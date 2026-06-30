# Order Check — Plan 2: Engine đa-nguồn + Luật lâm sàng A1 (tương tác thuốc) & A4 (thiếu chẩn đoán)

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Tổng quát hóa engine Plan 1 để quét nhiều nguồn HIS (multi-scanner), rồi thêm 2 luật Họ A: A1 nạp log tương tác thuốc HIS phát hiện (`HIS_MEDICINE_INTERACTIVE`) và A4 phát hiện phiếu chỉ định thiếu chẩn đoán ICD.

**Architecture:** Tách logic quét theo "Scanner" (mỗi nguồn HIS một scanner, có watermark riêng). `ServiceReqScanner` bọc lại luồng Plan 1 (Họ B structural + A4 missing-diagnosis trên `OrderContext`). `InteractionLogScanner` quét `HIS_MEDICINE_INTERACTIVE` theo watermark, mỗi dòng đã chứa đủ thông tin (treatment, bác sĩ, ICD, cặp thuốc, mức độ) → sinh violation A1. `OrderCheckEngine` lặp qua các scanner đã đăng ký; `persist()` được tách khỏi `OrderContext` (nhận snapshot dạng mảng) để mọi scanner dùng chung.

**Tech Stack:** PHP 7 / Laravel 5.5, Eloquent, yajra/laravel-oci8 (connection `HISPro`), MySQL (`qlbv`), PHPUnit.

**Tham chiếu:** spec `docs/superpowers/specs/2026-06-30-kiem-tra-sai-sot-y-lenh-design.md`; Plan 1 `docs/superpowers/plans/2026-06-30-order-check-plan1-foundation.md` (đã triển khai & commit `1bbca7b`).

---

## Bối cảnh từ Plan 1 (đã có sẵn, KHÔNG tạo lại)

- DTO: `App\Services\OrderCheck\Support\{OrderContext, OrderService, Violation}`. `Violation(ruleCode, orderRefType, orderRefId, message, array detail=[], subKey='')` + `dedupKey()`.
- Interface: `App\Services\OrderCheck\Contracts\RuleHandler` { `code()`, `check(OrderContext): Violation[]` }.
- Models: `App\Models\OrderCheck\{OrderCheckWatermark, OrderCheckRule, OrderCheckViolation, OrderCheckRuleLog}`. Bảng MySQL đã migrate.
- `App\Services\OrderCheck\HisOrderSource` { `fetchServiceRequests($lastCreateTime,$lastId,$limit)`, `fetchServicesByReqIds(array)`, `buildContext($row, array $services)` }.
- `App\Services\OrderCheck\RuleHandlers\StructuralRuleRegistry::handlers()` → 4 handler Họ B.
- `App\Services\OrderCheck\OrderCheckEngine` (Plan 1): hiện hardcode 1 nguồn `his_service_req`. **Plan 2 sẽ refactor file này.**
- Command `kiemtraylenh:scan` gọi `OrderCheckEngine::run($limit)`.
- Watermark đã khởi tạo = thời điểm deploy (chỉ bắt y lệnh mới).

## Dữ liệu HIS đã xác minh (Plan 2)

- `HIS_MEDICINE_INTERACTIVE` (owner HIS_RS): mỗi dòng = 1 tương tác HIS tự phát hiện, gắn `TREATMENT_ID`. Cột dùng: `ID, CREATE_TIME, MODIFY_TIME, IS_DELETE, REQUEST_LOGINNAME, REQUEST_DEPARTMENT_ID, TREATMENT_ID, ICD_CODE, ICD_NAME, MEDICINE_TYPE_ID1, MEDICINE_TYPE_ID2, INTERACTIVE_GRADE_ID`. (Không có sẵn tên thuốc/tên mức độ → Plan 2 lưu id, làm giàu tên ở Plan 3/UI.)
- `HIS_SERVICE_REQ.ICD_CODE`/`ICD_NAME` đã có trong `OrderContext` (Plan 1) → A4 dùng trực tiếp, không cần truy vấn thêm.

## File Structure (Plan 2)

