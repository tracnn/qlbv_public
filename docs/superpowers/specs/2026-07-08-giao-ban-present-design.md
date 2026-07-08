# Thiết kế: Chế độ trình chiếu (Present) báo cáo giao ban

**Ngày:** 08/07/2026
**Module:** KHTH — bổ sung cho tính năng Báo cáo giao ban (`docs/superpowers/specs/2026-07-08-bao-cao-giao-ban-design.md`)
**Bối cảnh:** Module giao ban đã có màn nhập/sửa số liệu và xuất Excel. Cần thêm nút **Present** để trình chiếu số liệu ra màn hình lớn (TV/máy chiếu) kiểu PowerPoint/Canva, phục vụ buổi giao ban chuyên nghiệp.

## 1. Mục tiêu & phạm vi

- Nút **Present** trên màn `khth/giao-ban` mở chế độ trình chiếu toàn màn hình cho báo cáo của **ngày đang chọn**.
- Deck slide: 1 slide tổng quan toàn viện + mỗi khoa 1 slide, tông nền tối xanh y tế, số liệu lớn dễ đọc từ xa, vài biểu đồ chọn lọc.
- Điều hướng bằng phím mũi tên/click, có thanh trạng thái số slide, nhảy nhanh tới khoa.
- **Không** query lại HIS khi trình chiếu (ảnh chụp số liệu hiện tại, gồm phần sửa tay + ghi chú). **Không** auto-play, **không** xuất PDF riêng (deck thuần HTML nên Ctrl+P vẫn ra PDF nếu cần).
- Đổi nhãn nút "Xem" hiện tại thành "Làm mới" cho đúng chức năng (tránh nhầm với Present).

## 2. Kiến trúc: trang trình chiếu độc lập

- Nút Present mở route mới `GET khth/giao-ban/present?date=YYYY-MM-DD` (mở tab mới).
- Trang này là Blade **trần** (không `@extends('adminlte::page')`, không sidebar) — tự chứa CSS/JS.
- Trang gọi lại API AJAX `khth.giao-ban-show` **sẵn có** (không sửa API) để lấy `report`, `configs`, `cells`, `balance_warnings`. Toàn bộ deck dựng phía client từ JSON đó.
- Lý do chọn trang riêng thay vì overlay trong màn index: fullscreen sạch (không dính menu AdminLTE), tách bạch code, in/PDF dễ.
- Không đụng tới service/model/DB/migration. Chỉ thêm: 1 route, 1 method controller, 1 view, sửa nhỏ view index.

## 3. Nội dung slide

Giá trị hiển thị mỗi ô = `COALESCE(manual_value, auto_value)` (giống bảng nhập). Chỉ lấy khoa `is_active`, đúng `sort_order`; chỉ tiêu nào có trong `metrics` của khoa mới lên slide.

### 3.1 Slide 1 — Tổng quan toàn viện
- Tiêu đề: tên đơn vị + "BÁO CÁO GIAO BAN" + ngày (định dạng "Sáng thứ …, dd/mm/yyyy"), phụ đề khoảng giờ (`from_time → to_time`).
- 4 KPI lớn (tính bằng tổng các khoa qua hàm sumMetric phía client):
  - Nội trú hiện có = Σ `hien_co`
  - Khám ngoại trú = giá trị chỉ tiêu khám của khoa Khám bệnh (metric_code khám nếu có; nếu không có thì bỏ ô này)
  - Giường yêu cầu = Σ `giuong_yc`
  - PT trong ngày = Σ (`pt_cap_cuu` + `pt_phien`) nếu có
- Biểu đồ cột nhóm: BN vào (`bn_vao`+`bn_chuyen_den`) / ra (`bn_ra_vien`+`bn_chuyen_vien`) theo từng khoa lâm sàng.
- Donut công suất giường: Σ `hien_co` / tổng số giường. Tổng giường KHÔNG có sẵn trong dữ liệu report → **ẩn donut nếu không tính được** (không hard-code). (YAGNI: không thêm truy vấn giường tổng ở bản này; chỉ vẽ donut khi có nguồn — hiện chưa có nên mặc định ẩn.)

> Quyết định: KPI/biểu đồ nào thiếu dữ liệu nguồn thì **ẩn ô đó**, không hiển thị 0 gây hiểu nhầm. Slide tổng quan luôn hiển thị được với tối thiểu tiêu đề + KPI "Nội trú hiện có".

