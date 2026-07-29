<?php

namespace App\Services\OrderCheck\Support;

/**
 * Chon ma BHYT cua mot dong dich vu theo dung nguon.
 *
 * Vi sao can: quy tac A_BHYT_CODE_MISSING truoc day luon doc
 * his_service.hein_service_bhyt_code, ma cot do CHI duoc duy tri cho dich vu ky thuat.
 * Voi thuoc, ma BHYT nam o his_medicine_type.active_ingr_bhyt_code.
 *
 * Do that voi cua so tdl_intruction_time >= 20260722000000 (khong chan tren, do ngay
 * 29/07/2026): 48.566 dong thuoc BHYT thieu hein_service_bhyt_code, va 100% so do DA
 * khai active_ingr_bhyt_code. Vat tu 0/177.730 va DVKT 0/355.497 khong thieu - nen chi
 * can them nguon thuoc, khong can nhanh rieng cho vat tu.
 *
 * Ham THUAN de kiem duoc.
 */
class MaBhytDong
{
    /**
     * Ham nay chon gia tri DAU TIEN khac rong, dung duoc cho ca cap ma lan cap ten - miem
     * la hai tham so cung mot cap dong nguon (vi du: ma hoat chat + ma dich vu, hoac ten
     * hoat chat + ten dich vu).
     *
     * @param string|null $giaTriUuTien   uu tien 1 (vi du: his_medicine_type.active_ingr_bhyt_code)
     * @param string|null $giaTriDuPhong  uu tien 2, dung khi gia tri dau rong (vi du: his_service.hein_service_bhyt_code)
     * @return string gia tri dau tien khac rong sau khi trim; CHUOI RONG neu khong co cai nao
     */
    public static function cua($giaTriUuTien, $giaTriDuPhong)
    {
        foreach ([$giaTriUuTien, $giaTriDuPhong] as $ma) {
            $ma = trim((string) $ma);

            if ($ma !== '') {
                return $ma;
            }
        }

        return '';
    }
}
