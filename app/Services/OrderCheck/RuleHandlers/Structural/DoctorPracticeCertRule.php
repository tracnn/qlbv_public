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
        $hasDoctor = !empty(trim((string) $c->doctorLoginname));
        $noCert = empty(trim((string) $c->doctorPracticeScope));
        if ($hasDoctor && $noCert) {
            return [new Violation(
                $this->code(), 'service_req', $c->serviceReqId,
                'Bác sĩ ra y lệnh (' . $c->doctorLoginname . ') chưa có/không hợp lệ chứng chỉ hành nghề',
                ['doctor_loginname' => $c->doctorLoginname]
            )];
        }
        return [];
    }
}
