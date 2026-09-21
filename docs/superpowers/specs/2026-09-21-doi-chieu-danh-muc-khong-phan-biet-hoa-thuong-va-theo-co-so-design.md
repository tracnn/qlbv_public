# Đối chiếu danh mục không phân biệt hoa thường, và CCHN theo cơ sở KCB

Ngày: 2026-09-21

## 1. Yêu cầu

1. Mọi phép **đối chiếu với danh mục** — ở order-check **và** XML3176 — không phân biệt
   chữ hoa chữ thường, cho cả **mã** lẫn **tên**.
2. Order-check kiểm tra theo **từng cơ sở KCB** với mọi danh mục đã có cột `MA_CSKCB`.

## 2. Hiện trạng đã xác minh

### 2.1 Collation không phải là vấn đề — bước tra mảng PHP mới là vấn đề

Mọi bảng danh mục dùng collation `utf8_general_ci`, nên **mọi phép so chạy trong SQL đã
không phân biệt hoa thường**. Lỗi chỉ nằm ở những chỗ lấy dòng danh mục về rồi so tiếp
bằng PHP.

Ví dụ nguyên nhân gốc ở order-check: `CatalogLookup::nap()` gửi mã y lệnh `abc01` vào
`whereIn` → SQL trả về dòng `ABC01` → PHP lưu vào `$this->dong['ABC01']` → tra bằng
`isset($this->dong['abc01'])` **trượt** → báo "không có trong danh mục" oan, dù SQL đã
tìm thấy đúng dòng.

Vì vậy **không** sửa bằng `LOWER()` hay đổi collation: SQL vốn đã đúng.

### 2.2 Kiểm kê các phép so phân biệt hoa thường

**Order-check** — tất cả đi qua `App\Services\OrderCheck\Support\CatalogLookup`:

| Quy tắc | Phép so |
|---|---|
| 3 quy tắc mã BHYT (thuốc, DV, VTYT) | khoá mảng `$this->dong[$ma]` |
| 3 quy tắc tên BHYT | `in_array($tenKhai, $tenDanhMuc, true)` trong `BhytNameMismatchRule` |
| 2 quy tắc ICD | khoá mảng |
| `A_STAFF_CERT_NOT_IN_CATALOG` | khoá mảng |

**XML3176** — đúng 4 chỗ:

| Mã lỗi | Vị trí | Phép so hiện tại |
|---|---|---|
| `INVALID_DRUG_NAME` | `Xml3176Xml2Checker.php:350` | `$data->ten_thuoc != $medicine->ten_thuoc` |
| `INVALID_MATERIAL_NAME` | `Xml3176Xml3Checker.php:796` | `$data->ten_vat_tu != $supply->ten_vat_tu` |
| `INVALID_TEN_DICH_VU` | `Xml3176Xml3Checker::tenLechDanhMuc()` | `in_array(..., true)` |
| `MEDICAL_SUPPLY_NOT_IN_CATALOG` | `Xml3176Xml3Checker.php:783` | 4 phần `TT_THAU` so bằng `==` |

**Đã không phân biệt hoa thường, KHÔNG sửa:**

- Tra mã qua SQL `where()`: mã thuốc, hàm lượng, số đăng ký, `TT_THAU` thuốc (SQL `LIKE`),
  mã VTYT, mã DVKT, ICD, mã máy, CCHN, mã CSKCB, đơn vị hành chính, nghề nghiệp.
- `DrugCatalogAttrChecker` (đường dùng, dạng bào chế…) và đơn vị tính ở XML2 — đã dùng
  `TextNormalizer`.
- `BenhPl1Matcher` — đã `strtoupper`.

Ghi nhận phụ: `TT_THAU` của **thuốc** so bằng SQL `LIKE` (không phân biệt) trong khi của
**VTYT** so bằng PHP `==` (phân biệt). Sửa mục 4 của bảng XML3176 cũng gỡ luôn sự lệch này.

### 2.3 Danh mục có `MA_CSKCB`

| Bảng | Có `ma_cskcb` | Quy tắc order-check dùng | Đang lọc cơ sở |
|---|---|---|---|
| `medicine_catalogs` | Có | mã + tên thuốc | Có |
| `service_catalogs` | Có | mã + tên DV | Có |
| `medical_supply_catalogs` | Có | mã + tên VTYT | Có |
| `medical_staffs` | **Có** — 18/18 dòng đã gán cơ sở | CCHN | **Không** |
| `icd10_categories`, `icd_yhct_categories` | Không | mã ICD | Không — ICD là danh mục quốc gia |

Lọc cơ sở cho 6 quy tắc BHYT đã làm ở đợt `2026-07-28-danh-muc-theo-co-so-kcb`; spec đợt
đó để nhân viên y tế ngoài phạm vi vì khi ấy bảng chưa có cột. Nay có rồi — quy tắc CCHN là
chỗ hở duy nhất của yêu cầu 2.

### 2.4 Hai bẫy kỹ thuật, đã kiểm chứng trên máy phát triển

