# Đặc tả: Nâng cấp Laravel 5.5 → 13 và Docker hoá

Ngày: 2026-07-31

## 1. Mục tiêu và bối cảnh

### 1.1 Mục tiêu

Trả nợ kỹ thuật ở tầng nền tảng: đưa ứng dụng từ Laravel 5.5.50 / PHP 7.4 (cả hai đều đã hết vòng đời) lên Laravel 13.x / PHP 8.x, đồng thời chuyển môi trường triển khai từ XAMPP thủ công trên Windows Server sang Docker.

Đây **không** phải dự án làm mới tính năng. Tiêu chí thành công là: người dùng cuối không nhận ra ứng dụng đã thay đổi, ngoại trừ giao diện AdminLTE 3 ở Pha 5.

### 1.2 Hiện trạng

| Hạng mục | Hiện tại |
|---|---|
| Framework | Laravel 5.5.50 |
| PHP | 7.4.33 (XAMPP, Windows Server) |
| DB nghiệp vụ của ứng dụng | MySQL, schema `qlbv` (connection mặc định) |
| DB của HIS | Oracle 19c, qua `yajra/laravel-oci8` 5.5 (các connection `oracle`, `HISPro`, `ACS_RS`, ...) |
| Quy mô mã nguồn | 462 file PHP trong `app/`, 311 blade, 147 migration, 126 model, 63 controller, 150 file service, 32 console command |
| Route | 439 route web + 28 route API |
| Test | 133 file, 13.461 dòng, PHPUnit 6 |
| Scheduler | Windows Task Scheduler gọi thẳng từng lệnh `artisan` (`Console\Kernel::schedule()` đang rỗng) |
| Queue worker | nssm (Windows service) |

### 1.3 Kết quả khảo sát mã nguồn

Số đo thực tế trên repo, làm cơ sở cho ước lượng công sức:

| Kiểm tra | Kết quả | Ảnh hưởng |
|---|---|---|
| Helper `str_*` / `array_*` (bỏ ở L6) | 33 chỗ | Thấp |
| `Input::` facade (bỏ ở L6) | 0 | Không |
| `->lists()` | 0 | Không |
| `Form::` / `Html::` trong blade | 0 | Không |
| `protected $dates` (bỏ ở L10) | 1 | Không |
| Factory kiểu cũ `$factory->` | 1 | Không |
| `exec` / `shell_exec` / COM | 0 | Docker hoá không vướng phần cứng |
| Ký số USB token / HSM | Gọi HTTP tới agent ngoài qua Guzzle | Docker hoá không vướng |
| `adminlte::` trong blade | 130 view, kèm `resources/views/vendor/adminlte/` tự sửa | **Cao** |
| Dùng Laratrust | 64 chỗ | Trung bình |
| `resources/views/vendor/datatables/` tự sửa | Có | Trung bình |

Kết luận: rào cản nằm ở **thư viện**, không ở mã ứng dụng.

## 2. Quyết định thiết kế

Các quyết định dưới đây đã được chốt trong quá trình brainstorm và là ràng buộc của kế hoạch thực thi.

| # | Quyết định | Lý do |
|---|---|---|
| QĐ-1 | Đích: **Laravel 13.x**, PHP 8.x (chốt phiên bản chính xác ở Pha 0 theo yêu cầu thực của `laravel/framework` 13) | Mọi package đang dùng đều có bản hỗ trợ L13 sau khi gỡ `orchestra/parser` |
| QĐ-2 | Lộ trình: **dựng skeleton Laravel 13 rồi chuyển mã sang**, không nâng tuần tự qua 8 major version | Chi phí thật nằm ở giải phụ thuộc composer quanh hệ Oracle; nâng tuần tự phải trả chi phí đó 8 lần. Mã nguồn không dùng API đã bị xoá (mục 1.3) nên nhảy thẳng là an toàn |
| QĐ-3 | Docker hoá **nằm trong** dự án này, làm trước khi nâng framework | Chỉ đổi môi trường một lần; container cũng đóng vai trò staging |
| QĐ-4 | XAMPP giữ nguyên, chạy production tới ngày cắt chuyển | Là mốc đối chiếu và là đường lùi |
| QĐ-5 | AdminLTE: **nâng lên 3.16**, làm ở pha riêng sau khi framework đã xanh | Quyết định của chủ dự án. Tách pha để không lẫn lỗi framework với lỗi giao diện |
| QĐ-6 | Chuyển DB `qlbv` MySQL → Oracle: **ngoài phạm vi**. Trước mắt giữ MySQL. Khi làm sẽ là triển khai mới, không di trú dữ liệu cũ | Tách hai nguồn lỗi; giữ schema bất biến để rollback sạch |
| QĐ-7 | Bỏ hẳn: phân hệ **vaccination**, **sarcov2**, **pusher/broadcasting** | Không còn dùng. Mỗi file xoá đi là một file không phải port |
| QĐ-8 | Gỡ package: `orchestra/parser` (0 chỗ dùng), `fideloper/proxy` (đã vào core Laravel) | Dọn phụ thuộc chết |
| QĐ-9 | Kiểm thử: **smoke test toàn route** + **4 test chốt hành vi** cho luồng trọng yếu, không phủ test toàn bộ | Lỗi khi nâng framework chủ yếu là lỗi "vỡ khi tải trang"; phủ test 462 file không kinh tế |
| QĐ-10 | Trong pha nâng framework **không đổi schema đang dùng**; ngoại lệ `activity_log` thì tạo bảng mới tên khác | Bảo đảm rollback chỉ là đổi định tuyến |

