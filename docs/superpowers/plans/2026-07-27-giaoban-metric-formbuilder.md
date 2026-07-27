# Form builder chỉ tiêu giao ban — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Thay ô JSON thô cấu hình chỉ tiêu giao ban bằng form builder dữ liệu-hoá từ một schema duy nhất, có tra danh mục HIS theo tên, validate siết phía server, tính thử trước khi lưu, thư viện mẫu trong DB, và chỉ tiêu nhập tay có đơn vị/ràng buộc/kế thừa kỳ trước.

**Architecture:** `MetricSchema` (mảng khai báo PHP) là nguồn sự thật duy nhất, được ba phía tiêu thụ: `MetricValidator` chặn payload sai, form builder JS render field động từ chính mảng đó (nhận qua `@json`), và một test đối chiếu schema với câu `switch` trong `GiaoBanMetricService::computeAll`. `GiaoBanCatalogService` dịch ID HIS ↔ tên cho các dropdown. **`GiaoBanMetricService.php` không sửa một dòng nào** — toàn bộ việc này là lớp cấu hình phía trên, không đụng logic tính số.

**Tech Stack:** Laravel 5.5, PHP >= 7.0, Oracle qua connection `HISPro` (oci8), AdminLTE + jQuery + select2 (đã có sẵn), PHPUnit + Mockery.

**Spec:** `docs/superpowers/specs/2026-07-27-giaoban-metric-formbuilder-design.md`

## Global Constraints

- **Không sửa `app/Services/GiaoBan/GiaoBanMetricService.php`.** Nếu một task tưởng như cần sửa file này, dừng lại và báo — đó là dấu hiệu thiết kế sai chỗ khác.
- **Laravel 5.5:** `Cache::put($key, $value, $minutes)` nhận **phút**, không phải giây. `Route::post(...)` khai trong `routes/web.php` trong nhóm hiện có ở dòng ~649-671.
- **PHP >= 7.0:** không dùng arrow function (`fn`), không dùng typed property, không dùng `??=`, không trailing comma trong tham số hàm. Mảng hằng trong class dùng `const X = [...]` (PHP 7.0 hỗ trợ).
- **Oracle trả tên cột HOA** — mọi row lấy từ connection `HISPro` phải qua `array_change_key_case((array) $row, CASE_LOWER)` trước khi dùng.
- **Mọi truy vấn HIS bọc `try/catch (\Exception $e)`** và trả mảng rỗng khi lỗi, theo khuôn `GiaoBanConfigController::searchUsers` (dòng 74-97). HIS lỗi không được làm trắng trang cấu hình.
- **Không mock bằng Mockery cho method có khai báo return type** — hỏng ở phiên bản PHPUnit/Mockery của dự án. Dùng dữ liệu thật, fake object, hoặc test hàm thuần.
- **Test hàm thuần là ưu tiên số một.** Khuôn mẫu đã có: `tests/Unit/GiaoBan/GiaoBanReportServiceTest.php` test toàn static method, không chạm DB. Feature test theo khuôn `tests/Feature/RevenueDeptRoomControllerTest.php` (class `FakeXAdminUser extends \App\User` override `can()`, rồi `actingAs`).
- **Chạy test:** `vendor/bin/phpunit --filter <TenTest>`. Suite đầy đủ: `vendor/bin/phpunit`.
- **Dự án không có hạ tầng test JS** (không có `package.json`). Task JS verify bằng trình duyệt với bước quan sát cụ thể ghi trong task.
- **Ngôn ngữ:** comment và message lỗi bằng tiếng Việt, theo đúng văn phong file hiện có.
- **Commit message:** tiếng Việt không dấu, prefix `feat:` / `test:` / `fix:` / `refactor:` theo khuôn lịch sử repo.

---

## Cấu trúc file

**Tạo mới:**

| File | Trách nhiệm |
|---|---|
| `app/Services/GiaoBan/MetricSchema.php` | Mảng khai báo 11 loại chỉ tiêu + danh mục nào dùng cho field nào. Không có logic truy vấn. |
| `app/Services/GiaoBan/MetricValidator.php` | Hàm thuần: nhận mảng metrics + block_type, trả danh sách lỗi có `index`/`field`/`message`. |
| `app/Services/GiaoBan/GiaoBanCatalogService.php` | Dịch ID HIS ↔ tên. Nhóm nhỏ có cache, nhóm lớn tìm theo `q` hoặc tra ngược theo `ids`. |
| `app/Console/Commands/GiaoBanKiemTraChiTieu.php` | Quét `giaoban_dept_configs` hiện có, in bản ghi không đạt schema. |
| `app/Models/GiaoBan/GiaoBanMetricTemplate.php` | Model bảng mẫu chỉ tiêu. |
| `database/migrations/2026_07_27_100000_create_giaoban_metric_templates_table.php` | Bảng mẫu + seed 5 mẫu đang nhúng cứng trong blade. |
| `resources/views/khth/partials/giaoban-metric-builder.blade.php` | Markup modal (chỉ HTML, không logic). |
| `public/js/giaoban/metric-builder.js` | Module `MetricBuilder`: `open(config)`, `getMetrics()`, `on('save', fn)`. |
| `tests/Unit/GiaoBan/MetricSchemaTest.php` | Đối chiếu schema ↔ `computeAll`. |
| `tests/Unit/GiaoBan/MetricValidatorTest.php` | Từng nhánh validate. |
| `tests/Unit/GiaoBan/GiaoBanCatalogServiceTest.php` | Khai báo danh mục + dựng SQL. |
| `tests/Feature/GiaoBan/GiaoBanConfigControllerTest.php` | 422 có `index`/`field`, endpoint danh mục, tính thử. |

**Sửa:**

| File | Thay đổi |
|---|---|
| `app/Http/Controllers/KHTH/GiaoBanConfigController.php` | Siết validate, endpoint danh mục, tính thử, CRUD mẫu |
| `routes/web.php` | 6 route mới trong nhóm dòng ~663-671 |
| `resources/views/khth/giaoban-config.blade.php` | Cột JSON → nút, nhúng partial, bỏ 5 template cứng |
| `resources/views/khth/giaoban-index.blade.php` | Render ô nhập tay theo `input` khai báo |
| `app/Http/Controllers/KHTH/GiaoBanController.php` | `saveCell` kiểm ràng buộc phía server |
| `app/Services/GiaoBan/GiaoBanReportService.php` | Hàm thuần `carryOverManualValues` + nối vào `fetchAndStore` |
| `app/Models/GiaoBan/GiaoBanDeptConfig.php` | Thêm `metricByCode($code)` để `saveCell` tra khai báo |

---

## Task 1: Baseline test + xác minh tên cột danh mục HIS

Task này không viết logic, nhưng nó chặn mọi task sau khỏi xây trên giả định sai. Kết thúc bằng một hằng khai báo đã được kiểm chứng bằng truy vấn thật.

**Files:**
- Create: `app/Services/GiaoBan/GiaoBanCatalogService.php` (chỉ phần hằng `CATALOGS`)
- Test: `tests/Unit/GiaoBan/GiaoBanCatalogServiceTest.php`

**Interfaces:**
- Produces: `GiaoBanCatalogService::CATALOGS` — mảng `key => ['table', 'id_col', 'name_col', 'remote' => bool]`; `GiaoBanCatalogService::isRemote($key)`, `GiaoBanCatalogService::smallKeys()`, `GiaoBanCatalogService::allKeys()`.

- [ ] **Step 1: Chốt danh sách test đang đỏ sẵn**

Chạy: `vendor/bin/phpunit`

Ghi lại tên các test FAIL vào một file nháp tạm (không commit). Repo này **có test đỏ sẵn từ trước**; không chốt baseline thì cuối việc không phân biệt được đỏ nào do mình gây ra.

- [ ] **Step 2: Truy vấn xác minh tên cột 5 bảng danh mục**

Chạy trên connection `HISPro` (dùng MCP `sqlcl`, hoặc `php artisan tinker`):

```sql
SELECT table_name, column_name FROM all_tab_columns
WHERE table_name IN ('HIS_SERVICE_TYPE','HIS_DIIM_TYPE','HIS_TEST_TYPE',
                     'HIS_PATIENT_TYPE','HIS_TREATMENT_TYPE','HIS_TREATMENT_END_TYPE',
                     'HIS_SERVICE','HIS_ROOM','HIS_BED','HIS_BED_ROOM')
  AND column_name LIKE '%CODE' OR column_name LIKE '%NAME'
ORDER BY table_name, column_name;
```

Quy ước dự đoán là `<tên_bảng>_code` / `<tên_bảng>_name` (đã xác nhận đúng với `his_treatment_end_type` tại `app/Http/Controllers/Category/CategoryHISController.php:85`). **Ghi lại tên cột thật**; nếu lệch dự đoán, dùng tên thật ở Step 4 và ghi chú lệch vào commit message.

- [ ] **Step 3: Viết test khai báo danh mục (đỏ)**

```php
<?php

namespace Tests\Unit\GiaoBan;

use Tests\TestCase;
use App\Services\GiaoBan\GiaoBanCatalogService;

class GiaoBanCatalogServiceTest extends TestCase
{
    /** @test */
    public function khai_bao_du_9_danh_muc_va_moi_muc_co_bang_cot_id_cot_ten()
    {
        $c = GiaoBanCatalogService::CATALOGS;

        $this->assertCount(9, $c);
        foreach (['service_type', 'diim_type', 'test_type', 'patient_type', 'treatment_type',
                  'end_type', 'service', 'room', 'bed'] as $key) {
            $this->assertArrayHasKey($key, $c, "Thieu danh muc $key");
            $this->assertArrayHasKey('table', $c[$key]);
            $this->assertArrayHasKey('id_col', $c[$key]);
            $this->assertArrayHasKey('name_col', $c[$key]);
        }
    }

    /** @test */
    public function ba_danh_muc_lon_duoc_danh_dau_remote_con_lai_thi_khong()
    {
        $this->assertEquals(['service', 'room', 'bed'],
            array_values(array_diff(GiaoBanCatalogService::allKeys(), GiaoBanCatalogService::smallKeys())));

        $this->assertTrue(GiaoBanCatalogService::isRemote('service'));
        $this->assertFalse(GiaoBanCatalogService::isRemote('diim_type'));
    }

    /** @test */
    public function end_type_dinh_danh_bang_code_khong_phai_id()
    {
        // metric end_type luu ["RV","CV"] chu khong luu id -> phai danh dau rieng
        $this->assertEquals('treatment_end_type_code', GiaoBanCatalogService::CATALOGS['end_type']['id_col']);
    }
}
```

- [ ] **Step 4: Chạy test, xác nhận đỏ**

Chạy: `vendor/bin/phpunit --filter GiaoBanCatalogServiceTest`
Kỳ vọng: FAIL — `Class 'App\Services\GiaoBan\GiaoBanCatalogService' not found`

- [ ] **Step 5: Viết hằng khai báo**

Thay `name_col` bằng **tên cột thật lấy ở Step 2** nếu khác dự đoán:

```php
<?php

namespace App\Services\GiaoBan;

/**
 * Dich ID danh muc HIS <-> ten hien thi cho form builder chi tieu giao ban.
 * Nhom nho: tai tron goi + cache. Nhom lon (remote): tim theo q, hoac tra nguoc theo ids.
 */
class GiaoBanCatalogService
{
    const CONN = 'HISPro';

    /** key => bang HIS, cot dinh danh, cot ten, co phai danh muc lon hay khong */
    const CATALOGS = [
        'service_type'   => ['table' => 'his_service_type',       'id_col' => 'id', 'name_col' => 'service_type_name',       'remote' => false, 'label' => 'Loại dịch vụ'],
        'diim_type'      => ['table' => 'his_diim_type',          'id_col' => 'id', 'name_col' => 'diim_type_name',          'remote' => false, 'label' => 'Loại CĐHA'],
        'test_type'      => ['table' => 'his_test_type',          'id_col' => 'id', 'name_col' => 'test_type_name',          'remote' => false, 'label' => 'Loại xét nghiệm'],
        'patient_type'   => ['table' => 'his_patient_type',       'id_col' => 'id', 'name_col' => 'patient_type_name',       'remote' => false, 'label' => 'Đối tượng BN'],
        'treatment_type' => ['table' => 'his_treatment_type',     'id_col' => 'id', 'name_col' => 'treatment_type_name',     'remote' => false, 'label' => 'Loại điều trị'],
        // end_type dinh danh bang CODE ('RV','CV'...) vi metric luu code chu khong luu id
        'end_type'       => ['table' => 'his_treatment_end_type', 'id_col' => 'treatment_end_type_code', 'name_col' => 'treatment_end_type_name', 'remote' => false, 'label' => 'Loại kết thúc'],
        'service'        => ['table' => 'his_service',            'id_col' => 'id', 'name_col' => 'service_name',            'remote' => true,  'label' => 'Dịch vụ'],
        'room'           => ['table' => 'his_room',               'id_col' => 'id', 'name_col' => 'room_name',               'remote' => true,  'label' => 'Phòng thực hiện'],
        'bed'            => ['table' => 'his_bed',                'id_col' => 'id', 'name_col' => 'bed_name',                'remote' => true,  'label' => 'Giường'],
    ];

    public static function allKeys()
    {
        return array_keys(self::CATALOGS);
    }

    /** Cac danh muc tai tron goi khi mo modal. */
    public static function smallKeys()
    {
        $out = [];
        foreach (self::CATALOGS as $k => $c) {
            if (!$c['remote']) $out[] = $k;
        }
        return $out;
    }

    public static function isRemote($key)
    {
        return isset(self::CATALOGS[$key]) && self::CATALOGS[$key]['remote'];
    }
}
```

- [ ] **Step 6: Chạy test, xác nhận xanh**

Chạy: `vendor/bin/phpunit --filter GiaoBanCatalogServiceTest`
Kỳ vọng: PASS (3 test)

- [ ] **Step 7: Commit**

```bash
git add app/Services/GiaoBan/GiaoBanCatalogService.php tests/Unit/GiaoBan/GiaoBanCatalogServiceTest.php
git commit -m "feat(giaoban): khai bao danh muc HIS cho form builder chi tieu"
```

---

## Task 2: `MetricSchema` — registry 11 loại chỉ tiêu

**Files:**
- Create: `app/Services/GiaoBan/MetricSchema.php`
- Test: `tests/Unit/GiaoBan/MetricSchemaTest.php`

**Interfaces:**
- Consumes: `GiaoBanCatalogService::CATALOGS` (Task 1) — chỉ để test đối chiếu khoá `catalog` là hợp lệ.
- Produces:
  - `MetricSchema::TYPES` — mảng `type => ['label', 'blocks' => [], 'fields' => [], 'filter' => [], 'scope' => ?]`
  - `MetricSchema::typeKeys()` → `string[]`
  - `MetricSchema::forBlock($blockType)` → mảng type hợp lệ với khối đó
  - `MetricSchema::has($type)` → bool
  - `MetricSchema::get($type)` → mảng khai báo hoặc `null`

- [ ] **Step 1: Viết test (đỏ)**

Test quan trọng nhất là test đối chiếu — nó đọc mã nguồn `GiaoBanMetricService` để bảo đảm registry không lệch với câu `switch` thật:

```php
<?php

namespace Tests\Unit\GiaoBan;

use Tests\TestCase;
use App\Services\GiaoBan\MetricSchema;
use App\Services\GiaoBan\GiaoBanCatalogService;

class MetricSchemaTest extends TestCase
{
    /** @test */
    public function moi_case_trong_computeAll_deu_co_trong_registry_va_nguoc_lai()
    {
        $src = file_get_contents(app_path('Services/GiaoBan/GiaoBanMetricService.php'));
        // Lay cac 'case' trong switch cua computeAll: case 'census_from':
        preg_match_all("/case\s+'([a-z_]+)'\s*:/", $src, $m);
        $casesTrongService = array_values(array_unique($m[1]));

        // 'manual' xu ly o nhanh default nen khong xuat hien duoi dang case rieng
        $trongRegistry = MetricSchema::typeKeys();

        $thieu = array_diff($casesTrongService, $trongRegistry);
        $thua  = array_diff($trongRegistry, array_merge($casesTrongService, ['manual']));

        $this->assertEmpty($thieu, 'Registry thieu type: ' . implode(', ', $thieu));
        $this->assertEmpty($thua, 'Registry thua type khong ai tinh: ' . implode(', ', $thua));
        $this->assertContains('manual', $trongRegistry, 'Registry phai co type manual');
    }

    /** @test */
    public function loc_type_theo_khoi()
    {
        $dieuTri = MetricSchema::forBlock('dieu_tri');
        $this->assertContains('census_from', $dieuTri);
        $this->assertNotContains('service_count', $dieuTri);

        $cls = MetricSchema::forBlock('can_lam_sang');
        $this->assertContains('service_count', $cls);
        $this->assertNotContains('census_from', $cls);

        $kham = MetricSchema::forBlock('kham');
        $this->assertContains('exam_visit', $kham);

        // manual dung duoc o moi khoi
        foreach (['dieu_tri', 'kham', 'can_lam_sang'] as $b) {
            $this->assertContains('manual', MetricSchema::forBlock($b), "manual phai dung duoc o khoi $b");
        }
    }

    /** @test */
    public function moi_field_tham_chieu_danh_muc_deu_tro_toi_danh_muc_co_that()
    {
        $keys = GiaoBanCatalogService::allKeys();
        foreach (MetricSchema::TYPES as $type => $def) {
            $nhom = array_merge(
                isset($def['fields']) ? $def['fields'] : [],
                isset($def['filter']) ? $def['filter'] : []
            );
            foreach ($nhom as $field => $meta) {
                if (!isset($meta['catalog'])) continue;
                $this->assertContains($meta['catalog'], $keys,
                    "Type $type field $field tro toi danh muc khong ton tai: {$meta['catalog']}");
            }
        }
    }

    /** @test */
    public function service_count_khai_day_du_khoa_filter_ma_service_that_su_doc()
    {
        $filter = MetricSchema::TYPES['service_count']['filter'];
        foreach (['service_type_ids', 'diim_type_ids', 'test_type_ids', 'service_ids',
                  'execute_room_ids', 'priority_min', 'priority_max'] as $k) {
            $this->assertArrayHasKey($k, $filter, "Thieu khoa filter $k");
        }
        // nhom "Khac" khai bang other_key chu khong phai khoa rieng
        $this->assertEquals('diim_type_other_of', $filter['diim_type_ids']['other_key']);
        $this->assertEquals('test_type_other_of', $filter['test_type_ids']['other_key']);
    }

    /** @test */
    public function manual_khai_du_thuoc_tinh_o_nhap_tay()
    {
        $fields = MetricSchema::TYPES['manual']['fields'];
        foreach (['unit', 'hint', 'value_type', 'min', 'max', 'required', 'default', 'carry_over'] as $k) {
            $this->assertArrayHasKey($k, $fields, "Thieu thuoc tinh nhap tay $k");
        }
        $this->assertEquals(['int', 'decimal', 'percent'], $fields['value_type']['options']);
    }
}
```

- [ ] **Step 2: Chạy test, xác nhận đỏ**

Chạy: `vendor/bin/phpunit --filter MetricSchemaTest`
Kỳ vọng: FAIL — `Class 'App\Services\GiaoBan\MetricSchema' not found`

- [ ] **Step 3: Viết registry**

```php
<?php

namespace App\Services\GiaoBan;

/**
 * Nguon su that duy nhat ve cau truc mot chi tieu giao ban.
 * Ba noi tieu thu: MetricValidator (chan payload sai), form builder JS (render field dong),
 * MetricSchemaTest (doi chieu voi switch trong GiaoBanMetricService::computeAll).
 *
 * Them loai chi tieu moi: sua o day, form + validate tu co.
 */
class MetricSchema
{
    const BLOCKS = ['dieu_tri', 'kham', 'can_lam_sang'];

    const TYPES = [
        'census_from' => [
            'label' => 'BN cũ (đầu kỳ)', 'blocks' => ['dieu_tri'], 'fields' => [], 'filter' => [],
        ],
        'census_to' => [
            'label' => 'Hiện có (cuối kỳ)', 'blocks' => ['dieu_tri'], 'fields' => [], 'filter' => [],
        ],
        'movement_in' => [
            'label' => 'BN vào thẳng', 'blocks' => ['dieu_tri'], 'fields' => [], 'filter' => [],
        ],
        'movement_transfer_in' => [
            'label' => 'BN chuyển đến', 'blocks' => ['dieu_tri'], 'fields' => [], 'filter' => [],
        ],
        'movement_transfer_out' => [
            'label' => 'BN chuyển khoa (đi)', 'blocks' => ['dieu_tri'], 'fields' => [], 'filter' => [],
        ],
        'end_type' => [
            'label' => 'Kết thúc điều trị', 'blocks' => ['dieu_tri'],
            'fields' => [
                'end_codes' => [
                    'widget' => 'catalog_multi', 'catalog' => 'end_type', 'value' => 'string',
                    'required' => true, 'label' => 'Loại kết thúc',
                ],
            ],
            'filter' => [],
        ],
        'bed_count' => [
            'label' => 'Đếm BN trên giường chỉ định', 'blocks' => ['dieu_tri'],
            'fields' => [
                'bed_ids' => [
                    'widget' => 'catalog_multi', 'catalog' => 'bed', 'value' => 'int',
                    'required' => true, 'label' => 'Giường',
                ],
            ],
            'filter' => [],
        ],
        'exam_visit' => [
            'label' => 'Lượt khám', 'blocks' => ['kham'], 'fields' => [],
            'filter' => [
                'treatment_type_ids' => ['widget' => 'catalog_multi', 'catalog' => 'treatment_type', 'value' => 'int', 'label' => 'Loại điều trị'],
                'patient_type_ids'   => ['widget' => 'catalog_multi', 'catalog' => 'patient_type', 'value' => 'int', 'label' => 'Đối tượng BN'],
                'end_type_codes'     => ['widget' => 'catalog_multi', 'catalog' => 'end_type', 'value' => 'string', 'label' => 'Loại kết thúc'],
            ],
        ],
        'service_count' => [
            'label' => 'Đếm dịch vụ', 'blocks' => ['can_lam_sang'], 'fields' => [],
            'scope' => 'service_dept', // widget pham vi khoa rieng, xem MetricValidator::SCOPE_*
            'filter' => [
                'service_type_ids' => ['widget' => 'catalog_multi', 'catalog' => 'service_type', 'value' => 'int', 'label' => 'Loại dịch vụ'],
                'diim_type_ids'    => ['widget' => 'catalog_multi', 'catalog' => 'diim_type', 'value' => 'int', 'label' => 'Loại CĐHA', 'other_key' => 'diim_type_other_of'],
                'test_type_ids'    => ['widget' => 'catalog_multi', 'catalog' => 'test_type', 'value' => 'int', 'label' => 'Loại xét nghiệm', 'other_key' => 'test_type_other_of'],
                'service_ids'      => ['widget' => 'catalog_multi', 'catalog' => 'service', 'value' => 'int', 'label' => 'Dịch vụ cụ thể'],
                'execute_room_ids' => ['widget' => 'catalog_multi', 'catalog' => 'room', 'value' => 'int', 'label' => 'Phòng thực hiện'],
                'priority_min'     => ['widget' => 'int', 'label' => 'Ưu tiên từ'],
                'priority_max'     => ['widget' => 'int', 'label' => 'Ưu tiên đến'],
            ],
        ],
        'admission' => [
            'label' => 'BN nhập viện nội trú (toàn viện)', 'blocks' => ['kham'], 'fields' => [], 'filter' => [],
        ],
        'manual' => [
            'label' => 'Nhập tay', 'blocks' => ['dieu_tri', 'kham', 'can_lam_sang'],
            'group' => 'input', // toan bo thuoc tinh nam trong khoa con "input"
            'fields' => [
                'unit'       => ['widget' => 'text', 'label' => 'Đơn vị', 'max' => 20],
                'hint'       => ['widget' => 'text', 'label' => 'Giải thích cho khoa', 'max' => 255],
                'value_type' => ['widget' => 'select', 'label' => 'Kiểu giá trị', 'options' => ['int', 'decimal', 'percent'], 'default' => 'int'],
                'min'        => ['widget' => 'number', 'label' => 'Nhỏ nhất'],
                'max'        => ['widget' => 'number', 'label' => 'Lớn nhất'],
                'required'   => ['widget' => 'bool', 'label' => 'Bắt buộc nhập'],
                'default'    => ['widget' => 'number', 'label' => 'Giá trị mặc định'],
                'carry_over' => ['widget' => 'bool', 'label' => 'Kế thừa từ phiên trước'],
            ],
            'filter' => [],
        ],
    ];

    public static function typeKeys()
    {
        return array_keys(self::TYPES);
    }

    public static function has($type)
    {
        return isset(self::TYPES[$type]);
    }

    public static function get($type)
    {
        return isset(self::TYPES[$type]) ? self::TYPES[$type] : null;
    }

    /** Cac type dung duoc voi mot block_type. */
    public static function forBlock($blockType)
    {
        $out = [];
        foreach (self::TYPES as $k => $def) {
            if (in_array($blockType, $def['blocks'], true)) $out[] = $k;
        }
        return $out;
    }
}
```

