# VTYT/thuốc ngoài giá phải đi kèm DVKT mẹ — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Bắt hồ sơ XML3176 có VTYT/thuốc thanh toán ngoài giá DVKT được quỹ BHYT trả trong khi DVKT mẹ không được kê hoặc quỹ không trả.

**Architecture:** Bảng tra `xml3176_vat_pham_kem_dvkt` (vật phẩm → DVKT mẹ hợp lệ) được học tự động từ các dòng HIS đã gắn `ma_dich_vu`, lọc theo tỷ lệ gắn ≥ 80%, duyệt/bổ sung qua Excel. Logic kiểm nằm trong lớp thuần `VatPhamKemDvktRule` (không chạm CSDL), được `Xml3176CompleteChecker` gọi ở cấp hồ sơ, sinh 4 mã lỗi `XMLComplete_*`.

**Tech Stack:** PHP 7.4, Laravel 5.5, maatwebsite/excel 3.1 (PhpSpreadsheet), PHPUnit 6, SQLite in-memory cho test.

**Spec:** `docs/superpowers/specs/2026-10-13-xml3176-vat-pham-kem-dvkt-design.md`

## Global Constraints

- PHP 7.4 / Laravel 5.5: `setUp()` **không** có `: void`; được dùng `??` và arrow function `fn`.
- **Cấm `RefreshDatabase` / `DatabaseMigrations`** trong mọi tệp `tests/`. Bảng test dựng bằng `Tests\Support\Xml3176RuleTestSupport::bootXml3176Sqlite([...])` (ghi đè kết nối `mysql` thành SQLite bộ nhớ).
- Luôn chạy test bằng: `DB_HOST=127.0.0.1 vendor/bin/phpunit <đường dẫn>` (`.env` dev trỏ CSDL THẬT 192.168.200.68/qlbv).
- CSDL thật chỉ được **đọc**. Không chạy migrate/seed/lệnh ghi vào 192.168.200.68 trong plan này.
- Mã lỗi lưu thực tế có tiền tố `XMLComplete_`: `XMLComplete_VTYT_KHONG_CO_DVKT_ME`, `XMLComplete_THUOC_KHONG_CO_DVKT_ME`, `XMLComplete_GOI_VTYT_KHONG_KHOP`, `XMLComplete_THUOC_DVKT_ME_KHONG_KHOP`.
- Hằng: `loai` ∈ {`VTYT`, `THUOC`}; `nguon` ∈ {`HOC`, `TAY`}; ghi chú tự tắt = `Học lại: dưới ngưỡng`.
- Ngưỡng mặc định `xml3176.vat_pham_kem_dvkt.nguong = 0.8`, `toi_thieu = 5`.
- Chuẩn hoá mã DVKT: trim + bỏ hậu tố `_GT` (không phân biệt hoa thường). Mã VTYT "nhóm chung" = ≤ 3 đoạn tách bởi dấu chấm → khoá khớp gồm mã + tên chuẩn hoá (`TextNormalizer::chuan`, cắt 191 ký tự).
- Độ dài cột có trong khoá duy nhất bị giới hạn để vừa chỉ mục utf8mb4 (≤ 3072 byte): `loai` 10, `ma_vat_pham` 100, `ten_khop` 191, `ma_dvkt` 50. (Spec ghi string(255) — đổi ở đây vì giới hạn chỉ mục.)
- Commit: tiếng Việt không dấu, dạng `feat(xml3176): ...`, kết thúc bằng dòng `Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>`.
- Tên test: `/** @test */` + snake_case tiếng Việt không dấu, theo mẫu `tests/Unit/Xml3176/Checker/Xml3176CompleteLienBangTest.php`.

## File Structure

| Tệp | Trách nhiệm |
|---|---|
| `database/migrations/2026_10_13_090000_create_xml3176_vat_pham_kem_dvkt_table.php` | Tạo bảng tra |
| `app/Models/BHYT/Xml3176VatPhamKemDvkt.php` | Model + hằng |
| `app/Services/Xml3176/Support/VatPhamKemDvktKey.php` | Khoá khớp vật phẩm, chuẩn hoá mã DVKT |
| `app/Services/Xml3176/VatPhamKemDvktLookup.php` | Nạp bảng tra (memo 10 phút) |
| `app/Services/Xml3176/VatPhamKemDvktRule.php` | R1 + R2, thuần, không CSDL |
| `app/Services/Xml3176/VatPhamKemDvktLearner.php` | Học (`hoc`), ghi (`ghi`), nạp Excel (`nap`) |
| `app/Exports/VatPhamKemDvktExport.php` | Xuất Excel (giữ mã dạng chuỗi) |
| `app/Imports/VatPhamKemDvktImport.php` | Đọc Excel có dòng tiêu đề |
| `app/Console/Commands/Xml3176HocVatPhamKemDvkt.php` | Lệnh học |
| `app/Console/Commands/Xml3176NapVatPhamKemDvkt.php` | Lệnh nạp |
| `app/Services/Xml3176CompleteChecker.php` | Thêm `checkVatPhamKemDvkt` |
| `config/xml3176.php` | Thêm `vat_pham_kem_dvkt` |
| `database/seeds/Xml3176ErrorCatalogVatPhamKemDvktSeeder.php` | Đăng ký 4 mã lỗi |
| `tests/Unit/Xml3176/VatPhamKemDvkt/*Test.php` | Test |

---

### Task 0: Mốc test trước khi sửa

**Files:** không sửa tệp nào.

- [ ] **Step 1: Chạy bộ test XML3176, ghi lại mốc**

Run: `DB_HOST=127.0.0.1 vendor/bin/phpunit tests/Unit/Xml3176 2>&1 | tail -5`
Expected: một dòng tổng kết `Tests: N, Assertions: M, ...`. Ghi lại N và số Failures/Errors vào ghi chú làm việc — cuối plan so lại, số lỗi không được tăng.

---

### Task 1: Bảng, model, khoá khớp, config

**Files:**
- Create: `database/migrations/2026_10_13_090000_create_xml3176_vat_pham_kem_dvkt_table.php`
- Create: `app/Models/BHYT/Xml3176VatPhamKemDvkt.php`
- Create: `app/Services/Xml3176/Support/VatPhamKemDvktKey.php`
- Modify: `config/xml3176.php` (thêm khoá cấp cao nhất)
- Test: `tests/Unit/Xml3176/VatPhamKemDvkt/VatPhamKemDvktKeyTest.php`, `tests/Unit/Xml3176/VatPhamKemDvkt/VatPhamKemDvktModelTest.php`

**Interfaces:**
- Produces:
  - `VatPhamKemDvktKey::laMaNhomChung($ma): bool`
  - `VatPhamKemDvktKey::tenKhop(string $loai, $ma, $ten): string`
  - `VatPhamKemDvktKey::khoa(string $loai, $ma, $ten): string` — dạng `"{ma đã trim}|{tenKhop}"`
  - `VatPhamKemDvktKey::chuanHoaMaDvkt($ma): string`
  - Model `App\Models\BHYT\Xml3176VatPhamKemDvkt` với hằng `LOAI_VTYT='VTYT'`, `LOAI_THUOC='THUOC'`, `NGUON_HOC='HOC'`, `NGUON_TAY='TAY'`, `GHI_CHU_TU_TAT='Học lại: dưới ngưỡng'`
  - `config('xml3176.vat_pham_kem_dvkt.nguong')`, `config('xml3176.vat_pham_kem_dvkt.toi_thieu')`

- [ ] **Step 1: Viết test khoá khớp (thất bại)**

`tests/Unit/Xml3176/VatPhamKemDvkt/VatPhamKemDvktKeyTest.php`:

```php
<?php

namespace Tests\Unit\Xml3176\VatPhamKemDvkt;

use App\Services\Xml3176\Support\VatPhamKemDvktKey;
use Tests\TestCase;

class VatPhamKemDvktKeyTest extends TestCase
{
    /** @test */
    public function ma_vtyt_day_du_khoa_theo_ma_bo_ten()
    {
        $this->assertSame('N06.02.020.0549.274.0004|', VatPhamKemDvktKey::khoa('VTYT', ' N06.02.020.0549.274.0004 ', 'Giá đỡ Orsiro'));
    }

    /** @test */
    public function ma_vtyt_nhom_chung_khoa_theo_ma_va_ten_chuan_hoa()
    {
        $a = VatPhamKemDvktKey::khoa('VTYT', 'N04.04.010', 'Ống dẫn lưu  đuôi lợn');
        $b = VatPhamKemDvktKey::khoa('VTYT', 'N04.04.010', ' ỐNG DẪN LƯU ĐUÔI LỢN ');
        $c = VatPhamKemDvktKey::khoa('VTYT', 'N04.04.010', 'Catheter chẩn đoán 4 cực');

        $this->assertSame('N04.04.010|ống dẫn lưu đuôi lợn', $a);
        $this->assertSame($a, $b);
        $this->assertNotSame($a, $c);
    }

    /** @test */
    public function thuoc_luon_khoa_theo_ma()
    {
        $this->assertSame('40.644|', VatPhamKemDvktKey::khoa('THUOC', '40.644', 'Omnipaque'));
        $this->assertSame('', VatPhamKemDvktKey::tenKhop('THUOC', '40.644', 'Omnipaque'));
    }

    /** @test */
    public function ten_khop_cat_191_ky_tu()
    {
        $ten = str_repeat('á', 300);
        $this->assertSame(191, mb_strlen(VatPhamKemDvktKey::tenKhop('VTYT', 'N08.00.150', $ten), 'UTF-8'));
    }

    /** @test */
    public function chuan_hoa_ma_dvkt_bo_hau_to_gt()
    {
        $this->assertSame('12.0448.1187', VatPhamKemDvktKey::chuanHoaMaDvkt(' 12.0448.1187_GT '));
        $this->assertSame('12.0448.1187', VatPhamKemDvktKey::chuanHoaMaDvkt('12.0448.1187_gt'));
        $this->assertSame('12.0448.1187', VatPhamKemDvktKey::chuanHoaMaDvkt('12.0448.1187'));
        $this->assertSame('', VatPhamKemDvktKey::chuanHoaMaDvkt(null));
    }

    /** @test */
    public function nhan_dien_ma_nhom_chung()
    {
        $this->assertTrue(VatPhamKemDvktKey::laMaNhomChung('N08.00.150'));
        $this->assertFalse(VatPhamKemDvktKey::laMaNhomChung('N08.00.150.1234.000.0001'));
        $this->assertFalse(VatPhamKemDvktKey::laMaNhomChung(''));
    }
}
```

- [ ] **Step 2: Chạy, xác nhận thất bại**

Run: `DB_HOST=127.0.0.1 vendor/bin/phpunit tests/Unit/Xml3176/VatPhamKemDvkt/VatPhamKemDvktKeyTest.php`
Expected: FAIL — `Class 'App\Services\Xml3176\Support\VatPhamKemDvktKey' not found`.

- [ ] **Step 3: Viết `VatPhamKemDvktKey`**

`app/Services/Xml3176/Support/VatPhamKemDvktKey.php`:

```php
<?php

namespace App\Services\Xml3176\Support;

/**
 * Khoa khop vat pham (VTYT/thuoc) voi bang tra xml3176_vat_pham_kem_dvkt.
 *
 * VTYT mang ma nhom chung (<= 3 doan, vd N04.04.010) gop nhieu vat pham khac han nhau:
 * do tren CSDL that, N04.04.010 vua la "Ong dan luu duoi lon" vua la "Catheter chan doan
 * 4 cuc". Voi ma nhom chung, khoa phai gom ca ten chuan hoa. Ma day du va ma thuoc thi
 * khoa theo ma.
 */
class VatPhamKemDvktKey
{
    const DO_DAI_TEN_KHOP = 191;

    public static function laMaNhomChung($ma): bool
    {
        $ma = trim((string) $ma);

        return $ma !== '' && count(explode('.', $ma)) <= 3;
    }

    public static function tenKhop(string $loai, $ma, $ten): string
    {
        if ($loai !== 'VTYT' || !self::laMaNhomChung($ma)) {
            return '';
        }

        return mb_substr(TextNormalizer::chuan($ten), 0, self::DO_DAI_TEN_KHOP, 'UTF-8');
    }

    public static function khoa(string $loai, $ma, $ten): string
    {
        return trim((string) $ma) . '|' . self::tenKhop($loai, $ma, $ten);
    }

    /** Bo hau to _GT (DVKT gay te) de 12.0448.1187_GT khop 12.0448.1187. */
    public static function chuanHoaMaDvkt($ma): string
    {
        return preg_replace('/_GT$/i', '', trim((string) $ma));
    }
}
```

- [ ] **Step 4: Chạy test khoá, xác nhận qua**

Run: `DB_HOST=127.0.0.1 vendor/bin/phpunit tests/Unit/Xml3176/VatPhamKemDvkt/VatPhamKemDvktKeyTest.php`
Expected: `OK (6 tests, ...)`

- [ ] **Step 5: Viết test model/migration (thất bại)**

`tests/Unit/Xml3176/VatPhamKemDvkt/VatPhamKemDvktModelTest.php`:

