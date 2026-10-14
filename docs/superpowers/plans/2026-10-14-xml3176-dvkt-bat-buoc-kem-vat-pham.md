# Quy tắc R3 — DVKT bắt buộc kèm vật phẩm — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Học danh sách DVKT mà hầu hết hồ sơ BHYT đều kèm vật phẩm, và cảnh báo khi hồ sơ có DVKT đó được BHYT trả nhưng không có vật phẩm đi kèm (R3); quản lý danh sách trên màn quản trị.

**Architecture:** Bảng mới `xml3176_vpkd_dvkt_bat_buoc` được học ngay trong lượt học hiện có (`VatPhamKemDvktHocLaiService::thucHien`) từ bảng tra vừa ghi + XML3/XML2. `VatPhamKemDvktLookup` nạp thêm danh sách này; `VatPhamKemDvktRule` thêm R3 (thuần, không CSDL). Màn quản trị thêm một tab dùng lại các thao tác chung của `VatPhamKemDvktQuanTri`.

**Tech Stack:** PHP 7.4, Laravel 5.5, yajra datatables, AdminLTE 2 / jQuery / select2 / SweetAlert2 / toastr, PHPUnit 6 + SQLite bộ nhớ.

**Spec:** `docs/superpowers/specs/2026-10-14-xml3176-dvkt-bat-buoc-kem-vat-pham-design.md`

## Global Constraints

- PHP 7.4 / Laravel 5.5: `setUp()`/`tearDown()` không có `: void`; được dùng `??`, `fn`.
- **Cấm `RefreshDatabase` / `DatabaseMigrations`**; bảng test dựng bằng `Tests\Support\Xml3176RuleTestSupport::bootXml3176Sqlite([...])`.
- Chạy test luôn bằng `DB_HOST=127.0.0.1 vendor/bin/phpunit <path>` (`.env` trỏ CSDL THẬT). Không chạy migrate/seed/lệnh ghi vào CSDL thật.
- Không type-hint tiêm service trên `Job::handle()`.
- Mã lỗi: `XMLComplete_DVKT_THIEU_VAT_PHAM_KEM` (khoá trong rule: `DVKT_THIEU_VAT_PHAM_KEM`), `error_name` = `DVKT bắt buộc kèm vật phẩm nhưng không có vật phẩm`, **`critical_error = false`**, chèn bằng migration (`firstOrCreate`).
- Config mới trong `xml3176.vat_pham_kem_dvkt`: `dvkt_bat_buoc_nguong` = `0.9`, `dvkt_bat_buoc_toi_thieu` = `20`.
- Chỉ đếm dòng `t_bhtt > 0` cả phía DVKT lẫn vật phẩm. Mã DVKT chuẩn hoá bằng `VatPhamKemDvktKey::chuanHoaMaDvkt` (bỏ `_GT`).
- Khoá vật phẩm trong nhóm: `"{LOAI}|" . VatPhamKemDvktKey::khoa($loai, $ma, $ten)`.
- Thông điệp 422 mới: `DVKT chưa là DVKT mẹ của vật phẩm nào trong bảng tra — không có gì để kiểm`; `Đã có`; `Mã DVKT phải từ 1 đến 50 ký tự`. Thông điệp cũ dùng lại: `Cần ghi lý do khi tắt`, `Chỉ xoá được dòng bổ sung tay (TAY); dòng học tự động hãy tắt hiệu lực`, `Ghi chú tối đa 1024 ký tự`.
- Quyền mọi route mới: nhóm `category/` + `checkrole:category-manager`, tên tiền tố `category-bhyt.vat-pham-kem-dvkt.bat-buoc-`.
- Commit: `feat(xml3176): ...` tiếng Việt không dấu, kết thúc bằng `Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>`.
- Tên test `/** @test */` snake_case tiếng Việt không dấu; comment mã tiếng Việt không dấu.

Danh sách migration dùng trong test (lặp lại ở nhiều task):

```php
'2014_10_12_000000_create_users_table.php',
'2024_06_12_145200_create_danh_muc_dich_vu_table.php',
'2026_01_09_152826_create_xml3176_xml2s_table.php',
'2026_01_09_152832_create_xml3176_xml3s_table.php',
'2026_10_13_090000_create_xml3176_vat_pham_kem_dvkt_table.php',
'2026_10_14_090000_them_nguoi_sua_vao_xml3176_vat_pham_kem_dvkt.php',
'2026_10_14_090100_create_xml3176_vpkd_lan_hoc_table.php',
'2026_10_14_090200_create_xml3176_vpkd_ung_vien_table.php',
'2026_10_15_090000_create_xml3176_vpkd_dvkt_bat_buoc_table.php',
```

(Migration mới cần `xml3176_error_catalogs`; test nào chạy migration mới thì thêm `'2026_01_09_161708_create_xml3176_error_catalogs_table.php'` vào **trước** nó.)

---

### Task 0: Mốc test

- [ ] Run `DB_HOST=127.0.0.1 vendor/bin/phpunit tests/Unit/Xml3176 2>&1 | tail -1`. Mốc: 772 test, 4 lỗi có sẵn (`Xml3176Xml3CheckerRuleTest::tg_th_yl_trung_tg_kq`, `::thuc_hien_duoi_3_phut_nhom_1`, `::bac_si_vua_ra_vua_thuc_hien`, `Xml3176ErrorServiceGomTest::che_do_gom_bat_tat_dung`).

---

### Task 1: Bảng, model, config, mã lỗi

**Files:**
- Create: `database/migrations/2026_10_15_090000_create_xml3176_vpkd_dvkt_bat_buoc_table.php`, `app/Models/BHYT/Xml3176VpkdDvktBatBuoc.php`
- Modify: `config/xml3176.php`
- Test: `tests/Unit/Xml3176/VatPhamKemDvkt/DvktBatBuocDuLieuTest.php`

**Produces:** model `Xml3176VpkdDvktBatBuoc` (cột `ma_dvkt, ten_dvkt, so_ho_so (int), so_ho_so_co_kem (int), ty_le (float), nguon, hieu_luc (bool, mặc định true), ghi_chu, nguoi_sua_id (int), sua_luc (Carbon)`).

- [ ] **Step 1: Test (đỏ)** — `tests/Unit/Xml3176/VatPhamKemDvkt/DvktBatBuocDuLieuTest.php`:

```php
<?php

namespace Tests\Unit\Xml3176\VatPhamKemDvkt;

use App\Models\BHYT\Xml3176ErrorCatalog;
use App\Models\BHYT\Xml3176VpkdDvktBatBuoc;
use Tests\Support\Xml3176RuleTestSupport;
use Tests\TestCase;

class DvktBatBuocDuLieuTest extends TestCase
{
    use Xml3176RuleTestSupport;

    protected function setUp()
    {
        parent::setUp();
        $this->bootXml3176Sqlite([
            '2026_01_09_161708_create_xml3176_error_catalogs_table.php',
            '2026_10_15_090000_create_xml3176_vpkd_dvkt_bat_buoc_table.php',
        ]);
    }

    /** @test */
    public function tao_dong_va_ep_kieu()
    {
        $m = Xml3176VpkdDvktBatBuoc::create([
            'ma_dvkt' => '18.0219.0041', 'ten_dvkt' => 'CLVT', 'so_ho_so' => '897', 'so_ho_so_co_kem' => '885',
            'ty_le' => '0.9866', 'nguon' => 'HOC',
        ])->fresh();

        $this->assertTrue($m->hieu_luc);
        $this->assertSame(897, $m->so_ho_so);
        $this->assertSame(0.9866, $m->ty_le);
    }

    /** @test */
    public function ma_dvkt_duy_nhat()
    {
        Xml3176VpkdDvktBatBuoc::create(['ma_dvkt' => '18.0219.0041', 'nguon' => 'HOC', 'so_ho_so' => 1, 'so_ho_so_co_kem' => 1]);

        $this->expectException(\Illuminate\Database\QueryException::class);
        Xml3176VpkdDvktBatBuoc::create(['ma_dvkt' => '18.0219.0041', 'nguon' => 'TAY', 'so_ho_so' => 0, 'so_ho_so_co_kem' => 0]);
    }

    /** @test */
    public function migration_nap_ma_loi_canh_bao()
    {
        $c = Xml3176ErrorCatalog::where('error_code', 'XMLComplete_DVKT_THIEU_VAT_PHAM_KEM')->first();

        $this->assertNotNull($c);
        $this->assertSame('XMLComplete', $c->xml);
        $this->assertFalse((bool) $c->critical_error);
        $this->assertTrue((bool) $c->is_check);
    }

    /** @test */
    public function config_co_nguong_dvkt_bat_buoc()
    {
        $this->assertEquals(0.9, config('xml3176.vat_pham_kem_dvkt.dvkt_bat_buoc_nguong'));
        $this->assertEquals(20, config('xml3176.vat_pham_kem_dvkt.dvkt_bat_buoc_toi_thieu'));
    }
}
```

- [ ] **Step 2:** Run `DB_HOST=127.0.0.1 vendor/bin/phpunit tests/Unit/Xml3176/VatPhamKemDvkt/DvktBatBuocDuLieuTest.php` → FAIL (thiếu migration).

- [ ] **Step 3: Migration** — `database/migrations/2026_10_15_090000_create_xml3176_vpkd_dvkt_bat_buoc_table.php`:

```php
<?php

use App\Models\BHYT\Xml3176ErrorCatalog;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * DVKT bat buoc kem vat pham (quy tac R3): hoc trong luot hoc bang tra vat pham kem DVKT me.
 * Kem nap ma loi XMLComplete_DVKT_THIEU_VAT_PHAM_KEM muc CANH BAO (khong chan gui) - chua co
 * trong danh muc thi getCriticalErrorStatus() mac dinh la nghiem trong. firstOrCreate: khong
 * ghi de muc nguoi van hanh da chinh.
 */
class CreateXml3176VpkdDvktBatBuocTable extends Migration
{
    public function up()
    {
        Schema::create('xml3176_vpkd_dvkt_bat_buoc', function (Blueprint $table) {
            $table->increments('id');
            $table->string('ma_dvkt', 50)->collation('utf8_bin')->unique();
            $table->string('ten_dvkt', 1024)->nullable();
            $table->integer('so_ho_so')->default(0);
            $table->integer('so_ho_so_co_kem')->default(0);
            $table->decimal('ty_le', 5, 4)->nullable();
            $table->string('nguon', 10);
            $table->boolean('hieu_luc')->default(true);
            $table->string('ghi_chu', 1024)->nullable();
            $table->unsignedInteger('nguoi_sua_id')->nullable();
            $table->timestamp('sua_luc')->nullable();
            $table->timestamps();
        });

        Xml3176ErrorCatalog::firstOrCreate(
            ['xml' => 'XMLComplete', 'error_code' => 'XMLComplete_DVKT_THIEU_VAT_PHAM_KEM'],
            [
                'error_name' => 'DVKT bắt buộc kèm vật phẩm nhưng không có vật phẩm',
                'description' => 'DVKT mà hầu hết hồ sơ BHYT đều kèm vật phẩm (vd CLVT có tiêm thuốc cản quang) được quỹ thanh toán nhưng hồ sơ không có vật phẩm đi kèm được quỹ thanh toán. Kiểm tra kê đúng mã DVKT (vd có/không cản quang).',
                'critical_error' => false,
                'is_check' => true,
            ]
        );
    }

    public function down()
    {
        Schema::dropIfExists('xml3176_vpkd_dvkt_bat_buoc');
    }
}
```

- [ ] **Step 4: Model** — `app/Models/BHYT/Xml3176VpkdDvktBatBuoc.php`:

```php
<?php

namespace App\Models\BHYT;

use Illuminate\Database\Eloquent\Model;

/** DVKT bat buoc kem vat pham (R3). Hang so nguon/ghi chu dung chung voi Xml3176VatPhamKemDvkt. */
class Xml3176VpkdDvktBatBuoc extends Model
{
    protected $table = 'xml3176_vpkd_dvkt_bat_buoc';

    protected $fillable = [
        'ma_dvkt', 'ten_dvkt', 'so_ho_so', 'so_ho_so_co_kem', 'ty_le', 'nguon', 'hieu_luc', 'ghi_chu',
        'nguoi_sua_id', 'sua_luc',
    ];

    protected $attributes = ['hieu_luc' => true, 'so_ho_so' => 0, 'so_ho_so_co_kem' => 0];

    protected $casts = [
        'so_ho_so' => 'integer',
        'so_ho_so_co_kem' => 'integer',
        'ty_le' => 'float',
        'hieu_luc' => 'boolean',
        'nguoi_sua_id' => 'integer',
    ];

    protected $dates = ['sua_luc'];
}
```

- [ ] **Step 5: Config** — trong `config/xml3176.php`, khối `vat_pham_kem_dvkt`, sau mảng `dvkt_me_loai_tru_ten` thêm:

```php
        // R3: DVKT ma >= nguong ho so BHYT tra deu kem vat pham cung nhom (va >= toi_thieu ho so) thi bat
        // buoc kem vat pham. Do 14/10/2026: CLVT co can quang 98-99% (vao), MRI co tiem chat tuong phan
        // ~37% (tu loai - gia nhieu kha nang da bao gom thuoc).
        'dvkt_bat_buoc_nguong' => 0.9,
        'dvkt_bat_buoc_toi_thieu' => 20,
```

- [ ] **Step 6:** Run Step 2 → PASS (4 test).

- [ ] **Step 7: Commit**

```bash
git add database/migrations/2026_10_15_090000_create_xml3176_vpkd_dvkt_bat_buoc_table.php app/Models/BHYT/Xml3176VpkdDvktBatBuoc.php config/xml3176.php tests/Unit/Xml3176/VatPhamKemDvkt/DvktBatBuocDuLieuTest.php
git commit -m "feat(xml3176): bang DVKT bat buoc kem vat pham va ma loi canh bao R3

Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>"
```

---

### Task 2: Học danh sách DVKT bắt buộc

**Files:**
- Modify: `app/Services/Xml3176/VatPhamKemDvktLearner.php`
- Test: `tests/Unit/Xml3176/VatPhamKemDvkt/DvktBatBuocLearnerTest.php`

**Produces:**
- `hocDvktBatBuoc(float $nguong, int $toiThieu, $tuNgay = null): array` → list `['ma_dvkt','ten_dvkt','so_ho_so','so_ho_so_co_kem','ty_le']`, sắp `ma_dvkt` tăng dần.
- `ghiDvktBatBuoc(array $dong): array` → `['them','cap_nhat','tat','bat_lai']`.

- [ ] **Step 1: Test (đỏ)** — `tests/Unit/Xml3176/VatPhamKemDvkt/DvktBatBuocLearnerTest.php`:

```php
<?php

namespace Tests\Unit\Xml3176\VatPhamKemDvkt;

use App\Models\BHYT\Xml3176VatPhamKemDvkt;
use App\Models\BHYT\Xml3176VpkdDvktBatBuoc;
use App\Services\Xml3176\VatPhamKemDvktLearner;
use Illuminate\Support\Facades\DB;
use Tests\Support\Xml3176RuleTestSupport;
use Tests\TestCase;

class DvktBatBuocLearnerTest extends TestCase
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
            '2026_01_09_161708_create_xml3176_error_catalogs_table.php',
            '2026_10_15_090000_create_xml3176_vpkd_dvkt_bat_buoc_table.php',
        ]);
        // Bang tra: Omnipaque -> CLVT co can quang, MRI co tuong phan; buong tiem truyen (ma nhom chung) -> dat buong
        foreach ([['THUOC', '40.644', '', '18.0219.0041'], ['THUOC', '40.644', '', '18.0320.0065'],
                  ['VTYT', 'N08.00.150', 'buồng tiêm truyền', '12.0448.1187']] as $d) {
            Xml3176VatPhamKemDvkt::create(['loai' => $d[0], 'ma_vat_pham' => $d[1], 'ten_khop' => $d[2],
                'ma_dvkt' => $d[3], 'ten_dvkt' => 'DV ' . $d[3], 'nguon' => 'HOC']);
        }
    }

    private function dv(string $lk, string $ma, $bhtt = 1000): void
    {
        DB::table('xml3176_xml3s')->insert(['ma_lk' => $lk, 'stt' => ++$this->stt, 'ma_vat_tu' => '',
            'ma_dich_vu' => $ma, 'ten_dich_vu' => 'DV ' . $ma, 't_bhtt' => $bhtt, 'so_luong' => 1, 'ngay_yl' => '202609010800']);
    }

    private function thuoc(string $lk, string $ma, $bhtt = 1000): void
    {
        DB::table('xml3176_xml2s')->insert(['ma_lk' => $lk, 'stt' => ++$this->stt, 'ma_thuoc' => $ma,
            'ten_thuoc' => 'Omnipaque', 't_bhtt' => $bhtt, 'ngay_yl' => '202609010800']);
    }

    private function vt(string $lk, string $ma, string $ten, $bhtt = 1000): void
    {
        DB::table('xml3176_xml3s')->insert(['ma_lk' => $lk, 'stt' => ++$this->stt, 'ma_vat_tu' => $ma,
            'ten_vat_tu' => $ten, 't_bhtt' => $bhtt, 'so_luong' => 1, 'ngay_yl' => '202609010800']);
    }

    private function l(): VatPhamKemDvktLearner
    {
        return new VatPhamKemDvktLearner();
    }

    /** @test */
    public function chon_dvkt_dat_ty_le_va_so_ho_so()
    {
        for ($i = 0; $i < 20; $i++) {          // CLVT: 19/20 co thuoc BHYT (95%)
            $this->dv("CT$i", '18.0219.0041');
            if ($i < 19) {
                $this->thuoc("CT$i", '40.644');
            }
        }
        for ($i = 0; $i < 20; $i++) {          // MRI: 7/20 co thuoc (35%) -> loai
            $this->dv("MR$i", '18.0320.0065');
            if ($i < 7) {
                $this->thuoc("MR$i", '40.644');
            }
        }

        $kq = $this->l()->hocDvktBatBuoc(0.9, 20);

        $this->assertCount(1, $kq);
        $this->assertSame('18.0219.0041', $kq[0]['ma_dvkt']);
        $this->assertSame(20, $kq[0]['so_ho_so']);
        $this->assertSame(19, $kq[0]['so_ho_so_co_kem']);
        $this->assertSame(0.95, $kq[0]['ty_le']);
        $this->assertSame('DV 18.0219.0041', $kq[0]['ten_dvkt']);
    }

    /** @test */
    public function duoi_so_ho_so_toi_thieu_bi_loai()
    {
        for ($i = 0; $i < 19; $i++) {
            $this->dv("CT$i", '18.0219.0041');
            $this->thuoc("CT$i", '40.644');
        }

        $this->assertSame([], $this->l()->hocDvktBatBuoc(0.9, 20));
    }

    /** @test */
    public function chi_dem_dong_bhyt_tra_ca_hai_phia()
    {
        for ($i = 0; $i < 20; $i++) {
            $this->dv("CT$i", '18.0219.0041');
            $this->thuoc("CT$i", '40.644', $i < 10 ? 1000 : 0);   // 10 HS thuoc BN tu tra
        }
        $this->dv('TU_TRA', '18.0219.0041', 0);                 // DVKT quy khong tra: khong dem

        $kq = $this->l()->hocDvktBatBuoc(0.4, 20);

        $this->assertSame(20, $kq[0]['so_ho_so']);
        $this->assertSame(10, $kq[0]['so_ho_so_co_kem']);
    }

    /** @test */
    public function dvkt_gt_chuan_hoa_va_vtyt_ma_nhom_chung_khop_ma_ten()
    {
        for ($i = 0; $i < 20; $i++) {
            $this->dv("B$i", '12.0448.1187_GT');
            $this->vt("B$i", 'N08.00.150', $i < 18 ? 'Buồng tiêm truyền' : 'Catheter khác');
        }

        $kq = $this->l()->hocDvktBatBuoc(0.9, 20);

        $this->assertSame('12.0448.1187', $kq[0]['ma_dvkt']);
        $this->assertSame(18, $kq[0]['so_ho_so_co_kem']);
    }

    /** @test */
    public function bang_tra_dong_tat_khong_vao_nhom()
    {
        Xml3176VatPhamKemDvkt::query()->update(['hieu_luc' => false]);
        for ($i = 0; $i < 20; $i++) {
            $this->dv("CT$i", '18.0219.0041');
            $this->thuoc("CT$i", '40.644');
        }

        $this->assertSame([], $this->l()->hocDvktBatBuoc(0.9, 20));
    }

    /** @test */
    public function ghi_them_cap_nhat_tat_bat_lai_giu_tay()
    {
        $l = $this->l();
        $d = ['ma_dvkt' => '18.0219.0041', 'ten_dvkt' => 'CLVT', 'so_ho_so' => 20, 'so_ho_so_co_kem' => 19, 'ty_le' => 0.95];
        Xml3176VpkdDvktBatBuoc::create(['ma_dvkt' => '99.9999.9999', 'nguon' => 'TAY']);

        $this->assertSame(['them' => 1, 'cap_nhat' => 0, 'tat' => 0, 'bat_lai' => 0], $l->ghiDvktBatBuoc([$d]));
        $this->assertSame(['them' => 0, 'cap_nhat' => 1, 'tat' => 0, 'bat_lai' => 0], $l->ghiDvktBatBuoc([['so_ho_so' => 30] + $d]));
        $this->assertSame(30, Xml3176VpkdDvktBatBuoc::where('ma_dvkt', '18.0219.0041')->first()->so_ho_so);

        $this->assertSame(['them' => 0, 'cap_nhat' => 0, 'tat' => 1, 'bat_lai' => 0], $l->ghiDvktBatBuoc([]));
        $m = Xml3176VpkdDvktBatBuoc::where('ma_dvkt', '18.0219.0041')->first();
        $this->assertFalse($m->hieu_luc);
        $this->assertSame(Xml3176VatPhamKemDvkt::GHI_CHU_TU_TAT, $m->ghi_chu);

        $this->assertSame(1, $l->ghiDvktBatBuoc([$d])['bat_lai']);
        $this->assertTrue(Xml3176VpkdDvktBatBuoc::where('ma_dvkt', '18.0219.0041')->first()->hieu_luc);

        Xml3176VpkdDvktBatBuoc::where('ma_dvkt', '18.0219.0041')->update(['hieu_luc' => false, 'ghi_chu' => 'Người dùng tắt']);
        $l->ghiDvktBatBuoc([$d]);
        $this->assertFalse(Xml3176VpkdDvktBatBuoc::where('ma_dvkt', '18.0219.0041')->first()->hieu_luc);
        $this->assertTrue(Xml3176VpkdDvktBatBuoc::where('ma_dvkt', '99.9999.9999')->first()->hieu_luc, 'Dong TAY khong bi dung');
    }
}
```