- [ ] **Step 4: Chạy test, xác nhận xanh**

Chạy: `vendor/bin/phpunit --filter MetricSchemaTest`
Kỳ vọng: PASS (5 test)

Nếu test đối chiếu báo "Registry thiếu type", nghĩa là `computeAll` có `case` mà registry chưa khai — thêm vào registry, **không** sửa `GiaoBanMetricService`.

- [ ] **Step 5: Commit**

```bash
git add app/Services/GiaoBan/MetricSchema.php tests/Unit/GiaoBan/MetricSchemaTest.php
git commit -m "feat(giaoban): MetricSchema lam nguon su that cho chi tieu giao ban"
```

---

## Task 3: `MetricValidator` — chặn payload sai

**Files:**
- Create: `app/Services/GiaoBan/MetricValidator.php`
- Test: `tests/Unit/GiaoBan/MetricValidatorTest.php`

**Interfaces:**
- Consumes: `MetricSchema::TYPES`, `MetricSchema::has()`, `MetricSchema::get()`, `MetricSchema::forBlock()` (Task 2).
- Produces:
  - `MetricValidator::validate(array $metrics, $blockType)` → `array` lỗi, mỗi lỗi `['index' => int, 'field' => string, 'message' => string]`. Mảng rỗng = hợp lệ. `index` là vị trí chỉ tiêu (từ 0); `index = -1` là lỗi cấp toàn danh sách.
  - `MetricValidator::validateJson($jsonString, $blockType)` → cùng dạng, thêm lỗi `index = -1, field = 'metrics'` khi JSON hỏng.
  - `MetricValidator::SCOPE_KEYS` — các khoá phạm vi khoa của `service_count`.

- [ ] **Step 1: Viết test (đỏ)**

```php
<?php

namespace Tests\Unit\GiaoBan;

use Tests\TestCase;
use App\Services\GiaoBan\MetricValidator;

class MetricValidatorTest extends TestCase
{
    protected function hopLe()
    {
        return [
            ['code' => 'bn_cu', 'name' => 'BN cũ', 'type' => 'census_from'],
            ['code' => 'bn_ra_vien', 'name' => 'BN ra viện', 'type' => 'end_type', 'end_codes' => ['RV', 'CV']],
        ];
    }

    /** @test */
    public function bo_chi_tieu_hop_le_khong_co_loi()
    {
        $this->assertSame([], MetricValidator::validate($this->hopLe(), 'dieu_tri'));
    }

    /** @test */
    public function danh_sach_rong_bi_chan()
    {
        $loi = MetricValidator::validate([], 'dieu_tri');
        $this->assertCount(1, $loi);
        $this->assertEquals(-1, $loi[0]['index']);
    }

    /** @test */
    public function code_trung_bi_chan_va_bao_dung_vi_tri()
    {
        $m = $this->hopLe();
        $m[1]['code'] = 'bn_cu';
        $loi = MetricValidator::validate($m, 'dieu_tri');

        $this->assertCount(1, $loi);
        $this->assertEquals(1, $loi[0]['index']);
        $this->assertEquals('code', $loi[0]['field']);
    }

    /** @test */
    public function code_sai_dinh_dang_bi_chan()
    {
        foreach (['BN_Cu', '1bn', 'bn cu', 'bn-cu', ''] as $xau) {
            $m = $this->hopLe();
            $m[0]['code'] = $xau;
            $loi = MetricValidator::validate($m, 'dieu_tri');
            $this->assertNotEmpty($loi, "Code '$xau' le ra phai bi chan");
            $this->assertEquals('code', $loi[0]['field']);
        }
    }

    /** @test */
    public function name_rong_bi_chan()
    {
        $m = $this->hopLe();
        $m[0]['name'] = '   ';
        $loi = MetricValidator::validate($m, 'dieu_tri');
        $this->assertEquals('name', $loi[0]['field']);
    }

    /** @test */
    public function type_la_bi_chan()
    {
        $m = $this->hopLe();
        $m[0]['type'] = 'khong_ton_tai';
        $loi = MetricValidator::validate($m, 'dieu_tri');
        $this->assertEquals('type', $loi[0]['field']);
    }

    /** @test */
    public function type_khong_hop_voi_khoi_bi_chan()
    {
        // census_from chi dung cho khoi dieu_tri
        $loi = MetricValidator::validate($this->hopLe(), 'can_lam_sang');
        $this->assertNotEmpty($loi);
        $this->assertEquals('type', $loi[0]['field']);
    }

    /** @test */
    public function field_bat_buoc_thieu_hoac_rong_bi_chan()
    {
        $m = [['code' => 'x', 'name' => 'X', 'type' => 'end_type']];
        $loi = MetricValidator::validate($m, 'dieu_tri');
        $this->assertEquals('end_codes', $loi[0]['field']);

        $m[0]['end_codes'] = [];
        $loi = MetricValidator::validate($m, 'dieu_tri');
        $this->assertEquals('end_codes', $loi[0]['field']);
    }

    /** @test */
    public function bed_ids_phai_la_mang_so_nguyen()
    {
        $m = [['code' => 'gyc', 'name' => 'Giường YC', 'type' => 'bed_count', 'bed_ids' => ['abc']]];
        $loi = MetricValidator::validate($m, 'dieu_tri');
        $this->assertEquals('bed_ids', $loi[0]['field']);
    }

    /** @test */
    public function khoa_filter_ngoai_whitelist_bi_chan()
    {
        $m = [['code' => 'dv', 'name' => 'DV', 'type' => 'service_count',
               'filter' => ['execute_department_id_self' => true, 'khoa_bia_dat' => [1]]]];
        $loi = MetricValidator::validate($m, 'can_lam_sang');
        $this->assertEquals('filter.khoa_bia_dat', $loi[0]['field']);
    }

    /** @test */
    public function khong_duoc_vua_khai_ids_vua_khai_nhom_khac()
    {
        $m = [['code' => 'dv', 'name' => 'DV', 'type' => 'service_count',
               'filter' => ['execute_department_id_self' => true,
                            'diim_type_ids' => [1], 'diim_type_other_of' => [1, 2]]]];
        $loi = MetricValidator::validate($m, 'can_lam_sang');
        $this->assertEquals('filter.diim_type_ids', $loi[0]['field']);
    }

    /** @test */
    public function khong_duoc_vua_tu_thuc_hien_vua_chi_dinh_khoa_cu_the()
    {
        $m = [['code' => 'dv', 'name' => 'DV', 'type' => 'service_count',
               'filter' => ['execute_department_id_self' => true, 'execute_room_ids' => [9]]]];
        $loi = MetricValidator::validate($m, 'can_lam_sang');
        $this->assertEquals('filter.execute_department_id_self', $loi[0]['field']);
    }

    /** @test */
    public function filter_cua_exam_visit_khong_nhan_khoa_cua_service_count()
    {
        $m = [['code' => 'lk', 'name' => 'Lượt khám', 'type' => 'exam_visit',
               'filter' => ['service_type_ids' => [2]]]];
        $loi = MetricValidator::validate($m, 'kham');
        $this->assertEquals('filter.service_type_ids', $loi[0]['field']);
    }

    /** @test */
    public function nhap_tay_min_lon_hon_max_bi_chan()
    {
        $m = [['code' => 'cg', 'name' => 'Chuyên gia', 'type' => 'manual',
               'input' => ['value_type' => 'int', 'min' => 10, 'max' => 5]]];
        $loi = MetricValidator::validate($m, 'kham');
        $this->assertEquals('input.min', $loi[0]['field']);
    }

    /** @test */
    public function nhap_tay_default_ngoai_khoang_bi_chan()
    {
        $m = [['code' => 'cg', 'name' => 'Chuyên gia', 'type' => 'manual',
               'input' => ['value_type' => 'int', 'min' => 0, 'max' => 10, 'default' => 99]]];
        $loi = MetricValidator::validate($m, 'kham');
        $this->assertEquals('input.default', $loi[0]['field']);
    }

    /** @test */
    public function nhap_tay_value_type_la_bi_chan()
    {
        $m = [['code' => 'cg', 'name' => 'Chuyên gia', 'type' => 'manual',
               'input' => ['value_type' => 'chuoi']]];
        $loi = MetricValidator::validate($m, 'kham');
        $this->assertEquals('input.value_type', $loi[0]['field']);
    }

    /** @test */
    public function nhap_tay_percent_gioi_han_0_100()
    {
        $m = [['code' => 'cs', 'name' => 'Công suất', 'type' => 'manual',
               'input' => ['value_type' => 'percent', 'max' => 150]]];
        $loi = MetricValidator::validate($m, 'kham');
        $this->assertEquals('input.max', $loi[0]['field']);
    }

    /** @test */
    public function nhap_tay_khoa_la_trong_input_bi_chan()
    {
        $m = [['code' => 'cg', 'name' => 'Chuyên gia', 'type' => 'manual',
               'input' => ['decimals' => 3]]];
        $loi = MetricValidator::validate($m, 'kham');
        $this->assertEquals('input.decimals', $loi[0]['field']);
    }

    /** @test */
    public function json_hong_tra_loi_cap_danh_sach()
    {
        $loi = MetricValidator::validateJson('{khong phai json', 'dieu_tri');
        $this->assertEquals(-1, $loi[0]['index']);
        $this->assertEquals('metrics', $loi[0]['field']);
    }

    /** @test */
    public function json_hop_le_di_qua_duoc()
    {
        $this->assertSame([], MetricValidator::validateJson(json_encode($this->hopLe()), 'dieu_tri'));
    }
}
```

- [ ] **Step 2: Chạy test, xác nhận đỏ**

Chạy: `vendor/bin/phpunit --filter MetricValidatorTest`
Kỳ vọng: FAIL — `Class 'App\Services\GiaoBan\MetricValidator' not found`

- [ ] **Step 3: Viết validator**

```php
<?php

namespace App\Services\GiaoBan;

/**
 * Kiem tra mang metrics theo MetricSchema. Ham thuan, khong cham DB.
 * Tra ve [['index' => vi tri chi tieu, 'field' => ten o, 'message' => ...]].
 * index = -1 la loi cap toan danh sach.
 */
class MetricValidator
{
    const CODE_PATTERN = '/^[a-z][a-z0-9_]{0,31}$/';

    /** Cac khoa chi dinh pham vi khoa cu the cua service_count (ngoai execute_department_id_self). */
    const SCOPE_KEYS = [
        'execute_department_id', 'execute_department_ids',
        'request_department_id', 'request_department_ids',
        'execute_room_ids', 'service_ids',
    ];

    public static function validateJson($jsonString, $blockType)
    {
        $data = json_decode($jsonString, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return [self::loi(-1, 'metrics', 'Chỉ tiêu không phải JSON hợp lệ.')];
        }
        if (!is_array($data)) {
            return [self::loi(-1, 'metrics', 'Chỉ tiêu phải là một mảng.')];
        }
        return self::validate($data, $blockType);
    }

    public static function validate(array $metrics, $blockType)
    {
        $loi = [];

        if (empty($metrics)) {
            return [self::loi(-1, 'metrics', 'Phải có ít nhất một chỉ tiêu.')];
        }
        if (array_keys($metrics) !== range(0, count($metrics) - 1)) {
            return [self::loi(-1, 'metrics', 'Chỉ tiêu phải là mảng tuần tự, không phải object.')];
        }

        $typeHopLe = MetricSchema::forBlock($blockType);
        $daThayCode = [];

        foreach ($metrics as $i => $m) {
            if (!is_array($m)) {
                $loi[] = self::loi($i, 'metrics', 'Chỉ tiêu phải là object.');
                continue;
            }

            $code = isset($m['code']) ? (string) $m['code'] : '';
            if (!preg_match(self::CODE_PATTERN, $code)) {
                $loi[] = self::loi($i, 'code', 'Mã chỉ tiêu phải bắt đầu bằng chữ thường, chỉ gồm a-z 0-9 _, tối đa 32 ký tự.');
            } elseif (isset($daThayCode[$code])) {
                $loi[] = self::loi($i, 'code', "Mã chỉ tiêu '$code' trùng với chỉ tiêu thứ " . ($daThayCode[$code] + 1) . '.');
            } else {
                $daThayCode[$code] = $i;
            }

            $name = isset($m['name']) ? trim((string) $m['name']) : '';
            if ($name === '') {
                $loi[] = self::loi($i, 'name', 'Tên hiển thị không được để trống.');
            } elseif (mb_strlen($name) > 255) {
                $loi[] = self::loi($i, 'name', 'Tên hiển thị tối đa 255 ký tự.');
            }

            $type = isset($m['type']) ? (string) $m['type'] : '';
            if (!MetricSchema::has($type)) {
                $loi[] = self::loi($i, 'type', "Loại chỉ tiêu '$type' không tồn tại.");
                continue; // khong biet type thi khong kiem tiep duoc
            }
            if (!in_array($type, $typeHopLe, true)) {
                $loi[] = self::loi($i, 'type', "Loại '$type' không dùng được cho khối '$blockType'.");
                continue;
            }

            $def = MetricSchema::get($type);

            if ($type === 'manual') {
                $loi = array_merge($loi, self::kiemNhapTay($i, isset($m['input']) ? $m['input'] : [], $def['fields']));
            } else {
                $loi = array_merge($loi, self::kiemFields($i, $m, $def['fields']));
                $loi = array_merge($loi, self::kiemFilter($i, isset($m['filter']) ? $m['filter'] : [], $def));
            }
        }

        return $loi;
    }

    /** Field nam thang trong chi tieu: end_codes, bed_ids. */
    protected static function kiemFields($i, array $m, array $fields)
    {
        $loi = [];
        foreach ($fields as $ten => $meta) {
            $coGiaTri = isset($m[$ten]) && is_array($m[$ten]) && count($m[$ten]) > 0;
            if (!empty($meta['required']) && !$coGiaTri) {
                $loi[] = self::loi($i, $ten, "Phải chọn ít nhất một giá trị cho '{$meta['label']}'.");
                continue;
            }
            if ($coGiaTri) {
                $loi = array_merge($loi, self::kiemKieuMang($i, $ten, $m[$ten], $meta));
            }
        }
        return $loi;
    }

    protected static function kiemFilter($i, $filter, array $def)
    {
        $loi = [];
        if (!is_array($filter) || empty($filter)) return $loi;

        $whitelist = [];
        foreach ($def['filter'] as $ten => $meta) {
            $whitelist[$ten] = $meta;
            if (isset($meta['other_key'])) $whitelist[$meta['other_key']] = $meta;
        }
        $laServiceCount = isset($def['scope']) && $def['scope'] === 'service_dept';
        if ($laServiceCount) {
            foreach (array_merge(['execute_department_id_self'], self::SCOPE_KEYS) as $k) {
                if (!isset($whitelist[$k])) {
                    $whitelist[$k] = ['widget' => 'catalog_multi', 'value' => 'int', 'label' => 'Phạm vi khoa'];
                }
            }
        }

        foreach ($filter as $ten => $giaTri) {
            if (!isset($whitelist[$ten])) {
                $loi[] = self::loi($i, 'filter.' . $ten, "Khoá lọc '$ten' không dùng được cho loại này.");
                continue;
            }
            $meta = $whitelist[$ten];

            if ($ten === 'execute_department_id_self') {
                if (!is_bool($giaTri)) {
                    $loi[] = self::loi($i, 'filter.' . $ten, 'Phạm vi "khoa này thực hiện" phải là true/false.');
                }
                continue;
            }
            if (in_array($ten, ['priority_min', 'priority_max', 'execute_department_id', 'request_department_id'], true)) {
                if (!is_numeric($giaTri)) {
                    $loi[] = self::loi($i, 'filter.' . $ten, "'$ten' phải là số.");
                }
                continue;
            }
            if (!is_array($giaTri) || count($giaTri) === 0) {
                $loi[] = self::loi($i, 'filter.' . $ten, "'{$meta['label']}' phải là mảng không rỗng.");
                continue;
            }
            $loi = array_merge($loi, self::kiemKieuMang($i, 'filter.' . $ten, $giaTri, $meta));
        }

        // khong duoc vua khai ids cu the vua danh dau nhom "Khac"
        foreach ($def['filter'] as $ten => $meta) {
            if (!isset($meta['other_key'])) continue;
            if (!empty($filter[$ten]) && !empty($filter[$meta['other_key']])) {
                $loi[] = self::loi($i, 'filter.' . $ten,
                    "Không được vừa chọn '{$meta['label']}' cụ thể vừa đánh dấu nhóm Khác. Chọn một trong hai.");
            }
        }

        // pham vi khoa: "khoa nay thuc hien" va "chi dinh cu the" loai tru nhau
        if ($laServiceCount && !empty($filter['execute_department_id_self'])) {
            foreach (self::SCOPE_KEYS as $k) {
                if (!empty($filter[$k])) {
                    $loi[] = self::loi($i, 'filter.execute_department_id_self',
                        'Đã chọn phạm vi "khoa này thực hiện" thì không khai thêm khoa/phòng/dịch vụ cụ thể.');
                    break;
                }
            }
        }

        return $loi;
    }

    protected static function kiemKieuMang($i, $field, $giaTri, array $meta)
    {
        if (!is_array($giaTri)) {
            return [self::loi($i, $field, 'Giá trị phải là mảng.')];
        }
        $kieu = isset($meta['value']) ? $meta['value'] : 'int';
        foreach ($giaTri as $v) {
            if ($kieu === 'int' && !is_numeric($v)) {
                return [self::loi($i, $field, 'Mọi giá trị phải là số.')];
            }
            if ($kieu === 'string' && (!is_string($v) || trim($v) === '')) {
                return [self::loi($i, $field, 'Mọi giá trị phải là chuỗi không rỗng.')];
            }
        }
        return [];
    }

    protected static function kiemNhapTay($i, $input, array $fields)
    {
        $loi = [];
        if (!is_array($input)) {
            return [self::loi($i, 'input', 'Khai báo ô nhập tay phải là object.')];
        }

        foreach ($input as $ten => $v) {
            if (!isset($fields[$ten])) {
                $loi[] = self::loi($i, 'input.' . $ten, "Thuộc tính '$ten' không dùng được cho chỉ tiêu nhập tay.");
            }
        }

        $valueType = isset($input['value_type']) ? $input['value_type'] : 'int';
        if (!in_array($valueType, $fields['value_type']['options'], true)) {
            $loi[] = self::loi($i, 'input.value_type', 'Kiểu giá trị chỉ nhận: int, decimal, percent.');
            $valueType = 'int';
        }

        if (isset($input['unit']) && mb_strlen((string) $input['unit']) > 20) {
            $loi[] = self::loi($i, 'input.unit', 'Đơn vị tối đa 20 ký tự.');
        }
        if (isset($input['hint']) && mb_strlen((string) $input['hint']) > 255) {
            $loi[] = self::loi($i, 'input.hint', 'Giải thích tối đa 255 ký tự.');
        }
        foreach (['required', 'carry_over'] as $ten) {
            if (isset($input[$ten]) && !is_bool($input[$ten])) {
                $loi[] = self::loi($i, 'input.' . $ten, "'$ten' phải là true/false.");
            }
        }

        $min = isset($input['min']) && $input['min'] !== '' ? $input['min'] : null;
        $max = isset($input['max']) && $input['max'] !== '' ? $input['max'] : null;
        $mac = isset($input['default']) && $input['default'] !== '' ? $input['default'] : null;

        foreach (['min' => $min, 'max' => $max, 'default' => $mac] as $ten => $v) {
            if ($v !== null && !is_numeric($v)) {
                $loi[] = self::loi($i, 'input.' . $ten, "'$ten' phải là số.");
            }
        }
        if (is_numeric($min) && is_numeric($max) && $min > $max) {
            $loi[] = self::loi($i, 'input.min', 'Giá trị nhỏ nhất không được lớn hơn giá trị lớn nhất.');
        }
        if (is_numeric($mac)) {
            if (is_numeric($min) && $mac < $min) {
                $loi[] = self::loi($i, 'input.default', 'Giá trị mặc định nhỏ hơn giá trị nhỏ nhất.');
            }
            if (is_numeric($max) && $mac > $max) {
                $loi[] = self::loi($i, 'input.default', 'Giá trị mặc định lớn hơn giá trị lớn nhất.');
            }
        }
        if ($valueType === 'percent') {
            if (is_numeric($min) && $min < 0) $loi[] = self::loi($i, 'input.min', 'Kiểu phần trăm giới hạn 0–100.');
            if (is_numeric($max) && $max > 100) $loi[] = self::loi($i, 'input.max', 'Kiểu phần trăm giới hạn 0–100.');
        }

        return $loi;
    }

    protected static function loi($index, $field, $message)
    {
        return ['index' => $index, 'field' => $field, 'message' => $message];
    }
}
```

