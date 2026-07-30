<?php

namespace Tests\Unit;

use App\Services\OrderCheck\RuleHandlers\Structural\DoctorPracticeCertRule;
use App\Services\OrderCheck\Support\OrderContext;
use Tests\TestCase;

class DoctorPracticeCertRuleTest extends TestCase
{
    /** Ngu canh toi thieu de quy tac chay: co nguoi thuc hien, khong co CCHN */
    protected function ctx($loginname, $diploma = '')
    {
        $c = new OrderContext();
        $c->serviceReqId = 1;
        $c->serviceReqTypeId = 2;          // Xet nghiem - khong nam trong danh sach loai tru
        $c->executeLoginname = $loginname;
        $c->executeDiploma = $diploma;

        return $c;
    }

    /** @test */
    public function tai_khoan_duoc_mien_thi_khong_bao_vi_pham()
    {
        $rule = new DoctorPracticeCertRule([], ['mitalab', 'vietrad', 'sys']);

        $this->assertSame([], $rule->check($this->ctx('mitalab')));
        $this->assertSame([], $rule->check($this->ctx('vietrad')));
        $this->assertSame([], $rule->check($this->ctx('sys')));
    }

    /** @test */
    public function tai_khoan_duoc_mien_viet_hoa_van_duoc_mien()
    {
        $rule = new DoctorPracticeCertRule([], ['mitalab']);

        $this->assertSame([], $rule->check($this->ctx('MitaLab')));
    }

    /**
     * Nguoi THAT thieu CCHN van phai bi bao - do la phat hien dung cua quy tac.
     */
    /** @test */
    public function nguoi_khong_duoc_mien_thi_van_bao_vi_pham()
    {
        $rule = new DoctorPracticeCertRule([], ['mitalab', 'vietrad', 'sys']);

        $vi = $rule->check($this->ctx('ntdh3'));

        $this->assertCount(1, $vi);
        $this->assertSame('B_DOCTOR_NO_PRACTICE_CERT', $vi[0]->ruleCode);
    }

    /** @test */
    public function tai_khoan_duoc_mien_nhung_co_CCHN_thi_van_khong_bao()
    {
        // Khong doi hanh vi cu: co CCHN thi khong bao, du co nam trong danh sach mien.
        $rule = new DoctorPracticeCertRule([], ['mitalab']);

        $this->assertSame([], $rule->check($this->ctx('mitalab', 'CCHN-123')));
    }

    /** @test */
    public function danh_sach_mien_rong_thi_hanh_vi_y_het_truoc_day()
    {
        $rule = new DoctorPracticeCertRule([], []);

        $vi = $rule->check($this->ctx('mitalab'));

        $this->assertCount(1, $vi, 'Danh sach rong thi khong duoc mien ai');
    }

    /** @test */
    public function khong_co_nguoi_thuc_hien_thi_khong_bao()
    {
        $rule = new DoctorPracticeCertRule([], ['mitalab']);

        $this->assertSame([], $rule->check($this->ctx('')));
    }
}
