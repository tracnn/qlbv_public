<?php

namespace App\Services\BHYT;

use DB;

/**
 * Tra khoa dieu tri CUOI (his_treatment.last_department_id) cua ho so trong HIS theo ma_lk
 * (= his_treatment.treatment_code).
 *
 * Tra THEO LO: Oracle gioi han IN 1000 phan tu, va tra tung dong thi 50 nghin dong xuat la
 * 50 nghin truy van.
 */
class KhoaDieuTriHis
{
    const LO = 1000;

    /**
     * @return array [ma_lk => ['ma_khoa' => ..., 'ten_khoa' => ...]] - ma khong co trong HIS
     *               thi khong co khoa trong mang.
     */
    public function theoMaLk(array $maLk)
    {
        $maLk = array_values(array_unique(array_filter(array_map(function ($m) {
            return trim((string) $m);
        }, $maLk), 'strlen')));

        $kq = [];

        foreach (array_chunk($maLk, self::LO) as $lo) {
            $kq += $this->traLo($lo);
        }

        return $kq;
    }

    protected function traLo(array $lo)
    {
        return DB::connection('HISPro')->table('his_treatment')
            ->leftJoin('his_department', 'his_department.id', '=', 'his_treatment.last_department_id')
            ->whereIn('his_treatment.treatment_code', $lo)
            ->get(['his_treatment.treatment_code', 'his_department.department_code',
                   'his_department.department_name'])
            ->mapWithKeys(function ($r) {
                return [$r->treatment_code => [
                    'ma_khoa' => $r->department_code,
                    'ten_khoa' => $r->department_name,
                ]];
            })
            ->all();
    }
}
