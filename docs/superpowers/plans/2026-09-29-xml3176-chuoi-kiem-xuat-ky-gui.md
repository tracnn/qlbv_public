# Chuỗi kiểm → xuất → ký → gửi cho hồ sơ XML3176 — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Thay cơ chế "bước xuất chờ bước kiểm 15 giây × 10 lần" bằng một chuỗi job `withChain` duy nhất cho mỗi hồ sơ: kiểm từng loại → kiểm tổng thể → xuất → ký → gửi, bước ký tách ra hàng đợi riêng, mã phiên chặn chuỗi cũ khi nạp lại, và một lệnh cứu hồ sơ đang kẹt.

**Architecture:** Lớp điều phối `Xml3176ChuoiXuLy` dựng chuỗi (khuôn `CtdtXepHangKyGui`). Mọi job trong chuỗi dùng chung trait `ThuocChuoiXml3176`: so mã phiên (`xml3176_informations.chain_token`) trước khi làm gì, cắt chuỗi bằng `$this->chained = []` khi dừng có chủ đích, và `failed()` chỉ ghi lỗi khi mã còn khớp. Việc ký / ghi tệp / copy chuyển từ `processExportXml()` sang `SignXml3176Job` + `Xml3176Service::kyVaGhiTep()`.

**Tech Stack:** Laravel 5.5.50, PHP 7.4, PHPUnit 6.5, hàng đợi driver `database`, dịch vụ Windows NSSM.

**Spec:** `docs/superpowers/specs/2026-09-29-xml3176-chuoi-kiem-xuat-ky-gui-design.md`

## Global Constraints

- **CẤM `RefreshDatabase`** trong mọi test — bộ test từng xoá sạch CSDL `qlbv`. Test cần bảng thì dùng `Tests\Support\Xml3176RuleTestSupport::bootXml3176Sqlite([...])` (SQLite in-memory).
- `.env` dev trỏ **CSDL thật** `192.168.200.68/qlbv`. **Không bao giờ** chạy `php artisan migrate` hay lệnh ghi nào không có `DB_HOST=127.0.0.1`. Mọi lệnh test chạy dạng `DB_HOST=127.0.0.1 php vendor/bin/phpunit ...`.
- PHPUnit 6.5: `protected function setUp()` **không** có `: void`; dùng `assertContains`/`assertNotContains` cho chuỗi.
- Laravel 5.5: **không có** `Str::uuid()` (dùng `bin2hex(random_bytes(16))`), **không có** `Queue::assertPushedWithChain()` (đọc `$job->chained` rồi `unserialize`).
- **Không xoá lớp job nào và không đổi tên thuộc tính của job hiện có** (`CheckXml3176TypeJob::$maLk/$xmlType`, `CheckCompleteXml3176RecordJob::$ma_lk`, `ExportXml3176Job::$ma_lk`, `SubmitXml3176Job::$ma_lk/$xmlFilePath/$macskcb`). Job đã serialize trong hàng đợi phải giải nén được — xoá lớp `CheckXml3176ErrorsJob` từng gây mất kết quả kiểm lỗi trên prod.
- `SubmitXml3176Job::handle()` **không** nhận dịch vụ gửi qua type-hint (bẫy tiêm container Laravel 5.5); giữ nguyên chuỗi `new BHYTLoginService($this->macskcb)` và `CauHinhCoSo::maTinh($this->macskcb)`.
- Mọi `$timeout` của 5 job trong chuỗi **< 300** (`queue.connections.database.retry_after`); mọi `$tries` **≥ 1**.
- Tệp `.bat` là **CRLF, UTF-8**. `update.bat`: **không đổi một byte nào** từ đầu tệp tới hết dòng `git pull origin main` (710 byte, SHA-256 `e27b07e78f81d9a9da717584ef712f4db10cd90ce67be0141ee1c14e10ec7117`).
- Tệp PHP giữ nguyên kiểu xuống dòng hiện có của tệp (kiểm bằng `git diff --numstat`: số dòng đổi phải khớp số dòng thật sự sửa).
- Chú thích trong mã viết **tiếng Việt không dấu**, như phần còn lại của repo. Chuỗi hiển thị cho người dùng (lỗi ghi vào hồ sơ) viết **có dấu**.
- Commit message không dấu, kết thúc bằng dòng `Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>`.
- Nền full suite trong môi trường này: **102 test đỏ** do môi trường. Chỉ so **tên** test đỏ giữa hai lượt (`git stash -u` gốc vs có sửa), không so số lượng.

## Bản đồ tệp

| Tệp | Trách nhiệm | Task |
|---|---|---|
| `database/migrations/2026_09_29_120000_them_chain_token_va_signed_file_path_vao_xml3176_informations.php` | hai cột mới | 1 |
| `app/Models/BHYT/Xml3176Information.php` | `$fillable` | 1 |
| `config/xml3176.php` | `sign_queue_name` | 1 |
| `app/Jobs/Concerns/ThuocChuoiXml3176.php` | mã phiên, cắt chuỗi, ghi lỗi có điều kiện | 2 |
| `app/Services/Xml3176Service.php` | `duongDanChoKy()`, `xuatTepChoKy()`, `kyVaGhiTep()`; nạp lại xoá `signed_file_path`; bỏ `processExportXml()`, `exportXml3176()`, `checkXml3176Complete()` | 1, 3, 9 |
| `app/Jobs/CheckXml3176TypeJob.php`, `app/Jobs/CheckCompleteXml3176RecordJob.php` | mã phiên, `$tries/$timeout`, `failed()`; kiểm tổng thể luôn đóng dấu | 4 |
| `app/Jobs/ExportXml3176Job.php` | viết lại: cổng kiểm, ghi tệp chờ ký | 5 |
| `app/Jobs/SignXml3176Job.php` (mới) | ký, ghi tệp, copy, quyết định gửi | 6 |
| `app/Jobs/SubmitXml3176Job.php` | mã phiên, đọc `signed_file_path`, `failed()` ghi lỗi | 7 |
| `app/Services/Xml3176/Xml3176ChuoiXuLy.php` (mới) | dựng chuỗi | 8 |
| `app/Services/Xml3176/Xml3176Importer.php` | sinh mã trong transaction, gọi `xepSauNap()` | 9 |
| `app/Console/Commands/Xml3176ChayLaiTuXuat.php` (mới) | lệnh cứu | 10 |
| `update.bat`, `install_service.bat`, `remove_service.bat` | dịch vụ `QLBV JobSignXml3176` | 11 |
| `readme.md`, `docs/quy-trinh-van-hanh/_nguon/build.js`, `.docx` | tài liệu | 12 |

Test mới nằm trong `tests/Unit/Xml3176/Chuoi/`.

---

### Task 1: Cột mới, cấu hình hàng đợi ký, nạp lại xoá đường dẫn tệp đã ký

**Files:**
- Create: `database/migrations/2026_09_29_120000_them_chain_token_va_signed_file_path_vao_xml3176_informations.php`
- Modify: `app/Models/BHYT/Xml3176Information.php` (mảng `$fillable`)
- Modify: `config/xml3176.php` (sau khoá `submit_queue_name`)
- Modify: `app/Services/Xml3176Service.php` (nhánh `'import'` của `storeXml3176Information()`, ngay sau dòng `$values['checked_at'] = null;`)
- Test: `tests/Unit/Xml3176/Chuoi/CotChuoiXuLyTest.php`

**Interfaces:**
- Produces: cột `xml3176_informations.chain_token` (`string(32)`, nullable), `xml3176_informations.signed_file_path` (`string`, nullable); `config('xml3176.sign_queue_name') === 'JobSignXml3176'`; migration dùng trong test các task sau với tên tệp ở trên.

- [ ] **Step 1: Viết test đỏ**

`tests/Unit/Xml3176/Chuoi/CotChuoiXuLyTest.php`:

```php
<?php

namespace Tests\Unit\Xml3176\Chuoi;

use App\Services\Xml3176Service;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Support\Xml3176RuleTestSupport;
use Tests\TestCase;

/**
 * Hai cot moi cua chuoi kiem -> xuat -> ky -> gui, va viec nap lai xoa trang thai chuoi.
 *
 * chain_token: ma phien xu ly - moi lan dung chuoi moi (nap hoac lenh cuu) sinh ma moi,
 * job cua chuoi cu so ma thay lech thi tu thoi.
 * signed_file_path: buoc ky ghi, buoc gui doc - thay cho tham so duong dan truoc day.
 */
class CotChuoiXuLyTest extends TestCase
{
    use Xml3176RuleTestSupport;

    protected function setUp()
    {
        parent::setUp();
        $this->bootXml3176Sqlite([
            '2026_01_09_162044_create_xml3176_informations_table.php',
            '2026_03_28_201021_add_sign_method_to_xml3176_informations.php',
            '2026_09_28_100000_them_checked_at_vao_xml3176_informations.php',
            '2026_09_29_120000_them_chain_token_va_signed_file_path_vao_xml3176_informations.php',
        ]);
    }

    /** @test */
    public function migration_them_hai_cot()
    {
        $this->assertTrue(Schema::hasColumn('xml3176_informations', 'chain_token'));
        $this->assertTrue(Schema::hasColumn('xml3176_informations', 'signed_file_path'));
    }

    /** @test */
    public function model_cho_ghi_hai_cot()
    {
        $fillable = (new \App\Models\BHYT\Xml3176Information())->getFillable();

        $this->assertContains('chain_token', $fillable);
        $this->assertContains('signed_file_path', $fillable);
    }

    /** @test */
    public function co_ten_hang_doi_ky_rieng()
    {
        $this->assertSame('JobSignXml3176', config('xml3176.sign_queue_name'));
    }

    /** @test */
    public function nap_lai_xoa_trang_thai_chuoi_nhung_giu_dau_vet_da_gui()
    {
        DB::table('xml3176_informations')->insert([
            'ma_lk' => 'LK1', 'macskcb' => '01929',
            'checked_at' => '2026-09-29 10:00:00',
            'exported_at' => '2026-09-29 10:01:00',
            'signed_file_path' => '01929/2026.09.29_10.01.00_LK1.xml',
            'submitted_at' => '2026-09-29 10:02:00',
        ]);

        (new Xml3176Service())->storeXml3176Information('LK1', '01929', 'import');

        $r = DB::table('xml3176_informations')->where('ma_lk', 'LK1')->first();
        $this->assertNull($r->checked_at, 'Khong xoa thi chuoi moi dung dau kiem cua lan nap truoc');
        $this->assertNull($r->exported_at);
        $this->assertNull($r->signed_file_path, 'Khong xoa thi buoc gui co the gui tep cua lan nap truoc');
        // submitted_at la dau vet ho so da tung len cong, can cho doi soat (chot 28/09/2026).
        $this->assertSame('2026-09-29 10:02:00', $r->submitted_at);
    }
}
```

- [ ] **Step 2: Chạy test, xác nhận đỏ**

Run: `DB_HOST=127.0.0.1 php vendor/bin/phpunit tests/Unit/Xml3176/Chuoi/CotChuoiXuLyTest.php`
Expected: lỗi `require_once(...2026_09_29_120000...): failed to open stream` (migration chưa có).

- [ ] **Step 3: Viết migration**

`database/migrations/2026_09_29_120000_them_chain_token_va_signed_file_path_vao_xml3176_informations.php`:

```php
<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Hai cot cua chuoi kiem -> xuat -> ky -> gui (spec 2026-09-29).
 *
 * chain_token: ma phien xu ly. Moi lan dung mot chuoi moi cho ho so (nap, hoac lenh
 * xml3176:chay-lai-tu-xuat) sinh ma moi; moi job trong chuoi mang ma cua chuoi da sinh ra
 * no va tu thoi khi thay ma tren ho so da khac. Chan truong hop nap lai giua chung: job
 * xuat cua chuoi cu con nam cho se thay "khong co loi nghiem trong" (loi cu vua bi xoa)
 * va xuat du lieu moi chua ai kiem.
 *
 * signed_file_path: duong dan tep da ky tren disk exportXml3176. Buoc ky ghi, buoc gui
 * doc. Truoc day duong dan duoc truyen thang qua tham so job gui, nhung trong mot chuoi
 * dung san tu luc nap thi luc do chua ai biet ten tep (ten co gio phut giay luc ghi).
 */
class ThemChainTokenVaSignedFilePathVaoXml3176Informations extends Migration
{
    public function up()
    {
        Schema::table('xml3176_informations', function (Blueprint $table) {
            $table->string('chain_token', 32)->nullable()->after('checked_at');
            $table->string('signed_file_path')->nullable()->after('sign_method');
        });
    }

    public function down()
    {
        Schema::table('xml3176_informations', function (Blueprint $table) {
            $table->dropColumn(['chain_token', 'signed_file_path']);
        });
    }
}
```

- [ ] **Step 4: Thêm vào `$fillable`**

Trong `app/Models/BHYT/Xml3176Information.php`, ngay sau dòng `'checked_at',` thêm:

```php
        'chain_token',
```

và ngay sau dòng `'sign_method',` thêm:

```php
        'signed_file_path',
```

- [ ] **Step 5: Thêm khoá cấu hình**

Trong `config/xml3176.php`, ngay sau dòng `'submit_queue_name' => ...` thêm:

```php
    'sign_queue_name' => 'JobSignXml3176', //Hang doi rieng cho buoc ky so XML 3176: ky hong vi ly do CUC BO (USB token, HSM), gui hong vi MANG - gop lai thi mang chap mot lan la ky lai ba lan
```

- [ ] **Step 6: Nạp lại xoá `signed_file_path`**

Trong `app/Services/Xml3176Service.php`, nhánh `if ($operationType === 'import')`, ngay sau dòng `$values['checked_at'] = null;` thêm:

```php
                // Tep da ky cua lan nap truoc khong con dung voi du lieu moi. De lai thi mot
                // job gui lot qua se gui tep cu.
                $values['signed_file_path'] = null;
```

- [ ] **Step 7: Chạy test, xác nhận xanh**

Run: `DB_HOST=127.0.0.1 php vendor/bin/phpunit tests/Unit/Xml3176/Chuoi/CotChuoiXuLyTest.php`
Expected: `OK (4 tests, ...)`

- [ ] **Step 8: Commit**

```bash
git add database/migrations/2026_09_29_120000_them_chain_token_va_signed_file_path_vao_xml3176_informations.php app/Models/BHYT/Xml3176Information.php config/xml3176.php app/Services/Xml3176Service.php tests/Unit/Xml3176/Chuoi/CotChuoiXuLyTest.php
git commit -m "feat(xml3176): cot chain_token, signed_file_path va hang doi ky rieng

Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>"
```

---

### Task 2: Trait `ThuocChuoiXml3176`

**Files:**
- Create: `app/Jobs/Concerns/ThuocChuoiXml3176.php`
- Test: `tests/Unit/Xml3176/Chuoi/ThuocChuoiXml3176Test.php`

**Interfaces:**
- Consumes: cột `chain_token` (Task 1).
- Produces (mọi job Task 4–7 dùng):
  - `protected $chainToken = null;`
  - `protected function laJobCu(): bool` — `true` khi job không mang mã (serialize bởi mã cũ).
  - `protected function conHieuLuc($maLk): bool` — mã khác null và bằng `chain_token` trên hồ sơ.
  - `protected function catChuoi(): void` — `$this->chained = []`.
  - `protected function ghiNeuConHieuLuc($maLk, array $cot): void` — `update($cot)` chỉ khi `conHieuLuc()`.

- [ ] **Step 1: Viết test đỏ**

`tests/Unit/Xml3176/Chuoi/ThuocChuoiXml3176Test.php`:

```php
<?php

namespace Tests\Unit\Xml3176\Chuoi;

use App\Jobs\Concerns\ThuocChuoiXml3176;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\DB;
use Tests\Support\Xml3176RuleTestSupport;
use Tests\TestCase;

class ThuocChuoiXml3176Test extends TestCase
{
    use Xml3176RuleTestSupport;

    protected function setUp()
    {
        parent::setUp();
        $this->bootXml3176Sqlite([
            '2026_01_09_162044_create_xml3176_informations_table.php',
            '2026_03_28_201021_add_sign_method_to_xml3176_informations.php',
            '2026_09_28_100000_them_checked_at_vao_xml3176_informations.php',
            '2026_09_29_120000_them_chain_token_va_signed_file_path_vao_xml3176_informations.php',
        ]);
        DB::table('xml3176_informations')->insert(['ma_lk' => 'LK1', 'macskcb' => '01929', 'chain_token' => 'MA_DUNG']);
    }

    private function job($ma)
    {
        return new class($ma) {
            use Queueable, ThuocChuoiXml3176;

            public function __construct($ma) { $this->chainToken = $ma; }

            public function goi($ham, ...$thamSo) { return $this->{$ham}(...$thamSo); }
        };
    }

    /** @test */
    public function ma_khop_la_con_hieu_luc()
    {
        $this->assertTrue($this->job('MA_DUNG')->goi('conHieuLuc', 'LK1'));
    }

    /** @test */
    public function ma_lech_la_het_hieu_luc()
    {
        // Ho so da duoc nap lai (hoac lenh cuu da dung chuoi moi): chuoi nay phai thoi.
        $this->assertFalse($this->job('MA_CU')->goi('conHieuLuc', 'LK1'));
    }

    /** @test */
    public function job_khong_mang_ma_la_job_cu_va_khong_bao_gio_con_hieu_luc()
    {
        $job = $this->job(null);

        $this->assertTrue($job->goi('laJobCu'));
        $this->assertFalse($job->goi('conHieuLuc', 'LK1'));
        $this->assertFalse($this->job('MA_DUNG')->goi('laJobCu'));
    }

    /** @test */
    public function ho_so_khong_ton_tai_la_het_hieu_luc()
    {
        $this->assertFalse($this->job('MA_DUNG')->goi('conHieuLuc', 'KHONG_CO'));
    }

    /** @test */
    public function cat_chuoi_xoa_cac_buoc_phia_sau()
    {
        $job = $this->job('MA_DUNG');
        $job->chained = ['buoc sau 1', 'buoc sau 2'];

        $job->goi('catChuoi');

        $this->assertSame([], $job->chained);
    }

    /** @test */
    public function chi_ghi_khi_ma_con_khop()
    {
        $this->job('MA_CU')->goi('ghiNeuConHieuLuc', 'LK1', ['export_error' => 'loi chuoi cu']);
        $this->assertNull(DB::table('xml3176_informations')->value('export_error'),
            'Chuoi cu khong duoc ghi de trang thai cua chuoi moi');

        $this->job('MA_DUNG')->goi('ghiNeuConHieuLuc', 'LK1', ['export_error' => 'loi that']);
        $this->assertSame('loi that', DB::table('xml3176_informations')->value('export_error'));
    }
}
```

