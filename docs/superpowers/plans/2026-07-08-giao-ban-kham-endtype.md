# Khối khám theo loại ra viện + gom migration — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: superpowers:subagent-driven-development / executing-plans.

**Goal:** Khối khám thống kê theo loại ra viện (treatment_end_type_id); gom migration giao ban còn 5 file.

**Spec:** `docs/superpowers/specs/2026-07-08-giao-ban-kham-endtype-design.md`

---

### Task 1: buildExamVisitSql — filter `end_type_codes` (TDD)

**Files:** `app/Services/GiaoBan/GiaoBanMetricService.php`, `tests/Unit/GiaoBan/GiaoBanMetricServiceTest.php`

- [ ] **Step 1: Test** (thêm vào class):
```php
    /** @test */
    public function exam_visit_sql_filters_by_end_type_code()
    {
        list($sql, $binds) = $this->svc->buildExamVisitSql('2026-07-07 07:00:00', '2026-07-08 07:00:00', [27],
            ['end_type_codes' => ['CC', 'CV']]);
        $this->assertContains('JOIN his_treatment t ON t.id = sr.treatment_id', $sql);
        $this->assertContains('JOIN his_treatment_end_type et ON et.id = t.treatment_end_type_id', $sql);
        $this->assertContains("et.treatment_end_type_code IN ('CC','CV')", $sql);
    }

    /** @test */
    public function exam_visit_end_type_codes_whitelisted()
    {
        list($sql, $binds) = $this->svc->buildExamVisitSql('2026-07-07 07:00:00', '2026-07-08 07:00:00', [27],
            ['end_type_codes' => ["CC'; DROP--", 'cv']]);
        $this->assertContains("et.treatment_end_type_code IN ('CC','CV')", $sql);
        $this->assertNotContains('DROP', $sql);
    }
```

- [ ] **Step 2: Run FAIL** — `vendor\bin\phpunit tests\Unit\GiaoBan\GiaoBanMetricServiceTest.php`

- [ ] **Step 3: Sửa `buildExamVisitSql`**. Đổi khối join hiện tại:
```php
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
```
thành:
```php
        $join = '';
        $extra = '';
        if (!empty($filter['treatment_type_ids']) || !empty($filter['patient_type_ids']) || !empty($filter['end_type_codes'])) {
            $join = ' JOIN his_treatment t ON t.id = sr.treatment_id';
        }
        if (!empty($filter['treatment_type_ids'])) {
            $t = implode(',', array_map('intval', $filter['treatment_type_ids']));
            $extra .= " AND t.tdl_treatment_type_id IN ($t)";
        }
        if (!empty($filter['patient_type_ids'])) {
            $p = implode(',', array_map('intval', $filter['patient_type_ids']));
            $extra .= " AND t.tdl_patient_type_id IN ($p)";
        }
        if (!empty($filter['end_type_codes'])) {
            $codes = array_filter(array_map(function ($c) {
                return preg_replace('/[^A-Z]/', '', strtoupper((string) $c));
            }, $filter['end_type_codes']));
            if (!empty($codes)) {
                $join .= ' JOIN his_treatment_end_type et ON et.id = t.treatment_end_type_id';
                $extra .= " AND et.treatment_end_type_code IN ('" . implode("','", $codes) . "')";
            }
        }
```

- [ ] **Step 4: Run PASS** — `vendor\bin\phpunit tests\Unit\GiaoBan` (kỳ vọng 31 tests).

- [ ] **Step 5: Commit** — `git commit -m "feat(giao-ban): exam_visit loc theo loai ra vien (end_type_codes) (TDD)"`

---

### Task 2: Cập nhật mẫu khối khám (tpl-kham)

**Files:** `resources/views/khth/giaoban-config.blade.php`

