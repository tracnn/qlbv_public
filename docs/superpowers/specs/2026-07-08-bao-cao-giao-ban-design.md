# Thiết kế: Báo cáo giao ban bệnh viện

**Ngày:** 08/07/2026
**Module:** KHTH (project qlbv — Laravel, dữ liệu HIS Pro Vietsens trên Oracle)
**Tham chiếu biểu mẫu:** Google Sheets "Báo cáo giao ban" (gid=0) — báo cáo hàng ngày theo khoa: BN cũ / vào / chuyển đến / ra viện / chuyển khoa / chuyển viện / hiện có / giường yêu cầu, PT cấp cứu/phiên, đẻ thường, lượt khám, XN (huyết học/sinh hóa/nước tiểu), CĐHA (X-quang/siêu âm), ghi chú BN chuyển tuyến, tổng hợp toàn viện.

## 1. Mục tiêu & phạm vi

- Màn hình web trong module KHTH: chọn ngày giao ban + khoảng giờ số liệu tùy ý (mặc định 07:00 hôm trước → 07:00 ngày giao ban), tự động tính số liệu từ HIS.
- Cho phép **sửa/ghi đè số liệu và nhập tay** trước khi in; phân quyền nhập theo khoa.
- **Chốt báo cáo** và **xuất Excel** đúng layout biểu mẫu.
- Cấu hình động danh sách khoa và chỉ tiêu (tái sử dụng cho nhiều đơn vị triển khai).

## 2. Kiến trúc: Snapshot + chỉnh sửa + chốt

Luồng nghiệp vụ:

1. Người dùng mở màn hình *Báo cáo giao ban*, chọn ngày + khoảng giờ.
2. Bấm **Lấy số liệu** → `GiaoBanDataService` query Oracle HIS, tính toàn bộ chỉ tiêu theo khoa, lưu snapshot vào DB local.
3. Người dùng sửa từng ô (giá trị sửa lưu cạnh giá trị gốc), nhập ghi chú theo khoa + ghi chú chung.
4. **Chốt báo cáo** → khóa chỉnh sửa (chỉ `giaoban-admin` mở khóa lại, có log). Excel xuất từ bản chốt.
5. **Lấy lại số liệu** trước khi chốt: cập nhật `auto_value` mới, **giữ nguyên** `manual_value` và ghi chú.

Giá trị hiển thị = `COALESCE(manual_value, auto_value)`.

## 3. Mô hình dữ liệu (DB local)

| Bảng | Nội dung |
|---|---|
| `giaoban_reports` | 1 dòng / ngày giao ban: `report_date`, `from_time`, `to_time`, `status` (draft/final), `general_note`, người tạo/chốt, timestamps |
| `giaoban_report_cells` | `report_id`, `dept_config_id` (null = dòng tổng), `metric_code`, `auto_value`, `manual_value` (null = chưa sửa), `note` |
| `giaoban_dept_configs` | `his_department_id`, tên hiển thị, thứ tự, `is_active`, `metrics` (JSON: chỉ tiêu bật cho khoa + danh sách mã dịch vụ đặc thù: đẻ thường, loại giường YC, nhóm XN…) |
| `giaoban_user_departments` | `user_id`, `dept_config_id` — gán tài khoản ↔ khoa (1 user nhiều khoa được) |

## 4. Phân quyền (Laratrust)

- **`giaoban-khoa`**: xem toàn báo cáo; chỉ nhập/sửa ô + ghi chú của khoa được gán (`giaoban_user_departments`). Không thấy nút Lấy số liệu / Chốt.
- **`giaoban-admin`** (KHTH/lãnh đạo): toàn quyền — lấy số liệu, sửa mọi khoa, chốt/mở khóa, cấu hình khoa, gán tài khoản↔khoa.
- Kiểm tra quyền **server-side** khi lưu từng ô, không chỉ ẩn UI.

## 5. Mapping chỉ tiêu → SQL HIS

Thời gian HIS Pro là số `YYYYMMDDHHMISS`; mọi query lọc theo `from_time`/`to_time`. Mỗi chỉ tiêu = 1 `metric_code`.

### 5.1 Khoa lâm sàng (HIS_DEPARTMENT_TRAN + HIS_TREATMENT)

