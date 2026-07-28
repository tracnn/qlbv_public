# Order-check: đối chiếu TÊN theo danh mục BHXH

Ngày: 2026-07-28
Trạng thái: đã chốt thiết kế, chờ viết kế hoạch

## 1. Bối cảnh

BHXH không chỉ từ chối theo mã — họ từ chối cả khi **tên** khai báo lệch so với danh mục.
Bộ kiểm XML3176 đã bắt việc này từ lâu, bằng bốn mã lỗi:

| Mã lỗi | Điều kiện | Nơi cài |
|---|---|---|
| `MISSING_DRUG_NAME` | XML không khai tên thuốc | `Xml3176Xml2Checker` |
| `INVALID_DRUG_NAME` | tên thuốc khai ≠ tên danh mục | `Xml3176Xml2Checker:337` |
| `MISSING_MATERIAL_NAME` | XML không khai tên vật tư | `Xml3176Xml3Checker` |
| `INVALID_MATERIAL_NAME` | tên vật tư khai ≠ tên danh mục | `Xml3176Xml3Checker` |

Nhưng XML3176 chỉ chạy **sau khi hồ sơ đã khoá và xuất XML**. Lúc đó sửa được rất ít.
Module order-check chạy trên y lệnh đang phát sinh, nên bắt được cùng loại lỗi ấy sớm hơn
nhiều ngày. Bốn quy tắc đối chiếu **mã** đã dựng xong (chưa bật). Spec này bổ sung nhánh
đối chiếu **tên**, và sửa một lỗi phạm vi phát hiện trong lúc rà soát.

## 2. Số liệu đo trên HIS thật

Đo ngày 2026-07-28, cửa sổ 7 ngày, kết nối `HISPro`.

### 2.1 Cột tên BHXH trong HIS

```
Dịch vụ đang hoạt động          : 21.778
Có HEIN_SERVICE_BHYT_CODE       : 10.552
Có HEIN_SERVICE_BHYT_NAME       : 11.655
Có mã nhưng THIẾU tên           :      0
Tên BHYT khác tên HIS           :  3.330  (31,5% số dịch vụ có mã)
```

Hai kết luận:

- Tên BHXH **luôn** được khai kèm mã. Không cần quy tắc "thiếu tên" — nó sẽ báo 0 vĩnh viễn.
- Tên BHXH lệch tên HIS là chuyện bình thường, đúng mục đích của cột. Không được lấy
  `SERVICE_NAME` làm chuẩn so sánh.

### 2.2 Một mã BHXH được bao nhiêu dịch vụ HIS dùng chung

```
 1 tên khác nhau -> 4.651 mã (88,7%)
 2 tên           ->   250 mã
 3-9 tên         ->   304 mã
10+ tên          ->    39 mã, cá biệt một mã có 226 tên
```

Ví dụ mã `40.805` dùng cho `Wosulin 30/70 100IU/ml`, `Wosulin 30/70 40IU/ml`,
`INSUNOVA - 30/70 (BIPHASIC)`.

**Đây là ràng buộc thiết kế quan trọng nhất.** XML3176 khoá được đúng một dòng danh mục
bằng bốn khoá `ma_thuoc + ham_luong + so_dang_ky + tt_thau`, rồi so tên của dòng đó. Tại
thời điểm y lệnh, HIS chỉ có `hein_service_bhyt_code` và `hein_service_bhyt_name` —
**không** có hàm lượng, số đăng ký, TT thầu. Không khoá được dòng duy nhất.

Nên phép so duy nhất đúng ở order-check là:

> tên khai phải trùng với tên của **ít nhất một** dòng danh mục mang mã đó

Bê nguyên logic "so với dòng duy nhất" của XML3176 sang sẽ báo sai hàng loạt ở nhóm thuốc
dùng chung mã, trong khi dữ liệu vẫn đúng.

### 2.3 Dòng BHYT có mã BHXH, tách theo loại dịch vụ (7 ngày)

```
id=6   Thuốc                 38.735
id=2   Xét nghiệm            15.954
id=7   Vật tư                14.523
id=4   Thủ thuật              6.538
id=1   Khám                   5.683
id=8   Giường                 3.932
id=3   Chẩn đoán hình ảnh     2.427
id=10  Siêu âm                2.400
id=9   Nội soi                  746
id=5   Thăm dò chức năng        670
id=14  Máu                      210
id=11  Phẫu thuật               195
id=15  Giải phẫu bệnh lý         10
                        TỔNG  92.023
```

