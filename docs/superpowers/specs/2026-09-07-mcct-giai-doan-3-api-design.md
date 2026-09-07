# Thiết kế: API tra cứu MCCT cho hệ thống ngoài — Giai đoạn 3

Ngày: 2026-09-07
Trạng thái: đã thống nhất, chờ lập kế hoạch thực thi

## 1. Mục tiêu

Cho hệ thống khác (trước hết là HIS) lấy được thông tin tiền cùng chi trả của người bệnh
qua HTTP, để xác định mức hưởng BHYT ngay tại quầy tiếp đón mà không phải mở màn hình qlbv.

Đây là **Giai đoạn 3** đã tách trong
`docs/superpowers/specs/2026-09-03-mcct-giai-doan-1-design.md` mục 2. Giai đoạn 1 (service
gọi cổng, hai bảng lưu vết, hai màn tra cứu) đã nghiệm thu xong; giai đoạn này dùng lại
nguyên hạ tầng đó.

Giai đoạn 2 (tra hàng loạt, báo cáo đối soát) **chưa làm** và không phải điều kiện tiên
quyết của giai đoạn này.

## 2. Vấn đề cốt lõi: độ trễ của cổng

Cổng BHXH trả lời trong **5–60 giây**. Một API tiếp đón mà chặn người dùng 60 giây là không
dùng được: phần lớn thư viện HTTP đặt timeout mặc định 30 giây, nên bên gọi sẽ tự cắt giữa
chừng — và lượt gọi lên cổng **vẫn bị tiêu**, vì cổng không biết bên kia đã bỏ đi.

Cổng còn có **danh sách tài khoản bị hạn chế tra cứu**, nên số lượt gọi là tài nguyên có
hạn. Một vòng lặp hỏng ở hệ thống gọi có thể làm tài khoản của cả bệnh viện bị khoá.

Toàn bộ thiết kế dưới đây xoay quanh hai ràng buộc này.

## 3. Quyết định đã chốt

1. **Mặc định trả dữ liệu đã lưu; có tham số buộc gọi cổng.** `lam_moi=0` (mặc định) trả về
   dưới 100ms từ CSDL kèm mốc `tra_luc` để bên gọi tự đánh giá độ tươi. `lam_moi=1` gọi cổng
   đồng bộ và chấp nhận chờ. Bên gọi tự quyết định, không bị ép chờ.
2. **Trả kết luận kèm chi tiết đợt KCB.** Bên gọi tự chọn hiển thị gì; không bắt họ gọi
   thêm một API thứ hai để lấy bảng chi tiết.
3. **Chặn gọi lại cùng một mã thẻ trong một khẩu độ** (`config('mcct.khoang_cho_lam_moi')`,
   mặc định 15 phút). Cộng thêm `throttle` theo IP như API sẵn có.
4. **Dùng lại `api.auth` sẵn có** — một token chung, băm SHA-256, đã chạy thật cho
   `api/order-check/violations`. Không thêm hạ tầng xác thực mới.
5. **Tách lõi tra cứu ra `McctTraCuuChung`**, dùng chung cho cả màn web lẫn API.
   `McctController::thucHien()` hiện là `private` và ghép: gọi cổng → tính ngưỡng → lưu vết.
   API cần đúng chuỗi đó cộng phần chống gọi lại. Chép lại nghĩa là có hai chỗ quyết định
   "một lần tra cứu nghĩa là gì" — dự án này đã bị cắn ba lần vì chép đôi (hai con số
   timeout, hai bộ dựng kết quả, và suýt nữa là hai cách quyết định ngưỡng tính theo ngày
   nào).
6. **Controller riêng** `app/Http/Controllers/Api/McctApiController.php`, không thêm method
   vào `McctController`. Màn web và API cho hệ thống ngoài có ranh giới xác thực khác nhau;
   để chung một lớp là mời gọi nhầm lẫn về sau.

## 4. Endpoint

`GET /api/mcct/tra-cuu`

Khai trong `routes/api.php`, trong nhóm `['throttle:60,1', 'api.auth']` — y hệt
`api/order-check/violations`.

