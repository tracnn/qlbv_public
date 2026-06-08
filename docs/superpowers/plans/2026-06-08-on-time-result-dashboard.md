# On-Time Result Dashboard Implementation Plan

> **For agentic workers:** REQUIRED: Use superpowers:subagent-driven-development (if subagents available) or superpowers:executing-plans to implement this plan. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Thêm trang report KHTH tính & hiển thị tỷ lệ % trả kết quả đúng hẹn / trễ hẹn của dịch vụ cận lâm sàng, có phần tổng hợp 4 chiều và bảng chi tiết drill-down + export Excel.

**Architecture:** Một service thuần `App\Services\OnTimeResultService` chứa (a) các method dựng SQL trả `[$sql, $bindings]` (Oracle, `DB::connection('HISPro')`), và (b) các method PHP thuần phân loại trạng thái dòng + tổng hợp KPI/breakdown — **unit-test được không cần DB**. Controller `KHTH\OnTimeResultController` mỏng, điều phối request → service → JSON / DataTables / Excel, được **feature-test với service mock**. View Blade dùng AdminLTE 2 + Chart.js + DataTables server-side theo đúng pattern report khảo sát hiện có.

**Tech Stack:** Laravel 5.5, yajra/laravel-oci8 (Oracle), yajra/laravel-datatables, maatwebsite/excel, Chart.js, AdminLTE 2, PHPUnit 6 + Mockery.

**Spec:** `docs/superpowers/specs/2026-06-08-on-time-result-dashboard-design.md`

> **Lưu ý lệch spec (có chủ đích):** Spec mục 4 ghi "thêm method vào `ReportDataService`". Plan này thay bằng service riêng `App\Services\OnTimeResultService` vì: (1) khớp đúng pattern test sẵn có của dự án — các `App\Services\Dashboard\*Service` đều có Unit test riêng, còn `ReportDataService` không có test; (2) gom logic phân loại/tổng hợp thuần vào 1 unit có biên rõ ràng, dễ test. Mọi yêu cầu nghiệp vụ trong spec giữ nguyên.

---

## File Structure

| File | Trách nhiệm |
|---|---|
| `app/Services/OnTimeResultService.php` | **Tạo.** SQL builders + logic thuần (classify row, summarize KPI/breakdown). Trái tim của tính toán. |
| `app/Http/Controllers/KHTH/OnTimeResultController.php` | **Tạo.** `index()`, `getSummary()`, `fetch()`, `export()`, `rooms()`. Mỏng, điều phối. |
| `app/Exports/OnTimeResultExport.php` | **Tạo.** Export Excel bảng chi tiết (dùng lại `buildDetailSqlAndBindings`). |
| `resources/views/khth/on-time-result.blade.php` | **Tạo.** View: filter + card KPI + chart + bảng tổng hợp + DataTables chi tiết. |
| `resources/views/khth/partials/search-on-time-result.blade.php` | **Tạo.** Partial bộ lọc (date range + phòng + loại DV). |
| `routes/web.php` | **Sửa.** Thêm 5 route trong group `khth/`. |
| `config/adminlte.php` | **Sửa.** Thêm 1 mục submenu KHTH. |
| `tests/Unit/OnTimeResultServiceTest.php` | **Tạo.** Unit test logic thuần. |
| `tests/Feature/OnTimeResultControllerTest.php` | **Tạo.** Feature test endpoint (mock service). |

---

## Quy ước dữ liệu (dùng xuyên suốt)

- Thời gian lưu `NUMBER` định dạng `YYYYMMDDHH24MISS`.
- Phút giữa 2 mốc (Oracle): `(TO_DATE(finish,'YYYYMMDDHH24MISS') - TO_DATE(intr,'YYYYMMDDHH24MISS')) * 24 * 60`.
- Base row (1 dòng / `his_sere_serv`) tối thiểu gồm các trường (alias):
  `service_type_id, service_type_name, execute_room_id, execute_room_name, service_id, service_name, day_val (NUMBER YYYYMMDD), estimate_duration, intruction_time, finish_time, actual_minutes (NULL nếu finish NULL)`.
- Join chuẩn (mọi query): `his_sere_serv ss` → `his_service_req sr ON sr.id = ss.service_req_id` → `his_service s ON s.id = ss.service_id` → `his_service_type st ON st.id = ss.tdl_service_type_id` → `his_execute_room er ON er.room_id = ss.tdl_execute_room_id` (LEFT JOIN er).
- WHERE chung (filter): `s.estimate_duration IS NOT NULL AND s.estimate_duration <> 0 AND ss.is_delete = 0 AND ss.is_no_execute IS NULL AND sr.is_active = 1 AND sr.is_delete = 0 AND sr.intruction_time BETWEEN :from AND :to` (+ optional `ss.tdl_execute_room_id`, `ss.tdl_service_type_id`).

**Phân loại trạng thái 1 dòng** (`OnTimeResultService::classify`):
- `finish_time` rỗng → `chua_tra`.
- `actual_minutes < 0` (finish < intr) → `bat_thuong`.
- `actual_minutes <= estimate_duration` → `dung_hen`.
- else → `tre_hen`.

**% (mẫu số chỉ gồm dung_hen + tre_hen):**
- `pct_dung_hen = round(dung_hen / (dung_hen + tre_hen) * 100, 1)` (0 nếu mẫu = 0).
- `pct_tre_hen = round(tre_hen / (dung_hen + tre_hen) * 100, 1)`.

---

## Chunk 1: OnTimeResultService — logic thuần (TDD, không DB)

Tạo service với các method PHP thuần, test trước. SQL builders thêm ở Chunk 2.

### Task 1: Khởi tạo service + `classify()`

**Files:**
- Create: `app/Services/OnTimeResultService.php`
- Test: `tests/Unit/OnTimeResultServiceTest.php`

- [ ] **Step 1: Viết test thất bại**

```php
<?php
// tests/Unit/OnTimeResultServiceTest.php
namespace Tests\Unit;

use Tests\TestCase;
use App\Services\OnTimeResultService;

class OnTimeResultServiceTest extends TestCase
{
    protected $service;

    protected function setUp()
    {
        parent::setUp();
        $this->service = new OnTimeResultService();
    }

    /** @test */
    public function classify_returns_chua_tra_when_finish_null()
    {
        $row = (object)['finish_time' => null, 'actual_minutes' => null, 'estimate_duration' => 60];
        $this->assertEquals('chua_tra', $this->service->classify($row));
    }

    /** @test */
    public function classify_returns_bat_thuong_when_actual_negative()
    {
        $row = (object)['finish_time' => 20260601070000, 'actual_minutes' => -5, 'estimate_duration' => 60];
        $this->assertEquals('bat_thuong', $this->service->classify($row));
    }

    /** @test */
    public function classify_returns_dung_hen_when_actual_le_estimate()
    {
        $row = (object)['finish_time' => 20260601070000, 'actual_minutes' => 60, 'estimate_duration' => 60];
        $this->assertEquals('dung_hen', $this->service->classify($row));
    }

    /** @test */
    public function classify_returns_tre_hen_when_actual_gt_estimate()
    {
        $row = (object)['finish_time' => 20260601090000, 'actual_minutes' => 124, 'estimate_duration' => 89];
        $this->assertEquals('tre_hen', $this->service->classify($row));
    }
}
```

