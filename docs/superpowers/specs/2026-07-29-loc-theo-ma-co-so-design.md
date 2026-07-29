# Lọc theo mã cơ sở ở màn XML3176 và order-check

Ngày: 2026-07-29

## Mục tiêu

Thêm bộ lọc theo mã cơ sở KCB vào hai màn danh sách: hồ sơ XML3176 và vi phạm y lệnh
(order-check). Hệ thống đang phục vụ nhiều cơ sở, nhưng hai màn này trộn chung dữ liệu của
mọi cơ sở.

## Hiện trạng đo được

Hai màn ở tình thế **hoàn toàn khác nhau** — đây là điều quyết định khối lượng công việc.

### XML3176 — dữ liệu đã sẵn sàng

Bảng `xml3176_xml1s` có cột `ma_cskcb`, đã đầy dữ liệu:

| Mã cơ sở | Số hồ sơ |
| --- | --- |
| 01929 | 166 |
| 37470 | 44 |

Bảng `xml3176_informations` cũng có cột `macskcb` với đúng phân bố đó. Chọn lọc trên
`xml3176_xml1s.ma_cskcb` vì đó chính là model của truy vấn danh sách — không phải thêm
ràng buộc qua quan hệ.

### Order-check — thiếu cột, nhưng dữ liệu đã chảy tới nơi

Bảng `order_check_violations` **không có** cột mã cơ sở nào.

Không thể lọc bằng cách nối bảng lúc truy vấn: vi phạm nằm ở MySQL còn HIS ở Oracle, hai
cơ sở dữ liệu khác nhau nên không JOIN được.

Tin tốt: `HisOrderSource::fetchServiceRequests` (`app/Services/OrderCheck/HisOrderSource.php:32`)
**đã lấy sẵn** `br.hein_medi_org_code as ma_cskcb`, và `OrderContext` đã có trường
`$maCskcb` (`app/Services/OrderCheck/Support/OrderContext.php:29`). Chỉ là
`ViolationContext` không mang nó theo và `OrderCheckEngine::persist()` không ghi xuống.
Dữ liệu đã chảy tới bước cuối rồi bị bỏ rơi.

### Vá ngược dữ liệu cũ — đã đo chính xác

1.065 vi phạm hiện có không dòng nào biết mình thuộc cơ sở nào, và **chúng không bao giờ
được quét lại** — bộ quét chỉ chạy tới trước theo mốc thời gian.

Tra ngược qua `treatment_id`:

```
order_check_violations.treatment_id
   → HIS_TREATMENT.BRANCH_ID
      → HIS_BRANCH.HEIN_MEDI_ORG_CODE
```

| | Số lượng |
| --- | --- |
| `treatment_id` phân biệt cần tra | 890 |
| Tra ra được | 829 (toàn bộ là `01929`) |
| Không tra ra | 61 |
| Số **dòng vi phạm** không tra ra được | 72 / 1.065 (6,8%) |

61 `treatment_id` không tra ra vì đợt điều trị đó **đã biến mất khỏi `his_treatment`** — đã
kiểm riêng, không phải do thiếu `branch_id`.

72 dòng đó để **trống** mã cơ sở. Không gán mặc định `01929`: gán bừa thì chúng trông như
đã biết chắc thuộc 01929, trong khi không ai kiểm chứng được nữa. Hệ quả người dùng cần
biết: khi lọc theo một cơ sở cụ thể, 72 dòng đó không hiện ra; chúng chỉ hiện khi không
chọn lọc.

## Thiết kế

### 1. Nguồn danh sách cơ sở cho ô chọn

Dùng lại `App\Services\BHYT\DanhSachCoSo::danhSach()` đã có sẵn — nó đọc `his_branch` với
`is_active = 1` và `is_delete = 0`, gom các chi nhánh dùng chung một `hein_medi_org_code`.
Đây đúng là nguồn mà màn nhập khẩu danh mục đang dùng, nên hai nơi luôn hiện cùng một danh
sách.

Ô chọn luôn có lựa chọn đầu là **"Tất cả cơ sở"** (giá trị rỗng). Không chọn gì thì không
lọc — giống mọi bộ lọc khác của hai màn.

### 2. XML3176 — thêm bộ lọc vào cả ba nhánh truy vấn

`BHYTXml3176Controller::fetchData` có **ba nhánh** dựng truy vấn riêng biệt:

1. Tìm theo `treatment_code`
2. Tìm theo `patient_code`
3. Lọc theo khoảng ngày (nhánh mặc định)

Bộ lọc mã cơ sở phải áp cho **cả ba**. Đây là cạm bẫy chính của phần này: nếu chỉ thêm vào
nhánh khoảng ngày, thì khi người dùng tra theo mã hồ sơ hoặc mã bệnh nhân, bộ lọc cơ sở bị
bỏ qua **im lặng** — kết quả vẫn hiện ra, chỉ là sai phạm vi, không có dấu hiệu gì báo.

Cách chống lặp: một method riêng nhận query rồi áp điều kiện, gọi ở cả ba nhánh:

```php
protected function locTheoCoSo($query, Request $request)
```

Nó đọc `ma_cskcb` từ request, bỏ qua nếu rỗng, và **kiểm giá trị nằm trong**
`DanhSachCoSo::danhSach()` trước khi áp — không hợp lệ thì coi như không lọc, không ném
lỗi (đây là màn danh sách, không phải thao tác ghi).

Giao diện: thêm một ô `<select>` vào khu vực lọc của `resources/views/bhyt/xml3176/index.blade.php`
và gửi kèm trong tham số DataTables.

