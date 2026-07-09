# Kíp trực lãnh đạo — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: superpowers:subagent-driven-development / executing-plans.

**Goal:** Nhập & hiển thị kíp trực lãnh đạo theo ngày (chức danh trực → acs_user + họ tên + SĐT) trên màn nhập, cấu hình danh mục chức danh, và trình chiếu. Không Excel.

**Spec:** `docs/superpowers/specs/2026-07-08-giao-ban-kip-truc-design.md`

**Đã xác minh:** endpoint `khth.giao-ban-config-search-users` (autocomplete acs_user) đã có; `show()` trả JSON cho index+present; nhóm route `khth/` `checkrole:giaoban`; `GiaoBanConfigController` có middleware `giaoban-admin` toàn controller.

---

### Task 1: Migrations + Models

**Files:**
- Create: `database/migrations/2026_07_09_100000_create_giaoban_duty_positions_table.php`
- Create: `database/migrations/2026_07_09_100001_create_giaoban_report_duties_table.php`
- Create: `app/Models/GiaoBan/GiaoBanDutyPosition.php`, `app/Models/GiaoBan/GiaoBanReportDuty.php`

- [ ] **Step 1: Migrations**

`2026_07_09_100000_create_giaoban_duty_positions_table.php`:
```php
<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateGiaobanDutyPositionsTable extends Migration
{
    public function up()
    {
        Schema::create('giaoban_duty_positions', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 255);
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('giaoban_duty_positions');
    }
}
```

`2026_07_09_100001_create_giaoban_report_duties_table.php`:
```php
<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateGiaobanReportDutiesTable extends Migration
{
    public function up()
    {
        Schema::create('giaoban_report_duties', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('report_id');
            $table->unsignedInteger('position_id');
            $table->unsignedInteger('user_id')->nullable();   // acs_user.id
            $table->string('person_name', 255)->nullable();   // snapshot ho ten
            $table->string('phone', 50)->nullable();
            $table->timestamps();
            $table->unique(['report_id', 'position_id'], 'giaoban_duty_unique');
            $table->index('report_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('giaoban_report_duties');
    }
}
```

- [ ] **Step 2: Models**

`app/Models/GiaoBan/GiaoBanDutyPosition.php`:
```php
<?php

namespace App\Models\GiaoBan;

use Illuminate\Database\Eloquent\Model;

class GiaoBanDutyPosition extends Model
{
    protected $table = 'giaoban_duty_positions';
    protected $fillable = ['name', 'sort_order', 'is_active'];
    protected $casts = ['is_active' => 'boolean'];
}
```

`app/Models/GiaoBan/GiaoBanReportDuty.php`:
```php
<?php

namespace App\Models\GiaoBan;

use Illuminate\Database\Eloquent\Model;

class GiaoBanReportDuty extends Model
{
    protected $table = 'giaoban_report_duties';
    protected $fillable = ['report_id', 'position_id', 'user_id', 'person_name', 'phone'];
    protected $casts = ['report_id' => 'integer', 'position_id' => 'integer', 'user_id' => 'integer'];
}
```

- [ ] **Step 3: Migrate + commit**

Run: `php artisan migrate` (Expected: 2 migrated).
```bash
git add database/migrations/2026_07_09_1000*.php app/Models/GiaoBan/GiaoBanDutyPosition.php app/Models/GiaoBan/GiaoBanReportDuty.php
git commit -m "feat(giao-ban): migrations + models kip truc lanh dao"
```

---

### Task 2: GiaoBanDutyService (TDD phần thuần + persistence)

**Files:**
- Create: `app/Services/GiaoBan/GiaoBanDutyService.php`
- Test: `tests/Unit/GiaoBan/GiaoBanDutyServiceTest.php`

- [ ] **Step 1: Test (hàm thuần copyRows)**

