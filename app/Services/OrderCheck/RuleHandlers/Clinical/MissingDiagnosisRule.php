<?php

namespace App\Services\OrderCheck\RuleHandlers\Clinical;

use App\Services\OrderCheck\Contracts\RuleHandler;
use App\Services\OrderCheck\Support\OrderContext;
use App\Services\OrderCheck\Support\Violation;

class MissingDiagnosisRule implements RuleHandler
{
    public function code()
    {
        return 'A_MISSING_DIAGNOSIS';
    }

    public function check(OrderContext $c)
    {
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