**Tạo mới:**
- `app/Services/OrderCheck/Contracts/Scanner.php` — interface scanner đa-nguồn.
- `app/Services/OrderCheck/Scanners/ServiceReqScanner.php` — bọc luồng Plan 1 (Họ B + A4).
- `app/Services/OrderCheck/Scanners/InteractionLogScanner.php` — A1, quét `HIS_MEDICINE_INTERACTIVE`.
- `app/Services/OrderCheck/Scanners/ScannerRegistry.php` — đăng ký danh sách scanner.
- `app/Services/OrderCheck/RuleHandlers/Clinical/MissingDiagnosisRule.php` — A4.
- `app/Services/OrderCheck/RuleHandlers/ClinicalServiceReqRuleRegistry.php` — registry handler Họ A cấp phiếu chỉ định.
- `app/Services/OrderCheck/Support/ViolationContext.php` — snapshot ngữ cảnh để persist (dùng chung mọi scanner).
- `database/migrations/2026_06_30_130000_seed_order_check_clinical_rules_a1_a4.php` — seed rule A1, A4.
- `tests/Unit/OrderCheck/MissingDiagnosisRuleTest.php`

**Sửa:**
- `app/Services/OrderCheck/OrderCheckEngine.php` — chuyển sang điều phối nhiều scanner; `persist()` nhận `ViolationContext`; thêm helper dùng chung cho scanner.
- `app/Services/OrderCheck/HisOrderSource.php` — thêm `fetchInteractions($lastCreateTime,$lastId,$limit)`.

---

## Task 1: ViolationContext (snapshot persist dùng chung)

**Files:**
- Create: `app/Services/OrderCheck/Support/ViolationContext.php`

- [ ] **Step 1: Tạo lớp ViolationContext**

```php
<?php

namespace App\Services\OrderCheck\Support;

/**
 * Snapshot ngữ cảnh kèm theo mỗi violation khi ghi DB.
 * Tách khỏi OrderContext để mọi scanner (service_req, interaction-log, ...) dùng chung.
 */
class ViolationContext
{
    public $treatmentId;
    public $treatmentCode;
    public $patientCode;
    public $patientName;
    public $doctorLoginname;
    public $doctorUsername;
    public $departmentId;

    public static function make(array $a)
    {
        $c = new self();
        $c->treatmentId = isset($a['treatment_id']) ? $a['treatment_id'] : null;
        $c->treatmentCode = isset($a['treatment_code']) ? $a['treatment_code'] : null;
        $c->patientCode = isset($a['patient_code']) ? $a['patient_code'] : null;
        $c->patientName = isset($a['patient_name']) ? $a['patient_name'] : null;
        $c->doctorLoginname = isset($a['doctor_loginname']) ? $a['doctor_loginname'] : null;
        $c->doctorUsername = isset($a['doctor_username']) ? $a['doctor_username'] : null;
        $c->departmentId = isset($a['department_id']) ? $a['department_id'] : null;
        return $c;
    }

    public static function fromOrderContext(OrderContext $o)
    {
        return self::make([
            'treatment_id' => $o->treatmentId,
            'treatment_code' => $o->treatmentCode,
            'patient_code' => $o->patientCode,
            'patient_name' => $o->patientName,
            'doctor_loginname' => $o->doctorLoginname,
            'doctor_username' => $o->doctorUsername,
            'department_id' => $o->departmentId,
        ]);
    }
}
```

- [ ] **Step 2: Verify autoload**

Run (Bash; tinker đọc qua stdin, KHÔNG dùng `--execute`):
```bash
echo 'echo class_exists(App\Services\OrderCheck\Support\ViolationContext::class) ? "ok" : "no";' | php artisan tinker
```
Expected: in `ok`

- [ ] **Step 3: Commit** (BỎ QUA nếu người điều phối yêu cầu không commit)

```bash
git add app/Services/OrderCheck/Support/ViolationContext.php
git commit -m "feat(order-check): ViolationContext snapshot dung chung cho scanner"
```

---

## Task 2: Interface Scanner

**Files:**
- Create: `app/Services/OrderCheck/Contracts/Scanner.php`

- [ ] **Step 1: Tạo interface**

```php
<?php

namespace App\Services\OrderCheck\Contracts;

use App\Services\OrderCheck\OrderCheckEngine;

interface Scanner
{
    /** Khóa nguồn, dùng cho watermark + rule_log. @return string */
    public function sourceKey();

    /**
     * Quét 1 lô từ nguồn của scanner, ghi violation qua $engine.
     * @param OrderCheckEngine $engine
     * @param int $limit
     * @return array ['scanned' => int, 'violations' => int]
     */
    public function scan(OrderCheckEngine $engine, $limit);
}
```

- [ ] **Step 2: Verify**

```bash
echo 'echo interface_exists(App\Services\OrderCheck\Contracts\Scanner::class) ? "ok" : "no";' | php artisan tinker
```
Expected: in `ok`

- [ ] **Step 3: Commit** (bỏ qua nếu được yêu cầu)

