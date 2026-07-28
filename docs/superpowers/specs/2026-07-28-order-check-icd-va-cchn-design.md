# Order-check: đối chiếu mã ICD và CCHN nhân viên y tế

Ngày: 2026-07-28
Trạng thái: đã chốt thiết kế, chờ viết kế hoạch

## 1. Bối cảnh

Order-check hiện đối chiếu bốn danh mục: ba danh mục BHXH nhập từ Excel (DVKT, thuốc,
VTYT) và một danh mục tự quản (giới hạn giới tính/tuổi). XML3176 còn dùng thêm bốn danh
mục nữa mà order-check chưa chạm tới. Spec này khảo sát cả bốn và bổ sung phần làm được.

## 2. Khảo sát bốn danh mục

Điều kiện để một luật tồn tại được: HIS phải có dữ liệu **ở mức y lệnh**, và danh mục phải
có dòng để đối chiếu. Đo ngày 2026-07-28, cửa sổ 7 ngày.

| Danh mục | Dữ liệu mức y lệnh | Số dòng danh mục | Kết luận |
|---|---|---|---|
| `icd10_categories` | `icd_code` 60.672 phiếu + `icd_sub_code` 39.239 phiếu | 12.229 | **Làm** |
| `medical_staffs` | CCHN qua `his_employee.diploma` — 629/742 nhân viên | **0** | **Làm**, seed tắt |
| `icd_yhct_categories` | `traditional_icd_code` 832 phiếu, `traditional_icd_sub_code` 367 | 4.144 | **Làm**, đo được 0 vi phạm |
| `equipment_catalogs` | `machine_id` rỗng 0/60.793; `his_sere_serv` không có cột máy | 0 | **Loại** |

### 2.1 Chẩn đoán trên phiếu chỉ định

`his_service_req` mang bốn nhóm trường chẩn đoán. Đo trên 61.003 phiếu của 7 ngày:

| Cột | Số phiếu có giá trị | Dùng |
|---|---|---|
| `icd_code` — chẩn đoán chính | 60.672 | **có** |
| `icd_sub_code` — chẩn đoán phụ | 39.242 | **có** |
| `traditional_icd_code` — YHCT chính | 832 | **có**, mục 2.2 |
| `traditional_icd_sub_code` — YHCT phụ | 367 | **có**, mục 2.2 |
| `icd_cause_code` — nguyên nhân | **0** | không có dữ liệu |

`icd_sub_code` là **chuỗi nhiều mã ngăn bởi dấu `;`, có dấu `;` dẫn đầu**:

```
;A04.9
;A04.9;E87.8
;A04.9;J44.8;N17.9
```

38.865/39.242 phiếu có nhiều hơn một mã. Nên việc tách chuỗi và **bỏ phần tử rỗng** là bắt
buộc, không phải phòng thủ. `icd_code` thì luôn là mã đơn (0/61.003 phiếu có dấu `;`).

### 2.2 `icd_yhct_categories` — làm, dù đo được 0 vi phạm

Trường mã bệnh YHCT **có tồn tại** ở mức phiếu (`traditional_icd_code`,
`traditional_icd_sub_code`) — khác với nhận định ban đầu của tài liệu này. Kết quả đo:

| Cột | Phiếu có giá trị | Mã phân biệt | Mã ngoài danh mục | Phiếu sai |
|---|---|---|---|---|
| `traditional_icd_code` | 832 | 24 | **0** | **0 (0,00%)** |
| `traditional_icd_sub_code` | 367 | 17 | **0** | **0 (0,00%)** |

Toàn bộ 41 mã YHCT đang dùng đều nằm trong danh mục.

**Vẫn làm**, và đây là chỗ khác với quyết định bỏ quy tắc "thiếu tên BHYT" ở đợt trước.
Hai con số 0 khác chất nhau:

| | "Thiếu tên BHYT" (đã bỏ) | YHCT (làm) |
|---|---|---|
| Số đo | 0/10.552 dịch vụ | 0/1.199 phiếu |
| Bản chất | **Cấu trúc** — HIS không cho khai mã mà thiếu tên | **Tình cờ** — 41 mã hiện dùng ngẫu nhiên đều hợp lệ |
| Có thể đổi không | Không, trừ khi HIS đổi cách nhập | Có, ngay khi bác sĩ gõ mã mới hoặc danh mục cập nhật |

