# DVKT bắt buộc có mã máy — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Đổi căn cứ của quy tắc "thiếu mã máy" trong XML3176 từ nhóm dịch vụ sang danh mục DVKT do BHXH ban hành.

**Architecture:** Thêm một loại danh mục quốc gia mới vào khung nhập danh mục sẵn có (bảng + model + 5 điểm khai báo), tra cứu qua `CommonValidationService` như mọi danh mục khác, và đổi điều kiện trong `Xml3176Xml3Checker`. Khi danh mục chưa nạp thì tự lùi về quy tắc nhóm cũ.

**Tech Stack:** Laravel 5.5, PHP 7.4, PHPUnit 6.5.

**Spec:** `docs/superpowers/specs/2026-09-10-dvkt-can-ma-may-design.md`

## Global Constraints

- PHP 7.4 / Laravel 5.5. PHPUnit 6.5: `setUp()` KHÔNG có `:void`; dùng `assertTrue`/`assertFalse`/`assertSame`.
- **CẤM `RefreshDatabase`** và mọi thao tác xoá/migrate CSDL dev `qlbv`. Test cần CSDL phải dùng sqlite in-memory qua trait `Tests\Support\Xml3176RuleTestSupport::bootXml3176Sqlite`.
- **CẤM `->change()` trong migration** — dự án KHÔNG cài `doctrine/dbal`. (Plan này chỉ tạo bảng mới nên không đụng tới.)
- **GIỮ NGUYÊN `error_code`** `XML3_INFO_ERROR_GROUP_CODE_MA_MAY`. Chỉ đổi `error_name` và `description`. Không tạo mã lỗi mới, không cần seeder.
- **KHÔNG đụng** hai quy tắc mã máy còn lại (`INFO_ERROR_MA_MAY_TOO_LONG`, `INFO_ERROR_MA_MAY_NOT_FOUND`) và **KHÔNG đụng** module QĐ130 (có config riêng `qd130xml.xml3.service_groups_requiring_machine`).
- Khi danh mục rỗng → **lùi về** `config('xml3176.xml3.service_groups_requiring_machine')` = `[1,2,3]`.
- Chạy test theo thư mục cụ thể, KHÔNG chạy toàn bộ `phpunit`.

## Sai khác có chủ ý so với spec

Spec §4.4 đặt logic tra cứu vào lớp mới `DvktCanMaMayService`. Plan này thay bằng **hai hàm trong `CommonValidationService`** — nơi dự án đã đặt mọi hàm tra danh mục (`isAdministrativeUnitProvinceValid`, `isMedicalOrganizationValid`, `isAdministrativeUnitWardInProvinceValid`…). Lý do: bám khuôn sẵn có, không sinh thêm một lớp chỉ chứa hai truy vấn. Phần *quyết định lùi* vẫn nằm ở checker vì đó là logic quy tắc, không phải tra danh mục.

---

## File Structure

- Create: `database/migrations/2026_09_10_100000_create_dvkt_can_ma_may_table.php`
- Create: `app/Models/BHYT/DvktCanMaMay.php`
- Modify: `config/danh_muc_bhyt.php` — thêm khoá `dvkt_can_ma_may`
- Modify: `config/catalog_import_mapping.php` — thêm khoá `dvkt_can_ma_may`
- Modify: `app/Services/CatalogImportService.php` — `GHI_THEO_LO`, `LAM_MOI_TRON_BO`, `bangCua()`
- Create: `app/Services/Xml3176/Support/MaDvktMatcher.php`
- Create: `tests/Unit/Xml3176/Support/MaDvktMatcherTest.php`
- Modify: `app/Services/CommonValidationService.php` — thêm 2 hàm
- Create: `tests/Unit/Xml3176/DvktCanMaMayTest.php`
- Modify: `app/Services/Xml3176Xml3Checker.php:258-267` — đổi điều kiện

---

### Task 1: Bảng danh mục và năm điểm khai báo

**Files:**
- Create: `database/migrations/2026_09_10_100000_create_dvkt_can_ma_may_table.php`
- Create: `app/Models/BHYT/DvktCanMaMay.php`
- Modify: `config/danh_muc_bhyt.php`
- Modify: `config/catalog_import_mapping.php`
- Modify: `app/Services/CatalogImportService.php`

