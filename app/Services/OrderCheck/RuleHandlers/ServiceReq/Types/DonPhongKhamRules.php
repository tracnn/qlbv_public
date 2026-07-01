<?php

namespace App\Services\OrderCheck\RuleHandlers\ServiceReq\Types;

use App\Services\OrderCheck\Contracts\TypeRules;

/**
 * Luật CHỈ áp cho phiếu loại "Đơn phòng khám" (SERVICE_REQ_TYPE id=6).
 */
class DonPhongKhamRules implements TypeRules
{
    public function typeId()
    {
        return 6;
    }

    public function handlers()
    {
        // Thêm luật CHỈ áp cho loại "Đơn phòng khám" vào đây, vd: return [new DonPhongKhamXxxRule()];
        return [];
    }
}
