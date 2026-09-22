<?php

namespace Tests\Unit\OrderCheck;

use Tests\TestCase;
use App\Services\OrderCheck\Support\OrderContext;
use App\Services\OrderCheck\Support\OrderService;
use App\Services\OrderCheck\Support\CatalogLookup;
use App\Services\OrderCheck\RuleHandlers\Bhyt\BhytDrugNameRule;
use App\Services\OrderCheck\RuleHandlers\Bhyt\BhytServiceNameRule;

class BhytNameRuleTest extends TestCase
{
    protected function setUp()
    {
        parent::setUp();
        config(['order_check.bhyt_patient_type_ids' => '1']);
    }

    private function dv($id, $maBhyt, $loai, $ten, $moc = 20240601080000)
    {
        $s = new OrderService();
        $s->sereServId = $id;
        $s->serviceCode = 'SV' . $id;
        $s->serviceName = 'DV ' . $id;
        $s->patientTypeId = 1;
        $s->bhytCode = $maBhyt;
        $s->bhytName = $ten;
        // Dong thuoc: HIS khai ten hoat chat (active_ingr_bhyt_name) - quy tac ten thuoc
        // doc truong nay. DVKT/VTYT khong dung toi.
        $s->activeIngrName = $ten;
        $s->serviceTypeId = $loai;
        $s->tdlIntructionTime = $moc;

        return $s;
    }

    private function ctx(array $dv)
    {
        $c = new OrderContext();
        $c->serviceReqId = 111;
        $c->serviceReqCode = 'PK001';
        $c->services = $dv;

        return $c;
    }

    private function traThuoc(array $dong)
    {
        $lk = new CatalogLookup('medicine_catalogs', 'ma_thuoc', 'ten_hoat_chat');
        $lk->datSanChoTest([], $dong);

        return new BhytDrugNameRule($lk);
    }

    /** @test */
    public function ten_khop_thi_khong_vi_pham()
    {
        $r = $this->traThuoc(['BH1' => [['ten' => 'Thuoc A', 'tu' => '', 'den' => '']]]);

        $this->assertCount(0, $r->check($this->ctx([$this->dv(1, 'BH1', 6, 'Thuoc A')])));
    }

    /** @test */
    public function ten_lech_thi_bao_vi_pham_va_neu_ca_hai_ten()
    {
        $r = $this->traThuoc(['BH1' => [['ten' => 'Thuoc A', 'tu' => '', 'den' => '']]]);

        $vi = $r->check($this->ctx([$this->dv(1, 'BH1', 6, 'Thuoc B')]));

        $this->assertCount(1, $vi);
        $this->assertEquals('A_BHYT_DRUG_NAME_MISMATCH', $vi[0]->ruleCode);
        $this->assertEquals(1, $vi[0]->orderRefId);
        $this->assertContains('Thuoc B', $vi[0]->message);
        $this->assertContains('Thuoc A', $vi[0]->message);
    }

    /** @test */
    public function khop_bat_ky_ten_nao_cua_ma_deu_dat()
    {
        // Mot ma BHYT duoc nhieu dich vu HIS dung chung voi ten khac nhau: 593 ma tren
        // HIS that, ca biet mot ma 226 ten. So voi "dong duy nhat" nhu XML3176 se bao sai
        // hang loat o nhom nay.
        $r = $this->traThuoc(['BH1' => [
            ['ten' => 'Wosulin 30/70', 'tu' => '', 'den' => ''],
            ['ten' => 'INSUNOVA - 30/70', 'tu' => '', 'den' => ''],
        ]]);

        $this->assertCount(0, $r->check($this->ctx([$this->dv(1, 'BH1', 6, 'INSUNOVA - 30/70')])));
    }

