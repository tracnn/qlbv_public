# XML3176 B2 — Ngày giường tối thiểu TT39 — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Thêm rule cảnh báo hồ sơ nội trú khai tổng ngày giường nhỏ hơn số ngày điều trị theo TT39 (lỗi giám định 440).

**Architecture:** Helper thuần `BedDaysTT39Calculator` (tính expected + isBelow, test không cần DB) + hàm `checkBedDaysBelowTT39` trong `Xml3176CompleteChecker` (tái tính số ngày từ ngày vào/ra theo dương lịch + quy tắc 4h + cờ đặc biệt, so với Σ so_luong dòng giường) + config `bed_days_tt39` + seeder 1 error_code (critical_error=false).

**Tech Stack:** Laravel 5.5, PHP 7.4, PHPUnit 6.5.

**Spec:** `docs/superpowers/specs/2026-09-04-xml3176-quy-tac-ngay-giuong-toi-thieu-tt39-b2-design.md`

## Global Constraints

- PHP 7.4 / Laravel 5.5. PHPUnit 6.5: `setUp()` KHÔNG `:void`; dùng `assertSame`/`assertTrue`/`assertFalse`.
- CẤM `RefreshDatabase` và mọi thao tác xoá/migrate DB dev `qlbv`.
- Helper thuần tại `app/Services/Xml3176/Support/`, KHÔNG chạm DB/model/config bên trong (nhận primitive).
- Error object: `(object)['error_code','error_name','critical_error'=>$this->xmlErrorService->getCriticalErrorStatus($code),'description']`. Prefix CompleteChecker = `XMLComplete_`.
- **Rule 440 là CẢNH BÁO** — seeder đặt `critical_error = false`.
- Chạy test theo file/thư mục cụ thể (`tests/Unit/Xml3176`), KHÔNG chạy toàn bộ `phpunit`.

---

## File Structure

- Create: `app/Services/Xml3176/Support/BedDaysTT39Calculator.php`
- Create: `tests/Unit/Xml3176/Support/BedDaysTT39CalculatorTest.php`
- Modify: `config/xml3176.php` — thêm khối `bed_days_tt39`.
- Modify: `app/Services/Xml3176CompleteChecker.php` — thêm `use` + hàm `checkBedDaysBelowTT39` + nối `checkErrors`.
- Create: `database/seeds/Xml3176ErrorCatalogBedDaysTT39Seeder.php`.

---

### Task 1: Helper `BedDaysTT39Calculator` (thuần)

**Files:**
- Create: `app/Services/Xml3176/Support/BedDaysTT39Calculator.php`
- Test: `tests/Unit/Xml3176/Support/BedDaysTT39CalculatorTest.php`

**Interfaces:**
- Produces:
  - `BedDaysTT39Calculator::expected(int $calendarDays, float $elapsedHours, bool $special): int`
  - `BedDaysTT39Calculator::isBelow(float $totalBedDays, int $expected, float $tolerance): bool`

- [ ] **Step 1: Viết test thất bại**

Tạo `tests/Unit/Xml3176/Support/BedDaysTT39CalculatorTest.php`:

```php
<?php

namespace Tests\Unit\Xml3176\Support;

use App\Services\Xml3176\Support\BedDaysTT39Calculator;
use Tests\TestCase;

class BedDaysTT39CalculatorTest extends TestCase
{
    /** @test */
    public function cung_ngay_tren_4h_tinh_1_ngay()
    {
        $this->assertSame(1, BedDaysTT39Calculator::expected(0, 5.0, false));
        $this->assertSame(1, BedDaysTT39Calculator::expected(0, 4.0, false));
    }

    /** @test */
    public function cung_ngay_duoi_4h_tinh_0_ngay()
    {
        $this->assertSame(0, BedDaysTT39Calculator::expected(0, 3.5, false));
    }

    /** @test */
    public function nhieu_ngay_thuong_bang_calendar_days()
    {
        $this->assertSame(8, BedDaysTT39Calculator::expected(8, 201.0, false));
    }

    /** @test */
    public function nhieu_ngay_dac_biet_cong_1()
    {
        // tử vong/chuyển viện/nặng xin về -> +1
        $this->assertSame(9, BedDaysTT39Calculator::expected(8, 201.0, true));
    }

    /** @test */
    public function is_below_false_khi_expected_duoi_1()
    {
        $this->assertFalse(BedDaysTT39Calculator::isBelow(0.0, 0, 0.5));
    }

    /** @test */
    public function is_below_false_khi_du_ngay_giuong()
    {
        $this->assertFalse(BedDaysTT39Calculator::isBelow(8.0, 8, 0.5));
        $this->assertFalse(BedDaysTT39Calculator::isBelow(9.0, 8, 0.5));
    }

    /** @test */
    public function is_below_false_khi_thieu_trong_dung_sai()
    {
        // expected 8, khai 7.6 -> thiếu 0.4 < 0.5 -> không cảnh báo
        $this->assertFalse(BedDaysTT39Calculator::isBelow(7.6, 8, 0.5));
    }

    /** @test */
    public function is_below_true_khi_thieu_ngoai_dung_sai()
    {
        // expected 8, khai 7.0 -> thiếu 1.0 > 0.5 -> cảnh báo
        $this->assertTrue(BedDaysTT39Calculator::isBelow(7.0, 8, 0.5));
    }

    /** @test */
    public function is_below_true_khi_tong_bang_0()
    {
        $this->assertTrue(BedDaysTT39Calculator::isBelow(0.0, 3, 0.5));
    }
}
```

- [ ] **Step 2: Chạy test để xác nhận thất bại**

Run: `./vendor/bin/phpunit tests/Unit/Xml3176/Support/BedDaysTT39CalculatorTest.php`
Expected: FAIL — `Class 'App\Services\Xml3176\Support\BedDaysTT39Calculator' not found`.

- [ ] **Step 3: Viết helper tối thiểu**

Tạo `app/Services/Xml3176/Support/BedDaysTT39Calculator.php`:

```php
<?php

namespace App\Services\Xml3176\Support;

/**
 * Tính số ngày giường đúng theo TT39 và so với tổng ngày giường khai.
 * Helper thuần — không chạm DB/model/config.
 */
class BedDaysTT39Calculator
{
    /**
     * Số ngày giường đúng theo TT39.
     *  - Cùng ngày (calendarDays == 0): elapsedHours >= 4 -> 1, ngược lại 0.
     *  - Nhiều ngày: calendarDays + (special ? 1 : 0).
     *
     * @param int   $calendarDays số ngày dương lịch giữa ngày vào và ngày ra (>= 0)
     * @param float $elapsedHours tổng giờ trôi qua giữa vào và ra
     * @param bool  $special      tử vong / chuyển viện / nặng xin về -> +1
     */
    public static function expected(int $calendarDays, float $elapsedHours, bool $special): int
    {
        if ($calendarDays <= 0) {
            return $elapsedHours >= 4 ? 1 : 0;
        }

        return $calendarDays + ($special ? 1 : 0);
    }

    /**
     * Có thiếu ngày giường không: chỉ xét khi expected >= 1; thiếu khi
     * totalBedDays < expected - tolerance.
     */
    public static function isBelow(float $totalBedDays, int $expected, float $tolerance): bool
    {
        if ($expected < 1) {
            return false;
        }

        return $totalBedDays < $expected - $tolerance;
    }
}
```

- [ ] **Step 4: Chạy test để xác nhận đạt**

Run: `./vendor/bin/phpunit tests/Unit/Xml3176/Support/BedDaysTT39CalculatorTest.php`
Expected: PASS (9 test).

- [ ] **Step 5: Commit**

```bash
git add app/Services/Xml3176/Support/BedDaysTT39Calculator.php tests/Unit/Xml3176/Support/BedDaysTT39CalculatorTest.php
git commit -m "feat(xml3176): helper BedDaysTT39Calculator tinh so ngay giuong TT39"
```

---

### Task 2: Config khối `bed_days_tt39`

**Files:**
- Modify: `config/xml3176.php` (thêm khối vào cuối mảng, cạnh `muc_huong`)

**Interfaces:**
- Produces: `config('xml3176.bed_days_tt39.tolerance')` — Task 3 tiêu thụ.

- [ ] **Step 1: Thêm khối config**

Trong `config/xml3176.php`, thêm ngay trước dòng đóng mảng `];` cuối cùng (sau khối `'muc_huong' => [...]`):

