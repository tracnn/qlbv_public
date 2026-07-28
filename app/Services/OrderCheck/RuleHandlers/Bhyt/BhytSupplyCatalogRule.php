<?php

namespace App\Services\OrderCheck\RuleHandlers\Bhyt;

/** Ma vat tu BHYT cua dong BHYT khong co trong danh muc vat tu con hieu luc. */
class BhytSupplyCatalogRule extends BhytCatalogRule
{
    public function code()          { return 'A_BHYT_SUPPLY_NOT_IN_CATALOG'; }
    protected function bang()       { return 'medical_supply_catalogs'; }
    protected function cot()        { return 'ma_vat_tu'; }
    protected function cotTen()     { return 'ten_vat_tu'; }
    protected function nhan()       { return 'Mã vật tư'; }
    protected function loaiDichVu() { return [self::LOAI_VAT_TU]; }
}
