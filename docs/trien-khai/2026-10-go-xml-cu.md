# Triển khai: gỡ module XML4750 (Qd130) và XML4210

Nhánh `go-xml-cu`. Chỉ gỡ mã, **không xoá bảng CSDL**: dữ liệu `qd130_*`, `xml1s..xml5s`,
`xml_error_checks`, `xml_error_catalogs` vẫn nằm nguyên trong CSDL. Không có migration mới.

## 1. Trước khi kéo mã mới

- [ ] Mở **Task Scheduler** trên máy chủ, tìm và **xoá/tắt** mọi tác vụ gọi một trong ba lệnh đã gỡ.
      Nếu để lại, mỗi lần chạy sẽ báo `Command "..." is not defined`.
  - `php artisan xml130import:day`
  - `php artisan xml4210import:day`
  - `php artisan sendqd130xmlerrors:day`
- [ ] Dịch vụ NSSM **không** cần làm tay: `update.bat` tự gỡ (idempotent) các dịch vụ `QLBV JobQd130Xml`, `QLBV JobSubmitQd130Xml`, `QLBV JobExportQd130Xml`, `QLBV XMLImport` và `QLBV ImportCatalog` (quét thư mục nhập danh mục BHXH, lệnh `importCatalogBHXH:data` đã gỡ — danh mục nay chỉ nhập qua màn hình).
- [ ] Giữ nguyên tác vụ `word:day` (nếu có). Lệnh vẫn còn, nay chỉ gửi phần lỗi thẻ BHYT.

## 2. Sau `git pull`

```
composer dump-autoload
php artisan config:cache
php artisan route:cache
php artisan view:clear
php artisan queue:restart
```

`config:cache` là **bắt buộc**: cache cũ còn trỏ `config/qd130xml.php` đã xoá.

## 3. Hàng đợi cũ

Các hàng đợi sau không còn worker hay mã đọc. Nếu còn việc tồn thì xoá:

- `JobQd130Xml`, `JobExportQd130Xml`, `JobSubmitQd130Xml` (Qd130)
- `JobXmlCheckError` (4210)

Laravel 5.5 **không có** `queue:clear`. Driver hàng đợi là `database`, nên xoá thẳng trong
bảng `jobs`. Đếm trước rồi mới xoá:

```sql
SELECT queue, COUNT(*) FROM jobs
WHERE queue IN ('JobQd130Xml','JobExportQd130Xml','JobSubmitQd130Xml','JobXmlCheckError')
GROUP BY queue;

DELETE FROM jobs
WHERE queue IN ('JobQd130Xml','JobExportQd130Xml','JobSubmitQd130Xml','JobXmlCheckError');
```

## 4. Dọn cấu hình riêng của máy (tuỳ chọn)

`config/filesystems.php` và `config/organization.php` nằm trong `.gitignore`, nên mã mới
không động vào. Các khoá dưới đây đã không còn mã nào đọc. Để lại thì vô hại, muốn gọn thì
xoá tay:

- `config/filesystems.php`: disk `CatalogBHXH` (thư mục quét danh mục; tệp còn nằm trong đó sẽ không ai xử lý nữa), disk `xml4210`, `xml4210tt`, `xml130`, `xml130tt`, `xml130GoogleDrive`, `exportXml130`
- `config/organization.php`: `submit_xml_enabled`, `submit_xml_url` (khối gốc gửi QĐ4750, **không phải** khối Điện Biên), `loai_ho_so_4750`, `xml_4750_not_check`

Xoá xong chạy lại `php artisan config:cache`.

## 5. Nghiệm thu tay

- [ ] Menu không còn "Xml 4750", "Xml 4210", "DM lỗi Xml 4750".
- [ ] Màn danh sách XML3176 mở được, breadcrumb hiện "Hồ sơ XML3176".
- [ ] Màn chi tiết lỗi một hồ sơ XML3176 có cột gợi ý xử lý (không phải toàn "Liên hệ quản trị hệ thống").
- [ ] Báo cáo "NVYT - Y lệnh" chọn một tháng: có số liệu; tổng cột Số lượng bằng tổng số dòng
      XML2 + XML3 của tháng đó. Xuất Excel được.
- [ ] Chạy `php artisan word:day --yesterday` (hoặc chờ lịch): mail đến, chỉ có mục "I. Lỗi thẻ",
      cột "Mã Thẻ (XML)" có giá trị.

## Quay lui

Revert các commit trên nhánh rồi chạy lại mục 2. CSDL không đổi nên không cần khôi phục dữ liệu.
