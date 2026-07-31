<?php

namespace App\Services\BHYT;

/**
 * Tra nhan tieng Viet cho cac ma ket qua tra cuu the BHYT.
 *
 * Vi sao can ham rieng: cac blade san co viet config('__tech.insurance_error_code')[$ma] -
 * truy cap mang KHONG PHONG VE. Gap mot ma la (cong BHXH them ma moi, hoac du lieu cu) la
 * "Undefined index" va trang trang. Tren mot danh sach nhieu dong thi som muon cung dinh.
 *
 * O day: khong co nhan thi tra chinh MA TRAN - van doc duoc, khong vo trang.
 *
 * Ham THUAN de kiem duoc.
 */
class NhanMaThe
{
    /**
     * @param mixed $ma ma can tra
     * @param array $bang bang nhan, vi du config('__tech.insurance_error_code')
     * @return string nhan neu co; ma tran neu khong co; chuoi rong neu ma rong
     */
    public static function nhan($ma, array $bang)
    {
        $ma = trim((string) $ma);

        if ($ma === '') {
            return '';
        }

        return isset($bang[$ma]) ? (string) $bang[$ma] : $ma;
    }

    /** Nhan cho ma_tracuu va ma_ketqua. */
    public static function traCuu($ma)
    {
        return self::nhan($ma, (array) config('__tech.insurance_error_code', []));
    }

    /** Nhan cho ma_kiemtra. */
    public static function kiemTra($ma)
    {
        return self::nhan($ma, (array) config('__tech.check_insurance_code', []));
    }
}
