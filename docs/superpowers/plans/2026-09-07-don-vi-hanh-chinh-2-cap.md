# Đơn vị hành chính 2 cấp Tỉnh/Xã — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Chuyển quy tắc kiểm cư trú của XML3176 sang mô hình 2 cấp Tỉnh/Xã và thêm quy tắc "xã thuộc tỉnh".

**Architecture:** Giữ nguyên bảng phẳng `administrative_units` và khung nhập danh mục sẵn có; nới hai cột huyện thành nullable; dùng cột `is_active` (đã có, chưa dùng) làm ranh giới giữa danh mục cũ và mới. `CommonValidationService` đã lọc sẵn `is_active` nên tầng tra cứu không phải sửa. Một lệnh artisan một lần thực hiện nghỉ hưu → nạp → kích hoạt lại.

**Tech Stack:** Laravel 5.5, PHP 7.4, PHPUnit 6.5, Maatwebsite Excel (đọc theo lô).

**Spec:** `docs/superpowers/specs/2026-09-07-don-vi-hanh-chinh-2-cap-design.md`

## Global Constraints

- PHP 7.4 / Laravel 5.5. PHPUnit 6.5: `setUp()` KHÔNG có `:void`; dùng `assertTrue`/`assertFalse`/`assertSame`.
- **CẤM `RefreshDatabase`** và mọi thao tác xoá/migrate CSDL dev `qlbv`. Test cần CSDL phải dùng sqlite in-memory qua trait `Tests\Support\Xml3176RuleTestSupport::bootXml3176Sqlite`.
- **CẤM dùng `->change()` trong migration**: dự án KHÔNG cài `doctrine/dbal`. Đổi kiểu cột phải dùng `DB::statement('ALTER TABLE ... MODIFY ...')` — theo khuôn `database/migrations/2024_12_10_221055_update_quy_trinh_nullable_in_service_catalogs_table.php`.
- Error object: `(object)['error_code','error_name','critical_error'=>$this->xmlErrorService->getCriticalErrorStatus($code),'description']`. Prefix của `Xml3176Xml1Checker` là `XML1_`.
- Quy tắc mới seed ở trạng thái **`is_check = false`, `critical_error = false`**.
- **KHÔNG đụng** `mahuyen_cu_tru`, **KHÔNG đụng** module QĐ130.
- Chạy test theo thư mục cụ thể (`tests/Unit/Xml3176`), KHÔNG chạy toàn bộ `phpunit`.

---

## File Structure

- Create: `database/migrations/2026_09_07_100000_noi_cot_huyen_nullable_administrative_units.php`
- Modify: `config/catalog_import_mapping.php` — khoá `administrative_unit`
- Modify: `app/Services/CommonValidationService.php` — thêm 1 hàm
- Create: `tests/Unit/Xml3176/HanhChinhWardInProvinceTest.php`
- Modify: `app/Services/Xml3176Xml1Checker.php` — thêm khối kiểm tra
- Create: `database/seeds/Xml3176ErrorCatalogHanhChinhSeeder.php`
- Create: `app/Console/Commands/HanhChinhChuyen2Cap.php`

---

### Task 1: Nới schema và mapping để nhận danh mục 2 cấp

**Files:**
- Create: `database/migrations/2026_09_07_100000_noi_cot_huyen_nullable_administrative_units.php`
- Modify: `config/catalog_import_mapping.php` (khoá `administrative_unit`, dòng ~191-203)

**Interfaces:**
- Produces: cột `district_code`/`district_name` nullable; mapping nhận được tệp không có cột huyện. Task 5 dựa vào cả hai.

- [ ] **Step 1: Tạo migration**

Tạo `database/migrations/2026_09_07_100000_noi_cot_huyen_nullable_administrative_units.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Bo cap huyen: danh muc 2 cap khong con ma/ten huyen, nhung KHONG xoa cot -
 * chung con giu du lieu cua cac dong da nghi huu (is_active = 0).
 *
 * Dung DB::statement chu khong dung ->change(): du an KHONG cai doctrine/dbal.
 */
class NoiCotHuyenNullableAdministrativeUnits extends Migration
{
    public function up()
    {
        DB::statement('ALTER TABLE administrative_units MODIFY district_code VARCHAR(10) NULL');
        DB::statement('ALTER TABLE administrative_units MODIFY district_name VARCHAR(255) NULL');
    }

    public function down()
    {
        DB::statement('ALTER TABLE administrative_units MODIFY district_code VARCHAR(10) NOT NULL');
        DB::statement('ALTER TABLE administrative_units MODIFY district_name VARCHAR(255) NOT NULL');
    }
}
```