- [ ] **Step 2: Chạy test, xác nhận FAIL**

Run: `php vendor/bin/phpunit tests/Unit/OnTimeResultServiceTest.php`
Expected: FAIL — "Class 'App\Services\OnTimeResultService' not found".

- [ ] **Step 3: Tạo service + `classify()`**

```php
<?php
// app/Services/OnTimeResultService.php
namespace App\Services;

use Illuminate\Http\Request;
use Carbon\Carbon;

class OnTimeResultService
{
    /**
     * Phân loại 1 dòng kết quả: chua_tra | bat_thuong | dung_hen | tre_hen
     */
    public function classify($row)
    {
        if (empty($row->finish_time)) {
            return 'chua_tra';
        }
        if ($row->actual_minutes < 0) {
            return 'bat_thuong';
        }
        return ($row->actual_minutes <= $row->estimate_duration) ? 'dung_hen' : 'tre_hen';
    }
}
```

- [ ] **Step 4: Chạy test, xác nhận PASS**

Run: `php vendor/bin/phpunit tests/Unit/OnTimeResultServiceTest.php`
Expected: PASS (4 tests).

- [ ] **Step 5: Commit**

```bash
git add app/Services/OnTimeResultService.php tests/Unit/OnTimeResultServiceTest.php
git commit -m "feat: OnTimeResultService classify() voi unit test"
```

### Task 2: `summarize()` — KPI tổng

**Files:**
- Modify: `app/Services/OnTimeResultService.php`
- Test: `tests/Unit/OnTimeResultServiceTest.php`

- [ ] **Step 1: Viết test thất bại** (thêm vào file test)

```php
    /** @test */
    public function summarize_computes_kpi_totals_and_percentages()
    {
        $rows = [
            (object)['finish_time'=>1,'actual_minutes'=>50,'estimate_duration'=>60,'service_type_id'=>2,'service_type_name'=>'XN','execute_room_id'=>10,'execute_room_name'=>'P.XN','service_id'=>100,'service_name'=>'SH','day_val'=>20260601], // dung_hen
            (object)['finish_time'=>1,'actual_minutes'=>90,'estimate_duration'=>60,'service_type_id'=>2,'service_type_name'=>'XN','execute_room_id'=>10,'execute_room_name'=>'P.XN','service_id'=>100,'service_name'=>'SH','day_val'=>20260601], // tre_hen
            (object)['finish_time'=>1,'actual_minutes'=>120,'estimate_duration'=>60,'service_type_id'=>2,'service_type_name'=>'XN','execute_room_id'=>10,'execute_room_name'=>'P.XN','service_id'=>100,'service_name'=>'SH','day_val'=>20260601], // tre_hen
            (object)['finish_time'=>null,'actual_minutes'=>null,'estimate_duration'=>60,'service_type_id'=>2,'service_type_name'=>'XN','execute_room_id'=>10,'execute_room_name'=>'P.XN','service_id'=>100,'service_name'=>'SH','day_val'=>20260601], // chua_tra
            (object)['finish_time'=>1,'actual_minutes'=>-3,'estimate_duration'=>60,'service_type_id'=>2,'service_type_name'=>'XN','execute_room_id'=>10,'execute_room_name'=>'P.XN','service_id'=>100,'service_name'=>'SH','day_val'=>20260601], // bat_thuong
        ];

        $kpi = $this->service->summarize($rows)['kpi'];

        $this->assertEquals(5, $kpi['tong_co_hen']);
        $this->assertEquals(3, $kpi['da_tra_hop_le']); // 1 dung + 2 tre
        $this->assertEquals(1, $kpi['dung_hen']);
        $this->assertEquals(2, $kpi['tre_hen']);
        $this->assertEquals(1, $kpi['chua_tra']);
        $this->assertEquals(1, $kpi['bat_thuong']);
        $this->assertEquals(33.3, $kpi['pct_dung_hen']); // 1/3
        $this->assertEquals(66.7, $kpi['pct_tre_hen']);  // 2/3
        $this->assertEquals(87, $kpi['tg_tra_tb']);      // (50+90+120)/3 = 86.67 -> 87
    }

    /** @test */
    public function summarize_handles_empty_denominator_without_division_error()
    {
        $rows = [
            (object)['finish_time'=>null,'actual_minutes'=>null,'estimate_duration'=>60,'service_type_id'=>2,'service_type_name'=>'XN','execute_room_id'=>10,'execute_room_name'=>'P.XN','service_id'=>100,'service_name'=>'SH','day_val'=>20260601],
        ];
        $kpi = $this->service->summarize($rows)['kpi'];
        $this->assertEquals(0, $kpi['pct_dung_hen']);
        $this->assertEquals(0, $kpi['pct_tre_hen']);
        $this->assertEquals(0, $kpi['tg_tra_tb']);
    }
```

- [ ] **Step 2: Chạy test, xác nhận FAIL**

Run: `php vendor/bin/phpunit tests/Unit/OnTimeResultServiceTest.php`
Expected: FAIL — "Call to undefined method ...summarize()".

- [ ] **Step 3: Implement `summarize()` (phần KPI; breakdown thêm ở Task 3)**

