# Spec: Biểu đồ "Doanh thu theo khoa thực hiện" (Home dashboard)

**Date:** 2026-06-10
**Status:** Approved (chờ user review spec)

---

## 1. Mục tiêu

Bổ sung vào **Home dashboard** một biểu đồ **cột** thống kê **doanh thu theo khoa thực hiện**, song song với biểu đồ "doanh thu theo đối tượng" (loại dịch vụ × đối tượng) đã có. Biểu đồ tuân theo khoảng ngày lọc chung của dashboard, mỗi khoa một màu, giữ thứ tự tự nhiên (không sắp xếp).

---

## 2. Phạm vi dữ liệu & chỉ số

### 2.1. Nguồn dữ liệu (đã khảo sát thực tế)

- Khoa thực hiện của từng dòng dịch vụ: cột denormalized `his_sere_serv.tdl_execute_department_id` → join `his_department` lấy `department_name`.
- Join: `his_sere_serv ss` → `his_service_req sr` ON `sr.id = ss.service_req_id` (để lọc theo thời gian) → `his_department d` ON `d.id = ss.tdl_execute_department_id`.
- Quy mô tuần khảo sát: 26 khoa thực hiện. Doanh thu cao nhất: Khoa Dược ~2,58 tỷ, CĐHA ~1,15 tỷ, Xét nghiệm ~881tr...

### 2.2. Chỉ số & điều kiện

- **Chỉ số:** `SUM(his_sere_serv.amount * his_sere_serv.price)` AS thanh_tien (doanh thu).
- **Điều kiện WHERE** (giống hệt method `doanhthu()` hiện có để số liệu khớp pattern doanh thu):
  - `his_service_req.intruction_time BETWEEN :from AND :to` (lọc theo **khoảng ngày dashboard**, định dạng `YYYYMMDDHH24MISS`)
  - `his_service_req.is_active = 1`
  - `his_service_req.is_delete = 0`
  - `his_sere_serv.is_delete = 0`
- **Nhóm:** `GROUP BY his_sere_serv.tdl_execute_department_id, his_department.department_name`.
- **Thứ tự:** **giữ tự nhiên** từ query (KHÔNG sắp xếp).

---

## 3. Endpoint (theo pattern Home)

- **Controller:** `HomeController@fetchDoanhthuByDepartment(Request $request)` — AJAX-only (nếu không `$request->ajax()` thì `redirect()->route('home')`, theo pattern các method chart khác).
- **Tham số:** `startDate`, `endDate`. Chuẩn hóa về `YYYYMMDDHH24MISS` bằng helper sẵn có `HomeController::currentDate(...)`.
- **Connection:** `DB::connection('HISPro')`.
- **Logic tổng hợp thuần** tách thành `public static function buildDoanhthuByDepartmentSeries($rows)` (unit-test được, không cần DB), trả:

```json
{
  "categories": ["Khoa Dược CS1", "Khoa CĐHA CS1", "..."],
  "data": [2580191563, 1146216800, "..."],
  "total": 9876543210
}
```
- `categories[i]` = tên khoa, `data[i]` = doanh thu (số nguyên VND), giữ **thứ tự tự nhiên** từ rows (không sort). `total` = tổng doanh thu (để hiển thị tiêu đề/subtitle).
- Private method `doanhthuByDepartment($from_date, $to_date)` dựng query mục 2.2 và `->get()`.

> Oracle column casing: các method Home đọc lowercase `$row->thanh_tien` chạy đúng (oci8 trả key lowercase) → đọc `$r->department_name`, `$r->thanh_tien` lowercase, không cần chuẩn hóa thêm.

---

## 4. Route & cấu hình

- Thêm route **bên trong** group `Route::group(['middleware' => ['checkrole:dashboard']], ...)` (nơi chứa các route fetch chart Home, cạnh `fetch-doanh-thu`):
  ```php
  Route::get('fetch-doanhthu-by-department', 'HomeController@fetchDoanhthuByDepartment')->name('fetch-doanhthu-by-department');
  ```
- Thêm vào map `window.DASHBOARD_CFG.routes` trong `resources/views/home.blade.php`:
  ```js
  fetchDoanhthuByDepartment: "{{ route('fetch-doanhthu-by-department') }}",
  ```

---

## 5. Giao diện (Highcharts column)

