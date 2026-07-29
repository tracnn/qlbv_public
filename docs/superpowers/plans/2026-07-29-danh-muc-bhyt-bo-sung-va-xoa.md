# Danh mục BHYT: bổ sung 3 màn, xem chi tiết, xoá toàn bộ — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Bổ sung 3 màn quản lý danh mục BHYT còn thiếu, thêm cột MA_CSKCB, thêm màn xem chi tiết chỉ đọc cho cả 11 bộ, và thêm chức năng xoá toàn bộ một danh mục cho superadministrator.

**Architecture:** Một sổ đăng ký `config/danh_muc_bhyt.php` làm nguồn duy nhất cho "11 bộ danh mục BHYT là những bộ nào". Ba tính năng mới đều đọc từ đó. Phần dễ sai nhất — lọc theo cơ sở khi xoá, và dựng nhãn trường cho màn chi tiết — tách thành hàm thuần để kiểm thử không cần CSDL. Tám màn quản lý đã có giữ nguyên, không viết lại.

**Tech Stack:** Laravel 5.5.50, PHP 7.4, PHPUnit 6.5, Blade, AdminLTE, jQuery DataTables (server-side), Yajra Datatables.

## Global Constraints

- Cổng kiểm thử: `vendor/bin/phpunit --testsuite Unit`. **KHÔNG** chạy `tests/Feature` — đỏ sẵn vì lý do môi trường, không liên quan.
- Comment trong code PHP viết tiếng Việt **không dấu**; chuỗi hiển thị cho người dùng (nhãn menu, tiêu đề, thông báo) viết **có dấu**.
- Sổ đăng ký có đúng **11** khoá, trùng khoá của `config/catalog_import_mapping.php`: `medicine`, `medical_supply`, `service`, `icd10`, `icd_yhct`, `medical_staff`, `department_bed`, `equipment`, `administrative_unit`, `medical_organization`, `job_categories`.
- `theo_co_so = true` **chỉ** với `medicine`, `medical_supply`, `service`. **Không** suy ra từ sự tồn tại của cột `ma_cskcb` — `medical_organizations` cũng có cột đó nhưng mang nghĩa khác (khoá của chính danh mục).
- Ba màn mới và màn chi tiết dùng `checkrole:category-manager`. Hai route xoá dùng `checkrole:superadministrator`.
- Không sửa `app/Http/Middleware/CheckRole.php` hay `app/Providers/AppServiceProvider.php`.
- Không cho sửa dữ liệu danh mục trên giao diện — màn chi tiết chỉ đọc.
- Không đụng hai danh mục mã lỗi XML ("DM lỗi Xml 4750", "DM lỗi Xml 3176").
- Sau khi sửa `config/adminlte.php` phải chạy `php artisan config:clear`.

## Cấu trúc tệp

| Tệp | Trách nhiệm |
| --- | --- |
| `config/danh_muc_bhyt.php` (tạo) | Sổ đăng ký 11 bộ: tên, model, bảng, cờ theo cơ sở |
| `app/Services/Category/XoaDanhMuc.php` (tạo) | Hàm thuần dựng điều kiện xoá từ `(loai, maCskcb)` |
| `app/Services/Category/NhanTruong.php` (tạo) | Hàm thuần dựng nhãn hiển thị cho tên cột |
| `app/Http/Controllers/Category/CategoryBHYTController.php` (sửa) | 3 cặp method danh sách, 1 method chi tiết, 2 method xoá |
| `resources/views/category/bhyt/administrative_unit.blade.php` (tạo) | Màn DM Đơn vị hành chính |
| `resources/views/category/bhyt/medical_organization.blade.php` (tạo) | Màn DM Cơ sở KCB |
| `resources/views/category/bhyt/job_category.blade.php` (tạo) | Màn DM Nghề nghiệp |
| `resources/views/category/bhyt/_chi_tiet.blade.php` (tạo) | Modal chi tiết dùng chung + JS |
| `routes/web.php` (sửa) | 6 route danh sách/fetch, 1 route chi tiết, 2 route xoá |
| `config/adminlte.php` (sửa) | 3 mục menu mới |

---

### Task 1: Sổ đăng ký 11 bộ danh mục

**Files:**
- Create: `config/danh_muc_bhyt.php`
- Test: `tests/Unit/SoDangKyDanhMucTest.php`

**Interfaces:**
- Consumes: không có gì từ task khác.
- Produces: `config('danh_muc_bhyt')` — mảng 11 khoá, mỗi khoá là mảng có 4 trường `ten` (string), `model` (tên lớp đầy đủ), `bang` (string), `theo_co_so` (bool). Task 2, 3, 5 đều đọc từ đây.

- [ ] **Step 1: Viết test đỏ**

Tạo `tests/Unit/SoDangKyDanhMucTest.php`:

```php
<?php

namespace Tests\Unit;

use DB;
use Tests\TestCase;

class SoDangKyDanhMucTest extends TestCase
{
    protected function so()
    {
        return config('danh_muc_bhyt');
    }

    /** @test */
    public function du_11_bo_va_trung_khoa_voi_cau_hinh_nhap_khau()
    {
        $so = $this->so();

        $this->assertCount(11, $so);

        $khoaSo = array_keys($so);
        $khoaNhap = array_keys(config('catalog_import_mapping'));

        sort($khoaSo);
        sort($khoaNhap);

        $this->assertSame($khoaNhap, $khoaSo,
            'So dang ky phai trung khoa voi catalog_import_mapping');
    }

    /** @test */
    public function moi_bang_deu_ton_tai_va_khop_model()
    {
        foreach ($this->so() as $loai => $x) {
            $this->assertArrayHasKey('ten', $x, "Loai $loai thieu 'ten'");
            $this->assertArrayHasKey('bang', $x, "Loai $loai thieu 'bang'");
            $this->assertArrayHasKey('model', $x, "Loai $loai thieu 'model'");

            $this->assertNotEmpty(
                DB::select('SHOW TABLES LIKE ?', [$x['bang']]),
                "Bang {$x['bang']} cua loai $loai khong ton tai"
            );

            $this->assertTrue(class_exists($x['model']), "Model {$x['model']} khong ton tai");

            $m = new $x['model'];

            $this->assertSame($x['bang'], $m->getTable(),
                "Model {$x['model']} tro toi bang khac voi khai bao");
        }
    }

    /**
     * Chot cung ba loai, KHONG suy ra tu cot ma_cskcb.
     *
     * medical_organizations CUNG co cot ma_cskcb nhung do la KHOA CUA CHINH DANH MUC
     * (ma cua tung co so trong danh sach), khong phai cot phan tach theo co so. Suy ra
     * tu su ton tai cua cot se danh dau nham no.
     */
    /** @test */
    public function chi_dung_ba_loai_theo_co_so()
    {
        $co = [];

        foreach ($this->so() as $loai => $x) {
            $this->assertArrayHasKey('theo_co_so', $x, "Loai $loai thieu 'theo_co_so'");
            $this->assertInternalType('bool', $x['theo_co_so'], "Loai $loai: theo_co_so phai la bool");

            if ($x['theo_co_so']) {
                $co[] = $loai;
            }
        }

        sort($co);

        $this->assertSame(['medical_supply', 'medicine', 'service'], $co);
    }
}
```