    /** @test */
    public function lech_hoa_thuong_khong_bao_vi_pham()
    {
        // So da chuan hoa, thong nhat voi INVALID_DRUG_NAME ben Xml3176Xml2Checker.
        // Truoc 2026-09-21 hai ben cung so TUYET DOI; nguoi dung chot bo phan biet hoa thuong.
        $r = $this->traThuoc(['BH1' => [['ten' => 'Thuoc A', 'tu' => '', 'den' => '']]]);

        $this->assertCount(0, $r->check($this->ctx([$this->dv(1, 'BH1', 6, 'THUOC A')])));
    }

    /** @test */
    public function lech_khoang_trang_dau_duoi_thi_bo_qua()
    {
        // trim la rang buoc ky thuat cua cot varchar Oracle, khong phai noi long.
        $r = $this->traThuoc(['BH1' => [['ten' => 'Thuoc A', 'tu' => '', 'den' => '']]]);

        $this->assertCount(0, $r->check($this->ctx([$this->dv(1, 'BH1', 6, '  Thuoc A  ')])));
    }

    /** @test */
    public function lech_khoang_trang_giua_chu_khong_bao()
    {
        // TextNormalizer gop khoang trang - nguoi dung chot dung lai bo chuan hoa co san.
        $r = $this->traThuoc(['BH1' => [['ten' => 'Thuoc A', 'tu' => '', 'den' => '']]]);

        $this->assertCount(0, $r->check($this->ctx([$this->dv(1, 'BH1', 6, 'Thuoc  A')])));
    }

    /** @test */
    public function chu_viet_co_dau_khac_hoa_thuong_khong_bao()
    {
        $r = $this->traThuoc(['BH1' => [['ten' => 'Đường huyết mao mạch', 'tu' => '', 'den' => '']]]);

        $this->assertCount(0, $r->check($this->ctx([$this->dv(1, 'BH1', 6, 'ĐƯỜNG HUYẾT MAO MẠCH')])));
    }

    /** @test */
    public function ma_khong_co_trong_danh_muc_thi_quy_tac_ten_im_lang()
    {
        // Quy tac MA da bao roi - khong chong hai vi pham len cung mot dong.
        $r = $this->traThuoc(['BH9' => [['ten' => 'X', 'tu' => '', 'den' => '']]]);

        $this->assertCount(0, $r->check($this->ctx([$this->dv(1, 'BH1', 6, 'Thuoc B')])));
    }

    /** @test */
    public function ten_khai_rong_thi_im_lang()
    {
        // Do duoc 0 dich vu co ma ma thieu ten BHYT, nen khong lam quy tac "thieu ten".
        $r = $this->traThuoc(['BH1' => [['ten' => 'Thuoc A', 'tu' => '', 'den' => '']]]);

        $this->assertCount(0, $r->check($this->ctx([$this->dv(1, 'BH1', 6, '')])));
        $this->assertCount(0, $r->check($this->ctx([$this->dv(1, 'BH1', 6, null)])));
    }

    /** @test */
    public function ten_khop_dong_da_het_hieu_luc_van_bao_vi_pham()
    {
        $r = $this->traThuoc(['BH1' => [
            ['ten' => 'Ten cu', 'tu' => '20230101', 'den' => '20231231'],
            ['ten' => 'Ten moi', 'tu' => '20240101', 'den' => ''],
        ]]);

        $vi = $r->check($this->ctx([$this->dv(1, 'BH1', 6, 'Ten cu', 20240601080000)]));

        $this->assertCount(1, $vi);
        $this->assertContains('Ten moi', $vi[0]->message);

        // Cung ten do, y lenh cua nam 2023 thi lai dat.
        $this->assertCount(0, $r->check($this->ctx([$this->dv(1, 'BH1', 6, 'Ten cu', 20230601080000)])));
    }

    /** @test */
    public function chi_neu_toi_da_ba_ten_danh_muc()
    {
        // Co ma mang toi 226 ten, do het vao cot mo ta se tran.
        $r = $this->traThuoc(['BH1' => [
            ['ten' => 'T1', 'tu' => '', 'den' => ''],
            ['ten' => 'T2', 'tu' => '', 'den' => ''],
            ['ten' => 'T3', 'tu' => '', 'den' => ''],
            ['ten' => 'T4', 'tu' => '', 'den' => ''],
        ]]);

        $vi = $r->check($this->ctx([$this->dv(1, 'BH1', 6, 'X')]));

        $this->assertCount(1, $vi);
        $this->assertContains('…', $vi[0]->message);
        $this->assertNotContains('T4', $vi[0]->message);
    }

