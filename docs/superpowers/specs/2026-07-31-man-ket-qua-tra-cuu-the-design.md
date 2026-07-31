# Màn danh sách kết quả tra cứu thẻ BHYT

Ngày: 2026-07-31

## Mục tiêu

Bảng `check_hein_cards` lưu kết quả tra thẻ BHYT của từng hồ sơ nhưng **chưa có màn danh
sách** nào. Dữ liệu chỉ xem được rời rạc trong tab chi tiết của từng hồ sơ XML.

Màn mới phục vụ **cả hai** nhu cầu người dùng đã chốt: tìm hồ sơ có vấn đề về thẻ, và tra cứu
lịch sử theo hồ sơ / số thẻ / họ tên.

## Hiện trạng đo được

`check_hein_cards`: 27 cột, hiện **10 dòng** — cả 10 đều sạch (`ma_tracuu = '000'`,
`ma_kiemtra = '00'`), 5 dòng cơ sở `01929` và 5 dòng `37470`.

Ba trường mã đã có sẵn bảng nhãn tiếng Việt:

- `ma_tracuu`, `ma_ketqua` → `config('__tech.insurance_error_code')` — 27 mã
- `ma_kiemtra` → `config('__tech.check_insurance_code')` — 13 mã

Hai blade đang dùng chúng (`bhyt/detail/detail-xml.blade.php`,
`bhyt/qd130/detail-xml-hein-card.blade.php`). Không có màn danh sách, không có route, không có
controller riêng.

## Thiết kế

### 1. Định nghĩa "lỗi" — hệ thống đang có hai, không trùng nhau

| Nơi | Quy tắc |
| --- | --- |
| `detail-xml.blade.php:12` tô đỏ | `ma_tracuu != '000'` **hoặc** `ma_kiemtra != '00'` |
| `jobKtTheBHYT` quyết định tra lại | mã nằm trong `config('qd130xml.hein_card_invalid')` |

Ví dụ lệch: `ma_tracuu = '001'` (thẻ do BHXH Bộ Quốc phòng quản lý) — blade tô đỏ, nhưng job
**không** coi là cần tra lại.

Màn này dùng **quy tắc thứ nhất** (khắt khe hơn): mục đích là nhìn ra thứ bất thường, nên
"khác chuẩn" đáng hiện hơn là "đáng tra lại".

Đặt thành scope `chiLoi()` / `chiHopLe()` trên model — một định nghĩa dùng chung, **không**
sinh thêm định nghĩa thứ ba rải rác trong controller.

**Không** hợp nhất hai quy tắc sẵn có: chúng phục vụ hai câu hỏi khác nhau, và đổi quy tắc của
job là đổi hành vi tra cứu thật.

### 2. Tra nhãn — phải phòng vệ

Các blade hiện tại viết `config('__tech.insurance_error_code')[$ma]` — **truy cập mảng không
phòng vệ**. Mã lạ là `Undefined index` và trắng trang. Trên danh sách nhiều dòng thì sớm muộn
cũng dính.

Hàm thuần `App\Services\BHYT\NhanMaThe::nhan($ma, array $bang)`:

- Có nhãn → trả nhãn
- Không có → trả **chính mã trần** (vẫn đọc được, không vỡ trang)
- Mã rỗng/null → trả chuỗi rỗng

**Không** sửa hai blade cũ trong đợt này — cùng rủi ro nhưng ngoài phạm vi; ghi lại để làm riêng.

### 3. Bộ lọc

Một hàng, khuôn `box box-primary` + select2 như màn order-check:

- **Cơ sở KCB** — dùng lại `partials/ma_cskcb`
- **Trạng thái** — Tất cả *(mặc định)* / Chỉ lỗi / Chỉ hợp lệ
- **Từ ngày – Đến ngày** trên `updated_at`
- **Ô tìm** — khớp `ma_lk` hoặc `ma_the` hoặc `ho_ten`

Lọc cơ sở ở đây là **so khớp thẳng** `ma_cskcb`, khác với màn danh mục. Lý do: đây là dữ liệu
sự kiện của một hồ sơ cụ thể tại một cơ sở cụ thể, không có khái niệm "dòng dùng chung" như
danh mục. Dòng `ma_cskcb` rỗng là dữ liệu cũ trước khi có cột đó, và chỉ hiện khi chọn
"Tất cả cơ sở".

### 4. Cột

`Mã hồ sơ` · `Số thẻ` · `Họ tên` · `Ngày sinh` · `Cơ sở` · `Mã tra cứu` · `Mã kiểm tra` ·
`Ghi chú` · `Thời gian` · `Xem`

Hai cột mã hiện **nhãn tiếng Việt**, không hiện mã trần: `000` không nói gì, "Thông tin thẻ
BHYT chính xác" thì nói đủ.

Dòng lỗi tô nền đỏ nhạt, dùng cùng ngưỡng với scope `chiLoi()`.

### 5. Chi tiết

17 cột còn lại (địa chỉ, thẻ cũ/mới, ĐKBĐ, giá trị thẻ từ/đến, mã KV, ngày đủ 5 năm, mã số
BHXH, mã kết quả…) hiện trong modal khi bấm **Xem**.

**Không** dùng lại `category/bhyt/_chi_tiet`: partial đó gọi endpoint
`category/bhyt/chi-tiet/{loai}/{id}` và đọc từ sổ đăng ký danh mục BHYT, mà bảng này không
phải danh mục. Ép vào sẽ phải khai một "loại danh mục" giả.

Thay vào đó modal dựng thẳng từ **dữ liệu dòng mà DataTables đã có sẵn** — không thêm route,
không thêm truy vấn.

### 6. Vị trí và quyền

Mục con của **Hồ sơ XML**, quyền `xml-man` — cùng nhóm với danh sách XML3176, vì dữ liệu gắn
theo `ma_lk` của hồ sơ.

## Kiểm thử

Trong `tests/Unit`. Cổng: `vendor/bin/phpunit --testsuite Unit`.

**Hàm thuần** (`NhanMaTheTest`):

1. Mã có trong bảng → trả nhãn.
2. Mã **không** có trong bảng → trả chính mã trần, **không** ném.
3. Mã rỗng / null → trả chuỗi rỗng.
4. Bảng rỗng → trả mã trần.

**Scope và màn** (`ManKetQuaTraCuuTheTest`):

1. `chiLoi()` bắt cả `ma_tracuu != '000'` lẫn `ma_kiemtra != '00'`.
2. `chiLoi()` và `chiHopLe()` bù nhau: tổng hai bên bằng tổng số dòng.
3. Route và mục menu tồn tại, quyền `xml-man`.
4. Blade có đủ bốn bộ lọc và khởi tạo select2.
5. Số `<th>` khớp số phần tử `columns`.
6. Blade **không** dùng truy cập mảng không phòng vệ cho bảng nhãn.

**Nghiệm thu bằng số** trên dữ liệu thật: hiện 10 dòng đều sạch, nên `chiLoi()` phải ra **0**
và `chiHopLe()` ra **10**. Hai con số khác nhau rõ rệt nên đây là phép thử có ý nghĩa.

## Phạm vi không làm

- **Không** thêm nút tra cứu lại từ màn này — gọi thật lên cổng BHXH, cần bàn riêng.
- **Không** xuất Excel.
- **Không** sửa hai blade chi tiết cũ đang truy cập mảng không phòng vệ.
- **Không** đổi quy tắc `hein_card_invalid` mà `jobKtTheBHYT` dùng.
- **Không** thêm cột hay migration — dùng nguyên bảng hiện có.
