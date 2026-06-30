<?php

namespace App\Services\OrderCheck\RuleHandlers\Structural;

use App\Services\OrderCheck\Contracts\RuleHandler;
use App\Services\OrderCheck\Support\OrderContext;
use App\Services\OrderCheck\Support\Violation;

class DoctorPracticeCertRule implements RuleHandler
{
    public function code()
    {
        return 'B_DOCTOR_NO_PRACTICE_CERT';
    }

    public function check(OrderContext $c)
    {
        $hasExecutor = !empty(trim((string) $c->executeLoginname));
        $noCert = empty(trim((string) $c->executeDiploma));
        if ($hasExecutor && $noCert) {
            return [new Violation(
                $this->code(), 'service_req', $c->serviceReqId,
                'Người thực hiện (' . $c->executeLoginname . ') chưa có/không hợp lệ chứng chỉ hành nghề',
                ['execute_loginname' => $c->executeLoginname]
            )];
        }
        return [];
    }
}
