<?php

namespace Tests\Unit\GoXmlCu;

use Tests\TestCase;

/**
 * Go chuc nang quet thu muc nhap danh muc BHXH (importCatalogBHXH:data, dich vu NSSM
 * "QLBV ImportCatalog"). Danh muc nay chi con nhap qua man hinh. CatalogImportService van
 * giu - man nhap danh muc va lenh HanhChinhChuyen2Cap dung no.
 *
 * May chu tu chay update.bat moi gio: dich vu dang chay phai duoc GO bang khoi idempotent,
 * khong chi xoa dong cai - xoa dong cai thi dich vu cu van chay mai va loi lenh khong ton tai.
 */
class GoQuetDanhMucTest extends TestCase
{
    const DICH_VU = 'QLBV ImportCatalog';

    private function tep($ten)
    {
        return file_get_contents(base_path($ten));
    }

    /** Danh sach ten dich vu trong vong go "for %%S in (...)". */
    private function vongGo($noiDung)
    {
        $this->assertSame(1, preg_match('/for %%S in \(([^)]*)\) do \(/', $noiDung, $m), 'Thieu vong go dich vu cu');

        return $m[1];
    }

    /** @test */
    public function lenh_quet_da_go()
    {
        $this->assertFileNotExists(app_path('Console/Commands/ImportCatalogBHXHFromFiles.php'));
        $this->assertArrayNotHasKey('importCatalogBHXH:data', \Artisan::all());
    }

    /** @test */
    public function update_bat_go_dich_vu_va_khong_cai_start_lai()
    {
        $b = $this->tep('update.bat');

        $this->assertContains('"' . self::DICH_VU . '"', $this->vongGo($b));
        $this->assertNotContains('nssm install "' . self::DICH_VU . '"', $b);
        $this->assertNotContains('nssm start "' . self::DICH_VU . '"', $b);
        $this->assertNotContains('nssm stop "' . self::DICH_VU . '"', $b);
        $this->assertNotContains('artisan importCatalogBHXH:data', $b);
    }

    /** @test */
    public function install_khong_cai_remove_van_don()
    {
        $cai = $this->tep('install_service.bat');
        $this->assertNotContains(self::DICH_VU, $cai);
        $this->assertNotContains('artisan importCatalogBHXH:data', $cai);

        $this->assertContains('"' . self::DICH_VU . '"', $this->vongGo($this->tep('remove_service.bat')));
    }
}
