# XML3176 Nhóm C — Quy tắc Mức hưởng — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Thêm rule XML3176 phát hiện hồ sơ khai `muc_huong` vượt trần cho phép (đúng tuyến ≥15% lương cơ sở; trái tuyến nội trú tuyến TW → 40%).

**Architecture:** Helper thuần `MucHuongCalculator` tính trần (test không cần DB) + hàm `checkMucHuong` trong `Xml3176CompleteChecker` (đọc thẻ/tuyến/chi phí từ XML1, soi `muc_huong` các dòng XML2/XML3, phát 1 lỗi/hồ sơ) + config `muc_huong` + seeder 2 error_code.

**Tech Stack:** Laravel 5.5, PHP 7.4, PHPUnit 6.5.

**Spec:** `docs/superpowers/specs/2026-09-04-xml3176-quy-tac-muc-huong-nhom-c-design.md`

## Global Constraints

- PHP 7.4 / Laravel 5.5. PHPUnit 6.5: `setUp()` KHÔNG có `:void`; dùng `assertContains`/`assertEquals`, KHÔNG `assertStringContainsString`.
- CẤM `RefreshDatabase` và bất kỳ thao tác nào xoá/migrate DB dev `qlbv`. Test cần DB phải dùng sqlite in-memory qua trait `Tests\Support\Xml3176RuleTestSupport::bootXml3176Sqlite`.
- Helper thuần đặt tại `app/Services/Xml3176/Support/`, KHÔNG chạm DB/model/config bên trong (nhận primitive).
- Error object mỗi lỗi: `(object)['error_code','error_name','critical_error'=>$this->xmlErrorService->getCriticalErrorStatus($code),'description']`.
- Prefix error của CompleteChecker = `XMLComplete_` (qua `generateErrorCode($key)`).
- So sánh mức hưởng: `> trần + 0.01` (chỉ bắt vượt trần). Guard "thiếu căn cứ thì im lặng" (xem spec §7).
- Chạy test theo file/thư mục cụ thể (`tests/Unit/Xml3176`), KHÔNG chạy toàn bộ `phpunit` (chéo nhiễm Unit↔Feature).

---

## File Structure

- Create: `app/Services/Xml3176/Support/MucHuongCalculator.php` — helper thuần tính trần.
- Create: `tests/Unit/Xml3176/Support/MucHuongCalculatorTest.php` — unit test helper.
- Modify: `config/xml3176.php` — thêm khối `muc_huong`.
- Modify: `app/Services/Xml3176CompleteChecker.php` — thêm `use` + hàm `checkMucHuong` + nối vào `checkErrors`.
- Create: `database/seeds/Xml3176ErrorCatalogMucHuongSeeder.php` — nạp 2 error_code.

---

### Task 1: Helper `MucHuongCalculator` (thuần)

**Files:**
- Create: `app/Services/Xml3176/Support/MucHuongCalculator.php`
- Test: `tests/Unit/Xml3176/Support/MucHuongCalculatorTest.php`

**Interfaces:**
- Produces:
  - `MucHuongCalculator::quyenLoiChar($maThe): ?string`
  - `MucHuongCalculator::entitlement(?string $qlChar, array $map): ?int`
  - `MucHuongCalculator::tranDungTuyen(?int $entitlement, float $chiPhi, ?int $luongCoSo, float $rate): ?int`

- [ ] **Step 1: Viết test thất bại**

Tạo `tests/Unit/Xml3176/Support/MucHuongCalculatorTest.php`:

```php
<?php

namespace Tests\Unit\Xml3176\Support;

use App\Services\Xml3176\Support\MucHuongCalculator;
use Tests\TestCase;

class MucHuongCalculatorTest extends TestCase
{
    private $map = ['1' => 100, '2' => 100, '3' => 95, '4' => 80, '5' => 100];

    /** @test */
    public function quyen_loi_char_lay_ky_tu_vi_tri_3()
    {
        $this->assertSame('4', MucHuongCalculator::quyenLoiChar('DN4010012345'));
        $this->assertSame('2', MucHuongCalculator::quyenLoiChar('DT2150067890'));
    }

    /** @test */
    public function quyen_loi_char_danh_sach_cung_quyen_loi()
    {
        // Thẻ cũ;mới cùng ký tự quyền lợi -> trả ký tự đó
        $this->assertSame('4', MucHuongCalculator::quyenLoiChar('DN4010012345;HC4010098765'));
    }

    /** @test */
    public function quyen_loi_char_danh_sach_khac_quyen_loi_tra_null()
    {
        $this->assertNull(MucHuongCalculator::quyenLoiChar('DN4010012345;HC2010098765'));
    }

    /** @test */
    public function quyen_loi_char_ngan_hoac_rong_tra_null()
    {
        $this->assertNull(MucHuongCalculator::quyenLoiChar('DN'));
        $this->assertNull(MucHuongCalculator::quyenLoiChar(''));
        $this->assertNull(MucHuongCalculator::quyenLoiChar(null));
    }

    /** @test */
    public function entitlement_tra_theo_map()
    {
        $this->assertSame(80, MucHuongCalculator::entitlement('4', $this->map));
        $this->assertSame(95, MucHuongCalculator::entitlement('3', $this->map));
        $this->assertSame(100, MucHuongCalculator::entitlement('1', $this->map));
        $this->assertNull(MucHuongCalculator::entitlement('9', $this->map));
        $this->assertNull(MucHuongCalculator::entitlement(null, $this->map));
    }

    /** @test */
    public function tran_dung_tuyen_chi_phi_tren_nguong_tra_entitlement()
    {
        // LCS 2.530.000, ngưỡng 15% = 379.500; chi phí 500.000 >= ngưỡng -> 80
        $this->assertSame(80, MucHuongCalculator::tranDungTuyen(80, 500000, 2530000, 0.15));
    }

    /** @test */
    public function tran_dung_tuyen_chi_phi_duoi_nguong_tra_100()
    {
        // chi phí 100.000 < 379.500 -> miễn, 100%
        $this->assertSame(100, MucHuongCalculator::tranDungTuyen(80, 100000, 2530000, 0.15));
    }

    /** @test */
    public function tran_dung_tuyen_bien_bang_nguong_tra_entitlement()
    {
        // chi phí = đúng 15% * LCS -> áp entitlement (>=)
        $this->assertSame(80, MucHuongCalculator::tranDungTuyen(80, 379500, 2530000, 0.15));
    }

    /** @test */
    public function tran_dung_tuyen_guard_khi_thieu_can_cu()
    {
        $this->assertNull(MucHuongCalculator::tranDungTuyen(80, 500000, null, 0.15));
        $this->assertNull(MucHuongCalculator::tranDungTuyen(80, 500000, 0, 0.15));
        $this->assertNull(MucHuongCalculator::tranDungTuyen(null, 500000, 2530000, 0.15));
    }
}
```

- [ ] **Step 2: Chạy test để xác nhận thất bại**

Run: `./vendor/bin/phpunit tests/Unit/Xml3176/Support/MucHuongCalculatorTest.php`
Expected: FAIL — `Class 'App\Services\Xml3176\Support\MucHuongCalculator' not found`.

- [ ] **Step 3: Viết helper tối thiểu**

Tạo `app/Services/Xml3176/Support/MucHuongCalculator.php`:

```php
<?php

namespace App\Services\Xml3176\Support;

/**
 * Tính trần mức hưởng BHYT cho phép của một hồ sơ, để so với muc_huong khai
 * trong XML2/XML3. Helper thuần — không chạm DB/model/config.
 */
class MucHuongCalculator
{
    /**
     * Trích ký tự quyền lợi (vị trí thứ 3) từ mã thẻ.
     * Mã thẻ có thể là danh sách ngăn cách ';' (thẻ cũ/mới). Nếu các đoạn cho
     * quyền lợi KHÁC nhau -> null (mơ hồ). Đoạn < 3 ký tự bị bỏ.
     */
    public static function quyenLoiChar($maThe): ?string
    {
        if ($maThe === null) {
            return null;
        }

        $found = null;
        foreach (explode(';', (string) $maThe) as $segment) {
            $segment = trim($segment);
            if (mb_strlen($segment) < 3) {
                continue;
            }
            $char = mb_substr($segment, 2, 1);
            if ($found === null) {
                $found = $char;
            } elseif ($found !== $char) {
                return null; // hai thẻ khác quyền lợi -> mơ hồ
            }
        }

        return $found;
    }

    /** Tra % mức hưởng theo ký tự quyền lợi. null nếu không có trong map. */
    public static function entitlement(?string $qlChar, array $map): ?int
    {
        if ($qlChar === null || !array_key_exists($qlChar, $map)) {
            return null;
        }

        return (int) $map[$qlChar];
    }

    /**
     * Trần mức hưởng cho phép khi ĐÚNG TUYẾN.
     *  - $luongCoSo null/<=0 hoặc $entitlement null -> null (guard).
     *  - chi phí >= rate * luongCoSo -> $entitlement (áp quyền lợi thẻ).
     *  - chi phí <  rate * luongCoSo -> 100 (miễn cùng chi trả, chi phí nhỏ).
     */
    public static function tranDungTuyen(?int $entitlement, float $chiPhi, ?int $luongCoSo, float $rate): ?int
    {
        if ($entitlement === null || $luongCoSo === null || $luongCoSo <= 0) {
            return null;
        }

        return $chiPhi >= $rate * $luongCoSo ? $entitlement : 100;
    }
}
```

