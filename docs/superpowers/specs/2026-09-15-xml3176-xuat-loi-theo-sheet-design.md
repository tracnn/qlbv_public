# Xuất lỗi XML3176 theo sheet — thiết kế

**Ngày:** 15/09/2026
**Phạm vi:** nút *Xuất danh sách lỗi* trên màn danh sách XML3176 (`bhyt.xml3176.export-xml3176-xml-errors`)
**Trạng thái:** đã duyệt thiết kế, chờ duyệt spec

## 1. Mục tiêu

Người dùng yêu cầu ba thay đổi với file xuất lỗi:

1. Mỗi loại XML1 → XML15 nằm trên một sheet riêng.
2. Thêm sheet danh mục khoa và sheet danh mục nhân viên y tế.
3. Mỗi sheet lỗi có thêm cột mã khoa.

Hiện tại file có hai sheet: `Lỗi XML` (toàn bộ dòng lỗi, mọi loại XML trộn chung) và sheet lỗi tra cứu thẻ.

## 2. Các quyết định đã chốt với người dùng

| # | Câu hỏi | Quyết định |
|---|---|---|
| Q1 | Mã khoa cho bảng XML không có cột khoa | Lấy khoa của chính dòng khi bảng có cột khoa; không có hoặc rỗng thì lấy khoa hồ sơ (`XML1.MA_KHOA`) |
| Q2 | "Danh mục khoa" là bảng nào | Danh mục khoa–giường gửi BHXH: `department_bed_catalogs` |
| Q3 | Lỗi `XMLComplete` và sheet lỗi tra cứu thẻ | Giữ cả hai thành sheet riêng |
| Q4 | Sheet XML không có dòng lỗi | Luôn đủ 15 sheet, sheet trống chỉ có dòng tiêu đề |
| — | Phương án kiến trúc | Một lớp sheet dùng chung nhận tham số loại XML, cộng một bảng ánh xạ nguồn khoa |

Người dùng đã chốt từ trước (sửa bộ lọc ngày 11/09/2026) và vẫn giữ: **xuất ra đúng cái nhìn thấy** — sheet lỗi cắt theo đúng tập hồ sơ của màn danh sách, trong mỗi hồ sơ lấy hết dòng lỗi, không cắt thêm ở mức dòng.

## 3. Số liệu khảo sát làm căn cứ

Đo trên CSDL dev ngày 15/09/2026, 1.213 hồ sơ:

**Phân bố dòng lỗi theo loại XML**

| Loại | Dòng lỗi | Hồ sơ |
|---|---|---|
| XML1 | 1.836 | 1.153 |
| XML2 | 32.703 | 1.084 |
| XML3 | 118.390 | 1.146 |
| XML4 | 152.700 | 904 |
| XML5 | 7.079 | 1.152 |
| XML7 | 1.032 | 469 |
| XML8 | 863 | 466 |
| XML9 | 17 | 16 |
| XML11 | 15 | 7 |
| XML13 | 96 | 43 |
| XML14 | 1.733 | 864 |
| XMLComplete | 1.982 | 235 |
| **Tổng** | **318.446** | |

XML6, XML10, XML12, XML15 hiện không có dòng lỗi. XML6, XML12, XML15 **không có checker** (`Xml3176CheckTypes::LOAI`), nên sheet của chúng luôn trống — đó là trạng thái có sẵn, không phải lỗi của thiết kế này.

**Cột khoa trên từng bảng XML**

| Bảng | Cột khoa | Có cột `stt` |
|---|---|---|
| XML1 | `ma_khoa` | có |
| XML2 | `ma_khoa` | có |
| XML3 | `ma_khoa` | có |
| XML4 | *không có* | có |
| XML5 | *không có* | có |
| XML7 | `ma_khoa_rv` | không |
| XML6, XML8–XML15 | *không có* | XML15 có, còn lại không |

**Độ tin cậy của phép nối**

- `xml3176_error_results.stt` nối về dòng gốc bằng `(ma_lk, stt)`: khớp **32.703/32.703** (XML2), **118.390/118.390** (XML3), **152.700/152.700** (XML4).
- Cặp `(ma_lk, stt)` trùng: **0** ở XML2, XML3, XML4, XML5.
- `ma_lk` trùng: **0** ở XML7, XML8, XML14.
- `ma_khoa` rỗng: **0/1.213** ở XML1, **0/29.566** ở XML3.

