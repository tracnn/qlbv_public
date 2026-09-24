<?php

namespace Tests\Unit\Xml3176\Support;

use App\Services\Xml3176\Support\MaKhoaLienKhoa;
use Tests\TestCase;

/**
 * MA_KHOA lien chuyen khoa = 'K' + cac cap 2 chu so noi lien: K103436 la K10, K34, K36. Tren
 * du lieu that 219/2.345 dong giuong dung dang nay (K3233, K0735, K024849, K103436, K3029).
 */
class MaKhoaLienKhoaTest extends TestCase
{
    /** @test */
    public function mot_khoa()
    {
        $this->assertSame(['K36'], MaKhoaLienKhoa::tach('K36'));
    }

    /** @test */
    public function lien_khoa_hai_va_ba_khoa()
    {
        $this->assertSame(['K32', 'K33'], MaKhoaLienKhoa::tach('K3233'));
        $this->assertSame(['K10', 'K34', 'K36'], MaKhoaLienKhoa::tach('K103436'));
        $this->assertSame(['K02', 'K48', 'K49'], MaKhoaLienKhoa::tach(' K024849 '));
    }

    /** @test */
    public function khong_dung_dang_thi_tra_nguyen_ma()
    {
        // So chu so le hoac co chu: khong doan cach tach, de quy tac dung cach so cu.
        $this->assertSame(['K123'], MaKhoaLienKhoa::tach('K123'));
        $this->assertSame(['K03A'], MaKhoaLienKhoa::tach('K03A'));
        $this->assertSame(['H01'], MaKhoaLienKhoa::tach('H01'));
    }

    /** @test */
    public function rong_thi_mang_rong()
    {
        $this->assertSame([], MaKhoaLienKhoa::tach(''));
        $this->assertSame([], MaKhoaLienKhoa::tach(null));
    }

    /** @test */
    public function la_lien_khoa()
    {
        $this->assertTrue(MaKhoaLienKhoa::laLienKhoa('K103436'));
        $this->assertFalse(MaKhoaLienKhoa::laLienKhoa('K36'));
        $this->assertFalse(MaKhoaLienKhoa::laLienKhoa('K123'));
    }
}
