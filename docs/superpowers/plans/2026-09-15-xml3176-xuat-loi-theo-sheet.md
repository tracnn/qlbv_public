# Xuất lỗi XML3176 theo sheet — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Nút *Xuất danh sách lỗi* của màn XML3176 xuất ra 19 sheet cố định (XML1–XML15, XMLComplete, Lỗi thẻ BHYT, DM khoa-giường, DM NVYT), mỗi sheet lỗi có thêm cột Mã Khoa.

**Architecture:** Một lớp sheet lỗi dùng chung `Xml3176ErrorSheetExport` nhận tham số loại XML, lấy nguồn mã khoa từ một bảng ánh xạ thuần `Xml3176KhoaNguon`. `Xml3176ErrorMultiSheetExport` dựng 19 sheet. Mọi sheet lỗi cắt theo `Xml3176LocDanhSach::truyVanMaLk()` (đã có từ 11/09/2026). Hai sheet danh mục là lớp mới, không lọc theo ngày.

**Tech Stack:** Laravel 5.5, PHP 7.4, maatwebsite/excel 3.1.25, phpoffice/phpspreadsheet 1.30.4, PHPUnit 6.5, MySQL (dev DB `qlbv`).

**Spec:** `docs/superpowers/specs/2026-09-15-xml3176-xuat-loi-theo-sheet-design.md`

## Global Constraints

- **CẤM `RefreshDatabase`** và mọi trait/lệnh xoá dữ liệu trong test — bộ test từng xoá sạch CSDL dev `qlbv`.
- PHPUnit 6.5: `protected function setUp()` **không** có `: void`; dùng `assertContains`/`assertNotContains` cho chuỗi và mảng.
- Test đơn vị ở plan này chỉ dựng truy vấn (`toSql()`, `getBindings()`, `getQuery()->joins`), **không thực thi** truy vấn.
- PHP 7.4: không dùng `match`, không dùng named arguments, không dùng union type.
- Chú thích trong mã viết tiếng Việt **không dấu**, theo phong cách các tệp `app/Exports/*` và `app/Services/BHYT/*` hiện có.
- Danh sách 15 sheet XML là `XML1` … `XML15`, **không** lấy từ `Xml3176CheckTypes::LOAI` (chỉ có 12 loại).
- Thứ tự 19 sheet cố định: `XML1`…`XML15`, `XMLComplete`, `Lỗi thẻ BHYT`, `DM khoa-giường`, `DM NVYT`.
- Sheet lỗi: 19 cột, `Mã Khoa` ở vị trí thứ 5 (cột E), ngay sau `Mã Liên Kết`.
- Sheet lỗi cắt theo `Xml3176LocDanhSach::truyVanMaLk($loc, $danhSachCoSo)`; không cắt thêm ở mức dòng.
- **Hai sheet danh mục phải đứng CUỐI file.** Laravel Excel 3.1.25 đặt bộ gắn giá trị bằng `Cell::setValueBinder()` — một biến **tĩnh toàn cục** — khi mở sheet (`vendor/maatwebsite/excel/src/Sheet.php:155`) và không trả lại khi đóng. Sheet nào đứng sau sheet danh mục sẽ thừa hưởng `StringValueBinder`.
- `HeinCardErrorExport` phải giữ nguyên hành vi khi gọi không kèm tham số mới — `Qd130ErrorMultiSheetExport` dùng chung lớp này.
- Không bật cache ô của Laravel Excel. Nếu phép đo vượt giới hạn bộ nhớ thì dừng và báo, không tự xử lý.
- Commit message kết thúc bằng dòng `Co-Authored-By: Claude Opus 5 <noreply@anthropic.com>`.

## Điều chỉnh so với spec (đã quyết khi lập plan)

Hai phát hiện khi đọc mã thật, đã sửa vào spec trong cùng commit với plan:

1. **Lọc cơ sở cho sheet danh mục dùng quy ước "dòng không gắn cơ sở là dùng chung"**, không dùng `LocCoSo::ap` (khớp đúng bằng) như spec viết. `DepartmentBedCatalog::scopeCuaCoSo()` đã định nghĩa quy ước này: dòng có `ma_cskcb` rỗng dùng cho mọi cơ sở. Dùng khớp đúng bằng sẽ làm mất các dòng dùng chung. `medical_staffs` không có scope đó nên sheet NVYT áp cùng điều kiện ngay trong lớp.
2. **Hai sheet danh mục ghi mọi ô dưới dạng chuỗi** (`StringValueBinder`). Mã cơ sở `01929`, số định danh, mã BHXH có số 0 đứng đầu; bộ gắn giá trị mặc định đổi chúng thành số và **mất số 0**, làm hỏng việc dò mã giữa các sheet.

## Cấu trúc tệp

| Tệp | Việc | Trách nhiệm |
|---|---|---|
| `scripts/do-xuat-loi-xml3176.php` | Tạo (Task 1) | Đo thời gian + đỉnh bộ nhớ khi xuất file lỗi trên CSDL hiện tại |
| `app/Services/Xml3176/Xml3176KhoaNguon.php` | Tạo (Task 2) | Ánh xạ loại XML → bảng/cột/khoá nối của mã khoa. Thuần. |
| `app/Exports/Xml3176ErrorSheetExport.php` | Tạo (Task 3) | Một sheet lỗi cho một loại XML |
| `app/Exports/HeinCardErrorExport.php` | Sửa (Task 4) | Thêm tuỳ chọn cột Mã Khoa, mặc định tắt |
| `app/Exports/DmKhoaGiuongSheetExport.php` | Tạo (Task 5) | Sheet danh mục khoa–giường |
| `app/Exports/DmNvytSheetExport.php` | Tạo (Task 5) | Sheet danh mục nhân viên y tế |
| `app/Exports/Xml3176ErrorMultiSheetExport.php` | Sửa (Task 6) | Dựng 19 sheet |
| `app/Exports/Xml3176ErrorExport.php` | Xoá (Task 6) | Thay bằng `Xml3176ErrorSheetExport` |
| `scripts/kiem-xuat-loi-xml3176.php` | Tạo (Task 7) | Kiểm bất biến trên dữ liệu thật |

---

### Task 1: Đo bản xuất hiện tại (trước khi sửa)

Không sửa mã ứng dụng. Mục đích: có con số "trước" để so với bản mới (spec §8). **Phải chạy trên mã hiện tại, trước mọi task khác.**

**Files:**
- Create: `scripts/do-xuat-loi-xml3176.php`

**Interfaces:**
- Produces: script CLI `php scripts/do-xuat-loi-xml3176.php <ten-tep.xlsx>` in ra một dòng `thoi_gian_giay=… dinh_bo_nho_mb=… tep=…`. Task 7 dùng lại script này.

- [ ] **Step 1: Viết script đo**

```php
<?php

/**
 * Do thoi gian va dinh bo nho khi xuat file loi XML3176 tren CSDL hien tai.
 *
 * Xuat TOAN BO ho so (khoang ngay tao 2000-2099, khong loc gi khac) de do dung khoi
 * luong xau nhat. Khong chay khi dang dang nhap nen Xml3176LocDanhSach khong ap pham vi
 * nguoi nap - tuc la lay het.
 *
 * Chay: php scripts/do-xuat-loi-xml3176.php truoc.xlsx
 * Tep ra: storage/app/do-xuat-loi/<ten-tep>
 */

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$ten = isset($argv[1]) ? $argv[1] : 'do-xuat-loi.xlsx';

$loc = array_merge(
    array_fill_keys(App\Services\BHYT\Xml3176LocDanhSach::KHOA, null),
    [
        'date_from' => '2000-01-01 00:00:00',
        'date_to'   => '2099-12-31 23:59:59',
        'date_type' => 'date_create',
    ]
);

$batDau = microtime(true);

Maatwebsite\Excel\Facades\Excel::store(
    new App\Exports\Xml3176ErrorMultiSheetExport($loc, []),
    'do-xuat-loi/' . $ten,
    'local'
);

printf(
    "thoi_gian_giay=%.1f dinh_bo_nho_mb=%.0f tep=%s\n",
    microtime(true) - $batDau,
    memory_get_peak_usage(true) / 1048576,
    storage_path('app/do-xuat-loi/' . $ten)
);
```

- [ ] **Step 2: Chạy đo trên mã hiện tại**

Việc xuất có thể mất nhiều phút. Chạy **nền** (`run_in_background: true` của Bash) và chờ kết thúc, không đặt timeout 2 phút mặc định:

Run: `php scripts/do-xuat-loi-xml3176.php truoc.xlsx`
Expected: một dòng `thoi_gian_giay=… dinh_bo_nho_mb=… tep=…`, **hoặc** một lỗi hết bộ nhớ / hết thời gian. Cả hai đều là kết quả hợp lệ — ghi nguyên văn vào báo cáo.

- [ ] **Step 3: Ghi thêm số dòng lỗi và kiểu ô `Mã Liên Kết`**

Nếu Step 2 ra tệp, chạy:

```bash
php -r "require 'vendor/autoload.php'; \$r = PhpOffice\PhpSpreadsheet\IOFactory::createReader('Xlsx'); \$r->setReadDataOnly(true); \$r->setLoadSheetsOnly(['Lỗi XML']); \$s = \$r->load('storage/app/do-xuat-loi/truoc.xlsx')->getActiveSheet(); echo 'so_dong=', \$s->getHighestRow() - 1, ' kieu_o_D2=', \$s->getCell('D2')->getDataType(), ' gia_tri_D2=', \$s->getCell('D2')->getValue(), PHP_EOL;"
```

Expected: `so_dong=318446` (hoặc số dòng lỗi hiện có), kèm kiểu ô D2 (`n` là số, `s` là chuỗi). Ghi lại: nếu `n` thì `Mã Liên Kết` như `000002261158` đang **mất số 0 đứng đầu** trong bản hiện tại — đây là phát hiện để báo người dùng, **không sửa** trong plan này.

Nếu tệp quá lớn để đọc lại (hết bộ nhớ), bỏ qua Step 3 và ghi rõ lý do.

- [ ] **Step 4: Commit script**

```bash
git add scripts/do-xuat-loi-xml3176.php
git commit -m "chore(xml3176): script do thoi gian va bo nho khi xuat file loi

Co-Authored-By: Claude Opus 5 <noreply@anthropic.com>"
```

