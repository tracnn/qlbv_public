# Báo cáo giao ban bệnh viện — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Màn hình KHTH "Báo cáo giao ban" — tự động tính số liệu theo khoa từ HIS Pro (Oracle), cho sửa tay theo phân quyền khoa, chốt báo cáo và xuất Excel theo biểu mẫu.

**Architecture:** Snapshot + chỉnh sửa + chốt. `GiaoBanMetricService` build SQL+bindings (thuần, test được) chạy trên `DB::connection('HISPro')`; snapshot lưu MySQL local (`giaoban_reports`/`giaoban_report_cells`); giá trị hiển thị `COALESCE(manual_value, auto_value)`; cấu hình động khoa/chỉ tiêu (`giaoban_dept_configs`); phân quyền nhập theo khoa (`giaoban_user_departments`, Laratrust).

**Tech Stack:** Laravel 5.5, PHP 7, Laratrust 5.0, yajra/laravel-oci8 (connection `HISPro`), maatwebsite/excel 3.1, AdminLTE 1.22 (`adminlte::page`), phpunit 6 (`vendor\bin\phpunit`).

**Spec:** `docs/superpowers/specs/2026-07-08-bao-cao-giao-ban-design.md`

**Kiến thức HIS đã xác minh trên `hispro_bvnn` (không cần xác minh lại):**
- Thời gian HIS = số `YYYYMMDDHHMISS` (ví dụ `20260708070000`).
- `HIS_DEPARTMENT_TRAN`: `TREATMENT_ID`, `DEPARTMENT_ID`, `PREVIOUS_ID` (tran đầu của đợt có `PREVIOUS_ID IS NULL`), `DEPARTMENT_IN_TIME`, `IS_DELETE`, `IS_ACTIVE`.
- `HIS_TREATMENT`: `TDL_TREATMENT_TYPE_ID` (3 = nội trú), `IN_TIME`, `OUT_TIME`, `LAST_DEPARTMENT_ID`, `TREATMENT_END_TYPE_ID`, `IS_DELETE`.
- `HIS_TREATMENT_END_TYPE` codes: `TV` tử vong, `CV` chuyển viện, `RV` ra viện, `HK` hẹn khám lại, `CC` cấp toa cho về, `XV` xin ra viện, `TR` trốn viện, `KH` khác.
- `HIS_SERVICE_TYPE`: 1=KH Khám, 2=XN, 3=HA CĐHA, 4=TT, 10=SA Siêu âm, 11=PT Phẫu thuật, 8=GI Giường.
- `HIS_TREATMENT_BED_ROOM`: `TREATMENT_ID`, `BED_ID`, `ADD_TIME`, `REMOVE_TIME`.
- Query census (BN hiện có tại khoa tại thời điểm T) đã chạy đúng — xem Task 3.

---

## File Structure

| File | Trách nhiệm |
|---|---|
| `database/migrations/2026_07_08_100000..100004_*.php` | 4 bảng mới + seed permissions Laratrust |
| `app/Models/GiaoBan/{GiaoBanReport,GiaoBanReportCell,GiaoBanDeptConfig,GiaoBanUserDepartment}.php` | Eloquent models (DB local) |
| `app/Services/GiaoBan/GiaoBanMetricService.php` | Build SQL+bindings query HIS (thuần) + chạy trên HISPro + tính metrics theo dept config |
| `app/Services/GiaoBan/GiaoBanReportService.php` | Snapshot/merge/balance/totals + đọc-ghi bảng local |
| `app/Services/GiaoBan/GiaoBanPermission.php` | Hàm thuần kiểm tra quyền sửa ô theo khoa |
| `app/Console/Commands/GiaoBanPreview.php` | `giaoban:preview` — in metrics ra console để đối chiếu số thật |
| `app/Http/Controllers/KHTH/GiaoBanController.php` | Màn chính: index/show/fetch-data/save-cell/save-note/finalize/unlock/export |
| `app/Http/Controllers/KHTH/GiaoBanConfigController.php` | Cấu hình khoa + gán user↔khoa |
| `app/Exports/GiaoBanExport.php` | Xuất Excel theo biểu mẫu |
| `resources/views/khth/giaoban-index.blade.php` | Màn chính |
| `resources/views/khth/giaoban-config.blade.php` | Màn cấu hình |
| `routes/web.php` | Nhóm route `khth/giao-ban*` |
| `config/adminlte.php` | Menu |
| `tests/Unit/GiaoBan/*.php` | Unit tests cho service thuần |

Quy ước phân quyền (Laratrust, middleware `checkrole` hiện có nhận role **hoặc** permission):
- Permission `giaoban` = được vào màn giao ban (cả 2 nhóm user đều có).
- Permission `giaoban-admin` = lấy số liệu, sửa mọi khoa, chốt/mở khóa, cấu hình.
- Role `giaoban_khoa` có perm `giaoban`; role `giaoban_admin` có perm `giaoban` + `giaoban-admin`; gắn thêm 2 perm này cho role `administrator` sẵn có.
- Route group dùng `checkrole:giaoban`; endpoint admin kiểm tra `auth()->user()->can('giaoban-admin')` trong controller.

---

### Task 1: Migrations — 4 bảng + seed permissions

**Files:**
- Create: `database/migrations/2026_07_08_100000_create_giaoban_dept_configs_table.php`
- Create: `database/migrations/2026_07_08_100001_create_giaoban_reports_table.php`
- Create: `database/migrations/2026_07_08_100002_create_giaoban_report_cells_table.php`
- Create: `database/migrations/2026_07_08_100003_create_giaoban_user_departments_table.php`
- Create: `database/migrations/2026_07_08_100004_seed_giaoban_permissions.php`

- [ ] **Step 1: Viết 4 migration bảng**

`2026_07_08_100000_create_giaoban_dept_configs_table.php`:

```php
<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateGiaobanDeptConfigsTable extends Migration
{
    public function up()
    {
        Schema::create('giaoban_dept_configs', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('his_department_id')->nullable(); // null = khối không gắn khoa HIS (VD: XN & CĐHA gộp)
            $table->string('display_name', 255);
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->text('metrics'); // JSON: danh sách chỉ tiêu, xem chú thích dưới
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('giaoban_dept_configs');
    }
}
```

Cấu trúc JSON cột `metrics` (mảng, mỗi phần tử 1 chỉ tiêu, thứ tự = thứ tự hiển thị):

```json
[
  {"code":"bn_cu","name":"BN cũ","type":"census_from"},
  {"code":"bn_vao","name":"BN vào","type":"movement_in"},
  {"code":"bn_chuyen_den","name":"BN chuyển đến","type":"movement_transfer_in"},
  {"code":"bn_ra_vien","name":"BN ra viện","type":"end_type","end_codes":["RV","HK","CC","XV","KH","TR"]},
  {"code":"bn_chuyen_vien","name":"BN chuyển viện","type":"end_type","end_codes":["CV"]},
  {"code":"bn_tu_vong","name":"BN tử vong","type":"end_type","end_codes":["TV"]},
  {"code":"bn_chuyen_khoa","name":"BN chuyển khoa","type":"movement_transfer_out"},
  {"code":"hien_co","name":"Hiện có","type":"census_to"},
  {"code":"giuong_yc","name":"Giường YC","type":"bed_count","bed_ids":[]},
  {"code":"pt_cap_cuu","name":"PT cấp cứu","type":"service_count","filter":{"service_type_ids":[11],"priority_min":2}},
  {"code":"pt_phien","name":"PT phiên","type":"service_count","filter":{"service_type_ids":[11],"priority_max":1}},
  {"code":"de_thuong","name":"Đẻ thường","type":"service_count","filter":{"service_ids":[]}},
  {"code":"chuyen_gia","name":"Chuyên gia BV tỉnh","type":"manual"}
]
```

`type` hợp lệ: `census_from`, `census_to`, `movement_in`, `movement_transfer_in`, `movement_transfer_out`, `end_type`, `bed_count`, `service_count`, `admission`, `manual`. Với `service_count`, `filter` hỗ trợ khóa: `service_type_ids`, `service_ids`, `execute_room_ids`, `execute_department_id_self` (bool — lọc theo chính khoa của config thay vì khoa mặc định), `priority_min`, `priority_max`.

`2026_07_08_100001_create_giaoban_reports_table.php`:

```php
<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateGiaobanReportsTable extends Migration
{
    public function up()
    {
        Schema::create('giaoban_reports', function (Blueprint $table) {
            $table->increments('id');
            $table->date('report_date')->unique();
            $table->dateTime('from_time');
            $table->dateTime('to_time');
            $table->string('status', 20)->default('draft'); // draft|final
            $table->text('general_note')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->unsignedInteger('finalized_by')->nullable();
            $table->dateTime('finalized_at')->nullable();
            $table->unsignedInteger('unlocked_by')->nullable();
            $table->dateTime('unlocked_at')->nullable();
            $table->dateTime('data_fetched_at')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('giaoban_reports');
    }
}
```

`2026_07_08_100002_create_giaoban_report_cells_table.php`:

```php
<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateGiaobanReportCellsTable extends Migration
{
    public function up()
    {
        Schema::create('giaoban_report_cells', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('report_id');
            $table->unsignedInteger('dept_config_id')->nullable(); // null = dòng ghi chú/tổng cấp báo cáo
            $table->string('metric_code', 50);
            $table->decimal('auto_value', 12, 2)->nullable();   // null = chỉ tiêu nhập tay thuần
            $table->decimal('manual_value', 12, 2)->nullable(); // null = chưa sửa tay
            $table->text('note')->nullable();                   // dùng cho metric_code = 'note' (ghi chú khoa)
            $table->unsignedInteger('updated_by')->nullable();
            $table->timestamps();

            $table->unique(['report_id', 'dept_config_id', 'metric_code'], 'giaoban_cells_unique');
            $table->index('report_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('giaoban_report_cells');
    }
}
```

Ghi chú theo khoa lưu bằng cell `metric_code = 'note'` (dùng cột `note`, không dùng value).

`2026_07_08_100003_create_giaoban_user_departments_table.php`:

```php
<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateGiaobanUserDepartmentsTable extends Migration
{
    public function up()
    {
        Schema::create('giaoban_user_departments', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('dept_config_id');
            $table->timestamps();
            $table->unique(['user_id', 'dept_config_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('giaoban_user_departments');
    }
}
```

- [ ] **Step 2: Viết migration seed permissions**

`2026_07_08_100004_seed_giaoban_permissions.php` (Laratrust 5: bảng `permissions`, `roles`, `permission_role`):

