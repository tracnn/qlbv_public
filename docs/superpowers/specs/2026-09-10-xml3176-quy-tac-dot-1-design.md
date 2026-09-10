# Bổ sung quy tắc XML3176 theo chuẩn dữ liệu đầu ra — Đợt 1 (A + B + C)

**Ngày:** 2026-09-10
**Module:** XML3176 — tiền giám định
**Nguồn:** `241028_Chuẩn dữ liệu đầu ra_điều chỉnh diễn giải_3176.xlsx` (QĐ 130/QĐ-BYT, đính chính và sửa đổi theo QĐ 4750/QĐ-BYT)
**Trạng thái:** Design đã duyệt, chờ lập plan

## 1. Mục tiêu

Bổ sung các quy tắc rà soát mà chuẩn dữ liệu quy định rõ nhưng bộ 266 quy tắc hiện có chưa chạm tới, tập trung vào ba nhóm: **cấu trúc mã dịch vụ**, **công thức tiền từng dòng**, và **ràng buộc liên bảng**.

Đợt 1 chỉ mở rộng ba checker sẵn có (`Xml3176Xml1Checker`, `Xml3176Xml2Checker`, `Xml3176Xml3Checker`) và `Xml3176CompleteChecker`. Việc dựng checker mới cho XML6/XML15 là Đợt 2, có spec riêng.

## 2. Hiện trạng đã đo, không suy đoán

Đo trực tiếp trên CSDL dev (51 hồ sơ, 762 dòng XML3, 1.611 dòng XML4, 15 dòng XML2):

| Phép đo | Kết quả | Hệ quả cho thiết kế |
|---|---|---|
| Vượt kích thước tối đa — toàn bộ trường có khai kích thước, mọi bảng có dữ liệu | **0** | Bỏ hẳn ý tưởng "checker định dạng lái bằng danh mục trường". Sẽ trả 0 lỗi. |
| Ngày sai định dạng / sai độ dài `yyyymmdd`, `yyyymmddHHMM` | **0** | Như trên. |
| Dòng XML3 mang hậu tố `_TB` | 15 | |
| Trong đó `DON_GIA_BH ≠ 0` hoặc `DON_GIA_BV ≠ 0` | **15/15** | Vi phạm 100%. Đây là lỗi thật duy nhất đo được ngay. |
| Dòng XML3 có `THANH_TIEN_BH > 0` | **1/762** | **CSDL dev gần như trống phần tiền.** Toàn bộ nhóm B không đo được ở đây. |
| Dòng XML3 mang `PHAM_VI = '2'` | 761/762 | Đáng ngờ (xem §7), nhưng không kết luận được khi chưa có tiền. |
| `NGAY_TAI_KHAM` (XML1) ↔ `NGAY_HEN_KL` (XML14) | khớp 100%, 24/24 | Nhóm C là guard, hiện không thu lỗi. |

**Giới hạn phải nói rõ:** nhóm B được thiết kế từ văn bản chứ không từ số đo. Con số 0 hôm nay không phải bằng chứng dữ liệu đúng — nó là bằng chứng dữ liệu trống. Trước khi bật `critical_error` cho bất kỳ quy tắc nhóm B nào, phải nạp một lô hồ sơ có tiền và đo lại.

## 3. Khoảng trống đã kiểm chứng trên mã nguồn

Trước khi thiết kế, đã đối chiếu từng quy tắc hiện có để không làm trùng:

**Đã có, KHÔNG làm lại:**
- `Xml3176CompleteChecker::checkExpenseErrors()` đã kiểm đủ **tổng cấp hồ sơ**: `T_THUOC`, `T_VTYT`, `T_TONGCHI_BV`, `T_TONGCHI_BH`, `T_BNTT`, `T_BNCCT`, `T_NGUONKHAC` (XML1 = tổng XML2+XML3) và `T_BHTT = T_TONGCHI_BH − T_BNCCT`.
- `Xml3176Xml2Checker` đã có `PHAM_VI_INVALID`.
- `Xml3176Xml3Checker` đã có `INFO_ERROR_TYLE_TT_DV`, `INFO_ERROR_TYLE_TT_BH`, `INVALID_T_TRANTT_T_BHTT`.
- `Xml3176CompleteChecker` đã có `MISSING_TRANSFER_OR_APPOINTMENT` (có `MA_NOI_DI` mà thiếu cả XML13 lẫn XML14).

