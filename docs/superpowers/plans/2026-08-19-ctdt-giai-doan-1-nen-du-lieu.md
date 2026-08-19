# Chứng từ điện tử PL02 — Giai đoạn 1: Nền dữ liệu — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Dựng nền dữ liệu cho module Chứng từ điện tử BHXH — 12 bảng, 12 model, registry `LoaiChungTu` 9 loại, cấu hình và disk — đủ để Giai đoạn 2 viết luồng nạp mà không phải đụng lại lược đồ.

**Architecture:** Ba tầng bảng (`ctdt_ho_so` → `ctdt_chung_tu` → 9 bảng chi tiết, cộng `ctdt_loi`). Mỗi loại chứng từ là một lớp tĩnh tự mô tả cài `LoaiChungTu`, tra qua `CtdtLoaiRegistry` theo giá trị `LOAIHOSO`. Danh sách cột nằm **hai nơi** — migration và lớp loại — nên có một test toàn vẹn bắt hai nơi phải khớp.

**Tech Stack:** Laravel 5.5, PHP 7.4, PHPUnit 6, MySQL (production) / SQLite in-memory (test).

## Global Constraints

- **Không dùng `: void` trên `setUp()`** — PHPUnit 6 khai `setUp()` không có kiểu trả về; thêm vào sẽ vỡ.
- **Không dùng `RefreshDatabase`** — `.env` dự án trỏ `DB_DATABASE=qlbv` là CSDL phát triển thật, `RefreshDatabase` sẽ xóa sạch. Dùng trait SQLite in-memory theo khuôn `tests/Support/DungBangPhanQuyenSqlite.php`.
- **Không dùng `artisan migrate --path` trỏ tới một tệp** — Laravel 5.5 `Migrator::getMigrationFiles()` luôn `glob($path.'/*_*.php')`, truyền đường dẫn tệp thì lệnh báo thành công nhưng không tạo bảng nào. Phải `require_once` tệp migration rồi gọi `up()` thủ công.
- **Mọi cột dữ liệu chứng từ là `string` hoặc `text`, luôn `nullable()`** — PL02 khai mọi trường là *Chuỗi ký tự*. Không `date`, không `integer`, không `boolean` cho nội dung chứng từ.
- **Giữ nguyên tên thẻ PL02 khi đặt tên cột**, kể cả khi không đều giữa các loại. Cụ thể: CT03 dùng `benhicd10_id`/`tenbenhnicd10`; CT04/CT06/CT07/GBT dùng `benh_icd10_id`/`benh_icd10_ten`; nội trú/vô sinh/sức khỏe mẹ dùng `benh_icd10_ma`/`benh_icd10_ten`; nội trú dùng `ma_dan_toc` còn CT03 dùng `ma_dantoc`. **Không "sửa cho đều".**
- **Mã cơ sở KCB dài 5 ký tự**, `string(5)`.
- **Đặc tả nguồn:** `docs/superpowers/specs/2026-08-19-chung-tu-dien-tu-pl02-design.md`
- **Baseline test đỏ có sẵn (2026-08-19), không phải do việc này gây ra:** `Tests: 886, Errors: 4, Failures: 7` — `NhapDanhMucUniqueTest` (2), `OrderCheck\CatalogLookupTest` (2), `BHYT\Xml3176ExportLocCoSoTest` (2), `Import\GhiTheoLoTest` (5). Chạy `php vendor/bin/phpunit --testsuite Unit` trước khi bắt đầu để xác nhận đúng con số này.

---

## File Structure

**Tạo mới:**

| Tệp | Trách nhiệm |
|---|---|
| `config/ctdt.php` | Cấu hình module: 3 dịch vụ, mã kết quả, cờ bật/tắt, hàng đợi |
| `database/migrations/2026_08_19_100001_create_ctdt_ho_so_table.php` | Bảng hồ sơ (đơn vị ký/gửi) |
| `database/migrations/2026_08_19_100002_create_ctdt_chung_tu_table.php` | Bảng chứng từ (nội dung + cột rút gọn) |
| `database/migrations/2026_08_19_100003_create_ctdt_loi_table.php` | Bảng lỗi kiểm tra |
| `database/migrations/2026_08_19_100011_create_ctdt_ct03_table.php` | Giấy ra viện |
| `database/migrations/2026_08_19_100012_create_ctdt_ct04_table.php` | Tóm tắt hồ sơ bệnh án |
| `database/migrations/2026_08_19_100013_create_ctdt_ct06_table.php` | Nghỉ dưỡng thai |
| `database/migrations/2026_08_19_100014_create_ctdt_ct07_table.php` | Nghỉ việc hưởng BHXH |
| `database/migrations/2026_08_19_100015_create_ctdt_dieu_tri_noi_tru_table.php` | Điều trị nội trú |
| `database/migrations/2026_08_19_100016_create_ctdt_dieu_tri_vo_sinh_table.php` | Điều trị vô sinh |
| `database/migrations/2026_08_19_100017_create_ctdt_suc_khoe_me_table.php` | Sức khỏe mẹ |
| `database/migrations/2026_08_19_100018_create_ctdt_giay_bao_tu_table.php` | Giấy báo tử |
| `database/migrations/2026_08_19_100019_create_ctdt_giay_chung_sinh_table.php` | Giấy chứng sinh |
| `app/Models/BHYT/Ctdt/CtdtHoSo.php` … `CtdtGiayChungSinh.php` | 12 model Eloquent |
| `app/Services/Ctdt/Loai/LoaiChungTu.php` | Interface một loại chứng từ tự mô tả |
| `app/Services/Ctdt/Loai/Ct03.php` … `GiayChungSinh.php` | 9 lớp loại |
| `app/Services/Ctdt/CtdtLoaiRegistry.php` | Tra loại theo `LOAIHOSO`, đối chiếu thẻ gốc |
| `tests/Support/DungBangCtdtSqlite.php` | Trait dựng 12 bảng trong SQLite in-memory |
| `tests/Unit/Ctdt/*Test.php` | 8 tệp test |

**Sửa:**

| Tệp | Sửa gì |
|---|---|
| `config/filesystems.php` | Thêm disk `exportCtdt` sau khối `exportXml3176` (dòng ~126-129) |

---

## Task 1: Cấu hình module và disk

**Files:**
- Create: `config/ctdt.php`
- Modify: `config/filesystems.php` (thêm disk `exportCtdt` ngay sau khối `exportXml3176`, dòng ~126-129)
- Test: `tests/Unit/Ctdt/CtdtCauHinhTest.php`

**Interfaces:**
- Consumes: không có
- Produces: `config('ctdt.dich_vu')` — mảng khóa `CT2025`/`GBT`/`GCS`, mỗi phần tử có khóa `ten`, `the_goc`, `loai_hs`, `url`. `config('ctdt.ma_ket_qua')` — mảng mã → mô tả. `config('ctdt.submit_enabled')` — bool. `config('filesystems.disks.exportCtdt')`.

- [ ] **Step 1: Viết test đỏ**

Tạo `tests/Unit/Ctdt/CtdtCauHinhTest.php`:

```php
<?php

namespace Tests\Unit\Ctdt;

use Tests\TestCase;

/**
 * Canh cau hinh module chung tu dien tu: ba dich vu phai khai du va dung theo PL02.
 *
 * VI SAO CAN TEST CHO MOT TEP CAU HINH: loai_hs va the_goc la thu duy nhat phan biet
 * ba dich vu. Go nham 60 thanh 61 thi ho so gui di van duoc cong nhan (cung URL) nhung
 * vao sai loai - hong IM LANG, khong co dau hieu gi cho toi luc doi soat.
 */
class CtdtCauHinhTest extends TestCase
{
    public function cacDichVu()
    {
        return [
            ['CT2025', 'HSCHUNGTU', '39', 'https://egw.baohiemxahoi.gov.vn/api/chungtugw/GuiHoSoChungTu2025'],
            ['GBT',    'HSDLGBT',   '60', 'https://egw.baohiemxahoi.gov.vn/api/hososuckhoe/guiGiayToDienTu'],
            ['GCS',    'HSDLGCS',   '61', 'https://egw.baohiemxahoi.gov.vn/api/hososuckhoe/guiGiayToDienTu'],
        ];
    }

    /** @test */
    public function ba_dich_vu_khai_dung_the_goc_loai_hs_va_url()
    {
        $dichVu = config('ctdt.dich_vu');

        $this->assertInternalType('array', $dichVu, 'Thieu config ctdt.dich_vu');
        $this->assertCount(3, $dichVu, 'Phai co dung ba dich vu');

        foreach ($this->cacDichVu() as list($ma, $theGoc, $loaiHs, $url)) {
            $this->assertArrayHasKey($ma, $dichVu, 'Thieu dich vu ' . $ma);
            $this->assertSame($theGoc, $dichVu[$ma]['the_goc'], $ma . ': sai the goc');
            $this->assertSame($loaiHs, $dichVu[$ma]['loai_hs'], $ma . ': sai loai_hs');
            $this->assertSame($url, $dichVu[$ma]['url'], $ma . ': sai url');
            $this->assertNotEmpty($dichVu[$ma]['ten'], $ma . ': thieu ten hien thi');
        }
    }

    /** @test */
    public function loai_hs_la_chuoi_khong_phai_so()
    {
        // '39' khac 39: form_params cua Guzzle se gui so thanh "39" nen chay duoc,
        // nhung so sanh trong ma se lech im lang. Ghim kieu ngay tu cau hinh.
        foreach (config('ctdt.dich_vu') as $ma => $cauHinh) {
            $this->assertInternalType('string', $cauHinh['loai_hs'], $ma . ': loai_hs phai la chuoi');
        }
    }

    /** @test */
    public function nam_ma_ket_qua_theo_pl02()
    {
        $maKetQua = config('ctdt.ma_ket_qua');

        foreach (['200', '205', '401', '500', '1001'] as $ma) {
            $this->assertArrayHasKey($ma, $maKetQua, 'Thieu ma ket qua ' . $ma);
            $this->assertNotEmpty($maKetQua[$ma], 'Ma ket qua ' . $ma . ' khong co mo ta');
        }
    }

    /** @test */
    public function gui_len_cong_mac_dinh_tat()
    {
        // Cong that cua BHXH nhan la nhan that. Mac dinh phai TAT de mot lan chay thu
        // khong tro thanh mot lan gui that.
        $this->assertFalse((bool) config('ctdt.submit_enabled'),
            'ctdt.submit_enabled phai mac dinh false');
    }

    /** @test */
    public function co_disk_export_ctdt_rieng()
    {
        $disk = config('filesystems.disks.exportCtdt');

        $this->assertInternalType('array', $disk, 'Thieu disk exportCtdt');
        $this->assertSame('local', $disk['driver']);
        $this->assertNotEmpty($disk['root']);
        $this->assertNotSame(
            config('filesystems.disks.exportXml3176.root'),
            $disk['root'],
            'exportCtdt phai tro thu muc khac exportXml3176'
        );
    }
}
```

- [ ] **Step 2: Chạy test để xác nhận đỏ**

```bash
php vendor/bin/phpunit tests/Unit/Ctdt/CtdtCauHinhTest.php
```

Kỳ vọng: đỏ, "Thieu config ctdt.dich_vu".

- [ ] **Step 3: Tạo `config/ctdt.php`**

```php
<?php

return [
    // Hang doi rieng cho tung viec: ky so cham (USB token) khong duoc chan viec kiem loi.
    'queue_name'        => env('CTDT_QUEUE', 'JobCtdt'),
    'sign_queue_name'   => env('CTDT_SIGN_QUEUE', 'JobSignCtdt'),
    'submit_queue_name' => env('CTDT_SUBMIT_QUEUE', 'JobSubmitCtdt'),

    'import_enabled' => true,
    'sign_enabled'   => true,

    // MAC DINH TAT. Cong that cua BHXH nhan la nhan that; chi bat sau khi da chay thu
    // va doi chieu.
    'submit_enabled' => env('CTDT_SUBMIT_ENABLED', false),

    'import_path' => env('CTDT_IMPORT_PATH', 'D:\XML\ChungTuDienTu\inbox'),

    'token_url' => 'https://egw.baohiemxahoi.gov.vn/api/token/take',

    // Ba dich vu gui cua PL02. Khac nhau DUY NHAT o the goc, loai_hs va url.
    // loai_hs de kieu CHUOI: '39' khac 39 khi so sanh nghiem ngat trong ma.
    'dich_vu' => [
        'CT2025' => [
            'ten'     => 'Chứng từ TT25/2025',
            'the_goc' => 'HSCHUNGTU',
            'loai_hs' => '39',
            'url'     => 'https://egw.baohiemxahoi.gov.vn/api/chungtugw/GuiHoSoChungTu2025',
        ],
        'GBT' => [
            'ten'     => 'Giấy báo tử',
            'the_goc' => 'HSDLGBT',
            'loai_hs' => '60',
            'url'     => 'https://egw.baohiemxahoi.gov.vn/api/hososuckhoe/guiGiayToDienTu',
        ],
        'GCS' => [
            'ten'     => 'Giấy chứng sinh',
            'the_goc' => 'HSDLGCS',
            'loai_hs' => '61',
            'url'     => 'https://egw.baohiemxahoi.gov.vn/api/hososuckhoe/guiGiayToDienTu',
        ],
    ],

    // Bang ma ket qua muc 4 cua PL02, dung chung cho ca ba dich vu gui.
    'ma_ket_qua' => [
        '200'  => 'Thành công',
        '205'  => 'fileBase64Str không hợp lệ',
        '401'  => 'Lỗi xác thực tài khoản',
        '500'  => 'Lỗi server',
        '1001' => 'File size quá dài',
    ],

    // Ma ket qua lay token (muc I) - khac bang tren, dung rieng cho duong dang nhap.
    'ma_ket_qua_token' => [
        '200' => 'Lấy token thành công',
        '401' => 'Tài khoản không tồn tại',
        '402' => 'Mã cơ sở KCB không đúng',
        '403' => 'Tài khoản đã bị khóa',
        '500' => 'Lỗi hệ thống',
    ],
];
```

- [ ] **Step 4: Thêm disk vào `config/filesystems.php`**

Chèn ngay sau khối `'exportXml3176' => [...]` (dòng ~126-129):

```php
        'exportCtdt' => [
            'driver' => 'local',
            'root' => 'D:\XML\ChungTuDienTu',
        ],
```

- [ ] **Step 5: Chạy test để xác nhận xanh**

```bash
php vendor/bin/phpunit tests/Unit/Ctdt/CtdtCauHinhTest.php
```

Kỳ vọng: `OK (5 tests)`.

- [ ] **Step 6: Commit**

```bash
git add config/ctdt.php config/filesystems.php tests/Unit/Ctdt/CtdtCauHinhTest.php
git commit -m "feat(ctdt): cau hinh module chung tu dien tu va disk exportCtdt"
```

---

## Task 2: Ba bảng khung và trait test SQLite

**Files:**
- Create: `database/migrations/2026_08_19_100001_create_ctdt_ho_so_table.php`
- Create: `database/migrations/2026_08_19_100002_create_ctdt_chung_tu_table.php`
- Create: `database/migrations/2026_08_19_100003_create_ctdt_loi_table.php`
- Create: `app/Models/BHYT/Ctdt/CtdtHoSo.php`
- Create: `app/Models/BHYT/Ctdt/CtdtChungTu.php`
- Create: `app/Models/BHYT/Ctdt/CtdtLoi.php`
- Create: `tests/Support/DungBangCtdtSqlite.php`
- Test: `tests/Unit/Ctdt/CtdtBangKhungTest.php`

**Interfaces:**
- Consumes: không có
- Produces:
  - Trait `Tests\Support\DungBangCtdtSqlite` với hai phương thức: `chuanBiBangCtdt()` (dựng cả 12 bảng, dùng được từ Task 3 trở đi) và `cacMigrationCtdt(): array` (danh sách `[tên tệp => tên lớp]`).
  - Model `App\Models\BHYT\Ctdt\CtdtHoSo` — quan hệ `chungTu(): HasMany`, `loi(): HasMany`.
  - Model `App\Models\BHYT\Ctdt\CtdtChungTu` — quan hệ `hoSo(): BelongsTo`, `loi(): HasMany`.
  - Model `App\Models\BHYT\Ctdt\CtdtLoi`.

- [ ] **Step 1: Viết trait dựng bảng**

Tạo `tests/Support/DungBangCtdtSqlite.php`:

