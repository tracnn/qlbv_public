<?php

namespace Tests\Unit\GiaoBan;

use Tests\TestCase;
use App\Services\GiaoBan\GiaoBanMetricService;

class GiaoBanMetricServiceTest extends TestCase
{
    protected $svc;

    protected function setUp()
    {
        parent::setUp();
        $this->svc = new GiaoBanMetricService();
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
}
