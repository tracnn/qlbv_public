# Nâng cấp cấu hình báo cáo giao ban — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Cho phép 1 khoa báo cáo gộp nhiều khoa HIS; phân loại khối (điều trị/khám/CLS) với cách thống kê riêng; gán tài khoản bằng CustomUser HIS (acs_user); thêm chỉ tiêu cận lâm sàng.

**Architecture:** Giữ nguyên các SQL builder trả kết quả theo từng khoa (GROUP BY department_id) + ADD builder mới; `computeAll` cộng dồn qua danh sách khoa của config và rẽ nhánh theo `block_type`; loại trừ chuyển nội bộ bằng 1 builder đếm riêng rồi trừ. Config controller thêm endpoint tìm acs_user. Không đụng màn nhập/present/export (đọc cells generic).

**Tech Stack:** Laravel 5.5 / PHP 7.4, Oracle qua `DB::connection('HISPro')` và `DB::connection('ACS_RS')`, phpunit 6 (`vendor\bin\phpunit`), Laratrust.

**Spec:** `docs/superpowers/specs/2026-07-08-giao-ban-config-nang-cap-design.md`

**Sự thật đã xác minh trên `hispro_bvnn` (KHÔNG cần xác minh lại):**
- `giaoban_dept_configs` hiện có cột `his_department_id` (int, nullable), `metrics` (JSON text), `block_type` CHƯA có.
- `HIS_DEPARTMENT`: `IS_EXAM`, `IS_CLINICAL`, `REALITY_PATIENT_COUNT`.
- App auth = `App\CustomUser` (bảng `acs_user` trên `ACS_RS`, cột `ID, LOGINNAME, USERNAME, EMAIL, IS_ACTIVE`). `auth()->id()` = `acs_user.id`.
- Lượt khám: `his_service_req` với `service_req_type_id = config('__tech.service_req_type_kham')` (= 1), `is_main_exam=1`, `is_delete=0`, `execute_department_id` = khoa khám, `intruction_time` trong kỳ. (Đã test: K01 id 27 → 834 lượt/ngày.)
- Nội trú `tdl_treatment_type_id=3`; thời gian HIS = số `YYYYMMDDHHMISS`.
- `buildServiceCountSql` đã hỗ trợ `service_type_ids`, `service_ids`, `execute_room_ids`, `request_department_id`, `execute_department_id`, `priority_min/max` (đơn).

**Quyết định thiết kế chốt trong plan:**
- Khối `kham`: mọi chỉ tiêu dựa trên lượt khám chính (`exam_visit`) + lọc tùy chọn trên `his_treatment` join qua `sr.treatment_id`:
  - `Lượt khám` = không lọc. `Vào viện` = `treatment_type_ids:[3]` (nội trú). `Cấp toa/ngoại trú` = `treatment_type_ids:[2]`. `Khám yêu cầu` = `patient_type_ids:[82]`. `Khám BHYT` = `patient_type_ids:[1]`.
  - Đã đối chiếu K01 (id 27) ngày mẫu: tổng 834; type3=34, type2=17; BHYT=773, Yêu cầu=5.
  - `Chuyên gia BV tỉnh` không có nguồn HIS → `manual`.
  - `his_patient_type`: 1=BHYT, 42=Viện Phí, 43=KSK, 62=Hợp đồng, 82=Yêu cầu. `his_treatment_type`: 1=Khám, 2=Ngoại trú, 3=Nội trú, 4=Ban ngày.
- Loại trừ chuyển nội bộ: chỉ áp khi config có >1 khoa HIS và có metric transfer.
- `computeAll` là orchestration chạm DB → KHÔNG unit-test trực tiếp; unit-test các builder (string-assert) + các hàm gộp thuần (`sumOverDepts`, `sumEndType`). Xác minh `computeAll` bằng preview trên HIS thật (Task 7).

---

## File Structure

| File | Trách nhiệm |
|---|---|
| `database/migrations/2026_07_08_110000_add_block_type_dept_ids_to_giaoban_dept_configs.php` | Thêm `block_type`, `his_department_ids` + backfill |
| `app/Models/GiaoBan/GiaoBanDeptConfig.php` | fillable + `hisDepartmentIds()` |
| `app/Services/GiaoBan/GiaoBanMetricService.php` | builder mới (internal-transfer, exam) + mở rộng service_count đa khoa + hàm gộp thuần + `computeAll` rẽ nhánh |
| `app/Http/Controllers/KHTH/GiaoBanConfigController.php` | `searchUsers`, `index` bỏ User MySQL, validate block_type/his_department_ids |
| `routes/web.php` | route `search-users` |
| `resources/views/khth/giaoban-config.blade.php` | loại khối, multi-select khoa, autocomplete user, template 3 khối |
| `tests/Unit/GiaoBan/*` | test builder + hàm gộp + model |

---

### Task 1: Migration — block_type + his_department_ids

**Files:**
- Create: `database/migrations/2026_07_08_110000_add_block_type_dept_ids_to_giaoban_dept_configs.php`

- [ ] **Step 1: Viết migration**

```php
<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AddBlockTypeDeptIdsToGiaobanDeptConfigs extends Migration
{
    public function up()
    {
        Schema::table('giaoban_dept_configs', function (Blueprint $table) {
            $table->string('block_type', 20)->default('dieu_tri')->after('display_name'); // dieu_tri|kham|can_lam_sang
            $table->text('his_department_ids')->nullable()->after('his_department_id');    // JSON mang int
        });

        // Backfill: his_department_ids = [his_department_id] neu co
        foreach (DB::table('giaoban_dept_configs')->whereNotNull('his_department_id')->get() as $r) {
            DB::table('giaoban_dept_configs')->where('id', $r->id)
                ->update(['his_department_ids' => json_encode([(int) $r->his_department_id])]);
        }
    }

    public function down()
    {
        Schema::table('giaoban_dept_configs', function (Blueprint $table) {
            $table->dropColumn(['block_type', 'his_department_ids']);
        });
    }
}
```

