<?php

namespace App\Services\OrderCheck\Scanners;

use App\Services\OrderCheck\Contracts\Scanner;
use App\Services\OrderCheck\OrderCheckEngine;
use App\Services\OrderCheck\RuleHandlers\ServiceReq\ServiceReqRuleRegistry;
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
        $commonHandlers = ServiceReqRuleRegistry::common();

        // Quét theo MODIFY_TIME (HIS_SERVICE_REQ.modify_time có index) để bắt cả phiếu
        // bị SỬA sau khi tạo — vd người thực hiện (execute_loginname) được gán lúc thực hiện.
        $wm = $engine->getWatermark(self::SOURCE_KEY);
        $rows = $source->fetchServiceRequests($wm->last_modify_time, $wm->last_id, $limit);
        $scanned = $rows->count();
        $violations = 0;

        if ($scanned > 0) {
            $reqIds = $rows->pluck('id')->map(function ($v) { return (int) $v; })->all();
            $servicesMap = $source->fetchServicesByReqIds($reqIds);

            $maxModify = $wm->last_modify_time;
            $maxId = $wm->last_id;

            foreach ($rows as $row) {
                $ctx = $source->buildContext($row, isset($servicesMap[(int) $row->id]) ? $servicesMap[(int) $row->id] : []);
                $vctx = ViolationContext::fromOrderContext($ctx);

                // Luật áp mọi loại + luật riêng theo loại của phiếu
                $handlers = array_merge($commonHandlers, ServiceReqRuleRegistry::forType($ctx->serviceReqTypeId));
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

                $mt = (int) $row->modify_time;
                $rid = (int) $row->id;
                if ($mt > $maxModify || ($mt == $maxModify && $rid > $maxId)) {
                    $maxModify = $mt;
                    $maxId = $rid;
                }
            }

            $engine->saveWatermarkModify(self::SOURCE_KEY, $maxModify, $maxId);
        }

        return ['scanned' => $scanned, 'violations' => $violations];
    }
}
