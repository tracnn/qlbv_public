<?php

namespace Tests\Unit\OrderCheck;

use Tests\TestCase;
use App\Services\OrderCheck\Support\OrderContext;
use App\Services\OrderCheck\Support\OrderService;
use App\Services\OrderCheck\Support\CatalogLookup;
use App\Services\OrderCheck\RuleHandlers\Bhyt\BhytCodeMissingRule;
use App\Services\OrderCheck\RuleHandlers\Bhyt\BhytServiceCatalogRule;
use App\Services\OrderCheck\RuleHandlers\Bhyt\BhytDrugCatalogRule;

class BhytRuleTest extends TestCase
{
    protected function setUp()
    {
        parent::setUp();
        config(['order_check.bhyt_patient_type_ids' => '1']);
    }

    private function ctx(array $dv)
    {
        $c = new OrderContext();
        $c->serviceReqId = 111;
        $c->serviceReqCode = 'PK001';
        $c->services = $dv;

        return $c;
    }

    /**
     * @param int $loai loai dich vu; mac dinh 2 (Xet nghiem) de roi vao pham vi DVKT
     * @param int $moc moc chi dinh dang YmdHis
     */
    private function dv($id, $ma, $patientTypeId, $maBhyt = null, $loai = 2, $moc = 20240601080000)
    {
        $s = new OrderService();
        $s->sereServId = $id;
        $s->serviceCode = $ma;
        $s->serviceName = 'DV ' . $ma;
        $s->patientTypeId = $patientTypeId;
        $s->bhytCode = $maBhyt;
        $s->serviceTypeId = $loai;
        $s->tdlIntructionTime = $moc;

        return $s;
    }

    /** @test */
    public function thieu_ma_bhyt_chi_bao_tren_dong_bhyt()
    {
        $r = new BhytCodeMissingRule();

        $vi = $r->check($this->ctx([
            $this->dv(1, 'DV1', 1,  null),   // BHYT, thieu ma -> vi pham
            $this->dv(2, 'DV2', 42, null),   // Vien phi, thieu ma -> BO QUA
            $this->dv(3, 'DV3', 1,  'BH3'),  // BHYT, co ma -> khong sao
        ]));

        $this->assertCount(1, $vi);
        $this->assertEquals('A_BHYT_CODE_MISSING', $vi[0]->ruleCode);
        $this->assertEquals(1, $vi[0]->orderRefId);
    }

    /** @test */
    public function moi_dong_vi_pham_co_subkey_rieng_de_khong_bi_gop()
    {
        $r = new BhytCodeMissingRule();

        $vi = $r->check($this->ctx([
            $this->dv(1, 'DV1', 1, null),
            $this->dv(2, 'DV2', 1, null),
        ]));

        $this->assertCount(2, $vi);
        $this->assertNotEquals($vi[0]->dedupKey(), $vi[1]->dedupKey());
    }

    /** @test */
    public function danh_muc_rong_thi_quy_tac_im_lang()
    {
        // Phep kiem quan trong nhat: danh muc chua nhap KHONG duoc bien moi dich vu
        // thanh vi pham.
        $lk = new CatalogLookup('service_catalogs', 'ma_dich_vu');   // bang dang rong
        $r = new BhytServiceCatalogRule($lk);

        $vi = $r->check($this->ctx([$this->dv(1, 'DV1', 1, 'BH1')]));

        $this->assertCount(0, $vi, 'Danh muc rong ma van bao vi pham');
    }

    /** @test */
    public function chi_bao_dong_co_ma_khong_khop_danh_muc()
    {
        $lk = new CatalogLookup('service_catalogs', 'ma_dich_vu');
        $lk->datSanChoTest(['BH1']);

        $r = new BhytServiceCatalogRule($lk);

        $vi = $r->check($this->ctx([
            $this->dv(1, 'DV1', 1,  'BH1'),   // khop -> khong sao
            $this->dv(2, 'DV2', 1,  'BH9'),   // khong khop -> vi pham
            $this->dv(3, 'DV3', 42, 'BH9'),   // Vien phi -> BO QUA
            $this->dv(4, 'DV4', 1,  null),    // thieu ma -> de quy tac kia lo
        ]));

        $this->assertCount(1, $vi);
        $this->assertEquals(2, $vi[0]->orderRefId);
    }

    /** @test */
    public function quy_tac_thuoc_bo_qua_dong_khong_phai_thuoc()
    {
        // Khong loc theo loai thi quy tac thuoc doi chieu ca xet nghiem voi
        // medicine_catalogs: 53.288 dong bat oan moi tuan tren so lieu that.
        $lk = new CatalogLookup('medicine_catalogs', 'ma_thuoc', 'ten_thuoc');
        $lk->datSanChoTest([]);

        $r = new BhytDrugCatalogRule($lk);

        $vi = $r->check($this->ctx([
            $this->dv(1, 'XN1', 1, 'BH1', 2),   // Xet nghiem -> BO QUA
            $this->dv(2, 'VT1', 1, 'BH2', 7),   // Vat tu     -> BO QUA
            $this->dv(3, 'TH1', 1, 'BH3', 6),   // Thuoc      -> vi pham
        ]));

        $this->assertCount(1, $vi);
        $this->assertEquals(3, $vi[0]->orderRefId);
        $this->assertEquals('A_BHYT_DRUG_NOT_IN_CATALOG', $vi[0]->ruleCode);
    }

