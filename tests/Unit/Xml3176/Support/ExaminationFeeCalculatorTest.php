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

    /** @test */
    public function vi_pham_30_false_khi_lan_2_da_giam()
    {
        // 100k full + 30k (đúng 30%) -> hợp lệ
        $this->assertFalse(ExaminationFeeCalculator::viPham30([100000, 30000], 0.30));
    }

    /** @test */
    public function vi_pham_30_true_khi_hai_dong_full_gia()
    {
        // 2 dòng đều full giá -> lần khám thứ 2 chưa giảm (trần 2x KHÔNG bắt được ca này)
        $this->assertTrue(ExaminationFeeCalculator::viPham30([100000, 100000], 0.30));
    }

    /** @test */
    public function vi_pham_30_true_khi_lan_2_giam_chua_du()
    {
        // 100k + 50k (50% > 30%) -> vi phạm
        $this->assertTrue(ExaminationFeeCalculator::viPham30([100000, 50000], 0.30));
    }

    /** @test */
    public function vi_pham_30_false_khi_duoi_2_dong()
    {
        $this->assertFalse(ExaminationFeeCalculator::viPham30([100000], 0.30));
        $this->assertFalse(ExaminationFeeCalculator::viPham30([], 0.30));
    }

    /** @test */
    public function vi_pham_30_false_khi_thieu_can_cu()
    {
        $this->assertFalse(ExaminationFeeCalculator::viPham30([0, 0], 0.30));
    }

    /** @test */
    public function vi_pham_30_false_khi_nhieu_dong_deu_giam_dung()
    {
        // 100k + 30k*3 -> phân bổ đúng (vượt trần hay không là việc của rule trần)
        $this->assertFalse(ExaminationFeeCalculator::viPham30([100000, 30000, 30000, 30000], 0.30));
    }

    /** @test */
    public function so_dong_chua_giam_dem_dung()
    {
        $this->assertSame(2, ExaminationFeeCalculator::soDongChuaGiam([100000, 100000, 30000], 0.30));
        $this->assertSame(1, ExaminationFeeCalculator::soDongChuaGiam([100000, 30000], 0.30));
    }
}
