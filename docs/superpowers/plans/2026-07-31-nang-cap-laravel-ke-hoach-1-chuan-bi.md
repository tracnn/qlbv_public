# Kế hoạch 1: Chuẩn bị nâng cấp Laravel (Pha 0–3)

> **Dành cho người thực thi (kể cả agent):** BẮT BUỘC dùng skill `superpowers:subagent-driven-development` (khuyến nghị) hoặc `superpowers:executing-plans` để thực thi từng task một. Các bước dùng cú pháp checkbox `- [ ]` để theo dõi.

**Đặc tả gốc:** [docs/superpowers/specs/2026-07-31-nang-cap-laravel-design.md](../specs/2026-07-31-nang-cap-laravel-design.md)

**Mục tiêu:** Đưa bản Laravel 5.5 **nguyên trạng** chạy được trong Docker, sau khi đã xoá mã chết và dựng xong lưới an toàn (smoke test + test chốt hành vi) — tạo mọi điều kiện để Kế hoạch 2 nâng lên Laravel 13.

**Kiến trúc:** Bốn pha nối tiếp. Pha 0 kiểm chứng rào cản kỹ thuật lớn nhất (oci8 trên PHP 8 + Oracle 19c) trước khi tiêu tốn công sức. Pha 1 xoá phân hệ chết để giảm khối lượng phải port. Pha 2 dựng lưới an toàn **trên bản 5.5** để có mốc so sánh. Pha 3 Docker hoá bằng Dockerfile tham số hoá theo `ARG PHP_VERSION`, build biến thể 7.4 để bản 5.5 chạy được — Pha 4 (kế hoạch sau) chỉ việc đổi tham số sang 8.x.

**Công nghệ:** Laravel 5.5.50, PHP 7.4 (XAMPP hiện tại), PHPUnit 6, Docker + docker-compose, Oracle Instant Client 23.x + extension oci8, MySQL 8, Redis, nginx.

## Ràng buộc toàn cục

Mọi task đều phải tuân thủ, không cần nhắc lại trong từng task:

- **Không đổi schema DB đang dùng.** Không chạy `php artisan migrate` lên DB production trong toàn bộ kế hoạch này.
- **Không sửa logic nghiệp vụ.** Kế hoạch này chỉ xoá mã chết, thêm test, thêm hạ tầng. Bất kỳ thay đổi hành vi nào là lỗi kế hoạch.
- **Container/test không được trỏ vào MySQL `qlbv` production** — dùng bản sao. Oracle HIS kết nối bằng tài khoản **chỉ đọc**.
- **Tích hợp ngoài chạy chế độ chặn gửi** (BHXH, cổng Điện Biên, Trục dữ liệu, ký số, SMS): không phát sinh lời gọi thật ra ngoài trong test.
- **Nhánh làm việc:** `upgrade/laravel-13`. Tạo từ `main` ở Task 1. Không commit thẳng lên `main`.
- **Ngôn ngữ:** thông điệp commit và tài liệu viết tiếng Việt không dấu cho commit message, có dấu cho tài liệu — theo đúng lệ hiện có của repo.
- **Chạy test:** `php vendor/bin/phpunit` (PHP 7.4 của XAMPP). Không dùng `php artisan test` (Laravel 5.5 không có lệnh này).
- Phiên bản Oracle server: **19c**. Instant Client dùng bản **23.x** (tương thích ngược tới 19c).

---

## Cấu trúc file

**Tạo mới:**

| File | Trách nhiệm |
|---|---|
| `docs/superpowers/notes/2026-07-31-spike-oci8-php8.md` | Kết quả Pha 0: phiên bản PHP chốt được, cách build oci8, các vấn đề gặp phải |
| `docker/php/Dockerfile` *(viết lại)* | Image ứng dụng, tham số hoá `ARG PHP_VERSION` |
| `docker/php/php.ini` *(sửa)* | Cấu hình PHP + múi giờ + NLS_LANG |
| `docker-compose.yml` *(viết lại)* | 5 service: app, nginx, redis, queue, scheduler |
| `docker/scheduler/entrypoint.sh` | Vòng lặp gọi `artisan schedule:run` mỗi phút |
| `tests/Feature/SmokeAllRoutesTest.php` | Duyệt mọi route GET, khẳng định không 500 |
| `tests/Support/smoke-baseline.json` | Danh sách route vốn đã hỏng trước khi nâng cấp |
| `tests/Feature/Golden/Xml3176ExportGoldenTest.php` | Chốt byte của XML gửi BHXH |
| `tests/Feature/Golden/GiaoBanSoLieuGoldenTest.php` | Chốt số liệu giao ban |
| `tests/Feature/Golden/PhanQuyenGoldenTest.php` | Chốt ma trận vai trò × route |
| `tests/Feature/Golden/XuatFileGoldenTest.php` | Chốt nội dung Excel (theo giá trị ô) và PDF (theo văn bản trích ra) |
| `tests/Support/golden/` | Thư mục chứa tệp mẫu |
| `docs/superpowers/notes/2026-07-31-chuan-nen-test.md` | Trạng thái đỏ/xanh của test trước khi nâng cấp |
| `docs/superpowers/notes/2026-07-31-tac-vu-task-scheduler.md` | Danh sách tác vụ định kỳ liệt kê từ máy production |

**Sửa:**

| File | Thay đổi |
|---|---|
| `composer.json` | Gỡ `orchestra/parser`, `fideloper/proxy`, `pusher/pusher-php-server` |
| `config/app.php` | Gỡ provider/alias của các package đã gỡ |
| `routes/web.php` | Xoá khối route vaccination (dòng 263–298) và 3 route sarcov2 |
| `routes/channels.php` | Xoá kênh broadcast |
| `app/Console/Kernel.php` | Nạp lịch tác vụ (Pha 3) |
| `app/Providers/EventServiceProvider.php` | Giữ `MedicalRegister`, bỏ các event broadcast |
| `app/Http/Controllers/AccountantController.php` | Gỡ 3 lời gọi `DemoPusherEvent` |
| `app/Http/Controllers/KHTH/KHTHController.php` | Gỡ lời gọi `DemoPusherEvent` và 3 action sarcov2 |
| `app/Jobs/JobBHYT.php`, `app/Jobs/JobInpatient.php` | Gỡ lời gọi event broadcast |
| 5 blade có mã Pusher | Gỡ khối JavaScript Pusher |

**Xoá:** liệt kê chi tiết trong từng task.

---

## Task 1: Pha 0 — Spike oci8 trên PHP 8 với Oracle 19c

Đây là **cổng chặn của cả dự án**. Nếu task này thất bại, dừng lại và thiết kế lại đích đến (hạ xuống Laravel 10 chạy PHP 8.1, hoặc giữ nguyên).

**Files:**
- Tạo: `docs/superpowers/notes/2026-07-31-spike-oci8-php8.md`
- Tạo tạm (xoá sau khi xong): `docker/spike/Dockerfile`

**Interfaces:**
- Sản phẩm cho task sau: phiên bản PHP chốt được (8.3 hay 8.4), phiên bản `oci8` tương ứng, danh sách gói hệ thống cần cài — Task 11 dùng lại nguyên xi.

- [ ] **Bước 1: Tạo nhánh làm việc**

```bash
git checkout -b upgrade/laravel-13
```

- [ ] **Bước 2: Tra phiên bản PHP mà Laravel 13 yêu cầu**

```bash
composer show --all laravel/framework 13.23.0 --no-ansi | grep -E "^php"
```

Ghi lại kết quả. Chọn **phiên bản PHP cao nhất mà `oci8` có bản phát hành ổn định** — kiểm bằng:

```bash
curl -s https://pecl.php.net/rest/r/oci8/allreleases.xml | grep -o "<v>[0-9.]*</v>"
```

- [ ] **Bước 3: Viết Dockerfile spike**

Tạo `docker/spike/Dockerfile`. Thay `8.3` bằng phiên bản chốt ở Bước 2, thay `3.4.0` bằng bản oci8 mới nhất:

```dockerfile
FROM php:8.3-cli

RUN apt-get update && apt-get install -y \
    libaio1 wget unzip \
    && rm -rf /var/lib/apt/lists/*

RUN mkdir -p /opt/oracle \
    && cd /opt/oracle \
    && wget -q https://download.oracle.com/otn_software/linux/instantclient/instantclient-basiclite-linuxx64.zip \
    && wget -q https://download.oracle.com/otn_software/linux/instantclient/instantclient-sdk-linuxx64.zip \
    && unzip -q -o instantclient-basiclite-linuxx64.zip \
    && unzip -q -o instantclient-sdk-linuxx64.zip \
    && mv instantclient_* instantclient \
    && rm -f *.zip \
    && echo /opt/oracle/instantclient > /etc/ld.so.conf.d/oracle-instantclient.conf \
    && ldconfig

ENV LD_LIBRARY_PATH=/opt/oracle/instantclient

RUN echo "instantclient,/opt/oracle/instantclient" | pecl install oci8-3.4.0 \
    && docker-php-ext-enable oci8

CMD ["php", "-m"]
```

- [ ] **Bước 4: Build và xác nhận extension nạp được**

```bash
docker build -f docker/spike/Dockerfile -t qlbv-spike docker/spike
docker run --rm qlbv-spike | grep -i oci8
```

Kỳ vọng: in ra `oci8`. Nếu `pecl install` báo lỗi biên dịch, thử bản oci8 kế trước; nếu vẫn hỏng, **dừng và báo cáo** — đây là điều kiện chặn.