`tests/Unit/GiaoBan/GiaoBanDutyServiceTest.php`:
```php
<?php

namespace Tests\Unit\GiaoBan;

use Tests\TestCase;
use App\Services\GiaoBan\GiaoBanDutyService;

class GiaoBanDutyServiceTest extends TestCase
{
    /** @test */
    public function copy_rows_keeps_fields_and_drops_ids()
    {
        $prev = [
            (object) ['id' => 9, 'report_id' => 3, 'position_id' => 1, 'user_id' => 100, 'person_name' => 'BS A', 'phone' => '0912'],
            (object) ['id' => 10, 'report_id' => 3, 'position_id' => 2, 'user_id' => null, 'person_name' => 'BS B', 'phone' => null],
        ];
        $rows = GiaoBanDutyService::copyRows($prev, 7);
        $this->assertSame([
            ['report_id' => 7, 'position_id' => 1, 'user_id' => 100, 'person_name' => 'BS A', 'phone' => '0912'],
            ['report_id' => 7, 'position_id' => 2, 'user_id' => null, 'person_name' => 'BS B', 'phone' => null],
        ], $rows);
    }

    /** @test */
    public function copy_rows_empty_input_returns_empty()
    {
        $this->assertSame([], GiaoBanDutyService::copyRows([], 7));
    }
}
```

- [ ] **Step 2: Run FAIL** — `vendor\bin\phpunit tests\Unit\GiaoBan\GiaoBanDutyServiceTest.php`

- [ ] **Step 3: Implement**

`app/Services/GiaoBan/GiaoBanDutyService.php`:
```php
<?php

namespace App\Services\GiaoBan;

use App\Models\GiaoBan\GiaoBanReport;
use App\Models\GiaoBan\GiaoBanReportDuty;

class GiaoBanDutyService
{
    /** Thuần: chuyển kíp trực ngày trước -> mảng dòng chèn cho report mới (bỏ id/report_id cũ). */
    public static function copyRows($prevRows, $newReportId)
    {
        $out = [];
        foreach ($prevRows as $r) {
            $out[] = [
                'report_id' => (int) $newReportId,
                'position_id' => (int) $r->position_id,
                'user_id' => $r->user_id !== null ? (int) $r->user_id : null,
                'person_name' => $r->person_name,
                'phone' => $r->phone,
            ];
        }
        return $out;
    }

    /** Upsert 1 dòng kíp trực theo (report_id, position_id). */
    public function saveDuty($reportId, $positionId, $userId, $personName, $phone)
    {
        $duty = GiaoBanReportDuty::firstOrNew(['report_id' => (int) $reportId, 'position_id' => (int) $positionId]);
        $duty->user_id = $userId !== null && $userId !== '' ? (int) $userId : null;
        $duty->person_name = $personName;
        $duty->phone = $phone;
        $duty->save();
        return $duty;
    }

    /** Sao chép kíp trực từ report gần nhất TRƯỚC ngày của $report (có kíp). Trả số dòng đã copy. */
    public function copyFromPrevious(GiaoBanReport $report)
    {
        $prevReport = GiaoBanReport::where('report_date', '<', $report->report_date)
            ->whereIn('id', GiaoBanReportDuty::select('report_id'))
            ->orderBy('report_date', 'desc')->first();
        if (!$prevReport) return 0;

        $prevRows = GiaoBanReportDuty::where('report_id', $prevReport->id)->get();
        $rows = self::copyRows($prevRows, $report->id);
        foreach ($rows as $row) {
            $duty = GiaoBanReportDuty::firstOrNew(['report_id' => $row['report_id'], 'position_id' => $row['position_id']]);
            $duty->fill($row)->save();
        }
        return count($rows);
    }
}
```

- [ ] **Step 4: Run PASS** — `vendor\bin\phpunit tests\Unit\GiaoBan` (Expected: +2 tests).

- [ ] **Step 5: Commit**
```bash
git add app/Services/GiaoBan/GiaoBanDutyService.php tests/Unit/GiaoBan/GiaoBanDutyServiceTest.php
git commit -m "feat(giao-ban): GiaoBanDutyService copyRows + persistence (TDD)"
```

---

### Task 3: GiaoBanController — show(duties) + saveDuty + copyDuties + routes

**Files:**
- Modify: `app/Http/Controllers/KHTH/GiaoBanController.php`
- Modify: `routes/web.php`

- [ ] **Step 1: use + show() bổ sung positions + duties**

Ở đầu file `GiaoBanController.php`, sau các `use App\Models\GiaoBan\...` hiện có, thêm:
```php
use App\Models\GiaoBan\GiaoBanDutyPosition;
use App\Models\GiaoBan\GiaoBanReportDuty;
use App\Services\GiaoBan\GiaoBanDutyService;
```