## 3. Lỗi phạm vi trong phần đã dựng

`BhytCatalogRule::check()` lọc dòng BHYT có mã, rồi đối chiếu **toàn bộ** số dòng đó với
bảng danh mục của mình. Không có bước lọc theo loại dịch vụ. Hệ quả trên số liệu mục 2.3:

| Quy tắc | Dòng đúng phạm vi | Dòng bị bắt oan / tuần |
|---|---|---|
| `A_BHYT_DRUG_NOT_IN_CATALOG` | 38.735 | 53.288 |
| `A_BHYT_SUPPLY_NOT_IN_CATALOG` | 14.523 | 77.500 |
| `A_BHYT_SERVICE_NOT_IN_CATALOG` | 38.765 | 53.258 |

Nghĩa là quy tắc thuốc sẽ báo "Mã thuốc không có trong danh mục" cho một xét nghiệm máu.

Quy tắc bật ở trạng thái tắt nên chưa gây hại thực tế, nhưng phải sửa trước khi bật, và
phải sửa **trong khung chung** để ba quy tắc tên kế thừa được ngay.

Ánh xạ loại dịch vụ → bảng danh mục:

| Bảng danh mục | `his_service.service_type_id` |
|---|---|
| `medicine_catalogs` | `6` (Thuốc) |
| `medical_supply_catalogs` | `7` (Vật tư) |
| `service_catalogs` | mọi giá trị **khác** `6` và `7` |

`service_catalogs` lấy phần bù thay vì liệt kê 14 id, để loại dịch vụ mới phát sinh trong
HIS vẫn nằm trong phạm vi thay vì lặng lẽ rơi ra ngoài.

## 3bis. Danh mục có hiệu lực theo thời gian

Ba bảng danh mục đều có cột hiệu lực, kiểu `varchar(255)`:

| Bảng | Hiệu lực từ | Hiệu lực đến |
|---|---|---|
| `service_catalogs` | `tu_ngay` | `den_ngay` |
| `medicine_catalogs` | `tu_ngay` | `den_ngay` |
| `medical_supply_catalogs` | `tu_ngay` | `den_ngay` (thêm `den_ngay_hd` — hết hạn hợp đồng, **không** dùng) |

Danh mục BHXH thay đổi và mở rộng theo từng đợt trúng thầu. Một mã có thể hết hiệu lực,
một tên có thể được sửa từ một ngày cụ thể. Đối chiếu y lệnh của tháng 3 với dòng danh mục
chỉ có hiệu lực từ tháng 6 là sai — cả chiều bỏ sót lẫn chiều bắt oan.

**Quy tắc chung, áp dụng cho cả bốn quy tắc mã lẫn ba quy tắc tên:** một dòng danh mục chỉ
được dùng để đối chiếu nếu nó **còn hiệu lực tại ngày chỉ định của y lệnh**.

### 3bis.1 Mốc thời gian bên y lệnh

Đo trên 94.461 dòng BHYT của 7 ngày:

```
thiếu tdl_intruction_time :      0
thiếu execute_time        : 94.461
```

`execute_time` rỗng hoàn toàn ở tầng `his_sere_serv`. Mốc so sánh là
`tdl_intruction_time`, không có phương án dự phòng nào khác. Chuyển sang dạng `Ymd` (số
nguyên 8 chữ số) để so.

Dòng nào không có mốc thời gian hợp lệ thì **bỏ qua**, không đối chiếu — cùng tinh thần
fail-safe của `sanSang()`.

### 3bis.2 Định dạng ngày trong danh mục — vấn đề đã biết

`CatalogImportService` ghi thẳng giá trị ô Excel vào cột, không chuẩn hoá. Trong toàn bộ
lớp đó chỉ `ngaycap_cchn` được `Date::excelToDateTimeObject()`. `Excel::toCollection()`
trả ô ngày về dạng **số serial Excel**, nên `tu_ngay` nhiều khả năng đang chứa `45292`
chứ không phải `01/01/2024` — nhưng nếu ô nguồn định dạng text thì lại ra chuỗi.

Ba bảng rỗng trên máy phát triển nên **không xác minh được dạng thật**. Do đó bộ phân tích
ngày phải chấp nhận cả bốn dạng, và phải fail-safe.

