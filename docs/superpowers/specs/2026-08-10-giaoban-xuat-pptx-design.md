# Trình chiếu giao ban: xuất PPTX

Ngày: 2026-08-10
Tệp tác động: `resources/views/khth/giaoban-present.blade.php`, thêm `public/js/giaoban/pptx.js` và `public/js/vendor/pptxgen.bundle.js`

## Bối cảnh

Màn trình chiếu giao ban (`khth.giao-ban-present`) dựng slide hoàn toàn ở trình duyệt từ một lời gọi API. Máy chủ trả dữ liệu thô; bố cục slide chỉ tồn tại trong JS của trang.

Người dùng cần mang bản giao ban sang PowerPoint để **chiếu lại ở cuộc họp khác và sửa thêm slide**. Vì vậy tệp xuất ra phải là PPTX với **đối tượng PowerPoint thật** — bảng là bảng, chữ là hộp văn bản, biểu đồ là biểu đồ — chứ không phải ảnh chụp.

Ràng buộc đã biết:

- Máy chủ mới giới hạn PHP 128MB / 120s.
- Dự án không có npm hay bước build; JS phía client là tệp thuần trong `public/js/` (mẫu: `public/js/giaoban/metric-builder.js`).
- Composer có `dompdf`, `fpdf`, `phpspreadsheet` nhưng **không có** thư viện PPTX, và không chỗ nào trong mã đang dùng `dompdf`.

## Quyết định kiến trúc

**Sinh PPTX ở trình duyệt bằng PptxGenJS**, không phải ở máy chủ.

Lý do: dữ liệu và theme đang chọn đã nằm sẵn trong trình duyệt; làm ở máy chủ nghĩa là viết lại logic dựng slide lần thứ hai bằng PHP, và hai bản sẽ trôi khỏi nhau. Thêm nữa, việc sinh tệp chạy trên máy đang chiếu nên không chạm giới hạn 128MB/120s, và không phải chạy `composer install` khi triển khai.

Thư viện được nhúng sẵn vào `public/js/vendor/pptxgen.bundle.js` (MIT), commit kèm repo — dự án không có npm nên không thể khôi phục bằng lệnh cài.

## Phạm vi

Trong phạm vi:

- Tách một tầng mô tả deck dùng chung giữa bộ dựng HTML và bộ dựng PPTX.
- Nút xuất PPTX trên thanh điều khiển.
- Bốn loại slide hiện có đều xuất được.

Ngoài phạm vi:

- **Không xuất PDF.** Người dùng chọn PPTX vì cần sửa lại nội dung; PDF không phục vụ mục đích đó.
- Không đổi API, controller, hay bất kỳ tệp PHP nào.
- Không đổi diện mạo màn trình chiếu. Phần tách tầng mô tả deck phải cho ra HTML **giống hệt** trước.

## Phần 1 — Tầng mô tả deck

Hiện mỗi hàm slide vừa đọc dữ liệu vừa nối chuỗi HTML (`dieuTriSlide` vừa đọc `bang_dieu_tri` vừa nối `<td>`). Tách thành:

```
moTaDeck(data) → mang cac slide, dung thu tu hien tai:
  Tong quan -> Hoat dong dieu tri -> tung khoa (sort_order) -> Cong suat giuong
```

Mỗi phần tử là một object thuần, không chứa HTML:

```js
// Tong quan
{ loai: 'tong-quan', tieuDe, ngay, badge: {chu, loai:'nhap'|'chot'}, phuDe,
  kipTruc: [{ viTri, nguoi: [{ten, dienThoai}] }],
  bang: { items: [{nhan, giaTri, mau:''|'teal'|'amber'}] },
  canhBao: { dat: true|false, chu },
  ghiChu: '' }

// Hoat dong dieu tri
{ loai: 'dieu-tri', tieuDe, ngay,
  cot: ['Đầu kỳ', ...], dong: [{ten, o: [số|null]}], tong: [số|null] }

// Tung khoa
{ loai: 'khoa', tieuDe, ngay, lechCanDoi: số|null,
  bang: { items: [...] },
  khoiChuoi: [{nhan, noiDung}],
  ghiChu: '' }

// Cong suat giuong
{ loai: 'cong-suat', tieuDe, ngay,
  donut: { tong, dung, trong, pct } | null,
  theoKhoa: [{ten, dung, tong, pct}] }
```

