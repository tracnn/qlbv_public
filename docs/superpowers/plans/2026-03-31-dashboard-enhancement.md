# Dashboard Enhancement Implementation Plan

> **For agentic workers:** REQUIRED: Use superpowers:subagent-driven-development (if subagents available) or superpowers:executing-plans to implement this plan. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Bổ sung 8 endpoints dashboard mới cho thống kê theo bác sĩ, xu hướng/vận hành, và công suất phòng mổ.

**Architecture:** 3 Controller mới trong namespace `Dashboard\`, mỗi Controller dùng 1 Service class tách biệt. Routes đặt trong `web.php` bên trong middleware group `checkrole:dashboard`. Frontend dùng IIFE JS modules + Highcharts theo pattern hiện có.

**Tech Stack:** Laravel 5.5, PHP, Oracle HISPro (`DB::connection('HISPro')`), Carbon, Blade + AdminLTE, jQuery IIFE, Highcharts.

**Spec:** `docs/superpowers/specs/2026-03-31-dashboard-enhancement-design.md`

---

## File Map

### Tạo mới
| File | Trách nhiệm |
|------|------------|
| `app/Services/Dashboard/DoctorService.php` | Query Oracle: lượt khám/DT/PT theo bác sĩ |
| `app/Services/Dashboard/TrendService.php` | Query Oracle: xu hướng, BN/giờ, cảnh báo quá tải |
| `app/Services/Dashboard/OperatingRoomService.php` | Query Oracle: ca PT/phòng, tính % công suất bằng Carbon |
| `app/Http/Controllers/Dashboard/DoctorStatsController.php` | 3 endpoints JSON cho thống kê bác sĩ |
| `app/Http/Controllers/Dashboard/TrendAnalysisController.php` | 3 endpoints JSON cho xu hướng & vận hành |
| `app/Http/Controllers/Dashboard/OperatingRoomController.php` | 2 endpoints JSON cho phòng mổ |
| `resources/views/dashboard/doctor-stats.blade.php` | Trang tab "Theo bác sĩ" |
| `resources/views/dashboard/trend-analysis.blade.php` | Trang tab "Xu hướng" |
| `resources/views/dashboard/operating-room.blade.php` | Trang tab "Phòng mổ" |
| `public/js/dashboard/doctor-stats.js` | IIFE module: charts + AJAX cho bác sĩ |
| `public/js/dashboard/trend-charts.js` | IIFE module: line chart + BN/giờ + alert |
| `public/js/dashboard/operating-room.js` | IIFE module: heatmap + bar chart |
| `tests/Unit/Dashboard/DoctorServiceTest.php` | Unit test: data processing logic |
| `tests/Unit/Dashboard/TrendServiceTest.php` | Unit test: trend + overload logic |
| `tests/Unit/Dashboard/OperatingRoomServiceTest.php` | Unit test: utilization calculation |
| `tests/Feature/Dashboard/DoctorStatsControllerTest.php` | Feature test: HTTP endpoints |
| `tests/Feature/Dashboard/TrendAnalysisControllerTest.php` | Feature test: HTTP endpoints |
| `tests/Feature/Dashboard/OperatingRoomControllerTest.php` | Feature test: HTTP endpoints |

### Sửa đổi
| File | Thay đổi |
|------|---------|
| `routes/web.php` | Thêm 8 routes mới vào group `checkrole:dashboard` |

---

## Chunk 1: Doctor Stats (Service + Controller + Routes + Views + JS)

### Task 1.1: Tạo DoctorService

**Files:**
- Create: `app/Services/Dashboard/DoctorService.php`
- Create: `tests/Unit/Dashboard/DoctorServiceTest.php`

- [ ] **Step 1: Tạo thư mục và file test trước (TDD)**

```bash
mkdir -p app/Services/Dashboard
mkdir -p tests/Unit/Dashboard
```

Tạo `tests/Unit/Dashboard/DoctorServiceTest.php`:

```php
<?php

namespace Tests\Unit\Dashboard;

use Tests\TestCase;
use App\Services\Dashboard\DoctorService;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DoctorServiceTest extends TestCase
{
    protected $service;

    protected function setUp()
    {
        parent::setUp();
        $this->service = new DoctorService();
    }

    /** @test */
    public function it_converts_date_range_to_oracle_format()
    {
        $from = '2026-03-01';
        $to   = '2026-03-31';

        $result = $this->service->buildDateRange($from, $to);

        $this->assertEquals('20260301000000', $result['from']);
        $this->assertEquals('20260331235959', $result['to']);
    }

    /** @test */
    public function it_formats_examination_results()
    {
        $rows = [
            (object)['loginname' => 'vck', 'username' => 'VŨ CÔNG KHANH', 'total_exams' => 450, 'total_patients' => 400],
            (object)['loginname' => 'abc', 'username' => 'NGUYỄN VĂN A', 'total_exams' => 200, 'total_patients' => 180],
        ];

        $result = $this->service->formatExaminationRows($rows);

        $this->assertCount(2, $result);
        $this->assertEquals('vck', $result[0]['loginname']);
        $this->assertEquals(450, $result[0]['total_exams']);
    }

    /** @test */
    public function it_formats_revenue_results()
    {
        $rows = [
            (object)['loginname' => 'vck', 'username' => 'VŨ CÔNG KHANH', 'total_revenue' => 125000000, 'total_patients' => 320],
        ];

        $result = $this->service->formatRevenueRows($rows);

        $this->assertEquals(125000000, $result[0]['total_revenue']);
        $this->assertEquals(320, $result[0]['total_patients']);
    }

    /** @test */
    public function it_formats_surgery_results()
    {
        $rows = [
            (object)['loginname' => 'ptv1', 'username' => 'PTV CHÍNH', 'total_surgeries' => 30],
        ];

        $result = $this->service->formatSurgeryRows($rows);

        $this->assertEquals(30, $result[0]['total_surgeries']);
    }
}
```

- [ ] **Step 2: Chạy test để xác nhận FAIL**

```bash
php artisan test tests/Unit/Dashboard/DoctorServiceTest.php
```

Expected: FAIL với "Class 'App\Services\Dashboard\DoctorService' not found"

- [ ] **Step 3: Tạo DoctorService**

Tạo `app/Services/Dashboard/DoctorService.php`:

```php
<?php

namespace App\Services\Dashboard;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DoctorService
{
    /**
     * Chuyển date range từ Y-m-d sang format YmdHis cho Oracle
     */
    public function buildDateRange(string $from, string $to): array
    {
        return [
            'from' => Carbon::parse($from)->startOfDay()->format('YmdHis'),
            'to'   => Carbon::parse($to)->endOfDay()->format('YmdHis'),
        ];
    }

    /**
     * Format rows lượt khám theo bác sĩ
     */
    public function formatExaminationRows($rows): array
    {
        return collect($rows)->map(function ($row) {
            return [
                'loginname'      => $row->loginname,
                'username'       => $row->username,
                'total_exams'    => (int) $row->total_exams,
                'total_patients' => (int) ($row->total_patients ?? 0),
            ];
        })->toArray();
    }

    /**
     * Format rows doanh thu theo bác sĩ
     */
    public function formatRevenueRows($rows): array
    {
        return collect($rows)->map(function ($row) {
            return [
                'loginname'      => $row->loginname,
                'username'       => $row->username,
                'total_revenue'  => (float) $row->total_revenue,
                'total_patients' => (int) $row->total_patients,
            ];
        })->toArray();
    }

    /**
     * Format rows ca phẫu thuật theo bác sĩ (PTV chính)
     */
    public function formatSurgeryRows($rows): array
    {
        return collect($rows)->map(function ($row) {
            return [
                'loginname'       => $row->loginname,
                'username'        => $row->username,
                'total_surgeries' => (int) $row->total_surgeries,
            ];
        })->toArray();
    }

