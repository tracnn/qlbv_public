<?php

namespace App\Services\OrderCheck\RuleHandlers\Clinical;

class DoseSanityRule
{
    public function code()
    {
        return 'A_DOSE_MISMATCH';
    }

    /**
     * True nếu liều/ngày × số ngày KHÁC số lượng cấp, và cả 3 đều > 0.
     * Dùng số thực; bỏ qua khi thiếu dữ liệu.
     */
    public function isMismatch($morning, $noon, $afternoon, $evening, $dayCount, $amount)
    {
        $perDay = (float) $morning + (float) $noon + (float) $afternoon + (float) $evening;
        $dayCount = (float) $dayCount;
        $amount = (float) $amount;

        if ($perDay <= 0 || $dayCount <= 0 || $amount <= 0) {
            return false;
        }
        $expected = $perDay * $dayCount;
        // So sánh với dung sai nhỏ cho số thực
        return abs($expected - $amount) > 0.0001;
    }
}
