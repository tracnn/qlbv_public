<?php

namespace Tests\Unit\Xml3176\Support;

use App\Services\Xml3176\Support\BedDaysTT39Calculator;
use Tests\TestCase;

class BedDaysTT39CalculatorTest extends TestCase
{
    /** @test */
    public function cung_ngay_tren_4h_tinh_1_ngay()
    {
        $this->assertSame(1, BedDaysTT39Calculator::expected(0, 5.0, false));
        $this->assertSame(1, BedDaysTT39Calculator::expected(0, 4.0, false));
    }

    /** @test */
    public function cung_ngay_duoi_4h_tinh_0_ngay()
    {
        $this->assertSame(0, BedDaysTT39Calculator::expected(0, 3.5, false));
    }

    /** @test */
    public function nhieu_ngay_thuong_bang_calendar_days()
    {
        $this->assertSame(8, BedDaysTT39Calculator::expected(8, 201.0, false));
    }

    /** @test */
    public function duoi_4h_qua_nua_dem_van_tinh_0()
    {
        // vào 23:30 ra 00:30 hôm sau: calendarDays=1 nhưng chỉ 1h -> không tính giường
        $this->assertSame(0, BedDaysTT39Calculator::expected(1, 1.0, false));
        $this->assertSame(0, BedDaysTT39Calculator::expected(1, 3.9, true));
    }

    /** @test */
    public function nhieu_ngay_dac_biet_cong_1()
    {
        // tử vong/chuyển viện/nặng xin về -> +1
        $this->assertSame(9, BedDaysTT39Calculator::expected(8, 201.0, true));
    }

    /** @test */
    public function is_below_false_khi_expected_duoi_1()
    {
        $this->assertFalse(BedDaysTT39Calculator::isBelow(0.0, 0, 0.5));
    }

    /** @test */
    public function is_below_false_khi_du_ngay_giuong()
    {
        $this->assertFalse(BedDaysTT39Calculator::isBelow(8.0, 8, 0.5));
        $this->assertFalse(BedDaysTT39Calculator::isBelow(9.0, 8, 0.5));
    }

    /** @test */
    public function is_below_false_khi_thieu_trong_dung_sai()
    {
        // expected 8, khai 7.6 -> thiếu 0.4 < 0.5 -> không cảnh báo
        $this->assertFalse(BedDaysTT39Calculator::isBelow(7.6, 8, 0.5));
    }

    /** @test */
    public function is_below_true_khi_thieu_ngoai_dung_sai()
    {
        // expected 8, khai 7.0 -> thiếu 1.0 > 0.5 -> cảnh báo
        $this->assertTrue(BedDaysTT39Calculator::isBelow(7.0, 8, 0.5));
    }

    /** @test */
    public function is_below_true_khi_tong_bang_0()
    {
        $this->assertTrue(BedDaysTT39Calculator::isBelow(0.0, 3, 0.5));
    }
}
