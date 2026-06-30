# Order Check — Plan 6: Hạ tầng danh mục tham chiếu + luật giới tính/tuổi (dịch vụ)

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) hoặc superpowers:executing-plans. Steps dùng checkbox (`- [ ]`).

**Goal:** Vì HIS KHÔNG nhập sẵn giới hạn giới tính/tuổi (cột `HIS_SERVICE.GENDER_ID/AGE_FROM/AGE_TO` trống 100%), xây bảng tham chiếu **tự quản** trong `qlbv` (`order_check_ref_service_restriction`) + màn nhập danh mục + 2 luật: A_GENDER_MISMATCH (DV giới hạn giới tính bị chỉ định sai giới), A_AGE_OUT_OF_RANGE (DV ngoài ngưỡng tuổi). Luật bật nhưng chỉ phát hiện khi danh mục đã được nhập.

**Architecture:** Bảng tham chiếu keyed theo `service_code`. `ServiceRestrictionScanner` (nguồn `his_sere_serv`, watermark riêng `his_sere_serv_restriction`): lấy dòng DV mới → join `HIS_TREATMENT` lấy giới tính/ngày sinh BN → tra danh mục (nạp 1 lần vào bộ nhớ) theo `tdl_service_code` → áp 2 rule thuần (GenderRestrictionRule, AgeRestrictionRule). Quản lý danh mục qua trang CRUD KHTH.

**Tech Stack:** PHP 7 / Laravel 5.5, Eloquent (MySQL), oci8 (HISPro), Yajra Datatables, PHPUnit.

**Tham chiếu:** Plan 1–5 (đã commit). Engine/Scanner pattern Plan 2/5; UI pattern Plan 3 (`OrderCheckController`/`order-check.blade.php`); helper `app/Http/Controllers/app-helpers.php` (`dob()`).

## Bối cảnh có sẵn (KHÔNG tạo lại)
- Engine đa-scanner, `Scanner` interface, `ScannerRegistry::all($source)` (Plan 2/5 — hiện 4 scanner; Plan 6 thêm 1).
- `OrderCheckEngine` helper: `source()`, `activeRules()`, `getWatermark()`, `saveWatermark()`, `persist()`.
- `HisOrderSource` (Plan 5 đã có `fetchTreatmentInfo` — Plan 6 thêm method riêng cho restriction).
- `Violation`, `ViolationContext`.
- Route group `prefix 'khth/'` + `checkrole:administrator`; menu `config/adminlte.php`.

## Dữ liệu HIS đã xác minh
- `HIS_SERE_SERV`: `ID, CREATE_TIME, IS_DELETE, TDL_TREATMENT_ID, TDL_SERVICE_CODE`.
- `HIS_TREATMENT`: `TDL_PATIENT_GENDER_ID` (1=Nữ,2=Nam,3=KXĐ — theo `HIS_GENDER`), `TDL_PATIENT_DOB` (NUMBER `YYYYMMDDHHMMSS`, lấy 8 ký tự đầu = ngày sinh).
- `HIS_SERVICE.SERVICE_CODE` (mã DV, để bệnh viện đối chiếu khi nhập danh mục).

## Ngoài phạm vi (Plan 7)
- BHYT payability (DV/thuốc không được BHYT chi trả, vượt định mức): phức tạp, cần thiết kế riêng + tận dụng `App\Models\CheckBHYT\*`.
- Giới hạn giới tính/tuổi cho THUỐC (mở rộng tương tự khi cần).

## File Structure (Plan 6)
**Tạo mới:**
- `database/migrations/2026_06_30_160000_create_order_check_ref_service_restriction_table.php`
- `app/Models/OrderCheck/OrderCheckRefServiceRestriction.php`
- `app/Services/OrderCheck/RuleHandlers/Clinical/GenderRestrictionRule.php`
- `app/Services/OrderCheck/RuleHandlers/Clinical/AgeRestrictionRule.php`
- `app/Services/OrderCheck/Scanners/ServiceRestrictionScanner.php`
- `database/migrations/2026_06_30_160001_seed_order_check_rules_gender_age.php`
- `database/migrations/2026_06_30_160002_init_restriction_watermark_now.php`
- `app/Http/Controllers/KHTH/OrderCheckRefController.php`
- `resources/views/khth/order-check-ref.blade.php`
- `tests/Unit/OrderCheck/GenderRestrictionRuleTest.php`
- `tests/Unit/OrderCheck/AgeRestrictionRuleTest.php`
**Sửa:**
- `app/Services/OrderCheck/HisOrderSource.php` (thêm fetch cho restriction)
- `app/Services/OrderCheck/Scanners/ScannerRegistry.php`
- `routes/web.php`, `config/adminlte.php`, `readme.md`