| Tham số | Bắt buộc | Ghi chú |
|---|---|---|
| `ma_the` | ✅ luôn | Sau khi bỏ khoảng trắng phải dài 10, 12 hoặc 15 ký tự |
| `lam_moi` | không | `1` = gọi cổng thật. Mặc định `0` = chỉ đọc dữ liệu đã lưu |
| `ho_ten` | ✅ khi `lam_moi=1` | Cổng đòi |
| `ngay_sinh` | ✅ khi `lam_moi=1` | `dd/mm/yyyy`, `mm/yyyy` hoặc `yyyy` |
| `ma_cskcb` | ✅ khi `lam_moi=1` | Phải thuộc `config('organization.BHYT_CO_SO')` |

Ba tham số cuối chỉ bắt buộc khi `lam_moi=1` chứ không bắt buộc luôn: một lời gọi "đã có dữ
liệu gì chưa" chỉ cần mã thẻ, và bắt bên gọi truyền thừa ba trường không dùng tới là bắt họ
đi tìm dữ liệu họ chưa có.

## 5. Khuôn phản hồi

Bám đúng khuôn `{success, data, meta}` của `ApiAuthMiddleware` và `apiViolations` sẵn có.

### 5.1. Thành công

```json
{
  "success": true,
  "data": {
    "ma_the": "HT3382797052765",
    "nguon": "da_luu",
    "tra_luc": "2026-09-07 11:45:11",
    "ghi_chu": "Nguồn DL lấy từ các CSKCB đề nghị thanh toán KCB BHYT trên HTTTGĐ BHYT tính đến: 14/08/2026 14:41",
    "thong_tin_the": {
      "ho_ten": "Trần Thị Vân",
      "ngay_sinh": "20/10/1964",
      "ngay_ket_thuc": "2027-06-30",
      "ma_bhxh": "03800"
    },
    "luy_ke_cung_chi_tra": 4762612,
    "nguong_ca_nam": 15066588,
    "con_thieu": 10303976,
    "du_nguong_6_thang_luong": false,
    "can_kiem_5_nam_lien_tuc": true,
    "chi_tiet": [
      {
        "ma_cskcb": "01929",
        "ngay_vao": "2026-08-13",
        "ngay_ra": "2026-08-14",
        "ma_doi_tuong_kcb": "1.5",
        "t_bn_cct_mcct": 893973,
        "t_bn_cct_luy_ke": 3862166,
        "ngay_nhan": "2026-08-14"
      }
    ]
  },
  "meta": { "timestamp": "20260907114511", "request_id": "req_..." }
}
```

`nguon` nhận `da_luu` hoặc `cong_bhxh` — bên gọi biết được số vừa nhận đến từ đâu.

**Hai tên trường cố ý đặt như vậy**, và đây là điểm quan trọng nhất của khuôn phản hồi:

- **`du_nguong_6_thang_luong`**, không phải `du_dieu_kien_mien`.
- **`can_kiem_5_nam_lien_tuc: true`** luôn có mặt.

Điều kiện miễn cùng chi trả gồm **hai vế**: tham gia BHYT đủ 5 năm liên tục **và** số tiền
cùng chi trả lũy kế lớn hơn 6 tháng lương cơ sở. Hàm MCCT của cổng **không trả về** vế thứ
nhất. Một tên trường như `du_dieu_kien_mien` sẽ được bên gọi hiển thị thẳng cho cán bộ là
"đủ điều kiện" — đúng cái sai vừa được sửa trên màn hình qlbv, giờ tái diễn ở một hệ thống
mình không kiểm soát được.

Ghi chú về `ghi_chu`: giữ **nguyên văn** chuỗi cổng trả về. Nó chứa mốc "dữ liệu tính đến
…", **khác** với `tra_luc` (thời điểm qlbv hỏi cổng). Hai mốc này phải đến tay bên gọi tách
bạch; gộp lại là cách chắc chắn khiến họ hiểu sai độ tươi của số liệu.