- [ ] **Step 2:** Run `DB_HOST=127.0.0.1 vendor/bin/phpunit tests/Unit/Xml3176/VatPhamKemDvkt/DvktBatBuocLearnerTest.php` → FAIL (method không có).

- [ ] **Step 3: Cài đặt** — trong `VatPhamKemDvktLearner.php`, thêm `use App\Models\BHYT\Xml3176VpkdDvktBatBuoc;` và hai method public sau `ghiUngVien()`:

```php
    /**
     * R3: DVKT ma >= $nguong ho so BHYT tra deu kem >= 1 vat pham cung nhom (vat pham co DVKT nay
     * trong danh sach me HIEU LUC cua bang tra) duoc BHYT tra, va co >= $toiThieu ho so. Doc bang tra
     * hien tai - goi SAU ghi(). Chi dem dong t_bhtt > 0 ca hai phia.
     */
    public function hocDvktBatBuoc(float $nguong, int $toiThieu, $tuNgay = null): array
    {
        $nhom = [];
        $tenDv = [];
        $maVp = ['VTYT' => [], 'THUOC' => []];
        foreach (Xml3176VatPhamKemDvkt::where('hieu_luc', true)->get(['loai', 'ma_vat_pham', 'ten_khop', 'ma_dvkt', 'ten_dvkt']) as $m) {
            $dv = VatPhamKemDvktKey::chuanHoaMaDvkt($m->ma_dvkt);
            $nhom[$dv][$m->loai . '|' . trim($m->ma_vat_pham) . '|' . $m->ten_khop] = true;
            $maVp[$m->loai][trim($m->ma_vat_pham)] = true;
            if (!isset($tenDv[$dv]) && $m->ten_dvkt !== null && $m->ten_dvkt !== '') {
                $tenDv[$dv] = $m->ten_dvkt;
            }
        }
        if (empty($nhom)) {
            return [];
        }

        // Ho so co DVKT duoc BHYT tra (ma goc va bien the _GT).
        $hs = [];
        foreach (array_chunk(array_keys($nhom), 250) as $lo) {
            $ma = array_merge($lo, array_map(function ($x) { return $x . '_GT'; }, $lo));
            $q = DB::table('xml3176_xml3s')->select('ma_lk', 'ma_dich_vu')
                ->where(function ($w) { $w->whereNull('ma_vat_tu')->orWhere('ma_vat_tu', ''); })
                ->whereIn('ma_dich_vu', $ma)->where('t_bhtt', '>', 0)
                ->groupBy('ma_lk', 'ma_dich_vu');
            if ($tuNgay) {
                $q->where('ngay_yl', '>=', $tuNgay);
            }
            foreach ($q->get() as $r) {
                $hs[VatPhamKemDvktKey::chuanHoaMaDvkt($r->ma_dich_vu)][$r->ma_lk] = true;
            }
        }
        $canLk = [];
        foreach ($hs as $lks) {
            $canLk += $lks;
        }

        // Vat pham duoc BHYT tra trong cac ho so do: ma_lk => {khoa vat pham}.
        $coVp = [];
        foreach (self::NGUON as $loai => $n) {
            foreach (array_chunk(array_keys($maVp[$loai]), 250) as $lo) {
                $q = DB::table($n['bang'])->select('ma_lk', "{$n['ma']} as ma", "{$n['ten']} as ten")
                    ->whereIn($n['ma'], $lo)->where('t_bhtt', '>', 0)
                    ->groupBy('ma_lk', $n['ma'], $n['ten']);
                if ($tuNgay) {
                    $q->where('ngay_yl', '>=', $tuNgay);
                }
                foreach ($q->cursor() as $r) {
                    if (isset($canLk[$r->ma_lk])) {
                        $coVp[$r->ma_lk][$loai . '|' . VatPhamKemDvktKey::khoa($loai, $r->ma, $r->ten)] = true;
                    }
                }
            }
        }

        $ra = [];
        foreach ($hs as $dv => $lks) {
            $n = count($lks);
            $co = 0;
            foreach (array_keys($lks) as $lk) {
                if (!empty(array_intersect_key($coVp[$lk] ?? [], $nhom[$dv]))) {
                    $co++;
                }
            }
            if ($n >= $toiThieu && $co / $n >= $nguong) {
                $ra[] = ['ma_dvkt' => (string) $dv, 'ten_dvkt' => $tenDv[$dv] ?? null, 'so_ho_so' => $n,
                    'so_ho_so_co_kem' => $co, 'ty_le' => round($co / $n, 4)];
            }
        }
        usort($ra, function ($a, $b) { return strcmp($a['ma_dvkt'], $b['ma_dvkt']); });

        return $ra;
    }

    /** Cung ngu nghia ghi(): chi dong HOC; giu hieu_luc/ghi_chu nguoi dung; tu tat / tu bat lai. */
    public function ghiDvktBatBuoc(array $dong): array
    {
        $kq = ['them' => 0, 'cap_nhat' => 0, 'tat' => 0, 'bat_lai' => 0];

        DB::transaction(function () use ($dong, &$kq) {
            $hienCo = Xml3176VpkdDvktBatBuoc::all()->keyBy('ma_dvkt');
            $daHoc = [];
            foreach ($dong as $d) {
                $daHoc[$d['ma_dvkt']] = true;
                $m = $hienCo->get($d['ma_dvkt']);
                if ($m === null) {
                    Xml3176VpkdDvktBatBuoc::create($d + ['nguon' => Xml3176VatPhamKemDvkt::NGUON_HOC, 'hieu_luc' => true]);
                    $kq['them']++;
                    continue;
                }
                if ($m->nguon !== Xml3176VatPhamKemDvkt::NGUON_HOC) {
                    continue;
                }
                $m->fill([
                    'ten_dvkt' => $d['ten_dvkt'] ?? $m->ten_dvkt,
                    'so_ho_so' => $d['so_ho_so'], 'so_ho_so_co_kem' => $d['so_ho_so_co_kem'], 'ty_le' => $d['ty_le'],
                ]);
                if (!$m->hieu_luc && $m->ghi_chu === Xml3176VatPhamKemDvkt::GHI_CHU_TU_TAT) {
                    $m->hieu_luc = true;
                    $m->ghi_chu = null;
                    $kq['bat_lai']++;
                }
                $m->save();
                $kq['cap_nhat']++;
            }
            foreach ($hienCo as $ma => $m) {
                if (isset($daHoc[$ma]) || $m->nguon !== Xml3176VatPhamKemDvkt::NGUON_HOC || !$m->hieu_luc) {
                    continue;
                }
                $m->hieu_luc = false;
                if ($m->ghi_chu === null || $m->ghi_chu === '') {
                    $m->ghi_chu = Xml3176VatPhamKemDvkt::GHI_CHU_TU_TAT;
                }
                $m->save();
                $kq['tat']++;
            }
        });

        return $kq;
    }
```

- [ ] **Step 4:** Run Step 2 → PASS (6 test). Run `DB_HOST=127.0.0.1 vendor/bin/phpunit tests/Unit/Xml3176/VatPhamKemDvkt` → xanh.

