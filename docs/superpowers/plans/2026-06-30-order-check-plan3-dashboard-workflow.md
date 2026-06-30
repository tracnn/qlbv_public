# Order Check — Plan 3: Dashboard + Workflow xử lý + Excel + API

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans để triển khai task-by-task. Steps dùng checkbox (`- [ ]`).

**Goal:** Cung cấp giao diện cho người dùng XEM và XỬ LÝ các vi phạm y lệnh đã phát hiện (Plan 1+2): trang dashboard có bộ lọc + KPI + bảng chi tiết (DataTables server-side), quy trình xử lý (đã xem / đã xử lý / bỏ qua), xuất Excel, và API JSON read-only để HIS/màn hình khác tra cứu vi phạm theo đợt điều trị.

**Architecture:** Vi phạm nằm ở MySQL `qlbv` (bảng `order_check_violations`) → query Eloquent trực tiếp (không cần HIS). Theo đúng khuôn báo cáo KHTH hiện có: controller trong `app/Http/Controllers/KHTH/`, view `resources/views/khth/`, route group `prefix 'khth/'` + `checkrole:administrator`, DataTables server-side (Yajra), Excel (Maatwebsite), menu trong `config/adminlte.php`. Một `ViolationQueryService` gom bộ lọc dùng chung cho fetch/summary/export.

**Tech Stack:** PHP 7 / Laravel 5.5, Eloquent (MySQL), Yajra Datatables, Maatwebsite Excel, AdminLTE/Blade, laratrust (`checkrole`).

**Tham chiếu:** spec `...specs/2026-06-30-kiem-tra-sai-sot-y-lenh-design.md`; Plan 1 (`1bbca7b`), Plan 2 (đã commit). Mẫu UI: `app/Http/Controllers/KHTH/OnTimeResultController.php`, `resources/views/khth/on-time-result.blade.php`, `config/adminlte.php` (menu).

## Bối cảnh có sẵn (KHÔNG tạo lại)
- Bảng `order_check_violations` (cột: `id, rule_id, rule_code, treatment_id, treatment_code, patient_code, patient_name, doctor_loginname, doctor_username, department_id, order_ref_type, order_ref_id, severity, message, detail, dedup_key, status, detected_at, processed_by, processed_at, note, timestamps`).
- Bảng `order_check_rules` (cột `code, name, severity, family, is_active, ...`).
- Models `App\Models\OrderCheck\{OrderCheckViolation, OrderCheckRule}`.
- `status` ∈ `new|seen|processed|false_positive`; `severity` ∈ `info|warning|critical`.
- Auth user lấy qua `auth()->user()` (cột định danh: dùng `->name` hoặc `->username` tùy User model — implementer kiểm tra `app/User.php`).

## Phạm vi & ngoài phạm vi
- **Trong Plan 3:** dashboard (lọc + KPI + bảng), workflow đổi trạng thái + ghi chú, Excel, API JSON read-only, menu/route/permission.
- **Ngoài phạm vi (Plan sau, nêu rõ — KHÔNG stub trong plan này):**
  - **Plan 4 — Thông báo chủ động** (email/SMS/Telegram): cần quyết định người nhận theo khoa + ngưỡng gộp/chống spam; xây trên chính `order_check_violations` Plan 3.
  - **Plan 5 — Luật mở rộng:** A2 trùng hoạt chất / A3 trùng DV / A5 liều (cấp đợt điều trị), gender/tuổi/BHYT (cần dữ liệu tham chiếu).
  - **Làm giàu tên:** tên khoa (`department_id`→tên, cross-DB sang HIS) và tên thuốc/tên mức độ tương tác — hiển thị id ở Plan 3, enrich sau.

## File Structure (Plan 3)
**Tạo mới:**
- `app/Services/OrderCheck/ViolationQueryService.php` — gom bộ lọc + truy vấn.
- `app/Http/Controllers/KHTH/OrderCheckController.php` — index/fetch/summary/updateStatus/export.
- `app/Exports/OrderCheckViolationExport.php` — xuất Excel.
- `resources/views/khth/order-check.blade.php` — trang dashboard.
- `tests/Unit/OrderCheck/ViolationQueryServiceTest.php` — test whitelist trạng thái.
**Sửa:**
- `routes/web.php` — thêm route trong group `khth/`.
- `routes/api.php` — thêm API JSON read-only.
- `config/adminlte.php` — thêm mục menu.

