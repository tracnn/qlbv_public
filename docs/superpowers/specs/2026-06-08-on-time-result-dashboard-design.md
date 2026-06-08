# Spec: Dashboard Tỷ lệ trả kết quả đúng hẹn (On-Time Result)

**Date:** 2026-06-08
**Status:** Approved (chờ user review spec)

> **Cập nhật 10/06/2026 (đổi định nghĩa thời gian):** "Thời gian xử lý" đổi từ `finish_time − intruction_time` (tổng turnaround gồm cả chờ) sang **`finish_time − start_time`** (chỉ thời gian thực hiện) — so sánh với `estimate_duration` (thời gian thực hiện dự kiến) cho đúng bản chất. Kiểm chứng dữ liệu: mọi dòng có `finish_time` đều có `start_time` (0 dòng thiếu), 0 dòng finish<start → không phát sinh edge case. Tác động (tuần test): % đúng hẹn 32.5% → 58.3%, TB 152→91 phút. Bảng chi tiết hiển thị "Giờ bắt đầu" (start_time) thay cho "Giờ chỉ định"; nhãn đổi "TG thực tế"→"TG xử lý". Filter và "ngày" (trend) vẫn theo `intruction_time`.
>
> **Cập nhật 09/06/2026 (sau triển khai — lý do hiệu năng):** Cột `his_service.estimate_duration` KHÔNG có index nên điều kiện `estimate_duration IS NOT NULL AND <> 0` làm query chậm. Đã đổi sang **lọc theo nhóm dịch vụ** `his_sere_serv.tdl_service_type_id IN (2,3,5,10)` (XN/CĐHA/TDCN/Siêu âm — cột CÓ index). Hệ quả: trong các nhóm này có một số dòng dịch vụ chưa khai báo `estimate_duration` (≈364 dòng/tuần). Các dòng đó được phân loại thành trạng thái mới **`khong_hen`** (ưu tiên đầu trong `classify()`), **loại khỏi mẫu số %**, đếm và hiển thị riêng 1 card KPI "Không có hẹn" — nhất quán với cách xử lý "Chưa trả"/"Bất thường". Kiểm chứng: các con số có hẹn (đúng 8205 / trễ 17052 / chưa trả 98 / bất thường 1) KHÔNG đổi so với cách lọc cũ → tỷ lệ % giữ nguyên. Mục 2.1, 2.3 và 10 dưới đây đọc theo ghi chú này.

---

## 1. Mục tiêu

Xây dựng một trang report trong menu **KHTH** tính và hiển thị **tỷ lệ % trả kết quả đúng hẹn / không đúng hẹn** của các dịch vụ cận lâm sàng, phục vụ bộ tiêu chí chấm điểm chất lượng bệnh viện ("83 tiêu chí" — chỉ là bối cảnh, KHÔNG chia nhỏ theo 83 mục).

Trang gồm **phần tổng hợp** (4 chiều breakdown) và cho phép **xem chi tiết** từng dòng kết quả (drill-down + export Excel).

---

## 2. Phạm vi dữ liệu & định nghĩa chỉ số

### 2.1. Đối tượng tính (mẫu)

Mỗi dòng `his_sere_serv` của dịch vụ **có hẹn**, tức `his_service.estimate_duration IS NOT NULL AND <> 0`.

- Khảo sát thực tế: chỉ ~271 dịch vụ có `estimate_duration`, thuộc nhóm: Xét nghiệm (121), Chẩn đoán hình ảnh (108), Siêu âm (28), Thăm dò chức năng (14).
- Dịch vụ KHÔNG có `estimate_duration` (thuốc, vật tư, giường, khám, phẫu thuật, thủ thuật…) được **loại khỏi mẫu** vì không có ngưỡng hẹn để so sánh.
- Điều kiện sạch dữ liệu: `his_sere_serv.is_delete = 0`, `his_sere_serv.is_no_execute IS NULL` (loại các dòng được đánh dấu "không thực hiện" — cột chỉ có giá trị `NULL` hoặc `1`), `his_service_req.is_active = 1`, `his_service_req.is_delete = 0`.

### 2.2. Công thức (đơn vị: phút)

- **TG thực tế trả KQ** = `his_service_req.finish_time − his_service_req.intruction_time`
  (Lưu ý: lấy cả 2 mốc từ bảng cha `his_service_req`, KHÔNG lấy `his_sere_serv.tdl_intruction_time`. Một `service_req` có nhiều dòng `sere_serv`; mỗi dòng được tính là 1 "kết quả" với cùng cặp mốc thời gian của req cha.)
