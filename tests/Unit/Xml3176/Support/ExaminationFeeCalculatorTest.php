<?php

namespace Tests\Unit\Xml3176\Support;

use App\Services\Xml3176\Support\ExaminationFeeCalculator;
use Tests\TestCase;

class ExaminationFeeCalculatorTest extends TestCase
{
    /** @test */
    public function ma_trung_tra_ma_lap_lai()
    {
        $this->assertSame(['02.05'], ExaminationFeeCalculator::maTrung(['02.05', '10.22', '02.05']));
    }

    /** @test */
    public function ma_trung_tra_nhieu_ma_lap()
    {
        $trung = ExaminationFeeCalculator::maTrung(['02.05', '10.22', '02.05', '10.22', '10.23']);
        sort($trung);
        $this->assertSame(['02.05', '10.22'], $trung);
    }

    /** @test */
    public function khong_trung_khi_cac_chuyen_khoa_khac_nhau()
    {
        // Khám nhiều chuyên khoa KHÁC nhau là hợp lệ theo TT39 -> không được coi là trùng
        $this->assertSame([], ExaminationFeeCalculator::maTrung(['02.05', '10.22', '10.23']));
    }

    /** @test */
    public function ma_trung_bo_qua_ma_rong()
    {
        $this->assertSame([], ExaminationFeeCalculator::maTrung(['', null, '   ', '']));
    }

    /** @test */
    public function ma_trung_chuan_hoa_khoang_trang_va_hoa_thuong()
    {
        $this->assertSame(['02.05'], ExaminationFeeCalculator::maTrung([' 02.05 ', '02.05']));
    }

    /** @test */
    public function vuot_tran_false_khi_trong_gioi_han()
    {
        // giá gốc 100k; 1 lần full + 1 lần 30% = 130k <= 200k
        $this->assertFalse(ExaminationFeeCalculator::vuotTran(130000, 100000, 2.0));
        // đúng biên 2 lần giá -> không vượt
        $this->assertFalse(ExaminationFeeCalculator::vuotTran(200000, 100000, 2.0));
    }

    /** @test */
    public function vuot_tran_true_khi_qua_gioi_han()
    {
        // 100k + 30k*4 = 220k > 200k
        $this->assertTrue(ExaminationFeeCalculator::vuotTran(220000, 100000, 2.0));
    }

    /** @test */
    public function vuot_tran_guard_khi_thieu_can_cu()
    {
        $this->assertFalse(ExaminationFeeCalculator::vuotTran(500000, 0, 2.0));
        $this->assertFalse(ExaminationFeeCalculator::vuotTran(500000, -100, 2.0));
    }
}