Đường dữ liệu YHCT đang sống (832 phiếu/tuần), chỉ là đang sạch. XML3176 có
`XML3_INFO_ERROR_MA_BENH_YHCT_INVALID`, nghĩa là BHXH thật sự từ chối mã YHCT ngoài danh
mục — ta chỉ chưa gặp.

Chi phí tăng thêm nhỏ vì hai bảng ICD cùng cấu trúc (`icd_code` + `is_active`) và cùng quy
ước chuỗi ghép: một lớp con, một dòng seed, một nhóm ca kiểm. Câu select của
`HisOrderSource` đằng nào cũng đang sửa để lấy `icd_sub_code`.

**Luật YHCT tra `icd_yhct_categories`, luật ICD10 tra `icd10_categories`. Không bắc cầu
giữa hai bảng** — người dùng chốt ngày 2026-07-28 rằng luật ICD10 không tra sang YHCT để
gợi ý mã tương đương, như `Xml3176Xml3Checker` đang làm.

### 2.3 Vì sao loại `equipment_catalogs`

`his_service_req.machine_id` tồn tại nhưng **rỗng ở cả 60.793 phiếu**. `his_sere_serv`
không có cột mã máy nào. Mã máy chỉ xuất hiện ở khâu xuất XML, không có ở thời điểm ra y
lệnh. Không có dữ liệu thì không có luật.

## 3. Quy mô luật ICD

Gộp chẩn đoán chính và chẩn đoán phụ, đã khử mã trùng giữa hai trường trong cùng một phiếu:

```
phiếu có ít nhất một mã ICD : 60.682
phiếu dính lỗi              :  9.962   (16,42%)
số dòng vi phạm sinh ra     : 11.887
do 287 mã gây ra
```

Tách theo từng trường để thấy chẩn đoán phụ nặng ngang chẩn đoán chính:

| Trường | Phiếu có giá trị | Mã phân biệt | Mã ngoài danh mục | Phiếu sai |
|---|---|---|---|---|
| `icd_code` | 60.473 | 1.041 | 197 | 5.853 (9,68%) |
| `icd_sub_code` | 39.239 | 945 | 177 | 4.989 (12,71%) |

Mã hay gặp nhất: `S06.00` (911 phiếu, chính), `M10.00` (604, phụ), `J96.00` (553, phụ),
`M54.22`, `M47.86`, `I70.20`. Hai tập mã chồng nhau nhiều — hợp lại còn 287 mã.

11.887 dòng vi phạm mỗi tuần là con số lớn, nhưng chỉ do **287 nguyên nhân gốc**. Sửa khai
báo 287 mã trong HIS là dứt điểm, không phải xử lý 11.887 việc rời rạc.

Nguyên nhân: HIS khai mã chi tiết hơn danh mục BHYT — `M47.86` trong khi danh mục chỉ có
`M47.8`. **Không được chuẩn hoá bằng cách cắt bớt ký tự**: danh mục vẫn có 629 mã dài 6 ký
tự và 412 mã dài 7, nên cắt bớt sẽ làm hỏng phép so ở những mã đó. Đây là lỗi khai báo
thật, không phải chênh lệch định dạng.

### 3.1 Đối chiếu với XML3176 để xác nhận không phải báo oan

Chạy lại đúng thuật toán của `Xml3176Xml3Checker` trên `xml3176_xml3s`:

```
mã phân biệt sau khi tách dấu ;  : 174
mã ngoài danh mục                :   7
dòng XML3 dính lỗi               :  74 / 11.310  (0,65%)
```

74 dòng này khớp **chính xác** số dòng `XML3_INFO_ERROR_MA_BENH_INVALID` đang có trong
`xml3176_error_results`. Bảy mã xấu (`S06.00`, `J96.00`, `J96.09`, `I70.20`, `M54.46`,
`M54.87`, …) đều nằm trong nhóm mã hay gặp nhất ở mức y lệnh.

Chênh lệch 0,65% với 9,68% là do cỡ mẫu, không phải do bản chất: XML3176 mới nhập 210 hồ
sơ, còn mức y lệnh phủ 60.793 phiếu. Cùng một loại lỗi, bắt sớm hơn và rộng hơn.

