# Bổ sung quy tắc bắt lỗi XML3176 Nhóm A — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Thêm 20 quy tắc bắt lỗi XML3176 (Nhóm A — thuần logic) vào các checker sẵn có, dựa trên 3 helper thuần được TDD.

**Architecture:** Bám pattern checker hiện có (Hướng 1): mỗi quy tắc là một hàm `check*()` (private) thêm vào checker theo loại, trả `Collection` object lỗi, nối vào `checkErrors()`. Logic khó nằm trong 3 helper thuần ở `App\Services\Xml3176\Support\`. Quy tắc tổng hợp/liên bảng đặt ở `Xml3176CompleteChecker`; quy tắc cần bản ghi anh em dùng truy vấn sibling.

**Tech Stack:** Laravel 5.5.50, PHP 7.4, PHPUnit 6.5.14, MySQL (mặc định), Eloquent.

**Spec:** `docs/superpowers/specs/2026-09-04-xml3176-them-quy-tac-nhom-a-design.md`

## Global Constraints

- PHPUnit 6.5: Unit test `setUp()` **KHÔNG** có `: void`. Dùng `assertContains`/`assertNotContains` (không có `assertStringContainsString`).
- **CẤM `RefreshDatabase`/`DatabaseMigrations`** — sẽ xoá sạch DB dev `qlbv`. Test chạm DB dùng SQLite in-memory ghi đè chính kết nối tên `mysql`.
- Không chạy full suite (`vendor/bin/phpunit` trần) — nhiễm chéo. Chạy file cụ thể.
- **CÁCH TEST CHECKER:** KHÔNG gọi `checkErrors()` đầy đủ (nó chạy cả rule cũ truy vấn nhiều bảng danh mục không có trong SQLite → đỏ nhầm). Thay vào đó: dựng checker với **fake ErrorService**, **gọi thẳng method mới qua reflection**, khẳng định trên `Collection` trả về. Per-record rule KHÔNG cần DB; chỉ XML5 (sibling) và Complete cần SQLite cho bảng được truy vấn.
- `error_code` theo convention `{XMLTYPE}_{KEY}` qua `generateErrorCode($key)`. Mỗi object lỗi có 4 khoá: `error_code`, `error_name`, `critical_error` (=`$this->xmlErrorService->getCriticalErrorStatus($errorCode)`), `description`.
- Mọi rule **bỏ qua khi trường liên quan rỗng/null hoặc ngày sai định dạng** — không false-positive, không ném exception.
- `Xml3176ErrorService` và `CommonValidationService` không có constructor (khởi tạo nhẹ, không DB) → dựng bằng `new`.

---

## Task 1: Helper `LieuDungParser`

**Files:**
- Create: `app/Services/Xml3176/Support/LieuDungParser.php`
- Test: `tests/Unit/Xml3176/Support/LieuDungParserTest.php`

**Interfaces:**
- Produces: `LieuDungParser::parse($lieu): array` → `['hop_le'=>bool,'sl_lan'=>float,'lan_ngay'=>float,'so_ngay'=>int,'don_vi'=>string,'tong_luong'=>float]`

- [ ] **Step 1: Viết test thất bại**

Tạo `tests/Unit/Xml3176/Support/LieuDungParserTest.php`:

```php
<?php

namespace Tests\Unit\Xml3176\Support;

use App\Services\Xml3176\Support\LieuDungParser;
use Tests\TestCase;

class LieuDungParserTest extends TestCase
{
    /** @test */
    public function parse_dung_dinh_dang_130_co_don_vi()
    {
        $r = LieuDungParser::parse('1 * 2 * 5 [Viên/ngày]');
        $this->assertTrue($r['hop_le']);
        $this->assertEquals(1.0, $r['sl_lan']);
        $this->assertEquals(2.0, $r['lan_ngay']);
        $this->assertEquals(5, $r['so_ngay']);
        $this->assertEquals('Viên', $r['don_vi']);
        $this->assertEquals(10.0, $r['tong_luong']);
    }

    /** @test */
    public function parse_chap_nhan_thap_phan_dau_phay_va_khong_don_vi()
    {
        $r = LieuDungParser::parse('1,5*2*3');
        $this->assertTrue($r['hop_le']);
        $this->assertEquals(9.0, $r['tong_luong']);
        $this->assertEquals('', $r['don_vi']);
    }

    /** @test */
    public function parse_khong_hop_le_khi_khong_theo_dinh_dang()
    {
        foreach (['1 viên x 2 lần/ngày x 5 ngày', '1*2', 'abc', '1**2*3'] as $x) {
            $this->assertFalse(LieuDungParser::parse($x)['hop_le'], "phai sai: $x");
        }
    }

    /** @test */
    public function parse_rong_va_null_tra_khong_hop_le()
    {
        $this->assertFalse(LieuDungParser::parse('')['hop_le']);
        $this->assertFalse(LieuDungParser::parse(null)['hop_le']);
        $this->assertEquals(0.0, LieuDungParser::parse(null)['tong_luong']);
    }
}
```

- [ ] **Step 2: Chạy test — FAIL**

Run: `vendor/bin/phpunit tests/Unit/Xml3176/Support/LieuDungParserTest.php`
Expected: FAIL — `Class 'App\Services\Xml3176\Support\LieuDungParser' not found`

- [ ] **Step 3: Viết implementation**

Tạo `app/Services/Xml3176/Support/LieuDungParser.php`:

```php
<?php

namespace App\Services\Xml3176\Support;

/**
 * Parse chuỗi liều dùng định dạng 130: "sl_lan * lan_ngay * so_ngay [đơn vị/ngày]".
 */
class LieuDungParser
{
    public static function parse($lieu): array
    {
        $rong = ['hop_le' => false, 'sl_lan' => 0.0, 'lan_ngay' => 0.0, 'so_ngay' => 0, 'don_vi' => '', 'tong_luong' => 0.0];

        if ($lieu === null) {
            return $rong;
        }
        $s = trim((string) $lieu);
        if ($s === '') {
            return $rong;
        }

        // Ba số ngăn bởi '*', tuỳ chọn phần [đơn vị] ở cuối.
        $re = '/^\s*(\d+(?:[.,]\d+)?)\s*\*\s*(\d+(?:[.,]\d+)?)\s*\*\s*(\d+)\s*(?:\[([^\]]*)\])?\s*$/u';
        if (!preg_match($re, $s, $m)) {
            return $rong;
        }

        $slLan   = (float) str_replace(',', '.', $m[1]);
        $lanNgay = (float) str_replace(',', '.', $m[2]);
        $soNgay  = (int) $m[3];
        $donVi   = isset($m[4]) ? trim($m[4]) : '';
        // Bỏ hậu tố "/ngày" nếu có
        $donVi   = preg_replace('#\s*/\s*ng[aà]y\s*$#ui', '', $donVi);

        return [
            'hop_le'     => true,
            'sl_lan'     => $slLan,
            'lan_ngay'   => $lanNgay,
            'so_ngay'    => $soNgay,
            'don_vi'     => trim($donVi),
            'tong_luong' => $slLan * $lanNgay * $soNgay,
        ];
    }
}
```

- [ ] **Step 4: Chạy test — PASS**

Run: `vendor/bin/phpunit tests/Unit/Xml3176/Support/LieuDungParserTest.php`
Expected: PASS (4 tests)

- [ ] **Step 5: Kiểm bằng đột biến**

Tạm sửa regex cho phép thiếu nhóm số thứ 3, chạy lại → ca `'1*2'` phải chuyển từ sai sang đúng (test đỏ). Hoàn tác.

- [ ] **Step 6: Commit**

```bash
git add app/Services/Xml3176/Support/LieuDungParser.php tests/Unit/Xml3176/Support/LieuDungParserTest.php
git commit -m "feat(xml3176): them LieuDungParser (parse lieu dung dinh dang 130)"
```

---

## Task 2: Helper `Xml3176DateHelper`

**Files:**
- Create: `app/Services/Xml3176/Support/Xml3176DateHelper.php`
- Test: `tests/Unit/Xml3176/Support/Xml3176DateHelperTest.php`

**Interfaces:**
- Produces: `Xml3176DateHelper::toDateTime($s): ?\DateTime`, `::datePart($s): ?string` (8 ký tự `Ymd`/null), `::diffMinutes($a,$b): ?int` (b−a phút), `::diffDays($a,$b): ?int` (b−a ngày)

- [ ] **Step 1: Viết test thất bại**

Tạo `tests/Unit/Xml3176/Support/Xml3176DateHelperTest.php`:

```php
<?php

namespace Tests\Unit\Xml3176\Support;

use App\Services\Xml3176\Support\Xml3176DateHelper;
use Tests\TestCase;

class Xml3176DateHelperTest extends TestCase
{
    /** @test */
    public function to_datetime_parse_do_rong_12_14_8()
    {
        $this->assertNotNull(Xml3176DateHelper::toDateTime('202607011530'));
        $this->assertNotNull(Xml3176DateHelper::toDateTime('20260701153059'));
        $this->assertNotNull(Xml3176DateHelper::toDateTime('20260701'));
    }

    /** @test */
    public function to_datetime_null_khi_khong_hop_le()
    {
        foreach ([null, '', '2026070', 'abcdefghijkl', '00000000', '202613011530'] as $x) {
            $this->assertNull(Xml3176DateHelper::toDateTime($x), 'phai null: ' . var_export($x, true));
        }
    }

    /** @test */
    public function date_part_tra_8_ky_tu()
    {
        $this->assertEquals('20260701', Xml3176DateHelper::datePart('202607011530'));
        $this->assertNull(Xml3176DateHelper::datePart('xxx'));
    }

