# Order Check — Plan 1: Nền tảng + Họ luật B (cấu trúc/thời gian & hành nghề) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Dựng nền tảng module kiểm tra sai sót y lệnh (đọc HIS qua connection `HISPro`, lưu kết quả MySQL `qlbv`) và triển khai trọn vẹn 4 luật Họ B (hardcode) thành một lát cắt chạy được end-to-end qua command `kiemtraylenh:scan`.

**Architecture:** Command Laravel chạy định kỳ → `HisOrderSource` đọc `HIS_SERVICE_REQ` (mới theo watermark) + join `HIS_TREATMENT`/`HIS_EMPLOYEE`/`HIS_SERE_SERV` dựng `OrderContext` → `OrderCheckEngine` chạy các `RuleHandler` (Họ B) lấy từ registry → ghi `order_check_violations` (idempotent qua `dedup_key`) + `order_check_rule_logs`. Mọi dữ liệu ghi nằm trong MySQL; HIS chỉ SELECT.

**Tech Stack:** PHP 7 / Laravel 5.5, Eloquent, yajra/laravel-oci8 (connection `HISPro`), MySQL (`qlbv`), PHPUnit (suite Unit/Feature).

**Tham chiếu spec:** `docs/superpowers/specs/2026-06-30-kiem-tra-sai-sot-y-lenh-design.md`

---

## Lộ trình tổng (3 plan)

- **Plan 1 (tài liệu này):** Nền tảng (4 bảng MySQL, models, DTO, source adapter, engine, command, registry) + 4 luật Họ B. Chạy được, test được độc lập.
- **Plan 2:** Họ A — luật lâm sàng data-driven (tương tác/trùng thuốc, liều, ICD/giới tính, BHYT) + cache dữ liệu tham chiếu. Tận dụng engine/registry của Plan 1.
- **Plan 3:** Đầu ra — dashboard + Excel, thông báo email/SMS/Telegram, workflow xử lý, API JSON; auto-resolve violation khi y lệnh bị sửa/hủy.

---

## File Structure (Plan 1)

**Tạo mới:**
- `config/order_check.php` — cấu hình connection HIS, batch size, exclude treatment types.
- `database/migrations/2026_06_30_120000_create_order_check_watermarks_table.php`
- `database/migrations/2026_06_30_120001_create_order_check_rules_table.php`
- `database/migrations/2026_06_30_120002_create_order_check_violations_table.php`
- `database/migrations/2026_06_30_120003_create_order_check_rule_logs_table.php`
- `database/migrations/2026_06_30_120004_seed_order_check_structural_rules.php` — chèn 4 rule Họ B mặc định.
- `app/Models/OrderCheck/OrderCheckWatermark.php`
- `app/Models/OrderCheck/OrderCheckRule.php`
- `app/Models/OrderCheck/OrderCheckViolation.php`
- `app/Models/OrderCheck/OrderCheckRuleLog.php`
- `app/Services/OrderCheck/Support/OrderContext.php` — DTO ngữ cảnh y lệnh.
- `app/Services/OrderCheck/Support/OrderService.php` — DTO dịch vụ con.
- `app/Services/OrderCheck/Support/Violation.php` — value object vi phạm.
- `app/Services/OrderCheck/Contracts/RuleHandler.php` — interface luật.
- `app/Services/OrderCheck/RuleHandlers/Structural/DischargeBeforeAdmissionRule.php`
- `app/Services/OrderCheck/RuleHandlers/Structural/OrderTimeOutOfStayRule.php`
- `app/Services/OrderCheck/RuleHandlers/Structural/ExecuteBeforeOrderRule.php`
- `app/Services/OrderCheck/RuleHandlers/Structural/DoctorPracticeCertRule.php`
- `app/Services/OrderCheck/RuleHandlers/StructuralRuleRegistry.php` — **chỗ cập nhật riêng luật Họ B**.
- `app/Services/OrderCheck/HisOrderSource.php` — đọc HIS, dựng OrderContext.
- `app/Services/OrderCheck/OrderCheckEngine.php` — điều phối + ghi violation/log.
- `app/Console/Commands/HISProKiemTraYLenh.php` — command `kiemtraylenh:scan`.
- `tests/Unit/OrderCheck/StructuralRulesTest.php`
- `tests/Unit/OrderCheck/ViolationTest.php`

**Sửa:** không sửa file hiện có (chỉ thêm mới). Command tự nạp qua `app/Console/Kernel.php::load(__DIR__.'/Commands')` đã có sẵn.

---

## Quy ước dữ liệu HIS (đã xác minh)

- Thời gian là NUMBER dạng `YYYYMMDDHH24MISS` → **so sánh trực tiếp bằng số nguyên** (không cần parse ngày).
- `HIS_SERVICE_REQ`: `ID, CREATE_TIME, MODIFY_TIME, IS_DELETE, IS_ACTIVE, SERVICE_REQ_CODE, SERVICE_REQ_TYPE_ID, TREATMENT_ID, INTRUCTION_TIME, REQUEST_ROOM_ID, REQUEST_DEPARTMENT_ID, REQUEST_LOGINNAME, REQUEST_USERNAME, ICD_CODE, ICD_NAME, TDL_TREATMENT_CODE, TDL_PATIENT_CODE, TDL_PATIENT_NAME`.
- `HIS_TREATMENT`: `ID, IN_TIME, OUT_TIME` (join theo `TREATMENT_ID`).
- `HIS_EMPLOYEE`: `LOGINNAME, PRACTICE_SCOPE_DECISION` (join `REQUEST_LOGINNAME = LOGINNAME`).
- `HIS_SERE_SERV`: `ID, SERVICE_REQ_ID, SERVICE_ID, TDL_SERVICE_CODE, TDL_SERVICE_NAME, EXECUTE_TIME, TDL_INTRUCTION_TIME, IS_DELETE`.

> Bảng/cột query qua builder dùng **chữ thường** (theo pattern command HISPro hiện có: `DB::connection('HISPro')->table('his_service_req')`).

---

## Task 1: Config module

**Files:**
- Create: `config/order_check.php`

- [ ] **Step 1: Tạo file config**

```php
<?php

return [
    // Connection đọc HIS (chỉ SELECT)
    'his_connection' => 'HISPro',

    // Số phiếu chỉ định tối đa xử lý mỗi lần quét
    'batch_size' => 500,

    // Bỏ qua các loại điều trị không áp dụng (vd loại test), CSV id; rỗng = không loại
    'exclude_treatment_type_ids' => env('ORDER_CHECK_EXCLUDE_TREATMENT_TYPES', ''),
];
```

- [ ] **Step 2: Xác minh config nạp được**

Run: `php artisan tinker --execute="echo config('order_check.his_connection');"`
Expected: in ra `HISPro`

- [ ] **Step 3: Commit**

```bash
git add config/order_check.php
git commit -m "feat(order-check): them config module kiem tra y lenh"
```

