# Home Chart "Tình trạng giường theo khoa" Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Thêm vào Home dashboard 1 biểu đồ cột nhóm (Highcharts) theo dõi tình trạng giường theo khoa: số giường đã sử dụng và còn trống + công suất %, là trạng thái hiện tại (real-time snapshot), không lọc theo ngày.

**Architecture:** Logic tổng hợp thuần tách thành static method `HomeController::buildBedStatusByDepartmentSeries($rows)` (unit-test được, không cần DB). Controller `fetchBedStatusByDepartment` chạy 1 query raw CTE (`his_bed`/`his_treatment_bed_room` → khoa), không dùng start/end. Frontend tích hợp đúng pattern module hóa: `DAPI` (api.js) → `renderBedStatusByDepartment` đăng ký trong `DCharts.renderAll` (charts.js); auto-refresh tự cập nhật snapshot.

**Tech Stack:** Laravel 5.5, yajra/laravel-oci8 (Oracle), Highcharts, AdminLTE 2, jQuery, PHPUnit 6.

**Spec:** `docs/superpowers/specs/2026-06-10-home-bed-status-by-department-chart-design.md`

---

## File Structure

| File | Trách nhiệm | Thao tác |
|---|---|---|
| `app/Http/Controllers/HomeController.php` | static method tổng hợp thuần + `fetchBedStatusByDepartment` + private `bedStatusByDepartment` (raw CTE) | Modify |
| `tests/Unit/HomeBedStatusByDepartmentTest.php` | Unit test cho static method | Create |
| `routes/web.php` | route `fetch-bed-status-by-department` (group `checkrole:dashboard`) | Modify |
| `resources/views/home.blade.php` | container box + `DASHBOARD_CFG.routes` | Modify |
| `public/js/dashboard/api.js` | `DAPI.bedStatusByDepartment` | Modify |
| `public/js/dashboard/charts.js` | `renderBedStatusByDepartment` + đăng ký `renderAll` | Modify |

> Không sửa `init.js` / `autorefresh.js` (không có tương tác; `renderAll` tự bao gồm chart mới).

**Quy ước đã xác minh:** oci8 trả key cột **lowercase** (đọc `$r->tong_giuong`, `$r->dang_dung`, `$r->department_name`). `DB::connection('HISPro')->select(DB::raw($sql))` chạy raw SQL (kể cả CTE `WITH`). `currentDate` không dùng ở đây (snapshot). Routes fetch chart Home nằm trong `Route::group(['middleware'=>['checkrole:dashboard']])`. `numeral` đã load toàn cục.

---

## Chunk 1: Biểu đồ tình trạng giường theo khoa

### Task 1: Static method tổng hợp thuần (TDD)

**Files:**
- Modify: `app/Http/Controllers/HomeController.php` (thêm 1 public static method)
- Test: `tests/Unit/HomeBedStatusByDepartmentTest.php`

- [ ] **Step 1: Viết test thất bại**

```php
<?php
// tests/Unit/HomeBedStatusByDepartmentTest.php
namespace Tests\Unit;

use Tests\TestCase;
use App\Http\Controllers\HomeController;

class HomeBedStatusByDepartmentTest extends TestCase
{
    /** @test */
    public function it_builds_used_free_utilization_and_total()
    {
        // Mỗi $row = 1 khoa: department_name, tong_giuong, dang_dung
        $rows = [
            (object)['department_name' => 'Khoa Nhi CS1',    'tong_giuong' => 168, 'dang_dung' => 72],
            (object)['department_name' => 'Khoa Nội TH CS1', 'tong_giuong' => 100, 'dang_dung' => 90],
            (object)['department_name' => 'Khoa Quá tải',     'tong_giuong' => 10,  'dang_dung' => 12], // overcrowd: free kẹp 0
        ];

        $res = HomeController::buildBedStatusByDepartmentSeries($rows);

        $this->assertEquals(['Khoa Nhi CS1','Khoa Nội TH CS1','Khoa Quá tải'], $res['categories']);
        $this->assertEquals([72, 90, 12], $res['used']);
        $this->assertEquals([96, 10, 0], $res['free']);          // con_trong = max(0, tong - dang)
        $this->assertEquals([43, 90, 120], $res['utilization']); // round(dang/tong*100); 72/168=42.857->43; 12/10=120

        $this->assertEquals(278, $res['total']['tong']);        // 168+100+10
        $this->assertEquals(174, $res['total']['dang_dung']);   // 72+90+12
        $this->assertEquals(106, $res['total']['con_trong']);   // 96+10+0
        $this->assertEquals(63, $res['total']['cong_suat']);    // round(174/278*100)=62.59->63
    }

    /** @test */
    public function it_handles_empty_input()
    {
        $res = HomeController::buildBedStatusByDepartmentSeries([]);
        $this->assertEquals([], $res['categories']);
        $this->assertEquals([], $res['used']);
        $this->assertEquals([], $res['free']);
        $this->assertEquals(0, $res['total']['tong']);
        $this->assertEquals(0, $res['total']['cong_suat']);
    }
}
```