---

## Task 1: Bảng tham chiếu + model

**Files:**
- Create: `database/migrations/2026_06_30_160000_create_order_check_ref_service_restriction_table.php`
- Create: `app/Models/OrderCheck/OrderCheckRefServiceRestriction.php`

- [ ] **Step 1: Migration**

```php
<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOrderCheckRefServiceRestrictionTable extends Migration
{
    public function up()
    {
        Schema::create('order_check_ref_service_restriction', function (Blueprint $table) {
            $table->increments('id');
            $table->string('service_code', 50)->unique();
            $table->string('service_name', 255)->nullable();
            $table->unsignedTinyInteger('required_gender_id')->nullable(); // 1=Nu,2=Nam; null=khong gioi han
            $table->unsignedSmallInteger('age_from')->nullable();          // tuoi nho nhat (nam)
            $table->unsignedSmallInteger('age_to')->nullable();            // tuoi lon nhat (nam)
            $table->string('note', 255)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('order_check_ref_service_restriction');
    }
}
```

- [ ] **Step 2: Model**

```php
<?php

namespace App\Models\OrderCheck;

use Illuminate\Database\Eloquent\Model;

class OrderCheckRefServiceRestriction extends Model
{
    protected $table = 'order_check_ref_service_restriction';
    protected $guarded = [];
    protected $casts = ['is_active' => 'boolean'];
}
```

- [ ] **Step 3: Migrate + verify**

Run: `php artisan migrate`
Expected: `Migrated: 2026_06_30_160000_create_order_check_ref_service_restriction_table`

Run: `echo 'echo Schema::hasTable("order_check_ref_service_restriction") ? "ok":"no";' | php artisan tinker`
Expected: `ok` (nếu tinker-pipe không in, bỏ qua — đã có dòng Migrated).

- [ ] **Step 4: Commit** (bỏ qua nếu được yêu cầu)

```bash
git add database/migrations/2026_06_30_160000_create_order_check_ref_service_restriction_table.php app/Models/OrderCheck/OrderCheckRefServiceRestriction.php
git commit -m "feat(order-check): bang tham chieu order_check_ref_service_restriction"
```

---

## Task 2: GenderRestrictionRule + AgeRestrictionRule + test

**Files:**
- Create: `app/Services/OrderCheck/RuleHandlers/Clinical/GenderRestrictionRule.php`
- Create: `app/Services/OrderCheck/RuleHandlers/Clinical/AgeRestrictionRule.php`
- Test: `tests/Unit/OrderCheck/GenderRestrictionRuleTest.php`
- Test: `tests/Unit/OrderCheck/AgeRestrictionRuleTest.php`

- [ ] **Step 1: Test GenderRestrictionRule (FAIL)**

`tests/Unit/OrderCheck/GenderRestrictionRuleTest.php`:
```php
<?php

namespace Tests\Unit\OrderCheck;

use Tests\TestCase;
use App\Services\OrderCheck\RuleHandlers\Clinical\GenderRestrictionRule;

class GenderRestrictionRuleTest extends TestCase
{
    public function test_lech_gioi_la_vi_pham()
    {
        $r = new GenderRestrictionRule();
        $this->assertTrue($r->mismatch(2, 1));  // BN nam (2), DV chi cho nu (1)
        $this->assertTrue($r->mismatch(1, 2));
    }

    public function test_dung_gioi_khong_vi_pham()
    {
        $r = new GenderRestrictionRule();
        $this->assertFalse($r->mismatch(1, 1));
        $this->assertFalse($r->mismatch(2, 2));
    }

    public function test_khong_gioi_han_hoac_khong_xac_dinh_thi_bo_qua()
    {
        $r = new GenderRestrictionRule();
        $this->assertFalse($r->mismatch(2, null)); // DV khong gioi han
        $this->assertFalse($r->mismatch(2, 3));    // rang buoc KXD
        $this->assertFalse($r->mismatch(3, 1));    // BN KXD
    }
}
```

Run: `vendor/bin/phpunit --filter GenderRestrictionRuleTest` → FAIL.

- [ ] **Step 2: Test AgeRestrictionRule (FAIL)**

