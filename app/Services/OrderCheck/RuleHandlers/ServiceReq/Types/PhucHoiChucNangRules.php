<?php

namespace App\Services\OrderCheck\RuleHandlers\ServiceReq\Types;

use App\Services\OrderCheck\Contracts\TypeRules;

/**
 * Luật CHỈ áp cho phiếu loại "Phục hồi chức năng" (SERVICE_REQ_TYPE id=12).
 */
class PhucHoiChucNangRules implements TypeRules
{
    public function typeId()
    {
        return 12;
    }

    public function handlers()
    {
        // Thêm luật CHỈ áp cho loại "Phục hồi chức năng" vào đây, vd: return [new PhucHoiChucNangXxxRule()];
        return [];
    }
}
