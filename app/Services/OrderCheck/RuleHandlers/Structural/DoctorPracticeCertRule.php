<?php

namespace App\Services\OrderCheck\RuleHandlers\Structural;

use App\Services\OrderCheck\Contracts\RuleHandler;
use App\Services\OrderCheck\Support\DsMienCchn;
use App\Services\OrderCheck\Support\OrderContext;
use App\Services\OrderCheck\Support\Violation;

class DoctorPracticeCertRule implements RuleHandler
{
    /** @var int[] loai phieu khong ap luat nay */
    protected $excludeTypeIds;

    /** @var string[] loginname nguoi thuc hien duoc mien kiem CCHN, da chuan hoa */
    protected $excludeLoginnames;

    public function __construct(array $excludeTypeIds = null, array $excludeLoginnames = null)
    {
        if ($excludeTypeIds === null) {
            $csv = trim((string) config('order_check.practice_cert_exclude_type_ids', ''));
            $excludeTypeIds = $csv === '' ? [] : array_map('intval', array_filter(explode(',', $csv), 'strlen'));
        }

        if ($excludeLoginnames === null) {
            $excludeLoginnames = DsMienCchn::doc(config('order_check.practice_cert_exclude_loginnames'));
        }

        $this->excludeTypeIds = $excludeTypeIds;
        // Chuan hoa truc tiep tung phan tu (khong di vong qua implode/explode) de tranh
        // bay ngam: mot loginname chua dau phay se bi tach lam hai neu dung implode(',').
        $this->excludeLoginnames = array_values(array_filter(
            array_map(function ($t) {
                return mb_strtolower(trim((string) $t));
            }, $excludeLoginnames),
            'strlen'
        ));
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

        // Tai khoan tich hop may moc (mitalab, vietrad, sys) khong phai nguoi nen khong the
        // co CCHN. Do 30/07/2026: chung chiem 99,2% vi pham cua quy tac nay.
        if (DsMienCchn::duocMien($c->executeLoginname, $this->excludeLoginnames)) {
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