---

## Task 2: Migrations 4 bảng MySQL

**Files:**
- Create: `database/migrations/2026_06_30_120000_create_order_check_watermarks_table.php`
- Create: `database/migrations/2026_06_30_120001_create_order_check_rules_table.php`
- Create: `database/migrations/2026_06_30_120002_create_order_check_violations_table.php`
- Create: `database/migrations/2026_06_30_120003_create_order_check_rule_logs_table.php`

- [ ] **Step 1: Migration watermarks**

```php
<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOrderCheckWatermarksTable extends Migration
{
    public function up()
    {
        Schema::create('order_check_watermarks', function (Blueprint $table) {
            $table->increments('id');
            $table->string('source_key', 100)->unique();
            $table->unsignedBigInteger('last_create_time')->default(0);
            $table->unsignedBigInteger('last_modify_time')->default(0);
            $table->unsignedBigInteger('last_id')->default(0);
            $table->timestamp('last_run_at')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('order_check_watermarks');
    }
}
```

- [ ] **Step 2: Migration rules**

```php
<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOrderCheckRulesTable extends Migration
{
    public function up()
    {
        Schema::create('order_check_rules', function (Blueprint $table) {
            $table->increments('id');
            $table->string('code', 100)->unique();      // vd B_DISCHARGE_BEFORE_ADMISSION
            $table->string('family', 1)->default('A');   // 'A' lam sang | 'B' cau truc/hardcode
            $table->string('rule_type', 150);            // ten class handler (basename)
            $table->string('name', 255);
            $table->string('severity', 20)->default('warning'); // info|warning|critical
            $table->text('params')->nullable();          // JSON cau hinh (Ho A)
            $table->text('scope')->nullable();           // JSON loc khoa/nhom DV
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('order_check_rules');
    }
}
```

- [ ] **Step 3: Migration violations**

```php
<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOrderCheckViolationsTable extends Migration
{
    public function up()
    {
        Schema::create('order_check_violations', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('rule_id')->nullable();
            $table->string('rule_code', 100);
            $table->unsignedBigInteger('treatment_id')->nullable();
            $table->string('treatment_code', 50)->nullable();
            $table->string('patient_code', 50)->nullable();
            $table->string('patient_name', 255)->nullable();
            $table->string('doctor_loginname', 100)->nullable();
            $table->string('doctor_username', 255)->nullable();
            $table->unsignedBigInteger('department_id')->nullable();
            $table->string('order_ref_type', 30);       // service_req|treatment|sere_serv
            $table->unsignedBigInteger('order_ref_id');
            $table->string('severity', 20)->default('warning');
            $table->text('message');
            $table->text('detail')->nullable();          // JSON
            $table->string('dedup_key', 200)->unique();
            $table->string('status', 20)->default('new'); // new|seen|processed|false_positive
            $table->dateTime('detected_at');
            $table->string('processed_by', 100)->nullable();
            $table->dateTime('processed_at')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index('treatment_id');
            $table->index('status');
            $table->index('detected_at');
            $table->index('rule_code');
        });
    }

    public function down()
    {
        Schema::dropIfExists('order_check_violations');
    }
}
```

- [ ] **Step 4: Migration rule_logs**

```php
<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOrderCheckRuleLogsTable extends Migration
{
    public function up()
    {
        Schema::create('order_check_rule_logs', function (Blueprint $table) {
            $table->increments('id');
            $table->string('source_key', 100);
            $table->dateTime('started_at');
            $table->dateTime('finished_at')->nullable();
            $table->unsignedInteger('scanned_count')->default(0);
            $table->unsignedInteger('violation_count')->default(0);
            $table->string('status', 20)->default('running'); // running|success|error
            $table->text('error')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('order_check_rule_logs');
    }
}
```

- [ ] **Step 5: Chạy migrate, kiểm tra**

Run: `php artisan migrate`
Expected: 4 dòng `Migrated: ..._create_order_check_*_table`

Run: `php artisan tinker --execute="echo Schema::hasTable('order_check_violations') ? 'ok' : 'no';"`
Expected: in ra `ok`

- [ ] **Step 6: Commit**

```bash
git add database/migrations/2026_06_30_12000*_*.php
git commit -m "feat(order-check): migrations 4 bang watermark/rules/violations/logs"
```

---

## Task 3: Eloquent models

**Files:**
- Create: `app/Models/OrderCheck/OrderCheckWatermark.php`
- Create: `app/Models/OrderCheck/OrderCheckRule.php`
- Create: `app/Models/OrderCheck/OrderCheckViolation.php`
- Create: `app/Models/OrderCheck/OrderCheckRuleLog.php`

- [ ] **Step 1: Model Watermark**

```php
<?php

namespace App\Models\OrderCheck;

use Illuminate\Database\Eloquent\Model;

class OrderCheckWatermark extends Model
{
    protected $table = 'order_check_watermarks';
    protected $guarded = [];
}
```

- [ ] **Step 2: Model Rule**

```php
<?php

namespace App\Models\OrderCheck;

use Illuminate\Database\Eloquent\Model;

class OrderCheckRule extends Model
{
    protected $table = 'order_check_rules';
    protected $guarded = [];
    protected $casts = ['is_active' => 'boolean'];

    public function getParamsArrayAttribute()
    {
        return $this->params ? json_decode($this->params, true) : [];
    }

    public function getScopeArrayAttribute()
    {
        return $this->scope ? json_decode($this->scope, true) : [];
    }
}
```

- [ ] **Step 3: Model Violation**

```php
<?php

namespace App\Models\OrderCheck;

use Illuminate\Database\Eloquent\Model;

class OrderCheckViolation extends Model
{
    protected $table = 'order_check_violations';
    protected $guarded = [];
}
```

- [ ] **Step 4: Model RuleLog**

```php
<?php

namespace App\Models\OrderCheck;

use Illuminate\Database\Eloquent\Model;

class OrderCheckRuleLog extends Model
{
    protected $table = 'order_check_rule_logs';
    protected $guarded = [];
}
```

- [ ] **Step 5: Xác minh autoload**

Run: `php artisan tinker --execute="echo class_exists(App\Models\OrderCheck\OrderCheckRule::class) ? 'ok' : 'no';"`
Expected: in ra `ok`

- [ ] **Step 6: Commit**

```bash
git add app/Models/OrderCheck/
git commit -m "feat(order-check): eloquent models cho 4 bang"
```

---

## Task 4: DTO OrderContext + OrderService + Violation

**Files:**
- Create: `app/Services/OrderCheck/Support/OrderService.php`
- Create: `app/Services/OrderCheck/Support/OrderContext.php`
- Create: `app/Services/OrderCheck/Support/Violation.php`
- Test: `tests/Unit/OrderCheck/ViolationTest.php`

- [ ] **Step 1: Viết test thất bại cho Violation::dedupKey**