- [ ] **Step 2: Chạy test, xác nhận đỏ**

Run: `DB_HOST=127.0.0.1 php vendor/bin/phpunit tests/Unit/Xml3176/Chuoi/ThuocChuoiXml3176Test.php`
Expected: lỗi `Trait 'App\Jobs\Concerns\ThuocChuoiXml3176' not found`.

- [ ] **Step 3: Viết trait**

`app/Jobs/Concerns/ThuocChuoiXml3176.php`:

```php
<?php

namespace App\Jobs\Concerns;

use App\Models\BHYT\Xml3176Information;

/**
 * Phan chung cua moi job trong chuoi kiem -> xuat -> ky -> gui cua mot ho so XML3176.
 *
 * MA PHIEN (chain_token): moi lan dung mot chuoi moi cho ho so - do nap, hoac do lenh
 * xml3176:chay-lai-tu-xuat - sinh mot ma moi, ghi len ho so. Moi job mang ma cua chuoi da
 * sinh ra no. Viec DAU TIEN cua moi job la so ma: lech nghia la ho so da co chuoi moi hon,
 * job nay thoi va KHONG GHI GI - ho so thuoc ve chuoi moi.
 *
 * Vi sao can: nguoi dung nap lai rat thuong xuyen, hang doi kiem co the ton 90 phut
 * (do 29/09/2026). Job xuat cua lan nap truoc con nam cho se thay "khong co loi nghiem
 * trong" - vi nap lai vua xoa sach loi cu - va xuat du lieu moi chua ai kiem.
 *
 * JOB CU: job serialize boi ma truoc khi co chuoi (chainToken = null). Moi job tu quyet
 * dinh lam gi voi job cu - xem tung lop.
 *
 * CAT CHUOI: Laravel 5.5 chi day job ke tiep khi handle() chay xong khong nem
 * (CallQueuedHandler), va chi khi $chained con phan tu. Dung co chu dich thi dat
 * $chained = [] roi thoat binh thuong - khong nem, vi nem la ton luot thu va vao
 * failed_jobs cho mot viec khong he hong.
 */
trait ThuocChuoiXml3176
{
    /** @var string|null Ma phien cua chuoi da sinh ra job; null = job cu truoc nang cap */
    protected $chainToken = null;

    protected function laJobCu()
    {
        return $this->chainToken === null;
    }

    protected function conHieuLuc($maLk)
    {
        if ($this->chainToken === null) {
            return false;
        }

        return Xml3176Information::where('ma_lk', $maLk)->value('chain_token') === $this->chainToken;
    }

    protected function catChuoi()
    {
        $this->chained = [];
    }

    /**
     * Ghi len ho so CHI KHI chuoi nay con hieu luc. Dung trong failed(): loi cua chuoi cu
     * khong duoc de len trang thai cua chuoi moi.
     */
    protected function ghiNeuConHieuLuc($maLk, array $cot)
    {
        if ($this->conHieuLuc($maLk)) {
            Xml3176Information::where('ma_lk', $maLk)->update($cot);
        }
    }
}
```

- [ ] **Step 4: Chạy test, xác nhận xanh**

Run: `DB_HOST=127.0.0.1 php vendor/bin/phpunit tests/Unit/Xml3176/Chuoi/ThuocChuoiXml3176Test.php`
Expected: `OK (6 tests, ...)`

- [ ] **Step 5: Commit**

```bash
git add app/Jobs/Concerns/ThuocChuoiXml3176.php tests/Unit/Xml3176/Chuoi/ThuocChuoiXml3176Test.php
git commit -m "feat(xml3176): trait ThuocChuoiXml3176 - ma phien, cat chuoi, ghi loi co dieu kien

Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>"
```

---

### Task 3: `Xml3176Service` — tệp chờ ký, ký và ghi tệp

Thêm ba phương thức mới. **Chưa** xoá `processExportXml()` — `ExportXml3176Job` cũ còn gọi nó cho tới Task 5, và các test quét mã nguồn còn bám vào nó cho tới Task 9.

**Files:**
- Modify: `app/Services/Xml3176Service.php` (thêm ba phương thức ngay trước `public function processExportXml($ma_lk)`)
- Test: `tests/Unit/Xml3176/Chuoi/Xml3176ServiceTepChoKyTest.php`

**Interfaces:**
- Consumes: cột `signed_file_path` (Task 1).
- Produces:
  - `public static function duongDanChoKy($ma_lk): string` — `'xml3176-cho-ky/{ma_lk đã làm sạch}.xml'` trên disk `local`.
  - `public function xuatTepChoKy($ma_lk): bool` — dựng XML (`getDataForXmlExport()`, việc này ghi `exported_at`), ghi tệp chờ ký; `false` khi không dựng được.
  - `public function kyVaGhiTep($ma_lk)` — trả `false` khi không có tệp chờ ký; ngược lại trả `['isSigned' => bool, 'macskcb' => string]`. Ném `\RuntimeException` khi không ghi được tệp đích.

- [ ] **Step 1: Viết test đỏ**

`tests/Unit/Xml3176/Chuoi/Xml3176ServiceTepChoKyTest.php`:

```php
<?php

namespace Tests\Unit\Xml3176\Chuoi;

use App\Services\Xml3176Service;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\Support\FakeXMLSignService;
use Tests\Support\Xml3176RuleTestSupport;
use Tests\TestCase;

/**
 * Buoc XUAT de lai tep cho ky; buoc KY doc tep do, ky, ghi tep dung cho va dung ten nhu
 * truoc day, copy sang cong ngoai, ghi duong dan cho buoc GUI.
 */
class Xml3176ServiceTepChoKyTest extends TestCase
{
    use Xml3176RuleTestSupport;

    protected function setUp()
    {
        parent::setUp();
        $this->bootXml3176Sqlite([
            '2026_01_09_162044_create_xml3176_informations_table.php',
            '2026_03_28_201021_add_sign_method_to_xml3176_informations.php',
            '2026_09_28_100000_them_checked_at_vao_xml3176_informations.php',
            '2026_09_29_120000_them_chain_token_va_signed_file_path_vao_xml3176_informations.php',
        ]);
        DB::table('xml3176_informations')->insert(['ma_lk' => 'LK1', 'macskcb' => '01929']);

        Storage::fake('local');
        Storage::fake('exportXml3176');
        config([
            'xml3176.export_to_directory_by_day' => false,
            'organization.truc_du_lieu_y_te.enabled' => false,
            'organization.cong_du_lieu_y_te_dien_bien.enabled' => false,
        ]);
    }

    /** Service that, voi lop ky gia. */
    private function service(FakeXMLSignService $ky)
    {
        $s = new Xml3176Service();
        $p = new \ReflectionProperty(Xml3176Service::class, 'xmlSignService');
        $p->setAccessible(true);
        $p->setValue($s, $ky);

        return $s;
    }

    /** @test */
    public function duong_dan_cho_ky_co_dinh_theo_ho_so_va_khong_thoat_ra_ngoai()
    {
        $this->assertSame('xml3176-cho-ky/LK1.xml', Xml3176Service::duongDanChoKy('LK1'));
        // ma_lk den tu tep XML ben ngoai: '../' ghep thang vao duong dan la mo loi thoat.
        $this->assertNotContains('..', Xml3176Service::duongDanChoKy('../../etc/x'));
    }

    /** @test */
    public function xuat_ghi_tep_cho_ky()
    {
        $s = new class extends Xml3176Service {
            public function getDataForXmlExport($ma) { return '<GIAMDINHHS/>'; }
        };

        $this->assertTrue($s->xuatTepChoKy('LK1'));
        $this->assertSame('<GIAMDINHHS/>', Storage::disk('local')->get('xml3176-cho-ky/LK1.xml'));
    }

    /** @test */
    public function xuat_khong_dung_duoc_du_lieu_thi_tra_false_va_khong_ghi()
    {
        $s = new class extends Xml3176Service {
            public function getDataForXmlExport($ma) { return false; }
        };

        $this->assertFalse($s->xuatTepChoKy('LK1'));
        $this->assertFalse(Storage::disk('local')->exists('xml3176-cho-ky/LK1.xml'));
    }

    /** @test */
    public function ky_thanh_cong_ghi_tep_da_ky_va_duong_dan_roi_xoa_tep_cho_ky()
    {
        Storage::disk('local')->put('xml3176-cho-ky/LK1.xml', '<CHUAKY/>');
        $ky = new FakeXMLSignService();

        $kq = $this->service($ky)->kyVaGhiTep('LK1');

        $this->assertSame(['isSigned' => true, 'macskcb' => '01929'], $kq);
        $this->assertSame('<CHUAKY/>', $ky->xmlNhanDuoc, 'Phai ky dung noi dung tep cho ky');

        $r = DB::table('xml3176_informations')->where('ma_lk', 'LK1')->first();
        $this->assertEquals(1, $r->is_signed);
        $this->assertSame('USB Token', $r->sign_method);
        $this->assertRegExp('#^01929/\d{4}\.\d{2}\.\d{2}_\d{2}\.\d{2}\.\d{2}_LK1\.xml$#', $r->signed_file_path,
            'Ten tep va thu muc phai giu nhu truoc day - co the co phan mem khac doc thu muc nay');
        $this->assertSame('<DAKY/>', Storage::disk('exportXml3176')->get($r->signed_file_path));
        $this->assertFalse(Storage::disk('local')->exists('xml3176-cho-ky/LK1.xml'));
    }

    /** @test */
    public function ky_khong_duoc_van_ghi_tep_chua_ky_nhu_truoc_day()
    {
        // Giu nguyen hanh vi: ky khong duoc van ghi tep va van copy sang cong ngoai, chi
        // khong gui cong BHXH (buoc ky quyet dinh viec do).
        Storage::disk('local')->put('xml3176-cho-ky/LK1.xml', '<CHUAKY/>');
        $ky = new FakeXMLSignService();
        $ky->ketQua = ['isSigned' => false, 'data' => '<CHUAKY/>', 'method' => null, 'error' => 'HSM tat'];

        $kq = $this->service($ky)->kyVaGhiTep('LK1');

        $this->assertSame(['isSigned' => false, 'macskcb' => '01929'], $kq);
        $r = DB::table('xml3176_informations')->where('ma_lk', 'LK1')->first();
        $this->assertEquals(0, $r->is_signed);
        $this->assertSame('HSM tat', $r->signed_error);
        $this->assertSame('<CHUAKY/>', Storage::disk('exportXml3176')->get($r->signed_file_path));
    }

    /** @test */
    public function khong_co_tep_cho_ky_thi_tra_false_va_khong_ky()
    {
        $ky = new FakeXMLSignService();

        $this->assertFalse($this->service($ky)->kyVaGhiTep('LK1'));
        $this->assertSame(0, $ky->soLanGoi);
    }
}
```

- [ ] **Step 2: Chạy test, xác nhận đỏ**

Run: `DB_HOST=127.0.0.1 php vendor/bin/phpunit tests/Unit/Xml3176/Chuoi/Xml3176ServiceTepChoKyTest.php`
Expected: lỗi `Call to undefined method ...::duongDanChoKy()` (và tương tự cho hai phương thức kia).

- [ ] **Step 3: Viết ba phương thức**

Trong `app/Services/Xml3176Service.php`, ngay trước khối docblock của `public function processExportXml($ma_lk)`, thêm:

```php
    /**
     * Duong dan tep cho ky cua mot ho so, tren disk 'local' (storage/app).
     *
     * Disk 'local' co san o moi co so; config/filesystems.php bi gitignore nen khong them
     * disk moi duoc. KHONG dat trong disk exportXml3176 hay disk cua cac dich vu quet Truc du
     * lieu / Dien Bien (chung doc allFiles()), de tep CHUA KY khong bi nhat nham.
     *
     * Ten co dinh theo ho so: xuat lai la ghi de, chay lai an toan. ma_lk den tu tep XML ben
     * ngoai nen phai lam sach - ghep thang '../' vao duong dan la mo loi thoat ra ngoai.
     */
    public static function duongDanChoKy($ma_lk)
    {
        return 'xml3176-cho-ky/' . preg_replace('/[^A-Za-z0-9_-]/', '_', (string) $ma_lk) . '.xml';
    }

    /**
     * Buoc XUAT cua chuoi: dung XML cua ho so va ghi ra tep cho ky.
     *
     * getDataForXmlExport() ghi exported_at va xoa export_error - giu nhu truoc day.
     *
     * @return bool false khi khong dung duoc du lieu XML
     */
    public function xuatTepChoKy($ma_lk)
    {
        $xmlData = $this->getDataForXmlExport($ma_lk);

        if (!$xmlData) {
            \Log::error('Khong dung duoc du lieu XML cho ma_lk: ' . $ma_lk);
            return false;
        }

        Storage::disk('local')->put(self::duongDanChoKy($ma_lk), $xmlData);

        return true;
    }

    /**
     * Buoc KY cua chuoi: doc tep cho ky, ky, ghi tep dung cho va dung ten nhu truoc day,
     * copy sang cong ngoai, ghi duong dan cho buoc gui, xoa tep cho ky.
     *
     * Ky KHONG duoc van ghi tep (noi dung chua ky) va van copy - giu nguyen hanh vi truoc
     * day; chi khong gui cong BHXH, viec do SignXml3176Job quyet dinh qua QuyetDinhGui.
     *
     * @return array|false ['isSigned' => bool, 'macskcb' => string]; false khi khong co tep cho ky
     * @throws \RuntimeException khi khong ghi duoc tep dich - de job thu lai roi failed() ghi loi
     */
    public function kyVaGhiTep($ma_lk)
    {
        $duongDanChoKy = self::duongDanChoKy($ma_lk);

        if (!Storage::disk('local')->exists($duongDanChoKy)) {
            return false;
        }

        $xmlData = Storage::disk('local')->get($duongDanChoKy);

        $xmlDataSigned = $this->xmlSignService->signXml($xmlData);
        $isSigned = $xmlDataSigned['isSigned'];
        $signMethod = $xmlDataSigned['method'] ?? null;

        if ($xmlDataSigned) {
            $xmlData = $xmlDataSigned['data'];
        }

        $macskcb = $this->getXmlInformation($ma_lk)->macskcb;
        $signedError = $xmlDataSigned['error'] ?? null;

        $this->storeXml3176Information($ma_lk, $macskcb, 'sign', 1, null, $isSigned, $signedError, null, $signMethod);

        // Ten tep va thu muc GIU NGUYEN nhu processExportXml() truoc day: co the co phan mem
        // khac dang doc thu muc nay.
        $fileName = date('Y.m.d_H.i.s') . '_' . $ma_lk . '.xml';
        $directoryPath = config('xml3176.export_to_directory_by_day')
            ? date('Ymd') . '/' . $macskcb
            : $macskcb;

        if ($directoryPath && !Storage::disk('exportXml3176')->exists($directoryPath)) {
            Storage::disk('exportXml3176')->makeDirectory($directoryPath);
        }

        $filePath = $directoryPath ? $directoryPath . '/' . $fileName : $fileName;

        if (!Storage::disk('exportXml3176')->put($filePath, $xmlData)) {
            throw new \RuntimeException('Khong ghi duoc tep XML: ' . $filePath);
        }

        // Moi cong tu kiem co CUA RIENG NO. Loi copy da duoc bat va ghi log ben trong.
        $fileCopy = app(FileCopyService::class);
        $fileCopy->copyExportXml3176ToTrucDuLieuYTe($filePath);
        $fileCopy->copyExportXml3176ToCongDuLieuYTeDienBien($filePath);

        Xml3176Information::where('ma_lk', $ma_lk)->update(['signed_file_path' => $filePath]);
        Storage::disk('local')->delete($duongDanChoKy);

        return ['isSigned' => (bool) $isSigned, 'macskcb' => $macskcb];
    }
```

`Storage` (dòng 32), `FileCopyService` (dòng 24) và `Xml3176Information` (dòng 20) đã được `use` sẵn trong tệp này — không cần thêm.

- [ ] **Step 4: Chạy test, xác nhận xanh**

Run: `DB_HOST=127.0.0.1 php vendor/bin/phpunit tests/Unit/Xml3176/Chuoi/Xml3176ServiceTepChoKyTest.php`
Expected: `OK (6 tests, ...)`

- [ ] **Step 5: Commit**