```php
<?php

namespace Tests\Unit\Xml3176\VatPhamKemDvkt;

use App\Models\BHYT\Xml3176VatPhamKemDvkt;
use Tests\Support\Xml3176RuleTestSupport;
use Tests\TestCase;

class VatPhamKemDvktModelTest extends TestCase
{
    use Xml3176RuleTestSupport;

    protected function setUp()
    {
        parent::setUp();
        $this->bootXml3176Sqlite(['2026_10_13_090000_create_xml3176_vat_pham_kem_dvkt_table.php']);
    }

    /** @test */
    public function tao_dong_va_ep_kieu()
    {
        $m = Xml3176VatPhamKemDvkt::create([
            'loai' => Xml3176VatPhamKemDvkt::LOAI_VTYT, 'ma_vat_pham' => 'N08.00.150',
            'ten_vat_pham' => 'Buồng tiêm truyền', 'ten_khop' => 'buồng tiêm truyền',
            'ma_dvkt' => '12.0448.1187', 'ten_dvkt' => 'Đặt buồng tiêm truyền dưới da',
            'nguon' => Xml3176VatPhamKemDvkt::NGUON_HOC, 'so_lan_gan' => '44', 'ty_le_gan' => '0.9362',
        ])->fresh();

        $this->assertTrue($m->hieu_luc);
        $this->assertSame(44, $m->so_lan_gan);
        $this->assertSame(0.9362, $m->ty_le_gan);
    }

    /** @test */
    public function khoa_duy_nhat_chan_trung()
    {
        $d = ['loai' => 'THUOC', 'ma_vat_pham' => '40.644', 'ten_khop' => '', 'ma_dvkt' => '18.0150.0041', 'nguon' => 'HOC'];
        Xml3176VatPhamKemDvkt::create($d);

        $this->expectException(\Illuminate\Database\QueryException::class);
        Xml3176VatPhamKemDvkt::create($d);
    }

    /** @test */
    public function config_co_nguong_mac_dinh()
    {
        $this->assertEquals(0.8, config('xml3176.vat_pham_kem_dvkt.nguong'));
        $this->assertEquals(5, config('xml3176.vat_pham_kem_dvkt.toi_thieu'));
    }
}
```

- [ ] **Step 6: Chạy, xác nhận thất bại**

Run: `DB_HOST=127.0.0.1 vendor/bin/phpunit tests/Unit/Xml3176/VatPhamKemDvkt/VatPhamKemDvktModelTest.php`
Expected: FAIL — không tìm thấy tệp migration / class model.

- [ ] **Step 7: Viết migration**

`database/migrations/2026_10_13_090000_create_xml3176_vat_pham_kem_dvkt_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bang tra vat pham (VTYT/thuoc) thanh toan ngoai gia DVKT -> DVKT me hop le.
 * Hoc tu du lieu da lien ket (lenh xml3176:hoc-vat-pham-kem-dvkt) va bo sung tay qua Excel.
 * Do dai cot trong khoa duy nhat bi gioi han de vua chi muc utf8mb4.
 */
class CreateXml3176VatPhamKemDvktTable extends Migration
{
    public function up()
    {
        Schema::create('xml3176_vat_pham_kem_dvkt', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('loai', 10);
            $table->string('ma_vat_pham', 100);
            $table->string('ten_vat_pham', 1024)->nullable();
            $table->string('ten_khop', 191)->default('');
            $table->string('ma_dvkt', 50);
            $table->string('ten_dvkt', 1024)->nullable();
            $table->string('nguon', 10);
            $table->integer('so_lan_gan')->default(0);
            $table->decimal('ty_le_gan', 5, 4)->nullable();
            $table->boolean('hieu_luc')->default(true);
            $table->string('ghi_chu', 1024)->nullable();
            $table->timestamps();

            $table->unique(['loai', 'ma_vat_pham', 'ten_khop', 'ma_dvkt'], 'xml3176_vpkd_khoa_unique');
            $table->index(['loai', 'hieu_luc'], 'xml3176_vpkd_loai_hieu_luc_index');
        });
    }

    public function down()
    {
        Schema::dropIfExists('xml3176_vat_pham_kem_dvkt');
    }
}
```

- [ ] **Step 8: Viết model**

`app/Models/BHYT/Xml3176VatPhamKemDvkt.php`:

```php
<?php

namespace App\Models\BHYT;

use Illuminate\Database\Eloquent\Model;

class Xml3176VatPhamKemDvkt extends Model
{
    const LOAI_VTYT = 'VTYT';
    const LOAI_THUOC = 'THUOC';
    const NGUON_HOC = 'HOC';
    const NGUON_TAY = 'TAY';
    /** Ghi chu do lenh hoc dat khi tu tat dong; chi dong mang dung ghi chu nay moi duoc tu bat lai. */
    const GHI_CHU_TU_TAT = 'Học lại: dưới ngưỡng';

    protected $table = 'xml3176_vat_pham_kem_dvkt';

    protected $fillable = [
        'loai', 'ma_vat_pham', 'ten_vat_pham', 'ten_khop', 'ma_dvkt', 'ten_dvkt',
        'nguon', 'so_lan_gan', 'ty_le_gan', 'hieu_luc', 'ghi_chu',
    ];

    protected $attributes = [
        'ten_khop' => '',
        'so_lan_gan' => 0,
        'hieu_luc' => true,
    ];

    protected $casts = [
        'so_lan_gan' => 'integer',
        'ty_le_gan' => 'float',
        'hieu_luc' => 'boolean',
    ];
}
```

- [ ] **Step 9: Thêm config**

Trong `config/xml3176.php`, thêm khoá cấp cao nhất (ngay trước `];` cuối tệp):

```php
    /*
     * Quy tac VTYT/thuoc thanh toan ngoai gia phai di kem DVKT me duoc BHYT tra.
     * Bang tra hoc tu dong da lien ket ma_dich_vu; chi giu vat pham co ty le dong BHYT
     * duoc gan >= nguong. Duoi nguong la vat tu/thuoc dung chung (bom kim, NaCl...) - do
     * tren CSDL that, hoc tho khong loc bao nham 9.067 ho so VTYT va 16.265 ho so thuoc.
     * Doi gia tri thi phai php artisan config:cache.
     */
    'vat_pham_kem_dvkt' => [
        'nguong' => 0.8,
        'toi_thieu' => 5, // so dong toi thieu, loai ca nhieu kieu 2/3
    ],
```

- [ ] **Step 10: Chạy cả hai test, xác nhận qua**

Run: `DB_HOST=127.0.0.1 vendor/bin/phpunit tests/Unit/Xml3176/VatPhamKemDvkt`
Expected: `OK (9 tests, ...)`

- [ ] **Step 11: Commit**

```bash
git add database/migrations/2026_10_13_090000_create_xml3176_vat_pham_kem_dvkt_table.php app/Models/BHYT/Xml3176VatPhamKemDvkt.php app/Services/Xml3176/Support/VatPhamKemDvktKey.php config/xml3176.php tests/Unit/Xml3176/VatPhamKemDvkt
git commit -m "feat(xml3176): bang tra vat pham kem DVKT me va khoa khop

Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>"
```

---

### Task 2: Lớp quy tắc thuần `VatPhamKemDvktRule` (R1 + R2)

**Files:**
- Create: `app/Services/Xml3176/VatPhamKemDvktRule.php`
- Test: `tests/Unit/Xml3176/VatPhamKemDvkt/VatPhamKemDvktRuleTest.php`

**Interfaces:**
- Consumes: `VatPhamKemDvktKey::khoa`, `VatPhamKemDvktKey::chuanHoaMaDvkt` (Task 1).
- Produces:
  - `new VatPhamKemDvktRule(array $bang)` — `$bang = ['VTYT' => [khoa => [ma_dvkt_chuan => ten_dvkt]], 'THUOC' => [...]]`
  - `kiem($xml3, $xml2): array` — mỗi phần tử `['key' => 'VTYT_KHONG_CO_DVKT_ME'|'THUOC_KHONG_CO_DVKT_ME'|'GOI_VTYT_KHONG_KHOP'|'THUOC_DVKT_ME_KHONG_KHOP', 'ten' => string, 'mo_ta' => string]`
  - Dòng vào là đối tượng (model hoặc `stdClass`). XML3 đọc: `ma_dich_vu, ma_vat_tu, ten_vat_tu, ten_dich_vu, goi_vtyt, so_luong, t_bhtt, ngay_yl`. XML2 đọc: `ma_thuoc, ten_thuoc, ma_dich_vu, t_bhtt, ngay_yl`.

- [ ] **Step 1: Viết test (thất bại)**

`tests/Unit/Xml3176/VatPhamKemDvkt/VatPhamKemDvktRuleTest.php`:

```php
<?php

namespace Tests\Unit\Xml3176\VatPhamKemDvkt;

use App\Services\Xml3176\VatPhamKemDvktRule;
use Tests\TestCase;

class VatPhamKemDvktRuleTest extends TestCase
{
    private function bang(): array
    {
        return [
            'VTYT' => [
                'N08.00.150|buồng tiêm truyền cấy dưới da' => ['12.0448.1187' => 'Đặt buồng tiêm truyền dưới da'],
                'N04.04.010|ống dẫn lưu đuôi lợn' => ['02.0086.0106' => 'Dẫn lưu'],
            ],
            'THUOC' => [
                '40.644|' => ['18.0150.0041' => 'CLVT có cản quang', '18.0156.0041' => 'CLVT bụng có cản quang'],
            ],
        ];
    }

    private function dv($ma, $bhtt, $sl = 1, $ten = 'DVKT')
    {
        return (object) ['ma_dich_vu' => $ma, 'ma_vat_tu' => '', 'ten_dich_vu' => $ten, 't_bhtt' => $bhtt, 'so_luong' => $sl, 'ngay_yl' => '202610050800'];
    }

    private function vt($ma, $ten, $bhtt, $maDv = null, $goi = null)
    {
        return (object) ['ma_dich_vu' => $maDv, 'ma_vat_tu' => $ma, 'ten_vat_tu' => $ten, 'goi_vtyt' => $goi, 't_bhtt' => $bhtt, 'so_luong' => 1, 'ngay_yl' => '202610050900'];
    }

    private function th($ma, $ten, $bhtt, $maDv = null)
    {
        return (object) ['ma_thuoc' => $ma, 'ten_thuoc' => $ten, 'ma_dich_vu' => $maDv, 't_bhtt' => $bhtt, 'ngay_yl' => '202610071000'];
    }

    private function kiem(array $xml3, array $xml2 = []): array
    {
        return (new VatPhamKemDvktRule($this->bang()))->kiem($xml3, $xml2);
    }

    private function keys(array $loi): array
    {
        return array_column($loi, 'key');
    }

    /** @test */
    public function r1_vtyt_khong_co_dvkt_me()
    {
        $loi = $this->kiem([$this->vt('N08.00.150', 'Buồng tiêm truyền cấy dưới da', 4188000)]);

        $this->assertSame(['VTYT_KHONG_CO_DVKT_ME'], $this->keys($loi));
        $this->assertContains('không có DVKT mẹ trong hồ sơ', $loi[0]['mo_ta']);
        $this->assertContains('4.188.000đ', $loi[0]['mo_ta']);
        $this->assertContains('05/10/2026', $loi[0]['mo_ta']);
        $this->assertContains('12.0448.1187 Đặt buồng tiêm truyền dưới da', $loi[0]['mo_ta']);
    }

    /** @test */
    public function r1_dvkt_me_co_nhung_quy_khong_tra()
    {
        $loi = $this->kiem([
            $this->dv('12.0448.1187', 0, 1, 'Đặt buồng tiêm truyền dưới da'),
            $this->vt('N08.00.150', 'Buồng tiêm truyền cấy dưới da', 4188000),
        ]);

        $this->assertSame(['VTYT_KHONG_CO_DVKT_ME'], $this->keys($loi));
        $this->assertContains('có trong hồ sơ nhưng quỹ BHYT không thanh toán', $loi[0]['mo_ta']);
    }

    /** @test */
    public function r1_dvkt_me_duoc_bhyt_tra_thi_khong_loi()
    {
        $this->assertSame([], $this->kiem([
            $this->dv('12.0448.1187', 1070800),
            $this->vt('N08.00.150', 'Buồng tiêm truyền cấy dưới da', 4188000),
        ]));
    }

    /** @test */
    public function r1_dvkt_me_hau_to_gt_van_khop()
    {
        $this->assertSame([], $this->kiem([
            $this->dv('12.0448.1187_GT', 1070800),
            $this->vt('N08.00.150', 'Buồng tiêm truyền cấy dưới da', 4188000),
        ]));
    }

    /** @test */
    public function r1_vat_pham_khong_bhyt_tra_hoac_khong_trong_bang_thi_im_lang()
    {
        $this->assertSame([], $this->kiem([$this->vt('N08.00.150', 'Buồng tiêm truyền cấy dưới da', 0)]));
        $this->assertSame([], $this->kiem([$this->vt('N03.01.070', 'Bơm tiêm 10ml', 5000)]));
    }

    /** @test */
    public function r1_ma_nhom_chung_khac_ten_khong_khop()
    {
        $this->assertSame([], $this->kiem([$this->vt('N04.04.010', 'Catheter chẩn đoán 4 cực', 692790)]));
        $this->assertSame(['VTYT_KHONG_CO_DVKT_ME'], $this->keys($this->kiem([$this->vt('N04.04.010', 'Ống dẫn lưu đuôi lợn', 692790)])));
    }

    /** @test */
    public function r1_gop_mot_loi_moi_vat_pham()
    {
        $loi = $this->kiem([
            $this->vt('N08.00.150', 'Buồng tiêm truyền cấy dưới da', 1000000),
            $this->vt('N08.00.150', 'Buồng tiêm truyền cấy dưới da', 2000000),
        ]);

        $this->assertCount(1, $loi);
        $this->assertContains('3.000.000đ', $loi[0]['mo_ta']);
    }

    /** @test */
    public function r1_bo_qua_dong_da_co_lien_ket()
    {
        // Dong co ma_dich_vu thuoc R2; R1 khong bao trung.
        $loi = $this->kiem([$this->vt('N08.00.150', 'Buồng tiêm truyền cấy dưới da', 4188000, '12.0448.1187', 'G1')]);

        $this->assertSame(['GOI_VTYT_KHONG_KHOP'], $this->keys($loi));
    }

    /** @test */
    public function r1_thuoc_can_quang_khong_co_clvt_co_can_quang()
    {
        $loi = $this->kiem([$this->dv('18.0149.0040', 502400, 1, 'CLVT sọ não không tiêm')], [$this->th('40.644', 'Omnipaque', 363632)]);

        $this->assertSame(['THUOC_KHONG_CO_DVKT_ME'], $this->keys($loi));
        $this->assertContains('Omnipaque (40.644)', $loi[0]['mo_ta']);
    }

    /** @test */
    public function r1_thuoc_co_dvkt_me_bhyt_thi_khong_loi()
    {
        $this->assertSame([], $this->kiem([$this->dv('18.0156.0041', 653700)], [$this->th('40.644', 'Omnipaque', 363632)]));
    }

    /** @test */
    public function r1_danh_sach_can_mot_trong_cat_5_ma()
    {
        $me = [];
        for ($i = 1; $i <= 7; $i++) {
            $me['01.000' . $i . '.0001'] = 'DV ' . $i;
        }
        $rule = new VatPhamKemDvktRule(['VTYT' => ['N08.00.150|buồng' => $me], 'THUOC' => []]);

        $loi = $rule->kiem([$this->vt('N08.00.150', 'Buồng', 100)], []);

        $this->assertContains('và 2 mã khác', $loi[0]['mo_ta']);
        $this->assertNotContains('01.0006.0001', $loi[0]['mo_ta']);
    }

    /** @test */
    public function r2_goi_vtyt_dvkt_me_thieu()
    {
        $loi = $this->kiem([$this->vt('N06.02.020.0549.274.0004', 'Giá đỡ', 29000000, '18.0659.0054', 'G1')]);

        $this->assertSame(['GOI_VTYT_KHONG_KHOP'], $this->keys($loi));
        $this->assertContains('DVKT mẹ 18.0659.0054 không có trong hồ sơ', $loi[0]['mo_ta']);
    }

    /** @test */
    public function r2_goi_vtyt_dvkt_me_quy_khong_tra()
    {
        $loi = $this->kiem([
            $this->dv('18.0659.0054', 0),
            $this->vt('N06.02.020.0549.274.0004', 'Giá đỡ', 29000000, '18.0659.0054', 'G1'),
        ]);

        $this->assertSame(['GOI_VTYT_KHONG_KHOP'], $this->keys($loi));
        $this->assertContains('quỹ BHYT không thanh toán', $loi[0]['mo_ta']);
    }

    /** @test */
    public function r2_so_goi_vuot_so_lan_dvkt()
    {
        $xml3 = [
            $this->dv('18.0659.0054', 6000000, 1),
            $this->vt('N06.02.020.0549.274.0004', 'Giá đỡ', 29000000, '18.0659.0054', 'G1'),
            $this->vt('N07.01.240', 'Bóng nong', 3000000, '18.0659.0054', 'G2'),
        ];
        $loi = $this->kiem($xml3);

        $this->assertSame(['GOI_VTYT_KHONG_KHOP'], $this->keys($loi));
        $this->assertContains('2 gói VTYT', $loi[0]['mo_ta']);

        $xml3[0] = $this->dv('18.0659.0054', 12000000, 2);
        $this->assertSame([], $this->kiem($xml3));
    }

    /** @test */
    public function r2_thuoc_gan_dvkt_me_khong_tra()
    {
        $loi = $this->kiem([$this->dv('18.0156.0041', 0)], [$this->th('40.644', 'Omnipaque', 363632, '18.0156.0041')]);

        $this->assertSame(['THUOC_DVKT_ME_KHONG_KHOP'], $this->keys($loi));
    }

    /** @test */
    public function r2_thuoc_gan_dvkt_me_bhyt_tra_thi_khong_loi()
    {
        $this->assertSame([], $this->kiem([$this->dv('18.0156.0041', 653700)], [$this->th('40.644', 'Omnipaque', 363632, '18.0156.0041')]));
    }

    /** @test */
    public function bang_rong_thi_r1_im_lang_r2_van_chay()
    {
        $rule = new VatPhamKemDvktRule([]);

        $this->assertSame([], $rule->kiem([$this->vt('N08.00.150', 'Buồng tiêm truyền cấy dưới da', 4188000)], []));
        $this->assertSame(['GOI_VTYT_KHONG_KHOP'], $this->keys($rule->kiem([$this->vt('N08.00.150', 'Buồng', 1, '12.0448.1187', 'G1')], [])));
    }
}
```

- [ ] **Step 2: Chạy, xác nhận thất bại**

Run: `DB_HOST=127.0.0.1 vendor/bin/phpunit tests/Unit/Xml3176/VatPhamKemDvkt/VatPhamKemDvktRuleTest.php`
Expected: FAIL — `Class 'App\Services\Xml3176\VatPhamKemDvktRule' not found`.

- [ ] **Step 3: Viết `VatPhamKemDvktRule`**

`app/Services/Xml3176/VatPhamKemDvktRule.php`:

```php
<?php

namespace App\Services\Xml3176;

use App\Services\Xml3176\Support\VatPhamKemDvktKey;

/**
 * VTYT/thuoc thanh toan ngoai gia DVKT ("Chua bao gom ..." trong QD gia) chi duoc quy tra
 * khi DVKT me duoc quy tra. Lop thuan: nhan dong XML3/XML2 cua MOT ho so, khong cham CSDL.
 *
 * R1 - dong KHONG lien ket (ma_dich_vu rong): vat pham co trong bang tra ma ho so khong co
 *      DVKT me nao duoc BHYT tra. Do tren CSDL that: 97% dong VTYT khong co ma_dich_vu,
 *      loi that (quen ke DVKT, BN tu tra DVKT) nam o day.
 * R2 - dong CO lien ket: DVKT me phai co, duoc BHYT tra, va so goi_vtyt khac nhau khong vuot
 *      so lan DVKT me duoc tra. Ngay 13/10/2026 do duoc 0 ca - day la chot chan khi HIS doi.
 */
class VatPhamKemDvktRule
{
    const TOI_DA_MA_ME = 5;
    const TOI_DA_TEN = 3;

    const TEN = [
        'VTYT_KHONG_CO_DVKT_ME' => 'VTYT thanh toán ngoài giá không có DVKT mẹ được BHYT thanh toán',
        'THUOC_KHONG_CO_DVKT_ME' => 'Thuốc thanh toán ngoài giá không có DVKT mẹ được BHYT thanh toán',
        'GOI_VTYT_KHONG_KHOP' => 'Gói VTYT không khớp DVKT mẹ được BHYT thanh toán',
        'THUOC_DVKT_ME_KHONG_KHOP' => 'Thuốc gắn DVKT mẹ không được BHYT thanh toán',
    ];

    /** @var array ['VTYT' => [khoa => [ma_dvkt => ten_dvkt]], 'THUOC' => [...]] */
    private $bang;

    public function __construct(array $bang)
    {
        $this->bang = $bang;
    }

    public function kiem($xml3, $xml2): array
    {
        $dvkt = $this->gomDvkt($xml3);

        $vtyt = [];
        foreach ($xml3 as $r) {
            if (self::rong($r->ma_vat_tu ?? null)) {
                continue;
            }
            $vtyt[] = self::vatPham($r->ma_vat_tu, $r->ten_vat_tu ?? '', $r, $r->goi_vtyt ?? '');
        }

        $thuoc = [];
        foreach ($xml2 as $r) {
            if (self::rong($r->ma_thuoc ?? null)) {
                continue;
            }
            $thuoc[] = self::vatPham($r->ma_thuoc, $r->ten_thuoc ?? '', $r, '');
        }

        return array_merge(
            $this->r1($vtyt, 'VTYT', $dvkt),
            $this->r1($thuoc, 'THUOC', $dvkt),
            $this->r2($vtyt, 'VTYT', $dvkt),
            $this->r2($thuoc, 'THUOC', $dvkt)
        );
    }

    private static function vatPham($ma, $ten, $r, $goi)
    {
        return (object) [
            'ma' => trim((string) $ma),
            'ten' => (string) $ten,
            'ma_dich_vu' => $r->ma_dich_vu ?? null,
            'goi' => trim((string) $goi),
            't_bhtt' => (float) ($r->t_bhtt ?? 0),
            'ngay_yl' => (string) ($r->ngay_yl ?? ''),
        ];
    }

    /** DVKT cua ho so (dong XML3 khong co ma_vat_tu) gom theo ma chuan hoa. */
    private function gomDvkt($xml3): array
    {
        $dv = [];
        foreach ($xml3 as $r) {
            if (!self::rong($r->ma_vat_tu ?? null)) {
                continue;
            }
            $k = VatPhamKemDvktKey::chuanHoaMaDvkt($r->ma_dich_vu ?? null);
            if ($k === '') {
                continue;
            }
            if (!isset($dv[$k])) {
                $dv[$k] = ['bhtt' => 0.0, 'sl_bhyt' => 0.0, 'ten' => (string) ($r->ten_dich_vu ?? '')];
            }
            $bhtt = (float) ($r->t_bhtt ?? 0);
            $dv[$k]['bhtt'] += $bhtt;
            if ($bhtt > 0) {
                $dv[$k]['sl_bhyt'] += (float) ($r->so_luong ?? 0);
            }
        }

        return $dv;
    }

    private function r1(array $dong, string $loai, array $dvkt): array
    {
        $bangLoai = $this->bang[$loai] ?? [];
        if (empty($bangLoai)) {
            return [];
        }

        $nhom = [];
        foreach ($dong as $d) {
            if ($d->t_bhtt <= 0 || !self::rong($d->ma_dich_vu)) {
                continue;
            }
            $khoa = VatPhamKemDvktKey::khoa($loai, $d->ma, $d->ten);
            if (!isset($bangLoai[$khoa])) {
                continue;
            }
            if (!isset($nhom[$khoa])) {
                $nhom[$khoa] = ['ma' => $d->ma, 'ten' => $d->ten, 'bhtt' => 0.0, 'ngay' => $d->ngay_yl];
            }
            $nhom[$khoa]['bhtt'] += $d->t_bhtt;
            if ($d->ngay_yl !== '' && ($nhom[$khoa]['ngay'] === '' || strcmp($d->ngay_yl, $nhom[$khoa]['ngay']) < 0)) {
                $nhom[$khoa]['ngay'] = $d->ngay_yl;
            }
        }

        $key = $loai === 'VTYT' ? 'VTYT_KHONG_CO_DVKT_ME' : 'THUOC_KHONG_CO_DVKT_ME';
        $ra = [];
        foreach ($nhom as $khoa => $g) {
            $me = $bangLoai[$khoa];
            $coTrongHs = [];
            $duocTra = false;
            foreach ($me as $maMe => $_) {
                if (!isset($dvkt[$maMe])) {
                    continue;
                }
                if ($dvkt[$maMe]['bhtt'] > 0) {
                    $duocTra = true;
                    break;
                }
                $coTrongHs[] = $maMe;
            }
            if ($duocTra) {
                continue;
            }

            $dau = sprintf('%s (%s) BHYT trả %s, YL %s: ', $g['ten'], $g['ma'], self::tien($g['bhtt']), self::ngay($g['ngay']));
            if (!empty($coTrongHs)) {
                $maMe = $coTrongHs[0];
                $moTa = $dau . sprintf('DVKT mẹ %s %s có trong hồ sơ nhưng quỹ BHYT không thanh toán.', $maMe, $dvkt[$maMe]['ten']);
            } else {
                $moTa = $dau . 'không có DVKT mẹ trong hồ sơ. Cần một trong: ' . self::dsMe($me) . '.';
            }

            $ra[] = ['key' => $key, 'ten' => self::TEN[$key], 'mo_ta' => $moTa];
        }

        return $ra;
    }

    private function r2(array $dong, string $loai, array $dvkt): array
    {
        $nhom = [];
        foreach ($dong as $d) {
            if ($d->t_bhtt <= 0 || self::rong($d->ma_dich_vu)) {
                continue;
            }
            $k = VatPhamKemDvktKey::chuanHoaMaDvkt($d->ma_dich_vu);
            if (!isset($nhom[$k])) {
                $nhom[$k] = ['ten' => [], 'goi' => []];
            }
            $nhom[$k]['ten'][$d->ten] = true;
            if ($d->goi !== '') {
                $nhom[$k]['goi'][$d->goi] = true;
            }
        }

        $key = $loai === 'VTYT' ? 'GOI_VTYT_KHONG_KHOP' : 'THUOC_DVKT_ME_KHONG_KHOP';
        $ra = [];
        foreach ($nhom as $k => $g) {
            if (!isset($dvkt[$k])) {
                $lyDo = sprintf('DVKT mẹ %s không có trong hồ sơ.', $k);
            } elseif ($dvkt[$k]['bhtt'] <= 0) {
                $lyDo = sprintf('DVKT mẹ %s %s có trong hồ sơ nhưng quỹ BHYT không thanh toán.', $k, $dvkt[$k]['ten']);
            } elseif ($loai === 'VTYT' && count($g['goi']) > $dvkt[$k]['sl_bhyt']) {
                $lyDo = sprintf(
                    '%d gói VTYT (%s) nhưng DVKT mẹ %s chỉ được BHYT thanh toán %s lần.',
                    count($g['goi']), implode(', ', array_keys($g['goi'])), $k, self::so($dvkt[$k]['sl_bhyt'])
                );
            } else {
                continue;
            }

            $ra[] = ['key' => $key, 'ten' => self::TEN[$key], 'mo_ta' => self::dsTen(array_keys($g['ten'])) . ' gắn DVKT ' . $k . ': ' . $lyDo];
        }

        return $ra;
    }

    private static function rong($s): bool
    {
        return $s === null || trim((string) $s) === '';
    }

    private static function tien(float $x): string
    {
        return number_format(round($x), 0, ',', '.') . 'đ';
    }

    private static function so(float $x): string
    {
        return rtrim(rtrim(number_format($x, 2, ',', ''), '0'), ',');
    }

    private static function ngay(string $s): string
    {
        $d = substr($s, 0, 8);
        if (strlen($d) === 8 && ctype_digit($d)) {
            return substr($d, 6, 2) . '/' . substr($d, 4, 2) . '/' . substr($d, 0, 4);
        }

        return $s;
    }

    private static function dsMe(array $me): string
    {
        $phan = [];
        foreach (array_slice($me, 0, self::TOI_DA_MA_ME, true) as $ma => $ten) {
            $phan[] = trim($ma . ' ' . $ten);
        }
        $con = count($me) - self::TOI_DA_MA_ME;

        return implode('; ', $phan) . ($con > 0 ? ' và ' . $con . ' mã khác' : '');
    }

    private static function dsTen(array $ten): string
    {
        $con = count($ten) - self::TOI_DA_TEN;

        return implode(', ', array_slice($ten, 0, self::TOI_DA_TEN)) . ($con > 0 ? ' và ' . $con . ' vật phẩm khác' : '');
    }
}
```

