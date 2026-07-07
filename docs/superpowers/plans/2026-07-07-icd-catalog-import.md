# Import + xem danh mục ICD (ICD-10 & ICD-YHCT) — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Bổ sung import Excel cho danh mục ICD-10 & ICD-YHCT (tự nhận diện) + trang xem DataTable cho mỗi loại, theo đúng khuôn mẫu 9 catalog hiện có.

**Architecture:** Thêm 2 loại vào `config/catalog_import_mapping.php`; 2 method `importIcd10`/`importIcdYhct` trong `CatalogImportService` ghi vào model `Icd10Category`/`IcdYhctCategory` (đã có bảng). Trang xem = controller index/fetch + route + Blade DataTable + menu, sao theo `service-catalog`.

**Tech Stack:** Laravel 5.5, MySQL/MariaDB, Maatwebsite Excel, Yajra Datatables, AdminLTE2, PHPUnit.

**Spec:** `docs/superpowers/specs/2026-07-07-icd-catalog-import-design.md`

**Verification note:** Test order-check/catalog hiện thuần logic. Nhận diện loại (`detectCatalogType`) test thuần (không DB). Đường ghi model (`importIcd10/importIcdYhct`) verify bằng **smoke tinker** với mã ICD giả (`TEST001`...) rồi xóa — tránh đụng dữ liệu thật.

---

### Task 1: Thêm `$fillable` cho 2 model ICD

**Files:**
- Modify: `app/Models/BHYT/Icd10Category.php`
- Modify: `app/Models/BHYT/IcdYhctCategory.php`

Lý do: model đang trơ (không `$fillable`) → `updateOrCreate` sẽ ném MassAssignmentException. Các model catalog khác (vd `ServiceCatalog`) đều khai báo `$fillable`.

- [ ] **Step 1: `Icd10Category` — thêm `$fillable`**

Thay nội dung `app/Models/BHYT/Icd10Category.php`:
```php
<?php

namespace App\Models\BHYT;

use Illuminate\Database\Eloquent\Model;

class Icd10Category extends Model
{
    protected $fillable = [
        'icd_code',
        'icd_name',
        'is_chronic',
        'is_active',
    ];
}
```

- [ ] **Step 2: `IcdYhctCategory` — thêm `$fillable`**

Thay nội dung `app/Models/BHYT/IcdYhctCategory.php`:
```php
<?php

namespace App\Models\BHYT;

use Illuminate\Database\Eloquent\Model;

class IcdYhctCategory extends Model
{
    protected $fillable = [
        'icd_code',
        'icd_name',
        'icd_yhct_name',
        'icd10_code',
        'icd10_name',
        'is_active',
    ];
}
```

- [ ] **Step 3: Lint**

Run: `cd "C:\Users\tracnn\qlbv" && php -l app/Models/BHYT/Icd10Category.php && php -l app/Models/BHYT/IcdYhctCategory.php`
Expected: `No syntax errors detected` cho cả hai.

- [ ] **Step 4: Commit**

```bash
git add app/Models/BHYT/Icd10Category.php app/Models/BHYT/IcdYhctCategory.php
git commit -m "feat(catalog-icd): them fillable cho Icd10Category + IcdYhctCategory"
```

---

### Task 2: Config nhận diện + ánh xạ ICD (TDD nhận diện)

**Files:**
- Modify: `config/catalog_import_mapping.php`
- Test: `tests/Unit/CatalogIcdDetectTest.php` (create)

- [ ] **Step 1: Viết test thất bại (nhận diện)**

Create `tests/Unit/CatalogIcdDetectTest.php`:
```php
<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\ExcelColumnMapper;

class CatalogIcdDetectTest extends TestCase
{
    private function detect(array $header)
    {
        $mapper = new ExcelColumnMapper();
        return $mapper->detectCatalogType($header, config('catalog_import_mapping'));
    }

    public function test_nhan_dien_icd10()
    {
        $this->assertSame('icd10', $this->detect(['MA_BENH', 'TEN_BENH', 'GHI_CHU']));
    }

    public function test_nhan_dien_icd_yhct_khong_nham_icd10()
    {
        // File YHCT có cả cột tham chiếu ICD10 nhưng vẫn phải ra icd_yhct.
        $this->assertSame('icd_yhct', $this->detect(['MA_YHCT', 'TEN_YHCT', 'TEN_BENH_YHCT', 'MA_ICD10', 'TEN_BENH']));
    }
}
```

- [ ] **Step 2: Chạy test — xác nhận FAIL**