| Metric | Cách tính |
|---|---|
| `bn_cu` | BN nội trú đang ở khoa tại `from_time`: có tran vào khoa trước `from_time`, chưa có tran sang khoa khác, chưa `OUT_TIME` trước mốc đó |
| `bn_vao` | Tran vào khoa trong kỳ, là tran **đầu tiên** của đợt điều trị (vào viện thẳng khoa) |
| `bn_chuyen_den` | Tran vào khoa trong kỳ, không phải tran đầu (từ khoa khác chuyển sang) |
| `bn_chuyen_khoa` | Tran rời khoa (tran kế tiếp sang khoa khác) trong kỳ |
| `bn_ra_vien` / `bn_chuyen_vien` / `bn_tu_vong` | `OUT_TIME` trong kỳ, khoa cuối = khoa này; phân loại theo `TREATMENT_END_TYPE_ID` (join `HIS_TREATMENT_END_TYPE`); tử vong theo `DEATH_TIME` |
| `hien_co` | Như `bn_cu` tại `to_time`. Tự kiểm tra cân đối: cũ + vào + đến − ra − đi = hiện có; lệch → cảnh báo UI |
| `giuong_yc` | BN đang nằm giường yêu cầu tại `to_time`: `HIS_TREATMENT_BED_ROOM` + `HIS_BED`; nhận diện theo danh sách loại giường cấu hình |
| `pt_cap_cuu` / `pt_phien` | Dịch vụ PTTT thực hiện trong kỳ tại khoa (`HIS_SERE_SERV` loại PT/TT), tách cấp cứu theo `PRIORITY`/`IS_EMERGENCY` của service req |
| `de_thuong` | Đếm dịch vụ thuộc danh sách mã dịch vụ đỡ đẻ (cấu hình) |
| `bn_cap_cuu` | Lượt vào khoa cấp cứu trong kỳ có `IS_EMERGENCY = 1` |

### 5.2 Khoa Khám bệnh (HIS_SERVICE_REQ loại khám)

- `kham_benh`: số lượt khám trong kỳ.
- `vao_vien`: lượt khám có quyết định nhập viện trong kỳ.
- `kham_yeu_cau`: lượt khám phòng yêu cầu / đối tượng thu phí (theo `PATIENT_TYPE`).
- Chỉ tiêu không có trong HIS ("Chuyên gia BV tỉnh"…): ô nhập tay thuần (`auto_value = null`).

### 5.3 Khoa XN & CĐHA (HIS_SERE_SERV + HIS_SERVICE_TYPE)

- XN tách Huyết học / Sinh hóa / Nước tiểu theo nhóm dịch vụ (danh sách mã nhóm cấu hình).
- CĐHA tách X-quang / Siêu âm theo loại dịch vụ.

### 5.4 Dòng tổng toàn viện

Khám ngoại trú, BN nội trú hiện có, tổng giường YC — tổng các khoa, tôn trọng giá trị đã sửa tay.

> Khi implement: từng SQL chạy thử trên connection `hispro_bvnn`, đối chiếu số thực tế (số đã báo cáo tay) trước khi đưa vào code — đặc biệt `bn_cu`/`hien_co` và phân loại ra viện/chuyển viện.

## 6. Giao diện

### Màn chính `khth/giao-ban`

- Toolbar: ngày giao ban, từ giờ – đến giờ, nút Lấy số liệu / Lưu / Chốt / Xuất Excel; trạng thái *Nháp / Đã chốt* (người chốt, thời điểm).
- Bảng biểu mẫu: mỗi khoa một khối theo thứ tự cấu hình, ô nhập inline. Ô sửa tay: đổi màu nền + tooltip "Số HIS: x" + nút ↺ trả về số tự động. Ô nhập tay thuần: nền bình thường.
- Ghi chú theo khoa (textarea) + ghi chú chung.
- Tài khoản `giaoban-khoa`: chỉ khối khoa được gán editable, khoa khác read-only.
- Icon cảnh báo vàng khi lệch cân đối.

### Màn cấu hình `khth/giao-ban/cau-hinh` (chỉ admin)

Danh sách khoa (chọn từ `HIS_DEPARTMENT`), tên hiển thị, thứ tự kéo-thả, bật/tắt chỉ tiêu từng khoa, danh sách mã dịch vụ đặc thù, tab gán tài khoản↔khoa.

## 7. Xuất Excel

Dùng thư viện export sẵn có của project. Layout bám biểu mẫu: tiêu đề "BÁO CÁO GIAO BAN + ngày", từng khoa một khối, ghi chú in nghiêng dưới khối, phần TỔNG HỢP cuối. Chưa chốt vẫn xuất được, đóng dấu "BẢN NHÁP".

## 8. Vị trí code

- Controller: `app/Http/Controllers/KHTH/GiaoBanController.php`
- Service: `app/Services/GiaoBan/GiaoBanDataService.php` (query Oracle), tách mỗi nhóm metric một method.
- Models: `GiaoBanReport`, `GiaoBanReportCell`, `GiaoBanDeptConfig`, `GiaoBanUserDepartment`.
- Views Blade + JS theo pattern báo cáo KHTH hiện có; routes nhóm `khth/` với middleware `checkrole`.
- Migrations cho 4 bảng mới.

## 9. Xử lý lỗi

- Mất kết nối Oracle: báo lỗi rõ, giữ snapshot cũ.
- Lấy lại số liệu: chỉ ghi đè `auto_value`, không đụng `manual_value`/ghi chú.
- Đã chốt: mọi API ghi từ chối, trừ admin mở khóa (log người mở).

## 10. Kiểm thử

- Unit test service tính chỉ tiêu (mock kết quả Oracle).
- Test phân quyền: user khoa A không ghi được ô khoa B (server-side).
- Đối chiếu số thật: chạy SQL trên `hispro_bvnn` cho 1–2 ngày gần nhất, so với báo cáo tay của bệnh viện để hiệu chỉnh logic.
