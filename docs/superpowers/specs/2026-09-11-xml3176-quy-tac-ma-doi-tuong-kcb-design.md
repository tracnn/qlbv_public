# Quy tắc XML3176 kiểm mã đối tượng đến khám bệnh, chữa bệnh

**Ngày:** 2026-09-11
**Module:** XML3176 — tiền giám định
**Nguồn:** Phụ lục 1 "Danh mục mã đối tượng đến khám bệnh, chữa bệnh" (Quyết định của Bộ trưởng Bộ Y tế, 2025), đối chiếu thêm Thông tư 01/2025/TT-BYT
**Trạng thái:** Design đã duyệt, chờ lập plan

## 1. Mục tiêu

Dựng bộ quy tắc kiểm `MA_DOITUONG_KCB` của bảng XML1 dựa trên danh mục mã đối tượng do Bộ Y tế ban hành, và vá một khiếm khuyết tiềm ẩn trong hai quy tắc sẵn có đang suy tính chất đối tượng từ **tiền tố mã** thay vì tra danh mục.

## 2. Danh mục có gì

**27 mã**: `1.1`–`1.7`, `1.11`–`1.18`, `2`, `3.1`, `3.2`, `3.3`, `3.6`, `7`, `7.2`, `7.3`, `7.4`, `8`, `9`, `10`. Không có `1.8`–`1.10`, không có `3.4`, `3.5`, không có mã `4`/`5`/`6`, **không có `7.1`**.

Số thứ tự trong văn bản chạy 1→28 nhưng **nhảy qua STT 22** — đã kiểm bằng cả trích văn bản lẫn trích bảng: dòng 21 là mã `7`, dòng kế tiếp là 23 với mã `7.2`. Đây là lỗi đánh số của chính văn bản nguồn, nên danh mục chỉ có 27 mã.

Danh mục không chỉ liệt kê mã hợp lệ mà **gắn mức hưởng vào từng mã**:

| Mã | Mức hưởng theo danh mục |
|---|---|
| `3.1` | **40% nội trú, 0% ngoại trú** |
| `1.13`, `1.14`, `1.18` | **0% đến 30/6/2026; 50% từ 01/7/2026** |
| `1.2` | 100% **không phụ thuộc mức hưởng trên thẻ BHYT** |
| `9` | **0%** — người bệnh không KCB BHYT |
| Các mã còn lại | 100% theo phạm vi quyền lợi, mức hưởng |

Ghi chú cuối danh mục: *"Trường hợp một người bệnh có thể áp dụng nhiều hơn 01 mã đối tượng thì cơ sở lựa chọn mã theo thứ tự sắp xếp từ trên xuống dưới."* Thứ tự này là căn cứ của quy tắc R7 ở §6.

## 3. Hiện trạng đã đo, không suy đoán

Đo trên 1.213 hồ sơ thật (1.153 hồ sơ được rà sau khi loại hồ sơ dịch vụ).

**Phân bố mã đối tượng — cả 9 mã đang dùng đều thuộc danh mục:**

| Mã | Hồ sơ | Mã | Hồ sơ |
|---|---|---|---|
| `1.5` | 761 | `9` | 60 |
| `1.3` | 184 | `1.7` | 14 |
| `2` | 91 | `1.17` | 10 |
| `3.1` | 87 | `1.1`, `3.6` | 6 |

**Ba quan hệ khớp tuyệt đối** giữa danh mục và dữ liệu — đây là bằng chứng danh mục mô tả đúng thực tế của cơ sở, không phải một bảng xa lạ:

- 60/60 hồ sơ mã `9` (không KCB BHYT) **đều không có thẻ BHYT**.
- 251/251 hồ sơ nhóm "tự đến", cấp cứu và không BHYT (`1.17`, `3.1`, `3.6`, `2`, `9`) **đều bỏ trống `MA_NOI_DI`**.
- 3/3 hồ sơ mã `1.1` (đúng nơi đăng ký ban đầu) **đều có `MA_DKBD` chứa `MA_CSKCB`**.

**Vi phạm đo được:** tổng cộng **5 hồ sơ** trên 1.213 — 3 hồ sơ mã `1.3` thiếu `MA_NOI_DI`, 2 hồ sơ đúng nơi đăng ký ban đầu mà khai sai mã. Đây là một đợt thiên về lưới chặn, không phải đợt thu hoạch lỗi. Giá trị lớn nhất nằm ở bản vá §7.

