<?php

namespace App\Services\OrderCheck\RuleHandlers\ServiceReq\Types;

use App\Services\OrderCheck\Contracts\TypeRules;

/**
 * Luật CHỈ áp cho phiếu loại "Thăm dò chức năng" (SERVICE_REQ_TYPE id=5).
 */
class ThamDoChucNangRules implements TypeRules
{
    public function typeId()
    {
        return 5;
    }

    public function handlers()
    {
        // Thêm luật CHỈ áp cho loại "Thăm dò chức năng" vào đây, vd: return [new ThamDoChucNangXxxRule()];
        return [];
    }
}
