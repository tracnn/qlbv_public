<?php

namespace Tests\Unit\Xml3176\Support;

use App\Services\Xml3176\Support\MucHuongCalculator;
use Tests\TestCase;

class MucHuongCalculatorTest extends TestCase
{
    private $map = ['1' => 100, '2' => 100, '3' => 95, '4' => 80, '5' => 100];

    /** @test */
    public function quyen_loi_char_lay_ky_tu_vi_tri_3()
    {
        $this->assertSame('4', MucHuongCalculator::quyenLoiChar('DN4010012345'));
        $this->assertSame('2', MucHuongCalculator::quyenLoiChar('DT2150067890'));
    }

    /** @test */
    public function quyen_loi_char_danh_sach_cung_quyen_loi()
    {
        // Thẻ cũ;mới cùng ký tự quyền lợi -> trả ký tự đó
        $this->assertSame('4', MucHuongCalculator::quyenLoiChar('DN4010012345;HC4010098765'));
    }

    /** @test */
    public function quyen_loi_char_danh_sach_khac_quyen_loi_tra_null()
    {
        $this->assertNull(MucHuongCalculator::quyenLoiChar('DN4010012345;HC2010098765'));
    }

    /** @test */
    public function quyen_loi_char_ngan_hoac_rong_tra_null()
    {
        $this->assertNull(MucHuongCalculator::quyenLoiChar('DN'));
        $this->assertNull(MucHuongCalculator::quyenLoiChar(''));
        $this->assertNull(MucHuongCalculator::quyenLoiChar(null));
    }

    /** @test */
    public function entitlement_tra_theo_map()
    {
        $this->assertSame(80, MucHuongCalculator::entitlement('4', $this->map));
        $this->assertSame(95, MucHuongCalculator::entitlement('3', $this->map));
        $this->assertSame(100, MucHuongCalculator::entitlement('1', $this->map));
        $this->assertNull(MucHuongCalculator::entitlement('9', $this->map));
        $this->assertNull(MucHuongCalculator::entitlement(null, $this->map));
    }

    /** @test */
    public function tran_dung_tuyen_chi_phi_tren_nguong_tra_entitlement()
    {
        // LCS 2.530.000, ngưỡng 15% = 379.500; chi phí 500.000 >= ngưỡng -> 80
        $this->assertSame(80, MucHuongCalculator::tranDungTuyen(80, 500000, 2530000, 0.15));
    }

    /** @test */
    public function tran_dung_tuyen_chi_phi_duoi_nguong_tra_100()
    {
        // chi phí 100.000 < 379.500 -> miễn, 100%
        $this->assertSame(100, MucHuongCalculator::tranDungTuyen(80, 100000, 2530000, 0.15));
    }

    /** @test */
    public function tran_dung_tuyen_bien_bang_nguong_tra_entitlement()
    {
        // chi phí = đúng 15% * LCS -> áp entitlement (>=)
        $this->assertSame(80, MucHuongCalculator::tranDungTuyen(80, 379500, 2530000, 0.15));
    }

    /** @test */
    public function tran_dung_tuyen_guard_khi_thieu_can_cu()
    {
        $this->assertNull(MucHuongCalculator::tranDungTuyen(80, 500000, null, 0.15));
        $this->assertNull(MucHuongCalculator::tranDungTuyen(80, 500000, 0, 0.15));
        $this->assertNull(MucHuongCalculator::tranDungTuyen(null, 500000, 2530000, 0.15));
    }
}