Bộ dựng HTML hiện tại đọc từ mảng này thay vì đọc `data` trực tiếp. Bộ dựng PPTX đọc **cùng** mảng đó.

PPTX **không được đọc ngược từ DOM**: đọc DOM là cách làm giòn, đổi một tên class là hỏng bản xuất mà không test nào bắt được.

## Phần 2 — Bảng màu PPTX

Lấy bằng `getComputedStyle(document.documentElement).getPropertyValue('--ten-bien')` trên chính các biến CSS đã có (`--bg`, `--strong`, `--muted`, `--txt-2`, `--panel-2`, `--line-2`, `--teal`, `--amber`, `--red`, `--blue`, `--brand`).

Nhờ vậy yêu cầu "PPTX theo theme đang chọn" là hệ quả tự nhiên, không phải chép lại bảng màu lần thứ ba.

Giá trị biến trả về dạng `#rrggbb` hoặc `#rgb`; PptxGenJS cần `RRGGBB` không có dấu thăng, nên có một hàm chuẩn hoá: bỏ `#`, bung dạng 3 ký tự thành 6, viết hoa. Biến không đọc được (trình duyệt cũ) thì lùi về màu của theme tối.

## Phần 3 — Ánh xạ sang PowerPoint

Khổ **16:9** (10 × 5.625 inch). Mọi slide: nền `--bg`, tiêu đề trên trái màu `--strong`, ngày trên phải màu `--muted`, một đường kẻ mảnh màu `--line-2` dưới tiêu đề.

| Slide | Dựng bằng |
|---|---|
| Tổng quan | Hộp văn bản kíp trực; **bảng thật** 4 cột `TIÊU CHÍ / SỐ LIỆU / TIÊU CHÍ / SỐ LIỆU`; dòng cảnh báo ô bắt buộc; hộp ghi chú chung. Badge trạng thái ghép vào tiêu đề. |
| Hoạt động điều trị | **Bảng thật** khoa × cột, hàng tiêu đề nền `--panel-2`, dòng TỔNG CỘNG in đậm. |
| Từng khoa | **Bảng thật** tiêu chí; mỗi khối chỉ tiêu chuỗi một hộp văn bản riêng bên dưới; ghi chú khoa cuối slide. |
| Công suất giường | **Biểu đồ doughnut** gốc PowerPoint (đang dùng / trống) + **biểu đồ cột** phần trăm công suất theo khoa. |

Quy tắc căn lề của bảng giữ đúng như trên màn: tiêu đề cột căn giữa, tên căn trái, số và phần trăm căn phải, ô khuyết (`—`) căn trái. Màu nhấn của ô số (`teal`/`amber`) giữ nguyên ngữ nghĩa.

Donut và biểu đồ cột là **biểu đồ gốc của PowerPoint**, không phải ảnh — người nhận vẫn sửa được số liệu, đúng yêu cầu "đối tượng thật".

Slide nào không có dữ liệu thì bị bỏ qua y như trên màn chiếu: không có `bang_dieu_tri` thì không có slide Hoạt động điều trị; không có giường thì không có slide Công suất.

## Phần 4 — Nút và hành vi

