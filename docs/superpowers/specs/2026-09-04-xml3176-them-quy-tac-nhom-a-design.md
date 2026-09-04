# Spec: Bổ sung quy tắc bắt lỗi XML3176 theo chuyên đề giám định (Nhóm A — thuần logic)

**Date:** 2026-09-04
**Status:** Approved (chờ user review spec)

---

## 1. Mục tiêu

Bổ sung ~20 quy tắc bắt lỗi vào module kiểm tra XML3176, đối chiếu với **60 chuyên đề lỗi** mà phần mềm giám định của cơ quan BHXH bắt được trên file hồ sơ thật của bệnh viện (nguồn: `Hoso-Tong-hop-24.8.26 (Giam dinh benh vien).xlsx`, sheet "Tổng Hợp Lỗi", 436 hồ sơ ngày 25/08/2026).

Đợt này **chỉ làm Nhóm A** — các quy tắc **thuần logic, không cần danh mục tham chiếu mới**. Nhóm B (cần bảng danh mục mới: TT39 ngày giường, QĐ622 thời gian CĐHA, đường dùng/dạng bào chế, người ký ủy quyền…) để đợt sau.

## 2. Bối cảnh & kết quả đối chiếu

Hệ thống hiện có ~200 quy tắc trên 13 checker (`Xml3176Xml{1..14}Checker` + `Xml3176CompleteChecker`), ghi lỗi vào `xml3176_error_results`, danh mục tự sinh ở `xml3176_error_catalogs`. Đối chiếu 60 chuyên đề BHXH:

- **~30/60 đã có** (chủ yếu đối chiếu danh mục: mã thuốc, tên thuốc, số đăng ký, hàm lượng, mã bệnh ICD/TT06, mã xã/tỉnh, CCHN, VTYT, mã máy…).
- **~30 còn thiếu**, chia Nhóm A (thuần logic, ~20 — làm đợt này) và Nhóm B (cần danh mục mới, ~10 — đợt sau).

Đã kiểm chứng bằng grep các checker: XML2 **không có xử lý `lieu_dung`** nào; XML5 không kiểm trùng diễn biến; XML4 chỉ kiểm **độ dài** mã/tên chỉ số chứ không kiểm **rỗng**; XML1 `ngay_sinh` chỉ kiểm thiếu chứ không so với ngày vào; XML7 không có `so_ngay_nghi`/ngoại trú.

## 3. Kiến trúc (Hướng 1)

Quyết định D1: **bám pattern checker hiện có**, KHÔNG dựng framework rule-handler chia sẻ với order-check (xem D2 để biết lý do loại Hướng 2).