- [ ] **Step 2: Chạy test, xác nhận FAIL**

Run: `php vendor/bin/phpunit tests/Unit/HomeBedStatusByDepartmentTest.php`
Expected: FAIL — "Call to undefined method ...buildBedStatusByDepartmentSeries()".

- [ ] **Step 3: Thêm static method vào `HomeController`** (đặt gần các method nội trú/giường, vd ngay trên `fetchBedStatusByDepartment` sẽ tạo ở Task 2)

```php
    /**
     * Tổng hợp dữ liệu biểu đồ "tình trạng giường theo khoa" (snapshot hiện tại).
     * @param  iterable $rows  Mỗi phần tử là 1 khoa: ->department_name, ->tong_giuong, ->dang_dung
     * @return array{categories: array, used: array, free: array, utilization: array, total: array}
     *   Giữ thứ tự tự nhiên từ rows (không sắp xếp). free = max(0, tong - dang).
     */
    public static function buildBedStatusByDepartmentSeries($rows)
    {
        $categories = [];
        $used = [];
        $free = [];
        $utilization = [];
        $sumTong = 0;
        $sumDang = 0;
        $sumFree = 0;

        foreach ($rows as $r) {
            $tong = (int) $r->tong_giuong;
            $dang = (int) $r->dang_dung;
            $tr   = max(0, $tong - $dang);
            $util = $tong > 0 ? (int) round($dang / $tong * 100) : 0;

            $categories[]  = $r->department_name;
            $used[]        = $dang;
            $free[]        = $tr;
            $utilization[] = $util;

            $sumTong += $tong;
            $sumDang += $dang;
            $sumFree += $tr;
        }

        return [
            'categories'  => $categories,
            'used'        => $used,
            'free'        => $free,
            'utilization' => $utilization,
            'total'       => [
                'tong'      => $sumTong,
                'dang_dung' => $sumDang,
                'con_trong' => $sumFree,
                'cong_suat' => $sumTong > 0 ? (int) round($sumDang / $sumTong * 100) : 0,
            ],
        ];
    }
```

- [ ] **Step 4: Chạy test, xác nhận PASS**

Run: `php vendor/bin/phpunit tests/Unit/HomeBedStatusByDepartmentTest.php`
Expected: PASS (2 tests).

- [ ] **Step 5: Commit**

```bash
git add app/Http/Controllers/HomeController.php tests/Unit/HomeBedStatusByDepartmentTest.php
git commit -m "feat: HomeController::buildBedStatusByDepartmentSeries() + unit test"
```

### Task 2: Controller + query raw + route + cấu hình route

**Files:**
- Modify: `app/Http/Controllers/HomeController.php`
- Modify: `routes/web.php`
- Modify: `resources/views/home.blade.php` (map `DASHBOARD_CFG.routes`)

- [ ] **Step 1: Thêm method `fetchBedStatusByDepartment` + private `bedStatusByDepartment` vào `HomeController`**

