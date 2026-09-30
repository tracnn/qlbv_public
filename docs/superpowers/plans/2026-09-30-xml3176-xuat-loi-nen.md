# Xuất danh sách lỗi XML3176 chạy nền — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Nút *Xuất danh sách lỗi* trên màn XML3176 không tải trực tiếp nữa mà xếp một yêu cầu; job nền tạo tệp 19 sheet, người bấm tải về ở mục *Tệp xuất của tôi* khi xong.

**Architecture:** Bảng `xml3176_tep_xuat` theo dõi yêu cầu. `Xml3176TepXuatService` lo tạo yêu cầu (chống bấm trùng), danh sách, tìm để tải, dọn dẹp 7 ngày, đánh dấu treo 90 phút. `XuatTepLoiXml3176Job` chạy trên kết nối hàng đợi riêng `xuat_tep` (`retry_after` 3600), hàng đợi `JobXuatTepXml3176`, dịch vụ NSSM riêng. `Xml3176ErrorSheetExport` đổi sang `FromGenerator` + `cursor()` (một truy vấn mỗi sheet thay vì một truy vấn mỗi 1.000 dòng).

**Tech Stack:** Laravel 5.5.50, PHP 7.4, PHPUnit 6.5, maatwebsite/excel 3.1.25 (PhpSpreadsheet), hàng đợi driver `database`, NSSM, jQuery + toastr (AdminLTE).

**Spec:** `docs/superpowers/specs/2026-09-30-xml3176-xuat-loi-nen-design.md`

## Global Constraints