- [ ] **Step 4: Chạy test, xác nhận xanh**

Chạy: `vendor/bin/phpunit --filter MetricValidatorTest`
Kỳ vọng: PASS (19 test)

- [ ] **Step 5: Commit**

```bash
git add app/Services/GiaoBan/MetricValidator.php tests/Unit/GiaoBan/MetricValidatorTest.php
git commit -m "feat(giaoban): MetricValidator siet schema chi tieu"
```

---

## Task 4: Command quét cấu hình cũ

Đây là **cửa an toàn** trước khi siết validate ở Task 5. Không có bước này thì việc bật validate có thể chặn một khoa đang chạy ngay giữa giờ giao ban.

**Files:**
- Create: `app/Console/Commands/GiaoBanKiemTraChiTieu.php`
- Modify: `app/Console/Kernel.php` (đăng ký command vào `$commands`)

**Interfaces:**
- Consumes: `MetricValidator::validateJson()` (Task 3), `GiaoBanDeptConfig`.
- Produces: command `giaoban:kiem-tra-chi-tieu`, exit code `0` khi mọi cấu hình đạt, `1` khi có cấu hình sai.

- [ ] **Step 1: Xem cách đăng ký command hiện có**

Đọc `app/Console/Kernel.php`, xem mảng `$commands` và cách các command khác trong `app/Console/Commands/` khai `$signature`. Làm theo đúng khuôn đó — không tự nghĩ kiểu khác.

- [ ] **Step 2: Viết command**

```php
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\GiaoBan\GiaoBanDeptConfig;
use App\Services\GiaoBan\MetricValidator;

/**
 * Quet toan bo giaoban_dept_configs, in ra cau hinh chi tieu khong dat schema.
 * Chay TRUOC khi bat siet validate o GiaoBanConfigController.
 */
class GiaoBanKiemTraChiTieu extends Command
{
    protected $signature = 'giaoban:kiem-tra-chi-tieu';
    protected $description = 'Kiem tra cau hinh chi tieu giao ban co dat MetricSchema khong';

    public function handle()
    {
        $configs = GiaoBanDeptConfig::orderBy('sort_order')->get();
        $soSai = 0;

        foreach ($configs as $cfg) {
            $loi = MetricValidator::validateJson($cfg->metrics, $cfg->block_type);
            if (empty($loi)) continue;

            $soSai++;
            $this->error(sprintf('#%d %s (khối %s) — %d lỗi',
                $cfg->id, $cfg->display_name, $cfg->block_type, count($loi)));
            foreach ($loi as $l) {
                $viTri = $l['index'] === -1 ? 'toàn danh sách' : ('chỉ tiêu thứ ' . ($l['index'] + 1));
                $this->line(sprintf('    - %s / %s: %s', $viTri, $l['field'], $l['message']));
            }
        }

        $this->info(sprintf('Đã kiểm %d cấu hình, %d cấu hình không đạt.', count($configs), $soSai));
        return $soSai > 0 ? 1 : 0;
    }
}
```

- [ ] **Step 3: Đăng ký command**

Thêm `\App\Console\Commands\GiaoBanKiemTraChiTieu::class,` vào mảng `$commands` trong `app/Console/Kernel.php`.

- [ ] **Step 4: Chạy trên dữ liệu thật**

Chạy: `php artisan giaoban:kiem-tra-chi-tieu`

Kỳ vọng: in ra dòng tổng kết. **Đọc kỹ kết quả:**
- Nếu `0 cấu hình không đạt` → sang Task 5 thẳng.
- Nếu có cấu hình sai → **dừng lại, báo cho người dùng danh sách sai kèm lý do trước khi làm tiếp**. Không tự ý sửa dữ liệu cấu hình đang chạy.
- Nếu command báo lỗi hàng loạt ở một khoá filter mà anh tin là đúng → schema ở Task 2 khai thiếu khoá đó; bổ sung vào `MetricSchema`, chạy lại `MetricSchemaTest` và `MetricValidatorTest`.

- [ ] **Step 5: Commit**

```bash
git add app/Console/Commands/GiaoBanKiemTraChiTieu.php app/Console/Kernel.php
git commit -m "feat(giaoban): command kiem tra cau hinh chi tieu theo schema"
```

---

## Task 5: Siết validate ở controller

**Files:**
- Modify: `app/Http/Controllers/KHTH/GiaoBanConfigController.php:99-135` (`store`, `update`), bỏ `validJson()` ở dòng 173-177
- Create: `tests/Feature/GiaoBan/GiaoBanConfigControllerTest.php`

**Interfaces:**
- Consumes: `MetricValidator::validateJson()` (Task 3).
- Produces: `store`/`update` trả `422` với body `['message' => string, 'errors' => [['index','field','message'], ...]]`. Form builder (Task 15) dựa vào đúng hình dạng này để tô đỏ card.

- [ ] **Step 1: Viết Feature test (đỏ)**

Theo khuôn `tests/Feature/RevenueDeptRoomControllerTest.php` — không chạm DB thật cho nhánh 422 vì validate chặn trước khi tới model:

```php
<?php

namespace Tests\Feature\GiaoBan;

use Tests\TestCase;

class FakeGiaoBanAdminUser extends \App\User
{
    public function hasRole($r, $team = null, $requireAll = false) { return true; }
    public function can($permission, $team = null, $requireAll = false) { return true; }
}

class GiaoBanConfigControllerTest extends TestCase
{
    protected function admin() { return new FakeGiaoBanAdminUser(); }

    /** @test */
    public function store_tra_422_kem_index_va_field_khi_type_khong_hop_khoi()
    {
        $metrics = json_encode([
            ['code' => 'bn_cu', 'name' => 'BN cũ', 'type' => 'census_from'],
        ]);

        $res = $this->actingAs($this->admin())->postJson(route('khth.giao-ban-config-store'), [
            'display_name' => 'Khoa thử',
            'block_type' => 'can_lam_sang',   // census_from khong dung duoc o khoi nay
            'his_department_ids' => '[]',
            'sort_order' => 99,
            'metrics' => $metrics,
        ]);

        $res->assertStatus(422)
            ->assertJsonStructure(['message', 'errors' => [['index', 'field', 'message']]]);
        $this->assertEquals('type', $res->json('errors.0.field'));
        $this->assertEquals(0, $res->json('errors.0.index'));
    }

    /** @test */
    public function store_tra_422_khi_json_hong()
    {
        $res = $this->actingAs($this->admin())->postJson(route('khth.giao-ban-config-store'), [
            'display_name' => 'Khoa thử',
            'block_type' => 'dieu_tri',
            'his_department_ids' => '[]',
            'metrics' => '{khong phai json',
        ]);

        $res->assertStatus(422);
        $this->assertEquals('metrics', $res->json('errors.0.field'));
        $this->assertEquals(-1, $res->json('errors.0.index'));
    }

    /** @test */
    public function store_tra_422_khi_his_department_ids_khong_phai_json()
    {
        $res = $this->actingAs($this->admin())->postJson(route('khth.giao-ban-config-store'), [
            'display_name' => 'Khoa thử',
            'block_type' => 'dieu_tri',
            'his_department_ids' => 'khong-phai-json',
            'metrics' => json_encode([['code' => 'bn_cu', 'name' => 'BN cũ', 'type' => 'census_from']]),
        ]);

        $res->assertStatus(422);
    }
}
```

- [ ] **Step 2: Chạy test, xác nhận đỏ**

Chạy: `vendor/bin/phpunit --filter GiaoBanConfigControllerTest`
Kỳ vọng: FAIL — hiện `store` chỉ check `validJson` nên trả `200`/`201` hoặc 422 nhưng thiếu khoá `errors`.

- [ ] **Step 3: Thay `validJson` bằng `MetricValidator`**

Trong `GiaoBanConfigController.php`, thêm `use App\Services\GiaoBan\MetricValidator;` ở đầu file, rồi sửa `store`:

```php
    public function store(Request $request)
    {
        $this->validate($request, [
            'display_name' => 'required|string|max:255',
            'block_type' => 'required|in:dieu_tri,kham,can_lam_sang',
            'his_department_ids' => 'nullable|string',
            'sort_order' => 'nullable|integer',
            'metrics' => 'required|string',
        ]);

        $loi = MetricValidator::validateJson($request->input('metrics'), $request->input('block_type'));
        if (!empty($loi)) {
            return $this->traLoiChiTieu($loi);
        }
        if ($request->filled('his_department_ids') && !$this->validJson($request->input('his_department_ids'))) {
            return response()->json(['message' => 'his_department_ids không phải JSON hợp lệ', 'errors' => []], 422);
        }

        $cfg = GiaoBanDeptConfig::create(
            $request->only(['display_name', 'block_type', 'his_department_ids', 'sort_order', 'metrics'])
            + ['is_active' => true]
        );
        return response()->json(['ok' => true, 'id' => $cfg->id]);
    }
```

và `update` — lưu ý `block_type` có thể không gửi kèm, khi đó lấy từ bản ghi hiện có, nếu không sẽ validate nhầm khối:

```php
    public function update(Request $request, $id)
    {
        $cfg = GiaoBanDeptConfig::findOrFail($id);
        if ($request->filled('block_type')) {
            $this->validate($request, ['block_type' => 'in:dieu_tri,kham,can_lam_sang']);
        }
        // block_type dung de validate: uu tien gia tri gui len, khong co thi lay tu ban ghi
        $blockType = $request->filled('block_type') ? $request->input('block_type') : $cfg->block_type;

        if ($request->filled('metrics')) {
            $loi = MetricValidator::validateJson($request->input('metrics'), $blockType);
            if (!empty($loi)) {
                return $this->traLoiChiTieu($loi);
            }
        }
        if ($request->filled('his_department_ids') && !$this->validJson($request->input('his_department_ids'))) {
            return response()->json(['message' => 'his_department_ids không phải JSON hợp lệ', 'errors' => []], 422);
        }

        $cfg->update($request->only(['display_name', 'block_type', 'his_department_ids', 'sort_order', 'metrics', 'is_active']));
        return response()->json(['ok' => true]);
    }
```

Thêm helper cạnh `validJson` (giữ `validJson` lại vì `his_department_ids` vẫn dùng):

```php
    /** Gói danh sách lỗi chỉ tiêu thành 422 để form builder tô đỏ đúng card. */
    protected function traLoiChiTieu(array $loi)
    {
        $dau = $loi[0];
        $viTri = $dau['index'] === -1 ? '' : (' (chỉ tiêu thứ ' . ($dau['index'] + 1) . ')');
        return response()->json([
            'message' => $dau['message'] . $viTri,
            'errors' => $loi,
        ], 422);
    }
```

- [ ] **Step 4: Chạy test, xác nhận xanh**

Chạy: `vendor/bin/phpunit --filter GiaoBanConfigControllerTest`
Kỳ vọng: PASS (3 test)

- [ ] **Step 5: Chạy lại toàn bộ suite, đối chiếu baseline**

Chạy: `vendor/bin/phpunit`
Kỳ vọng: không có test đỏ nào mới so với baseline ghi ở Task 1 Step 1.

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/KHTH/GiaoBanConfigController.php tests/Feature/GiaoBan/GiaoBanConfigControllerTest.php
git commit -m "feat(giaoban): siet validate chi tieu theo MetricSchema o store/update"
```

---

## Task 6: Danh mục nhóm nhỏ — truy vấn + cache + endpoint gộp

**Files:**
- Modify: `app/Services/GiaoBan/GiaoBanCatalogService.php` (thêm phần dựng SQL và thực thi)
- Modify: `app/Http/Controllers/KHTH/GiaoBanConfigController.php` (thêm `catalogs()`)
- Modify: `routes/web.php` (thêm route trong nhóm dòng ~663-671)
- Test: `tests/Unit/GiaoBan/GiaoBanCatalogServiceTest.php` (bổ sung)

**Interfaces:**
- Consumes: `GiaoBanCatalogService::CATALOGS`, `smallKeys()` (Task 1).
- Produces:
  - `GiaoBanCatalogService::buildSmallSql($key)` → `[string $sql, array $binds]` — hàm thuần, test được không cần Oracle.
  - `GiaoBanCatalogService::allSmall()` → `array` `key => [['id' => mixed, 'name' => string], ...]`, có cache 60 phút.
  - Route `khth.giao-ban-config-catalogs` → `GET khth/giao-ban/cau-hinh/danh-muc`, trả `['catalogs' => [...], 'labels' => [...]]`.

- [ ] **Step 1: Viết test dựng SQL (đỏ)**

Thêm vào `tests/Unit/GiaoBan/GiaoBanCatalogServiceTest.php`:

```php
    /** @test */
    public function dung_sql_danh_muc_nho_lay_dung_bang_va_cot()
    {
        list($sql, $binds) = GiaoBanCatalogService::buildSmallSql('diim_type');

        $this->assertContains('FROM his_diim_type', $sql);
        $this->assertContains('diim_type_name', $sql);
        $this->assertContains('is_delete = 0', $sql);
        $this->assertSame([], $binds);
    }

    /** @test */
    public function danh_muc_end_type_lay_code_lam_dinh_danh()
    {
        list($sql, ) = GiaoBanCatalogService::buildSmallSql('end_type');

        $this->assertContains('treatment_end_type_code AS ma', $sql);
        $this->assertNotContains('id AS ma', $sql);
    }

    /** @test */
    public function danh_muc_khong_ton_tai_thi_nem_loi()
    {
        $this->expectException(\InvalidArgumentException::class);
        GiaoBanCatalogService::buildSmallSql('khong_ton_tai');
    }
```

- [ ] **Step 2: Chạy test, xác nhận đỏ**

Chạy: `vendor/bin/phpunit --filter GiaoBanCatalogServiceTest`
Kỳ vọng: FAIL — `Call to undefined method ... ::buildSmallSql()`

- [ ] **Step 3: Thêm phần dựng SQL và thực thi vào `GiaoBanCatalogService`**

```php
    /**
     * SQL lay tron mot danh muc nho. Ham thuan (test duoc khong can Oracle).
     * Tra ve cot chuan: ma, ten.
     */
    public static function buildSmallSql($key)
    {
        if (!isset(self::CATALOGS[$key])) {
            throw new \InvalidArgumentException("Danh muc khong ton tai: $key");
        }
        $c = self::CATALOGS[$key];
        $sql = "SELECT {$c['id_col']} AS ma, {$c['name_col']} AS ten
                FROM {$c['table']}
                WHERE is_delete = 0 AND is_active = 1
                ORDER BY {$c['name_col']}";
        return [$sql, []];
    }

    /** Toan bo danh muc nho, cache 60 PHUT (Laravel 5.5 nhan phut). */
    public static function allSmall()
    {
        return \Illuminate\Support\Facades\Cache::remember('giaoban.catalogs', 60, function () {
            $out = [];
            foreach (self::smallKeys() as $key) {
                $out[$key] = self::layDanhMuc($key);
            }
            return $out;
        });
    }

    /** Chay 1 truy van danh muc tren HISPro. HIS loi -> mang rong, khong lam trang trang cau hinh. */
    protected static function layDanhMuc($key)
    {
        list($sql, $binds) = self::buildSmallSql($key);
        try {
            $rows = \Illuminate\Support\Facades\DB::connection(self::CONN)->select($sql, $binds);
        } catch (\Exception $e) {
            return [];
        }
        return self::chuanHoa($rows);
    }

    /** Oracle tra cot HOA -> ve dang [['id' =>, 'name' =>], ...] */
    protected static function chuanHoa($rows)
    {
        $out = [];
        foreach ($rows as $r) {
            $row = array_change_key_case((array) $r, CASE_LOWER);
            $out[] = ['id' => $row['ma'], 'name' => $row['ten']];
        }
        return $out;
    }

    /** Nhan hien thi cua tung danh muc, cho form builder dat label. */
    public static function labels()
    {
        $out = [];
        foreach (self::CATALOGS as $k => $c) $out[$k] = $c['label'];
        return $out;
    }
```

**Lưu ý:** một vài bảng danh mục HIS có thể không có cột `is_active` hoặc `is_delete`. Nếu Step 5 chạy ra lỗi `ORA-00904: invalid identifier`, bỏ điều kiện thiếu cho **riêng bảng đó** bằng cách thêm khoá `'where' => 'is_delete = 0'` vào `CATALOGS` và dùng nó thay cho chuỗi cứng — sửa cả test ở Step 1 cho khớp.

- [ ] **Step 4: Thêm endpoint và route**

Trong `GiaoBanConfigController.php`, thêm `use App\Services\GiaoBan\GiaoBanCatalogService;` và:

```php
    /** Toan bo danh muc nho + nhan, cho form builder tai mot lan khi mo modal. */
    public function catalogs()
    {
        return response()->json([
            'catalogs' => GiaoBanCatalogService::allSmall(),
            'labels' => GiaoBanCatalogService::labels(),
        ]);
    }
```

Trong `routes/web.php`, thêm ngay sau dòng `giao-ban/cau-hinh/search-users` (dòng ~668):

```php
        Route::get('giao-ban/cau-hinh/danh-muc', 'KHTH\GiaoBanConfigController@catalogs')->name('khth.giao-ban-config-catalogs');
```

- [ ] **Step 5: Chạy test đơn vị và thử endpoint thật**

Chạy: `vendor/bin/phpunit --filter GiaoBanCatalogServiceTest`
Kỳ vọng: PASS (6 test)

Thử endpoint thật bằng trình duyệt (đăng nhập tài khoản có quyền `giaoban-admin`), mở `khth/giao-ban/cau-hinh/danh-muc`.
Kỳ vọng: JSON có 6 khoá `service_type`, `diim_type`, `test_type`, `patient_type`, `treatment_type`, `end_type`; mỗi khoá là mảng `{id, name}` **không rỗng**. Mảng rỗng nghĩa là tên bảng/cột sai hoặc điều kiện `is_active`/`is_delete` không tồn tại — quay lại lưu ý ở Step 3.

- [ ] **Step 6: Commit**

```bash
git add app/Services/GiaoBan/GiaoBanCatalogService.php app/Http/Controllers/KHTH/GiaoBanConfigController.php routes/web.php tests/Unit/GiaoBan/GiaoBanCatalogServiceTest.php
git commit -m "feat(giaoban): endpoint danh muc HIS nhom nho co cache"
```

---

## Task 7: Danh mục nhóm lớn — tìm theo `q` và tra ngược theo `ids`

Không có phần tra ngược thì mở lại cấu hình cũ, select2 hiện ID trần thay vì tên.

**Files:**
- Modify: `app/Services/GiaoBan/GiaoBanCatalogService.php`
- Modify: `app/Http/Controllers/KHTH/GiaoBanConfigController.php`
- Modify: `routes/web.php`
- Test: `tests/Unit/GiaoBan/GiaoBanCatalogServiceTest.php` (bổ sung)

**Interfaces:**
- Consumes: `GiaoBanCatalogService::CATALOGS`, `isRemote()` (Task 1); `ViSearch::normalize()`, `ViSearch::noDiacriticsSql()` (đã có sẵn trong `app/Services/GiaoBan/ViSearch.php`).
- Produces:
  - `GiaoBanCatalogService::buildSearchSql($key, $q)` → `[string, array]`
  - `GiaoBanCatalogService::buildByIdsSql($key, array $ids)` → `[string, array]`
  - `GiaoBanCatalogService::search($key, $q)` / `::byIds($key, array $ids)` → `[['id','name'], ...]`
  - Route `khth.giao-ban-config-catalog` → `GET khth/giao-ban/cau-hinh/danh-muc/{key}?q=...` hoặc `?ids=1,2,3`

- [ ] **Step 1: Viết test (đỏ)**

```php
    /** @test */
    public function tim_danh_muc_lon_gioi_han_30_dong_va_bo_dau()
    {
        list($sql, $binds) = GiaoBanCatalogService::buildSearchSql('service', 'sieu am');

        $this->assertContains('ROWNUM <= 30', $sql);
        $this->assertContains('FROM his_service', $sql);
        $this->assertArrayHasKey('q1', $binds);
        $this->assertContains('%sieu am%', $binds['q1']);
    }

    /** @test */
    public function tra_nguoc_theo_ids_chi_nhan_so_nguyen()
    {
        list($sql, ) = GiaoBanCatalogService::buildByIdsSql('room', ['12', 34, 'x']);

        // 'x' bi ep ve 0, khong duoc chen chuoi vao SQL
        $this->assertContains('IN (12,34,0)', $sql);
        $this->assertNotContains("'x'", $sql);
    }

    /** @test */
    public function tra_nguoc_voi_mang_rong_khong_khop_gi()
    {
        list($sql, ) = GiaoBanCatalogService::buildByIdsSql('room', []);
        $this->assertContains('IN (-1)', $sql);
    }

    /** @test */
    public function danh_muc_nho_khong_dung_duong_tim_kiem_remote()
    {
        $this->expectException(\InvalidArgumentException::class);
        GiaoBanCatalogService::buildSearchSql('diim_type', 'abc');
    }
