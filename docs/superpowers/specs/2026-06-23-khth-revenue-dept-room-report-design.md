# Spec: Report "Doanh thu theo khoa/phòng thực hiện" (menu KHTH)

**Date:** 2026-06-23
**Status:** Approved (chờ user review spec)

> **Phạm vi:** Đây là **phần A** — report độc lập mới. Việc **B** (rà soát đổi `price → vir_price` cho tất cả báo cáo cũ) là spec/plan riêng, làm sau.

---

## 1. Mục tiêu

Bổ sung một **trang report độc lập** trong menu **Kế hoạch tổng hợp (KHTH)**: thống kê **doanh thu theo khoa thực hiện** kèm **chi tiết doanh thu các phòng thuộc khoa**, cho phép lọc theo **giai đoạn (khoảng ngày)**, **khoa**, **phòng**. Có phần tổng hợp (biểu đồ + bảng theo khoa) và bảng chi tiết theo phòng + xuất Excel.

---

## 2. Phạm vi dữ liệu & chỉ số

### 2.1. Nguồn dữ liệu

- Join: `his_sere_serv ss` → `his_service_req sr` ON `sr.id = ss.service_req_id` (lọc thời gian) → `his_department d` ON `d.id = ss.tdl_execute_department_id` (khoa thực hiện) → LEFT JOIN `his_execute_room er` ON `er.room_id = ss.tdl_execute_room_id` (phòng thực hiện).
- Quy mô: 26 khoa, 243 phòng (tham chiếu 1 tuần).

### 2.2. Chỉ số & điều kiện

- **Doanh thu (thanh_tien)** = `SUM(ss.amount * ss.vir_price)` — **dùng `vir_price`** (đơn giá ảo/thực sau điều chỉnh BHYT/giảm trừ), KHÔNG dùng `price`. (`ss.amount * ss.vir_price = ss.vir_total_price`; có thể dùng `SUM(ss.vir_total_price)` tương đương.)
- **Số lượng (so_luong)** = `SUM(ss.amount)`.
- **Điều kiện WHERE:**
  - `sr.intruction_time BETWEEN :from AND :to` (khoảng ngày lọc, định dạng `YYYYMMDDHH24MISS`)
  - `ss.tdl_intruction_date BETWEEN :from_day AND :to_day` (cột dẫn đầu index `HIS_SERE_SERV_INDEX16`, dạng `YYYYMMDD000000`) — để Oracle quét theo ngày bằng index khi chọn giai đoạn dài (giống tối ưu đã áp cho biểu đồ máy/doanh thu khoa).
  - `sr.is_active = 1`, `sr.is_delete = 0`, `ss.is_delete = 0`
  - Optional: `ss.tdl_execute_department_id = :department_id` (khi chọn khoa)
  - Optional: `ss.tdl_execute_room_id = :room_id` (khi chọn phòng)
- **Thứ tự:** giữ **tự nhiên** (không sắp xếp). Bảng DataTables người dùng vẫn click sắp xếp được.

---

## 3. Trang report (standalone trong KHTH)

### 3.1. Bộ lọc

| Filter | Field | Ghi chú |
|---|---|---|
| Giai đoạn | `sr.intruction_time` (+ `tdl_intruction_date`) | date range; chuẩn hóa `Y-m-d`/`Y-m-d H:i:s` → `YmdHis` (pattern report KHTH) |
| Khoa | `ss.tdl_execute_department_id` | dropdown "Tất cả"; nạp danh sách khoa có doanh thu trong kỳ |
| Phòng | `ss.tdl_execute_room_id` | dropdown "Tất cả"; nạp danh sách phòng (theo khoa đang chọn nếu có) |

### 3.2. Bố cục

- **Card KPI** (theo bộ lọc): Tổng doanh thu (Tr) · Số khoa · Số phòng.
- **Tổng hợp theo khoa:** biểu đồ **cột doanh thu theo khoa** (mỗi khoa một màu, đơn vị triệu Tr) + **bảng** (Khoa · Doanh thu · Số lượng · % trên tổng). Click 1 khoa (ở bảng hoặc cột) → đặt filter Khoa → tải lại phần chi tiết theo khoa đó.
- **Chi tiết theo phòng:** **DataTables server-side** (Khoa · Phòng · Doanh thu · Số lượng), lọc theo Giai đoạn + Khoa + Phòng. Doanh thu hiển thị **số đầy đủ (VND)**. Nút **Xuất Excel** theo bộ lọc hiện hành.

---

## 4. Kiến trúc (bám pattern report on-time-result)

| Thành phần | File | Vai trò |
|---|---|---|
| Controller | `app/Http/Controllers/KHTH/RevenueDeptRoomController.php` | `index` (view), `getSummary` (JSON: doanh thu theo khoa + KPI tổng), `fetch` (DataTables chi tiết theo phòng), `export` (Excel), `departments` (dropdown khoa), `rooms` (dropdown phòng theo khoa) |
| Service | `app/Services/RevenueDeptRoomService.php` | SQL builders (`buildDeptSummarySql`, `buildRoomDetailSqlAndBindings`, `buildDepartmentsSql`, `buildRoomsSql`) trả `[$sql,$bindings]` + helper chuẩn hóa filter (`commonConditions`) + tổng hợp/format thuần (`summarizeDept`) — phần thuần unit-test được |
| View | `resources/views/khth/revenue-dept-room.blade.php` + partial `khth/partials/search-revenue-dept-room.blade.php` | bộ lọc + KPI + Chart.js (cột theo khoa) + bảng khoa + DataTables chi tiết phòng |
| Export | `app/Exports/RevenueDeptRoomExport.php` | xuất bảng chi tiết theo phòng (dùng lại `buildRoomDetailSqlAndBindings`), maatwebsite/excel |
| Routes | `routes/web.php` (nhóm `khth/`, `checkrole:administrator`) | xem mục 5 |
| Menu | `config/adminlte.php` (submenu KHTH) | 1 mục mới |

