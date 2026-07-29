<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Exports\CatalogTemplateExport;

class CatalogTemplateExportTest extends TestCase
{
    public function test_headers_gom_alias_dau_moi_field()
    {
        $export = new CatalogTemplateExport('medicine');
        $headers = $export->headers();
        $this->assertContains('MA_THUOC', $headers);
        $this->assertContains('TEN_THUOC', $headers);
        $this->assertSame([], $export->array());
    }

    public function test_headers_chua_moi_detect_key_de_tu_nhan_dien()
    {
        $export = new CatalogTemplateExport('service');
        $headers = $export->headers();
        foreach (config('catalog_import_mapping.service.detect_keys') as $key) {
            $this->assertContains($key, $headers, "Thieu detect_key: $key");
        }
    }

    public function test_required_headers_dung_first_alias_cua_required_fields()
    {
        $export = new CatalogTemplateExport('medicine');
        $req = $export->requiredHeaders();
        $this->assertContains('MA_THUOC', $req);
        $this->assertContains('TEN_THUOC', $req);
    }

    /** @test */
    public function ba_danh_muc_theo_co_so_deu_to_mau_cot_ma_cskcb()
    {
        foreach (['medicine', 'medical_supply', 'service'] as $loai) {
            $e = new CatalogTemplateExport($loai);

            $this->assertSame(['MA_CSKCB'], $e->facilityHeaders(), "Danh muc $loai");
            $this->assertContains('MA_CSKCB', $e->headers());
        }
    }

    /** @test */
    public function danh_muc_dung_chung_khong_to_mau_ma_cskcb()
    {
        // ICD, nghe nghiep... khong co khai niem co so.
        foreach (['icd10', 'job_categories'] as $loai) {
            $this->assertSame([], (new CatalogTemplateExport($loai))->facilityHeaders(), "Danh muc $loai");
        }
    }

    /** @test */
    public function ma_cskcb_khong_nam_trong_nhom_cot_bat_buoc()
    {
        // To mau RIENG chu khong dung mau vang cua cot bat buoc: tep BHXH cap thuong khong
        // co cot nay, bat buoc thi se tu choi nhap.
        foreach (['medicine', 'medical_supply', 'service'] as $loai) {
            $this->assertNotContains('MA_CSKCB', (new CatalogTemplateExport($loai))->requiredHeaders(), $loai);
        }
    }
}