- [ ] **Step 4: Chạy test để xác nhận đạt**

Run: `./vendor/bin/phpunit tests/Unit/Xml3176/Support/MucHuongCalculatorTest.php`
Expected: PASS (11 test).

- [ ] **Step 5: Commit**

```bash
git add app/Services/Xml3176/Support/MucHuongCalculator.php tests/Unit/Xml3176/Support/MucHuongCalculatorTest.php
git commit -m "feat(xml3176): helper MucHuongCalculator tinh tran muc huong"
```

---

### Task 2: Config khối `muc_huong`

**Files:**
- Modify: `config/xml3176.php` (thêm khối `muc_huong` vào cuối mảng, cạnh `general`)

**Interfaces:**
- Produces: `config('xml3176.muc_huong.quyen_loi_map')`, `.nguong_luong_co_so_rate`, `.trai_tuyen_noi_tru_tw_rate`, `.tuyen_tw_values` — Task 3 tiêu thụ.

- [ ] **Step 1: Thêm khối config**

Trong `config/xml3176.php`, thêm ngay trước dòng đóng mảng `];` cuối cùng (sau khối `'general' => [...]`):

```php
    'muc_huong' => [
        // Ký tự quyền lợi (vị trí 3 mã thẻ) -> % mức hưởng. Chỉnh được không cần sửa code.
        'quyen_loi_map'              => ['1' => 100, '2' => 100, '3' => 95, '4' => 80, '5' => 100],
        'nguong_luong_co_so_rate'    => 0.15, // Ngưỡng 15% lương cơ sở (miễn cùng chi trả)
        'trai_tuyen_noi_tru_tw_rate' => 40,   // % mức hưởng trái tuyến nội trú tuyến TW
        'tuyen_tw_values'            => ['1'], // Giá trị medical_organizations.tuyen_cmkt coi là tuyến TW
    ],
```

- [ ] **Step 2: Xác nhận PHP hợp lệ**

Run: `php -r "var_dump(require 'config/xml3176.php');" | grep -A6 muc_huong`
Expected: in ra mảng `muc_huong` với 4 khoá.

- [ ] **Step 3: Commit**

```bash
git add config/xml3176.php
git commit -m "feat(xml3176): config muc_huong (map quyen loi, nguong 15%, ty le trai tuyen)"
```

---

### Task 3: Hàm `checkMucHuong` trong `Xml3176CompleteChecker`

**Files:**
- Modify: `app/Services/Xml3176CompleteChecker.php`

**Interfaces:**
- Consumes: `MucHuongCalculator` (Task 1); `config('xml3176.muc_huong.*')` (Task 2); sẵn có `NguongMienCungChiTra::luongCoSoTaiNgay`, `Xml3176DateHelper::toDateTime`, `MedicalOrganization`, config `xml3176.xml1.ma_doituong_kcb_trai_tuyen`, `xml3176.treatment_type_inpatient`, `mcct.luong_co_so`.
- Produces: error_code `XMLComplete_MUC_HUONG_EXCEEDS_ENTITLEMENT`, `XMLComplete_MUC_HUONG_TRAI_TUYEN_TW` (Task 4 seeder khai cùng chuỗi).

- [ ] **Step 1: Thêm các `use` cần thiết**