Run: `cd "C:\Users\tracnn\qlbv" && vendor/bin/phpunit tests/Unit/CatalogIcdDetectTest.php`
Expected: FAIL (trả null vì chưa có config icd).

- [ ] **Step 3: Thêm 2 loại vào config**

Trong `config/catalog_import_mapping.php`, thêm 2 khối sau vào mảng trả về. ĐẶT `icd_yhct` TRƯỚC `icd10` (để khi trùng số match, ưu tiên YHCT — tránh nhận nhầm). Chèn ngay sau khối `'service' => [ ... ],` (đâu cũng được miễn `icd_yhct` đứng trước `icd10`):

```php
    'icd_yhct' => [
        'detect_keys' => ['MA_YHCT', 'TEN_YHCT', 'TEN_BENH_YHCT'],
        'mapping' => [
            'icd_code' => ['MA_ICD_YHCT', 'MA_YHCT', 'MA_ICD', 'Mã ICD YHCT', 'Mã YHCT', 'MA YHCT'],
            'icd_name' => ['TEN_ICD_YHCT', 'TEN_YHCT', 'Tên ICD YHCT', 'Tên YHCT', 'TEN YHCT'],
            'icd_yhct_name' => ['TEN_BENH_YHCT', 'TEN_YHCT_BENH', 'Tên bệnh YHCT', 'ICD_YHCT'],
            'icd10_code' => ['MA_ICD10', 'MA_BENH', 'Mã ICD10', 'MA ICD10'],
            'icd10_name' => ['TEN_ICD10', 'TEN_BENH', 'Tên ICD10', 'TEN ICD10'],
        ],
        'required_fields' => ['icd_code', 'icd_name', 'icd_yhct_name'],
        'unique_keys' => ['icd_code'],
    ],

    'icd10' => [
        'detect_keys' => ['MA_ICD10', 'MA_BENH', 'TEN_BENH'],
        'mapping' => [
            'icd_code' => ['MA_ICD10', 'MA_BENH', 'MA_ICD', 'Mã ICD', 'Mã bệnh', 'MA ICD'],
            'icd_name' => ['TEN_ICD10', 'TEN_BENH', 'TEN_ICD', 'Tên ICD', 'Tên bệnh', 'TEN ICD'],
            'is_chronic' => ['BENH_MAN_TINH', 'MAN_TINH', 'Mãn tính', 'MAN TINH'],
        ],
        'required_fields' => ['icd_code', 'icd_name'],
        'unique_keys' => ['icd_code'],
    ],
```

- [ ] **Step 4: Chạy test — xác nhận PASS**

Run: `cd "C:\Users\tracnn\qlbv" && vendor/bin/phpunit tests/Unit/CatalogIcdDetectTest.php`
Expected: OK (2 tests).

- [ ] **Step 5: Commit**

```bash
git add config/catalog_import_mapping.php tests/Unit/CatalogIcdDetectTest.php
git commit -m "feat(catalog-icd): config nhan dien + anh xa ICD-10 & ICD-YHCT"
```

---

### Task 3: `CatalogImportService` — 2 method import

**Files:**
- Modify: `app/Services/CatalogImportService.php`

- [ ] **Step 1: Thêm import model**

Cạnh các dòng `use App\Models\BHYT\...;` (đầu file), thêm:
```php
use App\Models\BHYT\Icd10Category;
use App\Models\BHYT\IcdYhctCategory;
```

- [ ] **Step 2: Đăng ký vào `methodMap`**

Trong method `import()`, mảng `$methodMap` — thêm 2 dòng (cạnh các dòng khác):
```php
            'icd10' => 'importIcd10',
            'icd_yhct' => 'importIcdYhct',
```

- [ ] **Step 3: Thêm 2 method import**