```php
<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Migrations\Migration;

class SeedGiaobanPermissions extends Migration
{
    public function up()
    {
        $now = date('Y-m-d H:i:s');

        $permIds = [];
        foreach ([
            'giaoban'       => 'Báo cáo giao ban - Xem/nhập theo khoa',
            'giaoban-admin' => 'Báo cáo giao ban - Quản trị (lấy số liệu, chốt, cấu hình)',
        ] as $name => $display) {
            $id = DB::table('permissions')->where('name', $name)->value('id');
            if (!$id) {
                $id = DB::table('permissions')->insertGetId([
                    'name' => $name, 'display_name' => $display,
                    'created_at' => $now, 'updated_at' => $now,
                ]);
            }
            $permIds[$name] = $id;
        }

        $roles = [
            'giaoban_khoa'  => ['display' => 'Giao ban - Nhập liệu khoa', 'perms' => ['giaoban']],
            'giaoban_admin' => ['display' => 'Giao ban - Quản trị',       'perms' => ['giaoban', 'giaoban-admin']],
        ];
        foreach ($roles as $name => $def) {
            $roleId = DB::table('roles')->where('name', $name)->value('id');
            if (!$roleId) {
                $roleId = DB::table('roles')->insertGetId([
                    'name' => $name, 'display_name' => $def['display'],
                    'created_at' => $now, 'updated_at' => $now,
                ]);
            }
            foreach ($def['perms'] as $p) {
                $exists = DB::table('permission_role')
                    ->where('permission_id', $permIds[$p])->where('role_id', $roleId)->exists();
                if (!$exists) {
                    DB::table('permission_role')->insert(['permission_id' => $permIds[$p], 'role_id' => $roleId]);
                }
            }
        }

        // administrator sẵn có được full quyền giao ban
        $adminRoleId = DB::table('roles')->where('name', 'administrator')->value('id');
        if ($adminRoleId) {
            foreach ($permIds as $pid) {
                $exists = DB::table('permission_role')
                    ->where('permission_id', $pid)->where('role_id', $adminRoleId)->exists();
                if (!$exists) {
                    DB::table('permission_role')->insert(['permission_id' => $pid, 'role_id' => $adminRoleId]);
                }
            }
        }
    }

    public function down()
    {
        $ids = DB::table('permissions')->whereIn('name', ['giaoban', 'giaoban-admin'])->pluck('id');
        DB::table('permission_role')->whereIn('permission_id', $ids)->delete();
        DB::table('permissions')->whereIn('id', $ids)->delete();
        DB::table('roles')->whereIn('name', ['giaoban_khoa', 'giaoban_admin'])->delete();
    }
}
```

- [ ] **Step 3: Chạy migrate**

Run: `php artisan migrate`
Expected: 5 dòng `Migrated: 2026_07_08_...` không lỗi.

- [ ] **Step 4: Commit**

```bash
git add database/migrations/2026_07_08_*.php
git commit -m "feat(giao-ban): migrations 4 bang + seed permissions Laratrust"
```

---

### Task 2: Models

**Files:**
- Create: `app/Models/GiaoBan/GiaoBanDeptConfig.php`
- Create: `app/Models/GiaoBan/GiaoBanReport.php`
- Create: `app/Models/GiaoBan/GiaoBanReportCell.php`
- Create: `app/Models/GiaoBan/GiaoBanUserDepartment.php`

- [ ] **Step 1: Viết 4 model**

`app/Models/GiaoBan/GiaoBanDeptConfig.php`:

```php
<?php

namespace App\Models\GiaoBan;

use Illuminate\Database\Eloquent\Model;

class GiaoBanDeptConfig extends Model
{
    protected $table = 'giaoban_dept_configs';
    protected $fillable = ['his_department_id', 'display_name', 'sort_order', 'is_active', 'metrics'];
    protected $casts = ['is_active' => 'boolean'];

    /** @return array các chỉ tiêu đã decode */
    public function metricList()
    {
        $m = json_decode($this->metrics, true);
        return is_array($m) ? $m : [];
    }
}
```

`app/Models/GiaoBan/GiaoBanReport.php`:

```php
<?php

namespace App\Models\GiaoBan;

use Illuminate\Database\Eloquent\Model;

class GiaoBanReport extends Model
{
    protected $table = 'giaoban_reports';
    protected $fillable = [
        'report_date', 'from_time', 'to_time', 'status', 'general_note',
        'created_by', 'finalized_by', 'finalized_at', 'unlocked_by', 'unlocked_at', 'data_fetched_at',
    ];

    public function cells()
    {
        return $this->hasMany(GiaoBanReportCell::class, 'report_id');
    }

    public function isFinal()
    {
        return $this->status === 'final';
    }
}
```

`app/Models/GiaoBan/GiaoBanReportCell.php`:

```php
<?php

namespace App\Models\GiaoBan;

use Illuminate\Database\Eloquent\Model;

class GiaoBanReportCell extends Model
{
    protected $table = 'giaoban_report_cells';
    protected $fillable = [
        'report_id', 'dept_config_id', 'metric_code',
        'auto_value', 'manual_value', 'note', 'updated_by',
    ];

    /** Giá trị hiển thị: ưu tiên sửa tay */
    public function displayValue()
    {
        return $this->manual_value !== null ? $this->manual_value : $this->auto_value;
    }
}
```

`app/Models/GiaoBan/GiaoBanUserDepartment.php`:

```php
<?php

namespace App\Models\GiaoBan;

use Illuminate\Database\Eloquent\Model;

class GiaoBanUserDepartment extends Model
{
    protected $table = 'giaoban_user_departments';
    protected $fillable = ['user_id', 'dept_config_id'];
}
```

- [ ] **Step 2: Smoke check autoload**

Run: `php artisan tinker --execute="var_dump(class_exists('App\Models\GiaoBan\GiaoBanReport'));"`
Expected: `bool(true)` (nếu tinker 1.0 không hỗ trợ `--execute`, chạy `php -r "require 'vendor/autoload.php'; var_dump(class_exists('App\Models\GiaoBan\GiaoBanReport'));"`)

- [ ] **Step 3: Commit**

```bash
git add app/Models/GiaoBan
git commit -m "feat(giao-ban): models bao cao giao ban"
```

---

### Task 3: GiaoBanMetricService — SQL builders (TDD)

**Files:**
- Create: `app/Services/GiaoBan/GiaoBanMetricService.php`
- Test: `tests/Unit/GiaoBan/GiaoBanMetricServiceTest.php`

Nguyên tắc (theo pattern `RevenueDeptRoomService`): các hàm build trả `[$sql, $binds]`, controller/service chạy `DB::connection('HISPro')->select($sql, $binds)` rồi `normalizeRows` (Oracle trả cột HOA).

- [ ] **Step 1: Viết failing tests**

`tests/Unit/GiaoBan/GiaoBanMetricServiceTest.php`:

```php
<?php

namespace Tests\Unit\GiaoBan;

use Tests\TestCase;
use App\Services\GiaoBan\GiaoBanMetricService;

class GiaoBanMetricServiceTest extends TestCase
{
    protected $svc;

    protected function setUp()
    {
        parent::setUp();
        $this->svc = new GiaoBanMetricService();
    }

    /** @test */
    public function to_his_time_converts_datetime_to_numeric_string()
    {
        $this->assertEquals('20260708070000', $this->svc->toHisTime('2026-07-08 07:00:00'));
    }

    /** @test */
    public function census_sql_has_distinct_bind_names_and_inpatient_filter()
    {
        list($sql, $binds) = $this->svc->buildCensusSql('2026-07-08 07:00:00');
        $this->assertContains('tdl_treatment_type_id = 3', $sql);
        $this->assertContains('NOT EXISTS', $sql);
        $this->assertEquals(
            ['ts1' => '20260708070000', 'ts2' => '20260708070000', 'ts3' => '20260708070000'],
            $binds
        );
    }

    /** @test */
    public function movement_sql_counts_in_and_transfer_in_by_previous_id()
    {
        list($sql, $binds) = $this->svc->buildMovementInSql('2026-07-07 07:00:00', '2026-07-08 07:00:00');
        $this->assertContains('previous_id IS NULL', $sql);
        $this->assertContains('previous_id IS NOT NULL', $sql);
        $this->assertEquals(['from_time' => '20260707070000', 'to_time' => '20260708070000'], $binds);
    }

    /** @test */
    public function end_type_sql_groups_by_last_department()
    {
        list($sql, $binds) = $this->svc->buildEndTypeSql('2026-07-07 07:00:00', '2026-07-08 07:00:00');
        $this->assertContains('last_department_id', $sql);
        $this->assertContains('treatment_end_type_code', $sql);
    }

    /** @test */
    public function service_count_sql_applies_filters()
    {
        list($sql, $binds) = $this->svc->buildServiceCountSql(
            '2026-07-07 07:00:00', '2026-07-08 07:00:00',
            ['service_type_ids' => [11], 'priority_min' => 2, 'request_department_id' => 5]
        );
        $this->assertContains('service_type_id IN (11)', $sql);
        $this->assertContains('priority >= :priority_min', $sql);
        $this->assertContains('request_department_id = :req_dept', $sql);
        $this->assertEquals(2, $binds['priority_min']);
        $this->assertEquals(5, $binds['req_dept']);
    }

    /** @test */
    public function normalize_rows_lowercases_oracle_columns()
    {
        $rows = $this->svc->normalizeRows([(object) ['DEPARTMENT_ID' => 1, 'SO_BN' => 9]]);
        $this->assertEquals(1, $rows[0]->department_id);
        $this->assertEquals(9, $rows[0]->so_bn);
    }
}
```

- [ ] **Step 2: Chạy test, xác nhận FAIL**

Run: `vendor\bin\phpunit tests\Unit\GiaoBan\GiaoBanMetricServiceTest.php`
Expected: FAIL — `Class 'App\Services\GiaoBan\GiaoBanMetricService' not found`

- [ ] **Step 3: Implement service**

`app/Services/GiaoBan/GiaoBanMetricService.php`:

```php
<?php

namespace App\Services\GiaoBan;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Build SQL + bindings tính chỉ tiêu giao ban từ HIS Pro (Oracle, connection HISPro).
 * Thời gian HIS là số YYYYMMDDHHMISS. Chỉ tính điều trị nội trú (tdl_treatment_type_id = 3),
 * trừ các chỉ tiêu service_count/admission.
 */
class GiaoBanMetricService
{
    const CONN = 'HISPro';

    /** 'Y-m-d H:i:s' -> 'YmdHis' */
    public function toHisTime($datetime)
    {
        return Carbon::createFromFormat('Y-m-d H:i:s', $datetime)->format('YmdHis');
    }

    /** Oracle trả cột HOA -> lowercase */
    public function normalizeRows($rawRows)
    {
        return array_map(function ($row) {
            return (object) array_change_key_case((array) $row, CASE_LOWER);
        }, $rawRows);
    }

    /**
     * BN nội trú đang ở từng khoa tại thời điểm $at.
     * Trả về: department_id, so_bn. (oci8 không cho dùng lại 1 bind name -> ts1/ts2/ts3)
     */
    public function buildCensusSql($at)
    {
        $ts = $this->toHisTime($at);
        $sql = "
            SELECT dt.department_id, COUNT(DISTINCT dt.treatment_id) AS so_bn
            FROM his_department_tran dt
            JOIN his_treatment t ON t.id = dt.treatment_id
            WHERE dt.is_delete = 0 AND dt.is_active = 1 AND t.is_delete = 0
              AND t.tdl_treatment_type_id = 3
              AND dt.department_in_time <= :ts1
              AND (t.out_time IS NULL OR t.out_time = 0 OR t.out_time > :ts2)
              AND NOT EXISTS (
                    SELECT 1 FROM his_department_tran nx
                    WHERE nx.previous_id = dt.id AND nx.is_delete = 0
                      AND nx.department_in_time <= :ts3)
            GROUP BY dt.department_id";
        return [$sql, ['ts1' => $ts, 'ts2' => $ts, 'ts3' => $ts]];
    }

    /**
     * BN vào khoa trong kỳ, tách vào thẳng (previous_id IS NULL) / chuyển đến.
     * Trả về: department_id, bn_vao, bn_chuyen_den.
     */
    public function buildMovementInSql($from, $to)
    {
        $sql = "
            SELECT dt.department_id,
                   SUM(CASE WHEN dt.previous_id IS NULL THEN 1 ELSE 0 END) AS bn_vao,
                   SUM(CASE WHEN dt.previous_id IS NOT NULL THEN 1 ELSE 0 END) AS bn_chuyen_den
            FROM his_department_tran dt
            JOIN his_treatment t ON t.id = dt.treatment_id
            WHERE dt.is_delete = 0 AND dt.is_active = 1 AND t.is_delete = 0
              AND t.tdl_treatment_type_id = 3
              AND dt.department_in_time BETWEEN :from_time AND :to_time
            GROUP BY dt.department_id";
        return [$sql, ['from_time' => $this->toHisTime($from), 'to_time' => $this->toHisTime($to)]];
    }

    /**
     * BN chuyển khoa (đi) trong kỳ: đếm theo khoa nguồn (tran trước của tran mới).
     * Trả về: department_id, bn_chuyen_khoa.
     */
    public function buildMovementOutSql($from, $to)
    {
        $sql = "
            SELECT p.department_id, COUNT(*) AS bn_chuyen_khoa
            FROM his_department_tran nx
            JOIN his_department_tran p ON p.id = nx.previous_id
            JOIN his_treatment t ON t.id = nx.treatment_id
            WHERE nx.is_delete = 0 AND p.is_delete = 0 AND t.is_delete = 0
              AND t.tdl_treatment_type_id = 3
              AND nx.department_id <> p.department_id
              AND nx.department_in_time BETWEEN :from_time AND :to_time
            GROUP BY p.department_id";
        return [$sql, ['from_time' => $this->toHisTime($from), 'to_time' => $this->toHisTime($to)]];
    }

    /**
     * Kết thúc điều trị trong kỳ theo khoa cuối, kèm mã loại kết thúc.
     * Trả về: department_id, end_code, so_bn — service gộp theo end_codes của từng metric.
     */
    public function buildEndTypeSql($from, $to)
    {
        $sql = "
            SELECT t.last_department_id AS department_id,
                   et.treatment_end_type_code AS end_code,
                   COUNT(*) AS so_bn
            FROM his_treatment t
            JOIN his_treatment_end_type et ON et.id = t.treatment_end_type_id
            WHERE t.is_delete = 0 AND t.tdl_treatment_type_id = 3
              AND t.out_time BETWEEN :from_time AND :to_time
            GROUP BY t.last_department_id, et.treatment_end_type_code";
        return [$sql, ['from_time' => $this->toHisTime($from), 'to_time' => $this->toHisTime($to)]];
    }

    /**
     * Đếm BN đang nằm các giường chỉ định (giường yêu cầu) tại thời điểm $at.
     * $bedIds: danh sách his_bed.id cấu hình trong metric.
     */
    public function buildBedCountSql($at, array $bedIds)
    {
        $ts = $this->toHisTime($at);
        $ids = implode(',', array_map('intval', $bedIds)) ?: '-1';
        $sql = "
            SELECT COUNT(DISTINCT btr.treatment_id) AS so_bn
            FROM his_treatment_bed_room btr
            WHERE btr.is_delete = 0
              AND btr.bed_id IN ($ids)
              AND btr.add_time <= :ts1
              AND (btr.remove_time IS NULL OR btr.remove_time = 0 OR btr.remove_time > :ts2)";
        return [$sql, ['ts1' => $ts, 'ts2' => $ts]];
    }

    /**
     * Đếm dịch vụ thực hiện trong kỳ theo bộ lọc cấu hình.
     * $filter: service_type_ids[], service_ids[], execute_room_ids[],
     *          request_department_id, execute_department_id, priority_min, priority_max.
     */
    public function buildServiceCountSql($from, $to, array $filter)
    {
        $f = $this->toHisTime($from);
        $t = $this->toHisTime($to);
        $conds = [
            'ss.is_delete = 0', 'sr.is_delete = 0', 'sr.is_active = 1',
            'ss.tdl_intruction_date BETWEEN :from_day AND :to_day', // cột có index
            'sr.intruction_time BETWEEN :from_time AND :to_time',
        ];
        $binds = [
            'from_day' => substr($f, 0, 8) . '000000',
            'to_day'   => substr($t, 0, 8) . '235959',
            'from_time' => $f, 'to_time' => $t,
        ];

        if (!empty($filter['service_type_ids'])) {
            $ids = implode(',', array_map('intval', $filter['service_type_ids']));
            $conds[] = "ss.tdl_service_type_id IN ($ids)";
        }
        if (!empty($filter['service_ids'])) {
            $ids = implode(',', array_map('intval', $filter['service_ids']));
            $conds[] = "ss.service_id IN ($ids)";
        }
        if (!empty($filter['execute_room_ids'])) {
            $ids = implode(',', array_map('intval', $filter['execute_room_ids']));
            $conds[] = "ss.tdl_execute_room_id IN ($ids)";
        }
        if (!empty($filter['request_department_id'])) {
            $conds[] = 'sr.request_department_id = :req_dept';
            $binds['req_dept'] = (int) $filter['request_department_id'];
        }
        if (!empty($filter['execute_department_id'])) {
            $conds[] = 'ss.tdl_execute_department_id = :exec_dept';
            $binds['exec_dept'] = (int) $filter['execute_department_id'];
        }
        if (isset($filter['priority_min'])) {
            $conds[] = 'sr.priority >= :priority_min';
            $binds['priority_min'] = (int) $filter['priority_min'];
        }
        if (isset($filter['priority_max'])) {
            $conds[] = '(sr.priority IS NULL OR sr.priority <= :priority_max)';
            $binds['priority_max'] = (int) $filter['priority_max'];
        }

        $where = implode(' AND ', $conds);
        $sql = "
            SELECT COUNT(*) AS so_luong
            FROM his_sere_serv ss
            JOIN his_service_req sr ON sr.id = ss.service_req_id
            WHERE $where";
        return [$sql, $binds];
    }

    /** Số BN nhập viện nội trú toàn viện trong kỳ (dùng cho dòng khoa Khám bệnh). */
    public function buildAdmissionCountSql($from, $to)
    {
        $sql = "
            SELECT COUNT(*) AS so_luong
            FROM his_treatment t
            WHERE t.is_delete = 0 AND t.tdl_treatment_type_id = 3
              AND t.in_time BETWEEN :from_time AND :to_time";
        return [$sql, ['from_time' => $this->toHisTime($from), 'to_time' => $this->toHisTime($to)]];
    }

    // ================= chạy trên HISPro và ráp thành ma trận =================

    protected function selectHis($sqlAndBinds)
    {
        list($sql, $binds) = $sqlAndBinds;
        return $this->normalizeRows(DB::connection(self::CONN)->select($sql, $binds));
    }

    /**
     * Tính toàn bộ auto_value cho danh sách dept config.
     * @param \Illuminate\Support\Collection|array $deptConfigs  các GiaoBanDeptConfig active
     * @return array map "dept_config_id|metric_code" => float|null
     */
    public function computeAll($deptConfigs, $from, $to)
    {
        // 1. Batch queries dùng chung
        $censusFrom = $this->pluckByDept($this->selectHis($this->buildCensusSql($from)), 'so_bn');
        $censusTo   = $this->pluckByDept($this->selectHis($this->buildCensusSql($to)), 'so_bn');
        $moveIn     = $this->selectHis($this->buildMovementInSql($from, $to));
        $bnVao      = $this->pluckByDept($moveIn, 'bn_vao');
        $bnDen      = $this->pluckByDept($moveIn, 'bn_chuyen_den');
        $moveOut    = $this->pluckByDept($this->selectHis($this->buildMovementOutSql($from, $to)), 'bn_chuyen_khoa');
        $endRows    = $this->selectHis($this->buildEndTypeSql($from, $to)); // department_id, end_code, so_bn

        $values = [];
        foreach ($deptConfigs as $cfg) {
            $hisDept = $cfg->his_department_id;
            foreach ($cfg->metricList() as $m) {
                $key = $cfg->id . '|' . $m['code'];
                switch ($m['type']) {
                    case 'census_from':  $values[$key] = (float) ($censusFrom[$hisDept] ?? 0); break;
                    case 'census_to':    $values[$key] = (float) ($censusTo[$hisDept] ?? 0); break;
                    case 'movement_in':  $values[$key] = (float) ($bnVao[$hisDept] ?? 0); break;
                    case 'movement_transfer_in':  $values[$key] = (float) ($bnDen[$hisDept] ?? 0); break;
                    case 'movement_transfer_out': $values[$key] = (float) ($moveOut[$hisDept] ?? 0); break;
                    case 'end_type':
                        $sum = 0;
                        foreach ($endRows as $r) {
                            if ((int) $r->department_id === (int) $hisDept
                                && in_array($r->end_code, $m['end_codes'], true)) {
                                $sum += (int) $r->so_bn;
                            }
                        }
                        $values[$key] = (float) $sum;
                        break;
                    case 'bed_count':
                        $rows = $this->selectHis($this->buildBedCountSql($to, isset($m['bed_ids']) ? $m['bed_ids'] : []));
                        $values[$key] = (float) ($rows[0]->so_bn ?? 0);
                        break;
                    case 'service_count':
                        $filter = isset($m['filter']) ? $m['filter'] : [];
                        if (!empty($filter['execute_department_id_self'])) {
                            $filter['execute_department_id'] = $hisDept;
                            unset($filter['execute_department_id_self']);
                        } elseif ($hisDept && empty($filter['execute_room_ids']) && empty($filter['execute_department_id'])) {
                            $filter['request_department_id'] = $hisDept;
                        }
                        $rows = $this->selectHis($this->buildServiceCountSql($from, $to, $filter));
                        $values[$key] = (float) ($rows[0]->so_luong ?? 0);
                        break;
                    case 'admission':
                        $rows = $this->selectHis($this->buildAdmissionCountSql($from, $to));
                        $values[$key] = (float) ($rows[0]->so_luong ?? 0);
                        break;
                    case 'manual':
                    default:
                        $values[$key] = null; // ô nhập tay thuần
                }
            }
        }
        return $values;
    }

    /** rows(department_id, <col>) -> map department_id => value */
    protected function pluckByDept($rows, $col)
    {
        $map = [];
        foreach ($rows as $r) {
            $map[(int) $r->department_id] = $r->{$col};
        }
        return $map;
    }
}
```

Lưu ý PHP 7.0: toán tử `??` dùng được; `?->` KHÔNG dùng.