- [ ] **Step 2: Chạy migrate**

Run: `php artisan migrate`
Expected: `Migrated: 2026_07_08_110000_add_block_type_dept_ids_to_giaoban_dept_configs`.

- [ ] **Step 3: Commit**

```bash
git add database/migrations/2026_07_08_110000_add_block_type_dept_ids_to_giaoban_dept_configs.php
git commit -m "feat(giao-ban): migration block_type + his_department_ids"
```

---

### Task 2: Model GiaoBanDeptConfig — hisDepartmentIds() (TDD)

**Files:**
- Modify: `app/Models/GiaoBan/GiaoBanDeptConfig.php`
- Test: `tests/Unit/GiaoBan/GiaoBanDeptConfigTest.php`

- [ ] **Step 1: Viết failing test**

`tests/Unit/GiaoBan/GiaoBanDeptConfigTest.php`:
```php
<?php

namespace Tests\Unit\GiaoBan;

use Tests\TestCase;
use App\Models\GiaoBan\GiaoBanDeptConfig;

class GiaoBanDeptConfigTest extends TestCase
{
    /** @test */
    public function his_department_ids_parses_json_array_of_ints()
    {
        $c = new GiaoBanDeptConfig(['his_department_ids' => '[73, 54]']);
        $this->assertSame([73, 54], $c->hisDepartmentIds());
    }

    /** @test */
    public function his_department_ids_falls_back_to_legacy_single_column()
    {
        $c = new GiaoBanDeptConfig(['his_department_id' => 27]);
        $this->assertSame([27], $c->hisDepartmentIds());
    }

    /** @test */
    public function his_department_ids_empty_when_nothing_set()
    {
        $c = new GiaoBanDeptConfig([]);
        $this->assertSame([], $c->hisDepartmentIds());
    }
}
```

- [ ] **Step 2: Chạy test, xác nhận FAIL**

Run: `vendor\bin\phpunit tests\Unit\GiaoBan\GiaoBanDeptConfigTest.php`
Expected: FAIL — method `hisDepartmentIds` không tồn tại.

- [ ] **Step 3: Sửa model**

Trong `app/Models/GiaoBan/GiaoBanDeptConfig.php`, đổi `$fillable` và thêm method. File đầy đủ mới:

```php
<?php

namespace App\Models\GiaoBan;

use Illuminate\Database\Eloquent\Model;

class GiaoBanDeptConfig extends Model
{
    protected $table = 'giaoban_dept_configs';
    protected $fillable = ['his_department_id', 'his_department_ids', 'block_type', 'display_name', 'sort_order', 'is_active', 'metrics'];
    protected $casts = ['is_active' => 'boolean'];

    /** @return array các chỉ tiêu đã decode */
    public function metricList()
    {
        $m = json_decode($this->metrics, true);
        return is_array($m) ? $m : [];
    }

    /** @return int[] danh sách khoa HIS (JSON his_department_ids; fallback cột đơn cũ) */
    public function hisDepartmentIds()
    {
        $ids = json_decode($this->his_department_ids, true);
        if (is_array($ids) && count($ids)) {
            return array_values(array_map('intval', $ids));
        }
        if ($this->his_department_id !== null && $this->his_department_id !== '') {
            return [(int) $this->his_department_id];
        }
        return [];
    }
}
```

- [ ] **Step 4: Chạy test, xác nhận PASS**

Run: `vendor\bin\phpunit tests\Unit\GiaoBan\GiaoBanDeptConfigTest.php`
Expected: PASS (3 tests).

- [ ] **Step 5: Commit**

```bash
git add app/Models/GiaoBan/GiaoBanDeptConfig.php tests/Unit/GiaoBan/GiaoBanDeptConfigTest.php
git commit -m "feat(giao-ban): GiaoBanDeptConfig.hisDepartmentIds + block_type fillable (TDD)"
```

---

### Task 3: MetricService — builder mới + service_count đa khoa (TDD)

**Files:**
- Modify: `app/Services/GiaoBan/GiaoBanMetricService.php`
- Test: `tests/Unit/GiaoBan/GiaoBanMetricServiceTest.php` (thêm test, giữ test cũ)

- [ ] **Step 1: Thêm failing tests vào file test hiện có**

Thêm các method sau vào class `GiaoBanMetricServiceTest` (giữ nguyên 6 test cũ):

