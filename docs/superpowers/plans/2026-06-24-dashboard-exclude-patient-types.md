# Loại patient_type khỏi thống kê dashboard — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Loại các `patient_type` cấu hình được (mặc định KSK id 43) khỏi mọi chỉ số thống kê của dashboard Home.

**Architecture:** Một trait cung cấp accessor `excludedPatientTypeIds()` đọc từ `config/organization.php`. Mỗi query dashboard chèn `->when($this->excludedPatientTypeIds(), fn thêm whereNotIn)` ngay trước `->get()` — chain-safe và **an toàn khi danh sách rỗng** (mảng rỗng là falsy ⇒ `when` bỏ qua ⇒ không sinh `0=1` như `whereNotIn([])`). Method raw SQL `bedStatusByDepartment` chèn mệnh đề chuỗi.

**Tech Stack:** Laravel 5.5, Oracle 12c (yajra/laravel-oci8), PHPUnit.

**Spec:** `docs/superpowers/specs/2026-06-24-dashboard-exclude-patient-types-design.md`

> **Ghi chú tinh chỉnh so với spec:** spec mô tả helper `applyExcludePatientType($query,$column)`. Khi implement dùng `->when()` inline (giữ nguyên fluent chain, tránh bug `whereNotIn([])` = `0=1` khi config rỗng). Accessor `excludedPatientTypeIds()` vẫn nằm ở trait và được unit-test.

**Verification note:** Các query chạy trực tiếp trên Oracle 35M dòng, không seed được ⇒ không unit-test SQL. Unit-test phần thuần (`excludedPatientTypeIds` đọc/ép kiểu config). Đúng-sai SQL kiểm bằng smoke so tổng trước/sau trên Oracle + `php -l`.

---

### Task 1: Thêm config danh sách loại-trừ

**Files:**
- Modify: `config/organization.php`

- [ ] **Step 1: Thêm khối `dashboard` vào mảng trả về**

Tìm dòng `'patient_type_code_evenue' => [` (gần cuối, trước `];`). Thêm khối sau NGAY TRƯỚC dòng đóng mảng `];`:

```php
    'dashboard' => [
        // patient_type_id KHÔNG tính vào thống kê KCB của dashboard Home (KSK đoàn/từ thiện...)
        'exclude_patient_type_ids' => [43], // 43 = KSK (code 03)
    ],
```

- [ ] **Step 2: Verify**

Run: `cd "C:\Users\tracnn\qlbv" && php -r "echo json_encode(require 'config/organization.php');" 2>&1 | grep -o "exclude_patient_type_ids.\{0,20\}"`
Expected: thấy `exclude_patient_type_ids":[43]`.

- [ ] **Step 3: Commit**

```bash
git add config/organization.php
git commit -m "feat: config exclude_patient_type_ids cho dashboard (mac dinh KSK)"
```

---

### Task 2: Trait `ExcludesDashboardPatientTypes` (TDD)

**Files:**
- Create: `app/Http/Controllers/Concerns/ExcludesDashboardPatientTypes.php`
- Test: `tests/Unit/ExcludesDashboardPatientTypesTest.php`

- [ ] **Step 1: Viết test thất bại**

Create `tests/Unit/ExcludesDashboardPatientTypesTest.php`:

```php
<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Http\Controllers\Concerns\ExcludesDashboardPatientTypes;

class ExcludesDashboardPatientTypesTest extends TestCase
{
    /** Test double phơi bày method protected của trait. */
    private function subject()
    {
        return new class {
            use ExcludesDashboardPatientTypes;
            public function ids(): array { return $this->excludedPatientTypeIds(); }
        };
    }

    public function test_reads_config_and_casts_to_int()
    {
        config(['organization.dashboard.exclude_patient_type_ids' => ['43', 102]]);
        $this->assertSame([43, 102], $this->subject()->ids());
    }

    public function test_returns_empty_when_not_configured()
    {
        config(['organization.dashboard.exclude_patient_type_ids' => []]);
        $this->assertSame([], $this->subject()->ids());
    }

    public function test_returns_empty_when_key_missing()
    {
        config(['organization' => ['foo' => 'bar']]); // không có dashboard
        $this->assertSame([], $this->subject()->ids());
    }
}
```