Trong `show()`, TRƯỚC `return response()->json([`, thêm:
```php
        $positions = GiaoBanDutyPosition::where('is_active', true)->orderBy('sort_order')->get(['id', 'name']);
        $duties = [];
        if ($report) {
            foreach (GiaoBanReportDuty::where('report_id', $report->id)->get() as $d) {
                $duties[] = [
                    'position_id' => $d->position_id, 'user_id' => $d->user_id,
                    'person_name' => $d->person_name, 'phone' => $d->phone,
                ];
            }
        }
```
Và trong mảng trả về JSON, thêm 2 khóa (sau `'assigned_dept_ids' => ...`):
```php
            'duty_positions' => $positions, 'duties' => $duties,
```

- [ ] **Step 2: Thêm 2 method saveDuty + copyDuties**

Thêm vào class (VD sau `saveGeneralNote`):
```php
    /** Lưu 1 dòng kíp trực (cả admin & khoa). */
    public function saveDuty(Request $request)
    {
        $this->validate($request, [
            'date' => 'required|date_format:Y-m-d',
            'position_id' => 'required|integer',
            'user_id' => 'nullable|integer',
            'person_name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
        ]);
        $from = date('Y-m-d 07:00:00', strtotime('-1 day', strtotime($request->input('date'))));
        $to = date('Y-m-d 07:00:00', strtotime($request->input('date')));
        $report = $this->service->getOrCreateReport($request->input('date'), $from, $to, auth()->id());
        if ($report->isFinal()) {
            return response()->json(['message' => 'Báo cáo đã chốt.'], 422);
        }
        (new GiaoBanDutyService())->saveDuty(
            $report->id, $request->input('position_id'),
            $request->input('user_id'), $request->input('person_name'), $request->input('phone')
        );
        return response()->json(['ok' => true, 'report_id' => $report->id]);
    }

    /** Sao chép kíp trực từ ngày gần nhất trước đó. */
    public function copyDuties(Request $request)
    {
        $this->validate($request, ['date' => 'required|date_format:Y-m-d']);
        $from = date('Y-m-d 07:00:00', strtotime('-1 day', strtotime($request->input('date'))));
        $to = date('Y-m-d 07:00:00', strtotime($request->input('date')));
        $report = $this->service->getOrCreateReport($request->input('date'), $from, $to, auth()->id());
        if ($report->isFinal()) {
            return response()->json(['message' => 'Báo cáo đã chốt.'], 422);
        }
        $n = (new GiaoBanDutyService())->copyFromPrevious($report);
        if ($n === 0) return response()->json(['message' => 'Không có kíp trực ngày trước để sao chép.'], 422);
        return response()->json(['ok' => true, 'copied' => $n]);
    }
```

- [ ] **Step 3: Routes** — trong nhóm `checkrole:giaoban`, sau `giao-ban/save-general-note`:
```php
        Route::post('giao-ban/save-duty', 'KHTH\GiaoBanController@saveDuty')->name('khth.giao-ban-save-duty');
        Route::post('giao-ban/copy-duties', 'KHTH\GiaoBanController@copyDuties')->name('khth.giao-ban-copy-duties');
```

- [ ] **Step 4: Verify** — `php -l app/Http/Controllers/KHTH/GiaoBanController.php`; `Select-String routes/web.php -Pattern 'save-duty|copy-duties'` (2 dòng).

- [ ] **Step 5: Commit**
```bash
git add app/Http/Controllers/KHTH/GiaoBanController.php routes/web.php
git commit -m "feat(giao-ban): show duties + saveDuty/copyDuties + routes"
```

---

### Task 4: GiaoBanConfigController — danh mục chức danh trực + routes

**Files:**
- Modify: `app/Http/Controllers/KHTH/GiaoBanConfigController.php`
- Modify: `routes/web.php`

- [ ] **Step 1: use + fetch() + store/update duty position**

Thêm `use App\Models\GiaoBan\GiaoBanDutyPosition;` đầu file.

Trong `fetch()`, thêm `duty_positions` vào response. Tìm:
```php
        return response()->json(['configs' => $configs, 'assignments' => $assignments, 'user_names' => $users]);
```
Đổi thành:
```php
        $dutyPositions = GiaoBanDutyPosition::orderBy('sort_order')->get();
        return response()->json(['configs' => $configs, 'assignments' => $assignments, 'user_names' => $users, 'duty_positions' => $dutyPositions]);
```

