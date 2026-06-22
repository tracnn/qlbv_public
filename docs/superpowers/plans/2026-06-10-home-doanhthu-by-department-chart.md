# Home Chart "Doanh thu theo khoa thực hiện" Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Thêm vào Home dashboard 1 biểu đồ cột (Highcharts) thống kê doanh thu theo khoa thực hiện, mỗi khoa một màu, giữ thứ tự tự nhiên, lọc theo khoảng ngày chung của dashboard, gated theo quyền tài chính.

**Architecture:** Logic tổng hợp thuần tách thành static method `HomeController::buildDoanhthuByDepartmentSeries($rows)` (unit-test được, không cần DB). Controller `fetchDoanhthuByDepartment` truy vấn `his_sere_serv → his_service_req → his_department` rồi gọi method thuần, trả JSON `{categories, data, total}`. Frontend tích hợp đúng pattern module hóa: `DAPI` (api.js) → `renderDoanhThuByDepartment` đăng ký trong `DCharts.renderAll` (charts.js).

**Tech Stack:** Laravel 5.5, yajra/laravel-oci8 (Oracle), Highcharts, numeral.js, AdminLTE 2, jQuery, PHPUnit 6.

**Spec:** `docs/superpowers/specs/2026-06-10-home-doanhthu-by-department-chart-design.md`

---

## File Structure

| File | Trách nhiệm | Thao tác |
|---|---|---|
| `app/Http/Controllers/HomeController.php` | static method tổng hợp thuần + method `fetchDoanhthuByDepartment` (query + JSON) + private `doanhthuByDepartment` | Modify |
| `tests/Unit/HomeDoanhthuByDepartmentTest.php` | Unit test cho static method | Create |
| `routes/web.php` | route `fetch-doanhthu-by-department` (group `checkrole:dashboard`) | Modify |
| `resources/views/home.blade.php` | container box + thêm route vào `DASHBOARD_CFG.routes` | Modify |
| `public/js/dashboard/api.js` | `DAPI.doanhThuByDepartment` | Modify |
| `public/js/dashboard/charts.js` | `renderDoanhThuByDepartment` + đăng ký `renderAll` | Modify |

> Không sửa `init.js` / `autorefresh.js` (không có tương tác toggle/legend; `renderAll` tự bao gồm chart mới).

**Quy ước đã xác minh:** oci8 trả key cột **lowercase** (`$row->thanh_tien` chạy đúng ở các method Home). Helper `currentDate($start,$end)` trả `['from_date','to_date']` dạng `YmdHis`. `DUtils.showNoPermissionPie(containerId, title)` có sẵn. Routes fetch chart Home nằm trong `Route::group(['middleware'=>['checkrole:dashboard']])` (web.php ~dòng 70). `numeral` đã load toàn cục.

---

## Chunk 1: Biểu đồ doanh thu theo khoa thực hiện

### Task 1: Static method tổng hợp thuần (TDD)

**Files:**
- Modify: `app/Http/Controllers/HomeController.php` (thêm 1 public static method)
- Test: `tests/Unit/HomeDoanhthuByDepartmentTest.php`

- [ ] **Step 1: Viết test thất bại**

```php
<?php
// tests/Unit/HomeDoanhthuByDepartmentTest.php
namespace Tests\Unit;

use Tests\TestCase;
use App\Http\Controllers\HomeController;

class HomeDoanhthuByDepartmentTest extends TestCase
{
    /** @test */
    public function it_builds_categories_and_data_in_natural_order()
    {
        // Mỗi $row = 1 khoa (đã GROUP BY ở SQL): department_name, thanh_tien
        $rows = [
            (object)['department_name' => 'Khoa Dược CS1',     'thanh_tien' => 2580191563],
            (object)['department_name' => 'Khoa CĐHA CS1',     'thanh_tien' => 1146216800],
            (object)['department_name' => 'Khoa Xét nghiệm CS1','thanh_tien' => 881619700],
        ];

        $res = HomeController::buildDoanhthuByDepartmentSeries($rows);

        // Giữ nguyên thứ tự đầu vào (không sắp xếp)
        $this->assertEquals(['Khoa Dược CS1','Khoa CĐHA CS1','Khoa Xét nghiệm CS1'], $res['categories']);
        $this->assertEquals([2580191563, 1146216800, 881619700], $res['data']);
        $this->assertEquals(4608028063, $res['total']);
    }

    /** @test */
    public function it_handles_empty_input()
    {
        $res = HomeController::buildDoanhthuByDepartmentSeries([]);
        $this->assertEquals([], $res['categories']);
        $this->assertEquals([], $res['data']);
        $this->assertEquals(0, $res['total']);
    }
}
```