- [ ] **Step 4: Chạy test, xác nhận PASS**

Run: `vendor\bin\phpunit tests\Unit\GiaoBan\GiaoBanMetricServiceTest.php`
Expected: PASS (6 tests)

- [ ] **Step 5: Commit**

```bash
git add app/Services/GiaoBan/GiaoBanMetricService.php tests/Unit/GiaoBan/GiaoBanMetricServiceTest.php
git commit -m "feat(giao-ban): metric service build SQL chi tieu tu HIS (TDD)"
```

---

### Task 4: Command `giaoban:preview` + đối chiếu số thật trên HIS

**Files:**
- Create: `app/Console/Commands/GiaoBanPreview.php`
- Modify: `app/Console/Kernel.php` (đăng ký command vào mảng `$commands`)

Mục đích: chạy thử toàn bộ metric trên HIS thật, in bảng console để đối chiếu với số báo cáo tay trước khi làm UI. Đây là bước hiệu chỉnh SQL quan trọng nhất.

- [ ] **Step 1: Viết command**

`app/Console/Commands/GiaoBanPreview.php`:

```php
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Services\GiaoBan\GiaoBanMetricService;

class GiaoBanPreview extends Command
{
    protected $signature = 'giaoban:preview {--from=} {--to=}';
    protected $description = 'In so lieu giao ban tu HIS de doi chieu (khong ghi DB)';

    public function handle()
    {
        $svc = new GiaoBanMetricService();
        $to = $this->option('to') ?: date('Y-m-d 07:00:00');
        $from = $this->option('from') ?: date('Y-m-d 07:00:00', strtotime('-1 day', strtotime($to)));
        $this->info("Ky so lieu: $from -> $to");

        $censusFrom = $svc->normalizeRows(DB::connection('HISPro')->select(...$this->args($svc->buildCensusSql($from))));
        $censusTo = $svc->normalizeRows(DB::connection('HISPro')->select(...$this->args($svc->buildCensusSql($to))));
        $moveIn = $svc->normalizeRows(DB::connection('HISPro')->select(...$this->args($svc->buildMovementInSql($from, $to))));
        $moveOut = $svc->normalizeRows(DB::connection('HISPro')->select(...$this->args($svc->buildMovementOutSql($from, $to))));
        $ends = $svc->normalizeRows(DB::connection('HISPro')->select(...$this->args($svc->buildEndTypeSql($from, $to))));

        $depts = collect($svc->normalizeRows(DB::connection('HISPro')
            ->select('SELECT id, department_name FROM his_department WHERE is_delete = 0')))
            ->pluck('department_name', 'id');

        $rows = [];
        $ids = collect($censusFrom)->pluck('department_id')
            ->merge(collect($censusTo)->pluck('department_id'))
            ->merge(collect($moveIn)->pluck('department_id'))
            ->unique()->values();
        foreach ($ids as $id) {
            $find = function ($rows, $col) use ($id) {
                foreach ($rows as $r) if ((int) $r->department_id === (int) $id) return (int) $r->{$col};
                return 0;
            };
            $raVien = $chuyenVien = $tuVong = 0;
            foreach ($ends as $r) {
                if ((int) $r->department_id !== (int) $id) continue;
                if ($r->end_code === 'CV') $chuyenVien += (int) $r->so_bn;
                elseif ($r->end_code === 'TV') $tuVong += (int) $r->so_bn;
                else $raVien += (int) $r->so_bn;
            }
            $cu = $find($censusFrom, 'so_bn'); $vao = $find($moveIn, 'bn_vao');
            $den = $find($moveIn, 'bn_chuyen_den'); $di = $find($moveOut, 'bn_chuyen_khoa');
            $hienCo = $find($censusTo, 'so_bn');
            $rows[] = [
                'khoa' => isset($depts[$id]) ? $depts[$id] : $id,
                'cu' => $cu, 'vao' => $vao, 'den' => $den,
                'ra' => $raVien, 'cv' => $chuyenVien, 'tv' => $tuVong, 'di' => $di,
                'hien_co' => $hienCo,
                'can_doi' => ($cu + $vao + $den - $raVien - $chuyenVien - $tuVong - $di) - $hienCo,
            ];
        }
        $this->table(['Khoa', 'Cũ', 'Vào', 'Đến', 'Ra', 'CV', 'TV', 'Đi', 'Hiện có', 'Lệch'], $rows);
        return 0;
    }

    protected function args($sqlAndBinds)
    {
        return [$sqlAndBinds[0], $sqlAndBinds[1]];
    }
}
```

Đăng ký trong `app/Console/Kernel.php`: thêm `\App\Console\Commands\GiaoBanPreview::class,` vào mảng `$commands` (theo đúng chỗ các command hiện có như `kiemtraylenh:scan`).

- [ ] **Step 2: Chạy preview cho hôm nay**

Run: `php artisan giaoban:preview`
Expected: bảng số liệu các khoa, cột `Lệch` đa số = 0 hoặc nhỏ.

- [ ] **Step 3: Đối chiếu và hiệu chỉnh**

So sánh với số liệu bệnh viện báo cáo tay (biểu mẫu Google Sheets ngày tương ứng nếu có). Kiểm tra thêm trên sqlcl các điểm chưa chắc:
- Cột `PRIORITY` của `HIS_SERVICE_REQ`: `SELECT DISTINCT priority FROM his_service_req WHERE ROWNUM <= 100` — xác nhận giá trị nào là cấp cứu (thường 0/1 = thường, >=2 = cấp cứu). Nếu khác, sửa mô tả filter `priority_min`/`priority_max` trong config mẫu (không cần sửa code).
- Cột lệch cân đối lớn: soi từng treatment bằng `his_department_tran` để tìm case đặc thù (BN chuyển đến rồi đi trong kỳ, tran bị is_active=0…) và sửa builder tương ứng.

Nếu sửa SQL builder: cập nhật test tương ứng trong `GiaoBanMetricServiceTest`, chạy lại `vendor\bin\phpunit tests\Unit\GiaoBan\GiaoBanMetricServiceTest.php` → PASS.

- [ ] **Step 4: Commit**

```bash
git add app/Console/Commands/GiaoBanPreview.php app/Console/Kernel.php
git commit -m "feat(giao-ban): command giaoban:preview doi chieu so lieu HIS"
```

---

### Task 5: GiaoBanReportService — snapshot/merge/balance/totals (TDD)

**Files:**
- Create: `app/Services/GiaoBan/GiaoBanReportService.php`
- Test: `tests/Unit/GiaoBan/GiaoBanReportServiceTest.php`

- [ ] **Step 1: Viết failing tests (phần thuần)**

`tests/Unit/GiaoBan/GiaoBanReportServiceTest.php`:

```php
<?php

namespace Tests\Unit\GiaoBan;

use Tests\TestCase;
use App\Services\GiaoBan\GiaoBanReportService;

class GiaoBanReportServiceTest extends TestCase
{
    /** @test */
    public function merge_preserves_manual_values_and_updates_auto()
    {
        // existing: map "dept|metric" => ['auto' =>, 'manual' =>]
        $existing = [
            '1|bn_cu' => ['auto' => 10.0, 'manual' => 12.0],
            '1|bn_vao' => ['auto' => 5.0, 'manual' => null],
        ];
        $fresh = ['1|bn_cu' => 11.0, '1|bn_vao' => 6.0, '1|hien_co' => 9.0];

        $merged = GiaoBanReportService::mergeAutoValues($existing, $fresh);

        $this->assertEquals(['auto' => 11.0, 'manual' => 12.0], $merged['1|bn_cu']); // manual giữ nguyên
        $this->assertEquals(['auto' => 6.0, 'manual' => null], $merged['1|bn_vao']);
        $this->assertEquals(['auto' => 9.0, 'manual' => null], $merged['1|hien_co']); // ô mới
    }

    /** @test */
    public function balance_check_flags_mismatched_departments_using_display_values()
    {
        $cells = [
            // khoa 1: 10 + 5 + 1 - 3 - 1 - 0 - 2 = 10 == hien_co -> cân
            '1|bn_cu' => ['auto' => 10.0, 'manual' => null], '1|bn_vao' => ['auto' => 5.0, 'manual' => null],
            '1|bn_chuyen_den' => ['auto' => 1.0, 'manual' => null], '1|bn_ra_vien' => ['auto' => 3.0, 'manual' => null],
            '1|bn_chuyen_vien' => ['auto' => 1.0, 'manual' => null], '1|bn_tu_vong' => ['auto' => 0.0, 'manual' => null],
            '1|bn_chuyen_khoa' => ['auto' => 2.0, 'manual' => null], '1|hien_co' => ['auto' => 10.0, 'manual' => null],
            // khoa 2: manual sửa hien_co thành 8 trong khi công thức = 7 -> lệch 1
            '2|bn_cu' => ['auto' => 7.0, 'manual' => null], '2|bn_vao' => ['auto' => 0.0, 'manual' => null],
            '2|bn_chuyen_den' => ['auto' => 0.0, 'manual' => null], '2|bn_ra_vien' => ['auto' => 0.0, 'manual' => null],
            '2|bn_chuyen_vien' => ['auto' => 0.0, 'manual' => null], '2|bn_tu_vong' => ['auto' => 0.0, 'manual' => null],
            '2|bn_chuyen_khoa' => ['auto' => 0.0, 'manual' => null], '2|hien_co' => ['auto' => 7.0, 'manual' => 8.0],
        ];

        $warnings = GiaoBanReportService::checkBalance($cells, [1, 2]);

        $this->assertArrayNotHasKey(1, $warnings);
        $this->assertEquals(1.0, $warnings[2]); // chênh lệch
    }

    /** @test */
    public function totals_sum_display_values_across_departments()
    {
        $cells = [
            '1|hien_co' => ['auto' => 10.0, 'manual' => null],
            '2|hien_co' => ['auto' => 7.0, 'manual' => 9.0], // ưu tiên manual
        ];
        $this->assertEquals(19.0, GiaoBanReportService::sumMetric($cells, 'hien_co', [1, 2]));
    }
}
```

- [ ] **Step 2: Chạy test, xác nhận FAIL**

Run: `vendor\bin\phpunit tests\Unit\GiaoBan\GiaoBanReportServiceTest.php`
Expected: FAIL — class not found

- [ ] **Step 3: Implement service**

`app/Services/GiaoBan/GiaoBanReportService.php`:

```php
<?php

namespace App\Services\GiaoBan;

use App\Models\GiaoBan\GiaoBanDeptConfig;
use App\Models\GiaoBan\GiaoBanReport;
use App\Models\GiaoBan\GiaoBanReportCell;

class GiaoBanReportService
{
    const BALANCE_PLUS = ['bn_cu', 'bn_vao', 'bn_chuyen_den'];
    const BALANCE_MINUS = ['bn_ra_vien', 'bn_chuyen_vien', 'bn_tu_vong', 'bn_chuyen_khoa'];
    const BALANCE_TARGET = 'hien_co';

    protected $metricService;

    public function __construct(GiaoBanMetricService $metricService)
    {
        $this->metricService = $metricService;
    }

    // ===== Phần thuần (unit test) =====

    /**
     * Trộn auto_value mới vào cells hiện có, giữ nguyên manual.
     * @param array $existing map "dept|metric" => ['auto' => ?, 'manual' => ?]
     * @param array $fresh    map "dept|metric" => float|null
     */
    public static function mergeAutoValues(array $existing, array $fresh)
    {
        $out = $existing;
        foreach ($fresh as $key => $auto) {
            if (isset($out[$key])) {
                $out[$key]['auto'] = $auto;
            } else {
                $out[$key] = ['auto' => $auto, 'manual' => null];
            }
        }
        return $out;
    }

    protected static function display(array $cells, $deptId, $metric)
    {
        $key = $deptId . '|' . $metric;
        if (!isset($cells[$key])) return 0.0;
        $c = $cells[$key];
        return $c['manual'] !== null ? (float) $c['manual'] : (float) ($c['auto'] !== null ? $c['auto'] : 0);
    }

    /**
     * Kiểm tra cân đối: cũ + vào + đến − ra − cv − tv − đi == hiện có.
     * @return array map dept_config_id => chênh lệch (chỉ các khoa lệch)
     */
    public static function checkBalance(array $cells, array $deptConfigIds)
    {
        $warnings = [];
        foreach ($deptConfigIds as $id) {
            if (!isset($cells[$id . '|' . self::BALANCE_TARGET])) continue;
            $expect = 0.0;
            foreach (self::BALANCE_PLUS as $m) $expect += self::display($cells, $id, $m);
            foreach (self::BALANCE_MINUS as $m) $expect -= self::display($cells, $id, $m);
            $actual = self::display($cells, $id, self::BALANCE_TARGET);
            if (abs($expect - $actual) > 0.001) {
                $warnings[$id] = round(abs($expect - $actual), 2);
            }
        }
        return $warnings;
    }

    /** Tổng một metric trên các khoa (ưu tiên manual). */
    public static function sumMetric(array $cells, $metric, array $deptConfigIds)
    {
        $sum = 0.0;
        foreach ($deptConfigIds as $id) $sum += self::display($cells, $id, $metric);
        return $sum;
    }

    // ===== Phần persistence =====

    /** Lấy (tạo nếu chưa có) report của ngày. */
    public function getOrCreateReport($date, $from, $to, $userId)
    {
        $report = GiaoBanReport::where('report_date', $date)->first();
        if (!$report) {
            $report = GiaoBanReport::create([
                'report_date' => $date, 'from_time' => $from, 'to_time' => $to,
                'status' => 'draft', 'created_by' => $userId,
            ]);
        }
        return $report;
    }

    /**
     * Lấy số liệu từ HIS và upsert vào giaoban_report_cells (giữ manual_value).
     * Chỉ gọi khi report ở trạng thái draft.
     */
    public function fetchAndStore(GiaoBanReport $report, $from, $to, $userId)
    {
        $configs = GiaoBanDeptConfig::where('is_active', true)->orderBy('sort_order')->get();
        $fresh = $this->metricService->computeAll($configs, $from, $to);

        foreach ($fresh as $key => $auto) {
            list($deptConfigId, $metricCode) = explode('|', $key, 2);
            $cell = GiaoBanReportCell::firstOrNew([
                'report_id' => $report->id,
                'dept_config_id' => (int) $deptConfigId,
                'metric_code' => $metricCode,
            ]);
            $cell->auto_value = $auto;
            $cell->updated_by = $userId;
            $cell->save();
        }

        $report->update(['from_time' => $from, 'to_time' => $to, 'data_fetched_at' => date('Y-m-d H:i:s')]);
        return $report;
    }

    /** Cells của report dạng map "dept|metric" => ['auto','manual'] cho các hàm thuần. */
    public function cellMap(GiaoBanReport $report)
    {
        $map = [];
        foreach ($report->cells as $c) {
            if ($c->metric_code === 'note') continue;
            $map[$c->dept_config_id . '|' . $c->metric_code] = [
                'auto' => $c->auto_value !== null ? (float) $c->auto_value : null,
                'manual' => $c->manual_value !== null ? (float) $c->manual_value : null,
            ];
        }
        return $map;
    }
}
```

- [ ] **Step 4: Chạy test, xác nhận PASS**

Run: `vendor\bin\phpunit tests\Unit\GiaoBan\GiaoBanReportServiceTest.php`
Expected: PASS (3 tests)

- [ ] **Step 5: Commit**

```bash
git add app/Services/GiaoBan/GiaoBanReportService.php tests/Unit/GiaoBan/GiaoBanReportServiceTest.php
git commit -m "feat(giao-ban): report service merge/balance/totals + persistence (TDD)"
```

---

### Task 6: GiaoBanPermission — kiểm tra quyền sửa theo khoa (TDD)

**Files:**
- Create: `app/Services/GiaoBan/GiaoBanPermission.php`
- Test: `tests/Unit/GiaoBan/GiaoBanPermissionTest.php`

- [ ] **Step 1: Viết failing tests**

`tests/Unit/GiaoBan/GiaoBanPermissionTest.php`:

```php
<?php

namespace Tests\Unit\GiaoBan;

use Tests\TestCase;
use App\Services\GiaoBan\GiaoBanPermission;

class GiaoBanPermissionTest extends TestCase
{
    /** @test */
    public function admin_can_edit_any_dept_even_when_not_assigned()
    {
        $this->assertTrue(GiaoBanPermission::canEditDept(true, [], 5));
    }

    /** @test */
    public function khoa_user_can_edit_only_assigned_depts()
    {
        $this->assertTrue(GiaoBanPermission::canEditDept(false, [3, 5], 5));
        $this->assertFalse(GiaoBanPermission::canEditDept(false, [3, 5], 7));
    }

    /** @test */
    public function nobody_edits_when_report_is_final()
    {
        $this->assertFalse(GiaoBanPermission::canEditReport('final', true));
        $this->assertTrue(GiaoBanPermission::canEditReport('draft', true));
        $this->assertTrue(GiaoBanPermission::canEditReport('draft', false));
    }
}
```

- [ ] **Step 2: Chạy test, xác nhận FAIL**

Run: `vendor\bin\phpunit tests\Unit\GiaoBan\GiaoBanPermissionTest.php`
Expected: FAIL — class not found

- [ ] **Step 3: Implement**

`app/Services/GiaoBan/GiaoBanPermission.php`:

```php
<?php

namespace App\Services\GiaoBan;

class GiaoBanPermission
{
    /**
     * @param bool  $isAdmin          user->can('giaoban-admin')
     * @param array $assignedDeptIds  dept_config_id được gán trong giaoban_user_departments
     * @param int   $deptConfigId     khoa đang sửa
     */
    public static function canEditDept($isAdmin, array $assignedDeptIds, $deptConfigId)
    {
        if ($isAdmin) return true;
        return in_array((int) $deptConfigId, array_map('intval', $assignedDeptIds), true);
    }

    /** Báo cáo final thì không ai sửa (admin phải mở khóa trước). */
    public static function canEditReport($status, $isAdmin)
    {
        return $status !== 'final';
    }
}
```

- [ ] **Step 4: Chạy test, xác nhận PASS**

Run: `vendor\bin\phpunit tests\Unit\GiaoBan\GiaoBanPermissionTest.php`
Expected: PASS (3 tests)

- [ ] **Step 5: Commit**

```bash
git add app/Services/GiaoBan/GiaoBanPermission.php tests/Unit/GiaoBan/GiaoBanPermissionTest.php
git commit -m "feat(giao-ban): permission thuan kiem tra quyen sua theo khoa (TDD)"
```

---

### Task 7: GiaoBanController + routes

**Files:**
- Create: `app/Http/Controllers/KHTH/GiaoBanController.php`
- Modify: `routes/web.php` (thêm group mới NGAY SAU group `khth/` checkrole:administrator hiện có, ~dòng 630, cùng cấp trong khối auth)

- [ ] **Step 1: Viết controller**

`app/Http/Controllers/KHTH/GiaoBanController.php`:

```php
<?php

namespace App\Http\Controllers\KHTH;

use App\Http\Controllers\Controller;
use App\Models\GiaoBan\GiaoBanDeptConfig;
use App\Models\GiaoBan\GiaoBanReport;
use App\Models\GiaoBan\GiaoBanReportCell;
use App\Models\GiaoBan\GiaoBanUserDepartment;
use App\Services\GiaoBan\GiaoBanPermission;
use App\Services\GiaoBan\GiaoBanReportService;
use Illuminate\Http\Request;

class GiaoBanController extends Controller
{
    protected $service;

    public function __construct(GiaoBanReportService $service)
    {
        $this->service = $service;
    }

    protected function isAdmin()
    {
        return auth()->user()->can('giaoban-admin');
    }

    protected function assignedDeptIds()
    {
        return GiaoBanUserDepartment::where('user_id', auth()->id())->pluck('dept_config_id')->all();
    }

    public function index()
    {
        return view('khth.giaoban-index', [
            'isAdmin' => $this->isAdmin(),
            'assignedDeptIds' => $this->assignedDeptIds(),
        ]);
    }

    /** JSON toàn bộ report của 1 ngày: configs + cells + warnings. */
    public function show(Request $request)
    {
        $date = $request->input('date', date('Y-m-d'));
        $report = GiaoBanReport::with('cells')->where('report_date', $date)->first();
        $configs = GiaoBanDeptConfig::where('is_active', true)->orderBy('sort_order')->get()
            ->map(function ($c) {
                return [
                    'id' => $c->id, 'display_name' => $c->display_name,
                    'his_department_id' => $c->his_department_id, 'metrics' => $c->metricList(),
                ];
            });

        $cells = []; $warnings = [];
        if ($report) {
            foreach ($report->cells as $c) {
                $cells[] = [
                    'dept_config_id' => $c->dept_config_id, 'metric_code' => $c->metric_code,
                    'auto_value' => $c->auto_value, 'manual_value' => $c->manual_value, 'note' => $c->note,
                ];
            }
            $warnings = GiaoBanReportService::checkBalance(
                $this->service->cellMap($report),
                $configs->pluck('id')->all()
            );
        }

        return response()->json([
            'report' => $report, 'configs' => $configs, 'cells' => $cells,
            'balance_warnings' => $warnings,
            'is_admin' => $this->isAdmin(), 'assigned_dept_ids' => $this->assignedDeptIds(),
        ]);
    }

    /** Lấy/Lấy lại số liệu từ HIS (admin). */
    public function fetchData(Request $request)
    {
        if (!$this->isAdmin()) abort(403);
        $this->validate($request, [
            'date' => 'required|date_format:Y-m-d',
            'from_time' => 'required|date_format:Y-m-d H:i:s',
            'to_time' => 'required|date_format:Y-m-d H:i:s',
        ]);

        $report = $this->service->getOrCreateReport(
            $request->input('date'), $request->input('from_time'), $request->input('to_time'), auth()->id()
        );
        if ($report->isFinal()) {
            return response()->json(['message' => 'Báo cáo đã chốt, cần mở khóa trước.'], 422);
        }
        $this->service->fetchAndStore($report, $request->input('from_time'), $request->input('to_time'), auth()->id());
        return response()->json(['ok' => true]);
    }

    /** Sửa 1 ô (manual_value) hoặc ghi chú khoa. */
    public function saveCell(Request $request)
    {
        $this->validate($request, [
            'report_id' => 'required|integer',
            'dept_config_id' => 'required|integer',
            'metric_code' => 'required|string|max:50',
            'manual_value' => 'nullable|numeric',
            'note' => 'nullable|string',
        ]);
        $report = GiaoBanReport::findOrFail($request->input('report_id'));
        if (!GiaoBanPermission::canEditReport($report->status, $this->isAdmin())) {
            return response()->json(['message' => 'Báo cáo đã chốt.'], 422);
        }
        if (!GiaoBanPermission::canEditDept($this->isAdmin(), $this->assignedDeptIds(), $request->input('dept_config_id'))) {
            abort(403, 'Bạn không có quyền nhập số liệu khoa này.');
        }

        $cell = GiaoBanReportCell::firstOrNew([
            'report_id' => $report->id,
            'dept_config_id' => (int) $request->input('dept_config_id'),
            'metric_code' => $request->input('metric_code'),
        ]);
        if ($request->input('metric_code') === 'note') {
            $cell->note = $request->input('note');
        } else {
            // manual_value = null nghĩa là trả về số tự động
            $cell->manual_value = $request->filled('manual_value') ? $request->input('manual_value') : null;
        }
        $cell->updated_by = auth()->id();
        $cell->save();
        return response()->json(['ok' => true]);
    }

    /** Ghi chú chung (admin hoặc user bất kỳ có quyền giao ban? -> chỉ admin). */
    public function saveGeneralNote(Request $request)
    {
        if (!$this->isAdmin()) abort(403);
        $report = GiaoBanReport::findOrFail($request->input('report_id'));
        if ($report->isFinal()) return response()->json(['message' => 'Báo cáo đã chốt.'], 422);
        $report->update(['general_note' => $request->input('general_note')]);
        return response()->json(['ok' => true]);
    }

    public function finalize(Request $request)
    {
        if (!$this->isAdmin()) abort(403);
        $report = GiaoBanReport::findOrFail($request->input('report_id'));
        $report->update([
            'status' => 'final', 'finalized_by' => auth()->id(), 'finalized_at' => date('Y-m-d H:i:s'),
        ]);
        return response()->json(['ok' => true]);
    }

    public function unlock(Request $request)
    {
        if (!$this->isAdmin()) abort(403);
        $report = GiaoBanReport::findOrFail($request->input('report_id'));
        $report->update([
            'status' => 'draft', 'unlocked_by' => auth()->id(), 'unlocked_at' => date('Y-m-d H:i:s'),
        ]);
        return response()->json(['ok' => true]);
    }
}
```

