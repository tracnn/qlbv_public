<?php

namespace Tests\Unit\Xml3176\Checker;

use App\Models\BHYT\Xml3176Xml2;
use App\Services\Xml3176Xml2Checker;
use Tests\Support\Xml3176RuleTestSupport;
use Tests\TestCase;

class Xml3176Xml2CongThucTienTest extends TestCase
{
    use Xml3176RuleTestSupport;

    protected function setUp()
    {
        parent::setUp();
        $this->bootXml3176Sqlite(['2026_01_09_152826_create_xml3176_xml2s_table.php']);
        config(['xml3176.tien.sai_so' => 1.0]);
    }

    private function codes(array $ghiDe = []): array
    {
        $dong = new Xml3176Xml2(array_merge([
            'ma_lk' => 'A', 'stt' => 1,
            'so_luong' => 3, 'don_gia' => 10000, 'tyle_tt_bh' => 100,
            'thanh_tien_bv' => 30000, 'thanh_tien_bh' => 30000,
            'muc_huong' => 80, 't_bhtt' => 24000,
            't_nguonkhac' => 0, 't_nguonkhac_nsnn' => 0, 't_nguonkhac_vtnn' => 0,
            't_nguonkhac_vttn' => 0, 't_nguonkhac_cl' => 0,
        ], $ghiDe));

        return $this->errorCodes($this->invokePrivate(
            $this->makeChecker(Xml3176Xml2Checker::class),
            'checkCongThucTien',
            $dong
        ));
    }

    /** @test */
    public function dong_dung_het_thi_khong_sinh_loi_nao()
    {
        $this->assertSame([], $this->codes());
    }

    /** @test */
    public function thanh_tien_bv_bang_so_luong_nhan_don_gia()
    {
        // XML2 KHONG co TYLE_TT_DV: THANH_TIEN_BV = SO_LUONG * DON_GIA
        $this->assertContains('XML2_THANH_TIEN_BV_SAI_CONG_THUC',
            $this->codes(['thanh_tien_bv' => 27000]));
    }

    /** @test */
    public function thanh_tien_bh_nhan_ty_le_thanh_toan_bh()
    {
        $this->assertNotContains('XML2_THANH_TIEN_BH_SAI_CONG_THUC',
            $this->codes(['tyle_tt_bh' => 50, 'thanh_tien_bh' => 15000, 't_bhtt' => 12000]));

        $this->assertContains('XML2_THANH_TIEN_BH_SAI_CONG_THUC',
            $this->codes(['tyle_tt_bh' => 50, 'thanh_tien_bh' => 30000, 't_bhtt' => 24000]));
    }

    /** @test */
    public function t_nguonkhac_khong_bang_tong_bon_nguon_con()
    {
        $this->assertContains('XML2_T_NGUONKHAC_SAI_TONG',
            $this->codes(['t_nguonkhac' => 5000, 't_nguonkhac_nsnn' => 1000]));
    }

    /** @test */
    public function thanh_phan_nguon_khac_co_ma_tong_null_van_bao()
    {
        // Importer (Xml3176Service) quy 0 ve NULL: T_NGUONKHAC = NULL (rong) nhung
        // T_NGUONKHAC_NSNN = 100000 la bat nhat that, phai bao du t_nguonkhac rong.
        $codes = $this->codes([
            't_nguonkhac' => null,
            't_nguonkhac_nsnn' => 100000,
        ]);

        $this->assertContains('XML2_T_NGUONKHAC_SAI_TONG', $codes);
    }

    /** @test */
    public function t_bhtt_sai_cong_thuc()
    {
        $this->assertContains('XML2_T_BHTT_SAI_CONG_THUC', $this->codes(['t_bhtt' => 30000]));
    }

    /** @test */
    public function co_nguon_khac_thi_t_bhtt_im_lang()
    {
        $this->assertNotContains('XML2_T_BHTT_SAI_CONG_THUC',
            $this->codes(['t_nguonkhac' => 1000, 't_nguonkhac_cl' => 1000, 't_bhtt' => 30000]));
    }

    /** @test */
    public function ty_le_ngoai_khoang_hop_le_thi_im_lang()
    {
        $codes = $this->codes(['tyle_tt_bh' => 0]);

        $this->assertNotContains('XML2_THANH_TIEN_BH_SAI_CONG_THUC', $codes);
    }

    /** @test */
    public function thieu_toan_hang_thi_im_lang()
    {
        $this->assertSame([], $this->codes([
            'so_luong' => null, 'thanh_tien_bv' => null, 'thanh_tien_bh' => null,
            't_bhtt' => null, 't_nguonkhac' => null,
        ]));
    }
}
