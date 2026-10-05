<?php

namespace Tests\Unit\ExcelLuong;

use App\Services\ExcelLuong\KieuO;
use Tests\TestCase;

/**
 * Kieu o phai GIONG HET Laravel Excel (DefaultValueBinder) de tep ghi luong khong khac tep cu,
 * tru mot khac biet co y: chuoi bat dau '=' ghi thanh CHU (cu thanh cong thuc).
 */
class KieuOTest extends TestCase
{
    /** @test */
    public function chuoi_so_thanh_so()
    {
        $this->assertSame([202610050800, true], KieuO::chuyen('202610050800', KieuO::TU_DONG));
        $this->assertSame([1.5, true], KieuO::chuyen('1.5', KieuO::TU_DONG));
        $this->assertSame([7, true], KieuO::chuyen(7, KieuO::TU_DONG));
    }

    /** @test */
    public function so_0_dau_va_so_qua_lon_giu_chu()
    {
        $this->assertSame(['000007230917', false], KieuO::chuyen('000007230917', KieuO::TU_DONG));
        $this->assertSame(['99999999999999999999', false], KieuO::chuyen('99999999999999999999', KieuO::TU_DONG));
    }

    /** @test */
    public function null_va_chuoi_rong_thanh_o_trong()
    {
        $this->assertSame([null, false], KieuO::chuyen(null, KieuO::TU_DONG));
        $this->assertSame([null, false], KieuO::chuyen('', KieuO::TU_DONG));
        $this->assertSame([null, false], KieuO::chuyen(null, KieuO::CHU));
    }

    /** Khac biet co y: ban cu bien thanh cong thuc Excel - chen cong thuc tu du lieu. */
    /** @test */
    public function chuoi_bat_dau_bang_dau_bang_ghi_chu()
    {
        $this->assertSame(['=SUM(A1)', false], KieuO::chuyen('=SUM(A1)', KieuO::TU_DONG));
    }

    /** @test */
    public function ma_loi_excel_va_chu_thuong_giu_chu()
    {
        $this->assertSame(['#N/A', false], KieuO::chuyen('#N/A', KieuO::TU_DONG));
        $this->assertSame(['Khoa Noi', false], KieuO::chuyen('Khoa Noi', KieuO::TU_DONG));
    }

    /** Hai sheet danh muc dung StringValueBinder: moi o la chu. */
    /** @test */
    public function kieu_chu_moi_o_la_chu()
    {
        $this->assertSame(['123', false], KieuO::chuyen('123', KieuO::CHU));
        $this->assertSame(['45', false], KieuO::chuyen(45, KieuO::CHU));
    }
}