Lớp mới `Support\NgayHieuLuc`:

```php
public static function phanTich($gt)   // trả int Ymd, hoặc null nếu không hiểu
public static function conHieuLuc($tuNgay, $denNgay, $ngayYmd)   // bool
```

`phanTich` nhận:

| Dạng vào | Ví dụ | Nhận biết |
|---|---|---|
| Serial Excel | `45292`, `45292.0` | số, nằm trong `[1, 80000]` |
| `Ymd` | `20240101` | 8 chữ số, `[19000101, 29991231]` |
| `d/m/Y` | `01/01/2024` | có `/` |
| `Y-m-d` hoặc `d-m-Y` | `2024-01-01`, `01-01-2024` | có `-`, phân biệt bằng vị trí năm |

Ranh giới giữa serial Excel và `Ymd` không chồng nhau: serial của năm 2100 vẫn dưới
80.000, còn `Ymd` nhỏ nhất là 19.000.101.

`conHieuLuc` fail-safe theo hai chiều:

- `tu_ngay` rỗng hoặc không phân tích được → coi như **đã có hiệu lực** (không loại dòng).
- `den_ngay` rỗng hoặc không phân tích được → coi như **còn hiệu lực** (không loại dòng).

Lý do giống hệt `sanSang()`: lỗi chất lượng dữ liệu danh mục không được biến thành một
trận lũ vi phạm giả. Thà xét thừa một dòng danh mục còn hơn báo oan một y lệnh đúng.

Hệ quả cần chấp nhận: nếu toàn bộ cột `tu_ngay` ở dạng lạ không phân tích được, tính năng
lọc theo ngày sẽ tự vô hiệu hoá một cách im lặng và hành vi trở về như hiện nay. Lệnh
`kiemtraylenh:thu` phải in cảnh báo khi tỉ lệ phân tích được dưới 50%, để việc này không
trôi qua mà không ai biết.

## 4. Phạm vi

### Có làm

- Thêm `serviceTypeId` vào `OrderService`, lọc theo loại trong `BhytCatalogRule`.
- Lọc dòng danh mục theo hiệu lực tại ngày chỉ định — **cả bốn quy tắc mã đã có** lẫn ba
  quy tắc tên mới.
- `Support\NgayHieuLuc` — phân tích ngày đa định dạng, fail-safe.
- `CatalogLookup` nạp thêm tên và cặp ngày hiệu lực, tra được `mã → dòng còn hiệu lực`.
- Ba quy tắc mới đối chiếu tên: dịch vụ, thuốc, vật tư.
- Migration seed ba quy tắc ở trạng thái **tắt**.
- Đưa ba quy tắc mới vào lệnh đếm thử `kiemtraylenh:thu`, thêm cảnh báo tỉ lệ phân tích ngày.

### Không làm

- Quy tắc "thiếu tên BHXH": đo được 0 dòng.
- Chuẩn hoá tên trước khi so: đã chốt so tuyệt đối (mục 5.2).
- Đối chiếu hàm lượng / số đăng ký / TT thầu: HIS không có dữ liệu này ở mức y lệnh.
- Dùng `den_ngay_hd` của vật tư: đó là hạn hợp đồng, không phải hạn hiệu lực danh mục.
- Sửa `CatalogImportService` để chuẩn hoá ngày lúc nhập: đúng về lâu dài nhưng sẽ phải
  nhập lại toàn bộ danh mục, nằm ngoài phạm vi đợt này.
- Mở rộng sang `his_exp_mest_medicine` / `his_exp_mest_material`: việc riêng, chưa chốt.
- Sửa `Xml3176Xml2Checker` / `Xml3176Xml3Checker`: chúng đang đúng với dữ liệu của chúng.

## 5. Thiết kế

### 5.1 Điều kiện kích hoạt

Quy tắc tên chỉ xét một dòng khi **đủ cả sáu** điều kiện:

1. Dòng thuộc đối tượng BHYT — `BhytScope::laDongBhyt($s->patientTypeId)`, đã có.
2. Bảng danh mục tương ứng có dữ liệu — `CatalogLookup::sanSang()`, đã có.
3. Loại dịch vụ của dòng khớp phạm vi của quy tắc — mục 3.
4. Dòng có `tdl_intruction_time` hợp lệ — mục 3bis.1.
5. Dòng có khai `hein_service_bhyt_code`.
6. Mã đó **có** trong danh mục, xét trên các dòng **còn hiệu lực tại ngày chỉ định**.

