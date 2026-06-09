# Home Chart "Số lượng dịch vụ theo máy thực hiện" Implementation Plan

> **For agentic workers:** REQUIRED: Use superpowers:subagent-driven-development (if subagents available) or superpowers:executing-plans to implement this plan. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Thêm vào Home dashboard 1 biểu đồ cột (Highcharts) thống kê số lượng dịch vụ theo máy thực hiện, có nút chuyển "Theo nhóm máy" / "Theo từng máy", lọc theo khoảng ngày chung của dashboard.

**Architecture:** Logic tổng hợp thuần tách ra một static method `HomeController::buildServiceByMachineSeries($rows)` (unit-test được, không cần DB). Controller `fetchServiceByMachine` chỉ truy vấn `his_sere_serv → his_sere_serv_ext → his_machine` rồi gọi method thuần, trả JSON `{by_group, by_machine}`. Frontend tích hợp đúng pattern module hóa: `DAPI` (api.js) → `renderServiceByMachine` đăng ký trong `DCharts.renderAll` (charts.js) → nút bind trong `init.js bindClicks()`; trạng thái nút giữ ở biến cấp module trong charts.js.

**Tech Stack:** Laravel 5.5, yajra/laravel-oci8 (Oracle), Highcharts, AdminLTE 2, jQuery, PHPUnit 6.

**Spec:** `docs/superpowers/specs/2026-06-10-home-service-by-machine-chart-design.md`

---

## File Structure

| File | Trách nhiệm | Thao tác |
|---|---|---|
| `app/Http/Controllers/HomeController.php` | static method tổng hợp thuần + method `fetchServiceByMachine` (query + trả JSON) | Modify |
| `tests/Unit/HomeServiceByMachineTest.php` | Unit test cho static method tổng hợp | Create |
| `routes/web.php` | route `fetch-service-by-machine` (nhóm route Home) | Modify |
| `resources/views/home.blade.php` | container box + 2 nút toggle + thêm route vào `DASHBOARD_CFG.routes` | Modify |
| `public/js/dashboard/api.js` | `DAPI.serviceByMachine` | Modify |
| `public/js/dashboard/charts.js` | `renderServiceByMachine` + state module + `setServiceByMachineMode` + đăng ký vào `renderAll` | Modify |
| `public/js/dashboard/init.js` | bind 2 nút toggle trong `bindClicks()` | Modify |

> Không sửa `autorefresh.js` — nó đã gọi `DCharts.renderAll`, tự bao gồm chart mới; chế độ giữ nhờ biến module.

**Quy ước dữ liệu đã xác minh:** oci8 ở app này trả key cột **lowercase** (các method Home đọc `$row->so_luong` chạy đúng) → đọc `$r->machine_name`, `$r->machine_group_code`, `$r->so_luong`. Thời gian lưu `NUMBER` `YYYYMMDDHH24MISS`. Chuẩn hóa ngày bằng helper `currentDate()` sẵn có.

---

## Chunk 1: Biểu đồ số lượng dịch vụ theo máy

### Task 1: Static method tổng hợp thuần (TDD)

**Files:**
- Modify: `app/Http/Controllers/HomeController.php` (thêm 1 public static method)
- Test: `tests/Unit/HomeServiceByMachineTest.php`

- [ ] **Step 1: Viết test thất bại**

```php
<?php
// tests/Unit/HomeServiceByMachineTest.php
namespace Tests\Unit;

use Tests\TestCase;
use App\Http\Controllers\HomeController;

class HomeServiceByMachineTest extends TestCase
{
    /** @test */
    public function it_builds_by_group_and_by_machine_sorted_desc()
    {
        // Mỗi $row = 1 máy (đã GROUP BY ở SQL): machine_name, machine_group_code, so_luong
        $rows = [
            (object)['machine_name' => 'CT-01',  'machine_group_code' => 'CL',  'so_luong' => 254],
            (object)['machine_name' => 'TNT-04', 'machine_group_code' => 'TNT', 'so_luong' => 18],
            (object)['machine_name' => 'TNT-11', 'machine_group_code' => 'TNT', 'so_luong' => 17],
            (object)['machine_name' => 'SA-01',  'machine_group_code' => 'SA',  'so_luong' => 37],
        ];

        $res = HomeController::buildServiceByMachineSeries($rows);

        // by_machine: sắp xếp giảm dần theo số lượng
        $this->assertEquals(['CT-01','SA-01','TNT-04','TNT-11'], $res['by_machine']['labels']);
        $this->assertEquals([254, 37, 18, 17], $res['by_machine']['data']);
        $this->assertEquals(['CL','SA','TNT','TNT'], $res['by_machine']['groups']);
        $this->assertEquals(326, $res['by_machine']['total']);

        // by_group: gộp theo nhóm (TNT = 18+17 = 35), sắp xếp giảm dần
        $this->assertEquals(['CL','SA','TNT'], $res['by_group']['labels']);
        $this->assertEquals([254, 37, 35], $res['by_group']['data']);
        $this->assertEquals(326, $res['by_group']['total']);
    }

    /** @test */
    public function it_maps_empty_group_to_placeholder_and_handles_empty_input()
    {
        $rows = [ (object)['machine_name' => 'X', 'machine_group_code' => null, 'so_luong' => 5] ];
        $res = HomeController::buildServiceByMachineSeries($rows);
        $this->assertEquals(['(trống)'], $res['by_group']['labels']);
        $this->assertEquals(['(trống)'], $res['by_machine']['groups']);

        $empty = HomeController::buildServiceByMachineSeries([]);
        $this->assertEquals([], $empty['by_group']['labels']);
        $this->assertEquals([], $empty['by_machine']['labels']);
        $this->assertEquals(0, $empty['by_group']['total']);
    }
}
```