- **Container:** thêm 1 box full-width (`col-lg-12`) trong khu vực doanh thu của `home.blade.php`, đặt ngay dưới hàng chứa `chart_doanhthu` / `chart_treatment`:
  ```blade
  <div class="row">
      <div class="col-lg-12 connectedSortable">
          <div class="nav-tabs-custom text-center">
              <div class="tab-content no-padding" style="padding:10px;">
                  <div id="chart_doanhthu_by_department" style="width:100%; height:420px;"></div>
              </div>
          </div>
      </div>
  </div>
  ```
- **Loại biểu đồ:** Highcharts `column`, mỗi cột = 1 khoa.
- **Màu:** **mỗi khoa một màu** — dùng palette mặc định Highcharts, tô màu theo từng điểm (per-point), giống cách biểu đồ "số lượng dịch vụ theo máy" đang làm.
- **Trục X:** `categories` = tên khoa, nhãn xoay -45° (tên dài).
- **Trục Y:** doanh thu; nhãn trục & data label hiển thị theo **triệu (Tr)** cho dễ đọc (vd 2.580 Tr), dùng `numeral`/`Highcharts.numberFormat`. **Tooltip** hiện tên khoa + doanh thu đầy đủ (VND) kèm quy đổi Tr.
- **Tiêu đề:** "Doanh thu theo khoa thực hiện" + tổng (Tr).
- **Data labels:** bật, hiển thị giá trị theo Tr (nhất quán với biểu đồ máy đã bật label).
- **Phân quyền:** gated theo `CFG.hasFinanceRole` (giống `renderDoanhThu`): không có quyền → hiển thị thông báo "Không có quyền" (dùng `U.showNoPermissionPie` hoặc chèn div tương đương); khoảng ngày rỗng → "Không có dữ liệu" (chèn div, theo pattern `renderDoanhThu`).

---

## 6. Tích hợp JS module hóa (đúng điểm tích hợp thực tế)

Cấu trúc thực tế: `api.js` export `win.DAPI`; `charts.js` export `win.DCharts` với hàm điều phối `DCharts.renderAll(start, end)` gọi mọi chart qua `$.when([...])`; `autorefresh.js` gọi `renderAll`. KHÔNG render chart riêng trong init.js.

- **`public/js/dashboard/api.js`:** thêm vào object `API`:
  ```js
  doanhThuByDepartment: function (start, end) { return get(R.fetchDoanhthuByDepartment, { startDate: start, endDate: end }); }
  ```
- **`public/js/dashboard/charts.js`:**
  - Hàm `renderDoanhThuByDepartment(start, end)`:
    - Nếu `!CFG.hasFinanceRole` → hiển thị "Không có quyền" trong `#chart_doanhthu_by_department`, trả `$.Deferred().resolve().promise()`.
    - Ngược lại gọi `API.doanhThuByDepartment(start,end)`, vẽ Highcharts `column` với mỗi điểm một màu (palette), data label & tooltip theo Tr; rỗng → div "Không có dữ liệu". Trả promise.
  - **Đăng ký vào `renderAll`:** thêm `renderDoanhThuByDepartment(start, end)` vào mảng `$.when.apply($, [ ... ])` (cạnh `renderDoanhThu(start, end)`).
- **`init.js` / `autorefresh.js`:** KHÔNG cần sửa (không có tương tác toggle/legend).

---

## 7. Edge cases & lưu ý

- **Không có quyền tài chính** → hiển thị "Không có quyền", không gọi/không vẽ.
- **Không có dữ liệu trong khoảng ngày** → chèn div "Không có dữ liệu" (pattern như `renderDoanhThu`), không lỗi.
- **department_name NULL** (lý thuyết): INNER JOIN `his_department` đã loại dòng không map được khoa; nếu cần an toàn, static method có thể coi tên rỗng là "(không xác định)" — KHÔNG bắt buộc (YAGNI), join đủ.
- **Hiệu năng:** lọc theo `his_service_req.intruction_time` (giống method `doanhthu()` hiện có). Dữ liệu nhóm ≤ ~26 khoa nên xử lý PHP nhẹ.
- **Đơn vị hiển thị:** quy đổi VND → triệu chỉ ở tầng hiển thị (JS); dữ liệu JSON giữ số VND nguyên.

---

## 8. Out of scope (YAGNI)

- Không chia theo đối tượng (patient type) / loại dịch vụ.
- Không legend bấm ẩn/hiện.
- Không nút chuyển khoa/phòng (chỉ theo khoa).
- Không drill-down, không export riêng.
- Không sắp xếp (giữ thứ tự tự nhiên).
