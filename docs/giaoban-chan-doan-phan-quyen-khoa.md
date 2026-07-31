# Chẩn đoán: "Đã phân quyền khoa nhưng đăng nhập vẫn thấy tất cả khoa"

## Hành vi đúng

`GiaoBanController::show()` lọc **ở server** trước khi trả JSON: `GiaoBanPermission::visibleDeptConfigIds()`
quyết định nội dung `configs`, `cells` và `bang_dieu_tri`. Tài khoản chỉ có role `giaoban_khoa`
lẽ ra chỉ nhận về đúng các khoa được gán trong `giaoban_user_departments`.

Nếu thực tế khác, chạy các bước dưới đây trước khi sửa code.

## Bước 1 — Đọc dòng trạng thái trên màn giao ban

Ngay dưới thanh công cụ có dòng `Chế độ: ...`.

- `Chế độ: Quản trị — xem toàn viện` → tài khoản đang có quyền `giaoban-admin`. Sang Bước 2.
- `Chế độ: Khoa — được phân công N khoa: ...` mà màn hình vẫn hiện nhiều khoa hơn N → lỗi thật,
  sang Bước 4.

## Bước 2 — Kiểm tra role và permission của tài khoản

```sql
SELECT u.id, u.loginname, r.name role_name, p.name perm_name
  FROM acs_user u
  LEFT JOIN role_user ru       ON ru.user_id = u.id
  LEFT JOIN roles r            ON r.id = ru.role_id
  LEFT JOIN permission_role pr ON pr.role_id = r.id
  LEFT JOIN permissions p      ON p.id = pr.permission_id
 WHERE LOWER(u.loginname) = LOWER(:loginname);
```

Có dòng nào `perm_name = 'giaoban-admin'` → đây là nguyên nhân. Xử lý bằng cách gỡ role tương ứng
(thường là `administrator`, được migration `2026_07_08_100004_seed_giaoban_permissions` gán full
quyền giao ban), **không sửa code**.

## Bước 3 — Kiểm tra khoa đã phân công

```sql
SELECT ud.user_id, ud.dept_config_id, dc.display_name, dc.is_active
  FROM giaoban_user_departments ud
  JOIN giaoban_dept_configs dc ON dc.id = ud.dept_config_id
 WHERE ud.user_id = :user_id;
```

`is_active = 0` nghĩa là khoa đã tắt — người dùng được gán nhưng không nhìn thấy, màn hình sẽ ra
callout "Bạn chưa được phân công khoa nào". Đây là hành vi có chủ đích.

## Bước 4 — Đối chiếu bản triển khai

Việc lọc ở server là một lần sửa về sau; bản cũ chỉ ẩn bằng CSS, khớp đúng mô tả
"invisible không có tác dụng". Trên máy chủ:

```bash
git log --oneline -1
```

So với `main`. Nếu là bản cũ thì triển khai lại là hết.

## Bước 5 — Xóa cache quyền

Laratrust cache role/permission. Nếu vừa đổi phân quyền mà chưa thấy tác dụng:

```bash
php artisan cache:clear
```