Báo cáo task **bắt buộc** chứa ba con số: thời gian, đỉnh bộ nhớ, số dòng (hoặc thông báo lỗi nguyên văn).

---

### Task 2: `Xml3176KhoaNguon` — ánh xạ nguồn mã khoa

**Files:**
- Create: `app/Services/Xml3176/Xml3176KhoaNguon.php`
- Test: `tests/Unit/Xml3176/Xml3176KhoaNguonTest.php`

**Interfaces:**
- Produces:
  - `Xml3176KhoaNguon::LOAI_XML` — `const array`, đúng 15 phần tử `['XML1', …, 'XML15']` theo thứ tự.
  - `Xml3176KhoaNguon::nguon(string $loai): ?array` — trả `['bang' => string, 'cot' => string, 'noiStt' => bool]` khi khoa lấy từ dòng gốc; trả `null` khi dùng khoa hồ sơ (`xml3176_xml1s.ma_khoa`). Loại không biết cũng trả `null`.

- [ ] **Step 1: Viết test hỏng**

```php
<?php

namespace Tests\Unit\Xml3176;

use App\Services\Xml3176\Xml3176KhoaNguon;
use Tests\TestCase;

/**
 * Spec muc 5: khoa lay tu chinh dong khi bang co cot khoa (XML2, XML3 theo ma_lk+stt;
 * XML7 cot ma_khoa_rv theo ma_lk), con lai lay khoa ho so XML1.MA_KHOA.
 */
class Xml3176KhoaNguonTest extends TestCase
{
    /** @test */
    public function danh_sach_loai_xml_du_15_dung_thu_tu()
    {
        $this->assertSame(
            ['XML1', 'XML2', 'XML3', 'XML4', 'XML5', 'XML6', 'XML7', 'XML8',
             'XML9', 'XML10', 'XML11', 'XML12', 'XML13', 'XML14', 'XML15'],
            Xml3176KhoaNguon::LOAI_XML
        );
    }

    /** @test */
    public function xml2_va_xml3_lay_ma_khoa_cua_dong_theo_ma_lk_va_stt()
    {
        $this->assertSame(
            ['bang' => 'xml3176_xml2s', 'cot' => 'ma_khoa', 'noiStt' => true],
            Xml3176KhoaNguon::nguon('XML2')
        );
        $this->assertSame(
            ['bang' => 'xml3176_xml3s', 'cot' => 'ma_khoa', 'noiStt' => true],
            Xml3176KhoaNguon::nguon('XML3')
        );
    }

    /** @test */
    public function xml7_lay_khoa_ra_vien_theo_ma_lk()
    {
        $this->assertSame(
            ['bang' => 'xml3176_xml7s', 'cot' => 'ma_khoa_rv', 'noiStt' => false],
            Xml3176KhoaNguon::nguon('XML7')
        );
    }

    /** @test */
    public function cac_loai_con_lai_dung_khoa_ho_so()
    {
        // XML1 chinh la bang khoa ho so; XML4 CO Y khong suy tu XML3 theo ma dich vu vi
        // mot ma dich vu co the o nhieu khoa trong cung ho so.
        foreach (['XML1', 'XML4', 'XML5', 'XML6', 'XML8', 'XML9', 'XML10', 'XML11',
                  'XML12', 'XML13', 'XML14', 'XML15', 'XMLComplete'] as $loai) {
            $this->assertNull(Xml3176KhoaNguon::nguon($loai), "$loai phai dung khoa ho so");
        }
    }

    /** @test */
    public function loai_la_dung_khoa_ho_so()
    {
        $this->assertNull(Xml3176KhoaNguon::nguon('XML99'));
        $this->assertNull(Xml3176KhoaNguon::nguon(''));
    }
}
```

- [ ] **Step 2: Chạy test, xác nhận hỏng**

Run: `./vendor/bin/phpunit tests/Unit/Xml3176/Xml3176KhoaNguonTest.php`
Expected: 5 lỗi `Class 'App\Services\Xml3176\Xml3176KhoaNguon' not found`

- [ ] **Step 3: Viết lớp**

```php
<?php

namespace App\Services\Xml3176;

/**
 * Nguon cua cot "Ma Khoa" tren tung sheet loi khi xuat file loi XML3176.
 *
 * Chi bon bang XML co cot khoa that: XML1, XML2, XML3 (ma_khoa) va XML7 (ma_khoa_rv).
 * Quy tac nguoi dung da chot: lay khoa cua CHINH DONG khi bang co cot khoa, khong co
 * hoac rong thi lay khoa ho so (xml3176_xml1s.ma_khoa).
 *
 * Do tren du lieu that 15/09/2026: dong loi noi ve dong goc bang (ma_lk, stt) khop 100%
 * o XML2/XML3/XML4, va cap (ma_lk, stt) khong trung - tuc phep noi khong nhan doi dong
 * loi. Day la so do tren DU LIEU, luoc do khong bao dam; scripts/kiem-xuat-loi-xml3176.php
 * kiem lai bat bien nay.
 *
 * Lop thuan: khong cham DB, khong doc config.
 */
class Xml3176KhoaNguon
{
    /**
     * 15 sheet XML, thu tu co dinh.
     *
     * KHONG lay tu Xml3176CheckTypes::LOAI: hang so do chi co 12 loai co checker, dung no
     * se mat sheet XML6/XML12/XML15 ma nguoi dung yeu cau luon co.
     */
    const LOAI_XML = [
        'XML1', 'XML2', 'XML3', 'XML4', 'XML5', 'XML6', 'XML7', 'XML8',
        'XML9', 'XML10', 'XML11', 'XML12', 'XML13', 'XML14', 'XML15',
    ];

    /**
     * Loai XML co khoa rieng tren tung dong.
     *
     * XML1 KHONG nam day du co cot ma_khoa: XML1 chinh la bang khoa ho so, truy van sheet
     * da noi san bang nay nen khong can noi them lan nua.
     *
     * XML4 CO Y khong nam day: bang khong co cot khoa, va suy tu XML3 theo ma dich vu thi
     * nhap nhang vi mot ma dich vu co the xuat hien o nhieu khoa trong cung ho so.
     */
    const NGUON = [
        'XML2' => ['bang' => 'xml3176_xml2s', 'cot' => 'ma_khoa',    'noiStt' => true],
        'XML3' => ['bang' => 'xml3176_xml3s', 'cot' => 'ma_khoa',    'noiStt' => true],
        'XML7' => ['bang' => 'xml3176_xml7s', 'cot' => 'ma_khoa_rv', 'noiStt' => false],
    ];

    /**
     * @param string $loai 'XML1'...'XML15' hoac 'XMLComplete'
     * @return array|null ['bang', 'cot', 'noiStt'], hoac null nghia la dung khoa ho so
     */
    public static function nguon($loai)
    {
        return array_key_exists((string) $loai, self::NGUON) ? self::NGUON[$loai] : null;
    }
}
```

- [ ] **Step 4: Chạy test, xác nhận qua**

Run: `./vendor/bin/phpunit tests/Unit/Xml3176/Xml3176KhoaNguonTest.php`
Expected: `OK (5 tests, …)`

- [ ] **Step 5: Commit**

```bash
git add app/Services/Xml3176/Xml3176KhoaNguon.php tests/Unit/Xml3176/Xml3176KhoaNguonTest.php
git commit -m "feat(xml3176): anh xa nguon ma khoa cho tung sheet loi

Co-Authored-By: Claude Opus 5 <noreply@anthropic.com>"
```

---

### Task 3: `Xml3176ErrorSheetExport` — một sheet lỗi cho một loại XML

Tạo lớp mới, **chưa** nối vào `Xml3176ErrorMultiSheetExport` (Task 6 làm). `Xml3176ErrorExport` cũ giữ nguyên ở task này.

**Files:**
- Create: `app/Exports/Xml3176ErrorSheetExport.php`
- Test: `tests/Unit/Xml3176/Xml3176ErrorSheetExportTest.php`

**Interfaces:**
- Consumes: `Xml3176KhoaNguon::nguon(string): ?array` (Task 2); `Xml3176LocDanhSach::KHOA`, `Xml3176LocDanhSach::truyVanMaLk(array $loc, array $danhSachCoSo): \Illuminate\Database\Query\Builder` (có sẵn).
- Produces:
  - `new Xml3176ErrorSheetExport(string $loai, array $loc, array $danhSachCoSo = [])`
  - `query(): \Illuminate\Database\Eloquent\Builder` — cột mã khoa chọn ra với bí danh `ma_khoa_xuat`.
  - `headings(): array` — 19 phần tử.
  - `title(): string` — trả đúng `$loai`.
  - `const DO_RONG` — `['A' => 5, …, 'S' => 12]`, 19 cột.
  - `const COT_NGAY` — `['H', 'J', 'K', 'L', 'M', 'N']`.

- [ ] **Step 1: Viết test hỏng**

