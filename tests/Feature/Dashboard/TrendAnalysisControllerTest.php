<?php

namespace Tests\Feature\Dashboard;

use Tests\TestCase;
use Tests\Support\UserGiaCoQuyen;
use App\Services\Dashboard\TrendService;

/**
 * Lop gia ke thua service that - xem ghi chu day du o DoctorStatsControllerTest.
 * Tom tat: Mockery 0.9.11 no tren PHP 7.4 khi reflect method co kieu tra ve ": array".
 */
class FakeTrendService extends TrendService
{
    public $trendChartReturn      = array();
    public $patientsPerHourReturn = array();
    public $overloadAlertReturn   = array();

    /** So lan goi tung method - thay cho ->once() cua Mockery */
    public $soLanGoi = array('getTrendChart' => 0, 'getPatientsPerHour' => 0, 'getOverloadAlert' => 0);

    /** Tham so lan goi gan nhat - thay cho ->with() cua Mockery */
    public $thamSoCuoi = array();

    public function getTrendChart(string $from, string $to, string $mode, string $metric): array
    {
        $this->ghiNhan('getTrendChart', func_get_args());

        return $this->trendChartReturn;
    }

    public function getPatientsPerHour(string $from, string $to, ?int $departmentId = null): array
    {
        $this->ghiNhan('getPatientsPerHour', func_get_args());

        return $this->patientsPerHourReturn;
    }

    public function getOverloadAlert(string $date): array
    {
        $this->ghiNhan('getOverloadAlert', func_get_args());

        return $this->overloadAlertReturn;
    }

    private function ghiNhan($method, array $thamSo)
    {
        $this->soLanGoi[$method]++;
        $this->thamSoCuoi[$method] = $thamSo;
    }
}

class TrendAnalysisControllerTest extends TestCase
{
    /** @var FakeTrendService */
    private $gia;

    protected function setUp()
    {
        parent::setUp();

        $this->gia = new FakeTrendService();
        $this->app->instance(TrendService::class, $this->gia);
    }

    /** @test */
    public function trend_chart_endpoint_returns_json()
    {
        $this->gia->trendChartReturn = array(
            'labels'   => array('01/03'),
            'current'  => array(120),
            'previous' => array(100),
        );

        $response = $this->goi('/dashboard/trends/chart?from=2026-03-01&to=2026-03-31&mode=daily&metric=examinations');

        $response->assertStatus(200)
                 ->assertJsonStructure(array('labels', 'current', 'previous'));

        $this->assertSame(1, $this->gia->soLanGoi['getTrendChart']);
        // Bon tham so string lien nhau: hoan vi mode/metric van ra 200 nen phai khoa thu tu
        $this->assertSame(
            array('2026-03-01', '2026-03-31', 'daily', 'examinations'),
            $this->gia->thamSoCuoi['getTrendChart']
        );
    }

    /** @test */
    public function trend_chart_validates_mode_param()
    {
        $response = $this->goi('/dashboard/trends/chart?from=2026-03-01&to=2026-03-31&mode=invalid&metric=examinations');

        $response->assertStatus(422);
        $this->assertSame(0, $this->gia->soLanGoi['getTrendChart'], 'mode sai ma van cham vao service');
    }

    /** @test */
    public function patients_per_hour_endpoint_returns_json()
    {
        $this->gia->patientsPerHourReturn = array(
            'average_per_hour' => 15.2,
            'by_hour'          => array(array('hour' => 8, 'count' => 45)),
        );

        $response = $this->goi('/dashboard/trends/patients-per-hour?from=2026-03-01&to=2026-03-31');

        $response->assertStatus(200)
                 ->assertJsonStructure(array('average_per_hour', 'by_hour'));

        $this->assertSame(1, $this->gia->soLanGoi['getPatientsPerHour']);
        $this->assertSame(array('2026-03-01', '2026-03-31', null), $this->gia->thamSoCuoi['getPatientsPerHour']);
    }

    /** @test */
    public function overload_alert_endpoint_returns_json()
    {
        $this->gia->overloadAlertReturn = array(
            'today_count' => 180,
            'average_30d' => 150.0,
            'ratio'       => 1.2,
            'status'      => 'normal',
        );

        $response = $this->goi('/dashboard/trends/overload-alert?date=2026-03-31');

        $response->assertStatus(200)
                 ->assertJsonStructure(array('today_count', 'average_30d', 'ratio', 'status'));

        $this->assertSame(1, $this->gia->soLanGoi['getOverloadAlert']);
        $this->assertSame(array('2026-03-31'), $this->gia->thamSoCuoi['getOverloadAlert']);
    }

    private function goi($duongDan)
    {
        return $this->actingAs(UserGiaCoQuyen::tao())->getJson($duongDan);
    }
}
