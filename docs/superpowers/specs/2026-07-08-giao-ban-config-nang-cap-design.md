# Thiết kế: Nâng cấp cấu hình báo cáo giao ban (đa khoa, loại khối, CustomUser, CLS)

**Ngày:** 08/07/2026
**Module:** KHTH — nâng cấp cấu hình + engine tính chỉ tiêu của tính năng Báo cáo giao ban.
**Tiền đề:** Module giao ban đã có (`docs/superpowers/specs/2026-07-08-bao-cao-giao-ban-design.md`). Bản nâng cấp này thay đổi mô hình cấu hình khoa và mở rộng cách thống kê. Module chưa có dữ liệu production → đổi schema sạch.

## 1. Mục tiêu

Bốn thay đổi:
1. **Gộp nhiều khoa HIS** vào 1 khoa báo cáo (VD hệ Nội = Nội TH + Nội TM).
2. **Gán tài khoản dùng CustomUser HIS** (`acs_user`) thay vì User MySQL — đồng thời **sửa lỗi** hiện có.
3. **Phân loại khối** điều trị nội trú / khám ngoại trú / cận lâm sàng, mỗi khối tính khác nhau.
4. **Thêm chỉ tiêu cho khối cận lâm sàng** (XN, CĐHA) đếm theo dịch vụ thực hiện.

## 2. Bối cảnh CSDL đã xác minh trên `hispro_bvnn`

- `HIS_DEPARTMENT` có cờ `IS_EXAM` (khoa khám), `IS_CLINICAL` (lâm sàng), `REALITY_PATIENT_COUNT`/`THEORY_PATIENT_COUNT` (số giường thực kê/kế hoạch). CĐHA/XN nhận diện qua **dịch vụ thực hiện** (không dựa cờ vì không chuẩn tuyệt đối).
- App xác thực bằng **`App\CustomUser`** (bảng `acs_user`, connection `ACS_RS`), cột `ID, LOGINNAME, USERNAME, EMAIL, IS_ACTIVE`. `auth()->id()` = `acs_user.id`.
- **Lỗi hiện tại:** `GiaoBanConfigController@index` liệt kê `App\User` (MySQL) để gán khoa, nhưng `GiaoBanController::assignedDeptIds()` so `giaoban_user_departments.user_id` với `auth()->id()` (acs_user.id) → hai không gian id khác nhau, gán không bao giờ khớp. Bản này sửa: gán theo `acs_user.id`.
- Lượt khám: `his_service_req` join `his_execute_room` (`is_exam=1`), `service_req_type_id = config('__tech.service_req_type_kham')`, `is_main_exam=1`, lọc `intruction_time` trong kỳ (query đã dùng trong `KHTHController@ChiPhiKhamBenh`).
- Thời gian HIS = số `YYYYMMDDHHMISS`. Nội trú `tdl_treatment_type_id=3`.

## 3. Mô hình dữ liệu

### 3.1 `giaoban_dept_configs` (migration sửa cột)
- Thêm `block_type VARCHAR(20) NOT NULL DEFAULT 'dieu_tri'` — giá trị `dieu_tri` | `kham` | `can_lam_sang`.
- Thêm `his_department_ids TEXT` (JSON mảng int) — danh sách khoa HIS gộp.
- Giữ cột cũ `his_department_id` (nullable) làm fallback đọc; migration backfill: `his_department_ids = [his_department_id]` nếu cột cũ có giá trị.

### 3.2 `giaoban_user_departments`
- Giữ nguyên cấu trúc (`user_id`, `dept_config_id`). **Ngữ nghĩa `user_id` = `acs_user.id`** (CustomUser). Đã cast int từ bản trước.

### 3.3 Model `GiaoBanDeptConfig`
- `$fillable` thêm `block_type`, `his_department_ids`.
- Helper `hisDepartmentIds(): array` — decode JSON `his_department_ids`; nếu rỗng mà có `his_department_id` thì trả `[his_department_id]`; nếu không có, `[]`. Ép về int.
- Giữ `metricList()`.

## 4. Engine tính chỉ tiêu (`GiaoBanMetricService`)

`computeAll` rẽ nhánh theo `block_type` của từng config. Các builder census/movement/end/service nhận **danh sách khoa** (`IN (:ids)`) thay vì 1 dept, và (với movement) **tập loại trừ nội bộ**.