```bash
git add app/Services/OrderCheck/Contracts/Scanner.php
git commit -m "feat(order-check): interface Scanner da-nguon"
```

---

## Task 3: Refactor OrderCheckEngine thành điều phối đa-scanner

**Files:**
- Modify: `app/Services/OrderCheck/OrderCheckEngine.php` (thay toàn bộ nội dung)

> Engine mới: cung cấp helper dùng chung (`activeRules()`, `getWatermark()`, `saveWatermark()`, `persist()`), và `run()` lặp qua các scanner. Giữ method `run($limit)` để command Plan 1 không phải đổi.

- [ ] **Step 1: Thay toàn bộ nội dung file**

```php
<?php

namespace App\Services\OrderCheck;

use App\Models\OrderCheck\OrderCheckWatermark;
use App\Models\OrderCheck\OrderCheckRule;
use App\Models\OrderCheck\OrderCheckViolation;
use App\Models\OrderCheck\OrderCheckRuleLog;
use App\Services\OrderCheck\Scanners\ScannerRegistry;
use App\Services\OrderCheck\Support\Violation;
use App\Services\OrderCheck\Support\ViolationContext;

class OrderCheckEngine
{
    protected $source;
    protected $rulesByCode; // cache trong 1 lần run()

    public function __construct(HisOrderSource $source)
    {
        $this->source = $source;
    }

    public function source()
    {
        return $this->source;
    }

    /** Chạy tất cả scanner đã đăng ký. Trả tổng hợp. */
    public function run($limit = null)
    {
        $limit = $limit ?: (int) config('order_check.batch_size');
        $this->rulesByCode = OrderCheckRule::where('is_active', true)->get()->keyBy('code');

        $totalScanned = 0;
        $totalViolations = 0;

        foreach (ScannerRegistry::all($this->source) as $scanner) {
            $log = OrderCheckRuleLog::create([
                'source_key' => $scanner->sourceKey(),
                'started_at' => now(),
                'status' => 'running',
            ]);
            try {
                $res = $scanner->scan($this, $limit);
                $log->update([
                    'finished_at' => now(),
                    'scanned_count' => $res['scanned'],
                    'violation_count' => $res['violations'],
                    'status' => 'success',
                ]);
                $totalScanned += $res['scanned'];
                $totalViolations += $res['violations'];
            } catch (\Exception $e) {
                $log->update([
                    'finished_at' => now(),
                    'status' => 'error',
                    'error' => $e->getMessage(),
                ]);
                throw $e;
            }
        }

        return ['scanned' => $totalScanned, 'violations' => $totalViolations];
    }

    /** Rule active theo code (đã cache trong run()). */
    public function activeRules()
    {
        if ($this->rulesByCode === null) {
            $this->rulesByCode = OrderCheckRule::where('is_active', true)->get()->keyBy('code');
        }
        return $this->rulesByCode;
    }

    public function getWatermark($sourceKey)
    {
        return OrderCheckWatermark::firstOrCreate(
            ['source_key' => $sourceKey],
            ['last_create_time' => 0, 'last_modify_time' => 0, 'last_id' => 0]
        );
    }

    public function saveWatermark($sourceKey, $lastCreateTime, $lastId)
    {
        $wm = $this->getWatermark($sourceKey);
        $wm->last_create_time = $lastCreateTime;
        $wm->last_id = $lastId;
        $wm->last_run_at = now();
        $wm->save();
        return $wm;
    }

    /**
     * Ghi 1 violation idempotent theo dedup_key. Trả true nếu tạo mới.
     * @param Violation $vio
     * @param ViolationContext $ctx
     * @param OrderCheckRule $rule
     */
    public function persist(Violation $vio, ViolationContext $ctx, OrderCheckRule $rule)
    {
        $dedup = $vio->dedupKey();
        $row = OrderCheckViolation::where('dedup_key', $dedup)->first();

        if ($row && in_array($row->status, ['processed', 'false_positive'])) {
            return false;
        }

        $isNew = !$row;
        if ($isNew) {
            $row = new OrderCheckViolation();
            $row->dedup_key = $dedup;
            $row->status = 'new';
            $row->detected_at = now();
        }

        $row->rule_id = $rule->id;
        $row->rule_code = $vio->ruleCode;
        $row->treatment_id = $ctx->treatmentId;
        $row->treatment_code = $ctx->treatmentCode;
        $row->patient_code = $ctx->patientCode;
        $row->patient_name = $ctx->patientName;
        $row->doctor_loginname = $ctx->doctorLoginname;
        $row->doctor_username = $ctx->doctorUsername;
        $row->department_id = $ctx->departmentId;
        $row->order_ref_type = $vio->orderRefType;
        $row->order_ref_id = $vio->orderRefId;
        $row->severity = $rule->severity;
        $row->message = $vio->message;
        $row->detail = json_encode($vio->detail, JSON_UNESCAPED_UNICODE);
        $row->save();

        return $isNew;
    }
}
```

