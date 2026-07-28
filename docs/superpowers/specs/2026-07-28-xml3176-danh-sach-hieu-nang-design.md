# Màn danh sách hồ sơ XML3176 — cắt bộ nhớ và sửa lỗi lựa chọn

Ngày: 2026-07-28
Phạm vi: `resources/views/bhyt/xml3176/index.blade.php` + `app/Http/Controllers/BHYT/BHYTXml3176Controller.php@fetchData`

## Vấn đề

Triển khai trên máy chủ mới (`C:\qlbv_public`, `memory_limit` 128 MB, `max_execution_time` 120 s),
màn `bhyt/xml3176/index` sinh hai lỗi liên tiếp:

```
production.ERROR: Out of memory (allocated 142606336) (tried to allocate 2621440 bytes)
  at .../Illuminate/Support/Arr.php:115
production.ERROR: Maximum execution time of 120 seconds exceeded
  at .../Illuminate/Support/Arr.php:115
```

Hai lỗi khác loại nhưng chết cùng một dòng. `Arr.php:115` là thân `Arr::dot()` —
`array_merge` trong vòng lặp đệ quy. Việc cả bộ nhớ lẫn thời gian đều cạn tại đúng
dòng này nghĩa là `Arr::dot()` chiếm phần áp đảo của request.

Trong Laravel 5.5, `Arr::dot()` chỉ tới được từ hai nơi:

1. Tầng Validation, với luật có ký tự `*` (cả app chỉ có `'xmls.*'` ở ba controller upload).
2. yajra DataTables — `DataProcessor::escapeRow()` gọi `array_dot()` **cho từng dòng**,
   vì `config/datatables.php:87` đặt `'escape' => '*'`.

Nhánh (2) là nhánh của màn này.

**Lưu ý về chứng cứ:** log không ghi URL nên chưa chứng minh được request nào chết
(fatal error của PHP bị bắt ở shutdown handler, stacktrace chỉ còn `#0 {main}`).
Commit `bec2e05` đã bổ sung `url` / `method` / `route` / `query` / `mem_peak_mb` vào
`App\Exceptions\Handler::context()` để lần sau truy nguyên được. Thiết kế này dựa
trên phân tích tĩnh, không dựa trên log đã xác nhận.

## Nguyên nhân

### A. Payload mang dữ liệu không ai dùng

`fetchData()` eager-load ba quan hệ:

| Quan hệ | Kiểu | Dùng ở đâu |
|---|---|---|
| `Xml3176ErrorResult` | hasMany, **không giới hạn** | chỉ `setRowClass()`, chỉ hỏi `isNotEmpty()` |
| `check_hein_card` | hasOne | chỉ `setRowClass()` |
| `Xml3176Information` | hasOne | chỉ các `addColumn()` phía server |

Không cột nào trong `columns` của DataTables ([index.blade.php:260](../../../resources/views/bhyt/xml3176/index.blade.php)) đọc ba quan hệ này.
Nhưng `Helper::convertToArray($row)` gọi `toArray()` nên chúng đi theo JSON ra
trình duyệt, và yajra chạy `array_dot()` + `e()` lên từng giá trị lồng bên trong.

Nặng nhất là `Xml3176ErrorResult`: hasMany không giới hạn, kéo về cả cột
`description` kiểu TEXT. Một hồ sơ có thể hàng trăm bản ghi lỗi. `lengthMenu` cho
chọn tới 2000 dòng/trang.

Chi phí lớn hơn nữa nằm ở **model Eloquent phía server** — hàng trăm nghìn đối tượng
được dựng chỉ để trả lời một câu hỏi đúng/sai.

Ngoài ra: khi lọc theo mã lỗi (`$xml3176_error_catalog_id`), nhánh code dùng
`whereHas` và **không** eager-load, nên `setRowClass()` lazy-load **một truy vấn cho
mỗi dòng** — lỗi N+1 sẵn có.

