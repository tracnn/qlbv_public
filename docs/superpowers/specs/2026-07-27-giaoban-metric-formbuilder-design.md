# Form builder cho chỉ tiêu giao ban

Ngày: 2026-07-27
Phạm vi: trang cấu hình báo cáo giao ban (`GiaoBanConfigController`) và các điểm lan toả sang màn giao ban.

## 1. Bối cảnh và vấn đề

Cột "Chỉ tiêu (JSON)" trong `resources/views/khth/giaoban-config.blade.php:154` là một `<textarea rows="3">` chứa JSON thô, kèm một select "Nạp mẫu" đọc từ 5 template nhúng cứng ở dòng 60–97 của chính blade đó.

Bốn hệ quả:

1. **Không có schema.** Cấu trúc thật của một chỉ tiêu chỉ tồn tại ngầm trong câu `switch` ở `app/Services/GiaoBan/GiaoBanMetricService.php:389`. Blade không biết, controller không biết, người cấu hình càng không.
2. **Validate hở.** `GiaoBanConfigController::store` chỉ gọi `validJson()` (dòng 108). Sai `type`, sai khoá trong `filter`, trùng `code` đều lưu thành công; `computeAll` rơi vào nhánh `default` trả `null` hoặc bỏ qua filter âm thầm → số liệu sai mà không có cảnh báo.
3. **Phải gõ ID số của HIS bằng tay.** `service_type_ids`, `diim_type_ids`, `test_type_ids`, `patient_type_ids`, `treatment_type_ids`, `execute_room_ids`, `bed_ids` — người cấu hình phải tra tay trong Oracle rồi gõ số vào JSON.
4. **Bẫy phạm vi khoa.** Với `service_count`, `computeAll:412-431` có nhánh mặc định ngầm và một guard `$hasScope` **trả 0 trong im lặng** nếu cấu hình không khớp phạm vi nào.

Mỗi loại chỉ tiêu lại có bộ field riêng: `end_codes[]`, `bed_ids[]`, `filter{...}` — và `filter` của `service_count` có 13 khoá, của `exam_visit` có 3 khoá, khác nhau hoàn toàn.

## 2. Quyết định thiết kế đã chốt

| Quyết định | Lựa chọn |
|---|---|
| Chọn ID HIS | Dropdown tra danh mục từ HIS theo tên (không gõ ID) |
| Vị trí UI | Modal riêng, mở từ nút trong bảng |
| JSON thô | Giữ làm tab "Nâng cao", đồng bộ hai chiều với form |
| Validate server | Siết đầy đủ theo schema |
| Tính năng thêm | Tính thử (preview), kéo thả sắp thứ tự, nhân bản từ khoa khác, thư viện mẫu trong DB |
| Chỉ tiêu nhập tay | Đơn vị + giải thích, kiểu dữ liệu + ràng buộc, bắt buộc nhập, mặc định + kế thừa kỳ trước |
| `carry_over` | Làm trong đợt này (không tách pha sau) |

Nguyên tắc xuyên suốt: **`GiaoBanMetricService.php` không sửa một dòng nào.** Toàn bộ việc này là lớp cấu hình phía trên, không đụng logic tính số.

## 3. `MetricSchema` — nguồn sự thật duy nhất

`app/Services/GiaoBan/MetricSchema.php` — mảng khai báo mô tả 11 loại chỉ tiêu hiện có (`census_from`, `census_to`, `movement_in`, `movement_transfer_in`, `movement_transfer_out`, `end_type`, `bed_count`, `exam_visit`, `service_count`, `admission`, `manual`).

Ví dụ hai mục tiêu biểu:

```php
'end_type' => [
    'label'  => 'Kết thúc điều trị',
    'blocks' => ['dieu_tri'],
    'fields' => [
        'end_codes' => ['widget' => 'catalog_multi', 'catalog' => 'end_type',
                        'required' => true, 'label' => 'Loại kết thúc'],
    ],
],
'service_count' => [
    'label'  => 'Đếm dịch vụ',
    'blocks' => ['can_lam_sang'],
    'scope'  => 'service_dept',        // widget phạm vi khoa, xem mục 5
    'filter' => [
        'service_type_ids' => ['catalog' => 'service_type', 'label' => 'Loại dịch vụ'],
        'diim_type_ids'    => ['catalog' => 'diim_type', 'label' => 'Loại CĐHA',
                               'other_key' => 'diim_type_other_of'],
        'test_type_ids'    => ['catalog' => 'test_type', 'label' => 'Loại xét nghiệm',
                               'other_key' => 'test_type_other_of'],
        'service_ids'      => ['catalog' => 'service', 'remote' => true],
        'execute_room_ids' => ['catalog' => 'room', 'remote' => true],
        'priority_min'     => ['widget' => 'int'],
        'priority_max'     => ['widget' => 'int'],
    ],
],
```

Ba nơi tiêu thụ chung mảng này:

| Nơi dùng | Vai trò |
|---|---|
| `MetricValidator` (PHP) | Chặn payload sai ở `store`/`update`, cả đường form lẫn đường JSON thô |
| Form builder (JS, nhận qua `@json`) | Render form động — **không hard-code field nào trong JS** |
| `MetricSchemaTest` | Đối chiếu mọi `case` trong `computeAll` đều có mặt trong registry |

Thêm loại chỉ tiêu mới về sau: sửa một chỗ, form + validate tự có.

## 4. Danh mục HIS

`app/Services/GiaoBan/GiaoBanCatalogService.php` — chỗ duy nhất biết bảng nào ánh xạ ra danh mục nào.

**Nhóm nhỏ** (vài chục bản ghi, tải trọn gói khi mở modal):

| Khoá | Bảng HIS | Nhãn |
|---|---|---|
| `service_type` | `his_service_type` | Loại dịch vụ |
| `diim_type` | `his_diim_type` | Loại CĐHA |
| `test_type` | `his_test_type` | Loại xét nghiệm |
| `patient_type` | `his_patient_type` | Đối tượng BN |
| `treatment_type` | `his_treatment_type` | Loại điều trị |
| `end_type` | `his_treatment_end_type` | Loại kết thúc — trả `code`, không phải `id` |

Endpoint gộp `GET khth/giao-ban/cau-hinh/danh-muc` trả cả 6 trong một lượt, `Cache::put('giaoban.catalog', $data, 60)` — Laravel 5.5 tính bằng **phút**. Mở modal = 1 request.

**Nhóm lớn** (hàng trăm đến hàng nghìn, không tải hết):

| Khoá | Bảng | Cách lấy |
|---|---|---|
| `service` | `his_service` | select2 AJAX, `q` ≥ 2 ký tự, `ROWNUM <= 30` |
| `room` | `his_room` + `his_department` | như trên, nhãn kèm tên khoa |
| `bed` | `his_bed` + `his_bed_room` + `his_room` | như trên, nhãn kèm phòng |

`GET khth/giao-ban/cau-hinh/danh-muc/{key}?q=...` — theo đúng khuôn `searchUsers` (`GiaoBanConfigController.php:74`): bọc `try/catch`, bind tham số, `array_change_key_case` vì Oracle trả cột HOA. Tìm theo tên tiếng Việt dùng lại `ViSearch::noDiacriticsSql` để gõ không dấu vẫn ra.

**Tra ngược:** endpoint nhóm lớn phải hỗ trợ `?ids=12345,678`. Khi mở lại cấu hình cũ có `service_ids:[12345]`, select2 remote không có sẵn nhãn trong bộ nhớ; thiếu chế độ này thì form mở lên trơ ra con số hoặc trống.

**Dữ liệu lưu không đổi:** JSON vẫn chứa ID số. Danh mục chỉ là lớp dịch ID ↔ tên ở tầng UI.