- [ ] **Step 5: Commit**

```bash
git add app/Services/Xml3176/VatPhamKemDvktLearner.php tests/Unit/Xml3176/VatPhamKemDvkt/DvktBatBuocLearnerTest.php
git commit -m "feat(xml3176): hoc danh sach DVKT bat buoc kem vat pham

Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>"
```

---

### Task 3: Nối vào lượt học

**Files:**
- Modify: `app/Services/Xml3176/VatPhamKemDvktHocLaiService.php`, `app/Console/Commands/Xml3176HocVatPhamKemDvkt.php`
- Modify tests: `tests/Unit/Xml3176/VatPhamKemDvkt/HocLaiServiceTest.php`, và mọi test trong thư mục chạy lượt học (`VatPhamKemDvktLenhTest.php`) — thêm 2 migration `create_xml3176_error_catalogs` + `2026_10_15_090000_…` vào `bootXml3176Sqlite`.

**Consumes:** Task 2. **Produces:** `ket_qua` lượt học có thêm `dvkt_bat_buoc` (mảng `them/cap_nhat/tat/bat_lai`) và `so_dvkt_bat_buoc` (số dòng hiệu lực sau khi ghi); nhãn `moTa` lượt XONG thêm `, DVKT bắt buộc {n}`; lệnh dòng lệnh (không `--thu`) in `DVKT bắt buộc kèm vật phẩm: {n}`.

- [ ] **Step 1: Test (đỏ)** — trong `HocLaiServiceTest.php`: thêm 2 migration vào `setUp` (sau `create_xml3176_vpkd_ung_vien_table`), rồi thêm:

```php
    /** @test */
    public function luot_hoc_ghi_dvkt_bat_buoc_vao_ket_qua_va_nhan()
    {
        config(['xml3176.vat_pham_kem_dvkt.dvkt_bat_buoc_toi_thieu' => 1]);
        // setUp: 10 ho so "Buong" gan 12.0448.1187 (9 lien ket) - them dong DVKT BHYT tra cho 10 ho so do
        for ($i = 0; $i < 10; $i++) {
            \Illuminate\Support\Facades\DB::table('xml3176_xml3s')->insert(['ma_lk' => 'A' . $i, 'stt' => 3,
                'ma_vat_tu' => '', 'ma_dich_vu' => '12.0448.1187', 'ten_dich_vu' => 'Đặt buồng', 't_bhtt' => 1000,
                'so_luong' => 1, 'ngay_yl' => '202609010800']);
        }
        $lan = Xml3176VpkdLanHoc::create(['user_id' => 7, 'trang_thai' => Xml3176VpkdLanHoc::CHO]);

        (new HocVatPhamKemDvktJob($lan->id))->handle();

        $lan = $lan->fresh();
        $this->assertSame(1, $lan->ket_qua['so_dvkt_bat_buoc']);
        $this->assertSame(1, $lan->ket_qua['dvkt_bat_buoc']['them']);
        $this->assertContains(', DVKT bắt buộc 1', $this->sv()->moTa($lan)['nhan']);
        $this->assertSame('12.0448.1187', \App\Models\BHYT\Xml3176VpkdDvktBatBuoc::first()->ma_dvkt);
    }
```

Trong test `loi_khi_chay_ghi_luot_loi_va_nem_lai` thêm assert: `$this->assertSame(0, \App\Models\BHYT\Xml3176VpkdDvktBatBuoc::count());`

- [ ] **Step 2:** Run `DB_HOST=127.0.0.1 vendor/bin/phpunit tests/Unit/Xml3176/VatPhamKemDvkt/HocLaiServiceTest.php` → FAIL.

- [ ] **Step 3: Service** — trong `thucHien`, thay khối `$ketQua = DB::transaction(function () use ($hoc, $lan) { ... });` bằng:

```php
            $ketQua = DB::transaction(function () use ($hoc, $lan, $tuNgay) {
                $g = $this->learner->ghi($hoc['dong']);
                $g['so_ung_vien'] = $this->learner->ghiUngVien($hoc['ung_vien'], $lan->id);
                // R3: hoc tu bang tra VUA ghi, cung giao dich.
                $bb = $this->learner->hocDvktBatBuoc(
                    (float) config('xml3176.vat_pham_kem_dvkt.dvkt_bat_buoc_nguong', 0.9),
                    (int) config('xml3176.vat_pham_kem_dvkt.dvkt_bat_buoc_toi_thieu', 20),
                    $tuNgay
                );
                $g['dvkt_bat_buoc'] = $this->learner->ghiDvktBatBuoc($bb);
                $g['so_dvkt_bat_buoc'] = Xml3176VpkdDvktBatBuoc::where('hieu_luc', true)->count();

                return $g;
            });
```

Thêm `use App\Models\BHYT\Xml3176VpkdDvktBatBuoc;`. Trong `moTa`, nhánh `XONG`, đổi format thành `'Xong %s bởi %s: thêm %d, cập nhật %d, tắt %d, bật lại %d, ứng viên %d, DVKT bắt buộc %d'` và thêm đối số `$k['so_dvkt_bat_buoc'] ?? 0` ở cuối.

- [ ] **Step 4: Lệnh** — trong `Xml3176HocVatPhamKemDvkt.php`, nhánh không `--thu`, ngay sau dòng in `Ứng viên: …` thêm:

```php
            $this->info('DVKT bắt buộc kèm vật phẩm: ' . ($k['so_dvkt_bat_buoc'] ?? 0));
```

- [ ] **Step 5:** Thêm 2 migration vào `bootXml3176Sqlite` của `VatPhamKemDvktLenhTest.php` (và bất kỳ test nào khác trong thư mục chạy lượt học mà đỏ vì thiếu bảng) — không đổi assert. Test `mo_ta_luot_xong_co_nguoi_va_so_lieu` (assert chuỗi nhãn) sửa chuỗi kỳ vọng thành `'bởi tracnn: thêm 1, cập nhật 0, tắt 0, bật lại 0, ứng viên 1, DVKT bắt buộc 0'`.

- [ ] **Step 6:** Run `DB_HOST=127.0.0.1 vendor/bin/phpunit tests/Unit/Xml3176/VatPhamKemDvkt` → xanh.

- [ ] **Step 7: Commit**

```bash
git add app/Services/Xml3176/VatPhamKemDvktHocLaiService.php app/Console/Commands/Xml3176HocVatPhamKemDvkt.php tests/Unit/Xml3176/VatPhamKemDvkt
git commit -m "feat(xml3176): luot hoc sinh danh sach DVKT bat buoc kem vat pham

Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>"
```

---

### Task 4: Bảng tra nạp danh sách, quy tắc R3

**Files:**
- Modify: `app/Services/Xml3176/VatPhamKemDvktLookup.php`, `app/Services/Xml3176/VatPhamKemDvktRule.php`, `database/seeds/Xml3176ErrorCatalogVatPhamKemDvktSeeder.php`
- Test: `tests/Unit/Xml3176/VatPhamKemDvkt/DvktBatBuocRuleTest.php`; sửa `VatPhamKemDvktSeederTest.php` nếu cần

**Produces:**
- `VatPhamKemDvktLookup::tuDong($dong, $batBuoc = []): array` — thêm khoá `'DVKT_BAT_BUOC' => [ma_dvkt => ten_dvkt]` (bỏ dòng `hieu_luc` false) và `'TEN_VAT_PHAM' => ["LOAI|khoa" => ten_vat_pham]`. `bang()` chọn thêm cột `ten_vat_pham` và nạp `Xml3176VpkdDvktBatBuoc` (chỉ khi `Schema::hasTable('xml3176_vpkd_dvkt_bat_buoc')`, `hieu_luc = true`).
- `VatPhamKemDvktRule::TEN['DVKT_THIEU_VAT_PHAM_KEM'] = 'DVKT bắt buộc kèm vật phẩm nhưng không có vật phẩm'`; `kiem()` trả thêm lỗi R3.

- [ ] **Step 1: Test (đỏ)** — `tests/Unit/Xml3176/VatPhamKemDvkt/DvktBatBuocRuleTest.php`:

```php
<?php

namespace Tests\Unit\Xml3176\VatPhamKemDvkt;

use App\Services\Xml3176\VatPhamKemDvktLookup;
use App\Services\Xml3176\VatPhamKemDvktRule;
use Tests\TestCase;

class DvktBatBuocRuleTest extends TestCase
{
    private function rule(array $batBuoc = ['18.0219.0041' => 'CLVT tầng trên ổ bụng có cản quang']): VatPhamKemDvktRule
    {
        return new VatPhamKemDvktRule(VatPhamKemDvktLookup::tuDong([
            ['loai' => 'THUOC', 'ma_vat_pham' => '40.644', 'ten_vat_pham' => 'Omnipaque', 'ten_khop' => '', 'ma_dvkt' => '18.0219.0041', 'ten_dvkt' => 'CLVT'],
            ['loai' => 'THUOC', 'ma_vat_pham' => '40.645', 'ten_vat_pham' => 'Iopamiro', 'ten_khop' => '', 'ma_dvkt' => '18.0219.0041', 'ten_dvkt' => 'CLVT'],
        ], array_map(function ($ma, $ten) { return ['ma_dvkt' => $ma, 'ten_dvkt' => $ten]; }, array_keys($batBuoc), $batBuoc)));
    }

    private function dv($ma, $bhtt)
    {
        return (object) ['ma_dich_vu' => $ma, 'ma_vat_tu' => '', 'ten_dich_vu' => 'CLVT tầng trên ổ bụng', 't_bhtt' => $bhtt, 'so_luong' => 1, 'ngay_yl' => '202610070900'];
    }

    private function th($ma, $ten, $bhtt)
    {
        return (object) ['ma_thuoc' => $ma, 'ten_thuoc' => $ten, 'ma_dich_vu' => null, 't_bhtt' => $bhtt, 'ngay_yl' => '202610070900'];
    }

    private function keys(array $loi): array
    {
        return array_column($loi, 'key');
    }

    /** @test */
    public function co_dvkt_va_thuoc_bhyt_thi_khong_loi()
    {
        $this->assertSame([], $this->rule()->kiem([$this->dv('18.0219.0041', 522960)], [$this->th('40.645', 'Iopamiro', 295680)]));
    }

    /** @test */
    public function khong_co_vat_pham_thi_bao()
    {
        $loi = $this->rule()->kiem([$this->dv('18.0219.0041', 522960)], []);

        $this->assertSame(['DVKT_THIEU_VAT_PHAM_KEM'], $this->keys($loi));
        $this->assertContains('hồ sơ không có vật phẩm đi kèm', $loi[0]['mo_ta']);
        $this->assertContains('Omnipaque', $loi[0]['mo_ta']);
        $this->assertContains('522.960đ', $loi[0]['mo_ta']);
        $this->assertContains('07/10/2026', $loi[0]['mo_ta']);
    }

    /** @test */
    public function co_vat_pham_quy_khong_tra_thi_bao_rieng()
    {
        $loi = $this->rule()->kiem([$this->dv('18.0219.0041', 522960)], [$this->th('40.644', 'Omnipaque', 0)]);

        $this->assertSame(['DVKT_THIEU_VAT_PHAM_KEM'], $this->keys($loi));
        $this->assertContains('có Omnipaque nhưng quỹ BHYT không thanh toán vật phẩm', $loi[0]['mo_ta']);
    }

    /** @test */
    public function dvkt_quy_khong_tra_hoac_khong_trong_danh_sach_thi_im_lang()
    {
        $this->assertSame([], $this->rule()->kiem([$this->dv('18.0219.0041', 0)], []));
        $this->assertSame([], $this->rule([])->kiem([$this->dv('18.0219.0041', 522960)], []));
    }

    /** @test */
    public function dvkt_gt_chuan_hoa()
    {
        $this->assertSame(['DVKT_THIEU_VAT_PHAM_KEM'], $this->keys($this->rule()->kiem([$this->dv('18.0219.0041_GT', 1000)], [])));
    }

    /** @test */
    public function tu_dong_bo_dong_bat_buoc_bi_tat()
    {
        $b = VatPhamKemDvktLookup::tuDong([], [['ma_dvkt' => '18.0219.0041', 'ten_dvkt' => 'X', 'hieu_luc' => false]]);

        $this->assertSame([], $b['DVKT_BAT_BUOC']);
    }
}
```

- [ ] **Step 2:** Run `DB_HOST=127.0.0.1 vendor/bin/phpunit tests/Unit/Xml3176/VatPhamKemDvkt/DvktBatBuocRuleTest.php` → FAIL.

- [ ] **Step 3: Lookup** — trong `VatPhamKemDvktLookup`:
  - `bang()`: thêm `'ten_vat_pham'` vào danh sách cột `get([...])`; thêm biến

```php
            $batBuoc = Schema::hasTable('xml3176_vpkd_dvkt_bat_buoc')
                ? \App\Models\BHYT\Xml3176VpkdDvktBatBuoc::where('hieu_luc', true)->get(['ma_dvkt', 'ten_dvkt'])
                : [];
            self::$bang = self::tuDong($dong, $batBuoc);
```

  - `tuDong($dong, $batBuoc = [])`: khởi tạo `$bang = ['VTYT' => [], 'THUOC' => [], 'DVKT_BAT_BUOC' => [], 'TEN_VAT_PHAM' => []];`; trong vòng lặp dòng bảng tra, sau khi gán `$bang[$d->loai][$khoa][...]` thêm `$bang['TEN_VAT_PHAM'][$d->loai . '|' . $khoa] = (string) ($d->ten_vat_pham ?? $d->ma_vat_pham);`; sau vòng lặp thêm:

```php
        foreach ($batBuoc as $b) {
            $b = is_array($b) ? (object) $b : $b;
            if (isset($b->hieu_luc) && !$b->hieu_luc) {
                continue;
            }
            $bang['DVKT_BAT_BUOC'][VatPhamKemDvktKey::chuanHoaMaDvkt($b->ma_dvkt)] = (string) ($b->ten_dvkt ?? '');
        }
```

- [ ] **Step 4: Rule** — trong `VatPhamKemDvktRule`:
  - Thêm vào `TEN`: `'DVKT_THIEU_VAT_PHAM_KEM' => 'DVKT bắt buộc kèm vật phẩm nhưng không có vật phẩm',`
  - `gomDvkt`: khi khởi tạo thêm `'ngay' => ''`; sau khi cộng `bhtt`, nếu `$bhtt > 0` cập nhật ngày sớm nhất:

```php
                $ngay = (string) ($r->ngay_yl ?? '');
                if ($ngay !== '' && ($dv[$k]['ngay'] === '' || strcmp($ngay, $dv[$k]['ngay']) < 0)) {
                    $dv[$k]['ngay'] = $ngay;
                }
```

  - `kiem()`: thêm `$this->r3($vtyt, $thuoc, $dvkt)` vào cuối `array_merge(...)`.
  - Thêm method:

```php
    /**
     * R3: DVKT trong danh sach bat buoc duoc BHYT tra ma ho so khong co vat pham cung nhom duoc BHYT tra.
     * Nhom(D) = vat pham co D trong danh sach me (bang tra hieu luc).
     */
    private function r3(array $vtyt, array $thuoc, array $dvkt): array
    {
        $batBuoc = $this->bang['DVKT_BAT_BUOC'] ?? [];
        if (empty($batBuoc)) {
            return [];
        }
        $nhom = [];
        foreach (['VTYT', 'THUOC'] as $loai) {
            foreach ($this->bang[$loai] ?? [] as $khoa => $me) {
                foreach ($me as $dv => $_) {
                    $nhom[$dv][$loai . '|' . $khoa] = true;
                }
            }
        }
        $co = [];
        foreach (['VTYT' => $vtyt, 'THUOC' => $thuoc] as $loai => $ds) {
            foreach ($ds as $d) {
                $k = $loai . '|' . VatPhamKemDvktKey::khoa($loai, $d->ma, $d->ten);
                $co[$k]['bhtt'] = ($co[$k]['bhtt'] ?? 0.0) + $d->t_bhtt;
                $co[$k]['ten'] = $d->ten;
            }
        }

        $key = 'DVKT_THIEU_VAT_PHAM_KEM';
        $ra = [];
        foreach ($batBuoc as $dv => $tenBb) {
            if (!isset($dvkt[$dv]) || $dvkt[$dv]['bhtt'] <= 0 || empty($nhom[$dv])) {
                continue;
            }
            $tra = false;
            $khongTra = [];
            foreach ($nhom[$dv] as $k => $_) {
                if (!isset($co[$k])) {
                    continue;
                }
                if ($co[$k]['bhtt'] > 0) {
                    $tra = true;
                    break;
                }
                $khongTra[$co[$k]['ten']] = true;
            }
            if ($tra) {
                continue;
            }
            $dau = sprintf('%s %s BHYT trả %s, YL %s: ', $dv, $dvkt[$dv]['ten'] !== '' ? $dvkt[$dv]['ten'] : $tenBb,
                self::tien($dvkt[$dv]['bhtt']), self::ngay($dvkt[$dv]['ngay']));
            if (!empty($khongTra)) {
                $moTa = $dau . 'có ' . self::dsTen(array_keys($khongTra)) . ' nhưng quỹ BHYT không thanh toán vật phẩm.';
            } else {
                $ten = [];
                foreach (array_keys($nhom[$dv]) as $k) {
                    $ten[$this->bang['TEN_VAT_PHAM'][$k] ?? $k] = true;
                }
                $moTa = $dau . 'hồ sơ không có vật phẩm đi kèm (cần một trong: ' . self::dsTen(array_keys($ten))
                    . '). Kiểm tra kê đúng mã DVKT (vd có/không cản quang).';
            }
            $ra[] = ['key' => $key, 'ten' => self::TEN[$key], 'mo_ta' => $moTa];
        }

        return $ra;
    }
```

  (`dsTen` sẵn có cắt 3 tên + "và N vật phẩm khác"; spec ghi tối đa 5 — dùng `dsTen` để thống nhất thông điệp R1/R2.)

- [ ] **Step 5: Seeder** — `VatPhamKemDvktSeederTest` duyệt mọi khoá trong `VatPhamKemDvktRule::TEN`. Trong `Xml3176ErrorCatalogVatPhamKemDvktSeeder.php`, mỗi phần tử `$rules` thêm phần tử thứ 4 là mức `critical` (`true` cho 4 mã cũ), thêm dòng:

```php
            ['XMLComplete_DVKT_THIEU_VAT_PHAM_KEM', 'DVKT bắt buộc kèm vật phẩm nhưng không có vật phẩm',
                'DVKT mà hầu hết hồ sơ BHYT đều kèm vật phẩm (vd CLVT có tiêm thuốc cản quang) được quỹ thanh toán nhưng hồ sơ không có vật phẩm đi kèm được quỹ thanh toán.', false],
```

  và đổi vòng lặp thành `list($code, $name, $description, $critical) = $r;` … `'critical_error' => $critical`.

- [ ] **Step 6:** Run `DB_HOST=127.0.0.1 vendor/bin/phpunit tests/Unit/Xml3176/VatPhamKemDvkt` → xanh (sửa `VatPhamKemDvktSeederTest` chỉ khi assert `'critical_error' => true` không còn khớp chuỗi — giữ ý: 4 mã cũ vẫn nghiêm trọng).

- [ ] **Step 7: Commit**

```bash
git add app/Services/Xml3176/VatPhamKemDvktLookup.php app/Services/Xml3176/VatPhamKemDvktRule.php database/seeds/Xml3176ErrorCatalogVatPhamKemDvktSeeder.php tests/Unit/Xml3176/VatPhamKemDvkt
git commit -m "feat(xml3176): quy tac R3 DVKT bat buoc kem vat pham nhung khong co vat pham

Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>"
```

---

### Task 5: Thao tác quản trị + controller + route

