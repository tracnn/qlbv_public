<?php

namespace Tests\Unit\Xml3176;

use Tests\TestCase;

/**
 * Khoa viec thoat HTML cua man danh sach XML3176.
 *
 * Du lieu cac cot (ho_ten, ma_lk, ma_the_bhyt...) doc thang tu tep XML do co so KCB
 * nap len, con thong bao loi ky/gui den tu phan hoi cong BHXH - deu la du lieu ngoai.
 * DataTables 1.10.16 (public/vendor/datatables/js/jquery.dataTables.js) gan thang
 * nTd.innerHTML = gia tri khi cot khong co render, nen mot ho ten dang
 * "<img src=x onerror=...>" se chay ngay khi nguoi van hanh mo man danh sach.
 */
class Xml3176ThoatHtmlTest extends TestCase
{
    private function blade()
    {
        return file_get_contents(resource_path('views/bhyt/xml3176/index.blade.php'));
    }

    private function controller()
    {
        return file_get_contents(app_path('Http/Controllers/BHYT/BHYTXml3176Controller.php'));
    }

    /**
     * Cac cot yajra tra ve duoi dang HTML that su (rawColumns) - doc thang tu ma nguon
     * de danh sach trong test khong lech voi controller.
     */
    private function cotRaw()
    {
        preg_match('/->rawColumns\(\[(.*?)\]\)/s', $this->controller(), $m);
        $this->assertNotEmpty($m, 'Khong doc duoc rawColumns() trong controller');

        preg_match_all("/'([a-z0-9_]+)'/i", $m[1], $c);

        return $c[1];
    }

    /** @test */
    public function moi_cot_hien_gia_tri_tu_csdl_deu_co_render_thoat_html()
    {
        $blade = $this->blade();
        $raw   = $this->cotRaw();

        // Bat tung dong khai cot dang { "data": "x" ... } trong khoi columns.
        preg_match_all('/\{[^{}]*"data"\s*:\s*"([a-z0-9_]+)"[^{}]*\}/i', $blade, $m, PREG_SET_ORDER);
        $this->assertNotEmpty($m, 'Khong doc duoc khai bao cot nao tu blade - regex hong?');

        foreach ($m as $khai) {
            list($nguyenVan, $ten) = $khai;

            if (in_array($ten, $raw)) {
                // Cot raw la HTML that su: boc render.text() se hien ra the <i> tho.
                // Viec thoat cua chung lam o controller (xem test duoi).
                $this->assertNotContains('render', $nguyenVan,
                    "Cot raw '$ten' co render o blade - se hien ra ma HTML tho thay vi bieu tuong");
                continue;
            }

            $this->assertContains('render', $nguyenVan,
                "Cot '$ten' khai khong co render: DataTables gan thang vao innerHTML, "
                    . "mot gia tri chua the <script>/<img onerror> se chay. "
                    . 'Them render: $.fn.dataTable.render.text()');
        }
    }

    /** @test */
    public function cot_checkbox_thoat_ma_lk_truoc_khi_noi_vao_thuoc_tinh_value()
    {
        $this->assertContains(
            "$('<div>').text(row.ma_lk).html()",
            $this->blade(),
            'Cot checkbox noi thang row.ma_lk vao chuoi HTML - phai qua $(\'<div>\').text(x).html()'
        );
    }

    /** @test */
    public function nut_xoa_khong_noi_ma_lk_vao_onclick()
    {
        // onclick="deleteXML('...')" dat ma_lk vao trong mot chuoi JS: mot ma_lk chua
        // dau nhay se thoat khoi chuoi do va chay ma tuy y.
        $this->assertNotContains('onclick="deleteXML(', $this->controller(),
            'Nut xoa van noi ma_lk vao onclick - dung data-ma-lk voi e() thay the');

        $this->assertContains("data-ma-lk=\"' . e(\$result->ma_lk) . '\"", $this->controller());
        $this->assertContains("attr('data-ma-lk')", $this->blade(),
            'Blade khong con trinh xu ly doc data-ma-lk - nut xoa se khong hoat dong');
    }

    /** @test */
    public function cac_cot_raw_thoat_gia_tri_ngoai_truoc_khi_dung_lam_tooltip()
    {
        $ma = $this->controller();

        // exported_at va submitted_at: thong bao loi xuat/gui nam trong title="..."
        $this->assertSame(2, substr_count($ma, '$tooltip = e($tooltip);'),
            'Tooltip cot exported_at/submitted_at chua qua e() - thong bao loi tu cong '
                . 'BHXH co the thoat khoi thuoc tinh title va chay ma');

        // is_signed: sign_method va signed_error
        $this->assertContains('e($result->Xml3176Information->sign_method)', $ma);
        $this->assertContains('e($result->Xml3176Information->signed_error)', $ma);
    }
}
