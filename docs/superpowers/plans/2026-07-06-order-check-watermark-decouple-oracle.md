# Tách khởi tạo watermark order-check khỏi Oracle — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** `php artisan migrate` không còn cần Oracle-CLI; watermark order-check khởi tạo = MAX hiện tại tại runtime scanner (không backfill), chạy sạch ở mọi CSKCB.

**Architecture:** Migration `init_order_check_watermarks` thành no-op. Việc đặt mốc = MAX dời vào `OrderCheckEngine::getWatermark` (dùng cờ `wasRecentlyCreated` của `firstOrCreate`), lấy MAX qua `HisOrderSource::initialWatermark($sourceKey)`. Ánh xạ source_key→(bảng,cột,trường) tách thành hàm thuần `initTargetFor` để test không cần Oracle.

**Tech Stack:** Laravel 5.5, Oracle 12c (yajra/laravel-oci8) cho HISPro, MySQL/MariaDB cho bảng hệ thống, PHPUnit.

**Spec:** `docs/superpowers/specs/2026-07-06-order-check-watermark-decouple-oracle-design.md`

**Verification note:** Bộ test order-check hiện tại thuần logic (không đụng DB). Giữ phong cách đó: unit-test phần **thuần** (`initTargetFor`, nhánh key lạ của `initialWatermark`). Phần đọc Oracle + `getWatermark` (cần model/DB) kiểm bằng **smoke qua tinker** trên máy có Oracle.

---

### Task 1: `HisOrderSource::initTargetFor` + `initialWatermark` (TDD)

**Files:**
- Modify: `app/Services/OrderCheck/HisOrderSource.php`
- Test: `tests/Unit/OrderCheck/HisOrderSourceInitialWatermarkTest.php` (create)

- [ ] **Step 1: Viết test thất bại (phần thuần)**

Create `tests/Unit/OrderCheck/HisOrderSourceInitialWatermarkTest.php`:

```php
<?php

namespace Tests\Unit\OrderCheck;

use Tests\TestCase;
use App\Services\OrderCheck\HisOrderSource;

class HisOrderSourceInitialWatermarkTest extends TestCase
{
    private function source()
    {
        return new HisOrderSource();
    }

    public function test_map_moc_khoi_tao_dung_bang_cot_truong()
    {
        $s = $this->source();
        $this->assertSame(
            ['table' => 'his_service_req', 'column' => 'modify_time', 'field' => 'last_modify_time'],
            $s->initTargetFor('his_service_req')
        );
        $this->assertSame(
            ['table' => 'his_medicine_interactive', 'column' => 'id', 'field' => 'last_id'],
            $s->initTargetFor('his_medicine_interactive')
        );
        $this->assertSame(
            ['table' => 'his_exp_mest_medicine', 'column' => 'id', 'field' => 'last_id'],
            $s->initTargetFor('his_exp_mest_medicine')
        );
        $this->assertSame(
            ['table' => 'his_sere_serv', 'column' => 'id', 'field' => 'last_id'],
            $s->initTargetFor('his_sere_serv_restriction')
        );
    }

    public function test_key_la_tra_null_va_initialWatermark_tra_zeros_khong_cham_db()
    {
        $s = $this->source();
        $this->assertNull($s->initTargetFor('khong_ton_tai'));
        // key lạ => trả zeros theo đường tắt, KHÔNG query Oracle (test chạy được dù không có Oracle).
        $this->assertSame(
            ['last_create_time' => 0, 'last_modify_time' => 0, 'last_id' => 0],
            $s->initialWatermark('khong_ton_tai')
        );
    }
}
```

- [ ] **Step 2: Chạy test — xác nhận FAIL**

Run: `cd "C:\Users\tracnn\qlbv" && vendor/bin/phpunit tests/Unit/OrderCheck/HisOrderSourceInitialWatermarkTest.php`
Expected: FAIL — `Call to undefined method ...::initTargetFor()`.

- [ ] **Step 3: Thêm 2 method vào `HisOrderSource`**

Thêm 2 method sau vào trong class `HisOrderSource` (đặt sau constructor, trước các method fetch — đâu cũng được miễn trong class):

