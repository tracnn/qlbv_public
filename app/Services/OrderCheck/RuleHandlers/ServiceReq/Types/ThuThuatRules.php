<?php

namespace App\Services\OrderCheck\RuleHandlers\ServiceReq\Types;

use App\Services\OrderCheck\Contracts\TypeRules;

/**
 * Luật CHỈ áp cho phiếu loại "Thủ thuật" (SERVICE_REQ_TYPE id=4).
 */
class ThuThuatRules implements TypeRules
{
    public function typeId()
    {
        return 4;
    }

    public function handlers()
    {
        // Thêm luật CHỈ áp cho loại "Thủ thuật" vào đây, vd: return [new ThuThuatXxxRule()];
        return [];
    }
}
