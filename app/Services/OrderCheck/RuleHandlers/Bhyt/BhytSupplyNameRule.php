<?php

namespace App\Services\OrderCheck\RuleHandlers\Bhyt;

/** Ten vat tu khai o HIS lech ten trong danh muc vat tu BHYT con hieu luc. */
class BhytSupplyNameRule extends BhytNameMismatchRule
{
    public function code()          { return 'A_BHYT_SUPPLY_NAME_MISMATCH'; }
    protected function bang()       { return 'medical_supply_catalogs'; }
    protected function cot()        { return 'ma_vat_tu'; }
    protected function cotTen()     { return 'ten_vat_tu'; }
    protected function nhan()       { return 'Tên vật tư'; }
    protected function loaiDichVu() { return [self::LOAI_VAT_TU]; }
}
