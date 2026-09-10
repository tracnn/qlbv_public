# DVKT bắt buộc có mã máy — thiết kế lại quy tắc kiểm mã máy XML3176

**Ngày:** 2026-09-10
**Module:** XML3176 — kiểm dịch vụ kỹ thuật (XML3)
**Trạng thái:** Design đã duyệt, chờ lập plan

## 1. Mục tiêu

Chuyển quy tắc "thiếu mã máy" của XML3176 từ căn cứ **nhóm dịch vụ** sang căn cứ **danh mục DVKT do BHXH ban hành**, để vừa bớt báo oan vừa bắt được những dịch vụ hiện đang lọt lưới.

## 2. Bối cảnh & số đo thực tế

Quy tắc hiện tại bắt lỗi khi `ma_nhom ∈ [1,2,3]` và `ma_may` rỗng. File BHXH mới liệt kê **theo mã dịch vụ**, không theo nhóm.

Đối chiếu trên dữ liệu thật (762 dòng XML3 trong CSDL):

| | Số lỗi "thiếu mã máy" |
|---|---|
| Quy tắc hiện tại (nhóm 1,2,3) | **355** |
| Quy tắc theo danh mục | **307** |

- **64 lỗi báo oan biến mất** — nhóm 1: 56 dòng (các xét nghiệm "Đo hoạt độ" AST/ALT/GGT/Lipase), nhóm 3: 8 dòng.
- **16 lỗi bỏ sót được bắt** — toàn bộ **nhóm 18 (nội soi)**, ví dụ `02.0261.0319 Nội soi đại trực tràng`, hiện lọt vì nhóm 18 không nằm trong [1,2,3].
- **11 dòng chỉ khớp khi bỏ hậu tố** dạng `_TB` (`02.0261.0319_TB`).

Đã kiểm chứng khác biệt là **thật, không phải lỗi khớp mã**: file *có* 168 mã xét nghiệm `23.*` (ví dụ `23.0003.1494 Định lượng Acid Uric`) nhưng cố ý *không có* các mã "Đo hoạt độ". Danh mục phân biệt có chủ đích.

**Hệ mã trùng khớp sẵn:** `xml3176_xml3s.ma_dich_vu` dùng đúng hệ mã TT23 như cột `MA_DVKT` của file (`23.0077.1518`, `18.0065.0069`, `02.0085.1778`) — **không cần bảng ánh xạ nào**.

**Về tệp nguồn:** 4.773 dòng nhưng chỉ **3.471 mã duy nhất** (1.302 dòng trùng mã). Header sẵn có: `STT`, `MA_DVKT`, `TEN_DVKT_TT23`, `TEN_DVKT_PHE_DUYET`.

## 3. Phạm vi — đã chốt với người dùng

**Trong phạm vi:**
- Bảng danh mục quốc gia mới chứa danh sách DVKT bắt buộc có mã máy.
- Đổi căn cứ của quy tắc `XML3_INFO_ERROR_GROUP_CODE_MA_MAY`.

**Ngoài phạm vi (quyết định của người dùng):**
- **Giữ nguyên `error_code` cũ** `XML3_INFO_ERROR_GROUP_CODE_MA_MAY`, chỉ đổi `error_name` và mô tả. Đổi mã mới sẽ mất lịch sử lỗi đã ghi và buộc người vận hành cấu hình lại mức nghiêm trọng. Đánh đổi đã chấp nhận: tên hằng còn chữ "GROUP" hơi lệch nghĩa.
- **Không đụng** hai quy tắc mã máy còn lại (`MA_MAY_TOO_LONG`, `MA_MAY_NOT_FOUND`).
- **Không** làm danh mục có hiệu lực theo thời gian.

## 4. Kiến trúc

Tái dùng toàn bộ khung nhập danh mục sẵn có: khai thêm một loại danh mục thì nó **tự xuất hiện trên màn Nhập danh mục**, không cần dựng UI mới.

### 4.1 Bảng `dvkt_can_ma_may`