    /** @test */
    public function quy_tac_ten_thuoc_bo_qua_dong_khong_phai_thuoc()
    {
        $r = $this->traThuoc(['BH1' => [['ten' => 'Ten dung', 'tu' => '', 'den' => '']]]);

        $this->assertCount(0, $r->check($this->ctx([
            $this->dv(1, 'BH1', 2, 'Sai ten'),
            $this->dv(2, 'BH1', 7, 'Sai ten'),
        ])));
    }

    /** @test */
    public function quy_tac_ten_dich_vu_bo_qua_thuoc_va_vat_tu()
    {
        $lk = new CatalogLookup('service_catalogs', 'ma_dich_vu', 'ten_dich_vu');
        $lk->datSanChoTest([], ['BH1' => [['ten' => 'Ten dung', 'tu' => '', 'den' => '']]]);

        $r = new BhytServiceNameRule($lk);

        $vi = $r->check($this->ctx([
            $this->dv(1, 'BH1', 6, 'Sai ten'),
            $this->dv(2, 'BH1', 7, 'Sai ten'),
            $this->dv(3, 'BH1', 2, 'Sai ten'),
        ]));

        $this->assertCount(1, $vi);
        $this->assertEquals(3, $vi[0]->orderRefId);
    }

    /** @test */
    public function dong_khong_phai_bhyt_thi_bo_qua()
    {
        $r = $this->traThuoc(['BH1' => [['ten' => 'Ten dung', 'tu' => '', 'den' => '']]]);

        $c = $this->ctx([$this->dv(1, 'BH1', 6, 'Sai ten')]);
        $c->services[0]->patientTypeId = 42;

        $this->assertCount(0, $r->check($c));
    }

    /** @test */
    public function danh_muc_rong_thi_quy_tac_im_lang()
    {
        // Phep kiem quan trong nhat: don vi chua nhap danh muc KHONG duoc thay moi dich vu
        // thanh vi pham.
        $lk = new CatalogLookup('medicine_catalogs', 'ma_thuoc', 'ten_thuoc');
        $r = new BhytDrugNameRule($lk);

        $this->assertCount(0, $r->check($this->ctx([$this->dv(1, 'BH1', 6, 'X')])));
    }

    /** @test */
    public function ma_dinh_ky_tu_thua_van_tra_dung_va_thong_diep_sach()
    {
        // Du lieu HIS that co ma dinh ky tu tab, vi du "\t40.1021".
        $r = $this->traThuoc(['40.1021' => [['ten' => 'Natri clorid 0,9%', 'tu' => '', 'den' => '']]]);

        $this->assertCount(0, $r->check($this->ctx([
            $this->dv(1, "\t40.1021", 6, 'Natri clorid 0,9%'),
        ])), 'Ma dinh tab ma khong tra duoc trong danh muc');

        $vi = $r->check($this->ctx([$this->dv(1, "\t40.1021", 6, 'Sai ten')]));

        $this->assertCount(1, $vi);
        $this->assertNotContains("\t", $vi[0]->message, 'Thong diep vi pham con ky tu tab');
        $this->assertSame('40.1021', $vi[0]->detail['bhyt_code']);
    }

