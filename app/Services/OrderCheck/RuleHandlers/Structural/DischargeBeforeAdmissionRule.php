<?php

namespace App\Services\OrderCheck\RuleHandlers\Structural;

use App\Services\OrderCheck\Contracts\RuleHandler;
use App\Services\OrderCheck\Support\OrderContext;
use App\Services\OrderCheck\Support\Violation;

class DischargeBeforeAdmissionRule implements RuleHandler
{
    public function code()
    {
        return 'B_DISCHARGE_BEFORE_ADMISSION';
    }

    public function check(OrderContext $c)
    {
        if ($c->outTime > 0 && $c->inTime > 0 && $c->outTime < $c->inTime) {
            return [new Violation(
                $this->code(),
                'treatment',
                $c->treatmentId,
                'Ngày ra viện (' . $c->outTime . ') trước ngày vào viện (' . $c->inTime . ')',
                ['in_time' => $c->inTime, 'out_time' => $c->outTime]
            )];
        }
        return [];
    }
}
