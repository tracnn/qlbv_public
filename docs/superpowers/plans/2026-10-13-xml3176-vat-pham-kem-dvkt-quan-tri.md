# Màn quản trị bảng tra "Vật phẩm kèm DVKT mẹ" — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Màn hình trong "Quản lý danh mục → BHYT" để xem/lọc/bật-tắt/ghi chú/xoá bảng tra vật phẩm → DVKT mẹ, thêm dòng tay có kiểm khớp XML, học lại chạy nền, xuất/nhập Excel và duyệt ứng viên.

**Architecture:** Logic nghiệp vụ nằm trong hai service mới (`VatPhamKemDvktQuanTri` cho thao tác trên bảng tra, `VatPhamKemDvktHocLaiService` cho lượt học nền) và trong `VatPhamKemDvktLearner` (mở rộng ra ứng viên). Controller mới `VatPhamKemDvktController` chỉ đọc request, gọi service, đổi `InvalidArgumentException` thành 422. Học chạy bằng `HocVatPhamKemDvktJob` trên kết nối/hàng đợi xuất tệp sẵn có. Một view blade duy nhất, dựng dần qua các task bằng các chỗ đánh dấu.

**Tech Stack:** PHP 7.4, Laravel 5.5, yajra/laravel-datatables, maatwebsite/excel 3.1, AdminLTE 2 (jQuery, DataTables, select2, SweetAlert2, toastr đã nạp sẵn), Dropzone (`public/js/dropzone.min.js`), PHPUnit 6 + SQLite bộ nhớ.

**Spec:** `docs/superpowers/specs/2026-10-13-xml3176-vat-pham-kem-dvkt-quan-tri-design.md`

## Global Constraints

- PHP 7.4 / Laravel 5.5: `setUp()`/`tearDown()` **không** có `: void`; được dùng `??`, `fn`.
- **Cấm `RefreshDatabase` / `DatabaseMigrations`.** Bảng test dựng bằng `Tests\Support\Xml3176RuleTestSupport::bootXml3176Sqlite([...tên tệp migration...])`.
- Chạy test luôn bằng `DB_HOST=127.0.0.1 vendor/bin/phpunit <đường dẫn>` (`.env` trỏ CSDL THẬT 192.168.200.68/qlbv). Không chạy migrate/seed/lệnh ghi vào CSDL thật.
- Không dùng type-hint tiêm service trên `Job::handle()` — lấy service bằng `app(...)` trong thân hàm.
- Trạng thái lượt học: `CHO`, `DANG_CHAY`, `XONG`, `LOI`. Treo sau **60 phút**.
- Kết nối/hàng đợi job học: `config('xml3176.xuat_tep_connection')` (= `xuat_tep`), `config('xml3176.xuat_tep_queue_name')` (= `JobXuatTepXml3176`).
- Config mới: `xml3176.vat_pham_kem_dvkt.nguong_ung_vien = 0.5`.
- Kiểm khớp lấy **12 tháng** gần nhất theo `ngay_yl`, tối đa **50** tên.
- Quyền mọi route mới: nhóm `prefix 'category/'`, `middleware ['checkrole:category-manager']`; tên route tiền tố `category-bhyt.vat-pham-kem-dvkt.`.
- Menu: `text 'DM vật phẩm kèm DVKT mẹ'`, đặt ngay sau `'DM lỗi Xml 3176'` trong khối BHYT của `config/adminlte.php`.
- Thông điệp 422 (chính xác từng chữ): `Cần ghi lý do khi tắt`; `Chỉ xoá được dòng bổ sung tay (TAY); dòng học tự động hãy tắt hiệu lực`; `Loại không hợp lệ (VTYT hoặc THUOC)`; `Mã vật phẩm phải từ 1 đến 100 ký tự`; `VTYT mã nhóm chung cần tên vật phẩm đúng như trên XML`; `Mã DVKT phải từ 1 đến 50 ký tự`; `Chưa chọn DVKT mẹ`; `Ghi chú tối đa 1024 ký tự`; `Tất cả cặp đã có trong bảng tra: <mã, ...>`; `DVKT không thuộc ứng viên: <mã, ...>`; `Tệp phải là .xlsx`.
- Comment mã nguồn tiếng Việt không dấu như mã xung quanh; chuỗi hiển thị tiếng Việt có dấu.
- Commit: `feat(xml3176): ...` tiếng Việt không dấu, kết thúc bằng `Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>`.
- Tên test `/** @test */` + snake_case tiếng Việt không dấu.

## File Structure

| Tệp | Trách nhiệm |
|---|---|
| `database/migrations/2026_10_14_090000_them_nguoi_sua_vao_xml3176_vat_pham_kem_dvkt.php` | 2 cột ghi vết |
| `database/migrations/2026_10_14_090100_create_xml3176_vpkd_lan_hoc_table.php` | Lịch sử lượt học |
| `database/migrations/2026_10_14_090200_create_xml3176_vpkd_ung_vien_table.php` | Ứng viên |
| `app/Models/BHYT/Xml3176VpkdLanHoc.php`, `Xml3176VpkdUngVien.php` | Model mới |
| `app/Models/BHYT/Xml3176VatPhamKemDvkt.php` | Thêm 2 cột |
| `app/Services/Xml3176/VatPhamKemDvktLearner.php` | Ứng viên, `ghiUngVien`, `nap(.., $nguoiSuaId)` |
| `app/Services/Xml3176/VatPhamKemDvktHocLaiService.php` | Lượt học: yêu cầu, chạy, treo, mô tả |
| `app/Jobs/HocVatPhamKemDvktJob.php` | Job học nền |
| `app/Console/Commands/Xml3176HocVatPhamKemDvkt.php` | Đường không `--thu` đi qua service |
| `app/Services/Xml3176/VatPhamKemDvktQuanTri.php` | Bật/tắt, ghi chú, xoá, thêm dòng tay, kiểm khớp, tra cứu |
| `app/Http/Controllers/Category/VatPhamKemDvktController.php` | HTTP mỏng |
| `resources/views/category/bhyt/vat_pham_kem_dvkt.blade.php` | Màn hình |
| `routes/web.php`, `config/adminlte.php`, `config/xml3176.php` | Đăng ký |
| `tests/Unit/Xml3176/VatPhamKemDvkt/*Test.php`, `tests/Unit/MenuDanhMucBhytTest.php` | Test |
| `docs/trien-khai-xml3176-vat-pham-kem-dvkt.md` | Cập nhật hướng dẫn |

Đường dẫn migration dùng trong test (lặp lại ở nhiều task):

```php
const MIG = [
    '2026_01_09_152826_create_xml3176_xml2s_table.php',
    '2026_01_09_152832_create_xml3176_xml3s_table.php',
    '2026_10_13_090000_create_xml3176_vat_pham_kem_dvkt_table.php',
    '2026_10_14_090000_them_nguoi_sua_vao_xml3176_vat_pham_kem_dvkt.php',
    '2026_10_14_090100_create_xml3176_vpkd_lan_hoc_table.php',
    '2026_10_14_090200_create_xml3176_vpkd_ung_vien_table.php',
];
```

---

### Task 0: Mốc test

- [ ] **Step 1:** Run: `DB_HOST=127.0.0.1 vendor/bin/phpunit tests/Unit/Xml3176 2>&1 | tail -3` và `DB_HOST=127.0.0.1 vendor/bin/phpunit tests/Unit/MenuDanhMucBhytTest.php tests/Unit/RouteXoaDanhMucTest.php 2>&1 | tail -3`. Ghi số test/lỗi. Mốc đã biết: `tests/Unit/Xml3176` = 714 test, 4 lỗi có sẵn (`Xml3176Xml3CheckerRuleTest::tg_th_yl_trung_tg_kq`, `::thuc_hien_duoi_3_phut_nhom_1`, `::bac_si_vua_ra_vua_thuc_hien`, `Xml3176ErrorServiceGomTest::che_do_gom_bat_tat_dung`).

---

### Task 1: Dữ liệu — 3 migration, 2 model mới, model bảng tra, config

**Files:**
- Create: 3 migration ở bảng File Structure; `app/Models/BHYT/Xml3176VpkdLanHoc.php`; `app/Models/BHYT/Xml3176VpkdUngVien.php`
- Modify: `app/Models/BHYT/Xml3176VatPhamKemDvkt.php`; `config/xml3176.php` (khối `vat_pham_kem_dvkt`)
- Test: `tests/Unit/Xml3176/VatPhamKemDvkt/QuanTriDuLieuTest.php`

**Interfaces — Produces:**
- `Xml3176VpkdLanHoc` hằng `CHO`, `DANG_CHAY`, `XONG`, `LOI`; cột `user_id, trang_thai, ket_qua (array), loi, bat_dau_luc, xong_luc` (Carbon).
- `Xml3176VpkdUngVien` cột `lan_hoc_id, loai, ma_vat_pham, ten_vat_pham, ten_khop, so_dong (int), so_lien_ket (int), ty_le_gan (float), dvkt (array [{ma, ten, so_lan}])`.
- `Xml3176VatPhamKemDvkt` thêm `nguoi_sua_id (int|null)`, `sua_luc (Carbon|null)`.

- [ ] **Step 1: Test (đỏ)** — `tests/Unit/Xml3176/VatPhamKemDvkt/QuanTriDuLieuTest.php`:

```php
<?php

namespace Tests\Unit\Xml3176\VatPhamKemDvkt;

use App\Models\BHYT\Xml3176VatPhamKemDvkt;
use App\Models\BHYT\Xml3176VpkdLanHoc;
use App\Models\BHYT\Xml3176VpkdUngVien;
use Carbon\Carbon;
use Tests\Support\Xml3176RuleTestSupport;
use Tests\TestCase;

class QuanTriDuLieuTest extends TestCase
{
    use Xml3176RuleTestSupport;

    protected function setUp()
    {
        parent::setUp();
        $this->bootXml3176Sqlite([
            '2026_10_13_090000_create_xml3176_vat_pham_kem_dvkt_table.php',
            '2026_10_14_090000_them_nguoi_sua_vao_xml3176_vat_pham_kem_dvkt.php',
            '2026_10_14_090100_create_xml3176_vpkd_lan_hoc_table.php',
            '2026_10_14_090200_create_xml3176_vpkd_ung_vien_table.php',
        ]);
    }

    /** @test */
    public function bang_tra_co_cot_ghi_vet()
    {
        $m = Xml3176VatPhamKemDvkt::create([
            'loai' => 'VTYT', 'ma_vat_pham' => 'N08.00.150', 'ten_khop' => 'buồng', 'ma_dvkt' => '12.0448.1187',
            'nguon' => 'TAY', 'nguoi_sua_id' => '7', 'sua_luc' => '2026-10-14 08:00:00',
        ])->fresh();

        $this->assertSame(7, $m->nguoi_sua_id);
        $this->assertInstanceOf(Carbon::class, $m->sua_luc);
    }

    /** @test */
    public function lan_hoc_luu_ket_qua_dang_mang()
    {
        $l = Xml3176VpkdLanHoc::create([
            'user_id' => 7, 'trang_thai' => Xml3176VpkdLanHoc::XONG, 'ket_qua' => ['them' => 3],
            'bat_dau_luc' => '2026-10-14 08:00:00',
        ])->fresh();

        $this->assertSame(['them' => 3], $l->ket_qua);
        $this->assertInstanceOf(Carbon::class, $l->bat_dau_luc);
        $this->assertSame('CHO', Xml3176VpkdLanHoc::CHO);
        $this->assertSame('DANG_CHAY', Xml3176VpkdLanHoc::DANG_CHAY);
        $this->assertSame('LOI', Xml3176VpkdLanHoc::LOI);
    }

    /** @test */
    public function ung_vien_luu_dvkt_dang_mang_va_ep_kieu()
    {
        $u = Xml3176VpkdUngVien::create([
            'lan_hoc_id' => 1, 'loai' => 'THUOC', 'ma_vat_pham' => '40.644', 'ten_vat_pham' => 'Omnipaque',
            'ten_khop' => '', 'so_dong' => '10', 'so_lien_ket' => '6', 'ty_le_gan' => '0.6',
            'dvkt' => [['ma' => '18.0150.0041', 'ten' => 'CLVT', 'so_lan' => 6]],
        ])->fresh();

        $this->assertSame(10, $u->so_dong);
        $this->assertSame(0.6, $u->ty_le_gan);
        $this->assertSame('18.0150.0041', $u->dvkt[0]['ma']);
    }

    /** @test */
    public function config_co_nguong_ung_vien()
    {
        $this->assertEquals(0.5, config('xml3176.vat_pham_kem_dvkt.nguong_ung_vien'));
    }
}
```

- [ ] **Step 2:** Run `DB_HOST=127.0.0.1 vendor/bin/phpunit tests/Unit/Xml3176/VatPhamKemDvkt/QuanTriDuLieuTest.php` → FAIL (thiếu tệp migration).

- [ ] **Step 3: Migration ghi vết** — `database/migrations/2026_10_14_090000_them_nguoi_sua_vao_xml3176_vat_pham_kem_dvkt.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Ghi nguoi va thoi diem sua cuoi qua man quan tri (giai trinh voi BHXH). Lenh hoc khong ghi. */
class ThemNguoiSuaVaoXml3176VatPhamKemDvkt extends Migration
{
    public function up()
    {
        Schema::table('xml3176_vat_pham_kem_dvkt', function (Blueprint $table) {
            $table->unsignedInteger('nguoi_sua_id')->nullable();
            $table->timestamp('sua_luc')->nullable();
        });
    }

    public function down()
    {
        Schema::table('xml3176_vat_pham_kem_dvkt', function (Blueprint $table) {
            $table->dropColumn(['nguoi_sua_id', 'sua_luc']);
        });
    }
}
```

- [ ] **Step 4: Migration lượt học** — `database/migrations/2026_10_14_090100_create_xml3176_vpkd_lan_hoc_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Lich su luot hoc bang tra vat pham kem DVKT me (man quan tri va lenh dong lenh). */
class CreateXml3176VpkdLanHocTable extends Migration
{
    public function up()
    {
        Schema::create('xml3176_vpkd_lan_hoc', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('user_id')->nullable()->index();
            $table->string('trang_thai', 20)->index();
            $table->text('ket_qua')->nullable();
            $table->text('loi')->nullable();
            $table->timestamp('bat_dau_luc')->nullable();
            $table->timestamp('xong_luc')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('xml3176_vpkd_lan_hoc');
    }
}
```

- [ ] **Step 5: Migration ứng viên** — `database/migrations/2026_10_14_090200_create_xml3176_vpkd_ung_vien_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ung vien cho duyet: vat pham gan du dieu kien hoc. Thay toan bo sau moi luot hoc.
 * ma_vat_pham/ten_khop dung utf8_bin nhu bang tra (so khop theo byte).
 */
class CreateXml3176VpkdUngVienTable extends Migration
{
    public function up()
    {
        Schema::create('xml3176_vpkd_ung_vien', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('lan_hoc_id')->nullable()->index();
            $table->string('loai', 10);
            $table->string('ma_vat_pham', 100)->collation('utf8_bin');
            $table->string('ten_vat_pham', 1024)->nullable();
            $table->string('ten_khop', 191)->collation('utf8_bin')->default('');
            $table->integer('so_dong');
            $table->integer('so_lien_ket');
            $table->decimal('ty_le_gan', 5, 4);
            $table->text('dvkt');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('xml3176_vpkd_ung_vien');
    }
}
```

- [ ] **Step 6: Model lượt học** — `app/Models/BHYT/Xml3176VpkdLanHoc.php`:

```php
<?php

namespace App\Models\BHYT;

use Illuminate\Database\Eloquent\Model;

class Xml3176VpkdLanHoc extends Model
{
    const CHO = 'CHO';
    const DANG_CHAY = 'DANG_CHAY';
    const XONG = 'XONG';
    const LOI = 'LOI';

    protected $table = 'xml3176_vpkd_lan_hoc';

    protected $fillable = ['user_id', 'trang_thai', 'ket_qua', 'loi', 'bat_dau_luc', 'xong_luc'];

    protected $casts = ['user_id' => 'integer', 'ket_qua' => 'array'];

    protected $dates = ['bat_dau_luc', 'xong_luc'];
}
```

