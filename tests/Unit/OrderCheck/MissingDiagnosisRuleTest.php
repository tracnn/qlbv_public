<?php

namespace Tests\Unit\OrderCheck;

use Tests\TestCase;
use App\Services\OrderCheck\Support\OrderContext;
use App\Services\OrderCheck\RuleHandlers\Clinical\MissingDiagnosisRule;

class MissingDiagnosisRuleTest extends TestCase
{
    private function ctx($icd, $typeId = null)
    {
        $c = new OrderContext();
        $c->serviceReqId = 7;
        $c->treatmentId = 70;
        $c->icdCode = $icd;
        $c->serviceReqTypeId = $typeId;
        return $c;
    }

    public function test_thieu_icd_phat_hien_loi()
    {
        $rule = new MissingDiagnosisRule([1]);
        $vios = $rule->check($this->ctx('', 2)); // loại Xét nghiệm
        $this->assertCount(1, $vios);
        $this->assertSame('service_req', $vios[0]->orderRefType);
        $this->assertSame(7, $vios[0]->orderRefId);
    }

    public function test_icd_null_phat_hien_loi()
    {
        $rule = new MissingDiagnosisRule([1]);
        $this->assertCount(1, $rule->check($this->ctx(null, 3)));
    }

    public function test_co_icd_khong_loi()
    {
        $rule = new MissingDiagnosisRule([1]);
        $this->assertCount(0, $rule->check($this->ctx('J18', 2)));
    }

    public function test_loai_kham_thi_bo_qua_du_thieu_icd()
    {
        $rule = new MissingDiagnosisRule([1]); // 1 = Khám
        $this->assertCount(0, $rule->check($this->ctx('', 1)));
    }

    public function test_loai_khac_van_bat_khi_thieu_icd()
    {
        $rule = new MissingDiagnosisRule([1]);
        $this->assertCount(1, $rule->check($this->ctx('', 7))); // Giường
    }
}
