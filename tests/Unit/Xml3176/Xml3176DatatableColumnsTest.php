<?php

namespace Tests\Unit\Xml3176;

use Tests\TestCase;
use App\Http\Controllers\BHYT\BHYTXml3176Controller;

class Xml3176DatatableColumnsTest extends TestCase
{
    /** @test */
    public function danh_sach_trang_cot_khop_dung_cac_cot_blade_doc()
    {
        $blade = file_get_contents(resource_path('views/bhyt/xml3176/index.blade.php'));

        // Trong blade chi co cac khai bao cot moi dung "data" trong ngoac kep;
        // callback ajax dung `data:` khong ngoac nen khong bi bat nham.
        preg_match_all('/"data"\s*:\s*"([a-z0-9_]+)"/i', $blade, $m);
        $bladeDoc = array_values(array_unique($m[1]));

        $this->assertNotEmpty($bladeDoc, 'Khong doc duoc cot nao tu blade - regex hong?');

        $whitelist = BHYTXml3176Controller::DATATABLE_COLUMNS;

        $thieu = array_diff($bladeDoc, $whitelist);
        $this->assertEmpty(
            $thieu,
            'Blade doc cot khong co trong danh sach trang, cot se trong tren giao dien: '
                . implode(', ', $thieu)
        );

        $thua = array_diff($whitelist, $bladeDoc);
        $this->assertEmpty(
            $thua,
            'Danh sach trang giu cot khong ai doc, payload phinh vo ich: '
                . implode(', ', $thua)
        );
    }

    /** @test */
    public function cot_checkbox_render_tu_ma_lk_nen_ma_lk_phai_co_trong_danh_sach_trang()
    {
        // Cot checkbox khai "data": null va render tu row.ma_lk, nen regex tren
        // khong bat duoc - phai khoa rieng, neu khong checkbox se mat gia tri.
        $this->assertContains('ma_lk', BHYTXml3176Controller::DATATABLE_COLUMNS);
    }
}
