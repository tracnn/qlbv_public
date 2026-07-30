<?php

namespace Tests\Unit\Import;

use DB;
use Tests\TestCase;
use App\Services\CatalogImportService;

/**
 * Nhap danh muc ICD phai di duong GHI THEO LO.
 *
 * Do duoc tren tep ICD that cua cong BHXH (52.551 dong x 15 cot, nen 1,1 MB nhung bung ra
 * 29 MB XML + 5,8 MB sharedStrings): duong "gom" cu - dung toan bo dong trong mot Collection
 * roi moi ghi - CHI RIENG PHAN DOC da an 128 MB va 346 giay, chua tinh ~105.000 truy van
 * updateOrCreate. May chu dat PHP 128 MB / 120 giay nen tien trinh chet vi fatal error, ma
 * fatal error khong phai \Exception nen controller tra ve trang loi HTML thay vi JSON va
 * Dropzone chi hien duoc chuoi du phong "Loi khi tai len".
 */
class NhapIcdTheoLoTest extends TestCase
{
    /** @test */
    public function icd_nam_trong_danh_sach_ghi_theo_lo()
    {
        $this->assertContains('icd10', CatalogImportService::GHI_THEO_LO,
            'ICD10 con di duong gom - dung 52.551 dong trong bo nho');
        $this->assertContains('icd_yhct', CatalogImportService::GHI_THEO_LO);
    }

    /** @test */
    public function ba_danh_muc_theo_co_so_van_ghi_theo_lo()
    {
        foreach (CatalogImportService::DANH_MUC_THEO_CO_SO as $loai) {
            $this->assertContains($loai, CatalogImportService::GHI_THEO_LO,
                "Danh muc $loai bi tuot khoi duong ghi theo lo");
        }
    }

    /** @test */
    public function ten_bang_cua_icd_dung_ten_that_trong_csdl()
    {
        // Ghi theo lo dung DB::table($bang) truc tiep, khong qua Eloquent: sai ten bang la
        // hong ca lan nhap.
        $svc = app(CatalogImportService::class);
        $ham = new \ReflectionMethod($svc, 'bangCua');
        $ham->setAccessible(true);

        $this->assertSame('icd10_categories', $ham->invoke($svc, 'icd10'));
        $this->assertSame('icd_yhct_categories', $ham->invoke($svc, 'icd_yhct'));

        foreach (['icd10_categories', 'icd_yhct_categories'] as $bang) {
            $this->assertNotEmpty(DB::select('SHOW COLUMNS FROM ' . $bang),
                "Bang $bang khong ton tai");
        }
    }

    /** @test */
    public function khong_con_ham_nhap_tung_dong_cho_icd()
    {
        // Giu lai la de duong cu bi goi lai va vo bo nho lan nua.
        $svc = app(CatalogImportService::class);

        $this->assertFalse(method_exists($svc, 'importIcd10'),
            'Van con duong nhap tung dong cho ICD10');
        $this->assertFalse(method_exists($svc, 'importIcdYhct'));
    }

    /** @test */
    public function chi_danh_muc_theo_co_so_moi_nhan_ma_co_so()
    {
        // ganCoSo them khoa ma_cskcb vao du lieu ghi; bang icd10_categories KHONG co cot do
        // nen ap cho ICD la hong ca lo chen.
        $this->assertSame('01929', CatalogImportService::maCoSoApDung('medicine', '01929'));
        $this->assertNull(CatalogImportService::maCoSoApDung('icd10', '01929'));
        $this->assertNull(CatalogImportService::maCoSoApDung('icd_yhct', '01929'));
    }

    /** @test */
    public function co_man_tinh_ve_boolean()
    {
        // Cot is_chronic la boolean; gia tri tu Excel la chuoi tu do.
        $this->assertTrue(CatalogImportService::chuanHoaManTinh(['is_chronic' => 'x'])['is_chronic']);
        $this->assertTrue(CatalogImportService::chuanHoaManTinh(['is_chronic' => '1'])['is_chronic']);
        $this->assertTrue(CatalogImportService::chuanHoaManTinh(['is_chronic' => 'Có'])['is_chronic']);
        $this->assertFalse(CatalogImportService::chuanHoaManTinh(['is_chronic' => ''])['is_chronic'],
            'O de trong phai ra false, khong duoc thanh null');
        $this->assertFalse(CatalogImportService::chuanHoaManTinh(['is_chronic' => 'khong'])['is_chronic']);
    }

    /** @test */
    public function khong_tu_sinh_cot_man_tinh_khi_tep_khong_co()
    {
        $ra = CatalogImportService::chuanHoaManTinh(['icd_code' => 'A00']);

        $this->assertArrayNotHasKey('is_chronic', $ra);
    }
}
