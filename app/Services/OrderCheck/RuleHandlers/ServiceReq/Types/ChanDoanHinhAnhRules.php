<?php

namespace App\Services\OrderCheck\RuleHandlers\ServiceReq\Types;

use App\Services\OrderCheck\Contracts\TypeRules;

/**
 * Luật CHỈ áp cho phiếu loại "Chẩn đoán hình ảnh" (SERVICE_REQ_TYPE id=3).
 */
class ChanDoanHinhAnhRules implements TypeRules
{
    public function typeId()
    {
        return 3;
    }

    public function handlers()
    {
        // Thêm luật CHỈ áp cho loại "Chẩn đoán hình ảnh" vào đây, vd: return [new ChanDoanHinhAnhXxxRule()];
        return [];
    }
}
