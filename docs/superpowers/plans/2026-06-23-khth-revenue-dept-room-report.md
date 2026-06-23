# KHTH Report "Doanh thu theo khoa/phòng thực hiện" Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Thêm 1 trang report độc lập trong menu KHTH: doanh thu theo khoa thực hiện (biểu đồ + bảng) + chi tiết doanh thu các phòng thuộc khoa (DataTables + Excel), lọc theo giai đoạn/khoa/phòng, doanh thu tính bằng `amount * vir_price`.

**Architecture:** Bám template report on-time-result. `RevenueDeptRoomService` chứa SQL builders (`[$sql,$bindings]`) + helper chung (`commonConditions`, `normalizeRange`, `normalizeRows`) + tổng hợp thuần `buildDeptSummary` (unit-test). Controller `KHTH\RevenueDeptRoomController` mỏng (index/getSummary/fetch/export/departments/rooms). View Blade dùng Chart.js + DataTables + bộ lọc dùng partial chung.

**Tech Stack:** Laravel 5.5, yajra/laravel-oci8 (Oracle), yajra/laravel-datatables, maatwebsite/excel, Chart.js, AdminLTE 2, PHPUnit 6.

**Spec:** `docs/superpowers/specs/2026-06-23-khth-revenue-dept-room-report-design.md`

---

## File Structure

| File | Trách nhiệm | Thao tác |
|---|---|---|
| `app/Services/RevenueDeptRoomService.php` | SQL builders + helper chung + `buildDeptSummary` (thuần) + `getSummaryData` | Create |
| `tests/Unit/RevenueDeptRoomServiceTest.php` | Unit test `buildDeptSummary` | Create |
| `app/Http/Controllers/KHTH/RevenueDeptRoomController.php` | index/getSummary/fetch/export/departments/rooms | Create |
| `tests/Feature/RevenueDeptRoomControllerTest.php` | Feature test (mock service, không chạm DB) | Create |
| `app/Exports/RevenueDeptRoomExport.php` | Export Excel bảng chi tiết theo phòng | Create |
| `resources/views/khth/revenue-dept-room.blade.php` | View: filter + KPI + chart + bảng khoa + DataTables phòng | Create |
| `resources/views/khth/partials/search-revenue-dept-room.blade.php` | Partial bộ lọc | Create |
| `routes/web.php` | 6 route trong group `khth/` | Modify |
| `config/adminlte.php` | 1 mục menu KHTH | Modify |

**Quy ước đã xác minh (từ report on-time-result):** oci8 trả key cột VIẾT HOA → `normalizeRows()` mọi kết quả `DB::select`. Bind ngày `:from_time`/`:to_time` (KHÔNG `:from`/`:to`). Routes report KHTH nằm trong `Route::group(['prefix'=>'khth/','middleware'=>['checkrole:administrator']])`. DataTables: `use Yajra\Datatables\Datatables; Datatables::of(...)`. Partial `partials/load_data_button` tự gọi global `fetchData(startDate,endDate)` (định dạng `YYYY-MM-DD HH:mm:ss`); nút id `#load_data_button`. Helper `number_format` PHP cho VND. Menu AdminLTE 2: `'icon'=>'money'` (FA4, không tiền tố), có `'checkrole'`.

---

## Chunk 1: Service (logic thuần + SQL builders)

### Task 1: `buildDeptSummary` thuần (TDD)

**Files:**
- Create: `app/Services/RevenueDeptRoomService.php`
- Test: `tests/Unit/RevenueDeptRoomServiceTest.php`

- [ ] **Step 1: Viết test thất bại**