- [ ] **Step 7: Model ứng viên** — `app/Models/BHYT/Xml3176VpkdUngVien.php`:

```php
<?php

namespace App\Models\BHYT;

use Illuminate\Database\Eloquent\Model;

class Xml3176VpkdUngVien extends Model
{
    protected $table = 'xml3176_vpkd_ung_vien';

    protected $fillable = [
        'lan_hoc_id', 'loai', 'ma_vat_pham', 'ten_vat_pham', 'ten_khop',
        'so_dong', 'so_lien_ket', 'ty_le_gan', 'dvkt',
    ];

    protected $attributes = ['ten_khop' => ''];

    protected $casts = [
        'lan_hoc_id' => 'integer',
        'so_dong' => 'integer',
        'so_lien_ket' => 'integer',
        'ty_le_gan' => 'float',
        'dvkt' => 'array',
    ];
}
```

- [ ] **Step 8: Model bảng tra** — trong `app/Models/BHYT/Xml3176VatPhamKemDvkt.php`: thêm `'nguoi_sua_id', 'sua_luc'` vào cuối `$fillable`; thêm `'nguoi_sua_id' => 'integer'` vào `$casts`; thêm thuộc tính:

```php
    protected $dates = ['sua_luc'];
```

- [ ] **Step 9: Config** — trong `config/xml3176.php`, khối `'vat_pham_kem_dvkt' => [...]`, thêm sau dòng `toi_thieu`:

```php
        // Ung vien cho duyet: vat pham chua vao bang tra, co it nhat 1 lan gan, ty le gan >= gia tri nay.
        'nguong_ung_vien' => 0.5,
```

- [ ] **Step 10:** Run test Step 2 → PASS (4 test). Rồi `DB_HOST=127.0.0.1 vendor/bin/phpunit tests/Unit/Xml3176/VatPhamKemDvkt` → xanh.

- [ ] **Step 11: Commit**

```bash
git add database/migrations/2026_10_14_090000_them_nguoi_sua_vao_xml3176_vat_pham_kem_dvkt.php database/migrations/2026_10_14_090100_create_xml3176_vpkd_lan_hoc_table.php database/migrations/2026_10_14_090200_create_xml3176_vpkd_ung_vien_table.php app/Models/BHYT/Xml3176VpkdLanHoc.php app/Models/BHYT/Xml3176VpkdUngVien.php app/Models/BHYT/Xml3176VatPhamKemDvkt.php config/xml3176.php tests/Unit/Xml3176/VatPhamKemDvkt/QuanTriDuLieuTest.php
git commit -m "feat(xml3176): bang luot hoc, ung vien va cot ghi vet cho bang tra vat pham kem DVKT

Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>"
```

---

### Task 2: Learner — ứng viên, `ghiUngVien`, `nap()` ghi người sửa

**Files:**
- Modify: `app/Services/Xml3176/VatPhamKemDvktLearner.php`
- Test: `tests/Unit/Xml3176/VatPhamKemDvkt/UngVienLearnerTest.php`

**Interfaces:**
- Consumes: Task 1 models.
- Produces:
  - `hoc(float $nguong, int $toiThieu, $tuNgay = null, float $nguongUngVien = 0.5): array` — khoá cũ `dong`, `so_vat_pham` giữ nguyên; thêm `ung_vien` = list `['loai','ma_vat_pham','ten_vat_pham','ten_khop','so_dong','so_lien_ket','ty_le_gan','dvkt' => [['ma','ten','so_lan'], ...]]`, `dvkt` sắp `so_lan` giảm dần rồi `ma` tăng dần.
  - `ghiUngVien(array $ungVien, $lanHocId = null): int`.
  - `nap(array $hang, $nguoiSuaId = null): array` — như cũ; khi `$nguoiSuaId !== null` thì dòng thêm/cập nhật có `nguoi_sua_id`, `sua_luc = now`.

- [ ] **Step 1: Test (đỏ)** — `tests/Unit/Xml3176/VatPhamKemDvkt/UngVienLearnerTest.php`:

```php
<?php

namespace Tests\Unit\Xml3176\VatPhamKemDvkt;

use App\Models\BHYT\Xml3176VatPhamKemDvkt;
use App\Models\BHYT\Xml3176VpkdUngVien;
use App\Services\Xml3176\VatPhamKemDvktLearner;
use Illuminate\Support\Facades\DB;
use Tests\Support\Xml3176RuleTestSupport;
use Tests\TestCase;

class UngVienLearnerTest extends TestCase
{
    use Xml3176RuleTestSupport;

    private $stt = 0;

    protected function setUp()
    {
        parent::setUp();
        $this->bootXml3176Sqlite([
            '2026_01_09_152826_create_xml3176_xml2s_table.php',
            '2026_01_09_152832_create_xml3176_xml3s_table.php',
            '2026_10_13_090000_create_xml3176_vat_pham_kem_dvkt_table.php',
            '2026_10_14_090000_them_nguoi_sua_vao_xml3176_vat_pham_kem_dvkt.php',
            '2026_10_14_090100_create_xml3176_vpkd_lan_hoc_table.php',
            '2026_10_14_090200_create_xml3176_vpkd_ung_vien_table.php',
        ]);
    }

    /** $n dong VTYT BHYT tra; $dsGan = [ma_dvkt => so dong gan]. */
    private function vtyt(string $ma, string $ten, int $n, array $dsGan): void
    {
        $gan = [];
        foreach ($dsGan as $dv => $k) {
            for ($i = 0; $i < $k; $i++) {
                $gan[] = $dv;
            }
        }
        for ($i = 0; $i < $n; $i++) {
            DB::table('xml3176_xml3s')->insert([
                'ma_lk' => 'HS' . $i, 'stt' => ++$this->stt, 'ma_vat_tu' => $ma, 'ten_vat_tu' => $ten,
                'ma_dich_vu' => $gan[$i] ?? null, 't_bhtt' => 1000, 'so_luong' => 1, 'ngay_yl' => '202609010800',
            ]);
        }
    }

    private function hoc(): array
    {
        return (new VatPhamKemDvktLearner())->hoc(0.8, 5, null, 0.5);
    }

    /** @test */
    public function chon_ung_vien_theo_hai_nhanh_tieu_chi()
    {
        $this->vtyt('N01.01.001.0001.000.0001', 'A ty le 60%', 10, ['01.0001.0001' => 4, '01.0002.0001_GT' => 2]);
        $this->vtyt('N01.01.001.0001.000.0002', 'B du ty le thieu dong', 3, ['01.0003.0001' => 3]);
        $this->vtyt('N01.01.001.0001.000.0003', 'C ty le 30%', 10, ['01.0004.0001' => 3]);
        $this->vtyt('N01.01.001.0001.000.0004', 'D da vao bang tra', 10, ['01.0005.0001' => 9]);
        $this->vtyt('N01.01.001.0001.000.0005', 'E khong lien ket', 10, []);

        $kq = $this->hoc();
        $ma = array_column($kq['ung_vien'], 'ma_vat_pham');
        sort($ma);

        $this->assertSame(['N01.01.001.0001.000.0001', 'N01.01.001.0001.000.0002'], $ma);
        $this->assertSame(['VTYT' => 1, 'THUOC' => 0], $kq['so_vat_pham']);
        $this->assertCount(1, $kq['dong']);
    }

    /** @test */
    public function dvkt_ung_vien_chuan_hoa_va_sap_giam_dan()
    {
        $this->vtyt('N01.01.001.0001.000.0001', 'A', 10, ['01.0002.0001_GT' => 2, '01.0001.0001' => 4]);

        $u = $this->hoc()['ung_vien'][0];

        $this->assertSame(10, $u['so_dong']);
        $this->assertSame(6, $u['so_lien_ket']);
        $this->assertSame(0.6, $u['ty_le_gan']);
        $this->assertSame(['01.0001.0001', '01.0002.0001'], array_column($u['dvkt'], 'ma'));
        $this->assertSame([4, 2], array_column($u['dvkt'], 'so_lan'));
    }

    /** @test */
    public function ghi_ung_vien_thay_toan_bo()
    {
        $l = new VatPhamKemDvktLearner();
        $mau = ['loai' => 'VTYT', 'ma_vat_pham' => 'X', 'ten_vat_pham' => 'X', 'ten_khop' => '',
            'so_dong' => 10, 'so_lien_ket' => 6, 'ty_le_gan' => 0.6, 'dvkt' => []];

        $this->assertSame(2, $l->ghiUngVien([$mau, ['ma_vat_pham' => 'Y'] + $mau], 5));
        $this->assertSame(1, $l->ghiUngVien([['ma_vat_pham' => 'Z'] + $mau], 6));

        $this->assertSame(['Z'], Xml3176VpkdUngVien::pluck('ma_vat_pham')->all());
        $this->assertSame(6, Xml3176VpkdUngVien::first()->lan_hoc_id);
    }

    /** @test */
    public function nap_ghi_nguoi_sua_khi_duoc_truyen()
    {
        $l = new VatPhamKemDvktLearner();
        $hang = [['loai' => 'THUOC', 'ma_vat_pham' => '40.644', 'ten_vat_pham' => 'Omnipaque', 'ma_dvkt' => '18.0150.0041', 'hieu_luc' => '1']];

        $l->nap($hang);
        $this->assertNull(Xml3176VatPhamKemDvkt::first()->nguoi_sua_id);

        $l->nap([['hieu_luc' => '0', 'ghi_chu' => 'sai'] + $hang[0]], 7);
        $m = Xml3176VatPhamKemDvkt::first();
        $this->assertSame(7, $m->nguoi_sua_id);
        $this->assertNotNull($m->sua_luc);
        $this->assertFalse($m->hieu_luc);
    }
}
```

- [ ] **Step 2:** Run `DB_HOST=127.0.0.1 vendor/bin/phpunit tests/Unit/Xml3176/VatPhamKemDvkt/UngVienLearnerTest.php` → FAIL (`Undefined index: ung_vien` / `ghiUngVien` không có).

- [ ] **Step 3: Sửa `hoc()` và đổi `chonVatPham` thành thống kê toàn bộ** — trong `VatPhamKemDvktLearner.php`:

Thêm `use App\Models\BHYT\Xml3176VpkdUngVien;` và `use Carbon\Carbon;` vào khối `use`.

Thay **toàn bộ** method `hoc()` bằng:

```php
    public function hoc(float $nguong, int $toiThieu, $tuNgay = null, float $nguongUngVien = 0.5): array
    {
        $dong = [];
        $ungVienRa = [];
        $soVp = [];
        $cacCap = [];
        $maGoc = [];

        foreach (self::NGUON as $loai => $n) {
            $chon = [];
            $ungVien = [];
            foreach ($this->thongKeVatPham($loai, $n, $tuNgay) as $khoa => $g) {
                $tyLe = $g['lk'] / $g['n'];
                if ($g['n'] >= $toiThieu && $tyLe >= $nguong) {
                    $chon[$khoa] = $g;
                } elseif ($g['lk'] >= 1 && $tyLe >= $nguongUngVien) {
                    // Chua vao bang tra: hoac ty le trong [nguongUngVien, nguong), hoac du ty le
                    // nhung thieu so dong toi thieu.
                    $ungVien[$khoa] = $g;
                }
            }
            $soVp[$loai] = count($chon);
            if (empty($chon) && empty($ungVien)) {
                continue;
            }
            $cap = $this->capDaGan($loai, $n, $chon + $ungVien, $tuNgay);
            foreach ($cap as $dsDv) {
                foreach ($dsDv as $maDv => $c) {
                    // Ca ma goc (vd 12.0448.1187_GT) lan ma chuan hoa: dong DVKT co the mang dang nao cung duoc.
                    $maGoc[] = $maDv;
                    $maGoc = array_merge($maGoc, array_keys($c['goc']));
                }
            }
            $cacCap[$loai] = [$chon, $ungVien, $cap];
        }

        $tenDv = $this->tenDvkt($maGoc);

        foreach ($cacCap as $loai => list($chon, $ungVien, $cap)) {
            foreach ($chon as $khoa => $vp) {
                foreach ($cap[$khoa] ?? [] as $maDv => $c) {
                    $dong[] = [
                        'loai' => $loai,
                        'ma_vat_pham' => $vp['ma'],
                        'ten_vat_pham' => $vp['ten'],
                        'ten_khop' => $vp['ten_khop'],
                        'ma_dvkt' => $maDv,
                        'ten_dvkt' => $tenDv[$maDv] ?? null,
                        'so_lan_gan' => $c['n'],
                        'ty_le_gan' => round($vp['lk'] / $vp['n'], 4),
                    ];
                }
            }
            foreach ($ungVien as $khoa => $vp) {
                $dsDv = [];
                foreach ($cap[$khoa] ?? [] as $maDv => $c) {
                    $dsDv[] = ['ma' => (string) $maDv, 'ten' => $tenDv[$maDv] ?? null, 'so_lan' => $c['n']];
                }
                usort($dsDv, function ($a, $b) {
                    return ($b['so_lan'] <=> $a['so_lan']) ?: strcmp($a['ma'], $b['ma']);
                });
                $ungVienRa[] = [
                    'loai' => $loai,
                    'ma_vat_pham' => $vp['ma'],
                    'ten_vat_pham' => $vp['ten'],
                    'ten_khop' => $vp['ten_khop'],
                    'so_dong' => $vp['n'],
                    'so_lien_ket' => $vp['lk'],
                    'ty_le_gan' => round($vp['lk'] / $vp['n'], 4),
                    'dvkt' => $dsDv,
                ];
            }
        }

        return ['dong' => $dong, 'so_vat_pham' => $soVp, 'ung_vien' => $ungVienRa];
    }
```

Đổi tên method `chonVatPham` thành `thongKeVatPham`, đổi chữ ký thành `private function thongKeVatPham(string $loai, array $n, $tuNgay): array`, và thay câu `return array_filter($gom, ...)` cuối hàm bằng `return $gom;`.

- [ ] **Step 4: Thêm `ghiUngVien`** — ngay sau method `ghi()`:

```php
    /** Thay toan bo bang ung vien bang danh sach moi (tu hoc()). Tra so dong ghi. */
    public function ghiUngVien(array $ungVien, $lanHocId = null): int
    {
        return DB::transaction(function () use ($ungVien, $lanHocId) {
            Xml3176VpkdUngVien::query()->delete();
            foreach ($ungVien as $u) {
                Xml3176VpkdUngVien::create($u + ['lan_hoc_id' => $lanHocId]);
            }

            return count($ungVien);
        });
    }
```

- [ ] **Step 5: `nap()` ghi người sửa** — đổi chữ ký `public function nap(array $hang): array` thành `public function nap(array $hang, $nguoiSuaId = null): array`. Ngay sau dòng `$kq = ['them' => 0, 'cap_nhat' => 0, 'loi' => []];` thêm:

```php
        // Nap tu man quan tri thi ghi vet nguoi sua; lenh dong lenh truyen null.
        $vet = $nguoiSuaId === null ? [] : ['nguoi_sua_id' => (int) $nguoiSuaId, 'sua_luc' => Carbon::now()];
```

Trong nhánh `if ($m) {`, ngay trước `$m->save();` thêm `$m->fill($vet);`. Trong `Xml3176VatPhamKemDvkt::create([...])` của nhánh thêm mới, đổi thành `Xml3176VatPhamKemDvkt::create([...] + $vet);`.

- [ ] **Step 6:** Run Step 2 → PASS (4 test). Rồi `DB_HOST=127.0.0.1 vendor/bin/phpunit tests/Unit/Xml3176/VatPhamKemDvkt` → xanh (các test learner cũ vẫn qua).