### 4.1 Khối `dieu_tri` (nội trú) — nâng cấp gộp khoa
- `census_from`/`census_to`: `COUNT(DISTINCT treatment_id)` với `dt.department_id IN (deptSet)` tại mốc thời gian; điều kiện "chưa chuyển sang khoa khác" giữ nguyên nhưng khoa khác = ngoài deptSet không cần loại (census đếm BN đang ở 1 trong các khoa của khối tại thời điểm T — đúng).
- `movement_in` (vào thẳng, `previous_id IS NULL`) / `movement_transfer_in` (chuyển đến): `dt.department_id IN (deptSet)`. Với `transfer_in`, **loại tran mà khoa nguồn (previous.department_id) cũng ∈ deptSet** (chuyển nội bộ).
- `movement_transfer_out` (chuyển đi): đếm theo khoa nguồn `p.department_id IN (deptSet)` và **loại tran mà khoa đích (nx.department_id) cũng ∈ deptSet**.
- `end_type` (ra/chuyển viện/tử vong): `last_department_id IN (deptSet)`, gộp theo `end_codes`.
- `bed_count` (giường YC): như cũ, theo danh sách `bed_ids` cấu hình.
- `service_count` (PTTT, đẻ…): filter theo `request_department_id IN (deptSet)` hoặc `execute_department_id IN (deptSet)` tuỳ cấu hình.

Bộ chỉ tiêu mặc định khối điều trị: bn_cu, bn_vao, bn_chuyen_den, bn_ra_vien, bn_chuyen_vien, bn_tu_vong, bn_chuyen_khoa, hien_co, giuong_yc (giữ như template hiện có).

### 4.2 Khối `kham` (ngoại trú) — logic mới
- Base `exam_visit` (Lượt khám): đếm `his_service_req sr` với `sr.service_req_type_id = :kham_type` (config `__tech.service_req_type_kham`=1), `sr.is_main_exam=1`, `sr.is_delete=0`, `sr.execute_department_id IN (deptSet)` (khoa khám thực hiện), `sr.intruction_time BETWEEN`.
- Chỉ tiêu con lọc bằng JOIN `his_treatment t ON t.id = sr.treatment_id` khi metric có `filter`:
  - `Vào viện` = `treatment_type_ids:[3]`; `Cấp toa/ngoại trú` = `treatment_type_ids:[2]`.
  - `Khám yêu cầu` = `patient_type_ids:[82]`; `Khám BHYT` = `patient_type_ids:[1]`.
- Đã đối chiếu K01 (id 27): tổng 834; type3=34, type2=17; BHYT=773, Yêu cầu=5.
- `Chuyên gia BV tỉnh` không có nguồn HIS → `manual`. `his_patient_type`: 1=BHYT, 42=Viện Phí, 43=KSK, 82=Yêu cầu. `his_treatment_type`: 1=Khám, 2=Ngoại trú, 3=Nội trú.

Bộ chỉ tiêu mặc định khối khám: luot_kham, vao_vien, cap_toa_ve, kham_yeu_cau, kham_bhyt, chuyen_gia (manual).

### 4.3 Khối `can_lam_sang` (XN, CĐHA) — dùng lại service_count
- Mỗi chỉ tiêu con = `service_count` với `execute_department_id IN (deptSet)` (dịch vụ do khoa CLS thực hiện) + `filter` admin chọn: `service_type_ids` (VD 2=XN, 3=CĐHA, 10=Siêu âm) và/hoặc `service_ids` (danh sách dịch vụ cụ thể cho nhóm con như Huyết học/Sinh hóa).
- Không hard-code nhóm con; admin định nghĩa qua `metrics` JSON.

Bộ chỉ tiêu mặc định khối CLS: 1 chỉ tiêu "Tổng dịch vụ" (`service_count`, execute theo khoa) làm khởi điểm; admin nhân bản/đặt tên nhóm con.

### 4.4 Builder đa khoa — chữ ký
- Các builder hiện tại nhận `$deptId` đơn sẽ đổi thành nhận `array $deptIds` (và với movement thêm `array $excludeInternalIds` = chính deptSet). Sinh `IN (:d0,:d1,...)` bằng bind riêng để an toàn oci8. Test bằng string-assertion như bản cũ.
- `computeAll`: với mỗi config lấy `$deptIds = $cfg->hisDepartmentIds()`; batch query theo từng config (không dùng lại map toàn viện được nữa vì cần loại nội bộ theo từng khối). Chấp nhận nhiều query hơn (số khoa báo cáo nhỏ, ~10–20).

