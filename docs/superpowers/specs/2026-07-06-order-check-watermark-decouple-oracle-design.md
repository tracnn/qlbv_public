# Spec: Tách khởi tạo watermark order-check khỏi Oracle lúc migrate

**Date:** 2026-07-06
**Status:** Approved (chờ user review spec)

---

## 1. Vấn đề

Migration `2026_06_30_100006_init_order_check_watermarks` đọc HIS (Oracle `HISPro`) ngay trong `up()` để đặt mốc quét = MAX hiện tại:

```php
DB::connection(config('order_check.his_connection'))->table('his_service_req')->max('modify_time'); // + 3 bảng khác
```

Khi `php artisan migrate` chạy ở **CLI của site chưa kết nối được Oracle**, `oci_connect` trả `false` → yajra-oci8 (PHP 7.4) ném lỗi khó hiểu `Trying to access array offset on value of type bool` (Oci8.php:460). Web chạy được (dashboard đọc HISPro OK) nhưng CLI thì không → **deploy báo lỗi ở một số CSKCB**, migration order-check không áp được.

## 2. Mục tiêu

- `migrate` **không phụ thuộc** kết nối Oracle từ CLI → chạy sạch ở **mọi** CSKCB, dù site đó có dùng order-check hay không.
- Watermark vẫn khởi tạo = **MAX hiện tại** ("bắt từ lúc triển khai, KHÔNG backfill lịch sử") — thực hiện tại **runtime của scanner** (nơi Oracle chạy được).
- Không đụng logic 4 scanner; tập trung thay đổi ở một chỗ.

## 3. Ràng buộc / bẫy đã xác định

`OrderCheckEngine::getWatermark` hiện dùng `firstOrCreate(..., ['last_create_time'=>0,'last_modify_time'=>0,'last_id'=>0])`. Scanner fetch các bản ghi **> watermark**. Nếu watermark = 0 (mặc định), scanner **quét lại toàn bộ lịch sử** (backfill 35M dòng, sinh violation cho dữ liệu cũ). ⇒ **Không được** để watermark = 0 làm mốc khởi tạo thực tế.

## 4. Kiến trúc (Hướng A — lazy-init tại runtime)

### 4.1. Migration trở thành no-op
`InitOrderCheckWatermarks::up()` **bỏ toàn bộ lệnh đọc Oracle**, chuyển thành no-op có chú thích (khởi tạo mốc đã dời sang runtime scanner). `down()` giữ nguyên (xóa các dòng watermark — vô hại nếu chưa có).

- Site đã chạy migration bản cũ thành công (CLI có Oracle) → migration đã ghi nhận là "ran", **không chạy lại**; các dòng watermark đã có MAX → không ảnh hưởng.
- Site migration từng **fail** hoặc chưa chạy → lần deploy tới chạy bản no-op → **thành công**, không cần Oracle.

### 4.2. Khởi tạo mốc tại `getWatermark` (tập trung, an toàn)
`OrderCheckEngine::getWatermark($sourceKey)`:

```php
$wm = OrderCheckWatermark::firstOrCreate(
    ['source_key' => $sourceKey],
    ['last_create_time' => 0, 'last_modify_time' => 0, 'last_id' => 0]
);

if ($wm->wasRecentlyCreated) {
    // Lần đầu tạo dòng => đặt mốc = MAX hiện tại (đọc HIS), KHÔNG backfill lịch sử.
    $init = $this->source->initialWatermark($sourceKey); // ['last_create_time','last_modify_time','last_id']
    $wm->last_create_time = $init['last_create_time'];
    $wm->last_modify_time = $init['last_modify_time'];
    $wm->last_id          = $init['last_id'];
    $wm->last_run_at      = now();
    $wm->save();
}

return $wm;
```

- `wasRecentlyCreated` = `true` chỉ ở lần `firstOrCreate` thực sự tạo dòng ⇒ init đúng một lần.
- Đọc Oracle xảy ra **trong tiến trình scanner** (nơi Oracle sẵn sàng), KHÔNG phải lúc migrate.
- Scanner sau đó fetch `> MAX` = 0 dòng ở nguồn đó ⇒ không backfill; các vòng sau chạy bình thường.