- **CẤM `RefreshDatabase`** trong mọi test — bộ test từng xoá sạch CSDL `qlbv`. Test cần bảng dùng `Tests\Support\Xml3176RuleTestSupport::bootXml3176Sqlite([...])` (SQLite in-memory, ghi đè kết nối `mysql`).
- `.env` dev trỏ **CSDL thật** `192.168.200.68/qlbv`. **Không bao giờ** chạy `php artisan migrate` hay lệnh ghi nào. Mọi lệnh test chạy dạng `DB_HOST=127.0.0.1 php vendor/bin/phpunit <một tệp hoặc thư mục>`.
- PHPUnit 6.5: `protected function setUp()` **không** có `: void`; chuỗi dùng `assertContains`/`assertNotContains`.
- Test HTTP: user factory thật bị middleware `CheckRole` chặn 403 — dùng `Tests\Support\UserGiaCoQuyen::tao($id)` trong `actingAs()`.
- **Không** type-hint dịch vụ vào `Job::handle()` (bẫy tiêm container Laravel 5.5). Job này không cần tiêm gì: `handle()` không tham số.
- Tệp `.bat` là **CRLF, UTF-8**. `update.bat`: **không đổi một byte nào** từ đầu tệp tới hết dòng `git pull origin main` (710 byte, SHA-256 `e27b07e78f81d9a9da717584ef712f4db10cd90ce67be0141ee1c14e10ec7117`). Sửa `.bat` **chỉ** bằng kịch bản Python ghi ra tệp `.py` rồi chạy `python3 <tệp>.py` (chuỗi có `\` đi qua heredoc bash từng bị méo).
- Tệp PHP/Blade giữ nguyên kiểu xuống dòng hiện có: `git diff --numstat` chỉ được thấy đúng số dòng thật sự sửa.
- Chú thích trong mã: **tiếng Việt không dấu**. Chuỗi hiển thị cho người dùng: **có dấu**.
- Commit message không dấu, kết thúc bằng dòng `Co-Authored-By: <tên model thật đang viết mã> <noreply@anthropic.com>`.
- Hằng số cấu hình (đúng từng giá trị): kết nối `xuat_tep` (`retry_after` = 3600); hàng đợi `JobXuatTepXml3176`; dịch vụ `QLBV JobXuatTepXml3176`; giữ tệp 7 ngày; treo sau 90 phút; thư mục `xml3176-tep-xuat` trên disk `local`; kết nối `database` giữ `retry_after` = 300.
- Nền full suite trong môi trường này: **102 test đỏ** do môi trường. Chỉ so **tên** test đỏ giữa `main` và nhánh.

## Bản đồ tệp

| Tệp | Trách nhiệm | Task |
|---|---|---|
| `database/migrations/2026_09_30_100000_create_xml3176_tep_xuat_table.php` | bảng yêu cầu | 1 |
| `app/Models/BHYT/Xml3176TepXuat.php` | model + hằng trạng thái | 1 |
| `config/queue.php`, `config/xml3176.php` | kết nối `xuat_tep`, khoá `xuat_tep_*` | 1 |
| `app/Exports/Xml3176ErrorSheetExport.php` | `FromQuery` → `FromGenerator` | 2 |
| `app/Exports/Xml3176ErrorMultiSheetExport.php` | bỏ `set_time_limit(1800)`/`memory_limit` khỏi `sheets()` | 2 |
| `app/Services/Xml3176/Xml3176TepXuatService.php` | tạo / danh sách / tìm để tải / dọn / treo | 3 |
| `app/Jobs/XuatTepLoiXml3176Job.php` | job nền | 4 |
| `app/Http/Controllers/BHYT/BHYTXml3176Controller.php`, `routes/web.php` | 3 route mới, route cũ chuyển hướng | 5 |
| `resources/views/bhyt/xml3176/index.blade.php` | nút AJAX, mục *Tệp xuất của tôi* | 6 |
| `tests/Unit/Xml3176/Xml3176ExportParamsTest.php` | trỏ nút lỗi sang route mới | 6 |
| `update.bat`, `install_service.bat`, `remove_service.bat` | dịch vụ `QLBV JobXuatTepXml3176` | 7 |
| `readme.md`, `docs/quy-trinh-van-hanh/_nguon/build.js`, `.docx` | tài liệu | 8 |

Test mới nằm trong `tests/Unit/Xml3176/TepXuat/` (và một tệp `tests/Feature/Xml3176TepXuatControllerTest.php`).

---

### Task 1: Bảng yêu cầu, model, cấu hình hàng đợi

**Files:**
- Create: `database/migrations/2026_09_30_100000_create_xml3176_tep_xuat_table.php`
- Create: `app/Models/BHYT/Xml3176TepXuat.php`
- Modify: `config/queue.php` (thêm kết nối `xuat_tep` ngay sau khối `'database' => [...]`)
- Modify: `config/xml3176.php` (thêm bốn khoá ngay sau dòng `'sign_queue_name' => ...`)
- Test: `tests/Unit/Xml3176/TepXuat/CauHinhTepXuatTest.php`

**Interfaces:**
- Produces: bảng `xml3176_tep_xuat`; model `App\Models\BHYT\Xml3176TepXuat` với hằng `CHO='cho'`, `DANG_TAO='dang_tao'`, `XONG='xong'`, `LOI='loi'`, `LOAI_LOI='loi'`; `bo_loc` cast `array`; `bat_dau_luc`, `xong_luc` là date. Config: `queue.connections.xuat_tep`, `xml3176.xuat_tep_connection='xuat_tep'`, `xml3176.xuat_tep_queue_name='JobXuatTepXml3176'`, `xml3176.xuat_tep_giu_ngay=7`, `xml3176.xuat_tep_treo_phut=90`.

- [ ] **Step 1: Viết test đỏ**

`tests/Unit/Xml3176/TepXuat/CauHinhTepXuatTest.php`:

```php
<?php

namespace Tests\Unit\Xml3176\TepXuat;

use App\Models\BHYT\Xml3176TepXuat;
use Illuminate\Support\Facades\Schema;
use Tests\Support\Xml3176RuleTestSupport;
use Tests\TestCase;

class CauHinhTepXuatTest extends TestCase
{
    use Xml3176RuleTestSupport;

    protected function setUp()
    {
        parent::setUp();
        $this->bootXml3176Sqlite(['2026_09_30_100000_create_xml3176_tep_xuat_table.php']);
    }

    /** @test */
    public function bang_co_du_cot()
    {
        foreach (['id', 'user_id', 'loai', 'bo_loc', 'trang_thai', 'duong_dan', 'kich_thuoc', 'loi',
                  'bat_dau_luc', 'xong_luc', 'created_at', 'updated_at'] as $cot) {
            $this->assertTrue(Schema::hasColumn('xml3176_tep_xuat', $cot), "Thieu cot $cot");
        }
    }

    /** @test */
    public function model_luu_bo_loc_dang_mang()
    {
        $y = Xml3176TepXuat::create([
            'user_id' => 1, 'loai' => Xml3176TepXuat::LOAI_LOI,
            'bo_loc' => ['date_from' => '2026-09-29 00:00:00', 'ma_khoa' => 'K01'],
            'trang_thai' => Xml3176TepXuat::CHO,
        ]);

        $this->assertSame(['date_from' => '2026-09-29 00:00:00', 'ma_khoa' => 'K01'], $y->fresh()->bo_loc);
        $this->assertSame(['cho', 'dang_tao', 'xong', 'loi'],
            [Xml3176TepXuat::CHO, Xml3176TepXuat::DANG_TAO, Xml3176TepXuat::XONG, Xml3176TepXuat::LOI]);
    }

    /** @test */
    public function ket_noi_hang_doi_rieng_cho_viec_chay_lau()
    {
        // Job xuat chay 12-30 phut. Dung chung ket noi 'database' (retry_after 300) thi sau 5
        // phut hang doi coi job da chet va giao lai.
        $this->assertSame('database', config('queue.connections.xuat_tep.driver'));
        $this->assertSame('jobs', config('queue.connections.xuat_tep.table'));
        $this->assertGreaterThanOrEqual(3600, config('queue.connections.xuat_tep.retry_after'));
        $this->assertSame(300, config('queue.connections.database.retry_after'),
            'Ket noi database dung cho chuoi kiem-xuat-ky-gui, khong duoc doi');
    }

    /** @test */
    public function khoa_cau_hinh_xuat_tep()
    {
        $this->assertSame('xuat_tep', config('xml3176.xuat_tep_connection'));
        $this->assertSame('JobXuatTepXml3176', config('xml3176.xuat_tep_queue_name'));
        $this->assertSame(7, config('xml3176.xuat_tep_giu_ngay'));
        $this->assertSame(90, config('xml3176.xuat_tep_treo_phut'));
    }
}
```

- [ ] **Step 2: Chạy test, xác nhận đỏ**

Run: `DB_HOST=127.0.0.1 php vendor/bin/phpunit tests/Unit/Xml3176/TepXuat/CauHinhTepXuatTest.php`
Expected: lỗi `require_once(...2026_09_30_100000_create_xml3176_tep_xuat_table.php): failed to open stream`.

- [ ] **Step 3: Viết migration**

`database/migrations/2026_09_30_100000_create_xml3176_tep_xuat_table.php`:

```php
<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Yeu cau xuat tep chay nen cua man XML3176 (spec 2026-09-30).
 *
 * Nut "Xuat danh sach loi" tai truc tiep tra 504 tren prod: ngay 29/09/2026 (1.880 ho so,
 * 204.617 dong loi) mat 1.796 giay trong khi Cloudflare chi cho 100 giay. Nay moi lan bam la
 * mot dong o day; job nen tao tep, nguoi bam tai ve khi xong.
 */
class CreateXml3176TepXuatTable extends Migration
{
    public function up()
    {
        Schema::create('xml3176_tep_xuat', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('user_id')->index();
            $table->string('loai', 20);
            $table->text('bo_loc');
            $table->string('trang_thai', 20)->index();
            $table->string('duong_dan')->nullable();
            $table->unsignedBigInteger('kich_thuoc')->nullable();
            $table->text('loi')->nullable();
            $table->timestamp('bat_dau_luc')->nullable();
            $table->timestamp('xong_luc')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('xml3176_tep_xuat');
    }
}
```

- [ ] **Step 4: Viết model**

`app/Models/BHYT/Xml3176TepXuat.php`:

```php
<?php

namespace App\Models\BHYT;

use Illuminate\Database\Eloquent\Model;

/**
 * Mot yeu cau xuat tep chay nen cua man XML3176. Chi nguoi tao (user_id) thay va tai duoc.
 */
class Xml3176TepXuat extends Model
{
    const CHO = 'cho';
    const DANG_TAO = 'dang_tao';
    const XONG = 'xong';
    const LOI = 'loi';

    /** Loai tep: dot nay chi co xuat danh sach loi; de cho cho hai nut xuat khac sau nay. */
    const LOAI_LOI = 'loi';

    protected $table = 'xml3176_tep_xuat';

    protected $fillable = [
        'user_id', 'loai', 'bo_loc', 'trang_thai', 'duong_dan', 'kich_thuoc', 'loi',
        'bat_dau_luc', 'xong_luc',
    ];

    protected $casts = [
        'bo_loc' => 'array',
        'user_id' => 'integer',
        'kich_thuoc' => 'integer',
    ];

    protected $dates = ['bat_dau_luc', 'xong_luc'];
}
```

- [ ] **Step 5: Thêm cấu hình**

Trong `config/queue.php`, ngay sau dấu `],` đóng khối `'database' => [ ... 'retry_after' => 300, ],`, thêm:

```php

        // Ket noi RIENG cho job xuat tep XML3176 (XuatTepLoiXml3176Job): mot lan xuat ngay lon
        // chay 12-30 phut. Dung chung 'database' (retry_after 300) thi sau 5 phut hang doi coi
        // job da chet va giao lai - chay hai lan mot viec ton 30 phut va 2,5 GB bo nho.
        'xuat_tep' => [
            'driver' => 'database',
            'table' => 'jobs',
            'queue' => 'JobXuatTepXml3176',
            'retry_after' => 3600,
        ],
```

Trong `config/xml3176.php`, ngay sau dòng `'sign_queue_name' => ...`, thêm:

```php
    'xuat_tep_connection' => 'xuat_tep', //Ket noi hang doi rieng (retry_after 3600) cho job xuat tep chay nen
    'xuat_tep_queue_name' => 'JobXuatTepXml3176', //Hang doi rieng cho job xuat tep - khong chan chuoi kiem-xuat-ky-gui
    'xuat_tep_giu_ngay' => 7, //Tep xuat nen tu xoa sau so ngay nay (tep chua ho ten, ma the benh nhan)
    'xuat_tep_treo_phut' => 90, //Yeu cau dang_tao qua so phut nay coi nhu worker da dung, chuyen loi
```

- [ ] **Step 6: Chạy test, xác nhận xanh**

Run: `DB_HOST=127.0.0.1 php vendor/bin/phpunit tests/Unit/Xml3176/TepXuat/CauHinhTepXuatTest.php`
Expected: `OK (4 tests, ...)`

- [ ] **Step 7: Commit**

```bash
git add database/migrations/2026_09_30_100000_create_xml3176_tep_xuat_table.php app/Models/BHYT/Xml3176TepXuat.php config/queue.php config/xml3176.php tests/Unit/Xml3176/TepXuat/CauHinhTepXuatTest.php
git commit -m "feat(xml3176): bang xml3176_tep_xuat va ket noi hang doi rieng xuat_tep"
```
(Kèm dòng `Co-Authored-By` theo Global Constraints.)

---

### Task 2: Sheet lỗi đọc một lần bằng `cursor`

**Files:**
- Modify: `app/Exports/Xml3176ErrorSheetExport.php`
- Modify: `app/Exports/Xml3176ErrorMultiSheetExport.php` (`sheets()`)
- Test: `tests/Unit/Xml3176/TepXuat/SheetLoiDocMotLanTest.php`

**Interfaces:**
- Produces: `Xml3176ErrorSheetExport` implements `FromGenerator` (không còn `FromQuery`), có `public function generator(): \Generator`; `query()` giữ nguyên chữ ký và nội dung. `Xml3176ErrorMultiSheetExport::sheets()` không còn gọi `set_time_limit`/`ini_set` (job Task 4 tự đặt).

- [ ] **Step 1: Viết test đỏ**

`tests/Unit/Xml3176/TepXuat/SheetLoiDocMotLanTest.php`:

```php
<?php

namespace Tests\Unit\Xml3176\TepXuat;

use App\Exports\Xml3176ErrorMultiSheetExport;
use App\Exports\Xml3176ErrorSheetExport;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromGenerator;
use Maatwebsite\Excel\Concerns\FromQuery;
use Tests\Support\LocComment;
use Tests\Support\Xml3176RuleTestSupport;
use Tests\TestCase;

/**
 * FromQuery doc theo lo LIMIT/OFFSET: moi lo MySQL chay lai TOAN BO truy van nang (subquery +
 * 4 join + ORDER BY) - do 29/09/2026 moi lo ~7 giay, sheet XML3 ~102 lo. Doc mot lan bang
 * cursor(): 7,8 giay cho ca sheet. Noi dung, thu tu, dinh dang tep KHONG doi.
 */
class SheetLoiDocMotLanTest extends TestCase
{
    use Xml3176RuleTestSupport;
    use LocComment;

    protected function setUp()
    {
        parent::setUp();
        $this->bootXml3176Sqlite([
            '2026_01_09_152817_create_xml3176_xml1s_table.php',
            '2026_01_09_161708_create_xml3176_error_catalogs_table.php',
            '2026_01_09_161902_create_xml3176_error_results_table.php',
            '2026_01_09_162044_create_xml3176_informations_table.php',
        ]);

        foreach (['LK2' => 'Nguyen Van B', 'LK1' => 'Tran Thi A'] as $ma => $ten) {
            DB::table('xml3176_xml1s')->insert([
                'ma_lk' => $ma, 'stt' => 1, 'ho_ten' => $ten, 'ma_khoa' => 'K01',
                'ngay_ttoan' => '202609291000', 'ma_the_bhyt' => 'DN4010112345678',
            ]);
        }
        DB::table('xml3176_error_catalogs')->insert(['xml' => 'XML1', 'error_code' => 'E1', 'error_name' => 'Loi mot']);

        // Chen LECH thu tu de kiem ORDER BY ma_lk, stt, id.
        $dong = function ($ma, $stt, $ma_loi) {
            DB::table('xml3176_error_results')->insert([
                'xml' => 'XML1', 'ma_lk' => $ma, 'stt' => $stt, 'error_code' => $ma_loi,
                'description' => "$ma-$stt-$ma_loi", 'critical_error' => 0,
            ]);
        };
        $dong('LK2', 1, 'E1');
        $dong('LK1', 2, 'E1');
        $dong('LK1', 1, 'E2');
        // Dong cua loai XML khac khong duoc lot vao sheet XML1.
        DB::table('xml3176_error_results')->insert([
            'xml' => 'XML2', 'ma_lk' => 'LK1', 'stt' => 1, 'error_code' => 'X', 'description' => 'xml2', 'critical_error' => 0,
        ]);
    }

    private function loc()
    {
        return [
            'date_from' => '2026-09-29 00:00:00', 'date_to' => '2026-09-29 23:59:59',
            'date_type' => 'date_payment',
        ];
    }

    /** @test */
    public function sheet_la_from_generator_khong_con_from_query()
    {
        $s = new Xml3176ErrorSheetExport('XML1', $this->loc(), []);

        $this->assertInstanceOf(FromGenerator::class, $s);
        $this->assertNotInstanceOf(FromQuery::class, $s,
            'FromQuery doc phan trang OFFSET: ngay 29/09 mat 1.796 giay');
    }

    /** @test */
    public function doc_mot_lan_dung_dong_dung_thu_tu_dung_cot()
    {
        $s = new Xml3176ErrorSheetExport('XML1', $this->loc(), []);

        $dong = [];
        foreach ($s->generator() as $r) {
            $dong[] = $s->map($r);
        }

        $this->assertCount(3, $dong, 'Chi 3 dong loi cua XML1, khong lan XML2');
        // Thu tu ma_lk, stt, id: LK1/1, LK1/2, LK2/1 - STT chay 1..3.
        // PHP 7.4 + pdo_sqlite tra cot so dang CHUOI ('1') - dung assertEquals cho cot so.
        $this->assertEquals([1, 'XML1', 1, 'LK1', 'K01', null, 'Tran Thi A'], array_slice($dong[0], 0, 7));
        $this->assertSame('LK1-1-E2', $dong[0][15]);
        $this->assertSame('E2', $dong[0][14], 'Ma loi chua co trong danh muc thi hien ma');
        $this->assertEquals([2, 'LK1', 2], [$dong[1][0], $dong[1][3], $dong[1][2]]);
        $this->assertSame('Loi mot', $dong[1][14], 'Co trong danh muc thi hien ten loi');
        $this->assertEquals([3, 'LK2'], [$dong[2][0], $dong[2][3]]);
        $this->assertSame('Cảnh báo', $dong[2][16]);
    }

    /** @test */
    public function bo_xuat_khong_con_tu_dat_gioi_han_thoi_gian()
    {
        // sheets() chay BEN TRONG Excel::store, sau khi job da dat set_time_limit(0). De
        // set_time_limit(1800) o day thi no ghi de lai 30 phut - tren Windows do theo gio
        // thuc, lan xuat ngay lon co the sat 30 phut va bi PHP giet giua chung.
        $ma = $this->maKhongComment(app_path('Exports/Xml3176ErrorMultiSheetExport.php'));

        $this->assertNotContains('set_time_limit', $ma);
        $this->assertNotContains('memory_limit', $ma);
        $this->assertCount(19, (new Xml3176ErrorMultiSheetExport($this->loc(), []))->sheets());
    }
}
```

- [ ] **Step 2: Chạy test, xác nhận đỏ**

Run: `DB_HOST=127.0.0.1 php vendor/bin/phpunit tests/Unit/Xml3176/TepXuat/SheetLoiDocMotLanTest.php`
Expected: 3 test đỏ — không phải `FromGenerator`; `Call to undefined method ...::generator()`; tệp vẫn chứa `set_time_limit`.

- [ ] **Step 3: Đổi sang `FromGenerator`**

Trong `app/Exports/Xml3176ErrorSheetExport.php`:

- Dòng `use Maatwebsite\Excel\Concerns\FromQuery;` đổi thành `use Maatwebsite\Excel\Concerns\FromGenerator;`
- Khai báo lớp: `implements FromQuery, WithHeadings, ...` đổi `FromQuery` thành `FromGenerator` (giữ nguyên các interface còn lại, cùng thứ tự).
- Trong docblock đầu lớp, thêm đoạn (giữ nguyên các đoạn cũ):

```php
 *
 * DOC MOT LAN (FromGenerator + cursor), KHONG FromQuery: FromQuery doc theo lo LIMIT/OFFSET,
 * moi lo MySQL chay lai toan bo truy van (subquery + 4 join + ORDER BY). Do 29/09/2026: moi lo
 * ~7 giay, sheet XML3 ~102 lo; doc mot lan ca sheet 7,8 giay. query() giu lai de test va de
 * doc SQL.
```

- Ngay trước `public function query()`, thêm:

```php
    /**
     * Doc ca sheet bang MOT truy van (cursor), khong phan trang.
     *
     * @return \Generator
     */
    public function generator(): \Generator
    {
        foreach ($this->query()->cursor() as $dong) {
            yield $dong;
        }
    }
```

- Trong `query()`, thay hai dòng chú thích đầu hàm `// set_time_limit/memory_limit dat MOT LAN o Xml3176ErrorMultiSheetExport::sheets(),` và dòng tiếp theo của nó bằng:

```php
        // Gioi han thoi gian/bo nho do XuatTepLoiXml3176Job dat, khong dat o day.
```

- [ ] **Step 4: Bỏ đặt giới hạn trong `sheets()`**

Trong `app/Exports/Xml3176ErrorMultiSheetExport.php`, xoá ba dòng:

```php
        // Dat MOT LAN cho ca 19 sheet o day; neu de trong Xml3176ErrorSheetExport::query()
        // thi moi sheet goi lai se dat lai gio 16 lan (mot lan moi loai XML).
        set_time_limit(1800);
        ini_set('memory_limit', '4096M');
```

(bốn dòng: hai dòng chú thích và hai dòng lệnh) và đặt vào chỗ đó:

```php
        // KHONG dat set_time_limit/memory_limit o day: ham nay chay BEN TRONG Excel::store,
        // sau khi XuatTepLoiXml3176Job da dat set_time_limit(0). Dat 1800 o day se ghi de lai
        // 30 phut - tren Windows do theo gio thuc, lan xuat ngay lon co the bi giet giua chung.
```

- [ ] **Step 5: Chạy test mới và test cũ của hai lớp**

```bash
DB_HOST=127.0.0.1 php vendor/bin/phpunit tests/Unit/Xml3176/TepXuat/SheetLoiDocMotLanTest.php
DB_HOST=127.0.0.1 php vendor/bin/phpunit tests/Unit/Xml3176/Xml3176ErrorSheetExportTest.php
DB_HOST=127.0.0.1 php vendor/bin/phpunit tests/Unit/Xml3176/Xml3176ErrorMultiSheetExportTest.php
DB_HOST=127.0.0.1 php vendor/bin/phpunit tests/Unit/BHYT/Xml3176ExportLocCoSoTest.php
```

Expected: tất cả `OK` (các test cũ vẫn gọi `query()` — còn nguyên).

Nếu `doc_mot_lan_dung_dong_dung_thu_tu_dung_cot` đỏ vì chỉ số cột trong `map()` khác giả định của test (ví dụ cột `ma_bn` hay vị trí mô tả), đọc lại `map()` hiện có và **sửa chỉ số trong test cho khớp `map()`** — không đổi `map()`. Thứ tự cột của `map()` (0-based): 0 STT, 1 xml, 2 stt, 3 ma_lk, 4 ma_khoa_xuat, 5 ma_bn, 6 ho_ten, 7 ngay_sinh, 8 ma_the_bhyt, 9 ngay_vao, 10 ngay_ra, 11 ngay_ttoan, 12 ngay_yl, 13 ngay_kq, 14 tên/mã lỗi, 15 description, 16 loại lỗi, 17 imported_by, 18 exported_by.

- [ ] **Step 6: Commit**

```bash
git add app/Exports/Xml3176ErrorSheetExport.php app/Exports/Xml3176ErrorMultiSheetExport.php tests/Unit/Xml3176/TepXuat/SheetLoiDocMotLanTest.php
git commit -m "perf(xml3176): sheet loi doc mot lan bang cursor thay vi phan trang OFFSET"
```

---

### Task 3: `Xml3176TepXuatService`

**Files:**
- Create: `app/Services/Xml3176/Xml3176TepXuatService.php`
- Test: `tests/Unit/Xml3176/TepXuat/Xml3176TepXuatServiceTest.php`

**Interfaces:**
- Consumes: model và config Task 1. Job `App\Jobs\XuatTepLoiXml3176Job` với constructor `__construct($yeuCauId)` — **tạo trong Task 4**; Task 3 tạo trước một tệp job tối thiểu (xem Step 3) để service đẩy được, Task 4 viết thân job.
- Produces:
  - `const THU_MUC = 'xml3176-tep-xuat';`
  - `public function taoYeuCau(int $userId, array $boLoc): array` → `['yeuCau' => Xml3176TepXuat, 'trung' => bool]`
  - `public function danhSachCua(int $userId): array` → mảng các mảng `['id','tao_luc','bo_loc','trang_thai','so_truoc','so_phut','kich_thuoc','loi']`
  - `public function timDeTai(int $userId, int $id)` → `Xml3176TepXuat|null`
  - `public function donDep(): void`, `public function danhDauTreo(): void`
  - `public static function duongDanTep(Xml3176TepXuat $y): string` → `'xml3176-tep-xuat/{id}.xlsx'`
  - `public static function tenTepTai(Xml3176TepXuat $y): string`
  - `public static function tomTatBoLoc(array $boLoc): string`

- [ ] **Step 1: Viết test đỏ**

`tests/Unit/Xml3176/TepXuat/Xml3176TepXuatServiceTest.php`:

```php
<?php

namespace Tests\Unit\Xml3176\TepXuat;

use App\Jobs\XuatTepLoiXml3176Job;
use App\Models\BHYT\Xml3176TepXuat;
use App\Services\Xml3176\Xml3176TepXuatService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\Support\Xml3176RuleTestSupport;
use Tests\TestCase;

class Xml3176TepXuatServiceTest extends TestCase
{
    use Xml3176RuleTestSupport;

    /** @var Xml3176TepXuatService */
    private $s;

    protected function setUp()
    {
        parent::setUp();
        $this->bootXml3176Sqlite(['2026_09_30_100000_create_xml3176_tep_xuat_table.php']);
        Queue::fake();
        Storage::fake('local');
        Carbon::setTestNow(Carbon::create(2026, 9, 30, 9, 0, 0));
        $this->s = new Xml3176TepXuatService();
    }

    protected function tearDown()
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function boLoc(array $ghiDe = [])
    {
        return array_merge([
            'date_from' => '2026-09-29 00:00:00', 'date_to' => '2026-09-29 23:59:59',
            'date_type' => 'date_payment', 'hein_card_filter' => 'has_hein_card', 'ma_khoa' => null,
        ], $ghiDe);
    }

    private function yeuCau(array $gia)
    {
        return Xml3176TepXuat::create(array_merge([
            'user_id' => 1, 'loai' => Xml3176TepXuat::LOAI_LOI, 'bo_loc' => $this->boLoc(),
            'trang_thai' => Xml3176TepXuat::CHO,
        ], $gia));
    }

    // ─── Tạo yêu cầu ─────────────────────────────────────────────────────

    /** @test */
    public function tao_yeu_cau_ghi_dong_cho_va_day_dung_job_dung_ket_noi_dung_hang_doi()
    {
        $kq = $this->s->taoYeuCau(1, $this->boLoc());

        $this->assertFalse($kq['trung']);
        $this->assertSame(Xml3176TepXuat::CHO, $kq['yeuCau']->trang_thai);
        $this->assertSame(1, $kq['yeuCau']->user_id);
        Queue::assertPushedOn('JobXuatTepXml3176', XuatTepLoiXml3176Job::class, function ($job) use ($kq) {
            return $job->connection === 'xuat_tep' && $job->yeuCauId === $kq['yeuCau']->id;
        });
    }

    /** @test */
    public function bam_trung_cung_bo_loc_khi_dang_chay_thi_tra_yeu_cau_cu_khong_day_them()
    {
        $dau = $this->s->taoYeuCau(1, $this->boLoc())['yeuCau'];
        $dau->update(['trang_thai' => Xml3176TepXuat::DANG_TAO, 'bat_dau_luc' => Carbon::now()]);

        // Cung bo loc nhung thu tu khoa khac van la trung.
        $kq = $this->s->taoYeuCau(1, array_reverse($this->boLoc(), true));

        $this->assertTrue($kq['trung']);
        $this->assertSame($dau->id, $kq['yeuCau']->id);
        $this->assertSame(1, Xml3176TepXuat::count());
        Queue::assertPushed(XuatTepLoiXml3176Job::class, 1);
    }

    /** @test */
    public function bo_loc_khac_hoac_nguoi_khac_hoac_yeu_cau_cu_da_xong_thi_tao_moi()
    {
        $this->s->taoYeuCau(1, $this->boLoc());
        $this->s->taoYeuCau(1, $this->boLoc(['ma_khoa' => 'K01']));
        $this->s->taoYeuCau(2, $this->boLoc());
        Xml3176TepXuat::query()->update(['trang_thai' => Xml3176TepXuat::XONG]);
        $this->s->taoYeuCau(1, $this->boLoc());

        $this->assertSame(4, Xml3176TepXuat::count());
    }

    // ─── Danh sách ───────────────────────────────────────────────────────

    /** @test */
    public function danh_sach_chi_cua_minh_moi_nhat_truoc_kem_so_yeu_cau_dung_truoc()
    {
        $a = $this->yeuCau(['user_id' => 2]);                         // cho, cua nguoi khac, dung truoc
        $b = $this->yeuCau(['user_id' => 1]);                         // cho, cua minh
        $c = $this->yeuCau(['user_id' => 1, 'trang_thai' => Xml3176TepXuat::XONG, 'kich_thuoc' => 12345]);

        $ds = $this->s->danhSachCua(1);

        $this->assertSame([$c->id, $b->id], array_column($ds, 'id'));
        $this->assertSame(1, $ds[1]['so_truoc'], 'Yeu cau cho cua nguoi khac dung truoc van phai dem');
        $this->assertSame(12345, $ds[0]['kich_thuoc']);
        $this->assertContains('29/09/2026', $ds[0]['bo_loc']);
    }

    /** @test */
    public function dang_tao_qua_90_phut_bi_danh_dau_loi_khi_lay_danh_sach()
    {
        $treo = $this->yeuCau(['trang_thai' => Xml3176TepXuat::DANG_TAO, 'bat_dau_luc' => Carbon::now()->subMinutes(91)]);
        $moi = $this->yeuCau(['trang_thai' => Xml3176TepXuat::DANG_TAO, 'bat_dau_luc' => Carbon::now()->subMinutes(30)]);

        $this->s->danhSachCua(1);

        $this->assertSame(Xml3176TepXuat::LOI, $treo->fresh()->trang_thai);
        $this->assertContains('Quá thời gian', $treo->fresh()->loi);
        $this->assertSame(Xml3176TepXuat::DANG_TAO, $moi->fresh()->trang_thai);
    }

    /** @test */
    public function yeu_cau_cu_hon_7_ngay_bi_xoa_cung_tep()
    {
        $cu = $this->yeuCau(['trang_thai' => Xml3176TepXuat::XONG]);
        $cu->created_at = Carbon::now()->subDays(8);
        $cu->save();
        $cu->update(['duong_dan' => Xml3176TepXuatService::duongDanTep($cu)]);
        Storage::disk('local')->put($cu->duong_dan, 'x');
        $moi = $this->yeuCau(['trang_thai' => Xml3176TepXuat::XONG]);

        $this->s->danhSachCua(1);

        $this->assertNull(Xml3176TepXuat::find($cu->id));
        $this->assertFalse(Storage::disk('local')->exists('xml3176-tep-xuat/' . $cu->id . '.xlsx'));
        $this->assertNotNull(Xml3176TepXuat::find($moi->id));
    }

    // ─── Tải ─────────────────────────────────────────────────────────────

    /** @test */
    public function chi_tai_duoc_yeu_cau_cua_minh_da_xong_va_con_tep()
    {
        $xong = $this->yeuCau(['trang_thai' => Xml3176TepXuat::XONG]);
        $xong->update(['duong_dan' => Xml3176TepXuatService::duongDanTep($xong)]);
        Storage::disk('local')->put($xong->duong_dan, 'x');
        $chuaXong = $this->yeuCau([]);
        $matTep = $this->yeuCau(['trang_thai' => Xml3176TepXuat::XONG, 'duong_dan' => 'xml3176-tep-xuat/khong-co.xlsx']);

        $this->assertSame($xong->id, $this->s->timDeTai(1, $xong->id)->id);
        $this->assertNull($this->s->timDeTai(2, $xong->id), 'Nguoi khac khong tai duoc');
        $this->assertNull($this->s->timDeTai(1, $chuaXong->id));
        $this->assertNull($this->s->timDeTai(1, $matTep->id));
        $this->assertNull($this->s->timDeTai(1, 999));
    }

    /** @test */
    public function ten_tep_va_duong_dan()
    {
        $y = $this->yeuCau([]);

        $this->assertSame('xml3176-tep-xuat/' . $y->id . '.xlsx', Xml3176TepXuatService::duongDanTep($y));
        $this->assertSame('xml3176_loi_20260929_20260930090000.xlsx', Xml3176TepXuatService::tenTepTai($y));
    }
}
```

- [ ] **Step 2: Chạy test, xác nhận đỏ**

Run: `DB_HOST=127.0.0.1 php vendor/bin/phpunit tests/Unit/Xml3176/TepXuat/Xml3176TepXuatServiceTest.php`
Expected: lỗi `Class 'App\Services\Xml3176\Xml3176TepXuatService' not found`.

- [ ] **Step 3: Tạo khung job (thân job viết ở Task 4)**

`app/Jobs/XuatTepLoiXml3176Job.php`:

```php
<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

/**
 * Tao tep "danh sach loi" XML3176 chay nen cho mot yeu cau trong xml3176_tep_xuat.
 */
class XuatTepLoiXml3176Job implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** Hong thi ghi loi, KHONG tu chay lai mot viec ton 12-30 phut. */
    public $tries = 1;

    /** @var int id dong xml3176_tep_xuat - nhan id chu khong nhan model: job co the cho lau */
    public $yeuCauId;

    public function __construct($yeuCauId)
    {
        $this->yeuCauId = $yeuCauId;
    }

    public function handle()
    {
    }
}
```

- [ ] **Step 4: Viết service**

`app/Services/Xml3176/Xml3176TepXuatService.php`:

```php
<?php

namespace App\Services\Xml3176;

use App\Jobs\XuatTepLoiXml3176Job;
use App\Models\BHYT\Xml3176TepXuat;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * Yeu cau xuat tep chay nen cua man XML3176 (spec 2026-09-30).
 *
 * Prod KHONG chay Laravel scheduler (Kernel::schedule() trong, khong gi goi schedule:run), nen
 * don tep cu va danh dau yeu cau treo lam MOI KHI tao yeu cau hoac lay danh sach.
 */
class Xml3176TepXuatService
{
    /** Thu muc tren disk 'local' (storage/app). */
    const THU_MUC = 'xml3176-tep-xuat';

    /**
     * Tao yeu cau xuat danh sach loi. Nguoi nay da co yeu cau DANG CHO/DANG TAO voi bo loc
     * giong het thi tra yeu cau do: moi lan xuat ngay lon ton 12-30 phut va hang doi chi co
     * mot worker, bam lap nam lan thi nguoi sau cho hon hai tieng.
     *
     * @return array ['yeuCau' => Xml3176TepXuat, 'trung' => bool]
     */
    public function taoYeuCau(int $userId, array $boLoc): array
    {
        $this->donDep();
        $this->danhDauTreo();

        $chuan = self::chuanHoa($boLoc);

        $dangChay = Xml3176TepXuat::where('user_id', $userId)
            ->where('loai', Xml3176TepXuat::LOAI_LOI)
            ->whereIn('trang_thai', [Xml3176TepXuat::CHO, Xml3176TepXuat::DANG_TAO])
            ->get();

        foreach ($dangChay as $y) {
            if (self::chuanHoa((array) $y->bo_loc) === $chuan) {
                return ['yeuCau' => $y, 'trung' => true];
            }
        }

        $y = Xml3176TepXuat::create([
            'user_id' => $userId,
            'loai' => Xml3176TepXuat::LOAI_LOI,
            'bo_loc' => $chuan,
            'trang_thai' => Xml3176TepXuat::CHO,
        ]);

        // Day SAU khi dong da ghi: job doc dong theo id.
        XuatTepLoiXml3176Job::dispatch($y->id)
            ->onConnection(config('xml3176.xuat_tep_connection'))
            ->onQueue(config('xml3176.xuat_tep_queue_name'));

        return ['yeuCau' => $y, 'trung' => false];
    }

    /**
     * Cac yeu cau cua nguoi nay trong so ngay giu tep, moi nhat truoc.
     *
     * @return array mang cac mang ['id','tao_luc','bo_loc','trang_thai','so_truoc','so_phut','kich_thuoc','loi']
     */
    public function danhSachCua(int $userId): array
    {
        $this->donDep();
        $this->danhDauTreo();

        $ds = Xml3176TepXuat::where('user_id', $userId)
            ->orderBy('id', 'desc')
            ->get();

        return $ds->map(function (Xml3176TepXuat $y) {
            return [
                'id' => $y->id,
                'tao_luc' => $y->created_at->format('d/m/Y H:i'),
                'bo_loc' => self::tomTatBoLoc((array) $y->bo_loc),
                'trang_thai' => $y->trang_thai,
                // Dem tren MOI nguoi dung: hang doi chi co mot worker.
                'so_truoc' => $y->trang_thai === Xml3176TepXuat::CHO
                    ? Xml3176TepXuat::where('trang_thai', Xml3176TepXuat::CHO)->where('id', '<', $y->id)->count()
                    + Xml3176TepXuat::where('trang_thai', Xml3176TepXuat::DANG_TAO)->count()
                    : null,
                'so_phut' => $y->trang_thai === Xml3176TepXuat::DANG_TAO && $y->bat_dau_luc
                    ? $y->bat_dau_luc->diffInMinutes(Carbon::now())
                    : null,
                'kich_thuoc' => $y->kich_thuoc,
                'loi' => $y->loi,
            ];
        })->all();
    }

    /**
     * Yeu cau cua CHINH nguoi nay, da xong va tep con tren dia; nguoc lai null. Controller tra
     * 404 cho moi truong hop null - khong phan biet, de khong lo yeu cau cua nguoi khac.
     */
    public function timDeTai(int $userId, int $id)
    {
        $y = Xml3176TepXuat::where('id', $id)->where('user_id', $userId)->first();

        if ($y === null || $y->trang_thai !== Xml3176TepXuat::XONG || empty($y->duong_dan)) {
            return null;
        }

        return Storage::disk('local')->exists($y->duong_dan) ? $y : null;
    }

    /** Xoa yeu cau cu hon so ngay giu tep, KEM tep cua no. */
    public function donDep(): void
    {
        $moc = Carbon::now()->subDays((int) config('xml3176.xuat_tep_giu_ngay', 7));

        foreach (Xml3176TepXuat::where('created_at', '<', $moc)->get() as $y) {
            if ($y->duong_dan) {
                Storage::disk('local')->delete($y->duong_dan);
            }
            $y->delete();
        }
    }

    /**
     * Dang tao qua so phut treo thi coi nhu worker da dung (trien khai trung luc xuat, het RAM
     * - loi fatal cua PHP khong di qua failed()). Khong co buoc nay dong kep mai o dang_tao.
     */
    public function danhDauTreo(): void
    {
        $moc = Carbon::now()->subMinutes((int) config('xml3176.xuat_tep_treo_phut', 90));

        Xml3176TepXuat::where('trang_thai', Xml3176TepXuat::DANG_TAO)
            ->where('bat_dau_luc', '<', $moc)
            ->update([
                'trang_thai' => Xml3176TepXuat::LOI,
                'loi' => 'Quá thời gian, có thể dịch vụ xuất đã dừng. Bấm tạo lại.',
            ]);
    }

    public static function duongDanTep(Xml3176TepXuat $y): string
    {
        return self::THU_MUC . '/' . $y->id . '.xlsx';
    }

    public static function tenTepTai(Xml3176TepXuat $y): string
    {
        $boLoc = (array) $y->bo_loc;
        $tu = isset($boLoc['date_from']) ? preg_replace('/\D/', '', substr((string) $boLoc['date_from'], 0, 10)) : '';

        return 'xml3176_loi_' . $tu . '_' . $y->created_at->format('YmdHis') . '.xlsx';
    }

    /** Tom tat cac bo loc CO GIA TRI de nguoi dung nhan ra yeu cau cua minh. */
    public static function tomTatBoLoc(array $boLoc): string
    {
        $phan = [];

        if (!empty($boLoc['date_from']) && !empty($boLoc['date_to'])) {
            $dinhDang = function ($s) {
                return Carbon::parse(substr((string) $s, 0, 10))->format('d/m/Y');
            };
            $tu = $dinhDang($boLoc['date_from']);
            $den = $dinhDang($boLoc['date_to']);
            $phan[] = $tu === $den ? $tu : $tu . ' – ' . $den;
        }

        foreach ($boLoc as $khoa => $giaTri) {
            if (in_array($khoa, ['date_from', 'date_to'], true) || $giaTri === null || $giaTri === '') {
                continue;
            }
            $phan[] = $khoa . '=' . (is_array($giaTri) ? implode(',', $giaTri) : $giaTri);
        }

        return implode(' · ', $phan);
    }

    /** Sap khoa va bo gia tri rong de so sanh "bo loc giong het" khong phu thuoc thu tu. */
    private static function chuanHoa(array $boLoc): array
    {
        $boLoc = array_filter($boLoc, function ($v) {
            return $v !== null && $v !== '';
        });
        ksort($boLoc);

        return $boLoc;
    }
}
```

- [ ] **Step 5: Chạy test, xác nhận xanh**

Run: `DB_HOST=127.0.0.1 php vendor/bin/phpunit tests/Unit/Xml3176/TepXuat/Xml3176TepXuatServiceTest.php`
Expected: `OK (8 tests, ...)`

- [ ] **Step 6: Commit**

```bash
git add app/Services/Xml3176/Xml3176TepXuatService.php app/Jobs/XuatTepLoiXml3176Job.php tests/Unit/Xml3176/TepXuat/Xml3176TepXuatServiceTest.php
git commit -m "feat(xml3176): Xml3176TepXuatService - tao yeu cau, danh sach, tai, don dep, danh dau treo"
```

---

### Task 4: Thân job `XuatTepLoiXml3176Job`

**Files:**
- Modify: `app/Jobs/XuatTepLoiXml3176Job.php`
- Test: `tests/Unit/Xml3176/TepXuat/XuatTepLoiXml3176JobTest.php`

**Interfaces:**
- Consumes: model (Task 1), `Xml3176TepXuatService::duongDanTep()` (Task 3), `App\Exports\Xml3176ErrorMultiSheetExport($loc, $danhSachCoSo)`, `App\Services\BHYT\DanhSachCoSo::danhSach()` (không phụ thuộc người đăng nhập; đọc HIS qua cache khoá `DanhSachCoSo::KHOA_CACHE`).
- Produces: `handle()` không tham số; `failed(\Throwable $e)`.

- [ ] **Step 1: Viết test đỏ**

`tests/Unit/Xml3176/TepXuat/XuatTepLoiXml3176JobTest.php`:

```php
<?php

namespace Tests\Unit\Xml3176\TepXuat;

use App\Exports\Xml3176ErrorMultiSheetExport;
use App\Jobs\XuatTepLoiXml3176Job;
use App\Models\BHYT\Xml3176TepXuat;
use App\Services\BHYT\DanhSachCoSo;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Cell\StringValueBinder;
use Tests\Support\Xml3176RuleTestSupport;
use Tests\TestCase;

class XuatTepLoiXml3176JobTest extends TestCase
{
    use Xml3176RuleTestSupport;

    protected function setUp()
    {
        parent::setUp();
        $this->bootXml3176Sqlite(['2026_09_30_100000_create_xml3176_tep_xuat_table.php']);
        Storage::fake('local');
        // Khong cham HIS: DanhSachCoSo doc qua cache.
        Cache::put(DanhSachCoSo::KHOA_CACHE, ['01929' => 'BV A'], 60);
    }

    protected function tearDown()
    {
        Cell::setValueBinder(new DefaultValueBinder());
        parent::tearDown();
    }

    private function yeuCau(array $gia = [])
    {
        return Xml3176TepXuat::create(array_merge([
            'user_id' => 1, 'loai' => Xml3176TepXuat::LOAI_LOI,
            'bo_loc' => ['date_from' => '2026-09-29 00:00:00', 'date_to' => '2026-09-29 23:59:59', 'ma_cskcb' => '01929'],
            'trang_thai' => Xml3176TepXuat::CHO,
        ], $gia));
    }

    /** @test */
    public function chay_xong_thi_luu_tep_dung_cho_bang_bo_loc_da_luu_va_danh_dau_xong()
    {
        Excel::fake();
        $y = $this->yeuCau();

        (new XuatTepLoiXml3176Job($y->id))->handle();

        $duongDan = 'xml3176-tep-xuat/' . $y->id . '.xlsx';
        Excel::assertStored($duongDan, 'local', function ($export) {
            return $export instanceof Xml3176ErrorMultiSheetExport;
        });
        $y = $y->fresh();
        $this->assertSame(Xml3176TepXuat::XONG, $y->trang_thai);
        $this->assertSame($duongDan, $y->duong_dan);
        $this->assertNotNull($y->bat_dau_luc);
        $this->assertNotNull($y->xong_luc);
        $this->assertNull($y->loi);
    }

    /** @test */
    public function yeu_cau_khong_con_hoac_khong_o_cho_thi_khong_lam_gi()
    {
        Excel::fake();
        $daXong = $this->yeuCau(['trang_thai' => Xml3176TepXuat::XONG]);

        (new XuatTepLoiXml3176Job(999))->handle();
        (new XuatTepLoiXml3176Job($daXong->id))->handle();

        // Laravel Excel 3.1.25 khong co assertNotStored(): kiem qua trang thai - neu job da
        // xu ly thi bat_dau_luc duoc ghi.
        $daXong = $daXong->fresh();
        $this->assertSame(Xml3176TepXuat::XONG, $daXong->trang_thai);
        $this->assertNull($daXong->bat_dau_luc);
    }

    /** @test */
    public function sau_khi_xuat_bo_gan_gia_tri_tro_ve_mac_dinh()
    {
        // Hai sheet danh muc dat StringValueBinder vao bien TINH va khong tra lai. Worker chay
        // nhieu lan xuat noi tiep: thieu buoc tra lai thi cac lan sau ghi moi o thanh chuoi.
        Excel::fake();
        Cell::setValueBinder(new StringValueBinder());
        $y = $this->yeuCau();

        (new XuatTepLoiXml3176Job($y->id))->handle();

        $this->assertInstanceOf(DefaultValueBinder::class, Cell::getValueBinder());
        $this->assertNotInstanceOf(StringValueBinder::class, Cell::getValueBinder());
    }

    /** @test */
    public function hong_thi_danh_dau_loi_va_xoa_tep_do()
    {
        $y = $this->yeuCau(['trang_thai' => Xml3176TepXuat::DANG_TAO]);
        Storage::disk('local')->put('xml3176-tep-xuat/' . $y->id . '.xlsx', 'do dang');

        (new XuatTepLoiXml3176Job($y->id))->failed(new \RuntimeException('het bo nho'));

        $y = $y->fresh();
        $this->assertSame(Xml3176TepXuat::LOI, $y->trang_thai);
        $this->assertContains('het bo nho', $y->loi);
        $this->assertFalse(Storage::disk('local')->exists('xml3176-tep-xuat/' . $y->id . '.xlsx'));
    }

    /** @test */
    public function thu_mot_lan_va_handle_khong_nhan_tham_so()
    {
        $this->assertSame(1, (new XuatTepLoiXml3176Job(1))->tries);
        $this->assertSame(0, (new \ReflectionMethod(XuatTepLoiXml3176Job::class, 'handle'))->getNumberOfParameters(),
            'Khong nhan dich vu qua type-hint cua handle() (bay tiem container Laravel 5.5)');
    }
}
```

- [ ] **Step 2: Chạy test, xác nhận đỏ**

Run: `DB_HOST=127.0.0.1 php vendor/bin/phpunit tests/Unit/Xml3176/TepXuat/XuatTepLoiXml3176JobTest.php`
Expected: 4 test đỏ (thân `handle()` rỗng, chưa có `failed()`); `thu_mot_lan_va_handle_khong_nhan_tham_so` xanh sẵn.

- [ ] **Step 3: Viết thân job**

Thay toàn bộ `app/Jobs/XuatTepLoiXml3176Job.php` bằng:

```php
<?php

namespace App\Jobs;

use App\Exports\Xml3176ErrorMultiSheetExport;
use App\Models\BHYT\Xml3176TepXuat;
use App\Services\BHYT\DanhSachCoSo;
use App\Services\Xml3176\Xml3176TepXuatService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;

/**
 * Tao tep "danh sach loi" XML3176 chay nen cho mot yeu cau trong xml3176_tep_xuat.
 *
 * Chay tren ket noi 'xuat_tep' (retry_after 3600), hang doi JobXuatTepXml3176, dich vu NSSM
 * rieng - mot lan xuat ngay lon ton 12-30 phut va ~2,5 GB, khong duoc chan chuoi
 * kiem-xuat-ky-gui.
 *
 * KHONG khai $timeout: PHP Windows khong co pcntl nen $timeout khong duoc thi hanh. Treo thi
 * Xml3176TepXuatService::danhDauTreo() chuyen loi sau 90 phut.
 */
class XuatTepLoiXml3176Job implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** Hong thi ghi loi, KHONG tu chay lai mot viec ton 12-30 phut. */
    public $tries = 1;

    /** @var int id dong xml3176_tep_xuat - nhan id chu khong nhan model: job co the cho lau */
    public $yeuCauId;

    public function __construct($yeuCauId)
    {
        $this->yeuCauId = $yeuCauId;
    }

    public function handle()
    {
        $y = Xml3176TepXuat::find($this->yeuCauId);

        // Da bi don, hoac da duoc xu ly boi mot lan chay khac: khong lam gi.
        if ($y === null || $y->trang_thai !== Xml3176TepXuat::CHO) {
            return;
        }

        $y->update(['trang_thai' => Xml3176TepXuat::DANG_TAO, 'bat_dau_luc' => Carbon::now()]);

        // Ngay 29/09/2026 (204.617 dong loi): ~700 giay, bo nho dinh ~2,5 GB.
        set_time_limit(0);
        ini_set('memory_limit', '4096M');

        $duongDan = Xml3176TepXuatService::duongDanTep($y);

        try {
            Excel::store(
                new Xml3176ErrorMultiSheetExport((array) $y->bo_loc, DanhSachCoSo::danhSach()),
                $duongDan,
                'local'
            );
        } finally {
            // Hai sheet danh muc dat StringValueBinder vao bien TINH (vendor/maatwebsite/excel/
            // src/Sheet.php) va khong tra lai. Worker nay chay nhieu lan xuat noi tiep: khong
            // tra lai thi cac lan sau ghi moi o thanh chuoi.
            Cell::setValueBinder(new DefaultValueBinder());
        }

        $disk = Storage::disk('local');

        $y->update([
            'trang_thai' => Xml3176TepXuat::XONG,
            'duong_dan' => $duongDan,
            'kich_thuoc' => $disk->exists($duongDan) ? $disk->size($duongDan) : null,
            'xong_luc' => Carbon::now(),
            'loi' => null,
        ]);
    }

    public function failed(\Throwable $e)
    {
        Log::error('XuatTepLoiXml3176Job that bai: ' . $e->getMessage(), ['yeu_cau' => $this->yeuCauId]);

        $y = Xml3176TepXuat::find($this->yeuCauId);

        if ($y === null) {
            return;
        }

        Storage::disk('local')->delete(Xml3176TepXuatService::duongDanTep($y));

        $y->update([
            'trang_thai' => Xml3176TepXuat::LOI,
            'loi' => 'Tạo tệp lỗi — ' . $e->getMessage(),
        ]);
    }
}
```

- [ ] **Step 4: Chạy test, xác nhận xanh**

Run: `DB_HOST=127.0.0.1 php vendor/bin/phpunit tests/Unit/Xml3176/TepXuat/XuatTepLoiXml3176JobTest.php`
Expected: `OK (5 tests, ...)`

- [ ] **Step 5: Commit**

```bash
git add app/Jobs/XuatTepLoiXml3176Job.php tests/Unit/Xml3176/TepXuat/XuatTepLoiXml3176JobTest.php
git commit -m "feat(xml3176): XuatTepLoiXml3176Job tao tep loi nen, tra bo gan gia tri ve mac dinh"
```

---

### Task 5: Route và controller

**Files:**
- Modify: `app/Http/Controllers/BHYT/BHYTXml3176Controller.php`
- Modify: `routes/web.php` (trong nhóm `Route::group(['prefix' => 'bhyt/', 'middleware' => ['checkrole:xml-man']], ...)`, cạnh route `xml3176/export-xml3176-xml-errors`)
- Test: `tests/Feature/Xml3176TepXuatControllerTest.php`

**Interfaces:**
- Consumes: `Xml3176TepXuatService` (Task 3), `Xml3176LocDanhSach::tuRequest($request)`.
- Produces: route `bhyt.xml3176.tep-xuat.tao` (POST `bhyt/xml3176/tep-xuat`), `bhyt.xml3176.tep-xuat.danh-sach` (GET `bhyt/xml3176/tep-xuat`), `bhyt.xml3176.tep-xuat.tai` (GET `bhyt/xml3176/tep-xuat/{id}/tai`, tham số `id`); route cũ `bhyt.xml3176.export-xml3176-xml-errors` chuyển hướng về `bhyt.xml3176.index`.

- [ ] **Step 1: Viết test đỏ**

`tests/Feature/Xml3176TepXuatControllerTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Jobs\XuatTepLoiXml3176Job;
use App\Models\BHYT\Xml3176TepXuat;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\Support\UserGiaCoQuyen;
use Tests\Support\Xml3176RuleTestSupport;
use Tests\TestCase;

class Xml3176TepXuatControllerTest extends TestCase
{
    use Xml3176RuleTestSupport;

    protected function setUp()
    {
        parent::setUp();
        $this->bootXml3176Sqlite(['2026_09_30_100000_create_xml3176_tep_xuat_table.php']);
        Queue::fake();
        Storage::fake('local');
    }

    private function thamSo()
    {
        return [
            'date_from' => '2026-09-29 00:00:00', 'date_to' => '2026-09-29 23:59:59',
            'date_type' => 'date_payment', 'hein_card_filter' => 'has_hein_card',
        ];
    }

    /** @test */
    public function bam_xuat_tao_yeu_cau_va_day_job()
    {
        $r = $this->actingAs(UserGiaCoQuyen::tao(7))
            ->postJson(route('bhyt.xml3176.tep-xuat.tao'), $this->thamSo())
            ->assertStatus(200)
            ->json();

        $this->assertFalse($r['trung']);
        $this->assertSame('cho', $r['trang_thai']);
        $this->assertSame(7, Xml3176TepXuat::find($r['id'])->user_id);
        $this->assertSame('has_hein_card', Xml3176TepXuat::find($r['id'])->bo_loc['hein_card_filter']);
        Queue::assertPushed(XuatTepLoiXml3176Job::class, 1);
    }

    /** @test */
    public function danh_sach_tra_json_cua_nguoi_dang_nhap()
    {
        Xml3176TepXuat::create(['user_id' => 7, 'loai' => 'loi', 'bo_loc' => $this->thamSo(), 'trang_thai' => 'cho']);
        Xml3176TepXuat::create(['user_id' => 8, 'loai' => 'loi', 'bo_loc' => $this->thamSo(), 'trang_thai' => 'cho']);

        $r = $this->actingAs(UserGiaCoQuyen::tao(7))
            ->getJson(route('bhyt.xml3176.tep-xuat.danh-sach'))
            ->assertStatus(200)
            ->json();

        $this->assertCount(1, $r['data']);
    }

    /** @test */
    public function tai_tep_cua_minh_duoc_nguoi_khac_404()
    {
        $y = Xml3176TepXuat::create(['user_id' => 7, 'loai' => 'loi', 'bo_loc' => $this->thamSo(), 'trang_thai' => 'xong']);
        $y->update(['duong_dan' => 'xml3176-tep-xuat/' . $y->id . '.xlsx']);
        Storage::disk('local')->put($y->duong_dan, 'NOI DUNG');

        $this->actingAs(UserGiaCoQuyen::tao(8))
            ->get(route('bhyt.xml3176.tep-xuat.tai', ['id' => $y->id]))
            ->assertStatus(404);

        $r = $this->actingAs(UserGiaCoQuyen::tao(7))
            ->get(route('bhyt.xml3176.tep-xuat.tai', ['id' => $y->id]));
        $r->assertStatus(200);
        $this->assertContains('xml3176_loi_20260929_', $r->headers->get('content-disposition'));
    }

    /** @test */
    public function tai_yeu_cau_chua_xong_404()
    {
        $y = Xml3176TepXuat::create(['user_id' => 7, 'loai' => 'loi', 'bo_loc' => $this->thamSo(), 'trang_thai' => 'dang_tao']);

        $this->actingAs(UserGiaCoQuyen::tao(7))
            ->get(route('bhyt.xml3176.tep-xuat.tai', ['id' => $y->id]))
            ->assertStatus(404);
    }

    /** @test */
    public function route_xuat_cu_chuyen_ve_man_danh_sach_khong_xuat_dong_bo()
    {
        $r = $this->actingAs(UserGiaCoQuyen::tao(7))
            ->get(route('bhyt.xml3176.export-xml3176-xml-errors', $this->thamSo()));

        $r->assertRedirect(route('bhyt.xml3176.index'));
        Queue::assertNothingPushed();
        $this->assertSame(0, Xml3176TepXuat::count());
    }
}
```

- [ ] **Step 2: Chạy test, xác nhận đỏ**

Run: `DB_HOST=127.0.0.1 php vendor/bin/phpunit tests/Feature/Xml3176TepXuatControllerTest.php`
Expected: lỗi `Route [bhyt.xml3176.tep-xuat.tao] not defined.` (và route cũ vẫn xuất đồng bộ).

- [ ] **Step 3: Thêm route**

Trong `routes/web.php`, thay hai dòng:

```php
        Route::get('xml3176/export-xml3176-xml-errors', 'BHYT\BHYTXml3176Controller@exportXml3176XmlErrors')
        ->name('bhyt.xml3176.export-xml3176-xml-errors');
```

bằng:

```php
        // Tai truc tiep da chuyen sang tao tep nen (504 tren prod ngay 29/09/2026). Route cu giu
        // lai de ai con luu duong dan duoc chuyen ve man danh sach kem huong dan.
        Route::get('xml3176/export-xml3176-xml-errors', 'BHYT\BHYTXml3176Controller@exportXml3176XmlErrors')
        ->name('bhyt.xml3176.export-xml3176-xml-errors');
        Route::post('xml3176/tep-xuat', 'BHYT\BHYTXml3176Controller@taoTepXuat')
        ->name('bhyt.xml3176.tep-xuat.tao');
        Route::get('xml3176/tep-xuat', 'BHYT\BHYTXml3176Controller@danhSachTepXuat')
        ->name('bhyt.xml3176.tep-xuat.danh-sach');
        Route::get('xml3176/tep-xuat/{id}/tai', 'BHYT\BHYTXml3176Controller@taiTepXuat')
        ->where('id', '[0-9]+')
        ->name('bhyt.xml3176.tep-xuat.tai');
```

- [ ] **Step 4: Viết controller**

Trong `app/Http/Controllers/BHYT/BHYTXml3176Controller.php`:

Thêm vào khối `use` (cạnh các `use App\Services\...`):

```php
use App\Services\Xml3176\Xml3176TepXuatService;
```

Thay toàn bộ phương thức `exportXml3176XmlErrors(Request $request)` bằng:

```php
    /**
     * Route cu: tai truc tiep da chuyen sang tao tep nen. Ngay 29/09/2026 (204.617 dong loi)
     * lan xuat dong bo mat 1.796 giay trong khi Cloudflare chi cho 100 giay -> 504.
     */
    public function exportXml3176XmlErrors(Request $request)
    {
        flash('Xuất danh sách lỗi đã chuyển sang tạo tệp nền — bấm lại nút Xuất danh sách lỗi, rồi tải ở mục Tệp xuất của tôi.')->warning();

        return redirect()->route('bhyt.xml3176.index');
    }

    public function taoTepXuat(Request $request, Xml3176TepXuatService $tepXuat)
    {
        $kq = $tepXuat->taoYeuCau((int) \Auth::id(), Xml3176LocDanhSach::tuRequest($request));

        return response()->json([
            'id' => $kq['yeuCau']->id,
            'trang_thai' => $kq['yeuCau']->trang_thai,
            'trung' => $kq['trung'],
        ]);
    }

    public function danhSachTepXuat(Xml3176TepXuatService $tepXuat)
    {
        return response()->json(['data' => $tepXuat->danhSachCua((int) \Auth::id())]);
    }

    public function taiTepXuat($id, Xml3176TepXuatService $tepXuat)
    {
        $y = $tepXuat->timDeTai((int) \Auth::id(), (int) $id);

        // 404 cho MOI truong hop: khong ton tai, cua nguoi khac, chua xong, tep da don.
        abort_if($y === null, 404, 'Không tìm thấy tệp. Tệp có thể chưa tạo xong hoặc đã quá 7 ngày.');

        // Duong dan THAT cua disk (khong dung storage_path('app/...')): Storage::fake('local')
        // trong test doi thu muc goc cua disk.
        return response()->download(
            Storage::disk('local')->path($y->duong_dan),
            Xml3176TepXuatService::tenTepTai($y)
        );
    }
```

(`Illuminate\Support\Facades\Storage` đã được `use` sẵn trong controller.)

Nếu sau thay đổi này `use App\Exports\Xml3176ErrorMultiSheetExport;` không còn dùng trong controller, xoá dòng đó (kiểm bằng `grep -c "Xml3176ErrorMultiSheetExport" app/Http/Controllers/BHYT/BHYTXml3176Controller.php` — còn 1 nghĩa là chỉ còn dòng `use`).

- [ ] **Step 5: Chạy test, xác nhận xanh**

Run: `DB_HOST=127.0.0.1 php vendor/bin/phpunit tests/Feature/Xml3176TepXuatControllerTest.php`
Expected: `OK (5 tests, ...)`

- [ ] **Step 6: Commit**

```bash
git add routes/web.php app/Http/Controllers/BHYT/BHYTXml3176Controller.php tests/Feature/Xml3176TepXuatControllerTest.php
git commit -m "feat(xml3176): route tao/danh sach/tai tep xuat nen; route xuat loi cu chuyen huong"
```

---

### Task 6: Giao diện — nút xuất và mục *Tệp xuất của tôi*

**Files:**
- Modify: `resources/views/bhyt/xml3176/index.blade.php`
- Modify: `tests/Unit/Xml3176/Xml3176ExportParamsTest.php` (phương thức `ca_ba_nut_xuat_deu_dung_ham_dung_chung`)
- Test: `tests/Unit/Xml3176/TepXuat/GiaoDienTepXuatTest.php`

**Interfaces:**
- Consumes: ba route Task 5; hàm JS sẵn có `xml3176ThamSoLoc()`; `toastr` (đã dùng trong tệp).

- [ ] **Step 1: Sửa test gác và viết test giao diện (đỏ)**

Trong `tests/Unit/Xml3176/Xml3176ExportParamsTest.php`, phương thức `ca_ba_nut_xuat_deu_dung_ham_dung_chung`: đổi phần tử `'export-xml3176-xml-errors'` trong mảng thành `'tep-xuat.tao'`, và thêm ngay trên vòng `foreach` chú thích:

```php
        // Nut xuat loi nay goi route tao tep nen (tep-xuat.tao) bang AJAX, van phai gui du bo loc.
```

`tests/Unit/Xml3176/TepXuat/GiaoDienTepXuatTest.php`:

```php
<?php

namespace Tests\Unit\Xml3176\TepXuat;

use Tests\TestCase;

class GiaoDienTepXuatTest extends TestCase
{
    private function blade()
    {
        return file_get_contents(resource_path('views/bhyt/xml3176/index.blade.php'));
    }

    /** @test */
    public function nut_xuat_loi_khong_con_tai_truc_tiep()
    {
        $b = $this->blade();

        $this->assertNotContains('export-xml3176-xml-errors', $b,
            'Tai truc tiep tra 504 tren prod voi ngay lon');
        $this->assertContains('bhyt.xml3176.tep-xuat.tao', $b);
    }

    /** @test */
    public function co_muc_tep_xuat_cua_toi_goi_danh_sach_va_tai()
    {
        $b = $this->blade();

        $this->assertContains('id="tep-xuat-cua-toi-btn"', $b);
        $this->assertContains('id="tepXuatModal"', $b);
        $this->assertContains('bhyt.xml3176.tep-xuat.danh-sach', $b);
        $this->assertContains('bhyt.xml3176.tep-xuat.tai', $b);
    }

    /** @test */
    public function chi_hoi_lai_may_chu_khi_con_yeu_cau_dang_chay()
    {
        $b = $this->blade();

        $this->assertContains('15000', $b, 'Chu ky hoi lai 15 giay');
        $this->assertContains('conDangChay', $b, 'Chi hen gio khi con yeu cau cho/dang_tao');
    }

    /** @test */
    public function noi_dung_tu_may_chu_duoc_thoat_html()
    {
        // Tom tat bo loc chua gia tri nguoi dung go (ma ho so...). Chen bang .text() chu
        // khong ghep chuoi HTML.
        $b = $this->blade();
        $vt = strpos($b, 'function xml3176VeBangTepXuat');
        $this->assertNotFalse($vt);
        // Cat o ham ke tiep: ngay sau do da co san .html( cua ma cu, khong thuoc ham nay.
        $het = strpos($b, 'function applySelectedCheckboxes', $vt);
        $this->assertNotFalse($het);
        $than = substr($b, $vt, $het - $vt);
        $this->assertContains('.text(', $than);
        $this->assertNotContains('.html(', $than);
    }
}
```

- [ ] **Step 2: Chạy test, xác nhận đỏ**

```bash
DB_HOST=127.0.0.1 php vendor/bin/phpunit tests/Unit/Xml3176/TepXuat/GiaoDienTepXuatTest.php
DB_HOST=127.0.0.1 php vendor/bin/phpunit tests/Unit/Xml3176/Xml3176ExportParamsTest.php
```

Expected: `GiaoDienTepXuatTest` đỏ cả 4; `ca_ba_nut_xuat_deu_dung_ham_dung_chung` đỏ (`Khong tim thay nut tep-xuat.tao`).

- [ ] **Step 3: Thêm nút và modal**

Trong `resources/views/bhyt/xml3176/index.blade.php`, ngay sau khối nút:

```blade
<button id="openDownloadModalBtn" class="btn btn-primary">
    <i class="fa fa-download" aria-hidden="true"></i> Tải xuống 7980a/19/20/21
</button>
```

thêm:

```blade
<!-- Tep xuat chay nen (xuat danh sach loi): chi hien yeu cau cua chinh nguoi dang nhap -->
<button id="tep-xuat-cua-toi-btn" class="btn btn-default">
    <i class="fa fa-folder-open" aria-hidden="true"></i> Tệp xuất của tôi
    <span class="badge" id="tep-xuat-dem"></span>
</button>

<div class="modal fade" id="tepXuatModal" tabindex="-1" role="dialog" aria-labelledby="tepXuatModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="tepXuatModalLabel">Tệp xuất của tôi (giữ 7 ngày)</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body table-responsive">
                <table class="table table-condensed">
                    <thead>
                        <tr><th>Yêu cầu lúc</th><th>Bộ lọc</th><th>Trạng thái</th><th>Kích thước</th><th></th></tr>
                    </thead>
                    <tbody id="tep-xuat-bang"></tbody>
                </table>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>
```

- [ ] **Step 4: Đổi JS nút xuất lỗi và thêm JS mục tệp xuất**

Thay khối:

```js
        $('#export_xml3176_xml_error').click(function() {
            window.location.href = '{{ route("bhyt.xml3176.export-xml3176-xml-errors") }}?'
                + $.param(xml3176ThamSoLoc());
        });
```

bằng:

```js
        // Xuat danh sach loi chay NEN: ngay lon mat 12-30 phut, tai truc tiep bi Cloudflare cat
        // o 100 giay (504). Bam la xep mot yeu cau; tai ve o muc "Tep xuat cua toi".
        $('#export_xml3176_xml_error').click(function() {
            $.ajax({
                url: '{{ route("bhyt.xml3176.tep-xuat.tao") }}',
                type: 'POST',
                data: $.extend(xml3176ThamSoLoc(), { _token: '{{ csrf_token() }}' }),
                success: function (r) {
                    toastr.success(r.trung
                        ? 'Yêu cầu giống hệt đang chạy — theo dõi ở mục Tệp xuất của tôi.'
                        : 'Đã xếp hàng tạo tệp. Theo dõi ở mục Tệp xuất của tôi.');
                    xml3176TaiDanhSachTepXuat();
                },
                error: function () {
                    toastr.error('Không tạo được yêu cầu xuất, vui lòng thử lại.');
                }
            });
        });

        $('#tep-xuat-cua-toi-btn').click(function () {
            xml3176TaiDanhSachTepXuat();
            $('#tepXuatModal').modal('show');
        });

        xml3176TaiDanhSachTepXuat();
```

Ngay trước dòng `function applySelectedCheckboxes() {` (ngoài khối `$(document).ready`), thêm:

```js
    // ─── Tep xuat chay nen ────────────────────────────────────────────────
    var xml3176HenGioTepXuat = null;

    function xml3176TaiDanhSachTepXuat() {
        $.getJSON('{{ route("bhyt.xml3176.tep-xuat.danh-sach") }}', function (r) {
            var conDangChay = xml3176VeBangTepXuat(r.data || []);

            clearTimeout(xml3176HenGioTepXuat);
            // Chi hoi lai khi con yeu cau cho/dang_tao; khong con thi dung han.
            if (conDangChay > 0) {
                xml3176HenGioTepXuat = setTimeout(xml3176TaiDanhSachTepXuat, 15000);
            }
        });
    }

    function xml3176VeBangTepXuat(ds) {
        var bang = $('#tep-xuat-bang').empty();
        var conDangChay = 0;
        var nhan = { cho: 'Đang chờ', dang_tao: 'Đang tạo', xong: 'Xong', loi: 'Lỗi' };

        if (ds.length === 0) {
            bang.append($('<tr>').append($('<td colspan="5">').text('Chưa có tệp nào trong 7 ngày.')));
        }

        ds.forEach(function (y) {
            var trangThai = nhan[y.trang_thai] || y.trang_thai;
            if (y.trang_thai === 'cho') {
                conDangChay++;
                trangThai += ' (' + y.so_truoc + ' yêu cầu phía trước)';
            } else if (y.trang_thai === 'dang_tao') {
                conDangChay++;
                trangThai += ' — ' + (y.so_phut || 0) + ' phút';
            } else if (y.trang_thai === 'loi' && y.loi) {
                trangThai += ': ' + y.loi;
            }

            var kichThuoc = y.kich_thuoc ? (y.kich_thuoc / 1048576).toFixed(1) + ' MB' : '';
            var oTai = $('<td>');
            if (y.trang_thai === 'xong') {
                var url = '{{ route("bhyt.xml3176.tep-xuat.tai", ["id" => "__ID__"]) }}'.replace('__ID__', y.id);
                oTai.append($('<a class="btn btn-xs btn-primary">').attr('href', url).text('Tải'));
            }

            bang.append($('<tr>')
                .append($('<td>').text(y.tao_luc))
                .append($('<td>').text(y.bo_loc))
                .append($('<td>').text(trangThai))
                .append($('<td>').text(kichThuoc))
                .append(oTai));
        });

        $('#tep-xuat-dem').text(conDangChay > 0 ? conDangChay : '');

        return conDangChay;
    }
```

- [ ] **Step 5: Chạy test, xác nhận xanh**

```bash
DB_HOST=127.0.0.1 php vendor/bin/phpunit tests/Unit/Xml3176/TepXuat/GiaoDienTepXuatTest.php
DB_HOST=127.0.0.1 php vendor/bin/phpunit tests/Unit/Xml3176/Xml3176ExportParamsTest.php
```

Expected: cả hai `OK`.

- [ ] **Step 6: Kiểm Blade biên dịch được**

Run: `php artisan view:clear && php -r "require 'vendor/autoload.php'; \$app = require 'bootstrap/app.php'; \$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap(); echo app('blade.compiler')->compileString(file_get_contents('resources/views/bhyt/xml3176/index.blade.php')) ? 'OK' : 'FAIL';"`
Expected: `OK` (không có lỗi cú pháp Blade). Lệnh này chỉ biên dịch chuỗi, không ghi CSDL.

- [ ] **Step 7: Commit**

```bash
git add resources/views/bhyt/xml3176/index.blade.php tests/Unit/Xml3176/Xml3176ExportParamsTest.php tests/Unit/Xml3176/TepXuat/GiaoDienTepXuatTest.php
git commit -m "feat(xml3176): nut xuat loi xep yeu cau nen; muc Tep xuat cua toi tu cap nhat"
```

---

### Task 7: Dịch vụ Windows `QLBV JobXuatTepXml3176`

**Files:**
- Modify: `update.bat`, `install_service.bat`, `remove_service.bat`
- Test: `tests/Unit/Xml3176/TepXuat/DichVuXuatTepTest.php`

**Interfaces:**
- Consumes: `config('xml3176.xuat_tep_connection')`, `config('xml3176.xuat_tep_queue_name')` (Task 1).

- [ ] **Step 1: Viết test đỏ**

`tests/Unit/Xml3176/TepXuat/DichVuXuatTepTest.php`:

```php
<?php

namespace Tests\Unit\Xml3176\TepXuat;

use Tests\TestCase;

/**
 * Hang doi xuat tep phai co dich vu Windows chay no. Thieu thi moi yeu cau nam o "Dang cho"
 * mai - khong hong, khong ai biet.
 */
class DichVuXuatTepTest extends TestCase
{
    const DICH_VU = 'QLBV JobXuatTepXml3176';

    private function tep($ten)
    {
        return file_get_contents(base_path($ten));
    }

    private function lenh()
    {
        return 'nssm install "' . self::DICH_VU . '" %PHP_PATH% "%LARAVEL_PATH%artisan queue:work '
            . config('xml3176.xuat_tep_connection') . ' --queue=' . config('xml3176.xuat_tep_queue_name') . '"';
    }

    /** @test */
    public function update_bat_giu_nguyen_tung_byte_toi_het_git_pull()
    {
        $b = $this->tep('update.bat');
        $cuoi = strpos($b, "\n", strpos($b, 'git pull origin main')) + 1;

        $this->assertSame(710, $cuoi);
        $this->assertSame('e27b07e78f81d9a9da717584ef712f4db10cd90ce67be0141ee1c14e10ec7117',
            hash('sha256', substr($b, 0, $cuoi)));
    }

    /** @test */
    public function update_bat_cai_stop_start_dich_vu_xuat_tep()
    {
        $b = $this->tep('update.bat');

        $this->assertContains('nssm status "' . self::DICH_VU . '"', $b, 'Khoi cai phai kiem truoc - chay lai moi lan cap nhat');
        $this->assertContains($this->lenh(), $b, 'Worker phai chay dung ket noi xuat_tep (retry_after 3600)');
        $this->assertContains('nssm set "' . self::DICH_VU . '" AppDirectory %LARAVEL_PATH%', $b);
        $this->assertLessThan(strpos($b, 'php artisan config:clear'), strpos($b, 'nssm stop "' . self::DICH_VU . '"'));
        $this->assertGreaterThan(strpos($b, 'php artisan config:cache'), strpos($b, 'nssm start "' . self::DICH_VU . '"'));
    }

    /** @test */
    public function install_va_remove_co_dich_vu_xuat_tep()
    {
        $cai = $this->tep('install_service.bat');
        $this->assertContains($this->lenh(), $cai);
        $this->assertContains('nssm start "' . self::DICH_VU . '"', $cai);

        $go = $this->tep('remove_service.bat');
        $this->assertContains('nssm stop "' . self::DICH_VU . '"', $go);
        $this->assertContains('nssm remove "' . self::DICH_VU . '" confirm', $go);
    }

    /** @test */
    public function ba_tep_van_la_crlf()
    {
        foreach (['update.bat', 'install_service.bat', 'remove_service.bat'] as $t) {
            $b = $this->tep($t);
            $this->assertSame(substr_count($b, "\n"), substr_count($b, "\r\n"), $t . ' co dong LF tron');
        }
    }
}
```

- [ ] **Step 2: Chạy test, xác nhận đỏ**

Run: `DB_HOST=127.0.0.1 php vendor/bin/phpunit tests/Unit/Xml3176/TepXuat/DichVuXuatTepTest.php`
Expected: `update_bat_giu_nguyen...` và `ba_tep_van_la_crlf` xanh sẵn; hai test còn lại đỏ.

- [ ] **Step 3: Sửa ba tệp `.bat` bằng kịch bản Python ghi ra tệp**

**Không** sửa `.bat` bằng Edit/Write/sed và **không** chạy bất kỳ `.bat`, `nssm`, `sc` nào. Tạo tệp `them_dich_vu_xuat_tep.py` trong thư mục scratchpad (không trong repo) với nội dung dưới, rồi chạy `python3 <đường dẫn>/them_dich_vu_xuat_tep.py`:

```python
import io, os

os.chdir(r'C:\Users\tracnn\qlbv')

DV = 'QLBV JobXuatTepXml3176'
CMD = '"%LARAVEL_PATH%artisan queue:work xuat_tep --queue=JobXuatTepXml3176"'

def sua(tep, sau_dong_nay, them):
    b = io.open(tep, 'rb').read()
    neo = sau_dong_nay.encode('utf-8')
    assert b.count(neo) == 1, (tep, sau_dong_nay)
    i = b.index(neo) + len(neo)
    assert b[i:i + 2] == b'\r\n', tep
    i += 2
    them_b = them.replace('\r\n', '\n').replace('\n', '\r\n').encode('utf-8')
    io.open(tep, 'wb').write(b[:i] + them_b + b[i:])

# update.bat: khoi cai ngay sau khoi cai JobSignXml3176.
sua('update.bat',
    '    %NSSM_PATH%\\nssm set "QLBV JobSignXml3176" AppDirectory %LARAVEL_PATH%\r\n)',
    '\n'
    ':: Hang doi xuat tep XML3176 chay nen (30/09/2026): mot lan xuat ngay lon 12-30 phut.\n'
    ':: Ket noi xuat_tep (retry_after 3600), KHONG dung chung ket noi database (300).\n'
    '%NSSM_PATH%\\nssm status "' + DV + '" >nul 2>&1\n'
    'if errorlevel 1 (\n'
    '    echo Installing service ' + DV + '...\n'
    '    %NSSM_PATH%\\nssm install "' + DV + '" %PHP_PATH% ' + CMD + '\n'
    '    %NSSM_PATH%\\nssm set "' + DV + '" AppDirectory %LARAVEL_PATH%\n'
    ')\n')
sua('update.bat', '%NSSM_PATH%\\nssm stop "QLBV JobSignXml3176"',
    '%NSSM_PATH%\\nssm stop "' + DV + '"\n')
sua('update.bat', '%NSSM_PATH%\\nssm start "QLBV JobSignXml3176"',
    '%NSSM_PATH%\\nssm start "' + DV + '"\n')

sua('install_service.bat', '%NSSM_PATH%\\nssm set "QLBV JobSignXml3176" AppDirectory %LARAVEL_PATH%',
    '\n'
    ':: Tao dich vu cho JobXuatTepXml3176 - xuat tep XML3176 chay nen (ket noi xuat_tep)\n'
    '%NSSM_PATH%\\nssm install "' + DV + '" %PHP_PATH% ' + CMD + '\n'
    '%NSSM_PATH%\\nssm set "' + DV + '" AppDirectory %LARAVEL_PATH%\n')
sua('install_service.bat', '%NSSM_PATH%\\nssm start "QLBV JobSignXml3176"',
    '%NSSM_PATH%\\nssm start "' + DV + '"\n')

sua('remove_service.bat', '%NSSM_PATH%\\nssm remove "QLBV JobSignXml3176" confirm',
    '\n'
    ':: Xoa dich vu cho JobXuatTepXml3176\n'
    '%NSSM_PATH%\\nssm stop "' + DV + '"\n'
    '%NSSM_PATH%\\nssm remove "' + DV + '" confirm\n')
print('ok')
```

Nếu một `assert` hỏng (neo không có hoặc không duy nhất): **dừng**, báo BLOCKED kèm đầu ra — không tự chọn neo khác.

Kiểm: `git diff --numstat update.bat install_service.bat remove_service.bat` chỉ có dòng thêm, không dòng xoá.

- [ ] **Step 4: Chạy test, xác nhận xanh**

```bash
DB_HOST=127.0.0.1 php vendor/bin/phpunit tests/Unit/Xml3176/TepXuat/DichVuXuatTepTest.php
DB_HOST=127.0.0.1 php vendor/bin/phpunit tests/Unit/Xml3176/Chuoi/DichVuHangDoiKyTest.php
```

Expected: cả hai `OK` (test dịch vụ ký cũ vẫn xanh).

- [ ] **Step 5: Commit**

```bash
git add update.bat install_service.bat remove_service.bat tests/Unit/Xml3176/TepXuat/DichVuXuatTepTest.php
git commit -m "feat(xml3176): dich vu QLBV JobXuatTepXml3176 cho hang doi xuat tep nen"
```

---

### Task 8: Tài liệu

**Files:**
- Modify: `readme.md` (thêm mục `# 30/09/2026` lên **đầu** tệp)
- Modify: `docs/quy-trinh-van-hanh/_nguon/build.js` (Phụ lục B)
- Regenerate: `docs/quy-trinh-van-hanh/Quy-trinh-van-hanh-XML3176.docx`

- [ ] **Step 1: readme**

Chèn vào **đầu** `readme.md` (trước dòng `# 29/09/2026`), giữ kiểu xuống dòng hiện có của tệp:

```markdown
# 30/09/2026

- **Sửa lỗi 504 khi bấm "Xuất danh sách lỗi" XML3176.** Ngày 29/09 (1.880 hồ sơ có thẻ, 204.617 dòng lỗi) bản xuất mất khoảng 30 phút, trong khi Cloudflare chỉ chờ máy chủ 100 giây. Hai nguyên nhân: bản xuất đọc dữ liệu theo từng lô 1.000 dòng và mỗi lô chạy lại cả câu truy vấn nặng (khoảng 7 giây/lô); và riêng việc ghi tệp Excel khoảng 200 nghìn dòng đã quá 100 giây.

- **Xuất danh sách lỗi nay chạy nền.** Bấm nút là xếp một yêu cầu; máy chủ tạo tệp trong hàng đợi riêng. Theo dõi và tải ở nút **Tệp xuất của tôi** trên màn danh sách: hiện số yêu cầu đứng trước, số phút đã chạy, và nút **Tải** khi xong. Tệp giữ **7 ngày**, chỉ người bấm thấy và tải được (tệp chứa họ tên, mã thẻ bệnh nhân). Bấm lặp cùng bộ lọc khi yêu cầu cũ đang chạy thì không tạo thêm.

- **Nhanh hơn khoảng 2,6 lần.** Bản xuất nay đọc mỗi sheet bằng một truy vấn. Ngày cỡ 29/09 đo được khoảng 12 phút (trước đây 30 phút). Nội dung, thứ tự và định dạng tệp 19 sheet không đổi.

- **Dịch vụ Windows mới `QLBV JobXuatTepXml3176`** — `update.bat` tự cài. Dừng dịch vụ này thì mọi yêu cầu nằm ở "Đang chờ".

- **Yêu cầu "Đang tạo" quá 90 phút tự chuyển Lỗi** ("Quá thời gian, có thể dịch vụ xuất đã dừng. Bấm tạo lại.") — trường hợp dịch vụ bị dừng giữa chừng, hoặc máy hết RAM.

- **Việc cần làm trên prod sau khi cập nhật:**
  1. Kiểm dịch vụ `QLBV JobXuatTepXml3176` đang chạy.
  2. **Kiểm RAM trống**: một lần xuất ngày lớn cần khoảng **2,5 GB**.
  3. Xuất lại ngày 29/09 có thẻ BHYT; kiểm tệp đủ 19 sheet, XML3 101.646 dòng, XML4 91.234 dòng; ghi lại thời gian chạy thực tế.

- **Hạn chế đã biết:** trên Windows PHP không cắt được job treo theo thời gian — chỉ mốc 90 phút ở trên. Hai nút "Xuất danh sách hồ sơ" và "7980a" vẫn tải trực tiếp như cũ. Quy tắc `XML3_OVERLAPPING_SERVICE_EXECUTION` sinh một dòng cho mỗi cặp dịch vụ chồng giờ (ngày 29/09: 51.708 dòng từ 115 hồ sơ) — chiếm một phần tư bản xuất, xử lý riêng sau.

- **Cài đặt:** có migration (bảng `xml3176_tep_xuat`) và một dịch vụ Windows mới — `update.bat` tự lo cả hai.

```

- [ ] **Step 2: Phụ lục B của tài liệu vận hành**

Trong `docs/quy-trinh-van-hanh/_nguon/build.js`, trong bảng của `h1('Phụ lục B. Công tắc cấu hình (dành cho CNTT)'...)`, ngay sau dòng có `'xml3176.sign_queue_name (dịch vụ QLBV JobSignXml3176)'`, thêm:

```js
      ['xml3176.xuat_tep_queue_name (dịch vụ QLBV JobXuatTepXml3176)', 'Hàng đợi xuất danh sách lỗi chạy nền, kết nối xuat_tep (retry_after 3600)', 'Dịch vụ phải luôn chạy; dừng thì yêu cầu xuất nằm ở "Đang chờ". Một lần xuất ngày lớn cần ~2,5 GB RAM'],
```

- [ ] **Step 3: Dựng lại docx**

```bash
ls -d node_modules 2>/dev/null && echo "DUNG LAI: node_modules da ton tai, khong duoc xoa" || echo "khong co node_modules, tiep tuc"
```

Nếu "DUNG LAI" thì **không** chạy `rm` ở dưới; dùng `NODE_PATH="$(npm root -g)"` theo `docs/quy-trinh-van-hanh/_nguon/README.md`.

```bash
npm install docx --no-save --no-package-lock
```

```bash
node docs/quy-trinh-van-hanh/_nguon/build.js docs/quy-trinh-van-hanh/Quy-trinh-van-hanh-XML3176.docx
```

```bash
rm -rf node_modules
```

- [ ] **Step 4: Commit**

```bash
git add readme.md docs/quy-trinh-van-hanh/_nguon/build.js docs/quy-trinh-van-hanh/Quy-trinh-van-hanh-XML3176.docx
git commit -m "docs: readme va quy trinh van hanh cho xuat danh sach loi XML3176 chay nen"
```

---

### Task 9: Xác minh toàn bộ

- [ ] **Step 1: Test mới**

```bash
DB_HOST=127.0.0.1 php vendor/bin/phpunit tests/Unit/Xml3176/TepXuat/
DB_HOST=127.0.0.1 php vendor/bin/phpunit tests/Feature/Xml3176TepXuatControllerTest.php
```

Expected: cả hai `OK`.

- [ ] **Step 2: Full suite hai lượt, so tên test đỏ**

Mọi thay đổi đã commit. Chạy trên nhánh, rồi trên `main`, rồi quay lại nhánh:

```bash
DB_HOST=127.0.0.1 php vendor/bin/phpunit 2>&1 | grep -E "^[0-9]+\) " | sed 's/^[0-9]*) //' | sort > /tmp/sau.txt
git checkout -q main && DB_HOST=127.0.0.1 php vendor/bin/phpunit 2>&1 | grep -E "^[0-9]+\) " | sed 's/^[0-9]*) //' | sort > /tmp/truoc.txt; git checkout -q xml3176-xuat-loi-nen
diff /tmp/truoc.txt /tmp/sau.txt && echo "GIONG NHAU HOAN TOAN"
```

Expected: `GIONG NHAU HOAN TOAN` (nền 102 test đỏ do môi trường). Mọi dòng trong `diff` là lỗi phải sửa.

- [ ] **Step 3: Rà sót**

```bash
grep -rn "export-xml3176-xml-errors" resources/ app/ | grep -v "exportXml3176XmlErrors"
grep -n "set_time_limit\|memory_limit" app/Exports/Xml3176ErrorMultiSheetExport.php
```

Expected: dòng đầu chỉ còn định nghĩa route/phương thức (không nút nào gọi); dòng hai không có kết quả (chỉ còn trong chú thích nếu grep bắt chữ trong chú thích — kiểm bằng mắt).

- [ ] **Step 4: Báo kết quả cho người dùng**

Không merge, không push. Báo: danh sách commit, kết quả hai lượt full suite, và việc trên prod ở mục 7 của spec (kiểm dịch vụ, kiểm RAM trống ~2,5 GB, xuất lại ngày 29/09 có thẻ BHYT và đối chiếu số dòng, ghi lại thời gian thực tế).
