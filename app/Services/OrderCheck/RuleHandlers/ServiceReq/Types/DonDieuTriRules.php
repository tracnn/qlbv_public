<?php

namespace App\Services\OrderCheck\RuleHandlers\ServiceReq\Types;

use App\Services\OrderCheck\Contracts\TypeRules;

/**
 * Luật CHỈ áp cho phiếu loại "Đơn điều trị" (SERVICE_REQ_TYPE id=15).
 */
class DonDieuTriRules implements TypeRules
{
    public function typeId()
    {
        return 15;
    }

    public function handlers()
    {
        // Thêm luật CHỈ áp cho loại "Đơn điều trị" vào đây, vd: return [new DonDieuTriXxxRule()];
        return [];
    }
}
