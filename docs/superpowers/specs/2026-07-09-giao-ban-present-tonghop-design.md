# Thiết kế: Nâng cấp mục tổng hợp trình chiếu giao ban (công suất giường + KPI + biểu đồ khoa)

**Ngày:** 09/07/2026
**Module:** KHTH — trình chiếu (`giaoban-present`) báo cáo giao ban.
**Mục tiêu:** Làm dày slide tổng hợp: thêm công suất giường (donut + theo khoa), thêm KPI ra viện/chuyển viện/tử vong/cấp cứu/PT/đẻ, tách thành 2 slide cho thoáng. Dữ liệu giường **snapshot** tại thời điểm "Lấy số liệu".

## 1. Bối cảnh đã xác minh
- Dashboard home có `HomeController::bedStatusByDepartment()`: per HIS department `tong_giuong` (his_bed/his_bed_room/his_room) + `dang_dung` (his_treatment_bed_room, `tdl_treatment_type_id IN (3,4)`, chưa ra viện, không phải co-treatment) tại một thời điểm.
- Giao ban đã có (trong report cells) các metric: `hien_co`, `bn_ra_vien`, `bn_chuyen_vien`, `bn_tu_vong`, `bn_cap_cuu`, `pt_cap_cuu`, `pt_phien`, `de_thuong`, `giuong_yc`, `luot_kham/kham_benh`. KPI mới chỉ **cộng cells sẵn có** (không tính lại).
- Present đọc JSON `show()`; hiện overview 1 slide (4 KPI + biểu đồ vào/ra + kíp trực).
- Config có `his_department_ids` per khoa báo cáo (dieu_tri gộp nhiều HIS dept).

## 2. Kiến trúc dữ liệu giường (snapshot)

Bảng mới `giaoban_report_beds`:
- `id`, `report_id`, `department_id` (his_department.id), `total_beds` (int), `used_beds` (int), timestamps. index `report_id`.

`GiaoBanMetricService::buildBedCapacitySql($at)` — tái dùng logic dashboard (KHÔNG loại patient_type để đồng nhất census giao ban):
- Trả per department: `department_id, total_beds, used_beds` tại `$at` (YmdHis). `used_beds`: `his_treatment_bed_room` remove_time IS NULL hoặc > $at, is_delete=0, không co-treatment, `t.tdl_treatment_type_id IN (3,4)`, `(t.out_time IS NULL OR t.out_time=0 OR t.out_time > $at)`.

`GiaoBanReportService::fetchAndStore` — sau khi lưu cells, chạy `buildBedCapacitySql(to_time)`, xóa `giaoban_report_beds` của report rồi chèn per-department rows.

## 3. show() bổ sung
- `bed_total`, `bed_used` (tổng toàn viện = Σ rows).
- `bed_by_config`: mảng `{dept_config_id, total, used}` — với mỗi config khối `dieu_tri` (hoặc có his_department_ids), cộng total/used theo `his_department_ids`. Dùng cho biểu đồ công suất theo khoa.

## 4. Trình chiếu — 2 slide tổng hợp

### Slide 1 "Tổng quan toàn viện"
- Lưới tối đa 8 KPI (ẩn cái nào không có cell nguồn), thứ tự: Nội trú hiện có (`hien_co`), Khám ngoại trú (`luot_kham/kham_benh/kham`), Vào viện (`vao_vien`/admission), Ra viện (`bn_ra_vien`), Chuyển viện (`bn_chuyen_vien`), Tử vong (`bn_tu_vong`), Cấp cứu (`bn_cap_cuu`), PT/Đẻ (`pt_cap_cuu+pt_phien` / `de_thuong`).
- Donut công suất giường toàn viện: `bed_used/bed_total` (%), kèm số Tổng / Đang dùng / Trống. Ẩn nếu `bed_total = 0`.
- Kíp trực: dải gọn ở dưới (giữ nội dung hiện có, nhiều người/chức danh).

### Slide 2 "Công suất và biến động theo khoa" (chèn ngay sau slide 1, trước slide khoa)
- Thanh công suất % từng khoa (từ `bed_by_config`, chỉ khoa có total>0), màu cảnh báo: ≥90% đỏ, ≥80% cam, ≥60% teal, còn lại xanh. Sắp giảm dần.
- Biểu đồ BN vào/ra theo khoa (chuyển từ slide 1 hiện tại sang đây).
- Ẩn slide 2 nếu không có dữ liệu giường lẫn biến động.

Present dựng slides: `[overview1, capacityDept(nếu có), ...deptSlides]`.

## 5. Xử lý lỗi
- `bed_total=0` (chưa có giường/ chưa fetch) → ẩn donut + ẩn slide 2 phần công suất.
- buildBedCapacity lỗi HIS → fetchAndStore bỏ qua (không chặn lưu cells); beds rỗng → present ẩn.
- KPI thiếu cell → ẩn ô.

## 6. Files
- Mới: migration `giaoban_report_beds`, model `GiaoBanReportBed`.
- Sửa: `GiaoBanMetricService` (buildBedCapacitySql), `GiaoBanReportService` (lưu beds trong fetchAndStore), `GiaoBanController@show` (bed_total/used/by_config), `giaoban-present.blade.php` (2 slide).
- Test: `GiaoBanMetricServiceTest` (buildBedCapacitySql chứa his_bed/his_treatment_bed_room + bind $at), hàm gộp `bed_by_config` nếu tách thuần.

## 7. Kiểm thử
- Unit: buildBedCapacitySql (string-assert: SELECT department_id/total_beds/used_beds, JOIN his_bed_room, treatment_type IN (3,4), bind at). Aggregation bed_by_config (thuần, nếu tách).
- Đối chiếu HIS thật: chạy buildBedCapacitySql tại 1 thời điểm → tổng total/used hợp lý, so công suất với dashboard.
- Present render (Node): slide 1 có donut khi có beds; slide 2 có thanh công suất; ẩn khi bed_total=0.
- 37 test cũ vẫn pass.
