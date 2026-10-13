<?php

namespace Tests\Unit\Xml3176\VatPhamKemDvkt;

use App\Services\Xml3176\Support\VatPhamKemDvktKey;
use Tests\TestCase;

class VatPhamKemDvktKeyTest extends TestCase
{
    /** @test */
    public function ma_vtyt_day_du_khoa_theo_ma_bo_ten()
    {
        $this->assertSame('N06.02.020.0549.274.0004|', VatPhamKemDvktKey::khoa('VTYT', ' N06.02.020.0549.274.0004 ', 'Giá đỡ Orsiro'));
    }

    /** @test */
    public function ma_vtyt_nhom_chung_khoa_theo_ma_va_ten_chuan_hoa()
    {
        $a = VatPhamKemDvktKey::khoa('VTYT', 'N04.04.010', 'Ống dẫn lưu  đuôi lợn');
        $b = VatPhamKemDvktKey::khoa('VTYT', 'N04.04.010', ' ỐNG DẪN LƯU ĐUÔI LỢN ');
        $c = VatPhamKemDvktKey::khoa('VTYT', 'N04.04.010', 'Catheter chẩn đoán 4 cực');

        $this->assertSame('N04.04.010|ống dẫn lưu đuôi lợn', $a);
        $this->assertSame($a, $b);
        $this->assertNotSame($a, $c);
    }

    /** @test */
    public function thuoc_luon_khoa_theo_ma()
    {
        $this->assertSame('40.644|', VatPhamKemDvktKey::khoa('THUOC', '40.644', 'Omnipaque'));
        $this->assertSame('', VatPhamKemDvktKey::tenKhop('THUOC', '40.644', 'Omnipaque'));
    }

    /** @test */
    public function ten_khop_cat_191_ky_tu()
    {
        $ten = str_repeat('á', 300);
        $this->assertSame(191, mb_strlen(VatPhamKemDvktKey::tenKhop('VTYT', 'N08.00.150', $ten), 'UTF-8'));
    }

    /** @test */
    public function chuan_hoa_ma_dvkt_bo_hau_to_gt()
    {
        $this->assertSame('12.0448.1187', VatPhamKemDvktKey::chuanHoaMaDvkt(' 12.0448.1187_GT '));
        $this->assertSame('12.0448.1187', VatPhamKemDvktKey::chuanHoaMaDvkt('12.0448.1187_gt'));
        $this->assertSame('12.0448.1187', VatPhamKemDvktKey::chuanHoaMaDvkt('12.0448.1187'));
        $this->assertSame('', VatPhamKemDvktKey::chuanHoaMaDvkt(null));
    }

    /** @test */
    public function nhan_dien_ma_nhom_chung()
    {
        $this->assertTrue(VatPhamKemDvktKey::laMaNhomChung('N08.00.150'));
        $this->assertFalse(VatPhamKemDvktKey::laMaNhomChung('N08.00.150.1234.000.0001'));
        $this->assertFalse(VatPhamKemDvktKey::laMaNhomChung(''));
    }
}
