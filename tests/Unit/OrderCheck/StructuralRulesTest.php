<?php

namespace Tests\Unit\OrderCheck;

use Tests\TestCase;
use App\Services\OrderCheck\Support\OrderContext;
use App\Services\OrderCheck\Support\OrderService;
use App\Services\OrderCheck\RuleHandlers\Structural\DischargeBeforeAdmissionRule;
use App\Services\OrderCheck\RuleHandlers\Structural\OrderTimeOutOfStayRule;
use App\Services\OrderCheck\RuleHandlers\Structural\ExecuteBeforeOrderRule;
use App\Services\OrderCheck\RuleHandlers\Structural\DoctorPracticeCertRule;

class StructuralRulesTest extends TestCase
{
    private function ctx(array $over = [])
    {
        $c = new OrderContext();
        $c->serviceReqId = 1;
        $c->treatmentId = 100;
        $c->intructionTime = 20260101080000;
        $c->inTime = 20260101070000;
        $c->outTime = 0;
        $c->doctorLoginname = 'bs01';
        $c->executeLoginname = 'th01';
        $c->executeDiploma = 'CCHN-123';
        $c->services = [];
        foreach ($over as $k => $v) { $c->$k = $v; }
        return $c;
    }

    private function svc($id, $execute, $tdlIntr = 0, $code = 'DV01')
    {
        $s = new OrderService();
        $s->sereServId = $id;
        $s->serviceCode = $code;
        $s->serviceName = 'Dich vu ' . $code;
        $s->executeTime = $execute;
        $s->tdlIntructionTime = $tdlIntr;
        return $s;
    }

    // ===== B1 DischargeBeforeAdmissionRule =====
    public function test_discharge_before_admission_phat_hien_loi()
    {
        $rule = new DischargeBeforeAdmissionRule();
        $c = $this->ctx(['inTime' => 20260105080000, 'outTime' => 20260103080000]);
        $vios = $rule->check($c);
        $this->assertCount(1, $vios);
        $this->assertSame('treatment', $vios[0]->orderRefType);
        $this->assertSame(100, $vios[0]->orderRefId);
    }

    public function test_discharge_hop_le_khong_loi()
    {
        $rule = new DischargeBeforeAdmissionRule();
        $c = $this->ctx(['inTime' => 20260101080000, 'outTime' => 20260105080000]);
        $this->assertCount(0, $rule->check($c));
    }

    public function test_chua_ra_vien_out_time_0_khong_loi()
    {
        $rule = new DischargeBeforeAdmissionRule();
        $c = $this->ctx(['inTime' => 20260101080000, 'outTime' => 0]);
        $this->assertCount(0, $rule->check($c));
    }

    // ===== B2 OrderTimeOutOfStayRule =====
    public function test_order_time_truoc_gio_vao_vien()
    {
        $rule = new OrderTimeOutOfStayRule();
        $c = $this->ctx(['inTime' => 20260105080000, 'outTime' => 0, 'intructionTime' => 20260104080000]);
        $vios = $rule->check($c);
        $this->assertCount(1, $vios);
        $this->assertSame('before_in', $vios[0]->subKey);
        $this->assertSame('service_req', $vios[0]->orderRefType);
    }

    public function test_order_time_sau_gio_ra_vien()
    {
        $rule = new OrderTimeOutOfStayRule();
        $c = $this->ctx(['inTime' => 20260101080000, 'outTime' => 20260103080000, 'intructionTime' => 20260105080000]);
        $vios = $rule->check($c);
        $this->assertCount(1, $vios);
        $this->assertSame('after_out', $vios[0]->subKey);
    }

    public function test_order_time_trong_dot_khong_loi()
    {
        $rule = new OrderTimeOutOfStayRule();
        $c = $this->ctx(['inTime' => 20260101080000, 'outTime' => 20260110080000, 'intructionTime' => 20260105080000]);
        $this->assertCount(0, $rule->check($c));
    }

    // ===== B3 ExecuteBeforeOrderRule =====
    public function test_execute_truoc_gio_y_lenh_phat_hien_loi()
    {
        $rule = new ExecuteBeforeOrderRule();
        $c = $this->ctx(['intructionTime' => 20260101080000]);
        $c->services = [$this->svc(501, 20260101070000)];
        $vios = $rule->check($c);
        $this->assertCount(1, $vios);
        $this->assertSame('sere_serv', $vios[0]->orderRefType);
        $this->assertSame(501, $vios[0]->orderRefId);
    }

    public function test_execute_dung_chuan_khong_loi()
    {
        $rule = new ExecuteBeforeOrderRule();
        $c = $this->ctx(['intructionTime' => 20260101080000]);
        $c->services = [$this->svc(502, 20260101090000)];
        $this->assertCount(0, $rule->check($c));
    }

    public function test_execute_uu_tien_tdl_intruction_time()
    {
        $rule = new ExecuteBeforeOrderRule();
        $c = $this->ctx(['intructionTime' => 20260101060000]);
        $c->services = [$this->svc(503, 20260101080000, 20260101090000)];
        $this->assertCount(1, $rule->check($c));
    }

    // ===== B4 DoctorPracticeCertRule =====
    public function test_nguoi_thuc_hien_thieu_chung_chi_phat_hien_loi()
    {
        $rule = new DoctorPracticeCertRule();
        $c = $this->ctx(['executeLoginname' => 'th09', 'executeDiploma' => '']);
        $vios = $rule->check($c);
        $this->assertCount(1, $vios);
        $this->assertSame('service_req', $vios[0]->orderRefType);
    }

    public function test_nguoi_thuc_hien_co_chung_chi_khong_loi()
    {
        $rule = new DoctorPracticeCertRule();
        $c = $this->ctx(['executeLoginname' => 'th09', 'executeDiploma' => 'QD-555']);
        $this->assertCount(0, $rule->check($c));
    }

    public function test_chua_co_nguoi_thuc_hien_thi_bo_qua()
    {
        $rule = new DoctorPracticeCertRule();
        $c = $this->ctx(['executeLoginname' => null, 'executeDiploma' => null]);
        $this->assertCount(0, $rule->check($c));
    }
}
