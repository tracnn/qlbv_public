<?php

namespace Tests\Unit\OrderCheck;

use Tests\TestCase;
use App\Services\OrderCheck\Support\OrderContext;
use App\Services\OrderCheck\RuleHandlers\Clinical\MissingDiagnosisRule;

class MissingDiagnosisRuleTest extends TestCase
{
    private function ctx($icd)
    {
        $c = new OrderContext();
        $c->serviceReqId = 7;
        $c->treatmentId = 70;
        $c->icdCode = $icd;
        return $c;
    }

    public function test_thieu_icd_phat_hien_loi()
    {
        $rule = new MissingDiagnosisRule();
        $vios = $rule->check($this->ctx(''));
        $this->assertCount(1, $vios);
        $this->assertSame('service_req', $vios[0]->orderRefType);
        $this->assertSame(7, $vios[0]->orderRefId);
    }

    public function test_icd_null_phat_hien_loi()
    {
        $rule = new MissingDiagnosisRule();
        $this->assertCount(1, $rule->check($this->ctx(null)));
    }

    public function test_co_icd_khong_loi()
    {
        $rule = new MissingDiagnosisRule();
        $this->assertCount(0, $rule->check($this->ctx('J18')));
    }
}