## 4. Phạm vi

### Có làm

- Luật `A_ICD_NOT_IN_CATALOG` — mã ICD (chính + phụ) không có trong `icd10_categories`.
- Luật `A_ICD_YHCT_NOT_IN_CATALOG` — mã YHCT (chính + phụ) không có trong
  `icd_yhct_categories`.
- Luật `A_STAFF_CERT_NOT_IN_CATALOG` — CCHN bác sĩ chỉ định / người thực hiện không có
  trong `medical_staffs` còn hiệu lực.
- Lấy thêm CCHN bác sĩ chỉ định, `icd_sub_code` và hai cột YHCT trong `HisOrderSource`.
- Hai mở rộng nhỏ cho `CatalogLookup`: bỏ lọc hiệu lực khi bảng không có cột ngày, và điều
  kiện lọc bổ sung kiểu `is_active = 1`.
- Migration seed ba luật ở trạng thái **tắt**.
- Đưa ba luật vào lệnh đếm thử `kiemtraylenh:thu`.

### Không làm

- Luật cho `equipment_catalogs` — mục 2.3, HIS không có dữ liệu ở mức y lệnh.
- Chuẩn hoá mã ICD bằng cắt ký tự — mục 3.
- Bắc cầu ICD10 → YHCT để gợi ý mã tương đương như `Xml3176Xml3Checker` — người dùng chốt bỏ.
- Luật cho `icd_cause_code` — rỗng 0/61.010.
- Tổng quát hoá `CatalogLookup` thành tra nhiều khoá — mục 5.3.
- Sửa lỗi lụt vi phạm giả của XML3176 — mục 7, việc riêng, người dùng chưa chọn.

## 5. Thiết kế

### 5.1 Hai luật ICD — một lớp trừu tượng, hai lớp con

Cấp **phiếu chỉ định**: `order_ref_type = 'service_req'`, `order_ref_id = serviceReqId`.
Mã bệnh nằm trên phiếu chứ không trên dòng dịch vụ.

Hai luật cùng hình dạng, khác đúng ba thứ — theo đúng khuôn `BhytCatalogRule` đang có:

| | `A_ICD_NOT_IN_CATALOG` | `A_ICD_YHCT_NOT_IN_CATALOG` |
|---|---|---|
| Bảng | `icd10_categories` | `icd_yhct_categories` |
| Trường chính | `icdCode` | `traditionalIcdCode` |
| Trường phụ | `icdSubCode` | `traditionalIcdSubCode` |
| Nhãn | `danh mục ICD10` | `danh mục ICD YHCT` |

```
Điều kiện chạy:
  1. Bảng danh mục có dòng is_active = 1 (sanSang)
  2. Phiếu có ít nhất một mã sau khi tách

Gom mã:
  ma = tach(truongChinh) ∪ tach(truongPhu)          // khử trùng
  tach(s) = bỏ phần tử rỗng của explode(';', s), mỗi phần tử trim

Với mỗi mã trong tập:
  vi phạm  <=>  mã không có trong bảng danh mục với is_active = 1
```

Việc **bỏ phần tử rỗng** là bắt buộc: `icd_sub_code` có dấu `;` dẫn đầu nên `explode` luôn
sinh một phần tử rỗng đầu tiên. Không bỏ thì mọi phiếu có chẩn đoán phụ đều thành vi phạm.

**Khử trùng giữa hai trường**: một mã xuất hiện ở cả chẩn đoán chính lẫn phụ chỉ sinh một
vi phạm. Đó là cùng một mã khai sai, báo hai lần không giúp người sửa.

Thông điệp nêu rõ chỗ khai để người sửa biết mở đâu:

```
Mã bệnh không có trong danh mục ICD10: S06.00 (chẩn đoán chính)
Mã bệnh không có trong danh mục ICD10: M10.00 (chẩn đoán phụ)
Mã bệnh không có trong danh mục ICD10: J96.00 (chẩn đoán chính và phụ)
Mã bệnh không có trong danh mục ICD YHCT: Y99.9 (chẩn đoán chính)
```