**Files:**
- Modify: `app/Services/Xml3176/VatPhamKemDvktQuanTri.php`, `app/Http/Controllers/Category/VatPhamKemDvktController.php`, `routes/web.php`
- Test: `tests/Unit/Xml3176/VatPhamKemDvkt/DvktBatBuocQuanTriTest.php`

**Produces:**
- `datHieuLuc`, `datGhiChu`, `xoa` nhận `\Illuminate\Database\Eloquent\Model $m` (dùng cho cả hai bảng; logic không đổi).
- `themBatBuoc($maDvkt, $ghiChu, $nguoiSuaId): Xml3176VpkdDvktBatBuoc`.
- Controller: `batBuocDatatable`, `batBuocHieuLuc($r,$id)`, `batBuocGhiChu($r,$id)`, `batBuocXoa($id)`, `batBuocThem($r)`; route `bat-buoc-datatable` (GET), `bat-buoc-hieu-luc`, `bat-buoc-ghi-chu`, `bat-buoc-xoa` (POST `bat-buoc/{id}/…`), `bat-buoc-them` (POST).

- [ ] **Step 1: Test (đỏ)** — `tests/Unit/Xml3176/VatPhamKemDvkt/DvktBatBuocQuanTriTest.php`:

```php
<?php

namespace Tests\Unit\Xml3176\VatPhamKemDvkt;

use App\Http\Controllers\Category\VatPhamKemDvktController;
use App\Models\BHYT\Xml3176VatPhamKemDvkt;
use App\Models\BHYT\Xml3176VpkdDvktBatBuoc;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Route;
use Tests\Support\Xml3176RuleTestSupport;
use Tests\TestCase;

class DvktBatBuocQuanTriTest extends TestCase
{
    use Xml3176RuleTestSupport;

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
            '2026_01_09_161708_create_xml3176_error_catalogs_table.php',
            '2026_10_15_090000_create_xml3176_vpkd_dvkt_bat_buoc_table.php',
        ]);
        $u = new User();
        $u->id = 7;
        Auth::setUser($u);
        Xml3176VatPhamKemDvkt::create(['loai' => 'THUOC', 'ma_vat_pham' => '40.644', 'ten_khop' => '',
            'ma_dvkt' => '18.0219.0041', 'ten_dvkt' => 'CLVT', 'nguon' => 'HOC']);
    }

    private function c(): VatPhamKemDvktController
    {
        return $this->app->make(VatPhamKemDvktController::class);
    }

    /** @test */
    public function route_moi_co_auth_va_category_manager()
    {
        foreach (['bat-buoc-datatable', 'bat-buoc-hieu-luc', 'bat-buoc-ghi-chu', 'bat-buoc-xoa', 'bat-buoc-them'] as $ten) {
            $r = Route::getRoutes()->getByName('category-bhyt.vat-pham-kem-dvkt.' . $ten);
            $this->assertNotNull($r, "Thieu route $ten");
            $this->assertContains('auth', $r->gatherMiddleware());
            $this->assertContains('checkrole:category-manager', $r->gatherMiddleware());
        }
    }

    /** @test */
    public function them_tay_kiem_nhom_trung_va_chuan_hoa()
    {
        $r = $this->c()->batBuocThem(Request::create('/', 'POST', ['ma_dvkt' => '99.9999.9999']));
        $this->assertSame(422, $r->getStatusCode());
        $this->assertSame('DVKT chưa là DVKT mẹ của vật phẩm nào trong bảng tra — không có gì để kiểm', $r->getData()->message);

        $r = $this->c()->batBuocThem(Request::create('/', 'POST', ['ma_dvkt' => '18.0219.0041_GT', 'ghi_chu' => 'duyệt']));
        $this->assertSame(200, $r->getStatusCode());
        $m = Xml3176VpkdDvktBatBuoc::first();
        $this->assertSame('18.0219.0041', $m->ma_dvkt);
        $this->assertSame('TAY', $m->nguon);
        $this->assertSame('CLVT', $m->ten_dvkt);
        $this->assertSame(7, $m->nguoi_sua_id);

        $r = $this->c()->batBuocThem(Request::create('/', 'POST', ['ma_dvkt' => '18.0219.0041']));
        $this->assertSame(422, $r->getStatusCode());
        $this->assertSame('Đã có', $r->getData()->message);
    }

    /** @test */
    public function bat_tat_ghi_chu_xoa_dung_chung_luat_bang_tra()
    {
        $hoc = Xml3176VpkdDvktBatBuoc::create(['ma_dvkt' => '18.0219.0041', 'nguon' => 'HOC']);

        $this->assertSame(422, $this->c()->batBuocHieuLuc(Request::create('/', 'POST', ['hieu_luc' => 0]), $hoc->id)->getStatusCode());
        $this->assertSame(200, $this->c()->batBuocHieuLuc(Request::create('/', 'POST', ['hieu_luc' => 0, 'ghi_chu' => 'sai']), $hoc->id)->getStatusCode());
        $this->assertFalse($hoc->fresh()->hieu_luc);
        $this->assertSame(7, $hoc->fresh()->nguoi_sua_id);

        $this->assertSame(200, $this->c()->batBuocGhiChu(Request::create('/', 'POST', ['ghi_chu' => '']), $hoc->id)->getStatusCode());
        $this->assertNull($hoc->fresh()->ghi_chu);

        $this->assertSame(422, $this->c()->batBuocXoa($hoc->id)->getStatusCode());
        $this->assertSame(404, $this->c()->batBuocXoa(999)->getStatusCode());
        $tay = Xml3176VpkdDvktBatBuoc::create(['ma_dvkt' => '18.0220.0041', 'nguon' => 'TAY']);
        $this->assertSame(200, $this->c()->batBuocXoa($tay->id)->getStatusCode());
    }

    /** @test */
    public function datatable_tra_du_lieu()
    {
        DB::table('users')->insert(['id' => 7, 'name' => 'Nguyen Van A', 'email' => 'a@b.c', 'password' => 'x']);
        Xml3176VpkdDvktBatBuoc::create(['ma_dvkt' => '18.0219.0041', 'ten_dvkt' => 'CLVT', 'so_ho_so' => 897,
            'so_ho_so_co_kem' => 885, 'ty_le' => 0.9866, 'nguon' => 'HOC', 'nguoi_sua_id' => 7, 'sua_luc' => '2026-10-14 10:00:00']);
        $cot = [];
        foreach (['ma_dvkt', 'ten_dvkt', 'so_ho_so', 'so_ho_so_co_kem', 'ty_le', 'nguon', 'hieu_luc', 'ghi_chu', 'nguoi_sua_luc', 'thao_tac'] as $i => $c) {
            $cot[] = ['data' => $c, 'name' => in_array($c, ['nguoi_sua_luc', 'thao_tac'], true) ? '' : 'xml3176_vpkd_dvkt_bat_buoc.' . $c,
                'searchable' => in_array($c, ['ma_dvkt', 'ten_dvkt'], true) ? 'true' : 'false', 'orderable' => $c === 'thao_tac' ? 'false' : 'true',
                'search' => ['value' => '', 'regex' => 'false']];
        }
        $r = $this->c()->batBuocDatatable(Request::create('/', 'GET', ['draw' => 1, 'start' => 0, 'length' => 10,
            'columns' => $cot, 'order' => [['column' => 0, 'dir' => 'asc']], 'search' => ['value' => '18.0219', 'regex' => 'false']]));
        $d = $r->getData(true);

        $this->assertSame(1, $d['recordsFiltered']);
        $this->assertContains('vpkd-bb-hieu-luc', $d['data'][0]['hieu_luc']);
        $this->assertContains('Nguyen Van A', $d['data'][0]['nguoi_sua_luc']);
        $this->assertContains('98,7%', $d['data'][0]['ty_le']);
    }
}
```

(Nếu insert `users` lỗi vì cột NOT NULL khác, bổ sung giá trị tối thiểu — không sửa migration.)

- [ ] **Step 2:** Run test → FAIL.

- [ ] **Step 3: QuanTri** — trong `VatPhamKemDvktQuanTri.php`:
  - Đổi type-hint `Xml3176VatPhamKemDvkt $m` thành `\Illuminate\Database\Eloquent\Model $m` ở `datHieuLuc`, `datGhiChu`, `xoa` (giữ nguyên thân hàm — hằng `NGUON_HOC/NGUON_TAY/GHI_CHU_TU_TAT` lấy từ `Xml3176VatPhamKemDvkt`, áp được cho cả hai bảng). Thêm docblock 1 dòng: `/** Dung chung cho bang tra va bang DVKT bat buoc (cung cot hieu_luc, ghi_chu, nguon, nguoi_sua_id, sua_luc). */`
  - Thêm `use App\Models\BHYT\Xml3176VpkdDvktBatBuoc;` và method:

```php
    public function themBatBuoc($maDvkt, $ghiChu, $nguoiSuaId): Xml3176VpkdDvktBatBuoc
    {
        $ma = VatPhamKemDvktKey::chuanHoaMaDvkt($maDvkt);
        if ($ma === '' || mb_strlen($ma, 'UTF-8') > 50) {
            throw new \InvalidArgumentException('Mã DVKT phải từ 1 đến 50 ký tự');
        }
        $ghiChu = self::ghiChuHopLe($ghiChu);
        $me = Xml3176VatPhamKemDvkt::where('hieu_luc', true)->whereIn('ma_dvkt', [$ma, $ma . '_GT'])->first();
        if ($me === null) {
            throw new \InvalidArgumentException('DVKT chưa là DVKT mẹ của vật phẩm nào trong bảng tra — không có gì để kiểm');
        }
        if (Xml3176VpkdDvktBatBuoc::where('ma_dvkt', $ma)->exists()) {
            throw new \InvalidArgumentException('Đã có');
        }
        $ten = ServiceCatalog::where('ma_dich_vu', $ma)->value('ten_dich_vu') ?: $me->ten_dvkt;

        return Xml3176VpkdDvktBatBuoc::create([
            'ma_dvkt' => $ma, 'ten_dvkt' => $ten, 'so_ho_so' => 0, 'so_ho_so_co_kem' => 0, 'ty_le' => null,
            'nguon' => Xml3176VatPhamKemDvkt::NGUON_TAY, 'hieu_luc' => true, 'ghi_chu' => $ghiChu,
        ] + self::vet($nguoiSuaId));
    }
```

