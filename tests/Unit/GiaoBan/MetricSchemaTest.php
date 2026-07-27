<?php

namespace Tests\Unit\GiaoBan;

use Tests\TestCase;
use App\Services\GiaoBan\MetricSchema;
use App\Services\GiaoBan\GiaoBanCatalogService;

class MetricSchemaTest extends TestCase
{
    /** @test */
    public function moi_case_trong_computeAll_deu_co_trong_registry_va_nguoc_lai()
    {
        $src = file_get_contents(app_path('Services/GiaoBan/GiaoBanMetricService.php'));
        // Lay cac 'case' trong switch cua computeAll: case 'census_from':
        preg_match_all("/case\s+'([a-z_]+)'\s*:/", $src, $m);
        $casesTrongService = array_values(array_unique($m[1]));

        // 'manual' co case rieng tren default nen se bat duoc boi regex
        $trongRegistry = MetricSchema::typeKeys();

        $thieu = array_diff($casesTrongService, $trongRegistry);
        $thua  = array_diff($trongRegistry, array_merge($casesTrongService, ['manual']));

        $this->assertEmpty($thieu, 'Registry thieu type: ' . implode(', ', $thieu));
        $this->assertEmpty($thua, 'Registry thua type khong ai tinh: ' . implode(', ', $thua));
        $this->assertContains('manual', $trongRegistry, 'Registry phai co type manual');
    }

    /** @test */
    public function loc_type_theo_khoi()
    {
        $dieuTri = MetricSchema::forBlock('dieu_tri');
        $this->assertContains('census_from', $dieuTri);
        $this->assertNotContains('service_count', $dieuTri);

        $cls = MetricSchema::forBlock('can_lam_sang');
        $this->assertContains('service_count', $cls);
        $this->assertNotContains('census_from', $cls);

        $kham = MetricSchema::forBlock('kham');
        $this->assertContains('exam_visit', $kham);

        // manual dung duoc o moi khoi
        foreach (['dieu_tri', 'kham', 'can_lam_sang'] as $b) {
            $this->assertContains('manual', MetricSchema::forBlock($b), "manual phai dung duoc o khoi $b");
        }
    }

    /** @test */
    public function moi_field_tham_chieu_danh_muc_deu_tro_toi_danh_muc_co_that()
    {
        $keys = GiaoBanCatalogService::allKeys();
        foreach (MetricSchema::TYPES as $type => $def) {
            $nhom = array_merge(
                isset($def['fields']) ? $def['fields'] : [],
                isset($def['filter']) ? $def['filter'] : []
            );
            foreach ($nhom as $field => $meta) {
                if (!isset($meta['catalog'])) continue;
                $this->assertContains($meta['catalog'], $keys,
                    "Type $type field $field tro toi danh muc khong ton tai: {$meta['catalog']}");
            }
        }
    }

    /** @test */
    public function service_count_khai_day_du_khoa_filter_ma_service_that_su_doc()
    {
        $filter = MetricSchema::TYPES['service_count']['filter'];
        foreach (['service_type_ids', 'diim_type_ids', 'test_type_ids', 'service_ids',
                  'execute_room_ids', 'priority_min', 'priority_max'] as $k) {
            $this->assertArrayHasKey($k, $filter, "Thieu khoa filter $k");
        }
        // nhom "Khac" khai bang other_key chu khong phai khoa rieng
        $this->assertEquals('diim_type_other_of', $filter['diim_type_ids']['other_key']);
        $this->assertEquals('test_type_other_of', $filter['test_type_ids']['other_key']);
    }

    /** @test */
    public function manual_khai_du_thuoc_tinh_o_nhap_tay()
    {
        $fields = MetricSchema::TYPES['manual']['fields'];
        foreach (['unit', 'hint', 'value_type', 'min', 'max', 'required', 'default', 'carry_over'] as $k) {
            $this->assertArrayHasKey($k, $fields, "Thieu thuoc tinh nhap tay $k");
        }
        $this->assertEquals(['int', 'decimal', 'percent'], $fields['value_type']['options']);
    }
}
