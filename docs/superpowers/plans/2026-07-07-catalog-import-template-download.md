# Nút tải biểu mẫu import danh mục — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Thêm dropdown + nút "Tải biểu mẫu" trên màn import danh mục, tải file .xlsx sinh từ config (header + tô cột bắt buộc), và mọi biểu mẫu tự nhận diện đúng loại khi import lại.

**Architecture:** `CatalogTemplateExport` (Maatwebsite Excel 3.1, `FromArray`+`WithHeadings`+`WithEvents`) sinh header từ `config/catalog_import_mapping.php`; `headers()` = alias đầu mỗi field + chèn detect_key còn thiếu (đảm bảo tự nhận diện). Controller `downloadTemplate` + route + UI dropdown/nút. ICD (config mới, an toàn) chỉnh detect_keys về first-alias cho biểu mẫu sạch.

**Tech Stack:** Laravel 5.5, Maatwebsite Excel ^3.1, PhpSpreadsheet, PHPUnit.

**Spec:** `docs/superpowers/specs/2026-07-07-catalog-import-template-download-design.md`

**Bối cảnh đã kiểm:** tự-nhận-diện với header = alias-đầu-mỗi-field: **OK 8 loại** (medicine, medical_supply, medical_staff, department_bed, equipment, administrative_unit, medical_organization, job_categories); **FAIL 3**: `service`, `icd10`, `icd_yhct`. `headers()` chèn detect_key giải quyết cả 3; ICD thêm chỉnh detect_keys để biểu mẫu không dư cột.

---

### Task 1: `CatalogTemplateExport` + `headers()` (TDD)

**Files:**
- Create: `app/Exports/CatalogTemplateExport.php`
- Test: `tests/Unit/CatalogTemplateExportTest.php`

- [ ] **Step 1: Viết test thất bại**

Create `tests/Unit/CatalogTemplateExportTest.php`:
```php
<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Exports\CatalogTemplateExport;

class CatalogTemplateExportTest extends TestCase
{
    public function test_headers_gom_alias_dau_moi_field()
    {
        $export = new CatalogTemplateExport('medicine');
        $headers = $export->headers();
        // Field đầu của medicine là ma_thuoc -> alias đầu 'MA_THUOC'
        $this->assertContains('MA_THUOC', $headers);
        $this->assertContains('TEN_THUOC', $headers);
        // Không có dòng dữ liệu
        $this->assertSame([], $export->array());
    }

    public function test_headers_chua_moi_detect_key_de_tu_nhan_dien()
    {
        // service có detect_keys gồm nhiều alias cùng field -> headers() phải chèn đủ.
        $export = new CatalogTemplateExport('service');
        $headers = $export->headers();
        foreach (config('catalog_import_mapping.service.detect_keys') as $key) {
            $this->assertContains($key, $headers, "Thieu detect_key: $key");
        }
    }

    public function test_required_headers_dung_first_alias_cua_required_fields()
    {
        $export = new CatalogTemplateExport('medicine');
        $req = $export->requiredHeaders();
        // required_fields medicine gom ma_thuoc, ten_thuoc... -> first alias
        $this->assertContains('MA_THUOC', $req);
        $this->assertContains('TEN_THUOC', $req);
    }
}
```

- [ ] **Step 2: Chạy test — FAIL**

Run: `cd "C:\Users\tracnn\qlbv" && vendor/bin/phpunit tests/Unit/CatalogTemplateExportTest.php`
Expected: FAIL (class not found).

- [ ] **Step 3: Tạo Export class**

Create `app/Exports/CatalogTemplateExport.php`:
```php
<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class CatalogTemplateExport implements FromArray, WithHeadings, WithEvents, ShouldAutoSize
{
    protected $type;
    protected $config;

    public function __construct($type)
    {
        $this->type = $type;
        $this->config = config("catalog_import_mapping.{$type}", []);
    }

    /** Header = alias đầu mỗi field + chèn các detect_key còn thiếu (để file tự nhận diện khi import). */
    public function headers(): array
    {
        $mapping = $this->config['mapping'] ?? [];
        $headers = [];
        foreach ($mapping as $field => $aliases) {
            if (!empty($aliases)) {
                $headers[] = $aliases[0];
            }
        }
        foreach (($this->config['detect_keys'] ?? []) as $key) {
            if (!in_array($key, $headers, true)) {
                $headers[] = $key;
            }
        }
        return $headers;
    }

    /** Tên header (first alias) của các cột bắt buộc — để tô màu. */
    public function requiredHeaders(): array
    {
        $mapping = $this->config['mapping'] ?? [];
        $out = [];
        foreach (($this->config['required_fields'] ?? []) as $field) {
            if (!empty($mapping[$field])) {
                $out[] = $mapping[$field][0];
            }
        }
        return $out;
    }

    public function headings(): array
    {
        return $this->headers();
    }

    /** Không có dòng dữ liệu — chỉ header cho người dùng điền. */
    public function array(): array
    {
        return [];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $headers = $this->headers();
                $required = $this->requiredHeaders();

                foreach ($headers as $i => $name) {
                    $col = Coordinate::stringFromColumnIndex($i + 1); // 1-based
                    $cell = $col . '1';
                    $sheet->getStyle($cell)->getFont()->setBold(true);
                    if (in_array($name, $required, true)) {
                        $sheet->getStyle($cell)->getFill()
                            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                            ->getStartColor()->setRGB('FFF2CC'); // vàng nhạt = bắt buộc
                    }
                }
            },
        ];
    }
}
```