```php
    /**
     * API biểu đồ Home: tình trạng giường theo khoa (snapshot hiện tại, không lọc ngày).
     */
    public function fetchBedStatusByDepartment(Request $request)
    {
        if (!$request->ajax()) {
            return redirect()->route('home');
        }

        $rows = $this->bedStatusByDepartment();

        return response()->json(self::buildBedStatusByDepartmentSeries($rows));
    }

    private function bedStatusByDepartment()
    {
        $sql = "
            WITH tong AS (
                SELECT r.department_id, COUNT(*) tong_giuong
                FROM his_bed b
                JOIN his_bed_room br ON br.id = b.bed_room_id
                JOIN his_room r ON r.id = br.room_id
                WHERE b.is_active=1 AND b.is_delete=0 AND br.is_active=1 AND br.is_delete=0 AND r.is_active=1
                GROUP BY r.department_id
            ),
            dang AS (
                SELECT r.department_id, COUNT(*) dang_dung
                FROM his_treatment_bed_room tbr
                JOIN his_bed_room br ON br.id = tbr.bed_room_id
                JOIN his_room r ON r.id = br.room_id
                JOIN his_treatment t ON t.id = tbr.treatment_id
                LEFT JOIN his_co_treatment ct ON ct.id = tbr.co_treatment_id
                WHERE tbr.remove_time IS NULL AND tbr.is_delete=0 AND ct.id IS NULL
                  AND t.tdl_treatment_type_id IN (3,4) AND t.out_time IS NULL
                GROUP BY r.department_id
            )
            SELECT d.department_name,
                   tong.tong_giuong AS tong_giuong,
                   NVL(dang.dang_dung, 0) AS dang_dung
            FROM tong
            JOIN his_department d ON d.id = tong.department_id
            LEFT JOIN dang ON dang.department_id = tong.department_id
        ";

        return DB::connection('HISPro')->select(DB::raw($sql));
    }
```

> `DB` đã `use DB;` ở đầu HomeController. Query KHÔNG có bind (không lọc ngày). Static method gọi `self::`.

- [ ] **Step 2: Thêm route** — đặt **bên trong** group `Route::group(['middleware' => ['checkrole:dashboard']], ...)`, cạnh các route `fetch-*` chart Home (vd sau `fetch-doanhthu-by-department`):

```php
Route::get('fetch-bed-status-by-department', 'HomeController@fetchBedStatusByDepartment')->name('fetch-bed-status-by-department');
```

- [ ] **Step 3: Thêm route vào map `DASHBOARD_CFG.routes` trong `resources/views/home.blade.php`** (trong khối `window.DASHBOARD_CFG = { ... routes: { ... } }`, cạnh `fetchDoanhthuByDepartment`):

```js
        fetchBedStatusByDepartment: "{{ route('fetch-bed-status-by-department') }}",
```

- [ ] **Step 4: Smoke test cú pháp + route**

Run: `php -l app/Http/Controllers/HomeController.php`
Expected: "No syntax errors detected".
Run: `php artisan route:list --name=fetch-bed-status-by-department` (nếu chạy được)
Expected: thấy route.

- [ ] **Step 5: Commit**

```bash
git add app/Http/Controllers/HomeController.php routes/web.php resources/views/home.blade.php
git commit -m "feat: route + controller fetchBedStatusByDepartment (Home)"
```

### Task 3: Container biểu đồ (Blade)

**Files:**
- Modify: `resources/views/home.blade.php`

- [ ] **Step 1: Thêm box biểu đồ** — đặt ngay **sau** hàng chứa `chart_noitru`. Tìm khối:

```blade
                    <div class="chart tab-pane active" style="position: relative;">
                        <div id="chart_noitru" style="width:100%; height:500px;"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
```
và chèn **ngay sau** dòng `</div>` đóng `<div class="row">` đó (tức trước `@if(config("organization.is_bieudo_dieutringoaitru"))`):

```blade
    <div class="row">
        <div class="col-lg-12 connectedSortable">
            <div class="nav-tabs-custom text-center">
                <div class="tab-content no-padding" style="padding:10px;">
                    <div id="chart_bed_status_by_department" style="width:100%; height:420px;"></div>
                </div>
            </div>
        </div>
    </div>
```