**Interfaces:**
- Produces: bảng `dvkt_can_ma_may` (cột `ma_dvkt`, `ten_dvkt`, `is_active`); model `App\Models\BHYT\DvktCanMaMay`; loại danh mục `dvkt_can_ma_may` nhận được tệp BHXH. Task 3 truy vấn bảng này.

- [ ] **Step 1: Tạo migration**

Tạo `database/migrations/2026_09_10_100000_create_dvkt_can_ma_may_table.php`:

```php
<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Danh muc DVKT bat buoc phai gui kem ma may thuc hien, do BHXH ban hanh.
 *
 * Danh muc QUOC GIA (khong theo co so) va nap theo kieu THAY TRON BO: cot is_active la
 * bat buoc vi co che lam moi tron bo cua CatalogImportService dung chinh cot nay.
 */
class CreateDvktCanMaMayTable extends Migration
{
    public function up()
    {
        Schema::create('dvkt_can_ma_may', function (Blueprint $table) {
            $table->increments('id');
            $table->string('ma_dvkt', 50)->unique();
            $table->string('ten_dvkt', 1024)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('dvkt_can_ma_may');
    }
}
```

- [ ] **Step 2: Chạy migration**

Run: `php artisan migrate`
Expected: chạy trót lọt, in `CreateDvktCanMaMayTable`.

- [ ] **Step 3: Tạo model**

Tạo `app/Models/BHYT/DvktCanMaMay.php`:

```php
<?php

namespace App\Models\BHYT;

use Illuminate\Database\Eloquent\Model;

class DvktCanMaMay extends Model
{
    protected $table = 'dvkt_can_ma_may';
    protected $fillable = ['ma_dvkt', 'ten_dvkt', 'is_active'];
}
```

- [ ] **Step 4: Khai vào `config/danh_muc_bhyt.php`**

Thêm khối sau vào mảng trả về, ngay TRƯỚC dấu đóng mảng `];` cuối cùng:

```php
    'dvkt_can_ma_may' => [
        'ten' => 'DM DVKT cần mã máy',
        'model' => App\Models\BHYT\DvktCanMaMay::class,
        'bang' => 'dvkt_can_ma_may',
        'theo_co_so' => false,
    ],
```

- [ ] **Step 5: Khai vào `config/catalog_import_mapping.php`**

Thêm khối sau vào mảng trả về, ngay TRƯỚC dấu đóng mảng `];` cuối cùng. Bí danh lấy đúng header của tệp BHXH để nhập thẳng tệp gốc:

```php
    'dvkt_can_ma_may' => [
        'detect_keys' => ['MA_DVKT', 'TEN_DVKT_TT23'],
        'mapping' => [
            'ma_dvkt'  => ['MA_DVKT', 'Mã DVKT'],
            'ten_dvkt' => ['TEN_DVKT_TT23', 'TEN_DVKT_PHE_DUYET', 'Tên DVKT'],
        ],
        'required_fields' => ['ma_dvkt'],
        'unique_keys' => ['ma_dvkt'],
    ],
```

- [ ] **Step 6: Khai vào ba chỗ trong `CatalogImportService`**

Trong `app/Services/CatalogImportService.php`:

(a) Hằng `GHI_THEO_LO` — thêm `'dvkt_can_ma_may'` vào cuối danh sách:

```php
    const GHI_THEO_LO = ['medicine', 'medical_supply', 'service', 'icd10', 'icd_yhct',
                         'administrative_unit', 'medical_organization', 'medical_staff',
                         'department_bed', 'equipment', 'job_categories', 'dvkt_can_ma_may'];
```

(b) Hằng `LAM_MOI_TRON_BO` — thêm `'dvkt_can_ma_may'`:

```php
    const LAM_MOI_TRON_BO = ['administrative_unit', 'medical_organization', 'dvkt_can_ma_may'];
```

(c) Trong hàm `bangCua()`, thêm dòng vào mảng `$map` sau `'job_categories' => 'job_categories',`:

```php
            'dvkt_can_ma_may' => 'dvkt_can_ma_may',
```

- [ ] **Step 7: Chạy hai test tự động soi cấu hình danh mục**

Run: `./vendor/bin/phpunit tests/Unit/Import tests/Unit/CatalogTemplateSelfDetectTest.php`
Expected: PASS toàn bộ.

