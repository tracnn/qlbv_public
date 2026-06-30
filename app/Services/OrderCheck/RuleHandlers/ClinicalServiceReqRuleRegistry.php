<?php

namespace App\Services\OrderCheck\RuleHandlers;

use App\Services\OrderCheck\RuleHandlers\Clinical\MissingDiagnosisRule;

/**
 * Handler Họ A áp dụng trên OrderContext (cấp phiếu chỉ định).
 * Thêm handler cấp phiếu mới = thêm 1 dòng vào đây.
 */
class ClinicalServiceReqRuleRegistry
{
    /** @return \App\Services\OrderCheck\Contracts\RuleHandler[] */
    public static function handlers()
    {
        return [
            new MissingDiagnosisRule(),
        ];
    }
}