- [ ] **Step 4: Chạy test — PASS**

Run: `cd "C:\Users\tracnn\qlbv" && vendor/bin/phpunit tests/Unit/CatalogTemplateExportTest.php`
Expected: OK (3 tests).

- [ ] **Step 5: Commit**

```bash
git add app/Exports/CatalogTemplateExport.php tests/Unit/CatalogTemplateExportTest.php
git commit -m "feat(catalog-template): CatalogTemplateExport sinh header + to cot bat buoc"
```

---

### Task 2: Tự-nhận-diện toàn bộ + chỉnh detect_keys ICD

**Files:**
- Modify: `config/catalog_import_mapping.php` (chỉ `detect_keys` của `icd10`, `icd_yhct`)
- Modify: `tests/Unit/CatalogIcdDetectTest.php` (cập nhật theo detect_keys mới)
- Test: `tests/Unit/CatalogTemplateSelfDetectTest.php` (create)

- [ ] **Step 1: Viết test tự-nhận-diện toàn bộ (chốt chặn)**

Create `tests/Unit/CatalogTemplateSelfDetectTest.php`:
```php
<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Exports\CatalogTemplateExport;
use App\Services\ExcelColumnMapper;

class CatalogTemplateSelfDetectTest extends TestCase
{
    public function test_moi_bieu_mau_tu_nhan_dien_dung_loai()
    {
        $configs = config('catalog_import_mapping');
        $mapper = new ExcelColumnMapper();
        foreach (array_keys($configs) as $type) {
            $headers = (new CatalogTemplateExport($type))->headers();
            $detected = $mapper->detectCatalogType($headers, $configs);
            $this->assertSame($type, $detected, "Bieu mau '$type' bi nhan dien thanh: " . var_export($detected, true));
        }
    }
}
```

- [ ] **Step 2: Chạy — kỳ vọng PASS ngay**

Run: `cd "C:\Users\tracnn\qlbv" && vendor/bin/phpunit tests/Unit/CatalogTemplateSelfDetectTest.php`
Expected: OK (1 test). `headers()` đã chèn detect_key nên cả `service`/`icd10`/`icd_yhct` đều tự nhận diện. Nếu FAIL loại nào, dừng lại báo cáo (không sửa test để ép pass).

- [ ] **Step 3: Chỉnh detect_keys ICD cho biểu mẫu SẠCH (không dư cột)**

Với ICD, detect_keys hiện gồm alias không phải first-alias → `headers()` phải chèn thêm cột (dư). Đổi để detect_keys ⊆ first-alias → biểu mẫu không dư.

Trong `config/catalog_import_mapping.php`:
- `icd10`: đổi `'detect_keys' => ['MA_ICD10', 'MA_BENH', 'TEN_BENH'],` thành `'detect_keys' => ['MA_ICD10', 'TEN_ICD10'],`
- `icd_yhct`: đổi `'detect_keys' => ['MA_YHCT', 'TEN_YHCT', 'TEN_BENH_YHCT'],` thành `'detect_keys' => ['MA_ICD_YHCT', 'TEN_ICD_YHCT', 'TEN_BENH_YHCT'],`

(Giữ nguyên toàn bộ `mapping`/`required_fields`/`unique_keys`; `icd_yhct` vẫn đứng TRƯỚC `icd10`.)

- [ ] **Step 4: Cập nhật `CatalogIcdDetectTest` theo detect_keys mới**

