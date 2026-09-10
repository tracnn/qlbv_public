<?php

namespace Tests\Unit\Xml3176\Support;

use App\Services\Xml3176\Support\MaDvktStructure;
use Tests\TestCase;

class MaDvktStructureTest extends TestCase
{
    /** @test */
    public function hau_to_lay_phan_sau_dau_gach_duoi_cuoi_cung()
    {
        $this->assertSame('', MaDvktStructure::hauTo('02.0261.0319'));
        $this->assertSame('TB', MaDvktStructure::hauTo('02.0261.0319_TB'));
        $this->assertSame('GT', MaDvktStructure::hauTo('02.0261.0319_GT'));
        $this->assertSame('B', MaDvktStructure::hauTo('02.0261.0319_A_B'));
        $this->assertSame('', MaDvktStructure::hauTo(''));
        $this->assertSame('', MaDvktStructure::hauTo(null));
    }

    /** @test */
    public function nhan_dien_dvkt_chi_dinh_nhung_khong_thuc_hien()
    {
        $this->assertTrue(MaDvktStructure::laKhongThucHien('02.0261.0319_TB'));
        $this->assertFalse(MaDvktStructure::laKhongThucHien('02.0261.0319'));
        $this->assertFalse(MaDvktStructure::laKhongThucHien('02.0261.0319_GT'));
    }

    /** @test */
    public function nhan_dien_dvkt_chua_co_gia()
    {
        $this->assertTrue(MaDvktStructure::laChuaCoGia('02.0261.0000'));
        $this->assertFalse(MaDvktStructure::laChuaCoGia('02.0261.0319'));
    }

    /** @test */
    public function chua_co_gia_van_nhan_dien_duoc_khi_co_hau_to()
    {
        // Xet phan than da bo hau to, nen ma vua chua co gia vua khong thuc hien
        // deu nhan dien dung ca hai tinh chat.
        $this->assertTrue(MaDvktStructure::laChuaCoGia('02.0261.0000_TB'));
        $this->assertTrue(MaDvktStructure::laKhongThucHien('02.0261.0000_TB'));
    }

    /** @test */
    public function nhan_dien_va_tach_ma_co_so_van_chuyen()
    {
        $this->assertTrue(MaDvktStructure::laVanChuyen('VC.01234'));
        $this->assertSame('01234', MaDvktStructure::maCoSoVanChuyen('VC.01234'));

        $this->assertFalse(MaDvktStructure::laVanChuyen('02.0261.0319'));
        $this->assertSame('', MaDvktStructure::maCoSoVanChuyen('02.0261.0319'));
    }

    /** @test */
    public function tach_ma_co_so_chuyen_mau_benh_pham()
    {
        $this->assertSame('01234', MaDvktStructure::maCoSoChuyenMau('02.0261.0319.K.01234'));
        $this->assertSame('', MaDvktStructure::maCoSoChuyenMau('02.0261.0319'));
        $this->assertSame('', MaDvktStructure::maCoSoChuyenMau('VC.01234'));
    }

    /** @test */
    public function bo_khoang_trang_thua()
    {
        $this->assertTrue(MaDvktStructure::laKhongThucHien('  02.0261.0319_TB  '));
        $this->assertSame('01234', MaDvktStructure::maCoSoVanChuyen(' VC.01234 '));
    }

    /** @test */
    public function chuoi_rong_khong_lam_no_ham_nao()
    {
        $this->assertFalse(MaDvktStructure::laKhongThucHien(''));
        $this->assertFalse(MaDvktStructure::laChuaCoGia(''));
        $this->assertFalse(MaDvktStructure::laVanChuyen(''));
        $this->assertSame('', MaDvktStructure::maCoSoVanChuyen(''));
        $this->assertSame('', MaDvktStructure::maCoSoChuyenMau(''));
    }
}