```php
<?php

namespace Tests\Unit\OrderCheck;

use Tests\TestCase;
use App\Services\OrderCheck\Support\Violation;

class ViolationTest extends TestCase
{
    public function test_dedup_key_ket_hop_rule_ref_va_subkey()
    {
        $v = new Violation('B_TEST', 'service_req', 123, 'msg', ['a' => 1], 'after_out');
        $this->assertSame('B_TEST:service_req:123:after_out', $v->dedupKey());
    }

    public function test_dedup_key_rong_subkey_van_hop_le()
    {
        $v = new Violation('B_TEST', 'treatment', 9, 'msg');
        $this->assertSame('B_TEST:treatment:9:', $v->dedupKey());
    }
}
```

- [ ] **Step 2: Chạy test, xác nhận FAIL**

Run: `vendor/bin/phpunit --filter ViolationTest`
Expected: FAIL với "Class 'App\Services\OrderCheck\Support\Violation' not found"

- [ ] **Step 3: Tạo OrderService DTO**

```php
<?php

namespace App\Services\OrderCheck\Support;

class OrderService
{
    /** @var int */ public $sereServId;
    /** @var string */ public $serviceCode;
    /** @var string */ public $serviceName;
    /** @var int */ public $executeTime = 0;
    /** @var int */ public $tdlIntructionTime = 0;
}
```

- [ ] **Step 4: Tạo OrderContext DTO**

```php
<?php

namespace App\Services\OrderCheck\Support;

class OrderContext
{
    /** @var int */ public $serviceReqId;
    /** @var string */ public $serviceReqCode;
    /** @var int */ public $treatmentId;
    /** @var string */ public $treatmentCode;
    /** @var string */ public $patientCode;
    /** @var string */ public $patientName;
    /** @var int|null */ public $departmentId;
    /** @var string|null */ public $doctorLoginname;
    /** @var string|null */ public $doctorUsername;
    /** @var string|null */ public $doctorPracticeScope;
    /** @var int */ public $intructionTime = 0;
    /** @var int */ public $inTime = 0;
    /** @var int */ public $outTime = 0;
    /** @var string|null */ public $icdCode;

    /** @var OrderService[] */ public $services = [];
}
```

- [ ] **Step 5: Tạo Violation value object**

```php
<?php

namespace App\Services\OrderCheck\Support;

class Violation
{
    public $ruleCode;
    public $orderRefType;
    public $orderRefId;
    public $message;
    public $detail;
    public $subKey;

    public function __construct($ruleCode, $orderRefType, $orderRefId, $message, array $detail = [], $subKey = '')
    {
        $this->ruleCode = $ruleCode;
        $this->orderRefType = $orderRefType;
        $this->orderRefId = $orderRefId;
        $this->message = $message;
        $this->detail = $detail;
        $this->subKey = $subKey;
    }

    public function dedupKey()
    {
        return $this->ruleCode . ':' . $this->orderRefType . ':' . $this->orderRefId . ':' . $this->subKey;
    }
}
```

- [ ] **Step 6: Chạy test, xác nhận PASS**

Run: `vendor/bin/phpunit --filter ViolationTest`
Expected: PASS (2 tests, OK)

- [ ] **Step 7: Commit**

```bash
git add app/Services/OrderCheck/Support/ tests/Unit/OrderCheck/ViolationTest.php
git commit -m "feat(order-check): DTO OrderContext/OrderService + Violation co dedupKey"
```

---

## Task 5: Interface RuleHandler

**Files:**
- Create: `app/Services/OrderCheck/Contracts/RuleHandler.php`

- [ ] **Step 1: Tạo interface**

```php
<?php

namespace App\Services\OrderCheck\Contracts;

use App\Services\OrderCheck\Support\OrderContext;

interface RuleHandler
{
    /**
     * Mã luật, trùng với cột code trong order_check_rules.
     * @return string
     */
    public function code();

    /**
     * Kiểm tra một ngữ cảnh y lệnh.
     * @param OrderContext $context
     * @return \App\Services\OrderCheck\Support\Violation[]
     */
    public function check(OrderContext $context);
}
```

- [ ] **Step 2: Xác minh autoload**

Run: `php artisan tinker --execute="echo interface_exists(App\Services\OrderCheck\Contracts\RuleHandler::class) ? 'ok' : 'no';"`
Expected: in ra `ok`

- [ ] **Step 3: Commit**

```bash
git add app/Services/OrderCheck/Contracts/RuleHandler.php
git commit -m "feat(order-check): interface RuleHandler"
```

---

## Task 6: Luật B1 — DischargeBeforeAdmissionRule (ngày ra < ngày vào)

**Files:**
- Create: `app/Services/OrderCheck/RuleHandlers/Structural/DischargeBeforeAdmissionRule.php`
- Test: `tests/Unit/OrderCheck/StructuralRulesTest.php`

- [ ] **Step 1: Viết test thất bại**

```php
<?php

namespace Tests\Unit\OrderCheck;

use Tests\TestCase;
use App\Services\OrderCheck\Support\OrderContext;
use App\Services\OrderCheck\RuleHandlers\Structural\DischargeBeforeAdmissionRule;

class StructuralRulesTest extends TestCase
{
    private function ctx(array $over = [])
    {
        $c = new OrderContext();
        $c->serviceReqId = 1;
        $c->treatmentId = 100;
        $c->intructionTime = 20260101080000;
        $c->inTime = 20260101070000;
        $c->outTime = 0;
        $c->doctorLoginname = 'bs01';
        $c->doctorPracticeScope = 'QD-123';
        $c->services = [];
        foreach ($over as $k => $v) { $c->$k = $v; }
        return $c;
    }

    public function test_discharge_before_admission_phat_hien_loi()
    {
        $rule = new DischargeBeforeAdmissionRule();
        $c = $this->ctx(['inTime' => 20260105080000, 'outTime' => 20260103080000]);
        $vios = $rule->check($c);
        $this->assertCount(1, $vios);
        $this->assertSame('treatment', $vios[0]->orderRefType);
        $this->assertSame(100, $vios[0]->orderRefId);
    }

    public function test_discharge_hop_le_khong_loi()
    {
        $rule = new DischargeBeforeAdmissionRule();
        $c = $this->ctx(['inTime' => 20260101080000, 'outTime' => 20260105080000]);
        $this->assertCount(0, $rule->check($c));
    }

    public function test_chua_ra_vien_out_time_0_khong_loi()
    {
        $rule = new DischargeBeforeAdmissionRule();
        $c = $this->ctx(['inTime' => 20260101080000, 'outTime' => 0]);
        $this->assertCount(0, $rule->check($c));
    }
}
```

- [ ] **Step 2: Chạy test, xác nhận FAIL**

Run: `vendor/bin/phpunit --filter StructuralRulesTest`
Expected: FAIL với "Class '...DischargeBeforeAdmissionRule' not found"

