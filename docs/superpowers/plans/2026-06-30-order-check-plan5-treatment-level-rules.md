# Order Check — Plan 5: Luật cấp đợt điều trị (A3 trùng DV, A2 trùng hoạt chất, A5 liều bất thường)

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) hoặc superpowers:executing-plans. Steps dùng checkbox (`- [ ]`).

**Goal:** Thêm 3 luật lâm sàng cần xét toàn đợt điều trị / dòng thuốc: A3 trùng dịch vụ, A2 trùng hoạt chất, A5 liều–số lượng bất thường; qua pattern "quét theo hoạt động mới rồi re-evaluate cả đợt".

**Architecture:** Hai scanner mới trên engine đa-nguồn (Plan 2). `DuplicateServiceScanner` (nguồn `his_sere_serv`): lấy dòng DV mới theo watermark → gom các `treatment_id` vừa phát sinh → với mỗi đợt, nạp TOÀN BỘ DV đang hoạt động → bắt trùng `service_id`. `MedicineScanner` (nguồn `his_exp_mest_medicine`): lấy dòng thuốc mới → A5 kiểm tra từng dòng (liều×ngày vs số lượng), A2 re-evaluate cả đợt (trùng `active_ingr_bhyt_code`). Logic thuần tách ra helper/rule có test; scanner chỉ chạy luật khi rule `is_active`.

**Tech Stack:** PHP 7 / Laravel 5.5, oci8 (HISPro), Eloquent (MySQL), PHPUnit.

**Tham chiếu:** Plan 1–4 (đã commit). Engine/Scanner: `app/Services/OrderCheck/OrderCheckEngine.php`, `Contracts/Scanner.php`, `Scanners/ScannerRegistry.php`, `Support/ViolationContext.php`, `Support/Violation.php`, `HisOrderSource.php`.

## Bối cảnh có sẵn (KHÔNG tạo lại)
- Engine đa-scanner: `OrderCheckEngine` có `source()`, `activeRules()`, `getWatermark($k)`, `saveWatermark($k,$ct,$id)`, `persist(Violation,$ViolationContext,$rule)`.
- `Scanner` interface: `sourceKey()`, `scan($engine,$limit): ['scanned'=>int,'violations'=>int]`.
- `ScannerRegistry::all(HisOrderSource $source)` (Plan 2) → hiện trả `[ServiceReqScanner, InteractionLogScanner]`. **Plan 5 sửa file này.**
- `Violation(ruleCode, orderRefType, orderRefId, message, array detail=[], subKey='')`, `dedupKey()`.
- `ViolationContext::make(array)`.
- `HisOrderSource` (connection qua `config('order_check.his_connection')`). **Plan 5 thêm method.**

## Dữ liệu HIS đã xác minh
- `HIS_SERE_SERV`: `ID, CREATE_TIME, IS_DELETE, TDL_TREATMENT_ID, SERVICE_ID, TDL_SERVICE_CODE, TDL_SERVICE_NAME`.
- `HIS_EXP_MEST_MEDICINE`: `ID, CREATE_TIME, IS_DELETE, MEDICINE_ID, TDL_MEDICINE_TYPE_ID, TDL_TREATMENT_ID, AMOUNT, DAY_COUNT, MORNING, NOON, AFTERNOON, EVENING`.
- `HIS_MEDICINE`: `ID, ACTIVE_INGR_BHYT_CODE, ACTIVE_INGR_BHYT_NAME` (join `em.medicine_id = m.id`).
- `HIS_TREATMENT`: `ID, TREATMENT_CODE, TDL_PATIENT_CODE, TDL_PATIENT_NAME, LAST_DEPARTMENT_ID`.
- Thời gian NUMBER `YYYYMMDDHH24MISS`.

## Ngoài phạm vi (Plan 6)
Gender/giới tính, chống chỉ định tuổi/cân nặng, BHYT payability: cần **bảng tham chiếu tự xây + nhập liệu** (danh mục DV giới hạn giới tính/tuổi, quy tắc BHYT) — kiến trúc data-driven riêng + tận dụng `CheckBHYT`. Thiết kế ở Plan 6.

