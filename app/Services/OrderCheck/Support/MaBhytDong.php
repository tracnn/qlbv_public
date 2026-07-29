<?php

namespace App\Services\OrderCheck\Support;

/**
 * Chon ma BHYT cua mot dong dich vu theo dung nguon.
 *
 * Vi sao can: quy tac A_BHYT_CODE_MISSING truoc day luon doc
 * his_service.hein_service_bhyt_code, ma cot do CHI duoc duy tri cho dich vu ky thuat.
 * Voi thuoc, ma BHYT nam o his_medicine_type.active_ingr_bhyt_code.
 *
 * Do tren 7 ngay that: 48.234 dong thuoc BHYT thieu hein_service_bhyt_code, va 100% so do
 * DA khai active_ingr_bhyt_code. Vat tu 0/175.775 va DVKT 0/352.206 khong thieu - nen chi
 * can them nguon thuoc, khong can nhanh rieng cho vat tu.
 *
 * Ham THUAN de kiem duoc.
 */
class MaBhytDong
{
    /**
     * @param string|null $maHoatChat his_medicine_type.active_ingr_bhyt_code
     * @param string|null $maDichVu   his_service.hein_service_bhyt_code
     * @return string ma dau tien khac rong sau khi trim; CHUOI RONG neu khong co cai nao
     */
    public static function cua($maHoatChat, $maDichVu)
    {
        foreach ([$maHoatChat, $maDichVu] as $ma) {
            $ma = trim((string) $ma);

            if ($ma !== '') {
                return $ma;
            }
        }

        return '';
    }
}