- [ ] **Step 2: Chạy migration**

Run: `php artisan migrate`
Expected: chạy trót lọt, in tên migration `NoiCotHuyenNullableAdministrativeUnits`.

- [ ] **Step 3: Xác nhận cột đã nullable**

Run: `php -r "require 'vendor/autoload.php'; \$a=require 'bootstrap/app.php'; \$a->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap(); foreach (DB::select('SHOW COLUMNS FROM administrative_units') as \$c) { if (in_array(\$c->Field,['district_code','district_name'])) echo \$c->Field.' Null='.\$c->Null.PHP_EOL; }"`
Expected: cả hai dòng in `Null=YES`.

- [ ] **Step 4: Sửa mapping**

Trong `config/catalog_import_mapping.php`, khoá `administrative_unit`, thay hai dòng sau:

Dòng `detect_keys` cũ:
```php
        'detect_keys' => ['Tỉnh Thành Phố', 'Mã TP', 'Quận Huyện'],
```
thành:
```php
        // Bo 'Quan Huyen': danh muc 2 cap khong con cot nay. Nhan dien bang cot chac chan
        // co o CA tep 3 cap cu lan tep 2 cap moi.
        'detect_keys' => ['Tỉnh Thành Phố', 'Mã TP', 'Phường Xã'],
```

Dòng `required_fields` cũ:
```php
        'required_fields' => ['province_name', 'province_code', 'district_name', 'district_code', 'commune_name', 'commune_code'],
```
thành:
```php
        'required_fields' => ['province_name', 'province_code', 'commune_name', 'commune_code'],
```

**KHÔNG sửa** khối `mapping` (giữ hai dòng district để tệp 3 cấp cũ vẫn nạp được) và **KHÔNG sửa** `unique_keys`.

- [ ] **Step 5: Xác nhận config nạp đúng**

Run: `php -r "\$c=(require 'config/catalog_import_mapping.php')['administrative_unit']; print_r(\$c['detect_keys']); print_r(\$c['required_fields']);"`
Expected: `detect_keys` có 3 phần tử và KHÔNG chứa `Quận Huyện`; `required_fields` có 4 phần tử và KHÔNG chứa `district_name`/`district_code`.

- [ ] **Step 6: Commit**

```bash
git add database/migrations/2026_09_07_100000_noi_cot_huyen_nullable_administrative_units.php config/catalog_import_mapping.php
git commit -m "feat(hanh-chinh): noi cot huyen nullable va mapping nhan tep 2 cap"
```

---

### Task 2: Hàm tra "xã thuộc tỉnh"

**Files:**
- Modify: `app/Services/CommonValidationService.php` (thêm hàm sau `isAdministrativeUnitWardInDistrictValid`)
- Test: `tests/Unit/Xml3176/HanhChinhWardInProvinceTest.php`

**Interfaces:**
- Produces: `CommonValidationService::isAdministrativeUnitWardInProvinceValid($province_code, $commune_code): bool` — Task 3 gọi.

- [ ] **Step 1: Viết test thất bại**

Tạo `tests/Unit/Xml3176/HanhChinhWardInProvinceTest.php`:

```php
<?php

namespace Tests\Unit\Xml3176;

use App\Services\CommonValidationService;
use Illuminate\Support\Facades\DB;
use Tests\Support\Xml3176RuleTestSupport;
use Tests\TestCase;

class HanhChinhWardInProvinceTest extends TestCase
{
    use Xml3176RuleTestSupport;

    /** @var CommonValidationService */
    private $sv;

    protected function setUp()
    {
        parent::setUp();

        $this->bootXml3176Sqlite(['2024_07_07_221139_create_administrative_units_table.php']);

        // Migration goc khai district NOT NULL nen phai dien gia tri; cot huyen khong
        // tham gia phep kiem tra nay.
        DB::table('administrative_units')->insert([
            ['province_code' => '01', 'province_name' => 'Hà Nội', 'district_code' => '-',
             'district_name' => '-', 'commune_code' => '00001', 'commune_name' => 'Phường A',
             'is_active' => 1],
            ['province_code' => '96', 'province_name' => 'Cà Mau', 'district_code' => '-',
             'district_name' => '-', 'commune_code' => '99999', 'commune_name' => 'Xã B',
             'is_active' => 1],
            ['province_code' => '01', 'province_name' => 'Hà Nội', 'district_code' => '-',
             'district_name' => '-', 'commune_code' => '00777', 'commune_name' => 'Phường cũ',
             'is_active' => 0],
        ]);

        $this->sv = app(CommonValidationService::class);
    }

    /** @test */
    public function dung_khi_xa_thuoc_tinh_va_dang_hoat_dong()
    {
        $this->assertTrue($this->sv->isAdministrativeUnitWardInProvinceValid('01', '00001'));
    }

    /** @test */
    public function sai_khi_xa_thuoc_tinh_khac()
    {
        $this->assertFalse($this->sv->isAdministrativeUnitWardInProvinceValid('01', '99999'));
    }

    /** @test */
    public function sai_khi_dong_da_nghi_huu()
    {
        // Dong dung tinh dung xa nhung is_active = 0 -> coi nhu khong con
        $this->assertFalse($this->sv->isAdministrativeUnitWardInProvinceValid('01', '00777'));
    }

    /** @test */
    public function sai_khi_ma_rong()
    {
        $this->assertFalse($this->sv->isAdministrativeUnitWardInProvinceValid('', '00001'));
        $this->assertFalse($this->sv->isAdministrativeUnitWardInProvinceValid('01', ''));
    }
}
```

- [ ] **Step 2: Chạy test để xác nhận thất bại**

Run: `./vendor/bin/phpunit tests/Unit/Xml3176/HanhChinhWardInProvinceTest.php`
Expected: FAIL — `Call to undefined method ...::isAdministrativeUnitWardInProvinceValid()`.

- [ ] **Step 3: Thêm hàm**

Trong `app/Services/CommonValidationService.php`, thêm NGAY SAU hàm `isAdministrativeUnitWardInDistrictValid` (hàm này kết thúc bằng `->exists();` rồi `}`):

```php
    /**
     * Xa co thuoc tinh khong. Sau khi bo cap huyen day la quan he long nhau duy nhat
     * con lai giua hai cap.
     */
    public function isAdministrativeUnitWardInProvinceValid($province_code, $commune_code)
    {
        return AdministrativeUnit::where('province_code', $province_code)
        ->where('commune_code', $commune_code)
        ->where('is_active', true)
        ->exists();
    }
```

- [ ] **Step 4: Chạy test để xác nhận đạt**

Run: `./vendor/bin/phpunit tests/Unit/Xml3176/HanhChinhWardInProvinceTest.php`
Expected: PASS (4 test).

- [ ] **Step 5: Commit**

```bash
git add app/Services/CommonValidationService.php tests/Unit/Xml3176/HanhChinhWardInProvinceTest.php
git commit -m "feat(hanh-chinh): them ham tra xa thuoc tinh"
```

---

### Task 3: Quy tắc "xã không thuộc tỉnh" trong checker XML1

**Files:**
- Modify: `app/Services/Xml3176Xml1Checker.php` (thêm khối ngay sau khối kiểm `maxa_cu_tru`, kết thúc quanh dòng 215)

**Interfaces:**
- Consumes: `CommonValidationService::isAdministrativeUnitWardInProvinceValid` (Task 2); sẵn có `isAdministrativeUnitProvinceValid`, `isAdministrativeUnitCommuneValid`.
- Produces: error_code `XML1_ADMIN_INFO_ERROR_MAXA_NOT_IN_MATINH` — Task 4 seeder khai cùng chuỗi.

- [ ] **Step 1: Thêm khối kiểm tra**

Trong `app/Services/Xml3176Xml1Checker.php`, tìm khối kiểm `maxa_cu_tru`. Khối đó kết thúc bằng đúng đoạn sau:

```php
            $wardExists = $this->commonValidationService->isAdministrativeUnitCommuneValid($data->maxa_cu_tru);
            if (!$wardExists) {
                $errorCode = $this->generateErrorCode('ADMIN_INFO_ERROR_MAXA_CU_TRU_NOT_FOUND');
                $errors->push((object)[
                    'error_code' => $errorCode,
                    'error_name' => 'Mã phường xã không tồn tại',
                    'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                    'description' => 'Mã phường xã không tồn tại trong danh mục: ' . $data->maxa_cu_tru
                ]);
            }
        }
```

Thêm NGAY SAU dấu `}` cuối cùng của đoạn trên:

```php

        // Xa phai thuoc tinh - quan he long nhau duy nhat con lai sau khi bo cap huyen.
        // CHI chay khi ca hai ma da hop le rieng le: neu mot ma sai thi loi do da duoc bao
        // roi, bao them loi long nhau chi la nhieu tren cung mot nguyen nhan.
        if (!empty($data->matinh_cu_tru) && !empty($data->maxa_cu_tru)
            && $this->commonValidationService->isAdministrativeUnitProvinceValid($data->matinh_cu_tru)
            && $this->commonValidationService->isAdministrativeUnitCommuneValid($data->maxa_cu_tru)
            && !$this->commonValidationService->isAdministrativeUnitWardInProvinceValid($data->matinh_cu_tru, $data->maxa_cu_tru)) {
            $errorCode = $this->generateErrorCode('ADMIN_INFO_ERROR_MAXA_NOT_IN_MATINH');
            $errors->push((object)[
                'error_code' => $errorCode,
                'error_name' => 'Mã xã không thuộc tỉnh cư trú',
                'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                'description' => 'Mã xã ' . $data->maxa_cu_tru . ' không thuộc tỉnh ' . $data->matinh_cu_tru
            ]);
        }
```

- [ ] **Step 2: Kiểm tra cú pháp**

Run: `php -l app/Services/Xml3176Xml1Checker.php`
Expected: `No syntax errors detected`.

- [ ] **Step 3: Chạy hồi quy**

Run: `./vendor/bin/phpunit tests/Unit/Xml3176`
Expected: PASS toàn bộ (>= 201 test: 197 cũ + 4 của Task 2).

- [ ] **Step 4: Commit**

```bash
git add app/Services/Xml3176Xml1Checker.php
git commit -m "feat(xml3176): quy tac ma xa khong thuoc tinh cu tru"
```

---

### Task 4: Seeder mã lỗi (tắt sẵn)

**Files:**
- Create: `database/seeds/Xml3176ErrorCatalogHanhChinhSeeder.php`

**Interfaces:**
- Consumes: error_code `XML1_ADMIN_INFO_ERROR_MAXA_NOT_IN_MATINH` (Task 3).

- [ ] **Step 1: Tạo seeder**

Tạo `database/seeds/Xml3176ErrorCatalogHanhChinhSeeder.php`:

```php
<?php

use Illuminate\Database\Seeder;
use App\Models\BHYT\Xml3176ErrorCatalog;

/**
 * Nap ma loi "ma xa khong thuoc tinh cu tru".
 *
 * is_check = false CO Y: quy tac chi dung khi danh muc da la 2 cap thuan. Bat luc danh
 * muc con cu hoac con lan lon se bao oan hang loat. Nguoi van hanh bat bang o tich
 * "Co kiem tra" o man Danh muc ma loi SAU khi da nap danh muc moi va ra thu mot lo.
 *
 * critical_error = false: co quan bao hiem xep loi ma tinh/xa vao loai BAO LOI, khong
 * phai khoan tru tien - khong chan xuat ho so.
 *
 * Idempotent (updateOrCreate) - chay lai an toan.
 */
class Xml3176ErrorCatalogHanhChinhSeeder extends Seeder
{
    public function run()
    {
        Xml3176ErrorCatalog::updateOrCreate(
            ['xml' => 'XML1', 'error_code' => 'XML1_ADMIN_INFO_ERROR_MAXA_NOT_IN_MATINH'],
            [
                'error_name'     => 'Mã xã không thuộc tỉnh cư trú',
                'description'    => 'Mã xã cư trú không thuộc tỉnh cư trú đã khai trong danh mục đơn vị hành chính',
                'critical_error' => false,
                'is_check'       => false,
            ]
        );
    }
}
```

- [ ] **Step 2: Kiểm tra cú pháp**