**Phân quyền:** các route nằm trong `GiaoBanConfigController`, tự chịu `giaoban-admin` từ `__construct` — không thêm vào `except`.

**Chưa xác minh:** tên cột của `his_diim_type`, `his_test_type`, `his_patient_type`, `his_treatment_type`, `his_service_type`. Quy ước HIS quan sát được ở `CategoryHISController.php:85` là `<tên_bảng>_code` / `<tên_bảng>_name`. Truy vấn kiểm chứng là **task đầu tiên** của kế hoạch.

## 5. Modal form builder

**Điểm vào:** cột "Chỉ tiêu (JSON)" rút gọn thành nút `Chỉ tiêu (8) ✎`.

Bố cục (`modal-lg`, AdminLTE sẵn có):

```
┌─ Chỉ tiêu — Khoa Nội Tổng hợp ─────────── [Điều trị (nội trú)] ─┐
│ [+ Thêm chỉ tiêu ▾] [Nạp mẫu ▾] [Nhân bản từ khoa ▾] [⚡Tính thử]│
│ ┌ Form ┬ JSON ┐                                                  │
│ ⠿ [bn_cu]        BN cũ              · BN cũ (đầu kỳ)    ▾  🗑     │
│ ⠿ [bn_ra_vien]   BN ra viện         · Kết thúc điều trị ▾  🗑     │
│   └─ Loại kết thúc: [Ra viện ×][Hết KH ×][Chuyển ×]  ← select2   │
│ ⠿ [chuyen_gia]   Khám chuyên gia    · Nhập tay          ▾  🗑     │
│                                            [Huỷ] [Lưu chỉ tiêu]  │
└──────────────────────────────────────────────────────────────────┘
```

Mỗi chỉ tiêu là một card thu gọn, kéo thả bằng jQuery UI sortable, bấm mở ra phần khai báo riêng. **Phần mở rộng do JS render từ `MetricSchema`** — không có `if (type === 'end_type')` trong code. Menu "+ Thêm chỉ tiêu" lọc theo `blocks`: khối `kham` không thấy `service_count`, khối `can_lam_sang` không thấy `census_from`.

### 5.1 Phạm vi khoa của `service_count`

Trong JSON hiện tại đây là mớ bẫy: `execute_department_id_self`, `execute_department_ids`, `request_department_ids`, `execute_department_id`, `request_department_id`, cộng nhánh mặc định ngầm và guard `$hasScope` trả 0 trong im lặng.

Form thay bằng một nhóm radio:

> Đếm dịch vụ **do khoa này thực hiện** / **do khoa này chỉ định** / **theo khoa–phòng chỉ định cụ thể →** (hiện select2)

Ba lựa chọn sinh ra đúng ba tổ hợp khoá hợp lệ. Không còn trạng thái "không phạm vi".

### 5.2 Nhóm "Khác"

`diim_type_other_of` / `test_type_other_of` nghĩa là "phần còn lại ngoài các loại đã tách" (`GiaoBanMetricService.php:161-168`). Trong form: chọn loại như bình thường, cộng một checkbox **"Là nhóm Khác — lấy phần còn lại ngoài các loại đã chọn"**. Bật lên thì ghi vào `*_other_of` thay vì `*_ids`, nhãn card đổi thành *"CĐHA khác (ngoài X-Quang, CT, MRI)"*.

### 5.3 Tab JSON

Đồng bộ hai chiều: rời tab JSON thì parse lại và dựng lại danh sách card; JSON hỏng thì viền đỏ và khoá nút Lưu, không im lặng nuốt. Là cửa thoát cho cấu hình lạ mà form chưa diễn đạt được.

### 5.4 Tổ chức file

`giaoban-config.blade.php` đang 340 dòng và sẽ phình gấp ba nếu nhồi tiếp. Tách:

- `resources/views/khth/partials/giaoban-metric-builder.blade.php` — markup modal
- `public/js/giaoban/metric-builder.js` — module `MetricBuilder` với API: `open(config)`, `getMetrics()`, `on('save')`

Blade cha chỉ còn lo bảng và việc gọi `MetricBuilder.open()`.

### 5.5 Hiển thị lỗi validate

Server trả 422 kèm `[{index: 2, field: 'end_codes', message: '...'}]`; JS bung card thứ 2 và tô đỏ đúng ô — thay cho `alert()` cụt ở `giaoban-config.blade.php:250`.

## 6. Tính thử (preview)

`POST khth/giao-ban/cau-hinh/{id}/tinh-thu`, body là **metrics đang soạn dở, chưa lưu** + `his_department_ids` + `block_type` + `from`/`to`.

Thực thi: dựng một `GiaoBanDeptConfig` **không persist** (`new`, gán thuộc tính, không `save()`), đưa vào `GiaoBanMetricService::computeAll([$tmp], $from, $to)`. Hàm này chỉ gọi `metricList()` và `hisDepartmentIds()` nên model chưa lưu chạy được ngay.

Kết quả mỗi chỉ tiêu: giá trị + cờ cảnh báo.

| Cờ | Điều kiện |
|---|---|
| `no_scope` | rơi vào guard `$hasScope` → giá trị 0 do thiếu phạm vi, không phải do không có dịch vụ |
| `no_dept` | config chưa gán khoa HIS nào |
| `manual` | chỉ tiêu nhập tay, không có số tự động (hiển thị "—", không phải 0) |

Thiếu mấy cờ này thì preview trả về một cột số 0 và người cấu hình tưởng đúng — tệ hơn không có preview.

Mặc định khoảng thời gian là phiên giao ban gần nhất, cho sửa. Nút khoá lại khi đang chạy: `service_count` với `diim_type_other_of` quét `his_sere_serv`, không nhẹ.

## 7. Nhân bản từ khoa khác

Thuần client, không cần API mới: `STATE.configs` đã có `metrics` của mọi khoa từ `fetch` (`GiaoBanConfigController.php:35`).

Dropdown lọc theo cùng `block_type`. Chọn xong hỏi **thay thế** hay **nối thêm**; nối thêm thì tự đổi `code` trùng thành `code_2`.

## 8. Thư viện mẫu trong DB

5 mẫu đang nhúng cứng ở `giaoban-config.blade.php:60-97` — sửa một dòng phải deploy. Chuyển thành bảng:

```php
Schema::create('giaoban_metric_templates', function (Blueprint $table) {
    $table->increments('id');
    $table->string('name', 255);
    $table->string('block_type', 20);
    $table->text('metrics');
    $table->integer('sort_order')->default(0);
    $table->boolean('is_active')->default(true);
    $table->timestamps();
});
```

Migration kèm seed đúng 5 mẫu hiện có, **giữ nguyên nội dung** — không nhân cơ hội sửa số liệu.

Dropdown "Nạp mẫu" đọc từ DB, lọc theo `block_type`. Thêm nút **"Lưu bộ này thành mẫu"** trong modal. Quản lý mẫu (sửa tên, ẩn) đặt ở một box nhỏ trong trang cấu hình, dùng lại khuôn CRUD của box "Danh mục chức danh trực".

Metrics của mẫu **cũng đi qua `MetricValidator`** — không có cửa sau ghi được JSON sai.

## 9. Chỉ tiêu nhập tay

### 9.1 Hình dạng dữ liệu

Gom vào một khoá con `input`, không rải phẳng:

```json
{"code":"chuyen_gia","name":"Khám chuyên gia","type":"manual",
 "input":{"unit":"lượt","hint":"Số ca do chuyên gia được mời khám trong kỳ",
          "value_type":"int","min":0,"max":999,"required":true,
          "default":0,"carry_over":false}}
```

