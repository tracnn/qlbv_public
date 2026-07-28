<?php

namespace App\Services\Xml3176;

use DB;
use App\Jobs\CheckXml3176TypeJob;
use App\Services\Xml3176Service;
use App\Services\XmlStructures;

/**
 * Diem vao DUY NHAT de nhap mot ho so XML3176.
 *
 * Truoc day nghiep vu nay duoc cai dat hai lan - trong BHYTXml3176Controller (tai len
 * tay) va trong Console\Commands\XML3176Import (quet thu muc) - va hai ban DA lech
 * nhau: controller xu ly XML1-15, command xu ly XML1-18 va co them chinh sach
 * exportable_tt. Cung mot ho so cho hai ket qua khac nhau tuy duong vao.
 */
class Xml3176Importer
{
    /**
     * Anh xa LOAIHOSO -> phuong thuc luu tren Xml3176Service.
     *
     * Ba trang thai KHAC NHAU, dung lan lon:
     *   - co khoa, gia tri chuoi : goi phuong thuc do
     *   - co khoa, gia tri null  : BO QUA CO CHU DICH, khong ghi log
     *   - khong co khoa          : loai la, ghi Log::warning
     *
     * Su nhap nhang giua hai truong hop dau va cuoi chinh la thu da mat khi hai ban
     * cai dat lech nhau.
     */
    const LOAI_XML = [
        'XML1'  => 'storeXml3176Xml1',
        'XML2'  => 'storeXml3176Xml2',
        'XML3'  => 'storeXml3176Xml3',
        'XML4'  => 'storeXml3176Xml4',
        'XML5'  => 'storeXml3176Xml5',
        'XML6'  => 'storeXml3176Xml6',
        'XML7'  => 'storeXml3176Xml7',
        'XML8'  => 'storeXml3176Xml8',
        'XML9'  => 'storeXml3176Xml9',
        'XML10' => 'storeXml3176Xml10',
        'XML11' => 'storeXml3176Xml11',
        'XML12' => null,
        'XML13' => 'storeXml3176Xml13',
        'XML14' => 'storeXml3176Xml14',
        'XML15' => 'storeXml3176Xml15',
        'XML16' => null,
        'XML17' => null,
        'XML18' => null,
    ];

    protected $xml3176Service;

    public function __construct(Xml3176Service $xml3176Service)
    {
        $this->xml3176Service = $xml3176Service;
    }

    public static function coTrongDangKy($loai)
    {
        return is_string($loai) && array_key_exists($loai, self::LOAI_XML);
    }

    /**
     * @return string|null Ten phuong thuc, hoac null neu bo qua co chu dich
     */
    public static function handlerCho($loai)
    {
        return self::coTrongDangKy($loai) ? self::LOAI_XML[$loai] : null;
    }

    /**
     * So luong ho so khai trong phong bi.
     *
     * Ban cu dung count($xmldata->THONGTINHOSO->SOLUONGHOSO) - count() tren mot node la
     * dem so phan tu con nen LUON ra 1, bat ke gia tri that la bao nhieu.
     */
    public static function soLuongHoSo($xmldata)
    {
        if (!isset($xmldata->THONGTINHOSO->SOLUONGHOSO)) {
            return 0;
        }

        return (int) (string) $xmldata->THONGTINHOSO->SOLUONGHOSO;
    }

    /**
     * Thu tu duyet FILEHOSO, dua XML1 len dau.
     *
     * deleteExistingXml3176() chi chay khi gap XML1. Neu mot file liet ke XML2 truoc
     * XML1 thi cac dong XML2 vua ghi bi xoa ngay sau do - im lang.
     *
     * @param array $danhSachLoai Cac chuoi LOAIHOSO theo dung thu tu trong file
     * @return array Mang CHI SO theo thu tu can duyet
     */
    public static function sapXml1LenDau(array $danhSachLoai)
    {
        $dau = [];
        $sau = [];

        foreach ($danhSachLoai as $i => $loai) {
            // Chi dua XML1 DAU TIEN len; cai thu hai (neu co) giu thu tu cu.
            if ($loai === 'XML1' && empty($dau)) {
                $dau[] = $i;
            } else {
                $sau[] = $i;
            }
        }

        return array_merge($dau, $sau);
    }

