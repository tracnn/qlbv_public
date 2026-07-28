<?php

namespace Tests\Unit\OrderCheck;

use Tests\TestCase;
use App\Services\OrderCheck\Support\OrderContext;
use App\Services\OrderCheck\Support\CatalogLookup;
use App\Services\OrderCheck\RuleHandlers\Clinical\StaffCertNotInCatalogRule;

class StaffCertRuleTest extends TestCase
{
    private function ctx($cchnBacSi, $cchnNguoiTh, $moc = 20240601080000, $loaiPhieu = null)
    {
        $c = new OrderContext();
        $c->serviceReqId = 222;
        $c->serviceReqCode = 'PK002';
        $c->serviceReqTypeId = $loaiPhieu;
        $c->requestDiploma = $cchnBacSi;
        $c->executeDiploma = $cchnNguoiTh;
        $c->intructionTime = $moc;

        return $c;
    }

    /**
     * @param array $macchn ma => [ ['tu'=>, 'den'=>], ... ]
     * @param array $maBhxh nhu tren
     */
    private function tra(array $macchn, array $maBhxh = [], array $loaiTru = [])
    {
        $lkCchn = new CatalogLookup('medical_staffs', 'macchn');
        $lkCchn->datSanChoTest([], $macchn);

        $lkBhxh = new CatalogLookup('medical_staffs', 'ma_bhxh');
        $lkBhxh->datSanChoTest([], $maBhxh);

        return new StaffCertNotInCatalogRule($lkCchn, $lkBhxh, $loaiTru);
    }

    /** @test */
    public function danh_muc_rong_thi_im_lang()
    {
        // medical_staffs dang 0 dong. XML3176 thieu la chan nay va dang sinh 31.492 vi
        // pham gia - 100% so dong XML3.
        //
        // Van dung datRongChoTest chu khong dua vao bang dang rong: den ngay don vi nap
        // danh muc thi test nay se vo neu phu thuoc noi dung bang.
        $lkCchn = new CatalogLookup('medical_staffs', 'macchn');
        $lkCchn->datRongChoTest();
        $lkBhxh = new CatalogLookup('medical_staffs', 'ma_bhxh');
        $lkBhxh->datRongChoTest();

        $r = new StaffCertNotInCatalogRule($lkCchn, $lkBhxh);

        $this->assertCount(0, $r->check($this->ctx('X1', 'X2')));
    }

    /** @test */
    public function khop_macchn_thi_khong_vi_pham()
    {
        $r = $this->tra(['C1' => [['tu' => '', 'den' => '']]]);

        $this->assertCount(0, $r->check($this->ctx('C1', 'C1')));
    }

    /** @test */
    public function khop_ma_bhxh_thi_khong_vi_pham()
    {
        $r = $this->tra(['C1' => [['tu' => '', 'den' => '']]], ['B1' => [['tu' => '', 'den' => '']]]);

        $this->assertCount(0, $r->check($this->ctx('B1', 'B1')));
    }

    /** @test */
    public function khong_khop_cot_nao_thi_vi_pham()
    {
        $r = $this->tra(['C1' => [['tu' => '', 'den' => '']]]);
        $vi = $r->check($this->ctx('X9', null));

        $this->assertCount(1, $vi);
        $this->assertEquals('A_STAFF_CERT_NOT_IN_CATALOG', $vi[0]->ruleCode);
        $this->assertEquals('service_req', $vi[0]->orderRefType);
        $this->assertEquals(222, $vi[0]->orderRefId);
        $this->assertContains('X9', $vi[0]->message);
        $this->assertContains('chỉ định', $vi[0]->message);
    }

    /** @test */
    public function het_hieu_luc_tai_ngay_chi_dinh_thi_vi_pham()
    {
        $r = $this->tra(['C1' => [['tu' => '20230101', 'den' => '20231231']]]);

        $this->assertCount(0, $r->check($this->ctx('C1', null, 20230601080000)));
        $this->assertCount(1, $r->check($this->ctx('C1', null, 20240601080000)));
    }

