# Cấu hình cổng BHXH theo từng cơ sở — nền tảng và đường kiểm thẻ

Ngày: 2026-07-30

Đây là **spec thứ nhất trong hai**. Nó dựng nền cấu hình theo cơ sở và chuyển đường **kiểm
tra thẻ BHYT** sang dùng nền đó. Đường **gửi XML lên cổng** là spec thứ hai, dùng lại cùng
nền tảng.

## Mục tiêu

Hệ thống phục vụ nhiều cơ sở KCB, nhưng mọi lời gọi cổng BHXH đang dùng **một** tài khoản
duy nhất chốt cứng của một cơ sở. Hồ sơ của cơ sở nào phải được tra bằng tài khoản của cơ
sở đó mới hợp lệ.

## Hiện trạng đo được

Ba cơ sở đang hoạt động: `01283` (Phòng Y tế cơ quan), `01929` (Bạch Mai), `37470` (Bạch Mai
cơ sở Ninh Bình).

### Mọi trường riêng theo cơ sở đều đang chốt cứng vào 01013

| Trường | Giá trị hiện tại | Số nơi dùng | Hệ quả khi sai |
| --- | --- | --- | --- |
| `username` / `password` | `01013_BV` | 7 | tra cứu không hợp lệ |
| `hoTenCb` / `cccdCb` | chuỗi rỗng | 2 | cổng ghi nhận sai cán bộ tra cứu |
| `ma_tinh` | `01` | 5 (đều là gửi XML) | 37470 ở Ninh Bình, mã tỉnh phải là 37 |
| `correct_facility_code` | `['01013']` | 5 | xem mục dưới |
| `ma_cskcb` | `01013` | **0** | mã chết |

Tài khoản `01013_BV` không khớp bất kỳ mã nào trong ba cơ sở.

### `correct_facility_code` đang gánh hai nghĩa khác hẳn nhau

- **Nghĩa 1 — mã cơ sở của chính mình**: `BHYTXmlSubmitService::getMaCSKCBFromConfig()`
  (`app/Services/BHYTXmlSubmitService.php:140`) lấy phần tử đầu làm mã cơ sở khai khi gửi hồ
  sơ lên cổng. Nghĩa là **mọi** hồ sơ XML, dù của 01929 hay 37470, đều được khai là của
  `01013`.
- **Nghĩa 2 — danh sách mã ĐKBĐ được coi là đúng tuyến**: `Qd130Xml1Checker:36`,
  `Xml3176Xml1Checker:36`, `Qd130Xml3Checker:388` dùng nó làm `specialDKBD`.

Hai nghĩa này tình cờ trùng giá trị ở Bạch Mai nên bị gộp làm một trường. Người dùng đã chốt
ngày 30/07/2026 rằng chúng khác nhau và phải tách.

### Hai nơi dispatch job kiểm thẻ, cả hai cùng lỗi

`JobKtTheBHYT` được dispatch từ **hai** chỗ, và cả hai đều gán nơi ĐKBĐ vào tham số
`maCSKCB`:

- `app/Console/Commands/HISProKiemTraTheBHYT.php:96` — quét hồ sơ nội trú từ HIS.
- `app/Console/Commands/XML4210Import.php:96` — nhập tệp XML 4210, lấy `MA_DKBD` từ XML1.

Đổi tham số của job mà chỉ sửa một nơi sẽ làm nơi kia vỡ. Cả hai phải sửa cùng lượt.

### Job kiểm thẻ đang gửi sai mã cơ sở lên cổng

`app/Console/Commands/HISProKiemTraTheBHYT.php:70` gán
`$maDKBD = $value->tdl_hein_medi_org_code` rồi truyền vào tham số `'maCSKCB'`. Nhưng
`tdl_hein_medi_org_code` là **nơi đăng ký khám chữa bệnh ban đầu của bệnh nhân**, không phải
cơ sở đang điều trị.

Đo trên 45.995 hồ sơ nội trú có thẻ BHYT từ 01/07/2026:

| | |
| --- | --- |
| Số mã ĐKBĐ phân biệt | **4.194** (rải khắp cả nước) |
| Số cơ sở điều trị phân biệt | **2** — `01929`: 39.379, `37470`: 6.616 |
| Hai giá trị trùng nhau | **242 dòng (0,5%)** |

Tức 99,5% lời gọi đang khai sai cơ sở.

### Những thứ khác đã kiểm

- `JobBHYT` và `JobInpatient` là **mã chết** — không nơi nào dispatch. Không đụng tới.
- `check_hein_cards` không có cột nào cho biết bản ghi thuộc cơ sở nào; bảng đang **0 dòng**.
- `BHYTLoginService` lưu token vào **một khoá cache duy nhất** `bhyt_tokens`.

## Thiết kế

### 1. Cấu trúc cấu hình

