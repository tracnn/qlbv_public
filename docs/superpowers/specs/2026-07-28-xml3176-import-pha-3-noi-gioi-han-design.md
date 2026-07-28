# Import XML3176 — Giai đoạn 3: nới giới hạn cho endpoint tải lên

Ngày: 2026-07-28
Phạm vi: `BHYTXml3176Controller@uploadData`, `config/xml3176.php`

Giai đoạn 3 của bốn. **Phạm vi đã thu hẹp so với dự kiến ban đầu** — lý do ở mục "Tiền đề đã đổi".

Tài liệu này gộp cả thiết kế lẫn các bước thực hiện: thay đổi chỉ khoảng mười dòng, viết
thêm một file kế hoạch riêng là không tương xứng.

## Tiền đề đã đổi

Khi chia bốn giai đoạn, tôi cho rằng `uploadData` nhận **cả mẻ file trong một request**, và
đó là nghi can hàng đầu của lỗi hết bộ nhớ trên máy chủ mới.

Đọc lại màn nhập khẩu thì **sai**: nó dùng Dropzone với `uploadMultiple` để mặc định
(`false`), `parallelUploads = 2`. Trình duyệt gửi **mỗi request đúng một file**.

Hệ quả trực tiếp: luật `'xmls.*'` chỉ có một phần tử, nên nhánh `Arr::dot` ở tầng
Validation là không đáng kể. **`upload-data` gần như chắc chắn không phải thủ phạm** của
lỗi `Out of memory ... at Arr.php:115`. Nghi can còn lại là `fetch-data` — màn danh sách,
đã sửa ở đợt đầu ngày.

Việc đẩy import sang hàng đợi vì thế mất lý do cấp bách, và nó còn có một cái giá không
lường trước: Dropzone hiện hiện **kết quả từng file ngay tại chỗ**, mà giai đoạn 2 vừa
làm cho thông điệp từ chối đó trở nên có ý nghĩa. Chuyển sang hàng đợi là mất phản hồi
tức thì, muốn không thụt lùi thì phải dựng thêm bảng nhật ký nhập và màn theo dõi.

Chủ đầu tư chọn phương án nhỏ: **nới giới hạn cho riêng endpoint này**.

## Vấn đề còn lại

Một file XML3176 được phép tới **100 MB** (`max:102400` trong luật validate, và
`maxFilesize: 100` phía Dropzone). Xử lý một file như vậy gồm: đọc cả file vào bộ nhớ,
`simplexml_load_string` toàn bộ, rồi với **từng** `FILEHOSO` lại `base64_decode` và
`simplexml_load_string` một lần nữa. Bộ nhớ phình gấp nhiều lần kích thước file.

Máy chủ mới giới hạn **128 MB / 120 giây**, trong khi Dropzone chờ tới **300 giây**. Một
file lớn sẽ làm PHP chết ở mốc 120 giây còn người dùng chỉ thấy "lỗi" không rõ nguyên do.

`uploadData` hiện **không nới giới hạn nào**, khác với các lớp `Exports/` trong dự án vốn
đều tự nới.

## Thiết kế

Nới hai giới hạn ở đầu `uploadData()`, lấy giá trị từ cấu hình:

```php
set_time_limit((int) config('xml3176.import_time_limit', 600));
ini_set('memory_limit', config('xml3176.import_memory_limit', '512M'));
```

Thêm hai khoá vào `config/xml3176.php` kèm chú thích.

### Vì sao không dùng `4096M` / `1800` như các lớp Export

Quy ước sẵn có trong `app/Exports/` là `set_time_limit(1800)` + `ini_set('memory_limit', '4096M')`.
**Không sao chép con số đó**, vì bối cảnh khác hẳn:

- Các lớp Export chạy khi **một người** bấm xuất báo cáo, hoạ hoằn.
- `uploadData` là endpoint web mà Dropzone bắn **2 request song song cho mỗi người dùng**,
  và nhiều người có thể nhập cùng lúc.

