# Nâng cấp kíp trực (his_employee + nhiều người + phân quyền) — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: superpowers:subagent-driven-development / executing-plans.

**Goal:** Kíp trực: tìm người từ his_employee, nhiều người/chức danh, phân quyền cập nhật theo user (giống gán khoa). Trên branch `feature/giao-ban-kip-truc` (chưa merge) — gom migration.

**Spec:** `docs/superpowers/specs/2026-07-09-giao-ban-kip-truc-nangcap-design.md`

**Đã có trên branch:** models `GiaoBanDutyPosition`, `GiaoBanReportDuty` (user_id), `GiaoBanDutyService` (copyRows/saveDuty/copyFromPrevious), controller endpoints saveDuty/copyDuties, view kíp trực (single-person), present duty block. Migration `2026_07_09_100000` (positions), `100001` (report_duties: user_id + unique).

**his_employee (HISPro):** `id, tdl_username, tdl_mobile, title, employee_code, is_active, is_delete`.

---

### Task 1: Migration gom + models (reset DB dev)

- [ ] **Step 1: Sửa `database/migrations/2026_07_09_100001_create_giaoban_report_duties_table.php`** — thay `up()`:
```php
    public function up()
    {
        Schema::create('giaoban_report_duties', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('report_id');
            $table->unsignedInteger('position_id');
            $table->unsignedInteger('employee_id')->nullable(); // his_employee.id
            $table->string('person_name', 255)->nullable();
            $table->string('phone', 50)->nullable();
            $table->timestamps();
            $table->index('report_id');
            $table->index(['report_id', 'position_id']);
        });
    }
```

- [ ] **Step 2: Thêm `database/migrations/2026_07_09_100002_create_giaoban_duty_editors_table.php`:**
```php
<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateGiaobanDutyEditorsTable extends Migration
{
    public function up()
    {
        Schema::create('giaoban_duty_editors', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('user_id')->unique(); // acs_user.id
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('giaoban_duty_editors');
    }
}
```

- [ ] **Step 3: Reset DB dev** — script `scratchpad/reset_duty2.php`:
```php
<?php
require 'C:/Users/tracnn/qlbv/vendor/autoload.php';
$app = require 'C:/Users/tracnn/qlbv/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Illuminate\Support\Facades\Schema; use Illuminate\Support\Facades\DB;
Schema::dropIfExists('giaoban_report_duties');
Schema::dropIfExists('giaoban_duty_editors');
DB::table('migrations')->whereIn('migration', ['2026_07_09_100001_create_giaoban_report_duties_table','2026_07_09_100002_create_giaoban_duty_editors_table'])->delete();
echo "reset done\n";
```
Run: `php scratchpad/reset_duty2.php` then `php artisan migrate` (2 migrated).

- [ ] **Step 4: Model `GiaoBanReportDuty`** — đổi fillable/cast user_id→employee_id:
```php
    protected $fillable = ['report_id', 'position_id', 'employee_id', 'person_name', 'phone'];
    protected $casts = ['report_id' => 'integer', 'position_id' => 'integer', 'employee_id' => 'integer'];
```

- [ ] **Step 5: Model mới `app/Models/GiaoBan/GiaoBanDutyEditor.php`:**
```php
<?php

namespace App\Models\GiaoBan;

use Illuminate\Database\Eloquent\Model;

class GiaoBanDutyEditor extends Model
{
    protected $table = 'giaoban_duty_editors';
    protected $fillable = ['user_id'];
    protected $casts = ['user_id' => 'integer'];
}
```

- [ ] **Step 6: Commit** — `git add -A database/migrations app/Models/GiaoBan; git commit -m "feat(giao-ban): kip truc employee_id + bang duty_editors (gom migration)"`

---

### Task 2: GiaoBanDutyService — canEdit + add/remove/updatePhone (TDD)

- [ ] **Step 1: Sửa test `tests/Unit/GiaoBan/GiaoBanDutyServiceTest.php`** — đổi copyRows dùng employee_id + thêm canEdit:
```php
    /** @test */
    public function copy_rows_keeps_fields_and_drops_ids()
    {
        $prev = [
            (object) ['id' => 9, 'report_id' => 3, 'position_id' => 1, 'employee_id' => 100, 'person_name' => 'BS A', 'phone' => '0912'],
            (object) ['id' => 10, 'report_id' => 3, 'position_id' => 2, 'employee_id' => null, 'person_name' => 'BS B', 'phone' => null],
        ];
        $rows = GiaoBanDutyService::copyRows($prev, 7);
        $this->assertSame([
            ['report_id' => 7, 'position_id' => 1, 'employee_id' => 100, 'person_name' => 'BS A', 'phone' => '0912'],
            ['report_id' => 7, 'position_id' => 2, 'employee_id' => null, 'person_name' => 'BS B', 'phone' => null],
        ], $rows);
    }

    /** @test */
    public function copy_rows_empty_input_returns_empty()
    {
        $this->assertSame([], GiaoBanDutyService::copyRows([], 7));
    }

    /** @test */
    public function can_edit_admin_or_in_editor_list()
    {
        $this->assertTrue(GiaoBanDutyService::canEdit(true, [], 5));
        $this->assertTrue(GiaoBanDutyService::canEdit(false, [3, 5], 5));
        $this->assertFalse(GiaoBanDutyService::canEdit(false, [3, 5], 9));
    }
```

