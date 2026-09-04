<?php

namespace App\Services\Xml3176\Support;

use DateTime;

/**
 * Parse/so/diff chuỗi ngày-giờ XML3176 (YmdHis=14, YmdHi=12, Ymd=8).
 */
class Xml3176DateHelper
{
    public static function toDateTime($s): ?DateTime
    {
        if ($s === null) {
            return null;
        }
        $s = trim((string) $s);
        if (!ctype_digit($s)) {
            return null;
        }
        $len = strlen($s);
        $fmt = $len === 14 ? 'YmdHis' : ($len === 12 ? 'YmdHi' : ($len === 8 ? 'Ymd' : null));
        if ($fmt === null) {
            return null;
        }
        $dt  = DateTime::createFromFormat('!' . $fmt, $s);
        $err = DateTime::getLastErrors();
        if ($dt === false || $err['warning_count'] > 0 || $err['error_count'] > 0) {
            return null;
        }
        return $dt;
    }

    public static function datePart($s): ?string
    {
        $dt = self::toDateTime($s);
        return $dt ? $dt->format('Ymd') : null;
    }

    public static function diffMinutes($a, $b): ?int
    {
        $da = self::toDateTime($a);
        $db = self::toDateTime($b);
        if ($da === null || $db === null) {
            return null;
        }
        return intdiv($db->getTimestamp() - $da->getTimestamp(), 60);
    }

    public static function diffDays($a, $b): ?int
    {
        $pa = self::datePart($a);
        $pb = self::datePart($b);
        if ($pa === null || $pb === null) {
            return null;
        }
        $da = DateTime::createFromFormat('!Ymd', $pa);
        $db = DateTime::createFromFormat('!Ymd', $pb);
        return intdiv($db->getTimestamp() - $da->getTimestamp(), 86400);
    }
}