`tests/Unit/OrderCheck/AgeRestrictionRuleTest.php`:
```php
<?php

namespace Tests\Unit\OrderCheck;

use Tests\TestCase;
use App\Services\OrderCheck\RuleHandlers\Clinical\AgeRestrictionRule;

class AgeRestrictionRuleTest extends TestCase
{
    public function test_duoi_tuoi_toi_thieu_la_vi_pham()
    {
        $r = new AgeRestrictionRule();
        // sinh 2020-01-01, mốc 2026-06-30 => 6 tuoi; age_from=16 => vi pham
        $this->assertTrue($r->outOfRange('20200101000000', 16, null, '20260630'));
    }

    public function test_tren_tuoi_toi_da_la_vi_pham()
    {
        $r = new AgeRestrictionRule();
        // sinh 1950 => 76 tuoi; age_to=6 => vi pham
        $this->assertTrue($r->outOfRange('19500101000000', null, 6, '20260630'));
    }

    public function test_trong_khoang_khong_vi_pham()
    {
        $r = new AgeRestrictionRule();
        $this->assertFalse($r->outOfRange('20000101000000', 16, 60, '20260630')); // 26 tuoi
    }

    public function test_thieu_ngay_sinh_thi_bo_qua()
    {
        $r = new AgeRestrictionRule();
        $this->assertFalse($r->outOfRange('00000000000000', 16, null, '20260630'));
        $this->assertFalse($r->outOfRange(null, 16, null, '20260630'));
    }
}
```

Run: `vendor/bin/phpunit --filter AgeRestrictionRuleTest` → FAIL.

- [ ] **Step 3: Cài đặt GenderRestrictionRule**

```php
<?php

namespace App\Services\OrderCheck\RuleHandlers\Clinical;

class GenderRestrictionRule
{
    public function code()
    {
        return 'A_GENDER_MISMATCH';
    }

    /**
     * True nếu giới tính BN khác giới tính DV yêu cầu (chỉ xét 1=Nữ, 2=Nam).
     */
    public function mismatch($patientGenderId, $requiredGenderId)
    {
        $p = (int) $patientGenderId;
        $r = (int) $requiredGenderId;
        if ($r !== 1 && $r !== 2) {
            return false; // DV không giới hạn / KXĐ
        }
        if ($p !== 1 && $p !== 2) {
            return false; // BN không xác định giới → không gắn cờ
        }
        return $p !== $r;
    }
}
```

- [ ] **Step 4: Cài đặt AgeRestrictionRule**

```php
<?php

namespace App\Services\OrderCheck\RuleHandlers\Clinical;

class AgeRestrictionRule
{
    public function code()
    {
        return 'A_AGE_OUT_OF_RANGE';
    }

    /**
     * True nếu tuổi BN (năm tròn) ngoài [ageFrom, ageTo]. Bỏ qua khi thiếu ngày sinh.
     * @param string|int $dob YYYYMMDD... ; @param int|null $ageFrom ; @param int|null $ageTo ; @param string $refYmd YYYYMMDD
     */
    public function outOfRange($dob, $ageFrom, $ageTo, $refYmd)
    {
        $age = $this->ageInYears($dob, $refYmd);
        if ($age === null) {
            return false;
        }
        if ($ageFrom !== null && $ageFrom !== '' && $age < (int) $ageFrom) {
            return true;
        }
        if ($ageTo !== null && $ageTo !== '' && $age > (int) $ageTo) {
            return true;
        }
        return false;
    }

    public function ageInYears($dob, $refYmd)
    {
        $dob = (string) $dob;
        $by = (int) substr($dob, 0, 4);
        if ($by <= 0) {
            return null; // không rõ năm sinh
        }
        $bm = (int) substr($dob, 4, 2);
        $bd = (int) substr($dob, 6, 2);

        $refYmd = (string) $refYmd;
        $ry = (int) substr($refYmd, 0, 4);
        $rm = (int) substr($refYmd, 4, 2);
        $rd = (int) substr($refYmd, 6, 2);

        $age = $ry - $by;
        if ($rm < $bm || ($rm === $bm && $rd < $bd)) {
            $age--;
        }
        return $age < 0 ? null : $age;
    }
}
```

- [ ] **Step 5: Chạy 2 test → PASS**

Run: `vendor/bin/phpunit --filter GenderRestrictionRuleTest` → PASS (3 tests)
Run: `vendor/bin/phpunit --filter AgeRestrictionRuleTest` → PASS (4 tests)

- [ ] **Step 6: Commit** (bỏ qua nếu được yêu cầu)

```bash
git add app/Services/OrderCheck/RuleHandlers/Clinical/GenderRestrictionRule.php app/Services/OrderCheck/RuleHandlers/Clinical/AgeRestrictionRule.php tests/Unit/OrderCheck/GenderRestrictionRuleTest.php tests/Unit/OrderCheck/AgeRestrictionRuleTest.php
git commit -m "feat(order-check): rule gender/age restriction (thuan) + test"
```

---

## Task 3: HisOrderSource — fetch DV mới kèm giới tính/ngày sinh BN