- [ ] **Step 4: Chạy, xác nhận qua**

Run: `DB_HOST=127.0.0.1 vendor/bin/phpunit tests/Unit/Xml3176/VatPhamKemDvkt/VatPhamKemDvktRuleTest.php`
Expected: `OK (17 tests, ...)`

- [ ] **Step 5: Commit**

```bash
git add app/Services/Xml3176/VatPhamKemDvktRule.php tests/Unit/Xml3176/VatPhamKemDvkt/VatPhamKemDvktRuleTest.php
git commit -m "feat(xml3176): quy tac VTYT/thuoc ngoai gia phai co DVKT me BHYT tra

R1 kiem dong khong lien ket theo bang tra, R2 chot chan dong co goi_vtyt/ma_dich_vu.

Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>"
```

---

### Task 3: Nạp bảng tra, nối vào `Xml3176CompleteChecker`, seeder mã lỗi

**Files:**
- Create: `app/Services/Xml3176/VatPhamKemDvktLookup.php`
- Modify: `app/Services/Xml3176CompleteChecker.php` (thêm `use`, một dòng `merge` trong `checkErrors()` sau `checkBedDaysBelowTT39`, một method private trước dòng `// Thêm các phương thức kiểm tra khác ở đây`)
- Create: `database/seeds/Xml3176ErrorCatalogVatPhamKemDvktSeeder.php`
- Test: `tests/Unit/Xml3176/VatPhamKemDvkt/VatPhamKemDvktCheckerTest.php`, `tests/Unit/Xml3176/VatPhamKemDvkt/VatPhamKemDvktSeederTest.php`

**Interfaces:**
- Consumes: `VatPhamKemDvktRule` (Task 2), model + `VatPhamKemDvktKey::chuanHoaMaDvkt` (Task 1).
- Produces:
  - `VatPhamKemDvktLookup::bang(): array` (memo 600 giây; bảng chưa migrate → bảng rỗng)
  - `VatPhamKemDvktLookup::tuDong($dong): array` — `$dong` là iterable model hoặc mảng có khoá `loai, ma_vat_pham, ten_khop, ma_dvkt, ten_dvkt`, tuỳ chọn `hieu_luc`
  - `VatPhamKemDvktLookup::xoaBoNho(): void`
  - `Xml3176CompleteChecker::checkVatPhamKemDvkt($ma_lk): Collection` (private)

- [ ] **Step 1: Viết test checker (thất bại)**

`tests/Unit/Xml3176/VatPhamKemDvkt/VatPhamKemDvktCheckerTest.php`:

```php
<?php

namespace Tests\Unit\Xml3176\VatPhamKemDvkt;

use App\Models\BHYT\Xml3176VatPhamKemDvkt;
use App\Services\Xml3176\VatPhamKemDvktLookup;
use App\Services\Xml3176CompleteChecker;
use Illuminate\Support\Facades\DB;
use Tests\Support\FakeXml3176ErrorService;
use Tests\Support\Xml3176RuleTestSupport;
use Tests\TestCase;

class VatPhamKemDvktCheckerTest extends TestCase
{
    use Xml3176RuleTestSupport;

    protected function setUp()
    {
        parent::setUp();
        $this->bootXml3176Sqlite([
            '2026_01_09_152826_create_xml3176_xml2s_table.php',
            '2026_01_09_152832_create_xml3176_xml3s_table.php',
            '2026_10_13_090000_create_xml3176_vat_pham_kem_dvkt_table.php',
        ]);
        VatPhamKemDvktLookup::xoaBoNho();

        Xml3176VatPhamKemDvkt::create([
            'loai' => 'VTYT', 'ma_vat_pham' => 'N08.00.150', 'ten_khop' => 'buồng tiêm truyền cấy dưới da',
            'ma_dvkt' => '12.0448.1187', 'ten_dvkt' => 'Đặt buồng tiêm truyền dưới da', 'nguon' => 'HOC',
        ]);
        Xml3176VatPhamKemDvkt::create([
            'loai' => 'THUOC', 'ma_vat_pham' => '40.644', 'ten_khop' => '',
            'ma_dvkt' => '18.0156.0041', 'ten_dvkt' => 'CLVT có cản quang', 'nguon' => 'HOC', 'hieu_luc' => false,
        ]);
    }

    protected function tearDown()
    {
        VatPhamKemDvktLookup::xoaBoNho();
        parent::tearDown();
    }

    private function xml3(string $maLk, int $stt, array $f): void
    {
        DB::table('xml3176_xml3s')->insert(array_merge(['ma_lk' => $maLk, 'stt' => $stt], $f));
    }

    private function codes(string $maLk): array
    {
        $checker = new Xml3176CompleteChecker(new FakeXml3176ErrorService());

        return $this->errorCodes($this->invokePrivate($checker, 'checkVatPhamKemDvkt', $maLk));
    }

    /** @test */
    public function bat_buong_tiem_truyen_khong_co_dvkt_me()
    {
        $this->xml3('000007339564', 1, ['ma_dich_vu' => 'K33.NO1', 'ma_vat_tu' => '', 'ten_dich_vu' => 'Giường', 't_bhtt' => 340200, 'so_luong' => 1, 'ngay_yl' => '202610050000']);
        $this->xml3('000007339564', 2, ['ma_dich_vu' => '', 'ma_vat_tu' => 'N08.00.150', 'ten_vat_tu' => 'Buồng tiêm truyền cấy dưới da', 't_bhtt' => 4188000, 'so_luong' => 1, 'ngay_yl' => '202610050900']);

        $this->assertSame(['XMLComplete_VTYT_KHONG_CO_DVKT_ME'], $this->codes('000007339564'));
    }

    /** @test */
    public function co_dvkt_me_gay_te_bhyt_tra_thi_khong_loi()
    {
        $this->xml3('000007342941', 1, ['ma_dich_vu' => '12.0448.1187_GT', 'ma_vat_tu' => '', 't_bhtt' => 1070800, 'so_luong' => 1, 'ngay_yl' => '202610020800']);
        $this->xml3('000007342941', 2, ['ma_dich_vu' => null, 'ma_vat_tu' => 'N08.00.150', 'ten_vat_tu' => 'Buồng tiêm truyền cấy dưới da', 't_bhtt' => 4188000, 'so_luong' => 1, 'ngay_yl' => '202610020900']);

        $this->assertSame([], $this->codes('000007342941'));
    }

    /** @test */
    public function dong_bang_tra_bi_tat_thi_im_lang()
    {
        DB::table('xml3176_xml2s')->insert(['ma_lk' => '000007403605', 'stt' => 1, 'ma_thuoc' => '40.644', 'ten_thuoc' => 'Omnipaque', 't_bhtt' => 363632, 'ngay_yl' => '202610070900']);

        $this->assertSame([], $this->codes('000007403605'));
    }

    /** @test */
    public function bang_tra_chua_migrate_thi_r1_im_lang()
    {
        DB::statement('DROP TABLE xml3176_vat_pham_kem_dvkt');
        VatPhamKemDvktLookup::xoaBoNho();
        $this->xml3('X', 1, ['ma_dich_vu' => '', 'ma_vat_tu' => 'N08.00.150', 'ten_vat_tu' => 'Buồng tiêm truyền cấy dưới da', 't_bhtt' => 4188000, 'so_luong' => 1, 'ngay_yl' => '202610050900']);

        $this->assertSame([], $this->codes('X'));
    }

    /** @test */
    public function checkErrors_goi_quy_tac_moi()
    {
        $nguon = file_get_contents(app_path('Services/Xml3176CompleteChecker.php'));

        $this->assertContains('$this->checkVatPhamKemDvkt($ma_lk)', $nguon);
    }
}
```

- [ ] **Step 2: Chạy, xác nhận thất bại**

Run: `DB_HOST=127.0.0.1 vendor/bin/phpunit tests/Unit/Xml3176/VatPhamKemDvkt/VatPhamKemDvktCheckerTest.php`
Expected: FAIL — `Class 'App\Services\Xml3176\VatPhamKemDvktLookup' not found`.

- [ ] **Step 3: Viết `VatPhamKemDvktLookup`**

`app/Services/Xml3176/VatPhamKemDvktLookup.php`:

```php
<?php

namespace App\Services\Xml3176;

use App\Models\BHYT\Xml3176VatPhamKemDvkt;
use App\Services\Xml3176\Support\VatPhamKemDvktKey;
use Illuminate\Database\QueryException;

/**
 * Nap bang tra vat pham -> DVKT me mot lan moi tien trinh, lam moi sau TTL_GIAY de tien
 * trinh hang doi chay lau van thay thay doi tu lenh hoc/nap ma khong can khoi dong lai.
 */
class VatPhamKemDvktLookup
{
    const TTL_GIAY = 600;

    private static $bang = null;
    private static $napLuc = 0;

    public static function bang(): array
    {
        if (self::$bang === null || time() - self::$napLuc > self::TTL_GIAY) {
            try {
                $dong = Xml3176VatPhamKemDvkt::where('hieu_luc', true)
                    ->get(['loai', 'ma_vat_pham', 'ten_khop', 'ma_dvkt', 'ten_dvkt']);
            } catch (QueryException $e) {
                // Bang chua migrate (trien khai chua xong): R1 im lang, R2 van chay.
                $dong = [];
            }
            self::$bang = self::tuDong($dong);
            self::$napLuc = time();
        }

        return self::$bang;
    }

    public static function tuDong($dong): array
    {
        $bang = ['VTYT' => [], 'THUOC' => []];
        foreach ($dong as $d) {
            $d = is_array($d) ? (object) $d : $d;
            if (isset($d->hieu_luc) && !$d->hieu_luc) {
                continue;
            }
            $khoa = trim((string) $d->ma_vat_pham) . '|' . (string) $d->ten_khop;
            $bang[$d->loai][$khoa][VatPhamKemDvktKey::chuanHoaMaDvkt($d->ma_dvkt)] = (string) ($d->ten_dvkt ?? '');
        }

        return $bang;
    }

    public static function xoaBoNho(): void
    {
        self::$bang = null;
        self::$napLuc = 0;
    }
}
```

