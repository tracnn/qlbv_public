<?php

namespace App\Services\OrderCheck\RuleHandlers\ServiceReq\Types;

use App\Services\OrderCheck\Contracts\TypeRules;

/**
 * Luật CHỈ áp cho phiếu loại "Siêu âm" (SERVICE_REQ_TYPE id=9).
 */
class SieuAmRules implements TypeRules
{
    public function typeId()
    {
        return 9;
    }

    public function handlers()
    {
        // Thêm luật CHỈ áp cho loại "Siêu âm" vào đây, vd: return [new SieuAmXxxRule()];
        return [];
    }
}
