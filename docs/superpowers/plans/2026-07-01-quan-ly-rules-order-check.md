# Trang quản lý quy tắc kiểm tra y lệnh — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) hoặc superpowers:executing-plans. Steps dùng checkbox (`- [ ]`).

**Goal:** Thêm trang KHTH quản lý `order_check_rules` — bật/tắt `is_active`, sửa `severity`, sửa `name` (không tạo/xóa).

**Architecture:** Controller `OrderCheckRuleController` (dùng model `OrderCheckRule` sẵn có) + view DataTables server-side + form sửa, theo đúng khuôn trang "Danh mục giới hạn DV" (`OrderCheckRefController`). Route trong nhóm `khth/` (`checkrole:administrator`) + menu adminlte. Không migration/model mới.

**Tech Stack:** PHP 7 / Laravel 5.5, Eloquent (MySQL), Yajra Datatables, AdminLTE/Blade, laratrust.

**Tham chiếu:** spec `docs/superpowers/specs/2026-07-01-quan-ly-rules-order-check-design.md`; mẫu `app/Http/Controllers/KHTH/OrderCheckRefController.php` + `resources/views/khth/order-check-ref.blade.php`.

## Bối cảnh (KHÔNG tạo lại)
- Model `App\Models\OrderCheck\OrderCheckRule` (bảng `order_check_rules`, cast `is_active`→boolean).
- Route group `Route::group(['prefix' => 'khth/', 'middleware' => ['checkrole:administrator']]` trong `routes/web.php` (đã có các route order-check).
- Menu trong `config/adminlte.php` (đã có 2 mục order-check + 1 mục "Danh mục giới hạn DV").

## File Structure
**Tạo mới:**
- `app/Http/Controllers/KHTH/OrderCheckRuleController.php`
- `resources/views/khth/order-check-rule.blade.php`
- `tests/Unit/OrderCheck/OrderCheckRuleSeverityTest.php`
**Sửa:**
- `routes/web.php`, `config/adminlte.php`

---

## Task 1: Controller + test whitelist severity

**Files:**
- Create: `app/Http/Controllers/KHTH/OrderCheckRuleController.php`
- Test: `tests/Unit/OrderCheck/OrderCheckRuleSeverityTest.php`

- [ ] **Step 1: Viết test thất bại**

```php
<?php

namespace Tests\Unit\OrderCheck;

use Tests\TestCase;
use App\Http\Controllers\KHTH\OrderCheckRuleController;

class OrderCheckRuleSeverityTest extends TestCase
{
    public function test_severity_whitelist_dung_3_muc()
    {
        $s = OrderCheckRuleController::SEVERITIES;
        $this->assertSame(['info', 'warning', 'critical'], $s);
        $this->assertContains('critical', $s);
        $this->assertNotContains('xxx', $s);
    }
}
```

- [ ] **Step 2: Chạy test → FAIL**

Run: `vendor/bin/phpunit --filter OrderCheckRuleSeverityTest`
Expected: FAIL với "Class '...OrderCheckRuleController' not found"

- [ ] **Step 3: Tạo controller**