- [ ] **Step 4: Nối vào `Xml3176CompleteChecker`**

Trong `app/Services/Xml3176CompleteChecker.php`:

Thêm vào khối `use` (sau `use App\Services\Mcct\NguongMienCungChiTra;`):

```php
use App\Services\Xml3176\VatPhamKemDvktLookup;
use App\Services\Xml3176\VatPhamKemDvktRule;
```

Trong `checkErrors()`, ngay sau dòng `$errors = $errors->merge($this->checkBedDaysBelowTT39($data));` thêm:

```php
            $errors = $errors->merge($this->checkVatPhamKemDvkt($ma_lk));
```

Ngay trước dòng `    // Thêm các phương thức kiểm tra khác ở đây` thêm:

```php
    /**
     * VTYT/thuoc thanh toan ngoai gia DVKT phai di kem DVKT me duoc quy BHYT tra.
     *
     * Chuyen de chu dong BHXH 10/2026: ho so co VTYT "chua bao gom trong gia DVKT" de nghi
     * quy tra trong khi DVKT me quen ke hoac BN tu tra. Logic o VatPhamKemDvktRule; bang tra
     * hoc boi lenh xml3176:hoc-vat-pham-kem-dvkt.
     */
    private function checkVatPhamKemDvkt($ma_lk): Collection
    {
        $xml3 = Xml3176Xml3::where('ma_lk', $ma_lk)
            ->get(['ma_dich_vu', 'ma_vat_tu', 'ten_vat_tu', 'ten_dich_vu', 'goi_vtyt', 'so_luong', 't_bhtt', 'ngay_yl']);
        $xml2 = Xml3176Xml2::where('ma_lk', $ma_lk)
            ->get(['ma_thuoc', 'ten_thuoc', 'ma_dich_vu', 't_bhtt', 'ngay_yl']);

        $errors = collect();
        foreach ((new VatPhamKemDvktRule(VatPhamKemDvktLookup::bang()))->kiem($xml3, $xml2) as $loi) {
            $code = $this->generateErrorCode($loi['key']);
            $errors->push((object) [
                'error_code' => $code,
                'error_name' => $loi['ten'],
                'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($code),
                'description' => $loi['mo_ta'],
            ]);
        }

        return $errors;
    }

```

- [ ] **Step 5: Chạy test checker, xác nhận qua**

Run: `DB_HOST=127.0.0.1 vendor/bin/phpunit tests/Unit/Xml3176/VatPhamKemDvkt/VatPhamKemDvktCheckerTest.php`
Expected: `OK (5 tests, ...)`

- [ ] **Step 6: Viết test seeder (thất bại)**

`tests/Unit/Xml3176/VatPhamKemDvkt/VatPhamKemDvktSeederTest.php`:

```php
<?php

namespace Tests\Unit\Xml3176\VatPhamKemDvkt;

use App\Services\Xml3176\VatPhamKemDvktRule;
use Tests\TestCase;

class VatPhamKemDvktSeederTest extends TestCase
{
    /** @test */
    public function seeder_khai_du_4_ma_loi_dung_ten_cua_quy_tac()
    {
        $nguon = file_get_contents(database_path('seeds/Xml3176ErrorCatalogVatPhamKemDvktSeeder.php'));

        foreach (VatPhamKemDvktRule::TEN as $key => $ten) {
            $this->assertContains("'XMLComplete_{$key}'", $nguon);
            $this->assertContains($ten, $nguon);
        }
        $this->assertContains("'XMLComplete'", $nguon);
        $this->assertContains("'critical_error' => true", $nguon);
    }
}
```

- [ ] **Step 7: Chạy, xác nhận thất bại**

Run: `DB_HOST=127.0.0.1 vendor/bin/phpunit tests/Unit/Xml3176/VatPhamKemDvkt/VatPhamKemDvktSeederTest.php`
Expected: FAIL — `file_get_contents(...): failed to open stream`.

- [ ] **Step 8: Viết seeder**

`database/seeds/Xml3176ErrorCatalogVatPhamKemDvktSeeder.php`:

```php
<?php

use Illuminate\Database\Seeder;
use App\Models\BHYT\Xml3176ErrorCatalog;

/**
 * Nap san 4 ma loi VTYT/thuoc thanh toan ngoai gia DVKT phai di kem DVKT me duoc BHYT tra
 * (ghi chu "Chua bao gom ..." trong QD phe duyet gia). Idempotent (updateOrCreate).
 */
class Xml3176ErrorCatalogVatPhamKemDvktSeeder extends Seeder
{
    public function run()
    {
        $rules = [
            ['XMLComplete_VTYT_KHONG_CO_DVKT_ME', 'VTYT thanh toán ngoài giá không có DVKT mẹ được BHYT thanh toán',
                'VTYT thuộc danh mục "chưa bao gồm trong giá DVKT" được đề nghị quỹ thanh toán nhưng hồ sơ không có DVKT mẹ, hoặc DVKT mẹ không được quỹ thanh toán (BN tự trả).'],
            ['XMLComplete_THUOC_KHONG_CO_DVKT_ME', 'Thuốc thanh toán ngoài giá không có DVKT mẹ được BHYT thanh toán',
                'Thuốc chỉ dùng kèm DVKT (cản quang, dược chất phóng xạ...) được đề nghị quỹ thanh toán nhưng hồ sơ không có DVKT mẹ được quỹ thanh toán.'],
            ['XMLComplete_GOI_VTYT_KHONG_KHOP', 'Gói VTYT không khớp DVKT mẹ được BHYT thanh toán',
                'Dòng VTYT ghi MA_DICH_VU/GOI_VTYT nhưng DVKT mẹ không có trong hồ sơ, không được quỹ thanh toán, hoặc số gói vượt số lần DVKT mẹ được thanh toán.'],
            ['XMLComplete_THUOC_DVKT_ME_KHONG_KHOP', 'Thuốc gắn DVKT mẹ không được BHYT thanh toán',
                'Dòng thuốc ghi MA_DICH_VU nhưng DVKT mẹ không có trong hồ sơ hoặc không được quỹ thanh toán.'],
        ];

        foreach ($rules as $r) {
            list($code, $name, $description) = $r;
            Xml3176ErrorCatalog::updateOrCreate(
                ['xml' => 'XMLComplete', 'error_code' => $code],
                ['error_name' => $name, 'description' => $description, 'critical_error' => true, 'is_check' => true]
            );
        }
    }
}
```

- [ ] **Step 9: `composer dump-autoload` (seeder dùng classmap) rồi chạy test thư mục**

Run: `composer dump-autoload -q && DB_HOST=127.0.0.1 vendor/bin/phpunit tests/Unit/Xml3176/VatPhamKemDvkt`
Expected: `OK (32 tests, ...)`

- [ ] **Step 10: Chạy lại test Complete checker sẵn có để chắc không vỡ**

Run: `DB_HOST=127.0.0.1 vendor/bin/phpunit tests/Unit/Xml3176/Checker`
Expected: cùng số lỗi như mốc Task 0 cho thư mục này (không tăng).

- [ ] **Step 11: Commit**

```bash
git add app/Services/Xml3176/VatPhamKemDvktLookup.php app/Services/Xml3176CompleteChecker.php database/seeds/Xml3176ErrorCatalogVatPhamKemDvktSeeder.php tests/Unit/Xml3176/VatPhamKemDvkt
git commit -m "feat(xml3176): noi quy tac vat pham kem DVKT me vao kiem ho so

Them 4 ma loi XMLComplete_* (critical), bang tra nap mot lan moi tien trinh, TTL 10 phut.

Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>"
```

---

### Task 4: Học bảng tra từ dữ liệu (`VatPhamKemDvktLearner::hoc` + `ghi`)

**Files:**
- Create: `app/Services/Xml3176/VatPhamKemDvktLearner.php`
- Test: `tests/Unit/Xml3176/VatPhamKemDvkt/VatPhamKemDvktLearnerTest.php`

**Interfaces:**
- Consumes: model + hằng, `VatPhamKemDvktKey` (Task 1).
- Produces:
  - `hoc(float $nguong, int $toiThieu, $tuNgay = null): array` → `['dong' => [[loai, ma_vat_pham, ten_vat_pham, ten_khop, ma_dvkt, ten_dvkt, so_lan_gan, ty_le_gan], ...], 'so_vat_pham' => ['VTYT' => int, 'THUOC' => int]]`
  - `ghi(array $dong): array` → `['them' => int, 'cap_nhat' => int, 'tat' => int, 'bat_lai' => int]`

- [ ] **Step 1: Viết test (thất bại)**

`tests/Unit/Xml3176/VatPhamKemDvkt/VatPhamKemDvktLearnerTest.php`:

```php
<?php

namespace Tests\Unit\Xml3176\VatPhamKemDvkt;

use App\Models\BHYT\Xml3176VatPhamKemDvkt;
use App\Services\Xml3176\VatPhamKemDvktLearner;
use Illuminate\Support\Facades\DB;
use Tests\Support\Xml3176RuleTestSupport;
use Tests\TestCase;

class VatPhamKemDvktLearnerTest extends TestCase
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
        ]);
    }

    /** Them $n dong VTYT BHYT tra, $lk dong trong do gan ma_dich_vu = $dv. */
    private function vtyt(string $ma, string $ten, int $n, int $lk, string $dv): void
    {
        for ($i = 0; $i < $n; $i++) {
            DB::table('xml3176_xml3s')->insert([
                'ma_lk' => 'HS' . $i, 'stt' => ++$this->stt, 'ma_vat_tu' => $ma, 'ten_vat_tu' => $ten,
                'ma_dich_vu' => $i < $lk ? $dv : null, 'goi_vtyt' => $i < $lk ? 'G1' : null,
                't_bhtt' => 1000, 'so_luong' => 1, 'ngay_yl' => '202609010800',
            ]);
        }
    }

    private function dvkt(string $ma, string $ten): void
    {
        DB::table('xml3176_xml3s')->insert([
            'ma_lk' => 'DV', 'stt' => ++$this->stt, 'ma_vat_tu' => '', 'ma_dich_vu' => $ma, 'ten_dich_vu' => $ten,
            't_bhtt' => 1000, 'so_luong' => 1, 'ngay_yl' => '202609010800',
        ]);
    }

    private function thuoc(string $ma, string $ten, int $n, int $lk, string $dv): void
    {
        for ($i = 0; $i < $n; $i++) {
            DB::table('xml3176_xml2s')->insert([
                'ma_lk' => 'HS' . $i, 'stt' => ++$this->stt, 'ma_thuoc' => $ma, 'ten_thuoc' => $ten,
                'ma_dich_vu' => $i < $lk ? $dv : null, 't_bhtt' => 1000, 'ngay_yl' => '202609010800',
            ]);
        }
    }

    private function learner(): VatPhamKemDvktLearner
    {
        return new VatPhamKemDvktLearner();
    }

    /** @test */
    public function chon_vat_pham_dat_nguong_va_toi_thieu()
    {
        $this->vtyt('N08.00.150', 'Buồng tiêm truyền', 10, 9, '12.0448.1187_GT'); // 90% -> chon
        $this->vtyt('N03.01.070.5484.000.0004', 'Bơm tiêm 10ml', 10, 2, '01.0175.0196'); // 20% -> bo
        $this->vtyt('N03.03.030.2239.279.0001', 'Kim chọc tủy', 3, 3, '22.0126.0092'); // 3 < 5 -> bo
        $this->dvkt('12.0448.1187', 'Đặt buồng tiêm truyền dưới da');

        $kq = $this->learner()->hoc(0.8, 5);

        $this->assertSame(['VTYT' => 1, 'THUOC' => 0], $kq['so_vat_pham']);
        $this->assertCount(1, $kq['dong']);
        $d = $kq['dong'][0];
        $this->assertSame('VTYT', $d['loai']);
        $this->assertSame('N08.00.150', $d['ma_vat_pham']);
        $this->assertSame('buồng tiêm truyền', $d['ten_khop']);
        $this->assertSame('12.0448.1187', $d['ma_dvkt']);
        $this->assertSame('Đặt buồng tiêm truyền dưới da', $d['ten_dvkt']);
        $this->assertSame(9, $d['so_lan_gan']);
        $this->assertSame(0.9, $d['ty_le_gan']);
    }

    /** @test */
    public function moi_dvkt_tung_gan_deu_vao_danh_sach_me()
    {
        $this->thuoc('40.644', 'Omnipaque', 5, 3, '18.0150.0041');
        $this->thuoc('40.644', 'Omnipaque', 5, 5, '18.0156.0041');

        $kq = $this->learner()->hoc(0.8, 5);

        $this->assertSame(['18.0150.0041', '18.0156.0041'], collect($kq['dong'])->pluck('ma_dvkt')->sort()->values()->all());
        $this->assertSame('', $kq['dong'][0]['ten_khop']);
    }

    /** @test */
    public function ghi_them_moi_roi_hoc_lai_cap_nhat()
    {
        $this->vtyt('N08.00.150', 'Buồng tiêm truyền', 10, 9, '12.0448.1187');
        $l = $this->learner();

        $this->assertSame(['them' => 1, 'cap_nhat' => 0, 'tat' => 0, 'bat_lai' => 0], $l->ghi($l->hoc(0.8, 5)['dong']));
        $this->assertSame(['them' => 0, 'cap_nhat' => 1, 'tat' => 0, 'bat_lai' => 0], $l->ghi($l->hoc(0.8, 5)['dong']));
        $this->assertSame(1, Xml3176VatPhamKemDvkt::count());
    }

    /** @test */
    public function khong_sua_dong_tay_va_giu_dong_nguoi_dung_tat()
    {
        $this->vtyt('N08.00.150', 'Buồng tiêm truyền', 10, 9, '12.0448.1187');
        $l = $this->learner();
        $l->ghi($l->hoc(0.8, 5)['dong']);
        Xml3176VatPhamKemDvkt::query()->update(['hieu_luc' => false, 'ghi_chu' => 'Người dùng tắt']);
        Xml3176VatPhamKemDvkt::create(['loai' => 'VTYT', 'ma_vat_pham' => 'N06.02.100', 'ten_khop' => 'stent đại tràng',
            'ma_dvkt' => '20.0053.0105', 'nguon' => 'TAY', 'so_lan_gan' => 0]);

        $l->ghi($l->hoc(0.8, 5)['dong']);

        $hoc = Xml3176VatPhamKemDvkt::where('nguon', 'HOC')->first();
        $this->assertFalse($hoc->hieu_luc);
        $this->assertSame('Người dùng tắt', $hoc->ghi_chu);
        $this->assertTrue(Xml3176VatPhamKemDvkt::where('nguon', 'TAY')->first()->hieu_luc);
    }

    /** @test */
    public function dong_hoc_roi_duoi_nguong_bi_tat_va_tu_bat_lai()
    {
        $this->vtyt('N08.00.150', 'Buồng tiêm truyền', 10, 9, '12.0448.1187');
        $l = $this->learner();
        $l->ghi($l->hoc(0.8, 5)['dong']);

        $kq = $l->ghi($l->hoc(0.95, 5)['dong']); // nguong cao hon -> roi khoi bang
        $this->assertSame(1, $kq['tat']);
        $m = Xml3176VatPhamKemDvkt::first();
        $this->assertFalse($m->hieu_luc);
        $this->assertSame(Xml3176VatPhamKemDvkt::GHI_CHU_TU_TAT, $m->ghi_chu);

        $kq = $l->ghi($l->hoc(0.8, 5)['dong']);
        $this->assertSame(1, $kq['bat_lai']);
        $this->assertTrue(Xml3176VatPhamKemDvkt::first()->hieu_luc);
    }

    /** @test */
    public function loc_theo_tu_ngay()
    {
        $this->vtyt('N08.00.150', 'Buồng tiêm truyền', 10, 9, '12.0448.1187');

        $this->assertSame([], $this->learner()->hoc(0.8, 5, '202610')['dong']);
    }
}
```