Ở đầu `app/Services/Xml3176CompleteChecker.php`, thêm sau các `use` model hiện có:

```php
use App\Models\BHYT\MedicalOrganization;
use App\Services\Xml3176\Support\MucHuongCalculator;
use App\Services\Mcct\NguongMienCungChiTra;
```

(Chú ý: `Xml3176DateHelper` đã được `use` sẵn trong file — không thêm lại.)

- [ ] **Step 2: Nối `checkMucHuong` vào `checkErrors`**

Trong `checkErrors($ma_lk)`, ngay sau dòng `$errors = $errors->merge($this->checkSecondSurgeryFullPayment($ma_lk));`, thêm:

```php
            $errors = $errors->merge($this->checkMucHuong($data));
```

- [ ] **Step 3: Viết hàm `checkMucHuong`**

Thêm hàm private mới (đặt cạnh `checkInvalidBedDays`):

```php
    /**
     * Kiểm tra mức hưởng khai (muc_huong) có vượt trần cho phép không.
     *  - Đúng tuyến, chi phí >= 15% lương cơ sở -> trần = quyền lợi thẻ.
     *  - Trái tuyến nội trú tuyến TW -> trần = 40%.
     * Guard "thiếu căn cứ thì im lặng" (xem spec §7). Phát tối đa 1 lỗi/hồ sơ.
     *
     * @param Xml3176Xml1 $data
     * @return Collection
     */
    private function checkMucHuong(Xml3176Xml1 $data): Collection
    {
        $errors = collect();
        $cfg = config('xml3176.muc_huong');

        $qlChar = MucHuongCalculator::quyenLoiChar($data->ma_the_bhyt);
        if ($qlChar === null) {
            return $errors; // guard: quyền lợi mơ hồ
        }
        $entitlement = MucHuongCalculator::entitlement($qlChar, (array) $cfg['quyen_loi_map']);
        if ($entitlement === null) {
            return $errors; // guard: không map được quyền lợi
        }

        $traiTuyenPrefixes = (array) config('xml3176.xml1.ma_doituong_kcb_trai_tuyen', []);
        $maDoiTuong = (string) $data->ma_doituong_kcb;
        $traiTuyen = false;
        foreach ($traiTuyenPrefixes as $prefix) {
            if ($prefix !== '' && strpos($maDoiTuong, (string) $prefix) === 0) {
                $traiTuyen = true;
                break;
            }
        }
        $noiTru = in_array($data->ma_loai_kcb, (array) config('xml3176.treatment_type_inpatient', []));

        if ($traiTuyen) {
            if (!$noiTru) {
                return $errors; // ngoài phạm vi: trái tuyến ngoại trú
            }
            $tuyen = MedicalOrganization::where('ma_cskcb', $data->ma_cskcb)->value('tuyen_cmkt');
            if (!in_array($tuyen, (array) $cfg['tuyen_tw_values'], true)) {
                return $errors; // GUARD: không xác định được tuyến TW
            }
            $tran = (int) $cfg['trai_tuyen_noi_tru_tw_rate'];
            $errorKey = 'MUC_HUONG_TRAI_TUYEN_TW';
            $loaiMo = 'trái tuyến nội trú tuyến TW';
        } else {
            $dt = Xml3176DateHelper::toDateTime($data->ngay_vao);
            if ($dt === null) {
                return $errors; // guard: ngày vào không hợp lệ
            }
            $lcs = NguongMienCungChiTra::luongCoSoTaiNgay($dt->format('Y-m-d'), (array) config('mcct.luong_co_so', []));
            $tran = MucHuongCalculator::tranDungTuyen(
                $entitlement,
                (float) $data->t_tongchi_bh,
                $lcs ?: null,
                (float) $cfg['nguong_luong_co_so_rate']
            );
            if ($tran === null) {
                return $errors; // guard: chưa có mốc lương cơ sở
            }
            $errorKey = 'MUC_HUONG_EXCEEDS_ENTITLEMENT';
            $loaiMo = 'đúng tuyến';
        }

        $maxXml2 = $data->Xml3176Xml2()->whereNotNull('muc_huong')->max('muc_huong');
        $maxXml3 = $data->Xml3176Xml3()->whereNotNull('muc_huong')->max('muc_huong');
        if ($maxXml2 === null && $maxXml3 === null) {
            return $errors; // guard: không dòng nào khai mức hưởng
        }
        $maxDeclared = max((float) $maxXml2, (float) $maxXml3);

        if ($maxDeclared > $tran + 0.01) {
            $errorCode = $this->generateErrorCode($errorKey);
            $errors->push((object)[
                'error_code'     => $errorCode,
                'error_name'     => 'Mức hưởng khai vượt trần cho phép',
                'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                'description'    => 'Hồ sơ ' . $loaiMo . ': mức hưởng khai tối đa ' . $maxDeclared
                                  . '%, trần cho phép ' . $tran . '% (quyền lợi thẻ ' . $entitlement . '%).',
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
Expected: PASS toàn bộ (>= 168 test: 157 cũ + 11 helper mới).

- [ ] **Step 6: Commit**

```bash
git add app/Services/Xml3176CompleteChecker.php
git commit -m "feat(xml3176): rule kiem tra muc huong vuot tran (dung tuyen + trai tuyen TW)"
```

---

### Task 4: Seeder nạp error_code

**Files:**
- Create: `database/seeds/Xml3176ErrorCatalogMucHuongSeeder.php`

**Interfaces:**
- Consumes: error_code chuỗi từ Task 3 (`XMLComplete_MUC_HUONG_EXCEEDS_ENTITLEMENT`, `XMLComplete_MUC_HUONG_TRAI_TUYEN_TW`).

- [ ] **Step 1: Tạo seeder**

Tạo `database/seeds/Xml3176ErrorCatalogMucHuongSeeder.php` (khuôn giống `Xml3176ErrorCatalogTyLeVtytSeeder`):

```php
<?php

