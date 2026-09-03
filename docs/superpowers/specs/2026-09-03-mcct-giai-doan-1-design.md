# Thiết kế: Tra cứu tiền cùng chi trả / miễn cùng chi trả (MCCT) — Giai đoạn 1

Ngày: 2026-09-03
Trạng thái: đã thống nhất, chờ lập kế hoạch thực thi

## 1. Mục tiêu

Cho phép cơ sở KCB tra cứu **lũy kế số tiền cùng chi trả BHYT trong năm tài chính** của
người bệnh trên Cổng tiếp nhận dữ liệu thuộc Hệ thống thông tin giám định BHYT, để xác
định mức hưởng BHYT — cụ thể là xác định người bệnh đã đủ điều kiện **miễn cùng chi trả**
hay chưa.

Căn cứ pháp lý: điểm b khoản 2 Điều 18 Nghị định số 188/2025/NĐ-CP ngày 01/7/2025.

Tài liệu gốc:

- Công văn số 1839/CNTT-PM ngày 18/8/2026 của Trung tâm CNTT và Chuyển đổi số, BHXH Việt
  Nam — *V/v bổ sung hàm API tra cứu lũy kế số tiền cùng chi trả của người bệnh*. Hàm đã
  được đưa lên **môi trường chính thức**.
- Phụ lục *Mô tả chi tiết dịch vụ tra cứu tiền cùng chi trả / miễn cùng chi trả (MCCT)*
  ban hành kèm công văn trên.

## 2. Phạm vi

Toàn bộ nhu cầu đã thống nhất gồm bốn phần, tách làm ba giai đoạn. **Tài liệu này chỉ đặc
tả Giai đoạn 1.**

| GĐ | Nội dung | Kết quả |
|---|---|---|
| **1** (tài liệu này) | Service gọi cổng, xác thực header, xử lý 401, hai bảng lưu vết, ngưỡng miễn cùng chi trả, **màn tra cứu thủ công** | Tra được bằng tay, có lịch sử; chứng minh hàm chạy thật trên môi trường chính thức |
| 2 | Tra cứu hàng loạt (nguồn: HIS theo khoảng ngày **và** hồ sơ XML 3176/4750 đã nạp), màn danh sách + báo cáo đối soát + xuất Excel | Đối soát cả kỳ, lọc người đủ ngưỡng |
| 3 | `GET /api/mcct/tra-cuu` cho HIS gọi lúc tiếp đón/thanh toán, có cache + throttle | HIS xác định mức hưởng ngay tại quầy |

Thứ tự này bắt buộc: GĐ2 và GĐ3 đều dùng lại nguyên service và hai bảng của GĐ1. Làm ngược
lại thì phải viết tầng gọi cổng hai lần.

**Ngoài phạm vi Giai đoạn 1**, ghi lại để không quên:

- Quét mã QR thẻ BHYT trên màn MCCT (màn tra cứu thẻ đã có sẵn luồng này; GĐ1 nhập tay).
- Sinh danh sách đề nghị cấp giấy chứng nhận không cùng chi trả trong năm — thuộc GĐ2.
- Gộp `App\BHYT::checkInsuranceCard()` và hàm mới vào một client cổng BHXH chung. Đây là
  refactor không liên quan tới mục tiêu, và động vào sáu điểm gọi đang chạy sản xuất.
- Bổ sung retry-on-401 cho các hàm cổng cũ (Trục / BHYT / TT12). GĐ1 chỉ làm cho MCCT.

## 3. Đặc tả dịch vụ của cổng (trích phụ lục)

| Mục | Nội dung |
|---|---|
| URL | `POST http://egw.baohiemxahoi.gov.vn/api/TraCuuCCT/TraCuuTienMCCT` |
| Content-Type | `application/json`, charset `utf-8` |
| Xác thực | Ba **HTTP header**: `accessToken`, `tokenId`, `passwordHash` |
| Body | `{ username, maThe, hoTen, ngaySinh }` |
| Phản hồi | `{ MaKetQua, GhiChu, DataCCT[], ThongTinSoThe }` |

