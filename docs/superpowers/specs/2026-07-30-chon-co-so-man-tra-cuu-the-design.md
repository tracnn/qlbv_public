# Chọn cơ sở KCB trên màn tra cứu thẻ BHYT thủ công

Ngày: 2026-07-30

## Mục tiêu

Màn tra cứu thẻ BHYT thủ công (`/insurance/check-card`) hiện tra bằng **một tài khoản cổng
BHXH chốt cứng**. Hệ thống phục vụ nhiều cơ sở KCB, mà hồ sơ của cơ sở nào phải tra bằng tài
khoản của cơ sở đó mới hợp lệ.

Thêm ô chọn cơ sở trước khi tra, nhớ lựa chọn vào `localStorage`, lần sau mặc định lấy lên.

## Hiện trạng đo được

`app/Http/Controllers/Insurance/Manager/InsuranceController.php:27` đang dựng
`BHYTLoginService` trong **constructor**, với mã cơ sở mượn tạm từ
`config('organization.correct_facility_code')[0]` — giá trị `01013`.

Hai vấn đề:

1. `01013` **không có** trong `BHYT_CO_SO` (đang khai `01929`, `37470`, `01283`), nên màn này
   sẽ báo "chưa khai tài khoản cổng BHXH cho cơ sở 01013".
2. `correct_facility_code` vốn mang nghĩa **"nơi ĐKBĐ đúng tuyến"** — chú thích trong config
   ghi đúng như vậy, và `Qd130Xml1Checker`, `Xml3176Xml1Checker`, `Qd130Xml3Checker` dùng
   đúng nghĩa đó. Mượn nó làm "mã cơ sở của chính bệnh viện" là lẫn hai khái niệm.

Việc này gỡ luôn cả hai: mã cơ sở đến từ lựa chọn của người dùng, không mượn của ai.

`config('organization.BHYT.check_by_user')` hiện là `true`, nên `hoTenCb`/`cccdCb` lấy từ
người đang đăng nhập — **không phụ thuộc cơ sở**. Chỉ `username`/`password` cần đổi theo cơ sở.

`config('organization.BHYT.enableCheck')` hiện là `false` — màn này đang tắt.

## Thiết kế

### 1. Danh sách cơ sở: chỉ những cơ sở tra được thật

Hàm thuần mới `App\Services\BHYT\CoSoTraCuu`:

```php
/**
 * @param array $dsCoSo  config('organization.BHYT_CO_SO')
 * @param array $nhanHis DanhSachCoSo::danhSach() - ma => nhan
 * @return array ma => nhan, sap theo ma
 */
public static function danhSach(array $dsCoSo, array $nhanHis)
```

Lấy **khoá của `BHYT_CO_SO`** làm nguồn, gắn nhãn từ HIS nếu có, không có thì hiện mã trần.

Vì sao lấy `BHYT_CO_SO` làm nguồn chứ không lấy danh sách HIS: cơ sở chưa khai tài khoản thì
chọn vào chắc chắn lỗi. Một ô chọn mà có lựa chọn **không bao giờ dùng được** là bẫy người
dùng — họ phải thử mới biết, và thông báo lỗi lúc đó không nói được là do cấu hình thiếu hay
do cổng hỏng.

Cơ sở khai trong config mà HIS không có vẫn hiện (mã trần): nó tra được thật, chỉ là không
lấy được tên đẹp.

**Không dùng lại `resources/views/partials/ma_cskcb.blade.php`.** Partial đó là **bộ lọc**:
có mục "Tất cả cơ sở" và cho phép để rỗng. Ở đây cơ sở là **bắt buộc**. Sửa partial để gánh
cả hai ngữ nghĩa sẽ thêm nhánh điều kiện vào một tệp mà hai màn đang chạy tốt phụ thuộc vào.

### 2. Giao diện và localStorage

Ô `<select name="ma_cskcb">` đặt **bên trong** `<form id="target">` của
`resources/views/insurance/manager/check-card/search.blade.php`, ở hàng trên các ô nhập.

Phải nằm trong form vì luồng quét QR (`$('[name="qrcode"]').on('change')`) tự gọi
`$('#target').submit()` — ô nằm ngoài form sẽ không được gửi kèm.

Khoá `localStorage`: `bhyt_tra_cuu_ma_cskcb`.

- **Khi tải trang:** đọc khoá; **chỉ chọn nếu giá trị đó còn là một `<option>` đang có**.
  Cơ sở bị gỡ khỏi config thì bỏ qua giá trị cũ và để trống. Không chọn bừa một cơ sở khác —
  tra nhầm cơ sở là đúng thứ tính năng này sinh ra để chặn.
- **Khi đổi lựa chọn:** ghi ngay, không đợi bấm tra cứu. Người dùng đổi cơ sở rồi bỏ đi thì
  lần sau vẫn nhớ.
- **Danh sách chỉ có một cơ sở:** chọn sẵn.
- Giá trị vừa gửi lên (`old('ma_cskcb')` hoặc `$params['ma_cskcb']`) **thắng** giá trị
  `localStorage` — kết quả đang hiển thị trên màn phải khớp với ô chọn.