(Con số này đã trừ 3 hồ sơ không có thẻ BHYT: chúng bị loại bởi chính điều kiện `T_BHTT > 0` mà §6.1 đặt thêm để tránh bắt oan ca cấp cứu chưa xuất trình thẻ.)

## 4. Những hướng đã thử và bỏ, kèm lý do

Phần này quan trọng ngang phần quy tắc: nó ghi lại các ngả cụt để người sau không đi lại.

### 4.1 Bỏ: dùng XML13 / XML14 làm bằng chứng đầu vào

Ý tưởng ban đầu: mã `1.3` (đến có phiếu chuyển) phải có XML13; mã `1.5` (đến theo phiếu hẹn) phải có XML14.

**Sai về nghiệp vụ.** `MA_DOITUONG_KCB` mô tả **đường vào** của người bệnh, còn XML13/XML14 là **chứng từ đầu ra** do chính cơ sở cấp khi kết thúc điều trị. Phiếu người bệnh *mang đến* và phiếu cơ sở *cấp đi* là hai thứ khác nhau.

Số đo xác nhận: mã `1.3` có 166/184 hồ sơ (90,2%) không có XML13 — không phải vì thiếu chứng từ mà vì phiếu chuyển do **cơ sở khác** cấp, cơ sở này không xuất XML13 cho nó.

### 4.2 Bỏ: mã `1.5` phải có `MA_NOI_DI`

Đo được 164/761 hồ sơ (21,6%) thiếu. Nhưng **Điều 11 Thông tư 01/2025/TT-BYT** bác căn cứ này:

> Cơ sở KBCB ghi nội dung, lịch hẹn khám lại trong Phiếu hẹn khám lại theo mẫu Phụ lục V, **hoặc ghi trong đơn thuốc, giấy ra viện** cho người bệnh.

Phiếu hẹn do **chính cơ sở điều trị** cấp sau mỗi lượt KCB, nên không có "cơ sở chuyển đi" theo nghĩa chuyển tuyến; và lịch hẹn có thể nằm ngay trong đơn thuốc hoặc giấy ra viện, không nhất thiết là chứng từ riêng.

Yêu cầu `MA_NOI_DI` cho khám lại bắt nguồn từ **định nghĩa trường trong QĐ 130**, mà định nghĩa đó dẫn chiếu **khoản 6 Điều 6 Thông tư 30/2020/TT-BYT** — đúng thủ tục mà TT 01/2025 đã thay thế. Dữ liệu cũng không cho quy luật nào: trong 597 hồ sơ mã `1.5` *có* `MA_NOI_DI`, 466 khác `MA_DKBD`, 131 bằng `MA_DKBD`, chỉ 1 bằng `MA_CSKCB`.

### 4.3 Bỏ: dùng `GIAY_CHUYEN_TUYEN` làm căn cứ

Chỉ **7/1.213 hồ sơ (0,6%)** có giá trị ở trường này. Bất kỳ quy tắc nào đòi nó sẽ báo oan 99%.

### 4.4 Không khả thi: kiểm hạn phiếu chuyển

Điều 9, Điều 10 TT 01/2025: phiếu chuyển có giá trị **10 ngày làm việc** kể từ ngày ký; riêng bệnh mạn tính thuộc Phụ lục III thì **01 năm**. Đây là quy tắc có giá trị giám định cao.

**Nhưng XML1 không mang ngày ký phiếu chuyển** — chỉ có *số* phiếu (`GIAY_CHUYEN_TUYEN`, mà 99,4% hồ sơ bỏ trống). Ngày ký nằm ở XML13, là chứng từ cơ sở cấp khi chuyển đi, không phải phiếu người bệnh mang đến. Không đủ dữ liệu, bỏ.

### 4.5 Không dựng: "chỉ được hẹn khám lại một lần"

Điều 11 TT 01/2025 quy định *"Chỉ được hẹn khám lại một lần sau khi kết thúc một đợt điều trị"*. Đo: **0/1.213 hồ sơ** khai từ hai ngày tái khám trở lên — đã tuân thủ.

Quan trọng hơn, **hai văn bản vênh nhau**: QĐ 130 cho phép `NGAY_TAI_KHAM` chứa nhiều ngày ngăn bởi `;` khi người bệnh khám nhiều chuyên khoa, còn TT 01/2025 nói chỉ một lần. Không chọn bên khi hai căn cứ mâu thuẫn và dữ liệu chưa có ca nào.

