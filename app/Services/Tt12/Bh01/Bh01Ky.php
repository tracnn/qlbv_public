<?php

namespace App\Services\Tt12\Bh01;

/**
 * Ky quyet toan cua mot ban 01/BH.
 *
 * VI SAO TACH RA: nam_qt/thang_qt trong CSDL la CHUOI ('2026', '09'), so sanh chuoi nen thang
 * phai co so 0 dung dau; mot cho duy nhat chuan hoa de truy van, ma ban va the kyQT cung dung.
 */
class Bh01Ky
{
    /** Dieu 17: ap dung tu ky 04/2026, nen ky truoc do khong lap duoc. */
    const KY_DAU_TIEN = 202604;

    public static function tu($nam, $thang)
    {
        $n = (int) $nam;
        $t = (int) $thang;

        if ($t < 1 || $t > 12 || $n < 1000 || $n > 9999) {
            throw new \InvalidArgumentException('Kỳ ' . $thang . '/' . $nam . ' không hợp lệ (tháng 1..12, năm 4 chữ số).');
        }

        if ($n * 100 + $t < self::KY_DAU_TIEN) {
            throw new \InvalidArgumentException('Kỳ ' . sprintf('%02d', $t) . '/' . $n . ' trước 04/2026 (Điều 17), không lập hồ sơ 01/BH.');
        }

        $thangChuoi = sprintf('%02d', $t);

        return array(
            'nam'   => (string) $n,
            'thang' => $thangChuoi,
            'ky_qt' => $n . $thangChuoi,
        );
    }
}
