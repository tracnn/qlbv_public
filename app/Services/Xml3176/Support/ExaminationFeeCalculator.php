<?php

namespace App\Services\Xml3176\Support;

/**
 * Hỗ trợ kiểm tra công khám theo TT39/2024/TT-BYT (sửa TT35/2016):
 *  - Khám nhiều chuyên khoa KHÁC nhau trong cùng một lần đến khám là HỢP LỆ
 *    (từ lần 2 tính 30% mức giá) -> chỉ bắt khi CÙNG một mã dịch vụ lặp > 1 lần.
 *  - Tổng tiền khám không quá 2 lần mức giá của 1 lần khám.
 *
 * Helper thuần — không chạm DB/model/config.
 */
class ExaminationFeeCalculator
{
    /**
     * Các mã dịch vụ khám xuất hiện nhiều hơn 1 lần.
     * Bỏ qua mã rỗng; chuẩn hoá trim + hạ chữ thường để so trùng.
     *
     * @param array $maDichVus danh sách ma_dich_vu của các dòng khám trong hồ sơ
     * @return string[] các mã bị lặp (giữ dạng đã chuẩn hoá), không trùng nhau
     */
    public static function maTrung(array $maDichVus): array
    {
        $dem = [];
        foreach ($maDichVus as $ma) {
            $ma = TextNormalizer::chuan($ma);
            if ($ma === '') {
                continue;
            }
            $dem[$ma] = isset($dem[$ma]) ? $dem[$ma] + 1 : 1;
        }

        $trung = [];
        foreach ($dem as $ma => $soLan) {
            if ($soLan > 1) {
                $trung[] = (string) $ma;
            }
        }

        return $trung;
    }

    /**
     * Tổng tiền khám có vượt trần "không quá $heSo lần mức giá 1 lần khám" không.
     * Guard: $donGiaMax <= 0 -> false (thiếu căn cứ, không báo lỗi).
     *
     * @param float $tongThanhTien tổng thành tiền BH các dòng khám
     * @param float $donGiaMax     đơn giá BH cao nhất trong các dòng khám (= giá 1 lần khám đầy đủ)
     * @param float $heSo          hệ số trần (2.0 theo TT39)
     * @param float $eps           dung sai số học
     */
    public static function vuotTran(float $tongThanhTien, float $donGiaMax, float $heSo, float $eps = 0.01): bool
    {
        if ($donGiaMax <= 0) {
            return false;
        }

        return $tongThanhTien > ($heSo * $donGiaMax) + $eps;
    }
}
