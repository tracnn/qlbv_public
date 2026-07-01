<?php

namespace App\Services\OrderCheck\RuleHandlers\ServiceReq\Types;

use App\Services\OrderCheck\Contracts\TypeRules;

/**
 * Luật CHỈ áp cho phiếu loại "Xét nghiệm" (SERVICE_REQ_TYPE id=2).
 */
class XetNghiemRules implements TypeRules
{
    public function typeId()
    {
        return 2;
    }

    public function handlers()
    {
        // Thêm luật CHỈ áp cho loại "Xét nghiệm" vào đây, vd: return [new XetNghiemXxxRule()];
        return [];
    }
}