```php
<?php

namespace Tests\Unit\Xml3176;

use App\Exports\Xml3176ErrorSheetExport;
use App\Services\BHYT\Xml3176LocDanhSach;
use Tests\TestCase;

/**
 * Spec muc 4.1, 5, 7.1. Chi dung truy van, KHONG thuc thi.
 */
class Xml3176ErrorSheetExportTest extends TestCase
{
    private function loc(array $ghiDe = [])
    {
        return array_merge(
            array_fill_keys(Xml3176LocDanhSach::KHOA, null),
            ['date_from' => '2026-09-01 00:00:00', 'date_to' => '2026-09-30 23:59:59', 'date_type' => 'date_in'],
            $ghiDe
        );
    }

    private function sheet($loai, array $ghiDe = [])
    {
        return new Xml3176ErrorSheetExport($loai, $this->loc($ghiDe), []);
    }

    /** SQL bo dau nhay de so khop khong phu thuoc grammar (MySQL dung backtick). */
    private function sql($loai)
    {
        return str_replace(['`', '"'], '', $this->sheet($loai)->query()->toSql());
    }

    /** Tim mot join theo ten bang (ke ca bi danh "bang as ten"). */
    private function join($loai, $bang)
    {
        foreach ((array) $this->sheet($loai)->query()->getQuery()->joins as $j) {
            if (strpos($j->table, $bang) === 0) {
                return $j;
            }
        }

        return null;
    }

    /** Cac cot xuat hien trong dieu kien ON cua mot join. */
    private function cotTrongJoin($j)
    {
        $cot = [];

        foreach ((array) $j->wheres as $w) {
            foreach (['first', 'second'] as $k) {
                if (isset($w[$k])) {
                    $cot[] = $w[$k];
                }
            }
        }

        return $cot;
    }

    /** @test */
    public function tieu_de_19_cot_ma_khoa_o_vi_tri_thu_5()
    {
        $h = $this->sheet('XML3')->headings();

        $this->assertCount(19, $h);
        $this->assertSame('Mã Liên Kết', $h[3]);
        $this->assertSame('Mã Khoa', $h[4]);
        $this->assertSame('Mã Bệnh Nhân', $h[5]);
    }

    /** @test */
    public function ten_sheet_la_loai_xml()
    {
        $this->assertSame('XML7', $this->sheet('XML7')->title());
        $this->assertSame('XMLComplete', $this->sheet('XMLComplete')->title());
    }

    /** @test */
    public function do_rong_du_19_cot_va_cot_ngay_khop_tieu_de()
    {
        // Chen cot Ma Khoa lam dich moi cot tu E. Test nay chan viec dinh dang so ap nham
        // cot sau khi dich - loi im lang, file van mo duoc.
        $h = $this->sheet('XML3')->headings();

        $this->assertCount(19, Xml3176ErrorSheetExport::DO_RONG);
        $this->assertSame(range('A', 'S'), array_keys(Xml3176ErrorSheetExport::DO_RONG));

        $tenCotNgay = [];
        foreach (Xml3176ErrorSheetExport::COT_NGAY as $chu) {
            $tenCotNgay[] = $h[ord($chu) - ord('A')];
        }

        $this->assertSame(
            ['Ngày Sinh', 'Ngày Vào', 'Ngày Ra', 'Ngày T.Toán', 'Ngày Y Lệnh', 'Ngày Kết Quả'],
            $tenCotNgay
        );
    }

    /** @test */
    public function chi_lay_dong_loi_cua_dung_loai_xml()
    {
        $q = $this->sheet('XML4')->query();

        $this->assertContains('xml3176_error_results.xml = ?', str_replace(['`', '"'], '', $q->toSql()));
        $this->assertContains('XML4', $q->getBindings());
    }

    /** @test */
    public function cat_theo_tap_ho_so_cua_man_danh_sach()
    {
        $sql = $this->sql('XML2');
        $this->assertContains('xml3176_error_results.ma_lk in (select xml3176_xml1s.ma_lk', $sql);

        // Bo loc cua man danh sach phai theo vao truy van con.
        $q = (new Xml3176ErrorSheetExport('XML2', $this->loc(['ma_khoa' => 'K01']), []))->query();
        $this->assertContains('K01', $q->getBindings());
    }

    /** @test */
    public function noi_danh_muc_ma_loi_theo_ca_xml_lan_error_code()
    {
        $j = $this->join('XML3', 'xml3176_error_catalogs');

        $this->assertNotNull($j, 'Khong noi danh muc ma loi');
        $this->assertSame('left', $j->type, 'Phai LEFT JOIN: ma loi chua co trong danh muc khong duoc lam mat dong');

        $cot = $this->cotTrongJoin($j);
        $this->assertContains('xml3176_error_catalogs.xml', $cot);
        $this->assertContains('xml3176_error_catalogs.error_code', $cot);
    }

    /** @test */
    public function xml3_noi_bang_nguon_theo_ma_lk_va_stt()
    {
        $j = $this->join('XML3', 'xml3176_xml3s');

        $this->assertNotNull($j, 'XML3 phai noi bang nguon de lay ma khoa cua dong');
        $this->assertSame('left', $j->type);

        $cot = $this->cotTrongJoin($j);
        $this->assertContains('khoa_nguon.ma_lk', $cot);
        $this->assertContains('khoa_nguon.stt', $cot);

        $this->assertContains("COALESCE(NULLIF(khoa_nguon.ma_khoa, ''), xml3176_xml1s.ma_khoa) as ma_khoa_xuat", $this->sql('XML3'));
    }

    /** @test */
    public function xml7_noi_theo_ma_lk_khong_theo_stt_va_dung_ma_khoa_rv()
    {
        $j = $this->join('XML7', 'xml3176_xml7s');

        $this->assertNotNull($j);

        $cot = $this->cotTrongJoin($j);
        $this->assertContains('khoa_nguon.ma_lk', $cot);
        $this->assertNotContains('khoa_nguon.stt', $cot, 'Bang XML7 khong co cot stt');

        $this->assertContains("COALESCE(NULLIF(khoa_nguon.ma_khoa_rv, ''), xml3176_xml1s.ma_khoa) as ma_khoa_xuat", $this->sql('XML7'));
    }

    /** @test */
    public function loai_dung_khoa_ho_so_khong_noi_bang_nguon()
    {
        foreach (['XML1', 'XML4', 'XMLComplete'] as $loai) {
            $this->assertNull($this->join($loai, 'xml3176_xml4s'), "$loai khong duoc noi XML4");
            $this->assertNotContains('khoa_nguon', $this->sql($loai), "$loai khong duoc noi bang nguon");
            $this->assertContains('xml3176_xml1s.ma_khoa as ma_khoa_xuat', $this->sql($loai));
        }
    }

    /** @test */
    public function informations_la_left_join()
    {
        // Bat bien spec muc 8: tong dong cac sheet = so dong loi. Inner join se lam mat dong
        // loi cua ho so thieu dong informations.
        $j = $this->join('XML3', 'xml3176_informations');

        $this->assertNotNull($j);
        $this->assertSame('left', $j->type);
    }

    /** @test */
    public function map_dat_ma_khoa_o_cot_thu_5_va_danh_so_tu_1()
    {
        $sheet = $this->sheet('XML3');

        $dong = (object) [
            'xml' => 'XML3', 'stt' => 7, 'ma_lk' => 'LK1', 'ma_khoa_xuat' => 'K01',
            'ma_bn' => 'BN1', 'ho_ten' => 'A', 'ngay_sinh' => '19800101', 'ma_the_bhyt' => 'T',
            'ngay_vao' => '1', 'ngay_ra' => '2', 'ngay_ttoan' => '3', 'ngay_yl' => '4', 'ngay_kq' => '5',
            'error_code' => 'XML3_X', 'catalog_error_name' => 'Ten loi', 'description' => 'Mo ta',
            'critical_error' => 1, 'imported_by' => 'u1', 'exported_by' => 'u2',
        ];

        $ra = $sheet->map($dong);

        $this->assertCount(19, $ra);
        $this->assertSame(1, $ra[0]);
        $this->assertSame('K01', $ra[4]);
        $this->assertSame('Ten loi', $ra[14]);
        $this->assertSame('Nghiêm trọng', $ra[16]);

        $this->assertSame(2, $sheet->map($dong)[0], 'STT tang tren tung sheet');
    }

    /** @test */
    public function ma_loi_chua_co_trong_danh_muc_thi_hien_ma_thay_vi_o_trong()
    {
        $dong = (object) [
            'xml' => 'XML3', 'stt' => 1, 'ma_lk' => 'LK1', 'ma_khoa_xuat' => null,
            'ma_bn' => null, 'ho_ten' => null, 'ngay_sinh' => null, 'ma_the_bhyt' => null,
            'ngay_vao' => null, 'ngay_ra' => null, 'ngay_ttoan' => null, 'ngay_yl' => null, 'ngay_kq' => null,
            'error_code' => 'XML3_MOI', 'catalog_error_name' => null, 'description' => null,
            'critical_error' => 0, 'imported_by' => null, 'exported_by' => null,
        ];

        $ra = $this->sheet('XML3')->map($dong);

        $this->assertSame('XML3_MOI', $ra[14]);
        $this->assertSame('Cảnh báo', $ra[16]);
    }
}
```

- [ ] **Step 2: Chạy test, xác nhận hỏng**

Run: `./vendor/bin/phpunit tests/Unit/Xml3176/Xml3176ErrorSheetExportTest.php`
Expected: 12 lỗi `Class 'App\Exports\Xml3176ErrorSheetExport' not found`

- [ ] **Step 3: Viết lớp**

```php
<?php

namespace App\Exports;

use App\Models\BHYT\Xml3176ErrorResult;
use App\Services\BHYT\Xml3176LocDanhSach;
use App\Services\Xml3176\Xml3176KhoaNguon;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * MOT sheet loi cho MOT loai XML trong file xuat loi XML3176.
 *
 * Thay Xml3176ErrorExport (mot sheet tron moi loai XML). Quy tac "xuat ra dung cai nhin
 * thay" giu nguyen: tap ho so cat theo Xml3176LocDanhSach, trong tung ho so lay het cac
 * dong loi cua loai nay, khong cat them o muc dong.
 *
 * KHONG dung ShouldAutoSize: do rong da dat co dinh o DO_RONG, con tu co gian tren sheet
 * XML4 (152.700 dong tren du lieu that) phai do tung o.
 */
class Xml3176ErrorSheetExport implements FromQuery, WithHeadings, WithStyles, WithEvents, WithMapping, WithTitle
{
    /**
     * Do rong tung cot. Cot E (Ma Khoa) moi chen; moi cot tu F tro di la cot cu dich sang
     * phai mot vi tri so voi Xml3176ErrorExport.
     */
    const DO_RONG = [
        'A' => 5,  'B' => 10, 'C' => 8,  'D' => 13, 'E' => 10,
        'F' => 14, 'G' => 22, 'H' => 13, 'I' => 18, 'J' => 13,
        'K' => 13, 'L' => 13, 'M' => 13, 'N' => 13, 'O' => 30,
        'P' => 50, 'Q' => 13, 'R' => 12, 'S' => 12,
    ];

    /** Cot ngay dang so YYYYMMDD[HHMM]: dinh dang so de Excel khong hien dang 2,03E+11. */
    const COT_NGAY = ['H', 'J', 'K', 'L', 'M', 'N'];

    protected $loai;
    protected $loc;
    protected $danhSachCoSo;
    protected $rowNumber = 0;