Thiếu điều kiện 5 hoặc 6 thì quy tắc **mã** đã báo rồi. Quy tắc tên im lặng, không chồng
hai vi phạm lên cùng một dòng.

Điều kiện 4 và 6 áp dụng chung cho cả bốn quy tắc mã. Sau thay đổi này, một mã có trong
danh mục nhưng đã hết hiệu lực trước ngày chỉ định sẽ bị quy tắc **mã** báo — đúng nghiệp
vụ, vì BHXH cũng từ chối trường hợp đó.

Điều kiện 2 giữ nguyên tinh thần cũ: bảng danh mục rỗng thì im lặng hoàn toàn, vì đơn vị
chưa nhập danh mục mà thấy mọi dịch vụ thành vi phạm thì sẽ tắt luôn tính năng.

### 5.2 Phép so

```
ngayYmd = Ymd(tdl_intruction_time)
tenKhai = trim(hein_service_bhyt_name)
tapTen  = { trim(ten) : dòng danh mục mang mã này VÀ còn hiệu lực tại ngayYmd }

vi phạm  <=>  tenKhai !== ''  và  tenKhai ∉ tapTen
```

So **tuyệt đối**, phân biệt hoa thường, không chuẩn hoá khoảng trắng hay dấu gạch nối —
thống nhất với `Xml3176Xml2Checker:337`. Quyết định của người dùng ngày 2026-07-28.

`trim` là bắt buộc về mặt kỹ thuật, không phải nới lỏng: cột Oracle `VARCHAR2` thường
mang khoảng trắng đuôi do nhập liệu, và `CatalogLookup` hiện đã `trim` phía mã.

`tenKhai` rỗng thì bỏ qua, không báo — hệ quả trực tiếp của việc không làm quy tắc
"thiếu tên".

Hệ quả đã lường trước: đây là phép so chặt nhất có thể, nên số vi phạm ban đầu nhiều khả
năng cao. Đó là lý do quy tắc seed ở trạng thái tắt và có lệnh đếm thử.

### 5.3 Ba quy tắc mới

| Mã quy tắc | `rule_type` | Danh mục | Loại DV |
|---|---|---|---|
| `A_BHYT_SERVICE_NAME_MISMATCH` | `BhytServiceNameRule` | `service_catalogs.ten_dich_vu` | khác 6, 7 |
| `A_BHYT_DRUG_NAME_MISMATCH` | `BhytDrugNameRule` | `medicine_catalogs.ten_thuoc` | 6 |
| `A_BHYT_SUPPLY_NAME_MISMATCH` | `BhytSupplyNameRule` | `medical_supply_catalogs.ten_vat_tu` | 7 |

Tách khỏi ba quy tắc mã thay vì gộp, vì:

- Sai tên và thiếu mã là hai việc sửa khác nhau, do hai bộ phận khác nhau xử lý.
- Đơn vị cần bật quy tắc mã trước, để quy tắc tên tắt cho tới khi danh mục ổn định.

Tất cả `family = 'A'`, `severity = 'warning'`, `is_active = false`.

### 5.4 Nội dung mô tả vi phạm

Ghi cả tên khai lẫn tên danh mục để người sửa đối chiếu ngay, không phải mở thêm màn hình:

```
Tên thuốc lệch danh mục BHXH. Mã 40.805; khai "Wosulin 30/70 100IU/ml";
danh mục: "Wosulin 30/70", "INSUNOVA - 30/70 (BIPHASIC)"
```

Liệt kê tối đa **3** tên danh mục rồi thêm `…`. Có mã mang tới 226 tên, đổ hết vào cột
mô tả sẽ tràn.

Phần `context` của `Violation` mang `service_req_code`, `service_code`, `bhyt_code`,
`bhyt_name` — thống nhất với ba quy tắc mã, thêm đúng một khoá.

Khoá gộp (`dedupe key`) vẫn là `sereServId`, giống ba quy tắc mã.

## 6. Thay đổi mã nguồn

