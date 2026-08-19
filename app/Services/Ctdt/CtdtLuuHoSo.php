<?php

namespace App\Services\Ctdt;

use App\Models\BHYT\Ctdt\CtdtHoSo;
use App\Models\BHYT\Ctdt\CtdtChungTu;
use App\Models\BHYT\Ctdt\CtdtLoi;
use App\Services\Ctdt\Loi\HoSoRongException;

/**
 * Tang ghi CSDL cua luong nap. TACH KHOI CtdtImporter co chu dich: importer lo viec phan
 * ra ho so va bat loi tung ho so, lop nay lo viec ghi dung mot ho so - hai trach nhiem
 * kiem duoc doc lap.
 *
 * KHONG tu mo transaction: CtdtImporter bao MOT transaction quanh MOI ho so, va long
 * transaction trong nhau tren MySQL khong cho savepoint nhu nguoi ta tuong.
 */
class CtdtLuuHoSo
{
    /**
     * Xoa du lieu cu cua mot ho so, GIU LAI ban ghi ctdt_ho_so.
     *
     * Giu ctdt_ho_so vi no mang lich_su_gui, ma_gd, ma_ket_qua - dau vet doi soat voi BHXH.
     * Xoa ctdt_loi TRUOC ctdt_chung_tu de khong bao gio ton tai mot khoanh khac ma loi tro
     * toi chung tu da bien mat.
     */
    public function xoaHoSoCu($maHoSo)
    {
        $hoSo = CtdtHoSo::where('ma_ho_so', $maHoSo)->first();

        if ($hoSo === null) {
            return;
        }

        CtdtLoi::where('ho_so_id', $hoSo->id)->delete();

        foreach (CtdtChungTu::where('ho_so_id', $hoSo->id)->get() as $chungTu) {
            $this->xoaChiTiet($chungTu);
            $chungTu->delete();
        }
    }

    /**
     * Ghi mot ho so. Ghi de sach ban cu neu da co.
     *
     * @param array $hoSo Xem khoi Interfaces cua Task 6 trong ke hoach
     * @return CtdtHoSo
     */
    public function luu(array $hoSo)
    {
        // Bat bien cua tang ghi: mot ho so PHAI co it nhat mot chung tu. Nem TRUOC khi xoa
        // bat cu gi - <HOSO/> rong (hoac tat ca FILEHOSO da bi loai bo truoc do) khong duoc
        // phep di toi xoaHoSoCu() roi ghi lai voi so_chung_tu = 0, vi the la xoa sach du
        // lieu cu cua mot lan nap truoc trong khi bao "thanh cong".
        if (empty($hoSo['chung_tu'])) {
            throw new HoSoRongException(
                'Ho so ma_ho_so=' . $hoSo['ma_ho_so'] . ' khong co chung tu nao (HOSO rong)'
            );
        }

        // Kiem TOAN BO loai va the goc TRUOC khi ghi bat cu gi. Phat hien lech o giua chung
        // se de lai mot ho so nap do dang neu noi goi quen bao transaction.
        foreach ($hoSo['chung_tu'] as $ct) {
            CtdtLoaiRegistry::xacNhanTheGoc($ct['loai_ho_so'], $ct['noi_dung']->getName());
        }

        $this->xoaHoSoCu($hoSo['ma_ho_so']);

        $banGhi = $this->ghiHoSo($hoSo);

        foreach ($hoSo['chung_tu'] as $ct) {
            $this->ghiChungTu($banGhi, $ct);
        }

        return $banGhi->fresh();
    }