## File Structure (Plan 5)
**Tạo mới:**
- `app/Services/OrderCheck/Support/Duplicates.php` — helper gom nhóm trùng (thuần).
- `app/Services/OrderCheck/RuleHandlers/Clinical/DoseSanityRule.php` — A5 (thuần).
- `app/Services/OrderCheck/Scanners/DuplicateServiceScanner.php` — A3.
- `app/Services/OrderCheck/Scanners/MedicineScanner.php` — A2 + A5.
- `database/migrations/2026_06_30_150000_seed_order_check_rules_a2_a3_a5.php`
- `database/migrations/2026_06_30_150001_init_treatment_level_watermarks_now.php`
- `tests/Unit/OrderCheck/DuplicatesTest.php`
- `tests/Unit/OrderCheck/DoseSanityRuleTest.php`
**Sửa:**
- `app/Services/OrderCheck/HisOrderSource.php` (thêm fetch methods)
- `app/Services/OrderCheck/Scanners/ScannerRegistry.php` (đăng ký 2 scanner)
- `readme.md`

---

## Task 1: Helper Duplicates + test

**Files:**
- Create: `app/Services/OrderCheck/Support/Duplicates.php`
- Test: `tests/Unit/OrderCheck/DuplicatesTest.php`

- [ ] **Step 1: Viết test thất bại**

```php
<?php

namespace Tests\Unit\OrderCheck;

use Tests\TestCase;
use App\Services\OrderCheck\Support\Duplicates;

class DuplicatesTest extends TestCase
{
    public function test_gom_nhom_trung_bo_qua_key_rong()
    {
        $items = [
            (object) ['k' => 'A', 'n' => 'x1'],
            (object) ['k' => 'A', 'n' => 'x2'],
            (object) ['k' => 'B', 'n' => 'y1'],
            (object) ['k' => '', 'n' => 'z1'],
            (object) ['k' => '', 'n' => 'z2'],
        ];
        $groups = Duplicates::groupsWithCountAbove($items, function ($i) { return $i->k; }, 1);
        // Chỉ nhóm 'A' (2 phần tử) vượt ngưỡng; key rỗng bị bỏ qua
        $this->assertCount(1, $groups);
        $this->assertArrayHasKey('A', $groups);
        $this->assertCount(2, $groups['A']);
    }

    public function test_khong_co_trung_tra_rong()
    {
        $items = [(object) ['k' => 'A'], (object) ['k' => 'B']];
        $groups = Duplicates::groupsWithCountAbove($items, function ($i) { return $i->k; }, 1);
        $this->assertCount(0, $groups);
    }
}
```

- [ ] **Step 2: Chạy test → FAIL**

Run: `vendor/bin/phpunit --filter DuplicatesTest`
Expected: FAIL ("Class '...Duplicates' not found")

- [ ] **Step 3: Cài đặt helper**

```php
<?php

namespace App\Services\OrderCheck\Support;

class Duplicates
{
    /**
     * Gom các phần tử theo key; trả về [key => items[]] cho nhóm có số lượng > $min.
     * Key rỗng/null bị bỏ qua.
     *
     * @param iterable $items
     * @param callable $keyFn fn($item) => string|null
     * @param int $min
     * @return array
     */
    public static function groupsWithCountAbove($items, callable $keyFn, $min = 1)
    {
        $byKey = [];
        foreach ($items as $it) {
            $key = $keyFn($it);
            if ($key === null || trim((string) $key) === '') {
                continue;
            }
            $byKey[$key][] = $it;
        }
        $out = [];
        foreach ($byKey as $key => $group) {
            if (count($group) > $min) {
                $out[$key] = $group;
            }
        }
        return $out;
    }
}
```

- [ ] **Step 4: Chạy test → PASS**

Run: `vendor/bin/phpunit --filter DuplicatesTest`
Expected: PASS (2 tests)

- [ ] **Step 5: Commit** (bỏ qua nếu được yêu cầu)

```bash
git add app/Services/OrderCheck/Support/Duplicates.php tests/Unit/OrderCheck/DuplicatesTest.php
git commit -m "feat(order-check): helper Duplicates (gom nhom trung) + test"
```

---

## Task 2: DoseSanityRule (A5) + test

**Files:**
- Create: `app/Services/OrderCheck/RuleHandlers/Clinical/DoseSanityRule.php`
- Test: `tests/Unit/OrderCheck/DoseSanityRuleTest.php`

> A5 (heuristic, có thể nhiễu do đơn vị → severity 'info', dễ tắt): chỉ cảnh báo khi liều theo buổi VÀ số ngày ĐỀU được nhập (>0) nhưng `(morning+noon+afternoon+evening) * day_count != amount`. Không fire khi thiếu dữ liệu (tránh false positive cho vật tư/dịch truyền).

- [ ] **Step 1: Viết test thất bại**