- [ ] **Step 2: KHÔNG verify chạy ở task này** (ScannerRegistry + scanner chưa tồn tại → sẽ verify ở Task 6). Chỉ kiểm tra cú pháp:

```bash
php -l app/Services/OrderCheck/OrderCheckEngine.php
```
Expected: `No syntax errors detected`

- [ ] **Step 3: Commit** (bỏ qua nếu được yêu cầu)

```bash
git add app/Services/OrderCheck/OrderCheckEngine.php
git commit -m "refactor(order-check): engine dieu phoi da-scanner + persist dung ViolationContext"
```

---

## Task 4: A4 — MissingDiagnosisRule (handler Họ A cấp phiếu) + registry

**Files:**
- Create: `app/Services/OrderCheck/RuleHandlers/Clinical/MissingDiagnosisRule.php`
- Create: `app/Services/OrderCheck/RuleHandlers/ClinicalServiceReqRuleRegistry.php`
- Test: `tests/Unit/OrderCheck/MissingDiagnosisRuleTest.php`

- [ ] **Step 1: Viết test thất bại**

```php
<?php

namespace Tests\Unit\OrderCheck;

use Tests\TestCase;
use App\Services\OrderCheck\Support\OrderContext;
use App\Services\OrderCheck\RuleHandlers\Clinical\MissingDiagnosisRule;

class MissingDiagnosisRuleTest extends TestCase
{
    private function ctx($icd)
    {
        $c = new OrderContext();
        $c->serviceReqId = 7;
        $c->treatmentId = 70;
        $c->icdCode = $icd;
        return $c;
    }

    public function test_thieu_icd_phat_hien_loi()
    {
        $rule = new MissingDiagnosisRule();
        $vios = $rule->check($this->ctx(''));
        $this->assertCount(1, $vios);
        $this->assertSame('service_req', $vios[0]->orderRefType);
        $this->assertSame(7, $vios[0]->orderRefId);
    }

    public function test_icd_null_phat_hien_loi()
    {
        $rule = new MissingDiagnosisRule();
        $this->assertCount(1, $rule->check($this->ctx(null)));
    }

    public function test_co_icd_khong_loi()
    {
        $rule = new MissingDiagnosisRule();
        $this->assertCount(0, $rule->check($this->ctx('J18')));
    }
}
```

- [ ] **Step 2: Chạy test, xác nhận FAIL**

Run: `vendor/bin/phpunit --filter MissingDiagnosisRuleTest`
Expected: FAIL với "Class '...MissingDiagnosisRule' not found"

- [ ] **Step 3: Cài đặt handler**

```php
<?php

namespace App\Services\OrderCheck\RuleHandlers\Clinical;

use App\Services\OrderCheck\Contracts\RuleHandler;
use App\Services\OrderCheck\Support\OrderContext;
use App\Services\OrderCheck\Support\Violation;

class MissingDiagnosisRule implements RuleHandler
{
    public function code()
    {
        return 'A_MISSING_DIAGNOSIS';
    }

    public function check(OrderContext $c)
    {
        if (empty(trim((string) $c->icdCode))) {
            return [new Violation(
                $this->code(),
                'service_req',
                $c->serviceReqId,
                'Phiếu chỉ định thiếu mã chẩn đoán ICD',
                ['service_req_code' => $c->serviceReqCode]
            )];
        }
        return [];
    }
}
```

- [ ] **Step 4: Tạo registry handler Họ A cấp phiếu**

```php
<?php

namespace App\Services\OrderCheck\RuleHandlers;

use App\Services\OrderCheck\RuleHandlers\Clinical\MissingDiagnosisRule;

/**
 * Handler Họ A áp dụng trên OrderContext (cấp phiếu chỉ định).
 * Thêm handler cấp phiếu mới = thêm 1 dòng vào đây.
 */
class ClinicalServiceReqRuleRegistry
{
    /** @return \App\Services\OrderCheck\Contracts\RuleHandler[] */
    public static function handlers()
    {
        return [
            new MissingDiagnosisRule(),
        ];
    }
}
```

- [ ] **Step 5: Chạy test, xác nhận PASS**