```php
    'bed_days_tt39' => [
        'tolerance' => 0.5, // Dung sai (ngày) bỏ qua nhiễu làm tròn ½ ngày; chỉ cảnh báo khi thiếu rõ rệt
    ],
```

- [ ] **Step 2: Xác nhận PHP hợp lệ**

Run: `php -r "var_dump(config('xml3176.bed_days_tt39'));" 2>/dev/null || php -r "print_r((require 'config/xml3176.php')['bed_days_tt39']);"`
Expected: in ra `['tolerance' => 0.5]`.

- [ ] **Step 3: Commit**

```bash
git add config/xml3176.php
git commit -m "feat(xml3176): config bed_days_tt39 (dung sai ngay giuong toi thieu)"
```

---

### Task 3: Hàm `checkBedDaysBelowTT39` trong `Xml3176CompleteChecker`

**Files:**
- Modify: `app/Services/Xml3176CompleteChecker.php`

**Interfaces:**
- Consumes: `BedDaysTT39Calculator` (Task 1); `config('xml3176.bed_days_tt39.tolerance')` (Task 2); sẵn có `Xml3176DateHelper::toDateTime` (đã `use` trong file), `DateTime` (đã `use`), config `xml3176.treatment_type_inpatient`/`bed_group_code`/`invalid_treatment_result`/`invalid_end_type_treatment`; quan hệ `$data->Xml3176Xml3()`.
- Produces: error_code `XMLComplete_BED_DAYS_BELOW_TT39` (Task 4 seeder khai cùng chuỗi).

- [ ] **Step 1: Thêm `use` helper**

Ở đầu `app/Services/Xml3176CompleteChecker.php`, thêm (sau các `use` Support sẵn có; KHÔNG thêm lại `Xml3176DateHelper` hay `DateTime` — đã có):

```php
use App\Services\Xml3176\Support\BedDaysTT39Calculator;
```

- [ ] **Step 2: Nối vào `checkErrors`**

Trong `checkErrors($ma_lk)`, ngay sau dòng `$errors = $errors->merge($this->checkMucHuong($data));`, thêm:

```php
            $errors = $errors->merge($this->checkBedDaysBelowTT39($data));
```

- [ ] **Step 3: Viết hàm `checkBedDaysBelowTT39`**

Thêm hàm private mới (đặt cạnh `checkInvalidBedDays`):

```php
    /**
     * Cảnh báo hồ sơ nội trú khai tổng ngày giường NHỎ HƠN số ngày điều trị
     * tính theo TT39 (dương lịch + quy tắc 4h + cờ đặc biệt). Lỗi giám định 440.
     * Guard "thiếu căn cứ thì im lặng". critical_error do catalog quyết định (=false sau seed).
     *
     * @param Xml3176Xml1 $data
     * @return Collection
     */
    private function checkBedDaysBelowTT39(Xml3176Xml1 $data): Collection
    {
        $errors = collect();

        if (!in_array($data->ma_loai_kcb, (array) config('xml3176.treatment_type_inpatient', []))) {
            return $errors; // chỉ hồ sơ nội trú
        }

        $dtVao = Xml3176DateHelper::toDateTime($data->ngay_vao);
        $dtRa  = Xml3176DateHelper::toDateTime($data->ngay_ra);
        if ($dtVao === null || $dtRa === null) {
            return $errors; // guard: ngày không hợp lệ
        }

        $elapsedHours = ($dtRa->getTimestamp() - $dtVao->getTimestamp()) / 3600;
        if ($elapsedHours < 0) {
            return $errors; // guard: ra trước vào
        }

        $calendarDays = (int) (new DateTime($dtVao->format('Y-m-d')))
            ->diff(new DateTime($dtRa->format('Y-m-d')))->days;

        $special = in_array($data->ket_qua_dtri, (array) config('xml3176.invalid_treatment_result', []))
                || in_array($data->ma_loai_rv, (array) config('xml3176.invalid_end_type_treatment', []));

        $expected = BedDaysTT39Calculator::expected($calendarDays, $elapsedHours, $special);
        if ($expected < 1) {
            return $errors; // guard: lưu trú <4h hợp lệ
        }

        $totalBedDays = (float) $data->Xml3176Xml3()
            ->whereIn('ma_nhom', (array) config('xml3176.bed_group_code', []))
            ->sum('so_luong');

        $tol = (float) config('xml3176.bed_days_tt39.tolerance', 0.5);
        if (BedDaysTT39Calculator::isBelow($totalBedDays, $expected, $tol)) {
            $errorCode = $this->generateErrorCode('BED_DAYS_BELOW_TT39');
            $errors->push((object)[
                'error_code'     => $errorCode,
                'error_name'     => 'Tổng ngày giường nhỏ hơn hướng dẫn TT39',
                'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                'description'    => 'Tổng ngày giường khai ' . $totalBedDays . ' nhỏ hơn số ngày điều trị theo TT39 '
                                  . $expected . ' (chênh ' . round($expected - $totalBedDays, 2) . ').',
            ]);
        }

        return $errors;
    }
```