```php
<?php

namespace Tests\Unit\OrderCheck;

use Tests\TestCase;
use App\Services\OrderCheck\RuleHandlers\Clinical\DoseSanityRule;

class DoseSanityRuleTest extends TestCase
{
    public function test_lech_so_luong_la_bat_thuong()
    {
        $r = new DoseSanityRule();
        // 2 vien/ngay * 5 ngay = 10, nhung amount = 8 => lech
        $this->assertTrue($r->isMismatch(1, 0, 1, 0, 5, 8));
    }

    public function test_khop_so_luong_khong_bat_thuong()
    {
        $r = new DoseSanityRule();
        // 2/ngay * 5 = 10 == amount 10
        $this->assertFalse($r->isMismatch(1, 0, 1, 0, 5, 10));
    }

    public function test_thieu_du_lieu_thi_khong_fire()
    {
        $r = new DoseSanityRule();
        $this->assertFalse($r->isMismatch(0, 0, 0, 0, 5, 8)); // khong co lieu buoi
        $this->assertFalse($r->isMismatch(1, 0, 1, 0, 0, 8)); // khong co so ngay
        $this->assertFalse($r->isMismatch(1, 0, 1, 0, 5, 0)); // khong co so luong
    }
}
```

- [ ] **Step 2: Chạy test → FAIL**

Run: `vendor/bin/phpunit --filter DoseSanityRuleTest`
Expected: FAIL ("Class '...DoseSanityRule' not found")

- [ ] **Step 3: Cài đặt rule**

```php
<?php

namespace App\Services\OrderCheck\RuleHandlers\Clinical;

class DoseSanityRule
{
    public function code()
    {
        return 'A_DOSE_MISMATCH';
    }

    /**
     * True nếu liều/ngày × số ngày KHÁC số lượng cấp, và cả 3 đều > 0.
     * Dùng số thực; bỏ qua khi thiếu dữ liệu.
     */
    public function isMismatch($morning, $noon, $afternoon, $evening, $dayCount, $amount)
    {
        $perDay = (float) $morning + (float) $noon + (float) $afternoon + (float) $evening;
        $dayCount = (float) $dayCount;
        $amount = (float) $amount;

        if ($perDay <= 0 || $dayCount <= 0 || $amount <= 0) {
            return false;
        }
        $expected = $perDay * $dayCount;
        // So sánh với dung sai nhỏ cho số thực
        return abs($expected - $amount) > 0.0001;
    }
}
```

- [ ] **Step 4: Chạy test → PASS**

Run: `vendor/bin/phpunit --filter DoseSanityRuleTest`
Expected: PASS (3 tests)

- [ ] **Step 5: Commit** (bỏ qua nếu được yêu cầu)

```bash
git add app/Services/OrderCheck/RuleHandlers/Clinical/DoseSanityRule.php tests/Unit/OrderCheck/DoseSanityRuleTest.php
git commit -m "feat(order-check): rule A5 DoseSanityRule (lech lieu x ngay vs so luong)"
```

---

## Task 3: HisOrderSource — fetch methods cấp đợt

**Files:**
- Modify: `app/Services/OrderCheck/HisOrderSource.php`

- [ ] **Step 1: Thêm các method (đặt sau `fetchInteractions`)**