- [ ] **Step 2: Chạy test, xác nhận FAIL**

Run: `php vendor/bin/phpunit tests/Unit/HomeServiceByMachineTest.php`
Expected: FAIL — "Call to undefined method ...buildServiceByMachineSeries()".

- [ ] **Step 3: Thêm static method vào `HomeController`** (đặt gần các method chart, vd ngay trên `fetchServiceByMachine` sẽ tạo ở Task 2)

```php
    /**
     * Tổng hợp dữ liệu biểu đồ "số lượng dịch vụ theo máy thực hiện".
     * @param  iterable $rows  Mỗi phần tử là 1 máy (đã GROUP BY): ->machine_name, ->machine_group_code, ->so_luong
     * @return array{by_group: array, by_machine: array}
     *   by_machine: ['labels'=>[], 'data'=>[], 'groups'=>[], 'total'=>int]  (sắp xếp số lượng giảm dần)
     *   by_group:   ['labels'=>[], 'data'=>[], 'total'=>int]                (gộp theo nhóm, giảm dần)
     */
    public static function buildServiceByMachineSeries($rows)
    {
        // Chuẩn hóa thành mảng máy + gộp nhóm
        $machines = [];   // [ ['name'=>, 'group'=>, 'sl'=>], ... ]
        $groupMap = [];   // group => tổng số lượng
        $total = 0;

        foreach ($rows as $r) {
            $name  = $r->machine_name;
            $group = ($r->machine_group_code === null || $r->machine_group_code === '') ? '(trống)' : $r->machine_group_code;
            $sl    = (float) $r->so_luong;

            $machines[] = ['name' => $name, 'group' => $group, 'sl' => $sl];
            $groupMap[$group] = (isset($groupMap[$group]) ? $groupMap[$group] : 0) + $sl;
            $total += $sl;
        }

        // by_machine: sắp xếp số lượng giảm dần
        usort($machines, function ($a, $b) { return $b['sl'] <=> $a['sl']; });
        $byMachine = ['labels' => [], 'data' => [], 'groups' => [], 'total' => $total];
        foreach ($machines as $m) {
            $byMachine['labels'][] = $m['name'];
            $byMachine['data'][]   = $m['sl'];
            $byMachine['groups'][] = $m['group'];
        }

        // by_group: sắp xếp số lượng giảm dần
        arsort($groupMap);
        $byGroup = ['labels' => array_keys($groupMap), 'data' => array_values($groupMap), 'total' => $total];

        return ['by_group' => $byGroup, 'by_machine' => $byMachine];
    }
```

- [ ] **Step 4: Chạy test, xác nhận PASS**

Run: `php vendor/bin/phpunit tests/Unit/HomeServiceByMachineTest.php`
Expected: PASS (2 tests).

- [ ] **Step 5: Commit**

```bash
git add app/Http/Controllers/HomeController.php tests/Unit/HomeServiceByMachineTest.php
git commit -m "feat: HomeController::buildServiceByMachineSeries() + unit test"
```

### Task 2: Controller `fetchServiceByMachine` + route + cấu hình route

**Files:**
- Modify: `app/Http/Controllers/HomeController.php`
- Modify: `routes/web.php`
- Modify: `resources/views/home.blade.php` (map `DASHBOARD_CFG.routes`)

- [ ] **Step 1: Thêm method `fetchServiceByMachine` vào `HomeController`** (theo pattern `fetchDiagnoticImaging`: AJAX-only, `currentDate`, `DB::connection('HISPro')`, `response()->json`)