- [ ] **Step 3: Cài đặt rule**

```php
<?php

namespace App\Services\OrderCheck\RuleHandlers\Structural;

use App\Services\OrderCheck\Contracts\RuleHandler;
use App\Services\OrderCheck\Support\OrderContext;
use App\Services\OrderCheck\Support\Violation;

class DischargeBeforeAdmissionRule implements RuleHandler
{
    public function code()
    {
        return 'B_DISCHARGE_BEFORE_ADMISSION';
    }

    public function check(OrderContext $c)
    {
        if ($c->outTime > 0 && $c->inTime > 0 && $c->outTime < $c->inTime) {
            return [new Violation(
                $this->code(),
                'treatment',
                $c->treatmentId,
                'Ngày ra viện (' . $c->outTime . ') trước ngày vào viện (' . $c->inTime . ')',
                ['in_time' => $c->inTime, 'out_time' => $c->outTime]
            )];
        }
        return [];
    }
}
```

- [ ] **Step 4: Chạy test, xác nhận PASS**

Run: `vendor/bin/phpunit --filter StructuralRulesTest`
Expected: PASS (3 tests)

- [ ] **Step 5: Commit**

```bash
git add app/Services/OrderCheck/RuleHandlers/Structural/DischargeBeforeAdmissionRule.php tests/Unit/OrderCheck/StructuralRulesTest.php
git commit -m "feat(order-check): rule B1 ngay ra truoc ngay vao"
```

---

## Task 7: Luật B2 — OrderTimeOutOfStayRule (giờ y lệnh ngoài đợt điều trị)

**Files:**
- Create: `app/Services/OrderCheck/RuleHandlers/Structural/OrderTimeOutOfStayRule.php`
- Modify: `tests/Unit/OrderCheck/StructuralRulesTest.php` (thêm test, import)

- [ ] **Step 1: Thêm import vào đầu file test**

Thêm dòng use sau các use hiện có trong `tests/Unit/OrderCheck/StructuralRulesTest.php`:

```php
use App\Services\OrderCheck\RuleHandlers\Structural\OrderTimeOutOfStayRule;
```

- [ ] **Step 2: Thêm test thất bại (cuối class StructuralRulesTest)**

```php
    public function test_order_time_truoc_gio_vao_vien()
    {
        $rule = new OrderTimeOutOfStayRule();
        $c = $this->ctx(['inTime' => 20260105080000, 'outTime' => 0, 'intructionTime' => 20260104080000]);
        $vios = $rule->check($c);
        $this->assertCount(1, $vios);
        $this->assertSame('before_in', $vios[0]->subKey);
        $this->assertSame('service_req', $vios[0]->orderRefType);
    }

    public function test_order_time_sau_gio_ra_vien()
    {
        $rule = new OrderTimeOutOfStayRule();
        $c = $this->ctx(['inTime' => 20260101080000, 'outTime' => 20260103080000, 'intructionTime' => 20260105080000]);
        $vios = $rule->check($c);
        $this->assertCount(1, $vios);
        $this->assertSame('after_out', $vios[0]->subKey);
    }

    public function test_order_time_trong_dot_khong_loi()
    {
        $rule = new OrderTimeOutOfStayRule();
        $c = $this->ctx(['inTime' => 20260101080000, 'outTime' => 20260110080000, 'intructionTime' => 20260105080000]);
        $this->assertCount(0, $rule->check($c));
    }
```

- [ ] **Step 3: Chạy test, xác nhận FAIL**

Run: `vendor/bin/phpunit --filter StructuralRulesTest`
Expected: FAIL với "Class '...OrderTimeOutOfStayRule' not found"

- [ ] **Step 4: Cài đặt rule**

```php
<?php

namespace App\Services\OrderCheck\RuleHandlers\Structural;

use App\Services\OrderCheck\Contracts\RuleHandler;
use App\Services\OrderCheck\Support\OrderContext;
use App\Services\OrderCheck\Support\Violation;

class OrderTimeOutOfStayRule implements RuleHandler
{
    public function code()
    {
        return 'B_ORDER_TIME_OUT_OF_STAY';
    }

    public function check(OrderContext $c)
    {
        $vios = [];
        if ($c->intructionTime > 0 && $c->inTime > 0 && $c->intructionTime < $c->inTime) {
            $vios[] = new Violation(
                $this->code(), 'service_req', $c->serviceReqId,
                'Giờ y lệnh (' . $c->intructionTime . ') trước giờ vào viện (' . $c->inTime . ')',
                ['intruction_time' => $c->intructionTime, 'in_time' => $c->inTime],
                'before_in'
            );
        }
        if ($c->intructionTime > 0 && $c->outTime > 0 && $c->intructionTime > $c->outTime) {
            $vios[] = new Violation(
                $this->code(), 'service_req', $c->serviceReqId,
                'Giờ y lệnh (' . $c->intructionTime . ') sau giờ ra viện (' . $c->outTime . ')',
                ['intruction_time' => $c->intructionTime, 'out_time' => $c->outTime],
                'after_out'
            );
        }
        return $vios;
    }
}
```

- [ ] **Step 5: Chạy test, xác nhận PASS**

Run: `vendor/bin/phpunit --filter StructuralRulesTest`
Expected: PASS (6 tests)

- [ ] **Step 6: Commit**

```bash
git add app/Services/OrderCheck/RuleHandlers/Structural/OrderTimeOutOfStayRule.php tests/Unit/OrderCheck/StructuralRulesTest.php
git commit -m "feat(order-check): rule B2 gio y lenh ngoai dot dieu tri"
```

---

## Task 8: Luật B3 — ExecuteBeforeOrderRule (giờ thực hiện trước giờ y lệnh)

**Files:**
- Create: `app/Services/OrderCheck/RuleHandlers/Structural/ExecuteBeforeOrderRule.php`
- Modify: `tests/Unit/OrderCheck/StructuralRulesTest.php`

- [ ] **Step 1: Thêm import**

```php
use App\Services\OrderCheck\RuleHandlers\Structural\ExecuteBeforeOrderRule;
use App\Services\OrderCheck\Support\OrderService;
```

- [ ] **Step 2: Thêm test thất bại**