Lưu ý: `BHYTXml3176Controller::index()` hiện trả `view('bhyt.xml3176.index')` **không kèm
dữ liệu nào** (dòng 74). Phải sửa thành truyền `danhSachCoSo` xuống, nếu không ô chọn sẽ
rỗng.

### 3. Order-check — thêm cột, ghi lúc quét, vá ngược dữ liệu cũ

**Migration** làm ba việc theo đúng thứ tự:

1. Thêm cột `ma_cskcb VARCHAR(20) NULL` vào `order_check_violations`, kèm index — bộ lọc sẽ
   chạy trên cột này.
2. Vá ngược: gom `treatment_id` phân biệt, tra HIS theo lô (900 mỗi lô, tránh giới hạn 1000
   phần tử của mệnh đề `IN` trong Oracle), dựng bảng tra `treatment_id → ma_cskcb`, rồi cập
   nhật gom nhóm theo mã cơ sở (mỗi mã một câu `UPDATE ... WHERE treatment_id IN (...)`)
   thay vì cập nhật từng dòng.
3. `down()`: xoá index rồi xoá cột.

Migration phải **chạy lại được nhiều lần không vỡ**: kiểm cột đã tồn tại chưa trước khi
thêm; bước vá ngược chỉ đụng dòng đang có `ma_cskcb` là `NULL`.

Nếu kết nối HIS lỗi lúc migrate, **không được để migration chết giữa chừng** làm cột đã
thêm mà dữ liệu chưa vá. Bọc bước 2 trong `try/catch`: lỗi thì ghi cảnh báo ra console và
để cột trống — chạy lại migration sau sẽ vá tiếp, vì bước 2 chỉ đụng dòng `NULL`.

**Đường ghi mới:**

- `ViolationContext` thêm trường `public $maCskcb`, `make()` đọc khoá `ma_cskcb`,
  `fromOrderContext()` chuyển `$o->maCskcb` sang.
- `OrderCheckEngine::persist()` thêm một dòng `$row->ma_cskcb = $ctx->maCskcb;`.

**Bộ lọc:** `ViolationQueryService::filtered()` thêm một khối, đúng khuôn các khối đang có:

```php
if ($request->filled('ma_cskcb')) {
    $q->where('ma_cskcb', $request->input('ma_cskcb'));
}
```

Nó phục vụ cả ba đường `fetch`, `summary`, `export` vì cả ba dùng chung method này.

Giao diện: thêm ô `<select>` vào khu vực lọc của `resources/views/khth/order-check.blade.php`
và thêm `ma_cskcb` vào hàm `filters()` (dòng 80) — đó là **nơi duy nhất** gom tham số, và
cả bảng, phần tổng hợp lẫn nút xuất Excel đều gọi nó, nên sửa một chỗ là đủ cho cả ba.

`OrderCheckController::index()` phải truyền thêm `danhSachCoSo` xuống view (hiện chỉ truyền
`rules`).

## Kiểm thử

Toàn bộ trong `tests/Unit`. Cổng: `vendor/bin/phpunit --testsuite Unit`.

**Lược đồ và đường ghi** (`ViPhamMaCoSoTest`):

1. `order_check_violations` có cột `ma_cskcb`.
2. `ViolationContext::make(['ma_cskcb' => '01929'])` giữ được giá trị.
3. `ViolationContext::fromOrderContext()` chuyển được `maCskcb` từ `OrderContext` sang —
   đây là mắt xích dễ quên nhất, vì `fromOrderContext` là một danh sách khoá chép tay.

**Bộ lọc order-check** (`ViolationQueryServiceTest` hoặc bổ sung vào tệp test sẵn có):

1. Request không có `ma_cskcb` → SQL sinh ra **không** chứa mệnh đề `ma_cskcb`.
2. Request có `ma_cskcb = '01929'` → SQL chứa mệnh đề đó, và tham số ràng buộc đúng
   `'01929'`.

Kiểm bằng `->toSql()` và `->getBindings()`, không cần chạm dữ liệu.

**Bộ lọc XML3176** (`Xml3176LocCoSoTest`):

1. Ba nhánh truy vấn (`treatment_code`, `patient_code`, khoảng ngày) đều áp được điều kiện
   `ma_cskcb` — đây là bài kiểm chống đúng cạm bẫy đã nêu ở mục 2. Kiểm bằng cách gọi
   `locTheoCoSo()` trên một query dựng sẵn rồi soi `toSql()`/`getBindings()`.
2. `ma_cskcb` không nằm trong `DanhSachCoSo::danhSach()` → không áp điều kiện nào.

## Phạm vi không làm

- Không thêm bộ lọc vào dashboard XML3176 (`dashboard/xml3176`) — yêu cầu nói "màn hình
  XML3176", tức màn danh sách hồ sơ. Dashboard là việc riêng nếu cần.
- Không đổi cách phân quyền xem hồ sơ theo `imported_by` đang có.
- Không thêm cột mã cơ sở vào các bảng XML3176 khác — dữ liệu đã có sẵn ở nơi cần.
- Không gán mã cơ sở mặc định cho 72 dòng không tra ra được.
- Không sửa email digest hay API của order-check để lọc theo cơ sở.

## Việc người dùng phải làm sau khi triển khai

Chạy `php artisan migrate`. Bước vá ngược cần **kết nối HIS còn sống**; nếu lúc migrate HIS
không truy cập được, cột sẽ để trống và cần chạy lại migration sau.