    /** @test */
    public function diff_minutes_va_diff_days()
    {
        $this->assertEquals(2, Xml3176DateHelper::diffMinutes('202607010900', '202607010902'));
        $this->assertEquals(-2, Xml3176DateHelper::diffMinutes('202607010902', '202607010900'));
        $this->assertNull(Xml3176DateHelper::diffMinutes('xxx', '202607010900'));
        $this->assertEquals(3, Xml3176DateHelper::diffDays('202607010000', '202607040000'));
    }
}
```

- [ ] **Step 2: Chạy test — FAIL**

Run: `vendor/bin/phpunit tests/Unit/Xml3176/Support/Xml3176DateHelperTest.php`
Expected: FAIL — class not found

- [ ] **Step 3: Viết implementation**

Tạo `app/Services/Xml3176/Support/Xml3176DateHelper.php`:

```php
<?php

namespace App\Services\Xml3176\Support;

use DateTime;

/**
 * Parse/so/diff chuỗi ngày-giờ XML3176 (YmdHis=14, YmdHi=12, Ymd=8).
 */
class Xml3176DateHelper
{
    public static function toDateTime($s): ?DateTime
    {
        if ($s === null) {
            return null;
        }
        $s = trim((string) $s);
        if (!ctype_digit($s)) {
            return null;
        }
        $len = strlen($s);
        $fmt = $len === 14 ? 'YmdHis' : ($len === 12 ? 'YmdHi' : ($len === 8 ? 'Ymd' : null));
        if ($fmt === null) {
            return null;
        }
        $dt  = DateTime::createFromFormat('!' . $fmt, $s);
        $err = DateTime::getLastErrors();
        if ($dt === false || $err['warning_count'] > 0 || $err['error_count'] > 0) {
            return null;
        }
        return $dt;
    }

    public static function datePart($s): ?string
    {
        $dt = self::toDateTime($s);
        return $dt ? $dt->format('Ymd') : null;
    }

    public static function diffMinutes($a, $b): ?int
    {
        $da = self::toDateTime($a);
        $db = self::toDateTime($b);
        if ($da === null || $db === null) {
            return null;
        }
        return intdiv($db->getTimestamp() - $da->getTimestamp(), 60);
    }

    public static function diffDays($a, $b): ?int
    {
        $pa = self::datePart($a);
        $pb = self::datePart($b);
        if ($pa === null || $pb === null) {
            return null;
        }
        $da = DateTime::createFromFormat('!Ymd', $pa);
        $db = DateTime::createFromFormat('!Ymd', $pb);
        return intdiv($db->getTimestamp() - $da->getTimestamp(), 86400);
    }
}
```

Ghi chú: tiền tố `!` đặt các phần thời gian không cung cấp về epoch, tránh lẫn giờ hiện tại.

- [ ] **Step 4: Chạy test — PASS**

Run: `vendor/bin/phpunit tests/Unit/Xml3176/Support/Xml3176DateHelperTest.php`
Expected: PASS (4 tests)

- [ ] **Step 5: Commit**

```bash
git add app/Services/Xml3176/Support/Xml3176DateHelper.php tests/Unit/Xml3176/Support/Xml3176DateHelperTest.php
git commit -m "feat(xml3176): them Xml3176DateHelper (parse/diff ngay-gio)"
```

---

## Task 3: Helper `TextNormalizer`

**Files:**
- Create: `app/Services/Xml3176/Support/TextNormalizer.php`
- Test: `tests/Unit/Xml3176/Support/TextNormalizerTest.php`

**Interfaces:** Produces `TextNormalizer::chuan($s): string`.

- [ ] **Step 1: Viết test thất bại**

Tạo `tests/Unit/Xml3176/Support/TextNormalizerTest.php`:

```php
<?php

namespace Tests\Unit\Xml3176\Support;

use App\Services\Xml3176\Support\TextNormalizer;
use Tests\TestCase;

class TextNormalizerTest extends TestCase
{
    /** @test */
    public function chuan_trim_gop_khoang_trang_ha_chu_thuong()
    {
        $this->assertEquals('a b c', TextNormalizer::chuan("  A   B\tC "));
    }

    /** @test */
    public function chuan_null_tra_chuoi_rong()
    {
        $this->assertEquals('', TextNormalizer::chuan(null));
    }

    /** @test */
    public function chuan_hai_chuoi_khac_khoang_trang_hoa_thuong_thi_bang_nhau()
    {
        $this->assertEquals(
            TextNormalizer::chuan('Diễn Biến  ổn định'),
            TextNormalizer::chuan('diễn biến ổn định')
        );
    }
}
```

- [ ] **Step 2: Chạy test — FAIL**

Run: `vendor/bin/phpunit tests/Unit/Xml3176/Support/TextNormalizerTest.php`
Expected: FAIL — class not found

- [ ] **Step 3: Viết implementation**

Tạo `app/Services/Xml3176/Support/TextNormalizer.php`:

```php
<?php

namespace App\Services\Xml3176\Support;

/**
 * Chuẩn hoá văn bản để so trùng: trim, gộp khoảng trắng, hạ chữ thường (UTF-8).
 * KHÔNG bỏ dấu — giữ nguyên tiếng Việt để tránh gộp nhầm nội dung khác nhau.
 */
class TextNormalizer
{
    public static function chuan($s): string
    {
        if ($s === null) {
            return '';
        }
        $s = preg_replace('/\s+/u', ' ', trim((string) $s));
        return function_exists('mb_strtolower') ? mb_strtolower($s, 'UTF-8') : strtolower($s);
    }
}
```

- [ ] **Step 4: Chạy test — PASS**

Run: `vendor/bin/phpunit tests/Unit/Xml3176/Support/TextNormalizerTest.php`
Expected: PASS (3 tests)

- [ ] **Step 5: Commit**

```bash
git add app/Services/Xml3176/Support/TextNormalizer.php tests/Unit/Xml3176/Support/TextNormalizerTest.php
git commit -m "feat(xml3176): them TextNormalizer (chuan hoa van ban so trung)"
```

---

## Task 4: Hạ tầng test checker (fake ErrorService + trait)

**Files:**
- Create: `tests/Support/FakeXml3176ErrorService.php`
- Create: `tests/Support/Xml3176RuleTestSupport.php`

**Interfaces:**
- Produces:
  - Class `Tests\Support\FakeXml3176ErrorService extends \App\Services\Xml3176ErrorService` (override `getCriticalErrorStatus` → `true`).
  - Trait `Tests\Support\Xml3176RuleTestSupport` với: `makeChecker(string $class)` (dựng checker per-type với fake service), `invokePrivate($obj, string $method, ...$args)`, `errorCodes($collection): array`, `bootXml3176Sqlite(array $files): void`.

- [ ] **Step 1: Tạo fake ErrorService**

Tạo `tests/Support/FakeXml3176ErrorService.php`:

```php
<?php

namespace Tests\Support;

use App\Services\Xml3176ErrorService;

/**
 * ErrorService giả cho test: getCriticalErrorStatus trả true (không truy vấn catalog).
 * Xml3176ErrorService KHÔNG có constructor nên kế thừa trực tiếp là đủ.
 */
class FakeXml3176ErrorService extends Xml3176ErrorService
{
    public function getCriticalErrorStatus($errorCode)
    {
        return true;
    }
}
```

- [ ] **Step 2: Tạo trait hỗ trợ**

Tạo `tests/Support/Xml3176RuleTestSupport.php`:

```php
<?php

namespace Tests\Support;

use App\Services\CommonValidationService;
use Illuminate\Support\Facades\DB;

trait Xml3176RuleTestSupport
{
    /** Dựng checker per-type (nhận ErrorService + CommonValidationService) với fake service. */
    protected function makeChecker(string $class)
    {
        return new $class(new FakeXml3176ErrorService(), new CommonValidationService());
    }

    /** Gọi thẳng một method private của checker và trả kết quả. */
    protected function invokePrivate($obj, string $method, ...$args)
    {
        $m = new \ReflectionMethod($obj, $method);
        $m->setAccessible(true);
        return $m->invoke($obj, ...$args);
    }

    /** Lấy mảng error_code từ Collection lỗi trả về. */
    protected function errorCodes($collection): array
    {
        return collect($collection)->pluck('error_code')->all();
    }

    /**
     * Dựng SQLite in-memory cho test cần truy vấn bảng (XML5 sibling, Complete).
     * Ghi đè CHÍNH kết nối 'mysql'. TUYỆT ĐỐI không dùng RefreshDatabase.
     */
    protected function bootXml3176Sqlite(array $files): void
    {
        config(['database.connections.mysql' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
        ]]);
        DB::purge('mysql');
        foreach ($files as $f) {
            $this->artisan('migrate', ['--database' => 'mysql', '--path' => 'database/migrations/' . $f]);
        }
    }
}
```

- [ ] **Step 3: Smoke test trait (tạm)**

Tạo tạm `tests/Unit/Xml3176/BootSmokeTest.php`:

```php
<?php

namespace Tests\Unit\Xml3176;

use Illuminate\Support\Facades\DB;
use Tests\Support\Xml3176RuleTestSupport;
use Tests\TestCase;

class BootSmokeTest extends TestCase
{
    use Xml3176RuleTestSupport;