`value_type` ∈ `int` / `decimal` / `percent`, và **chính nó quyết định số chữ số lẻ** — không có khoá `decimals` riêng để khỏi khai báo mâu thuẫn. Ràng buộc bởi cột thật: `manual_value` là `decimal(12,2)` (`2026_07_08_100002_create_giaoban_report_cells_table.php:17`) → `int` = 0 chữ số lẻ, `decimal` = tối đa 2, `percent` = tối đa 2 và giới hạn 0–100. Validator ép `min ≤ max` và `default` nằm trong khoảng; ràng buộc tự mâu thuẫn sẽ chặn cứng khoa không nhập nổi số nào.

### 9.2 Ba điểm lan toả ngoài trang cấu hình

**1. Màn giao ban — `giaoban-index.blade.php:141`.** Ô nhập hiện là input trần. Đọc `input` để render `step`/`min`/`max`, hậu tố đơn vị, `title` = hint, viền đỏ khi `required` mà còn trống. Blade cần nhận `metrics` đã decode; nếu payload của `GiaoBanController` (dòng 65) chưa trả kèm khai báo chỉ tiêu thì bổ sung vào payload, không query thêm.

**2. Chặn phía server — `GiaoBanController.php:159`.** Hiện chỉ `nullable|numeric`. Ràng buộc `min`/`max`/`value_type` phải kiểm ở đây nữa, nếu không nó chỉ là trang trí — gọi thẳng API vẫn ghi được số âm. Tra khai báo từ `dept_config` của ô đang lưu rồi đối chiếu.

**3. Kế thừa kỳ trước (`carry_over`) — `GiaoBanReportService::fetchAndStore:104`.**

`fetchAndStore` dùng `firstOrNew` rồi chỉ gán `auto_value`; `manual_value` giữ nguyên, đúng ý đồ. `computeAll` trả `null` cho chỉ tiêu `manual`, nên ô nhập tay vẫn được tạo với `manual_value` rỗng. Điểm chèn cho `carry_over` là **đúng lúc cell được tạo mới** (`!$cell->exists`): tra báo cáo liền trước cùng `dept_config_id|metric_code`, chép `manual_value` sang.

Hai điều kiện an toàn bắt buộc:

- **Chỉ chép khi cell chưa tồn tại.** `fetchAndStore` được gọi lại nhiều lần trên cùng một báo cáo draft; chép lặp sẽ ghi đè số khoa vừa sửa tay bằng số kỳ trước. Đây là lỗi mất dữ liệu.
- **Giá trị kế thừa phải phân biệt được** với giá trị khoa tự nhập: hiển thị nhạt màu + tooltip *"Kế thừa từ phiên trước, chưa xác nhận"*, và coi như chưa điền nếu chỉ tiêu `required`.

Đây là phần duy nhất trong toàn bộ thiết kế đụng vào đường ghi số liệu thật.

## 10. Kiểm thử

Thêm vào `tests/Unit/GiaoBan/` (đã có 7 file cùng chỗ), theo TDD — viết test đỏ trước.

| Test | Bảo vệ điều gì |
|---|---|
| `MetricSchemaTest` | Mọi `case` trong `computeAll` đều có trong registry và ngược lại — chống lệch ngầm giữa service và form |
| `MetricValidatorTest` | `code` trùng/sai định dạng, `type` lạ, `type` không hợp `block_type`, khoá `filter` ngoài whitelist, vừa `diim_type_ids` vừa `diim_type_other_of`, `min > max`, `default` ngoài khoảng, `value_type` lạ |
| `GiaoBanCatalogServiceTest` | Nhóm nhỏ có cache, nhóm lớn chặn `q < 2`, tra ngược `?ids=` trả đúng nhãn |
| `GiaoBanReportServiceTest` (bổ sung) | **`carry_over` chỉ chép khi cell chưa tồn tại** — gọi `fetchAndStore` hai lần, số khoa sửa tay giữa hai lần không bị ghi đè. Bắt buộc |
| `GiaoBanConfigControllerTest` (Feature) | `store`/`update` trả 422 kèm `index`+`field`; `tinh-thu` trả cờ `no_scope` |