```php
<?php
// tests/Unit/RevenueDeptRoomServiceTest.php
namespace Tests\Unit;

use Tests\TestCase;
use App\Services\RevenueDeptRoomService;

class RevenueDeptRoomServiceTest extends TestCase
{
    /** @test */
    public function build_dept_summary_computes_kpi_and_percentages_natural_order()
    {
        // Mỗi $row = 1 khoa (đã GROUP BY): department_id, department_name, thanh_tien, so_luong
        $rows = [
            (object)['department_id' => 10, 'department_name' => 'Khoa Dược CS1', 'thanh_tien' => 600, 'so_luong' => 100],
            (object)['department_id' => 20, 'department_name' => 'Khoa CĐHA CS1', 'thanh_tien' => 300, 'so_luong' => 50],
            (object)['department_id' => 30, 'department_name' => 'Khoa XN CS1',   'thanh_tien' => 100, 'so_luong' => 20],
        ];

        $res = RevenueDeptRoomService::buildDeptSummary($rows, 7);

        // giữ thứ tự tự nhiên
        $this->assertEquals(['Khoa Dược CS1','Khoa CĐHA CS1','Khoa XN CS1'], array_column($res['by_department'], 'department_name'));
        $this->assertEquals([600, 300, 100], array_column($res['by_department'], 'thanh_tien'));
        $this->assertEquals([60.0, 30.0, 10.0], array_column($res['by_department'], 'pct'));

        $this->assertEquals(1000, $res['kpi']['tong_doanh_thu']);
        $this->assertEquals(3, $res['kpi']['so_khoa']);
        $this->assertEquals(7, $res['kpi']['so_phong']);
    }

    /** @test */
    public function build_dept_summary_handles_empty()
    {
        $res = RevenueDeptRoomService::buildDeptSummary([], 0);
        $this->assertEquals([], $res['by_department']);
        $this->assertEquals(0, $res['kpi']['tong_doanh_thu']);
        $this->assertEquals(0, $res['kpi']['so_khoa']);
        $this->assertEquals(0, $res['kpi']['so_phong']);
    }
}
```

- [ ] **Step 2: Chạy test, xác nhận FAIL**

Run: `php vendor/bin/phpunit tests/Unit/RevenueDeptRoomServiceTest.php`
Expected: FAIL — "Class 'App\Services\RevenueDeptRoomService' not found".

- [ ] **Step 3: Tạo service + `buildDeptSummary`**

```php
<?php
// app/Services/RevenueDeptRoomService.php
namespace App\Services;

use Illuminate\Http\Request;
use Carbon\Carbon;

class RevenueDeptRoomService
{
    /**
     * Tổng hợp doanh thu theo khoa (thuần). Giữ thứ tự tự nhiên (không sắp xếp).
     * @param iterable $rows  mỗi phần tử: ->department_id, ->department_name, ->thanh_tien, ->so_luong
     * @param int $soPhong    số phòng (distinct) trong kỳ, đếm sẵn ở tầng query
     * @return array{kpi: array, by_department: array}
     */
    public static function buildDeptSummary($rows, $soPhong = 0)
    {
        $byDept = [];
        $tong = 0;
        foreach ($rows as $r) {
            $tt = (float) $r->thanh_tien;
            $byDept[] = [
                'department_id'   => (int) $r->department_id,
                'department_name' => $r->department_name,
                'thanh_tien'      => $tt,
                'so_luong'        => (float) $r->so_luong,
                'pct'             => 0,
            ];
            $tong += $tt;
        }
        foreach ($byDept as &$d) {
            $d['pct'] = $tong > 0 ? round($d['thanh_tien'] / $tong * 100, 1) : 0;
        }
        unset($d);

        return [
            'kpi' => [
                'tong_doanh_thu' => $tong,
                'so_khoa'        => count($byDept),
                'so_phong'       => (int) $soPhong,
            ],
            'by_department' => $byDept,
        ];
    }
}
```

- [ ] **Step 4: Chạy test, xác nhận PASS**

Run: `php vendor/bin/phpunit tests/Unit/RevenueDeptRoomServiceTest.php`
Expected: PASS (2 tests).

- [ ] **Step 5: Commit**

```bash
git add app/Services/RevenueDeptRoomService.php tests/Unit/RevenueDeptRoomServiceTest.php
git commit -m "feat: RevenueDeptRoomService::buildDeptSummary() + unit test"
```

### Task 2: SQL builders + getSummaryData (service)

**Files:**
- Modify: `app/Services/RevenueDeptRoomService.php`

> SQL builders không unit-test (cần Oracle); kiểm chứng ở Task 8. Dùng named bindings.

- [ ] **Step 1: Thêm helper chuẩn hóa ngày + WHERE chung + normalizeRows**

