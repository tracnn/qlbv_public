<?php

namespace App\Services\Xml3176\Support;

/**
 * Parse chuỗi liều dùng định dạng 130: "sl_lan * lan_ngay * so_ngay [đơn vị/ngày]".
 */
class LieuDungParser
{
    public static function parse($lieu): array
    {
        $rong = ['hop_le' => false, 'sl_lan' => 0.0, 'lan_ngay' => 0.0, 'so_ngay' => 0, 'don_vi' => '', 'tong_luong' => 0.0];

        if ($lieu === null) {
            return $rong;
        }
        $s = trim((string) $lieu);
        if ($s === '') {
            return $rong;
        }

        // Ba số ngăn bởi '*', tuỳ chọn phần [đơn vị] ở cuối.
        $re = '/^\s*(\d+(?:[.,]\d+)?)\s*\*\s*(\d+(?:[.,]\d+)?)\s*\*\s*(\d+)\s*(?:\[([^\]]*)\])?\s*$/u';
        if (!preg_match($re, $s, $m)) {
            return $rong;
        }

        $slLan   = (float) str_replace(',', '.', $m[1]);
        $lanNgay = (float) str_replace(',', '.', $m[2]);
        $soNgay  = (int) $m[3];
        $donVi   = isset($m[4]) ? trim($m[4]) : '';
        // Bỏ hậu tố "/ngày" nếu có
        $donVi   = preg_replace('#\s*/\s*ng[aà]y\s*$#ui', '', $donVi);

        return [
            'hop_le'     => true,
            'sl_lan'     => $slLan,
            'lan_ngay'   => $lanNgay,
            'so_ngay'    => $soNgay,
            'don_vi'     => trim($donVi),
            'tong_luong' => $slLan * $lanNgay * $soNgay,
        ];
    }
}
