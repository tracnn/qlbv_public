# Spec: Bổ sung import + xem danh mục ICD (ICD-10 & ICD-YHCT)

**Date:** 2026-07-07
**Status:** Approved (chờ user review spec)

---

## 1. Bối cảnh & mục tiêu

Chức năng "import danh mục" hiện hỗ trợ **9 loại** (medicine, medical_supply, service, medical_staff, department_bed, equipment, administrative_unit, medical_organization, job_categories) qua `CatalogImportService` — **chưa có ICD**. Hai bảng đích đã tồn tại sẵn (`icd10_categories`, `icd_yhct_categories`) nhưng chưa có đường import (mới chỉ được validator dùng).

**Mục tiêu:** bổ sung import **ICD-10** và **ICD-YHCT** vào đúng khuôn mẫu sẵn có (upload Excel → tự nhận diện loại → `updateOrCreate`), kèm **trang xem** (DataTable) cho mỗi loại như các catalog khác.

**Quyết định đã chốt với user:** hỗ trợ **cả ICD-10 và ICD-YHCT**; làm **cả import lẫn trang xem**.

## 2. Kiến trúc hiện có (bám theo)

- `config/catalog_import_mapping.php`: mỗi loại có `detect_keys` (cột nhận diện), `mapping` (field → danh sách tên cột chấp nhận), `required_fields`, `unique_keys`.
- `CatalogImportService::import($filePath)`: `detectCatalogType` theo `detect_keys` → dựng field mapping → dispatch qua `methodMap` tới `importXxx` → `Model::updateOrCreate($uniqueKeys, $updateData)`. Bỏ qua dòng thiếu `required_fields`; lỗi 1 dòng thì log và `continue`.
- Upload dùng chung: KHÔNG cần sửa (tự nhận diện).
- Mỗi catalog có trang xem: `indexXxxCatalog` + `fetchXxxCatalog` (DataTable server-side) + route + Blade + menu.

## 3. Bảng đích (đã có)

**`icd10_categories`** (model `App\Models\BHYT\Icd10Category`): `icd_code` (unique, 10), `icd_name`, `is_chronic` (bool, default false), `is_active` (bool, default true), timestamps.

**`icd_yhct_categories`** (model `App\Models\BHYT\IcdYhctCategory`): `icd_code` (unique, 10), `icd_name`, `icd_yhct_name`, `icd10_code` (nullable), `icd10_name` (nullable), `is_active` (default true), timestamps.

> Khi implement: xác minh `$table` và `$fillable`/`$guarded` của 2 model (migration có điểm không nhất quán tên bảng ở `down()`), đảm bảo `updateOrCreate` ghi được các cột trên.

## 4. Thay đổi thiết kế

### 4.1. `config/catalog_import_mapping.php` — thêm 2 loại

**`icd10`:**
- `detect_keys`: `['MA_ICD10', 'MA_BENH', 'TEN_BENH']` (đủ để nhận diện file ICD-10).
- `mapping`:
  - `icd_code` ← `['MA_ICD10','MA_BENH','MA_ICD','Mã ICD','Mã bệnh','MA ICD']`
  - `icd_name` ← `['TEN_ICD10','TEN_BENH','TEN_ICD','Tên ICD','Tên bệnh','TEN ICD']`
  - `is_chronic` ← `['BENH_MAN_TINH','MAN_TINH','Mãn tính','MAN TINH']`
- `required_fields`: `['icd_code','icd_name']`
- `unique_keys`: `['icd_code']`

**`icd_yhct`:**
- `detect_keys`: `['MA_YHCT','TEN_YHCT','ICD_YHCT']` — cột đặc trưng YHCT để **phân biệt với ICD-10** (tránh nhận nhầm vì cả hai đều có icd_code/icd_name).
- `mapping`:
  - `icd_code` ← `['MA_ICD_YHCT','MA_YHCT','MA_ICD','Mã ICD YHCT','Mã YHCT']`
  - `icd_name` ← `['TEN_ICD_YHCT','TEN_YHCT','Tên ICD YHCT','Tên YHCT']`
  - `icd_yhct_name` ← `['TEN_BENH_YHCT','TEN_YHCT_BENH','Tên bệnh YHCT','ICD_YHCT']`
  - `icd10_code` ← `['MA_ICD10','MA_BENH','Mã ICD10']`
  - `icd10_name` ← `['TEN_ICD10','TEN_BENH','Tên ICD10']`
