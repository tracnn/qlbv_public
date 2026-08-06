# Bảo mật lớp API — ưu tiên 1

Ngày: 2026-08-06

## Bối cảnh

Spec `docs/superpowers/specs/2026-08-06-order-check-api-gop-loi-design.md` (Phần B) đã
ghi 10 điểm yếu bảo mật và 5 điểm hiệu năng của lớp API, kèm thứ tự triển khai. Tài liệu
này đặc tả **nhóm ưu tiên 1** — bốn việc rẻ nhưng chặn được rủi ro lớn nhất:

| Mã trong spec gốc | Việc |
|---|---|
| B1-1 | Token hiện tại là `md5("7")` — đoán được trong vài giây |
| B1-2 | So sánh token bằng `!==`, không constant-time |
| B1-6 | Ghi `Log::info` cho **mọi** request thành công |
| B2-1 | `order_check_violations` thiếu index trên `treatment_code` |

Ba nhóm còn lại (nhiều client, scope, giới hạn theo cơ sở KCB, audit log, gọi theo lô)
vẫn nằm ở spec gốc, không thuộc phạm vi tài liệu này.

## Bối cảnh đã xác minh

- Token chỉ được đọc ở **đúng một chỗ**: `app/Http/Middleware/ApiAuthMiddleware.php:47`.
- `config/organization.php` nằm trong `.gitignore` — mỗi bản cài là một tệp riêng, chưa
  từng được commit. Tệp này còn chứa cấu hình cơ sở KCB và tài khoản cổng BHXH.
- **Chưa có bên ngoài nào gọi API thật.** Vì vậy đổi token thẳng, không cần giai đoạn
  chấp nhận song song token cũ và mới.
- Nền tảng: Laravel 5.5, PHP ≥ 7.0.

---

## 1. Xác thực bằng bản băm

### Cấu hình

Mục `api` trong `config/organization.php` **bỏ** khoá `access_token`, thay bằng:

```php
'access_token_hash' => '',  // SHA-256 (hex) cua token; token goc KHONG luu o day
```

Token gốc không tồn tại ở bất kỳ đâu trong mã nguồn hay cấu hình — chỉ nằm ở bên gọi.

### Middleware

`ApiAuthMiddleware` so sánh:

```php
hash_equals($hashCauHinh, hash('sha256', $token))
```

`hash_equals` chạy thời gian không phụ thuộc số ký tự trùng đầu chuỗi, khép lại kênh
kề timing của `!==`.

### Bốn nhánh trả 401

Giữ nguyên khuôn response `{success:false, error:{code,message,details}, meta}` sẵn có.
`error.code` vẫn là `UNAUTHORIZED` cho cả bốn nhánh — không tách mã lỗi chi tiết ra ngoài,
vì phân biệt "sai token" với "chưa cấu hình" là thông tin có ích cho người dò.

| Nhánh | Khi nào | `error.details` |
|---|---|---|
| Thiếu header | Không có `Authorization` | `Please include 'Authorization: Bearer {token}' in your request headers` |
| Sai định dạng | Header không khớp `Bearer {token}` | `Authorization header must be in format: Bearer {token}` |
| Sai token | Hash không khớp | `The provided token is not valid or has expired` |
| **Chưa cấu hình** | `access_token_hash` rỗng hoặc thiếu | `The provided token is not valid or has expired` (giống nhánh sai token) |

Nhánh thứ tư là điểm dễ sai nhất và phải làm cho đúng: `config/organization.php` không
nằm trong git, nên bản cài chưa cập nhật sẽ **thiếu** khoá mới. Trạng thái an toàn duy
nhất khi thiếu cấu hình là **từ chối** — không phải 500, và tuyệt đối không phải cho qua.
Người vận hành nhận biết qua log `warning` với lý do `chua_cau_hinh`, chứ không qua
response.

## 2. Lệnh `php artisan api:generate`

Mô phỏng đúng cách làm của `php artisan key:generate`.

### Luồng

1. Sinh `random_bytes(32)`, đổi sang hex → token 64 ký tự.
2. Tính `hash('sha256', $token)`.
3. Nếu `config/organization.php` **đã có** hash khác rỗng: hỏi xác nhận trước khi ghi đè.
   `--force` bỏ qua bước hỏi. Ghi đè là cắt đứt mọi bên đang gọi, nên không làm lặng lẽ.
4. Thay **đúng một dòng** trong tệp bằng regex:

```php
preg_replace(
    "/'access_token_hash'\s*=>\s*'[^']*'/",
    "'access_token_hash' => '{$hash}'",
    $noiDung
)
```

5. Nếu tệp **không chứa** khoá `access_token_hash`: không ghi gì, in ra dòng cần tự thêm,
   trả mã thoát khác 0. Lệnh không đoán chỗ chèn — sửa mù một tệp bí mật thủ công là cách
   nhanh nhất làm hỏng nó.
6. Nếu tồn tại `bootstrap/cache/config.php`: gọi `config:clear`. Không làm thì hash mới
   nằm im trong tệp còn ứng dụng vẫn dùng bản cache cũ.
