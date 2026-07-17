<?php

namespace Tests\Feature\Dashboard;

use Tests\TestCase;
use App\Services\Dashboard\Xml3176DashboardService;

/**
 * Lớp giả kế thừa service thật.
 * KHÔNG dùng Mockery: Mockery 0.9.11 gọi ReflectionType::__toString() (deprecated ở PHP 7.4)
 * khi reflect method có khai báo kiểu trả về, mà phpunit.xml bật convertNoticesToExceptions
 * → mọi Mockery::mock() trên service này đều nổ. Kế thừa thật thì giữ đúng chữ ký, không reflect.
 */
class FakeXml3176DashboardService extends Xml3176DashboardService
{
    public $overviewReturn = [];
    public $topErrorsReturn = [];
    public $agingReturn = [];
    public $byDepartmentReturn = [];

    /** Số tham số controller truyền vào getAging() — dùng để khoá hợp đồng D11 */
    public $agingArgCount = null;

    /** Tham số lần gọi gần nhất của từng method — để test kiểm controller truyền đúng thứ tự */
    public $lastOverviewArgs = null;
    public $lastTopErrorsArgs = null;
    public $lastByDepartmentArgs = null;

    public function __construct() { /* bỏ qua constructor cha */ }

    public function getOverview(string $dateType, string $dateFrom, string $dateTo): array
    {
        $this->lastOverviewArgs = func_get_args();
        return $this->overviewReturn;
    }

    public function getTopErrors(string $dateType, string $dateFrom, string $dateTo): array
    {
        $this->lastTopErrorsArgs = func_get_args();
        return $this->topErrorsReturn;
    }

    public function getAging(): array
    {
        // PHP không báo lỗi khi truyền thừa tham số cho method không khai tham số,
        // nên phải tự đếm để thay cho Mockery ->withNoArgs()
        $this->agingArgCount = func_num_args();
        return $this->agingReturn;
    }

    public function getByDepartment(string $dateType, string $dateFrom, string $dateTo): array
    {
        $this->lastByDepartmentArgs = func_get_args();
        return $this->byDepartmentReturn;
    }
}

/**
 * User giả thỏa CheckRole middleware mà không truy vấn bảng roles trong DB.
 * (Theo pattern của tests/Feature/OnTimeResultControllerTest.php)
 */
class FakeAdminUser extends \App\User
{
    public function hasRole($role, $team = null, $requireAll = false) { return true; }
    public function can($permission, $team = null, $requireAll = false) { return true; }
}

class Xml3176DashboardControllerTest extends TestCase
{
    private function query()
    {
        return '?date_type=date_out&date_from=2026-07-01&date_to=2026-07-17';
    }

    private function fakeService(): FakeXml3176DashboardService
    {
        $fake = new FakeXml3176DashboardService();
        $this->app->instance(Xml3176DashboardService::class, $fake);
        return $fake;
    }

    protected function getAdminUser()
    {
        $user = new FakeAdminUser();
        $user->id = 1;
        return $user;
    }

