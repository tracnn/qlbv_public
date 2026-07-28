<?php

namespace App\Services\OrderCheck\RuleHandlers\ServiceReq;

use App\Services\OrderCheck\RuleHandlers\Structural\DischargeBeforeAdmissionRule;
use App\Services\OrderCheck\RuleHandlers\Structural\OrderTimeOutOfStayRule;
use App\Services\OrderCheck\RuleHandlers\Structural\ExecuteBeforeOrderRule;
use App\Services\OrderCheck\RuleHandlers\Structural\DoctorPracticeCertRule;
use App\Services\OrderCheck\RuleHandlers\Clinical\MissingDiagnosisRule;
use App\Services\OrderCheck\RuleHandlers\Bhyt\BhytCodeMissingRule;
use App\Services\OrderCheck\RuleHandlers\Bhyt\BhytServiceCatalogRule;
use App\Services\OrderCheck\RuleHandlers\Bhyt\BhytDrugCatalogRule;
use App\Services\OrderCheck\RuleHandlers\Bhyt\BhytSupplyCatalogRule;
use App\Services\OrderCheck\RuleHandlers\Bhyt\BhytServiceNameRule;
use App\Services\OrderCheck\RuleHandlers\Bhyt\BhytDrugNameRule;
use App\Services\OrderCheck\RuleHandlers\Bhyt\BhytSupplyNameRule;
use App\Services\OrderCheck\RuleHandlers\Clinical\IcdNotInCatalogRule;
use App\Services\OrderCheck\RuleHandlers\Clinical\IcdYhctNotInCatalogRule;
use App\Services\OrderCheck\RuleHandlers\Clinical\StaffCertNotInCatalogRule;

/**
 * Luật cấp phiếu áp cho MỌI loại dịch vụ.
 * Thêm luật áp cho tất cả loại vào mảng handlers() dưới đây.
 */
class CommonRules
{
    /** @return \App\Services\OrderCheck\Contracts\RuleHandler[] */
    public static function handlers()
    {
        return [
            new DischargeBeforeAdmissionRule(),
            new OrderTimeOutOfStayRule(),
            new ExecuteBeforeOrderRule(),
            new DoctorPracticeCertRule(),
            new MissingDiagnosisRule(),

            // Nhom doi chieu danh muc BHYT. Chi chay tren DONG thuoc doi tuong BHYT, dung
            // loai dich vu, va tu im lang khi bang danh muc con rong.
            new BhytCodeMissingRule(),
            new BhytServiceCatalogRule(),
            new BhytDrugCatalogRule(),
            new BhytSupplyCatalogRule(),

            // Doi chieu TEN: BHXH tu choi ca khi ten lech chu khong chi khi ma sai.
            new BhytServiceNameRule(),
            new BhytDrugNameRule(),
            new BhytSupplyNameRule(),

            // Doi chieu danh muc trong ung dung. Ba luat deu tu im lang khi bang danh muc
            // con rong. Khong loc theo doi tuong BHYT: ma benh sai va CCHN sai la loi ho
            // so bat ke ai chi tra.
            new IcdNotInCatalogRule(),
            new IcdYhctNotInCatalogRule(),
            new StaffCertNotInCatalogRule(),
        ];
    }
}
