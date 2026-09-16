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

    /**
     * Tuoi du nam tai ngay moc, chi doc 8 ky tu dau (Ymd) cua moi chuoi.
     *
     * Mot trong hai ngay khong doc duoc - rong, thang/ngay 00 (XML3176 ghi ngay sinh khong
     * ro ngay bang 00), ngay khong ton tai - hoac ngay sinh sau ngay moc thi tra null: quy
     * tac dung tuoi phai im lang khi thieu can cu, khong duoc doan.
     */
    public static function tuoiDuNam($ngaySinh, $ngayMoc): ?int
    {
        $sinh = self::toDateTime(substr(trim((string) $ngaySinh), 0, 8));
        $moc  = self::toDateTime(substr(trim((string) $ngayMoc), 0, 8));

        if ($sinh === null || $moc === null || $sinh > $moc) {
            return null;
        }

        return (int) $sinh->diff($moc)->y;
    }
}
