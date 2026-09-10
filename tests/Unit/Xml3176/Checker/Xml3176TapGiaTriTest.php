<?php

namespace Tests\Unit\Xml3176\Checker;

use App\Models\BHYT\Xml3176Xml1;
use App\Models\BHYT\Xml3176Xml2;
use App\Models\BHYT\Xml3176Xml3;
use App\Services\Xml3176Xml1Checker;
use App\Services\Xml3176Xml2Checker;
use App\Services\Xml3176Xml3Checker;
use Tests\Support\Xml3176RuleTestSupport;
use Tests\TestCase;

class Xml3176TapGiaTriTest extends TestCase
{
    use Xml3176RuleTestSupport;

    protected function setUp()
    {
        parent::setUp();
        $this->bootXml3176Sqlite([
            '2026_01_09_152817_create_xml3176_xml1s_table.php',
            '2026_01_09_152826_create_xml3176_xml2s_table.php',
            '2026_01_09_152832_create_xml3176_xml3s_table.php',
        ]);
        config(['xml3176.tien.sai_so' => 1.0]);
    }

    private function x3(array $ghiDe): array
    {
        return $this->errorCodes($this->invokePrivate(
            $this->makeChecker(Xml3176Xml3Checker::class), 'checkTapGiaTri',
            new Xml3176Xml3(array_merge(['ma_lk' => 'A', 'stt' => 1], $ghiDe))
        ));
    }

    private function x2(array $ghiDe): array
    {
        return $this->errorCodes($this->invokePrivate(
            $this->makeChecker(Xml3176Xml2Checker::class), 'checkNguonChiTra',
            new Xml3176Xml2(array_merge(['ma_lk' => 'A', 'stt' => 1], $ghiDe))
        ));
    }

    private function x1(array $ghiDe): array
    {
        return $this->errorCodes($this->invokePrivate(
            $this->makeChecker(Xml3176Xml1Checker::class), 'checkMaKhuVuc',
            new Xml3176Xml1(array_merge(['ma_lk' => 'A', 'stt' => 1], $ghiDe))
        ));
    }

    /** @test */
    public function pham_vi_ngoai_tap_gia_tri()
    {
        $this->assertContains('XML3_PHAM_VI_NGOAI_TAP_GIA_TRI', $this->x3(['pham_vi' => '4']));
        $this->assertNotContains('XML3_PHAM_VI_NGOAI_TAP_GIA_TRI', $this->x3(['pham_vi' => '1']));
        $this->assertNotContains('XML3_PHAM_VI_NGOAI_TAP_GIA_TRI', $this->x3(['pham_vi' => '2']));
        $this->assertNotContains('XML3_PHAM_VI_NGOAI_TAP_GIA_TRI', $this->x3(['pham_vi' => '3']));
    }

    /** @test */
    public function pham_vi_rong_khong_sinh_loi()
    {
        // Chuan khong danh dau truong nay bat buoc.
        $this->assertSame([], $this->x3(['pham_vi' => null]));
    }

    /** @test */
    public function pham_vi_2_la_nguoi_benh_tu_tra_nen_quy_khong_duoc_tra()
    {
        // QD 4750 sua toan bo dien giai: ma 2 = do nguoi benh tu tra.
        $this->assertContains('XML3_PHAM_VI_TU_TRA_MA_BH_TRA',
            $this->x3(['pham_vi' => '2', 'thanh_tien_bh' => 50000]));

        $this->assertContains('XML3_PHAM_VI_TU_TRA_MA_BH_TRA',
            $this->x3(['pham_vi' => '2', 't_bhtt' => 50000]));

        $this->assertNotContains('XML3_PHAM_VI_TU_TRA_MA_BH_TRA',
            $this->x3(['pham_vi' => '2', 'thanh_tien_bh' => 0, 't_bhtt' => 0]));

        $this->assertNotContains('XML3_PHAM_VI_TU_TRA_MA_BH_TRA',
            $this->x3(['pham_vi' => '1', 'thanh_tien_bh' => 50000]));
    }