```php
<?php

namespace App\Http\Controllers\KHTH;

use App\Http\Controllers\Controller;
use App\Models\OrderCheck\OrderCheckRule;
use Illuminate\Http\Request;
use Yajra\Datatables\Datatables;

class OrderCheckRuleController extends Controller
{
    const SEVERITIES = ['info', 'warning', 'critical'];

    public function index()
    {
        return view('khth.order-check-rule');
    }

    public function fetch()
    {
        return Datatables::of(OrderCheckRule::query()->orderBy('family')->orderBy('code'))
            ->addColumn('severity_badge', function ($r) {
                $map = [
                    'critical' => '<span class="label label-danger">Nghiêm trọng</span>',
                    'warning' => '<span class="label label-warning">Cảnh báo</span>',
                    'info' => '<span class="label label-info">Thông tin</span>',
                ];
                return isset($map[$r->severity]) ? $map[$r->severity] : e($r->severity);
            })
            ->addColumn('active_text', function ($r) {
                return $r->is_active
                    ? '<span class="label label-success">Bật</span>'
                    : '<span class="label label-default">Tắt</span>';
            })
            ->editColumn('updated_at', function ($r) {
                return $r->updated_at ? $r->updated_at->format('d/m/Y H:i') : '';
            })
            ->addColumn('actions', function ($r) {
                $label = $r->is_active ? 'Tắt' : 'Bật';
                $cls = $r->is_active ? 'btn-default' : 'btn-success';
                return '<button class="btn btn-xs btn-primary rule-edit" data-id="' . $r->id . '">Sửa</button> '
                    . '<button class="btn btn-xs ' . $cls . ' rule-toggle" data-id="' . $r->id . '">' . $label . '</button>';
            })
            ->rawColumns(['severity_badge', 'active_text', 'actions'])
            ->make(true);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'severity' => 'required|in:' . implode(',', self::SEVERITIES),
        ]);
        $rule = OrderCheckRule::findOrFail($id);
        $rule->name = $request->input('name');
        $rule->severity = $request->input('severity');
        $rule->is_active = $request->input('is_active') ? 1 : 0;
        $rule->save();
        return response()->json(['ok' => true]);
    }

    public function toggle(Request $request, $id)
    {
        $rule = OrderCheckRule::findOrFail($id);
        $rule->is_active = $rule->is_active ? 0 : 1;
        $rule->save();
        return response()->json(['ok' => true, 'is_active' => (bool) $rule->is_active]);
    }
}
```

- [ ] **Step 4: Chạy test → PASS**

Run: `vendor/bin/phpunit --filter OrderCheckRuleSeverityTest`
Expected: PASS (1 test)

Run: `php -l app/Http/Controllers/KHTH/OrderCheckRuleController.php`
Expected: `No syntax errors detected`

- [ ] **Step 5: Commit** (bỏ qua nếu người điều phối yêu cầu không commit)

```bash
git add app/Http/Controllers/KHTH/OrderCheckRuleController.php tests/Unit/OrderCheck/OrderCheckRuleSeverityTest.php
git commit -m "feat(order-check): controller quan ly rules (fetch/update/toggle) + test severity"
```

---

## Task 2: Route + Menu

**Files:**
- Modify: `routes/web.php` (trong group `prefix 'khth/'`, `checkrole:administrator`)
- Modify: `config/adminlte.php`

- [ ] **Step 1: Thêm route**

Trong `routes/web.php`, tìm dòng route `khth.order-check-ref-index` (nhóm `khth/`) và thêm NGAY SAU khối route order-check-ref:

```php
        /* Quản lý quy tắc kiểm tra y lệnh */
        Route::get('order-check-rule-index', 'KHTH\OrderCheckRuleController@index')->name('khth.order-check-rule-index');
        Route::get('order-check-rule-index/fetch', 'KHTH\OrderCheckRuleController@fetch')->name('khth.order-check-rule-fetch');
        Route::post('order-check-rule-index/{id}', 'KHTH\OrderCheckRuleController@update')->name('khth.order-check-rule-update');
        Route::post('order-check-rule-index/{id}/toggle', 'KHTH\OrderCheckRuleController@toggle')->name('khth.order-check-rule-toggle');
```

- [ ] **Step 2: Thêm menu**

Trong `config/adminlte.php`, tìm mục `'route' => 'khth.order-check-ref-index',` và thêm NGAY SAU block đó (cùng mảng `submenu`):

```php
                [
                    'text'      => 'Quản lý quy tắc kiểm tra',
                    'icon'      => 'sliders',
                    'checkrole' => 'administrator',
                    'route'     => 'khth.order-check-rule-index',
                    'active'    => ['khth/order-check-rule-index*'],
                ],
```

- [ ] **Step 3: Verify route**

Run: `php -d memory_limit=-1 artisan route:list 2>&1 | grep order-check-rule`
Expected: 4 route `khth.order-check-rule-*` (index/fetch/update/toggle).

- [ ] **Step 4: Commit** (bỏ qua nếu được yêu cầu)

```bash
git add routes/web.php config/adminlte.php
git commit -m "feat(order-check): route + menu quan ly quy tac"
```

---

## Task 3: View

