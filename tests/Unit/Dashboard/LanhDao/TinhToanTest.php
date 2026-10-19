<?php

namespace Tests\Unit\Dashboard\LanhDao;

use Tests\TestCase;
use App\Services\Dashboard\LanhDao\TinhToan;

class TinhToanTest extends TestCase
{
    /** @test */
    public function ty_le_mau_0_tra_null()
    {
        $this->assertNull(TinhToan::tyLe(3, 0));
        $this->assertSame(33.3, TinhToan::tyLe(1, 3));
    }

    /** @test */
    public function trung_vi()
    {
        $this->assertSame(3.0, TinhToan::trungVi([5, 1, 3]));
        $this->assertSame(2.5, TinhToan::trungVi([1, 2, 3, 4]));
        $this->assertNull(TinhToan::trungVi([]));
    }

    /** @test */
    public function cong_suat()
    {
        $this->assertSame(100.0, TinhToan::congSuat(30, 10, 3));
        $this->assertNull(TinhToan::congSuat(5, 0, 3));
        $this->assertNull(TinhToan::congSuat(5, 10, 0));
    }

    /** @test */
    public function gop_khoang_chong_chi_tinh_mot()
    {
        $this->assertSame([[10, 30], [40, 50]], TinhToan::gopKhoang([[40, 50], [15, 30], [10, 20]]));
        $this->assertSame([[10, 30]], TinhToan::gopKhoang([[10, 20], [20, 30]]));
        $this->assertSame([], TinhToan::gopKhoang([]));
    }

    /** @test */
    public function giay_trong_ky_cat_hai_dau()
    {
        $this->assertSame(30, TinhToan::giayTrongKy([[0, 100]], 20, 50));
        $this->assertSame(0, TinhToan::giayTrongKy([[0, 10]], 20, 50));
        $this->assertSame(40, TinhToan::giayTrongKy([[0, 30], [20, 60], [90, 100]], 10, 50));
    }

    /** @test */
    public function sap_giam_null_xuong_cuoi()
    {
        $cot = [['t' => 'a', 'v' => null], ['t' => 'b', 'v' => 5], ['t' => 'c', 'v' => 9.5]];

        $this->assertSame(['c', 'b', 'a'], array_column(TinhToan::sapGiam($cot, 'v'), 't'));
    }

    /** @test */
    public function nhom_phut_co_sau_nhom()
    {
        $this->assertCount(6, TinhToan::NHOM_PHUT);
        $this->assertSame([120, null], TinhToan::NHOM_PHUT['>120']);
    }
}
