# Thiết kế: Kíp trực lãnh đạo cho báo cáo giao ban

**Ngày:** 08/07/2026
**Module:** KHTH — báo cáo giao ban.
**Mục tiêu:** Nhập & hiển thị kíp trực lãnh đạo theo ngày (chức danh trực → người trực + SĐT), phục vụ giao ban. Hiển thị ở màn nhập, màn cấu hình (danh mục chức danh) và trình chiếu. KHÔNG xuất Excel.

## 1. Quyết định đã chốt
- Cấu trúc: **danh mục chức danh trực cấu hình** (VD Trực lãnh đạo, Trực lâm sàng, Trực hành chính, Trực điều dưỡng); mỗi ngày nhập người trực + SĐT cho từng chức danh.
- Người trực: **tìm & chọn từ acs_user** (autocomplete, tái dùng endpoint `search-users`), lưu snapshot họ tên; SĐT nhập tay (tùy chọn).
- Có nút **"Sao chép kíp ngày trước"**.
- Hiển thị: màn nhập (`giao-ban`), màn cấu hình (danh mục chức danh), trình chiếu (`present`). Không Excel.
- **Quyền nhập kíp trực hàng ngày**: cả `giaoban-admin` và role khoa (bất kỳ user có permission `giaoban`). **Cấu hình danh mục chức danh**: chỉ `giaoban-admin`.

## 2. Mô hình dữ liệu (2 bảng mới, DB local)

`giaoban_duty_positions` — danh mục chức danh trực:
- `id`, `name` (VARCHAR 255), `sort_order` (int, default 0), `is_active` (bool, default true), timestamps.

`giaoban_report_duties` — kíp trực theo report:
- `id`, `report_id` (unsignedInt), `position_id` (unsignedInt), `user_id` (unsignedInt, nullable — acs_user.id), `person_name` (VARCHAR 255, nullable — snapshot họ tên), `phone` (VARCHAR 50, nullable), timestamps.
- unique `(report_id, position_id)`.

## 3. Models
- `App\Models\GiaoBan\GiaoBanDutyPosition` (bảng `giaoban_duty_positions`, fillable name/sort_order/is_active, cast is_active bool).
- `App\Models\GiaoBan\GiaoBanReportDuty` (bảng `giaoban_report_duties`, fillable report_id/position_id/user_id/person_name/phone).

## 4. Service — sao chép kíp trực (thuần, test được)
`App\Services\GiaoBan\GiaoBanDutyService`:
- `copyRows(array $prevRows): array` — hàm thuần: từ mảng kíp trực ngày trước (mỗi phần tử position_id/user_id/person_name/phone) trả mảng dòng để chèn cho report mới (bỏ id/report_id). Dùng unit test.
- Phần persistence: `saveDuty(report, positionId, userId, personName, phone)` (upsert theo report_id+position_id), `copyFromPrevious(report)` (tìm report gần nhất < report_date có duties, copy sang).

## 5. Controller & routes

### `GiaoBanController` (nhóm `checkrole:giaoban` — cả admin & khoa)
- `show()`: bổ sung vào JSON `positions` (danh mục active theo sort_order) và `duties` (kíp trực của report: position_id, user_id, person_name, phone).
- `saveDuty(Request)`: validate position_id, user_id (nullable int), person_name (nullable string), phone (nullable string ≤ 50). getOrCreateReport nếu chưa có (dùng khoảng giờ mặc định 7h hôm trước → 7h nay). Upsert `giaoban_report_duties`. Không chặn theo khoa (kíp trực dùng chung). Nếu report `final` → 422 (không sửa khi đã chốt), trừ admin? — theo cơ chế hiện có: final thì khóa; giữ nhất quán, khóa với mọi role.
- `copyDuties(Request)`: report_id → copy kíp trực từ report gần nhất trước đó. Chặn khi final.

### `GiaoBanConfigController` (đã có middleware `giaoban-admin` cho toàn controller)
- `fetch()`: trả thêm `duty_positions` (danh mục).
- `storeDutyPosition`, `updateDutyPosition` (name, sort_order, is_active).

### Routes (nhóm `khth/` `checkrole:giaoban`)
- `POST giao-ban/save-duty`, `POST giao-ban/copy-duties`.
- `POST giao-ban/cau-hinh-duty` (store), `POST giao-ban/cau-hinh-duty/{id}` (update) — controller tự kiểm giaoban-admin.

## 6. Giao diện

### Màn nhập `giaoban-index`
- Thêm khối "Kíp trực lãnh đạo": bảng theo danh mục chức danh (active) — mỗi dòng: tên chức danh | ô autocomplete người trực (search-users) | ô SĐT | (đã chọn hiển thị họ tên).
- Nút "Sao chép kíp ngày trước". Lưu từng dòng khi đổi (hoặc nút Lưu kíp).
- Ẩn/không sửa khi báo cáo `final`.
- Escape mọi giá trị (person_name, phone, position name) khi dựng DOM.

### Màn cấu hình `giaoban-config`
- Thêm khối "Danh mục chức danh trực" (chỉ admin): bảng name + sort_order + is_active + nút thêm/lưu.

### Trình chiếu `giaoban-present`
- Thêm khối/slide "KÍP TRỰC LÃNH ĐẠO": liệt kê chức danh — họ tên — SĐT (chỉ dòng có người trực). Ẩn nếu trống. Tông màu đồng bộ nền tối.

## 7. Xử lý lỗi
- position_id không tồn tại → 422. Report final → 422 khi lưu/sao chép.
- Không có ngày trước có kíp → copyDuties trả thông báo "không có kíp ngày trước".
- acs_user chọn nhưng mất tên → person_name vẫn lưu snapshot lúc chọn.

## 8. Files
- Mới: 2 migration (`giaoban_duty_positions`, `giaoban_report_duties`), 2 model, `GiaoBanDutyService`.
- Sửa: `GiaoBanController` (show + saveDuty + copyDuties), `GiaoBanConfigController` (duty positions), `routes/web.php`, `giaoban-index.blade.php`, `giaoban-config.blade.php`, `giaoban-present.blade.php`.
- Test: `GiaoBanDutyServiceTest` (copyRows thuần).

## 9. Kiểm thử
- Unit: `copyRows` bỏ id/report_id, giữ position_id/user_id/person_name/phone.
- Đối chiếu: `search-users` trả acs_user (đã có). Seed vài chức danh + 1 report có kíp, copy sang report ngày sau → khớp.
- Present render (Node) kíp trực hiển thị đúng, escape.
- 31 unit test cũ vẫn pass.