- Nút `⬇ PPTX` trên thanh `#bar`, đặt cạnh nút theme. **Không thêm phím tắt** — cùng lý do đã áp cho nút theme: tránh đụng phím điều hướng.
- **Nạp lười**: thư viện chỉ được tải ở lần bấm đầu tiên, bằng cách chèn thẻ `<script>`. Người chỉ chiếu mà không xuất thì không phải tải 1MB. Lần bấm sau dùng lại thư viện đã nạp.
- Trong lúc nạp và dựng: nút đổi chữ thành `Đang xuất…` và bị vô hiệu hoá, tránh bấm chồng sinh hai tệp.
- Xong thì nút trở lại `⬇ PPTX`. Tên tệp: `giao-ban-YYYY-MM-DD.pptx` theo ngày báo cáo, không phải ngày hệ thống.
- Lỗi (không nạp được thư viện, dựng hỏng): nút hiện `Xuất lỗi`, chi tiết ghi ra console, và **màn chiếu không bị ảnh hưởng gì**. Đang họp mà trắng màn vì bấm nhầm nút xuất là hỏng việc. Sau 3 giây nút trở lại trạng thái thường để thử lại được.
- Nút chỉ xuất hiện khi đã có dữ liệu báo cáo. Ngày chưa có số liệu thì không có gì để xuất.

## Phần 5 — Tổ chức tệp

| Tệp | Trách nhiệm |
|---|---|
| `public/js/vendor/pptxgen.bundle.js` | Thư viện PptxGenJS, nhúng nguyên bản, không sửa |
| `public/js/giaoban/pptx.js` | Nhận mảng mô tả deck + bảng màu, trả về tệp PPTX. Không biết gì về DOM |
| `resources/views/khth/giaoban-present.blade.php` | `moTaDeck()`, bộ dựng HTML, nút và trạng thái nút |

`pptx.js` tách riêng vì hai lý do: tệp Blade đã dài (khoảng 640 dòng) và không nên gánh thêm vài trăm dòng dựng slide; và tách ra thì chạy được trong Node để kiểm chứng tự động — thứ không làm được nếu mã nằm trong Blade.

`pptx.js` phơi một hàm duy nhất:

```js
xuatPptx(deck, mau, tenTep, PptxGenJS) → Promise
```

Nhận `PptxGenJS` qua tham số chứ không đọc biến toàn cục, để Node truyền vào bản `require` được.

## Kiểm chứng

**Tự động (Node).** Chạy chính `pptx.js` trong Node với thư viện đã nhúng, dựng deck mẫu bao các trường hợp biên, sinh tệp thật, giải nén và đối chiếu XML:

1. Đúng số slide và đúng thứ tự.
2. Bảng Hoạt động điều trị đúng số dòng, số cột, có dòng TỔNG CỘNG.
3. Chữ tiếng Việt còn nguyên dấu trong XML.
4. Màu nền slide đổi theo bảng màu truyền vào (chạy hai lần, hai bảng màu).
5. Slide thiếu dữ liệu bị bỏ qua đúng như quy tắc.

**Trên trình duyệt.** Dùng lại trang kiểm thử sinh từ chính tệp Blade:

1. Bốn loại slide hiển thị **không đổi** so với trước khi tách tầng mô tả deck.
2. Nút hiện đúng, bấm thì đổi trạng thái rồi trở về.
3. Không có dữ liệu báo cáo thì không có nút.

## Rủi ro

- Tầng mô tả deck chạm vào cả bốn hàm dựng slide. Sai một chỗ là màn chiếu hỏng — đây là màn dùng thật trong giao ban hằng ngày. Vì vậy việc tách tầng phải đi trước và được xác nhận "không đổi gì" trước khi thêm phần PPTX.
- PptxGenJS dựng biểu đồ doughnut và cột theo lược đồ riêng; nếu lược đồ không khớp mong đợi thì lùi về bảng số liệu cho slide Công suất, vẫn là đối tượng thật và vẫn sửa được. Ghi rõ ở đây để không phải quyết định vội lúc đang viết mã.
- Thư viện 1MB commit vào repo. Chấp nhận: dự án không có npm nên không có cách khôi phục nào khác.
