<?php

namespace App\Services\OrderCheck\Contracts;

/**
 * Nhóm luật cấp phiếu áp cho MỘT loại dịch vụ (SERVICE_REQ_TYPE).
 */
interface TypeRules
{
    /** Id loại phiếu (HIS_SERVICE_REQ_TYPE). @return int */
    public function typeId();

    /** Handler CHỈ áp cho loại này. @return \App\Services\OrderCheck\Contracts\RuleHandler[] */
    public function handlers();
}
