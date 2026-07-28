<?php

namespace App\Services\OrderCheck\RuleHandlers\Clinical;

use App\Services\OrderCheck\Support\OrderContext;

/**
 * Ma benh YHCT cua phieu chi dinh khong co trong danh muc ICD YHCT.
 *
 * Do tren 7 ngay that: 1.199 phieu co ma YHCT, 41 ma phan biet, KHONG ma nao sai. Luat nay
 * se IM LANG sau khi bat - DO LA DUNG, KHONG PHAI HONG.
 *
 * Van viet vi day la so 0 TINH CO chu khong phai so 0 CAU TRUC: 41 ma hien dung ngau nhien
 * deu hop le, nhung khong co gi ngan bac si go mot ma moi hay danh muc doi o dot cap nhat
 * sau. Doi lai, quy tac "thieu ten BHYT" da bi bo o dot truoc vi so 0 cua no la cau truc -
 * HIS khong cho khai ma ma thieu ten.
 *
 * KHONG bac cau sang icd10_categories. Xml3176Xml3Checker co lam viec do de goi y ma tuong
 * duong, nhung nguoi dung da chot bo o dot nay.
 */
class IcdYhctNotInCatalogRule extends IcdCatalogRule
{
    public function code()    { return 'A_ICD_YHCT_NOT_IN_CATALOG'; }
    protected function bang() { return 'icd_yhct_categories'; }
    protected function nhan() { return 'danh mục ICD YHCT'; }

    protected function maChinh(OrderContext $c) { return $c->traditionalIcdCode; }
    protected function maPhu(OrderContext $c)   { return $c->traditionalIcdSubCode; }
}