    /** @test */
    public function makechecker_va_boot_sqlite_chay_duoc()
    {
        $checker = $this->makeChecker(\App\Services\Xml3176Xml1Checker::class);
        $this->assertInstanceOf(\App\Services\Xml3176Xml1Checker::class, $checker);

        $this->bootXml3176Sqlite(['2026_01_09_152843_create_xml3176_xml5s_table.php']);
        $this->assertTrue(DB::connection('mysql')->getSchemaBuilder()->hasTable('xml3176_xml5s'));
    }
}
```

Run: `vendor/bin/phpunit tests/Unit/Xml3176/BootSmokeTest.php`
Expected: PASS (1 test). Nếu migration nào vấp SQLite, dừng và báo (có thể cần hand-create bảng đó).

- [ ] **Step 4: Xoá smoke test, commit**

```bash
rm tests/Unit/Xml3176/BootSmokeTest.php
git add tests/Support/FakeXml3176ErrorService.php tests/Support/Xml3176RuleTestSupport.php
git commit -m "test(xml3176): ha tang test checker (fake ErrorService + trait)"
```

---

## Task 5: XML1 — Ngày sinh > ngày vào (#140)

**Files:**
- Modify: `app/Services/Xml3176Xml1Checker.php`
- Test: `tests/Unit/Xml3176/Checker/Xml3176Xml1CheckerRuleTest.php`

**Interfaces:** Consumes `Xml3176DateHelper::datePart`, `Xml3176RuleTestSupport`.

- [ ] **Step 1: Viết test thất bại**

Tạo `tests/Unit/Xml3176/Checker/Xml3176Xml1CheckerRuleTest.php`:

```php
<?php

namespace Tests\Unit\Xml3176\Checker;

use App\Models\BHYT\Xml3176Xml1;
use App\Services\Xml3176Xml1Checker;
use Tests\Support\Xml3176RuleTestSupport;
use Tests\TestCase;

class Xml3176Xml1CheckerRuleTest extends TestCase
{
    use Xml3176RuleTestSupport;

    private function chay(array $attrs): array
    {
        $checker = $this->makeChecker(Xml3176Xml1Checker::class);
        $d = new Xml3176Xml1();
        foreach ($attrs as $k => $v) {
            $d->{$k} = $v;
        }
        return $this->errorCodes($this->invokePrivate($checker, 'checkNgaySinhVsNgayVao', $d));
    }

    /** @test */
    public function bat_loi_ngay_sinh_lon_hon_ngay_vao()
    {
        $this->assertContains('XML1_NGAY_SINH_GREATER_NGAY_VAO',
            $this->chay(['ngay_sinh' => '20000101', 'ngay_vao' => '199001010800']));
    }

    /** @test */
    public function khong_bat_khi_ngay_sinh_nho_hon()
    {
        $this->assertNotContains('XML1_NGAY_SINH_GREATER_NGAY_VAO',
            $this->chay(['ngay_sinh' => '19800101', 'ngay_vao' => '202607010800']));
    }

    /** @test */
    public function khong_bat_khi_thieu_ngay()
    {
        $this->assertNotContains('XML1_NGAY_SINH_GREATER_NGAY_VAO',
            $this->chay(['ngay_sinh' => '', 'ngay_vao' => '202607010800']));
    }
}
```

- [ ] **Step 2: Chạy test — FAIL**

Run: `vendor/bin/phpunit tests/Unit/Xml3176/Checker/Xml3176Xml1CheckerRuleTest.php`
Expected: FAIL — `ReflectionException` (method `checkNgaySinhVsNgayVao` chưa tồn tại)

- [ ] **Step 3: Thêm import + method + wiring**

Trong `app/Services/Xml3176Xml1Checker.php`, thêm vào khối `use` đầu file:

```php
use App\Services\Xml3176\Support\Xml3176DateHelper;
```

Thêm method (cạnh các method `check*` khác):

```php
    /**
     * #140 — Ngày sinh không được lớn hơn ngày vào viện.
     */
    private function checkNgaySinhVsNgayVao(Xml3176Xml1 $data): \Illuminate\Support\Collection
    {
        $errors = collect();

        $ns = Xml3176DateHelper::datePart($data->ngay_sinh);
        $nv = Xml3176DateHelper::datePart($data->ngay_vao);
        if ($ns !== null && $nv !== null && $ns > $nv) {
            $errorCode = $this->generateErrorCode('NGAY_SINH_GREATER_NGAY_VAO');
            $errors->push((object) [
                'error_code'     => $errorCode,
                'error_name'     => 'Ngày sinh lớn hơn ngày vào viện',
                'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                'description'    => 'Ngày sinh (' . strtodatetime($data->ngay_sinh) . ') lớn hơn ngày vào viện (' . strtodatetime($data->ngay_vao) . ')',
            ]);
        }

        return $errors;
    }
```

Trong `checkErrors(...)`, thêm trước `saveErrors`:

```php
        $errors = $errors->merge($this->checkNgaySinhVsNgayVao($data));
```

- [ ] **Step 4: Chạy test — PASS**

Run: `vendor/bin/phpunit tests/Unit/Xml3176/Checker/Xml3176Xml1CheckerRuleTest.php`
Expected: PASS (3 tests)

- [ ] **Step 5: Commit**

```bash
git add app/Services/Xml3176Xml1Checker.php tests/Unit/Xml3176/Checker/Xml3176Xml1CheckerRuleTest.php
git commit -m "feat(xml3176): XML1 bat loi ngay sinh > ngay vao (#140)"
```

---

## Task 6: XML2 — Họ liều dùng (#2345, #1638, #1636/887, #2394/2395)

**Files:**
- Modify: `app/Services/Xml3176Xml2Checker.php`
- Test: `tests/Unit/Xml3176/Checker/Xml3176Xml2CheckerRuleTest.php`

**Interfaces:** Consumes `LieuDungParser::parse`, `TextNormalizer::chuan`, `Xml3176RuleTestSupport`.

- [ ] **Step 1: Viết test thất bại**

Tạo `tests/Unit/Xml3176/Checker/Xml3176Xml2CheckerRuleTest.php`:

```php
<?php

namespace Tests\Unit\Xml3176\Checker;

use App\Models\BHYT\Xml3176Xml2;
use App\Services\Xml3176Xml2Checker;
use Tests\Support\Xml3176RuleTestSupport;
use Tests\TestCase;

class Xml3176Xml2CheckerRuleTest extends TestCase
{
    use Xml3176RuleTestSupport;

    private function chay(array $attrs): array
    {
        $checker = $this->makeChecker(Xml3176Xml2Checker::class);
        $d = new Xml3176Xml2();
        foreach ($attrs as $k => $v) {
            $d->{$k} = $v;
        }
        return $this->errorCodes($this->invokePrivate($checker, 'checkLieuDung', $d));
    }

    /** @test */
    public function sai_dinh_dang_130()
    {
        $this->assertContains('XML2_LIEU_DUNG_INVALID_FORMAT',
            $this->chay(['lieu_dung' => '1 viên x 2 lần', 'ten_thuoc' => 'Paracetamol']));
    }

    /** @test */
    public function ke_qua_30_ngay()
    {
        $this->assertContains('XML2_PRESCRIPTION_EXCEEDS_30_DAYS',
            $this->chay(['lieu_dung' => '1*1*45', 'so_luong' => 45]));
    }

    /** @test */
    public function tong_lieu_khac_so_luong()
    {
        $this->assertContains('XML2_LIEU_DUNG_QUANTITY_MISMATCH',
            $this->chay(['lieu_dung' => '1*2*5', 'so_luong' => 8])); // tong=10 != 8
    }

    /** @test */
    public function tong_lieu_khop_thi_khong_bao()
    {
        $this->assertNotContains('XML2_LIEU_DUNG_QUANTITY_MISMATCH',
            $this->chay(['lieu_dung' => '1*2*5', 'so_luong' => 10]));
    }

    /** @test */
    public function don_vi_lieu_khac_don_vi_thuoc()
    {
        $this->assertContains('XML2_LIEU_DUNG_UNIT_INVALID',
            $this->chay(['lieu_dung' => '1*1*3 [Ống/ngày]', 'so_luong' => 3, 'don_vi_tinh' => 'Viên']));
    }
}
```

- [ ] **Step 2: Chạy test — FAIL**

Run: `vendor/bin/phpunit tests/Unit/Xml3176/Checker/Xml3176Xml2CheckerRuleTest.php`
Expected: FAIL — `ReflectionException` (method `checkLieuDung` chưa có)

- [ ] **Step 3: Thêm import + method + wiring**

Trong `app/Services/Xml3176Xml2Checker.php`, thêm `use`:

```php
use App\Services\Xml3176\Support\LieuDungParser;
use App\Services\Xml3176\Support\TextNormalizer;
```

Thêm method:

```php
    /**
     * Họ quy tắc liều dùng (#2345 định dạng, #1638 kê>30 ngày,
     * #1636/#887 tổng liều≠SL, #2394/#2395 ĐVT liều).
     */
    private function checkLieuDung(Xml3176Xml2 $data): Collection
    {
        $errors = collect();
        if (empty($data->lieu_dung)) {
            return $errors;
        }

        $p = LieuDungParser::parse($data->lieu_dung);

        if (!$p['hop_le']) {
            $code = $this->generateErrorCode('LIEU_DUNG_INVALID_FORMAT');
            $errors->push((object) [
                'error_code'     => $code,
                'error_name'     => 'Liều dùng không đúng định dạng 130',
                'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($code),
                'description'    => 'Liều dùng "' . $data->lieu_dung . '" của thuốc ' . $data->ten_thuoc
                    . ' không đúng định dạng 130 (SL/lần * số lần/ngày * số ngày)',
            ]);
            return $errors; // parse fail → các kiểm tra dựa trên số phía sau vô nghĩa
        }

        $maxDays = (int) config('xml3176.xml2.max_prescription_days', 30);
        if ($p['so_ngay'] > $maxDays) {
            $code = $this->generateErrorCode('PRESCRIPTION_EXCEEDS_30_DAYS');
            $errors->push((object) [
                'error_code'     => $code,
                'error_name'     => 'Kê thuốc quá số ngày cho phép',
                'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($code),
                'description'    => 'Kê ' . $p['so_ngay'] . ' ngày (> ' . $maxDays . '). Thuốc: ' . $data->ten_thuoc,
            ]);
        }

        $eps = (float) config('xml3176.xml2.lieu_dung_quantity_epsilon', 0.001);
        if ($data->so_luong !== null && $data->so_luong !== ''
            && abs($p['tong_luong'] - (float) $data->so_luong) > $eps) {
            $code  = $this->generateErrorCode('LIEU_DUNG_QUANTITY_MISMATCH');
            $chieu = $p['tong_luong'] > (float) $data->so_luong ? 'cao hơn' : 'thấp hơn';
            $errors->push((object) [
                'error_code'     => $code,
                'error_name'     => 'Tổng lượng theo liều khác số lượng thanh toán',
                'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($code),
                'description'    => 'Tổng lượng theo liều (' . $p['tong_luong'] . ') ' . $chieu
                    . ' số lượng thanh toán (' . $data->so_luong . '). Thuốc: ' . $data->ten_thuoc,
            ]);
        }

        if ($p['don_vi'] !== '' && !empty($data->don_vi_tinh)
            && TextNormalizer::chuan($p['don_vi']) !== TextNormalizer::chuan($data->don_vi_tinh)) {
            $code = $this->generateErrorCode('LIEU_DUNG_UNIT_INVALID');
            $errors->push((object) [
                'error_code'     => $code,
                'error_name'     => 'Đơn vị trong liều dùng khác đơn vị tính của thuốc',
                'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($code),
                'description'    => 'Đơn vị trong liều dùng (' . $p['don_vi'] . ') khác đơn vị tính của thuốc (' . $data->don_vi_tinh . ')',
            ]);
        }

        return $errors;
    }