Tức phép nối không nhân đôi dòng lỗi trên dữ liệu hiện có. Đây là **số đo trên dữ liệu**, không phải ràng buộc của lược đồ — xem mục 8.

## 4. Cấu trúc file

19 sheet, thứ tự cố định:

| # | Tên sheet | Nội dung |
|---|---|---|
| 1–15 | `XML1` … `XML15` | Dòng lỗi có `xml = 'XMLn'` |
| 16 | `XMLComplete` | Dòng lỗi có `xml = 'XMLComplete'` |
| 17 | `Lỗi tra cứu thẻ` | Như hiện nay, thêm cột mã khoa |
| 18 | `DM khoa-giường` | `department_bed_catalogs` |
| 19 | `DM NVYT` | `medical_staffs` |

Tên sheet đều dưới giới hạn 31 ký tự của Excel và không chứa ký tự cấm (`: \ / ? * [ ]`).

### 4.1. Cột của sheet lỗi (sheet 1–16)

Giữ nguyên 18 cột hiện có của `Xml3176ErrorExport`, **chèn cột `Mã khoa` ngay sau `Mã Liên Kết`**, thành 19 cột:

`STT` · `Loại XML` · `STT XML` · `Mã Liên Kết` · **`Mã Khoa`** · `Mã Bệnh Nhân` · `Họ Và Tên` · `Ngày Sinh` · `Mã Thẻ BHYT` · `Ngày Vào` · `Ngày Ra` · `Ngày T.Toán` · `Ngày Y Lệnh` · `Ngày Kết Quả` · `Mã Lỗi` · `Mô Tả` · `Loại lỗi` · `Imported by` · `Exported by`

Cột `Loại XML` được giữ dù trùng tên sheet: người dùng quen dán nhiều sheet vào một bảng tính chung để lọc, và cột này là thứ duy nhất phân biệt nguồn sau khi dán.

Toàn bộ chỉ số cột trong `AfterSheet` (độ rộng, định dạng số) phải dịch sang phải một cột kể từ cột E. Mã hiện tại có một chỗ viết thường `getColumnDimension('l')` — sửa thành `'M'` sau khi dịch.

### 4.2. Cột của sheet lỗi tra cứu thẻ (sheet 17)

Giữ nguyên các cột hiện có, thêm `Mã Khoa` (khoa hồ sơ) ngay sau `Mã điều trị`.

### 4.3. Cột của hai sheet danh mục

- **DM khoa-giường:** mọi cột nghiệp vụ của `department_bed_catalogs` — `ma_cskcb`, `ma_loai_kcb`, `ma_khoa`, `ten_khoa`, `ban_kham`, `giuong_pd`, `giuong_2015`, `giuong_tk`, `giuong_hstc`, `giuong_hscc`, `ldlk`, `lien_khoa`, `tu_ngay`, `den_ngay`. Bỏ `id`, `created_at`, `updated_at`.
- **DM NVYT:** mọi cột nghiệp vụ của `medical_staffs` — bỏ `id`, `created_at`, `updated_at`.

Tiêu đề cột dùng đúng tên trường của chuẩn (viết hoa: `MA_KHOA`, `TEN_KHOA`, `MACCHN`…), vì người dùng đối chiếu hai sheet này với tệp danh mục gửi cổng BHXH.

Sắp xếp: DM khoa-giường theo `ma_cskcb, ma_khoa, tu_ngay`; DM NVYT theo `ma_cskcb, ma_khoa, ho_ten`.

## 5. Nguồn mã khoa

| Sheet | Biểu thức | Nối |
|---|---|---|
| XML1 | `xml1.ma_khoa` | `ma_lk` |
| XML2 | `COALESCE(NULLIF(xml2.ma_khoa, ''), xml1.ma_khoa)` | `ma_lk` + `stt` |
| XML3 | `COALESCE(NULLIF(xml3.ma_khoa, ''), xml1.ma_khoa)` | `ma_lk` + `stt` |
| XML7 | `COALESCE(NULLIF(xml7.ma_khoa_rv, ''), xml1.ma_khoa)` | `ma_lk` |
| XML4, XML5, XML6, XML8–XML15, XMLComplete | `xml1.ma_khoa` | `ma_lk` |
| Lỗi tra cứu thẻ | `xml1.ma_khoa` | `ma_lk` |