```php
    /** @test */
    public function internal_transfer_sql_counts_transfers_within_dept_set()
    {
        list($sql, $binds) = $this->svc->buildInternalTransferSql('2026-07-07 07:00:00', '2026-07-08 07:00:00', [73, 54]);
        $this->assertContains('p.department_id IN (73,54)', $sql);
        $this->assertContains('nx.department_id IN (73,54)', $sql);
        $this->assertContains('nx.department_id <> p.department_id', $sql);
        $this->assertEquals(['from_time' => '20260707070000', 'to_time' => '20260708070000'], $binds);
    }

    /** @test */
    public function exam_visit_sql_filters_exam_type_and_execute_dept()
    {
        list($sql, $binds) = $this->svc->buildExamVisitSql('2026-07-07 07:00:00', '2026-07-08 07:00:00', [27]);
        $this->assertContains('is_main_exam = 1', $sql);
        $this->assertContains('execute_department_id IN (27)', $sql);
        $this->assertContains('service_req_type_id = :kham_type', $sql);
        $this->assertNotContains('his_treatment', $sql); // không join khi không lọc
        $this->assertEquals('20260707070000', $binds['from_time']);
        $this->assertArrayHasKey('kham_type', $binds);
    }

    /** @test */
    public function exam_visit_sql_joins_treatment_for_type_and_patient_filters()
    {
        list($sql, $binds) = $this->svc->buildExamVisitSql('2026-07-07 07:00:00', '2026-07-08 07:00:00', [27],
            ['treatment_type_ids' => [3], 'patient_type_ids' => [82]]);
        $this->assertContains('JOIN his_treatment t ON t.id = sr.treatment_id', $sql);
        $this->assertContains('t.tdl_treatment_type_id IN (3)', $sql);
        $this->assertContains('t.tdl_patient_type_id IN (82)', $sql);
    }

    /** @test */
    public function service_count_supports_execute_department_ids_list()
    {
        list($sql, $binds) = $this->svc->buildServiceCountSql(
            '2026-07-07 07:00:00', '2026-07-08 07:00:00',
            ['service_type_ids' => [2], 'execute_department_ids' => [43, 62]]
        );
        $this->assertContains('tdl_execute_department_id IN (43,62)', $sql);
        $this->assertContains('tdl_service_type_id IN (2)', $sql);
    }

    /** @test */
    public function sum_over_depts_sums_map_values()
    {
        $map = [73 => 91, 54 => 54, 27 => 3];
        $this->assertSame(145.0, $this->svc->sumOverDepts($map, [73, 54]));
        $this->assertSame(0.0, $this->svc->sumOverDepts($map, [999]));
    }

    /** @test */
    public function sum_end_type_sums_matching_dept_and_codes()
    {
        $rows = [
            (object) ['department_id' => 73, 'end_code' => 'RV', 'so_bn' => 10],
            (object) ['department_id' => 73, 'end_code' => 'CV', 'so_bn' => 2],
            (object) ['department_id' => 54, 'end_code' => 'RV', 'so_bn' => 5],
        ];
        $this->assertSame(15.0, $this->svc->sumEndType($rows, [73, 54], ['RV']));
        $this->assertSame(2.0, $this->svc->sumEndType($rows, [73, 54], ['CV']));
    }
```

- [ ] **Step 2: Chạy test, xác nhận FAIL**

Run: `vendor\bin\phpunit tests\Unit\GiaoBan\GiaoBanMetricServiceTest.php`
Expected: FAIL — các method mới chưa tồn tại (6 test cũ vẫn PASS).

- [ ] **Step 3: Thêm builder + hàm gộp vào GiaoBanMetricService**

Mở `app/Services/GiaoBan/GiaoBanMetricService.php`.

3a. Trong `buildServiceCountSql`, NGAY SAU khối `if (!empty($filter['execute_department_id'])) { ... }` (kết thúc bằng `$binds['exec_dept'] = ...;` rồi `}`), thêm hỗ trợ danh sách:

```php
        if (!empty($filter['execute_department_ids'])) {
            $ids = implode(',', array_map('intval', $filter['execute_department_ids']));
            $conds[] = "ss.tdl_execute_department_id IN ($ids)";
        }
        if (!empty($filter['request_department_ids'])) {
            $ids = implode(',', array_map('intval', $filter['request_department_ids']));
            $conds[] = "sr.request_department_id IN ($ids)";
        }
```

3b. NGAY TRƯỚC dòng `// ================= chạy trên HISPro` (khối comment phân cách), thêm các method mới:

```php
    /**
     * Đếm lượt chuyển khoa NỘI BỘ trong tập khoa (cả nguồn và đích đều thuộc set).
     * Dùng để trừ khỏi chuyển đến / chuyển đi khi gộp nhiều khoa HIS.
     */
    public function buildInternalTransferSql($from, $to, array $deptIds)
    {
        $ids = implode(',', array_map('intval', $deptIds)) ?: '-1';
        $sql = "
            SELECT COUNT(*) AS so_noi_bo
            FROM his_department_tran nx
            JOIN his_department_tran p ON p.id = nx.previous_id
            JOIN his_treatment t ON t.id = nx.treatment_id
            WHERE nx.is_delete = 0 AND p.is_delete = 0 AND t.is_delete = 0
              AND t.tdl_treatment_type_id = 3
              AND nx.department_id <> p.department_id
              AND p.department_id IN ($ids) AND nx.department_id IN ($ids)
              AND nx.department_in_time BETWEEN :from_time AND :to_time";
        return [$sql, ['from_time' => $this->toHisTime($from), 'to_time' => $this->toHisTime($to)]];
    }

    /**
     * Đếm lượt khám (khối ngoại trú) do các khoa khám thực hiện trong kỳ.
     * $deptIds: danh sách khoa khám (his_department.id).
     */
    public function buildExamVisitSql($from, $to, array $deptIds, array $filter = [])
    {
        $ids = implode(',', array_map('intval', $deptIds)) ?: '-1';
        $khamType = (int) config('__tech.service_req_type_kham', 1);
        $join = '';
        $extra = '';
        if (!empty($filter['treatment_type_ids'])) {
            $t = implode(',', array_map('intval', $filter['treatment_type_ids']));
            $join = ' JOIN his_treatment t ON t.id = sr.treatment_id';
            $extra .= " AND t.tdl_treatment_type_id IN ($t)";
        }
        if (!empty($filter['patient_type_ids'])) {
            $p = implode(',', array_map('intval', $filter['patient_type_ids']));
            if ($join === '') $join = ' JOIN his_treatment t ON t.id = sr.treatment_id';
            $extra .= " AND t.tdl_patient_type_id IN ($p)";
        }
        $sql = "
            SELECT COUNT(*) AS so_luong
            FROM his_service_req sr$join
            WHERE sr.is_delete = 0
              AND sr.service_req_type_id = :kham_type
              AND sr.is_main_exam = 1
              AND sr.execute_department_id IN ($ids)
              AND sr.intruction_time BETWEEN :from_time AND :to_time$extra";
        return [$sql, [
            'kham_type' => $khamType,
            'from_time' => $this->toHisTime($from),
            'to_time' => $this->toHisTime($to),
        ]];
    }

    /** Tổng giá trị map (department_id => value) trên danh sách khoa. */
    public function sumOverDepts(array $map, array $deptIds)
    {
        $s = 0.0;
        foreach ($deptIds as $id) {
            if (isset($map[(int) $id])) $s += (float) $map[(int) $id];
        }
        return $s;
    }

    /** Tổng end_type rows khớp khoa (trong deptIds) và mã kết thúc (trong endCodes). */
    public function sumEndType($endRows, array $deptIds, array $endCodes)
    {
        $set = array_map('intval', $deptIds);
        $s = 0.0;
        foreach ($endRows as $r) {
            if (in_array((int) $r->department_id, $set, true) && in_array($r->end_code, $endCodes, true)) {
                $s += (float) $r->so_bn;
            }
        }
        return $s;
    }
```

