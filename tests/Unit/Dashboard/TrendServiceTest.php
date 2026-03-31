<?php

namespace Tests\Unit\Dashboard;

use Tests\TestCase;
use App\Services\Dashboard\TrendService;
use Carbon\Carbon;

class TrendServiceTest extends TestCase
{
    protected $service;

    protected function setUp()
    {
        parent::setUp();
        $this->service = new TrendService();
    }

    /** @test */
    public function it_extracts_day_from_intruction_time()
    {
        // INTRUCTION_TIME = 20260315143000 → day_val = 20260315
        $rows = [(object)['day_val' => 20260315, 'total' => 120]];
        $result = $this->service->formatTrendRows($rows, 'daily');

        $this->assertEquals('15/03', $result[0]['label']);
        $this->assertEquals(120, $result[0]['value']);
    }

    /** @test */
    public function it_extracts_month_from_intruction_time()
    {
        // month_val = 202603 → label = '03/2026'
        $rows = [(object)['month_val' => 202603, 'total' => 1500]];
        $result = $this->service->formatTrendRows($rows, 'monthly');

        $this->assertEquals('03/2026', $result[0]['label']);
        $this->assertEquals(1500, $result[0]['value']);
    }

    /** @test */
    public function it_calculates_overload_status_overload()
    {
        $status = $this->service->calculateOverloadStatus(181, 150);
        $this->assertEquals('overload', $status['status']); // 181/150 = 1.207 > 1.2
    }

    /** @test */
    public function it_calculates_overload_status_normal()
    {
        $status = $this->service->calculateOverloadStatus(150, 150);
        $this->assertEquals('normal', $status['status']); // ratio = 1.0
    }

    /** @test */
    public function it_calculates_overload_status_underload()
    {
        $status = $this->service->calculateOverloadStatus(100, 150);
        $this->assertEquals('underload', $status['status']); // ratio = 0.67 < 0.8
    }

    /** @test */
    public function it_calculates_previous_period_for_daily_mode()
    {
        // Cùng khoảng ngày nhưng tháng trước
        $prev = $this->service->buildPreviousPeriod('2026-03-05', '2026-03-20', 'daily');
        $this->assertEquals('2026-02-05', $prev['from']);
        $this->assertEquals('2026-02-20', $prev['to']);
    }

    /** @test */
    public function it_calculates_previous_period_for_monthly_mode()
    {
        $prev = $this->service->buildPreviousPeriod('2026-01-01', '2026-12-31', 'monthly');
        $this->assertEquals('2025-01-01', $prev['from']);
        $this->assertEquals('2025-12-31', $prev['to']);
    }
}
