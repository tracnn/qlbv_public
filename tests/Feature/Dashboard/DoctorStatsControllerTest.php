<?php

namespace Tests\Feature\Dashboard;

use Tests\TestCase;
use App\Services\Dashboard\DoctorService;
use Mockery;

class DoctorStatsControllerTest extends TestCase
{
    /** @test */
    public function examinations_endpoint_returns_json()
    {
        $mock = Mockery::mock(DoctorService::class);
        $mock->shouldReceive('getExaminations')
             ->once()
             ->andReturn([
                 ['loginname' => 'vck', 'username' => 'VŨ CÔNG KHANH', 'total_exams' => 450, 'total_patients' => 400]
             ]);
        $this->app->instance(DoctorService::class, $mock);

        $response = $this->actingAs($this->getAdminUser())
                         ->getJson('/dashboard/doctor-stats/examinations?from=2026-03-01&to=2026-03-31');

        $response->assertStatus(200)
                 ->assertJsonStructure(['data' => [['loginname', 'username', 'total_exams']]]);
    }

    /** @test */
    public function examinations_endpoint_validates_required_params()
    {
        $response = $this->actingAs($this->getAdminUser())
                         ->getJson('/dashboard/doctor-stats/examinations');

        $response->assertStatus(422);
    }

    /** @test */
    public function revenue_endpoint_returns_json()
    {
        $mock = Mockery::mock(DoctorService::class);
        $mock->shouldReceive('getRevenue')->once()->andReturn([]);
        $this->app->instance(DoctorService::class, $mock);

        $response = $this->actingAs($this->getAdminUser())
                         ->getJson('/dashboard/doctor-stats/revenue?from=2026-03-01&to=2026-03-31');

        $response->assertStatus(200)->assertJsonStructure(['data']);
    }

    /** @test */
    public function surgeries_endpoint_returns_json()
    {
        $mock = Mockery::mock(DoctorService::class);
        $mock->shouldReceive('getSurgeries')->once()->andReturn([]);
        $this->app->instance(DoctorService::class, $mock);

        $response = $this->actingAs($this->getAdminUser())
                         ->getJson('/dashboard/doctor-stats/surgeries?from=2026-03-01&to=2026-03-31');

        $response->assertStatus(200)->assertJsonStructure(['data']);
    }

    protected function tearDown()
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Helper: lấy user có role dashboard (tạo nếu chưa có)
     */
    protected function getAdminUser()
    {
        return factory(\App\User::class)->make(['id' => 1]);
    }
}