    /**
     * @param string $loai 'XML1'...'XML15' hoac 'XMLComplete'
     * @param array $loc bo loc doc tu man danh sach (Xml3176LocDanhSach::tuRequest())
     * @param array $danhSachCoSo ma co so => nhan
     */
    public function __construct($loai, array $loc, array $danhSachCoSo = [])
    {
        $this->loai = $loai;
        $this->loc = $loc;
        $this->danhSachCoSo = $danhSachCoSo;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query()
    {
        set_time_limit(1800);
        ini_set('memory_limit', '4096M');

        $query = Xml3176ErrorResult::query()
            ->whereIn('xml3176_error_results.ma_lk',
                Xml3176LocDanhSach::truyVanMaLk($this->loc, $this->danhSachCoSo))
            ->where('xml3176_error_results.xml', $this->loai)
            // Moi ma_lk trong truy van con deu lay tu xml3176_xml1s nen join nay khong lam
            // mat dong loi nao.
            ->join('xml3176_xml1s', 'xml3176_xml1s.ma_lk', '=', 'xml3176_error_results.ma_lk')
            // LEFT: ho so thieu dong informations van phai ra du dong loi.
            ->leftJoin('xml3176_informations', 'xml3176_informations.ma_lk', '=', 'xml3176_error_results.ma_lk')
            // Noi theo CA xml lan error_code: ban cu chi noi error_code, mot ma loi trung o
            // hai loai XML se nhan doi dong. LEFT: ma loi chua co trong danh muc van ra dong.
            ->leftJoin('xml3176_error_catalogs', function ($j) {
                $j->on('xml3176_error_catalogs.xml', '=', 'xml3176_error_results.xml')
                  ->on('xml3176_error_catalogs.error_code', '=', 'xml3176_error_results.error_code');
            })
            ->select(
                'xml3176_error_results.xml',
                'xml3176_error_results.stt',
                'xml3176_error_results.ma_lk',
                'xml3176_error_results.ngay_yl',
                'xml3176_error_results.ngay_kq',
                'xml3176_error_results.error_code',
                'xml3176_error_results.description',
                'xml3176_error_results.critical_error',
                'xml3176_error_catalogs.error_name as catalog_error_name',
                'xml3176_xml1s.ma_bn',
                'xml3176_xml1s.ho_ten',
                'xml3176_xml1s.ngay_sinh',
                'xml3176_xml1s.ma_the_bhyt',
                'xml3176_xml1s.ngay_vao',
                'xml3176_xml1s.ngay_ra',
                'xml3176_xml1s.ngay_ttoan',
                'xml3176_informations.imported_by',
                'xml3176_informations.exported_by'
            );

        $nguon = Xml3176KhoaNguon::nguon($this->loai);

        if ($nguon === null) {
            $query->selectRaw('xml3176_xml1s.ma_khoa as ma_khoa_xuat');
        } else {
            $query->leftJoin($nguon['bang'] . ' as khoa_nguon', function ($j) use ($nguon) {
                $j->on('khoa_nguon.ma_lk', '=', 'xml3176_error_results.ma_lk');

                if ($nguon['noiStt']) {
                    $j->on('khoa_nguon.stt', '=', 'xml3176_error_results.stt');
                }
            });

            // Ten cot lay tu hang so cua Xml3176KhoaNguon, khong tu dau vao nguoi dung.
            $query->selectRaw(
                "COALESCE(NULLIF(khoa_nguon.{$nguon['cot']}, ''), xml3176_xml1s.ma_khoa) as ma_khoa_xuat"
            );
        }

        return $query
            ->orderBy('xml3176_error_results.ma_lk')
            ->orderBy('xml3176_error_results.stt')
            ->orderBy('xml3176_error_results.id');
    }

    public function headings(): array
    {
        return [
            'STT',
            'Loại XML',
            'STT XML',
            'Mã Liên Kết',
            'Mã Khoa',
            'Mã Bệnh Nhân',
            'Họ Và Tên',
            'Ngày Sinh',
            'Mã Thẻ BHYT',
            'Ngày Vào',
            'Ngày Ra',
            'Ngày T.Toán',
            'Ngày Y Lệnh',
            'Ngày Kết Quả',
            'Mã Lỗi',
            'Mô Tả',
            'Loại lỗi',
            'Imported by',
            'Exported by',
        ];
    }

    public function map($data): array
    {
        $this->rowNumber++;

        return [
            $this->rowNumber,
            $data->xml,
            $data->stt,
            $data->ma_lk,
            $data->ma_khoa_xuat,
            $data->ma_bn,
            $data->ho_ten,
            $data->ngay_sinh,
            $data->ma_the_bhyt,
            $data->ngay_vao,
            $data->ngay_ra,
            $data->ngay_ttoan,
            $data->ngay_yl,
            $data->ngay_kq,
            // Cot tieu de ghi "Ma Loi" nhung tu ban cu da chua TEN loi tu danh muc; giu nguyen.
            // Ma loi chua co trong danh muc (LEFT JOIN ra null) thi hien ma de khong o trong.
            $data->catalog_error_name ?: $data->error_code,
            $data->description,
            $data->critical_error ? 'Nghiêm trọng' : 'Cảnh báo',
            $data->imported_by,
            $data->exported_by,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                foreach (self::DO_RONG as $cot => $rong) {
                    $sheet->getColumnDimension($cot)->setWidth($rong);
                }

                foreach (self::COT_NGAY as $cot) {
                    $sheet->getStyle($cot)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER);
                }
            },
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A:S')->getAlignment()->setWrapText(true);

        return [
            1 => [
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                'font' => ['bold' => true],
            ],
        ];
    }

    public function title(): string
    {
        return $this->loai;
    }
}
```

- [ ] **Step 4: Chạy test, xác nhận qua**

Run: `./vendor/bin/phpunit tests/Unit/Xml3176/Xml3176ErrorSheetExportTest.php`
Expected: `OK (12 tests, …)`

Nếu `noi_danh_muc_ma_loi_theo_ca_xml_lan_error_code` hoặc các test join báo `$j->type` không phải `'left'`, kiểm lại việc dùng `leftJoin` — **không** sửa test để khớp mã.

- [ ] **Step 5: Kiểm truy vấn chạy được trên MySQL thật**

Test ở Step 1 không thực thi truy vấn. Chạy một lần trên CSDL dev (chỉ đọc):

```bash
php -r "require 'vendor/autoload.php'; \$app = require 'bootstrap/app.php'; \$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap(); \$loc = array_merge(array_fill_keys(App\Services\BHYT\Xml3176LocDanhSach::KHOA, null), ['date_from'=>'2000-01-01 00:00:00','date_to'=>'2099-12-31 23:59:59','date_type'=>'date_create']); foreach (['XML1','XML2','XML3','XML7','XMLComplete'] as \$l) { \$q = (new App\Exports\Xml3176ErrorSheetExport(\$l, \$loc, []))->query(); \$r = \$q->first(); echo \$l, ' so_dong=', \$q->count(), ' ma_khoa_mau=', \$r ? \$r->ma_khoa_xuat : '-', PHP_EOL; }"
```

Expected: năm dòng, không lỗi SQL. Trên dữ liệu 15/09/2026: XML1 = 1836, XML2 = 32703, XML3 = 118390, XML7 = 1032, XMLComplete = 1982; `ma_khoa_mau` khác rỗng. Ghi nguyên văn vào báo cáo. Số khác vì dữ liệu đã được nạp lại thì ghi số thật và nói rõ.

- [ ] **Step 6: Commit**

```bash
git add app/Exports/Xml3176ErrorSheetExport.php tests/Unit/Xml3176/Xml3176ErrorSheetExportTest.php
git commit -m "feat(xml3176): lop sheet loi cho mot loai XML, them cot ma khoa

Co-Authored-By: Claude Opus 5 <noreply@anthropic.com>"
```

---

### Task 4: `HeinCardErrorExport` — tuỳ chọn cột Mã Khoa

**Files:**
- Modify: `app/Exports/HeinCardErrorExport.php`
- Test: `tests/Unit/Xml3176/HeinCardErrorExportMaKhoaTest.php`

**Interfaces:**
- Produces: `new HeinCardErrorExport($fromDate = null, $toDate = null, $maLkChoPhep = null, $khoaCauHinh = 'qd130xml', $coMaKhoa = false)`. Khi `$coMaKhoa = true`: tiêu đề 7 cột, `Mã Khoa` ở vị trí thứ 3 (sau `Mã điều trị`), truy vấn chọn thêm `ma_khoa_xuat`. Khi `false`: y hệt trước khi sửa.

- [ ] **Step 1: Viết test hỏng**

```php
<?php

namespace Tests\Unit\Xml3176;

use App\Exports\HeinCardErrorExport;
use Tests\TestCase;

/**
 * HeinCardErrorExport dung chung cho man QD130 (Qd130ErrorMultiSheetExport) va XML3176.
 * Cot Ma Khoa chi bat cho XML3176; goi mac dinh phai giu nguyen y het truoc khi sua.
 */
class HeinCardErrorExportMaKhoaTest extends TestCase
{
    private function mo($coMaKhoa)
    {
        return new HeinCardErrorExport('2026-09-01 00:00:00', '2026-09-30 23:59:59', null, 'xml3176', $coMaKhoa);
    }

    /** @test */
    public function goi_mac_dinh_giu_nguyen_tieu_de_va_truy_van()
    {
        $cu = new HeinCardErrorExport('2026-09-01 00:00:00', '2026-09-30 23:59:59');

        $this->assertSame(['STT', 'Mã điều trị', 'Mã kiểm tra', 'Mã kết quả', 'Ghi chú', 'Mã thẻ'], $cu->headings());

        $sql = str_replace(['`', '"'], '', $cu->query()->toSql());
        $this->assertNotContains('xml3176_xml1s', $sql);
        $this->assertNotContains('ma_khoa_xuat', $sql);
        $this->assertSame('Lỗi thẻ BHYT', $cu->title());
    }

    /** @test */
    public function bat_ma_khoa_thi_them_cot_sau_ma_dieu_tri()
    {
        $h = $this->mo(true)->headings();

        $this->assertCount(7, $h);
        $this->assertSame('Mã điều trị', $h[1]);
        $this->assertSame('Mã Khoa', $h[2]);
        $this->assertSame('Mã kiểm tra', $h[3]);
    }