```php
    /**
     * Ánh xạ source_key -> nơi lấy MAX để khởi tạo mốc ("bắt từ hiện tại, không backfill").
     * Trả null nếu key không xác định.
     * @return array{table:string,column:string,field:string}|null
     */
    public function initTargetFor($sourceKey)
    {
        $map = [
            'his_service_req'           => ['table' => 'his_service_req',          'column' => 'modify_time', 'field' => 'last_modify_time'],
            'his_medicine_interactive'  => ['table' => 'his_medicine_interactive', 'column' => 'id',          'field' => 'last_id'],
            'his_exp_mest_medicine'     => ['table' => 'his_exp_mest_medicine',    'column' => 'id',          'field' => 'last_id'],
            'his_sere_serv_restriction' => ['table' => 'his_sere_serv',            'column' => 'id',          'field' => 'last_id'],
        ];
        return isset($map[$sourceKey]) ? $map[$sourceKey] : null;
    }

    /**
     * Mốc khởi tạo = MAX hiện tại của nguồn (đọc HIS). Key lạ -> toàn 0 (không chạm DB).
     * @return array{last_create_time:int,last_modify_time:int,last_id:int}
     */
    public function initialWatermark($sourceKey)
    {
        $base = ['last_create_time' => 0, 'last_modify_time' => 0, 'last_id' => 0];

        $target = $this->initTargetFor($sourceKey);
        if ($target === null) {
            return $base;
        }

        $max = (int) DB::connection($this->conn)->table($target['table'])->max($target['column']);
        $base[$target['field']] = $max;
        return $base;
    }
```

- [ ] **Step 4: Chạy test — xác nhận PASS**

Run: `cd "C:\Users\tracnn\qlbv" && vendor/bin/phpunit tests/Unit/OrderCheck/HisOrderSourceInitialWatermarkTest.php`
Expected: OK (2 tests).

- [ ] **Step 5: Smoke đọc Oracle (máy có HISPro)**

Run:
```bash
cd "C:\Users\tracnn\qlbv" && printf '%s\n' \
'$s = new App\Services\OrderCheck\HisOrderSource();' \
'$a = $s->initialWatermark("his_service_req");' \
'$b = $s->initialWatermark("his_medicine_interactive");' \
'echo "service_req last_modify_time=".$a["last_modify_time"]." (id=".$a["last_id"].")\n";' \
'echo "interactive last_id=".$b["last_id"]." (modify=".$b["last_modify_time"].")\n";' \
'exit' | php artisan tinker 2>&1 | grep -E "service_req|interactive|ORA-|error"
```
Expected: `service_req last_modify_time=<số>0000..` > 0; `interactive last_id=<số>` >= 0; không lỗi ORA-.

- [ ] **Step 6: Commit**

```bash
git add app/Services/OrderCheck/HisOrderSource.php tests/Unit/OrderCheck/HisOrderSourceInitialWatermarkTest.php
git commit -m "feat(order-check): HisOrderSource::initialWatermark lay MAX theo source_key"
```

---

### Task 2: `OrderCheckEngine::getWatermark` khởi tạo mốc lần đầu

**Files:**
- Modify: `app/Services/OrderCheck/OrderCheckEngine.php` (method `getWatermark`, dòng ~75-81)

- [ ] **Step 1: Sửa `getWatermark`**

Thay method hiện tại:
```php
    public function getWatermark($sourceKey)
    {
        return OrderCheckWatermark::firstOrCreate(
            ['source_key' => $sourceKey],
            ['last_create_time' => 0, 'last_modify_time' => 0, 'last_id' => 0]
        );
    }
```
bằng:
```php
    public function getWatermark($sourceKey)
    {
        $wm = OrderCheckWatermark::firstOrCreate(
            ['source_key' => $sourceKey],
            ['last_create_time' => 0, 'last_modify_time' => 0, 'last_id' => 0]
        );

        // Lần ĐẦU tạo dòng => đặt mốc = MAX hiện tại (đọc HIS ở runtime scanner),
        // KHÔNG backfill lịch sử. Các lần sau chạy bình thường.
        if ($wm->wasRecentlyCreated) {
            $init = $this->source->initialWatermark($sourceKey);
            $wm->last_create_time = $init['last_create_time'];
            $wm->last_modify_time = $init['last_modify_time'];
            $wm->last_id          = $init['last_id'];
            $wm->last_run_at      = now();
            $wm->save();
        }

        return $wm;
    }
```

- [ ] **Step 2: Lint**

Run: `cd "C:\Users\tracnn\qlbv" && php -l app/Services/OrderCheck/OrderCheckEngine.php`
Expected: `No syntax errors detected`.

- [ ] **Step 3: Smoke end-to-end (init-once, đặt đúng MAX)**

Kiểm nhánh `wasRecentlyCreated` bằng cách xóa tạm dòng watermark `his_service_req` rồi gọi lại (nó tự đặt lại = MAX hiện tại — an toàn, đúng giá trị đáng có):
```bash
cd "C:\Users\tracnn\qlbv" && printf '%s\n' \
'$eng = new App\Services\OrderCheck\OrderCheckEngine(new App\Services\OrderCheck\HisOrderSource());' \
'App\Models\OrderCheck\OrderCheckWatermark::where("source_key","his_service_req")->delete();' \
'$wm = $eng->getWatermark("his_service_req");' \
'$maxDb = (int) DB::connection(config("order_check.his_connection"))->table("his_service_req")->max("modify_time");' \
'echo "wm_modify=".$wm->last_modify_time." max_db=".$maxDb." match=".($wm->last_modify_time===$maxDb?"OK":"FAIL")."\n";' \
'$wm2 = $eng->getWatermark("his_service_req");' \
'echo "second_recentlyCreated=".($wm2->wasRecentlyCreated?"true":"false")." (ky vong false)\n";' \
'exit' | php artisan tinker 2>&1 | grep -E "wm_modify|second_|ORA-|error"
```
Expected: `match=OK`; `second_recentlyCreated=false`. (Dòng watermark được đặt lại = MAX hiện tại, đúng như trước.)