Thêm 2 method sau vào class (đặt cạnh `importService`):
```php
    private function importIcd10($data, array $fieldMapping, array $config)
    {
        $data = $data->slice(1);

        foreach ($data as $row) {
            if (!$this->hasRequiredFields($row, $config['required_fields'], $fieldMapping)) {
                continue;
            }

            try {
                $uniqueKeys = [];
                $updateData = [];

                foreach ($config['unique_keys'] as $key) {
                    $value = $this->getRowValue($row, $key, $fieldMapping);
                    if ($value !== null) {
                        $uniqueKeys[$key] = $value;
                    }
                }

                foreach ($config['mapping'] as $field => $possibleNames) {
                    if (!in_array($field, $config['unique_keys'])) {
                        $value = $this->getRowValue($row, $field, $fieldMapping);
                        if ($value !== null) {
                            $updateData[$field] = $value;
                        }
                    }
                }

                // Chuẩn hóa cờ mãn tính về boolean nếu file có cột đó.
                if (array_key_exists('is_chronic', $updateData)) {
                    $v = mb_strtolower(trim((string) $updateData['is_chronic']));
                    $updateData['is_chronic'] = in_array($v, ['1', 'true', 'x', 'co', 'có', 'yes'], true);
                }

                Icd10Category::updateOrCreate($uniqueKeys, $updateData);
            } catch (\Exception $e) {
                Log::error('Error updating or creating Icd10Category record', [
                    'error' => $e->getMessage(),
                    'row' => $row,
                ]);
                continue;
            }
        }
    }

    private function importIcdYhct($data, array $fieldMapping, array $config)
    {
        $data = $data->slice(1);

        foreach ($data as $row) {
            if (!$this->hasRequiredFields($row, $config['required_fields'], $fieldMapping)) {
                continue;
            }

            try {
                $uniqueKeys = [];
                $updateData = [];

                foreach ($config['unique_keys'] as $key) {
                    $value = $this->getRowValue($row, $key, $fieldMapping);
                    if ($value !== null) {
                        $uniqueKeys[$key] = $value;
                    }
                }

                foreach ($config['mapping'] as $field => $possibleNames) {
                    if (!in_array($field, $config['unique_keys'])) {
                        $value = $this->getRowValue($row, $field, $fieldMapping);
                        if ($value !== null) {
                            $updateData[$field] = $value;
                        }
                    }
                }

                IcdYhctCategory::updateOrCreate($uniqueKeys, $updateData);
            } catch (\Exception $e) {
                Log::error('Error updating or creating IcdYhctCategory record', [
                    'error' => $e->getMessage(),
                    'row' => $row,
                ]);
                continue;
            }
        }
    }
```

- [ ] **Step 4: Lint**

Run: `cd "C:\Users\tracnn\qlbv" && php -l app/Services/CatalogImportService.php`
Expected: `No syntax errors detected`.

- [ ] **Step 5: Smoke ghi model (mã ICD giả, tự xóa)**

Run:
```bash
cd "C:\Users\tracnn\qlbv" && printf '%s\n' \
'$svc = app(App\Services\CatalogImportService::class);' \
'$m = new ReflectionMethod($svc, "importIcd10"); $m->setAccessible(true);' \
'$cfg = config("catalog_import_mapping.icd10");' \
'$fmap = ["icd_code" => 0, "icd_name" => 1, "is_chronic" => 2];' \
'$data = collect([["MA_BENH","TEN_BENH","MAN_TINH"],["TEST001","Benh test 1","1"],["TEST002","Benh test 2","0"]]);' \
'$m->invoke($svc, $data, $fmap, $cfg);' \
'$r = App\Models\BHYT\Icd10Category::where("icd_code","TEST001")->first();' \
'echo "TEST001 name=".($r->icd_name ?? "NULL")." chronic=".var_export($r->is_chronic ?? null, true)."\n";' \
'App\Models\BHYT\Icd10Category::whereIn("icd_code",["TEST001","TEST002"])->delete();' \
'echo "cleaned=".App\Models\BHYT\Icd10Category::whereIn("icd_code",["TEST001","TEST002"])->count()."\n";' \
'exit' | php artisan tinker 2>&1 | grep -E "TEST001|cleaned|error|Exception"
```
Expected: `TEST001 name=Benh test 1 chronic=true` và `cleaned=0`.

- [ ] **Step 6: Commit**

```bash
git add app/Services/CatalogImportService.php
git commit -m "feat(catalog-icd): importIcd10 + importIcdYhct trong CatalogImportService"
```

---

### Task 4: Controller index/fetch + routes

**Files:**
- Modify: `app/Http/Controllers/Category/CategoryBHYTController.php`
- Modify: `routes/web.php`

- [ ] **Step 1: Thêm import model vào controller (nếu chưa có)**

Đầu `CategoryBHYTController.php`, cạnh các `use App\Models\BHYT\...;`, thêm:
```php
use App\Models\BHYT\Icd10Category;
use App\Models\BHYT\IcdYhctCategory;
```

- [ ] **Step 2: Thêm 4 method (cạnh `fetchServiceCatalog`)**