Cổng đọc token trong Redis theo khoá `TOKEN-{tokenId}`, tính lại chữ ký HMACSHA256 từ *tài
khoản – địa chỉ IP bên gọi – tokenId – dấu thời gian* rồi đối chiếu với `accessToken`, đồng
thời kiểm tra token còn trong hạn (`SessionTimeLimit`, mặc định **10 phút**). **Địa chỉ IP
gọi dịch vụ phải trùng địa chỉ IP đã dùng khi lấy token.**

Ràng buộc đầu vào:

- `maThe`: sau khi bỏ khoảng trắng, độ dài hợp lệ là **10, 12 hoặc 15** ký tự.
- `ngaySinh`: chấp nhận ba định dạng — `dd/MM/yyyy` (10 ký tự), `MM/yyyy` (7 ký tự), `yyyy`
  (4 ký tự).
- `hoTen`: cổng tự loại bỏ khoảng trắng thừa trước khi đối chiếu.
- `username`: "tài khoản người dùng thực hiện tra cứu; dùng để ghi nhận phiên tra cứu và
  kiểm soát danh sách tài khoản bị hạn chế tra cứu".

`DataCCT[]` — mỗi phần tử là một đợt KCB, **đã sắp giảm dần theo ngày ra viện**, các trường
ngày đã được cổng định dạng sẵn thành chuỗi `dd/MM/yyyy`, không có giá trị thì trả chuỗi
rỗng:

| Trường | Ý nghĩa |
|---|---|
| `Id` | Mã định danh bản ghi hồ sơ KCB |
| `ngayTraCuu` | Ngày thực hiện tra cứu |
| `maThe`, `maCskcb` | Mã thẻ; mã CSKCB nơi phát sinh đợt KCB |
| `ngayVao`, `ngayRa` | Ngày vào viện, ngày ra viện |
| `maDoiTuongKCB` | Mã đối tượng khi đi KCB |
| `tBNCCTMCCT` | Số tiền cùng chi trả thuộc diện được miễn trong đợt KCB |
| `tBNCCTLuyKe` | **Số tiền người bệnh cùng chi trả lũy kế** — dùng để xét ngưỡng |
| `ngayNhanCong`, `ngayNhan` | Ngày nhận hồ sơ qua Cổng tiếp nhận; ngày nhận hồ sơ |
| `duPhong1`…`duPhong5` | Dự phòng, luôn là chuỗi rỗng — **bỏ qua, không lưu** |

`ThongTinSoThe`: `hoTen`, `ngaySinh`, `ngayKetThuc` (ngày hết hạn thẻ), `maBhxh`, kèm năm
trường dự phòng luôn rỗng.

Mã kết quả — **`MaKetQua` là căn cứ, mã HTTP chỉ tham khảo** (không tìm thấy dữ liệu vẫn
trả HTTP 200):

| HTTP | `MaKetQua` | Nghĩa |
|---|---|---|
| 200 | `200` | Thành công, có dữ liệu. `GhiChu` ghi nguồn và mốc thời gian dữ liệu |
| 200 | `204` | Không tìm thấy thông tin thẻ, **hoặc** tìm thấy thẻ nhưng không có chi phí cùng chi trả. `DataCCT` và `ThongTinSoThe` đều `null` |
| 400 | `400` | Thiếu tham số bắt buộc, hoặc `maThe` sai độ dài |
| 401 | *(rỗng)* | Token sai/hết hạn, hoặc gọi từ IP khác IP đã cấp token. **Phản hồi không kèm nội dung** |
| 500 | `500` | Hai trường hợp khác nhau, phân biệt bằng `GhiChu`: "Có lỗi xảy ra trong quá trình tra cứu!" = **tài khoản thuộc danh sách hạn chế tra cứu**; "Có lỗi hệ thống xảy ra trong quá trình xử lý!" = lỗi hệ thống |