- [ ] **Step 4: Chạy test, xác nhận PASS**

Run: `vendor\bin\phpunit tests\Unit\GiaoBan\GiaoBanMetricServiceTest.php`
Expected: PASS (11 tests — 6 cũ + 5 mới).

- [ ] **Step 5: Commit**

```bash
git add app/Services/GiaoBan/GiaoBanMetricService.php tests/Unit/GiaoBan/GiaoBanMetricServiceTest.php
git commit -m "feat(giao-ban): builder internal-transfer/exam + service_count da khoa + ham gop (TDD)"
```

---

### Task 4: MetricService.computeAll — rẽ nhánh block_type + gộp khoa

**Files:**
- Modify: `app/Services/GiaoBan/GiaoBanMetricService.php` (thay method `computeAll`)

Không unit-test trực tiếp (chạm DB); xác minh ở Task 7. Đảm bảo không phá `computeAll($deptConfigs, $from, $to)` chữ ký.

- [ ] **Step 1: Thay toàn bộ method `computeAll`**

Thay method `computeAll(...)` hiện tại bằng:

```php
    /**
     * Tính toàn bộ auto_value cho danh sách dept config.
     * Rẽ nhánh theo block_type; cộng dồn qua danh sách khoa HIS của config;
     * loại trừ chuyển nội bộ khi config gộp >1 khoa.
     * @return array map "dept_config_id|metric_code" => float|null
     */
    public function computeAll($deptConfigs, $from, $to)
    {
        // Maps toàn viện dùng chung cho khối điều trị
        $censusFrom = $this->pluckByDept($this->selectHis($this->buildCensusSql($from)), 'so_bn');
        $censusTo   = $this->pluckByDept($this->selectHis($this->buildCensusSql($to)), 'so_bn');
        $moveIn     = $this->selectHis($this->buildMovementInSql($from, $to));
        $bnVao      = $this->pluckByDept($moveIn, 'bn_vao');
        $bnDen      = $this->pluckByDept($moveIn, 'bn_chuyen_den');
        $moveOut    = $this->pluckByDept($this->selectHis($this->buildMovementOutSql($from, $to)), 'bn_chuyen_khoa');
        $endRows    = $this->selectHis($this->buildEndTypeSql($from, $to));

        $values = [];
        foreach ($deptConfigs as $cfg) {
            $deptIds = $cfg->hisDepartmentIds();
            // Chuyển nội bộ (chỉ khi gộp >1 khoa)
            $internal = 0.0;
            if (count($deptIds) > 1) {
                $rows = $this->selectHis($this->buildInternalTransferSql($from, $to, $deptIds));
                $internal = (float) ($rows[0]->so_noi_bo ?? 0);
            }

            foreach ($cfg->metricList() as $m) {
                $key = $cfg->id . '|' . $m['code'];
                switch ($m['type']) {
                    case 'census_from':  $values[$key] = $this->sumOverDepts($censusFrom, $deptIds); break;
                    case 'census_to':    $values[$key] = $this->sumOverDepts($censusTo, $deptIds); break;
                    case 'movement_in':  $values[$key] = $this->sumOverDepts($bnVao, $deptIds); break;
                    case 'movement_transfer_in':
                        $values[$key] = max(0.0, $this->sumOverDepts($bnDen, $deptIds) - $internal);
                        break;
                    case 'movement_transfer_out':
                        $values[$key] = max(0.0, $this->sumOverDepts($moveOut, $deptIds) - $internal);
                        break;
                    case 'end_type':
                        $values[$key] = $this->sumEndType($endRows, $deptIds, $m['end_codes']);
                        break;
                    case 'bed_count':
                        $rows = $this->selectHis($this->buildBedCountSql($to, isset($m['bed_ids']) ? $m['bed_ids'] : []));
                        $values[$key] = (float) ($rows[0]->so_bn ?? 0);
                        break;
                    case 'exam_visit':
                        $rows = $this->selectHis($this->buildExamVisitSql($from, $to, $deptIds, isset($m['filter']) ? $m['filter'] : []));
                        $values[$key] = (float) ($rows[0]->so_luong ?? 0);
                        break;
                    case 'service_count':
                        $filter = isset($m['filter']) ? $m['filter'] : [];
                        if (!empty($filter['execute_department_id_self'])) {
                            $filter['execute_department_ids'] = $deptIds;
                            unset($filter['execute_department_id_self']);
                        } elseif (!empty($deptIds)
                            && empty($filter['execute_room_ids'])
                            && empty($filter['execute_department_id'])
                            && empty($filter['execute_department_ids'])
                            && empty($filter['request_department_id'])
                            && empty($filter['request_department_ids'])) {
                            $filter['request_department_ids'] = $deptIds;
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
                        $values[$key] = null;
                }
            }
        }
        return $values;
    }
```

