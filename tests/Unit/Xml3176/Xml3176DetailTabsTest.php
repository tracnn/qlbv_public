<?php

namespace Tests\Unit\Xml3176;

use Tests\TestCase;
use Illuminate\Support\Collection;
use App\Services\Xml3176\Xml3176DetailTabs;

class Xml3176DetailTabsTest extends TestCase
{
    /** @test */
    public function khoa_nhom_cat_dung_so_ky_tu_khu_trung_lap_va_sap_tang()
    {
        $kq = Xml3176DetailTabs::khoaNhom(
            ['202607031200', '202607011000', '202607031800', '202607021500'],
            8
        );

        $this->assertEquals(['20260701', '20260702', '20260703'], $kq);
    }

    /** @test */
    public function khoa_nhom_giu_nguyen_gia_tri_khi_cat_bang_khong()
    {
        // XML3 nhom theo ma_nhom, khong phai theo ngay -> khong cat.
        // ma_nhom la ma so 1..14 nen sort() cua PHP so theo SO: 1, 2, 10.
        // Day la thu tu nguoi dung can - nhom 2 truoc nhom 10.
        $kq = Xml3176DetailTabs::khoaNhom(['2', '1', '2', '10'], 0);

        $this->assertEquals(['1', '2', '10'], $kq);
    }

    /** @test */
    public function khoa_nhom_loai_gia_tri_rong_va_null()
    {
        $kq = Xml3176DetailTabs::khoaNhom(['20260701', null, '', '20260702', '   '], 8);

        $this->assertEquals(['20260701', '20260702'], $kq);
    }

    /** @test */
    public function khoa_nhom_nhan_collection_va_danh_so_lai_tu_khong()
    {
        $kq = Xml3176DetailTabs::khoaNhom(new Collection(['20260702', '20260701']), 8);

        $this->assertEquals([0, 1], array_keys($kq));
    }

    /** @test */
    public function khoa_nhom_tra_mang_rong_khi_khong_co_gia_tri()
    {
        $this->assertEquals([], Xml3176DetailTabs::khoaNhom([], 8));
    }

    /** @test */
    public function dang_ky_phu_dung_bon_bang_nhieu_dong()
    {
        $this->assertEquals(
            ['XML2', 'XML3', 'XML4', 'XML5'],
            array_keys(Xml3176DetailTabs::BANG_NHIEU_DONG)
        );

        // XML3 nhom theo ma_nhom, cat = 0. Day la khac biet de bi lam sai nhat.
        $this->assertEquals('ma_nhom', Xml3176DetailTabs::BANG_NHIEU_DONG['XML3']['cot_nhom']);
        $this->assertEquals(0, Xml3176DetailTabs::BANG_NHIEU_DONG['XML3']['cat']);

        foreach (['XML2' => 'ngay_yl', 'XML4' => 'ngay_kq', 'XML5' => 'thoi_diem_dbls'] as $xml => $cot) {
            $this->assertEquals($cot, Xml3176DetailTabs::BANG_NHIEU_DONG[$xml]['cot_nhom']);
            $this->assertEquals(8, Xml3176DetailTabs::BANG_NHIEU_DONG[$xml]['cat']);
        }
    }

    /** @test */
    public function la_bang_nhieu_dong_tu_choi_gia_tri_ngoai_danh_sach()
    {
        $this->assertTrue(Xml3176DetailTabs::laBangNhieuDong('XML2'));
        $this->assertFalse(Xml3176DetailTabs::laBangNhieuDong('XML7'));
        $this->assertFalse(Xml3176DetailTabs::laBangNhieuDong('../../etc'));
        $this->assertFalse(Xml3176DetailTabs::laBangNhieuDong(''));
    }

    /** @test */
    public function cau_hinh_nem_404_khi_xml_ngoai_danh_sach_trang()
    {
        $this->expectException(\Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class);

        Xml3176DetailTabs::cauHinh('XML999');
    }
}