| Cột | Kiểu | Ghi chú |
|---|---|---|
| `id` | increments | |
| `ma_dvkt` | string(50), unique | Mã DVKT theo TT23 |
| `ten_dvkt` | string(1024) nullable | Tên để người vận hành đối chiếu |
| `is_active` | boolean, default true | **Bắt buộc có** — cơ chế thay trọn bộ dùng cột này |
| timestamps | | |

`unique_keys = ['ma_dvkt']` tự khử 1.302 dòng trùng mã của tệp nguồn.

### 4.2 Sáu điểm khai báo (thiếu điểm nào cũng hỏng theo cách riêng)

1. **Model** `App\Models\BHYT\DvktCanMaMay` — `$fillable` gồm 4 cột nghiệp vụ.
2. **`config/danh_muc_bhyt.php`** — khoá `dvkt_can_ma_may`: `ten` = 'DM DVKT cần mã máy', `model`, `bang`, `theo_co_so => false`.
3. **`config/catalog_import_mapping.php`** — bí danh lấy **đúng header của tệp BHXH** để nhập thẳng tệp gốc:
   ```php
   'dvkt_can_ma_may' => [
       'detect_keys' => ['MA_DVKT', 'TEN_DVKT_TT23'],
       'mapping' => [
           'ma_dvkt'  => ['MA_DVKT', 'Mã DVKT'],
           'ten_dvkt' => ['TEN_DVKT_TT23', 'TEN_DVKT_PHE_DUYET', 'Tên DVKT'],
       ],
       'required_fields' => ['ma_dvkt'],
       'unique_keys' => ['ma_dvkt'],
   ],
   ```
4. **`CatalogImportService::bangCua()`** — thêm `'dvkt_can_ma_may' => 'dvkt_can_ma_may'`.
5. **`CatalogImportService::GHI_THEO_LO`** — thêm khoá mới. **Bắt buộc**: `NhapDanhMucConLaiTheoLoTest` khẳng định *mọi* khoá trong `catalog_import_mapping` đều phải nằm trong hằng này; quên là test đỏ ngay.
6. **`CatalogImportService::LAM_MOI_TRON_BO`** — thêm khoá mới. Đây là danh mục quốc gia: nạp lại là **thay trọn bộ** (tắt hết rồi bật lại dòng có trong tệp), đúng ngữ nghĩa như `administrative_unit`. Cơ chế đã có sẵn, không phải viết lại.

Không cần lớp ghi mới: `GhiTheoLo` là lớp chung nhận (bảng, khoá).

### 4.3 Đổi quy tắc trong `Xml3176Xml3Checker`

Thay điều kiện nhóm bằng:

```
canMay = (danh mục RỖNG)
           ? in_array(ma_nhom, config('xml3176.xml3.service_groups_requiring_machine'))
           : DvktCanMaMay dang dung CO ma_dich_vu HOAC ma goc(ma_dich_vu)

nếu canMay && rỗng(ma_may) → XML3_INFO_ERROR_GROUP_CODE_MA_MAY
```

**Tự lùi về quy tắc nhóm khi danh mục rỗng** — quyết định của người dùng. Lý do: nếu thay thẳng mà quên nạp danh mục thì bảng rỗng ⇒ **không sinh lỗi nào**, mất sạch 355 cảnh báo mà không ai biết. Lùi về quy tắc cũ bảo đảm **không có khoảng trống mất bảo vệ**, và nạp danh mục xong thì tự chuyển sang quy tắc mới.

**Khớp cả mã gốc:** ngoài khớp chính xác, thử thêm mã đã bỏ hậu tố sau `_` (`02.0261.0319_TB` → `02.0261.0319`). Bỏ bước này là mất **11/307** lỗi đo được trên dữ liệu thật.

**Chỉ đổi `error_name`/mô tả**, giữ nguyên `error_code`. Mô tả mới nêu rõ căn cứ là danh mục BHXH.

### 4.4 Tách phần kiểm được ra khỏi phần chạm CSDL

- Helper thuần `MaDvktMatcher::maGoc($ma): string` — bỏ hậu tố sau `_`, trim. Test không cần CSDL.
- `DvktCanMaMayService::canMaMay($maDichVu, $maNhom): bool` — chứa cả nhánh lùi; truy vấn CSDL. Test bằng sqlite với hai trạng thái: danh mục rỗng và đã nạp.