- [ ] **Step 2: Chạy toàn bộ unit test giao ban (không phá test cũ)**

Run: `vendor\bin\phpunit tests\Unit\GiaoBan`
Expected: PASS toàn bộ (model 3 + metric 11 + report 3 + permission 3 = 20 tests).

- [ ] **Step 3: Commit**

```bash
git add app/Services/GiaoBan/GiaoBanMetricService.php
git commit -m "feat(giao-ban): computeAll re nhanh block_type + gop khoa + loai noi bo"
```

---

### Task 5: ConfigController — searchUsers + index + validate

**Files:**
- Modify: `app/Http/Controllers/KHTH/GiaoBanConfigController.php`
- Modify: `routes/web.php`

- [ ] **Step 1: Sửa controller**

Thay toàn bộ `app/Http/Controllers/KHTH/GiaoBanConfigController.php`:

```php
<?php

namespace App\Http\Controllers\KHTH;

use App\Http\Controllers\Controller;
use App\Models\GiaoBan\GiaoBanDeptConfig;
use App\Models\GiaoBan\GiaoBanUserDepartment;
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
            'SELECT id, department_code, department_name, NVL(is_exam,0) is_exam, NVL(is_clinical,0) is_clinical
             FROM his_department WHERE is_delete = 0 ORDER BY department_name'
        ))->map(function ($r) {
            return (object) array_change_key_case((array) $r, CASE_LOWER);
        });
        return view('khth.giaoban-config', ['hisDepartments' => $hisDepartments]);
    }

    public function fetch()
    {
        $configs = GiaoBanDeptConfig::orderBy('sort_order')->get();
        $assignments = GiaoBanUserDepartment::all();
        // Ten user acs_user cho cac id da gan (de hien chip)
        $userIds = $assignments->pluck('user_id')->unique()->filter()->values()->all();
        $users = [];
        if (count($userIds)) {
            $ids = implode(',', array_map('intval', $userIds));
            try {
                $rows = DB::connection('ACS_RS')->select(
                    "SELECT id, loginname, username FROM acs_user WHERE id IN ($ids)"
                );
                foreach ($rows as $r) {
                    $u = (object) array_change_key_case((array) $r, CASE_LOWER);
                    $users[(int) $u->id] = $u->username ?: $u->loginname;
                }
            } catch (\Exception $e) {
                $users = [];
            }
        }
        return response()->json(['configs' => $configs, 'assignments' => $assignments, 'user_names' => $users]);
    }

    /** Tim acs_user (CustomUser HIS) theo ten/loginname. */
    public function searchUsers(Request $request)
    {
        $q = strtolower(trim((string) $request->input('q', '')));
        if (mb_strlen($q) < 2) return response()->json([]);
        try {
            $rows = DB::connection('ACS_RS')->select(
                "SELECT * FROM (
                    SELECT id, loginname, username FROM acs_user
                    WHERE is_active = 1
                      AND (LOWER(loginname) LIKE :q1 OR LOWER(username) LIKE :q2)
                    ORDER BY username
                 ) WHERE ROWNUM <= 20",
                ['q1' => '%' . $q . '%', 'q2' => '%' . $q . '%']
            );
        } catch (\Exception $e) {
            return response()->json([]);
        }
        $out = array_map(function ($r) {
            return (object) array_change_key_case((array) $r, CASE_LOWER);
        }, $rows);
        return response()->json($out);
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'display_name' => 'required|string|max:255',
            'block_type' => 'required|in:dieu_tri,kham,can_lam_sang',
            'his_department_ids' => 'nullable|string',
            'sort_order' => 'nullable|integer',
            'metrics' => 'required|string',
        ]);
        if (!$this->validJson($request->input('metrics'))) {
            return response()->json(['message' => 'metrics không phải JSON hợp lệ'], 422);
        }
        if ($request->filled('his_department_ids') && !$this->validJson($request->input('his_department_ids'))) {
            return response()->json(['message' => 'his_department_ids không phải JSON hợp lệ'], 422);
        }
        $cfg = GiaoBanDeptConfig::create(
            $request->only(['display_name', 'block_type', 'his_department_ids', 'sort_order', 'metrics'])
            + ['is_active' => true]
        );
        return response()->json(['ok' => true, 'id' => $cfg->id]);
    }

    public function update(Request $request, $id)
    {
        $cfg = GiaoBanDeptConfig::findOrFail($id);
        if ($request->filled('block_type')) {
            $this->validate($request, ['block_type' => 'in:dieu_tri,kham,can_lam_sang']);
        }
        if ($request->filled('metrics') && !$this->validJson($request->input('metrics'))) {
            return response()->json(['message' => 'metrics không phải JSON hợp lệ'], 422);
        }
        if ($request->filled('his_department_ids') && !$this->validJson($request->input('his_department_ids'))) {
            return response()->json(['message' => 'his_department_ids không phải JSON hợp lệ'], 422);
        }
        $cfg->update($request->only(['display_name', 'block_type', 'his_department_ids', 'sort_order', 'metrics', 'is_active']));
        return response()->json(['ok' => true]);
    }

    /** Gán lại toàn bộ khoa cho 1 acs_user. */
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

    protected function validJson($s)
    {
        json_decode($s, true);
        return json_last_error() === JSON_ERROR_NONE;
    }
}
```

- [ ] **Step 2: Thêm route**

Trong `routes/web.php`, trong group `checkrole:giaoban`, ngay sau route `giao-ban/cau-hinh-assign`, thêm:

```php
        Route::get('giao-ban/cau-hinh/search-users', 'KHTH\GiaoBanConfigController@searchUsers')->name('khth.giao-ban-config-search-users');
```