Thêm 2 method:
```php
    public function storeDutyPosition(Request $request)
    {
        $this->validate($request, ['name' => 'required|string|max:255', 'sort_order' => 'nullable|integer']);
        $p = GiaoBanDutyPosition::create($request->only(['name', 'sort_order']) + ['is_active' => true]);
        return response()->json(['ok' => true, 'id' => $p->id]);
    }

    public function updateDutyPosition(Request $request, $id)
    {
        $p = GiaoBanDutyPosition::findOrFail($id);
        $p->update($request->only(['name', 'sort_order', 'is_active']));
        return response()->json(['ok' => true]);
    }
```

- [ ] **Step 2: Routes** — trong nhóm `checkrole:giaoban`, sau `giao-ban/cau-hinh/search-users`:
```php
        Route::post('giao-ban/cau-hinh-duty', 'KHTH\GiaoBanConfigController@storeDutyPosition')->name('khth.giao-ban-config-duty-store');
        Route::post('giao-ban/cau-hinh-duty/{id}', 'KHTH\GiaoBanConfigController@updateDutyPosition')->name('khth.giao-ban-config-duty-update');
```

- [ ] **Step 3: Verify** — `php -l ...GiaoBanConfigController.php`; grep routes 2 dòng `cau-hinh-duty`.

- [ ] **Step 4: Commit**
```bash
git add app/Http/Controllers/KHTH/GiaoBanConfigController.php routes/web.php
git commit -m "feat(giao-ban): config danh muc chuc danh truc + routes"
```

---

### Task 5: Views — khối kíp trực (index) + danh mục chức danh (config)

**Files:**
- Modify: `resources/views/khth/giaoban-index.blade.php`
- Modify: `resources/views/khth/giaoban-config.blade.php`

- [ ] **Step 1: index — thêm khối "Kíp trực lãnh đạo"**

Trong `giaoban-index.blade.php`, TRƯỚC khối "Ghi chú chung" (`<div class="box box-default">...Ghi chú chung...`), thêm:
```blade
<div class="box box-info">
  <div class="box-header with-border"><b>Kíp trực lãnh đạo</b>
    <button id="btn-copy-duty" class="btn btn-xs btn-default"><i class="fa fa-copy"></i> Sao chép kíp ngày trước</button>
  </div>
  <div class="box-body"><div id="duty-body"></div></div>
</div>
```

Trong JS `render(res)`, sau khi set ghi chú chung (`$('#general-note-view').html(...)` hoặc gần đó), thêm gọi `renderDuties(res)`. Và thêm hàm + handlers (đặt cùng khu vực các hàm JS):
```js
function renderDuties(res) {
  var editable = !(res.report && res.report.status === 'final');
  var byPos = {};
  (res.duties || []).forEach(function (d) { byPos[d.position_id] = d; });
  var $b = $('#duty-body').empty();
  if (!res.duty_positions || !res.duty_positions.length) {
    $b.html('<i class="text-muted">Chưa cấu hình chức danh trực (Cấu hình giao ban).</i>');
    return;
  }
  var html = '<table class="table table-bordered"><thead><tr><th style="width:220px">Chức danh</th><th>Người trực</th><th style="width:160px">SĐT</th></tr></thead><tbody>';
  res.duty_positions.forEach(function (p) {
    var d = byPos[p.id] || {};
    html += '<tr data-pos="' + p.id + '"><td>' + esc(p.name) + '</td>' +
      '<td><input type="text" class="form-control duty-user" data-pos="' + p.id + '" data-uid="' + (d.user_id || '') + '" value="' + esc(d.person_name || '') + '"' + (editable ? '' : ' readonly') + ' placeholder="gõ tìm tài khoản..."></td>' +
      '<td><input type="text" class="form-control duty-phone" data-pos="' + p.id + '" value="' + esc(d.phone || '') + '"' + (editable ? '' : ' readonly') + '></td></tr>';
  });
  html += '</tbody></table><div id="duty-results" class="list-group" style="position:absolute;z-index:20;max-width:400px;display:none"></div>';
  $b.html(html);
}
```