---

## Task 1: ViolationQueryService (bộ lọc dùng chung) + test whitelist

**Files:**
- Create: `app/Services/OrderCheck/ViolationQueryService.php`
- Test: `tests/Unit/OrderCheck/ViolationQueryServiceTest.php`

- [ ] **Step 1: Viết test thất bại (whitelist trạng thái workflow)**

```php
<?php

namespace Tests\Unit\OrderCheck;

use Tests\TestCase;
use App\Services\OrderCheck\ViolationQueryService;

class ViolationQueryServiceTest extends TestCase
{
    public function test_status_hop_le_duoc_chap_nhan()
    {
        $svc = new ViolationQueryService();
        $this->assertTrue($svc->isValidUpdateStatus('processed'));
        $this->assertTrue($svc->isValidUpdateStatus('false_positive'));
        $this->assertTrue($svc->isValidUpdateStatus('seen'));
    }

    public function test_status_khong_hop_le_bi_tu_choi()
    {
        $svc = new ViolationQueryService();
        $this->assertFalse($svc->isValidUpdateStatus('new'));   // 'new' do engine dat, khong cho set tay
        $this->assertFalse($svc->isValidUpdateStatus('xyz'));
        $this->assertFalse($svc->isValidUpdateStatus(''));
    }
}
```

- [ ] **Step 2: Chạy test, xác nhận FAIL**

Run: `vendor/bin/phpunit --filter ViolationQueryServiceTest`
Expected: FAIL ("Class '...ViolationQueryService' not found")

- [ ] **Step 3: Cài đặt service**

```php
<?php

namespace App\Services\OrderCheck;

use App\Models\OrderCheck\OrderCheckViolation;
use Illuminate\Http\Request;

class ViolationQueryService
{
    /** Trạng thái người dùng được phép set qua workflow. */
    const UPDATABLE_STATUSES = ['seen', 'processed', 'false_positive'];

    public function isValidUpdateStatus($status)
    {
        return in_array($status, self::UPDATABLE_STATUSES, true);
    }

    /**
     * Query đã áp bộ lọc từ request. Dùng chung cho fetch/summary/export.
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function filtered(Request $request)
    {
        $q = OrderCheckViolation::query();

        if ($request->filled('date_from')) {
            $q->where('detected_at', '>=', $request->input('date_from') . ' 00:00:00');
        }
        if ($request->filled('date_to')) {
            $q->where('detected_at', '<=', $request->input('date_to') . ' 23:59:59');
        }
        if ($request->filled('severity')) {
            $q->where('severity', $request->input('severity'));
        }
        if ($request->filled('status')) {
            $q->where('status', $request->input('status'));
        }
        if ($request->filled('rule_code')) {
            $q->where('rule_code', $request->input('rule_code'));
        }
        if ($request->filled('department_id')) {
            $q->where('department_id', $request->input('department_id'));
        }
        if ($request->filled('keyword')) {
            $kw = trim($request->input('keyword'));
            $q->where(function ($w) use ($kw) {
                $w->where('patient_code', 'like', "%{$kw}%")
                  ->orWhere('patient_name', 'like', "%{$kw}%")
                  ->orWhere('treatment_code', 'like', "%{$kw}%")
                  ->orWhere('doctor_loginname', 'like', "%{$kw}%")
                  ->orWhere('doctor_username', 'like', "%{$kw}%");
            });
        }

        return $q;
    }
}
```

- [ ] **Step 4: Chạy test, xác nhận PASS**

Run: `vendor/bin/phpunit --filter ViolationQueryServiceTest`
Expected: PASS (2 tests)

- [ ] **Step 5: Commit** (bỏ qua nếu được yêu cầu)

```bash
git add app/Services/OrderCheck/ViolationQueryService.php tests/Unit/OrderCheck/ViolationQueryServiceTest.php
git commit -m "feat(order-check): ViolationQueryService (bo loc dung chung) + test whitelist trang thai"
```

---

## Task 2: Excel Export

**Files:**
- Create: `app/Exports/OrderCheckViolationExport.php`

- [ ] **Step 1: Tạo Export class**

