# Nâng cấp API order-check: gộp lỗi y lệnh + tra thẻ + XML3176

Ngày: 2026-08-06

## Bối cảnh

Hiện có `GET /api/order-check/violations` trả về danh sách vi phạm y lệnh của một đợt
điều trị, dùng cho HIS và các màn hình khác. Endpoint này chỉ nhìn thấy **một trong ba**
nguồn lỗi mà hệ thống đang nắm về cùng một đợt điều trị:

| Nguồn | Bảng | Khoá |
|---|---|---|
| Sai sót y lệnh | `order_check_violations` | `treatment_code` / `treatment_id` |
| Lỗi tra thẻ BHYT | `check_hein_cards` | `ma_lk` (unique) |
| Lỗi XML3176 | `xml3176_error_results` | `ma_lk` |

Ba khoá là cùng một giá trị (`ma_lk` = `treatment_code`), nên gộp được trong một lần gọi.

Chưa có tài liệu API riêng cho module; code hiện tại
(`OrderCheckController@apiViolations`) là đặc tả duy nhất.

## Mục tiêu

1. Một lần gọi trả về đủ ba nhóm lỗi của một đợt điều trị. **Thực thi ngay.**
2. Xác định và ghi lại các điểm yếu bảo mật, hiệu năng của lớp API. **Chỉ viết tài
   liệu ở giai đoạn này, chưa sửa code.**

## Ngoài phạm vi

- Không sửa `ApiAuthMiddleware`, không đổi token, không thêm bảng ở giai đoạn này.
- Không đụng tới các endpoint dashboard khác trong `routes/api.php`.
- Không xây giao diện quản trị token.

---

# Phần A — Hợp đồng API (thực thi ngay)

## A1. Endpoint

`GET /api/order-check/violations`

Giữ nguyên URL và middleware (`throttle:60,1`, `api.auth`). **Định dạng response thay
đổi phá vỡ tương thích** — trước đây trả mảng thuần, nay trả đối tượng bọc. Đã xác nhận
chấp nhận vì bên gọi hiện tại nằm trong tầm kiểm soát.

### Tham số

| Tham số | Bắt buộc | Mô tả |
|---|---|---|
| `treatment_code` | Một trong hai | Mã đợt điều trị (= `ma_lk`) |
| `treatment_id` | Một trong hai | ID đợt điều trị trên HIS |
| `status` | Không | Lọc riêng nhóm `order_check` theo trạng thái (`new`/`seen`/`processed`/`false_positive`) |

Khi chỉ truyền `treatment_id`, hai nhóm `hein_card` và `xml3176` cần `ma_lk` để tra.
Service tự lấy `treatment_code` từ dòng vi phạm đầu tiên tìm được; nếu không có dòng nào
thì hai nhóm đó trả rỗng. Ghi rõ trong tài liệu cho bên gọi: **nên truyền
`treatment_code`** để có đủ ba nhóm.

