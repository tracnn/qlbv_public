# Spec: Biểu đồ "Tình trạng giường theo khoa" (Home dashboard)

**Date:** 2026-06-10
**Status:** Approved (chờ user review spec)

---

## 1. Mục tiêu

Bổ sung vào **Home dashboard** một biểu đồ **cột nhóm** theo dõi **tình trạng giường tại từng khoa**: số giường **đã sử dụng** và **còn trống**, kèm **công suất sử dụng (%)**. Đây là trạng thái **hiện tại (real-time snapshot)**, không phụ thuộc khoảng ngày lọc của dashboard.

---

## 2. Phạm vi dữ liệu & chỉ số (snapshot hiện tại)

### 2.1. Nguồn dữ liệu (đã khảo sát thực tế)

- Giường: `his_bed` → `his_bed_room` (qua `bed_room_id`) → `his_room` (qua `room_id`) → `his_department` (qua `room.department_id`).
- Phân giường BN: `his_treatment_bed_room` (gắn `treatment_id`, `bed_room_id`). **Lưu ý:** cột `bed_id` chỉ điền ~24% lượt → KHÔNG đếm theo `bed_id`; đếm theo **lượt BN đang nằm** (mỗi lượt = 1 giường sử dụng).
- Quy mô thực tế: 18 khoa có giường; toàn viện 831 giường, ~495 đang dùng (~60%), 336 trống; 0 khoa quá tải (đã dùng ≤ tổng ở mọi khoa).

### 2.2. Chỉ số & điều kiện

- **Tổng giường / khoa (tong_giuong):** đếm `his_bed` với
  `his_bed.is_active=1 AND his_bed.is_delete=0 AND his_bed_room.is_active=1 AND his_bed_room.is_delete=0 AND his_room.is_active=1`,
  GROUP BY `his_room.department_id`.
- **Đã sử dụng / khoa (dang_dung):** đếm `his_treatment_bed_room` với
  `tbr.remove_time IS NULL AND tbr.is_delete=0 AND his_co_treatment.id IS NULL` (LEFT JOIN co_treatment để loại đồng điều trị), join `his_treatment` với `t.tdl_treatment_type_id IN (3,4) AND t.out_time IS NULL` (BN nội trú chưa ra viện), GROUP BY `his_room.department_id` (qua tbr.bed_room_id → his_bed_room → his_room).
- **Còn trống / khoa (con_trong)** = `tong_giuong − dang_dung` (≥ 0 theo dữ liệu thực; nếu quá tải có thể âm — xem mục 7).
- **Công suất / khoa (cong_suat)** = `round(dang_dung / tong_giuong * 100)` (0 nếu tong_giuong = 0).
- **Không lọc theo khoảng ngày** — query phản ánh trạng thái hiện tại.

### 2.3. Query (1 câu, CTE)

Lấy mỗi khoa 1 dòng `department_name, tong_giuong, dang_dung`. Khung:

```sql
WITH tong AS (
  SELECT r.department_id, COUNT(*) tong_giuong
  FROM his_bed b
  JOIN his_bed_room br ON br.id = b.bed_room_id
  JOIN his_room r ON r.id = br.room_id
  WHERE b.is_active=1 AND b.is_delete=0 AND br.is_active=1 AND br.is_delete=0 AND r.is_active=1
  GROUP BY r.department_id
),
dang AS (
  SELECT r.department_id, COUNT(*) dang_dung
  FROM his_treatment_bed_room tbr
  JOIN his_bed_room br ON br.id = tbr.bed_room_id
  JOIN his_room r ON r.id = br.room_id
  JOIN his_treatment t ON t.id = tbr.treatment_id
  LEFT JOIN his_co_treatment ct ON ct.id = tbr.co_treatment_id
  WHERE tbr.remove_time IS NULL AND tbr.is_delete=0 AND ct.id IS NULL
    AND t.tdl_treatment_type_id IN (3,4) AND t.out_time IS NULL
  GROUP BY r.department_id
)
SELECT d.department_name,
       tong.tong_giuong AS tong_giuong,
       NVL(dang.dang_dung, 0) AS dang_dung
FROM tong
JOIN his_department d ON d.id = tong.department_id
LEFT JOIN dang ON dang.department_id = tong.department_id
```

> Driving từ `tong` (chỉ khoa CÓ giường), LEFT JOIN `dang` (khoa chưa có BN nằm → dang_dung=0). Giữ **thứ tự tự nhiên** (không ORDER BY).

---

## 3. Endpoint (theo pattern Home)

- **Controller:** `HomeController@fetchBedStatusByDepartment(Request $request)` — AJAX-only (nếu không `$request->ajax()` thì `redirect()->route('home')`). **Bỏ qua** `startDate`/`endDate` (snapshot hiện tại).
- **Connection:** `DB::connection('HISPro')`. Query mục 2.3 đặt trong private `bedStatusByDepartment()` (dùng `selectRaw` cho CTE qua `DB::connection('HISPro')->select(DB::raw($sql))`, KHÔNG bind tham số vì không lọc ngày).
- **Logic tổng hợp thuần** tách thành `public static function buildBedStatusByDepartmentSeries($rows)` (unit-test được), trả:

```json
{
  "categories": ["Khoa Nhi CS1", "Khoa Nội TH CS1", "..."],
  "used": [72, 90, "..."],
  "free": [96, 10, "..."],
  "utilization": [43, 90, "..."],
  "total": { "tong": 831, "dang_dung": 495, "con_trong": 336, "cong_suat": 60 }
}
```
- `categories[i]` = tên khoa (giữ thứ tự tự nhiên từ rows). `used[i]`/`free[i]` = đã dùng / còn trống. `utilization[i]` = công suất %/khoa. `total` = tổng toàn viện (tong, dang_dung, con_trong, cong_suat%).

> Oracle column casing: oci8 ở app này trả key cột lowercase → đọc `$r->department_name`, `$r->tong_giuong`, `$r->dang_dung` lowercase. Vì query dùng `DB::select(DB::raw(...))`, vẫn áp dụng — đọc lowercase (đồng nhất các method Home khác).

---

## 4. Route & cấu hình

- Thêm route **bên trong** group `Route::group(['middleware' => ['checkrole:dashboard']], ...)` (nơi chứa các route fetch chart Home):
  ```php
  Route::get('fetch-bed-status-by-department', 'HomeController@fetchBedStatusByDepartment')->name('fetch-bed-status-by-department');
  ```
- Thêm vào map `window.DASHBOARD_CFG.routes` trong `resources/views/home.blade.php`:
  ```js
  fetchBedStatusByDepartment: "{{ route('fetch-bed-status-by-department') }}",
  ```

---

## 5. Giao diện (Highcharts column nhóm)

- **Container:** thêm 1 box full-width (`col-lg-12`) trong `home.blade.php`, đặt trong khu vực nội trú (cạnh các chart liên quan giường/nội trú; nếu không có vị trí rõ ràng thì thêm 1 hàng mới):
  ```blade
  <div class="row">
      <div class="col-lg-12 connectedSortable">
          <div class="nav-tabs-custom text-center">
              <div class="tab-content no-padding" style="padding:10px;">
                  <div id="chart_bed_status_by_department" style="width:100%; height:420px;"></div>
              </div>
          </div>
      </div>
  </div>
  ```
- **Loại biểu đồ:** Highcharts `column` (KHÔNG stacked), **2 series cạnh nhau**:
  - `Đã sử dụng` — data = `used`, màu đỏ/cam (vd `#dd4b39`).
  - `Còn trống` — data = `free`, màu xanh (vd `#00a65a`).
- **Trục X:** `categories` = tên khoa, nhãn xoay -45°. **Trục Y:** số giường (min 0).
- **Data labels:** bật, hiện số giường trên mỗi cột.
- **Tooltip (shared theo khoa):** hiện tên khoa + Đã dùng, Còn trống, Tổng, **Công suất %** (lấy từ `utilization[index]`).
- **Tiêu đề:** "Tình trạng giường theo khoa" + tổng toàn viện (vd "831 giường · 495 đã dùng · 60%").
- **Legend:** bật (2 series: Đã sử dụng / Còn trống).
- **Phân quyền:** KHÔNG gated tài chính (không phải số liệu doanh thu). Quyền truy cập đã do route group `checkrole:dashboard` đảm bảo; JS hiển thị mặc định. Rỗng → div "Không có dữ liệu".

---

## 6. Tích hợp JS module hóa

- **`public/js/dashboard/api.js`:** thêm vào object `API` (export `win.DAPI`):
  ```js
  bedStatusByDepartment: function (start, end) { return get(R.fetchBedStatusByDepartment, { startDate: start, endDate: end }); }
  ```
  (Vẫn truyền start/end cho đồng nhất signature; controller bỏ qua.)
- **`public/js/dashboard/charts.js`:** thêm `renderBedStatusByDepartment(start, end)`:
  - Gọi `API.bedStatusByDepartment(start,end)`; rỗng → div "Không có dữ liệu"; ngược lại vẽ Highcharts `column` 2 series như mục 5.
  - **Đăng ký vào `renderAll`:** thêm `renderBedStatusByDepartment(start, end)` vào mảng `$.when.apply($, [ ... ])` trong `DCH.renderAll`.
  - Auto-refresh & đổi ngày gọi `renderAll` → tự cập nhật snapshot (dù không dùng ngày).
- **`init.js` / `autorefresh.js`:** KHÔNG cần sửa.

---

## 7. Edge cases & lưu ý

- **Khoa quá tải** (dang_dung > tong_giuong): con_trong âm. Dữ liệu thực hiện tại 0 khoa quá tải, nhưng để an toàn: static method giữ giá trị thực (cho phép âm) HOẶC kẹp `free = max(0, tong − dang)`. **Chốt:** kẹp `con_trong = max(0, tong − dang)` để cột không âm; công suất vẫn `round(dang/tong*100)` (có thể > 100% nếu quá tải, phản ánh đúng).
- **Khoa có giường nhưng chưa có BN nằm** → dang_dung = 0 (LEFT JOIN), vẫn hiển thị (cột "Còn trống" = tổng).
- **Không có dữ liệu** (không khoa nào có giường — lý thuyết) → categories rỗng → div "Không có dữ liệu".
- **tong_giuong = 0** không xảy ra do driving từ `tong` (chỉ khoa có giường); guard chia 0 cho công suất vẫn để phòng.
- **Hiệu năng:** dữ liệu nhỏ (18 khoa, 831 giường, ~495 lượt nằm) → nhẹ.

---

## 8. Out of scope (YAGNI)

- Không chia theo phòng / loại giường.
- Không drill-down danh sách BN.
- Không lịch sử tình trạng giường theo ngày (chỉ snapshot hiện tại).
- Không export.
- Không sắp xếp (giữ thứ tự tự nhiên).