- [ ] **Step 7: Commit**

```bash
git add app/Services/Xml3176/VatPhamKemDvktLearner.php tests/Unit/Xml3176/VatPhamKemDvkt/UngVienLearnerTest.php
git commit -m "feat(xml3176): learner tinh ung vien cho duyet va ghi vet nguoi sua khi nap

Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>"
```

---

### Task 3: Lượt học nền — service, job, lệnh dòng lệnh

**Files:**
- Create: `app/Services/Xml3176/VatPhamKemDvktHocLaiService.php`, `app/Jobs/HocVatPhamKemDvktJob.php`
- Modify: `app/Console/Commands/Xml3176HocVatPhamKemDvkt.php`
- Test: `tests/Unit/Xml3176/VatPhamKemDvkt/HocLaiServiceTest.php`

**Interfaces:**
- Consumes: Task 2 `hoc(..., $nguongUngVien)`, `ghi()`, `ghiUngVien()`; Task 1 `Xml3176VpkdLanHoc`.
- Produces (`VatPhamKemDvktHocLaiService`):
  - `const PHUT_TREO = 60;`
  - `yeuCau($userId): array` → `['lan_hoc' => Xml3176VpkdLanHoc, 'moi' => bool]`
  - `thucHien(Xml3176VpkdLanHoc $lan, float $nguong, int $toiThieu, $tuNgay = null): array` → `ket_qua` = `['so_vat_pham' => [...], 'so_cap' => ['VTYT' => int, 'THUOC' => int], 'them', 'cap_nhat', 'tat', 'bat_lai', 'so_ung_vien']`
  - `danhDauTreo(): int`, `ganNhat(): ?Xml3176VpkdLanHoc`, `moTa($lan): ?array` → `['id','trang_thai','dang_chay' => bool,'nhan' => string]`
- `HocVatPhamKemDvktJob($lanHocId)`.

- [ ] **Step 1: Test (đỏ)** — `tests/Unit/Xml3176/VatPhamKemDvkt/HocLaiServiceTest.php`:

```php
<?php

namespace Tests\Unit\Xml3176\VatPhamKemDvkt;

use App\Jobs\HocVatPhamKemDvktJob;
use App\Models\BHYT\Xml3176VatPhamKemDvkt;
use App\Models\BHYT\Xml3176VpkdLanHoc;
use App\Models\BHYT\Xml3176VpkdUngVien;
use App\Services\Xml3176\VatPhamKemDvktHocLaiService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Tests\Support\Xml3176RuleTestSupport;
use Tests\TestCase;

class HocLaiServiceTest extends TestCase
{
    use Xml3176RuleTestSupport;

    protected function setUp()
    {
        parent::setUp();
        $this->bootXml3176Sqlite([
            '2014_10_12_000000_create_users_table.php',
            '2026_01_09_152826_create_xml3176_xml2s_table.php',
            '2026_01_09_152832_create_xml3176_xml3s_table.php',
            '2026_10_13_090000_create_xml3176_vat_pham_kem_dvkt_table.php',
            '2026_10_14_090000_them_nguoi_sua_vao_xml3176_vat_pham_kem_dvkt.php',
            '2026_10_14_090100_create_xml3176_vpkd_lan_hoc_table.php',
            '2026_10_14_090200_create_xml3176_vpkd_ung_vien_table.php',
        ]);
        // 10 dong, 9 gan -> vao bang tra; 10 dong, 6 gan -> ung vien
        for ($i = 0; $i < 10; $i++) {
            DB::table('xml3176_xml3s')->insert([
                ['ma_lk' => 'A' . $i, 'stt' => 1, 'ma_vat_tu' => 'N08.00.150.0001.000.0001', 'ten_vat_tu' => 'Buồng',
                 'ma_dich_vu' => $i < 9 ? '12.0448.1187' : null, 't_bhtt' => 1000, 'so_luong' => 1, 'ngay_yl' => '202609010800'],
                ['ma_lk' => 'A' . $i, 'stt' => 2, 'ma_vat_tu' => 'N03.03.030.0001.000.0001', 'ten_vat_tu' => 'Kim',
                 'ma_dich_vu' => $i < 6 ? '22.0126.0092' : null, 't_bhtt' => 1000, 'so_luong' => 1, 'ngay_yl' => '202609010800'],
            ]);
        }
        DB::table('users')->insert(['id' => 7, 'name' => 'tracnn', 'email' => 'a@b.c', 'password' => 'x']);
    }

    private function sv(): VatPhamKemDvktHocLaiService
    {
        return app(VatPhamKemDvktHocLaiService::class);
    }

    /** @test */
    public function yeu_cau_tao_luot_cho_va_day_job_dung_ket_noi_hang_doi()
    {
        Queue::fake();

        $r = $this->sv()->yeuCau(7);

        $this->assertTrue($r['moi']);
        $this->assertSame(Xml3176VpkdLanHoc::CHO, $r['lan_hoc']->trang_thai);
        $this->assertSame(7, $r['lan_hoc']->user_id);
        Queue::assertPushedOn('JobXuatTepXml3176', HocVatPhamKemDvktJob::class, function ($job) use ($r) {
            return $job->lanHocId === $r['lan_hoc']->id && $job->connection === 'xuat_tep';
        });
    }

    /** @test */
    public function dang_co_luot_chay_thi_khong_day_them()
    {
        Queue::fake();
        $cu = Xml3176VpkdLanHoc::create(['user_id' => 7, 'trang_thai' => Xml3176VpkdLanHoc::DANG_CHAY, 'bat_dau_luc' => Carbon::now()]);

        $r = $this->sv()->yeuCau(8);

        $this->assertFalse($r['moi']);
        $this->assertSame($cu->id, $r['lan_hoc']->id);
        Queue::assertNotPushed(HocVatPhamKemDvktJob::class);
    }

    /** @test */
    public function danh_dau_treo_sau_60_phut_khong_dung_luot_moi()
    {
        $treo = Xml3176VpkdLanHoc::create(['trang_thai' => Xml3176VpkdLanHoc::DANG_CHAY, 'bat_dau_luc' => Carbon::now()->subMinutes(61)]);
        $moi = Xml3176VpkdLanHoc::create(['trang_thai' => Xml3176VpkdLanHoc::DANG_CHAY, 'bat_dau_luc' => Carbon::now()->subMinutes(5)]);
        $choCu = Xml3176VpkdLanHoc::create(['trang_thai' => Xml3176VpkdLanHoc::CHO]);
        DB::table('xml3176_vpkd_lan_hoc')->where('id', $choCu->id)->update(['created_at' => Carbon::now()->subMinutes(61)]);

        $this->assertSame(2, $this->sv()->danhDauTreo());

        $this->assertSame(Xml3176VpkdLanHoc::LOI, $treo->fresh()->trang_thai);
        $this->assertSame('Treo quá 60 phút', $treo->fresh()->loi);
        $this->assertSame(Xml3176VpkdLanHoc::LOI, $choCu->fresh()->trang_thai);
        $this->assertSame(Xml3176VpkdLanHoc::DANG_CHAY, $moi->fresh()->trang_thai);
    }

    /** @test */
    public function job_chay_luot_ghi_xong_bang_tra_va_ung_vien()
    {
        $lan = Xml3176VpkdLanHoc::create(['user_id' => 7, 'trang_thai' => Xml3176VpkdLanHoc::CHO]);

        (new HocVatPhamKemDvktJob($lan->id))->handle();

        $lan = $lan->fresh();
        $this->assertSame(Xml3176VpkdLanHoc::XONG, $lan->trang_thai);
        $this->assertSame(1, $lan->ket_qua['them']);
        $this->assertSame(1, $lan->ket_qua['so_ung_vien']);
        $this->assertSame(['VTYT' => 1, 'THUOC' => 0], $lan->ket_qua['so_cap']);
        $this->assertNotNull($lan->xong_luc);
        $this->assertSame(1, Xml3176VatPhamKemDvkt::count());
        $this->assertSame($lan->id, Xml3176VpkdUngVien::first()->lan_hoc_id);
    }

    /** @test */
    public function job_bo_qua_luot_khong_o_trang_thai_cho()
    {
        $lan = Xml3176VpkdLanHoc::create(['trang_thai' => Xml3176VpkdLanHoc::XONG]);

        (new HocVatPhamKemDvktJob($lan->id))->handle();

        $this->assertSame(0, Xml3176VatPhamKemDvkt::count());
    }

    /** @test */
    public function loi_khi_chay_ghi_luot_loi_va_nem_lai()
    {
        $lan = Xml3176VpkdLanHoc::create(['trang_thai' => Xml3176VpkdLanHoc::CHO]);
        DB::statement('DROP TABLE xml3176_vpkd_ung_vien');

        try {
            (new HocVatPhamKemDvktJob($lan->id))->handle();
            $this->fail('Phai nem lai loi');
        } catch (\Throwable $e) {
            $this->assertNotInstanceOf(\PHPUnit\Framework\AssertionFailedError::class, $e);
        }

        $this->assertSame(Xml3176VpkdLanHoc::LOI, $lan->fresh()->trang_thai);
        $this->assertNotEmpty($lan->fresh()->loi);
        $this->assertSame(0, Xml3176VatPhamKemDvkt::count(), 'Giao dich phai rollback ca bang tra');
    }

    /** @test */
    public function mo_ta_luot_xong_co_nguoi_va_so_lieu()
    {
        $lan = Xml3176VpkdLanHoc::create(['user_id' => 7, 'trang_thai' => Xml3176VpkdLanHoc::CHO]);
        (new HocVatPhamKemDvktJob($lan->id))->handle();

        $m = $this->sv()->moTa($this->sv()->ganNhat());

        $this->assertFalse($m['dang_chay']);
        $this->assertContains('bởi tracnn: thêm 1, cập nhật 0, tắt 0, bật lại 0, ứng viên 1', $m['nhan']);
        $this->assertNull($this->sv()->moTa(null));
    }

    /** @test */
    public function lenh_hoc_khong_thu_ghi_luot_va_ung_vien()
    {
        Artisan::call('xml3176:hoc-vat-pham-kem-dvkt');

        $lan = Xml3176VpkdLanHoc::first();
        $this->assertNull($lan->user_id);
        $this->assertSame(Xml3176VpkdLanHoc::XONG, $lan->trang_thai);
        $this->assertSame(1, Xml3176VpkdUngVien::count());
        $this->assertContains('Ứng viên: 1', Artisan::output());
    }

    /** @test */
    public function lenh_hoc_che_do_thu_khong_ghi_luot()
    {
        Artisan::call('xml3176:hoc-vat-pham-kem-dvkt', ['--thu' => true]);

        $this->assertSame(0, Xml3176VpkdLanHoc::count());
        $this->assertSame(0, Xml3176VpkdUngVien::count());
    }
}
```

Lưu ý: nếu `insert` vào `users` lỗi vì cột NOT NULL khác, đọc `database/migrations/2014_10_12_000000_create_users_table.php` và các migration sửa bảng `users`, rồi bổ sung giá trị tối thiểu trong mảng insert — không sửa migration.

- [ ] **Step 2:** Run `DB_HOST=127.0.0.1 vendor/bin/phpunit tests/Unit/Xml3176/VatPhamKemDvkt/HocLaiServiceTest.php` → FAIL (class không có).

- [ ] **Step 3: Service** — `app/Services/Xml3176/VatPhamKemDvktHocLaiService.php`:

```php
<?php

namespace App\Services\Xml3176;

use App\Jobs\HocVatPhamKemDvktJob;
use App\Models\BHYT\Xml3176VpkdLanHoc;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Luot hoc bang tra vat pham kem DVKT me: yeu cau tu man quan tri (chay nen), chay that
 * (dung chung cho job va lenh dong lenh), danh dau treo, mo ta trang thai.
 *
 * Hoc ton 2-5 phut tren ~1,4 trieu dong XML3 - qua gioi han 120 giay cua request web nen
 * phai chay nen, tren ket noi/hang doi xuat tep (retry_after 3600) da co dich vu NSSM.
 * PHP Windows khong co pcntl nen $timeout cua job khong duoc thi hanh: treo duoc phat hien
 * bang moc thoi gian (khuon Xml3176TepXuatService::danhDauTreo).
 */
class VatPhamKemDvktHocLaiService
{
    const PHUT_TREO = 60;

    /** @var VatPhamKemDvktLearner */
    private $learner;

    public function __construct(VatPhamKemDvktLearner $learner)
    {
        $this->learner = $learner;
    }

    public function yeuCau($userId): array
    {
        $this->danhDauTreo();

        $dang = Xml3176VpkdLanHoc::whereIn('trang_thai', [Xml3176VpkdLanHoc::CHO, Xml3176VpkdLanHoc::DANG_CHAY])
            ->orderBy('id', 'desc')->first();
        if ($dang !== null) {
            return ['lan_hoc' => $dang, 'moi' => false];
        }

        $lan = Xml3176VpkdLanHoc::create(['user_id' => $userId, 'trang_thai' => Xml3176VpkdLanHoc::CHO]);

        // Day SAU khi dong da ghi: job doc dong theo id.
        try {
            HocVatPhamKemDvktJob::dispatch($lan->id)
                ->onConnection(config('xml3176.xuat_tep_connection'))
                ->onQueue(config('xml3176.xuat_tep_queue_name'));
        } catch (\Throwable $e) {
            $lan->update(['trang_thai' => Xml3176VpkdLanHoc::LOI, 'loi' => 'Không xếp được vào hàng đợi, vui lòng thử lại.', 'xong_luc' => Carbon::now()]);
            throw $e;
        }

        return ['lan_hoc' => $lan, 'moi' => true];
    }

    public function thucHien(Xml3176VpkdLanHoc $lan, float $nguong, int $toiThieu, $tuNgay = null): array
    {
        $lan->update(['trang_thai' => Xml3176VpkdLanHoc::DANG_CHAY, 'bat_dau_luc' => Carbon::now()]);

        try {
            $hoc = $this->learner->hoc($nguong, $toiThieu, $tuNgay, (float) config('xml3176.vat_pham_kem_dvkt.nguong_ung_vien', 0.5));

            $ketQua = DB::transaction(function () use ($hoc, $lan) {
                $g = $this->learner->ghi($hoc['dong']);
                $g['so_ung_vien'] = $this->learner->ghiUngVien($hoc['ung_vien'], $lan->id);

                return $g;
            });

            $soCap = [];
            foreach (array_keys($hoc['so_vat_pham']) as $loai) {
                $soCap[$loai] = count(array_filter($hoc['dong'], function ($d) use ($loai) {
                    return $d['loai'] === $loai;
                }));
            }
            $ketQua = ['so_vat_pham' => $hoc['so_vat_pham'], 'so_cap' => $soCap] + $ketQua;

            $lan->update(['trang_thai' => Xml3176VpkdLanHoc::XONG, 'ket_qua' => $ketQua, 'xong_luc' => Carbon::now()]);

            return $ketQua;
        } catch (\Throwable $e) {
            $lan->update(['trang_thai' => Xml3176VpkdLanHoc::LOI, 'loi' => mb_substr($e->getMessage(), 0, 2000), 'xong_luc' => Carbon::now()]);
            throw $e;
        }
    }

    public function danhDauTreo(): int
    {
        $moc = Carbon::now()->subMinutes(self::PHUT_TREO);
        $loi = ['trang_thai' => Xml3176VpkdLanHoc::LOI, 'loi' => 'Treo quá 60 phút', 'xong_luc' => Carbon::now()];

        $n = Xml3176VpkdLanHoc::where('trang_thai', Xml3176VpkdLanHoc::DANG_CHAY)->where('bat_dau_luc', '<', $moc)->update($loi);
        $n += Xml3176VpkdLanHoc::where('trang_thai', Xml3176VpkdLanHoc::CHO)->where('created_at', '<', $moc)->update($loi);

        return $n;
    }

    public function ganNhat()
    {
        return Xml3176VpkdLanHoc::orderBy('id', 'desc')->first();
    }

    public function moTa($lan)
    {
        if ($lan === null) {
            return null;
        }

        $nguoi = $lan->user_id
            ? (DB::table('users')->where('id', $lan->user_id)->value('name') ?: '#' . $lan->user_id)
            : 'dòng lệnh';
        $k = (array) $lan->ket_qua;

        switch ($lan->trang_thai) {
            case Xml3176VpkdLanHoc::CHO:
                $nhan = 'Đang chờ chạy (yêu cầu ' . $lan->created_at->format('d/m H:i') . ' bởi ' . $nguoi . ')';
                break;
            case Xml3176VpkdLanHoc::DANG_CHAY:
                $nhan = 'Đang chạy từ ' . optional($lan->bat_dau_luc)->format('H:i') . ' (bởi ' . $nguoi . ')';
                break;
            case Xml3176VpkdLanHoc::XONG:
                $nhan = sprintf(
                    'Xong %s bởi %s: thêm %d, cập nhật %d, tắt %d, bật lại %d, ứng viên %d',
                    optional($lan->xong_luc)->format('d/m H:i'), $nguoi,
                    $k['them'] ?? 0, $k['cap_nhat'] ?? 0, $k['tat'] ?? 0, $k['bat_lai'] ?? 0, $k['so_ung_vien'] ?? 0
                );
                break;
            default:
                $nhan = 'Lỗi: ' . $lan->loi;
        }

        return [
            'id' => $lan->id,
            'trang_thai' => $lan->trang_thai,
            'dang_chay' => in_array($lan->trang_thai, [Xml3176VpkdLanHoc::CHO, Xml3176VpkdLanHoc::DANG_CHAY], true),
            'nhan' => $nhan,
        ];
    }
}
```

