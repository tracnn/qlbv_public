# Xuất danh sách lỗi XML3176 chạy nền — thiết kế

**Ngày:** 30/09/2026
**Phạm vi:** nút *Xuất danh sách lỗi* trên màn danh sách XML3176 (hiện là route `bhyt.xml3176.export-xml3176-xml-errors`)
**Trạng thái:** đã duyệt thiết kế (3 phần), chờ duyệt spec

## 1. Vấn đề

### 1.1. Sự cố

Trên prod (`tgdbhyt.bachmai.edu.vn`, đứng sau Cloudflare), bấm *Xuất danh sách lỗi* với bộ lọc ngày thanh toán 29/09/2026, có thẻ BHYT, trả **504 Gateway time-out**.

### 1.2. Đo trên dữ liệu thật (chỉ đọc)

Cùng đúng bộ lọc trong URL lỗi:

| Đại lượng | Giá trị |
|---|---:|
| Hồ sơ khớp bộ lọc | 1.880 |
| Tổng dòng lỗi (16 sheet lỗi) | 204.617 |
| trong đó sheet XML3 / XML4 | 101.646 / 91.234 |
| Sheet thẻ BHYT (ngày 29/09 / cả tháng 9) | 0 / 9 |
| **Thời gian xuất như hiện nay** | **1.796 giây (~30 phút)**, bộ nhớ đỉnh 2,1 GB, tệp 12,4 MB |
| Giới hạn chờ của Cloudflare | **100 giây** |

Ngày 29/09 không bất thường về mật độ lỗi (121,7 dòng/hồ sơ; 24/09 là 95,1, 25/09 là 87,0). Nó chỉ là ngày có nhiều hồ sơ nhất (lô nạp 5.443 hồ sơ).

### 1.3. Nguyên nhân gốc

**(a) Đọc phân trang lặp lại truy vấn nặng.** `Xml3176ErrorSheetExport` là `FromQuery`. Laravel Excel đọc theo lô `chunk_size = 1000` bằng `LIMIT … OFFSET`; mỗi lô MySQL chạy lại **toàn bộ** truy vấn (subquery lọc hồ sơ + 4 join + `ORDER BY ma_lk, stt, id`) rồi mới cắt 1.000 dòng:

| Cách đọc sheet XML3 | Thời gian |
|---|---:|
| Một lô 1.000 dòng, OFFSET 0 / 50.000 / 100.000 | 7,2 / 6,7 / 6,7 giây |
| Đọc hết 101.646 dòng bằng `cursor()` — một truy vấn | **7,8 giây** |

~102 lô XML3 + ~92 lô XML4, mỗi lô ~7 giây ≈ 1.400 giây.

**(b) Ghi Excel tự nó vượt 100 giây.** Bản thử nghiệm (dùng rồi bỏ) đọc mỗi sheet bằng `cursor()`: **700 giây**, bộ nhớ đỉnh 2,5 GB. Phần còn lại là PhpSpreadsheet giữ ~3,9 triệu ô trong bộ nhớ rồi ghi.

Kết luận: với ngày cỡ 29/09 **không có cách xuất đồng bộ nào vừa 100 giây**. Sửa (a) là cần nhưng không đủ.

### 1.4. Phát hiện phụ (ngoài phạm vi)

`XML3_OVERLAPPING_SERVICE_EXECUTION` sinh 51.708 dòng từ 115 hồ sơ (~450 dòng/hồ sơ) — dáng của việc báo từng **cặp** dịch vụ chồng giờ, tăng theo bình phương. Chiếm một phần tư bản xuất. Việc riêng.

## 2. Các quyết định đã chốt với người dùng

| # | Câu hỏi | Quyết định |
|---|---|---|
| Q1 | Hướng sửa | **Xuất nền qua hàng đợi**, tải về khi xong |
| Q2 | Áp dụng khi nào | **Luôn** xuất nền — một đường cho mọi cỡ dữ liệu |
| Q3 | Ai được tải, giữ bao lâu | **Chỉ người bấm**; tự xoá sau **7 ngày** (tệp chứa họ tên, mã thẻ bệnh nhân) |
| Q4 | Hàng đợi | **Hàng đợi + dịch vụ riêng** — không chặn chuỗi kiểm → xuất → ký → gửi |
| Q5 | Cách làm | **A**: bảng theo dõi yêu cầu + job riêng (không dùng `Excel::queue()`: nó lại chia lô `OFFSET` và mở lại tệp xlsx mỗi lô) |
| — | Hai nút *Xuất danh sách hồ sơ*, *7980a* | Ngoài phạm vi; giữ tải trực tiếp |

## 3. Kiến trúc