`subKey` là mã bệnh, nên một phiếu nhiều mã sai cho nhiều vi phạm riêng thay vì bị gộp.
Hai luật có `ruleCode` khác nhau nên mã trùng giữa ICD10 và YHCT cũng không đụng nhau.

**Không lọc theo ngày hiệu lực**: cả hai bảng chỉ có `is_active`, không có
`tu_ngay`/`den_ngay`.

### 5.2 `A_STAFF_CERT_NOT_IN_CATALOG`

Cũng cấp phiếu chỉ định. Xét hai người:

| Người | Nguồn CCHN |
|---|---|
| Bác sĩ chỉ định | `his_employee.diploma` theo `sr.request_loginname` — **cần bổ sung** |
| Người thực hiện | `his_employee.diploma` theo `sr.execute_loginname` — đã có (`executeDiploma`) |

```
Điều kiện chạy:
  1. Bảng medical_staffs có dữ liệu (sanSang)
  2. Phiếu có mốc chỉ định đọc được: NgayHieuLuc::tuMocHis($c->intructionTime)

Với mỗi CCHN khác rỗng:
  đạt  <=>  có dòng medical_staffs với macchn = CCHN HOẶC ma_bhxh = CCHN,
            và còn hiệu lực tại ngày chỉ định
```

Chỉ xét khi CCHN **có** giá trị. CCHN rỗng đã là việc của `B_DOCTOR_NO_PRACTICE_CERT`,
không báo chồng hai vi phạm lên cùng một phiếu.

Hai khoá `macchn` / `ma_bhxh` lấy theo `CommonValidationService::isMedicalStaffValid` — giữ
nguyên ngữ nghĩa của XML3176 để hai nơi không cho hai kết luận khác nhau.

`subKey` là `vai_tro:CCHN` (ví dụ `bs:0123456`), nên bác sĩ và người thực hiện cùng sai
trên một phiếu cho hai vi phạm riêng.

Thông điệp: `CCHN bác sĩ chỉ định không có trong danh mục nhân viên y tế còn hiệu lực: 0123456`

**Hai điểm khác XML3176, đều có chủ đích:**

1. **Có lọc hiệu lực** theo `tu_ngay`/`den_ngay` bằng `NgayHieuLuc` đã có. XML3176 không
   lọc — `isMedicalStaffValid` chỉ `exists()`.
2. **Danh mục rỗng thì im lặng.** XML3176 thiếu lá chắn này và đang sinh 31.492 vi phạm
   giả (mục 7).

### 5.3 Hai mở rộng cho `CatalogLookup`

| Mở rộng | Vì sao |
|---|---|
| `$cotTu = null` → bỏ lọc hiệu lực | `icd10_categories` không có cột ngày |
| `$dieuKien = []` → thêm `where` cố định | `icd10_categories` cần `is_active = 1`; áp cả trong `sanSang()` lẫn `nap()` |

Áp `$dieuKien` **cả trong `sanSang()`** là bắt buộc: nếu bảng có 12.229 dòng nhưng tất cả
`is_active = 0` thì `sanSang()` phải trả false, nếu không mọi mã ICD sẽ thành vi phạm.

**Không** tổng quát hoá thành tra nhiều khoá. `medical_staffs` cần tra theo hai cột, giải
quyết bằng **hai thực thể** `CatalogLookup` — một trên `macchn`, một trên `ma_bhxh`, "có"
nghĩa là một trong hai trúng. Đổi lại hai truy vấn mỗi lô thay vì một, nhưng giữ lớp đó
đơn giản và không đụng tới bảy luật đang dùng nó.

## 6. Thay đổi mã nguồn

| Tệp | Việc |
|---|---|
| `Support/CatalogLookup.php` | `$cotTu`/`$cotDen` nhận null; thêm `$dieuKien`; thêm `datRongChoTest()` |
| `Support/MaBenh.php` | **mới** — tách chuỗi mã bệnh, gom chính/phụ, khử trùng |
| `Support/OrderContext.php` | thêm `$icdSubCode`, `$traditionalIcdCode`, `$traditionalIcdSubCode`, `$requestDiploma` |
| `HisOrderSource.php` | select thêm 3 cột ICD; join thêm `his_employee` cho `request_loginname` |
| `RuleHandlers/Clinical/IcdCatalogRule.php` | **mới** — lớp trừu tượng |
| `RuleHandlers/Clinical/IcdNotInCatalogRule.php` | **mới** |
| `RuleHandlers/Clinical/IcdYhctNotInCatalogRule.php` | **mới** |
| `RuleHandlers/Clinical/StaffCertNotInCatalogRule.php` | **mới** |
| `RuleHandlers/ServiceReq/CommonRules.php` | đăng ký ba handler |
| `database/migrations/…_seed_order_check_icd_staff_rules.php` | **mới** — seed 3 luật, tắt |
| `Console/Commands/OrderCheckDryRun.php` | thêm ba handler vào lệnh đếm thử |