```php
    public function indexIcd10Catalog()
    {
        return view('category.bhyt.icd10_catalog');
    }

    public function fetchIcd10Catalog()
    {
        $result = Icd10Category::query();

        return Datatables::of($result)
        ->make(true);
    }

    public function indexIcdYhctCatalog()
    {
        return view('category.bhyt.icd_yhct_catalog');
    }

    public function fetchIcdYhctCatalog()
    {
        $result = IcdYhctCategory::query();

        return Datatables::of($result)
        ->make(true);
    }
```

- [ ] **Step 3: Thêm 4 route (cạnh route `service-catalog`)**

Trong `routes/web.php`, ngay sau 2 dòng route `bhyt/service-catalog` và `bhyt/fetch-service-catalog`, thêm (giữ đúng nhóm/middleware hiện hành của khối đó):
```php
        Route::get('bhyt/icd10-catalog', 'Category\CategoryBHYTController@indexIcd10Catalog')->name('category-bhyt.icd10-catalog');
        Route::get('bhyt/fetch-icd10-catalog', 'Category\CategoryBHYTController@fetchIcd10Catalog')->name('category-bhyt.fetch-icd10-catalog');
        Route::get('bhyt/icd-yhct-catalog', 'Category\CategoryBHYTController@indexIcdYhctCatalog')->name('category-bhyt.icd-yhct-catalog');
        Route::get('bhyt/fetch-icd-yhct-catalog', 'Category\CategoryBHYTController@fetchIcdYhctCatalog')->name('category-bhyt.fetch-icd-yhct-catalog');
```

- [ ] **Step 4: Lint + kiểm route đăng ký**

Run: `cd "C:\Users\tracnn\qlbv" && php -l app/Http/Controllers/Category/CategoryBHYTController.php && php artisan route:list --name=category-bhyt.icd 2>&1 | grep -E "icd10-catalog|icd-yhct-catalog"`
Expected: `No syntax errors detected` và thấy 4 route ICD.

- [ ] **Step 5: Commit**

```bash
git add app/Http/Controllers/Category/CategoryBHYTController.php routes/web.php
git commit -m "feat(catalog-icd): controller index/fetch + route xem ICD-10 & ICD-YHCT"
```

---

### Task 5: View Blade + menu

**Files:**
- Create: `resources/views/category/bhyt/icd10_catalog.blade.php`
- Create: `resources/views/category/bhyt/icd_yhct_catalog.blade.php`
- Modify: `config/adminlte.php`

- [ ] **Step 1: View ICD-10**

Create `resources/views/category/bhyt/icd10_catalog.blade.php`:
```blade
@extends('adminlte::page')

@section('title', 'Danh mục ICD-10')

@section('content_header')
  <h1>
    Danh mục
    <small>ICD-10</small>
  </h1>
@stop

@section('content')
@include('includes.message')

<div class="panel panel-default">
    <div class="panel-body table-responsive">
        <table id="icd10-list" class="table display table-hover responsive nowrap datatable dtr-inline" width="100%">
            <thead>
                <tr>
                    <th>Mã ICD</th>
                    <th>Tên ICD</th>
                    <th>Mãn tính</th>
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
        table = $('#icd10-list').DataTable({
            "processing": true,
            "serverSide": true,
            "destroy": true,
            "responsive": true,
            "scrollX": true,
            "ajax": { url: "{{ route('category-bhyt.fetch-icd10-catalog') }}" },
            "columns": [
                { "data": "icd_code" },
                { "data": "icd_name" },
                { "data": "is_chronic", "render": function (d) { return d ? 'Có' : ''; } },
            ],
        });
    }
    $(document).ready(function () { fetchData(); });
</script>
@endpush
```

- [ ] **Step 2: View ICD-YHCT**

Create `resources/views/category/bhyt/icd_yhct_catalog.blade.php`:
```blade
@extends('adminlte::page')

@section('title', 'Danh mục ICD-YHCT')

@section('content_header')
  <h1>
    Danh mục
    <small>ICD Y học cổ truyền</small>
  </h1>
@stop

@section('content')
@include('includes.message')

<div class="panel panel-default">
    <div class="panel-body table-responsive">
        <table id="icd-yhct-list" class="table display table-hover responsive nowrap datatable dtr-inline" width="100%">
            <thead>
                <tr>
                    <th>Mã ICD</th>
                    <th>Tên ICD</th>
                    <th>Tên bệnh YHCT</th>
                    <th>Mã ICD10</th>
                    <th>Tên ICD10</th>
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
        table = $('#icd-yhct-list').DataTable({
            "processing": true,
            "serverSide": true,
            "destroy": true,
            "responsive": true,
            "scrollX": true,
            "ajax": { url: "{{ route('category-bhyt.fetch-icd-yhct-catalog') }}" },
            "columns": [
                { "data": "icd_code" },
                { "data": "icd_name" },
                { "data": "icd_yhct_name" },
                { "data": "icd10_code" },
                { "data": "icd10_name" },
            ],
        });
    }
    $(document).ready(function () { fetchData(); });
</script>
@endpush
```