**Files:**
- Modify: `app/Services/OrderCheck/HisOrderSource.php`

- [ ] **Step 1: Thêm method (sau `fetchTreatmentInfo`)**

```php
    /**
     * Lô dòng dịch vụ mới kèm mã DV + giới tính/ngày sinh BN (join treatment) — cho luật giới tính/tuổi.
     */
    public function fetchSereServWithPatient($lastCreateTime, $lastId, $limit)
    {
        return DB::connection($this->conn)
            ->table('his_sere_serv as ss')
            ->leftJoin('his_treatment as t', 'ss.tdl_treatment_id', '=', 't.id')
            ->where('ss.is_delete', 0)
            ->where(function ($w) use ($lastCreateTime, $lastId) {
                $w->where('ss.create_time', '>', $lastCreateTime)
                  ->orWhere(function ($w2) use ($lastCreateTime, $lastId) {
                      $w2->where('ss.create_time', '=', $lastCreateTime)->where('ss.id', '>', $lastId);
                  });
            })
            ->orderBy('ss.create_time')->orderBy('ss.id')->limit($limit)
            ->selectRaw('ss.id, ss.create_time, ss.tdl_treatment_id, ss.tdl_service_code, ss.tdl_service_name,
                t.treatment_code, t.tdl_patient_code, t.tdl_patient_name, t.last_department_id,
                t.tdl_patient_gender_id, t.tdl_patient_dob')
            ->get();
    }
```

- [ ] **Step 2: Verify**

Run: `php -l app/Services/OrderCheck/HisOrderSource.php` → No syntax errors.

File tạm `rchk.php` (`php rchk.php`, xóa sau):
```php
<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$rows = app(App\Services\OrderCheck\HisOrderSource::class)->fetchSereServWithPatient(0,0,2);
echo $rows->count().PHP_EOL;
foreach ($rows as $r) { echo $r->id.' code='.$r->tdl_service_code.' gender='.$r->tdl_patient_gender_id.' dob='.$r->tdl_patient_dob.PHP_EOL; }
```
Expected: in 2 dòng có `code`, `gender` (1/2/3), `dob` (14 số). Lỗi cột → BLOCKED.

- [ ] **Step 3: Commit** (bỏ qua nếu được yêu cầu)

```bash
git add app/Services/OrderCheck/HisOrderSource.php
git commit -m "feat(order-check): HisOrderSource fetchSereServWithPatient (gioi tinh/dob)"
```

---

## Task 4: ServiceRestrictionScanner

**Files:**
- Create: `app/Services/OrderCheck/Scanners/ServiceRestrictionScanner.php`

- [ ] **Step 1: Tạo scanner**

```php
<?php

namespace App\Services\OrderCheck\Scanners;

use App\Services\OrderCheck\Contracts\Scanner;
use App\Services\OrderCheck\OrderCheckEngine;
use App\Services\OrderCheck\Support\Violation;
use App\Services\OrderCheck\Support\ViolationContext;
use App\Services\OrderCheck\RuleHandlers\Clinical\GenderRestrictionRule;
use App\Services\OrderCheck\RuleHandlers\Clinical\AgeRestrictionRule;
use App\Models\OrderCheck\OrderCheckRefServiceRestriction;

class ServiceRestrictionScanner implements Scanner
{
    const SOURCE_KEY = 'his_sere_serv_restriction';
    const RULE_GENDER = 'A_GENDER_MISMATCH';
    const RULE_AGE = 'A_AGE_OUT_OF_RANGE';

    public function sourceKey()
    {
        return self::SOURCE_KEY;
    }

    public function scan(OrderCheckEngine $engine, $limit)
    {
        $rules = $engine->activeRules();
        $genderActive = isset($rules[self::RULE_GENDER]);
        $ageActive = isset($rules[self::RULE_AGE]);

        $source = $engine->source();
        $wm = $engine->getWatermark(self::SOURCE_KEY);
        $rows = $source->fetchSereServWithPatient($wm->last_create_time, $wm->last_id, $limit);
        $scanned = $rows->count();
        $violations = 0;

        if ($scanned > 0) {
            // Nạp danh mục giới hạn 1 lần, key theo service_code.
            $catalog = OrderCheckRefServiceRestriction::where('is_active', true)->get()->keyBy('service_code');

            $maxCreate = $wm->last_create_time;
            $maxId = $wm->last_id;

            $genderRule = new GenderRestrictionRule();
            $ageRule = new AgeRestrictionRule();
            $refYmd = date('Ymd');

            foreach ($rows as $row) {
                if (($genderActive || $ageActive) && isset($catalog[$row->tdl_service_code])) {
                    $ref = $catalog[$row->tdl_service_code];
                    $vctx = $this->context($row);

                    if ($genderActive && $genderRule->mismatch($row->tdl_patient_gender_id, $ref->required_gender_id)) {
                        $vio = new Violation(
                            self::RULE_GENDER, 'sere_serv', (int) $row->id,
                            'Chỉ định DV giới hạn giới tính sai: ' . $row->tdl_service_code . ' - ' . $row->tdl_service_name,
                            ['service_code' => $row->tdl_service_code, 'required_gender_id' => (int) $ref->required_gender_id, 'patient_gender_id' => (int) $row->tdl_patient_gender_id]
                        );
                        if ($engine->persist($vio, $vctx, $rules[self::RULE_GENDER])) {
                            $violations++;
                        }
                    }

                    if ($ageActive && $ageRule->outOfRange($row->tdl_patient_dob, $ref->age_from, $ref->age_to, $refYmd)) {
                        $age = $ageRule->ageInYears($row->tdl_patient_dob, $refYmd);
                        $vio = new Violation(
                            self::RULE_AGE, 'sere_serv', (int) $row->id,
                            'Chỉ định DV ngoài ngưỡng tuổi: ' . $row->tdl_service_code . ' (BN ' . $age . ' tuổi, cho phép ' . $ref->age_from . '-' . $ref->age_to . ')',
                            ['service_code' => $row->tdl_service_code, 'age' => $age, 'age_from' => $ref->age_from, 'age_to' => $ref->age_to]
                        );
                        if ($engine->persist($vio, $vctx, $rules[self::RULE_AGE])) {
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

    private function context($row)
    {
        return ViolationContext::make([
            'treatment_id' => (int) $row->tdl_treatment_id,
            'treatment_code' => $row->treatment_code,
            'patient_code' => $row->tdl_patient_code,
            'patient_name' => $row->tdl_patient_name,
            'department_id' => $row->last_department_id !== null ? (int) $row->last_department_id : null,
        ]);
    }
}
```