- [ ] **Step 2: Chạy, xác nhận thất bại**

Run: `DB_HOST=127.0.0.1 vendor/bin/phpunit tests/Unit/Xml3176/VatPhamKemDvkt/VatPhamKemDvktLearnerTest.php`
Expected: FAIL — `Class 'App\Services\Xml3176\VatPhamKemDvktLearner' not found`.

- [ ] **Step 3: Viết `VatPhamKemDvktLearner` (hoc + ghi)**

`app/Services/Xml3176/VatPhamKemDvktLearner.php`:

```php
<?php

namespace App\Services\Xml3176;

use App\Models\BHYT\Xml3176VatPhamKemDvkt;
use App\Services\Xml3176\Support\VatPhamKemDvktKey;
use Illuminate\Support\Facades\DB;

/**
 * Hoc bang tra vat pham -> DVKT me tu dong XML3/XML2 da duoc HIS gan ma_dich_vu.
 *
 * Chi giu vat pham co ty le dong BHYT tra duoc gan >= nguong va so dong >= toi thieu: bom
 * kim, day truyen, NaCl... cung tung duoc gan vao DVKT (dung trong ca thu thuat) nen hoc tho
 * se bao nham hang chuc nghin ho so. Gom bang SQL GROUP BY, khong keo dong chi tiet ve PHP.
 */
class VatPhamKemDvktLearner
{
    const NGUON = [
        'VTYT' => ['bang' => 'xml3176_xml3s', 'ma' => 'ma_vat_tu', 'ten' => 'ten_vat_tu'],
        'THUOC' => ['bang' => 'xml3176_xml2s', 'ma' => 'ma_thuoc', 'ten' => 'ten_thuoc'],
    ];

    public function hoc(float $nguong, int $toiThieu, $tuNgay = null): array
    {
        $dong = [];
        $soVp = [];
        $cacCap = [];
        $maGoc = [];

        foreach (self::NGUON as $loai => $n) {
            $chon = $this->chonVatPham($loai, $n, $nguong, $toiThieu, $tuNgay);
            $soVp[$loai] = count($chon);
            if (empty($chon)) {
                continue;
            }
            $cap = $this->capDaGan($loai, $n, $chon, $tuNgay);
            foreach ($cap as $dsDv) {
                foreach ($dsDv as $maDv => $c) {
                    // Ca ma goc (vd 12.0448.1187_GT) lan ma chuan hoa: dong DVKT co the mang dang nao cung duoc.
                    $maGoc[] = $maDv;
                    $maGoc = array_merge($maGoc, array_keys($c['goc']));
                }
            }
            $cacCap[$loai] = [$chon, $cap];
        }

        $tenDv = $this->tenDvkt($maGoc);

        foreach ($cacCap as $loai => list($chon, $cap)) {
            foreach ($cap as $khoa => $dsDv) {
                $vp = $chon[$khoa];
                foreach ($dsDv as $maDv => $c) {
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
        }

        return ['dong' => $dong, 'so_vat_pham' => $soVp];
    }

    public function ghi(array $dong): array
    {
        $kq = ['them' => 0, 'cap_nhat' => 0, 'tat' => 0, 'bat_lai' => 0];

        DB::transaction(function () use ($dong, &$kq) {
            $hienCo = [];
            foreach (Xml3176VatPhamKemDvkt::all() as $m) {
                $hienCo[self::khoaDong($m->loai, $m->ma_vat_pham, $m->ten_khop, $m->ma_dvkt)] = $m;
            }

            $daHoc = [];
            foreach ($dong as $d) {
                $k = self::khoaDong($d['loai'], $d['ma_vat_pham'], $d['ten_khop'], $d['ma_dvkt']);
                $daHoc[$k] = true;
                $m = $hienCo[$k] ?? null;

                if ($m === null) {
                    Xml3176VatPhamKemDvkt::create($d + ['nguon' => Xml3176VatPhamKemDvkt::NGUON_HOC, 'hieu_luc' => true]);
                    $kq['them']++;
                    continue;
                }
                if ($m->nguon !== Xml3176VatPhamKemDvkt::NGUON_HOC) {
                    continue;
                }

                $m->fill([
                    'ten_vat_pham' => $d['ten_vat_pham'],
                    'ten_dvkt' => $d['ten_dvkt'],
                    'so_lan_gan' => $d['so_lan_gan'],
                    'ty_le_gan' => $d['ty_le_gan'],
                ]);
                if (!$m->hieu_luc && $m->ghi_chu === Xml3176VatPhamKemDvkt::GHI_CHU_TU_TAT) {
                    $m->hieu_luc = true;
                    $m->ghi_chu = null;
                    $kq['bat_lai']++;
                }
                $m->save();
                $kq['cap_nhat']++;
            }

            foreach ($hienCo as $k => $m) {
                if (isset($daHoc[$k]) || $m->nguon !== Xml3176VatPhamKemDvkt::NGUON_HOC || !$m->hieu_luc) {
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

    private static function khoaDong($loai, $ma, $tenKhop, $maDvkt): string
    {
        return implode("\x1F", [$loai, trim((string) $ma), (string) $tenKhop, VatPhamKemDvktKey::chuanHoaMaDvkt($maDvkt)]);
    }

    private function chonVatPham(string $loai, array $n, float $nguong, int $toiThieu, $tuNgay): array
    {
        $q = DB::table($n['bang'])
            ->selectRaw("{$n['ma']} as ma, {$n['ten']} as ten, COUNT(*) as so_dong, "
                . "SUM(CASE WHEN ma_dich_vu IS NOT NULL AND ma_dich_vu <> '' THEN 1 ELSE 0 END) as so_lk")
            ->where($n['ma'], '<>', '')
            ->where('t_bhtt', '>', 0)
            ->groupBy($n['ma'], $n['ten']);
        if ($tuNgay) {
            $q->where('ngay_yl', '>=', $tuNgay);
        }

        $gom = [];
        foreach ($q->get() as $r) {
            $khoa = VatPhamKemDvktKey::khoa($loai, $r->ma, $r->ten);
            if (!isset($gom[$khoa])) {
                $gom[$khoa] = [
                    'ma' => trim((string) $r->ma),
                    'ten' => (string) $r->ten,
                    'ten_khop' => VatPhamKemDvktKey::tenKhop($loai, $r->ma, $r->ten),
                    'n' => 0,
                    'lk' => 0,
                ];
            }
            $gom[$khoa]['n'] += (int) $r->so_dong;
            $gom[$khoa]['lk'] += (int) $r->so_lk;
        }

        return array_filter($gom, function ($g) use ($nguong, $toiThieu) {
            return $g['n'] >= $toiThieu && $g['lk'] / $g['n'] >= $nguong;
        });
    }

    private function capDaGan(string $loai, array $n, array $chon, $tuNgay): array
    {
        $q = DB::table($n['bang'])
            ->selectRaw("{$n['ma']} as ma, {$n['ten']} as ten, ma_dich_vu as dv, COUNT(*) as so_dong")
            ->where($n['ma'], '<>', '')
            ->where('ma_dich_vu', '<>', '')
            ->groupBy($n['ma'], $n['ten'], 'ma_dich_vu');
        if ($tuNgay) {
            $q->where('ngay_yl', '>=', $tuNgay);
        }

        $cap = [];
        foreach ($q->get() as $r) {
            $khoa = VatPhamKemDvktKey::khoa($loai, $r->ma, $r->ten);
            $dv = VatPhamKemDvktKey::chuanHoaMaDvkt($r->dv);
            if (!isset($chon[$khoa]) || $dv === '') {
                continue;
            }
            if (!isset($cap[$khoa][$dv])) {
                $cap[$khoa][$dv] = ['n' => 0, 'goc' => []];
            }
            $cap[$khoa][$dv]['n'] += (int) $r->so_dong;
            $cap[$khoa][$dv]['goc'][$r->dv] = true;
        }

        return $cap;
    }

    /** Ten DVKT lay tu chinh dong DVKT trong XML3 (ma_vat_tu rong), khoa theo ma chuan hoa. */
    private function tenDvkt(array $maGoc): array
    {
        $ten = [];
        foreach (array_chunk(array_values(array_unique($maGoc)), 500) as $lo) {
            $rows = DB::table('xml3176_xml3s')
                ->selectRaw('ma_dich_vu as ma, MAX(ten_dich_vu) as ten')
                ->where(function ($q) {
                    $q->whereNull('ma_vat_tu')->orWhere('ma_vat_tu', '');
                })
                ->whereIn('ma_dich_vu', $lo)
                ->groupBy('ma_dich_vu')
                ->get();
            foreach ($rows as $r) {
                $k = VatPhamKemDvktKey::chuanHoaMaDvkt($r->ma);
                if (!isset($ten[$k]) && $r->ten !== null && $r->ten !== '') {
                    $ten[$k] = $r->ten;
                }
            }
        }

        return $ten;
    }
}
```

- [ ] **Step 4: Chạy, xác nhận qua**

Run: `DB_HOST=127.0.0.1 vendor/bin/phpunit tests/Unit/Xml3176/VatPhamKemDvkt/VatPhamKemDvktLearnerTest.php`
Expected: `OK (6 tests, ...)`

- [ ] **Step 5: Commit**

```bash
git add app/Services/Xml3176/VatPhamKemDvktLearner.php tests/Unit/Xml3176/VatPhamKemDvkt/VatPhamKemDvktLearnerTest.php
git commit -m "feat(xml3176): hoc bang tra vat pham kem DVKT me tu du lieu da lien ket

Loc ty le gan >= nguong va so dong toi thieu; giu dong TAY va trang thai tat cua nguoi dung.

Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>"
```

---

### Task 5: Nạp Excel (`nap`), xuất/đọc Excel, hai lệnh artisan

