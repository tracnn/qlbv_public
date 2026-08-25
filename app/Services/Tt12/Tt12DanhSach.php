<?php

namespace App\Services\Tt12;

use App\Models\BHYT\Tt12\Tt12HoSo;

/**
 * MOT noi dung truy van danh sach ho so.
 *
 * Man hinh, xuat Excel va man ky/gui hang loat deu goi ham nay. Viet lai dieu kien loc o
 * ba cho la ba ban se lech nhau - va nguoi dung se thay bang tren man hinh mot dang, tep
 * Excel xuat ra mot dang khac.
 */
class Tt12DanhSach
{
    /**
     * @param array $loc mau, ma_cskcb, imported_by, trang_thai, tu_ngay, den_ngay, tim
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function truyVan(array $loc)
    {
        $q = Tt12HoSo::query();

        if (!empty($loc['mau'])) {
            $q->where('mau', $loc['mau']);
        }

        if (!empty($loc['ma_cskcb'])) {
            $q->where('ma_cskcb', $loc['ma_cskcb']);
        }

        // !empty chu khong isset: o chon co muc "Tat ca" mang value RONG, va chuoi rong
        // phai nghia la "khong loc" chu khong phai "loc theo nguoi co ten rong".
        if (!empty($loc['imported_by'])) {
            $q->where('imported_by', $loc['imported_by']);
        }

        if (!empty($loc['tu_ngay'])) {
            $q->where('imported_at', '>=', self::mocDau($loc['tu_ngay']));
        }

        if (!empty($loc['den_ngay'])) {
            $q->where('imported_at', '<=', self::mocCuoi($loc['den_ngay']));
        }

        if (!empty($loc['tim'])) {
            $tim = '%' . $loc['tim'] . '%';

            $q->where(function ($con) use ($tim) {
                $con->where('ma_ho_so', 'like', $tim)
                    ->orWhere('ten_tep', 'like', $tim)
                    ->orWhere('ma_gd', 'like', $tim);
            });
        }

        if (!empty($loc['trang_thai'])) {
            self::locTrangThai($q, $loc['trang_thai']);
        }

        return $q->orderBy('id', 'desc');
    }

    /**
     * Trang thai la DAN XUAT tu cac cot, khong phai mot cot rieng.
     *
     * Khong them cot 'trang_thai' vao bang: no se phai duoc cap nhat o nam cho khac nhau
     * (nap, kiem, ky, gui, dong bo) va chi can mot cho quen la bang noi doi.
     */
    private static function locTrangThai($q, $trangThai)
    {
        if ($trangThai === 'chua_kiem') {
            return $q->whereNull('checked_at');
        }

        if ($trangThai === 'con_loi') {
            return $q->whereNotNull('checked_at')->where('so_loi', '>', 0);
        }

        if ($trangThai === 'san_sang') {
            return $q->whereNotNull('checked_at')->where('so_loi', 0)->where('is_signed', false);
        }

        if ($trangThai === 'da_ky') {
            return $q->where('is_signed', true)->whereNull('ma_gd');
        }

        if ($trangThai === 'da_gui') {
            return $q->whereIn('ma_ket_qua', config('tt12.ma_thanh_cong', array('200')));
        }

        if ($trangThai === 'loi_gui') {
            return $q->whereNotNull('ma_ket_qua')
                ->whereNotIn('ma_ket_qua', config('tt12.ma_thanh_cong', array('200')));
        }

        return $q;
    }

    /**
     * Moc dau khoang loc.
     *
     * Bo chon khoang thoi gian (partials.date_range) gui len dang 'YYYY-MM-DD HH:mm:ss',
     * con o ngay tran gui 'YYYY-MM-DD'. Cu noi ' 00:00:00' vo dieu kien vao ca hai thi
     * dang co gio thanh '2026-08-19 10:00:00 00:00:00' - MySQL doc khong ra va tra ve
     * rong. Nhan dien bang dau hai cham, giong CtdtDanhSach::mocDau().
     *
     * PUBLIC vi Tt12NhatKyGuiExport phai chuan hoa dung mot kieu voi man hinh. Viet ban
     * chuan hoa thu hai o ben do la tao co hoi cho hai ban lech nhau.
     */
    public static function mocDau($giaTri)
    {
        $giaTri = trim((string) $giaTri);

        return strpos($giaTri, ':') === false ? $giaTri . ' 00:00:00' : $giaTri;
    }

    /**
     * Moc cuoi khoang loc.
     *
     * Voi ngay tran phai la 23:59:59, khong phai '<= ngay'. So sanh chuoi ngay tran tren
     * cot datetime se bo het ho so nap trong chinh ngay do tru dung luc 00:00:00.
     *
     * PUBLIC cung mot ly do voi mocDau().
     */
    public static function mocCuoi($giaTri)
    {
        $giaTri = trim((string) $giaTri);

        return strpos($giaTri, ':') === false ? $giaTri . ' 23:59:59' : $giaTri;
    }

    /**
     * Bu khoang ngay mac dinh khi loi goi khong co.
     *
     * Goi thang URL xuat (hoac bam nut truoc khi man hinh kip gan tt12LocDaTai) ma khong
     * kem tham so se quet TOAN BO tt12_lich_su_gui. Tren may chu gioi han PHP 128MB/120s
     * do la mot yeu cau chet giua chung, khong phai mot tep xuat lon. Khuon
     * CtdtDanhSach::khoangMacDinh().
     *
     * KHONG de len gia tri nguoi dung da chon: mot bo loc bi bo qua am tham te hon han
     * loi no dang chua.
     *
     * @param array $loc
     * @param int   $soNgayLui
     * @return array
     */
    public static function khoangMacDinh(array $loc, $soNgayLui = 30)
    {
        if (empty(trim((string) ($loc['tu_ngay'] ?? '')))) {
            $loc['tu_ngay'] = now()->subDays($soNgayLui)->format('Y-m-d');
        }

        if (empty(trim((string) ($loc['den_ngay'] ?? '')))) {
            $loc['den_ngay'] = now()->format('Y-m-d');
        }

        return $loc;
    }

    /** @return array [ma => nhan] cho o chon tren man hinh */
    public static function cacTrangThai()
    {
        return array(
            'chua_kiem' => 'Chưa kiểm',
            'con_loi'   => 'Còn lỗi',
            'san_sang'  => 'Sẵn sàng ký',
            'da_ky'     => 'Đã ký, chưa gửi',
            'da_gui'    => 'Đã được tiếp nhận',
            'loi_gui'   => 'Gửi lỗi',
        );
    }
}
