# Thiết kế: Đổi "Số lượng" chart "Doanh thu & Số lượng theo loại dịch vụ" sang đếm phiếu his_service_req

Ngày: 2026-05-18

## Bối cảnh

Dashboard tại `http://localhost:8000/` có chart "Doanh thu & Số lượng theo loại dịch vụ".
Dữ liệu chart đến từ `HomeController::doanhthuOverview()`
(`app/Http/Controllers/HomeController.php:331-355`), được xử lý bởi
`HomeController::fetchDoanhthuOverview()` (`app/Http/Controllers/HomeController.php:260`).

Hiện tại cột `so_luong` của chart = `sum(his_sere_serv.amount)` — tổng số lượng
các dòng dịch vụ chi tiết trong bảng `his_sere_serv`.

## Vấn đề

Người dùng muốn "Số lượng" trên chart phản ánh **số phiếu chỉ định**
(`his_service_req`) thay vì tổng số lượng dòng dịch vụ chi tiết.

## Mục tiêu

`so_luong` = số phiếu `his_service_req` riêng biệt, gom nhóm theo loại dịch vụ
(`service_req_type`) và đối tượng (`patient_type`).

## Phạm vi

CHỈ sửa hàm `doanhthuOverview()` trong `HomeController.php`. Không đổi:
- `thanh_tien` (doanh thu) — vẫn tính từ `his_sere_serv.amount * price`.
- `fetchDoanhthuOverview()` — cấu trúc trả về JSON giữ nguyên.
- JavaScript render chart.

## Giải pháp (Hướng A — query đơn)

Trong `doanhthuOverview()`, đổi biểu thức select của `so_luong`:

- Trước: `sum(his_sere_serv.amount) as so_luong`
- Sau:   `count(distinct his_service_req.id) as so_luong`

Các phần khác của query giữ nguyên:
- JOIN `his_service_req`, `his_service_req_type`, `his_patient_type`.
- Điều kiện `whereBetween intruction_time`, `is_active = 1`, `is_delete = 0`
  (cho cả `his_service_req` và `his_sere_serv`).
- `groupBy` theo `service_req_type_id`, `service_req_type_name`,
  `patient_type_id`, `patient_type_name`.

Lý do dùng `count(distinct ...)`: một phiếu `his_service_req` có nhiều dòng
`his_sere_serv`; sau khi JOIN mỗi phiếu xuất hiện nhiều lần, nên phải đếm
distinct để không đếm trùng.

## Cân nhắc đã biết

- `patient_type_id` lấy từ `his_sere_serv`. Nếu một phiếu có các dòng chi tiết
  khác đối tượng nhau thì phiếu đó bị đếm ở nhiều cột đối tượng. Thực tế gần
  như mọi phiếu chỉ có một đối tượng nên ảnh hưởng không đáng kể; chấp nhận.
- Chỉ đếm các phiếu có ít nhất một dòng `his_sere_serv` chưa xóa (do INNER
  JOIN). Phù hợp vì chart gắn với doanh thu theo dòng chi tiết.

## Phương án bị loại

Hướng B — tách query riêng đếm `his_service_req` rồi merge trong PHP. Bị loại
vì phức tạp hơn, thêm query, và không cần đếm phiếu không có dòng chi tiết.

## Kiểm thử

- Truy cập `http://localhost:8000/`, mở chart "Doanh thu & Số lượng theo loại
  dịch vụ".
- Xác nhận tổng "Số lượng" ở header chart giảm xuống (số phiếu < tổng số dòng
  dịch vụ) và các datalabel cột phản ánh số phiếu.
- Xác nhận "Doanh thu" tổng và theo cột không thay đổi so với trước.