    /** @test */
    public function overview_endpoint_returns_json()
    {
        $fake = $this->fakeService();
        $fake->overviewReturn = [
            'kpi' => [
                'total' => 100, 'critical' => 20, 'critical_pct' => 20.0,
                'hein_card' => 5, 'hein_card_pct' => 5.0,
                'blocked_amount' => 1234567.0,
                'submitted' => 60, 'submitted_pct' => 60.0,
            ],
            'funnel' => [
                ['key' => 'imported', 'label' => 'Đã import', 'count' => 100, 'pct_of_prev' => 100.0],
            ],
        ];

        $response = $this->actingAs($this->getAdminUser())
                         ->getJson('/dashboard/xml3176/overview' . $this->query());

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'kpi' => ['total', 'critical', 'critical_pct', 'hein_card', 'blocked_amount', 'submitted'],
                     'funnel' => [['key', 'label', 'count', 'pct_of_prev']],
                 ]);

        // Bắt lỗi hoán vị: 3 tham số string cùng kiểu liền nhau rất dễ truyền nhầm thứ tự
        $this->assertSame(['date_out', '2026-07-01', '2026-07-17'], $fake->lastOverviewArgs);
    }

    /** @test */
    public function top_errors_endpoint_returns_json()
    {
        $fake = $this->fakeService();
        $fake->topErrorsReturn = [
            [
                'catalog_id' => 11, 'xml' => 'XML1', 'error_code' => 'E001',
                'error_name' => 'Loi 1', 'critical_error' => true,
                'total' => 30, 'cumulative_pct' => 100.0,
            ],
        ];

        $response = $this->actingAs($this->getAdminUser())
                         ->getJson('/dashboard/xml3176/top-errors' . $this->query());

        $response->assertStatus(200)
                 ->assertJsonStructure(['data' => [['catalog_id', 'error_code', 'error_name', 'critical_error', 'total', 'cumulative_pct']]]);

        $this->assertSame(['date_out', '2026-07-01', '2026-07-17'], $fake->lastTopErrorsArgs);
    }

    /** @test */
    public function aging_endpoint_returns_json_and_ignores_period_filter()
    {
        // D11: getAging() không nhận tham số. assertSame(0, agingArgCount) khoá hợp đồng đó —
        // nếu ai đó lỡ truyền lại bộ lọc kỳ vào, test này sẽ đỏ.
        $fake = $this->fakeService();
        $fake->agingReturn = [
            ['key' => '0-7', 'label' => '0–7 ngày', 'from' => '202607100000', 'to' => '202607172359', 'total' => 12],
        ];

        // Vẫn gửi kèm tham số kỳ để chứng minh endpoint bỏ qua chúng
        $response = $this->actingAs($this->getAdminUser())
                         ->getJson('/dashboard/xml3176/aging' . $this->query());

        $response->assertStatus(200)
                 ->assertJsonStructure(['data' => [['key', 'label', 'from', 'to', 'total']]]);

        $this->assertSame(0, $fake->agingArgCount, 'D11: controller phải gọi getAging() không tham số');
    }

    /** @test */
    public function by_department_endpoint_returns_json()
    {
        $fake = $this->fakeService();
        $fake->byDepartmentReturn = [
            ['ma_khoa' => 'K01', 'ten_khoa' => 'Khoa Noi', 'total' => 9],
        ];

        $response = $this->actingAs($this->getAdminUser())
                         ->getJson('/dashboard/xml3176/by-department' . $this->query());

        $response->assertStatus(200)
                 ->assertJsonStructure(['data' => [['ma_khoa', 'ten_khoa', 'total']]]);

        $this->assertSame(['date_out', '2026-07-01', '2026-07-17'], $fake->lastByDepartmentArgs);
    }

    /** @test */
    public function aging_endpoint_works_without_any_query_params()
    {
        // D11: aging KHÔNG được validate bộ lọc kỳ. Task 7 sẽ gọi endpoint này
        // không kèm tham số ngày — nếu ai đó thêm validateFilters() vào aging(),
        // test này đỏ ngay thay vì để Task 7 ăn 422.
        $fake = $this->fakeService();
        $fake->agingReturn = [
            ['key' => '0-7', 'label' => '0–7 ngày', 'from' => '202607100000', 'to' => '202607172359', 'total' => 12],
        ];

        $response = $this->actingAs($this->getAdminUser())
                         ->getJson('/dashboard/xml3176/aging');   // KHÔNG query string

        $response->assertStatus(200)
                 ->assertJsonStructure(['data' => [['key', 'label', 'from', 'to', 'total']]]);
    }

    /** @test */
    public function endpoints_require_date_params()
    {
        $this->fakeService();

        $response = $this->actingAs($this->getAdminUser())
                         ->getJson('/dashboard/xml3176/overview');

        $response->assertStatus(422);
    }

    /** @test */
    public function endpoint_rejects_invalid_date_type()
    {
        $this->fakeService();

        $response = $this->actingAs($this->getAdminUser())
                         ->getJson('/dashboard/xml3176/overview?date_type=bogus&date_from=2026-07-01&date_to=2026-07-17');

        $response->assertStatus(422);
    }

    /** @test */
    public function endpoint_rejects_date_to_before_date_from()
    {
        $this->fakeService();

        $response = $this->actingAs($this->getAdminUser())
                         ->getJson('/dashboard/xml3176/overview?date_type=date_out&date_from=2026-07-17&date_to=2026-07-01');

        $response->assertStatus(422);
    }
}
