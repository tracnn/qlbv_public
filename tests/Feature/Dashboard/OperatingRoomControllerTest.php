<?php

namespace Tests\Feature\Dashboard;

use Tests\TestCase;
use App\Services\Dashboard\OperatingRoomService;
use Mockery;

class OperatingRoomControllerTest extends TestCase
{
    /** @test */
    public function cases_per_room_endpoint_returns_json()
    {
        $mock = Mockery::mock(OperatingRoomService::class);
        $mock->shouldReceive('getCasesPerRoom')->once()->andReturn([
            'rooms'  => ['Phòng mổ 1'],
            'dates'  => ['01/03'],
            'matrix' => [[5]],
        ]);
        $this->app->instance(OperatingRoomService::class, $mock);

        $response = $this->actingAs($this->getAdminUser())
                         ->getJson('/dashboard/operating-room/cases-per-room?from=2026-03-01&to=2026-03-31');

        $response->assertStatus(200)
                 ->assertJsonStructure(['rooms', 'dates', 'matrix']);
    }

    /** @test */
    public function utilization_endpoint_returns_json()
    {
        $mock = Mockery::mock(OperatingRoomService::class);
        $mock->shouldReceive('getUtilization')->once()->andReturn([
            [
                'room_name' => 'Phòng mổ 1', 'total_cases' => 45,
                'total_minutes' => 2160, 'working_days' => 22,
                'utilization_pct' => 20.45, 'status' => 'underload'
            ]
        ]);
        $this->app->instance(OperatingRoomService::class, $mock);

        $response = $this->actingAs($this->getAdminUser())
                         ->getJson('/dashboard/operating-room/utilization?from=2026-03-01&to=2026-03-31');

        $response->assertStatus(200)
                 ->assertJsonStructure(['data' => [['room_name', 'utilization_pct', 'status']]]);
    }

    /** @test */
    public function endpoints_require_date_params()
    {
        $response = $this->actingAs($this->getAdminUser())
                         ->getJson('/dashboard/operating-room/cases-per-room');

        $response->assertStatus(422);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    protected function getAdminUser()
    {
        return factory(\App\User::class)->make(['id' => 1]);
    }
}