Run: `vendor/bin/phpunit --filter MissingDiagnosisRuleTest`
Expected: PASS (3 tests)

- [ ] **Step 6: Commit** (bỏ qua nếu được yêu cầu)

```bash
git add app/Services/OrderCheck/RuleHandlers/Clinical/MissingDiagnosisRule.php app/Services/OrderCheck/RuleHandlers/ClinicalServiceReqRuleRegistry.php tests/Unit/OrderCheck/MissingDiagnosisRuleTest.php
git commit -m "feat(order-check): rule A4 thieu chan doan ICD + registry Ho A cap phieu"
```

---

## Task 5: ServiceReqScanner (bọc luồng Plan 1 + A4)

**Files:**
- Create: `app/Services/OrderCheck/Scanners/ServiceReqScanner.php`

> Gộp Họ B (`StructuralRuleRegistry`) + Họ A cấp phiếu (`ClinicalServiceReqRuleRegistry`) chạy trên cùng `OrderContext`. Logic fetch + watermark giữ y như Plan 1.

- [ ] **Step 1: Tạo scanner**

```php
<?php

namespace App\Services\OrderCheck\Scanners;

use App\Services\OrderCheck\Contracts\Scanner;
use App\Services\OrderCheck\OrderCheckEngine;
use App\Services\OrderCheck\RuleHandlers\StructuralRuleRegistry;
use App\Services\OrderCheck\RuleHandlers\ClinicalServiceReqRuleRegistry;
use App\Services\OrderCheck\Support\ViolationContext;

class ServiceReqScanner implements Scanner
{
    const SOURCE_KEY = 'his_service_req';

    public function sourceKey()
    {
        return self::SOURCE_KEY;
    }

    public function scan(OrderCheckEngine $engine, $limit)
    {
        $source = $engine->source();
        $rulesByCode = $engine->activeRules();
        $handlers = array_merge(
            StructuralRuleRegistry::handlers(),
            ClinicalServiceReqRuleRegistry::handlers()
        );

        $wm = $engine->getWatermark(self::SOURCE_KEY);
        $rows = $source->fetchServiceRequests($wm->last_create_time, $wm->last_id, $limit);
        $scanned = $rows->count();
        $violations = 0;

        if ($scanned > 0) {
            $reqIds = $rows->pluck('id')->map(function ($v) { return (int) $v; })->all();
            $servicesMap = $source->fetchServicesByReqIds($reqIds);

            $maxCreate = $wm->last_create_time;
            $maxId = $wm->last_id;

            foreach ($rows as $row) {
                $ctx = $source->buildContext($row, isset($servicesMap[(int) $row->id]) ? $servicesMap[(int) $row->id] : []);
                $vctx = ViolationContext::fromOrderContext($ctx);

                foreach ($handlers as $handler) {
                    if (!isset($rulesByCode[$handler->code()])) {
                        continue;
                    }
                    $rule = $rulesByCode[$handler->code()];
                    foreach ($handler->check($ctx) as $vio) {
                        if ($engine->persist($vio, $vctx, $rule)) {
                            $violations++;
                        }
                    }
                }

                if ((int) $row->create_time > $maxCreate || ((int) $row->create_time == $maxCreate && (int) $row->id > $maxId)) {
                    $maxCreate = (int) $row->create_time;
                    $maxId = (int) $row->id;
                }
            }

            $engine->saveWatermark(self::SOURCE_KEY, $maxCreate, $maxId);
        }

        return ['scanned' => $scanned, 'violations' => $violations];
    }
}
```

- [ ] **Step 2: Kiểm tra cú pháp**

Run: `php -l app/Services/OrderCheck/Scanners/ServiceReqScanner.php`
Expected: `No syntax errors detected`

- [ ] **Step 3: Commit** (bỏ qua nếu được yêu cầu)

```bash
git add app/Services/OrderCheck/Scanners/ServiceReqScanner.php
git commit -m "feat(order-check): ServiceReqScanner (Ho B + A4) tren OrderContext"
```

---

## Task 6: A1 — InteractionLogScanner + HisOrderSource::fetchInteractions

**Files:**
- Modify: `app/Services/OrderCheck/HisOrderSource.php` (thêm method)
- Create: `app/Services/OrderCheck/Scanners/InteractionLogScanner.php`

- [ ] **Step 1: Thêm method `fetchInteractions` vào `HisOrderSource`**

Thêm method sau vào trong class `HisOrderSource` (sau `fetchServicesByReqIds`):

