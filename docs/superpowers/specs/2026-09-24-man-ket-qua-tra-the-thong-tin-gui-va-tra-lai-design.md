# Màn Kết quả tra cứu thẻ: hiện thông tin đã gửi và nút Tra lại — Thiết kế

Ngày: 2026-09-24 · Màn: `/bhyt/check-hein-card/index` · Trạng thái: đã duyệt

## 1. Bối cảnh và vấn đề

Màn "Kết quả tra cứu thẻ" (`CheckHeinCardController`, bảng `check_hein_cards`) chỉ hiện những gì
**cổng BHXH trả về**. Khi cổng báo lỗi, cổng thường không trả số thẻ, họ tên, ngày sinh, nên dòng
lỗi chỉ còn mã hồ sơ, khó theo dõi.

Số liệu CSDL thật (24/09/2026, chỉ đọc):

| Chỉ số | Số dòng |
|---|---|
| Tổng `check_hein_cards` | 46.396 |
| Dòng lỗi (`chiLoi()`) | 1.525 |
| Dòng lỗi thiếu số thẻ | 1.213 (050: 1.162 · 070: 52 · 053: 1) |
| Trong đó tìm thấy trên HIS | 1.213 / 1.213 |
| Trong đó là thẻ tạm trẻ sơ sinh (`TheTamSoSinh::la`) | 1.149 |
| Dòng có `gt_the_tumoi` | **0** / 46.396 |

Phát hiện kèm theo:
- **Thẻ tạm sơ sinh cũ không bao giờ được dọn.** Commit `05abb860` chỉ xoá kết quả lỗi khi job chạy
  lại, mà lệnh quét hằng ngày chỉ quét bệnh nhân đang nằm viện.
- **Lỗi ngầm trong model.** `$fillable` ghi `gt_the_tu_moi` / `gt_the_den_moi`, trong khi cột thật là
  `gt_the_tumoi` / `gt_the_denmoi`. Vì vậy `updateOrCreate` lặng lẽ bỏ hai trường này, và ô "Thẻ mới
  giá trị từ/đến" luôn trống.

Không có nút tra lại trên màn này. Nút "Tra lại thẻ" hiện chỉ có ở màn Tra cứu lỗi hồ sơ
(`TraCuuLoiHoSoController::traLaiThe`, quyền `tra-cuu-loi-ho-so`).

## 2. Mục tiêu / Ngoài phạm vi

Mục tiêu:
1. Dòng lỗi luôn có số thẻ, họ tên, ngày sinh để quan sát. Lấy giá trị cổng; cổng trả trống thì lấy
   giá trị **đã gửi** lên cổng.
2. Nút **Tra lại** trên **từng dòng lỗi**.
3. Dọn một lần các dòng lỗi cũ của thẻ tạm sơ sinh.
4. Sửa lỗi ngầm `gt_the_tumoi` / `gt_the_denmoi`.

Ngoài phạm vi: tra lại hàng loạt; đổi định nghĩa "lỗi" (`chiLoi()`); luồng QĐ130 cũ; màn nhập thẻ
cũ `App\BHYT`.

## 3. Quyết định đã chốt

| # | Quyết định | Phương án bị loại |
|---|---|---|
| Q1 | **Lưu giá trị đã gửi** vào 4 cột mới, bù dữ liệu cũ từ HIS | Tra HIS lúc hiển thị: làm hỏng ô tìm kiếm và khiến màn phụ thuộc HIS |
| Q2 | Nút **chỉ trên từng dòng** | Tra hàng loạt |
| Q3 | **Lệnh dọn một lần** cho thẻ tạm cũ, mặc định chỉ đếm (`--ghi` mới xoá) | Để nguyên, chỉ gắn nhãn |
| Q4 | **Sửa luôn** lỗi `$fillable` | Để đợt khác |
| Q5 | Tách **service dùng chung** `TraLaiThe` cho cả hai màn | Viết lại logic ở controller mới |
| Q6 | Thẻ tạm khi bấm Tra lại: **vẫn đẩy job** (job tự xoá dòng lỗi) và báo rõ. Áp dụng cho **cả màn Tra cứu lỗi hồ sơ** (thay cho 422 hiện tại) | Giữ 422 |

## 4. Thiết kế chi tiết

### 4.1 Dữ liệu
- Migration `2026_09_24_100000_them_thong_tin_gui_vao_check_hein_cards.php` thêm 4 cột, tất cả
  `nullable`, **không** index (bảng ~46 nghìn dòng, ô tìm kiếm vốn đã dùng `LIKE %x%`):
  - `ma_the_gui` string(50)
  - `ho_ten_gui` string(255)
  - `ngay_sinh_gui` string(20): giữ nguyên định dạng job gửi lên cổng (`dob()`: dd/mm/yyyy hoặc yyyy)
  - `ma_dkbd_gui` string(20)
- `down()` xoá đúng 4 cột.
- Model `check_hein_card`: thêm 4 cột vào `$fillable`; sửa `gt_the_tu_moi` → `gt_the_tumoi`,
  `gt_the_den_moi` → `gt_the_denmoi`.