    /** @test */
    public function bat_ma_khoa_thi_left_join_xml1_lay_khoa_ho_so()
    {
        $q = $this->mo(true)->query();
        $sql = str_replace(['`', '"'], '', $q->toSql());

        $this->assertContains('left join xml3176_xml1s', $sql);
        $this->assertContains('xml3176_xml1s.ma_khoa as ma_khoa_xuat', $sql);

        // Cot cua check_hein_card phai ghi ro bang de khong nhap nhang voi xml3176_xml1s.
        $this->assertContains('check_hein_cards.updated_at between', $sql);
    }

    /** @test */
    public function map_theo_dung_so_cot()
    {
        $dong = (object) [
            'ma_lk' => 'LK1', 'ma_khoa_xuat' => 'K01', 'ma_kiemtra' => '00',
            'ma_ketqua' => '000', 'ghi_chu' => 'g', 'ma_the' => 'T1',
        ];

        $coKhoa = $this->mo(true)->map($dong);
        $this->assertCount(7, $coKhoa);
        $this->assertSame('K01', $coKhoa[2]);

        $khong = $this->mo(false)->map($dong);
        $this->assertCount(6, $khong);
        $this->assertSame('LK1', $khong[1]);
    }
}
```

- [ ] **Step 2: Chạy test, xác nhận hỏng**

Run: `./vendor/bin/phpunit tests/Unit/Xml3176/HeinCardErrorExportMaKhoaTest.php`
Expected: `goi_mac_dinh_giu_nguyen_tieu_de_va_truy_van` QUA (hành vi cũ), ba test còn lại HỎNG (thiếu cột / thiếu join).

Nếu `check_hein_cards.updated_at between` không khớp vì tên bảng thật khác, chạy `php -r "require 'vendor/autoload.php'; \$app = require 'bootstrap/app.php'; \$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap(); echo (new App\Models\CheckBHYT\check_hein_card)->getTable();"` và sửa **cả test lẫn mã** theo tên bảng thật.

- [ ] **Step 3: Sửa lớp**

Thay constructor, `query()`, `headings()`, `registerEvents()`, `map()` của `app/Exports/HeinCardErrorExport.php`. Giữ nguyên `styles()` và `title()`. Bảng lấy bằng `getTable()` để không đoán tên:

```php
    protected $fromDate;
    protected $toDate;
    protected $rowNumber = 0;
    /**
     * Truy van con tra ve tap ma_lk duoc phep xuat, hoac null nghia la khong cat.
     *
     * Lop nay dung chung cho CA hai man QD130 va XML3176. Man QD130 goi khong kem tham
     * so nay nen giu nguyen hanh vi cu; rieng man XML3176 truyen vao tap ho so ma nguoi
     * dung dang nhin thay, vi truoc day sheet nay chi nhan khoang ngay va bo QUA moi bo
     * loc khac - ke ca ma co so, nen file xuat tron ca co so khac.
     *
     * Bang check_hein_card khong co cot ma_cskcb, nen cat theo ma_lk la cach duy nhat.
     */
    protected $maLkChoPhep;
    /** Khoa config chua danh sach ma kiem tra / ma ket qua duoc coi la loi. */
    protected $khoaCauHinh;
    /**
     * Them cot Ma Khoa (khoa ho so XML3176) sau cot Ma dieu tri.
     *
     * Mac dinh TAT: man QD130 dung chung lop nay va file cua man do phai giu nguyen.
     */
    protected $coMaKhoa;

    public function __construct($fromDate = null, $toDate = null, $maLkChoPhep = null,
        $khoaCauHinh = 'qd130xml', $coMaKhoa = false)
    {
        $this->fromDate = $fromDate;
        $this->toDate = $toDate;
        $this->maLkChoPhep = $maLkChoPhep;
        $this->khoaCauHinh = $khoaCauHinh;
        $this->coMaKhoa = (bool) $coMaKhoa;
    }

    public function query()
    {
        $dateFrom = $this->fromDate;
        $dateTo = $this->toDate;

        $formattedDateFromForTimestamp = Carbon::createFromFormat('Y-m-d H:i:s', $dateFrom)->format('Y-m-d H:i:s');
        $formattedDateToForTimestamp = Carbon::createFromFormat('Y-m-d H:i:s', $dateTo)->format('Y-m-d H:i:s');

        $khoa = $this->khoaCauHinh;

        if (!$this->coMaKhoa) {
            $query = check_hein_card::where(function($query) use ($khoa) {
                $query->whereIn('ma_kiemtra', config($khoa . '.hein_card_invalid.check_code', []))
                ->orWhereIn('ma_tracuu', config($khoa . '.hein_card_invalid.result_code', []));
            })
            ->whereBetween('updated_at', [$formattedDateFromForTimestamp, $formattedDateToForTimestamp]);

            if ($this->maLkChoPhep !== null) {
                $query->whereIn('ma_lk', $this->maLkChoPhep);
            }

            return $query;
        }

        // Co join xml3176_xml1s: MOI cot cua check_hein_card phai ghi ro bang, vi hai bang
        // cung co ma_lk, updated_at.
        $bang = (new check_hein_card)->getTable();

        $query = check_hein_card::query()
            ->leftJoin('xml3176_xml1s', 'xml3176_xml1s.ma_lk', '=', $bang . '.ma_lk')
            ->select($bang . '.*')
            ->selectRaw('xml3176_xml1s.ma_khoa as ma_khoa_xuat')
            ->where(function($query) use ($khoa, $bang) {
                $query->whereIn($bang . '.ma_kiemtra', config($khoa . '.hein_card_invalid.check_code', []))
                ->orWhereIn($bang . '.ma_tracuu', config($khoa . '.hein_card_invalid.result_code', []));
            })
            ->whereBetween($bang . '.updated_at', [$formattedDateFromForTimestamp, $formattedDateToForTimestamp]);

        if ($this->maLkChoPhep !== null) {
            $query->whereIn($bang . '.ma_lk', $this->maLkChoPhep);
        }

        return $query;
    }

    public function headings(): array
    {
        $h = [
            'STT',
            'Mã điều trị',
            'Mã kiểm tra',
            'Mã kết quả',
            'Ghi chú',
            'Mã thẻ',
        ];

        if ($this->coMaKhoa) {
            array_splice($h, 2, 0, ['Mã Khoa']);
        }

        return $h;
    }

    public function registerEvents(): array
    {
        $doRong = $this->coMaKhoa
            ? ['A' => 5, 'B' => 13, 'C' => 10, 'D' => 15, 'E' => 15, 'F' => 50, 'G' => 18]
            : ['A' => 5, 'B' => 13, 'C' => 15, 'D' => 15, 'E' => 50, 'F' => 18];

        return [
            AfterSheet::class => function(AfterSheet $event) use ($doRong) {
                $sheet = $event->sheet->getDelegate();
                foreach ($doRong as $cot => $rong) {
                    $sheet->getColumnDimension($cot)->setWidth($rong);
                }
            },
        ];
    }
```

và `map()`:

```php
    public function map($data): array
    {
        $this->rowNumber++;

        $dong = [
            $this->rowNumber,
            $data->ma_lk,
            \App\Services\BHYT\NhanMaThe::kiemTra($data->ma_kiemtra),
            \App\Services\BHYT\NhanMaThe::traCuu($data->ma_ketqua),
            $data->ghi_chu,
            $data->ma_the,
        ];

        if ($this->coMaKhoa) {
            array_splice($dong, 2, 0, [$data->ma_khoa_xuat]);
        }

        return $dong;
    }
```

- [ ] **Step 4: Chạy test, xác nhận qua**

Run: `./vendor/bin/phpunit tests/Unit/Xml3176/HeinCardErrorExportMaKhoaTest.php`
Expected: `OK (4 tests, …)`

Nếu `map_theo_dung_so_cot` hỏng vì `NhanMaThe::kiemTra`/`traCuu` ném lỗi với giá trị mẫu, đọc `app/Services/BHYT/NhanMaThe.php` và đổi giá trị mẫu trong test sang giá trị hợp lệ — **không** đổi cách `map()` gọi `NhanMaThe`.

- [ ] **Step 5: Kiểm màn QĐ130 không bị ảnh hưởng**

Run: `./vendor/bin/phpunit --filter Qd130`
Expected: kết quả giống hệt khi chạy trên commit trước task này (chạy `git stash` / so với báo cáo Task 3 nếu cần). Không có test nào mới hỏng.

- [ ] **Step 6: Commit**

```bash
git add app/Exports/HeinCardErrorExport.php tests/Unit/Xml3176/HeinCardErrorExportMaKhoaTest.php
git commit -m "feat(xml3176): tuy chon cot ma khoa cho sheet loi the BHYT, mac dinh tat

Co-Authored-By: Claude Opus 5 <noreply@anthropic.com>"
```

---

### Task 5: Hai sheet danh mục — khoa–giường và NVYT

**Files:**
- Create: `app/Exports/DmKhoaGiuongSheetExport.php`
- Create: `app/Exports/DmNvytSheetExport.php`
- Test: `tests/Unit/Xml3176/DmSheetExportTest.php`

**Interfaces:**
- Consumes: `App\Services\BHYT\LocCoSo::maHopLe($ma, array $danhSach): string` (có sẵn); `DepartmentBedCatalog::scopeCuaCoSo($q, $maCskcb)` (có sẵn).
- Produces:
  - `new DmKhoaGiuongSheetExport($maCskcb, array $danhSachCoSo = [])` — `title()` = `'DM khoa-giường'`, `const COT` = danh sách cột theo thứ tự.
  - `new DmNvytSheetExport($maCskcb, array $danhSachCoSo = [])` — `title()` = `'DM NVYT'`, `const COT`.
  - Cả hai kế thừa `PhpOffice\PhpSpreadsheet\Cell\StringValueBinder` và cài `WithCustomValueBinder`.

- [ ] **Step 1: Viết test hỏng**

```php
<?php

namespace Tests\Unit\Xml3176;

use App\Exports\DmKhoaGiuongSheetExport;
use App\Exports\DmNvytSheetExport;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Tests\TestCase;

/**
 * Spec muc 4.3, 6 va phan "Dieu chinh so voi spec" trong plan:
 * - xuat toan bo, KHONG loc theo ngay;
 * - loc co so theo quy uoc "dong khong gan co so la dung chung" (scopeCuaCoSo);
 * - moi o la chuoi de khong mat so 0 dung dau (01929, so dinh danh, ma BHXH).
 */
