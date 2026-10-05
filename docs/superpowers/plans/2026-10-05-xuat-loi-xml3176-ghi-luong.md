# Xuất lỗi XML3176 ghi Excel theo luồng — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Tệp lỗi XML3176 (19 sheet, chạy nền) ghi theo luồng bằng Spout để ngày ~358.800 dòng chạy xong với bộ nhớ đỉnh < 512 MB, nội dung và giao diện y hệt tệp hiện tại.

**Architecture:** Bộ ghi `GhiExcelLuong` đi qua đúng các lớp sheet mà `Xml3176ErrorMultiSheetExport::sheets()` trả về, đọc dòng qua `NguonSheet` (generator hoặc cursor gom lô 1000 → `prepareRows` → `map`), ghi từng dòng bằng Spout, rồi `ChenDoRongCot` chèn `<cols>` vào zip theo luồng. Định dạng mỗi sheet lấy từ một method `dinhDangLuong()` mà cả đường Laravel Excel cũ lẫn đường luồng cùng đọc. Job chọn đường theo công tắc `xml3176.xuat_tep_luong`.

**Tech Stack:** PHP 7.4, Laravel 5.5, PHPUnit 6.5, maatwebsite/excel 3.1.25, phpoffice/phpspreadsheet 1.30.4, box/spout 3.3.0 (đã có trong `composer.lock`), Python 3 + openpyxl 3.1 (chỉ cho script đối chiếu).

**Spec:** `docs/superpowers/specs/2026-10-05-xuat-loi-xml3176-ghi-luong-design.md`

## Global Constraints

- PHP 7.4: không dùng cú pháp PHP 8 (`match`, named args, nullsafe `?->`, union types, `str_contains`).
- PHPUnit 6.5: dùng `assertContains`/`assertNotContains` cho chuỗi; `@test` + `/** @test */`; `setUp()`/`tearDown()` KHÔNG khai báo `: void`.
- KHÔNG đổi `composer.json` / `composer.lock`; KHÔNG thêm thư viện.
- KHÔNG dùng `RefreshDatabase`. Test DB dùng `Tests\Support\Xml3176RuleTestSupport::bootXml3176Sqlite()` (SQLite `:memory:`).
- KHÔNG gọi Oracle/HIS trong test: dùng `Tests\Support\FakeKhoaDieuTriHis`.
- KHÔNG dùng Mockery cho lớp có kiểu trả về (vỡ trên dự án này) — dùng lớp ẩn danh kế thừa.
- `handle()` của job KHÔNG nhận tham số type-hint (bẫy tiêm container Laravel 5.5) — lấy dịch vụ bằng `app(...)` trong thân hàm.
- Lệnh test luôn ép `DB_HOST=127.0.0.1` (`.env` dev trỏ CSDL thật): `DB_HOST=127.0.0.1 vendor/bin/phpunit ...`.
- Comment trong mã: tiếng Việt KHÔNG dấu (theo mã sẵn có). Chuỗi hiện cho người dùng: tiếng Việt CÓ dấu.
- Khác biệt cố ý duy nhất so với tệp cũ: ô có chuỗi bắt đầu bằng `=` ghi thành CHỮ (cũ thành công thức).
- Đường QD130 (`Qd130ErrorMultiSheetExport`) và mọi nút xuất khác KHÔNG đổi hành vi.
- Công tắc: `config('xml3176.xuat_tep_luong')` ← `env('XML3176_XUAT_TEP_LUONG', true)`.
- `memory_limit` 4096M và `set_time_limit(0)` của job GIỮ NGUYÊN trong plan này.
- Commit kết thúc bằng dòng: `Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>`.

## File Structure

| Tệp | Trách nhiệm |
|---|---|
| Create `app/Services/ExcelLuong/KieuO.php` | Quyết định kiểu một ô (số/chữ/trống) theo `DefaultValueBinder` |
| Create `app/Services/ExcelLuong/NguonSheet.php` | Bọc lớp export: tên, tiêu đề, định dạng, generator dòng đã `map()` |
| Create `app/Services/ExcelLuong/ChenDoRongCot.php` | Chèn `<cols>` vào `xl/worksheets/sheetN.xml` theo luồng |
| Create `app/Services/ExcelLuong/GhiExcelLuong.php` | Ghi nhiều sheet bằng Spout, thư mục tạm, đổi tên sang đích |
| Modify `app/Exports/Xml3176ErrorSheetExport.php` | Thêm `dinhDangLuong()`; `registerEvents()`/`styles()` đọc từ đó |
| Modify `app/Exports/HeinCardErrorExport.php` | Như trên |
| Modify `app/Exports/DmKhoaGiuongSheetExport.php` | Thêm `dinhDangLuong()` |
| Modify `app/Exports/DmNvytSheetExport.php` | Thêm `dinhDangLuong()` |
| Modify `app/Jobs/XuatTepLoiXml3176Job.php` | Chọn đường theo công tắc |
| Modify `config/xml3176.php` | Khoá `xuat_tep_luong` |
| Create `tests/Support/SheetGia.php` | Lớp sheet giả cho test bộ ghi |
| Create `tests/Unit/ExcelLuong/KieuOTest.php` | |
| Create `tests/Unit/ExcelLuong/NguonSheetTest.php` | |
| Create `tests/Unit/ExcelLuong/ChenDoRongCotTest.php` | |
| Create `tests/Unit/ExcelLuong/GhiExcelLuongTest.php` | |
| Create `tests/Unit/Xml3176/TepXuat/DinhDangLuongTest.php` | |
| Modify `tests/Unit/Xml3176/TepXuat/XuatTepLoiXml3176JobTest.php` | Test cũ ép công tắc tắt; thêm test công tắc bật |
| Create `scripts/so-sanh-xuat-loi-xml3176.php` | Xuất một ngày bằng một đường, in thời gian + đỉnh bộ nhớ |
| Create `scripts/so-sanh-xlsx.py` | So hai tệp xlsx từng ô, kiểu, định dạng, độ rộng |

---

### Task 1: KieuO — kiểu ô giống `DefaultValueBinder`

**Files:**
- Create: `app/Services/ExcelLuong/KieuO.php`
- Test: `tests/Unit/ExcelLuong/KieuOTest.php`

**Interfaces:**
- Produces: `App\Services\ExcelLuong\KieuO::chuyen($v, string $kieu): array` → `[mixed $giaTriGhi, bool $laSo]`; hằng `KieuO::TU_DONG = 'tu_dong'`, `KieuO::CHU = 'chu'`.

- [ ] **Step 1: Write the failing test**

```php
<?php

namespace Tests\Unit\ExcelLuong;

use App\Services\ExcelLuong\KieuO;
use Tests\TestCase;

/**
 * Kieu o phai GIONG HET Laravel Excel (DefaultValueBinder) de tep ghi luong khong khac tep cu,
 * tru mot khac biet co y: chuoi bat dau '=' ghi thanh CHU (cu thanh cong thuc).
 */
class KieuOTest extends TestCase
{
    /** @test */
    public function chuoi_so_thanh_so()
    {
        $this->assertSame([202610050800, true], KieuO::chuyen('202610050800', KieuO::TU_DONG));
        $this->assertSame([1.5, true], KieuO::chuyen('1.5', KieuO::TU_DONG));
        $this->assertSame([7, true], KieuO::chuyen(7, KieuO::TU_DONG));
    }

    /** @test */
    public function so_0_dau_va_so_qua_lon_giu_chu()
    {
        $this->assertSame(['000007230917', false], KieuO::chuyen('000007230917', KieuO::TU_DONG));
        $this->assertSame(['99999999999999999999', false], KieuO::chuyen('99999999999999999999', KieuO::TU_DONG));
    }

    /** @test */
    public function null_va_chuoi_rong_thanh_o_trong()
    {
        $this->assertSame([null, false], KieuO::chuyen(null, KieuO::TU_DONG));
        $this->assertSame([null, false], KieuO::chuyen('', KieuO::TU_DONG));
        $this->assertSame([null, false], KieuO::chuyen(null, KieuO::CHU));
    }

    /** Khac biet co y: ban cu bien thanh cong thuc Excel - chen cong thuc tu du lieu. */
    /** @test */
    public function chuoi_bat_dau_bang_dau_bang_ghi_chu()
    {
        $this->assertSame(['=SUM(A1)', false], KieuO::chuyen('=SUM(A1)', KieuO::TU_DONG));
    }

    /** @test */
    public function ma_loi_excel_va_chu_thuong_giu_chu()
    {
        $this->assertSame(['#N/A', false], KieuO::chuyen('#N/A', KieuO::TU_DONG));
        $this->assertSame(['Khoa Noi', false], KieuO::chuyen('Khoa Noi', KieuO::TU_DONG));
    }

    /** Hai sheet danh muc dung StringValueBinder: moi o la chu. */
    /** @test */
    public function kieu_chu_moi_o_la_chu()
    {
        $this->assertSame(['123', false], KieuO::chuyen('123', KieuO::CHU));
        $this->assertSame(['45', false], KieuO::chuyen(45, KieuO::CHU));
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `DB_HOST=127.0.0.1 vendor/bin/phpunit tests/Unit/ExcelLuong/KieuOTest.php`
Expected: 6 errors `Class 'App\Services\ExcelLuong\KieuO' not found`.

- [ ] **Step 3: Write minimal implementation**

```php
<?php