```php
    /** Chuẩn hóa from/to (Y-m-d hoặc Y-m-d H:i:s) -> YmdHis */
    protected function normalizeRange(Request $request)
    {
        $from = $request->input('date_from');
        $to   = $request->input('date_to');
        if (strlen($from) == 10) $from = Carbon::createFromFormat('Y-m-d', $from)->startOfDay()->format('Y-m-d H:i:s');
        if (strlen($to)   == 10) $to   = Carbon::createFromFormat('Y-m-d', $to)->endOfDay()->format('Y-m-d H:i:s');
        return [
            Carbon::createFromFormat('Y-m-d H:i:s', $from)->format('YmdHis'),
            Carbon::createFromFormat('Y-m-d H:i:s', $to)->format('YmdHis'),
        ];
    }

    /** WHERE + bindings dùng chung cho summary/detail/dropdown */
    protected function commonConditions(Request $request)
    {
        list($from, $to) = $this->normalizeRange($request);
        $fromDay = substr($from, 0, 8) . '000000';
        $toDay   = substr($to, 0, 8) . '000000';

        $conds = [
            "ss.tdl_intruction_date BETWEEN :from_day AND :to_day", // cột index HIS_SERE_SERV_INDEX16
            "sr.intruction_time BETWEEN :from_time AND :to_time",
            "sr.is_active = 1", "sr.is_delete = 0", "ss.is_delete = 0",
        ];
        $binds = ['from_day' => $fromDay, 'to_day' => $toDay, 'from_time' => $from, 'to_time' => $to];

        if ($request->filled('department_id')) {
            $conds[] = "ss.tdl_execute_department_id = :department_id";
            $binds['department_id'] = $request->input('department_id');
        }
        if ($request->filled('room_id')) {
            $conds[] = "ss.tdl_execute_room_id = :room_id";
            $binds['room_id'] = $request->input('room_id');
        }
        return [$conds, $binds];
    }

    /** Oracle trả tên cột HOA -> lowercase (bắt buộc trước khi dùng) */
    public function normalizeRows($rawRows)
    {
        return array_map(function ($row) {
            return (object) array_change_key_case((array) $row, CASE_LOWER);
        }, $rawRows);
    }
```

- [ ] **Step 2: Thêm `buildDeptSummarySqlAndBindings` + `buildRoomCountSqlAndBindings`**

```php
    /** Doanh thu theo khoa (mỗi khoa 1 dòng) */
    public function buildDeptSummarySqlAndBindings(Request $request)
    {
        list($conds, $binds) = $this->commonConditions($request);
        $where = implode(' AND ', $conds);
        $sql = "
            SELECT ss.tdl_execute_department_id AS department_id,
                   d.department_name             AS department_name,
                   SUM(ss.amount * ss.vir_price) AS thanh_tien,
                   SUM(ss.amount)                AS so_luong
            FROM his_sere_serv ss
            JOIN his_service_req sr ON sr.id = ss.service_req_id
            JOIN his_department d   ON d.id  = ss.tdl_execute_department_id
            WHERE $where
            GROUP BY ss.tdl_execute_department_id, d.department_name
        ";
        return [$sql, $binds];
    }

    /** Đếm số phòng (distinct) trong kỳ theo bộ lọc */
    public function buildRoomCountSqlAndBindings(Request $request)
    {
        list($conds, $binds) = $this->commonConditions($request);
        $where = implode(' AND ', $conds);
        $sql = "
            SELECT COUNT(DISTINCT ss.tdl_execute_room_id) AS so_phong
            FROM his_sere_serv ss
            JOIN his_service_req sr ON sr.id = ss.service_req_id
            WHERE $where
        ";
        return [$sql, $binds];
    }
```

- [ ] **Step 3: Thêm `buildRoomDetailSqlAndBindings` (DataTables chi tiết theo phòng)**

```php
    /** Chi tiết doanh thu theo phòng (mỗi phòng 1 dòng) cho DataTables & Export */
    public function buildRoomDetailSqlAndBindings(Request $request)
    {
        list($conds, $binds) = $this->commonConditions($request);
        $where = implode(' AND ', $conds);
        $sql = "
            SELECT d.department_name        AS department_name,
                   NVL(er.execute_room_name, '(không xác định)') AS room_name,
                   SUM(ss.amount * ss.vir_price) AS thanh_tien,
                   SUM(ss.amount)                AS so_luong
            FROM his_sere_serv ss
            JOIN his_service_req sr ON sr.id = ss.service_req_id
            JOIN his_department d   ON d.id  = ss.tdl_execute_department_id
            LEFT JOIN his_execute_room er ON er.room_id = ss.tdl_execute_room_id
            WHERE $where
            GROUP BY ss.tdl_execute_department_id, d.department_name, ss.tdl_execute_room_id, er.execute_room_name
        ";
        return [$sql, $binds];
    }
```

- [ ] **Step 4: Thêm `buildDepartmentsSqlAndBindings` + `buildRoomsSqlAndBindings` (dropdown)**