```bash
git add app/Services/Xml3176Service.php tests/Unit/Xml3176/Chuoi/Xml3176ServiceTepChoKyTest.php
git commit -m "feat(xml3176): tach xuat tep cho ky va ky-ghi tep thanh hai phuong thuc

Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>"
```

---

### Task 4: Hai job kiểm — mã phiên, thử lại hữu hạn, ghi lỗi khi hỏng

**Files:**
- Modify: `app/Jobs/CheckXml3176TypeJob.php`
- Modify: `app/Jobs/CheckCompleteXml3176RecordJob.php`
- Test: `tests/Unit/Xml3176/Chuoi/JobKiemTrongChuoiTest.php`

**Interfaces:**
- Consumes: trait `ThuocChuoiXml3176` (Task 2).
- Produces: `new CheckXml3176TypeJob($maLk, $xmlType, $chainToken = null)`; `new CheckCompleteXml3176RecordJob($ma_lk, $chainToken = null)`; cả hai `$tries = 2`, `$timeout = 240`.

- [ ] **Step 1: Viết test đỏ**

`tests/Unit/Xml3176/Chuoi/JobKiemTrongChuoiTest.php`:

```php
<?php

namespace Tests\Unit\Xml3176\Chuoi;

use App\Jobs\CheckCompleteXml3176RecordJob;
use App\Jobs\CheckXml3176TypeJob;
use App\Services\Xml3176CompleteChecker;
use Illuminate\Support\Facades\DB;
use Tests\Support\Xml3176RuleTestSupport;
use Tests\TestCase;

class JobKiemTrongChuoiTest extends TestCase
{
    use Xml3176RuleTestSupport;

    protected function setUp()
    {
        parent::setUp();
        $this->bootXml3176Sqlite([
            '2026_01_09_152817_create_xml3176_xml1s_table.php',
            '2026_01_09_161902_create_xml3176_error_results_table.php',
            '2026_01_09_162044_create_xml3176_informations_table.php',
            '2026_03_28_201021_add_sign_method_to_xml3176_informations.php',
            '2026_09_28_100000_them_checked_at_vao_xml3176_informations.php',
            '2026_09_29_120000_them_chain_token_va_signed_file_path_vao_xml3176_informations.php',
        ]);
        DB::table('xml3176_informations')->insert(['ma_lk' => 'LK1', 'macskcb' => '01929', 'chain_token' => 'MA_DUNG']);
    }

    private function loiXml1Cu()
    {
        DB::table('xml3176_error_results')->insert([
            'ma_lk' => 'LK1', 'xml' => 'XML1', 'stt' => 1,
            'error_code' => 'CU', 'description' => 'loi cu', 'critical_error' => 0,
        ]);
    }

    private function soLoiXml1()
    {
        return DB::table('xml3176_error_results')->where('ma_lk', 'LK1')->where('xml', 'XML1')->count();
    }

    /** Checker tong the gia: dem so lan goi, khong cham CSDL. */
    private function checkerTongThe()
    {
        return new class extends Xml3176CompleteChecker {
            public $soLanGoi = 0;
            public function __construct() {}
            public function checkErrors($maLk) { $this->soLanGoi++; }
        };
    }

    // ─── Kiem tung loai ───────────────────────────────────────────────────

    /** @test */
    public function kiem_loai_ma_khop_thi_chay_va_giu_chuoi()
    {
        $this->loiXml1Cu();
        $job = new CheckXml3176TypeJob('LK1', 'XML1', 'MA_DUNG');
        $job->chained = ['buoc sau'];

        $job->handle();

        $this->assertSame(0, $this->soLoiXml1(), 'Job phai chay: tu xoa loi cu cua loai minh');
        $this->assertSame(['buoc sau'], $job->chained);
    }

    /** @test */
    public function kiem_loai_ma_lech_thi_khong_lam_gi_va_cat_chuoi()
    {
        $this->loiXml1Cu();
        $job = new CheckXml3176TypeJob('LK1', 'XML1', 'MA_CU');
        $job->chained = ['buoc sau'];

        $job->handle();

        $this->assertSame(1, $this->soLoiXml1(), 'Chuoi cu khong duoc dung vao ket qua kiem cua chuoi moi');
        $this->assertSame([], $job->chained);
    }

    /** @test */
    public function kiem_loai_job_cu_van_kiem_nhu_thuong()
    {
        $this->loiXml1Cu();

        (new CheckXml3176TypeJob('LK1', 'XML1'))->handle();

        $this->assertSame(0, $this->soLoiXml1());
    }

    /** @test */
    public function kiem_loai_hong_het_luot_thi_ghi_ly_do_vao_cot_xuat()
    {
        (new CheckXml3176TypeJob('LK1', 'XML2', 'MA_DUNG'))->failed(new \RuntimeException('mat ket noi'));

        $loi = DB::table('xml3176_informations')->value('export_error');
        $this->assertContains('Không xuất', $loi);
        $this->assertContains('XML2', $loi);
        $this->assertContains('mat ket noi', $loi);
    }

    /** @test */
    public function kiem_loai_hong_cua_chuoi_cu_khong_ghi()
    {
        (new CheckXml3176TypeJob('LK1', 'XML2', 'MA_CU'))->failed(new \RuntimeException('x'));

        $this->assertNull(DB::table('xml3176_informations')->value('export_error'));
    }

    // ─── Kiem tong the ────────────────────────────────────────────────────

    /** @test */
    public function kiem_tong_the_chay_va_dong_dau()
    {
        $checker = $this->checkerTongThe();

        (new CheckCompleteXml3176RecordJob('LK1', 'MA_DUNG'))->handle($checker);

        $this->assertSame(1, $checker->soLanGoi);
        $this->assertNotNull(DB::table('xml3176_informations')->value('checked_at'));
    }

    /** @test */
    public function tat_kiem_tong_the_van_dong_dau_de_chuoi_di_tiep()
    {
        // xml_3176_not_check chi tat Xml3176CompleteChecker. Job van la moc "buoc kiem da
        // xong" cua chuoi - khong co no thi khong co gi dung giua buoc kiem va buoc xuat.
        config(['organization.xml_3176_not_check' => true]);
        $checker = $this->checkerTongThe();
        $job = new CheckCompleteXml3176RecordJob('LK1', 'MA_DUNG');
        $job->chained = ['xuat'];

        $job->handle($checker);

        $this->assertSame(0, $checker->soLanGoi);
        $this->assertNotNull(DB::table('xml3176_informations')->value('checked_at'));
        $this->assertSame(['xuat'], $job->chained);
    }

    /** @test */
    public function kiem_tong_the_ma_lech_thi_khong_dong_dau()
    {
        $checker = $this->checkerTongThe();
        $job = new CheckCompleteXml3176RecordJob('LK1', 'MA_CU');
        $job->chained = ['xuat'];

        $job->handle($checker);

        $this->assertSame(0, $checker->soLanGoi);
        $this->assertNull(DB::table('xml3176_informations')->value('checked_at'),
            'Dau cua chuoi cu se cho chuoi moi xuat khi chua kiem');
        $this->assertSame([], $job->chained);
    }

    /** @test */
    public function kiem_tong_the_job_cu_van_kiem_va_dong_dau()
    {
        $checker = $this->checkerTongThe();

        (new CheckCompleteXml3176RecordJob('LK1'))->handle($checker);

        $this->assertSame(1, $checker->soLanGoi);
        $this->assertNotNull(DB::table('xml3176_informations')->value('checked_at'));
    }

    /** @test */
    public function kiem_tong_the_hong_thi_ghi_ly_do()
    {
        (new CheckCompleteXml3176RecordJob('LK1', 'MA_DUNG'))->failed(new \RuntimeException('het bo nho'));

        $loi = DB::table('xml3176_informations')->value('export_error');
        $this->assertContains('kiểm tổng thể', $loi);
        $this->assertContains('het bo nho', $loi);
    }
}
```

- [ ] **Step 2: Chạy test, xác nhận đỏ**

Run: `DB_HOST=127.0.0.1 php vendor/bin/phpunit tests/Unit/Xml3176/Chuoi/JobKiemTrongChuoiTest.php`
Expected: nhiều lỗi, ví dụ `kiem_loai_ma_lech_thi_khong_lam_gi_va_cat_chuoi` đỏ vì job vẫn xoá lỗi; `failed()` chưa tồn tại.

- [ ] **Step 3: Sửa `CheckXml3176TypeJob`**

Trong `app/Jobs/CheckXml3176TypeJob.php`:

Thêm `use App\Jobs\Concerns\ThuocChuoiXml3176;` vào khối `use` đầu tệp, và thêm trait vào dòng `use` trong lớp:

```php
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, ThuocChuoiXml3176;
```

Ngay sau hằng `CO_LO` thêm:

```php
    /**
     * Thu lai HUU HAN. queue:work mac dinh --tries=0 la thu lai VO HAN, va moi hang doi chi co
     * mot worker: mot ho so doc se chan dung moi ho so xep sau no.
     */
    public $tries = 2;

    /** Phai nho hon retry_after (300) cua ket noi database. */
    public $timeout = 240;
```

Thay constructor:

```php
    public function __construct($maLk, $xmlType, $chainToken = null)
    {
        $this->maLk = $maLk;
        $this->xmlType = $xmlType;
        $this->chainToken = $chainToken;
    }
```

Ở đầu `handle()`, trước dòng `$cauHinh = ...`, thêm:

```php
        // Ho so da co chuoi moi hon (nap lai / lenh cuu): thoi, KHONG xoa hay ghi loi - ket qua
        // kiem la cua chuoi moi. Job cu (khong mang ma) van kiem nhu thuong.
        if (!$this->laJobCu() && !$this->conHieuLuc($this->maLk)) {
            $this->catChuoi();
            return;
        }
```

Thêm phương thức cuối lớp:

```php
    /**
     * Het luot thu. Laravel tu bo phan con lai cua chuoi, nen ho so se khong duoc xuat - ghi
     * ly do vao cot XUAT vi do la hau qua nguoi van hanh nhin thay.
     */
    public function failed(\Throwable $e)
    {
        \Log::error('CheckXml3176TypeJob that bai: ' . $e->getMessage(), [
            'ma_lk' => $this->maLk, 'xml' => $this->xmlType,
        ]);

        $this->ghiNeuConHieuLuc($this->maLk, [
            'export_error' => 'Không xuất: bước kiểm ' . $this->xmlType . ' lỗi — ' . $e->getMessage(),
        ]);
    }
```

- [ ] **Step 4: Sửa `CheckCompleteXml3176RecordJob`**

Thay toàn bộ nội dung `app/Jobs/CheckCompleteXml3176RecordJob.php` bằng:

```php
<?php

namespace App\Jobs;

use App\Jobs\Concerns\ThuocChuoiXml3176;
use App\Models\BHYT\Xml3176Information;
use App\Services\Xml3176CompleteChecker;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

/**
 * Buoc kiem CUOI CUNG cua chuoi: kiem tong the roi dong dau checked_at.
 *
 * Job nay luon co mat trong chuoi, ke ca khi xml_3176_not_check bat: co do chi tat
 * Xml3176CompleteChecker. Job la moc "buoc kiem da xong" - khong co no thi khong co gi
 * dung giua buoc kiem va buoc xuat.
 */
class CheckCompleteXml3176RecordJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, ThuocChuoiXml3176;

    /** Thu lai huu han - xem chu thich o CheckXml3176TypeJob. */
    public $tries = 2;

    /** Phai nho hon retry_after (300) cua ket noi database. */
    public $timeout = 240;

    protected $ma_lk;

    public function __construct($ma_lk, $chainToken = null)
    {
        $this->ma_lk = $ma_lk;
        $this->chainToken = $chainToken;
    }

    public function handle(Xml3176CompleteChecker $xmlCompleteChecker)
    {
        // Dau cua chuoi cu se cho chuoi moi xuat khi chua kiem: thoi, khong dong dau.
        if (!$this->laJobCu() && !$this->conHieuLuc($this->ma_lk)) {
            $this->catChuoi();
            return;
        }

        if (!config('organization.xml_3176_not_check', false)) {
            $xmlCompleteChecker->checkErrors($this->ma_lk);
        }

        Xml3176Information::where('ma_lk', $this->ma_lk)
            ->update(['checked_at' => now()]);
    }

    public function failed(\Throwable $e)
    {
        \Log::error('CheckCompleteXml3176RecordJob that bai: ' . $e->getMessage(), ['ma_lk' => $this->ma_lk]);

        $this->ghiNeuConHieuLuc($this->ma_lk, [
            'export_error' => 'Không xuất: bước kiểm tổng thể lỗi — ' . $e->getMessage(),
        ]);
    }
}
```

- [ ] **Step 5: Chạy test mới và test cũ của job kiểm**

Run: `DB_HOST=127.0.0.1 php vendor/bin/phpunit tests/Unit/Xml3176/Chuoi/JobKiemTrongChuoiTest.php`
Expected: `OK (10 tests, ...)`

Run: `DB_HOST=127.0.0.1 php vendor/bin/phpunit tests/Unit/Xml3176/CheckXml3176TypeJobTest.php`
Expected: `OK (5 tests, ...)`

- [ ] **Step 6: Commit**

```bash
git add app/Jobs/CheckXml3176TypeJob.php app/Jobs/CheckCompleteXml3176RecordJob.php tests/Unit/Xml3176/Chuoi/JobKiemTrongChuoiTest.php
git commit -m "feat(xml3176): job kiem mang ma phien, thu lai huu han, ghi ly do khi hong

Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>"
```

---

### Task 5: Viết lại `ExportXml3176Job`

Bỏ vòng chờ 15 giây × 10. Job xuất nay là **một cửa kiểm** rồi ghi tệp chờ ký.

**Files:**
- Modify: `app/Jobs/ExportXml3176Job.php` (thay toàn bộ)
- Delete: `tests/Unit/Xml3176/XuatChoKiemXongTest.php` (test của cơ chế chờ đã bỏ; hai khẳng định còn đúng — nạp lại xoá `checked_at`, kiểm tổng thể đóng dấu — đã nằm trong `CotChuoiXuLyTest` và `JobKiemTrongChuoiTest`)
- Test: `tests/Unit/Xml3176/Chuoi/ExportXml3176JobTest.php`

**Interfaces:**
- Consumes: trait (Task 2); `Xml3176Service::xuatTepChoKy($ma_lk): bool` (Task 3).
- Produces: `new ExportXml3176Job($ma_lk, $chainToken = null)`; `$tries = 2`, `$timeout = 120`.

- [ ] **Step 1: Viết test đỏ**

`tests/Unit/Xml3176/Chuoi/ExportXml3176JobTest.php`:

```php
<?php

namespace Tests\Unit\Xml3176\Chuoi;

use App\Jobs\ExportXml3176Job;
use App\Services\Xml3176Service;
use Illuminate\Support\Facades\DB;
use Tests\Support\Xml3176RuleTestSupport;
use Tests\TestCase;

/**
 * Buoc XUAT trong chuoi: mot cua kiem roi ghi tep cho ky.
 *
 * Truoc day (28/09/2026) job cho kiem xong theo thoi gian - 15s x 10 lan - va ngay nap lo
 * 29/09 hang doi kiem ton toi 90 phut nen 5.043/5.443 ho so het luot cho, 1.934 ho so sach
 * khong len cong. Nay job chi chay khi chuoi da di qua buoc kiem tong the.
 */
class ExportXml3176JobTest extends TestCase
{
    use Xml3176RuleTestSupport;

    protected function setUp()
    {
        parent::setUp();
        $this->bootXml3176Sqlite([
            '2026_01_09_152817_create_xml3176_xml1s_table.php',
            '2026_01_09_161902_create_xml3176_error_results_table.php',
            '2026_01_09_162044_create_xml3176_informations_table.php',
            '2026_03_28_201021_add_sign_method_to_xml3176_informations.php',
            '2026_09_28_100000_them_checked_at_vao_xml3176_informations.php',
            '2026_09_29_120000_them_chain_token_va_signed_file_path_vao_xml3176_informations.php',
        ]);
        DB::table('xml3176_xml1s')->insert(['ma_lk' => 'LK1', 'stt' => 1, 'ngay_ra' => '202001010800']);
        DB::table('xml3176_informations')->insert([
            'ma_lk' => 'LK1', 'macskcb' => '01929',
            'chain_token' => 'MA_DUNG', 'checked_at' => '2026-09-29 09:00:00',
        ]);
        config(['organization.export_xml_not_check' => false]);
    }

    /** Service gia: dem so lan xuatTepChoKy() duoc goi. */
    private function service($ketQua = true)
    {
        return new class($ketQua) extends Xml3176Service {
            public $soLanXuat = 0;
            private $kq;
            public function __construct($kq) { $this->kq = $kq; }
            public function xuatTepChoKy($ma) { $this->soLanXuat++; return $this->kq; }
        };
    }

    private function chay($maPhien = 'MA_DUNG', $service = null)
    {
        $service = $service ?: $this->service();
        $job = new ExportXml3176Job('LK1', $maPhien);
        $job->chained = ['ky', 'gui'];
        $job->handle($service);

        return [$job, $service];
    }

    private function loiXuat()
    {
        return DB::table('xml3176_informations')->where('ma_lk', 'LK1')->value('export_error');
    }

    private function loiNghiemTrong($soDong)
    {
        for ($i = 1; $i <= $soDong; $i++) {
            DB::table('xml3176_error_results')->insert([
                'ma_lk' => 'LK1', 'xml' => 'XML1', 'stt' => $i,
                'error_code' => 'X' . $i, 'description' => 'x', 'critical_error' => 1,
            ]);
        }
    }

    /** @test */
    public function sach_va_da_kiem_thi_xuat_va_giu_chuoi()
    {
        list($job, $s) = $this->chay();

        $this->assertSame(1, $s->soLanXuat);
        $this->assertSame(['ky', 'gui'], $job->chained);
    }

    /** @test */
    public function con_loi_nghiem_trong_thi_khong_xuat_ghi_so_loi_va_cat_chuoi()
    {
        // Truoc day viec chan nay IM LANG: nguoi van hanh khong phan biet duoc "bi chan" voi
        // "chua toi luot".
        $this->loiNghiemTrong(2);

        list($job, $s) = $this->chay();

        $this->assertSame(0, $s->soLanXuat);
        $this->assertSame('Không xuất: còn 2 lỗi nghiêm trọng', $this->loiXuat());
        $this->assertSame([], $job->chained);
    }

    /** @test */
    public function bo_kiem_loi_nghiem_trong_thi_van_xuat()
    {
        config(['organization.export_xml_not_check' => true]);
        $this->loiNghiemTrong(2);

        list($job, $s) = $this->chay();

        $this->assertSame(1, $s->soLanXuat);
        $this->assertSame(['ky', 'gui'], $job->chained);
    }

    /** @test */
    public function chua_kiem_xong_thi_khong_xuat()
    {
        // Trong chuoi dieu nay khong xay ra; nhung lenh cuu --ma-lk co the chi dinh ho so
        // chua kiem. Hong thi dong.
        DB::table('xml3176_informations')->update(['checked_at' => null]);

        list($job, $s) = $this->chay();

        $this->assertSame(0, $s->soLanXuat);
        $this->assertSame('Không xuất: hồ sơ chưa kiểm xong', $this->loiXuat());
        $this->assertSame([], $job->chained);
    }

    /** @test */
    public function ngay_ra_o_tuong_lai_thi_khong_xuat_va_ghi_ro()
    {
        // Truoc day job thoat IM LANG - ho so khong bao gio duoc xuat va khong ai biet.
        DB::table('xml3176_xml1s')->update(['ngay_ra' => '209912312359']);

        list($job, $s) = $this->chay();

        $this->assertSame(0, $s->soLanXuat);
        $this->assertContains('209912312359', $this->loiXuat());
        $this->assertContains('sau thời điểm xuất', $this->loiXuat());
        $this->assertSame([], $job->chained);
    }

    /** @test */
    public function khong_dung_duoc_xml_thi_ghi_loi_va_cat_chuoi()
    {
        list($job, $s) = $this->chay('MA_DUNG', $this->service(false));

        $this->assertSame(1, $s->soLanXuat);
        $this->assertContains('Xuất lỗi', $this->loiXuat());
        $this->assertSame([], $job->chained);
    }

    /** @test */
    public function ma_lech_thi_khong_lam_gi_khong_ghi_gi()
    {
        $this->loiNghiemTrong(1);

        list($job, $s) = $this->chay('MA_CU');

        $this->assertSame(0, $s->soLanXuat);
        $this->assertNull($this->loiXuat(), 'Chuoi cu khong duoc ghi de trang thai cua chuoi moi');
        $this->assertSame([], $job->chained);
    }

    /** @test */
    public function job_cu_dang_cho_thi_thoat_khong_xuat()
    {
        // Job dang cu (vong cho 15s x 10) con nam trong hang doi luc nang cap. Phia sau no
        // khong con buoc nao; lenh xml3176:chay-lai-tu-xuat gom ho so lai.
        $s = $this->service();

        (new ExportXml3176Job('LK1'))->handle($s);

        $this->assertSame(0, $s->soLanXuat);
    }

    /** @test */
    public function hong_het_luot_thi_ghi_ly_do_neu_ma_con_khop()
    {
        (new ExportXml3176Job('LK1', 'MA_DUNG'))->failed(new \RuntimeException('dia day'));
        $this->assertSame('Xuất lỗi — dia day', $this->loiXuat());

        DB::table('xml3176_informations')->update(['export_error' => null]);
        (new ExportXml3176Job('LK1', 'MA_CU'))->failed(new \RuntimeException('dia day'));
        $this->assertNull($this->loiXuat());
    }

    /** @test */
    public function khong_con_vong_cho_theo_thoi_gian()
    {
        $this->assertFalse(defined(ExportXml3176Job::class . '::SO_LAN_CHO_TOI_DA'),
            'Cho theo thoi gian da lam 1.934 ho so sach khong len cong ngay 29/09/2026');
        $this->assertFalse(method_exists(ExportXml3176Job::class, 'phaiChoKiemLoi'));
    }
}
```

- [ ] **Step 2: Chạy test, xác nhận đỏ**

Run: `DB_HOST=127.0.0.1 php vendor/bin/phpunit tests/Unit/Xml3176/Chuoi/ExportXml3176JobTest.php`
Expected: nhiều test đỏ (job hiện gọi `processExportXml`, còn `SO_LAN_CHO_TOI_DA`).

- [ ] **Step 3: Viết lại job**

Thay toàn bộ `app/Jobs/ExportXml3176Job.php` bằng:

```php
<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

use App\Jobs\Concerns\ThuocChuoiXml3176;
use App\Services\Xml3176Service;
use App\Models\BHYT\Xml3176ErrorResult;
use App\Models\BHYT\Xml3176Information;
use App\Models\BHYT\Xml3176Xml1;

/**
 * Buoc XUAT cua chuoi kiem -> xuat -> ky -> gui: mot cua kiem roi ghi tep cho ky.
 *
 * Job chi chay SAU buoc kiem tong the vi no nam sau buoc do trong cung mot chuoi
 * (Xml3176ChuoiXuLy). Truoc 29/09/2026 job chay song song voi buoc kiem va phai cho theo thoi
 * gian (15s x 10); ngay nap lo 29/09 hang doi kiem ton toi 90 phut, 1.934 ho so sach het luot
 * cho va khong len cong.
 *
 * Moi lan DUNG co chu dich deu ghi ly do vao export_error roi cat chuoi - khong nem, vi nem
 * la ton luot thu cho mot viec khong he hong.
 */
class ExportXml3176Job implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, ThuocChuoiXml3176;

    /** Thu lai huu han - queue:work mac dinh thu lai vo han. */
    public $tries = 2;

    /** Phai nho hon retry_after (300) cua ket noi database. */
    public $timeout = 120;

    protected $ma_lk;

    public function __construct($ma_lk, $chainToken = null)
    {
        $this->ma_lk = $ma_lk;
        $this->chainToken = $chainToken;
    }

    public function handle(Xml3176Service $xmlService)
    {
        if ($this->laJobCu()) {
            // Job dang cu (vong cho 15s x 10) con nam trong hang doi luc nang cap. Phia sau no
            // khong con buoc nao; lenh xml3176:chay-lai-tu-xuat gom ho so nay lai.
            Log::info('ExportXml3176Job: job dang cu, bo qua ma_lk ' . $this->ma_lk);
            return;
        }

        if (!$this->conHieuLuc($this->ma_lk)) {
            $this->catChuoi();
            return;
        }

        $thongTin = Xml3176Information::where('ma_lk', $this->ma_lk)->first();

        if (empty($thongTin->checked_at)) {
            $this->dung('Không xuất: hồ sơ chưa kiểm xong');
            return;
        }

        $xml1 = Xml3176Xml1::where('ma_lk', $this->ma_lk)->first();

        if ($xml1 && $this->ngayRaOTuongLai($xml1->ngay_ra)) {
            $this->dung('Không xuất: ngày ra (' . $xml1->ngay_ra . ') sau thời điểm xuất');
            return;
        }

        if (!config('organization.export_xml_not_check')) {
            $soLoi = Xml3176ErrorResult::where('ma_lk', $this->ma_lk)
                ->where('critical_error', true)
                ->count();

            if ($soLoi > 0) {
                $this->dung('Không xuất: còn ' . $soLoi . ' lỗi nghiêm trọng');
                return;
            }
        }

        if (!$xmlService->xuatTepChoKy($this->ma_lk)) {
            $this->dung('Xuất lỗi — không dựng được dữ liệu XML của hồ sơ');
        }
    }

    public function failed(\Throwable $e)
    {
        Log::error('ExportXml3176Job that bai: ' . $e->getMessage(), ['ma_lk' => $this->ma_lk]);

        $this->ghiNeuConHieuLuc($this->ma_lk, ['export_error' => 'Xuất lỗi — ' . $e->getMessage()]);
    }

    private function dung($lyDo)
    {
        Xml3176Information::where('ma_lk', $this->ma_lk)->update(['export_error' => $lyDo]);
        $this->catChuoi();
    }

    /** ngay_ra dang YmdHi (12 ky tu). Sai dang thi coi nhu khong o tuong lai, nhu truoc day. */
    private function ngayRaOTuongLai($ngayRa)
    {
        if (!$ngayRa || strlen($ngayRa) != 12) {
            return false;
        }

        try {
            return Carbon::createFromFormat('YmdHi', $ngayRa)->gt(Carbon::now());
        } catch (\Exception $e) {
            Log::warning('Invalid date format for ngay_ra: ' . $ngayRa);
            return false;
        }
    }
}
```

- [ ] **Step 4: Xoá test của cơ chế chờ cũ**

```bash
git rm tests/Unit/Xml3176/XuatChoKiemXongTest.php
```

- [ ] **Step 5: Chạy test, xác nhận xanh**

Run: `DB_HOST=127.0.0.1 php vendor/bin/phpunit tests/Unit/Xml3176/Chuoi/ExportXml3176JobTest.php`
Expected: `OK (10 tests, ...)`

- [ ] **Step 6: Commit**

```bash
git add app/Jobs/ExportXml3176Job.php tests/Unit/Xml3176/Chuoi/ExportXml3176JobTest.php
git commit -m "feat(xml3176): job xuat thanh cua kiem trong chuoi, bo vong cho theo thoi gian

Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>"
```

---

### Task 6: `SignXml3176Job` (mới)

**Files:**
- Create: `app/Jobs/SignXml3176Job.php`
- Test: `tests/Unit/Xml3176/Chuoi/SignXml3176JobTest.php`

**Interfaces:**
- Consumes: trait (Task 2); `Xml3176Service::kyVaGhiTep($ma_lk)` (Task 3); `App\Services\Xml3176\QuyetDinhGui::nen($guiBat, $daKy)` với hằng `GUI`, `CHUA_KY`, `KHONG_GUI`.
- Produces: `new SignXml3176Job($ma_lk, $chainToken)`; `$tries = 2`, `$timeout = 120`.

- [ ] **Step 1: Viết test đỏ**

`tests/Unit/Xml3176/Chuoi/SignXml3176JobTest.php`:

```php
<?php

namespace Tests\Unit\Xml3176\Chuoi;

use App\Jobs\SignXml3176Job;
use App\Services\Xml3176Service;
use Illuminate\Support\Facades\DB;
use Tests\Support\Xml3176RuleTestSupport;
use Tests\TestCase;

/**
 * Buoc KY trong chuoi. Quyet dinh chuoi co di tiep toi buoc GUI hay khong - thay cho viec
 * truoc day processExportXml() hoi QuyetDinhGui roi moi dispatch job gui.
 */
class SignXml3176JobTest extends TestCase
{
    use Xml3176RuleTestSupport;

    protected function setUp()
    {
        parent::setUp();
        $this->bootXml3176Sqlite([
            '2026_01_09_162044_create_xml3176_informations_table.php',
            '2026_03_28_201021_add_sign_method_to_xml3176_informations.php',
            '2026_09_28_100000_them_checked_at_vao_xml3176_informations.php',
            '2026_09_29_120000_them_chain_token_va_signed_file_path_vao_xml3176_informations.php',
        ]);
        DB::table('xml3176_informations')->insert(['ma_lk' => 'LK1', 'macskcb' => '01929', 'chain_token' => 'MA_DUNG']);
        config(['organization.BHYT.submit_xml_3176_enabled' => true]);
    }

    /** Service gia: kyVaGhiTep() tra ket qua dung san. */
    private function service($ketQua)
    {
        return new class($ketQua) extends Xml3176Service {
            public $soLanKy = 0;
            private $kq;
            public function __construct($kq) { $this->kq = $kq; }
            public function kyVaGhiTep($ma) { $this->soLanKy++; return $this->kq; }
        };
    }

    private function chay($ketQua, $maPhien = 'MA_DUNG')
    {
        $s = $this->service($ketQua);
        $job = new SignXml3176Job('LK1', $maPhien);
        $job->chained = ['gui'];
        $job->handle($s);

        return [$job, $s];
    }

    private function cot($ten)
    {
        return DB::table('xml3176_informations')->where('ma_lk', 'LK1')->value($ten);
    }

    /** @test */
    public function ky_duoc_va_bat_gui_thi_chuoi_di_tiep()
    {
        list($job, $s) = $this->chay(['isSigned' => true, 'macskcb' => '01929']);

        $this->assertSame(1, $s->soLanKy);
        $this->assertSame(['gui'], $job->chained);
    }

    /** @test */
    public function chua_ky_thi_ghi_nhan_va_khong_gui()
    {
        // Chua ky ma bo qua im lang thi nguoi dung khong biet vi sao ho so khong di.
        list($job,) = $this->chay(['isSigned' => false, 'macskcb' => '01929']);

        $this->assertSame('Hồ sơ chưa ký số, không gửi lên cổng BHXH', $this->cot('submit_error'));
        $this->assertSame([], $job->chained);
    }

    /** @test */
    public function tat_gui_thi_cat_chuoi_va_khong_ghi_gi()
    {
        // Tat gui thi khong co lan gui nao dien ra - ghi submit_error la BIA.
        config(['organization.BHYT.submit_xml_3176_enabled' => false]);

        list($job,) = $this->chay(['isSigned' => true, 'macskcb' => '01929']);

        $this->assertNull($this->cot('submit_error'));
        $this->assertSame([], $job->chained);
    }

    /** @test */
    public function khong_co_tep_cho_ky_thi_ghi_loi_ky_va_cat_chuoi()
    {
        list($job,) = $this->chay(false);

        $this->assertContains('không tìm thấy tệp chờ ký', $this->cot('signed_error'));
        $this->assertSame([], $job->chained);
    }

    /** @test */
    public function ma_lech_thi_khong_ky()
    {
        list($job, $s) = $this->chay(['isSigned' => true, 'macskcb' => '01929'], 'MA_CU');

        $this->assertSame(0, $s->soLanKy);
        $this->assertSame([], $job->chained);
    }

    /** @test */
    public function hong_het_luot_thi_ghi_loi_ky_neu_ma_con_khop()
    {
        (new SignXml3176Job('LK1', 'MA_DUNG'))->failed(new \RuntimeException('HSM khong phan hoi'));
        $this->assertSame('Ký lỗi — HSM khong phan hoi', $this->cot('signed_error'));

        DB::table('xml3176_informations')->update(['signed_error' => null]);
        (new SignXml3176Job('LK1', 'MA_CU'))->failed(new \RuntimeException('x'));
        $this->assertNull($this->cot('signed_error'));
    }
}
```

- [ ] **Step 2: Chạy test, xác nhận đỏ**

Run: `DB_HOST=127.0.0.1 php vendor/bin/phpunit tests/Unit/Xml3176/Chuoi/SignXml3176JobTest.php`
Expected: lỗi `Class 'App\Jobs\SignXml3176Job' not found`.

- [ ] **Step 3: Viết job**

`app/Jobs/SignXml3176Job.php`:

```php
<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;

use App\Jobs\Concerns\ThuocChuoiXml3176;
use App\Models\BHYT\Xml3176Information;
use App\Services\Xml3176Service;
use App\Services\Xml3176\QuyetDinhGui;

/**
 * Buoc KY cua chuoi kiem -> xuat -> ky -> gui.
 *
 * VI SAO TACH KHOI BUOC XUAT VA BUOC GUI (khuon SignCtdtJob / SignTt12Job): ky hong do ly do
 * CUC BO (USB token bi rut, HSM khong phan hoi) con gui hong do MANG. Gop voi buoc gui thi
 * mang chap mot lan la ky lai ba lan - ma ky la thao tac ton thoi gian nhat.
 *
 * Job nay QUYET DINH chuoi co di tiep toi buoc gui hay khong, qua QuyetDinhGui - thay cho
 * viec truoc day processExportXml() hoi roi moi dispatch job gui.
 */
class SignXml3176Job implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, ThuocChuoiXml3176;

    /** Ky lai it lan: hong ky thuong do ly do cuc bo, thu lai it giup. */
    public $tries = 2;

    /** Phai nho hon retry_after (300) cua ket noi database. */
    public $timeout = 120;

    protected $ma_lk;

    public function __construct($ma_lk, $chainToken)
    {
        $this->ma_lk = $ma_lk;
        $this->chainToken = $chainToken;
    }

    public function handle(Xml3176Service $xmlService)
    {
        // Lop moi: khong co job cu nao cua lop nay trong hang doi, nen ma null cung la het
        // hieu luc.
        if (!$this->conHieuLuc($this->ma_lk)) {
            $this->catChuoi();
            return;
        }

        $ketQua = $xmlService->kyVaGhiTep($this->ma_lk);

        if ($ketQua === false) {
            Xml3176Information::where('ma_lk', $this->ma_lk)
                ->update(['signed_error' => 'Ký lỗi — không tìm thấy tệp chờ ký']);
            $this->catChuoi();
            return;
        }

        $quyetDinh = QuyetDinhGui::nen(
            config('organization.BHYT.submit_xml_3176_enabled', false),
            $ketQua['isSigned']
        );

        if ($quyetDinh === QuyetDinhGui::GUI) {
            return;
        }

        if ($quyetDinh === QuyetDinhGui::CHUA_KY) {
            // Ghi qua dung nhanh 'submit' san co: submit_error duoc dat va submitted_at de
            // null - dung hinh dang cua mot ho so bi cong tu choi.
            $xmlService->storeXml3176Information($this->ma_lk, $ketQua['macskcb'], 'submit', 1,
                'Hồ sơ chưa ký số, không gửi lên cổng BHXH');
        }

        // KHONG_GUI: chuc nang gui dang tat - khong ghi gi, ghi la bia.
        $this->catChuoi();
    }

    public function failed(\Throwable $e)
    {
        Log::error('SignXml3176Job that bai: ' . $e->getMessage(), ['ma_lk' => $this->ma_lk]);

        $this->ghiNeuConHieuLuc($this->ma_lk, ['signed_error' => 'Ký lỗi — ' . $e->getMessage()]);
    }
}
```