### 4.2 Ghi khi tra (`jobKtTheBHYT::addCheckHeinCard`)
- Thêm vào mảng ghi: `ma_the_gui` ← `params['maThe']`, `ho_ten_gui` ← `params['hoTen']`,
  `ngay_sinh_gui` ← `params['ngaySinh']`, `ma_dkbd_gui` ← `params['maDkbd']` (thiếu khoá thì `null`).
- Ghi ở **mọi** nhánh: thành công, `ClientException`, lỗi khác. Mọi nơi gửi job đều hưởng: lệnh quét
  HIS, nạp XML, nút bấm.
- Các truy cập `$result_check['x']` hiện tại vốn không phòng vệ. Khi cổng lỗi mạng, `$result_check`
  là `null` và dòng vẫn được ghi với giá trị `null`. Giữ nguyên hành vi này, không mở rộng phạm vi.

### 4.3 Bù dữ liệu cũ: lệnh `the-bhyt:bu-thong-tin-gui {--ghi} {--tat-ca}`
- Mặc định chỉ xét **dòng lỗi** (`chiLoi()`) còn `ma_the_gui` rỗng. `--tat-ca` xét mọi dòng còn rỗng.
- Duyệt theo lô 900 `ma_lk` (dưới giới hạn 1000 phần tử `IN` của Oracle). Mỗi lô gọi một truy vấn
  `his_treatment` lấy `tdl_hein_card_number`, `tdl_patient_name`, `tdl_patient_dob`,
  `tdl_hein_medi_org_code`.
- Ngày sinh chuyển bằng `dob()`, **đúng hàm lệnh quét dùng**, để dữ liệu bù giống dữ liệu ghi mới.
- Mặc định **chỉ đếm**: không có `--ghi` thì in số dòng sẽ bù và số không thấy trên HIS, không ghi gì.
  (Khác `kiemtrathebhyt:day --thu`, lệnh đó mặc định chạy thật. Ở đây mặc định an toàn vì lệnh ghi
  hàng loạt.)
- Chỉ ghi vào 4 cột `_gui`, dùng `DB::table()->update()`. **Không** chạm `updated_at`, vì cột đó là
  "thời gian tra cứu" hiện trên màn và là trường bộ lọc ngày.

### 4.4 Service `App\Services\BHYT\TraLaiThe`
- `gui(string $treatmentCode): array` trả về `['ok' => bool, 'message' => string]`.
- Chuyển nguyên logic từ `TraCuuLoiHoSoController::traLaiThe`, theo thứ tự:
  1. mã rỗng
  2. lỗi đọc HIS
  3. không có hồ sơ
  4. thiếu số thẻ
  5. thiếu giới tính
  6. cơ sở không có trong `organization.BHYT_CO_SO`
  7. đảo giới tính (`gioiTinhCongBhxh`)
  8. `jobKtTheBHYT::dispatch([...], false)->onQueue('JobKtTheBHYT')`
- Thẻ tạm (`TheTamSoSinh::la`): **vẫn dispatch**. Message "Thẻ tạm trẻ sơ sinh (nơi ĐKBĐ X), không tra
  cổng BHXH — đã gửi yêu cầu xoá kết quả lỗi cũ".
- Nhận `TreatmentProfileService` qua constructor (container). **Không** type-hint trên
  `Job::handle()`: bẫy tiêm container đã biết.
- `TraCuuLoiHoSoController::traLaiThe` còn: đọc mã → `TraLaiThe::gui()` → `ok` trả 200, ngược lại 422.
  Nội dung thông báo và route giữ nguyên.

### 4.5 Route + controller màn Kết quả tra cứu thẻ
- `POST bhyt/check-hein-card/tra-lai` → `CheckHeinCardController@traLai`, tên
  `bhyt.check-hein-card.tra-lai`. Nằm trong nhóm `checkrole:xml-man` sẵn có; tham số `ma_lk`.
- `fetch()` thêm cột tính sẵn, qua `check_hein_card::hienThi($truong)`:
  - `hien_ma_the`, `hien_ho_ten`, `hien_ngay_sinh`: giá trị cổng nếu khác rỗng, ngược lại giá trị `_gui`
  - `nguon_ma_the`, `nguon_ho_ten`, `nguon_ngay_sinh`: `'cong'` | `'gui'` | `''`. Tách theo TỪNG
    trường, vì một dòng có thể có số thẻ từ cổng nhưng họ tên phải lấy giá trị đã gửi.
- Ba cột `hien_*` đặt `orderable: false, searchable: false`. Chúng không phải cột SQL; sắp xếp theo
  chúng sẽ làm truy vấn Datatables vỡ.
- Ô `tim` tìm thêm `ma_the_gui`, `ho_ten_gui`.

