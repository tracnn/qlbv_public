<?php

namespace Tests\Unit\Xml3176\Support;

use App\Services\Xml3176\Support\TyLeComparator;
use Tests\TestCase;

class TyLeComparatorTest extends TestCase
{
    /** @test */
    public function khong_lech_khi_bang_nhau()
    {
        $this->assertFalse(TyLeComparator::lech(100, 100));
        $this->assertFalse(TyLeComparator::lech('100', '100.00'));
    }

    /** @test */
    public function lech_khi_khac_nhau()
    {
        // BV đề nghị 100 nhưng danh mục duyệt 50
        $this->assertTrue(TyLeComparator::lech(100, 50));
        $this->assertTrue(TyLeComparator::lech('80', '100'));
    }

    /** @test */
    public function bo_qua_khi_danh_muc_rong_hoac_null()
    {
        $this->assertFalse(TyLeComparator::lech(100, null));
        $this->assertFalse(TyLeComparator::lech(100, ''));
    }

    /** @test */
    public function bo_qua_khi_xml_rong_hoac_null()
    {
        $this->assertFalse(TyLeComparator::lech(null, 100));
        $this->assertFalse(TyLeComparator::lech('', 100));
    }

    /** @test */
    public function bo_qua_khi_danh_muc_khong_duong()
    {
        // tyle_tt_bh = 0 trong danh mục coi như chưa cấu hình -> không đối chiếu
        $this->assertFalse(TyLeComparator::lech(100, 0));
        $this->assertFalse(TyLeComparator::lech(100, '0.00'));
    }

    /** @test */
    public function ton_trong_dung_sai()
    {
        // trong dung sai mặc định 0.01 -> không lệch
        $this->assertFalse(TyLeComparator::lech(100, 100.009));
        // ngoài dung sai -> lệch
        $this->assertTrue(TyLeComparator::lech(100, 100.02));
    }

    /** @test */
    public function bo_qua_khi_khong_phai_so()
    {
        $this->assertFalse(TyLeComparator::lech('abc', 100));
        $this->assertFalse(TyLeComparator::lech(100, 'x'));
    }
}