```php
    /** Lô dòng dịch vụ mới (his_sere_serv) theo watermark — để biết đợt nào vừa phát sinh. */
    public function fetchSereServBatch($lastCreateTime, $lastId, $limit)
    {
        return DB::connection($this->conn)
            ->table('his_sere_serv')
            ->where('is_delete', 0)
            ->where(function ($w) use ($lastCreateTime, $lastId) {
                $w->where('create_time', '>', $lastCreateTime)
                  ->orWhere(function ($w2) use ($lastCreateTime, $lastId) {
                      $w2->where('create_time', '=', $lastCreateTime)->where('id', '>', $lastId);
                  });
            })
            ->orderBy('create_time')->orderBy('id')->limit($limit)
            ->selectRaw('id, create_time, tdl_treatment_id')
            ->get();
    }

    /** Toàn bộ dịch vụ đang hoạt động của 1 đợt điều trị. */
    public function fetchTreatmentServices($treatmentId)
    {
        return DB::connection($this->conn)
            ->table('his_sere_serv')
            ->where('is_delete', 0)
            ->where('tdl_treatment_id', $treatmentId)
            ->selectRaw('id, service_id, tdl_service_code, tdl_service_name')
            ->get();
    }

    /** Lô dòng thuốc mới (his_exp_mest_medicine) theo watermark. */
    public function fetchExpMestBatch($lastCreateTime, $lastId, $limit)
    {
        return DB::connection($this->conn)
            ->table('his_exp_mest_medicine')
            ->where('is_delete', 0)
            ->where(function ($w) use ($lastCreateTime, $lastId) {
                $w->where('create_time', '>', $lastCreateTime)
                  ->orWhere(function ($w2) use ($lastCreateTime, $lastId) {
                      $w2->where('create_time', '=', $lastCreateTime)->where('id', '>', $lastId);
                  });
            })
            ->orderBy('create_time')->orderBy('id')->limit($limit)
            ->selectRaw('id, create_time, tdl_treatment_id, medicine_id, tdl_medicine_type_id,
                amount, day_count, morning, noon, afternoon, evening')
            ->get();
    }

    /** Toàn bộ thuốc đang hoạt động của 1 đợt, kèm hoạt chất từ his_medicine. */
    public function fetchTreatmentMedicines($treatmentId)
    {
        return DB::connection($this->conn)
            ->table('his_exp_mest_medicine as em')
            ->leftJoin('his_medicine as m', 'em.medicine_id', '=', 'm.id')
            ->where('em.is_delete', 0)
            ->where('em.tdl_treatment_id', $treatmentId)
            ->selectRaw('em.id, em.medicine_id, em.tdl_medicine_type_id,
                m.active_ingr_bhyt_code as active_ingr_code, m.active_ingr_bhyt_name as active_ingr_name')
            ->get();
    }

    /** Map id => thông tin đợt (treatment_code, bệnh nhân, khoa) cho ngữ cảnh vi phạm. */
    public function fetchTreatmentInfo(array $treatmentIds)
    {
        if (empty($treatmentIds)) {
            return [];
        }
        $rows = DB::connection($this->conn)
            ->table('his_treatment')
            ->whereIn('id', $treatmentIds)
            ->selectRaw('id, treatment_code, tdl_patient_code, tdl_patient_name, last_department_id')
            ->get();

        $map = [];
        foreach ($rows as $r) {
            $map[(int) $r->id] = $r;
        }
        return $map;
    }
```

- [ ] **Step 2: Verify cú pháp + đọc HIS thật**

Run: `php -l app/Services/OrderCheck/HisOrderSource.php` → No syntax errors.

Tạo file tạm `vchk.php` (chạy `php vchk.php` rồi xóa):
```php
<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$s = app(App\Services\OrderCheck\HisOrderSource::class);
$ss = $s->fetchSereServBatch(0,0,2); echo 'sereserv='.$ss->count().PHP_EOL;
$em = $s->fetchExpMestBatch(0,0,2); echo 'expmest='.$em->count().PHP_EOL;
if ($ss->count()) { $tid=(int)$ss[0]->tdl_treatment_id; echo 'svc_of_treatment='.$s->fetchTreatmentServices($tid)->count().PHP_EOL; $info=$s->fetchTreatmentInfo([$tid]); echo 'tcode='.(isset($info[$tid])?$info[$tid]->treatment_code:'?').PHP_EOL; }
if ($em->count()) { $tid2=(int)$em[0]->tdl_treatment_id; echo 'med_of_treatment='.$s->fetchTreatmentMedicines($tid2)->count().PHP_EOL; }
```
Expected: in ra các dòng count không lỗi (số có thể là 2/…); `tcode` ra mã đợt. Nếu lỗi cột → BLOCKED + lỗi nguyên văn.

- [ ] **Step 3: Commit** (bỏ qua nếu được yêu cầu)

```bash
git add app/Services/OrderCheck/HisOrderSource.php
git commit -m "feat(order-check): HisOrderSource fetch cap dot (sere_serv/exp_mest/treatment)"
```

---

## Task 4: DuplicateServiceScanner (A3)

**Files:**
- Create: `app/Services/OrderCheck/Scanners/DuplicateServiceScanner.php`

- [ ] **Step 1: Tạo scanner**

