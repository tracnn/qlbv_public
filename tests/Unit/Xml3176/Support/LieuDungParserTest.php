<?php

namespace Tests\Unit\Xml3176\Support;

use App\Services\Xml3176\Support\LieuDungParser;
use Tests\TestCase;

/**
 * Chuan du lieu dau ra (QD 130, sua doi theo QD 4750) dinh nghia BON dang lieu dung
 * hop le, khong phai mot. Ban parser dau tien chi biet mot dang "so * so * so", va do
 * la dang chuan KHONG he neu ra - no bac bo ca ba vi du in trong chuan, lam
 * XML2_LIEU_DUNG_INVALID_FORMAT no 15.585 lan tren 100% dong thuoc cua du lieu that.
 */
class LieuDungParserTest extends TestCase
{
    // ---- Dang mot: ngoai tru / noi tru, ba phan ngan boi '*' ----

    /** @test */
    public function dang_ba_phan_khong_don_vi_o_tung_phan()
    {
        $r = LieuDungParser::parse('1 * 2 * 5 [Viên/ngày]');
        $this->assertTrue($r['hop_le']);
        $this->assertSame('ba_phan', $r['dang']);
        $this->assertEquals(1.0, $r['sl_lan']);
        $this->assertEquals(2.0, $r['lan_ngay']);
        $this->assertEquals(5, $r['so_ngay']);
        $this->assertEquals('Viên', $r['don_vi']);
        $this->assertEquals(10.0, $r['tong_luong']);
        $this->assertTrue($r['co_tong_luong']);
    }

    /** @test */
    public function dang_ba_phan_theo_dung_VI_DU_NGUYEN_VAN_CUA_CHUAN()
    {
        // Chuan viet: "2 viên/lần * 2 lần/ngày * 5 ngày [4 viên/ngày]".
        // Moi so DEU kem don vi - ban parser cu doi so tran nen truot ngay ky tu dau.
        $r = LieuDungParser::parse('2 viên/lần * 2 lần/ngày * 5 ngày [4 viên/ngày]');
        $this->assertTrue($r['hop_le']);
        $this->assertEquals(2.0, $r['sl_lan']);
        $this->assertEquals(2.0, $r['lan_ngay']);
        $this->assertEquals(5, $r['so_ngay']);
        $this->assertEquals(20.0, $r['tong_luong']);
        $this->assertEquals('viên', $r['don_vi']);
        $this->assertEquals(4.0, $r['tong_ngay']);
    }

    /** @test */
    public function dang_ba_phan_thuoc_thang_yhct()
    {
        // Chuan: "12g * 1 thang * 5 ngày".
        $r = LieuDungParser::parse('12g * 1 thang * 5 ngày');
        $this->assertTrue($r['hop_le']);
        $this->assertEquals(12.0, $r['sl_lan']);
        $this->assertEquals(1.0, $r['lan_ngay']);
        $this->assertEquals(5, $r['so_ngay']);
        $this->assertEquals(60.0, $r['tong_luong']);
    }

    /** @test */
    public function chap_nhan_thap_phan_dau_phay_va_khong_co_ngoac()
    {
        $r = LieuDungParser::parse('1,5*2*3');
        $this->assertTrue($r['hop_le']);
        $this->assertEquals(9.0, $r['tong_luong']);
        $this->assertSame('', $r['don_vi']);
    }

    // ---- Dang hai: thuoc dung ngoai, hai phan ----

    /** @test */
    public function dang_hai_phan_la_HOP_LE_theo_chuan()
    {
        // Chuan, bo sung luu y: "Doi voi cac loai thuoc dung ngoai nhu nho giot, boi...
        // khong xac dinh duoc chinh xac lieu luong thi chi ghi so lan su dung trong ngay
        // va so ngay su dung: So lan dung trong ngay * so ngay su dung".
        // Ban test cu khang dinh '1*2' PHAI SAI - trai voi chuan, nen da sua.
        $r = LieuDungParser::parse('1*2');
        $this->assertTrue($r['hop_le']);
        $this->assertSame('hai_phan', $r['dang']);
        $this->assertEquals(2, $r['so_ngay']);
    }