- Mỗi quy tắc = một hàm `check*(...)` thêm vào checker sẵn có, trả `Collection` các object lỗi `{error_code, error_name, critical_error, description}`, nối vào `checkErrors()`. Không job/route mới; luồng scan hiện tại tự chạy.
- `error_code` đặt theo convention `{XMLTYPE}_{KEY}` qua `generateErrorCode()`; `critical_error` lấy động qua `getCriticalErrorStatus()` (mặc định `true` nếu catalog chưa có).
- **Ba helper thuần** tách ra namespace mới `App\Services\Xml3176\Support\` để test độc lập và (nếu sau này muốn) dùng chung:
  - `LieuDungParser` — parse chuỗi liều dùng định dạng 130.
  - `Xml3176DateHelper` — parse/so/diff chuỗi ngày-giờ `YmdHi`/`YmdHis` (xử lý cả độ rộng 12 và 14), so theo phần ngày.
  - `TextNormalizer` — chuẩn hoá văn bản để so trùng (trim, gộp khoảng trắng, hạ chữ thường, bỏ dấu tuỳ chọn).
- **Đặt quy tắc theo phạm vi dữ liệu:**
  - *Trong một bản ghi* → checker theo loại (đọc thuộc tính `$data`, có thể qua quan hệ `$data->Xml3176Xml1`).
  - *Cần bản ghi anh em cùng `ma_lk`* → truy vấn sibling ngay trong checker theo loại (giống overlap XML3 `Xml3176Xml3Checker.php:525-532`).
  - *Tổng hợp/liên nhiều bảng* → `Xml3176CompleteChecker`.

### 3.1. Vì sao loại Hướng 2 (D2)

Hợp đồng rule order-check (`RuleHandler` = `code()` + `check(OrderContext): Violation[]`) chia sẻ được *hình dạng*, nhưng `OrderContext` gắn chặt HIS (serviceReqId, treatmentId, thời gian số `YmdHis`, `OrderService[]`), còn XML3176 làm việc trên bản ghi MySQL đã import. Hai nguồn dữ liệu khác vòng đời, khác bảng kết quả, khác danh mục. Một engine thống nhất là không khả thi cho đợt này và sẽ phải viết lại ~200 rule cũ. Phần lớn rule XML3176 kiểm cấu trúc tệp XML (không có tương đương HIS). Kết luận: giữ Hướng 1; chỉ tách helper thuần để sẵn dùng lại về sau, không xây framework.

## 4. Danh mục 20 quy tắc (Nhóm A)

Ký hiệu: **#** = mã lỗi BHXH; **Critical mặc định** = `true` (mọi chuyên đề đều là "BÁO LỖI"), người dùng chỉnh sau qua màn quản lý. Mọi rule **bỏ qua (không phát lỗi) khi trường liên quan rỗng/null hoặc ngày sai định dạng** — tránh false-positive và không ném exception.

### 4.1. XML1 — `Xml3176Xml1Checker`

| # | error_code | Điều kiện phát lỗi | Ghi chú |
|---|---|---|---|
| 140 | `XML1_NGAY_SINH_GREATER_NGAY_VAO` | Phần ngày của `ngay_sinh` > phần ngày của `ngay_vao` | So theo ngày; bỏ qua nếu một trong hai rỗng/sai định dạng |

### 4.2. XML2 — `Xml3176Xml2Checker` (họ liều dùng)

Tất cả rút ra từ `LieuDungParser::parse($data->lieu_dung)`. Bỏ qua nếu `lieu_dung` rỗng.

| # | error_code | Điều kiện | Ghi chú |
|---|---|---|---|
| 2345 | `XML2_LIEU_DUNG_INVALID_FORMAT` | `parse().hop_le === false` | Sai định dạng 130 |
| 1638 | `XML2_PRESCRIPTION_EXCEEDS_30_DAYS` | `hop_le` và `so_ngay > config('xml3176.xml2.max_prescription_days', 30)` | Chỉ tính khi parse được |
| 1636/887 | `XML2_LIEU_DUNG_QUANTITY_MISMATCH` | `hop_le` và `abs(tong_luong − so_luong) > eps` | `eps=0.001`; description ghi rõ cao/thấp hơn |
| 2394/2395 | `XML2_LIEU_DUNG_UNIT_INVALID` | `hop_le` và `don_vi` (trong liều) khác `don_vi_tinh` của thuốc (chuẩn hoá) | Điểm mờ nhất — xem Open Question §9 |

### 4.3. XML3 — `Xml3176Xml3Checker`

| # | error_code | Điều kiện | Ghi chú |
|---|---|---|---|
| 2391 | `XML3_NGAY_TH_YL_EQUALS_NGAY_KQ` | `ngay_th_yl` và `ngay_kq` đều có và **bằng nhau** (so chuỗi) | |
| 199 | `XML3_NGAY_KQ_GREATER_NGAY_RA` | `ngay_kq` (XML3) > `ngay_ra` (XML1 qua quan hệ `$data->Xml3176Xml1`) | Bỏ qua nếu thiếu quan hệ hoặc rỗng |
| 2452/2453 | `XML3_EXECUTION_TIME_UNDER_3MIN` | `ma_nhom ∈ config('xml3176.xml3.execution_time_check_groups', [1,3])` và `diffMinutes(ngay_th_yl, ngay_kq) < config('...execution_min_minutes', 3)` | Một mã chung, description nêu nhóm (XN/TDCN); bỏ qua nếu thiếu 1 mốc |
| 2486 | `XML3_SAME_DOCTOR_ORDER_AND_EXECUTE` | `ma_nhom ∈ [1,2,3]` (config) và `ma_bac_si === nguoi_thuc_hien` (cùng khác rỗng) | |

### 4.4. XML4 — `Xml3176Xml4Checker`

Chỉ áp cho **dòng chỉ số xét nghiệm**, nhận diện bằng heuristic: `gia_tri` khác rỗng **hoặc** `don_vi_do` khác rỗng (để không false-positive với dòng CĐHA chỉ có mô tả/kết luận). Xem Open Question §9.

| # | error_code | Điều kiện | Ghi chú |
|---|---|---|---|
| 1274 | `XML4_MA_CHI_SO_EMPTY` | dòng là chỉ số và `ma_chi_so` rỗng | |
| 1276 | `XML4_TEN_CHI_SO_EMPTY` | dòng là chỉ số và `ten_chi_so` rỗng | |
| 2521 | `XML4_XN_MISSING_VALUE_RESULT` | dòng là XN và `gia_tri` rỗng **và** `mo_ta` rỗng **và** `ket_luan` rỗng | |

### 4.5. XML5 — `Xml3176Xml5Checker` (truy vấn sibling)

| # | error_code | Điều kiện | Ghi chú |
|---|---|---|---|
| 436 | `XML5_DIEN_BIEN_DUPLICATE` | Trong cùng `ma_lk`, tồn tại dòng XML5 khác có `TextNormalizer::chuan(dien_bien_ls)` trùng dòng hiện tại | Truy vấn sibling; chỉ phát lỗi ở dòng có `stt` lớn hơn để không báo trùng hai chiều |

### 4.6. XML7 — `Xml3176Xml7Checker`

| # | error_code | Điều kiện | Ghi chú |
|---|---|---|---|
| 2163 | `XML7_SO_NGAY_NGHI_MISMATCH` | Đủ `so_ngay_nghi`, `ngoaitru_tungay`, `ngoaitru_denngay` và `so_ngay_nghi ≠ diffDays(tungay, denngay) + 1` | Inclusive — xem Open Question §9 |
| 2313 | `XML7_NGOAITRU_TUNGAY_BEFORE_NGAY_RA` | `ngoaitru_tungay < ngay_ra` (phần ngày) | |
| 2314 | `XML7_NGOAITRU_DENNGAY_BEFORE_NGAY_RA` | `ngoaitru_denngay < ngay_ra` (phần ngày) | |

### 4.7. XML8 — `Xml3176Xml8Checker`

| # | error_code | Điều kiện | Ghi chú |
|---|---|---|---|
| 2342 | `XML8_TOMTAT_KQ_TOO_SHORT` | `tomtat_kq` khác rỗng và `mb_strlen(trim(tomtat_kq)) < config('xml3176.xml8.tomtat_kq_min_length')` | Ngưỡng — xem Open Question §9 |

### 4.8. Tổng hợp/liên bảng — `Xml3176CompleteChecker`

| # | error_code | Điều kiện | Ghi chú |
|---|---|---|---|
| 891 | `XMLComplete_SECOND_SURGERY_FULL_PAYMENT` | Trong `ma_lk`, xét các dòng XML3 là PTTT cùng ngày (`ma_pttt` có giá trị / thuộc nhóm PTTT theo config), dòng PTTT **thứ 2 trở đi trong ngày** có `tyle_tt_dv == config('...surgery_full_payment_rate', '100')` | CV824/QĐ3176 |
| 2098 | `XMLComplete_XML4_NGAY_KQ_MISMATCH_XML3` | Với cùng `ma_lk` + `ma_dich_vu`, `ngay_kq` ở XML4 khác `ngay_kq` ở XML3 | Khoá ghép — xem Open Question §9 |
| 2498 | `XMLComplete_MISSING_TRANSFER_OR_APPOINTMENT` | `ma_noi_di` (XML1) khác rỗng và **không** có dòng XML13 (giấy chuyển tuyến) **và cũng không** có dòng XML14 (giấy hẹn khám lại) cho `ma_lk` | Thay thế rule cũ hẹp hơn — xem §5 |

## 5. Đối chiếu với rule cũ để không báo trùng

- **#2498 vs rule cũ `XML1_ADMIN_INFO_ERROR_GIAY_CHUYEN_TUYEN`:** rule cũ báo lỗi khi có `ma_noi_di` mà trống trường `giay_chuyen_tuyen` — **không** chấp nhận giấy hẹn khám lại (XML14) thay thế, nên báo dư. Xử lý: thêm rule Complete #2498 chính xác hơn, và **tắt rule cũ** bằng cách seed `is_check=false` cho `XML1_ADMIN_INFO_ERROR_GIAY_CHUYEN_TUYEN` (không xoá code, chỉ tắt kiểm — có thể bật lại qua màn quản lý).
- **#2454** (TG YL trùng dịch vụ khác, chỉ 4 lỗi) trùng ý rule PTTT-duplicate đã có (`XML3_SERVICE_GROUP_PTTT_DUPLICATE`, `Xml3176Xml3Checker.php:961-983`) → **loại khỏi đợt này** (D3) để tránh nhiễu.

## 6. Parser liều dùng 130 (`LieuDungParser`)

Định dạng 130 (theo văn bản lỗi BHXH): *"Số viên/lần * số lần/ngày * số ngày [Viên/ngày]"* — ba số phân tách bởi `*`, tuỳ chọn phần đơn vị trong ngoặc vuông.

`parse(string $lieu): array` trả:
```
['hop_le' => bool, 'sl_lan' => float, 'lan_ngay' => float, 'so_ngay' => int, 'don_vi' => string, 'tong_luong' => float]
```
- `hop_le = true` khi khớp mẫu ba số ngăn bởi `*` (chấp nhận dấu thập phân `.`/`,`, khoảng trắng quanh `*`, phần `[...]` cuối tuỳ chọn). Regex đề xuất (sẽ tinh chỉnh):
  `^\s*\d+([.,]\d+)?\s*\*\s*\d+([.,]\d+)?\s*\*\s*\d+\s*(\[[^\]]*\])?\s*$`
- `tong_luong = sl_lan * lan_ngay * so_ngay`; `don_vi` = nội dung trong `[...]` (nếu có), cắt phần `/ngày`.
- Không khớp → `hop_le=false`, các số = 0.

**Nguồn fixture kiểm thử:** định dạng QĐ130 (mẫu hợp lệ/không hợp lệ tự soạn theo đặc tả) **+ mẫu thật lấy từ bảng `xml3176_xml2s`** khi triển khai. *Lưu ý đính chính:* file Excel giám định **không chứa** giá trị chuỗi `lieu_dung` (chỉ có metadata lỗi), nên không dùng làm fixture được — lấy mẫu thật từ DB của mình.

## 7. Cấu hình, seed, wiring

### 7.1. Cấu hình mới trong `config/xml3176.php`
```php
'xml2' => [ 'max_prescription_days' => 30, 'lieu_dung_quantity_epsilon' => 0.001 ],
'xml3' => [ 'execution_min_minutes' => 3, 'execution_time_check_groups' => [1, 3],
            'same_doctor_check_groups' => [1, 2, 3], 'surgery_full_payment_rate' => '100' ],