```php
<?php

namespace App\Exports;

use App\Services\OrderCheck\ViolationQueryService;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class OrderCheckViolationExport implements FromCollection, WithHeadings, WithMapping
{
    protected $params;

    public function __construct(array $params)
    {
        $this->params = $params;
    }

    public function collection()
    {
        $service = new ViolationQueryService();
        return $service->filtered(new Request($this->params))
            ->orderBy('detected_at', 'desc')
            ->get();
    }

    public function headings(): array
    {
        return ['Thời điểm', 'Mức độ', 'Mã luật', 'Mã ĐT', 'Mã BN', 'Tên BN', 'Bác sĩ', 'Khoa (ID)', 'Nội dung', 'Trạng thái', 'Người xử lý', 'Ghi chú'];
    }

    public function map($v): array
    {
        return [
            (string) $v->detected_at,
            $v->severity,
            $v->rule_code,
            $v->treatment_code,
            $v->patient_code,
            $v->patient_name,
            $v->doctor_username ?: $v->doctor_loginname,
            $v->department_id,
            $v->message,
            $v->status,
            $v->processed_by,
            $v->note,
        ];
    }
}
```

- [ ] **Step 2: Kiểm tra cú pháp**

Run: `php -l app/Exports/OrderCheckViolationExport.php`
Expected: `No syntax errors detected`

- [ ] **Step 3: Commit** (bỏ qua nếu được yêu cầu)

```bash
git add app/Exports/OrderCheckViolationExport.php
git commit -m "feat(order-check): Excel export vi pham y lenh"
```

---

## Task 3: Controller

**Files:**
- Create: `app/Http/Controllers/KHTH/OrderCheckController.php`

> Trước khi viết, kiểm tra cột định danh user: mở `app/User.php` xem có `username` không; nếu không, dùng `name`. Code dưới dùng `auth()->user()->username ?? auth()->user()->name`.

- [ ] **Step 1: Tạo controller**

```php
<?php

namespace App\Http\Controllers\KHTH;

use App\Http\Controllers\Controller;
use App\Models\OrderCheck\OrderCheckViolation;
use App\Models\OrderCheck\OrderCheckRule;
use App\Services\OrderCheck\ViolationQueryService;
use App\Exports\OrderCheckViolationExport;
use Illuminate\Http\Request;
use Yajra\Datatables\Datatables;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;

class OrderCheckController extends Controller
{
    protected $service;

    public function __construct(ViolationQueryService $service)
    {
        $this->service = $service;
    }

    public function index()
    {
        $rules = OrderCheckRule::orderBy('code')->get(['code', 'name']);
        return view('khth.order-check', compact('rules'));
    }

    public function summary(Request $request)
    {
        $base = $this->service->filtered($request);

        $bySeverity = (clone $base)->selectRaw('severity, COUNT(*) c')->groupBy('severity')->pluck('c', 'severity');
        $byStatus = (clone $base)->selectRaw('status, COUNT(*) c')->groupBy('status')->pluck('c', 'status');

        return response()->json([
            'total' => (clone $base)->count(),
            'critical' => (int) ($bySeverity['critical'] ?? 0),
            'warning' => (int) ($bySeverity['warning'] ?? 0),
            'info' => (int) ($bySeverity['info'] ?? 0),
            'new' => (int) ($byStatus['new'] ?? 0),
            'processed' => (int) ($byStatus['processed'] ?? 0),
            'false_positive' => (int) ($byStatus['false_positive'] ?? 0),
        ]);
    }

    public function fetch(Request $request)
    {
        $query = $this->service->filtered($request)->orderBy('detected_at', 'desc');

        return Datatables::of($query)
            ->editColumn('detected_at', function ($v) {
                return $v->detected_at ? Carbon::parse($v->detected_at)->format('d/m/Y H:i') : '';
            })
            ->addColumn('severity_badge', function ($v) {
                $map = [
                    'critical' => '<span class="label label-danger">Nghiêm trọng</span>',
                    'warning' => '<span class="label label-warning">Cảnh báo</span>',
                    'info' => '<span class="label label-info">Thông tin</span>',
                ];
                return $map[$v->severity] ?? $v->severity;
            })
            ->addColumn('status_badge', function ($v) {
                $map = [
                    'new' => '<span class="label label-default">Mới</span>',
                    'seen' => '<span class="label label-primary">Đã xem</span>',
                    'processed' => '<span class="label label-success">Đã xử lý</span>',
                    'false_positive' => '<span class="label label-warning">Bỏ qua</span>',
                ];
                return $map[$v->status] ?? $v->status;
            })
            ->addColumn('doctor', function ($v) {
                return $v->doctor_username ?: $v->doctor_loginname;
            })
            ->addColumn('actions', function ($v) {
                return '<div class="btn-group">'
                    . '<button class="btn btn-xs btn-success oc-act" data-id="' . $v->id . '" data-status="processed">Đã xử lý</button> '
                    . '<button class="btn btn-xs btn-warning oc-act" data-id="' . $v->id . '" data-status="false_positive">Bỏ qua</button>'
                    . '</div>';
            })
            ->rawColumns(['severity_badge', 'status_badge', 'actions'])
            ->make(true);
    }

    public function updateStatus(Request $request)
    {
        $request->validate([
            'id' => 'required|integer',
            'status' => 'required|string',
            'note' => 'nullable|string|max:1000',
        ]);

        if (!$this->service->isValidUpdateStatus($request->input('status'))) {
            return response()->json(['ok' => false, 'message' => 'Trạng thái không hợp lệ'], 422);
        }

        $v = OrderCheckViolation::find($request->input('id'));
        if (!$v) {
            return response()->json(['ok' => false, 'message' => 'Không tìm thấy vi phạm'], 404);
        }

        $user = auth()->user();
        $v->status = $request->input('status');
        $v->processed_by = $user ? ($user->username ?? $user->name) : null;
        $v->processed_at = Carbon::now();
        if ($request->filled('note')) {
            $v->note = $request->input('note');
        }
        $v->save();

        return response()->json(['ok' => true]);
    }

    public function export(Request $request)
    {
        $fileName = 'sai_sot_y_lenh_' . Carbon::now()->format('YmdHis') . '.xlsx';
        return Excel::download(new OrderCheckViolationExport($request->all()), $fileName);
    }
}
```

