<?php

namespace Tests\Unit\Xml3176\Checker;

use App\Models\BHYT\Xml3176Xml3;
use App\Services\Xml3176Xml3Checker;
use Tests\Support\Xml3176RuleTestSupport;
use Tests\TestCase;

class Xml3176Xml3CongThucTienTest extends TestCase
{
    use Xml3176RuleTestSupport;

    protected function setUp()
    {
        parent::setUp();
        $this->bootXml3176Sqlite(['2026_01_09_152832_create_xml3176_xml3s_table.php']);
        config(['xml3176.tien.sai_so' => 1.0]);
    }

    /** Mot dong dung het moi cong thuc, de tung ca chi sua dung truong minh thu. */
    private function dongDung(array $ghiDe = []): array
    {
        return array_merge([
            'ma_lk' => 'A', 'stt' => 1,
            'so_luong' => 2, 'don_gia_bv' => 100000, 'don_gia_bh' => 100000,
            'tyle_tt_dv' => 100, 'tyle_tt_bh' => 100,
            'thanh_tien_bv' => 200000, 'thanh_tien_bh' => 200000,
            'muc_huong' => 80, 't_bhtt' => 160000,
            't_nguonkhac' => 0, 't_nguonkhac_nsnn' => 0, 't_nguonkhac_vtnn' => 0,
            't_nguonkhac_vttn' => 0, 't_nguonkhac_cl' => 0,
            't_trantt' => null,
        ], $ghiDe);
    }

    private function codes(array $ghiDe = []): array
    {
        return $this->errorCodes($this->invokePrivate(
            $this->makeChecker(Xml3176Xml3Checker::class),
            'checkCongThucTien',
            new Xml3176Xml3($this->dongDung($ghiDe))
        ));
    }

    /** @test */
    public function dong_dung_het_thi_khong_sinh_loi_nao()
    {
        $this->assertSame([], $this->codes());
    }

    /** @test */
    public function thanh_tien_bv_sai_cong_thuc()
    {
        $this->assertContains('XML3_THANH_TIEN_BV_SAI_CONG_THUC',
            $this->codes(['thanh_tien_bv' => 250000]));
    }

    /** @test */
    public function thanh_tien_bv_tinh_ca_ty_le_dich_vu()
    {
        // TYLE_TT_DV = 90 => 2 * 100000 * 0.9 = 180000
        $this->assertNotContains('XML3_THANH_TIEN_BV_SAI_CONG_THUC',
            $this->codes(['tyle_tt_dv' => 90, 'thanh_tien_bv' => 180000,
                'thanh_tien_bh' => 180000, 't_bhtt' => 144000]));
    }

    /** @test */
    public function thanh_tien_bh_sai_cong_thuc()
    {
        $this->assertContains('XML3_THANH_TIEN_BH_SAI_CONG_THUC',
            $this->codes(['thanh_tien_bh' => 199000, 't_bhtt' => 159200]));
    }

    /** @test */
    public function t_nguonkhac_khong_bang_tong_bon_nguon_con()
    {
        $this->assertContains('XML3_T_NGUONKHAC_SAI_TONG',
            $this->codes(['t_nguonkhac' => 5000, 't_nguonkhac_nsnn' => 1000]));
    }

    /** @test */
    public function t_bhtt_sai_cong_thuc()
    {
        $this->assertContains('XML3_T_BHTT_SAI_CONG_THUC',
            $this->codes(['t_bhtt' => 200000]));
    }

    /** @test */
    public function co_nguon_khac_thi_t_bhtt_im_lang()
    {
        // Chuan quy dinh giam tru lan luot vao T_BNTT, T_BNCCT, T_BHTT tuy loai nguon,
        // ma du lieu khong phan biet duoc loai. Khong du can cu de ket luan.
        $codes = $this->codes([
            't_nguonkhac' => 1000, 't_nguonkhac_cl' => 1000, 't_bhtt' => 200000,
        ]);

        $this->assertNotContains('XML3_T_BHTT_SAI_CONG_THUC', $codes);
    }

    /** @test */
    public function co_tran_thanh_toan_thi_t_bhtt_im_lang()
    {
        // Da co INVALID_T_TRANTT_T_BHTT lo truong hop bi chan tren.
        $this->assertNotContains('XML3_T_BHTT_SAI_CONG_THUC',
            $this->codes(['t_trantt' => 50000, 't_bhtt' => 200000]));
    }

    /** @test */
    public function ty_le_ngoai_khoang_hop_le_thi_im_lang()
    {
        // Da co INFO_ERROR_TYLE_TT_DV / INFO_ERROR_TYLE_TT_BH lo viec do; bao them
        // chi la nhieu vi moi cong thuc dan xuat deu sai theo.
        $codes = $this->codes(['tyle_tt_dv' => 0]);

        $this->assertNotContains('XML3_THANH_TIEN_BV_SAI_CONG_THUC', $codes);
        $this->assertNotContains('XML3_THANH_TIEN_BH_SAI_CONG_THUC', $codes);
    }

    /** @test */
    public function thieu_toan_hang_thi_im_lang()
    {
        $codes = $this->codes([
            'so_luong' => null, 'thanh_tien_bv' => null, 'thanh_tien_bh' => null,
            't_bhtt' => null, 't_nguonkhac' => null,
        ]);

        $this->assertSame([], $codes);
    }

    /** @test */
    public function chenh_trong_sai_so_thi_khong_bao()
    {
        $this->assertSame([], $this->codes(['thanh_tien_bv' => 200000.5]));
    }
}
