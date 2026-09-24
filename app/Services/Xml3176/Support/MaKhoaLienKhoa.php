<?php

namespace App\Services\Xml3176\Support;

/**
 * Tach MA_KHOA lien chuyen khoa thanh cac khoa thanh phan.
 *
 * HIS ghi khoa lien chuyen khoa bang 'K' + cac cap 2 chu so noi lien: K103436 = K10, K34, K36.
 * Tren du lieu that 219/2.345 dong giuong dung dang nay (K3233, K0735, K024849, K103436,
 * K3029). Ma khong dung dang (so chu so le, co chu, khong bat dau bang K) tra ve nguyen ma -
 * noi goi tu quyet dinh cach so, khong doan cach tach.
 *
 * Ham thuan - khong cham DB/config.
 */
class MaKhoaLienKhoa
{
    /**
     * @param string|null $maKhoa
     * @return string[] cac khoa thanh phan; ma rong -> []
     */
    public static function tach($maKhoa)
    {
        $maKhoa = trim((string) $maKhoa);

        if ($maKhoa === '') {
            return [];
        }

        if (!self::dungDang($maKhoa)) {
            return [$maKhoa];
        }

        return array_map(function ($cap) {
            return 'K' . $cap;
        }, str_split(substr($maKhoa, 1), 2));
    }

    /** Ma co dang 'K' + mot hoac nhieu cap 2 chu so (tach duoc). */
    public static function dungDang($maKhoa)
    {
        return (bool) preg_match('/^K(?:\d{2})+$/', trim((string) $maKhoa));
    }

    /** Ma gom tu hai khoa tro len. */
    public static function laLienKhoa($maKhoa)
    {
        return count(self::tach($maKhoa)) > 1;
    }
}
