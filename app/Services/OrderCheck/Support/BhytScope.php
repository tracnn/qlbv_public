<?php

namespace App\Services\OrderCheck\Support;

/**
 * Pham vi doi tuong BHYT cho order-check.
 *
 * Loc PHAI o muc DONG DICH VU (his_sere_serv.patient_type_id), khong phai muc phieu hay
 * muc ho so. Do tren 148.915 dong cua 7 ngay that: hai cach lech 44.927 dong (30,17%),
 * lon nhat la 43.264 dong Vien phi (02) nam trong ho so BHYT (01) - benh nhan co the
 * nhung rieng dich vu do tu chi tra. Loc sai cap se bat loi oan ~6.200 vi pham gia/ngay.
 *
 * Cot primary_patient_type_id KHONG dung duoc: chi co gia tri o 2,2% so dong va khong
 * mot dong nao mang gia tri BHYT.
 */
class BhytScope
{
    /**
     * @return array id doi tuong duoc coi la BHYT; mang RONG nghia la KHONG loc
     */
    public static function dsDoiTuong()
    {
        $csv = trim((string) config('order_check.bhyt_patient_type_ids', ''));

        if ($csv === '') {
            return [];
        }

        return array_values(array_map('intval', array_filter(explode(',', $csv), 'strlen')));
    }

    public static function laDongBhyt($patientTypeId)
    {
        $ds = self::dsDoiTuong();

        if (empty($ds)) {
            return true;   // khong loc
        }

        return $patientTypeId !== null && in_array((int) $patientTypeId, $ds, true);
    }

    /**
     * @param OrderService[] $services
     * @return OrderService[] danh so lai tu 0
     */
    public static function locDongBhyt(array $services)
    {
        if (empty(self::dsDoiTuong())) {
            return $services;
        }

        return array_values(array_filter($services, function ($s) {
            return self::laDongBhyt($s->patientTypeId);
        }));
    }

    /**
     * Tang loc THO: phieu co it nhat mot dong BHYT khong.
     */
    public static function coDongBhyt(array $services)
    {
        return !empty(self::locDongBhyt($services));
    }
}