    /** @test */
    public function tai_su_dung_chi_duoc_rong_hoac_bang_1()
    {
        $this->assertContains('XML3_TAI_SU_DUNG_INVALID', $this->x3(['tai_su_dung' => '2']));
        $this->assertNotContains('XML3_TAI_SU_DUNG_INVALID', $this->x3(['tai_su_dung' => '1',
            'don_gia_bv' => 55000, 'don_gia_bh' => 55000]));
        $this->assertNotContains('XML3_TAI_SU_DUNG_INVALID', $this->x3(['tai_su_dung' => null]));
    }

    /** @test */
    public function vtyt_tai_su_dung_thi_hai_don_gia_phai_bang_nhau()
    {
        // Chuan, truong DON_GIA_BV: "VTYT tai su dung: DON_GIA_BV = DON_GIA_BH".
        $this->assertContains('XML3_TAI_SU_DUNG_DON_GIA_LECH',
            $this->x3(['tai_su_dung' => '1', 'don_gia_bv' => 100000, 'don_gia_bh' => 55000]));

        $this->assertNotContains('XML3_TAI_SU_DUNG_DON_GIA_LECH',
            $this->x3(['tai_su_dung' => '1', 'don_gia_bv' => 55000, 'don_gia_bh' => 55000]));
    }

    /** @test */
    public function tai_su_dung_thieu_don_gia_thi_im_lang()
    {
        $this->assertNotContains('XML3_TAI_SU_DUNG_DON_GIA_LECH',
            $this->x3(['tai_su_dung' => '1', 'don_gia_bv' => null, 'don_gia_bh' => 55000]));
    }

    /** @test */
    public function nguon_ctra_ngoai_tap_gia_tri()
    {
        $this->assertContains('XML2_NGUON_CTRA_INVALID', $this->x2(['nguon_ctra' => '5']));
        $this->assertNotContains('XML2_NGUON_CTRA_INVALID', $this->x2(['nguon_ctra' => '1']));
        $this->assertNotContains('XML2_NGUON_CTRA_INVALID', $this->x2(['nguon_ctra' => '4']));
        $this->assertSame([], $this->x2(['nguon_ctra' => null]));
    }

    /** @test */
    public function nguon_ngoai_quy_bhyt_thi_quy_khong_duoc_tra()
    {
        // Ma 2/3/4 = thuoc du an, chuong trinh muc tieu, nguon khac.
        $this->assertContains('XML2_NGUON_CTRA_NGOAI_QUY_MA_BH_TRA',
            $this->x2(['nguon_ctra' => '2', 't_bhtt' => 10000]));

        $this->assertContains('XML2_NGUON_CTRA_NGOAI_QUY_MA_BH_TRA',
            $this->x2(['nguon_ctra' => '4', 't_bhtt' => 10000]));

        $this->assertNotContains('XML2_NGUON_CTRA_NGOAI_QUY_MA_BH_TRA',
            $this->x2(['nguon_ctra' => '1', 't_bhtt' => 10000]));

        $this->assertNotContains('XML2_NGUON_CTRA_NGOAI_QUY_MA_BH_TRA',
            $this->x2(['nguon_ctra' => '4', 't_bhtt' => 0]));
    }

    /** @test */
    public function ma_khu_vuc_chi_duoc_k1_k2_k3()
    {
        $this->assertContains('XML1_ADMIN_INFO_ERROR_MA_KHUVUC', $this->x1(['ma_khuvuc' => 'K4']));
        $this->assertContains('XML1_ADMIN_INFO_ERROR_MA_KHUVUC', $this->x1(['ma_khuvuc' => 'XX']));
        $this->assertNotContains('XML1_ADMIN_INFO_ERROR_MA_KHUVUC', $this->x1(['ma_khuvuc' => 'K1']));
        $this->assertNotContains('XML1_ADMIN_INFO_ERROR_MA_KHUVUC', $this->x1(['ma_khuvuc' => 'K3']));
    }

    /** @test */
    public function ma_khu_vuc_rong_khong_sinh_loi()
    {
        $this->assertSame([], $this->x1(['ma_khuvuc' => null]));
        $this->assertSame([], $this->x1(['ma_khuvuc' => '']));
    }
}