Ba luật đều **cấp phiếu**, không lọc theo đối tượng BHYT: mã bệnh sai và CCHN sai là lỗi
hồ sơ bất kể đối tượng nào chi trả.

## 7. Phát hiện ngoài phạm vi

`xml3176_error_results` đang chứa ~36.100 vi phạm sinh ra từ ba danh mục rỗng:

| Mã lỗi | Số dòng | Nguyên nhân |
|---|---|---|
| `XML3_INFO_ERROR_NGUOI_THUC_HIEN_NOT_FOUND` | 16.002 | `medical_staffs` rỗng |
| `XML3_INFO_ERROR_MA_BAC_SI_NOT_FOUND` | 15.490 | `medical_staffs` rỗng |
| `XML3_INFO_ERROR_MA_MAY_NOT_FOUND` | 4.608 | `equipment_catalogs` rỗng |

15.490 đúng bằng **toàn bộ** số dòng `xml3176_xml3s` — 100% số dòng bị gắn lỗi. XML3176
không có cơ chế "danh mục rỗng thì im lặng".

Ngoài phạm vi đợt này. Ghi lại để không rơi. Cần xác nhận máy chủ thật có nạp
`medical_staffs` không trước khi kết luận mức độ.

## 8. Kiểm thử

Cổng: `vendor/bin/phpunit --testsuite Unit`. Bộ `tests/Feature` đỏ sẵn vì lý do môi
trường, không dùng làm cổng.

### `CatalogLookup` sau mở rộng

| Ca | Kỳ vọng |
|---|---|
| `$cotTu = null` | không lọc hiệu lực, mọi dòng đều dùng được |
| `$dieuKien` không dòng nào thoả | `sanSang()` trả false |
| `$dieuKien = ['is_active' => 1]`, nạp lô có cả dòng bật lẫn tắt | chỉ dòng bật được coi là có |
| `datRongChoTest()` | `sanSang()` false, không tra bảng thật |
| Bảy luật BHYT hiện có | không đổi hành vi |

Test **không được** dựa vào việc hai bảng ICD đang rỗng — chúng có 12.229 và 4.144 dòng
thật. Đó là lý do có `datRongChoTest()`.

### `A_ICD_NOT_IN_CATALOG`

| Ca | Kỳ vọng |
|---|---|
| Danh mục rỗng | không vi phạm |
| Bảng có dòng nhưng tất cả `is_active = 0` | không vi phạm (`sanSang` trả false) |
| Mã có trong danh mục, `is_active = 1` | không vi phạm |
| Mã có nhưng `is_active = 0` | 1 vi phạm |
| Mã chính sai | 1 vi phạm, thông điệp ghi "chẩn đoán chính" |
| `icd_code` rỗng / null, không có mã phụ | không vi phạm |
| `icd_sub_code = ';A00'`, `A00` đúng | không vi phạm — **ca chặn lỗi phần tử rỗng** |
| `icd_sub_code = ';A00;B00'`, `B00` sai | 1 vi phạm, ghi "chẩn đoán phụ", chỉ nêu `B00` |
| `icd_sub_code = ';A00;B00'`, cả hai sai | 2 vi phạm, `dedupKey` khác nhau |
| Mã `X99` sai, xuất hiện ở **cả** chính và phụ | **1** vi phạm, ghi "chẩn đoán chính và phụ" |
| Phiếu chỉ có chẩn đoán phụ, không có chính | vẫn xét phần phụ |
| Mã dính khoảng trắng ` A00 ` | tra đúng, thông điệp sạch |
| `icd_sub_code = ';;;'` | không vi phạm, không nổ |

