# Nâng cấp tổng hợp trình chiếu (công suất giường + KPI) — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: superpowers:subagent-driven-development / executing-plans.

**Goal:** Slide tổng hợp trình chiếu giao ban: thêm công suất giường (snapshot), 8 KPI, tách 2 slide (tổng quan + công suất/biến động theo khoa).

**Spec:** `docs/superpowers/specs/2026-07-09-giao-ban-present-tonghop-design.md`

**Đã xác minh:** bed snapshot SQL tại 20260709070000 → total 831, used 506, công suất 60.9%. Present đọc `show()`.

---

### Task 1: Migration + model beds

- [ ] `database/migrations/2026_07_09_110000_create_giaoban_report_beds_table.php`:
```php
<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateGiaobanReportBedsTable extends Migration
{
    public function up()
    {
        Schema::create('giaoban_report_beds', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('report_id');
            $table->unsignedInteger('department_id'); // his_department.id
            $table->integer('total_beds')->default(0);
            $table->integer('used_beds')->default(0);
            $table->timestamps();
            $table->index('report_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('giaoban_report_beds');
    }
}
```

- [ ] `app/Models/GiaoBan/GiaoBanReportBed.php`:
```php
<?php

namespace App\Models\GiaoBan;

use Illuminate\Database\Eloquent\Model;

class GiaoBanReportBed extends Model
{
    protected $table = 'giaoban_report_beds';
    protected $fillable = ['report_id', 'department_id', 'total_beds', 'used_beds'];
    protected $casts = ['report_id' => 'integer', 'department_id' => 'integer', 'total_beds' => 'integer', 'used_beds' => 'integer'];
}
```

- [ ] `php artisan migrate`. Commit `feat(giao-ban): migration + model giuong snapshot (report_beds)`.

---

### Task 2: GiaoBanMetricService::buildBedCapacitySql (TDD)

- [ ] Test (thêm vào `GiaoBanMetricServiceTest`):
```php
    /** @test */
    public function bed_capacity_sql_snapshots_beds_at_time()
    {
        list($sql, $binds) = $this->svc->buildBedCapacitySql('2026-07-09 07:00:00');
        $this->assertContains('his_bed', $sql);
        $this->assertContains('his_treatment_bed_room', $sql);
        $this->assertContains('tdl_treatment_type_id IN (3,4)', $sql);
        $this->assertContains('total_beds', $sql);
        $this->assertContains('used_beds', $sql);
        $this->assertEquals(['at1' => '20260709070000', 'at2' => '20260709070000', 'at3' => '20260709070000'], $binds);
    }
```

- [ ] Run FAIL. Implement (thêm method vào GiaoBanMetricService, cạnh các builder khác):
```php
    /**
     * Snapshot công suất giường tại thời điểm $at: per department total_beds + used_beds.
     * (oci8 không cho dùng lại bind name -> at1/at2/at3)
     */
    public function buildBedCapacitySql($at)
    {
        $ts = $this->toHisTime($at);
        $sql = "
            SELECT tong.department_id, tong.total_beds AS total_beds, NVL(dang.used_beds, 0) AS used_beds
            FROM (
                SELECT r.department_id, COUNT(*) total_beds
                FROM his_bed b
                JOIN his_bed_room br ON br.id = b.bed_room_id
                JOIN his_room r ON r.id = br.room_id
                WHERE b.is_active=1 AND b.is_delete=0 AND br.is_active=1 AND br.is_delete=0 AND r.is_active=1
                GROUP BY r.department_id
            ) tong
            LEFT JOIN (
                SELECT r.department_id, COUNT(*) used_beds
                FROM his_treatment_bed_room tbr
                JOIN his_bed_room br ON br.id = tbr.bed_room_id
                JOIN his_room r ON r.id = br.room_id
                JOIN his_treatment t ON t.id = tbr.treatment_id
                LEFT JOIN his_co_treatment ct ON ct.id = tbr.co_treatment_id
                WHERE tbr.is_delete=0 AND ct.id IS NULL
                  AND t.tdl_treatment_type_id IN (3,4)
                  AND tbr.add_time <= :at1
                  AND (tbr.remove_time IS NULL OR tbr.remove_time = 0 OR tbr.remove_time > :at2)
                  AND (t.out_time IS NULL OR t.out_time = 0 OR t.out_time > :at3)
                GROUP BY r.department_id
            ) dang ON dang.department_id = tong.department_id";
        return [$sql, ['at1' => $ts, 'at2' => $ts, 'at3' => $ts]];
    }
```

- [ ] Run PASS (`vendor\bin\phpunit tests\Unit\GiaoBan`). Commit `feat(giao-ban): buildBedCapacitySql snapshot cong suat giuong (TDD)`.

---

### Task 3: GiaoBanReportService — lưu beds khi fetch