- [ ] **Step 2: Chạy test, xác nhận đỏ**

```bash
vendor/bin/phpunit tests/Unit/SoDangKyDanhMucTest.php
```

Kỳ vọng: cả 3 test FAIL vì `config('danh_muc_bhyt')` trả `null`.

- [ ] **Step 3: Tạo sổ đăng ký**

Tạo `config/danh_muc_bhyt.php`:

```php
<?php

/**
 * So dang ky 11 bo danh muc BHYT — nguon DUY NHAT cho cau hoi "11 bo la nhung bo nao".
 *
 * Truoc day thong tin nay nam rai o ba noi: config/catalog_import_mapping.php (11 khoa),
 * menu trong config/adminlte.php (8 muc), va CategoryBHYTController (8 cap method).
 *
 * Khoa phai TRUNG khoa cua catalog_import_mapping.
 *
 * theo_co_so: danh muc co tach theo co so KCB khong. CHI dung voi medicine,
 * medical_supply, service. Luu y bang medical_organizations CUNG co cot ma_cskcb nhung
 * do la KHOA CUA CHINH DANH MUC (ma cua tung co so trong danh sach), khong phai cot
 * phan tach — nen KHONG duoc suy ra co nay tu su ton tai cua cot ma_cskcb.
 */

return [
    'medicine' => [
        'ten' => 'DM thuốc BHYT',
        'model' => App\Models\BHYT\MedicineCatalog::class,
        'bang' => 'medicine_catalogs',
        'theo_co_so' => true,
    ],
    'medical_supply' => [
        'ten' => 'DM Vật tư y tế',
        'model' => App\Models\BHYT\MedicalSupplyCatalog::class,
        'bang' => 'medical_supply_catalogs',
        'theo_co_so' => true,
    ],
    'service' => [
        'ten' => 'DM Dịch vụ kỹ thuật',
        'model' => App\Models\BHYT\ServiceCatalog::class,
        'bang' => 'service_catalogs',
        'theo_co_so' => true,
    ],
    'icd10' => [
        'ten' => 'DM ICD-10',
        'model' => App\Models\BHYT\Icd10Category::class,
        'bang' => 'icd10_categories',
        'theo_co_so' => false,
    ],
    'icd_yhct' => [
        'ten' => 'DM ICD-YHCT',
        'model' => App\Models\BHYT\IcdYhctCategory::class,
        'bang' => 'icd_yhct_categories',
        'theo_co_so' => false,
    ],
    'medical_staff' => [
        'ten' => 'DM Nhân viên y tế',
        'model' => App\Models\BHYT\MedicalStaff::class,
        'bang' => 'medical_staffs',
        'theo_co_so' => false,
    ],
    'department_bed' => [
        'ten' => 'DM Khoa Phòng Giường',
        'model' => App\Models\BHYT\DepartmentBedCatalog::class,
        'bang' => 'department_bed_catalogs',
        'theo_co_so' => false,
    ],
    'equipment' => [
        'ten' => 'DM Trang thiết bị',
        'model' => App\Models\BHYT\EquipmentCatalog::class,
        'bang' => 'equipment_catalogs',
        'theo_co_so' => false,
    ],
    'administrative_unit' => [
        'ten' => 'DM Đơn vị hành chính',
        'model' => App\Models\BHYT\AdministrativeUnit::class,
        'bang' => 'administrative_units',
        'theo_co_so' => false,
    ],
    'medical_organization' => [
        'ten' => 'DM Cơ sở KCB',
        'model' => App\Models\BHYT\MedicalOrganization::class,
        'bang' => 'medical_organizations',
        'theo_co_so' => false,
    ],
    'job_categories' => [
        'ten' => 'DM Nghề nghiệp',
        'model' => App\Models\BHYT\JobCategory::class,
        'bang' => 'job_categories',
        'theo_co_so' => false,
    ],
];
```

- [ ] **Step 4: Chạy test, xác nhận xanh**

```bash
vendor/bin/phpunit tests/Unit/SoDangKyDanhMucTest.php
```

Kỳ vọng: PASS cả 3 test.

- [ ] **Step 5: Chạy suite Unit**

```bash
vendor/bin/phpunit --testsuite Unit
```

Kỳ vọng: OK, không đỏ thêm.

- [ ] **Step 6: Commit**

```bash
git add config/danh_muc_bhyt.php tests/Unit/SoDangKyDanhMucTest.php
git commit -m "feat(danh muc bhyt): so dang ky 11 bo danh muc"
```

---

### Task 2: Ba màn quản lý còn thiếu

**Files:**
- Modify: `app/Http/Controllers/Category/CategoryBHYTController.php` (thêm 6 method)
- Modify: `routes/web.php` (thêm 6 route vào nhóm `category/` sẵn có)
- Modify: `config/adminlte.php` (thêm 3 mục menu vào khối `BHYT`)
- Create: `resources/views/category/bhyt/administrative_unit.blade.php`
- Create: `resources/views/category/bhyt/medical_organization.blade.php`
- Create: `resources/views/category/bhyt/job_category.blade.php`
- Test: `tests/Unit/MenuDanhMucBhytTest.php`

**Interfaces:**
- Consumes: model `AdministrativeUnit`, `MedicalOrganization`, `JobCategory` trong `App\Models\BHYT` (đã tồn tại sẵn, không phải tạo).
- Produces: 6 tên route — `category-bhyt.administrative-unit`, `category-bhyt.fetch-administrative-unit`, `category-bhyt.medical-organization`, `category-bhyt.fetch-medical-organization`, `category-bhyt.job-category`, `category-bhyt.fetch-job-category`. Task 3 sẽ thêm cột nút "Xem" vào 3 blade này.

- [ ] **Step 1: Viết test đỏ cho menu**

Tạo `tests/Unit/MenuDanhMucBhytTest.php`:

