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
}