- [ ] **Step 2: Kiểm tra Blade compile**

Run: `php artisan view:clear`
Expected: "Compiled views cleared!" (không lỗi).

- [ ] **Step 3: Commit**

```bash
git add resources/views/home.blade.php
git commit -m "feat: container bieu do tinh trang giuong theo khoa"
```

### Task 4: `api.js` — thêm `DAPI.bedStatusByDepartment`

**Files:**
- Modify: `public/js/dashboard/api.js`

- [ ] **Step 1: Thêm method vào object `API`** (thêm dấu phẩy ở dòng cuối hiện tại `doanhThuByDepartment` rồi thêm dòng mới)

```js
      doanhThuByDepartment: function (start, end) { return get(R.fetchDoanhthuByDepartment, { startDate: start, endDate: end }); },
      bedStatusByDepartment: function (start, end) { return get(R.fetchBedStatusByDepartment, { startDate: start, endDate: end }); }
```

- [ ] **Step 2: Kiểm tra cú pháp JS**

Run: `node --check public/js/dashboard/api.js`
Expected: không lỗi. (Nếu không có node: kiểm tra mắt — dấu phẩy/ngoặc cân đối.)

- [ ] **Step 3: Commit**

```bash
git add public/js/dashboard/api.js
git commit -m "feat: DAPI.bedStatusByDepartment"
```

### Task 5: `charts.js` — render + đăng ký renderAll

**Files:**
- Modify: `public/js/dashboard/charts.js`

- [ ] **Step 1: Thêm hàm render** (đặt cùng cấp với các `function renderXxx` bên trong IIFE; `var API = win.DAPI` đã có sẵn ở đầu file)

```js
    // ----- Tình trạng giường theo khoa (snapshot hiện tại) -----
    function renderBedStatusByDepartment(start, end) {
      var el = 'chart_bed_status_by_department';
      return API.bedStatusByDepartment(start, end).done(function (r) {
        if (!r || !r.categories || !r.categories.length) {
          $('#' + el).html('<div style="text-align:center;padding:40px;color:#999;">Không có dữ liệu</div>');
          return;
        }
        var t = r.total || { tong: 0, dang_dung: 0, cong_suat: 0 };

        Highcharts.chart(el, {
          chart: { type: 'column' },
          title: {
            text: 'Tình trạng giường theo khoa: ' + t.tong + ' giường · ' + t.dang_dung + ' đã dùng · ' + t.cong_suat + '%',
            style: { fontSize: '16px', fontWeight: 'bold' }
          },
          xAxis: { categories: r.categories, labels: { rotation: -45, style: { fontSize: '12px' } } },
          yAxis: { min: 0, title: { text: 'Số giường' } },
          legend: { enabled: true },
          tooltip: {
            shared: true,
            formatter: function () {
              var i = (this.points && this.points.length) ? this.points[0].point.index : this.point.index;
              var used = r.used[i], free = r.free[i], util = r.utilization[i];
              return '<b>' + r.categories[i] + '</b><br/>Đã sử dụng: ' + used +
                     '<br/>Còn trống: ' + free +
                     '<br/>Tổng: ' + (used + free) +
                     '<br/>Công suất: ' + util + '%';
            }
          },
          plotOptions: { column: { borderWidth: 0, dataLabels: { enabled: true, style: { fontSize: '11px', fontWeight: 'bold' } } } },
          series: [
            { name: 'Đã sử dụng', data: r.used, color: '#dd4b39' },
            { name: 'Còn trống', data: r.free, color: '#00a65a' }
          ]
        });
      });
    }
```

- [ ] **Step 2: Đăng ký vào `renderAll`** — thêm `renderBedStatusByDepartment(start, end),` vào mảng `$.when.apply($, [ ... ])` trong `DCH.renderAll` (đặt cạnh `renderNoiTru(start, end),`)

```js
          renderNoiTru(start, end),
          renderBedStatusByDepartment(start, end),
```