```

- [ ] **Step 2: Chạy test, xác nhận đỏ**

Chạy: `vendor/bin/phpunit --filter GiaoBanCatalogServiceTest`
Kỳ vọng: FAIL — `Call to undefined method ... ::buildSearchSql()`

- [ ] **Step 3: Thêm phần nhóm lớn**

```php
    /** SQL tim danh muc lon theo tu khoa (bo dau). Ham thuan. */
    public static function buildSearchSql($key, $q)
    {
        $c = self::layKhaiBaoRemote($key);
        $chuan = ViSearch::normalize($q);
        $bieuThucTen = ViSearch::noDiacriticsSql($c['name_col']);
        $sql = "SELECT * FROM (
                    SELECT {$c['id_col']} AS ma, {$c['name_col']} AS ten
                    FROM {$c['table']}
                    WHERE is_delete = 0 AND is_active = 1
                      AND $bieuThucTen LIKE :q1
                    ORDER BY {$c['name_col']}
                ) WHERE ROWNUM <= 30";
        return [$sql, ['q1' => '%' . $chuan . '%']];
    }

    /** SQL tra nguoc ID -> ten, de select2 hien nhan khi mo lai cau hinh cu. */
    public static function buildByIdsSql($key, array $ids)
    {
        $c = self::layKhaiBaoRemote($key);
        $danhSach = implode(',', array_map('intval', $ids));
        if ($danhSach === '') $danhSach = '-1';
        $sql = "SELECT {$c['id_col']} AS ma, {$c['name_col']} AS ten
                FROM {$c['table']}
                WHERE {$c['id_col']} IN ($danhSach)";
        return [$sql, []];
    }

    protected static function layKhaiBaoRemote($key)
    {
        if (!self::isRemote($key)) {
            throw new \InvalidArgumentException("Danh muc '$key' khong phai danh muc lon.");
        }
        return self::CATALOGS[$key];
    }

    public static function search($key, $q)
    {
        if (mb_strlen(trim((string) $q)) < 2) return [];
        return self::chay(self::buildSearchSql($key, $q));
    }

    public static function byIds($key, array $ids)
    {
        if (empty($ids)) return [];
        return self::chay(self::buildByIdsSql($key, $ids));
    }

    protected static function chay($sqlVaBinds)
    {
        list($sql, $binds) = $sqlVaBinds;
        try {
            $rows = \Illuminate\Support\Facades\DB::connection(self::CONN)->select($sql, $binds);
        } catch (\Exception $e) {
            return [];
        }
        return self::chuanHoa($rows);
    }
```

Thêm `use App\Services\GiaoBan\ViSearch;`? Không cần — `ViSearch` cùng namespace `App\Services\GiaoBan`.

**Lưu ý về `room` và `bed`:** spec muốn nhãn kèm tên khoa/phòng cho dễ chọn. Làm ở bước sau nếu cần; ở task này `room_name`/`bed_name` trần là đủ để form chạy đúng. Nếu tên phòng/giường trùng nhiều gây khó chọn, mở rộng `CATALOGS` thêm khoá `'name_expr'` (biểu thức nối chuỗi) và dùng nó thay `name_col` — sửa một chỗ, cả 3 hàm dựng SQL hưởng.

- [ ] **Step 4: Thêm endpoint và route**

```php
    /** Tim danh muc lon theo tu khoa, hoac tra nguoc theo danh sach id. */
    public function catalog(Request $request, $key)
    {
        if (!GiaoBanCatalogService::isRemote($key)) {
            return response()->json(['message' => 'Danh mục không hợp lệ'], 422);
        }
        if ($request->filled('ids')) {
            $ids = array_filter(explode(',', (string) $request->input('ids')), 'strlen');
            return response()->json(GiaoBanCatalogService::byIds($key, $ids));
        }
        return response()->json(GiaoBanCatalogService::search($key, $request->input('q', '')));
    }
```

Route (đặt **sau** route `danh-muc` không tham số để khỏi nuốt nhau):

```php
        Route::get('giao-ban/cau-hinh/danh-muc/{key}', 'KHTH\GiaoBanConfigController@catalog')->name('khth.giao-ban-config-catalog');
```

- [ ] **Step 5: Chạy test và thử thật**

Chạy: `vendor/bin/phpunit --filter GiaoBanCatalogServiceTest`
Kỳ vọng: PASS (10 test)

Thử trên trình duyệt: `khth/giao-ban/cau-hinh/danh-muc/service?q=sieu`
Kỳ vọng: mảng ≤ 30 phần tử `{id, name}`, tên có chứa "siêu" (gõ không dấu vẫn ra).

Thử tra ngược: lấy một `id` từ kết quả trên, mở `khth/giao-ban/cau-hinh/danh-muc/service?ids=<id>`
Kỳ vọng: đúng 1 phần tử, `name` khớp.

Thử chặn: `khth/giao-ban/cau-hinh/danh-muc/diim_type?q=abc`
Kỳ vọng: HTTP 422.

- [ ] **Step 6: Commit**

```bash
git add app/Services/GiaoBan/GiaoBanCatalogService.php app/Http/Controllers/KHTH/GiaoBanConfigController.php routes/web.php tests/Unit/GiaoBan/GiaoBanCatalogServiceTest.php
git commit -m "feat(giaoban): tim danh muc HIS lon theo tu khoa va tra nguoc theo id"
```

---

## Task 8: Bảng mẫu chỉ tiêu + chuyển 5 mẫu cứng vào DB

**Files:**
- Create: `database/migrations/2026_07_27_100000_create_giaoban_metric_templates_table.php`
- Create: `app/Models/GiaoBan/GiaoBanMetricTemplate.php`
- Modify: `app/Http/Controllers/KHTH/GiaoBanConfigController.php:35-71` (`fetch` trả thêm `metric_templates`)
- Test: `tests/Unit/GiaoBan/MetricTemplateSeedTest.php`

**Interfaces:**
- Consumes: `MetricValidator::validate()` (Task 3).
- Produces:
  - `GiaoBanMetricTemplate::SEED` — mảng 5 mẫu `['name', 'block_type', 'metrics' => array, 'sort_order']`, dùng cho cả migration lẫn test.
  - `GiaoBanMetricTemplate::metricList()` — decode cột `metrics`.
  - `fetch` trả thêm khoá `metric_templates`.

- [ ] **Step 1: Viết test seed (đỏ)**

Test này bắt được lỗi chép sai mẫu — 5 mẫu chuyển từ blade sang phải **vẫn hợp lệ theo schema**:

```php
<?php

namespace Tests\Unit\GiaoBan;

use Tests\TestCase;
use App\Models\GiaoBan\GiaoBanMetricTemplate;
use App\Services\GiaoBan\MetricValidator;

class MetricTemplateSeedTest extends TestCase
{
    /** @test */
    public function co_du_5_mau_chuyen_tu_blade_sang()
    {
        $this->assertCount(5, GiaoBanMetricTemplate::SEED);

        $ten = array_column(GiaoBanMetricTemplate::SEED, 'name');
        foreach (['Điều trị (mặc định)', 'Khám (mặc định)', 'Tổng dịch vụ',
                  'CĐHA (XQ/CT/MRI/SA)', 'Xét nghiệm (HH/SH/VS...)'] as $t) {
            $this->assertContains($t, $ten, "Thieu mau: $t");
        }
    }

    /** @test */
    public function moi_mau_deu_dat_schema()
    {
        foreach (GiaoBanMetricTemplate::SEED as $mau) {
            $loi = MetricValidator::validate($mau['metrics'], $mau['block_type']);
            $this->assertSame([], $loi,
                "Mau '{$mau['name']}' khong dat schema: " . json_encode($loi, JSON_UNESCAPED_UNICODE));
        }
    }

    /** @test */
    public function mau_dieu_tri_giu_nguyen_8_chi_tieu_va_dung_thu_tu()
    {
        $mau = null;
        foreach (GiaoBanMetricTemplate::SEED as $m) {
            if ($m['name'] === 'Điều trị (mặc định)') $mau = $m;
        }

        $this->assertEquals(
            ['bn_cu', 'bn_vao', 'bn_chuyen_den', 'bn_ra_vien', 'bn_chuyen_vien',
             'bn_tu_vong', 'bn_chuyen_khoa', 'hien_co'],
            array_column($mau['metrics'], 'code')
        );
    }
}
```

- [ ] **Step 2: Chạy test, xác nhận đỏ**

Chạy: `vendor/bin/phpunit --filter MetricTemplateSeedTest`
Kỳ vọng: FAIL — `Class 'App\Models\GiaoBan\GiaoBanMetricTemplate' not found`

- [ ] **Step 3: Viết model kèm hằng SEED**

Chép **nguyên văn** nội dung 5 khối `<script type="application/json">` ở `resources/views/khth/giaoban-config.blade.php:60-97`. Không nhân cơ hội sửa số liệu — nếu thấy mẫu nào sai, báo riêng, đừng lặng lẽ sửa.

```php
<?php

namespace App\Models\GiaoBan;

use Illuminate\Database\Eloquent\Model;

class GiaoBanMetricTemplate extends Model
{
    protected $table = 'giaoban_metric_templates';
    protected $fillable = ['name', 'block_type', 'metrics', 'sort_order', 'is_active'];
    protected $casts = ['is_active' => 'boolean'];

    /** 5 mau chuyen tu giaoban-config.blade.php (script tpl-*), giu nguyen noi dung. */
    const SEED = [
        [
            'name' => 'Điều trị (mặc định)', 'block_type' => 'dieu_tri', 'sort_order' => 1,
            'metrics' => [
                ['code' => 'bn_cu', 'name' => 'BN cũ', 'type' => 'census_from'],
                ['code' => 'bn_vao', 'name' => 'BN vào', 'type' => 'movement_in'],
                ['code' => 'bn_chuyen_den', 'name' => 'BN chuyển đến', 'type' => 'movement_transfer_in'],
                ['code' => 'bn_ra_vien', 'name' => 'BN ra viện', 'type' => 'end_type', 'end_codes' => ['RV', 'HK', 'CC', 'XV', 'KH', 'TR']],
                ['code' => 'bn_chuyen_vien', 'name' => 'BN chuyển viện', 'type' => 'end_type', 'end_codes' => ['CV']],
                ['code' => 'bn_tu_vong', 'name' => 'BN tử vong', 'type' => 'end_type', 'end_codes' => ['TV']],
                ['code' => 'bn_chuyen_khoa', 'name' => 'BN chuyển khoa', 'type' => 'movement_transfer_out'],
                ['code' => 'hien_co', 'name' => 'Hiện có', 'type' => 'census_to'],
            ],
        ],
        [
            'name' => 'Khám (mặc định)', 'block_type' => 'kham', 'sort_order' => 2,
            'metrics' => [
                ['code' => 'luot_kham', 'name' => 'Lượt khám', 'type' => 'exam_visit'],
                ['code' => 'vao_vien', 'name' => 'Vào viện', 'type' => 'exam_visit', 'filter' => ['treatment_type_ids' => [3]]],
                ['code' => 'cap_toa_ve', 'name' => 'Cấp toa cho về', 'type' => 'exam_visit', 'filter' => ['end_type_codes' => ['CC']]],
                ['code' => 'chuyen_vien', 'name' => 'Chuyển viện', 'type' => 'exam_visit', 'filter' => ['end_type_codes' => ['CV']]],
                ['code' => 'hen_kham_lai', 'name' => 'Hẹn khám lại', 'type' => 'exam_visit', 'filter' => ['end_type_codes' => ['HK']]],
                ['code' => 'kham_yeu_cau', 'name' => 'Khám yêu cầu', 'type' => 'exam_visit', 'filter' => ['patient_type_ids' => [82]]],
                ['code' => 'kham_bhyt', 'name' => 'Khám BHYT', 'type' => 'exam_visit', 'filter' => ['patient_type_ids' => [1]]],
                ['code' => 'chuyen_gia', 'name' => 'Khám chuyên gia', 'type' => 'manual'],
            ],
        ],
        [
            'name' => 'Tổng dịch vụ', 'block_type' => 'can_lam_sang', 'sort_order' => 3,
            'metrics' => [
                ['code' => 'tong_dv', 'name' => 'Tổng dịch vụ', 'type' => 'service_count', 'filter' => ['execute_department_id_self' => true]],
            ],
        ],
        [
            'name' => 'CĐHA (XQ/CT/MRI/SA)', 'block_type' => 'can_lam_sang', 'sort_order' => 4,
            'metrics' => [
                ['code' => 'cdha_xq', 'name' => 'X-Quang', 'type' => 'service_count', 'filter' => ['execute_department_id_self' => true, 'service_type_ids' => [3], 'diim_type_ids' => [1]]],
                ['code' => 'cdha_ct', 'name' => 'CT', 'type' => 'service_count', 'filter' => ['execute_department_id_self' => true, 'service_type_ids' => [3], 'diim_type_ids' => [2]]],
                ['code' => 'cdha_mri', 'name' => 'MRI', 'type' => 'service_count', 'filter' => ['execute_department_id_self' => true, 'service_type_ids' => [3], 'diim_type_ids' => [3]]],
                ['code' => 'cdha_khac', 'name' => 'CĐHA khác', 'type' => 'service_count', 'filter' => ['execute_department_id_self' => true, 'service_type_ids' => [3], 'diim_type_other_of' => [1, 2, 3]]],
                ['code' => 'sieu_am', 'name' => 'Siêu âm', 'type' => 'service_count', 'filter' => ['execute_department_id_self' => true, 'service_type_ids' => [10]]],
            ],
        ],
        [
            'name' => 'Xét nghiệm (HH/SH/VS...)', 'block_type' => 'can_lam_sang', 'sort_order' => 5,
            'metrics' => [
                ['code' => 'xn_hh', 'name' => 'Huyết học', 'type' => 'service_count', 'filter' => ['execute_department_id_self' => true, 'service_type_ids' => [2], 'test_type_ids' => [1]]],
                ['code' => 'xn_sh', 'name' => 'Sinh hóa', 'type' => 'service_count', 'filter' => ['execute_department_id_self' => true, 'service_type_ids' => [2], 'test_type_ids' => [3]]],
                ['code' => 'xn_vs', 'name' => 'Vi sinh', 'type' => 'service_count', 'filter' => ['execute_department_id_self' => true, 'service_type_ids' => [2], 'test_type_ids' => [2]]],
                ['code' => 'xn_md', 'name' => 'Miễn dịch', 'type' => 'service_count', 'filter' => ['execute_department_id_self' => true, 'service_type_ids' => [2], 'test_type_ids' => [4]]],
                ['code' => 'xn_nt', 'name' => 'Nước tiểu', 'type' => 'service_count', 'filter' => ['execute_department_id_self' => true, 'service_type_ids' => [2], 'test_type_ids' => [7]]],
                ['code' => 'xn_khac', 'name' => 'XN khác', 'type' => 'service_count', 'filter' => ['execute_department_id_self' => true, 'service_type_ids' => [2], 'test_type_other_of' => [1, 2, 3, 4, 7]]],
            ],
        ],
    ];

    /** @return array chi tieu da decode */
    public function metricList()
    {
        $m = json_decode($this->metrics, true);
        return is_array($m) ? $m : [];
    }
}
```

- [ ] **Step 4: Chạy test, xác nhận xanh**

Chạy: `vendor/bin/phpunit --filter MetricTemplateSeedTest`
Kỳ vọng: PASS (3 test)

Nếu test `moi_mau_deu_dat_schema` đỏ: mẫu cứng trong blade **đang sai schema**. Đọc kỹ lỗi — hoặc `MetricSchema` khai thiếu (sửa Task 2), hoặc mẫu thật sự sai (báo người dùng, đừng tự sửa số).

- [ ] **Step 5: Viết migration + seed**

```php
<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use App\Models\GiaoBan\GiaoBanMetricTemplate;

class CreateGiaobanMetricTemplatesTable extends Migration
{
    public function up()
    {
        Schema::create('giaoban_metric_templates', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 255);
            $table->string('block_type', 20);
            $table->text('metrics');
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        foreach (GiaoBanMetricTemplate::SEED as $mau) {
            GiaoBanMetricTemplate::create([
                'name' => $mau['name'],
                'block_type' => $mau['block_type'],
                'sort_order' => $mau['sort_order'],
                'metrics' => json_encode($mau['metrics'], JSON_UNESCAPED_UNICODE),
                'is_active' => true,
            ]);
        }
    }

    public function down()
    {
        Schema::dropIfExists('giaoban_metric_templates');
    }
}
```

- [ ] **Step 6: Chạy migration và kiểm tra dữ liệu**

Chạy: `php artisan migrate`

Kiểm tra: `php artisan tinker` → `App\Models\GiaoBan\GiaoBanMetricTemplate::count()`
Kỳ vọng: `5`

- [ ] **Step 7: `fetch` trả thêm danh sách mẫu**

Trong `GiaoBanConfigController::fetch()` (dòng 35-71), thêm trước `return`:

```php
        $metricTemplates = \App\Models\GiaoBan\GiaoBanMetricTemplate::where('is_active', true)
            ->orderBy('sort_order')->get(['id', 'name', 'block_type', 'metrics']);
```

và thêm `'metric_templates' => $metricTemplates,` vào mảng `response()->json([...])`.

- [ ] **Step 8: Commit**

```bash
git add database/migrations/2026_07_27_100000_create_giaoban_metric_templates_table.php app/Models/GiaoBan/GiaoBanMetricTemplate.php app/Http/Controllers/KHTH/GiaoBanConfigController.php tests/Unit/GiaoBan/MetricTemplateSeedTest.php
git commit -m "feat(giaoban): chuyen 5 mau chi tieu tu blade vao bang giaoban_metric_templates"
```

---

## Task 9: CRUD mẫu chỉ tiêu

**Files:**
- Modify: `app/Http/Controllers/KHTH/GiaoBanConfigController.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/GiaoBan/GiaoBanConfigControllerTest.php` (bổ sung)

**Interfaces:**
- Consumes: `MetricValidator::validateJson()` (Task 3), `GiaoBanMetricTemplate` (Task 8), `traLoiChiTieu()` (Task 5).
- Produces:
  - `POST khth/giao-ban/cau-hinh/mau` → `khth.giao-ban-config-template-store`, body `name`, `block_type`, `metrics` (chuỗi JSON), `sort_order`
  - `POST khth/giao-ban/cau-hinh/mau/{id}` → `khth.giao-ban-config-template-update`, body `name`, `sort_order`, `is_active`, `metrics` (tuỳ chọn)

- [ ] **Step 1: Viết test (đỏ)**

```php
    /** @test */
    public function luu_mau_bi_chan_khi_chi_tieu_sai_schema()
    {
        $res = $this->actingAs($this->admin())->postJson(route('khth.giao-ban-config-template-store'), [
            'name' => 'Mẫu hỏng',
            'block_type' => 'dieu_tri',
            'metrics' => json_encode([['code' => 'x', 'name' => 'X', 'type' => 'end_type']]), // thieu end_codes
        ]);

        $res->assertStatus(422);
        $this->assertEquals('end_codes', $res->json('errors.0.field'));
    }
```

- [ ] **Step 2: Chạy test, xác nhận đỏ**

Chạy: `vendor/bin/phpunit --filter GiaoBanConfigControllerTest`
Kỳ vọng: FAIL — route `khth.giao-ban-config-template-store` chưa tồn tại (`InvalidArgumentException: Route ... not defined`)

- [ ] **Step 3: Thêm hai action**

```php
    public function storeTemplate(Request $request)
    {
        $this->validate($request, [
            'name' => 'required|string|max:255',
            'block_type' => 'required|in:dieu_tri,kham,can_lam_sang',
            'metrics' => 'required|string',
            'sort_order' => 'nullable|integer',
        ]);
        $loi = MetricValidator::validateJson($request->input('metrics'), $request->input('block_type'));
        if (!empty($loi)) return $this->traLoiChiTieu($loi);

        $t = \App\Models\GiaoBan\GiaoBanMetricTemplate::create(
            $request->only(['name', 'block_type', 'metrics', 'sort_order']) + ['is_active' => true]
        );
        return response()->json(['ok' => true, 'id' => $t->id]);
    }

    public function updateTemplate(Request $request, $id)
    {
        $t = \App\Models\GiaoBan\GiaoBanMetricTemplate::findOrFail($id);
        if ($request->filled('metrics')) {
            $loi = MetricValidator::validateJson($request->input('metrics'), $t->block_type);
            if (!empty($loi)) return $this->traLoiChiTieu($loi);
        }
        $t->update($request->only(['name', 'sort_order', 'is_active', 'metrics']));
        return response()->json(['ok' => true]);
    }
```

- [ ] **Step 4: Thêm route**

```php
        Route::post('giao-ban/cau-hinh/mau', 'KHTH\GiaoBanConfigController@storeTemplate')->name('khth.giao-ban-config-template-store');
        Route::post('giao-ban/cau-hinh/mau/{id}', 'KHTH\GiaoBanConfigController@updateTemplate')->name('khth.giao-ban-config-template-update');
```

**Thứ tự route quan trọng:** hai dòng này phải đặt **trước** `Route::post('giao-ban/cau-hinh/{id}', ...)` (dòng ~666), nếu không `{id}` sẽ nuốt mất `mau`.

- [ ] **Step 5: Chạy test, xác nhận xanh**

Chạy: `vendor/bin/phpunit --filter GiaoBanConfigControllerTest`
Kỳ vọng: PASS (4 test)

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/KHTH/GiaoBanConfigController.php routes/web.php tests/Feature/GiaoBan/GiaoBanConfigControllerTest.php
git commit -m "feat(giaoban): CRUD mau chi tieu co validate schema"
```

---

## Task 10: Tính thử (preview)

**Files:**
- Modify: `app/Services/GiaoBan/MetricSchema.php` (thêm `warningFor()`)
- Modify: `app/Http/Controllers/KHTH/GiaoBanConfigController.php` (thêm `preview()`)
- Modify: `routes/web.php`
- Test: `tests/Unit/GiaoBan/MetricSchemaTest.php` (bổ sung), `tests/Feature/GiaoBan/GiaoBanConfigControllerTest.php` (bổ sung)

**Interfaces:**
- Consumes: `MetricSchema`, `MetricValidator` (Task 2, 3), `GiaoBanMetricService::computeAll()` (đã có, **không sửa**), `GiaoBanDeptConfig` (đã có).
- Produces:
  - `MetricSchema::warningFor(array $metric, array $deptIds)` → `null | 'manual' | 'no_dept' | 'no_scope'` — hàm thuần.
  - `POST khth/giao-ban/cau-hinh/{id}/tinh-thu` → `khth.giao-ban-config-preview`. Body: `metrics` (chuỗi JSON), `block_type`, `his_department_ids` (chuỗi JSON), `from`, `to`. Trả `['rows' => [['code','name','value','warning']], 'ms' => int]`.

- [ ] **Step 1: Viết test cảnh báo (đỏ)**

