<?php

namespace App\Services\OrderCheck\RuleHandlers\Structural;

use App\Services\OrderCheck\Contracts\RuleHandler;
use App\Services\OrderCheck\Support\OrderContext;
use App\Services\OrderCheck\Support\Violation;

class DoctorPracticeCertRule implements RuleHandler
{
    /** @var int[] loai phieu khong ap luat nay */
    protected $excludeTypeIds;

    public function __construct(array $excludeTypeIds = null)
    {
        if ($excludeTypeIds === null) {
            $csv = trim((string) config('order_check.practice_cert_exclude_type_ids', ''));
            $excludeTypeIds = $csv === '' ? [] : array_map('intval', array_filter(explode(',', $csv), 'strlen'));
        }

        $this->excludeTypeIds = $excludeTypeIds;
    }

    public function code()
    {
        return 'B_DOCTOR_NO_PRACTICE_CERT';
    }

    public function check(OrderContext $c)
    {
        // Don thuoc (Don phong kham, Don tu truc, Don dieu tri): nguoi thuc hien la duoc si
        // hoac dieu duong cap phat, khong phai nguoi can CCHN theo nghia cua luat nay.
        if ($c->serviceReqTypeId !== null
            && in_array((int) $c->serviceReqTypeId, $this->excludeTypeIds, true)) {
            return [];
        }

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