- [ ] **Step 3: Kiểm tra cú pháp JS**

Run: `node --check public/js/dashboard/charts.js`
Expected: không lỗi.

- [ ] **Step 4: Commit**

```bash
git add public/js/dashboard/charts.js
git commit -m "feat: renderBedStatusByDepartment + dang ky renderAll"
```

### Task 6: Cập nhật `readme.md`

**Files:**
- Modify: `readme.md`

- [ ] **Step 1: Thêm mục changelog** vào đầu phần `# 10/06/2026` (ngay sau dòng tiêu đề ngày, trước dòng "Doanh thu theo khoa..."):

```markdown
- Bổ sung biểu đồ "Tình trạng giường theo khoa" trên Home dashboard: cột nhóm giường đã sử dụng / còn trống + công suất % theo từng khoa (his_bed, his_treatment_bed_room), trạng thái hiện tại (real-time, không lọc ngày)
```

Cụ thể, đổi:
```markdown
# 10/06/2026

- Bổ sung biểu đồ "Doanh thu theo khoa thực hiện" trên Home dashboard: ...
```
thành:
```markdown
# 10/06/2026

- Bổ sung biểu đồ "Tình trạng giường theo khoa" trên Home dashboard: cột nhóm giường đã sử dụng / còn trống + công suất % theo từng khoa (his_bed, his_treatment_bed_room), trạng thái hiện tại (real-time, không lọc ngày)
- Bổ sung biểu đồ "Doanh thu theo khoa thực hiện" trên Home dashboard: ...
```

> Nếu lúc triển khai phần `# 10/06/2026` không còn ở đầu file (đã sang ngày khác), tạo mục ngày mới ở đầu file rồi thêm dòng trên.

- [ ] **Step 2: Commit**

```bash
git add readme.md
git commit -m "docs: readme cap nhat bieu do tinh trang giuong theo khoa"
```

### Task 7: Kiểm chứng thật (tinker + HTTP)

**Files:** (không sửa code trừ khi phát hiện lệch)

- [ ] **Step 1: Kiểm chứng query + static method qua oci8 (tinker)**

```
$c=new App\Http\Controllers\HomeController();
$ref=new ReflectionMethod($c,'bedStatusByDepartment'); $ref->setAccessible(true);
$rows=$ref->invoke($c);
$res=App\Http\Controllers\HomeController::buildBedStatusByDepartmentSeries($rows);
echo 'khoa='.count($res['categories']).' tong='.$res['total']['tong'].' dung='.$res['total']['dang_dung'].' trong='.$res['total']['con_trong'].' cs='.$res['total']['cong_suat'].'%'.PHP_EOL;
```
Đối chiếu: ~18 khoa; tong ≈ 831; dang_dung ≈ 495 (~60%) — số có thể đổi theo thời điểm vì là snapshot. Nếu lệch nhiều → rà điều kiện.

- [ ] **Step 2: Kiểm chứng HTTP end-to-end** (server `php artisan serve`, đăng nhập `dattt`/`Olala123`):
  - Login lấy cookie + csrf, rồi `GET /fetch-bed-status-by-department` với header `X-Requested-With: XMLHttpRequest`.
  - Kỳ vọng: HTTP 200, JSON có `categories`, `used`, `free`, `utilization`, `total{tong,dang_dung,con_trong,cong_suat}`.
  - (Nếu Chrome khả dụng) mở Home dashboard xem cột nhóm 2 màu (Đã dùng đỏ / Còn trống xanh), tooltip có công suất %.

- [ ] **Step 3: Commit** (nếu có sửa)

```bash
git add -A && git commit -m "fix: kiem chung bieu do tinh trang giuong theo khoa"
```

---

## Hoàn tất

- [ ] **Chạy test:** `php vendor/bin/phpunit tests/Unit/HomeBedStatusByDepartmentTest.php` → PASS.
- [ ] **readme.md** đã cập nhật ở Task 6.
- [ ] **Verify** bằng @superpowers:verification-before-completion trước khi tuyên bố hoàn thành.