```

Trong `checkErrors(...)`, thêm trước `saveErrors`:

```php
        $errors = $errors->merge($this->checkLieuDung($data));
```

- [ ] **Step 4: Chạy test — PASS**

Run: `vendor/bin/phpunit tests/Unit/Xml3176/Checker/Xml3176Xml2CheckerRuleTest.php`
Expected: PASS (5 tests)

- [ ] **Step 5: Commit**

```bash
git add app/Services/Xml3176Xml2Checker.php tests/Unit/Xml3176/Checker/Xml3176Xml2CheckerRuleTest.php
git commit -m "feat(xml3176): XML2 ho quy tac lieu dung (#2345/#1638/#1636/#2394)"
```

---

## Task 7: XML3 — Bốn quy tắc thời gian/người thực hiện (#2391, #199, #2452/2453, #2486)

**Files:**
- Modify: `app/Services/Xml3176Xml3Checker.php`
- Test: `tests/Unit/Xml3176/Checker/Xml3176Xml3CheckerRuleTest.php`

**Interfaces:** Consumes `Xml3176DateHelper`, `Xml3176RuleTestSupport`. Quan hệ `Xml3176Xml1` được gán bằng `setRelation` trong test (không DB).

- [ ] **Step 1: Viết test thất bại**

Tạo `tests/Unit/Xml3176/Checker/Xml3176Xml3CheckerRuleTest.php`:

```php
<?php

namespace Tests\Unit\Xml3176\Checker;

use App\Models\BHYT\Xml3176Xml1;
use App\Models\BHYT\Xml3176Xml3;
use App\Services\Xml3176Xml3Checker;
use Tests\Support\Xml3176RuleTestSupport;
use Tests\TestCase;

class Xml3176Xml3CheckerRuleTest extends TestCase
{
    use Xml3176RuleTestSupport;

    private function chay(array $attrs, array $xml1 = null): array
    {
        $checker = $this->makeChecker(Xml3176Xml3Checker::class);
        $d = new Xml3176Xml3();
        foreach ($attrs as $k => $v) {
            $d->{$k} = $v;
        }
        if ($xml1 !== null) {
            $x1 = new Xml3176Xml1();
            foreach ($xml1 as $k => $v) {
                $x1->{$k} = $v;
            }
            $d->setRelation('Xml3176Xml1', $x1);
        }
        return $this->errorCodes($this->invokePrivate($checker, 'checkTimingAndExecutor', $d));
    }

    /** @test */
    public function tg_th_yl_trung_tg_kq()
    {
        $this->assertContains('XML3_NGAY_TH_YL_EQUALS_NGAY_KQ',
            $this->chay(['ngay_th_yl' => '202607010900', 'ngay_kq' => '202607010900']));
    }

    /** @test */
    public function ngay_kq_lon_hon_ngay_ra()
    {
        $this->assertContains('XML3_NGAY_KQ_GREATER_NGAY_RA',
            $this->chay(['ngay_kq' => '202607060800'], ['ngay_ra' => '202607050800']));
    }

    /** @test */
    public function thuc_hien_duoi_3_phut_nhom_1()
    {
        $this->assertContains('XML3_EXECUTION_TIME_UNDER_3MIN',
            $this->chay(['ma_nhom' => '1', 'ngay_th_yl' => '202607010900', 'ngay_kq' => '202607010901']));
    }

    /** @test */
    public function bac_si_vua_ra_vua_thuc_hien()
    {
        $this->assertContains('XML3_SAME_DOCTOR_ORDER_AND_EXECUTE',
            $this->chay(['ma_nhom' => '2', 'ma_bac_si' => 'BS01', 'nguoi_thuc_hien' => 'BS01']));
    }
}
```

- [ ] **Step 2: Chạy test — FAIL**

Run: `vendor/bin/phpunit tests/Unit/Xml3176/Checker/Xml3176Xml3CheckerRuleTest.php`
Expected: FAIL — `ReflectionException` (method chưa có)

- [ ] **Step 3: Thêm import + method + wiring**

Trong `app/Services/Xml3176Xml3Checker.php`, thêm `use`:

```php
use App\Services\Xml3176\Support\Xml3176DateHelper;
```

Thêm method:

```php
    /**
     * #2391 TG th YL trùng TG KQ; #199 ngày KQ > ngày ra;
     * #2452/2453 thực hiện < 3 phút (nhóm XN/TDCN); #2486 BS vừa YL vừa thực hiện.
     */
    private function checkTimingAndExecutor(Xml3176Xml3 $data): Collection
    {
        $errors = collect();
        $data->loadMissing('Xml3176Xml1');

        // #2391
        if (!empty($data->ngay_th_yl) && !empty($data->ngay_kq) && $data->ngay_th_yl === $data->ngay_kq) {
            $code = $this->generateErrorCode('NGAY_TH_YL_EQUALS_NGAY_KQ');
            $errors->push((object) [
                'error_code' => $code, 'error_name' => 'Thời gian thực hiện y lệnh trùng thời gian kết quả',
                'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($code),
                'description' => 'NGAY_TH_YL = NGAY_KQ = ' . strtodatetime($data->ngay_kq) . '. Dịch vụ: ' . $data->ten_dich_vu,
            ]);
        }

        // #199
        if (!empty($data->ngay_kq) && $data->Xml3176Xml1 && !empty($data->Xml3176Xml1->ngay_ra)) {
            $kq = Xml3176DateHelper::datePart($data->ngay_kq);
            $ra = Xml3176DateHelper::datePart($data->Xml3176Xml1->ngay_ra);
            if ($kq !== null && $ra !== null && $kq > $ra) {
                $code = $this->generateErrorCode('NGAY_KQ_GREATER_NGAY_RA');
                $errors->push((object) [
                    'error_code' => $code, 'error_name' => 'Ngày kết quả dịch vụ lớn hơn ngày ra viện',
                    'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($code),
                    'description' => 'Ngày KQ (' . strtodatetime($data->ngay_kq) . ') > ngày ra (' . strtodatetime($data->Xml3176Xml1->ngay_ra) . '). Dịch vụ: ' . $data->ten_dich_vu,
                ]);
            }
        }

        // #2452/2453
        $groups = array_map('intval', (array) config('xml3176.xml3.execution_time_check_groups', [1, 3]));
        $minMin = (int) config('xml3176.xml3.execution_min_minutes', 3);
        if (in_array((int) $data->ma_nhom, $groups, true)) {
            $d = Xml3176DateHelper::diffMinutes($data->ngay_th_yl, $data->ngay_kq);
            if ($d !== null && $d < $minMin) {
                $code = $this->generateErrorCode('EXECUTION_TIME_UNDER_3MIN');
                $errors->push((object) [
                    'error_code' => $code, 'error_name' => 'Thời gian thực hiện nhỏ hơn quy định',
                    'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($code),
                    'description' => 'Nhóm ' . $data->ma_nhom . ': thực hiện ' . $d . ' phút (< ' . $minMin . '). Dịch vụ: ' . $data->ten_dich_vu,
                ]);
            }
        }

        // #2486
        $sameGroups = array_map('intval', (array) config('xml3176.xml3.same_doctor_check_groups', [1, 2, 3]));
        if (in_array((int) $data->ma_nhom, $sameGroups, true)
            && !empty($data->ma_bac_si) && !empty($data->nguoi_thuc_hien)
            && $data->ma_bac_si === $data->nguoi_thuc_hien) {
            $code = $this->generateErrorCode('SAME_DOCTOR_ORDER_AND_EXECUTE');
            $errors->push((object) [
                'error_code' => $code, 'error_name' => 'Bác sĩ vừa ra y lệnh vừa thực hiện',
                'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($code),
                'description' => 'Mã bác sĩ = người thực hiện = ' . $data->ma_bac_si . '. Dịch vụ: ' . $data->ten_dich_vu,
            ]);
        }

        return $errors;
    }
```

Trong `checkErrors(...)`, thêm trước `saveErrors`:

```php
        $errors = $errors->merge($this->checkTimingAndExecutor($data));
