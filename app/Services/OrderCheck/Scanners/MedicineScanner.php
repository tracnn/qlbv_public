<?php

namespace App\Services\OrderCheck\Scanners;

use App\Services\OrderCheck\Contracts\Scanner;
use App\Services\OrderCheck\OrderCheckEngine;
use App\Services\OrderCheck\Support\Duplicates;
use App\Services\OrderCheck\Support\Violation;
use App\Services\OrderCheck\Support\ViolationContext;
use App\Services\OrderCheck\RuleHandlers\Clinical\DoseSanityRule;

class MedicineScanner implements Scanner
{
    const SOURCE_KEY = 'his_exp_mest_medicine';
    const RULE_DUP = 'A_DUPLICATE_ACTIVE_INGREDIENT';
    const RULE_DOSE = 'A_DOSE_MISMATCH';

    public function sourceKey()
    {
        return self::SOURCE_KEY;
    }

    public function scan(OrderCheckEngine $engine, $limit)
    {
        $rules = $engine->activeRules();
        $dupActive = isset($rules[self::RULE_DUP]);
        $doseActive = isset($rules[self::RULE_DOSE]);

        $source = $engine->source();
        $wm = $engine->getWatermark(self::SOURCE_KEY);
        $rows = $source->fetchExpMestBatch($wm->last_create_time, $wm->last_id, $limit);
        $scanned = $rows->count();
        $violations = 0;

        if ($scanned > 0) {
            $maxCreate = $wm->last_create_time;
            $maxId = $wm->last_id;
            $treatmentIds = [];

            foreach ($rows as $row) {
                $treatmentIds[(int) $row->tdl_treatment_id] = true;
                if ((int) $row->create_time > $maxCreate || ((int) $row->create_time == $maxCreate && (int) $row->id > $maxId)) {
                    $maxCreate = (int) $row->create_time;
                    $maxId = (int) $row->id;
                }
            }

            $info = $source->fetchTreatmentInfo(array_keys($treatmentIds));

            // ===== A5: kiểm tra từng dòng thuốc mới =====
            if ($doseActive) {
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

            // ===== A2: re-evaluate trùng hoạt chất cả đợt =====
            if ($dupActive && !empty($treatmentIds)) {
                $rule = $rules[self::RULE_DUP];
                foreach (array_keys($treatmentIds) as $tid) {
                    $meds = $source->fetchTreatmentMedicines($tid);
                    $dups = Duplicates::groupsWithCountAbove($meds, function ($m) { return $m->active_ingr_code; }, 1);
                    if (empty($dups)) {
                        continue;
                    }
                    $vctx = $this->context($tid, isset($info[$tid]) ? $info[$tid] : null);
                    foreach ($dups as $code => $group) {
                        $first = $group[0];
                        $vio = new Violation(
                            self::RULE_DUP, 'treatment', $tid,
                            'Trùng hoạt chất trong đợt: ' . $first->active_ingr_name . ' (' . count($group) . ' thuốc)',
                            ['active_ingr_code' => $code, 'active_ingr_name' => $first->active_ingr_name, 'count' => count($group)],
                            'ai' . $code
                        );
                        if ($engine->persist($vio, $vctx, $rule)) {
                            $violations++;
                        }
                    }
                }
            }

            $engine->saveWatermark(self::SOURCE_KEY, $maxCreate, $maxId);
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