- [ ] **Step 4: Chạy test, xác nhận xanh**

Run: `DB_HOST=127.0.0.1 php vendor/bin/phpunit tests/Unit/Xml3176/Chuoi/SignXml3176JobTest.php`
Expected: `OK (6 tests, ...)`

- [ ] **Step 5: Commit**

```bash
git add app/Jobs/SignXml3176Job.php tests/Unit/Xml3176/Chuoi/SignXml3176JobTest.php
git commit -m "feat(xml3176): SignXml3176Job - buoc ky rieng, quyet dinh chuoi co di toi buoc gui

Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>"
```

---

### Task 7: `SubmitXml3176Job` trong chuỗi

**Files:**
- Modify: `app/Jobs/SubmitXml3176Job.php`
- Test: `tests/Unit/Xml3176/Chuoi/SubmitXml3176JobTest.php`

**Interfaces:**
- Consumes: trait (Task 2); cột `signed_file_path` (Task 1, ghi bởi Task 3).
- Produces: `new SubmitXml3176Job($ma_lk, $chainToken = null)`; `public $submitServiceGia = null;` (điểm tiêm cho test, khuôn `SubmitTt12Job`); `$tries = 3`, `$timeout = 60`.

- [ ] **Step 1: Viết test đỏ**

`tests/Unit/Xml3176/Chuoi/SubmitXml3176JobTest.php`:

```php
<?php

namespace Tests\Unit\Xml3176\Chuoi;

use App\Jobs\SubmitXml3176Job;
use App\Services\Xml3176Service;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\Support\Xml3176RuleTestSupport;
use Tests\TestCase;

class SubmitXml3176JobTest extends TestCase
{
    use Xml3176RuleTestSupport;

    protected function setUp()
    {
        parent::setUp();
        $this->bootXml3176Sqlite([
            '2026_01_09_162044_create_xml3176_informations_table.php',
            '2026_03_28_201021_add_sign_method_to_xml3176_informations.php',
            '2026_09_28_100000_them_checked_at_vao_xml3176_informations.php',
            '2026_09_29_120000_them_chain_token_va_signed_file_path_vao_xml3176_informations.php',
        ]);
        DB::table('xml3176_informations')->insert([
            'ma_lk' => 'LK1', 'macskcb' => '01929', 'chain_token' => 'MA_DUNG',
            'signed_file_path' => '01929/2026.09.29_10.00.00_LK1.xml',
        ]);
        Storage::fake('exportXml3176');
        Storage::disk('exportXml3176')->put('01929/2026.09.29_10.00.00_LK1.xml', '<DAKY/>');
        config(['organization.BHYT.submit_xml_3176_enabled' => true]);
    }

    /** Dich vu gui gia: ghi lai noi dung va ma co so nhan duoc, tra ket qua 200. */
    private function dichVuGia()
    {
        return new class {
            public $noiDung = null;
            public $maCoSo = null;
            public function submitXml($xml, $url, $loai, $maTinh, $maCoSo)
            {
                $this->noiDung = $xml;
                $this->maCoSo = $maCoSo;
                return ['maKetQua' => '200', 'maGiaoDich' => 'GD1', 'thongDiep' => 'OK'];
            }
        };
    }

    private function chay(SubmitXml3176Job $job)
    {
        $gia = $this->dichVuGia();
        $job->submitServiceGia = $gia;
        $job->handle(new Xml3176Service());

        return $gia;
    }

    private function cot($ten)
    {
        return DB::table('xml3176_informations')->where('ma_lk', 'LK1')->value($ten);
    }

    /** @test */
    public function trong_chuoi_doc_duong_dan_tep_da_ky_tu_ho_so()
    {
        $gia = $this->chay(new SubmitXml3176Job('LK1', 'MA_DUNG'));

        $this->assertSame('<DAKY/>', $gia->noiDung);
        $this->assertSame('01929', $gia->maCoSo, 'Ma co so lay tu chinh ho so');
        $this->assertNotNull($this->cot('submitted_at'));
    }

    /** @test */
    public function ma_lech_thi_khong_gui()
    {
        $job = new SubmitXml3176Job('LK1', 'MA_CU');
        $job->chained = ['x'];

        $gia = $this->chay($job);

        $this->assertNull($gia->noiDung);
        $this->assertSame([], $job->chained);
    }

    /** @test */
    public function khong_co_duong_dan_tep_thi_ghi_loi_gui()
    {
        DB::table('xml3176_informations')->update(['signed_file_path' => null]);

        $gia = $this->chay(new SubmitXml3176Job('LK1', 'MA_DUNG'));

        $this->assertNull($gia->noiDung);
        $this->assertContains('không tìm thấy tệp đã ký', $this->cot('submit_error'));
    }

    /** @test */
    public function job_cu_mang_san_duong_dan_van_gui_nhu_truoc()
    {
        // Job serialize boi ma cu (truoc chuoi): tep da qua du cua kiem theo ma cu.
        $job = new SubmitXml3176Job('LK1');
        foreach (['xmlFilePath' => '01929/2026.09.29_10.00.00_LK1.xml', 'macskcb' => '01929'] as $ten => $giaTri) {
            $p = new \ReflectionProperty(SubmitXml3176Job::class, $ten);
            $p->setAccessible(true);
            $p->setValue($job, $giaTri);
        }

        $gia = $this->chay($job);

        $this->assertSame('<DAKY/>', $gia->noiDung);
    }

    /** @test */
    public function tat_gui_thi_khong_gui()
    {
        config(['organization.BHYT.submit_xml_3176_enabled' => false]);

        $gia = $this->chay(new SubmitXml3176Job('LK1', 'MA_DUNG'));

        $this->assertNull($gia->noiDung);
    }

    /** @test */
    public function hong_het_luot_thi_ghi_loi_gui_neu_ma_con_khop()
    {
        (new SubmitXml3176Job('LK1', 'MA_DUNG'))->failed(new \RuntimeException('mang chap'));
        $this->assertSame('Gửi lỗi — mang chap', $this->cot('submit_error'));

        DB::table('xml3176_informations')->update(['submit_error' => null]);
        (new SubmitXml3176Job('LK1', 'MA_CU'))->failed(new \RuntimeException('x'));
        $this->assertNull($this->cot('submit_error'));
    }
}
```

- [ ] **Step 2: Chạy test, xác nhận đỏ**

Run: `DB_HOST=127.0.0.1 php vendor/bin/phpunit tests/Unit/Xml3176/Chuoi/SubmitXml3176JobTest.php`
Expected: đỏ — constructor hiện nhận `($ma_lk, $xmlFilePath, $macskcb)`, chưa có `submitServiceGia`.

- [ ] **Step 3: Sửa job**

Trong `app/Jobs/SubmitXml3176Job.php`:

Thêm vào khối `use` đầu tệp:

```php
use App\Jobs\Concerns\ThuocChuoiXml3176;
use App\Models\BHYT\Xml3176Information;
```

Dòng `use` trong lớp thành:

```php
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, ThuocChuoiXml3176;
```

Ngay sau khai báo `protected $macskcb;` thêm:

```php
    /**
     * Diem tiem cho test thay cho tham so handle() (khuon SubmitTt12Job): container Laravel
     * 5.5 tiem ca tham so khai "= null", nen dich vu gui KHONG duoc nhan qua handle().
     */
    public $submitServiceGia = null;
```

Thay constructor (và docblock của nó):

```php
    /**
     * @param string      $ma_lk
     * @param string|null $chainToken Ma phien cua chuoi. Duong dan tep va ma co so doc tu ho
     *                                so luc chay. Job cu (truoc chuoi) mang san $xmlFilePath
     *                                va $macskcb - giu ten thuoc tinh de chung giai nen dung.
     */
    public function __construct($ma_lk, $chainToken = null)
    {
        $this->ma_lk = $ma_lk;
        $this->chainToken = $chainToken;
    }
```

Trong `handle()`, **ngay sau** khối `if (!$submitEnabled) { ... return; }` và **trước** dòng `$xmlSubmitService = new BHYTXmlSubmitService(...)`, thêm:

```php
        if (!$this->laJobCu()) {
            if (!$this->conHieuLuc($this->ma_lk)) {
                $this->catChuoi();
                return;
            }

            $thongTin = Xml3176Information::where('ma_lk', $this->ma_lk)->first();
            $this->macskcb = $thongTin->macskcb;
            $this->xmlFilePath = $thongTin->signed_file_path;

            if (empty($this->xmlFilePath)) {
                $xml3176Service->storeXml3176Information($this->ma_lk, $this->macskcb, 'submit', 1,
                    'Gửi lỗi — không tìm thấy tệp đã ký');
                return;
            }
        }
        // Job cu (truoc chuoi) mang san xmlFilePath va macskcb, di thang xuong duoi.
```

Đổi dòng tạo dịch vụ gửi thành:

```php
        $xmlSubmitService = $this->submitServiceGia
            ?: new BHYTXmlSubmitService(new BHYTLoginService($this->macskcb));
```

Thay `failed()`:

```php
    public function failed(\Throwable $exception)
    {
        Log::error('SubmitXml3176Job failed after all retries for ma_lk: ' . $this->ma_lk, [
            'error' => $exception->getMessage(),
        ]);

        $this->ghiNeuConHieuLuc($this->ma_lk, ['submit_error' => 'Gửi lỗi — ' . $exception->getMessage()]);
    }
```

`$tries = 3` và `$timeout = 60` giữ nguyên.

- [ ] **Step 4: Chạy test mới và các test gác cũ của job gửi**

Run: `DB_HOST=127.0.0.1 php vendor/bin/phpunit tests/Unit/Xml3176/Chuoi/SubmitXml3176JobTest.php`
Expected: `OK (6 tests, ...)`

Run: `DB_HOST=127.0.0.1 php vendor/bin/phpunit tests/Unit/GuiXmlTheoCoSoTest.php`
Expected: `OK` — chuỗi `new BHYTLoginService($this->macskcb)` và `CauHinhCoSo::maTinh($this->macskcb)` vẫn còn; `handle()` không nhận `BHYTXmlSubmitService`.

Run: `DB_HOST=127.0.0.1 php vendor/bin/phpunit --filter job_khong_kiem_lai_trang_thai_ky tests/Unit/ChuaKyKhongGuiTest.php`
Expected: `OK` — job gửi không chứa `is_signed`.

- [ ] **Step 5: Commit**

```bash
git add app/Jobs/SubmitXml3176Job.php tests/Unit/Xml3176/Chuoi/SubmitXml3176JobTest.php
git commit -m "feat(xml3176): job gui mang ma phien, doc signed_file_path, ghi loi khi het luot

Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>"
```

---

### Task 8: `Xml3176ChuoiXuLy` — dựng chuỗi, và chốt thời hạn các job

**Files:**
- Create: `app/Services/Xml3176/Xml3176ChuoiXuLy.php`
- Test: `tests/Unit/Xml3176/Chuoi/Xml3176ChuoiXuLyTest.php`
- Test: `tests/Unit/Xml3176/Chuoi/ThoiHanJobChuoiTest.php`

**Interfaces:**
- Consumes: năm job với constructor ở Task 4–7; `Xml3176CheckTypes::coChecker($loai)`.
- Produces:
  - `Xml3176ChuoiXuLy::sinhMa(): string` (32 ký tự hex)
  - `Xml3176ChuoiXuLy::xepSauNap($maLk, $maPhien, array $loaiDaNap, $choPhepXuat): void`
  - `Xml3176ChuoiXuLy::xepTuXuat($maLk): string` — trả mã phiên mới

- [ ] **Step 1: Viết test đỏ cho lớp điều phối**

`tests/Unit/Xml3176/Chuoi/Xml3176ChuoiXuLyTest.php`:

```php
<?php

namespace Tests\Unit\Xml3176\Chuoi;

use App\Jobs\CheckCompleteXml3176RecordJob;
use App\Jobs\CheckXml3176TypeJob;
use App\Jobs\ExportXml3176Job;
use App\Jobs\SignXml3176Job;
use App\Jobs\SubmitXml3176Job;
use App\Services\Xml3176\Xml3176ChuoiXuLy;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Tests\Support\Xml3176RuleTestSupport;
use Tests\TestCase;

/**
 * Thu tu chuoi do FRAMEWORK bao dam, khong con dua vao viec moi hang doi chi co mot worker.
 * Laravel 5.5 khong co Queue::assertPushedWithChain(): doc $job->chained roi unserialize.
 */
class Xml3176ChuoiXuLyTest extends TestCase
{
    use Xml3176RuleTestSupport;

    protected function setUp()
    {
        parent::setUp();
        $this->bootXml3176Sqlite([
            '2026_01_09_162044_create_xml3176_informations_table.php',
            '2026_03_28_201021_add_sign_method_to_xml3176_informations.php',
            '2026_09_28_100000_them_checked_at_vao_xml3176_informations.php',
            '2026_09_29_120000_them_chain_token_va_signed_file_path_vao_xml3176_informations.php',
        ]);
        config(['xml3176.export_xml3176_enabled' => true]);
        Queue::fake();
    }

    private function thuocTinh($doiTuong, $ten)
    {
        $p = new \ReflectionProperty($doiTuong, $ten);
        $p->setAccessible(true);

        return $p->getValue($doiTuong);
    }

    /** Job dau tien da day + cac job trong chuoi cua no, theo thu tu. */
    private function chuoiDaDay($lopDau)
    {
        $dau = Queue::pushed($lopDau)->first();
        $this->assertNotNull($dau, 'Khong co job ' . $lopDau . ' nao duoc day');

        return array_merge([$dau], array_map('unserialize', $dau->chained));
    }

    /** Mo ta ngan gon moi job: lop|loai|hang doi|ma phien. */
    private function moTa(array $chuoi)
    {
        return array_map(function ($job) {
            $loai = $job instanceof CheckXml3176TypeJob ? $this->thuocTinh($job, 'xmlType') : '-';

            return class_basename($job) . '|' . $loai . '|' . $job->queue . '|' . $this->thuocTinh($job, 'chainToken');
        }, $chuoi);
    }

    /** @test */
    public function sinh_ma_32_ky_tu_hex_va_moi_lan_mot_khac()
    {
        $a = Xml3176ChuoiXuLy::sinhMa();

        $this->assertRegExp('/^[0-9a-f]{32}$/', $a);
        $this->assertNotSame($a, Xml3176ChuoiXuLy::sinhMa());
    }

    /** @test */
    public function sau_nap_du_chuoi_dung_thu_tu_dung_hang_doi_cung_mot_ma()
    {
        Xml3176ChuoiXuLy::xepSauNap('LK1', 'M1', ['XML1', 'XML2', 'XML1', 'KHONG_CO_CHECKER'], true);

        $this->assertSame([
            'CheckXml3176TypeJob|XML1|JobXml3176|M1',
            'CheckXml3176TypeJob|XML2|JobXml3176|M1',
            'CheckCompleteXml3176RecordJob|-|JobXml3176|M1',
            'ExportXml3176Job|-|JobExportXml3176|M1',
            'SignXml3176Job|-|JobSignXml3176|M1',
            'SubmitXml3176Job|-|JobSubmitXml3176|M1',
        ], $this->moTa($this->chuoiDaDay(CheckXml3176TypeJob::class)));
    }

    /** @test */
    public function khong_cho_xuat_thi_chuoi_dung_o_kiem_tong_the()
    {
        Xml3176ChuoiXuLy::xepSauNap('LK1', 'M1', ['XML1'], false);

        $this->assertSame([
            'CheckXml3176TypeJob|XML1|JobXml3176|M1',
            'CheckCompleteXml3176RecordJob|-|JobXml3176|M1',
        ], $this->moTa($this->chuoiDaDay(CheckXml3176TypeJob::class)));
    }

    /** @test */
    public function tat_tu_dong_xuat_thi_chuoi_dung_o_kiem_tong_the()
    {
        config(['xml3176.export_xml3176_enabled' => false]);

        Xml3176ChuoiXuLy::xepSauNap('LK1', 'M1', ['XML1'], true);

        $this->assertCount(2, $this->chuoiDaDay(CheckXml3176TypeJob::class));
    }

    /** @test */
    public function tat_kiem_tong_the_van_co_job_kiem_tong_the_trong_chuoi()
    {
        // Job do la moc "kiem xong"; co xml_3176_not_check chi tat phan kiem ben trong no.
        config(['organization.xml_3176_not_check' => true]);

        Xml3176ChuoiXuLy::xepSauNap('LK1', 'M1', ['XML1'], true);

        $this->assertContains('CheckCompleteXml3176RecordJob|-|JobXml3176|M1',
            $this->moTa($this->chuoiDaDay(CheckXml3176TypeJob::class)));
    }

    /** @test */
    public function khong_loai_nao_co_checker_thi_chuoi_bat_dau_tu_kiem_tong_the()
    {
        Xml3176ChuoiXuLy::xepSauNap('LK1', 'M1', ['KHONG_CO_CHECKER'], true);

        $this->assertSame('CheckCompleteXml3176RecordJob|-|JobXml3176|M1',
            $this->moTa($this->chuoiDaDay(CheckCompleteXml3176RecordJob::class))[0]);
        Queue::assertNotPushed(CheckXml3176TypeJob::class);
    }

    /** @test */
    public function tu_xuat_sinh_ma_moi_ghi_len_ho_so_va_day_xuat_ky_gui()
    {
        DB::table('xml3176_informations')->insert(['ma_lk' => 'LK1', 'macskcb' => '01929', 'chain_token' => 'MA_CU']);

        $ma = Xml3176ChuoiXuLy::xepTuXuat('LK1');

        $this->assertNotSame('MA_CU', $ma);
        $this->assertSame($ma, DB::table('xml3176_informations')->value('chain_token'),
            'Ma moi phai ghi len ho so de chuoi cu con song tu thoi - thieu thi co the gui trung');
        $this->assertSame([
            'ExportXml3176Job|-|JobExportXml3176|' . $ma,
            'SignXml3176Job|-|JobSignXml3176|' . $ma,
            'SubmitXml3176Job|-|JobSubmitXml3176|' . $ma,
        ], $this->moTa($this->chuoiDaDay(ExportXml3176Job::class)));
    }
}
```

