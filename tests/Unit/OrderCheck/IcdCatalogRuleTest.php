<?php

namespace Tests\Unit\OrderCheck;

use DB;
use Tests\TestCase;
use App\Services\OrderCheck\Support\OrderContext;
use App\Services\OrderCheck\Support\CatalogLookup;
use App\Services\OrderCheck\RuleHandlers\Clinical\IcdNotInCatalogRule;
use App\Services\OrderCheck\RuleHandlers\Clinical\IcdYhctNotInCatalogRule;

class IcdCatalogRuleTest extends TestCase
{
    private function ctx($chinh, $phu = null, $yhctChinh = null, $yhctPhu = null)
    {
        $c = new OrderContext();
        $c->serviceReqId = 111;
        $c->serviceReqCode = 'PK001';
        $c->icdCode = $chinh;
        $c->icdSubCode = $phu;
        $c->traditionalIcdCode = $yhctChinh;
        $c->traditionalIcdSubCode = $yhctPhu;

        return $c;
    }

    private function traIcd10(array $ma)
    {
        $lk = new CatalogLookup('icd10_categories', 'icd_code', null, null, null, ['is_active' => 1]);
        $lk->datSanChoTest($ma);

        return $lk;
    }

    private function tra(array $ma)
    {
        return new IcdNotInCatalogRule($this->traIcd10($ma));
    }

    private function traYhct(array $ma)
    {
        $lk = new CatalogLookup('icd_yhct_categories', 'icd_code', null, null, null, ['is_active' => 1]);
        $lk->datSanChoTest($ma);

        return new IcdYhctNotInCatalogRule($lk);
    }

    /** @test */
    public function danh_muc_rong_thi_im_lang()
    {
        // Phep kiem quan trong nhat: danh muc chua nap KHONG duoc bien moi ma thanh vi pham.
        // Dung datRongChoTest chu KHONG dua vao bang that - icd10_categories dang co
        // 12.229 dong nen sanSang() se tra true.
        $lk = new CatalogLookup('icd10_categories', 'icd_code', null, null, null, ['is_active' => 1]);
        $lk->datRongChoTest();

        $r = new IcdNotInCatalogRule($lk);

        $this->assertCount(0, $r->check($this->ctx('ZZZ97')));
    }

    /** @test */
    public function ma_chinh_dung_thi_khong_vi_pham()
    {
        $this->assertCount(0, $this->tra(['ZZZ90'])->check($this->ctx('ZZZ90')));
    }

    /** @test */
    public function ma_chinh_sai_thi_bao_va_ghi_ro_vi_tri()
    {
        $vi = $this->tra(['ZZZ90'])->check($this->ctx('ZZZ97'));

        $this->assertCount(1, $vi);
        $this->assertEquals('A_ICD_NOT_IN_CATALOG', $vi[0]->ruleCode);
        $this->assertEquals('service_req', $vi[0]->orderRefType);
        $this->assertEquals(111, $vi[0]->orderRefId);
        $this->assertContains('ZZZ97', $vi[0]->message);
        $this->assertContains('chẩn đoán chính', $vi[0]->message);
    }

    /** @test */
    public function chuoi_chan_doan_phu_co_dau_cham_phay_dan_dau_khong_gay_bao_oan()
    {
        // Ca chan loi nghiem trong nhat cua ca dot.
        $r = $this->tra(['ZZZ90']);

        $this->assertCount(0, $r->check($this->ctx(null, ';ZZZ90')));
        $this->assertCount(0, $r->check($this->ctx('ZZZ90', ';ZZZ90')));
        $this->assertCount(0, $r->check($this->ctx(null, ';;;')));
    }

    /** @test */
    public function ma_phu_sai_thi_bao_va_ghi_ro_vi_tri()
    {
        $vi = $this->tra(['ZZZ90'])->check($this->ctx('ZZZ90', ';ZZZ90;ZZZ98'));

        $this->assertCount(1, $vi);
        $this->assertContains('ZZZ98', $vi[0]->message);
        $this->assertContains('chẩn đoán phụ', $vi[0]->message);
    }

