<?php

namespace App\Services\OrderCheck\RuleHandlers\Clinical;

use App\Services\OrderCheck\Support\OrderContext;

/**
 * Ma benh ICD10 cua phieu chi dinh khong co trong danh muc ICD10 dang hoat dong.
 *
 * Do tren 7 ngay that: 9.962/60.682 phieu dinh loi (16,42%), 11.887 dong vi pham, do 287
 * ma gay ra. Nguyen nhan chinh la HIS khai ma chi tiet hon danh muc BHYT (M47.86 trong khi
 * danh muc chi co M47.8). KHONG duoc chuan hoa bang cach cat bot ky tu: danh muc van co
 * 629 ma dai 6 va 412 ma dai 7 ky tu.
 */
class IcdNotInCatalogRule extends IcdCatalogRule
{
    public function code()    { return 'A_ICD_NOT_IN_CATALOG'; }
    protected function bang() { return 'icd10_categories'; }
    protected function nhan() { return 'danh mục ICD10'; }

    protected function maChinh(OrderContext $c) { return $c->icdCode; }
    protected function maPhu(OrderContext $c)   { return $c->icdSubCode; }
}