Giữ cấu trúc trong `config/organization.php` như người dùng chọn, để mỗi đơn vị triển khai
sửa danh sách cơ sở cho khớp của họ. **Giá trị đọc từ `.env`** — đó là điểm khác nhau giữa
mỗi lần cài đặt, và giữ mật khẩu ra ngoài kho mã.

```php
'BHYT_CO_SO' => [
    '01929' => [
        'username' => env('BHXH_01929_USER'),
        'password' => env('BHXH_01929_PASS'),
        'ho_ten_cb' => env('BHXH_01929_HOTEN'),
        'cccd_cb'   => env('BHXH_01929_CCCD'),
    ],
    '37470' => [ /* ... tương tự với hậu tố 37470 ... */ ],
    '01283' => [ /* ... tương tự với hậu tố 01283 ... */ ],
],
```

**`ma_tinh` không khai** — nó luôn là hai ký tự đầu của mã cơ sở (`01283`→`01`, `01929`→`01`,
`37470`→`37`), đúng như chú thích sẵn có trong config. Suy ra thay vì khai giảm một chỗ có
thể khai sai.

Khối `BHYT` cũ giữ lại phần **dùng chung**: các URL cổng, `loai_ho_so_4750`,
`loai_ho_so_3176`, và các công tắc bật/tắt. Xoá `ma_cskcb` (mã chết) khỏi khối đó.

### 2. `correct_facility_code` — không đụng ở spec này

Trường này gánh hai nghĩa (mã cơ sở của mình / danh sách ĐKBĐ đúng tuyến) và cả năm nơi dùng
nó đều thuộc đường gửi XML hoặc ba bộ kiểm XML. Tách nó ra **thuộc spec thứ hai**, nơi kiểm
chứng được cùng lúc với đường tiêu thụ.

Spec này giữ nó **nguyên vẹn** để không đổi hành vi của ba bộ kiểm XML. Không định nghĩa
trước khoá mới ở đây — khoá cấu hình chưa ai đọc là thứ dễ bị khai sai rồi quên.

### 3. Lớp phân giải cấu hình theo cơ sở

`App\Services\BHYT\CauHinhCoSo` — hàm thuần, kiểm thử được:

```php
/** Tra cau hinh cua mot co so; nem InvalidArgumentException neu chua khai */
public static function cua($maCskcb, array $dsCoSo)

/** Ma tinh = hai ky tu dau cua ma co so */
public static function maTinh($maCskcb)
```

`cua()` ném ngoại lệ khi mã rỗng, khi cơ sở chưa khai trong `BHYT_CO_SO`, hoặc khi có khai
nhưng thiếu `username`/`password`. **Không rơi về tài khoản mặc định** — rơi về tài khoản
của cơ sở khác chính là thứ làm kết quả không hợp lệ.

`maTinh()` trả hai ký tự đầu; mã ngắn hơn 2 ký tự → ném ngoại lệ.

### 4. `BHYTLoginService` nhận mã cơ sở

Hàm dựng nhận `$maCskcb`. Đọc tài khoản qua `CauHinhCoSo::cua()`.

**Khoá cache đổi từ `bhyt_tokens` thành `bhyt_tokens:{maCskcb}`.** Không đổi thì token của
cơ sở này ghi đè cơ sở kia, và mọi lời gọi sau đó sai danh nghĩa mà không có dấu hiệu gì —
đây là kiểu hỏng im lặng nguy hiểm nhất của thiết kế cũ.

Để không phá ba nơi đang gọi `new BHYTLoginService()` không tham số, tham số có giá trị mặc
định `null`; khi `null` thì ném ngoại lệ nêu rõ phải truyền mã cơ sở. Ném chứ không đoán:
đoán nghĩa là quay lại đúng lỗi đang sửa.

### 5. Đường kiểm thẻ

**Lệnh quét** (`HISProKiemTraTheBHYT`): thêm `leftJoin('his_branch', 'branch_id')`, lấy
`hein_medi_org_code` làm mã cơ sở thật. Truyền xuống job **hai** giá trị tách bạch:

- `maCskcb` — cơ sở điều trị, từ `his_branch`
- `maDkbd` — nơi ĐKBĐ của bệnh nhân, từ `tdl_hein_medi_org_code`

Hồ sơ không xác định được cơ sở (`branch_id` rỗng hoặc cơ sở chưa khai tài khoản) thì **bỏ
qua và ghi log kèm mã hồ sơ**, không dispatch job. Đếm số hồ sơ bị bỏ qua và in ra cuối lệnh.

Thêm cờ `--thu` cho lệnh: chạy trọn phần quét và dựng tham số nhưng **không dispatch job**,
in ra thống kê mã cơ sở sinh ra. Cờ này phục vụ nghiệm thu ở mục dưới, và về sau dùng lại
được mỗi khi cần kiểm mà không muốn gọi lên cổng BHXH.

**Job** (`jobKtTheBHYT`): dựng `BHYTLoginService` với `maCskcb`; lấy `hoTenCb`/`cccdCb` từ
cấu hình của cơ sở đó; gửi `maCSKCB` = cơ sở điều trị.

