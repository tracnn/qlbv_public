<?php
// tests/Feature/OnTimeResultControllerTest.php
namespace Tests\Feature;

use Tests\TestCase;
use App\Services\OnTimeResultService;
use Mockery;

class OnTimeResultControllerTest extends TestCase
{
    /** @test */
    public function summary_endpoint_returns_json_structure()
    {
        // Mock thẳng getSummaryData để KHÔNG chạm DB.
        $mock = Mockery::mock(OnTimeResultService::class);
        $mock->shouldReceive('getSummaryData')->once()->andReturn([
            'kpi' => ['tong_co_hen'=>0,'da_tra_hop_le'=>0,'dung_hen'=>0,'tre_hen'=>0,'chua_tra'=>0,'bat_thuong'=>0,'pct_dung_hen'=>0,'pct_tre_hen'=>0,'tg_tra_tb'=>0],
            'breakdown_loai_dich_vu' => [], 'breakdown_phong' => [], 'breakdown_dich_vu' => [], 'trend_theo_ngay' => [],
        ]);
        $this->app->instance(OnTimeResultService::class, $mock);

        $response = $this->actingAs($this->getAdminUser())
                         ->getJson(route('khth.on-time-result-summary', ['date_from'=>'2026-06-01','date_to'=>'2026-06-07']));

        $response->assertStatus(200)
                 ->assertJsonStructure(['kpi'=>['tong_co_hen','pct_dung_hen','pct_tre_hen'],'breakdown_loai_dich_vu','breakdown_phong','breakdown_dich_vu','trend_theo_ngay']);
    }

    /** @test */
    public function index_renders_view()
    {
        $response = $this->actingAs($this->getAdminUser())->get(route('khth.on-time-result-index'));
        $response->assertStatus(200);
    }

    protected function tearDown(): void { Mockery::close(); parent::tearDown(); }

    /**
     * Trả về 1 User thỏa middleware checkrole:administrator mà KHÔNG chạm DB roles.
     * (Trong môi trường test, không có user nào mang role 'administrator' thực sự,
     *  nên stub hasRole/can = true để vượt qua CheckRole middleware.)
     */
    protected function getAdminUser()
    {
        $user = new FakeAdminUser();
        $user->id = 1;
        return $user;
    }
}

/**
 * User giả thỏa CheckRole middleware mà không truy vấn bảng roles trong DB.
 * (Môi trường test không có user mang role 'administrator', nên override
 *  hasRole/can = true để vượt qua middleware checkrole:administrator.)
 */
class FakeAdminUser extends \App\User
{
    public function hasRole($role, $team = null, $requireAll = false) { return true; }
    public function can($permission, $team = null, $requireAll = false) { return true; }
}