```php
<?php

namespace App\Services\OrderCheck\Scanners;

use App\Services\OrderCheck\Contracts\Scanner;
use App\Services\OrderCheck\OrderCheckEngine;
use App\Services\OrderCheck\Support\Duplicates;
use App\Services\OrderCheck\Support\Violation;
use App\Services\OrderCheck\Support\ViolationContext;

class DuplicateServiceScanner implements Scanner
{
    const SOURCE_KEY = 'his_sere_serv';
    const RULE_CODE = 'A_DUPLICATE_SERVICE';

    public function sourceKey()
    {
        return self::SOURCE_KEY;
    }

    public function scan(OrderCheckEngine $engine, $limit)
    {
        $rules = $engine->activeRules();
        $active = isset($rules[self::RULE_CODE]);
        $rule = $active ? $rules[self::RULE_CODE] : null;

        $source = $engine->source();
        $wm = $engine->getWatermark(self::SOURCE_KEY);
        $rows = $source->fetchSereServBatch($wm->last_create_time, $wm->last_id, $limit);
        $scanned = $rows->count();
        $violations = 0;

        if ($scanned > 0) {
            $maxCreate = $wm->last_create_time;
            $maxId = $wm->last_id;
            $treatmentIds = [];

            foreach ($rows as $row) {
                $treatmentIds[(int) $row->tdl_treatment_id] = true;
                if ((int) $row->create_time > $maxCreate || ((int) $row->create_time == $maxCreate && (int) $row->id > $maxId)) {
                    $maxCreate = (int) $row->create_time;
                    $maxId = (int) $row->id;
                }
            }

            if ($active && !empty($treatmentIds)) {
                $ids = array_keys($treatmentIds);
                $info = $source->fetchTreatmentInfo($ids);

                foreach ($ids as $tid) {
                    $services = $source->fetchTreatmentServices($tid);
                    $dups = Duplicates::groupsWithCountAbove($services, function ($s) { return $s->service_id; }, 1);
                    if (empty($dups)) {
                        continue;
                    }
                    $vctx = $this->context($tid, isset($info[$tid]) ? $info[$tid] : null);
                    foreach ($dups as $serviceId => $group) {
                        $first = $group[0];
                        $vio = new Violation(
                            self::RULE_CODE, 'treatment', $tid,
                            'Trùng dịch vụ trong đợt: ' . $first->tdl_service_code . ' - ' . $first->tdl_service_name . ' (' . count($group) . ' lần)',
                            ['service_id' => (int) $serviceId, 'service_code' => $first->tdl_service_code, 'count' => count($group)],
                            'svc' . $serviceId
                        );
                        if ($engine->persist($vio, $vctx, $rule)) {
                            $violations++;
                        }
                    }
                }
            }

            $engine->saveWatermark(self::SOURCE_KEY, $maxCreate, $maxId);
        }

        return ['scanned' => $scanned, 'violations' => $violations];
    }

    private function context($tid, $info)
    {
        return ViolationContext::make([
            'treatment_id' => $tid,
            'treatment_code' => $info ? $info->treatment_code : null,
            'patient_code' => $info ? $info->tdl_patient_code : null,
            'patient_name' => $info ? $info->tdl_patient_name : null,
            'department_id' => $info && $info->last_department_id !== null ? (int) $info->last_department_id : null,
        ]);
    }
}
```

- [ ] **Step 2: Verify cú pháp**

Run: `php -l app/Services/OrderCheck/Scanners/DuplicateServiceScanner.php` → No syntax errors.

- [ ] **Step 3: Commit** (bỏ qua nếu được yêu cầu)

```bash
git add app/Services/OrderCheck/Scanners/DuplicateServiceScanner.php
git commit -m "feat(order-check): A3 DuplicateServiceScanner (trung DV cap dot)"
```

---

## Task 5: MedicineScanner (A2 + A5)

**Files:**
- Create: `app/Services/OrderCheck/Scanners/MedicineScanner.php`

- [ ] **Step 1: Tạo scanner**