XML4 **cố ý không** suy khoa từ XML3 theo mã dịch vụ: một mã dịch vụ có thể xuất hiện nhiều lần ở các khoa khác nhau trong cùng hồ sơ, nên phép suy đó nhập nhằng. Theo Q1, dùng khoa hồ sơ.

Mọi truy vấn đều **LEFT JOIN** bảng nguồn và bảng XML1: thiếu dòng nguồn thì cột mã khoa rỗng, không được làm mất dòng lỗi.

## 6. Bộ lọc

- **Sheet 1–17** cắt theo `Xml3176LocDanhSach::truyVanMaLk($loc, $danhSachCoSo)` — cùng một nguồn với màn danh sách, như bản sửa ngày 11/09/2026.
- **Sheet 18–19** xuất **toàn bộ** dòng, **không lọc theo ngày**, chỉ lọc theo mã cơ sở khi người dùng đang chọn một cơ sở hợp lệ (`LocCoSo::ap`, cột `ma_cskcb`). Giữ các dòng đã hết hiệu lực; cột `tu_ngay`/`den_ngay` cho thấy hiệu lực.

Lý do sheet danh mục không lọc theo hồ sơ: chúng là bảng tra cứu để người dùng dò tên khoa, tên nhân viên từ mã trong các sheet lỗi. Chỉ giữ những mã có mặt trong sheet lỗi thì không dò ra được mã **sai** — mà mã sai chính là thứ người dùng cần tìm.

## 7. Thành phần

| Thành phần | Loại | Trách nhiệm |
|---|---|---|
| `App\Services\Xml3176\Xml3176KhoaNguon` | mới, thuần | Ánh xạ mục 5. `nguon(string $loai): ?array` trả `['bang' => ..., 'cot' => ..., 'noiStt' => bool]` hoặc `null` khi dùng khoa hồ sơ. Không chạm DB. |
| `App\Exports\Xml3176ErrorSheetExport` | mới | Một sheet lỗi cho **một** loại XML. Nhận `($loai, array $loc, array $danhSachCoSo)`. `title()` = `$loai`. Thay thế `Xml3176ErrorExport`. |
| `App\Exports\DmKhoaGiuongSheetExport` | mới | Sheet 18. Nhận `($maCskcb, array $danhSachCoSo)`. |
| `App\Exports\DmNvytSheetExport` | mới | Sheet 19. Nhận `($maCskcb, array $danhSachCoSo)`. |
| `App\Exports\Xml3176ErrorMultiSheetExport` | sửa | Dựng đúng 19 sheet theo thứ tự mục 4. |
| `App\Exports\HeinCardErrorExport` | sửa | Thêm tham số tuỳ chọn `$coMaKhoa = false`. **Mặc định tắt**: `Qd130ErrorMultiSheetExport` dùng chung lớp này và file QĐ130 phải giữ nguyên. |
| `App\Exports\Xml3176ErrorExport` | xoá | Chỉ còn được dùng ở `Xml3176ErrorMultiSheetExport` và hai test; thay bằng `Xml3176ErrorSheetExport`. |

Danh sách 15 loại XML lấy từ một hằng số trong `Xml3176ErrorMultiSheetExport` (`XML1` … `XML15`), **không** lấy từ `Xml3176CheckTypes::LOAI` — hằng số đó chỉ có 12 loại có checker, dùng nó sẽ làm mất các sheet trống mà Q4 yêu cầu.

### 7.1. Truy vấn của `Xml3176ErrorSheetExport`

Dựa trên truy vấn hiện có của `Xml3176ErrorExport`, khác ở bốn điểm:

1. Thêm `where('xml3176_error_results.xml', $loai)`.
2. Nối danh mục mã lỗi bằng **cả** `xml` lẫn `error_code`, thay vì chỉ `error_code` như hiện nay — một mã lỗi trùng ở hai loại XML sẽ nhân đôi dòng.
3. LEFT JOIN bảng nguồn khoa theo `Xml3176KhoaNguon`, chọn thêm cột `ma_khoa` theo biểu thức mục 5.
4. Bỏ `ShouldAutoSize` — độ rộng cột đã đặt cố định trong `AfterSheet`, còn tự co giãn trên sheet XML4 (152.700 dòng) phải đo từng ô.