- [ ] **Step 4: Job** — `app/Jobs/HocVatPhamKemDvktJob.php`:

```php
<?php

namespace App\Jobs;

use App\Models\BHYT\Xml3176VpkdLanHoc;
use App\Services\Xml3176\VatPhamKemDvktHocLaiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Hoc lai bang tra vat pham kem DVKT me chay nen cho mot dong xml3176_vpkd_lan_hoc.
 *
 * KHONG khai $timeout (PHP Windows khong co pcntl); treo do VatPhamKemDvktHocLaiService::danhDauTreo
 * phat hien. KHONG type-hint service tren handle(): lay bang app() (bay tiem container da gap).
 */
class HocVatPhamKemDvktJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** Hong thi ghi loi, khong tu chay lai mot viec ton vai phut. */
    public $tries = 1;

    /** @var int */
    public $lanHocId;

    public function __construct($lanHocId)
    {
        $this->lanHocId = (int) $lanHocId;
    }

    public function handle()
    {
        $lan = Xml3176VpkdLanHoc::find($this->lanHocId);
        if ($lan === null || $lan->trang_thai !== Xml3176VpkdLanHoc::CHO) {
            return;
        }

        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        app(VatPhamKemDvktHocLaiService::class)->thucHien(
            $lan,
            (float) config('xml3176.vat_pham_kem_dvkt.nguong', 0.8),
            (int) config('xml3176.vat_pham_kem_dvkt.toi_thieu', 5)
        );
    }
}
```

- [ ] **Step 5: Lệnh dòng lệnh** — trong `app/Console/Commands/Xml3176HocVatPhamKemDvkt.php`:

Thêm `use App\Models\BHYT\Xml3176VpkdLanHoc;` và `use App\Services\Xml3176\VatPhamKemDvktHocLaiService;`.

Thay toàn bộ thân `handle()` từ dòng `$kq = $learner->hoc(...)` đến hết khối `else { ... }` (trước `if ($tep = $this->option('xuat'))`) bằng:

```php
        if ($this->option('thu')) {
            $kq = $learner->hoc($nguong, $toiThieu, $this->option('tu-ngay') ?: null,
                (float) config('xml3176.vat_pham_kem_dvkt.nguong_ung_vien', 0.5));
            foreach ($kq['so_vat_pham'] as $loai => $soVp) {
                $soCap = count(array_filter($kq['dong'], function ($d) use ($loai) { return $d['loai'] === $loai; }));
                $this->info("{$loai}: {$soVp} vật phẩm, {$soCap} cặp");
            }
            $this->info('Ứng viên: ' . count($kq['ung_vien']));
            $this->warn('Chế độ thử: không ghi bảng.');
            if ($this->option('xuat')) {
                $this->warn('Cảnh báo: tệp xuất ở chế độ thử có cột nguon rỗng; nạp lại tệp này sẽ biến toàn bộ dòng thành TAY.');
            }
            $dongXuat = $kq['dong'];
        } else {
            // Di qua service de lich su luot hoc va bang ung vien nhat quan voi man quan tri.
            $lan = Xml3176VpkdLanHoc::create(['user_id' => null, 'trang_thai' => Xml3176VpkdLanHoc::CHO]);
            $k = app(VatPhamKemDvktHocLaiService::class)->thucHien($lan, $nguong, $toiThieu, $this->option('tu-ngay') ?: null);
            foreach ($k['so_vat_pham'] as $loai => $soVp) {
                $this->info("{$loai}: {$soVp} vật phẩm, {$k['so_cap'][$loai]} cặp");
            }
            $this->info("Ghi: thêm {$k['them']}, cập nhật {$k['cap_nhat']}, tắt {$k['tat']}, bật lại {$k['bat_lai']}");
            $this->info("Ứng viên: {$k['so_ung_vien']}");
            $dongXuat = Xml3176VatPhamKemDvkt::orderBy('loai')->orderBy('ma_vat_pham')->orderBy('ma_dvkt')->get()->all();
        }
```

(Giữ nguyên phần kiểm ngưỡng phía trên và khối `--xuat` phía dưới.)

- [ ] **Step 6:** Run Step 2 → PASS (9 test). Rồi `DB_HOST=127.0.0.1 vendor/bin/phpunit tests/Unit/Xml3176/VatPhamKemDvkt` → xanh. Lưu ý `VatPhamKemDvktLenhTest` cũ: nếu một ca lệnh học (không `--thu`) đỏ vì thiếu bảng `xml3176_vpkd_lan_hoc`/`xml3176_vpkd_ung_vien`, thêm 3 migration Task 1 vào danh sách `bootXml3176Sqlite` của test đó (không đổi assert).

- [ ] **Step 7: Commit**

```bash
git add app/Services/Xml3176/VatPhamKemDvktHocLaiService.php app/Jobs/HocVatPhamKemDvktJob.php app/Console/Commands/Xml3176HocVatPhamKemDvkt.php tests/Unit/Xml3176/VatPhamKemDvkt
git commit -m "feat(xml3176): hoc lai bang tra chay nen co lich su luot hoc va ung vien

Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>"
```

---

### Task 4: Service thao tác bảng tra `VatPhamKemDvktQuanTri`

**Files:**
- Create: `app/Services/Xml3176/VatPhamKemDvktQuanTri.php`
- Test: `tests/Unit/Xml3176/VatPhamKemDvkt/QuanTriServiceTest.php`

**Interfaces — Produces** (mọi lỗi đầu vào ném `\InvalidArgumentException` với thông điệp đúng Global Constraints):
- `datHieuLuc(Xml3176VatPhamKemDvkt $m, bool $hieuLuc, $ghiChu, $nguoiSuaId): void`
- `datGhiChu(Xml3176VatPhamKemDvkt $m, $ghiChu, $nguoiSuaId): void`
- `xoa(Xml3176VatPhamKemDvkt $m): void`
- `themDongTay($loai, $ma, $ten, array $dsMaDvkt, array $tenDvkt, $ghiChu, $nguoiSuaId): array` → `['them' => string[], 'da_co' => string[]]`
- `kiemKhop($loai, $ma, Carbon $bayGio = null): array` → `['ma_nhom_chung' => bool, 'ds' => [['ten','ten_khop','so_dong','so_dong_bhyt','so_ho_so'], ...]]`
- `timVatPham($loai, $q): array`, `timDvkt($q): array` → `[['id' => ma, 'text' => 'ma — ten'], ...]` (≤ 30)
- `const THANG_KIEM_KHOP = 12;`

- [ ] **Step 1: Test (đỏ)** — `tests/Unit/Xml3176/VatPhamKemDvkt/QuanTriServiceTest.php`:

```php
<?php

namespace Tests\Unit\Xml3176\VatPhamKemDvkt;

use App\Models\BHYT\Xml3176VatPhamKemDvkt;
use App\Services\Xml3176\VatPhamKemDvktQuanTri;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\Support\Xml3176RuleTestSupport;
use Tests\TestCase;

class QuanTriServiceTest extends TestCase
{
    use Xml3176RuleTestSupport;

    protected function setUp()
    {
        parent::setUp();
        $this->bootXml3176Sqlite([
            '2024_06_12_142339_create_danh_muc_thuoc_table.php',
            '2024_06_12_143949_create_danh_muc_vat_tu_table.php',
            '2024_06_12_145200_create_danh_muc_dich_vu_table.php',
            '2026_01_09_152826_create_xml3176_xml2s_table.php',
            '2026_01_09_152832_create_xml3176_xml3s_table.php',
            '2026_10_13_090000_create_xml3176_vat_pham_kem_dvkt_table.php',
            '2026_10_14_090000_them_nguoi_sua_vao_xml3176_vat_pham_kem_dvkt.php',
        ]);
    }

    private function sv(): VatPhamKemDvktQuanTri
    {
        return new VatPhamKemDvktQuanTri();
    }

    private function dong(array $f = []): Xml3176VatPhamKemDvkt
    {
        return Xml3176VatPhamKemDvkt::create($f + [
            'loai' => 'VTYT', 'ma_vat_pham' => 'N08.00.150', 'ten_khop' => 'buồng tiêm truyền',
            'ma_dvkt' => '12.0448.1187', 'nguon' => 'HOC', 'hieu_luc' => true,
        ]);
    }

    private function loi(callable $f): string
    {
        try {
            $f();
        } catch (\InvalidArgumentException $e) {
            return $e->getMessage();
        }
        $this->fail('Khong nem InvalidArgumentException');
    }

    /** @test */
    public function tat_bat_buoc_ly_do_va_ghi_vet()
    {
        $m = $this->dong();

        $this->assertSame('Cần ghi lý do khi tắt', $this->loi(fn() => $this->sv()->datHieuLuc($m, false, '  ', 7)));

        $this->sv()->datHieuLuc($m, false, 'Cặp sai chuyên môn', 7);
        $m = $m->fresh();
        $this->assertFalse($m->hieu_luc);
        $this->assertSame('Cặp sai chuyên môn', $m->ghi_chu);
        $this->assertSame(7, $m->nguoi_sua_id);
        $this->assertNotNull($m->sua_luc);

        $this->sv()->datHieuLuc($m, true, '', 8);
        $this->assertTrue($m->fresh()->hieu_luc);
        $this->assertNull($m->fresh()->ghi_chu);
    }

    /** @test */
    public function ghi_chu_rong_thanh_null_va_gioi_han_do_dai()
    {
        $m = $this->dong(['ghi_chu' => 'cu']);

        $this->sv()->datGhiChu($m, '', 7);
        $this->assertNull($m->fresh()->ghi_chu);
        $this->assertSame('Ghi chú tối đa 1024 ký tự', $this->loi(fn() => $this->sv()->datGhiChu($m, str_repeat('a', 1025), 7)));
    }

    /** @test */
    public function chi_xoa_duoc_dong_tay()
    {
        $hoc = $this->dong();
        $tay = $this->dong(['ma_dvkt' => '12.0448.1188', 'nguon' => 'TAY']);

        $this->assertSame('Chỉ xoá được dòng bổ sung tay (TAY); dòng học tự động hãy tắt hiệu lực', $this->loi(fn() => $this->sv()->xoa($hoc)));
        $this->sv()->xoa($tay);
        $this->assertSame(1, Xml3176VatPhamKemDvkt::count());
    }

    /** @test */
    public function them_dong_tay_nhieu_dvkt_chuan_hoa_va_lay_ten_danh_muc()
    {
        DB::table('service_catalogs')->insert(['ma_dich_vu' => '20.0053.0105', 'ten_dich_vu' => 'Nội soi đặt stent']);

        $kq = $this->sv()->themDongTay('vtyt', ' N06.02.100 ', 'Stent đại tràng', ['20.0053.0105', '20.0054.0105_GT'], [], 'thêm', 7);

        $this->assertSame(['20.0053.0105', '20.0054.0105'], $kq['them']);
        $m = Xml3176VatPhamKemDvkt::where('ma_dvkt', '20.0053.0105')->first();
        $this->assertSame('TAY', $m->nguon);
        $this->assertSame('N06.02.100', $m->ma_vat_pham);
        $this->assertSame('stent đại tràng', $m->ten_khop);
        $this->assertSame('Nội soi đặt stent', $m->ten_dvkt);
        $this->assertSame(0, $m->so_lan_gan);
        $this->assertSame(7, $m->nguoi_sua_id);
    }

    /** @test */
    public function them_dong_tay_kiem_dau_vao()
    {
        $sv = $this->sv();
        $this->assertSame('Loại không hợp lệ (VTYT hoặc THUOC)', $this->loi(fn() => $sv->themDongTay('X', 'a', '', ['b'], [], null, 7)));
        $this->assertSame('Mã vật phẩm phải từ 1 đến 100 ký tự', $this->loi(fn() => $sv->themDongTay('THUOC', ' ', '', ['b'], [], null, 7)));
        $this->assertSame('VTYT mã nhóm chung cần tên vật phẩm đúng như trên XML', $this->loi(fn() => $sv->themDongTay('VTYT', 'N08.00.150', '', ['b'], [], null, 7)));
        $this->assertSame('Mã DVKT phải từ 1 đến 50 ký tự', $this->loi(fn() => $sv->themDongTay('THUOC', '40.644', '', [str_repeat('9', 51)], [], null, 7)));
        $this->assertSame('Chưa chọn DVKT mẹ', $this->loi(fn() => $sv->themDongTay('THUOC', '40.644', '', [], [], null, 7)));
    }

    /** @test */
    public function them_trung_mot_phan_va_trung_toan_bo()
    {
        $this->dong();

        $kq = $this->sv()->themDongTay('VTYT', 'N08.00.150', 'Buồng tiêm truyền', ['12.0448.1187', '12.0448.1188'], [], null, 7);
        $this->assertSame(['12.0448.1188'], $kq['them']);
        $this->assertSame(['12.0448.1187'], $kq['da_co']);

        $this->assertSame('Tất cả cặp đã có trong bảng tra: 12.0448.1187, 12.0448.1188',
            $this->loi(fn() => $this->sv()->themDongTay('VTYT', 'N08.00.150', 'Buồng tiêm truyền', ['12.0448.1187', '12.0448.1188'], [], null, 7)));
    }

    /** @test */
    public function kiem_khop_tra_ten_tren_xml_12_thang()
    {
        $bayGio = Carbon::create(2026, 10, 14);
        $dong = function ($lk, $stt, $ten, $bhtt, $ngay) {
            return ['ma_lk' => $lk, 'stt' => $stt, 'ma_vat_tu' => 'N04.04.010', 'ten_vat_tu' => $ten,
                't_bhtt' => $bhtt, 'so_luong' => 1, 'ngay_yl' => $ngay];
        };
        DB::table('xml3176_xml3s')->insert([
            $dong('A', 1, 'Ống dẫn lưu đuôi lợn', 1000, '202609010800'),
            $dong('A', 2, 'Ống dẫn lưu đuôi lợn', 0, '202609010800'),
            $dong('B', 1, 'Ống dẫn lưu đuôi lợn', 1000, '202608010800'),
            $dong('C', 1, 'Catheter 4 cực', 1000, '202609010800'),
            $dong('D', 1, 'Ống dẫn lưu đuôi lợn', 1000, '202509010800'), // ngoai 12 thang
        ]);

        $kq = $this->sv()->kiemKhop('VTYT', 'N04.04.010', $bayGio);

        $this->assertTrue($kq['ma_nhom_chung']);
        $this->assertSame('Ống dẫn lưu đuôi lợn', $kq['ds'][0]['ten']);
        $this->assertSame('ống dẫn lưu đuôi lợn', $kq['ds'][0]['ten_khop']);
        $this->assertSame(3, $kq['ds'][0]['so_dong']);
        $this->assertSame(2, $kq['ds'][0]['so_dong_bhyt']);
        $this->assertSame(2, $kq['ds'][0]['so_ho_so']);
        $this->assertCount(2, $kq['ds']);
    }

    /** @test */
    public function tim_vat_pham_va_dvkt_tu_danh_muc()
    {
        DB::table('medical_supply_catalogs')->insert(['ma_vat_tu' => 'N08.00.150', 'ten_vat_tu' => 'Buồng tiêm truyền']);
        DB::table('medicine_catalogs')->insert(['ma_thuoc' => '40.644', 'ten_thuoc' => 'Omnipaque']);
        DB::table('service_catalogs')->insert(['ma_dich_vu' => '12.0448.1187', 'ten_dich_vu' => 'Đặt buồng tiêm truyền']);

        $this->assertSame([['id' => 'N08.00.150', 'text' => 'N08.00.150 — Buồng tiêm truyền']], $this->sv()->timVatPham('VTYT', 'buồng'));
        $this->assertSame('40.644', $this->sv()->timVatPham('THUOC', '40.6')[0]['id']);
        $this->assertSame('12.0448.1187', $this->sv()->timDvkt('12.0448')[0]['id']);
        $this->assertSame([], $this->sv()->timDvkt(' '));
    }
}
```

