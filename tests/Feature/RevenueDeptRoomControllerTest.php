<?php
// tests/Feature/RevenueDeptRoomControllerTest.php
namespace Tests\Feature;

use Tests\TestCase;
use App\Services\RevenueDeptRoomService;
use Mockery;

class FakeRevAdminUser extends \App\User
{
    public function hasRole($r, $team = null, $requireAll = false) { return true; }
    public function can($permission, $team = null, $requireAll = false) { return true; }
}

class RevenueDeptRoomControllerTest extends TestCase
{
    /** @test */
    public function summary_endpoint_returns_json_structure()
    {
        $mock = Mockery::mock(RevenueDeptRoomService::class);
        $mock->shouldReceive('getSummaryData')->once()->andReturn([
            'kpi' => ['tong_doanh_thu' => 0, 'so_khoa' => 0, 'so_phong' => 0],
            'by_department' => [],
        ]);
        $this->app->instance(RevenueDeptRoomService::class, $mock);

        $response = $this->actingAs($this->getAdminUser())
                         ->getJson(route('khth.revenue-dept-room-summary', ['date_from' => '2026-06-01', 'date_to' => '2026-06-07']));

        $response->assertStatus(200)
                 ->assertJsonStructure(['kpi' => ['tong_doanh_thu', 'so_khoa', 'so_phong'], 'by_department']);
    }

    /** @test */
    public function index_renders_view()
    {
        $response = $this->actingAs($this->getAdminUser())->get(route('khth.revenue-dept-room-index'));
        $response->assertStatus(200);
    }

    protected function tearDown(): void { Mockery::close(); parent::tearDown(); }

    protected function getAdminUser() { return new FakeRevAdminUser(); }
}