```
[Màn XML3176]  bấm "Xuất danh sách lỗi"
      │ AJAX POST bộ lọc
      ▼
[Controller]  ghi yêu cầu (cho) ─► đẩy XuatTepLoiXml3176Job  [hàng đợi JobXuatTepXml3176, kết nối xuat_tep]
      │                                   │
      │                                   ▼ dang_tao → dựng Xml3176ErrorMultiSheetExport(bộ lọc đã lưu)
      │                                   ▼ ghi tệp storage/app/xml3176-tep-xuat/ → xong (hoặc loi)
      ▼
[Mục "Tệp xuất của tôi"]  GET danh sách (tự hỏi lại 15 s khi còn việc đang chạy) ─► GET tải (chỉ chủ yêu cầu)
```

### 3.1. Bảng `xml3176_tep_xuat`

| Cột | Kiểu | Ý nghĩa |
|---|---|---|
| `id` | increments | |
| `user_id` | unsigned int, index | người bấm; chỉ người này thấy và tải |
| `loai` | string(20) | `'loi'` — để chỗ cho hai nút xuất khác sau này; đợt này chỉ `'loi'` |
| `bo_loc` | text (JSON) | đúng mảng `Xml3176LocDanhSach::tuRequest()` lúc bấm |
| `trang_thai` | string(20), index | `cho` → `dang_tao` → `xong` \| `loi` |
| `duong_dan` | string, nullable | đường dẫn tương đối trên disk `local` |
| `kich_thuoc` | unsigned bigint, nullable | byte |
| `loi` | text, nullable | lý do khi `loi` |
| `bat_dau_luc`, `xong_luc` | timestamp, nullable | |
| `created_at`, `updated_at` | timestamps | |

Model `App\Models\BHYT\Xml3176TepXuat`, hằng trạng thái `CHO`, `DANG_TAO`, `XONG`, `LOI`.

### 3.2. Job `XuatTepLoiXml3176Job`

- Nhận **id yêu cầu** (không nhận model — job có thể chờ lâu, model serialize sẵn mang dữ liệu cũ).
- `$tries = 1`: hỏng thì ghi lỗi, **không** tự chạy lại một việc tốn 12–30 phút.
- Không khai `$timeout` có ý nghĩa: PHP Windows không có `pcntl` nên `$timeout` không được thi hành; chặn treo bằng mốc 90 phút ở mục 4.1.
- Trình tự: đọc yêu cầu (không còn hoặc không ở `cho` → thoát) → `dang_tao`, `bat_dau_luc` → nâng `set_time_limit(0)` và `memory_limit` → `Excel::store(new Xml3176ErrorMultiSheetExport($boLoc, $danhSachCoSo), $duongDan, 'local')` → `xong`, `kich_thuoc`, `xong_luc`.
- `$danhSachCoSo` lấy lúc chạy bằng `App\Services\BHYT\DanhSachCoSo::danhSach()` — đúng hàm controller đang dùng (`boLocDanhSach()`). Hàm này đọc danh sách cơ sở từ HIS qua cache, **không** phụ thuộc người đăng nhập; `Xml3176LocDanhSach` cũng không đọc thông tin đăng nhập (giới hạn "chỉ thấy hồ sơ mình nạp" đã bỏ từ 25/09). Nên job cho ra đúng tập hồ sơ người dùng nhìn thấy lúc bấm, không cần tách hàm mới.
- **Luôn** đặt lại bộ gán giá trị của Laravel Excel về mặc định sau khi xuất (trong `finally`): hai sheet danh mục đặt `StringValueBinder` vào biến **tĩnh** và không trả lại (`vendor/maatwebsite/excel/src/Sheet.php`; chú thích sẵn trong `Xml3176ErrorMultiSheetExport`). Trong worker chạy nhiều lần xuất nối tiếp, thiếu bước này thì các lần sau ghi mọi ô thành chuỗi.
- `failed()`: đánh dấu `loi` với thông điệp; xoá tệp dở nếu có.

### 3.3. Đọc một lần thay vì phân trang

`Xml3176ErrorSheetExport` đổi từ `FromQuery` sang `FromGenerator`, `generator()` duyệt `$this->query()->cursor()`. Giữ nguyên `query()` (cùng chọn cột, join, thứ tự `ma_lk, stt, id`), `headings()`, `map()`, `styles()`, `registerEvents()`, `title()`. Nội dung, thứ tự và định dạng tệp **không đổi**.

**Không** đổi `HeinCardErrorExport` (dùng chung với module Qd130; tối đa 9 dòng/tháng) và hai sheet danh mục (bảng tra cứu nhỏ).

### 3.4. Kết nối hàng đợi và dịch vụ