```php
<?php

namespace App\Services\OrderCheck\Scanners;

use App\Services\OrderCheck\Contracts\Scanner;
use App\Services\OrderCheck\OrderCheckEngine;
use App\Services\OrderCheck\Support\Duplicates;
use App\Services\OrderCheck\Support\Violation;
use App\Services\OrderCheck\Support\ViolationContext;
use App\Services\OrderCheck\RuleHandlers\Clinical\DoseSanityRule;

class MedicineScanner implements Scanner
{
    const SOURCE_KEY = 'his_exp_mest_medicine';
    const RULE_DUP = 'A_DUPLICATE_ACTIVE_INGREDIENT';
    const RULE_DOSE = 'A_DOSE_MISMATCH';

    public function sourceKey()
    {
        return self::SOURCE_KEY;
    }

    public function scan(OrderCheckEngine $engine, $limit)
    {
        $rules = $engine->activeRules();
        $dupActive = isset($rules[self::RULE_DUP]);
        $doseActive = isset($rules[self::RULE_DOSE]);

        $source = $engine->source();
        $wm = $engine->getWatermark(self::SOURCE_KEY);
        $rows = $source->fetchExpMestBatch($wm->last_create_time, $wm->last_id, $limit);
        $scanned = $rows->count();
        $violations = 0;

        if ($scanned > 0) {
            $maxCreate = $wm->last_create_time;
            $maxId = $wm->last_id;
            $treatmentIds = [];

            foreach ($rows as $row) {
                $treatmentIds[(int) $row->tdl_treatment_id] = true;
                if ((int) $row->create_time > $maxCreate || ((int) $row->create_time == $maxCreate && (int) $row->id > $maxId)) {
                    $maxCreate = (int) $row->create_time;
                    $maxId = (int) $row->id;
                }
            }

            $info = $source->fetchTreatmentInfo(array_keys($treatmentIds));

            // ===== A5: kiểm tra từng dòng thuốc mới =====
            if ($doseActive) {
                $doseRule = new DoseSanityRule();
                $rule = $rules[self::RULE_DOSE];
                foreach ($rows as $row) {
                    if ($doseRule->isMismatch($row->morning, $row->noon, $row->afternoon, $row->evening, $row->day_count, $row->amount)) {
                        $tid = (int) $row->tdl_treatment_id;
                        $perDay = (float) $row->morning + (float) $row->noon + (float) $row->afternoon + (float) $row->evening;
                        $vio = new Violation(
                            self::RULE_DOSE, 'exp_mest_medicine', (int) $row->id,
                            'Liều×ngày (' . $perDay . '×' . $row->day_count . ') không khớp số lượng cấp (' . $row->amount . ')',
                            ['per_day' => $perDay, 'day_count' => (float) $row->day_count, 'amount' => (float) $row->amount]
                        );
                        if ($engine->persist($vio, $this->context($tid, isset($info[$tid]) ? $info[$tid] : null), $rule)) {
                            $violations++;
                        }
                    }
                }
            }

            // ===== A2: re-evaluate trùng hoạt chất cả đợt =====
            if ($dupActive && !empty($treatmentIds)) {
                $rule = $rules[self::RULE_DUP];
                foreach (array_keys($treatmentIds) as $tid) {
                    $meds = $source->fetchTreatmentMedicines($tid);
                    $dups = Duplicates::groupsWithCountAbove($meds, function ($m) { return $m->active_ingr_code; }, 1);
                    if (empty($dups)) {
                        continue;
                    }
                    $vctx = $this->context($tid, isset($info[$tid]) ? $info[$tid] : null);
                    foreach ($dups as $code => $group) {
                        $first = $group[0];
                        $vio = new Violation(
                            self::RULE_DUP, 'treatment', $tid,
                            'Trùng hoạt chất trong đợt: ' . $first->active_ingr_name . ' (' . count($group) . ' thuốc)',
                            ['active_ingr_code' => $code, 'active_ingr_name' => $first->active_ingr_name, 'count' => count($group)],
                            'ai' . $code
                        );
                        if ($engine->persist($vio, $vctx, $rule)) {
                            $violations++;
                        }
                    }
                }
            }

            $engine->saveWatermark(self::SOURCE_KEY, $maxCreate, $maxId);
        }

        return ['scanned' => $scanned, 'violations' => $violations];
    }

    private function context($tid, $info)
    {
        return ViolationContext::make([
            'treatment_id' => $tid,
            'treatment_code' => $info ? $info->treatment_code : null,
            'patient_code' => $info ? $info->tdl_patient_code : null,
            'patient_name' => $info ? $info->tdl_patient_name : null,
            'department_id' => $info && $info->last_department_id !== null ? (int) $info->last_department_id : null,
        ]);
    }
}
```

- [ ] **Step 2: Verify cú pháp**

Run: `php -l app/Services/OrderCheck/Scanners/MedicineScanner.php` → No syntax errors.

- [ ] **Step 3: Commit** (bỏ qua nếu được yêu cầu)

```bash
git add app/Services/OrderCheck/Scanners/MedicineScanner.php
git commit -m "feat(order-check): A2+A5 MedicineScanner (trung hoat chat + lech lieu)"
```

---

## Task 6: Đăng ký scanner + seed rule + init watermark

**Files:**
- Modify: `app/Services/OrderCheck/Scanners/ScannerRegistry.php`
- Create: `database/migrations/2026_06_30_150000_seed_order_check_rules_a2_a3_a5.php`
- Create: `database/migrations/2026_06_30_150001_init_treatment_level_watermarks_now.php`

