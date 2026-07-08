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
}