- **TG hẹn** = `his_service.estimate_duration`, lấy **theo từng dòng** sere_serv qua join `his_service ON his_service.id = his_sere_serv.service_id` (KHÔNG lấy theo req cha — mỗi dòng có service_id riêng nên estimate_duration riêng).
- Thời gian lưu dạng `NUMBER` định dạng `YYYYMMDDHH24MISS`. Quy đổi phút:
  `(TO_DATE(finish_time,'YYYYMMDDHH24MISS') − TO_DATE(intruction_time,'YYYYMMDDHH24MISS')) * 24 * 60`

### 2.3. Phân loại trạng thái mỗi dòng

| Trạng thái | Điều kiện |
|---|---|
| **Đúng hẹn** | `finish_time` không null, TG thực tế **≤** TG hẹn, và TG thực tế ≥ 0 |
| **Trễ hẹn** | `finish_time` không null, TG thực tế **>** TG hẹn |
| **Chưa trả KQ** | `finish_time IS NULL` |
| **Bất thường/loại trừ** | `finish_time` không null nhưng `finish_time < intruction_time` (TG thực tế âm) |

### 2.4. Quyết định đã chốt với user

1. **Mẫu số của % đúng hẹn** = chỉ các dòng **đã trả KQ hợp lệ** (Đúng hẹn + Trễ hẹn). Nhóm "Chưa trả KQ" và "Bất thường" hiển thị số lượng riêng để minh bạch, KHÔNG đưa vào mẫu số.
   - `% Đúng hẹn = Đúng hẹn / (Đúng hẹn + Trễ hẹn) * 100`
   - `% Trễ hẹn = Trễ hẹn / (Đúng hẹn + Trễ hẹn) * 100`
2. **Dữ liệu bẩn** (`finish < intruction`) → loại khỏi tính toán, gom vào nhóm "Bất thường", hiển thị số lượng.

---

## 3. Bộ lọc (filters)

| Filter | Field | Ghi chú |
|---|---|---|
| Khoảng ngày | lọc theo `his_service_req.intruction_time BETWEEN :from AND :to` | Input dạng `Y-m-d`, chuẩn hóa về `YmdHis` (startOfDay / endOfDay) như pattern khảo sát |
| Khoa/phòng thực hiện | `his_sere_serv.tdl_execute_room_id` (join `his_execute_room` lấy tên) | Optional; dropdown |
| Loại dịch vụ | `his_sere_serv.tdl_service_type_id` (Khám/XN/CĐHA/SA/TDCN…) | Optional; chỉ liệt kê các loại có dịch vụ với estimate_duration |

---

## 4. Kiến trúc (bám pattern sẵn có)

Theo đúng pattern của `ReportController` (report khảo sát) + `ReportDataService` (SQL builder) + `KhaoSatExport`.

| Thành phần | File | Vai trò |
|---|---|---|
| Controller | `app/Http/Controllers/KHTH/OnTimeResultController.php` | `index()` (view), `getSummary()` (JSON tổng hợp), `fetch()` (DataTables chi tiết), `export()` (Excel) |
| SQL builder | thêm methods vào `app/Services/ReportDataService.php` | `buildSummaryOnTimeResult($request)` và `buildDetailSqlAndBindingsOnTimeResult($request)` trả về `[$sql, $bindings]` |
| View | `resources/views/khth/on-time-result.blade.php` | Bộ lọc + card KPI + bảng/biểu đồ tổng hợp + DataTables chi tiết |
| Export | `app/Exports/OnTimeResultExport.php` | Xuất Excel bảng chi tiết theo filter (dùng `maatwebsite/excel` như `KhaoSatExport`) |
| Routes | `routes/web.php` (nhóm `khth`) | Xem mục 6 |
| Menu | `config/adminlte.php` (nhóm KHTH) | 1 mục mới trỏ route index |

Connection: tất cả query dùng `DB::connection('HISPro')`.

### 4.1. Nguyên tắc isolation

- **ReportDataService** chỉ chịu trách nhiệm dựng SQL + bindings (thuần dữ liệu, test được độc lập), KHÔNG format.
- **Controller** điều phối request → gọi service → format kết quả (phân loại trạng thái, tính %, DataTables transform).
- **View** chỉ hiển thị; gọi AJAX tới `getSummary` và `fetch`.

---

## 5. Phần TỔNG HỢP

### 5.1. Endpoint `getSummary(Request $request)` → JSON

