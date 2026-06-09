# Spec: Biểu đồ "Số lượng dịch vụ theo máy thực hiện" (Home dashboard)

**Date:** 2026-06-10
**Status:** Approved (chờ user review spec)

---

## 1. Mục tiêu

Bổ sung vào **Home dashboard** một biểu đồ cột thống kê **số lượng dịch vụ theo máy thực hiện**, có nút chuyển giữa hai chiều xem: **theo nhóm máy** và **theo từng máy**. Biểu đồ tuân theo khoảng ngày lọc chung của dashboard.

---

## 2. Phạm vi dữ liệu & chỉ số

### 2.1. Nguồn dữ liệu (đã khảo sát thực tế)

- Máy thực hiện của từng dòng dịch vụ nằm ở bảng mở rộng `his_sere_serv_ext` (KHÔNG ở `his_service_req.machine_id` — cột này không được dùng, 0 bản ghi trong dữ liệu thực).
- Join: `his_sere_serv ss` → `his_sere_serv_ext ext` ON `ext.sere_serv_id = ss.id` → `his_machine m` ON `m.id = ext.machine_id`.
- `his_machine`: `id`, `machine_name`, `machine_group_code` (mã nhóm máy: TNT/CL/SA/XQ/MĐX...), `machine_code`.

### 2.2. Độ phủ (lưu ý quan trọng)

- Chỉ ~0.8% dòng dịch vụ có gắn máy (tuần khảo sát: 1.078/133.854 dòng), thuộc các modal có ghi máy: CT (CL), siêu âm (SA), chạy thận nhân tạo (TNT, ~49 máy), X-quang (XQ), đo mật độ xương (MĐX)... Biểu đồ chỉ phản ánh các dịch vụ CÓ gắn máy — đây là phạm vi đúng và mong muốn.
- Số lượng: 58 máy cá thể, gộp thành ~10 nhóm máy.

### 2.3. Chỉ số & điều kiện

- **Chỉ số:** `SUM(ss.amount)` AS so_luong (số lượng dịch vụ).
- **Điều kiện WHERE:**
  - `ss.is_delete = 0`
  - `ss.is_no_execute IS NULL` (chỉ dịch vụ đã thực hiện trên máy)
  - `ss.tdl_intruction_time BETWEEN :from AND :to` (lọc theo **ngày chỉ định y lệnh** — đồng bộ với các chart dịch vụ Home hiện có; định dạng `YYYYMMDDHH24MISS`).
  - `ext.machine_id IS NOT NULL` (đảm bảo có máy — thực chất join INNER đã loại NULL).
- **Hai chiều tổng hợp** (trả về trong cùng một response):
  - `by_group`: `GROUP BY m.machine_group_code` (hiển thị mã nhóm; nếu rỗng → "(trống)"), sắp xếp `so_luong` giảm dần.
  - `by_machine`: `GROUP BY m.machine_name` (hoặc `m.id, m.machine_name`), sắp xếp `so_luong` giảm dần.

---

## 3. Endpoint (theo pattern Home)

- **Controller:** `HomeController@fetchServiceByMachine(Request $request)` — AJAX-only (nếu không `$request->ajax()` thì `redirect()->route('home')`, theo pattern các method chart khác).
- **Tham số:** `startDate`, `endDate` (chuỗi). Chuẩn hóa về `YYYYMMDDHH24MISS` bằng helper sẵn có `HomeController::currentDate($startDate, $endDate)` (đã dùng ở `fetchDiagnoticImaging` v.v. — `Carbon::parse(...)->format('YmdHis')`).
- **Connection:** `DB::connection('HISPro')`.
- **Trả về** (JSON):

```json
{
  "by_group":   { "labels": ["TNT","CL","SA", "..."], "data": [709, 254, 37, "..."], "total": 1078 },
  "by_machine": { "labels": ["Máy chụp cắt lớp...", "..."], "data": [254, "..."], "total": 1078,
                  "groups": ["CL", "..."] }
}
```
- `by_machine.groups` (tùy chọn): mã nhóm tương ứng từng máy, để tooltip hiển thị nhóm khi xem theo từng máy.

Một query gom dữ liệu chi tiết theo `machine_id` rồi controller tổng hợp ra cả 2 chiều bằng PHP (giống pattern gom nhóm bằng PHP trong các method Home khác), HOẶC 2 query GROUP BY riêng — chốt khi triển khai theo cách gọn/đúng pattern nhất.

---

## 4. Route & cấu hình

- Thêm route trong nhóm route Home ở `routes/web.php`:
  ```php
  Route::get('fetch-service-by-machine', 'HomeController@fetchServiceByMachine')->name('fetch-service-by-machine');
  ```
- Thêm vào map `window.DASHBOARD_CFG.routes` trong `resources/views/home.blade.php`:
  ```js
  fetchServiceByMachine: "{{ route('fetch-service-by-machine') }}",
  ```

---

## 5. Giao diện (Highcharts)

- **Container:** thêm 1 box AdminLTE full-width (`col-md-12`) trong khu vực các chart dịch vụ/CLS của `home.blade.php` (gần `chart_diagnotic_imaging_time`, ~dòng 355):
  - Header box: tiêu đề **"Số lượng dịch vụ theo máy thực hiện"** + **btn-group 2 nút** (`#btn-machine-by-group` / `#btn-machine-by-item`), mặc định chọn "Theo nhóm máy".
  - Thân box: `<div id="chart_service_by_machine" style="width:100%;height:420px;"></div>`.