    /** @test */
    public function cchn_rong_thi_im_lang()
    {
        // Thieu CCHN da la viec cua B_DOCTOR_NO_PRACTICE_CERT.
        $r = $this->tra(['C1' => [['tu' => '', 'den' => '']]]);

        $this->assertCount(0, $r->check($this->ctx(null, null)));
        $this->assertCount(0, $r->check($this->ctx('', '   ')));
    }

    /** @test */
    public function ca_hai_vai_tro_deu_sai_cho_hai_vi_pham()
    {
        $r = $this->tra(['C1' => [['tu' => '', 'den' => '']]]);
        $vi = $r->check($this->ctx('X1', 'X2'));

        $this->assertCount(2, $vi);
        $this->assertNotEquals($vi[0]->dedupKey(), $vi[1]->dedupKey());
    }

    /** @test */
    public function hai_vai_tro_cung_mot_cchn_sai_van_cho_hai_vi_pham()
    {
        // Hai vai tro khac nhau, nguoi sua phai xu ly ca hai cho.
        $r = $this->tra(['C1' => [['tu' => '', 'den' => '']]]);
        $vi = $r->check($this->ctx('X1', 'X1'));

        $this->assertCount(2, $vi);
        $this->assertNotEquals($vi[0]->dedupKey(), $vi[1]->dedupKey());
    }

    /** @test */
    public function khong_doc_duoc_moc_chi_dinh_thi_im_lang()
    {
        $r = $this->tra(['C1' => [['tu' => '', 'den' => '']]]);

        $this->assertCount(0, $r->check($this->ctx('X1', 'X2', 0)));
    }

    /** @test */
    public function loai_phieu_loai_tru_chi_bo_qua_nguoi_thuc_hien()
    {
        // Khac B_DOCTOR_NO_PRACTICE_CERT: chi bo vai tro nguoi thuc hien, bac si ra don
        // thuoc van phai co CCHN hop le.
        $r = $this->tra(['C1' => [['tu' => '', 'den' => '']]], [], [6, 14, 15]);

        $vi = $r->check($this->ctx('X1', 'X2', 20240601080000, 6));

        $this->assertCount(1, $vi, 'Phai con lai dung vi pham cua bac si chi dinh');
        $this->assertSame('bs', $vi[0]->detail['vai_tro']);
    }

    /** @test */
    public function loai_phieu_loai_tru_chi_nguoi_thuc_hien_sai_thi_im_lang()
    {
        $r = $this->tra(['C1' => [['tu' => '', 'den' => '']]], [], [6, 14, 15]);

        $this->assertCount(0, $r->check($this->ctx('C1', 'X2', 20240601080000, 14)));
    }

    /** @test */
    public function loai_phieu_ngoai_danh_sach_van_xet_ca_hai_vai_tro()
    {
        $r = $this->tra(['C1' => [['tu' => '', 'den' => '']]], [], [6, 14, 15]);

        $this->assertCount(2, $r->check($this->ctx('X1', 'X2', 20240601080000, 2)));
    }

    /** @test */
    public function loai_phieu_null_van_xet_ca_hai_vai_tro()
    {
        $r = $this->tra(['C1' => [['tu' => '', 'den' => '']]], [], [6, 14, 15]);

        $this->assertCount(2, $r->check($this->ctx('X1', 'X2', 20240601080000, null)));
    }

    /** @test */
    public function loai_tru_het_cchn_can_tra_thi_khong_cham_danh_muc()
    {
        // Bac si chi dinh khong co CCHN, nguoi thuc hien bi loai tru -> khong con gi de tra.
        // De danh muc o trang thai CHUA nap: neu luat cham toi no, sanSang() se hoi CSDL va
        // medical_staffs dang rong nen cung tra 0 - nen ca nay khang dinh them bang viec
        // khong co ngoai le nao duoc nem.
        $lkCchn = new CatalogLookup('medical_staffs', 'macchn');
        $lkCchn->datSanChoTest([], ['C1' => [['tu' => '', 'den' => '']]]);
        $lkBhxh = new CatalogLookup('medical_staffs', 'ma_bhxh');
        $lkBhxh->datSanChoTest([]);

        $r = new StaffCertNotInCatalogRule($lkCchn, $lkBhxh, [6]);

        $this->assertCount(0, $r->check($this->ctx(null, 'X2', 20240601080000, 6)));
    }
}
