<?php

namespace App\Services\Xml3176\Support;

/**
 * So hai khoảng thời gian thực hiện dịch vụ [ngay_th_yl, ngay_kq) xem có CHỒNG nhau không.
 *
 * Dùng biên nửa mở: dịch vụ này kết thúc đúng lúc dịch vụ kia bắt đầu thì KHÔNG coi là
 * chồng (nối tiếp nhau là hợp lệ).
 *
 * Helper thuần — không chạm DB/model/config.
 */
class ServiceOverlapChecker
{
    /**
     * @param mixed $aTu  ngay_th_yl của dịch vụ A
     * @param mixed $aDen ngay_kq  của dịch vụ A
     * @param mixed $bTu  ngay_th_yl của dịch vụ B
     * @param mixed $bDen ngay_kq  của dịch vụ B
     * @return bool true nếu hai khoảng chồng nhau
     */
    public static function chongNhau($aTu, $aDen, $bTu, $bDen): bool
    {
        $a1 = self::moc($aTu);
        $a2 = self::moc($aDen);
        $b1 = self::moc($bTu);
        $b2 = self::moc($bDen);

        // Thiếu bất kỳ mốc nào, hoặc khoảng ngược đầu-cuối (dữ liệu hỏng) -> không kết luận.
        if ($a1 === null || $a2 === null || $b1 === null || $b2 === null) {
            return false;
        }
        if ($a2 <= $a1 || $b2 <= $b1) {
            return false;
        }

        return $a1 < $b2 && $a2 > $b1;
    }

    /** Đổi mốc thời gian XML sang timestamp; null nếu rỗng hoặc không hợp lệ. */
    private static function moc($v): ?int
    {
        $dt = Xml3176DateHelper::toDateTime($v);

        return $dt ? $dt->getTimestamp() : null;
    }
}