Hai test này chính là lưới an toàn: `NhapDanhMucConLaiTheoLoTest` đỏ nếu quên bước 6(a); `CatalogTemplateSelfDetectTest` đỏ nếu biểu mẫu của loại mới bị nhận diện nhầm sang loại khác.

- [ ] **Step 8: Commit**

```bash
git add database/migrations/2026_09_10_100000_create_dvkt_can_ma_may_table.php app/Models/BHYT/DvktCanMaMay.php config/danh_muc_bhyt.php config/catalog_import_mapping.php app/Services/CatalogImportService.php
git commit -m "feat(xml3176): them danh muc DVKT can ma may vao khung nhap danh muc"
```

---

### Task 2: Helper thuần tách mã gốc

**Files:**
- Create: `app/Services/Xml3176/Support/MaDvktMatcher.php`
- Test: `tests/Unit/Xml3176/Support/MaDvktMatcherTest.php`

**Interfaces:**
- Produces: `MaDvktMatcher::maGoc($ma): string` — Task 3 gọi.

- [ ] **Step 1: Viết test thất bại**

Tạo `tests/Unit/Xml3176/Support/MaDvktMatcherTest.php`:

```php
<?php

namespace Tests\Unit\Xml3176\Support;

use App\Services\Xml3176\Support\MaDvktMatcher;
use Tests\TestCase;

class MaDvktMatcherTest extends TestCase
{
    /** @test */
    public function ma_thuong_giu_nguyen()
    {
        $this->assertSame('02.0261.0319', MaDvktMatcher::maGoc('02.0261.0319'));
    }

    /** @test */
    public function bo_hau_to_sau_gach_duoi()
    {
        $this->assertSame('02.0261.0319', MaDvktMatcher::maGoc('02.0261.0319_TB'));
    }

    /** @test */
    public function bo_tu_dau_gach_duoi_dau_tien()
    {
        $this->assertSame('02.0261.0319', MaDvktMatcher::maGoc('02.0261.0319_TB_XX'));
    }

    /** @test */
    public function cat_khoang_trang()
    {
        $this->assertSame('02.0261.0319', MaDvktMatcher::maGoc('  02.0261.0319_TB  '));
    }

    /** @test */
    public function chuoi_rong_va_null()
    {
        $this->assertSame('', MaDvktMatcher::maGoc(''));
        $this->assertSame('', MaDvktMatcher::maGoc(null));
    }

    /** @test */
    public function ma_bat_dau_bang_gach_duoi_tra_rong()
    {
        $this->assertSame('', MaDvktMatcher::maGoc('_TB'));
    }
}
```

- [ ] **Step 2: Chạy test để xác nhận thất bại**

Run: `./vendor/bin/phpunit tests/Unit/Xml3176/Support/MaDvktMatcherTest.php`
Expected: FAIL — `Class 'App\Services\Xml3176\Support\MaDvktMatcher' not found`.

- [ ] **Step 3: Viết helper**

Tạo `app/Services/Xml3176/Support/MaDvktMatcher.php`:

```php
<?php

namespace App\Services\Xml3176\Support;

/**
 * Tach ma DVKT goc tu ma khai trong XML3.
 *
 * Co so dat them hau to sau dau gach duoi de phan biet bien the cua cung mot dich vu
 * (vi du 02.0261.0319_TB), trong khi danh muc BHXH chi liet ke ma goc. Khong bo hau to
 * thi 11/307 dong do duoc tren du lieu that se lot luoi.
 *
 * Helper thuan - khong cham DB/model/config.
 */
class MaDvktMatcher
{
    public static function maGoc($ma): string
    {
        $ma = trim((string) $ma);
        $pos = strpos($ma, '_');

        return $pos === false ? $ma : trim(substr($ma, 0, $pos));
    }
}
```

- [ ] **Step 4: Chạy test để xác nhận đạt**

Run: `./vendor/bin/phpunit tests/Unit/Xml3176/Support/MaDvktMatcherTest.php`
Expected: PASS (6 test).

- [ ] **Step 5: Commit**

```bash
git add app/Services/Xml3176/Support/MaDvktMatcher.php tests/Unit/Xml3176/Support/MaDvktMatcherTest.php
git commit -m "feat(xml3176): helper tach ma DVKT goc (bo hau to _TB)"
```