```php
    /** @test */
    public function canh_bao_manual_cho_chi_tieu_nhap_tay()
    {
        $m = ['code' => 'cg', 'name' => 'Chuyên gia', 'type' => 'manual'];
        $this->assertEquals('manual', MetricSchema::warningFor($m, [12]));
    }

    /** @test */
    public function canh_bao_no_dept_khi_chua_gan_khoa_HIS()
    {
        $m = ['code' => 'bn_cu', 'name' => 'BN cũ', 'type' => 'census_from'];
        $this->assertEquals('no_dept', MetricSchema::warningFor($m, []));
        $this->assertNull(MetricSchema::warningFor($m, [12]));
    }

    /** @test */
    public function admission_khong_can_khoa_nen_khong_canh_bao()
    {
        $m = ['code' => 'vv', 'name' => 'Vào viện', 'type' => 'admission'];
        $this->assertNull(MetricSchema::warningFor($m, []));
    }

    /** @test */
    public function bed_count_dua_vao_bed_ids_nen_khong_canh_bao_thieu_khoa()
    {
        $m = ['code' => 'gyc', 'name' => 'Giường YC', 'type' => 'bed_count', 'bed_ids' => [5]];
        $this->assertNull(MetricSchema::warningFor($m, []));
    }

    /** @test */
    public function canh_bao_no_scope_khi_service_count_khong_co_pham_vi_nao()
    {
        // khong gan khoa HIS + khong khai pham vi cu the -> computeAll tra 0 trong im lang
        $m = ['code' => 'dv', 'name' => 'DV', 'type' => 'service_count', 'filter' => ['service_type_ids' => [2]]];
        $this->assertEquals('no_scope', MetricSchema::warningFor($m, []));

        // co khoa HIS -> computeAll tu gan request_department_ids -> khong canh bao
        $this->assertNull(MetricSchema::warningFor($m, [12]));

        // khai phong thuc hien cu the -> co pham vi du khong gan khoa
        $m2 = ['code' => 'dv', 'name' => 'DV', 'type' => 'service_count', 'filter' => ['execute_room_ids' => [9]]];
        $this->assertNull(MetricSchema::warningFor($m2, []));
    }
```

- [ ] **Step 2: Chạy test, xác nhận đỏ**

Chạy: `vendor/bin/phpunit --filter MetricSchemaTest`
Kỳ vọng: FAIL — `Call to undefined method ... ::warningFor()`

- [ ] **Step 3: Thêm `warningFor` vào `MetricSchema`**

Hàm này phản chiếu guard `$hasScope` ở `GiaoBanMetricService::computeAll` (dòng 412-431) **mà không sửa file đó**. Nếu sau này logic guard bên service đổi, hàm này phải đổi theo — ghi rõ trong docblock.

```php
    /** Cac type khong can khoa HIS de tinh duoc. */
    const KHONG_CAN_KHOA = ['admission', 'bed_count', 'manual'];

    /**
     * Canh bao cho mot chi tieu khi tinh thu.
     * Phan chieu guard $hasScope trong GiaoBanMetricService::computeAll (dong 412-431).
     * Neu logic guard ben do doi, sua ham nay theo.
     *
     * @return null|string 'manual' | 'no_dept' | 'no_scope'
     */
    public static function warningFor(array $metric, array $deptIds)
    {
        $type = isset($metric['type']) ? $metric['type'] : '';
        if ($type === 'manual') return 'manual';

        if ($type === 'service_count') {
            $f = isset($metric['filter']) ? $metric['filter'] : [];
            if (!empty($deptIds)) return null; // computeAll tu gan pham vi theo khoa cua config
            foreach (['execute_department_ids', 'execute_department_id',
                      'request_department_ids', 'request_department_id',
                      'execute_room_ids', 'service_ids'] as $k) {
                if (!empty($f[$k])) return null;
            }
            return 'no_scope';
        }

        if (in_array($type, self::KHONG_CAN_KHOA, true)) return null;

        return empty($deptIds) ? 'no_dept' : null;
    }
```

- [ ] **Step 4: Chạy test, xác nhận xanh**

Chạy: `vendor/bin/phpunit --filter MetricSchemaTest`
Kỳ vọng: PASS (10 test)

- [ ] **Step 5: Viết action `preview`**

Điểm mấu chốt: dựng `GiaoBanDeptConfig` **không lưu**. `computeAll` chỉ gọi `metricList()` và `hisDepartmentIds()` nên model chưa persist chạy được ngay.

```php
    /**
     * Tinh thu bo chi tieu DANG SOAN (chua luu) tren mot khoang thoi gian.
     * Khong ghi gi vao DB.
     */
    public function preview(Request $request, $id)
    {
        $this->validate($request, [
            'metrics' => 'required|string',
            'block_type' => 'required|in:dieu_tri,kham,can_lam_sang',
            'his_department_ids' => 'nullable|string',
            'from' => 'required|date_format:Y-m-d H:i:s',
            'to' => 'required|date_format:Y-m-d H:i:s',
        ]);

        $loi = MetricValidator::validateJson($request->input('metrics'), $request->input('block_type'));
        if (!empty($loi)) return $this->traLoiChiTieu($loi);

        // config tam, KHONG save()
        $tam = new GiaoBanDeptConfig();
        $tam->id = (int) $id;
        $tam->display_name = 'Tính thử';
        $tam->block_type = $request->input('block_type');
        $tam->his_department_ids = $request->input('his_department_ids', '[]');
        $tam->metrics = $request->input('metrics');

        $deptIds = $tam->hisDepartmentIds();
        $batDau = microtime(true);
        try {
            $giaTri = app(\App\Services\GiaoBan\GiaoBanMetricService::class)
                ->computeAll([$tam], $request->input('from'), $request->input('to'));
        } catch (\Exception $e) {
            return response()->json(['message' => 'Không lấy được số liệu từ HIS: ' . $e->getMessage()], 422);
        }

        $rows = [];
        foreach ($tam->metricList() as $m) {
            $key = $tam->id . '|' . $m['code'];
            $rows[] = [
                'code' => $m['code'],
                'name' => $m['name'],
                'value' => isset($giaTri[$key]) ? $giaTri[$key] : null,
                'warning' => MetricSchema::warningFor($m, $deptIds),
            ];
        }

        return response()->json([
            'rows' => $rows,
            'ms' => (int) round((microtime(true) - $batDau) * 1000),
        ]);
    }
```

Thêm `use App\Services\GiaoBan\MetricSchema;` ở đầu controller.

- [ ] **Step 6: Thêm route**

Đặt **trước** `Route::post('giao-ban/cau-hinh/{id}', ...)`:

```php
        Route::post('giao-ban/cau-hinh/{id}/tinh-thu', 'KHTH\GiaoBanConfigController@preview')->name('khth.giao-ban-config-preview');
```

- [ ] **Step 7: Viết Feature test cho preview (đỏ → xanh)**

Test chỉ kiểm nhánh validate, không chạm HIS:

```php
    /** @test */
    public function tinh_thu_tra_422_khi_chi_tieu_sai_schema()
    {
        $res = $this->actingAs($this->admin())->postJson(route('khth.giao-ban-config-preview', ['id' => 1]), [
            'metrics' => json_encode([['code' => 'x', 'name' => 'X', 'type' => 'end_type']]),
            'block_type' => 'dieu_tri',
            'his_department_ids' => '[]',
            'from' => '2026-07-26 07:00:00',
            'to' => '2026-07-27 07:00:00',
        ]);

        $res->assertStatus(422);
        $this->assertEquals('end_codes', $res->json('errors.0.field'));
    }
```

Chạy: `vendor/bin/phpunit --filter GiaoBanConfigControllerTest`
Kỳ vọng: PASS (5 test)

- [ ] **Step 8: Thử thật với HIS**

Bằng trình duyệt hoặc `php artisan tinker`, gọi `POST khth/giao-ban/cau-hinh/1/tinh-thu` với một bộ chỉ tiêu điều trị hợp lệ và `his_department_ids` là khoa thật.

Kỳ vọng: `rows` có đủ số chỉ tiêu, `value` là số, `ms` > 0.
Thử tiếp với `his_department_ids: '[]'`: kỳ vọng mọi `warning` là `no_dept`, `value` là 0 — **đây chính là cái bẫy mà preview sinh ra để lộ**.

- [ ] **Step 9: Commit**

```bash
git add app/Services/GiaoBan/MetricSchema.php app/Http/Controllers/KHTH/GiaoBanConfigController.php routes/web.php tests/Unit/GiaoBan/MetricSchemaTest.php tests/Feature/GiaoBan/GiaoBanConfigControllerTest.php
git commit -m "feat(giaoban): tinh thu chi tieu chua luu kem canh bao thieu pham vi"
```

---

## Task 11: Khung modal — danh sách card, thêm/xoá, kéo thả, lưu

Từ đây trở đi không có test tự động (dự án không có hạ tầng test JS). Mỗi task có bước kiểm tra trên trình duyệt với quan sát cụ thể — **phải làm thật, không được bỏ qua rồi báo xong**.

**Files:**
- Create: `resources/views/khth/partials/giaoban-metric-builder.blade.php`
- Create: `public/js/giaoban/metric-builder.js`
- Modify: `resources/views/khth/giaoban-config.blade.php`

**Interfaces:**
- Consumes: `MetricSchema::TYPES` (Task 2) qua `@json`; route `khth.giao-ban-config-update` (đã có).
- Produces: biến toàn cục `MetricBuilder` với:
  - `MetricBuilder.init({schema, catalogs, labels, routes, csrf})`
  - `MetricBuilder.open(config, onSaved)` — `config` là phần tử `STATE.configs`; `onSaved` gọi sau khi lưu thành công.

- [ ] **Step 1: Viết partial modal**

`resources/views/khth/partials/giaoban-metric-builder.blade.php` — chỉ markup, không logic:

```blade
<div class="modal fade" id="mb-modal" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal">&times;</button>
        <h4 class="modal-title">Chỉ tiêu — <span id="mb-dept-name"></span>
          <small id="mb-block-label" class="text-muted"></small></h4>
      </div>
      <div class="modal-body">
        <div class="btn-toolbar" style="margin-bottom:8px">
          <div class="btn-group">
            <button class="btn btn-default dropdown-toggle" data-toggle="dropdown">
              <i class="fa fa-plus"></i> Thêm chỉ tiêu <span class="caret"></span></button>
            <ul class="dropdown-menu" id="mb-add-menu"></ul>
          </div>
          <div class="btn-group">
            <button class="btn btn-default dropdown-toggle" data-toggle="dropdown">Nạp mẫu <span class="caret"></span></button>
            <ul class="dropdown-menu" id="mb-tpl-menu"></ul>
          </div>
          <div class="btn-group">
            <button class="btn btn-default dropdown-toggle" data-toggle="dropdown">Nhân bản từ khoa <span class="caret"></span></button>
            <ul class="dropdown-menu" id="mb-clone-menu"></ul>
          </div>
          <button class="btn btn-info" id="mb-preview"><i class="fa fa-bolt"></i> Tính thử</button>
        </div>

        <ul class="nav nav-tabs">
          <li class="active"><a href="#mb-tab-form" data-toggle="tab">Form</a></li>
          <li><a href="#mb-tab-json" data-toggle="tab">JSON (nâng cao)</a></li>
        </ul>
        <div class="tab-content" style="padding-top:10px">
          <div class="tab-pane active" id="mb-tab-form">
            <div id="mb-preview-box" style="display:none;margin-bottom:10px"></div>
            <div id="mb-list"></div>
            <p class="text-muted" id="mb-empty" style="display:none">
              <i>Chưa có chỉ tiêu nào. Bấm "Thêm chỉ tiêu" hoặc "Nạp mẫu".</i></p>
          </div>
          <div class="tab-pane" id="mb-tab-json">
            <textarea id="mb-json" class="form-control" rows="18" spellcheck="false"></textarea>
            <p class="help-block" id="mb-json-msg"></p>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <span id="mb-save-msg" class="text-danger pull-left" style="text-align:left"></span>
        <button class="btn btn-default" data-dismiss="modal">Huỷ</button>
        <button class="btn btn-primary" id="mb-save">Lưu chỉ tiêu</button>
      </div>
    </div>
  </div>
</div>
```

- [ ] **Step 2: Viết module JS (phần khung)**

`public/js/giaoban/metric-builder.js`:

```js
/* Form builder chi tieu giao ban. Render field dong tu MetricSchema — khong hard-code type nao. */
var MetricBuilder = (function ($) {
  var SCHEMA = {}, ROUTES = {}, CSRF = '', BLOCK_LABELS = {};
  var st = { cfg: null, metrics: [], onSaved: null };

  function esc(s) {
    return String(s === null || s === undefined ? '' : s).replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
  }

  function init(opts) {
    SCHEMA = opts.schema || {};
    ROUTES = opts.routes || {};
    CSRF = opts.csrf || '';
    BLOCK_LABELS = opts.blockLabels || {};
    bind();
  }

  /** Cac type dung duoc voi block hien tai. */
  function typesForBlock(block) {
    var out = [];
    for (var k in SCHEMA) {
      if (SCHEMA[k].blocks.indexOf(block) >= 0) out.push(k);
    }
    return out;
  }

  function open(cfg, onSaved) {
    st.cfg = cfg;
    st.onSaved = onSaved;
    try {
      var parsed = JSON.parse(cfg.metrics || '[]');
      st.metrics = Array.isArray(parsed) ? parsed : [];
    } catch (e) {
      st.metrics = [];
    }
    $('#mb-dept-name').text(cfg.display_name);
    $('#mb-block-label').text('[' + (BLOCK_LABELS[cfg.block_type] || cfg.block_type) + ']');
    $('#mb-save-msg').text('');
    $('#mb-preview-box').hide().empty();
    renderAddMenu();
    render();
    $('#mb-modal').modal('show');
  }

  function renderAddMenu() {
    var $m = $('#mb-add-menu').empty();
    typesForBlock(st.cfg.block_type).forEach(function (t) {
      $m.append('<li><a href="#" class="mb-add" data-type="' + t + '">' + esc(SCHEMA[t].label) + '</a></li>');
    });
  }

  /** Ma goi y tu ten: bo dau, thay khoang trang bang _. */
  function slug(name) {
    var s = String(name || '').toLowerCase()
      .replace(/[àáạảãâầấậẩẫăằắặẳẵ]/g, 'a').replace(/[èéẹẻẽêềếệểễ]/g, 'e')
      .replace(/[ìíịỉĩ]/g, 'i').replace(/[òóọỏõôồốộổỗơờớợởỡ]/g, 'o')
      .replace(/[ùúụủũưừứựửữ]/g, 'u').replace(/[ỳýỵỷỹ]/g, 'y').replace(/đ/g, 'd')
      .replace(/[^a-z0-9]+/g, '_').replace(/^_+|_+$/g, '');
    if (!s || !/^[a-z]/.test(s)) s = 'ct_' + s;
    return s.substring(0, 32);
  }

  function maDuyNhat(goc) {
    var ma = goc, i = 2;
    while (st.metrics.some(function (m) { return m.code === ma; })) {
      ma = goc.substring(0, 29) + '_' + i;
      i++;
    }
    return ma;
  }

  function render() {
    var $l = $('#mb-list').empty();
    $('#mb-empty').toggle(st.metrics.length === 0);

    st.metrics.forEach(function (m, i) {
      var def = SCHEMA[m.type] || { label: m.type };
      $l.append(
        '<div class="panel panel-default mb-card" data-i="' + i + '" style="margin-bottom:6px">' +
          '<div class="panel-heading" style="padding:6px 10px;cursor:move">' +
            '<span class="mb-handle text-muted" style="margin-right:8px">&#x283F;</span>' +
            '<code>' + esc(m.code) + '</code> ' +
            '<b class="mb-name-view">' + esc(m.name) + '</b> ' +
            '<span class="label label-default">' + esc(def.label) + '</span> ' +
            '<span class="mb-warn"></span>' +
            '<span class="pull-right">' +
              '<a href="#" class="mb-toggle" title="Mở/đóng"><i class="fa fa-chevron-down"></i></a> ' +
              '<a href="#" class="mb-del text-red" title="Xoá"><i class="fa fa-trash"></i></a>' +
            '</span>' +
          '</div>' +
          '<div class="panel-body mb-body" style="display:none">' + renderBody(m, i) + '</div>' +
        '</div>'
      );
    });

    $('#mb-json').val(JSON.stringify(st.metrics, null, 2));
    $l.sortable({ handle: '.mb-handle', axis: 'y', update: onSort });
  }

  /** Task 12 se thay ham nay bang render field dong tu schema. */
  function renderBody(m, i) {
    return '<div class="row">' +
      '<div class="col-md-4"><label>Mã chỉ tiêu</label>' +
        '<input class="form-control mb-f" data-k="code" value="' + esc(m.code) + '"></div>' +
      '<div class="col-md-8"><label>Tên hiển thị</label>' +
        '<input class="form-control mb-f" data-k="name" value="' + esc(m.name) + '"></div>' +
      '</div>';
  }

  function onSort() {
    var thuTuMoi = [];
    $('#mb-list .mb-card').each(function () {
      thuTuMoi.push(st.metrics[$(this).data('i')]);
    });
    st.metrics = thuTuMoi;
    render();
  }

  function themChiTieu(type) {
    var def = SCHEMA[type];
    var ten = def.label;
    st.metrics.push({ code: maDuyNhat(slug(ten)), name: ten, type: type });
    render();
    $('#mb-list .mb-card').last().find('.mb-body').show();
  }

  function luu() {
    $('#mb-save-msg').text('');
    $('#mb-list .mb-card').removeClass('panel-danger').addClass('panel-default');

    $.post(ROUTES.update.replace('__ID__', st.cfg.id), {
      _token: CSRF,
      metrics: JSON.stringify(st.metrics),
      block_type: st.cfg.block_type
    }).done(function () {
      $('#mb-modal').modal('hide');
      if (typeof st.onSaved === 'function') st.onSaved(JSON.stringify(st.metrics));
    }).fail(function (xhr) {
      hienLoi(xhr);
    });
  }

  /** To do dung card sai + hien thong bao (Task 15 mo rong them cho tab JSON). */
  function hienLoi(xhr) {
    var res = xhr.responseJSON || {};
    $('#mb-save-msg').text(res.message || 'Lỗi lưu chỉ tiêu');
    (res.errors || []).forEach(function (e) {
      if (e.index < 0) return;
      var $card = $('#mb-list .mb-card').eq(e.index);
      $card.removeClass('panel-default').addClass('panel-danger');
      $card.find('.mb-body').show();
      $card.find('.mb-warn').html(' <span class="text-red">' + esc(e.field + ': ' + e.message) + '</span>');
    });
  }

  function bind() {
    $(document).on('click', '.mb-add', function (e) {
      e.preventDefault();
      themChiTieu($(this).data('type'));
    });
    $(document).on('click', '.mb-toggle', function (e) {
      e.preventDefault();
      $(this).closest('.mb-card').find('.mb-body').toggle();
    });
    $(document).on('click', '.mb-del', function (e) {
      e.preventDefault();
      var i = $(this).closest('.mb-card').data('i');
      if (!confirm('Xoá chỉ tiêu "' + st.metrics[i].name + '"?')) return;
      st.metrics.splice(i, 1);
      render();
    });
    $(document).on('input', '#mb-list .mb-f', function () {
      var $c = $(this).closest('.mb-card');
      st.metrics[$c.data('i')][$(this).data('k')] = $(this).val();
      $c.find('.mb-name-view').text($(this).data('k') === 'name' ? $(this).val() : $c.find('.mb-name-view').text());
    });
    $(document).on('click', '#mb-save', luu);
  }

  return { init: init, open: open };
})(jQuery);
```

- [ ] **Step 3: Nối vào trang cấu hình**

Trong `resources/views/khth/giaoban-config.blade.php`:

1. Cuối `@section('content')` (trước dòng `@stop` ở dòng 98), thêm:
```blade
@include('khth.partials.giaoban-metric-builder')
```

2. Đầu `@section('js')`, thêm script và khởi tạo:
```blade
<script src="{{ asset('js/giaoban/metric-builder.js') }}"></script>
<script>
MetricBuilder.init({
  schema: @json(\App\Services\GiaoBan\MetricSchema::TYPES),
  blockLabels: { dieu_tri: 'Điều trị (nội trú)', kham: 'Khám (ngoại trú)', can_lam_sang: 'Cận lâm sàng' },
  csrf: '{{ csrf_token() }}',
  routes: {
    update: '{{ url('khth/giao-ban/cau-hinh') }}/__ID__',
    catalogs: '{{ route('khth.giao-ban-config-catalogs') }}',
    catalog: '{{ url('khth/giao-ban/cau-hinh/danh-muc') }}/__KEY__',
    preview: '{{ url('khth/giao-ban/cau-hinh') }}/__ID__/tinh-thu'
  }
});
</script>
```

3. Thêm hàm đếm cạnh `parseIds` (dòng 140-142):
```js
function demChiTieu(jsonStr) {
  try { var a = JSON.parse(jsonStr || '[]'); return Array.isArray(a) ? a.length : 0; } catch (e) { return '?'; }
}
```

4. Trong `renderConfigs()`, thay hai dòng 154-155 (textarea `.f-metrics` + select `.f-tpl`) bằng một nút:
```js
      '<td><button class="btn btn-default btn-sm btn-edit-metrics">Chỉ tiêu (' + demChiTieu(c.metrics) + ') <i class="fa fa-pencil"></i></button></td>' +
```

5. Trong handler `.btn-save-cfg` (dòng 240-252), **bỏ** dòng `metrics: $tr.find('.f-metrics').val(),` — chỉ tiêu giờ lưu riêng qua modal, hàng bảng chỉ lưu tên/khối/khoa/thứ tự.

6. Trong `$(function () {...})`, thêm handler mở modal:
```js
  $('#tbl-configs').on('click', '.btn-edit-metrics', function () {
    var id = $(this).closest('tr').data('id');
    var cfg = null;
    STATE.configs.forEach(function (c) { if (c.id === id) cfg = c; });
    if (!cfg) return;
    // lay block_type dang chon tren hang (co the vua doi chua luu)
    cfg = $.extend({}, cfg, { block_type: $(this).closest('tr').find('.f-block').val() });
    MetricBuilder.open(cfg, function () { loadAll(); });
  });
```

7. **Bỏ** handler `.f-tpl` (dòng 232-238) và **bỏ** 5 khối `<script type="application/json" id="tpl-*">` (dòng 60-97) — mẫu giờ nằm trong DB (Task 8). Nhưng handler `#btn-add` (dòng 216-223) đang dùng `$('#tpl-dieu_tri').text()`; thay bằng mảng inline tối thiểu để tạo khoa mới vẫn chạy:
```js
      metrics: JSON.stringify([{ code: 'bn_cu', name: 'BN cũ', type: 'census_from' }])
```

- [ ] **Step 4: Kiểm tra jQuery UI sortable có sẵn**