class DmSheetExportTest extends TestCase
{
    private function danhSach()
    {
        return ['01929' => 'Co so A', '37470' => 'Co so B'];
    }

    private function sql($export)
    {
        return str_replace(['`', '"'], '', $export->query()->toSql());
    }

    /** @test */
    public function ten_sheet()
    {
        $this->assertSame('DM khoa-giường', (new DmKhoaGiuongSheetExport(null))->title());
        $this->assertSame('DM NVYT', (new DmNvytSheetExport(null))->title());
    }

    /** @test */
    public function ghi_moi_o_duoi_dang_chuoi()
    {
        $this->assertInstanceOf(WithCustomValueBinder::class, new DmKhoaGiuongSheetExport(null));
        $this->assertInstanceOf(WithCustomValueBinder::class, new DmNvytSheetExport(null));
    }

    /** @test */
    public function cot_khoa_giuong_dung_thu_tu_tieu_de_viet_hoa()
    {
        $e = new DmKhoaGiuongSheetExport(null);

        $this->assertSame(
            ['ma_cskcb', 'ma_loai_kcb', 'ma_khoa', 'ten_khoa', 'ban_kham', 'giuong_pd',
             'giuong_2015', 'giuong_tk', 'giuong_hstc', 'giuong_hscc', 'ldlk', 'lien_khoa',
             'tu_ngay', 'den_ngay'],
            DmKhoaGiuongSheetExport::COT
        );
        $this->assertSame(array_map('strtoupper', DmKhoaGiuongSheetExport::COT), $e->headings());
    }

    /** @test */
    public function cot_nvyt_du_va_khong_co_cot_ky_thuat()
    {
        $this->assertSame(
            ['ma_cskcb', 'ma_loai_kcb', 'ma_khoa', 'ten_khoa', 'ma_bhxh', 'ho_ten', 'gioi_tinh',
             'so_dinh_danh', 'chucdanh_nn', 'vi_tri', 'macchn', 'ngaycap_cchn', 'noicap_cchn',
             'phamvi_cm', 'phamvi_cmbs', 'dvkt_khac', 'vb_phancong', 'thoigian_dk',
             'thoigian_ngay', 'thoigian_tuan', 'cskcb_khac', 'cskcb_cgkt', 'qd_cgkt',
             'tu_ngay', 'den_ngay'],
            DmNvytSheetExport::COT
        );

        foreach (['id', 'created_at', 'updated_at'] as $bo) {
            $this->assertNotContains($bo, DmNvytSheetExport::COT);
            $this->assertNotContains($bo, DmKhoaGiuongSheetExport::COT);
        }
    }

    /** @test */
    public function khong_loc_theo_ngay()
    {
        foreach ([new DmKhoaGiuongSheetExport('01929', $this->danhSach()),
                  new DmNvytSheetExport('01929', $this->danhSach())] as $e) {
            $sql = $this->sql($e);
            $this->assertNotContains('between', $sql);
            $this->assertNotContains('created_at', $sql);
            $this->assertNotContains('tu_ngay <', $sql);
            $this->assertNotContains('den_ngay >', $sql);
        }
    }

    /** @test */
    public function ma_co_so_hop_le_thi_loc_va_giu_dong_dung_chung()
    {
        foreach ([new DmKhoaGiuongSheetExport('01929', $this->danhSach()),
                  new DmNvytSheetExport('01929', $this->danhSach())] as $e) {
            $q = $e->query();
            $sql = $this->sql($e);

            $this->assertContains('01929', $q->getBindings());
            $this->assertContains('ma_cskcb is null', $sql, 'Phai giu dong khong gan co so (dung chung)');
        }
    }

    /** @test */
    public function ma_co_so_rong_hoac_khong_hop_le_thi_khong_loc()
    {
        foreach ([null, '', '99999'] as $ma) {
            foreach ([new DmKhoaGiuongSheetExport($ma, $this->danhSach()),
                      new DmNvytSheetExport($ma, $this->danhSach())] as $e) {
                $this->assertNotContains('ma_cskcb =', $this->sql($e), 'ma ' . var_export($ma, true) . ' khong duoc loc');
                $this->assertNotContains('99999', $e->query()->getBindings());
            }
        }
    }

    /** @test */
    public function map_theo_dung_thu_tu_cot()
    {
        $e = new DmNvytSheetExport(null);

        $dong = (object) array_combine(DmNvytSheetExport::COT, DmNvytSheetExport::COT);

        $this->assertSame(DmNvytSheetExport::COT, $e->map($dong));
    }
}
```

- [ ] **Step 2: Chạy test, xác nhận hỏng**

Run: `./vendor/bin/phpunit tests/Unit/Xml3176/DmSheetExportTest.php`
Expected: 8 lỗi `Class 'App\Exports\DmKhoaGiuongSheetExport' not found`

- [ ] **Step 3: Viết `DmKhoaGiuongSheetExport`**

```php
<?php

namespace App\Exports;

use App\Models\BHYT\DepartmentBedCatalog;
use App\Services\BHYT\LocCoSo;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Cell\StringValueBinder;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Sheet danh muc khoa-giuong trong file xuat loi XML3176.
 *
 * Bang tra cuu de nguoi dung do ten khoa tu ma khoa tren cac sheet loi. Xuat TOAN BO,
 * khong loc theo ngay hay theo ho so: chi giu ma co trong sheet loi thi khong do ra duoc
 * ma SAI, ma ma sai chinh la thu can tim. Giu ca dong het hieu luc; cot tu_ngay/den_ngay
 * cho thay hieu luc.
 *
 * Ke thua StringValueBinder: ma co so 01929 va cac ma khac co so 0 dung dau, bo gan gia
 * tri mac dinh doi chung thanh so va MAT so 0.
 */
class DmKhoaGiuongSheetExport extends StringValueBinder implements FromQuery, WithHeadings, WithMapping, WithStyles, WithTitle, WithCustomValueBinder
{
    /** Cot nghiep vu theo thu tu xuat. Bo id, created_at, updated_at. */
    const COT = [
        'ma_cskcb', 'ma_loai_kcb', 'ma_khoa', 'ten_khoa', 'ban_kham', 'giuong_pd',
        'giuong_2015', 'giuong_tk', 'giuong_hstc', 'giuong_hscc', 'ldlk', 'lien_khoa',
        'tu_ngay', 'den_ngay',
    ];

    protected $maCskcb;
    protected $danhSachCoSo;

    public function __construct($maCskcb, array $danhSachCoSo = [])
    {
        $this->maCskcb = $maCskcb;
        $this->danhSachCoSo = $danhSachCoSo;
    }

    /**
     * Loc co so theo scopeCuaCoSo: dong co ma_cskcb rong dung chung cho moi co so.
     * Ma khong hop le thi LocCoSo::maHopLe tra '' va scope bo qua loc.
     */
    public function query()
    {
        return DepartmentBedCatalog::query()
            ->cuaCoSo(LocCoSo::maHopLe($this->maCskcb, $this->danhSachCoSo))
            ->select(self::COT)
            ->orderBy('ma_cskcb')
            ->orderBy('ma_khoa')
            ->orderBy('tu_ngay');
    }

    public function headings(): array
    {
        // Ten truong viet hoa theo chuan: nguoi dung doi chieu voi tep danh muc gui cong BHXH.
        return array_map('strtoupper', self::COT);
    }

    public function map($data): array
    {
        $dong = [];

        foreach (self::COT as $cot) {
            $dong[] = $data->{$cot};
        }

        return $dong;
    }

    public function styles(Worksheet $sheet)
    {
        return [1 => ['font' => ['bold' => true]]];
    }

    public function title(): string
    {
        return 'DM khoa-giường';
    }
}
```

- [ ] **Step 4: Viết `DmNvytSheetExport`**

`medical_staffs` không có `scopeCuaCoSo`, và `config/danh_muc_bhyt.php` khai `medical_staff` là `theo_co_so => false` — nhưng bảng **có** cột `ma_cskcb` và dữ liệu thật đã gắn cơ sở cho mọi dòng. Áp cùng quy ước "dòng rỗng là dùng chung" ngay trong lớp để lọc được mà không làm mất dòng chưa gắn cơ sở:

```php
<?php

namespace App\Exports;

use App\Models\BHYT\MedicalStaff;
use App\Services\BHYT\LocCoSo;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Cell\StringValueBinder;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Sheet danh muc nhan vien y te trong file xuat loi XML3176.
 *
 * Cung ly do voi DmKhoaGiuongSheetExport: xuat toan bo, khong loc theo ngay hay ho so,
 * moi o la chuoi (so dinh danh, ma BHXH, ma co so co so 0 dung dau).
 */
class DmNvytSheetExport extends StringValueBinder implements FromQuery, WithHeadings, WithMapping, WithStyles, WithTitle, WithCustomValueBinder
{
    /** Cot nghiep vu theo thu tu xuat. Bo id, created_at, updated_at. */
    const COT = [
        'ma_cskcb', 'ma_loai_kcb', 'ma_khoa', 'ten_khoa', 'ma_bhxh', 'ho_ten', 'gioi_tinh',
        'so_dinh_danh', 'chucdanh_nn', 'vi_tri', 'macchn', 'ngaycap_cchn', 'noicap_cchn',
        'phamvi_cm', 'phamvi_cmbs', 'dvkt_khac', 'vb_phancong', 'thoigian_dk',
        'thoigian_ngay', 'thoigian_tuan', 'cskcb_khac', 'cskcb_cgkt', 'qd_cgkt',
        'tu_ngay', 'den_ngay',
    ];

    protected $maCskcb;
    protected $danhSachCoSo;

    public function __construct($maCskcb, array $danhSachCoSo = [])
    {
        $this->maCskcb = $maCskcb;
        $this->danhSachCoSo = $danhSachCoSo;
    }

    /**
     * MedicalStaff khong co scopeCuaCoSo nen ap cung quy uoc ngay tai day: dong co
     * ma_cskcb rong dung chung cho moi co so. Khop DUNG BANG (LocCoSo::ap) se lam mat
     * cac dong do.
     */
    public function query()
    {
        $query = MedicalStaff::query()->select(self::COT);

        $ma = LocCoSo::maHopLe($this->maCskcb, $this->danhSachCoSo);

        if ($ma !== '') {
            $query->where(function ($w) use ($ma) {
                $w->whereNull('ma_cskcb')
                  ->orWhere('ma_cskcb', '')
                  ->orWhere('ma_cskcb', $ma);
            });
        }

        return $query
            ->orderBy('ma_cskcb')
            ->orderBy('ma_khoa')
            ->orderBy('ho_ten');
    }