    /**
     * Loi goc: HIS khai TEN HOAT CHAT (active_ingr_bhyt_name) nhung quy tac tung so voi
     * cot ten_thuoc (ten thuong mai) cua danh muc - ma 40.220 bi bao "Clarithromycin" lech
     * "Klacid MR". Ca nay di duong THAT: quy tac tu dung CatalogLookup mac dinh va nap tu
     * CSDL, nen no chung minh cot duoc doc la ten_hoat_chat.
     *
     * @test
     */
    public function doi_chieu_voi_ten_hoat_chat_cua_danh_muc_chu_khong_phai_ten_thuoc()
    {
        \DB::table('medicine_catalogs')->insert([
            'ma_thuoc' => 'ZZHC1', 'ten_hoat_chat' => 'Clarithromycin', 'ten_thuoc' => 'Klacid MR',
            'don_vi_tinh' => 'Vien', 'ham_luong' => '500mg', 'duong_dung' => 'Uong',
            'ma_duong_dung' => '1', 'dang_bao_che' => 'Vien', 'so_dang_ky' => 'SDK',
            'ma_cskcb' => null,
        ]);

        try {
            $r = new BhytDrugNameRule();

            $this->assertCount(0, $r->check($this->ctx([$this->dv(1, 'ZZHC1', 6, 'Clarithromycin')])),
                'Ten hoat chat khop danh muc ma van bi bao lech');

            $vi = $r->check($this->ctx([$this->dv(2, 'ZZHC1', 6, 'Klacid MR')]));

            $this->assertCount(1, $vi, 'Khai ten thuong mai thay cho ten hoat chat phai bi bao');
            $this->assertContains('Clarithromycin', $vi[0]->message);
        } finally {
            \DB::table('medicine_catalogs')->where('ma_thuoc', 'ZZHC1')->delete();
        }
    }

    /**
     * 38/8.544 loai thuoc tren HIS co ma hoat chat nhung de trong TEN hoat chat. Khi do
     * bhytName roi ve ten dich vu (thuong la ten thuong mai) - dem no so voi ten hoat
     * chat se bao lech oan. Nguoi dung chot 2026-09-22: im lang.
     *
     * @test
     */
    public function thieu_ten_hoat_chat_thi_im_lang_du_co_ten_thay_the()
    {
        $r = $this->traThuoc(['BH1' => [['ten' => 'Clarithromycin', 'tu' => '', 'den' => '']]]);

        $s = $this->dv(1, 'BH1', 6, 'Klacid MR');
        $s->activeIngrName = '';

        $this->assertCount(0, $r->check($this->ctx([$s])));
    }

    /** @test */
    public function thong_diep_ghi_ten_hoat_chat()
    {
        $r = $this->traThuoc(['BH1' => [['ten' => 'Clarithromycin', 'tu' => '', 'den' => '']]]);

        $vi = $r->check($this->ctx([$this->dv(1, 'BH1', 6, 'Azithromycin')]));

        $this->assertCount(1, $vi);
        $this->assertContains('Tên hoạt chất', $vi[0]->message);
    }

    /**
     * Quy tac ten DVKT van doc bhytName (ten dich vu BHYT), KHONG doc ten hoat chat.
     *
     * @test
     */
    public function quy_tac_ten_dich_vu_van_doc_ten_bhyt_cua_dong()
    {
        $lk = new CatalogLookup('service_catalogs', 'ma_dich_vu', 'ten_dich_vu');
        $lk->datSanChoTest([], ['BH1' => [['ten' => 'Ten dung', 'tu' => '', 'den' => '']]]);

        $s = $this->dv(1, 'BH1', 2, 'Sai ten');
        $s->activeIngrName = '';

        $this->assertCount(1, (new BhytServiceNameRule($lk))->check($this->ctx([$s])));
    }

    /** @test */
    public function moi_dong_vi_pham_co_subkey_rieng_de_khong_bi_gop()
    {
        $r = $this->traThuoc(['BH1' => [['ten' => 'Ten dung', 'tu' => '', 'den' => '']]]);

        $vi = $r->check($this->ctx([
            $this->dv(1, 'BH1', 6, 'Sai 1'),
            $this->dv(2, 'BH1', 6, 'Sai 2'),
        ]));

        $this->assertCount(2, $vi);
        $this->assertNotEquals($vi[0]->dedupKey(), $vi[1]->dedupKey());
    }
}