## 4. Quyết định đã chốt

1. **Service riêng, không đụng `App\BHYT`.** Tầng gọi cổng đặt ở `app/Services/Mcct/`, dùng
   lại `BHYTLoginService` sẵn có. `App\BHYT` là lớp static có sáu điểm gọi đang chạy sản
   xuất và đang mang sẵn vết "rơi về tài khoản chốt cứng" phải vá bằng tham số
   `$loginService` tuỳ chọn; thêm hàm vào đó là làm sâu thêm vết đó và không test được nếu
   không gọi mạng thật.
2. **Không thêm chỗ khai tài khoản thứ hai.** Tài khoản, mật khẩu, cán bộ tra cứu vẫn lấy
   từ `config('organization.BHYT_CO_SO')` theo mã cơ sở. `passwordHash` chính là mật khẩu
   MD5 đã khai ở đó — thêm accessor `passwordHash()` vào `BHYTLoginService`, không sửa
   luồng đăng nhập.
3. **Hằng số giao thức tách khỏi tham số cơ sở**, theo đúng tiền lệ `config/tt12.php`: URL,
   số tháng lương cơ sở và bảng lương cơ sở nằm ở `config/mcct.php`; tài khoản nằm ở
   `config/organization.php`.
4. **Chạy thẳng trên môi trường chính thức** (`egw.baohiemxahoi.gov.vn`). URL vẫn đọc từ
   config để đổi sang `daotaoegw` được khi cần dò lỗi.
5. **Retry đúng một lần, chỉ với 401.** Phiên 10 phút và khoá theo IP nên 401 dễ gặp hơn
   hẳn luồng cũ. Lỗi mạng/timeout **không** retry: cổng có danh sách tài khoản bị hạn chế
   tra cứu, tự nhân đôi lượt gọi là tự chuốc lấy nó.
6. **Lưu mọi lần tra, kể cả lần hỏng** (`204`, `400`, `401`, `500`). Mất dấu vết lần hỏng
   là mất đúng thứ cần khi đi hỏi cổng.
7. **Ngưỡng dùng dấu `>`**, không phải `≥`. NĐ 188/2025 dùng câu chữ "lớn hơn 6 tháng lương
   cơ sở": lũy kế **bằng đúng** ngưỡng là *chưa* đủ điều kiện.
8. **Lương cơ sở khai theo mốc hiệu lực**, không phải một số. Ngưỡng = 6 × giá trị có hiệu
   lực tại ngày tra. Khai một số trần thì lần tăng lương tiếp theo sẽ lặng lẽ tính sai
   ngưỡng cho toàn bộ dữ liệu cũ.
9. **`nguong_ap_dung` lưu vào bảng**, không tính lại lúc đọc. Tính lại nghĩa là mọi bản ghi
   cũ đột ngột đổi kết luận "đủ / chưa đủ" mỗi khi lương cơ sở tăng — dữ liệu đối soát năm
   trước tự viết lại chính nó.
10. **Màn riêng, không làm tab trong màn tra cứu thẻ.** Form của `check-card` có ràng buộc
    đã ghi chú trong mã: ô chọn cơ sở phải nằm trong form vì luồng quét QR tự gọi
    `$('#target').submit()`. Thêm form thứ hai vào đó là cách chắc chắn làm hỏng luồng quét
    QR đang chạy.

## 5. Điểm chưa chắc chắn — phải nghiệm thu thật

**Trường `username` trong body.** Phụ lục nêu ví dụ `"username": "01001"`, trông như **mã
CSKCB**; nhưng phần đặc tả lại mô tả là "tài khoản người dùng thực hiện tra cứu". Tài khoản
của cơ sở trong hệ thống này là dạng `01929_BV`.