**Chưa có gì:**
- Không quy tắc nào kiểm **công thức tiền của một dòng** XML2 hoặc XML3. Tổng khớp không suy ra từng dòng đúng: hai dòng sai ngược chiều nhau vẫn cho tổng đúng.
- Không quy tắc nào đọc cấu trúc `MA_DICH_VU` ngoài việc tách hậu tố (`MaDvktMatcher::maGoc`).
- XML3 không có quy tắc `PHAM_VI`, `TAI_SU_DUNG`; XML2 không có `NGUON_CTRA`; XML1 không có `MA_KHUVUC`, `NGAY_TAI_KHAM`, `CAN_NANG_CON`, `MA_PTTT_QT`.

## 4. Phạm vi

**Trong phạm vi:** 24 quy tắc mới, chia ba nhóm, kèm 3 helper thuần và 1 seeder danh mục mã lỗi.

**Ngoài phạm vi, kèm lý do:**
- **`MA_TAI_NAN`** — chuẩn nói "tham chiếu Phụ lục số 4 QĐ 5937/QĐ-BYT". Chúng ta không có phụ lục đó trong hệ thống. Kiểm "phải là một chữ số" là kiểm rỗng nghĩa. Thiếu căn cứ thì im lặng.
- **`VI_TRI_TH_DVKT`** — chuẩn ghi rõ "áp dụng khi Bộ trưởng Bộ Y tế ban hành danh mục mã vị trí cơ thể". Danh mục chưa ban hành.
- **`MA_HIEU_SP`** — "chỉ bắt buộc đối với VTYT *có* thông tin mã hiệu sản phẩm". Không có cách xác định VTYT nào có, nên không kiểm được.
- **`MA_PP_CHEBIEN`, `MA_CSKCB_THUOC`, `GOI_VTYT`, `MA_XANG_DAU` (tập giá trị)** — đều cần danh mục chưa nạp. Riêng `MA_XANG_DAU` có kiểm *sự tồn tại* ở A3, không kiểm tập giá trị.
- **Họ `Qd130*Checker`** (13 tệp song song, không nơi nào gọi tới) — không đụng.
- **XML6, XML12, XML15** — Đợt 2 và dự án riêng.

## 5. Ba helper thuần

Đặt tại `app/Services/Xml3176/Support/`, không chạm CSDL, test không cần sqlite.

### 5.1 `MaDvktStructure`

Đọc cấu trúc mã dịch vụ theo quy định tại trường `MA_PTTT_QT` bảng XML3 của chuẩn.

```php
hauTo(string $ma): string           // phan sau dau '_' cuoi cung; '' neu khong co
laKhongThucHien(string $ma): bool   // hau to 'TB'
laChuaCoGia(string $ma): bool       // 4 ky tu cuoi cua phan than la '0000'
laVanChuyen(string $ma): bool       // bat dau bang 'VC.'
maCoSoVanChuyen(string $ma): string // 5 ky tu sau 'VC.'; '' neu khong phai
maCoSoChuyenMau(string $ma): string // WWWWW trong XX.YYYY.ZZZZ.K.WWWWW; '' neu khong khop
```

Đã có `MaDvktMatcher::maGoc()` bỏ hậu tố. `MaDvktStructure` là lớp riêng vì nó trả về *thành phần* chứ không chuẩn hoá mã; gộp vào `MaDvktMatcher` sẽ làm lớp đó mang hai trách nhiệm.

`laChuaCoGia` xét phần thân (đã bỏ hậu tố) để `XX.YYYY.0000_TB` vẫn nhận diện đúng cả hai tính chất.

### 5.2 `TienTeCalculator`

Công thức lấy nguyên văn từ chuẩn, bản **sửa đổi theo QĐ 4750** (bản QĐ 130 gốc đã bị bãi bỏ):

```php
thanhTienBvXml3($soLuong, $donGiaBv, $tyLeTtDv): float            // SL * DON_GIA_BV * TYLE_TT_DV/100
thanhTienBhXml3($soLuong, $donGiaBh, $tyLeTtDv, $tyLeTtBh): float // SL * DON_GIA_BH * TYLE_TT_DV/100 * TYLE_TT_BH/100
thanhTienBvXml2($soLuong, $donGia): float                         // SL * DON_GIA
thanhTienBhXml2($soLuong, $donGia, $tyLeTtBh): float              // SL * DON_GIA * TYLE_TT_BH/100
tBhtt($thanhTienBh, $mucHuong): float                             // THANH_TIEN_BH * MUC_HUONG/100
tongNguonKhac($nsnn, $vtnn, $vttn, $cl): float
lech(float $thucTe, float $kyVong, float $saiSo): bool             // abs(thucTe - kyVong) > saiSo
```

