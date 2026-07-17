<?php

namespace Tests\Unit\Dashboard;

use Tests\TestCase;
use App\Services\Dashboard\Xml3176DashboardService;
use Carbon\Carbon;

class Xml3176DashboardServiceTest extends TestCase
{
    protected $service;

    protected function setUp()
    {
        parent::setUp();
        $this->service = new Xml3176DashboardService();
    }

    /** @test */
    public function calc_percent_returns_zero_when_total_is_zero()
    {
        $this->assertEquals(0.0, $this->service->calcPercent(5, 0));
    }

    /** @test */
    public function calc_percent_computes_correctly()
    {
        $this->assertEquals(25.0, $this->service->calcPercent(25, 100));
        $this->assertEquals(33.33, $this->service->calcPercent(1, 3));
    }

    /** @test */
    public function normalize_date_range_uses_YmdHi_for_string_fields()
    {
        $r = $this->service->normalizeDateRange('date_out', '2026-07-01', '2026-07-17');

        $this->assertEquals('ngay_ra', $r['field']);
        $this->assertFalse($r['is_timestamp']);
        $this->assertEquals('202607010000', $r['from']);
        $this->assertEquals('202607172359', $r['to']);
    }

    /** @test */
    public function normalize_date_range_uses_timestamp_for_created_at()
    {
        $r = $this->service->normalizeDateRange('date_create', '2026-07-01', '2026-07-17');

        $this->assertEquals('created_at', $r['field']);
        $this->assertTrue($r['is_timestamp']);
        $this->assertEquals('2026-07-01 00:00:00', $r['from']);
        $this->assertEquals('2026-07-17 23:59:59', $r['to']);
    }

    /** @test */
    public function normalize_date_range_maps_all_date_types()
    {
        $this->assertEquals('ngay_vao', $this->service->normalizeDateRange('date_in', '2026-07-01', '2026-07-17')['field']);
        $this->assertEquals('ngay_ttoan', $this->service->normalizeDateRange('date_payment', '2026-07-01', '2026-07-17')['field']);
    }

    /** @test */
    public function build_funnel_steps_computes_pct_of_previous()
    {
        $steps = $this->service->buildFunnelSteps([
            'imported'    => 100,
            'no_critical' => 80,
            'exported'    => 60,
            'signed'      => 30,
            'submitted'   => 15,
        ]);

        $this->assertCount(5, $steps);

        $this->assertEquals('imported', $steps[0]['key']);
        $this->assertEquals(100, $steps[0]['count']);
        $this->assertEquals(100.0, $steps[0]['pct_of_prev']); // bậc đầu luôn 100%

        $this->assertEquals('no_critical', $steps[1]['key']);
        $this->assertEquals(80.0, $steps[1]['pct_of_prev']);  // 80/100

        $this->assertEquals(75.0, $steps[2]['pct_of_prev']);  // 60/80
        $this->assertEquals(50.0, $steps[3]['pct_of_prev']);  // 30/60
        $this->assertEquals(50.0, $steps[4]['pct_of_prev']);  // 15/30
    }

    /** @test */
    public function build_funnel_steps_returns_zero_pct_when_previous_is_zero()
    {
        $steps = $this->service->buildFunnelSteps([
            'imported'    => 0,
            'no_critical' => 0,
            'exported'    => 0,
            'signed'      => 0,
            'submitted'   => 0,
        ]);

        $this->assertEquals(100.0, $steps[0]['pct_of_prev']);
        $this->assertEquals(0.0, $steps[1]['pct_of_prev']);
        $this->assertEquals(0.0, $steps[4]['pct_of_prev']);
    }

    /** @test */
    public function build_funnel_steps_has_vietnamese_labels()
    {
        $steps = $this->service->buildFunnelSteps([
            'imported' => 10, 'no_critical' => 9, 'exported' => 8, 'signed' => 7, 'submitted' => 6,
        ]);

        $this->assertEquals('Đã import', $steps[0]['label']);
        $this->assertEquals('Đã gửi BHXH', $steps[4]['label']);
    }

    /** @test */
    public function build_funnel_steps_normalizes_missing_keys_and_numeric_strings()
    {
        $steps = $this->service->buildFunnelSteps([
            'imported' => '100', 'no_critical' => '50',   // DB trả về string
            // exported, signed, submitted: thiếu key
        ]);

        $this->assertSame(100, $steps[0]['count']);       // string → int
        $this->assertEquals(50.0, $steps[1]['pct_of_prev']);
        $this->assertSame(0, $steps[2]['count']);         // key thiếu → 0
        $this->assertEquals(0.0, $steps[2]['pct_of_prev']);
    }

