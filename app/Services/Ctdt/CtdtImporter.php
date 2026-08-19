<?php

namespace App\Services\Ctdt;

use DB;
use App\Services\Ctdt\Loi\CtdtLoiNap;
use App\Services\Ctdt\Loi\ThieuMacskcbException;
use App\Services\Ctdt\Loi\MacskcbKhongHopLeException;

/**
 * Diem vao DUY NHAT de nhap mot goi chung tu dien tu.
 *
 * VI SAO DUY NHAT: trong XML3176, nghiep vu nay tung duoc cai HAI lan - mot lan trong
 * controller tai len tay, mot lan trong lenh console quet thu muc - va hai ban DA LECH
 * NHAU. Cung mot ho so cho hai ket qua khac nhau tuy duong vao. Man tai len (Giai doan 2B)
 * va lenh console (Giai doan 5) deu goi ham nay.
 */
class CtdtImporter
{
    /** @var CtdtLuuHoSo */
    private $luu;

    public function __construct(CtdtLuuHoSo $luu = null)
    {
        $this->luu = $luu ?: new CtdtLuuHoSo();
    }

    /**
     * @param string $noiDungXml Noi dung goi HSCHUNGTU / HSDLGBT / HSDLGCS
     * @param array  $tuyChon    macskcb, imported_by, duong_dan_goc - deu tuy chon
     * @return CtdtImportFileResult
     */
    public function nhapTuChuoi($noiDungXml, array $tuyChon = [])
    {
        try {
            $goi     = CtdtGoiParser::doc($noiDungXml);
            $dichVu  = CtdtGoiParser::nhanDienDichVu($goi);
            $macskcb = $this->macskcb($goi, $dichVu, $tuyChon);
            $danhSach = CtdtGoiParser::danhSachHoSo($goi, $dichVu);
        } catch (CtdtLoiNap $e) {
            // Hong ngay tu dau tep: chua xu ly ho so nao.
            return CtdtImportFileResult::thatBaiSom($e->getMessage());
        }

        $idGoi   = CtdtGoiParser::idGoi($goi, $dichVu);
        $ngayLap = CtdtGoiParser::ngayLap($goi);
        $soKhaiBao = CtdtGoiParser::soLuongHoSo($goi);

        $ketQua = [];

        foreach ($danhSach as $i => $chungTu) {
            $ketQua[] = $this->nhapMotHoSo(
                $chungTu, $i + 1, $dichVu, $macskcb, $idGoi, $ngayLap, $soKhaiBao, $tuyChon
            );
        }

        return CtdtImportFileResult::tu($ketQua, $soKhaiBao, count($danhSach));
    }

    /**
     * Nhap MOT ho so. Moi ho so mot transaction RIENG.
     *
     * Mot ho so hong khong duoc keo cac ho so con lai xuong: mot tep 200 ho so ma mot cai
     * sai chinh ta ngay thang thi 199 cai kia van phai vao duoc.
     */
    private function nhapMotHoSo(
        array $chungTu, $chiSo, $dichVu, $macskcb, $idGoi, $ngayLap, $soKhaiBao, array $tuyChon
    ) {
        try {
            $chungTu = CtdtGoiParser::phanTichChungTu($chungTu, $chiSo);

            $maHoSo = CtdtMaHoSo::cua($chungTu, $idGoi, $chiSo);

            $moTa = [
                'ma_ho_so'       => $maHoSo,
                'id_goi_xml'     => $idGoi,
                'dich_vu'        => $dichVu,
                'loai_hs'        => (string) config('ctdt.dich_vu.' . $dichVu . '.loai_hs'),
                'macskcb'        => $macskcb,
                'ngay_lap'       => $ngayLap,
                'so_luong_ho_so' => $soKhaiBao,
                'imported_by'    => isset($tuyChon['imported_by']) ? $tuyChon['imported_by'] : null,
                'duong_dan_goc'  => isset($tuyChon['duong_dan_goc']) ? $tuyChon['duong_dan_goc'] : null,
                'chung_tu'       => $chungTu,
            ];

            // Mot ho so = mot transaction. Hong o dau cung quay lui sach, va viec xoa ban cu
            // nam BEN TRONG day nen du lieu cu con nguyen khi nap that bai.
            DB::transaction(function () use ($moTa) {
                $this->luu->luu($moTa);
            });

            return CtdtImportResult::thanhCong($maHoSo, array_column($chungTu, 'loai_ho_so'));
        } catch (CtdtLoiNap $e) {
            // CHI bat loi mang dau hieu CtdtLoiNap. Loi lap trinh va loi ha tang phai noi
            // len - nuot chung thanh "ho so nay hong" la cach chac chan de mot bug song
            // hang thang ma khong ai biet.
            \Log::warning('CTDT nhap that bai (ho so #' . $chiSo . '): ' . $e->getMessage());

            return CtdtImportResult::thatBai($e->getMessage());
        }
    }

    /**
     * Ma co so KCB, theo thu tu: XML -> tuy chon nguoi nap -> cau hinh don vi.
     *
     * Goi HSDLGCS khong mang ma co so o bat ky the nao (MA_TTDV la ma so BHXH cua Thu truong
     * co so, khong phai ma co so), nen chuoi lui nay la duong duy nhat cho GCS.
     *
     * @throws ThieuMacskcbException khi can ca ba nguon
     * @throws MacskcbKhongHopLeException khi ma phan giai duoc dai qua cot varchar(5)
     */
    private function macskcb(\SimpleXMLElement $goi, $dichVu, array $tuyChon)
    {
        $ma = CtdtGoiParser::macskcb($goi, $dichVu);

        if ($ma === null && !empty($tuyChon['macskcb'])) {
            $ma = trim((string) $tuyChon['macskcb']);
        }

        if ($ma === null || $ma === '') {
            $ma = trim((string) config('organization.BHYT.ma_cskcb', ''));
        }

        if ($ma === '') {
            throw new ThieuMacskcbException(
                'Khong xac dinh duoc ma co so KCB: goi khong khai, nguoi nap khong chon,'
                . ' va cau hinh organization.BHYT.ma_cskcb dang trong'
            );
        }

        // Cot ctdt_ho_so.macskcb la varchar(5). SQLite cua test khong cuong che do dai nen
        // khong tu bat duoc; tren MySQL strict mode se la QueryException KHONG mang
        // CtdtLoiNap (thoat khoi catch cua importer, thanh 500 chua bat), con che do long
        // se cat cut im lang va gui sai ma co so len cong BHXH. Chan som tai day.
        if (strlen($ma) > 5) {
            throw new MacskcbKhongHopLeException(
                'Ma co so KCB khong hop le: "' . $ma . '" dai ' . strlen($ma)
                . ' ky tu, vuot qua gioi han 5 ky tu'
            );
        }

        return $ma;
    }
}
