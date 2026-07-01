<?php

namespace App\Services\OrderCheck\RuleHandlers\ServiceReq\Types;

use App\Services\OrderCheck\Contracts\TypeRules;

/**
 * Luật CHỈ áp cho phiếu loại "Khám" (SERVICE_REQ_TYPE id=1).
 */
class KhamRules implements TypeRules
{
    public function typeId()
    {
        return 1;
    }

    public function handlers()
    {
        // Thêm luật CHỈ áp cho loại "Khám" vào đây, vd: return [new KhamXxxRule()];
        return [];
    }
}
