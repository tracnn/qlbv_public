<?php

namespace Tests\Unit\GiaoBan;

use Tests\TestCase;
use App\Services\GiaoBan\GiaoBanMetricService;

class GiaoBanMetricServiceTest extends TestCase
{
    /** @test */
    public function dem_luot_kham_theo_phong_bam_dung_quy_uoc_luot_kham_cua_bao_cao()
    {
        $svc = new GiaoBanMetricService();
        list($sql, $binds) = $svc->buildExamByRoomSql('2026-07-26 07:00:00', '2026-07-27 07:00:00');

        // Bam dung quy uoc cua buildExamVisitSql: chi dem lan kham chinh
        $this->assertContains('is_main_exam = 1', $sql);
        $this->assertContains('sr.service_req_type_id = :kham_type', $sql);
        // Ten phong lay tu view da xac minh phu du ten, khong phai his_room (khong co cot ten)
        $this->assertContains('v_his_room', $sql);
        $this->assertNotContains('his_execute_room', $sql);

        $this->assertEquals('20260726070000', $binds['from_time']);
        $this->assertEquals('20260727070000', $binds['to_time']);
        $this->assertArrayHasKey('kham_type', $binds);
    }

    /** @test */
    public function gom_phong_it_luot_thanh_mot_cot_khac()
    {
        $rows = array(
            (object) array('ten_phong' => 'PK A', 'so_luot' => 50),
            (object) array('ten_phong' => 'PK B', 'so_luot' => 30),
            (object) array('ten_phong' => 'PK C', 'so_luot' => 20),
            (object) array('ten_phong' => 'PK D', 'so_luot' => 5),
        );

        $ra = GiaoBanMetricService::gomPhongKham($rows, 2);

        $this->assertCount(3, $ra);
        $this->assertEquals(array('ten' => 'PK A', 'so' => 50), $ra[0]);
        $this->assertEquals(array('ten' => 'PK B', 'so' => 30), $ra[1]);
        // 20 + 5 gop lai, nhan noi ro con bao nhieu phong
        $this->assertEquals(25, $ra[2]['so']);
        $this->assertContains('2 phòng', $ra[2]['ten']);
    }

    /** @test */
    public function khong_gom_khi_so_phong_khong_vuot_gioi_han()
    {
        $rows = array(
            (object) array('ten_phong' => 'PK A', 'so_luot' => 9),
            (object) array('ten_phong' => 'PK B', 'so_luot' => 4),
        );

        $ra = GiaoBanMetricService::gomPhongKham($rows, 15);

        $this->assertCount(2, $ra);
        $this->assertEquals('PK B', $ra[1]['ten']);
    }

    protected $svc;

    protected function setUp()
    {
        parent::setUp();
        $this->svc = new GiaoBanMetricService();
    }

    /** @test */
    public function service_count_diim_type_other_of_matches_null_or_out_of_list()
    {
        list($sql, $binds) = $this->svc->buildServiceCountSql('2026-07-07 07:00:00', '2026-07-08 07:00:00',
            ['execute_department_ids' => [46], 'service_type_ids' => [3], 'diim_type_other_of' => [1, 2, 3]]);
        $this->assertContains('JOIN his_service hs ON hs.id = ss.service_id', $sql);
        $this->assertContains('(hs.diim_type_id IS NULL OR hs.diim_type_id NOT IN (1,2,3))', $sql);
    }

    /** @test */
    public function service_count_test_type_other_of_matches_null_or_out_of_list()
    {
        list($sql, $binds) = $this->svc->buildServiceCountSql('2026-07-07 07:00:00', '2026-07-08 07:00:00',
            ['execute_department_ids' => [43], 'service_type_ids' => [2], 'test_type_other_of' => [1, 3, 7]]);
        $this->assertContains('JOIN his_service hs ON hs.id = ss.service_id', $sql);
        $this->assertContains('(hs.test_type_id IS NULL OR hs.test_type_id NOT IN (1,3,7))', $sql);
    }

    /** @test */
    public function to_his_time_converts_datetime_to_numeric_string()
    {
        $this->assertEquals('20260708070000', $this->svc->toHisTime('2026-07-08 07:00:00'));
    }