    /** @test */
    public function quy_tac_dich_vu_lay_phan_bu_tru_thuoc_va_vat_tu()
    {
        $lk = new CatalogLookup('service_catalogs', 'ma_dich_vu', 'ten_dich_vu');
        $lk->datSanChoTest([]);

        $r = new BhytServiceCatalogRule($lk);

        $vi = $r->check($this->ctx([
            $this->dv(1, 'TH1', 1, 'BH1', 6),    // Thuoc  -> BO QUA
            $this->dv(2, 'VT1', 1, 'BH2', 7),    // Vat tu -> BO QUA
            $this->dv(3, 'XN1', 1, 'BH3', 2),    // Xet nghiem -> vi pham
            $this->dv(4, 'ZZ1', 1, 'BH4', 99),   // loai la  -> van xet (phan bu)
        ]));

        $this->assertCount(2, $vi);
        $this->assertEquals([3, 4], [$vi[0]->orderRefId, $vi[1]->orderRefId]);
    }

    /** @test */
    public function ma_het_hieu_luc_truoc_ngay_chi_dinh_bi_bat()
    {
        $lk = new CatalogLookup('medicine_catalogs', 'ma_thuoc', 'ten_thuoc');
        $lk->datSanChoTest([], [
            'BH1' => [['ten' => 'Thuoc A', 'tu' => '20230101', 'den' => '20231231']],
        ]);

        $r = new BhytDrugCatalogRule($lk);

        $this->assertCount(0, $r->check($this->ctx([
            $this->dv(1, 'TH1', 1, 'BH1', 6, 20230601080000),
        ])), 'Y lenh trong thoi han hieu luc ma van bao vi pham');

        $this->assertCount(1, $r->check($this->ctx([
            $this->dv(1, 'TH1', 1, 'BH1', 6, 20240601080000),
        ])), 'Y lenh sau khi ma het hieu luc ma khong bao vi pham');
    }

    /** @test */
    public function dong_khong_co_moc_chi_dinh_thi_bo_qua()
    {
        // execute_time rong 100% tren his_sere_serv nen tdl_intruction_time la moc duy
        // nhat; khong co no thi khong biet doi chieu voi danh muc cua thoi diem nao.
        $lk = new CatalogLookup('medicine_catalogs', 'ma_thuoc', 'ten_thuoc');
        $lk->datSanChoTest([]);

        $r = new BhytDrugCatalogRule($lk);
        $vi = $r->check($this->ctx([$this->dv(1, 'TH1', 1, 'BH1', 6, 0)]));

        $this->assertCount(0, $vi);
    }

    private function traTheoCoSo(array $dong)
    {
        $lk = new CatalogLookup('medicine_catalogs', 'ma_thuoc', 'ten_thuoc',
            'tu_ngay', 'den_ngay', [], 'ma_cskcb');
        $lk->datSanChoTest([], $dong);

        return new BhytDrugCatalogRule($lk);
    }

    private function ctxCoSo($maCskcb)
    {
        $c = $this->ctx([$this->dv(1, 'TH1', 1, 'BH1', 6)]);
        $c->maCskcb = $maCskcb;

        return $c;
    }

    /** @test */
    public function chi_tra_danh_muc_cua_co_so_cua_ho_so()
    {
        $r = $this->traTheoCoSo(['BH1' => [['ten' => 'Thuoc A', 'tu' => '', 'den' => '', 'cs' => '01929']]]);

        $this->assertCount(0, $r->check($this->ctxCoSo('01929')),
            'Ma cua chinh co so minh ma van bao vi pham');
        $this->assertCount(1, $r->check($this->ctxCoSo('37470')),
            'Ma cua co so khac ma khong bao vi pham');
    }

    /** @test */
    public function dong_danh_muc_dung_chung_khop_moi_co_so()
    {
        // Dieu kien de trien khai khong lam tat cac kiem tra dang chay.
        $r = $this->traTheoCoSo(['BH1' => [['ten' => 'Thuoc A', 'tu' => '', 'den' => '', 'cs' => '']]]);

        $this->assertCount(0, $r->check($this->ctxCoSo('01929')));
        $this->assertCount(0, $r->check($this->ctxCoSo('37470')));
    }

    /** @test */
    public function phieu_khong_co_ma_co_so_thi_khong_loc()
    {
        $r = $this->traTheoCoSo(['BH1' => [['ten' => 'Thuoc A', 'tu' => '', 'den' => '', 'cs' => '01929']]]);

        $this->assertCount(0, $r->check($this->ctxCoSo(null)));
    }

    /** @test */
    public function phieu_khong_co_dong_bhyt_nao_thi_khong_bao_gi()
    {
        $r = new BhytCodeMissingRule();

        $vi = $r->check($this->ctx([
            $this->dv(1, 'DV1', 42, null),
            $this->dv(2, 'DV2', 43, null),
        ]));

        $this->assertCount(0, $vi);
    }
}