    public function headings(): array
    {
        return array_map('strtoupper', self::COT);
    }

    public function map($data): array
    {
        $dong = [];

        foreach (self::COT as $cot) {
            $dong[] = $data->{$cot};
        }

        return $dong;
    }

    public function styles(Worksheet $sheet)
    {
        return [1 => ['font' => ['bold' => true]]];
    }

    public function title(): string
    {
        return 'DM NVYT';
    }
}
```

- [ ] **Step 5: Chạy test, xác nhận qua**

Run: `./vendor/bin/phpunit tests/Unit/Xml3176/DmSheetExportTest.php`
Expected: `OK (8 tests, …)`

Test `ma_co_so_rong_hoac_khong_hop_le_thi_khong_loc` so chuỗi `ma_cskcb =`; nếu scope sinh `ma_cskcb = ?` ngay cả khi mã rỗng thì đó là lỗi mã, không phải lỗi test.

- [ ] **Step 6: Kiểm chạy được trên MySQL thật**

```bash
php -r "require 'vendor/autoload.php'; \$app = require 'bootstrap/app.php'; \$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap(); foreach ([new App\Exports\DmKhoaGiuongSheetExport(null), new App\Exports\DmNvytSheetExport(null), new App\Exports\DmKhoaGiuongSheetExport('01929', ['01929'=>'A']), new App\Exports\DmNvytSheetExport('01929', ['01929'=>'A'])] as \$e) { echo get_class(\$e), ' so_dong=', \$e->query()->count(), PHP_EOL; }"
```

Expected: không lỗi SQL. Trên dữ liệu 15/09/2026: khoa-giường không lọc = 7, NVYT không lọc = 18, NVYT lọc `01929` = 9. Ghi nguyên văn vào báo cáo.

- [ ] **Step 7: Commit**

```bash
git add app/Exports/DmKhoaGiuongSheetExport.php app/Exports/DmNvytSheetExport.php tests/Unit/Xml3176/DmSheetExportTest.php
git commit -m "feat(xml3176): sheet danh muc khoa-giuong va NVYT cho file xuat loi

Co-Authored-By: Claude Opus 5 <noreply@anthropic.com>"
```

---

### Task 6: Nối 19 sheet, xoá `Xml3176ErrorExport`

**Files:**
- Modify: `app/Exports/Xml3176ErrorMultiSheetExport.php` (thay toàn bộ)
- Delete: `app/Exports/Xml3176ErrorExport.php`
- Modify: `tests/Unit/BHYT/Xml3176ExportLocCoSoTest.php` (hai ca `xml3176_error_export_*`)
- Modify: `tests/Unit/Xml3176/Xml3176ExportParamsTest.php` (`lop_export_khong_con_tu_doc_request`)
- Test: `tests/Unit/Xml3176/Xml3176ErrorMultiSheetExportTest.php`

**Interfaces:**
- Consumes: `Xml3176KhoaNguon::LOAI_XML` (Task 2); `new Xml3176ErrorSheetExport(string $loai, array $loc, array $danhSachCoSo)` (Task 3); `new HeinCardErrorExport($from, $to, $maLkChoPhep, 'xml3176', true)` (Task 4); `new DmKhoaGiuongSheetExport($maCskcb, $danhSachCoSo)`, `new DmNvytSheetExport($maCskcb, $danhSachCoSo)` (Task 5).
- Produces: `Xml3176ErrorMultiSheetExport::sheets(): array` — đúng 19 phần tử theo thứ tự Global Constraints. Constructor giữ nguyên `(array $loc, array $danhSachCoSo = [])` nên controller **không** phải sửa.

- [ ] **Step 1: Viết test hỏng**

```php
<?php

namespace Tests\Unit\Xml3176;

use App\Exports\DmKhoaGiuongSheetExport;
use App\Exports\DmNvytSheetExport;
use App\Exports\HeinCardErrorExport;
use App\Exports\Xml3176ErrorMultiSheetExport;
use App\Exports\Xml3176ErrorSheetExport;
use App\Services\BHYT\Xml3176LocDanhSach;
use Tests\TestCase;

class Xml3176ErrorMultiSheetExportTest extends TestCase
{
    private function sheets(array $ghiDe = [])
    {
        $loc = array_merge(
            array_fill_keys(Xml3176LocDanhSach::KHOA, null),
            ['date_from' => '2026-09-01 00:00:00', 'date_to' => '2026-09-30 23:59:59', 'date_type' => 'date_in'],
            $ghiDe
        );

        return (new Xml3176ErrorMultiSheetExport($loc, ['01929' => 'A']))->sheets();
    }

    /** @test */
    public function du_19_sheet_dung_thu_tu_va_ten()
    {
        $ten = array_map(function ($s) { return $s->title(); }, $this->sheets());

        $this->assertSame([
            'XML1', 'XML2', 'XML3', 'XML4', 'XML5', 'XML6', 'XML7', 'XML8',
            'XML9', 'XML10', 'XML11', 'XML12', 'XML13', 'XML14', 'XML15',
            'XMLComplete', 'Lỗi thẻ BHYT', 'DM khoa-giường', 'DM NVYT',
        ], $ten);
    }

    /** @test */
    public function dung_lop_dung_cho_tung_vi_tri()
    {
        $s = $this->sheets();

        for ($i = 0; $i < 16; $i++) {
            $this->assertInstanceOf(Xml3176ErrorSheetExport::class, $s[$i], "sheet $i");
        }

        $this->assertInstanceOf(HeinCardErrorExport::class, $s[16]);
        $this->assertInstanceOf(DmKhoaGiuongSheetExport::class, $s[17]);
        $this->assertInstanceOf(DmNvytSheetExport::class, $s[18]);
    }

    /** @test */
    public function ten_sheet_hop_le_voi_excel()
    {
        foreach ($this->sheets() as $s) {
            $t = $s->title();
            $this->assertLessThanOrEqual(31, mb_strlen($t), "$t qua 31 ky tu");
            $this->assertSame(0, preg_match('#[:\\\\/?*\[\]]#', $t), "$t chua ky tu cam");
        }
    }

    /** @test */
    public function sheet_loi_the_bat_cot_ma_khoa_va_cat_theo_tap_ho_so()
    {
        $the = $this->sheets(['ma_khoa' => 'K01'])[16];

        $this->assertContains('Mã Khoa', $the->headings());
        $this->assertContains('K01', $the->query()->getBindings());
    }

    /** @test */
    public function sheet_danh_muc_nhan_ma_co_so_dang_loc()
    {
        $s = $this->sheets(['ma_cskcb' => '01929']);

        $this->assertContains('01929', $s[17]->query()->getBindings());
        $this->assertContains('01929', $s[18]->query()->getBindings());
    }
}
```

- [ ] **Step 2: Chạy test, xác nhận hỏng**

Run: `./vendor/bin/phpunit tests/Unit/Xml3176/Xml3176ErrorMultiSheetExportTest.php`
Expected: HỎNG — hiện chỉ có 2 sheet (`Lỗi XML`, `Lỗi thẻ BHYT`).

- [ ] **Step 3: Thay `Xml3176ErrorMultiSheetExport`**

```php
<?php

namespace App\Exports;

use App\Services\BHYT\Xml3176LocDanhSach;
use App\Services\Xml3176\Xml3176KhoaNguon;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * Bo xuat "danh sach loi" cua man XML3176 - 19 sheet, thu tu co dinh:
 *
 *   1-15  XML1 ... XML15      dong loi tung loai XML, LUON du 15 sheet ke ca sheet trong
 *   16    XMLComplete         loi lien bang
 *   17    Loi the BHYT        ket qua tra cuu the co loi
 *   18    DM khoa-giuong      bang tra cuu, xuat toan bo
 *   19    DM NVYT             bang tra cuu, xuat toan bo
 *
 * Sheet 1-17 cat theo DUNG tap ho so ma man danh sach dang hien thi
 * (Xml3176LocDanhSach). Sheet 18-19 chi loc theo ma co so dang chon.
 */
class Xml3176ErrorMultiSheetExport implements WithMultipleSheets
{
    /** @var array bo loc doc tu man danh sach (Xml3176LocDanhSach::tuRequest()) */
    protected $loc;
    /** @var array ma co so => nhan */
    protected $danhSachCoSo;

    public function __construct(array $loc, array $danhSachCoSo = [])
    {
        $this->loc = $loc;
        $this->danhSachCoSo = $danhSachCoSo;
    }