## A2. Response thành công (HTTP 200)

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
        "ghi_chu": "...",
        "ma_the_masked": "****1234",
        "checked_at": "2026-08-05 14:03:00"
      }
    ],
    "xml3176": [
      {
        "xml": "XML1",
        "stt": 1,
        "error_code": "L001",
        "error_name": "Sai mã thẻ BHYT",
        "description": "...",
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
    "request_id": "req_..."
  }
}
```

`summary.critical` đếm gộp: `order_check.severity = 'critical'` cộng
`xml3176.critical_error = true`. Nhóm tra thẻ không có khái niệm mức độ nên không tính
vào `critical`, nhưng luôn tính vào `total`.

`summary.truncated` bật khi bất kỳ nhóm nào chạm trần 500 dòng (xem B2-3).

## A3. Quy tắc lấy dữ liệu

### Nhóm `order_check`

- Nguồn: `order_check_violations`.
- Lọc theo `treatment_code` hoặc `treatment_id` theo tham số nhận được.
- **Mặc định loại bỏ `status = 'false_positive'`** — đây là các dòng người dùng đã xác
  nhận không phải lỗi, đẩy sang HIS chỉ gây nhiễu. Bên gọi muốn xem thì truyền
  `status=false_positive` tường minh.
- Sắp xếp `detected_at` giảm dần.
- Cột trả về: giữ nguyên tập cột hiện tại (`id`, `rule_code`, `severity`,
  `order_ref_type`, `order_ref_id`, `message`, `detail`, `status`, `detected_at`).
  Không bổ sung thông tin bệnh nhân/khoa: HIS đã có sẵn, thêm vào chỉ là PII thừa.
- `detail` đang lưu dạng chuỗi JSON trong cột `text`. Service giải mã thành đối tượng
  trước khi trả; giải mã thất bại thì trả `null` (không ném lỗi vì một dòng hỏng).

### Nhóm `hein_card`

- Nguồn: `check_hein_cards`, unique theo `ma_lk` nên tối đa một dòng. Vẫn trả về **mảng**
  cho đồng nhất với hai nhóm kia và để mở khả năng lưu nhiều lần tra sau này.
- Chỉ trả khi có bất thường: dùng lại scope sẵn có `check_hein_card::scopeChiLoi()`
  (`ma_tracuu != '000'` HOẶC `ma_kiemtra != '00'`). Không tự viết lại điều kiện — quy tắc
  này đã được cân nhắc và ghi chú kỹ trong model.
- **Tối thiểu PII:** chỉ trả `ma_tracuu`, `ma_kiemtra`, `ma_ketqua`, `ghi_chu`,
  `ma_the_masked`, `checked_at`. Không trả `ho_ten`, `ngay_sinh`, `dia_chi`, `maso_bhxh`,
  `ma_the` đầy đủ.
- `ma_the_masked` = 4 ký tự cuối của `ma_the`, tiền tố `****`. Thẻ rỗng → `null`.
- `checked_at` lấy từ `updated_at` (lần tra gần nhất).

### Nhóm `xml3176`

- Nguồn: `xml3176_error_results` lọc theo `ma_lk`.
- **LEFT JOIN `xml3176_error_catalogs` theo cặp `(xml, error_code)`** — bảng danh mục
  unique theo cặp này, join chỉ bằng `error_code` sẽ nhân dòng khi một mã lỗi xuất hiện ở
  nhiều loại XML. Quan hệ `hasOne` sẵn có trong model `Xml3176ErrorResult` nối thiếu cột
  `xml`, **không dùng lại** cho API này.
- `error_name` lấy từ danh mục; không khớp danh mục → `null` (vẫn trả dòng lỗi).
- `critical_error` lấy từ chính `xml3176_error_results` (giá trị tại thời điểm kiểm),
  không lấy từ danh mục.
- Sắp xếp `xml`, rồi `stt`.

## A4. Trường hợp không có dữ liệu

Không tìm thấy đợt điều trị, hoặc đợt sạch: trả **HTTP 200** với ba mảng rỗng,
`summary.total = 0`, `has_error = false`. Không dùng 404 — HIS gọi cho mọi đợt điều trị,
"không có lỗi" là kết quả hợp lệ chứ không phải tài nguyên không tồn tại.

## A5. Response lỗi

Thiếu cả `treatment_code` lẫn `treatment_id` → **HTTP 422** theo đúng khuôn của
`ApiAuthMiddleware`:

```json
{
  "success": false,
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "Thiếu tham số bắt buộc",
    "details": "Cần truyền treatment_code hoặc treatment_id"
  },
  "meta": { "timestamp": "20260806091200", "request_id": "req_..." }
}
```

Hiện `$request->validate()` trả khuôn Laravel mặc định (`{"treatment_code": [...]}`),
khác hoàn toàn khuôn lỗi 401 của middleware — bên gọi phải xử lý hai định dạng. Nâng cấp
này thống nhất về một khuôn bằng cách kiểm tham số thủ công trong controller thay vì
`validate()`.

Lỗi không lường trước (DB hỏng...) → HTTP 500 cùng khuôn, `code = "INTERNAL_ERROR"`,
`details` không lộ thông điệp ngoại lệ ra ngoài; chi tiết chỉ ghi vào log.

## A6. Kiến trúc

```
routes/api.php
  └─ OrderCheckController@apiViolations   (chỉ đọc request, gọi service, bọc JSON)
       └─ App\Services\OrderCheck\TreatmentIssueService
            ├─ viPhamYLenh()   → order_check_violations
            ├─ loiTraThe()     → check_hein_cards (scope chiLoi)
            └─ loiXml3176()    → xml3176_error_results JOIN catalog