---

### Task 3: Hai hàm tra danh mục trong `CommonValidationService`

**Files:**
- Modify: `app/Services/CommonValidationService.php`
- Test: `tests/Unit/Xml3176/DvktCanMaMayTest.php`

**Interfaces:**
- Consumes: `MaDvktMatcher::maGoc($ma): string` (Task 2); bảng `dvkt_can_ma_may` (Task 1).
- Produces:
  - `CommonValidationService::coDanhMucDvktCanMaMay(): bool` — danh mục có dòng đang dùng nào không.
  - `CommonValidationService::isDvktCanMaMay($maDvkt): bool` — mã này (hoặc mã gốc của nó) có trong danh mục đang dùng không.

  Task 4 gọi cả hai.

- [ ] **Step 1: Viết test thất bại**

Tạo `tests/Unit/Xml3176/DvktCanMaMayTest.php`:

```php
<?php

namespace Tests\Unit\Xml3176;

use App\Services\CommonValidationService;
use Illuminate\Support\Facades\DB;
use Tests\Support\Xml3176RuleTestSupport;
use Tests\TestCase;

class DvktCanMaMayTest extends TestCase
{
    use Xml3176RuleTestSupport;

    /** @var CommonValidationService */
    private $sv;

    protected function setUp()
    {
        parent::setUp();

        $this->bootXml3176Sqlite(['2026_09_10_100000_create_dvkt_can_ma_may_table.php']);

        $this->sv = app(CommonValidationService::class);
    }

    private function nap(array $dong)
    {
        DB::table('dvkt_can_ma_may')->insert($dong);
    }

    /** @test */
    public function danh_muc_rong_thi_bao_la_chua_co()
    {
        $this->assertFalse($this->sv->coDanhMucDvktCanMaMay());
    }

    /** @test */
    public function danh_muc_chi_co_dong_nghi_huu_van_coi_la_chua_co()
    {
        // Dong is_active = 0 la du lieu cua lan nap truoc, khong duoc tinh
        $this->nap([['ma_dvkt' => '18.0015.0001', 'ten_dvkt' => 'Sieu am', 'is_active' => 0]]);

        $this->assertFalse($this->sv->coDanhMucDvktCanMaMay());
    }

    /** @test */
    public function co_dong_dang_dung_thi_bao_la_da_co()
    {
        $this->nap([['ma_dvkt' => '18.0015.0001', 'ten_dvkt' => 'Sieu am', 'is_active' => 1]]);

        $this->assertTrue($this->sv->coDanhMucDvktCanMaMay());
    }

    /** @test */
    public function ma_co_trong_danh_muc_thi_dung()
    {
        $this->nap([['ma_dvkt' => '02.0261.0319', 'ten_dvkt' => 'Noi soi', 'is_active' => 1]]);

        $this->assertTrue($this->sv->isDvktCanMaMay('02.0261.0319'));
    }

    /** @test */
    public function ma_co_hau_to_khop_qua_ma_goc()
    {
        $this->nap([['ma_dvkt' => '02.0261.0319', 'ten_dvkt' => 'Noi soi', 'is_active' => 1]]);

        $this->assertTrue($this->sv->isDvktCanMaMay('02.0261.0319_TB'));
    }

    /** @test */
    public function ma_khong_co_trong_danh_muc_thi_sai()
    {
        $this->nap([['ma_dvkt' => '02.0261.0319', 'ten_dvkt' => 'Noi soi', 'is_active' => 1]]);

        $this->assertFalse($this->sv->isDvktCanMaMay('23.0020.1493'));
    }

    /** @test */
    public function dong_nghi_huu_khong_duoc_tinh()
    {
        $this->nap([['ma_dvkt' => '02.0261.0319', 'ten_dvkt' => 'Noi soi', 'is_active' => 0]]);

        $this->assertFalse($this->sv->isDvktCanMaMay('02.0261.0319'));
    }

    /** @test */
    public function ma_rong_thi_sai()
    {
        $this->nap([['ma_dvkt' => '02.0261.0319', 'ten_dvkt' => 'Noi soi', 'is_active' => 1]]);

        $this->assertFalse($this->sv->isDvktCanMaMay(''));
        $this->assertFalse($this->sv->isDvktCanMaMay(null));
    }
}
```