namespace App\Services\ExcelLuong;

use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;

/**
 * Kieu mot o khi ghi luong - GIONG quy tac Laravel Excel dang dung (DefaultValueBinder) de
 * tep moi khong khac tep cu. Goi thang ham cua PhpSpreadsheet, khong viet lai quy tac.
 *
 * Khac biet CO Y: chuoi bat dau '=' ghi thanh CHU. Ban cu bien no thanh cong thuc Excel -
 * mo ta loi tu du lieu co the chen cong thuc vao tep.
 */
final class KieuO
{
    /** Nhu DefaultValueBinder. */
    const TU_DONG = 'tu_dong';

    /** Nhu StringValueBinder: moi o khac rong la chu. */
    const CHU = 'chu';

    /**
     * @param mixed $v
     * @return array [gia tri ghi, la so?] - null nghia la o trong
     */
    public static function chuyen($v, string $kieu): array
    {
        if ($v === null || $v === '') {
            return [null, false];
        }

        if ($kieu === self::CHU) {
            return [(string) $v, false];
        }

        switch (DefaultValueBinder::dataTypeForValue($v)) {
            case DataType::TYPE_NUMERIC:
                // '202610050800' + 0 = int; '1.5' + 0 = float.
                return [is_string($v) ? $v + 0 : $v, true];
            case DataType::TYPE_BOOL:
                return [$v, false];
            default:
                // STRING, FORMULA (co y ghi chu), ERROR.
                return [(string) $v, false];
        }
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `DB_HOST=127.0.0.1 vendor/bin/phpunit tests/Unit/ExcelLuong/KieuOTest.php`
Expected: `OK (6 tests, ...)`

- [ ] **Step 5: Commit**

```bash
git add app/Services/ExcelLuong/KieuO.php tests/Unit/ExcelLuong/KieuOTest.php
git commit -m "feat(excel-luong): KieuO quyet dinh kieu o giong DefaultValueBinder

Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>"
```

---

### Task 2: `dinhDangLuong()` — một nguồn định dạng cho 4 lớp sheet

**Files:**
- Modify: `app/Exports/Xml3176ErrorSheetExport.php` (method `registerEvents()`, `styles()`; thêm `dinhDangLuong()`)
- Modify: `app/Exports/HeinCardErrorExport.php` (method `registerEvents()`; thêm `dinhDangLuong()`)
- Modify: `app/Exports/DmKhoaGiuongSheetExport.php` (thêm `dinhDangLuong()`)
- Modify: `app/Exports/DmNvytSheetExport.php` (thêm `dinhDangLuong()`)
- Test: `tests/Unit/Xml3176/TepXuat/DinhDangLuongTest.php`

**Interfaces:**
- Produces: trên 4 lớp, `public function dinhDangLuong(): array` trả về đúng các khoá:
  `'do_rong' => array<string,int|float>` (chữ cột ⇒ độ rộng; `[]` = mặc định),
  `'cot_so' => string[]` (chữ cột áp numFmt `0` cho ô số),
  `'kieu_o' => 'tu_dong'|'chu'`, `'xuong_dong' => bool`, `'tieu_de_can_giua' => bool`.

- [ ] **Step 1: Write the failing test**

```php
<?php

namespace Tests\Unit\Xml3176\TepXuat;

use App\Exports\DmKhoaGiuongSheetExport;
use App\Exports\DmNvytSheetExport;
use App\Exports\HeinCardErrorExport;
use App\Exports\Xml3176ErrorSheetExport;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Sheet;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Tests\Support\FakeKhoaDieuTriHis;
use Tests\TestCase;

/**
 * dinhDangLuong() la nguon DUY NHAT cua dinh dang: duong Laravel Excel cu (registerEvents)
 * va duong ghi luong (GhiExcelLuong) cung doc. Test nay chay that registerEvents() tren mot
 * worksheet trong va doi chieu voi dinhDangLuong() - lech la hai duong ra tep khac nhau.
 */
class DinhDangLuongTest extends TestCase
{
    private function apSuKien($export)
    {
        $ws = (new Spreadsheet())->getActiveSheet();
        $su = $export->registerEvents()[AfterSheet::class];
        $su(new AfterSheet(new Sheet($ws), $export));

        return $ws;
    }

    private function loc()
    {
        return ['date_from' => '2026-10-05 00:00:00', 'date_to' => '2026-10-05 23:59:59'];
    }

    /** @test */
    public function sheet_loi_dinh_dang_khop_registerEvents()
    {
        $x = new Xml3176ErrorSheetExport('XML3', $this->loc(), [], new FakeKhoaDieuTriHis());
        $d = $x->dinhDangLuong();

        $this->assertSame(Xml3176ErrorSheetExport::DO_RONG, $d['do_rong']);
        $this->assertSame(Xml3176ErrorSheetExport::COT_NGAY, $d['cot_so']);
        $this->assertSame('tu_dong', $d['kieu_o']);
        $this->assertTrue($d['xuong_dong']);
        $this->assertTrue($d['tieu_de_can_giua']);

        $ws = $this->apSuKien($x);
        foreach ($d['do_rong'] as $cot => $rong) {
            $this->assertEquals($rong, $ws->getColumnDimension($cot)->getWidth(), "do rong cot $cot");
        }
        foreach ($d['cot_so'] as $cot) {
            $ws->setCellValue($cot . '2', 202610050800);
            $this->assertSame('0', $ws->getStyle($cot . '2')->getNumberFormat()->getFormatCode(), "cot so $cot");
        }
    }

    /** @test */
    public function sheet_the_dinh_dang_khop_registerEvents_ca_hai_che_do()
    {
        foreach ([true => 'I', false => 'F'] as $coMaKhoa => $cotCuoi) {
            $x = new HeinCardErrorExport('2026-10-01 00:00:00', '2026-10-05 23:59:59', null,
                $coMaKhoa ? 'xml3176' : 'qd130xml', $coMaKhoa, new FakeKhoaDieuTriHis());
            $d = $x->dinhDangLuong();

            $this->assertSame($cotCuoi, array_keys($d['do_rong'])[count($d['do_rong']) - 1]);
            $this->assertSame([], $d['cot_so']);
            $this->assertSame('tu_dong', $d['kieu_o']);
            $this->assertTrue($d['xuong_dong']);
            $this->assertTrue($d['tieu_de_can_giua']);

            $ws = $this->apSuKien($x);
            foreach ($d['do_rong'] as $cot => $rong) {
                $this->assertEquals($rong, $ws->getColumnDimension($cot)->getWidth(), "do rong cot $cot");
            }
        }
    }

    /** Hai sheet danh muc: StringValueBinder, chi tieu de dam, khong do rong, khong xuong dong. */
    /** @test */
    public function sheet_danh_muc_toan_chu_khong_do_rong()
    {
        foreach ([new DmKhoaGiuongSheetExport(null, []), new DmNvytSheetExport(null, [])] as $x) {
            $this->assertSame([
                'do_rong' => [], 'cot_so' => [], 'kieu_o' => 'chu',
                'xuong_dong' => false, 'tieu_de_can_giua' => false,
            ], $x->dinhDangLuong());
        }
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `DB_HOST=127.0.0.1 vendor/bin/phpunit tests/Unit/Xml3176/TepXuat/DinhDangLuongTest.php`
Expected: 3 errors `Call to undefined method ...::dinhDangLuong()`.

- [ ] **Step 3: Implement — `Xml3176ErrorSheetExport`**

Thêm method (đặt ngay trên `registerEvents()`):

```php
    /**
     * Dinh dang dung CHUNG cho duong Laravel Excel (registerEvents/styles) va duong ghi luong
     * (App\Services\ExcelLuong\GhiExcelLuong): mot nguon, hai duong khong the lech nhau.
     */
    public function dinhDangLuong(): array
    {
        return [
            'do_rong' => self::DO_RONG,
            'cot_so' => self::COT_NGAY,
            'kieu_o' => 'tu_dong',
            'xuong_dong' => true,
            'tieu_de_can_giua' => true,
        ];
    }
```

Thay thân `registerEvents()` bằng:

```php
    public function registerEvents(): array
    {
        $d = $this->dinhDangLuong();

        return [
            AfterSheet::class => function (AfterSheet $event) use ($d) {
                $sheet = $event->sheet->getDelegate();

                foreach ($d['do_rong'] as $cot => $rong) {
                    $sheet->getColumnDimension($cot)->setWidth($rong);
                }

                foreach ($d['cot_so'] as $cot) {
                    $sheet->getStyle($cot)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER);
                }
            },
        ];
    }
```

Trong `styles()`, thay `$sheet->getStyle('A:U')` bằng:

```php
        $doRong = $this->dinhDangLuong()['do_rong'];
        $sheet->getStyle('A:' . array_keys($doRong)[count($doRong) - 1])->getAlignment()->setWrapText(true);
```

(giữ nguyên phần `return [1 => ...]`).

- [ ] **Step 4: Implement — `HeinCardErrorExport`**

Thêm method (ngay trên `registerEvents()`):

```php
    /**
     * Dinh dang dung CHUNG cho duong Laravel Excel (registerEvents/styles) va duong ghi luong
     * (App\Services\ExcelLuong\GhiExcelLuong).
     *
     * ShouldAutoSize khong co tac dung that: AfterSheet dat cung do rong cho MOI cot cua sheet.
     */
    public function dinhDangLuong(): array
    {
        return [
            'do_rong' => $this->coMaKhoa
                ? ['A' => 5, 'B' => 13, 'C' => 10, 'D' => 15, 'E' => 15, 'F' => 50, 'G' => 18, 'H' => 12, 'I' => 30]
                : ['A' => 5, 'B' => 13, 'C' => 15, 'D' => 15, 'E' => 50, 'F' => 18],
            'cot_so' => [],
            'kieu_o' => 'tu_dong',
            'xuong_dong' => true,
            'tieu_de_can_giua' => true,
        ];
    }
```

Trong `registerEvents()`, thay khối gán `$doRong = $this->coMaKhoa ? [...] : [...];` bằng:

```php
        $doRong = $this->dinhDangLuong()['do_rong'];
```

(phần `return [AfterSheet::class => ...]` giữ nguyên).

- [ ] **Step 5: Implement — hai sheet danh mục**

Thêm vào CẢ `DmKhoaGiuongSheetExport` và `DmNvytSheetExport` (ngay trên `styles()`):

```php
    /**
     * Dinh dang cho duong ghi luong (App\Services\ExcelLuong\GhiExcelLuong): 'chu' = nhu
     * StringValueBinder ma lop nay ke thua; chi tieu de dam nhu styles().
     */
    public function dinhDangLuong(): array
    {
        return [
            'do_rong' => [],
            'cot_so' => [],
            'kieu_o' => 'chu',
            'xuong_dong' => false,
            'tieu_de_can_giua' => false,
        ];
    }
```

- [ ] **Step 6: Run tests to verify they pass**

Run: `DB_HOST=127.0.0.1 vendor/bin/phpunit tests/Unit/Xml3176`
Expected: `DinhDangLuongTest` OK (3 tests). Các test sẵn có trong `tests/Unit/Xml3176` không có lỗi MỚI — lỗi duy nhất được phép là 4 test cần MySQL thật đã đỏ từ trước (`Xml3176Xml3CheckerRuleTest` ×3, `Xml3176ErrorServiceGomTest::che_do_gom_bat_tat_dung`, lỗi `Access denied for user 'appuser'`).

- [ ] **Step 7: Commit**

```bash
git add app/Exports/Xml3176ErrorSheetExport.php app/Exports/HeinCardErrorExport.php app/Exports/DmKhoaGiuongSheetExport.php app/Exports/DmNvytSheetExport.php tests/Unit/Xml3176/TepXuat/DinhDangLuongTest.php
git commit -m "refactor(xml3176): dinhDangLuong() la nguon dinh dang duy nhat cua 4 sheet

Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>"
```

---

### Task 3: NguonSheet — đọc dòng từ lớp export (generator hoặc cursor gom lô)

**Files:**
- Create: `app/Services/ExcelLuong/NguonSheet.php`
- Create: `tests/Support/SheetGia.php`
- Test: `tests/Unit/ExcelLuong/NguonSheetTest.php`

**Interfaces:**
- Consumes: `KieuO::TU_DONG` (Task 1); `dinhDangLuong()` (Task 2) nếu lớp có.
- Produces:
  - `new NguonSheet(object $export)`
  - `ten(): string` (= `$export->title()`), `tieuDe(): array` (= `$export->headings()`)
  - `dinhDang(): array` — `NguonSheet::MAC_DINH` gộp với `dinhDangLuong()` nếu có
  - `dong(): \Generator` — mỗi phần tử là mảng đã `map()`; khoá tăng dần (KHÔNG `yield from`)
  - hằng `NguonSheet::LO = 1000`
  - `Tests\Support\SheetGia` (dùng ở Task 5): `new SheetGia(string $ten, array $tieuDe, iterable $dong, array $dinhDang = [], int $nemSau = null)`, có `title()`, `headings()`, `generator()`, `map($d)`, `dinhDangLuong()`.

- [ ] **Step 1: Write the test support class**

`tests/Support/SheetGia.php`:

```php
<?php

namespace Tests\Support;

/**
 * Sheet gia cho test bo ghi luong: cung "hinh" voi lop export FromGenerator (title, headings,
 * generator, map, dinhDangLuong). $nemSau: nem loi sau N dong de thu duong hong giua chung.
 */
class SheetGia
{
    public $ten;
    public $tieuDe;
    public $dong;
    public $dinhDang;
    public $nemSau;

