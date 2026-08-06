# API tra cứu lỗi của một đợt điều trị

Trả về **toàn bộ lỗi** hệ thống ghi nhận được cho một đợt điều trị, gồm ba nhóm:

1. **Sai sót y lệnh** (`order_check`) — do bộ quét order-check phát hiện trên HIS.
2. **Lỗi tra thẻ BHYT** (`hein_card`) — kết quả tra cứu thẻ trên cổng BHXH.
3. **Lỗi XML3176** (`xml3176`) — kết quả kiểm hồ sơ XML theo QĐ 3176.

## 1. Endpoint và xác thực

```
GET /api/order-check/violations
```

| Mục | Giá trị |
|---|---|
| Header bắt buộc | `Authorization: Bearer {token}` |
| Giới hạn | 60 request/phút |
| Phương thức | Chỉ `GET`, chỉ đọc, không thay đổi dữ liệu |

Token do đơn vị quản trị hệ thống cấp.

### Cấp token (dành cho quản trị)

```bash
php artisan api:generate
```

Lệnh sinh token ngẫu nhiên 64 ký tự, in ra màn hình **một lần**, và ghi bản băm SHA-256
của nó vào `config/organization.php`. Token gốc không được lưu ở đâu trong hệ thống —
chép ngay khi lệnh in ra để giao cho bên gọi; mất thì sinh lại chứ không xem lại được.

Sinh lại token làm mọi bên đang dùng token cũ nhận 401 ngay lập tức. Lệnh hỏi xác nhận
trước khi ghi đè; `--force` bỏ qua bước hỏi.

Bản cài chưa có khoá `access_token_hash` trong `config/organization.php` sẽ trả 401 cho
mọi request — thêm dòng `'access_token_hash' => '',` vào mục `api` rồi chạy lại lệnh.

## 2. Tham số

| Tham số | Bắt buộc | Mô tả |
|---|---|---|
| `treatment_code` | Một trong hai | Mã đợt điều trị (chính là `ma_lk` trong hồ sơ XML) |
| `treatment_id` | Một trong hai | ID đợt điều trị trên HIS |
| `status` | Không | Lọc riêng nhóm `order_check` theo trạng thái: `new`, `seen`, `processed`, `false_positive` |

> **Nên truyền `treatment_code`.** Hai nhóm `hein_card` và `xml3176` khoá theo `ma_lk`.
> Nếu chỉ truyền `treatment_id`, hệ thống phải suy ngược `ma_lk` từ dòng vi phạm y lệnh —
> đợt điều trị chưa có vi phạm y lệnh nào thì hai nhóm đó sẽ trả về rỗng dù thực tế có lỗi
> thẻ hoặc lỗi XML.

Ví dụ:

```
GET /api/order-check/violations?treatment_code=01013250800123
Authorization: Bearer {token}
```

## 3. Ví dụ response thành công (HTTP 200)

```json
{
  "success": true,
  "data": {
    "treatment_code": "01013250800123",
    "order_check": [
      {
        "id": 1,
        "rule_code": "REQ_TIME_INVALID",
        "severity": "critical",
        "order_ref_type": "service_req",
        "order_ref_id": 123456,
        "message": "Thời gian y lệnh trước thời gian vào viện",
        "detail": { },
        "status": "new",
        "detected_at": "2026-08-06 09:12:00"
      }
    ],
    "hein_card": [
      {
        "ma_tracuu": "005",
        "ma_kiemtra": "00",
        "ma_ketqua": "Thẻ hết hạn sử dụng",
        "ghi_chu": null,
        "ma_the_masked": "****5678",
        "checked_at": "2026-08-05 14:03:00"
      }
    ],
    "xml3176": [
      {
        "xml": "XML1",
        "stt": 1,
        "error_code": "L001",
        "error_name": "Sai mã thẻ BHYT",
        "description": "Mã thẻ không khớp dữ liệu cổng",
        "critical_error": true,
        "ngay_yl": "20260805",
        "ngay_kq": "20260805"
      }
    ]
  },
  "summary": {
    "total": 3,
    "order_check": 1,
    "hein_card": 1,
    "xml3176": 1,
    "critical": 2,
    "has_error": true,
    "truncated": false
  },
  "meta": {
    "timestamp": "20260806091200",
    "request_id": "req_66b2c1f0a1b2c"
  }
}
```

## 4. Giải thích các trường

### `data.order_check[]` — sai sót y lệnh

| Trường | Kiểu | Ý nghĩa |
|---|---|---|
| `id` | số | Khoá của bản ghi vi phạm |
| `rule_code` | chuỗi | Mã quy tắc đã phát hiện ra vi phạm |
| `severity` | chuỗi | `critical` / `warning` / `info` |
| `order_ref_type` | chuỗi | Loại đối tượng bị vi phạm: `service_req`, `treatment`, `sere_serv`, `exp_mest_medicine`, `medicine_interactive` |
| `order_ref_id` | số | Khoá của đối tượng đó trên HIS |
| `message` | chuỗi | Mô tả lỗi hiển thị được cho người dùng |
| `detail` | đối tượng hoặc `null` | Dữ liệu bổ sung tuỳ theo quy tắc. `null` khi quy tắc không kèm chi tiết |
| `status` | chuỗi | `new` / `seen` / `processed` / `false_positive` |
| `detected_at` | chuỗi | Thời điểm phát hiện, dạng `Y-m-d H:i:s` |

