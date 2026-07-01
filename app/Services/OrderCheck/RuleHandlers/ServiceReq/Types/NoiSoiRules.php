<?php

namespace App\Services\OrderCheck\RuleHandlers\ServiceReq\Types;

use App\Services\OrderCheck\Contracts\TypeRules;

/**
 * Luật CHỈ áp cho phiếu loại "Nội soi" (SERVICE_REQ_TYPE id=8).
 */
class NoiSoiRules implements TypeRules
{
    public function typeId()
    {
        return 8;
    }

    public function handlers()
    {
        // Thêm luật CHỈ áp cho loại "Nội soi" vào đây, vd: return [new NoiSoiXxxRule()];
        return [];
    }
}