```php
    /**
     * Lấy lô tương tác thuốc HIS đã phát hiện, theo watermark (create_time, id).
     * Mỗi dòng đã gắn treatment_id, bác sĩ, ICD, cặp thuốc, mức độ.
     */
    public function fetchInteractions($lastCreateTime, $lastId, $limit)
    {
        return DB::connection($this->conn)
            ->table('his_medicine_interactive')
            ->where('is_delete', 0)
            ->where(function ($w) use ($lastCreateTime, $lastId) {
                $w->where('create_time', '>', $lastCreateTime)
                  ->orWhere(function ($w2) use ($lastCreateTime, $lastId) {
                      $w2->where('create_time', '=', $lastCreateTime)
                         ->where('id', '>', $lastId);
                  });
            })
            ->orderBy('create_time')
            ->orderBy('id')
            ->limit($limit)
            ->selectRaw('id, create_time, treatment_id, request_loginname,
                request_department_id, icd_code, icd_name,
                medicine_type_id1, medicine_type_id2, interactive_grade_id')
            ->get();
    }
```

- [ ] **Step 2: Verify fetchInteractions đọc HIS thật**

Tạo file tạm `verify_a1.php`:
```php
$s = app(App\Services\OrderCheck\HisOrderSource::class);
$rows = $s->fetchInteractions(0, 0, 3);
echo $rows->count() . PHP_EOL;
foreach ($rows as $r) { echo $r->id . " tr=" . $r->treatment_id . " m1=" . $r->medicine_type_id1 . " m2=" . $r->medicine_type_id2 . " grade=" . $r->interactive_grade_id . PHP_EOL; }
```
Run: `php artisan tinker < verify_a1.php` rồi xóa file.
Expected: in `3` và 3 dòng có `m1`/`m2`/`grade`. Nếu lỗi cột → BLOCKED + báo lỗi nguyên văn.

- [ ] **Step 3: Tạo InteractionLogScanner**

```php
<?php

namespace App\Services\OrderCheck\Scanners;

use App\Services\OrderCheck\Contracts\Scanner;
use App\Services\OrderCheck\OrderCheckEngine;
use App\Services\OrderCheck\Support\Violation;
use App\Services\OrderCheck\Support\ViolationContext;

class InteractionLogScanner implements Scanner
{
    const SOURCE_KEY = 'his_medicine_interactive';
    const RULE_CODE = 'A_DRUG_INTERACTION';

    public function sourceKey()
    {
        return self::SOURCE_KEY;
    }

    public function scan(OrderCheckEngine $engine, $limit)
    {
        $rulesByCode = $engine->activeRules();

        // Rule A1 bị tắt → vẫn tiến watermark để không tồn đọng, nhưng không sinh violation.
        $ruleActive = isset($rulesByCode[self::RULE_CODE]);
        $rule = $ruleActive ? $rulesByCode[self::RULE_CODE] : null;

        $source = $engine->source();
        $wm = $engine->getWatermark(self::SOURCE_KEY);
        $rows = $source->fetchInteractions($wm->last_create_time, $wm->last_id, $limit);
        $scanned = $rows->count();
        $violations = 0;

        if ($scanned > 0) {
            $maxCreate = $wm->last_create_time;
            $maxId = $wm->last_id;

            foreach ($rows as $row) {
                if ($ruleActive) {
                    $vctx = ViolationContext::make([
                        'treatment_id' => (int) $row->treatment_id,
                        'doctor_loginname' => $row->request_loginname,
                        'department_id' => $row->request_department_id !== null ? (int) $row->request_department_id : null,
                    ]);

                    $vio = new Violation(
                        self::RULE_CODE,
                        'medicine_interactive',
                        (int) $row->id,
                        'Tương tác thuốc (HIS phát hiện): cặp thuốc ' . $row->medicine_type_id1 . ' - ' . $row->medicine_type_id2 . ', mức độ ' . $row->interactive_grade_id,
                        [
                            'medicine_type_id1' => (int) $row->medicine_type_id1,
                            'medicine_type_id2' => (int) $row->medicine_type_id2,
                            'interactive_grade_id' => $row->interactive_grade_id !== null ? (int) $row->interactive_grade_id : null,
                            'icd_code' => $row->icd_code,
                        ]
                    );

                    if ($engine->persist($vio, $vctx, $rule)) {
                        $violations++;
                    }
                }

                if ((int) $row->create_time > $maxCreate || ((int) $row->create_time == $maxCreate && (int) $row->id > $maxId)) {
                    $maxCreate = (int) $row->create_time;
                    $maxId = (int) $row->id;
                }
            }

            $engine->saveWatermark(self::SOURCE_KEY, $maxCreate, $maxId);
        }

        return ['scanned' => $scanned, 'violations' => $violations];
    }
}
```