### 3.2 Slide 2..N — mỗi khoa 1 slide
- Tên khoa lớn (từ `display_name`) + ngày giao ban ở header.
- Lưới KPI: mỗi chỉ tiêu bật của khoa là một card (nhãn nhỏ + số lớn). Màu nhấn: vào = xanh ngọc, ra viện = cam, hiện có = trắng; còn lại trung tính.
- Hộp ghi chú khoa (cell `metric_code = note`) nếu có nội dung — nền nhấn, chữ lớn dễ đọc. Ẩn nếu rỗng.
- Icon cảnh báo nếu khoa nằm trong `balance_warnings`.

### 3.3 Tông màu
Nền navy tối (#0d1b2a), card #13293d, chữ trắng/#e8eef5, accent teal #5dcaa5 và amber #ef9f27, phụ #8aa4bd. Cố định (không phụ thuộc dark/light của host vì là trang trình chiếu riêng).

## 4. Điều hướng & UX

- Bấm Present (màn index) → mở tab present. Trang tự gọi `requestFullscreen()` khi người dùng bấm nút "Toàn màn hình" (một số trình duyệt chặn fullscreen tự động khi tải trang → có nút bấm rõ ràng; phím `F` cũng bật fullscreen).
- `→` / click nửa phải / `Space`: slide sau. `←` / click nửa trái: slide trước. `Home`/`End`: slide đầu/cuối.
- `ESC`: thoát fullscreen (trình duyệt xử lý), vẫn ở trang.
- Thanh dưới: `chỉ số / tổng` (VD 3/12), dãy chấm điều hướng, dòng phím tắt.
- Nhảy nhanh tới khoa: phím số hoặc menu danh sách khoa (nút mở danh sách overlay, click tên khoa để tới slide đó).
- Chỉ dựng deck sau khi JSON tải xong; khi đang tải hiện màn chờ. Nếu ngày chưa có báo cáo → slide thông báo "Chưa có dữ liệu cho ngày dd/mm/yyyy".

## 5. Biểu đồ

Dùng Chart.js nếu project đã nhúng sẵn ở layout dùng chung; trang present là trang trần nên sẽ **nạp Chart.js từ asset nội bộ của project** (kiểm tra `public/` có sẵn; nếu không có, vẽ cột bằng SVG/CSS thuần để tránh phụ thuộc CDN — môi trường bệnh viện có thể không có internet). Quyết định mặc định: **vẽ bằng SVG/CSS thuần**, không phụ thuộc thư viện ngoài, cho chắc chắn chạy offline.

## 6. Bảo mật

- Route trong group `checkrole:giaoban` — chỉ user có quyền giao ban vào được.
- Mọi dữ liệu người dùng (display_name, note, tên khoa, tên chỉ tiêu) phải **escape HTML** khi dựng DOM phía client (hàm `esc()` như đã áp dụng ở view index/config sau lần review trước) — tránh stored XSS.

## 7. Vị trí code

| File | Thay đổi |
|---|---|
| `routes/web.php` | Thêm `GET khth/giao-ban/present` → `GiaoBanController@present` trong group `checkrole:giaoban` |
| `app/Http/Controllers/KHTH/GiaoBanController.php` | Thêm method `present(Request $request)` trả view với `date`, `isAdmin` |
| `resources/views/khth/giaoban-present.blade.php` | Trang trần: CSS + JS deck (fetch show → dựng slide → phím/fullscreen/nav), có `esc()` |
| `resources/views/khth/giaoban-index.blade.php` | Thêm nút "Present" (mở URL present kèm date); đổi nhãn nút "Xem" → "Làm mới" |

## 8. Kiểm thử

- Client-side; verify thủ công: tạo báo cáo có dữ liệu (dùng pipeline test sẵn có), mở `khth/giao-ban/present?date=...`, chụp màn hình xác nhận: slide tổng quan render đúng KPI, mỗi khoa 1 slide, chuyển slide bằng phím/click, nút fullscreen, slide "chưa có dữ liệu" khi chọn ngày trống.
- Kiểm tra escape: đặt ghi chú khoa chứa `<script>`/`</textarea>` → xác nhận hiển thị dưới dạng text, không thực thi.
- Không có unit test mới (thuần view); các test cũ của module giao ban phải vẫn pass.