Thêm handlers trong `$(function(){ ... })` (dùng lại route search-users; lưu khi blur/chọn):
```js
  var dutyTimer = null, dutyActivePos = null;
  $('#duty-body').on('input', '.duty-user', function () {
    var $i = $(this); dutyActivePos = $i.data('pos');
    $i.data('uid', ''); // gõ tay -> reset uid, dùng person_name
    var q = $i.val();
    clearTimeout(dutyTimer);
    var $res = $('#duty-results');
    if (q.length < 2) { $res.hide(); return; }
    dutyTimer = setTimeout(function () {
      $.get('{{ route('khth.giao-ban-config-search-users') }}', { q: q }, function (rows) {
        var off = $i.offset();
        $res.empty().css({ top: off.top + $i.outerHeight(), left: off.left, width: $i.outerWidth() });
        rows.forEach(function (u) {
          $res.append('<a href="#" class="list-group-item duty-pick" data-uid="' + u.id + '" data-name="' +
            esc(u.username || u.loginname) + '">' + esc(u.username || u.loginname) + ' <small>(' + esc(u.loginname) + ')</small></a>');
        });
        $res.show();
      });
    }, 300);
  });
  $('#duty-body').on('click', '.duty-pick', function (e) {
    e.preventDefault();
    var $row = $('#duty-body tr[data-pos="' + dutyActivePos + '"]');
    var $u = $row.find('.duty-user');
    $u.val($(this).data('name')).data('uid', $(this).data('uid'));
    $('#duty-results').hide();
    saveDuty(dutyActivePos);
  });
  $('#duty-body').on('blur', '.duty-user, .duty-phone', function () { saveDuty($(this).data('pos')); });
  $(document).on('click', function (e) { if (!$(e.target).closest('#duty-body, #duty-results').length) $('#duty-results').hide(); });

  function saveDuty(posId) {
    var $row = $('#duty-body tr[data-pos="' + posId + '"]');
    var $u = $row.find('.duty-user'), $p = $row.find('.duty-phone');
    $.post('{{ route('khth.giao-ban-save-duty') }}', {
      _token: '{{ csrf_token() }}', date: $('#report_date').val(), position_id: posId,
      user_id: $u.data('uid') || '', person_name: $u.val(), phone: $p.val()
    }).fail(function (xhr) {
      alert(xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Lỗi lưu kíp trực');
    });
  }
  $('#btn-copy-duty').on('click', function () {
    $.post('{{ route('khth.giao-ban-copy-duties') }}', { _token: '{{ csrf_token() }}', date: $('#report_date').val() })
      .done(loadReport)
      .fail(function (xhr) { alert(xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Lỗi sao chép'); });
  });
```

Và trong `render(res)` gọi `renderDuties(res);` (thêm 1 dòng, ví dụ ngay sau dòng set general-note-view).

- [ ] **Step 2: config — thêm khối "Danh mục chức danh trực"**

Trong `giaoban-config.blade.php`, thêm 1 box (VD dưới box gán tài khoản, cột phải hoặc hàng mới):
```blade
<div class="row"><div class="col-md-6">
  <div class="box box-success">
    <div class="box-header with-border"><b>Danh mục chức danh trực</b></div>
    <div class="box-body table-responsive">
      <table class="table table-bordered" id="tbl-duty"><thead><tr><th style="width:70px">TT</th><th>Chức danh</th><th style="width:70px">Hoạt động</th><th style="width:60px"></th></tr></thead><tbody></tbody></table>
      <button id="btn-add-duty" class="btn btn-success"><i class="fa fa-plus"></i> Thêm chức danh</button>
    </div>
  </div>
</div></div>
```

