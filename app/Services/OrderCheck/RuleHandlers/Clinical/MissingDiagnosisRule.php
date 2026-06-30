<?php

namespace App\Services\OrderCheck\RuleHandlers\Clinical;

use App\Services\OrderCheck\Contracts\RuleHandler;
use App\Services\OrderCheck\Support\OrderContext;
use App\Services\OrderCheck\Support\Violation;

class MissingDiagnosisRule implements RuleHandler
{
    /** @var int[] loại phiếu được loại trừ (vd Khám) */
    protected $excludeTypeIds;

    public function __construct(array $excludeTypeIds = null)
    {
        if ($excludeTypeIds === null) {
            $csv = trim((string) config('order_check.missing_diagnosis_exclude_type_ids', ''));
            $excludeTypeIds = $csv === '' ? [] : array_map('intval', array_filter(explode(',', $csv), 'strlen'));
        }
        $this->excludeTypeIds = $excludeTypeIds;
    }

    public function code()
    {
        return 'A_MISSING_DIAGNOSIS';
    }

    public function check(OrderContext $c)
    {
        // Loại trừ một số loại phiếu (vd Khám: chẩn đoán có sau khám)
        if ($c->serviceReqTypeId !== null && in_array((int) $c->serviceReqTypeId, $this->excludeTypeIds, true)) {
            return [];
        }

        if (empty(trim((string) $c->icdCode))) {
            return [new Violation(
                $this->code(),
                'service_req',
                $c->serviceReqId,
                'Phiếu chỉ định thiếu mã chẩn đoán ICD',
                ['service_req_code' => $c->serviceReqCode]
            )];
        }
        return [];
    }
}