- [ ] **Step 4: Controller** — trong `VatPhamKemDvktController.php`:
  - Đổi `voiDong($id, callable $f)` thành `voiDong($id, callable $f, string $lop = Xml3176VatPhamKemDvkt::class)` và dùng `$lop::find($id)`.
  - Thêm `use App\Models\BHYT\Xml3176VpkdDvktBatBuoc;` và các method:

```php
    public function batBuocDatatable(Request $request)
    {
        $q = Xml3176VpkdDvktBatBuoc::query()
            ->leftJoin('users', 'users.id', '=', 'xml3176_vpkd_dvkt_bat_buoc.nguoi_sua_id')
            ->select('xml3176_vpkd_dvkt_bat_buoc.*', 'users.name as nguoi_sua');
        if ($request->filled('nguon')) {
            $q->where('xml3176_vpkd_dvkt_bat_buoc.nguon', $request->input('nguon'));
        }
        if ($request->filled('hieu_luc')) {
            $q->where('xml3176_vpkd_dvkt_bat_buoc.hieu_luc', (int) $request->input('hieu_luc'));
        }

        return Datatables::of($q)
            ->editColumn('ty_le', function ($m) {
                return $m->ty_le === null ? '' : number_format($m->ty_le * 100, 1, ',', '.') . '%';
            })
            ->editColumn('hieu_luc', function ($m) {
                return '<input type="checkbox" class="vpkd-bb-hieu-luc" data-id="' . $m->id . '" data-nguon="' . e($m->nguon) . '"'
                    . ' data-ghi-chu="' . e($m->ghi_chu) . '"' . ($m->hieu_luc ? ' checked' : '') . '>';
            })
            ->editColumn('ghi_chu', function ($m) {
                return e($m->ghi_chu) . ' <a href="#" class="vpkd-bb-ghi-chu" data-id="' . $m->id . '" data-ghi-chu="'
                    . e($m->ghi_chu) . '" title="Sửa ghi chú"><i class="fa fa-pencil"></i></a>';
            })
            ->addColumn('nguoi_sua_luc', function ($m) {
                return $m->sua_luc ? e($m->nguoi_sua ?: '#' . $m->nguoi_sua_id) . '<br>' . $m->sua_luc->format('d/m/Y H:i') : '';
            })
            ->addColumn('thao_tac', function ($m) {
                return $m->nguon === Xml3176VatPhamKemDvkt::NGUON_TAY
                    ? '<button class="btn btn-xs btn-danger vpkd-bb-xoa" data-id="' . $m->id . '"><i class="fa fa-trash"></i></button>'
                    : '';
            })
            ->rawColumns(['hieu_luc', 'ghi_chu', 'nguoi_sua_luc', 'thao_tac'])
            ->toJson();
    }

    public function batBuocHieuLuc(Request $request, $id)
    {
        return $this->voiDong($id, function ($m) use ($request) {
            $this->quanTri->datHieuLuc($m, (bool) (int) $request->input('hieu_luc'), $request->input('ghi_chu'), Auth::id());
        }, Xml3176VpkdDvktBatBuoc::class);
    }

    public function batBuocGhiChu(Request $request, $id)
    {
        return $this->voiDong($id, function ($m) use ($request) {
            $this->quanTri->datGhiChu($m, $request->input('ghi_chu'), Auth::id());
        }, Xml3176VpkdDvktBatBuoc::class);
    }

    public function batBuocXoa($id)
    {
        return $this->voiDong($id, function ($m) {
            $this->quanTri->xoa($m);
        }, Xml3176VpkdDvktBatBuoc::class);
    }

    public function batBuocThem(Request $request)
    {
        return $this->bat(function () use ($request) {
            $m = $this->quanTri->themBatBuoc($request->input('ma_dvkt'), $request->input('ghi_chu'), Auth::id());

            return response()->json(['message' => 'Đã thêm DVKT ' . $m->ma_dvkt]);
        });
    }
```

- [ ] **Step 5: Route** — trong `routes/web.php`, nhóm `bhyt/vat-pham-kem-dvkt` (as `category-bhyt.vat-pham-kem-dvkt.`), trước dấu `});` đóng nhóm, thêm:

```php
            Route::get('bat-buoc-datatable', $c . 'batBuocDatatable')->name('bat-buoc-datatable');
            Route::post('bat-buoc/{id}/hieu-luc', $c . 'batBuocHieuLuc')->where('id', '[0-9]+')->name('bat-buoc-hieu-luc');
            Route::post('bat-buoc/{id}/ghi-chu', $c . 'batBuocGhiChu')->where('id', '[0-9]+')->name('bat-buoc-ghi-chu');
            Route::post('bat-buoc/{id}/xoa', $c . 'batBuocXoa')->where('id', '[0-9]+')->name('bat-buoc-xoa');
            Route::post('bat-buoc/them', $c . 'batBuocThem')->name('bat-buoc-them');
```

- [ ] **Step 6:** Run test → PASS (4 test). Run `DB_HOST=127.0.0.1 vendor/bin/phpunit tests/Unit/Xml3176/VatPhamKemDvkt` → xanh (các test cũ của `QuanTriServiceTest` vẫn qua với type-hint `Model`).

- [ ] **Step 7: Commit**

```bash
git add app/Services/Xml3176/VatPhamKemDvktQuanTri.php app/Http/Controllers/Category/VatPhamKemDvktController.php routes/web.php tests/Unit/Xml3176/VatPhamKemDvkt/DvktBatBuocQuanTriTest.php
git commit -m "feat(xml3176): quan tri danh sach DVKT bat buoc kem vat pham

Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>"
```

---

### Task 6: Tab giao diện

**Files:**
- Modify: `resources/views/category/bhyt/vat_pham_kem_dvkt.blade.php`
- Test: `tests/Unit/Xml3176/VatPhamKemDvkt/QuanTriViewTest.php`

- [ ] **Step 1: Test (đỏ)** — trong `QuanTriViewTest.php`, method `view_goi_du_route_cua_man`, thêm vào mảng route: `'bat-buoc-datatable', 'bat-buoc-hieu-luc', 'bat-buoc-ghi-chu', 'bat-buoc-xoa', 'bat-buoc-them'`; thêm test:

```php
    /** @test */
    public function co_tab_dvkt_bat_buoc()
    {
        $src = $this->nguon();
        $this->assertContains('vpkd-tab-bat-buoc', $src);
        $this->assertContains('DVKT bắt buộc kèm vật phẩm', $src);
        $this->assertContains('vpkd-bb-hieu-luc', $src);
    }
```

- [ ] **Step 2:** Run `DB_HOST=127.0.0.1 vendor/bin/phpunit tests/Unit/Xml3176/VatPhamKemDvkt/QuanTriViewTest.php` → FAIL.

- [ ] **Step 3: View** — chỉnh `vat_pham_kem_dvkt.blade.php`:

  (a) Trong `<ul class="nav nav-tabs">`, sau `<li>` Ứng viên thêm:

```blade
    <li><a href="#vpkd-tab-bat-buoc" data-toggle="tab">DVKT bắt buộc kèm vật phẩm</a></li>
```

  (b) Trong `<div class="tab-content">`, sau `div#vpkd-tab-ung-vien` thêm:

```blade
    <div class="tab-pane" id="vpkd-tab-bat-buoc">
        <div class="panel panel-default"><div class="panel-body">
            <p class="text-muted">DVKT mà hầu hết hồ sơ BHYT đều kèm vật phẩm (học tự động). Hồ sơ có DVKT này được BHYT trả mà không có vật phẩm đi kèm sẽ bị cảnh báo <code>XMLComplete_DVKT_THIEU_VAT_PHAM_KEM</code>.</p>
            <div class="form-inline" style="margin-bottom:10px">
                <select id="loc-bb-nguon" class="form-control input-sm"><option value="">Nguồn: tất cả</option><option>HOC</option><option>TAY</option></select>
                <select id="loc-bb-hieu-luc" class="form-control input-sm"><option value="">Hiệu lực: tất cả</option><option value="1">Đang dùng</option><option value="0">Đã tắt</option></select>
                <select id="bb-them-ma" class="form-control input-sm" style="width:320px"><option></option></select>
                <button id="bb-them" class="btn btn-success btn-sm"><i class="fa fa-plus"></i> Thêm DVKT tay</button>
            </div>
            <table id="vpkd-bat-buoc" class="table display table-hover nowrap" width="100%">
                <thead><tr>
                    <th>Mã DVKT</th><th>Tên DVKT</th><th>Số HS</th><th>Số HS có kèm</th><th>Tỷ lệ</th>
                    <th>Nguồn</th><th>Hiệu lực</th><th>Ghi chú</th><th>Người sửa</th><th></th>
                </tr></thead>
            </table>
        </div></div>
    </div>
```

  (c) Trong `var URL_ID = {…}` thêm 3 khoá:

```js
        bbHieuLuc: "{{ route('category-bhyt.vat-pham-kem-dvkt.bat-buoc-hieu-luc', ['id' => '__ID__']) }}",
        bbGhiChu: "{{ route('category-bhyt.vat-pham-kem-dvkt.bat-buoc-ghi-chu', ['id' => '__ID__']) }}",
        bbXoa: "{{ route('category-bhyt.vat-pham-kem-dvkt.bat-buoc-xoa', ['id' => '__ID__']) }}",
```

  (d) Hàm `taiLai()` đổi thành `function taiLai() { bangTra.ajax.reload(null, false); ungVien.ajax.reload(null, false); batBuoc.ajax.reload(null, false); }` và khai báo `batBuoc` cùng dòng `var bangTra, ungVien, …` (thêm `batBuoc`).

  (e) Ngay trước dòng `// ---- Them dong tay ----` thêm:

```js
    // ---- DVKT bat buoc kem vat pham ----
    batBuoc = $('#vpkd-bat-buoc').DataTable({
        processing: true, serverSide: true, scrollX: true, order: [[2, 'desc']],
        ajax: { url: "{{ route('category-bhyt.vat-pham-kem-dvkt.bat-buoc-datatable') }}",
                data: function (d) { d.nguon = $('#loc-bb-nguon').val(); d.hieu_luc = $('#loc-bb-hieu-luc').val(); } },
        columns: [
            { data: 'ma_dvkt', name: 'xml3176_vpkd_dvkt_bat_buoc.ma_dvkt' },
            { data: 'ten_dvkt', name: 'xml3176_vpkd_dvkt_bat_buoc.ten_dvkt' },
            { data: 'so_ho_so', name: 'xml3176_vpkd_dvkt_bat_buoc.so_ho_so', searchable: false },
            { data: 'so_ho_so_co_kem', name: 'xml3176_vpkd_dvkt_bat_buoc.so_ho_so_co_kem', searchable: false },
            { data: 'ty_le', name: 'xml3176_vpkd_dvkt_bat_buoc.ty_le', searchable: false },
            { data: 'nguon', name: 'xml3176_vpkd_dvkt_bat_buoc.nguon', searchable: false },
            { data: 'hieu_luc', name: 'xml3176_vpkd_dvkt_bat_buoc.hieu_luc', searchable: false },
            { data: 'ghi_chu', name: 'xml3176_vpkd_dvkt_bat_buoc.ghi_chu', searchable: false },
            { data: 'nguoi_sua_luc', name: 'xml3176_vpkd_dvkt_bat_buoc.sua_luc', searchable: false },
            { data: 'thao_tac', orderable: false, searchable: false }
        ]
    });
    $('#loc-bb-nguon, #loc-bb-hieu-luc').on('change', function () { batBuoc.ajax.reload(); });
    $('a[href="#vpkd-tab-bat-buoc"]').on('shown.bs.tab', function () { batBuoc.columns.adjust(); });

    $('#vpkd-bat-buoc').on('change', '.vpkd-bb-hieu-luc', function () {
        var o = $(this), bat = o.is(':checked');
        Swal.fire({
            title: bat ? 'Bật lại DVKT này?' : 'Tắt DVKT này?', icon: 'warning', showCancelButton: true,
            input: 'text', inputValue: bat ? (o.attr('data-ghi-chu') || '') : '',
            inputPlaceholder: bat ? 'Ghi chú (tuỳ chọn)' : 'Lý do tắt (bắt buộc)',
            inputValidator: function (v) { if (!bat && !$.trim(v)) { return 'Cần ghi lý do khi tắt'; } }
        }).then(function (r) {
            if (!r.isConfirmed) { o.prop('checked', !bat); return; }
            $.post(url(URL_ID.bbHieuLuc, o.attr('data-id')), { _token: TOKEN, hieu_luc: bat ? 1 : 0, ghi_chu: r.value })
                .done(function () { toastr.success('Đã cập nhật.'); batBuoc.ajax.reload(null, false); })
                .fail(function (x) { o.prop('checked', !bat); baoLoi(x); });
        });
    });

    $('#vpkd-bat-buoc').on('click', '.vpkd-bb-ghi-chu', function (e) {
        e.preventDefault();
        var o = $(this);
        Swal.fire({ title: 'Ghi chú', input: 'text', inputValue: o.attr('data-ghi-chu') || '', showCancelButton: true })
            .then(function (r) {
                if (!r.isConfirmed) { return; }
                $.post(url(URL_ID.bbGhiChu, o.attr('data-id')), { _token: TOKEN, ghi_chu: r.value })
                    .done(function () { toastr.success('Đã lưu ghi chú.'); batBuoc.ajax.reload(null, false); })
                    .fail(baoLoi);
            });
    });

    $('#vpkd-bat-buoc').on('click', '.vpkd-bb-xoa', function () {
        var id = $(this).attr('data-id');
        Swal.fire({ title: 'Bạn có chắc chắn?', text: 'Xoá DVKT bổ sung tay này.', icon: 'warning', showCancelButton: true })
            .then(function (r) {
                if (!r.isConfirmed) { return; }
                $.post(url(URL_ID.bbXoa, id), { _token: TOKEN })
                    .done(function () { toastr.success('Đã xoá.'); batBuoc.ajax.reload(null, false); })
                    .fail(baoLoi);
            });
    });

    $('#bb-them-ma').select2({
        tags: true, placeholder: 'Mã hoặc tên DVKT', width: 'resolve',
        ajax: { url: "{{ route('category-bhyt.vat-pham-kem-dvkt.tim-dvkt') }}", delay: 300,
                data: function (p) { return { q: p.term }; } }
    });
    $('#bb-them').on('click', function () {
        var btn = $(this), ma = $('#bb-them-ma').val();
        if (!ma) { toastr.warning('Chọn mã DVKT.'); return; }
        btn.prop('disabled', true);
        $.post("{{ route('category-bhyt.vat-pham-kem-dvkt.bat-buoc-them') }}", { _token: TOKEN, ma_dvkt: ma })
            .done(function (r) { toastr.success(r.message); $('#bb-them-ma').val(null).trigger('change'); batBuoc.ajax.reload(null, false); })
            .fail(baoLoi)
            .always(function () { btn.prop('disabled', false); });
    });
```

- [ ] **Step 4:** Run Step 2 → PASS. Run `DB_HOST=127.0.0.1 vendor/bin/phpunit tests/Unit/Xml3176/VatPhamKemDvkt` → xanh.

- [ ] **Step 5: Commit**

```bash
git add resources/views/category/bhyt/vat_pham_kem_dvkt.blade.php tests/Unit/Xml3176/VatPhamKemDvkt/QuanTriViewTest.php
git commit -m "feat(xml3176): tab DVKT bat buoc kem vat pham tren man quan tri

Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>"
```

---

### Task 7: Hồi quy, nghiệm thu chỉ đọc, tài liệu

**Files:** Modify `docs/trien-khai-xml3176-vat-pham-kem-dvkt.md`.

- [ ] **Step 1:** `DB_HOST=127.0.0.1 vendor/bin/phpunit tests/Unit/Xml3176` → chỉ 4 lỗi có sẵn (Task 0).

- [ ] **Step 2: Nghiệm thu chỉ đọc** — script trong scratchpad (KHÔNG trong repo), dòng đầu `DB::statement('SET SESSION TRANSACTION READ ONLY');`. Vì bảng `xml3176_vpkd_dvkt_bat_buoc` chưa có trên CSDL thật và lệnh học ghi bảng, KHÔNG gọi `hocDvktBatBuoc` thật; thay vào đó dựng lại phép đếm của nó trong script (đọc bảng tra `xml3176_vat_pham_kem_dvkt` hiện có trên prod — chỉ đọc), ngưỡng 0.9 / 20, rồi `VatPhamKemDvktLookup::tuDong($bangTra, $danhSach)` + `VatPhamKemDvktRule::kiem` trên các hồ sơ nạp từ 01/09/2026 có DVKT trong danh sách. Kỳ vọng: danh sách gồm CLVT có cản quang (18.0219.0041, 18.0192.0041, 18.0169.0042, 18.0220.0041…), **không** có `18.0320.0065`, `18.0299.0065`, `18.0297.0065`; số hồ sơ R3 khoảng 30. In 10 ví dụ `ma_lk + mo_ta` để người dùng kiểm tay. Lệch kỳ vọng → báo, không chỉnh ngưỡng.

- [ ] **Step 3: Tài liệu** — trong `docs/trien-khai-xml3176-vat-pham-kem-dvkt.md`, mục "Làm bằng màn hình", thêm:

```markdown
**R3 — DVKT bắt buộc kèm vật phẩm (từ 15/10/2026).** Mỗi lượt Học lại sinh thêm danh sách DVKT mà ≥ 90% hồ sơ BHYT (≥ 20 hồ sơ) đều kèm vật phẩm cùng nhóm (cấu hình `vat_pham_kem_dvkt.dvkt_bat_buoc_nguong` / `_toi_thieu`). Hồ sơ có DVKT này được BHYT trả mà không có vật phẩm đi kèm → cảnh báo `XMLComplete_DVKT_THIEU_VAT_PHAM_KEM` (không chặn gửi; mã lỗi tự nạp khi migrate). Quản lý ở tab **DVKT bắt buộc kèm vật phẩm**. Sau khi cập nhật mã: bấm **Học lại** một lần.
```

- [ ] **Step 4: Commit**

```bash
git add docs/trien-khai-xml3176-vat-pham-kem-dvkt.md
git commit -m "docs(xml3176): huong dan quy tac R3 DVKT bat buoc kem vat pham

Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>"
```

---

## Self-review (đã chạy)

- Spec §3.1 → Task 1; §3.2–3.3 → Task 2, 3; §4 → Task 4; §5 → Task 5, 6; §7 → test các task + Task 7; §8 → Task 7.
- Khác spec có chủ đích: (1) `datHieuLuc/datGhiChu/xoa` dùng chung cho hai bảng (type-hint `Model`) thay vì 3 method `*BatBuoc` mới — cùng cột, cùng luật, tránh trùng mã; (2) thông điệp "cần một trong" dùng `dsTen` (3 tên + "và N vật phẩm khác") thống nhất R1/R2 thay vì 5 tên; (3) seeder có thêm mã R3 với `critical = false` để `VatPhamKemDvktSeederTest` (duyệt mọi khoá `TEN`) vẫn đúng.
- Tên nhất quán: `hocDvktBatBuoc`, `ghiDvktBatBuoc`, `so_dvkt_bat_buoc`, `dvkt_bat_buoc`, `DVKT_BAT_BUOC`, `TEN_VAT_PHAM`, `DVKT_THIEU_VAT_PHAM_KEM`, `themBatBuoc`, `batBuoc*`, route `bat-buoc-*`, class `vpkd-bb-*`.
