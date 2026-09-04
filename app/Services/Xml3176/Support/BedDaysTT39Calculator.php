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
     *  - Lưu trú < 4h -> 0 (không tính giường, kể cả khi vắt qua nửa đêm).
     *  - Cùng ngày (calendarDays == 0) và >= 4h -> 1.
     *  - Nhiều ngày: calendarDays + (special ? 1 : 0).
     *
     * @param int   $calendarDays số ngày dương lịch giữa ngày vào và ngày ra (>= 0)
     * @param float $elapsedHours tổng giờ trôi qua giữa vào và ra
     * @param bool  $special      tử vong / chuyển viện / nặng xin về -> +1
     */
    public static function expected(int $calendarDays, float $elapsedHours, bool $special): int
    {
        if ($elapsedHours < 4) {
            return 0; // lưu trú < 4h không tính giường, kể cả vắt qua nửa đêm (calendarDays >= 1)
        }

        if ($calendarDays <= 0) {
            return 1; // cùng ngày, >= 4h
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
