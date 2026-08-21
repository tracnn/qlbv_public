<?php

namespace App\Services\Ctdt;

use DB;
use App\Jobs\CheckCtdtJob;
use App\Models\BHYT\Ctdt\CtdtHoSo;
use App\Services\Ctdt\CtdtHangDoi;
use App\Services\Ctdt\CtdtMacskcb;
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
            $macskcb = CtdtMacskcb::phanGiai(
                CtdtGoiParser::macskcb($goi, $dichVu),
                isset($tuyChon['macskcb']) ? $tuyChon['macskcb'] : null
            );
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
     * Nhap tu mot tep tren dia.
     *
     * VI SAO NAM O DAY chu khong o controller: man tai len (Giai doan 2B) va lenh console
     * (Giai doan 5) deu can no. De o controller thi lenh console se viet lai ban thu hai, va
     * hai ban se lech nhau - dung dieu da xay ra that voi XML3176.
     *
     * @param string $duongDan
     * @param array  $tuyChon  macskcb, imported_by, duong_dan_goc - deu tuy chon
     * @return CtdtImportFileResult
     */
    public function nhapTuTep($duongDan, array $tuyChon = [])
    {
        if (!is_file($duongDan) || !is_readable($duongDan)) {
            return CtdtImportFileResult::thatBaiSom('Khong doc duoc tep: ' . $duongDan);
        }

        $noiDung = file_get_contents($duongDan);

        if ($noiDung === false) {
            return CtdtImportFileResult::thatBaiSom('Khong doc duoc tep: ' . $duongDan);
        }

        // Mac dinh ghi lai chinh duong dan da doc. Bat noi goi tu truyen lai mot lan nua la
        // moi khi mot noi goi quen mat dau vet nguon cua ho so.
        if (!array_key_exists('duong_dan_goc', $tuyChon)) {
            $tuyChon['duong_dan_goc'] = $duongDan;
        }

        return $this->nhapTuChuoi($noiDung, $tuyChon);
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

            // Doc TRUOC transaction: sau khi luu() chay xong thi cot nay da bi reset ve null.
            $maGdBiGhiDe = CtdtHoSo::where('ma_ho_so', $maHoSo)->value('ma_gd');

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

            // SAU COMMIT, khong phai trong transaction: job dat trong transaction se tro
            // toi du lieu chua ton tai neu rollback. Ghi chu nay da co trong Xml3176Importer
            // va van dung nguyen o day.
            //
            // Moi ho so MOT job rieng: mot ho so hong khong lam mat ket qua kiem cua cac
            // ho so con lai trong cung mot tep.
            CheckCtdtJob::dispatch($maHoSo)
                ->onQueue(CtdtHangDoi::kiem());

            return CtdtImportResult::thanhCong(
                $maHoSo,
                array_column($chungTu, 'loai_ho_so'),
                $maGdBiGhiDe
            );
        } catch (CtdtLoiNap $e) {
            // CHI bat loi mang dau hieu CtdtLoiNap. Loi lap trinh va loi ha tang phai noi
            // len - nuot chung thanh "ho so nay hong" la cach chac chan de mot bug song
            // hang thang ma khong ai biet.
            \Log::warning('CTDT nhap that bai (ho so #' . $chiSo . '): ' . $e->getMessage());

            return CtdtImportResult::thatBai($e->getMessage());
        }
    }
}
