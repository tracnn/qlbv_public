<?php

namespace App\Services\Ctdt;

use App\Models\BHYT\Ctdt\CtdtHoSo;

/**
 * Suy trang thai gui cua mot ho so, de man danh sach hien mot cot duy nhat.
 *
 * NAM LY DO "chua gui" la NAM chuyen khac nhau, va nguoi van hanh phai phan biet duoc:
 *   chua kiem -> bo kiem chua chay: xem worker hang doi JobCtdt con song khong
 *   con loi   -> di sua ho so
 *   chua ky   -> di ky so
 *   gui tat   -> bao quan tri bat cau hinh
 *   cong tu choi -> doc ma loi cua cong
 * Gop lai thanh mot chu "chua gui" la de nguoi ta ngoi cho mot ho so vinh vien khong bao
 * gio duoc gui.
 *
 * Ham THUAN: nhan mot ban ghi, tra mot chuoi. Khong truy van gi them.
 */
class CtdtTrangThaiGui
{
    const CHUA_KIEM    = 'chua_kiem';
    const CON_LOI      = 'con_loi';
    const CHUA_KY      = 'chua_ky';
    const GUI_TAT      = 'gui_tat';
    const DA_GUI       = 'da_gui';
    const CONG_TU_CHOI = 'cong_tu_choi';
    const CHUA_GUI     = 'chua_gui';

    const NHAN = [
        self::CHUA_KIEM    => 'Chưa kiểm',
        self::CON_LOI      => 'Còn lỗi chặn',
        self::CHUA_KY      => 'Chưa ký số',
        self::GUI_TAT      => 'Chức năng gửi đang tắt',
        self::DA_GUI       => 'Đã gửi',
        self::CONG_TU_CHOI => 'Cổng từ chối',
        self::CHUA_GUI     => 'Chờ gửi',
    ];

    /**
     * THU TU quan trong: bao ly do GAN NHAT truoc. Mot ho so vua con loi vua chua ky thi
     * viec can lam truoc la sua loi.
     */
    public static function cua($hoSo)
    {
        // "Chua kiem" KHAC "da kiem va sach", du ca hai deu co so_loi = 0. Ho so vua nap
        // xong - hay MOI ho so tren may chu chua cai dich vu JobCtdt - roi vao nhanh nay.
        // Khong tach ra thi cua chan gui chi dong khi hang doi dang chay, va khong co gi
        // bao cho ai biet khi no khong chay.
        if (empty($hoSo->checked_at)) {
            return self::CHUA_KIEM;
        }

        if ((int) $hoSo->so_loi > 0) {
            return self::CON_LOI;
        }

        // Ep ve bool: is_signed doc tu MySQL tinyint(1) ve dang 0/1. So sanh === true se
        // coi MOI ho so la chua ky.
        if (!(bool) $hoSo->is_signed) {
            return self::CHUA_KY;
        }

        // Da co ket qua tu cong thi ket qua do thang cau hinh: cau hinh co the vua bi tat
        // sau khi ho so da gui xong, va luc do hien "dang tat" la noi sai.
        if (!empty($hoSo->ma_ket_qua)) {
            // So sanh LONG: cong co the tra ve so 200 thay vi chuoi '200'. So sanh nghiem
            // ngat se coi mot ho so THANH CONG la bi tu choi.
            return (string) $hoSo->ma_ket_qua == '200' ? self::DA_GUI : self::CONG_TU_CHOI;
        }

        if (!(bool) config('organization.chung_tu_dien_tu.submit_enabled', false)) {
            return self::GUI_TAT;
        }

        return self::CHUA_GUI;
    }

    /** @return string Nhan tieng Viet; ma la thi tra chinh ma de khong bao gio hien o trong */
    public static function nhan($ma)
    {
        return isset(self::NHAN[$ma]) ? self::NHAN[$ma] : (string) $ma;
    }
}
