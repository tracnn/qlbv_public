<?php

namespace App\Services\OrderCheck\Scanners;

use App\Services\OrderCheck\Contracts\Scanner;
use App\Services\OrderCheck\OrderCheckEngine;
use App\Services\OrderCheck\Support\Violation;
use App\Services\OrderCheck\Support\ViolationContext;
use App\Services\OrderCheck\RuleHandlers\Clinical\DoseSanityRule;

class MedicineScanner implements Scanner
{
    const SOURCE_KEY = 'his_exp_mest_medicine';
    const RULE_DOSE = 'A_DOSE_MISMATCH';

    public function sourceKey()
    {
        return self::SOURCE_KEY;
    }

    public function scan(OrderCheckEngine $engine, $limit)
    {
        $rules = $engine->activeRules();
        $doseActive = isset($rules[self::RULE_DOSE]);

        $source = $engine->source();
        $wm = $engine->getWatermark(self::SOURCE_KEY);
        $rows = $source->fetchExpMestBatch($wm->last_create_time, $wm->last_id, $limit);
        $scanned = $rows->count();
        $violations = 0;

        if ($scanned > 0) {
            $maxId = $wm->last_id;
            $treatmentIds = [];
            foreach ($rows as $row) {
                $treatmentIds[(int) $row->tdl_treatment_id] = true;
                if ((int) $row->id > $maxId) {
                    $maxId = (int) $row->id;
                }
            }

            // ===== A5: liều × ngày không khớp số lượng cấp (kiểm tra từng dòng thuốc mới) =====
            if ($doseActive) {
                $info = $source->fetchTreatmentInfo(array_keys($treatmentIds));
                $doseRule = new DoseSanityRule();
                $rule = $rules[self::RULE_DOSE];
                foreach ($rows as $row) {
                    if ($doseRule->isMismatch($row->morning, $row->noon, $row->afternoon, $row->evening, $row->day_count, $row->amount)) {
                        $tid = (int) $row->tdl_treatment_id;
                        $perDay = (float) $row->morning + (float) $row->noon + (float) $row->afternoon + (float) $row->evening;
                        $vio = new Violation(
                            self::RULE_DOSE, 'exp_mest_medicine', (int) $row->id,
                            'Liều×ngày (' . $perDay . '×' . $row->day_count . ') không khớp số lượng cấp (' . $row->amount . ')',
                            ['per_day' => $perDay, 'day_count' => (float) $row->day_count, 'amount' => (float) $row->amount]
                        );
                        if ($engine->persist($vio, $this->context($tid, isset($info[$tid]) ? $info[$tid] : null), $rule)) {
                            $violations++;
                        }
                    }
                }
            }

            $engine->saveWatermark(self::SOURCE_KEY, $wm->last_create_time, $maxId);
        }

        return ['scanned' => $scanned, 'violations' => $violations];
    }

    private function context($tid, $info)
    {
        return ViolationContext::make([
            'treatment_id' => $tid,
            'treatment_code' => $info ? $info->treatment_code : null,
            'patient_code' => $info ? $info->tdl_patient_code : null,
            'patient_name' => $info ? $info->tdl_patient_name : null,
            'department_id' => $info && $info->last_department_id !== null ? (int) $info->last_department_id : null,
        ]);
    }
}