```

`TreatmentIssueService::cua(string $treatmentCode = null, $treatmentId = null, array $tuyChon = []): array`
trả mảng thuần gồm `data` và `summary`. Không phụ thuộc `Request`, không tự bọc HTTP —
nhờ vậy test được không cần gọi HTTP, và màn hình nội bộ khác dùng lại được.

Controller không chứa logic truy vấn. Ba hàm con của service tách rời, mỗi hàm một nguồn,
thay đổi quy tắc của một nguồn không đụng hai nguồn kia.

## A7. Kiểm thử

Feature test cho endpoint (`tests/Feature/OrderCheckApiTest.php`):

1. Đợt có đủ ba nhóm lỗi → 200, ba mảng đúng số dòng, `summary` khớp, `has_error = true`.
2. Đợt chỉ có lỗi XML3176 → hai mảng kia rỗng.
3. Đợt sạch → ba mảng rỗng, `has_error = false`, HTTP 200.
4. Thiếu cả hai tham số → 422 đúng khuôn `{success:false, error:{code,...}}`.
5. Thẻ hợp lệ (`ma_tracuu='000'`, `ma_kiemtra='00'`) → nhóm `hein_card` rỗng.
6. Một `error_code` tồn tại ở hai loại XML trong danh mục → **không nhân dòng** (chốt
   quy tắc join theo cặp `(xml, error_code)`).
7. Vi phạm `false_positive` → không xuất hiện khi không truyền `status`.

Unit test cho service ở mức che mã: masking thẻ (thẻ rỗng, thẻ ngắn hơn 4 ký tự) và giải
mã `detail` hỏng → `null`.

Kiểm thử dùng hạ tầng test sẵn có của dự án. Lưu ý đã biết: bộ test hiện có sẵn một số
ca đỏ — chạy toàn bộ test **trước** khi sửa để biết mốc, không lấy trạng thái đỏ sẵn làm
kết quả của thay đổi này.

---

# Phần B — Bảo mật và hiệu năng (chỉ tài liệu, chưa thực thi)

Nền tảng: Laravel 5.5, PHP ≥ 7.0. Không dùng được Laravel Sanctum; hướng đi là mở rộng
middleware tự viết sẵn có.

## B1. Điểm yếu bảo mật đã xác minh

### 1. Token đoán được trong vài giây

`config/organization.php:80` đặt `access_token = '8f14e45fceea167a5a36dedd4bea2543'`.
Chuỗi này chính là `md5("7")` — trông như ngẫu nhiên nhưng nằm trong mọi bảng tra md5
phổ thông.

Xử lý: sinh token ngẫu nhiên 32 byte, lưu **bản băm SHA-256** trong config, phát bản gốc
một lần cho bên gọi.

### 2. So sánh chuỗi không constant-time

`ApiAuthMiddleware.php:50` dùng `$token !== $validToken`. Thời gian so sánh phụ thuộc số
ký tự trùng đầu chuỗi. Xử lý: `hash_equals()`.

### 3. Một token dùng chung cho mọi bên gọi

`routes/api.php:20` bọc toàn bộ nhóm endpoint bằng một token duy nhất. Lộ token ở một
bên là phải đổi cho tất cả; không truy được bên nào đã gọi.

Xử lý: danh sách client trong config, mỗi client có `ten`, `token_hash`, `scopes`,
`ip_allowlist`, `bat`, `het_han`.

### 4. Khai quyền nhưng không thực thi

`permissions => ['read:all']` có trong config nhưng **không được đọc ở bất kỳ đâu**.
Token nào cũng gọi được mọi endpoint.

Xử lý: scope thực thi thật, middleware nhận tham số — `api.auth:order-check:read`.

### 5. Không giới hạn theo cơ sở KCB

Client của cơ sở A tra được đợt điều trị của cơ sở B.

Xử lý: gán `ma_cskcb` cho client, lọc `order_check_violations.ma_cskcb` và
`check_hein_cards.ma_cskcb`. Lưu ý: `xml3176_error_results` **không có cột cơ sở**, phải
join ngược qua `xml3176_xml1s` / `xml3176_information` — cần khảo sát đường tra trước
khi làm.

### 6. Log phình và nhiễu

`ApiAuthMiddleware.php:66` ghi `Log::info` cho **mọi** request thành công, kèm IP,
user-agent, đường dẫn.

Xử lý: thành công ghi mức `debug`; thất bại ghi mức `warning` kèm IP và `request_id`.
**Không bao giờ ghi token, kể cả một phần.**

### 7. Không chặn dò token

Không có limiter riêng cho xác thực sai; kẻ dò chỉ bị chặn bởi throttle chung.

Xử lý: quá 10 lần sai/phút/IP → 429 kèm `Retry-After`.

### 8. Rate limit theo IP dùng chung

`throttle:60,1` khoá theo IP cho cả nhóm endpoint. HIS đứng sau NAT dùng chung hạn mức
với mọi bên gọi khác.

Xử lý: đặt khoá throttle theo **tên client** thay vì IP.

### 9. Không ép HTTPS

Không có kiểm tra `isSecure()`; token đi qua HTTP là lộ nguyên văn trên đường truyền.

Xử lý: bắt buộc HTTPS ở môi trường production. Nếu triển khai trong mạng LAN nội bộ
không có chứng chỉ, ghi rõ ngoại lệ đó trong tài liệu triển khai và cấu hình
`TrustProxies` cho đúng.

### 10. Không có vết truy cập dữ liệu bệnh nhân

Không trả lời được câu hỏi "ai đã tra hồ sơ này".

Xử lý: bảng `api_access_logs` tối giản — client, endpoint, `ma_lk`, thời điểm, số dòng
trả về.

### Nguyên tắc cấu hình

Danh sách client khai **thẳng** trong `config/organization.php`, không qua `env()`. Tệp
này đã nằm trong `.gitignore` và vốn là tệp bí mật riêng của từng lần cài đặt; thêm một
tầng `env()` ở giữa không giấu thêm được gì mà chỉ làm cấu hình chia đôi. Đây là nguyên
tắc đã được ghi trong chính tệp đó, giữ nguyên.

## B2. Hiệu năng

### 1. Truy vấn chính quét toàn bảng

`order_check_violations` chỉ có index trên `treatment_id`
(`database/migrations/2026_06_30_100002_create_order_check_violations_table.php:45-50`),
**không có index trên `treatment_code`** — trong khi API lọc chủ yếu theo
`treatment_code`.

Xử lý: thêm index `treatment_code`; cân nhắc composite `(treatment_code, status)` nếu đo
thấy đáng.

### 2. Nguy cơ N+1 khi lấy tên lỗi

Quan hệ `hasOne` trong `Xml3176ErrorResult` nếu dùng theo kiểu lazy sẽ sinh một truy vấn
mỗi dòng. Phần A đã chốt: join sẵn trong một truy vấn.

### 3. Payload không có trần

Một đợt điều trị dài có thể có rất nhiều dòng lỗi XML3176. Máy chủ mới giới hạn PHP
128MB / 120s nên cần trần cứng.

Xử lý: giới hạn 500 dòng mỗi nhóm, bật cờ `summary.truncated`. Áp dụng ngay ở phần A.

### 4. HIS gọi vòng lặp từng đợt

Giai đoạn sau: nhận `treatment_codes[]` tối đa 50 mã trong một lần gọi, trả về map theo
mã.

### 5. Không cache — có chủ đích

Dữ liệu đổi sau mỗi lần quét order-check và mỗi lần import XML3176. Cache 60s khiến bác
sĩ sửa xong y lệnh vẫn thấy lỗi cũ, sinh nghi ngờ vào toàn bộ số liệu. Ghi rõ lý do ở
đây để sau này không ai thêm cache nhầm.

## B3. Thứ tự triển khai đề xuất

1. **Ưu tiên cao, chi phí thấp:** đổi token mạnh + `hash_equals` + hạ mức log + index
   `treatment_code` (B1-1, B1-2, B1-6, B2-1).
2. **Trung bình:** nhiều client + scope + throttle theo client + limiter chống dò
   (B1-3, B1-4, B1-7, B1-8).
3. **Sau cùng:** giới hạn theo cơ sở KCB, audit log, endpoint gọi theo lô
   (B1-5, B1-10, B2-4).

Mục B1-9 (ép HTTPS) phụ thuộc hạ tầng triển khai, quyết định riêng theo từng nơi cài.

---

## Tài liệu kèm theo

Sau khi thực thi phần A, viết tài liệu API cho bên gọi tại
`docs/order-check/API-TRA-CUU-LOI.md`: endpoint, tham số, ví dụ request/response đầy đủ
ba nhóm, bảng mã lỗi HTTP, và ghi chú **nên truyền `treatment_code`** thay vì
`treatment_id`.