- [ ] **Bước 5: Kết nối thật tới Oracle 19c**

Chuẩn bị tài khoản Oracle **chỉ đọc**. Chạy (thay `<user>`, `<pass>`, `<host>`, `<service>`, và `<BANG_THAT>` bằng một bảng HIS có dữ liệu):

```bash
docker run --rm qlbv-spike php -r '
$c = oci_connect("<user>", "<pass>", "<host>:1521/<service>", "AL32UTF8");
if (!$c) { $e = oci_error(); exit("LOI KET NOI: " . $e["message"] . PHP_EOL); }
$s = oci_parse($c, "select banner from v\$version where rownum = 1");
oci_execute($s); print_r(oci_fetch_assoc($s));
$s2 = oci_parse($c, "select count(*) as SL from <BANG_THAT> where rownum <= 10");
oci_execute($s2); print_r(oci_fetch_assoc($s2));
'
```

Kỳ vọng: in ra banner Oracle 19c và một số đếm. Đây là bằng chứng quyết định của Pha 0.

- [ ] **Bước 6: Kiểm tiếng Việt và múi giờ**

```bash
docker run --rm -e NLS_LANG=AMERICAN_AMERICA.AL32UTF8 -e TZ=Asia/Ho_Chi_Minh qlbv-spike php -r '
$c = oci_connect("<user>", "<pass>", "<host>:1521/<service>", "AL32UTF8");
$s = oci_parse($c, "select <COT_CO_TIENG_VIET> as V from <BANG_THAT> where rownum = 1");
oci_execute($s); $r = oci_fetch_assoc($s);
echo $r["V"] . PHP_EOL;
echo "mb_check_encoding UTF-8: " . var_export(mb_check_encoding($r["V"], "UTF-8"), true) . PHP_EOL;
echo date("Y-m-d H:i:s T") . PHP_EOL;
'
```

Kỳ vọng: chuỗi tiếng Việt hiển thị đúng dấu, `mb_check_encoding` trả `true`, giờ đúng giờ Việt Nam.

- [ ] **Bước 7: Kiểm truy cập thư mục dùng chung**

Xác định các đường dẫn mà `FileCopyService`, `FtpService` và phần lưu PDF đang dùng trên XAMPP:

```bash
grep -rn "storage_path\|base_path\|\\\\\\\\\|[A-Z]:" config/organization.php config/signing.php app/Services/FileCopyService.php app/Services/FtpService.php | head -30
```

Với mỗi đường dẫn UNC hoặc ổ đĩa Windows tìm được, thử mount vào container và đọc thử:

```bash
docker run --rm -v "//<may>/<thumuc>:/mnt/thu" qlbv-spike php -r 'var_dump(is_readable("/mnt/thu"), scandir("/mnt/thu"));'
```

Ghi lại đường dẫn nào mount được, đường dẫn nào không.

- [ ] **Bước 8: Viết báo cáo spike**

Tạo `docs/superpowers/notes/2026-07-31-spike-oci8-php8.md` ghi: phiên bản PHP chốt, phiên bản oci8, phiên bản Instant Client, danh sách gói `apt` cần thiết, kết quả từng bước 4–7, và mọi trục trặc kèm cách khắc phục. Task 11 sẽ đọc file này.

- [ ] **Bước 9: Xoá Dockerfile spike và commit**

```bash
rm -rf docker/spike
git add docs/superpowers/notes/2026-07-31-spike-oci8-php8.md
git commit -m "docs: ket qua spike oci8 tren PHP 8 voi Oracle 19c"
```

---

## Task 2: Pha 1 — Gỡ phân hệ vaccination

**Files:**
- Xoá: `app/Http/Controllers/Vaccination/` (4 file), `app/Vaccination.php`, `app/PreVaccinationCheck.php`, `app/Vaccine.php`, `resources/views/vaccination/` (12 file)
- Sửa: `routes/web.php` (khối dòng 263–298)
- Giữ nguyên: `database/migrations/2024_05_09_101459_create_vaccinations_table.php`, `database/migrations/2024_05_20_080159_create_pre_vaccination_checks_table.php` — **không xoá migration** vì bảng đã tồn tại trên DB; xoá file migration sẽ làm lịch sử migration lệch

**Interfaces:**
- Sản phẩm: không còn tham chiếu `Vaccination\` hay `App\Vaccination` trong toàn repo.

- [ ] **Bước 1: Ghi lại danh sách route trước khi xoá**

```bash
php artisan route:list --json > /tmp/routes-truoc.json 2>/dev/null || php artisan route:list > /tmp/routes-truoc.txt
```

*(Ghi chú: repo này từng gặp lỗi `route:list` chết. Nếu lệnh hỏng, bỏ qua bước này và dùng `grep -c "Route::" routes/web.php` làm mốc thay thế.)*

- [ ] **Bước 2: Xoá khối route vaccination**

Mở `routes/web.php`, xoá toàn bộ khối từ comment `Vaccination` (khoảng dòng 263) tới hết `Route::group` tương ứng (khoảng dòng 298). Kiểm tra bằng:

```bash
grep -niE "vaccinat" routes/web.php
```

Kỳ vọng: không còn dòng nào.

- [ ] **Bước 3: Xoá file mã nguồn và view**

```bash
rm -rf app/Http/Controllers/Vaccination resources/views/vaccination
rm -f app/Vaccination.php app/PreVaccinationCheck.php app/Vaccine.php
```

- [ ] **Bước 4: Tìm tham chiếu còn sót**

```bash
grep -rniE "Vaccination\\\\|App\\\\Vaccination|PreVaccinationCheck|App\\\\Vaccine|vaccination\\.|checkrole:vaccination" app routes resources config database --include=*.php --include=*.blade.php
```

Kỳ vọng: không có kết quả. Nếu còn (thường là mục menu trong `app/Menu/` hoặc blade sidebar), xoá luôn mục đó.

- [ ] **Bước 5: Xác nhận ứng dụng vẫn nạp được**

```bash
composer dump-autoload
php artisan config:clear && php artisan cache:clear
php -r 'require "vendor/autoload.php"; $app = require "bootstrap/app.php"; $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap(); echo "BOOT OK\n";'
```

Kỳ vọng: in `BOOT OK`.

- [ ] **Bước 6: Chạy test hiện có, so với trước khi sửa**

```bash
php vendor/bin/phpunit 2>&1 | tail -20
```

Kỳ vọng: số test đỏ **không tăng** so với lần chạy gần nhất. (Repo có sẵn test đỏ — xem Task 6.)

- [ ] **Bước 7: Commit**

```bash
git add -A
git commit -m "chore: go phan he vaccination khong con su dung"
```

---

## Task 3: Pha 1 — Gỡ phân hệ sarcov2

**Files:**
- Xoá: `app/Console/Commands/sarcov2.php`, `resources/views/khth/BNSarCov2Index.blade.php`
- Sửa: `routes/web.php` (dòng 629, 630, 632), `app/Http/Controllers/KHTH/KHTHController.php` (các action `BNSarCov2Index`, `getsarcov2`, `get_sarcov2_ct`)

**Interfaces:**
- Sản phẩm: không còn tham chiếu `sarcov` hay `SarCov` trong repo.

- [ ] **Bước 1: Xoá 3 dòng route**

Trong `routes/web.php`, xoá các dòng chứa `bn-sar-cov-2-index`, `get-sar-cov-2`, `get-sarcov2-ct`.

- [ ] **Bước 2: Xoá 3 action trong KHTHController**

```bash
grep -n "function BNSarCov2Index\|function getsarcov2\|function get_sarcov2_ct" app/Http/Controllers/KHTH/KHTHController.php
```

Xoá trọn ba phương thức này (từ dòng `public function` tới dấu `}` đóng tương ứng).

- [ ] **Bước 3: Xoá file lệnh và view**

```bash
rm -f app/Console/Commands/sarcov2.php resources/views/khth/BNSarCov2Index.blade.php
```

- [ ] **Bước 4: Tìm tham chiếu còn sót**

```bash
grep -rniE "sarcov|sar-cov|covid" app routes resources config --include=*.php --include=*.blade.php
```

Kỳ vọng: không có kết quả. Chú ý mục menu ở sidebar.

- [ ] **Bước 5: Xác nhận boot và test**

```bash
composer dump-autoload && php artisan config:clear
php -r 'require "vendor/autoload.php"; $app = require "bootstrap/app.php"; $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap(); echo "BOOT OK\n";'
php vendor/bin/phpunit 2>&1 | tail -20
```

Kỳ vọng: `BOOT OK`, số test đỏ không tăng.

- [ ] **Bước 6: Commit**

```bash
git add -A
git commit -m "chore: go phan he sarcov2 khong con su dung"
```

---

## Task 4: Pha 1 — Gỡ Pusher và broadcasting

Cẩn thận: event `MedicalRegister` **phải giữ lại** vì có listener gửi email (`SendMailMedicalRegister`). Chỉ bỏ phần broadcast của nó.

**Files:**
- Xoá: `app/Events/DemoPusherEvent.php`, `app/Events/CheckInpatientEvent.php`, `app/Events/KtTheBHYTEvent.php`, `resources/views/includes/pusher-chanel.blade.php`, `resources/views/emr/broadcast/index.blade.php`
- Sửa: `app/Events/MedicalRegister.php`, `app/Http/Controllers/AccountantController.php` (dòng ~16, 33–35, 110–112, 233–235), `app/Http/Controllers/KHTH/KHTHController.php` (dòng ~1004), `app/Jobs/JobBHYT.php` (dòng ~90), `app/Jobs/JobInpatient.php` (dòng ~85), `routes/channels.php`, `composer.json`, `config/app.php`
- Sửa blade: `resources/views/bhyt/check-card.blade.php`, `resources/views/insurance/manager/check-entered/index.blade.php`

**Interfaces:**
- Sản phẩm: không còn `Pusher`, `ShouldBroadcast`, `ShouldBroadcastNow` trong repo; event `MedicalRegister` vẫn kích hoạt listener gửi mail.

- [ ] **Bước 1: Bỏ giao diện broadcast khỏi MedicalRegister**

Trong `app/Events/MedicalRegister.php`: xoá hai dòng `use Illuminate\Contracts\Broadcasting\ShouldBroadcast;` / `ShouldBroadcastNow;`, xoá phần `implements ShouldBroadcast...` ở khai báo class, và xoá phương thức `broadcastOn()` nếu có. Giữ nguyên constructor và các thuộc tính — listener gửi mail phụ thuộc vào chúng.

- [ ] **Bước 2: Viết test khẳng định listener gửi mail vẫn chạy**

Tạo `tests/Feature/MedicalRegisterEventTest.php`:

```php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\Event;
use App\Events\MedicalRegister;
use App\Listeners\SendMailMedicalRegister;

