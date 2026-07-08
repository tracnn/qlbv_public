<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Exports\CatalogTemplateExport;
use App\Services\ExcelColumnMapper;

class CatalogTemplateSelfDetectTest extends TestCase
{
    public function test_moi_bieu_mau_tu_nhan_dien_dung_loai()
    {
        $configs = config('catalog_import_mapping');
        $mapper = new ExcelColumnMapper();
        foreach (array_keys($configs) as $type) {
            $headers = (new CatalogTemplateExport($type))->headers();
            $detected = $mapper->detectCatalogType($headers, $configs);
            $this->assertSame($type, $detected, "Bieu mau '$type' bi nhan dien thanh: " . var_export($detected, true));
        }
    }
}
