<?php

namespace App\Services\OrderCheck\RuleHandlers\ServiceReq\Types;

use App\Services\OrderCheck\Contracts\TypeRules;

/**
 * Luật CHỈ áp cho phiếu loại "Đơn tủ trực" (SERVICE_REQ_TYPE id=14).
 */
class DonTuTrucRules implements TypeRules
{
    public function typeId()
    {
        return 14;
    }

    public function handlers()
    {
        // Thêm luật CHỈ áp cho loại "Đơn tủ trực" vào đây, vd: return [new DonTuTrucXxxRule()];
        return [];
    }
}
