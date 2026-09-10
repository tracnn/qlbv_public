<?php

namespace Tests\Unit\Xml3176\Support;

use App\Services\Xml3176\Support\TienTeCalculator;
use Tests\TestCase;

class TienTeCalculatorTest extends TestCase
{
    /** @test */
    public function thanh_tien_bv_xml3_nhan_them_ty_le_dich_vu()
    {
        // Chuan (QD 4750): THANH_TIEN_BV = SO_LUONG * DON_GIA_BV * TYLE_TT_DV/100
        $this->assertSame(200000.0, TienTeCalculator::thanhTienBvXml3(2, 100000, 100));
        $this->assertSame(180000.0, TienTeCalculator::thanhTienBvXml3(2, 100000, 90));
    }

    /** @test */
    public function thanh_tien_bh_xml3_nhan_ca_hai_ty_le()
    {
        // THANH_TIEN_BH = SO_LUONG * DON_GIA_BH * TYLE_TT_DV/100 * TYLE_TT_BH/100
        $this->assertSame(100000.0, TienTeCalculator::thanhTienBhXml3(2, 100000, 100, 50));
        $this->assertSame(90000.0, TienTeCalculator::thanhTienBhXml3(2, 100000, 90, 50));
    }

    /** @test */
    public function cong_thuc_xml2_khong_co_ty_le_dich_vu()
    {
        $this->assertSame(30000.0, TienTeCalculator::thanhTienBvXml2(3, 10000));
        $this->assertSame(24000.0, TienTeCalculator::thanhTienBhXml2(3, 10000, 80));
    }

    /** @test */
    public function t_bhtt_theo_muc_huong()
    {
        $this->assertSame(80000.0, TienTeCalculator::tBhtt(100000, 80));
        $this->assertSame(32000.0, TienTeCalculator::tBhtt(100000, 32));
    }

    /** @test */
    public function tong_nguon_khac_cong_du_bon_nguon()
    {
        $this->assertSame(1000.0, TienTeCalculator::tongNguonKhac(100, 200, 300, 400));
        $this->assertSame(100.0, TienTeCalculator::tongNguonKhac(100, null, null, null));
    }

    /** @test */
    public function lam_tron_den_hai_chu_so_thap_phan()
    {
        $this->assertSame(33333.33, TienTeCalculator::thanhTienBvXml2(1, 33333.333));
    }

    /** @test */
    public function lech_dung_o_bien_sai_so()
    {
        $this->assertFalse(TienTeCalculator::lech(100.0, 100.99, 1.0));
        $this->assertFalse(TienTeCalculator::lech(100.0, 101.0, 1.0));
        $this->assertTrue(TienTeCalculator::lech(100.0, 101.01, 1.0));
        $this->assertTrue(TienTeCalculator::lech(101.01, 100.0, 1.0));
    }

    /** @test */
    public function la_so_nhan_dien_dung_toan_hang_thieu()
    {
        $this->assertTrue(TienTeCalculator::laSo(0));
        $this->assertTrue(TienTeCalculator::laSo('1234.5'));
        $this->assertFalse(TienTeCalculator::laSo(null));
        $this->assertFalse(TienTeCalculator::laSo(''));
        $this->assertFalse(TienTeCalculator::laSo('abc'));
    }

    /** @test */
    public function ty_le_hop_le_chi_trong_khoang_0_den_100()
    {
        $this->assertTrue(TienTeCalculator::tyLeHopLe(100));
        $this->assertTrue(TienTeCalculator::tyLeHopLe(0.5));
        $this->assertFalse(TienTeCalculator::tyLeHopLe(0));
        $this->assertFalse(TienTeCalculator::tyLeHopLe(101));
        $this->assertFalse(TienTeCalculator::tyLeHopLe(null));
    }
}