- [ ] **Step 2: Run FAIL.**

- [ ] **Step 3: Sửa `app/Services/GiaoBan/GiaoBanDutyService.php`:**
```php
<?php

namespace App\Services\GiaoBan;

use App\Models\GiaoBan\GiaoBanReport;
use App\Models\GiaoBan\GiaoBanReportDuty;
use App\Models\GiaoBan\GiaoBanDutyEditor;

class GiaoBanDutyService
{
    /** Thuần: chuyển kíp trực ngày trước -> mảng dòng chèn cho report mới. */
    public static function copyRows($prevRows, $newReportId)
    {
        $out = [];
        foreach ($prevRows as $r) {
            $out[] = [
                'report_id' => (int) $newReportId,
                'position_id' => (int) $r->position_id,
                'employee_id' => $r->employee_id !== null ? (int) $r->employee_id : null,
                'person_name' => $r->person_name,
                'phone' => $r->phone,
            ];
        }
        return $out;
    }

    /** Thuần: quyền sửa kíp trực (admin hoặc trong danh sách editor). */
    public static function canEdit($isAdmin, array $editorUserIds, $userId)
    {
        if ($isAdmin) return true;
        return in_array((int) $userId, array_map('intval', $editorUserIds), true);
    }

    /** Thêm 1 người vào chức danh (bỏ qua nếu employee đã có trong chức danh đó). */
    public function addDuty($reportId, $positionId, $employeeId, $personName, $phone)
    {
        $employeeId = $employeeId !== null && $employeeId !== '' ? (int) $employeeId : null;
        if ($employeeId !== null) {
            $exists = GiaoBanReportDuty::where('report_id', $reportId)->where('position_id', $positionId)
                ->where('employee_id', $employeeId)->first();
            if ($exists) return $exists;
        }
        return GiaoBanReportDuty::create([
            'report_id' => (int) $reportId, 'position_id' => (int) $positionId,
            'employee_id' => $employeeId, 'person_name' => $personName, 'phone' => $phone,
        ]);
    }

    public function removeDuty($dutyId)
    {
        return GiaoBanReportDuty::where('id', (int) $dutyId)->delete();
    }

    public function updatePhone($dutyId, $phone)
    {
        $d = GiaoBanReportDuty::find((int) $dutyId);
        if ($d) { $d->phone = $phone; $d->save(); }
        return $d;
    }

    /** Sao chép kíp trực từ report gần nhất TRƯỚC ngày (có kíp). Trả số dòng copy. */
    public function copyFromPrevious(GiaoBanReport $report)
    {
        $prevReport = GiaoBanReport::where('report_date', '<', $report->report_date)
            ->whereIn('id', GiaoBanReportDuty::select('report_id'))
            ->orderBy('report_date', 'desc')->first();
        if (!$prevReport) return 0;
        $rows = self::copyRows(GiaoBanReportDuty::where('report_id', $prevReport->id)->get(), $report->id);
        foreach ($rows as $row) GiaoBanReportDuty::create($row);
        return count($rows);
    }

    public function editorUserIds()
    {
        return GiaoBanDutyEditor::pluck('user_id')->map(function ($x) { return (int) $x; })->all();
    }
}
```
LƯU Ý: bỏ method `saveDuty` cũ (thay bằng addDuty/removeDuty/updatePhone).

- [ ] **Step 4: Run PASS** (`vendor\bin\phpunit tests\Unit\GiaoBan`). **Step 5: Commit.**

---

### Task 3: GiaoBanController — searchEmployees + add/remove/updatePhone + canEditDuty + show + routes