- [ ] **Step 2: Kiểm tra cú pháp + cột user**

Run: `php -l app/Http/Controllers/KHTH/OrderCheckController.php`
Expected: `No syntax errors detected`

Kiểm tra `app/User.php` có thuộc tính `username` không; nếu KHÔNG có cột `username`, sửa 2 chỗ `$user->username ?? $user->name` cho khớp (giữ fallback `name`).

- [ ] **Step 3: Commit** (bỏ qua nếu được yêu cầu)

```bash
git add app/Http/Controllers/KHTH/OrderCheckController.php
git commit -m "feat(order-check): controller dashboard + workflow + export"
```

---

## Task 4: Routes (web) + Menu

**Files:**
- Modify: `routes/web.php` (thêm trong group `prefix 'khth/'`, `checkrole:administrator`)
- Modify: `config/adminlte.php` (thêm mục menu)

- [ ] **Step 1: Thêm route web**

Trong `routes/web.php`, tìm group `Route::group(['prefix' => 'khth/', 'middleware' => ['checkrole:administrator']], function () {` và thêm các route sau vào TRONG group đó (cạnh các route `revenue-dept-room-*`):

```php
        /* Kiểm tra sai sót y lệnh */
        Route::get('order-check-index', 'KHTH\OrderCheckController@index')->name('khth.order-check-index');
        Route::get('order-check-index/summary', 'KHTH\OrderCheckController@summary')->name('khth.order-check-summary');
        Route::get('order-check-index/fetch', 'KHTH\OrderCheckController@fetch')->name('khth.order-check-fetch');
        Route::post('order-check-index/update-status', 'KHTH\OrderCheckController@updateStatus')->name('khth.order-check-update-status');
        Route::get('order-check-index/export', 'KHTH\OrderCheckController@export')->name('khth.order-check-export');
```

- [ ] **Step 2: Thêm mục menu**

Trong `config/adminlte.php`, tìm mục `'route' => 'khth.revenue-dept-room-index',` và thêm NGAY SAU block đó (trong cùng mảng `submenu`):

```php
                [
                    'text'      => 'Kiểm tra sai sót y lệnh',
                    'icon'      => 'stethoscope',
                    'checkrole' => 'administrator',
                    'route'     => 'khth.order-check-index',
                    'active'    => ['khth/order-check-index*'],
                ],
```

- [ ] **Step 3: Verify route đăng ký**

Run: `php artisan route:list --name=order-check 2>&1 | head -20`
Expected: liệt kê 5 route `khth.order-check-*`.

- [ ] **Step 4: Commit** (bỏ qua nếu được yêu cầu)

```bash
git add routes/web.php config/adminlte.php
git commit -m "feat(order-check): route web + menu dashboard sai sot y lenh"
```