```php
<?php

namespace Tests\Unit;

use Tests\TestCase;

class MenuDanhMucBhytTest extends TestCase
{
    /** Tra ve mang cac muc con cua khoi 'BHYT' trong menu quan ly danh muc */
    protected function khoiBhyt()
    {
        foreach (config('adminlte.menu') as $cap1) {
            if (!is_array($cap1) || !isset($cap1['submenu'])) {
                continue;
            }

            foreach ($cap1['submenu'] as $cap2) {
                if (is_array($cap2) && isset($cap2['text']) && $cap2['text'] === 'BHYT') {
                    return $cap2['submenu'];
                }
            }
        }

        return null;
    }

    /** Chi so cua mot muc theo text; -1 neu khong co */
    protected function viTri(array $muc, $text)
    {
        foreach (array_values($muc) as $i => $x) {
            if (isset($x['text']) && $x['text'] === $text) {
                return $i;
            }
        }

        return -1;
    }

    /** @test */
    public function co_du_ba_muc_moi()
    {
        $khoi = $this->khoiBhyt();

        $this->assertNotNull($khoi, 'Khong tim thay khoi BHYT trong menu');

        foreach (['DM Đơn vị hành chính', 'DM Cơ sở KCB', 'DM Nghề nghiệp'] as $ten) {
            $this->assertNotSame(-1, $this->viTri($khoi, $ten), "Thieu muc menu \"$ten\"");
        }
    }

    /** @test */
    public function ba_muc_moi_dat_sau_trang_thiet_bi_va_truoc_dm_loi_xml()
    {
        $khoi = $this->khoiBhyt();

        $tb = $this->viTri($khoi, 'DM Trang thiết bị');
        $loi = $this->viTri($khoi, 'DM lỗi Xml 4750');

        $this->assertNotSame(-1, $tb);
        $this->assertNotSame(-1, $loi);

        foreach (['DM Đơn vị hành chính', 'DM Cơ sở KCB', 'DM Nghề nghiệp'] as $ten) {
            $i = $this->viTri($khoi, $ten);

            $this->assertGreaterThan($tb, $i, "\"$ten\" phai nam sau DM Trang thiet bi");
            $this->assertLessThan($loi, $i, "\"$ten\" phai nam truoc DM loi Xml 4750");
        }
    }

    /** @test */
    public function ba_muc_moi_tro_dung_route()
    {
        $khoi = $this->khoiBhyt();

        $mong = [
            'DM Đơn vị hành chính' => 'category-bhyt.administrative-unit',
            'DM Cơ sở KCB' => 'category-bhyt.medical-organization',
            'DM Nghề nghiệp' => 'category-bhyt.job-category',
        ];

        foreach ($mong as $ten => $route) {
            $i = $this->viTri($khoi, $ten);

            $this->assertSame($route, $khoi[$i]['route'], "Muc \"$ten\" tro sai route");
        }
    }
}
```

- [ ] **Step 2: Chạy test, xác nhận đỏ**

```bash
vendor/bin/phpunit tests/Unit/MenuDanhMucBhytTest.php
```

Kỳ vọng: FAIL với `Thieu muc menu "DM Đơn vị hành chính"`.

- [ ] **Step 3: Thêm 6 method vào controller**

Trong `app/Http/Controllers/Category/CategoryBHYTController.php`, thêm ngay **sau** method `fetchEquipmentCatalog` (tìm bằng nội dung, đừng neo theo số dòng):

```php
    public function indexAdministrativeUnit()
    {
        return view('category.bhyt.administrative_unit');
    }

    public function fetchAdministrativeUnit()
    {
        $result = \App\Models\BHYT\AdministrativeUnit::query();

        return Datatables::of($result)
        ->make(true);
    }

    public function indexMedicalOrganization()
    {
        return view('category.bhyt.medical_organization');
    }

    public function fetchMedicalOrganization()
    {
        $result = \App\Models\BHYT\MedicalOrganization::query();

        return Datatables::of($result)
        ->make(true);
    }

    public function indexJobCategory()
    {
        return view('category.bhyt.job_category');
    }

    public function fetchJobCategory()
    {
        $result = \App\Models\BHYT\JobCategory::query();

        return Datatables::of($result)
        ->make(true);
    }
```

- [ ] **Step 4: Thêm 6 route**

Trong `routes/web.php`, thêm ngay **sau** dòng đặt tên `category-bhyt.fetch-equipment-catalog` (tìm bằng nội dung):

```php
        Route::get('bhyt/administrative-unit', 'Category\CategoryBHYTController@indexAdministrativeUnit')
        ->name('category-bhyt.administrative-unit');
        Route::get('bhyt/fetch-administrative-unit', 'Category\CategoryBHYTController@fetchAdministrativeUnit')
        ->name('category-bhyt.fetch-administrative-unit');

        Route::get('bhyt/medical-organization', 'Category\CategoryBHYTController@indexMedicalOrganization')
        ->name('category-bhyt.medical-organization');
        Route::get('bhyt/fetch-medical-organization', 'Category\CategoryBHYTController@fetchMedicalOrganization')
        ->name('category-bhyt.fetch-medical-organization');

        Route::get('bhyt/job-category', 'Category\CategoryBHYTController@indexJobCategory')
        ->name('category-bhyt.job-category');
        Route::get('bhyt/fetch-job-category', 'Category\CategoryBHYTController@fetchJobCategory')
        ->name('category-bhyt.fetch-job-category');
```

- [ ] **Step 5: Tạo blade DM Đơn vị hành chính**

Tạo `resources/views/category/bhyt/administrative_unit.blade.php`:

```blade
@extends('adminlte::page')

@section('title', 'Danh mục Đơn vị hành chính')

@section('content_header')
  <h1>
    Danh mục
    <small>Đơn vị hành chính</small>
  </h1>
@stop

@section('content')
@include('includes.message')

<div class="panel panel-default">
    <div class="panel-body table-responsive">
        <table id="administrative-unit-list" class="table display table-hover responsive nowrap datatable dtr-inline" width="100%">
            <thead>
                <tr>
                    <th>Mã tỉnh</th>
                    <th>Tên tỉnh</th>
                    <th>Mã huyện</th>
                    <th>Tên huyện</th>
                    <th>Mã xã</th>
                    <th>Tên xã</th>
                </tr>
            </thead>
        </table>
    </div>
</div>
@stop

@push('after-scripts')
<script type="text/javascript">
    var table = null;
    function fetchData() {
        table = $('#administrative-unit-list').DataTable({
            "processing": true,
            "serverSide": true,
            "destroy": true,
            "responsive": true,
            "scrollX": true,
            "ajax": { url: "{{ route('category-bhyt.fetch-administrative-unit') }}" },
            "columns": [
                { "data": "province_code" },
                { "data": "province_name" },
                { "data": "district_code" },
                { "data": "district_name" },
                { "data": "commune_code" },
                { "data": "commune_name" },
            ],
        });
    }
    $(document).ready(function () { fetchData(); });
</script>
@endpush
```

- [ ] **Step 6: Tạo blade DM Cơ sở KCB**

Tạo `resources/views/category/bhyt/medical_organization.blade.php`:

```blade
@extends('adminlte::page')

@section('title', 'Danh mục Cơ sở KCB')

@section('content_header')
  <h1>
    Danh mục
    <small>Cơ sở khám chữa bệnh</small>
  </h1>
@stop

@section('content')
@include('includes.message')

<div class="panel panel-default">
    <div class="panel-body table-responsive">
        <table id="medical-organization-list" class="table display table-hover responsive nowrap datatable dtr-inline" width="100%">
            <thead>
                <tr>
                    <th>Mã CSKCB</th>
                    <th>Tên CSKCB</th>
                    <th>Địa chỉ</th>
                </tr>
            </thead>
        </table>
    </div>
</div>
@stop

@push('after-scripts')
<script type="text/javascript">
    var table = null;
    function fetchData() {
        table = $('#medical-organization-list').DataTable({
            "processing": true,
            "serverSide": true,
            "destroy": true,
            "responsive": true,
            "scrollX": true,
            "ajax": { url: "{{ route('category-bhyt.fetch-medical-organization') }}" },
            "columns": [
                { "data": "ma_cskcb" },
                { "data": "ten_cskcb" },
                { "data": "dia_chi_cskcb" },
            ],
        });
    }
    $(document).ready(function () { fetchData(); });
</script>
@endpush
```