Lưu ý: nếu `insert` vào `medical_supply_catalogs`/`medicine_catalogs`/`service_catalogs` lỗi vì cột NOT NULL khác, đọc migration 2024 tương ứng và bổ sung giá trị tối thiểu cho các cột đó trong mảng insert — **không** sửa migration.

- [ ] **Step 2:** Run `DB_HOST=127.0.0.1 vendor/bin/phpunit tests/Unit/Xml3176/VatPhamKemDvkt/QuanTriServiceTest.php` → FAIL (class không có).

- [ ] **Step 3: Service** — `app/Services/Xml3176/VatPhamKemDvktQuanTri.php`:

```php
<?php

namespace App\Services\Xml3176;

use App\Models\BHYT\MedicalSupplyCatalog;
use App\Models\BHYT\MedicineCatalog;
use App\Models\BHYT\ServiceCatalog;
use App\Models\BHYT\Xml3176VatPhamKemDvkt;
use App\Services\Xml3176\Support\VatPhamKemDvktKey;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Thao tac tren bang tra vat pham kem DVKT me tu man quan tri. Loi dau vao nem
 * InvalidArgumentException (controller doi thanh 422). Moi thao tac ghi vet nguoi sua.
 */
class VatPhamKemDvktQuanTri
{
    const THANG_KIEM_KHOP = 12;
    const TOI_DA_TEN_KHOP = 50;
    const TOI_DA_TIM = 30;

    public function datHieuLuc(Xml3176VatPhamKemDvkt $m, bool $hieuLuc, $ghiChu, $nguoiSuaId): void
    {
        $ghiChu = self::ghiChuHopLe($ghiChu);
        if (!$hieuLuc && $ghiChu === null) {
            throw new \InvalidArgumentException('Cần ghi lý do khi tắt');
        }
        $m->fill(['hieu_luc' => $hieuLuc, 'ghi_chu' => $ghiChu] + self::vet($nguoiSuaId))->save();
    }

    public function datGhiChu(Xml3176VatPhamKemDvkt $m, $ghiChu, $nguoiSuaId): void
    {
        $m->fill(['ghi_chu' => self::ghiChuHopLe($ghiChu)] + self::vet($nguoiSuaId))->save();
    }

    public function xoa(Xml3176VatPhamKemDvkt $m): void
    {
        if ($m->nguon !== Xml3176VatPhamKemDvkt::NGUON_TAY) {
            // Xoa dong HOC thi lan hoc sau them lai - chi cho tat.
            throw new \InvalidArgumentException('Chỉ xoá được dòng bổ sung tay (TAY); dòng học tự động hãy tắt hiệu lực');
        }
        $m->delete();
    }

    public function themDongTay($loai, $ma, $ten, array $dsMaDvkt, array $tenDvkt, $ghiChu, $nguoiSuaId): array
    {
        $loai = self::loaiHopLe($loai);
        $ma = trim((string) $ma);
        if ($ma === '' || mb_strlen($ma, 'UTF-8') > 100) {
            throw new \InvalidArgumentException('Mã vật phẩm phải từ 1 đến 100 ký tự');
        }
        $ten = trim((string) $ten);
        if ($loai === Xml3176VatPhamKemDvkt::LOAI_VTYT && VatPhamKemDvktKey::laMaNhomChung($ma) && $ten === '') {
            throw new \InvalidArgumentException('VTYT mã nhóm chung cần tên vật phẩm đúng như trên XML');
        }
        $ghiChu = self::ghiChuHopLe($ghiChu);

        $maDv = [];
        foreach ($dsMaDvkt as $x) {
            $k = VatPhamKemDvktKey::chuanHoaMaDvkt($x);
            if ($k === '' || mb_strlen($k, 'UTF-8') > 50) {
                throw new \InvalidArgumentException('Mã DVKT phải từ 1 đến 50 ký tự');
            }
            $maDv[$k] = true;
        }
        if (empty($maDv)) {
            throw new \InvalidArgumentException('Chưa chọn DVKT mẹ');
        }
        $maDv = array_keys($maDv);

        $tenKhop = VatPhamKemDvktKey::tenKhop($loai, $ma, $ten);
        $tenDanhMuc = ServiceCatalog::whereIn('ma_dich_vu', $maDv)->pluck('ten_dich_vu', 'ma_dich_vu')->all();
        $kq = ['them' => [], 'da_co' => []];

        DB::transaction(function () use ($loai, $ma, $ten, $tenKhop, $maDv, $tenDvkt, $tenDanhMuc, $ghiChu, $nguoiSuaId, &$kq) {
            foreach ($maDv as $k) {
                $co = Xml3176VatPhamKemDvkt::where([
                    'loai' => $loai, 'ma_vat_pham' => $ma, 'ten_khop' => $tenKhop, 'ma_dvkt' => $k,
                ])->exists();
                if ($co) {
                    $kq['da_co'][] = $k;
                    continue;
                }
                Xml3176VatPhamKemDvkt::create([
                    'loai' => $loai, 'ma_vat_pham' => $ma, 'ten_vat_pham' => $ten === '' ? null : $ten,
                    'ten_khop' => $tenKhop, 'ma_dvkt' => $k,
                    'ten_dvkt' => $tenDvkt[$k] ?? $tenDanhMuc[$k] ?? null,
                    'nguon' => Xml3176VatPhamKemDvkt::NGUON_TAY, 'so_lan_gan' => 0, 'ty_le_gan' => null,
                    'hieu_luc' => true, 'ghi_chu' => $ghiChu,
                ] + self::vet($nguoiSuaId));
                $kq['them'][] = $k;
            }
        });

        if (empty($kq['them'])) {
            throw new \InvalidArgumentException('Tất cả cặp đã có trong bảng tra: ' . implode(', ', $kq['da_co']));
        }

        return $kq;
    }

    /**
     * Ten that cua ma vat pham tren XML 12 thang gan nhat. VTYT ma nhom chung khop theo ma+ten,
     * nen nguoi duyet phai chon dung mot ten o day - go ten lech thi dong tay khong bao gio khop.
     */
    public function kiemKhop($loai, $ma, Carbon $bayGio = null): array
    {
        $loai = self::loaiHopLe($loai);
        $ma = trim((string) $ma);
        if ($ma === '') {
            throw new \InvalidArgumentException('Mã vật phẩm phải từ 1 đến 100 ký tự');
        }
        $n = VatPhamKemDvktLearner::NGUON[$loai];
        $tu = ($bayGio ?: Carbon::now())->copy()->subMonths(self::THANG_KIEM_KHOP)->format('Ymd');

        $rows = DB::table($n['bang'])
            ->selectRaw("{$n['ten']} as ten, COUNT(*) as so_dong, "
                . 'SUM(CASE WHEN t_bhtt > 0 THEN 1 ELSE 0 END) as so_dong_bhyt, COUNT(DISTINCT ma_lk) as so_ho_so')
            ->where($n['ma'], $ma)
            ->where('ngay_yl', '>=', $tu)
            ->groupBy($n['ten'])
            ->orderBy('so_dong', 'desc')
            ->limit(self::TOI_DA_TEN_KHOP)
            ->get();

        $ds = [];
        foreach ($rows as $r) {
            $ds[] = [
                'ten' => (string) $r->ten,
                'ten_khop' => VatPhamKemDvktKey::tenKhop($loai, $ma, $r->ten),
                'so_dong' => (int) $r->so_dong,
                'so_dong_bhyt' => (int) $r->so_dong_bhyt,
                'so_ho_so' => (int) $r->so_ho_so,
            ];
        }

        return [
            'ma_nhom_chung' => $loai === Xml3176VatPhamKemDvkt::LOAI_VTYT && VatPhamKemDvktKey::laMaNhomChung($ma),
            'ds' => $ds,
        ];
    }

    public function timVatPham($loai, $q): array
    {
        $loai = self::loaiHopLe($loai);
        if ($loai === Xml3176VatPhamKemDvkt::LOAI_VTYT) {
            return self::tim(MedicalSupplyCatalog::query(), 'ma_vat_tu', 'ten_vat_tu', $q);
        }

        return self::tim(MedicineCatalog::query(), 'ma_thuoc', 'ten_thuoc', $q);
    }

    public function timDvkt($q): array
    {
        return self::tim(ServiceCatalog::query(), 'ma_dich_vu', 'ten_dich_vu', $q);
    }

    private static function tim($query, string $cotMa, string $cotTen, $q): array
    {
        $q = trim((string) $q);
        if ($q === '') {
            return [];
        }

        $rows = $query->select($cotMa . ' as ma', $cotTen . ' as ten')
            ->where(function ($w) use ($cotMa, $cotTen, $q) {
                $w->where($cotMa, 'like', $q . '%')->orWhere($cotTen, 'like', '%' . $q . '%');
            })
            ->groupBy($cotMa, $cotTen)
            ->orderBy($cotMa)
            ->limit(self::TOI_DA_TIM)
            ->get();

        $ra = [];
        foreach ($rows as $r) {
            $ra[] = ['id' => (string) $r->ma, 'text' => $r->ma . ' — ' . $r->ten];
        }

        return $ra;
    }

    private static function loaiHopLe($loai): string
    {
        $loai = strtoupper(trim((string) $loai));
        if (!in_array($loai, [Xml3176VatPhamKemDvkt::LOAI_VTYT, Xml3176VatPhamKemDvkt::LOAI_THUOC], true)) {
            throw new \InvalidArgumentException('Loại không hợp lệ (VTYT hoặc THUOC)');
        }

        return $loai;
    }

    private static function ghiChuHopLe($ghiChu)
    {
        $ghiChu = trim((string) $ghiChu);
        if ($ghiChu === '') {
            return null;
        }
        if (mb_strlen($ghiChu, 'UTF-8') > 1024) {
            throw new \InvalidArgumentException('Ghi chú tối đa 1024 ký tự');
        }

        return $ghiChu;
    }

    private static function vet($nguoiSuaId): array
    {
        return ['nguoi_sua_id' => $nguoiSuaId === null ? null : (int) $nguoiSuaId, 'sua_luc' => Carbon::now()];
    }
}
```

- [ ] **Step 4:** Run Step 2 → PASS (8 test).

- [ ] **Step 5: Commit**

```bash
git add app/Services/Xml3176/VatPhamKemDvktQuanTri.php tests/Unit/Xml3176/VatPhamKemDvkt/QuanTriServiceTest.php
git commit -m "feat(xml3176): service thao tac bang tra vat pham kem DVKT (bat tat, them tay, kiem khop)

Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>"
```

---

### Task 5: Controller, route, menu

**Files:**
- Create: `app/Http/Controllers/Category/VatPhamKemDvktController.php`
- Modify: `routes/web.php` (nhóm `checkrole:category-manager`, ngay sau route `category-bhyt.update-xml3176-error-catalog`); `config/adminlte.php` (mục menu); `tests/Unit/MenuDanhMucBhytTest.php`
- Test: `tests/Unit/Xml3176/VatPhamKemDvkt/QuanTriControllerTest.php`

**Interfaces:**
- Consumes: Task 3 `VatPhamKemDvktHocLaiService` (`yeuCau`, `danhDauTreo`, `ganNhat`, `moTa`); Task 4 `VatPhamKemDvktQuanTri`; Task 2 `nap(.., $nguoiSuaId)`; `App\Exports\VatPhamKemDvktExport`, `App\Imports\VatPhamKemDvktImport`, `App\Exports\LoiNhapDanhMucExport` (sẵn có).
- Produces: route `category-bhyt.vat-pham-kem-dvkt.{index,datatable,ung-vien-datatable,hieu-luc,ghi-chu,xoa,kiem-khop,tim-vat-pham,tim-dvkt,them,ung-vien-them,hoc-lai,trang-thai,xuat,nhap,nhap-loi}`; view name `category.bhyt.vat_pham_kem_dvkt` (tạo ở Task 6) nhận biến `$lanHoc` (mảng `moTa` hoặc null).

- [ ] **Step 1: Test (đỏ)** — `tests/Unit/Xml3176/VatPhamKemDvkt/QuanTriControllerTest.php`:

```php
<?php

namespace Tests\Unit\Xml3176\VatPhamKemDvkt;

use App\Http\Controllers\Category\VatPhamKemDvktController;
use App\Models\BHYT\Xml3176VatPhamKemDvkt;
use App\Models\BHYT\Xml3176VpkdUngVien;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Queue;
use Route;
use Tests\Support\Xml3176RuleTestSupport;
use Tests\TestCase;

class QuanTriControllerTest extends TestCase
{
    use Xml3176RuleTestSupport;

    const ROUTES = ['index', 'datatable', 'ung-vien-datatable', 'hieu-luc', 'ghi-chu', 'xoa', 'kiem-khop',
        'tim-vat-pham', 'tim-dvkt', 'them', 'ung-vien-them', 'hoc-lai', 'trang-thai', 'xuat', 'nhap', 'nhap-loi'];

    protected function setUp()
    {
        parent::setUp();
        $this->bootXml3176Sqlite([
            '2014_10_12_000000_create_users_table.php',
            '2024_06_12_145200_create_danh_muc_dich_vu_table.php',
            '2026_01_09_152826_create_xml3176_xml2s_table.php',
            '2026_01_09_152832_create_xml3176_xml3s_table.php',
            '2026_10_13_090000_create_xml3176_vat_pham_kem_dvkt_table.php',
            '2026_10_14_090000_them_nguoi_sua_vao_xml3176_vat_pham_kem_dvkt.php',
            '2026_10_14_090100_create_xml3176_vpkd_lan_hoc_table.php',
            '2026_10_14_090200_create_xml3176_vpkd_ung_vien_table.php',
        ]);
        $u = new User();
        $u->id = 7;
        Auth::setUser($u);
    }

    private function c(): VatPhamKemDvktController
    {
        return $this->app->make(VatPhamKemDvktController::class);
    }

    private function dong(array $f = []): Xml3176VatPhamKemDvkt
    {
        return Xml3176VatPhamKemDvkt::create($f + [
            'loai' => 'THUOC', 'ma_vat_pham' => '40.644', 'ten_khop' => '', 'ma_dvkt' => '18.0150.0041', 'nguon' => 'HOC',
        ]);
    }

    /** @test */
    public function moi_route_co_auth_va_category_manager()
    {
        foreach (self::ROUTES as $ten) {
            $r = Route::getRoutes()->getByName('category-bhyt.vat-pham-kem-dvkt.' . $ten);
            $this->assertNotNull($r, "Thieu route $ten");
            $mw = $r->gatherMiddleware();
            $this->assertContains('auth', $mw, "Route $ten mat xac thuc");
            $this->assertContains('checkrole:category-manager', $mw, "Route $ten sai quyen");
        }
    }

    /** @test */
    public function hieu_luc_tat_thieu_ly_do_422_co_ly_do_ghi_vet()
    {
        $m = $this->dong();

        $r = $this->c()->hieuLuc(Request::create('/', 'POST', ['hieu_luc' => 0]), $m->id);
        $this->assertSame(422, $r->getStatusCode());
        $this->assertSame('Cần ghi lý do khi tắt', $r->getData()->message);

        $r = $this->c()->hieuLuc(Request::create('/', 'POST', ['hieu_luc' => 0, 'ghi_chu' => 'sai']), $m->id);
        $this->assertSame(200, $r->getStatusCode());
        $this->assertSame(7, $m->fresh()->nguoi_sua_id);
        $this->assertFalse($m->fresh()->hieu_luc);
    }

    /** @test */
    public function id_khong_co_tra_404()
    {
        $this->assertSame(404, $this->c()->hieuLuc(Request::create('/', 'POST', ['hieu_luc' => 1]), 999)->getStatusCode());
        $this->assertSame(404, $this->c()->ghiChu(Request::create('/', 'POST', []), 999)->getStatusCode());
        $this->assertSame(404, $this->c()->xoa(999)->getStatusCode());
    }

    /** @test */
    public function xoa_dong_hoc_422_dong_tay_ok()
    {
        $this->assertSame(422, $this->c()->xoa($this->dong()->id)->getStatusCode());
        $this->assertSame(200, $this->c()->xoa($this->dong(['ma_dvkt' => 'X', 'nguon' => 'TAY'])->id)->getStatusCode());
    }

    /** @test */
    public function them_tra_thong_diep_va_bao_da_co()
    {
        $this->dong();
        $r = $this->c()->them(Request::create('/', 'POST', [
            'loai' => 'THUOC', 'ma_vat_pham' => '40.644', 'ma_dvkt' => ['18.0150.0041', '18.0156.0041'],
        ]));

        $this->assertSame(200, $r->getStatusCode());
        $this->assertSame('Đã thêm 1 dòng. Đã có: 18.0150.0041', $r->getData()->message);
    }

    /** @test */
    public function ung_vien_them_chi_nhan_dvkt_cua_ung_vien()
    {
        $uv = Xml3176VpkdUngVien::create([
            'lan_hoc_id' => 3, 'loai' => 'THUOC', 'ma_vat_pham' => '40.644', 'ten_vat_pham' => 'Omnipaque', 'ten_khop' => '',
            'so_dong' => 10, 'so_lien_ket' => 6, 'ty_le_gan' => 0.6,
            'dvkt' => [['ma' => '18.0150.0041', 'ten' => 'CLVT có cản quang', 'so_lan' => 6]],
        ]);

        $r = $this->c()->ungVienThem(Request::create('/', 'POST', ['ma_dvkt' => ['18.0150.0041', '99.9999.9999']]), $uv->id);
        $this->assertSame(422, $r->getStatusCode());
        $this->assertSame('DVKT không thuộc ứng viên: 99.9999.9999', $r->getData()->message);

        $r = $this->c()->ungVienThem(Request::create('/', 'POST', ['ma_dvkt' => ['18.0150.0041']]), $uv->id);
        $this->assertSame(200, $r->getStatusCode());
        $m = Xml3176VatPhamKemDvkt::first();
        $this->assertSame('TAY', $m->nguon);
        $this->assertSame('CLVT có cản quang', $m->ten_dvkt);
        $this->assertSame('Duyệt từ ứng viên lượt #3', $m->ghi_chu);
    }

    /** @test */
    public function hoc_lai_va_trang_thai_tra_mo_ta()
    {
        Queue::fake();

        $d = $this->c()->hocLai()->getData(true);
        $this->assertTrue($d['moi']);
        $this->assertTrue($d['lan_hoc']['dang_chay']);

        $d2 = $this->c()->hocLai()->getData(true);
        $this->assertFalse($d2['moi']);

        $t = $this->c()->trangThai()->getData(true);
        $this->assertSame('CHO', $t['lan_hoc']['trang_thai']);
    }

    /** @test */
    public function kiem_khop_loai_sai_422()
    {
        $r = $this->c()->kiemKhop(Request::create('/', 'GET', ['loai' => 'X', 'ma' => 'a']));
        $this->assertSame(422, $r->getStatusCode());
    }
}
```

- [ ] **Step 2: Menu test (đỏ)** — trong `tests/Unit/MenuDanhMucBhytTest.php`, thêm method vào cuối class:

```php
    /** @test */
    public function muc_vat_pham_kem_dvkt_dat_ngay_sau_dm_loi_xml_3176()
    {
        $khoi = $this->khoiBhyt();

        $loi = $this->viTri($khoi, 'DM lỗi Xml 3176');
        $vp = $this->viTri($khoi, 'DM vật phẩm kèm DVKT mẹ');

        $this->assertNotSame(-1, $vp, 'Thieu muc DM vat pham kem DVKT me');
        $this->assertSame($loi + 1, $vp);
        $this->assertSame('category-bhyt.vat-pham-kem-dvkt.index', array_values($khoi)[$vp]['route']);
    }
```

- [ ] **Step 3:** Run `DB_HOST=127.0.0.1 vendor/bin/phpunit tests/Unit/Xml3176/VatPhamKemDvkt/QuanTriControllerTest.php tests/Unit/MenuDanhMucBhytTest.php` → FAIL.

- [ ] **Step 4: Controller** — `app/Http/Controllers/Category/VatPhamKemDvktController.php`:

```php
<?php

namespace App\Http\Controllers\Category;

use App\Exports\LoiNhapDanhMucExport;
use App\Exports\VatPhamKemDvktExport;
use App\Http\Controllers\Controller;
use App\Imports\VatPhamKemDvktImport;
use App\Models\BHYT\Xml3176VatPhamKemDvkt;
use App\Models\BHYT\Xml3176VpkdUngVien;
use App\Services\Xml3176\VatPhamKemDvktHocLaiService;
use App\Services\Xml3176\VatPhamKemDvktLearner;
use App\Services\Xml3176\VatPhamKemDvktQuanTri;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use Yajra\Datatables\Datatables;

/**
 * Man quan tri bang tra "Vat pham kem DVKT me" (XML3176). Controller mong: nghiep vu o
 * VatPhamKemDvktQuanTri / VatPhamKemDvktHocLaiService / VatPhamKemDvktLearner;
 * InvalidArgumentException -> 422.
 */
class VatPhamKemDvktController extends Controller
{
    private $quanTri;
    private $hocLai;
    private $learner;

    public function __construct(VatPhamKemDvktQuanTri $quanTri, VatPhamKemDvktHocLaiService $hocLai, VatPhamKemDvktLearner $learner)
    {
        $this->quanTri = $quanTri;
        $this->hocLai = $hocLai;
        $this->learner = $learner;
    }

    public function index()
    {
        $this->hocLai->danhDauTreo();

        return view('category.bhyt.vat_pham_kem_dvkt', ['lanHoc' => $this->hocLai->moTa($this->hocLai->ganNhat())]);
    }

    public function datatable(Request $request)
    {
        $q = Xml3176VatPhamKemDvkt::query()
            ->leftJoin('users', 'users.id', '=', 'xml3176_vat_pham_kem_dvkt.nguoi_sua_id')
            ->select('xml3176_vat_pham_kem_dvkt.*', 'users.name as nguoi_sua');
        foreach (['loai', 'nguon'] as $cot) {
            if ($request->filled($cot)) {
                $q->where('xml3176_vat_pham_kem_dvkt.' . $cot, $request->input($cot));
            }
        }
        if ($request->filled('hieu_luc')) {
            $q->where('xml3176_vat_pham_kem_dvkt.hieu_luc', (int) $request->input('hieu_luc'));
        }

        return Datatables::of($q)
            ->editColumn('ty_le_gan', function ($m) {
                return $m->ty_le_gan === null ? '' : number_format($m->ty_le_gan * 100, 1, ',', '.') . '%';
            })
            ->editColumn('hieu_luc', function ($m) {
                return '<input type="checkbox" class="vpkd-hieu-luc" data-id="' . $m->id . '"'
                    . ' data-ghi-chu="' . e($m->ghi_chu) . '"' . ($m->hieu_luc ? ' checked' : '') . '>';
            })
            ->editColumn('ghi_chu', function ($m) {
                return e($m->ghi_chu) . ' <a href="#" class="vpkd-ghi-chu" data-id="' . $m->id . '" data-ghi-chu="'
                    . e($m->ghi_chu) . '" title="Sửa ghi chú"><i class="fa fa-pencil"></i></a>';
            })
            ->addColumn('nguoi_sua_luc', function ($m) {
                return $m->sua_luc ? e($m->nguoi_sua ?: '#' . $m->nguoi_sua_id) . '<br>' . $m->sua_luc->format('d/m/Y H:i') : '';
            })
            ->addColumn('thao_tac', function ($m) {
                return $m->nguon === Xml3176VatPhamKemDvkt::NGUON_TAY
                    ? '<button class="btn btn-xs btn-danger vpkd-xoa" data-id="' . $m->id . '"><i class="fa fa-trash"></i></button>'
                    : '';
            })
            ->rawColumns(['hieu_luc', 'ghi_chu', 'nguoi_sua_luc', 'thao_tac'])
            ->toJson();
    }

    public function ungVienDatatable(Request $request)
    {
        $q = Xml3176VpkdUngVien::query();
        if ($request->filled('loai')) {
            $q->where('loai', $request->input('loai'));
        }

        // Cac cap da co trong bang tra (moi nguon) - danh dau "da co" tren o tick.
        $daCo = [];
        foreach (Xml3176VatPhamKemDvkt::get(['loai', 'ma_vat_pham', 'ten_khop', 'ma_dvkt']) as $m) {
            $daCo[$m->loai . '|' . $m->ma_vat_pham . '|' . $m->ten_khop . '|' . $m->ma_dvkt] = true;
        }

        return Datatables::of($q)
            ->editColumn('ty_le_gan', function ($u) {
                return number_format($u->ty_le_gan * 100, 1, ',', '.') . '%';
            })
            ->editColumn('dvkt', function ($u) use ($daCo) {
                $html = '';
                foreach ((array) $u->dvkt as $d) {
                    $co = isset($daCo[$u->loai . '|' . $u->ma_vat_pham . '|' . $u->ten_khop . '|' . $d['ma']]);
                    $html .= '<label style="display:block;font-weight:normal"><input type="checkbox" class="vpkd-uv-dv" data-uv="'
                        . $u->id . '" value="' . e($d['ma']) . '"' . ($co ? ' checked disabled' : '') . '> '
                        . e($d['ma']) . ' — ' . e($d['ten']) . ' (' . (int) $d['so_lan'] . ' lần)'
                        . ($co ? ' <span class="label label-default">đã có</span>' : '') . '</label>';
                }

                return $html;
            })
            ->addColumn('thao_tac', function ($u) {
                return '<button class="btn btn-xs btn-primary vpkd-uv-them" data-id="' . $u->id . '">Thêm thành dòng tay</button>';
            })
            ->rawColumns(['dvkt', 'thao_tac'])
            ->toJson();
    }

    public function hieuLuc(Request $request, $id)
    {
        return $this->voiDong($id, function ($m) use ($request) {
            $this->quanTri->datHieuLuc($m, (bool) (int) $request->input('hieu_luc'), $request->input('ghi_chu'), Auth::id());
        });
    }

    public function ghiChu(Request $request, $id)
    {
        return $this->voiDong($id, function ($m) use ($request) {
            $this->quanTri->datGhiChu($m, $request->input('ghi_chu'), Auth::id());
        });
    }

    public function xoa($id)
    {
        return $this->voiDong($id, function ($m) {
            $this->quanTri->xoa($m);
        });
    }

    public function kiemKhop(Request $request)
    {
        return $this->bat(function () use ($request) {
            return response()->json($this->quanTri->kiemKhop($request->input('loai'), $request->input('ma')));
        });
    }

    public function timVatPham(Request $request)
    {
        return $this->bat(function () use ($request) {
            return response()->json(['results' => $this->quanTri->timVatPham($request->input('loai'), $request->input('q'))]);
        });
    }

    public function timDvkt(Request $request)
    {
        return response()->json(['results' => $this->quanTri->timDvkt($request->input('q'))]);
    }

    public function them(Request $request)
    {
        return $this->bat(function () use ($request) {
            $kq = $this->quanTri->themDongTay(
                $request->input('loai'), $request->input('ma_vat_pham'), $request->input('ten_vat_pham'),
                (array) $request->input('ma_dvkt', []), [], $request->input('ghi_chu'), Auth::id()
            );

            return response()->json(['message' => self::thongDiepThem($kq)]);
        });
    }

    public function ungVienThem(Request $request, $id)
    {
        $uv = Xml3176VpkdUngVien::find($id);
        if ($uv === null) {
            return response()->json(['message' => 'Không tìm thấy ứng viên'], 404);
        }

        return $this->bat(function () use ($request, $uv) {
            $ten = [];
            foreach ((array) $uv->dvkt as $d) {
                $ten[$d['ma']] = $d['ten'];
            }
            $chon = array_values(array_unique(array_map('strval', (array) $request->input('ma_dvkt', []))));
            $la = array_values(array_diff($chon, array_keys($ten)));
            if (!empty($la)) {
                throw new \InvalidArgumentException('DVKT không thuộc ứng viên: ' . implode(', ', $la));
            }
            $kq = $this->quanTri->themDongTay(
                $uv->loai, $uv->ma_vat_pham, $uv->ten_vat_pham, $chon, $ten,
                'Duyệt từ ứng viên lượt #' . $uv->lan_hoc_id, Auth::id()
            );

            return response()->json(['message' => self::thongDiepThem($kq)]);
        });
    }

    public function hocLai()
    {
        $r = $this->hocLai->yeuCau(Auth::id());

        return response()->json(['moi' => $r['moi'], 'lan_hoc' => $this->hocLai->moTa($r['lan_hoc'])]);
    }

    public function trangThai()
    {
        $this->hocLai->danhDauTreo();

        return response()->json(['lan_hoc' => $this->hocLai->moTa($this->hocLai->ganNhat())]);
    }

    public function xuat()
    {
        $dong = Xml3176VatPhamKemDvkt::orderBy('loai')->orderBy('ma_vat_pham')->orderBy('ma_dvkt')->get()->all();

        return Excel::download(new VatPhamKemDvktExport($dong), 'vat_pham_kem_dvkt_' . date('Ymd_His') . '.xlsx');
    }

    public function nhap(Request $request)
    {
        $tep = $request->file('file');
        if ($tep === null || strtolower($tep->getClientOriginalExtension()) !== 'xlsx') {
            return response()->json(['message' => 'Tệp phải là .xlsx'], 422);
        }

        $imp = new VatPhamKemDvktImport();
        Excel::import($imp, $tep);
        $kq = $this->learner->nap($imp->hang, Auth::id());

        return response()->json([
            'message' => "Thêm {$kq['them']}, cập nhật {$kq['cap_nhat']}, lỗi " . count($kq['loi']),
            'ket_qua' => $kq,
        ]);
    }

    public function nhapLoi(Request $request)
    {
        $dongLoi = [];
        foreach ((array) json_decode((string) $request->input('dong_loi'), true) as $l) {
            $dongLoi[] = ['dong' => (int) ($l['dong'] ?? 0), 'loai' => 'loi', 'ly_do' => (string) ($l['ly_do'] ?? '')];
        }

        return Excel::download(new LoiNhapDanhMucExport($dongLoi, (string) $request->input('ten_tep')), 'loi_nhap_vat_pham_kem_dvkt.xlsx');
    }

    private function voiDong($id, callable $f)
    {
        $m = Xml3176VatPhamKemDvkt::find($id);
        if ($m === null) {
            return response()->json(['message' => 'Không tìm thấy dòng'], 404);
        }

        return $this->bat(function () use ($f, $m) {
            $f($m);

            return response()->json(['success' => true]);
        });
    }

    private function bat(callable $f)
    {
        try {
            return $f();
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    private static function thongDiepThem(array $kq): string
    {
        return 'Đã thêm ' . count($kq['them']) . ' dòng.' . (empty($kq['da_co']) ? '' : ' Đã có: ' . implode(', ', $kq['da_co']));
    }
}
```