## 3. Kiến trúc đích

### 3.1 Runtime

- Base image `php:8.x-fpm` trên Debian (**không dùng Alpine** — Oracle Instant Client cần glibc).
- Oracle Instant Client (basiclite + sdk) + extension `oci8` build qua `pecl`.
- Extension khác: `pdo_mysql` (DB `qlbv` vẫn là MySQL theo QĐ-6), `gd, mbstring, zip, intl, xml, xsl, gmp, bcmath, soap, opcache, redis`.
- Cấu hình `NLS_LANG` / charset Oracle và múi giờ Việt Nam ở tầng image; nhúng font tiếng Việt cho dompdf/FPDI.

### 3.2 docker-compose (viết lại toàn bộ)

`docker-compose.yml` hiện tại không dùng được: nó dựng MySQL 8 trong compose, base image PHP 7.4, không có OCI8.

Cấu hình đích gồm các service: `app` (php-fpm), `nginx`, `redis`, `queue` (thay nssm), `scheduler` (thay Task Scheduler).

**Bỏ service `mysql` khỏi compose** — MySQL `qlbv` và Oracle 19c đều là DB ngoài, container chỉ kết nối tới.

### 3.3 Chuyển scheduler và queue

- `Console\Kernel::schedule()` đang rỗng ⇒ danh sách tác vụ định kỳ **chỉ tồn tại trong Windows Task Scheduler, không có trong mã nguồn**. Phải liệt kê lại toàn bộ tác vụ này và đưa vào `routes/console.php` / `withSchedule()`, chạy bằng container `scheduler`. Đây là hạng mục dễ bị bỏ sót nhất của cả dự án.
- Queue worker nssm → container `queue`.

## 4. Cách chuyển mã sang skeleton mới

### 4.1 Quy ước git

Nhánh `upgrade/laravel-13` trên chính repo này. **Thay thế tại chỗ** các file skeleton (`composer.json`, `artisan`, `bootstrap/`, `public/index.php`, config của framework) bằng bản Laravel 13. Không tạo repo mới — để diff đọc được và lịch sử liên tục.

### 4.2 Skeleton Laravel 11+ (đã bỏ Kernel)

- `app/Http/Kernel.php` + `app/Console/Kernel.php` → gộp vào `bootstrap/app.php`; 8 middleware đăng ký lại theo cú pháp mới.
- 69 dòng `::class` trong `config/app.php` (providers + aliases): phần lớn để package tự khai báo qua auto-discovery; chỉ provider của dự án đưa vào `bootstrap/providers.php`.

### 4.3 Xử lý thư mục `config/`

Chia làm hai nhóm, xử lý khác nhau:

**Nhóm A — config của framework** (`app, auth, cache, database, mail, queue, session, view, broadcasting, filesystems, services, logging`): lấy **bản mặc định mới của Laravel 13**, rồi chép lại phần tuỳ biến của dự án. Không sửa vá file cũ, vì file config 5.5 thiếu quá nhiều khoá mới. Chú ý riêng `database.php`: giữ nguyên toàn bộ connection Oracle của HIS và connection `mysql` mặc định.

**Nhóm B — config riêng của dự án**, giữ nguyên: `__tech, catalog_import_mapping, danh_muc_bhyt, dvkt_nhom, emr_messages, loading, order_check, organization, qd130xml, qd130xml_suggestions, signing, xml3176, xml3176_suggestions`.

