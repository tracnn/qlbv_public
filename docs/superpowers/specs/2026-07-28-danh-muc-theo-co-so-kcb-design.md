# Danh mục theo cơ sở khám chữa bệnh

Ngày: 2026-07-28
Trạng thái: đã chốt thiết kế

## 1. Yêu cầu

Hệ thống phục vụ nhiều đơn vị. Ba danh mục **dịch vụ kỹ thuật, thuốc, vật tư y tế** do BHXH
cấp riêng cho từng cơ sở khám chữa bệnh. Hồ sơ của cơ sở nào phải được kiểm bằng danh mục
của cơ sở đó.

Các danh mục còn lại (ICD10, ICD YHCT, CSKCB, đơn vị hành chính, nghề nghiệp…) là **dùng
chung**, không đụng tới.

## 2. Khảo sát

Đo ngày 2026-07-28 trên HIS thật.

### 2.1 Đơn vị được nhận diện thế nào

```
HIS_BRANCH — 5 cơ sở khai, 2 đang phát sinh hồ sơ:
  id=1   Bạch Mai              HEIN_MEDI_ORG_CODE = 01929
  id=21  Bạch Mai CS2          HEIN_MEDI_ORG_CODE = 01929   <- dùng CHUNG mã với id=1
  id=41  TT Hồi sức tích cực   HEIN_MEDI_ORG_CODE = 79693
  id=61  Phòng Y tế cơ quan    HEIN_MEDI_ORG_CODE = 01283
  id=81  Cơ sở Ninh Bình       HEIN_MEDI_ORG_CODE = 37470

Hồ sơ 30 ngày:  01929 → 171.518    37470 → 56.427
```

**Khoá danh mục là mã CSKCB, không phải `branch_id`.** Cơ sở 1 và 21 dùng chung mã `01929`;
BHXH cấp danh mục theo mã CSKCB nên hai cơ sở đó dùng chung một bộ. Lấy `branch_id` làm khoá
sẽ buộc nhập trùng danh mục.

**Ánh xạ suy ra được từ HIS**, không phải khai tay: `HIS_BRANCH.HEIN_MEDI_ORG_CODE`. Khớp
chính xác với dữ liệu XML3176 đang có — `01929` (166 hồ sơ) và `37470` (44 hồ sơ).

### 2.2 Lấy mã cơ sở ở tầng nào

```
1.561.688 dòng dịch vụ / 7 ngày
  thiếu cơ sở thực hiện (his_sere_serv.tdl_execute_branch_id) : 0
  thiếu cơ sở hồ sơ     (his_treatment.branch_id)             : 0
  LỆCH giữa hai tầng : 278 dòng  (0,02%)
     hồ sơ Ninh Bình thực hiện ở Bạch Mai : 220
     hồ sơ Bạch Mai thực hiện ở Ninh Bình :  58
```

Lấy theo **hồ sơ** (`his_treatment.branch_id`). Khác hẳn đợt lọc đối tượng BHYT: ở đó hai
tầng lệch 30,17% nên buộc phải lọc ở mức dòng, còn ở đây lệch 0,02%.

Rẻ thêm một bậc: `HisOrderSource` **đã join sẵn** `his_treatment`, chỉ cần join thêm
`his_branch` để lấy mã CSKCB.

278 dòng lệch được chấp nhận kiểm theo cơ sở của hồ sơ, không phải cơ sở thực hiện.

### 2.3 Danh mục hai cơ sở có thật sự khác nhau — có

```
5.965 mã dịch vụ dùng trong 7 ngày
  1.000 mã (16,8%) dùng ở CẢ HAI cơ sở
Cơ sở 01929: 2.497 mã BHYT phân biệt
Cơ sở 37470: 1.590 mã BHYT phân biệt
```

Phần giao chỉ 1/6. Việc tách danh mục theo cơ sở là cần thật, không phải phòng xa.

### 2.4 Hai lỗi sẵn có phát hiện khi rà

**a. `service_catalogs` không có cột mã cơ sở nào.**