- [ ] **Step 7: Tạo blade DM Nghề nghiệp**

Tạo `resources/views/category/bhyt/job_category.blade.php`:

```blade
@extends('adminlte::page')

@section('title', 'Danh mục Nghề nghiệp')

@section('content_header')
  <h1>
    Danh mục
    <small>Nghề nghiệp</small>
  </h1>
@stop

@section('content')
@include('includes.message')

<div class="panel panel-default">
    <div class="panel-body table-responsive">
        <table id="job-category-list" class="table display table-hover responsive nowrap datatable dtr-inline" width="100%">
            <thead>
                <tr>
                    <th>Mã nghề nghiệp</th>
                    <th>Tên nghề nghiệp</th>
                </tr>
            </thead>
        </table>
    </div>
</div>
@stop

@push('after-scripts')
<script type="text/javascript">
    var table = null;
    function fetchData() {
        table = $('#job-category-list').DataTable({
            "processing": true,
            "serverSide": true,
            "destroy": true,
            "responsive": true,
            "scrollX": true,
            "ajax": { url: "{{ route('category-bhyt.fetch-job-category') }}" },
            "columns": [
                { "data": "job_code" },
                { "data": "job_name" },
            ],
        });
    }
    $(document).ready(function () { fetchData(); });
</script>
@endpush
```

- [ ] **Step 8: Thêm 3 mục menu**

Trong `config/adminlte.php`, trong khối `'text' => 'BHYT'`, chèn **sau** phần tử `'text' => 'DM Trang thiết bị'` và **trước** phần tử `'text' => 'DM lỗi Xml 4750'`:

```php
                        [
                            'text'  => 'DM Đơn vị hành chính',
                            'icon'  => 'book',
                            'route'   => 'category-bhyt.administrative-unit',
                            'active'=> ['category/bhyt/administrative-unit*'],
                        ],
                        [
                            'text'  => 'DM Cơ sở KCB',
                            'icon'  => 'book',
                            'route'   => 'category-bhyt.medical-organization',
                            'active'=> ['category/bhyt/medical-organization*'],
                        ],
                        [
                            'text'  => 'DM Nghề nghiệp',
                            'icon'  => 'book',
                            'route'   => 'category-bhyt.job-category',
                            'active'=> ['category/bhyt/job-category*'],
                        ],
```

- [ ] **Step 9: Kiểm cú pháp**

```bash
php -l config/adminlte.php && php -l routes/web.php && php -l app/Http/Controllers/Category/CategoryBHYTController.php
```

Kỳ vọng: `No syntax errors detected` cả ba.

- [ ] **Step 10: Chạy test menu và suite Unit**

```bash
vendor/bin/phpunit tests/Unit/MenuDanhMucBhytTest.php && vendor/bin/phpunit --testsuite Unit
```

Kỳ vọng: PASS 3 test menu; suite Unit OK.

- [ ] **Step 11: Commit**

```bash
git add config/adminlte.php routes/web.php app/Http/Controllers/Category/CategoryBHYTController.php resources/views/category/bhyt/ tests/Unit/MenuDanhMucBhytTest.php
git commit -m "feat(danh muc bhyt): bo sung 3 man quan ly con thieu"
```

---

### Task 3: Cột MA_CSKCB và màn chi tiết chỉ đọc

**Files:**
- Create: `app/Services/Category/NhanTruong.php`
- Create: `resources/views/category/bhyt/_chi_tiet.blade.php`
- Modify: `app/Http/Controllers/Category/CategoryBHYTController.php` (thêm 1 method)
- Modify: `routes/web.php` (thêm 1 route)
- Modify: 11 blade trong `resources/views/category/bhyt/`
- Test: `tests/Unit/NhanTruongTest.php`

**Interfaces:**
- Consumes: `config('danh_muc_bhyt')` từ Task 1; 3 blade mới từ Task 2.
- Produces: route `category-bhyt.chi-tiet`; lớp `App\Services\Category\NhanTruong` với `public static function cua($loai, $cot)` trả string.

- [ ] **Step 1: Viết test đỏ cho nhãn trường**

Tạo `tests/Unit/NhanTruongTest.php`:

```php
<?php

namespace Tests\Unit;

use App\Services\Category\NhanTruong;
use Tests\TestCase;

class NhanTruongTest extends TestCase
{
    /** @test */
    public function truong_co_trong_mapping_thi_lay_ten_chuan()
    {
        // catalog_import_mapping: 'ma_thuoc' => ['MA_THUOC', 'Mã thuốc', 'MA THUOC']
        // Phan tu DAU TIEN la ten chuan.
        $this->assertSame('MA_THUOC', NhanTruong::cua('medicine', 'ma_thuoc'));
        $this->assertSame('MA_NGHE_NGHIEP', NhanTruong::cua('job_categories', 'job_code'));
    }

    /** @test */
    public function truong_ngoai_mapping_thi_giu_ten_cot_tho()
    {
        // ma_cskcb, id, created_at khong nam trong mapping nhap khau.
        $this->assertSame('ma_cskcb', NhanTruong::cua('medicine', 'ma_cskcb'));
        $this->assertSame('id', NhanTruong::cua('medicine', 'id'));
    }

    /** @test */
    public function loai_khong_ton_tai_thi_giu_ten_cot_tho()
    {
        $this->assertSame('bat_ky', NhanTruong::cua('khong_co_loai_nay', 'bat_ky'));
    }
}
```

- [ ] **Step 2: Chạy test, xác nhận đỏ**

```bash
vendor/bin/phpunit tests/Unit/NhanTruongTest.php
```

Kỳ vọng: FAIL với `Class 'App\Services\Category\NhanTruong' not found`.

- [ ] **Step 3: Viết lớp NhanTruong**

Tạo `app/Services/Category/NhanTruong.php`:

```php
<?php

namespace App\Services\Category;

/**
 * Nhan hien thi cho ten cot khi xem chi tiet mot ban ghi danh muc.
 *
 * Lay tu config/catalog_import_mapping.php: voi moi truong, phan tu DAU TIEN cua mang
 * ten cot chap nhan duoc chinh la ten chuan (vi du 'ma_thuoc' => ['MA_THUOC', ...]).
 *
 * Cot nao khong co trong mapping (id, ma_cskcb, created_at, updated_at...) thi giu
 * nguyen ten cot tho — tha hien ten ky thuat con hon bo trong.
 *
 * Ham THUAN de kiem duoc.
 */
class NhanTruong
{
    public static function cua($loai, $cot)
    {
        $cfg = config('catalog_import_mapping.' . $loai . '.mapping');

        if (!is_array($cfg) || !isset($cfg[$cot])) {
            return $cot;
        }

        $ten = $cfg[$cot];

        if (!is_array($ten) || empty($ten)) {
            return $cot;
        }

        return (string) reset($ten);
    }
}
```