```php
    /**
     * Tổng hợp toàn bộ chỉ số từ tập base rows.
     * Trả: ['kpi'=>..., 'breakdown_loai_dich_vu'=>..., 'breakdown_phong'=>..., 'breakdown_dich_vu'=>..., 'trend_theo_ngay'=>...]
     */
    public function summarize($rows)
    {
        $dung = $tre = $chua = $bat = 0;
        $sumActual = 0;

        foreach ($rows as $r) {
            switch ($this->classify($r)) {
                case 'dung_hen': $dung++; $sumActual += $r->actual_minutes; break;
                case 'tre_hen':  $tre++;  $sumActual += $r->actual_minutes; break;
                case 'chua_tra': $chua++; break;
                case 'bat_thuong': $bat++; break;
            }
        }

        $hopLe = $dung + $tre;

        $kpi = [
            'tong_co_hen'   => count($rows),
            'da_tra_hop_le' => $hopLe,
            'dung_hen'      => $dung,
            'tre_hen'       => $tre,
            'chua_tra'      => $chua,
            'bat_thuong'    => $bat,
            'pct_dung_hen'  => $hopLe > 0 ? round($dung / $hopLe * 100, 1) : 0,
            'pct_tre_hen'   => $hopLe > 0 ? round($tre / $hopLe * 100, 1) : 0,
            'tg_tra_tb'     => $hopLe > 0 ? round($sumActual / $hopLe) : 0,
        ];

        return [
            'kpi' => $kpi,
            'breakdown_loai_dich_vu' => $this->groupBy($rows, 'service_type_id', 'service_type_name'),
            'breakdown_phong'        => $this->groupBy($rows, 'execute_room_id', 'execute_room_name'),
            'breakdown_dich_vu'      => $this->groupBy($rows, 'service_id', 'service_name'),
            'trend_theo_ngay'        => $this->groupBy($rows, 'day_val', 'day_val'),
        ];
    }
```

> Method `groupBy()` được implement ở Task 3 — tạm thời sẽ lỗi "undefined method" ở các dòng breakdown. Để 2 test KPI pass trước, **tạm comment 4 dòng breakdown** (trả mảng rỗng) rồi mở lại ở Task 3. Ghi chú này có chủ đích để giữ bước nhỏ.

Phiên bản tạm cho Step 3 (chỉ KPI):

```php
        return [
            'kpi' => $kpi,
            'breakdown_loai_dich_vu' => [],
            'breakdown_phong'        => [],
            'breakdown_dich_vu'      => [],
            'trend_theo_ngay'        => [],
        ];
```

- [ ] **Step 4: Chạy test, xác nhận PASS**

Run: `php vendor/bin/phpunit tests/Unit/OnTimeResultServiceTest.php`
Expected: PASS (6 tests).

- [ ] **Step 5: Commit**

```bash
git add app/Services/OnTimeResultService.php tests/Unit/OnTimeResultServiceTest.php
git commit -m "feat: OnTimeResultService summarize() KPI tong hop"
```

### Task 3: `groupBy()` — breakdown theo chiều

**Files:**
- Modify: `app/Services/OnTimeResultService.php`
- Test: `tests/Unit/OnTimeResultServiceTest.php`

- [ ] **Step 1: Viết test thất bại**

```php
    /** @test */
    public function groupby_aggregates_each_group_with_counts_and_percentages()
    {
        $rows = [
            (object)['finish_time'=>1,'actual_minutes'=>50,'estimate_duration'=>60,'service_type_id'=>2,'service_type_name'=>'XN','execute_room_id'=>10,'execute_room_name'=>'P.XN','service_id'=>100,'service_name'=>'SH','day_val'=>20260601],
            (object)['finish_time'=>1,'actual_minutes'=>90,'estimate_duration'=>60,'service_type_id'=>2,'service_type_name'=>'XN','execute_room_id'=>10,'execute_room_name'=>'P.XN','service_id'=>100,'service_name'=>'SH','day_val'=>20260601],
            (object)['finish_time'=>1,'actual_minutes'=>10,'estimate_duration'=>60,'service_type_id'=>3,'service_type_name'=>'CDHA','execute_room_id'=>20,'execute_room_name'=>'P.CT','service_id'=>200,'service_name'=>'CT','day_val'=>20260602],
        ];

        $bk = $this->service->groupBy($rows, 'service_type_id', 'service_type_name');

        // sắp xếp % trễ giảm dần: XN (50% trễ) trước CDHA (0% trễ)
        $this->assertCount(2, $bk);
        $this->assertEquals('XN', $bk[0]['name']);
        $this->assertEquals(2, $bk[0]['tong']);
        $this->assertEquals(1, $bk[0]['dung_hen']);
        $this->assertEquals(1, $bk[0]['tre_hen']);
        $this->assertEquals(50.0, $bk[0]['pct_tre_hen']);
        $this->assertEquals('CDHA', $bk[1]['name']);
    }
```

- [ ] **Step 2: Chạy test, xác nhận FAIL**

Run: `php vendor/bin/phpunit tests/Unit/OnTimeResultServiceTest.php --filter groupby`
Expected: FAIL — "Call to undefined method ...groupBy()".

- [ ] **Step 3: Implement `groupBy()` + mở lại 4 dòng breakdown ở `summarize()`**

```php
    /**
     * Gom nhóm base rows theo (idField, nameField), tính count + % cho từng nhóm.
     * Sắp xếp % trễ giảm dần (riêng trend theo ngày sẽ sort lại ở controller nếu cần).
     */
    public function groupBy($rows, $idField, $nameField)
    {
        $groups = [];
        foreach ($rows as $r) {
            $key = $r->$idField;
            if (!isset($groups[$key])) {
                $groups[$key] = ['id'=>$r->$idField,'name'=>$r->$nameField,'tong'=>0,'dung_hen'=>0,'tre_hen'=>0,'chua_tra'=>0,'bat_thuong'=>0,'_sumActual'=>0];
            }
            $g =& $groups[$key];
            $g['tong']++;
            $cls = $this->classify($r);
            $g[$cls]++;
            if ($cls === 'dung_hen' || $cls === 'tre_hen') {
                $g['_sumActual'] += $r->actual_minutes;
            }
            unset($g);
        }

        $result = [];
        foreach ($groups as $g) {
            $hopLe = $g['dung_hen'] + $g['tre_hen'];
            $g['pct_dung_hen'] = $hopLe > 0 ? round($g['dung_hen']/$hopLe*100, 1) : 0;
            $g['pct_tre_hen']  = $hopLe > 0 ? round($g['tre_hen']/$hopLe*100, 1) : 0;
            $g['tg_tra_tb']    = $hopLe > 0 ? round($g['_sumActual']/$hopLe) : 0;
            unset($g['_sumActual']);
            $result[] = $g;
        }

        usort($result, function($a, $b) {
            return $b['pct_tre_hen'] <=> $a['pct_tre_hen'];
        });

        return $result;
    }
```

Mở lại 4 dòng breakdown trong `summarize()` (thay mảng rỗng bằng lời gọi `groupBy` như bản đầy đủ ở Task 2 Step 3).

- [ ] **Step 4: Chạy test, xác nhận PASS**

Run: `php vendor/bin/phpunit tests/Unit/OnTimeResultServiceTest.php`
Expected: PASS (7 tests).

- [ ] **Step 5: Commit**