- `config/queue.php` (git có theo dõi) thêm kết nối `xuat_tep`: driver `database`, bảng `jobs`, `queue` mặc định `JobXuatTepXml3176`, **`retry_after` = 3600**. Kết nối `database` hiện có (`retry_after` 300) phải giữ nguyên: job xuất chạy quá 300 giây, dùng chung thì hàng đợi coi job đã chết và giao lại.
- `config/xml3176.php` thêm `'xuat_tep_queue_name' => 'JobXuatTepXml3176'`, `'xuat_tep_connection' => 'xuat_tep'`, `'xuat_tep_giu_ngay' => 7`, `'xuat_tep_treo_phut' => 90`.
- Dịch vụ `QLBV JobXuatTepXml3176`: `artisan queue:work xuat_tep --queue=JobXuatTepXml3176`, thêm vào `update.bat`, `install_service.bat`, `remove_service.bat` theo khuôn `QLBV JobSignXml3176`. **Không đổi một byte nào** từ đầu `update.bat` tới hết dòng `git pull origin main` (có test chốt SHA-256 sẵn trong `tests/Unit/Xml3176/Chuoi/DichVuHangDoiKyTest.php` — giữ xanh).

## 4. Hành vi

### 4.1. Tạo yêu cầu

`POST bhyt/xml3176/tep-xuat` (tên route `bhyt.xml3176.tep-xuat.tao`), trong nhóm route/phân quyền của màn XML3176.

1. Dọn dẹp (mục 4.4).
2. Chuẩn hoá bộ lọc bằng `Xml3176LocDanhSach::tuRequest()`.
3. **Chống bấm trùng:** nếu chính người này có yêu cầu `loai='loi'` ở `cho` hoặc `dang_tao` với `bo_loc` **giống hệt** → trả yêu cầu đó, không tạo mới, không đẩy job.
4. Ngược lại: tạo dòng `cho`, đẩy job lên kết nối `xuat_tep`, hàng đợi `JobXuatTepXml3176` — **sau** khi dòng đã ghi.
5. Trả JSON `{id, trang_thai, trung: bool}`; màn hình báo *"Đã xếp hàng tạo tệp. Theo dõi ở mục Tệp xuất của tôi."* (hoặc *"Yêu cầu giống hệt đang chạy"* khi `trung`).

### 4.2. Danh sách "Tệp xuất của tôi"

`GET bhyt/xml3176/tep-xuat` (`bhyt.xml3176.tep-xuat.danh-sach`) trả JSON các yêu cầu của **người đang đăng nhập** trong 7 ngày, mới nhất trước: `id`, thời điểm yêu cầu, tóm tắt bộ lọc (khoảng ngày, loại ngày, cơ sở, bộ lọc thẻ — các bộ lọc có giá trị), trạng thái, **số yêu cầu `cho` đứng trước** (với dòng `cho`, đếm trên mọi người dùng), số phút đã chạy (với `dang_tao`), kích thước, lỗi.

Trước khi trả: dọn dẹp (4.4) và **đánh dấu treo** — dòng `dang_tao` có `bat_dau_luc` cũ hơn 90 phút chuyển `loi` với *"Quá thời gian, có thể dịch vụ xuất đã dừng. Bấm tạo lại."*

Giao diện: nút *Tệp xuất của tôi* (kèm số yêu cầu đang chờ/đang tạo) trên màn danh sách, mở bảng. Bảng chỉ tự hỏi lại (15 giây) khi còn dòng `cho`/`dang_tao`; không còn thì dừng.

### 4.3. Tải tệp

`GET bhyt/xml3176/tep-xuat/{id}/tai` (`bhyt.xml3176.tep-xuat.tai`):
- Yêu cầu không tồn tại, **không thuộc người đang đăng nhập**, chưa `xong`, hoặc tệp không còn trên đĩa → **404**. Không phân biệt các trường hợp, để không lộ yêu cầu của người khác.
- Tên tệp tải về: `xml3176_loi_{Ymd của date_from}_{YmdHis lúc tạo}.xlsx`.

### 4.4. Dọn dẹp (không có scheduler)

Prod không chạy Laravel scheduler (`Kernel::schedule()` trống, không gì gọi `schedule:run`). Nên mỗi lần **tạo yêu cầu** hoặc **lấy danh sách**: xoá các dòng `created_at` cũ hơn 7 ngày **và** tệp của chúng.

### 4.5. Route cũ

`GET bhyt/xml3176/export-xml3176-xml-errors` không còn nút nào gọi. Chuyển hướng về màn danh sách XML3176 kèm thông báo *"Xuất danh sách lỗi đã chuyển sang tạo tệp nền — bấm lại nút Xuất danh sách lỗi."*

## 5. Lỗi

