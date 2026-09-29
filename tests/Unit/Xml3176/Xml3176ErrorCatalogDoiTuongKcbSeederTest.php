<?php

namespace Tests\Unit\Xml3176;

use Tests\TestCase;

class Xml3176ErrorCatalogDoiTuongKcbSeederTest extends TestCase
{
    private function nguon(): string
    {
        return file_get_contents(database_path('seeds/Xml3176ErrorCatalogDoiTuongKcbSeeder.php'));
    }

    /** @test */
    public function seeder_khai_du_14_ma_loi()
    {
        $src = $this->nguon();

        // Khong co XML1_DOI_TUONG_KCB_KHONG_BHYT_CO_THE: quy tac do bo tu 11/09/2026.
        // Cong chan ho so ma 9 da go ngay 28/09/2026 nen quy tac do dung lai duoc; them
        // lai thi con so 14 o duoi phai tang theo.
        //
        // KHONG doi so nay thanh count(...) doc tu seeder: no la chot an toan co y, bat
        // ai do lang le xoa mot ma khoi seeder.
        $ma = [
            'XML1_DOI_TUONG_KCB_NGOAI_DANH_MUC',
            'XML1_DOI_TUONG_KCB_THIEU_NOI_DI',
            'XML1_DOI_TUONG_KCB_TU_DEN_CO_NOI_DI',
            'XML1_DOI_TUONG_KCB_THIEU_THE_BHYT',
            'XML1_DOI_TUONG_KCB_KHONG_THE_SAI_MA',
            'XML1_DOI_TUONG_KCB_THIEU_GIAY_CHUYEN_TUYEN',
            'XML1_DOI_TUONG_KCB_DKBD_KHAC_CSKCB',
            'XML1_DOI_TUONG_KCB_THIEU_MA_KHUVUC',
            'XML1_DOI_TUONG_KCB_BENH_NGOAI_PL1',
            'XML1_DOI_TUONG_KCB_DUNG_DKBD_SAI_MA',
            'XML1_DOI_TUONG_KCB_31_NGOAI_TRU_CO_BHTT',
            'XMLComplete_DOI_TUONG_KCB_MUC_HUONG_CO_DINH',
            'XMLComplete_DOI_TUONG_KCB_MUC_HUONG_THEO_MOC',
            'XMLComplete_DOI_TUONG_KCB_LINH_THUOC_CO_TIEN_KHAM',
        ];

        $this->assertCount(14, $ma);

        foreach ($ma as $m) {
            $this->assertContains($m, $src, "Seeder thieu ma $m");
        }
    }

    /** @test */
    public function moi_ma_deu_khong_chan_xuat_xml()
    {
        $src = $this->nguon();
        $this->assertNotContains("'critical_error' => true", $src);
        $this->assertContains("'critical_error' => false", $src);
    }

    /** @test */
    public function seeder_idempotent()
    {
        $this->assertContains('updateOrCreate', $this->nguon());
    }

    /** @test */
    public function migration_goi_seeder_va_khong_dung_change()
    {
        $files = glob(database_path('migrations/*nap_danh_muc_ma_loi_doi_tuong_kcb.php'));
        $this->assertCount(1, $files, 'Khong tim thay migration nap danh muc');

        $src = file_get_contents($files[0]);
        $this->assertContains('Xml3176ErrorCatalogDoiTuongKcbSeeder', $src);
        $this->assertNotContains('->change()', $src, 'Du an khong co doctrine/dbal');
    }

    /** @test */
    public function migration_bo_sung_chi_tao_ba_ma_moi()
    {
        $files = glob(database_path('migrations/*nap_ma_loi_doi_tuong_kcb_bo_sung.php'));
        $this->assertCount(1, $files, 'Khong tim thay migration nap ma loi bo sung');

        $src = file_get_contents($files[0]);

        $ma = [
            'XML1_DOI_TUONG_KCB_DKBD_KHAC_CSKCB',
            'XML1_DOI_TUONG_KCB_THIEU_MA_KHUVUC',
            'XML1_DOI_TUONG_KCB_BENH_NGOAI_PL1',
        ];

        foreach ($ma as $m) {
            $this->assertContains($m, $src, "Migration bo sung thieu ma $m");
        }

        $this->assertContains('firstOrCreate', $src);
        $this->assertNotContains('Xml3176ErrorCatalogDoiTuongKcbSeeder', $src, 'Khong duoc goi lai seeder: se ghi de cau hinh 10 ma cu nguoi van hanh da tu chinh');
        $this->assertNotContains('->change()', $src, 'Du an khong co doctrine/dbal');
    }
}