```php
    public function fetchServiceByMachine(Request $request)
    {
        if (!$request->ajax()) {
            return redirect()->route('home');
        }

        $current_date = $this->currentDate($request->input('startDate'), $request->input('endDate'));

        $rows = DB::connection('HISPro')
            ->table('his_sere_serv')
            ->join('his_sere_serv_ext', 'his_sere_serv_ext.sere_serv_id', '=', 'his_sere_serv.id')
            ->join('his_machine', 'his_machine.id', '=', 'his_sere_serv_ext.machine_id')
            ->selectRaw('his_machine.machine_name as machine_name, his_machine.machine_group_code as machine_group_code, SUM(his_sere_serv.amount) as so_luong')
            ->whereBetween('his_sere_serv.tdl_intruction_time', [$current_date['from_date'], $current_date['to_date']])
            ->where('his_sere_serv.is_delete', 0)
            ->whereNull('his_sere_serv.is_no_execute')
            ->groupBy('his_machine.machine_name', 'his_machine.machine_group_code')
            ->get();

        return response()->json(self::buildServiceByMachineSeries($rows));
    }
```

> Lưu ý: dùng `DB` (đã `use DB;` ở đầu HomeController — xác nhận; nếu chưa có thì dùng `\DB`). `Request` đã được import (các method khác dùng `Request $request`).

- [ ] **Step 2: Thêm route** — đặt **bên trong** group `Route::group(['middleware' => ['checkrole:dashboard']], ...)` (group bắt đầu ~dòng 70), ngay cạnh `Route::get('fetch-service-by-type/{id}', ...)` (~dòng 80). Các route fetch chart Home đều nằm trong group này.

```php
Route::get('fetch-service-by-machine', 'HomeController@fetchServiceByMachine')->name('fetch-service-by-machine');
```

> Hệ quả: endpoint chỉ truy cập được với quyền `dashboard` — đồng bộ với `DASHBOARD_CFG.canDashboard` gác hiển thị.

- [ ] **Step 3: Thêm route vào map `DASHBOARD_CFG.routes` trong `resources/views/home.blade.php`** (trong khối `<script> window.DASHBOARD_CFG = {... routes: { ... } }`, thêm trước dòng `listPatientPT: ...`)

```js
        fetchServiceByMachine: "{{ route('fetch-service-by-machine') }}",
```

- [ ] **Step 4: Smoke test cú pháp + route đăng ký**

Run: `php -l app/Http/Controllers/HomeController.php`
Expected: "No syntax errors detected".
Run: `php artisan route:list --name=fetch-service-by-machine` (nếu chạy được)
Expected: thấy route `fetch-service-by-machine`.

- [ ] **Step 5: Commit**

```bash
git add app/Http/Controllers/HomeController.php routes/web.php resources/views/home.blade.php
git commit -m "feat: route + controller fetchServiceByMachine (Home)"
```

### Task 3: Container biểu đồ + 2 nút toggle (Blade)

**Files:**
- Modify: `resources/views/home.blade.php`

- [ ] **Step 1: Thêm box biểu đồ** vào khu vực các chart dịch vụ/CLS (đặt ngay **sau** `<div class="row">` chứa `chart_diagnotic_imaging_time`, tức sau khối row đó). Dùng AdminLTE `box` để có header chứa nút toggle:

```blade
    <div class="row">
        <div class="col-lg-12 connectedSortable">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">Số lượng dịch vụ theo máy thực hiện</h3>
                    <div class="box-tools pull-right">
                        <div class="btn-group" data-toggle="btn-toggle">
                            <button type="button" id="btn-machine-by-group" class="btn btn-default btn-sm active">Theo nhóm máy</button>
                            <button type="button" id="btn-machine-by-item" class="btn btn-default btn-sm">Theo từng máy</button>
                        </div>
                    </div>
                </div>
                <div class="box-body">
                    <div id="chart_service_by_machine" style="width:100%; height:420px;"></div>
                </div>
            </div>
        </div>
    </div>
```

- [ ] **Step 2: Kiểm tra Blade compile**

Run: `php artisan view:clear` (xóa cache; trang sẽ được biên dịch lại khi truy cập).
Expected: "Compiled views cleared!" (không lỗi). Việc render thật kiểm ở Task 7.

- [ ] **Step 3: Commit**

```bash
git add resources/views/home.blade.php
git commit -m "feat: container + nut toggle bieu do so luong dich vu theo may"
```

### Task 4: `api.js` — thêm `DAPI.serviceByMachine`

**Files:**
- Modify: `public/js/dashboard/api.js`

