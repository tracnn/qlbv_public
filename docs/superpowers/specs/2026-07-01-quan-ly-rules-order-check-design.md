# Thiết kế: Trang quản lý quy tắc kiểm tra y lệnh (order_check_rules)

- **Ngày:** 2026-07-01
- **Module:** Kiểm tra sai sót y lệnh (order-check)
- **Mục tiêu:** Giao diện cho admin quản lý các quy tắc trong `order_check_rules` (bật/tắt, đổi mức độ, sửa tên) thay vì phải chỉnh bằng SQL.

## 1. Bối cảnh & ràng buộc

- Bảng `order_check_rules` (đã có): `code, family, rule_type, name, severity, params, scope, is_active, timestamps`. Model `App\Models\OrderCheck\OrderCheckRule` đã có.
- Hiện chỉ chỉnh được bằng SQL trực tiếp.
- **Ràng buộc:** mỗi rule gắn với `rule_type` = một class handler/scanner trong source. Vì vậy **KHÔNG cho tạo/xóa rule qua UI** (rule mới không có handler sẽ vô tác dụng; xóa rule đang có handler gây lệch code↔data).
- **Phạm vi thao tác (đã chốt):** chỉ **bật/tắt `is_active`, sửa `severity`, sửa `name`**.

## 2. Kiến trúc (bám pattern trang "Danh mục giới hạn DV" — `OrderCheckRefController`)

- **Controller:** `app/Http/Controllers/KHTH/OrderCheckRuleController.php` (dùng model `OrderCheckRule`, không migration/model mới).
- **View:** `resources/views/khth/order-check-rule.blade.php` (DataTables server-side + form sửa).
- **Route** (nhóm `prefix 'khth/'`, `checkrole:administrator`):
  - `GET order-check-rule-index` → `index` (`khth.order-check-rule-index`)
  - `GET order-check-rule-index/fetch` → `fetch` (`khth.order-check-rule-fetch`)
  - `POST order-check-rule-index/{id}` → `update` (`khth.order-check-rule-update`)
  - `POST order-check-rule-index/{id}/toggle` → `toggle` (`khth.order-check-rule-toggle`)
- **Menu:** `config/adminlte.php`, mục "Quản lý quy tắc" cạnh 2 mục order-check hiện có (checkrole administrator).

## 3. Controller

```php
class OrderCheckRuleController extends Controller
{
    const SEVERITIES = ['info', 'warning', 'critical'];

    public function index();     // trả view
    public function fetch();      // Datatables::of(OrderCheckRule::query()->orderBy('family')->orderBy('code'))
                                  //   addColumn: severity_badge, active_text, actions; rawColumns
    public function update(Request $request, $id);  // validate name required, severity in SEVERITIES; cập nhật name+severity+is_active
    public function toggle(Request $request, $id);  // đảo is_active; trả json
}
```
- `code`, `rule_type`, `family` **không được sửa** (server chỉ nhận `name`, `severity`, `is_active`).
- `update` validate: `name` `required|string|max:255`; `severity` `required|in:info,warning,critical`; `is_active` boolean.

## 4. View (bảng + thao tác)

Cột DataTable: `family` | `code` | `rule_type` | Tên (`name`) | Mức độ (badge) | Trạng thái (Bật/Tắt) | Cập nhật (`updated_at`) | Thao tác.

Thao tác mỗi dòng:
- **Bật/Tắt** (nút) → POST `toggle` → reload.
- **Sửa** (nút) → đổ `id, code(readonly), name, severity, is_active` vào form; Lưu → POST `update` → reload.

Form sửa (trên bảng): `code` (readonly, để nhận diện) + `name` (text) + `severity` (select 3 mức) + `is_active` (checkbox) + nút Lưu.

## 5. Test

- Unit `tests/Unit/OrderCheck/OrderCheckRuleSeverityTest.php`: kiểm helper whitelist severity của controller (hoặc hằng `SEVERITIES`) — hợp lệ/không hợp lệ. (Test thuần, không DB.)
- Verify: `php artisan route:list | grep order-check-rule` (4 route); toggle/update qua HTTP hoặc tinker (đổi is_active/severity rồi đọc lại).

## 6. Ngoài phạm vi (YAGNI)

- Không tạo/xóa rule.
- Không sửa `params`/`scope` (chưa có handler đọc params; để sau).
- Không hiển thị số vi phạm mỗi rule (có thể bổ sung sau nếu cần).
- Không migration/model mới.