'xml8' => [ 'tomtat_kq_min_length' => 20 ],  // ngưỡng tạm — chốt ở review
```
(Gộp vào cấu trúc `xml3176.*` hiện có, không tạo file mới.)

### 7.2. Seed danh mục lỗi (idempotent)
Thêm seeder `Xml3176ErrorCatalogNhomASeeder` (hoặc migration data) dùng `Xml3176ErrorCatalog::updateOrCreate` nạp sẵn ~20 error_code mới (kèm `error_name`, `critical_error=true`, `is_check=true`) để người dùng cấu hình **trước** lần scan đầu; đồng thời set `is_check=false` cho `XML1_ADMIN_INFO_ERROR_GIAY_CHUYEN_TUYEN` (§5). Idempotent nên chạy lại an toàn.

### 7.3. Wiring
Mỗi `check*()` được gọi trong `checkErrors()` của checker tương ứng, gom lỗi vào cùng collection rồi `saveErrors()` như hiện tại. Complete: thêm vào chuỗi kiểm của `Xml3176CompleteChecker::checkErrors()`. Không đổi job/route/màn hình.

## 8. Kiểm thử (tuân thủ bẫy hạ tầng — memory `qlbv-test-infra-gotchas`)

- **Helper thuần** (`LieuDungParser`, `Xml3176DateHelper`, `TextNormalizer`) → **Unit test TDD, không chạm DB**; parser được **mutation-test**. `setUp()` KHÔNG có `: void` (PHPUnit 6.5). Dùng `assertContains` cho chuỗi (không có `assertStringContainsString`).
- **`check*()` nhận sẵn `$data`** (đọc thuộc tính, không query) → test bằng bản ghi giả (model chưa lưu / stdClass), khẳng định collection lỗi trả về — không DB.
- **Rule sibling-query / Complete** → SQLite in-memory theo pattern an toàn trong memory (override chính kết nối `mysql`, chỉ tạo bảng cần) HOẶC verify bằng chạy scan thật. **CẤM `RefreshDatabase`/`DatabaseMigrations`**. Chạy `--testsuite Unit` hoặc file cụ thể, **không** chạy full suite (nhiễm chéo).
- Ca biên bắt buộc: trường rỗng/null → không phát lỗi; ngày sai định dạng → bỏ qua, không ném; so số thực có `eps`; `lieu_dung` biến thể lạ → `hop_le=false` chứ không crash.

## 9. Câu hỏi mở (chốt ở review spec / khi có dữ liệu thật)

1. **Định dạng liều dùng chuẩn (#2345):** xác thực regex parser với mẫu thật lấy từ `xml3176_xml2s`. Nếu HIS ghi bằng `x` thay `*` hay tự do, cần điều chỉnh mẫu.
2. **Ngưỡng "tóm tắt quá ngắn" (#2342):** BHXH không nêu số cụ thể — tạm 20 ký tự.
3. **Heuristic "dòng chỉ số" XML4 (#1274/#1276/#2521):** dùng `gia_tri`/`don_vi_do` khác rỗng để nhận diện dòng xét nghiệm — cần xác nhận không bỏ sót/nhầm với CĐHA.
4. **#2163 inclusive:** `so_ngay_nghi == denngay − tungay + 1` (tính cả hai đầu) hay không `+1` — chốt theo cách BHXH tính.
5. **#2098 khoá ghép XML3↔XML4:** ghép theo `(ma_lk, ma_dich_vu)`; nếu một dịch vụ có nhiều dòng, cần quy tắc ghép rõ hơn.
6. **#2394/#2395 ĐVT liều:** điểm mờ nhất — có thể thu hẹp hoặc tách nếu so ĐVT gây nhiễu.

## 10. Nhật ký quyết định

| # | Quyết định | Chọn | Lý do |
|---|---|---|---|
| D1 | Kiến trúc | Hướng 1 (thêm vào checker sẵn có) | Nhất quán ~200 rule cũ, rủi ro thấp |
| D2 | Framework chia sẻ với order-check | Không | Khác nguồn dữ liệu (HIS live vs XML import), sẽ phải viết lại 200 rule; chỉ tách helper thuần |
| D3 | Phạm vi đợt này | Toàn bộ Nhóm A gồm họ liều dùng | Phủ chuyên đề số lượng lớn nhất (liều 2.906, mã/tên chỉ số 1.532, diễn biến trùng 363) |
| D4 | #2454 | Loại | Trùng rule PTTT-duplicate đã có, chỉ 4 lỗi |
| D5 | #2498 | Thêm rule Complete + tắt rule cũ hẹp hơn | Chấp nhận XML14 (hẹn khám lại) thay thế chuyển tuyến |
| D6 | Seed danh mục lỗi | Có seeder idempotent | Cho cấu hình trước lần scan đầu |