**Nhóm C — config của package**, phải lấy bản mới rồi ánh xạ lại: `adminlte, laratrust, datatables, datatables-buttons, datatables-fractal, datatables-html, jwt, activitylog, excel`.

### 4.4 Thứ tự bật phân hệ

Mỗi mốc là một cổng; không đạt thì không đi tiếp.

| # | Mốc | Bằng chứng đạt |
|---|---|---|
| 0 | `composer install` xanh trên PHP 8.x | Không còn xung đột phiên bản |
| 1 | Ứng dụng khởi động | `php artisan about` chạy |
| 2 | Kết nối Oracle 19c và MySQL | `select 1 from dual` + đọc được bảng thật ở cả hai DB |
| 3 | Đăng nhập + phân quyền (jwt-auth, laratrust) | Vào được trang chủ bằng tài khoản thật |
| 4 | Layout + Dashboard | Trang chủ hiển thị đúng |
| 5 | Datatables | Một màn hình danh sách chạy đủ lọc/sắp xếp/phân trang |
| 6 | Màn hình tra cứu & báo cáo | Smoke test toàn route không có route hỏng thêm |
| 7 | Xuất Excel / PDF | Đối chiếu file xuất với bản XAMPP |
| 8 | Tích hợp ngoài: BHXH, XML3176/QĐ130, cổng Điện Biên, Trục dữ liệu, ký số, FTP, SMS, LIS/PACS | Chạy chế độ chặn gửi, đối chiếu payload |
| 9 | Queue jobs + 32 console command | Chạy tay từng lệnh, so kết quả |

Mốc 8 có hậu quả nghiêm trọng nhất nếu sai (gửi dữ liệu sai lên cổng BHXH) nên bắt buộc chạy ở chế độ chặn gửi hoặc môi trường thử của các cổng.

## 5. Ma trận thư viện

Tra trực tiếp từ Packagist ngày 2026-07-31.

| Package | Hiện tại | Đích | Rủi ro | Ghi chú |
|---|---|---|---|---|
| `laravel/framework` | 5.5.50 | 13.x | — | |
| `yajra/laravel-oci8` | 5.5 | 13.x | Thấp | Hỗ trợ L13 |
| `yajra/laravel-datatables-oracle` | 8.3 | 13.x | Trung bình | Còn `vendor/datatables/` tự sửa phải rà |
| `yajra/laravel-datatables-buttons` | 4.13 | bản hỗ trợ L13 | Thấp | |
| `santigarcor/laratrust` | 5.0 | 8.5.5 | **Cao** | Đổi trait + cấu trúc config; xem mục 5.1 |
| `jeroennoten/laravel-adminlte` | 1.22 | 3.16 | **Cao nhất** | Pha riêng; xem mục 5.2 |
| `tymon/jwt-auth` | 1.0 | 2.3 | Thấp | Giữ `JWT_SECRET` ⇒ token đang phát hành vẫn hợp lệ |
| `spatie/laravel-activitylog` | 3.1 | 5.0 | Thấp | Xem mục 5.3 |
| `maatwebsite/excel` | 3.1 | 3.1 (mới nhất) | Thấp | Không đổi major |
| `rap2hpoutre/fast-excel` | 3.2 | 5.x | Thấp | |
| `diglactic/laravel-breadcrumbs` | 4.2 | 10.x | Thấp | |
| `hisorange/browser-detect` | 4.5 | 5.x | Thấp | |
| `guzzlehttp/guzzle` | 6.5 | 7.x | Thấp | Nhiều service tích hợp dùng; API tương thích phần lớn |
| `dompdf/dompdf` | 2.0 | 3.x | Thấp | Kiểm lại font tiếng Việt |
| `predis/predis` | 1.1 | 2.x | Thấp | |
| `laravel/tinker` | 1.0 | 2.x | Thấp | |
| `setasign/fpdf`, `setasign/fpdi`, `picqer/php-barcode-generator`, `simplesoftwareio/simple-qrcode`, `phpoffice/phpspreadsheet`, `laracasts/flash` | — | bản hỗ trợ L13 | Thấp | |
| `orchestra/parser` | 3.5 | **gỡ bỏ** | — | 0 chỗ dùng |
| `fideloper/proxy` | 3.3 | **gỡ bỏ** | — | Đã vào core |
| `egulias/email-validator` | 3.1 | **gỡ khỏi require** | — | Để composer tự kéo theo |
| `pusher/pusher-php-server` | 3.2 | **gỡ bỏ** | — | QĐ-7 |
| `phpunit/phpunit` (dev) | 6 | 11/12 | Trung bình | Xem mục 6.1 |
| `mockery/mockery` (dev) | 0.9 | 1.6+ | Trung bình | Mockery mới nghiêm ngặt với return type |