Thiết kế chọn gửi `$loginService->username()` (tài khoản đăng nhập thật), vì cổng đối chiếu
tài khoản với token. **Nếu cổng từ chối thì đổi sang mã CSKCB** — sửa một dòng. Đây là mục
nghiệm thu bắt buộc ở mục 10, không mock được và không được coi test xanh là xong.

## 6. Kiến trúc

**Tệp mới**

```
config/mcct.php                              # URL, số tháng, bảng lương cơ sở theo mốc
app/Services/Mcct/McctTraCuuService.php      # gọi cổng: header + JSON, xử lý 401
app/Services/Mcct/KetQuaMcct.php             # DTO kết quả, thay cho mảng thô
app/Services/Mcct/NguongMienCungChiTra.php   # hàm THUẦN: ngày → lương cơ sở → ngưỡng
app/Services/Mcct/McctLuuTraCuu.php          # lưu phiên tra + các dòng chi phí
app/Models/Mcct/McctTraCuu.php
app/Models/Mcct/McctChiPhi.php
app/Http/Controllers/Insurance/Manager/McctController.php
app/Http/Requests/McctRequest.php
resources/views/insurance/manager/mcct/index.blade.php
resources/views/insurance/manager/mcct/search.blade.php
resources/views/insurance/manager/mcct/result.blade.php
database/migrations/…_create_mcct_tra_cuu_table.php
database/migrations/…_create_mcct_chi_phi_table.php
```

**Tệp sửa**

- `app/Services/BHYTLoginService.php` — thêm `passwordHash()` cạnh `username()` /
  `password()` đã có. Không đổi hành vi cũ.
- `routes/web.php`, `routes/breadcrumbs.php`, cấu hình menu — thêm màn MCCT.
- `resources/views/insurance/manager/check-card/result.blade.php` — thêm **một liên kết**
  "Tra tiền cùng chi trả" mang sẵn cơ sở / mã thẻ / họ tên / ngày sinh sang màn mới. Chỉ
  một thẻ `<a>`, không đụng form.

**Không đụng**: `app/BHYT.php` và sáu điểm gọi `checkInsuranceCard` (2 Job, 3 Controller).

### `config/mcct.php`

```php
return [
    'tra_cuu_url' => 'http://egw.baohiemxahoi.gov.vn/api/TraCuuCCT/TraCuuTienMCCT',
    'so_thang_luong_co_so' => 6,
    // Mốc hiệu lực => mức lương cơ sở. Sắp tăng dần theo ngày.
    'luong_co_so' => [
        '2023-07-01' => 1800000,
        '2024-07-01' => 2340000,
    ],
    'timeout_ket_noi' => 10,
    'timeout_tong' => 30,
];
```

### Luồng một lần tra

1. `McctController@search` → `McctRequest` validate:
   - `ma_cskcb` phải thuộc `CoSoTraCuu::maDangChuoi()`;
   - `maThe` sau khi bỏ khoảng trắng phải dài 10 / 12 / 15;
   - `hoTen` bắt buộc;
   - `ngaySinh` khớp một trong ba định dạng `dd/MM/yyyy`, `MM/yyyy`, `yyyy`.

   Chặn tại đây, không để cổng trả `400`: vừa tiết kiệm lượt gọi (cổng có danh sách hạn chế
   tra cứu), vừa báo lỗi đúng chỗ sai.
2. `new McctTraCuuService($maCskcb)` → bên trong dựng `BHYTLoginService($maCskcb)`, lấy
   `accessToken`, `idToken` (dùng làm header `tokenId`), `passwordHash`.
3. `POST` Guzzle: ba header + body JSON bốn trường.
4. HTTP `401` → `logout()` xoá cache token của cơ sở đó → đăng nhập lại → gọi lại **đúng
   một lần**. Lần hai vẫn `401` thì dừng và báo lỗi phân biệt được.
5. Đọc `MaKetQua` (**không** đọc HTTP status) → dựng `KetQuaMcct`.
6. `McctLuuTraCuu::luu()` — ghi một dòng `mcct_tra_cuu` và các dòng `mcct_chi_phi`, với
   **mọi** mã kết quả.