Run: `php -l database/seeds/Xml3176ErrorCatalogHanhChinhSeeder.php`
Expected: `No syntax errors detected`.

- [ ] **Step 3: Commit**

```bash
git add database/seeds/Xml3176ErrorCatalogHanhChinhSeeder.php
git commit -m "feat(xml3176): seeder ma loi xa khong thuoc tinh (tat san)"
```

---

### Task 5: Lệnh chuyển danh mục sang 2 cấp

**Files:**
- Create: `app/Console/Commands/HanhChinhChuyen2Cap.php`

**Interfaces:**
- Consumes: cột huyện nullable + mapping đã sửa (Task 1); `CatalogImportService::import($filePath, $maCskcb = null)` sẵn có; `App\Imports\CatalogChunkImport` sẵn có với callback `function ($rows, $dongDau, $laLoDau)`.

- [ ] **Step 1: Tạo lệnh**

Tạo `app/Console/Commands/HanhChinhChuyen2Cap.php`:

```php
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\CatalogChunkImport;
use App\Services\CatalogImportService;

/**
 * Chuyen danh muc don vi hanh chinh sang 2 cap Tinh/Xa.
 *
 * THU TU la van de dung/sai chu khong phai phong cach: dong trung ma xa giua danh muc cu
 * va moi se bi CAP NHAT chu khong chen moi, ma is_active KHONG nam trong mapping nen no
 * giu nguyen gia tri 0 vua dat. Khong co buoc kich hoat lai thi dung nhung xa trung ma se
 * nam im o trang thai nghi huu va bi bao "khong ton tai".
 *
 * Doc ma xa THEO LO (CatalogChunkImport) chu khong dung Excel::toCollection: tep 10.000
 * dong tung lam dinh bo nho 208 MB tren may chu 128 MB.
 */
class HanhChinhChuyen2Cap extends Command
{
    protected $signature = 'hanh-chinh:chuyen-2-cap {tep : Duong dan tep Excel danh muc 2 cap}
                            {--force : Khong hoi xac nhan}';

    protected $description = 'Chuyen danh muc don vi hanh chinh sang 2 cap Tinh/Xa (nghi huu dong cu, nap tep moi)';

    public function handle(CatalogImportService $importService)
    {
        $tep = $this->argument('tep');

        if (!is_file($tep)) {
            $this->error('Khong tim thay tep: ' . $tep);
            return 1;
        }

        $maXa = $this->docMaXa($tep);
        if (empty($maXa)) {
            $this->error('Khong doc duoc ma xa nao tu tep. Kiem tra tep co cot "Ma PX" khong.');
            return 1;
        }

        $truoc = DB::table('administrative_units')->count();
        $this->info('Dang co trong bang : ' . $truoc . ' dong');
        $this->info('Ma xa trong tep    : ' . count($maXa));

        if (!$this->option('force') && !$this->confirm('Nghi huu toan bo dong hien co roi nap tep moi?')) {
            $this->warn('Da huy, khong thay doi gi.');
            return 0;
        }

        DB::transaction(function () use ($importService, $tep, $maXa) {
            // 1. Nghi huu toan bo
            DB::table('administrative_units')->update(['is_active' => 0]);

            // 2. Nap tep moi (upsert theo commune_code)
            $importService->import($tep);

            // 3. Kich hoat lai dung cac ma xa co trong tep, va xoa du lieu huyen con sot
            //    o cac dong bi cap nhat (tep 2 cap khong mang cot huyen nen import khong ghi de).
            foreach (array_chunk($maXa, 1000) as $lo) {
                DB::table('administrative_units')
                    ->whereIn('commune_code', $lo)
                    ->update(['is_active' => 1, 'district_code' => null, 'district_name' => null]);
            }
        });

        $sauTong   = DB::table('administrative_units')->count();
        $sauActive = DB::table('administrative_units')->where('is_active', 1)->count();
        $soTinh    = DB::table('administrative_units')->where('is_active', 1)->distinct()->count('province_code');

        $this->info('---');
        $this->info('Tong dong sau     : ' . $sauTong);
        $this->info('Dang hoat dong    : ' . $sauActive);
        $this->info('So tinh hoat dong : ' . $soTinh);
        $this->info('Da nghi huu       : ' . ($sauTong - $sauActive));

        return 0;
    }

    /**
     * Doc tap hop ma xa tu tep, theo lo. Tim cot bang chinh danh sach bi danh trong
     * mapping - mot nguon su that, khong khai lai o day.
     *
     * @return string[]
     */
    private function docMaXa($tep): array
    {
        $biDanh = (array) config('catalog_import_mapping.administrative_unit.mapping.commune_code', []);
        $viTri = null;
        $ma = [];

        $doc = new CatalogChunkImport(function ($rows, $dongDau, $laLoDau) use (&$viTri, &$ma, $biDanh) {
            if ($laLoDau) {
                foreach ($rows->first() as $i => $ten) {
                    if (in_array(trim((string) $ten), $biDanh, true)) {
                        $viTri = $i;
                        break;
                    }
                }
                $rows = $rows->slice(1);
            }

            if ($viTri === null) {
                return;
            }

            foreach ($rows as $dong) {
                $gt = trim((string) ($dong[$viTri] ?? ''));
                if ($gt !== '') {
                    $ma[$gt] = true;
                }
            }
        });

        Excel::import($doc, $tep);

        return array_keys($ma);
    }
}
```