```php
    /** Danh sách khoa có doanh thu trong kỳ */
    public function buildDepartmentsSqlAndBindings(Request $request)
    {
        list($conds, $binds) = $this->commonConditions($request);
        $where = implode(' AND ', $conds);
        $sql = "
            SELECT DISTINCT ss.tdl_execute_department_id AS department_id, d.department_name AS department_name
            FROM his_sere_serv ss
            JOIN his_service_req sr ON sr.id = ss.service_req_id
            JOIN his_department d   ON d.id  = ss.tdl_execute_department_id
            WHERE $where
            ORDER BY d.department_name
        ";
        return [$sql, $binds];
    }

    /** Danh sách phòng (theo khoa đang chọn nếu có) */
    public function buildRoomsSqlAndBindings(Request $request)
    {
        list($conds, $binds) = $this->commonConditions($request);
        $where = implode(' AND ', $conds);
        $sql = "
            SELECT DISTINCT ss.tdl_execute_room_id AS room_id, er.execute_room_name AS room_name
            FROM his_sere_serv ss
            JOIN his_service_req sr ON sr.id = ss.service_req_id
            LEFT JOIN his_execute_room er ON er.room_id = ss.tdl_execute_room_id
            WHERE $where AND er.execute_room_name IS NOT NULL
            ORDER BY er.execute_room_name
        ";
        return [$sql, $binds];
    }
```

- [ ] **Step 5: Thêm `getSummaryData` (chạy DB rồi tổng hợp)**

```php
    /** Lấy doanh thu theo khoa + số phòng rồi tổng hợp. */
    public function getSummaryData(Request $request)
    {
        list($sqlD, $bD) = $this->buildDeptSummarySqlAndBindings($request);
        $deptRows = $this->normalizeRows(\DB::connection('HISPro')->select(\DB::raw($sqlD), $bD));

        list($sqlP, $bP) = $this->buildRoomCountSqlAndBindings($request);
        $cnt = $this->normalizeRows(\DB::connection('HISPro')->select(\DB::raw($sqlP), $bP));
        $soPhong = isset($cnt[0]) ? (int) $cnt[0]->so_phong : 0;

        return self::buildDeptSummary($deptRows, $soPhong);
    }
```

- [ ] **Step 6: Commit**

```bash
git add app/Services/RevenueDeptRoomService.php
git commit -m "feat: RevenueDeptRoomService SQL builders + getSummaryData"
```

---

## Chunk 2: Controller + routes + view + export + menu

### Task 3: Controller + routes (feature-tested)

**Files:**
- Create: `app/Http/Controllers/KHTH/RevenueDeptRoomController.php`
- Modify: `routes/web.php` (trong group `khth/`)
- Test: `tests/Feature/RevenueDeptRoomControllerTest.php`

- [ ] **Step 1: Viết feature test thất bại** (mock `getSummaryData` để KHÔNG chạm DB; theo pattern OnTimeResult feature test với `FakeAdminUser`)

```php
<?php
// tests/Feature/RevenueDeptRoomControllerTest.php
namespace Tests\Feature;

use Tests\TestCase;
use App\Services\RevenueDeptRoomService;
use Mockery;

class FakeRevAdminUser extends \App\User
{
    public function hasRole($r, $team = null, $requireAll = false) { return true; }
    public function can($permission, $arguments = []) { return true; }
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
```

- [ ] **Step 2: Chạy test, xác nhận FAIL**

Run: `php vendor/bin/phpunit tests/Feature/RevenueDeptRoomControllerTest.php`
Expected: FAIL — route/class chưa tồn tại.

- [ ] **Step 3: Tạo Controller + routes + view stub**

Controller:
```php
<?php
// app/Http/Controllers/KHTH/RevenueDeptRoomController.php
namespace App\Http\Controllers\KHTH;

use App\Http\Controllers\Controller;
use App\Services\RevenueDeptRoomService;
use App\Exports\RevenueDeptRoomExport;
use Illuminate\Http\Request;
use Yajra\Datatables\Datatables;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;
use DB;

class RevenueDeptRoomController extends Controller
{
    protected $service;

    public function __construct(RevenueDeptRoomService $service)
    {
        $this->service = $service;
    }

    public function index()
    {
        return view('khth.revenue-dept-room');
    }

    private function validateDates(Request $request)
    {
        $request->validate(['date_from' => 'required|date', 'date_to' => 'required|date']);
    }

    public function getSummary(Request $request)
    {
        $this->validateDates($request);
        return response()->json($this->service->getSummaryData($request));
    }

    public function departments(Request $request)
    {
        $this->validateDates($request);
        list($sql, $binds) = $this->service->buildDepartmentsSqlAndBindings($request);
        return response()->json($this->service->normalizeRows(DB::connection('HISPro')->select(DB::raw($sql), $binds)));
    }

    public function rooms(Request $request)
    {
        $this->validateDates($request);
        list($sql, $binds) = $this->service->buildRoomsSqlAndBindings($request);
        return response()->json($this->service->normalizeRows(DB::connection('HISPro')->select(DB::raw($sql), $binds)));
    }

    public function fetch(Request $request)
    {
        $this->validateDates($request);
        list($sql, $binds) = $this->service->buildRoomDetailSqlAndBindings($request);
        $rows = $this->service->normalizeRows(DB::connection('HISPro')->select(DB::raw($sql), $binds));

        return Datatables::of($rows)
            ->editColumn('thanh_tien', function ($r) { return number_format($r->thanh_tien); })
            ->editColumn('so_luong', function ($r) { return number_format($r->so_luong); })
            ->make(true);
    }

    public function export(Request $request)
    {
        $this->validateDates($request);
        $fileName = 'doanh_thu_khoa_phong_' . Carbon::now()->format('YmdHis') . '.xlsx';
        return Excel::download(new RevenueDeptRoomExport($request->all()), $fileName);
    }
}
```