| Tệp | Việc |
|---|---|
| `Support/OrderService.php` | thêm `$serviceTypeId`, `$bhytName` |
| `HisOrderSource.php` | select thêm `sv.service_type_id`, `sv.hein_service_bhyt_name` |
| `Support/NgayHieuLuc.php` | **mới** — `phanTich()`, `conHieuLuc()` |
| `Support/CatalogLookup.php` | `nap()` lấy thêm tên + cặp ngày; thêm `tenTheoMa($ma, $ngay)`; `coTrongDanhMuc($ma, $ngay)`; `datSanChoTest()` nhận map |
| `RuleHandlers/Bhyt/BhytCatalogRule.php` | thêm `loaiDichVu()`, lọc theo loại; lọc theo hiệu lực; tách bước lọc dùng chung |
| `RuleHandlers/Bhyt/BhytServiceCatalogRule.php` | khai `loaiDichVu()` |
| `RuleHandlers/Bhyt/BhytDrugCatalogRule.php` | khai `loaiDichVu()` |
| `RuleHandlers/Bhyt/BhytSupplyCatalogRule.php` | khai `loaiDichVu()` |
| `RuleHandlers/Bhyt/BhytNameMismatchRule.php` | **mới** — lớp trừu tượng, kế thừa `BhytCatalogRule` |
| `RuleHandlers/Bhyt/BhytServiceNameRule.php` | **mới** |
| `RuleHandlers/Bhyt/BhytDrugNameRule.php` | **mới** |
| `RuleHandlers/Bhyt/BhytSupplyNameRule.php` | **mới** |
| `database/migrations/…_seed_order_check_bhyt_name_rules.php` | **mới** — seed 3 quy tắc, tắt |
| `Console/Commands/OrderCheckDryRun.php` | thêm 3 handler vào lệnh đếm thử |

`CatalogLookup::nap()` giữ nguyên cam kết cũ: **một truy vấn cho cả phiếu**, cộng dồn qua
nhiều lần gọi. Lấy thêm một cột không đổi số truy vấn.

### 6.1 Giao diện `CatalogLookup` sau khi sửa

```php
// $cotTen, $cotTu, $cotDen truyền qua hàm dựng
public function nap(array $ma)                    // giữ chữ ký; nay select 4 cột
public function coTrongDanhMuc($ma, $ngayYmd = null)   // $ngayYmd = null: không lọc hiệu lực
public function tenTheoMa($ma, $ngayYmd = null)        // MỚI: array tên đã trim, [] nếu không có
public function datSanChoTest(array $ma, array $dong = [])
```

Cả `coTrongDanhMuc` và `tenTheoMa` lọc hiệu lực **trong bộ nhớ**, không truy vấn lại. Đây
là lý do `nap()` phải kéo cả cặp ngày về ngay từ đầu: một lô y lệnh có nhiều ngày chỉ định
khác nhau, nếu lọc ngày trong SQL thì mỗi ngày là một truy vấn, phá vỡ cam kết một truy
vấn cho cả phiếu.

`$ngayYmd = null` giữ đường lui cho lời gọi cũ và cho test không quan tâm ngày.

`datSanChoTest` giữ tham số cũ ở vị trí một để 4 test hiện có không phải sửa; `$dong` là
`mã => [ ['ten'=>…, 'tu'=>…, 'den'=>…], … ]`.

## 7. Kiểm thử

Chạy `vendor/bin/phpunit --testsuite Unit`. Bộ `tests/Feature` đang đỏ sẵn vì lý do môi
trường, không dùng làm cổng.

Ca kiểm bắt buộc:

| Ca | Kỳ vọng |
|---|---|
| Danh mục rỗng (`sanSang() === false`) | không vi phạm nào |
| Dòng không phải BHYT | bỏ qua |
| Dòng đúng loại, mã có trong danh mục, tên khớp | không vi phạm |
| Dòng đúng loại, mã có, tên lệch | đúng 1 vi phạm, mô tả chứa cả hai tên |
| Mã có nhiều tên, tên khai khớp tên thứ hai | không vi phạm |
| Mã có nhiều tên, không khớp tên nào | 1 vi phạm, mô tả liệt kê tối đa 3 tên + `…` |
| Mã không có trong danh mục | quy tắc tên im lặng (quy tắc mã lo) |
| Tên khai rỗng | im lặng |
| Tên khai lệch mỗi khoảng trắng đuôi | không vi phạm (do `trim`) |
| Tên khai lệch hoa/thường | **có** vi phạm (so tuyệt đối) |
| Quy tắc thuốc gặp dòng xét nghiệm | bỏ qua — ca chặn lỗi mục 3 |
| Quy tắc dịch vụ gặp dòng thuốc | bỏ qua — ca chặn lỗi mục 3 |
| Một phiếu nhiều dòng | đúng một truy vấn danh mục |

