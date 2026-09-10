<?php

namespace Tests\Unit\Xml3176;

use App\Services\Xml3176\Support\DoiTuongKcbMatcher;
use Tests\TestCase;

/**
 * Ho so khong phai BHYT (ho so dich vu) khong can ra loi BHYT.
 * Ma doi tuong KCB theo bo ma DMDC dung dang phan cap co dau cham ('1.1', '1.17'),
 * nen '9' phai bao ca nhanh '9.*' nhung KHONG duoc nuot '91'.
 */
class DoiTuongKcbMatcherTest extends TestCase
{
    private $danhSach = ['9'];

    /** @test */
    public function khop_dung_ma_trong_danh_sach()
    {
        $this->assertTrue(DoiTuongKcbMatcher::khongCanKiem('9', $this->danhSach));
    }

    /** @test */
    public function khop_ca_nhanh_con_ngan_boi_dau_cham()
    {
        $this->assertTrue(DoiTuongKcbMatcher::khongCanKiem('9.1', $this->danhSach));
        $this->assertTrue(DoiTuongKcbMatcher::khongCanKiem('9.17', $this->danhSach));
    }

    /** @test */
    public function khong_nuot_ma_chi_trung_ky_tu_dau()
    {
        // '91' khong phai nhanh con cua '9'. Neu dung strpos()===0 thuan thi ca nay do.
        $this->assertFalse(DoiTuongKcbMatcher::khongCanKiem('91', $this->danhSach));
        $this->assertFalse(DoiTuongKcbMatcher::khongCanKiem('99', $this->danhSach));
    }

    /** @test */
    public function ma_bhyt_that_khong_bi_loai_tru()
    {
        $this->assertFalse(DoiTuongKcbMatcher::khongCanKiem('1.1', $this->danhSach));
        $this->assertFalse(DoiTuongKcbMatcher::khongCanKiem('1.17', $this->danhSach));
    }

    /** @test */
    public function thieu_can_cu_thi_van_ra_loi()
    {
        // Bo nham la mat bao ve trong im lang; ra nham chi la nhieu nhin thay duoc.
        $this->assertFalse(DoiTuongKcbMatcher::khongCanKiem(null, $this->danhSach));
        $this->assertFalse(DoiTuongKcbMatcher::khongCanKiem('', $this->danhSach));
        $this->assertFalse(DoiTuongKcbMatcher::khongCanKiem('   ', $this->danhSach));
    }

    /** @test */
    public function danh_sach_rong_thi_khong_loai_tru_gi()
    {
        $this->assertFalse(DoiTuongKcbMatcher::khongCanKiem('9', []));
    }

    /** @test */
    public function bo_khoang_trang_thua_hai_ben()
    {
        $this->assertTrue(DoiTuongKcbMatcher::khongCanKiem(' 9 ', $this->danhSach));
    }

    /** @test */
    public function nhieu_ma_trong_danh_sach()
    {
        $this->assertTrue(DoiTuongKcbMatcher::khongCanKiem('5.2', ['9', '5']));
        $this->assertFalse(DoiTuongKcbMatcher::khongCanKiem('4', ['9', '5']));
    }
}
