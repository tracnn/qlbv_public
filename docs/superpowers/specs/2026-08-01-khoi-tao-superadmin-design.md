# Khởi tạo quản trị viên đầu tiên: thay `CheckFirstLogin` bằng màn khởi tạo có chủ đích

Ngày: 2026-08-01

## Mục tiêu

Thay cơ chế "ai đăng nhập đầu tiên thì tự thành superadministrator" — hiện cài
trong middleware chạy trên mọi route — bằng một bước khởi tạo có chủ đích, thực
hiện qua trình duyệt, phục vụ việc triển khai sản phẩm ở các bệnh viện khác.

## Hiện trạng

**Middleware** — `app/Http/Middleware/CheckFirstLogin.php` được đăng ký ở
`app/Http/Kernel.php:65` và gắn vào nhóm route ngoài cùng
`['auth', 'check.first.login']` tại `routes/web.php:58`, bao trùm từ dòng 58 đến
847. Nghĩa là nó chạy trên gần như mọi request đã đăng nhập.

Nội dung của nó: tìm vai trò `superadministrator`; nếu chưa có ai trong
`role_user` mang vai trò đó thì gán cho người đang đăng nhập.

**Người dùng đến từ HIS, không do ứng dụng tạo** — `App\CustomUser`
(`app/CustomUser.php:16-17`) ánh xạ vào bảng `acs_user` trên kết nối Oracle
`ACS_RS`. Mọi nhân viên có tài khoản HIS đều đăng nhập được vào qlbv. Vì vậy
"người đầu tiên đăng nhập" không phải người cài đặt hệ thống, mà là bất kỳ ai
mở ứng dụng sớm nhất — điều dưỡng, kế toán, ai cũng được.

Đường tự đăng ký đã khoá đúng: `RegisterController::__construct`
(`app/Http/Controllers/Auth/RegisterController.php:38`) yêu cầu
`role:superadministrator`. Middleware này là lối vào duy nhất còn hở.

### Bốn khiếm khuyết trong đoạn mã hiện tại

1. **Đọc thuộc tính trên `null`.** Dòng 26 `Role::where('name', 'superadministrator')->first()`
   trả `null` khi bảng `roles` chưa có bản ghi đó; dòng 29 đọc ngay `$role->id`.
   Trong môi trường test (có `convertNoticesToExceptions`) điều này thành
   exception, khiến 210 route báo 500 giả. Khi chạy thật trên PHP 7.4/8.x nó chỉ
   là cảnh báo câm, `$role->id` thành `null`, rồi khối dòng 35 ghi vào `role_user`
   một bản ghi `role_id = null` **trên mỗi request của mỗi người đăng nhập**.

2. **Vai trò không do migration tạo.** `2017_10_23_052501_laratrust_setup_tables.php`
   chỉ tạo bảng, không chèn dữ liệu. Vai trò `superadministrator` sinh ra từ
   `laratrust:seeder` đọc `config/laratrust_seeder.php:5`. Trên một bản cài mới
   chưa chạy seeder, bảng `roles` rỗng — đúng kịch bản gây lỗi ở mục 1. Đây
   không phải giả thuyết: nó sẽ xảy ra ở bệnh viện tiếp theo.

3. **Không xoá cache của Laratrust.** Laratrust 5.0 cache vai trò theo từng
   người dùng. Mã hiện tại ghi thẳng bằng `RoleUser::updateOrCreate()` nên không
   gọi `flushCache()`; người vừa được cấp quyền có thể vẫn không thấy menu cho
   tới khi cache hết hạn.

4. **`user_type` lệch nguồn.** Dòng 30 ghi cứng chuỗi `'App\CustomUser'` khi
   đọc, còn dòng 38 lấy từ `config('auth.providers.users.model')` khi ghi. Hôm
   nay hai giá trị trùng nhau (`config/auth.php:70`), nên lỗi ngủ yên; đổi
   provider là hai bên lệch ngay.

**Chi phí thường trực** — trên production hiện tại, `role_user` đã có
superadministrator nên nhánh gán không bao giờ chạy nữa. Nhưng hai truy vấn vẫn
được phát trên mỗi request, vĩnh viễn, cho một khối mã đã chết.

## Bối cảnh quyết định

- Sản phẩm sẽ được triển khai cho các bệnh viện khác; mỗi nơi cần một bước khởi
  tạo quản trị viên đầu tiên. Không thể bỏ hẳn cơ chế này.
- Người thực hiện bước đó là IT của bệnh viện, **chỉ dùng trình duyệt**, không
  chạy được `php artisan`. Mọi phương án đòi CLI đều bị loại.

## Phương án đã chọn

Chọn **màn khởi tạo trên web, không dùng mã cài đặt**: cổng mở khi và chỉ khi hệ
thống chưa có superadministrator nào, người đã đăng nhập tự xác nhận để nhận
quyền, sau đó cổng đóng vĩnh viễn.

Hai phương án khác đã cân nhắc và loại:

- **Chỉ định trước trong `.env`** (`SETUP_SUPERADMIN_LOGINNAME`, listener trên
  sự kiện `Login` tự gán): an toàn nhất, không có URL công khai nào, nhưng đòi
  biết trước loginname lúc bàn giao.
- **Màn web khoá bằng `SETUP_TOKEN`**: linh hoạt, nhưng phải thêm cơ chế chặn
  dò mã và một URL công khai tồn tại trong mọi bản cài.

**Rủi ro đã biết của phương án được chọn, và đã được chấp nhận:** nếu bệnh viện
mở hệ thống cho nhân viên trước khi IT kịp khởi tạo, người vào đúng URL trước sẽ
chiếm quyền cao nhất. Phương án này thu hẹp rủi ro so với hiện trạng (phải chủ
động vào một URL cụ thể và bấm xác nhận, thay vì trúng ngẫu nhiên) nhưng không
loại bỏ nó. Giảm nhẹ bằng quy trình bàn giao: khởi tạo ngay sau khi cài, trước
khi thông báo đường dẫn cho nhân viên.

## Thiết kế

### Thành phần

**`App\Services\SuperAdminBootstrap`** — chỗ duy nhất giữ luật. Ba phương thức:

```php
const TEN_VAI_TRO = 'superadministrator';

public function chuaKhoiTao(): bool
{
    $roleId = Role::where('name', self::TEN_VAI_TRO)->value('id');

    if (! $roleId) {
        return true;   // chưa có vai trò thì chắc chắn chưa gán cho ai
    }

    return ! RoleUser::where('role_id', $roleId)
        ->where('user_type', $this->userType())
        ->exists();
}

public function vaiTro(): Role         // firstOrCreate
public function gan(CustomUser $user): void   // ném DaKhoiTaoException nếu đã có
private function userType(): string    // config('auth.providers.users.model')
```

`gan()` kiểm tra lại `chuaKhoiTao()` bên trong transaction; nếu hệ thống đã có
quản trị viên thì ném `App\Exceptions\DaKhoiTaoException`, controller bắt và trả
404. Không có đường nào gán quyền mà bỏ qua lần kiểm tra này.

Ba điểm cố ý:

- `value('id')` thay cho `first()->id`. Lỗi null biến mất không phải bằng cách
  vá thêm `if (! $role)`, mà vì không còn phép truy vấn nào có thể trả về đối
  tượng `null` để đọc thuộc tính.
- `vaiTro()` dùng `Role::firstOrCreate(['name' => ...], ['display_name' => ..., 'description' => ...])`.
  Người cài không chạy được seeder, nên màn khởi tạo phải tự tạo vai trò nếu
  thiếu. Chỉ tạo vai trò, không tạo permission — phần đó vẫn thuộc seeder.
- `gan()` dùng `$user->attachRole($this->vaiTro())` chứ không `RoleUser::create()`,
  để Laratrust tự `flushCache()`.
- `userType()` là nguồn duy nhất cho `user_type`, dùng ở cả đọc lẫn ghi.

**`App\Listeners\DanhDauCanKhoiTaoSuperAdmin`** — gắn vào
`Illuminate\Auth\Events\Login`. Gọi `chuaKhoiTao()` **một lần mỗi phiên đăng
nhập**, đặt cờ session `setup.can_khoi_tao`. Đây là chỗ đổi *2 truy vấn × mọi
request* thành *1 truy vấn × mỗi lần đăng nhập*.

**`App\Http\Controllers\SetupController`** — hai hành động:

| Phương thức | Đường dẫn | Việc |
|---|---|---|
| `GET` | `/setup/quan-tri-dau-tien` | Màn xác nhận |
| `POST` | `/setup/quan-tri-dau-tien` | Gán quyền |

Route nằm trong nhóm `['auth']` (nhóm `check.first.login` sẽ bị xoá).

**View** — một trang xác nhận nêu rõ: tài khoản đang đăng nhập là ai, quyền sắp
được cấp là quyền cao nhất, và sau bước này cổng đóng vĩnh viễn.

### Luồng

1. Người dùng đăng nhập → listener đặt cờ session nếu hệ thống còn trống.
2. Cờ bật → trang chủ hiện dải cảnh báo kèm đường dẫn tới màn khởi tạo.
3. `GET` hiển thị màn xác nhận.
4. `POST` (có CSRF) **kiểm tra lại `chuaKhoiTao()` phía máy chủ** trong một
   transaction rồi mới gán. Cờ session chỉ dùng để hiển thị; ranh giới bảo mật
   nằm ở lần kiểm tra lại này.
5. Gán xong: xoá cờ, ghi log kiểm toán, chuyển về trang chủ kèm thông báo.

### Đồng thời

`gan()` bọc trong `DB::connection('mysql')->transaction()`, kiểm tra lại
`chuaKhoiTao()` bên trong transaction. Hai người bấm cùng lúc thì người thứ hai
nhận thông báo hệ thống đã có quản trị viên, không tạo bản ghi thứ hai.

### Ghi log kiểm toán

