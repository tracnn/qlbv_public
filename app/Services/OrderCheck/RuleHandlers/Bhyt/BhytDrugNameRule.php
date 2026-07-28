<?php

namespace App\Services\OrderCheck\RuleHandlers\Bhyt;

/** Ten thuoc khai o HIS lech ten trong danh muc thuoc BHYT con hieu luc. */
class BhytDrugNameRule extends BhytNameMismatchRule
{
    public function code()          { return 'A_BHYT_DRUG_NAME_MISMATCH'; }
    protected function bang()       { return 'medicine_catalogs'; }
    protected function cot()        { return 'ma_thuoc'; }
    protected function cotTen()     { return 'ten_thuoc'; }
    protected function nhan()       { return 'Tên thuốc'; }
    protected function loaiDichVu() { return [self::LOAI_THUOC]; }
}