Routes (trong group `khth/` đã có ở `routes/web.php`, cạnh `on-time-result-index`):
```php
Route::get('revenue-dept-room-index', 'KHTH\RevenueDeptRoomController@index')->name('khth.revenue-dept-room-index');
Route::get('revenue-dept-room-index/summary', 'KHTH\RevenueDeptRoomController@getSummary')->name('khth.revenue-dept-room-summary');
Route::get('revenue-dept-room-index/fetch', 'KHTH\RevenueDeptRoomController@fetch')->name('khth.revenue-dept-room-fetch');
Route::get('revenue-dept-room-index/export', 'KHTH\RevenueDeptRoomController@export')->name('khth.revenue-dept-room-export');
Route::get('revenue-dept-room-index/departments', 'KHTH\RevenueDeptRoomController@departments')->name('khth.revenue-dept-room-departments');
Route::get('revenue-dept-room-index/rooms', 'KHTH\RevenueDeptRoomController@rooms')->name('khth.revenue-dept-room-rooms');
```

View stub (bắt buộc để `index_renders_view` pass; Task 5 thay bản đầy đủ):
```blade
{{-- resources/views/khth/revenue-dept-room.blade.php (STUB) --}}
@extends('adminlte::page')
@section('title', 'Doanh thu theo khoa/phòng')
@section('content')
@stop
```

- [ ] **Step 4: Chạy test, xác nhận PASS**

Run: `php vendor/bin/phpunit tests/Feature/RevenueDeptRoomControllerTest.php`
Expected: PASS (2 tests).

- [ ] **Step 5: Commit**

```bash
git add app/Http/Controllers/KHTH/RevenueDeptRoomController.php routes/web.php tests/Feature/RevenueDeptRoomControllerTest.php resources/views/khth/revenue-dept-room.blade.php
git commit -m "feat: RevenueDeptRoomController + routes (index/summary/fetch/export/departments/rooms)"
```

### Task 4: Export Excel

**Files:**
- Create: `app/Exports/RevenueDeptRoomExport.php`

- [ ] **Step 1: Tạo Export (pattern OnTimeResultExport)**

```php
<?php
// app/Exports/RevenueDeptRoomExport.php
namespace App\Exports;

use Illuminate\Http\Request;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Illuminate\Support\Collection;
use App\Services\RevenueDeptRoomService;
use DB;

class RevenueDeptRoomExport implements FromCollection, WithHeadings, WithMapping
{
    protected $filters;
    protected $service;
    protected $rowNumber = 0;

    public function __construct(array $filters)
    {
        $this->filters = $filters;
        $this->service = new RevenueDeptRoomService();
    }

    public function collection()
    {
        $request = new Request($this->filters);
        list($sql, $binds) = $this->service->buildRoomDetailSqlAndBindings($request);
        $rows = $this->service->normalizeRows(DB::connection('HISPro')->select(DB::raw($sql), $binds));
        return new Collection($rows);
    }

    public function headings(): array
    {
        return ['STT', 'Khoa', 'Phòng', 'Doanh thu (VNĐ)', 'Số lượng'];
    }

    public function map($r): array
    {
        $this->rowNumber++;
        return [
            $this->rowNumber,
            $r->department_name,
            $r->room_name,
            (float) $r->thanh_tien,
            (float) $r->so_luong,
        ];
    }
}
```

- [ ] **Step 2: Smoke test cú pháp**

Run: `php -l app/Exports/RevenueDeptRoomExport.php`
Expected: "No syntax errors detected".

- [ ] **Step 3: Commit**

```bash
git add app/Exports/RevenueDeptRoomExport.php
git commit -m "feat: RevenueDeptRoomExport xuat Excel chi tiet theo phong"
```