Khi quyền được cấp, ghi vào log mặc định: loginname, `user_id`, IP, user agent,
thời điểm. Đây là sự kiện xảy ra đúng một lần trong vòng đời một bản cài, cần có
dấu vết.

### Xử lý lỗi

- **Đã khởi tạo rồi** → cả `GET` lẫn `POST` trả **404**, không phải 403. Sau khi
  cổng đóng, không để lộ sự tồn tại của màn này trong suốt vòng đời còn lại của
  bản cài.
- **Chưa đăng nhập** → middleware `auth` chuyển về trang đăng nhập như thường.
- **Vai trò chưa tồn tại** → `firstOrCreate` tự tạo, không phải lỗi.

### Điểm phải kiểm chứng khi triển khai

`CustomUser` nằm trên kết nối Oracle `ACS_RS`, còn bảng `role_user` và model
`App\Role` nằm trên `mysql`. Quan hệ `morphToMany` dựng truy vấn trên kết nối
của model liên quan (`App\Role` → `mysql`), nên `attachRole()` **được kỳ vọng**
ghi đúng vào MySQL — đây cũng là lý do `hasRole()` chạy được lâu nay. Nhưng mã
hiện tại cố tình tránh `attachRole()` và ghi thẳng qua model `RoleUser` (vốn
ghim `mysql`), nên không loại trừ khả năng tác giả cũ đã gặp trục trặc. Phải có
test tích hợp chứng minh `attachRole()` ghi đúng bảng trên đúng kết nối trước
khi coi phần này là xong; nếu không, `gan()` quay lại ghi qua `RoleUser` kèm gọi
`$user->flushCache()` thủ công.

## Xoá mã cũ

- Xoá `app/Http/Middleware/CheckFirstLogin.php`.
- Xoá dòng đăng ký `'check.first.login'` ở `app/Http/Kernel.php:65`.
- Sửa `routes/web.php:58`: `['auth', 'check.first.login']` → `['auth']`.
- Rà `tests/Unit/RouteOrderCheckTest.php:101` — test đó có nhắc tới nhóm
  middleware ngoài cùng; kiểm tra xem có assertion nào phụ thuộc chuỗi
  `check.first.login` không và cập nhật.
- Rà hai tài liệu cũ nhắc tới nhóm này:
  `docs/superpowers/specs/2026-07-29-order-check-menu-quyen-design.md:75` và
  `docs/superpowers/plans/2026-07-29-order-check-menu-quyen.md:101`. Chỉ sửa nếu
  gây hiểu nhầm; không viết lại lịch sử.

## Kiểm thử

| # | Ca | Kỳ vọng |
|---|---|---|
| 1 | Hệ thống trống, đã đăng nhập, `GET` | 200, hiện màn xác nhận |
| 2 | Đã có superadmin, `GET` | 404 |
| 3 | Đã có superadmin, `POST` | 404, không tạo bản ghi |
| 4 | Hệ thống trống, `POST` | Người dùng có vai trò; bản ghi `role_user` đúng `role_id`, `user_id`, `user_type` |
| 5 | `POST` lần hai sau khi đã thành công | 404, `role_user` vẫn đúng một bản ghi |
| 6 | Bảng `roles` chưa có `superadministrator`, `POST` | Vai trò được tạo, rồi gán |
| 7 | Nhóm route ngoài cùng | Không còn middleware `check.first.login` |

Cả bảy ca là feature test chạy thật vào cơ sở dữ liệu, **không mock**
`SuperAdminBootstrap`. Lý do: hạ tầng test của dự án có sẵn trục trặc giữa
Mockery và các phương thức khai báo kiểu trả về, mà lớp này khai báo `: bool`,
`: Role`, `: void`. Ca 4 và ca 6 đồng thời là bằng chứng cho điểm "phải kiểm
chứng" ở phần thiết kế — chúng kiểm tra bản ghi có thật trên kết nối `mysql`.

**Mốc nền bắt buộc:** hạ tầng test của dự án đã có sẵn một số test đỏ không liên
quan (Mockery vỡ với kiểu trả về, `route:list` chết). Phải chạy toàn bộ bộ test
**trước** khi sửa để ghi lại mốc nền, nếu không sẽ không phân biệt được test đỏ
do thay đổi này gây ra với test vốn đã đỏ.

## Ngoài phạm vi

- Không đụng tới `LaratrustSeederSuperUser` (đang ghim cứng `User::find(473)`) —
  nó là seeder riêng, không nằm trên đường đi của thay đổi này.
- Không đổi cơ chế `CheckRole` hay cách `filterMenu` xử lý superadministrator.
- Không thêm mã cài đặt hay biến `.env` nào; nếu sau này muốn siết, phương án
  `SETUP_TOKEN` đã mô tả ở phần "Phương án đã chọn" là đường nâng cấp sẵn có.

## Nhánh

`fix/khoi-tao-superadmin`, tách từ `main`, tách biệt khỏi `upgrade/laravel-13`
để nếu đợt nâng cấp có trục trặc thì biết chắc không phải do thay đổi này.
