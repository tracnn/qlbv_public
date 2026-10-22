<?php

namespace App\Services\SinhXml3176\Bang;

/**
 * Doi so thoi gian HIS (yyyyMMddHHmmss) sang DateTime va nguoc lai; dung chung cho cac lop dung bang.
 */
class ThoiGianHis
{
    /** Chuoi 14 chu so cua so thoi gian HIS; null/rong/khong phai so => null. */
    public static function chuoi($v)
    {
        if ($v === null || $v === '') {
            return null;
        }
        $s = is_float($v) ? sprintf('%.0f', $v) : trim((string) $v);
        if (strpos($s, '.') !== false) {
            $s = substr($s, 0, strpos($s, '.'));
        }

        return ctype_digit($s) ? $s : null;
    }

    /** Inventec TimeNumberToSystemDateTime: <=0 hoac khong hop le => null. */
    public static function ngay($v)
    {
        $s = self::chuoi($v);
        if ($s === null || strlen($s) !== 14 || (int) $s <= 0) {
            return null;
        }
        $t = \DateTime::createFromFormat('!YmdHis', $s);
        $loi = \DateTime::getLastErrors();
        if ($t === false || ($loi && ($loi['warning_count'] > 0 || $loi['error_count'] > 0))) {
            return null;
        }

        return $t;
    }

    /** DateTime -> 'yyyyMMddHHmm'; null => ''. */
    public static function phut(\DateTime $t = null)
    {
        return $t === null ? '' : $t->format('YmdHi');
    }
}