- [ ] **Step 2: Viết test chốt thời hạn**

`tests/Unit/Xml3176/Chuoi/ThoiHanJobChuoiTest.php`:

```php
<?php

namespace Tests\Unit\Xml3176\Chuoi;

use Tests\TestCase;

/**
 * Chot an toan cho moi job trong chuoi.
 *
 * $tries: queue:work mac dinh --tries=0 la thu lai VO HAN; moi hang doi chi mot worker nen
 * mot ho so doc chan dung ca hang doi.
 *
 * $timeout < retry_after: lon hon thi hang doi giao lai job cho luot thu hai khi luot dau con
 * chay - voi job gui la HAI lan POST that len cong BHXH (xem chu thich config/queue.php).
 */
class ThoiHanJobChuoiTest extends TestCase
{
    private $cacJob = [
        \App\Jobs\CheckXml3176TypeJob::class,
        \App\Jobs\CheckCompleteXml3176RecordJob::class,
        \App\Jobs\ExportXml3176Job::class,
        \App\Jobs\SignXml3176Job::class,
        \App\Jobs\SubmitXml3176Job::class,
    ];

    private function mau($lop)
    {
        return (new \ReflectionClass($lop))->newInstanceWithoutConstructor();
    }

    /** @test */
    public function moi_job_thu_lai_huu_han()
    {
        foreach ($this->cacJob as $lop) {
            $this->assertGreaterThanOrEqual(1, (int) $this->mau($lop)->tries,
                class_basename($lop) . ' khong khai $tries - se thu lai vo han');
        }
    }

    /** @test */
    public function moi_job_het_han_truoc_retry_after()
    {
        $retryAfter = config('queue.connections.database.retry_after');
        $this->assertSame(300, $retryAfter, 'retry_after doi - doc lai chu thich config/queue.php');

        foreach ($this->cacJob as $lop) {
            $timeout = (int) $this->mau($lop)->timeout;
            $this->assertGreaterThan(0, $timeout, class_basename($lop) . ' khong khai $timeout');
            $this->assertLessThan($retryAfter, $timeout, class_basename($lop) . ' co the bi chay hai lan');
        }
    }
}
```

- [ ] **Step 3: Chạy hai test, xác nhận đỏ**

Run: `DB_HOST=127.0.0.1 php vendor/bin/phpunit tests/Unit/Xml3176/Chuoi/Xml3176ChuoiXuLyTest.php`
Expected: lỗi `Class 'App\Services\Xml3176\Xml3176ChuoiXuLy' not found`.

Run: `DB_HOST=127.0.0.1 php vendor/bin/phpunit tests/Unit/Xml3176/Chuoi/ThoiHanJobChuoiTest.php`
Expected: `OK` — năm job đã khai `$tries`/`$timeout` ở Task 4–7. Test này là **chốt chống thoái lui**, không phải test dẫn dắt; xanh ngay là đúng.

- [ ] **Step 4: Viết lớp điều phối**

`app/Services/Xml3176/Xml3176ChuoiXuLy.php`:

```php
<?php

namespace App\Services\Xml3176;

use App\Jobs\CheckCompleteXml3176RecordJob;
use App\Jobs\CheckXml3176TypeJob;
use App\Jobs\ExportXml3176Job;
use App\Jobs\SignXml3176Job;
use App\Jobs\SubmitXml3176Job;
use App\Models\BHYT\Xml3176Information;

/**
 * Dung chuoi kiem -> xuat -> ky -> gui cho MOT ho so XML3176 (khuon CtdtXepHangKyGui).
 *
 * [JobXml3176]        Kiem XML1 -> Kiem XML2 -> ... -> Kiem tong the
 * [JobExportXml3176]  -> Xuat
 * [JobSignXml3176]    -> Ky
 * [JobSubmitXml3176]  -> Gui cong
 *
 * VI SAO MOT CHUOI (withChain) CHU KHONG DISPATCH ROI RAC: truoc 29/09/2026 buoc xuat chay
 * song song voi buoc kiem va phai cho theo thoi gian; hang doi kiem ton 90 phut lam 1.934 ho
 * so sach khong len cong. Chuoi con cho hai bao dam ma dispatch roi rac khong co:
 *  - THU TU do framework giu, khong dua vao viec moi hang doi chi co mot worker;
 *  - HONG THI DONG: mot job kiem nem het luot thi phan sau khong chay - khong co chuyen
 *    "kiem do dang ma van xuat".
 *
 * Moi lan dung chuoi deu mang MA PHIEN (xem ThuocChuoiXml3176). Luc nap, ma phai duoc ghi
 * TRONG transaction nap (Xml3176Importer) - ghi sau commit thi co mot khoanh khac du lieu
 * moi da hien ra ma ma cu van con hieu luc.
 */
class Xml3176ChuoiXuLy
{
    /** Laravel 5.5 khong co Str::uuid(). */
    public static function sinhMa()
    {
        return bin2hex(random_bytes(16));
    }

    /**
     * Goi tu bo nap, SAU commit. Ma phien da duoc bo nap ghi trong transaction.
     *
     * @param string $maLk
     * @param string $maPhien
     * @param array  $loaiDaNap cac LOAIHOSO da nap (co the trung, co the co loai khong co checker)
     * @param bool   $choPhepXuat
     */
    public static function xepSauNap($maLk, $maPhien, array $loaiDaNap, $choPhepXuat)
    {
        $hangDoiKiem = config('xml3176.queue_name');
        $jobs = [];

        foreach (array_values(array_unique($loaiDaNap)) as $loai) {
            if (Xml3176CheckTypes::coChecker($loai)) {
                $jobs[] = (new CheckXml3176TypeJob($maLk, $loai, $maPhien))->onQueue($hangDoiKiem);
            }
        }

        // LUON co mat, ke ca khi xml_3176_not_check bat - job la moc "kiem xong" cua chuoi.
        $jobs[] = (new CheckCompleteXml3176RecordJob($maLk, $maPhien))->onQueue($hangDoiKiem);

        if ($choPhepXuat && config('xml3176.export_xml3176_enabled')) {
            $jobs = array_merge($jobs, self::buocTuXuat($maLk, $maPhien));
        }

        self::day($jobs);
    }

    /**
     * Goi tu lenh xml3176:chay-lai-tu-xuat: day lai xuat -> ky -> gui, khong kiem lai.
     *
     * Sinh ma MOI va ghi len ho so TRUOC khi day: chuoi cu con song cua ho so do se tu thoi.
     * Thieu buoc nay, hai chuoi cung ma co the gui trung len cong.
     *
     * @return string ma phien moi
     */
    public static function xepTuXuat($maLk)
    {
        $maPhien = self::sinhMa();

        Xml3176Information::where('ma_lk', $maLk)->update(['chain_token' => $maPhien]);

        self::day(self::buocTuXuat($maLk, $maPhien));

        return $maPhien;
    }

    private static function buocTuXuat($maLk, $maPhien)
    {
        return [
            (new ExportXml3176Job($maLk, $maPhien))->onQueue(config('xml3176.export_queue_name')),
            (new SignXml3176Job($maLk, $maPhien))->onQueue(config('xml3176.sign_queue_name')),
            (new SubmitXml3176Job($maLk, $maPhien))->onQueue(config('xml3176.submit_queue_name')),
        ];
    }

    /** Moi job da tu khai hang doi cua minh truoc khi serialize vao chuoi. */
    private static function day(array $jobs)
    {
        $dau = array_shift($jobs);

        dispatch($dau->chain($jobs));
    }
}
```

- [ ] **Step 5: Chạy test, xác nhận xanh**

Run: `DB_HOST=127.0.0.1 php vendor/bin/phpunit tests/Unit/Xml3176/Chuoi/`
Expected: `OK` cho toàn bộ thư mục `Chuoi` (Task 1–8).

- [ ] **Step 6: Commit**

```bash
git add app/Services/Xml3176/Xml3176ChuoiXuLy.php tests/Unit/Xml3176/Chuoi/Xml3176ChuoiXuLyTest.php tests/Unit/Xml3176/Chuoi/ThoiHanJobChuoiTest.php
git commit -m "feat(xml3176): Xml3176ChuoiXuLy dung chuoi kiem-xuat-ky-gui, chot thoi han cac job

Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>"
```

---

### Task 9: Nối bộ nạp vào chuỗi, gỡ đường cũ, trỏ lại các test gác

Task này bật chuỗi cho luồng thật. Trước task này, bộ nạp vẫn dùng đường cũ và mọi test đều xanh.

**Files:**
- Modify: `app/Services/Xml3176/Xml3176Importer.php`
- Modify: `app/Services/Xml3176Service.php` (xoá `processExportXml()`, `exportXml3176()`, `checkXml3176Complete()` cùng docblock của chúng; xoá `use` không còn dùng)
- Modify: `tests/Unit/ChuaKyKhongGuiTest.php`
- Modify: `tests/Unit/CoLapCongTacLuongTest.php`
- Modify: `tests/Unit/Xml3176/Xml3176ChuyenDoiJobTest.php`
- Modify: `tests/Unit/Xml3176/Xml3176ImporterTransactionTest.php`
- Modify: `tests/Unit/Xml3176/Xml3176KhongConCongBoQuaTest.php`

**Interfaces:**
- Consumes: `Xml3176ChuoiXuLy::sinhMa()`, `Xml3176ChuoiXuLy::xepSauNap($maLk, $maPhien, array $loaiDaNap, $choPhepXuat)` (Task 8).

- [ ] **Step 1: Sửa các test gác sang hình dạng mới (đỏ)**

`tests/Unit/Xml3176/Xml3176ChuyenDoiJobTest.php` — thay phương thức `importer_dispatch_job_theo_loai_sau_commit` bằng:

```php
    /** @test */
    public function importer_dung_chuoi_sau_commit()
    {
        // Job kiem tung loai nay nam TRONG chuoi do Xml3176ChuoiXuLy dung; bo nap khong con tu
        // dispatch roi rac - thu tu kiem loai truoc, kiem tong the sau do chuoi bao dam.
        $ma = $this->maKhongComment(app_path('Services/Xml3176/Xml3176Importer.php'));

        $viTriTransaction = strpos($ma, 'DB::transaction');
        $viTriXep = strpos($ma, 'Xml3176ChuoiXuLy::xepSauNap');

        $this->assertNotFalse($viTriXep, 'Importer chua dung chuoi');
        $this->assertGreaterThan($viTriTransaction, $viTriXep, 'Phai dung chuoi SAU khoi transaction');
        $this->assertNotContains('CheckXml3176TypeJob::dispatch', $ma, 'Con dispatch roi rac ngoai chuoi');
    }
```

`tests/Unit/Xml3176/Xml3176ImporterTransactionTest.php` — thay phương thức `day_job_kiem_tra_va_xuat_nam_ngoai_transaction` bằng:

```php
    /** @test */
    public function ma_phien_ghi_trong_transaction_chuoi_day_sau_commit()
    {
        // Ma phien PHAI ghi trong transaction nap: ghi sau commit thi co mot khoanh khac du
        // lieu moi da hien ra (loi cu da xoa) ma ma cu van con hieu luc - job xuat cua chuoi
        // cu chay dung luc do se xuat du lieu chua kiem.
        //
        // Chuoi PHAI day sau commit: rollback khong de lai job mo coi tro toi du lieu khong ton tai.
        $src = $this->maKhongComment(app_path('Services/Xml3176/Xml3176Importer.php'));

        $viTriTransaction = strpos($src, 'DB::transaction');
        $viTriGhiMa = strpos($src, "'chain_token' => \$maPhien");
        $viTriCatch = strpos($src, 'catch (\\Exception');
        $viTriXep = strpos($src, 'Xml3176ChuoiXuLy::xepSauNap');

        $this->assertNotFalse($viTriGhiMa, 'Bo nap khong ghi ma phien');
        $this->assertGreaterThan($viTriTransaction, $viTriGhiMa);
        $this->assertLessThan($viTriCatch, $viTriGhiMa, 'Ma phien phai ghi BEN TRONG transaction');
        $this->assertGreaterThan($viTriCatch, $viTriXep, 'Chuoi phai day SAU transaction');
    }
```

`tests/Unit/Xml3176/Xml3176KhongConCongBoQuaTest.php` — trong `ma_nguon_importer_khong_con_dieu_kien_bo_qua_ra_loi`, thay hai dòng cuối (`assertContains('CheckXml3176TypeJob::dispatch'...)` và `assertContains('checkXml3176Complete'...)`) cùng chú thích phía trên chúng bằng:

```php
        // Moi ho so van vao chuoi, va chuoi luon co job kiem tong the
        // (Xml3176ChuoiXuLyTest chot dieu do) - go cong khong duoc lam mat viec ra loi.
        $this->assertContains('Xml3176ChuoiXuLy::xepSauNap', $src);
        $this->assertNotContains('xml_3176_not_check', $src,
            'Co nay nay do CheckCompleteXml3176RecordJob xu ly; bo nap bo job kiem tong the la mat moc kiem xong');
```

`tests/Unit/ChuaKyKhongGuiTest.php` — thay phương thức `cacLuong()` và hai test đầu có chữ "hai_luong" như sau (giữ nguyên `job_khong_kiem_lai_trang_thai_ky`):

```php
    /**
     * Luong XML3176 KHONG con o day: tu 29/09/2026 no la mot chuoi withChain, SignXml3176Job
     * quyet dinh chuoi co di toi buoc gui qua QuyetDinhGui, khong con dispatch job gui. Hanh
     * vi do duoc kiem bang test chay that o Tests\Unit\Xml3176\Chuoi\SignXml3176JobTest.
     */
    public function cacLuong()
    {
        return [
            ['app/Services/Qd130XmlService.php', 'SubmitQd130XmlJob::dispatch'],
        ];
    }

    /** @test */
    public function luong_qd130_hoi_quyet_dinh_gui()
    {
        foreach ($this->cacLuong() as list($tep, $_)) {
            $ma = $this->maKhongComment(base_path($tep));
            $this->assertContains('QuyetDinhGui::nen', $ma, $tep . ': phai hoi QuyetDinhGui truoc khi gui');
        }
    }

    /** @test */
    public function luong_qd130_hoi_quyet_dinh_truoc_khi_dispatch()
    {
        foreach ($this->cacLuong() as list($tep, $dispatch)) {
            $ma = $this->maKhongComment(base_path($tep));
            $this->assertLessThan(strpos($ma, $dispatch), strpos($ma, 'QuyetDinhGui::nen'),
                $tep . ': phai quyet dinh truoc khi dispatch, khong phai sau');
        }
    }

    /** @test */
    public function luong_qd130_co_nhanh_ghi_nhan_chua_ky()
    {
        foreach ($this->cacLuong() as list($tep, $_)) {
            $ma = $this->maKhongComment(base_path($tep));
            $this->assertContains('QuyetDinhGui::CHUA_KY', $ma, $tep . ': thieu nhanh xu ly ho so chua ky');
            $this->assertContains('chưa ký số', $ma, $tep . ': phai ghi thong diep cho nguoi dung');
        }
    }

    /** @test */
    public function job_ky_xml3176_hoi_quyet_dinh_gui_va_ghi_nhan_chua_ky()
    {
        $ma = $this->maKhongComment(base_path('app/Jobs/SignXml3176Job.php'));

        $this->assertContains('QuyetDinhGui::nen', $ma);
        $this->assertContains('QuyetDinhGui::CHUA_KY', $ma);
        $this->assertContains('chưa ký số', $ma);
    }
```

(Xoá hẳn ba phương thức cũ `hai_luong_export_deu_hoi_quyet_dinh_gui`, `quyet_dinh_duoc_hoi_truoc_khi_dispatch`, `hai_luong_deu_co_nhanh_ghi_nhan_chua_ky`.)