```bash
git add app/Services/OnTimeResultService.php tests/Unit/OnTimeResultServiceTest.php
git commit -m "feat: OnTimeResultService groupBy() breakdown theo chieu"
```

---

## Chunk 2: SQL builders + Controller + Routes (integration)

### Task 4: SQL builders trong service

**Files:**
- Modify: `app/Services/OnTimeResultService.php`

> SQL builders không unit-test (cần Oracle thật). Sẽ kiểm chứng qua chạy thực ở Task 8. Viết cẩn thận, dùng bindings.

- [ ] **Step 1: Thêm helper chuẩn hóa filter + WHERE dùng chung**

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

    /** WHERE + bindings dùng chung cho summary & detail */
    protected function commonConditions(Request $request)
    {
        list($from, $to) = $this->normalizeRange($request);
        $conds = [
            "s.estimate_duration IS NOT NULL", "s.estimate_duration <> 0",
            "ss.is_delete = 0", "ss.is_no_execute IS NULL",
            "sr.is_active = 1", "sr.is_delete = 0",
            "sr.intruction_time BETWEEN :from AND :to",
        ];
        $binds = ['from' => $from, 'to' => $to];

        if ($request->filled('execute_room_id')) {
            $conds[] = "ss.tdl_execute_room_id = :room_id";
            $binds['room_id'] = $request->input('execute_room_id');
        }
        if ($request->filled('service_type_id')) {
            $conds[] = "ss.tdl_service_type_id = :service_type_id";
            $binds['service_type_id'] = $request->input('service_type_id');
        }
        if ($request->filled('service_id')) {
            $conds[] = "ss.service_id = :service_id";
            $binds['service_id'] = $request->input('service_id');
        }
        return [$conds, $binds];
    }

    /**
     * Oracle (connection HISPro) trả tên cột VIẾT HOA → chuẩn hóa về lowercase
     * để mọi truy cập $row->field (và DataTables data:'field') đọc đúng.
     * Bắt buộc áp dụng cho MỌI kết quả DB::select trước khi dùng (theo pattern DoctorService).
     */
    public function normalizeRows($rawRows)
    {
        return array_map(function ($row) {
            return (object) array_change_key_case((array) $row, CASE_LOWER);
        }, $rawRows);
    }
```

- [ ] **Step 2: Thêm `buildBaseSqlAndBindings()` (cho summary)**

```php
    /** SQL trả base rows (1 dòng / sere_serv) cho summarize() */
    public function buildBaseSqlAndBindings(Request $request)
    {
        list($conds, $binds) = $this->commonConditions($request);
        $where = implode(' AND ', $conds);

        $sql = "
            SELECT
                ss.tdl_service_type_id            AS service_type_id,
                st.service_type_name              AS service_type_name,
                ss.tdl_execute_room_id            AS execute_room_id,
                er.execute_room_name              AS execute_room_name,
                ss.service_id                     AS service_id,
                s.service_name                    AS service_name,
                TO_NUMBER(SUBSTR(sr.intruction_time,1,8)) AS day_val,
                s.estimate_duration               AS estimate_duration,
                sr.intruction_time                AS intruction_time,
                sr.finish_time                    AS finish_time,
                CASE WHEN sr.finish_time IS NULL THEN NULL
                     ELSE (TO_DATE(sr.finish_time,'YYYYMMDDHH24MISS') - TO_DATE(sr.intruction_time,'YYYYMMDDHH24MISS')) * 24 * 60
                END                               AS actual_minutes
            FROM his_sere_serv ss
            JOIN his_service_req sr ON sr.id = ss.service_req_id
            JOIN his_service s      ON s.id  = ss.service_id
            JOIN his_service_type st ON st.id = ss.tdl_service_type_id
            LEFT JOIN his_execute_room er ON er.room_id = ss.tdl_execute_room_id
            WHERE $where
        ";
        return [$sql, $binds];
    }
```

- [ ] **Step 3: Thêm `buildDetailSqlAndBindings()` (cho DataTables/Export) với predicate `status`**

```php
    /** SQL chi tiết từng dòng cho DataTables & Export; hỗ trợ drill-down status */
    public function buildDetailSqlAndBindings(Request $request)
    {
        list($conds, $binds) = $this->commonConditions($request);

        // predicate cho drill-down trang thai
        $actualExpr = "(TO_DATE(sr.finish_time,'YYYYMMDDHH24MISS') - TO_DATE(sr.intruction_time,'YYYYMMDDHH24MISS')) * 24 * 60";
        switch ($request->input('status')) {
            case 'chua_tra':
                $conds[] = "sr.finish_time IS NULL"; break;
            case 'bat_thuong':
                $conds[] = "sr.finish_time IS NOT NULL AND $actualExpr < 0"; break;
            case 'dung_hen':
                $conds[] = "sr.finish_time IS NOT NULL AND $actualExpr >= 0 AND $actualExpr <= s.estimate_duration"; break;
            case 'tre_hen':
                $conds[] = "sr.finish_time IS NOT NULL AND $actualExpr > s.estimate_duration"; break;
        }
        $where = implode(' AND ', $conds);

        $sql = "
            SELECT
                ss.tdl_treatment_code   AS tdl_treatment_code,
                ss.tdl_patient_name     AS tdl_patient_name,
                er.execute_room_name    AS execute_room_name,
                st.service_type_name    AS service_type_name,
                ss.tdl_service_name     AS service_name,
                sr.intruction_time      AS intruction_time,
                sr.finish_time          AS finish_time,
                s.estimate_duration     AS estimate_duration,
                CASE WHEN sr.finish_time IS NULL THEN NULL ELSE $actualExpr END AS actual_minutes
            FROM his_sere_serv ss
            JOIN his_service_req sr ON sr.id = ss.service_req_id
            JOIN his_service s      ON s.id  = ss.service_id
            JOIN his_service_type st ON st.id = ss.tdl_service_type_id
            LEFT JOIN his_execute_room er ON er.room_id = ss.tdl_execute_room_id
            WHERE $where
        ";
        return [$sql, $binds];
    }
```

- [ ] **Step 4: Thêm `buildRoomsSqlAndBindings()` (dropdown phòng)**

```php
    /** Danh sách phòng thực hiện có dịch vụ thuộc mẫu (distinct) */
    public function buildRoomsSqlAndBindings(Request $request)
    {
        list($conds, $binds) = $this->commonConditions($request);
        $where = implode(' AND ', $conds);
        $sql = "
            SELECT DISTINCT ss.tdl_execute_room_id AS room_id, er.execute_room_name AS execute_room_name
            FROM his_sere_serv ss
            JOIN his_service_req sr ON sr.id = ss.service_req_id
            JOIN his_service s      ON s.id  = ss.service_id
            LEFT JOIN his_execute_room er ON er.room_id = ss.tdl_execute_room_id
            WHERE $where AND er.execute_room_name IS NOT NULL
            ORDER BY er.execute_room_name
        ";
        return [$sql, $binds];
    }
