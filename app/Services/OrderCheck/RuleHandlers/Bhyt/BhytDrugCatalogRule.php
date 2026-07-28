<?php

namespace App\Services\OrderCheck\RuleHandlers\Bhyt;

/** Ma thuoc BHYT cua dong BHYT khong co trong danh muc thuoc con hieu luc. */
class BhytDrugCatalogRule extends BhytCatalogRule
{
    public function code()          { return 'A_BHYT_DRUG_NOT_IN_CATALOG'; }
    protected function bang()       { return 'medicine_catalogs'; }
    protected function cot()        { return 'ma_thuoc'; }
    protected function cotTen()     { return 'ten_thuoc'; }
    protected function nhan()       { return 'Mã thuốc'; }
    protected function loaiDichVu() { return [self::LOAI_THUOC]; }
}
