<?php

namespace App\Services\OrderCheck\RuleHandlers\Bhyt;

/** Ten DVKT khai o HIS lech ten trong danh muc dich vu BHYT con hieu luc. */
class BhytServiceNameRule extends BhytNameMismatchRule
{
    public function code()          { return 'A_BHYT_SERVICE_NAME_MISMATCH'; }
    protected function bang()       { return 'service_catalogs'; }
    protected function cot()        { return 'ma_dich_vu'; }
    protected function cotTen()     { return 'ten_dich_vu'; }
    protected function nhan()       { return 'Tên dịch vụ'; }
    protected function loaiDichVu() { return null; }   // phan bu: moi loai tru Thuoc va Vat tu
}