Giữ nguyên: cắt theo `truyVanMaLk`, sắp xếp `ma_lk, xml, stt`, đánh số `STT` bắt đầu từ 1 **trên từng sheet**.

## 8. Rủi ro và cách kiểm

| Rủi ro | Cách kiểm bắt buộc |
|---|---|
| **Bộ nhớ.** PhpSpreadsheet 1.30.4 giữ cả workbook trong RAM. 318 nghìn dòng × 19 cột ≈ 6 triệu ô. Chia sheet không tăng số ô, nhưng **chưa ai biết bản hiện tại có chạy nổi khối lượng này không**. | Trước khi sửa: đo thời gian và đỉnh bộ nhớ của bản xuất hiện tại trên 1.213 hồ sơ. Sau khi sửa: đo lại trên cùng dữ liệu. Ghi cả hai con số vào báo cáo. Nếu vượt `memory_limit` 4096M mà lớp Export tự đặt thì dừng lại báo, không tự bật cache ô. |
| **Nhân đôi dòng lỗi qua phép nối.** Mục 3 chỉ đo được "không trùng" trên dữ liệu, lược đồ không bảo đảm. | Test so khớp: tổng số dòng của 16 sheet lỗi **bằng đúng** số dòng `xml3176_error_results` trong cùng tập hồ sơ. |
| **Lệch chỉ số cột** sau khi chèn cột mã khoa (định dạng số áp nhầm cột). | Test đọc tiêu đề và vị trí cột ngày trong `AfterSheet` phải khớp nhau. |
| **Làm hỏng file xuất lỗi QĐ130** vì dùng chung `HeinCardErrorExport`. | Test: gọi `HeinCardErrorExport` không kèm tham số mới thì tiêu đề cột và truy vấn giữ nguyên như trước khi sửa. |

## 9. Kiểm thử

**Test đơn vị (không chạm DB):**

- `Xml3176KhoaNguon`: đủ 16 loại (XML1–XML15, XMLComplete); XML2/XML3 nối theo `stt`; XML7 dùng `ma_khoa_rv`; XML4 trả `null`.
- `Xml3176ErrorMultiSheetExport::sheets()`: đúng 19 sheet, đúng thứ tự và tên ở mục 4.
- `Xml3176ErrorSheetExport::headings()`: 19 cột, `Mã Khoa` ở vị trí thứ 5.
- Truy vấn sheet lỗi có điều kiện `xml = ?` và nối danh mục theo cả `xml` lẫn `error_code`.
- `HeinCardErrorExport` giữ nguyên hành vi khi không truyền tham số mới.
- Hai sheet danh mục: có lọc `ma_cskcb` khi mã hợp lệ, không lọc khi mã không hợp lệ hoặc rỗng, không có điều kiện ngày.

**Kiểm trên dữ liệu thật (CSDL dev):**

- Tổng dòng 16 sheet lỗi = số dòng lỗi của tập hồ sơ (mục 8), với ít nhất hai tổ hợp bộ lọc: không lọc và `ma_khoa = K01`.
- Sheet XML2 và XML3: cột mã khoa bằng `ma_khoa` của dòng gốc theo `(ma_lk, stt)` trên một mẫu dòng.
- Sheet XML6, XML12, XML15 tồn tại và chỉ có dòng tiêu đề.
- Đo bộ nhớ và thời gian trước/sau (mục 8).

**Test hiện có phải cập nhật:** `Xml3176ExportLocCoSoTest` (hai ca dùng `Xml3176ErrorExport`), `Xml3176ExportParamsTest::lop_export_khong_con_tu_doc_request` (danh sách tên lớp).

## 10. Ngoài phạm vi

- Nút *Xuất danh sách hồ sơ* và nút *79/80a*.
- File xuất lỗi của màn QĐ130.
- Thêm tên khoa hoặc tên nhân viên vào sheet lỗi — người dùng dò qua hai sheet danh mục.
- Suy khoa cho XML4 từ XML3.
- Bật cache ô của Laravel Excel. Chỉ xem xét nếu phép đo ở mục 8 cho thấy cần, và phải hỏi trước.
