# Chỉnh màn trình chiếu giao ban: thứ tự cột, bỏ slide phòng khám, tăng cỡ chữ

Ngày: 2026-08-04

## Bối cảnh

Màn trình chiếu giao ban (`resources/views/khth/giaoban-present.blade.php`) đang có ba vấn đề khi
chiếu lên tường phòng họp:

1. Slide "Hoạt động điều trị" sắp cột trông như ngẫu nhiên.
2. Slide "Lượt khám theo phòng khám" không còn cần thiết.
3. Chữ nhỏ, người ngồi xa khó đọc.

Về vấn đề 1, thứ tự cột hiện tại **không ngẫu nhiên** mà là "theo thứ tự xuất hiện đầu tiên":
`BangDieuTri::dungCot()` duyệt các khoa theo `sort_order`, trong mỗi khoa duyệt chỉ tiêu theo thứ
tự khai, gặp nhãn mới thì thêm cột. Cột nào lên trước phụ thuộc vào khoa nào tình cờ khai nhãn đó
sớm nhất — không phản ánh ý đồ nào của người dùng. Hệ thống hiện **không có** chỗ nào để khai thứ
tự mong muốn.

## Mục 1 — Thứ tự cột do người dùng khai

### Khai báo

Thêm vào `MetricSchema::COMMON_FIELDS`, cạnh `dieu_tri_slide`:

```php
'dieu_tri_order' => ['widget' => 'number', 'label' => 'Thứ tự cột trên slide',
                     'show_if' => ['dieu_tri_slide' => [true]]],
```

Không phải sửa giao diện cấu hình: `public/js/giaoban/metric-builder.js` dựng widget `number` và
xử lý `show_if` một cách tổng quát cho mọi khóa trong `COMMON_FIELDS`. Ô chỉ hiện khi đã tích
"Hiện ở slide Hoạt động điều trị".

### Luật sắp xếp (`BangDieuTri::dungCot`)

- Cột vẫn gộp theo NHÃN như hiện nay, không đổi.
- Một nhãn được nhiều khoa khai với số thứ tự khác nhau thì **lấy số nhỏ nhất**, không báo lỗi.
  Nhờ vậy KHTH chỉ cần khai một chỗ thay vì nhớ khai đủ mọi khoa — cùng tinh thần với
  `dieu_tri_slide` (bật một nơi là cột lên slide).
- Cột chưa khai số xếp **sau** toàn bộ cột đã khai, giữ nguyên thứ tự xuất hiện đầu tiên như hiện
  tại. KHTH có thể chỉ đánh số cho vài cột quan trọng, phần còn lại tự trôi xuống cuối.
- Sắp bằng khóa kép `(số thứ tự, vị trí xuất hiện)`, không chỉ theo số. Lý do: `usort` của PHP 7.4
  không ổn định (ổn định từ PHP 8.0), hai cột cùng số mà thiếu khóa phụ thì thứ tự nhảy giữa các
  lần chạy — đúng cái bệnh đang chữa.

### Kiểm tra đầu vào (`MetricValidator::kiemKhoaDungChung`)

- `dieu_tri_order` phải là số nguyên (cho phép bỏ trống).
- Khai số mà không bật `dieu_tri_slide` thì báo lỗi, theo đúng luật đang áp cho `overview_label`:
  đã khai thì phải dùng được vào đâu đó.
- Không cần luật riêng cho khối: `dieu_tri_slide` đã bị chặn ngoài khối Điều trị nội trú, mà
  `dieu_tri_order` bắt buộc đi kèm `dieu_tri_slide`.

## Mục 2 — Bỏ slide "Lượt khám theo phòng khám"

Xóa hẳn khỏi blade:

- hàm `phongKhamSlide()`,
- khối đẩy slide và mục "Phòng khám" trong menu nhảy nhanh,
- các CSS `.pk-cot`, `.pk-list`, `.pk-kpis` chỉ phục vụ slide này.

Giữ `.caprow`, `.capname`, `.captrack`, `.capfill`, `.cappct`, `.capnum` vì slide Công suất giường
dùng chung.

Ngoài phạm vi: backend vẫn trả `room_stats`. Không đụng tới, vì có thể còn nơi khác dùng và yêu
cầu chỉ nói tới màn trình chiếu.

## Mục 3 — Tăng cỡ chữ

Nhân **1.25** mọi `font-size` tính theo `vh` trong khối `<style>` và các chỗ đặt inline trong JS.

Hai ngoại lệ:

- `.donut-pct: 17px` và `.donut-cap: 7px` **giữ nguyên**. Chúng nằm trong hệ tọa độ `viewBox` của
  SVG, không phải đơn vị màn hình, nên đã tự phóng to theo khung donut; nhân thêm là chữ tràn ra
  ngoài vòng tròn.
- Ngưỡng cỡ chữ bảng điều trị (biến `co` trong `dieuTriSlide`) nhân **1.15** thay vì 1.25. Bảng này
  tự thu nhỏ theo số cột để vừa màn; tăng mạnh thì bảng nhiều cột sẽ tràn và phải cuộn, mà chiếu
  lên tường thì phần phải cuộn coi như mất dữ liệu.

## Kiểm chứng

- `php -l` trên các file sửa.
- Unit test thuần cho `BangDieuTri::dungCot()`: cột có số sắp đúng thứ tự; cột không số xếp cuối
  giữ thứ tự cũ; hai khoa khai cùng nhãn khác số thì lấy số nhỏ nhất; hai cột cùng số giữ thứ tự
  xuất hiện. Chạy bằng `vendor/bin/phpunit --testsuite Unit` (gate thực tế của kho này).
- Mắt thường trên màn trình chiếu cho phần cỡ chữ và slide bị bỏ.