### `data.hein_card[]` — lỗi tra thẻ BHYT

| Trường | Kiểu | Ý nghĩa |
|---|---|---|
| `ma_tracuu` | chuỗi | Mã tra cứu trả về từ cổng BHXH (`000` là sạch) |
| `ma_kiemtra` | chuỗi | Mã kiểm tra trả về từ cổng BHXH (`00` là sạch) |
| `ma_ketqua` | chuỗi | Diễn giải kết quả tra cứu |
| `ghi_chu` | chuỗi hoặc `null` | Ghi chú kèm theo từ cổng |
| `ma_the_masked` | chuỗi hoặc `null` | **Chỉ 4 ký tự cuối** của mã thẻ, tiền tố `****`. `null` khi hồ sơ không có mã thẻ |
| `checked_at` | chuỗi | Thời điểm tra cứu gần nhất, dạng `Y-m-d H:i:s` |

Mảng này tối đa một phần tử (mỗi đợt điều trị lưu một kết quả tra thẻ).

### `data.xml3176[]` — lỗi XML3176

| Trường | Kiểu | Ý nghĩa |
|---|---|---|
| `xml` | chuỗi | Loại XML chứa lỗi, ví dụ `XML1`, `XML2` |
| `stt` | số | Số thứ tự dòng trong XML đó |
| `error_code` | chuỗi | Mã lỗi |
| `error_name` | chuỗi hoặc `null` | Tên lỗi lấy từ danh mục. `null` khi mã lỗi chưa có trong danh mục — dòng lỗi vẫn được trả về |
| `description` | chuỗi hoặc `null` | Mô tả cụ thể tại thời điểm kiểm |
| `critical_error` | boolean | Lỗi nghiêm trọng (chặn gửi hồ sơ) hay không |
| `ngay_yl` | chuỗi hoặc `null` | Ngày y lệnh, dạng `YYYYMMDD` |
| `ngay_kq` | chuỗi hoặc `null` | Ngày kết quả, dạng `YYYYMMDD` |

### `summary`

| Trường | Ý nghĩa |
|---|---|
| `total` | Tổng số dòng của **cả ba** nhóm |
| `order_check`, `hein_card`, `xml3176` | Số dòng của từng nhóm |
| `critical` | Gộp hai nguồn: `order_check` có `severity = critical`, cộng `xml3176` có `critical_error = true`. **Nhóm tra thẻ không tính vào `critical`** (dữ liệu tra thẻ không có khái niệm mức độ) nhưng vẫn tính vào `total` |
| `has_error` | `true` khi `total > 0` |
| `truncated` | `true` khi một nhóm chạm trần 500 dòng và đã bị cắt bớt |

### `meta`

| Trường | Ý nghĩa |
|---|---|
| `timestamp` | Thời điểm xử lý, dạng `YmdHis` |
| `request_id` | Mã định danh lần gọi, dùng khi báo lỗi để đối chiếu log |

## 5. Quy tắc lọc dữ liệu

- **Nhóm `order_check`:** mặc định **bỏ** các dòng `status = false_positive` (đã được xác
  nhận không phải lỗi). Muốn xem thì truyền `status=false_positive` tường minh.
- **Nhóm `hein_card`:** chỉ trả dòng **bất thường** — `ma_tracuu` khác `000` **hoặc**
  `ma_kiemtra` khác `00`. Thẻ hợp lệ hoàn toàn thì mảng rỗng.
- **Mã thẻ BHYT** chỉ trả 4 ký tự cuối. Không trả họ tên, ngày sinh, địa chỉ, mã số BHXH —
  HIS đã có sẵn các thông tin này.
- **Trần 500 dòng mỗi nhóm.** Chạm trần thì `summary.truncated = true`.

## 6. Mã lỗi HTTP

| Mã | `error.code` | Khi nào |
|---|---|---|
| 200 | — | Thành công. **Kể cả khi không có lỗi nào**: ba mảng rỗng, `has_error = false` |
| 401 | `UNAUTHORIZED` | Thiếu header `Authorization`, sai định dạng `Bearer`, hoặc token không hợp lệ |
| 422 | `VALIDATION_ERROR` | Thiếu cả `treatment_code` lẫn `treatment_id` |
| 429 | — | Vượt 60 request/phút |
| 500 | `INTERNAL_ERROR` | Lỗi hệ thống. Gửi `meta.request_id` cho quản trị để tra log |

Khuôn response lỗi:

```json
{
  "success": false,
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "Thiếu tham số bắt buộc",
    "details": "Cần truyền treatment_code hoặc treatment_id"
  },
  "meta": {
    "timestamp": "20260806091200",
    "request_id": "req_66b2c1f0a1b2c"
  }
}
```

Đợt điều trị không tồn tại hoặc không có lỗi nào **không** trả 404 — trả 200 với ba mảng
rỗng. Bên gọi dùng `summary.has_error` để biết có lỗi hay không.

## 7. Ghi chú thay đổi

Trước bản nâng cấp này, endpoint trả về **một mảng thuần** các vi phạm y lệnh:

```json
[ { "id": 1, "rule_code": "...", "severity": "...", "...": "..." } ]
```

Nay trả về **đối tượng bọc** `{success, data, summary, meta}` và có thêm hai nhóm lỗi.
Bên gọi cũ phải sửa: danh sách vi phạm y lệnh nay nằm ở `data.order_check` thay vì ở gốc
response.