Một CTE Oracle tính cho từng dòng sere_serv (có estimate_duration, trong filter) các cờ: `da_tra` (finish not null), `am` (finish < intruction), `dung_hen`, `tre_hen`. Sau đó controller/SQL tổng hợp ra:

- **KPI tổng:**
  - `tong_co_hen` (tổng dòng trong mẫu)
  - `da_tra_hop_le` = đúng + trễ
  - `dung_hen`, `tre_hen`
  - `pct_dung_hen`, `pct_tre_hen` (trên mẫu số `da_tra_hop_le`)
  - `chua_tra`, `bat_thuong`
  - `tg_tra_tb` = trung bình TG thực tế (phút) của các dòng đã trả hợp lệ
- **breakdown_loai_dich_vu:** group theo `tdl_service_type_id`/tên → SL, đúng, trễ, %.
- **breakdown_phong:** group theo phòng thực hiện → SL, đúng, trễ, % (sắp xếp % trễ giảm dần).
- **breakdown_dich_vu:** group theo `service_id`/mã/tên → top dịch vụ trễ hẹn nhiều nhất.
- **trend_theo_ngay:** group theo ngày (`SUBSTR(intruction_time,1,8)`) → % đúng hẹn từng ngày.

### 5.2. Hiển thị

- Hàng card KPI (tổng, % đúng, % trễ, chưa trả, TG TB).
- Bảng + biểu đồ cột "theo Loại dịch vụ".
- Bảng "theo Khoa/phòng thực hiện" (xếp hạng % trễ).
- Bảng "top dịch vụ trễ hẹn".
- Biểu đồ đường "xu hướng % đúng hẹn theo ngày".

Biểu đồ dùng **Chart.js** (`Chart.min.js`) — đúng thư viện đang dùng trong các view KHTH hiện có (`resources/views/khth/dashboard.blade.php`, `so-luot-kham-index.blade.php`, `chi-phi-kham-benh-index.blade.php`).

### 5.3. Interface giữa Service và Controller (làm rõ)

`getSummary` chạy **nhiều khối query** (KPI tổng, breakdown loại DV, breakdown phòng, breakdown dịch vụ, trend ngày). Service cung cấp các method builder riêng cho từng khối (mỗi method trả `[$sql, $bindings]`) dùng chung điều kiện filter; Controller chạy lần lượt, phân loại trạng thái + tính % + `ROUND` rồi gộp thành 1 mảng JSON. `tg_tra_tb` làm tròn 0 chữ số thập phân, đơn vị "phút".

Tất cả query tổng hợp & chi tiết đều join: `his_sere_serv` → `his_service_req` (mốc thời gian, is_active/is_delete) → `his_service` (estimate_duration, service_name) → `his_service_type` (service_type_name) → `his_execute_room` (execute_room_name). Cờ trạng thái tính trên biểu thức phút như mục 2.

---

## 6. Phần CHI TIẾT

### 6.1. Endpoint `fetch(Request $request)` → DataTables (server-side)

`buildDetailSqlAndBindingsOnTimeResult` trả SQL liệt kê từng dòng sere_serv. Join: `his_sere_serv` → `his_service_req` → `his_service` → `his_service_type` → `his_execute_room`. Cột:

| Cột | Nguồn |
|---|---|
| Mã/Tên BN | `his_sere_serv.tdl_treatment_code`, `his_sere_serv.tdl_patient_name` (các cột `tdl_*` nằm trên `his_sere_serv`) |
| Khoa/phòng thực hiện | `his_execute_room.execute_room_name` (join `his_execute_room.room_id = his_sere_serv.tdl_execute_room_id`) |
| Loại dịch vụ | `his_service_type.service_type_name` (join `his_service_type.id = his_sere_serv.tdl_service_type_id`) |
| Tên dịch vụ | `his_sere_serv.tdl_service_name` (hoặc `his_service.service_name`) |
| Giờ chỉ định | `intruction_time` (format) |
| Giờ trả KQ | `finish_time` (format) |
| TG thực tế (phút) | tính trong controller hoặc SQL |
| TG hẹn (phút) | `estimate_duration` |
| Chênh lệch (phút) | TG thực tế − TG hẹn |
| Trạng thái | Đúng / Trễ / Chưa trả / Bất thường (badge màu) |

Controller dùng `DataTables::of($results)` + `editColumn` format thời gian (`strtodatetime`) + `addColumn` trạng thái — y pattern `fetchKhaoSat`.

### 6.2. Drill-down từ tổng hợp