- [ ] Thay khối `<script ... id="tpl-kham">` bằng:
```blade
<script type="application/json" id="tpl-kham">[
  {"code":"luot_kham","name":"Lượt khám","type":"exam_visit"},
  {"code":"vao_vien","name":"Vào viện","type":"exam_visit","filter":{"treatment_type_ids":[3]}},
  {"code":"cap_toa_ve","name":"Cấp toa cho về","type":"exam_visit","filter":{"end_type_codes":["CC"]}},
  {"code":"chuyen_vien","name":"Chuyển viện","type":"exam_visit","filter":{"end_type_codes":["CV"]}},
  {"code":"hen_kham_lai","name":"Hẹn khám lại","type":"exam_visit","filter":{"end_type_codes":["HK"]}},
  {"code":"kham_yeu_cau","name":"Khám yêu cầu","type":"exam_visit","filter":{"patient_type_ids":[82]}},
  {"code":"kham_bhyt","name":"Khám BHYT","type":"exam_visit","filter":{"patient_type_ids":[1]}},
  {"code":"chuyen_gia","name":"Khám chuyên gia","type":"manual"}
]</script>
```
- [ ] `php artisan view:clear`; commit `feat(giao-ban): mau khoi kham them loai ra vien (cap toa ve/chuyen vien/hen kham lai)`

---

### Task 3: Gom migration giao ban (reset DB dev + fold)

**Files:** sửa `2026_07_08_100000_create_giaoban_dept_configs_table.php`; xóa `110000`, `110001`, `120000`.

- [ ] **Step 1: Reset bản ghi migration + drop bảng giao ban** (dev, không có dữ liệu thật). Script:
```php
// scratchpad/reset_giaoban_mig.php
DB::statement('DROP TABLE IF EXISTS giaoban_report_cells');
DB::statement('DROP TABLE IF EXISTS giaoban_reports');
DB::statement('DROP TABLE IF EXISTS giaoban_user_departments');
DB::statement('DROP TABLE IF EXISTS giaoban_dept_configs');
DB::table('migrations')->where('migration','like','2026_07_08_1%giaoban%')
  ->orWhere('migration','like','2026_07_08_11%')->orWhere('migration','like','2026_07_08_12%')->delete();
```
(An toàn: chỉ khớp các migration giao ban `2026_07_08_1000xx`/`110xxx`/`120xxx`.)

- [ ] **Step 2: Sửa `100000` — tạo bảng đã gồm block_type + his_department_ids**. Thay `up()`:
```php
    public function up()
    {
        Schema::create('giaoban_dept_configs', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('his_department_id')->nullable(); // legacy fallback
            $table->text('his_department_ids')->nullable();           // JSON mang int khoa HIS gop
            $table->string('block_type', 20)->default('dieu_tri');    // dieu_tri|kham|can_lam_sang
            $table->string('display_name', 255);
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->text('metrics');
            $table->timestamps();
        });
    }
```

- [ ] **Step 3: Xóa 3 file** `110000_add_block_type_dept_ids...`, `110001_clear_stale...`, `120000_sanitize_existing...`.

- [ ] **Step 4: Chạy** `php artisan migrate` → tạo lại 4 bảng + seed permission (idempotent). Kiểm cột:
```php
// verify: Schema::hasColumns('giaoban_dept_configs', ['block_type','his_department_ids','his_department_id','metrics'])
```
Expected: true; permission `giaoban`/`giaoban-admin` vẫn còn.

- [ ] **Step 5: Commit** — `git commit -m "chore(giao-ban): gom migration (fold block_type/dept_ids vao create, bo cleanup)"`

---

### Task 4: Đối chiếu HIS + full test

- [ ] Script verify: seed 1 config khối khám (khoa 27) mẫu tpl-kham mới, chạy `computeAll` → Cấp toa cho về ≈ 650, Chuyển viện = 8, Hẹn khám lại = 56; Vào viện 34; Lượt khám 834. Dọn dữ liệu.
- [ ] `vendor\bin\phpunit tests\Unit\GiaoBan` → PASS (31 tests).
- [ ] readme cập nhật (mục 6) + commit.