```

> Lưu ý: `commonConditions` thêm `:room_id`/`:service_type_id`/`:service_id` chỉ khi filled; trong `buildRoomsSqlAndBindings` thường không truyền các filter này nên không phát sinh bind thừa. Khi gọi `DB::select` phải truyền `$binds` khớp số placeholder.

- [ ] **Step 5: Commit**

```bash
git add app/Services/OnTimeResultService.php
git commit -m "feat: OnTimeResultService SQL builders (base/detail/rooms)"
```

### Task 5: Controller + routes (feature-tested)

**Files:**
- Create: `app/Http/Controllers/KHTH/OnTimeResultController.php`
- Modify: `routes/web.php` (trong group `khth/`)
- Test: `tests/Feature/OnTimeResultControllerTest.php`

- [ ] **Step 1: Viết feature test thất bại**

```php
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

    protected function getAdminUser() { return factory(\App\User::class)->make(['id' => 1]); }
}
```

> Mock `Mockery::mock(OnTimeResultService::class)` (KHÔNG `makePartial`) + chỉ `shouldReceive('getSummaryData')` → controller không bao giờ chạm DB. `index_renders_view` cần view tồn tại → tạo view stub ở Step 3 trước khi chạy test.

- [ ] **Step 2: Chạy test, xác nhận FAIL**

Run: `php vendor/bin/phpunit tests/Feature/OnTimeResultControllerTest.php`
Expected: FAIL — route/class chưa tồn tại.

- [ ] **Step 3: Thêm method `getSummaryData()` vào service + tạo Controller**

Thêm vào `OnTimeResultService` (bọc select + summarize để controller mỏng & dễ mock):

```php
    /** Lấy base rows từ DB rồi tổng hợp. */
    public function getSummaryData(Request $request)
    {
        list($sql, $binds) = $this->buildBaseSqlAndBindings($request);
        $rows = \DB::connection('HISPro')->select(\DB::raw($sql), $binds);
        return $this->summarize($this->normalizeRows($rows));
    }
```

> **Hiệu năng:** `getSummaryData` kéo toàn bộ base rows về PHP để gom nhóm (giống pattern `MedicalCenterDashboard`). Tuần ~25k dòng là chấp nhận được; khoảng ngày rất rộng (vài tháng) có thể nặng RAM/transfer — nếu cần, sau này tối ưu bằng GROUP BY trong SQL. Ghi nhận, KHÔNG tối ưu sớm (YAGNI).

Controller:

```php
<?php
// app/Http/Controllers/KHTH/OnTimeResultController.php
namespace App\Http\Controllers\KHTH;

use App\Http\Controllers\Controller;
use App\Services\OnTimeResultService;
use App\Exports\OnTimeResultExport;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;
use DB;

class OnTimeResultController extends Controller
{
    protected $service;

    public function __construct(OnTimeResultService $service)
    {
        $this->service = $service;
    }

    public function index()
    {
        return view('khth.on-time-result');
    }

    public function getSummary(Request $request)
    {
        return response()->json($this->service->getSummaryData($request));
    }

    public function rooms(Request $request)
    {
        list($sql, $binds) = $this->service->buildRoomsSqlAndBindings($request);
        return response()->json(DB::connection('HISPro')->select(DB::raw($sql), $binds));
    }

    public function fetch(Request $request)
    {
        list($sql, $binds) = $this->service->buildDetailSqlAndBindings($request);
        $results = $this->service->normalizeRows(DB::connection('HISPro')->select(DB::raw($sql), $binds));

        $service = $this->service;
        return DataTables::of($results)
            ->editColumn('intruction_time', function ($r) { return strtodatetime($r->intruction_time); })
            ->editColumn('finish_time', function ($r) { return $r->finish_time ? strtodatetime($r->finish_time) : ''; })
            ->addColumn('actual_minutes_fmt', function ($r) { return is_null($r->actual_minutes) ? '' : round($r->actual_minutes) . ' phút'; })
            ->addColumn('chenh_lech', function ($r) { return is_null($r->actual_minutes) ? '' : round($r->actual_minutes - $r->estimate_duration) . ' phút'; })
            ->addColumn('trang_thai', function ($r) use ($service) {
                $map = [
                    'dung_hen'  => '<span class="label label-success">Đúng hẹn</span>',
                    'tre_hen'   => '<span class="label label-danger">Trễ hẹn</span>',
                    'chua_tra'  => '<span class="label label-warning">Chưa trả KQ</span>',
                    'bat_thuong'=> '<span class="label label-default">Bất thường</span>',
                ];
                return $map[$service->classify($r)] ?? '';
            })
            ->rawColumns(['trang_thai'])
            ->make(true);
    }

    public function export(Request $request)
    {
        $fileName = 'tra_kq_dung_hen_' . Carbon::now()->format('YmdHis') . '.xlsx';
        return Excel::download(new OnTimeResultExport($request->all()), $fileName);
    }
}
```

Routes (trong group `khth/` đã có ở `routes/web.php`):

```php
Route::get('on-time-result-index', 'KHTH\OnTimeResultController@index')->name('khth.on-time-result-index');
Route::get('on-time-result-index/summary', 'KHTH\OnTimeResultController@getSummary')->name('khth.on-time-result-summary');
Route::get('on-time-result-index/fetch', 'KHTH\OnTimeResultController@fetch')->name('khth.on-time-result-fetch');
Route::get('on-time-result-index/export', 'KHTH\OnTimeResultController@export')->name('khth.on-time-result-export');
Route::get('on-time-result-index/rooms', 'KHTH\OnTimeResultController@rooms')->name('khth.on-time-result-rooms');
```

**Tạo view stub** (bắt buộc để `index_renders_view` pass; Task 7 sẽ thay bằng bản đầy đủ):

```blade
{{-- resources/views/khth/on-time-result.blade.php (STUB - hoàn thiện ở Task 7) --}}
@extends('adminlte::page')
@section('title', 'Tỷ lệ trả KQ đúng hẹn')
@section('content')
@stop
```

- [ ] **Step 4: Chạy test, xác nhận PASS**

Run: `php vendor/bin/phpunit tests/Feature/OnTimeResultControllerTest.php`
Expected: PASS (2 tests).

- [ ] **Step 5: Commit**

```bash
git add app/Http/Controllers/KHTH/OnTimeResultController.php app/Services/OnTimeResultService.php routes/web.php tests/Feature/OnTimeResultControllerTest.php resources/views/khth/on-time-result.blade.php
git commit -m "feat: OnTimeResultController + routes (index/summary/fetch/export/rooms)"
```

### Task 6: Export Excel

**Files:**
- Create: `app/Exports/OnTimeResultExport.php`

- [ ] **Step 1: Tạo Export (theo pattern KhaoSatExport)**

```php
<?php
// app/Exports/OnTimeResultExport.php
namespace App\Exports;

