<?php

namespace Tests\Unit\OrderCheck;

use Tests\TestCase;
use App\Services\OrderCheck\RuleHandlers\ServiceReq\ServiceReqRuleRegistry;
use App\Services\OrderCheck\Contracts\RuleHandler;

class ServiceReqRuleRegistryTest extends TestCase
{
    public function test_common_tra_ve_cac_handler_ap_moi_loai()
    {
        $handlers = ServiceReqRuleRegistry::common();
        $codes = array_map(function (RuleHandler $h) { return $h->code(); }, $handlers);

        // Kiem theo MA thay vi dem so luong: dem cung so thi moi lan them mot quy tac la
        // test do ma khong noi len dieu gi sai.
        foreach ([
            'B_DISCHARGE_BEFORE_ADMISSION',
            'B_ORDER_TIME_OUT_OF_STAY',
            'B_EXECUTE_BEFORE_ORDER',
            'B_DOCTOR_NO_PRACTICE_CERT',
            'A_MISSING_DIAGNOSIS',
            'A_BHYT_CODE_MISSING',
            'A_BHYT_SERVICE_NOT_IN_CATALOG',
            'A_BHYT_DRUG_NOT_IN_CATALOG',
            'A_BHYT_SUPPLY_NOT_IN_CATALOG',
            'A_BHYT_SERVICE_NAME_MISMATCH',
            'A_BHYT_DRUG_NAME_MISMATCH',
            'A_BHYT_SUPPLY_NAME_MISMATCH',
        ] as $ma) {
            $this->assertContains($ma, $codes, "Thieu handler $ma trong common()");
        }

        $this->assertCount(count(array_unique($codes)), $codes, 'Co ma quy tac bi trung');
    }

    public function test_for_type_chua_co_luat_rieng_tra_mang_rong()
    {
        $this->assertSame([], ServiceReqRuleRegistry::forType(2));   // Xét nghiệm
        $this->assertSame([], ServiceReqRuleRegistry::forType(999)); // không tồn tại
        $this->assertSame([], ServiceReqRuleRegistry::forType(null));
    }
}
