<?php

namespace Tests\Unit\Xml3176\Checker;

use App\Models\BHYT\Xml3176Xml3;
use App\Services\Xml3176Xml3Checker;
use Tests\Support\Xml3176RuleTestSupport;
use Tests\TestCase;

class Xml3176Xml3CauTrucMaDichVuTest extends TestCase
{
    use Xml3176RuleTestSupport;

    protected function setUp()
    {
        parent::setUp();
        // isMedicalOrganizationValid() truy van bang medical_organizations - thieu bang
        // nay thi test nem QueryException chu khong tra ve false.
        $this->bootXml3176Sqlite([
            '2026_01_09_152832_create_xml3176_xml3s_table.php',
            '2024_07_05_113054_create_medical_organizations_table.php',
        ]);
    }

    private function codes(array $thuocTinh): array
    {
        $dong = new Xml3176Xml3(array_merge(['ma_lk' => 'A', 'stt' => 1], $thuocTinh));

        return $this->errorCodes($this->invokePrivate(
            $this->makeChecker(Xml3176Xml3Checker::class),
            'checkCauTrucMaDichVu',
            $dong
        ));
    }

    /** @test */
    public function hau_to_tb_ma_con_don_gia_thi_bao_loi()
    {
        // Dung hinh dang do duoc tren du lieu that: 02.0261.0319_TB, don gia 1.595.000.
        $codes = $this->codes([
            'ma_dich_vu' => '02.0261.0319_TB',
            'don_gia_bh' => 1595000,
            'don_gia_bv' => 1595000,
        ]);

        $this->assertContains('XML3_MA_DICH_VU_TB_CO_DON_GIA', $codes);
    }

    /** @test */
    public function hau_to_tb_va_don_gia_bang_khong_thi_im_lang()
    {
        $codes = $this->codes([
            'ma_dich_vu' => '02.0261.0319_TB',
            'don_gia_bh' => 0,
            'don_gia_bv' => 0,
        ]);

        $this->assertNotContains('XML3_MA_DICH_VU_TB_CO_DON_GIA', $codes);
    }

    /** @test */
    public function ma_chua_co_gia_ma_con_don_gia_bh()
    {
        $codes = $this->codes(['ma_dich_vu' => '02.0261.0000', 'don_gia_bh' => 500000]);
        $this->assertContains('XML3_MA_DICH_VU_CHUA_CO_GIA_CO_DON_GIA_BH', $codes);

        $codes = $this->codes(['ma_dich_vu' => '02.0261.0000', 'don_gia_bh' => 0]);
        $this->assertNotContains('XML3_MA_DICH_VU_CHUA_CO_GIA_CO_DON_GIA_BH', $codes);
    }

    /** @test */
    public function hau_to_la_bi_bao_con_tb_va_gt_thi_khong()
    {
        $this->assertContains('XML3_MA_DICH_VU_HAU_TO_LA',
            $this->codes(['ma_dich_vu' => '02.0261.0319_XX']));

        $this->assertNotContains('XML3_MA_DICH_VU_HAU_TO_LA',
            $this->codes(['ma_dich_vu' => '02.0261.0319_TB', 'don_gia_bh' => 0, 'don_gia_bv' => 0]));

        $this->assertNotContains('XML3_MA_DICH_VU_HAU_TO_LA',
            $this->codes(['ma_dich_vu' => '02.0261.0319_GT']));

        $this->assertNotContains('XML3_MA_DICH_VU_HAU_TO_LA',
            $this->codes(['ma_dich_vu' => '02.0261.0319']));
    }

    /** @test */
    public function van_chuyen_thieu_ma_xang_dau()
    {
        $this->assertContains('XML3_MA_DICH_VU_VAN_CHUYEN_THIEU_XANG_DAU',
            $this->codes(['ma_dich_vu' => 'VC.01234', 'ma_xang_dau' => null]));

        $this->assertNotContains('XML3_MA_DICH_VU_VAN_CHUYEN_THIEU_XANG_DAU',
            $this->codes(['ma_dich_vu' => 'VC.01234', 'ma_xang_dau' => 'XD01']));
    }

    /** @test */
    public function van_chuyen_va_chuyen_mau_bao_ma_co_so_khong_co_trong_danh_muc()
    {
        // Danh muc co so KBCB rong trong sqlite nen moi ma deu khong tra duoc.
        $this->assertContains('XML3_MA_DICH_VU_VAN_CHUYEN_CSKCB_NOT_FOUND',
            $this->codes(['ma_dich_vu' => 'VC.01234', 'ma_xang_dau' => 'XD01']));

        $this->assertContains('XML3_MA_DICH_VU_CHUYEN_MAU_CSKCB_NOT_FOUND',
            $this->codes(['ma_dich_vu' => '02.0261.0319.K.01234']));
    }

    /** @test */
    public function ma_dich_vu_rong_thi_moi_quy_tac_im_lang()
    {
        // Da co MISSING_SERVICE_OR_MATERIAL lo truong hop nay.
        $this->assertSame([], $this->codes(['ma_dich_vu' => null, 'don_gia_bh' => 999]));
    }

    /** @test */
    public function ma_thuong_khong_sinh_loi_nao()
    {
        $this->assertSame([], $this->codes([
            'ma_dich_vu' => '02.0261.0319',
            'don_gia_bh' => 1595000,
            'don_gia_bv' => 1595000,
        ]));
    }
}