### 5.2. Chưa từng tra thẻ này

```json
{ "success": true, "data": null,
  "meta": { "trang_thai": "chua_tra_lan_nao", "timestamp": "...", "request_id": "..." } }
```

Không có dữ liệu **không phải lỗi**. Trả `success: true` với `data: null` để bên gọi không
phải phân biệt "lỗi thật" với "chưa có gì".

### 5.3. Bỏ qua `lam_moi` vì còn trong khẩu độ

```json
{ "success": true,
  "data": { "...": "bản đã lưu, nguon = da_luu" },
  "meta": { "bo_qua_lam_moi": true, "lam_moi_duoc_sau": 480, "timestamp": "...", "request_id": "..." } }
```

Đây là **trả dữ liệu, không phải lỗi**. Một vòng lặp hỏng ở bên gọi sẽ nhận dữ liệu hợp lệ
chứ không nhận `429` — nên nó không sinh thêm vòng thử lại, và không tự khuếch đại sự cố.

### 5.4. Lỗi

Dùng nguyên khuôn `loiApi()` sẵn có, không lộ thông điệp ngoại lệ ra ngoài:

```json
{ "success": false,
  "error": { "code": "...", "message": "...", "details": "..." },
  "meta": { "...": "..." } }
```

| HTTP | `code` | Khi nào |
|---|---|---|
| 401 | *(của `ApiAuthMiddleware`)* | Thiếu / sai token |
| 422 | `VALIDATION_ERROR` | Thiếu `ma_the`, sai độ dài, hoặc `lam_moi=1` mà thiếu ba tham số kia |
| 502 | `GATEWAY_ERROR` | Cổng trả `400`/`500`, hoặc token cổng hỏng |
| 504 | `GATEWAY_TIMEOUT` | Cổng không trả lời trong `timeout_tong` |
| 429 | *(của `throttle`)* | Vượt hạn mức request |
| 500 | `INTERNAL_ERROR` | Lỗi phía qlbv — kể cả **cơ sở chưa khai tài khoản cổng BHXH**. Đây là cấu hình thiếu của qlbv, không phải cổng hỏng; trả `502` sẽ đẩy bên gọi đi hỏi nhầm phía BHXH |

Riêng mã `204` của cổng (không tìm thấy dữ liệu) **không phải lỗi**: trả `success: true`,
`data` có `luy_ke_cung_chi_tra = 0` và `chi_tiet = []`, kèm `meta.ma_ket_qua_cong = "204"`.

## 6. Chống bùng nổ lượt gọi lên cổng

`lam_moi=1` cho cùng một `ma_the` trong vòng `config('mcct.khoang_cho_lam_moi')` (mặc định
**900 giây**) → **không** gọi cổng, trả bản đã lưu kèm `meta.bo_qua_lam_moi` và
`meta.lam_moi_duoc_sau`.

**Không cần bảng mới.** Cột `tra_luc` trong `mcct_tra_cuu` đã đủ. Tính theo bản ghi gần nhất
của mã thẻ đó **bất kể mã kết quả** — một lần trả `204` cũng đã tiêu một lượt gọi.

Cột `nguon` từ giai đoạn 1 đã chừa sẵn giá trị **`api_his`**; giai đoạn này mới dùng đến.
Nhờ vậy phân biệt được lượt gọi từ HIS với lượt tra tay trên màn khi đối soát.

## 7. Kiến trúc

**Tệp mới**

```
app/Services/Mcct/McctTraCuuChung.php        # loi dung chung cho web va API
app/Services/Mcct/QuyetDinhGoiCong.php       # ham THUAN: co goi cong hay khong
app/Http/Controllers/Api/McctApiController.php
tests/Unit/Mcct/QuyetDinhGoiCongTest.php
tests/Feature/McctApiTest.php
```

**Tệp sửa**

- `routes/api.php` — thêm một route vào nhóm `['throttle:60,1', 'api.auth']` sẵn có.
- `config/mcct.php` — thêm `khoang_cho_lam_moi`.
- `app/Http/Controllers/Insurance/Manager/McctController.php` — bỏ `thucHien()` riêng, gọi
  `McctTraCuuChung`.

