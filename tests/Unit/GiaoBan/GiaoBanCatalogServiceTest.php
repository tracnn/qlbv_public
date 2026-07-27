<?php

namespace Tests\Unit\GiaoBan;

use Tests\TestCase;
use App\Services\GiaoBan\GiaoBanCatalogService;

class GiaoBanCatalogServiceTest extends TestCase
{
    /** @test */
    public function khai_bao_du_9_danh_muc_va_moi_muc_co_bang_cot_id_cot_ten()
    {
        $c = GiaoBanCatalogService::CATALOGS;

        $this->assertCount(9, $c);
        foreach (['service_type', 'diim_type', 'test_type', 'patient_type', 'treatment_type',
                  'end_type', 'service', 'room', 'bed'] as $key) {
            $this->assertArrayHasKey($key, $c, "Thieu danh muc $key");
            $this->assertArrayHasKey('table', $c[$key]);
            $this->assertArrayHasKey('id_col', $c[$key]);
            $this->assertArrayHasKey('name_col', $c[$key]);
        }
    }

    /** @test */
    public function ba_danh_muc_lon_duoc_danh_dau_remote_con_lai_thi_khong()
    {
        $this->assertEquals(['service', 'room', 'bed'],
            array_values(array_diff(GiaoBanCatalogService::allKeys(), GiaoBanCatalogService::smallKeys())));

        $this->assertTrue(GiaoBanCatalogService::isRemote('service'));
        $this->assertFalse(GiaoBanCatalogService::isRemote('diim_type'));
    }

    /** @test */
    public function end_type_dinh_danh_bang_code_khong_phai_id()
    {
        // metric end_type luu ["RV","CV"] chu khong luu id -> phai danh dau rieng
        $this->assertEquals('treatment_end_type_code', GiaoBanCatalogService::CATALOGS['end_type']['id_col']);
    }
}