### 5.1 Laratrust 5.0 → 8.5.5

Đổi tên trait và cấu trúc `config/laratrust.php`.

**Ràng buộc tuyệt đối: không chạy migration của Laratrust.** Bảng phân quyền đã có dữ liệu thật. Cách làm: khai báo lại tên bảng/khoá hiện hữu trong config mới để package đọc đúng schema đang có. Nếu schema mới đòi cột không tồn tại, thêm bằng migration tự viết, kèm kịch bản lùi.

Nghiệm thu: mỗi vai trò đăng nhập thấy đúng menu và bị chặn đúng chỗ như bản cũ.

### 5.2 AdminLTE 1.22 → 3.16 (Pha 5)

AdminLTE 3 đổi hoàn toàn cấu trúc view và component so với 1.x. Khối lượng: 130 blade dùng `adminlte::` cộng toàn bộ `resources/views/vendor/adminlte/` tự sửa phải bỏ hoặc viết lại.

Đây là loại rủi ro **smoke test không bắt được** (trang vẫn trả 200 nhưng hiển thị sai), nên bắt buộc nghiệm thu giao diện thủ công theo danh sách màn hình, do người dùng nghiệp vụ thực hiện. Vì lý do đó nó là pha riêng, chỉ bắt đầu khi Pha 4 đã xanh.

### 5.3 spatie/laravel-activitylog 3.1 → 5.0

Bảng `activity_log` đổi cấu trúc (thêm `batch_uuid`, `event`, thuộc tính kiểu JSON). Bảng này chỉ dùng làm nhật ký kỹ thuật, không phục vụ tra cứu nghiệp vụ.

Cách xử lý: **tạo bảng mới theo schema 5.0 với tên khác, giữ nguyên bảng cũ**. Không di trú dữ liệu. Cách này thoả QĐ-10 (rollback sạch).

## 6. Kiểm thử và đối chiếu

### 6.1 Chuẩn nền

Chạy toàn bộ test hiện có trên XAMPP và **ghi lại bằng văn bản trạng thái đỏ/xanh hôm nay**. Repo đang có test đỏ sẵn; không chốt mốc nền thì sau khi nâng không phân biệt được "đỏ do nâng cấp" hay "đỏ từ trước".

Nâng PHPUnit 6 → 11/12 là việc cơ giới nhưng trải rộng: 794 chỗ annotation `@test` (chuyển sang attribute `#[Test]`), 10 `setUp()` thiếu `: void`, 6 file dùng Mockery. Thực hiện chủ yếu bằng script thay thế hàng loạt, rà tay phần còn lại.

### 6.2 Smoke test toàn route

Một test duyệt 439 route web + 28 route API, đăng nhập bằng tài khoản có quyền đầy đủ, gọi mọi route GET không đòi tham số bắt buộc, khẳng định không trả 500.

**Viết và chạy trên bản 5.5 trước** để lấy danh sách route vốn đã hỏng sẵn. Sau khi nâng, chỉ route hỏng thêm mới tính là hồi quy.

### 6.3 Bốn test chốt hành vi (kiểu tệp mẫu)

Chạy trên bản cũ, lưu kết quả làm mẫu, so khớp trên bản mới:

1. Sinh XML 3176 / QĐ130 và bộ checker — so khớp chính xác từng ký tự, vì đây là dữ liệu gửi lên BHXH.
2. Số liệu giao ban.
3. Đăng nhập và phân quyền Laratrust theo từng vai trò.
4. Một file Excel và một file PDF xuất ra.

### 6.4 Ràng buộc an toàn khi chạy song song

- Container mới **không được trỏ vào MySQL `qlbv` production** — dùng bản sao.
- Oracle HIS: kết nối bằng tài khoản **chỉ đọc**.
- Nhóm tích hợp ngoài (BHXH, cổng Điện Biên, Trục dữ liệu, ký số, SMS): chạy ở chế độ **chặn gửi**, chỉ ghi payload ra file để đối chiếu.

### 6.5 Cắt chuyển và rollback

XAMPP chạy production tới phút cuối. Cắt chuyển là đổi định tuyến (nginx/cổng) sang container; giữ XAMPP ở trạng thái chờ ít nhất một tuần.

