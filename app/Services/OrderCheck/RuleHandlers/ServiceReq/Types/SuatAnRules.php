<?php

namespace App\Services\OrderCheck\RuleHandlers\ServiceReq\Types;

use App\Services\OrderCheck\Contracts\TypeRules;

/**
 * Luật CHỈ áp cho phiếu loại "Suất ăn" (SERVICE_REQ_TYPE id=17).
 */
class SuatAnRules implements TypeRules
{
    public function typeId()
    {
        return 17;
    }

    public function handlers()
    {
        // Thêm luật CHỈ áp cho loại "Suất ăn" vào đây, vd: return [new SuatAnXxxRule()];
        return [];
    }
}