use Illuminate\Database\Seeder;
use App\Models\BHYT\Xml3176ErrorCatalog;

/**
 * Nạp sẵn 2 error_code kiểm tra mức hưởng (XMLComplete) để cấu hình trước lần
 * scan đầu. Idempotent (updateOrCreate) — chạy lại an toàn.
 */
class Xml3176ErrorCatalogMucHuongSeeder extends Seeder
{
    public function run()
    {
        $rules = [
            ['XMLComplete', 'XMLComplete_MUC_HUONG_EXCEEDS_ENTITLEMENT', 'Mức hưởng vượt quyền lợi thẻ (đúng tuyến, chi phí >= 15% lương cơ sở)'],
            ['XMLComplete', 'XMLComplete_MUC_HUONG_TRAI_TUYEN_TW', 'Mức hưởng vượt 40% (trái tuyến nội trú tuyến TW)'],
        ];

        foreach ($rules as $r) {
            list($xml, $code, $name) = $r;
            Xml3176ErrorCatalog::updateOrCreate(
                ['xml' => $xml, 'error_code' => $code],
                ['error_name' => $name, 'description' => $name, 'critical_error' => true, 'is_check' => true]
            );
        }
    }
}
```

- [ ] **Step 2: Kiểm tra cú pháp**

Run: `php -l database/seeds/Xml3176ErrorCatalogMucHuongSeeder.php`
Expected: `No syntax errors detected`.

- [ ] **Step 3: Commit**

```bash
git add database/seeds/Xml3176ErrorCatalogMucHuongSeeder.php
git commit -m "feat(xml3176): seeder nap 2 error_code muc huong"
```

---

## Self-Review

- **Spec coverage:** Rule 1 (đúng tuyến) → Task 3 nhánh `else`; Rule 2 (trái tuyến TW + guard) → Task 3 nhánh `if ($traiTuyen)`; config → Task 2; helper → Task 1; seeder/error_code → Task 4; test helper → Task 1. Integration test là tuỳ chọn trong spec §8 — plan chọn KHÔNG làm (theo tiền lệ B1: helper thuần + hồi quy + rà nối tay), giảm rủi ro dựng bảng `medical_organizations` sqlite; đánh đổi đã ghi.
- **Placeholder scan:** không có TBD/TODO; mọi step có mã cụ thể.
- **Type consistency:** `quyenLoiChar`/`entitlement`/`tranDungTuyen` chữ ký khớp giữa Task 1 (định nghĩa) và Task 3 (gọi). `toDateTime()->format('Y-m-d')` khớp key config `mcct.luong_co_so` (dạng `Y-m-d`). error_code chuỗi khớp giữa Task 3 và Task 4.
