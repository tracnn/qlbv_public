<?php

namespace Tests\Unit\GiaoBan;

use Tests\TestCase;
use App\Services\GiaoBan\MetricSchema;

class ManualInputRuleTest extends TestCase
{
    protected function chiTieu($input)
    {
        return ['code' => 'cg', 'name' => 'Chuyên gia', 'type' => 'manual', 'input' => $input];
    }

    /** @test */
    public function gia_tri_trong_khoang_thi_hop_le()
    {
        $this->assertNull(MetricSchema::kiemGiaTriNhapTay($this->chiTieu(['min' => 0, 'max' => 10]), 5));
    }

    /** @test */
    public function nho_hon_min_bi_chan()
    {
        $this->assertNotNull(MetricSchema::kiemGiaTriNhapTay($this->chiTieu(['min' => 0]), -1));
    }

    /** @test */
    public function lon_hon_max_bi_chan()
    {
        $this->assertNotNull(MetricSchema::kiemGiaTriNhapTay($this->chiTieu(['max' => 10]), 11));
    }

    /** @test */
    public function kieu_int_khong_nhan_so_le()
    {
        $this->assertNotNull(MetricSchema::kiemGiaTriNhapTay($this->chiTieu(['value_type' => 'int']), 1.5));
        $this->assertNull(MetricSchema::kiemGiaTriNhapTay($this->chiTieu(['value_type' => 'int']), 2));
    }

    /** @test */
    public function kieu_decimal_toi_da_2_chu_so_le_theo_cot_decimal_12_2()
    {
        $this->assertNull(MetricSchema::kiemGiaTriNhapTay($this->chiTieu(['value_type' => 'decimal']), 1.25));
        $this->assertNotNull(MetricSchema::kiemGiaTriNhapTay($this->chiTieu(['value_type' => 'decimal']), 1.234));
    }

    /** @test */
    public function kieu_percent_gioi_han_0_100()
    {
        $this->assertNotNull(MetricSchema::kiemGiaTriNhapTay($this->chiTieu(['value_type' => 'percent']), 101));
        $this->assertNotNull(MetricSchema::kiemGiaTriNhapTay($this->chiTieu(['value_type' => 'percent']), -1));
    }

    /** @test */
    public function chi_tieu_tu_dong_khong_bi_rang_buoc()
    {
        $m = ['code' => 'bn_cu', 'name' => 'BN cũ', 'type' => 'census_from'];
        $this->assertNull(MetricSchema::kiemGiaTriNhapTay($m, -999));
    }

    /** @test */
    public function gia_tri_null_la_xoa_o_nen_hop_le()
    {
        $this->assertNull(MetricSchema::kiemGiaTriNhapTay($this->chiTieu(['min' => 5]), null));
    }
}