class MedicalRegisterEventTest extends TestCase
{
    /** @test */
    public function su_kien_dang_ky_kham_van_gan_voi_listener_gui_mail()
    {
        $anhXa = app('events')->getListeners(MedicalRegister::class);

        $this->assertNotEmpty($anhXa, 'Su kien MedicalRegister phai con it nhat mot listener');
    }

    /** @test */
    public function su_kien_dang_ky_kham_khong_con_la_su_kien_broadcast()
    {
        $lop = new \ReflectionClass(MedicalRegister::class);

        $this->assertFalse(
            $lop->implementsInterface(\Illuminate\Contracts\Broadcasting\ShouldBroadcast::class),
            'MedicalRegister khong duoc broadcast nua'
        );
    }
}
```

- [ ] **Bước 3: Chạy test, xác nhận nó xanh**

```bash
php vendor/bin/phpunit --filter MedicalRegisterEventTest
```

Kỳ vọng: 2 test PASS. (Test này chốt rằng bước 1 làm đúng: bỏ broadcast nhưng giữ listener.)

- [ ] **Bước 4: Gỡ các lời gọi event broadcast**

Xoá các dòng sau (và biến `$channelPusher`/`$jsonData` nếu chỉ dùng cho chúng):

- `app/Http/Controllers/AccountantController.php`: phương thức `broadcast()` (dòng ~16) và 3 chỗ `event(new \App\Events\DemoPusherEvent(...))`
- `app/Http/Controllers/KHTH/KHTHController.php` dòng ~1004
- `app/Jobs/JobBHYT.php` dòng ~90
- `app/Jobs/JobInpatient.php` dòng ~85
- `app/Http/Controllers/BHYT/BHYTController.php` dòng ~309 (đang là comment — xoá luôn)

- [ ] **Bước 5: Xoá các lớp event và view chỉ phục vụ Pusher**

```bash
rm -f app/Events/DemoPusherEvent.php app/Events/CheckInpatientEvent.php app/Events/KtTheBHYTEvent.php
rm -f resources/views/includes/pusher-chanel.blade.php
rm -rf resources/views/emr/broadcast
```

- [ ] **Bước 6: Gỡ mã JavaScript Pusher khỏi 2 blade còn lại**

Trong `resources/views/bhyt/check-card.blade.php` và `resources/views/insurance/manager/check-entered/index.blade.php`: xoá khối `<script>` khởi tạo `new Pusher(...)` cùng các `channel.bind(...)`. **Lưu ý bảo mật:** hai file này đang nhúng thẳng app key `32ba995928282d3d2fce` vào mã nguồn — sau khi xoá, đề nghị chủ dự án thu hồi key đó trên bảng điều khiển Pusher, vì nó đã nằm trong lịch sử git.

Tìm mọi chỗ `@include('includes.pusher-chanel')` và xoá:

```bash
grep -rn "pusher-chanel\|emr.broadcast" resources/views routes app
```

- [ ] **Bước 7: Dọn cấu hình broadcasting**

Trong `routes/channels.php`, xoá khối `Broadcast::channel('App.User.{id}', ...)`, giữ lại file rỗng có comment.

Trong `composer.json`, xoá dòng `"pusher/pusher-php-server": "^3.2",`.

Trong `config/app.php`, xoá `BroadcastServiceProvider` khỏi mảng providers nếu có.

```bash
composer update --lock --no-scripts
composer dump-autoload
```

- [ ] **Bước 8: Tìm tham chiếu còn sót**

```bash
grep -rniE "pusher|ShouldBroadcast|Broadcast::" app routes resources config composer.json --include=*.php --include=*.blade.php --include=*.json
```

Kỳ vọng: chỉ còn `config/broadcasting.php` (giữ nguyên, driver mặc định `log`) và `env.docker`.

- [ ] **Bước 9: Chạy toàn bộ test**

```bash
php vendor/bin/phpunit 2>&1 | tail -20
```

Kỳ vọng: số test đỏ không tăng; `MedicalRegisterEventTest` xanh.

- [ ] **Bước 10: Commit**

```bash
git add -A
git commit -m "chore: go pusher va broadcasting, giu listener gui mail dang ky kham"
```

---

## Task 5: Pha 1 — Gỡ orchestra/parser và fideloper/proxy

**Files:**
- Sửa: `composer.json`, `config/app.php`, `app/Http/Middleware/TrustProxies.php` *(chỉ khi nó kế thừa từ fideloper)*

**Interfaces:**
- Sản phẩm: `composer.json` không còn hai package này; ứng dụng vẫn boot.

- [ ] **Bước 1: Xác nhận orchestra/parser thật sự không được dùng**

```bash
grep -rniE "Orchestra\\\\Parser|XmlParser|orchestra" app routes config resources database tests --include=*.php --include=*.blade.php
```

Kỳ vọng: không có kết quả. Nếu có, **dừng lại** và báo cáo — quyết định QĐ-8 dựa trên giả định này.

- [ ] **Bước 2: Kiểm TrustProxies có phụ thuộc fideloper không**

```bash
cat app/Http/Middleware/TrustProxies.php
```

Nếu nó `extends Fideloper\Proxy\TrustProxies`, thay bằng phiên bản độc lập (Laravel 5.5 chưa có `Illuminate\Http\Middleware\TrustProxies`, nên **giữ nguyên `fideloper/proxy` ở kế hoạch này** và chỉ gỡ ở Kế hoạch 2, khi đã lên Laravel 13 — ghi chú lại vào commit message và bỏ qua bước 3 cho package này).

- [ ] **Bước 3: Gỡ package khỏi composer.json**

Xoá dòng `"orchestra/parser": "^3.5",` (và `"fideloper/proxy": "~3.3",` **chỉ khi** bước 2 xác định không còn phụ thuộc).

```bash
composer update --lock --no-scripts
composer dump-autoload
```

- [ ] **Bước 4: Gỡ provider/alias tương ứng trong config/app.php**

```bash
grep -n "Orchestra\|Fideloper" config/app.php
```

Xoá các dòng tìm được.

- [ ] **Bước 5: Xác nhận boot và test**

```bash
php artisan config:clear
php -r 'require "vendor/autoload.php"; $app = require "bootstrap/app.php"; $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap(); echo "BOOT OK\n";'
php vendor/bin/phpunit 2>&1 | tail -20
```

Kỳ vọng: `BOOT OK`, số test đỏ không tăng.

- [ ] **Bước 6: Commit**

```bash
git add -A
git commit -m "chore: go orchestra/parser khong su dung khoi phu thuoc"
```

---

## Task 6: Pha 2 — Chốt chuẩn nền test

Repo **đang có test đỏ sẵn**. Không chốt mốc này thì sau khi nâng cấp sẽ không phân biệt được "đỏ do nâng cấp" hay "đỏ từ trước".

**Files:**
- Tạo: `docs/superpowers/notes/2026-07-31-chuan-nen-test.md`

**Interfaces:**
- Sản phẩm: file ghi rõ tên từng test đỏ hiện tại. Kế hoạch 2 dùng file này làm mốc so sánh.

- [ ] **Bước 1: Chạy toàn bộ test, ghi ra file**

```bash
php vendor/bin/phpunit --testdox 2>&1 | tee /tmp/chuan-nen.txt
```

- [ ] **Bước 2: Trích danh sách test đỏ**

```bash
php vendor/bin/phpunit 2>&1 | grep -E "^[0-9]+\)" | tee /tmp/test-do.txt
php vendor/bin/phpunit 2>&1 | tail -5
```

- [ ] **Bước 3: Viết tài liệu chuẩn nền**

Tạo `docs/superpowers/notes/2026-07-31-chuan-nen-test.md` gồm: ngày chạy, phiên bản PHP/PHPUnit, tổng số test, số xanh/đỏ/bỏ qua, **danh sách đầy đủ tên test đỏ kèm thông điệp lỗi rút gọn**, và một câu kết luận cho mỗi test đỏ: "đỏ từ trước, không phải hồi quy".

- [ ] **Bước 4: Commit**

```bash
git add docs/superpowers/notes/2026-07-31-chuan-nen-test.md
git commit -m "docs: chot chuan nen trang thai test truoc khi nang cap"
```

---

## Task 7: Pha 2 — Smoke test toàn route

Lưới an toàn chính. Bắt các lỗi "class không tồn tại / helper bị xoá / chữ ký hàm đổi" — dạng lỗi chủ đạo khi nâng framework.

**Files:**
- Tạo: `tests/Feature/SmokeAllRoutesTest.php`
- Tạo: `tests/Support/smoke-baseline.json`

**Interfaces:**
- Consumes: mẫu `FakeAdminUser` trong `tests/Feature/OnTimeResultControllerTest.php`
- Produces: `tests/Support/smoke-baseline.json` — bản đồ `"METHOD uri" => mã HTTP`. Kế hoạch 2 chạy lại chính test này và so với file đó.

- [ ] **Bước 1: Viết test smoke**

Tạo `tests/Feature/SmokeAllRoutesTest.php`:

```php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\Route;