LƯU Ý thứ tự route: `giao-ban/cau-hinh/search-users` (literal) phải nằm TRƯỚC `giao-ban/cau-hinh/{id}` (POST) — nhưng cái kia là POST còn đây là GET nên không đụng nhau. Vẫn đặt ngay cạnh nhóm cau-hinh cho gọn.

- [ ] **Step 3: Verify cú pháp + route**

Run: `php -l app/Http/Controllers/KHTH/GiaoBanConfigController.php`
Expected: `No syntax errors detected`.
Run (PowerShell): `Select-String -Path routes/web.php -Pattern 'search-users'`
Expected: 1 dòng.

- [ ] **Step 4: Commit**

```bash
git add app/Http/Controllers/KHTH/GiaoBanConfigController.php routes/web.php
git commit -m "feat(giao-ban): config searchUsers (acs_user) + validate block_type/dept_ids"
```

---

### Task 6: View cấu hình — loại khối, multi-select khoa, autocomplete user

**Files:**
- Modify: `resources/views/khth/giaoban-config.blade.php`

- [ ] **Step 1: Thay toàn bộ view**

Thay `resources/views/khth/giaoban-config.blade.php` bằng:

```blade
@extends('adminlte::page')
@section('title', 'Cấu hình báo cáo giao ban')
@section('content_header')<h1>Cấu hình báo cáo giao ban</h1>@stop

@section('content')
<div class="row">
  <div class="col-md-8">
    <div class="box box-primary">
      <div class="box-header with-border"><b>Khoa hiển thị trên báo cáo</b></div>
      <div class="box-body table-responsive">
        <table class="table table-bordered" id="tbl-configs">
          <thead><tr><th style="width:70px">TT</th><th>Tên hiển thị</th><th style="width:130px">Loại khối</th><th>Khoa HIS (gộp)</th><th>Chỉ tiêu (JSON)</th><th style="width:60px">BID</th><th style="width:60px"></th></tr></thead>
          <tbody></tbody>
        </table>
        <button id="btn-add" class="btn btn-primary"><i class="fa fa-plus"></i> Thêm khoa</button>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="box box-warning">
      <div class="box-header with-border"><b>Gán tài khoản HIS ↔ khoa</b></div>
      <div class="box-body">
        <label>Tìm tài khoản (tên / loginname)</label>
        <input type="text" id="user-search" class="form-control" placeholder="gõ ≥ 2 ký tự...">
        <div id="user-results" class="list-group" style="max-height:180px;overflow:auto;margin-top:4px"></div>
        <div id="user-picked" style="margin-top:8px"></div>
        <label style="margin-top:10px">Khoa được nhập</label>
        <select id="assign-depts" class="form-control" multiple size="10"></select>
        <button id="btn-assign" class="btn btn-warning" style="margin-top:10px" disabled>Lưu gán khoa</button>
      </div>
    </div>
  </div>
</div>

<script type="application/json" id="tpl-dieu_tri">[
  {"code":"bn_cu","name":"BN cũ","type":"census_from"},
  {"code":"bn_vao","name":"BN vào","type":"movement_in"},
  {"code":"bn_chuyen_den","name":"BN chuyển đến","type":"movement_transfer_in"},
  {"code":"bn_ra_vien","name":"BN ra viện","type":"end_type","end_codes":["RV","HK","CC","XV","KH","TR"]},
  {"code":"bn_chuyen_vien","name":"BN chuyển viện","type":"end_type","end_codes":["CV"]},
  {"code":"bn_tu_vong","name":"BN tử vong","type":"end_type","end_codes":["TV"]},
  {"code":"bn_chuyen_khoa","name":"BN chuyển khoa","type":"movement_transfer_out"},
  {"code":"hien_co","name":"Hiện có","type":"census_to"}
]</script>
<script type="application/json" id="tpl-kham">[
  {"code":"luot_kham","name":"Lượt khám","type":"exam_visit"},
  {"code":"vao_vien","name":"Vào viện","type":"exam_visit","filter":{"treatment_type_ids":[3]}},
  {"code":"cap_toa_ve","name":"Cấp toa/ngoại trú","type":"exam_visit","filter":{"treatment_type_ids":[2]}},
  {"code":"kham_yeu_cau","name":"Khám yêu cầu","type":"exam_visit","filter":{"patient_type_ids":[82]}},
  {"code":"kham_bhyt","name":"Khám BHYT","type":"exam_visit","filter":{"patient_type_ids":[1]}},
  {"code":"chuyen_gia","name":"Chuyên gia BV tỉnh","type":"manual"}
]</script>
<script type="application/json" id="tpl-can_lam_sang">[
  {"code":"tong_dv","name":"Tổng dịch vụ","type":"service_count","filter":{"execute_department_id_self":true}}
]</script>
@stop

@section('js')
<script>
var HIS_DEPTS = @json($hisDepartments);
var STATE = { configs: [], assignments: [], user_names: {} };
var PICKED_USER = null;
var BLOCKS = { dieu_tri: 'Điều trị (nội trú)', kham: 'Khám (ngoại trú)', can_lam_sang: 'Cận lâm sàng' };

function esc(s) {
  return String(s === null || s === undefined ? '' : s).replace(/[&<>"']/g, function (c) {
    return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
  });
}
function blockOptions(sel) {
  var h = '';
  for (var k in BLOCKS) h += '<option value="' + k + '"' + (k === sel ? ' selected' : '') + '>' + esc(BLOCKS[k]) + '</option>';
  return h;
}
function deptMultiOptions(selectedIds) {
  var sel = {};
  (selectedIds || []).forEach(function (id) { sel[String(id)] = 1; });
  var h = '';
  HIS_DEPTS.forEach(function (d) {
    h += '<option value="' + d.id + '"' + (sel[String(d.id)] ? ' selected' : '') + '>' + esc(d.department_name) + '</option>';
  });
  return h;
}
function parseIds(jsonStr) {
  try { var a = JSON.parse(jsonStr || '[]'); return Array.isArray(a) ? a : []; } catch (e) { return []; }
}

function renderConfigs() {
  var $tb = $('#tbl-configs tbody').empty();
  STATE.configs.forEach(function (c) {
    var ids = parseIds(c.his_department_ids);
    $tb.append('<tr data-id="' + c.id + '">' +
      '<td><input class="form-control f-sort" type="number" value="' + (c.sort_order || 0) + '"></td>' +
      '<td><input class="form-control f-name" value="' + esc(c.display_name) + '"></td>' +
      '<td><select class="form-control f-block">' + blockOptions(c.block_type || 'dieu_tri') + '</select></td>' +
      '<td><select class="form-control f-depts" multiple size="4">' + deptMultiOptions(ids) + '</select></td>' +
      '<td><textarea class="form-control f-metrics" rows="3">' + esc(c.metrics) + '</textarea>' +
      '<button class="btn btn-xs btn-default btn-tpl" style="margin-top:4px">Nạp mẫu theo khối</button></td>' +
      '<td><input type="checkbox" class="f-active"' + (c.is_active ? ' checked' : '') + '></td>' +
      '<td><button class="btn btn-sm btn-primary btn-save-cfg">Lưu</button></td></tr>');
  });
  var $sel = $('#assign-depts').empty();
  STATE.configs.forEach(function (c) {
    if (c.is_active) $sel.append('<option value="' + c.id + '">' + esc(c.display_name) + '</option>');
  });
}

function loadAll() {
  $.get('{{ route('khth.giao-ban-config-fetch') }}', function (res) {
    STATE = res; renderConfigs(); syncAssign();
  });
}

function collectIds($sel) {
  var v = $sel.val() || [];
  return JSON.stringify(v.map(function (x) { return parseInt(x, 10); }));
}

function syncAssign() {
  if (!PICKED_USER) return;
  var mine = STATE.assignments.filter(function (a) { return a.user_id === PICKED_USER.id; })
    .map(function (a) { return String(a.dept_config_id); });
  $('#assign-depts').val(mine);
}

$(function () {
  loadAll();

  $('#btn-add').on('click', function () {
    var name = prompt('Tên hiển thị khoa mới:');
    if (!name) return;
    $.post('{{ route('khth.giao-ban-config-store') }}', {
      _token: '{{ csrf_token() }}', display_name: name, block_type: 'dieu_tri',
      sort_order: STATE.configs.length + 1, his_department_ids: '[]',
      metrics: $('#tpl-dieu_tri').text().trim()
    }).done(loadAll).fail(function (xhr) {
      alert(xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Lỗi thêm khoa');
    });
  });

  $('#tbl-configs').on('click', '.btn-tpl', function () {
    var $tr = $(this).closest('tr');
    var block = $tr.find('.f-block').val();
    $tr.find('.f-metrics').val($('#tpl-' + block).text().trim());
  });

  $('#tbl-configs').on('click', '.btn-save-cfg', function () {
    var $tr = $(this).closest('tr');
    $.post('{{ url('khth/giao-ban/cau-hinh') }}/' + $tr.data('id'), {
      _token: '{{ csrf_token() }}',
      sort_order: $tr.find('.f-sort').val(), display_name: $tr.find('.f-name').val(),
      block_type: $tr.find('.f-block').val(),
      his_department_ids: collectIds($tr.find('.f-depts')),
      metrics: $tr.find('.f-metrics').val(),
      is_active: $tr.find('.f-active').is(':checked') ? 1 : 0
    }).done(loadAll).fail(function (xhr) {
      alert(xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Lỗi lưu');
    });
  });

  // autocomplete user
  var timer = null;
  $('#user-search').on('input', function () {
    var q = $(this).val();
    clearTimeout(timer);
    if (q.length < 2) { $('#user-results').empty(); return; }
    timer = setTimeout(function () {
      $.get('{{ route('khth.giao-ban-config-search-users') }}', { q: q }, function (rows) {
        var $r = $('#user-results').empty();
        rows.forEach(function (u) {
          $r.append('<a href="#" class="list-group-item u-pick" data-id="' + u.id + '" data-name="' +
            esc((u.username || u.loginname)) + '">' + esc(u.username || u.loginname) +
            ' <small>(' + esc(u.loginname) + ')</small></a>');
        });
      });
    }, 300);
  });
  $('#user-results').on('click', '.u-pick', function (e) {
    e.preventDefault();
    PICKED_USER = { id: parseInt($(this).data('id'), 10), name: $(this).data('name') };
    $('#user-picked').html('Đang gán cho: <b>' + esc(PICKED_USER.name) + '</b>');
    $('#user-results').empty();
    $('#btn-assign').prop('disabled', false);
    syncAssign();
  });

  $('#btn-assign').on('click', function () {
    if (!PICKED_USER) return;
    $.post('{{ route('khth.giao-ban-config-assign') }}', {
      _token: '{{ csrf_token() }}', user_id: PICKED_USER.id,
      dept_config_ids: $('#assign-depts').val() || []
    }).done(function () { alert('Đã lưu'); loadAll(); });
  });
});
</script>
@stop
```

