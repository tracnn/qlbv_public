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
            $q->where('imported_at', '>=', $loc['tu_ngay'] . ' 00:00:00');
        }

        if (!empty($loc['den_ngay'])) {
            $q->where('imported_at', '<=', $loc['den_ngay'] . ' 23:59:59');
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