| Bảng | Cột mã cơ sở |
|---|---|
| `medicine_catalogs` | `ma_cskcb` |
| `medical_supply_catalogs` | `ma_cskcb` |
| `service_catalogs` | **không có** |

`config/catalog_import_mapping.php` *có* khai `ma_cskcb` cho danh mục dịch vụ, nhưng
`ServiceCatalog::$fillable` không có và bảng không có cột — giá trị bị bỏ **im lặng** mỗi
lần nhập.

**b. Cả ba danh mục không đưa mã cơ sở vào khoá duy nhất khi nhập:**

```
medicine : ma_thuoc, ten_thuoc, ham_luong, so_dang_ky, don_gia_bh, tt_thau, tu_ngay
supply   : ma_vat_tu, ten_vat_tu, tt_thau, don_gia_bh, tu_ngay
service  : ma_dich_vu, ten_dich_vu, don_gia, quy_trinh, tu_ngay
```

Nhập danh mục cơ sở thứ hai sẽ `updateOrCreate` **đè lên** dòng của cơ sở thứ nhất. Đây là
mất dữ liệu, và đã đúng như vậy từ trước khi có yêu cầu này.

### 2.5 Nơi tra danh mục

24 chỗ tra ba danh mục, rải ở 9 tệp:

```
Xml3176Xml2/3/4Checker : 8    Qd130Xml2/3/4Checker : 8
Xml2Checker/Xml3Checker: 5    CatalogImportService : 3
```

Phía XML3176 lấy mã cơ sở dễ: checker đã `load('Xml3176Xml1')` nên có `ma_cskcb`.

`xml3176_xml2s.ma_cskcb_thuoc` tồn tại nhưng **rỗng toàn bộ 6.987 dòng** — không dùng được,
phải lấy từ XML1. XML3 và XML4 không có cột cơ sở riêng.

## 3. Phạm vi

### Có làm

- Thêm cột `ma_cskcb` cho `service_catalogs`, mở `fillable`.
- Đưa `ma_cskcb` vào khoá duy nhất khi nhập cho cả ba danh mục.
- Ô chọn cơ sở trên màn nhập danh mục — mục 4.8.
- Lọc theo cơ sở ở **8 chỗ** của XML3176 (`Xml3176Xml2/3/4Checker`).
- Lọc theo cơ sở ở **3 luật danh mục BHYT** của order-check.
- `OrderContext` mang mã CSKCB; `HisOrderSource` join `his_branch`.

### Không làm

- QĐ130 (`Qd130Xml2/3/4Checker`) và bộ cũ (`Xml2Checker`, `Xml3Checker`) — người dùng chốt
  ngày 2026-07-28. Chấp nhận hai nơi xử lý khác nhau một thời gian. Bảng QĐ130 hiện 0 dòng
  nên cũng không kiểm chứng được bằng dữ liệu thật.
- Bảng ánh xạ cơ sở khai tay — suy được từ `HIS_BRANCH.HEIN_MEDI_ORG_CODE`.
- Sửa dữ liệu danh mục cũ — mục 4.3.
- Tách các danh mục dùng chung (ICD, CSKCB, nhân viên y tế, trang thiết bị).
- Dùng `tdl_execute_branch_id` (cơ sở thực hiện) — mục 2.2.

## 4. Thiết kế

### 4.1 Quy tắc khớp cơ sở

```
dòng danh mục khớp cơ sở X  <=>  ma_cskcb rỗng  HOẶC  ma_cskcb = X
```

"Rỗng" gồm cả `NULL` lẫn chuỗi rỗng: dữ liệu nhập từ Excel có thể ra một trong hai.

### 4.2 Cơ sở chưa nhập danh mục thì im lặng

Nếu bảng **không có dòng nào** khớp cơ sở X (kể cả dòng dùng chung), mọi quy tắc liên quan
im lặng cho hồ sơ của cơ sở đó — cùng cơ chế `sanSang()` đang dùng ở order-check.

Hệ quả: `sanSang()` chuyển từ hỏi "bảng có dữ liệu không" sang "cơ sở này có dữ liệu không",
nên phải nhận tham số mã cơ sở và nhớ kết quả **theo từng cơ sở**.

