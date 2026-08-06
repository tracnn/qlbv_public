# Trình chiếu giao ban: chỉ tiêu dạng bảng + theme sáng/tối

Ngày: 2026-08-06
Tệp tác động: `resources/views/khth/giaoban-present.blade.php` (một tệp duy nhất, không đổi phía máy chủ)

## Bối cảnh

Màn trình chiếu giao ban hiện có bốn loại slide:

- **Tổng quan** (`overviewSlide`): kíp trực, lưới thẻ KPI 4 cột, cảnh báo ô bắt buộc, ghi chú chung.
- **Hoạt động điều trị** (`dieuTriSlide`): bảng `.bdt` ma trận khoa × chỉ tiêu, dữ liệu do máy chủ dựng sẵn.
- **Từng khoa** (`deptSlide`): lưới thẻ KPI 4 cột, các khối chỉ tiêu chuỗi, ghi chú khoa.
- **Công suất giường** (`capacityDeptSlide`): donut + thanh công suất theo khoa.

Hai vấn đề cần xử lý:

1. Thẻ KPI (nhãn nhỏ, số rất to) tốn diện tích và không cho phép so sánh nhanh giữa các chỉ tiêu. Người dùng muốn chúng ở dạng bảng có viền, căn lề theo kiểu dữ liệu.
2. Toàn bộ trình chiếu chỉ có nền tối. Một số phòng họp sáng cần bản nền sáng.

## Phạm vi

Trong phạm vi:

- Đổi lưới thẻ KPI sang bảng ở **cả** `overviewSlide` và `deptSlide`.
- Thêm nút chuyển theme sáng/tối trên thanh điều khiển dưới, có ghi nhớ lựa chọn.

Ngoài phạm vi (chốt rõ để không phình việc):

- **Không** dựng ma trận khoa × chỉ tiêu cho màn Tổng quan. Số liệu Tổng quan vẫn là tổng toàn viện gom theo `overview_label`, đúng như `theTongQuan()` đang làm.
- **Không** đổi bảng Hoạt động điều trị. Giữ nguyên cả dữ liệu, cột, căn lề (hiện căn giữa toàn bộ).
- Không đổi API, không đổi `App\Services\GiaoBan\BangDieuTri`, không đổi màn nhập liệu.

## Phần 1 — Chỉ tiêu dạng bảng

### Bố cục

Một hàm dựng bảng dùng chung, gọi từ cả hai chỗ đang sinh `.kpis`:

```
bangChiTieu(items)   // items: [{ nhan, gia_tri, cls }]
```

Bảng có **4 cột**, mỗi dòng chứa **2 cặp `Chỉ tiêu | Số liệu`**:

```
CHỈ TIÊU        | SỐ LIỆU |  CHỈ TIÊU        | SỐ LIỆU
BN vào viện     |      21 |  BN ra viện      |      19
Chuyển viện     |       1 |  Tử vong         |       0
```

- Số chỉ tiêu lẻ thì cặp cuối cùng để trống hai ô (`<td>` rỗng, vẫn có viền).
- Hàng tiêu đề lặp lại nhãn `CHỈ TIÊU | SỐ LIỆU` hai lần, căn giữa.

### Căn lề

| Loại nội dung | Căn lề |
|---|---|
| Ô tiêu đề (`th`) | giữa |
| Tên chỉ tiêu | trái |
| Giá trị số | phải |
| Giá trị phần trăm | phải |
| Giá trị chuỗi/khuyết (`—`) | trái |

Nhận biết phần trăm: giá trị được coi là phần trăm nếu chuỗi hiển thị kết thúc bằng `%`. Hiện `num()` chỉ trả số nên thực tế mọi giá trị số đều căn phải — quy tắc này chỉ để bảng không sai khi sau này có chỉ tiêu phần trăm.

### Viền và màu

- Mọi ô đều có viền, dùng lại đúng bộ viền/nền tiêu đề của `.bdt` để hai màn nhìn cùng một hệ thị giác. Lớp CSS mới `.bct` kế thừa cùng biến màu, không sửa `.bdt`.
- Màu nhấn hiện có (`kpiClass`) giữ nguyên ngữ nghĩa nhưng tô vào **ô số**, không tô cả thẻ:
  - `movement_in` / `movement_transfer_in` → màu xanh (tăng).
  - `bn_ra_vien` / `bn_chuyen_vien` → màu cam (giảm).

### Cỡ chữ và tràn

