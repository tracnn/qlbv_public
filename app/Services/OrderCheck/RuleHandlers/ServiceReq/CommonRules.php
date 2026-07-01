<?php

namespace App\Services\OrderCheck\RuleHandlers\ServiceReq;

use App\Services\OrderCheck\RuleHandlers\Structural\DischargeBeforeAdmissionRule;
use App\Services\OrderCheck\RuleHandlers\Structural\OrderTimeOutOfStayRule;
use App\Services\OrderCheck\RuleHandlers\Structural\ExecuteBeforeOrderRule;
use App\Services\OrderCheck\RuleHandlers\Structural\DoctorPracticeCertRule;
use App\Services\OrderCheck\RuleHandlers\Clinical\MissingDiagnosisRule;

/**
 * Luật cấp phiếu áp cho MỌI loại dịch vụ.
 * Thêm luật áp cho tất cả loại vào mảng handlers() dưới đây.
 */
class CommonRules
{
    /** @return \App\Services\OrderCheck\Contracts\RuleHandler[] */
    public static function handlers()
    {
        return [
            new DischargeBeforeAdmissionRule(),
            new OrderTimeOutOfStayRule(),
            new ExecuteBeforeOrderRule(),
            new DoctorPracticeCertRule(),
            new MissingDiagnosisRule(),
        ];
    }
}
