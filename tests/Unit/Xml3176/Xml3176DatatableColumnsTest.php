<?php

namespace Tests\Unit\Xml3176;

use Tests\TestCase;
use App\Http\Controllers\BHYT\BHYTXml3176Controller;
use Yajra\DataTables\Processors\DataProcessor;

class Xml3176DatatableColumnsTest extends TestCase
{
    /**
     * Dung DataProcessor that su cua yajra voi dung cau hinh ma fetchData() dat,
     * de kiem hai dieu khong the suy luan bang mat:
     *   - only() co cat nham DT_RowClass khong (neu co, dong loi mat mau do)
     *   - quan he long co that su bi cat khoi payload khong
     */
    private function chayProcessor()
    {
        $row = (object) [
            'ma_lk' => 'MA1', 'ma_bn' => 'BN1', 'ho_ten' => 'Nguyen Van A',
            'ma_the_bhyt' => 'T1', 'ngay_sinh' => '1979', 'ngay_vao' => '1',
            'ngay_ra' => '2', 'ngay_ttoan' => '3', 'created_at' => 'c', 'updated_at' => 'u',
            'xml3176_error_result_count' => 5,
            'check_hein_card' => ['ma_lk' => 'MA1', 'ghi_chu' => 'NOI DUNG DAI'],
            'xml3176_information' => ['ma_lk' => 'MA1', 'submitted_message' => 'NOI DUNG DAI'],
        ];

        // 'name' la khoa Helper::includeInArray dung de ghi vao mang - phai dung
        // ten cot that, khong duoc dat chung mot ten.
        $cot = function ($ten, $noi_dung) {
            return ['content' => function ($r) use ($noi_dung) { return $noi_dung; },
                    'order' => false, 'name' => $ten];
        };

        $columnDef = [
            'append' => [
                'action'       => $cot('action', '<b>ACT</b>'),
                'imported_by'  => $cot('imported_by', 'admin'),
                'exported_at'  => $cot('exported_at', '<i></i>'),
                'submitted_at' => $cot('submitted_at', '<i></i>'),
                'is_signed'    => $cot('is_signed', '<i></i>'),
            ],
            'edit'   => [],
            'excess' => ['rn', 'row_num'],
            'only'   => BHYTXml3176Controller::DATATABLE_COLUMNS,
            'escape' => config('datatables.columns.escape'),
            'index'  => false,
            'raw'    => ['exported_at', 'is_signed', 'action', 'submitted_at'],
        ];

        $templates = [
            'DT_RowId'    => '',
            'DT_RowData'  => [],
            'DT_RowAttr'  => [],
            // Giong setRowClass() trong fetchData()
            'DT_RowClass' => function ($r) {
                return $r->xml3176_error_result_count > 0 ? 'highlight-red' : '';
            },
        ];

        $out = (new DataProcessor([$row], $columnDef, $templates, 0))->process(true);

        return $out[0];
    }

    /** @test */
    public function only_khong_cat_nham_dt_row_class_nen_dong_co_loi_van_duoc_to_do()
    {
        $dong = $this->chayProcessor();

        $this->assertArrayHasKey('DT_RowClass', $dong,
            'only() da cat mat DT_RowClass - dong co loi se khong con duoc to do');
        $this->assertEquals('highlight-red', $dong['DT_RowClass']);
    }

    /** @test */
    public function quan_he_long_bi_cat_khoi_payload()
    {
        $dong = $this->chayProcessor();

        foreach (['check_hein_card', 'xml3176_information', 'xml3176_error_result_count'] as $khoa) {
            $this->assertArrayNotHasKey($khoa, $dong,
                "Khoa '$khoa' van di ra ngoai trong payload - only() khong an");
        }
    }

    /** @test */
    public function moi_cot_trong_danh_sach_trang_deu_ra_duoc_ngoai()
    {
        $dong = $this->chayProcessor();

        foreach (BHYTXml3176Controller::DATATABLE_COLUMNS as $cot) {
            $this->assertArrayHasKey($cot, $dong,
                "Cot '$cot' bi mat khoi payload - se trong tron tren giao dien");
        }
    }

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