/**
 * Duyet moi route GET khong doi tham so, khang dinh khong tra ve loi 500.
 *
 * Chay lai voi bien moi truong SMOKE_WRITE_BASELINE=1 de ghi lai chuan nen:
 *   SMOKE_WRITE_BASELINE=1 php vendor/bin/phpunit --filter SmokeAllRoutesTest
 *
 * Chuan nen ghi lai nhung route VON DA HONG truoc khi nang cap, de sau khi
 * nang chi nhung route hong THEM moi bi tinh la hoi quy.
 */
class SmokeAllRoutesTest extends TestCase
{
    const DUONG_DAN_CHUAN_NEN = __DIR__ . '/../Support/smoke-baseline.json';

    /** @test */
    public function khong_route_get_nao_hong_them_so_voi_chuan_nen()
    {
        $nguoiDung = $this->taoNguoiDungQuanTri();
        $ketQua = [];

        foreach (Route::getRoutes() as $route) {
            if (! in_array('GET', $route->methods(), true)) {
                continue;
            }

            $uri = $route->uri();

            // Bo route can tham so - khong doan duoc gia tri hop le.
            if (strpos($uri, '{') !== false) {
                continue;
            }

            // Bo route tich hop ngoai de khong phat sinh loi goi that.
            if (preg_match('#(telescope|horizon|_debugbar|logout|dang-xuat)#i', $uri)) {
                continue;
            }

            $khoa = 'GET /' . ltrim($uri, '/');

            try {
                $phanHoi = $this->actingAs($nguoiDung)->get('/' . ltrim($uri, '/'));
                $ketQua[$khoa] = $phanHoi->getStatusCode();
            } catch (\Throwable $e) {
                $ketQua[$khoa] = 500;
            }
        }

        $this->assertNotEmpty($ketQua, 'Phai duyet duoc it nhat mot route');

        if (getenv('SMOKE_WRITE_BASELINE')) {
            ksort($ketQua);
            file_put_contents(
                self::DUONG_DAN_CHUAN_NEN,
                json_encode($ketQua, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n"
            );
            $this->assertTrue(true, 'Da ghi chuan nen');

            return;
        }

        $this->assertFileExists(
            self::DUONG_DAN_CHUAN_NEN,
            'Chua co chuan nen. Chay lai voi SMOKE_WRITE_BASELINE=1 truoc.'
        );

        $chuanNen = json_decode(file_get_contents(self::DUONG_DAN_CHUAN_NEN), true);
        $hongThem = [];

        foreach ($ketQua as $khoa => $ma) {
            $maCu = isset($chuanNen[$khoa]) ? $chuanNen[$khoa] : null;

            if ($ma >= 500 && $maCu !== null && $maCu < 500) {
                $hongThem[] = sprintf('%s: chuan nen %d -> hien tai %d', $khoa, $maCu, $ma);
            }
        }

        $this->assertSame([], $hongThem, "Co route hong them so voi chuan nen:\n" . implode("\n", $hongThem));
    }

    /**
     * Nguoi dung gia thoa middleware checkrole ma khong truy van bang roles.
     * Cung ky thuat voi FakeAdminUser trong OnTimeResultControllerTest.
     */
    protected function taoNguoiDungQuanTri()
    {
        $nguoiDung = new SmokeAdminUser();
        $nguoiDung->id = 1;

        return $nguoiDung;
    }
}

class SmokeAdminUser extends \App\User
{
    public function hasRole($role, $team = null, $requireAll = false)
    {
        return true;
    }

    public function can($permission, $team = null, $requireAll = false)
    {
        return true;
    }
}
```

- [ ] **Bước 2: Chạy để xác nhận test thất bại đúng lý do**

```bash
php vendor/bin/phpunit --filter SmokeAllRoutesTest
```

Kỳ vọng: FAIL với thông điệp `Chua co chuan nen. Chay lai voi SMOKE_WRITE_BASELINE=1 truoc.` — chứng tỏ test chạy được và cơ chế chuẩn nền hoạt động.

- [ ] **Bước 3: Sinh chuẩn nền**

```bash
SMOKE_WRITE_BASELINE=1 php vendor/bin/phpunit --filter SmokeAllRoutesTest
```

Trên PowerShell:

```bash
$env:SMOKE_WRITE_BASELINE=1; php vendor/bin/phpunit --filter SmokeAllRoutesTest; Remove-Item Env:\SMOKE_WRITE_BASELINE
```

- [ ] **Bước 4: Xem lại chuẩn nền và ghi nhận số route đã hỏng sẵn**

```bash
php -r '$d = json_decode(file_get_contents("tests/Support/smoke-baseline.json"), true);
echo "Tong route duyet: " . count($d) . PHP_EOL;
$hong = array_filter($d, function ($m) { return $m >= 500; });
echo "Route dang hong (>=500): " . count($hong) . PHP_EOL;
print_r(array_keys($hong));'
```

Ghi con số này vào `docs/superpowers/notes/2026-07-31-chuan-nen-test.md` (mục mới "Smoke test").

- [ ] **Bước 5: Chạy lại ở chế độ so sánh, xác nhận xanh**

```bash
php vendor/bin/phpunit --filter SmokeAllRoutesTest
```

Kỳ vọng: PASS.

- [ ] **Bước 6: Commit**

```bash
git add tests/Feature/SmokeAllRoutesTest.php tests/Support/smoke-baseline.json docs/superpowers/notes/2026-07-31-chuan-nen-test.md
git commit -m "test: them smoke test toan route va chot chuan nen"
```

---

## Task 8: Pha 2 — Test chốt hành vi: XML gửi BHXH

Luồng có hậu quả nghiêm trọng nhất nếu sai. So khớp **chính xác từng ký tự**.

**Files:**
- Tạo: `tests/Feature/Golden/Xml3176ExportGoldenTest.php`
- Tạo: `tests/Support/golden/` (thư mục chứa tệp mẫu)

**Interfaces:**
- Consumes: `App\Services\Xml3176Service::getDataForXmlExport($selectedRecord)`, `App\Services\XMLService`
- Produces: tệp mẫu `tests/Support/golden/xml3176-<ma_lk>.xml`. Kế hoạch 2 chạy lại đúng test này.

- [ ] **Bước 1: Chọn hồ sơ mẫu từ bản sao DB**

Chọn **3 mã `ma_lk`** có dữ liệu đầy đủ nhất (có cả thuốc, DVKT, chỉ định cận lâm sàng). Ghi 3 mã này vào hằng số trong test ở bước sau.

```bash
php artisan tinker --execute="echo json_encode(DB::table('xml3176_xml1')->orderBy('id','desc')->limit(10)->pluck('ma_lk'));"
```

*(Nếu tên bảng khác, tìm bằng: `grep -n "protected \$table" app/Models/BHYT/Xml3176Xml1.php`)*

- [ ] **Bước 2: Viết test chốt**

Tạo `tests/Feature/Golden/Xml3176ExportGoldenTest.php`:

```php
<?php

namespace Tests\Feature\Golden;

use Tests\TestCase;
use App\Services\Xml3176Service;

/**
 * Chot noi dung XML gui BHXH theo tung ky tu.
 *
 * Sinh lai tep mau (chi lam khi da xac nhan thay doi la CO Y):
 *   GOLDEN_WRITE=1 php vendor/bin/phpunit --filter Xml3176ExportGoldenTest
 */
class Xml3176ExportGoldenTest extends TestCase
{
    /** Thay bang 3 ma_lk that chon o Buoc 1. */
    const CAC_MA_LK = ['<MA_LK_1>', '<MA_LK_2>', '<MA_LK_3>'];

    const THU_MUC_MAU = __DIR__ . '/../../Support/golden';