- [ ] **Step 5: Route** — trong `routes/web.php`, ngay sau khối 2 dòng `Route::post('/category-bhyt/update-xml3176-error-catalog', ...)->name('category-bhyt.update-xml3176-error-catalog');` (cùng nhóm `checkrole:category-manager`), thêm:

```php

        // Bang tra vat pham (VTYT/thuoc) kem DVKT me - XML3176
        Route::group(['prefix' => 'bhyt/vat-pham-kem-dvkt', 'as' => 'category-bhyt.vat-pham-kem-dvkt.'], function () {
            $c = 'Category\VatPhamKemDvktController@';
            Route::get('/', $c . 'index')->name('index');
            Route::get('datatable', $c . 'datatable')->name('datatable');
            Route::get('ung-vien-datatable', $c . 'ungVienDatatable')->name('ung-vien-datatable');
            Route::post('{id}/hieu-luc', $c . 'hieuLuc')->where('id', '[0-9]+')->name('hieu-luc');
            Route::post('{id}/ghi-chu', $c . 'ghiChu')->where('id', '[0-9]+')->name('ghi-chu');
            Route::post('{id}/xoa', $c . 'xoa')->where('id', '[0-9]+')->name('xoa');
            Route::get('kiem-khop', $c . 'kiemKhop')->name('kiem-khop');
            Route::get('tim-vat-pham', $c . 'timVatPham')->name('tim-vat-pham');
            Route::get('tim-dvkt', $c . 'timDvkt')->name('tim-dvkt');
            Route::post('them', $c . 'them')->name('them');
            Route::post('ung-vien/{id}/them', $c . 'ungVienThem')->where('id', '[0-9]+')->name('ung-vien-them');
            Route::post('hoc-lai', $c . 'hocLai')->name('hoc-lai');
            Route::get('hoc-lai/trang-thai', $c . 'trangThai')->name('trang-thai');
            Route::get('xuat', $c . 'xuat')->name('xuat');
            Route::post('nhap', $c . 'nhap')->name('nhap');
            Route::post('nhap-loi', $c . 'nhapLoi')->name('nhap-loi');
        });
```

(Nhóm cha có `prefix 'category/'` nên URL thật là `category/bhyt/vat-pham-kem-dvkt/...`.)

- [ ] **Step 6: Menu** — trong `config/adminlte.php`, ngay sau mảng mục `'text' => 'DM lỗi Xml 3176'` (kết thúc bằng `],`), thêm:

```php
                        [
                            'text'  => 'DM vật phẩm kèm DVKT mẹ',
                            'icon'  => 'book',
                            'route'   => 'category-bhyt.vat-pham-kem-dvkt.index',
                            'active'=> ['category/bhyt/vat-pham-kem-dvkt*'],
                        ],
```

- [ ] **Step 7:** Run Step 3 → PASS (controller 8 test + menu). Nếu `Datatables` alias trong repo là `Yajra\Datatables\Datatables` (đúng như `CategoryBHYTController.php:10`) thì giữ nguyên `use`.

- [ ] **Step 8: Commit**

```bash
git add app/Http/Controllers/Category/VatPhamKemDvktController.php routes/web.php config/adminlte.php tests/Unit/MenuDanhMucBhytTest.php tests/Unit/Xml3176/VatPhamKemDvkt/QuanTriControllerTest.php
git commit -m "feat(xml3176): controller, route va menu man quan tri vat pham kem DVKT me

Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>"
```

---

### Task 6: View màn hình

**Files:**
- Create: `resources/views/category/bhyt/vat_pham_kem_dvkt.blade.php`
- Test: `tests/Unit/Xml3176/VatPhamKemDvkt/QuanTriViewTest.php`

**Interfaces:** Consumes route names Task 5; biến `$lanHoc` (`null` hoặc `['id','trang_thai','dang_chay','nhan']`).

- [ ] **Step 1: Test (đỏ)** — `tests/Unit/Xml3176/VatPhamKemDvkt/QuanTriViewTest.php`:

```php
<?php

namespace Tests\Unit\Xml3176\VatPhamKemDvkt;

use Tests\TestCase;

class QuanTriViewTest extends TestCase
{
    private function nguon(): string
    {
        return file_get_contents(resource_path('views/category/bhyt/vat_pham_kem_dvkt.blade.php'));
    }

    /** @test */
    public function blade_bien_dich_ra_php_hop_le()
    {
        $compiled = app('blade.compiler')->compileString($this->nguon());
        try {
            token_get_all($compiled, TOKEN_PARSE);
        } catch (\ParseError $e) {
            $this->fail('Blade khong bien dich duoc: ' . $e->getMessage());
        }
        $this->assertTrue(true);
    }

    /** @test */
    public function view_goi_du_route_cua_man()
    {
        $src = $this->nguon();
        foreach (['datatable', 'ung-vien-datatable', 'hieu-luc', 'ghi-chu', 'xoa', 'kiem-khop', 'tim-vat-pham',
                  'tim-dvkt', 'them', 'ung-vien-them', 'hoc-lai', 'trang-thai', 'xuat', 'nhap', 'nhap-loi'] as $r) {
            $this->assertContains("category-bhyt.vat-pham-kem-dvkt.$r", $src, "View chua dung route $r");
        }
        $this->assertContains('Cần ghi lý do khi tắt', $src);
        $this->assertContains('csrf_token()', $src);
    }
}
```

- [ ] **Step 2:** Run `DB_HOST=127.0.0.1 vendor/bin/phpunit tests/Unit/Xml3176/VatPhamKemDvkt/QuanTriViewTest.php` → FAIL.

- [ ] **Step 3: View** — `resources/views/category/bhyt/vat_pham_kem_dvkt.blade.php`. Route có tham số `{id}` được dựng trong JS bằng cách gọi `route(..., ['id' => '__ID__'])` rồi thay `__ID__`.

