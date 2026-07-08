# Thiết kế: Ghi chú rich text (CKEditor) cho báo cáo giao ban

**Ngày:** 08/07/2026
**Module:** KHTH — báo cáo giao ban (nhập liệu + trình chiếu + Excel).
**Mục tiêu:** Ghi chú khoa và ghi chú chung soạn bằng CKEditor (định dạng đậm/nghiêng/màu/danh sách...) để khi trình chiếu hiển thị đẹp; xử lý XSS, Excel strip HTML, chạy offline.

## 1. Bối cảnh đã xác minh
- CKEditor 4 đã bundle offline: `public/vendor/ckeditor/ckeditor.js`, **nạp toàn cục** trong `resources/views/vendor/adminlte/master.blade.php:59` → có sẵn trên mọi trang AdminLTE (gồm `giaoban-index`). sticky-note dùng `<textarea class="ckeditor">`.
- HTMLPurifier (`ezyang/htmlpurifier`) đã có trong composer; `class_exists('HTMLPurifier')` = true; chưa dùng ở đâu.
- Ghi chú lưu ở: `giaoban_report_cells.note` (metric_code = 'note', theo khoa) và `giaoban_reports.general_note`. Cột `text` — chứa HTML được.
- Màn `giaoban-index` render lưới động qua JS `render()`; lưới dựng lại mỗi lần lưu ô số liệu (loadReport). Ghi chú hiện là textarea inline, đọc `.val()`.
- `giaoban-present` (trang trần) render ghi chú khoa qua `esc(noteCell.note)` (text thuần).
- `GiaoBanExport` xuất Excel: ghi chú khoa (`* note`) và ghi chú chung in trực tiếp.

## 2. Kiến trúc soạn thảo — Modal CKEditor dùng chung

Vòng đời editor phải tách khỏi lưới re-render:
- Một **modal Bootstrap** (AdminLTE có sẵn) đặt ngoài `#report-body`, chứa **một** `<textarea id="note-editor">`. Khởi tạo `CKEDITOR.replace('note-editor', <config gọn>)` một lần khi tải trang.
- Trong lưới `render()`: mỗi khối khoa hiển thị **vùng xem** `<div class="dept-note-view">{{HTML}}</div>` + nút **"Sửa ghi chú"** (chỉ khi `canEditDept`). Ghi chú chung: vùng xem + nút "Sửa" (chỉ admin).
- Bấm "Sửa" → mở modal, ghi nhớ target (`dept_config_id` hoặc `'general'`), `editor.setData(currentHtml)`. Bấm "Lưu" trong modal → `editor.getData()` → POST (saveCell note / saveGeneralNote) → cập nhật `.dept-note-view` tương ứng → đóng modal. Editor không bị hủy khi lưới dựng lại (nằm trong modal tĩnh).
- Toolbar gọn (config CKEditor): `Bold Italic Underline`, `TextColor`, `FontSize`, `NumberedList BulletedList`, `JustifyLeft/Center/Right`, `RemoveFormat`. `removePlugins` ảnh/bảng/link để tránh HTML phức tạp.

## 3. Lưu trữ & bảo mật (chống XSS)

- Thêm `App\Services\GiaoBan\NoteSanitizer::clean(?string $html): string` — bọc HTMLPurifier:
  - `HTMLPurifier_Config::createDefault()`, `Cache.DefinitionImpl = null` (không ghi cache ra đĩa), `HTML.Allowed` giới hạn: `p,br,b,strong,i,em,u,ul,ol,li,span[style],div[style],h3,h4`, `CSS.AllowedProperties = color,font-size,text-align,font-weight,font-style,text-decoration`.
  - Trả `''` khi input null/rỗng.
- `GiaoBanController@saveCell`: khi `metric_code === 'note'` → `$cell->note = NoteSanitizer::clean($request->input('note'))`.
- `GiaoBanController@saveGeneralNote` → `general_note = NoteSanitizer::clean(...)`.
- Chỉ sanitize tại điểm ghi (single choke point) → DB luôn HTML an toàn; nơi hiển thị render thô an toàn.

## 4. Hiển thị

- **Màn nhập (`giaoban-index`)**: `.dept-note-view` và vùng ghi chú chung đặt HTML bằng `.html(note)` (đã sanitize khi lưu). Người không có quyền sửa khoa: chỉ thấy vùng xem, không có nút Sửa (đúng phân quyền hiện có). Các trường KHÁC (display_name, m.name) vẫn `esc()`.
- **Trình chiếu (`giaoban-present`)**: ô ghi chú khoa render **HTML thô** — bỏ `esc()` CHỈ cho trường note (`'<div class="txt">' + note + '</div>'`), các trường khác giữ `esc()`. CSS `.note .txt` cho phép hiển thị định dạng.
- **Excel (`GiaoBanExport`)**: ghi chú khoa và ghi chú chung qua `trim(strip_tags(...))` (kèm `html_entity_decode`) → văn bản thuần, không thẻ HTML, không lỗi ô Excel.

## 5. Files
- Mới: `app/Services/GiaoBan/NoteSanitizer.php`.
- Sửa: `app/Http/Controllers/KHTH/GiaoBanController.php` (saveCell, saveGeneralNote).
- Sửa: `resources/views/khth/giaoban-index.blade.php` (modal + vùng xem + init CKEditor + save note qua modal).
- Sửa: `resources/views/khth/giaoban-present.blade.php` (render note HTML).
- Sửa: `app/Exports/GiaoBanExport.php` (strip_tags note + general_note).
- Test: `tests/Unit/GiaoBan/NoteSanitizerTest.php`.

## 6. Xử lý lỗi
- CKEditor chưa nạp (lỗi asset): nút Sửa fallback — nếu `window.CKEDITOR` undefined, dùng `prompt`/textarea thường (không chặn nhập). (Rủi ro thấp vì đã nạp toàn cục.)
- NoteSanitizer với input rỗng → `''`. HTMLPurifier lỗi cấu hình cache đã tránh bằng `Cache.DefinitionImpl = null`.
- Ghi chú quá dài: cột `text` đủ chứa.

## 7. Kiểm thử
- **Unit** `NoteSanitizerTest`: giữ `<b>`/`<span style="color:...">`; cắt `<script>`, thuộc tính `onerror`, thẻ `<iframe>`; input null → `''`.
- **Present render** (Node như các lần trước): note chứa `<b>` + `<script>` → slide hiển thị `<b>` giữ, `<script>` đã bị sanitize từ khâu lưu (mô phỏng: đưa HTML đã-sanitize vào payload, xác nhận render thô có `<b>`).
- **Excel**: unit/nhỏ kiểm `strip_tags` cho note trong mảng export (hoặc kiểm hàm build ra chuỗi không còn `<`).
- Không phá 26 unit test giao ban hiện có.
