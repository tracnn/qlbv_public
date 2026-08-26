<?php

namespace Tests\Feature\Dashboard;

use Tests\TestCase;
use Tests\Support\UserGiaCoQuyen;
use App\Services\Dashboard\OperatingRoomService;

/**
 * Lop gia ke thua service that - xem ghi chu day du o DoctorStatsControllerTest.
 * Tom tat: Mockery 0.9.11 no tren PHP 7.4 khi reflect method co kieu tra ve ": array".
 */
class FakeOperatingRoomService extends OperatingRoomService
{
    public $casesPerRoomReturn = array();
    public $utilizationReturn  = array();

    /** So lan goi tung method - thay cho ->once() cua Mockery */
    public $soLanGoi = array('getCasesPerRoom' => 0, 'getUtilization' => 0);

    /** Tham so lan goi gan nhat - thay cho ->with() cua Mockery */
    public $thamSoCuoi = array();

    public function getCasesPerRoom(string $from, string $to): array
    {
        $this->ghiNhan('getCasesPerRoom', func_get_args());

        return $this->casesPerRoomReturn;
    }

    public function getUtilization(string $from, string $to): array
    {
        $this->ghiNhan('getUtilization', func_get_args());

        return $this->utilizationReturn;
    }

    private function ghiNhan($method, array $thamSo)
    {
        $this->soLanGoi[$method]++;
        $this->thamSoCuoi[$method] = $thamSo;
    }
}

class OperatingRoomControllerTest extends TestCase
{
    /** @var FakeOperatingRoomService */
    private $gia;

    protected function setUp()
    {
        parent::setUp();

        $this->gia = new FakeOperatingRoomService();
        $this->app->instance(OperatingRoomService::class, $this->gia);
    }

    /** @test */
    public function cases_per_room_endpoint_returns_json()
    {
        $this->gia->casesPerRoomReturn = array(
            'rooms'  => array('Phòng mổ 1'),
            'dates'  => array('01/03'),
            'matrix' => array(array(5)),
        );

        $response = $this->goi('/dashboard/operating-room/cases-per-room?from=2026-03-01&to=2026-03-31');

        $response->assertStatus(200)
                 ->assertJsonStructure(array('rooms', 'dates', 'matrix'));

        $this->assertSame(1, $this->gia->soLanGoi['getCasesPerRoom']);
        $this->assertSame(array('2026-03-01', '2026-03-31'), $this->gia->thamSoCuoi['getCasesPerRoom']);
    }

    /** @test */
    public function utilization_endpoint_returns_json()
    {
        $this->gia->utilizationReturn = array(
            array(
                'room_name'       => 'Phòng mổ 1',
                'total_cases'     => 45,
                'total_minutes'   => 2160,
                'working_days'    => 22,
                'utilization_pct' => 20.45,
                'status'          => 'underload',
            ),
        );

        $response = $this->goi('/dashboard/operating-room/utilization?from=2026-03-01&to=2026-03-31');

        $response->assertStatus(200)
                 ->assertJsonStructure(array('data' => array(array('room_name', 'utilization_pct', 'status'))));

        $this->assertSame(1, $this->gia->soLanGoi['getUtilization']);
        $this->assertSame(array('2026-03-01', '2026-03-31'), $this->gia->thamSoCuoi['getUtilization']);
    }

    /** @test */
    public function endpoints_require_date_params()
    {
        $response = $this->goi('/dashboard/operating-room/cases-per-room');

        $response->assertStatus(422);
        $this->assertSame(0, $this->gia->soLanGoi['getCasesPerRoom'], 'Validation truot ma van cham vao service');
    }

    private function goi($duongDan)
    {
        return $this->actingAs(UserGiaCoQuyen::tao())->getJson($duongDan);
    }
}