---

## Task 5: View dashboard

**Files:**
- Create: `resources/views/khth/order-check.blade.php`

> View tự chứa bộ lọc (2 ô ngày + select severity/status/rule + keyword + nút Tải) và DataTables server-side; không phụ thuộc partial daterange. Theo phong cách AdminLTE + select2 như các trang KHTH khác.

- [ ] **Step 1: Tạo view**

```blade
@extends('adminlte::page')
@section('title', 'Kiểm tra sai sót y lệnh')
@section('content_header')<h1>Kiểm tra sai sót y lệnh</h1>@stop

@section('content')
<div class="box box-primary">
  <div class="box-body">
    <div class="row">
      <div class="col-md-2"><label>Từ ngày</label><input type="date" id="date_from" class="form-control" value="{{ date('Y-m-d') }}"></div>
      <div class="col-md-2"><label>Đến ngày</label><input type="date" id="date_to" class="form-control" value="{{ date('Y-m-d') }}"></div>
      <div class="col-md-2"><label>Mức độ</label>
        <select id="severity" class="form-control select2"><option value="">Tất cả</option>
          <option value="critical">Nghiêm trọng</option><option value="warning">Cảnh báo</option><option value="info">Thông tin</option>
        </select>
      </div>
      <div class="col-md-2"><label>Trạng thái</label>
        <select id="status" class="form-control select2"><option value="">Tất cả</option>
          <option value="new">Mới</option><option value="seen">Đã xem</option><option value="processed">Đã xử lý</option><option value="false_positive">Bỏ qua</option>
        </select>
      </div>
      <div class="col-md-2"><label>Loại luật</label>
        <select id="rule_code" class="form-control select2"><option value="">Tất cả</option>
          @foreach($rules as $r)<option value="{{ $r->code }}">{{ $r->name }}</option>@endforeach
        </select>
      </div>
      <div class="col-md-2"><label>Tìm BN/BS/ĐT</label><input type="text" id="keyword" class="form-control" placeholder="mã/tên..."></div>
    </div>
    <div class="row" style="margin-top:10px"><div class="col-md-12">
      <button id="btn-load" class="btn btn-primary"><i class="fa fa-search"></i> Tải dữ liệu</button>
      <a id="btn-export" class="btn btn-success"><i class="fa fa-file-excel-o"></i> Xuất Excel</a>
    </div></div>
  </div>
</div>

<div class="row">
  <div class="col-md-3"><div class="info-box"><span class="info-box-icon bg-aqua"><i class="fa fa-list"></i></span><div class="info-box-content"><span class="info-box-text">Tổng</span><span class="info-box-number" id="kpi-total">0</span></div></div></div>
  <div class="col-md-3"><div class="info-box"><span class="info-box-icon bg-red"><i class="fa fa-exclamation-triangle"></i></span><div class="info-box-content"><span class="info-box-text">Nghiêm trọng</span><span class="info-box-number" id="kpi-critical">0</span></div></div></div>
  <div class="col-md-3"><div class="info-box"><span class="info-box-icon bg-yellow"><i class="fa fa-bell"></i></span><div class="info-box-content"><span class="info-box-text">Cảnh báo</span><span class="info-box-number" id="kpi-warning">0</span></div></div></div>
  <div class="col-md-3"><div class="info-box"><span class="info-box-icon bg-gray"><i class="fa fa-inbox"></i></span><div class="info-box-content"><span class="info-box-text">Chưa xử lý</span><span class="info-box-number" id="kpi-new">0</span></div></div></div>
</div>

<div class="box">
  <div class="box-header"><h3 class="box-title">Danh sách vi phạm</h3></div>
  <div class="box-body table-responsive">
    <table id="oc-table" class="table table-hover table-bordered" width="100%">
      <thead><tr>
        <th>Thời điểm</th><th>Mức độ</th><th>Luật</th><th>Mã ĐT</th><th>Tên BN</th><th>Bác sĩ</th><th>Khoa</th><th>Nội dung</th><th>Trạng thái</th><th>Thao tác</th>
      </tr></thead>
    </table>
  </div>
</div>
@stop

@push('after-scripts')
<script>
var DT_VI = { search:'Tìm:', lengthMenu:'Hiện _MENU_ dòng', info:'Hiển thị _START_-_END_ / _TOTAL_', infoEmpty:'Không có dữ liệu', zeroRecords:'Không tìm thấy', emptyTable:'Không có dữ liệu', paginate:{ first:'Đầu', last:'Cuối', next:'Sau', previous:'Trước' } };
var ocTable = null;

function filters(){
  return { date_from:$('#date_from').val(), date_to:$('#date_to').val(), severity:$('#severity').val(), status:$('#status').val(), rule_code:$('#rule_code').val(), keyword:$('#keyword').val() };
}

function loadSummary(){
  $.getJSON("{{ route('khth.order-check-summary') }}", filters(), function(r){
    $('#kpi-total').text(r.total); $('#kpi-critical').text(r.critical); $('#kpi-warning').text(r.warning); $('#kpi-new').text(r.new);
  });
}

function reload(){
  loadSummary();
  if(ocTable){ ocTable.ajax.reload(); return; }
  ocTable = $('#oc-table').DataTable({
    processing:true, serverSide:true, destroy:true, scrollX:true, order:[[0,'desc']],
    ajax:{ url:"{{ route('khth.order-check-fetch') }}", data:function(d){ Object.assign(d, filters()); } },
    language:DT_VI,
    columns:[
      {data:'detected_at'},{data:'severity_badge'},{data:'rule_code'},{data:'treatment_code'},
      {data:'patient_name'},{data:'doctor'},{data:'department_id'},{data:'message'},
      {data:'status_badge'},{data:'actions',orderable:false,searchable:false}
    ]
  });
}

$(function(){
  $('.select2').select2({width:'100%'});
  $('#btn-load').on('click', reload);

  $('#btn-export').on('click', function(){
    var q = $.param(filters());
    window.location = "{{ route('khth.order-check-export') }}?" + q;
  });

  // Thao tác workflow
  $(document).on('click', '.oc-act', function(){
    var id=$(this).data('id'), status=$(this).data('status');
    var note = prompt('Ghi chú (tùy chọn):', '');
    if(note === null) return; // bấm Cancel
    $.ajax({
      url:"{{ route('khth.order-check-update-status') }}", method:'POST',
      data:{ _token:"{{ csrf_token() }}", id:id, status:status, note:note },
      success:function(){ reload(); },
      error:function(xhr){ alert((xhr.responseJSON && xhr.responseJSON.message) || 'Lỗi cập nhật'); }
    });
  });

  reload(); // tải lần đầu
});
</script>
@endpush
```