- Connection: `DB::connection('HISPro')`. Named bindings (key không có `:` prefix theo pattern ReportDataService). Bind ngày `:from_time`/`:to_time` (KHÔNG dùng `:from`/`:to` vì `from` là từ khóa Oracle → ORA-01745).
- Oracle trả tên cột VIẾT HOA → `normalizeRows()` (array_change_key_case CASE_LOWER) cho mọi kết quả `DB::select` trước khi dùng (đọc `$r->department_name`, `$r->room_name`, `$r->thanh_tien`, `$r->so_luong` lowercase). DataTables column `data:'...'` cũng lowercase.
- Đơn vị tiền: bảng chi tiết & bảng khoa hiển thị **VND đầy đủ**; biểu đồ + KPI hiển thị **triệu (Tr)**.

### 4.1. Interface getSummary

`getSummary` trả JSON:
```json
{
  "kpi": { "tong_doanh_thu": 9587241012, "so_khoa": 26, "so_phong": 120 },
  "by_department": [
    { "department_id": 10, "department_name": "Khoa Dược CS1", "thanh_tien": 2400000000, "so_luong": 10000, "pct": 25.0 },
    ...
  ]
}
```
- `by_department` giữ thứ tự tự nhiên từ query; `pct` = thanh_tien/tổng*100 (1 chữ số thập phân).

### 4.2. Interface fetch (DataTables chi tiết theo phòng)

`buildRoomDetailSqlAndBindings` trả SQL mỗi dòng = 1 phòng: `department_name, room_name (er.execute_room_name; NULL → "(không xác định)"), thanh_tien, so_luong`. Group theo `tdl_execute_department_id, tdl_execute_room_id`. Controller `fetch` → `Datatables::of($rows)` format số tiền (number_format) — theo pattern `OnTimeResultController@fetch`.

---

## 5. Routes

Đặt **bên trong** group `Route::group(['prefix' => 'khth/', 'middleware' => ['checkrole:administrator']], ...)` (cạnh `on-time-result-index`):

```php
Route::get('revenue-dept-room-index', 'KHTH\RevenueDeptRoomController@index')->name('khth.revenue-dept-room-index');
Route::get('revenue-dept-room-index/summary', 'KHTH\RevenueDeptRoomController@getSummary')->name('khth.revenue-dept-room-summary');
Route::get('revenue-dept-room-index/fetch', 'KHTH\RevenueDeptRoomController@fetch')->name('khth.revenue-dept-room-fetch');
Route::get('revenue-dept-room-index/export', 'KHTH\RevenueDeptRoomController@export')->name('khth.revenue-dept-room-export');
Route::get('revenue-dept-room-index/departments', 'KHTH\RevenueDeptRoomController@departments')->name('khth.revenue-dept-room-departments');
Route::get('revenue-dept-room-index/rooms', 'KHTH\RevenueDeptRoomController@rooms')->name('khth.revenue-dept-room-rooms');
```

---

## 6. Menu

Thêm 1 mục trong submenu KHTH ở `config/adminlte.php` (định dạng AdminLTE 2: `'icon'` không tiền tố `fas fa-`, có `'checkrole'`), cạnh mục "Tỷ lệ trả KQ đúng hẹn":

```php
[
    'text'      => 'Doanh thu theo khoa/phòng',
    'icon'      => 'money',
    'checkrole' => 'administrator',
    'route'     => 'khth.revenue-dept-room-index',
    'active'    => ['khth/revenue-dept-room-index*'],
],
```

---

## 7. Edge cases & lưu ý

- **Không có dữ liệu trong kỳ** → KPI = 0, biểu đồ/bảng rỗng (hiển thị "Không có dữ liệu"), DataTables rỗng; không lỗi.
- **Phòng NULL** (`tdl_execute_room_id` không map được) → tên phòng "(không xác định)", vẫn gộp đúng theo id.
- **Chọn phòng nhưng không chọn khoa** → vẫn lọc theo phòng (room_id là duy nhất). Dropdown phòng nên nạp theo khoa đang chọn để tránh nhầm.
- **Hiệu năng:** `his_sere_serv` lớn (~35tr dòng); bắt buộc filter theo `tdl_intruction_date` (index) + `intruction_time`. Tổng hợp theo khoa ≤ 26 dòng, theo phòng ≤ ~243 dòng → nhẹ.
- **vir_price NULL:** vài dòng có thể NULL → `amount * vir_price` = NULL → SUM bỏ qua (Oracle SUM bỏ NULL). Chấp nhận (đúng bản chất: dòng chưa có giá ảo không tính doanh thu). Nếu cần, dùng `NVL(ss.vir_price,0)` — **chốt: dùng `ss.vir_price` trực tiếp** (SUM tự bỏ NULL), nhất quán cách các báo cáo vir_ khác.

---

## 8. Out of scope (YAGNI)

- Không chi tiết tới từng dịch vụ (chỉ tổng hợp theo phòng).
- Không chia theo đối tượng (patient type) / loại dịch vụ.
- Không so sánh kỳ trước.
- Không sắp xếp mặc định (giữ thứ tự tự nhiên; DataTables vẫn cho sort thủ công).
- Phần B (đổi `price→vir_price` báo cáo cũ) KHÔNG thuộc spec này.
