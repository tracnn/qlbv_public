<?php

namespace Tests\Unit\Dashboard;

use Tests\TestCase;
use App\Services\Dashboard\OperatingRoomService;
use Carbon\Carbon;

class OperatingRoomServiceTest extends TestCase
{
    protected $service;

    protected function setUp()
    {
        parent::setUp();
        $this->service = new OperatingRoomService();
    }

    /** @test */
    public function it_calculates_duration_in_minutes_correctly()
    {
        // start: 20260331083000, end: 20260331090000 → 30 phút
        $minutes = $this->service->calcDurationMinutes(20260331083000, 20260331090000);
        $this->assertEquals(30, $minutes);
    }

    /** @test */
    public function it_returns_zero_for_invalid_times()
    {
        $minutes = $this->service->calcDurationMinutes(null, null);
        $this->assertEquals(0, $minutes);
    }

    /** @test */
    public function it_calculates_utilization_percentage()
    {
        // 240 phút sử dụng / (1 ngày × 480 phút) = 50%
        $pct = $this->service->calcUtilizationPct(240, 1);
        $this->assertEquals(50.0, $pct);
    }

    /** @test */
    public function it_determines_status_optimal()
    {
        $this->assertEquals('optimal',  $this->service->getUtilizationStatus(85.0));
    }

    /** @test */
    public function it_determines_status_overload()
    {
        $this->assertEquals('overload',  $this->service->getUtilizationStatus(105.0));
    }

    /** @test */
    public function it_determines_status_underload()
    {
        $this->assertEquals('underload', $this->service->getUtilizationStatus(60.0));
    }

    /** @test */
    public function it_builds_heatmap_matrix_correctly()
    {
        $rows = [
            (object)['room_name' => 'Phòng 1', 'day_val' => 20260301, 'total_cases' => 5],
            (object)['room_name' => 'Phòng 1', 'day_val' => 20260302, 'total_cases' => 3],
            (object)['room_name' => 'Phòng 2', 'day_val' => 20260301, 'total_cases' => 4],
        ];

        $result = $this->service->buildHeatmapData($rows);

        $this->assertEquals(['Phòng 1', 'Phòng 2'], $result['rooms']);
        $this->assertCount(2, $result['dates']);
        $this->assertEquals([[5, 3], [4, 0]], $result['matrix']);
    }
}
