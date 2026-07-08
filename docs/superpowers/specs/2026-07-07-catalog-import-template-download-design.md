# Spec: Nút "Tải biểu mẫu" cho import danh mục (.xlsx sinh từ config)

**Date:** 2026-07-07
**Status:** Approved (chờ user review spec)

---

## 1. Mục tiêu

Tại màn hình import (`/category/bhyt/category-bhyt-import-index`, view `category.bhyt.import`), bổ sung **dropdown chọn loại danh mục + nút "Tải biểu mẫu"** để tải file `.xlsx` mẫu (đúng cột) cho loại đó; người dùng điền dữ liệu rồi upload import.

**Quyết định đã chốt với user:**
- Phát hành: **chọn loại rồi tải, 1 file/loại** (không phải 1 workbook nhiều sheet).
- Nội dung mẫu: **dòng header + đánh dấu cột bắt buộc**.
- Header đánh dấu bắt buộc bằng **tô màu/in đậm ô**, KHÔNG đổi chữ header (giữ import nhận diện được).
- Chữ header = **alias đầu tiên** của mỗi field trong `mapping` (xác định, nằm trong danh sách nhận diện).

## 2. Bối cảnh (đã khảo sát)

- Import: `CatalogImportService::import($file)` đọc `Excel::toCollection`, lấy **dòng đầu = header**, `detectCatalogType(header, configs)` (khớp `detect_keys`, ngưỡng `max(2, ceil(count*0.6))`, chọn loại khớp nhiều nhất), rồi `createFieldMapping` + `importXxx`.
- Config `config/catalog_import_mapping.php`: mỗi loại có `detect_keys`, `mapping` (field → danh sách alias), `required_fields`, `unique_keys`. Có ~11 loại (gồm ICD-10/ICD-YHCT vừa thêm).
- View `category.bhyt.import` hiện chỉ có Dropzone upload (không có tải mẫu).

## 3. Kiến trúc (Hướng A — sinh động từ config)

Sinh biểu mẫu tại chỗ từ config (không dùng file tĩnh) để luôn khớp cấu hình.

### 3.1. Export class — `App\Exports\CatalogTemplateExport`
- Dùng Maatwebsite Excel (đã có trong dự án).
- Constructor nhận `$type`; đọc `config("catalog_import_mapping.$type")`.
- **Header (dòng 1):** mỗi field trong `mapping` → 1 cột; chữ = phần tử **đầu tiên** của danh sách alias field đó. Thứ tự cột = thứ tự field trong `mapping`.
- **Không có dòng dữ liệu** (để trống cho người dùng điền).
- **Tô cột bắt buộc:** field thuộc `required_fields` → ô header in đậm + nền màu (vd `FFF2CC` vàng nhạt); field khác → header thường. (Implement qua `WithStyles`/`WithEvents` của Maatwebsite + PhpSpreadsheet; **không** thêm ký tự vào chữ header.)
- Cung cấp method thuần `headers(): array` trả mảng chữ header (để test tự-nhận-diện dùng lại — mục 3.4).

### 3.2. Controller + route
- `CategoryBHYTController::downloadTemplate(Request $request)`:
  - `$type = $request->get('type')`.
  - Validate `$type` ∈ `array_keys(config('catalog_import_mapping'))`; không hợp lệ → `abort(404)` (hoặc redirect kèm message lỗi).
  - `return Excel::download(new CatalogTemplateExport($type), $type.'_bieu_mau.xlsx');`
- Route GET `bhyt/category-bhyt-import-template` → `@downloadTemplate`, name `category-bhyt.import-template`, đặt cạnh route `category-bhyt.import-index` (cùng nhóm/middleware).

### 3.3. UI trên `resources/views/category/bhyt/import.blade.php`
- Thêm 1 panel/khối phía TRÊN Dropzone:
  - `<select id="template_type">` liệt kê các loại với **label tiếng Việt** + `value` = khóa config:
    `medicine`=Thuốc, `medical_supply`=Vật tư y tế, `service`=Dịch vụ kỹ thuật, `medical_staff`=Nhân viên y tế, `department_bed`=Khoa/Phòng/Giường, `equipment`=Trang thiết bị, `administrative_unit`=Đơn vị hành chính, `medical_organization`=Cơ sở y tế, `job_categories`=Nghề nghiệp, `icd10`=ICD-10, `icd_yhct`=ICD-YHCT.
  - Nút "Tải biểu mẫu".
