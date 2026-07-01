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
        $this->assertCount(5, $handlers);
        $codes = array_map(function (RuleHandler $h) { return $h->code(); }, $handlers);
        $this->assertContains('A_MISSING_DIAGNOSIS', $codes);
        $this->assertContains('B_DOCTOR_NO_PRACTICE_CERT', $codes);
    }

    public function test_for_type_chua_co_luat_rieng_tra_mang_rong()
    {
        $this->assertSame([], ServiceReqRuleRegistry::forType(2));   // Xét nghiệm
        $this->assertSame([], ServiceReqRuleRegistry::forType(999)); // không tồn tại
        $this->assertSame([], ServiceReqRuleRegistry::forType(null));
    }
}
