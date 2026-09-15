# Quy tắc mã đối tượng KCB bổ sung (1.1, 3.6, 1.17) — thiết kế

**Ngày:** 15/09/2026
**Nguồn yêu cầu:** tệp `quy tắc đối tượng.xlsx` của người dùng
**Trạng thái:** đã duyệt thiết kế, chờ duyệt spec

## 1. Yêu cầu và phạm vi

Tệp của người dùng liệt kê 5 quy tắc theo `MA_DOITUONG_KCB`:

| Mã | Quy tắc trong tệp | Hiện trạng | Việc |
|---|---|---|---|
| 1.1 | XML1 `MA_DKBD` phải trùng với `MA_CSKCB` | chưa có | **làm** |
| 1.3 | XML1 `GIAY_CHUYEN_TUYEN` không được để trống | đã có (`XML1_DOI_TUONG_KCB_THIEU_GIAY_CHUYEN_TUYEN`, merge 11/09/2026) | không làm |
| 1.5 | XML1 `GIAY_CHUYEN_TUYEN` không được để trống | đã có (cùng mã lỗi trên) | không làm |
| 3.6 | XML1 `MA_KHUVUC` không được để trống | chưa có — hiện chỉ có `ADMIN_INFO_ERROR_MA_KHUVUC` kiểm giá trị K1/K2/K3 **khi có điền** | **làm** |
| 1.17 | XML1 `MA_BENH_CHINH` phải nằm trong danh mục mã bệnh Phụ lục I TT 01/2025 | chưa có, repo **chưa có danh mục** | **làm** |

Trên CSDL dev chưa thấy dòng lỗi `GIAY_CHUYEN_TUYEN` nào vì dữ liệu được nạp trước khi quy tắc đó có; nạp lại hồ sơ là lỗi hiện ra. Không phải lỗi phần mềm.

## 2. Quyết định đã chốt với người dùng

| # | Câu hỏi | Quyết định |
|---|---|---|
| Q1 | Nguồn danh mục Phụ lục I | Tự tra văn bản; thiết kế một danh mục **giống danh mục DVKT cần mã máy** |
| Q2 | Mã 1.1 khi `MA_DKBD` chứa nhiều mã ngăn bởi `;` | **Toàn bộ** mã trong `MA_DKBD` phải bằng `MA_CSKCB` |
| Q3 | Mức độ lỗi | Cảnh báo, không chặn xuất (`critical_error = false`) |
| Q4 | Điều kiện "người dưới 18 tuổi" của Phụ lục I | **Có kiểm**, tính tuổi đủ năm tại `NGAY_VAO` |
| Q5 | Dòng 44 văn bản in `I51.2` | Ghi **cả** `I51.2` và `L51.2` |
| Q6 | Nạp dữ liệu ban đầu | **Chỉ** qua màn Nhập danh mục; kèm tệp Excel mẫu đã đối chiếu |
| — | Phương án lưu danh mục | Mỗi dòng tệp là một mẫu mã (phương án A) |

## 3. Căn cứ văn bản