- **Loại biểu đồ:** Highcharts `column` (cột đứng), hiển thị tất cả mục.
- **Trục X:** nhãn xoay -45° để đọc tên dài; **Trục Y:** số lượng (bắt đầu 0).
- **Tooltip:** hiện tên + số lượng; ở chế độ "từng máy" hiện thêm nhóm máy.
- **Tiêu đề phụ/legend:** hiện tổng số lượng.

---

## 6. Tích hợp JS module hóa (theo đúng điểm tích hợp thực tế)

Cấu trúc thực tế: `api.js` export `win.DAPI`; `charts.js` export `win.DCharts` với hàm điều phối duy nhất `DCharts.renderAll(start, end)` (gọi mọi chart qua `$.when([...])`); `autorefresh.js` (`DAutoRefresh`) gọi `DCharts.renderAll` khi tải lần đầu, theo chu kỳ, và khi đổi khoảng ngày; `init.js` chỉ khởi tạo daterangepicker + `bindClicks()` + khởi động auto-refresh. KHÔNG có nơi nào trong init.js render từng chart riêng — phải tích hợp vào `renderAll`.

- **`public/js/dashboard/api.js`:** thêm vào object `API` (export `win.DAPI`):
  ```js
  serviceByMachine: function (start, end) { return get(R.fetchServiceByMachine, { startDate: start, endDate: end }); }
  ```

- **`public/js/dashboard/charts.js`:**
  - Khai báo **biến cấp module** giữ trạng thái xuyên qua các lần `renderAll`:
    ```js
    var sbmLastData = null;          // payload {by_group, by_machine} đã fetch gần nhất
    var sbmMode = 'group';           // 'group' | 'machine' (mặc định nhóm)
    ```
  - Hàm `renderServiceByMachine(start, end)`: gọi `DAPI.serviceByMachine(start,end)`, lưu `sbmLastData = resp`, rồi vẽ theo `sbmMode` (Highcharts column). Trả về promise (để đưa vào `$.when`).
  - Hàm vẽ nội bộ `drawSbm()`: đọc `sbmLastData[sbmMode === 'group' ? 'by_group' : 'by_machine']` → `Highcharts.chart('chart_service_by_machine', {...})`.
  - Export thêm `DCharts.setServiceByMachineMode = function(mode){ sbmMode = mode; if (sbmLastData) drawSbm(); }` để nút chuyển vẽ lại **từ data đã lưu, KHÔNG gọi lại server**.
  - **Đăng ký vào `renderAll`:** thêm `renderServiceByMachine(start, end)` vào mảng `$.when.apply($, [ ... ])` trong `DCH.renderAll` (gần `renderDiagImaging(...)`).
  - Vì `renderServiceByMachine` đọc `sbmMode` cấp module, auto-refresh và đổi khoảng ngày sẽ **tự fetch dữ liệu mới nhưng giữ nguyên chế độ** đang chọn (không cần sửa autorefresh.js).

- **`public/js/dashboard/init.js`:** trong `bindClicks()` thêm xử lý 2 nút:
  ```js
  $(document).on('click', '#btn-machine-by-group, #btn-machine-by-item', function () {
    var mode = (this.id === 'btn-machine-by-item') ? 'machine' : 'group';
    $('#btn-machine-by-group, #btn-machine-by-item').removeClass('active');
    $(this).addClass('active');
    DCharts.setServiceByMachineMode(mode);
  });
  ```

- **`public/js/dashboard/autorefresh.js`:** KHÔNG cần sửa (đã gọi `DCharts.renderAll`, tự bao gồm chart mới; chế độ được giữ nhờ biến module ở charts.js).

> Phân biệt rõ: `renderAll` (tải trang / auto-refresh / đổi ngày) luôn **fetch lại dữ liệu mới**; chỉ thao tác **bấm nút chuyển** mới tái dùng `sbmLastData` (không fetch). Không cache xuyên qua lần đổi ngày.

---

## 7. Phân quyền / hiển thị

- Hiển thị theo cùng cờ điều kiện với các chart dịch vụ hiện có trên Home (vd `DASHBOARD_CFG.canDashboard`) — xác minh khi triển khai để nhất quán; nếu các chart cùng khu vực không gắn cờ thì để hiển thị mặc định.

---

## 8. Edge cases & lưu ý

- **Không có dữ liệu trong khoảng ngày** → labels/data rỗng → hiển thị empty-state theo pattern code hiện có (chèn `<div>Không có dữ liệu</div>` vào container, như `renderDoanhThu`/`renderTreatment`) thay vì dựa vào "No data" mặc định của Highcharts (plugin no-data có thể chưa load).
- **machine_group_code NULL/rỗng** → gộp vào nhãn "(trống)".
- **Tên máy trùng** (vd nhiều "Máy chạy thận nhân tạo.xx") → đã là tên riêng từng máy nên không gộp nhầm; nếu gộp theo `machine_name` mà trùng tên thì gộp đúng theo tên.
- **Hiệu năng:** dữ liệu nhỏ (≤ ~1k–vài k dòng/khoảng ngày, ≤58 máy) → nhẹ. Lọc theo `tdl_intruction_time` (đã có index theo pattern các chart khác).
- **Oracle column casing:** các method Home hiện có đọc lowercase `$row->so_luong` chạy đúng (yajra/oci8 trả key lowercase ở các query này) → KHÔNG cần chuẩn hóa thêm, chỉ theo cùng convention lowercase.

---

## 9. Out of scope (YAGNI)

- Không thêm bộ lọc riêng (loại DV, khoa) cho biểu đồ này — chỉ theo khoảng ngày chung.
- Không drill-down/đi tới bảng chi tiết.
- Không export riêng cho biểu đồ này.
- Không tính dịch vụ không gắn máy (ngoài phạm vi).
