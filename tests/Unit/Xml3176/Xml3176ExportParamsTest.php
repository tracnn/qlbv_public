<?php

namespace Tests\Unit\Xml3176;

use Tests\TestCase;

class Xml3176ExportParamsTest extends TestCase
{
    /** @test */
    public function nut_7980a_gui_du_moi_tham_so_ma_lop_export_doc()
    {
        $blade = file_get_contents(resource_path('views/bhyt/xml3176/index.blade.php'));

        // Cat doan $.param({...}) cua rieng nut 79/80a: tu ten route den dau
        // ngoac dong cua loi goi $.param.
        $start = strpos($blade, 'export-7980a-data');
        $this->assertNotFalse($start, 'Khong tim thay nut 79/80a trong blade');

        $end = strpos($blade, '});', $start);
        $this->assertNotFalse($end, 'Khong tim thay diem ket thuc cua $.param');

        preg_match_all("/'([a-z0-9_]+)'\s*:/", substr($blade, $start, $end - $start), $m);
        $guiDi = array_values(array_unique($m[1]));
        $this->assertNotEmpty($guiDi, 'Khong doc duoc tham so nao - regex hong?');

        // Ben nhan: export7980aData() chuyen thang ca $request sang lop Export,
        // nen tham so that su duoc doc nam trong lop do.
        $export = file_get_contents(app_path('Exports/Xml3176Xml7980aExport.php'));
        preg_match_all("/request->input\('([a-z0-9_]+)'\)/", $export, $m2);
        $docDen = array_values(array_unique($m2[1]));
        $this->assertNotEmpty($docDen, 'Khong doc duoc tham so nao ben Export - regex hong?');

        $thieu = array_diff($docDen, $guiDi);
        $this->assertEmpty(
            $thieu,
            'Lop Export doc tham so ma nut khong gui -> file xuat khong khop man hinh: '
                . implode(', ', $thieu)
        );
    }
}
