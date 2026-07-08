<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\ExcelColumnMapper;

class CatalogIcdDetectTest extends TestCase
{
    private function detect(array $header)
    {
        $mapper = new ExcelColumnMapper();
        return $mapper->detectCatalogType($header, config('catalog_import_mapping'));
    }

    public function test_nhan_dien_icd10()
    {
        $this->assertSame('icd10', $this->detect(['MA_ICD10', 'TEN_ICD10', 'BENH_MAN_TINH']));
    }

    public function test_nhan_dien_icd_yhct_khong_nham_icd10()
    {
        $this->assertSame('icd_yhct', $this->detect(['MA_ICD_YHCT', 'TEN_ICD_YHCT', 'TEN_BENH_YHCT', 'MA_ICD10', 'TEN_ICD10']));
    }
}
