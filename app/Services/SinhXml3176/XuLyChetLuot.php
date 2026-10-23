<?php

namespace App\Services\SinhXml3176;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Xu ly khi mot luot sinhxml3176:quet chet vi PHP Fatal (het bo nho...). Fatal bo qua khoi `finally`
 * nen khoa luot (khoa_luot_phut) khong duoc nha; ham shutdown nay nha khoa va ghi log critical kem cac
 * treatment id dang nap de nguoi van hanh biet ho so nao lam chet luot.
 */
class XuLyChetLuot
{
    /** Vung nho giu san, giai phong dau tien trong ham shutdown de con bo nho cho Cache/Log */
    private static $duPhong;

    /** @var int[] treatment id dang nap/xu ly */
    private static $dangNap = [];

    private static $daDangKy = false;

    private static $khoa;

    /** Dang ky ham shutdown (mot lan moi tien trinh). */
    public static function dangKy($khoa)
    {
        self::$khoa = $khoa;
        self::$duPhong = str_repeat(' ', 1 << 20);
        self::$dangNap = [];
        if (!self::$daDangKy) {
            self::$daDangKy = true;
            register_shutdown_function(function () {
                self::xuLy(error_get_last());
            });
        }
    }

    /** Lo ho so dang nap (LuotQuet goi truoc nap(), xoa bang mang rong sau khi xu ly xong). */
    public static function datDangNap(array $ids)
    {
        self::$dangNap = $ids;
    }

    /** Luot ket thuc binh thuong: khong con gi de nha. */
    public static function ketThuc()
    {
        self::$dangNap = [];
    }

    /**
     * @param array|null $loi ket qua error_get_last()
     * @return bool true neu la loi chet va da nha khoa
     */
    public static function xuLy($loi)
    {
        if (!is_array($loi) || !self::laFatal(isset($loi['type']) ? (int) $loi['type'] : 0) || self::$khoa === null) {
            return false;
        }
        self::$duPhong = null;
        Cache::forget(self::$khoa);
        Log::critical('sinhxml3176:quet chet: ' . (isset($loi['message']) ? $loi['message'] : '')
            . ' treatment_id dang nap=' . implode(',', self::$dangNap));

        return true;
    }

    private static function laFatal($type)
    {
        return ($type & (E_ERROR | E_CORE_ERROR | E_COMPILE_ERROR | E_USER_ERROR)) !== 0;
    }
}