    /** @test */
    public function dang_hai_phan_nhu_du_lieu_that_cua_co_so()
    {
        // Hinh dang chiem 97,6% du lieu that: loi dan van xuoi * so ngay [tong/ngay].
        $r = LieuDungParser::parse('Ngày uống 1 viên buổi sáng * 60 ngày [1,0 Viên/ngày]');
        $this->assertTrue($r['hop_le']);
        $this->assertSame('hai_phan', $r['dang']);
        $this->assertEquals(60, $r['so_ngay']);
        $this->assertEquals(1.0, $r['tong_ngay']);
        $this->assertEquals('Viên', $r['don_vi']);
        // KHONG suy tong luong tu [tong/ngay] x so ngay: do tren du lieu that, 90% khac
        // biet sinh ra theo cach do la ao (lech don vi hoac lam tron trong ngoac).
        $this->assertFalse($r['co_tong_luong']);
        $this->assertEquals(0.0, $r['tong_luong']);
    }

    /** @test */
    public function dang_hai_phan_khong_co_ngoac_thi_khong_suy_ra_duoc_tong_luong()
    {
        // Thieu can cu thi im lang: quy tac doi chieu so luong khong duoc chay.
        $r = LieuDungParser::parse('Pha thuốc * 1 ngày');
        $this->assertTrue($r['hop_le']);
        $this->assertEquals(1, $r['so_ngay']);
        $this->assertFalse($r['co_tong_luong']);
        $this->assertEquals(0.0, $r['tong_luong']);
    }

    // ---- Dang ba: lieu thay doi trong ngay, khong co dau '*' ----

    /** @test */
    public function dang_theo_buoi_la_HOP_LE_theo_chuan()
    {
        // Chuan: "Sáng: 3 viên, Chiều: 2 viên, Tối: 1 viên [6 viên/ngày]".
        $r = LieuDungParser::parse('Sáng: 3 viên, Chiều: 2 viên, Tối: 1 viên [6 viên/ngày]');
        $this->assertTrue($r['hop_le']);
        $this->assertSame('theo_buoi', $r['dang']);
        $this->assertEquals(6.0, $r['tong_ngay']);
        $this->assertEquals('viên', $r['don_vi']);
    }

    /** @test */
    public function dang_theo_buoi_khong_biet_so_ngay_nen_khong_suy_ra_tong_luong()
    {
        // Dang nay khong ma hoa so ngay, nen khong the doi chieu voi so luong thanh toan.
        $r = LieuDungParser::parse('Sáng: 0, Trưa: 0, Chiều: 0, Tối: 0');
        $this->assertTrue($r['hop_le']);
        $this->assertSame('theo_buoi', $r['dang']);
        $this->assertSame(0, $r['so_ngay']);
        $this->assertFalse($r['co_tong_luong']);
    }

    // ---- Khong khop dang nao ----

    /** @test */
    public function khong_khop_dang_nao_thi_bao_sai()
    {
        foreach (['1 viên x 2 lần/ngày x 5 ngày', 'abc', '1**2*3', '1*2*3*4', 'Pha thuốc * mỗi ngày'] as $x) {
            $this->assertFalse(LieuDungParser::parse($x)['hop_le'], "phai sai: $x");
        }
    }

    /** @test */
    public function rong_va_null_tra_khong_hop_le()
    {
        $this->assertFalse(LieuDungParser::parse('')['hop_le']);
        $this->assertFalse(LieuDungParser::parse(null)['hop_le']);
        $this->assertEquals(0.0, LieuDungParser::parse(null)['tong_luong']);
        $this->assertSame('', LieuDungParser::parse(null)['dang']);
    }
}