    public function __construct(string $ten, array $tieuDe, $dong, array $dinhDang = [], int $nemSau = null)
    {
        $this->ten = $ten;
        $this->tieuDe = $tieuDe;
        $this->dong = $dong;
        $this->dinhDang = $dinhDang;
        $this->nemSau = $nemSau;
    }

    public function title(): string
    {
        return $this->ten;
    }

    public function headings(): array
    {
        return $this->tieuDe;
    }

    public function generator(): \Generator
    {
        $i = 0;

        foreach ($this->dong as $d) {
            if ($this->nemSau !== null && $i >= $this->nemSau) {
                throw new \RuntimeException('hong giua chung');
            }
            $i++;
            yield $d;
        }
    }

    public function map($d): array
    {
        return $d;
    }

    public function dinhDangLuong(): array
    {
        return $this->dinhDang;
    }
}
```

- [ ] **Step 2: Write the failing test**

`tests/Unit/ExcelLuong/NguonSheetTest.php`:

```php
<?php

namespace Tests\Unit\ExcelLuong;

use App\Models\BHYT\Xml3176ErrorResult;
use App\Services\ExcelLuong\NguonSheet;
use Illuminate\Support\Facades\DB;
use Tests\Support\SheetGia;
use Tests\Support\Xml3176RuleTestSupport;
use Tests\TestCase;

class NguonSheetTest extends TestCase
{
    use Xml3176RuleTestSupport;

    /** Lop export FromQuery gia: query() cho truoc, ghi lai cac lo prepareRows. */
    private function xuatTruyVan($query)
    {
        return new class($query) {
            public $q;
            public $cacLo = [];

            public function __construct($q)
            {
                $this->q = $q;
            }

            public function query()
            {
                return $this->q;
            }

            public function prepareRows($rows)
            {
                $this->cacLo[] = count($rows);

                return $rows;
            }

            public function map($r): array
            {
                return [(int) $r->id, $r->ma_lk];
            }

            public function headings(): array
            {
                return ['ID', 'Ma LK'];
            }

            public function title(): string
            {
                return 'Gia';
            }
        };
    }

    private function bangLoi(array $ids)
    {
        $this->bootXml3176Sqlite(['2026_01_09_161902_create_xml3176_error_results_table.php']);

        foreach (array_chunk($ids, 100) as $lo) {
            DB::table('xml3176_error_results')->insert(array_map(function ($id) {
                return ['id' => $id, 'xml' => 'XML1', 'ma_lk' => 'LK' . $id, 'stt' => 1,
                        'error_code' => 'E', 'description' => 'd', 'critical_error' => 0];
            }, $lo));
        }
    }

    /** @test */
    public function generator_di_qua_map_va_khoa_tang_dan()
    {
        $n = new NguonSheet(new SheetGia('S', ['A'], [[1], [2], [3]]));

        $this->assertSame([[1], [2], [3]], iterator_to_array($n->dong(), true));
        $this->assertSame('S', $n->ten());
        $this->assertSame(['A'], $n->tieuDe());
    }

    /**
     * Duong cu FromQuery dung chunk(): tu orderBy khoa chinh khi truy van chua co thu tu.
     * cursor() thi khong - phai tu them, khong thi thu tu dong khac tep cu.
     */
    /** @test */
    public function truy_van_chua_co_thu_tu_thi_sap_theo_khoa_chinh()
    {
        $this->bangLoi([3, 1, 2]);
        $x = $this->xuatTruyVan(Xml3176ErrorResult::query()->select('id', 'ma_lk'));

        $ra = iterator_to_array((new NguonSheet($x))->dong(), true);

        $this->assertSame([[1, 'LK1'], [2, 'LK2'], [3, 'LK3']], $ra);
    }

    /** @test */
    public function truy_van_da_co_thu_tu_thi_giu()
    {
        $this->bangLoi([3, 1, 2]);
        $x = $this->xuatTruyVan(Xml3176ErrorResult::query()->select('id', 'ma_lk')->orderByDesc('id'));

        $this->assertSame([3, 2, 1], array_column(iterator_to_array((new NguonSheet($x))->dong(), true), 0));
    }

    /** @test */
    public function prepare_rows_mot_lan_moi_lo_1000()
    {
        $this->bangLoi(range(1, 2500));
        $x = $this->xuatTruyVan(Xml3176ErrorResult::query()->select('id', 'ma_lk'));

        $ra = iterator_to_array((new NguonSheet($x))->dong(), true);

        $this->assertCount(2500, $ra, 'Khong duoc mat dong giua cac lo');
        $this->assertSame([1000, 1000, 500], $x->cacLo);
    }

