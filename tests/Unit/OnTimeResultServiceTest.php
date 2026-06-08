<?php
namespace Tests\Unit;

use Tests\TestCase;
use App\Services\OnTimeResultService;

class OnTimeResultServiceTest extends TestCase
{
    protected $service;

    protected function setUp()
    {
        parent::setUp();
        $this->service = new OnTimeResultService();
    }

    /** @test */
    public function classify_returns_chua_tra_when_finish_null()
    {
        $row = (object)['finish_time' => null, 'actual_minutes' => null, 'estimate_duration' => 60];
        $this->assertEquals('chua_tra', $this->service->classify($row));
    }

    /** @test */
    public function classify_returns_bat_thuong_when_actual_negative()
    {
        $row = (object)['finish_time' => 20260601070000, 'actual_minutes' => -5, 'estimate_duration' => 60];
        $this->assertEquals('bat_thuong', $this->service->classify($row));
    }

    /** @test */
    public function classify_returns_dung_hen_when_actual_le_estimate()
    {
        $row = (object)['finish_time' => 20260601070000, 'actual_minutes' => 60, 'estimate_duration' => 60];
        $this->assertEquals('dung_hen', $this->service->classify($row));
    }

    /** @test */
    public function classify_returns_tre_hen_when_actual_gt_estimate()
    {
        $row = (object)['finish_time' => 20260601090000, 'actual_minutes' => 124, 'estimate_duration' => 89];
        $this->assertEquals('tre_hen', $this->service->classify($row));
    }
}