```php
<?php

namespace Tests\Support;

use Illuminate\Support\Facades\DB;

/**
 * Dung 12 bang cua module chung tu dien tu trong SQLite bo nho.
 *
 * VI SAO KHONG DUNG RefreshDatabase: .env cua du an tro DB_DATABASE=qlbv - co so du
 * lieu phat trien that. RefreshDatabase se xoa sach no.
 *
 * VI SAO KHONG DUNG artisan migrate --path: tren Laravel 5.5,
 * Migrator::getMigrationFiles() luon glob($path.'/*_*.php'), tuc --path bat buoc la
 * MOT THU MUC. Truyen duong dan tep khien lenh bao "thanh cong" (exit 0) nhung khong
 * tao bang nao. Thu muc that database/migrations lai chua nhieu migration phu thuoc
 * cu phap MySQL va ket noi Oracle, khong tro --path vao do duoc. Nen nap thang tung
 * tep roi goi up().
 *
 * VI SAO GHI DE KET NOI TEN 'mysql' chu khong doi database.default: giu dung khuon
 * cua DungBangPhanQuyenSqlite, va phong khi model ve sau ghim cung $connection.
 */
trait DungBangCtdtSqlite
{
    /**
     * Thu tu QUAN TRONG: bang khung truoc, bang chi tiet sau - khoa ngoai cua bang
     * chi tiet tro toi ctdt_chung_tu.
     *
     * @return array [ten tep migration => ten lop]
     */
    protected function cacMigrationCtdt()
    {
        return [
            '2026_08_19_100001_create_ctdt_ho_so_table'              => 'CreateCtdtHoSoTable',
            '2026_08_19_100002_create_ctdt_chung_tu_table'           => 'CreateCtdtChungTuTable',
            '2026_08_19_100003_create_ctdt_loi_table'                => 'CreateCtdtLoiTable',
            '2026_08_19_100011_create_ctdt_ct03_table'               => 'CreateCtdtCt03Table',
            '2026_08_19_100012_create_ctdt_ct04_table'               => 'CreateCtdtCt04Table',
            '2026_08_19_100013_create_ctdt_ct06_table'               => 'CreateCtdtCt06Table',
            '2026_08_19_100014_create_ctdt_ct07_table'               => 'CreateCtdtCt07Table',
            '2026_08_19_100015_create_ctdt_dieu_tri_noi_tru_table'   => 'CreateCtdtDieuTriNoiTruTable',
            '2026_08_19_100016_create_ctdt_dieu_tri_vo_sinh_table'   => 'CreateCtdtDieuTriVoSinhTable',
            '2026_08_19_100017_create_ctdt_suc_khoe_me_table'        => 'CreateCtdtSucKhoeMeTable',
            '2026_08_19_100018_create_ctdt_giay_bao_tu_table'        => 'CreateCtdtGiayBaoTuTable',
            '2026_08_19_100019_create_ctdt_giay_chung_sinh_table'    => 'CreateCtdtGiayChungSinhTable',
        ];
    }

    /**
     * Dung cac bang da co migration. Bo qua tep chua ton tai de trait dung duoc ngay
     * tu Task 2, khi 9 migration chi tiet chua duoc viet.
     */
    protected function chuanBiBangCtdt()
    {
        config([
            'database.default' => 'mysql',
            'database.connections.mysql' => [
                'driver'   => 'sqlite',
                'database' => ':memory:',
                'prefix'   => '',
            ],
        ]);

        DB::purge('mysql');

        foreach ($this->cacMigrationCtdt() as $tep => $lop) {
            $duongDan = base_path('database/migrations/' . $tep . '.php');

            if (!file_exists($duongDan)) {
                continue;
            }

            require_once $duongDan;

            (new $lop())->up();
        }
    }
}
```

- [ ] **Step 2: Viết test đỏ**

Tạo `tests/Unit/Ctdt/CtdtBangKhungTest.php`:

```php
<?php

namespace Tests\Unit\Ctdt;

use Tests\TestCase;
use Tests\Support\DungBangCtdtSqlite;
use Illuminate\Support\Facades\Schema;
use App\Models\BHYT\Ctdt\CtdtHoSo;
use App\Models\BHYT\Ctdt\CtdtChungTu;
use App\Models\BHYT\Ctdt\CtdtLoi;

/**
 * Canh ba bang khung: ho so (don vi ky/gui), chung tu (noi dung), loi (ket qua kiem).
 */
class CtdtBangKhungTest extends TestCase
{
    use DungBangCtdtSqlite;

    protected function setUp()
    {
        parent::setUp();
        $this->chuanBiBangCtdt();
    }

    /** @test */
    public function ba_bang_khung_duoc_tao()
    {
        foreach (['ctdt_ho_so', 'ctdt_chung_tu', 'ctdt_loi'] as $bang) {
            $this->assertTrue(Schema::hasTable($bang), 'Thieu bang ' . $bang);
        }
    }

    /** @test */
    public function ctdt_ho_so_co_du_cot_trang_thai()
    {
        $cot = [
            'ma_ho_so', 'id_goi_xml', 'dich_vu', 'loai_hs', 'macskcb', 'ngay_lap',
            'so_luong_ho_so', 'so_chung_tu', 'duong_dan_goc', 'duong_dan_da_ky',
            'imported_at', 'imported_by', 'import_error',
            'checked_at', 'so_loi',
            'is_signed', 'sign_method', 'signed_at', 'signed_error',
            'submitted_at', 'submitted_by', 'submit_error', 'submitted_message',
            'ma_gd', 'ma_ket_qua', 'thoi_gian_tiep_nhan', 'lich_su_gui',
        ];

        foreach ($cot as $ten) {
            $this->assertTrue(Schema::hasColumn('ctdt_ho_so', $ten), 'ctdt_ho_so thieu cot ' . $ten);
        }
    }

    /** @test */
    public function ma_ho_so_la_duy_nhat()
    {
        // Ghi de ban cu phai khoa duoc vao mot cot. Khong unique thi nap lai se de
        // ra hai ban ghi ma khong bao gi ca.
        CtdtHoSo::create(['ma_ho_so' => 'HS001', 'dich_vu' => 'CT2025', 'loai_hs' => '39', 'macskcb' => '01929']);

        $this->expectException(\Illuminate\Database\QueryException::class);

        CtdtHoSo::create(['ma_ho_so' => 'HS001', 'dich_vu' => 'GBT', 'loai_hs' => '60', 'macskcb' => '01929']);
    }

    /** @test */
    public function ho_so_co_nhieu_chung_tu()
    {
        $hoSo = CtdtHoSo::create(['ma_ho_so' => 'HS002', 'dich_vu' => 'CT2025', 'loai_hs' => '39', 'macskcb' => '01929']);

        CtdtChungTu::create(['ho_so_id' => $hoSo->id, 'loai_ho_so' => 'CT03', 'ma_chung_tu' => 'YT001', 'noi_dung_goc' => '<CT03/>']);
        CtdtChungTu::create(['ho_so_id' => $hoSo->id, 'loai_ho_so' => 'CT04', 'ma_chung_tu' => 'YT001', 'noi_dung_goc' => '<CT04/>']);

        $this->assertCount(2, $hoSo->fresh()->chungTu);
        $this->assertSame('HS002', CtdtChungTu::first()->hoSo->ma_ho_so);
    }

    /** @test */
    public function chung_tu_giu_cot_rut_gon_de_loc_danh_sach()
    {
        // Cot rut gon la trung lap CO CHU DICH: khong co chung thi man danh sach phai
        // UNION 9 bang chi tiet vi ten truong khac nhau tung loai.
        foreach (['ma_the', 'ho_ten', 'ngay_sinh', 'ngay_vao', 'ngay_ra'] as $ten) {
            $this->assertTrue(Schema::hasColumn('ctdt_chung_tu', $ten), 'ctdt_chung_tu thieu cot rut gon ' . $ten);
        }
    }

    /** @test */
    public function loi_gan_duoc_vao_ho_so_va_chung_tu()
    {
        $hoSo = CtdtHoSo::create(['ma_ho_so' => 'HS003', 'dich_vu' => 'CT2025', 'loai_hs' => '39', 'macskcb' => '01929']);
        $chungTu = CtdtChungTu::create(['ho_so_id' => $hoSo->id, 'loai_ho_so' => 'CT03', 'noi_dung_goc' => '<CT03/>']);

        CtdtLoi::create([
            'ho_so_id'    => $hoSo->id,
            'chung_tu_id' => $chungTu->id,
            'ma_loi'      => 'CTDT002',
            'ten_truong'  => 'NGAY_VAO',
            'mo_ta'       => 'Sai dinh dang ngay',
            'muc_do'      => 'chan',
        ]);

        $this->assertCount(1, $hoSo->fresh()->loi);
        $this->assertSame('chan', CtdtLoi::first()->muc_do);
    }
}
```

- [ ] **Step 3: Chạy test để xác nhận đỏ**

```bash
php vendor/bin/phpunit tests/Unit/Ctdt/CtdtBangKhungTest.php
```

Kỳ vọng: đỏ — `Class 'App\Models\BHYT\Ctdt\CtdtHoSo' not found`.

- [ ] **Step 4: Viết migration `ctdt_ho_so`**

Tạo `database/migrations/2026_08_19_100001_create_ctdt_ho_so_table.php`:

```php
<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Mot HOSO = mot ban ghi = mot don vi ky / gui / ghi de.
 *
 * Mot tep HSCHUNGTU co the chua nhieu HOSO. Neu lay ca TEP lam don vi giao dich thi
 * khong ghi de, khong gui lai, khong tra trang thai o muc tung ho so duoc.
 */
class CreateCtdtHoSoTable extends Migration
{
    public function up()
    {
        Schema::create('ctdt_ho_so', function (Blueprint $table) {
            $table->increments('id');

            // Khoa NGHIEP VU, khong phai danh tinh tep: ban sua gui lai mang Id GUID moi,
            // khoa theo GUID thi khong bao gio dung ban cu.
            $table->string('ma_ho_so', 100)->unique();
            $table->string('id_goi_xml', 64)->nullable();

            $table->string('dich_vu', 10)->index();      // CT2025 | GBT | GCS
            $table->string('loai_hs', 2);                // 39 | 60 | 61
            $table->string('macskcb', 5)->index();
            $table->string('ngay_lap', 8)->nullable();
            $table->integer('so_luong_ho_so')->nullable();
            $table->integer('so_chung_tu')->default(0);

            $table->string('duong_dan_goc')->nullable();
            $table->string('duong_dan_da_ky')->nullable();

            $table->timestamp('imported_at')->nullable();
            $table->string('imported_by')->nullable()->index();
            $table->text('import_error')->nullable();

            $table->timestamp('checked_at')->nullable();
            $table->integer('so_loi')->default(0)->index();

            $table->boolean('is_signed')->default(false);
            $table->string('sign_method')->nullable();
            $table->timestamp('signed_at')->nullable();
            $table->text('signed_error')->nullable();

            $table->timestamp('submitted_at')->nullable()->index();
            $table->string('submitted_by')->nullable()->index();
            $table->string('submit_error')->nullable()->index();
            $table->text('submitted_message')->nullable();

            // Cot tra cuu quan trong nhat khi doi soat voi BHXH.
            $table->string('ma_gd', 50)->nullable()->index();
            $table->string('ma_ket_qua', 10)->nullable()->index();
            $table->string('thoi_gian_tiep_nhan', 14)->nullable();

            // Noi them mot dong moi lan gui: giu dau vet doi soat sau khi ghi de.
            $table->text('lich_su_gui')->nullable();

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('ctdt_ho_so');
    }
}
```

- [ ] **Step 5: Viết migration `ctdt_chung_tu`**

Tạo `database/migrations/2026_08_19_100002_create_ctdt_chung_tu_table.php`:

```php
<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCtdtChungTuTable extends Migration
{
    public function up()
    {
        Schema::create('ctdt_chung_tu', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('ho_so_id')->index();

            $table->string('loai_ho_so', 30)->index();       // gia tri LOAIHOSO nguyen van
            $table->string('ma_chung_tu', 100)->nullable()->index();

            // COT RUT GON - trung lap co chu dich. Chin loai chung tu dat ten truong khac
            // nhau (NGAY_SINH nguoi benh, NGAYSINH_NND nguoi me, NGAY_SINH_CON cua con),
            // khong rut gon thi moi truy van danh sach thanh UNION 9 nhanh.
            $table->string('ma_the', 20)->nullable()->index();
            $table->string('ho_ten')->nullable()->index();
            $table->string('ngay_sinh', 14)->nullable();
            $table->string('ngay_vao', 14)->nullable();
            $table->string('ngay_ra', 14)->nullable();

            $table->longText('noi_dung_goc')->nullable();

            $table->timestamps();

            $table->foreign('ho_so_id')->references('id')->on('ctdt_ho_so')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('ctdt_chung_tu');
    }
}
```

- [ ] **Step 6: Viết migration `ctdt_loi`**

Tạo `database/migrations/2026_08_19_100003_create_ctdt_loi_table.php`:

```php
<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCtdtLoiTable extends Migration
{
    public function up()
    {
        Schema::create('ctdt_loi', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('ho_so_id')->index();
            $table->unsignedInteger('chung_tu_id')->nullable()->index();

            $table->string('ma_loi', 20)->index();
            $table->string('ten_truong', 50)->nullable();
            $table->string('mo_ta', 255);
            $table->string('muc_do', 10)->index();   // chan | canh_bao

            $table->timestamps();

            $table->foreign('ho_so_id')->references('id')->on('ctdt_ho_so')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('ctdt_loi');
    }
}
```

- [ ] **Step 7: Viết ba model**

Tạo `app/Models/BHYT/Ctdt/CtdtHoSo.php`:

```php
<?php

namespace App\Models\BHYT\Ctdt;

use Illuminate\Database\Eloquent\Model;

class CtdtHoSo extends Model
{
    protected $table = 'ctdt_ho_so';

    protected $fillable = [
        'ma_ho_so', 'id_goi_xml', 'dich_vu', 'loai_hs', 'macskcb', 'ngay_lap',
        'so_luong_ho_so', 'so_chung_tu', 'duong_dan_goc', 'duong_dan_da_ky',
        'imported_at', 'imported_by', 'import_error',
        'checked_at', 'so_loi',
        'is_signed', 'sign_method', 'signed_at', 'signed_error',
        'submitted_at', 'submitted_by', 'submit_error', 'submitted_message',
        'ma_gd', 'ma_ket_qua', 'thoi_gian_tiep_nhan', 'lich_su_gui',
    ];

    public function chungTu()
    {
        return $this->hasMany(CtdtChungTu::class, 'ho_so_id', 'id');
    }

    public function loi()
    {
        return $this->hasMany(CtdtLoi::class, 'ho_so_id', 'id');
    }
}
```

Tạo `app/Models/BHYT/Ctdt/CtdtChungTu.php`:

```php
<?php

namespace App\Models\BHYT\Ctdt;

use Illuminate\Database\Eloquent\Model;

class CtdtChungTu extends Model
{
    protected $table = 'ctdt_chung_tu';

    protected $fillable = [
        'ho_so_id', 'loai_ho_so', 'ma_chung_tu',
        'ma_the', 'ho_ten', 'ngay_sinh', 'ngay_vao', 'ngay_ra',
        'noi_dung_goc',
    ];

    public function hoSo()
    {
        return $this->belongsTo(CtdtHoSo::class, 'ho_so_id', 'id');
    }

    public function loi()
    {
        return $this->hasMany(CtdtLoi::class, 'chung_tu_id', 'id');
    }
}
```

Tạo `app/Models/BHYT/Ctdt/CtdtLoi.php`:

```php
<?php

namespace App\Models\BHYT\Ctdt;

use Illuminate\Database\Eloquent\Model;

class CtdtLoi extends Model
{
    protected $table = 'ctdt_loi';

    protected $fillable = [
        'ho_so_id', 'chung_tu_id', 'ma_loi', 'ten_truong', 'mo_ta', 'muc_do',
    ];

    public function hoSo()
    {
        return $this->belongsTo(CtdtHoSo::class, 'ho_so_id', 'id');
    }

    public function chungTu()
    {
        return $this->belongsTo(CtdtChungTu::class, 'chung_tu_id', 'id');
    }
}
```

- [ ] **Step 8: Chạy test để xác nhận xanh**

```bash
php vendor/bin/phpunit tests/Unit/Ctdt/CtdtBangKhungTest.php
```

Kỳ vọng: `OK (6 tests)`.

- [ ] **Step 9: Commit**

```bash
git add database/migrations/2026_08_19_1000*.php app/Models/BHYT/Ctdt tests/Support/DungBangCtdtSqlite.php tests/Unit/Ctdt/CtdtBangKhungTest.php
git commit -m "feat(ctdt): ba bang khung ho so - chung tu - loi"
```

---

## Task 3: Chín bảng chi tiết và model

**Files:**
- Create: 9 migration `2026_08_19_100011` … `2026_08_19_100019` (tên đầy đủ ở mục File Structure)
- Create: 9 model trong `app/Models/BHYT/Ctdt/`
- Test: `tests/Unit/Ctdt/CtdtBangChiTietTest.php`

**Interfaces:**
- Consumes: `Tests\Support\DungBangCtdtSqlite::chuanBiBangCtdt()` (Task 2); bảng `ctdt_chung_tu` (Task 2)
- Produces: 9 model `CtdtCt03`, `CtdtCt04`, `CtdtCt06`, `CtdtCt07`, `CtdtDieuTriNoiTru`, `CtdtDieuTriVoSinh`, `CtdtSucKhoeMe`, `CtdtGiayBaoTu`, `CtdtGiayChungSinh` — mỗi model có `$fillable` chứa `chung_tu_id` + toàn bộ cột dữ liệu, và quan hệ `chungTu(): BelongsTo`.

- [ ] **Step 1: Viết test đỏ**

Tạo `tests/Unit/Ctdt/CtdtBangChiTietTest.php`:

```php
<?php

namespace Tests\Unit\Ctdt;

use Tests\TestCase;
use Tests\Support\DungBangCtdtSqlite;
use Illuminate\Support\Facades\Schema;
use App\Models\BHYT\Ctdt\CtdtHoSo;
use App\Models\BHYT\Ctdt\CtdtChungTu;
use App\Models\BHYT\Ctdt\CtdtCt03;

/**
 * Canh chin bang chi tiet: du cot theo PL02, va GIU NGUYEN ten the ke ca khi khong deu.
 */
class CtdtBangChiTietTest extends TestCase
{
    use DungBangCtdtSqlite;

    protected function setUp()
    {
        parent::setUp();
        $this->chuanBiBangCtdt();
    }

    public function soCotMongDoi()
    {
        return [
            ['ctdt_ct03', 33],
            ['ctdt_ct04', 45],
            ['ctdt_ct06', 24],
            ['ctdt_ct07', 27],
            ['ctdt_dieu_tri_noi_tru', 36],
            ['ctdt_dieu_tri_vo_sinh', 29],
            ['ctdt_suc_khoe_me', 29],
            ['ctdt_giay_bao_tu', 38],
            ['ctdt_giay_chung_sinh', 69],
        ];
    }

    /** @test */
    public function chin_bang_chi_tiet_duoc_tao_du_so_cot()
    {
        foreach ($this->soCotMongDoi() as list($bang, $soCot)) {
            $this->assertTrue(Schema::hasTable($bang), 'Thieu bang ' . $bang);

            // id + chung_tu_id + created_at + updated_at = 4 cot khung
            $thucTe = count(Schema::getColumnListing($bang)) - 4;

            $this->assertSame($soCot, $thucTe,
                $bang . ': mong doi ' . $soCot . ' cot du lieu, thuc te ' . $thucTe);
        }
    }

    /** @test */
    public function ten_the_icd_giu_nguyen_khong_sua_cho_deu()
    {
        // Ba kieu dat ten ICD khac nhau trong cung mot dac ta. Sua cho deu la lam sai
        // anh xa the -> cot, va loi chi lo ra luc gui that bai.
        $this->assertTrue(Schema::hasColumn('ctdt_ct03', 'benhicd10_id'), 'CT03 phai la benhicd10_id');
        $this->assertTrue(Schema::hasColumn('ctdt_ct03', 'tenbenhnicd10'), 'CT03 phai la tenbenhnicd10');

        $this->assertTrue(Schema::hasColumn('ctdt_ct04', 'benh_icd10_id'), 'CT04 phai la benh_icd10_id');
        $this->assertTrue(Schema::hasColumn('ctdt_giay_bao_tu', 'benh_icd10_id'), 'GBT phai la benh_icd10_id');

        $this->assertTrue(Schema::hasColumn('ctdt_dieu_tri_noi_tru', 'benh_icd10_ma'), 'Noi tru phai la benh_icd10_ma');
        $this->assertTrue(Schema::hasColumn('ctdt_dieu_tri_vo_sinh', 'benh_icd10_ma'), 'Vo sinh phai la benh_icd10_ma');
        $this->assertTrue(Schema::hasColumn('ctdt_suc_khoe_me', 'benh_icd10_ma'), 'Suc khoe me phai la benh_icd10_ma');
    }

    /** @test */
    public function ten_the_dan_toc_giu_nguyen_khac_biet()
    {
        $this->assertTrue(Schema::hasColumn('ctdt_ct03', 'ma_dantoc'), 'CT03 phai la ma_dantoc');
        $this->assertTrue(Schema::hasColumn('ctdt_dieu_tri_noi_tru', 'ma_dan_toc'), 'Noi tru phai la ma_dan_toc');
    }

    /** @test */
    public function ct03_co_cot_ghi_chu_du_xml_mau_khong_co()
    {
        // Muc 9.2 cua PL02 liet ke GHI_CHU nhung XML mau muc 9.1 khong co the nay.
        // Thua mot cot rong thi vo hai, thieu mot cot thi mat du lieu.
        $this->assertTrue(Schema::hasColumn('ctdt_ct03', 'ghi_chu'));
    }

    /** @test */
    public function giay_chung_sinh_du_ba_nhom_nguoi()
    {
        // NND = nguoi de, MTH = me thay the (mang thai ho), CHA_NND = cha cua nguoi de.
        // Ba nhom nay dung hau to khac nhau cho cung mot khai niem - de nham lan nhat.
        foreach (['hoten_nnd', 'hoten_mth', 'ho_ten_cha_mth', 'so_cccd_cha_nnd'] as $cot) {
            $this->assertTrue(Schema::hasColumn('ctdt_giay_chung_sinh', $cot),
                'ctdt_giay_chung_sinh thieu cot ' . $cot);
        }
    }

    /** @test */
    public function chi_tiet_gan_duoc_vao_chung_tu()
    {
        $hoSo = CtdtHoSo::create(['ma_ho_so' => 'HS100', 'dich_vu' => 'CT2025', 'loai_hs' => '39', 'macskcb' => '01929']);
        $chungTu = CtdtChungTu::create(['ho_so_id' => $hoSo->id, 'loai_ho_so' => 'CT03', 'noi_dung_goc' => '<CT03/>']);

        CtdtCt03::create([
            'chung_tu_id' => $chungTu->id,
            'ma_yte'      => 'YT001',
            'ho_ten'      => 'Nguyen Van Test',
            'ngay_vao'    => '201912121200',
        ]);

        $this->assertSame('YT001', CtdtCt03::first()->ma_yte);
        $this->assertSame('CT03', CtdtCt03::first()->chungTu->loai_ho_so);
    }

    /** @test */
    public function ngay_gio_giu_nguyen_khong_bi_ep_kieu()
    {
        // PL02 khai moi truong la Chuoi ky tu. Ep sang date se lam mat phan phut cua
        // 201912121200 va mat so 0 dau cua '01'.
        $hoSo = CtdtHoSo::create(['ma_ho_so' => 'HS101', 'dich_vu' => 'CT2025', 'loai_hs' => '39', 'macskcb' => '01929']);
        $chungTu = CtdtChungTu::create(['ho_so_id' => $hoSo->id, 'loai_ho_so' => 'CT03', 'noi_dung_goc' => '<CT03/>']);

        CtdtCt03::create([
            'chung_tu_id' => $chungTu->id,
            'ngay_vao'    => '201912121200',
            'ma_dantoc'   => '01',
        ]);

        $ban = CtdtCt03::first();

        $this->assertSame('201912121200', $ban->ngay_vao);
        $this->assertSame('01', $ban->ma_dantoc);
    }
}
```

- [ ] **Step 2: Chạy test để xác nhận đỏ**

```bash
php vendor/bin/phpunit tests/Unit/Ctdt/CtdtBangChiTietTest.php
```

Kỳ vọng: đỏ — "Thieu bang ctdt_ct03".

- [ ] **Step 3: Viết migration `ctdt_ct03`**

Tạo `database/migrations/2026_08_19_100011_create_ctdt_ct03_table.php`:

```php
<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * CT03 - Giay ra vien (Mau so 02 - TT25).
 *
 * GHI_CHU co trong bang mo ta muc 9.2 nhung khong co trong XML mau muc 9.1 - van tao cot.
 * BENHICD10_ID / TENBENHNICD10 dat ten KHAC cac loai khac (benh_icd10_id / benh_icd10_ten).
 * Giu nguyen theo dac ta, khong sua cho deu.
 */
class CreateCtdtCt03Table extends Migration
{
    public function up()
    {
        Schema::create('ctdt_ct03', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('chung_tu_id')->unique();

            $chuoi = [
                'so_luu_tru', 'ma_yte', 'ma_khoa', 'ma_bhxh', 'ma_the', 'ho_ten',
                'ngay_sinh', 'gioi_tinh', 'ma_dantoc', 'nghe_nghiep',
                'ngay_vao', 'ngay_ra', 'dinh_chi_thai_nghen', 'tuoi_thai',
                'thu_truong_dvi', 'ma_cchn_truongkhoa', 'ten_truongkhoa',
                'ngay_chung_tu', 'tekt', 'ho_ten_cha', 'ho_ten_me',
                'ngoaitru_tungay', 'ngoaitru_denngay',
                'loai_giayto', 'so_cccd', 'ngaycap_cccd', 'noicap_cccd',
                'benhicd10_id',
            ];

            $vanBan = ['dia_chi', 'chan_doan', 'pp_dieutri', 'ghi_chu', 'tenbenhnicd10'];

            foreach ($chuoi as $cot) {
                $table->string($cot)->nullable();
            }

            foreach ($vanBan as $cot) {
                $table->text($cot)->nullable();
            }

            $table->timestamps();

            $table->foreign('chung_tu_id')->references('id')->on('ctdt_chung_tu')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('ctdt_ct03');
    }
}
```

- [ ] **Step 4: Viết migration `ctdt_ct04`**

Tạo `database/migrations/2026_08_19_100012_create_ctdt_ct04_table.php`:

```php
<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/** CT04 - Ban tom tat ho so benh an (Mau so 03 - TT25). KHONG co MA_YTE. */
class CreateCtdtCt04Table extends Migration
{
    public function up()
    {
        Schema::create('ctdt_ct04', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('chung_tu_id')->unique();

            $chuoi = [
                'ma_ct', 'so_seri', 'ma_bhxh', 'ma_the', 'ho_ten', 'ngay_sinh',
                'gioi_tinh', 'ma_dantoc', 'nghe_nghiep', 'ho_ten_cha', 'ho_ten_me',
                'nguoi_giam_ho', 'ten_donvi', 'nguoi_dai_dien',
                'ngay_ct', 'ngay_vao', 'ngay_ra',
                'ngay_sinhcon', 'ngay_chetcon', 'so_conchet',
                'tekt', 'loai_giayto', 'so_cccd', 'ngaycap_cccd', 'noicap_cccd',
                'is_noi_khoa', 'is_phau_thuat_thu_thuat', 'benh_icd10_id',
                'is_lao_giai_doan_nang', 'is_xo_gan_giai_doan_mat_bu',
            ];

            $vanBan = [
                'dia_chi', 'chan_doan_vao', 'chan_doan_ra', 'qt_benhly', 'tomtat_kq',
                'pp_dieutri', 'tt_ravien', 'ghi_chu', 'lydo_vvien', 'tien_su_benh',
                'dau_hieu_lam_sang', 'noi_khoa', 'phau_thuat_thu_thuat',
                'huong_dieu_tri', 'benh_icd10_ten',
            ];

            foreach ($chuoi as $cot) {
                $table->string($cot)->nullable();
            }

            foreach ($vanBan as $cot) {
                $table->text($cot)->nullable();
            }

            $table->timestamps();

            $table->foreign('chung_tu_id')->references('id')->on('ctdt_chung_tu')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('ctdt_ct04');
    }
}
```

- [ ] **Step 5: Viết migration `ctdt_ct06`**

Tạo `database/migrations/2026_08_19_100013_create_ctdt_ct06_table.php`:

```php
<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/** CT06 - Giay xac nhan nghi duong thai (Mau so 11 - TT25). KHONG co MA_YTE. */
class CreateCtdtCt06Table extends Migration
{
    public function up()
    {
        Schema::create('ctdt_ct06', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('chung_tu_id')->unique();

            $chuoi = [
                'ma_bhxh', 'ma_the', 'ho_ten', 'ngay_sinh', 'ngay_vao', 'ngay_ra',
                'nguoi_dai_dien', 'ma_bs', 'ten_bs', 'ten_dvi', 'so_kcb',
                'ngay_ct', 'so_seri', 'ma_ct',
                'loai_giayto', 'so_cccd', 'ngaycap_cccd',
                'matinh_cu_tru', 'maxa_cu_tru', 'tuoi_thai', 'benh_icd10_id',
            ];

            $vanBan = ['chan_doan', 'noi_cu_tru_nnd', 'benh_icd10_ten'];

            foreach ($chuoi as $cot) {
                $table->string($cot)->nullable();
            }

            foreach ($vanBan as $cot) {
                $table->text($cot)->nullable();
            }

            $table->timestamps();

            $table->foreign('chung_tu_id')->references('id')->on('ctdt_chung_tu')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('ctdt_ct06');
    }
}
```

- [ ] **Step 6: Viết migration `ctdt_ct07`**

Tạo `database/migrations/2026_08_19_100014_create_ctdt_ct07_table.php`:

```php
<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/** CT07 - Giay chung nhan nghi viec huong BHXH (Mau so 07 - TT25). KHONG co MA_YTE. */
class CreateCtdtCt07Table extends Migration
{
    public function up()
    {
        Schema::create('ctdt_ct07', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('chung_tu_id')->unique();

            $chuoi = [
                'ma_ct', 'mau_so', 'so_seri', 'so_kcb', 'ma_bhxh', 'ma_the',
                'ho_ten', 'ngay_sinh', 'gioi_tinh',
                'tu_ngay', 'den_ngay', 'ho_ten_cha', 'ho_ten_me',
                'thu_truong_dv', 'ma_cchn', 'ten_nguoi_hanh_nghe',
                'ngay_chung_tu', 'tekt',
                'loai_giayto', 'so_cccd', 'ngaycap_cccd', 'noicap_cccd',
                'ngay_kcb', 'benh_icd10_id',
            ];

            $vanBan = ['don_vi', 'chandoan_dieutri', 'benh_icd10_ten'];

            foreach ($chuoi as $cot) {
                $table->string($cot)->nullable();
            }

            foreach ($vanBan as $cot) {
                $table->text($cot)->nullable();
            }

            $table->timestamps();

            $table->foreign('chung_tu_id')->references('id')->on('ctdt_chung_tu')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('ctdt_ct07');
    }
}
```

- [ ] **Step 7: Viết migration `ctdt_dieu_tri_noi_tru`**

Tạo `database/migrations/2026_08_19_100015_create_ctdt_dieu_tri_noi_tru_table.php`:

```php
<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * GIAYDIEUTRINOITRU - Giay xac nhan qua trinh dieu tri noi tru (Mau so 06 - TT25).
 *
 * The goc trong base64 la <CTGiayDieuTriNoiTru>, KHAC gia tri LOAIHOSO.
 * MA_DAN_TOC co gach duoi giua DAN va TOC, khac CT03 (MA_DANTOC). BENH_ICD10_MA la MA,
 * khong phai ID. Giu nguyen.
 */
class CreateCtdtDieuTriNoiTruTable extends Migration
{
    public function up()
    {
        Schema::create('ctdt_dieu_tri_noi_tru', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('chung_tu_id')->unique();

            $chuoi = [
                'so_luu_tru', 'ma_yte', 'ma_bhxh', 'ma_the', 'ho_ten', 'ngay_sinh',
                'gioi_tinh', 'ma_khoa', 'ten_dan_toc', 'ma_dan_toc', 'nghe_nghiep',
                'ngay_vao', 'ngay_ra',
                'dai_dien_dvi', 'ma_cchn_bs', 'ten_bs',
                'loai_giayto', 'so_cccd', 'ngaycap_cccd', 'noicap_cccd',
                'benh_icd10_ma', 'ma_ct', 'ngay_ct', 'so_seri', 'tuoi_thai',
                'loai_phuong_phap', 'loai_pp_dieu_tri_vosinh',
                'ngay_dinh_chi_thainghen', 'is_nghiduongthai', 'so_ngay_nghiduongthai',
            ];

            $vanBan = ['dia_chi', 'chan_doan', 'pp_dieutri', 'mo_ta', 'ghi_chu', 'benh_icd10_ten'];

            foreach ($chuoi as $cot) {
                $table->string($cot)->nullable();
            }

            foreach ($vanBan as $cot) {
                $table->text($cot)->nullable();
            }

            $table->timestamps();

            $table->foreign('chung_tu_id')->references('id')->on('ctdt_chung_tu')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('ctdt_dieu_tri_noi_tru');
    }
}
```

- [ ] **Step 8: Viết migration `ctdt_dieu_tri_vo_sinh`**

Tạo `database/migrations/2026_08_19_100016_create_ctdt_dieu_tri_vo_sinh_table.php`:

```php
<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * GIAYDIEUTRIVOSINH - Giay xac nhan qua trinh dieu tri vo sinh (Mau so 09 - TT25).
 * The goc trong base64 la <CTGiayDieuTriVoSinh>, KHAC gia tri LOAIHOSO.
 */
class CreateCtdtDieuTriVoSinhTable extends Migration
{
    public function up()
    {
        Schema::create('ctdt_dieu_tri_vo_sinh', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('chung_tu_id')->unique();

            $chuoi = [
                'so_luu_tru', 'ma_yte', 'ma_bhxh', 'ma_the', 'ho_ten', 'ngay_sinh',
                'ma_khoa', 'ma_tinhcutru', 'ma_xacutru', 'nghe_nghiep',
                'ngay_vao', 'ngay_ra',
                'dai_dien_dvi', 'ma_cchn_bs', 'ten_bs',
                'loai_giayto', 'so_cccd', 'ngaycap_cccd', 'noicap_cccd',
                'benh_icd10_ma', 'ma_ct', 'ngay_ct', 'so_seri', 'loai_phuong_phap',
            ];

            $vanBan = ['dia_chi', 'chan_doan', 'pp_dieutri', 'ghi_chu', 'benh_icd10_ten'];

            foreach ($chuoi as $cot) {
                $table->string($cot)->nullable();
            }

            foreach ($vanBan as $cot) {
                $table->text($cot)->nullable();
            }

            $table->timestamps();

            $table->foreign('chung_tu_id')->references('id')->on('ctdt_chung_tu')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('ctdt_dieu_tri_vo_sinh');
    }
}
```

- [ ] **Step 9: Viết migration `ctdt_suc_khoe_me`**

Tạo `database/migrations/2026_08_19_100017_create_ctdt_suc_khoe_me_table.php`:

```php
<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * GIAYSUCKHOEME - Giay xac nhan nguoi me khong du suc khoe cham soc con (Mau so 10 - TT25).
 * The goc trong base64 la <CTGiaySucKhoeMe>, KHAC gia tri LOAIHOSO.
 */
class CreateCtdtSucKhoeMeTable extends Migration
{
    public function up()
    {
        Schema::create('ctdt_suc_khoe_me', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('chung_tu_id')->unique();

            $chuoi = [
                'so_luu_tru', 'ma_yte', 'ma_bhxh', 'ma_the', 'ho_ten', 'ngay_sinh',
                'ma_khoa', 'ma_tinhcutru', 'ma_xacutru', 'nghe_nghiep',
                'ngay_vao', 'ngay_ra',
                'dai_dien_dvi', 'ma_cchn_bs', 'ten_bs',
                'loai_giayto', 'so_cccd', 'ngaycap_cccd', 'noicap_cccd',
                'benh_icd10_ma', 'ma_ct', 'ngay_ct', 'so_seri',
            ];

            $vanBan = [
                'dia_chi', 'chan_doan', 'pp_dieutri', 'ket_luan',
                'tinhtrangbenhhientai', 'benh_icd10_ten',
            ];

            foreach ($chuoi as $cot) {
                $table->string($cot)->nullable();
            }

            foreach ($vanBan as $cot) {
                $table->text($cot)->nullable();
            }

            $table->timestamps();

            $table->foreign('chung_tu_id')->references('id')->on('ctdt_chung_tu')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('ctdt_suc_khoe_me');
    }
}
```

- [ ] **Step 10: Viết migration `ctdt_giay_bao_tu`**

Tạo `database/migrations/2026_08_19_100018_create_ctdt_giay_bao_tu_table.php`:

```php
<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/** GIAYBAOTU - dich vu loaiHs=60, goi HSDLGBT. Khoa nghiep vu la MA_GBT. */
class CreateCtdtGiayBaoTuTable extends Migration
{
    public function up()
    {
        Schema::create('ctdt_giay_bao_tu', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('chung_tu_id')->unique();

            $chuoi = [
                'ma_gbt', 'ma_bn', 'ma_hsba', 'ho_ten', 'ngay_sinh', 'gioi_tinh',
                'ma_the', 'ma_dantoc', 'ma_quoctich',
                'matinh_thuongtru', 'mahuyen_thuongtru', 'maxa_thuongtru',
                'matinh_hientai', 'mahuyen_hientai', 'maxa_hientai',
                'loai_giayto', 'so_giayto', 'ngay_cap', 'noi_cap',
                'ngaygio_vv', 'ngay_tv', 'tinh_trang_tv',
                'nguoi_ghigiay', 'nguoi_thanthich', 'ttruong_dvi',
                'so_baotu', 'quyen_so', 'ngay_capgiaybt', 'so_baotu_bd', 'quyen_so_bd',
                'macskcb', 'ma_bhxh', 'benh_icd10_id',
            ];

            $vanBan = [
                'dchi_thuongtru', 'dchi_hientai', 'nguyennhan_tv',
                'diachi_cskcb', 'benh_icd10_ten',
            ];

            foreach ($chuoi as $cot) {
                $table->string($cot)->nullable();
            }

            foreach ($vanBan as $cot) {
                $table->text($cot)->nullable();
            }

            $table->timestamps();

            $table->foreign('chung_tu_id')->references('id')->on('ctdt_chung_tu')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('ctdt_giay_bao_tu');
    }
}
```

- [ ] **Step 11: Viết migration `ctdt_giay_chung_sinh`**

Tạo `database/migrations/2026_08_19_100019_create_ctdt_giay_chung_sinh_table.php`:

```php
<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * GIAYCHUNGSINH - dich vu loaiHs=61, goi HSDLGCS. Khoa nghiep vu la MA_GCS.
 *
 * BA nhom nguoi dung hau to khac nhau, de nham nhat trong ca dac ta:
 *   _NND     : nguoi de
 *   _MTH     : me thay the (mang thai ho)
 *   _CHA_MTH : cha cua me thay the
 *   _CHA_NND : cha cua nguoi de
 *
 * NGAY_SINH_CON dai 14 ky tu (20251021000000), khac cac truong ngay 8 hoac 12 ky tu.
 */
class CreateCtdtGiayChungSinhTable extends Migration
{
    public function up()
    {
        Schema::create('ctdt_giay_chung_sinh', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('chung_tu_id')->unique();

            $chuoi = [
                // Dinh danh va nguoi de
                'ma_gcs', 'ma_bn', 'ma_ct', 'so_seri',
                'ma_bhxh_nnd', 'ma_the_nnd', 'hoten_nnd', 'ngaysinh_nnd',
                'ma_dantoc_nnd', 'ma_quoctich_nnd', 'loai_giayto_nnd', 'so_cccd_nnd',
                'ngaycap_cccd_nnd', 'noicap_cccd_nnd',
                'matinh_cu_tru', 'mahuyen_cu_tru', 'maxa_cu_tru',
                'ho_ten_cha', 'ma_the_tam',
                // Thong tin con
                'ten_con', 'gioi_tinh_con', 'so_con', 'lan_sinh', 'so_con_song',
                'can_nang_con', 'ngay_sinh_con',
                'sinhcon_phauthuat', 'sinhcon_duoi32tuan',
                // Nguoi lap phieu va don vi
                'nguoi_do_de', 'nguoi_ghi_phieu', 'ma_ttdv', 'thu_truong_dvi',
                'ngay_ct', 'so', 'quyen_so',
                // Me thay the
                'ma_bhxh_mth', 'ma_the_mth', 'hoten_mth', 'ngaysinh_mth',
                'ma_dantoc_mth', 'ma_quoctich_mth', 'loai_giayto_mth', 'so_cccd_mth',
                'ngaycap_cccd_mth', 'noicap_cccd_mth',
                'matinh_cu_tru_mth', 'maxa_cu_tru_mth',
                'ho_ten_cha_mth', 'ngaysinh_cha_mth', 'ma_dantoc_cha_mth',
                'matinh_cu_tru_cha_mth', 'maxa_cu_tru_cha_mth',
                'loai_giayto_cha_mth', 'so_cccd_cha_mth',
                'ngaycap_cccd_cha_mth', 'noicap_cccd_cha_mth',
                // Cha cua nguoi de
                'ngaysinh_cha_nnd', 'ma_dantoc_cha_nnd', 'loai_giayto_cha_nnd',
                'so_cccd_cha_nnd', 'ngaycap_cccd_cha_nnd', 'noicap_cccd_cha_nnd',
                'cap_lan_dau',
            ];

            $vanBan = [
                'noi_cu_tru_nnd', 'noi_sinh_con', 'tinh_trang_con', 'ghi_chu',
                'noi_cu_tru_mth', 'noi_cu_tru_cha_mth',
            ];

            foreach ($chuoi as $cot) {
                $table->string($cot)->nullable();
            }

            foreach ($vanBan as $cot) {
                $table->text($cot)->nullable();
            }

            $table->timestamps();

            $table->foreign('chung_tu_id')->references('id')->on('ctdt_chung_tu')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('ctdt_giay_chung_sinh');
    }
}
```

- [ ] **Step 12: Viết 9 model chi tiết**

Mỗi model theo đúng khuôn dưới đây. Tạo `app/Models/BHYT/Ctdt/CtdtCt03.php`:

```php
<?php

namespace App\Models\BHYT\Ctdt;

use Illuminate\Database\Eloquent\Model;

class CtdtCt03 extends Model
{
    protected $table = 'ctdt_ct03';

    protected $fillable = [
        'chung_tu_id',
        'so_luu_tru', 'ma_yte', 'ma_khoa', 'ma_bhxh', 'ma_the', 'ho_ten',
        'ngay_sinh', 'gioi_tinh', 'ma_dantoc', 'nghe_nghiep', 'dia_chi',
        'ngay_vao', 'ngay_ra', 'dinh_chi_thai_nghen', 'tuoi_thai',
        'chan_doan', 'pp_dieutri', 'ghi_chu',
        'thu_truong_dvi', 'ma_cchn_truongkhoa', 'ten_truongkhoa',
        'ngay_chung_tu', 'tekt', 'ho_ten_cha', 'ho_ten_me',
        'ngoaitru_tungay', 'ngoaitru_denngay',
        'loai_giayto', 'so_cccd', 'ngaycap_cccd', 'noicap_cccd',
        'benhicd10_id', 'tenbenhnicd10',
    ];

    public function chungTu()
    {
        return $this->belongsTo(CtdtChungTu::class, 'chung_tu_id', 'id');
    }
}
```

Tám model còn lại giống hệt khuôn trên, chỉ khác `$table`, `$fillable` và tên lớp. Danh sách
`$fillable` của mỗi model = `'chung_tu_id'` + hợp của hai mảng `$chuoi` và `$vanBan` trong
migration tương ứng:

| Lớp | `$table` | Nguồn `$fillable` |
|---|---|---|
| `CtdtCt04` | `ctdt_ct04` | migration `2026_08_19_100012` |
| `CtdtCt06` | `ctdt_ct06` | migration `2026_08_19_100013` |
| `CtdtCt07` | `ctdt_ct07` | migration `2026_08_19_100014` |
| `CtdtDieuTriNoiTru` | `ctdt_dieu_tri_noi_tru` | migration `2026_08_19_100015` |
| `CtdtDieuTriVoSinh` | `ctdt_dieu_tri_vo_sinh` | migration `2026_08_19_100016` |
| `CtdtSucKhoeMe` | `ctdt_suc_khoe_me` | migration `2026_08_19_100017` |
| `CtdtGiayBaoTu` | `ctdt_giay_bao_tu` | migration `2026_08_19_100018` |
| `CtdtGiayChungSinh` | `ctdt_giay_chung_sinh` | migration `2026_08_19_100019` |

Task 8 có test bắt `$fillable` phải khớp đúng cột bảng, nên sai sót ở bước này sẽ bị bắt.

- [ ] **Step 13: Chạy test để xác nhận xanh**

```bash
php vendor/bin/phpunit tests/Unit/Ctdt/CtdtBangChiTietTest.php
```

Kỳ vọng: `OK (7 tests)`.

- [ ] **Step 14: Commit**

```bash
git add database/migrations/2026_08_19_1000*.php app/Models/BHYT/Ctdt tests/Unit/Ctdt/CtdtBangChiTietTest.php
git commit -m "feat(ctdt): chin bang chi tiet chung tu va model"
```

---

## Task 4: Interface `LoaiChungTu`, registry và lớp `Ct03`

**Files:**
- Create: `app/Services/Ctdt/Loai/LoaiChungTu.php`
- Create: `app/Services/Ctdt/Loai/Ct03.php`
- Create: `app/Services/Ctdt/CtdtLoaiRegistry.php`
- Test: `tests/Unit/Ctdt/CtdtLoaiRegistryTest.php`

**Interfaces:**
- Consumes: model `App\Models\BHYT\Ctdt\CtdtCt03` (Task 3)
- Produces:
  - Interface `App\Services\Ctdt\Loai\LoaiChungTu` với 9 phương thức tĩnh:
    `maLoaiHoSo(): string`, `theGoc(): string`, `dichVu(): string`, `bang(): string`,
    `model(): string`, `tenTab(): string`, `truong(): array`,
    `maChungTu(\SimpleXMLElement $xml): ?string`, `rutGon(\SimpleXMLElement $xml): array`.
  - Lớp `App\Services\Ctdt\CtdtLoaiRegistry` với:
    `tatCa(): array` (mảng `LOAIHOSO => tên lớp`),
    `cho(string $loaiHoSo): string` (ném `\InvalidArgumentException` nếu không có),
    `co(string $loaiHoSo): bool`,
    `xacNhanTheGoc(string $loaiHoSo, string $theGocThucTe): void` (ném `\RuntimeException` nếu lệch),
    `cuaDichVu(string $dichVu): array`.

- [ ] **Step 1: Viết test đỏ**

Tạo `tests/Unit/Ctdt/CtdtLoaiRegistryTest.php`:

```php
<?php

namespace Tests\Unit\Ctdt;

use Tests\TestCase;
use App\Services\Ctdt\CtdtLoaiRegistry;
use App\Services\Ctdt\Loai\Ct03;

/**
 * Canh registry loai chung tu: tra dung lop, va DOI CHIEU CHEO the goc.
 */
class CtdtLoaiRegistryTest extends TestCase
{
    /** @test */
    public function tra_duoc_lop_theo_gia_tri_loai_ho_so()
    {
        $this->assertSame(Ct03::class, CtdtLoaiRegistry::cho('CT03'));
        $this->assertTrue(CtdtLoaiRegistry::co('CT03'));
    }

    /** @test */
    public function loai_la_thi_nem_chu_khong_doan()
    {
        // Doan nghia la mot loai chung tu moi cua BHXH se bi nap vao SAI BANG ma khong
        // bao gi ca. Nem de nguoi van hanh biet ngay o buoc nap.
        $this->assertFalse(CtdtLoaiRegistry::co('CT99'));

        $this->expectException(\InvalidArgumentException::class);

        CtdtLoaiRegistry::cho('CT99');
    }

    /** @test */
    public function the_goc_khop_thi_khong_nem()
    {
        CtdtLoaiRegistry::xacNhanTheGoc('CT03', 'CT03');

        $this->assertTrue(true, 'Khop the goc thi khong duoc nem');
    }

    /** @test */
    public function the_goc_lech_thi_nem()
    {
        $this->expectException(\RuntimeException::class);

        CtdtLoaiRegistry::xacNhanTheGoc('CT03', 'CT04');
    }

    /** @test */
    public function ct03_tu_mo_ta_dung_bang_va_model()
    {
        $this->assertSame('CT03', Ct03::maLoaiHoSo());
        $this->assertSame('CT03', Ct03::theGoc());
        $this->assertSame('CT2025', Ct03::dichVu());
        $this->assertSame('ctdt_ct03', Ct03::bang());
        $this->assertSame(\App\Models\BHYT\Ctdt\CtdtCt03::class, Ct03::model());
        $this->assertNotEmpty(Ct03::tenTab());
    }

    /** @test */
    public function ct03_anh_xa_the_sang_cot()
    {
        $truong = Ct03::truong();

        // Anh xa la THE -> COT, khong phai cot -> the: doc XML thi ta co ten the truoc.
        $this->assertSame('ma_yte', $truong['MA_YTE']);
        $this->assertSame('benhicd10_id', $truong['BENHICD10_ID']);
        $this->assertSame('tenbenhnicd10', $truong['TENBENHNICD10']);
        $this->assertSame('ghi_chu', $truong['GHI_CHU']);
        $this->assertCount(33, $truong, 'CT03 phai co dung 33 truong');
    }

    /** @test */
    public function ct03_lay_ma_chung_tu_tu_ma_yte()
    {
        $xml = simplexml_load_string('<CT03><MA_YTE>YT001</MA_YTE></CT03>');

        $this->assertSame('YT001', Ct03::maChungTu($xml));
    }

    /** @test */
    public function ct03_thieu_ma_yte_thi_tra_null()
    {
        $xml = simplexml_load_string('<CT03><MA_THE>DN123</MA_THE></CT03>');

        $this->assertNull(Ct03::maChungTu($xml));
    }

    /** @test */
    public function ct03_rut_gon_du_nam_cot_danh_sach()
    {
        $xml = simplexml_load_string(
            '<CT03><MA_THE>DN123</MA_THE><HO_TEN>Nguyen Van Test</HO_TEN>'
            . '<NGAY_SINH>19950914</NGAY_SINH><NGAY_VAO>201912121200</NGAY_VAO>'
            . '<NGAY_RA>201912180001</NGAY_RA></CT03>'
        );

        $this->assertSame([
            'ma_the'    => 'DN123',
            'ho_ten'    => 'Nguyen Van Test',
            'ngay_sinh' => '19950914',
            'ngay_vao'  => '201912121200',
            'ngay_ra'   => '201912180001',
        ], Ct03::rutGon($xml));
    }

    /** @test */
    public function rut_gon_the_rong_tra_null_khong_tra_chuoi_rong()
    {
        // Chuoi rong va "khong khai" la hai chuyen khac nhau khi doi soat voi BHXH.
        $xml = simplexml_load_string('<CT03><MA_THE></MA_THE></CT03>');

        $this->assertNull(Ct03::rutGon($xml)['ma_the']);
    }
}
```

- [ ] **Step 2: Chạy test để xác nhận đỏ**

```bash
php vendor/bin/phpunit tests/Unit/Ctdt/CtdtLoaiRegistryTest.php
```

Kỳ vọng: đỏ — `Class 'App\Services\Ctdt\CtdtLoaiRegistry' not found`.

- [ ] **Step 3: Viết interface**

Tạo `app/Services/Ctdt/Loai/LoaiChungTu.php`:

```php
<?php

namespace App\Services\Ctdt\Loai;

/**
 * Mot loai chung tu TU MO TA: bang nao, the nao, quy tac kiem nao, tab nao.
 *
 * Vi sao tinh (static) chu khong phai doi tuong: khong lop nao co trang thai rieng, va
 * registry chi giu TEN LOP. Khoi tao chi de goi mot ham thuan la them buoc thua.
 *
 * Vi sao KHONG dat quy tac kiem o day trong Giai doan 1: bo kiem thuoc Giai doan 3.
 * Them phuong thuc quyTacKiem() vao interface se buoc 9 lop cai mot ham rong ngay bay gio.
 */
interface LoaiChungTu
{
    /** Gia tri the LOAIHOSO trong FILEHOSO, vi du 'GIAYDIEUTRINOITRU' */
    public static function maLoaiHoSo();

    /** Ten the goc BEN TRONG base64, vi du 'CTGiayDieuTriNoiTru'. KHONG luon trung maLoaiHoSo(). */
    public static function theGoc();

    /** Dich vu chua loai nay: 'CT2025' | 'GBT' | 'GCS' */
    public static function dichVu();

    /** Ten bang chi tiet */
    public static function bang();

    /** Ten lop model */
    public static function model();

    /** Nhan hien thi tren tab chi tiet */
    public static function tenTab();

    /** @return array Anh xa TEN THE => ten cot */
    public static function truong();

    /** @return string|null Khoa nghiep vu cua chung tu (MA_YTE / MA_GBT / MA_GCS) */
    public static function maChungTu(\SimpleXMLElement $xml);

    /** @return array Nam cot rut gon: ma_the, ho_ten, ngay_sinh, ngay_vao, ngay_ra */
    public static function rutGon(\SimpleXMLElement $xml);
}
```

- [ ] **Step 4: Viết lớp trợ giúp đọc thẻ**

Tạo `app/Services/Ctdt/Loai/DocThe.php`:

```php
<?php

namespace App\Services\Ctdt\Loai;

/**
 * Doc mot the XML ve chuoi hoac null.
 *
 * VI SAO CAN: (string) tren the vang tra ve chuoi rong, va chuoi rong khac "khong khai"
 * khi doi soat voi BHXH. Chin lop loai deu can dung mot cach doc nay - viet mot lan.
 */
class DocThe
{
    /**
     * @return string|null null khi the khong ton tai hoac rong sau khi trim
     */
    public static function chuoi(\SimpleXMLElement $xml, $ten)
    {
        if (!isset($xml->{$ten})) {
            return null;
        }

        $giaTri = trim((string) $xml->{$ten});

        return $giaTri === '' ? null : $giaTri;
    }
}
```

- [ ] **Step 5: Viết lớp `Ct03`**

Tạo `app/Services/Ctdt/Loai/Ct03.php`:

```php
<?php

namespace App\Services\Ctdt\Loai;

use App\Models\BHYT\Ctdt\CtdtCt03;

/**
 * CT03 - Giay ra vien (Mau so 02 - TT25).
 *
 * BENHICD10_ID / TENBENHNICD10 dat ten khac cac loai khac. Giu nguyen theo dac ta.
 */
class Ct03 implements LoaiChungTu
{
    public static function maLoaiHoSo()
    {
        return 'CT03';
    }

    public static function theGoc()
    {
        return 'CT03';
    }

    public static function dichVu()
    {
        return 'CT2025';
    }

    public static function bang()
    {
        return 'ctdt_ct03';
    }

    public static function model()
    {
        return CtdtCt03::class;
    }

    public static function tenTab()
    {
        return 'Giấy ra viện';
    }

    public static function truong()
    {
        return [
            'SO_LUU_TRU'          => 'so_luu_tru',
            'MA_YTE'              => 'ma_yte',
            'MA_KHOA'             => 'ma_khoa',
            'MA_BHXH'             => 'ma_bhxh',
            'MA_THE'              => 'ma_the',
            'HO_TEN'              => 'ho_ten',
            'NGAY_SINH'           => 'ngay_sinh',
            'GIOI_TINH'           => 'gioi_tinh',
            'MA_DANTOC'           => 'ma_dantoc',
            'NGHE_NGHIEP'         => 'nghe_nghiep',
            'DIA_CHI'             => 'dia_chi',
            'NGAY_VAO'            => 'ngay_vao',
            'NGAY_RA'             => 'ngay_ra',
            'DINH_CHI_THAI_NGHEN' => 'dinh_chi_thai_nghen',
            'TUOI_THAI'           => 'tuoi_thai',
            'CHAN_DOAN'           => 'chan_doan',
            'PP_DIEUTRI'          => 'pp_dieutri',
            'GHI_CHU'             => 'ghi_chu',
            'THU_TRUONG_DVI'      => 'thu_truong_dvi',
            'MA_CCHN_TRUONGKHOA'  => 'ma_cchn_truongkhoa',
            'TEN_TRUONGKHOA'      => 'ten_truongkhoa',
            'NGAY_CHUNG_TU'       => 'ngay_chung_tu',
            'TEKT'                => 'tekt',
            'HO_TEN_CHA'          => 'ho_ten_cha',
            'HO_TEN_ME'           => 'ho_ten_me',
            'NGOAITRU_TUNGAY'     => 'ngoaitru_tungay',
            'NGOAITRU_DENNGAY'    => 'ngoaitru_denngay',
            'LOAI_GIAYTO'         => 'loai_giayto',
            'SO_CCCD'             => 'so_cccd',
            'NGAYCAP_CCCD'        => 'ngaycap_cccd',
            'NOICAP_CCCD'         => 'noicap_cccd',
            'BENHICD10_ID'        => 'benhicd10_id',
            'TENBENHNICD10'       => 'tenbenhnicd10',
        ];
    }

    public static function maChungTu(\SimpleXMLElement $xml)
    {
        return DocThe::chuoi($xml, 'MA_YTE');
    }

    public static function rutGon(\SimpleXMLElement $xml)
    {
        return [
            'ma_the'    => DocThe::chuoi($xml, 'MA_THE'),
            'ho_ten'    => DocThe::chuoi($xml, 'HO_TEN'),
            'ngay_sinh' => DocThe::chuoi($xml, 'NGAY_SINH'),
            'ngay_vao'  => DocThe::chuoi($xml, 'NGAY_VAO'),
            'ngay_ra'   => DocThe::chuoi($xml, 'NGAY_RA'),
        ];
    }
}
```

- [ ] **Step 6: Viết registry**

Tạo `app/Services/Ctdt/CtdtLoaiRegistry.php`:

```php
<?php

namespace App\Services\Ctdt;

use App\Services\Ctdt\Loai\Ct03;

/**
 * Diem tra cuu DUY NHAT tu gia tri LOAIHOSO sang lop loai chung tu.
 *
 * VI SAO PHAI DOI CHIEU CHEO THE GOC: gia tri LOAIHOSO KHONG luon trung ten the goc
 * ben trong base64 - GIAYDIEUTRINOITRU chua <CTGiayDieuTriNoiTru>. Neu lay ten the goc
 * lam khoa tra (cach tu nhien nhat) thi ba loai lech nay roi vao nhanh "loai la" mot
 * cach IM LANG. Doi chieu hai chieu de sai lech lo ra ngay o buoc nap.
 */
class CtdtLoaiRegistry
{
    /**
     * @return array [gia tri LOAIHOSO => ten lop]
     */
    public static function tatCa()
    {
        return [
            'CT03' => Ct03::class,
        ];
    }

    public static function co($loaiHoSo)
    {
        return is_string($loaiHoSo) && array_key_exists($loaiHoSo, self::tatCa());
    }

    /**
     * @throws \InvalidArgumentException khi loai khong co trong dang ky
     */
    public static function cho($loaiHoSo)
    {
        if (!self::co($loaiHoSo)) {
            throw new \InvalidArgumentException('Loai ho so khong nam trong dang ky: ' . $loaiHoSo);
        }

        return self::tatCa()[$loaiHoSo];
    }

    /**
     * @throws \RuntimeException khi the goc thuc te khac the goc khai trong lop loai
     */
    public static function xacNhanTheGoc($loaiHoSo, $theGocThucTe)
    {
        $lop = self::cho($loaiHoSo);
        $mongDoi = $lop::theGoc();

        if ($mongDoi !== $theGocThucTe) {
            throw new \RuntimeException(
                'LOAIHOSO ' . $loaiHoSo . ' mong doi the goc <' . $mongDoi
                . '> nhung noi dung la <' . $theGocThucTe . '>'
            );
        }
    }

    /**
     * @return array [gia tri LOAIHOSO => ten lop] cua mot dich vu
     */
    public static function cuaDichVu($dichVu)
    {
        $ketQua = [];

        foreach (self::tatCa() as $loai => $lop) {
            if ($lop::dichVu() === $dichVu) {
                $ketQua[$loai] = $lop;
            }
        }

        return $ketQua;
    }
}
```

- [ ] **Step 7: Chạy test để xác nhận xanh**

```bash
php vendor/bin/phpunit tests/Unit/Ctdt/CtdtLoaiRegistryTest.php
```

Kỳ vọng: `OK (10 tests)`.

- [ ] **Step 8: Commit**

```bash
git add app/Services/Ctdt tests/Unit/Ctdt/CtdtLoaiRegistryTest.php
git commit -m "feat(ctdt): interface LoaiChungTu, registry va lop Ct03"
```

---

## Task 5: Ba loại trùng tên thẻ — `Ct04`, `Ct06`, `Ct07`

**Files:**
- Create: `app/Services/Ctdt/Loai/Ct04.php`
- Create: `app/Services/Ctdt/Loai/Ct06.php`
- Create: `app/Services/Ctdt/Loai/Ct07.php`
- Modify: `app/Services/Ctdt/CtdtLoaiRegistry.php` (thêm ba dòng vào `tatCa()`)
- Test: `tests/Unit/Ctdt/CtdtLoaiTt25Test.php`

**Interfaces:**
- Consumes: `LoaiChungTu`, `DocThe`, `CtdtLoaiRegistry` (Task 4); model `CtdtCt04`, `CtdtCt06`, `CtdtCt07` (Task 3)
- Produces: `App\Services\Ctdt\Loai\Ct04`, `Ct06`, `Ct07` — cùng interface

- [ ] **Step 1: Viết test đỏ**

Tạo `tests/Unit/Ctdt/CtdtLoaiTt25Test.php`:

```php
<?php

namespace Tests\Unit\Ctdt;

use Tests\TestCase;
use App\Services\Ctdt\CtdtLoaiRegistry;
use App\Services\Ctdt\Loai\Ct04;
use App\Services\Ctdt\Loai\Ct06;
use App\Services\Ctdt\Loai\Ct07;

/**
 * Ba loai co ten the goc TRUNG voi gia tri LOAIHOSO, va deu KHONG co MA_YTE.
 */
class CtdtLoaiTt25Test extends TestCase
{
    public function baLoai()
    {
        return [
            ['CT04', Ct04::class, 'ctdt_ct04', 45],
            ['CT06', Ct06::class, 'ctdt_ct06', 24],
            ['CT07', Ct07::class, 'ctdt_ct07', 27],
        ];
    }

    /** @test */
    public function ba_loai_nam_trong_registry_va_thuoc_dich_vu_ct2025()
    {
        foreach ($this->baLoai() as list($ma, $lop, $bang, $soTruong)) {
            $this->assertSame($lop, CtdtLoaiRegistry::cho($ma));
            $this->assertSame($ma, $lop::maLoaiHoSo());
            $this->assertSame($ma, $lop::theGoc(), $ma . ': the goc phai trung LOAIHOSO');
            $this->assertSame('CT2025', $lop::dichVu());
            $this->assertSame($bang, $lop::bang());
            $this->assertCount($soTruong, $lop::truong(), $ma . ': sai so truong');
            $this->assertNotEmpty($lop::tenTab());
        }
    }

    /** @test */
    public function ba_loai_deu_khong_co_ma_yte_nen_ma_chung_tu_la_null()
    {
        // Day chinh la ly do ho so chi gom CT04/CT06/CT07 roi vao nhanh lui GUID va
        // KHONG ghi de duoc. Ghim hanh vi nay bang test de khong ai "sua" thanh doan bua.
        foreach ($this->baLoai() as list($ma, $lop, $_bang, $_so)) {
            $xml = simplexml_load_string('<' . $ma . '><MA_THE>DN123</MA_THE></' . $ma . '>');

            $this->assertNull($lop::maChungTu($xml), $ma . ': khong duoc bia ma chung tu');
        }
    }

    /** @test */
    public function ct04_dung_ngay_ct_khong_phai_ngay_chung_tu()
    {
        $truong = Ct04::truong();

        $this->assertSame('ngay_ct', $truong['NGAY_CT']);
        $this->assertArrayNotHasKey('NGAY_CHUNG_TU', $truong, 'CT04 khong co the NGAY_CHUNG_TU');
    }

    /** @test */
    public function ct07_dung_tu_ngay_den_ngay_lam_khoang_dieu_tri()
    {
        // CT07 khong co NGAY_VAO/NGAY_RA ma dung TU_NGAY/DEN_NGAY. Cot rut gon van phai
        // day du de man danh sach loc duoc theo mot bo cot duy nhat.
        $xml = simplexml_load_string(
            '<CT07><MA_THE>DN123</MA_THE><HO_TEN>Test</HO_TEN>'
            . '<NGAY_SINH>19950914</NGAY_SINH><TU_NGAY>20251001</TU_NGAY>'
            . '<DEN_NGAY>20251010</DEN_NGAY></CT07>'
        );

        $rutGon = Ct07::rutGon($xml);

        $this->assertSame('20251001', $rutGon['ngay_vao']);
        $this->assertSame('20251010', $rutGon['ngay_ra']);
    }

    /** @test */
    public function ct06_rut_gon_doc_dung_the()
    {
        $xml = simplexml_load_string(
            '<CT06><MA_THE>DN456</MA_THE><HO_TEN>Tran Thi Test</HO_TEN>'
            . '<NGAY_SINH>19480826</NGAY_SINH><NGAY_VAO>20251003</NGAY_VAO>'
            . '<NGAY_RA>20251030</NGAY_RA></CT06>'
        );

        $this->assertSame([
            'ma_the'    => 'DN456',
            'ho_ten'    => 'Tran Thi Test',
            'ngay_sinh' => '19480826',
            'ngay_vao'  => '20251003',
            'ngay_ra'   => '20251030',
        ], Ct06::rutGon($xml));
    }
}
```

- [ ] **Step 2: Chạy test để xác nhận đỏ**

```bash
php vendor/bin/phpunit tests/Unit/Ctdt/CtdtLoaiTt25Test.php
```

Kỳ vọng: đỏ — `Class 'App\Services\Ctdt\Loai\Ct04' not found`.

- [ ] **Step 3: Viết lớp `Ct04`**

Tạo `app/Services/Ctdt/Loai/Ct04.php`:

```php
<?php

namespace App\Services\Ctdt\Loai;

use App\Models\BHYT\Ctdt\CtdtCt04;

/**
 * CT04 - Ban tom tat ho so benh an (Mau so 03 - TT25).
 *
 * KHONG co MA_YTE: mot HOSO chi gom CT04 se khong co khoa nghiep vu va phai lui ve
 * Id GUID cua THONGTINHOSO - luc do khong ghi de duoc. Day la han che DA BIET.
 */
class Ct04 implements LoaiChungTu
{
    public static function maLoaiHoSo()
    {
        return 'CT04';
    }

    public static function theGoc()
    {
        return 'CT04';
    }

    public static function dichVu()
    {
        return 'CT2025';
    }

    public static function bang()
    {
        return 'ctdt_ct04';
    }

    public static function model()
    {
        return CtdtCt04::class;
    }

    public static function tenTab()
    {
        return 'Tóm tắt hồ sơ bệnh án';
    }

    public static function truong()
    {
        return [
            'MA_CT'                      => 'ma_ct',
            'SO_SERI'                    => 'so_seri',
            'MA_BHXH'                    => 'ma_bhxh',
            'MA_THE'                     => 'ma_the',
            'HO_TEN'                     => 'ho_ten',
            'NGAY_SINH'                  => 'ngay_sinh',
            'GIOI_TINH'                  => 'gioi_tinh',
            'MA_DANTOC'                  => 'ma_dantoc',
            'DIA_CHI'                    => 'dia_chi',
            'NGHE_NGHIEP'                => 'nghe_nghiep',
            'HO_TEN_CHA'                 => 'ho_ten_cha',
            'HO_TEN_ME'                  => 'ho_ten_me',
            'NGUOI_GIAM_HO'              => 'nguoi_giam_ho',
            'TEN_DONVI'                  => 'ten_donvi',
            'NGUOI_DAI_DIEN'             => 'nguoi_dai_dien',
            'NGAY_CT'                    => 'ngay_ct',
            'NGAY_VAO'                   => 'ngay_vao',
            'NGAY_RA'                    => 'ngay_ra',
            'CHAN_DOAN_VAO'              => 'chan_doan_vao',
            'CHAN_DOAN_RA'               => 'chan_doan_ra',
            'QT_BENHLY'                  => 'qt_benhly',
            'TOMTAT_KQ'                  => 'tomtat_kq',
            'PP_DIEUTRI'                 => 'pp_dieutri',
            'NGAY_SINHCON'               => 'ngay_sinhcon',
            'NGAY_CHETCON'               => 'ngay_chetcon',
            'SO_CONCHET'                 => 'so_conchet',
            'TT_RAVIEN'                  => 'tt_ravien',
            'GHI_CHU'                    => 'ghi_chu',
            'TEKT'                       => 'tekt',
            'LOAI_GIAYTO'                => 'loai_giayto',
            'SO_CCCD'                    => 'so_cccd',
            'NGAYCAP_CCCD'               => 'ngaycap_cccd',
            'NOICAP_CCCD'                => 'noicap_cccd',
            'LYDO_VVIEN'                 => 'lydo_vvien',
            'TIEN_SU_BENH'               => 'tien_su_benh',
            'DAU_HIEU_LAM_SANG'          => 'dau_hieu_lam_sang',
            'NOI_KHOA'                   => 'noi_khoa',
            'IS_NOI_KHOA'                => 'is_noi_khoa',
            'PHAU_THUAT_THU_THUAT'       => 'phau_thuat_thu_thuat',
            'IS_PHAU_THUAT_THU_THUAT'    => 'is_phau_thuat_thu_thuat',
            'HUONG_DIEU_TRI'             => 'huong_dieu_tri',
            'BENH_ICD10_ID'              => 'benh_icd10_id',
            'BENH_ICD10_TEN'             => 'benh_icd10_ten',
            'IS_LAO_GIAI_DOAN_NANG'      => 'is_lao_giai_doan_nang',
            'IS_XO_GAN_GIAI_DOAN_MAT_BU' => 'is_xo_gan_giai_doan_mat_bu',
        ];
    }

    public static function maChungTu(\SimpleXMLElement $xml)
    {
        // CT04 khong co MA_YTE. Tra null thay vi bia tu MA_CT / SO_SERI: bia khoa co
        // rui ro nang hon - hai ho so khac nhau bi coi la mot va mat du lieu im lang.
        return null;
    }

    public static function rutGon(\SimpleXMLElement $xml)
    {
        return [
            'ma_the'    => DocThe::chuoi($xml, 'MA_THE'),
            'ho_ten'    => DocThe::chuoi($xml, 'HO_TEN'),
            'ngay_sinh' => DocThe::chuoi($xml, 'NGAY_SINH'),
            'ngay_vao'  => DocThe::chuoi($xml, 'NGAY_VAO'),
            'ngay_ra'   => DocThe::chuoi($xml, 'NGAY_RA'),
        ];
    }
}
```

- [ ] **Step 4: Viết lớp `Ct06`**

Tạo `app/Services/Ctdt/Loai/Ct06.php`:

```php
<?php

namespace App\Services\Ctdt\Loai;

use App\Models\BHYT\Ctdt\CtdtCt06;

/** CT06 - Giay xac nhan nghi duong thai (Mau so 11 - TT25). KHONG co MA_YTE. */
class Ct06 implements LoaiChungTu
{
    public static function maLoaiHoSo()
    {
        return 'CT06';
    }

    public static function theGoc()
    {
        return 'CT06';
    }

    public static function dichVu()
    {
        return 'CT2025';
    }

    public static function bang()
    {
        return 'ctdt_ct06';
    }

    public static function model()
    {
        return CtdtCt06::class;
    }

    public static function tenTab()
    {
        return 'Nghỉ dưỡng thai';
    }

    public static function truong()
    {
        return [
            'MA_BHXH'        => 'ma_bhxh',
            'MA_THE'         => 'ma_the',
            'HO_TEN'         => 'ho_ten',
            'NGAY_SINH'      => 'ngay_sinh',
            'NGAY_VAO'       => 'ngay_vao',
            'NGAY_RA'        => 'ngay_ra',
            'CHAN_DOAN'      => 'chan_doan',
            'NGUOI_DAI_DIEN' => 'nguoi_dai_dien',
            'MA_BS'          => 'ma_bs',
            'TEN_BS'         => 'ten_bs',
            'TEN_DVI'        => 'ten_dvi',
            'SO_KCB'         => 'so_kcb',
            'NGAY_CT'        => 'ngay_ct',
            'SO_SERI'        => 'so_seri',
            'MA_CT'          => 'ma_ct',
            'LOAI_GIAYTO'    => 'loai_giayto',
            'SO_CCCD'        => 'so_cccd',
            'NGAYCAP_CCCD'   => 'ngaycap_cccd',
            'NOI_CU_TRU_NND' => 'noi_cu_tru_nnd',
            'MATINH_CU_TRU'  => 'matinh_cu_tru',
            'MAXA_CU_TRU'    => 'maxa_cu_tru',
            'TUOI_THAI'      => 'tuoi_thai',
            'BENH_ICD10_ID'  => 'benh_icd10_id',
            'BENH_ICD10_TEN' => 'benh_icd10_ten',
        ];
    }

    public static function maChungTu(\SimpleXMLElement $xml)
    {
        return null;
    }

    public static function rutGon(\SimpleXMLElement $xml)
    {
        return [
            'ma_the'    => DocThe::chuoi($xml, 'MA_THE'),
            'ho_ten'    => DocThe::chuoi($xml, 'HO_TEN'),
            'ngay_sinh' => DocThe::chuoi($xml, 'NGAY_SINH'),
            'ngay_vao'  => DocThe::chuoi($xml, 'NGAY_VAO'),
            'ngay_ra'   => DocThe::chuoi($xml, 'NGAY_RA'),
        ];
    }
}
```

- [ ] **Step 5: Viết lớp `Ct07`**

Tạo `app/Services/Ctdt/Loai/Ct07.php`:

```php
<?php

namespace App\Services\Ctdt\Loai;

use App\Models\BHYT\Ctdt\CtdtCt07;

/**
 * CT07 - Giay chung nhan nghi viec huong BHXH (Mau so 07 - TT25). KHONG co MA_YTE.
 *
 * Khong co NGAY_VAO/NGAY_RA ma dung TU_NGAY/DEN_NGAY. Cot rut gon van dien vao
 * ngay_vao/ngay_ra de man danh sach chi phai loc theo MOT bo cot cho ca chin loai.
 */
class Ct07 implements LoaiChungTu
{
    public static function maLoaiHoSo()
    {
        return 'CT07';
    }

    public static function theGoc()
    {
        return 'CT07';
    }

    public static function dichVu()
    {
        return 'CT2025';
    }

    public static function bang()
    {
        return 'ctdt_ct07';
    }

    public static function model()
    {
        return CtdtCt07::class;
    }

    public static function tenTab()
    {
        return 'Nghỉ việc hưởng BHXH';
    }

    public static function truong()
    {
        return [
            'MA_CT'                => 'ma_ct',
            'MAU_SO'               => 'mau_so',
            'SO_SERI'              => 'so_seri',
            'SO_KCB'               => 'so_kcb',
            'MA_BHXH'              => 'ma_bhxh',
            'MA_THE'               => 'ma_the',
            'HO_TEN'               => 'ho_ten',
            'NGAY_SINH'            => 'ngay_sinh',
            'GIOI_TINH'            => 'gioi_tinh',
            'DON_VI'               => 'don_vi',
            'CHANDOAN_DIEUTRI'     => 'chandoan_dieutri',
            'TU_NGAY'              => 'tu_ngay',
            'DEN_NGAY'             => 'den_ngay',
            'HO_TEN_CHA'           => 'ho_ten_cha',
            'HO_TEN_ME'            => 'ho_ten_me',
            'THU_TRUONG_DV'        => 'thu_truong_dv',
            'MA_CCHN'              => 'ma_cchn',
            'TEN_NGUOI_HANH_NGHE'  => 'ten_nguoi_hanh_nghe',
            'NGAY_CHUNG_TU'        => 'ngay_chung_tu',
            'TEKT'                 => 'tekt',
            'LOAI_GIAYTO'          => 'loai_giayto',
            'SO_CCCD'              => 'so_cccd',
            'NGAYCAP_CCCD'         => 'ngaycap_cccd',
            'NOICAP_CCCD'          => 'noicap_cccd',
            'NGAY_KCB'             => 'ngay_kcb',
            'BENH_ICD10_ID'        => 'benh_icd10_id',
            'BENH_ICD10_TEN'       => 'benh_icd10_ten',
        ];
    }

    public static function maChungTu(\SimpleXMLElement $xml)
    {
        return null;
    }

    public static function rutGon(\SimpleXMLElement $xml)
    {
        return [
            'ma_the'    => DocThe::chuoi($xml, 'MA_THE'),
            'ho_ten'    => DocThe::chuoi($xml, 'HO_TEN'),
            'ngay_sinh' => DocThe::chuoi($xml, 'NGAY_SINH'),
            'ngay_vao'  => DocThe::chuoi($xml, 'TU_NGAY'),
            'ngay_ra'   => DocThe::chuoi($xml, 'DEN_NGAY'),
        ];
    }
}
```

- [ ] **Step 6: Thêm ba loại vào registry**

Trong `app/Services/Ctdt/CtdtLoaiRegistry.php`, thêm `use` và ba dòng vào `tatCa()`:

```php
use App\Services\Ctdt\Loai\Ct03;
use App\Services\Ctdt\Loai\Ct04;
use App\Services\Ctdt\Loai\Ct06;
use App\Services\Ctdt\Loai\Ct07;
```

```php
    public static function tatCa()
    {
        return [
            'CT03' => Ct03::class,
            'CT04' => Ct04::class,
            'CT06' => Ct06::class,
            'CT07' => Ct07::class,
        ];
    }
```

- [ ] **Step 7: Chạy test để xác nhận xanh**

```bash
php vendor/bin/phpunit tests/Unit/Ctdt/CtdtLoaiTt25Test.php tests/Unit/Ctdt/CtdtLoaiRegistryTest.php
```

Kỳ vọng: `OK (15 tests)`.

- [ ] **Step 8: Commit**

```bash
git add app/Services/Ctdt tests/Unit/Ctdt/CtdtLoaiTt25Test.php
git commit -m "feat(ctdt): ba loai CT04, CT06, CT07"
```

---

## Task 6: Ba loại lệch tên thẻ — nội trú, vô sinh, sức khỏe mẹ

**Files:**
- Create: `app/Services/Ctdt/Loai/DieuTriNoiTru.php`
- Create: `app/Services/Ctdt/Loai/DieuTriVoSinh.php`
- Create: `app/Services/Ctdt/Loai/SucKhoeMe.php`
- Modify: `app/Services/Ctdt/CtdtLoaiRegistry.php` (thêm ba dòng vào `tatCa()`)
- Test: `tests/Unit/Ctdt/CtdtLoaiLechTenTest.php`

**Interfaces:**
- Consumes: `LoaiChungTu`, `DocThe`, `CtdtLoaiRegistry` (Task 4); model `CtdtDieuTriNoiTru`, `CtdtDieuTriVoSinh`, `CtdtSucKhoeMe` (Task 3)
- Produces: `App\Services\Ctdt\Loai\DieuTriNoiTru`, `DieuTriVoSinh`, `SucKhoeMe`

- [ ] **Step 1: Viết test đỏ**

Tạo `tests/Unit/Ctdt/CtdtLoaiLechTenTest.php`:

```php
<?php

namespace Tests\Unit\Ctdt;

use Tests\TestCase;
use App\Services\Ctdt\CtdtLoaiRegistry;
use App\Services\Ctdt\Loai\DieuTriNoiTru;
use App\Services\Ctdt\Loai\DieuTriVoSinh;
use App\Services\Ctdt\Loai\SucKhoeMe;

/**
 * BAY CHINH CUA CA DAC TA: ba loai nay co gia tri LOAIHOSO KHAC ten the goc ben trong
 * base64. Neu lay ten the goc lam khoa tra thi chung roi vao nhanh "loai la" IM LANG.
 */
class CtdtLoaiLechTenTest extends TestCase
{
    public function baLoaiLech()
    {
        return [
            ['GIAYDIEUTRINOITRU', 'CTGiayDieuTriNoiTru', DieuTriNoiTru::class, 'ctdt_dieu_tri_noi_tru', 36],
            ['GIAYDIEUTRIVOSINH', 'CTGiayDieuTriVoSinh', DieuTriVoSinh::class, 'ctdt_dieu_tri_vo_sinh', 29],
            ['GIAYSUCKHOEME',     'CTGiaySucKhoeMe',     SucKhoeMe::class,     'ctdt_suc_khoe_me', 29],
        ];
    }

    /** @test */
    public function ba_loai_khai_the_goc_KHAC_gia_tri_loai_ho_so()
    {
        foreach ($this->baLoaiLech() as list($loaiHoSo, $theGoc, $lop, $bang, $soTruong)) {
            $this->assertSame($loaiHoSo, $lop::maLoaiHoSo());
            $this->assertSame($theGoc, $lop::theGoc());
            $this->assertNotSame($lop::maLoaiHoSo(), $lop::theGoc(),
                $loaiHoSo . ': the goc phai KHAC gia tri LOAIHOSO');
            $this->assertSame('CT2025', $lop::dichVu());
            $this->assertSame($bang, $lop::bang());
            $this->assertCount($soTruong, $lop::truong(), $loaiHoSo . ': sai so truong');
        }
    }

    /** @test */
    public function doi_chieu_cheo_chap_nhan_the_goc_dung()
    {
        foreach ($this->baLoaiLech() as list($loaiHoSo, $theGoc, $_lop, $_bang, $_so)) {
            CtdtLoaiRegistry::xacNhanTheGoc($loaiHoSo, $theGoc);
        }

        $this->assertTrue(true, 'The goc dung thi khong duoc nem');
    }

    /** @test */
    public function doi_chieu_cheo_bat_duoc_khi_noi_dung_la_ten_loai_ho_so()
    {
        // Loi de xay ra nhat: phan mem sinh XML dat ten the goc bang chinh gia tri
        // LOAIHOSO. Phai bat, khong duoc nap im lang.
        $this->expectException(\RuntimeException::class);

        CtdtLoaiRegistry::xacNhanTheGoc('GIAYDIEUTRINOITRU', 'GIAYDIEUTRINOITRU');
    }

    /** @test */
    public function ba_loai_deu_lay_ma_chung_tu_tu_ma_yte()
    {
        foreach ($this->baLoaiLech() as list($_loai, $theGoc, $lop, $_bang, $_so)) {
            $xml = simplexml_load_string('<' . $theGoc . '><MA_YTE>YT777</MA_YTE></' . $theGoc . '>');

            $this->assertSame('YT777', $lop::maChungTu($xml));
        }
    }

    /** @test */
    public function noi_tru_giu_ten_the_khong_deu_theo_dac_ta()
    {
        $truong = DieuTriNoiTru::truong();

        // MA_DAN_TOC co gach duoi, khac CT03 (MA_DANTOC). BENH_ICD10_MA la MA khong phai ID.
        $this->assertSame('ma_dan_toc', $truong['MA_DAN_TOC']);
        $this->assertSame('benh_icd10_ma', $truong['BENH_ICD10_MA']);
        $this->assertArrayNotHasKey('MA_DANTOC', $truong);
        $this->assertArrayNotHasKey('BENH_ICD10_ID', $truong);
    }

    /** @test */
    public function vo_sinh_va_suc_khoe_me_khong_co_gioi_tinh()
    {
        // Ca hai loai deu chi ap dung cho lao dong nu nen dac ta khong khai GIOI_TINH.
        // Cot rut gon van phai chay duoc, khong duoc nem vi thieu the.
        foreach ([DieuTriVoSinh::class, SucKhoeMe::class] as $lop) {
            $this->assertArrayNotHasKey('GIOI_TINH', $lop::truong());
        }
    }

    /** @test */
    public function rut_gon_chay_duoc_ca_khi_thieu_the()
    {
        $xml = simplexml_load_string('<CTGiaySucKhoeMe><HO_TEN>Test</HO_TEN></CTGiaySucKhoeMe>');

        $rutGon = SucKhoeMe::rutGon($xml);

        $this->assertSame('Test', $rutGon['ho_ten']);
        $this->assertNull($rutGon['ma_the']);
        $this->assertNull($rutGon['ngay_vao']);
    }
}
```

- [ ] **Step 2: Chạy test để xác nhận đỏ**

```bash
php vendor/bin/phpunit tests/Unit/Ctdt/CtdtLoaiLechTenTest.php
```

Kỳ vọng: đỏ — `Class 'App\Services\Ctdt\Loai\DieuTriNoiTru' not found`.

- [ ] **Step 3: Viết lớp `DieuTriNoiTru`**

Tạo `app/Services/Ctdt/Loai/DieuTriNoiTru.php`:

```php
<?php

namespace App\Services\Ctdt\Loai;

use App\Models\BHYT\Ctdt\CtdtDieuTriNoiTru;

/**
 * Giay xac nhan qua trinh dieu tri noi tru (Mau so 06 - TT25).
 *
 * LECH TEN: LOAIHOSO la 'GIAYDIEUTRINOITRU' nhung the goc trong base64 la
 * '<CTGiayDieuTriNoiTru>'. Khai ca hai de registry doi chieu cheo duoc.
 */
class DieuTriNoiTru implements LoaiChungTu
{
    public static function maLoaiHoSo()
    {
        return 'GIAYDIEUTRINOITRU';
    }

    public static function theGoc()
    {
        return 'CTGiayDieuTriNoiTru';
    }

    public static function dichVu()
    {
        return 'CT2025';
    }

    public static function bang()
    {
        return 'ctdt_dieu_tri_noi_tru';
    }

    public static function model()
    {
        return CtdtDieuTriNoiTru::class;
    }

    public static function tenTab()
    {
        return 'Điều trị nội trú';
    }

    public static function truong()
    {
        return [
            'SO_LUU_TRU'               => 'so_luu_tru',
            'MA_YTE'                   => 'ma_yte',
            'MA_BHXH'                  => 'ma_bhxh',
            'MA_THE'                   => 'ma_the',
            'HO_TEN'                   => 'ho_ten',
            'NGAY_SINH'                => 'ngay_sinh',
            'GIOI_TINH'                => 'gioi_tinh',
            'MA_KHOA'                  => 'ma_khoa',
            'TEN_DAN_TOC'              => 'ten_dan_toc',
            'MA_DAN_TOC'               => 'ma_dan_toc',
            'NGHE_NGHIEP'              => 'nghe_nghiep',
            'DIA_CHI'                  => 'dia_chi',
            'NGAY_VAO'                 => 'ngay_vao',
            'NGAY_RA'                  => 'ngay_ra',
            'CHAN_DOAN'                => 'chan_doan',
            'PP_DIEUTRI'               => 'pp_dieutri',
            'MO_TA'                    => 'mo_ta',
            'GHI_CHU'                  => 'ghi_chu',
            'DAI_DIEN_DVI'             => 'dai_dien_dvi',
            'MA_CCHN_BS'               => 'ma_cchn_bs',
            'TEN_BS'                   => 'ten_bs',
            'LOAI_GIAYTO'              => 'loai_giayto',
            'SO_CCCD'                  => 'so_cccd',
            'NGAYCAP_CCCD'             => 'ngaycap_cccd',
            'NOICAP_CCCD'              => 'noicap_cccd',
            'BENH_ICD10_MA'            => 'benh_icd10_ma',
            'BENH_ICD10_TEN'           => 'benh_icd10_ten',
            'MA_CT'                    => 'ma_ct',
            'NGAY_CT'                  => 'ngay_ct',
            'SO_SERI'                  => 'so_seri',
            'TUOI_THAI'                => 'tuoi_thai',
            'LOAI_PHUONG_PHAP'         => 'loai_phuong_phap',
            'LOAI_PP_DIEU_TRI_VOSINH'  => 'loai_pp_dieu_tri_vosinh',
            'NGAY_DINH_CHI_THAINGHEN'  => 'ngay_dinh_chi_thainghen',
            'IS_NGHIDUONGTHAI'         => 'is_nghiduongthai',
            'SO_NGAY_NGHIDUONGTHAI'    => 'so_ngay_nghiduongthai',
        ];
    }

    public static function maChungTu(\SimpleXMLElement $xml)
    {
        return DocThe::chuoi($xml, 'MA_YTE');
    }

    public static function rutGon(\SimpleXMLElement $xml)
    {
        return [
            'ma_the'    => DocThe::chuoi($xml, 'MA_THE'),
            'ho_ten'    => DocThe::chuoi($xml, 'HO_TEN'),
            'ngay_sinh' => DocThe::chuoi($xml, 'NGAY_SINH'),
            'ngay_vao'  => DocThe::chuoi($xml, 'NGAY_VAO'),
            'ngay_ra'   => DocThe::chuoi($xml, 'NGAY_RA'),
        ];
    }
}
```

- [ ] **Step 4: Viết lớp `DieuTriVoSinh`**

Tạo `app/Services/Ctdt/Loai/DieuTriVoSinh.php`:

```php
<?php

namespace App\Services\Ctdt\Loai;

use App\Models\BHYT\Ctdt\CtdtDieuTriVoSinh;

/**
 * Giay xac nhan qua trinh dieu tri vo sinh cua lao dong nu (Mau so 09 - TT25).
 * LECH TEN: LOAIHOSO 'GIAYDIEUTRIVOSINH' vs the goc '<CTGiayDieuTriVoSinh>'.
 * Dac ta KHONG khai GIOI_TINH cho loai nay.
 */
class DieuTriVoSinh implements LoaiChungTu
{
    public static function maLoaiHoSo()
    {
        return 'GIAYDIEUTRIVOSINH';
    }

    public static function theGoc()
    {
        return 'CTGiayDieuTriVoSinh';
    }

    public static function dichVu()
    {
        return 'CT2025';
    }

    public static function bang()
    {
        return 'ctdt_dieu_tri_vo_sinh';
    }

    public static function model()
    {
        return CtdtDieuTriVoSinh::class;
    }

    public static function tenTab()
    {
        return 'Điều trị vô sinh';
    }

    public static function truong()
    {
        return [
            'SO_LUU_TRU'       => 'so_luu_tru',
            'MA_YTE'           => 'ma_yte',
            'MA_BHXH'          => 'ma_bhxh',
            'MA_THE'           => 'ma_the',
            'HO_TEN'           => 'ho_ten',
            'NGAY_SINH'        => 'ngay_sinh',
            'MA_KHOA'          => 'ma_khoa',
            'MA_TINHCUTRU'     => 'ma_tinhcutru',
            'MA_XACUTRU'       => 'ma_xacutru',
            'NGHE_NGHIEP'      => 'nghe_nghiep',
            'DIA_CHI'          => 'dia_chi',
            'NGAY_VAO'         => 'ngay_vao',
            'NGAY_RA'          => 'ngay_ra',
            'CHAN_DOAN'        => 'chan_doan',
            'PP_DIEUTRI'       => 'pp_dieutri',
            'GHI_CHU'          => 'ghi_chu',
            'DAI_DIEN_DVI'     => 'dai_dien_dvi',
            'MA_CCHN_BS'       => 'ma_cchn_bs',
            'TEN_BS'           => 'ten_bs',
            'LOAI_GIAYTO'      => 'loai_giayto',
            'SO_CCCD'          => 'so_cccd',
            'NGAYCAP_CCCD'     => 'ngaycap_cccd',
            'NOICAP_CCCD'      => 'noicap_cccd',
            'BENH_ICD10_MA'    => 'benh_icd10_ma',
            'BENH_ICD10_TEN'   => 'benh_icd10_ten',
            'MA_CT'            => 'ma_ct',
            'NGAY_CT'          => 'ngay_ct',
            'SO_SERI'          => 'so_seri',
            'LOAI_PHUONG_PHAP' => 'loai_phuong_phap',
        ];
    }

    public static function maChungTu(\SimpleXMLElement $xml)
    {
        return DocThe::chuoi($xml, 'MA_YTE');
    }

    public static function rutGon(\SimpleXMLElement $xml)
    {
        return [
            'ma_the'    => DocThe::chuoi($xml, 'MA_THE'),
            'ho_ten'    => DocThe::chuoi($xml, 'HO_TEN'),
            'ngay_sinh' => DocThe::chuoi($xml, 'NGAY_SINH'),
            'ngay_vao'  => DocThe::chuoi($xml, 'NGAY_VAO'),
            'ngay_ra'   => DocThe::chuoi($xml, 'NGAY_RA'),
        ];
    }
}
```

- [ ] **Step 5: Viết lớp `SucKhoeMe`**

Tạo `app/Services/Ctdt/Loai/SucKhoeMe.php`:

```php
<?php

namespace App\Services\Ctdt\Loai;

use App\Models\BHYT\Ctdt\CtdtSucKhoeMe;

/**
 * Giay xac nhan nguoi me khong du suc khoe cham soc con (Mau so 10 - TT25).
 * LECH TEN: LOAIHOSO 'GIAYSUCKHOEME' vs the goc '<CTGiaySucKhoeMe>'.
 */
class SucKhoeMe implements LoaiChungTu
{
    public static function maLoaiHoSo()
    {
        return 'GIAYSUCKHOEME';
    }

    public static function theGoc()
    {
        return 'CTGiaySucKhoeMe';
    }

    public static function dichVu()
    {
        return 'CT2025';
    }

    public static function bang()
    {
        return 'ctdt_suc_khoe_me';
    }

    public static function model()
    {
        return CtdtSucKhoeMe::class;
    }

    public static function tenTab()
    {
        return 'Sức khỏe người mẹ';
    }

    public static function truong()
    {
        return [
            'SO_LUU_TRU'           => 'so_luu_tru',
            'MA_YTE'               => 'ma_yte',
            'MA_BHXH'              => 'ma_bhxh',
            'MA_THE'               => 'ma_the',
            'HO_TEN'               => 'ho_ten',
            'NGAY_SINH'            => 'ngay_sinh',
            'MA_KHOA'              => 'ma_khoa',
            'MA_TINHCUTRU'         => 'ma_tinhcutru',
            'MA_XACUTRU'           => 'ma_xacutru',
            'NGHE_NGHIEP'          => 'nghe_nghiep',
            'DIA_CHI'              => 'dia_chi',
            'NGAY_VAO'             => 'ngay_vao',
            'NGAY_RA'              => 'ngay_ra',
            'CHAN_DOAN'            => 'chan_doan',
            'PP_DIEUTRI'           => 'pp_dieutri',
            'KET_LUAN'             => 'ket_luan',
            'TINHTRANGBENHHIENTAI' => 'tinhtrangbenhhientai',
            'DAI_DIEN_DVI'         => 'dai_dien_dvi',
            'MA_CCHN_BS'           => 'ma_cchn_bs',
            'TEN_BS'               => 'ten_bs',
            'LOAI_GIAYTO'          => 'loai_giayto',
            'SO_CCCD'              => 'so_cccd',
            'NGAYCAP_CCCD'         => 'ngaycap_cccd',
            'NOICAP_CCCD'          => 'noicap_cccd',
            'BENH_ICD10_MA'        => 'benh_icd10_ma',
            'BENH_ICD10_TEN'       => 'benh_icd10_ten',
            'MA_CT'                => 'ma_ct',
            'NGAY_CT'              => 'ngay_ct',
            'SO_SERI'              => 'so_seri',
        ];
    }

    public static function maChungTu(\SimpleXMLElement $xml)
    {
        return DocThe::chuoi($xml, 'MA_YTE');
    }

    public static function rutGon(\SimpleXMLElement $xml)
    {
        return [
            'ma_the'    => DocThe::chuoi($xml, 'MA_THE'),
            'ho_ten'    => DocThe::chuoi($xml, 'HO_TEN'),
            'ngay_sinh' => DocThe::chuoi($xml, 'NGAY_SINH'),
            'ngay_vao'  => DocThe::chuoi($xml, 'NGAY_VAO'),
            'ngay_ra'   => DocThe::chuoi($xml, 'NGAY_RA'),
        ];
    }
}
```

- [ ] **Step 6: Thêm ba loại vào registry**

Trong `app/Services/Ctdt/CtdtLoaiRegistry.php`, thêm `use` cho `DieuTriNoiTru`, `DieuTriVoSinh`, `SucKhoeMe` và ba dòng vào `tatCa()`:

```php
    public static function tatCa()
    {
        return [
            'CT03'              => Ct03::class,
            'CT04'              => Ct04::class,
            'CT06'              => Ct06::class,
            'CT07'              => Ct07::class,
            'GIAYDIEUTRINOITRU' => DieuTriNoiTru::class,
            'GIAYDIEUTRIVOSINH' => DieuTriVoSinh::class,
            'GIAYSUCKHOEME'     => SucKhoeMe::class,
        ];
    }
```

- [ ] **Step 7: Chạy test để xác nhận xanh**

```bash
php vendor/bin/phpunit tests/Unit/Ctdt/CtdtLoaiLechTenTest.php
```

Kỳ vọng: `OK (7 tests)`.

- [ ] **Step 8: Commit**

```bash
git add app/Services/Ctdt tests/Unit/Ctdt/CtdtLoaiLechTenTest.php
git commit -m "feat(ctdt): ba loai lech ten the - noi tru, vo sinh, suc khoe me"
```

---

## Task 7: Hai dịch vụ còn lại — `GiayBaoTu`, `GiayChungSinh`

**Files:**
- Create: `app/Services/Ctdt/Loai/GiayBaoTu.php`
- Create: `app/Services/Ctdt/Loai/GiayChungSinh.php`
- Modify: `app/Services/Ctdt/CtdtLoaiRegistry.php` (thêm hai dòng vào `tatCa()`)
- Test: `tests/Unit/Ctdt/CtdtLoaiGbtGcsTest.php`

**Interfaces:**
- Consumes: `LoaiChungTu`, `DocThe`, `CtdtLoaiRegistry` (Task 4); model `CtdtGiayBaoTu`, `CtdtGiayChungSinh` (Task 3)
- Produces: `App\Services\Ctdt\Loai\GiayBaoTu` (dịch vụ `GBT`), `GiayChungSinh` (dịch vụ `GCS`)

- [ ] **Step 1: Viết test đỏ**

Tạo `tests/Unit/Ctdt/CtdtLoaiGbtGcsTest.php`:

```php
<?php

namespace Tests\Unit\Ctdt;

use Tests\TestCase;
use App\Services\Ctdt\CtdtLoaiRegistry;
use App\Services\Ctdt\Loai\GiayBaoTu;
use App\Services\Ctdt\Loai\GiayChungSinh;

/**
 * Hai loai thuoc HAI dich vu rieng (loaiHs 60 va 61), khac bay loai TT25 (loaiHs 39).
 */
class CtdtLoaiGbtGcsTest extends TestCase
{
    /** @test */
    public function giay_bao_tu_thuoc_dich_vu_gbt()
    {
        $this->assertSame('GIAYBAOTU', GiayBaoTu::maLoaiHoSo());
        $this->assertSame('GIAYBAOTU', GiayBaoTu::theGoc());
        $this->assertSame('GBT', GiayBaoTu::dichVu());
        $this->assertSame('ctdt_giay_bao_tu', GiayBaoTu::bang());
        $this->assertCount(38, GiayBaoTu::truong());
    }

    /** @test */
    public function giay_chung_sinh_thuoc_dich_vu_gcs()
    {
        $this->assertSame('GIAYCHUNGSINH', GiayChungSinh::maLoaiHoSo());
        $this->assertSame('GIAYCHUNGSINH', GiayChungSinh::theGoc());
        $this->assertSame('GCS', GiayChungSinh::dichVu());
        $this->assertSame('ctdt_giay_chung_sinh', GiayChungSinh::bang());
        $this->assertCount(69, GiayChungSinh::truong());
    }

    /** @test */
    public function khoa_nghiep_vu_lay_tu_ma_gbt_va_ma_gcs()
    {
        // Hai loai nay LUON co khoa nghiep vu, khac CT04/CT06/CT07. Nghia la ho so
        // GBT/GCS luon ghi de duoc khi nap lai.
        $gbt = simplexml_load_string('<GIAYBAOTU><MA_GBT>00002.GBT.XXXX.25</MA_GBT></GIAYBAOTU>');
        $gcs = simplexml_load_string('<GIAYCHUNGSINH><MA_GCS>00005.GCS.XXXXX.25</MA_GCS></GIAYCHUNGSINH>');

        $this->assertSame('00002.GBT.XXXX.25', GiayBaoTu::maChungTu($gbt));
        $this->assertSame('00005.GCS.XXXXX.25', GiayChungSinh::maChungTu($gcs));
    }

    /** @test */
    public function giay_bao_tu_rut_gon_dung_ngay_gio_vv_va_ngay_tv()
    {
        // Giay bao tu khong co NGAY_RA. Ngay tu vong dong vai tro ket thuc dot dieu tri.
        $xml = simplexml_load_string(
            '<GIAYBAOTU><MA_THE>GD497</MA_THE><HO_TEN>Nguyen Van Test</HO_TEN>'
            . '<NGAY_SINH>20220101</NGAY_SINH><NGAYGIO_VV>202510070100</NGAYGIO_VV>'
            . '<NGAY_TV>202510070200</NGAY_TV></GIAYBAOTU>'
        );

        $rutGon = GiayBaoTu::rutGon($xml);

        $this->assertSame('202510070100', $rutGon['ngay_vao']);
        $this->assertSame('202510070200', $rutGon['ngay_ra']);
    }

    /** @test */
    public function giay_chung_sinh_rut_gon_lay_thong_tin_NGUOI_ME_khong_phai_con()
    {
        // Man danh sach tra cuu theo nguoi co the BHYT - la nguoi me, khong phai dua con.
        $xml = simplexml_load_string(
            '<GIAYCHUNGSINH><MA_THE_NND>GD123</MA_THE_NND><HOTEN_NND>Pham Minh Test</HOTEN_NND>'
            . '<NGAYSINH_NND>20000220</NGAYSINH_NND><TEN_CON>Test Test</TEN_CON>'
            . '<NGAY_SINH_CON>20251021000000</NGAY_SINH_CON></GIAYCHUNGSINH>'
        );

        $rutGon = GiayChungSinh::rutGon($xml);

        $this->assertSame('GD123', $rutGon['ma_the']);
        $this->assertSame('Pham Minh Test', $rutGon['ho_ten']);
        $this->assertSame('20000220', $rutGon['ngay_sinh']);
        $this->assertSame('20251021000000', $rutGon['ngay_vao'], 'Ngay sinh con dai 14 ky tu');
    }

    /** @test */
    public function giay_chung_sinh_phan_biet_ba_nhom_hau_to()
    {
        $truong = GiayChungSinh::truong();

        $this->assertSame('hoten_nnd', $truong['HOTEN_NND']);
        $this->assertSame('hoten_mth', $truong['HOTEN_MTH']);
        $this->assertSame('ho_ten_cha_mth', $truong['HO_TEN_CHA_MTH']);
        $this->assertSame('so_cccd_cha_nnd', $truong['SO_CCCD_CHA_NND']);
    }

    /** @test */
    public function registry_du_chin_loai_va_chia_dung_ba_dich_vu()
    {
        $this->assertCount(9, CtdtLoaiRegistry::tatCa(), 'Phai co dung chin loai chung tu');

        $this->assertCount(7, CtdtLoaiRegistry::cuaDichVu('CT2025'), 'CT2025 co bay loai');
        $this->assertCount(1, CtdtLoaiRegistry::cuaDichVu('GBT'));
        $this->assertCount(1, CtdtLoaiRegistry::cuaDichVu('GCS'));
    }
}
```

- [ ] **Step 2: Chạy test để xác nhận đỏ**

```bash
php vendor/bin/phpunit tests/Unit/Ctdt/CtdtLoaiGbtGcsTest.php
```

Kỳ vọng: đỏ — `Class 'App\Services\Ctdt\Loai\GiayBaoTu' not found`.

- [ ] **Step 3: Viết lớp `GiayBaoTu`**

Tạo `app/Services/Ctdt/Loai/GiayBaoTu.php`:

```php
<?php

namespace App\Services\Ctdt\Loai;

use App\Models\BHYT\Ctdt\CtdtGiayBaoTu;

/**
 * Giay bao tu - dich vu rieng, loaiHs=60, goi HSDLGBT.
 *
 * Khong co NGAY_RA: NGAY_TV (ngay tu vong) dong vai tro ket thuc dot dieu tri trong
 * cot rut gon, de man danh sach loc theo mot bo cot duy nhat cho ca chin loai.
 */
class GiayBaoTu implements LoaiChungTu
{
    public static function maLoaiHoSo()
    {
        return 'GIAYBAOTU';
    }

    public static function theGoc()
    {
        return 'GIAYBAOTU';
    }

    public static function dichVu()
    {
        return 'GBT';
    }

    public static function bang()
    {
        return 'ctdt_giay_bao_tu';
    }

    public static function model()
    {
        return CtdtGiayBaoTu::class;
    }

    public static function tenTab()
    {
        return 'Giấy báo tử';
    }

    public static function truong()
    {
        return [
            'MA_GBT'             => 'ma_gbt',
            'MA_BN'              => 'ma_bn',
            'MA_HSBA'            => 'ma_hsba',
            'HO_TEN'             => 'ho_ten',
            'NGAY_SINH'          => 'ngay_sinh',
            'GIOI_TINH'          => 'gioi_tinh',
            'MA_THE'             => 'ma_the',
            'MA_DANTOC'          => 'ma_dantoc',
            'MA_QUOCTICH'        => 'ma_quoctich',
            'DCHI_THUONGTRU'     => 'dchi_thuongtru',
            'MATINH_THUONGTRU'   => 'matinh_thuongtru',
            'MAHUYEN_THUONGTRU'  => 'mahuyen_thuongtru',
            'MAXA_THUONGTRU'     => 'maxa_thuongtru',
            'DCHI_HIENTAI'       => 'dchi_hientai',
            'MATINH_HIENTAI'     => 'matinh_hientai',
            'MAHUYEN_HIENTAI'    => 'mahuyen_hientai',
            'MAXA_HIENTAI'       => 'maxa_hientai',
            'LOAI_GIAYTO'        => 'loai_giayto',
            'SO_GIAYTO'          => 'so_giayto',
            'NGAY_CAP'           => 'ngay_cap',
            'NOI_CAP'            => 'noi_cap',
            'NGAYGIO_VV'         => 'ngaygio_vv',
            'NGAY_TV'            => 'ngay_tv',
            'TINH_TRANG_TV'      => 'tinh_trang_tv',
            'NGUYENNHAN_TV'      => 'nguyennhan_tv',
            'NGUOI_GHIGIAY'      => 'nguoi_ghigiay',
            'NGUOI_THANTHICH'    => 'nguoi_thanthich',
            'TTRUONG_DVI'        => 'ttruong_dvi',
            'SO_BAOTU'           => 'so_baotu',
            'QUYEN_SO'           => 'quyen_so',
            'NGAY_CAPGIAYBT'     => 'ngay_capgiaybt',
            'SO_BAOTU_BD'        => 'so_baotu_bd',
            'QUYEN_SO_BD'        => 'quyen_so_bd',
            'MACSKCB'            => 'macskcb',
            'DIACHI_CSKCB'       => 'diachi_cskcb',
            'MA_BHXH'            => 'ma_bhxh',
            'BENH_ICD10_ID'      => 'benh_icd10_id',
            'BENH_ICD10_TEN'     => 'benh_icd10_ten',
        ];
    }

    public static function maChungTu(\SimpleXMLElement $xml)
    {
        return DocThe::chuoi($xml, 'MA_GBT');
    }

    public static function rutGon(\SimpleXMLElement $xml)
    {
        return [
            'ma_the'    => DocThe::chuoi($xml, 'MA_THE'),
            'ho_ten'    => DocThe::chuoi($xml, 'HO_TEN'),
            'ngay_sinh' => DocThe::chuoi($xml, 'NGAY_SINH'),
            'ngay_vao'  => DocThe::chuoi($xml, 'NGAYGIO_VV'),
            'ngay_ra'   => DocThe::chuoi($xml, 'NGAY_TV'),
        ];
    }
}
```