- JS: bấm nút → `window.location.href = "{{ route('category-bhyt.import-template') }}?type=" + encodeURIComponent($('#template_type').val());`
- (Danh sách label giữ trong blade; nếu sau này thêm loại vào config, chỉ cần thêm 1 `<option>`.)

### 3.4. Đảm bảo chất lượng — biểu mẫu phải TỰ nhận diện đúng loại
Người dùng tải mẫu → điền → upload; lúc upload, `detectCatalogType` phải trả đúng loại đó, nếu không import báo "không xác định được loại".

**Ràng buộc:** `detect_keys` một số loại chứa nhiều alias của **cùng một field** (vd `service`: `MA_DICH_VU` & `MA_TUONG_DUONG` đều là alias của `ma_dich_vu`; ba `TEN_*` đều của `ten_dich_vu`). Biểu mẫu 1-cột-mỗi-field chỉ khớp được **1 detect_key per field** → có thể không đạt ngưỡng 60%.

**Giải pháp:**
- **Test bắt buộc:** với MỌI loại, `detectCatalogType(CatalogTemplateExport($type)->headers(), config(...)) === $type`.
- Loại nào trượt → **chỉnh `detect_keys` của loại đó về tập cột thuộc các FIELD PHÂN BIỆT** mà (a) biểu mẫu (1 cột/field) khớp đủ, và (b) file thật vẫn có. Ví dụ `service`: đổi `detect_keys` sang `['MA_DICH_VU','TEN_DICH_VU','DON_GIA']` (3 field khác nhau, đều là alias đầu → biểu mẫu có đủ; file thật cũng có mã DV/tên/đơn giá).
- **An toàn:** các alias bị bỏ khỏi `detect_keys` VẪN nằm trong `mapping` → file thật dùng tên cột đó vẫn map đúng; chỉ thay đổi cách NHẬN DIỆN (cần vài cột phân biệt hiện diện — file thật đều có). Danh sách loại cần chỉnh sẽ xác định khi chạy test ở bước triển khai (dự kiến: `service`; kiểm các loại khác).

## 4. Xử lý lỗi & biên

- `type` thiếu/không hợp lệ → `abort(404)`.
- Loại không có `mapping` (không xảy ra với config hiện tại) → export rỗng header; đã chặn bằng validate `array_keys`.
- Không đụng luồng upload/import hiện có; không đổi alias trong `mapping` (chỉ có thể tinh chỉnh `detect_keys` cho mục 3.4).

## 5. Kiểm thử

**Unit (không cần Oracle):**
- `CatalogTemplateExport::headers($type)` trả đúng số cột = số field trong `mapping`, phần tử = alias đầu mỗi field, đúng thứ tự.
- **Tự-nhận-diện:** vòng qua TẤT CẢ loại trong config → `detectCatalogType(headers, config) === type`. (Đây là test chốt chặn; ép chỉnh `detect_keys` nơi trượt.)
- (Tô màu cột bắt buộc: kiểm ở mức smoke — mở file tải ra thấy header + màu; không unit-test styling.)

**Smoke:** tải 1 biểu mẫu (vd `service`, `icd10`) → mở bằng PhpSpreadsheet đọc dòng 1 = đúng header; nạp ngược qua `CatalogImportService` (chỉ header, 0 dòng dữ liệu) không lỗi nhận diện.

## 6. Out of scope (YAGNI)

- Không kèm dòng dữ liệu ví dụ; không data-validation/dropdown trong Excel.
- Không làm 1 workbook nhiều sheet.
- Không đụng logic upload/import hay `mapping` (ngoài tinh chỉnh `detect_keys` tối thiểu ở 3.4).
- Không xử lý vấn đề "file thật có dòng tiêu đề phía trên header" (việc riêng) — biểu mẫu này header ở dòng 1.