### B. Mỗi lần tải bảng phát sinh hai request

```js
table = $('#xml-list').DataTable({ serverSide: true, destroy: true, ajax: {...} });
table.ajax.reload();
```

`DataTable()` với `serverSide` tự gọi ajax khi khởi tạo; `reload()` gọi lần thứ hai.
`validateAndFetchData()` trong `partials/load_data_button.blade.php` tự chạy khi
trang tải xong, nên **mở màn hình lên đã là hai request nặng chạy chồng nhau**.
Bấm "Tải dữ liệu" thêm hai nữa.

Ở giới hạn 128 MB, hai request cùng lúc chia nhau bộ nhớ — nhiều khả năng đây là
khác biệt khiến máy cũ sống mà máy mới chết.

## Thiết kế

### 1. Thay eager-load tập lỗi bằng đếm

Trong `fetchData()`, thay `with(['Xml3176ErrorResult' => ...])` bằng
`withCount('Xml3176ErrorResult')` — áp dụng cho **cả ba nhánh** (`treatment_code`,
`patient_code`, nhánh lọc theo ngày), kể cả nhánh lọc theo mã lỗi vốn không eager-load.

Thuộc tính sinh ra: `xml3176_error_result_count` (đã xác minh bằng
`Str::snake('Xml3176ErrorResult').'_count'`).

`setRowClass()` đổi từ `$result->Xml3176ErrorResult->isNotEmpty()` sang
`$result->xml3176_error_result_count > 0`.

**Rủi ro đã loại trừ:** `withCount` thêm subquery vào mệnh đề SELECT, có thể làm nặng
hai truy vấn đếm của yajra. Đã đọc `QueryDataTable::prepareCountQuery()`: nó thay hẳn
SELECT bằng `'1' as row_count` và xoá select bindings khi truy vấn không "phức tạp"
(không có union/having/distinct/order by/group by). Truy vấn đếm chạy **trước**
`ordering()` trong `make()`, nên không có `order by` tại thời điểm đó. Subquery của
`withCount` bị loại khỏi cả hai truy vấn đếm.

### 2. Danh sách trắng cột được trả về

Thêm `->only([...])` vào chuỗi Datatables với đúng 15 khoá mà JS đọc:

```
ma_lk, ma_bn, ho_ten, ma_the_bhyt, ngay_sinh, ngay_vao, ngay_ra, ngay_ttoan,
created_at, updated_at, exported_at, submitted_at, is_signed, imported_by, action
```

Dùng **danh sách trắng** thay vì `removeColumn()` (danh sách đen) để quan hệ thêm
vào sau này không làm payload phình lại. Lỗi này sẽ không tái phát theo cùng cách.

**Thứ tự xử lý đã xác minh** trong `DataProcessor::process()`:

```
addColumns → editColumns → setupRowVariables → selectOnlyNeededColumns → removeExcessColumns
```

`setRowClass()` nằm trong `setupRowVariables`, chạy **trước** bước lọc cột → tô màu
dòng không bị ảnh hưởng. `DT_RowClass` nằm trong `DataProcessor::$exceptions`
(`['DT_RowId','DT_RowClass','DT_RowData','DT_RowAttr']`) nên `only()` không cắt nhầm.

Cột checkbox khai `"data": null` và render từ `row.ma_lk`; `ma_lk` có trong danh sách
trắng nên vẫn hoạt động. Sự kiện dblclick đọc `data.ma_lk` — cũng vậy.

### 3. Khởi tạo DataTable một lần

`fetchData(startDate, endDate)` tách làm hai phần:

- Lần gọi đầu: khởi tạo DataTable. Bỏ `destroy: true`, bỏ `table.ajax.reload()` ngay sau.
- Các lần sau: chỉ cập nhật khoảng ngày rồi `table.ajax.reload()`.