### 4.6 Đã kiểm chứng: `MA_NOI_DI` là trường đầu vào, `MA_NOI_DEN` là trường đầu ra

Có nghi vấn rằng `MA_NOI_DI` gắn với `MA_LOAI_RV` (kết quả lúc kết thúc điều trị). Số đo bác bỏ điều đó và chỉ vào `MA_NOI_DEN`:

| `MA_LOAI_RV` | Hồ sơ | Có `MA_NOI_DEN` | Có `MA_NOI_DI` |
|---|---|---|---|
| 1 — ra viện | 1.152 | **0 (0%)** | 755 (65,5%) |
| 2 — chuyển theo yêu cầu chuyên môn | 45 | **45 (100%)** | 18 (40,0%) |
| 4 — xin ra viện | 16 | 0 (0%) | 6 (37,5%) |

`MA_NOI_DEN` theo `MA_LOAI_RV` tuyệt đối, không một ngoại lệ — đó là trường đầu ra. `MA_NOI_DI` không có quy luật nào theo `MA_LOAI_RV`, nhưng bám chặt `MA_DOITUONG_KCB` (98% ở mã `1.3`, **0/251** ở nhóm tự đến/cấp cứu/không BHYT).

Khớp đúng ví dụ nguyên văn của QĐ 130: tại cơ sở **chuyển đi** ghi `MA_NOI_DEN`, để trống `MA_NOI_DI`; tại cơ sở **nhận** ghi `MA_NOI_DI`, để trống `MA_NOI_DEN`.

**Không quy tắc nào trong spec này dùng `MA_NOI_DEN`.**

## 5. Kiến trúc

### 5.1 Danh mục đặt ở đâu

Tệp cấu hình mới `config/doi_tuong_kcb.php` — không phải bảng CSDL.

Lý do: 28 mã, thay đổi bằng quyết định của Bộ Y tế chứ không phải theo từng cơ sở, và không có tệp Excel nguồn để nhập. Dựng bảng + đường nhập + màn hình cho 28 dòng tĩnh là thừa. Khác hẳn `dvkt_can_ma_may` (3.471 dòng, có tệp nguồn, thay theo đợt).

Cấu trúc mỗi mục khai đủ thứ quy tắc cần, không hơn:

```php
return [
    '1.1' => ['ten' => 'Đến KCB đúng nơi đăng ký ban đầu', 'tu_den' => false, 'dung_dkbd' => true],
    '1.2' => ['ten' => '...', 'tu_den' => false, 'muc_huong_co_dinh' => 100],
    '1.3' => ['ten' => 'Đến KCB có phiếu chuyển cơ sở KCB', 'tu_den' => false, 'can_noi_di' => true],
    '3.1' => ['ten' => '...', 'tu_den' => true, 'ngoai_tru_khong_huong' => true],
    '9'   => ['ten' => 'Người bệnh không KCB BHYT', 'tu_den' => false, 'khong_bhyt' => true],
    // ... đủ 28 mã
];
```

Khoá vắng mặt nghĩa là thuộc tính đó không áp dụng. Mã không có trong tệp là mã ngoài danh mục (R1). Tệp khai đúng **27** mã của §2 — không tự thêm `7.1` dù số thứ tự văn bản nhảy qua 22.

### 5.2 Helper thuần `DoiTuongKcbCatalog`

`app/Services/Xml3176/Support/DoiTuongKcbCatalog.php`, nhận danh mục **truyền vào** để giữ tính thuần (người gọi đọc config rồi truyền, đúng lối `TienTeCalculator` đã dùng):

```php
coTrongDanhMuc($ma, array $danhMuc): bool
thuocTinh($ma, array $danhMuc, string $khoa, $macDinh = null)   // đọc một thuộc tính
laTuDen($ma, array $danhMuc): bool
chuanHoa($ma): string    // trim; KHÔNG cắt hậu tố, KHÔNG khớp tiền tố
```

`chuanHoa()` cố ý chỉ `trim`: bài học từ chính bản vá §7 là khớp tiền tố trên mã phân cấp có dấu chấm là nguồn gốc của lỗi.

### 5.3 Quy tắc đặt ở đâu