    /** @test */
    public function census_sql_has_distinct_bind_names_and_inpatient_filter()
    {
        list($sql, $binds) = $this->svc->buildCensusSql('2026-07-08 07:00:00');
        $this->assertContains('tdl_treatment_type_id = 3', $sql);
        $this->assertContains('NOT EXISTS', $sql);
        $this->assertEquals(
            ['ts1' => '20260708070000', 'ts2' => '20260708070000', 'ts3' => '20260708070000'],
            $binds
        );
    }

    /** @test */
    public function movement_sql_counts_in_and_transfer_in_by_previous_id()
    {
        list($sql, $binds) = $this->svc->buildMovementInSql('2026-07-07 07:00:00', '2026-07-08 07:00:00');
        $this->assertContains('previous_id IS NULL', $sql);
        $this->assertContains('previous_id IS NOT NULL', $sql);
        $this->assertEquals(['from_time' => '20260707070000', 'to_time' => '20260708070000'], $binds);
    }

    /** @test */
    public function end_type_sql_groups_by_last_department()
    {
        list($sql, $binds) = $this->svc->buildEndTypeSql('2026-07-07 07:00:00', '2026-07-08 07:00:00');
        $this->assertContains('last_department_id', $sql);
        $this->assertContains('treatment_end_type_code', $sql);
    }

    /** @test */
    public function service_count_sql_applies_filters()
    {
        list($sql, $binds) = $this->svc->buildServiceCountSql(
            '2026-07-07 07:00:00', '2026-07-08 07:00:00',
            ['service_type_ids' => [11], 'priority_min' => 2, 'request_department_id' => 5]
        );
        $this->assertContains('service_type_id IN (11)', $sql);
        $this->assertContains('priority >= :priority_min', $sql);
        $this->assertContains('request_department_id = :req_dept', $sql);
        $this->assertEquals(2, $binds['priority_min']);
        $this->assertEquals(5, $binds['req_dept']);
    }

    /** @test */
    public function normalize_rows_lowercases_oracle_columns()
    {
        $rows = $this->svc->normalizeRows([(object) ['DEPARTMENT_ID' => 1, 'SO_BN' => 9]]);
        $this->assertEquals(1, $rows[0]->department_id);
        $this->assertEquals(9, $rows[0]->so_bn);
    }

    /** @test */
    public function internal_transfer_sql_counts_transfers_within_dept_set()
    {
        list($sql, $binds) = $this->svc->buildInternalTransferSql('2026-07-07 07:00:00', '2026-07-08 07:00:00', [73, 54]);
        $this->assertContains('p.department_id IN (73,54)', $sql);
        $this->assertContains('nx.department_id IN (73,54)', $sql);
        $this->assertContains('nx.department_id <> p.department_id', $sql);
        $this->assertEquals(['from_time' => '20260707070000', 'to_time' => '20260708070000'], $binds);
    }

    /** @test */
    public function exam_visit_sql_filters_exam_type_and_execute_dept()
    {
        list($sql, $binds) = $this->svc->buildExamVisitSql('2026-07-07 07:00:00', '2026-07-08 07:00:00', [27]);
        $this->assertContains('is_main_exam = 1', $sql);
        $this->assertContains('execute_department_id IN (27)', $sql);
        $this->assertContains('service_req_type_id = :kham_type', $sql);
        $this->assertNotContains('his_treatment', $sql);
        $this->assertEquals('20260707070000', $binds['from_time']);
        $this->assertArrayHasKey('kham_type', $binds);
    }

    /** @test */
    public function exam_visit_sql_joins_treatment_for_type_and_patient_filters()
    {
        list($sql, $binds) = $this->svc->buildExamVisitSql('2026-07-07 07:00:00', '2026-07-08 07:00:00', [27],
            ['treatment_type_ids' => [3], 'patient_type_ids' => [82]]);
        $this->assertContains('JOIN his_treatment t ON t.id = sr.treatment_id', $sql);
        $this->assertContains('t.tdl_treatment_type_id IN (3)', $sql);
        $this->assertContains('t.tdl_patient_type_id IN (82)', $sql);
    }

    /** @test */
    public function service_count_supports_execute_department_ids_list()
    {
        list($sql, $binds) = $this->svc->buildServiceCountSql(
            '2026-07-07 07:00:00', '2026-07-08 07:00:00',
            ['service_type_ids' => [2], 'execute_department_ids' => [43, 62]]
        );
        $this->assertContains('tdl_execute_department_id IN (43,62)', $sql);
        $this->assertContains('tdl_service_type_id IN (2)', $sql);
    }