**Thông tư 01/2025/TT-BYT**, Phụ lục I *"Danh mục một số bệnh được khám bệnh, chữa bệnh tại cơ sở khám bệnh, chữa bệnh cấp chuyên sâu"*, 62 dòng. Nguồn đọc: bản PDF có chữ ký số, 40 trang, Phụ lục I ở trang 19–25 ([benhviendetmay.vn](https://www.benhviendetmay.vn/sites/default/files/2025-01/Th%C3%B4ng%20t%C6%B0-01-2025-TT-BYT.pdf)); đối chiếu thêm bản gõ lại tại [blogbhxh.com](https://www.blogbhxh.com/thong-tu-01-2025-tt-byt-pl1-1191). Hai nguồn khớp mã từng dòng; bản blog có vài lỗi gõ ở tên bệnh, không ở mã.

Ghi chú nguyên văn dưới bảng:

1. Các mã bệnh có 03 ký tự trong Phụ lục này bao gồm tất cả các mã bệnh chi tiết có 04 ký tự. Ví dụ: Mã C25 bao gồm các mã C25.0, C25.1, …, C25.9.
2. Trường hợp có mã bệnh chi tiết đến 04 ký tự, khi xác định mã bệnh phải ghi rõ mã chi tiết 04 ký tự.

Ba đặc điểm quyết định thiết kế:

- **Khoảng mã và mã trừ.** Có dòng là khoảng (`C00`–`C97`, `C81`–`C86` và `C90`–`C96`, `Q20`–`Q28`) và có mã trừ (`C38` trừ `C38.4`, `C83.5`, `D61` trừ `D61.9`, `G04` trừ `G04.2`).
- **Mã trừ chỉ có hiệu lực trong dòng của nó.** Dòng 23 trừ `C83.5`, nhưng dòng 22 (`C00`–`C97`, người dưới 18 tuổi) vẫn bao gồm `C83.5`. Không được gộp thành một danh sách trừ chung.
- **Dòng 44 in `I51.2`** cho "Hoại tử thượng bì nhiễm độc (Lyell/Steven Johnson)". Mã ICD-10 đúng của bệnh này là `L51.2`; `I51.2` là "Đứt cơ nhú". Theo Q5 ghi cả hai.

Ký hiệu `†` sau mã (A17.0†, B42.0†, M32.1†) là ký hiệu phân loại kép của ICD-10, không thuộc mã — bỏ khi ghi vào danh mục.

## 4. Số liệu dữ liệu thật

CSDL dev ngày 15/09/2026, 1.213 hồ sơ:

| Mã | Hồ sơ | Vi phạm nếu áp quy tắc |
|---|---|---|
| 1.1 | 3 | 0 |
| 3.6 | 3 | 0 |
| 1.17 | 10 | 3 — `G44.0`, `N18.5`, `B44.9` không thuộc Phụ lục I; 7 hồ sơ `Z94.0` thuộc dòng 62 |

Số hồ sơ quá ít để kiểm chứng quy tắc 1.1 và 3.6 trên dữ liệu thật. Cả ba quy tắc được xây trên căn cứ văn bản và nạp ở mức cảnh báo.

## 5. Danh mục Phụ lục I

### 5.1. Bảng `benh_pl1_cap_chuyen_sau`

| Cột | Kiểu | Ghi chú |
|---|---|---|
| `id` | increments | |
| `stt` | unsignedSmallInteger | STT dòng trong Phụ lục I (1–62) |
| `ten_benh` | string(1024) | |
| `ma_icd` | string(20) | Mã 3 ký tự (`C25`) hoặc 4 ký tự có dấu chấm (`C79.3`), viết hoa, không `†` |
| `loai` | string(10) | `bao_gom` hoặc `tru` |
| `tuoi_duoi` | unsignedTinyInteger, nullable | `18` với dòng yêu cầu "người dưới 18 tuổi"; rỗng nếu không có điều kiện tuổi |
| `dieu_kien` | text, nullable | Nguyên văn cột "Tình trạng, điều kiện" để người dùng đọc; **phần mềm không kiểm** trừ điều kiện tuổi |
| `is_active` | boolean, default true | Bắt buộc cho cơ chế làm mới trọn bộ của `CatalogImportService` |
| timestamps | | |

Khoá duy nhất `(stt, ma_icd, loai)`. Danh mục **quốc gia**, không theo cơ sở (`theo_co_so = false`).

### 5.2. Quy ước tách dòng văn bản thành dòng danh mục

- Mỗi mã, hoặc mỗi mã 3 ký tự trong một khoảng, là một dòng `bao_gom` cùng STT. Khoảng `C00`–`C97` thành 98 dòng `C00`, `C01`, …, `C97`.
- Mỗi mã trừ là một dòng `tru` cùng STT.
- Dòng 44 có hai dòng `bao_gom`: `I51.2` và `L51.2`.
- `tuoi_duoi = 18` gắn cho **mọi** dòng của STT 22, 30, 31, 32, 58.

Kết quả: 186 dòng — 182 `bao_gom`, 4 `tru`. Chi tiết ở Phụ lục A.

### 5.3. Nhập danh mục

Theo khuôn `dvkt_can_ma_may`:

- `config/catalog_import_mapping.php`, khoá `benh_pl1_cap_chuyen_sau`:
  - `detect_keys` = `['STT', 'MA_ICD', 'LOAI']`
  - `mapping`: `stt ← STT`, `ten_benh ← TEN_BENH`, `ma_icd ← MA_ICD`, `loai ← LOAI`, `tuoi_duoi ← TUOI_DUOI`, `dieu_kien ← DIEU_KIEN`
  - `required_fields` = `['stt', 'ma_icd', 'loai']`
  - `unique_keys` = `['stt', 'ma_icd', 'loai']`
- `config/danh_muc_bhyt.php`: thêm `benh_pl1_cap_chuyen_sau` (tên hiển thị `DM bệnh PL1 TT01 cấp chuyên sâu`, `theo_co_so = false`). Số danh mục tăng **12 → 13**: `tests/Unit/SoDangKyDanhMucTest.php` phải nâng con số chốt cứng.
- `app/Services/CatalogImportService.php`: thêm vào danh sách loại danh mục và vào `LAM_MOI_TRON_BO` (nạp lại thì dòng không còn trong tệp chuyển `is_active = 0`).
- `config/danh_muc_tra_cuu.php`: thêm mục để xem ở khu *Danh mục tra cứu* (chỉ xem, không sửa), sắp xếp `stt, loai, ma_icd`.

Giá trị `LOAI` trong tệp nhập là `BAO_GOM` / `TRU` (viết hoa, dễ gõ); khi lưu chuẩn hoá về chữ thường. `MA_ICD` chuẩn hoá: trim, viết hoa, bỏ `†` và `*`. Dòng có `LOAI` khác hai giá trị trên thì bỏ qua và báo trong kết quả nhập — theo cách `CatalogImportService` đang báo dòng thiếu trường bắt buộc.

### 5.4. Tệp mẫu

`docs/danh-muc/PL1_TT01_2025.xlsx`, một sheet, tiêu đề đúng các cột ở 5.3, đủ 186 dòng Phụ lục A. Người dùng nhập tệp này qua màn Nhập danh mục. Không có migration nạp sẵn dữ liệu (Q6).

## 6. So khớp mã bệnh — `App\Services\Xml3176\Support\BenhPl1Matcher`

Lớp thuần, không chạm DB.

```
kiemTra(string $maBenh, array $dongDanhMuc, ?int $tuoi): array
  trả ['khop' => bool, 'stt_sai_tuoi' => int[]]
```

`$dongDanhMuc` là mảng các dòng `['stt', 'ma_icd', 'loai', 'tuoi_duoi']`.

**Chuẩn hoá `$maBenh`:** trim, viết hoa, bỏ `†`/`*`.

**Một mẫu `ma_icd` khớp `$maBenh` khi:**
- `ma_icd` dài 3 ký tự: `$maBenh` bằng `ma_icd` hoặc bắt đầu bằng `ma_icd . '.'` — ghi chú 1;
- `ma_icd` có 4 ký tự (dạng `X00.0`): `$maBenh` bằng đúng `ma_icd`. Hồ sơ ghi `C79` không khớp dòng `C79.3` — ghi chú 2.

**Một STT khớp khi đồng thời:**
1. có ít nhất một dòng `bao_gom` của STT đó khớp;
2. không có dòng `tru` nào của **chính STT đó** khớp;
3. nếu dòng `bao_gom` khớp có `tuoi_duoi`: `$tuoi === null` (không xác định được tuổi) **hoặc** `$tuoi < tuoi_duoi`.

Khi 1 và 2 đúng nhưng 3 sai, ghi STT vào `stt_sai_tuoi` để mô tả lỗi nói rõ lý do.

**Kết quả:** `khop = true` khi có ít nhất một STT khớp.

**Tuổi** tính ở checker, không ở matcher: tuổi đủ năm từ 8 ký tự đầu `NGAY_SINH` tới 8 ký tự đầu `NGAY_VAO`. Không đọc được một trong hai ngày (rỗng, tháng/ngày `00`, ngày không tồn tại) thì `$tuoi = null`.

## 7. Quy tắc trong `Xml3176Xml1Checker::checkDoiTuongKcb()`

Ba thuộc tính mới trong `config/doi_tuong_kcb.php`, cùng khuôn các thuộc tính đang có (`can_noi_di`, `can_giay_chuyen_tuyen`…):

| Thuộc tính | Gắn cho | Mã lỗi (`generateErrorCode` thêm tiền tố `XML1_`) |
|---|---|---|
| `dkbd_phai_la_cskcb` | `1.1` | `XML1_DOI_TUONG_KCB_DKBD_KHAC_CSKCB` |
| `can_ma_khuvuc` | `3.6` | `XML1_DOI_TUONG_KCB_THIEU_MA_KHUVUC` |
| `benh_pl1` | `1.17` | `XML1_DOI_TUONG_KCB_BENH_NGOAI_PL1` |

### 7.1. `DKBD_KHAC_CSKCB` (mã 1.1)

- Tách `MA_DKBD` bằng `DanhSachPhanCachParser::tach()`.
- Báo lỗi khi `MA_CSKCB` (đã trim) khác rỗng, `MA_DKBD` có ít nhất một mã, và **có mã nào khác** `MA_CSKCB`.
- Mô tả liệt kê các mã lệch.
- `MA_DKBD` rỗng hoặc `MA_CSKCB` rỗng: không báo (thiếu căn cứ).

### 7.2. `THIEU_MA_KHUVUC` (mã 3.6)

- Báo lỗi khi `MA_KHUVUC` (đã trim) rỗng.
- Giá trị khác K1/K2/K3 vẫn do `ADMIN_INFO_ERROR_MA_KHUVUC` lo, không trùng việc.

### 7.3. `BENH_NGOAI_PL1` (mã 1.17)

- `MA_BENH_CHINH` rỗng: không báo (đã có quy tắc riêng về thiếu mã bệnh chính).
- Danh mục không có dòng `is_active` nào: không báo — chưa nạp danh mục thì không có căn cứ.
- Nạp danh mục qua `CommonValidationService::danhMucBenhPl1(): array` (mới) — một truy vấn lấy mọi dòng `is_active = 1`. Gọi **chỉ khi** mã đối tượng có thuộc tính `benh_pl1`, nên chỉ hồ sơ mã 1.17 tốn truy vấn. **Không lưu đệm** giữa các hồ sơ: queue worker sống lâu, đệm sẽ giữ danh mục cũ sau khi người dùng nạp lại.
- Báo lỗi khi `BenhPl1Matcher::kiemTra()` trả `khop = false`. Mô tả:
  - không thuộc STT nào: `Mã đối tượng 1.17 nhưng MA_BENH_CHINH = <mã> không thuộc Phụ lục I Thông tư 01/2025/TT-BYT`;
  - `stt_sai_tuoi` khác rỗng: thêm `(thuộc dòng <STT> nhưng người bệnh <tuổi> tuổi, dòng này chỉ áp dụng người dưới 18 tuổi)`.

### 7.4. Danh mục mã lỗi

Thêm 3 dòng vào `database/seeds/Xml3176ErrorCatalogDoiTuongKcbSeeder.php` (10 → 13 mã), `critical_error = false`, `is_check = true`. Thêm migration mới gọi lại seeder (seeder idempotent). Cập nhật `Xml3176ErrorCatalogDoiTuongKcbSeederTest` lên 13 mã.

Phải chạy migration **trước** khi quy tắc nổ lần đầu: thiếu dòng danh mục thì `getCriticalErrorStatus()` mặc định `true` và chặn xuất hồ sơ.

## 8. Kiểm thử

**Đơn vị — `BenhPl1Matcher`:**
- `C25.3` khớp dòng `C25`; `C25` khớp dòng `C25`; `C79` **không** khớp dòng `C79.3`; `C79.3` khớp.
- `C38.4` không khớp dòng 16; `C38.1` khớp.
- `C83.5`: tuổi 10 → khớp (dòng 22); tuổi 40 → không khớp, `stt_sai_tuoi` chứa 22; tuổi `null` → khớp.
- `C50.9` (ung thư vú): tuổi 10 → khớp dòng 22; tuổi 40 → không khớp.
- Đúng 18 tuổi → không khớp dòng có `tuoi_duoi = 18`; 17 tuổi → khớp.
- `L51.2` và `I51.2` đều khớp.
- `A17.0†` (có ký hiệu) khớp `A17.0`.
- Danh mục rỗng → `khop = false` (checker chịu trách nhiệm im lặng trước khi gọi matcher).

**Đơn vị — tính tuổi:** sinh `20080916` vào `20260915` → 17; vào `20260916` → 18; ngày sinh `20080000` → `null`.

**Checker** (`tests/Unit/Xml3176/Checker/Xml3176Xml1DoiTuongKcbTest.php`, khuôn sqlite hiện có):
- 1.1: `MA_DKBD = MA_CSKCB` → sạch; `MA_DKBD = '01929;37470'` với `MA_CSKCB = '01929'` → lỗi; `MA_DKBD` rỗng → sạch; mã khác 1.1 → không kiểm.
- 3.6: `MA_KHUVUC` rỗng → lỗi; `K1` → sạch; mã khác 3.6 → không kiểm.
- 1.17: danh mục rỗng → sạch; mã trong danh mục → sạch; mã ngoài → lỗi; `MA_BENH_CHINH` rỗng → sạch.
- Test `ho_so_dung_hoan_toan_khong_sinh_loi_nao` hiện có vẫn xanh.

**Danh mục:** test ánh xạ nhập (`detect_keys`, chuẩn hoá `LOAI`/`MA_ICD`), `SoDangKyDanhMucTest` 13 bộ, `DanhMucTraCuuSoDangKyTest`, seeder 13 mã.

**Tệp mẫu:** test đọc `docs/danh-muc/PL1_TT01_2025.xlsx` và khẳng định: 186 dòng dữ liệu; 62 STT phân biệt; đúng 4 dòng `TRU` là `C38.4`/16, `C83.5`/23, `D61.9`/25, `G04.2`/38; STT 22 có 98 dòng; STT 44 có `I51.2` và `L51.2`; `TUOI_DUOI = 18` đúng ở STT 22, 30, 31, 32, 58.

**Dữ liệu thật (sau khi nạp tệp mẫu vào CSDL dev, chỉ đọc hồ sơ):** chạy checker trên 10 hồ sơ mã 1.17 → đúng 3 lỗi `BENH_NGOAI_PL1` (`G44.0`, `N18.5`, `B44.9`).

## 9. Rủi ro

| Rủi ro | Xử lý |
|---|---|
| Chép sai mã từ văn bản vào tệp mẫu | Test tệp mẫu (mục 8) chốt các điểm dễ sai; implementer đối chiếu từng STT với Phụ lục A |
| Người dùng quên nạp danh mục | Quy tắc 1.17 im lặng — không báo oan, nhưng cũng không bảo vệ. Ghi rõ trong readme khi triển khai |
| Quên chạy migration mã lỗi | Mã lỗi mặc định nghiêm trọng, chặn xuất. Readme ghi thứ tự `migrate` → `queue:restart` |
| Điều kiện lâm sàng không kiểm được | Phạm vi cố ý: chỉ kiểm mã và tuổi. Cột `dieu_kien` lưu để người rà soát đọc |
| Quy tắc 1.1 chặt khi đổi thẻ giữa đợt | Theo quyết định Q2; mức cảnh báo nên không chặn xuất |

## 10. Ngoài phạm vi

- Quy tắc 1.3 và 1.5 (đã có).
- Phụ lục II (mã 1.16) và Phụ lục III.
- Kiểm `MA_BENH_KT`.
- Kiểm các điều kiện lâm sàng ngoài tuổi.

## Phụ lục A — 186 dòng danh mục

Cột: STT · `MA_ICD` · `LOAI` · `TUOI_DUOI`. Tên bệnh và điều kiện lấy nguyên văn Phụ lục I khi dựng tệp mẫu.

| STT | Tên bệnh (rút gọn) | Dòng `BAO_GOM` | Dòng `TRU` | `TUOI_DUOI` |
|---|---|---|---|---|
| 1 | Viêm màng não do lao | A17.0 | | |
| 2 | U lao màng não | A17.1 | | |
| 3 | Lao khác của hệ thần kinh | A17.8 | | |
| 4 | Lao hệ thần kinh, không xác định | A17.9 | | |
| 5 | Nhiễm mycobacteria ở phổi | A31.0 | | |
| 6 | Nhiễm histoplasma capsulatum ở phổi cấp tính | B39.0 | | |
| 7 | Nhiễm nấm blastomyces ở phổi cấp tính | B40.0 | | |
| 8 | Nhiễm nấm paracoccidioides ở phổi | B41.0 | | |
| 9 | Nhiễm sporotrichum ở phổi | B42.0 | | |
| 10 | Nhiễm aspergillus ở phổi xâm lấn | B44.0 | | |
| 11 | Nhiễm cryptococcus ở phổi | B45.0 | | |
| 12 | Nhiễm mucor ở phổi | B46.0 | | |
| 13 | Nhiễm mucor lan toả | B46.4 | | |
| 14 | U ác tụy | C25 | | |
| 15 | U ác tuyến ức | C37 | | |
| 16 | U ác của tim, trung thất và màng phổi | C38 | C38.4 | |
| 17 | U ác của xương và sụn khớp ở vị trí khác và không xác định | C41 | | |
| 18 | U ác của màng não | C70 | | |
| 19 | U ác của não | C71 | | |
| 20 | U ác của tủy sống, dây thần kinh sọ và các phần khác của hệ thần kinh trung ương | C72 | | |
| 21 | U ác thứ phát của não và màng não | C79.3 | | |
| 22 | Nhóm u ác tính | C00, C01, …, C97 (98 dòng) | | 18 |
| 23 | U ác của hệ lympho, hệ tạo máu và các mô liên quan | C81, C82, C83, C84, C85, C86, C90, C91, C92, C93, C94, C95, C96 (13 dòng) | C83.5 | |
| 24 | Hội chứng loạn sản tủy xương | D46 | | |
| 25 | Các thể suy tủy xương khác | D61 | D61.9 | |
| 26 | Bệnh tăng đông máu khác (Hội chứng kháng phospho lipid) | D68.6 | | |
| 27 | Hội chứng thực bào tế bào máu liên quan đến nhiễm trùng | D76.2 | | |
| 28 | Bệnh đái tháo đường phụ thuộc insuline (có đa biến chứng) | E10.7 | | |
| 29 | Bệnh đái tháo đường không phụ thuộc insuline (có đa biến chứng) | E11.7 | | |
| 30 | Rối loạn chuyển hóa acid amin thơm | E70 | | 18 |
| 31 | Rối loạn chuyển hóa acid amin chuỗi nhánh và rối loạn chuyển hóa acid béo | E71 | | 18 |
| 32 | Các rối loạn khác của chuyển hóa acid amin | E72 | | 18 |
| 33 | Nhóm rối loạn dự trữ thể tiêu bào | E74, E75, E76 (3 dòng) | | |
| 34 | Rối loạn chuyển hóa đồng (bao gồm cả bệnh Wilson) | E83.0 | | |
| 35 | Thoái hóa dạng bột | E85 | | |
| 36 | Rối loạn trầm cảm tái diễn | F33 | | |
| 37 | Rối loạn ám ảnh nghi thức | F42 | | |
| 38 | Viêm não, viêm tủy và viêm não-tủy | G04 | G04.2 | |
| 39 | Xơ cứng rải rác | G35 | | |
| 40 | Viêm tủy thị thần kinh [Devic] | G36.0 | | |
| 41 | Nhược cơ | G70.0 | | |
| 42 | Bệnh lý võng mạc của trẻ đẻ non | H35.1 | | |
| 43 | Suy tim | I50 | | |
| 44 | Hoại tử thượng bì nhiễm độc (Lyell/Steven Johnson) | I51.2, L51.2 (2 dòng) | | |
| 45 | Hội chứng sau mổ tim | I97.0 | | |
| 46 | Rối loạn chức năng khác sau phẫu thuật tim | I97.1 | | |
| 47 | Bệnh phổi mô kẽ khác | J84 | | |
| 48 | Áp xe phổi và trung thất | J85 | | |
| 49 | Mủ lồng ngực | J86 | | |
| 50 | Bệnh Crohn (viêm ruột từng vùng) | K50 | | |
| 51 | Pemphigus | L10 | | |
| 52 | Viêm mạch mạng lưới | L95.0 | | |
| 53 | Bệnh da tăng bạch cầu trung tính có sốt [Hội chứng Sweet] | L98.2 | | |
| 54 | Bệnh Lupus ban đỏ hệ thống có tổn thương phủ tạng | M32.1 | | |
| 55 | Đái tháo đường sơ sinh | P70.2 | | |
| 56 | Dị tật bẩm sinh khác của não | Q04 | | |
| 57 | Các dị tật bẩm sinh khác của tủy sống | Q06 | | |
| 58 | Nhóm các dị tật bẩm sinh của hệ thống tuần hoàn | Q20, Q21, …, Q28 (9 dòng) | | 18 |
| 59 | Biến dạng bẩm sinh của khớp háng | Q65 | | |
| 60 | Kháng (các) thuốc chống lao | U84.3 | | |
| 61 | Di chứng của hoạt động chiến tranh | Y89.1 | | |
| 62 | Tình trạng của mảnh ghép cơ quan và tổ chức | Z94 | | |

**Kiểm đếm:** STT có một dòng `bao_gom` = 62 − 5 (STT 22, 23, 33, 44, 58) = 57 dòng; cộng 98 + 13 + 3 + 2 + 9 = 125 → **182 dòng `bao_gom`**; cộng **4 dòng `tru`** → **186 dòng**.
