<?php

namespace Tests\Unit\Xml3176\Support;

use App\Services\Xml3176\Support\DanhSachPhanCachParser;
use Tests\TestCase;

class DanhSachPhanCachParserTest extends TestCase
{
    /** @test */
    public function mot_phan_tu()
    {
        $this->assertSame(['20260910'], DanhSachPhanCachParser::tach('20260910'));
    }

    /** @test */
    public function nhieu_phan_tu()
    {
        $this->assertSame(
            ['20260910', '20260913'],
            DanhSachPhanCachParser::tach('20260910;20260913')
        );
    }

    /** @test */
    public function bo_khoang_trang_thua_quanh_tung_phan_tu()
    {
        $this->assertSame(
            ['20260910', '20260913'],
            DanhSachPhanCachParser::tach(' 20260910 ; 20260913 ')
        );
    }

    /** @test */
    public function bo_phan_tu_rong_va_danh_lai_chi_so()
    {
        // Dau ';' o cuoi la thoi quen pho bien cua bo xuat HIS.
        $this->assertSame(['3200', '2800'], DanhSachPhanCachParser::tach('3200;;2800;'));
    }

    /** @test */
    public function chuoi_rong_tra_mang_rong()
    {
        $this->assertSame([], DanhSachPhanCachParser::tach(''));
        $this->assertSame([], DanhSachPhanCachParser::tach('   '));
        $this->assertSame([], DanhSachPhanCachParser::tach(null));
        $this->assertSame([], DanhSachPhanCachParser::tach(';;'));
    }
}