- [ ] **Step 1:** use thêm `GiaoBanDutyEditor`. Sửa show(): duties trả `employee_id` (thay user_id) + nhiều dòng; thêm khóa `can_edit_duty`:
```php
        $duties = [];
        if ($report) {
            foreach (GiaoBanReportDuty::where('report_id', $report->id)->orderBy('position_id')->orderBy('id')->get() as $d) {
                $duties[] = ['id' => $d->id, 'position_id' => $d->position_id,
                    'employee_id' => $d->employee_id, 'person_name' => $d->person_name, 'phone' => $d->phone];
            }
        }
```
Và trong JSON return thêm `'can_edit_duty' => $this->canEditDuty(),` (giữ duty_positions).

- [ ] **Step 2: Thêm helper + 4 endpoint** (thay saveDuty cũ):
```php
    protected function canEditDuty()
    {
        return GiaoBanDutyService::canEdit($this->isAdmin(),
            \App\Models\GiaoBan\GiaoBanDutyEditor::pluck('user_id')->all(), auth()->id());
    }

    public function searchEmployees(Request $request)
    {
        $q = strtolower(trim((string) $request->input('q', '')));
        if (mb_strlen($q) < 2) return response()->json([]);
        try {
            $rows = \Illuminate\Support\Facades\DB::connection('HISPro')->select(
                "SELECT * FROM (
                    SELECT id, tdl_username, tdl_mobile, title FROM his_employee
                    WHERE is_delete = 0 AND is_active = 1
                      AND (LOWER(tdl_username) LIKE :q1 OR LOWER(employee_code) LIKE :q2)
                    ORDER BY tdl_username
                 ) WHERE ROWNUM <= 20",
                ['q1' => '%' . $q . '%', 'q2' => '%' . $q . '%']
            );
        } catch (\Exception $e) { return response()->json([]); }
        $out = array_map(function ($r) {
            $u = (object) array_change_key_case((array) $r, CASE_LOWER);
            return ['id' => (int) $u->id, 'name' => $u->tdl_username, 'phone' => $u->tdl_mobile, 'title' => $u->title];
        }, $rows);
        return response()->json($out);
    }

    protected function reportForDuty($date)
    {
        $from = date('Y-m-d 07:00:00', strtotime('-1 day', strtotime($date)));
        $to = date('Y-m-d 07:00:00', strtotime($date));
        return $this->service->getOrCreateReport($date, $from, $to, auth()->id());
    }

    public function addDuty(Request $request)
    {
        if (!$this->canEditDuty()) abort(403);
        $this->validate($request, [
            'date' => 'required|date_format:Y-m-d', 'position_id' => 'required|integer',
            'employee_id' => 'nullable|integer', 'person_name' => 'nullable|string|max:255', 'phone' => 'nullable|string|max:50',
        ]);
        $report = $this->reportForDuty($request->input('date'));
        if ($report->isFinal()) return response()->json(['message' => 'Báo cáo đã chốt.'], 422);
        $d = (new GiaoBanDutyService())->addDuty($report->id, $request->input('position_id'),
            $request->input('employee_id'), $request->input('person_name'), $request->input('phone'));
        return response()->json(['ok' => true, 'id' => $d->id]);
    }

    public function removeDuty(Request $request)
    {
        if (!$this->canEditDuty()) abort(403);
        $this->validate($request, ['duty_id' => 'required|integer']);
        $duty = GiaoBanReportDuty::find($request->input('duty_id'));
        if ($duty) {
            $report = GiaoBanReport::find($duty->report_id);
            if ($report && $report->isFinal()) return response()->json(['message' => 'Báo cáo đã chốt.'], 422);
            (new GiaoBanDutyService())->removeDuty($duty->id);
        }
        return response()->json(['ok' => true]);
    }

    public function updateDutyPhone(Request $request)
    {
        if (!$this->canEditDuty()) abort(403);
        $this->validate($request, ['duty_id' => 'required|integer', 'phone' => 'nullable|string|max:50']);
        $duty = GiaoBanReportDuty::find($request->input('duty_id'));
        if ($duty) {
            $report = GiaoBanReport::find($duty->report_id);
            if ($report && $report->isFinal()) return response()->json(['message' => 'Báo cáo đã chốt.'], 422);
            (new GiaoBanDutyService())->updatePhone($duty->id, $request->input('phone'));
        }
        return response()->json(['ok' => true]);
    }
```
Sửa `copyDuties`: thêm `if (!$this->canEditDuty()) abort(403);` ở đầu.

- [ ] **Step 3: Routes** — thay `giao-ban/save-duty` bằng:
```php
        Route::get('giao-ban/search-employees', 'KHTH\GiaoBanController@searchEmployees')->name('khth.giao-ban-search-employees');
        Route::post('giao-ban/add-duty', 'KHTH\GiaoBanController@addDuty')->name('khth.giao-ban-add-duty');
        Route::post('giao-ban/remove-duty', 'KHTH\GiaoBanController@removeDuty')->name('khth.giao-ban-remove-duty');
        Route::post('giao-ban/update-duty-phone', 'KHTH\GiaoBanController@updateDutyPhone')->name('khth.giao-ban-update-duty-phone');
```
(Giữ `giao-ban/copy-duties`.)