    public function sheets(): array
    {
        $sheets = [];

        foreach (Xml3176KhoaNguon::LOAI_XML as $loai) {
            $sheets[] = new Xml3176ErrorSheetExport($loai, $this->loc, $this->danhSachCoSo);
        }

        $sheets[] = new Xml3176ErrorSheetExport('XMLComplete', $this->loc, $this->danhSachCoSo);

        // Bang check_hein_card khong co cot ma_cskcb nen khong ap truc tiep duoc bo loc co
        // so; cat theo tap ma_lk cua man danh sach thi ap duoc CA bo loc.
        $sheets[] = new HeinCardErrorExport(
            array_get($this->loc, 'date_from'),
            array_get($this->loc, 'date_to'),
            Xml3176LocDanhSach::truyVanMaLk($this->loc, $this->danhSachCoSo),
            'xml3176',
            true
        );

        // Hai sheet danh muc PHAI dung cuoi. Chung cai StringValueBinder, ma Laravel Excel
        // dat bo gan gia tri bang bien TINH toan cuc khi mo sheet va khong tra lai khi dong
        // (vendor/maatwebsite/excel/src/Sheet.php) - sheet nao dung sau se thua huong.
        $sheets[] = new DmKhoaGiuongSheetExport(array_get($this->loc, 'ma_cskcb'), $this->danhSachCoSo);
        $sheets[] = new DmNvytSheetExport(array_get($this->loc, 'ma_cskcb'), $this->danhSachCoSo);

        return $sheets;
    }
}
```

- [ ] **Step 4: Xoá lớp cũ và sửa hai test đang dùng nó**

```bash
git rm app/Exports/Xml3176ErrorExport.php
```

Trong `tests/Unit/BHYT/Xml3176ExportLocCoSoTest.php`:
- Thay dòng `use App\Exports\Xml3176ErrorExport;` bằng `use App\Exports\Xml3176ErrorSheetExport;`.
- Trong hai test `xml3176_error_export_ap_bo_loc_co_so_khi_ma_hop_le` và `xml3176_error_export_khong_loc_khi_ma_khong_hop_le`, thay `new Xml3176ErrorExport(` bằng `new Xml3176ErrorSheetExport('XML3', `. Giữ nguyên các đối số còn lại và mọi khẳng định.
- Trong docblock của `khangDinhCoLocTrongTruyVanCon`, thay chữ `Xml3176ErrorExport` bằng `Xml3176ErrorSheetExport`.

Trong `tests/Unit/Xml3176/Xml3176ExportParamsTest.php`, test `lop_export_khong_con_tu_doc_request`: thay mảng
`['Xml3176XmlExport', 'Xml3176ErrorExport', 'Xml3176ErrorMultiSheetExport']`
bằng
`['Xml3176XmlExport', 'Xml3176ErrorSheetExport', 'Xml3176ErrorMultiSheetExport', 'DmKhoaGiuongSheetExport', 'DmNvytSheetExport']`.

- [ ] **Step 5: Kiểm không còn tham chiếu tới lớp đã xoá**

Run: `grep -rn "Xml3176ErrorExport\b" app tests routes resources --include=*.php`
Expected: chỉ còn các dòng **chú thích** (ví dụ trong `app/Services/BHYT/Xml3176LocDanhSach.php`). Không còn `new Xml3176ErrorExport` hay `use App\Exports\Xml3176ErrorExport`.

- [ ] **Step 6: Chạy toàn bộ test XML3176 và BHYT**

Run: `./vendor/bin/phpunit tests/Unit/Xml3176 && ./vendor/bin/phpunit tests/Unit/BHYT`
Expected: cả hai `OK`.

- [ ] **Step 7: Chạy toàn bộ test**

Run: `./vendor/bin/phpunit`
Expected: đúng **1** thất bại — `Tests\Unit\Ctdt\CtdtCauHinhTest::gui_len_cong_mac_dinh_tat` (mốc đỏ có chủ đích, **không sửa**). Mọi thất bại khác là do task này.

- [ ] **Step 8: Commit**

```bash
git add app/Exports/Xml3176ErrorMultiSheetExport.php tests/Unit/Xml3176/Xml3176ErrorMultiSheetExportTest.php tests/Unit/BHYT/Xml3176ExportLocCoSoTest.php tests/Unit/Xml3176/Xml3176ExportParamsTest.php
git commit -m "feat(xml3176): file xuat loi thanh 19 sheet, bo lop sheet loi gop chung

Co-Authored-By: Claude Opus 5 <noreply@anthropic.com>"
```

---

### Task 7: Kiểm bất biến trên dữ liệu thật và đo lại

**Files:**
- Create: `scripts/kiem-xuat-loi-xml3176.php`

**Interfaces:**
- Consumes: mọi lớp của Task 2–6; `scripts/do-xuat-loi-xml3176.php` (Task 1).
- Produces: báo cáo nghiệm thu (con số) — không có mã ứng dụng mới.

- [ ] **Step 1: Viết script kiểm**

```php
<?php

/**
 * Kiem bat bien cua file xuat loi XML3176 tren CSDL that (CHI DOC).
 *
 * Spec muc 8-9:
 *   1. Tong dong 16 sheet loi = so dong xml3176_error_results cua cung tap ho so.
 *   2. Cot ma khoa sheet XML2/XML3 bang ma_khoa cua dong goc theo (ma_lk, stt).
 *   3. Sheet XML6, XML12, XML15 ton tai va khong co dong.
 *
 * Chay: php scripts/kiem-xuat-loi-xml3176.php
 * Thoat ma 1 neu co bat bien vi pham.
 */

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Exports\Xml3176ErrorMultiSheetExport;
use App\Services\BHYT\Xml3176LocDanhSach;
use Illuminate\Support\Facades\DB;

$coBat = [
    'khong loc' => [],
    'ma_khoa=K01' => ['ma_khoa' => 'K01'],
];

$vipPham = 0;

foreach ($coBat as $ten => $ghiDe) {
    $loc = array_merge(
        array_fill_keys(Xml3176LocDanhSach::KHOA, null),
        ['date_from' => '2000-01-01 00:00:00', 'date_to' => '2099-12-31 23:59:59', 'date_type' => 'date_create'],
        $ghiDe
    );

    $sheets = (new Xml3176ErrorMultiSheetExport($loc, []))->sheets();

    $tongSheet = 0;
    $theoSheet = [];

    for ($i = 0; $i < 16; $i++) {
        $n = $sheets[$i]->query()->count();
        $theoSheet[$sheets[$i]->title()] = $n;
        $tongSheet += $n;
    }

    $maLk = Xml3176LocDanhSach::truyVanMaLk($loc, []);
    $tongLoi = DB::table('xml3176_error_results')->whereIn('ma_lk', $maLk)->count();

    // Dong loi co loai XML nam ngoai 16 sheet se khong xuat ra dau ca.
    $ngoai = DB::table('xml3176_error_results')
        ->whereIn('ma_lk', Xml3176LocDanhSach::truyVanMaLk($loc, []))
        ->whereNotIn('xml', array_keys($theoSheet))
        ->count();

    $khop = $tongSheet === $tongLoi;
    printf("[%s] tong 16 sheet=%d, tong dong loi=%d, loai XML ngoai 16 sheet=%d -> %s\n",
        $ten, $tongSheet, $tongLoi, $ngoai, $khop ? 'KHOP' : 'LECH');

    foreach ($theoSheet as $t => $n) {
        printf("    %-12s %d\n", $t, $n);
    }

    foreach (['XML6', 'XML12', 'XML15'] as $t) {
        if (!array_key_exists($t, $theoSheet)) {
            printf("    VI PHAM: thieu sheet %s\n", $t);
            $vipPham++;
        }
    }

    if (!$khop) {
        $vipPham++;
    }
}

// Mau ma khoa: 200 dong dau moi sheet XML2/XML3 so voi dong goc.
$locTatCa = array_merge(
    array_fill_keys(Xml3176LocDanhSach::KHOA, null),
    ['date_from' => '2000-01-01 00:00:00', 'date_to' => '2099-12-31 23:59:59', 'date_type' => 'date_create']
);

foreach (['XML2' => 'xml3176_xml2s', 'XML3' => 'xml3176_xml3s'] as $loai => $bang) {
    $sai = 0;
    $dong = (new App\Exports\Xml3176ErrorSheetExport($loai, $locTatCa, []))->query()->limit(200)->get();

    foreach ($dong as $d) {
        $goc = DB::table($bang)->where('ma_lk', $d->ma_lk)->where('stt', $d->stt)->value('ma_khoa');
        $ky = ($goc === null || $goc === '')
            ? DB::table('xml3176_xml1s')->where('ma_lk', $d->ma_lk)->value('ma_khoa')
            : $goc;

        if ((string) $ky !== (string) $d->ma_khoa_xuat) {
            $sai++;
        }
    }

    printf("[mau ma khoa %s] %d dong, sai %d\n", $loai, count($dong), $sai);
    $vipPham += $sai;
}

echo $vipPham === 0 ? "KET LUAN: DAT\n" : "KET LUAN: VI PHAM $vipPham\n";
exit($vipPham === 0 ? 0 : 1);
```

- [ ] **Step 2: Chạy kiểm**

Run: `php scripts/kiem-xuat-loi-xml3176.php`
Expected: cả hai tổ hợp `KHOP`, `loai XML ngoai 16 sheet=0`, hai mẫu mã khoa `sai 0`, dòng cuối `KET LUAN: DAT`. Ghi **toàn bộ** đầu ra vào báo cáo.

Nếu `LECH`: **dừng**, không sửa để khớp. Báo cáo con số từng sheet và điều tra phép nối nào nhân đôi hoặc làm mất dòng — đó là rủi ro spec §8 đã nêu.

Nếu `loai XML ngoai 16 sheet` khác 0: báo giá trị `xml` lạ (`SELECT DISTINCT xml FROM xml3176_error_results`) — đó là dòng lỗi sẽ không có sheet nào chứa.

- [ ] **Step 3: Đo bản mới trên cùng dữ liệu**

Chạy nền như Task 1:

Run: `php scripts/do-xuat-loi-xml3176.php sau.xlsx`
Expected: `thoi_gian_giay=… dinh_bo_nho_mb=… tep=…` hoặc lỗi nguyên văn.

- [ ] **Step 4: Đọc lại tệp, kiểm 19 sheet và sheet trống**

Nếu Step 3 ra tệp:

```bash
php -r "require 'vendor/autoload.php'; \$r = PhpOffice\PhpSpreadsheet\IOFactory::createReader('Xlsx'); echo implode(' | ', \$r->listWorksheetNames('storage/app/do-xuat-loi/sau.xlsx')), PHP_EOL; foreach (\$r->listWorksheetInfo('storage/app/do-xuat-loi/sau.xlsx') as \$i) { echo \$i['worksheetName'], '=', \$i['totalRows'], ' '; } echo PHP_EOL;"
```

Expected: 19 tên sheet đúng thứ tự Global Constraints; `XML6`, `XML12`, `XML15` có `totalRows = 1` (chỉ dòng tiêu đề); `DM khoa-giường` = 8, `DM NVYT` = 19 trên dữ liệu 15/09/2026 (7 và 18 dòng dữ liệu + tiêu đề).

`listWorksheetInfo` không nạp dữ liệu ô nên chạy được cả khi tệp lớn.

- [ ] **Step 5: Commit script kiểm**

```bash
git add scripts/kiem-xuat-loi-xml3176.php
git commit -m "chore(xml3176): script kiem bat bien file xuat loi 19 sheet tren du lieu that

Co-Authored-By: Claude Opus 5 <noreply@anthropic.com>"
```

Báo cáo task **bắt buộc** có bảng so sánh Task 1 và Task 7:

| | Trước (Task 1) | Sau (Task 7) |
|---|---|---|
| Thời gian (giây) | | |
| Đỉnh bộ nhớ (MB) | | |
| Số dòng lỗi xuất ra | | |