- [ ] **Step 4: Viết lớp `GiayChungSinh`**

Tạo `app/Services/Ctdt/Loai/GiayChungSinh.php`:

```php
<?php

namespace App\Services\Ctdt\Loai;

use App\Models\BHYT\Ctdt\CtdtGiayChungSinh;

/**
 * Giay chung sinh - dich vu rieng, loaiHs=61, goi HSDLGCS (TT22/2025).
 *
 * BA nhom nguoi dung hau to khac nhau:
 *   _NND     nguoi de   |   _MTH  me thay the   |   _CHA_MTH  cha cua me thay the
 *   _CHA_NND cha cua nguoi de
 *
 * Cot rut gon lay thong tin NGUOI ME (_NND) chu khong phai dua con: man danh sach tra
 * cuu theo nguoi co the BHYT.
 */
class GiayChungSinh implements LoaiChungTu
{
    public static function maLoaiHoSo()
    {
        return 'GIAYCHUNGSINH';
    }

    public static function theGoc()
    {
        return 'GIAYCHUNGSINH';
    }

    public static function dichVu()
    {
        return 'GCS';
    }

    public static function bang()
    {
        return 'ctdt_giay_chung_sinh';
    }

    public static function model()
    {
        return CtdtGiayChungSinh::class;
    }

    public static function tenTab()
    {
        return 'Giấy chứng sinh';
    }

    public static function truong()
    {
        return [
            // Dinh danh va nguoi de
            'MA_GCS'                => 'ma_gcs',
            'MA_BN'                 => 'ma_bn',
            'MA_CT'                 => 'ma_ct',
            'SO_SERI'               => 'so_seri',
            'MA_BHXH_NND'           => 'ma_bhxh_nnd',
            'MA_THE_NND'            => 'ma_the_nnd',
            'HOTEN_NND'             => 'hoten_nnd',
            'NGAYSINH_NND'          => 'ngaysinh_nnd',
            'MA_DANTOC_NND'         => 'ma_dantoc_nnd',
            'MA_QUOCTICH_NND'       => 'ma_quoctich_nnd',
            'LOAI_GIAYTO_NND'       => 'loai_giayto_nnd',
            'SO_CCCD_NND'           => 'so_cccd_nnd',
            'NGAYCAP_CCCD_NND'      => 'ngaycap_cccd_nnd',
            'NOICAP_CCCD_NND'       => 'noicap_cccd_nnd',
            'NOI_CU_TRU_NND'        => 'noi_cu_tru_nnd',
            'MATINH_CU_TRU'         => 'matinh_cu_tru',
            'MAHUYEN_CU_TRU'        => 'mahuyen_cu_tru',
            'MAXA_CU_TRU'           => 'maxa_cu_tru',
            'HO_TEN_CHA'            => 'ho_ten_cha',
            'MA_THE_TAM'            => 'ma_the_tam',
            // Thong tin con
            'TEN_CON'               => 'ten_con',
            'GIOI_TINH_CON'         => 'gioi_tinh_con',
            'SO_CON'                => 'so_con',
            'LAN_SINH'              => 'lan_sinh',
            'SO_CON_SONG'           => 'so_con_song',
            'CAN_NANG_CON'          => 'can_nang_con',
            'NGAY_SINH_CON'         => 'ngay_sinh_con',
            'NOI_SINH_CON'          => 'noi_sinh_con',
            'TINH_TRANG_CON'        => 'tinh_trang_con',
            'SINHCON_PHAUTHUAT'     => 'sinhcon_phauthuat',
            'SINHCON_DUOI32TUAN'    => 'sinhcon_duoi32tuan',
            'GHI_CHU'               => 'ghi_chu',
            // Nguoi lap phieu va don vi
            'NGUOI_DO_DE'           => 'nguoi_do_de',
            'NGUOI_GHI_PHIEU'       => 'nguoi_ghi_phieu',
            'MA_TTDV'               => 'ma_ttdv',
            'THU_TRUONG_DVI'        => 'thu_truong_dvi',
            'NGAY_CT'               => 'ngay_ct',
            'SO'                    => 'so',
            'QUYEN_SO'              => 'quyen_so',
            // Me thay the
            'MA_BHXH_MTH'           => 'ma_bhxh_mth',
            'MA_THE_MTH'            => 'ma_the_mth',
            'HOTEN_MTH'             => 'hoten_mth',
            'NGAYSINH_MTH'          => 'ngaysinh_mth',
            'MA_DANTOC_MTH'         => 'ma_dantoc_mth',
            'MA_QUOCTICH_MTH'       => 'ma_quoctich_mth',
            'LOAI_GIAYTO_MTH'       => 'loai_giayto_mth',
            'SO_CCCD_MTH'           => 'so_cccd_mth',
            'NGAYCAP_CCCD_MTH'      => 'ngaycap_cccd_mth',
            'NOICAP_CCCD_MTH'       => 'noicap_cccd_mth',
            'NOI_CU_TRU_MTH'        => 'noi_cu_tru_mth',
            'MATINH_CU_TRU_MTH'     => 'matinh_cu_tru_mth',
            'MAXA_CU_TRU_MTH'       => 'maxa_cu_tru_mth',
            'HO_TEN_CHA_MTH'        => 'ho_ten_cha_mth',
            'NGAYSINH_CHA_MTH'      => 'ngaysinh_cha_mth',
            'MA_DANTOC_CHA_MTH'     => 'ma_dantoc_cha_mth',
            'NOI_CU_TRU_CHA_MTH'    => 'noi_cu_tru_cha_mth',
            'MATINH_CU_TRU_CHA_MTH' => 'matinh_cu_tru_cha_mth',
            'MAXA_CU_TRU_CHA_MTH'   => 'maxa_cu_tru_cha_mth',
            'LOAI_GIAYTO_CHA_MTH'   => 'loai_giayto_cha_mth',
            'SO_CCCD_CHA_MTH'       => 'so_cccd_cha_mth',
            'NGAYCAP_CCCD_CHA_MTH'  => 'ngaycap_cccd_cha_mth',
            'NOICAP_CCCD_CHA_MTH'   => 'noicap_cccd_cha_mth',
            // Cha cua nguoi de
            'NGAYSINH_CHA_NND'      => 'ngaysinh_cha_nnd',
            'MA_DANTOC_CHA_NND'     => 'ma_dantoc_cha_nnd',
            'LOAI_GIAYTO_CHA_NND'   => 'loai_giayto_cha_nnd',
            'SO_CCCD_CHA_NND'       => 'so_cccd_cha_nnd',
            'NGAYCAP_CCCD_CHA_NND'  => 'ngaycap_cccd_cha_nnd',
            'NOICAP_CCCD_CHA_NND'   => 'noicap_cccd_cha_nnd',
            'CAP_LAN_DAU'           => 'cap_lan_dau',
        ];
    }

    public static function maChungTu(\SimpleXMLElement $xml)
    {
        return DocThe::chuoi($xml, 'MA_GCS');
    }

    public static function rutGon(\SimpleXMLElement $xml)
    {
        return [
            'ma_the'    => DocThe::chuoi($xml, 'MA_THE_NND'),
            'ho_ten'    => DocThe::chuoi($xml, 'HOTEN_NND'),
            'ngay_sinh' => DocThe::chuoi($xml, 'NGAYSINH_NND'),
            'ngay_vao'  => DocThe::chuoi($xml, 'NGAY_SINH_CON'),
            'ngay_ra'   => DocThe::chuoi($xml, 'NGAY_CT'),
        ];
    }
}
```

- [ ] **Step 5: Thêm hai loại vào registry**

Trong `app/Services/Ctdt/CtdtLoaiRegistry.php`, thêm `use` cho `GiayBaoTu`, `GiayChungSinh` và hai dòng vào `tatCa()`:

```php
    public static function tatCa()
    {
        return [
            'CT03'              => Ct03::class,
            'CT04'              => Ct04::class,
            'CT06'              => Ct06::class,
            'CT07'              => Ct07::class,
            'GIAYDIEUTRINOITRU' => DieuTriNoiTru::class,
            'GIAYDIEUTRIVOSINH' => DieuTriVoSinh::class,
            'GIAYSUCKHOEME'     => SucKhoeMe::class,
            'GIAYBAOTU'         => GiayBaoTu::class,
            'GIAYCHUNGSINH'     => GiayChungSinh::class,
        ];
    }
```

- [ ] **Step 6: Chạy test để xác nhận xanh**

```bash
php vendor/bin/phpunit tests/Unit/Ctdt/CtdtLoaiGbtGcsTest.php
```

Kỳ vọng: `OK (7 tests)`.

- [ ] **Step 7: Commit**

```bash
git add app/Services/Ctdt tests/Unit/Ctdt/CtdtLoaiGbtGcsTest.php
git commit -m "feat(ctdt): hai loai GiayBaoTu va GiayChungSinh"
```

---

## Task 8: Test toàn vẹn registry ↔ CSDL

**Files:**
- Test: `tests/Unit/Ctdt/CtdtToanVenTest.php`

**Interfaces:**
- Consumes: `CtdtLoaiRegistry` (Task 4-7), 9 model (Task 3), trait `DungBangCtdtSqlite` (Task 2)
- Produces: không có mã sản phẩm mới — đây là lưới an toàn cho toàn Giai đoạn 1

**Vì sao cần task này:** danh sách cột nằm **hai nơi** — mảng trong migration và `truong()` trong lớp loại. Hai nơi thì sẽ lệch nhau, đó là chuyện thời gian. Test này bắt chúng phải khớp, và bắt luôn `$fillable` của model.

- [ ] **Step 1: Viết test**

Tạo `tests/Unit/Ctdt/CtdtToanVenTest.php`:

```php
<?php

namespace Tests\Unit\Ctdt;

use Tests\TestCase;
use Tests\Support\DungBangCtdtSqlite;
use Illuminate\Support\Facades\Schema;
use App\Services\Ctdt\CtdtLoaiRegistry;

/**
 * Luoi an toan cho ca Giai doan 1.
 *
 * Danh sach cot nam HAI NOI: mang trong migration va truong() trong lop loai. Hai noi
 * thi se lech nhau - do la chuyen thoi gian, khong phai chuyen co hay khong. Test nay
 * bat chung phai khop, va bat luon $fillable cua model.
 */
class CtdtToanVenTest extends TestCase
{
    use DungBangCtdtSqlite;

    protected function setUp()
    {
        parent::setUp();
        $this->chuanBiBangCtdt();
    }

    /** @test */
    public function moi_lop_loai_cai_dung_interface()
    {
        foreach (CtdtLoaiRegistry::tatCa() as $loai => $lop) {
            $this->assertContains(
                \App\Services\Ctdt\Loai\LoaiChungTu::class,
                class_implements($lop),
                $lop . ' phai cai LoaiChungTu'
            );
        }
    }

    /** @test */
    public function khoa_registry_trung_voi_maLoaiHoSo_cua_lop()
    {
        // Lech nhau thi cho('CT03') tra ve lop tu xung la 'CT04' - va khong ai biet.
        foreach (CtdtLoaiRegistry::tatCa() as $loai => $lop) {
            $this->assertSame($loai, $lop::maLoaiHoSo(),
                'Khoa registry "' . $loai . '" lech voi maLoaiHoSo() cua ' . $lop);
        }
    }

    /** @test */
    public function moi_cot_khai_trong_truong_deu_ton_tai_trong_bang()
    {
        foreach (CtdtLoaiRegistry::tatCa() as $loai => $lop) {
            $bang = $lop::bang();

            $this->assertTrue(Schema::hasTable($bang), $loai . ': thieu bang ' . $bang);

            foreach ($lop::truong() as $the => $cot) {
                $this->assertTrue(Schema::hasColumn($bang, $cot),
                    $loai . ': the ' . $the . ' anh xa toi cot ' . $cot . ' khong co trong ' . $bang);
            }
        }
    }

    /** @test */
    public function moi_cot_du_lieu_trong_bang_deu_duoc_khai_trong_truong()
    {
        // Chieu nguoc lai: cot co trong bang ma khong co trong truong() nghia la mot the
        // PL02 se khong bao gio duoc nap - im lang, va chi lo ra khi BHXH doi soat thieu.
        $cotKhung = ['id', 'chung_tu_id', 'created_at', 'updated_at'];

        foreach (CtdtLoaiRegistry::tatCa() as $loai => $lop) {
            $cotTrongBang = array_diff(Schema::getColumnListing($lop::bang()), $cotKhung);
            $cotKhai = array_values($lop::truong());

            $thieu = array_diff($cotTrongBang, $cotKhai);

            $this->assertEmpty($thieu,
                $loai . ': cot co trong bang nhung khong khai trong truong(): ' . implode(', ', $thieu));
        }
    }

    /** @test */
    public function fillable_cua_model_phu_du_cac_cot_khai_trong_truong()
    {
        foreach (CtdtLoaiRegistry::tatCa() as $loai => $lop) {
            $tenLop = $lop::model();
            $model = new $tenLop();
            $fillable = $model->getFillable();

            $this->assertContains('chung_tu_id', $fillable, $loai . ': model thieu chung_tu_id');

            foreach ($lop::truong() as $the => $cot) {
                $this->assertContains($cot, $fillable,
                    $loai . ': model thieu ' . $cot . ' trong $fillable - create() se bo qua im lang');
            }
        }
    }

    /** @test */
    public function model_tro_dung_bang_ma_lop_loai_khai()
    {
        foreach (CtdtLoaiRegistry::tatCa() as $loai => $lop) {
            $tenLop = $lop::model();
            $model = new $tenLop();

            $this->assertSame($lop::bang(), $model->getTable(),
                $loai . ': model tro bang khac voi bang() cua lop loai');
        }
    }

    /** @test */
    public function moi_lop_loai_thuoc_mot_dich_vu_co_khai_trong_config()
    {
        $dichVuHopLe = array_keys(config('ctdt.dich_vu'));

        foreach (CtdtLoaiRegistry::tatCa() as $loai => $lop) {
            $this->assertContains($lop::dichVu(), $dichVuHopLe,
                $loai . ': dich vu "' . $lop::dichVu() . '" khong co trong config ctdt.dich_vu');
        }
    }

    /** @test */
    public function khong_hai_lop_nao_dung_chung_mot_bang()
    {
        $bang = [];

        foreach (CtdtLoaiRegistry::tatCa() as $loai => $lop) {
            $this->assertNotContains($lop::bang(), $bang,
                $loai . ': bang ' . $lop::bang() . ' da duoc mot loai khac dung');

            $bang[] = $lop::bang();
        }
    }

    /** @test */
    public function rut_gon_luon_tra_dung_nam_khoa()
    {
        // Man danh sach doc mot bo cot duy nhat cho ca chin loai. Thieu mot khoa la
        // Undefined index luc nap - va chi lo ra voi dung loai chung tu do.
        foreach (CtdtLoaiRegistry::tatCa() as $loai => $lop) {
            $xml = simplexml_load_string('<' . $lop::theGoc() . '/>');
            $rutGon = $lop::rutGon($xml);

            $this->assertSame(
                ['ma_the', 'ho_ten', 'ngay_sinh', 'ngay_vao', 'ngay_ra'],
                array_keys($rutGon),
                $loai . ': rutGon() phai tra dung nam khoa theo dung thu tu'
            );
        }
    }
}
```

- [ ] **Step 2: Chạy test**

```bash
php vendor/bin/phpunit tests/Unit/Ctdt/CtdtToanVenTest.php
```

Kỳ vọng: `OK (9 tests)`. Nếu đỏ, sửa nơi lệch — thường là `$fillable` của model thiếu cột, hoặc `truong()` sót một thẻ.

**Lưu ý cú pháp:** khởi tạo model phải viết hai dòng (`$tenLop = $lop::model(); $model = new $tenLop();`). Dạng một dòng `new ($lop::model())` cần PHP 8, còn môi trường này là PHP 7.4.

- [ ] **Step 3: Chạy toàn bộ test của module**

```bash
php vendor/bin/phpunit tests/Unit/Ctdt
```

Kỳ vọng: `OK (56 tests)`.

- [ ] **Step 4: Chạy toàn bộ Unit suite, đối chiếu baseline**

```bash
php vendor/bin/phpunit --testsuite Unit
```

Kỳ vọng: `Errors: 4, Failures: 7` — **đúng bằng baseline**. Nhiều hơn nghĩa là việc này làm hỏng thứ khác.

- [ ] **Step 5: Commit**

```bash
git add tests/Unit/Ctdt/CtdtToanVenTest.php
git commit -m "test(ctdt): luoi an toan toan ven registry - bang - model"
```

---

## Hoàn tất Giai đoạn 1

Sau Task 8, những thứ sau đã sẵn sàng cho Giai đoạn 2 (luồng nạp):

- `CtdtLoaiRegistry::cho($loaiHoSo)` → lớp loại; `xacNhanTheGoc()` bắt lệch tên thẻ.
- `$lop::truong()` → ánh xạ thẻ → cột, dùng trực tiếp để dựng mảng ghi CSDL.
- `$lop::maChungTu($xml)` → khóa nghiệp vụ, dùng để suy ra `ma_ho_so`.
- `$lop::rutGon($xml)` → năm cột rút gọn của `ctdt_chung_tu`.
- 12 bảng, 12 model, disk `exportCtdt`, `config/ctdt.php`.

**Chưa có và cố ý chưa có:** `CtdtGoiParser`, `CtdtImporter`, controller, view, job, `CtdtSubmitService`. Giai đoạn 1 không gọi mạng, không đọc tệp, không có giao diện — rủi ro bằng 0.

**Việc chạy migration trên môi trường thật:**

```bash
php artisan migrate --path=database/migrations
```

Migration của module này chỉ tạo bảng mới, không sửa bảng nào đang có, nên chạy được trên CSDL đang vận hành mà không ảnh hưởng XML3176.