- [ ] **Step 2: Chạy test, xác nhận FAIL**

Run: `php vendor/bin/phpunit tests/Unit/HomeDoanhthuByDepartmentTest.php`
Expected: FAIL — "Call to undefined method ...buildDoanhthuByDepartmentSeries()".

- [ ] **Step 3: Thêm static method vào `HomeController`** (đặt gần các method doanh thu, vd ngay trên `fetchDoanhthu`)

```php
    /**
     * Tổng hợp dữ liệu biểu đồ "doanh thu theo khoa thực hiện".
     * @param  iterable $rows  Mỗi phần tử là 1 khoa (đã GROUP BY): ->department_name, ->thanh_tien
     * @return array{categories: array, data: array, total: float}
     *   Giữ thứ tự tự nhiên từ rows (không sắp xếp).
     */
    public static function buildDoanhthuByDepartmentSeries($rows)
    {
        $categories = [];
        $data = [];
        $total = 0;

        foreach ($rows as $r) {
            $dt = (float) $r->thanh_tien;
            $categories[] = $r->department_name;
            $data[]       = $dt;
            $total       += $dt;
        }

        return ['categories' => $categories, 'data' => $data, 'total' => $total];
    }
```

- [ ] **Step 4: Chạy test, xác nhận PASS**

Run: `php vendor/bin/phpunit tests/Unit/HomeDoanhthuByDepartmentTest.php`
Expected: PASS (2 tests).

- [ ] **Step 5: Commit**

```bash
git add app/Http/Controllers/HomeController.php tests/Unit/HomeDoanhthuByDepartmentTest.php
git commit -m "feat: HomeController::buildDoanhthuByDepartmentSeries() + unit test"
```

### Task 2: Controller method + query + route + cấu hình route

**Files:**
- Modify: `app/Http/Controllers/HomeController.php`
- Modify: `routes/web.php`
- Modify: `resources/views/home.blade.php` (map `DASHBOARD_CFG.routes`)

- [ ] **Step 1: Thêm method `fetchDoanhthuByDepartment` + private `doanhthuByDepartment` vào `HomeController`** (theo pattern `fetchDoanhthu` + `doanhthu`)

```php
    public function fetchDoanhthuByDepartment(Request $request)
    {
        if (!$request->ajax()) {
            return redirect()->route('home');
        }

        $current_date = $this->currentDate($request->input('startDate'), $request->input('endDate'));
        $rows = $this->doanhthuByDepartment($current_date['from_date'], $current_date['to_date']);

        return response()->json(self::buildDoanhthuByDepartmentSeries($rows));
    }

    private function doanhthuByDepartment($from_date, $to_date)
    {
        return DB::connection('HISPro')
            ->table('his_sere_serv')
            ->join('his_service_req', 'his_service_req.id', '=', 'his_sere_serv.service_req_id')
            ->join('his_department', 'his_department.id', '=', 'his_sere_serv.tdl_execute_department_id')
            ->selectRaw('his_department.department_name,
                         sum(his_sere_serv.amount * his_sere_serv.price) as thanh_tien')
            ->whereBetween('his_service_req.intruction_time', [$from_date, $to_date])
            ->where('his_service_req.is_active', 1)
            ->where('his_service_req.is_delete', 0)
            ->where('his_sere_serv.is_delete', 0)
            ->groupBy('his_sere_serv.tdl_execute_department_id', 'his_department.department_name')
            ->get();
    }
```

> `DB` đã được `use DB;` ở đầu HomeController. `currentDate` là private, gọi `$this->currentDate(...)`. Static method `buildDoanhthuByDepartmentSeries` gọi `self::`.

- [ ] **Step 2: Thêm route** — đặt **bên trong** group `Route::group(['middleware' => ['checkrole:dashboard']], ...)` (~dòng 70), ngay cạnh `Route::get('fetch-doanh-thu', ...)`:

```php
Route::get('fetch-doanhthu-by-department', 'HomeController@fetchDoanhthuByDepartment')->name('fetch-doanhthu-by-department');
```