### Task 5: View + partial filter

**Files:**
- Create: `resources/views/khth/partials/search-revenue-dept-room.blade.php`
- Modify (thay stub): `resources/views/khth/revenue-dept-room.blade.php`

- [ ] **Step 1: Partial bộ lọc** (date_range + khoa + phòng; dùng partial chung như on-time-result)

```blade
{{-- resources/views/khth/partials/search-revenue-dept-room.blade.php --}}
<div class="panel panel-default">
    <div class="panel-body">
        @include('partials.date_range')
        <div class="col-sm-12">
            <div class="form-group row">
                <div class="col-sm-4">
                    <label for="department_id">Khoa thực hiện</label>
                    <select id="department_id" class="form-control select2"><option value="">-- Tất cả --</option></select>
                </div>
                <div class="col-sm-4">
                    <label for="room_id">Phòng thực hiện</label>
                    <select id="room_id" class="form-control select2"><option value="">-- Tất cả --</option></select>
                </div>
            </div>
        </div>
        @include('partials.load_data_button')
    </div>
</div>
```

- [ ] **Step 2: View chính** — KPI + chart khoa + bảng khoa + DataTables phòng. Section content:

```blade
@extends('adminlte::page')
@section('title', 'Doanh thu theo khoa/phòng')
@section('content_header')<h1>Doanh thu theo khoa/phòng thực hiện</h1>@stop
@section('content')
@include('khth.partials.search-revenue-dept-room')

<div class="row">
  <div class="col-md-4"><div class="info-box"><span class="info-box-icon bg-green"><i class="fa fa-money"></i></span><div class="info-box-content"><span class="info-box-text">Tổng doanh thu (Tr)</span><span class="info-box-number" id="kpi-tong">0</span></div></div></div>
  <div class="col-md-4"><div class="info-box"><span class="info-box-icon bg-aqua"><i class="fa fa-hospital-o"></i></span><div class="info-box-content"><span class="info-box-text">Số khoa</span><span class="info-box-number" id="kpi-khoa">0</span></div></div></div>
  <div class="col-md-4"><div class="info-box"><span class="info-box-icon bg-yellow"><i class="fa fa-th"></i></span><div class="info-box-content"><span class="info-box-text">Số phòng</span><span class="info-box-number" id="kpi-phong">0</span></div></div></div>
</div>

<div class="row">
  <div class="col-md-7"><div class="box"><div class="box-header"><h3 class="box-title">Doanh thu theo khoa (triệu)</h3></div><div class="box-body"><canvas id="chart-khoa" height="140"></canvas></div></div></div>
  <div class="col-md-5"><div class="box"><div class="box-header"><h3 class="box-title">Bảng theo khoa</h3></div><div class="box-body table-responsive"><table class="table table-bordered" id="tbl-khoa"></table></div></div></div>
</div>

<div class="box">
  <div class="box-header"><h3 class="box-title">Chi tiết theo phòng</h3><button id="export_xlsx" class="btn btn-success btn-sm pull-right"><i class="fa fa-file-excel-o"></i> Xuất Excel</button></div>
  <div class="box-body table-responsive">
    <table id="detail-table" class="table table-hover" width="100%">
      <thead><tr><th>Khoa</th><th>Phòng</th><th>Doanh thu</th><th>Số lượng</th></tr></thead>
    </table>
  </div>
</div>
@stop
```

- [ ] **Step 3: JS** (`@push('after-scripts')`) — nạp Chart.js, gọi summary, vẽ chart + bảng khoa, nạp dropdown, init DataTables, drill-down khoa, export. Dùng convention `fetchData(startDate,endDate)`:

```blade
@push('after-scripts')
@stack('after-scripts-date-range')
@stack('after-scripts-load-data-button')
<script src="{{ asset('vendor/chart/js/Chart.min.js') }}"></script>
<script>
let chartKhoa=null, detailTable=null, curFrom=null, curTo=null;
const PALETTE=['#3c8dbc','#00a65a','#dd4b39','#f39c12','#605ca8','#39cccc','#d81b60','#00c0ef','#001f3f','#f012be'];

function getRange(){ var d=$('#date_range').data('daterangepicker'); return {from:d.startDate.format('YYYY-MM-DD HH:mm:ss'), to:d.endDate.format('YYYY-MM-DD HH:mm:ss')}; }
function baseFilters(){ return {date_from:curFrom, date_to:curTo, department_id:$('#department_id').val(), room_id:$('#room_id').val()}; }

// partial load_data_button tự gọi fetchData(startDate,endDate) khi tải trang & bấm nút
function fetchData(startDate, endDate){ curFrom=startDate; curTo=endDate; loadDropdowns(); reloadAll(); }

function loadDropdowns(){
  $.getJSON("{{ route('khth.revenue-dept-room-departments') }}", {date_from:curFrom, date_to:curTo}, function(data){
    var cur=$('#department_id').val();
    var h='<option value="">-- Tất cả --</option>';
    data.forEach(function(it){ h+='<option value="'+it.department_id+'">'+it.department_name+'</option>'; });
    $('#department_id').html(h).val(cur);
  });
  $.getJSON("{{ route('khth.revenue-dept-room-rooms') }}", {date_from:curFrom, date_to:curTo, department_id:$('#department_id').val()}, function(data){
    var cur=$('#room_id').val();
    var h='<option value="">-- Tất cả --</option>';
    data.forEach(function(it){ h+='<option value="'+it.room_id+'">'+it.room_name+'</option>'; });
    $('#room_id').html(h).val(cur);
  });
}

function loadSummary(){
  $.getJSON("{{ route('khth.revenue-dept-room-summary') }}", baseFilters(), function(res){
    var k=res.kpi;
    $('#kpi-tong').text(numberFmt(Math.round(k.tong_doanh_thu/1e6))); $('#kpi-khoa').text(k.so_khoa); $('#kpi-phong').text(k.so_phong);
    renderKhoaChart(res.by_department);
    renderKhoaTable(res.by_department);
  });
}
function numberFmt(n){ return (n||0).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ','); }

function renderKhoaChart(rows){
  var ctx=document.getElementById('chart-khoa').getContext('2d');
  if(chartKhoa) chartKhoa.destroy();
  chartKhoa=new Chart(ctx,{type:'bar',data:{labels:rows.map(r=>r.department_name),datasets:[{label:'Doanh thu (Tr)',data:rows.map(r=>Math.round(r.thanh_tien/1e6)),backgroundColor:rows.map((r,i)=>PALETTE[i%PALETTE.length])}]},options:{legend:{display:false},scales:{xAxes:[{ticks:{autoSkip:false,maxRotation:60,minRotation:45}}],yAxes:[{ticks:{beginAtZero:true}}]}}});
}
function renderKhoaTable(rows){
  var html='<thead><tr><th>Khoa</th><th>Doanh thu (Tr)</th><th>SL</th><th>%</th></tr></thead><tbody>';
  rows.forEach(function(r){
    html+='<tr class="drill" style="cursor:pointer" data-id="'+r.department_id+'"><td>'+r.department_name+'</td><td>'+numberFmt(Math.round(r.thanh_tien/1e6))+'</td><td>'+numberFmt(Math.round(r.so_luong))+'</td><td>'+r.pct+'%</td></tr>';
  });
  $('#tbl-khoa').html(html+'</tbody>');
}

function loadDetail(){
  if(detailTable){ detailTable.ajax.reload(); return; }
  detailTable=$('#detail-table').DataTable({
    processing:true, serverSide:true, destroy:true, scrollX:true,
    ajax:{ url:"{{ route('khth.revenue-dept-room-fetch') }}", data:function(d){ Object.assign(d, baseFilters()); } },
    columns:[ {data:'department_name'},{data:'room_name'},{data:'thanh_tien'},{data:'so_luong'} ]
  });
}
function reloadAll(){ loadSummary(); loadDetail(); }

$(function(){
  $('.select2').select2({width:'100%'});
  // đổi khoa -> nạp lại phòng
  $(document).on('change', '#department_id', function(){ loadDropdowns(); });
  // drill: click khoa ở bảng -> set filter khoa -> reload
  $(document).on('click', '#tbl-khoa .drill', function(){ $('#department_id').val($(this).data('id')).trigger('change'); reloadAll(); });
  // export
  $('#export_xlsx').click(function(){ window.location.href="{{ route('khth.revenue-dept-room-export') }}?"+$.param(baseFilters()); });
});
</script>
@endpush
```

> **Đã xác minh:** path Chart.js là `vendor/chart/js/Chart.min.js`; partial `load_data_button` tự gọi `fetchData(startDate,endDate)`. Nếu khác lúc triển khai → sửa cho khớp.

- [ ] **Step 4: Kiểm tra Blade compile**

Run: `php artisan view:clear` rồi `php vendor/bin/phpunit tests/Feature/RevenueDeptRoomControllerTest.php` (test `index_renders_view` render thật view với bản đầy đủ phải PASS).
Expected: PASS (2 tests).

- [ ] **Step 5: Commit**

