<?php

namespace App\Services\Xml3176\Support;

/**
 * Tính số ngày giường đúng theo TT39 và so với tổng ngày giường khai.
 * Helper thuần — không chạm DB/model/config.
 */
class BedDaysTT39Calculator
{
    /**
     * Số ngày giường đúng theo TT39.
     *  - Cùng ngày (calendarDays == 0): elapsedHours >= 4 -> 1, ngược lại 0.
     *  - Nhiều ngày: calendarDays + (special ? 1 : 0).
     *
     * @param int   $calendarDays số ngày dương lịch giữa ngày vào và ngày ra (>= 0)
     * @param float $elapsedHours tổng giờ trôi qua giữa vào và ra
     * @param bool  $special      tử vong / chuyển viện / nặng xin về -> +1
     */
    public static function expected(int $calendarDays, float $elapsedHours, bool $special): int
    {
        if ($calendarDays <= 0) {
            return $elapsedHours >= 4 ? 1 : 0;
        }

        return $calendarDays + ($special ? 1 : 0);
    }

    /**
     * Có thiếu ngày giường không: chỉ xét khi expected >= 1; thiếu khi
     * totalBedDays < expected - tolerance.
     */
    public static function isBelow(float $totalBedDays, int $expected, float $tolerance): bool
    {
        if ($expected < 1) {
            return false;
        }

        return $totalBedDays < $expected - $tolerance;
    }
}