**Files:**
- Create: `resources/views/khth/order-check-rule.blade.php`

- [ ] **Step 1: Tạo view**

```blade
@extends('adminlte::page')
@section('title', 'Quản lý quy tắc kiểm tra')
@section('content_header')<h1>Quản lý quy tắc kiểm tra y lệnh</h1>@stop

@section('content')
<div class="box box-primary"><div class="box-body">
  <form id="rule-form" class="row">
    <input type="hidden" id="rule-id">
    <div class="col-md-2"><label>Mã luật</label><input id="f-code" class="form-control" readonly></div>
    <div class="col-md-4"><label>Tên hiển thị *</label><input id="f-name" class="form-control" required></div>
    <div class="col-md-2"><label>Mức độ</label>
      <select id="f-severity" class="form-control">
        <option value="info">Thông tin</option><option value="warning">Cảnh báo</option><option value="critical">Nghiêm trọng</option>
      </select>
    </div>
    <div class="col-md-2"><label>Trạng thái</label><br><label><input type="checkbox" id="f-active"> Bật</label></div>
    <div class="col-md-2"><label>&nbsp;</label><br>
      <button type="submit" class="btn btn-primary">Lưu</button>
      <button type="button" id="f-cancel" class="btn btn-default">Hủy</button>
    </div>
  </form>
  <p class="text-muted" style="margin-top:8px">Bấm <b>Sửa</b> ở bảng dưới để chọn quy tắc; chỉ sửa được Tên/Mức độ/Trạng thái. Mã luật &amp; class xử lý cố định theo code.</p>
</div></div>

<div class="box"><div class="box-body table-responsive">
  <table id="rule-table" class="table table-bordered table-hover" width="100%">
    <thead><tr><th>Họ</th><th>Mã luật</th><th>Loại (class)</th><th>Tên</th><th>Mức độ</th><th>Trạng thái</th><th>Cập nhật</th><th>Thao tác</th></tr></thead>
  </table>
</div></div>
@stop

@push('after-scripts')
<script>
var t = null;
function reset(){ $('#rule-id').val(''); $('#f-code').val(''); $('#f-name').val(''); $('#f-severity').val('warning'); $('#f-active').prop('checked', true); }

$(function(){
  t = $('#rule-table').DataTable({
    processing:true, serverSide:true, order:[[0,'asc']],
    ajax:"{{ route('khth.order-check-rule-fetch') }}",
    columns:[
      {data:'family'},{data:'code'},{data:'rule_type'},{data:'name'},
      {data:'severity_badge'},{data:'active_text'},{data:'updated_at'},
      {data:'actions',orderable:false,searchable:false}
    ]
  });

  $('#rule-form').on('submit', function(e){
    e.preventDefault();
    var id=$('#rule-id').val();
    if(!id){ alert('Chọn một quy tắc để sửa (bấm "Sửa" ở bảng dưới).'); return; }
    $.ajax({ url:"{{ url('khth/order-check-rule-index') }}/"+id, method:'POST',
      data:{ _token:"{{ csrf_token() }}", name:$('#f-name').val(), severity:$('#f-severity').val(), is_active:$('#f-active').is(':checked')?1:0 },
      success:function(){ reset(); t.ajax.reload(); },
      error:function(x){ alert(x.responseJSON ? JSON.stringify(x.responseJSON) : 'Lỗi'); }
    });
  });

  $('#f-cancel').on('click', reset);

  $(document).on('click','.rule-edit', function(){
    var row = t.row($(this).closest('tr')).data();
    $('#rule-id').val(row.id); $('#f-code').val(row.code);
    $('#f-name').val(row.name); $('#f-severity').val(row.severity);
    $('#f-active').prop('checked', row.is_active===true || row.is_active==1);
  });

  $(document).on('click','.rule-toggle', function(){
    var id=$(this).data('id');
    $.ajax({ url:"{{ url('khth/order-check-rule-index') }}/"+id+"/toggle", method:'POST',
      data:{ _token:"{{ csrf_token() }}" }, success:function(){ t.ajax.reload(); },
      error:function(){ alert('Lỗi cập nhật trạng thái'); }
    });
  });
});
</script>
@endpush
```