```php
    private function svc($id, $execute, $tdlIntr = 0, $code = 'DV01')
    {
        $s = new OrderService();
        $s->sereServId = $id;
        $s->serviceCode = $code;
        $s->serviceName = 'Dich vu ' . $code;
        $s->executeTime = $execute;
        $s->tdlIntructionTime = $tdlIntr;
        return $s;
    }

    public function test_execute_truoc_gio_y_lenh_phat_hien_loi()
    {
        $rule = new ExecuteBeforeOrderRule();
        $c = $this->ctx(['intructionTime' => 20260101080000]);
        $c->services = [$this->svc(501, 20260101070000)]; // thuc hien truoc y lenh
        $vios = $rule->check($c);
        $this->assertCount(1, $vios);
        $this->assertSame('sere_serv', $vios[0]->orderRefType);
        $this->assertSame(501, $vios[0]->orderRefId);
    }

    public function test_execute_dung_chuan_khong_loi()
    {
        $rule = new ExecuteBeforeOrderRule();
        $c = $this->ctx(['intructionTime' => 20260101080000]);
        $c->services = [$this->svc(502, 20260101090000)];
        $this->assertCount(0, $rule->check($c));
    }

    public function test_execute_uu_tien_tdl_intruction_time()
    {
        $rule = new ExecuteBeforeOrderRule();
        $c = $this->ctx(['intructionTime' => 20260101060000]);
        // baseline lay tdlIntruction = 09:00, execute 08:00 < 09:00 => loi
        $c->services = [$this->svc(503, 20260101080000, 20260101090000)];
        $this->assertCount(1, $rule->check($c));
    }
```

- [ ] **Step 3: Chạy test, xác nhận FAIL**

Run: `vendor/bin/phpunit --filter StructuralRulesTest`
Expected: FAIL với "Class '...ExecuteBeforeOrderRule' not found"

- [ ] **Step 4: Cài đặt rule**

```php
<?php

namespace App\Services\OrderCheck\RuleHandlers\Structural;

use App\Services\OrderCheck\Contracts\RuleHandler;
use App\Services\OrderCheck\Support\OrderContext;
use App\Services\OrderCheck\Support\Violation;

class ExecuteBeforeOrderRule implements RuleHandler
{
    public function code()
    {
        return 'B_EXECUTE_BEFORE_ORDER';
    }

    public function check(OrderContext $c)
    {
        $vios = [];
        foreach ($c->services as $s) {
            $baseline = $s->tdlIntructionTime > 0 ? $s->tdlIntructionTime : $c->intructionTime;
            if ($s->executeTime > 0 && $baseline > 0 && $s->executeTime < $baseline) {
                $vios[] = new Violation(
                    $this->code(), 'sere_serv', $s->sereServId,
                    'Giờ thực hiện (' . $s->executeTime . ') trước giờ y lệnh (' . $baseline . ') - DV ' . $s->serviceCode,
                    ['execute_time' => $s->executeTime, 'baseline' => $baseline, 'service_code' => $s->serviceCode]
                );
            }
        }
        return $vios;
    }
}
```

- [ ] **Step 5: Chạy test, xác nhận PASS**

Run: `vendor/bin/phpunit --filter StructuralRulesTest`
Expected: PASS (9 tests)

- [ ] **Step 6: Commit**

```bash
git add app/Services/OrderCheck/RuleHandlers/Structural/ExecuteBeforeOrderRule.php tests/Unit/OrderCheck/StructuralRulesTest.php
git commit -m "feat(order-check): rule B3 gio thuc hien truoc gio y lenh"
```

---

## Task 9: Luật B4 — DoctorPracticeCertRule (BS thiếu chứng chỉ hành nghề)

**Files:**
- Create: `app/Services/OrderCheck/RuleHandlers/Structural/DoctorPracticeCertRule.php`
- Modify: `tests/Unit/OrderCheck/StructuralRulesTest.php`

- [ ] **Step 1: Thêm import**

```php
use App\Services\OrderCheck\RuleHandlers\Structural\DoctorPracticeCertRule;
```

- [ ] **Step 2: Thêm test thất bại**

```php
    public function test_bs_thieu_chung_chi_phat_hien_loi()
    {
        $rule = new DoctorPracticeCertRule();
        $c = $this->ctx(['doctorLoginname' => 'bs09', 'doctorPracticeScope' => '']);
        $vios = $rule->check($c);
        $this->assertCount(1, $vios);
        $this->assertSame('service_req', $vios[0]->orderRefType);
    }

    public function test_bs_co_chung_chi_khong_loi()
    {
        $rule = new DoctorPracticeCertRule();
        $c = $this->ctx(['doctorLoginname' => 'bs09', 'doctorPracticeScope' => 'QD-555']);
        $this->assertCount(0, $rule->check($c));
    }

    public function test_khong_co_loginname_thi_bo_qua()
    {
        $rule = new DoctorPracticeCertRule();
        $c = $this->ctx(['doctorLoginname' => null, 'doctorPracticeScope' => null]);
        $this->assertCount(0, $rule->check($c));
    }
```

- [ ] **Step 3: Chạy test, xác nhận FAIL**

Run: `vendor/bin/phpunit --filter StructuralRulesTest`
Expected: FAIL với "Class '...DoctorPracticeCertRule' not found"

- [ ] **Step 4: Cài đặt rule**

```php
<?php

namespace App\Services\OrderCheck\RuleHandlers\Structural;

use App\Services\OrderCheck\Contracts\RuleHandler;
use App\Services\OrderCheck\Support\OrderContext;
use App\Services\OrderCheck\Support\Violation;

class DoctorPracticeCertRule implements RuleHandler
{
    public function code()
    {
        return 'B_DOCTOR_NO_PRACTICE_CERT';
    }

    public function check(OrderContext $c)
    {
        $hasDoctor = !empty(trim((string) $c->doctorLoginname));
        $noCert = empty(trim((string) $c->doctorPracticeScope));
        if ($hasDoctor && $noCert) {
            return [new Violation(
                $this->code(), 'service_req', $c->serviceReqId,
                'Bác sĩ ra y lệnh (' . $c->doctorLoginname . ') chưa có/không hợp lệ chứng chỉ hành nghề',
                ['doctor_loginname' => $c->doctorLoginname]
            )];
        }
        return [];
    }
}
```

- [ ] **Step 5: Chạy test, xác nhận PASS**

Run: `vendor/bin/phpunit --filter StructuralRulesTest`
Expected: PASS (12 tests)

- [ ] **Step 6: Commit**

```bash
git add app/Services/OrderCheck/RuleHandlers/Structural/DoctorPracticeCertRule.php tests/Unit/OrderCheck/StructuralRulesTest.php
git commit -m "feat(order-check): rule B4 BS thieu chung chi hanh nghe"
```

---

## Task 10: Registry luật Họ B (chỗ cập nhật riêng)

**Files:**
- Create: `app/Services/OrderCheck/RuleHandlers/StructuralRuleRegistry.php`

- [ ] **Step 1: Tạo registry**