### 4.6 Giao diện (`index.blade.php`)
- Ba cột Số thẻ / Họ tên / Ngày sinh render từ `hien_*`. Khi `nguon_<trường> === 'gui'` thì bọc
  `<i class="text-muted" title="Theo HIS (cổng không trả về)">…</i>`. Giá trị qua `.text()` rồi
  `.html()` (chống XSS, giống modal hiện có).
- Cột "Xem" đổi thành cột "Thao tác": nút **Xem** và nút **Tra lại**. Nút Tra lại chỉ hiện khi
  `co_loi`.
  - Bấm: khoá nút, `POST` kèm CSRF, hiện `alert` message.
  - Sau 5 giây gọi `table.ajax.reload(null, false)`: giữ trang, giữ bộ lọc.
- Modal chi tiết: thêm 4 dòng "Số thẻ đã gửi / Họ tên đã gửi / Ngày sinh đã gửi / Nơi ĐKBĐ đã gửi".

### 4.7 Xuất Excel (`KetQuaTraCuuTheExport`)
- Thêm 4 cột cuối: "Số thẻ đã gửi", "Họ tên đã gửi", "Ngày sinh đã gửi", "Nơi ĐKBĐ đã gửi". Thêm vào
  cuối để không xê dịch cột người dùng đã quen.

### 4.8 Dọn thẻ tạm cũ: lệnh `the-bhyt:don-the-tam {--ghi}`
- Xét dòng **lỗi theo quy tắc job** (`qd130xml.hein_card_invalid`, giống điều kiện xoá trong
  `jobKtTheBHYT`) để hai nơi xoá đúng một tập.
- Lấy số thẻ + ĐKBĐ: ưu tiên cột `_gui`, rỗng thì tra HIS theo lô 900. Xoá khi `TheTamSoSinh::la`.
- Không `--ghi` thì chỉ in: tổng xét, số thẻ tạm, số không thấy trên HIS. Có `--ghi` thì xoá và in
  số đã xoá.

## 5. Xử lý lỗi
- HIS không trả lời trong lệnh bù/dọn: ghi log, dừng lệnh với mã thoát khác 0. Lô đã ghi vẫn giữ,
  chạy lại được vì lệnh chỉ xét dòng còn rỗng/còn lỗi.
- Nút Tra lại: mọi lỗi nghiệp vụ trả 422 kèm message tiếng Việt. Lỗi HIS trả "Không lấy được thông
  tin từ HIS" (như hiện có).
- Cổng lỗi trong job: không đổi hành vi, chỉ thêm cột `_gui`.

## 6. Kiểm thử
Viết test trước, đỏ trên code cũ. SQLite in-memory ghi đè kết nối `mysql`/`HISPro`, **không**
RefreshDatabase, chạy với `DB_HOST=127.0.0.1`.
- Job: ghi 4 cột `_gui` khi cổng trả lỗi (`ClientException` giả qua Guzzle MockHandler hoặc tách hàm
  ghi); `gt_the_tumoi`/`gt_the_denmoi` được lưu.
- Service `TraLaiThe`: từng nhánh 1–8; thẻ tạm vẫn dispatch và trả message thẻ tạm.
- `TraCuuLoiHoSoTest`: toàn bộ ca cũ giữ xanh; ca thẻ tạm đổi từ "422, không dispatch" sang
  "200, dispatch 1 lần, message thẻ tạm".
- Route mới: có quyền `xml-man`; dispatch đúng 1 job trên queue `JobKtTheBHYT`; thiếu `ma_lk` trả 422.
- `fetch()`: `hien_*` lấy `_gui` khi cổng rỗng, lấy cổng khi có; tìm theo `ho_ten_gui` ra dòng.
- Export: số tiêu đề = số cột `map()` (test sẵn có, cập nhật con số).
- Lệnh bù và lệnh dọn: không có `--ghi` thì không ghi/xoá gì; có `--ghi` thì đúng số dòng, không chạm
  `updated_at`, chỉ xoá thẻ tạm lỗi.
- Nghiệm thu chỉ đọc trên CSDL thật: chạy hai lệnh không `--ghi`, kỳ vọng khoảng 1.213 dòng bù và
  khoảng 1.149 dòng dọn.

## 7. Triển khai trên prod
1. Kéo code rồi chạy `php artisan migrate` NGAY (migration chỉ thêm cột nullable — code cũ vẫn
   chạy đúng với bảng mới).
2. `php artisan config:cache` nếu prod cache cấu hình.
3. `php artisan queue:restart` (worker cũ giữ class cũ trong bộ nhớ: không ghi cột `_gui`, không
   nhận bản sửa fillable).
4. `php artisan the-bhyt:bu-thong-tin-gui`: xem số liệu (kỳ vọng ~1.213 dòng), rồi thêm `--ghi`.
5. Sao lưu bảng `check_hein_cards`.
6. `php artisan the-bhyt:don-the-tam`: xem số liệu (kỳ vọng ~1.149), rồi thêm `--ghi`.
7. Nghiệm thu: mở màn, tìm theo họ tên đã gửi, bấm "Tra lại" một dòng lỗi thường và một dòng thẻ
   tạm.