    /**
     * Lấy số lượt khám theo bác sĩ
     */
    public function getExaminations(string $from, string $to, ?int $departmentId = null): array
    {
        $dates = $this->buildDateRange($from, $to);

        $query = DB::connection('HISPro')
            ->table('his_service_req as sr')
            ->selectRaw('sr.EXECUTE_LOGINNAME as loginname, sr.EXECUTE_USERNAME as username,
                         COUNT(*) as total_exams,
                         COUNT(DISTINCT sr.TREATMENT_ID) as total_patients')
            ->where('sr.SERVICE_REQ_TYPE_ID', 1)
            ->where('sr.IS_DELETE', 0)
            ->where('sr.IS_ACTIVE', 1)
            ->whereBetween('sr.INTRUCTION_TIME', [$dates['from'], $dates['to']]);

        if ($departmentId) {
            $query->where('sr.EXECUTE_ROOM_ID', $departmentId);
        }

        $rows = $query->groupBy('sr.EXECUTE_LOGINNAME', 'sr.EXECUTE_USERNAME')
                      ->orderByRaw('total_exams DESC')
                      ->get();

        return $this->formatExaminationRows($rows);
    }

    /**
     * Lấy doanh thu theo bác sĩ
     */
    public function getRevenue(string $from, string $to, ?int $departmentId = null): array
    {
        $dates = $this->buildDateRange($from, $to);

        $query = DB::connection('HISPro')
            ->table('his_service_req as sr')
            ->join('his_sere_serv as ss', function ($join) {
                $join->on('ss.SERVICE_REQ_ID', '=', 'sr.ID')
                     ->where('ss.IS_DELETE', 0)
                     ->where('ss.IS_ACTIVE', 1);
            })
            ->selectRaw('sr.EXECUTE_LOGINNAME as loginname, sr.EXECUTE_USERNAME as username,
                         SUM(ss.VIR_TOTAL_PRICE) as total_revenue,
                         COUNT(DISTINCT sr.TREATMENT_ID) as total_patients')
            ->where('sr.IS_DELETE', 0)
            ->where('sr.IS_ACTIVE', 1)
            ->whereBetween('sr.INTRUCTION_TIME', [$dates['from'], $dates['to']]);

        if ($departmentId) {
            $query->where('sr.EXECUTE_ROOM_ID', $departmentId);
        }

        $rows = $query->groupBy('sr.EXECUTE_LOGINNAME', 'sr.EXECUTE_USERNAME')
                      ->orderByRaw('total_revenue DESC')
                      ->get();

        return $this->formatRevenueRows($rows);
    }

    /**
     * Lấy số ca phẫu thuật theo PTV chính
     */
    public function getSurgeries(string $from, string $to): array
    {
        $dates = $this->buildDateRange($from, $to);

        $rows = DB::connection('HISPro')
            ->table('his_sere_serv as ss')
            ->join('his_ekip_user as eu', function ($join) {
                $join->on('eu.EKIP_ID', '=', 'ss.EKIP_ID')
                     ->where('eu.IS_DELETE', 0);
            })
            ->join('his_execute_role as er', function ($join) {
                $join->on('er.ID', '=', 'eu.EXECUTE_ROLE_ID')
                     ->where('er.IS_SURG_MAIN', 1);
            })
            ->join('his_service_req as sr', function ($join) {
                $join->on('sr.ID', '=', 'ss.SERVICE_REQ_ID')
                     ->where('sr.IS_DELETE', 0)
                     ->where('sr.IS_ACTIVE', 1);
            })
            ->selectRaw('eu.LOGINNAME as loginname, eu.USERNAME as username, COUNT(*) as total_surgeries')
            ->where('ss.IS_DELETE', 0)
            ->where('ss.IS_ACTIVE', 1)
            ->whereNotNull('ss.EKIP_ID')
            ->where('sr.SERVICE_REQ_TYPE_ID', 4)
            ->whereBetween('sr.INTRUCTION_TIME', [$dates['from'], $dates['to']])
            ->groupBy('eu.LOGINNAME', 'eu.USERNAME')
            ->orderByRaw('total_surgeries DESC')
            ->get();

        return $this->formatSurgeryRows($rows);
    }
}
```

- [ ] **Step 4: Chạy test để xác nhận PASS**

```bash
php artisan test tests/Unit/Dashboard/DoctorServiceTest.php
```

Expected: 4 tests PASS

- [ ] **Step 5: Commit**

```bash
git add app/Services/Dashboard/DoctorService.php tests/Unit/Dashboard/DoctorServiceTest.php
git commit -m "feat: add DoctorService with examination, revenue, surgery queries"
```

---

### Task 1.2: Tạo DoctorStatsController

**Files:**
- Create: `app/Http/Controllers/Dashboard/DoctorStatsController.php`
- Create: `tests/Feature/Dashboard/DoctorStatsControllerTest.php`

- [ ] **Step 1: Tạo thư mục và file test**

```bash
mkdir -p app/Http/Controllers/Dashboard
mkdir -p tests/Feature/Dashboard
```

Tạo `tests/Feature/Dashboard/DoctorStatsControllerTest.php`:

```php
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

    protected function tearDown(): void
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
```

- [ ] **Step 2: Chạy test để xác nhận FAIL**

```bash
php artisan test tests/Feature/Dashboard/DoctorStatsControllerTest.php
```

Expected: FAIL với "Route not found" hoặc "Class not found"

- [ ] **Step 3: Tạo DoctorStatsController**

Tạo `app/Http/Controllers/Dashboard/DoctorStatsController.php`:

```php
<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Services\Dashboard\DoctorService;
use Illuminate\Http\Request;

class DoctorStatsController extends Controller
{
    protected $doctorService;

    public function __construct(DoctorService $doctorService)
    {
        $this->doctorService = $doctorService;
    }

    /**
     * GET /dashboard/doctor-stats/examinations
     * Số lượt khám theo bác sĩ
     */
    public function examinations(Request $request)
    {
        $request->validate([
            'from' => 'required|date',
            'to'   => 'required|date|after_or_equal:from',
        ]);

        $data = $this->doctorService->getExaminations(
            $request->input('from'),
            $request->input('to'),
            $request->input('department_id')
        );

        return response()->json(['data' => $data]);
    }

    /**
     * GET /dashboard/doctor-stats/revenue
     * Doanh thu theo bác sĩ
     */
    public function revenue(Request $request)
    {
        $request->validate([
            'from' => 'required|date',
            'to'   => 'required|date|after_or_equal:from',
        ]);

        $data = $this->doctorService->getRevenue(
            $request->input('from'),
            $request->input('to'),
            $request->input('department_id')
        );

        return response()->json(['data' => $data]);
    }

    /**
     * GET /dashboard/doctor-stats/surgeries
     * Ca phẫu thuật theo PTV chính
     */
    public function surgeries(Request $request)
    {
        $request->validate([
            'from' => 'required|date',
            'to'   => 'required|date|after_or_equal:from',
        ]);

        $data = $this->doctorService->getSurgeries(
            $request->input('from'),
            $request->input('to')
        );

        return response()->json(['data' => $data]);
    }
}
```

- [ ] **Step 4: Thêm routes vào `routes/web.php`**

Mở `routes/web.php`, tìm block `Route::group(['middleware' => ['checkrole:dashboard']], function () {` (khoảng dòng 64). Thêm vào **bên trong** block này, trước `});` đóng group:

```php
        // ── Doctor Stats ──────────────────────────────────────────────────────
        Route::get('dashboard/doctor-stats/examinations', 'Dashboard\DoctorStatsController@examinations')
             ->name('dashboard.doctor-stats.examinations');
        Route::get('dashboard/doctor-stats/revenue', 'Dashboard\DoctorStatsController@revenue')
             ->name('dashboard.doctor-stats.revenue');
        Route::get('dashboard/doctor-stats/surgeries', 'Dashboard\DoctorStatsController@surgeries')
             ->name('dashboard.doctor-stats.surgeries');
```

- [ ] **Step 5: Chạy test để xác nhận PASS**

```bash
php artisan test tests/Feature/Dashboard/DoctorStatsControllerTest.php
```

Expected: 4 tests PASS

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/Dashboard/DoctorStatsController.php \
        tests/Feature/Dashboard/DoctorStatsControllerTest.php \
        routes/web.php
git commit -m "feat: add DoctorStatsController with 3 endpoints and routes"
```

---

### Task 1.3: Tạo Blade view + JS cho Doctor Stats

**Files:**
- Create: `resources/views/dashboard/doctor-stats.blade.php`
- Create: `public/js/dashboard/doctor-stats.js`

- [ ] **Step 1: Tạo Blade view**

Tạo `resources/views/dashboard/doctor-stats.blade.php`:

```blade
@extends('adminlte::page')

@section('title', 'Thống kê theo bác sĩ')

@section('content_header')
<h1>Thống kê theo bác sĩ <small>Dashboard</small></h1>
{{-- Breadcrumbs nếu cần: đăng ký 'dashboard.doctor-stats' trong routes/breadcrumbs.php --}}
@endsection

@push('after-styles')
<style>
    .filter-row { margin-bottom: 15px; }
    .chart-box { min-height: 350px; }
    .tab-content { padding-top: 15px; }
</style>
@endpush

@section('content')
<div class="row filter-row">
    <div class="col-md-3">
        <div class="form-group">
            <label>Từ ngày</label>
            <input type="date" id="from-date" class="form-control"
                   value="{{ date('Y-m-01') }}">
        </div>
    </div>
    <div class="col-md-3">
        <div class="form-group">
            <label>Đến ngày</label>
            <input type="date" id="to-date" class="form-control"
                   value="{{ date('Y-m-d') }}">
        </div>
    </div>
    <div class="col-md-2">
        <div class="form-group">
            <label>&nbsp;</label>
            <button id="btn-load" class="btn btn-primary form-control">
                <i class="fa fa-search"></i> Xem
            </button>
        </div>
    </div>
</div>

{{-- Tabs --}}
<div class="nav-tabs-custom">
    <ul class="nav nav-tabs">
        <li class="active"><a href="#tab-exam" data-toggle="tab">Lượt khám</a></li>
        <li><a href="#tab-revenue" data-toggle="tab">Doanh thu</a></li>
        <li><a href="#tab-surgery" data-toggle="tab">Phẫu thuật</a></li>
    </ul>
    <div class="tab-content">
        {{-- Tab: Lượt khám --}}
        <div class="tab-pane active" id="tab-exam">
            <div class="row">
                <div class="col-md-7">
                    <div id="chart-exam" class="chart-box"></div>
                </div>
                <div class="col-md-5">
                    <table class="table table-bordered table-hover table-condensed" id="tbl-exam">
                        <thead>
                            <tr>
                                <th>Bác sĩ</th>
                                <th>Lượt khám</th>
                                <th>BN</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Tab: Doanh thu --}}
        <div class="tab-pane" id="tab-revenue">
            <div class="row">
                <div class="col-md-7">
                    <div id="chart-revenue" class="chart-box"></div>
                </div>
                <div class="col-md-5">
                    <table class="table table-bordered table-hover table-condensed" id="tbl-revenue">
                        <thead>
                            <tr>
                                <th>Bác sĩ</th>
                                <th>Doanh thu</th>
                                <th>BN</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Tab: Phẫu thuật --}}
        <div class="tab-pane" id="tab-surgery">
            <div class="row">
                <div class="col-md-7">
                    <div id="chart-surgery" class="chart-box"></div>
                </div>
                <div class="col-md-5">
                    <table class="table table-bordered table-hover table-condensed" id="tbl-surgery">
                        <thead>
                            <tr>
                                <th>PTV chính</th>
                                <th>Số ca</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('after-scripts')
<script>
window.DOCTOR_STATS_CFG = {
    routes: {
        examinations: '{{ route("dashboard.doctor-stats.examinations") }}',
        revenue:      '{{ route("dashboard.doctor-stats.revenue") }}',
        surgeries:    '{{ route("dashboard.doctor-stats.surgeries") }}'
    }
};
</script>
<script src="{{ asset('js/dashboard/doctor-stats.js') }}"></script>
@endpush
```

- [ ] **Step 2: Tạo JS module**

Tạo `public/js/dashboard/doctor-stats.js`:

```javascript
(function (win, $) {
    'use strict';

    var R = (win.DOCTOR_STATS_CFG || {}).routes || {};

    // ── Helpers ──────────────────────────────────────────────────────────────

    function getParams() {
        return {
            from: $('#from-date').val(),
            to:   $('#to-date').val()
        };
    }

    function formatMoney(num) {
        if (!num) return '0';
        return Number(num).toLocaleString('vi-VN') + ' đ';
    }

    function showError(containerId, msg) {
        $('#' + containerId).html('<div class="text-center text-danger" style="padding:40px">' + (msg || 'Không thể tải dữ liệu') + '</div>');
    }

    function showBarChart(containerId, title, categories, data, yTitle) {
        Highcharts.chart(containerId, {
            chart: { type: 'bar' },
            title: { text: title },
            xAxis: { categories: categories, title: { text: null } },
            yAxis: { title: { text: yTitle }, allowDecimals: false },
            legend: { enabled: false },
            series: [{ name: yTitle, data: data, colorByPoint: true }],
            credits: { enabled: false }
        });
    }

    // ── Tab: Lượt khám ───────────────────────────────────────────────────────

    function loadExaminations() {
        $.ajax({ url: R.examinations, data: getParams(), dataType: 'json' })
            .fail(function () { showError('chart-exam', 'Không thể kết nối HIS'); })
            .done(function (res) {
                var rows = (res.data || []).slice(0, 10);
                var names   = rows.map(function (r) { return r.username; });
                var counts  = rows.map(function (r) { return r.total_exams; });

                showBarChart('chart-exam', 'Top bác sĩ – Lượt khám', names, counts, 'Lượt khám');

                var tbody = '';
                (res.data || []).forEach(function (r) {
                    tbody += '<tr><td>' + r.username + '</td><td>' + r.total_exams + '</td><td>' + r.total_patients + '</td></tr>';
                });
                $('#tbl-exam tbody').html(tbody || '<tr><td colspan="3" class="text-center">Không có dữ liệu</td></tr>');
            });
    }

    // ── Tab: Doanh thu ───────────────────────────────────────────────────────

    function loadRevenue() {
        $.ajax({ url: R.revenue, data: getParams(), dataType: 'json' })
            .fail(function () { showError('chart-revenue', 'Không thể kết nối HIS'); })
            .done(function (res) {
                var rows   = (res.data || []).slice(0, 10);
                var names  = rows.map(function (r) { return r.username; });
                var values = rows.map(function (r) { return r.total_revenue; });

                showBarChart('chart-revenue', 'Top bác sĩ – Doanh thu', names, values, 'Doanh thu (đ)');

                var tbody = '';
                (res.data || []).forEach(function (r) {
                    tbody += '<tr><td>' + r.username + '</td><td>' + formatMoney(r.total_revenue) + '</td><td>' + r.total_patients + '</td></tr>';
                });
                $('#tbl-revenue tbody').html(tbody || '<tr><td colspan="3" class="text-center">Không có dữ liệu</td></tr>');
            });
    }

    // ── Tab: Phẫu thuật ──────────────────────────────────────────────────────

    function loadSurgeries() {
        $.ajax({ url: R.surgeries, data: getParams(), dataType: 'json' })
            .fail(function () { showError('chart-surgery', 'Không thể kết nối HIS'); })
            .done(function (res) {
                var rows   = (res.data || []).slice(0, 10);
                var names  = rows.map(function (r) { return r.username; });
                var counts = rows.map(function (r) { return r.total_surgeries; });

                showBarChart('chart-surgery', 'Top PTV chính – Số ca mổ', names, counts, 'Số ca');

                var tbody = '';
                (res.data || []).forEach(function (r) {
                    tbody += '<tr><td>' + r.username + '</td><td>' + r.total_surgeries + '</td></tr>';
                });
                $('#tbl-surgery tbody').html(tbody || '<tr><td colspan="2" class="text-center">Không có dữ liệu</td></tr>');
            });
    }

    // ── Init ─────────────────────────────────────────────────────────────────

    function loadAll() {
        loadExaminations();
        loadRevenue();
        loadSurgeries();
    }

    $(document).ready(function () {
        $('#btn-load').on('click', loadAll);
        loadAll();
    });

})(window, jQuery);
```

- [ ] **Step 3: Thêm route cho view page vào `routes/web.php`**

```php
        Route::get('dashboard/doctor-stats', 'Dashboard\DoctorStatsController@index')
             ->name('dashboard.doctor-stats');
```

Thêm method `index()` vào `DoctorStatsController.php`:

```php
    public function index()
    {
        return view('dashboard.doctor-stats');
    }
```

- [ ] **Step 4: Kiểm tra thủ công trên trình duyệt**

Truy cập `http://localhost:8000/dashboard/doctor-stats`, xác nhận:
- [ ] Trang load không lỗi
- [ ] 3 tabs hiển thị
- [ ] Click "Xem" → AJAX call xuất hiện trong Network tab
- [ ] Charts render với Highcharts

- [ ] **Step 5: Commit**

```bash
git add resources/views/dashboard/doctor-stats.blade.php \
        public/js/dashboard/doctor-stats.js
git commit -m "feat: add doctor-stats view and JS module with bar charts"
```

---

## Chunk 2: Trend Analysis (Service + Controller + Routes + Views + JS)

### Task 2.1: Tạo TrendService

**Files:**
- Create: `app/Services/Dashboard/TrendService.php`
- Create: `tests/Unit/Dashboard/TrendServiceTest.php`

- [ ] **Step 1: Tạo file test**

Tạo `tests/Unit/Dashboard/TrendServiceTest.php`:

```php
<?php

namespace Tests\Unit\Dashboard;

use Tests\TestCase;
use App\Services\Dashboard\TrendService;
use Carbon\Carbon;

class TrendServiceTest extends TestCase
{
    protected $service;

    protected function setUp()
    {
        parent::setUp();
        $this->service = new TrendService();
    }

    /** @test */
    public function it_extracts_day_from_intruction_time()
    {
        // INTRUCTION_TIME = 20260315143000 → day_val = 20260315
        $rows = [(object)['day_val' => 20260315, 'total' => 120]];
        $result = $this->service->formatTrendRows($rows, 'daily');

        $this->assertEquals('15/03', $result[0]['label']);
        $this->assertEquals(120, $result[0]['value']);
    }

    /** @test */
    public function it_extracts_month_from_intruction_time()
    {
        // month_val = 202603 → label = '03/2026'
        $rows = [(object)['month_val' => 202603, 'total' => 1500]];
        $result = $this->service->formatTrendRows($rows, 'monthly');

        $this->assertEquals('03/2026', $result[0]['label']);
        $this->assertEquals(1500, $result[0]['value']);
    }

    /** @test */
    public function it_calculates_overload_status_overload()
    {
        $status = $this->service->calculateOverloadStatus(181, 150);
        $this->assertEquals('overload', $status['status']); // 181/150 = 1.207 > 1.2
    }

    /** @test */
    public function it_calculates_overload_status_normal()
    {
        $status = $this->service->calculateOverloadStatus(150, 150);
        $this->assertEquals('normal', $status['status']); // ratio = 1.0
    }

    /** @test */
    public function it_calculates_overload_status_underload()
    {
        $status = $this->service->calculateOverloadStatus(100, 150);
        $this->assertEquals('underload', $status['status']); // ratio = 0.67 < 0.8
    }

    /** @test */
    public function it_calculates_previous_period_for_daily_mode()
    {
        // Cùng khoảng ngày nhưng tháng trước
        $prev = $this->service->buildPreviousPeriod('2026-03-05', '2026-03-20', 'daily');
        $this->assertEquals('2026-02-05', $prev['from']);
        $this->assertEquals('2026-02-20', $prev['to']);
    }

    /** @test */
    public function it_calculates_previous_period_for_monthly_mode()
    {
        $prev = $this->service->buildPreviousPeriod('2026-01-01', '2026-12-31', 'monthly');
        $this->assertEquals('2025-01-01', $prev['from']);
        $this->assertEquals('2025-12-31', $prev['to']);
    }
}
```

- [ ] **Step 2: Chạy test để xác nhận FAIL**

```bash
php artisan test tests/Unit/Dashboard/TrendServiceTest.php
```

Expected: FAIL với "Class not found"

- [ ] **Step 3: Tạo TrendService**

Tạo `app/Services/Dashboard/TrendService.php`:

```php
<?php

namespace App\Services\Dashboard;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class TrendService
{
    /**
     * Format trend rows từ Oracle thành label + value
     */
    public function formatTrendRows($rows, string $mode): array
    {
        return collect($rows)->map(function ($row) use ($mode) {
            if ($mode === 'daily') {
                $dayStr = (string) $row->day_val; // e.g. '20260315'
                $label  = substr($dayStr, 6, 2) . '/' . substr($dayStr, 4, 2);
            } else {
                $monthStr = (string) $row->month_val; // e.g. '202603'
                $label    = substr($monthStr, 4, 2) . '/' . substr($monthStr, 0, 4);
            }
            return [
                'label' => $label,
                'value' => (int) ($row->total ?? 0),
            ];
        })->toArray();
    }

    /**
     * Tính kỳ trước (dùng để vẽ đường nét đứt so sánh)
     */
    public function buildPreviousPeriod(string $from, string $to, string $mode): array
    {
        if ($mode === 'daily') {
            // Shift 1 tháng về trước — cùng khoảng ngày
            $prevFrom = Carbon::parse($from)->subMonth()->format('Y-m-d');
            $prevTo   = Carbon::parse($to)->subMonth()->format('Y-m-d');
        } else {
            // Shift 1 năm về trước
            $prevFrom = Carbon::parse($from)->subYear()->format('Y-m-d');
            $prevTo   = Carbon::parse($to)->subYear()->format('Y-m-d');
        }

        return ['from' => $prevFrom, 'to' => $prevTo];
    }

    /**
     * Tính trạng thái quá tải
     */
    public function calculateOverloadStatus(int $todayCount, float $avg30d): array
    {
        $ratio = $avg30d > 0 ? round($todayCount / $avg30d, 2) : 0;

        if ($ratio > 1.2) {
            $status = 'overload';
        } elseif ($ratio < 0.8) {
            $status = 'underload';
        } else {
            $status = 'normal';
        }

        return [
            'today_count' => $todayCount,
            'average_30d' => round($avg30d, 1),
            'ratio'       => $ratio,
            'status'      => $status,
        ];
    }

    /**
     * Lấy dữ liệu xu hướng lượt khám hoặc doanh thu
     */
    public function getTrendChart(string $from, string $to, string $mode, string $metric): array
    {
        $fromDate   = Carbon::parse($from)->startOfDay()->format('YmdHis');
        $toDate     = Carbon::parse($to)->endOfDay()->format('YmdHis');
        $prevPeriod = $this->buildPreviousPeriod($from, $to, $mode);
        $prevFrom   = Carbon::parse($prevPeriod['from'])->startOfDay()->format('YmdHis');
        $prevTo     = Carbon::parse($prevPeriod['to'])->endOfDay()->format('YmdHis');

        $currentRows  = $this->formatTrendRows($this->queryTrendData($fromDate, $toDate, $mode, $metric), $mode);
        $previousRows = $this->formatTrendRows($this->queryTrendData($prevFrom, $prevTo, $mode, $metric), $mode);

        return [
            'labels'   => array_map(function ($r) { return $r['label']; }, $currentRows),
            'current'  => array_map(function ($r) { return $r['value']; }, $currentRows),
            'previous' => array_map(function ($r) { return $r['value']; }, $previousRows),
        ];
    }

    private function queryTrendData(string $fromDate, string $toDate, string $mode, string $metric)
    {
        $groupExpr = $mode === 'daily'
            ? 'TRUNC(sr.INTRUCTION_TIME / 1000000)'
            : 'TRUNC(sr.INTRUCTION_TIME / 100000000)';

        $alias = $mode === 'daily' ? 'day_val' : 'month_val';

        $query = DB::connection('HISPro')
            ->table('his_service_req as sr')
            ->where('sr.SERVICE_REQ_TYPE_ID', 1)
            ->where('sr.IS_DELETE', 0)
            ->where('sr.IS_ACTIVE', 1)
            ->whereBetween('sr.INTRUCTION_TIME', [$fromDate, $toDate]);

        if ($metric === 'revenue') {
            $query->join('his_sere_serv as ss', function ($join) {
                $join->on('ss.SERVICE_REQ_ID', '=', 'sr.ID')
                     ->where('ss.IS_DELETE', 0)
                     ->where('ss.IS_ACTIVE', 1);
            });
            $selectRaw = "$groupExpr as $alias, SUM(ss.VIR_TOTAL_PRICE) as total";
        } else {
            $selectRaw = "$groupExpr as $alias, COUNT(*) as total";
        }

        return $query->selectRaw($selectRaw)
                     ->groupByRaw($groupExpr)
                     ->orderByRaw($alias)
                     ->get();
    }

    /**
     * Lấy số BN/giờ theo khung giờ trong ngày
     */
    public function getPatientsPerHour(string $from, string $to, ?int $departmentId = null): array
    {
        $fromDate = Carbon::parse($from)->startOfDay()->format('YmdHis');
        $toDate   = Carbon::parse($to)->endOfDay()->format('YmdHis');

        $query = DB::connection('HISPro')
            ->table('his_service_req as sr')
            ->selectRaw('FLOOR(MOD(sr.START_TIME, 1000000) / 10000) as hour_of_day, COUNT(*) as total_patients')
            ->where('sr.SERVICE_REQ_TYPE_ID', 1)
            ->where('sr.IS_DELETE', 0)
            ->where('sr.IS_ACTIVE', 1)
            ->whereNotNull('sr.START_TIME')
            ->whereBetween('sr.INTRUCTION_TIME', [$fromDate, $toDate]);

        if ($departmentId) {
            $query->where('sr.EXECUTE_ROOM_ID', $departmentId);
        }

        $rows = $query->groupByRaw('FLOOR(MOD(sr.START_TIME, 1000000) / 10000)')
                      ->orderBy('hour_of_day')
                      ->get();

        $totalPatients = $rows->sum('total_patients');
        $workingDays   = max(1, Carbon::parse($from)->diffInWeekdays(Carbon::parse($to)) + 1);
        $avgPerHour    = round($totalPatients / ($workingDays * 8), 1);

        $byHour = $rows->map(function ($r) {
            return ['hour' => (int) $r->hour_of_day, 'count' => (int) $r->total_patients];
        })->toArray();

        return ['average_per_hour' => $avgPerHour, 'by_hour' => $byHour];
    }

    /**
     * Kiểm tra quá tải ngày hôm nay so với trung bình 30 ngày
     */
    public function getOverloadAlert(string $date): array
    {
        $dayVal     = Carbon::parse($date)->format('Ymd');
        $from30d    = Carbon::parse($date)->subDays(30)->startOfDay()->format('YmdHis');
        $to30d      = Carbon::parse($date)->subDay()->endOfDay()->format('YmdHis');
        $todayFrom  = Carbon::parse($date)->startOfDay()->format('YmdHis');
        $todayTo    = Carbon::parse($date)->endOfDay()->format('YmdHis');

        $todayCount = DB::connection('HISPro')
            ->table('his_service_req')
            ->where('SERVICE_REQ_TYPE_ID', 1)
            ->where('IS_DELETE', 0)
            ->where('IS_ACTIVE', 1)
            ->whereBetween('INTRUCTION_TIME', [$todayFrom, $todayTo])
            ->count();

        $avg30d = DB::connection('HISPro')
            ->table('his_service_req')
            ->where('SERVICE_REQ_TYPE_ID', 1)
            ->where('IS_DELETE', 0)
            ->where('IS_ACTIVE', 1)
            ->whereBetween('INTRUCTION_TIME', [$from30d, $to30d])
            ->count() / 30;

        return $this->calculateOverloadStatus($todayCount, $avg30d);
    }
}
```

- [ ] **Step 4: Chạy test để xác nhận PASS**

```bash
php artisan test tests/Unit/Dashboard/TrendServiceTest.php
```

Expected: 7 tests PASS

- [ ] **Step 5: Commit**

```bash
git add app/Services/Dashboard/TrendService.php tests/Unit/Dashboard/TrendServiceTest.php
git commit -m "feat: add TrendService with trend chart, BN/hour, and overload alert logic"
```

---

### Task 2.2: Tạo TrendAnalysisController + Routes

**Files:**
- Create: `app/Http/Controllers/Dashboard/TrendAnalysisController.php`
- Create: `tests/Feature/Dashboard/TrendAnalysisControllerTest.php`
- Modify: `routes/web.php`

- [ ] **Step 1: Tạo file test**

Tạo `tests/Feature/Dashboard/TrendAnalysisControllerTest.php`:

```php
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
```

- [ ] **Step 2: Tạo TrendAnalysisController**

Tạo `app/Http/Controllers/Dashboard/TrendAnalysisController.php`:

```php
<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Services\Dashboard\TrendService;
use Illuminate\Http\Request;

class TrendAnalysisController extends Controller
{
    protected $trendService;

    public function __construct(TrendService $trendService)
    {
        $this->trendService = $trendService;
    }

    public function index()
    {
        return view('dashboard.trend-analysis');
    }

    /**
     * GET /dashboard/trends/chart
     */
    public function trendChart(Request $request)
    {
        $request->validate([
            'from'   => 'required|date',
            'to'     => 'required|date|after_or_equal:from',
            'mode'   => 'required|in:daily,monthly',
            'metric' => 'required|in:examinations,revenue',
        ]);

        $data = $this->trendService->getTrendChart(
            $request->input('from'),
            $request->input('to'),
            $request->input('mode'),
            $request->input('metric')
        );

        return response()->json($data);
    }

    /**
     * GET /dashboard/trends/patients-per-hour
     */
    public function patientsPerHour(Request $request)
    {
        $request->validate([
            'from' => 'required|date',
            'to'   => 'required|date|after_or_equal:from',
        ]);

        $data = $this->trendService->getPatientsPerHour(
            $request->input('from'),
            $request->input('to'),
            $request->input('department_id')
        );

        return response()->json($data);
    }

    /**
     * GET /dashboard/trends/overload-alert
     */
    public function overloadAlert(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
        ]);

        $data = $this->trendService->getOverloadAlert($request->input('date'));

        return response()->json($data);
    }
}
```

- [ ] **Step 3: Thêm routes vào `routes/web.php`**

Thêm tiếp vào **bên trong** group `checkrole:dashboard` của `routes/web.php`:

```php
        // ── Trend Analysis ────────────────────────────────────────────────────
        Route::get('dashboard/trends', 'Dashboard\TrendAnalysisController@index')
             ->name('dashboard.trends');
        Route::get('dashboard/trends/chart', 'Dashboard\TrendAnalysisController@trendChart')
             ->name('dashboard.trends.chart');
        Route::get('dashboard/trends/patients-per-hour', 'Dashboard\TrendAnalysisController@patientsPerHour')
             ->name('dashboard.trends.patients-per-hour');
        Route::get('dashboard/trends/overload-alert', 'Dashboard\TrendAnalysisController@overloadAlert')
             ->name('dashboard.trends.overload-alert');
```

- [ ] **Step 4: Chạy test**

```bash
php artisan test tests/Feature/Dashboard/TrendAnalysisControllerTest.php
```

Expected: 4 tests PASS

- [ ] **Step 5: Commit**

```bash
git add app/Http/Controllers/Dashboard/TrendAnalysisController.php \
        tests/Feature/Dashboard/TrendAnalysisControllerTest.php \
        routes/web.php
git commit -m "feat: add TrendAnalysisController with trend chart, BN/hour, overload alert endpoints"
```

---

### Task 2.3: Tạo Blade view + JS cho Trend Analysis

**Files:**
- Create: `resources/views/dashboard/trend-analysis.blade.php`
- Create: `public/js/dashboard/trend-charts.js`

- [ ] **Step 1: Tạo Blade view**

Tạo `resources/views/dashboard/trend-analysis.blade.php`:

```blade
@extends('adminlte::page')

@section('title', 'Xu hướng & Vận hành')

@section('content_header')
<h1>Xu hướng & Vận hành <small>Dashboard</small></h1>
{{-- Breadcrumbs nếu cần: đăng ký 'dashboard.trends' trong routes/breadcrumbs.php --}}
@endsection

@push('after-styles')
<style>
    .filter-row { margin-bottom: 15px; }
    .chart-box  { min-height: 350px; }
    .kpi-card   { font-size: 2em; font-weight: bold; text-align: center; padding: 20px; }
    .alert-card { padding: 15px; border-radius: 4px; margin-bottom: 15px; }
    .alert-overload  { background: #f2dede; border: 1px solid #ebccd1; color: #a94442; }
    .alert-underload { background: #fcf8e3; border: 1px solid #faebcc; color: #8a6d3b; }
    .alert-normal    { background: #dff0d8; border: 1px solid #d6e9c6; color: #3c763d; }
</style>
@endpush

@section('content')
{{-- Alert quá tải --}}
<div id="overload-alert-box"></div>

{{-- Bộ lọc --}}
<div class="row filter-row">
    <div class="col-md-3">
        <label>Từ ngày</label>
        <input type="date" id="from-date" class="form-control" value="{{ date('Y-m-01') }}">
    </div>
    <div class="col-md-3">
        <label>Đến ngày</label>
        <input type="date" id="to-date" class="form-control" value="{{ date('Y-m-d') }}">
    </div>
    <div class="col-md-2">
        <label>Chỉ số</label>
        <select id="metric" class="form-control">
            <option value="examinations">Lượt khám</option>
            <option value="revenue">Doanh thu</option>
        </select>
    </div>
    <div class="col-md-2">
        <label>&nbsp;</label>
        <button id="btn-load" class="btn btn-primary form-control">
            <i class="fa fa-search"></i> Xem
        </button>
    </div>
</div>

{{-- Xu hướng --}}
<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title">Biểu đồ xu hướng</h3>
        <div class="box-tools">
            <div class="btn-group" data-toggle="buttons">
                <label class="btn btn-default btn-sm active">
                    <input type="radio" name="mode" value="daily" checked> Theo ngày
                </label>
                <label class="btn btn-default btn-sm">
                    <input type="radio" name="mode" value="monthly"> Theo tháng
                </label>
            </div>
        </div>
    </div>
    <div class="box-body">
        <div id="chart-trend" class="chart-box"></div>
    </div>
</div>

{{-- BN/giờ --}}
<div class="row">
    <div class="col-md-4">
        <div class="box box-info">
            <div class="box-header with-border">
                <h3 class="box-title">BN/giờ trung bình</h3>
            </div>
            <div class="box-body">
                <div id="kpi-avg-per-hour" class="kpi-card text-info">--</div>
            </div>
        </div>
    </div>
    <div class="col-md-8">
        <div class="box box-info">
            <div class="box-header with-border">
                <h3 class="box-title">Phân bố BN theo khung giờ</h3>
            </div>
            <div class="box-body">
                <div id="chart-by-hour" class="chart-box"></div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('after-scripts')
<script>
window.TREND_CFG = {
    routes: {
        trendChart:      '{{ route("dashboard.trends.chart") }}',
        patientsPerHour: '{{ route("dashboard.trends.patients-per-hour") }}',
        overloadAlert:   '{{ route("dashboard.trends.overload-alert") }}'
    }
};
</script>
<script src="{{ asset('js/dashboard/trend-charts.js') }}"></script>
@endpush
```

- [ ] **Step 2: Tạo JS module**

Tạo `public/js/dashboard/trend-charts.js`:

```javascript
(function (win, $) {
    'use strict';

    var R = (win.TREND_CFG || {}).routes || {};

    function getParams() {
        return {
            from:   $('#from-date').val(),
            to:     $('#to-date').val(),
            mode:   $('input[name="mode"]:checked').val() || 'daily',
            metric: $('#metric').val() || 'examinations'
        };
    }

    // ── Overload Alert ───────────────────────────────────────────────────────

    function loadOverloadAlert() {
        var today = new Date().toISOString().split('T')[0];
        $.ajax({ url: R.overloadAlert, data: { date: today }, dataType: 'json' })
            .done(function (res) {
                var cls = 'alert-' + res.status;
                var labels = {
                    'overload':  '⚠ Quá tải: ' + res.today_count + ' lượt hôm nay (TB 30 ngày: ' + res.average_30d + ')',
                    'underload': '↓ Dưới công suất: ' + res.today_count + ' lượt hôm nay (TB 30 ngày: ' + res.average_30d + ')',
                    'normal':    '✓ Bình thường: ' + res.today_count + ' lượt hôm nay (TB 30 ngày: ' + res.average_30d + ')'
                };
                $('#overload-alert-box').html(
                    '<div class="alert-card ' + cls + '">' + (labels[res.status] || '') + '</div>'
                );
            });
    }

    // ── Trend Chart ──────────────────────────────────────────────────────────

    function loadTrendChart() {
        var p = getParams();
        $.ajax({ url: R.trendChart, data: p, dataType: 'json' })
            .done(function (res) {
                var labels   = res.labels || [];
                var current  = res.current || [];
                var previous = res.previous || [];

                var metricLabel = p.metric === 'revenue' ? 'Doanh thu (đ)' : 'Lượt khám';

                Highcharts.chart('chart-trend', {
                    chart: { type: 'line' },
                    title: { text: 'Xu hướng ' + metricLabel },
                    xAxis: { categories: labels },
                    yAxis: { title: { text: metricLabel }, allowDecimals: false },
                    series: [
                        { name: 'Kỳ hiện tại', data: current, dashStyle: 'Solid' },
                        { name: 'Kỳ trước',    data: previous, dashStyle: 'Dash', color: '#aaa' }
                    ],
                    credits: { enabled: false }
                });
            });
    }

    // ── BN/giờ ───────────────────────────────────────────────────────────────

    function loadPatientsPerHour() {
        var p = getParams();
        $.ajax({ url: R.patientsPerHour, data: { from: p.from, to: p.to }, dataType: 'json' })
            .done(function (res) {
                $('#kpi-avg-per-hour').text(res.average_per_hour + ' BN/giờ');

                var hours  = (res.by_hour || []).map(function (r) { return r.hour + 'h'; });
                var counts = (res.by_hour || []).map(function (r) { return r.count; });

                Highcharts.chart('chart-by-hour', {
                    chart: { type: 'column' },
                    title: { text: 'BN theo khung giờ' },
                    xAxis: { categories: hours, title: { text: 'Giờ' } },
                    yAxis: { title: { text: 'Số BN' }, allowDecimals: false },
                    legend: { enabled: false },
                    series: [{ name: 'Số BN', data: counts }],
                    credits: { enabled: false }
                });
            });
    }

    // ── Init ─────────────────────────────────────────────────────────────────

    function loadAll() {
        loadTrendChart();
        loadPatientsPerHour();
    }

    $(document).ready(function () {
        loadOverloadAlert();
        loadAll();
        $('#btn-load').on('click', loadAll);
        $('input[name="mode"]').on('change', loadAll);
        $('#metric').on('change', loadAll);
    });

})(window, jQuery);
```

- [ ] **Step 3: Kiểm tra thủ công**

Truy cập `http://localhost:8000/dashboard/trends`, xác nhận:
- [ ] Alert box hiển thị trạng thái đúng màu
- [ ] Line chart render với 2 đường (current + previous nét đứt)
- [ ] Toggle Daily/Monthly reload chart
- [ ] KPI card hiển thị số BN/giờ
- [ ] Bar chart theo khung giờ render đúng

- [ ] **Step 4: Commit**

```bash
git add resources/views/dashboard/trend-analysis.blade.php \
        public/js/dashboard/trend-charts.js
git commit -m "feat: add trend-analysis view and JS module with line chart, BN/hour, overload alert"
```

---

## Chunk 3: Operating Room (Service + Controller + Routes + Views + JS)

### Task 3.1: Tạo OperatingRoomService

**Files:**
- Create: `app/Services/Dashboard/OperatingRoomService.php`
- Create: `tests/Unit/Dashboard/OperatingRoomServiceTest.php`

- [ ] **Step 1: Tạo file test**

Tạo `tests/Unit/Dashboard/OperatingRoomServiceTest.php`:

```php
<?php

namespace Tests\Unit\Dashboard;

use Tests\TestCase;
use App\Services\Dashboard\OperatingRoomService;
use Carbon\Carbon;

class OperatingRoomServiceTest extends TestCase
{
    protected $service;

    protected function setUp()
    {
        parent::setUp();
        $this->service = new OperatingRoomService();
    }

    /** @test */
    public function it_calculates_duration_in_minutes_correctly()
    {
        // start: 20260331083000, end: 20260331090000 → 30 phút
        $minutes = $this->service->calcDurationMinutes(20260331083000, 20260331090000);
        $this->assertEquals(30, $minutes);
    }

    /** @test */
    public function it_returns_zero_for_invalid_times()
    {
        $minutes = $this->service->calcDurationMinutes(null, null);
        $this->assertEquals(0, $minutes);
    }

    /** @test */
    public function it_calculates_utilization_percentage()
    {
        // 240 phút sử dụng / (1 ngày × 480 phút) = 50%
        $pct = $this->service->calcUtilizationPct(240, 1);
        $this->assertEquals(50.0, $pct);
    }

    /** @test */
    public function it_determines_status_optimal()
    {
        $this->assertEquals('optimal',  $this->service->getUtilizationStatus(85.0));
    }

    /** @test */
    public function it_determines_status_overload()
    {
        $this->assertEquals('overload',  $this->service->getUtilizationStatus(105.0));
    }

    /** @test */
    public function it_determines_status_underload()
    {
        $this->assertEquals('underload', $this->service->getUtilizationStatus(60.0));
    }

    /** @test */
    public function it_builds_heatmap_matrix_correctly()
    {
        $rows = [
            (object)['room_name' => 'Phòng 1', 'day_val' => 20260301, 'total_cases' => 5],
            (object)['room_name' => 'Phòng 1', 'day_val' => 20260302, 'total_cases' => 3],
            (object)['room_name' => 'Phòng 2', 'day_val' => 20260301, 'total_cases' => 4],
        ];

        $result = $this->service->buildHeatmapData($rows);

        $this->assertEquals(['Phòng 1', 'Phòng 2'], $result['rooms']);
        $this->assertCount(2, $result['dates']);
        $this->assertEquals([[5, 3], [4, 0]], $result['matrix']);
    }
}
```

- [ ] **Step 2: Chạy test để xác nhận FAIL**

```bash
php artisan test tests/Unit/Dashboard/OperatingRoomServiceTest.php
```

Expected: FAIL với "Class not found"

- [ ] **Step 3: Tạo OperatingRoomService**

Tạo `app/Services/Dashboard/OperatingRoomService.php`:

```php
<?php

namespace App\Services\Dashboard;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class OperatingRoomService
{
    const WORKING_MINUTES_PER_DAY = 480; // 8h × 60 phút

    /**
     * Tính thời gian (phút) giữa 2 mốc thời gian YmdHis dạng NUMBER
     */
    public function calcDurationMinutes($startRaw, $endRaw): int
    {
        if (!$startRaw || !$endRaw) {
            return 0;
        }
        try {
            $start = Carbon::createFromFormat('YmdHis', sprintf('%014d', (int) $startRaw));
            $end   = Carbon::createFromFormat('YmdHis', sprintf('%014d', (int) $endRaw));
            return max(0, (int) $start->diffInMinutes($end));
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Tính % công suất sử dụng phòng mổ
     */
    public function calcUtilizationPct(int $totalMinutes, int $workingDays): float
    {
        if ($workingDays <= 0) return 0.0;
        return round($totalMinutes / ($workingDays * self::WORKING_MINUTES_PER_DAY) * 100, 2);
    }

    /**
     * Xác định trạng thái công suất
     */
    public function getUtilizationStatus(float $pct): string
    {
        if ($pct > 100) return 'overload';
        if ($pct >= 70) return 'optimal';
        return 'underload';
    }

    /**
     * Build dữ liệu heatmap từ rows Oracle
     */
    public function buildHeatmapData(array $rows): array
    {
        $rooms = array_values(array_unique(array_map(function ($r) { return $r->room_name; }, $rows)));
        $dates = array_values(array_unique(array_map(function ($r) { return $r->day_val; }, $rows)));
        sort($dates);

        // Format ngày hiển thị
        $dateLabels = array_map(function ($d) {
            $s = (string) $d;
            return substr($s, 6, 2) . '/' . substr($s, 4, 2);
        }, $dates);

        // Build lookup: room → date → count
        $lookup = [];
        foreach ($rows as $r) {
            $lookup[$r->room_name][$r->day_val] = (int) $r->total_cases;
        }

        $matrix = [];
        foreach ($rooms as $room) {
            $rowData = [];
            foreach ($dates as $d) {
                $rowData[] = $lookup[$room][$d] ?? 0;
            }
            $matrix[] = $rowData;
        }

        return [
            'rooms'  => $rooms,
            'dates'  => $dateLabels,
            'matrix' => $matrix,
        ];
    }

    /**
     * Số ca PT theo phòng theo ngày
     */
    public function getCasesPerRoom(string $from, string $to): array
    {
        $fromDate = Carbon::parse($from)->startOfDay()->format('YmdHis');
        $toDate   = Carbon::parse($to)->endOfDay()->format('YmdHis');

        $rows = DB::connection('HISPro')
            ->table('his_service_req as sr')
            ->join('his_execute_room as er', 'er.ROOM_ID', '=', 'sr.EXECUTE_ROOM_ID')
            ->selectRaw('er.EXECUTE_ROOM_NAME as room_name,
                         TRUNC(sr.START_TIME / 1000000) as day_val,
                         COUNT(*) as total_cases')
            ->where('sr.SERVICE_REQ_TYPE_ID', 4)
            ->where('sr.IS_DELETE', 0)
            ->where('sr.IS_ACTIVE', 1)
            ->whereNotNull('sr.START_TIME')
            ->whereBetween('sr.INTRUCTION_TIME', [$fromDate, $toDate])
            ->groupByRaw('er.EXECUTE_ROOM_NAME, TRUNC(sr.START_TIME / 1000000)')
            ->orderBy('room_name')
            ->orderBy('day_val')
            ->get()
            ->toArray();

        return $this->buildHeatmapData($rows);
    }

    /**
     * % Công suất sử dụng phòng mổ
     */
    public function getUtilization(string $from, string $to): array
    {
        $fromDate    = Carbon::parse($from)->startOfDay()->format('YmdHis');
        $toDate      = Carbon::parse($to)->endOfDay()->format('YmdHis');
        // Bệnh viện làm 6 ngày/tuần (Mon-Sat), chỉ nghỉ CN
        $startDate   = Carbon::parse($from);
        $endDate     = Carbon::parse($to);
        $workingDays = 0;
        for ($d = $startDate->copy(); $d->lte($endDate); $d->addDay()) {
            if ($d->dayOfWeek !== Carbon::SUNDAY) $workingDays++;
        }
        $workingDays = max(1, $workingDays);

        // Lấy raw records (không aggregate thời gian trong SQL)
        $rows = DB::connection('HISPro')
            ->table('his_service_req as sr')
            ->join('his_execute_room as er', 'er.ROOM_ID', '=', 'sr.EXECUTE_ROOM_ID')
            ->selectRaw('sr.EXECUTE_ROOM_ID as room_id, er.EXECUTE_ROOM_NAME as room_name,
                         sr.START_TIME, sr.FINISH_TIME')
            ->where('sr.SERVICE_REQ_TYPE_ID', 4)
            ->where('sr.IS_DELETE', 0)
            ->where('sr.IS_ACTIVE', 1)
            ->whereNotNull('sr.START_TIME')
            ->whereNotNull('sr.FINISH_TIME')
            ->whereBetween('sr.INTRUCTION_TIME', [$fromDate, $toDate])
            ->get();

        // Group và tính thời gian trong PHP
        $grouped = [];
        foreach ($rows as $row) {
            $key = $row->room_id;
            if (!isset($grouped[$key])) {
                $grouped[$key] = [
                    'room_name'     => $row->room_name,
                    'total_cases'   => 0,
                    'total_minutes' => 0,
                ];
            }
            $grouped[$key]['total_cases']++;
            $grouped[$key]['total_minutes'] += $this->calcDurationMinutes(
                $row->START_TIME,
                $row->FINISH_TIME
            );
        }

        $result = [];
        foreach ($grouped as $data) {
            $pct = $this->calcUtilizationPct($data['total_minutes'], $workingDays);
            $result[] = [
                'room_name'       => $data['room_name'],
                'total_cases'     => $data['total_cases'],
                'total_minutes'   => $data['total_minutes'],
                'working_days'    => $workingDays,
                'utilization_pct' => $pct,
                'status'          => $this->getUtilizationStatus($pct),
            ];
        }

        usort($result, function ($a, $b) { return strcmp($a['room_name'], $b['room_name']); });

        return $result;
    }
}
```

- [ ] **Step 4: Chạy test để xác nhận PASS**

```bash
php artisan test tests/Unit/Dashboard/OperatingRoomServiceTest.php
```

Expected: 7 tests PASS

- [ ] **Step 5: Commit**

```bash
git add app/Services/Dashboard/OperatingRoomService.php \
        tests/Unit/Dashboard/OperatingRoomServiceTest.php
git commit -m "feat: add OperatingRoomService with Carbon-based duration calculation and utilization logic"
```

---

### Task 3.2: Tạo OperatingRoomController + Routes

**Files:**
- Create: `app/Http/Controllers/Dashboard/OperatingRoomController.php`
- Create: `tests/Feature/Dashboard/OperatingRoomControllerTest.php`
- Modify: `routes/web.php`

- [ ] **Step 1: Tạo file test**

Tạo `tests/Feature/Dashboard/OperatingRoomControllerTest.php`:

```php
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
```

- [ ] **Step 2: Tạo OperatingRoomController**

Tạo `app/Http/Controllers/Dashboard/OperatingRoomController.php`:

```php
<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Services\Dashboard\OperatingRoomService;
use Illuminate\Http\Request;

class OperatingRoomController extends Controller
{
    protected $orService;

    public function __construct(OperatingRoomService $orService)
    {
        $this->orService = $orService;
    }

    public function index()
    {
        return view('dashboard.operating-room');
    }

    /**
     * GET /dashboard/operating-room/cases-per-room
     */
    public function casesPerRoom(Request $request)
    {
        $request->validate([
            'from' => 'required|date',
            'to'   => 'required|date|after_or_equal:from',
        ]);

        $data = $this->orService->getCasesPerRoom(
            $request->input('from'),
            $request->input('to')
        );

        return response()->json($data);
    }

    /**
     * GET /dashboard/operating-room/utilization
     */
    public function utilization(Request $request)
    {
        $request->validate([
            'from' => 'required|date',
            'to'   => 'required|date|after_or_equal:from',
        ]);

        $data = $this->orService->getUtilization(
            $request->input('from'),
            $request->input('to')
        );

        return response()->json(['data' => $data]);
    }
}
```

- [ ] **Step 3: Thêm routes vào `routes/web.php`**

Thêm tiếp vào **bên trong** group `checkrole:dashboard` của `routes/web.php`:

```php
        // ── Operating Room ────────────────────────────────────────────────────
        Route::get('dashboard/operating-room', 'Dashboard\OperatingRoomController@index')
             ->name('dashboard.operating-room');
        Route::get('dashboard/operating-room/cases-per-room', 'Dashboard\OperatingRoomController@casesPerRoom')
             ->name('dashboard.operating-room.cases-per-room');
        Route::get('dashboard/operating-room/utilization', 'Dashboard\OperatingRoomController@utilization')
             ->name('dashboard.operating-room.utilization');
```

- [ ] **Step 4: Chạy test**

```bash
php artisan test tests/Feature/Dashboard/OperatingRoomControllerTest.php
```

Expected: 3 tests PASS

- [ ] **Step 5: Commit**

```bash
git add app/Http/Controllers/Dashboard/OperatingRoomController.php \
        tests/Feature/Dashboard/OperatingRoomControllerTest.php \
        routes/web.php
git commit -m "feat: add OperatingRoomController with cases-per-room and utilization endpoints"
```

---

### Task 3.3: Tạo Blade view + JS cho Operating Room

**Files:**
- Create: `resources/views/dashboard/operating-room.blade.php`
- Create: `public/js/dashboard/operating-room.js`

- [ ] **Step 1: Tạo Blade view**

Tạo `resources/views/dashboard/operating-room.blade.php`:

```blade
@extends('adminlte::page')

@section('title', 'Công suất phòng mổ')

@section('content_header')
<h1>Công suất phòng mổ <small>Chỉ phẫu thuật</small></h1>
{{-- Breadcrumbs nếu cần: đăng ký 'dashboard.operating-room' trong routes/breadcrumbs.php --}}
@endsection

@push('after-styles')
<style>
    .filter-row { margin-bottom: 15px; }
    .chart-box  { min-height: 350px; }
    .status-overload  { color: #a94442; font-weight: bold; }
    .status-optimal   { color: #3c763d; font-weight: bold; }
    .status-underload { color: #8a6d3b; font-weight: bold; }
</style>
@endpush

@section('content')
<div class="row filter-row">
    <div class="col-md-3">
        <label>Từ ngày</label>
        <input type="date" id="from-date" class="form-control" value="{{ date('Y-m-01') }}">
    </div>
    <div class="col-md-3">
        <label>Đến ngày</label>
        <input type="date" id="to-date" class="form-control" value="{{ date('Y-m-d') }}">
    </div>
    <div class="col-md-2">
        <label>&nbsp;</label>
        <button id="btn-load" class="btn btn-primary form-control">
            <i class="fa fa-search"></i> Xem
        </button>
    </div>
</div>

{{-- Heatmap: ca mổ/phòng/ngày --}}
<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title">Số ca phẫu thuật theo phòng / ngày</h3>
    </div>
    <div class="box-body">
        <div id="chart-heatmap" class="chart-box"></div>
    </div>
</div>

{{-- Bar chart: % công suất --}}
<div class="box box-danger">
    <div class="box-header with-border">
        <h3 class="box-title">% Công suất sử dụng phòng mổ (mặc định 8h/ngày)</h3>
    </div>
    <div class="box-body">
        <div id="chart-utilization" class="chart-box"></div>
        <table class="table table-bordered table-hover table-condensed" id="tbl-utilization">
            <thead>
                <tr>
                    <th>Phòng mổ</th>
                    <th>Số ca</th>
                    <th>Tổng phút sử dụng</th>
                    <th>Ngày làm việc</th>
                    <th>% Công suất</th>
                    <th>Trạng thái</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>
@endsection

@push('after-scripts')
<script>
window.OR_CFG = {
    routes: {
        casesPerRoom: '{{ route("dashboard.operating-room.cases-per-room") }}',
        utilization:  '{{ route("dashboard.operating-room.utilization") }}'
    }
};
</script>
<script src="{{ asset('js/dashboard/operating-room.js') }}"></script>
@endpush
```

- [ ] **Step 2: Tạo JS module**

Tạo `public/js/dashboard/operating-room.js`:

```javascript
(function (win, $) {
    'use strict';

    var R = (win.OR_CFG || {}).routes || {};

    function getParams() {
        return { from: $('#from-date').val(), to: $('#to-date').val() };
    }

    // ── Heatmap ──────────────────────────────────────────────────────────────

    function showError(containerId, msg) {
        $('#' + containerId).html('<div class="text-center text-danger" style="padding:40px">' + (msg || 'Không thể tải dữ liệu') + '</div>');
    }

    function loadCasesPerRoom() {
        $.ajax({ url: R.casesPerRoom, data: getParams(), dataType: 'json' })
            .fail(function () { showError('chart-heatmap', 'Không thể kết nối HIS'); })
            .done(function (res) {
                var series = (res.rooms || []).map(function (room, idx) {
                    return { name: room, data: (res.matrix || [])[idx] || [] };
                });

                Highcharts.chart('chart-heatmap', {
                    chart: { type: 'column' },
                    title: { text: 'Ca PT theo phòng / ngày' },
                    xAxis: { categories: res.dates || [], crosshair: true },
                    yAxis: { title: { text: 'Số ca' }, allowDecimals: false },
                    plotOptions: { column: { grouping: true, shadow: false } },
                    series: series,
                    credits: { enabled: false }
                });
            });
    }

    // ── Utilization Bar Chart ─────────────────────────────────────────────────

    function loadUtilization() {
        $.ajax({ url: R.utilization, data: getParams(), dataType: 'json' })
            .fail(function () { showError('chart-utilization', 'Không thể kết nối HIS'); })
            .done(function (res) {
                var data = res.data || [];
                var rooms  = data.map(function (r) { return r.room_name; });
                var values = data.map(function (r) { return r.utilization_pct; });
                var colors = data.map(function (r) {
                    if (r.status === 'overload')  return '#d9534f';
                    if (r.status === 'optimal')   return '#5cb85c';
                    return '#f0ad4e';
                });

                Highcharts.chart('chart-utilization', {
                    chart: { type: 'bar' },
                    title: { text: '% Công suất sử dụng phòng mổ' },
                    xAxis: { categories: rooms },
                    yAxis: {
                        title: { text: '% Sử dụng' },
                        max: 150,
                        plotLines: [{
                            value: 100, color: '#d9534f', width: 2,
                            label: { text: '100% (tối đa)', align: 'right' }
                        }, {
                            value: 70, color: '#f0ad4e', width: 1, dashStyle: 'Dash',
                            label: { text: '70% (ngưỡng tối ưu)', align: 'right' }
                        }]
                    },
                    legend: { enabled: false },
                    series: [{
                        name: '% Công suất',
                        data: values.map(function (v, i) { return { y: v, color: colors[i] }; })
                    }],
                    credits: { enabled: false }
                });

                var statusLabel = {
                    overload:  '<span class="status-overload">Quá tải</span>',
                    optimal:   '<span class="status-optimal">Tối ưu</span>',
                    underload: '<span class="status-underload">Chưa khai thác</span>'
                };

                var tbody = '';
                data.forEach(function (r) {
                    tbody += '<tr>'
                        + '<td>' + r.room_name + '</td>'
                        + '<td>' + r.total_cases + '</td>'
                        + '<td>' + r.total_minutes + ' phút</td>'
                        + '<td>' + r.working_days + '</td>'
                        + '<td>' + r.utilization_pct + '%</td>'
                        + '<td>' + (statusLabel[r.status] || r.status) + '</td>'
                        + '</tr>';
                });
                $('#tbl-utilization tbody').html(tbody || '<tr><td colspan="6" class="text-center">Không có dữ liệu</td></tr>');
            });
    }

    // ── Init ─────────────────────────────────────────────────────────────────

    function loadAll() {
        loadCasesPerRoom();
        loadUtilization();
    }

    $(document).ready(function () {
        $('#btn-load').on('click', loadAll);
        loadAll();
    });

})(window, jQuery);
```

- [ ] **Step 3: Kiểm tra thủ công**

Truy cập `http://localhost:8000/dashboard/operating-room`, xác nhận:
- [ ] Grouped column chart hiển thị ca PT theo phòng/ngày
- [ ] Bar chart công suất có đường ngưỡng 100% (đỏ) và 70% (vàng)
- [ ] Table hiển thị đúng màu status
- [ ] Chọn date range khác → click Xem → data reload

- [ ] **Step 4: Chạy tất cả tests**

```bash
php artisan test tests/Unit/Dashboard/ tests/Feature/Dashboard/
```

Expected: 18+ tests PASS, 0 FAIL

- [ ] **Step 5: Commit cuối**

```bash
git add resources/views/dashboard/operating-room.blade.php \
        public/js/dashboard/operating-room.js
git commit -m "feat: add operating-room view and JS module with heatmap and utilization chart"
```

---

## Kiểm tra tổng thể

- [ ] Chạy toàn bộ test suite

```bash
php artisan test
```

Expected: tất cả test PASS (không có regression)

- [ ] Kiểm tra thủ công 3 trang:
  - `http://localhost:8000/dashboard/doctor-stats`
  - `http://localhost:8000/dashboard/trends`
  - `http://localhost:8000/dashboard/operating-room`

- [ ] Kiểm tra routes đã đăng ký đúng

```bash
php artisan route:list | grep dashboard
```

Expected: thấy 11 routes mới (3 view pages + 8 API endpoints)
