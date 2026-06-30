<?php

namespace App\Services\OrderCheck\Scanners;

use App\Services\OrderCheck\Contracts\Scanner;
use App\Services\OrderCheck\OrderCheckEngine;
use App\Services\OrderCheck\Support\Duplicates;
use App\Services\OrderCheck\Support\Violation;
use App\Services\OrderCheck\Support\ViolationContext;

class DuplicateServiceScanner implements Scanner
{
    const SOURCE_KEY = 'his_sere_serv';
    const RULE_CODE = 'A_DUPLICATE_SERVICE';

    public function sourceKey()
    {
        return self::SOURCE_KEY;
    }

    public function scan(OrderCheckEngine $engine, $limit)
    {
        $rules = $engine->activeRules();
        $active = isset($rules[self::RULE_CODE]);
        $rule = $active ? $rules[self::RULE_CODE] : null;

        $source = $engine->source();
        $wm = $engine->getWatermark(self::SOURCE_KEY);
        $rows = $source->fetchSereServBatch($wm->last_create_time, $wm->last_id, $limit);
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

            if ($active && !empty($treatmentIds)) {
                $ids = array_keys($treatmentIds);
                $info = $source->fetchTreatmentInfo($ids);

                foreach ($ids as $tid) {
                    $services = $source->fetchTreatmentServices($tid);
                    $dups = Duplicates::groupsWithCountAbove($services, function ($s) { return $s->service_id; }, 1);
                    if (empty($dups)) {
                        continue;
                    }
                    $vctx = $this->context($tid, isset($info[$tid]) ? $info[$tid] : null);
                    foreach ($dups as $serviceId => $group) {
                        $first = $group[0];
                        $vio = new Violation(
                            self::RULE_CODE, 'treatment', $tid,
                            'Trùng dịch vụ trong đợt: ' . $first->tdl_service_code . ' - ' . $first->tdl_service_name . ' (' . count($group) . ' lần)',
                            ['service_id' => (int) $serviceId, 'service_code' => $first->tdl_service_code, 'count' => count($group)],
                            'svc' . $serviceId
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