Rollback là đổi định tuyến ngược lại. Không cần khôi phục dữ liệu vì schema không đổi (QĐ-10).

Tiêu chí cho phép cắt chuyển:
- Smoke test không có route hỏng thêm so với chuẩn nền.
- Bốn test chốt hành vi khớp.
- Người dùng nghiệp vụ nghiệm thu xong giao diện AdminLTE 3.
- Toàn bộ tác vụ Task Scheduler đã được liệt kê lại và chạy đúng trong container `scheduler`.

## 7. Trình tự các pha

| Pha | Nội dung | Điều kiện qua |
|---|---|---|
| 0 | Spike hạ tầng: `oci8` trên PHP 8.x + Oracle 19c trong Docker; kiểm đường dẫn file dùng chung, NLS_LANG, múi giờ, font tiếng Việt | Kết nối được Oracle 19c, đọc được bảng thật |
| 1 | Dọn dẹp trên bản 5.5: xoá vaccination, sarcov2, pusher; gỡ `orchestra/parser`, `fideloper/proxy` | Ứng dụng cũ vẫn chạy đúng |
| 2 | Chuẩn nền + smoke test + 4 test chốt hành vi (viết trên 5.5) | Có mốc so sánh bằng văn bản |
| 3 | Hạ tầng Docker: image PHP 8.x + oci8 + pdo_mysql, compose, container `queue` và `scheduler` | **Bản 5.5 nguyên trạng chạy được trong Docker** |
| 4 | Nâng lên Laravel 13 + toàn bộ package trừ AdminLTE, theo 10 mốc mục 4.4 | Smoke test không hồi quy; 4 test chốt khớp |
| 5 | Nâng AdminLTE 1.22 → 3.16 | Nghiệm thu giao diện thủ công |
| 6 | Cắt chuyển production + theo dõi | Chạy ổn 1 tuần thì mới gỡ XAMPP |
| — | *(ngoài phạm vi)* Chuyển `qlbv` sang Oracle | Dự án riêng sau này (QĐ-6) |

Pha 3 cố ý đưa **bản 5.5 nguyên trạng** vào Docker chạy được trước khi nâng, để nếu Docker trục trặc thì tách bạch được với lỗi do nâng cấp.

## 8. Rủi ro đã nhận diện

| Rủi ro | Mức | Cách xử lý |
|---|---|---|
| Không dựng được `oci8` trên PHP 8.x hoặc không kết nối được Oracle 19c | Chặn toàn bộ dự án | Pha 0 làm trước tiên; hỏng thì dừng và tính lại đích đến |
| Danh sách tác vụ Task Scheduler chỉ nằm ngoài mã nguồn | Cao — dễ bỏ sót âm thầm | Liệt kê thủ công ở Pha 3, đưa vào mã, đối chiếu với máy production |
| AdminLTE 3 làm sai hiển thị mà test không bắt được | Cao | Pha riêng + nghiệm thu thủ công theo danh sách màn hình |
| Laratrust 8 không khớp schema phân quyền hiện có | Cao | Cấu hình lại tên bảng, cấm chạy migration của package |
| Đường dẫn file dùng chung (FileCopyService, FtpService, lưu PDF) không truy cập được từ container | Trung bình | Kiểm ở Pha 0 |
| Gửi sai dữ liệu lên cổng BHXH trong lúc thử nghiệm | Nghiêm trọng nếu xảy ra | Chế độ chặn gửi, chỉ ghi payload; tài khoản Oracle chỉ đọc; MySQL dùng bản sao |
| Guzzle 6 → 7 làm đổi hành vi các tích hợp ngoài | Trung bình | Đối chiếu payload ở mốc 8 |

## 9. Ngoài phạm vi

- Chuyển DB `qlbv` từ MySQL sang Oracle (QĐ-6). Khi làm sẽ là triển khai mới, không di trú dữ liệu cũ. Công việc đã khảo sát sẵn cho lần đó: 147 migration cần rà (`text`→CLOB không index/`GROUP BY` được, `boolean`→`NUMBER(1)`, `increments`→sequence+trigger, chuỗi rỗng `''` trong Oracle chính là `NULL`), và viết lại SQL thô trong 69 file (`NOW()` 127 chỗ, `DATE_FORMAT` 72 chỗ, `IF()` 49 chỗ).
- Làm mới tính năng nghiệp vụ.
- Tối ưu hiệu năng ngoài phần PHP 8 mang lại sẵn.
- Tách module sang NestJS (dự án riêng đang có kế hoạch khác).