- [ ] **Step 4: Chạy test, xác nhận xanh**

```bash
vendor/bin/phpunit tests/Unit/NhanTruongTest.php
```

Kỳ vọng: PASS cả 3 test.

- [ ] **Step 5: Thêm method chi tiết vào controller**

Trong `app/Http/Controllers/Category/CategoryBHYTController.php`, thêm sau `fetchJobCategory`:

```php
    /**
     * Chi tiet mot ban ghi danh muc — CHI DOC, dung chung cho ca 11 bo.
     *
     * Man danh sach chi hien duoc vai cot (medicine_catalogs co 26 cot ma danh sach chi
     * hien 11), nen day moi la cho xem duoc day du.
     */
    public function chiTietDanhMuc($loai, $id)
    {
        $so = config('danh_muc_bhyt.' . $loai);

        if (!$so) {
            return response()->json(['message' => 'Loại danh mục không hợp lệ'], 404);
        }

        $model = $so['model'];
        $ban = $model::find($id);

        if (!$ban) {
            return response()->json(['message' => 'Không tìm thấy bản ghi'], 404);
        }

        $truong = [];

        foreach ($ban->toArray() as $cot => $giaTri) {
            $truong[] = [
                'nhan' => \App\Services\Category\NhanTruong::cua($loai, $cot),
                'gia_tri' => is_null($giaTri) ? '' : (string) $giaTri,
            ];
        }

        return response()->json(['ten' => $so['ten'], 'truong' => $truong]);
    }
```

- [ ] **Step 6: Thêm route chi tiết**

Trong `routes/web.php`, thêm sau route `category-bhyt.fetch-job-category`:

```php
        Route::get('bhyt/chi-tiet/{loai}/{id}', 'Category\CategoryBHYTController@chiTietDanhMuc')
        ->name('category-bhyt.chi-tiet');
```

- [ ] **Step 7: Tạo partial modal dùng chung**

Tạo `resources/views/category/bhyt/_chi_tiet.blade.php`:

```blade
{{-- Modal xem chi tiet mot ban ghi danh muc — CHI DOC, dung chung cho ca 11 man. --}}
<div class="modal fade" id="modal-chi-tiet" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title" id="modal-chi-tiet-ten">Chi tiết</h4>
            </div>
            <div class="modal-body table-responsive">
                <table class="table table-bordered table-condensed">
                    <tbody id="modal-chi-tiet-body"></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@push('after-scripts')
<script type="text/javascript">
// Dung bang khoa-gia tri tu JSON tra ve. Khong biet truoc danh muc nao co cot gi, nen
// duyet thang mang 'truong' may chu gui xuong.
function xemChiTiet(loai, id) {
    var url = "{{ url('category/bhyt/chi-tiet') }}/" + loai + "/" + id;

    $.getJSON(url, function (r) {
        $('#modal-chi-tiet-ten').text(r.ten);

        var html = '';
        for (var i = 0; i < r.truong.length; i++) {
            html += '<tr><th style="width:34%">' + $('<div>').text(r.truong[i].nhan).html()
                 + '</th><td>' + $('<div>').text(r.truong[i].gia_tri).html() + '</td></tr>';
        }

        $('#modal-chi-tiet-body').html(html);
        $('#modal-chi-tiet').modal('show');
    }).fail(function (x) {
        alert(x.responseJSON && x.responseJSON.message ? x.responseJSON.message : 'Không tải được chi tiết');
    });
}

// Uy quyen su kien: DataTable ve lai dong moi lan phan trang nen khong bind truc tiep.
$(document).on('click', '.nut-chi-tiet', function () {
    xemChiTiet($(this).data('loai'), $(this).data('id'));
});
</script>
@endpush
```

- [ ] **Step 8: Gắn nút "Xem" vào 11 blade**

Với **mỗi** tệp trong danh sách dưới đây, làm đúng ba việc:

1. Thêm `@include('category.bhyt._chi_tiet')` ngay trước `@stop` của `@section('content')`.
2. Thêm `<th>Xem</th>` vào cuối hàng `<thead>`.
3. Thêm cột cuối vào mảng `"columns"` của DataTable:

```javascript
                { "data": "id", "orderable": false, "searchable": false, "render": function (d) {
                    return '<button type="button" class="btn btn-xs btn-default nut-chi-tiet" data-loai="LOAI" data-id="' + d + '">Xem</button>';
                } },
```

Thay `LOAI` bằng khoá tương ứng:

| Tệp blade | Giá trị `LOAI` |
| --- | --- |
| `medicine_catalog.blade.php` | `medicine` |
| `medical_supply_catalog.blade.php` | `medical_supply` |
| `service_catalog.blade.php` | `service` |
| `icd10_catalog.blade.php` | `icd10` |
| `icd_yhct_catalog.blade.php` | `icd_yhct` |
| `medical_staff.blade.php` | `medical_staff` |
| `department_bed_catalog.blade.php` | `department_bed` |
| `equipment_catalog.blade.php` | `equipment` |
| `administrative_unit.blade.php` | `administrative_unit` |
| `medical_organization.blade.php` | `medical_organization` |
| `job_category.blade.php` | `job_categories` |

- [ ] **Step 9: Thêm cột MA_CSKCB vào 3 blade theo cơ sở**

Với `medicine_catalog.blade.php`, `medical_supply_catalog.blade.php`, `service_catalog.blade.php`: thêm `<th>MA_CSKCB</th>` vào `<thead>` ngay **trước** `<th>Xem</th>`, và thêm cột tương ứng vào mảng `"columns"` ngay **trước** cột nút Xem:

```javascript
                { "data": "ma_cskcb", "render": function (d) { return d ? d : 'Dùng chung'; } },
```

Giá trị rỗng hiển thị `Dùng chung` chứ không để trống: `ma_cskcb = NULL` đúng nghĩa là dùng chung cho mọi cơ sở, để trống thì người xem không phân biệt được với "chưa gán".

**Không** thêm cột này vào `medical_organization.blade.php` — ở bảng đó `ma_cskcb` là dữ liệu của chính danh mục và đã có trong danh sách rồi.

- [ ] **Step 10: Kiểm cú pháp và chạy suite**

```bash
php -l routes/web.php && php -l app/Http/Controllers/Category/CategoryBHYTController.php && php -l app/Services/Category/NhanTruong.php && vendor/bin/phpunit --testsuite Unit
```

Kỳ vọng: không lỗi cú pháp; suite Unit OK.

- [ ] **Step 11: Commit**

```bash
git add app/Services/Category/NhanTruong.php resources/views/category/bhyt/ routes/web.php app/Http/Controllers/Category/CategoryBHYTController.php tests/Unit/NhanTruongTest.php
git commit -m "feat(danh muc bhyt): cot MA_CSKCB va man xem chi tiet chi doc"
```

