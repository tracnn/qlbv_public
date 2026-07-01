<?php

namespace App\Services\OrderCheck\RuleHandlers\ServiceReq\Types;

use App\Services\OrderCheck\Contracts\TypeRules;

/**
 * Luật CHỈ áp cho phiếu loại "Giải phẫu bệnh" (SERVICE_REQ_TYPE id=13).
 */
class GiaiPhauBenhRules implements TypeRules
{
    public function typeId()
    {
        return 13;
    }

    public function handlers()
    {
        // Thêm luật CHỈ áp cho loại "Giải phẫu bệnh" vào đây, vd: return [new GiaiPhauBenhXxxRule()];
        return [];
    }
}
