<?php

namespace Tests\Unit\Xml3176\TepXuat;

use Tests\TestCase;

class GiaoDienTepXuatTest extends TestCase
{
    private function blade()
    {
        return file_get_contents(resource_path('views/bhyt/xml3176/index.blade.php'));
    }

    /** @test */
    public function nut_xuat_loi_khong_con_tai_truc_tiep()
    {
        $b = $this->blade();

        $this->assertNotContains('export-xml3176-xml-errors', $b,
            'Tai truc tiep tra 504 tren prod voi ngay lon');
        $this->assertContains('bhyt.xml3176.tep-xuat.tao', $b);
    }

    /** @test */
    public function co_muc_tep_xuat_cua_toi_goi_danh_sach_va_tai()
    {
        $b = $this->blade();

        $this->assertContains('id="tep-xuat-cua-toi-btn"', $b);
        $this->assertContains('id="tepXuatModal"', $b);
        $this->assertContains('bhyt.xml3176.tep-xuat.danh-sach', $b);
        $this->assertContains('bhyt.xml3176.tep-xuat.tai', $b);
    }

    /** @test */
    public function chi_hoi_lai_may_chu_khi_con_yeu_cau_dang_chay()
    {
        $b = $this->blade();

        $this->assertContains('15000', $b, 'Chu ky hoi lai 15 giay');
        $this->assertContains('conDangChay', $b, 'Chi hen gio khi con yeu cau cho/dang_tao');
    }

    /** @test */
    public function hoi_lai_that_bai_thi_hen_gio_thu_lai_khong_dung_han()
    {
        $b = $this->blade();
        $vt = strpos($b, 'function xml3176TaiDanhSachTepXuat');
        $het = strpos($b, 'function xml3176VeBangTepXuat', $vt);
        $this->assertNotFalse($vt);
        $this->assertNotFalse($het);
        $than = substr($b, $vt, $het - $vt);
        $this->assertContains('.fail(', $than);
        $this->assertContains('setTimeout', $than);
        $this->assertContains('30000', $than);
    }

    /** @test */
    public function nut_xuat_bi_khoa_trong_luc_cho_may_chu_tra_loi()
    {
        $b = $this->blade();
        $vt = strpos($b, "\$('#export_xml3176_xml_error').click");
        $this->assertNotFalse($vt);
        $than = substr($b, $vt, 1500);
        $this->assertContains("prop('disabled', true)", $than);
        $this->assertContains("prop('disabled', false)", $than);
    }

    /** @test */
    public function noi_dung_tu_may_chu_duoc_thoat_html()
    {
        // Tom tat bo loc chua gia tri nguoi dung go (ma ho so...). Chen bang .text() chu
        // khong ghep chuoi HTML.
        $b = $this->blade();
        $vt = strpos($b, 'function xml3176VeBangTepXuat');
        $this->assertNotFalse($vt);
        // Cat o ham ke tiep: ngay sau do da co san .html( cua ma cu, khong thuoc ham nay.
        $het = strpos($b, 'function applySelectedCheckboxes', $vt);
        $this->assertNotFalse($het);
        $than = substr($b, $vt, $het - $vt);
        $this->assertContains('.text(', $than);
        $this->assertNotContains('.html(', $than);
    }
}