- Cỡ chữ co theo **số dòng** của bảng, cùng tinh thần với cách `dieuTriSlide` co theo số cột: ≤ 6 dòng dùng cỡ lớn nhất, càng nhiều dòng cỡ càng nhỏ, có sàn tối thiểu.
- Cỡ chữ vẫn nhân với `var(--z)` để nút `A−/A+` tiếp tục có tác dụng.
- Chạm sàn mà vẫn tràn thì khung bọc cho cuộn, giống `.bdt-wrap`.

### Không đổi

- Chỉ tiêu kiểu chuỗi (`laChiTieuChuoi`) vẫn nằm ở khối `.note` riêng bên dưới bảng, không nhét vào bảng.
- Các khối kíp trực, cảnh báo ô bắt buộc, ghi chú chung, ghi chú khoa giữ nguyên vị trí và hình thức.
- Thông báo "Chưa đánh dấu chỉ tiêu nào" ở Tổng quan giữ nguyên nội dung, chỉ đổi vỏ cho khớp bố cục mới.

## Phần 2 — Theme sáng/tối

### Cơ chế

Biến CSS + thuộc tính `data-theme` trên `<html>`:

- Rút toàn bộ màu viết cứng trong CSS về biến ở `:root` (nền, panel, chữ, chữ mờ, đường kẻ, các màu nhấn, các ngưỡng công suất).
- Khai báo lại giá trị của cùng bộ biến đó trong `html[data-theme="light"]`.
- Màu do JS sinh (viền donut, thanh công suất, các `style="color:#fff"` nội tuyến) đổi sang trả **tên biến CSS** thay vì hex. Nhờ vậy đổi theme là màu tự đổi theo, **không phải dựng lại DOM**, không mất slide đang chiếu và không mất vị trí cuộn.

`capColor(pct)` đổi từ trả hex sang trả `var(--cap-*)` theo bốn ngưỡng hiện có (≥90, ≥80, ≥60, còn lại).

### Điều khiển

- Một nút trên thanh `#bar`, đặt cạnh nhóm `A− 100% A+`, nhãn `☀` khi đang ở theme tối (bấm để sang sáng) và `☾` khi đang ở theme sáng.
- **Không thêm phím tắt**, tránh đụng các phím đang dùng cho điều hướng và zoom.
- Lưu localStorage khoá `giaoban.present.theme`, giá trị `dark` | `light`. Nạp lúc khởi động theo đúng khuôn `napZoom()`, có bọc `try/catch` cho chế độ riêng tư.
- **Mặc định `dark`** — bản hiện tại — kể cả khi máy đặt chế độ sáng. Không đọc `prefers-color-scheme`.
- Giống mức zoom, theme là thiết lập của **máy đang chiếu**, không gửi lên máy chủ, không thuộc về báo cáo.

### Bảng màu sáng

Thiết kế riêng cho nền sáng, không đảo màu máy móc:

- Nền trang trắng ngà, panel trắng có viền nhạt (thay cho panel `#13293d` nổi trên nền tối).
- Chữ chính màu than, chữ phụ xám trung tính.
- Màu nhấn được chỉnh lại độ đậm và độ bão hoà để đủ tương phản trên nền sáng — cụ thể là đậm hơn bản tối, vì các màu pastel của theme tối (`#5dcaa5`, `#efc877`) đọc không rõ trên nền trắng.
- Các cặp nền/chữ của khối cảnh báo (`.ov-canh-bao.tot` / `.xau`) và badge trạng thái (`.ov-badge.nhap` / `.chot`) đều có bản sáng tương ứng.

## Kiểm chứng

Đây là một tệp Blade thuần, không có tầng logic để viết test đơn vị. Kiểm chứng bằng cách mở màn trình chiếu thật và xác nhận:

1. Slide Tổng quan và slide từng khoa hiện bảng 4 cột, viền đủ, căn lề đúng quy tắc.
2. Khoa có số chỉ tiêu lẻ vẫn ra bảng cân, ô cuối trống có viền.
3. Nút `A−/A+` vẫn phóng chữ trong bảng; phóng tối đa thì bảng cuộn chứ không tràn khỏi slide.
4. Bấm nút theme: toàn bộ deck đổi màu, **vẫn ở đúng slide đang xem**, donut và thanh công suất đổi màu theo.
5. Tải lại trang: theme được giữ như lần chọn trước.
6. Slide Hoạt động điều trị không thay đổi so với trước.

## Rủi ro

- Số lượng màu viết cứng khá lớn và nằm rải ở cả CSS lẫn JS. Sót một chỗ thì theme sáng sẽ có mảng tối lạc lõng — cần rà cả `donutHtml`, `capColor`, và mọi `style="...#..."` nội tuyến trong chuỗi HTML.
- Bảng nhiều dòng ở mức zoom cao sẽ phải cuộn. Đây là đánh đổi đã chấp nhận, giống bảng Hoạt động điều trị.
