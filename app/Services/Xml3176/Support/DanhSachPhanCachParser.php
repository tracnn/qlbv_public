<?php

namespace App\Services\Xml3176\Support;

/**
 * Tach cac truong chua nhieu gia tri ngan boi dau cham phay theo chuan du lieu dau ra:
 * NGAY_TAI_KHAM, CAN_NANG_CON, MA_PTTT_QT, MA_PP_CHEBIEN, MA_THE_BHYT.
 *
 * Helper thuan - khong cham DB/model/config.
 */
class DanhSachPhanCachParser
{
    public static function tach($chuoi): array
    {
        $chuoi = trim((string) $chuoi);

        if ($chuoi === '') {
            return [];
        }

        $phan = array_map('trim', explode(';', $chuoi));

        return array_values(array_filter($phan, function ($v) {
            return $v !== '';
        }));
    }
}