---

### Task 4: Xoá toàn bộ một danh mục

**Files:**
- Create: `app/Services/Category/XoaDanhMuc.php`
- Modify: `app/Http/Controllers/Category/CategoryBHYTController.php` (thêm 2 method)
- Modify: `routes/web.php` (thêm nhóm route mới)
- Modify: `resources/views/category/bhyt/import.blade.php`
- Test: `tests/Unit/XoaDanhMucTest.php`, `tests/Unit/RouteXoaDanhMucTest.php`

**Interfaces:**
- Consumes: `config('danh_muc_bhyt')` từ Task 1; `App\Services\BHYT\DanhSachCoSo::danhSach()` (đã có sẵn).
- Produces: `App\Services\Category\XoaDanhMuc::dieuKien($loai, $maCskcb, array $soDangKy)` trả `['bang' => string, 'dieu_kien' => array]`, ném `InvalidArgumentException` nếu `$loai` không có trong `$soDangKy`.

- [ ] **Step 1: Viết test đỏ cho điều kiện xoá**

Tạo `tests/Unit/XoaDanhMucTest.php`:

```php
<?php

namespace Tests\Unit;

use App\Services\Category\XoaDanhMuc;
use Tests\TestCase;

class XoaDanhMucTest extends TestCase
{
    protected function so()
    {
        return config('danh_muc_bhyt');
    }

    /** @test */
    public function loai_dung_chung_thi_dieu_kien_rong()
    {
        $ra = XoaDanhMuc::dieuKien('icd10', '', $this->so());

        $this->assertSame('icd10_categories', $ra['bang']);
        $this->assertSame([], $ra['dieu_kien']);
    }

    /** @test */
    public function loai_dung_chung_thi_bo_qua_ma_co_so_truyen_vao()
    {
        // Tham so lac khong duoc bien thanh dieu kien loc: cot ma_cskcb khong ton tai o
        // bang nay, loc theo no se lam vo truy van.
        $ra = XoaDanhMuc::dieuKien('icd10', '01929', $this->so());

        $this->assertSame([], $ra['dieu_kien']);
    }

    /** @test */
    public function theo_co_so_nhung_khong_chon_co_so_thi_xoa_tat_ca()
    {
        $ra = XoaDanhMuc::dieuKien('medicine', '', $this->so());

        $this->assertSame('medicine_catalogs', $ra['bang']);
        $this->assertSame([], $ra['dieu_kien']);
    }

    /** @test */
    public function theo_co_so_va_chon_co_so_thi_loc_dung_co_so_do()
    {
        $ra = XoaDanhMuc::dieuKien('medicine', '01929', $this->so());

        $this->assertSame(['ma_cskcb' => '01929'], $ra['dieu_kien']);
    }

    /**
     * Bay da gap: medical_organizations CUNG co cot ma_cskcb, nhung do la KHOA CUA CHINH
     * DANH MUC (ma cua tung co so trong danh sach), khong phai cot phan tach theo co so.
     * Neu ai do suy theo_co_so tu su ton tai cua cot, test nay se do.
     */
    /** @test */
    public function medical_organization_khong_phai_danh_muc_theo_co_so()
    {
        $ra = XoaDanhMuc::dieuKien('medical_organization', '01929', $this->so());

        $this->assertSame('medical_organizations', $ra['bang']);
        $this->assertSame([], $ra['dieu_kien']);
    }

    /** @test */
    public function loai_khong_ton_tai_thi_nem_ngoai_le()
    {
        $this->expectException(\InvalidArgumentException::class);

        XoaDanhMuc::dieuKien('khong_co_loai_nay', '', $this->so());
    }
}
```

- [ ] **Step 2: Chạy test, xác nhận đỏ**

```bash
vendor/bin/phpunit tests/Unit/XoaDanhMucTest.php
```

Kỳ vọng: FAIL với `Class 'App\Services\Category\XoaDanhMuc' not found`.

- [ ] **Step 3: Viết lớp XoaDanhMuc**

Tạo `app/Services/Category/XoaDanhMuc.php`:

```php
<?php

namespace App\Services\Category;

use InvalidArgumentException;

/**
 * Dung dieu kien xoa toan bo mot danh muc.
 *
 * Tach rieng khoi controller vi day la phan de sai nhat: loc theo co so. Ham THUAN nen
 * kiem duoc ma khong dung toi mot dong du lieu nao.
 */
class XoaDanhMuc
{
    /**
     * @param string $loai      khoa trong so dang ky danh_muc_bhyt
     * @param string $maCskcb   ma co so; rong nghia la "tat ca co so"
     * @param array  $soDangKy  config('danh_muc_bhyt')
     *
     * @return array ['bang' => string, 'dieu_kien' => array]
     *
     * @throws InvalidArgumentException khi $loai khong co trong so dang ky
     */
    public static function dieuKien($loai, $maCskcb, array $soDangKy)
    {
        if (!isset($soDangKy[$loai])) {
            throw new InvalidArgumentException('Loai danh muc khong hop le: ' . $loai);
        }

        $x = $soDangKy[$loai];
        $ma = trim((string) $maCskcb);

        // Danh muc dung chung: BO QUA ma co so du co truyen vao. Cot ma_cskcb khong ton
        // tai o cac bang do, loc theo no se lam vo truy van.
        if (empty($x['theo_co_so']) || $ma === '') {
            return ['bang' => $x['bang'], 'dieu_kien' => []];
        }

        return ['bang' => $x['bang'], 'dieu_kien' => ['ma_cskcb' => $ma]];
    }
}
```

- [ ] **Step 4: Chạy test, xác nhận xanh**

```bash
vendor/bin/phpunit tests/Unit/XoaDanhMucTest.php
```

Kỳ vọng: PASS cả 6 test.

- [ ] **Step 5: Viết test đỏ cho route xoá**

Tạo `tests/Unit/RouteXoaDanhMucTest.php`:

```php
<?php

namespace Tests\Unit;

use Route;
use Tests\TestCase;

class RouteXoaDanhMucTest extends TestCase
{
    /** @test */
    public function hai_route_xoa_chi_danh_cho_superadministrator()
    {
        foreach (['category-bhyt.xoa-danh-muc-dem', 'category-bhyt.xoa-danh-muc'] as $ten) {
            $r = Route::getRoutes()->getByName($ten);

            $this->assertNotNull($r, "Thieu route $ten");

            $mw = $r->gatherMiddleware();

            $this->assertContains('checkrole:superadministrator', $mw,
                "Route $ten phai gioi han cho superadministrator");
            $this->assertNotContains('checkrole:category-manager', $mw,
                "Route $ten khong duoc mo cho ca category-manager");
            $this->assertContains('auth', $mw, "Route $ten mat xac thuc");
        }
    }

    /** @test */
    public function route_chi_tiet_dung_quyen_category_manager()
    {
        $r = Route::getRoutes()->getByName('category-bhyt.chi-tiet');

        $this->assertNotNull($r, 'Thieu route category-bhyt.chi-tiet');

        $mw = $r->gatherMiddleware();

        $this->assertContains('checkrole:category-manager', $mw);
        $this->assertContains('auth', $mw);
    }
}
```