7. In token gốc ra màn hình **một lần**:

```
Token API mới (chép ngay, không hiện lại):
  7f3c9a...e91a
Đã ghi hash vào config/organization.php
```

### Vì sao ghi đè tại chỗ chứ không ghi lại cả tệp

`config/organization.php` chứa cấu hình cơ sở KCB và tài khoản cổng BHXH của từng bản
cài. Thay đúng một dòng bằng regex giữ nguyên mọi thứ còn lại, kể cả chú thích và thứ tự
khoá.

### Vì sao chỉ thay, không chèn

Xem bước 5. Bản cài cũ thiếu khoá là tình huống thật (tệp không nằm trong git), và người
vận hành cần biết để tự sửa đúng chỗ.

## 3. Ghi log

| Sự kiện | Mức | Nội dung |
|---|---|---|
| Xác thực thành công | `debug` | `endpoint`, `request_id` |
| Xác thực thất bại | `warning` | `endpoint`, `ip`, `user_agent`, `ly_do` |

`ly_do` nhận một trong bốn giá trị: `thieu_header`, `sai_dinh_dang`, `sai_token`,
`chua_cau_hinh`.

**Không ghi token dưới bất kỳ dạng nào** — kể cả một phần, kể cả bản băm.

Hiện `Log::info` chạy cho **mọi** request thành công, kèm IP và user-agent. Trên một API
được gọi liên tục, đó là dòng log vô nghĩa lấn át những dòng thật sự cần đọc. Hạ xuống
`debug` giữ lại khả năng bật khi cần chẩn đoán mà không phình log thường ngày.

## 4. Index `treatment_code`

Migration mới thêm index đơn `treatment_code` cho `order_check_violations`.

- Có kiểm tra tồn tại trước khi thêm, để chạy lại migration không lỗi.
- `down()` gỡ index, cũng có kiểm tra tồn tại.
- **Chỉ index đơn.** Composite `(treatment_code, status)` để lại cho tới khi đo thấy cần
  — API lọc theo `treatment_code` trước, số dòng còn lại của một đợt điều trị vốn nhỏ.

Bảng hiện chỉ có index trên `treatment_id`
(`database/migrations/2026_06_30_100002_create_order_check_violations_table.php:45-50`),
trong khi API tra cứu lọc chủ yếu theo `treatment_code`.

## 5. Kiểm thử

### Feature test cho middleware

Chạy qua chính endpoint `GET /api/order-check/violations`:

1. Token đúng (hash trong config khớp) → 200.
2. Token sai → 401.
3. Thiếu header `Authorization` → 401.
4. Header sai định dạng (`Token abc`, `Bearer` trống) → 401.
5. **`access_token_hash` rỗng trong config → 401** — ca dễ bị bỏ sót nhất.
6. Hash cũ dạng token thô (không phải SHA-256) → 401, để chắc chắn không còn đường so
   sánh trực tiếp.

### Unit test cho lệnh `api:generate`

Thao tác trên **tệp tạm** trong thư mục scratchpad, không đụng `config/organization.php`
thật:

1. Token sinh ra dài 64 ký tự, chỉ gồm `[0-9a-f]`.
2. Hai lần chạy cho hai token khác nhau.
3. Hash ghi vào tệp đúng bằng `hash('sha256', $token)` của token in ra.
4. Các dòng khác trong tệp giữ nguyên từng ký tự.
5. Tệp thiếu khoá `access_token_hash` → không ghi gì, mã thoát khác 0.

### Migration index

Không có test tự động — SQLite trong test không phản ánh index của MySQL. Nghiệm thu tay
sau khi chạy `php artisan migrate`:

```sql
SHOW INDEX FROM order_check_violations WHERE Column_name = 'treatment_code';
```

## 6. Triển khai

`config/organization.php` nằm trong `.gitignore` nên **không đi theo commit** — mỗi bản
cài phải tự sửa. Thứ tự đúng trên từng máy:

1. Trong mục `api`, bỏ dòng `'access_token' => '...'`, thêm `'access_token_hash' => ''`.
2. Chạy `php artisan api:generate`.
3. Chép token gốc, giao cho bên gọi.
4. Chạy `php artisan migrate` để thêm index.

Bỏ qua bước 1–2 thì API trả 401 cho mọi request — có chủ đích, xem mục 1.

## 7. Tài liệu

Bổ sung vào `docs/order-check/API-TRA-CUU-LOI.md` một mục ngắn **"Cấp token (dành cho
quản trị)"**: chạy `php artisan api:generate`, chép token gốc giao cho bên gọi, hash tự
ghi vào config; token gốc không hiện lại lần thứ hai, mất thì sinh lại.

## Ngoài phạm vi

- Nhiều client, scope, throttle theo client, limiter chống dò (ưu tiên 2 của spec gốc).
- Giới hạn theo cơ sở KCB, audit log, endpoint gọi theo lô (ưu tiên 3).
- Ép HTTPS — phụ thuộc hạ tầng từng nơi cài, quyết định riêng.
- Giao diện quản trị token.
