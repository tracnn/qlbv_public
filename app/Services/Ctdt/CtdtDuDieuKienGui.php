<?php

namespace App\Services\Ctdt;

/**
 * Tra loi "ho so nay bam gui duoc chua, neu chua thi vi sao" - cho CA nut don le lan nut
 * gui hang loat.
 *
 * VI SAO TACH RA: chuoi cua chan nay truoc do nam thang trong BHYTCtdtController::kyVaGui().
 * Nut gui hang loat can dung chuoi do, va chep tay lan hai la cach chac chan nhat de hai
 * duong lech nhau - dung dieu da xay ra that voi XML3176, va la ly do CtdtXepHangKyGui ton
 * tai. Lech o day khong phai loi hien thi: mot cua chan co o duong don le ma thieu o duong
 * hang loat nghia la lo 50 ho so gui duoc thu ma nguoi bam tung cai khong gui duoc.
 *
 * KHONG dat khoa va KHONG dispatch gi ca - do la viec cua CtdtXepHangKyGui. Lop nay chi
 * quyet dinh.
 */
class CtdtDuDieuKienGui
{
    /** Du dieu kien: goi CtdtXepHangKyGui::xep() duoc */
    const DUOC = 'duoc';

    /** Cau hinh gui dang tat */
    const GUI_TAT = 'gui_tat';

    /** Bo kiem chua chay xong */
    const CHUA_KIEM = 'chua_kiem';

    /** Con loi muc chan */
    const CON_LOI = 'con_loi';

    /** Chuc nang ky dang tat, va ho so nay chua ky */
    const KY_TAT = 'ky_tat';

    /** Da tung gui len cong nhung dau vet bi nap lai xoa - phai xac nhan */
    const CAN_XAC_NHAN = 'can_xac_nhan';

    /** Cong da tiep nhan va ho so con giu ma giao dich */
    const DA_CO_MA_GD = 'da_co_ma_gd';

    /**
     * @param  \App\Models\BHYT\Ctdt\CtdtHoSo $hoSo
     * @return string mot trong cac hang so tren
     */
    public static function cua($hoSo)
    {
        $quyetDinh = CtdtQuyetDinhGui::nen(
            config('organization.chung_tu_dien_tu.submit_enabled', false),
            $hoSo->checked_at,
            $hoSo->so_loi,
            // KHONG truyen $hoSo->is_signed: ho so chua ky la binh thuong o day - ta sap ky
            // no. Truyen gia tri that se lam moi ho so chua ky bi tu choi ngay tai cua nay.
            true
        );

        if ($quyetDinh !== CtdtQuyetDinhGui::GUI) {
            return self::anhXa($quyetDinh);
        }

        // Chuc nang ky tat + ho so CHUA ky = bam nut cung khong di den dau. Ho so DA ky roi
        // thi van gui lai duoc binh thuong: khong can ky lai.
        $kyBat = (bool) config('organization.chung_tu_dien_tu.sign_enabled', false);

        if (!$kyBat && !(bool) $hoSo->is_signed) {
            return self::KY_TAT;
        }

        // Nap lai xoa ma_gd/ma_ket_qua (noi dung da doi thi ket qua cu noi ve mot ban khac),
        // nen mot ho so DA duoc cong nhan that se hien "Chua ky so" va gui lai duoc ma khong
        // co gi canh bao - dau vet chi con o lich_su_gui, von chi hien o man chi tiet.
        //
        // Dieu kien phai dung cho CA HAI ca: CtdtLuuHoSo::noiLichSu() ghi mot dong lich su
        // khi ma_gd HOAC ma_ket_qua khac rong, nen mot ho so tung bi cong TU CHOI (chi co
        // ma_ket_qua) cung thoa dieu kien nay.
        if (!empty($hoSo->lich_su_gui) && empty($hoSo->ma_gd)) {
            return self::CAN_XAC_NHAN;
        }

        // Ho so CON GIU ma giao dich: cong da tiep nhan, va no KHONG roi vao nhanh
        // CAN_XAC_NHAN o tren (nhanh do doi ma_gd rong). Duong don le cho gui lai va do la
        // co y - nhan nut doi thanh "Ky va gui lai", nguoi bam dang nhin man chi tiet cua
        // dung ho so do. Duong HANG LOAT thi khong: mot dong trong lo 50 dong se duoc gui
        // lai im lang, khong mot cau hoi nao.
        //
        // Tra ve mot ma RIENG chu khong gop vao CAN_XAC_NHAN: hai chuyen khac nhau, va
        // duong don le phai phan biet duoc de giu nguyen hanh vi cu cua no.
        if (!empty($hoSo->ma_gd)) {
            return self::DA_CO_MA_GD;
        }

        return self::DUOC;
    }

    /**
     * Ly do doc duoc cho nguoi bam nut, khong phai ma trang thai.
     *
     * @param  string $ma
     * @return string
     */
    public static function lyDo($ma)
    {
        $ly = [
            self::GUI_TAT      => 'Chức năng gửi đang tắt trong cấu hình. Liên hệ quản trị để bật.',
            self::CHUA_KIEM    => 'Hồ sơ chưa kiểm — công việc kiểm còn nằm trong hàng đợi. Thử lại sau ít phút.',
            self::CON_LOI      => 'Hồ sơ còn lỗi chặn gửi. Xem tab Lỗi, sửa ở phần mềm sinh XML rồi nạp lại.',
            self::KY_TAT       => 'Chức năng ký số đang tắt trong cấu hình, và hồ sơ này chưa ký. Liên hệ quản trị để bật.',
            self::CAN_XAC_NHAN => 'Hồ sơ này đã từng được gửi lên cổng BHXH, nhưng dấu vết đã bị '
                . 'xoá khi nạp lại. Xem tab lịch sử gửi ở màn chi tiết trước khi gửi lại.',
            self::DA_CO_MA_GD  => 'Cổng BHXH đã tiếp nhận hồ sơ này và đã cấp mã giao dịch. '
                . 'Muốn gửi lại thì mở màn chi tiết, xem lịch sử gửi rồi bấm "Ký và gửi lại".',
        ];

        return isset($ly[$ma]) ? $ly[$ma] : 'Hồ sơ chưa đủ điều kiện gửi.';
    }

    /**
     * Anh xa TUONG MINH tu ma cua CtdtQuyetDinhGui sang ma cua lop nay.
     *
     * Khong tra thang gia tri cua lop kia du hai ben tinh co dung chung chuoi
     * 'chua_kiem'/'con_loi': dua vao su trung hop do la buoc mot lop phai xin phep lop kia
     * moi doi duoc gia tri hang so cua chinh minh.
     *
     * @param  string $quyetDinh
     * @return string
     */
    private static function anhXa($quyetDinh)
    {
        switch ($quyetDinh) {
            case CtdtQuyetDinhGui::KHONG_GUI:
                return self::GUI_TAT;

            case CtdtQuyetDinhGui::CHUA_KIEM:
                return self::CHUA_KIEM;

            case CtdtQuyetDinhGui::CON_LOI:
                return self::CON_LOI;
        }

        // CHUA_KY khong the roi vao day: cua tren goi nen() voi $daKy = true co y.
        return self::CHUA_KIEM;
    }
}