use Illuminate\Http\Request;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Illuminate\Support\Collection;
use App\Services\OnTimeResultService;
use DB;

class OnTimeResultExport implements FromCollection, WithHeadings, WithMapping
{
    protected $filters;
    protected $service;
    protected $rowNumber = 0;

    public function __construct(array $filters)
    {
        $this->filters = $filters;
        $this->service = new OnTimeResultService();
    }

    public function collection()
    {
        $request = new Request($this->filters);
        list($sql, $binds) = $this->service->buildDetailSqlAndBindings($request);
        $rows = $this->service->normalizeRows(DB::connection('HISPro')->select(DB::raw($sql), $binds));
        return new Collection($rows);
    }

    public function headings(): array
    {
        return ['STT','Mã ĐT','Họ tên BN','Khoa/Phòng TH','Loại DV','Tên DV','Giờ chỉ định','Giờ trả KQ','TG thực tế (phút)','TG hẹn (phút)','Chênh lệch (phút)','Trạng thái'];
    }

    public function map($r): array
    {
        $this->rowNumber++;
        $statusLabel = [
            'dung_hen'=>'Đúng hẹn','tre_hen'=>'Trễ hẹn','chua_tra'=>'Chưa trả KQ','bat_thuong'=>'Bất thường',
        ];
        $cls = $this->service->classify($r);
        return [
            $this->rowNumber,
            $r->tdl_treatment_code,
            $r->tdl_patient_name,
            $r->execute_room_name,
            $r->service_type_name,
            $r->service_name,
            strtodatetime($r->intruction_time),
            $r->finish_time ? strtodatetime($r->finish_time) : '',
            is_null($r->actual_minutes) ? '' : round($r->actual_minutes),
            $r->estimate_duration,
            is_null($r->actual_minutes) ? '' : round($r->actual_minutes - $r->estimate_duration),
            $statusLabel[$cls] ?? '',
        ];
    }
}
```

- [ ] **Step 2: Smoke test cú pháp**

Run: `php -l app/Exports/OnTimeResultExport.php`
Expected: "No syntax errors detected".

- [ ] **Step 3: Commit**

```bash
git add app/Exports/OnTimeResultExport.php
git commit -m "feat: OnTimeResultExport xuat Excel bang chi tiet"
```

---

## Chunk 3: View, Menu, kiểm chứng dữ liệu thật

### Task 7: View + partial filter

**Files:**
- Create: `resources/views/khth/on-time-result.blade.php`
- Create: `resources/views/khth/partials/search-on-time-result.blade.php`

- [ ] **Step 1: Partial bộ lọc** (date range + phòng + loại DV; dùng partial chung `date_range`, `load_data_button` như khảo sát)

```blade
{{-- resources/views/khth/partials/search-on-time-result.blade.php --}}
<div class="panel panel-default">
    <div class="panel-body">
        @include('partials.date_range')
        <div class="col-sm-12">
            <div class="form-group row">
                <div class="col-sm-4">
                    <label for="execute_room_id">Khoa/Phòng thực hiện</label>
                    <select id="execute_room_id" class="form-control select2"><option value="">-- Tất cả --</option></select>
                </div>
                <div class="col-sm-4">
                    <label for="service_type_id">Loại dịch vụ</label>
                    <select id="service_type_id" class="form-control select2">
                        <option value="">-- Tất cả --</option>
                        <option value="2">Xét nghiệm</option>
                        <option value="3">Chẩn đoán hình ảnh</option>
                        <option value="5">Thăm dò chức năng</option>
                        <option value="10">Siêu âm</option>
                    </select>
                </div>
            </div>
        </div>
        <input type="hidden" id="drill_service_id" value="">
        <input type="hidden" id="drill_status" value="">
        @include('partials.load_data_button')
    </div>
</div>
```

> Map `tdl_service_type_id` đã **xác minh thực tế**: 2=Xét nghiệm, 3=Chẩn đoán hình ảnh, 5=Thăm dò chức năng, 10=Siêu âm (đây là `his_service_type.id`, KHÁC `service_req_type_id`). Task 8 chỉ tái xác nhận.

- [ ] **Step 2: View chính** — card KPI + 4 chart/bảng + DataTables. Khung:

```blade
@extends('adminlte::page')
@section('title', 'Tỷ lệ trả KQ đúng hẹn')
@section('content_header')<h1>Tỷ lệ trả kết quả đúng hẹn</h1>@stop
@section('content')
@include('khth.partials.search-on-time-result')

{{-- Card KPI --}}
<div class="row" id="kpi-cards">
  <div class="col-md-2"><div class="info-box"><span class="info-box-icon bg-aqua"><i class="fa fa-list"></i></span><div class="info-box-content"><span class="info-box-text">Tổng có hẹn</span><span class="info-box-number" id="kpi-tong">0</span></div></div></div>
  <div class="col-md-2"><div class="info-box"><span class="info-box-icon bg-green"><i class="fa fa-check"></i></span><div class="info-box-content"><span class="info-box-text">% Đúng hẹn</span><span class="info-box-number" id="kpi-pct-dung">0%</span></div></div></div>
  <div class="col-md-2"><div class="info-box"><span class="info-box-icon bg-red"><i class="fa fa-times"></i></span><div class="info-box-content"><span class="info-box-text">% Trễ hẹn</span><span class="info-box-number" id="kpi-pct-tre">0%</span></div></div></div>
  <div class="col-md-2"><div class="info-box"><span class="info-box-icon bg-yellow"><i class="fa fa-clock-o"></i></span><div class="info-box-content"><span class="info-box-text">Chưa trả KQ</span><span class="info-box-number" id="kpi-chua">0</span></div></div></div>
  <div class="col-md-2"><div class="info-box"><span class="info-box-icon bg-gray"><i class="fa fa-exclamation"></i></span><div class="info-box-content"><span class="info-box-text">Bất thường</span><span class="info-box-number" id="kpi-bat">0</span></div></div></div>
  <div class="col-md-2"><div class="info-box"><span class="info-box-icon bg-purple"><i class="fa fa-hourglass-half"></i></span><div class="info-box-content"><span class="info-box-text">TG trả KQ TB</span><span class="info-box-number" id="kpi-tgtb">0</span></div></div></div>