    /** @test */
    public function du_lieu_xuat_xml3176_khong_doi_so_voi_tep_mau()
    {
        $dichVu = app(Xml3176Service::class);

        foreach (self::CAC_MA_LK as $maLk) {
            $duLieu = $dichVu->getDataForXmlExport($maLk);

            $this->assertNotEmpty($duLieu, "Khong lay duoc du lieu cho ma_lk {$maLk}");

            // Chuan hoa: sap xep khoa de thu tu tra ve tu DB khong lam vo so sanh.
            $chuanHoa = $this->sapXepDeQuy(json_decode(json_encode($duLieu), true));
            $noiDung = json_encode($chuanHoa, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";

            $duongDan = self::THU_MUC_MAU . "/xml3176-{$maLk}.json";

            if (getenv('GOLDEN_WRITE')) {
                if (! is_dir(self::THU_MUC_MAU)) {
                    mkdir(self::THU_MUC_MAU, 0777, true);
                }
                file_put_contents($duongDan, $noiDung);
                continue;
            }

            $this->assertFileExists($duongDan, "Chua co tep mau cho {$maLk}. Chay lai voi GOLDEN_WRITE=1.");
            $this->assertSame(
                file_get_contents($duongDan),
                $noiDung,
                "Du lieu xuat XML3176 cho ma_lk {$maLk} da thay doi so voi tep mau"
            );
        }

        $this->assertTrue(true);
    }

    private function sapXepDeQuy($gioTri)
    {
        if (! is_array($gioTri)) {
            return $gioTri;
        }

        foreach ($gioTri as $khoa => $con) {
            $gioTri[$khoa] = $this->sapXepDeQuy($con);
        }

        if (! array_key_exists(0, $gioTri)) {
            ksort($gioTri);
        }

        return $gioTri;
    }
}
```

- [ ] **Bước 3: Chạy để xác nhận thất bại đúng lý do**

```bash
php vendor/bin/phpunit --filter Xml3176ExportGoldenTest
```

Kỳ vọng: FAIL với `Chua co tep mau ... Chay lai voi GOLDEN_WRITE=1.`

- [ ] **Bước 4: Sinh tệp mẫu**

```bash
GOLDEN_WRITE=1 php vendor/bin/phpunit --filter Xml3176ExportGoldenTest
```

- [ ] **Bước 5: Kiểm mắt thường tệp mẫu**

```bash
ls -la tests/Support/golden/
head -40 tests/Support/golden/xml3176-*.json
```

Xác nhận có dữ liệu thật, tiếng Việt đúng dấu, không rỗng.

- [ ] **Bước 6: Chạy lại ở chế độ so khớp**

```bash
php vendor/bin/phpunit --filter Xml3176ExportGoldenTest
```

Kỳ vọng: PASS.

- [ ] **Bước 7: Commit**

```bash
git add tests/Feature/Golden/Xml3176ExportGoldenTest.php tests/Support/golden/
git commit -m "test: chot du lieu xuat XML3176 bang tep mau"
```

---

## Task 9: Pha 2 — Test chốt hành vi: số liệu giao ban

**Files:**
- Tạo: `tests/Feature/Golden/GiaoBanSoLieuGoldenTest.php`

**Interfaces:**
- Consumes: `App\Services\GiaoBan\GiaoBanReportService`
- Produces: tệp mẫu `tests/Support/golden/giaoban-<ngay>.json`

- [ ] **Bước 1: Tìm phương thức lấy số liệu báo cáo**

```bash
grep -n "public function" app/Services/GiaoBan/GiaoBanReportService.php
```

Chọn phương thức trả về số liệu tổng hợp của một phiên báo cáo (thường tên dạng `getReportData`/`laySoLieu`). Ghi lại tên và tham số.

- [ ] **Bước 2: Chọn một phiên báo cáo đã có dữ liệu**

```bash
php artisan tinker --execute="echo json_encode(DB::table('giao_ban_reports')->orderBy('id','desc')->limit(5)->get());"
```

*(Nếu tên bảng khác, tìm bằng `grep -rn 'protected \$table' app/Models | grep -i giaoban`)*

- [ ] **Bước 3: Viết test chốt**

Tạo `tests/Feature/Golden/GiaoBanSoLieuGoldenTest.php` — thay `<PHUONG_THUC>` và `<THAM_SO>` bằng kết quả bước 1–2:

```php
<?php

namespace Tests\Feature\Golden;

use Tests\TestCase;
use App\Services\GiaoBan\GiaoBanReportService;

class GiaoBanSoLieuGoldenTest extends TestCase
{
    const THU_MUC_MAU = __DIR__ . '/../../Support/golden';

    /** Thay bang id phien bao cao that chon o Buoc 2. */
    const ID_PHIEN = <ID_PHIEN_THAT>;

    /** @test */
    public function so_lieu_giao_ban_khong_doi_so_voi_tep_mau()
    {
        $dichVu = app(GiaoBanReportService::class);

        $soLieu = $dichVu-><PHUONG_THUC>(self::ID_PHIEN);

        $this->assertNotEmpty($soLieu, 'Khong lay duoc so lieu giao ban');

        $noiDung = json_encode(
            json_decode(json_encode($soLieu), true),
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        ) . "\n";

        $duongDan = self::THU_MUC_MAU . '/giaoban-' . self::ID_PHIEN . '.json';

        if (getenv('GOLDEN_WRITE')) {
            if (! is_dir(self::THU_MUC_MAU)) {
                mkdir(self::THU_MUC_MAU, 0777, true);
            }
            file_put_contents($duongDan, $noiDung);
            $this->assertTrue(true);

            return;
        }

        $this->assertFileExists($duongDan, 'Chua co tep mau. Chay lai voi GOLDEN_WRITE=1.');
        $this->assertSame(file_get_contents($duongDan), $noiDung, 'So lieu giao ban da thay doi so voi tep mau');
    }
}
```

- [ ] **Bước 4: Chạy, xác nhận thất bại đúng lý do**

```bash
php vendor/bin/phpunit --filter GiaoBanSoLieuGoldenTest
```

Kỳ vọng: FAIL với `Chua co tep mau`.

- [ ] **Bước 5: Sinh tệp mẫu và chạy lại**

```bash
GOLDEN_WRITE=1 php vendor/bin/phpunit --filter GiaoBanSoLieuGoldenTest
php vendor/bin/phpunit --filter GiaoBanSoLieuGoldenTest
```

Kỳ vọng: lần thứ hai PASS.

- [ ] **Bước 6: Commit**

```bash
git add tests/Feature/Golden/GiaoBanSoLieuGoldenTest.php tests/Support/golden/
git commit -m "test: chot so lieu giao ban bang tep mau"
```

---

## Task 10: Pha 2 — Test chốt hành vi: phân quyền và xuất file

Gộp hai luồng vào một task vì cùng dùng chung cơ chế tệp mẫu và cùng một vòng nghiệm thu.

**Files:**
- Tạo: `tests/Feature/Golden/PhanQuyenGoldenTest.php`
- Tạo: `tests/Feature/Golden/XuatFileGoldenTest.php`

**Interfaces:**
- Consumes: `App\User`, middleware `checkrole`, một lớp trong `app/Exports/`
- Produces: tệp mẫu `tests/Support/golden/phan-quyen.json` và `tests/Support/golden/xuat-excel.json`

- [ ] **Bước 1: Liệt kê các vai trò thật**

```bash
php artisan tinker --execute="echo json_encode(DB::table('roles')->pluck('name'));"
```

Chọn **4 vai trò** đại diện (ví dụ `administrator`, và 3 vai trò nghiệp vụ hay dùng nhất).

- [ ] **Bước 2: Viết test ma trận phân quyền**

Tạo `tests/Feature/Golden/PhanQuyenGoldenTest.php` — thay danh sách vai trò và 10 route đại diện:

```php
<?php

namespace Tests\Feature\Golden;

use Tests\TestCase;
use App\User;

/**
 * Chot ma tran vai tro x route: moi vai tro phai thay dung nhung gi duoc thay
 * va bi chan dung nhung cho bi chan, khong doi sau khi nang cap.
 */
class PhanQuyenGoldenTest extends TestCase
{
    const THU_MUC_MAU = __DIR__ . '/../../Support/golden';

    /** Thay bang 4 vai tro that chon o Buoc 1. */
    const CAC_VAI_TRO = ['<VAI_TRO_1>', '<VAI_TRO_2>', '<VAI_TRO_3>', '<VAI_TRO_4>'];

    /** 10 duong dan dai dien cho cac phan he chinh. */
    const CAC_DUONG_DAN = [
        '/home',
        '<DUONG_DAN_2>',
        '<DUONG_DAN_3>',
        '<DUONG_DAN_4>',
        '<DUONG_DAN_5>',
        '<DUONG_DAN_6>',
        '<DUONG_DAN_7>',
        '<DUONG_DAN_8>',
        '<DUONG_DAN_9>',
        '<DUONG_DAN_10>',
    ];

    /** @test */
    public function ma_tran_phan_quyen_khong_doi_so_voi_tep_mau()
    {
        $maTran = [];

        foreach (self::CAC_VAI_TRO as $vaiTro) {
            $nguoiDung = new NguoiDungTheoVaiTro();
            $nguoiDung->id = 1;
            $nguoiDung->vaiTroDangXet = $vaiTro;

            foreach (self::CAC_DUONG_DAN as $duongDan) {
                try {
                    $ma = $this->actingAs($nguoiDung)->get($duongDan)->getStatusCode();
                } catch (\Throwable $e) {
                    $ma = 500;
                }

                $maTran[$vaiTro . ' ' . $duongDan] = $ma;
            }
        }

        ksort($maTran);
        $noiDung = json_encode($maTran, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
        $duongDanMau = self::THU_MUC_MAU . '/phan-quyen.json';

        if (getenv('GOLDEN_WRITE')) {
            file_put_contents($duongDanMau, $noiDung);
            $this->assertTrue(true);

            return;
        }

        $this->assertFileExists($duongDanMau, 'Chua co tep mau. Chay lai voi GOLDEN_WRITE=1.');
        $this->assertSame(file_get_contents($duongDanMau), $noiDung, 'Ma tran phan quyen da thay doi');
    }
}

/**
 * Nguoi dung gia chi mang dung MOT vai tro, de kiem tra middleware checkrole
 * ma khong phai truy van bang roles.
 */
class NguoiDungTheoVaiTro extends User
{
    public $vaiTroDangXet = '';