    /** @test */
    public function ma_sai_o_ca_hai_cho_chi_sinh_mot_vi_pham()
    {
        $vi = $this->tra(['ZZZ90'])->check($this->ctx('ZZZ97', ';ZZZ97'));

        $this->assertCount(1, $vi);
        $this->assertContains('chẩn đoán chính và phụ', $vi[0]->message);
    }

    /** @test */
    public function nhieu_ma_sai_cho_nhieu_vi_pham_khong_bi_gop()
    {
        $vi = $this->tra(['ZZZ90'])->check($this->ctx('ZZZ97', ';ZZZ96'));

        $this->assertCount(2, $vi);
        $this->assertNotEquals($vi[0]->dedupKey(), $vi[1]->dedupKey());
    }

    /** @test */
    public function phieu_khong_co_ma_nao_thi_khong_vi_pham()
    {
        $r = $this->tra(['ZZZ90']);

        $this->assertCount(0, $r->check($this->ctx(null, null)));
        $this->assertCount(0, $r->check($this->ctx('', '')));
    }

    /** @test */
    public function phieu_chi_co_chan_doan_phu_van_duoc_xet()
    {
        $this->assertCount(1, $this->tra(['ZZZ90'])->check($this->ctx(null, ';ZZZ97')));
    }

    /** @test */
    public function ma_dinh_khoang_trang_tra_dung_va_thong_diep_sach()
    {
        $r = $this->tra(['ZZZ90']);

        $this->assertCount(0, $r->check($this->ctx('  ZZZ90  ')));

        $vi = $r->check($this->ctx('  ZZZ97  '));
        $this->assertCount(1, $vi);
        $this->assertNotContains('  ZZZ97', $vi[0]->message);
        $this->assertSame('ZZZ97', $vi[0]->detail['ma_benh']);
    }

    /** @test */
    public function luat_yhct_tra_bang_rieng_khong_bac_cau_sang_icd10()
    {
        // Ma nam trong ICD10 nhung khong nam trong YHCT van la vi pham cua luat YHCT.
        $vi = $this->traYhct(['ZZY90'])->check($this->ctx('ZZZ90', ';ZZZ90', 'ZZZ90'));

        $this->assertCount(1, $vi);
        $this->assertEquals('A_ICD_YHCT_NOT_IN_CATALOG', $vi[0]->ruleCode);
        $this->assertContains('ICD YHCT', $vi[0]->message);
    }

    /** @test */
    public function luat_yhct_chi_doc_truong_yhct()
    {
        $r = $this->traYhct(['ZZY90']);

        // Ma ICD10 sai nam o truong thuong -> luat YHCT khong quan tam.
        $this->assertCount(0, $r->check($this->ctx('ZZZ97', ';ZZZ95')));

        // Ma YHCT sai o truong YHCT phu -> co bao.
        $this->assertCount(1, $r->check($this->ctx('ZZZ97', ';ZZZ95', 'ZZY90', ';ZZY99')));
    }

    /** @test */
    public function hai_luat_doc_lap_nhau()
    {
        $c = $this->ctx('ZZZ97', null, 'ZZY99');

        $vi10 = $this->tra(['ZZZ90'])->check($c);
        $viYhct = $this->traYhct(['ZZY90'])->check($c);

        $this->assertCount(1, $vi10);
        $this->assertCount(1, $viYhct);
        $this->assertNotEquals($vi10[0]->dedupKey(), $viYhct[0]->dedupKey());
    }

    /** @test */
    public function dong_is_active_0_khong_duoc_coi_la_co_trong_danh_muc()
    {
        DB::table('icd10_categories')->insert([
            ['icd_code' => 'ZZ9', 'icd_name' => 'Tat', 'is_active' => 0],
            ['icd_code' => 'ZZ8', 'icd_name' => 'Bat', 'is_active' => 1],
        ]);

        try {
            $lk = new CatalogLookup('icd10_categories', 'icd_code', null, null, null, ['is_active' => 1]);
            $r = new IcdNotInCatalogRule($lk);

            $this->assertCount(0, $r->check($this->ctx('ZZ8')));
            $this->assertCount(1, $r->check($this->ctx('ZZ9')));
        } finally {
            DB::table('icd10_categories')->whereIn('icd_code', ['ZZ8', 'ZZ9'])->delete();
        }
    }
}