- [ ] **Step 2: Chạy test — xác nhận FAIL**

Run: `cd "C:\Users\tracnn\qlbv" && vendor/bin/phpunit tests/Unit/ExcludesDashboardPatientTypesTest.php`
Expected: FAIL — `Class 'App\Http\Controllers\Concerns\ExcludesDashboardPatientTypes' not found`.

- [ ] **Step 3: Tạo trait (minimal)**

Create `app/Http/Controllers/Concerns/ExcludesDashboardPatientTypes.php`:

```php
<?php

namespace App\Http\Controllers\Concerns;

trait ExcludesDashboardPatientTypes
{
    /**
     * Danh sách patient_type_id KHÔNG tính vào thống kê dashboard (đọc từ config).
     * Ép về int, reindex; rỗng nếu không cấu hình.
     */
    protected function excludedPatientTypeIds(): array
    {
        $ids = (array) config('organization.dashboard.exclude_patient_type_ids', []);
        return array_values(array_map('intval', $ids));
    }
}
```

- [ ] **Step 4: Chạy test — xác nhận PASS**

Run: `cd "C:\Users\tracnn\qlbv" && vendor/bin/phpunit tests/Unit/ExcludesDashboardPatientTypesTest.php`
Expected: PASS (3 tests, 3 assertions OK).

- [ ] **Step 5: Commit**

```bash
git add app/Http/Controllers/Concerns/ExcludesDashboardPatientTypes.php tests/Unit/ExcludesDashboardPatientTypesTest.php
git commit -m "feat: trait ExcludesDashboardPatientTypes + unit test"
```

---

### Task 3: Wire trait + áp lọc nhóm `his_sere_serv`

**Files:**
- Modify: `app/Http/Controllers/HomeController.php`

- [ ] **Step 1: `use` trait trong class**

Tìm dòng khai báo `class HomeController extends Controller` (đầu class, sau `use App\...` imports). Thêm import ở đầu file (cạnh các `use App\...`):
```php
use App\Http\Controllers\Concerns\ExcludesDashboardPatientTypes;
```
Và ngay sau dòng `class HomeController extends Controller\n{` thêm:
```php
    use ExcludesDashboardPatientTypes;
```

- [ ] **Step 2: Áp lọc cho từng method nhóm `his_sere_serv`**

Với MỖI method dưới đây, chèn khối sau **ngay trước** dòng `->get();` cuối cùng của method (khớp thụt đầu dòng xung quanh):

```php
            ->when($this->excludedPatientTypeIds(), function ($q, $ids) {
                $q->whereNotIn('his_sere_serv.patient_type_id', $ids);
            })
```

Danh sách method (cột lọc = `his_sere_serv.patient_type_id`) và dòng `->get();` neo hiện tại:

| Method | dòng `->get();` |
|---|---|
| `fetchServiceByMachine` | 116 |
| `doanhthuByDepartment` | 322 |
| `doanhthuOverview` | 571 |
| `getExamAndParraclinical` | 1039 |
| `getDiagnoticImaging` | 1157 |
| `serviceByType` | 1395 |
| `doanhthu` | 1492 |
| `top_service_sl_chart` | 1737 |
| `top_service_st_chart` | 1779 |

- [ ] **Step 3: Verify cú pháp + số chỗ thêm**

Run: `cd "C:\Users\tracnn\qlbv" && php -l app/Http/Controllers/HomeController.php && grep -c "his_sere_serv.patient_type_id', \$ids" app/Http/Controllers/HomeController.php`
Expected: `No syntax errors detected` và số đếm = `9`.

- [ ] **Step 4: Commit**

```bash
git add app/Http/Controllers/HomeController.php
git commit -m "feat: dashboard loai patient_type - nhom his_sere_serv"
```

---

### Task 4: Áp lọc nhóm `his_treatment`

**Files:**
- Modify: `app/Http/Controllers/HomeController.php`

- [ ] **Step 1: Áp lọc cho từng method nhóm `his_treatment`**

Với MỖI method dưới đây, chèn khối sau **ngay trước** dòng `->get();` cuối cùng của method:

```php
            ->when($this->excludedPatientTypeIds(), function ($q, $ids) {
                $q->whereNotIn('his_treatment.tdl_patient_type_id', $ids);
            })
```