- [ ] **Step 2: Thêm routes**

Trong `routes/web.php`, sau group `khth/` + `checkrole:administrator` hiện có (kết thúc ~dòng 630), cùng bên trong khối middleware auth bao ngoài, thêm:

```php
    // Báo cáo giao ban — quyền riêng (giaoban), không yêu cầu administrator
    Route::group(['prefix' => 'khth/', 'middleware' => ['checkrole:giaoban']], function () {
        Route::get('giao-ban', 'KHTH\GiaoBanController@index')->name('khth.giao-ban');
        Route::get('giao-ban/show', 'KHTH\GiaoBanController@show')->name('khth.giao-ban-show');
        Route::post('giao-ban/fetch-data', 'KHTH\GiaoBanController@fetchData')->name('khth.giao-ban-fetch');
        Route::post('giao-ban/save-cell', 'KHTH\GiaoBanController@saveCell')->name('khth.giao-ban-save-cell');
        Route::post('giao-ban/save-general-note', 'KHTH\GiaoBanController@saveGeneralNote')->name('khth.giao-ban-save-note');
        Route::post('giao-ban/finalize', 'KHTH\GiaoBanController@finalize')->name('khth.giao-ban-finalize');
        Route::post('giao-ban/unlock', 'KHTH\GiaoBanController@unlock')->name('khth.giao-ban-unlock');
        Route::get('giao-ban/export', 'KHTH\GiaoBanController@export')->name('khth.giao-ban-export'); // Task 10
    });
```

(Method `export` viết ở Task 10 — thêm stub tạm để route không gãy:)

```php
    public function export(Request $request)
    {
        abort(501, 'Chưa triển khai');
    }
```

- [ ] **Step 3: Kiểm tra route đăng ký đúng**

Run: `php artisan route:list | findstr giao-ban`
Expected: 8 route `khth/giao-ban*`.

- [ ] **Step 4: Chạy toàn bộ unit test giao ban**

Run: `vendor\bin\phpunit tests\Unit\GiaoBan`
Expected: PASS toàn bộ.

- [ ] **Step 5: Commit**

```bash
git add app/Http/Controllers/KHTH/GiaoBanController.php routes/web.php
git commit -m "feat(giao-ban): controller man chinh + routes khth/giao-ban"
```

---

### Task 8: GiaoBanConfigController — cấu hình khoa + gán user

**Files:**
- Create: `app/Http/Controllers/KHTH/GiaoBanConfigController.php`
- Modify: `routes/web.php` (thêm vào group `checkrole:giaoban` đã tạo ở Task 7 — các endpoint tự kiểm tra admin)

- [ ] **Step 1: Viết controller**

`app/Http/Controllers/KHTH/GiaoBanConfigController.php`:

```php
<?php

namespace App\Http\Controllers\KHTH;

use App\Http\Controllers\Controller;
use App\Models\GiaoBan\GiaoBanDeptConfig;
use App\Models\GiaoBan\GiaoBanUserDepartment;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GiaoBanConfigController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (!auth()->user()->can('giaoban-admin')) abort(403);
            return $next($request);
        });
    }

    public function index()
    {
        $hisDepartments = collect(DB::connection('HISPro')->select(
            'SELECT id, department_code, department_name FROM his_department WHERE is_delete = 0 ORDER BY department_name'
        ))->map(function ($r) {
            return (object) array_change_key_case((array) $r, CASE_LOWER);
        });
        return view('khth.giaoban-config', [
            'hisDepartments' => $hisDepartments,
            'users' => User::orderBy('name')->get(['id', 'name', 'email']),
        ]);
    }

    public function fetch()
    {
        $configs = GiaoBanDeptConfig::orderBy('sort_order')->get();
        $assignments = GiaoBanUserDepartment::all();
        return response()->json(['configs' => $configs, 'assignments' => $assignments]);
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'display_name' => 'required|string|max:255',
            'his_department_id' => 'nullable|integer',
            'sort_order' => 'nullable|integer',
            'metrics' => 'required|string', // JSON string
        ]);
        json_decode($request->input('metrics'), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return response()->json(['message' => 'metrics không phải JSON hợp lệ'], 422);
        }
        $cfg = GiaoBanDeptConfig::create($request->only(['display_name', 'his_department_id', 'sort_order', 'metrics'])
            + ['is_active' => true]);
        return response()->json(['ok' => true, 'id' => $cfg->id]);
    }

    public function update(Request $request, $id)
    {
        $cfg = GiaoBanDeptConfig::findOrFail($id);
        if ($request->filled('metrics')) {
            json_decode($request->input('metrics'), true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return response()->json(['message' => 'metrics không phải JSON hợp lệ'], 422);
            }
        }
        $cfg->update($request->only(['display_name', 'his_department_id', 'sort_order', 'metrics', 'is_active']));
        return response()->json(['ok' => true]);
    }

    /** Gán lại toàn bộ khoa cho 1 user. */
    public function assignUser(Request $request)
    {
        $this->validate($request, ['user_id' => 'required|integer', 'dept_config_ids' => 'nullable|array']);
        $userId = (int) $request->input('user_id');
        GiaoBanUserDepartment::where('user_id', $userId)->delete();
        foreach ((array) $request->input('dept_config_ids', []) as $deptId) {
            GiaoBanUserDepartment::create(['user_id' => $userId, 'dept_config_id' => (int) $deptId]);
        }
        return response()->json(['ok' => true]);
    }
}
```

- [ ] **Step 2: Thêm routes vào group `checkrole:giaoban` (Task 7)**

```php
        Route::get('giao-ban/cau-hinh', 'KHTH\GiaoBanConfigController@index')->name('khth.giao-ban-config');
        Route::get('giao-ban/cau-hinh/fetch', 'KHTH\GiaoBanConfigController@fetch')->name('khth.giao-ban-config-fetch');
        Route::post('giao-ban/cau-hinh', 'KHTH\GiaoBanConfigController@store')->name('khth.giao-ban-config-store');
        Route::post('giao-ban/cau-hinh/{id}', 'KHTH\GiaoBanConfigController@update')->name('khth.giao-ban-config-update');
        Route::post('giao-ban/cau-hinh-assign', 'KHTH\GiaoBanConfigController@assignUser')->name('khth.giao-ban-config-assign');
```

LƯU Ý: đặt các route `giao-ban/cau-hinh*` TRƯỚC route `giao-ban/{...}` nào có tham số (hiện không có route tham số nên thứ tự trong group không gây xung đột; giữ `giao-ban/export` và `giao-ban/show` nguyên trạng).

- [ ] **Step 3: Kiểm tra route**

Run: `php artisan route:list | findstr cau-hinh`
Expected: 5 route.

- [ ] **Step 4: Commit**

```bash
git add app/Http/Controllers/KHTH/GiaoBanConfigController.php routes/web.php
git commit -m "feat(giao-ban): controller cau hinh khoa + gan tai khoan-khoa"
```

---

### Task 9: Views — màn chính + màn cấu hình

**Files:**
- Create: `resources/views/khth/giaoban-index.blade.php`
- Create: `resources/views/khth/giaoban-config.blade.php`

- [ ] **Step 1: Viết view màn chính**

`resources/views/khth/giaoban-index.blade.php` (theo pattern `order-check.blade.php`, AdminLTE box + jQuery có sẵn trong layout):