AdminLTE của dự án có thể chưa nạp jQuery UI. Kiểm tra trên trình duyệt (Console): gõ `typeof jQuery.fn.sortable`.
- Nếu ra `"function"` → xong, đi tiếp.
- Nếu ra `"undefined"` → thêm vào `@section('js')` **trước** `metric-builder.js`:
```blade
<script src="{{ asset('adminlte/plugins/jQueryUI/jquery-ui.min.js') }}"></script>
```
Kiểm tra đường dẫn thật bằng `ls public/adminlte/plugins/` trước khi ghi; nếu không có, tìm file jQuery UI khác trong `public/` bằng `Glob` với mẫu `**/jquery-ui*.js`.

- [ ] **Step 5: Kiểm tra trên trình duyệt**

Mở `khth/giao-ban/cau-hinh` bằng tài khoản có quyền `giaoban-admin`. Quan sát từng điểm:

1. Cột "Chỉ tiêu" là **nút** ghi số lượng đúng (ví dụ `Chỉ tiêu (8)`), không còn textarea JSON.
2. Bấm nút → modal mở, tiêu đề có tên khoa và nhãn khối.
3. Danh sách card đúng số lượng, mỗi card hiện `code`, tên, nhãn loại.
4. Bấm mũi tên → card mở ra, sửa "Tên hiển thị" thì chữ đậm trên đầu card đổi theo.
5. Kéo card bằng biểu tượng ⣿ → thứ tự đổi, thả ra không lỗi Console.
6. Bấm "Thêm chỉ tiêu" → menu liệt kê **chỉ các loại hợp khối**: khoa khối `dieu_tri` không được thấy "Đếm dịch vụ".
7. Bấm 🗑 → hỏi xác nhận, đồng ý thì card biến mất.
8. Bấm "Lưu chỉ tiêu" → modal đóng, bảng nạp lại, số trên nút đổi đúng.
9. Cố tình sửa `code` thành `BN_Cu` rồi Lưu → **card đó viền đỏ**, mở sẵn, hiện thông báo lỗi tiếng Việt; modal **không** đóng.

Điểm 9 là điểm nghiệm thu quan trọng nhất của task này — nó chứng minh đường 422 từ Task 5 nối đúng tới UI.

- [ ] **Step 6: Commit**

```bash
git add resources/views/khth/partials/giaoban-metric-builder.blade.php public/js/giaoban/metric-builder.js resources/views/khth/giaoban-config.blade.php
git commit -m "feat(giaoban): khung modal form builder chi tieu, keo tha va to do loi 422"
```

---

## Task 12: Render field động từ schema + danh mục nhóm nhỏ

Task này là trái tim của form builder: **không một dòng `if (type === ...)` nào**. Thêm loại chỉ tiêu mới ở `MetricSchema` là form tự có field.

Vì `renderBody` đọc thẳng `fields`, chỉ tiêu **nhập tay cũng render xong luôn** trong task này (`unit`, `hint`, `value_type`, `min`, `max`, `required`, `default`, `carry_over`) — chúng chỉ là các widget `text`/`number`/`select`/`bool` khai trong schema.

**Files:**
- Modify: `public/js/giaoban/metric-builder.js`

**Interfaces:**
- Consumes: `SCHEMA[type].fields`, `.filter`, `.group` (Task 2); route `catalogs` (Task 6).
- Produces: `renderBody()` sinh field theo `widget`; giá trị ghi vào `m[field]`, `m.filter[field]`, hoặc `m.input[field]` tuỳ vị trí khai báo.

- [ ] **Step 1: Tải danh mục nhóm nhỏ khi mở modal lần đầu**

Thêm vào module, và sửa `open()` để chờ danh mục xong mới render:

```js
  var CATALOGS = null; // null = chua tai

  function taiDanhMuc(xong) {
    if (CATALOGS) { xong(); return; }
    $.get(ROUTES.catalogs, function (res) {
      CATALOGS = res.catalogs || {};
      xong();
    }).fail(function () {
      CATALOGS = {};                       // HIS loi -> van mo duoc modal, dropdown rong
      $('#mb-save-msg').text('Không tải được danh mục HIS — các ô chọn sẽ trống.');
      xong();
    });
  }
```

Trong `open()`, bọc phần render:

```js
    taiDanhMuc(function () {
      renderAddMenu();
      render();
      $('#mb-modal').modal('show');
    });
```

- [ ] **Step 2: Thay `renderBody` bằng bản render theo schema**

```js
  /** Doc gia tri cua mot field theo vi tri khai bao: goc / filter / input. */
  function layGiaTri(m, noi, ten) {
    if (noi === 'filter') return (m.filter || {})[ten];
    if (noi === 'input') return (m.input || {})[ten];
    return m[ten];
  }

  function datGiaTri(m, noi, ten, v) {
    var rong = v === '' || v === null || v === undefined ||
               (Array.isArray(v) && v.length === 0);
    if (noi === 'goc') {
      if (rong) delete m[ten]; else m[ten] = v;
      return;
    }
    if (!m[noi]) m[noi] = {};
    if (rong) delete m[noi][ten]; else m[noi][ten] = v;
    if (Object.keys(m[noi]).length === 0) delete m[noi];
  }

  /** Mot o nhap, dua tren khai bao widget trong MetricSchema. */
  function renderField(m, i, noi, ten, meta) {
    var id = 'mb-' + i + '-' + noi + '-' + ten;
    var v = layGiaTri(m, noi, ten);
    var nhan = esc(meta.label || ten);
    var attr = 'id="' + id + '" data-i="' + i + '" data-noi="' + noi + '" data-ten="' + ten + '"';
    var h = '';

    if (meta.widget === 'catalog_multi') {
      var kieu = meta.value === 'string' ? 'string' : 'int';
      h = '<select class="form-control mb-cat" multiple ' + attr +
          ' data-catalog="' + meta.catalog + '" data-kieu="' + kieu + '"></select>';
    } else if (meta.widget === 'bool') {
      h = '<div class="checkbox" style="margin-top:0"><label>' +
          '<input type="checkbox" class="mb-w" ' + attr + (v ? ' checked' : '') + '> ' + nhan +
          '</label></div>';
      return '<div class="col-md-3">' + h + '</div>';   // bool tu chua nhan
    } else if (meta.widget === 'select') {
      h = '<select class="form-control mb-w" ' + attr + '>';
      (meta.options || []).forEach(function (o) {
        h += '<option value="' + esc(o) + '"' + (v === o ? ' selected' : '') + '>' + esc(o) + '</option>';
      });
      h += '</select>';
    } else if (meta.widget === 'number' || meta.widget === 'int') {
      h = '<input type="number" class="form-control mb-w" ' + attr +
          ' value="' + (v === undefined || v === null ? '' : esc(v)) + '">';
    } else {
      h = '<input type="text" class="form-control mb-w" ' + attr +
          ' value="' + (v === undefined || v === null ? '' : esc(v)) +
          '" maxlength="' + (meta.max || 255) + '">';
    }

    var batBuoc = meta.required ? ' <span class="text-red">*</span>' : '';
    return '<div class="col-md-4" style="margin-bottom:8px">' +
             '<label style="font-weight:normal">' + nhan + batBuoc + '</label>' + h +
           '</div>';
  }

  function renderBody(m, i) {
    var def = SCHEMA[m.type] || { fields: {}, filter: {} };
    var noiFields = def.group === 'input' ? 'input' : 'goc';

    var h = '<div class="row">' +
      '<div class="col-md-4"><label style="font-weight:normal">Mã chỉ tiêu</label>' +
        '<input class="form-control mb-f" data-k="code" value="' + esc(m.code) + '"></div>' +
      '<div class="col-md-8"><label style="font-weight:normal">Tên hiển thị</label>' +
        '<input class="form-control mb-f" data-k="name" value="' + esc(m.name) + '"></div>' +
      '</div>';

    var oField = '';
    for (var ten in (def.fields || {})) {
      oField += renderField(m, i, noiFields, ten, def.fields[ten]);
    }
    if (oField) h += '<div class="row" style="margin-top:6px">' + oField + '</div>';

    var oFilter = '';
    for (var f in (def.filter || {})) {
      oFilter += renderField(m, i, 'filter', f, def.filter[f]);
    }
    if (oFilter) {
      h += '<div style="margin-top:6px"><b class="text-muted">Điều kiện lọc</b></div>' +
           '<div class="row">' + oFilter + '</div>';
    }

    return h;
  }
```

- [ ] **Step 3: Nạp select2 cho các ô danh mục sau khi render**

Thêm hàm và gọi ở cuối `render()`:

```js
  /** Gan select2 cho cac o danh mux nho (du lieu co san trong CATALOGS). */
  function ganSelect2($goc) {
    $goc.find('.mb-cat').each(function () {
      var $s = $(this);
      if ($s.data('select2')) return;
      var key = $s.data('catalog');
      var kieu = $s.data('kieu');
      var i = $s.data('i'), noi = $s.data('noi'), ten = $s.data('ten');
      var daChon = layGiaTri(st.metrics[i], noi, ten) || [];
      var daChonStr = daChon.map(String);

      (CATALOGS[key] || []).forEach(function (o) {
        var chon = daChonStr.indexOf(String(o.id)) >= 0;
        $s.append('<option value="' + esc(o.id) + '"' + (chon ? ' selected' : '') + '>' + esc(o.name) + '</option>');
      });

      $s.select2({ width: '100%', placeholder: 'Chọn...', dropdownParent: $('#mb-modal') });
      $s.on('change', function () {
        var v = ($s.val() || []).map(function (x) { return kieu === 'int' ? parseInt(x, 10) : String(x); });
        datGiaTri(st.metrics[i], noi, ten, v);
      });
    });
  }
```

Trong `render()`, sau `$l.sortable(...)`, thêm `ganSelect2($l);`

**Lưu ý `dropdownParent`:** không có nó, dropdown select2 bị modal Bootstrap cắt mất và không gõ được. Đây là lỗi kinh điển khi nhét select2 vào modal.

- [ ] **Step 4: Ghi giá trị cho các widget còn lại**

Thêm vào `bind()`:

```js
    $(document).on('change input', '#mb-list .mb-w', function () {
      var $e = $(this);
      var m = st.metrics[$e.data('i')];
      var v;
      if ($e.attr('type') === 'checkbox') {
        v = $e.is(':checked') ? true : '';          // '' -> datGiaTri xoa khoa
      } else if ($e.attr('type') === 'number') {
        v = $e.val() === '' ? '' : Number($e.val());
      } else {
        v = $e.val();
      }
      datGiaTri(m, $e.data('noi'), $e.data('ten'), v);
      $('#mb-json').val(JSON.stringify(st.metrics, null, 2));
    });
```

- [ ] **Step 5: Kiểm tra trên trình duyệt**

Mở modal cho **ba khoa khác khối** và quan sát:

1. **Khối điều trị**, thêm chỉ tiêu "Kết thúc điều trị" → mở card thấy ô "Loại kết thúc **\***" là select2 đa chọn, **liệt kê tên tiếng Việt** (Ra viện, Chuyển viện...), không phải số.
2. Chọn 2 loại → sang tab JSON, thấy `"end_codes": ["RV","CV"]` (mã chữ, không phải id số).
3. **Khối cận lâm sàng**, thêm "Đếm dịch vụ" → thấy nhóm "Điều kiện lọc" với các ô "Loại dịch vụ", "Loại CĐHA", "Loại xét nghiệm", "Ưu tiên từ/đến".
4. Chọn "Loại CĐHA" = CT → JSON có `"filter": {"diim_type_ids":[2]}` (số nguyên, không phải chuỗi `"2"`).
5. Bỏ chọn hết → khoá `diim_type_ids` **biến mất** khỏi JSON, và nếu `filter` rỗng thì cả `filter` cũng biến mất. Đây là điểm dễ sai: JSON còn `"filter":{}` thì rác, tuy vẫn hợp lệ.
6. **Khối khám**, thêm "Nhập tay" → thấy đủ 8 ô: Đơn vị, Giải thích cho khoa, Kiểu giá trị (dropdown int/decimal/percent), Nhỏ nhất, Lớn nhất, Bắt buộc nhập (checkbox), Giá trị mặc định, Kế thừa từ phiên trước (checkbox).
7. Điền Đơn vị = `lượt`, tick Bắt buộc → JSON có `"input":{"unit":"lượt","required":true}`.
8. Đặt Nhỏ nhất = 10, Lớn nhất = 5, bấm Lưu → 422, card viền đỏ, thông báo "Giá trị nhỏ nhất không được lớn hơn giá trị lớn nhất."
9. Mở dropdown select2 trong modal → danh sách **không bị modal cắt**, gõ tìm được.

- [ ] **Step 6: Commit**

```bash
git add public/js/giaoban/metric-builder.js
git commit -m "feat(giaoban): render field chi tieu dong tu MetricSchema + select2 danh muc"
```

---

## Task 13: Danh mục lớn — select2 AJAX + tra ngược khi mở cấu hình cũ

**Files:**
- Modify: `public/js/giaoban/metric-builder.js`

**Interfaces:**
- Consumes: route `catalog` (Task 7) với `?q=` và `?ids=`; `MetricSchema` field có `remote: true`? — **không**: schema không khai `remote`, nó nằm ở `GiaoBanCatalogService`. JS nhận biết qua danh sách truyền vào `init()`.
- Produces: `MetricBuilder.init({..., remoteCatalogs: ['service','room','bed']})`.

- [ ] **Step 1: Truyền danh sách danh mục lớn từ blade**

Trong `resources/views/khth/giaoban-config.blade.php`, thêm vào `MetricBuilder.init({...})`:

```blade
  remoteCatalogs: @json(array_values(array_diff(
      \App\Services\GiaoBan\GiaoBanCatalogService::allKeys(),
      \App\Services\GiaoBan\GiaoBanCatalogService::smallKeys()
  ))),
```

- [ ] **Step 2: Nhận trong module**

```js
  var REMOTE = [];
  // trong init():
  REMOTE = opts.remoteCatalogs || [];

  function laDanhMucLon(key) { return REMOTE.indexOf(key) >= 0; }
```

- [ ] **Step 3: Tách `ganSelect2` thành hai nhánh**

Sửa `ganSelect2`, phần sau khi lấy `key`, `kieu`, `i`, `noi`, `ten`, `daChon`:

```js
      if (!laDanhMucLon(key)) {
        // nhom nho: du lieu co san
        var daChonStr = daChon.map(String);
        (CATALOGS[key] || []).forEach(function (o) {
          var chon = daChonStr.indexOf(String(o.id)) >= 0;
          $s.append('<option value="' + esc(o.id) + '"' + (chon ? ' selected' : '') + '>' + esc(o.name) + '</option>');
        });
        $s.select2({ width: '100%', placeholder: 'Chọn...', dropdownParent: $('#mb-modal') });
        ganDoiGiaTri($s, i, noi, ten, kieu);
        return;
      }

      // nhom lon: tim qua AJAX
      $s.select2({
        width: '100%',
        placeholder: 'Gõ ≥ 2 ký tự để tìm...',
        dropdownParent: $('#mb-modal'),
        minimumInputLength: 2,
        ajax: {
          url: ROUTES.catalog.replace('__KEY__', key),
          dataType: 'json',
          delay: 300,
          data: function (params) { return { q: params.term }; },
          processResults: function (rows) {
            return { results: (rows || []).map(function (o) { return { id: o.id, text: o.name }; }) };
          }
        }
      });
      ganDoiGiaTri($s, i, noi, ten, kieu);

      // tra nguoc ID -> ten cho gia tri da luu, neu khong select2 hien so tran
      if (daChon.length) {
        $.get(ROUTES.catalog.replace('__KEY__', key), { ids: daChon.join(',') }, function (rows) {
          (rows || []).forEach(function (o) {
            $s.append(new Option(o.name, o.id, true, true));
          });
          $s.trigger('change.select2');   // change.select2 KHONG kich handler ghi gia tri
        });
      }
```

và tách handler ghi giá trị ra hàm riêng để hai nhánh dùng chung:

```js
  function ganDoiGiaTri($s, i, noi, ten, kieu) {
    $s.on('change', function () {
      var v = ($s.val() || []).map(function (x) { return kieu === 'int' ? parseInt(x, 10) : String(x); });
      datGiaTri(st.metrics[i], noi, ten, v);
      $('#mb-json').val(JSON.stringify(st.metrics, null, 2));
    });
  }
```

**Vì sao `change.select2` chứ không phải `change`:** `change` sẽ chạy handler ghi giá trị và ghi đè `st.metrics` bằng đúng giá trị vừa nạp — vô hại lần này, nhưng nếu request tra ngược lỗi (HIS chết) thì nó ghi mảng rỗng đè lên cấu hình cũ, **mất dữ liệu người dùng chưa hề đụng vào**. Dùng `change.select2` để chỉ vẽ lại giao diện.

- [ ] **Step 4: Kiểm tra trên trình duyệt**

1. Khối điều trị, thêm chỉ tiêu "Đếm BN trên giường chỉ định" → ô "Giường **\***" hiện placeholder "Gõ ≥ 2 ký tự để tìm...".
2. Gõ 1 ký tự → không gọi API (xem tab Network). Gõ 2 ký tự → có request tới `danh-muc/bed?q=...`, kết quả hiện tên giường.
3. Chọn 2 giường → JSON có `"bed_ids":[<id>,<id>]` là số nguyên.
4. Bấm Lưu, đóng modal, **mở lại** → ô Giường hiển thị **đúng tên** hai giường đã chọn, không phải số. Đây là điểm nghiệm thu của task này.
5. Khối cận lâm sàng, ô "Dịch vụ cụ thể" và "Phòng thực hiện" hành xử tương tự.

- [ ] **Step 5: Commit**

```bash
git add public/js/giaoban/metric-builder.js resources/views/khth/giaoban-config.blade.php
git commit -m "feat(giaoban): select2 AJAX cho danh muc lon va tra nguoc ten khi mo lai"
```

---

## Task 14: Widget phạm vi khoa + nhóm "Khác"

Hai chỗ form phải thông minh hơn JSON. Không có task này thì `service_count` vẫn là bãi mìn: đủ 6 khoá phạm vi bày ra cho người dùng tự đoán, và `diim_type_other_of` không có cách nào diễn đạt.

**Files:**
- Modify: `public/js/giaoban/metric-builder.js`

**Interfaces:**
- Consumes: `SCHEMA[type].scope === 'service_dept'`, `SCHEMA[type].filter[x].other_key` (Task 2).
- Produces: không có API mới; chỉ đổi cách sinh JSON.

- [ ] **Step 1: Ẩn các khoá phạm vi khỏi vùng "Điều kiện lọc"**

Trong `renderBody`, khi duyệt `def.filter`, bỏ qua `execute_room_ids` và `service_ids` nếu type có `scope` — chúng thuộc về widget phạm vi:

```js
    var PHAM_VI_FIELDS = ['execute_room_ids', 'service_ids'];
    for (var f in (def.filter || {})) {
      if (def.scope === 'service_dept' && PHAM_VI_FIELDS.indexOf(f) >= 0) continue;
      oFilter += renderField(m, i, 'filter', f, def.filter[f]);
    }
```

- [ ] **Step 2: Thêm widget phạm vi**

```js
  /** Suy ra pham vi hien tai tu filter da luu. */
  function phamViHienTai(m) {
    var f = m.filter || {};
    if (f.execute_department_id_self) return 'self';
    if (f.execute_room_ids || f.service_ids || f.execute_department_ids ||
        f.execute_department_id || f.request_department_ids || f.request_department_id) return 'explicit';
    return 'request';
  }

  function renderPhamVi(m, i, def) {
    var pv = phamViHienTai(m);
    var r = function (v, nhan) {
      return '<div class="radio" style="margin:2px 0"><label>' +
        '<input type="radio" name="mb-pv-' + i + '" class="mb-pv" data-i="' + i + '" value="' + v + '"' +
        (pv === v ? ' checked' : '') + '> ' + nhan + '</label></div>';
    };
    var h = '<div style="margin-top:6px"><b class="text-muted">Phạm vi khoa</b></div>' +
      r('self', 'Dịch vụ do <b>khoa này thực hiện</b>') +
      r('request', 'Dịch vụ do <b>khoa này chỉ định</b>') +
      r('explicit', 'Chỉ định <b>phòng / dịch vụ cụ thể</b>');

    if (pv === 'explicit') {
      h += '<div class="row">' +
        renderField(m, i, 'filter', 'execute_room_ids', def.filter.execute_room_ids) +
        renderField(m, i, 'filter', 'service_ids', def.filter.service_ids) +
        '</div>';
    }
    return h;
  }
```

Trong `renderBody`, trước phần "Điều kiện lọc":

```js
    if (def.scope === 'service_dept') h += renderPhamVi(m, i, def);
```

Handler đổi phạm vi — dọn sạch các khoá cũ để không sinh JSON mâu thuẫn (validator ở Task 3 sẽ chặn, nhưng để người dùng gặp lỗi vì UI để lại rác là dở):

```js
    $(document).on('change', '#mb-list .mb-pv', function () {
      var i = $(this).data('i');
      var m = st.metrics[i];
      var f = m.filter || {};
      ['execute_department_id_self', 'execute_room_ids', 'service_ids',
       'execute_department_ids', 'execute_department_id',
       'request_department_ids', 'request_department_id'].forEach(function (k) { delete f[k]; });

      if ($(this).val() === 'self') f.execute_department_id_self = true;
      // 'request' = khong khai gi, computeAll tu gan theo khoa cua config
      m.filter = Object.keys(f).length ? f : undefined;
      if (!m.filter) delete m.filter;
      render();
      $('#mb-list .mb-card').eq(i).find('.mb-body').show();
    });
```

- [ ] **Step 3: Thêm checkbox nhóm "Khác"**

Trong `renderField`, nhánh `catalog_multi`, nếu `meta.other_key` thì thêm checkbox ngay dưới ô chọn:

```js
      if (meta.other_key) {
        var laKhac = !!((m.filter || {})[meta.other_key]);
        h += '<div class="checkbox" style="margin:2px 0"><label>' +
             '<input type="checkbox" class="mb-other" data-i="' + i + '" data-ten="' + ten +
             '" data-other="' + meta.other_key + '"' + (laKhac ? ' checked' : '') + '> ' +
             '<small>Là nhóm <b>Khác</b> — lấy phần còn lại ngoài các loại đã chọn</small></label></div>';
      }
```

Để nhánh này lấy được `m`, đổi chữ ký `renderField(m, i, noi, ten, meta)` — nó đã nhận `m` rồi, dùng thẳng.

Khi bật checkbox, giá trị đang chọn chuyển từ `*_ids` sang `*_other_of`; khi tắt thì chuyển ngược:

```js
    $(document).on('change', '#mb-list .mb-other', function () {
      var i = $(this).data('i');
      var ten = $(this).data('ten'), other = $(this).data('other');
      var m = st.metrics[i];
      var f = m.filter || {};
      if ($(this).is(':checked')) {
        if (f[ten]) { f[other] = f[ten]; delete f[ten]; }
      } else {
        if (f[other]) { f[ten] = f[other]; delete f[other]; }
      }
      m.filter = Object.keys(f).length ? f : undefined;
      if (!m.filter) delete m.filter;
      render();
      $('#mb-list .mb-card').eq(i).find('.mb-body').show();
    });
```

Và trong `ganSelect2`, khi nạp giá trị đã chọn cho field có `other_key`, phải đọc từ **cả hai** khoá:

```js
      var daChon = layGiaTri(st.metrics[i], noi, ten) || [];
      var otherKey = $s.data('other-key');
      if (!daChon.length && otherKey) daChon = (st.metrics[i].filter || {})[otherKey] || [];
```

Muốn có `data-other-key`, thêm vào thẻ `<select>` ở `renderField`:
`(meta.other_key ? ' data-other-key="' + meta.other_key + '"' : '')`

Và trong `ganDoiGiaTri`, ghi vào đúng khoá đang bật:

```js
  function ganDoiGiaTri($s, i, noi, ten, kieu) {
    $s.on('change', function () {
      var v = ($s.val() || []).map(function (x) { return kieu === 'int' ? parseInt(x, 10) : String(x); });
      var otherKey = $s.data('other-key');
      var dangKhac = otherKey && $('#mb-list .mb-other[data-i="' + i + '"][data-ten="' + ten + '"]').is(':checked');
      datGiaTri(st.metrics[i], noi, dangKhac ? otherKey : ten, v);
      $('#mb-json').val(JSON.stringify(st.metrics, null, 2));
    });
  }
```

- [ ] **Step 4: Nhãn card cho nhóm "Khác"**

Trong `render()`, phần nhãn loại, thêm chú thích để nhìn phát hiểu:

```js
        var ghiChu = '';
        var d = SCHEMA[m.type] || {};
        for (var ff in (d.filter || {})) {
          var ok = (d.filter[ff] || {}).other_key;
          if (ok && (m.filter || {})[ok]) ghiChu = ' <small class="text-muted">(nhóm Khác)</small>';
        }
```
rồi chèn `ghiChu` sau nhãn loại trong chuỗi HTML của card.

- [ ] **Step 5: Kiểm tra trên trình duyệt**

Khoa khối cận lâm sàng:

1. Thêm "Đếm dịch vụ" → thấy 3 radio phạm vi, mặc định là "khoa này chỉ định".
2. Chọn "khoa này thực hiện" → JSON có `"filter":{"execute_department_id_self":true}`.
3. Chọn "phòng / dịch vụ cụ thể" → hiện thêm 2 ô select2 remote; JSON **không còn** `execute_department_id_self`.
4. Chọn lại "khoa này chỉ định" → `filter` **biến mất hoàn toàn** khỏi JSON (hoặc chỉ còn các khoá lọc khác).
5. Chọn "Loại CĐHA" = X-Quang, CT, MRI → JSON `"diim_type_ids":[1,2,3]`. Tick "Là nhóm Khác" → JSON đổi thành `"diim_type_other_of":[1,2,3]`, `diim_type_ids` biến mất, nhãn card có `(nhóm Khác)`.
6. Bỏ tick → quay lại `diim_type_ids`.
7. Lưu, mở lại → trạng thái checkbox và danh sách chọn giữ nguyên.

Điểm 4 và 6 là hai chỗ dễ để lại rác nhất.

- [ ] **Step 6: Commit**

```bash
git add public/js/giaoban/metric-builder.js
git commit -m "feat(giaoban): widget pham vi khoa va nhom Khac cho service_count"
```

---

## Task 15: Tab JSON đồng bộ hai chiều

**Files:**
- Modify: `public/js/giaoban/metric-builder.js`

**Interfaces:**
- Consumes: `#mb-json`, `#mb-json-msg` (Task 11).
- Produces: khoá nút Lưu khi JSON hỏng; dựng lại danh sách card khi rời tab JSON.

- [ ] **Step 1: Thêm xử lý tab JSON**

```js
  /** JSON hong -> vien do + khoa nut Luu. Tra ve mang metrics hoac null. */
  function docJson() {
    var raw = $('#mb-json').val();
    var $o = $('#mb-json').closest('.tab-pane');
    try {
      var a = JSON.parse(raw);
      if (!Array.isArray(a)) throw new Error('Phải là một mảng chỉ tiêu.');
      $('#mb-json').css('border-color', '');
      $('#mb-json-msg').text('').removeClass('text-red');
      $('#mb-save').prop('disabled', false);
      return a;
    } catch (e) {
      $('#mb-json').css('border-color', '#dd4b39');
      $('#mb-json-msg').text('JSON không hợp lệ: ' + e.message).addClass('text-red');
      $('#mb-save').prop('disabled', true);
      return null;
    }
  }
```

Trong `bind()`:

```js
    $(document).on('input', '#mb-json', function () { docJson(); });

    // roi tab JSON -> dung lai danh sach card tu JSON vua go
    $(document).on('shown.bs.tab', 'a[href="#mb-tab-form"]', function () {
      var a = docJson();
      if (a === null) return;          // JSON hong: giu nguyen card cu, khong nuot im lang
      st.metrics = a;
      render();
    });

    // mo modal lai thi go khoa nut Luu
    $('#mb-modal').on('show.bs.modal', function () { $('#mb-save').prop('disabled', false); });
```

- [ ] **Step 2: Chặn Lưu khi đang ở tab JSON mà JSON hỏng**

Đầu hàm `luu()`:

```js
    if ($('#mb-tab-json').hasClass('active')) {
      var a = docJson();
      if (a === null) return;
      st.metrics = a;
    }
```

- [ ] **Step 3: Kiểm tra trên trình duyệt**

1. Mở modal → tab JSON hiển thị đúng nội dung đang có, thụt lề 2 dấu cách.
2. Sang tab JSON, xoá một chỉ tiêu trong JSON, quay lại tab Form → danh sách card giảm đúng một cái.
3. Gõ JSON hỏng (xoá một dấu `]`) → textarea viền đỏ, có dòng chữ đỏ, nút "Lưu chỉ tiêu" **bị mờ không bấm được**.
4. Sửa lại đúng → viền và nút trở lại bình thường.
5. Với JSON hỏng, bấm sang tab Form → danh sách card **giữ nguyên như cũ**, không bị xoá trắng.
6. Sửa một giá trị bên tab Form (ví dụ đổi tên) → sang tab JSON thấy giá trị mới.

Điểm 5 là chỗ dễ mất dữ liệu nhất nếu làm ẩu.

- [ ] **Step 4: Commit**

```bash
git add public/js/giaoban/metric-builder.js
git commit -m "feat(giaoban): tab JSON nang cao dong bo hai chieu voi form"
```

---

## Task 16: Nạp mẫu từ DB, lưu thành mẫu, nhân bản từ khoa khác

**Files:**
- Modify: `public/js/giaoban/metric-builder.js`
- Modify: `resources/views/khth/giaoban-config.blade.php`

**Interfaces:**
- Consumes: `STATE.metric_templates` (Task 8), `STATE.configs`, route `khth.giao-ban-config-template-store` (Task 9).
- Produces: `MetricBuilder.open(cfg, onSaved, {templates, configs})` — tham số thứ ba cấp dữ liệu cho hai menu.

- [ ] **Step 1: Truyền dữ liệu vào khi mở modal**

Trong `giaoban-config.blade.php`, handler `.btn-edit-metrics`:

```js
    MetricBuilder.open(cfg, function () { loadAll(); }, {
      templates: STATE.metric_templates || [],
      configs: STATE.configs || []
    });
```

Trong module, `open(cfg, onSaved, data)`:

```js
    st.templates = (data && data.templates) || [];
    st.configs = (data && data.configs) || [];
```

và trong nhánh `taiDanhMuc(...)` gọi thêm `renderTplMenu(); renderCloneMenu();`

- [ ] **Step 2: Hai menu**

```js
  function renderTplMenu() {
    var $m = $('#mb-tpl-menu').empty();
    var co = false;
    st.templates.forEach(function (t) {
      if (t.block_type !== st.cfg.block_type) return;
      co = true;
      $m.append('<li><a href="#" class="mb-tpl" data-id="' + t.id + '">' + esc(t.name) + '</a></li>');
    });
    if (co) $m.append('<li class="divider"></li>');
    $m.append('<li><a href="#" id="mb-tpl-save"><i class="fa fa-save"></i> Lưu bộ này thành mẫu…</a></li>');
    if (!co) $m.prepend('<li class="disabled"><a href="#">(chưa có mẫu cho khối này)</a></li>');
  }

  function renderCloneMenu() {
    var $m = $('#mb-clone-menu').empty();
    var co = false;
    st.configs.forEach(function (c) {
      if (c.id === st.cfg.id || c.block_type !== st.cfg.block_type) return;
      co = true;
      $m.append('<li><a href="#" class="mb-clone" data-id="' + c.id + '">' + esc(c.display_name) + '</a></li>');
    });
    if (!co) $m.append('<li class="disabled"><a href="#">(không có khoa cùng khối)</a></li>');
  }
```

- [ ] **Step 3: Handler nạp / nhân bản / lưu mẫu**

Dùng chung một hàm hỏi thay thế hay nối thêm — nối thêm phải đổi mã trùng, nếu không validate chặn ngay:

```js
  function napBoChiTieu(ds) {
    if (!ds.length) return;
    if (st.metrics.length &&
        !confirm('Thay thế toàn bộ ' + st.metrics.length + ' chỉ tiêu hiện có?\n\n' +
                 'OK = thay thế, Cancel = nối thêm vào cuối')) {
      ds.forEach(function (m) {
        var b = JSON.parse(JSON.stringify(m));
        b.code = maDuyNhat(b.code);
        st.metrics.push(b);
      });
    } else {
      st.metrics = JSON.parse(JSON.stringify(ds));
    }
    render();
  }
```

Trong `bind()`:

```js
    $(document).on('click', '.mb-tpl', function (e) {
      e.preventDefault();
      var id = $(this).data('id'), t = null;
      st.templates.forEach(function (x) { if (x.id === id) t = x; });
      if (!t) return;
      try { napBoChiTieu(JSON.parse(t.metrics || '[]')); } catch (err) { alert('Mẫu hỏng JSON.'); }
    });

    $(document).on('click', '.mb-clone', function (e) {
      e.preventDefault();
      var id = $(this).data('id'), c = null;
      st.configs.forEach(function (x) { if (x.id === id) c = x; });
      if (!c) return;
      try { napBoChiTieu(JSON.parse(c.metrics || '[]')); } catch (err) { alert('Cấu hình nguồn hỏng JSON.'); }
    });

    $(document).on('click', '#mb-tpl-save', function (e) {
      e.preventDefault();
      var ten = prompt('Tên mẫu:', st.cfg.display_name);
      if (!ten) return;
      $.post(ROUTES.templateStore, {
        _token: CSRF, name: ten, block_type: st.cfg.block_type,
        metrics: JSON.stringify(st.metrics), sort_order: st.templates.length + 1
      }).done(function () {
        alert('Đã lưu mẫu.');
      }).fail(function (xhr) {
        var r = xhr.responseJSON || {};
        alert(r.message || 'Không lưu được mẫu.');
      });
    });
```

Thêm `templateStore: '{{ route('khth.giao-ban-config-template-store') }}'` vào `routes` trong `init()` ở blade.

- [ ] **Step 4: Kiểm tra trên trình duyệt**

1. Khoa khối điều trị → menu "Nạp mẫu" liệt kê **chỉ** mẫu khối điều trị, không thấy mẫu CĐHA.
2. Nạp mẫu "Điều trị (mặc định)" vào khoa đang trống → 8 card xuất hiện đúng thứ tự.
3. Nạp lại lần nữa, chọn **Cancel** (nối thêm) → 16 card, 8 cái sau có mã `bn_cu_2`, `bn_vao_2`… không trùng.
4. Bấm Lưu → 200, không lỗi trùng mã.
5. Menu "Nhân bản từ khoa" liệt kê các khoa **cùng khối**, không có chính nó.
6. "Lưu bộ này thành mẫu…" → nhập tên, báo "Đã lưu mẫu"; tải lại trang, mẫu mới xuất hiện trong menu.
7. Thử lưu mẫu khi đang có chỉ tiêu sai schema → hiện thông báo lỗi tiếng Việt, **không** tạo mẫu rác.

- [ ] **Step 5: Commit**

```bash
git add public/js/giaoban/metric-builder.js resources/views/khth/giaoban-config.blade.php
git commit -m "feat(giaoban): nap mau tu DB, luu thanh mau va nhan ban tu khoa khac"
```

---

## Task 17: Nút Tính thử trong modal

**Files:**
- Modify: `public/js/giaoban/metric-builder.js`

**Interfaces:**
- Consumes: route `preview` (Task 10) → `['rows' => [['code','name','value','warning']], 'ms']`.
- Produces: bảng kết quả trong `#mb-preview-box`.

- [ ] **Step 1: Thêm handler**

Khoảng thời gian mặc định: 7h hôm qua → 7h hôm nay, cho sửa bằng `prompt` (đủ dùng, không cần datepicker riêng trong modal).

```js
  var CANH_BAO = {
    no_scope: ['danger', 'Chưa có phạm vi khoa — số 0 là do cấu hình, không phải do không có dịch vụ'],
    no_dept:  ['danger', 'Cấu hình chưa gán khoa HIS nào'],
    manual:   ['info', 'Chỉ tiêu nhập tay — không có số tự động']
  };

  function haiChuSo(n) { return (n < 10 ? '0' : '') + n; }

  function mocThoiGianMacDinh() {
    var nay = new Date();
    var hn = nay.getFullYear() + '-' + haiChuSo(nay.getMonth() + 1) + '-' + haiChuSo(nay.getDate());
    var hq = new Date(nay.getTime() - 86400000);
    var hqs = hq.getFullYear() + '-' + haiChuSo(hq.getMonth() + 1) + '-' + haiChuSo(hq.getDate());
    return { from: hqs + ' 07:00:00', to: hn + ' 07:00:00' };
  }

  function tinhThu() {
    var moc = mocThoiGianMacDinh();
    var from = prompt('Tính thử từ (YYYY-MM-DD HH:MM:SS):', moc.from);
    if (!from) return;
    var to = prompt('đến (YYYY-MM-DD HH:MM:SS):', moc.to);
    if (!to) return;

    var $b = $('#mb-preview-box').show()
      .html('<div class="text-muted"><i class="fa fa-spinner fa-spin"></i> Đang tính…</div>');
    $('#mb-preview').prop('disabled', true);

    $.post(ROUTES.preview.replace('__ID__', st.cfg.id), {
      _token: CSRF,
      metrics: JSON.stringify(st.metrics),
      block_type: st.cfg.block_type,
      his_department_ids: st.cfg.his_department_ids || '[]',
      from: from, to: to
    }).done(function (res) {
      var h = '<table class="table table-condensed table-bordered" style="margin-bottom:4px">' +
              '<thead><tr><th>Chỉ tiêu</th><th style="width:100px">Giá trị</th><th>Ghi chú</th></tr></thead><tbody>';
      (res.rows || []).forEach(function (r) {
        var cb = CANH_BAO[r.warning];
        h += '<tr' + (cb && cb[0] === 'danger' ? ' class="danger"' : '') + '>' +
             '<td>' + esc(r.name) + ' <code>' + esc(r.code) + '</code></td>' +
             '<td class="text-right">' + (r.value === null ? '—' : esc(r.value)) + '</td>' +
             '<td><small>' + (cb ? esc(cb[1]) : '') + '</small></td></tr>';
      });
      h += '</tbody></table><small class="text-muted">Tính trong ' + res.ms + ' ms. ' +
           'Đây là số tính thử, chưa ghi vào báo cáo.</small>';
      $b.html(h);
    }).fail(function (xhr) {
      var r = xhr.responseJSON || {};
      $b.html('<div class="text-red">' + esc(r.message || 'Không tính thử được.') + '</div>');
      hienLoi(xhr);
    }).always(function () {
      $('#mb-preview').prop('disabled', false);
    });
  }
```

Trong `bind()`: `$(document).on('click', '#mb-preview', tinhThu);`

- [ ] **Step 2: Kiểm tra trên trình duyệt**

1. Khoa điều trị đã gán khoa HIS → bấm Tính thử, nhận hai mốc mặc định → bảng hiện giá trị số cho 8 chỉ tiêu, có dòng "Tính trong … ms".
2. Trong lúc đang chạy, nút Tính thử **mờ đi**, không bấm lại được.
3. Khoa **chưa gán khoa HIS** → mọi dòng nền đỏ, ghi chú "Cấu hình chưa gán khoa HIS nào", giá trị 0. Đây là cái bẫy mà tính thử sinh ra để lộ.
4. Khoa cận lâm sàng, chọn phạm vi "phòng / dịch vụ cụ thể" nhưng chưa chọn phòng nào, và khoa chưa gán khoa HIS → dòng đó cảnh báo `no_scope`.
5. Chỉ tiêu nhập tay → giá trị `—`, ghi chú "Chỉ tiêu nhập tay", **không phải số 0**.
6. Chỉ tiêu sai schema → hiện lỗi đỏ và card tương ứng tô đỏ.

- [ ] **Step 3: Commit**

```bash
git add public/js/giaoban/metric-builder.js
git commit -m "feat(giaoban): nut tinh thu chi tieu trong modal kem canh bao"
```

---

## Task 18: Màn giao ban render ô nhập tay theo khai báo

**Files:**
- Modify: `resources/views/khth/giaoban-index.blade.php:139-154`

**Interfaces:**
- Consumes: `res.configs[].metrics[].input` — `GiaoBanController::show()` (dòng 52-58) **đã** trả `metricList()` nên không cần sửa controller.
- Produces: ô nhập có `step`/`min`/`max`, hậu tố đơn vị, tooltip hint, viền đỏ khi bắt buộc mà trống.

- [ ] **Step 1: Sửa vòng lặp render ô**

Thay khối `cfg.metrics.forEach(...)` (dòng 139-154) bằng:

```js
    cfg.metrics.forEach(function (m) {
      var c = cellOf(res, cfg.id, m.code) || {};
      var val = c.manual_value !== null && c.manual_value !== undefined ? c.manual_value : c.auto_value;
      var edited = c.manual_value !== null && c.manual_value !== undefined;
      var inp = m.input || {};
      var laNhapTay = m.type === 'manual';

      // step theo kieu gia tri; decimal(12,2) o DB nen toi da 2 chu so le
      var step = inp.value_type === 'decimal' || inp.value_type === 'percent' ? '0.01' : '1';
      var rangBuoc = ' step="' + (laNhapTay ? step : 'any') + '"';
      if (inp.min !== undefined && inp.min !== null) rangBuoc += ' min="' + inp.min + '"';
      if (inp.max !== undefined && inp.max !== null) rangBuoc += ' max="' + inp.max + '"';
      if (inp.value_type === 'percent') rangBuoc += ' max="100"';

      var trong = val === null || val === undefined || val === '';
      var thieuBatBuoc = laNhapTay && inp.required && trong;

      var tip = edited ? 'Số HIS: ' + (c.auto_value === null ? '—' : c.auto_value)
                       : (inp.hint || '');

      var nhan = esc(m.name) + (inp.required ? ' <span class="text-red">*</span>' : '') +
                 (inp.hint ? ' <i class="fa fa-question-circle text-muted" title="' + esc(inp.hint) + '"></i>' : '');

      html += '<div class="col-md-2" style="margin-bottom:8px"><label style="font-weight:normal">' + nhan + '</label>' +
        '<div class="input-group">' +
        '<input type="number"' + rangBuoc + ' class="form-control cell-input' +
          (edited ? ' bg-warning' : '') + (thieuBatBuoc ? ' mb-thieu' : '') + '"' +
        ' data-dept="' + cfg.id + '" data-metric="' + m.code + '"' +
        (tip ? ' title="' + esc(tip) + '"' : '') +
        ' value="' + (trong ? '' : Number(val)) + '"' + (editable ? '' : ' readonly') + '>' +
        (inp.unit ? '<span class="input-group-addon">' + esc(inp.unit) + '</span>' : '') +
        (edited && editable
          ? '<span class="input-group-btn"><button class="btn btn-default btn-reset-cell" title="Trả về số tự động" data-dept="' +
            cfg.id + '" data-metric="' + m.code + '"><i class="fa fa-undo"></i></button></span>'
          : '') +
        '</div></div>';
    });
```

- [ ] **Step 2: Thêm CSS cho ô thiếu bắt buộc**

Trong `@section('css')` của blade (nếu chưa có section này thì thêm ngay sau `@section('content_header')`):

```blade
@section('css')
<style>
  .cell-input.mb-thieu { border-color: #dd4b39; background: #fff5f4; }
</style>
@stop
```

- [ ] **Step 3: Kiểm tra trên trình duyệt**

Cần một chỉ tiêu nhập tay có khai báo đầy đủ — dùng form builder tạo trước:
`{"code":"chuyen_gia","name":"Khám chuyên gia","type":"manual","input":{"unit":"lượt","hint":"Số ca chuyên gia được mời khám","value_type":"int","min":0,"max":999,"required":true}}`

Mở `khth/giao-ban`:

1. Ô "Khám chuyên gia" có dấu `*` đỏ sau tên và biểu tượng `?`; rê chuột vào `?` hiện đúng câu giải thích.
2. Bên phải ô nhập có addon ghi `lượt`.
3. Ô đang trống → **viền đỏ nền hồng nhạt**.
4. Gõ số vào → viền đỏ mất sau khi lưu và tải lại.
5. Bấm mũi tên tăng/giảm của input → nhảy từng 1 (vì `value_type` là `int`); đổi cấu hình sang `decimal` rồi tải lại → nhảy 0.01.
6. Gõ `-5` rồi rời ô → trình duyệt chặn (do `min="0"`). Đây mới là chặn phía client; phía server làm ở Task 19.
7. Các chỉ tiêu tự động (BN cũ, BN vào…) **hiển thị y như trước**, không dấu `*`, không addon.

- [ ] **Step 4: Commit**

```bash
git add resources/views/khth/giaoban-index.blade.php
git commit -m "feat(giaoban): o nhap tay hien don vi, goi y va rang buoc theo cau hinh"
```

---

## Task 19: Chặn ràng buộc phía server ở `saveCell`

Không có task này thì `min`/`max` chỉ là trang trí: gọi thẳng API vẫn ghi được số âm.