```

- [ ] **Step 4: Chạy test — PASS**

Run: `vendor/bin/phpunit tests/Unit/Xml3176/Checker/Xml3176Xml3CheckerRuleTest.php`
Expected: PASS (4 tests)

- [ ] **Step 5: Commit**

```bash
git add app/Services/Xml3176Xml3Checker.php tests/Unit/Xml3176/Checker/Xml3176Xml3CheckerRuleTest.php
git commit -m "feat(xml3176): XML3 quy tac thoi gian/nguoi thuc hien (#2391/#199/#2452/#2486)"
```

---

## Task 8: XML4 — Mã/tên chỉ số trống & XN thiếu giá trị (#1274, #1276, #2521)

**Files:**
- Modify: `app/Services/Xml3176Xml4Checker.php`
- Test: `tests/Unit/Xml3176/Checker/Xml3176Xml4CheckerRuleTest.php`

**Heuristic (chốt Open Question §3):**
- Dòng có **giá trị đo** (`gia_tri` hoặc `don_vi_do` khác rỗng) mà thiếu `ma_chi_so`/`ten_chi_so` → #1274/#1276.
- Dòng là **chỉ số** (`ma_chi_so` hoặc `ten_chi_so` khác rỗng) mà **không có bất kỳ giá trị/kết quả nào** (`gia_tri`, `mo_ta`, `ket_luan` đều rỗng) → #2521.

- [ ] **Step 1: Viết test thất bại**

Tạo `tests/Unit/Xml3176/Checker/Xml3176Xml4CheckerRuleTest.php`:

```php
<?php

namespace Tests\Unit\Xml3176\Checker;

use App\Models\BHYT\Xml3176Xml4;
use App\Services\Xml3176Xml4Checker;
use Tests\Support\Xml3176RuleTestSupport;
use Tests\TestCase;

class Xml3176Xml4CheckerRuleTest extends TestCase
{
    use Xml3176RuleTestSupport;

    private function chay(array $attrs): array
    {
        $checker = $this->makeChecker(Xml3176Xml4Checker::class);
        $d = new Xml3176Xml4();
        foreach ($attrs as $k => $v) {
            $d->{$k} = $v;
        }
        return $this->errorCodes($this->invokePrivate($checker, 'checkChiSo', $d));
    }

    /** @test */
    public function ma_chi_so_trong_khi_co_gia_tri()
    {
        $this->assertContains('XML4_MA_CHI_SO_EMPTY',
            $this->chay(['gia_tri' => '5.2', 'ma_chi_so' => '', 'ten_chi_so' => 'Glucose']));
    }

    /** @test */
    public function ten_chi_so_trong_khi_co_don_vi_do()
    {
        $this->assertContains('XML4_TEN_CHI_SO_EMPTY',
            $this->chay(['don_vi_do' => 'mmol/L', 'ma_chi_so' => 'GLU', 'ten_chi_so' => '']));
    }

    /** @test */
    public function xn_khong_gia_tri_khong_ket_qua()
    {
        $this->assertContains('XML4_XN_MISSING_VALUE_RESULT',
            $this->chay(['ma_chi_so' => 'GLU', 'ten_chi_so' => 'Glucose', 'gia_tri' => '', 'mo_ta' => '', 'ket_luan' => '']));
    }

    /** @test */
    public function dong_hinh_anh_chi_co_mo_ta_khong_bao_thieu_chi_so()
    {
        $codes = $this->chay(['mo_ta' => 'Bình thường', 'ket_luan' => 'Không bất thường']);
        $this->assertNotContains('XML4_MA_CHI_SO_EMPTY', $codes);
        $this->assertNotContains('XML4_XN_MISSING_VALUE_RESULT', $codes);
    }
}
```

- [ ] **Step 2: Chạy test — FAIL**

Run: `vendor/bin/phpunit tests/Unit/Xml3176/Checker/Xml3176Xml4CheckerRuleTest.php`
Expected: FAIL — `ReflectionException`

- [ ] **Step 3: Thêm method + wiring**

Trong `app/Services/Xml3176Xml4Checker.php` thêm method:

```php
    /**
     * #1274 mã chỉ số trống, #1276 tên chỉ số trống, #2521 XN không nhập giá trị+kết quả.
     */
    private function checkChiSo(Xml3176Xml4 $data): Collection
    {
        $errors = collect();

        $coGiaTriDo = (trim((string) $data->gia_tri) !== '') || (trim((string) $data->don_vi_do) !== '');
        if ($coGiaTriDo && empty($data->ma_chi_so)) {
            $code = $this->generateErrorCode('MA_CHI_SO_EMPTY');
            $errors->push((object) [
                'error_code' => $code, 'error_name' => 'Mã chỉ số để trống',
                'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($code),
                'description' => 'Dòng có giá trị/đơn vị đo nhưng thiếu mã chỉ số. Dịch vụ: ' . $data->ma_dich_vu,
            ]);
        }
        if ($coGiaTriDo && empty($data->ten_chi_so)) {
            $code = $this->generateErrorCode('TEN_CHI_SO_EMPTY');
            $errors->push((object) [
                'error_code' => $code, 'error_name' => 'Tên chỉ số để trống',
                'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($code),
                'description' => 'Dòng có giá trị/đơn vị đo nhưng thiếu tên chỉ số. Dịch vụ: ' . $data->ma_dich_vu,
            ]);
        }

        $laChiSo = (trim((string) $data->ma_chi_so) !== '') || (trim((string) $data->ten_chi_so) !== '');
        $rongHet = empty($data->gia_tri) && empty($data->mo_ta) && empty($data->ket_luan);
        if ($laChiSo && $rongHet) {
            $code = $this->generateErrorCode('XN_MISSING_VALUE_RESULT');
            $errors->push((object) [
                'error_code' => $code, 'error_name' => 'Xét nghiệm không nhập giá trị và kết quả',
                'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($code),
                'description' => 'Chỉ số ' . ($data->ten_chi_so ?: $data->ma_chi_so) . ' không có giá trị/mô tả/kết luận. Dịch vụ: ' . $data->ma_dich_vu,
            ]);
        }

        return $errors;
    }
```

Trong `checkErrors(...)`, thêm trước `saveErrors`:

```php
        $errors = $errors->merge($this->checkChiSo($data));
```

- [ ] **Step 4: Chạy test — PASS**

Run: `vendor/bin/phpunit tests/Unit/Xml3176/Checker/Xml3176Xml4CheckerRuleTest.php`
Expected: PASS (4 tests)

- [ ] **Step 5: Commit**

```bash
git add app/Services/Xml3176Xml4Checker.php tests/Unit/Xml3176/Checker/Xml3176Xml4CheckerRuleTest.php
git commit -m "feat(xml3176): XML4 ma/ten chi so trong & XN thieu gia tri (#1274/#1276/#2521)"
```

---

## Task 9: XML5 — Diễn biến điều trị trùng nhau (#436) [cần SQLite]

**Files:**
- Modify: `app/Services/Xml3176Xml5Checker.php`
- Test: `tests/Unit/Xml3176/Checker/Xml3176Xml5CheckerRuleTest.php`

**Interfaces:** Consumes `TextNormalizer::chuan`, `Xml3176RuleTestSupport`. Method truy vấn sibling → test dùng SQLite (chỉ bảng `xml3176_xml5s`).

- [ ] **Step 1: Viết test thất bại**

Tạo `tests/Unit/Xml3176/Checker/Xml3176Xml5CheckerRuleTest.php`:

```php
<?php

namespace Tests\Unit\Xml3176\Checker;

use App\Models\BHYT\Xml3176Xml5;
use App\Services\Xml3176Xml5Checker;
use Tests\Support\Xml3176RuleTestSupport;
use Tests\TestCase;

class Xml3176Xml5CheckerRuleTest extends TestCase
{
    use Xml3176RuleTestSupport;

    protected function setUp()
    {
        parent::setUp();
        $this->bootXml3176Sqlite(['2026_01_09_152843_create_xml3176_xml5s_table.php']);
    }

    /** @test */
    public function bat_dien_bien_trung_o_dong_stt_lon_hon()
    {
        Xml3176Xml5::create(['ma_lk' => 'L', 'stt' => 1, 'dien_bien_ls' => 'Bệnh ổn định']);
        $row2 = Xml3176Xml5::create(['ma_lk' => 'L', 'stt' => 2, 'dien_bien_ls' => 'bệnh  ổn định']);

        $checker = $this->makeChecker(Xml3176Xml5Checker::class);
        $codes = $this->errorCodes($this->invokePrivate($checker, 'checkDienBienDuplicate', $row2));

        $this->assertContains('XML5_DIEN_BIEN_DUPLICATE', $codes);
    }

    /** @test */
    public function khong_bat_khi_khac_nhau()
    {
        Xml3176Xml5::create(['ma_lk' => 'M', 'stt' => 1, 'dien_bien_ls' => 'Sốt cao']);
        $row2 = Xml3176Xml5::create(['ma_lk' => 'M', 'stt' => 2, 'dien_bien_ls' => 'Hết sốt']);

        $checker = $this->makeChecker(Xml3176Xml5Checker::class);
        $codes = $this->errorCodes($this->invokePrivate($checker, 'checkDienBienDuplicate', $row2));

        $this->assertNotContains('XML5_DIEN_BIEN_DUPLICATE', $codes);
    }
}
```

- [ ] **Step 2: Chạy test — FAIL**

Run: `vendor/bin/phpunit tests/Unit/Xml3176/Checker/Xml3176Xml5CheckerRuleTest.php`
Expected: FAIL — `ReflectionException`

- [ ] **Step 3: Thêm import + method + wiring**

Trong `app/Services/Xml3176Xml5Checker.php` thêm `use` (nếu `Xml3176Xml5` đã import thì bỏ dòng trùng):

```php
use App\Models\BHYT\Xml3176Xml5;
use App\Services\Xml3176\Support\TextNormalizer;
```

Thêm method:

```php
    /**
     * #436 — Diễn biến điều trị trùng nhau trong cùng hồ sơ.
     * Chỉ báo ở dòng có stt LỚN HƠN để lỗi một chiều, không nhân đôi.
     */
    private function checkDienBienDuplicate(Xml3176Xml5 $data): Collection
    {
        $errors = collect();
        if (empty($data->dien_bien_ls)) {
            return $errors;
        }
        $chuan = TextNormalizer::chuan($data->dien_bien_ls);

        $truoc = Xml3176Xml5::where('ma_lk', $data->ma_lk)
            ->where('id', '!=', $data->id)
            ->where('stt', '<', $data->stt)
            ->get();

        foreach ($truoc as $sib) {
            if (TextNormalizer::chuan($sib->dien_bien_ls) === $chuan) {
                $code = $this->generateErrorCode('DIEN_BIEN_DUPLICATE');
                $errors->push((object) [
                    'error_code' => $code, 'error_name' => 'Diễn biến điều trị trùng nhau',
                    'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($code),
                    'description' => 'Diễn biến lâm sàng trùng với dòng STT ' . $sib->stt . ': "' . mb_substr((string) $data->dien_bien_ls, 0, 100) . '"',
                ]);
                break;
            }
        }

        return $errors;
    }