- [ ] **Step 1: Thêm method vào object `API`** (thêm dấu phẩy ở dòng cuối hiện tại `patientInRoomNgoaiTru` rồi thêm dòng mới)

```js
      patientInRoomNgoaiTru: function (start, end) { return get(R.fetchPatientInRoomNgoaiTru, { startDate: start, endDate: end }); },
      serviceByMachine: function (start, end) { return get(R.fetchServiceByMachine, { startDate: start, endDate: end }); }
```

- [ ] **Step 2: Kiểm tra cú pháp JS** (nếu có node)

Run: `node --check public/js/dashboard/api.js`
Expected: không lỗi. (Nếu không có node, kiểm tra bằng mắt: dấu phẩy/đóng ngoặc cân đối.)

- [ ] **Step 3: Commit**

```bash
git add public/js/dashboard/api.js
git commit -m "feat: DAPI.serviceByMachine"
```

### Task 5: `charts.js` — render + state module + đăng ký vào renderAll

**Files:**
- Modify: `public/js/dashboard/charts.js`

- [ ] **Step 1: Thêm biến cấp module + hàm render** (đặt cùng cấp với các `function renderXxx`, bên trong IIFE; `var API = win.DAPI` đã có sẵn ở đầu file)

```js
    // ----- Số lượng dịch vụ theo máy thực hiện -----
    var sbmLastData = null;   // payload {by_group, by_machine} fetch gần nhất
    var sbmMode = 'group';    // 'group' | 'machine' (mặc định nhóm)

    function drawServiceByMachine() {
      var el = 'chart_service_by_machine';
      if (!sbmLastData) return;
      var src = (sbmMode === 'machine') ? sbmLastData.by_machine : sbmLastData.by_group;
      var labels = (src && src.labels) || [];
      var data = (src && src.data) || [];
      var groups = (src && src.groups) || [];

      if (!labels.length) {
        $('#' + el).html('<div style="text-align:center;padding:40px;color:#999;">Không có dữ liệu</div>');
        return;
      }

      var isMachine = (sbmMode === 'machine');
      Highcharts.chart(el, {
        chart: { type: 'column' },
        title: {
          text: isMachine ? 'Số lượng dịch vụ theo từng máy' : 'Số lượng dịch vụ theo nhóm máy',
          style: { fontSize: '16px', fontWeight: 'bold' }
        },
        subtitle: { text: 'Tổng: ' + (src.total || 0) },
        xAxis: { categories: labels, labels: { rotation: -45, style: { fontSize: '12px' } } },
        yAxis: { min: 0, title: { text: 'Số lượng' } },
        legend: { enabled: false },
        tooltip: {
          formatter: function () {
            var s = '<b>' + this.x + '</b><br/>Số lượng: ' + Highcharts.numberFormat(this.y, 0);
            if (isMachine && groups[this.point.index]) s += '<br/>Nhóm: ' + groups[this.point.index];
            return s;
          }
        },
        plotOptions: { column: { borderWidth: 0, dataLabels: { enabled: true, style: { fontSize: '11px', fontWeight: 'bold' } } } },
        series: [{ name: 'Số lượng', data: data, color: '#3c8dbc' }]
      });
    }

    function renderServiceByMachine(start, end) {
      return API.serviceByMachine(start, end).done(function (d) {
        sbmLastData = d;
        drawServiceByMachine();
      });
    }
```

- [ ] **Step 2: Đăng ký vào `renderAll`** — thêm `renderServiceByMachine(start, end),` vào mảng `$.when.apply($, [ ... ])` trong `DCH.renderAll` (đặt cạnh `renderDiagImaging(start, end),`)

```js
          renderDiagImaging(start, end),
          renderServiceByMachine(start, end),
```

- [ ] **Step 3: Export hàm đổi chế độ** — trong object `DCH` (chỗ `var DCH = { renderAll: function () {...} }`), thêm method:

```js
    var DCH = {
      renderAll: function (start, end) {
        // ... giữ nguyên ...
      },
      setServiceByMachineMode: function (mode) {
        sbmMode = (mode === 'machine') ? 'machine' : 'group';
        if (sbmLastData) drawServiceByMachine();
      }
    };
```

> Lưu ý: `setServiceByMachineMode` chỉ vẽ lại từ `sbmLastData` (KHÔNG gọi server). `renderAll` (tải trang / auto-refresh / đổi ngày) luôn fetch mới qua `renderServiceByMachine` nhưng đọc `sbmMode` cấp module nên giữ nguyên chế độ đang chọn.

- [ ] **Step 4: Kiểm tra cú pháp JS**