- [ ] **Step 2: Kiểm tra cú pháp**

Run: `php -l app/Console/Commands/HanhChinhChuyen2Cap.php`
Expected: `No syntax errors detected`.

- [ ] **Step 3: Xác nhận lệnh đã đăng ký**

Run: `php artisan list | grep hanh-chinh`
Expected: in ra dòng `hanh-chinh:chuyen-2-cap`.

- [ ] **Step 4: Xác nhận lệnh chặn tệp không tồn tại**

Run: `php artisan hanh-chinh:chuyen-2-cap khong-co-that.xlsx`
Expected: in `Khong tim thay tep: khong-co-that.xlsx`, thoát mã 1, **không** đụng vào CSDL.

- [ ] **Step 5: Chạy hồi quy**

Run: `./vendor/bin/phpunit tests/Unit/Xml3176`
Expected: PASS toàn bộ (>= 201 test).

- [ ] **Step 6: Commit**

```bash
git add app/Console/Commands/HanhChinhChuyen2Cap.php
git commit -m "feat(hanh-chinh): lenh chuyen danh muc sang 2 cap Tinh/Xa"
```

---

## Self-Review

**1. Spec coverage:**
- §4.1 nới cột huyện → Task 1 Step 1-3.
- §4.2 mapping (detect_keys, required_fields, giữ mapping và unique_keys) → Task 1 Step 4-5.
- §4.3 lệnh chuyển đổi (6 bước, đúng thứ tự, một giao dịch, xác nhận) → Task 5.
- §4.4 hàm tra + khối kiểm trong checker → Task 2, Task 3.
- §4.5 seeder `is_check=false`, `critical_error=false` → Task 4.
- §6 guard (rỗng / không tồn tại / quy tắc tắt sẵn) → điều kiện trong Task 3 Step 1 + `is_check=false` ở Task 4.
- §7 kiểm thử: unit 4 ca → Task 2; hồi quy → Task 3 Step 3 và Task 5 Step 5. Kiểm chứng lệnh trên bản sao dữ liệu là **việc vận hành thủ công**, đã ghi ở §7 spec và §8 thứ tự triển khai — không phải bước tự động hoá trong plan này.
- §8 thứ tự triển khai → tài liệu vận hành, không sinh mã.

**2. Placeholder scan:** không có TBD/TODO; mọi bước có mã hoặc lệnh cụ thể kèm kết quả mong đợi.

**3. Type consistency:** `isAdministrativeUnitWardInProvinceValid($province_code, $commune_code)` khai ở Task 2, gọi đúng tên và đúng thứ tự tham số ở Task 3. Error_code `XML1_ADMIN_INFO_ERROR_MAXA_NOT_IN_MATINH` khớp giữa Task 3 (qua `generateErrorCode('ADMIN_INFO_ERROR_MAXA_NOT_IN_MATINH')` với prefix `XML1_`) và Task 4. Callback `function ($rows, $dongDau, $laLoDau)` ở Task 5 khớp chữ ký `CatalogChunkImport` mà `CatalogImportService::import` đang dùng. Tên migration class `NoiCotHuyenNullableAdministrativeUnits` khớp quy ước suy tên từ tên tệp.