7. `NguongMienCungChiTra` tính ngưỡng theo ngày tra → trả view.

### Ranh giới giữa các lớp

- `NguongMienCungChiTra` là **hàm thuần**: nhận ngày và số tiền lũy kế, trả ngưỡng và kết
  luận. Không đọc config trực tiếp, không chạm CSDL, không chạm mạng — nhận bảng lương cơ
  sở làm tham số, giống cách `CoSoTraCuu` nhận `$dsCoSo` và `$nhanHis`.
- `KetQuaMcct` chỉ mang dữ liệu đã phân tích từ JSON, không biết gì về CSDL.
- `McctLuuTraCuu` chỉ biết `KetQuaMcct` và Model, không chạm mạng.
- `McctTraCuuService` là nơi **duy nhất** chạm mạng.

Nhờ vậy bốn trong sáu bộ test ở mục 9 không cần mạng và không cần CSDL.

## 7. Lược đồ cơ sở dữ liệu (MySQL)

### `mcct_tra_cuu` — một dòng mỗi lần bấm tra

| Cột | Kiểu | Ghi chú |
|---|---|---|
| `id` | increments | |
| `ma_cskcb` | string(10), index | Cơ sở dùng tài khoản để tra |
| `ma_the` | string(20), index | Tham số đã gửi, đã chuẩn hoá |
| `ho_ten` | string | |
| `ngay_sinh` | string(10) | Giữ nguyên dạng đã gửi (có thể là `yyyy`) |
| `ma_ket_qua` | string(10), index | `200` / `204` / `400` / `401` / `500` |
| `ghi_chu` | text nullable | **Nguyên văn `GhiChu`** — chứa mốc "dữ liệu tính đến …" |
| `the_ho_ten` | string nullable | Từ `ThongTinSoThe` |
| `the_ngay_sinh` | string(10) nullable | |
| `the_ngay_ket_thuc` | date nullable | Ngày hết hạn thẻ |
| `the_ma_bhxh` | string(15) nullable, index | |
| `luy_ke_lon_nhat` | decimal(15,2) nullable | Max `tBNCCTLuyKe` trong `DataCCT` |
| `nguong_ap_dung` | decimal(15,2) nullable | Ngưỡng **tại thời điểm tra** |
| `du_dieu_kien_mien` | boolean nullable | `luy_ke_lon_nhat > nguong_ap_dung` |
| `nguon` | string(20) | `thu_cong` \| `hang_loat` \| `api_his` — chừa sẵn GĐ2/3 |
| `tra_boi` | string nullable | Tài khoản người dùng trong hệ thống |
| `tra_luc` | timestamp nullable | |
| `timestamps` | | |

### `mcct_chi_phi` — các dòng `DataCCT` của phiên đó

| Cột | Kiểu | Ghi chú |
|---|---|---|
| `id` | increments | |
| `tra_cuu_id` | unsignedInteger, index | Khoá ngoại về `mcct_tra_cuu` |
| `id_cong` | bigint nullable, index | Chính là `Id` cổng trả về |
| `ma_the` | string(20) | |
| `ma_cskcb` | string(10) | Nơi phát sinh đợt KCB |
| `ngay_vao`, `ngay_ra` | date nullable | |
| `ma_doi_tuong_kcb` | string(10) nullable | |
| `t_bn_cct_mcct` | decimal(15,2) | |
| `t_bn_cct_luy_ke` | decimal(15,2) | |
| `ngay_nhan_cong`, `ngay_nhan`, `ngay_tra_cuu` | date nullable | |
| `timestamps` | | |

Ba quyết định có lý do, không phải mặc định:

- **Ngày lưu kiểu `date`**, không giữ chuỗi `dd/MM/yyyy`; chuỗi rỗng → `null`. GĐ2 cần lọc
  và sắp theo ngày ra viện.