- [ ] **Step 2: Verify Blade compile**

Run: `php artisan view:clear`
Expected: chạy không lỗi.
Run (PowerShell): `Test-Path resources/views/khth/giaoban-config.blade.php`
Expected: `True`.

- [ ] **Step 3: Commit**

```bash
git add resources/views/khth/giaoban-config.blade.php
git commit -m "feat(giao-ban): view cau hinh loai khoi + multi khoa + autocomplete user HIS"
```

---

### Task 7: Đối chiếu HIS thật + kiểm thử tổng thể + readme

**Files:** không sửa code trừ khi phát hiện lỗi.

- [ ] **Step 1: Script đối chiếu 3 khối trên HIS thật**

Tạo `scratchpad/verify_config_upgrade.php` (đường dẫn tuyệt đối) — seed 3 config (khoa gộp Nội, khoa khám K01, khoa CLS CĐHA) rồi chạy `computeAll`, in giá trị:

```php
<?php
require 'C:/Users/tracnn/qlbv/vendor/autoload.php';
$app = require 'C:/Users/tracnn/qlbv/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use App\Models\GiaoBan\GiaoBanDeptConfig;
use App\Services\GiaoBan\GiaoBanMetricService;

GiaoBanDeptConfig::query()->delete();
GiaoBanDeptConfig::create(['display_name'=>'Hệ Nội (gộp)','block_type'=>'dieu_tri','sort_order'=>1,'is_active'=>1,
  'his_department_ids'=>json_encode([73,54]),
  'metrics'=>json_encode([
    ['code'=>'bn_cu','name'=>'BN cũ','type'=>'census_from'],
    ['code'=>'bn_chuyen_den','name'=>'Chuyển đến','type'=>'movement_transfer_in'],
    ['code'=>'bn_chuyen_khoa','name'=>'Chuyển đi','type'=>'movement_transfer_out'],
    ['code'=>'hien_co','name'=>'Hiện có','type'=>'census_to'],
  ])]);
GiaoBanDeptConfig::create(['display_name'=>'Khoa Khám bệnh','block_type'=>'kham','sort_order'=>2,'is_active'=>1,
  'his_department_ids'=>json_encode([27]),
  'metrics'=>json_encode([
    ['code'=>'luot_kham','name'=>'Lượt khám','type'=>'exam_visit'],
    ['code'=>'vao_vien','name'=>'Vào viện','type'=>'exam_visit','filter'=>['treatment_type_ids'=>[3]]],
    ['code'=>'cap_toa_ve','name'=>'Cấp toa/ngoại trú','type'=>'exam_visit','filter'=>['treatment_type_ids'=>[2]]],
    ['code'=>'kham_yeu_cau','name'=>'Khám yêu cầu','type'=>'exam_visit','filter'=>['patient_type_ids'=>[82]]],
    ['code'=>'kham_bhyt','name'=>'Khám BHYT','type'=>'exam_visit','filter'=>['patient_type_ids'=>[1]]],
  ])]);
GiaoBanDeptConfig::create(['display_name'=>'Khoa CĐHA','block_type'=>'can_lam_sang','sort_order'=>3,'is_active'=>1,
  'his_department_ids'=>json_encode([46]),
  'metrics'=>json_encode([['code'=>'tong_dv','name'=>'Tổng DV','type'=>'service_count','filter'=>['execute_department_id_self'=>true]]])]);

$svc = new GiaoBanMetricService();
$configs = GiaoBanDeptConfig::orderBy('sort_order')->get();
$from = date('Y-m-d 07:00:00', strtotime('-1 day'));
$to = date('Y-m-d 07:00:00');
$vals = $svc->computeAll($configs, $from, $to);
foreach ($configs as $c) {
  echo "== {$c->display_name} ({$c->block_type}) khoa=" . implode(',', $c->hisDepartmentIds()) . " ==\n";
  foreach ($c->metricList() as $m) {
    $k = $c->id . '|' . $m['code'];
    echo "  {$m['name']}: " . (array_key_exists($k,$vals) && $vals[$k]!==null ? $vals[$k] : 'NULL') . "\n";
  }
}
echo "\n(kiem tra thu cong: chuyen den/di cua 'He Noi' gop phai <= tong roi cua khoa 73+54 do da loai noi bo)\n";
GiaoBanDeptConfig::query()->delete();
echo "cleaned\n";
```