### 4.3 Dòng danh mục cũ dùng chung mọi cơ sở

Dòng có `ma_cskcb` rỗng khớp với **mọi** cơ sở. Người dùng chốt ngày 2026-07-28.

Đây là điều kiện để triển khai không gây thoái lui: dữ liệu danh mục trên máy chủ thật hiện
chưa gắn mã cơ sở; nếu lọc chặt thì cộng với mục 4.2, toàn bộ kiểm tra danh mục của XML3176
đang chạy sẽ **tắt ngóm mà không báo gì**.

Không cần migration dữ liệu. Đơn vị nhập lại danh mục có cột `MA_CSKCB` thì tự chuyển dần
sang theo cơ sở.

### 4.4 Khoá duy nhất khi nhập

Thêm `ma_cskcb` vào `unique_keys` của cả ba danh mục.

`CatalogImportService` bỏ qua khoá có giá trị `null`:

```php
$value = $this->getRowValue($row, $key, $fieldMapping);
if ($value !== null) { $uniqueKeys[$key] = $value; }
```

Nên hành vi tự phân nhánh đúng như mong muốn, không cần thêm điều kiện:

| Tệp Excel | Kết quả |
|---|---|
| Không có cột `MA_CSKCB` | khoá bỏ qua `ma_cskcb` → giữ nguyên hành vi cũ |
| Có cột `MA_CSKCB` | mỗi cơ sở một dòng riêng, không đè nhau |

### 4.5 Phía XML3176

Thêm scope dùng chung vào ba model:

```php
public function scopeCuaCoSo($q, $maCskcb)
{
    return $q->where(function ($w) use ($maCskcb) {
        $w->whereNull('ma_cskcb')->orWhere('ma_cskcb', '')
          ->orWhere('ma_cskcb', $maCskcb);
    });
}
```

Áp vào 8 chỗ tra:

| Tệp | Dòng | Model |
|---|---|---|
| `Xml3176Xml2Checker` | 297, 304, 312, 320 | `MedicineCatalog` |
| `Xml3176Xml3Checker` | 747 | `MedicalSupplyCatalog` |
| `Xml3176Xml3Checker` | 864, 877 | `ServiceCatalog` |
| `Xml3176Xml4Checker` | 91 | `ServiceCatalog` |

Mã cơ sở lấy từ `$data->Xml3176Xml1->ma_cskcb`. `Xml3176Xml2Checker` và `Xml3176Xml4Checker`
phải `load('Xml3176Xml1')` nếu chưa có — `Xml3176Xml3Checker` đã có sẵn.

### 4.6 Phía order-check

`CatalogLookup` giữ cam kết **một truy vấn cho cả phiếu**: `nap()` **không** lọc cơ sở trong
SQL mà kéo cả cột `ma_cskcb` về, lọc trong bộ nhớ — cùng cách đã làm với ngày hiệu lực. Lý
do như cũ: một lô y lệnh có thể thuộc nhiều cơ sở, lọc trong SQL thì mỗi cơ sở một truy vấn.

```php
__construct($bang, $cot, $cotTen = null, $cotTu = 'tu_ngay', $cotDen = 'den_ngay',
            array $dieuKien = [], $cotCoSo = null)

sanSang($maCskcb = null)                       // nho ket qua theo tung co so
coTrongDanhMuc($ma, $ngayYmd = null, $maCskcb = null)
tenTheoMa($ma, $ngayYmd = null, $maCskcb = null)
```

`$cotCoSo = null` nghĩa là bảng không có khái niệm cơ sở (hai bảng ICD, `medical_staffs`) —
giữ nguyên hành vi, không lọc.

`$maCskcb = null` ở các hàm tra nghĩa là không lọc cơ sở — đường lui cho lời gọi cũ và cho
test.

Ba luật `BhytServiceCatalogRule`, `BhytDrugCatalogRule`, `BhytSupplyCatalogRule` và ba luật
tên tương ứng truyền `$c->maCskcb` xuống. Ba luật ICD và luật CCHN **không** đổi.