Mọi hàm làm tròn 2 chữ số thập phân đúng như chuẩn quy định.

**Sai số** `$saiSo` mặc định **1.0 (một đồng)**, khai tại `config/xml3176.php` khoá `tien.sai_so`. Lý do: chuẩn bắt làm tròn 2 chữ số ở từng phép nhân, nên chênh lệch hợp lệ chỉ ở mức xu; lấy 1 đồng là biên rộng rãi mà vẫn bắt được sai thật (thường sai cả nghìn đồng trở lên). Mã hiện có trong `checkExpenseErrors` dùng `!=` trực tiếp trên số thực — cách đó dễ báo oan vì lỗi dấu phẩy động; quy tắc mới **không** lặp lại, dùng `lech()`. Không sửa mã cũ trong đợt này.

### 5.3 `DanhSachPhanCachParser`

Chuẩn dùng dấu `;` để ngăn nhiều giá trị trong một trường (`NGAY_TAI_KHAM`, `CAN_NANG_CON`, `MA_PTTT_QT`, `MA_PP_CHEBIEN`).

```php
tach(string $chuoi): array   // tach theo ';', trim tung phan tu, bo phan tu rong
```

## 6. Danh sách quy tắc

Tất cả dùng khuôn sẵn có: `generateErrorCode($key)` + đối tượng lỗi `(object)['error_code','error_name','critical_error','description']`.

### 6.1 Nhóm A — cấu trúc mã dịch vụ (`Xml3176Xml3Checker`, 6 quy tắc)

| Mã (hậu tố sau `XML3_`) | Điều kiện sinh lỗi | Căn cứ |
|---|---|---|
| `MA_DICH_VU_TB_CO_DON_GIA` | `laKhongThucHien()` và (`DON_GIA_BH ≠ 0` hoặc `DON_GIA_BV ≠ 0`) | Chuẩn, trường `MA_PTTT_QT`: hậu tố `_TB` ⇒ `DON_GIA_BH = 0; DON_GIA_BV = 0` (khoản 3 Điều 7 TT 39/2018/TT-BYT) |
| `MA_DICH_VU_CHUA_CO_GIA_CO_DON_GIA_BH` | `laChuaCoGia()` và `DON_GIA_BH ≠ 0` | Cùng trường: mã kết `.0000` ⇒ `DON_GIA_BH = 0` |
| `MA_DICH_VU_HAU_TO_LA` | `hauTo()` khác rỗng và không thuộc `{TB, GT}` | Chuẩn chỉ định nghĩa hai hậu tố `_TB` và `_GT` |
| `MA_DICH_VU_VAN_CHUYEN_THIEU_XANG_DAU` | `laVanChuyen()` và `MA_XANG_DAU` rỗng | Trường `MA_XANG_DAU`: mã xăng dầu để tính chi phí vận chuyển |
| `MA_DICH_VU_VAN_CHUYEN_CSKCB_NOT_FOUND` | `maCoSoVanChuyen()` khác rỗng và `isMedicalOrganizationValid()` sai | `VC.XXXXX`, XXXXX là mã cơ sở KBCB nơi chuyển đến |
| `MA_DICH_VU_CHUYEN_MAU_CSKCB_NOT_FOUND` | `maCoSoChuyenMau()` khác rỗng và `isMedicalOrganizationValid()` sai | `XX.YYYY.ZZZZ.K.WWWWW` theo TT 09/2019/TT-BYT |

Guard chung: `MA_DICH_VU` rỗng thì cả sáu quy tắc im lặng — đã có `MISSING_SERVICE_OR_MATERIAL` lo việc đó.

### 6.2 Nhóm B — công thức tiền từng dòng và tập giá trị

**B-a. Công thức tiền (8 quy tắc)**

`Xml3176Xml3Checker`:

| Mã | Điều kiện |
|---|---|
| `THANH_TIEN_BV_SAI_CONG_THUC` | `lech(THANH_TIEN_BV, thanhTienBvXml3(SO_LUONG, DON_GIA_BV, TYLE_TT_DV))` |
| `THANH_TIEN_BH_SAI_CONG_THUC` | `lech(THANH_TIEN_BH, thanhTienBhXml3(SO_LUONG, DON_GIA_BH, TYLE_TT_DV, TYLE_TT_BH))` |
| `T_NGUONKHAC_SAI_TONG` | `lech(T_NGUONKHAC, tongNguonKhac(...4 nguồn con))` |
| `T_BHTT_SAI_CONG_THUC` | `lech(T_BHTT, tBhtt(THANH_TIEN_BH, MUC_HUONG))` |

`Xml3176Xml2Checker`: bốn quy tắc cùng tên, dùng `thanhTienBvXml2` / `thanhTienBhXml2` (XML2 **không có** `TYLE_TT_DV`).

**Guard bắt buộc — thiếu căn cứ thì im lặng.** Mỗi quy tắc chỉ chạy khi mọi toán hạng của nó có mặt và hợp lệ:

1. Bất kỳ toán hạng nào `null` hoặc không phải số ⇒ im lặng. Đã có quy tắc riêng bắt trường rỗng.
2. `TYLE_TT_DV` hoặc `TYLE_TT_BH` ngoài khoảng `(0, 100]` ⇒ im lặng. Đã có `INFO_ERROR_TYLE_TT_DV` / `INFO_ERROR_TYLE_TT_BH`; nếu tỷ lệ sai thì mọi công thức dẫn xuất đều sai theo, báo thêm chỉ là nhiễu.
3. Riêng `T_BHTT_SAI_CONG_THUC` thêm ba điều kiện, vì công thức gốc có nhánh:
   - `T_NGUONKHAC = 0`. Khi có nguồn khác, chuẩn quy định giảm trừ lần lượt vào `T_BNTT`, `T_BNCCT`, `T_BHTT` — kết quả phụ thuộc loại nguồn (hỗ trợ cá nhân hay hỗ trợ chung cho cơ sở), mà dữ liệu không phân biệt được. Không đủ căn cứ để kết luận.
   - `T_TRANTT` rỗng. Khi có trần thanh toán, `T_BHTT` bị chặn trên; đã có `INVALID_T_TRANTT_T_BHTT` lo việc đó.
   - `MUC_HUONG` trong khoảng `(0, 100]`.

Cố ý **không** làm `T_BNCCT` và `T_BNTT` từng dòng: cả hai đều là hiệu số dẫn xuất, nên khi `THANH_TIEN_BH` hoặc `T_BHTT` sai thì chúng sai theo — ba lỗi cho một nguyên nhân. Tổng cấp hồ sơ của hai trường này đã được `checkExpenseErrors` kiểm.

**B-b. Tập giá trị hợp lệ (7 quy tắc)**

| Checker | Mã | Điều kiện | Căn cứ |
|---|---|---|---|
| XML3 | `PHAM_VI_INVALID` | `PHAM_VI` khác rỗng và không thuộc `{1,2,3}` | Trường `PHAM_VI` |
| XML3 | `PHAM_VI_TU_TRA_MA_BH_TRA` | `PHAM_VI = '2'` và (`THANH_TIEN_BH > 0` hoặc `T_BHTT > 0`) | QĐ 4750 sửa toàn bộ diễn giải: mã 2 = *do người bệnh tự trả* |
| XML3 | `TAI_SU_DUNG_INVALID` | `TAI_SU_DUNG` khác rỗng và khác `'1'` | Trường `TAI_SU_DUNG`: chỉ ghi `1`, không tái sử dụng thì để trống |
| XML3 | `TAI_SU_DUNG_DON_GIA_LECH` | `TAI_SU_DUNG = '1'` và `lech(DON_GIA_BV, DON_GIA_BH)` | Trường `DON_GIA_BV`: "VTYT tái sử dụng: `DON_GIA_BV = DON_GIA_BH`" |
| XML2 | `NGUON_CTRA_INVALID` | `NGUON_CTRA` khác rỗng và không thuộc `{1,2,3,4}` | Trường `NGUON_CTRA` |
| XML2 | `NGUON_CTRA_NGOAI_QUY_MA_BH_TRA` | `NGUON_CTRA` thuộc `{2,3,4}` và `T_BHTT > 0` | Mã 2/3/4 = thuốc dự án, chương trình mục tiêu, nguồn khác — không phải quỹ BHYT chi trả |
| XML1 | `ADMIN_INFO_ERROR_MA_KHUVUC` | `MA_KHUVUC` khác rỗng và không thuộc `{K1,K2,K3}` | Trường `MA_KHUVUC` |