```blade
@extends('adminlte::page')

@section('title', 'Vật phẩm kèm DVKT mẹ')

@section('content_header')
  <h1>
    Danh mục
    <small>Vật phẩm (VTYT/thuốc) thanh toán ngoài giá kèm DVKT mẹ — XML3176</small>
  </h1>
@stop

@section('content')
@include('includes.message')

<div class="panel panel-default">
    <div class="panel-body">
        <button id="vpkd-hoc-lai" class="btn btn-primary" @if($lanHoc && $lanHoc['dang_chay']) disabled @endif>
            <i class="fa fa-refresh"></i> Học lại
        </button>
        <span id="vpkd-trang-thai" class="text-muted" style="margin-left:8px">{{ $lanHoc ? $lanHoc['nhan'] : 'Chưa có lượt học nào' }}</span>
        <div class="pull-right">
            <a href="{{ route('category-bhyt.vat-pham-kem-dvkt.xuat') }}" class="btn btn-default"><i class="fa fa-download"></i> Xuất Excel</a>
            <button class="btn btn-default" data-toggle="modal" data-target="#vpkd-modal-nhap"><i class="fa fa-upload"></i> Nhập Excel</button>
            <button class="btn btn-success" data-toggle="modal" data-target="#vpkd-modal-them"><i class="fa fa-plus"></i> Thêm dòng tay</button>
        </div>
    </div>
</div>

<ul class="nav nav-tabs">
    <li class="active"><a href="#vpkd-tab-bang-tra" data-toggle="tab">Bảng tra</a></li>
    <li><a href="#vpkd-tab-ung-vien" data-toggle="tab">Ứng viên chờ duyệt</a></li>
</ul>
<div class="tab-content">
    <div class="tab-pane active" id="vpkd-tab-bang-tra">
        <div class="panel panel-default"><div class="panel-body">
            <div class="form-inline" style="margin-bottom:10px">
                <select id="loc-loai" class="form-control input-sm"><option value="">Loại: tất cả</option><option>VTYT</option><option>THUOC</option></select>
                <select id="loc-nguon" class="form-control input-sm"><option value="">Nguồn: tất cả</option><option>HOC</option><option>TAY</option></select>
                <select id="loc-hieu-luc" class="form-control input-sm"><option value="">Hiệu lực: tất cả</option><option value="1">Đang dùng</option><option value="0">Đã tắt</option></select>
            </div>
            <table id="vpkd-bang-tra" class="table display table-hover nowrap" width="100%">
                <thead><tr>
                    <th>Loại</th><th>Mã vật phẩm</th><th>Tên vật phẩm</th><th>Mã DVKT</th><th>Tên DVKT</th>
                    <th>Nguồn</th><th>Số lần gắn</th><th>Tỷ lệ gắn</th><th>Hiệu lực</th><th>Ghi chú</th><th>Người sửa</th><th></th>
                </tr></thead>
            </table>
        </div></div>
    </div>
    <div class="tab-pane" id="vpkd-tab-ung-vien">
        <div class="panel panel-default"><div class="panel-body">
            <div class="form-inline" style="margin-bottom:10px">
                <select id="loc-uv-loai" class="form-control input-sm"><option value="">Loại: tất cả</option><option>VTYT</option><option>THUOC</option></select>
            </div>
            <table id="vpkd-ung-vien" class="table display table-hover" width="100%">
                <thead><tr>
                    <th>Loại</th><th>Mã</th><th>Tên</th><th>Số dòng BHYT</th><th>Số liên kết</th><th>Tỷ lệ gắn</th><th>DVKT từng gắn</th><th></th>
                </tr></thead>
            </table>
        </div></div>
    </div>
</div>

<div class="modal fade" id="vpkd-modal-them" tabindex="-1">
  <div class="modal-dialog modal-lg"><div class="modal-content">
    <div class="modal-header"><button type="button" class="close" data-dismiss="modal">&times;</button><h4 class="modal-title">Thêm dòng tay</h4></div>
    <div class="modal-body">
        <div class="form-group"><label>Loại</label>
            <select id="them-loai" class="form-control"><option value="VTYT">VTYT</option><option value="THUOC">Thuốc</option></select></div>
        <div class="form-group"><label>Mã vật phẩm</label>
            <select id="them-ma" class="form-control" style="width:100%"></select></div>
        <div id="them-khop" class="form-group"></div>
        <div class="form-group"><label>Tên vật phẩm</label>
            <input id="them-ten" class="form-control" placeholder="Đúng như tên trên XML"></div>
        <div class="form-group"><label>DVKT mẹ</label>
            <select id="them-dvkt" class="form-control" multiple style="width:100%"></select></div>
        <div class="form-group"><label>Ghi chú</label><input id="them-ghi-chu" class="form-control" maxlength="1024"></div>
    </div>
    <div class="modal-footer">
        <button class="btn btn-default" data-dismiss="modal">Đóng</button>
        <button id="them-luu" class="btn btn-success">Lưu</button>
    </div>
  </div></div>
</div>

<div class="modal fade" id="vpkd-modal-nhap" tabindex="-1">
  <div class="modal-dialog"><div class="modal-content">
    <div class="modal-header"><button type="button" class="close" data-dismiss="modal">&times;</button><h4 class="modal-title">Nhập Excel bảng tra</h4></div>
    <div class="modal-body">
        <p class="text-muted">Tệp .xlsx đúng định dạng của nút "Xuất Excel". Cột mã phải ở định dạng Text. Dòng mới thành nguồn TAY; cột hieu_luc bật/tắt dòng; không xoá dòng nào.</p>
        <form action="{{ route('category-bhyt.vat-pham-kem-dvkt.nhap') }}" class="dropzone" id="vpkd-dropzone">
            {{ csrf_field() }}
        </form>
        <div id="nhap-ket-qua" style="margin-top:10px"></div>
    </div>
  </div></div>
</div>
@stop

@push('after-scripts')
<script src="{{ asset('js/dropzone.min.js') }}"></script>
<script type="text/javascript">
    Dropzone.autoDiscover = false;
    var TOKEN = "{{ csrf_token() }}";
    var URL_ID = {
        hieuLuc: "{{ route('category-bhyt.vat-pham-kem-dvkt.hieu-luc', ['id' => '__ID__']) }}",
        ghiChu: "{{ route('category-bhyt.vat-pham-kem-dvkt.ghi-chu', ['id' => '__ID__']) }}",
        xoa: "{{ route('category-bhyt.vat-pham-kem-dvkt.xoa', ['id' => '__ID__']) }}",
        uvThem: "{{ route('category-bhyt.vat-pham-kem-dvkt.ung-vien-them', ['id' => '__ID__']) }}"
    };
    function url(mau, id) { return mau.replace('__ID__', id); }
    function baoLoi(xhr) {
        toastr.error(xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Có lỗi xảy ra khi gửi yêu cầu.');
    }
    var bangTra, ungVien, hoiTrangThai = null, kiemKhopHienTai = null;

    function taiLai() { bangTra.ajax.reload(null, false); ungVien.ajax.reload(null, false); }

    // ---- Bang tra ----
    bangTra = $('#vpkd-bang-tra').DataTable({
        processing: true, serverSide: true, scrollX: true, order: [[1, 'asc']],
        ajax: { url: "{{ route('category-bhyt.vat-pham-kem-dvkt.datatable') }}",
                data: function (d) { d.loai = $('#loc-loai').val(); d.nguon = $('#loc-nguon').val(); d.hieu_luc = $('#loc-hieu-luc').val(); } },
        columns: [
            { data: 'loai', name: 'xml3176_vat_pham_kem_dvkt.loai' },
            { data: 'ma_vat_pham', name: 'xml3176_vat_pham_kem_dvkt.ma_vat_pham' },
            { data: 'ten_vat_pham', name: 'xml3176_vat_pham_kem_dvkt.ten_vat_pham' },
            { data: 'ma_dvkt', name: 'xml3176_vat_pham_kem_dvkt.ma_dvkt' },
            { data: 'ten_dvkt', name: 'xml3176_vat_pham_kem_dvkt.ten_dvkt' },
            { data: 'nguon', name: 'xml3176_vat_pham_kem_dvkt.nguon', searchable: false },
            { data: 'so_lan_gan', name: 'xml3176_vat_pham_kem_dvkt.so_lan_gan', searchable: false },
            { data: 'ty_le_gan', name: 'xml3176_vat_pham_kem_dvkt.ty_le_gan', searchable: false },
            { data: 'hieu_luc', name: 'xml3176_vat_pham_kem_dvkt.hieu_luc', searchable: false },
            { data: 'ghi_chu', name: 'xml3176_vat_pham_kem_dvkt.ghi_chu', searchable: false },
            { data: 'nguoi_sua_luc', name: 'xml3176_vat_pham_kem_dvkt.sua_luc', searchable: false },
            { data: 'thao_tac', orderable: false, searchable: false }
        ]
    });
    $('#loc-loai, #loc-nguon, #loc-hieu-luc').on('change', function () { bangTra.ajax.reload(); });

    $('#vpkd-bang-tra').on('change', '.vpkd-hieu-luc', function () {
        var o = $(this), bat = o.is(':checked');
        Swal.fire({
            title: bat ? 'Bật lại cặp này?' : 'Tắt cặp này?', icon: 'warning', showCancelButton: true,
            input: 'text', inputValue: o.data('ghi-chu') || '', inputPlaceholder: bat ? 'Ghi chú (tuỳ chọn)' : 'Lý do tắt (bắt buộc)',
            inputValidator: function (v) { if (!bat && !$.trim(v)) { return 'Cần ghi lý do khi tắt'; } }
        }).then(function (r) {
            if (!r.isConfirmed) { o.prop('checked', !bat); return; }
            $.post(url(URL_ID.hieuLuc, o.data('id')), { _token: TOKEN, hieu_luc: bat ? 1 : 0, ghi_chu: r.value })
                .done(function () { toastr.success('Đã cập nhật.'); bangTra.ajax.reload(null, false); })
                .fail(function (x) { o.prop('checked', !bat); baoLoi(x); });
        });
    });

    $('#vpkd-bang-tra').on('click', '.vpkd-ghi-chu', function (e) {
        e.preventDefault();
        var o = $(this);
        Swal.fire({ title: 'Ghi chú', input: 'text', inputValue: o.data('ghi-chu') || '', showCancelButton: true })
            .then(function (r) {
                if (!r.isConfirmed) { return; }
                $.post(url(URL_ID.ghiChu, o.data('id')), { _token: TOKEN, ghi_chu: r.value })
                    .done(function () { toastr.success('Đã lưu ghi chú.'); bangTra.ajax.reload(null, false); })
                    .fail(baoLoi);
            });
    });

    $('#vpkd-bang-tra').on('click', '.vpkd-xoa', function () {
        var id = $(this).data('id');
        Swal.fire({ title: 'Bạn có chắc chắn?', text: 'Xoá dòng bổ sung tay này.', icon: 'warning', showCancelButton: true })
            .then(function (r) {
                if (!r.isConfirmed) { return; }
                $.post(url(URL_ID.xoa, id), { _token: TOKEN })
                    .done(function () { toastr.success('Đã xoá.'); taiLai(); })
                    .fail(baoLoi);
            });
    });

    // ---- Ung vien ----
    ungVien = $('#vpkd-ung-vien').DataTable({
        processing: true, serverSide: true, order: [[3, 'desc']],
        ajax: { url: "{{ route('category-bhyt.vat-pham-kem-dvkt.ung-vien-datatable') }}",
                data: function (d) { d.loai = $('#loc-uv-loai').val(); } },
        columns: [
            { data: 'loai', name: 'loai' },
            { data: 'ma_vat_pham', name: 'ma_vat_pham' },
            { data: 'ten_vat_pham', name: 'ten_vat_pham' },
            { data: 'so_dong', name: 'so_dong', searchable: false },
            { data: 'so_lien_ket', name: 'so_lien_ket', searchable: false },
            { data: 'ty_le_gan', name: 'ty_le_gan', searchable: false },
            { data: 'dvkt', orderable: false, searchable: false },
            { data: 'thao_tac', orderable: false, searchable: false }
        ]
    });
    $('#loc-uv-loai').on('change', function () { ungVien.ajax.reload(); });
    $('a[href="#vpkd-tab-ung-vien"]').on('shown.bs.tab', function () { ungVien.columns.adjust(); });

    $('#vpkd-ung-vien').on('click', '.vpkd-uv-them', function () {
        var id = $(this).data('id');
        var ma = $('.vpkd-uv-dv[data-uv="' + id + '"]:checked:not(:disabled)').map(function () { return this.value; }).get();
        if (!ma.length) { toastr.warning('Hãy tick ít nhất một DVKT chưa có.'); return; }
        $.post(url(URL_ID.uvThem, id), { _token: TOKEN, ma_dvkt: ma })
            .done(function (r) { toastr.success(r.message); taiLai(); })
            .fail(baoLoi);
    });

    // ---- Them dong tay ----
    $('#them-ma').select2({
        dropdownParent: $('#vpkd-modal-them'), tags: true, placeholder: 'Gõ mã hoặc tên',
        ajax: { url: "{{ route('category-bhyt.vat-pham-kem-dvkt.tim-vat-pham') }}", delay: 300,
                data: function (p) { return { q: p.term, loai: $('#them-loai').val() }; } }
    });
    $('#them-dvkt').select2({
        dropdownParent: $('#vpkd-modal-them'), tags: true, placeholder: 'Gõ mã hoặc tên DVKT',
        ajax: { url: "{{ route('category-bhyt.vat-pham-kem-dvkt.tim-dvkt') }}", delay: 300,
                data: function (p) { return { q: p.term }; } }
    });
    $('#them-loai').on('change', function () { $('#them-ma').val(null).trigger('change'); $('#them-khop').empty(); kiemKhopHienTai = null; });

    $('#them-ma').on('change', function () {
        var ma = $(this).val();
        $('#them-khop').empty(); kiemKhopHienTai = null;
        if (!ma) { return; }
        $.get("{{ route('category-bhyt.vat-pham-kem-dvkt.kiem-khop') }}", { loai: $('#them-loai').val(), ma: ma })
            .done(function (r) {
                kiemKhopHienTai = r;
                if (!r.ds.length) {
                    $('#them-khop').html(r.ma_nhom_chung
                        ? '<div class="alert alert-danger">Mã nhóm chung chưa xuất hiện trên XML 12 tháng — không thể khớp</div>'
                        : '<div class="alert alert-warning">Chưa thấy trên XML 12 tháng</div>');
                    $('#them-ten').prop('readonly', false);
                    return;
                }
                var h = '<label>Tên trên XML 12 tháng</label><table class="table table-condensed"><tr><th></th><th>Tên</th><th>Số dòng</th><th>BHYT trả</th><th>Hồ sơ</th></tr>';
                $.each(r.ds, function (i, d) {
                    h += '<tr><td><input type="radio" name="them-ten-xml" value="' + $('<div>').text(d.ten).html() + '"' + (i === 0 ? ' checked' : '') + '></td><td>'
                        + $('<div>').text(d.ten).html() + '</td><td>' + d.so_dong + '</td><td>' + d.so_dong_bhyt + '</td><td>' + d.so_ho_so + '</td></tr>';
                });
                $('#them-khop').html(h + '</table>');
                $('#them-ten').val(r.ds[0].ten).prop('readonly', r.ma_nhom_chung);
            })
            .fail(baoLoi);
    });
    $('#them-khop').on('change', 'input[name="them-ten-xml"]', function () { $('#them-ten').val($(this).val()); });

    $('#them-luu').on('click', function () {
        if (kiemKhopHienTai && kiemKhopHienTai.ma_nhom_chung && !kiemKhopHienTai.ds.length) {
            toastr.error('Mã nhóm chung chưa xuất hiện trên XML 12 tháng — không thể khớp'); return;
        }
        $.post("{{ route('category-bhyt.vat-pham-kem-dvkt.them') }}", {
            _token: TOKEN, loai: $('#them-loai').val(), ma_vat_pham: $('#them-ma').val(),
            ten_vat_pham: $('#them-ten').val(), ma_dvkt: $('#them-dvkt').val() || [], ghi_chu: $('#them-ghi-chu').val()
        }).done(function (r) {
            toastr.success(r.message);
            $('#vpkd-modal-them').modal('hide');
            $('#them-ma, #them-dvkt').val(null).trigger('change'); $('#them-ten, #them-ghi-chu').val(''); $('#them-khop').empty();
            taiLai();
        }).fail(baoLoi);
    });

    // ---- Hoc lai ----
    function capNhatTrangThai(lan) {
        $('#vpkd-trang-thai').text(lan ? lan.nhan : 'Chưa có lượt học nào');
        var dang = !!(lan && lan.dang_chay);
        $('#vpkd-hoc-lai').prop('disabled', dang);
        if (dang && hoiTrangThai === null) {
            hoiTrangThai = setInterval(function () {
                $.get("{{ route('category-bhyt.vat-pham-kem-dvkt.trang-thai') }}").done(function (r) {
                    var xong = !(r.lan_hoc && r.lan_hoc.dang_chay);
                    capNhatTrangThai(r.lan_hoc);
                    if (xong) {
                        clearInterval(hoiTrangThai); hoiTrangThai = null;
                        if (r.lan_hoc && r.lan_hoc.trang_thai === 'XONG') { toastr.success('Học lại xong.'); taiLai(); }
                        else if (r.lan_hoc) { toastr.error(r.lan_hoc.nhan); }
                    }
                });
            }, 10000);
        }
    }
    capNhatTrangThai(@json($lanHoc));

    $('#vpkd-hoc-lai').on('click', function () {
        Swal.fire({ title: 'Học lại bảng tra?', text: 'Chạy nền 2–5 phút. Dòng TAY và dòng bạn đã tắt được giữ nguyên.', icon: 'question', showCancelButton: true })
            .then(function (r) {
                if (!r.isConfirmed) { return; }
                $.post("{{ route('category-bhyt.vat-pham-kem-dvkt.hoc-lai') }}", { _token: TOKEN })
                    .done(function (r) {
                        toastr.info(r.moi ? 'Đã xếp lượt học vào hàng đợi.' : 'Đang có lượt học chạy — theo dõi trạng thái.');
                        capNhatTrangThai(r.lan_hoc);
                    }).fail(baoLoi);
            });
    });

    // ---- Nhap Excel ----
    new Dropzone('#vpkd-dropzone', {
        paramName: 'file', uploadMultiple: false, maxFiles: 1, acceptedFiles: '.xlsx',
        dictDefaultMessage: 'Kéo thả tệp .xlsx vào đây hoặc bấm để chọn',
        success: function (file, r) {
            var h = '<div class="alert alert-' + (r.ket_qua.loi.length ? 'warning' : 'success') + '">' + $('<div>').text(r.message).html() + '</div>';
            if (r.ket_qua.loi.length) {
                h += '<form method="POST" action="{{ route('category-bhyt.vat-pham-kem-dvkt.nhap-loi') }}">'
                    + '<input type="hidden" name="_token" value="' + TOKEN + '">'
                    + '<input type="hidden" name="ten_tep" value="' + $('<div>').text(file.name).html() + '">'
                    + '<input type="hidden" name="dong_loi" value=\'' + $('<div>').text(JSON.stringify(r.ket_qua.loi)).html() + '\'>'
                    + '<button class="btn btn-default btn-sm"><i class="fa fa-download"></i> Tải tệp lỗi (' + r.ket_qua.loi.length + ')</button></form>';
            }
            $('#nhap-ket-qua').html(h);
            this.removeAllFiles();
            taiLai();
        },
        error: function (file, r) {
            $('#nhap-ket-qua').html('<div class="alert alert-danger">' + $('<div>').text(r && r.message ? r.message : r).html() + '</div>');
            this.removeAllFiles();
        }
    });
</script>
@endpush
```

- [ ] **Step 4:** Run Step 2 → PASS. Nếu `@json` không có trong Laravel 5.5 (có từ 5.5.?) và blade báo lỗi, đổi `@json($lanHoc)` thành `{!! json_encode($lanHoc) !!}`.

- [ ] **Step 5: Commit**

```bash
git add resources/views/category/bhyt/vat_pham_kem_dvkt.blade.php tests/Unit/Xml3176/VatPhamKemDvkt/QuanTriViewTest.php
git commit -m "feat(xml3176): giao dien man quan tri vat pham kem DVKT me

Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>"
```

---

### Task 7: Hồi quy, kiểm giao diện, tài liệu

**Files:**
- Modify: `docs/trien-khai-xml3176-vat-pham-kem-dvkt.md`

- [ ] **Step 1: Hồi quy** — `DB_HOST=127.0.0.1 vendor/bin/phpunit tests/Unit/Xml3176` → chỉ đúng 4 lỗi có sẵn (Task 0). `DB_HOST=127.0.0.1 vendor/bin/phpunit tests/Unit/MenuDanhMucBhytTest.php tests/Unit/RouteXoaDanhMucTest.php tests/Unit/XoaDanhMucControllerTest.php` → không tăng lỗi so với mốc.

- [ ] **Step 2: Route** — `php artisan route:list --name=vat-pham-kem-dvkt` → in đủ 16 route, URL bắt đầu `category/bhyt/vat-pham-kem-dvkt`. (Lệnh không chạm CSDL.)

- [ ] **Step 3: Tài liệu** — trong `docs/trien-khai-xml3176-vat-pham-kem-dvkt.md`, thêm ngay sau mục `## 0. Tóm tắt trước khi làm` (trước `## Bước 1`) một mục mới:

```markdown
## Làm bằng màn hình (khuyến nghị, từ commit có màn quản trị)

Menu **Quản lý danh mục → BHYT → DM vật phẩm kèm DVKT mẹ** (quyền `category-manager`) thay cho Bước 3–5:

| Việc | Trên màn hình |
|---|---|
| Học bảng tra (Bước 3) | Nút **Học lại** — chạy nền qua dịch vụ `QLBV JobXuatTepXml3176` 2–5 phút; nhãn cạnh nút hiện kết quả. Đang có lượt chạy thì nút khoá. Lượt quá 60 phút tự chuyển "Lỗi: Treo quá 60 phút". |
| Duyệt (Bước 4) | Tab **Bảng tra**: lọc, tắt cặp sai (bắt buộc ghi lý do), sửa ghi chú, xoá dòng TAY. Nút **Thêm dòng tay**: chọn mã → bảng tên thật trên XML 12 tháng → chọn tên (bắt buộc với VTYT mã nhóm chung) → chọn DVKT mẹ. Tab **Ứng viên chờ duyệt**: tick DVKT đúng → **Thêm thành dòng tay**. |
| Excel (Bước 3.2, 5) | Nút **Xuất Excel** / **Nhập Excel** (kéo-thả, có tệp lỗi theo dòng). |

Mọi thao tác trên màn hình ghi người và thời điểm sửa cuối (cột "Người sửa"). Dòng lệnh ở Bước 3–5 vẫn dùng được làm phương án dự phòng; lệnh học (không `--thu`) cũng cập nhật lịch sử lượt học và tab ứng viên.

Sau khi `update.bat` chạy, kiểm `php artisan migrate:status | findstr vpkd` thấy 2 migration `create_xml3176_vpkd_*` và `them_nguoi_sua_vao_xml3176_vat_pham_kem_dvkt` đã chạy (`Y`).
```

- [ ] **Step 4: Kiểm giao diện thủ công** — báo người dùng (không tự đăng nhập): mở màn trên máy dev/prod, kiểm: hai bảng tải được; lọc; tìm select2; kiểm khớp hiện bảng tên; trên **CSDL kiểm thử hoặc prod sau triển khai** mới thử bật/tắt, thêm, học lại, nhập.

- [ ] **Step 5: Commit**

```bash
git add docs/trien-khai-xml3176-vat-pham-kem-dvkt.md
git commit -m "docs(xml3176): huong dan dung man quan tri vat pham kem DVKT me

Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>"
```

---

## Self-review (đã chạy)

- Spec §3.1 → Task 1, 4 (vet), 2 (`nap`); §3.2–3.4 → Task 1, 2; §4 → Task 2, 3; §5.1 → Task 5 (`hocLai/trangThai/xuat/nhap/nhapLoi`) + Task 6; §5.2 → Task 4, 5, 6; §5.3 → Task 4 (`themDongTay`, `kiemKhop`, `tim*`), 5, 6; §5.4 → Task 5 (`ungVienDatatable`, `ungVienThem`), 6; §6 route/menu/kiểm đầu vào → Task 4, 5; §8 test → Task 1–6; §9 tài liệu → Task 7.
- Khác spec có chủ đích: logic thao tác tách ra `VatPhamKemDvktQuanTri` (spec chỉ nêu controller) để controller mỏng và test được không cần HTTP; datatable không có unit test (yajra + SQLite), phủ bằng kiểm thủ công Task 7.
- Tên nhất quán: `thongKeVatPham`, `ghiUngVien`, `nap($hang, $nguoiSuaId)`, `yeuCau/thucHien/danhDauTreo/ganNhat/moTa`, `datHieuLuc/datGhiChu/xoa/themDongTay/kiemKhop/timVatPham/timDvkt`, route `category-bhyt.vat-pham-kem-dvkt.*`.