Lưu ý hạ tầng test của dự án:

- **Chạy `phpunit` trước khi bắt đầu** để chốt danh sách test đang đỏ sẵn.
- **Không mock bằng Mockery cho method có return type** — hỏng ở phiên bản PHPUnit/Mockery này. Dùng dữ liệu thật hoặc fake object.
- `Cache::put` tính bằng **phút** (Laravel 5.5).
- Phần SQL chạm Oracle: test **chuỗi SQL và bindings** do `build*Sql` sinh ra, không cần kết nối HIS — theo khuôn `GiaoBanMetricServiceTest`.

## 11. Danh sách file

**Mới:**

```
app/Services/GiaoBan/MetricSchema.php
app/Services/GiaoBan/MetricValidator.php
app/Services/GiaoBan/GiaoBanCatalogService.php
app/Console/Commands/GiaoBanKiemTraChiTieu.php
database/migrations/..._create_giaoban_metric_templates_table.php
resources/views/khth/partials/giaoban-metric-builder.blade.php
public/js/giaoban/metric-builder.js
tests/Unit/GiaoBan/MetricSchemaTest.php
tests/Unit/GiaoBan/MetricValidatorTest.php
tests/Unit/GiaoBan/GiaoBanCatalogServiceTest.php
tests/Feature/GiaoBan/GiaoBanConfigControllerTest.php
```

**Sửa:**

| File | Thay đổi |
|---|---|
| `GiaoBanConfigController.php` | Validate siết, endpoint danh mục, tính thử, CRUD mẫu |
| `routes/web.php` | ~5 route mới |
| `giaoban-config.blade.php` | Cột JSON → nút, nhúng partial, bỏ 5 template cứng |
| `giaoban-index.blade.php` | Render ô nhập tay theo `unit`/`min`/`max`/`required` |
| `GiaoBanController.php` | `saveCell` kiểm ràng buộc phía server |
| `GiaoBanReportService.php` | `carry_over` khi tạo cell mới |

`GiaoBanMetricService.php` không sửa.

## 12. Thứ tự triển khai

1. **Xác minh tên cột 5 bảng danh mục HIS** — việc đầu tiên, tránh viết code lệch.
2. `MetricSchema` + `MetricValidator` + test — nền của mọi thứ.
3. Command `giaoban:kiem-tra-chi-tieu` quét toàn bộ `giaoban_dept_configs` hiện có, in ra bản ghi nào không đạt và sai ở đâu → **chạy trên dữ liệu thật**, sửa cấu hình sai, rồi mới bật siết validate ở controller.
4. `GiaoBanCatalogService` + endpoints.
5. Modal form builder — form trước, tab JSON sau. Phần việc lớn nhất.
6. Tính thử.
7. Bảng mẫu + nhân bản.
8. Chỉ tiêu nhập tay: schema → màn giao ban → chặn server → `carry_over`.

Bước 3 là cửa an toàn: siết validate chỉ bật sau khi biết chắc không cấu hình nào đang chạy bị chặn.

## 13. Rủi ro

| Rủi ro | Cách xử lý |
|---|---|
| Siết validate chặn cấu hình cũ đang sai | Command kiểm tra ở bước 3, chạy và dọn trước khi bật |
| `carry_over` ghi đè số khoa vừa nhập tay | Chỉ chép khi `!$cell->exists`; có test riêng bắt buộc |
| Tên cột danh mục HIS đoán sai | Xác minh bằng truy vấn ở task đầu tiên |
| select2 remote mở lại cấu hình cũ hiện ID trần | Endpoint nhóm lớn hỗ trợ `?ids=` tra ngược |
| Preview chạy nặng làm treo modal | Khoá nút khi đang chạy, giới hạn khoảng thời gian |