```

Trong `checkErrors(...)`, thêm trước `saveErrors`:

```php
        $errors = $errors->merge($this->checkDienBienDuplicate($data));
```

- [ ] **Step 4: Chạy test — PASS**

Run: `vendor/bin/phpunit tests/Unit/Xml3176/Checker/Xml3176Xml5CheckerRuleTest.php`
Expected: PASS (2 tests)

- [ ] **Step 5: Commit**

```bash
git add app/Services/Xml3176Xml5Checker.php tests/Unit/Xml3176/Checker/Xml3176Xml5CheckerRuleTest.php
git commit -m "feat(xml3176): XML5 bat dien bien dieu tri trung nhau (#436)"
```

---

## Task 10: XML7 — Số ngày nghỉ & ngoại trú (#2163, #2313, #2314)

**Files:**
- Modify: `app/Services/Xml3176Xml7Checker.php`
- Test: `tests/Unit/Xml3176/Checker/Xml3176Xml7CheckerRuleTest.php`

**Chốt Open Question §4:** `so_ngay_nghi == diffDays(tungay, denngay) + 1` (inclusive).

- [ ] **Step 1: Viết test thất bại**

Tạo `tests/Unit/Xml3176/Checker/Xml3176Xml7CheckerRuleTest.php`:

```php
<?php

namespace Tests\Unit\Xml3176\Checker;

use App\Models\BHYT\Xml3176Xml7;
use App\Services\Xml3176Xml7Checker;
use Tests\Support\Xml3176RuleTestSupport;
use Tests\TestCase;

class Xml3176Xml7CheckerRuleTest extends TestCase
{
    use Xml3176RuleTestSupport;

    private function chay(array $attrs): array
    {
        $checker = $this->makeChecker(Xml3176Xml7Checker::class);
        $d = new Xml3176Xml7();
        foreach ($attrs as $k => $v) {
            $d->{$k} = $v;
        }
        return $this->errorCodes($this->invokePrivate($checker, 'checkNghiNgoaiTru', $d));
    }

    /** @test */
    public function so_ngay_nghi_sai()
    {
        // 01/07..05/07 = 5 ngày (inclusive), khai 3 → sai
        $this->assertContains('XML7_SO_NGAY_NGHI_MISMATCH',
            $this->chay(['so_ngay_nghi' => 3, 'ngoaitru_tungay' => '20260701', 'ngoaitru_denngay' => '20260705', 'ngay_ra' => '20260701']));
    }

    /** @test */
    public function so_ngay_nghi_dung_thi_khong_bao()
    {
        $this->assertNotContains('XML7_SO_NGAY_NGHI_MISMATCH',
            $this->chay(['so_ngay_nghi' => 5, 'ngoaitru_tungay' => '20260701', 'ngoaitru_denngay' => '20260705', 'ngay_ra' => '20260701']));
    }

    /** @test */
    public function ngoai_tru_tu_ngay_truoc_ngay_ra()
    {
        $this->assertContains('XML7_NGOAITRU_TUNGAY_BEFORE_NGAY_RA',
            $this->chay(['so_ngay_nghi' => 1, 'ngoaitru_tungay' => '20260630', 'ngoaitru_denngay' => '20260630', 'ngay_ra' => '20260701']));
    }
}
```

- [ ] **Step 2: Chạy test — FAIL**

Run: `vendor/bin/phpunit tests/Unit/Xml3176/Checker/Xml3176Xml7CheckerRuleTest.php`
Expected: FAIL — `ReflectionException`

- [ ] **Step 3: Thêm import + method + wiring**

Trong `app/Services/Xml3176Xml7Checker.php` thêm `use`:

```php
use App\Services\Xml3176\Support\Xml3176DateHelper;
```

Thêm method:

```php
    /**
     * #2163 số ngày nghỉ ≠ (đến − từ + 1); #2313/#2314 ngoại trú từ/đến ngày < ngày ra.
     */
    private function checkNghiNgoaiTru(Xml3176Xml7 $data): Collection
    {
        $errors = collect();

        if ($data->so_ngay_nghi !== null && $data->so_ngay_nghi !== ''
            && !empty($data->ngoaitru_tungay) && !empty($data->ngoaitru_denngay)) {
            $days = Xml3176DateHelper::diffDays($data->ngoaitru_tungay, $data->ngoaitru_denngay);
            if ($days !== null && (int) $data->so_ngay_nghi !== $days + 1) {
                $code = $this->generateErrorCode('SO_NGAY_NGHI_MISMATCH');
                $errors->push((object) [
                    'error_code' => $code, 'error_name' => 'Số ngày nghỉ không đúng (đến − từ)',
                    'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($code),
                    'description' => 'Khai ' . $data->so_ngay_nghi . ' ngày, nhưng từ ' . strtodatetime($data->ngoaitru_tungay) . ' đến ' . strtodatetime($data->ngoaitru_denngay) . ' là ' . ($days + 1) . ' ngày',
                ]);
            }
        }

        $ra = Xml3176DateHelper::datePart($data->ngay_ra);
        if ($ra !== null) {
            $tu = Xml3176DateHelper::datePart($data->ngoaitru_tungay);
            if ($tu !== null && $tu < $ra) {
                $code = $this->generateErrorCode('NGOAITRU_TUNGAY_BEFORE_NGAY_RA');
                $errors->push((object) [
                    'error_code' => $code, 'error_name' => 'Bắt đầu nghỉ ngoại trú trước ngày ra viện',
                    'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($code),
                    'description' => 'Từ ngày ngoại trú (' . strtodatetime($data->ngoaitru_tungay) . ') < ngày ra (' . strtodatetime($data->ngay_ra) . ')',
                ]);
            }
            $den = Xml3176DateHelper::datePart($data->ngoaitru_denngay);
            if ($den !== null && $den < $ra) {
                $code = $this->generateErrorCode('NGOAITRU_DENNGAY_BEFORE_NGAY_RA');
                $errors->push((object) [
                    'error_code' => $code, 'error_name' => 'Đến ngày nghỉ ngoại trú trước ngày ra viện',
                    'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($code),
                    'description' => 'Đến ngày ngoại trú (' . strtodatetime($data->ngoaitru_denngay) . ') < ngày ra (' . strtodatetime($data->ngay_ra) . ')',
                ]);
            }
        }

        return $errors;
    }
```

Trong `checkErrors(...)`, thêm trước `saveErrors`:

```php
        $errors = $errors->merge($this->checkNghiNgoaiTru($data));
```

- [ ] **Step 4: Chạy test — PASS**

Run: `vendor/bin/phpunit tests/Unit/Xml3176/Checker/Xml3176Xml7CheckerRuleTest.php`
Expected: PASS (3 tests)

- [ ] **Step 5: Commit**

```bash
git add app/Services/Xml3176Xml7Checker.php tests/Unit/Xml3176/Checker/Xml3176Xml7CheckerRuleTest.php
git commit -m "feat(xml3176): XML7 so ngay nghi & ngoai tru (#2163/#2313/#2314)"
```

---

## Task 11: XML8 — Tóm tắt kết quả quá ngắn (#2342)

**Files:**
- Modify: `app/Services/Xml3176Xml8Checker.php`
- Test: `tests/Unit/Xml3176/Checker/Xml3176Xml8CheckerRuleTest.php`

**Chốt Open Question §2:** ngưỡng `config('xml3176.xml8.tomtat_kq_min_length', 20)`.

- [ ] **Step 1: Viết test thất bại**

Tạo `tests/Unit/Xml3176/Checker/Xml3176Xml8CheckerRuleTest.php`:

```php
<?php

namespace Tests\Unit\Xml3176\Checker;

use App\Models\BHYT\Xml3176Xml8;
use App\Services\Xml3176Xml8Checker;
use Tests\Support\Xml3176RuleTestSupport;
use Tests\TestCase;

class Xml3176Xml8CheckerRuleTest extends TestCase
{
    use Xml3176RuleTestSupport;

    private function chay(string $tomtat): array
    {
        $checker = $this->makeChecker(Xml3176Xml8Checker::class);
        $d = new Xml3176Xml8();
        $d->tomtat_kq = $tomtat;
        return $this->errorCodes($this->invokePrivate($checker, 'checkTomTatQuaNgan', $d));
    }

    /** @test */
    public function tom_tat_qua_ngan()
    {
        $this->assertContains('XML8_TOMTAT_KQ_TOO_SHORT', $this->chay('Ổn'));
    }