### 4.7 Nguồn mã cơ sở cho order-check

`OrderContext::$maCskcb`, lấy trong `HisOrderSource::fetchServiceRequests()`:

```sql
leftJoin('his_branch as br', 'br.id', '=', 't.branch_id')
...
br.hein_medi_org_code as ma_cskcb
```

`t` là `his_treatment` đã join sẵn. Không thêm truy vấn nào.

Hồ sơ không đọc được mã cơ sở (`branch_id` rỗng hoặc cơ sở chưa khai `HEIN_MEDI_ORG_CODE`)
→ truyền `null` → không lọc cơ sở, giữ hành vi cũ. Đo được 0/1.561.688 dòng thiếu, nhưng
vẫn phải xử lý.

### 4.8 Chọn cơ sở trên màn nhập danh mục

Mẫu Excel sinh động từ `mapping` nên `MA_CSKCB` **đã** có sẵn trong cả ba mẫu. Nhưng nó
không nằm trong `required_fields` nên không được tô màu, lại lọt giữa 23 cột (vị trí 20 ở
thuốc, 18 ở vật tư, 7 ở dịch vụ) — rất dễ bỏ trống. Bỏ trống thì theo mục 4.3 dòng đó thành
dùng chung mọi cơ sở: sai, và im lặng.

Thêm ô chọn cơ sở trên màn nhập, nguồn từ `his_branch`:

```
— Dùng chung cho mọi cơ sở —
01283 — Phòng Y tế cơ quan Bệnh Viện Bạch Mai
01929 — BỆNH VIỆN BẠCH MAI
37470 — BỆNH VIỆN BẠCH MAI CƠ SỞ NINH BÌNH
```

Các cơ sở HIS cùng mã CSKCB **gộp thành một lựa chọn**. Cùng lý do với mục 2.1.

**Chỉ lấy cơ sở đang hoạt động** (`is_active = 1`, `is_delete = 0`). Trên HIS thật, Bạch Mai
CS2 (id=21) và TT Hồi sức tích cực COVID-19 (id=41) đang tắt, nên mã `79693` không còn trong
danh sách và nhãn của `01929` không kèm CS2.

Hệ quả đã lường: cơ sở `79693` có **1.320 hồ sơ lịch sử**. Chúng vẫn đọc được mã cơ sở bình
thường vì `HisOrderSource` **không** lọc theo trạng thái hoạt động — lọc ở đó sẽ làm mã thành
rỗng và hồ sơ mất luôn phần kiểm theo cơ sở. Chỉ là không chọn được `79693` trên ô chọn nữa;
muốn nhập danh mục cho nó thì điền cột `MA_CSKCB` trong tệp. Đây là lý do nữa để **không**
bắt buộc ô chọn.

Quy tắc ưu tiên:

```
dòng trong tệp có MA_CSKCB  ->  lấy theo TỆP
dòng bỏ trống                ->  lấy ô đã chọn trên màn nhập
không chọn gì                ->  để trống = dùng chung mọi cơ sở
```

Giá trị trong tệp luôn thắng vì một tệp có thể chứa nhiều cơ sở.

**Chỉ áp cho ba danh mục theo cơ sở.** ICD, nhân viên y tế, trang thiết bị… là danh mục dùng
chung, không đóng mã cơ sở vào.

**Không** đưa `ma_cskcb` vào `required_fields`: làm vậy thì tệp BHXH cấp — vốn thường không
có cột này — sẽ bị từ chối nhập, buộc mọi đơn vị kể cả nơi một cơ sở phải tự thêm cột.

Thay vào đó **tô màu riêng** cột `MA_CSKCB` trong ba mẫu Excel: xanh `DDEBF7`, khác màu vàng
`FFF2CC` của cột bắt buộc, kèm ghi chú trong ô giải thích rằng bỏ trống nghĩa là dùng chung
mọi cơ sở. Màn nhập có chú giải hai màu. Danh mục dùng chung (ICD, nghề nghiệp…) không tô.

HIS hỏng thì danh sách rỗng và màn nhập vẫn dùng được, chỉ mất khả năng chọn cơ sở.

