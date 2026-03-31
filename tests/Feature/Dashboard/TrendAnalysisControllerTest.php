<?php

namespace Tests\Feature\Dashboard;

use Tests\TestCase;
use App\Services\Dashboard\TrendService;
use Mockery;

class TrendAnalysisControllerTest extends TestCase
{
    /** @test */
    public function trend_chart_endpoint_returns_json()
    {
        $mock = Mockery::mock(TrendService::class);
        $mock->shouldReceive('getTrendChart')->once()->andReturn([
            'labels'   => ['01/03'],
            'current'  => [120],
            'previous' => [100],
        ]);
        $this->app->instance(TrendService::class, $mock);

        $response = $this->actingAs($this->getAdminUser())
                         ->getJson('/dashboard/trends/chart?from=2026-03-01&to=2026-03-31&mode=daily&metric=examinations');

        $response->assertStatus(200)
                 ->assertJsonStructure(['labels', 'current', 'previous']);
    }

    /** @test */
    public function trend_chart_validates_mode_param()
    {
        $response = $this->actingAs($this->getAdminUser())
                         ->getJson('/dashboard/trends/chart?from=2026-03-01&to=2026-03-31&mode=invalid&metric=examinations');

        $response->assertStatus(422);
    }

    /** @test */
    public function patients_per_hour_endpoint_returns_json()
    {
        $mock = Mockery::mock(TrendService::class);
        $mock->shouldReceive('getPatientsPerHour')->once()->andReturn([
            'average_per_hour' => 15.2,
            'by_hour'          => [['hour' => 8, 'count' => 45]],
        ]);
        $this->app->instance(TrendService::class, $mock);

        $response = $this->actingAs($this->getAdminUser())
                         ->getJson('/dashboard/trends/patients-per-hour?from=2026-03-01&to=2026-03-31');

        $response->assertStatus(200)
                 ->assertJsonStructure(['average_per_hour', 'by_hour']);
    }

    /** @test */
    public function overload_alert_endpoint_returns_json()
    {
        $mock = Mockery::mock(TrendService::class);
        $mock->shouldReceive('getOverloadAlert')->once()->andReturn([
            'today_count' => 180,
            'average_30d' => 150.0,
            'ratio'       => 1.2,
            'status'      => 'normal',
        ]);
        $this->app->instance(TrendService::class, $mock);

        $response = $this->actingAs($this->getAdminUser())
                         ->getJson('/dashboard/trends/overload-alert?date=2026-03-31');

        $response->assertStatus(200)
                 ->assertJsonStructure(['today_count', 'average_30d', 'ratio', 'status']);
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