Trong JS, sau `renderConfigs()`, thêm render + handlers cho duty positions (STATE.duty_positions từ fetch):
```js
function renderDutyPositions() {
  var $tb = $('#tbl-duty tbody').empty();
  (STATE.duty_positions || []).forEach(function (p) {
    $tb.append('<tr data-id="' + p.id + '">' +
      '<td><input class="form-control d-sort" type="number" value="' + (p.sort_order || 0) + '"></td>' +
      '<td><input class="form-control d-name" value="' + esc(p.name) + '"></td>' +
      '<td><input type="checkbox" class="d-active"' + (p.is_active ? ' checked' : '') + '></td>' +
      '<td><button class="btn btn-sm btn-primary btn-save-duty">Lưu</button></td></tr>');
  });
}
```
Trong `loadAll()` done callback, thêm `renderDutyPositions();`. Thêm handlers:
```js
  $('#btn-add-duty').on('click', function () {
    var name = prompt('Tên chức danh trực:');
    if (!name) return;
    $.post('{{ route('khth.giao-ban-config-duty-store') }}', { _token: '{{ csrf_token() }}', name: name, sort_order: (STATE.duty_positions || []).length + 1 })
      .done(loadAll);
  });
  $('#tbl-duty').on('click', '.btn-save-duty', function () {
    var $tr = $(this).closest('tr');
    $.post('{{ url('khth/giao-ban/cau-hinh-duty') }}/' + $tr.data('id'), {
      _token: '{{ csrf_token() }}', name: $tr.find('.d-name').val(),
      sort_order: $tr.find('.d-sort').val(), is_active: $tr.find('.d-active').is(':checked') ? 1 : 0
    }).done(loadAll);
  });
```

- [ ] **Step 3: Verify** — `php artisan view:clear`; render check index (`VIEW_COMPILED_OK`).

- [ ] **Step 4: Commit**
```bash
git add resources/views/khth/giaoban-index.blade.php resources/views/khth/giaoban-config.blade.php
git commit -m "feat(giao-ban): view kip truc (nhap + danh muc chuc danh)"
```

---

### Task 6: Present — khối kíp trực

**Files:**
- Modify: `resources/views/khth/giaoban-present.blade.php`

- [ ] **Step 1: Render khối kíp trực trên slide tổng quan**

Trong hàm `overviewSlide(data)`, trước dòng `return '<div class="slide">...`, dựng biến `dutyHtml`:
```js
    var duties = (data.duties || []).filter(function (d) { return (d.person_name || '').trim() !== ''; });
    var posName = {};
    (data.duty_positions || []).forEach(function (p) { posName[p.id] = p.name; });
    var dutyHtml = '';
    if (duties.length) {
      dutyHtml = '<div class="panel"><div class="lbl">KÍP TRỰC LÃNH ĐẠO</div><div style="display:flex;flex-wrap:wrap;gap:1vh 2vw">';
      duties.forEach(function (d) {
        dutyHtml += '<div style="font-size:1.9vh"><span style="color:#8aa4bd">' + esc(posName[d.position_id] || '') +
          ':</span> <b style="color:#fff">' + esc(d.person_name) + '</b>' + (d.phone ? ' <span style="color:#6ea8d8">' + esc(d.phone) + '</span>' : '') + '</div>';
      });
      dutyHtml += '</div></div>';
    }
```
Rồi chèn `dutyHtml` vào cuối phần `<div class="charts">...</div>` của slide tổng quan — sửa dòng return: tìm `'<div class="charts">' + chart + '</div></div>';` đổi thành `'<div class="charts">' + chart + '</div>' + dutyHtml + '</div>';`.

- [ ] **Step 2: Verify** — `php artisan view:clear`; kiểm không lỗi.

- [ ] **Step 3: Commit**
```bash
git add resources/views/khth/giaoban-present.blade.php
git commit -m "feat(giao-ban): trinh chieu khoi kip truc lanh dao"
```

---

### Task 7: Kiểm thử + readme

- [ ] **Step 1:** `vendor\bin\phpunit tests\Unit\GiaoBan` → PASS (33 tests: 31 + 2 duty).
- [ ] **Step 2: Đối chiếu runtime** — script: tạo 2 chức danh + 1 report ngày A có kíp trực (2 dòng), tạo report ngày B, gọi `GiaoBanDutyService::copyFromPrevious(reportB)` → 2 dòng copy đúng position/user/name/phone. Dọn dữ liệu.
- [ ] **Step 3: readme + commit**

Thêm đầu `readme.md`:
```markdown
# 09/07/2026

- Báo cáo giao ban: bổ sung Kíp trực lãnh đạo — cấu hình danh mục chức danh trực; nhập người trực (chọn tài khoản HIS) + SĐT theo ngày, nút sao chép kíp ngày trước; hiển thị trên trình chiếu.
```
```bash
git add readme.md
git commit -m "docs(giao-ban): readme kip truc lanh dao"
```