    /** @test */
    public function tom_tat_du_dai_thi_khong_bao()
    {
        $this->assertNotContains('XML8_TOMTAT_KQ_TOO_SHORT', $this->chay(str_repeat('Bệnh nhân ổn định. ', 3)));
    }
}
```

- [ ] **Step 2: Chạy test — FAIL**

Run: `vendor/bin/phpunit tests/Unit/Xml3176/Checker/Xml3176Xml8CheckerRuleTest.php`
Expected: FAIL — `ReflectionException`

- [ ] **Step 3: Thêm method + wiring**

Trong `app/Services/Xml3176Xml8Checker.php` thêm method:

```php
    /**
     * #2342 — Tóm tắt kết quả quá ngắn.
     */
    private function checkTomTatQuaNgan(Xml3176Xml8 $data): Collection
    {
        $errors = collect();
        $min = (int) config('xml3176.xml8.tomtat_kq_min_length', 20);
        if (!empty($data->tomtat_kq) && mb_strlen(trim($data->tomtat_kq)) < $min) {
            $code = $this->generateErrorCode('TOMTAT_KQ_TOO_SHORT');
            $errors->push((object) [
                'error_code' => $code, 'error_name' => 'Tóm tắt kết quả quá ngắn',
                'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($code),
                'description' => 'Tóm tắt kết quả chỉ ' . mb_strlen(trim($data->tomtat_kq)) . ' ký tự (< ' . $min . '): "' . trim($data->tomtat_kq) . '"',
            ]);
        }
        return $errors;
    }
```

Trong `checkErrors(...)`, thêm trước `saveErrors`:

```php
        $errors = $errors->merge($this->checkTomTatQuaNgan($data));
```

- [ ] **Step 4: Chạy test — PASS**

Run: `vendor/bin/phpunit tests/Unit/Xml3176/Checker/Xml3176Xml8CheckerRuleTest.php`
Expected: PASS (2 tests)

- [ ] **Step 5: Commit**

```bash
git add app/Services/Xml3176Xml8Checker.php tests/Unit/Xml3176/Checker/Xml3176Xml8CheckerRuleTest.php
git commit -m "feat(xml3176): XML8 tom tat ket qua qua ngan (#2342)"
```

---

## Task 12: Complete — 3 quy tắc tổng hợp (#891, #2098, #2498) [cần SQLite]

**Files:**
- Modify: `app/Services/Xml3176CompleteChecker.php`
- Test: `tests/Unit/Xml3176/Checker/Xml3176CompleteCheckerRuleTest.php`

**Interfaces:** Consumes `Xml3176DateHelper`. Models `Xml3176Xml3/Xml4/Xml13/Xml14` đã import sẵn ở đầu file Complete. Complete constructor chỉ nhận `Xml3176ErrorService` → test dựng bằng `new Xml3176CompleteChecker(new FakeXml3176ErrorService())` (không qua `makeChecker`).

**Chốt Open Question §5:** ghép XML3↔XML4 theo `(ma_lk, ma_dich_vu)`, so **phần ngày** của `ngay_kq`.

- [ ] **Step 1: Viết test thất bại**

Tạo `tests/Unit/Xml3176/Checker/Xml3176CompleteCheckerRuleTest.php`:

```php
<?php

namespace Tests\Unit\Xml3176\Checker;

use App\Models\BHYT\Xml3176Xml1;
use App\Models\BHYT\Xml3176Xml3;
use App\Models\BHYT\Xml3176Xml4;
use App\Services\Xml3176CompleteChecker;
use Tests\Support\FakeXml3176ErrorService;
use Tests\Support\Xml3176RuleTestSupport;
use Tests\TestCase;

class Xml3176CompleteCheckerRuleTest extends TestCase
{
    use Xml3176RuleTestSupport;

    protected function setUp()
    {
        parent::setUp();
        $this->bootXml3176Sqlite([
            '2026_01_09_152817_create_xml3176_xml1s_table.php',
            '2026_01_09_152832_create_xml3176_xml3s_table.php',
            '2026_01_09_152838_create_xml3176_xml4s_table.php',
            '2026_01_09_152930_create_xml3176_xml13s_table.php',
            '2026_01_09_152936_create_xml3176_xml14s_table.php',
        ]);
    }

    private function checker(): Xml3176CompleteChecker
    {
        return new Xml3176CompleteChecker(new FakeXml3176ErrorService());
    }

    /** @test */
    public function thieu_chuyen_tuyen_va_hen_kham_lai()
    {
        $x1 = Xml3176Xml1::create(['ma_lk' => 'A', 'stt' => 1, 'ma_noi_di' => '01001']);
        $codes = $this->errorCodes($this->invokePrivate($this->checker(), 'checkMissingTransferOrAppointment', $x1));
        $this->assertContains('XMLComplete_MISSING_TRANSFER_OR_APPOINTMENT', $codes);
    }

    /** @test */
    public function ngay_kq_xml4_khac_xml3()
    {
        Xml3176Xml1::create(['ma_lk' => 'B', 'stt' => 1]);
        Xml3176Xml3::create(['ma_lk' => 'B', 'stt' => 1, 'ma_dich_vu' => 'DV1', 'ngay_kq' => '202607010900']);
        Xml3176Xml4::create(['ma_lk' => 'B', 'stt' => 1, 'ma_dich_vu' => 'DV1', 'ngay_kq' => '202607020900']);
        $codes = $this->errorCodes($this->invokePrivate($this->checker(), 'checkXml4NgayKqMismatchXml3', 'B'));
        $this->assertContains('XMLComplete_XML4_NGAY_KQ_MISMATCH_XML3', $codes);
    }

    /** @test */
    public function pt_lan_2_thanh_toan_100()
    {
        Xml3176Xml1::create(['ma_lk' => 'C', 'stt' => 1]);
        Xml3176Xml3::create(['ma_lk' => 'C', 'stt' => 1, 'ma_dich_vu' => 'PT', 'ma_pttt' => 'PT01', 'ngay_yl' => '202607010800', 'tyle_tt_dv' => '100']);
        Xml3176Xml3::create(['ma_lk' => 'C', 'stt' => 2, 'ma_dich_vu' => 'PT', 'ma_pttt' => 'PT02', 'ngay_yl' => '202607011000', 'tyle_tt_dv' => '100']);
        $codes = $this->errorCodes($this->invokePrivate($this->checker(), 'checkSecondSurgeryFullPayment', 'C'));
        $this->assertContains('XMLComplete_SECOND_SURGERY_FULL_PAYMENT', $codes);
    }
}
```

- [ ] **Step 2: Chạy test — FAIL**

Run: `vendor/bin/phpunit tests/Unit/Xml3176/Checker/Xml3176CompleteCheckerRuleTest.php`
Expected: FAIL — `ReflectionException`

- [ ] **Step 3: Thêm import + 3 method + wiring**

Trong `app/Services/Xml3176CompleteChecker.php` thêm `use`:

```php
use App\Services\Xml3176\Support\Xml3176DateHelper;
```

Thêm 3 method:

```php
    /**
     * #2498 — Có mã nơi đi nhưng thiếu CẢ giấy chuyển tuyến (XML13) LẪN giấy hẹn khám lại (XML14).
     */
    private function checkMissingTransferOrAppointment(Xml3176Xml1 $data): Collection
    {
        $errors = collect();
        if (empty($data->ma_noi_di)) {
            return $errors;
        }
        $hasXml13 = Xml3176Xml13::where('ma_lk', $data->ma_lk)->exists();
        $hasXml14 = Xml3176Xml14::where('ma_lk', $data->ma_lk)->exists();
        if (!$hasXml13 && !$hasXml14) {
            $code = $this->generateErrorCode('MISSING_TRANSFER_OR_APPOINTMENT');
            $errors->push((object) [
                'error_code' => $code, 'error_name' => 'Có nơi đi nhưng thiếu giấy chuyển tuyến hoặc hẹn khám lại',
                'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($code),
                'description' => 'Mã nơi đi ' . $data->ma_noi_di . ' nhưng không có XML13 (chuyển tuyến) lẫn XML14 (hẹn khám lại)',
            ]);
        }
        return $errors;
    }

    /**
     * #2098 — Ngày KQ tại XML4 không khớp ngày KQ tại XML3 (cùng ma_dich_vu).
     */
    private function checkXml4NgayKqMismatchXml3($ma_lk): Collection
    {
        $errors = collect();

        $xml3 = Xml3176Xml3::where('ma_lk', $ma_lk)
            ->whereNotNull('ngay_kq')->where('ngay_kq', '<>', '')->get()->groupBy('ma_dich_vu');
        $xml4 = Xml3176Xml4::where('ma_lk', $ma_lk)
            ->whereNotNull('ngay_kq')->where('ngay_kq', '<>', '')->get();

        foreach ($xml4 as $r4) {
            if (!isset($xml3[$r4->ma_dich_vu])) {
                continue;
            }
            $days3 = $xml3[$r4->ma_dich_vu]
                ->map(function ($r) { return Xml3176DateHelper::datePart($r->ngay_kq); })
                ->filter()->unique();
            $day4 = Xml3176DateHelper::datePart($r4->ngay_kq);
            if ($day4 !== null && $days3->isNotEmpty() && !$days3->contains($day4)) {
                $code = $this->generateErrorCode('XML4_NGAY_KQ_MISMATCH_XML3');
                $errors->push((object) [
                    'error_code' => $code, 'error_name' => 'Ngày KQ XML4 khác ngày KQ XML3',
                    'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($code),
                    'description' => 'Dịch vụ ' . $r4->ma_dich_vu . ': ngày KQ XML4 (' . strtodatetime($r4->ngay_kq) . ') khác ngày KQ XML3',
                ]);
            }
        }
        return $errors;
    }

    /**
     * #891 — PTTT lần 2 trở đi trong cùng ngày có tỷ lệ thanh toán = 100% (CV824/QĐ3176).
     */
    private function checkSecondSurgeryFullPayment($ma_lk): Collection
    {
        $errors = collect();
        $rate = (string) config('xml3176.xml3.surgery_full_payment_rate', '100');

        $rows = Xml3176Xml3::where('ma_lk', $ma_lk)
            ->whereNotNull('ma_pttt')->where('ma_pttt', '<>', '')
            ->orderBy('ngay_yl')->get();

        $byDay = [];
        foreach ($rows as $r) {
            $day = Xml3176DateHelper::datePart($r->ngay_yl);
            if ($day === null) {
                continue;
            }
            $byDay[$day][] = $r;
        }

        foreach ($byDay as $day => $list) {
            if (count($list) < 2) {
                continue;
            }
            for ($i = 1; $i < count($list); $i++) {
                if ((string) $list[$i]->tyle_tt_dv === $rate) {
                    $code = $this->generateErrorCode('SECOND_SURGERY_FULL_PAYMENT');
                    $errors->push((object) [
                        'error_code' => $code, 'error_name' => 'PTTT lần 2 trong ngày thanh toán 100%',
                        'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($code),
                        'description' => 'PTTT lần ' . ($i + 1) . ' ngày ' . $day . ' (dịch vụ ' . $list[$i]->ma_dich_vu . ') thanh toán ' . $rate . '%',
                    ]);
                }
            }
        }
        return $errors;
    }