    public function hasRole($role, $team = null, $requireAll = false)
    {
        $canhSat = is_array($role) ? $role : explode('|', (string) $role);

        return in_array($this->vaiTroDangXet, $canhSat, true);
    }

    public function can($permission, $team = null, $requireAll = false)
    {
        return $this->hasRole($permission, $team, $requireAll);
    }
}
```

- [ ] **Bước 3: Chạy, xác nhận thất bại đúng lý do, rồi sinh tệp mẫu**

```bash
php vendor/bin/phpunit --filter PhanQuyenGoldenTest
GOLDEN_WRITE=1 php vendor/bin/phpunit --filter PhanQuyenGoldenTest
php vendor/bin/phpunit --filter PhanQuyenGoldenTest
```

Kỳ vọng: lần 1 FAIL (`Chua co tep mau`), lần 3 PASS.

- [ ] **Bước 4: Kiểm mắt thường ma trận**

```bash
cat tests/Support/golden/phan-quyen.json
```

Xác nhận **có cả 200 lẫn 403** — nếu toàn bộ là 200 thì test vô nghĩa, phải chọn lại danh sách route cho phân hoá hơn.

- [ ] **Bước 5: Viết test chốt file Excel**

So sánh **giá trị ô**, không so byte — vì file `.xlsx` là zip, byte đổi mỗi lần tạo.

Tạo `tests/Feature/Golden/XuatFileGoldenTest.php`:

```php
<?php

namespace Tests\Feature\Golden;

use Tests\TestCase;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Chot noi dung file Excel xuat ra theo GIA TRI O, khong theo byte
 * (file .xlsx la zip nen byte doi moi lan tao).
 */
class XuatFileGoldenTest extends TestCase
{
    const THU_MUC_MAU = __DIR__ . '/../../Support/golden';

    /** Thay bang route xuat Excel that va tham so cua no. */
    const DUONG_DAN_XUAT_EXCEL = '<DUONG_DAN_XUAT_EXCEL>';

    /** @test */
    public function noi_dung_file_excel_xuat_ra_khong_doi()
    {
        $nguoiDung = new \Tests\Feature\SmokeAdminUser();
        $nguoiDung->id = 1;

        $phanHoi = $this->actingAs($nguoiDung)->get(self::DUONG_DAN_XUAT_EXCEL);

        $this->assertSame(200, $phanHoi->getStatusCode(), 'Route xuat Excel phai tra ve 200');

        $duongDanTam = sys_get_temp_dir() . '/qlbv-xuat-excel-test.xlsx';
        file_put_contents($duongDanTam, $phanHoi->getContent());

        $bang = IOFactory::load($duongDanTam)->getActiveSheet()->toArray(null, true, false, false);
        unlink($duongDanTam);

        $noiDung = json_encode($bang, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
        $duongDanMau = self::THU_MUC_MAU . '/xuat-excel.json';

        if (getenv('GOLDEN_WRITE')) {
            file_put_contents($duongDanMau, $noiDung);
            $this->assertTrue(true);

            return;
        }

        $this->assertFileExists($duongDanMau, 'Chua co tep mau. Chay lai voi GOLDEN_WRITE=1.');
        $this->assertSame(file_get_contents($duongDanMau), $noiDung, 'Noi dung file Excel xuat ra da thay doi');
    }
}
```

Tìm route xuất Excel để điền vào hằng số:

```bash
grep -rn "Export\b" routes/web.php | head -10
```

- [ ] **Bước 6: Chạy, sinh tệp mẫu, chạy lại**

```bash
php vendor/bin/phpunit --filter XuatFileGoldenTest
GOLDEN_WRITE=1 php vendor/bin/phpunit --filter XuatFileGoldenTest
php vendor/bin/phpunit --filter XuatFileGoldenTest
```

Kỳ vọng: lần 3 PASS.

- [ ] **Bước 7: Kiểm mắt thường tệp mẫu Excel**

```bash
head -30 tests/Support/golden/xuat-excel.json
```

Xác nhận có tiêu đề cột và ít nhất một dòng dữ liệu thật.

- [ ] **Bước 8: Bổ sung test chốt file PDF**

PDF không so được theo byte (dompdf nhúng dấu thời gian tạo file), cũng không so được theo cấu trúc như xlsx. Cách chốt: **trích văn bản trong PDF** và so sánh — như vậy vẫn bắt được lỗi mất chữ, sai số liệu, và **mất dấu tiếng Việt do thiếu font trong container** (rủi ro đã nêu ở Pha 0).

Thêm vào `tests/Feature/Golden/XuatFileGoldenTest.php`, trong cùng class:

```php
    /** Thay bang route xuat PDF that va tham so cua no. */
    const DUONG_DAN_XUAT_PDF = '<DUONG_DAN_XUAT_PDF>';

