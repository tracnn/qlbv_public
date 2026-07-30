<?php

namespace Tests\Unit;

use App\Services\OrderCheck\Support\DsMienCchn;
use Tests\TestCase;

class DsMienCchnTest extends TestCase
{
    /** @test */
    public function doc_csv_thanh_mang()
    {
        $this->assertSame(['mitalab', 'vietrad', 'sys'], DsMienCchn::doc('mitalab,vietrad,sys'));
    }

    /** @test */
    public function csv_rong_thi_mang_rong()
    {
        $this->assertSame([], DsMienCchn::doc(''));
        $this->assertSame([], DsMienCchn::doc(null));
        $this->assertSame([], DsMienCchn::doc('   '));
    }

    /** @test */
    public function doc_cat_khoang_trang_va_ha_thuong()
    {
        // HIS co tai khoan viet hoa lan lon (BHXHConnector, BMCS, PACS) nen phai chuan hoa,
        // neu khong nguoi dung khai 'pacs' ma he thong van bao vi pham cho 'PACS'.
        $this->assertSame(['mitalab', 'vietrad'], DsMienCchn::doc(' Mitalab , VIETRAD '));
    }

    /** @test */
    public function doc_bo_phan_tu_rong()
    {
        $this->assertSame(['mitalab', 'sys'], DsMienCchn::doc('mitalab,,sys,'));
    }

    /** @test */
    public function tai_khoan_trong_danh_sach_thi_duoc_mien()
    {
        $this->assertTrue(DsMienCchn::duocMien('mitalab', ['mitalab']));
    }

    /** @test */
    public function so_khop_khong_phan_biet_hoa_thuong()
    {
        $this->assertTrue(DsMienCchn::duocMien('MitaLab', ['mitalab']));
    }

    /** @test */
    public function so_khop_cat_khoang_trang()
    {
        $this->assertTrue(DsMienCchn::duocMien('  mitalab  ', ['mitalab']));
    }

    /**
     * Nguoi THAT van phai bi kiem. ntdh3 la Nguyen Thi Dieu Hang, vttq2 la Vo Thi Thuy
     * Quynh - ho thieu CCHN trong HIS, do la phat hien DUNG cua quy tac.
     */
    /** @test */
    public function nguoi_that_khong_duoc_mien()
    {
        $this->assertFalse(DsMienCchn::duocMien('ntdh3', ['mitalab', 'vietrad', 'sys']));
        $this->assertFalse(DsMienCchn::duocMien('vttq2', ['mitalab', 'vietrad', 'sys']));
    }

    /** @test */
    public function danh_sach_rong_thi_khong_mien_ai()
    {
        $this->assertFalse(DsMienCchn::duocMien('mitalab', []));
    }

    /** @test */
    public function loginname_rong_thi_khong_duoc_mien()
    {
        $this->assertFalse(DsMienCchn::duocMien(null, ['mitalab']));
        $this->assertFalse(DsMienCchn::duocMien('', ['mitalab']));
    }

    /** @test */
    public function cau_hinh_mac_dinh_co_ba_tai_khoan()
    {
        $ds = DsMienCchn::doc(config('order_check.practice_cert_exclude_loginnames'));

        sort($ds);

        $this->assertSame(['mitalab', 'sys', 'vietrad'], $ds);
    }

    /** @test */
    public function danh_sach_chua_chuan_hoa_van_so_khop_dung()
    {
        // Chuan hoa phai ap CA HAI VE. Neu chi chuan hoa mot ve, ai do truyen thang danh
        // sach chua qua doc() se bi bo sot IM LANG.
        $this->assertTrue(DsMienCchn::duocMien('mitalab', ['MitaLab']));
        $this->assertTrue(DsMienCchn::duocMien('MITALAB', [' mitalab ']));
        $this->assertFalse(DsMienCchn::duocMien('ntdh3', ['MitaLab', 'VIETRAD']));
    }
}