Danh sách method (cột lọc = `his_treatment.tdl_patient_type_id`) và dòng `->get();` neo hiện tại:

| Method | dòng `->get();` | Ghi chú bảng |
|---|---|---|
| `getDetailDayCountInpatient` | 176 | base `his_treatment` |
| `getPrescription` | 1270 | base `his_service_req` join `his_treatment` |
| `getFee` | 1380 | base `his_treatment` |
| `treatmentsByTreatmentEndType` | 1409 | base `his_treatment` |
| `getTransactionDetail` | 1439 | base `his_transaction` join `his_treatment` |
| `getTreatmentByTreatmentType` | 1453 | base `his_treatment` |
| `newpatient` | 1467 | base `his_treatment` |
| `inTreatment` | 1506 | base `his_treatment` |
| `reExamination` | 1520 | base `his_treatment` |
| `outTreatment` | 1539 | base `his_treatment` |
| `getPatientInRoomByTreatmentType` | 1638 | base `his_treatment_bed_room` join `his_treatment` |
| `treatment_type_chart` | 1654 | base `his_treatment` |
| `treatment_number_chart` | 1695 | base `his_treatment` |
| `noitru_by_department_chart` | 1821 | base `his_treatment` |
| `noitru_by_patient_type_chart` | 1864 | base `his_treatment` |

> Mọi method trên đều có bảng `his_treatment` trong FROM/JOIN nên cột `his_treatment.tdl_patient_type_id` hợp lệ (đã map ở spec).

- [ ] **Step 2: Verify cú pháp + số chỗ thêm**

Run: `cd "C:\Users\tracnn\qlbv" && php -l app/Http/Controllers/HomeController.php && grep -c "his_treatment.tdl_patient_type_id', \$ids" app/Http/Controllers/HomeController.php`
Expected: `No syntax errors detected` và số đếm = `15`.

- [ ] **Step 3: Commit**

```bash
git add app/Http/Controllers/HomeController.php
git commit -m "feat: dashboard loai patient_type - nhom his_treatment"
```

---

### Task 5: Áp lọc method `fetchKhamByRoom` (`his_service_req`)

**Files:**
- Modify: `app/Http/Controllers/HomeController.php`

- [ ] **Step 1: Chèn lọc trước `->get();` (dòng 887)**

Trong `fetchKhamByRoom`, chèn khối sau ngay trước dòng `->get();` (dòng 887):

```php
            ->when($this->excludedPatientTypeIds(), function ($q, $ids) {
                $q->whereNotIn('his_service_req.tdl_patient_type_id', $ids);
            })
```

- [ ] **Step 2: Verify**

Run: `cd "C:\Users\tracnn\qlbv" && php -l app/Http/Controllers/HomeController.php && grep -c "his_service_req.tdl_patient_type_id', \$ids" app/Http/Controllers/HomeController.php`
Expected: `No syntax errors detected` và số đếm = `1`.

- [ ] **Step 3: Commit**

```bash
git add app/Http/Controllers/HomeController.php
git commit -m "feat: dashboard loai patient_type - fetchKhamByRoom (his_service_req)"
```

---

### Task 6: Raw SQL `bedStatusByDepartment` (CTE `dang`)

**Files:**
- Modify: `app/Http/Controllers/HomeController.php` (method `bedStatusByDepartment`, ~dòng 385-416)

- [ ] **Step 1: Dựng mệnh đề loại-trừ + chèn vào CTE `dang`**

Đổi đầu method để dựng mệnh đề từ config (int đã ép nên an toàn nội suy):

Tìm:
```php
    private function bedStatusByDepartment()
    {
        $sql = "
```
Đổi thành:
```php
    private function bedStatusByDepartment()
    {
        $ids = $this->excludedPatientTypeIds();
        $excludePt = empty($ids) ? '' : ' AND t.tdl_patient_type_id NOT IN (' . implode(',', $ids) . ')';
        $sql = "
```

Trong CTE `dang`, tìm dòng:
```php
                  AND (t.out_time IS NULL OR t.out_time > :now_ts) -- chưa ra viện HOẶC hẹn ra viện ở tương lai => vẫn đang nằm giường
                GROUP BY r.department_id
```
Đổi thành (thêm `{$excludePt}` — loại KSK khỏi giường đang dùng; `tong` giữ nguyên):
```php
                  AND (t.out_time IS NULL OR t.out_time > :now_ts) -- chưa ra viện HOẶC hẹn ra viện ở tương lai => vẫn đang nằm giường
                  {$excludePt}
                GROUP BY r.department_id
```