```php
<?php

namespace App\Services\OrderCheck\RuleHandlers;

use App\Services\OrderCheck\RuleHandlers\Structural\DischargeBeforeAdmissionRule;
use App\Services\OrderCheck\RuleHandlers\Structural\OrderTimeOutOfStayRule;
use App\Services\OrderCheck\RuleHandlers\Structural\ExecuteBeforeOrderRule;
use App\Services\OrderCheck\RuleHandlers\Structural\DoctorPracticeCertRule;

/**
 * CHỖ CẬP NHẬT RIÊNG cho luật Họ B (hardcode).
 * Thêm luật mới = thêm 1 class trong Structural/ + 1 dòng vào đây.
 */
class StructuralRuleRegistry
{
    /**
     * @return \App\Services\OrderCheck\Contracts\RuleHandler[]
     */
    public static function handlers()
    {
        return [
            new DischargeBeforeAdmissionRule(),
            new OrderTimeOutOfStayRule(),
            new ExecuteBeforeOrderRule(),
            new DoctorPracticeCertRule(),
        ];
    }
}
```

- [ ] **Step 2: Xác minh registry trả 4 handler đúng code**

Run:
```bash
php artisan tinker --execute="foreach (App\Services\OrderCheck\RuleHandlers\StructuralRuleRegistry::handlers() as \$h) { echo \$h->code() . PHP_EOL; }"
```
Expected: in ra 4 dòng:
```
B_DISCHARGE_BEFORE_ADMISSION
B_ORDER_TIME_OUT_OF_STAY
B_EXECUTE_BEFORE_ORDER
B_DOCTOR_NO_PRACTICE_CERT
```

- [ ] **Step 3: Commit**

```bash
git add app/Services/OrderCheck/RuleHandlers/StructuralRuleRegistry.php
git commit -m "feat(order-check): registry luat Ho B"
```

---

## Task 11: Seed 4 rule Họ B vào order_check_rules

**Files:**
- Create: `database/migrations/2026_06_30_120004_seed_order_check_structural_rules.php`