- [ ] **Step 6: Chạy test, xác nhận đỏ**

```bash
vendor/bin/phpunit tests/Unit/RouteXoaDanhMucTest.php
```

Kỳ vọng: `hai_route_xoa_chi_danh_cho_superadministrator` FAIL với `Thieu route category-bhyt.xoa-danh-muc-dem`; `route_chi_tiet_dung_quyen_category_manager` PASS (route đó đã có từ Task 3).

- [ ] **Step 7: Thêm 2 method vào controller**

Trong `app/Http/Controllers/Category/CategoryBHYTController.php`, thêm sau `chiTietDanhMuc`:

```php
    /** Dem so dong se bi xoa, de nguoi dung thay con so THAT truoc khi bam nut */
    public function demXoaDanhMuc(Request $request)
    {
        try {
            $q = $this->truyVanXoa($request);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['so_dong' => $q->count()]);
    }

    public function xoaDanhMuc(Request $request)
    {
        if (trim((string) $request->input('xac_nhan')) !== 'XOA') {
            return response()->json(['message' => 'Phải gõ đúng chữ XOA để xác nhận'], 422);
        }

        try {
            $q = $this->truyVanXoa($request);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['so_dong' => $q->delete()]);
    }

    /** Dung truy van dung chung cho ca dem lan xoa, de hai ben khong bao gio lech nhau */
    protected function truyVanXoa(Request $request)
    {
        $loai = (string) $request->input('loai');
        $maCskcb = trim((string) $request->input('ma_cskcb'));

        if ($maCskcb !== '' && !array_key_exists($maCskcb, \App\Services\BHYT\DanhSachCoSo::danhSach())) {
            throw new \InvalidArgumentException('Cơ sở khám chữa bệnh không hợp lệ');
        }

        $x = \App\Services\Category\XoaDanhMuc::dieuKien($loai, $maCskcb, config('danh_muc_bhyt'));

        return DB::table($x['bang'])->where($x['dieu_kien']);
    }
```

Nếu đầu tệp chưa có `use DB;` hoặc `use Illuminate\Http\Request;` thì bổ sung.

- [ ] **Step 8: Thêm nhóm route xoá**

Trong `routes/web.php`, thêm **ngay sau** dấu đóng `});` của nhóm `['prefix' => 'category/', 'middleware' => ['checkrole:category-manager']]`:

```php
    // Xoa toan bo mot danh muc: thao tac PHA HUY, chi superadministrator. Khong gop vao
    // nhom category-manager o tren.
    Route::group(['prefix' => 'category/', 'middleware' => ['checkrole:superadministrator']], function () {
        Route::get('bhyt/xoa-danh-muc/dem', 'Category\CategoryBHYTController@demXoaDanhMuc')
        ->name('category-bhyt.xoa-danh-muc-dem');
        Route::post('bhyt/xoa-danh-muc', 'Category\CategoryBHYTController@xoaDanhMuc')
        ->name('category-bhyt.xoa-danh-muc');
    });
```

- [ ] **Step 9: Chạy test route, xác nhận xanh**

```bash
vendor/bin/phpunit tests/Unit/RouteXoaDanhMucTest.php
```

Kỳ vọng: PASS cả 2 test.

- [ ] **Step 10: Thêm khối xoá vào màn nhập khẩu**

Trong `resources/views/category/bhyt/import.blade.php`, thêm ngay **trước** `@stop` của `@section('content')`:

```blade
@if (auth()->check() && auth()->user()->hasRole('superadministrator'))
<div class="panel panel-danger">
    <div class="panel-heading"><strong>Xoá toàn bộ một danh mục</strong></div>
    <div class="panel-body">
        <div class="alert alert-warning" style="margin-bottom:12px;">
            Xoá xong mà <strong>chưa nhập lại</strong> thì XML3176 và kiểm tra y lệnh sẽ báo
            <strong>mọi mã đều sai</strong> — đã đo được ba danh mục rỗng sinh khoảng 36.100
            vi phạm giả. Nên xoá và nhập lại liền tay.
        </div>

        <div class="form-inline" style="margin-bottom:10px;">
            <label style="margin-right:8px;">Danh mục:</label>
            <select id="xoa_loai" class="form-control" style="min-width:260px;margin-right:8px;">
                @foreach (config('danh_muc_bhyt') as $ma => $x)
                    <option value="{{ $ma }}" data-theo-co-so="{{ $x['theo_co_so'] ? 1 : 0 }}">{{ $x['ten'] }}</option>
                @endforeach
            </select>

            <span id="xoa_co_so_wrap" style="display:none;">
                <label style="margin-right:8px;">Cơ sở:</label>
                <select id="xoa_ma_cskcb" class="form-control" style="min-width:220px;margin-right:8px;">
                    <option value="">Tất cả cơ sở</option>
                    @foreach ($danhSachCoSo as $ma => $nhan)
                        <option value="{{ $ma }}">{{ $nhan }}</option>
                    @endforeach
                </select>
            </span>

            <button type="button" id="xoa_dem" class="btn btn-default">Đếm số dòng sẽ xoá</button>
        </div>

        <div id="xoa_ket_qua" style="margin-bottom:10px;"></div>

        <div class="form-inline">
            <label style="margin-right:8px;">Gõ <code>XOA</code> để xác nhận:</label>
            <input type="text" id="xoa_xac_nhan" class="form-control" style="width:120px;margin-right:8px;">
            <button type="button" id="xoa_thuc_hien" class="btn btn-danger" disabled>Xoá</button>
        </div>
    </div>
</div>
@endif
```

- [ ] **Step 11: Thêm JS cho khối xoá**

Trong cùng tệp, thêm vào cuối khối `@push('after-scripts')` đã có (nếu chưa có khối đó thì tạo mới trước `@stop` cuối tệp):