- **Tiền dùng `decimal(15,2)`**, không `float`.
- **Không đặt unique `(ma_the, id_cong)`**: mỗi lần tra là một ảnh chụp mới, trùng `id_cong`
  giữa các phiên là chuyện đương nhiên.

Năm trường `duPhong1`…`duPhong5` của cả hai đối tượng **không lưu** — phụ lục ghi rõ chúng
luôn là chuỗi rỗng.

## 8. Giao diện

Màn riêng: route `insurance.mcct` (GET, form rỗng) và `insurance.mcct.search` (GET, tra
cứu), cùng nhóm `checkrole` như các màn BHYT khác.

Bố cục từ trên xuống:

1. **Ô tra cứu** — chọn cơ sở (`CoSoTraCuu::danhSach()`, dùng lại nguyên), mã thẻ, họ tên,
   ngày sinh, nút Tra cứu. Chưa nhập được cơ sở thì dừng ở màn đã điền sẵn và nhắc chọn cơ
   sở, **không** báo lỗi — giống hành vi đang có ở `InsuranceController@search`: người dùng
   không làm gì sai, và cũng chưa biết dùng tài khoản của cơ sở nào để gọi.
2. **Thông tin thẻ** — `hoTen`, `ngaySinh`, `ngayKetThuc`, `maBhxh`.
3. **Khối kết luận**, đặt nổi bật:
   - Lũy kế cùng chi trả: `12.500.000 đ`
   - Ngưỡng 6 tháng lương cơ sở: `14.040.000 đ`
   - Nhãn: **ĐỦ ĐIỀU KIỆN MIỄN CÙNG CHI TRẢ** (xanh) hoặc **CÒN THIẾU 1.540.000 đ** (vàng)
   - Ngay dưới, chữ nhỏ: **nguyên văn `GhiChu`**. Bắt buộc hiển thị, không rút gọn — số
     liệu cổng có độ trễ, người dùng phải thấy mốc "tính đến …" trước khi kết luận với
     người bệnh.
4. **Bảng chi tiết đợt KCB** — mã CSKCB, ngày vào, ngày ra, đối tượng, tiền CCT thuộc diện
   miễn, lũy kế, ngày nhận. **Giữ nguyên thứ tự cổng trả** (đã giảm dần theo ngày ra viện),
   không sắp lại.
5. **Lịch sử tra cứu thẻ này** — vài lần gần nhất đọc từ `mcct_tra_cuu`, để thấy số lũy kế
   đã thay đổi thế nào.

Tiền định dạng `number_format($x, 0, ',', '.')` kèm `đ`.

## 9. Xử lý lỗi

| Tình huống | Hiện cho người dùng |
|---|---|
| `200` | Hiển thị đầy đủ như mục 8 |
| `204` | Vàng: "Không tìm thấy dữ liệu. Có thể do sai thông tin thẻ, **hoặc** thẻ chưa phát sinh chi phí cùng chi trả." Cổng dùng chung một mã cho hai chuyện khác nhau; nói gộp thành "thẻ sai" là đẩy người dùng đi sửa cái không sai |
| `400` | Đỏ, kèm `Log::warning` ghi body đã gửi, **che `passwordHash`**. Đáng lẽ không xảy ra vì đã validate — xảy ra tức là luật validate lệch với cổng |
| `401` sau khi đã thử lại một lần | Đỏ: "Không xác thực được với cổng BHXH. Kiểm tra: phiên hết hạn, hoặc **IP máy chủ gọi khác IP lúc lấy token** (cổng khoá theo IP)." Nêu thẳng nguyên nhân hay gặp nhất, vì phản hồi 401 của cổng có thân rỗng |
| `500` + `GhiChu` "Có lỗi xảy ra trong quá trình tra cứu!" | Đỏ riêng: "Tài khoản {username} của cơ sở {mã} đang bị cổng hạn chế tra cứu." Đây là vấn đề tài khoản; gộp vào "lỗi hệ thống" sẽ dẫn người ta đi dò nhầm hướng hàng giờ |
| `500` khác | Đỏ: "Cổng báo lỗi hệ thống, thử lại sau." |
| Timeout / không nối được | Đỏ: "Không kết nối được cổng BHXH." **Không tự thử lại** |
| Cơ sở chưa khai tài khoản (`InvalidArgumentException` từ `CauHinhCoSo`) | Đỏ, nói rõ khai ở `config/organization.php`, khối `BHYT_CO_SO` — cùng giọng với thông báo đã có ở `InsuranceController` |