```

Trong `checkErrors($ma_lk)`, bên trong `if ($data) { ... }`, thêm trước `saveErrors`:

```php
            $errors = $errors->merge($this->checkMissingTransferOrAppointment($data));
            $errors = $errors->merge($this->checkXml4NgayKqMismatchXml3($ma_lk));
            $errors = $errors->merge($this->checkSecondSurgeryFullPayment($ma_lk));
```

- [ ] **Step 4: Chạy test — PASS**

Run: `vendor/bin/phpunit tests/Unit/Xml3176/Checker/Xml3176CompleteCheckerRuleTest.php`
Expected: PASS (3 tests)

- [ ] **Step 5: Commit**

```bash
git add app/Services/Xml3176CompleteChecker.php tests/Unit/Xml3176/Checker/Xml3176CompleteCheckerRuleTest.php
git commit -m "feat(xml3176): Complete PT lan 2 100%, KQ XML4!=XML3, thieu chuyen tuyen/hen (#891/#2098/#2498)"
```

---

## Task 13: Cấu hình + seeder danh mục lỗi + tắt rule cũ trùng

**Files:**
- Modify: `config/xml3176.php`
- Create: `database/seeds/Xml3176ErrorCatalogNhomASeeder.php`

- [ ] **Step 1: Thêm khoá cấu hình**

Trong `config/xml3176.php`, **bổ sung** các khoá con sau vào mảng `xml2`/`xml3` nếu đã tồn tại (giữ nguyên khoá cũ), hoặc tạo mảng mới nếu chưa; thêm mảng `xml8` nếu chưa có:

```php
    // trong 'xml2' => [ ... ]:
    'max_prescription_days'      => 30,
    'lieu_dung_quantity_epsilon' => 0.001,

    // trong 'xml3' => [ ... ]:
    'execution_min_minutes'       => 3,
    'execution_time_check_groups' => [1, 3],
    'same_doctor_check_groups'    => [1, 2, 3],
    'surgery_full_payment_rate'   => '100',

    // mảng mới:
    'xml8' => [
        'tomtat_kq_min_length' => 20,
    ],
```

- [ ] **Step 2: Kiểm cấu hình**

Run: `php -l config/xml3176.php && php artisan config:clear`
Expected: No syntax errors; Configuration cache cleared!

- [ ] **Step 3: Tạo seeder**

Tạo `database/seeds/Xml3176ErrorCatalogNhomASeeder.php`:

```php
<?php

use Illuminate\Database\Seeder;
use App\Models\BHYT\Xml3176ErrorCatalog;

/**
 * Nạp sẵn 20 error_code Nhóm A để cấu hình trước lần scan đầu; TẮT rule cũ trùng
 * (#2498 đã thay giấy chuyển tuyến). Idempotent (updateOrCreate).
 */
class Xml3176ErrorCatalogNhomASeeder extends Seeder
{
    public function run()
    {
        $rules = [
            ['XML1', 'XML1_NGAY_SINH_GREATER_NGAY_VAO', 'Ngày sinh lớn hơn ngày vào viện'],
            ['XML2', 'XML2_LIEU_DUNG_INVALID_FORMAT', 'Liều dùng không đúng định dạng 130'],
            ['XML2', 'XML2_PRESCRIPTION_EXCEEDS_30_DAYS', 'Kê thuốc quá số ngày cho phép'],
            ['XML2', 'XML2_LIEU_DUNG_QUANTITY_MISMATCH', 'Tổng lượng theo liều khác số lượng thanh toán'],
            ['XML2', 'XML2_LIEU_DUNG_UNIT_INVALID', 'Đơn vị trong liều dùng khác đơn vị tính của thuốc'],
            ['XML3', 'XML3_NGAY_TH_YL_EQUALS_NGAY_KQ', 'Thời gian thực hiện y lệnh trùng thời gian kết quả'],
            ['XML3', 'XML3_NGAY_KQ_GREATER_NGAY_RA', 'Ngày kết quả dịch vụ lớn hơn ngày ra viện'],
            ['XML3', 'XML3_EXECUTION_TIME_UNDER_3MIN', 'Thời gian thực hiện nhỏ hơn quy định'],
            ['XML3', 'XML3_SAME_DOCTOR_ORDER_AND_EXECUTE', 'Bác sĩ vừa ra y lệnh vừa thực hiện'],
            ['XML4', 'XML4_MA_CHI_SO_EMPTY', 'Mã chỉ số để trống'],
            ['XML4', 'XML4_TEN_CHI_SO_EMPTY', 'Tên chỉ số để trống'],
            ['XML4', 'XML4_XN_MISSING_VALUE_RESULT', 'Xét nghiệm không nhập giá trị và kết quả'],
            ['XML5', 'XML5_DIEN_BIEN_DUPLICATE', 'Diễn biến điều trị trùng nhau'],
            ['XML7', 'XML7_SO_NGAY_NGHI_MISMATCH', 'Số ngày nghỉ không đúng (đến − từ)'],
            ['XML7', 'XML7_NGOAITRU_TUNGAY_BEFORE_NGAY_RA', 'Bắt đầu nghỉ ngoại trú trước ngày ra viện'],
            ['XML7', 'XML7_NGOAITRU_DENNGAY_BEFORE_NGAY_RA', 'Đến ngày nghỉ ngoại trú trước ngày ra viện'],
            ['XML8', 'XML8_TOMTAT_KQ_TOO_SHORT', 'Tóm tắt kết quả quá ngắn'],
            ['XMLComplete', 'XMLComplete_SECOND_SURGERY_FULL_PAYMENT', 'PTTT lần 2 trong ngày thanh toán 100%'],
            ['XMLComplete', 'XMLComplete_XML4_NGAY_KQ_MISMATCH_XML3', 'Ngày KQ XML4 khác ngày KQ XML3'],
            ['XMLComplete', 'XMLComplete_MISSING_TRANSFER_OR_APPOINTMENT', 'Có nơi đi nhưng thiếu giấy chuyển tuyến hoặc hẹn khám lại'],
        ];

        foreach ($rules as $r) {
            list($xml, $code, $name) = $r;
            Xml3176ErrorCatalog::updateOrCreate(
                ['xml' => $xml, 'error_code' => $code],
                ['error_name' => $name, 'description' => $name, 'critical_error' => true, 'is_check' => true]
            );
        }

        // Tắt rule cũ trùng: #2498 (Complete) đã thay thế; rule cũ không chấp nhận XML14.
        Xml3176ErrorCatalog::where('error_code', 'XML1_ADMIN_INFO_ERROR_GIAY_CHUYEN_TUYEN')
            ->update(['is_check' => false]);
    }
}
```

- [ ] **Step 4: Chạy seeder trên môi trường thật**

Run: `php artisan db:seed --class=Xml3176ErrorCatalogNhomASeeder`
Expected: chạy không lỗi (idempotent).
Kiểm: `php artisan tinker --execute="echo App\Models\BHYT\Xml3176ErrorCatalog::where('error_code','like','XML2_LIEU_DUNG%')->count();"` → ≥ 3.

- [ ] **Step 5: Commit**

```bash
git add config/xml3176.php database/seeds/Xml3176ErrorCatalogNhomASeeder.php
git commit -m "feat(xml3176): cau hinh Nhom A + seeder danh muc loi + tat rule chuyen tuyen cu"
```

---

## Kiểm tra nghiệm thu cuối

- [ ] `vendor/bin/phpunit tests/Unit/Xml3176` → toàn bộ test helper + checker XANH.
- [ ] `php -l` sạch trên 7 checker + 3 helper + config + seeder.
- [ ] Chạy scan thật một hồ sơ đã biết lỗi liều dùng → `xml3176_error_results` xuất hiện `XML2_LIEU_DUNG_INVALID_FORMAT`.
- [ ] Đối chiếu với file giám định: scan cùng tập hồ sơ rồi so số hồ sơ dính từng error_code mới với cột "SỐ FILE LỖI" trong Excel (cùng bậc độ lớn; sai lệch nhỏ do khác cách đếm/định dạng chấp nhận được).
- [ ] Câu hỏi mở còn lại: #1 (regex liều) và #6 (ĐVT liều) — lấy mẫu `lieu_dung` thật từ `xml3176_xml2s` (`SELECT DISTINCT lieu_dung ... LIMIT 50`), đối chiếu với `LieuDungParser`; nếu HIS ghi khác (vd dùng `x`), tinh chỉnh regex + bổ sung fixture rồi chạy lại Task 1.
