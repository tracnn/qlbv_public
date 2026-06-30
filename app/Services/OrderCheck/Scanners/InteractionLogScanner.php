<?php

namespace App\Services\OrderCheck\Scanners;

use App\Services\OrderCheck\Contracts\Scanner;
use App\Services\OrderCheck\OrderCheckEngine;
use App\Services\OrderCheck\Support\Violation;
use App\Services\OrderCheck\Support\ViolationContext;

class InteractionLogScanner implements Scanner
{
    const SOURCE_KEY = 'his_medicine_interactive';
    const RULE_CODE = 'A_DRUG_INTERACTION';

    public function sourceKey()
    {
        return self::SOURCE_KEY;
    }

    public function scan(OrderCheckEngine $engine, $limit)
    {
        $rulesByCode = $engine->activeRules();

        // Rule A1 bị tắt → vẫn tiến watermark để không tồn đọng, nhưng không sinh violation.
        $ruleActive = isset($rulesByCode[self::RULE_CODE]);
        $rule = $ruleActive ? $rulesByCode[self::RULE_CODE] : null;

        $source = $engine->source();
        $wm = $engine->getWatermark(self::SOURCE_KEY);
        $rows = $source->fetchInteractions($wm->last_create_time, $wm->last_id, $limit);
        $scanned = $rows->count();
        $violations = 0;

        if ($scanned > 0) {
            $maxCreate = $wm->last_create_time;
            $maxId = $wm->last_id;

            foreach ($rows as $row) {
                if ($ruleActive) {
                    $vctx = ViolationContext::make([
                        'treatment_id' => (int) $row->treatment_id,
                        'doctor_loginname' => $row->request_loginname,
                        'department_id' => $row->request_department_id !== null ? (int) $row->request_department_id : null,
                    ]);

                    $vio = new Violation(
                        self::RULE_CODE,
                        'medicine_interactive',
                        (int) $row->id,
                        'Tương tác thuốc (HIS phát hiện): cặp thuốc ' . $row->medicine_type_id1 . ' - ' . $row->medicine_type_id2 . ', mức độ ' . $row->interactive_grade_id,
                        [
                            'medicine_type_id1' => (int) $row->medicine_type_id1,
                            'medicine_type_id2' => (int) $row->medicine_type_id2,
                            'interactive_grade_id' => $row->interactive_grade_id !== null ? (int) $row->interactive_grade_id : null,
                            'icd_code' => $row->icd_code,
                        ]
                    );

                    if ($engine->persist($vio, $vctx, $rule)) {
                        $violations++;
                    }
                }

                if ((int) $row->create_time > $maxCreate || ((int) $row->create_time == $maxCreate && (int) $row->id > $maxId)) {
                    $maxCreate = (int) $row->create_time;
                    $maxId = (int) $row->id;
                }
            }

            $engine->saveWatermark(self::SOURCE_KEY, $maxCreate, $maxId);
        }

        return ['scanned' => $scanned, 'violations' => $violations];
    }
}