| Quy tắc | Checker | Vì sao |
|---|---|---|
| R1, R2, R4, R5, R6, R7, R8 | `Xml3176Xml1Checker` | Chỉ cần các trường của XML1 (`ma_doituong_kcb`, `ma_noi_di`, `ma_the_bhyt`, `ma_dkbd`, `ma_cskcb`, `ma_loai_kcb`, `t_bhtt`) |
| R9, R10, R11 | `Xml3176CompleteChecker` | Cần `MUC_HUONG` (chỉ có ở từng dòng XML2/XML3) hoặc dòng dịch vụ khám của XML3 |

`xml3176_xml1s` **không có cột `muc_huong`** — đã kiểm trên lược đồ. Đó là lý do R9/R10 phải nằm ở checker tổng thể.

**Cảnh báo vận hành cho R9–R11:** `Xml3176CompleteChecker` chỉ chạy khi `organization.xml_3176_not_check = false`. Trên máy dev khoá này từng là `true` suốt và làm 26 quy tắc `XMLComplete_` nằm im không ai biết. Ba quy tắc này sẽ câm nếu khoá đó bật.

## 6. Danh sách quy tắc

Mọi quy tắc dùng khuôn sẵn có: `generateErrorCode($key)` + đối tượng lỗi bốn khoá.

**Guard chung cho tất cả:** `MA_DOITUONG_KCB` rỗng thì im lặng — đã có `ADMIN_INFO_ERROR_MA_DOITUONG_KCB` lo việc đó.

### 6.1 Nhóm ở `Xml3176Xml1Checker` (7 quy tắc)

| Mã lỗi (sau `XML1_`) | Điều kiện sinh lỗi | Vi phạm đo được |
|---|---|---|
| `DOI_TUONG_KCB_NGOAI_DANH_MUC` | Mã khác rỗng và không có trong danh mục 28 mã | 0 |
| `DOI_TUONG_KCB_THIEU_NOI_DI` | Mã có `can_noi_di` (hiện chỉ `1.3`) mà `MA_NOI_DI` rỗng | **3/184** |
| `DOI_TUONG_KCB_TU_DEN_CO_NOI_DI` | Mã có `tu_den` (`1.11`–`1.18`, `3.1`, `3.2`, `3.3`, `3.6`) mà `MA_NOI_DI` khác rỗng | 0/100 |
| `DOI_TUONG_KCB_THIEU_THE_BHYT` | Mã **không** có `khong_bhyt`, `MA_THE_BHYT` rỗng, **và** `T_BHTT > 0` | 0 (xem ghi chú) |
| `DOI_TUONG_KCB_KHONG_BHYT_CO_THE` | Mã có `khong_bhyt` (mã `9`) mà `MA_THE_BHYT` khác rỗng | 0/60 |
| `DOI_TUONG_KCB_DUNG_DKBD_SAI_MA` | `MA_DKBD` chứa `MA_CSKCB` mà mã khai không phải `1.1` hoặc `1.2` | **2** |
| `DOI_TUONG_KCB_31_NGOAI_TRU_CO_BHTT` | Mã có `ngoai_tru_khong_huong` (mã `3.1`), hồ sơ **không** thuộc `treatment_type_inpatient`, và `T_BHTT > 0` | 0 |

Ghi chú cho `DOI_TUONG_KCB_DUNG_DKBD_SAI_MA`: `MA_DKBD` có thể chứa nhiều mã ngăn bởi `;` (chuẩn cho phép khi người bệnh đổi thẻ giữa đợt), nên phải tách bằng `DanhSachPhanCachParser::tach()` rồi mới so, không so chuỗi thô.

Ghi chú cho `DOI_TUONG_KCB_THIEU_THE_BHYT`: **cấp cứu là ngoại lệ đã biết** — chuẩn cho phép tra cứu thẻ trước khi người bệnh ra viện. Cả 3 hồ sơ đo được đều có `T_BHTT` rỗng, tức chưa đề nghị quỹ thanh toán. Quy tắc vì vậy **chỉ nổ khi `T_BHTT > 0`**: đòi quỹ trả mà không có thẻ mới là mâu thuẫn thật. Với điều kiện này, 3 hồ sơ nói trên **không** bị bắt — con số thật của quy tắc là 0. Giữ nguyên chủ đích đó.

### 6.2 Nhóm ở `Xml3176CompleteChecker` (3 quy tắc)