Run: `php scratchpad/verify_config_upgrade.php`
Expected: in ra giá trị 3 khối; "Hệ Nội" chuyển đến/đi đã trừ nội bộ (nhỏ hơn tổng rời nếu có luân chuyển Nội TH↔Nội TM); "Khoa Khám" lượt khám > 0; "Khoa CĐHA" tổng DV > 0. Đối chiếu số hợp lý với thực tế.

- [ ] **Step 2: Nếu số bất hợp lý → chẩn đoán & sửa**

Nếu lượt khám = 0: kiểm tra `execute_department_id` vs `request_department_id` cho khoa khám bằng sqlcl (đã biết execute_department_id=27 cho 834 lượt). Nếu CLS tổng DV = 0: xác nhận `tdl_execute_department_id` của CĐHA. Sửa builder tương ứng + cập nhật test string-assert, chạy lại `vendor\bin\phpunit tests\Unit\GiaoBan`.

- [ ] **Step 3: Chạy toàn bộ test giao ban**

Run: `vendor\bin\phpunit tests\Unit\GiaoBan`
Expected: PASS toàn bộ (20 tests).

- [ ] **Step 4: Cập nhật readme + commit**

Thêm đầu `readme.md`:
```markdown
# 08/07/2026 (cập nhật 4)

- Nâng cấp cấu hình Báo cáo giao ban: 1 khoa báo cáo gộp nhiều khoa HIS (loại trừ chuyển nội bộ); phân loại khối Điều trị/Khám/Cận lâm sàng với cách thống kê riêng (census, lượt khám, đếm dịch vụ); gán tài khoản bằng tài khoản HIS (acs_user) qua ô tìm kiếm; thêm chỉ tiêu cho khoa CĐHA/Xét nghiệm.
```

```bash
git add readme.md
git commit -m "docs(giao-ban): readme nang cap cau hinh; hoan tat kiem thu"
```