```blade
@extends('adminlte::page')
@section('title', 'Báo cáo giao ban')
@section('content_header')<h1>Báo cáo giao ban <small id="report-status"></small></h1>@stop

@section('content')
<div class="box box-primary">
  <div class="box-body">
    <div class="row">
      <div class="col-md-2"><label>Ngày giao ban</label>
        <input type="date" id="report_date" class="form-control" value="{{ date('Y-m-d') }}"></div>
      <div class="col-md-2"><label>Từ thời điểm</label>
        <input type="datetime-local" id="from_time" class="form-control"></div>
      <div class="col-md-2"><label>Đến thời điểm</label>
        <input type="datetime-local" id="to_time" class="form-control"></div>
      <div class="col-md-6" style="padding-top:24px">
        <button id="btn-view" class="btn btn-default"><i class="fa fa-eye"></i> Xem</button>
        @if($isAdmin)
        <button id="btn-fetch" class="btn btn-primary"><i class="fa fa-refresh"></i> Lấy số liệu</button>
        <button id="btn-finalize" class="btn btn-danger"><i class="fa fa-lock"></i> Chốt báo cáo</button>
        <button id="btn-unlock" class="btn btn-warning" style="display:none"><i class="fa fa-unlock"></i> Mở khóa</button>
        @endif
        <a id="btn-export" class="btn btn-success"><i class="fa fa-file-excel-o"></i> Xuất Excel</a>
      </div>
    </div>
  </div>
</div>

<div id="report-body"></div>

<div class="box box-default">
  <div class="box-header with-border"><b>Ghi chú chung</b></div>
  <div class="box-body">
    <textarea id="general_note" class="form-control" rows="3" @if(!$isAdmin) readonly @endif></textarea>
    @if($isAdmin)<button id="btn-save-note" class="btn btn-sm btn-primary" style="margin-top:5px">Lưu ghi chú</button>@endif
  </div>
</div>
@stop

@section('js')
<script>
var IS_ADMIN = {{ $isAdmin ? 'true' : 'false' }};
var ASSIGNED = @json($assignedDeptIds);
var CURRENT = null; // dữ liệu show() gần nhất

function defaultTimes() {
  var d = $('#report_date').val();
  var prev = new Date(new Date(d).getTime() - 86400000).toISOString().slice(0, 10);
  $('#from_time').val(prev + 'T07:00');
  $('#to_time').val(d + 'T07:00');
}

function fmt(dtLocal) { return dtLocal.replace('T', ' ') + ':00'; }

function canEditDept(deptId) {
  if (CURRENT && CURRENT.report && CURRENT.report.status === 'final') return false;
  return IS_ADMIN || ASSIGNED.indexOf(deptId) !== -1;
}

function loadReport() {
  $.get('{{ route('khth.giao-ban-show') }}', { date: $('#report_date').val() }, function (res) {
    CURRENT = res;
    render(res);
  });
}

function cellOf(res, deptId, code) {
  for (var i = 0; i < res.cells.length; i++) {
    var c = res.cells[i];
    if (c.dept_config_id === deptId && c.metric_code === code) return c;
  }
  return null;
}

function render(res) {
  var $body = $('#report-body').empty();
  if (!res.report) {
    $('#report-status').text('(chưa có dữ liệu — bấm Lấy số liệu)');
    return;
  }
  var r = res.report;
  $('#report-status').text(r.status === 'final' ? '(ĐÃ CHỐT)' : '(nháp, số liệu ' + r.from_time + ' → ' + r.to_time + ')');
  $('#btn-unlock').toggle(r.status === 'final');
  $('#btn-finalize').toggle(r.status !== 'final');
  $('#general_note').val(r.general_note || '');

  res.configs.forEach(function (cfg) {
    var editable = canEditDept(cfg.id);
    var warn = res.balance_warnings && res.balance_warnings[cfg.id]
      ? ' <i class="fa fa-warning text-yellow" title="Lệch cân đối: ' + res.balance_warnings[cfg.id] + '"></i>' : '';
    var html = '<div class="box box-solid"><div class="box-header with-border"><b>' +
      cfg.display_name + '</b>' + warn + '</div><div class="box-body"><div class="row">';
    cfg.metrics.forEach(function (m) {
      var c = cellOf(res, cfg.id, m.code) || {};
      var val = c.manual_value !== null && c.manual_value !== undefined ? c.manual_value : c.auto_value;
      var edited = c.manual_value !== null && c.manual_value !== undefined;
      html += '<div class="col-md-2" style="margin-bottom:8px"><label style="font-weight:normal">' + m.name + '</label>' +
        '<div class="input-group">' +
        '<input type="number" step="any" class="form-control cell-input' + (edited ? ' bg-warning' : '') + '"' +
        ' data-dept="' + cfg.id + '" data-metric="' + m.code + '"' +
        (edited ? ' title="Số HIS: ' + (c.auto_value === null ? '—' : c.auto_value) + '"' : '') +
        ' value="' + (val === null || val === undefined ? '' : Number(val)) + '"' + (editable ? '' : ' readonly') + '>' +
        (edited && editable
          ? '<span class="input-group-btn"><button class="btn btn-default btn-reset-cell" title="Trả về số tự động" data-dept="' +
            cfg.id + '" data-metric="' + m.code + '"><i class="fa fa-undo"></i></button></span>'
          : '') +
        '</div></div>';
    });
    var noteCell = cellOf(res, cfg.id, 'note') || {};
    html += '</div><label style="font-weight:normal">Ghi chú khoa</label>' +
      '<textarea class="form-control dept-note" rows="2" data-dept="' + cfg.id + '"' +
      (editable ? '' : ' readonly') + '>' + (noteCell.note || '') + '</textarea>';
    html += '</div></div>';
    $body.append(html);
  });
}

function saveCell(deptId, metric, payload, done) {
  $.post('{{ route('khth.giao-ban-save-cell') }}', $.extend({
    _token: '{{ csrf_token() }}', report_id: CURRENT.report.id,
    dept_config_id: deptId, metric_code: metric
  }, payload)).done(done).fail(function (xhr) {
    alert(xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Lỗi lưu dữ liệu');
    loadReport();
  });
}

$(function () {
  defaultTimes();
  $('#report_date').on('change', function () { defaultTimes(); loadReport(); });
  $('#btn-view').on('click', loadReport);

  $('#btn-fetch').on('click', function () {
    $.post('{{ route('khth.giao-ban-fetch') }}', {
      _token: '{{ csrf_token() }}', date: $('#report_date').val(),
      from_time: fmt($('#from_time').val()), to_time: fmt($('#to_time').val())
    }).done(loadReport).fail(function (xhr) {
      alert(xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Lỗi lấy số liệu từ HIS');
    });
  });

  $('#report-body').on('change', '.cell-input', function () {
    var $i = $(this);
    saveCell($i.data('dept'), $i.data('metric'), { manual_value: $i.val() }, loadReport);
  });
  $('#report-body').on('click', '.btn-reset-cell', function () {
    saveCell($(this).data('dept'), $(this).data('metric'), { manual_value: '' }, loadReport);
  });
  $('#report-body').on('change', '.dept-note', function () {
    saveCell($(this).data('dept'), 'note', { note: $(this).val() }, function () {});
  });

  $('#btn-save-note').on('click', function () {
    $.post('{{ route('khth.giao-ban-save-note') }}', {
      _token: '{{ csrf_token() }}', report_id: CURRENT.report.id, general_note: $('#general_note').val()
    }).done(function () { alert('Đã lưu'); });
  });
  $('#btn-finalize').on('click', function () {
    if (!confirm('Chốt báo cáo? Sau khi chốt sẽ không sửa được.')) return;
    $.post('{{ route('khth.giao-ban-finalize') }}', { _token: '{{ csrf_token() }}', report_id: CURRENT.report.id }).done(loadReport);
  });
  $('#btn-unlock').on('click', function () {
    $.post('{{ route('khth.giao-ban-unlock') }}', { _token: '{{ csrf_token() }}', report_id: CURRENT.report.id }).done(loadReport);
  });
  $('#btn-export').on('click', function () {
    window.location = '{{ route('khth.giao-ban-export') }}?date=' + $('#report_date').val();
  });

  loadReport();
});
</script>
@stop
```

- [ ] **Step 2: Viết view cấu hình**

`resources/views/khth/giaoban-config.blade.php`:

```blade
@extends('adminlte::page')
@section('title', 'Cấu hình báo cáo giao ban')
@section('content_header')<h1>Cấu hình báo cáo giao ban</h1>@stop

@section('content')
<div class="row">
  <div class="col-md-7">
    <div class="box box-primary">
      <div class="box-header with-border"><b>Khoa hiển thị trên báo cáo</b></div>
      <div class="box-body">
        <table class="table table-bordered" id="tbl-configs">
          <thead><tr><th>Thứ tự</th><th>Tên hiển thị</th><th>Khoa HIS</th><th>Chỉ tiêu (JSON)</th><th>Hoạt động</th><th></th></tr></thead>
          <tbody></tbody>
        </table>
        <button id="btn-add" class="btn btn-primary"><i class="fa fa-plus"></i> Thêm khoa</button>
      </div>
    </div>
  </div>
  <div class="col-md-5">
    <div class="box box-warning">
      <div class="box-header with-border"><b>Gán tài khoản ↔ khoa</b></div>
      <div class="box-body">
        <label>Tài khoản</label>
        <select id="assign-user" class="form-control">
          @foreach($users as $u)<option value="{{ $u->id }}">{{ $u->name }} ({{ $u->email }})</option>@endforeach
        </select>
        <label style="margin-top:10px">Khoa được nhập</label>
        <select id="assign-depts" class="form-control" multiple size="12"></select>
        <button id="btn-assign" class="btn btn-warning" style="margin-top:10px">Lưu gán khoa</button>
      </div>
    </div>
  </div>
</div>

{{-- template metrics mặc định cho khoa lâm sàng --}}
<script type="application/json" id="default-metrics">[
  {"code":"bn_cu","name":"BN cũ","type":"census_from"},
  {"code":"bn_vao","name":"BN vào","type":"movement_in"},
  {"code":"bn_chuyen_den","name":"BN chuyển đến","type":"movement_transfer_in"},
  {"code":"bn_ra_vien","name":"BN ra viện","type":"end_type","end_codes":["RV","HK","CC","XV","KH","TR"]},
  {"code":"bn_chuyen_vien","name":"BN chuyển viện","type":"end_type","end_codes":["CV"]},
  {"code":"bn_tu_vong","name":"BN tử vong","type":"end_type","end_codes":["TV"]},
  {"code":"bn_chuyen_khoa","name":"BN chuyển khoa","type":"movement_transfer_out"},
  {"code":"hien_co","name":"Hiện có","type":"census_to"}
]</script>
@stop

@section('js')
<script>
var HIS_DEPTS = @json($hisDepartments);
var STATE = { configs: [], assignments: [] };

function deptOptions(selected) {
  var html = '<option value="">— Không gắn khoa HIS —</option>';
  HIS_DEPTS.forEach(function (d) {
    html += '<option value="' + d.id + '"' + (d.id == selected ? ' selected' : '') + '>' + d.department_name + '</option>';
  });
  return html;
}

function renderConfigs() {
  var $tb = $('#tbl-configs tbody').empty();
  STATE.configs.forEach(function (c) {
    $tb.append('<tr data-id="' + c.id + '">' +
      '<td><input class="form-control f-sort" type="number" value="' + c.sort_order + '" style="width:70px"></td>' +
      '<td><input class="form-control f-name" value="' + c.display_name + '"></td>' +
      '<td><select class="form-control f-dept">' + deptOptions(c.his_department_id) + '</select></td>' +
      '<td><textarea class="form-control f-metrics" rows="2">' + c.metrics + '</textarea></td>' +
      '<td><input type="checkbox" class="f-active"' + (c.is_active ? ' checked' : '') + '></td>' +
      '<td><button class="btn btn-sm btn-primary btn-save-cfg">Lưu</button></td></tr>');
  });
  var $sel = $('#assign-depts').empty();
  STATE.configs.forEach(function (c) {
    if (c.is_active) $sel.append('<option value="' + c.id + '">' + c.display_name + '</option>');
  });
}

function loadAll() {
  $.get('{{ route('khth.giao-ban-config-fetch') }}', function (res) {
    STATE = res; renderConfigs(); syncAssign();
  });
}

function syncAssign() {
  var uid = parseInt($('#assign-user').val(), 10);
  var mine = STATE.assignments.filter(function (a) { return a.user_id === uid; })
    .map(function (a) { return String(a.dept_config_id); });
  $('#assign-depts').val(mine);
}

$(function () {
  loadAll();
  $('#assign-user').on('change', syncAssign);

  $('#btn-add').on('click', function () {
    var name = prompt('Tên hiển thị khoa mới:');
    if (!name) return;
    $.post('{{ route('khth.giao-ban-config-store') }}', {
      _token: '{{ csrf_token() }}', display_name: name, sort_order: STATE.configs.length + 1,
      metrics: $('#default-metrics').text().trim()
    }).done(loadAll);
  });

  $('#tbl-configs').on('click', '.btn-save-cfg', function () {
    var $tr = $(this).closest('tr');
    $.post('{{ url('khth/giao-ban/cau-hinh') }}/' + $tr.data('id'), {
      _token: '{{ csrf_token() }}',
      sort_order: $tr.find('.f-sort').val(), display_name: $tr.find('.f-name').val(),
      his_department_id: $tr.find('.f-dept').val() || null,
      metrics: $tr.find('.f-metrics').val(),
      is_active: $tr.find('.f-active').is(':checked') ? 1 : 0
    }).done(loadAll).fail(function (xhr) {
      alert(xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Lỗi lưu');
    });
  });

  $('#btn-assign').on('click', function () {
    $.post('{{ route('khth.giao-ban-config-assign') }}', {
      _token: '{{ csrf_token() }}', user_id: $('#assign-user').val(),
      dept_config_ids: $('#assign-depts').val() || []
    }).done(function () { alert('Đã lưu'); loadAll(); });
  });
});
</script>
@stop
```

