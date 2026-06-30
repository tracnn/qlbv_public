<?php

namespace App\Services\OrderCheck;

use App\Models\OrderCheck\OrderCheckWatermark;
use App\Models\OrderCheck\OrderCheckRule;
use App\Models\OrderCheck\OrderCheckViolation;
use App\Models\OrderCheck\OrderCheckRuleLog;
use App\Services\OrderCheck\Scanners\ScannerRegistry;
use App\Services\OrderCheck\Support\Violation;
use App\Services\OrderCheck\Support\ViolationContext;

class OrderCheckEngine
{
    protected $source;
    protected $rulesByCode; // cache trong 1 lần run()

    public function __construct(HisOrderSource $source)
    {
        $this->source = $source;
    }

    public function source()
    {
        return $this->source;
    }

    /** Chạy tất cả scanner đã đăng ký. Trả tổng hợp. */
    public function run($limit = null)
    {
        $limit = $limit ?: (int) config('order_check.batch_size');
        $this->rulesByCode = OrderCheckRule::where('is_active', true)->get()->keyBy('code');

        $totalScanned = 0;
        $totalViolations = 0;

        foreach (ScannerRegistry::all($this->source) as $scanner) {
            $log = OrderCheckRuleLog::create([
                'source_key' => $scanner->sourceKey(),
                'started_at' => now(),
                'status' => 'running',
            ]);
            try {
                $res = $scanner->scan($this, $limit);
                $log->update([
                    'finished_at' => now(),
                    'scanned_count' => $res['scanned'],
                    'violation_count' => $res['violations'],
                    'status' => 'success',
                ]);
                $totalScanned += $res['scanned'];
                $totalViolations += $res['violations'];
            } catch (\Exception $e) {
                $log->update([
                    'finished_at' => now(),
                    'status' => 'error',
                    'error' => $e->getMessage(),
                ]);
                throw $e;
            }
        }

        return ['scanned' => $totalScanned, 'violations' => $totalViolations];
    }

    /** Rule active theo code (đã cache trong run()). */
    public function activeRules()
    {
        if ($this->rulesByCode === null) {
            $this->rulesByCode = OrderCheckRule::where('is_active', true)->get()->keyBy('code');
        }
        return $this->rulesByCode;
    }

    public function getWatermark($sourceKey)
    {
        return OrderCheckWatermark::firstOrCreate(
            ['source_key' => $sourceKey],
            ['last_create_time' => 0, 'last_modify_time' => 0, 'last_id' => 0]
        );
    }

    public function saveWatermark($sourceKey, $lastCreateTime, $lastId)
    {
        $wm = $this->getWatermark($sourceKey);
        $wm->last_create_time = $lastCreateTime;
        $wm->last_id = $lastId;
        $wm->last_run_at = now();
        $wm->save();
        return $wm;
    }

    /** Lưu watermark khi quét theo modify_time (vd HIS_SERVICE_REQ có index modify_time). */
    public function saveWatermarkModify($sourceKey, $lastModifyTime, $lastId)
    {
        $wm = $this->getWatermark($sourceKey);
        $wm->last_modify_time = $lastModifyTime;
        $wm->last_id = $lastId;
        $wm->last_run_at = now();
        $wm->save();
        return $wm;
    }

    /**
     * Ghi 1 violation idempotent theo dedup_key. Trả true nếu tạo mới.
     * @param Violation $vio
     * @param ViolationContext $ctx
     * @param OrderCheckRule $rule
     */
    public function persist(Violation $vio, ViolationContext $ctx, OrderCheckRule $rule)
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
