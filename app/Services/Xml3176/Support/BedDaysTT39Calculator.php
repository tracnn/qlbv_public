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
     *  - Từ 4h đến dưới 24h -> 1, KỂ CẢ khi vắt qua nửa đêm và kể cả trường hợp đặc biệt.
     *    (Bản cũ chỉ tính 1 khi cùng ngày dương lịch, nên ca 12,5h tử vong qua nửa đêm —
     *    000007305574 — bị đòi 2 ngày và báo "thiếu" nhầm.)
     *  - Từ 24h trở lên: calendarDays + (special ? 1 : 0).
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

        if ($elapsedHours < 24 || $calendarDays <= 0) {
            return 1; // 4h–<24h: 1 ngày, dù qua nửa đêm hay thuộc trường hợp đặc biệt
        }

        return $calendarDays + ($special ? 1 : 0);
    }

    /**
     * Hồ sơ thuộc trường hợp đặc biệt được cộng thêm 1 ngày giường (tử vong, chuyển viện,
     * nặng xin về...) khi KẾT QUẢ điều trị HOẶC LOẠI ra viện thuộc diện đặc biệt — chỉ cần
     * một trong hai. Luật "thừa ngày giường" cũ viết điều kiện ngược bằng ||, nên hồ sơ
     * chuyển viện có kết quả thường vẫn bị coi là hồ sơ thường và bị báo nhầm.
     *
     * @param mixed $ketQua   ket_qua_dtri
     * @param mixed $loaiRv   ma_loai_rv
     * @param array $kqDacBiet config xml3176.invalid_treatment_result
     * @param array $rvDacBiet config xml3176.invalid_end_type_treatment
     */
    public static function laDacBiet($ketQua, $loaiRv, array $kqDacBiet, array $rvDacBiet): bool
    {
        return in_array($ketQua, $kqDacBiet) || in_array($loaiRv, $rvDacBiet);
    }

    /**
     * Thừa ngày giường với lưu trú NHIỀU NGÀY (trên 24 giờ): tổng khai lớn hơn số ngày đúng
     * theo TT39 = số ngày dương lịch (+1 nếu đặc biệt). Giờ lẻ ngoài ngày tròn KHÔNG cho cộng
     * thêm ngày — BHXH trừ đúng như vậy (hồ sơ 000007093453: lẻ 9,93h, khai 9, đúng 8, bị
     * trừ 1). Lưu trú từ 24 giờ trở xuống do hai nhánh riêng trong checker xử lý.
     *
     * @param string|null $ngayVao 'YmdHi'
     * @param string|null $ngayRa  'YmdHi'
     * @return array|null ['expected' => int, 'total' => float, 'excess' => float]; null khi
     *                    không thừa hoặc thiếu căn cứ (ngày hỏng, ra trước vào, <= 24 giờ).
     */
    public static function thua($ngayVao, $ngayRa, bool $special, float $total)
    {
        $vao = $ngayVao ? \DateTime::createFromFormat('YmdHi', (string) $ngayVao) : false;
        $ra = $ngayRa ? \DateTime::createFromFormat('YmdHi', (string) $ngayRa) : false;
        if (!$vao || !$ra) {
            return null;
        }

        $elapsedHours = ($ra->getTimestamp() - $vao->getTimestamp()) / 3600;
        if ($elapsedHours <= 24) {
            return null;
        }

        $calendarDays = (int) (new \DateTime($vao->format('Y-m-d')))
            ->diff(new \DateTime($ra->format('Y-m-d')))->days;
        $expected = self::expected($calendarDays, $elapsedHours, $special);

        if ($total <= $expected) {
            return null;
        }

        return ['expected' => $expected, 'total' => $total, 'excess' => round($total - $expected, 2)];
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