Các tham số filter mở rộng để bảng chi tiết nhận thêm:
- `service_type_id` (khi click 1 loại dịch vụ)
- `execute_room_id` (khi click 1 phòng)
- `service_id` (khi click 1 dịch vụ)
- `status` (`dung_hen` / `tre_hen` / `chua_tra` / `bat_thuong`) khi click 1 nhóm trạng thái

`buildDetailSqlAndBindingsOnTimeResult` phải nhận các tham số này và sinh predicate tương ứng. Riêng `status` là điều kiện trên **biểu thức tính phút** (không phải cột thẳng), ví dụ:
- `chua_tra` → `finish_time IS NULL`
- `bat_thuong` → `finish_time IS NOT NULL AND TO_DATE(finish_time,...) < TO_DATE(intruction_time,...)`
- `dung_hen` / `tre_hen` → so sánh `(finish−intruction)*24*60` với `estimate_duration`

Vì `export()` dùng chung builder này nên Export tự động lọc đúng theo cả drill-down. Click ở bảng tổng hợp → JS set các filter này → reload DataTables.

### 6.3. Export Excel

`export(Request $request)` trả `Excel::download(new OnTimeResultExport($filters), $fileName)` — cùng tập cột và cùng filter (gồm cả drill-down) như bảng chi tiết.

---

## 7. Routes

Đặt **bên trong** group KHTH đã có sẵn ở `routes/web.php` (dòng ~539):
`Route::group(['prefix' => 'khth/', 'middleware' => ['checkrole:administrator']], function () { ... })`.

Vì group đã có `prefix => 'khth/'`, các path bên dưới **KHÔNG** ghi lại tiền tố `khth/` (URL cuối là `/khth/on-time-result-index`):

```php
Route::get('on-time-result-index', 'KHTH\OnTimeResultController@index')->name('khth.on-time-result-index');
Route::get('on-time-result-index/summary', 'KHTH\OnTimeResultController@getSummary')->name('khth.on-time-result-summary');
Route::get('on-time-result-index/fetch', 'KHTH\OnTimeResultController@fetch')->name('khth.on-time-result-fetch');
Route::get('on-time-result-index/export', 'KHTH\OnTimeResultController@export')->name('khth.on-time-result-export');
Route::get('on-time-result-index/rooms', 'KHTH\OnTimeResultController@rooms')->name('khth.on-time-result-rooms'); // dropdown phòng (nếu cần)
```

Phân quyền: kế thừa `checkrole:administrator` từ group (giống mọi report KHTH hiện có).

---

## 8. Menu

Thêm 1 mục trong submenu nhóm KHTH ở `config/adminlte.php`, đúng định dạng AdminLTE 2 (FontAwesome 4, không tiền tố `fas fa-fw fa-`) và có `checkrole` như các mục cùng nhóm:

```php
[
    'text'      => 'Tỷ lệ trả KQ đúng hẹn',
    'icon'      => 'clock-o',
    'checkrole' => 'administrator',
    'route'     => 'khth.on-time-result-index',
    'active'    => ['khth/on-time-result-index*'],
],
```

---

## 9. Edge cases & lưu ý

- **finish_time NULL** → "Chưa trả KQ", không vào mẫu số.
- **finish_time < intruction_time** → "Bất thường", loại khỏi tính %, hiển thị số lượng (thực tế khảo sát 01–07/06/2026 chỉ 1 dòng).
- **estimate_duration NULL/0** → không vào mẫu (đã lọc từ đầu).
- **start_time** không dùng cho chỉ số đúng hẹn (chỉ dùng intruction & finish theo yêu cầu user).
- Hiệu năng: filter bắt buộc theo `his_service_req.intruction_time`. Cần xác nhận có index trên cột này (giả định theo pattern các report khác; nếu chưa có, đánh giá thêm khi triển khai). Tổng hợp gom theo từng khối GROUP BY trên cùng tập filter.
- Số liệu kiểm chứng (tham chiếu): tuần 01–07/06/2026 có 25.375 dòng có hẹn, 117 chưa trả, đúng hẹn 8.206 (~32%), trễ hẹn 17.052 (~68%), 1 dòng bất thường.

---

## 10. Out of scope (YAGNI)

- Không chia theo 83 tiêu chí riêng lẻ.
- Không có ngưỡng hẹn riêng ngoài `estimate_duration`.
- Không tính cho dịch vụ không có `estimate_duration`.
- Không auto-refresh kiểu màn hình lớn (đây là trang report có filter, không phải TV dashboard).