- `strtolower('ĐƯỜNG HUYẾT')` cho ra `��Ờng huyẾt` — **làm hỏng tiếng Việt**. Phải dùng
  `mb_strtolower(…, 'UTF-8')`.
- Extension `intl` **không có**, nên không chuẩn hoá được Unicode (chữ dựng sẵn và chữ tổ
  hợp dấu). Xem mục 6.

## 3. Quyết định đã chốt

| # | Câu hỏi | Chốt |
|---|---|---|
| Q1 | Áp cho mã hay tên | **Cả mã và tên** |
| Q2 | Bác sĩ đăng ký ở cơ sở A, y lệnh phát sinh ở cơ sở B | **Báo vi phạm** — CCHN phải đăng ký tại chính cơ sở phát sinh hồ sơ |
| Q3 | Bộ chuẩn hoá | **Dùng lại `Xml3176\Support\TextNormalizer::chuan()`** — trim + gộp khoảng trắng + `mb_strtolower`. Hệ quả: khoảng trắng thừa ở giữa cũng không còn bị coi là lệch |

**Đảo một quyết định cũ.** Ba test hiện hữu đang khoá hành vi "so TUYỆT ĐỐI, thống nhất
giữa order-check và XML3176":

- `Xml3TenDichVuTest::lech_hoa_thuong_van_tinh_la_lech`
- `BhytNameRuleTest::lech_hoa_thuong_van_bao_vi_pham`
- `BhytNameRuleTest::lech_khoang_trang_giua_chu_thi_van_bao`

Chúng được **đảo**, không xoá. Tinh thần "hai bên thống nhất" giữ nguyên — nay thống nhất
ở chuẩn mới.

## 4. Thiết kế

### 4.1 Một bộ chuẩn hoá duy nhất

`TextNormalizer::chuan()` đã có sẵn và đã được XML3176 dùng. Thêm một hàm tiện ích:

```php
/** Hai chuoi co bang nhau sau khi chuan hoa khong. */
public static function bang($a, $b): bool
{
    return self::chuan($a) === self::chuan($b);
}
```

So **nghiêm ngặt** (`===`) sau chuẩn hoá. Việc bỏ `!=` kiểu lỏng còn chặn thêm một lỗi
tiềm ẩn của PHP 7: `'1e3' == '1000'` là `true`.

Order-check dùng lớp này từ `App\Services\Xml3176\Support`. Chấp nhận phụ thuộc chéo: hai
module luôn đi cùng nhau (kể cả trong kế hoạch tách sang NestJS). Không chuyển lớp sang
chỗ khác — đó là tái cấu trúc ngoài phạm vi.

### 4.2 Order-check — `CatalogLookup`

- Hàm riêng `khoa($s)` = `TextNormalizer::chuan($s)`, dùng cho **mọi** khoá mảng.
- `nap()`: **giữ nguyên** giá trị gửi vào SQL `whereIn` (collation CI đã tìm đúng dòng);
  chỉ **lưu vào mảng bằng khoá đã chuẩn hoá**. Như vậy vẫn đúng cả khi sau này một bảng
  đổi sang collation phân biệt hoa thường với các mã trùng đúng chữ.
- `dongConHieuLuc()`: tra bằng `khoa($ma)`.
- Hàm mới:

```php
/** Ten khai co trung MOT dong danh muc con hieu luc cua ma nay khong (da chuan hoa). */
public function coTen($ma, $ten, $ngayYmd = null, $maCskcb = null): bool
```

- `tenTheoMa()` **giữ nguyên**, trả tên gốc — để thông điệp vi phạm vẫn hiện đúng chữ
  người dùng nhập và chữ trong danh mục.
- `datSanChoTest()` chuẩn hoá khoá theo đúng đường của `nap()`, để test đi đúng đường thật.
- So mã cơ sở (`$dongCs === $cs`) giữ nguyên: mã CSKCB là chuỗi số.

### 4.3 Order-check — `BhytNameMismatchRule`

Thay `in_array($tenKhai, $tenDanhMuc, true)` bằng
`$this->danhMuc->coTen($ma, $tenKhai, $ngay, $c->maCskcb)`. `tenTheoMa()` vẫn được gọi
trước để (a) giữ quy tắc "mã không có hoặc hết hiệu lực thì im lặng", (b) có tên gốc cho
thông điệp.

### 4.4 Order-check — `StaffCertNotInCatalogRule`

Hai `CatalogLookup` (theo `macchn` và theo `ma_bhxh`) khai thêm `cotCoSo = 'ma_cskcb'`,
truyền `$c->maCskcb` vào `sanSang()` và `coTrongDanhMuc()`. Dùng lại đúng cơ chế 6 quy tắc
BHYT đang chạy:

| Tình huống | Kết quả |
|---|---|
| CCHN có ở cơ sở của hồ sơ | không vi phạm |
| CCHN có ở dòng bỏ trống `MA_CSKCB` (dùng chung) | không vi phạm |
| CCHN chỉ có ở cơ sở khác | **vi phạm** (Q2) |
| Cơ sở của hồ sơ chưa nhập danh mục nhân viên | quy tắc **im lặng** — hiện là 01283 |
| Hồ sơ không xác định được cơ sở | không lọc cơ sở, giữ hành vi cũ (spec 2026-07-28 mục 4.7) |

`medical_staffs` đã có `tu_ngay`, `den_ngay`, `ma_cskcb` — không cần migration.

### 4.5 Order-check — ICD

Không thêm lọc cơ sở. Được hưởng so mã không phân biệt hoa thường tự động qua 4.2.

### 4.6 XML3176

| Mã lỗi | Thay đổi |
|---|---|
| `INVALID_DRUG_NAME` | `!TextNormalizer::bang($data->ten_thuoc, $medicine->ten_thuoc)` |
| `INVALID_MATERIAL_NAME` | `!TextNormalizer::bang($data->ten_vat_tu, $supply->ten_vat_tu)` |
| `INVALID_TEN_DICH_VU` | `tenLechDanhMuc()` so dạng chuẩn hoá; `tenPheDuyet()` bỏ trùng theo dạng chuẩn hoá, giữ chữ gốc của lần xuất hiện đầu để hiển thị |
| `MEDICAL_SUPPLY_NOT_IN_CATALOG` | 4 phần `TT_THAU` so bằng `TextNormalizer::bang()` |

`Xml3176Xml3Checker` phải thêm `use App\Services\Xml3176\Support\TextNormalizer;` —
`Xml3176Xml2Checker` đã có sẵn.

Thông điệp lỗi giữ nguyên chữ gốc ở cả hai phía.

## 5. Kiểm thử và nghiệm thu

### 5.1 Tự động

| Đối tượng | Ca |
|---|---|
| `TextNormalizer::bang` | khác hoa thường; chữ Việt `Đ`/`Ư` có dấu; khoảng trắng thừa; null với rỗng; khác nội dung thật |
| `CatalogLookup` | tra mã khác hoa thường qua `datSanChoTest`; **qua `nap()` trên CSDL thật** — dòng `ZZ3` tra bằng `zz3`, dọn trong `finally` như test `dieu_kien_loc_duoc_ap_khi_nap` sẵn có; `coTen` khác hoa thường / khác khoảng trắng / khác nội dung / lọc cơ sở |
| `BhytNameRuleTest` | đảo 2 ca; thông điệp giữ chữ gốc |
| `StaffCertRuleTest` | 4 tình huống ở 4.4 + CCHN khác hoa thường + truyền đúng mã cơ sở xuống `sanSang()` |
| `Xml3TenDichVuTest` | đảo 1 ca; thêm khoảng trắng giữa; bỏ trùng theo dạng chuẩn hoá |

Mốc toàn bộ bộ test **phải lấy lại khi bắt đầu** — không dùng số của đợt trước.

### 5.2 Đo trên dữ liệu thật

- **Order-check:** `php artisan kiemtraylenh:thu` trước và sau — lệnh này chạy đủ 7 quy
  tắc BHYT, 2 quy tắc ICD và quy tắc CCHN, chỉ đếm, không ghi. Kỳ vọng: số vi phạm
  mã/tên BHYT và ICD **giảm hoặc giữ**; CCHN **có thể tăng** (hệ quả chủ đích của Q2).
- **XML3176:** chỉ có hiệu lực với hồ sơ **được kiểm lại**; kết quả lỗi đã lưu không tự
  đổi. Nghiệm thu bằng cách tìm một hồ sơ có tên thuốc chỉ lệch hoa thường với danh mục
  rồi chạy lại `Xml3176Xml2Checker::checkErrors()` cho dòng đó.

## 6. Giới hạn đã biết

- **Không chuẩn hoá Unicode.** Máy không có `intl`: hai tên trông giống hệt nhưng khác cách
  mã hoá dấu (dựng sẵn và tổ hợp) vẫn bị coi là khác.
- **Kết quả XML3176 đã lưu không tự cập nhật** — cần kiểm lại hồ sơ.
- **Số vi phạm CCHN có thể tăng** sau khi bật lọc cơ sở: bác sĩ làm ở hai cơ sở mà danh
  mục chỉ khai một dòng sẽ bắt đầu bị báo ở cơ sở còn lại. Đó là hành vi đúng theo Q2; cần
  thông báo để đơn vị bổ sung danh mục (hoặc bỏ trống `MA_CSKCB` cho dòng dùng chung).

## 7. Ngoài phạm vi

- Bộ kiểm tra QĐ130 (`Qd130Xml*Checker`) và bộ cũ (`Xml2Checker`, `Xml3Checker`) — giữ
  như quyết định ngày 2026-07-28.
- Chuyển `TextNormalizer` sang namespace dùng chung.
- Vá lại kết quả XML3176 đã lưu.
- Bảng riêng của order-check `order_check_ref_service_restriction` (chưa có cột cơ sở,
  không được yêu cầu).