- [ ] **Step 1: Thay nội dung ScannerRegistry**

```php
<?php

namespace App\Services\OrderCheck\Scanners;

use App\Services\OrderCheck\HisOrderSource;

class ScannerRegistry
{
    /**
     * @param HisOrderSource $source
     * @return \App\Services\OrderCheck\Contracts\Scanner[]
     */
    public static function all(HisOrderSource $source)
    {
        return [
            new ServiceReqScanner(),
            new InteractionLogScanner(),
            new DuplicateServiceScanner(),
            new MedicineScanner(),
        ];
    }
}
```

- [ ] **Step 2: Migration seed rule A2/A3/A5**

File `database/migrations/2026_06_30_150000_seed_order_check_rules_a2_a3_a5.php`:
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class SeedOrderCheckRulesA2A3A5 extends Migration
{
    public function up()
    {
        $now = date('Y-m-d H:i:s');
        $rules = [
            ['code' => 'A_DUPLICATE_SERVICE', 'rule_type' => 'DuplicateServiceScanner', 'name' => 'Trùng dịch vụ trong đợt điều trị', 'severity' => 'warning'],
            ['code' => 'A_DUPLICATE_ACTIVE_INGREDIENT', 'rule_type' => 'MedicineScanner', 'name' => 'Trùng hoạt chất trong đợt điều trị', 'severity' => 'warning'],
            ['code' => 'A_DOSE_MISMATCH', 'rule_type' => 'DoseSanityRule', 'name' => 'Liều × ngày không khớp số lượng cấp', 'severity' => 'info'],
        ];
        foreach ($rules as $r) {
            if (!DB::table('order_check_rules')->where('code', $r['code'])->exists()) {
                DB::table('order_check_rules')->insert([
                    'code' => $r['code'], 'family' => 'A', 'rule_type' => $r['rule_type'],
                    'name' => $r['name'], 'severity' => $r['severity'],
                    'params' => null, 'scope' => null, 'is_active' => 1,
                    'created_at' => $now, 'updated_at' => $now,
                ]);
            }
        }
    }

    public function down()
    {
        DB::table('order_check_rules')->whereIn('code', ['A_DUPLICATE_SERVICE', 'A_DUPLICATE_ACTIVE_INGREDIENT', 'A_DOSE_MISMATCH'])->delete();
    }
}
```

- [ ] **Step 3: Migration init watermark 2 nguồn mới = thời điểm deploy**

File `database/migrations/2026_06_30_150001_init_treatment_level_watermarks_now.php`:
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class InitTreatmentLevelWatermarksNow extends Migration
{
    public function up()
    {
        $nowNum = (int) date('YmdHis');
        $nowDt = date('Y-m-d H:i:s');
        foreach (['his_sere_serv', 'his_exp_mest_medicine'] as $key) {
            DB::table('order_check_watermarks')->updateOrInsert(
                ['source_key' => $key],
                [
                    'last_create_time' => $nowNum, 'last_modify_time' => $nowNum, 'last_id' => 0,
                    'last_run_at' => $nowDt, 'created_at' => $nowDt, 'updated_at' => $nowDt,
                ]
            );
        }
    }

    public function down()
    {
        DB::table('order_check_watermarks')->whereIn('source_key', ['his_sere_serv', 'his_exp_mest_medicine'])->delete();
    }
}
```

- [ ] **Step 4: Chạy migrate + verify**

Run: `php artisan migrate`
Expected: 2 dòng `Migrated: ..._seed_order_check_rules_a2_a3_a5` và `..._init_treatment_level_watermarks_now`.

```bash
echo 'echo App\Models\OrderCheck\OrderCheckRule::whereIn("code",["A_DUPLICATE_SERVICE","A_DUPLICATE_ACTIVE_INGREDIENT","A_DOSE_MISMATCH"])->count();' | php artisan tinker
```
Expected: in `3` (nếu tinker-pipe không in, bỏ qua — đã verify qua migrate).

- [ ] **Step 5: Commit** (bỏ qua nếu được yêu cầu)

```bash
git add app/Services/OrderCheck/Scanners/ScannerRegistry.php database/migrations/2026_06_30_15000*_*.php
git commit -m "feat(order-check): dang ky scanner A2/A3/A5 + seed rule + init watermark"
```

---

## Task 7: Verify e2e + regression + readme

**Files:**
- Modify: `readme.md`

- [ ] **Step 1: Regression Unit OrderCheck**

