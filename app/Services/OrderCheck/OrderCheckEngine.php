<?php

namespace App\Services\OrderCheck;

use App\Models\OrderCheck\OrderCheckWatermark;
use App\Models\OrderCheck\OrderCheckRule;
use App\Models\OrderCheck\OrderCheckViolation;
use App\Models\OrderCheck\OrderCheckRuleLog;
use App\Services\OrderCheck\RuleHandlers\StructuralRuleRegistry;
use App\Services\OrderCheck\Support\OrderContext;
use App\Services\OrderCheck\Support\Violation;

class OrderCheckEngine
{
    const SOURCE_KEY = 'his_service_req';

    protected $source;

    public function __construct(HisOrderSource $source)
    {
        $this->source = $source;
    }

    public function run($limit = null)
    {
        $limit = $limit ?: (int) config('order_check.batch_size');

        $log = OrderCheckRuleLog::create([
            'source_key' => self::SOURCE_KEY,
            'started_at' => now(),
            'status' => 'running',
        ]);

        try {
            $wm = OrderCheckWatermark::firstOrCreate(
                ['source_key' => self::SOURCE_KEY],
                ['last_create_time' => 0, 'last_modify_time' => 0, 'last_id' => 0]
            );

            $rulesByCode = OrderCheckRule::where('is_active', true)->get()->keyBy('code');
            $handlers = StructuralRuleRegistry::handlers();

            $rows = $this->source->fetchServiceRequests($wm->last_create_time, $wm->last_id, $limit);
            $scanned = $rows->count();
            $violationCount = 0;

            if ($scanned > 0) {
                $reqIds = $rows->pluck('id')->map(function ($v) { return (int) $v; })->all();
                $servicesMap = $this->source->fetchServicesByReqIds($reqIds);

                $maxCreate = $wm->last_create_time;
                $maxId = $wm->last_id;

                foreach ($rows as $row) {
                    $ctx = $this->source->buildContext($row, isset($servicesMap[(int) $row->id]) ? $servicesMap[(int) $row->id] : []);

                    foreach ($handlers as $handler) {
                        if (!isset($rulesByCode[$handler->code()])) {
                            continue;
                        }
                        $rule = $rulesByCode[$handler->code()];
                        foreach ($handler->check($ctx) as $vio) {
                            if ($this->persist($vio, $ctx, $rule)) {
                                $violationCount++;
                            }
                        }
                    }

                    if ((int) $row->create_time > $maxCreate || ((int) $row->create_time == $maxCreate && (int) $row->id > $maxId)) {
                        $maxCreate = (int) $row->create_time;
                        $maxId = (int) $row->id;
                    }
                }

                $wm->last_create_time = $maxCreate;
                $wm->last_id = $maxId;
                $wm->last_run_at = now();
                $wm->save();
            }

            $log->update([
                'finished_at' => now(),
                'scanned_count' => $scanned,
                'violation_count' => $violationCount,
                'status' => 'success',
            ]);

            return ['scanned' => $scanned, 'violations' => $violationCount];
        } catch (\Exception $e) {
            $log->update([
                'finished_at' => now(),
                'status' => 'error',
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    protected function persist(Violation $vio, OrderContext $ctx, OrderCheckRule $rule)
    {
        $dedup = $vio->dedupKey();
        $row = OrderCheckViolation::where('dedup_key', $dedup)->first();

        if ($row && in_array($row->status, ['processed', 'false_positive'])) {
            return false;
        }

        $isNew = !$row;
        if ($isNew) {
            $row = new OrderCheckViolation();
            $row->dedup_key = $dedup;
            $row->status = 'new';
            $row->detected_at = now();
        }

        $row->rule_id = $rule->id;
        $row->rule_code = $vio->ruleCode;
        $row->treatment_id = $ctx->treatmentId;
        $row->treatment_code = $ctx->treatmentCode;
        $row->patient_code = $ctx->patientCode;
        $row->patient_name = $ctx->patientName;
        $row->doctor_loginname = $ctx->doctorLoginname;
        $row->doctor_username = $ctx->doctorUsername;
        $row->department_id = $ctx->departmentId;
        $row->order_ref_type = $vio->orderRefType;
        $row->order_ref_id = $vio->orderRefId;
        $row->severity = $rule->severity;
        $row->message = $vio->message;
        $row->detail = json_encode($vio->detail, JSON_UNESCAPED_UNICODE);
        $row->save();

        return $isNew;
    }
}