</div>

{{-- Tong hop --}}
<div class="row">
  <div class="col-md-6"><div class="box"><div class="box-header"><h3 class="box-title">Theo loại dịch vụ</h3></div><div class="box-body"><canvas id="chart-loai-dv" height="120"></canvas><table class="table table-bordered" id="tbl-loai-dv"></table></div></div></div>
  <div class="col-md-6"><div class="box"><div class="box-header"><h3 class="box-title">Xu hướng % đúng hẹn theo ngày</h3></div><div class="box-body"><canvas id="chart-trend" height="120"></canvas></div></div></div>
</div>
<div class="row">
  <div class="col-md-6"><div class="box"><div class="box-header"><h3 class="box-title">Theo khoa/phòng thực hiện (xếp % trễ)</h3></div><div class="box-body table-responsive"><table class="table table-bordered" id="tbl-phong"></table></div></div></div>
  <div class="col-md-6"><div class="box"><div class="box-header"><h3 class="box-title">Top dịch vụ trễ hẹn</h3></div><div class="box-body table-responsive"><table class="table table-bordered" id="tbl-dich-vu"></table></div></div></div>
</div>

{{-- Chi tiet --}}
<div class="box">
  <div class="box-header"><h3 class="box-title">Chi tiết</h3><button id="export_xlsx" class="btn btn-success btn-sm pull-right"><i class="fa fa-file-excel-o"></i> Xuất Excel</button></div>
  <div class="box-body table-responsive">
    <table id="detail-table" class="table table-hover" width="100%">
      <thead><tr>
        <th>Mã ĐT</th><th>Họ tên BN</th><th>Khoa/Phòng TH</th><th>Loại DV</th><th>Tên DV</th>
        <th>Giờ chỉ định</th><th>Giờ trả KQ</th><th>TG thực tế</th><th>TG hẹn</th><th>Chênh lệch</th><th>Trạng thái</th>
      </tr></thead>
    </table>
  </div>
</div>
@stop
```

- [ ] **Step 3: JS** (`@push('after-scripts')`) — nạp Chart.js, gọi `summary`, vẽ chart/bảng, init DataTables, drill-down, export. Điểm chính:

```blade
@push('after-scripts')
@stack('after-scripts-date-range')
@stack('after-scripts-load-data-button')
<script src="{{ asset('vendor/adminlte/bower_components/Chart.js/Chart.min.js') }}"></script>
<script>
let chartLoai=null, chartTrend=null, detailTable=null;

// Khoảng ngày hiện hành lưu lại để summary/detail/export dùng chung
let curFrom=null, curTo=null;
function getRange(){ var d=$('#date_range').data('daterangepicker'); return {from:d.startDate.format('YYYY-MM-DD HH:mm:ss'), to:d.endDate.format('YYYY-MM-DD HH:mm:ss')}; }
function baseFilters(){ return {date_from:curFrom, date_to:curTo, execute_room_id:$('#execute_room_id').val(), service_type_id:$('#service_type_id').val(), service_id:$('#drill_service_id').val(), status:$('#drill_status').val()}; }

// CONVENTION BẮT BUỘC: partial load_data_button tự gọi fetchData(startDate,endDate)
// khi trang tải xong và mỗi lần bấm nút #load_data_button.
function fetchData(startDate, endDate){
  curFrom=startDate; curTo=endDate;
  $('#drill_service_id').val(''); $('#drill_status').val(''); // reset drill khi tải lại từ nút
  reloadAll();
}

function loadSummary(){
  $.getJSON("{{ route('khth.on-time-result-summary') }}", baseFilters(), function(res){
    var k=res.kpi;
    $('#kpi-tong').text(k.tong_co_hen); $('#kpi-pct-dung').text(k.pct_dung_hen+'%'); $('#kpi-pct-tre').text(k.pct_tre_hen+'%');
    $('#kpi-chua').text(k.chua_tra); $('#kpi-bat').text(k.bat_thuong); $('#kpi-tgtb').text(k.tg_tra_tb+' phút');
    renderBreakdownTable('#tbl-loai-dv', res.breakdown_loai_dich_vu, 'service_type_id');
    renderBreakdownTable('#tbl-phong', res.breakdown_phong, 'execute_room_id');
    renderBreakdownTable('#tbl-dich-vu', res.breakdown_dich_vu, 'service_id');
    renderLoaiChart(res.breakdown_loai_dich_vu);
    renderTrendChart(res.trend_theo_ngay);
  });
}

function renderBreakdownTable(sel, rows, drillField){
  var html='<thead><tr><th>Nhóm</th><th>Tổng</th><th>Đúng</th><th>Trễ</th><th>% Trễ</th></tr></thead><tbody>';
  rows.forEach(function(g){
    html+='<tr class="drill" style="cursor:pointer" data-field="'+drillField+'" data-id="'+g.id+'"><td>'+(g.name||'(trống)')+'</td><td>'+g.tong+'</td><td>'+g.dung_hen+'</td><td>'+g.tre_hen+'</td><td>'+g.pct_tre_hen+'%</td></tr>';
  });
  $(sel).html(html+'</tbody>');
}

function renderLoaiChart(rows){
  var ctx=document.getElementById('chart-loai-dv').getContext('2d');
  if(chartLoai) chartLoai.destroy();
  chartLoai=new Chart(ctx,{type:'bar',data:{labels:rows.map(r=>r.name),datasets:[{label:'% Đúng hẹn',backgroundColor:'#00a65a',data:rows.map(r=>r.pct_dung_hen)},{label:'% Trễ hẹn',backgroundColor:'#dd4b39',data:rows.map(r=>r.pct_tre_hen)}]},options:{scales:{yAxes:[{ticks:{beginAtZero:true,max:100}}]}}});
}
function renderTrendChart(rows){
  rows.sort((a,b)=>a.id-b.id);
  var ctx=document.getElementById('chart-trend').getContext('2d');
  if(chartTrend) chartTrend.destroy();
  chartTrend=new Chart(ctx,{type:'line',data:{labels:rows.map(r=>String(r.id).substr(6,2)+'/'+String(r.id).substr(4,2)),datasets:[{label:'% Đúng hẹn',borderColor:'#00a65a',fill:false,data:rows.map(r=>r.pct_dung_hen)}]},options:{scales:{yAxes:[{ticks:{beginAtZero:true,max:100}}]}}});
}