### `A_ICD_YHCT_NOT_IN_CATALOG`

Chạy lại **toàn bộ** bảng ca trên với trường `traditional_icd_code` /
`traditional_icd_sub_code` và bảng `icd_yhct_categories`, cộng thêm:

| Ca | Kỳ vọng |
|---|---|
| Mã có trong `icd10_categories` nhưng không có trong `icd_yhct_categories` | 1 vi phạm — **không bắc cầu giữa hai bảng** |
| Phiếu có mã ICD10 sai **và** mã YHCT sai | 2 vi phạm, `ruleCode` khác nhau |
| Cùng một mã sai xuất hiện ở cả trường ICD10 lẫn trường YHCT | 2 vi phạm, `dedupKey` khác nhau vì khác `ruleCode` |
| Danh mục YHCT rỗng, danh mục ICD10 có dữ liệu | chỉ luật ICD10 chạy |

### `A_STAFF_CERT_NOT_IN_CATALOG`

| Ca | Kỳ vọng |
|---|---|
| Danh mục rỗng | không vi phạm |
| CCHN khớp `macchn` | không vi phạm |
| CCHN khớp `ma_bhxh` | không vi phạm |
| CCHN không khớp cột nào | 1 vi phạm |
| CCHN khớp nhưng dòng đã hết hiệu lực tại ngày chỉ định | 1 vi phạm |
| CCHN rỗng | không vi phạm — nhường `B_DOCTOR_NO_PRACTICE_CERT` |
| Cả bác sĩ và người thực hiện đều sai | 2 vi phạm, `dedupKey` khác nhau |
| Bác sĩ và người thực hiện **cùng một** CCHN sai | 2 vi phạm (hai vai trò), `dedupKey` khác nhau |
| Phiếu không đọc được mốc chỉ định | không vi phạm |

## 9. Triển khai

Cả ba luật seed **tắt**, nhưng vì ba lý do khác nhau — cần ghi rõ để người vận hành biết
cái nào bật được ngay:

| Luật | Vì sao tắt | Bật được khi nào |
|---|---|---|
| `A_ICD_NOT_IN_CATALOG` | Quy mô lớn (11.887 dòng/tuần), cần xác nhận con số trên máy chủ thật | Ngay sau khi chạy `kiemtraylenh:thu` và chấp nhận con số |
| `A_ICD_YHCT_NOT_IN_CATALOG` | Đo được **0 vi phạm** — bật cũng không đổi gì | Ngay, gần như không rủi ro |
| `A_STAFF_CERT_NOT_IN_CATALOG` | `medical_staffs` đang **0 dòng** | Sau khi nạp danh mục nhân viên y tế |

`A_ICD_YHCT_NOT_IN_CATALOG` sẽ im lặng sau khi bật. **Đó là đúng, không phải hỏng** — ghi
lại ở đây để sau này không ai nhìn thấy luật không ra vi phạm rồi đi tìm lỗi.

Trình tự: `php artisan migrate`, rồi `php artisan kiemtraylenh:thu --ngay=7` để đếm mà
không ghi gì, rồi bật từng luật trên màn Quản lý quy tắc.

## 10. Rủi ro

| Rủi ro | Xử lý |
|---|---|
| 11.887 dòng vi phạm/tuần làm ngợp màn hình | Chỉ do 287 mã gốc; seed tắt và đếm thử trước; cân nhắc bật theo khoa |
| 287 mã ICD sai là lỗi khai báo HIS, sửa mất thời gian | Luật chỉ ra đúng 287 nguyên nhân gốc, không phải 11.887 việc rời rạc |
| `medical_staffs` nạp thiếu → báo oan hàng loạt | `sanSang()` chặn ở mức bảng; lọc hiệu lực chặn tiếp ở mức dòng |
| Quên lọc phần tử rỗng khi tách `;` → mọi phiếu có chẩn đoán phụ thành vi phạm | Có ca kiểm riêng cho `';A00'` và `';;;'` |
| Thêm join `his_employee` làm chậm truy vấn quét | Join theo `loginname`, cùng dạng join đã có cho `execute_loginname`; đo lại thời gian quét sau khi sửa |
| Luật YHCT im lặng bị tưởng là hỏng | Ghi rõ ở mục 9 và trong chú thích lớp |
