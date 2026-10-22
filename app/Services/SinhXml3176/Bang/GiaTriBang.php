<?php

namespace App\Services\SinhXml3176\Bang;

/**
 * Ham dinh dang / doc gia tri dung chung cho cac lop dung bang (XML2, XML3...).
 * Tach tu Xml2Dung de khong chep lai ma.
 */
class GiaTriBang
{
    /** Nullable.HasValue: Oracle tra null, SQLite co the tra chuoi rong. */
    public static function co($v)
    {
        return $v !== null && $v !== '';
    }

    public static function chu($v)
    {
        return $v === null ? '' : (string) $v;
    }

    /** Tien: chuoi 2 chu so thap phan (half-up). */
    public static function tien($x)
    {
        $v = round((float) $x, 2);

        return sprintf('%.2f', $v == 0 ? 0.0 : $v);
    }

    /** So lam tron $cs chu so, bo so 0 thua cuoi. */
    public static function so($x, $cs)
    {
        $v = round((float) $x, $cs);
        $s = sprintf('%.' . $cs . 'f', $v == 0 ? 0.0 : $v);
        if ($cs > 0) {
            $s = rtrim(rtrim($s, '0'), '.');
        }

        return $s;
    }

    /**
     * Ty le phan tram ve so nguyen HALF-UP (QD130: So, do dai 3). Round 6 truoc de bo nhieu float
     * (vd 32.4999999 -> 32.5 -> 33). DLL dung Math.Round banker.
     */
    public static function tyLeNguyen($x)
    {
        return round(round((float) $x, 6), 0, PHP_ROUND_HALF_UP);
    }

    /** Noi cac phan khac null/rong bang ';'. */
    public static function noi(array $phan)
    {
        $ra = [];
        foreach ($phan as $p) {
            if ($p !== null && (string) $p !== '') {
                $ra[] = (string) $p;
            }
        }

        return implode(';', $ra);
    }

    /** Sap xep on dinh (LINQ OrderBy on dinh, usort PHP 7 thi khong). */
    public static function sapOnDinh(array $mang, callable $ss)
    {
        $kem = [];
        foreach (array_values($mang) as $i => $v) {
            $kem[] = [$i, $v];
        }
        usort($kem, function ($a, $b) use ($ss) {
            $c = $ss($a[1], $b[1]);

            return $c !== 0 ? $c : $a[0] - $b[0];
        });

        return array_map(function ($x) {
            return $x[1];
        }, $kem);
    }

    public static function soSanh($a, $b)
    {
        return $a < $b ? -1 : ($a > $b ? 1 : 0);
    }
}