`PHAM_VI` rỗng ở XML3 không sinh lỗi mới: chuẩn không đánh dấu trường này bắt buộc, và XML2 hiện cũng chỉ kiểm khi có giá trị. Giữ hai bảng hành xử giống nhau.

### 6.3 Nhóm C — liên bảng (`Xml3176CompleteChecker`, 3 quy tắc)

| Mã | Điều kiện | Căn cứ |
|---|---|---|
| `NGAY_TAI_KHAM_SAI_DINH_DANG` | `NGAY_TAI_KHAM` khác rỗng và có phần tử không phải 8 chữ số hoặc không phải ngày có thật | Trường `NGAY_TAI_KHAM`: mỗi ngày 8 ký tự `yyyymmdd`, ngăn bởi `;` |
| `NGAY_TAI_KHAM_KHONG_KHOP_XML14` | Có phần tử `NGAY_TAI_KHAM` hợp lệ không tìm được dòng XML14 cùng `MA_LK` có `NGAY_HEN_KL` bằng nó (kể cả khi hồ sơ không có dòng XML14 nào) | Hẹn tái khám phải có giấy hẹn khám lại tương ứng |
| `CAN_NANG_CON_THIEU_XML9` | `CAN_NANG_CON` khác rỗng và không có dòng XML9 nào cùng `MA_LK` | Trường `CAN_NANG_CON`: "chỉ ghi trong trường hợp sinh con" ⇒ phải có giấy chứng sinh |

Gộp "thiếu XML14" và "lệch ngày" vào **một** mã: cả hai là cùng một sai — ngày hẹn trong XML1 không có giấy hẹn tương ứng. Tách hai mã buộc người vận hành cấu hình hai lần cho một vấn đề. Mô tả lỗi nêu rõ những ngày nào không khớp.

`NGAY_TAI_KHAM_KHONG_KHOP_XML14` chỉ xét các phần tử đã hợp lệ về định dạng; phần tử sai định dạng do `NGAY_TAI_KHAM_SAI_DINH_DANG` lo, không báo hai lần cho một giá trị.

**Không** làm `MA_PTTT_QT` (XML1) rỗng khi XML3 có dòng phẫu thuật/thủ thuật: xác định "dòng nào là phẫu thuật thủ thuật" hiện dựa vào `ma_nhom`, mà chính giả định "nhóm ⇒ tính chất dịch vụ" là thứ đợt trước đã chứng minh sai (bỏ 64 báo oan khi chuyển quy tắc mã máy từ nhóm sang danh mục). Lặp lại giả định đó là đi ngược bài học vừa học.

**Không** so `CAN_NANG_CON` với `SO_CON` của XML9: `SO_CON` là số con của lần sinh, còn `CAN_NANG_CON` ghi con còn sống hay ghi cả con chết thì chuẩn không nói rõ. Thiếu căn cứ.

## 7. Điều cần người vận hành xác minh, không phải lỗi phần mềm

**761/762 dòng XML3 đang mang `PHAM_VI = '2'`.** Theo diễn giải sửa đổi của QĐ 4750, mã 2 nghĩa là *dịch vụ do người bệnh tự trả*. Nếu HIS đang gán mặc định `2` cho mọi dòng thì khi hồ sơ có tiền thật, quy tắc `PHAM_VI_TU_TRA_MA_BH_TRA` sẽ báo hàng loạt — và **lỗi nằm ở bộ xuất HIS, không ở quy tắc**. Cần kiểm tra bộ xuất trước khi bật `critical_error` cho quy tắc này.

## 8. Danh mục mã lỗi

24 mã mới đều phải có dòng trong `xml3176_error_catalogs`. **Thiếu dòng thì `critical_error` mặc định là `true`** và sẽ chặn xuất XML — bẫy này đã cắn ở các đợt trước.

Seeder `Xml3176ErrorCatalogChuan3176Dot1Seeder` ghi cả 24 mã với `is_check = true`, `critical_error = false`.