- [ ] **Step 4: Kiểm tra cú pháp**

Run: `php -l app/Services/OrderCheck/Scanners/InteractionLogScanner.php`
Expected: `No syntax errors detected`

- [ ] **Step 5: Commit** (bỏ qua nếu được yêu cầu)

```bash
git add app/Services/OrderCheck/HisOrderSource.php app/Services/OrderCheck/Scanners/InteractionLogScanner.php
git commit -m "feat(order-check): A1 quet log tuong tac thuoc HIS (InteractionLogScanner)"
```

---

## Task 7: ScannerRegistry + seed rule A1/A4

**Files:**
- Create: `app/Services/OrderCheck/Scanners/ScannerRegistry.php`
- Create: `database/migrations/2026_06_30_130000_seed_order_check_clinical_rules_a1_a4.php`

- [ ] **Step 1: Tạo ScannerRegistry**

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
        ];
    }
}
```

- [ ] **Step 2: Tạo migration seed A1/A4**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class SeedOrderCheckClinicalRulesA1A4 extends Migration
{
    public function up()
    {
        $now = date('Y-m-d H:i:s');
        $rules = [
            ['code' => 'A_DRUG_INTERACTION', 'rule_type' => 'InteractionLogScanner', 'name' => 'Tương tác thuốc (HIS phát hiện)', 'severity' => 'warning'],
            ['code' => 'A_MISSING_DIAGNOSIS', 'rule_type' => 'MissingDiagnosisRule', 'name' => 'Phiếu chỉ định thiếu chẩn đoán ICD', 'severity' => 'warning'],
        ];

        foreach ($rules as $r) {
            $exists = DB::table('order_check_rules')->where('code', $r['code'])->exists();
            if (!$exists) {
                DB::table('order_check_rules')->insert([
                    'code' => $r['code'],
                    'family' => 'A',
                    'rule_type' => $r['rule_type'],
                    'name' => $r['name'],
                    'severity' => $r['severity'],
                    'params' => null,
                    'scope' => null,
                    'is_active' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down()
    {
        DB::table('order_check_rules')->whereIn('code', ['A_DRUG_INTERACTION', 'A_MISSING_DIAGNOSIS'])->delete();
    }
}
```

- [ ] **Step 3: Chạy migrate**

Run: `php artisan migrate`
Expected: `Migrated: 2026_06_30_130000_seed_order_check_clinical_rules_a1_a4`

```bash
echo 'echo App\Models\OrderCheck\OrderCheckRule::where("family","A")->count();' | php artisan tinker
```
Expected: in `2`

- [ ] **Step 4: Commit** (bỏ qua nếu được yêu cầu)

```bash
git add app/Services/OrderCheck/Scanners/ScannerRegistry.php database/migrations/2026_06_30_130000_seed_order_check_clinical_rules_a1_a4.php
git commit -m "feat(order-check): ScannerRegistry + seed rule A1/A4"
```

---

## Task 8: Verify end-to-end + regression + readme

**Files:**
- Modify: `readme.md`

- [ ] **Step 1: Regression toàn bộ Unit OrderCheck (Plan 1 + A4)**

Run: `vendor/bin/phpunit tests/Unit/OrderCheck`
Expected: PASS toàn bộ (Plan 1: 14 + MissingDiagnosis: 3 = 17 tests).

- [ ] **Step 2: Chạy engine thật 1 lần (cả 2 scanner)**

> Watermark `his_medicine_interactive` lần đầu = 0 → A1 sẽ backfill log tương tác lịch sử. Nếu CHỈ muốn bắt tương tác mới từ nay, set watermark trước (xem Step 3). Để verify, chạy giới hạn nhỏ trước:

Tạo file tạm `verify_run.php`:
```php
$r = app(App\Services\OrderCheck\OrderCheckEngine::class)->run(20);
echo "scanned=" . $r['scanned'] . " violations=" . $r['violations'] . PHP_EOL;
foreach (App\Models\OrderCheck\OrderCheckRuleLog::latest('id')->take(2)->get() as $l) {
    echo $l->source_key . " => " . $l->status . " scanned=" . $l->scanned_count . " vio=" . $l->violation_count . PHP_EOL;
}
```
Run: `php artisan tinker < verify_run.php` rồi xóa file.
Expected: 2 dòng log (`his_service_req`, `his_medicine_interactive`) status `success`, không exception.

- [ ] **Step 3: (Quyết định vận hành) Set mốc A1 = hiện tại nếu không muốn backfill**