- [ ] **Step 2: Verify**

Run: `php -l app/Services/OrderCheck/Scanners/ServiceRestrictionScanner.php` → No syntax errors.

- [ ] **Step 3: Commit** (bỏ qua nếu được yêu cầu)

```bash
git add app/Services/OrderCheck/Scanners/ServiceRestrictionScanner.php
git commit -m "feat(order-check): ServiceRestrictionScanner (gioi tinh/tuoi theo danh muc)"
```

---

## Task 5: Đăng ký scanner + seed rule + init watermark

**Files:**
- Modify: `app/Services/OrderCheck/Scanners/ScannerRegistry.php`
- Create: `database/migrations/2026_06_30_160001_seed_order_check_rules_gender_age.php`
- Create: `database/migrations/2026_06_30_160002_init_restriction_watermark_now.php`

- [ ] **Step 1: Thêm scanner vào ScannerRegistry**

Mở `ScannerRegistry.php`, thêm `new ServiceRestrictionScanner(),` vào cuối mảng trả về của `all()` (sau `new MedicineScanner(),`). (Giữ nguyên các scanner cũ.)

- [ ] **Step 2: Migration seed rule**

`database/migrations/2026_06_30_160001_seed_order_check_rules_gender_age.php`:
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class SeedOrderCheckRulesGenderAge extends Migration
{
    public function up()
    {
        $now = date('Y-m-d H:i:s');
        $rules = [
            ['code' => 'A_GENDER_MISMATCH', 'rule_type' => 'GenderRestrictionRule', 'name' => 'Chỉ định DV sai giới tính (theo danh mục)', 'severity' => 'warning'],
            ['code' => 'A_AGE_OUT_OF_RANGE', 'rule_type' => 'AgeRestrictionRule', 'name' => 'Chỉ định DV ngoài ngưỡng tuổi (theo danh mục)', 'severity' => 'warning'],
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
        DB::table('order_check_rules')->whereIn('code', ['A_GENDER_MISMATCH', 'A_AGE_OUT_OF_RANGE'])->delete();
    }
}
```

- [ ] **Step 3: Migration init watermark**

`database/migrations/2026_06_30_160002_init_restriction_watermark_now.php`:
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class InitRestrictionWatermarkNow extends Migration
{
    public function up()
    {
        $nowNum = (int) date('YmdHis');
        $nowDt = date('Y-m-d H:i:s');
        DB::table('order_check_watermarks')->updateOrInsert(
            ['source_key' => 'his_sere_serv_restriction'],
            ['last_create_time' => $nowNum, 'last_modify_time' => $nowNum, 'last_id' => 0, 'last_run_at' => $nowDt, 'created_at' => $nowDt, 'updated_at' => $nowDt]
        );
    }

    public function down()
    {
        DB::table('order_check_watermarks')->where('source_key', 'his_sere_serv_restriction')->delete();
    }
}
```

- [ ] **Step 4: Migrate + verify**

Run: `php artisan migrate`
Expected: 2 dòng Migrated (seed rule + init watermark).

- [ ] **Step 5: Commit** (bỏ qua nếu được yêu cầu)

```bash
git add app/Services/OrderCheck/Scanners/ScannerRegistry.php database/migrations/2026_06_30_16000*_*.php
git commit -m "feat(order-check): dang ky ServiceRestrictionScanner + seed rule gender/age + watermark"
```

---

## Task 6: Màn nhập danh mục giới hạn (CRUD)

**Files:**
- Create: `app/Http/Controllers/KHTH/OrderCheckRefController.php`
- Create: `resources/views/khth/order-check-ref.blade.php`
- Modify: `routes/web.php`, `config/adminlte.php`

- [ ] **Step 1: Controller**

```php
<?php

namespace App\Http\Controllers\KHTH;

use App\Http\Controllers\Controller;
use App\Models\OrderCheck\OrderCheckRefServiceRestriction;
use Illuminate\Http\Request;
use Yajra\Datatables\Datatables;

class OrderCheckRefController extends Controller
{
    public function index()
    {
        return view('khth.order-check-ref');
    }

    public function fetch()
    {
        return Datatables::of(OrderCheckRefServiceRestriction::query()->orderBy('service_code'))
            ->addColumn('gender_text', function ($r) {
                $map = [1 => 'Nữ', 2 => 'Nam'];
                return isset($map[$r->required_gender_id]) ? $map[$r->required_gender_id] : '';
            })
            ->addColumn('age_text', function ($r) {
                if ($r->age_from === null && $r->age_to === null) return '';
                return ($r->age_from === null ? '' : $r->age_from) . ' - ' . ($r->age_to === null ? '' : $r->age_to);
            })
            ->addColumn('active_text', function ($r) {
                return $r->is_active ? '<span class="label label-success">Bật</span>' : '<span class="label label-default">Tắt</span>';
            })
            ->addColumn('actions', function ($r) {
                return '<button class="btn btn-xs btn-primary ref-edit" data-id="' . $r->id . '">Sửa</button> '
                    . '<button class="btn btn-xs btn-danger ref-del" data-id="' . $r->id . '">Xóa</button>';
            })
            ->rawColumns(['active_text', 'actions'])
            ->make(true);
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);
        OrderCheckRefServiceRestriction::create($data);
        return response()->json(['ok' => true]);
    }

    public function update(Request $request, $id)
    {
        $row = OrderCheckRefServiceRestriction::findOrFail($id);
        $row->update($this->validateData($request, $id));
        return response()->json(['ok' => true]);
    }

    public function destroy($id)
    {
        OrderCheckRefServiceRestriction::where('id', $id)->delete();
        return response()->json(['ok' => true]);
    }

    private function validateData(Request $request, $id = null)
    {
        $unique = 'unique:order_check_ref_service_restriction,service_code' . ($id ? (',' . $id) : '');
        $request->validate([
            'service_code' => 'required|string|max:50|' . $unique,
            'service_name' => 'nullable|string|max:255',
            'required_gender_id' => 'nullable|in:1,2',
            'age_from' => 'nullable|integer|min:0|max:150',
            'age_to' => 'nullable|integer|min:0|max:150',
            'note' => 'nullable|string|max:255',
        ]);
        return [
            'service_code' => $request->input('service_code'),
            'service_name' => $request->input('service_name'),
            'required_gender_id' => $request->input('required_gender_id') ?: null,
            'age_from' => $request->input('age_from') !== null && $request->input('age_from') !== '' ? (int) $request->input('age_from') : null,
            'age_to' => $request->input('age_to') !== null && $request->input('age_to') !== '' ? (int) $request->input('age_to') : null,
            'note' => $request->input('note'),
            'is_active' => $request->input('is_active', 1) ? 1 : 0,
        ];
    }
}
```

- [ ] **Step 2: Route (trong group `khth/` checkrole:administrator)**

Thêm vào `routes/web.php` (cạnh route order-check):
```php
        /* Danh muc gioi han DV (gioi tinh/tuoi) */
        Route::get('order-check-ref-index', 'KHTH\OrderCheckRefController@index')->name('khth.order-check-ref-index');
        Route::get('order-check-ref-index/fetch', 'KHTH\OrderCheckRefController@fetch')->name('khth.order-check-ref-fetch');
        Route::post('order-check-ref-index', 'KHTH\OrderCheckRefController@store')->name('khth.order-check-ref-store');
        Route::post('order-check-ref-index/{id}', 'KHTH\OrderCheckRefController@update')->name('khth.order-check-ref-update');
        Route::delete('order-check-ref-index/{id}', 'KHTH\OrderCheckRefController@destroy')->name('khth.order-check-ref-destroy');
```

- [ ] **Step 3: Menu (config/adminlte.php)**

Thêm sau mục `khth.order-check-index`:
```php
                [
                    'text'      => 'Danh mục giới hạn DV',
                    'icon'      => 'venus-mars',
                    'checkrole' => 'administrator',
                    'route'     => 'khth.order-check-ref-index',
                    'active'    => ['khth/order-check-ref-index*'],
                ],
```

- [ ] **Step 4: View**

`resources/views/khth/order-check-ref.blade.php`:
```blade
@extends('adminlte::page')
@section('title', 'Danh mục giới hạn dịch vụ')
@section('content_header')<h1>Danh mục giới hạn dịch vụ (giới tính/tuổi)</h1>@stop

@section('content')
<div class="box box-primary"><div class="box-body">
  <form id="ref-form" class="row">
    <input type="hidden" id="ref-id">
    <div class="col-md-2"><label>Mã DV *</label><input id="f-code" class="form-control" required></div>
    <div class="col-md-3"><label>Tên DV</label><input id="f-name" class="form-control"></div>
    <div class="col-md-2"><label>Giới tính</label>
      <select id="f-gender" class="form-control"><option value="">Không giới hạn</option><option value="1">Nữ</option><option value="2">Nam</option></select>
    </div>
    <div class="col-md-1"><label>Tuổi từ</label><input id="f-agefrom" type="number" class="form-control"></div>
    <div class="col-md-1"><label>Tuổi đến</label><input id="f-ageto" type="number" class="form-control"></div>
    <div class="col-md-2"><label>Ghi chú</label><input id="f-note" class="form-control"></div>
    <div class="col-md-1"><label>&nbsp;</label><br><button type="submit" class="btn btn-primary">Lưu</button></div>
  </form>
</div></div>

<div class="box"><div class="box-body table-responsive">
  <table id="ref-table" class="table table-bordered table-hover" width="100%">
    <thead><tr><th>Mã DV</th><th>Tên DV</th><th>Giới tính</th><th>Tuổi</th><th>Ghi chú</th><th>Trạng thái</th><th>Thao tác</th></tr></thead>
  </table>
</div></div>
@stop

@push('after-scripts')
<script>
var t = null;
function reset(){ $('#ref-id').val(''); $('#f-code').val('').prop('readonly',false); $('#f-name,#f-agefrom,#f-ageto,#f-note').val(''); $('#f-gender').val(''); }
$(function(){
  t = $('#ref-table').DataTable({
    processing:true, serverSide:true,
    ajax:"{{ route('khth.order-check-ref-fetch') }}",
    columns:[{data:'service_code'},{data:'service_name'},{data:'gender_text'},{data:'age_text'},{data:'note'},{data:'active_text'},{data:'actions',orderable:false,searchable:false}]
  });

  $('#ref-form').on('submit', function(e){
    e.preventDefault();
    var id=$('#ref-id').val();
    var url = id ? "{{ url('khth/order-check-ref-index') }}/"+id : "{{ route('khth.order-check-ref-store') }}";
    $.ajax({ url:url, method:'POST', data:{ _token:"{{ csrf_token() }}", service_code:$('#f-code').val(), service_name:$('#f-name').val(), required_gender_id:$('#f-gender').val(), age_from:$('#f-agefrom').val(), age_to:$('#f-ageto').val(), note:$('#f-note').val(), is_active:1 },
      success:function(){ reset(); t.ajax.reload(); },
      error:function(x){ alert(x.responseJSON ? JSON.stringify(x.responseJSON) : 'Lỗi'); }
    });
  });

  $(document).on('click','.ref-edit', function(){
    var row = t.row($(this).closest('tr')).data();
    $('#ref-id').val(row.id); $('#f-code').val(row.service_code).prop('readonly',true);
    $('#f-name').val(row.service_name); $('#f-gender').val(row.required_gender_id||''); $('#f-agefrom').val(row.age_from||''); $('#f-ageto').val(row.age_to||''); $('#f-note').val(row.note||'');
  });

  $(document).on('click','.ref-del', function(){
    if(!confirm('Xóa mục này?')) return;
    var id=$(this).data('id');
    $.ajax({ url:"{{ url('khth/order-check-ref-index') }}/"+id, method:'POST', data:{ _token:"{{ csrf_token() }}", _method:'DELETE' }, success:function(){ t.ajax.reload(); } });
  });
});
</script>
@endpush
```

- [ ] **Step 5: Verify route**

Run: `php -d memory_limit=-1 artisan route:list 2>&1 | grep order-check-ref`
Expected: 5 route `khth.order-check-ref-*`.

- [ ] **Step 6: Commit** (bỏ qua nếu được yêu cầu)

```bash
git add app/Http/Controllers/KHTH/OrderCheckRefController.php resources/views/khth/order-check-ref.blade.php routes/web.php config/adminlte.php
git commit -m "feat(order-check): man nhap danh muc gioi han DV (CRUD)"
```

---

## Task 7: Verify e2e + regression + readme

**Files:**
- Modify: `readme.md`

- [ ] **Step 1: Regression**

Run: `vendor/bin/phpunit tests/Unit/OrderCheck`
Expected: PASS toàn bộ (28 cũ + Gender 3 + Age 4 = 35 tests).

- [ ] **Step 2: Verify e2e (5 scanner)**

Run: `php artisan kiemtraylenh:scan --once --limit=20`
Expected: "Quet xong..." không exception.

File tạm `l2.php` (`php l2.php`, xóa):
```php
<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
foreach (App\Models\OrderCheck\OrderCheckRuleLog::orderBy('id','desc')->take(5)->get() as $l) {
    echo $l->source_key.' => '.$l->status.PHP_EOL;
}
```
Expected: thấy `his_sere_serv_restriction => success` cùng 4 nguồn cũ. (Danh mục trống → vio=0, đúng: chưa nhập danh mục.)

- [ ] **Step 3: Readme**

Chèn vào đầu `readme.md`:
```markdown
# 30/06/2026 (cập nhật 5)

- Module Kiểm tra sai sót y lệnh (giai đoạn 6): thêm danh mục tự quản "Giới hạn dịch vụ" (giới tính/tuổi) + màn nhập (KHTH) + 2 luật A_GENDER_MISMATCH, A_AGE_OUT_OF_RANGE đối chiếu chỉ định với giới tính/tuổi bệnh nhân. Luật chỉ phát hiện khi danh mục đã được nhập (HIS không có sẵn dữ liệu giới hạn).

```

- [ ] **Step 4: Commit** (bỏ qua nếu được yêu cầu)

```bash
git add readme.md
git commit -m "docs(order-check): readme giai doan 6 (danh muc gioi han + gender/age)"
```

---

## Lưu ý vận hành
- 2 luật gender/age **chỉ phát hiện khi đã nhập danh mục** `order_check_ref_service_restriction` (qua màn KHTH → Danh mục giới hạn DV). Ví dụ: thêm mã DV siêu âm thai với `required_gender_id=1` (Nữ); khám tuyến tiền liệt `required_gender_id=2` (Nam); DV nhi khoa `age_to=15`.
- Danh mục trống = không cảnh báo (không false positive).

## Self-Review (đã thực hiện khi viết plan)

**1. Spec coverage (Plan 6 = gender/tuổi qua danh mục tự quản):**
- Bảng tham chiếu tự quản → Task 1. ✅
- Luật gender + age (thuần, test) → Task 2. ✅
- Đọc HIS (DV mới + giới tính/dob BN) → Task 3. ✅
- Scanner đối chiếu danh mục → Task 4 + Task 5 (đăng ký/seed/watermark deploy-time). ✅
- Màn nhập danh mục (CRUD) → Task 6. ✅
- Idempotent (dedup theo sere_serv id) + bật/tắt data-driven + init watermark = deploy-time. ✅

**2. Ngoài phạm vi (nêu rõ):** BHYT payability → Plan 7; gender/age cho thuốc → sau.

**3. Type consistency:** `Scanner::scan` trả `['scanned','violations']`; `engine->persist(Violation,ViolationContext,$rule)`; `GenderRestrictionRule::mismatch(p,r)` và `AgeRestrictionRule::outOfRange(dob,from,to,refYmd)`/`ageInYears` khớp test (Task 2) ↔ scanner (Task 4). Watermark key `his_sere_serv_restriction` khớp scanner ↔ migration (Task 5). Model `OrderCheckRefServiceRestriction` cột khớp migration (Task 1) ↔ controller (Task 6) ↔ scanner catalog (Task 4). Route name `khth.order-check-ref-*` khớp web.php ↔ view ↔ menu (Task 6). ✅

**4. Lưu ý:** Watermark riêng `his_sere_serv_restriction` (khác `his_sere_serv` của A3) → 2 scanner cùng đọc bảng his_sere_serv nhưng theo dõi vị trí độc lập. CRUD UI cần xác minh trực quan thủ công (đăng nhập admin).