Đặt `critical_error = false` cho **tất cả**, kể cả `MA_DICH_VU_TB_CO_DON_GIA` (quy tắc duy nhất đã đo được vi phạm thật): 23 quy tắc còn lại chưa từng chạy trên dữ liệu có tiền, và một quy tắc báo oan mà lại chặn xuất XML thì làm tê liệt việc gửi hồ sơ. Người vận hành bật lên qua màn danh mục mã lỗi sau khi đã quan sát vài lô.

## 9. Kế hoạch kiểm thử

**Unit thuần — không cần CSDL:**
- `MaDvktStructure`: mã thường; `_TB`; `_GT`; hậu tố lạ `_XX`; `VC.01234`; `02.0261.0319.K.01234`; `XX.YYYY.0000`; `XX.YYYY.0000_TB` (vừa chưa có giá vừa không thực hiện); chuỗi rỗng; nhiều dấu `_`.
- `TienTeCalculator`: từng công thức với số tròn; trường hợp `TYLE_TT_DV = 90`; làm tròn 2 chữ số; `lech()` đúng ở biên sai số (chênh 0.99 ⇒ không lệch, chênh 1.01 ⇒ lệch).
- `DanhSachPhanCachParser`: một phần tử; nhiều phần tử; có khoảng trắng thừa; có `;` ở cuối; chuỗi rỗng.

**Unit có sqlite — dùng `Xml3176RuleTestSupport::bootXml3176Sqlite()`:**
- Mỗi quy tắc nhóm A, B: một ca sinh lỗi, một ca không sinh lỗi, và một ca **guard im lặng** (toán hạng thiếu).
- Nhóm C: có XML14 khớp ⇒ im; thiếu XML14 ⇒ lỗi; XML14 có nhưng lệch ngày ⇒ lỗi; nhiều ngày ngăn bởi `;` khớp đủ ⇒ im.

**Ca hồi quy bắt buộc (từ số đo thật):** dựng 15 dòng `_TB` với đơn giá khác 0 như dữ liệu thật ⇒ phải sinh đúng 15 lỗi `MA_DICH_VU_TB_CO_DON_GIA`.

**Hồi quy:** toàn bộ `tests/Unit/Xml3176` (215 test) và `tests/Unit/Import` (63 test) phải xanh.

**Cấm `RefreshDatabase`** — sẽ xoá sạch CSDL dev `qlbv`.

## 10. Thứ tự triển khai

1. Chạy seeder danh mục mã lỗi **trước** khi bật quy tắc, nếu không mọi mã mới đều `critical_error = true` và chặn xuất XML.
2. Rà một lô hồ sơ, đối chiếu: phải xuất hiện `MA_DICH_VU_TB_CO_DON_GIA` ở đúng những dòng `_TB` còn đơn giá.
3. Nạp một lô hồ sơ **có phần tiền đầy đủ**, đo số lỗi từng quy tắc nhóm B. Quy tắc nào báo trên 20% số dòng thì dừng lại xem xét — gần như chắc chắn là báo oan hoặc bộ xuất HIS sai hệ thống, không phải 20% hồ sơ sai.
4. Chỉ sau bước 3 mới cân nhắc bật `critical_error` cho từng mã.

## 11. Rủi ro

- **Nhóm B chưa từng chạy trên dữ liệu có tiền.** Rủi ro lớn nhất của đợt này. Giảm thiểu bằng `critical_error = false` toàn bộ và bằng bước 3 của §10.
- **Sai số 1 đồng có thể quá chặt** nếu bộ xuất HIS làm tròn ở bước khác chuẩn (ví dụ làm tròn đơn giá trước khi nhân thay vì sau). Biểu hiện: một quy tắc thành tiền báo gần như toàn bộ dòng. Xử lý: nới `xml3176.tien.sai_so` trong config, không phải sửa mã.
- **`PHAM_VI` mặc định sai ở bộ xuất** (§7) sẽ làm `PHAM_VI_TU_TRA_MA_BH_TRA` báo hàng loạt. Đây là lỗi dữ liệu thật, nhưng cần xác minh trước để không đổ cho quy tắc.
- **Mã cơ sở KBCB trong `VC.XXXXX` / `.K.WWWWW`** phụ thuộc danh mục cơ sở KBCB đã nạp. Danh mục thiếu ⇒ báo oan. Hiện `isMedicalOrganizationValid()` đã được dùng cho `MA_CSKCB` ở XML1 nên rủi ro này đã tồn tại sẵn và có thể quan sát được.