- `required_fields`: `['icd_code','icd_name','icd_yhct_name']`
- `unique_keys`: `['icd_code']`

> **Lưu ý tên cột:** đây là bộ alias đề xuất theo quy ước BHXH; mỗi field chấp nhận nhiều biến thể nên khá bền. Nếu file Excel thực tế dùng tên khác, chỉ cần bổ sung alias vào `mapping`/`detect_keys` (không đổi code). Cần đối chiếu với file mẫu khi triển khai để tránh nhận diện trượt.

### 4.2. `CatalogImportService`
- Thêm `use App\Models\BHYT\Icd10Category;` và `use App\Models\BHYT\IcdYhctCategory;`.
- Thêm vào `methodMap`: `'icd10' => 'importIcd10'`, `'icd_yhct' => 'importIcdYhct'`.
- `importIcd10($data, $fieldMapping, $config)`: theo khuôn `importService` — `$data->slice(1)`; mỗi dòng kiểm `hasRequiredFields`; gom `unique_keys`→`$uniqueKeys`, các field còn lại→`$updateData`; ép `is_chronic` về boolean (nếu có cột: giá trị "1"/"true"/"x"/"có" → true, còn lại false); `Icd10Category::updateOrCreate($uniqueKeys, $updateData)`; try/catch log lỗi + `continue`.
- `importIcdYhct(...)`: tương tự, `IcdYhctCategory::updateOrCreate($uniqueKeys, $updateData)` (không có `is_chronic`).

### 4.3. Trang xem
- `CategoryBHYTController`: `indexIcd10Catalog`/`fetchIcd10Catalog`, `indexIcdYhctCatalog`/`fetchIcdYhctCatalog` — DataTable server-side (dùng `Yajra\Datatables`, theo mẫu `fetchServiceCatalog`).
- `routes/web.php`: 2 route index + 2 route fetch (nhóm `bhyt/`, đặt cạnh các route catalog khác, cùng middleware).
- 2 Blade list (`resources/views/category/...` theo cấu trúc catalog hiện có): cột ICD-10 = STT/Mã ICD/Tên ICD/Mãn tính; ICD-YHCT = STT/Mã ICD/Tên ICD/Tên bệnh YHCT/Mã ICD10/Tên ICD10.
- `config/adminlte.php`: 2 mục menu (định dạng AdminLTE2 như các catalog khác).

## 5. Xử lý lỗi & biên

- Nhận diện sai/không nhận diện được loại: `detectCatalogType` trả null → dùng lỗi hiện có ("Không nhận diện được loại danh mục..."). ICD-YHCT phải có `detect_keys` đặc trưng để không bị nhận nhầm thành ICD-10.
- Dòng thiếu `required_fields` → bỏ qua (như các loại khác).
- Trùng `icd_code` → `updateOrCreate` cập nhật (idempotent, re-import an toàn).
- `is_chronic` khi không có cột → giữ mặc định (false cho bản ghi mới; không ghi đè nếu không có trong `updateData`).

## 6. Kiểm thử

**Unit (`tests/Unit/...`, theo phong cách test hiện có — thuần/không phụ thuộc Oracle):**
- `detectCatalogType`: header có cột ICD-10 → trả `'icd10'`; header có cột YHCT đặc trưng → trả `'icd_yhct'` (không nhầm sang icd10).
- `importIcd10`/`importIcdYhct`: với vài dòng mẫu, ghi đúng số bản ghi vào model, dedup theo `icd_code` (re-import không nhân đôi). (Cần DB test cho model — nếu hạ tầng test chưa có, dùng sqlite in-memory riêng cho case này hoặc kiểm bằng smoke; chốt cách ở plan.)

**Smoke:** import 1 file Excel ICD-10 và 1 file ICD-YHCT thật (hoặc mẫu nhỏ) → số bản ghi trong bảng khớp; trang xem hiển thị đúng.

## 7. Out of scope (YAGNI)

- Không sửa luồng upload (đã tự nhận diện).
- Không tạo bảng/model mới (đã có sẵn).
- Không đụng 9 loại catalog hiện có.
- Không làm chức năng sửa/xóa từng dòng ICD trên UI (chỉ xem + import); nếu cần sẽ tách yêu cầu riêng.