- [ ] **Step 4: Commit**

```bash
git add app/Services/OrderCheck/OrderCheckEngine.php
git commit -m "feat(order-check): getWatermark khoi tao moc=MAX lan dau (khong backfill)"
```

---

### Task 3: Migration `init_order_check_watermarks` -> no-op

**Files:**
- Modify: `database/migrations/2026_06_30_100006_init_order_check_watermarks.php`

- [ ] **Step 1: Bỏ đọc Oracle trong `up()`**

Thay toàn bộ nội dung method `up()` (hiện đọc HISPro và upsert 4 dòng) bằng no-op có chú thích. Method `down()` giữ nguyên. Nội dung `up()` mới:

```php
    public function up()
    {
        // Cố ý KHÔNG đọc HIS (Oracle) tại đây: migrate không được phụ thuộc Oracle-CLI
        // (nhiều CSKCB CLI chưa kết nối được Oracle -> Oci8:460 khi deploy).
        // Việc đặt mốc = MAX hiện tại đã dời sang runtime: OrderCheckEngine::getWatermark()
        // khởi tạo lần đầu qua HisOrderSource::initialWatermark() (không backfill lịch sử).
    }
```

- [ ] **Step 2: Lint + xác nhận không còn tham chiếu Oracle**

Run: `cd "C:\Users\tracnn\qlbv" && php -l database/migrations/2026_06_30_100006_init_order_check_watermarks.php && grep -cE "DB::connection|->max\(" database/migrations/2026_06_30_100006_init_order_check_watermarks.php`
Expected: `No syntax errors detected` và số đếm = `0`.

- [ ] **Step 3: Commit**

```bash
git add database/migrations/2026_06_30_100006_init_order_check_watermarks.php
git commit -m "fix(order-check): migration init watermark thanh no-op (bo doc Oracle luc migrate)"
```

---

### Task 4: Verify toàn bộ + push

**Files:** (không sửa code)

- [ ] **Step 1: Chạy toàn bộ unit test order-check**

Run: `cd "C:\Users\tracnn\qlbv" && vendor/bin/phpunit tests/Unit/OrderCheck/`
Expected: OK (tất cả pass, gồm test mới `HisOrderSourceInitialWatermarkTest`).

- [ ] **Step 2: Xác nhận migration không còn chạm Oracle**

Run: `cd "C:\Users\tracnn\qlbv" && grep -rEn "DB::connection|->max\(|his_service_req|his_sere_serv" database/migrations/2026_06_30_100006_init_order_check_watermarks.php`
Expected: 0 kết quả (up() sạch, chỉ còn chú thích).

- [ ] **Step 3: Smoke lại getWatermark cho 1 nguồn id-based (đảm bảo cả nhánh id)**

Run:
```bash
cd "C:\Users\tracnn\qlbv" && printf '%s\n' \
'$eng = new App\Services\OrderCheck\OrderCheckEngine(new App\Services\OrderCheck\HisOrderSource());' \
'App\Models\OrderCheck\OrderCheckWatermark::where("source_key","his_sere_serv_restriction")->delete();' \
'$wm = $eng->getWatermark("his_sere_serv_restriction");' \
'$maxDb = (int) DB::connection(config("order_check.his_connection"))->table("his_sere_serv")->max("id");' \
'echo "wm_id=".$wm->last_id." max_db=".$maxDb." match=".($wm->last_id===$maxDb?"OK":"FAIL")."\n";' \
'exit' | php artisan tinker 2>&1 | grep -E "wm_id|match|ORA-|error"
```
Expected: `match=OK`.

- [ ] **Step 4: Push**

```bash
git push origin main
```

---

## Hoàn tất

Sau 4 task: `migrate` chạy được ở mọi CSKCB dù CLI có Oracle hay không (hết lỗi Oci8:460 khi deploy). Watermark tự khởi tạo = MAX ở lần scanner đầu tiên (không backfill). Site đã có watermark từ trước không bị ảnh hưởng. 4 scanner không đổi.

**Lưu ý deploy:** sau khi merge, ở các site từng lỗi migrate — migration no-op sẽ chạy qua ở lần `update` kế; nếu site có dùng order-check, scanner sẽ tự đặt mốc khi chạy (cần Oracle ở runtime scanner như thiết kế).