- [ ] **Step 3: Smoke test bằng trình duyệt/dev server**

Đăng nhập tài khoản administrator, mở `/khth/giao-ban/cau-hinh`: thêm 2-3 khoa (VD Khoa Nội TH CS1, Khoa Ngoại TH), lưu. Mở `/khth/giao-ban`, bấm **Lấy số liệu** → thấy các khối khoa với số liệu; sửa 1 ô → ô đổi nền vàng, F5 vẫn giữ; bấm nút ↺ → về số tự động.

- [ ] **Step 4: Commit**

```bash
git add resources/views/khth/giaoban-index.blade.php resources/views/khth/giaoban-config.blade.php
git commit -m "feat(giao-ban): man hinh bao cao giao ban + man cau hinh"
```

---

### Task 10: Xuất Excel

**Files:**
- Create: `app/Exports/GiaoBanExport.php`
- Modify: `app/Http/Controllers/KHTH/GiaoBanController.php` (thay stub `export`)

- [ ] **Step 1: Viết export class**

`app/Exports/GiaoBanExport.php` (maatwebsite 3.1, FromArray + WithStyles + WithColumnWidths):

```php
<?php

namespace App\Exports;

use App\Models\GiaoBan\GiaoBanReport;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class GiaoBanExport implements FromArray, WithStyles, WithColumnWidths
{
    protected $report;
    protected $configs;
    protected $boldRows = [];   // các dòng cần in đậm (tiêu đề khoa)
    protected $italicRows = []; // ghi chú

    /** @param \Illuminate\Support\Collection $configs GiaoBanDeptConfig active theo sort_order */
    public function __construct(GiaoBanReport $report, $configs)
    {
        $this->report = $report;
        $this->configs = $configs;
    }

    public function array(): array
    {
        $cells = [];
        foreach ($this->report->cells as $c) {
            $cells[$c->dept_config_id . '|' . $c->metric_code] = $c;
        }
        $display = function ($deptId, $code) use ($cells) {
            $key = $deptId . '|' . $code;
            if (!isset($cells[$key])) return null;
            return $cells[$key]->displayValue();
        };

        $rows = [];
        $title = 'BÁO CÁO GIAO BAN NGÀY ' . date('d/m/Y', strtotime($this->report->report_date));
        if ($this->report->status !== 'final') $title .= ' (BẢN NHÁP)';
        $rows[] = [$title];
        $rows[] = ['Số liệu từ ' . $this->report->from_time . ' đến ' . $this->report->to_time];
        $rows[] = [];

        $i = 1;
        foreach ($this->configs as $cfg) {
            $this->boldRows[] = count($rows) + 1; // 1-based
            $rows[] = [$i . '. ' . mb_strtoupper($cfg->display_name)];
            $line = []; $vals = [];
            foreach ($cfg->metricList() as $m) {
                $line[] = $m['name'];
                $v = $display($cfg->id, $m['code']);
                $vals[] = $v === null ? '' : (float) $v;
            }
            $rows[] = $line;
            $rows[] = $vals;
            $noteKey = $cfg->id . '|note';
            if (isset($cells[$noteKey]) && trim((string) $cells[$noteKey]->note) !== '') {
                $this->italicRows[] = count($rows) + 1;
                $rows[] = ['* ' . $cells[$noteKey]->note];
            }
            $rows[] = [];
            $i++;
        }

        if (trim((string) $this->report->general_note) !== '') {
            $this->boldRows[] = count($rows) + 1;
            $rows[] = ['GHI CHÚ CHUNG'];
            $this->italicRows[] = count($rows) + 1;
            $rows[] = [$this->report->general_note];
        }
        return $rows;
    }

    public function styles(Worksheet $sheet)
    {
        $styles = [1 => ['font' => ['bold' => true, 'size' => 14]]];
        foreach ($this->boldRows as $r) $styles[$r] = ['font' => ['bold' => true]];
        foreach ($this->italicRows as $r) $styles[$r] = ['font' => ['italic' => true]];
        return $styles;
    }

    public function columnWidths(): array
    {
        return ['A' => 18, 'B' => 14, 'C' => 14, 'D' => 14, 'E' => 14, 'F' => 14,
                'G' => 14, 'H' => 14, 'I' => 14, 'J' => 14, 'K' => 14, 'L' => 14];
    }
}
```

LƯU Ý: `array()` phải chạy TRƯỚC `styles()` để `$boldRows` có dữ liệu — maatwebsite gọi đúng thứ tự đó (FromArray trước Styles). Không đổi thứ tự interface.

- [ ] **Step 2: Thay stub `export` trong `GiaoBanController`**

```php
    public function export(Request $request)
    {
        $date = $request->input('date', date('Y-m-d'));
        $report = \App\Models\GiaoBan\GiaoBanReport::with('cells')->where('report_date', $date)->firstOrFail();
        $configs = \App\Models\GiaoBan\GiaoBanDeptConfig::where('is_active', true)->orderBy('sort_order')->get();
        $filename = 'bao-cao-giao-ban-' . $date . ($report->status === 'final' ? '' : '-nhap') . '.xlsx';
        return \Maatwebsite\Excel\Facades\Excel::download(new \App\Exports\GiaoBanExport($report, $configs), $filename);
    }
```

- [ ] **Step 3: Smoke test export**

Trên màn `/khth/giao-ban` (đã có dữ liệu từ Task 9 Step 3), bấm **Xuất Excel** → file tải về mở được, có tiêu đề, các khối khoa, dòng số khớp màn hình, chữ "(BẢN NHÁP)" khi chưa chốt.

- [ ] **Step 4: Commit**

```bash
git add app/Exports/GiaoBanExport.php app/Http/Controllers/KHTH/GiaoBanController.php
git commit -m "feat(giao-ban): xuat Excel bao cao giao ban theo bieu mau"
```

---

### Task 11: Menu + readme

**Files:**
- Modify: `config/adminlte.php` (khu vực menu KHTH, gần entry `khth.order-check-index` ~dòng 217)
- Modify: `readme.md` (thêm entry đầu file)

- [ ] **Step 1: Thêm menu**

Trong `config/adminlte.php`, cùng section menu KHTH (đặt ngay sau item "Kiểm tra sai sót y lệnh"), thêm 2 item theo đúng format các item xung quanh:

```php
                        [
                            'text'      => 'Báo cáo giao ban',
                            'route'     => 'khth.giao-ban',
                            'active'    => ['khth/giao-ban*'],
                            'icon'      => 'calendar-check-o',
                        ],
                        [
                            'text'      => 'Cấu hình giao ban',
                            'route'     => 'khth.giao-ban-config',
                            'active'    => ['khth/giao-ban/cau-hinh*'],
                            'icon'      => 'cogs',
                        ],
```

(Nếu các item xung quanh có khóa `can`/`permission`, thêm tương ứng `'can' => 'giaoban'` và `'can' => 'giaoban-admin'` theo đúng convention file này.)

- [ ] **Step 2: Cập nhật readme**

Thêm đầu `readme.md`:

```markdown
# 08/07/2026 (cập nhật 2)

- Bổ sung Báo cáo giao ban bệnh viện (KHTH): tự động tính số liệu theo khoa từ HIS (BN cũ/vào/chuyển/ra/hiện có, PTTT, giường YC, XN/CĐHA...) theo khoảng giờ tùy chọn; cho sửa tay từng ô theo phân quyền khoa (giaoban_khoa/giaoban_admin); chốt báo cáo + xuất Excel theo biểu mẫu; màn cấu hình động khoa/chỉ tiêu và gán tài khoản↔khoa.
```

- [ ] **Step 3: Commit**

```bash
git add config/adminlte.php readme.md
git commit -m "feat(giao-ban): menu KHTH + cap nhat readme"
```

---

### Task 12: Kiểm thử tổng thể & nghiệm thu

- [ ] **Step 1: Chạy toàn bộ test**

Run: `vendor\bin\phpunit tests\Unit\GiaoBan`
Expected: PASS 12 tests. Chạy thêm `vendor\bin\phpunit` toàn bộ để chắc không vỡ test cũ.

- [ ] **Step 2: Đối chiếu số liệu lần cuối**

Run: `php artisan giaoban:preview --from="2026-07-07 07:00:00" --to="2026-07-08 07:00:00"`
So khớp với màn hình `/khth/giao-ban` sau khi Lấy số liệu cùng khoảng giờ — số phải trùng nhau tuyệt đối.

- [ ] **Step 3: Kiểm thử phân quyền thủ công**

1. Tạo user test, gán role `giaoban_khoa`, gán 1 khoa ở màn cấu hình.
2. Đăng nhập user test: chỉ khoa được gán editable; sửa ô khoa khác qua devtools (POST save-cell với dept khác) → nhận 403.
3. Chốt báo cáo bằng admin → user test không sửa được nữa (422); admin mở khóa → sửa lại được.

- [ ] **Step 4: Verification-before-completion**

Dùng skill `superpowers:verification-before-completion` trước khi báo cáo hoàn thành: chạy lại lệnh test + smoke các route, dán output thật.

- [ ] **Step 5: Commit cuối (nếu còn thay đổi) và tổng kết**

```bash
git add -A
git commit -m "test(giao-ban): hoan thien kiem thu bao cao giao ban"
```