    /**
     * Nhap MOT ho so tu chuoi XML.
     *
     * @param string $noiDungXml Noi dung file GIAMDINHHS
     * @param array  $tuyChon    ['cho_phep_xuat' => bool] - mac dinh true
     * @return Xml3176ImportResult
     */
    public function nhapTuChuoi($noiDungXml, array $tuyChon = [])
    {
        $choPhepXuat = array_key_exists('cho_phep_xuat', $tuyChon)
            ? (bool) $tuyChon['cho_phep_xuat']
            : true;

        // simplexml_load_string phat warning voi chuoi hong; tat di va tu bao loi.
        $truocDo = libxml_use_internal_errors(true);
        $xmldata = @simplexml_load_string($noiDungXml);
        libxml_clear_errors();
        libxml_use_internal_errors($truocDo);

        if ($xmldata === false) {
            return Xml3176ImportResult::thatBai('Khong doc duoc noi dung XML');
        }

        if (!isset($xmldata->THONGTINDONVI->MACSKCB)
            || trim((string) $xmldata->THONGTINDONVI->MACSKCB) === '') {
            \Log::error('MACSKCB not found or is empty in XML data');

            return Xml3176ImportResult::thatBai('Thieu MACSKCB trong noi dung XML');
        }

        $macskcb = (string) $xmldata->THONGTINDONVI->MACSKCB;

        $soluonghoso = self::soLuongHoSo($xmldata);

        // Ca hai ban cu deu foreach thang vao day: DANHSACHHOSO rong thi foreach chay
        // tren null va nem exception. Duong tai len bien thanh loi 500, duong quet thu
        // muc bi try/catch ngoai nuot roi dung ca luot. Chan lai thanh mot ket qua
        // that bai sach se.
        if (!isset($xmldata->THONGTINHOSO->DANHSACHHOSO->HOSO->FILEHOSO)) {
            return Xml3176ImportResult::thatBai('Khong tim thay FILEHOSO trong file');
        }

        // Gom thanh mang de sap duoc thu tu: XML1 phai duoc xu ly TRUOC, vi
        // deleteExistingXml3176() chi chay khi gap no.
        $danhSachFile = [];
        $danhSachLoai = [];

        foreach ($xmldata->THONGTINHOSO->DANHSACHHOSO->HOSO->FILEHOSO as $file_hs) {
            $danhSachFile[] = $file_hs;
            $danhSachLoai[] = (string) $file_hs->LOAIHOSO;
        }

        $ma_lk = null;
        $processedFileTypes = [];

        try {
            // Mot ho so = mot transaction. Hong o dau cung quay lui sach, va vi
            // deleteExistingXml3176() nam trong day nen DU LIEU CU CON NGUYEN.
            //
            // Job kiem loi tung dong duoc dispatch BEN TRONG day la co chu dich:
            // hang doi dung driver database tren cung connection nen rollback xoa
            // luon cac job do.
            DB::transaction(function () use (
                $danhSachFile, $danhSachLoai, $macskcb, $soluonghoso, &$ma_lk, &$processedFileTypes
            ) {
                foreach (self::sapXml1LenDau($danhSachLoai) as $i) {
                    $file_hs  = $danhSachFile[$i];
                    $fileType = $danhSachLoai[$i];

                    if (!self::coTrongDangKy($fileType)) {
                        \Log::warning('Unknown XML type: ' . $fileType);
                        continue;
                    }

                    $handler = self::handlerCho($fileType);

                    if ($handler === null) {
                        continue;   // bo qua co chu dich
                    }

                    $data = simplexml_load_string(base64_decode($file_hs->NOIDUNGFILE));

                    if ($data === false) {
                        throw new \RuntimeException('Noi dung ' . $fileType . ' khong doc duoc');
                    }

                    if ($fileType === 'XML1') {
                        $expectedStructure = XmlStructures::$expectedStructures3176[$fileType] ?? [];

                        if (!empty($expectedStructure) && !validateDataStructure($data, $expectedStructure)) {
                            throw new \RuntimeException('Sai cau truc du lieu ' . $fileType);
                        }

                        $ma_lk = (string) $data->MA_LK;
                        $this->xml3176Service->deleteExistingXml3176($ma_lk);
                    }

                    $processedFileTypes[] = $fileType;
                    $this->xml3176Service->{$handler}($data, $fileType);
                }

                if ($ma_lk === null || empty($processedFileTypes)) {
                    throw new \RuntimeException('Khong tim thay du lieu ho so hop le trong file');
                }

                $this->xml3176Service->storeXml3176Information($ma_lk, $macskcb, 'import', $soluonghoso);
            });
        } catch (\Exception $e) {
            \Log::error('Import that bai' . ($ma_lk ? ' (' . $ma_lk . ')' : '') . ': ' . $e->getMessage());

            return Xml3176ImportResult::thatBai($e->getMessage());
        }

        // Mot job cho moi loai da xu ly, thay vi mot job moi dong. Dat sau commit de job
        // khong tro toi du lieu chua ton tai. Dispatch TRUOC checkXml3176Complete de giu
        // dung thu tu FIFO hien nay: kiem tung loai truoc, kiem tong the sau.
        foreach (array_unique($processedFileTypes) as $loai) {
            if (Xml3176CheckTypes::coChecker($loai)) {
                CheckXml3176TypeJob::dispatch($ma_lk, $loai)
                    ->onQueue(config('xml3176.queue_name'));
            }
        }

        // Sau commit: hai ham nay chi day job, dat o day de rollback khong de lai
        // job mo coi tro toi du lieu khong ton tai.
        if (!config('organization.xml_3176_not_check', false)) {
            $this->xml3176Service->checkXml3176Complete($ma_lk);
        }

        if ($choPhepXuat && config('xml3176.export_xml3176_enabled')) {
            $this->xml3176Service->exportXml3176($ma_lk);
        }

        return Xml3176ImportResult::thanhCong($ma_lk, $processedFileTypes);
    }
}
