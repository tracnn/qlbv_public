<?php

namespace App\Services\OrderCheck\RuleHandlers\ServiceReq\Types;

use App\Services\OrderCheck\Contracts\TypeRules;

/**
 * Luật CHỈ áp cho phiếu loại "Đơn máu" (SERVICE_REQ_TYPE id=16).
 */
class DonMauRules implements TypeRules
{
    public function typeId()
    {
        return 16;
    }

    public function handlers()
    {
        // Thêm luật CHỈ áp cho loại "Đơn máu" vào đây, vd: return [new DonMauXxxRule()];
        return [];
    }
}
