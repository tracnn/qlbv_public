<?php

namespace Tests\Unit\Xml3176\Support;

use App\Services\Xml3176\Support\LieuDungParser;
use Tests\TestCase;

class LieuDungParserTest extends TestCase
{
    /** @test */
    public function parse_dung_dinh_dang_130_co_don_vi()
    {
        $r = LieuDungParser::parse('1 * 2 * 5 [Viên/ngày]');
        $this->assertTrue($r['hop_le']);
        $this->assertEquals(1.0, $r['sl_lan']);
        $this->assertEquals(2.0, $r['lan_ngay']);
        $this->assertEquals(5, $r['so_ngay']);
        $this->assertEquals('Viên', $r['don_vi']);
        $this->assertEquals(10.0, $r['tong_luong']);
    }

    /** @test */
    public function parse_chap_nhan_thap_phan_dau_phay_va_khong_don_vi()
    {
        $r = LieuDungParser::parse('1,5*2*3');
        $this->assertTrue($r['hop_le']);
        $this->assertEquals(9.0, $r['tong_luong']);
        $this->assertEquals('', $r['don_vi']);
    }

    /** @test */
    public function parse_khong_hop_le_khi_khong_theo_dinh_dang()
    {
        foreach (['1 viên x 2 lần/ngày x 5 ngày', '1*2', 'abc', '1**2*3'] as $x) {
            $this->assertFalse(LieuDungParser::parse($x)['hop_le'], "phai sai: $x");
        }
    }

    /** @test */
    public function parse_rong_va_null_tra_khong_hop_le()
    {
        $this->assertFalse(LieuDungParser::parse('')['hop_le']);
        $this->assertFalse(LieuDungParser::parse(null)['hop_le']);
        $this->assertEquals(0.0, LieuDungParser::parse(null)['tong_luong']);
    }
}
