<?php

namespace Tests\Unit\OrderCheck;

use Tests\TestCase;
use App\Services\OrderCheck\Support\NgayHieuLuc;

class NgayHieuLucTest extends TestCase
{
    /** @test */
    public function doc_duoc_serial_excel()
    {
        // CatalogImportService ghi tho gia tri o Excel vao cot varchar, khong chuan hoa.
        // O ngay trong file BHXH rat co the ve dang serial.
        $this->assertSame(20240101, NgayHieuLuc::phanTich(45292));
        $this->assertSame(20240101, NgayHieuLuc::phanTich('45292'));
        $this->assertSame(20240101, NgayHieuLuc::phanTich(45292.0));
    }

    /** @test */
    public function doc_duoc_cac_dang_chuoi()
    {
        $this->assertSame(20240101, NgayHieuLuc::phanTich('20240101'));
        $this->assertSame(20240101, NgayHieuLuc::phanTich('01/01/2024'));
        $this->assertSame(20240101, NgayHieuLuc::phanTich('2024-01-01'));
        $this->assertSame(20240101, NgayHieuLuc::phanTich('01-01-2024'));
        $this->assertSame(20240315, NgayHieuLuc::phanTich('15/03/2024'));
        $this->assertSame(20240315, NgayHieuLuc::phanTich(' 15/3/2024 '));
    }

    /** @test */
    public function gia_tri_khong_hieu_thi_tra_null()
    {
        $this->assertNull(NgayHieuLuc::phanTich(''));
        $this->assertNull(NgayHieuLuc::phanTich(null));
        $this->assertNull(NgayHieuLuc::phanTich('abc'));
        $this->assertNull(NgayHieuLuc::phanTich(0));
        $this->assertNull(NgayHieuLuc::phanTich('32/13/2024'));
        $this->assertNull(NgayHieuLuc::phanTich('20241332'));
    }

    /** @test */
    public function trong_khoang_thi_con_hieu_luc()
    {
        $this->assertTrue(NgayHieuLuc::conHieuLuc('20240101', '20241231', 20240601));
        $this->assertTrue(NgayHieuLuc::conHieuLuc('20240101', '20241231', 20240101));
        $this->assertTrue(NgayHieuLuc::conHieuLuc('20240101', '20241231', 20241231));
    }

    /** @test */
    public function ngoai_khoang_thi_het_hieu_luc()
    {
        $this->assertFalse(NgayHieuLuc::conHieuLuc('20240101', '20241231', 20231231));
        $this->assertFalse(NgayHieuLuc::conHieuLuc('20240101', '20241231', 20250101));
    }

    /** @test */
    public function ngay_khong_doc_duoc_thi_coi_nhu_con_hieu_luc()
    {
        // Fail-safe: loi chat luong du lieu danh muc khong duoc bien thanh mot tran lu vi
        // pham gia. Tha xet thua mot dong danh muc con hon bao oan mot y lenh dung.
        $this->assertTrue(NgayHieuLuc::conHieuLuc('', '20241231', 20200101));
        $this->assertTrue(NgayHieuLuc::conHieuLuc('20240101', '', 20990101));
        $this->assertTrue(NgayHieuLuc::conHieuLuc('abc', 'xyz', 20240601));
        $this->assertTrue(NgayHieuLuc::conHieuLuc(null, null, 20240601));
    }

    /** @test */
    public function ngay_xet_khong_hop_le_thi_khong_loc()
    {
        $this->assertTrue(NgayHieuLuc::conHieuLuc('20240101', '20241231', null));
        $this->assertTrue(NgayHieuLuc::conHieuLuc('20240101', '20241231', 0));
    }

    /** @test */
    public function doc_duoc_moc_thoi_gian_his()
    {
        $this->assertSame(20260728, NgayHieuLuc::tuMocHis(20260728143015));
        $this->assertSame(20260728, NgayHieuLuc::tuMocHis('20260728143015'));
        $this->assertNull(NgayHieuLuc::tuMocHis(0));
        $this->assertNull(NgayHieuLuc::tuMocHis(null));
        $this->assertNull(NgayHieuLuc::tuMocHis(20260728));
    }

    /** @test */
    public function serial_excel_va_ymd_khong_chong_lan_nhau()
    {
        // Ranh gioi phai tach bach: serial cua nam 2100 van duoi 80.000, con Ymd nho nhat
        // la 19.000.101. Neu chong lan thi mot ngay se bi doc thanh ngay khac hoan toan.
        $this->assertSame(20240101, NgayHieuLuc::phanTich(45292));
        $this->assertSame(19000101, NgayHieuLuc::phanTich('19000101'));
        $this->assertSame(29991231, NgayHieuLuc::phanTich('29991231'));
    }
}
