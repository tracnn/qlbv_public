# Thiết kế: Nâng cấp kíp trực (his_employee + nhiều người + phân quyền theo user)

**Ngày:** 09/07/2026
**Module:** KHTH — báo cáo giao ban, tính năng kíp trực lãnh đạo (branch `feature/giao-ban-kip-truc`, CHƯA merge).
**Ba thay đổi:** (1) tìm người trực từ `his_employee` thay `acs_user`; (2) cho phép nhiều người/chức danh; (3) phân quyền cập nhật kíp trực theo user (giống gán khoa cho số liệu). Gom vào migration kíp trực hiện có (chưa deploy).

## 1. Bối cảnh đã xác minh (hispro_bvnn)
- `his_employee`: `ID, IS_ACTIVE, IS_DELETE, LOGINNAME, TDL_USERNAME` (họ tên), `TDL_MOBILE` (SĐT), `TITLE`, `EMPLOYEE_CODE`. Tìm `LOWER(tdl_username) LIKE` hoặc `LOWER(employee_code) LIKE` (VD 'an' → 159 kết quả). Có SĐT → tự điền.
- Phân quyền số liệu hiện có: `GiaoBanPermission::canEditDept($isAdmin, $assignedDeptIds, $deptId)`; gán qua `giaoban_user_departments` (user_id = acs_user.id = auth()->id()). Kíp trực sẽ mô phỏng cơ chế này.
- Endpoint `khth.giao-ban-config-search-users` (acs_user) đã có, đã miễn trừ admin-middleware → dùng cho gán editor.

## 2. Mô hình dữ liệu (gom vào migration kíp trực, reset trên DB dev)

Sửa `2026_07_09_100001_create_giaoban_report_duties_table` (đang có `user_id` + unique(report_id,position_id)):
- Đổi `user_id` → `employee_id` (unsignedInt, nullable — his_employee.id).
- **Bỏ** unique `(report_id, position_id)` (cho nhiều người/chức danh). Giữ `index(report_id)`, thêm `index(['report_id','position_id'])`.
- Cột: `report_id, position_id, employee_id, person_name, phone`, timestamps.

Thêm `2026_07_09_100002_create_giaoban_duty_editors_table`:
- `id`, `user_id` (unsignedInt, unique — acs_user.id), timestamps.

Reset DB dev: drop `giaoban_report_duties`, xóa bản ghi migration `...100001`/`...100002`, sửa file, `php artisan migrate`.

## 3. Models
- `GiaoBanReportDuty`: fillable đổi `user_id`→`employee_id`; cast `employee_id` int.
- Mới `GiaoBanDutyEditor` (bảng `giaoban_duty_editors`, fillable `user_id`, cast int).

## 4. Service `GiaoBanDutyService`
- `copyRows`: đổi khóa `user_id`→`employee_id` (giữ logic bỏ id/report_id).
- Thuần (TDD) `canEdit($isAdmin, array $editorUserIds, $userId): bool` = `$isAdmin || in_array((int)$userId, editorIds)`.
- `addDuty(report, positionId, employeeId, personName, phone)`: chèn 1 dòng; chặn trùng (report+position+employee đã có thì bỏ qua/không nhân đôi). Trả model.
- `removeDuty($dutyId)`: xóa 1 dòng.
- `updatePhone($dutyId, $phone)`: cập nhật SĐT.
- `copyFromPrevious(report)`: giữ (đã copy mọi dòng → nhiều người OK).
- `editorUserIds(): array` (persistence) — danh sách user_id được cập nhật.

## 5. Controller & routes

### `GiaoBanController` (nhóm `checkrole:giaoban`)
- `canEditDuty()`: `GiaoBanDutyService::canEdit($this->isAdmin(), GiaoBanDutyEditor::pluck('user_id')->all(), auth()->id())`.
- `show()`: đổi duties trả `employee_id` (thay user_id); nhiều dòng/chức danh; thêm `can_edit_duty`.
- `searchEmployees(Request $q)`: HISPro `his_employee` LIKE, trả `[{id,name,phone,title}]` ≤20. (Chỉ trả khi `q` ≥2 ký tự.)
- `addDuty`: kiểm `canEditDuty` (403 nếu không), final→422, getOrCreateReport, `addDuty()`. Validate position_id, employee_id nullable int, person_name, phone.
- `removeDuty`: kiểm quyền + final (qua report của duty) → `removeDuty()`.
- `updateDutyPhone`: kiểm quyền + final → `updatePhone()`.
- `copyDuties`: kiểm quyền (đã có final).
- Routes: `POST giao-ban/add-duty`, `POST giao-ban/remove-duty`, `POST giao-ban/update-duty-phone`, `GET giao-ban/search-employees` (thay `save-duty`). Giữ `copy-duties`.

### `GiaoBanConfigController` (admin-only)
- `fetch()`: thêm `duty_editors` (mảng {user_id}) + tên (từ acs_user).
- `assignDutyEditors(Request)`: nhận `user_ids[]` → ghi lại toàn bộ `giaoban_duty_editors`.
- Route: `POST giao-ban/cau-hinh-duty-editors`.

## 6. Giao diện

### Màn nhập `giaoban-index`
- Mỗi chức danh: danh sách chip người trực (tên — ô SĐT sửa được — nút ✕), + ô autocomplete "thêm người" (gọi `search-employees`, chọn → điền tên + SĐT từ HIS, POST add-duty). ✕ → remove-duty. SĐT blur → update-duty-phone.
- Chỉ hiện ô thêm/nút ✕/sửa SĐT khi `can_edit_duty` và report chưa final; ngược lại chỉ xem.
- Escape mọi giá trị.

### Màn cấu hình `giaoban-config`
- Khối "Người được cập nhật kíp trực": autocomplete acs_user (search-users) thêm user vào danh sách chip + ✕ xóa; nút lưu (assignDutyEditors). Admin-only.

### Trình chiếu `giaoban-present`
- Mỗi chức danh liệt kê nhiều người: "Chức danh: A (sđt), B (sđt)".

## 7. Xử lý lỗi
- Không quyền → 403. Final → 422. search-employees lỗi HISPro → []. Trùng người trong chức danh → bỏ qua (không nhân đôi).

## 8. Files
- Sửa migration `...100001`, thêm `...100002`; models (`GiaoBanReportDuty`, `GiaoBanDutyEditor`); `GiaoBanDutyService`; `GiaoBanController` (+routes); `GiaoBanConfigController` (+routes); views `giaoban-index`, `giaoban-config`, `giaoban-present`; test `GiaoBanDutyServiceTest` (copyRows employee_id + canEdit).

## 9. Kiểm thử
- Unit: `copyRows` (employee_id), `canEdit` (admin/editor/none).
- Runtime: search-employees trả his_employee; add 2 người vào 1 chức danh → show 2 dòng; copyFromPrevious nhiều người; canEditDuty chặn user không thuộc editor.
- Present render nhiều người + escape.
- Test cũ (33) vẫn pass (điều chỉnh test copyRows theo employee_id).