- [ ] **Step 2: Chạy test để xác nhận thất bại**

Run: `./vendor/bin/phpunit tests/Unit/Xml3176/DvktCanMaMayTest.php`
Expected: FAIL — `Call to undefined method ...::coDanhMucDvktCanMaMay()`.

- [ ] **Step 3: Thêm import và hai hàm**

Trong `app/Services/CommonValidationService.php`:

(a) Thêm hai dòng `use` cạnh các `use` model sẵn có ở đầu tệp:

```php
use App\Models\BHYT\DvktCanMaMay;
use App\Services\Xml3176\Support\MaDvktMatcher;
```

(b) Thêm hai hàm sau, đặt NGAY SAU hàm `isAdministrativeUnitWardInProvinceValid` (hàm này kết thúc bằng `->exists();` rồi `}`):

```php
    /**
     * Danh muc DVKT can ma may da duoc nap chua (co dong nao dang dung khong).
     *
     * Quy tac ma may dung ket qua nay de quyet dinh co lui ve cach loc theo nhom cu
     * hay khong - danh muc rong ma van doi theo danh muc thi khong ho so nao bi bao
     * thieu ma may nua, tuc mat sach canh bao ma khong ai biet.
     */
    public function coDanhMucDvktCanMaMay()
    {
        return DvktCanMaMay::where('is_active', true)->exists();
    }

    /**
     * DVKT nay co bat buoc phai gui kem ma may khong.
     *
     * Thu ca ma khai lan ma goc: co so dat hau to sau dau gach duoi de phan biet bien
     * the (02.0261.0319_TB) trong khi danh muc chi liet ke ma goc.
     */
    public function isDvktCanMaMay($maDvkt)
    {
        $ma = trim((string) $maDvkt);

        if ($ma === '') {
            return false;
        }

        $goc = MaDvktMatcher::maGoc($ma);
        $ung = $goc !== '' && $goc !== $ma ? [$ma, $goc] : [$ma];

        return DvktCanMaMay::whereIn('ma_dvkt', $ung)
        ->where('is_active', true)
        ->exists();
    }
```

- [ ] **Step 4: Chạy test để xác nhận đạt**

Run: `./vendor/bin/phpunit tests/Unit/Xml3176/DvktCanMaMayTest.php`
Expected: PASS (8 test).

- [ ] **Step 5: Commit**

```bash
git add app/Services/CommonValidationService.php tests/Unit/Xml3176/DvktCanMaMayTest.php
git commit -m "feat(xml3176): ham tra danh muc DVKT can ma may"
```

---

### Task 4: Đổi căn cứ quy tắc trong `Xml3176Xml3Checker`

**Files:**
- Modify: `app/Services/Xml3176Xml3Checker.php` (khối quanh dòng 258-267)

**Interfaces:**
- Consumes: `CommonValidationService::coDanhMucDvktCanMaMay(): bool` và `CommonValidationService::isDvktCanMaMay($maDvkt): bool` (Task 3).

- [ ] **Step 1: Đổi khối điều kiện**

Trong `app/Services/Xml3176Xml3Checker.php`, tìm khối sau (nguyên văn):

```php
        // Bổ sung kiểm tra bắt buộc phải có mã máy đối với những nhóm
        if (in_array($data->ma_nhom, config('xml3176.xml3.service_groups_requiring_machine')) && empty($data->ma_may)) {
            $errorCode = $this->generateErrorCode('INFO_ERROR_GROUP_CODE_MA_MAY');
            $errors->push((object)[
                'error_code' => $errorCode,
                'error_name' => 'Thiếu mã máy',
                'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                'description' => 'Mã máy không được để trống đối với DVKT: ' . $this->serviceDisplay
            ]);
        }
```

Thay bằng:

```php
        // Bat buoc co ma may: can cu DANH MUC DVKT do BHXH ban hanh, khong con loc theo nhom.
        //
        // Danh muc chua nap -> LUI ve cach loc theo nhom cu. Neu doi thang theo danh muc ma
        // bang con rong thi khong ho so nao bi bao thieu ma may nua - mat sach canh bao ma
        // khong co dau hieu gi. Nap danh muc xong thi tu chuyen sang quy tac moi.
        if ($this->commonValidationService->coDanhMucDvktCanMaMay()) {
            $canMaMay = $this->commonValidationService->isDvktCanMaMay($data->ma_dich_vu);
        } else {
            $canMaMay = in_array($data->ma_nhom, config('xml3176.xml3.service_groups_requiring_machine'));
        }

        if ($canMaMay && empty($data->ma_may)) {
            $errorCode = $this->generateErrorCode('INFO_ERROR_GROUP_CODE_MA_MAY');
            $errors->push((object)[
                'error_code' => $errorCode,
                'error_name' => 'Thiếu mã máy',
                'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                'description' => 'Mã máy không được để trống đối với DVKT: ' . $this->serviceDisplay
                    . ' (mã ' . $data->ma_dich_vu . ')'
            ]);
        }
```

- [ ] **Step 2: Kiểm tra cú pháp**

Run: `php -l app/Services/Xml3176Xml3Checker.php`
Expected: `No syntax errors detected`.

- [ ] **Step 3: Chạy hồi quy**

Run: `./vendor/bin/phpunit tests/Unit/Xml3176 tests/Unit/Import`
Expected: PASS toàn bộ (>= 215 test: 201 cũ + 6 của Task 2 + 8 của Task 3).

- [ ] **Step 4: Xác nhận nhánh lùi đang hoạt động trên CSDL thật**

Bảng `dvkt_can_ma_may` vừa tạo còn rỗng, nên quy tắc phải chạy đúng như cũ.

Run: `php -r "require 'vendor/autoload.php'; \$a=require 'bootstrap/app.php'; \$a->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap(); \$sv=app(App\Services\CommonValidationService::class); var_dump(\$sv->coDanhMucDvktCanMaMay());"`
Expected: `bool(false)` — tức đang ở nhánh lùi, hành vi giữ nguyên cho tới khi nạp danh mục.

- [ ] **Step 5: Commit**

```bash
git add app/Services/Xml3176Xml3Checker.php
git commit -m "feat(xml3176): quy tac ma may can cu danh muc DVKT, lui ve nhom khi chua nap"
```

---

## Self-Review

**1. Spec coverage:**
- §4.1 bảng `dvkt_can_ma_may` (kèm `is_active` bắt buộc) → Task 1 Step 1-3.
- §4.2 sáu điểm khai báo → Task 1: model (Step 3), `danh_muc_bhyt` (Step 4), `catalog_import_mapping` (Step 5), `bangCua`/`GHI_THEO_LO`/`LAM_MOI_TRON_BO` (Step 6).
- §4.3 đổi quy tắc + nhánh lùi + khớp mã gốc + giữ `error_code` → Task 4 Step 1 (nhánh lùi, error_code) và Task 2/3 (mã gốc).
- §4.4 tách phần thuần khỏi phần chạm CSDL → Task 2 (`MaDvktMatcher`) và Task 3 (hai hàm tra) — **có sai khác về nơi đặt, đã nêu rõ ở mục "Sai khác có chủ ý"**.
- §6 guard (danh mục rỗng, mã rỗng, chỉ tra `is_active=1`) → Task 3 Step 3 + Task 4 Step 1.
- §7 kiểm thử: helper thuần → Task 2; sqlite hai trạng thái danh mục → Task 3; hai test tự động soi cấu hình → Task 1 Step 7; hồi quy → Task 4 Step 3.
- §8 thứ tự triển khai → tài liệu vận hành, không sinh mã. **Không có seeder** vì `error_code` giữ nguyên — đúng như spec ghi.

**2. Placeholder scan:** không có TBD/TODO; mọi bước có mã hoặc lệnh cụ thể kèm kết quả mong đợi.

**3. Type consistency:** `MaDvktMatcher::maGoc($ma): string` khai ở Task 2, gọi đúng tên ở Task 3. `coDanhMucDvktCanMaMay()` / `isDvktCanMaMay($maDvkt)` khai ở Task 3, gọi đúng tên và đúng số tham số ở Task 4. Tên bảng `dvkt_can_ma_may` thống nhất giữa migration, model `$table`, `danh_muc_bhyt.bang`, và `bangCua()`. Tên lớp migration `CreateDvktCanMaMayTable` khớp quy ước suy tên từ tên tệp mà `bootXml3176Sqlite` dùng ở Task 3.