```javascript
// Khoi xoa danh muc — chi ton tai voi superadministrator nen kiem su ton tai truoc.
if (document.getElementById('xoa_loai')) {
    function xoaThamSo() {
        var $l = $('#xoa_loai option:selected');
        return {
            loai: $l.val(),
            ma_cskcb: $l.data('theo-co-so') == 1 ? $('#xoa_ma_cskcb').val() : ''
        };
    }

    function xoaDatLaiNut() {
        $('#xoa_thuc_hien').prop('disabled', $('#xoa_xac_nhan').val().trim() !== 'XOA');
    }

    $('#xoa_loai').on('change', function () {
        $('#xoa_co_so_wrap').toggle($('#xoa_loai option:selected').data('theo-co-so') == 1);
        $('#xoa_ket_qua').html('');
    }).trigger('change');

    $('#xoa_ma_cskcb').on('change', function () { $('#xoa_ket_qua').html(''); });
    $('#xoa_xac_nhan').on('input', xoaDatLaiNut);

    $('#xoa_dem').on('click', function () {
        $.getJSON("{{ route('category-bhyt.xoa-danh-muc-dem') }}", xoaThamSo(), function (r) {
            $('#xoa_ket_qua').html('<div class="alert alert-info">Sẽ xoá <strong>'
                + r.so_dong + '</strong> dòng.</div>');
        }).fail(function (x) {
            $('#xoa_ket_qua').html('<div class="alert alert-danger">'
                + ((x.responseJSON && x.responseJSON.message) || 'Lỗi') + '</div>');
        });
    });

    $('#xoa_thuc_hien').on('click', function () {
        var d = xoaThamSo();
        d._token = "{{ csrf_token() }}";
        d.xac_nhan = $('#xoa_xac_nhan').val().trim();

        $.post("{{ route('category-bhyt.xoa-danh-muc') }}", d, function (r) {
            $('#xoa_ket_qua').html('<div class="alert alert-success">Đã xoá <strong>'
                + r.so_dong + '</strong> dòng.</div>');
            $('#xoa_xac_nhan').val('');
            xoaDatLaiNut();
        }, 'json').fail(function (x) {
            $('#xoa_ket_qua').html('<div class="alert alert-danger">'
                + ((x.responseJSON && x.responseJSON.message) || 'Lỗi') + '</div>');
        });
    });
}
```

- [ ] **Step 12: Chạy test cú pháp JS của blade**

Dự án có sẵn `tests/Unit/ImportBladeJsTest.php` chạy `node --check` trên các khối `<script>` của chính tệp `import.blade.php`. Chạy nó:

```bash
vendor/bin/phpunit tests/Unit/ImportBladeJsTest.php
```

Kỳ vọng: PASS. Test này từng bắt được một lỗi cú pháp JS làm hỏng **toàn bộ** màn nhập khẩu — nếu nó đỏ, sửa cho xanh rồi mới đi tiếp.

- [ ] **Step 13: Chạy suite Unit và kiểm cú pháp**

```bash
php -l routes/web.php && php -l app/Http/Controllers/Category/CategoryBHYTController.php && php -l app/Services/Category/XoaDanhMuc.php && vendor/bin/phpunit --testsuite Unit
```

Kỳ vọng: không lỗi cú pháp; suite Unit OK.

- [ ] **Step 14: Commit**

```bash
git add app/Services/Category/XoaDanhMuc.php routes/web.php app/Http/Controllers/Category/CategoryBHYTController.php resources/views/category/bhyt/import.blade.php tests/Unit/XoaDanhMucTest.php tests/Unit/RouteXoaDanhMucTest.php
git commit -m "feat(danh muc bhyt): xoa toan bo mot danh muc cho superadministrator"
```

---

### Task 5: Cập nhật tài liệu

**Files:**
- Modify: `docs/tai-lieu-tong-hop-xml3176-order-check.md`

**Interfaces:**
- Consumes: kết quả Task 1-4.
- Produces: không có gì.

- [ ] **Step 1: Thêm mục mô tả danh mục BHYT**

Trong `docs/tai-lieu-tong-hop-xml3176-order-check.md`, chèn đoạn dưới đây vào **cuối mục `## 4. So sánh & điểm chung hai module`**, tức là ngay **trước** dòng tiêu đề `## 5. Tóm tắt chuẩn bị`. Đặt ở đó vì mục 4 là chỗ nói về phần dùng chung của hai module, mà danh mục BHYT chính là nền dữ liệu chung của cả hai.

Cả hai module đều tra cùng bộ danh mục này, nên đừng chèn vào riêng mục 2 hay mục 3.

```markdown
### 4.1. Quản lý danh mục BHYT (cập nhật 29/07/2026)

Hệ thống quản lý **11 bộ danh mục BHYT**. Danh sách chuẩn nằm ở
`config/danh_muc_bhyt.php` — đây là nguồn duy nhất, mọi tính năng mới đọc từ đó thay vì
khai lại.

Ba bộ trước đây nhập được nhưng không xem được, nay đã có màn quản lý: Đơn vị hành chính,
Cơ sở KCB, Nghề nghiệp.

Mỗi màn danh sách có nút **Xem** mở popup chi tiết **chỉ đọc**, hiển thị đầy đủ mọi cột —
cần thiết vì `medicine_catalogs` có 26 cột mà danh sách chỉ hiện 11. Không sửa được dữ
liệu trên giao diện: nguồn chuẩn là tệp BHXH phát hành, sửa tay sẽ bị ghi đè ở lần nhập
kế tiếp mà người sửa lại tưởng đã sửa xong.

Ba bộ thuốc / vật tư y tế / dịch vụ kỹ thuật tách theo cơ sở KCB, danh sách có cột
`MA_CSKCB`; giá trị rỗng hiển thị là **Dùng chung**.

**Lưu ý về cột `ma_cskcb`:** bảng `medical_organizations` cũng có cột này nhưng đó là
**khoá của chính danh mục** (mã của từng cơ sở trong danh sách), không phải cột phân tách
theo cơ sở. Đừng suy cờ `theo_co_so` từ sự tồn tại của cột.

**Xoá toàn bộ một danh mục** nằm ở màn "Nhập khẩu danh mục", chỉ hiện với
`superadministrator`. Chọn danh mục, chọn cơ sở (với ba bộ theo cơ sở), đếm số dòng, gõ
`XOA` để xác nhận. Xoá xong mà chưa nhập lại thì XML3176 và order-check sẽ báo **mọi mã
đều sai** — đã đo được ba danh mục rỗng sinh khoảng 36.100 vi phạm giả.
```

- [ ] **Step 2: Commit**

```bash
git add docs/tai-lieu-tong-hop-xml3176-order-check.md
git commit -m "docs(danh muc bhyt): ghi lai 11 bo danh muc, man chi tiet va chuc nang xoa"
```

---

## Nghiệm thu cuối

- [ ] `vendor/bin/phpunit --testsuite Unit` — OK, không đỏ.
- [ ] `php artisan config:clear` (bắt buộc, vì đã sửa `config/adminlte.php` và thêm `config/danh_muc_bhyt.php`).
- [ ] Đăng nhập, vào Quản lý danh mục → BHYT: thấy đủ 3 mục mới, mỗi mục mở ra bảng có dữ liệu.
- [ ] Bấm nút **Xem** ở một dòng của DM Vật tư y tế: popup hiện đầy đủ các cột.
- [ ] DM Vật tư y tế có cột `MA_CSKCB`, dòng không gán cơ sở hiển thị `Dùng chung`.
- [ ] Màn "Nhập khẩu danh mục": khối xoá màu đỏ chỉ hiện với tài khoản superadministrator.
- [ ] Chọn `DM Nghề nghiệp`, bấm "Đếm số dòng sẽ xoá" → hiện **835**. **Không bấm nút Xoá** ở bước nghiệm thu này.
- [ ] Chọn `DM Vật tư y tế` → ô chọn cơ sở hiện ra. Chọn `DM ICD-10` → ô chọn cơ sở ẩn đi.
