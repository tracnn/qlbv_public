<?php

namespace App\Services\OrderCheck\RuleHandlers\Bhyt;

use App\Services\Xml3176\Support\MaDvktMatcher;

/** Ma DVKT BHYT cua dong BHYT khong co trong danh muc dich vu con hieu luc. */
class BhytServiceCatalogRule extends BhytCatalogRule
{
    public function code()          { return 'A_BHYT_SERVICE_NOT_IN_CATALOG'; }
    protected function bang()       { return 'service_catalogs'; }
    protected function cot()        { return 'ma_dich_vu'; }
    protected function cotTen()     { return 'ten_dich_vu'; }
    protected function nhan()       { return 'Mã dịch vụ'; }
    protected function loaiDichVu() { return null; }   // phan bu: moi loai tru Thuoc va Vat tu

    /** Ma co hau to _XX (vd 18.0593.0059_TB) khong co nguyen van thi tra ma goc. */
    protected function maDuPhong($ma)
    {
        $goc = MaDvktMatcher::maGoc($ma);

        return $goc !== '' && $goc !== trim((string) $ma) ? [$goc] : [];
    }
}