- [ ] **Step 3: Thêm route vào map `DASHBOARD_CFG.routes` trong `resources/views/home.blade.php`** (trong khối `window.DASHBOARD_CFG = { ... routes: { ... } }`, thêm cạnh `fetchDoanhThu`):

```js
        fetchDoanhthuByDepartment: "{{ route('fetch-doanhthu-by-department') }}",
```

- [ ] **Step 4: Smoke test cú pháp + route**

Run: `php -l app/Http/Controllers/HomeController.php`
Expected: "No syntax errors detected".
Run: `php artisan route:list --name=fetch-doanhthu-by-department` (nếu chạy được)
Expected: thấy route.

- [ ] **Step 5: Commit**

```bash
git add app/Http/Controllers/HomeController.php routes/web.php resources/views/home.blade.php
git commit -m "feat: route + controller fetchDoanhthuByDepartment (Home)"
```

### Task 3: Container biểu đồ (Blade)

**Files:**
- Modify: `resources/views/home.blade.php`

- [ ] **Step 1: Thêm box biểu đồ** vào khu vực doanh thu, đặt ngay **sau** hàng `<div class="row">` chứa `chart_doanhthu` / `chart_treatment` (tức sau khối row đó). Tìm dòng có `id="chart_treatment"` rồi chèn sau `</div>` đóng row đó:

```blade
    <div class="row">
        <div class="col-lg-12 connectedSortable">
            <div class="nav-tabs-custom text-center">
                <div class="tab-content no-padding" style="padding:10px;">
                    <div id="chart_doanhthu_by_department" style="width:100%; height:420px;"></div>
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
git commit -m "feat: container bieu do doanh thu theo khoa thuc hien"
```

### Task 4: `api.js` — thêm `DAPI.doanhThuByDepartment`

**Files:**
- Modify: `public/js/dashboard/api.js`

- [ ] **Step 1: Thêm method vào object `API`** (thêm dấu phẩy ở dòng cuối hiện tại `serviceByMachine` rồi thêm dòng mới)

```js
      serviceByMachine: function (start, end) { return get(R.fetchServiceByMachine, { startDate: start, endDate: end }); },
      doanhThuByDepartment: function (start, end) { return get(R.fetchDoanhthuByDepartment, { startDate: start, endDate: end }); }
```

- [ ] **Step 2: Kiểm tra cú pháp JS**

Run: `node --check public/js/dashboard/api.js`
Expected: không lỗi. (Nếu không có node: kiểm tra mắt — dấu phẩy/ngoặc cân đối.)

- [ ] **Step 3: Commit**

```bash
git add public/js/dashboard/api.js
git commit -m "feat: DAPI.doanhThuByDepartment"
```

### Task 5: `charts.js` — render + đăng ký renderAll

**Files:**
- Modify: `public/js/dashboard/charts.js`

- [ ] **Step 1: Thêm hàm render** (đặt cùng cấp với các `function renderXxx` bên trong IIFE, ví dụ ngay sau `renderDoanhThu`; `var API = win.DAPI`, `var CFG`, `var U = win.DUtils` đã có sẵn ở đầu file)

```js
    // ----- Doanh thu theo khoa thực hiện -----
    var DTKHOA_PALETTE = (window.Highcharts && Highcharts.getOptions && Highcharts.getOptions().colors)
      ? Highcharts.getOptions().colors
      : ['#7cb5ec','#434348','#90ed7d','#f7a35c','#8085e9','#f15c80','#e4d354','#2b908f','#f45b5b','#91e8e1'];

    function renderDoanhThuByDepartment(start, end) {
      var el = 'chart_doanhthu_by_department';
      if (!CFG.hasFinanceRole) {
        U.showNoPermissionPie(el, 'Doanh thu theo khoa');
        return $.Deferred().resolve().promise();
      }
      return API.doanhThuByDepartment(start, end).done(function (r) {
        if (!r || !r.categories || !r.categories.length) {
          $('#' + el).html('<div style="text-align:center;padding:40px;color:#999;">Không có dữ liệu</div>');
          return;
        }
        // Mỗi khoa một màu (tô theo từng điểm)
        var points = r.data.map(function (y, i) { return { y: y, color: DTKHOA_PALETTE[i % DTKHOA_PALETTE.length] }; });
        var totalTr = Math.round((r.total || 0) / 1e6);

        Highcharts.chart(el, {
          chart: { type: 'column' },
          title: { text: 'Doanh thu theo khoa thực hiện: ' + numeral(totalTr).format('0,0') + ' Tr', style: { fontSize: '16px', fontWeight: 'bold' } },
          xAxis: { categories: r.categories, labels: { rotation: -45, style: { fontSize: '12px' } } },
          yAxis: { min: 0, title: { text: 'Doanh thu (triệu)' }, labels: { formatter: function () { return numeral(Math.round(this.value / 1e6)).format('0,0'); } } },
          legend: { enabled: false },
          tooltip: {
            formatter: function () {
              return '<b>' + this.x + '</b><br/>Doanh thu: ' + numeral(this.y).format('0,0') +
                     ' (' + numeral(Math.round(this.y / 1e6)).format('0,0') + ' Tr)';
            }
          },
          plotOptions: { column: { borderWidth: 0, dataLabels: { enabled: true, formatter: function () { return numeral(Math.round(this.y / 1e6)).format('0,0'); }, style: { fontSize: '11px', fontWeight: 'bold' } } } },
          series: [{ name: 'Doanh thu', data: points }]
        });
      });
    }
```