## 5. Controller & endpoint

### `GiaoBanConfigController`
- `index()`: bỏ `User::all()`. Truyền danh sách khoa HIS kèm `is_exam`, `is_clinical` (gợi ý loại khối). Không nạp toàn bộ acs_user.
- `searchUsers(Request)`: `q` (string) → query `DB::connection('ACS_RS')` bảng `acs_user` `WHERE is_active=1 AND (LOWER(loginname) LIKE :q OR LOWER(username) LIKE :q)` giới hạn 20 dòng; trả `[{id, loginname, username}]`. Lỗi kết nối → `[]`.
- `store`/`update`: validate `block_type in:dieu_tri,kham,can_lam_sang`; `his_department_ids` là JSON mảng (validate parse). Lưu.
- `assignUser`: `user_id` = acs_user.id (validate integer). Ghi `giaoban_user_departments`.
- (Tùy chọn) `usersByIds(ids)`: trả tên các acs_user đã gán để hiển thị chip (tránh gọi ACS mỗi lần render toàn bộ).

### Route (group `checkrole:giaoban`, các endpoint admin tự kiểm tra `giaoban-admin`)
- Thêm `GET giao-ban/cau-hinh/search-users` → `searchUsers`.
- (Tùy chọn) `GET giao-ban/cau-hinh/users-by-ids`.

## 6. Giao diện `giaoban-config.blade.php`
- Bảng khoa báo cáo: thêm cột **Loại khối** (select), **Khoa HIS** đổi từ select đơn → **multi-select** (chọn nhiều). Nút "Nạp chỉ tiêu mặc định" theo loại khối (template JS cho 3 khối, thay `#default-metrics` cũ).
- Khối "Gán tài khoản ↔ khoa": thay dropdown user MySQL bằng **ô search autocomplete** gọi `search-users`; hiển thị chip user đã gán theo từng khoa (hoặc theo user → nhiều khoa như cũ nhưng nguồn acs_user).
- Tất cả dữ liệu HIS/user hiển thị qua `esc()` (giữ chống XSS như bản trước).

## 7. Xử lý lỗi
- `block_type` sai → 422. Khối điều trị/khám mà `his_department_ids` rỗng → 422 (CLS cho phép rỗng nếu lọc theo service_ids).
- `searchUsers`/`usersByIds` lỗi ACS_RS → trả rỗng + thông báo, không vỡ trang.
- Cân đối (`checkBalance`) chỉ áp khối `dieu_tri`; khối khác không cảnh báo lệch.

## 8. Kiểm thử
- **Unit (thuần, TDD):** builder census/movement đa khoa (SQL chứa `IN (:d0,:d1)`; movement chứa mệnh đề loại nội bộ `NOT IN`), builder `exam_visit` (chứa `is_exam`, `is_main_exam`, `:kham_type`), CLS `service_count` execute theo khoa. `GiaoBanDeptConfig::hisDepartmentIds()` (JSON→int[], fallback cột cũ). `computeAll` rẽ nhánh theo block_type (mock selectHis).
- **Đối chiếu HIS thật:** script/preview cho 3 khối — khoa gộp Nội (Nội TH id 73 + Nội TM id 54), khoa khám K01 (id 27), khoa CLS CĐHA (id 46) — trên `hispro_bvnn`, đối chiếu số hợp lý; kiểm loại trừ nội bộ bằng cách so tổng khi gộp vs cộng rời.
- **Controller:** `searchUsers` trả acs_user đúng; `assignUser` lưu acs_user.id; `store` với block_type + his_department_ids hợp lệ.
- **Không phá vỡ:** 12 unit test cũ vẫn pass (điều chỉnh test census/movement cũ theo chữ ký đa khoa nếu đổi).

## 9. Vị trí code
- Migration: `database/migrations/2026_07_08_1100xx_*` (add block_type + his_department_ids + backfill).
- `app/Models/GiaoBan/GiaoBanDeptConfig.php` (fillable + hisDepartmentIds).
- `app/Services/GiaoBan/GiaoBanMetricService.php` (builder đa khoa + exam + rẽ nhánh computeAll).
- `app/Http/Controllers/KHTH/GiaoBanConfigController.php` (searchUsers, index, store/update validate).
- `routes/web.php` (route search-users).
- `resources/views/khth/giaoban-config.blade.php` (loại khối, multi-select khoa, autocomplete user, template 3 khối).
- Tests: `tests/Unit/GiaoBan/*`.