- [ ] Trong `fetchAndStore`, sau vòng lặp lưu cells (trước `$report->update([...])`), thêm:
```php
        try {
            list($bedSql, $bedBinds) = $this->metricService->buildBedCapacitySql($to);
            $bedRows = $this->metricService->normalizeRows(
                \Illuminate\Support\Facades\DB::connection('HISPro')->select($bedSql, $bedBinds)
            );
            \App\Models\GiaoBan\GiaoBanReportBed::where('report_id', $report->id)->delete();
            foreach ($bedRows as $b) {
                \App\Models\GiaoBan\GiaoBanReportBed::create([
                    'report_id' => $report->id, 'department_id' => (int) $b->department_id,
                    'total_beds' => (int) $b->total_beds, 'used_beds' => (int) $b->used_beds,
                ]);
            }
        } catch (\Exception $e) { /* bỏ qua nếu HIS lỗi, không chặn lưu cells */ }
```

- [ ] `php -l`. Commit `feat(giao-ban): fetchAndStore luu snapshot giuong`.

---

### Task 4: GiaoBanController@show — bed_total/used/by_config

- [ ] use `App\Models\GiaoBan\GiaoBanReportBed`. Trong show(), trước return, thêm:
```php
        $bedTotal = 0; $bedUsed = 0; $bedByDept = [];
        if ($report) {
            foreach (GiaoBanReportBed::where('report_id', $report->id)->get() as $b) {
                $bedTotal += (int) $b->total_beds; $bedUsed += (int) $b->used_beds;
                $bedByDept[(int) $b->department_id] = ['total' => (int) $b->total_beds, 'used' => (int) $b->used_beds];
            }
        }
        $bedByConfig = [];
        foreach (GiaoBanDeptConfig::where('is_active', true)->orderBy('sort_order')->get() as $cfg) {
            $t = 0; $u = 0; $has = false;
            foreach ($cfg->hisDepartmentIds() as $hid) {
                if (isset($bedByDept[$hid])) { $t += $bedByDept[$hid]['total']; $u += $bedByDept[$hid]['used']; $has = true; }
            }
            if ($has && $t > 0) $bedByConfig[] = ['dept_config_id' => $cfg->id, 'display_name' => $cfg->display_name, 'total' => $t, 'used' => $u];
        }
```
Và JSON return thêm: `'bed_total' => $bedTotal, 'bed_used' => $bedUsed, 'bed_by_config' => $bedByConfig,`.

- [ ] `php -l`. Commit `feat(giao-ban): show tra bed_total/used/by_config`.

---

### Task 5: giaoban-present.blade.php — 2 slide tổng hợp

**Do controller (người thực thi màn hình) làm inline** — thay `overviewSlide` + thêm `capacityDeptSlide`, và cập nhật `build()` để chèn slide công suất sau overview.

- [ ] **5a. overviewSlide**: mở rộng lưới KPI lên 8 ô (thêm Ra viện `bn_ra_vien`, Chuyển viện `bn_chuyen_vien`, Tử vong `bn_tu_vong`, Cấp cứu `bn_cap_cuu`, Vào viện `vao_vien`; ẩn ô nào `sumMetric` trả null). Thêm donut công suất giường (`data.bed_used/data.bed_total`, ẩn nếu `bed_total` falsy) ở cột phải. BỎ biểu đồ vào/ra khỏi overview (chuyển sang slide công suất). Giữ khối kíp trực dưới cùng.
- [ ] **5b. capacityDeptSlide(data)**: slide riêng — thanh công suất % từng khoa từ `data.bed_by_config` (màu: ≥90 đỏ #e57373, ≥80 cam #ef9f27, ≥60 teal #5dcaa5, còn lại xanh #378add; sắp giảm dần) + biểu đồ BN vào/ra theo khoa (di chuyển code cũ sang). Trả `''` nếu `!bed_by_config.length && !rows.length`.
- [ ] **5c. build()**: `slides.push(overviewSlide(data))`; nếu `capacityDeptSlide(data)` khác rỗng thì push tiếp; rồi các dept slide. Dot/counter tự tính theo slides.length (đã có).
- [ ] Escape mọi text. `php artisan view:clear` + render check.
- [ ] Commit `feat(giao-ban): trinh chieu 2 slide tong hop (KPI + cong suat giuong theo khoa)`.

---

### Task 6: Verify + readme
- [ ] `vendor\bin\phpunit tests\Unit\GiaoBan` PASS.
- [ ] Runtime: seed 1 config dieu_tri + report, gọi buildBedCapacitySql + lưu beds; show()-style tính bed_total/used/by_config → hợp lý (đối chiếu ~831/506). Dọn.
- [ ] Present render (Node): overview có donut khi bed_total>0; capacity slide có thanh %; ẩn khi bed_total=0.
- [ ] readme + commit.
