<?php

namespace App\Services\OrderCheck\RuleHandlers\ServiceReq\Types;

use App\Services\OrderCheck\Contracts\TypeRules;

/**
 * Luật CHỈ áp cho phiếu loại "Khác" (SERVICE_REQ_TYPE id=11).
 */
class KhacRules implements TypeRules
{
    public function typeId()
    {
        return 11;
    }

    public function handlers()
    {
        // Thêm luật CHỈ áp cho loại "Khác" vào đây, vd: return [new KhacXxxRule()];
        return [];
    }
}