### Ranh giới giữa các lớp

- `QuyetDinhGoiCong` là **hàm thuần**: nhận thời điểm lần tra gần nhất, thời điểm hiện tại,
  khẩu độ, và cờ `lam_moi`; trả về có gọi cổng hay không cộng số giây còn lại. Không chạm
  CSDL, không chạm mạng — kiểm được đầy đủ.
- `McctTraCuuChung` ghép: đọc bản ghi gần nhất → hỏi `QuyetDinhGoiCong` → gọi cổng nếu cần →
  tính ngưỡng → lưu vết. Đây là nơi **duy nhất** định nghĩa "một lần tra cứu nghĩa là gì".
- `McctApiController` chỉ đổi kết quả của `McctTraCuuChung` thành khuôn JSON của API. Không
  chứa logic nghiệp vụ.
- `McctController` (web) cũng chỉ gọi `McctTraCuuChung`.

## 8. Điều bên gọi phải biết

Ghi vào tài liệu bàn giao, vì nó quyết định họ viết đúng hay sai:

- `lam_moi=0` trả về trong **dưới 100ms** → đặt timeout ngắn phía họ.
- `lam_moi=1` có thể mất **tới 60 giây** → timeout phía họ phải **≥ 70 giây**. Đặt 30 giây
  (mặc định của phần lớn thư viện HTTP) là tự cắt giữa chừng, và lượt gọi cổng vẫn bị tiêu.
- Luồng khuyến nghị lúc tiếp đón: gọi `lam_moi=0` trước để hiện ngay; chỉ gọi `lam_moi=1`
  khi cán bộ chủ động yêu cầu.
- `du_nguong_6_thang_luong = true` **chưa** đủ để kết luận người bệnh được miễn cùng chi
  trả. Còn phải kiểm điều kiện tham gia BHYT đủ 5 năm liên tục — cờ `can_kiem_5_nam_lien_tuc`
  có mặt để nhắc điều đó ở mỗi phản hồi.

## 9. Kiểm thử

| Bộ | Kiểm gì |
|---|---|
| `QuyetDinhGoiCongTest` | Hàm thuần: `lam_moi=0` → không gọi; `lam_moi=1` chưa từng tra → gọi; `lam_moi=1` vừa tra 5 phút trước, khẩu độ 15 phút → không gọi, còn lại 600 giây; đúng biên khẩu độ; khẩu độ bằng 0 (tắt chặn) → luôn gọi |
| `McctApiTest` | Thiếu token → 401; thiếu `ma_the` → 422 đúng khuôn; `ma_the` sai độ dài → 422; `lam_moi=1` thiếu `ho_ten` → 422; chưa từng tra + `lam_moi=0` → `data: null` và `meta.trang_thai`; có bản ghi → đúng khuôn `data` với đủ tên trường, đặc biệt `du_nguong_6_thang_luong` và `can_kiem_5_nam_lien_tuc` |

Ràng buộc bám chốt an toàn CSDL của dự án: **không dùng `RefreshDatabase`**, không trỏ vào
CSDL `qlbv`; test tự dọn theo id nó tạo ra.

**Không có test nào gọi cổng thật.** `McctApiTest` chỉ chạy nhánh `lam_moi=0` và các nhánh
validate — nhánh gọi cổng đã được `McctXacThucTest` phủ ở giai đoạn 1 bằng
`Guzzle MockHandler`. Bộ test của dự án này đã một lần vô tình gửi request đăng nhập thật
lên cổng sản xuất; không lặp lại.

## 10. Ngoài phạm vi

- Webhook / hàng đợi bất đồng bộ để qlbv chủ động báo HIS khi có số mới.
- Token riêng cho từng hệ thống gọi. Hiện `api.auth` dùng một token chung; khi có hệ thống
  thứ hai gọi vào và cần tách log hay hạn mức thì mới làm.
- Tra hàng loạt và báo cáo đối soát — đó là Giai đoạn 2.