| Tình huống | Xử lý |
|---|---|
| Job ném lỗi | `failed()`: `loi` + thông điệp; xoá tệp dở |
| Worker chết giữa chừng (triển khai trúng lúc xuất, hết RAM — lỗi fatal không qua `failed()`) | Dòng kẹt `dang_tao`; mốc 90 phút ở 4.2 chuyển `loi` |
| Job được giao lại sau khi worker chết | `retry_after` 3600 + `$tries = 1` → Laravel coi là vượt số lần thử, gọi `failed()` → `loi`; không chạy lại |
| Yêu cầu bị dọn trước khi job chạy | Job đọc không thấy → thoát |

`auto-updater.ps1` chỉ chạy `update.bat` khi `origin/main` có commit mới (so `git rev-parse HEAD`), nên job chỉ có thể bị giết đúng lúc triển khai, không phải mỗi giờ.

## 6. Kiểm thử

Quy ước repo: **cấm `RefreshDatabase`**; SQLite in-memory qua `Tests\Support\Xml3176RuleTestSupport::bootXml3176Sqlite()`; `setUp()` không `: void`; test chạy `DB_HOST=127.0.0.1`.

1. **Nội dung không đổi khi chuyển sang `cursor`:** trên dữ liệu SQLite nhỏ, sheet lỗi cho ra đúng các dòng, **đúng thứ tự** `ma_lk, stt, id`, đúng cột (so với mong đợi viết tay).
2. `Xml3176ErrorSheetExport` là `FromGenerator`, không còn `FromQuery` (chốt chống thoái lui).
3. Job: chuyển `cho` → `dang_tao` → `xong`, ghi tệp (`Storage::fake('local')`), `kich_thuoc` > 0; yêu cầu không còn / không ở `cho` → thoát không làm gì.
4. `failed()` → `loi` + thông điệp.
5. **Sau khi job chạy xong, bộ gán giá trị của Laravel Excel là mặc định** (không phải `StringValueBinder`).
6. Tạo yêu cầu: tạo dòng + đẩy đúng job lên đúng kết nối/hàng đợi (`Queue::fake()`); bấm trùng cùng bộ lọc → không tạo, không đẩy, trả `trung = true`.
7. Danh sách: chỉ yêu cầu của mình; dòng `dang_tao` quá 90 phút → `loi`; dòng cũ hơn 7 ngày bị xoá cùng tệp.
8. Tải: của mình và `xong` → tải được; của người khác / chưa xong / tệp mất → 404.
9. Route cũ chuyển hướng kèm thông báo.
10. `config('queue.connections.xuat_tep.retry_after')` ≥ 3600 và kết nối `database` vẫn 300.
11. Ba tệp `.bat` có dịch vụ `QLBV JobXuatTepXml3176` (install/stop/start/remove), vẫn CRLF; test chốt byte `update.bat` sẵn có vẫn xanh.

**Xác minh toàn bộ:** full suite hai lượt (`main` vs nhánh) với `DB_HOST=127.0.0.1`, so **tên** test đỏ (nền hiện tại: 102 test đỏ do môi trường).

## 7. Triển khai và việc trên prod

- Push lên `main` là triển khai (`auto-updater.ps1` → `update.bat`: migrate, cài dịch vụ còn thiếu, khởi động lại dịch vụ).
- Sau cập nhật, người vận hành:
  1. Kiểm tra dịch vụ `QLBV JobXuatTepXml3176` đang chạy.
  2. **Kiểm tra RAM trống** của máy chủ: một lần xuất ngày lớn cần ~2,5 GB. Không đủ thì job chết và yêu cầu chuyển `loi` sau 90 phút.
  3. Xuất lại đúng ngày 29/09 có thẻ BHYT; xác nhận tệp đủ 19 sheet, XML3 101.646 dòng, XML4 91.234 dòng; ghi lại thời gian chạy thực tế.

## 8. Ngoài phạm vi

- Hai nút *Xuất danh sách hồ sơ* và *7980a* (vẫn tải trực tiếp).
- Quy tắc `XML3_OVERLAPPING_SERVICE_EXECUTION` sinh dòng theo cặp (mục 1.4).
- Thông báo (email/chuông) khi tệp xong — người dùng xem ở mục *Tệp xuất của tôi*.
- Giảm bộ nhớ của PhpSpreadsheet (ghi dạng luồng) — Laravel Excel 3.1.25 không hỗ trợ; xuất nền đã gỡ ràng buộc thời gian.

## 9. Việc đi kèm

- `readme.md`: mục 30/09.
- Tài liệu quy trình vận hành (`docs/quy-trinh-van-hanh/_nguon/build.js` → `.docx`): thêm dịch vụ `QLBV JobXuatTepXml3176` và hướng dẫn *Tệp xuất của tôi*.