- [ ] **Step 3: Thêm 2 mục menu**

Trong `config/adminlte.php`, ngay sau mục submenu `'DM Dịch vụ kỹ thuật'` (route `category-bhyt.service-catalog`), thêm:
```php
                        [
                            'text'  => 'DM ICD-10',
                            'icon'  => 'book',
                            'route'   => 'category-bhyt.icd10-catalog',
                            'active'=> ['category/bhyt/icd10-catalog*'],
                        ],
                        [
                            'text'  => 'DM ICD-YHCT',
                            'icon'  => 'book',
                            'route'   => 'category-bhyt.icd-yhct-catalog',
                            'active'=> ['category/bhyt/icd-yhct-catalog*'],
                        ],
```

- [ ] **Step 4: Verify render 2 trang (HTTP 200)**

Run: `cd "C:\Users\tracnn\qlbv" && php -l config/adminlte.php && php artisan route:list --name=category-bhyt.icd10-catalog 2>&1 | grep icd10-catalog`
Expected: `No syntax errors detected` và route hiện diện. (Render trực quan để lại cho bước smoke thủ công.)

- [ ] **Step 5: Commit**

```bash
git add resources/views/category/bhyt/icd10_catalog.blade.php resources/views/category/bhyt/icd_yhct_catalog.blade.php config/adminlte.php
git commit -m "feat(catalog-icd): trang xem DataTable + menu ICD-10 & ICD-YHCT"
```

---

### Task 6: Verify toàn bộ + push

**Files:** (không sửa code)

- [ ] **Step 1: Chạy các unit test liên quan**

Run: `cd "C:\Users\tracnn\qlbv" && vendor/bin/phpunit tests/Unit/CatalogIcdDetectTest.php`
Expected: OK (2 tests).

- [ ] **Step 2: Smoke ICD-YHCT (ghi + xóa, mã giả)**

Run:
```bash
cd "C:\Users\tracnn\qlbv" && printf '%s\n' \
'$svc = app(App\Services\CatalogImportService::class);' \
'$m = new ReflectionMethod($svc, "importIcdYhct"); $m->setAccessible(true);' \
'$cfg = config("catalog_import_mapping.icd_yhct");' \
'$fmap = ["icd_code" => 0, "icd_name" => 1, "icd_yhct_name" => 2, "icd10_code" => 3, "icd10_name" => 4];' \
'$data = collect([["MA_YHCT","TEN_YHCT","TEN_BENH_YHCT","MA_ICD10","TEN_ICD10"],["YTEST01","Ten YHCT","Benh YHCT","A00","Benh ta"]]);' \
'$m->invoke($svc, $data, $fmap, $cfg);' \
'$r = App\Models\BHYT\IcdYhctCategory::where("icd_code","YTEST01")->first();' \
'echo "YTEST01 yhct=".($r->icd_yhct_name ?? "NULL")." icd10=".($r->icd10_code ?? "NULL")."\n";' \
'App\Models\BHYT\IcdYhctCategory::where("icd_code","YTEST01")->delete();' \
'exit' | php artisan tinker 2>&1 | grep -E "YTEST01|error|Exception"
```
Expected: `YTEST01 yhct=Benh YHCT icd10=A00`.

- [ ] **Step 3: Push**

```bash
git push origin main
```

---

## Hoàn tất

Sau 6 task: upload Excel ICD-10 / ICD-YHCT được tự nhận diện và ghi vào `icd10_categories` / `icd_yhct_categories` (dedup theo `icd_code`, re-import an toàn); có 2 trang xem DataTable + menu.

**Cần đối chiếu khi dùng thật:** tên cột trong file Excel ICD thực tế phải khớp một trong các alias ở `detect_keys`/`mapping` (Task 2). Nếu import 1 file thật mà không nhận diện được hoặc thiếu cột → bổ sung alias tương ứng vào config (không cần đổi code). Nên chạy thử 1 file mẫu nhỏ trước khi dùng đại trà.