Cho phép mỗi request tới 4 GB trên một máy đang cấu hình 128 MB nghĩa là vài request đồng
thời có thể làm **cạn RAM thật của máy**. Tiến trình bị hệ điều hành giết là tình huống
tệ hơn hẳn một lỗi PHP sạch sẽ: không log, không thông điệp, và có thể kéo theo các
request khác.

`512M` đủ rộng cho file cỡ vài chục MB mà vẫn giữ trần: 2 request song song × 512 MB là
1 GB — chấp nhận được trên máy chủ thông thường.

Để trong cấu hình để anh chỉnh theo máy thật mà không phải sửa mã.

### Đây là giảm nhẹ, không phải bảo đảm

Nới giới hạn **không** biến file 100 MB thành xử lý được. Nó dời bức tường ra xa, không
dỡ bỏ. Nếu vẫn có file chết, số liệu cần nhìn là `mem_peak_mb` trong log — commit
`bec2e05` đã ghi sẵn trường này vào mọi dòng log lỗi.

## Không thuộc phạm vi

1. **Đẩy import sang hàng đợi + bảng nhật ký nhập + màn theo dõi.** Đã cân nhắc và
   không làm ở đợt này; xem "Tiền đề đã đổi".
2. **Giới hạn kích thước file** hiện là 100 MB — giữ nguyên, chưa có số liệu để chọn mức khác.
3. **Lệnh quét thư mục** chạy ở CLI, nơi `memory_limit` thường đã là `-1` và không có
   `max_execution_time` — không cần nới.
4. Giai đoạn 4: một job kiểm lỗi mỗi *(hồ sơ, loại XML)* thay vì mỗi dòng.

## Các bước thực hiện

- [ ] **Bước 1: Viết test (sẽ đỏ)** — `tests/Unit/Xml3176/Xml3176UploadGioiHanTest.php`
      kiểm `uploadData` có nới cả hai giới hạn, và hai khoá cấu hình tồn tại với giá trị
      mặc định hợp lệ.
- [ ] **Bước 2:** chạy test, xác nhận đỏ.
- [ ] **Bước 3:** thêm hai khoá vào `config/xml3176.php`.
- [ ] **Bước 4:** thêm hai lời gọi vào đầu `uploadData()`, kèm chú thích nêu rõ vì sao
      không dùng `4096M`.
- [ ] **Bước 5:** chạy test, xác nhận xanh.
- [ ] **Bước 6:** `php -l` hai file, chạy `vendor/bin/phpunit --testsuite Unit`
      (mốc hiện tại **308 test xanh**).
- [ ] **Bước 7:** commit.

## Kiểm chứng

**Tự động:**

- `uploadData()` chứa cả `set_time_limit` lẫn `ini_set('memory_limit'`.
- Hai khoá cấu hình tồn tại; `import_time_limit` là số nguyên dương;
  `import_memory_limit` khớp dạng `\d+[KMG]`.
- **Không** dùng `4096M` — chặn việc ai đó sao chép quy ước của `Exports/` vào đây.

**Thủ công:**

| # | Việc | Mong đợi |
|---|---|---|
| 1 | Tải lên một file XML bình thường | Nhập được như trước, Dropzone vẫn hiện kết quả từng file |
| 2 | Tải lên file lớn (vài chục MB) từng làm treo | Xử lý xong, không còn chết ở mốc 120 giây |
| 3 | Tải lên hai file lớn cùng lúc | Cả hai xong; theo dõi RAM máy chủ trong lúc chạy |
| 4 | Nếu vẫn có file chết | Đọc `mem_peak_mb` trong log để biết mức thật, rồi chỉnh `import_memory_limit` |

Mục 3 là mục cần chú ý nhất: nó kiểm đúng cái rủi ro mà việc nới giới hạn tạo ra.
