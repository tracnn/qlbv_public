<?php

namespace App\Services\Xml3176\Support;

/**
 * Chuẩn hoá văn bản để so trùng: trim, gộp khoảng trắng, hạ chữ thường (UTF-8).
 * KHÔNG bỏ dấu — giữ nguyên tiếng Việt để tránh gộp nhầm nội dung khác nhau.
 */
class TextNormalizer
{
    public static function chuan($s): string
    {
        if ($s === null) {
            return '';
        }
        $s = preg_replace('/\s+/u', ' ', trim((string) $s));
        return function_exists('mb_strtolower') ? mb_strtolower($s, 'UTF-8') : strtolower($s);
    }

    /**
     * Hai chuoi co bang nhau sau khi chuan hoa khong.
     *
     * So NGHIEM NGAT (===). Phep so long (==) cua PHP 7 coi '1e3' == '1000' la bang.
     */
    public static function bang($a, $b): bool
    {
        return self::chuan($a) === self::chuan($b);
    }
}