| Mã lỗi (sau `XMLComplete_`) | Điều kiện | Vi phạm |
|---|---|---|
| `DOI_TUONG_KCB_MUC_HUONG_CO_DINH` | Mã có `muc_huong_co_dinh` (mã `1.2` = 100) mà có dòng XML2/XML3 khai `MUC_HUONG` khác giá trị đó | 0 hồ sơ |
| `DOI_TUONG_KCB_MUC_HUONG_THEO_MOC` | Mã `1.13`/`1.14`/`1.18`: `NGAY_VAO` từ 01/7/2026 trở đi mà `MUC_HUONG` khác 50; trước mốc đó mà `T_BHTT > 0` | 0 hồ sơ |
| `DOI_TUONG_KCB_LINH_THUOC_CO_TIEN_KHAM` | Mã `7`, `7.2`, `7.3`, `7.4`, `10` mà XML3 có dòng thuộc `examination_group_code` (= `[13]`) | 0 hồ sơ |

Mốc 01/7/2026 khai trong config, không viết cứng trong mã.

**Ba quy tắc này chưa có hồ sơ nào để chạy** — không mã nào trong `1.2`, `1.13`, `1.14`, `1.18`, `7`, `7.2`, `7.3`, `7.4`, `10` xuất hiện trong 1.213 hồ sơ. Chúng là lưới chặn cho tương lai, và căn cứ của chúng lấy từ **cột `MUC_HUONG` của danh mục**, chưa đối chiếu văn bản gốc (Nghị định 188/2025/NĐ-CP cho mốc 50%, Phụ lục I/II TT 01/2025 cho phạm vi các mã `1.1x`). Nếu về sau có hồ sơ dùng các mã này, **phải đối chiếu văn bản gốc trước khi tin kết quả**.

### 6.3 Cố ý không làm

- **Không kiểm thứ tự ưu tiên mã** ngoài trường hợp `1.1`/`1.2` ở R7. Ghi chú danh mục nói chọn mã theo thứ tự từ trên xuống, nhưng xác định "hồ sơ này *đáng lẽ* thuộc mã nào" đòi hỏi biết cấp chuyên môn cơ sở, chẩn đoán có thuộc Phụ lục I/II không, người bệnh có thuộc diện dân tộc thiểu số/hộ nghèo không — dữ liệu XML không mang đủ.
- **Không làm lại "mã `3.1` nội trú hưởng 40%"** — `Xml3176CompleteChecker::checkMucHuong()` đã cài rồi. R8 chỉ bù đúng nhánh ngoại trú mà hàm đó cố ý bỏ qua (`return` sớm với chú thích "ngoài phạm vi: trái tuyến ngoại trú").

## 7. Bản vá: cấu hình khớp tiền tố đang sai

`config/xml3176.php` khoá `xml1.ma_doituong_kcb_trai_tuyen = ['3']`, và cả hai nơi dùng nó đều khớp bằng `strpos($ma, $prefix) === 0`:

- `Xml3176Xml1Checker` — quy tắc `ADMIN_INFO_ERROR_MA_DOITUONG_KCB_INVALID`
- `Xml3176CompleteChecker::checkMucHuong()` — nhánh trái tuyến nội trú tuyến trung ương

Theo danh mục, **chỉ `3.1`** bị giảm mức hưởng. `3.2`, `3.3`, `3.6` đều hưởng **100%**. Cấu hình hiện tại coi cả ba là trái tuyến.

**Sửa:** đổi giá trị thành `['3.1']` và đổi cách khớp từ tiền tố sang **khớp đúng bằng** (`in_array($ma, $danhSach, true)`). Chỉ đổi giá trị mà giữ `strpos` là chưa đủ: `'3.1'` vẫn sẽ khớp tiền tố với một mã `3.10` giả định về sau.

**Hiện chưa báo oan.** `checkMucHuong` còn một chốt nữa là phải tra được `tuyen_cmkt` của cơ sở là tuyến trung ương, mà danh mục cơ sở chưa nạp đủ. Ba hồ sơ mã `3.6` vì thế đang lọt. Khi danh mục cơ sở được nạp đủ, chúng sẽ bị báo sai ngay — đây là quả mìn hẹn giờ, và là phần có giá trị nhất của đợt này.

## 8. Danh mục mã lỗi

10 mã mới phải có dòng trong `xml3176_error_catalogs`. **Thiếu dòng thì `getCriticalErrorStatus()` trả mặc định `true`**, quy tắc nổ lần đầu sẽ tự ghi dòng danh mục ở mức nghiêm trọng và **chặn xuất XML** — bẫy đã cắn ở các đợt trước.