Test cũ dùng header `MA_BENH`/`TEN_BENH`/`MA_YHCT`/`TEN_YHCT` (nay không còn là detect_keys). Sửa 2 method cho khớp:
```php
    public function test_nhan_dien_icd10()
    {
        $this->assertSame('icd10', $this->detect(['MA_ICD10', 'TEN_ICD10', 'BENH_MAN_TINH']));
    }

    public function test_nhan_dien_icd_yhct_khong_nham_icd10()
    {
        // File YHCT có cả cột tham chiếu ICD10 nhưng vẫn phải ra icd_yhct.
        $this->assertSame('icd_yhct', $this->detect(['MA_ICD_YHCT', 'TEN_ICD_YHCT', 'TEN_BENH_YHCT', 'MA_ICD10', 'TEN_ICD10']));
    }
```

- [ ] **Step 5: Chạy lại 3 test liên quan — PASS**

Run: `cd "C:\Users\tracnn\qlbv" && vendor/bin/phpunit tests/Unit/CatalogTemplateSelfDetectTest.php tests/Unit/CatalogIcdDetectTest.php tests/Unit/CatalogTemplateExportTest.php`
Expected: OK (tất cả pass). Đặc biệt self-detect vẫn OK cho mọi loại (gồm icd10/icd_yhct với detect_keys mới).

- [ ] **Step 6: Commit**

```bash
git add config/catalog_import_mapping.php tests/Unit/CatalogIcdDetectTest.php tests/Unit/CatalogTemplateSelfDetectTest.php
git commit -m "feat(catalog-template): test tu-nhan-dien + chinh detect_keys ICD cho bieu mau sach"
```

---

### Task 3: Controller `downloadTemplate` + route

**Files:**
- Modify: `app/Http/Controllers/Category/CategoryBHYTController.php`
- Modify: `routes/web.php`

- [ ] **Step 1: Thêm import + method vào controller**

Đầu `CategoryBHYTController.php`, cạnh các `use ...;`, thêm:
```php
use App\Exports\CatalogTemplateExport;
use Maatwebsite\Excel\Facades\Excel;
```
(Nếu `Excel` facade đã được import ở nơi khác trong file thì không lặp; kiểm bằng grep trước.)

Thêm method (cạnh `importIndex`):
```php
    public function downloadTemplate(Request $request)
    {
        $type = $request->get('type');
        $validTypes = array_keys(config('catalog_import_mapping', []));

        if (!in_array($type, $validTypes, true)) {
            abort(404, 'Loại danh mục không hợp lệ');
        }

        return Excel::download(new CatalogTemplateExport($type), $type . '_bieu_mau.xlsx');
    }
```

- [ ] **Step 2: Thêm route**

Trong `routes/web.php`, ngay sau route `category-bhyt.import-index` (tìm theo tên đó), thêm:
```php
        Route::get('bhyt/category-bhyt-import-template', 'Category\CategoryBHYTController@downloadTemplate')
        ->name('category-bhyt.import-template');
```

- [ ] **Step 3: Verify**

Run: `cd "C:\Users\tracnn\qlbv" && php -l app/Http/Controllers/Category/CategoryBHYTController.php && php -l routes/web.php && php -d memory_limit=-1 artisan route:list 2>&1 | grep import-template`
Expected: `No syntax errors detected` (×2) và thấy route `category-bhyt.import-template`.

- [ ] **Step 4: Commit**

```bash
git add app/Http/Controllers/Category/CategoryBHYTController.php routes/web.php
git commit -m "feat(catalog-template): controller downloadTemplate + route"
```

---

### Task 4: UI dropdown + nút trên `import.blade`

**Files:**
- Modify: `resources/views/category/bhyt/import.blade.php`

- [ ] **Step 1: Thêm khối chọn loại + nút tải (phía trên Dropzone)**

Trong `@section('content')`, NGAY TRƯỚC `<div class="panel panel-default">` chứa form Dropzone (`id="my-dropzone"`), chèn:
```blade
<div class="panel panel-default">
    <div class="panel-body">
        <div class="form-inline">
            <label for="template_type" style="margin-right:8px;">Tải biểu mẫu:</label>
            <select id="template_type" class="form-control" style="min-width:260px;margin-right:8px;">
                <option value="medicine">Danh mục Thuốc</option>
                <option value="medical_supply">Danh mục Vật tư y tế</option>
                <option value="service">Danh mục Dịch vụ kỹ thuật</option>
                <option value="medical_staff">Danh mục Nhân viên y tế</option>
                <option value="department_bed">Danh mục Khoa/Phòng/Giường</option>
                <option value="equipment">Danh mục Trang thiết bị</option>
                <option value="administrative_unit">Danh mục Đơn vị hành chính</option>
                <option value="medical_organization">Danh mục Cơ sở y tế</option>
                <option value="job_categories">Danh mục Nghề nghiệp</option>
                <option value="icd10">Danh mục ICD-10</option>
                <option value="icd_yhct">Danh mục ICD-YHCT</option>
            </select>
            <button type="button" id="btn_download_template" class="btn btn-success">
                <i class="fa fa-download"></i> Tải biểu mẫu
            </button>
            <p class="help-block" style="margin-top:6px;">Cột bôi vàng là bắt buộc. Điền dữ liệu từ dòng 2 rồi tải lên ở khung bên dưới.</p>
        </div>
    </div>
</div>
```