Run: `vendor/bin/phpunit tests/Unit/OrderCheck`
Expected: PASS toàn bộ (23 cũ + Duplicates 2 + DoseSanity 3 = 28 tests).

- [ ] **Step 2: Verify engine chạy thật (4 scanner)**

Run: `php artisan kiemtraylenh:scan --once --limit=20`
Expected: in "Quet xong: ... phieu, ... vi pham moi, ...s" không exception.

File tạm `lchk.php` (chạy `php lchk.php`, xóa sau):
```php
<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
foreach (App\Models\OrderCheck\OrderCheckRuleLog::orderBy('id','desc')->take(4)->get() as $l) {
    echo $l->source_key.' => '.$l->status.' scanned='.$l->scanned_count.' vio='.$l->violation_count.PHP_EOL;
}
```
Expected: thấy 4 nguồn `his_service_req`, `his_medicine_interactive`, `his_sere_serv`, `his_exp_mest_medicine` đều `success`.

- [ ] **Step 3: Cập nhật readme**

Chèn vào đầu `readme.md`:
```markdown
# 30/06/2026 (cập nhật 4)

- Module Kiểm tra sai sót y lệnh (giai đoạn 5): bổ sung luật cấp đợt điều trị — A3 trùng dịch vụ, A2 trùng hoạt chất (HIS_EXP_MEST_MEDICINE + HIS_MEDICINE), A5 liều×ngày không khớp số lượng cấp. Quét incremental theo hoạt động mới rồi re-evaluate cả đợt; bật/tắt trong order_check_rules.

```

- [ ] **Step 4: Commit** (bỏ qua nếu được yêu cầu)

```bash
git add readme.md
git commit -m "docs(order-check): readme giai doan 5 (luat cap dot A2/A3/A5)"
```

---

## Plan 6 (preview — ngoài phạm vi Plan 5)
Gender/giới tính, tuổi/cân nặng, BHYT payability: cần bảng tham chiếu tự xây trong `qlbv` (vd `order_check_ref_service_gender`, `order_check_ref_service_age`, `order_check_ref_bhyt`) + giao diện/nhập liệu danh mục + luật đọc bảng đó; BHYT tận dụng `App\Models\CheckBHYT\*`. Là một dự án dữ liệu + rule riêng → Plan 6.

## Self-Review (đã thực hiện khi viết plan)

**1. Spec coverage (Plan 5):**
- A3 trùng DV cấp đợt → Task 4 (DuplicateServiceScanner) + Task 1 (Duplicates helper) + Task 3 (fetchTreatmentServices). ✅
- A2 trùng hoạt chất cấp đợt → Task 5 (MedicineScanner, nhánh A2) + Task 3 (fetchTreatmentMedicines join his_medicine). ✅
- A5 liều bất thường → Task 2 (DoseSanityRule, có test) + Task 5 (nhánh A5). ✅
- Pattern incremental + re-evaluate đợt, watermark riêng mỗi nguồn, init = deploy-time (không backfill) → Task 3/4/5/6. ✅
- Idempotent (dedup_key có subKey theo service_id/active_ingr_code/line) → Task 4/5. ✅
- Bật/tắt data-driven → seed rule + scanner kiểm tra `activeRules()` (Task 5/6). ✅

**2. Placeholder scan:** mọi step in-scope có code/lệnh + kỳ vọng. Gender/tuổi/BHYT là "Plan 6 preview" có chủ đích (không stub trong Plan 5).

**3. Type consistency:** `Scanner::scan()` trả `['scanned','violations']` khớp các scanner mới ↔ engine.run (Plan 2). `engine->persist(Violation,ViolationContext,$rule)` dùng đúng. `Duplicates::groupsWithCountAbove($items,$keyFn,$min)` khớp test (Task 1) ↔ scanner (Task 4/5). `DoseSanityRule::isMismatch(m,n,a,e,day,amount)` khớp test (Task 2) ↔ scanner (Task 5). Watermark keys `his_sere_serv`/`his_exp_mest_medicine` khớp scanner ↔ migration init (Task 6). Cột HIS dùng đúng tên đã xác minh. ✅

**4. Lưu ý:** A5 là heuristic (severity 'info', dễ nhiễu do đơn vị đóng gói khác nhau) — có thể tắt qua `is_active=0` nếu nhiều false positive. A2/A3 dựa trên `service_id`/`active_ingr_bhyt_code` (sạch). Re-evaluate cả đợt mỗi khi có item mới → idempotent nhờ dedup_key; chi phí bị giới hạn bởi số đợt phát sinh trong 1 lô.
