# Order-check: chuyển menu lên cấp 1 và tách quyền riêng

Ngày: 2026-07-29

## Mục tiêu

Đưa module kiểm tra sai sót y lệnh (order-check) ra khỏi vị trí lồng sâu trong
"Kế hoạch tổng hợp", thành một mục menu cấp 1 đặt ngay trên "Hồ sơ XML", và
chuyển quyền truy cập từ `administrator` sang một role riêng.

## Hiện trạng

**Menu** — order-check nằm ở tầng 3: `Kế hoạch tổng hợp` → `Kiểm tra sai sót y lệnh`
→ 3 mục con, tất cả `checkrole: administrator` (`config/adminlte.php:217-244`).
Khối `Hồ sơ XML` là mục cấp 1, `checkrole: xml-man` (`config/adminlte.php:460`).

**Route** — 15 route `khth.order-check-*` nằm trong nhóm `khth/` dùng
`checkrole:administrator` (`routes/web.php:573`).

**Cơ chế quyền** — `CheckRole` kiểm `hasRole($role) || can($role)`
(`app/Http/Middleware/CheckRole.php:26`). Laratrust hỗ trợ `a|b` nghĩa là HOẶC.

**Tài khoản** — chỉ một tài khoản (user_id 14874) giữ đồng thời `administrator`,
`xml-man` và `superadministrator`. Tài khoản còn lại (6460) chỉ có
`category-manager`. Nên việc `administrator` mất quyền vào order-check không ảnh
hưởng người dùng nào trên thực tế.

## Quyết định: dùng ROLE, không dùng permission

Menu đi qua hai bộ lọc:

- `AppServiceProvider::filterMenu` (`app/Providers/AppServiceProvider.php:58`) —
  chỉ kiểm `hasRole()`, **không** có nhánh `can()`.
- `App\Menu\Filters\CheckRoleFilter` — kiểm `hasRole() || can()`.

Cả hai cùng chạy, nên điều kiện hiệu dụng để menu hiện là `hasRole()` phải đúng.
Nếu cấp quyền bằng **permission**, route sẽ cho vào nhưng **menu vẫn ẩn**. Vì vậy
phải tạo **role**.

## Thiết kế

### 1. Role mới `order-check`

Migration theo khuôn mẫu sẵn có
(`database/migrations/2025_03_26_140737_add_category_manager_role_to_roles_table.php`):

```php
Role::create([
    'name' => 'order-check',
    'display_name' => 'Kiểm tra sai sót y lệnh',
    'description' => 'Kiểm tra sai sót y lệnh',
]);
```

Sau khi tạo, **gán role cho mọi bản ghi trong `role_user` đang trỏ tới role
`xml-man`**, giữ nguyên `user_type` của bản ghi gốc (`App\CustomUser`). Bước gán
là **bắt buộc**, không phải tuỳ chọn — lý do ở mục 4.

`down()`: xoá các bản ghi `role_user` của role này, rồi xoá role.

Migration phải chạy được nhiều lần không vỡ: kiểm tra role đã tồn tại chưa trước
khi tạo, và không chèn trùng bản ghi `role_user`.

### 2. Route

Tách 15 route order-check ra khỏi nhóm `khth/` + `checkrole:administrator`
(`routes/web.php:573`) thành nhóm riêng đặt ngay sau nhóm đó:

```php
Route::group(['prefix' => 'khth/', 'middleware' => ['checkrole:order-check']], function () {
```

Đây đúng khuôn mẫu đã có: nhóm giao ban (`routes/web.php:652`) cũng dùng prefix
`khth/` với `checkrole` riêng, nằm cạnh nhóm administrator. Nhóm mới đặt cùng
chỗ nên vẫn nằm trong nhóm ngoài `['auth', 'check.first.login']`
(`routes/web.php:58`) — không mất xác thực.

**Giữ nguyên prefix `khth/` và giữ nguyên toàn bộ tên route.** Lý do: các blade
hardcode URL, không chỉ dùng `route()`:

- `resources/views/khth/order-check-ref.blade.php:42` — `url('khth/order-check-ref-index')`
- `resources/views/khth/order-check-ref.blade.php:58` — `url('khth/order-check-ref-index')`
- `resources/views/khth/order-check-rule.blade.php:52` — `url('khth/order-check-rule-index')`
- `resources/views/khth/order-check-rule.blade.php:70` — `url('khth/order-check-rule-index')`

Đổi prefix là vỡ 4 chỗ này, mà đổi URL không nằm trong yêu cầu.

Danh sách 15 route phải giữ nguyên tên và URI:

| Tên route | Phương thức | URI |
| --- | --- | --- |
| khth.order-check-index | GET | khth/order-check-index |
| khth.order-check-summary | GET | khth/order-check-index/summary |
| khth.order-check-scan-stats | GET | khth/order-check-index/scan-stats |
| khth.order-check-fetch | GET | khth/order-check-index/fetch |
| khth.order-check-update-status | POST | khth/order-check-index/update-status |
| khth.order-check-export | GET | khth/order-check-index/export |
| khth.order-check-ref-index | GET | khth/order-check-ref-index |
| khth.order-check-ref-fetch | GET | khth/order-check-ref-index/fetch |
| khth.order-check-ref-store | POST | khth/order-check-ref-index |
| khth.order-check-ref-update | POST | khth/order-check-ref-index/{id} |
| khth.order-check-ref-destroy | DELETE | khth/order-check-ref-index/{id} |
| khth.order-check-rule-index | GET | khth/order-check-rule-index |
| khth.order-check-rule-fetch | GET | khth/order-check-rule-index/fetch |
| khth.order-check-rule-update | POST | khth/order-check-rule-index/{id} |
| khth.order-check-rule-toggle | POST | khth/order-check-rule-index/{id}/toggle |

### 3. Menu

Gỡ khối `Kiểm tra sai sót y lệnh` khỏi submenu của `Kế hoạch tổng hợp`
(`config/adminlte.php:217-244`), chuyển nguyên vẹn thành mục **cấp 1 đặt ngay
trước `Hồ sơ XML`**. Đổi cả 4 chỗ `checkrole` từ `administrator` sang
`order-check`. Giữ icon `stethoscope` và 3 mục con:

```php
[
    'text'      => 'Kiểm tra sai sót y lệnh',
    'icon'      => 'stethoscope',
    'checkrole' => 'order-check',
    'submenu'   => [
        [
            'text'      => 'Danh sách vi phạm',
            'icon'      => 'list',
            'checkrole' => 'order-check',
            'route'     => 'khth.order-check-index',
            'active'    => ['khth/order-check-index*'],
        ],
        [
            'text'      => 'Danh mục giới hạn DV',
            'icon'      => 'venus-mars',
            'checkrole' => 'order-check',
            'route'     => 'khth.order-check-ref-index',
            'active'    => ['khth/order-check-ref-index*'],
        ],
        [
            'text'      => 'Quản lý quy tắc kiểm tra',
            'icon'      => 'sliders',
            'checkrole' => 'order-check',
            'route'     => 'khth.order-check-rule-index',
            'active'    => ['khth/order-check-rule-index*'],
        ],
    ],
],
```

### 4. Cái bẫy: superadministrator

`filterMenu` cho `superadministrator` xem **toàn bộ menu không lọc**
(`app/Providers/AppServiceProvider.php:46`), nhưng middleware `CheckRole`
**không có** ngoại lệ cho superadministrator. Nên một superadmin không có role
mới sẽ **thấy menu nhưng bấm vào là 403**.

Hôm nay lỗi này không lộ ra chỉ vì tài khoản superadmin duy nhất tình cờ cũng có
role `administrator`.

Đây là lý do bước gán role trong migration là bắt buộc.

**Không sửa `CheckRole` để thêm ngoại lệ superadmin.** Đó là thay đổi hành vi bảo
mật toàn hệ thống, ảnh hưởng mọi route đang dùng `checkrole:`, vượt phạm vi yêu
cầu này.

## Kiểm thử

Hai test thuần, không cần DB, đặt trong `tests/Unit`.

**Test menu** (`tests/Unit/MenuOrderCheckTest.php`) — đọc `config('adminlte.menu')`:

1. Có đúng một mục cấp 1 có `text` là `Kiểm tra sai sót y lệnh`.
2. Chỉ số của nó trong mảng menu **nhỏ hơn** chỉ số của mục cấp 1 `Hồ sơ XML`.
3. Mục cấp 1 đó và cả 3 mục con đều có `checkrole === 'order-check'`.
4. Trong nhánh `Kế hoạch tổng hợp` **không còn** mục nào có `route` bắt đầu bằng
   `khth.order-check`.

**Test route** (`tests/Unit/RouteOrderCheckTest.php`) — duyệt `Route::getRoutes()`:

1. Cả 15 tên route trong bảng ở mục 2 đều tồn tại.
2. Mỗi route có middleware `checkrole:order-check`.
3. Không route nào trong số đó còn middleware `checkrole:administrator`.
4. URI của từng route khớp đúng bảng ở mục 2 (chặn việc vô tình đổi URL).

Cổng kiểm thử của dự án: `vendor/bin/phpunit --testsuite Unit`. Không chạy
`tests/Feature` (đang đỏ sẵn vì lý do môi trường, không liên quan thay đổi này).

## Phạm vi không làm

- Không đổi URL của bất kỳ route order-check nào.
- Không sửa `CheckRole` hay `filterMenu`.
- Không đụng tới quyền của các module khác.
- Không gộp order-check vào khối `Hồ sơ XML`.

## Việc người dùng phải làm sau khi triển khai

Chạy `php artisan migrate` trên máy chủ. Không chạy thì role chưa tồn tại và
**không ai vào được order-check**, kể cả tài khoản superadmin.

> Cập nhật 2026-08-01: nhóm route ngoài cùng nay chỉ còn `['auth']` — middleware
> `check.first.login` đã bị xoá, xem
> `docs/superpowers/specs/2026-08-01-khoi-tao-superadmin-design.md`.