- [ ] **Step 2: Verify cú pháp**

Run: `cd "C:\Users\tracnn\qlbv" && php -l app/Http/Controllers/HomeController.php`
Expected: `No syntax errors detected`.

- [ ] **Step 3: Smoke — query chạy được với config KSK**

Run:
```bash
cd "C:\Users\tracnn\qlbv" && printf '%s\n' \
'$c = new ReflectionMethod(App\Http\Controllers\HomeController::class, "bedStatusByDepartment");' \
'$c->setAccessible(true); $rows = $c->invoke(app(App\Http\Controllers\HomeController::class));' \
'echo "ROWS=".count($rows)." sample_dang=".($rows[0]->dang_dung ?? "n/a")."\n";' \
'exit' | php artisan tinker 2>&1 | grep -E "ROWS=|error|ORA-|Exception" | head
```
Expected: `ROWS=<n>` không lỗi ORA-/Exception (query hợp lệ với mệnh đề loại KSK).

- [ ] **Step 4: Commit**

```bash
git add app/Http/Controllers/HomeController.php
git commit -m "feat: dashboard loai patient_type - bedStatus (CTE dang)"
```

---

### Task 7: Verify toàn cục + smoke so trước/sau + push

**Files:** (không sửa code)

- [ ] **Step 1: Chạy toàn bộ unit test liên quan**

Run: `cd "C:\Users\tracnn\qlbv" && vendor/bin/phpunit tests/Unit/ExcludesDashboardPatientTypesTest.php`
Expected: OK (3 tests).

- [ ] **Step 2: Đếm tổng số điểm áp lọc (kỳ vọng 25) + lint**

Run: `cd "C:\Users\tracnn\qlbv" && php -l app/Http/Controllers/HomeController.php && grep -cE "patient_type_id', \\\$ids" app/Http/Controllers/HomeController.php`
Expected: `No syntax errors detected` và số đếm = `25` (9 + 15 + 1). (bedStatus dùng chuỗi riêng, không tính ở đây.)

- [ ] **Step 3: Smoke so tổng doanh thu trước/sau khi loại KSK (Oracle)**

Run:
```bash
cd "C:\Users\tracnn\qlbv" && printf '%s\n' \
'$from="20260601000000"; $to="20260623235959";' \
'$q = DB::connection("HISPro")->table("his_sere_serv as ss")->join("his_service_req as sr","sr.id","=","ss.service_req_id")->whereBetween("sr.intruction_time",[$from,$to])->where("sr.is_active",1)->where("sr.is_delete",0)->where("ss.is_delete",0);' \
'$all=(clone $q)->sum(DB::raw("ss.amount*ss.vir_price"));' \
'$noksk=(clone $q)->whereNotIn("ss.patient_type_id",[43])->sum(DB::raw("ss.amount*ss.vir_price"));' \
'$ksk=(clone $q)->where("ss.patient_type_id",43)->sum(DB::raw("ss.amount*ss.vir_price"));' \
'echo "ALL=".number_format((float)$all)." NO_KSK=".number_format((float)$noksk)." KSK=".number_format((float)$ksk)."\n";' \
'echo "check_all_eq_sum=".(abs((float)$all-((float)$noksk+(float)$ksk))<1?"OK":"MISMATCH")."\n";' \
'exit' | php artisan tinker 2>&1 | grep -E "ALL=|check_" | head
```
Expected: `NO_KSK = ALL - KSK` (check `OK`); phần KSK bị loại đúng bằng doanh thu KSK.

- [ ] **Step 4: Push**

```bash
git push origin main
```

---

## Hoàn tất

Sau 7 task: dashboard Home loại `patient_type` theo config (mặc định KSK id 43) trên toàn bộ chỉ số (doanh thu, đếm BN, DVKT, đơn thuốc, viện phí, giao dịch, giường). Đặt `exclude_patient_type_ids => []` ⇒ khôi phục hành vi cũ. Không đụng báo cáo KHTH khác.