- [ ] **Step 2: Verify view tồn tại + route index trả 200 (thủ công)**

> UI cần đăng nhập (checkrole:administrator) nên xác minh tự động hạn chế. Kiểm tra view không lỗi cú pháp blade bằng cách build route:
Run: `php artisan route:list --name=order-check-index 2>&1 | head`
Expected: thấy `khth.order-check-index`.

Xác minh trực quan (thủ công trên trình duyệt khi đã đăng nhập): mở `/khth/order-check-index`, chọn khoảng ngày bao trùm dữ liệu test, bấm "Tải dữ liệu" → thấy KPI + bảng vi phạm; bấm "Đã xử lý"/"Bỏ qua" → dòng đổi trạng thái; "Xuất Excel" tải file.

- [ ] **Step 3: Commit** (bỏ qua nếu được yêu cầu)

```bash
git add resources/views/khth/order-check.blade.php
git commit -m "feat(order-check): view dashboard sai sot y lenh (loc + KPI + DataTables + workflow)"
```

---

## Task 6: API JSON read-only (tra cứu vi phạm theo đợt điều trị)

**Files:**
- Modify: `routes/api.php`

> Trước khi thêm: mở `routes/api.php` xem nhóm middleware xác thực hiện có (vd `auth:api`/`jwt.auth`). Thêm route vào nhóm xác thực đó. Nếu chưa rõ, dùng middleware `auth:api` (JWT đã cài `tymon/jwt-auth`).

- [ ] **Step 1: Thêm route API**

Thêm vào `routes/api.php` (trong nhóm xác thực phù hợp):