    /** @test */
    public function dinh_dang_gop_mac_dinh()
    {
        $n = new NguonSheet(new SheetGia('S', ['A'], [], ['xuong_dong' => true]));

        $this->assertSame([
            'do_rong' => [], 'cot_so' => [], 'kieu_o' => 'tu_dong',
            'xuong_dong' => true, 'tieu_de_can_giua' => false,
        ], $n->dinhDang());
    }
}
```

- [ ] **Step 3: Run test to verify it fails**

Run: `DB_HOST=127.0.0.1 vendor/bin/phpunit tests/Unit/ExcelLuong/NguonSheetTest.php`
Expected: 5 errors `Class 'App\Services\ExcelLuong\NguonSheet' not found`.

- [ ] **Step 4: Write minimal implementation**

`app/Services/ExcelLuong/NguonSheet.php`:

```php
<?php

namespace App\Services\ExcelLuong;

/**
 * Boc mot lop export san co (Laravel Excel concern) de bo ghi luong doc: ten, tieu de, dinh
 * dang va CAC DONG DA map(). Noi DUY NHAT biet khac biet FromGenerator / FromQuery.
 *
 * RAM chi giu mot lo LO dong - khong gom ca sheet nhu Laravel Excel 3.1.25 (appendRows ->
 * new Collection(generator)).
 */
class NguonSheet
{
    const LO = 1000;

    const MAC_DINH = [
        'do_rong' => [],
        'cot_so' => [],
        'kieu_o' => KieuO::TU_DONG,
        'xuong_dong' => false,
        'tieu_de_can_giua' => false,
    ];

    protected $export;

    public function __construct($export)
    {
        $this->export = $export;
    }

    public function ten(): string
    {
        return $this->export->title();
    }

    public function tieuDe(): array
    {
        return $this->export->headings();
    }

    public function dinhDang(): array
    {
        $rieng = method_exists($this->export, 'dinhDangLuong') ? $this->export->dinhDangLuong() : [];

        return array_merge(self::MAC_DINH, $rieng);
    }

    /**
     * Cac dong da map(). 'yield' tung phan tu - KHONG 'yield from $mang': khoa lap lai giua cac
     * lo, ai doc bang iterator_to_array giu khoa se mat dong (bai hoc 05/10/2026).
     */
    public function dong(): \Generator
    {
        if (method_exists($this->export, 'generator')) {
            foreach ($this->export->generator() as $r) {
                yield $this->export->map($r);
            }

            return;
        }

        $q = $this->export->query();

        // Duong cu FromQuery dung chunk() - Eloquent tu orderBy khoa chinh khi chua co thu tu
        // (enforceOrderBy). cursor() khong lam: tu them de thu tu dong giong tep cu.
        if (empty($q->getQuery()->orders)) {
            $q->orderBy($q->getModel()->getQualifiedKeyName());
        }

        $lo = [];

        foreach ($q->cursor() as $r) {
            $lo[] = $r;

            if (count($lo) >= self::LO) {
                foreach ($this->mapLo($lo) as $d) {
                    yield $d;
                }
                $lo = [];
            }
        }

        foreach ($this->mapLo($lo) as $d) {
            yield $d;
        }
    }