Ca kiểm cho `NgayHieuLuc::phanTich`:

| Vào | Ra |
|---|---|
| `45292` (serial Excel) | `20240101` |
| `'45292'` | `20240101` |
| `45292.0` | `20240101` |
| `'20240101'` | `20240101` |
| `'01/01/2024'` | `20240101` |
| `'2024-01-01'` | `20240101` |
| `'01-01-2024'` | `20240101` |
| `''`, `null`, `'abc'`, `0` | `null` |

Ca kiểm cho `NgayHieuLuc::conHieuLuc`:

| Từ ngày | Đến ngày | Ngày xét | Kết quả |
|---|---|---|---|
| `20240101` | `20241231` | `20240601` | còn |
| `20240101` | `20241231` | `20231231` | chưa |
| `20240101` | `20241231` | `20250101` | hết |
| `20240101` | `20241231` | `20240101` | còn (bao gồm mốc đầu) |
| `20240101` | `20241231` | `20241231` | còn (bao gồm mốc cuối) |
| `''` | `20241231` | `20200101` | còn — fail-safe chiều `tu_ngay` |
| `20240101` | `''` | `20990101` | còn — fail-safe chiều `den_ngay` |
| `'abc'` | `'xyz'` | bất kỳ | còn — fail-safe cả hai chiều |

Ca kiểm hiệu lực ở mức quy tắc:

| Ca | Kỳ vọng |
|---|---|
| Mã có trong danh mục nhưng dòng đã hết hiệu lực trước ngày chỉ định | quy tắc **mã** báo vi phạm |
| Mã có hai dòng, một hết hiệu lực một còn, tên khớp dòng còn hiệu lực | không vi phạm |
| Mã có hai dòng, tên khớp **dòng đã hết hiệu lực** | quy tắc **tên** báo vi phạm |
| Dòng y lệnh không có `tdl_intruction_time` | bỏ qua hoàn toàn |

Cập nhật `ServiceReqRuleRegistryTest`: khẳng định theo mã quy tắc và tính duy nhất, không
đếm cứng số handler.

## 8. Triển khai

Quy tắc seed **tắt**, có chủ đích, cùng lý do với đợt quy tắc mã: ba bảng danh mục trên
máy phát triển đều 0 dòng nên không đo được tỉ lệ lệch tên thật trước khi triển khai.

Trình tự cho đơn vị:

1. Nạp đủ ba bảng danh mục BHXH.
2. Chạy `php artisan kiemtraylenh:thu --ngay=7` — chỉ đếm, không ghi gì.
3. Xem con số từng quy tắc. Nếu quy tắc tên ra hàng chục nghìn thì dữ liệu danh mục hoặc
   khai báo HIS có vấn đề hệ thống, xử lý gốc trước khi bật.
4. Bật từng quy tắc trên màn Quản lý quy tắc.

## 9. Rủi ro

| Rủi ro | Xử lý |
|---|---|
| So tuyệt đối ra quá nhiều vi phạm | seed tắt + lệnh đếm thử; nếu cần nới thì thêm tham số chuẩn hoá sau, không phải sửa kiến trúc |
| Danh mục nhập thiếu → báo oan | `sanSang()` chặn ở mức bảng; mã không có trong danh mục thì quy tắc tên im |
| Nạp thêm cột tên làm phình bộ nhớ | tên chỉ giữ cho mã thực sự xuất hiện trong phiếu; mã 226 tên là cá biệt, vẫn bị chặn bởi lô |
| Lọc loại dịch vụ dùng phần bù, loại mới rơi vào `service_catalogs` | có chủ đích: thà xét thừa còn hơn lặng lẽ bỏ sót |
| `tu_ngay` ở dạng lạ → lọc hiệu lực tự vô hiệu hoá im lặng | `kiemtraylenh:thu` in cảnh báo khi tỉ lệ phân tích được dưới 50% |
| Danh mục nhập thiếu đợt cũ → y lệnh cũ thành vi phạm hàng loạt | lệnh đếm thử chạy trước khi bật; mặc định `--ngay=7` nên chỉ chạm dữ liệu gần |