    /** @test */
    public function noi_dung_van_ban_trong_file_pdf_xuat_ra_khong_doi()
    {
        $nguoiDung = new \Tests\Feature\SmokeAdminUser();
        $nguoiDung->id = 1;

        $phanHoi = $this->actingAs($nguoiDung)->get(self::DUONG_DAN_XUAT_PDF);

        $this->assertSame(200, $phanHoi->getStatusCode(), 'Route xuat PDF phai tra ve 200');

        $noiDungPdf = $phanHoi->getContent();
        $this->assertStringStartsWith('%PDF', $noiDungPdf, 'Phan hoi phai la file PDF');

        // Trich cac chuoi van ban trong PDF: giai nen tung stream roi lay noi dung
        // trong toan tu Tj/TJ. Du de bat loi mat chu hoac mat dau tieng Viet.
        $cacChuoi = [];
        if (preg_match_all('/stream\r?\n(.*?)\r?\nendstream/s', $noiDungPdf, $khop)) {
            foreach ($khop[1] as $luong) {
                $giaiNen = @gzuncompress($luong);
                if ($giaiNen === false) {
                    continue;
                }
                if (preg_match_all('/\(((?:[^()\\\\]|\\\\.)*)\)\s*Tj/', $giaiNen, $khopChu)) {
                    foreach ($khopChu[1] as $chu) {
                        $cacChuoi[] = $chu;
                    }
                }
            }
        }

        $this->assertNotEmpty($cacChuoi, 'Khong trich duoc van ban nao tu PDF - kiem tra lai route hoac font');

        $noiDung = json_encode($cacChuoi, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
        $duongDanMau = self::THU_MUC_MAU . '/xuat-pdf.json';

        if (getenv('GOLDEN_WRITE')) {
            file_put_contents($duongDanMau, $noiDung);
            $this->assertTrue(true);

            return;
        }

        $this->assertFileExists($duongDanMau, 'Chua co tep mau. Chay lai voi GOLDEN_WRITE=1.');
        $this->assertSame(file_get_contents($duongDanMau), $noiDung, 'Van ban trong file PDF xuat ra da thay doi');
    }
```

Tìm route xuất PDF để điền vào hằng số:

```bash
grep -rniE "pdf" routes/web.php | head -10
```

- [ ] **Bước 9: Sinh tệp mẫu PDF và kiểm mắt thường**

```bash
php vendor/bin/phpunit --filter noi_dung_van_ban_trong_file_pdf
GOLDEN_WRITE=1 php vendor/bin/phpunit --filter noi_dung_van_ban_trong_file_pdf
php vendor/bin/phpunit --filter noi_dung_van_ban_trong_file_pdf
head -30 tests/Support/golden/xuat-pdf.json
```

Kỳ vọng: lần 1 FAIL (`Chua co tep mau`), lần 3 PASS. Trong tệp mẫu phải thấy **chuỗi tiếng Việt có dấu** — nếu chỉ thấy chữ không dấu hoặc ký tự lạ thì PDF đang thiếu font, phải xử lý trước khi đi tiếp (đây chính là rủi ro font đã nêu ở Pha 0).

- [ ] **Bước 10: Chạy toàn bộ test một lượt**

```bash
php vendor/bin/phpunit 2>&1 | tail -20
```

Kỳ vọng: các test mới xanh, số test đỏ cũ không tăng.

- [ ] **Bước 11: Commit**

```bash
git add tests/Feature/Golden/ tests/Support/golden/
git commit -m "test: chot ma tran phan quyen va noi dung file Excel, PDF xuat ra"
```

---

## Task 11: Pha 3 — Dockerfile tham số hoá theo phiên bản PHP

**Files:**
- Sửa: `docker/php/Dockerfile` (viết lại toàn bộ)
- Sửa: `docker/php/php.ini`

**Interfaces:**
- Consumes: `docs/superpowers/notes/2026-07-31-spike-oci8-php8.md` (Task 1) — lấy phiên bản oci8, Instant Client, danh sách gói apt
- Produces: image build được với `--build-arg PHP_VERSION=7.4` (dùng ngay) và `--build-arg PHP_VERSION=8.x` (Kế hoạch 2 dùng)

- [ ] **Bước 1: Viết lại Dockerfile**

Thay `docker/php/Dockerfile` bằng nội dung sau. Ba khác biệt chính so với bản cũ: tham số hoá phiên bản PHP, chọn phiên bản oci8 theo PHP, và **bỏ `COPY . /var/www` + `composer install`** (mã nguồn được mount qua volume, giữ nguyên cách làm của compose hiện tại):

```dockerfile
ARG PHP_VERSION=7.4
FROM php:${PHP_VERSION}-fpm

# Phien ban oci8: 2.2.0 cho PHP 7.x, 3.x cho PHP 8.x. Xem ket qua spike Pha 0.
ARG OCI8_VERSION=2.2.0

RUN apt-get update && apt-get install -y \
    git curl unzip zip wget libaio1 \
    libpng-dev libonig-dev libxml2-dev libzip-dev \
    libfreetype6-dev libjpeg62-turbo-dev \
    libicu-dev libxslt1-dev libgmp-dev \
    fonts-dejavu-core \
    && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        pdo_mysql mbstring exif pcntl bcmath gd intl soap xsl gmp zip opcache

# Oracle Instant Client - can glibc nen KHONG dung Alpine.
RUN mkdir -p /opt/oracle \
    && cd /opt/oracle \
    && wget -q https://download.oracle.com/otn_software/linux/instantclient/instantclient-basiclite-linuxx64.zip \
    && wget -q https://download.oracle.com/otn_software/linux/instantclient/instantclient-sdk-linuxx64.zip \
    && unzip -q -o instantclient-basiclite-linuxx64.zip \
    && unzip -q -o instantclient-sdk-linuxx64.zip \
    && mv instantclient_* instantclient \
    && rm -f *.zip \
    && echo /opt/oracle/instantclient > /etc/ld.so.conf.d/oracle-instantclient.conf \
    && ldconfig

ENV LD_LIBRARY_PATH=/opt/oracle/instantclient
ENV NLS_LANG=AMERICAN_AMERICA.AL32UTF8
ENV TZ=Asia/Ho_Chi_Minh

RUN echo "instantclient,/opt/oracle/instantclient" | pecl install oci8-${OCI8_VERSION} \
    && docker-php-ext-enable oci8

RUN pecl install redis && docker-php-ext-enable redis

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY docker/php/php.ini /usr/local/etc/php/conf.d/custom.ini

WORKDIR /var/www

EXPOSE 9000
CMD ["php-fpm"]
```

- [ ] **Bước 2: Bổ sung php.ini**

Thêm vào cuối `docker/php/php.ini` (giữ nguyên phần đã có):

```ini
[Date]
date.timezone = Asia/Ho_Chi_Minh

[oci8]
oci8.privileged_connect = Off
oci8.max_persistent = -1
```

Xoá dòng `extension=redis.so` ở cuối file — `docker-php-ext-enable redis` đã lo việc này, khai báo hai lần gây cảnh báo.

- [ ] **Bước 3: Build biến thể PHP 7.4**

```bash
docker build -f docker/php/Dockerfile --build-arg PHP_VERSION=7.4 --build-arg OCI8_VERSION=2.2.0 -t qlbv-app:php74 .
```

Kỳ vọng: build thành công.

- [ ] **Bước 4: Xác nhận extension nạp đủ**

```bash
docker run --rm qlbv-app:php74 php -m | grep -iE "oci8|pdo_mysql|redis|intl|gd|zip|xsl|soap"
docker run --rm qlbv-app:php74 php -r 'echo date("Y-m-d H:i:s T"), PHP_EOL;'
```

Kỳ vọng: đủ 8 extension, giờ là giờ Việt Nam.

- [ ] **Bước 5: Build thử biến thể PHP 8 để chắc Dockerfile tham số hoá đúng**

Thay `8.3`/`3.4.0` bằng giá trị chốt ở Task 1:

```bash
docker build -f docker/php/Dockerfile --build-arg PHP_VERSION=8.3 --build-arg OCI8_VERSION=3.4.0 -t qlbv-app:php83 .
docker run --rm qlbv-app:php83 php -m | grep -i oci8
```

Kỳ vọng: build thành công, có `oci8`. Đây là bằng chứng Kế hoạch 2 chỉ cần đổi tham số.

- [ ] **Bước 6: Commit**

```bash
git add docker/php/Dockerfile docker/php/php.ini
git commit -m "build: Dockerfile tham so hoa theo phien ban PHP, kem oci8 va Instant Client"
```

---

## Task 12: Pha 3 — Viết lại docker-compose

**Files:**
- Sửa: `docker-compose.yml` (viết lại toàn bộ)
- Tạo: `docker/scheduler/entrypoint.sh`
- Sửa: `env.docker`

**Interfaces:**
- Consumes: image `qlbv-app` từ Task 11
- Produces: 5 service `app`, `nginx`, `redis`, `queue`, `scheduler`

- [ ] **Bước 1: Viết lại docker-compose.yml**

Ba thay đổi so với bản cũ: **bỏ service `mysql`** (MySQL và Oracle đều là DB ngoài), thêm `queue` + `scheduler`, tham số hoá phiên bản PHP:

```yaml
services:
  app:
    build:
      context: .
      dockerfile: docker/php/Dockerfile
      args:
        PHP_VERSION: "${PHP_VERSION:-7.4}"
        OCI8_VERSION: "${OCI8_VERSION:-2.2.0}"
    image: qlbv-app:php${PHP_VERSION:-7.4}
    container_name: qlbv_app
    restart: unless-stopped
    working_dir: /var/www
    env_file: .env
    volumes:
      - ./:/var/www
    networks:
      - qlbv_network
    depends_on:
      - redis

  nginx:
    image: nginx:alpine
    container_name: qlbv_nginx
    restart: unless-stopped
    ports:
      - "8080:80"
    volumes:
      - ./:/var/www
      - ./docker/nginx/nginx.conf:/etc/nginx/nginx.conf:ro
    depends_on:
      - app
    networks:
      - qlbv_network

  redis:
    image: redis:7-alpine
    container_name: qlbv_redis
    restart: unless-stopped
    networks:
      - qlbv_network

  queue:
    image: qlbv-app:php${PHP_VERSION:-7.4}
    container_name: qlbv_queue
    restart: unless-stopped
    working_dir: /var/www
    env_file: .env
    volumes:
      - ./:/var/www
    command: php artisan queue:work --tries=3 --timeout=300 --sleep=3
    depends_on:
      - app
      - redis
    networks:
      - qlbv_network

  scheduler:
    image: qlbv-app:php${PHP_VERSION:-7.4}
    container_name: qlbv_scheduler
    restart: unless-stopped
    working_dir: /var/www
    env_file: .env
    volumes:
      - ./:/var/www
      - ./docker/scheduler/entrypoint.sh:/entrypoint.sh:ro
    entrypoint: ["sh", "/entrypoint.sh"]
    depends_on:
      - app
    networks:
      - qlbv_network

networks:
  qlbv_network:
    driver: bridge
```

**Lưu ý:** cổng đổi sang `8080:80` để không đụng XAMPP đang chạy ở cổng 80.

- [ ] **Bước 2: Viết entrypoint cho scheduler**

Tạo `docker/scheduler/entrypoint.sh`:

```sh
#!/bin/sh
# Thay cho Windows Task Scheduler: goi schedule:run moi phut.
while true; do
    php /var/www/artisan schedule:run --no-interaction >> /var/www/storage/logs/scheduler.log 2>&1
    sleep 60
done
```

- [ ] **Bước 3: Cập nhật env.docker**

Sửa `env.docker`: bỏ khối `PUSHER_*` và `BROADCAST_DRIVER` (đã gỡ ở Task 4), đổi `DB_HOST` trỏ tới **MySQL bản sao** chứ không phải service compose (đã bỏ service mysql), thêm khối Oracle:

```
# Database ung dung (MySQL ban sao - KHONG dung production)
DB_CONNECTION=mysql
DB_HOST=host.docker.internal
DB_PORT=3306
DB_DATABASE=qlbv_ban_sao
DB_USERNAME=<user>
DB_PASSWORD=<pass>

# Oracle HIS - tai khoan CHI DOC
ORACLE_HOST=<host>
ORACLE_PORT=1521
ORACLE_SERVICE=<service>
ORACLE_USERNAME=<user_chi_doc>
ORACLE_PASSWORD=<pass>

# Redis
REDIS_HOST=redis
REDIS_PORT=6379

BROADCAST_DRIVER=log
```

- [ ] **Bước 4: Kiểm cú pháp compose**

```bash
docker compose config > /dev/null && echo "COMPOSE OK"
```

Kỳ vọng: in `COMPOSE OK`.

- [ ] **Bước 5: Commit**

```bash
git add docker-compose.yml docker/scheduler/entrypoint.sh env.docker
git commit -m "build: viet lai docker-compose voi service queue va scheduler, bo mysql noi bo"
```

---

## Task 13: Pha 3 — Liệt kê và đưa tác vụ Task Scheduler vào mã nguồn

**Đây là hạng mục dễ bỏ sót nhất của cả dự án.** Danh sách tác vụ định kỳ hiện **chỉ tồn tại trên máy production**, không có trong git — `Console\Kernel::schedule()` đang rỗng.

**Files:**
- Tạo: `docs/superpowers/notes/2026-07-31-tac-vu-task-scheduler.md`
- Sửa: `app/Console/Kernel.php`

**Interfaces:**
- Produces: `Console\Kernel::schedule()` chứa đầy đủ tác vụ; container `scheduler` (Task 12) chạy được chúng

- [ ] **Bước 1: Xuất danh sách tác vụ từ máy production**

Chạy trên **máy Windows production** (PowerShell, quyền quản trị):

```bash
Get-ScheduledTask | Where-Object { $_.Actions.Execute -match "php|artisan" } | ForEach-Object { [PSCustomObject]@{ Ten = $_.TaskName; Lenh = ($_.Actions.Execute + " " + $_.Actions.Arguments); Lich = ($_.Triggers | ForEach-Object { $_.CimClass.CimClassName + " " + $_.StartBoundary + " " + $_.Repetition.Interval }) -join "; " } } | Format-List
```

Nếu lệnh trên không bắt hết (một số tác vụ gọi qua file `.bat`), bổ sung:

```bash
Get-ScheduledTask | Where-Object { $_.Actions.Execute -match "\.bat|\.cmd|\.ps1" } | Select-Object TaskName, @{n="Lenh";e={$_.Actions.Execute + " " + $_.Actions.Arguments}} | Format-List
```

rồi mở từng file `.bat` để xem nó gọi lệnh artisan nào.

- [ ] **Bước 2: Ghi thành tài liệu**

Tạo `docs/superpowers/notes/2026-07-31-tac-vu-task-scheduler.md` — một bảng gồm: tên tác vụ, lệnh artisan đầy đủ, tần suất, giờ chạy, ghi chú (có được phép chạy trùng không, có phụ thuộc tác vụ khác không).

Đối chiếu với danh sách 32 lệnh trong mã nguồn để phát hiện lệnh mồ côi:

```bash
ls app/Console/Commands/
```

Đánh dấu trong tài liệu: lệnh nào có trong Task Scheduler, lệnh nào có mã nhưng **không được lên lịch** (có thể là lệnh chạy tay).

- [ ] **Bước 3: Đưa lịch vào Kernel**

Sửa `app/Console/Kernel.php`, phương thức `schedule()`. Mẫu (thay bằng danh sách thật ở Bước 2):

```php
protected function schedule(Schedule $schedule)
{
    // Nguon: docs/superpowers/notes/2026-07-31-tac-vu-task-scheduler.md
    // Truoc day cac tac vu nay nam trong Windows Task Scheduler, khong co trong ma nguon.

    $schedule->command('<lenh:mot>')
             ->cron('<bieu_thuc_cron>')
             ->withoutOverlapping()
             ->appendOutputTo(storage_path('logs/schedule-<lenh-mot>.log'));

    $schedule->command('<lenh:hai>')
             ->cron('<bieu_thuc_cron>')
             ->withoutOverlapping()
             ->appendOutputTo(storage_path('logs/schedule-<lenh-hai>.log'));
}
```

`withoutOverlapping()` là bắt buộc cho mọi tác vụ — Task Scheduler trước đây có thể đã ngăn chạy chồng bằng cấu hình riêng, cơ chế đó không tự chuyển sang.

- [ ] **Bước 4: Xác nhận Laravel nhìn thấy đủ tác vụ**

```bash
php artisan schedule:run --help > /dev/null && php -r '
require "vendor/autoload.php";
$app = require "bootstrap/app.php";
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$lich = $app->make(Illuminate\Console\Scheduling\Schedule::class);
foreach ($lich->events() as $su) { echo $su->getSummaryForDisplay(), " | ", $su->expression, PHP_EOL; }
'
```

Kỳ vọng: in ra đúng số tác vụ đã liệt kê ở Bước 2. Đối chiếu từng dòng với tài liệu.

- [ ] **Bước 5: Chạy thử một tác vụ vô hại**

Chọn một lệnh chỉ đọc (không ghi DB, không gửi ra ngoài) và chạy tay:

```bash
php artisan <lenh_chi_doc>
```

Kỳ vọng: chạy xong không lỗi.

- [ ] **Bước 6: Commit**

```bash
git add app/Console/Kernel.php docs/superpowers/notes/2026-07-31-tac-vu-task-scheduler.md
git commit -m "feat: dua lich tac vu tu Windows Task Scheduler vao ma nguon"
```

---

## Task 14: Pha 3 — Chạy bản 5.5 nguyên trạng trong Docker

Cổng nghiệm thu của cả Kế hoạch 1.

**Files:**
- Tạo: `.env.docker.local` *(không commit — thêm vào `.gitignore`)*
- Sửa: `docker/nginx/nginx.conf` nếu cần

**Interfaces:**
- Consumes: mọi thứ từ Task 11–13
- Produces: bằng chứng bản 5.5 chạy trong Docker với smoke test xanh

- [ ] **Bước 1: Chuẩn bị file môi trường**

```bash
cp env.docker .env.docker.local
```

Điền giá trị thật vào `.env.docker.local`: MySQL **bản sao**, Oracle tài khoản **chỉ đọc**, và đặt `APP_KEY` bằng khoá hiện có của production (để session/cache tương thích).

Thêm vào `.gitignore`:

```bash
echo ".env.docker.local" >> .gitignore
```

- [ ] **Bước 2: Khởi động toàn bộ**

```bash
docker compose --env-file .env.docker.local up -d --build
docker compose ps
```

Kỳ vọng: 5 container ở trạng thái `running`.

- [ ] **Bước 3: Xác nhận ứng dụng boot trong container**

```bash
docker compose exec app php artisan --version
docker compose exec app php artisan config:clear
```

Kỳ vọng: in `Laravel Framework 5.5.50`.

- [ ] **Bước 4: Xác nhận kết nối cả hai DB từ container**

```bash
docker compose exec app php artisan tinker --execute="echo 'MySQL: ' . DB::connection('mysql')->select('select 1 as x')[0]->x . PHP_EOL;"
docker compose exec app php artisan tinker --execute="echo 'Oracle: ' . DB::connection('oracle')->select('select 1 as x from dual')[0]->x . PHP_EOL;"
```

Kỳ vọng: in `MySQL: 1` và `Oracle: 1`.

- [ ] **Bước 5: Mở ứng dụng qua trình duyệt**

```bash
curl -s -o /dev/null -w "%{http_code}\n" http://localhost:8080/
```

Kỳ vọng: 200 hoặc 302 (chuyển hướng tới trang đăng nhập). Nếu 502, xem log:

```bash
docker compose logs nginx --tail 30
docker compose logs app --tail 30
```

- [ ] **Bước 6: Đăng nhập thật bằng trình duyệt**

Mở `http://localhost:8080/`, đăng nhập bằng tài khoản thật, vào Dashboard. Chụp màn hình lưu vào `docs/superpowers/notes/`.

- [ ] **Bước 7: Chạy toàn bộ test bên trong container**

```bash
docker compose exec app php vendor/bin/phpunit 2>&1 | tail -20
```

Kỳ vọng: kết quả **giống hệt** chuẩn nền ở Task 6. Nếu khác, đó là lỗi môi trường Docker — sửa trước khi đi tiếp, **không** chuyển sang Kế hoạch 2.

- [ ] **Bước 8: Xác nhận smoke test xanh trong container**

```bash
docker compose exec app php vendor/bin/phpunit --filter SmokeAllRoutesTest
```

Kỳ vọng: PASS. Đây là bằng chứng chính: bản 5.5 chạy trong Docker cho kết quả giống trên XAMPP.

- [ ] **Bước 9: Xác nhận queue và scheduler sống**

```bash
docker compose logs queue --tail 20
docker compose logs scheduler --tail 20
docker compose exec app cat storage/logs/scheduler.log | tail -20
```

Kỳ vọng: queue worker đang lắng nghe, scheduler ghi log mỗi phút không lỗi.

- [ ] **Bước 10: Ghi biên bản nghiệm thu Pha 3**

Bổ sung vào `docs/superpowers/notes/2026-07-31-spike-oci8-php8.md` một mục "Nghiệm thu Pha 3": kết quả từng bước 3–9, kèm ảnh chụp màn hình.

- [ ] **Bước 11: Commit và mở pull request**

```bash
git add -A
git commit -m "build: ban Laravel 5.5 nguyen trang chay duoc trong Docker"
git push -u origin upgrade/laravel-13
```

---

## Tiêu chí hoàn thành Kế hoạch 1

Toàn bộ phải đúng trước khi bắt đầu Kế hoạch 2:

- [ ] Spike Pha 0 thành công: oci8 chạy trên PHP 8.x, kết nối được Oracle 19c, đọc được bảng thật, tiếng Việt đúng dấu.
- [ ] Không còn tham chiếu vaccination, sarcov2, pusher trong repo.
- [ ] `composer.json` đã gỡ `orchestra/parser` và `pusher/pusher-php-server`.
- [ ] Có `docs/superpowers/notes/2026-07-31-chuan-nen-test.md` ghi rõ từng test đỏ hiện tại.
- [ ] `tests/Support/smoke-baseline.json` tồn tại và smoke test xanh.
- [ ] Bốn test chốt hành vi (XML3176, giao ban, phân quyền, xuất Excel + PDF) đều xanh và có tệp mẫu trong git; tệp mẫu PDF chứa tiếng Việt có dấu.
- [ ] Dockerfile build được **cả hai** biến thể PHP 7.4 và PHP 8.x.
- [ ] Danh sách tác vụ Task Scheduler đã nằm trong `Console\Kernel::schedule()` và trong tài liệu.
- [ ] `docker compose up` cho ra 5 container chạy, đăng nhập được qua trình duyệt, test trong container ra kết quả giống chuẩn nền.