- [ ] **Step 2: Đăng ký vào `renderAll`** — thêm `renderDoanhThuByDepartment(start, end),` vào mảng `$.when.apply($, [ ... ])` trong `DCH.renderAll` (đặt cạnh `renderDoanhThu(start, end),`)

```js
          renderDoanhThu(start, end),
          renderDoanhThuByDepartment(start, end),
```

- [ ] **Step 3: Kiểm tra cú pháp JS**

Run: `node --check public/js/dashboard/charts.js`
Expected: không lỗi.

- [ ] **Step 4: Commit**

```bash
git add public/js/dashboard/charts.js
git commit -m "feat: renderDoanhThuByDepartment + dang ky renderAll"
```

### Task 6: Kiểm chứng thật (tinker + trình duyệt)

**Files:** (không sửa code trừ khi phát hiện lệch)

- [ ] **Step 1: Kiểm chứng query + static method qua oci8 (tinker)**

```
$from='20260601000000'; $to='20260607235959';
$rows=DB::connection('HISPro')->table('his_sere_serv')
 ->join('his_service_req','his_service_req.id','=','his_sere_serv.service_req_id')
 ->join('his_department','his_department.id','=','his_sere_serv.tdl_execute_department_id')
 ->selectRaw('his_department.department_name, sum(his_sere_serv.amount*his_sere_serv.price) as thanh_tien')
 ->whereBetween('his_service_req.intruction_time',[$from,$to])
 ->where('his_service_req.is_active',1)->where('his_service_req.is_delete',0)->where('his_sere_serv.is_delete',0)
 ->groupBy('his_sere_serv.tdl_execute_department_id','his_department.department_name')->get();
$res=App\Http\Controllers\HomeController::buildDoanhthuByDepartmentSeries($rows);
echo 'khoa='.count($res['categories']).' total_tr='.round($res['total']/1e6).PHP_EOL;
echo json_encode(array_slice($res['categories'],0,5), JSON_UNESCAPED_UNICODE).PHP_EOL;
```
Đối chiếu: ~26 khoa; tổng (Tr) hợp lý; tên khoa đúng (Dược/CĐHA/XN... có trong danh sách). Nếu lệch → rà điều kiện.

- [ ] **Step 2: Kiểm chứng trên trình duyệt** (server `php artisan serve`, đăng nhập `dattt`/`Olala123`):
  - Mở Home dashboard, cuộn tới box "Doanh thu theo khoa thực hiện".
  - Biểu đồ cột hiện doanh thu theo khoa, **mỗi cột một màu**, nhãn theo Tr, tooltip hiện số đầy đủ + Tr.
  - Đổi khoảng ngày → biểu đồ cập nhật.
  - (Nếu tài khoản không có quyền `thungan-tonghop` → hiện "Không có quyền".)

- [ ] **Step 3: Commit** (nếu có sửa)

```bash
git add -A && git commit -m "fix: kiem chung bieu do doanh thu theo khoa thuc hien"
```

---

## Hoàn tất

- [ ] **Chạy test:** `php vendor/bin/phpunit tests/Unit/HomeDoanhthuByDepartmentTest.php` → PASS.
- [ ] **Cập nhật `readme.md`** mục ngày mới: "Bổ sung biểu đồ Doanh thu theo khoa thực hiện trên Home dashboard".
- [ ] **Verify** bằng @superpowers:verification-before-completion trước khi tuyên bố hoàn thành.