Timeout: kết nối 10s, tổng 30s (đọc từ `config/mcct.php`).

Nhật ký: mỗi lần gọi ghi `Log::info` gồm `ma_cskcb`, `ma_the`, `ma_ket_qua`, thời gian.
**Không bao giờ** ghi `accessToken` hay `passwordHash` vào log.

## 10. Kiểm thử

Viết test trước (TDD). Sáu bộ; bốn bộ đầu không chạm mạng và không chạm CSDL.

| Bộ | Kiểm gì |
|---|---|
| `NguongMienCungChiTraTest` | Lương cơ sở theo mốc hiệu lực; ngày trước mốc đầu tiên; **đúng ngày đổi lương**; ba trường hợp dưới / **bằng** / trên ngưỡng — bằng đúng ngưỡng phải cho kết quả *chưa* đủ (quyết định 7) |
| `McctPhanTichKetQuaTest` | JSON mẫu **đúng như phụ lục** → DTO đúng; `204` với `DataCCT: null` và `ThongTinSoThe: null` không nổ; chuỗi ngày rỗng → `null`; số tiền dạng chuỗi → decimal; `duPhong*` bị bỏ qua |
| `McctXacThucTest` | Dùng **Guzzle `MockHandler`**: gửi đúng ba header đúng tên; body JSON đúng bốn trường; `401` lần đầu → làm mới token và gọi lại lần hai; `401` lần hai → ném lỗi phân biệt được; lỗi mạng **không** sinh lần gọi thứ hai |
| `McctValidateTest` | Mã thẻ 9/11/13 ký tự bị chặn, 10/12/15 qua; ba định dạng ngày sinh qua, `1/1/1990` bị chặn; mã cơ sở ngoài `BHYT_CO_SO` bị chặn |
| `McctLuuTraCuuTest` | Lưu đủ dòng chi phí; **lưu cả khi `204`** (có phiên, không có dòng nào); `nguong_ap_dung` được ghi vào bảng chứ không tính lại khi đọc |
| `RouteMcctTest`, `MenuMcctTest` | Route tồn tại và có `checkrole`; menu hiện — theo mẫu `RouteOrderCheckTest` / `MenuOrderCheckTest` sẵn có |

Ràng buộc bám chốt an toàn CSDL của dự án: **không dùng `RefreshDatabase`**, không trỏ vào
CSDL `qlbv`. Chỉ đúng một bộ (`McctLuuTraCuuTest`) chạm CSDL.

Dùng `MockHandler` của Guzzle chứ không dùng Mockery cho tầng HTTP — Mockery đã nhiều lần
vỡ với các lớp có khai báo kiểu trả về trong dự án này.

**Nghiệm thu tay — không test nào thay được:**

1. Gọi thật một thẻ **có phát sinh chi phí** trên môi trường chính thức.
2. Xác nhận cổng chấp nhận `username` là tài khoản đăng nhập (`01929_BV`) chứ không đòi mã
   CSKCB (mục 5). Nếu bị từ chối thì đổi sang mã CSKCB và gọi lại.
3. Xác nhận IP máy chủ qlbv không bị cổng từ chối (`401`).
4. Ghi lại nguyên văn `GhiChu` cổng trả về, đối chiếu với số lũy kế hiển thị trên màn.

Chỉ khi bốn bước này xong mới coi Giai đoạn 1 là hoàn thành.
