<?php

namespace Tests\Unit\Xml3176;

use Tests\TestCase;

class Xml3176DetailBladeTest extends TestCase
{
    /** @test */
    public function khong_blade_chi_tiet_nao_con_ban_truy_van_trong_vong_lap()
    {
        $thuMuc = resource_path('views/bhyt/xml3176');
        $viPham = [];

        foreach (glob($thuMuc . '/detail-xml*.blade.php') as $file) {
            $noiDung = file_get_contents($file);

            // errorResult() CO dau ngoac = query builder moi moi lan goi, khong bao gio
            // duoc cache. Trong vong lap thi thanh mot truy van cho moi dong.
            if (strpos($noiDung, 'errorResult()') !== false) {
                $viPham[] = basename($file) . ' -> errorResult()';
            }

            // Xml3176ErrorResult() CO dau ngoac cung vay. Ban khong ngoac la truy cap
            // collection da nap, hoan toan hop le.
            if (strpos($noiDung, 'Xml3176ErrorResult()') !== false) {
                $viPham[] = basename($file) . ' -> Xml3176ErrorResult()';
            }
        }

        $this->assertEmpty(
            $viPham,
            "Blade chi tiet ban truy van khi render, se thanh N+1: \n" . implode("\n", $viPham)
        );
    }

    /** @test */
    public function quan_he_blade_doc_trong_vong_lap_phai_duoc_eager_load()
    {
        // detail-xml-errors doc $error->Xml3176ErrorCatalog cho TUNG dong loi. Neu
        // detailXmlTab khong nap kem thi moi dong la mot truy van - dung loi da lam
        // tab "Loi XML" cham.
        $blade = file_get_contents(
            resource_path('views/bhyt/xml3176/detail-xml-errors.blade.php')
        );

        if (strpos($blade, 'Xml3176ErrorCatalog') === false) {
            $this->markTestSkipped('Blade khong con doc danh muc loi - hang rao het y nghia');
        }

        $controller = file_get_contents(
            app_path('Http/Controllers/BHYT/BHYTXml3176Controller.php')
        );

        $this->assertContains(
            'Xml3176ErrorResult.Xml3176ErrorCatalog',
            $controller,
            'Blade doc quan he Xml3176ErrorCatalog nhung controller khong eager-load -> N+1'
        );
    }
}