```bash
git add resources/views/khth/revenue-dept-room.blade.php resources/views/khth/partials/search-revenue-dept-room.blade.php
git commit -m "feat: view revenue-dept-room (KPI, chart khoa, bang khoa, datatable phong, drill, export)"
```

### Task 6: Menu

**Files:**
- Modify: `config/adminlte.php`

- [ ] **Step 1: Thêm mục menu** trong submenu KHTH (cạnh mục 'Tỷ lệ trả KQ đúng hẹn'):

```php
[
    'text'      => 'Doanh thu theo khoa/phòng',
    'icon'      => 'money',
    'checkrole' => 'administrator',
    'route'     => 'khth.revenue-dept-room-index',
    'active'    => ['khth/revenue-dept-room-index*'],
],
```

- [ ] **Step 2: Kiểm tra** `php -l config/adminlte.php` → no syntax errors.

- [ ] **Step 3: Commit**

```bash
git add config/adminlte.php
git commit -m "feat: them menu KHTH 'Doanh thu theo khoa/phong'"
```

### Task 7: Cập nhật `readme.md`

**Files:**
- Modify: `readme.md`

- [ ] **Step 1: Thêm mục ngày mới ở đầu file** (hôm nay 23/06/2026 đã có heading; thêm dòng vào đầu phần `# 23/06/2026`):

```markdown
- Bổ sung báo cáo KHTH "Doanh thu theo khoa/phòng thực hiện": lọc theo giai đoạn/khoa/phòng, biểu đồ + bảng doanh thu theo khoa, chi tiết theo phòng (DataTables) + xuất Excel; doanh thu tính theo vir_price (amount × vir_price)
```
Cụ thể chèn ngay dưới dòng `# 23/06/2026` (trước dòng "Tình trạng giường...").

> Nếu lúc triển khai đã sang ngày khác, tạo heading ngày mới ở đầu file.

- [ ] **Step 2: Commit**

```bash
git add readme.md
git commit -m "docs: readme bo sung bao cao doanh thu theo khoa/phong (KHTH)"
```

### Task 8: Kiểm chứng thật (tinker + HTTP)

**Files:** (không sửa code trừ khi phát hiện lệch)

- [ ] **Step 1: Kiểm chứng getSummaryData qua oci8 (tinker)**

```
$svc=new App\Services\RevenueDeptRoomService();
$req=new Illuminate\Http\Request(['date_from'=>'2026-06-01','date_to'=>'2026-06-07']);
$res=$svc->getSummaryData($req);
echo 'so_khoa='.$res['kpi']['so_khoa'].' so_phong='.$res['kpi']['so_phong'].' tong_tr='.round($res['kpi']['tong_doanh_thu']/1e6).PHP_EOL;
```
Đối chiếu: ~26 khoa; tổng (Tr) hợp lý (~9.587 Tr cho tuần 01–07/06 nếu lọc giống khảo sát — lưu ý báo cáo lọc cả is_active nên có thể nhỏ hơn); doanh thu theo vir_price (< theo price).

- [ ] **Step 2: Kiểm chứng chi tiết phòng (tinker)**

```
list($sql,$b)=$svc->buildRoomDetailSqlAndBindings($req);
$rows=$svc->normalizeRows(DB::connection('HISPro')->select(DB::raw($sql),$b));
echo 'so_phong_dong='.count($rows).' vd='.json_encode([$rows[0]->department_name,$rows[0]->room_name,round($rows[0]->thanh_tien)],JSON_UNESCAPED_UNICODE).PHP_EOL;
```

- [ ] **Step 3: Kiểm chứng HTTP** (server `php artisan serve`, đăng nhập `dattt`/`Olala123`): login lấy cookie+csrf rồi `GET /khth/revenue-dept-room-index/summary?date_from=2026-06-01&date_to=2026-06-07` → HTTP 200, JSON `{kpi,by_department}`. (Nếu Chrome khả dụng) mở trang xem chart + 2 bảng + dropdown + export + drill khoa.

- [ ] **Step 4: Commit** (nếu có sửa)

```bash
git add -A && git commit -m "fix: kiem chung bao cao doanh thu theo khoa/phong"
```

---

## Hoàn tất

- [ ] **Chạy test:** `php vendor/bin/phpunit tests/Unit/RevenueDeptRoomServiceTest.php tests/Feature/RevenueDeptRoomControllerTest.php` → tất cả PASS.
- [ ] **readme.md** đã cập nhật ở Task 7.
- [ ] **Verify** bằng @superpowers:verification-before-completion trước khi tuyên bố hoàn thành.