Phép so ở `app/Jobs/jobKtTheBHYT.php:152` hiện là `$params['maCSKCB'] != $maDKBD` — sau khi
tách, nó phải dùng **`maDkbd`**, vì nó đang đối chiếu nơi đăng ký ban đầu do cổng trả về với
nơi đăng ký ban đầu trong HIS. Đây là điểm dễ sửa nhầm nhất của cả spec.

**Đường tra cứu thủ công** (`app/BHYT.php`): **không đụng ở spec này.** `loginBHYT()` và
`checkInsuranceCard()` là method tĩnh không có tham số cơ sở, được gọi từ **6 controller**;
thêm tham số là thay đổi lan sang cả 6 nơi. Chuyển sang spec thứ hai, làm cùng lúc với
`BHYTXmlSubmitService` vốn cũng cần đúng cách sửa đó.

Spec này giữ nguyên các khoá `username`/`password`/`hoTenCb`/`cccdCb` cũ trong khối `BHYT`
để đường đó chạy y như trước. Công tắc `check_by_user` khi bật vốn lấy tên và CCCD của người
đang đăng nhập, nên phần lớn lượt tra thủ công không đụng tới cấu hình.

Job chạy nền không có người đăng nhập nên luôn đi đường cấu hình — đó là đường spec này sửa.

### 6. Lưu kết quả kèm cơ sở

Thêm cột `ma_cskcb VARCHAR(20) NULL` vào `check_hein_cards`, kèm index. Bảng đang 0 dòng nên
**không cần vá ngược**.

## Kiểm thử

Trong `tests/Unit`. Cổng: `vendor/bin/phpunit --testsuite Unit`.

**Hàm thuần** (`CauHinhCoSoTest`):

1. Cơ sở có khai đủ → trả đúng bộ `username`/`password`/`ho_ten_cb`/`cccd_cb`.
2. Cơ sở **chưa khai** → ném `InvalidArgumentException`, thông báo có chứa mã cơ sở.
3. Có khai nhưng **thiếu `password`** → ném ngoại lệ.
4. Mã cơ sở rỗng hoặc `null` → ném ngoại lệ.
5. `maTinh('37470')` → `'37'`; `maTinh('01929')` → `'01'`.
6. `maTinh('1')` → ném ngoại lệ.

**Khoá cache** (`BHYTLoginServiceCacheTest`): khoá cache của `01929` và `37470` **khác nhau**.
Đây là bài kiểm chống đúng lỗi token ghi đè.

**Lệnh quét** (`KiemTraTheCoSoTest`): quét mã nguồn bằng trait `Tests\Support\LocComment`
method `maKhongComment()` — khẳng định lệnh có join `his_branch` và **không** còn gán
`tdl_hein_medi_org_code` vào tham số `maCSKCB`.

**Job** (`JobKtTheBhytTest`): khẳng định phép so ở dòng ~152 dùng `maDkbd`, không dùng
`maCSKCB`.

## Nghiệm thu bằng số

Chạy `php artisan kiemtrathebhyt:day --thu` (chế độ chỉ đếm, không dispatch — xem mục 5),
rồi thống kê tham số `maCSKCB` sinh ra:

- Trước: 4.194 giá trị phân biệt.
- Sau: **chỉ được có các mã nằm trong `BHYT_CO_SO`** — với dữ liệu hiện tại là `01929` và
  `37470`.

Kèm số hồ sơ bị bỏ qua vì không xác định được cơ sở.

Đây là nghiệm thu bắt buộc; nó là bằng chứng duy nhất cho thấy đã sửa đúng lỗi 99,5% khai
sai cơ sở.

## Phạm vi không làm

- **Không** đụng đường gửi XML (`BHYTXmlSubmitService` và 4 nơi gọi nó, `ma_tinh` ở 5 nơi) —
  đó là spec thứ hai.
- **Không** đổi ba bộ kiểm XML1/XML3 đang dùng `correct_facility_code` — spec thứ hai.
- **Không** đụng `JobBHYT`, `JobInpatient` (mã chết).
- **Không** đụng `app/BHYT.php` — method tĩnh, 6 controller gọi; thuộc spec thứ hai.
- **Không** gọi thật lên cổng BHXH trong lúc kiểm thử.
- **Không** vá ngược `check_hein_cards` (bảng rỗng).
- **Không** đưa mật khẩu hiện tại vào `.env` giúp người dùng — đó là việc của người vận hành,
  và mật khẩu cũ đã lộ trong lịch sử git nên nên đổi mới.

## Việc người vận hành phải làm

1. Khai `.env` cho từng cơ sở: `BHXH_<mã>_USER`, `BHXH_<mã>_PASS`, `BHXH_<mã>_HOTEN`,
   `BHXH_<mã>_CCCD`.
2. `php artisan config:clear`.
3. **Đổi mật khẩu cổng BHXH** — mật khẩu cũ nằm trong lịch sử git, ai có bản sao kho mã đều
   đọc được.