function loadDetail(){
  var f=baseFilters();
  if(detailTable){ detailTable.ajax.reload(); return; }
  detailTable=$('#detail-table').DataTable({
    processing:true, serverSide:true, destroy:true, scrollX:true,
    ajax:{ url:"{{ route('khth.on-time-result-fetch') }}", data:function(d){ Object.assign(d, baseFilters()); } },
    columns:[
      {data:'tdl_treatment_code'},{data:'tdl_patient_name'},{data:'execute_room_name'},{data:'service_type_name'},{data:'service_name'},
      {data:'intruction_time'},{data:'finish_time'},{data:'actual_minutes_fmt'},{data:'estimate_duration'},{data:'chenh_lech'},{data:'trang_thai'}
    ]
  });
}

function reloadAll(){ loadSummary(); loadDetail(); }

$(function(){
  $('.select2').select2({width:'100%'});
  // nap dropdown phong (theo khoang ngay mac dinh cua daterangepicker)
  var r0=getRange();
  $.getJSON("{{ route('khth.on-time-result-rooms') }}", {date_from:r0.from, date_to:r0.to}, function(data){
    data.forEach(function(it){ $('#execute_room_id').append('<option value="'+it.room_id+'">'+it.execute_room_name+'</option>'); });
  });

  // Nút "Tải dữ liệu" KHÔNG bind ở đây — partial load_data_button tự gọi fetchData().

  // drill-down tu bang tong hop
  $(document).on('click', '.drill', function(){
    var field=$(this).data('field'), id=$(this).data('id');
    if(field==='service_type_id') $('#service_type_id').val(id).trigger('change');
    if(field==='execute_room_id') $('#execute_room_id').val(id).trigger('change');
    if(field==='service_id') $('#drill_service_id').val(id);
    reloadAll();
  });

  // export theo filter hien hanh
  $('#export_xlsx').click(function(){
    window.location.href="{{ route('khth.on-time-result-export') }}?"+$.param(baseFilters());
  });
});
</script>
@endpush
```

> **Đã xác minh:** Partial `partials/load_data_button` dùng nút id `#load_data_button`, và tự động gọi hàm toàn cục `fetchData(startDate, endDate)` (định dạng `YYYY-MM-DD HH:mm:ss`) cả khi trang tải xong lẫn khi bấm nút. Vì vậy view PHẢI định nghĩa `fetchData(startDate,endDate)` (đã có ở Step 3) — KHÔNG tự bind nút. Helper `strtodatetime()` tồn tại ở `app/Http/Controllers/app-helpers.php`.
> **Cần xác nhận khi triển khai:** đường dẫn `Chart.min.js` — dùng đúng path mà `resources/views/khth/dashboard.blade.php` đang nạp (kiểm tra `public/vendor/adminlte/...`); nếu khác, sửa thẻ `<script src>` cho khớp.

- [ ] **Step 4: Smoke test view compile**

Run: `php artisan view:clear` rồi truy cập route (Task 8). Tạm thời kiểm tra Blade không lỗi cú pháp bằng cách mở trang.

- [ ] **Step 5: Commit**

```bash
git add resources/views/khth/on-time-result.blade.php resources/views/khth/partials/search-on-time-result.blade.php
git commit -m "feat: view on-time-result (KPI, chart, breakdown, datatable, drill-down)"
```

### Task 8: Kiểm chứng dữ liệu thật + map loại dịch vụ

**Files:** (không sửa code trừ khi phát hiện lệch)

- [ ] **Step 1: Xác nhận map id↔tên loại dịch vụ có estimate_duration** (qua sqlcl MCP hoặc tinker)

```sql
SELECT DISTINCT ss.tdl_service_type_id, st.service_type_name
FROM his_sere_serv ss JOIN his_service s ON s.id=ss.service_id JOIN his_service_type st ON st.id=ss.tdl_service_type_id
WHERE s.estimate_duration IS NOT NULL AND s.estimate_duration<>0 ORDER BY 1;
```
Đối chiếu với option `service_type_id` trong partial (Task 7 Step 1). Nếu lệch → sửa option.

- [ ] **Step 2: Đối chiếu KPI tổng với query tham chiếu** (tuần 01–07/06/2026): mở trang với khoảng ngày tương ứng, so `dung_hen`/`tre_hen`/`chua_tra` với số liệu spec mục 9 (8.206 / 17.052 / 117). Cho phép sai lệch nhỏ do điều kiện `is_no_execute IS NULL` mới thêm.

- [ ] **Step 3: Kiểm tra index** trên `his_service_req.intruction_time`:

```sql
SELECT index_name, column_name FROM all_ind_columns WHERE table_name='HIS_SERVICE_REQ' AND column_name='INTRUCTION_TIME';
```
Nếu không có index và truy vấn chậm → ghi nhận, đề xuất (KHÔNG tự tạo index trên DB production).

- [ ] **Step 4: Test drill-down & export thủ công**: click 1 nhóm ở mỗi bảng tổng hợp → bảng chi tiết lọc đúng; bấm Xuất Excel → file tải về đúng cột & đúng filter.

- [ ] **Step 5: Commit** (nếu có sửa option/map)

```bash
git add -A && git commit -m "fix: dong bo map loai dich vu va kiem chung du lieu on-time-result"
```

### Task 9: Menu

**Files:**
- Modify: `config/adminlte.php` (submenu nhóm KHTH)

- [ ] **Step 1: Thêm mục menu** (đặt cạnh các mục KHTH hiện có, định dạng AdminLTE 2):

```php
[
    'text'      => 'Tỷ lệ trả KQ đúng hẹn',
    'icon'      => 'clock-o',
    'checkrole' => 'administrator',
    'route'     => 'khth.on-time-result-index',
    'active'    => ['khth/on-time-result-index*'],
],
```

- [ ] **Step 2: Kiểm tra** menu hiển thị đúng, click vào ra trang, phân quyền administrator hoạt động.

- [ ] **Step 3: Commit**

```bash
git add config/adminlte.php
git commit -m "feat: them menu KHTH 'Ty le tra KQ dung hen'"
```

---

## Hoàn tất

- [ ] **Chạy toàn bộ test:** `php vendor/bin/phpunit tests/Unit/OnTimeResultServiceTest.php tests/Feature/OnTimeResultControllerTest.php` → tất cả PASS.
- [ ] **Cập nhật `readme.md`** mục ngày mới: "Bổ sung dashboard Tỷ lệ trả kết quả đúng hẹn (KHTH)".
- [ ] **Verify** bằng @superpowers:verification-before-completion trước khi tuyên bố hoàn thành.
