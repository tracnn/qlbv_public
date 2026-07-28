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
}