- [ ] **Step 2: Verify route index**

Run: `php -d memory_limit=-1 artisan route:list --name=order-check-rule-index 2>&1 | head`
Expected: thấy `khth.order-check-rule-index`.

Xác minh trực quan (thủ công, đăng nhập role administrator): mở `/khth/order-check-rule-index` → thấy bảng 9 quy tắc; bấm **Tắt** một luật → đổi trạng thái; bấm **Sửa** → đổi tên/mức độ → Lưu → cập nhật.

- [ ] **Step 3: Commit** (bỏ qua nếu được yêu cầu)

```bash
git add resources/views/khth/order-check-rule.blade.php
git commit -m "feat(order-check): view quan ly quy tac (DataTables + form sua + toggle)"
```

---

## Task 4: Verify e2e + regression

**Files:** (không sửa file mới)

- [ ] **Step 1: Regression Unit OrderCheck**

Run: `vendor/bin/phpunit tests/Unit/OrderCheck`
Expected: PASS toàn bộ (37 cũ + OrderCheckRuleSeverity: 1 = 38 tests).

- [ ] **Step 2: Verify toggle/update qua tinker (không cần UI)**

Tạo file tạm `verify_rule.php`, chạy `php verify_rule.php`, xóa sau:
```php
<?php
require __DIR__.'/vendor/autoload.php'; $app=require __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$c = $app->make(App\Http\Controllers\KHTH\OrderCheckRuleController::class);
$rule = App\Models\OrderCheck\OrderCheckRule::first();
$before = (bool) $rule->is_active;
$c->toggle(new Illuminate\Http\Request(), $rule->id);
$after = (bool) App\Models\OrderCheck\OrderCheckRule::find($rule->id)->is_active;
echo "toggle: $before -> $after (khac nhau: ".($before!==$after?'OK':'FAIL').")".PHP_EOL;
// toggle lai ve cu
$c->toggle(new Illuminate\Http\Request(), $rule->id);
$req = Illuminate\Http\Request::create('/','POST',['name'=>$rule->name,'severity'=>'critical','is_active'=>1]);
$c->update($req, $rule->id);
echo "severity sau update = ".App\Models\OrderCheck\OrderCheckRule::find($rule->id)->severity.PHP_EOL;
// tra ve severity cu
$req2 = Illuminate\Http\Request::create('/','POST',['name'=>$rule->name,'severity'=>$rule->severity,'is_active'=>$rule->is_active?1:0]);
$c->update($req2, $rule->id);
```
Expected: `toggle: ... (khac nhau: OK)` và `severity sau update = critical`.

- [ ] **Step 3: Commit** (nếu có thay đổi phát sinh; thường không). Không bắt buộc.

---

## Self-Review (đã thực hiện khi viết plan)

**1. Spec coverage:**
- Controller (index/fetch/update/toggle), chỉ sửa name/severity/is_active → Task 1. ✅
- Route 4 endpoint + menu (checkrole administrator) → Task 2. ✅
- View bảng + form sửa + toggle → Task 3. ✅
- Không tạo/xóa, không params/scope, không migration → không có task nào làm (đúng). ✅
- Test whitelist severity → Task 1; verify toggle/update → Task 4. ✅

**2. Placeholder scan:** mọi step có code/lệnh + kỳ vọng cụ thể; UI verify là thủ công có mô tả (đúng pattern KHTH). Không placeholder.

**3. Type consistency:** `OrderCheckRuleController::SEVERITIES` (Task 1) khớp test (Task 1) + validate `in:` (Task 1). Route name `khth.order-check-rule-{index,fetch,update,toggle}` (Task 2) khớp controller method ↔ view JS (Task 3). Cột DataTable `family/code/rule_type/name/severity_badge/active_text/updated_at/actions` (Task 3) khớp `addColumn`/`editColumn` trong fetch (Task 1). `row.id/code/name/severity/is_active` (view edit) là thuộc tính model trả trong JSON. ✅

**Lưu ý:** `code`/`rule_type`/`family` chỉ đọc (server chỉ nhận name/severity/is_active). `is_active` cast boolean → JSON true/false; view kiểm cả `===true` và `==1`.
