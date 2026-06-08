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

    /** @test */
    public function summarize_computes_kpi_totals_and_percentages()
    {
        $rows = [
            (object)['finish_time'=>1,'actual_minutes'=>50,'estimate_duration'=>60,'service_type_id'=>2,'service_type_name'=>'XN','execute_room_id'=>10,'execute_room_name'=>'P.XN','service_id'=>100,'service_name'=>'SH','day_val'=>20260601],
            (object)['finish_time'=>1,'actual_minutes'=>90,'estimate_duration'=>60,'service_type_id'=>2,'service_type_name'=>'XN','execute_room_id'=>10,'execute_room_name'=>'P.XN','service_id'=>100,'service_name'=>'SH','day_val'=>20260601],
            (object)['finish_time'=>1,'actual_minutes'=>120,'estimate_duration'=>60,'service_type_id'=>2,'service_type_name'=>'XN','execute_room_id'=>10,'execute_room_name'=>'P.XN','service_id'=>100,'service_name'=>'SH','day_val'=>20260601],
            (object)['finish_time'=>null,'actual_minutes'=>null,'estimate_duration'=>60,'service_type_id'=>2,'service_type_name'=>'XN','execute_room_id'=>10,'execute_room_name'=>'P.XN','service_id'=>100,'service_name'=>'SH','day_val'=>20260601],
            (object)['finish_time'=>1,'actual_minutes'=>-3,'estimate_duration'=>60,'service_type_id'=>2,'service_type_name'=>'XN','execute_room_id'=>10,'execute_room_name'=>'P.XN','service_id'=>100,'service_name'=>'SH','day_val'=>20260601],
        ];
        $kpi = $this->service->summarize($rows)['kpi'];
        $this->assertEquals(5, $kpi['tong_co_hen']);
        $this->assertEquals(3, $kpi['da_tra_hop_le']);
        $this->assertEquals(1, $kpi['dung_hen']);
        $this->assertEquals(2, $kpi['tre_hen']);
        $this->assertEquals(1, $kpi['chua_tra']);
        $this->assertEquals(1, $kpi['bat_thuong']);
        $this->assertEquals(33.3, $kpi['pct_dung_hen']);
        $this->assertEquals(66.7, $kpi['pct_tre_hen']);
        $this->assertEquals(87, $kpi['tg_tra_tb']);
    }

    /** @test */
    public function summarize_handles_empty_denominator_without_division_error()
    {
        $rows = [
            (object)['finish_time'=>null,'actual_minutes'=>null,'estimate_duration'=>60,'service_type_id'=>2,'service_type_name'=>'XN','execute_room_id'=>10,'execute_room_name'=>'P.XN','service_id'=>100,'service_name'=>'SH','day_val'=>20260601],
        ];
        $kpi = $this->service->summarize($rows)['kpi'];
        $this->assertEquals(0, $kpi['pct_dung_hen']);
        $this->assertEquals(0, $kpi['pct_tre_hen']);
        $this->assertEquals(0, $kpi['tg_tra_tb']);
    }

    /** @test */
    public function groupby_aggregates_each_group_with_counts_and_percentages()
    {
        $rows = [
            (object)['finish_time'=>1,'actual_minutes'=>50,'estimate_duration'=>60,'service_type_id'=>2,'service_type_name'=>'XN','execute_room_id'=>10,'execute_room_name'=>'P.XN','service_id'=>100,'service_name'=>'SH','day_val'=>20260601],
            (object)['finish_time'=>1,'actual_minutes'=>90,'estimate_duration'=>60,'service_type_id'=>2,'service_type_name'=>'XN','execute_room_id'=>10,'execute_room_name'=>'P.XN','service_id'=>100,'service_name'=>'SH','day_val'=>20260601],
            (object)['finish_time'=>1,'actual_minutes'=>10,'estimate_duration'=>60,'service_type_id'=>3,'service_type_name'=>'CDHA','execute_room_id'=>20,'execute_room_name'=>'P.CT','service_id'=>200,'service_name'=>'CT','day_val'=>20260602],
        ];
        $bk = $this->service->groupBy($rows, 'service_type_id', 'service_type_name');
        $this->assertCount(2, $bk);
        $this->assertEquals('XN', $bk[0]['name']);
        $this->assertEquals(2, $bk[0]['tong']);
        $this->assertEquals(1, $bk[0]['dung_hen']);
        $this->assertEquals(1, $bk[0]['tre_hen']);
        $this->assertEquals(50.0, $bk[0]['pct_tre_hen']);
        $this->assertEquals('CDHA', $bk[1]['name']);
    }
}
