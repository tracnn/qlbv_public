<?php

namespace App\Services\OrderCheck\RuleHandlers\ServiceReq\Types;

use App\Services\OrderCheck\Contracts\TypeRules;

/**
 * Luật CHỈ áp cho phiếu loại "Giường" (SERVICE_REQ_TYPE id=7).
 */
class GiuongRules implements TypeRules
{
    public function typeId()
    {
        return 7;
    }

    public function handlers()
    {
        // Thêm luật CHỈ áp cho loại "Giường" vào đây, vd: return [new GiuongXxxRule()];
        return [];
    }
}
