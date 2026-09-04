<?php

namespace App\Services\Xml3176\Support;

/**
 * Tính trần mức hưởng BHYT cho phép của một hồ sơ, để so với muc_huong khai
 * trong XML2/XML3. Helper thuần — không chạm DB/model/config.
 */
class MucHuongCalculator
{
    /**
     * Trích ký tự quyền lợi (vị trí thứ 3) từ mã thẻ.
     * Mã thẻ có thể là danh sách ngăn cách ';' (thẻ cũ/mới). Nếu các đoạn cho
     * quyền lợi KHÁC nhau -> null (mơ hồ). Đoạn < 3 ký tự bị bỏ.
     */
    public static function quyenLoiChar($maThe): ?string
    {
        if ($maThe === null) {
            return null;
        }

        $found = null;
        foreach (explode(';', (string) $maThe) as $segment) {
            $segment = trim($segment);
            if (mb_strlen($segment) < 3) {
                continue;
            }
            $char = mb_substr($segment, 2, 1);
            if ($found === null) {
                $found = $char;
            } elseif ($found !== $char) {
                return null; // hai thẻ khác quyền lợi -> mơ hồ
            }
        }

        return $found;
    }

    /** Tra % mức hưởng theo ký tự quyền lợi. null nếu không có trong map. */
    public static function entitlement(?string $qlChar, array $map): ?int
    {
        if ($qlChar === null || !array_key_exists($qlChar, $map)) {
            return null;
        }

        return (int) $map[$qlChar];
    }

    /**
     * Trần mức hưởng cho phép khi ĐÚNG TUYẾN.
     *  - $luongCoSo null/<=0 hoặc $entitlement null -> null (guard).
     *  - chi phí >= rate * luongCoSo -> $entitlement (áp quyền lợi thẻ).
     *  - chi phí <  rate * luongCoSo -> 100 (miễn cùng chi trả, chi phí nhỏ).
     */
    public static function tranDungTuyen(?int $entitlement, float $chiPhi, ?int $luongCoSo, float $rate): ?int
    {
        if ($entitlement === null || $luongCoSo === null || $luongCoSo <= 0) {
            return null;
        }

        return $chiPhi >= $rate * $luongCoSo ? $entitlement : 100;
    }
}
