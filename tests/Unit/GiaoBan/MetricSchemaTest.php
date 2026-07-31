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
        $this->assertEquals(['int', 'decimal', 'percent', 'text'], $fields['value_type']['options']);
    }

    /** @test */
    public function common_fields_ap_cho_moi_loai_chi_tieu()
    {
        $c = MetricSchema::COMMON_FIELDS;

        $this->assertEquals(['overview', 'overview_label', 'dieu_tri_slide'], array_keys($c));
        $this->assertEquals('bool', $c['overview']['widget']);
        // O nhan chi hien sau khi tich — dung lai co che show_if
        $this->assertEquals(['overview' => [true]], $c['overview_label']['show_if']);
        $this->assertEquals(60, $c['overview_label']['max']);
        $this->assertEquals('bool', $c['dieu_tri_slide']['widget']);
    }

    /** @test */
    public function cac_o_chi_co_nghia_voi_so_deu_khai_show_if()
    {
        $fields = MetricSchema::TYPES['manual']['fields'];

        foreach (['unit', 'min', 'max', 'default'] as $ten) {
            $this->assertArrayHasKey('show_if', $fields[$ten], "Thieu show_if cho o '$ten'");
            $this->assertEquals(
                ['int', 'decimal', 'percent'],
                $fields[$ten]['show_if']['value_type'],
                "O '$ten' phai an khi kieu gia tri la text"
            );
        }

        // hint / required van hien voi moi kieu
        foreach (['hint', 'required'] as $ten) {
            $this->assertArrayNotHasKey('show_if', $fields[$ten]);
        }
    }

    /** @test */
    public function canh_bao_manual_cho_chi_tieu_nhap_tay()
    {
        $m = ['code' => 'cg', 'name' => 'Chuyên gia', 'type' => 'manual'];
        $this->assertEquals('manual', MetricSchema::warningFor($m, [12]));
    }

    /** @test */
    public function canh_bao_no_dept_khi_chua_gan_khoa_HIS()
    {
        $m = ['code' => 'bn_cu', 'name' => 'BN cũ', 'type' => 'census_from'];
        $this->assertEquals('no_dept', MetricSchema::warningFor($m, []));
        $this->assertNull(MetricSchema::warningFor($m, [12]));
    }

    /** @test */
    public function admission_khong_can_khoa_nen_khong_canh_bao()
    {
        $m = ['code' => 'vv', 'name' => 'Vào viện', 'type' => 'admission'];
        $this->assertNull(MetricSchema::warningFor($m, []));
    }

    /** @test */
    public function bed_count_dua_vao_bed_ids_nen_khong_canh_bao_thieu_khoa()
    {
        $m = ['code' => 'gyc', 'name' => 'Giường YC', 'type' => 'bed_count', 'bed_ids' => [5]];
        $this->assertNull(MetricSchema::warningFor($m, []));
    }

    /** @test */
    public function canh_bao_no_scope_khi_service_count_khong_co_pham_vi_nao()
    {
        // khong gan khoa HIS + khong khai pham vi cu the -> computeAll tra 0 trong im lang
        $m = ['code' => 'dv', 'name' => 'DV', 'type' => 'service_count', 'filter' => ['service_type_ids' => [2]]];
        $this->assertEquals('no_scope', MetricSchema::warningFor($m, []));

        // co khoa HIS -> computeAll tu gan request_department_ids -> khong canh bao
        $this->assertNull(MetricSchema::warningFor($m, [12]));

        // khai phong thuc hien cu the -> co pham vi du khong gan khoa
        $m2 = ['code' => 'dv', 'name' => 'DV', 'type' => 'service_count', 'filter' => ['execute_room_ids' => [9]]];
        $this->assertNull(MetricSchema::warningFor($m2, []));
    }

    /** @test */
    public function service_count_co_self_thi_execute_department_ids_bi_ghi_de_thanh_rong_nen_van_no_scope()
    {
        // computeAll ghi de filter['execute_department_ids'] = $deptIds khi bat co self.
        // $deptIds rong -> khoa nay coi nhu rong du filter goc co gia tri.
        $m = ['code' => 'dv', 'name' => 'DV', 'type' => 'service_count', 'filter' => [
            'execute_department_id_self' => true,
            'execute_department_ids' => [9],
        ]];
        $this->assertEquals('no_scope', MetricSchema::warningFor($m, []));
    }

    /** @test */
    public function service_count_co_self_nhung_co_execute_room_ids_thi_van_cuu_duoc_pham_vi()
    {
        $m = ['code' => 'dv', 'name' => 'DV', 'type' => 'service_count', 'filter' => [
            'execute_department_id_self' => true,
            'execute_room_ids' => [9],
        ]];
        $this->assertNull(MetricSchema::warningFor($m, []));
    }

    /** @test */
    public function danh_sach_khoa_hasScope_trong_computeAll_khop_voi_danh_sach_warningFor_kiem()
    {
        // warningFor() co-tinh guard $hasScope cua GiaoBanMetricService::computeAll (case 'service_count').
        // Neu mot ben doi danh sach khoa filter ma ben kia khong doi theo, canh bao no_scope
        // se lech voi thuc te tinh toan. Test nay scrape ca hai nguon va doi chieu danh sach khoa.

        $srcService = file_get_contents(app_path('Services/GiaoBan/GiaoBanMetricService.php'));
        preg_match('/\$hasScope\s*=(.*?);/s', $srcService, $m1);
        $this->assertNotEmpty($m1, 'Khong tim thay bieu thuc $hasScope trong GiaoBanMetricService.php');
        preg_match_all("/\\\$filter\\['([a-z_]+)'\\]/", $m1[1], $m2);
        $khoaHasScope = array_values(array_unique($m2[1]));
        sort($khoaHasScope);

        $srcSchema = file_get_contents(app_path('Services/GiaoBan/MetricSchema.php'));
        preg_match('/\$khoaConLai\s*=(.*?);/s', $srcSchema, $m3);
        $this->assertNotEmpty($m3, 'Khong tim thay bien $khoaConLai trong MetricSchema::warningFor');
        // Bo phan dieu kien truoc dau '?' (chua $f['execute_department_id_self'], khong phai
        // khoa can doi chieu), chi lay khoa trong hai mang [...] cua hai nhanh ternary.
        $vePhaiTernary = strstr($m3[1], '?');
        $this->assertNotFalse($vePhaiTernary, 'khoaConLai khong phai bieu thuc ternary nhu ky vong');
        preg_match_all('/\[([^\]]*)\]/', $vePhaiTernary, $mangCon);
        $khoaWarningFor = [];
        foreach ($mangCon[1] as $noiDung) {
            preg_match_all("/'([a-z_]+)'/", $noiDung, $m4);
            $khoaWarningFor = array_merge($khoaWarningFor, $m4[1]);
        }
        $khoaWarningFor = array_values(array_unique($khoaWarningFor));
        sort($khoaWarningFor);

        $this->assertEquals($khoaHasScope, $khoaWarningFor,
            'Danh sach khoa filter trong $hasScope (computeAll) va $khoaConLai (warningFor) da lech nhau. '
            . 'Sua guard o mot ben thi phai sua ca ben kia (xem comment o ca hai noi).');
    }
}