### 3. Đường đi phía máy chủ

`InsuranceController`:

- **Bỏ** việc dựng `BHYTLoginService` trong `__construct()`, và bỏ luôn dòng mượn
  `correct_facility_code`. Dựng trong `search()` với mã cơ sở lấy từ request.
  Làm được vì `BHYTLoginService` đã chuyển sang **phân giải lười** — dựng không ném.
- `checkCard()` và `search()` truyền `$danhSachCoSo` xuống view.
- `$this->searchParams` thêm khoá `ma_cskcb`.

`InsuranceRequest`:

- `ma_cskcb` => `required|in:<các mã hợp lệ>`, danh sách lấy từ `CoSoTraCuu::danhSach()`.

**Kiểm lại ở máy chủ, không tin trình duyệt.** `localStorage` và `<select>` đều sửa được từ
phía người dùng. Mã ngoài danh sách thì báo lỗi và dựng lại màn, **không gọi lên cổng**.

### 4. `app/BHYT.php` — điểm bắt buộc phải sửa

`BHYT::checkInsuranceCard()` hiện đọc `username`/`password` từ khối `BHYT` **cũ**. Không sửa
chỗ này thì người dùng chọn cơ sở nhưng lời gọi vẫn dùng tài khoản chốt cứng — tính năng
trông như chạy mà **không có tác dụng gì**. Đây là kiểu hỏng tệ nhất: im lặng và trông giống
thành công.

Thêm tham số **tuỳ chọn** thứ sáu:

```php
public static function checkInsuranceCard($number, $name, $birthday, $access_token, $id_token,
    BHYTLoginService $loginService = null)
```

Có truyền thì `username`/`password` lấy từ `$loginService->username()`/`password()`; không
truyền thì giữ nguyên hành vi cũ. **5 nơi gọi còn lại không phải đụng** — thay đổi thuần cộng
thêm.

`hoTenCb`/`cccdCb` **không đổi**: `check_by_user = true` nên chúng lấy từ người đăng nhập.

`loginBHYT()` **không đụng** — thuộc spec sau.

## Kiểm thử

Trong `tests/Unit`. Cổng: `vendor/bin/phpunit --testsuite Unit`.

**Hàm thuần** (`CoSoTraCuuTest`):

1. Giao bình thường: `BHYT_CO_SO` có `01929`, `37470`; HIS có nhãn cho cả hai → trả hai mục
   kèm nhãn HIS.
2. Cơ sở khai trong config mà HIS không có → vẫn có mặt, nhãn là mã trần.
3. HIS có cơ sở mà config **không** khai → **không** xuất hiện.
4. `BHYT_CO_SO` rỗng → mảng rỗng.
5. `$nhanHis` rỗng (HIS hỏng, `DanhSachCoSo::doc()` trả `[]`) → vẫn trả đủ các mã, nhãn là mã
   trần. Màn tra cứu phải dùng được khi HIS không đọc được.
6. Kết quả sắp theo mã.

**Validation** (`TraCuuTheChonCoSoTest`):

1. `InsuranceRequest` có luật `required` cho `ma_cskcb`.
2. Mã ngoài danh sách bị luật `in:` từ chối.

**Canh không quay lại lỗi cũ** (quét mã nguồn, dùng `Tests\Support\LocComment`):

1. `InsuranceController` **không còn** chuỗi `correct_facility_code`.
2. `InsuranceController` **không** dựng `BHYTLoginService` trong `__construct` nữa.
3. `search.blade.php` có chuỗi khoá `bhyt_tra_cuu_ma_cskcb`.

**Không test nào gọi cổng BHXH.**

## Nghiệm thu

- `vendor/bin/phpunit --testsuite Unit` xanh, số test tăng đúng phần thêm.
- `CoSoTraCuu::danhSach()` trên cấu hình thật trả đúng `01929`, `37470`, `01283`.

**Giới hạn phải nói rõ:** `enableCheck = false` nên màn này đang tắt. Nghiệm thu được tới mức
danh sách đúng, `localStorage` nhớ đúng, validation chặn đúng, và service nhận đúng mã cơ sở.
**Không** chứng minh được lời gọi thật lên cổng trả về đúng — việc đó cần bật chức năng và gọi
thật lên cổng BHXH, nằm ngoài phạm vi và không được làm trong lúc phát triển.

## Phạm vi không làm

- **Không** bật `enableCheck`.
- **Không** đụng 5 nơi gọi `checkInsuranceCard()` còn lại.
- **Không** đụng `BHYT::loginBHYT()` — spec sau.
- **Không** đụng `correct_facility_code` và ba bộ kiểm XML dùng nó đúng nghĩa "ĐKBĐ đúng
  tuyến". Việc tách khái niệm này thuộc spec sau.
- **Không** sửa `partials/ma_cskcb.blade.php` dùng chung.
- **Không** thêm màn quản lý tài khoản cơ sở — khai trong `config/organization.php` là đủ.