`tests/Unit/CoLapCongTacLuongTest.php` — xoá phương thức `xml3176_hoi_co_truoc_khi_dispatch_job_gui` và đặt vào chỗ đó chú thích:

```php
    // Luong XML3176 khong con dispatch job gui: tu 29/09/2026 no la mot chuoi withChain va
    // SignXml3176Job cat chuoi khi co gui tat - tat thi KHONG co job gui nao chay. Hanh vi do
    // kiem bang test chay that: SignXml3176JobTest::tat_gui_thi_cat_chuoi_va_khong_ghi_gi.
```

Hai test `hai_job_kiem_co_truoc_khi_dung_service` (vẫn đúng: job gửi hỏi cờ trước `new BHYTXmlSubmitService`) và `luong_export_goi_ca_hai_duong_copy` (vẫn đúng: `kyVaGhiTep()` trong `Xml3176Service` gọi hai đường copy) **giữ nguyên**.

- [ ] **Step 2: Chạy các test gác, xác nhận đúng những test mong đỏ thì đỏ**

Run: `DB_HOST=127.0.0.1 php vendor/bin/phpunit tests/Unit/Xml3176/Xml3176ChuyenDoiJobTest.php tests/Unit/Xml3176/Xml3176ImporterTransactionTest.php tests/Unit/Xml3176/Xml3176KhongConCongBoQuaTest.php`

(PHPUnit 6 nhận một đường dẫn mỗi lần; chạy ba lệnh riêng nếu cần.)
Expected: đỏ ở `importer_dung_chuoi_sau_commit`, `ma_phien_ghi_trong_transaction_chuoi_day_sau_commit`, `ma_nguon_importer_khong_con_dieu_kien_bo_qua_ra_loi`. `ChuaKyKhongGuiTest` và `CoLapCongTacLuongTest` đã xanh (chúng chỉ còn quét Qd130 và `SignXml3176Job`).

- [ ] **Step 3: Sửa bộ nạp**

Trong `app/Services/Xml3176/Xml3176Importer.php`:

Khối `use` đầu tệp: xoá `use App\Jobs\CheckXml3176TypeJob;`, thêm:

```php
use App\Models\BHYT\Xml3176Information;
```

(`Xml3176ChuoiXuLy` cùng namespace `App\Services\Xml3176`, không cần `use`.)

Trong `nhapMotHoSo()`, ngay sau dòng `$processedFileTypes = [];` thêm:

```php
        // Sinh ma phien TRUOC transaction de ghi no BEN TRONG transaction (xem Xml3176ChuoiXuLy).
        $maPhien = Xml3176ChuoiXuLy::sinhMa();
```

Thêm `$maPhien` vào danh sách `use` của closure transaction:

```php
            DB::transaction(function () use (
                $danhSachFile, $danhSachLoai, $macskcb, $soluonghoso, $maPhien, &$ma_lk, &$processedFileTypes
            ) {
```

Ngay sau dòng `$this->xml3176Service->storeXml3176Information($ma_lk, $macskcb, 'import', $soluonghoso);` (vẫn trong closure) thêm:

```php
                // Ma phien ghi TRONG transaction: luc du lieu moi hien ra thi ma cu da het hieu
                // luc, job xuat cua chuoi cu con nam cho khong the xuat du lieu chua kiem.
                Xml3176Information::where('ma_lk', $ma_lk)->update(['chain_token' => $maPhien]);
```

Thay toàn bộ khối từ chú thích `// Mot job cho moi loai da xu ly, thay vi mot job moi dong.` tới hết khối `if ($choPhepXuat && config('xml3176.export_xml3176_enabled')) { ... }` bằng:

```php
        // Sau commit: rollback khong de lai job mo coi tro toi du lieu khong ton tai.
        // Mot chuoi duy nhat: kiem tung loai -> kiem tong the -> xuat -> ky -> gui. Chuoi LUON
        // co job kiem tong the (co tat kiem tong the do chinh job do xu ly); xuat hay khong
        // quyet dinh theo $choPhepXuat va xml3176.export_xml3176_enabled, nhu truoc.
        Xml3176ChuoiXuLy::xepSauNap($ma_lk, $maPhien, $processedFileTypes, $choPhepXuat);
```

Giữ nguyên khối chú thích "MOI ho so deu ra loi, khong con ngoai le nao..." phía trên.

**Lưu ý:** `Xml3176KhongConCongBoQuaTest` đọc tệp **thô** (không bỏ chú thích) và cấm chuỗi `xml_3176_not_check` trong bộ nạp. Không được viết tên khoá cấu hình đó vào bất kỳ chú thích nào của `Xml3176Importer.php`.

Kiểm: `grep -n "Job::dispatch\|xml_3176_not_check\|exportXml3176\|checkXml3176Complete" app/Services/Xml3176/Xml3176Importer.php` → không còn dòng mã nào (chỉ có thể còn trong chú thích).

- [ ] **Step 4: Gỡ ba phương thức cũ khỏi `Xml3176Service`**

Trong `app/Services/Xml3176Service.php`, xoá trọn ba phương thức cùng docblock ngay trên chúng: `checkXml3176Complete($ma_lk)`, `exportXml3176($ma_lk)`, `processExportXml($ma_lk)`.

Kiểm không còn ai gọi:

```bash
grep -rn "processExportXml\|->exportXml3176(\|checkXml3176Complete" app/ routes/
```

Expected: không có kết quả (dòng `ExportQd130XmlJob.php` gọi `processExportXml` của **`Qd130XmlService`** — nếu grep ra dòng đó, mở tệp xác nhận biến `$xmlService` trong đó là `Qd130XmlService` rồi bỏ qua).

Xoá các dòng `use` không còn dùng trong `Xml3176Service.php` — kiểm từng cái:

```bash
for c in ExportXml3176Job CheckCompleteXml3176RecordJob SubmitXml3176Job QuyetDinhGui; do echo "$c: $(grep -c "$c" app/Services/Xml3176Service.php)"; done
```

Cái nào chỉ còn đếm được 1 (chính dòng `use`) thì xoá dòng `use` đó.

- [ ] **Step 5: Chạy các test liên quan, xác nhận xanh**

```bash
DB_HOST=127.0.0.1 php vendor/bin/phpunit tests/Unit/Xml3176/Chuoi/
DB_HOST=127.0.0.1 php vendor/bin/phpunit tests/Unit/Xml3176/Xml3176ChuyenDoiJobTest.php
DB_HOST=127.0.0.1 php vendor/bin/phpunit tests/Unit/Xml3176/Xml3176ImporterTransactionTest.php
DB_HOST=127.0.0.1 php vendor/bin/phpunit tests/Unit/Xml3176/Xml3176KhongConCongBoQuaTest.php
DB_HOST=127.0.0.1 php vendor/bin/phpunit tests/Unit/ChuaKyKhongGuiTest.php
DB_HOST=127.0.0.1 php vendor/bin/phpunit tests/Unit/CoLapCongTacLuongTest.php
DB_HOST=127.0.0.1 php vendor/bin/phpunit tests/Unit/GuiXmlTheoCoSoTest.php
```

Expected: tất cả `OK`.

- [ ] **Step 6: Commit**

```bash
git add app/Services/Xml3176/Xml3176Importer.php app/Services/Xml3176Service.php tests/Unit/ChuaKyKhongGuiTest.php tests/Unit/CoLapCongTacLuongTest.php tests/Unit/Xml3176/Xml3176ChuyenDoiJobTest.php tests/Unit/Xml3176/Xml3176ImporterTransactionTest.php tests/Unit/Xml3176/Xml3176KhongConCongBoQuaTest.php
git commit -m "feat(xml3176): bo nap dung chuoi kiem-xuat-ky-gui, go duong dispatch roi rac cu

Ma phien ghi trong transaction nap, chuoi day sau commit. Go processExportXml,
exportXml3176, checkXml3176Complete. Test gac quet ma nguon tro sang hinh dang
moi; hanh vi quyet dinh gui kiem bang SignXml3176JobTest.

Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>"
```

---

### Task 10: Lệnh cứu `xml3176:chay-lai-tu-xuat`

**Files:**
- Create: `app/Console/Commands/Xml3176ChayLaiTuXuat.php` (tự đăng ký: `Kernel::commands()` gọi `$this->load(__DIR__.'/Commands')`)
- Test: `tests/Unit/Xml3176/Chuoi/Xml3176ChayLaiTuXuatTest.php`

**Interfaces:**
- Consumes: `Xml3176ChuoiXuLy::xepTuXuat($maLk): string` (Task 8).

- [ ] **Step 1: Viết test đỏ**

`tests/Unit/Xml3176/Chuoi/Xml3176ChayLaiTuXuatTest.php`:

```php
<?php

namespace Tests\Unit\Xml3176\Chuoi;

use App\Jobs\ExportXml3176Job;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Tests\Support\Xml3176RuleTestSupport;
use Tests\TestCase;

/**
 * Cuu ho so da kiem xong ma chua xuat - 5.043 ho so ngay 29/09/2026 het luot cho.
 */
class Xml3176ChayLaiTuXuatTest extends TestCase
{
    use Xml3176RuleTestSupport;

    protected function setUp()
    {
        parent::setUp();
        $this->bootXml3176Sqlite([
            '2026_01_09_161902_create_xml3176_error_results_table.php',
            '2026_01_09_162044_create_xml3176_informations_table.php',
            '2026_03_28_201021_add_sign_method_to_xml3176_informations.php',
            '2026_09_28_100000_them_checked_at_vao_xml3176_informations.php',
            '2026_09_29_120000_them_chain_token_va_signed_file_path_vao_xml3176_informations.php',
        ]);

        $mau = ['macskcb' => '01929', 'chain_token' => 'MA_CU'];
        DB::table('xml3176_informations')->insert([
            // Da kiem, chua xuat, sach -> chon
            $mau + ['ma_lk' => 'SACH', 'checked_at' => '2026-09-29 10:00:00', 'export_error' => 'Không xuất: chờ 150 giây mà bước kiểm lỗi chưa xong.'],
            // Da kiem, chua xuat, co loi nghiem trong -> chon (buoc xuat se chan lai)
            $mau + ['ma_lk' => 'LOI', 'checked_at' => '2026-09-29 10:00:00'],
            // Da xuat -> khong chon
            $mau + ['ma_lk' => 'DA_XUAT', 'checked_at' => '2026-09-29 10:00:00', 'exported_at' => '2026-09-29 10:01:00'],
            // Chua kiem xong (dang kiem do, hoac nap truoc 28/09) -> khong chon
            $mau + ['ma_lk' => 'CHUA_KIEM'],
        ]);
        DB::table('xml3176_error_results')->insert([
            'ma_lk' => 'LOI', 'xml' => 'XML1', 'stt' => 1, 'error_code' => 'X', 'description' => 'x', 'critical_error' => 1,
        ]);

        Queue::fake();
    }

    private function maDaDay()
    {
        return Queue::pushed(ExportXml3176Job::class)->map(function ($job) {
            $p = new \ReflectionProperty($job, 'ma_lk');
            $p->setAccessible(true);
            return $p->getValue($job);
        })->sort()->values()->all();
    }

    /** @test */
    public function mac_dinh_chi_dem_khong_day_gi()
    {
        Artisan::call('xml3176:chay-lai-tu-xuat');
        $ra = Artisan::output();

        Queue::assertNothingPushed();
        $this->assertContains('2 hồ sơ', $ra);
        $this->assertContains('1 sạch', $ra);
        $this->assertContains('1 có lỗi nghiêm trọng', $ra);
        $this->assertContains('--thuc-hien', $ra);
        $this->assertSame('MA_CU', DB::table('xml3176_informations')->where('ma_lk', 'SACH')->value('chain_token'),
            'Chi dem thi khong duoc doi ma phien');
    }

    /** @test */
    public function thuc_hien_day_dung_tap_da_kiem_chua_xuat_va_sinh_ma_moi()
    {
        Artisan::call('xml3176:chay-lai-tu-xuat', ['--thuc-hien' => true]);

        $this->assertSame(['LOI', 'SACH'], $this->maDaDay());
        $this->assertNotSame('MA_CU', DB::table('xml3176_informations')->where('ma_lk', 'SACH')->value('chain_token'));
        $this->assertSame('MA_CU', DB::table('xml3176_informations')->where('ma_lk', 'DA_XUAT')->value('chain_token'));
    }

    /** @test */
    public function chi_dinh_ma_lk_bo_qua_tieu_chi_mac_dinh()
    {
        // Dung khi ky lai sau su co HSM: ho so da xuat nhung chua ky.
        Artisan::call('xml3176:chay-lai-tu-xuat', ['--ma-lk' => ['DA_XUAT'], '--thuc-hien' => true]);

        $this->assertSame(['DA_XUAT'], $this->maDaDay());
    }
}
```

- [ ] **Step 2: Chạy test, xác nhận đỏ**

Run: `DB_HOST=127.0.0.1 php vendor/bin/phpunit tests/Unit/Xml3176/Chuoi/Xml3176ChayLaiTuXuatTest.php`
Expected: lỗi `The command "xml3176:chay-lai-tu-xuat" does not exist.`

- [ ] **Step 3: Viết lệnh**

`app/Console/Commands/Xml3176ChayLaiTuXuat.php`:

```php
<?php

namespace App\Console\Commands;

use App\Models\BHYT\Xml3176ErrorResult;
use App\Models\BHYT\Xml3176Information;
use App\Services\Xml3176\Xml3176ChuoiXuLy;
use Illuminate\Console\Command;

/**
 * Day lai chuoi xuat -> ky -> gui cho ho so DA KIEM XONG ma chua xuat. Khong kiem lai.
 *
 * Ra doi ngay 29/09/2026: co che cho theo thoi gian cu lam 5.043 ho so het luot cho, 1.934
 * ho so sach khong len cong. Dung lai duoc sau moi su co lam chuoi dut (HSM hong, CSDL mat).
 *
 * TIEU CHI MAC DINH: checked_at co gia tri va exported_at rong. Tu loai ho so nap truoc
 * 28/09 (cot checked_at sinh ngay do) va ho so dang kiem do (nap lai dat checked_at = null).
 *
 * KHONG chon dai tra "da xuat ma chua ky": o co so khong bat ky so, moi ho so deu thuoc
 * nhom do - chay lai hang loat se COPY TRUNG sang Truc du lieu / Dien Bien. Can ky lai thi
 * chi dinh --ma-lk.
 *
 * Moi ho so duoc day nhan MA PHIEN MOI: chuoi cu con song cua no tu thoi, khong gui trung.
 */
class Xml3176ChayLaiTuXuat extends Command
{
    protected $signature = 'xml3176:chay-lai-tu-xuat
        {--ma-lk=* : Chi dinh tung ho so, bo qua tieu chi mac dinh}
        {--thuc-hien : Day chuoi that; thieu co nay thi chi dem}';

    protected $description = 'Day lai chuoi xuat -> ky -> gui cho ho so XML3176 da kiem xong ma chua xuat';

    public function handle()
    {
        $chiDinh = (array) $this->option('ma-lk');

        $q = Xml3176Information::query();

        if (!empty($chiDinh)) {
            $q->whereIn('ma_lk', $chiDinh);
        } else {
            $q->whereNotNull('checked_at')->whereNull('exported_at');
        }

        $danhSach = $q->orderBy('id')->pluck('ma_lk')->all();

        $coLoi = 0;
        foreach (array_chunk($danhSach, 1000) as $lo) {
            $coLoi += Xml3176ErrorResult::whereIn('ma_lk', $lo)
                ->where('critical_error', true)
                ->distinct()
                ->count('ma_lk');
        }

        $tong = count($danhSach);
        $this->info($tong . ' hồ sơ: ' . ($tong - $coLoi) . ' sạch, ' . $coLoi . ' có lỗi nghiêm trọng'
            . ' (bước xuất sẽ chặn lại nếu export_xml_not_check tắt).');

        if (!$this->option('thuc-hien')) {
            $this->info('Chỉ đếm. Chạy lại với --thuc-hien để đẩy chuỗi thật.');
            return 0;
        }

        foreach ($danhSach as $maLk) {
            Xml3176ChuoiXuLy::xepTuXuat($maLk);
        }

        $this->info('Đã đẩy ' . $tong . ' chuỗi xuất → ký → gửi.');

        return 0;
    }
}
```

- [ ] **Step 4: Chạy test, xác nhận xanh**

Run: `DB_HOST=127.0.0.1 php vendor/bin/phpunit tests/Unit/Xml3176/Chuoi/Xml3176ChayLaiTuXuatTest.php`
Expected: `OK (3 tests, ...)`

- [ ] **Step 5: Commit**

```bash
git add app/Console/Commands/Xml3176ChayLaiTuXuat.php tests/Unit/Xml3176/Chuoi/Xml3176ChayLaiTuXuatTest.php
git commit -m "feat(xml3176): lenh xml3176:chay-lai-tu-xuat cuu ho so da kiem ma chua xuat

Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>"
```

---

### Task 11: Dịch vụ Windows `QLBV JobSignXml3176`

**Files:**
- Modify: `update.bat`, `install_service.bat`, `remove_service.bat`
- Test: `tests/Unit/Xml3176/Chuoi/DichVuHangDoiKyTest.php`

**Interfaces:**
- Consumes: `config('xml3176.sign_queue_name')` (Task 1).

- [ ] **Step 1: Viết test đỏ**

`tests/Unit/Xml3176/Chuoi/DichVuHangDoiKyTest.php`:

```php
<?php

namespace Tests\Unit\Xml3176\Chuoi;

use Tests\TestCase;

/**
 * Hang doi ky moi phai co dich vu Windows chay no. Thieu thi MOI chuoi dung o buoc Ky - job
 * nam cho, khong hong, khong ai biet.
 */
class DichVuHangDoiKyTest extends TestCase
{
    const DICH_VU = 'QLBV JobSignXml3176';

    private function tep($ten)
    {
        return file_get_contents(base_path($ten));
    }

    /** @test */
    public function update_bat_giu_nguyen_tung_byte_toi_het_git_pull()
    {
        // May chu tu chay update.bat moi gio. cmd doc tiep tep MOI theo vi tri byte sau
        // 'git pull' - doi mot byte phia truoc la dich chuyen moi lenh phia sau.
        $b = $this->tep('update.bat');
        $cuoi = strpos($b, "\n", strpos($b, 'git pull origin main')) + 1;

        $this->assertSame(710, $cuoi);
        $this->assertSame('e27b07e78f81d9a9da717584ef712f4db10cd90ce67be0141ee1c14e10ec7117',
            hash('sha256', substr($b, 0, $cuoi)));
    }

    /** @test */
    public function update_bat_cai_dung_stop_start_dich_vu_ky()
    {
        $b = $this->tep('update.bat');
        $hangDoi = config('xml3176.sign_queue_name');

        $this->assertContains('nssm status "' . self::DICH_VU . '"', $b, 'Khoi cai phai kiem truoc - chay lai moi gio');
        $this->assertContains('nssm install "' . self::DICH_VU . '" %PHP_PATH% "%LARAVEL_PATH%artisan queue:work --queue=' . $hangDoi . '"', $b);
        $this->assertContains('nssm set "' . self::DICH_VU . '" AppDirectory %LARAVEL_PATH%', $b);
        $this->assertContains('nssm stop "' . self::DICH_VU . '"', $b);
        $this->assertContains('nssm start "' . self::DICH_VU . '"', $b);
        $this->assertLessThan(strpos($b, 'php artisan config:clear'), strpos($b, 'nssm stop "' . self::DICH_VU . '"'));
        $this->assertGreaterThan(strpos($b, 'php artisan config:cache'), strpos($b, 'nssm start "' . self::DICH_VU . '"'));
    }

    /** @test */
    public function install_va_remove_co_dich_vu_ky()
    {
        $hangDoi = config('xml3176.sign_queue_name');

        $cai = $this->tep('install_service.bat');
        $this->assertContains('nssm install "' . self::DICH_VU . '" %PHP_PATH% "%LARAVEL_PATH%artisan queue:work --queue=' . $hangDoi . '"', $cai);
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

Run: `DB_HOST=127.0.0.1 php vendor/bin/phpunit tests/Unit/Xml3176/Chuoi/DichVuHangDoiKyTest.php`
Expected: `update_bat_giu_nguyen_tung_byte_toi_het_git_pull` và `ba_tep_van_la_crlf` **xanh**; hai test còn lại đỏ.

- [ ] **Step 3: Sửa ba tệp `.bat` bằng script giữ CRLF**

Không sửa bằng tay trong trình soạn thảo có thể đổi xuống dòng. Chạy từ thư mục gốc dự án:

```bash
python - <<'PY'
import io

def sua(tep, sau_dong_nay, them):
    b = io.open(tep, 'rb').read()
    neo = sau_dong_nay.encode('utf-8')
    assert b.count(neo) == 1, (tep, sau_dong_nay)
    i = b.index(neo) + len(neo)
    assert b[i:i+2] == b'\r\n', tep
    i += 2
    them_b = them.replace('\r\n', '\n').replace('\n', '\r\n').encode('utf-8')
    io.open(tep, 'wb').write(b[:i] + them_b + b[i:])

DV = 'QLBV JobSignXml3176'
CMD = '"%LARAVEL_PATH%artisan queue:work --queue=JobSignXml3176"'

# update.bat: khoi cai dat ngay sau khoi cai JobExportXml3176 (dong AppDirectory cua no la
# dong duy nhat trong khoi if), roi dong trong va khoi moi.
sua('update.bat',
    '    %NSSM_PATH%\\nssm set "QLBV JobExportXml3176" AppDirectory %LARAVEL_PATH%\r\n)',
    '\n'
    ':: Hang doi ky rieng cho XML3176 (29/09/2026): ky hong vi ly do CUC BO, gui hong vi MANG.\n'
    ':: Dich vu nay khong chay thi MOI ho so dung o buoc Ky - job nam cho, khong hong.\n'
    '%NSSM_PATH%\\nssm status "' + DV + '" >nul 2>&1\n'
    'if errorlevel 1 (\n'
    '    echo Installing service ' + DV + '...\n'
    '    %NSSM_PATH%\\nssm install "' + DV + '" %PHP_PATH% ' + CMD + '\n'
    '    %NSSM_PATH%\\nssm set "' + DV + '" AppDirectory %LARAVEL_PATH%\n'
    ')\n')
sua('update.bat', '%NSSM_PATH%\\nssm stop "QLBV JobExportXml3176"',
    '%NSSM_PATH%\\nssm stop "' + DV + '"\n')
sua('update.bat', '%NSSM_PATH%\\nssm start "QLBV JobExportXml3176"',
    '%NSSM_PATH%\\nssm start "' + DV + '"\n')

# install_service.bat
sua('install_service.bat', '%NSSM_PATH%\\nssm set "QLBV JobExportXml3176" AppDirectory %LARAVEL_PATH%',
    '\n'
    ':: Tao dich vu cho JobSignXml3176 - buoc ky cua chuoi kiem -> xuat -> ky -> gui XML3176\n'
    '%NSSM_PATH%\\nssm install "' + DV + '" %PHP_PATH% ' + CMD + '\n'
    '%NSSM_PATH%\\nssm set "' + DV + '" AppDirectory %LARAVEL_PATH%\n')
sua('install_service.bat', '%NSSM_PATH%\\nssm start "QLBV JobExportXml3176"',
    '%NSSM_PATH%\\nssm start "' + DV + '"\n')

# remove_service.bat
sua('remove_service.bat', '%NSSM_PATH%\\nssm remove "QLBV JobExportXml3176" confirm',
    '\n'
    ':: Xoa dich vu cho JobSignXml3176\n'
    '%NSSM_PATH%\\nssm stop "' + DV + '"\n'
    '%NSSM_PATH%\\nssm remove "' + DV + '" confirm\n')
print('ok')
PY
```

Kiểm bằng mắt kết quả:

```bash
git diff update.bat install_service.bat remove_service.bat
```

Expected: chỉ có dòng thêm (`+`), không có dòng xoá (`-`); khối mới của `update.bat` nằm **sau** dòng 191 (sau khối cài `JobExportXml3176`), không có gì thay đổi trong 24 dòng đầu.

- [ ] **Step 4: Chạy test, xác nhận xanh**

Run: `DB_HOST=127.0.0.1 php vendor/bin/phpunit tests/Unit/Xml3176/Chuoi/DichVuHangDoiKyTest.php`
Expected: `OK (4 tests, ...)`

- [ ] **Step 5: Commit**

```bash
git add update.bat install_service.bat remove_service.bat tests/Unit/Xml3176/Chuoi/DichVuHangDoiKyTest.php
git commit -m "feat(xml3176): dich vu QLBV JobSignXml3176 cho hang doi ky

Khoi cai idempotent (nssm status), them dong stop/start. update.bat giu nguyen
tung byte toi het dong git pull - co test chot SHA-256.

Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>"
```

---

### Task 12: Tài liệu — readme và quy trình vận hành

**Files:**
- Modify: `readme.md` (chèn một mục `# 29/09/2026` mới vào **đầu** mục 29/09 hiện có — mục 29/09 đã có phần quy tắc "không thẻ thì phải là mã 9")
- Modify: `docs/quy-trinh-van-hanh/_nguon/build.js` (Phụ lục B)
- Regenerate: `docs/quy-trinh-van-hanh/Quy-trinh-van-hanh-XML3176.docx`

- [ ] **Step 1: Sửa readme**

Trong `readme.md`, ngay sau dòng `# 29/09/2026` đầu tệp và dòng trống kế tiếp, chèn (giữ nguyên các gạch đầu dòng về quy tắc mã 9 phía sau):

```markdown
- **Sửa sự cố 29/09: 1.934 hồ sơ sạch không lên cổng.** Bản sửa ngày 28/09 cho bước xuất chờ bước kiểm tối đa 2,5 phút. Ngày 29/09 nạp lô 5.443 hồ sơ, hàng đợi kiểm tồn trung bình 46 phút, tối đa 90 phút — **5.043 hồ sơ hết lượt chờ và không được xuất**, trong đó 1.934 hồ sơ sạch. Không hồ sơ lỗi nào lọt, nhưng hồ sơ sạch cũng bị giữ.

- **Cách sửa: mỗi hồ sơ đi qua đúng một chuỗi, bước trước xong mới gọi bước sau.** Kiểm từng loại XML → kiểm tổng thể → xuất → **ký** → gửi cổng. Không còn chờ theo thời gian: hàng đợi kiểm tồn bao lâu thì hồ sơ được xuất ngay khi kiểm xong bấy lâu. Thứ tự do khung Laravel bảo đảm, không còn dựa vào việc mỗi hàng đợi chỉ có một tiến trình. Một bước kiểm hỏng thì hồ sơ không được xuất — không có chuyện kiểm dở dang mà vẫn lên cổng.

- **Bước ký tách ra hàng đợi riêng `JobSignXml3176`, với dịch vụ Windows mới `QLBV JobSignXml3176`.** Ký hỏng do lý do cục bộ (rút USB token, HSM treo), gửi hỏng do mạng — tách ra thì mạng chập không bắt ký lại. `update.bat` tự cài dịch vụ này. **Nếu dịch vụ không chạy, mọi hồ sơ dừng ở bước ký** — sau khi cập nhật hãy kiểm dịch vụ đang chạy.

- **Nạp lại giữa chừng không còn gây xuất nhầm.** Mỗi lần nạp (hoặc chạy lệnh cứu bên dưới) hồ sơ nhận một mã phiên mới; các bước của lần trước còn đang chờ tự nhận ra mình đã lỗi thời và thôi. Khi nạp lại hàng loạt, hàng đợi kiểm cũng nhẹ đi vì không phải kiểm mỗi hồ sơ hai lần.

- **Hồ sơ bị chặn giờ có lý do trên màn danh sách.** Cột lỗi xuất ghi "Không xuất: còn N lỗi nghiêm trọng", "Không xuất: ngày ra … sau thời điểm xuất", hoặc bước nào hỏng. Trước đây việc chặn này im lặng — không phân biệt được "bị chặn" với "chưa tới lượt".

- **Không còn thử lại vô hạn.** Mọi bước trong chuỗi có số lần thử giới hạn; hết lượt thì ghi lỗi vào hồ sơ. Trước đây các bước kiểm và xuất thử lại mãi, một hồ sơ độc có thể chặn đứng cả hàng đợi.

- **Cứu hồ sơ đang kẹt — chạy MỘT LẦN trên máy chủ sau khi cập nhật:**

```bash
php artisan xml3176:chay-lai-tu-xuat
```

  Lệnh này **chỉ đếm** hồ sơ đã kiểm xong mà chưa xuất (chia sạch / có lỗi nghiêm trọng). Đọc số rồi chạy thật:

```bash
php artisan xml3176:chay-lai-tu-xuat --thuc-hien
```

  Hồ sơ có lỗi nghiêm trọng sẽ lại bị chặn — đúng như mong đợi. Cần ký lại vài hồ sơ sau sự cố HSM thì chỉ định `--ma-lk=... --thuc-hien`. Lệnh cố ý **không** chọn đại trà "đã xuất mà chưa ký": ở cơ sở không bật ký số, việc đó sẽ copy trùng sang Trục dữ liệu / Điện Biên.

- **Giữ nguyên:** ký không được thì vẫn ghi tệp và vẫn copy sang Trục dữ liệu / Điện Biên, chỉ không gửi cổng BHXH; tên tệp và thư mục xuất không đổi.

- **Hạn chế đã biết:** bấm nút xuất tay (tải zip) đánh dấu hồ sơ là "đã xuất" dù không có gì lên cổng, nên lệnh cứu sẽ bỏ qua hồ sơ đó. Nếu job gửi của lần nạp trước đang giữa lúc gọi cổng đúng lúc nạp lại, bản cũ vẫn lên cổng rồi bản mới gửi đè — khe hở vài giây, chấp nhận.

- **Cài đặt:** có migration (hai cột mới) và một dịch vụ Windows mới — `update.bat` tự lo cả hai. Không đổi cấu hình bắt buộc.
```

- [ ] **Step 2: Sửa Phụ lục B của tài liệu vận hành**

Trong `docs/quy-trinh-van-hanh/_nguon/build.js`, trong bảng của `h1('Phụ lục B. Công tắc cấu hình (dành cho CNTT)'...)`, ngay sau dòng `['xml3176.export_xml3176_enabled', ...],` thêm:

```js
      ['xml3176.sign_queue_name (dịch vụ QLBV JobSignXml3176)', 'Hàng đợi bước ký số trong chuỗi kiểm → xuất → ký → gửi', 'Dịch vụ phải luôn chạy; dừng thì mọi hồ sơ nằm chờ ở bước ký'],
```

Ngay sau lời gọi `table([...], [3100, 3400, 2520]),` của Phụ lục B (trước `];` đóng mảng), thêm:

```js
    forIt('Hồ sơ đã kiểm xong mà chưa xuất (ví dụ sau sự cố HSM hoặc mất kết nối cơ sở dữ liệu) được đẩy lại bằng lệnh php artisan xml3176:chay-lai-tu-xuat — lệnh chỉ đếm; thêm --thuc-hien để đẩy thật, --ma-lk=<mã> để chỉ định từng hồ sơ.'),
```

- [ ] **Step 3: Dựng lại tệp docx**

Repo không có sẵn `node_modules` (đã kiểm), nên bước xoá ở cuối an toàn. Kiểm lại ngay trước khi cài:

```bash
ls -d node_modules 2>/dev/null && echo "DUNG LAI: node_modules da ton tai, khong duoc xoa" || echo "khong co node_modules, tiep tuc"
```

Nếu thông báo "DUNG LAI" thì **không** chạy lệnh `rm` ở dưới; dùng `NODE_PATH="$(npm root -g)"` theo `docs/quy-trinh-van-hanh/_nguon/README.md`.

```bash
npm install docx --no-save --no-package-lock
```

```bash
node docs/quy-trinh-van-hanh/_nguon/build.js docs/quy-trinh-van-hanh/Quy-trinh-van-hanh-XML3176.docx
```

```bash
rm -rf node_modules
```

Sơ đồ (`ve_so_do.py`) không đổi, không cần vẽ lại.

- [ ] **Step 4: Commit**

```bash
git add readme.md docs/quy-trinh-van-hanh/_nguon/build.js docs/quy-trinh-van-hanh/Quy-trinh-van-hanh-XML3176.docx
git commit -m "docs: readme va quy trinh van hanh cho chuoi kiem-xuat-ky-gui XML3176

Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>"
```

---

### Task 13: Xác minh toàn bộ

- [ ] **Step 1: Toàn bộ test mới**

Run: `DB_HOST=127.0.0.1 php vendor/bin/phpunit tests/Unit/Xml3176/Chuoi/`
Expected: `OK` — 8 tệp test của Task 1–11.

- [ ] **Step 2: Full suite hai lượt, so tên test đỏ**

```bash
DB_HOST=127.0.0.1 php vendor/bin/phpunit 2>&1 | grep -E "^[0-9]+\) " | sed 's/^[0-9]*) //' | sort > /tmp/sau.txt
git stash -u -q && git checkout -q main && DB_HOST=127.0.0.1 php vendor/bin/phpunit 2>&1 | grep -E "^[0-9]+\) " | sed 's/^[0-9]*) //' | sort > /tmp/truoc.txt; git checkout -q xml3176-chuoi-kiem-xuat-ky-gui && git stash pop -q
diff /tmp/truoc.txt /tmp/sau.txt && echo "GIONG NHAU HOAN TOAN"
```

(Mọi thay đổi đã commit, nên `git stash -u` chỉ giữ tệp chưa theo dõi nếu có; lượt "trước" chạy trên `main`.)

Expected: `GIONG NHAU HOAN TOAN` (nền hiện tại: 102 test đỏ do môi trường). Mọi dòng trong `diff` là lỗi phải sửa — kể cả một test mới của nhánh bị đỏ (nó chỉ xuất hiện ở `sau.txt`).

- [ ] **Step 3: Rà mã chết và chuỗi thừa**

```bash
grep -rn "SO_LAN_CHO_TOI_DA\|GIAY_CHO_MOI_LAN\|phaiChoKiemLoi\|processExportXml\|->exportXml3176(\|checkXml3176Complete" app/ tests/ routes/
```

Expected: không còn dòng nào thuộc XML3176 (dòng của `ExportQd130XmlJob`/`Qd130XmlService` không liên quan).

- [ ] **Step 4: Báo kết quả cho người dùng**

Không merge, không push. Báo: danh sách commit, kết quả hai lượt full suite, và trình tự trên prod ở mục 7 của spec (chờ lượt tự cập nhật → kiểm `QLBV JobSignXml3176` đang chạy → `xml3176:chay-lai-tu-xuat` đọc số → `--thuc-hien` → đo cả lô: hồ sơ sạch lên cổng, hồ sơ lỗi bị chặn, không hồ sơ lỗi nào lọt, độ trễ từng bước).
