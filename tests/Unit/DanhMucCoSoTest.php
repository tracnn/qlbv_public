<?php

namespace Tests\Unit;

use DB;
use Tests\TestCase;

class DanhMucCoSoTest extends TestCase
{
    /** @test */
    public function ba_danh_muc_deu_co_cot_ma_cskcb()
    {
        foreach (['service_catalogs', 'medicine_catalogs', 'medical_supply_catalogs'] as $bang) {
            $co = false;

            foreach (DB::select('SHOW COLUMNS FROM ' . $bang) as $c) {
                if ($c->Field === 'ma_cskcb') { $co = true; break; }
            }

            $this->assertTrue($co, "Bang $bang thieu cot ma_cskcb");
        }
    }

    /** @test */
    public function ba_model_deu_cho_ghi_ma_cskcb()
    {
        // ServiceCatalog truoc day khong co trong fillable nen gia tri nhap vao bi bo im lang.
        foreach ([
            \App\Models\BHYT\ServiceCatalog::class,
            \App\Models\BHYT\MedicineCatalog::class,
            \App\Models\BHYT\MedicalSupplyCatalog::class,
        ] as $lop) {
            $m = new $lop();

            $this->assertContains('ma_cskcb', $m->getFillable(), "$lop khong cho ghi ma_cskcb");
        }
    }

    /** @test */
    public function ba_danh_muc_deu_co_ma_cskcb_trong_khoa_duy_nhat()
    {
        // Thieu khoa nay thi nhap danh muc co so thu hai se de len dong cua co so thu nhat.
        $cfg = config('catalog_import_mapping');

        foreach (['medicine', 'medical_supply', 'service'] as $loai) {
            $this->assertArrayHasKey($loai, $cfg, "Thieu cau hinh $loai");
            $this->assertContains('ma_cskcb', $cfg[$loai]['unique_keys'],
                "Danh muc $loai chua dua ma_cskcb vao unique_keys");
        }
    }

    /** @test */
    public function ba_danh_muc_deu_anh_xa_cot_ma_cskcb_tu_excel()
    {
        $cfg = config('catalog_import_mapping');

        foreach (['medicine', 'medical_supply', 'service'] as $loai) {
            $this->assertArrayHasKey('ma_cskcb', $cfg[$loai]['mapping'],
                "Danh muc $loai chua anh xa cot MA_CSKCB");
        }
    }

    /** @test */
    public function gom_co_so_cung_ma_cskcb_thanh_mot_lua_chon()
    {
        // Bach Mai (id=1) va Bach Mai CS2 (id=21) deu la 01929: BHXH cap danh muc theo MA
        // CSKCB nen hai co so do dung chung mot bo, phai gom thanh mot lua chon.
        $ra = \App\Services\BHYT\DanhSachCoSo::gom([
            ['hein_medi_org_code' => '01929', 'branch_name' => 'Bach Mai'],
            ['hein_medi_org_code' => '01929', 'branch_name' => 'Bach Mai CS2'],
            ['hein_medi_org_code' => '37470', 'branch_name' => 'Ninh Binh'],
        ]);

        $this->assertCount(2, $ra);
        $this->assertArrayHasKey('01929', $ra);
        $this->assertContains('Bach Mai', $ra['01929']);
        $this->assertContains('Bach Mai CS2', $ra['01929']);
    }

    /** @test */
    public function bo_dong_thieu_ma_cskcb_khi_gom_co_so()
    {
        $ra = \App\Services\BHYT\DanhSachCoSo::gom([
            ['hein_medi_org_code' => null, 'branch_name' => 'Chua khai'],
            ['hein_medi_org_code' => '  ', 'branch_name' => 'Rong'],
            ['hein_medi_org_code' => '01929', 'branch_name' => 'Bach Mai'],
        ]);

        $this->assertSame(['01929'], array_keys($ra));
    }

    /** @test */
    public function gan_co_so_chi_dien_cho_dong_bo_trong()
    {
        // Gia tri trong TEP luon thang: mot tep co the chua nhieu co so.
        $co = \App\Services\CatalogImportService::ganCoSo(['ma_thuoc' => 'A', 'ma_cskcb' => '37470'], '01929');
        $this->assertSame('37470', $co['ma_cskcb']);

        $thieu = \App\Services\CatalogImportService::ganCoSo(['ma_thuoc' => 'A'], '01929');
        $this->assertSame('01929', $thieu['ma_cskcb']);

        $rong = \App\Services\CatalogImportService::ganCoSo(['ma_thuoc' => 'A', 'ma_cskcb' => '  '], '01929');
        $this->assertSame('01929', $rong['ma_cskcb']);
    }