    /** @test */
    public function sum_over_depts_sums_map_values()
    {
        $map = [73 => 91, 54 => 54, 27 => 3];
        $this->assertSame(145.0, $this->svc->sumOverDepts($map, [73, 54]));
        $this->assertSame(0.0, $this->svc->sumOverDepts($map, [999]));
    }

    /** @test */
    public function sum_end_type_sums_matching_dept_and_codes()
    {
        $rows = [
            (object) ['department_id' => 73, 'end_code' => 'RV', 'so_bn' => 10],
            (object) ['department_id' => 73, 'end_code' => 'CV', 'so_bn' => 2],
            (object) ['department_id' => 54, 'end_code' => 'RV', 'so_bn' => 5],
        ];
        $this->assertSame(15.0, $this->svc->sumEndType($rows, [73, 54], ['RV']));
        $this->assertSame(2.0, $this->svc->sumEndType($rows, [73, 54], ['CV']));
    }

    /** @test */
    public function service_count_joins_his_service_for_diim_type()
    {
        list($sql, $binds) = $this->svc->buildServiceCountSql('2026-07-07 07:00:00', '2026-07-08 07:00:00',
            ['execute_department_ids' => [46], 'service_type_ids' => [3], 'diim_type_ids' => [1, 2]]);
        $this->assertContains('JOIN his_service hs ON hs.id = ss.service_id', $sql);
        $this->assertContains('hs.diim_type_id IN (1,2)', $sql);
    }

    /** @test */
    public function service_count_joins_his_service_for_test_type()
    {
        list($sql, $binds) = $this->svc->buildServiceCountSql('2026-07-07 07:00:00', '2026-07-08 07:00:00',
            ['execute_department_ids' => [43], 'service_type_ids' => [2], 'test_type_ids' => [1, 3]]);
        $this->assertContains('JOIN his_service hs ON hs.id = ss.service_id', $sql);
        $this->assertContains('hs.test_type_id IN (1,3)', $sql);
    }

    /** @test */
    public function service_count_no_service_join_when_no_subtype_filter()
    {
        list($sql, $binds) = $this->svc->buildServiceCountSql('2026-07-07 07:00:00', '2026-07-08 07:00:00',
            ['service_type_ids' => [2], 'execute_department_ids' => [43]]);
        $this->assertNotContains('JOIN his_service hs', $sql);
    }

    /** @test */
    public function exam_visit_sql_filters_by_end_type_code()
    {
        list($sql, $binds) = $this->svc->buildExamVisitSql('2026-07-07 07:00:00', '2026-07-08 07:00:00', [27],
            ['end_type_codes' => ['CC', 'CV']]);
        $this->assertContains('JOIN his_treatment t ON t.id = sr.treatment_id', $sql);
        $this->assertContains('JOIN his_treatment_end_type et ON et.id = t.treatment_end_type_id', $sql);
        $this->assertContains("et.treatment_end_type_code IN ('CC','CV')", $sql);
    }

    /** @test */
    public function exam_visit_end_type_codes_whitelisted()
    {
        list($sql, $binds) = $this->svc->buildExamVisitSql('2026-07-07 07:00:00', '2026-07-08 07:00:00', [27],
            ['end_type_codes' => ["CC'; DROP--", 'cv']]);
        $this->assertContains("et.treatment_end_type_code IN ('CC','CV')", $sql);
        $this->assertNotContains('DROP', $sql);
    }

    /** @test */
    public function bed_capacity_sql_snapshots_beds_at_time()
    {
        list($sql, $binds) = $this->svc->buildBedCapacitySql('2026-07-09 07:00:00');
        $this->assertContains('his_bed', $sql);
        $this->assertContains('his_treatment_bed_room', $sql);
        $this->assertContains('tdl_treatment_type_id IN (3,4)', $sql);
        $this->assertContains('total_beds', $sql);
        $this->assertContains('used_beds', $sql);
        $this->assertEquals(['at1' => '20260709070000', 'at2' => '20260709070000', 'at3' => '20260709070000'], $binds);
    }
}