Nếu KHÔNG muốn backfill toàn bộ log tương tác lịch sử, chạy:
```bash
echo '$n=(int)date("YmdHis"); App\Models\OrderCheck\OrderCheckWatermark::updateOrCreate(["source_key"=>"his_medicine_interactive"],["last_create_time"=>$n,"last_modify_time"=>$n,"last_id"=>0,"last_run_at"=>date("Y-m-d H:i:s")]); echo "set";' | php artisan tinker
```
Expected: in `set`. (Bỏ qua nếu MUỐN backfill.)

- [ ] **Step 4: Cập nhật readme**

Thêm vào đầu `readme.md` (trên khối `# 30/06/2026` của Plan 1) bằng Edit tool:

```markdown
# 30/06/2026 (cập nhật)

- Module Kiểm tra sai sót y lệnh (giai đoạn 2): tổng quát hóa engine đa-nguồn (multi-scanner); bổ sung luật A1 nạp cảnh báo tương tác thuốc do HIS phát hiện (HIS_MEDICINE_INTERACTIVE) và A4 phát hiện phiếu chỉ định thiếu chẩn đoán ICD. Các luật bật/tắt trong order_check_rules.

```

- [ ] **Step 5: Commit** (bỏ qua nếu được yêu cầu)

```bash
git add readme.md
git commit -m "docs(order-check): ghi chu readme giai doan 2 (A1/A4 + engine da-nguon)"
```

---

## Self-Review (đã thực hiện khi viết plan)

**1. Spec coverage (Plan 2 scope):**
- Tương tác thuốc (đọc log HIS) → Task 6 InteractionLogScanner + Task 7 seed A1. ✅
- Thiếu chẩn đoán → Task 4 MissingDiagnosisRule. ✅
- Engine đa-nguồn để mở rộng → Task 1–3, 5, 7 (ViolationContext, Scanner, refactor engine, ServiceReqScanner, ScannerRegistry). ✅
- Giữ Họ B Plan 1 hoạt động → Task 5 ServiceReqScanner gộp StructuralRuleRegistry; Task 8 regression 17 test. ✅
- Idempotent/watermark/log cho nguồn mới → Task 3 helper + Task 6 scanner dùng dedup_key + watermark riêng `his_medicine_interactive`. ✅

**2. Ngoài phạm vi Plan 2 → Plan 3 (nêu rõ lý do, KHÔNG để placeholder trong plan này):**
- A2 trùng hoạt chất, A3 trùng dịch vụ, A5 liều/đường dùng: đều là luật **cấp đợt điều trị** (phải re-evaluate toàn bộ item của 1 treatment khi có item mới) — cần pattern "treatment re-scan" khác hẳn row-watermark, xứng đáng thiết kế riêng. Dữ liệu đã xác minh sẵn: `HIS_EXP_MEST_MEDICINE` (MEDICINE_ID, TDL_MEDICINE_TYPE_ID, AMOUNT, DAY_COUNT, MORNING/NOON/AFTERNOON/EVENING, TDL_TREATMENT_ID) join `HIS_MEDICINE.ACTIVE_INGR_BHYT_CODE`; trùng DV từ `HIS_SERE_SERV` theo treatment.
- Gender/diagnosis, age/weight, BHYT payability: cần **dữ liệu tham chiếu không có sẵn trong HIS** (danh mục DV giới hạn giới tính/tuổi, quy tắc BHYT) → cần bảng `order_check_ref_*` + cấu hình, và tận dụng `CheckBHYT` hiện có. Thiết kế ở Plan 3.

**3. Placeholder scan:** mọi step in-scope có code/lệnh + output kỳ vọng cụ thể; phần Plan 3 là "ngoài phạm vi" có chủ đích, không phải stub.

**4. Type consistency:** `Scanner::scan()` trả `['scanned','violations']` — khớp engine.run() (Task 3) ↔ ServiceReqScanner (Task 5) ↔ InteractionLogScanner (Task 6). `engine->persist(Violation, ViolationContext, OrderCheckRule)` — khớp Task 3 ↔ 5 ↔ 6. `ViolationContext::make()`/`fromOrderContext()` (Task 1) dùng đúng ở Task 5/6. `OrderContext->icdCode` (Plan 1) dùng ở Task 4. Watermark keys `his_service_req`/`his_medicine_interactive` nhất quán. ✅

**Kiểm thử:** handler thuần (MissingDiagnosis) test PHPUnit; scanner/source gắn Oracle live verify bằng artisan — đúng pattern dự án.