### 4.3. Nguồn cung cấp MAX: `HisOrderSource::initialWatermark($sourceKey)`
Trả về mảng `['last_create_time'=>int, 'last_modify_time'=>int, 'last_id'=>int]` theo đúng ánh xạ migration cũ:

| source_key | Cách lấy MAX | Trường đặt |
|---|---|---|
| `his_service_req` | `MAX(his_service_req.modify_time)` | `last_modify_time` (create/id = 0) |
| `his_medicine_interactive` | `MAX(his_medicine_interactive.id)` | `last_id` (còn lại = 0) |
| `his_exp_mest_medicine` | `MAX(his_exp_mest_medicine.id)` | `last_id` (còn lại = 0) |
| `his_sere_serv_restriction` | `MAX(his_sere_serv.id)` | `last_id` (còn lại = 0) |

- source_key lạ / không map ⇒ trả `['last_create_time'=>0,'last_modify_time'=>0,'last_id'=>0]` (an toàn: giữ hành vi mặc định).
- Đọc qua `DB::connection($this->conn)` (chính là `order_check.his_connection` = `HISPro`).

## 5. Luồng theo tình huống

- **Site có Oracle-CLI, migration cũ đã chạy:** watermark rows có sẵn (MAX). `getWatermark` trả rows đã lưu, `wasRecentlyCreated=false` ⇒ chạy như cũ. Không đổi hành vi.
- **Site migration fail/chưa chạy:** migrate (no-op) OK. Lần scanner đầu ⇒ `firstOrCreate` tạo dòng ⇒ init = MAX ⇒ bỏ qua batch ⇒ từ đó bắt y lệnh mới. Đúng ý "từ lúc deploy".
- **Site không dùng order-check:** scanner không chạy ⇒ watermark rows không tạo ⇒ vô hại. Migrate vẫn OK.

## 6. Xử lý lỗi & biên

- Nếu Oracle lỗi lúc scanner init (`initialWatermark`) ⇒ ném lỗi bình thường (scanner cần Oracle mới hoạt động; không thể quét khi HIS không truy cập được). KHÔNG nuốt lỗi để đặt 0 (tránh backfill).
- `initialWatermark` với source_key không xác định ⇒ trả toàn 0 (không vỡ).
- Không thêm cột DB mới; dùng cờ `wasRecentlyCreated` của Eloquent.

## 7. Kiểm thử

**Unit (không cần Oracle):** dùng sqlite in-memory cho model `OrderCheckWatermark` + **fake source** implement `initialWatermark()` trả giá trị cố định.
- Lần đầu `getWatermark('his_service_req')` ⇒ trả watermark có `last_modify_time` = giá trị fake MAX, và dòng được lưu (đọc lại DB thấy đúng).
- Lần hai `getWatermark` cùng key ⇒ trả đúng giá trị đã lưu, KHÔNG gọi lại `initialWatermark` (fake source đếm số lần gọi = 1).
- `getWatermark` cho key khác ⇒ init riêng.

**Migration:** `php -l` file migration; xác nhận `up()` không còn tham chiếu `DB::connection`/`->max(`.

**Smoke (môi trường có Oracle):** gọi `HisOrderSource::initialWatermark('his_service_req')` trả `last_modify_time` > 0 khớp `MAX(modify_time)`; các key id trả `last_id` > 0.

## 8. Out of scope (YAGNI)

- Không sửa 4 scanner (logic init tập trung ở `getWatermark`).
- Không thêm cột/flag DB mới.
- Không đụng cấu hình dịch vụ Windows hay quy trình `update` script.
- Không xử lý việc cài Oracle client cho CLI (không còn cần cho migrate; nếu site muốn chạy scanner thì vẫn cần Oracle ở runtime — nằm ngoài thay đổi này).