**Files:**
- Modify: `app/Models/GiaoBan/GiaoBanDeptConfig.php` (thêm `metricByCode`)
- Modify: `app/Http/Controllers/KHTH/GiaoBanController.php:153-183` (`saveCell`)
- Create: `tests/Unit/GiaoBan/ManualInputRuleTest.php`

**Interfaces:**
- Produces:
  - `GiaoBanDeptConfig::metricByCode($code)` → mảng khai báo chỉ tiêu hoặc `null`
  - `MetricSchema::kiemGiaTriNhapTay($metric, $value)` → `null` nếu hợp lệ, hoặc chuỗi thông báo lỗi tiếng Việt.

- [ ] **Step 1: Viết test (đỏ)**

```php
<?php

namespace Tests\Unit\GiaoBan;

use Tests\TestCase;
use App\Services\GiaoBan\MetricSchema;

class ManualInputRuleTest extends TestCase
{
    protected function chiTieu($input)
    {
        return ['code' => 'cg', 'name' => 'Chuyên gia', 'type' => 'manual', 'input' => $input];
    }

    /** @test */
    public function gia_tri_trong_khoang_thi_hop_le()
    {
        $this->assertNull(MetricSchema::kiemGiaTriNhapTay($this->chiTieu(['min' => 0, 'max' => 10]), 5));
    }

    /** @test */
    public function nho_hon_min_bi_chan()
    {
        $this->assertNotNull(MetricSchema::kiemGiaTriNhapTay($this->chiTieu(['min' => 0]), -1));
    }

    /** @test */
    public function lon_hon_max_bi_chan()
    {
        $this->assertNotNull(MetricSchema::kiemGiaTriNhapTay($this->chiTieu(['max' => 10]), 11));
    }

    /** @test */
    public function kieu_int_khong_nhan_so_le()
    {
        $this->assertNotNull(MetricSchema::kiemGiaTriNhapTay($this->chiTieu(['value_type' => 'int']), 1.5));
        $this->assertNull(MetricSchema::kiemGiaTriNhapTay($this->chiTieu(['value_type' => 'int']), 2));
    }

    /** @test */
    public function kieu_decimal_toi_da_2_chu_so_le_theo_cot_decimal_12_2()
    {
        $this->assertNull(MetricSchema::kiemGiaTriNhapTay($this->chiTieu(['value_type' => 'decimal']), 1.25));
        $this->assertNotNull(MetricSchema::kiemGiaTriNhapTay($this->chiTieu(['value_type' => 'decimal']), 1.234));
    }

    /** @test */
    public function kieu_percent_gioi_han_0_100()
    {
        $this->assertNotNull(MetricSchema::kiemGiaTriNhapTay($this->chiTieu(['value_type' => 'percent']), 101));
        $this->assertNotNull(MetricSchema::kiemGiaTriNhapTay($this->chiTieu(['value_type' => 'percent']), -1));
    }

    /** @test */
    public function chi_tieu_tu_dong_khong_bi_rang_buoc()
    {
        $m = ['code' => 'bn_cu', 'name' => 'BN cũ', 'type' => 'census_from'];
        $this->assertNull(MetricSchema::kiemGiaTriNhapTay($m, -999));
    }

    /** @test */
    public function gia_tri_null_la_xoa_o_nen_hop_le()
    {
        $this->assertNull(MetricSchema::kiemGiaTriNhapTay($this->chiTieu(['min' => 5]), null));
    }
}
```

- [ ] **Step 2: Chạy test, xác nhận đỏ**

Chạy: `vendor/bin/phpunit --filter ManualInputRuleTest`
Kỳ vọng: FAIL — `Call to undefined method ... ::kiemGiaTriNhapTay()`

- [ ] **Step 3: Thêm hàm kiểm vào `MetricSchema`**

```php
    /**
     * Kiem gia tri nhap tay theo khai bao input cua chi tieu.
     * Chi ap dung cho type manual; chi tieu tu dong khong rang buoc.
     * @return null|string null = hop le, nguoc lai la thong bao loi
     */
    public static function kiemGiaTriNhapTay($metric, $value)
    {
        if (!is_array($metric) || !isset($metric['type']) || $metric['type'] !== 'manual') return null;
        if ($value === null || $value === '') return null;   // xoa o
        if (!is_numeric($value)) return 'Giá trị phải là số.';

        $in = isset($metric['input']) && is_array($metric['input']) ? $metric['input'] : [];
        $v = (float) $value;
        $kieu = isset($in['value_type']) ? $in['value_type'] : 'int';

        if ($kieu === 'int' && floor($v) != $v) {
            return 'Chỉ tiêu này chỉ nhận số nguyên.';
        }
        if (in_array($kieu, ['decimal', 'percent'], true) && round($v, 2) != $v) {
            return 'Chỉ tiêu này tối đa 2 chữ số thập phân.';
        }
        if ($kieu === 'percent' && ($v < 0 || $v > 100)) {
            return 'Giá trị phần trăm phải trong khoảng 0–100.';
        }
        if (isset($in['min']) && is_numeric($in['min']) && $v < (float) $in['min']) {
            return 'Giá trị nhỏ hơn mức tối thiểu (' . $in['min'] . ').';
        }
        if (isset($in['max']) && is_numeric($in['max']) && $v > (float) $in['max']) {
            return 'Giá trị lớn hơn mức tối đa (' . $in['max'] . ').';
        }
        return null;
    }
```

- [ ] **Step 4: Thêm `metricByCode` vào model**

Trong `app/Models/GiaoBan/GiaoBanDeptConfig.php`, sau `metricList()`:

```php
    /** @return array|null khai bao mot chi tieu theo ma */
    public function metricByCode($code)
    {
        foreach ($this->metricList() as $m) {
            if (isset($m['code']) && $m['code'] === $code) return $m;
        }
        return null;
    }
```

- [ ] **Step 5: Nối vào `saveCell`**

Trong `GiaoBanController::saveCell`, sau khối kiểm quyền `canEditDept` (dòng 166-168) và **trước** `firstOrNew`:

```php
        if ($request->input('metric_code') !== 'note' && $request->filled('manual_value')) {
            $cfg = GiaoBanDeptConfig::find($request->input('dept_config_id'));
            $metric = $cfg ? $cfg->metricByCode($request->input('metric_code')) : null;
            if ($metric) {
                $loi = \App\Services\GiaoBan\MetricSchema::kiemGiaTriNhapTay($metric, $request->input('manual_value'));
                if ($loi !== null) {
                    return response()->json(['message' => $loi], 422);
                }
            }
        }
```

- [ ] **Step 6: Chạy test, xác nhận xanh và thử thật**

Chạy: `vendor/bin/phpunit --filter ManualInputRuleTest`
Kỳ vọng: PASS (8 test)

Thử thật: với chỉ tiêu nhập tay `min: 0`, gửi thẳng `POST khth/giao-ban/save-cell` với `manual_value = -5` (dùng Console: `$.post('/khth/giao-ban/save-cell', {...})`).
Kỳ vọng: HTTP 422, `message` = "Giá trị nhỏ hơn mức tối thiểu (0)."

- [ ] **Step 7: Commit**

```bash
git add app/Services/GiaoBan/MetricSchema.php app/Models/GiaoBan/GiaoBanDeptConfig.php app/Http/Controllers/KHTH/GiaoBanController.php tests/Unit/GiaoBan/ManualInputRuleTest.php
git commit -m "feat(giaoban): chan rang buoc gia tri nhap tay phia server"
```

---

## Task 20: Hàm thuần tính giá trị khởi tạo cho ô nhập tay

Tách phần quyết định ra thành hàm thuần **trước** khi đụng vào `fetchAndStore` — đường ghi số liệu thật. Đây là task quan trọng nhất về mặt an toàn dữ liệu trong cả kế hoạch.

**Files:**
- Modify: `app/Services/GiaoBan/GiaoBanReportService.php` (thêm vào phần "Phần thuần")
- Modify: `tests/Unit/GiaoBan/GiaoBanReportServiceTest.php`

**Interfaces:**
- Produces: `GiaoBanReportService::initialManualValues(array $metrics, array $daCoCode, array $prevManual)` → `array` map `metric_code => ['value' => float, 'carried' => bool]`. Chỉ trả về cho ô **chưa tồn tại**; xử lý cả `carry_over` lẫn `default`.

- [ ] **Step 1: Viết test (đỏ)**

Test thứ hai là test chống mất dữ liệu — không có nó thì `carry_over` là một quả mìn.

```php
    protected function metricsNhapTay()
    {
        return [
            ['code' => 'bn_cu', 'name' => 'BN cũ', 'type' => 'census_from'],
            ['code' => 'ke_thua', 'name' => 'Giường kế hoạch', 'type' => 'manual',
             'input' => ['carry_over' => true]],
            ['code' => 'mac_dinh', 'name' => 'Ca mổ', 'type' => 'manual',
             'input' => ['default' => 0]],
            ['code' => 'tron', 'name' => 'Ghi chú số', 'type' => 'manual'],
        ];
    }

    /** @test */
    public function ke_thua_gia_tri_ky_truoc_va_ap_gia_tri_mac_dinh()
    {
        $ra = GiaoBanReportService::initialManualValues(
            $this->metricsNhapTay(), [], ['ke_thua' => 12.0, 'tron' => 5.0]
        );

        $this->assertEquals(['value' => 12.0, 'carried' => true], $ra['ke_thua']);
        $this->assertEquals(['value' => 0.0, 'carried' => false], $ra['mac_dinh']);
        // 'tron' khong bat carry_over -> khong ke thua du ky truoc co so
        $this->assertArrayNotHasKey('tron', $ra);
        // chi tieu tu dong khong bao gio duoc gan manual
        $this->assertArrayNotHasKey('bn_cu', $ra);
    }

    /** @test */
    public function khong_dung_toi_o_da_ton_tai()
    {
        // day la test chong mat du lieu: fetchAndStore chay lai nhieu lan tren cung bao cao draft
        $ra = GiaoBanReportService::initialManualValues(
            $this->metricsNhapTay(), ['ke_thua', 'mac_dinh'], ['ke_thua' => 12.0]
        );

        $this->assertSame([], $ra);
    }

    /** @test */
    public function ky_truoc_khong_co_so_thi_khong_ke_thua()
    {
        $ra = GiaoBanReportService::initialManualValues($this->metricsNhapTay(), [], []);

        $this->assertArrayNotHasKey('ke_thua', $ra);
        $this->assertEquals(['value' => 0.0, 'carried' => false], $ra['mac_dinh']);
    }

    /** @test */
    public function carry_over_uu_tien_hon_default()
    {
        $metrics = [['code' => 'x', 'name' => 'X', 'type' => 'manual',
                     'input' => ['carry_over' => true, 'default' => 0]]];

        $ra = GiaoBanReportService::initialManualValues($metrics, [], ['x' => 7.0]);
        $this->assertEquals(['value' => 7.0, 'carried' => true], $ra['x']);
    }
```

- [ ] **Step 2: Chạy test, xác nhận đỏ**

Chạy: `vendor/bin/phpunit --filter GiaoBanReportServiceTest`
Kỳ vọng: FAIL — `Call to undefined method ... ::initialManualValues()`

- [ ] **Step 3: Viết hàm thuần**

Thêm vào phần "===== Phần thuần (unit test) =====" của `GiaoBanReportService`:

```php
    /**
     * Gia tri khoi tao cho cac o nhap tay CHUA TON TAI trong bao cao.
     * carry_over uu tien hon default. O da ton tai khong bao gio bi dung toi
     * — neu khong, chay lai fetchAndStore se ghi de so khoa vua sua tay.
     *
     * @param array $metrics    metricList() cua mot dept config
     * @param array $daCoCode   cac metric_code da co cell trong bao cao hien tai
     * @param array $prevManual map metric_code => manual_value cua bao cao lien truoc
     * @return array map metric_code => ['value' => float, 'carried' => bool]
     */
    public static function initialManualValues(array $metrics, array $daCoCode, array $prevManual)
    {
        $out = [];
        foreach ($metrics as $m) {
            if (!isset($m['type']) || $m['type'] !== 'manual') continue;
            $code = isset($m['code']) ? $m['code'] : null;
            if ($code === null || in_array($code, $daCoCode, true)) continue;

            $in = isset($m['input']) && is_array($m['input']) ? $m['input'] : [];

            if (!empty($in['carry_over']) && isset($prevManual[$code]) && $prevManual[$code] !== null) {
                $out[$code] = ['value' => (float) $prevManual[$code], 'carried' => true];
                continue;
            }
            if (isset($in['default']) && is_numeric($in['default'])) {
                $out[$code] = ['value' => (float) $in['default'], 'carried' => false];
            }
        }
        return $out;
    }
```

- [ ] **Step 4: Chạy test, xác nhận xanh**

Chạy: `vendor/bin/phpunit --filter GiaoBanReportServiceTest`
Kỳ vọng: PASS (7 test — 3 test cũ + 4 test mới)

- [ ] **Step 5: Commit**

```bash
git add app/Services/GiaoBan/GiaoBanReportService.php tests/Unit/GiaoBan/GiaoBanReportServiceTest.php
git commit -m "feat(giaoban): ham thuan tinh gia tri khoi tao o nhap tay (carry_over + default)"
```

---

## Task 21: Nối kế thừa vào `fetchAndStore` + đánh dấu trên màn giao ban

**Files:**
- Create: `database/migrations/2026_07_27_110000_add_carried_over_to_giaoban_report_cells.php`
- Modify: `app/Services/GiaoBan/GiaoBanReportService.php:97-130` (`fetchAndStore`)
- Modify: `app/Http/Controllers/KHTH/GiaoBanController.php` (`show` trả `carried_over`, `saveCell` xoá cờ)
- Modify: `resources/views/khth/giaoban-index.blade.php`

**Interfaces:**
- Consumes: `initialManualValues()` (Task 20).
- Produces: cột `giaoban_report_cells.carried_over` (boolean, mặc định `false`); `show` trả thêm `carried_over` mỗi cell.

- [ ] **Step 1: Migration thêm cột**

Không có cột này thì giá trị kế thừa trông y hệt giá trị khoa tự nhập — khoa nhìn thấy số có sẵn rồi bấm qua, và số kỳ trước lặng lẽ trở thành số kỳ này.

```php
<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddCarriedOverToGiaobanReportCells extends Migration
{
    public function up()
    {
        Schema::table('giaoban_report_cells', function (Blueprint $table) {
            // true = so ke thua tu phien truoc, khoa chua xac nhan
            $table->boolean('carried_over')->default(false);
        });
    }

    public function down()
    {
        Schema::table('giaoban_report_cells', function (Blueprint $table) {
            $table->dropColumn('carried_over');
        });
    }
}
```

Chạy: `php artisan migrate`

Thêm `'carried_over'` vào `$fillable` của `app/Models/GiaoBan/GiaoBanReportCell.php` và `'carried_over' => 'boolean'` vào `$casts` (đọc file trước, làm theo khuôn đang có).

- [ ] **Step 2: Nối vào `fetchAndStore`**

Trong `GiaoBanReportService::fetchAndStore`, **sau** vòng lặp upsert `$fresh` (dòng 102-112) và **trước** khối giường (dòng 114):

```php
        // Khoi tao o nhap tay: ke thua ky truoc / gia tri mac dinh.
        // CHI cham vao o CHUA TON TAI — fetchAndStore duoc goi lai nhieu lan tren cung bao cao draft.
        $baoCaoTruoc = GiaoBanReport::where('report_date', '<', $report->report_date)
            ->orderBy('report_date', 'desc')->first();

        foreach ($configs as $cfg) {
            $daCo = GiaoBanReportCell::where('report_id', $report->id)
                ->where('dept_config_id', $cfg->id)
                ->whereNotNull('manual_value')
                ->pluck('metric_code')->all();

            $truoc = [];
            if ($baoCaoTruoc) {
                $truoc = GiaoBanReportCell::where('report_id', $baoCaoTruoc->id)
                    ->where('dept_config_id', $cfg->id)
                    ->whereNotNull('manual_value')
                    ->pluck('manual_value', 'metric_code')->all();
            }

            foreach (self::initialManualValues($cfg->metricList(), $daCo, $truoc) as $code => $khoiTao) {
                $cell = GiaoBanReportCell::firstOrNew([
                    'report_id' => $report->id,
                    'dept_config_id' => $cfg->id,
                    'metric_code' => $code,
                ]);
                if ($cell->manual_value !== null) continue;   // chan lop hai: khong bao gio de len so co san
                $cell->manual_value = $khoiTao['value'];
                $cell->carried_over = $khoiTao['carried'];
                $cell->updated_by = $userId;
                $cell->save();
            }
        }
```

**Hai lớp chặn là cố ý:** `$daCo` lọc theo `manual_value` đã có, và kiểm tra `$cell->manual_value !== null` một lần nữa ngay trước khi ghi. Đường này chạm số liệu thật; thà thừa một dòng còn hơn mất số của khoa.

- [ ] **Step 3: `show` trả cờ, `saveCell` xoá cờ**

Trong `GiaoBanController::show` (dòng 62-67), thêm `'carried_over' => (bool) $c->carried_over,` vào mảng `$cells[]`.

Trong `saveCell`, ở nhánh ghi `manual_value` (dòng 178), thêm ngay sau:

```php
            $cell->carried_over = false;   // khoa da xac nhan -> khong con la so ke thua
```

- [ ] **Step 4: Hiển thị nhạt màu trên màn giao ban**

Trong `giaoban-index.blade.php`, ở vòng lặp render ô (Task 18), thêm:

```js
      var keThua = !!c.carried_over;
```

và sửa hai chỗ:
- class ô nhập: thêm `(keThua ? ' mb-ke-thua' : '')`
- tooltip: `var tip = keThua ? 'Kế thừa từ phiên trước, chưa xác nhận' : (edited ? ... : (inp.hint || ''));`
- và ô kế thừa vẫn tính là **chưa điền** nếu bắt buộc:
```js
      var thieuBatBuoc = laNhapTay && inp.required && (trong || keThua);
```

CSS:

```css
  .cell-input.mb-ke-thua { color: #999; font-style: italic; background: #fafafa; }
```

- [ ] **Step 5: Kiểm tra trên trình duyệt**

Chuẩn bị: một chỉ tiêu nhập tay có `"input":{"carry_over":true}` ở một khoa, và một báo cáo của ngày hôm trước đã có số nhập tay cho chỉ tiêu đó.

1. Tạo báo cáo ngày mới, bấm "Lấy số liệu" → ô đó có sẵn số của ngày hôm trước, chữ **xám nghiêng**, rê chuột hiện "Kế thừa từ phiên trước, chưa xác nhận".
2. Bấm "Lấy số liệu" **lần nữa** → số không đổi, vẫn xám.
3. Sửa ô thành số khác → lưu, chữ trở lại bình thường (hết xám).
4. Bấm "Lấy số liệu" **lần thứ ba** → **số vừa sửa còn nguyên**, không bị số ngày hôm trước ghi đè. Đây là điểm nghiệm thu quan trọng nhất của cả kế hoạch; nếu điểm này sai là mất dữ liệu thật.
5. Chỉ tiêu nhập tay có `"input":{"default":0}` mà không bật `carry_over` → ô có sẵn số 0, chữ **bình thường** (không xám, vì không phải kế thừa).
6. Chỉ tiêu nhập tay không khai gì → ô vẫn trống như trước.

- [ ] **Step 6: Chạy toàn bộ suite, đối chiếu baseline**

Chạy: `vendor/bin/phpunit`
Kỳ vọng: không có test đỏ mới so với baseline ghi ở Task 1 Step 1.

- [ ] **Step 7: Commit**

```bash
git add database/migrations/2026_07_27_110000_add_carried_over_to_giaoban_report_cells.php app/Services/GiaoBan/GiaoBanReportService.php app/Models/GiaoBan/GiaoBanReportCell.php app/Http/Controllers/KHTH/GiaoBanController.php resources/views/khth/giaoban-index.blade.php
git commit -m "feat(giaoban): ke thua so nhap tay tu phien truoc, danh dau chua xac nhan"
```

---

## Đối chiếu kế hoạch với spec

| Mục spec | Task |
|---|---|
| §3 `MetricSchema` nguồn sự thật | 2 |
| §4 Danh mục nhóm nhỏ + cache | 6 |
| §4 Danh mục nhóm lớn + tra ngược `?ids=` | 7 |
| §4 Xác minh tên cột HIS | 1 |
| §5 Modal, card, kéo thả, nút thay cột JSON | 11 |
| §5 Render field động từ schema | 12 |
| §5.1 Widget phạm vi khoa | 14 |
| §5.2 Nhóm "Khác" | 14 |
| §5.3 Tab JSON hai chiều | 15 |
| §5.4 Tách file blade partial + JS module | 11 |
| §5.5 Lỗi 422 tô đỏ đúng card | 5 (server) + 11 (UI) |
| §6 Tính thử + cờ cảnh báo | 10 (API) + 17 (UI) |
| §7 Nhân bản từ khoa khác | 16 |
| §8 Thư viện mẫu trong DB | 8 (bảng + seed) + 9 (CRUD) + 16 (UI) |
| §9.1 Hình dạng `input` | 2 (schema) + 3 (validate) + 12 (form) |
| §9.2.1 Màn giao ban render theo khai báo | 18 |
| §9.2.2 Chặn ràng buộc phía server | 19 |
| §9.2.3 `carry_over` | 20 (hàm thuần) + 21 (nối vào + UI) |
| §10 Kiểm thử | rải trong 1, 2, 3, 5, 8, 9, 10, 19, 20 |
| §12 Command quét cấu hình cũ trước khi siết | 4 |
| §13 Rủi ro: chặn cấu hình cũ | 4 |
| §13 Rủi ro: `carry_over` ghi đè | 20 Step 1 test 2, 21 Step 2 hai lớp chặn, 21 Step 5 điểm 4 |
| §13 Rủi ro: tên cột HIS đoán sai | 1 Step 2 |
| §13 Rủi ro: select2 hiện ID trần | 7 + 13 |
| §13 Rủi ro: preview treo modal | 17 Step 1 (khoá nút) |

**Ghi chú lệch so với spec:** spec dự kiến chỉ tiêu nhập tay là một task riêng ở bước 8 của thứ tự triển khai. Trong kế hoạch này, phần **form builder** cho chỉ tiêu nhập tay không cần task riêng — `renderBody` ở Task 12 đọc thẳng `MetricSchema`, nên 8 thuộc tính `input` render ra miễn phí. Ba phần lan toả (màn giao ban, chặn server, `carry_over`) vẫn tách riêng thành Task 18–21 đúng như spec.


---
