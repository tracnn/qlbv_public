<?php

namespace Tests\Unit\Xml3176\VatPhamKemDvkt;

use Tests\TestCase;

class QuanTriViewTest extends TestCase
{
    private function nguon(): string
    {
        return file_get_contents(resource_path('views/category/bhyt/vat_pham_kem_dvkt.blade.php'));
    }

    /** @test */
    public function blade_bien_dich_ra_php_hop_le()
    {
        $compiled = app('blade.compiler')->compileString($this->nguon());
        try {
            token_get_all($compiled, TOKEN_PARSE);
        } catch (\ParseError $e) {
            $this->fail('Blade khong bien dich duoc: ' . $e->getMessage());
        }
        $this->assertTrue(true);
    }

    /** @test */
    public function view_goi_du_route_cua_man()
    {
        $src = $this->nguon();
        foreach (['datatable', 'ung-vien-datatable', 'hieu-luc', 'ghi-chu', 'xoa', 'kiem-khop', 'tim-vat-pham',
                  'tim-dvkt', 'them', 'ung-vien-them', 'hoc-lai', 'trang-thai', 'xuat', 'nhap', 'nhap-loi'] as $r) {
            $this->assertContains("category-bhyt.vat-pham-kem-dvkt.$r", $src, "View chua dung route $r");
        }
        $this->assertContains('Cần ghi lý do khi tắt', $src);
        $this->assertContains('csrf_token()', $src);
    }

    /** @test */
    public function view_khong_nhung_du_lieu_vao_thuoc_tinh_bang_noi_chuoi()
    {
        $src = $this->nguon();
        $this->assertContains('data-idx', $src);
        $this->assertNotContains("value='' + ", $src);
        $this->assertContains('.val(JSON.stringify(', $src);
    }

    /** @test */
    public function view_chong_xss_toastr_race_kiem_khop_va_doc_thuoc_tinh_nguyen_van()
    {
        $src = $this->nguon();
        $this->assertContains('escapeHtml = true', $src);
        $this->assertContains("attr('data-nguon')", $src);
        $this->assertContains('maGui', $src);
        $this->assertNotContains(".data('ghi-chu')", $src);
    }
}