- [ ] **Step 4: Kiểm tra cú pháp**

Run: `php -l app/Services/Xml3176CompleteChecker.php`
Expected: `No syntax errors detected`.

- [ ] **Step 5: Chạy hồi quy toàn bộ unit test Xml3176**

Run: `./vendor/bin/phpunit tests/Unit/Xml3176`
Expected: PASS toàn bộ (>= 175 test: 166 cũ + 9 helper mới).

- [ ] **Step 6: Commit**

```bash
git add app/Services/Xml3176CompleteChecker.php
git commit -m "feat(xml3176): rule canh bao ngay giuong nho hon TT39 (loi 440)"
```

---

### Task 4: Seeder nạp error_code (cảnh báo)

**Files:**
- Create: `database/seeds/Xml3176ErrorCatalogBedDaysTT39Seeder.php`

**Interfaces:**
- Consumes: error_code `XMLComplete_BED_DAYS_BELOW_TT39` (Task 3).

- [ ] **Step 1: Tạo seeder**

Tạo `database/seeds/Xml3176ErrorCatalogBedDaysTT39Seeder.php` (LƯU Ý `critical_error => false`):

```php
<?php

use Illuminate\Database\Seeder;
use App\Models\BHYT\Xml3176ErrorCatalog;

/**
 * Nạp error_code cảnh báo ngày giường nhỏ hơn TT39 (lỗi giám định 440).
 * critical_error = false: đây là CẢNH BÁO, không chặn xuất XML.
 * Idempotent (updateOrCreate) — chạy lại an toàn.
 */
class Xml3176ErrorCatalogBedDaysTT39Seeder extends Seeder
{
    public function run()
    {
        Xml3176ErrorCatalog::updateOrCreate(
            ['xml' => 'XMLComplete', 'error_code' => 'XMLComplete_BED_DAYS_BELOW_TT39'],
            [
                'error_name'     => 'Tổng ngày giường nhỏ hơn hướng dẫn TT39',
                'description'    => 'Tổng ngày giường khai nhỏ hơn số ngày điều trị nội trú tính theo TT39',
                'critical_error' => false,
                'is_check'       => true,
            ]
        );
    }
}
```

- [ ] **Step 2: Kiểm tra cú pháp**

Run: `php -l database/seeds/Xml3176ErrorCatalogBedDaysTT39Seeder.php`
Expected: `No syntax errors detected`.

- [ ] **Step 3: Commit**

```bash
git add database/seeds/Xml3176ErrorCatalogBedDaysTT39Seeder.php
git commit -m "feat(xml3176): seeder error_code ngay giuong TT39 (canh bao, khong chan xuat)"
```

---

## Self-Review

- **Spec coverage:** helper `expected`/`isBelow` → Task 1; config tolerance → Task 2; rule + guard + Σ=0 cảnh báo → Task 3; error_code critical_error=false → Task 4; test helper → Task 1. Integration test là tùy chọn (spec §7) — plan KHÔNG làm (tiền lệ B1/Nhóm C), đánh đổi đã ghi.
- **Placeholder scan:** không có TBD/TODO; mọi step có mã cụ thể.
- **Type consistency:** `expected`/`isBelow` chữ ký khớp giữa Task 1 và Task 3. `new DateTime(...->format('Y-m-d'))` dùng dương lịch (khớp spec §6). error_code `XMLComplete_BED_DAYS_BELOW_TT39` khớp giữa Task 3 và Task 4. `critical_error=false` (Task 4) là điều kiện để `getCriticalErrorStatus` trả false (Task 3).