    protected function mapLo(array $lo): array
    {
        if (!$lo) {
            return [];
        }

        $rows = collect($lo);

        if (method_exists($this->export, 'prepareRows')) {
            $rows = $this->export->prepareRows($rows);
        }

        $ra = [];

        foreach ($rows as $r) {
            $ra[] = $this->export->map($r);
        }

        return $ra;
    }
}
```

- [ ] **Step 5: Run test to verify it passes**

Run: `DB_HOST=127.0.0.1 vendor/bin/phpunit tests/Unit/ExcelLuong/NguonSheetTest.php`
Expected: `OK (5 tests, ...)`

- [ ] **Step 6: Commit**

```bash
git add app/Services/ExcelLuong/NguonSheet.php tests/Support/SheetGia.php tests/Unit/ExcelLuong/NguonSheetTest.php
git commit -m "feat(excel-luong): NguonSheet doc dong tu lop export theo lo 1000

Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>"
```

---

### Task 4: ChenDoRongCot — chèn `<cols>` vào zip theo luồng

**Files:**
- Create: `app/Services/ExcelLuong/ChenDoRongCot.php`
- Test: `tests/Unit/ExcelLuong/ChenDoRongCotTest.php`

**Interfaces:**
- Produces: `(new ChenDoRongCot())->chen(string $tepXlsx, array $doRongTheoSheet): void` — `$doRongTheoSheet` dạng `[soThuTuSheet1Base => ['A' => 5, 'C' => 50], ...]`; sheet có mảng rỗng thì bỏ qua. Ném `\RuntimeException` khi không mở được zip, không thấy mục sheet, hoặc không thấy `<sheetData` trong 64 KB đầu.

- [ ] **Step 1: Write the failing test**

```php
<?php

namespace Tests\Unit\ExcelLuong;

use App\Services\ExcelLuong\ChenDoRongCot;
use Box\Spout\Writer\Common\Creator\WriterEntityFactory;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class ChenDoRongCotTest extends TestCase
{
    protected $thuMuc;

    protected function setUp()
    {
        parent::setUp();
        $this->thuMuc = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('chen_cols_', true);
        mkdir($this->thuMuc);
    }

    protected function tearDown()
    {
        foreach (glob($this->thuMuc . DIRECTORY_SEPARATOR . '*') as $f) {
            @unlink($f);
        }
        @rmdir($this->thuMuc);
        parent::tearDown();
    }

    /** Tep 2 sheet ghi bang Spout - dung dinh dang that ma GhiExcelLuong se tao. */
    private function tepSpout()
    {
        $tep = $this->thuMuc . DIRECTORY_SEPARATOR . 'a.xlsx';
        $w = WriterEntityFactory::createXLSXWriter();
        $w->setTempFolder($this->thuMuc);
        $w->openToFile($tep);
        $w->addRow(WriterEntityFactory::createRowFromArray(['a', 'b', 'c']));
        $w->addNewSheetAndMakeItCurrent();
        $w->addRow(WriterEntityFactory::createRowFromArray(['x']));
        $w->close();

        return $tep;
    }

    private function docMuc($tep, $ten)
    {
        $z = new \ZipArchive();
        $z->open($tep);
        $nd = $z->getFromName($ten);
        $z->close();

        return $nd;
    }

    /** @test */
    public function chen_cols_ngay_truoc_sheetData_va_khong_dung_sheet_khong_co_do_rong()
    {
        $tep = $this->tepSpout();
        $sheet2Truoc = $this->docMuc($tep, 'xl/worksheets/sheet2.xml');

        (new ChenDoRongCot())->chen($tep, [1 => ['C' => 50, 'A' => 5], 2 => []]);

        $this->assertContains(
            '<cols><col min="1" max="1" width="5" customWidth="1"/><col min="3" max="3" width="50" customWidth="1"/></cols><sheetData>',
            $this->docMuc($tep, 'xl/worksheets/sheet1.xml')
        );
        $this->assertSame($sheet2Truoc, $this->docMuc($tep, 'xl/worksheets/sheet2.xml'));
    }

    /** @test */
    public function tep_sau_chen_van_hop_le_va_do_rong_dung()
    {
        $tep = $this->tepSpout();

        (new ChenDoRongCot())->chen($tep, [1 => ['A' => 5, 'C' => 50]]);

        $ws = IOFactory::load($tep)->getSheet(0);
        $this->assertEquals(5, $ws->getColumnDimension('A')->getWidth());
        $this->assertEquals(50, $ws->getColumnDimension('C')->getWidth());
        $this->assertSame('c', $ws->getCell('C1')->getValue());
    }

    /** Spout doi dinh dang ghi ma khong ai biet: phai NEM, khong xuat tep thieu do rong im lang. */
    /** @test */
    public function khong_thay_sheetData_thi_nem_loi()
    {
        $tep = $this->thuMuc . DIRECTORY_SEPARATOR . 'b.xlsx';
        $z = new \ZipArchive();
        $z->open($tep, \ZipArchive::CREATE);
        $z->addFromString('xl/worksheets/sheet1.xml', '<worksheet>' . str_repeat(' ', 70000) . '</worksheet>');
        $z->close();

        $this->expectException(\RuntimeException::class);
        (new ChenDoRongCot())->chen($tep, [1 => ['A' => 5]]);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `DB_HOST=127.0.0.1 vendor/bin/phpunit tests/Unit/ExcelLuong/ChenDoRongCotTest.php`
Expected: 3 errors `Class 'App\Services\ExcelLuong\ChenDoRongCot' not found`.

- [ ] **Step 3: Write minimal implementation**

```php
<?php

namespace App\Services\ExcelLuong;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

/**
 * Chen do rong cot (<cols>) vao tep xlsx Spout da ghi - Spout 3.3 khong dat duoc do rong.
 *
 * Spout ghi sheet thu n vao xl/worksheets/sheet{n}.xml, phan dau la '<worksheet ...><sheetData>';
 * thu tu OOXML: <cols> dung truoc <sheetData>. Chep THEO LUONG sang tep tam (sheet XML3 co the
 * > 100 MB) roi thay muc trong zip - khong nap ca sheet vao RAM.
 */
class ChenDoRongCot
{
    /** Chi tim <sheetData trong phan dau nay; khong thay = Spout doi dinh dang -> nem. */
    const TIM_TRONG = 65536;

    const KHOI = 1048576;

    /**
     * @param array $doRongTheoSheet [so thu tu sheet (tu 1) => ['A' => 5, ...]]
     */
    public function chen(string $tepXlsx, array $doRongTheoSheet): void
    {
        $zip = new \ZipArchive();

        if ($zip->open($tepXlsx) !== true) {
            throw new \RuntimeException("Không mở được tệp xlsx $tepXlsx");
        }

        $tepTam = [];

        try {
            foreach ($doRongTheoSheet as $so => $doRong) {
                if (!$doRong) {
                    continue;
                }

                $ten = "xl/worksheets/sheet{$so}.xml";
                $tam = tempnam(dirname($tepXlsx), 'cols');
                $tepTam[] = $tam;

                $this->chepCoCols($zip, $ten, $tam, $this->xmlCols($doRong));

                if (!$zip->addFile($tam, $ten)) {
                    throw new \RuntimeException("Không thay được $ten trong tệp xlsx");
                }
            }

            if (!$zip->close()) {
                throw new \RuntimeException('Không ghi lại được tệp xlsx sau khi chèn độ rộng cột');
            }
            $zip = null;
        } finally {
            // addFile() doc tep tam LUC close() - chi xoa sau khi da close.
            if ($zip === null) {
                foreach ($tepTam as $t) {
                    @unlink($t);
                }
            }
        }
    }

    protected function chepCoCols(\ZipArchive $zip, string $ten, string $tam, string $cols): void
    {
        $vao = $zip->getStream($ten);

        if ($vao === false) {
            throw new \RuntimeException("Không thấy $ten trong tệp xlsx");
        }

        $ra = fopen($tam, 'wb');

        try {
            $dau = '';
            while (!feof($vao) && strlen($dau) < self::TIM_TRONG && strpos($dau, '<sheetData') === false) {
                $dau .= fread($vao, 8192);
            }

            $vt = strpos($dau, '<sheetData');

            if ($vt === false) {
                throw new \RuntimeException("Không thấy <sheetData> trong $ten - định dạng Spout đã đổi?");
            }

            fwrite($ra, substr($dau, 0, $vt) . $cols . substr($dau, $vt));

            while (!feof($vao)) {
                fwrite($ra, fread($vao, self::KHOI));
            }
        } finally {
            fclose($vao);
            fclose($ra);
        }
    }

    protected function xmlCols(array $doRong): string
    {
        $theoSo = [];

        foreach ($doRong as $chu => $rong) {
            $theoSo[Coordinate::columnIndexFromString($chu)] = $rong;
        }

        ksort($theoSo);

        $xml = '<cols>';

        foreach ($theoSo as $i => $rong) {
            $xml .= '<col min="' . $i . '" max="' . $i . '" width="' . $this->so($rong) . '" customWidth="1"/>';
        }

        return $xml . '</cols>';
    }

    /** So khong phu thuoc locale (PHP 7.4 doi float sang chuoi theo LC_NUMERIC). */
    protected function so($rong): string
    {
        if (is_int($rong)) {
            return (string) $rong;
        }

        return rtrim(rtrim(sprintf('%.4F', $rong), '0'), '.');
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `DB_HOST=127.0.0.1 vendor/bin/phpunit tests/Unit/ExcelLuong/ChenDoRongCotTest.php`
Expected: `OK (3 tests, ...)`

- [ ] **Step 5: Commit**

```bash
git add app/Services/ExcelLuong/ChenDoRongCot.php tests/Unit/ExcelLuong/ChenDoRongCotTest.php
git commit -m "feat(excel-luong): ChenDoRongCot chen <cols> vao xlsx theo luong

Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>"
```

---

### Task 5: GhiExcelLuong — ghi nhiều sheet bằng Spout

**Files:**
- Create: `app/Services/ExcelLuong/GhiExcelLuong.php`
- Test: `tests/Unit/ExcelLuong/GhiExcelLuongTest.php`

**Interfaces:**
- Consumes: `KieuO::chuyen()` (Task 1), `NguonSheet` (Task 3), `ChenDoRongCot::chen()` (Task 4), `Tests\Support\SheetGia` (Task 3).
- Produces: `new GhiExcelLuong(string $thuMucTamGoc = null)` (mặc định `storage_path('app/xuat-tam')`); `ghi(array $sheets, string $dich): void` — `$sheets` là danh sách lớp export (như `Xml3176ErrorMultiSheetExport::sheets()`), `$dich` đường dẫn tuyệt đối. Ném lại mọi lỗi; không để tệp đích dở dang; luôn xoá thư mục tạm.

- [ ] **Step 1: Write the failing test**

```php
<?php

namespace Tests\Unit\ExcelLuong;

use App\Services\ExcelLuong\GhiExcelLuong;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\Support\SheetGia;
use Tests\TestCase;

class GhiExcelLuongTest extends TestCase
{
    protected $goc;
    protected $tamGoc;

    protected function setUp()
    {
        parent::setUp();
        $this->goc = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('ghi_luong_', true);
        $this->tamGoc = $this->goc . DIRECTORY_SEPARATOR . 'tam';
        mkdir($this->tamGoc, 0777, true);
    }

    protected function tearDown()
    {
        $it = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->goc, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($it as $f) {
            $f->isDir() ? @rmdir($f->getPathname()) : @unlink($f->getPathname());
        }
        @rmdir($this->goc);
        parent::tearDown();
    }

    private function dich()
    {
        return $this->goc . DIRECTORY_SEPARATOR . 'ra' . DIRECTORY_SEPARATOR . 'tep.xlsx';
    }

    private function ghi(array $sheets)
    {
        (new GhiExcelLuong($this->tamGoc))->ghi($sheets, $this->dich());

        return IOFactory::load($this->dich());
    }

    /** @test */
    public function dung_so_sheet_ten_va_thu_tu()
    {
        $wb = $this->ghi([
            new SheetGia('XML1', ['A'], [[1]]),
            new SheetGia('Lỗi thẻ BHYT', ['A'], []),
            new SheetGia('DM khoa-giường', ['A'], [['x']]),
        ]);

        $this->assertSame(['XML1', 'Lỗi thẻ BHYT', 'DM khoa-giường'], $wb->getSheetNames());
    }

    /** Chan loi mat dong im lang kieu 'yield from' (05/10/2026): du dong qua nhieu lo. */
    /** @test */
    public function tieu_de_dong_1_va_du_dong_qua_nhieu_lo()
    {
        $dong = [];
        for ($i = 1; $i <= 2500; $i++) {
            $dong[] = [$i, 'LK' . $i];
        }

        $ws = $this->ghi([new SheetGia('S', ['STT', 'Mã LK'], $dong)])->getSheet(0);

        $this->assertSame('STT', $ws->getCell('A1')->getValue());
        $this->assertSame(2501, $ws->getHighestRow());
        $this->assertEquals(2500, $ws->getCell('A2501')->getValue());
        $this->assertSame('LK2500', $ws->getCell('B2501')->getValue());
    }

    /** @test */
    public function kieu_o_giong_tep_cu_tru_cong_thuc()
    {
        $ws = $this->ghi([
            new SheetGia('S', ['a', 'b', 'c', 'd'], [['202610050800', '000007230917', null, '=SUM(A1)']]),
            new SheetGia('DM', ['a'], [['123']], ['kieu_o' => 'chu']),
        ]);
        $s = $ws->getSheet(0);

        $this->assertSame('n', $s->getCell('A2')->getDataType());
        $this->assertEquals(202610050800, $s->getCell('A2')->getValue());
        $this->assertSame('s', $s->getCell('B2')->getDataType());
        $this->assertSame('000007230917', $s->getCell('B2')->getValue());
        $this->assertNull($s->getCell('C2')->getValue());
        $this->assertSame('s', $s->getCell('D2')->getDataType(), 'Chuoi = phai la chu, khong la cong thuc');
        $this->assertSame('=SUM(A1)', $s->getCell('D2')->getValue());
        $this->assertSame('s', $ws->getSheet(1)->getCell('A2')->getDataType());
        $this->assertSame('123', $ws->getSheet(1)->getCell('A2')->getValue());
    }

    /** @test */
    public function style_tieu_de_cot_so_xuong_dong_va_do_rong()
    {
        $wb = $this->ghi([
            new SheetGia('Loi', ['STT', 'Ngay'], [[1, '202610050800']], [
                'do_rong' => ['A' => 5, 'B' => 13], 'cot_so' => ['B'],
                'xuong_dong' => true, 'tieu_de_can_giua' => true,
            ]),
            new SheetGia('DM', ['MA'], [['K01']], ['kieu_o' => 'chu']),
        ]);
        $s = $wb->getSheet(0);
        $dm = $wb->getSheet(1);

        $this->assertTrue($s->getStyle('A1')->getFont()->getBold());
        $this->assertSame('center', $s->getStyle('A1')->getAlignment()->getHorizontal());
        $this->assertTrue($s->getStyle('A2')->getAlignment()->getWrapText());
        $this->assertSame('0', $s->getStyle('B2')->getNumberFormat()->getFormatCode());
        $this->assertSame('General', $s->getStyle('A2')->getNumberFormat()->getFormatCode(),
            'Cot khong nam trong cot_so khong mang dinh dang 0');
        $this->assertEquals(5, $s->getColumnDimension('A')->getWidth());
        $this->assertEquals(13, $s->getColumnDimension('B')->getWidth());

        $this->assertTrue($dm->getStyle('A1')->getFont()->getBold());
        $this->assertNotSame('center', $dm->getStyle('A1')->getAlignment()->getHorizontal());
        $this->assertFalse($dm->getStyle('A2')->getAlignment()->getWrapText());
    }

    /** @test */
    public function hong_giua_chung_thi_khong_co_tep_dich_va_don_thu_muc_tam()
    {
        $dong = [];
        for ($i = 1; $i <= 2000; $i++) {
            $dong[] = [$i];
        }

        try {
            (new GhiExcelLuong($this->tamGoc))->ghi([new SheetGia('S', ['A'], $dong, [], 1500)], $this->dich());
            $this->fail('Phai nem lai loi');
        } catch (\RuntimeException $e) {
            $this->assertSame('hong giua chung', $e->getMessage());
        }

        $this->assertFileNotExists($this->dich());
        $this->assertSame([], glob($this->tamGoc . DIRECTORY_SEPARATOR . '*'), 'Thu muc tam phai duoc don');
    }

    /** @test */
    public function thanh_cong_cung_don_thu_muc_tam()
    {
        $this->ghi([new SheetGia('S', ['A'], [[1]])]);

        $this->assertSame([], glob($this->tamGoc . DIRECTORY_SEPARATOR . '*'));
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `DB_HOST=127.0.0.1 vendor/bin/phpunit tests/Unit/ExcelLuong/GhiExcelLuongTest.php`
Expected: 6 errors `Class 'App\Services\ExcelLuong\GhiExcelLuong' not found`.

- [ ] **Step 3: Write minimal implementation**

```php
<?php

namespace App\Services\ExcelLuong;

use Box\Spout\Common\Entity\Style\CellAlignment;
use Box\Spout\Writer\Common\Creator\Style\StyleBuilder;
use Box\Spout\Writer\Common\Creator\WriterEntityFactory;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

/**
 * Ghi nhieu sheet ra MOT tep xlsx THEO LUONG (Spout 3.3): moi dong xuong dia ngay, RAM ~ mot lo
 * 1000 dong bat ke so dong.
 *
 * Vi sao: PhpSpreadsheet giu moi o trong RAM - do 50.000 dong x 21 cot = 666 MB; ngay 05/10/2026
 * (~358.800 dong loi XML3176) het 4096M. Spout cung 50.000 dong: 8 s, ~0 MB.
 *
 * Doc sheet qua NguonSheet nen dung lai NGUYEN cac lop export Laravel Excel san co (headings,
 * map, generator/query, dinhDangLuong) - cot chi dinh nghia mot noi.
 *
 * Khong bao gio de tep dich do dang: ghi vao thu muc tam rieng moi lan chay, xong moi rename;
 * finally luon xoa thu muc tam. Moi loi nem lai cho job.
 */
class GhiExcelLuong
{
    protected $thuMucTamGoc;

    public function __construct(string $thuMucTamGoc = null)
    {
        $this->thuMucTamGoc = $thuMucTamGoc ?: storage_path('app/xuat-tam');
    }

    /**
     * @param array $sheets lop export theo thu tu sheet (vd Xml3176ErrorMultiSheetExport::sheets())
     * @param string $dich duong dan tuyet doi tep xlsx dich
     */
    public function ghi(array $sheets, string $dich): void
    {
        $tam = $this->thuMucTamGoc . DIRECTORY_SEPARATOR . uniqid('xuat_', true);

        if (!is_dir($tam) && !mkdir($tam, 0777, true)) {
            throw new \RuntimeException("Không tạo được thư mục tạm $tam");
        }

        $writer = null;
        $daDong = false;

        try {
            $tepTam = $tam . DIRECTORY_SEPARATOR . 'tep.xlsx';

            $writer = WriterEntityFactory::createXLSXWriter();
            $writer->setTempFolder($tam);
            $writer->openToFile($tepTam);

            $doRong = [];

            foreach (array_values($sheets) as $i => $export) {
                $nguon = new NguonSheet($export);

                if ($i > 0) {
                    $writer->addNewSheetAndMakeItCurrent();
                }

                $writer->getCurrentSheet()->setName($nguon->ten());
                $this->ghiSheet($writer, $nguon);
                $doRong[$i + 1] = $nguon->dinhDang()['do_rong'];
            }

            $writer->close();
            $daDong = true;

            (new ChenDoRongCot())->chen($tepTam, $doRong);

            $this->dua($tepTam, $dich);
        } finally {
            // Hong giua chung: dong writer de nha file handle (Windows khong xoa duoc tep dang mo).
            if ($writer !== null && !$daDong) {
                try {
                    $writer->close();
                } catch (\Throwable $e) {
                    // Loi goc dang duoc nem ra - loi dong khong che no.
                }
            }

            $this->xoaThuMuc($tam);
        }
    }

    protected function ghiSheet($writer, NguonSheet $nguon): void
    {
        $d = $nguon->dinhDang();

        $b = (new StyleBuilder())->setFontBold();
        if ($d['tieu_de_can_giua']) {
            $b->setCellAlignment(CellAlignment::CENTER);
        }
        if ($d['xuong_dong']) {
            $b->setShouldWrapText();
        }
        $tieuDe = $b->build();

        $b = new StyleBuilder();
        if ($d['xuong_dong']) {
            $b->setShouldWrapText();
        }
        $duLieu = $b->build();

        $b = (new StyleBuilder())->setFormat('0');
        if ($d['xuong_dong']) {
            $b->setShouldWrapText();
        }
        $duLieuSo = $b->build();

        $cotSo = [];
        foreach ($d['cot_so'] as $chu) {
            $cotSo[Coordinate::columnIndexFromString($chu) - 1] = true;
        }

        $writer->addRow(WriterEntityFactory::createRowFromArray($nguon->tieuDe(), $tieuDe));

        foreach ($nguon->dong() as $dong) {
            $o = [];

            foreach (array_values($dong) as $j => $v) {
                list($gia, $laSo) = KieuO::chuyen($v, $d['kieu_o']);

                $o[] = ($laSo && isset($cotSo[$j]))
                    ? WriterEntityFactory::createCell($gia, $duLieuSo)
                    : WriterEntityFactory::createCell($gia);
            }

            $writer->addRow(WriterEntityFactory::createRow($o, $duLieu));
        }
    }

    protected function dua(string $tepTam, string $dich): void
    {
        $thuMuc = dirname($dich);

        if (!is_dir($thuMuc) && !mkdir($thuMuc, 0777, true)) {
            throw new \RuntimeException("Không tạo được thư mục $thuMuc");
        }

        if (is_file($dich) && !unlink($dich)) {
            throw new \RuntimeException("Không xoá được tệp cũ $dich");
        }

        if (!rename($tepTam, $dich)) {
            throw new \RuntimeException('Không ghi được tệp xuất ra đĩa');
        }
    }

    protected function xoaThuMuc(string $thuMuc): void
    {
        if (!is_dir($thuMuc)) {
            return;
        }

        $it = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($thuMuc, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($it as $f) {
            $f->isDir() ? @rmdir($f->getPathname()) : @unlink($f->getPathname());
        }

        @rmdir($thuMuc);
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `DB_HOST=127.0.0.1 vendor/bin/phpunit tests/Unit/ExcelLuong`
Expected: `OK` — 6 test GhiExcelLuongTest + các test Task 1, 3, 4.

Nếu `style_tieu_de_cot_so_xuong_dong_va_do_rong` đỏ ở dòng `'General'` cho A2: KHÔNG nới test — kiểm lại `isset($cotSo[$j])` (chỉ số 0-based) trong `ghiSheet()`.

- [ ] **Step 5: Commit**

```bash
git add app/Services/ExcelLuong/GhiExcelLuong.php tests/Unit/ExcelLuong/GhiExcelLuongTest.php
git commit -m "feat(excel-luong): GhiExcelLuong ghi nhieu sheet bang Spout, khong de tep do dang

Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>"
```

---

### Task 6: Job dùng bộ ghi luồng theo công tắc

**Files:**
- Modify: `config/xml3176.php` (thêm khoá cuối mảng)
- Modify: `app/Jobs/XuatTepLoiXml3176Job.php` (`handle()`, khối `try` quanh `Excel::store`)
- Modify: `tests/Unit/Xml3176/TepXuat/XuatTepLoiXml3176JobTest.php`

**Interfaces:**
- Consumes: `GhiExcelLuong::ghi(array $sheets, string $dich): void` (Task 5); `Xml3176ErrorMultiSheetExport::sheets(): array` (sẵn có, 19 phần tử).
- Produces: `config('xml3176.xuat_tep_luong')` (bool, mặc định `true`).

- [ ] **Step 1: Write the failing tests**

Trong `XuatTepLoiXml3176JobTest`:

(a) Thêm `use App\Services\ExcelLuong\GhiExcelLuong;` vào khối `use`.

(b) Bốn test đang dùng đường cũ phải ép công tắc TẮT — thêm dòng đầu thân hàm
`config(['xml3176.xuat_tep_luong' => false]);` vào:
`chay_xong_thi_luu_tep_dung_cho_bang_bo_loc_da_luu_va_danh_dau_xong`,
`yeu_cau_khong_con_hoac_khong_o_cho_thi_khong_lam_gi`,
`sau_khi_xuat_bo_gan_gia_tri_tro_ve_mac_dinh`,
`excel_store_tra_false_thi_nem_ngoai_le_va_khong_danh_dau_xong`.

(c) Thêm các test mới:

```php
    /** Bo ghi gia: ghi lai tham so, tao tep that de job doc kich thuoc. */
    private function ghiGia()
    {
        $gia = new class extends GhiExcelLuong {
            public $goi = [];

            public function ghi(array $sheets, string $dich): void
            {
                $this->goi[] = [$sheets, $dich];
                if (!is_dir(dirname($dich))) {
                    mkdir(dirname($dich), 0777, true);
                }
                file_put_contents($dich, 'xlsx');
            }
        };
        app()->instance(GhiExcelLuong::class, $gia);

        return $gia;
    }

    /** @test */
    public function mac_dinh_bat_ghi_luong()
    {
        $this->assertTrue(config('xml3176.xuat_tep_luong'));
    }

    /** @test */
    public function cong_tac_bat_thi_ghi_luong_du_19_sheet_vao_dung_duong_dan()
    {
        config(['xml3176.xuat_tep_luong' => true]);
        $gia = $this->ghiGia();
        $y = $this->yeuCau();

        (new XuatTepLoiXml3176Job($y->id))->handle();

        $duongDan = 'xml3176-tep-xuat/' . $y->id . '.xlsx';
        $this->assertCount(1, $gia->goi);
        $this->assertCount(19, $gia->goi[0][0]);
        $this->assertSame(Storage::disk('local')->path($duongDan), $gia->goi[0][1]);

        $y = $y->fresh();
        $this->assertSame(Xml3176TepXuat::XONG, $y->trang_thai);
        $this->assertSame($duongDan, $y->duong_dan);
        $this->assertEquals(4, $y->kich_thuoc);
        $this->assertNotNull($y->xong_luc);
    }

    /** @test */
    public function ghi_luong_nem_loi_thi_khong_danh_dau_xong()
    {
        config(['xml3176.xuat_tep_luong' => true]);
        app()->instance(GhiExcelLuong::class, new class extends GhiExcelLuong {
            public function ghi(array $sheets, string $dich): void
            {
                throw new \RuntimeException('hong');
            }
        });
        $y = $this->yeuCau();

        try {
            (new XuatTepLoiXml3176Job($y->id))->handle();
            $this->fail('Phai nem lai loi');
        } catch (\RuntimeException $e) {
            $this->assertSame('hong', $e->getMessage());
        }

        $y = $y->fresh();
        $this->assertSame(Xml3176TepXuat::DANG_TAO, $y->trang_thai);
        $this->assertNull($y->duong_dan);
    }
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `DB_HOST=127.0.0.1 vendor/bin/phpunit tests/Unit/Xml3176/TepXuat/XuatTepLoiXml3176JobTest.php`
Expected: `mac_dinh_bat_ghi_luong` FAIL (`null` is not true); hai test ghi luồng FAIL (bộ ghi giả không được gọi — job vẫn gọi `Excel::store` thật). Các test cũ vẫn xanh.

- [ ] **Step 3: Implement — config**

Cuối mảng trong `config/xml3176.php` (trước `];` đóng) thêm:

```php
    //Xuất tệp lỗi XML3176 (XuatTepLoiXml3176Job) ghi THEO LUỒNG bằng Spout - RAM ~ một lô
    //1000 dòng. false = quay về Laravel Excel (giữ mọi ô trong RAM; ngày 05/10/2026 ~358.800
    //dòng hết 4096M). Quay lui trên prod: XML3176_XUAT_TEP_LUONG=false + khởi động lại dịch vụ
    //QLBV JobXuatTepXml3176.
    'xuat_tep_luong' => env('XML3176_XUAT_TEP_LUONG', true),
```

- [ ] **Step 4: Implement — job**

Thêm `use App\Services\ExcelLuong\GhiExcelLuong;` vào khối `use` của `XuatTepLoiXml3176Job`.

Thay khối `try { $daGhi = Excel::store(...); if ($daGhi === false) {...} } finally {...}` bằng:

```php
        $export = new Xml3176ErrorMultiSheetExport((array) $y->bo_loc, DanhSachCoSo::danhSach());

        try {
            if (config('xml3176.xuat_tep_luong', true)) {
                // Ghi THEO LUONG (Spout): RAM ~ mot lo 1000 dong bat ke so dong. Lay qua app()
                // chu khong type-hint handle() - bay tiem container Laravel 5.5.
                app(GhiExcelLuong::class)->ghi($export->sheets(), Storage::disk('local')->path($duongDan));
            } else {
                // Duong cu Laravel Excel - giu de quay lui nhanh tren prod (config xml3176.xuat_tep_luong).
                $daGhi = Excel::store($export, $duongDan, 'local');

                // Excel::store tra false khi khong chep duoc tep vao disk: khong danh dau xong.
                if ($daGhi === false) {
                    throw new \RuntimeException('Không ghi được tệp xuất ra đĩa');
                }
            }
        } finally {
            // Hai sheet danh muc dat StringValueBinder vao bien TINH (vendor/maatwebsite/excel/
            // src/Sheet.php) va khong tra lai. Worker nay chay nhieu lan xuat noi tiep: khong
            // tra lai thi cac lan sau ghi moi o thanh chuoi. Can khi cong tac tat.
            Cell::setValueBinder(new DefaultValueBinder());
        }
```

- [ ] **Step 5: Run tests to verify they pass**

Run: `DB_HOST=127.0.0.1 vendor/bin/phpunit tests/Unit/Xml3176/TepXuat/XuatTepLoiXml3176JobTest.php`
Expected: `OK (10 tests, ...)`

- [ ] **Step 6: Commit**

```bash
git add config/xml3176.php app/Jobs/XuatTepLoiXml3176Job.php tests/Unit/Xml3176/TepXuat/XuatTepLoiXml3176JobTest.php
git commit -m "feat(xml3176): job xuat tep loi ghi theo luong, cong tac quay lui XML3176_XUAT_TEP_LUONG

Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>"
```

---

### Task 7: Đối chiếu trên dữ liệu thật (04/10 từng ô, 05/10 bộ nhớ)

Chỉ ĐỌC CSDL (`.env` dev trỏ `192.168.200.68/qlbv` + HIS thật). KHÔNG chạy trên máy chủ prod.

**Files:**
- Create: `scripts/so-sanh-xuat-loi-xml3176.php`
- Create: `scripts/so-sanh-xlsx.py`
- Modify: `app/Jobs/XuatTepLoiXml3176Job.php` (comment số đo cạnh `memory_limit`)

**Interfaces:**
- Consumes: `GhiExcelLuong` (Task 5), `Xml3176ErrorMultiSheetExport`.

- [ ] **Step 1: Write the export script**

`scripts/so-sanh-xuat-loi-xml3176.php`:

```php
<?php

/**
 * Xuat tep loi XML3176 MOT ngay bang MOT duong, in thoi gian + dinh bo nho - de doi chieu
 * duong luong (GhiExcelLuong) voi duong cu (Laravel Excel). Moi duong mot tien trinh rieng
 * de dinh bo nho khong lan nhau.
 *
 * KHONG CHAY tren may chu san xuat: ngay lon duong cu ton hon 4 GB RAM va 20 phut.
 *
 * Chay:  php scripts/so-sanh-xuat-loi-xml3176.php <luong|cu> <Y-m-d> <date_type> [xml_filter_status]
 * Vd:    php scripts/so-sanh-xuat-loi-xml3176.php luong 2026-10-04 date_payment
 *        php scripts/so-sanh-xuat-loi-xml3176.php luong 2026-10-05 date_create has_error
 * Tep:   storage/app/so-sanh/<ngay>-<duong>.xlsx
 */

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

set_time_limit(0);
ini_set('memory_limit', '4096M');

list(, $duong, $ngay, $loaiNgay) = $argv + [null, null, null, null];
$trangThai = isset($argv[4]) ? $argv[4] : null;

if (!in_array($duong, ['luong', 'cu'], true) || !$ngay || !$loaiNgay) {
    fwrite(STDERR, "Cach dung: php scripts/so-sanh-xuat-loi-xml3176.php <luong|cu> <Y-m-d> <date_type> [xml_filter_status]\n");
    exit(2);
}

$loc = ['date_from' => "$ngay 00:00:00", 'date_to' => "$ngay 23:59:59", 'date_type' => $loaiNgay];
if ($trangThai) {
    $loc['xml_filter_status'] = $trangThai;
}

$tuongDoi = "so-sanh/$ngay-$duong.xlsx";
$tuyetDoi = storage_path('app/' . $tuongDoi);
$batDau = microtime(true);

register_shutdown_function(function () use ($batDau, $duong, $tuyetDoi) {
    printf("[%s] %.0f s, dinh %d MB, tep %s (%s)\n", $duong, microtime(true) - $batDau,
        memory_get_peak_usage(true) / 1048576, $tuyetDoi,
        is_file($tuyetDoi) ? number_format(filesize($tuyetDoi)) . ' byte' : 'KHONG CO');
});

$export = new App\Exports\Xml3176ErrorMultiSheetExport($loc, App\Services\BHYT\DanhSachCoSo::danhSach());

if ($duong === 'luong') {
    (new App\Services\ExcelLuong\GhiExcelLuong())->ghi($export->sheets(), $tuyetDoi);
} else {
    Maatwebsite\Excel\Facades\Excel::store($export, $tuongDoi, 'local');
}
```

- [ ] **Step 2: Write the comparison script**

`scripts/so-sanh-xlsx.py`:

```python
"""So hai tep xlsx tep loi XML3176: tung sheet, tung o (gia tri + kieu so/chu), numFmt cua o so,
tieu de (dam/can giua), do rong cot. In khac biet; ma thoat 1 neu co khac biet NGOAI o bat dau '='.

Chay: python scripts/so-sanh-xlsx.py <cu.xlsx> <moi.xlsx>
"""
import re
import sys
import zipfile

import openpyxl

sys.stdout.reconfigure(encoding='utf-8')


def do_rong(tep):
    """{ten sheet: {so cot: do rong}} doc thang <cols> (read_only cua openpyxl khong co)."""
    z = zipfile.ZipFile(tep)
    wb = z.read('xl/workbook.xml').decode('utf-8')
    rels = z.read('xl/_rels/workbook.xml.rels').decode('utf-8')
    dich = dict(re.findall(r'Id="([^"]+)"[^>]*Target="([^"]+)"', rels))
    dich.update({k: v for v, k in re.findall(r'Target="([^"]+)"[^>]*Id="([^"]+)"', rels)})
    ra = {}
    for ten, rid in re.findall(r'<sheet [^>]*name="([^"]+)"[^>]*r:id="([^"]+)"', wb):
        duong = dich[rid].lstrip('/')
        duong = duong if duong.startswith('xl/') else 'xl/' + duong
        dau = z.open(duong).read(200000).decode('utf-8', 'ignore')
        ra[ten] = {int(a): float(w) for a, w in re.findall(r'<col min="(\d+)" max="\d+" width="([\d.]+)"', dau)}
    return ra


def kieu(o):
    return 'trong' if o.value in (None, '') else ('so' if o.data_type == 'n' else 'chu')


def main(cu, moi):
    a = openpyxl.load_workbook(cu, read_only=True)
    b = openpyxl.load_workbook(moi, read_only=True)
    loi, cong_thuc = [], []
    if a.sheetnames != b.sheetnames:
        loi.append(f'Ten/thu tu sheet: {a.sheetnames} != {b.sheetnames}')
    ra_a, ra_b = do_rong(cu), do_rong(moi)
    for ten in a.sheetnames:
        if ten not in b.sheetnames:
            continue
        if ra_a.get(ten) != ra_b.get(ten):
            loi.append(f'[{ten}] do rong: {ra_a.get(ten)} != {ra_b.get(ten)}')
        da, db = a[ten].iter_rows(), b[ten].iter_rows()
        so_dong = 0
        for r, (ha, hb) in enumerate(zip(da, db), start=1):
            so_dong = r
            n = max(len(ha), len(hb))
            ha = list(ha) + [None] * (n - len(ha))
            hb = list(hb) + [None] * (n - len(hb))
            for c, (x, y) in enumerate(zip(ha, hb), start=1):
                vx = None if x is None or x.value == '' else x.value
                vy = None if y is None or y.value == '' else y.value
                if x is not None and x.data_type == 'f':
                    cong_thuc.append(f'[{ten}] R{r}C{c}: cu la cong thuc {vx!r}, moi {vy!r}')
                    continue
                if vx != vy or (x is not None and y is not None and kieu(x) != kieu(y)):
                    loi.append(f'[{ten}] R{r}C{c}: {vx!r} ({kieu(x) if x else "-"}) != {vy!r} ({kieu(y) if y else "-"})')
                elif x is not None and y is not None and kieu(x) == 'so' and x.number_format != y.number_format:
                    loi.append(f'[{ten}] R{r}C{c}: numFmt {x.number_format!r} != {y.number_format!r}')
                elif r == 1 and x is not None and y is not None and (
                        bool(x.font.b) != bool(y.font.b) or x.alignment.horizontal != y.alignment.horizontal):
                    loi.append(f'[{ten}] tieu de C{c}: dam/can {x.font.b}/{x.alignment.horizontal} != {y.font.b}/{y.alignment.horizontal}')
                if len(loi) > 50:
                    break
        con_a, con_b = sum(1 for _ in da), sum(1 for _ in db)
        if con_a or con_b:
            loi.append(f'[{ten}] so dong lech: cu them {con_a}, moi them {con_b} (sau dong {so_dong})')
        print(f'{ten:16} {so_dong - 1:>8} dong du lieu')
    print(f'\nO cong thuc o ban cu (khac biet CO Y): {len(cong_thuc)}')
    for d in cong_thuc[:20]:
        print('  ' + d)
    print(f'Khac biet KHAC: {len(loi)}')
    for d in loi[:50]:
        print('  ' + d)
    return 1 if loi else 0


if __name__ == '__main__':
    sys.exit(main(sys.argv[1], sys.argv[2]))
```

- [ ] **Step 3: Run the 04/10 parity check**

Run (hai lệnh, mỗi lệnh một tiến trình; mỗi lệnh có thể mất vài phút):

```bash
php scripts/so-sanh-xuat-loi-xml3176.php cu 2026-10-04 date_payment
php scripts/so-sanh-xuat-loi-xml3176.php luong 2026-10-04 date_payment
python scripts/so-sanh-xlsx.py storage/app/so-sanh/2026-10-04-cu.xlsx storage/app/so-sanh/2026-10-04-luong.xlsx
```

Expected: hai dòng `[cu] ... s, dinh ... MB` / `[luong] ...`; bảng số dòng từng sheet (XML3 64.392, XML4 9.516 theo đo 05/10 — số có thể tăng nếu dữ liệu ngày 04/10 được nạp thêm, nhưng HAI tệp phải bằng nhau); `Khac biet KHAC: 0`; mã thoát 0.

Nếu `Khac biet KHAC` > 0: DỪNG, dùng superpowers:systematic-debugging; KHÔNG sửa script để bỏ qua khác biệt.

- [ ] **Step 4: Run the 05/10 memory check (bộ lọc của job #11)**

```bash
php scripts/so-sanh-xuat-loi-xml3176.php luong 2026-10-05 date_create has_error
```

Expected: chạy xong (KHÔNG có `Allowed memory size ... exhausted`), `dinh` < 512 MB, tệp tồn tại. Đối chiếu số dòng sheet XML3/XML4 với:

```bash
python -c "import openpyxl,sys;sys.stdout.reconfigure(encoding='utf-8');wb=openpyxl.load_workbook('storage/app/so-sanh/2026-10-05-luong.xlsx',read_only=True);[print(n, sum(1 for _ in wb[n].iter_rows())-1) for n in wb.sheetnames]"
```

So với `xml3176_error_results` theo cùng bộ lọc (đo 05/10 lúc 14:0x: XML3 223.138, XML4 118.631 — số thật có thể lớn hơn vì dữ liệu ngày 05/10 còn được nạp thêm; điều kiện là tệp KHỚP số đếm cùng lúc, không phải khớp số cũ).

Nếu đỉnh ≥ 512 MB: DỪNG, báo người dùng kèm số đo — không hạ mục tiêu.

Mở `storage/app/so-sanh/2026-10-05-luong.xlsx` bằng Excel trên máy: mở được, cột rộng đúng, tiêu đề đậm.

- [ ] **Step 5: Ghi số đo vào comment job**

Trong `XuatTepLoiXml3176Job::handle()`, thay comment
`// Ngay 29/09/2026 (204.617 dong loi): ~700 giay, bo nho dinh ~2,5 GB.` bằng (điền số đo thật ở Step 3–4):

```php
        // Duong cu (Laravel Excel): 29/09/2026 204.617 dong ~700 s / ~2,5 GB; 05/10/2026 ~358.800
        // dong HET 4096M. Duong luong (GhiExcelLuong) do <ngay do>: 05/10 <N> s, dinh <M> MB.
        // memory_limit giu 4096M toi khi nghiem thu prod (spec 2026-10-05 ghi-luong).
```

- [ ] **Step 6: Commit**

```bash
git add scripts/so-sanh-xuat-loi-xml3176.php scripts/so-sanh-xlsx.py app/Jobs/XuatTepLoiXml3176Job.php
git commit -m "chore(xml3176): script doi chieu tep loi duong luong voi duong cu, ghi so do

Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>"
```

---

### Task 8: Cổng toàn bộ bộ test

- [ ] **Step 1: Run full Unit suite on branch**

```bash
DB_HOST=127.0.0.1 vendor/bin/phpunit --testsuite Unit 2>&1 | grep -E "^[0-9]+\) |^Tests:|^OK" > /tmp/nhanh.txt
```

- [ ] **Step 2: Run full Unit suite on main for baseline**

```bash
git stash -u -q; git checkout -q main
DB_HOST=127.0.0.1 vendor/bin/phpunit --testsuite Unit 2>&1 | grep -E "^[0-9]+\) |^Tests:|^OK" > /tmp/goc.txt
git checkout -q xuat-loi-xml3176-ghi-luong; git stash pop -q
```

- [ ] **Step 3: Compare failure lists**

```bash
diff <(sed 's/^[0-9]*) //' /tmp/goc.txt | sort | grep -v Tests:) <(sed 's/^[0-9]*) //' /tmp/nhanh.txt | sort | grep -v Tests:) && echo "GIONG HET MAIN"
```

Expected: `GIONG HET MAIN` (main hiện: 94 error + 3 failure có sẵn, đều không liên quan). Có dòng mới → sửa trước khi bàn giao.

- [ ] **Step 4: Hand off**

Báo người dùng: số đo Task 7, kết quả cổng, các bước nghiệm thu prod trong spec (xuất lại #11; quay lui bằng `XML3176_XUAT_TEP_LUONG=false`). KHÔNG merge/push khi chưa được yêu cầu.