Run: `node --check public/js/dashboard/charts.js`
Expected: không lỗi.

- [ ] **Step 5: Commit**

```bash
git add public/js/dashboard/charts.js
git commit -m "feat: renderServiceByMachine + state toggle + dang ky renderAll"
```

### Task 6: `init.js` — bind 2 nút toggle

**Files:**
- Modify: `public/js/dashboard/init.js`

- [ ] **Step 1: Thêm handler vào hàm `bindClicks()`** (cuối hàm, trước dấu `}` đóng `bindClicks`)

```js
      // Toggle biểu đồ số lượng dịch vụ theo máy: nhóm máy / từng máy
      $(document).on('click', '#btn-machine-by-group, #btn-machine-by-item', function () {
        var mode = (this.id === 'btn-machine-by-item') ? 'machine' : 'group';
        $('#btn-machine-by-group, #btn-machine-by-item').removeClass('active');
        $(this).addClass('active');
        if (win.DCharts && DCharts.setServiceByMachineMode) DCharts.setServiceByMachineMode(mode);
      });
```

- [ ] **Step 2: Kiểm tra cú pháp JS**

Run: `node --check public/js/dashboard/init.js`
Expected: không lỗi.

- [ ] **Step 3: Commit**

```bash
git add public/js/dashboard/init.js
git commit -m "feat: bind nut toggle bieu do may trong bindClicks"
```

### Task 7: Kiểm chứng thật (tinker + trình duyệt)

**Files:** (không sửa code trừ khi phát hiện lệch)

- [ ] **Step 1: Kiểm chứng endpoint qua oci8 (tinker)** — so dữ liệu với DB

```
$svc=new App\Http\Controllers\HomeController();
$req=new Illuminate\Http\Request(['startDate'=>'2026-06-01 00:00:00','endDate'=>'2026-06-07 23:59:59']);
```
Vì `fetchServiceByMachine` chặn non-ajax, kiểm tra trực tiếp truy vấn + static method:
```
$from='20260601000000'; $to='20260607235959';
$rows=DB::connection('HISPro')->table('his_sere_serv')
 ->join('his_sere_serv_ext','his_sere_serv_ext.sere_serv_id','=','his_sere_serv.id')
 ->join('his_machine','his_machine.id','=','his_sere_serv_ext.machine_id')
 ->selectRaw('his_machine.machine_name as machine_name, his_machine.machine_group_code as machine_group_code, SUM(his_sere_serv.amount) as so_luong')
 ->whereBetween('his_sere_serv.tdl_intruction_time',[$from,$to])
 ->where('his_sere_serv.is_delete',0)->whereNull('his_sere_serv.is_no_execute')
 ->groupBy('his_machine.machine_name','his_machine.machine_group_code')->get();
$res=App\Http\Controllers\HomeController::buildServiceByMachineSeries($rows);
echo 'groups='.count($res['by_group']['labels']).' machines='.count($res['by_machine']['labels']).' total='.$res['by_group']['total'].PHP_EOL;
echo json_encode($res['by_group']).PHP_EOL;
```
Đối chiếu: `by_group` ~10 nhóm (TNT lớn nhất), `by_machine` ~58 máy, total khớp số khảo sát (~1.078 tuần đó). Nếu lệch → rà lại điều kiện.

- [ ] **Step 2: Kiểm chứng trên trình duyệt** (server `php artisan serve`, đăng nhập `dattt`/`Olala123`):
  - Mở Home dashboard, cuộn tới box "Số lượng dịch vụ theo máy thực hiện".
  - Mặc định "Theo nhóm máy": cột theo nhóm (TNT/CL/SA...).
  - Bấm "Theo từng máy": cột đổi sang từng máy, **không gọi lại server** (kiểm tab Network: không có request mới khi bấm).
  - Đổi khoảng ngày (daterangepicker) → biểu đồ cập nhật và **giữ** chế độ đang chọn.
  - Khoảng ngày không có dữ liệu → hiển thị "Không có dữ liệu".

- [ ] **Step 3: Commit** (nếu có sửa)

```bash
git add -A && git commit -m "fix: kiem chung bieu do so luong dich vu theo may"
```

---

## Hoàn tất

- [ ] **Chạy test:** `php vendor/bin/phpunit tests/Unit/HomeServiceByMachineTest.php` → PASS.
- [ ] **Cập nhật `readme.md`** mục ngày mới: "Bổ sung biểu đồ Số lượng dịch vụ theo máy thực hiện trên Home dashboard".
- [ ] **Verify** bằng @superpowers:verification-before-completion trước khi tuyên bố hoàn thành.