    /** @test */
    public function khong_chon_co_so_thi_khoa_giu_nguyen()
    {
        // Giu duong lui "dung chung moi co so".
        $ra = \App\Services\CatalogImportService::ganCoSo(['ma_thuoc' => 'A'], null);

        $this->assertArrayNotHasKey('ma_cskcb', $ra);
        $this->assertSame(['ma_thuoc' => 'A'], $ra);
    }

    /** @test */
    public function chi_ba_danh_muc_duoc_gan_theo_co_so()
    {
        // ICD, nhan vien y te, trang thiet bi... la danh muc DUNG CHUNG.
        $this->assertSame(['medicine', 'medical_supply', 'service'],
            \App\Services\CatalogImportService::DANH_MUC_THEO_CO_SO);
    }

    /** @test */
    public function danh_sach_co_so_chi_lay_co_so_dang_hoat_dong()
    {
        // Chi ap cho O CHON khi nhap danh muc. KHONG duoc ap o leftJoin('his_branch') trong
        // HisOrderSource: ho so cu thuoc co so da tat van phai doc duoc ma CSKCB.
        $ma = file_get_contents(app_path('Services/BHYT/DanhSachCoSo.php'));

        $this->assertContains("where('is_delete', 0)", $ma);
        $this->assertContains("where('is_active', 1)", $ma);

        $nguon = file_get_contents(app_path('Services/OrderCheck/HisOrderSource.php'));
        $this->assertNotContains("br.is_active", $nguon,
            'HisOrderSource khong duoc loc co so theo trang thai hoat dong');
    }

    /** @test */
    public function gia_khong_con_bat_buoc_khi_nhap_vat_tu_y_te()
    {
        // Danh muc BHXH cap co dong chua co gia; cot gia trong CSDL deu nullable.
        $bb = config('catalog_import_mapping.medical_supply.required_fields');

        $this->assertNotContains('don_gia', $bb);
        $this->assertNotContains('don_gia_bh', $bb);
        $this->assertContains('ma_vat_tu', $bb, 'Van phai bat buoc ma vat tu');
    }

    /** @test */
    public function gia_khong_bat_buoc_khi_nhap_thuoc()
    {
        $bb = config('catalog_import_mapping.medicine.required_fields');

        $this->assertNotContains('don_gia', $bb);
        $this->assertNotContains('don_gia_bh', $bb);
    }

    /** @test */
    public function o_so_de_trong_duoc_quy_ve_null()
    {
        // MySQL o che do STRICT_TRANS_TABLES tu choi chuoi rong cho cot decimal:
        // "Incorrect decimal value: ''" - dong bi bo va nguoi dung chi thay mot con so loi.
        $ra = \App\Services\CatalogImportService::chuanHoaSo(
            ['ma' => 'A', 'don_gia' => '', 'don_gia_bh' => '   ', 'so_luong' => '5'],
            ['don_gia', 'don_gia_bh', 'so_luong']
        );

        $this->assertNull($ra['don_gia']);
        $this->assertNull($ra['don_gia_bh'], 'Chuoi toan khoang trang cung phai ve null');
        $this->assertSame('5', $ra['so_luong'], 'Gia tri co that phai giu nguyen');
    }

    /** @test */
    public function cot_chu_de_trong_khong_bi_quy_ve_null()
    {
        // Chi cot SO moi can quy ve null; cot chu de chuoi rong la hop le.
        $ra = \App\Services\CatalogImportService::chuanHoaSo(
            ['ten' => '', 'don_gia' => ''],
            ['don_gia']
        );

        $this->assertSame('', $ra['ten']);
        $this->assertNull($ra['don_gia']);
    }

    /** @test */
    public function so_0_khong_bi_coi_la_trong()
    {
        // Gia = 0 la gia tri hop le, khong duoc bien thanh null.
        $ra = \App\Services\CatalogImportService::chuanHoaSo(['don_gia' => 0], ['don_gia']);

        $this->assertSame(0, $ra['don_gia']);
    }

    /** @test */
    public function cot_khong_co_trong_du_lieu_thi_khong_tu_sinh_ra()
    {
        $ra = \App\Services\CatalogImportService::chuanHoaSo(['ma' => 'A'], ['don_gia']);

        $this->assertArrayNotHasKey('don_gia', $ra);
    }

    /** @test */
    public function cat_khoang_trang_thua_o_gia_tri_chuoi()
    {
        // O Excel hay dinh TAB o cuoi; gia tri do luu nguyen se lam khoa so khop lech nhau
        // va dong da co bi chen lai moi lan nhap.
        $ra = \App\Services\CatalogImportService::catKhoangTrang([
            'ma' => "A	", 'ten' => '  B  ', 'gia' => 10, 'rong' => null,
        ]);

        $this->assertSame('A', $ra['ma']);
        $this->assertSame('B', $ra['ten']);
        $this->assertSame(10, $ra['gia'], 'Gia tri khong phai chuoi giu nguyen');
        $this->assertNull($ra['rong']);
    }
}