- [ ] **Step 4: Verify + Commit.**

---

### Task 4: GiaoBanConfigController — duty_editors

- [ ] `fetch()`: thêm `duty_editors` (danh sách user_id + tên acs_user). Method `assignDutyEditors(Request)` ghi lại toàn bộ `giaoban_duty_editors` từ `user_ids[]`. Route `POST giao-ban/cau-hinh-duty-editors`.
```php
    public function assignDutyEditors(Request $request)
    {
        $this->validate($request, ['user_ids' => 'nullable|array']);
        \App\Models\GiaoBan\GiaoBanDutyEditor::query()->delete();
        foreach ((array) $request->input('user_ids', []) as $uid) {
            \App\Models\GiaoBan\GiaoBanDutyEditor::create(['user_id' => (int) $uid]);
        }
        return response()->json(['ok' => true]);
    }
```
`fetch()` thêm:
```php
        $editorIds = \App\Models\GiaoBan\GiaoBanDutyEditor::pluck('user_id')->all();
        $editorNames = [];
        if (count($editorIds)) {
            $ids = implode(',', array_map('intval', $editorIds));
            try {
                foreach (DB::connection('ACS_RS')->select("SELECT id, loginname, username FROM acs_user WHERE id IN ($ids)") as $r) {
                    $u = (object) array_change_key_case((array) $r, CASE_LOWER);
                    $editorNames[(int) $u->id] = $u->username ?: $u->loginname;
                }
            } catch (\Exception $e) {}
        }
```
và trả thêm `'duty_editors' => $editorIds, 'duty_editor_names' => $editorNames` trong JSON của `fetch()`.

- [ ] Commit.

---

### Task 5: View index — chip nhiều người + his_employee autocomplete + phân quyền

- [ ] Viết lại `renderDuties(res)`: mỗi chức danh 1 khối; danh sách chip người (tên + input SĐT + ✕) dựng từ `res.duties` lọc theo position; nếu `res.can_edit_duty && !final` thêm ô "thêm người" (class `duty-add`, data-pos). Kết quả autocomplete `#duty-results` normal-flow (như đã fix). Handlers:
  - input `.duty-add` → `$.get(search-employees, {q})` → list `.emp-pick` (data-id/name/phone).
  - mousedown `.emp-pick` → `$.post(add-duty, {date, position_id, employee_id, person_name, phone})` → done: loadReport.
  - click `.duty-remove` (✕) → `$.post(remove-duty, {duty_id})` → loadReport.
  - blur `.duty-phone` (input SĐT của chip) → `$.post(update-duty-phone, {duty_id, phone})`.
- Tất cả qua `esc()`. Chỉ hiện add/✕/sửa SĐT khi `can_edit_duty && status!=final`.
- Cập nhật `saveDuty()` cũ → bỏ; bỏ handler `.duty-user` cũ.

(Chi tiết JS đầy đủ do người thực thi viết theo pattern autocomplete gán khoa hiện có — normal-flow results, mousedown pick. Kiểm compile bằng render_index.php.)

- [ ] Commit.

---

### Task 6: View config — người được cập nhật kíp trực

- [ ] Thêm box "Người được cập nhật kíp trực": ô autocomplete acs_user (search-users) → thêm chip user (id+tên) vào danh sách `#duty-editors`; ✕ xóa; nút "Lưu" POST `cau-hinh-duty-editors` với `user_ids[]`. Nạp sẵn từ `STATE.duty_editors` + `STATE.duty_editor_names` khi loadAll. Escape.
- [ ] Commit.

---

### Task 7: Present — nhiều người/chức danh

- [ ] Trong `overviewSlide`, đổi khối kíp trực: gom `data.duties` theo position → mỗi position liệt kê nhiều người "name (phone), name2 (phone2)". Lọc bỏ chức danh không có người. Escape.
- [ ] Commit.

---

### Task 8: Verify + readme
- [ ] `vendor\bin\phpunit tests\Unit\GiaoBan` PASS (34 tests).
- [ ] Runtime: search-employees trả his_employee (đối chiếu 'an' → có kết quả); add 2 người 1 chức danh → show 2 dòng; canEdit chặn user ngoài editor; copyFromPrevious nhiều người. Dọn data.
- [ ] Present render nhiều người (Node).
- [ ] readme cập nhật + commit.