**Files:**
- Modify: `app/Services/Xml3176/VatPhamKemDvktLearner.php` (thêm `nap`, `docHieuLuc`)
- Create: `app/Exports/VatPhamKemDvktExport.php`
- Create: `app/Imports/VatPhamKemDvktImport.php`
- Create: `app/Console/Commands/Xml3176HocVatPhamKemDvkt.php`
- Create: `app/Console/Commands/Xml3176NapVatPhamKemDvkt.php`
- Test: `tests/Unit/Xml3176/VatPhamKemDvkt/VatPhamKemDvktLenhTest.php`

**Interfaces:**
- Consumes: `VatPhamKemDvktLearner::hoc`, `ghi` (Task 4); model (Task 1).
- Produces:
  - `VatPhamKemDvktLearner::nap(array $hang): array` → `['them' => int, 'cap_nhat' => int, 'loi' => [['dong' => int, 'ly_do' => string], ...]]`; `$hang` là danh sách mảng có khoá `loai, ma_vat_pham, ten_vat_pham, ma_dvkt, ten_dvkt, hieu_luc, ghi_chu` (phần tử 0 = dòng Excel 2)
  - `VatPhamKemDvktLearner::docHieuLuc($v): bool`
  - `VatPhamKemDvktExport::COT` = `['loai','ma_vat_pham','ten_vat_pham','ma_dvkt','ten_dvkt','nguon','hieu_luc','so_lan_gan','ty_le_gan','ghi_chu']`
  - Lệnh `xml3176:hoc-vat-pham-kem-dvkt {--nguong=} {--toi-thieu=} {--tu-ngay=} {--thu} {--xuat=}` (`--xuat` là tên tệp trong `storage/app`)
  - Lệnh `xml3176:nap-vat-pham-kem-dvkt {tep}` (đường dẫn tuyệt đối hoặc tương đối thư mục làm việc)

- [ ] **Step 1: Viết test (thất bại)**

`tests/Unit/Xml3176/VatPhamKemDvkt/VatPhamKemDvktLenhTest.php`:

```php
<?php

namespace Tests\Unit\Xml3176\VatPhamKemDvkt;

use App\Exports\VatPhamKemDvktExport;
use App\Models\BHYT\Xml3176VatPhamKemDvkt;
use App\Services\Xml3176\VatPhamKemDvktLearner;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Tests\Support\Xml3176RuleTestSupport;
use Tests\TestCase;

class VatPhamKemDvktLenhTest extends TestCase
{
    use Xml3176RuleTestSupport;

    private $thuMuc;

    protected function setUp()
    {
        parent::setUp();
        $this->bootXml3176Sqlite([
            '2026_01_09_152826_create_xml3176_xml2s_table.php',
            '2026_01_09_152832_create_xml3176_xml3s_table.php',
            '2026_10_13_090000_create_xml3176_vat_pham_kem_dvkt_table.php',
        ]);
        for ($i = 0; $i < 10; $i++) {
            DB::table('xml3176_xml3s')->insert([
                'ma_lk' => 'HS' . $i, 'stt' => $i + 1, 'ma_vat_tu' => 'N08.00.150', 'ten_vat_tu' => 'Buồng tiêm truyền',
                'ma_dich_vu' => $i < 9 ? '12.0448.1187' : null, 't_bhtt' => 1000, 'so_luong' => 1, 'ngay_yl' => '202609010800',
            ]);
        }
        $this->thuMuc = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'vpkd_' . uniqid();
        mkdir($this->thuMuc);
    }

    protected function tearDown()
    {
        foreach (glob($this->thuMuc . DIRECTORY_SEPARATOR . '*') as $tep) {
            unlink($tep);
        }
        rmdir($this->thuMuc);
        parent::tearDown();
    }

    private function ghiXlsx(array $hang): string
    {
        $duongDan = $this->thuMuc . DIRECTORY_SEPARATOR . 'nap.xlsx';
        $sheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $ws = $sheet->getActiveSheet();
        foreach ($hang as $i => $dong) {
            foreach (array_values($dong) as $j => $v) {
                $ws->setCellValueExplicitByColumnAndRow($j + 1, $i + 1, (string) $v, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            }
        }
        (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($sheet))->save($duongDan);
        $sheet->disconnectWorksheets();

        return $duongDan;
    }

    /** @test */
    public function lenh_hoc_che_do_thu_khong_ghi()
    {
        $ma = Artisan::call('xml3176:hoc-vat-pham-kem-dvkt', ['--thu' => true]);

        $this->assertSame(0, $ma);
        $this->assertContains('VTYT: 1 vật phẩm, 1 cặp', Artisan::output());
        $this->assertSame(0, Xml3176VatPhamKemDvkt::count());
    }

    /** @test */
    public function lenh_hoc_ghi_va_xuat()
    {
        Excel::fake();

        Artisan::call('xml3176:hoc-vat-pham-kem-dvkt', ['--xuat' => 'vpkd.xlsx']);

        $this->assertSame(1, Xml3176VatPhamKemDvkt::count());
        Excel::assertStored('vpkd.xlsx', 'local');
    }

    /** @test */
    public function lenh_hoc_tu_choi_nguong_ngoai_khoang()
    {
        $this->assertSame(1, Artisan::call('xml3176:hoc-vat-pham-kem-dvkt', ['--nguong' => '1.5']));
    }

    /** @test */
    public function xuat_giu_ma_dang_chuoi_va_hieu_luc_1_0()
    {
        $ex = new VatPhamKemDvktExport([
            ['loai' => 'THUOC', 'ma_vat_pham' => '40.540', 'ma_dvkt' => '18.0659.0054', 'hieu_luc' => false],
            ['loai' => 'VTYT', 'ma_vat_pham' => 'N08.00.150', 'ma_dvkt' => '12.0448.1187'],
        ]);

        $this->assertSame(VatPhamKemDvktExport::COT, $ex->headings());
        $this->assertSame('40.540', $ex->array()[0][1]);
        $this->assertSame('0', $ex->array()[0][6]);
        $this->assertSame('1', $ex->array()[1][6]);
    }

    /** @test */
    public function nap_them_dong_tay_cap_nhat_dong_co_san_va_bao_loi()
    {
        Artisan::call('xml3176:hoc-vat-pham-kem-dvkt');
        $tep = $this->ghiXlsx([
            VatPhamKemDvktExport::COT,
            ['VTYT', 'N08.00.150', 'Buồng tiêm truyền', '12.0448.1187', 'Đặt buồng', 'HOC', '0', '9', '0.9', 'Đã rà soát'],
            ['VTYT', 'N06.02.100', 'Stent đại tràng', '20.0053.0105', 'Nội soi đặt stent', '', '1', '', '', ''],
            ['XYZ', 'N06.02.100', '', '20.0053.0105', '', '', '1', '', '', ''],
            ['THUOC', '', '', '18.0150.0041', '', '', '1', '', '', ''],
        ]);

        $ma = Artisan::call('xml3176:nap-vat-pham-kem-dvkt', ['tep' => $tep]);
        $ra = Artisan::output();

        $this->assertSame(0, $ma);
        $this->assertContains('Thêm 1, cập nhật 1, lỗi 2', $ra);
        $this->assertContains('Dòng 4', $ra);
        $this->assertContains('Dòng 5', $ra);

        $hoc = Xml3176VatPhamKemDvkt::where('ma_vat_pham', 'N08.00.150')->first();
        $this->assertFalse($hoc->hieu_luc);
        $this->assertSame('Đã rà soát', $hoc->ghi_chu);

        $tay = Xml3176VatPhamKemDvkt::where('ma_vat_pham', 'N06.02.100')->first();
        $this->assertSame('TAY', $tay->nguon);
        $this->assertSame('stent đại tràng', $tay->ten_khop);
        $this->assertTrue($tay->hieu_luc);
    }

    /** @test */
    public function nap_tep_khong_ton_tai_thi_bao_loi()
    {
        $this->assertSame(1, Artisan::call('xml3176:nap-vat-pham-kem-dvkt', ['tep' => $this->thuMuc . '/khong-co.xlsx']));
    }

    /** @test */
    public function doc_hieu_luc()
    {
        foreach ([null, '', '1', 'x', 'có', 1, true] as $v) {
            $this->assertTrue(VatPhamKemDvktLearner::docHieuLuc($v), var_export($v, true));
        }
        foreach (['0', 'false', 'không', 'khong', 'N', 0, false] as $v) {
            $this->assertFalse(VatPhamKemDvktLearner::docHieuLuc($v), var_export($v, true));
        }
    }
}
```

- [ ] **Step 2: Chạy, xác nhận thất bại**

Run: `DB_HOST=127.0.0.1 vendor/bin/phpunit tests/Unit/Xml3176/VatPhamKemDvkt/VatPhamKemDvktLenhTest.php`
Expected: FAIL — lệnh `xml3176:hoc-vat-pham-kem-dvkt` không tồn tại / class Export không tìm thấy.

- [ ] **Step 3: Thêm `nap` và `docHieuLuc` vào `VatPhamKemDvktLearner`**

Thêm hai method public vào cuối class `VatPhamKemDvktLearner` (trước `}` cuối):

```php
    /**
     * Nap cac dong Excel da duyet. Dong co san -> cap nhat hieu_luc, ghi_chu; dong moi -> them
     * voi nguon TAY. Khong xoa dong nao. Dong loi duoc bao lai, cac dong khac van nap.
     */
    public function nap(array $hang): array
    {
        $kq = ['them' => 0, 'cap_nhat' => 0, 'loi' => []];

        foreach (array_values($hang) as $i => $h) {
            $dongExcel = $i + 2;
            if (count(array_filter(array_map(function ($v) { return trim((string) $v); }, (array) $h), 'strlen')) === 0) {
                continue;
            }

            $loai = strtoupper(trim((string) ($h['loai'] ?? '')));
            $ma = trim((string) ($h['ma_vat_pham'] ?? ''));
            $maDv = VatPhamKemDvktKey::chuanHoaMaDvkt($h['ma_dvkt'] ?? '');

            if (!in_array($loai, [Xml3176VatPhamKemDvkt::LOAI_VTYT, Xml3176VatPhamKemDvkt::LOAI_THUOC], true)) {
                $kq['loi'][] = ['dong' => $dongExcel, 'ly_do' => "Loại '{$loai}' không hợp lệ (VTYT hoặc THUOC)"];
                continue;
            }
            if ($ma === '' || $maDv === '') {
                $kq['loi'][] = ['dong' => $dongExcel, 'ly_do' => 'Thiếu mã vật phẩm hoặc mã DVKT'];
                continue;
            }

            $ten = trim((string) ($h['ten_vat_pham'] ?? ''));
            $tenKhop = VatPhamKemDvktKey::tenKhop($loai, $ma, $ten);
            $ghiChu = trim((string) ($h['ghi_chu'] ?? ''));
            $ghiChu = $ghiChu === '' ? null : $ghiChu;
            $hieuLuc = self::docHieuLuc($h['hieu_luc'] ?? null);

            $m = Xml3176VatPhamKemDvkt::where([
                'loai' => $loai, 'ma_vat_pham' => $ma, 'ten_khop' => $tenKhop, 'ma_dvkt' => $maDv,
            ])->first();

            if ($m) {
                $m->hieu_luc = $hieuLuc;
                $m->ghi_chu = $ghiChu;
                $m->save();
                $kq['cap_nhat']++;
                continue;
            }

            $tenDvkt = trim((string) ($h['ten_dvkt'] ?? ''));
            Xml3176VatPhamKemDvkt::create([
                'loai' => $loai, 'ma_vat_pham' => $ma, 'ten_vat_pham' => $ten === '' ? null : $ten,
                'ten_khop' => $tenKhop, 'ma_dvkt' => $maDv, 'ten_dvkt' => $tenDvkt === '' ? null : $tenDvkt,
                'nguon' => Xml3176VatPhamKemDvkt::NGUON_TAY, 'so_lan_gan' => 0, 'ty_le_gan' => null,
                'hieu_luc' => $hieuLuc, 'ghi_chu' => $ghiChu,
            ]);
            $kq['them']++;
        }

        return $kq;
    }

    /** O trong -> bat. Tat khi: 0, false, khong/không, n, no, tat/tắt. */
    public static function docHieuLuc($v): bool
    {
        if ($v === null) {
            return true;
        }
        if (is_bool($v)) {
            return $v;
        }
        $s = mb_strtolower(trim((string) $v), 'UTF-8');

        return !in_array($s, ['0', 'false', 'khong', 'không', 'n', 'no', 'tat', 'tắt'], true);
    }
```

- [ ] **Step 4: Viết Export**

`app/Exports/VatPhamKemDvktExport.php`:

```php
<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithHeadings;
use PhpOffice\PhpSpreadsheet\Cell\StringValueBinder;

/**
 * Xuat bang tra vat pham -> DVKT me de nguoi dung duyet. Moi o ghi dang CHUOI: ma thuoc
 * '40.540' ma de Excel doan kieu se thanh so 40.54 va khong khop lai khi nap.
 * Tieu de cot chinh la khoa ma lenh nap doc (WithHeadingRow).
 */
class VatPhamKemDvktExport extends StringValueBinder implements FromArray, WithHeadings, WithCustomValueBinder
{
    const COT = ['loai', 'ma_vat_pham', 'ten_vat_pham', 'ma_dvkt', 'ten_dvkt', 'nguon', 'hieu_luc', 'so_lan_gan', 'ty_le_gan', 'ghi_chu'];

    /** @var array */
    private $dong;

    public function __construct(array $dong)
    {
        $this->dong = $dong;
    }

    public function headings(): array
    {
        return self::COT;
    }

    public function array(): array
    {
        return array_map(function ($d) {
            $d = is_array($d) ? $d : $d->toArray();

            return array_map(function ($c) use ($d) {
                $v = $d[$c] ?? null;
                if ($c === 'hieu_luc') {
                    return ($v === null || $v) ? '1' : '0';
                }

                return $v === null ? '' : (string) $v;
            }, self::COT);
        }, $this->dong);
    }
}
```

- [ ] **Step 5: Viết Import**

`app/Imports/VatPhamKemDvktImport.php`:

```php
<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\ToArray;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

/** Doc tep Excel bang tra vat pham -> DVKT me (dong 1 la tieu de = VatPhamKemDvktExport::COT). */
class VatPhamKemDvktImport implements ToArray, WithHeadingRow
{
    /** @var array */
    public $hang = [];

    public function array(array $array)
    {
        $this->hang = array_merge($this->hang, $array);
    }
}
```

- [ ] **Step 6: Viết lệnh học**

`app/Console/Commands/Xml3176HocVatPhamKemDvkt.php`:

```php
<?php

namespace App\Console\Commands;

use App\Exports\VatPhamKemDvktExport;
use App\Models\BHYT\Xml3176VatPhamKemDvkt;
use App\Services\Xml3176\VatPhamKemDvktLearner;
use Illuminate\Console\Command;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Hoc bang tra vat pham (VTYT/thuoc) -> DVKT me tu dong XML3176 da duoc HIS gan ma_dich_vu.
 * Chay lai bat cu luc nao: dong TAY va trang thai tat cua nguoi dung duoc giu nguyen.
 */
class Xml3176HocVatPhamKemDvkt extends Command
{
    protected $signature = 'xml3176:hoc-vat-pham-kem-dvkt
        {--nguong= : Ty le gan toi thieu (0-1), mac dinh config xml3176.vat_pham_kem_dvkt.nguong}
        {--toi-thieu= : So dong BHYT tra toi thieu cua vat pham}
        {--tu-ngay= : Chi xet ngay_yl tu (yyyymmdd)}
        {--thu : Chi bao cao, khong ghi bang}
        {--xuat= : Ten tep .xlsx trong storage/app de xuat bang sau khi hoc}';

    protected $description = 'Học bảng tra VTYT/thuốc thanh toán ngoài giá -> DVKT mẹ từ dữ liệu XML3176 đã liên kết';

    public function handle(VatPhamKemDvktLearner $learner)
    {
        $nguong = (float) ($this->option('nguong') ?? config('xml3176.vat_pham_kem_dvkt.nguong', 0.8));
        $toiThieu = (int) ($this->option('toi-thieu') ?? config('xml3176.vat_pham_kem_dvkt.toi_thieu', 5));
        if ($nguong <= 0 || $nguong > 1 || $toiThieu < 1) {
            $this->error('Ngưỡng phải trong (0, 1] và tối thiểu >= 1.');

            return 1;
        }

        $kq = $learner->hoc($nguong, $toiThieu, $this->option('tu-ngay') ?: null);
        foreach ($kq['so_vat_pham'] as $loai => $soVp) {
            $soCap = count(array_filter($kq['dong'], function ($d) use ($loai) { return $d['loai'] === $loai; }));
            $this->info("{$loai}: {$soVp} vật phẩm, {$soCap} cặp");
        }

        if ($this->option('thu')) {
            $this->warn('Chế độ thử: không ghi bảng.');
            $dongXuat = $kq['dong'];
        } else {
            $g = $learner->ghi($kq['dong']);
            $this->info("Ghi: thêm {$g['them']}, cập nhật {$g['cap_nhat']}, tắt {$g['tat']}, bật lại {$g['bat_lai']}");
            $dongXuat = Xml3176VatPhamKemDvkt::orderBy('loai')->orderBy('ma_vat_pham')->orderBy('ma_dvkt')->get()->all();
        }

        if ($tep = $this->option('xuat')) {
            Excel::store(new VatPhamKemDvktExport($dongXuat), $tep, 'local');
            $this->info('Đã xuất: ' . storage_path('app/' . $tep));
        }

        return 0;
    }
}
```

- [ ] **Step 7: Viết lệnh nạp**

`app/Console/Commands/Xml3176NapVatPhamKemDvkt.php`:

```php
<?php

namespace App\Console\Commands;

use App\Imports\VatPhamKemDvktImport;
use App\Services\Xml3176\VatPhamKemDvktLearner;
use Illuminate\Console\Command;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Nap tep Excel bang tra vat pham -> DVKT me sau khi nguoi dung duyet (dinh dang = tep xuat
 * cua xml3176:hoc-vat-pham-kem-dvkt). Dong moi thanh nguon TAY; cot hieu_luc bat/tat dong.
 * Ma phai o dinh dang Text trong Excel ('40.540' go thanh so se mat so 0 cuoi).
 */
class Xml3176NapVatPhamKemDvkt extends Command
{
    protected $signature = 'xml3176:nap-vat-pham-kem-dvkt {tep : Duong dan tep .xlsx}';

    protected $description = 'Nạp bảng tra VTYT/thuốc -> DVKT mẹ đã duyệt từ Excel';

    public function handle(VatPhamKemDvktLearner $learner)
    {
        $tep = $this->argument('tep');
        if (!is_file($tep)) {
            $this->error("Không tìm thấy tệp: {$tep}");

            return 1;
        }

        $imp = new VatPhamKemDvktImport();
        Excel::import($imp, $tep);
        $kq = $learner->nap($imp->hang);

        $this->info("Thêm {$kq['them']}, cập nhật {$kq['cap_nhat']}, lỗi " . count($kq['loi']));
        foreach ($kq['loi'] as $l) {
            $this->warn("Dòng {$l['dong']}: {$l['ly_do']}");
        }

        return 0;
    }
}
```

- [ ] **Step 8: Chạy, xác nhận qua**

Run: `DB_HOST=127.0.0.1 vendor/bin/phpunit tests/Unit/Xml3176/VatPhamKemDvkt/VatPhamKemDvktLenhTest.php`
Expected: `OK (7 tests, ...)`

Nếu `nap_them_dong_tay...` thất bại vì `Excel::import` với đường dẫn tuyệt đối không đọc được trên máy này: đổi lệnh nạp sang `Excel::import($imp, $tep, null, \Maatwebsite\Excel\Excel::XLSX)` rồi chạy lại (mẫu sẵn có `app/Services/Tt12/Tt12DocExcel.php:62` gọi `Excel::import($import, $duongDan)` với đường dẫn tuyệt đối).

- [ ] **Step 9: Chạy toàn thư mục**

Run: `DB_HOST=127.0.0.1 vendor/bin/phpunit tests/Unit/Xml3176/VatPhamKemDvkt`
Expected: `OK (45 tests, ...)`

- [ ] **Step 10: Commit**

```bash
git add app/Services/Xml3176/VatPhamKemDvktLearner.php app/Exports/VatPhamKemDvktExport.php app/Imports/VatPhamKemDvktImport.php app/Console/Commands/Xml3176HocVatPhamKemDvkt.php app/Console/Commands/Xml3176NapVatPhamKemDvkt.php tests/Unit/Xml3176/VatPhamKemDvkt/VatPhamKemDvktLenhTest.php
git commit -m "feat(xml3176): lenh hoc va nap bang tra vat pham kem DVKT me qua Excel

Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>"
```

---

### Task 6: Hồi quy toàn bộ và nghiệm thu trên CSDL thật (chỉ đọc)

**Files:**
- Create (ngoài repo, trong scratchpad): `nghiem_thu_vpkd.php`

**Interfaces:**
- Consumes: `VatPhamKemDvktLearner::hoc`, `VatPhamKemDvktLookup::tuDong`, `VatPhamKemDvktRule::kiem`.

- [ ] **Step 1: Hồi quy bộ test XML3176**

Run: `DB_HOST=127.0.0.1 vendor/bin/phpunit tests/Unit/Xml3176 2>&1 | tail -5`
Expected: số Failures/Errors **không lớn hơn** mốc Task 0; số test tăng đúng 45.

- [ ] **Step 2: Chạy thử lệnh học trên CSDL thật (không ghi)**

Run: `php artisan xml3176:hoc-vat-pham-kem-dvkt --thu`
Expected: in `VTYT: ~350 vật phẩm, ... cặp`, `THUOC: ~9 vật phẩm, ... cặp`, `Chế độ thử: không ghi bảng.` (Khảo sát ngày 13/10 theo khoá chỉ-mã ra 356/9; khoá mã+tên với mã nhóm chung có thể lệch vài vật phẩm.) Lệnh này chỉ đọc vì có `--thu`.

- [ ] **Step 3: Viết script nghiệm thu chỉ đọc**

Tạo `<scratchpad>/nghiem_thu_vpkd.php` (đường dẫn scratchpad của phiên làm việc, KHÔNG đặt trong repo):

```php
<?php
// Chay: php artisan tinker <scratchpad>/nghiem_thu_vpkd.php  (CHI DOC CSDL that)
use App\Models\BHYT\Xml3176Xml2;
use App\Models\BHYT\Xml3176Xml3;
use App\Services\Xml3176\VatPhamKemDvktLearner;
use App\Services\Xml3176\VatPhamKemDvktLookup;
use App\Services\Xml3176\VatPhamKemDvktRule;
use Illuminate\Support\Facades\DB;

DB::statement('SET SESSION TRANSACTION READ ONLY');
$kq = (new VatPhamKemDvktLearner())->hoc(0.8, 5);
$rule = new VatPhamKemDvktRule(VatPhamKemDvktLookup::tuDong($kq['dong']));

$phaiBat = ['000007339564', '000007285039', '000007301718', '000007403605'];
$khongBat = ['000007321137', '000007342941'];
foreach (array_merge($phaiBat, $khongBat) as $maLk) {
    $loi = $rule->kiem(
        Xml3176Xml3::where('ma_lk', $maLk)->get(),
        Xml3176Xml2::where('ma_lk', $maLk)->get()
    );
    $mongDoi = in_array($maLk, $phaiBat, true) ? 'PHAI BAT' : 'KHONG BAT';
    echo $maLk . ' [' . $mongDoi . '] ' . (empty($loi) ? '(khong loi)' : implode(' | ', array_map(function ($l) { return $l['key'] . ': ' . $l['mo_ta']; }, $loi))) . PHP_EOL;
}
```

- [ ] **Step 4: Chạy script nghiệm thu**

Run: `php artisan tinker <scratchpad>/nghiem_thu_vpkd.php`
Expected:
- 4 hồ sơ `[PHAI BAT]` mỗi hồ sơ có ít nhất một lỗi `VTYT_KHONG_CO_DVKT_ME` hoặc `THUOC_KHONG_CO_DVKT_ME` đúng vật phẩm (buồng tiêm truyền, kim chọc tủy, ống dẫn lưu qua da, Omnipaque).
- 2 hồ sơ `[KHONG BAT]` in `(khong loi)`.
Nếu lệch: KHÔNG sửa ngưỡng cho khớp — ghi lại hồ sơ lệch và nguyên nhân (khoá mã+tên, tên DVKT...), báo người dùng trước khi đi tiếp.

- [ ] **Step 5: Báo cáo người dùng**

Gửi: số vật phẩm/cặp học được, kết quả 6 hồ sơ nghiệm thu, mốc test trước/sau. Nhắc các bước triển khai prod (spec §5) — **người dùng tự chạy** migrate/seed/học/nạp/`config:cache` trên prod; không chạy từ máy dev vào CSDL thật.

---

## Self-review (đã chạy)

- Spec §3.1 bảng/khoá/chuẩn hoá → Task 1. §3.2 lệnh học/nạp, config → Task 1 (config), 4, 5. §3.3 R1/R2, nạp bảng tra, seeder → Task 2, 3. §4 kiểm thử → test trong Task 1–5; nghiệm thu → Task 6. §5 triển khai → Task 6 Step 5 (người dùng chạy).
- Khác spec có chủ đích: độ dài cột khoá (100/191/50 thay vì 255) vì giới hạn chỉ mục; `VatPhamKemDvktKey` đặt trong `app/Services/Xml3176/Support/` theo thư mục hỗ trợ sẵn có; thêm `bat_lai` (dòng tự tắt được tự bật lại khi vượt ngưỡng trở lại — chỉ dòng mang ghi chú tự tắt).
- Tên/kiểu nhất quán: `khoa()`, `tenKhop()`, `chuanHoaMaDvkt()`, `tuDong()`, `bang()`, `xoaBoNho()`, `hoc()`, `ghi()`, `nap()`, `docHieuLuc()`, `VatPhamKemDvktRule::TEN`, `VatPhamKemDvktExport::COT` dùng giống nhau ở mọi task.