## 5. Luồng dữ liệu

```
Tệp Excel BHXH (MA_DVKT, TEN_DVKT_TT23, TEN_DVKT_PHE_DUYET) — nhập nguyên bản
   │  màn Nhập danh mục sẵn có (thay trọn bộ)
   ▼
dvkt_can_ma_may — dòng cũ is_active=0, dòng trong tệp is_active=1
   │  DvktCanMaMayService::canMaMay()  (rỗng → lùi về nhóm 1,2,3)
   ▼
Xml3176Xml3Checker — XML3_INFO_ERROR_GROUP_CODE_MA_MAY
```

## 6. Xử lý biên / guard

1. Danh mục rỗng (chưa nạp) → dùng quy tắc nhóm cũ.
2. `ma_dich_vu` rỗng → không kết luận cần máy; đã có quy tắc riêng cho mã dịch vụ rỗng.
3. `ma_may` có giá trị → quy tắc này im; hai quy tắc còn lại (quá dài, không có trong danh mục thiết bị) vẫn chạy như cũ.
4. Chỉ tra dòng `is_active = 1` — dòng của lần nạp trước đã nghỉ hưu không được tính.

## 7. Kế hoạch kiểm thử

**Unit thuần — `MaDvktMatcher::maGoc`:** mã thường giữ nguyên; `02.0261.0319_TB` → `02.0261.0319`; nhiều dấu `_`; chuỗi rỗng.

**Unit có sqlite — `DvktCanMaMayService::canMaMay`:**
- Danh mục rỗng: nhóm 1 → true; nhóm 18 → false (đúng hành vi cũ).
- Danh mục đã nạp: mã có trong danh mục → true dù nhóm 18; mã không có → false dù nhóm 1.
- Mã `..._TB` khớp qua mã gốc → true.
- Dòng `is_active = 0` không được tính → false.

**Hai test tự động sẽ soi ta** (không phải viết mới, nhưng phải xanh):
- `NhapDanhMucConLaiTheoLoTest` — bắt khoá mới phải có trong `GHI_THEO_LO`.
- `CatalogTemplateSelfDetectTest` — dựng biểu mẫu cho từng loại và bắt nó tự nhận diện đúng loại; biểu mẫu mới không được đụng loại khác.

**Hồi quy:** toàn bộ `tests/Unit/Xml3176` và `tests/Unit/Import` phải xanh.

## 8. Thứ tự triển khai

1. Chạy migration tạo bảng.
2. **Nạp danh mục** qua màn Nhập danh mục, chọn tệp BHXH nguyên bản. Đối chiếu: phải có **3.471** dòng đang dùng.
3. Rà một lô hồ sơ, so số lỗi "thiếu mã máy" trước/sau. Kỳ vọng **giảm khoảng 14%** (355 → 307 trên tập mẫu), trong đó xuất hiện lỗi mới ở **nhóm 18**.
4. Nếu số lỗi **không đổi**, gần như chắc chắn danh mục chưa nạp được và quy tắc đang chạy nhánh lùi — kiểm lại bước 2.

Không cần seeder mã lỗi: `error_code` giữ nguyên nên dòng danh mục lỗi đã tồn tại.

## 9. Rủi ro

- **Quên nạp danh mục** → chạy nhánh lùi, hành vi y như cũ. Không mất bảo vệ, nhưng cũng không có cải tiến; bước 4 của quy trình triển khai là chốt phát hiện.
- **Tệp BHXH đổi header ở phiên bản sau** → không nhận diện được loại danh mục. Biểu hiện rõ ràng ngay khi nhập (báo không xác định được loại), không hỏng lặng lẽ.
- **Danh mục nạp thiếu** (tệp bị cắt bớt) → thay trọn bộ sẽ làm những mã thiếu không còn bị đòi mã máy. Đây là rủi ro cố hữu của mọi danh mục thay trọn bộ, đã được cảnh báo sẵn trong tài liệu hướng dẫn mục 4.4.2.