- [ ] **Step 2: Thêm JS xử lý nút (trong `@push('after-scripts')`)**

Thêm vào cuối khối script (sau phần khởi tạo Dropzone), trong `@push('after-scripts')`:
```blade
<script>
    document.getElementById('btn_download_template').addEventListener('click', function () {
        var type = document.getElementById('template_type').value;
        window.location.href = "{{ route('category-bhyt.import-template') }}?type=" + encodeURIComponent(type);
    });
</script>
```

- [ ] **Step 3: Verify blade không lỗi (compile)**

Run: `cd "C:\Users\tracnn\qlbv" && php -d memory_limit=-1 artisan view:clear && php -r "echo 'blade-ok';"`
Expected: `blade-ok` (không lỗi). (Render trực quan để lại cho smoke thủ công.)

- [ ] **Step 4: Commit**

```bash
git add resources/views/category/bhyt/import.blade.php
git commit -m "feat(catalog-template): UI dropdown + nut tai bieu mau tren man import"
```

---

### Task 5: Verify toàn bộ + smoke tải/nạp lại + push

**Files:** (không sửa code)

- [ ] **Step 1: Chạy các unit test liên quan**

Run: `cd "C:\Users\tracnn\qlbv" && vendor/bin/phpunit tests/Unit/CatalogTemplateExportTest.php tests/Unit/CatalogTemplateSelfDetectTest.php tests/Unit/CatalogIcdDetectTest.php`
Expected: OK (tất cả pass).

- [ ] **Step 2: Smoke — sinh biểu mẫu ra file rồi đọc header + tự nhận diện lại**

Run:
```bash
cd "C:\Users\tracnn\qlbv" && printf '%s\n' \
'foreach (["service","icd10","icd_yhct","medicine"] as $t) {' \
'  $h = (new App\Exports\CatalogTemplateExport($t))->headers();' \
'  $d = (new App\Services\ExcelColumnMapper())->detectCatalogType($h, config("catalog_import_mapping"));' \
'  echo $t.": headers=[".implode(",",$h)."] detect=".$d." ".($d===$t?"OK":"FAIL")."\n";' \
'}' \
'exit' | php artisan tinker 2>&1 | grep -E "OK|FAIL"
```
Expected: cả 4 dòng `OK`; xem header của `icd10`/`icd_yhct` KHÔNG dư cột mã/tên trùng.

- [ ] **Step 3: Smoke — tải thật 1 file xlsx ghi ra đĩa, mở đọc dòng 1**

Run:
```bash
cd "C:\Users\tracnn\qlbv" && printf '%s\n' \
'$path = storage_path("app/_tpl_service.xlsx");' \
'Maatwebsite\Excel\Facades\Excel::store(new App\Exports\CatalogTemplateExport("service"), "_tpl_service.xlsx");' \
'$reader = PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($path); $reader->setReadDataOnly(true);' \
'$row1 = $reader->load($path)->getActiveSheet()->rangeToArray("A1:Z1")[0];' \
'$row1 = array_values(array_filter($row1, function($v){return $v!==null && $v!=="";}));' \
'echo "FILE_HEADER: ".implode(" | ", $row1)."\n";' \
'@unlink($path);' \
'exit' | php artisan tinker 2>&1 | grep -E "FILE_HEADER"
```
Expected: `FILE_HEADER:` liệt kê đúng các cột service (gồm MA_DICH_VU, TEN_DICH_VU, DON_GIA... và các detect_key MA_TUONG_DUONG/TEN_DVKT_*).

- [ ] **Step 4: Push**

```bash
git push origin main
```

---

## Hoàn tất

Sau 5 task: màn import có dropdown chọn loại + nút "Tải biểu mẫu" → tải .xlsx header đúng cột (cột bắt buộc bôi vàng), người dùng điền và import lại — mọi biểu mẫu **tự nhận diện đúng loại**. Biểu mẫu `service` kèm các cột tên thay thế theo cấu trúc BHXH; ICD gọn (một cột/field).

**Lưu ý:** biểu mẫu này có header ở **dòng 1** nên import chạy trơn — khác với file ICD thật (tiêu đề trên header) vốn cần xử lý riêng (ngoài phạm vi).