Khoảng ngày hiện được đóng kín trong closure `data:` qua biến `startDate`/`endDate`.
Vì DataTable không còn được dựng lại, phải chuyển hai giá trị này thành biến ở phạm
vi module (ví dụ `xml3176Range = {from, to}`) để closure đọc giá trị mới nhất.

Các bộ lọc khác vốn đã được closure `data:` đọc từ DOM tại thời điểm gửi request nên
không cần đụng tới — đúng như comment sẵn có ở `index.blade.php:293`.

Logic áp bộ lọc từ URL (`xml3176UrlFilters`) giữ nguyên vị trí: chạy đúng một lần
trong lần gọi `fetchData()` đầu tiên.

### 4. Nút "Hồ sơ 79/80a" gửi đúng bộ tham số

`export7980aData()` chuyển thẳng cả `$request` sang `Xml3176Xml7980aExport`. Bộ lọc
thực sự được đọc nằm trong `Xml3176Xml7980aExport::query()`:

`date_from, date_to, xml_filter_status, date_type, xml3176_error_catalog,
payment_date_filter, xml_export_status, xml_submit_status, treatment_code`

Đối chiếu với `#bulk-7980a-btn`:

| Tham số | Nút gửi | Export đọc | |
|---|---|---|---|
| `xml_export_status` | không | **có** | **lỗi** |
| `treatment_code` | có | có | đúng |
| `imported_by` | có | không | thừa, vô hại |
| `xml_sign_status` | có | không | thừa, vô hại |

Hệ quả của dòng đầu: người dùng lọc "Đã xuất XML" trên màn hình rồi tải 79/80a vẫn
nhận về cả hồ sơ chưa xuất — sai âm thầm, không báo lỗi.

Sửa: thêm `xml_export_status` vào bộ tham số nút gửi. Giữ nguyên `treatment_code`
(export có đọc, và khi có giá trị thì nó bỏ qua mọi điều kiện khác). Hai tham số thừa
để nguyên — chúng vô hại, và việc export bỏ qua `imported_by` / `xml_sign_status` là
thiếu sót phía server, thuộc nợ kỹ thuật bên dưới.

**Không thuộc phạm vi:** hai nút *Export lỗi* và *Export xlsx* gửi **đúng** những gì
endpoint của chúng đọc. Chỗ lệch nằm ở phía server — các endpoint xuất chỉ nhận 9–10
bộ lọc trong khi màn danh sách có 14 (thiếu `patient_code`, `hein_card_filter`,
`treatment_type_fillter`, và `treatment_code` với hai endpoint kia). Sửa việc đó phải
động vào các lớp `Export`. Ghi nhận thành nợ.

**Ghi chú củng cố chẩn đoán:** `Xml3176Xml7980aExport::query()` mở đầu bằng
`set_time_limit(1800)` và `ini_set('memory_limit', '4096M')` — các luồng xuất tự nới
giới hạn cho mình. `fetchData()` thì không. Điều này giải thích vì sao trên máy chủ
mới các nút xuất vẫn chạy được mà riêng màn danh sách chết, và củng cố việc chọn
`fetchData` làm mục tiêu sửa.

### 5. Lựa chọn bền qua các lần tải

`updateSelectedRecords()` hiện **dựng lại** mảng từ checkbox đang có trên DOM:

```js
selectedRecords = [];
$('.row-select:checked').each(function () { selectedRecords.push($(this).val()); });
```

Với `serverSide`, DOM chỉ chứa trang hiện tại. Chọn vài hồ sơ ở trang 1, sang trang 2
tích thêm một cái → **toàn bộ lựa chọn trang 1 bị xoá**. `#bulk-action-btn` cũng chỉ
gom từ trang hiện tại.

Đây là lý do người dùng buộc phải đặt cỡ trang 2000 — không phải vì tiện, mà vì đó là
cách duy nhất để lựa chọn không bị mất.

Sửa:

- `selectedRecords` chuyển thành `Set` (hoặc object map nếu cần đỡ trình duyệt cũ),
  bền qua các lần `ajax.reload()`.
- Sự kiện `.row-select` change: `add`/`delete` một mã, không dựng lại.
- `#select-all`: add/delete toàn bộ mã của **trang hiện tại** (giữ nguyên ngữ nghĩa
  hiện có của DataTables — không mở rộng thành "chọn tất cả theo bộ lọc").
- `applySelectedCheckboxes()`: tra `Set` thay vì `Array.includes()` — hết O(n²) ở 2000 dòng.
- `#bulk-action-btn` và `exportSelectedRecordsToXml()`: đọc thẳng từ `Set`.

Sau thay đổi này cỡ trang 2000 vẫn còn, nhưng người dùng không còn bị ép dùng nó.

### 6. Modal chi tiết khởi tạo lại được

`initializeModalDataTables()` khởi tạo 6 DataTable không có `destroy`, nên mở modal
lần thứ hai ném "Cannot reinitialise DataTable". Thêm `destroy: true` cho cả sáu.

## Ngoài phạm vi (nợ kỹ thuật)

1. **`exportXml()`** dựng tuần tự tới 2000 file XML trong một request rồi mới nén zip
   ([BHYTXml3176Controller.php:566](../../../app/Http/Controllers/BHYT/BHYTXml3176Controller.php)).
   Trên máy chủ mới đây là bức tường tiếp theo sẽ đổ, và đúng vào thao tác người dùng
   hay làm nhất. App đã có sẵn cơ chế job + đồng hồ theo dõi (`bhyt.xml3176.jobs.status`).
2. **Các endpoint xuất nhận thiếu bộ lọc** so với màn danh sách (mục 4).
3. **`uploadData()`** với luật `'xmls.*' => 'mimes:xml|max:102400'` là nhánh còn lại
   tới `Arr::dot()`. Nếu log mới (commit `bec2e05`) cho thấy URL lỗi là
   `xml3176/index/upload-data` thì nguyên nhân nằm ở đây chứ không phải màn danh sách,
   và thiết kế này cần được xem lại.
4. **`setInterval(checkJobStatus, 5000)`** chạy mãi, cộng thêm 4 lời gọi rời rạc khác.

## Kiểm chứng

**Tự động (unit test):** chỉ phần logic thuần —

- Tham số nút 79/80a khớp đúng tập tham số `export7980aData` đọc.
- Gom/tách lựa chọn giữ được mã từ trang trước.

Cổng kiểm thử của repo là `vendor/bin/phpunit --testsuite Unit` (toàn bộ
`tests/Feature` đỏ sẵn vì lý do môi trường). Mốc hiện tại: 254 test xanh.

**Thủ công — bắt buộc, vì DB dev trống cả bốn bảng `xml3176_*` nên không đo được
trước/sau tại chỗ:**

1. Tab Network: mở màn danh sách → đúng **một** request `fetch-data`, không phải hai.
2. Bấm "Tải dữ liệu" → thêm đúng **một** request.
3. So kích thước JSON trả về trước/sau ở cùng bộ lọc và cùng cỡ trang.
4. Đặt cỡ trang 2000 trên khoảng một tháng → không còn lỗi hết bộ nhớ.
5. Dòng có lỗi XML vẫn được tô đỏ (`highlight-red`).
6. Lọc theo mã lỗi → vẫn tô đỏ đúng, và số truy vấn không tăng theo số dòng.
7. Chọn hồ sơ ở trang 1, sang trang 2 tích thêm, quay lại trang 1 → lựa chọn cũ còn
   nguyên; "Xuất XML3176" nhận đủ cả hai trang.
8. Lọc "Đã xuất XML" rồi tải 79/80a → file chỉ chứa hồ sơ đã xuất.
9. Mở modal chi tiết hai lần liên tiếp → không lỗi console.