    /** @test */
    public function aging_bucket_ranges_returns_four_buckets_with_correct_boundaries()
    {
        $today   = Carbon::create(2026, 7, 17, 12, 0, 0);
        $buckets = $this->service->agingBucketRanges($today);

        $this->assertCount(4, $buckets);

        // 0–7 ngày: ngay_ra từ 10/07 đến hết 17/07
        $this->assertEquals('0-7', $buckets[0]['key']);
        $this->assertEquals('202607100000', $buckets[0]['from']);
        $this->assertEquals('202607172359', $buckets[0]['to']);

        // 8–15 ngày: ngay_ra từ 02/07 đến hết 09/07
        $this->assertEquals('8-15', $buckets[1]['key']);
        $this->assertEquals('202607020000', $buckets[1]['from']);
        $this->assertEquals('202607092359', $buckets[1]['to']);

        // 16–30 ngày: ngay_ra từ 17/06 đến hết 01/07
        $this->assertEquals('16-30', $buckets[2]['key']);
        $this->assertEquals('202606170000', $buckets[2]['from']);
        $this->assertEquals('202607012359', $buckets[2]['to']);

        // >30 ngày: từ rất xa đến hết 16/06
        $this->assertEquals('30+', $buckets[3]['key']);
        $this->assertEquals('000000000000', $buckets[3]['from']);
        $this->assertEquals('202606162359', $buckets[3]['to']);
    }

    /** @test */
    public function aging_bucket_ranges_has_vietnamese_labels()
    {
        $buckets = $this->service->agingBucketRanges(Carbon::create(2026, 7, 17, 12, 0, 0));

        $this->assertEquals('0–7 ngày', $buckets[0]['label']);
        $this->assertEquals('Trên 30 ngày', $buckets[3]['label']);
    }

    /** @test */
    public function build_pareto_data_sorts_desc_and_computes_cumulative_pct()
    {
        $rows = [
            (object) ['xml' => 'XML1', 'error_code' => 'E001', 'total' => 20],
            (object) ['xml' => 'XML2', 'error_code' => 'E002', 'total' => 60],
            (object) ['xml' => 'XML3', 'error_code' => 'E003', 'total' => 20],
        ];
        $catalogMap = [
            'XML1|E001' => (object) ['id' => 11, 'error_name' => 'Loi 1', 'critical_error' => 1],
            'XML2|E002' => (object) ['id' => 22, 'error_name' => 'Loi 2', 'critical_error' => 0],
            'XML3|E003' => (object) ['id' => 33, 'error_name' => 'Loi 3', 'critical_error' => 1],
        ];

        $result = $this->service->buildParetoData($rows, $catalogMap);

        // Sắp xếp giảm dần theo total
        $this->assertEquals('E002', $result[0]['error_code']);
        $this->assertEquals(60, $result[0]['total']);
        $this->assertEquals(22, $result[0]['catalog_id']);
        $this->assertEquals('Loi 2', $result[0]['error_name']);
        $this->assertFalse($result[0]['critical_error']);

        // % tích luỹ: 60/100 = 60 → 80 → 100
        $this->assertEquals(60.0, $result[0]['cumulative_pct']);
        $this->assertEquals(80.0, $result[1]['cumulative_pct']);
        $this->assertEquals(100.0, $result[2]['cumulative_pct']);

        $this->assertTrue($result[1]['critical_error']);
    }

    /** @test */
    public function build_pareto_data_skips_rows_missing_from_catalog()
    {
        $rows = [
            (object) ['xml' => 'XML1', 'error_code' => 'E001', 'total' => 10],
            (object) ['xml' => 'XML9', 'error_code' => 'GHOST', 'total' => 99],
        ];
        $catalogMap = [
            'XML1|E001' => (object) ['id' => 11, 'error_name' => 'Loi 1', 'critical_error' => 1],
        ];

        $result = $this->service->buildParetoData($rows, $catalogMap);

        $this->assertCount(1, $result);
        $this->assertEquals('E001', $result[0]['error_code']);
    }

    /** @test */
    public function build_pareto_data_returns_empty_array_for_no_rows()
    {
        $this->assertEquals([], $this->service->buildParetoData([], []));
    }

    /** @test */
    public function build_pareto_data_limits_to_top_15()
    {
        $rows = [];
        $catalogMap = [];
        for ($i = 1; $i <= 20; $i++) {
            $rows[] = (object) ['xml' => 'XML1', 'error_code' => 'E' . $i, 'total' => $i];
            $catalogMap['XML1|E' . $i] = (object) ['id' => $i, 'error_name' => 'Loi ' . $i, 'critical_error' => 0];
        }

        $result = $this->service->buildParetoData($rows, $catalogMap);

        $this->assertCount(15, $result);
        $this->assertEquals('E20', $result[0]['error_code']); // lớn nhất đứng đầu
        $this->assertEquals('E6', $result[14]['error_code']);
        $this->assertEquals(100.0, $result[14]['cumulative_pct']); // tổng tính sau khi cắt top-15
    }
}
