<?php

namespace App\Services\OrderCheck\RuleHandlers;

use App\Services\OrderCheck\RuleHandlers\Structural\DischargeBeforeAdmissionRule;
use App\Services\OrderCheck\RuleHandlers\Structural\OrderTimeOutOfStayRule;
use App\Services\OrderCheck\RuleHandlers\Structural\ExecuteBeforeOrderRule;
use App\Services\OrderCheck\RuleHandlers\Structural\DoctorPracticeCertRule;

/**
 * CHỖ CẬP NHẬT RIÊNG cho luật Họ B (hardcode).
 * Thêm luật mới = thêm 1 class trong Structural/ + 1 dòng vào đây.
 */
class StructuralRuleRegistry
{
    /**
     * @return \App\Services\OrderCheck\Contracts\RuleHandler[]
     */
    public static function handlers()
    {
        return [
            new DischargeBeforeAdmissionRule(),
            new OrderTimeOutOfStayRule(),
            new ExecuteBeforeOrderRule(),
            new DoctorPracticeCertRule(),
        ];
    }
}