## 5. Kiểm thử

Cổng: `vendor/bin/phpunit --testsuite Unit`.

### `CatalogLookup` sau mở rộng

| Ca | Kỳ vọng |
|---|---|
| `$cotCoSo = null` | không lọc cơ sở, hành vi như cũ |
| Dòng `ma_cskcb` rỗng, tra cơ sở bất kỳ | khớp |
| Dòng `ma_cskcb = NULL`, tra cơ sở bất kỳ | khớp |
| Dòng `ma_cskcb = 01929`, tra `01929` | khớp |
| Dòng `ma_cskcb = 01929`, tra `37470` | **không** khớp |
| `$maCskcb = null` khi tra | không lọc, khớp mọi dòng |
| `sanSang('01929')` khi chỉ có dòng của `37470` | false |
| `sanSang('01929')` khi có dòng dùng chung | true |
| `sanSang()` gọi hai cơ sở khác nhau | mỗi cơ sở một kết quả, không lẫn |
| Bảy luật BHYT hiện có | không đổi hành vi khi không truyền cơ sở |

### Sáu luật danh mục BHYT

| Ca | Kỳ vọng |
|---|---|
| Mã có trong danh mục cơ sở khác | báo vi phạm |
| Mã có trong danh mục cơ sở mình | không vi phạm |
| Mã có ở dòng dùng chung | không vi phạm |
| Cơ sở chưa nhập danh mục | im lặng |
| Phiếu không có mã cơ sở | không lọc, hành vi cũ |
| Lô y lệnh trộn hai cơ sở | mỗi dòng tra đúng cơ sở của mình, vẫn một truy vấn |

### Nhập danh mục

| Ca | Kỳ vọng |
|---|---|
| Excel không có cột `MA_CSKCB` | như cũ, không nhân đôi dòng khi nhập lại |
| Excel có `MA_CSKCB`, hai cơ sở | hai dòng riêng, không đè nhau |
| Nhập lại cùng cơ sở | cập nhật, không thêm dòng |
| Danh mục dịch vụ có `MA_CSKCB` | giá trị **được lưu** (trước đây bị bỏ im lặng) |
| Chọn cơ sở, tệp có `MA_CSKCB` khác | lấy theo **tệp** |
| Chọn cơ sở, dòng bỏ trống | lấy theo ô đã chọn |
| Không chọn cơ sở | khoá giữ nguyên, không thêm `ma_cskcb` |
| Hai cơ sở HIS cùng mã CSKCB | gộp thành một lựa chọn |
| Dòng `his_branch` thiếu mã CSKCB | bị bỏ khỏi danh sách |
| Nhập danh mục ICD kèm ô chọn cơ sở | **không** đóng mã cơ sở |

### XML3176

Phần này nằm trong checker gắn chặt model nên khó kiểm bằng PHPUnit. Kiểm bằng test quét mã
nguồn: 8 chỗ tra ở mục 4.5 đều phải có `cuaCoSo(`. Dùng trait `Tests\Support\LocComment` để
bỏ chú thích trước khi tìm, nếu không sẽ đỗ giả.

## 6. Rủi ro

| Rủi ro | Xử lý |
|---|---|
| Triển khai làm tắt kiểm tra danh mục đang chạy | Dòng rỗng dùng chung mọi cơ sở (4.3); không cần migration dữ liệu |
| Đơn vị nhập danh mục cơ sở B thiếu cột `MA_CSKCB` → thành dùng chung | Đúng thiết kế nhưng dễ nhầm; cần ghi rõ trong readme |
| `sanSang()` nhớ theo cơ sở, gọi sai khoá → im lặng oan | Có ca kiểm hai cơ sở khác nhau không lẫn kết quả |
| QĐ130 và bộ cũ vẫn tra toàn bộ danh mục | Ngoài phạm vi, ghi lại ở mục 3; hai nơi cho kết luận khác nhau tới khi làm tiếp |
| 278 dòng/tuần thực hiện chéo cơ sở bị kiểm theo cơ sở hồ sơ | 0,02%, chấp nhận |
