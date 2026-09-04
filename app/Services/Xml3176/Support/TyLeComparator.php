<?php

namespace App\Services\Xml3176\Support;

/**
 * So sánh tỷ lệ thanh toán BHYT của dòng XML với tỷ lệ được duyệt trong danh mục.
 *
 * Quy ước bỏ qua (thiếu căn cứ → không báo lỗi):
 *  - Một trong hai bên null / rỗng / không phải số.
 *  - Giá trị <= 0 (coi như danh mục chưa cấu hình tỷ lệ, tránh báo lỗi hàng loạt
 *    khi cột danh mục để mặc định 0).
 */
class TyLeComparator
{
    /**
     * @param mixed $xmlTyle Tỷ lệ trong XML (đề nghị của cơ sở)
     * @param mixed $dmTyle  Tỷ lệ duyệt trong danh mục (căn cứ)
     * @param float $eps     Dung sai số học
     * @return bool true nếu hai tỷ lệ LỆCH ngoài dung sai
     */
    public static function lech($xmlTyle, $dmTyle, float $eps = 0.01): bool
    {
        $x = self::num($xmlTyle);
        $d = self::num($dmTyle);

        if ($x === null || $d === null) {
            return false;
        }

        return abs($x - $d) > $eps;
    }

    /** Ép về số dương; null nếu rỗng, không phải số, hoặc <= 0. */
    private static function num($v): ?float
    {
        if ($v === null || $v === '' || !is_numeric($v)) {
            return null;
        }

        $f = (float) $v;

        return $f > 0 ? $f : null;
    }
}