Nạp bằng **migration gọi seeder** như đợt trước, không phải lệnh chạy tay: điều kiện "nhớ chạy seeder" không được phép chỉ tồn tại trong trí nhớ người triển khai.

Cả 10 mã đặt `is_check = true`, `critical_error = false`.

## 9. Kế hoạch kiểm thử

**Unit thuần — `DoiTuongKcbCatalog`:** mã có trong danh mục; mã lạ; mã rỗng và `null`; đọc thuộc tính có và không có; `chuanHoa` chỉ trim; **`3.1` không được khớp `3`, và `3` không được khớp `3.1`** (ca khoá chính bài học của §7).

**Unit có sqlite — nhóm XML1:** mỗi quy tắc một ca sinh lỗi, một ca không, một ca guard im lặng (mã rỗng). Riêng `DOI_TUONG_KCB_DUNG_DKBD_SAI_MA` thêm ca `MA_DKBD` nhiều mã ngăn bởi `;`. Riêng `DOI_TUONG_KCB_THIEU_THE_BHYT` thêm ca không thẻ nhưng `T_BHTT` rỗng ⇒ **không** báo.

**Unit có sqlite — nhóm Complete:** mã `1.2` với dòng XML3 khai mức hưởng 80 ⇒ báo; khai 100 ⇒ im. Mã `1.13` với `NGAY_VAO` trước và sau mốc 01/7/2026. Mã `7` với dòng XML3 nhóm 13 ⇒ báo.

**Ca khoá bản vá §7:** hồ sơ mã `3.6`, nội trú, cơ sở tuyến trung ương, mức hưởng 100 ⇒ **không** được báo `MUC_HUONG_TRAI_TUYEN_TW`. Trước khi vá, ca này đỏ.

**Hồi quy:** toàn bộ `tests/Unit/Xml3176` và `tests/Unit/Import` phải xanh.

**Cấm `RefreshDatabase`** — sẽ xoá sạch CSDL dev `qlbv`.

## 10. Thứ tự triển khai

1. `php artisan migrate` — migration tự nạp danh mục 10 mã lỗi.
2. `php artisan config:clear` — có tệp config mới và một khoá đổi giá trị.
3. **`php artisan queue:restart`** — worker là tiến trình thường trú, giữ cả mã lẫn cấu hình trong bộ nhớ. Bỏ bước này thì quy tắc mới im lặng chạy bằng mã cũ, không dấu hiệu gì. Đã mất một vòng vì đúng chỗ này ở đợt trước.
4. Rà lại một lô hồ sơ. Đối chiếu: `DOI_TUONG_KCB_THIEU_NOI_DI` khoảng 3 hồ sơ, `DOI_TUONG_KCB_DUNG_DKBD_SAI_MA` khoảng 2, các mã còn lại 0.
5. Nếu mã nào báo trên **20% số hồ sơ** thì dừng xem xét — gần như chắc chắn là báo oan hoặc bộ xuất sai hệ thống.

## 11. Rủi ro

- **Đợt này bắt được 5 hồ sơ trên 1.213.** Giá trị chủ yếu là lưới chặn và bản vá §7, không phải thu hoạch lỗi. Cần hiểu đúng kỳ vọng trước khi triển khai.
- **Ba quy tắc ở §6.2 chưa có hồ sơ nào để chạy**, và căn cứ lấy từ cột `MUC_HUONG` của danh mục chứ chưa từ văn bản gốc. Rủi ro sai ngữ nghĩa là có thật; giảm thiểu bằng `critical_error = false` và bằng bước 5 của §10.
- **Danh mục có thể là bản dự thảo.** Tệp PDF ghi "Quyết định số ___/QĐ-BYT ngày ___ tháng ___ năm 2025" với số và ngày để trống. Người dùng xác nhận đã ban hành và đang áp dụng; nếu sau này phát hiện ngược lại thì `DOI_TUONG_KCB_NGOAI_DANH_MUC` là quy tắc chịu ảnh hưởng đầu tiên.
- **Danh mục khai trong config** nên sửa phải qua triển khai mã, không sửa được trên màn hình. Đánh đổi có chủ đích cho 28 mã tĩnh; nếu Bộ Y tế bổ sung mã thường xuyên thì nên chuyển sang bảng danh mục.
- **`MA_DKBD` nhiều mã ngăn bởi `;`** — không tách đúng sẽ làm `DOI_TUONG_KCB_DUNG_DKBD_SAI_MA` bỏ sót. Đã có `DanhSachPhanCachParser` từ đợt trước.
