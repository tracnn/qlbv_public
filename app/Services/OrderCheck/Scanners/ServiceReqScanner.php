<?php

namespace App\Services\OrderCheck\Scanners;

use App\Services\OrderCheck\Contracts\Scanner;
use App\Services\OrderCheck\OrderCheckEngine;
use App\Services\OrderCheck\RuleHandlers\StructuralRuleRegistry;
use App\Services\OrderCheck\RuleHandlers\ClinicalServiceReqRuleRegistry;
use App\Services\OrderCheck\Support\ViolationContext;

class ServiceReqScanner implements Scanner
{
    const SOURCE_KEY = 'his_service_req';

    public function sourceKey()
    {
        return self::SOURCE_KEY;
    }

    public function scan(OrderCheckEngine $engine, $limit)
    {
        $source = $engine->source();
        $rulesByCode = $engine->activeRules();
        $handlers = array_merge(
            StructuralRuleRegistry::handlers(),
            ClinicalServiceReqRuleRegistry::handlers()
        );

        $wm = $engine->getWatermark(self::SOURCE_KEY);
        $rows = $source->fetchServiceRequests($wm->last_create_time, $wm->last_id, $limit);
        $scanned = $rows->count();
        $violations = 0;

        if ($scanned > 0) {
            $reqIds = $rows->pluck('id')->map(function ($v) { return (int) $v; })->all();
            $servicesMap = $source->fetchServicesByReqIds($reqIds);

            $maxCreate = $wm->last_create_time;
            $maxId = $wm->last_id;

            foreach ($rows as $row) {
                $ctx = $source->buildContext($row, isset($servicesMap[(int) $row->id]) ? $servicesMap[(int) $row->id] : []);
                $vctx = ViolationContext::fromOrderContext($ctx);

                foreach ($handlers as $handler) {
                    if (!isset($rulesByCode[$handler->code()])) {
                        continue;
                    }
                    $rule = $rulesByCode[$handler->code()];
                    foreach ($handler->check($ctx) as $vio) {
                        if ($engine->persist($vio, $vctx, $rule)) {
                            $violations++;
                        }
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