```php
// Tra cứu vi phạm y lệnh theo đợt điều trị (cho HIS/màn hình khác)
Route::get('order-check/violations', function (\Illuminate\Http\Request $request) {
    $request->validate(['treatment_code' => 'required_without:treatment_id', 'treatment_id' => 'required_without:treatment_code']);

    $q = \App\Models\OrderCheck\OrderCheckViolation::query();
    if ($request->filled('treatment_code')) {
        $q->where('treatment_code', $request->input('treatment_code'));
    }
    if ($request->filled('treatment_id')) {
        $q->where('treatment_id', $request->input('treatment_id'));
    }
    if ($request->filled('status')) {
        $q->where('status', $request->input('status'));
    }

    return response()->json($q->orderBy('detected_at', 'desc')->get([
        'id', 'rule_code', 'severity', 'order_ref_type', 'order_ref_id',
        'message', 'detail', 'status', 'detected_at',
    ]));
});
```

- [ ] **Step 2: Verify route**

Run: `php artisan route:list --name= 2>&1 | grep order-check/violations || php artisan route:list 2>&1 | grep order-check/violations`
Expected: thấy route `api/order-check/violations`.

- [ ] **Step 3: Commit** (bỏ qua nếu được yêu cầu)

```bash
git add routes/api.php
git commit -m "feat(order-check): API JSON read-only tra cuu vi pham theo dot dieu tri"
```

---

## Task 7: Regression + readme

**Files:**
- Modify: `readme.md`

- [ ] **Step 1: Regression Unit OrderCheck**

Run: `vendor/bin/phpunit tests/Unit/OrderCheck`
Expected: PASS toàn bộ (Plan 1: 14 + A4: 3 + ViolationQueryService: 2 = 19 tests).

- [ ] **Step 2: Verify toàn bộ route module**

Run: `php artisan route:list 2>&1 | grep order-check`
Expected: 5 route web + 1 route api.

- [ ] **Step 3: Cập nhật readme**

Chèn vào đầu `readme.md` (trên khối ngày gần nhất):

```markdown
# 30/06/2026 (cập nhật 2)

- Module Kiểm tra sai sót y lệnh (giai đoạn 3): dashboard KHTH "Kiểm tra sai sót y lệnh" (lọc theo ngày/khoa/mức độ/loại luật/trạng thái + KPI + DataTables), quy trình xử lý (đã xử lý/bỏ qua + ghi chú + người xử lý), xuất Excel, và API JSON tra cứu vi phạm theo đợt điều trị.

```

- [ ] **Step 4: Commit** (bỏ qua nếu được yêu cầu)

```bash
git add readme.md
git commit -m "docs(order-check): readme giai doan 3 (dashboard/workflow/export/API)"
```

---

## Self-Review (đã thực hiện khi viết plan)

**1. Spec coverage (Plan 3 = đầu ra "xem & xử lý"):**
- Dashboard + báo cáo (lọc khoa/bác sĩ/loại/mức độ/ngày + Excel) → Task 1 (filters), 3 (fetch/summary), 2 (export), 5 (view). ✅
- Workflow xử lý (new→seen→processed/false_positive + người duyệt + ghi chú) → Task 3 updateStatus + Task 1 whitelist + Task 5 nút thao tác. ✅
- API JSON read-only cho HIS → Task 6. ✅
- Menu/route/permission (checkrole:administrator) → Task 4. ✅

**2. Ngoài phạm vi (nêu rõ, không stub):** Thông báo email/SMS/Telegram → Plan 4; luật A2/A3/A5 + gender/tuổi/BHYT → Plan 5; enrich tên khoa/tên thuốc → sau.

**3. Placeholder scan:** mọi step in-scope có code/lệnh + kỳ vọng. Hai điểm cần implementer kiểm tra môi trường (KHÔNG phải placeholder, có hướng dẫn cụ thể + fallback): cột định danh user trong `app/User.php` (Task 3), nhóm middleware xác thực trong `routes/api.php` (Task 6).

**4. Type/route consistency:** tên route `khth.order-check-{index,summary,fetch,update-status,export}` khớp giữa web.php (Task 4) ↔ controller method ↔ view JS (Task 5). `ViolationQueryService::filtered()` dùng chung ở controller fetch/summary (Task 3) và export (Task 2). `UPDATABLE_STATUSES`/`isValidUpdateStatus` khớp Task 1 ↔ Task 3. Cột model khớp bảng `order_check_violations` (Plan 1). ✅

**Kiểm thử:** logic thuần (whitelist trạng thái) test PHPUnit; controller/route verify bằng `route:list` + `php -l`; UI (DataTables/workflow/export) xác minh trực quan thủ công khi đăng nhập (đúng pattern các báo cáo KHTH hiện có — không có test tự động UI).
