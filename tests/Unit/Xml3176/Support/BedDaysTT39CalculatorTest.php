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
    public function tu_4h_den_duoi_24h_qua_nua_dem_van_tinh_1_ngay_ke_ca_dac_biet()
    {
        // 000007305574: vào 23/09 16:20, ra 24/09 04:50 (12,5h), tử vong. TT39: 4h–<24h tính
        // 1 ngày; +1 ngày đặc biệt chỉ áp cho lưu trú từ 24h. Bản cũ trả 2 -> báo thiếu nhầm.
        $this->assertSame(1, BedDaysTT39Calculator::expected(1, 12.5, true));
        $this->assertSame(1, BedDaysTT39Calculator::expected(1, 12.5, false));
        $this->assertSame(1, BedDaysTT39Calculator::expected(1, 23.9, true));
    }

    /** @test */
    public function tu_24h_tro_len_ap_quy_tac_nhieu_ngay()
    {
        $this->assertSame(2, BedDaysTT39Calculator::expected(1, 24.0, true));
        $this->assertSame(1, BedDaysTT39Calculator::expected(1, 24.0, false));
    }

    /** @test */
    public function ho_so_duoi_24h_tu_vong_khai_1_ngay_khong_bi_bao_thieu()
    {
        $this->assertFalse(BedDaysTT39Calculator::isBelow(1.0, BedDaysTT39Calculator::expected(1, 12.5, true), 0.5));
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

    // ---- Trường hợp đặc biệt: CHỈ CẦN MỘT trong hai trường thuộc diện đặc biệt ----

    /** @test */
    public function dac_biet_khi_ket_qua_dieu_tri_dac_biet_du_loai_ra_vien_thuong()
    {
        // Kết quả 3 + loại ra viện 5. Luật cũ viết || nên coi là hồ sơ thường -> báo nhầm.
        $this->assertTrue(BedDaysTT39Calculator::laDacBiet(3, 5, [3, 4, 5, 6], [2, 3, 4]));
    }

    /** @test */
    public function dac_biet_khi_chuyen_vien_du_ket_qua_thuong()
    {
        $this->assertTrue(BedDaysTT39Calculator::laDacBiet(2, 2, [3, 4, 5, 6], [2, 3, 4]));
    }

    /** @test */
    public function khong_dac_biet_khi_ca_hai_deu_thuong()
    {
        $this->assertFalse(BedDaysTT39Calculator::laDacBiet(2, 1, [3, 4, 5, 6], [2, 3, 4]));
    }

    // ---- Thừa ngày giường, lưu trú nhiều ngày. Số liệu lấy từ hồ sơ thật đã đối chiếu. ----

    /** @test */
    public function thua_1_ngay_khi_gio_le_tren_4h_ho_so_bhxh_da_tru()
    {
        // 000007093453: vào 24/08 02:01, ra 01/09 11:57 (lẻ 9,93h), ra viện thường, khai 9.
        // BHXH trừ đúng 1 ngày. Luật cũ bỏ sót vì chỉ báo khi giờ lẻ < 4.
        $kq = BedDaysTT39Calculator::thua('202608240201', '202609011157', false, 9.0);

        $this->assertNotNull($kq);
        $this->assertSame(8, $kq['expected']);
        $this->assertEquals(1.0, $kq['excess']);
    }

    /** @test */
    public function khong_thua_khi_khai_dung_so_ngay_duong_lich()
    {
        $this->assertNull(BedDaysTT39Calculator::thua('202608240201', '202609011157', false, 8.0));
    }

    /** @test */
    public function khong_thua_khi_chuyen_vien_duoc_cong_1_ngay()
    {
        // 000006913722: 36 ngày dương lịch, chuyển viện -> đúng 37, khai 37. Luật cũ báo nhầm.
        $this->assertNull(BedDaysTT39Calculator::thua('202607301317', '202609041700', true, 37.0));
    }

    /** @test */
    public function khong_thua_khi_ket_qua_dac_biet_duoc_cong_1_ngay()
    {
        // 000007170492: 2 ngày dương lịch, kết quả điều trị 3 -> đúng 3, khai 3. Luật cũ báo nhầm.
        $this->assertNull(BedDaysTT39Calculator::thua('202609061242', '202609081457', true, 3.0));
    }

    /** @test */
    public function thua_khi_gio_le_duoi_4h()
    {
        // 3 ngày dương lịch, lẻ 1,5h, ra viện thường, khai 4 -> thừa 1 (luật cũ cũng bắt ca này).
        $kq = BedDaysTT39Calculator::thua('202609031210', '202609061342', false, 4.0);

        $this->assertNotNull($kq);
        $this->assertSame(3, $kq['expected']);
        $this->assertEquals(1.0, $kq['excess']);
    }

    /** @test */
    public function thua_nua_ngay_cung_bao()
    {
        $kq = BedDaysTT39Calculator::thua('202608240201', '202609011157', false, 8.5);

        $this->assertNotNull($kq);
        $this->assertEquals(0.5, $kq['excess']);
    }

    /** @test */
    public function khong_xet_luu_tru_tu_24h_tro_xuong()
    {
        // Lưu trú <= 24h do hai nhánh riêng (SHORT_INPATIENT_STAY, EXCESS_BED_DAYS) lo.
        $this->assertNull(BedDaysTT39Calculator::thua('202609010800', '202609011800', false, 2.0));
        $this->assertNull(BedDaysTT39Calculator::thua('202609012000', '202609021900', false, 2.0));
    }

    /** @test */
    public function khong_xet_khi_ngay_khong_hop_le()
    {
        $this->assertNull(BedDaysTT39Calculator::thua(null, '202609011157', false, 9.0));
        $this->assertNull(BedDaysTT39Calculator::thua('abc', '202609011157', false, 9.0));
        $this->assertNull(BedDaysTT39Calculator::thua('202609011157', '202608240201', false, 9.0));
    }
}