- [ ] **Step 1: Tạo migration seed**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class SeedOrderCheckStructuralRules extends Migration
{
    public function up()
    {
        $now = date('Y-m-d H:i:s');
        $rules = [
            ['code' => 'B_DISCHARGE_BEFORE_ADMISSION', 'rule_type' => 'DischargeBeforeAdmissionRule', 'name' => 'Ngày ra viện trước ngày vào viện', 'severity' => 'critical'],
            ['code' => 'B_ORDER_TIME_OUT_OF_STAY',     'rule_type' => 'OrderTimeOutOfStayRule',     'name' => 'Giờ y lệnh ngoài khoảng đợt điều trị', 'severity' => 'warning'],
            ['code' => 'B_EXECUTE_BEFORE_ORDER',       'rule_type' => 'ExecuteBeforeOrderRule',     'name' => 'Giờ thực hiện trước giờ y lệnh', 'severity' => 'warning'],
            ['code' => 'B_DOCTOR_NO_PRACTICE_CERT',    'rule_type' => 'DoctorPracticeCertRule',     'name' => 'Bác sĩ thiếu chứng chỉ hành nghề', 'severity' => 'critical'],
        ];

        foreach ($rules as $r) {
            $exists = DB::table('order_check_rules')->where('code', $r['code'])->exists();
            if (!$exists) {
                DB::table('order_check_rules')->insert([
                    'code' => $r['code'],
                    'family' => 'B',
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
        DB::table('order_check_rules')->whereIn('code', [
            'B_DISCHARGE_BEFORE_ADMISSION',
            'B_ORDER_TIME_OUT_OF_STAY',
            'B_EXECUTE_BEFORE_ORDER',
            'B_DOCTOR_NO_PRACTICE_CERT',
        ])->delete();
    }
}
```

- [ ] **Step 2: Chạy migrate, kiểm tra**

Run: `php artisan migrate`
Expected: `Migrated: 2026_06_30_120004_seed_order_check_structural_rules`

Run: `php artisan tinker --execute="echo App\Models\OrderCheck\OrderCheckRule::where('family','B')->count();"`
Expected: in ra `4`

- [ ] **Step 3: Commit**

```bash
git add database/migrations/2026_06_30_120004_seed_order_check_structural_rules.php
git commit -m "feat(order-check): seed 4 rule Ho B mac dinh"
```

---

## Task 12: HisOrderSource — đọc HIS, dựng OrderContext

**Files:**
- Create: `app/Services/OrderCheck/HisOrderSource.php`

> Phần này gắn DB Oracle nên kiểm thử bằng artisan tinker với dữ liệu HIS thật (không unit test query). Logic dựng context tách thành method `buildContext()` thuần để engine/test dùng lại.

- [ ] **Step 1: Tạo HisOrderSource**

```php
<?php

namespace App\Services\OrderCheck;

use Illuminate\Support\Facades\DB;
use App\Services\OrderCheck\Support\OrderContext;
use App\Services\OrderCheck\Support\OrderService;

class HisOrderSource
{
    protected $conn;
    protected $excludeTreatmentTypeIds;

    public function __construct()
    {
        $this->conn = config('order_check.his_connection');
        $ex = config('order_check.exclude_treatment_type_ids');
        $this->excludeTreatmentTypeIds = $ex === '' ? [] : explode(',', $ex);
    }

    /**
     * Lấy lô phiếu chỉ định mới theo watermark (create_time, id).
     * @return \Illuminate\Support\Collection các bản ghi service_req + in/out time + practice scope
     */
    public function fetchServiceRequests($lastCreateTime, $lastId, $limit)
    {
        $q = DB::connection($this->conn)
            ->table('his_service_req as sr')
            ->leftJoin('his_treatment as t', 'sr.treatment_id', '=', 't.id')
            ->leftJoin('his_employee as e', 'sr.request_loginname', '=', 'e.loginname')
            ->where('sr.is_delete', 0)
            ->where(function ($w) use ($lastCreateTime, $lastId) {
                $w->where('sr.create_time', '>', $lastCreateTime)
                  ->orWhere(function ($w2) use ($lastCreateTime, $lastId) {
                      $w2->where('sr.create_time', '=', $lastCreateTime)
                         ->where('sr.id', '>', $lastId);
                  });
            })
            ->orderBy('sr.create_time')
            ->orderBy('sr.id')
            ->limit($limit)
            ->selectRaw('sr.id, sr.service_req_code, sr.treatment_id, sr.intruction_time,
                sr.request_department_id, sr.request_loginname, sr.request_username,
                sr.icd_code, sr.icd_name, sr.create_time,
                sr.tdl_treatment_code, sr.tdl_patient_code, sr.tdl_patient_name,
                t.in_time as in_time, t.out_time as out_time,
                e.practice_scope_decision as practice_scope_decision');

        if (!empty($this->excludeTreatmentTypeIds)) {
            $q->whereNotIn('t.tdl_treatment_type_id', $this->excludeTreatmentTypeIds);
        }

        return $q->get();
    }

    /**
     * Lấy chi tiết dịch vụ (sere_serv) theo danh sách service_req_id, gom theo service_req_id.
     * @return array map service_req_id => OrderService[]
     */
    public function fetchServicesByReqIds(array $reqIds)
    {
        if (empty($reqIds)) {
            return [];
        }
        $rows = DB::connection($this->conn)
            ->table('his_sere_serv')
            ->where('is_delete', 0)
            ->whereIn('service_req_id', $reqIds)
            ->selectRaw('id, service_req_id, tdl_service_code, tdl_service_name, execute_time, tdl_intruction_time')
            ->get();

        $map = [];
        foreach ($rows as $r) {
            $s = new OrderService();
            $s->sereServId = (int) $r->id;
            $s->serviceCode = $r->tdl_service_code;
            $s->serviceName = $r->tdl_service_name;
            $s->executeTime = (int) $r->execute_time;
            $s->tdlIntructionTime = (int) $r->tdl_intruction_time;
            $map[(int) $r->service_req_id][] = $s;
        }
        return $map;
    }

    /**
     * Dựng OrderContext từ 1 bản ghi service_req + mảng dịch vụ (thuần, test được).
     */
    public function buildContext($row, array $services = [])
    {
        $c = new OrderContext();
        $c->serviceReqId = (int) $row->id;
        $c->serviceReqCode = $row->service_req_code;
        $c->treatmentId = (int) $row->treatment_id;
        $c->treatmentCode = $row->tdl_treatment_code;
        $c->patientCode = $row->tdl_patient_code;
        $c->patientName = $row->tdl_patient_name;
        $c->departmentId = $row->request_department_id !== null ? (int) $row->request_department_id : null;
        $c->doctorLoginname = $row->request_loginname;
        $c->doctorUsername = $row->request_username;
        $c->doctorPracticeScope = $row->practice_scope_decision;
        $c->intructionTime = (int) $row->intruction_time;
        $c->inTime = (int) $row->in_time;
        $c->outTime = (int) $row->out_time;
        $c->icdCode = $row->icd_code;
        $c->services = $services;
        return $c;
    }
}
```

- [ ] **Step 2: Xác minh đọc HIS thật (lấy 3 phiếu gần nhất)**

Run:
```bash
php artisan tinker --execute="\$s=app(App\Services\OrderCheck\HisOrderSource::class); \$rows=\$s->fetchServiceRequests(0,0,3); echo \$rows->count().PHP_EOL; foreach(\$rows as \$r){ echo \$r->id.' tr='.\$r->treatment_id.' intr='.\$r->intruction_time.' in='.\$r->in_time.' out='.\$r->out_time.PHP_EOL; }"
```
Expected: in ra `3` và 3 dòng có `intruction_time`, `in_time` dạng số 14 chữ số. (Nếu lỗi tên cột/bảng → sửa theo thông báo trước khi đi tiếp.)

- [ ] **Step 3: Commit**

```bash
git add app/Services/OrderCheck/HisOrderSource.php
git commit -m "feat(order-check): HisOrderSource doc HIS dung OrderContext"
```

---

## Task 13: OrderCheckEngine — điều phối + ghi violation/log idempotent

**Files:**
- Create: `app/Services/OrderCheck/OrderCheckEngine.php`

- [ ] **Step 1: Tạo engine**

```php
<?php

namespace App\Services\OrderCheck;

use App\Models\OrderCheck\OrderCheckWatermark;
use App\Models\OrderCheck\OrderCheckRule;
use App\Models\OrderCheck\OrderCheckViolation;
use App\Models\OrderCheck\OrderCheckRuleLog;
use App\Services\OrderCheck\RuleHandlers\StructuralRuleRegistry;
use App\Services\OrderCheck\Support\OrderContext;
use App\Services\OrderCheck\Support\Violation;

class OrderCheckEngine
{
    const SOURCE_KEY = 'his_service_req';

    protected $source;

    public function __construct(HisOrderSource $source)
    {
        $this->source = $source;
    }

    /**
     * Chạy 1 lượt quét. Trả về ['scanned'=>int,'violations'=>int].
     */
    public function run($limit = null)
    {
        $limit = $limit ?: (int) config('order_check.batch_size');

        $log = OrderCheckRuleLog::create([
            'source_key' => self::SOURCE_KEY,
            'started_at' => now(),
            'status' => 'running',
        ]);

        try {
            $wm = OrderCheckWatermark::firstOrCreate(
                ['source_key' => self::SOURCE_KEY],
                ['last_create_time' => 0, 'last_modify_time' => 0, 'last_id' => 0]
            );

            // Bản đồ code => rule active; chỉ chạy handler có rule active.
            $rulesByCode = OrderCheckRule::where('is_active', true)
                ->get()->keyBy('code');

            $handlers = StructuralRuleRegistry::handlers();

            $rows = $this->source->fetchServiceRequests($wm->last_create_time, $wm->last_id, $limit);
            $scanned = $rows->count();
            $violationCount = 0;

            if ($scanned > 0) {
                $reqIds = $rows->pluck('id')->map(function ($v) { return (int) $v; })->all();
                $servicesMap = $this->source->fetchServicesByReqIds($reqIds);

                $maxCreate = $wm->last_create_time;
                $maxId = $wm->last_id;

                foreach ($rows as $row) {
                    $ctx = $this->source->buildContext($row, isset($servicesMap[(int) $row->id]) ? $servicesMap[(int) $row->id] : []);

                    foreach ($handlers as $handler) {
                        if (!isset($rulesByCode[$handler->code()])) {
                            continue; // rule bị tắt hoặc chưa seed
                        }
                        $rule = $rulesByCode[$handler->code()];
                        foreach ($handler->check($ctx) as $vio) {
                            if ($this->persist($vio, $ctx, $rule)) {
                                $violationCount++;
                            }
                        }
                    }

                    if ((int) $row->create_time > $maxCreate || ((int) $row->create_time == $maxCreate && (int) $row->id > $maxId)) {
                        $maxCreate = (int) $row->create_time;
                        $maxId = (int) $row->id;
                    }
                }

                $wm->last_create_time = $maxCreate;
                $wm->last_id = $maxId;
                $wm->last_run_at = now();
                $wm->save();
            }

            $log->update([
                'finished_at' => now(),
                'scanned_count' => $scanned,
                'violation_count' => $violationCount,
                'status' => 'success',
            ]);

            return ['scanned' => $scanned, 'violations' => $violationCount];
        } catch (\Exception $e) {
            $log->update([
                'finished_at' => now(),
                'status' => 'error',
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Ghi 1 violation idempotent theo dedup_key. Trả true nếu tạo mới.
     */
    protected function persist(Violation $vio, OrderContext $ctx, OrderCheckRule $rule)
    {
        $dedup = $vio->dedupKey();
        $row = OrderCheckViolation::where('dedup_key', $dedup)->first();

        if ($row && in_array($row->status, ['processed', 'false_positive'])) {
            return false; // tôn trọng kết quả đã xử lý, không hồi sinh
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

- [ ] **Step 2: Xác minh engine chạy thật (1 lô nhỏ)**

Run:
```bash
php artisan tinker --execute="\$r=app(App\Services\OrderCheck\OrderCheckEngine::class)->run(50); var_export(\$r);"
```
Expected: in ra mảng kiểu `array ('scanned' => 50, 'violations' => N,)` không ném exception. Kiểm tra log:

Run: `php artisan tinker --execute="echo App\Models\OrderCheck\OrderCheckRuleLog::latest('id')->first()->status;"`
Expected: in ra `success`

- [ ] **Step 3: Xác minh idempotent (chạy lại không nhân đôi)**

Run:
```bash
php artisan tinker --execute="\$before=App\Models\OrderCheck\OrderCheckViolation::count(); app(App\Services\OrderCheck\OrderCheckEngine::class)->run(50); echo 'before='.\$before.' after='.App\Models\OrderCheck\OrderCheckViolation::count();"
```
Expected: lô tiếp theo (watermark đã tiến) — số violation chỉ tăng do bản ghi MỚI, không tạo trùng `dedup_key` (không có lỗi unique constraint).

- [ ] **Step 4: Commit**

```bash
git add app/Services/OrderCheck/OrderCheckEngine.php
git commit -m "feat(order-check): OrderCheckEngine dieu phoi + ghi violation idempotent"
```

---

## Task 14: Command kiemtraylenh:scan

**Files:**
- Create: `app/Console/Commands/HISProKiemTraYLenh.php`

- [ ] **Step 1: Tạo command**

```php
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\OrderCheck\OrderCheckEngine;

class HISProKiemTraYLenh extends Command
{
    protected $signature = 'kiemtraylenh:scan {--limit= : So phieu toi da moi lan quet}';

    protected $description = 'Quet y lenh moi tu HIS va kiem tra sai sot (Ho luat B)';

    public function handle(OrderCheckEngine $engine)
    {
        $limit = $this->option('limit') ? (int) $this->option('limit') : null;

        $start = microtime(true);
        $result = $engine->run($limit);
        $sec = round(microtime(true) - $start, 2);

        $this->info(sprintf(
            'Quet xong: %d phieu, %d vi pham moi, %ss',
            $result['scanned'], $result['violations'], $sec
        ));

        return 0;
    }
}
```

- [ ] **Step 2: Xác minh command hiển thị trong danh sách**

Run: `php artisan list | grep kiemtraylenh`
Expected: in ra dòng `kiemtraylenh:scan  Quet y lenh moi tu HIS ...`

- [ ] **Step 3: Chạy thử command**

Run: `php artisan kiemtraylenh:scan --limit=100`
Expected: in ra `Quet xong: 100 phieu, N vi pham moi, X.XXs` (không lỗi)

- [ ] **Step 4: Commit**

```bash
git add app/Console/Commands/HISProKiemTraYLenh.php
git commit -m "feat(order-check): command kiemtraylenh:scan"
```

---

## Task 15: Chạy toàn bộ test + tài liệu vận hành

**Files:**
- Modify: `readme.md` (thêm mục module + cách lập lịch)

- [ ] **Step 1: Chạy toàn bộ suite Unit OrderCheck**

Run: `vendor/bin/phpunit tests/Unit/OrderCheck`
Expected: PASS toàn bộ (ViolationTest 2 + StructuralRulesTest 12 = 14 tests, OK)

- [ ] **Step 2: Thêm mục readme (đầu file, theo định dạng nhật ký hiện có)**

Thêm khối sau vào đầu `readme.md` (trên dòng `# 23/06/2026`):

```markdown
# 30/06/2026

- Bổ sung module Kiểm tra sai sót y lệnh (giai đoạn 1): quét incremental phiếu chỉ định từ HIS (HIS_SERVICE_REQ) theo watermark, chạy 4 quy tắc hợp lệ cấu trúc/thời gian & hành nghề (ngày ra<vào, giờ y lệnh ngoài đợt, giờ thực hiện trước y lệnh, BS thiếu chứng chỉ), lưu vi phạm vào order_check_violations. Chạy bằng `php artisan kiemtraylenh:scan` (lập lịch mỗi 1–5 phút qua Windows Task/nssm).
```

- [ ] **Step 3: Commit**

```bash
git add readme.md
git commit -m "docs(order-check): ghi chu readme module kiem tra y lenh giai doan 1"
```

- [ ] **Step 4: (Vận hành) Đăng ký lập lịch**

Lập lịch chạy command mỗi 1–5 phút. Vì `App\Console\Kernel::schedule` đang trống và app chạy bằng nssm/Windows Task, tạo Windows Scheduled Task gọi:
```
php C:\Users\tracnn\qlbv\artisan kiemtraylenh:scan
```
(hoặc thêm `$schedule->command('kiemtraylenh:scan')->everyFiveMinutes();` vào Kernel nếu có chạy `schedule:run`). Bước này thực hiện thủ công trên máy chủ — ghi lại trong tài liệu vận hành.

---

## Self-Review (đã thực hiện khi viết plan)

**1. Spec coverage (Plan 1 scope = Họ B + nền tảng):**
- Quét incremental theo watermark → Task 12 (query create_time,id) + Task 13 (cập nhật watermark). ✅
- Không đụng HIS (chỉ SELECT) → Task 12 chỉ dùng SELECT. ✅
- Lưu MySQL qlbv → Task 2/3. ✅
- 4 luật Họ B (ngày ra<vào, giờ y lệnh ngoài đợt, giờ thực hiện trước y lệnh, BS thiếu CCHN) → Task 6–9. ✅
- Chỗ cập nhật riêng cho Họ B → Task 10 registry. ✅
- Idempotent (dedup_key) + tôn trọng trạng thái đã xử lý → Task 13 persist. ✅
- Log sức khỏe mỗi lần chạy → Task 2 (bảng) + Task 13 (ghi log). ✅
- Command + lập lịch → Task 14 + Task 15. ✅
- (Họ A, dashboard/notify/workflow/API: thuộc Plan 2/3 — ngoài phạm vi plan này, có ghi rõ.)

**2. Placeholder scan:** không còn TBD/TODO; mọi step có code/lệnh + output kỳ vọng cụ thể.

**3. Type consistency:** `RuleHandler::code()`/`check()` dùng nhất quán ở Task 5–10,13; `Violation` ctor (ruleCode, orderRefType, orderRefId, message, detail, subKey) + `dedupKey()` khớp Task 4 ↔ 6–9 ↔ 13; thuộc tính `OrderContext`/`OrderService` khớp giữa Task 4, 12, 13 và test; tên cột MySQL khớp migration (Task 2) ↔ model/engine (Task 3,13). ✅

**Lưu ý kiểm thử:** rule handler (logic thuần) test bằng PHPUnit Unit; `HisOrderSource`/`OrderCheckEngine`/command (gắn Oracle live) xác minh bằng `php artisan tinker`/chạy command thật theo từng step — phù hợp pattern dự án (các command HISPro không unit test query DB).