    /**
     * Tao moi hoac cap nhat ctdt_ho_so, reset trang thai ky/gui va noi lich su.
     */
    private function ghiHoSo(array $hoSo)
    {
        $cu = CtdtHoSo::where('ma_ho_so', $hoSo['ma_ho_so'])->first();

        $thuocTinh = [
            'id_goi_xml'     => $hoSo['id_goi_xml'],
            'dich_vu'        => $hoSo['dich_vu'],
            'loai_hs'        => $hoSo['loai_hs'],
            'macskcb'        => $hoSo['macskcb'],
            'ngay_lap'       => $hoSo['ngay_lap'],
            'so_luong_ho_so' => $hoSo['so_luong_ho_so'],
            'so_chung_tu'    => count($hoSo['chung_tu']),
            'duong_dan_goc'  => $hoSo['duong_dan_goc'],
            'imported_at'    => now(),
            'imported_by'    => $hoSo['imported_by'],
            'import_error'   => null,

            // Noi dung da doi thi chu ky cu khong con ung voi noi dung moi, va ket qua gui
            // cu noi ve mot ban khac. Giu lai la noi doi voi nguoi doc man danh sach.
            'checked_at'          => null,
            'so_loi'              => 0,
            'is_signed'           => false,
            'sign_method'         => null,
            'signed_at'           => null,
            'signed_error'        => null,
            'submitted_at'        => null,
            'submitted_by'        => null,
            'submit_error'        => null,
            'submitted_message'   => null,
            'ma_gd'               => null,
            'ma_ket_qua'          => null,
            'thoi_gian_tiep_nhan' => null,
        ];

        if ($cu === null) {
            $thuocTinh['ma_ho_so'] = $hoSo['ma_ho_so'];

            return CtdtHoSo::create($thuocTinh);
        }

        $lichSu = $this->noiLichSu($cu);

        if ($lichSu !== null) {
            $thuocTinh['lich_su_gui'] = $lichSu;
        }

        $cu->update($thuocTinh);

        return $cu;
    }

    /**
     * Noi mot dong vao lich_su_gui neu ban cu DA TUNG duoc gui.
     *
     * @return string|null null khi chua tung gui - khong ghi dong rong lam nhieu
     */
    private function noiLichSu(CtdtHoSo $cu)
    {
        if (empty($cu->ma_gd) && empty($cu->ma_ket_qua)) {
            return null;
        }

        $dong = '[' . now()->format('Y-m-d H:i:s') . '] nap lai, ban truoc:'
            . ' MaGD=' . (string) $cu->ma_gd
            . ' MaKetQua=' . (string) $cu->ma_ket_qua
            . ' ThoiGianTiepNhan=' . (string) $cu->thoi_gian_tiep_nhan;

        return empty($cu->lich_su_gui) ? $dong : $cu->lich_su_gui . "\n" . $dong;
    }

    /**
     * Ghi mot chung tu: ban ghi chung + cot rut gon + ban ghi chi tiet theo loai.
     */
    private function ghiChungTu(CtdtHoSo $hoSo, array $ct)
    {
        $lop = CtdtLoaiRegistry::cho($ct['loai_ho_so']);
        $xml = $ct['noi_dung'];

        $rutGon = $lop::rutGon($xml);

        $chungTu = CtdtChungTu::create(array_merge($rutGon, [
            'ho_so_id'     => $hoSo->id,
            'loai_ho_so'   => $ct['loai_ho_so'],
            'ma_chung_tu'  => $lop::maChungTu($xml),
            'noi_dung_goc' => $xml->asXML(),
        ]));

        $chiTiet = ['chung_tu_id' => $chungTu->id];

        foreach ($lop::truong() as $the => $cot) {
            if (!isset($xml->{$the})) {
                continue;
            }

            $chiTiet[$cot] = (string) $xml->{$the};
        }

        // The co trong XML nhung KHONG khai trong truong() bi bo qua co chu dich: BHXH them
        // the moi truoc khi ta kip cap nhat la chuyen se xay ra, va nem o day se lam ca ho
        // so hong chi vi mot the ta chua biet den. Luoi an toan CtdtToanVenTest canh chieu
        // nguoc lai - cot co trong bang ma khong khai trong truong().
        $tenModel = $lop::model();
        $tenModel::create($chiTiet);
    }

    /** Xoa ban ghi chi tiet ung voi mot chung tu, o dung bang cua loai do. */
    private function xoaChiTiet(CtdtChungTu $chungTu)
    {
        if (!CtdtLoaiRegistry::co($chungTu->loai_ho_so)) {
            // Loai la trong CSDL nghia la dang ky da bi thu hep sau khi du lieu duoc ghi.
            // Khong biet xoa o bang nao thi de lai con hon xoa nham bang khac.
            return;
        }

        $lop = CtdtLoaiRegistry::cho($chungTu->loai_ho_so);
        $tenModel = $lop::model();

        $tenModel::where('chung_tu_id', $chungTu->id)->delete();
    }
}
