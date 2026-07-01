<?php

namespace App\Services\OrderCheck\RuleHandlers\ServiceReq\Types;

use App\Services\OrderCheck\Contracts\TypeRules;

/**
 * Luật CHỈ áp cho phiếu loại "Ngoài khám chữa bệnh" (SERVICE_REQ_TYPE id=18).
 */
class NgoaiKcbRules implements TypeRules
{
    public function typeId()
    {
        return 18;
    }

    public function handlers()
    {
        // Thêm luật CHỈ áp cho loại "Ngoài khám chữa bệnh" vào đây, vd: return [new NgoaiKcbXxxRule()];
        return [];
    }
}
