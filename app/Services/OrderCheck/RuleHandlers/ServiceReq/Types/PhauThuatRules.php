<?php

namespace App\Services\OrderCheck\RuleHandlers\ServiceReq\Types;

use App\Services\OrderCheck\Contracts\TypeRules;

/**
 * Luật CHỈ áp cho phiếu loại "Phẫu thuật" (SERVICE_REQ_TYPE id=10).
 */
class PhauThuatRules implements TypeRules
{
    public function typeId()
    {
        return 10;
    }

    public function handlers()
    {
        // Thêm luật CHỈ áp cho loại "Phẫu thuật" vào đây, vd: return [new PhauThuatXxxRule()];
        return [];
    }
}
