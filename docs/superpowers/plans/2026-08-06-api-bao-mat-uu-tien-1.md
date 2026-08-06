# Bảo mật API ưu tiên 1 — Kế hoạch thực thi

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Thay token API đoán được bằng token ngẫu nhiên lưu dạng băm SHA-256, so sánh constant-time, hạ mức log xác thực, và thêm index `treatment_code` cho `order_check_violations`.

**Architecture:** `ApiAuthMiddleware` so `hash_equals(hash cấu hình, sha256(token nhận được))` và có bốn nhánh 401 (thiếu header, sai định dạng, sai token, chưa cấu hình). Lệnh `php artisan api:generate` sinh token, thay đúng một dòng trong `config/organization.php` bằng regex — mô phỏng `key:generate`. Index thêm bằng migration riêng.

**Tech Stack:** Laravel 5.5, PHP 7.0, MySQL, PHPUnit 6.

**Spec:** `docs/superpowers/specs/2026-08-06-api-bao-mat-uu-tien-1-design.md`.

## Global Constraints

- **Cú pháp PHP 7.0**: không dùng kiểu trả về `void`/nullable type, không typed property, không arrow function.
- **Laravel 5.5**: `setUp()` trong test không khai báo kiểu trả về (PHPUnit 6); `TestResponse::json()` không nhận tham số khoá.
- **`config/organization.php` nằm trong `.gitignore`** — mọi thay đổi trong tệp đó **không được commit** và phải làm tay trên từng máy. Không thêm tệp này vào git dù bất cứ lý do gì.
- **Không bao giờ ghi token vào log**, kể cả một phần hay bản băm.
- **Không dùng `RefreshDatabase`**: `.env` trỏ `DB_DATABASE=qlbv` là CSDL phát triển thật.
- Test bắt log qua sự kiện `Illuminate\Log\Events\MessageLogged`. Sự kiện này được bắn **trước** bộ lọc mức của Monolog (`vendor/laravel/framework/src/Illuminate/Log/Writer.php:201`), nên vẫn bắt được dù `phpunit.xml` đặt `APP_LOG_LEVEL=emergency`.
- **Chạy toàn bộ test trước khi bắt đầu** để lấy mốc. Mốc đã biết tại thời điểm viết kế hoạch: **907 tests, 8 errors, 6 failures, 1 skipped**. Tiêu chí: số đỏ không tăng.

## File Structure

| Tệp | Trách nhiệm |
|---|---|
| `app/Http/Middleware/ApiAuthMiddleware.php` (viết lại) | Xác thực bằng băm, bốn nhánh 401, ghi log đúng mức |
| `app/Console/Commands/ApiGenerateToken.php` (tạo) | Sinh token, thay một dòng hash trong config, xoá cache config |
| `database/migrations/2026_08_06_100000_them_index_treatment_code_vao_order_check_violations.php` (tạo) | Index `treatment_code` |
| `tests/Feature/ApiAuthMiddlewareTest.php` (tạo) | Sáu ca xác thực + hai ca log |
| `tests/Unit/ApiGenerateTokenTest.php` (tạo) | Lệnh sinh token, thao tác trên tệp tạm |
| `docs/order-check/API-TRA-CUU-LOI.md` (sửa) | Mục "Cấp token (dành cho quản trị)" |

`config/organization.php` cũng phải sửa nhưng **không nằm trong danh sách trên** vì không được commit — sửa tay ở Task 2 Step 7.

---

### Task 1: Middleware xác thực bằng băm

**Files:**
- Modify: `app/Http/Middleware/ApiAuthMiddleware.php` (viết lại toàn bộ)
- Create: `tests/Feature/ApiAuthMiddlewareTest.php`

**Interfaces:**
- Consumes: `config('organization.api.access_token_hash')` — chuỗi hex SHA-256, hoặc rỗng/thiếu.
- Consumes: `Tests\Support\DungBangLoiDotDieuTriSqlite::chuanBiBangLoi()` (đã có sẵn trong repo) để endpoint `/api/order-check/violations` chạy được trên SQLite.
- Produces: bốn giá trị `ly_do` trong log — `thieu_header`, `sai_dinh_dang`, `sai_token`, `chua_cau_hinh`. Task 4 nhắc tới chúng trong tài liệu.

- [ ] **Step 1: Viết test thất bại**

Tạo `tests/Feature/ApiAuthMiddlewareTest.php`:

```php
<?php

namespace Tests\Feature;

use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Event;
use Tests\Support\DungBangLoiDotDieuTriSqlite;
use Tests\TestCase;

class ApiAuthMiddlewareTest extends TestCase
{
    use DungBangLoiDotDieuTriSqlite;

    const TOKEN = 'token-thu-nghiem-64-ky-tu';

    protected function setUp()
    {
        parent::setUp();

        $this->chuanBiBangLoi();

        config(['organization.api.access_token_hash' => hash('sha256', self::TOKEN)]);
    }

    protected function goi($header = null)
    {
        $tuyChon = $header === null ? [] : ['Authorization' => $header];

        return $this->getJson('/api/order-check/violations?treatment_code=X', $tuyChon);
    }

    /**
     * Bat log qua su kien MessageLogged: su kien nay duoc ban TRUOC bo loc muc cua
     * Monolog, nen van bat duoc du phpunit.xml dat APP_LOG_LEVEL=emergency.
     *
     * @return array danh sach ['level' => ..., 'context' => [...]]
     */
    protected function batLog(callable $viec)
    {
        $ghi = [];

        Event::listen(MessageLogged::class, function ($e) use (&$ghi) {
            $ghi[] = ['level' => $e->level, 'context' => $e->context];
        });

        $viec();

        return $ghi;
    }

    /** @test */
    public function token_dung_thi_qua_duoc()
    {
        $this->goi('Bearer ' . self::TOKEN)->assertStatus(200);
    }

    /** @test */
    public function token_sai_thi_401()
    {
        $this->goi('Bearer token-bay-ba')
            ->assertStatus(401)
            ->assertJson(['success' => false, 'error' => ['code' => 'UNAUTHORIZED']]);
    }

    /** @test */
    public function thieu_header_thi_401()
    {
        $this->goi(null)->assertStatus(401);
    }

    /** @test */
    public function sai_dinh_dang_header_thi_401()
    {
        $this->goi('Token ' . self::TOKEN)->assertStatus(401);
        $this->goi('Bearer')->assertStatus(401);
    }

    /**
     * config/organization.php khong nam trong git nen ban cai chua cap nhat se THIEU
     * khoa nay. Trang thai an toan duy nhat la tu choi - khong phai 500, va tuyet doi
     * khong phai cho qua.
     *
     * @test
     */
    public function chua_cau_hinh_hash_thi_401_chu_khong_cho_qua()
    {
        config(['organization.api.access_token_hash' => '']);

        $this->goi('Bearer ' . self::TOKEN)->assertStatus(401);
        $this->goi('Bearer ')->assertStatus(401);
    }

    /**
     * Chan duong so sanh truc tiep: neu con doan code nao so token voi gia tri cau hinh
     * ma khong bam, ca nay se do.
     *
     * @test
     */
    public function cau_hinh_luu_token_tho_thi_khong_qua_duoc()
    {
        config(['organization.api.access_token_hash' => self::TOKEN]);

        $this->goi('Bearer ' . self::TOKEN)->assertStatus(401);
    }

    /** @test */
    public function that_bai_ghi_warning_kem_ly_do_va_khong_kem_token()
    {
        $ghi = $this->batLog(function () {
            $this->goi('Bearer token-bay-ba');
        });

        $warning = array_values(array_filter($ghi, function ($d) {
            return $d['level'] === 'warning';
        }));

        $this->assertCount(1, $warning);
        $this->assertEquals('sai_token', $warning[0]['context']['ly_do']);
        $this->assertNotContains('token-bay-ba', json_encode($warning[0]['context']));
    }

    /**
     * Truoc day moi request thanh cong deu ghi Log::info, lam ngap log that.
     *
     * @test
     */
    public function thanh_cong_ghi_debug_chu_khong_phai_info()
    {
        $ghi = $this->batLog(function () {
            $this->goi('Bearer ' . self::TOKEN);
        });

        $muc = array_column($ghi, 'level');

        $this->assertContains('debug', $muc);
        $this->assertNotContains('info', $muc);
    }
}
```

- [ ] **Step 2: Chạy test để xác nhận đỏ**

Run: `vendor/bin/phpunit tests/Feature/ApiAuthMiddlewareTest.php`
Expected: FAIL — middleware còn đọc `access_token` nên `token_dung_thi_qua_duoc` nhận 401, và `thanh_cong_ghi_debug_chu_khong_phai_info` thấy mức `info`.

- [ ] **Step 3: Viết lại middleware**

Thay toàn bộ nội dung `app/Http/Middleware/ApiAuthMiddleware.php`:

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Xac thuc token API bang BAN BAM SHA-256.
 *
 * Config chi chua hash; token goc khong ton tai o bat ky dau trong ma nguon hay cau
 * hinh. Lo tep config (backup, log, xem nham) khong du de goi API.
 */
class ApiAuthMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $authHeader = $request->header('Authorization');

        if (!$authHeader) {
            return $this->tuChoi(
                $request,
                'thieu_header',
                'Authorization header is required',
                'Please include \'Authorization: Bearer {token}\' in your request headers'
            );
        }

        if (!preg_match('/^Bearer\s+(.+)$/i', $authHeader, $khop)) {
            return $this->tuChoi(
                $request,
                'sai_dinh_dang',
                'Invalid authorization format',
                'Authorization header must be in format: Bearer {token}'
            );
        }

        $token = $khop[1];
        $hashCauHinh = (string) config('organization.api.access_token_hash');

        // Thieu cau hinh => TU CHOI. config/organization.php khong nam trong git nen ban
        // cai chua cap nhat se thieu khoa nay; trang thai an toan duy nhat la 401.
        // Thong diep giong het nhanh sai token - khong de lo cho nguoi do biet he thong
        // dang thieu cau hinh.
        if ($hashCauHinh === '') {
            return $this->tuChoi(
                $request,
                'chua_cau_hinh',
                'Invalid access token',
                'The provided token is not valid or has expired'
            );
        }

        // hash_equals: thoi gian so sanh khong phu thuoc so ky tu trung dau chuoi.
        if (!hash_equals($hashCauHinh, hash('sha256', $token))) {
            return $this->tuChoi(
                $request,
                'sai_token',
                'Invalid access token',
                'The provided token is not valid or has expired'
            );
        }

        // Muc debug chu khong phai info: truoc day moi request thanh cong deu ghi mot
        // dong info, lam ngap nhung dong that su can doc.
        \Log::debug('API xac thuc thanh cong', [
            'endpoint' => $request->path(),
            'request_id' => $this->maYeuCau(),
        ]);

        return $next($request);
    }

    protected function tuChoi(Request $request, $lyDo, $message, $details)
    {
        // KHONG ghi token duoi bat ky dang nao - ke ca mot phan, ke ca ban bam.
        \Log::warning('API xac thuc that bai', [
            'endpoint' => $request->path(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'ly_do' => $lyDo,
        ]);

        return response()->json([
            'success' => false,
            'error' => [
                'code' => 'UNAUTHORIZED',
                'message' => $message,
                'details' => $details,
            ],
            'meta' => [
                'timestamp' => now()->format('YmdHis'),
                'request_id' => $this->maYeuCau(),
            ],
        ], 401);
    }

    protected function maYeuCau()
    {
        return uniqid('req_');
    }
}
```

- [ ] **Step 4: Chạy test để xác nhận xanh**

Run: `vendor/bin/phpunit tests/Feature/ApiAuthMiddlewareTest.php`
Expected: PASS — 8 tests.

- [ ] **Step 5: Chạy bộ test của API order-check để chắc không vỡ**

Run: `vendor/bin/phpunit tests/Feature/OrderCheckApiTest.php`
Expected: FAIL — 5 ca đều 401, vì tệp test đó đang đặt `organization.api.access_token` (khoá cũ).

- [ ] **Step 6: Sửa `tests/Feature/OrderCheckApiTest.php` sang khoá mới**

Trong `tests/Feature/OrderCheckApiTest.php`, thay dòng trong `setUp()`:

```php
        config(['organization.api.access_token' => self::TOKEN]);
```

bằng:

```php
        config(['organization.api.access_token_hash' => hash('sha256', self::TOKEN)]);
```

- [ ] **Step 7: Chạy lại để xác nhận xanh**

Run: `vendor/bin/phpunit tests/Feature/OrderCheckApiTest.php`
Expected: PASS — 5 tests.

- [ ] **Step 8: Commit**

```bash
git add app/Http/Middleware/ApiAuthMiddleware.php tests/Feature/ApiAuthMiddlewareTest.php tests/Feature/OrderCheckApiTest.php
git commit -m "feat(api): xac thuc bang ban bam SHA-256, hash_equals, ha muc log"
```

---

### Task 2: Lệnh `php artisan api:generate`

**Files:**
- Create: `app/Console/Commands/ApiGenerateToken.php`
- Create: `tests/Unit/ApiGenerateTokenTest.php`
- Modify (KHÔNG commit, gitignored): `config/organization.php`

**Interfaces:**
- Produces: lệnh `api:generate {--force}`; hằng `ApiGenerateToken::KHOA = 'access_token_hash'`.
- Produces: hai hàm `protected` để test ghi đè — `duongDanConfig()` và `duongDanCacheConfig()`.

**Cảnh báo:** trong repo đã có `app/Console/Commands/AddConfigOrganizationKey.php` (`config:add-keys`) ghi lại **toàn bộ** `config/organization.php` bằng `var_export`, xoá sạch chú thích. **Không lấy lệnh đó làm mẫu.** Lệnh mới chỉ thay đúng một dòng.

- [ ] **Step 1: Viết test thất bại**

Tạo `tests/Unit/ApiGenerateTokenTest.php`:

```php
<?php

namespace Tests\Unit;

use App\Console\Commands\ApiGenerateToken;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Tests\TestCase;

/** Ghi de duong dan de test KHONG cham config/organization.php that. */
class ApiGenerateTokenGiaLap extends ApiGenerateToken
{
    public $tepGiaLap;

    protected function duongDanConfig()
    {
        return $this->tepGiaLap;
    }

    protected function duongDanCacheConfig()
    {
        return '/duong-dan-khong-ton-tai';
    }
}

class ApiGenerateTokenTest extends TestCase
{
    protected $tep;

    protected function setUp()
    {
        parent::setUp();

        $this->tep = tempnam(sys_get_temp_dir(), 'cfg');
    }

    protected function tearDown()
    {
        if ($this->tep && is_file($this->tep)) {
            unlink($this->tep);
        }

        parent::tearDown();
    }

    protected function noiDungMau($hashCu = '')
    {
        return "<?php\n\nreturn [\n"
            . "    // Chu thich phai con nguyen sau khi ghi\n"
            . "    'api' => [\n"
            . "        'access_token_hash' => '" . $hashCu . "',\n"
            . "        'organization' => '01013',\n"
            . "    ],\n"
            . "];\n";
    }

    /** @return array ['ma_thoat' => int, 'ra' => string] */
    protected function chay(array $thamSo = ['--force' => true])
    {
        $lenh = new ApiGenerateTokenGiaLap();
        $lenh->tepGiaLap = $this->tep;
        $lenh->setLaravel($this->app);

        $ra = new BufferedOutput();
        $maThoat = $lenh->run(new ArrayInput($thamSo), $ra);

        return ['ma_thoat' => $maThoat, 'ra' => $ra->fetch()];
    }

    protected function tokenTrongDauRa($ra)
    {
        preg_match('/\b([0-9a-f]{64})\b/', $ra, $khop);

        return isset($khop[1]) ? $khop[1] : null;
    }

    /** @test */
    public function sinh_token_64_ky_tu_hex()
    {
        file_put_contents($this->tep, $this->noiDungMau());

        $kq = $this->chay();

        $token = $this->tokenTrongDauRa($kq['ra']);

        $this->assertNotNull($token);
        $this->assertSame(64, strlen($token));
        $this->assertEquals(0, $kq['ma_thoat']);
    }

    /** @test */
    public function hai_lan_chay_cho_hai_token_khac_nhau()
    {
        file_put_contents($this->tep, $this->noiDungMau());
        $mot = $this->tokenTrongDauRa($this->chay()['ra']);

        file_put_contents($this->tep, $this->noiDungMau());
        $hai = $this->tokenTrongDauRa($this->chay()['ra']);

        $this->assertNotEquals($mot, $hai);
    }

    /** @test */
    public function hash_ghi_vao_tep_dung_bang_sha256_cua_token_in_ra()
    {
        file_put_contents($this->tep, $this->noiDungMau());

        $token = $this->tokenTrongDauRa($this->chay()['ra']);

        $this->assertContains(
            "'access_token_hash' => '" . hash('sha256', $token) . "'",
            file_get_contents($this->tep)
        );
    }

    /**
     * Tep nay con chua cau hinh co so KCB va tai khoan cong BHXH - ghi lai ca tep la
     * cach nhanh nhat lam mat chung.
     *
     * @test
     */
    public function cac_dong_khac_giu_nguyen_tung_ky_tu()
    {
        file_put_contents($this->tep, $this->noiDungMau());

        $this->chay();

        $sau = file_get_contents($this->tep);

        $this->assertContains('// Chu thich phai con nguyen sau khi ghi', $sau);
        $this->assertContains("'organization' => '01013',", $sau);
    }

    /** @test */
    public function tep_thieu_khoa_thi_khong_ghi_gi_va_bao_loi()
    {
        $truoc = "<?php\n\nreturn [\n    'api' => [\n        'organization' => '01013',\n    ],\n];\n";
        file_put_contents($this->tep, $truoc);

        $kq = $this->chay();

        $this->assertNotEquals(0, $kq['ma_thoat']);
        $this->assertEquals($truoc, file_get_contents($this->tep));
        $this->assertContains('access_token_hash', $kq['ra']);
    }
}
```

- [ ] **Step 2: Chạy test để xác nhận đỏ**

Run: `vendor/bin/phpunit tests/Unit/ApiGenerateTokenTest.php`
Expected: FAIL — `Class 'App\Console\Commands\ApiGenerateToken' not found`.

- [ ] **Step 3: Viết lệnh**

Tạo `app/Console/Commands/ApiGenerateToken.php`:

```php
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * Sinh token API moi va ghi BAN BAM cua no vao config/organization.php.
 *
 * Lam giong key:generate: thay dung MOT DONG bang regex, khong ghi lai ca tep. Tep nay
 * con chua cau hinh co so KCB va tai khoan cong BHXH cua tung ban cai - ghi lai ca tep
 * (nhu lenh config:add-keys dang lam) xoa sach chu thich va de lam mat cau hinh.
 */
class ApiGenerateToken extends Command
{
    protected $signature = 'api:generate {--force : Ghi de token cu khong hoi}';

    protected $description = 'Sinh token API moi, ghi ban bam SHA-256 vao config/organization.php';

    const KHOA = 'access_token_hash';

    public function handle()
    {
        $duongDan = $this->duongDanConfig();

        if (!is_file($duongDan)) {
            $this->error('Khong tim thay ' . $duongDan);

            return 1;
        }

        $noiDung = file_get_contents($duongDan);

        // Chi THAY, khong CHEN: ban cai cu thieu khoa nay la tinh huong that (tep khong
        // nam trong git). Doan cho chen vao mot tep bi mat thu cong la cach nhanh nhat
        // lam hong no - bao de nguoi van hanh tu sua dung cho.
        if (!preg_match($this->mauKhoa(), $noiDung, $khop)) {
            $this->error('Khong tim thay khoa \'' . self::KHOA . '\' trong ' . $duongDan);
            $this->line('Them thu cong dong sau vao muc api roi chay lai:');
            $this->line('    \'' . self::KHOA . '\' => \'\',');

            return 1;
        }

        if ($khop[1] !== '' && !$this->option('force')
            && !$this->confirm('Da co token. Ghi de se cat dut moi ben dang goi. Tiep tuc?')) {
            $this->line('Da huy.');

            return 1;
        }

        $token = bin2hex(random_bytes(32));

        file_put_contents($duongDan, preg_replace(
            $this->mauKhoa(),
            '\'' . self::KHOA . '\' => \'' . hash('sha256', $token) . '\'',
            $noiDung,
            1
        ));

        // Khong xoa cache thi hash moi nam im trong tep con ung dung van dung ban cu.
        if (is_file($this->duongDanCacheConfig())) {
            $this->call('config:clear');
        }

        $this->info('Token API moi (chep ngay, khong hien lai):');
        $this->line('  ' . $token);
        $this->info('Da ghi hash vao ' . $duongDan);

        return 0;
    }

    protected function mauKhoa()
    {
        return '/\'' . self::KHOA . '\'\s*=>\s*\'([^\']*)\'/';
    }

    /** Tach rieng de test ghi vao tep tam, khong cham config that. */
    protected function duongDanConfig()
    {
        return config_path('organization.php');
    }

    protected function duongDanCacheConfig()
    {
        return base_path('bootstrap/cache/config.php');
    }
}
```

- [ ] **Step 4: Chạy test để xác nhận xanh**

Run: `vendor/bin/phpunit tests/Unit/ApiGenerateTokenTest.php`
Expected: PASS — 5 tests.

- [ ] **Step 5: Kiểm tra lệnh xuất hiện trong danh sách artisan**

Run: `php artisan list 2>&1 | grep api:generate`
Expected: thấy dòng `api:generate`. Kernel đã tự nạp thư mục `app/Console/Commands` (`app/Console/Kernel.php:38`), không cần đăng ký thủ công.

- [ ] **Step 6: Commit (chưa đụng config thật)**

```bash
git add app/Console/Commands/ApiGenerateToken.php tests/Unit/ApiGenerateTokenTest.php
git commit -m "feat(api): lenh api:generate sinh token va ghi ban bam vao config"
```

- [ ] **Step 7: Sửa `config/organization.php` trên máy này (KHÔNG commit)**

Mở `config/organization.php`, trong mục `'api' => [`:

- Xoá dòng `'access_token' => '8f14e45fceea167a5a36dedd4bea2543',`
- Thêm vào đúng chỗ đó: `'access_token_hash' => '',`

Tệp nằm trong `.gitignore`; kiểm chứng bằng `git status --short` — **không được** thấy `config/organization.php`. Nếu thấy, dừng lại và kiểm tra `.gitignore`.

- [ ] **Step 8: Sinh token thật**

Run: `php artisan api:generate --force`
Expected: in ra token 64 ký tự hex và dòng `Da ghi hash vao ...`. Chép token cất vào nơi an toàn — không hiện lại.

- [ ] **Step 9: Kiểm chứng tệp config vẫn nguyên vẹn**

Run: `php artisan config:clear` rồi `php -l config/organization.php`
Expected: `No syntax errors detected`. Mở tệp xem nhanh: các chú thích và cấu hình cơ sở KCB còn nguyên, chỉ dòng `access_token_hash` đổi.

---

### Task 3: Index `treatment_code`

**Files:**
- Create: `database/migrations/2026_08_06_100000_them_index_treatment_code_vao_order_check_violations.php`

**Interfaces:**
- Consumes: bảng `order_check_violations` (MySQL, kết nối mặc định).
- Produces: index tên `order_check_violations_treatment_code_index` (tên mặc định Laravel sinh ra).

Không có test tự động: SQLite trong test không phản ánh index của MySQL. Nghiệm thu bằng SQL ở Step 3.

- [ ] **Step 1: Viết migration**

Tạo `database/migrations/2026_08_06_100000_them_index_treatment_code_vao_order_check_violations.php`:

```php
<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * API tra cuu loi loc theo treatment_code, nhung bang chi co index tren treatment_id -
 * moi lan goi la mot lan quet toan bang.
 *
 * Chi index don. Composite (treatment_code, status) de lai toi khi do thay can: loc
 * treatment_code truoc thi so dong con lai cua mot dot dieu tri von rat nho.
 */
class ThemIndexTreatmentCodeVaoOrderCheckViolations extends Migration
{
    const TEN = 'order_check_violations_treatment_code_index';

    public function up()
    {
        if ($this->coIndex()) {
            return;
        }

        Schema::table('order_check_violations', function (Blueprint $t) {
            $t->index('treatment_code');
        });
    }

    public function down()
    {
        if (!$this->coIndex()) {
            return;
        }

        Schema::table('order_check_violations', function (Blueprint $t) {
            $t->dropIndex(['treatment_code']);
        });
    }

    /**
     * Doc het roi so trong PHP thay vi SHOW INDEX ... WHERE: menh de WHERE cua SHOW
     * khong nhan tham so gan san mot cach dang tin cay tren moi phien ban MySQL.
     */
    protected function coIndex()
    {
        foreach (DB::select('SHOW INDEX FROM order_check_violations') as $dong) {
            if ($dong->Key_name === self::TEN) {
                return true;
            }
        }

        return false;
    }
}
```

- [ ] **Step 2: Chạy migration**

Run: `php artisan migrate`
Expected: thấy dòng `Migrated: 2026_08_06_100000_them_index_treatment_code_vao_order_check_violations`.

- [ ] **Step 3: Nghiệm thu index có thật**

Chạy trên CSDL `qlbv`:

```sql
SHOW INDEX FROM order_check_violations WHERE Column_name = 'treatment_code';
```

Expected: đúng một dòng, `Key_name = order_check_violations_treatment_code_index`.

- [ ] **Step 4: Kiểm tra chạy lại không lỗi**

Run: `php artisan migrate:rollback --step=1` rồi `php artisan migrate`
Expected: cả hai lệnh chạy sạch; `SHOW INDEX` ở Step 3 lại cho đúng một dòng.

- [ ] **Step 5: Commit**

```bash
git add database/migrations/2026_08_06_100000_them_index_treatment_code_vao_order_check_violations.php
git commit -m "perf(order-check): them index treatment_code cho order_check_violations"
```

---

### Task 4: Tài liệu cấp token

**Files:**
- Modify: `docs/order-check/API-TRA-CUU-LOI.md` (thêm mục sau mục 1 "Endpoint và xác thực")

**Interfaces:**
- Consumes: lệnh `api:generate` (Task 2), khoá config `access_token_hash` (Task 1).

- [ ] **Step 1: Thêm mục vào tài liệu**

Chèn vào `docs/order-check/API-TRA-CUU-LOI.md`, ngay sau bảng của mục "## 1. Endpoint và xác thực" và trước "## 2. Tham số":

```markdown
### Cấp token (dành cho quản trị)

```bash
php artisan api:generate
```

Lệnh sinh token ngẫu nhiên 64 ký tự, in ra màn hình **một lần**, và ghi bản băm SHA-256
của nó vào `config/organization.php`. Token gốc không được lưu ở đâu trong hệ thống —
chép ngay khi lệnh in ra để giao cho bên gọi; mất thì sinh lại chứ không xem lại được.

Sinh lại token làm mọi bên đang dùng token cũ nhận 401 ngay lập tức. Lệnh hỏi xác nhận
trước khi ghi đè; `--force` bỏ qua bước hỏi.

Bản cài chưa có khoá `access_token_hash` trong `config/organization.php` sẽ trả 401 cho
mọi request — thêm dòng `'access_token_hash' => '',` vào mục `api` rồi chạy lại lệnh.
```

- [ ] **Step 2: Đối chiếu tài liệu với hành vi thật của lệnh**

Đọc lại `app/Console/Commands/ApiGenerateToken.php`, xác nhận: độ dài token 64 ký tự, có `--force`, thông báo khi thiếu khoá đúng như tài liệu mô tả.

- [ ] **Step 3: Chạy toàn bộ bộ test và so với mốc**

Run: `vendor/bin/phpunit`
Expected: **8 errors / 6 failures** — bằng đúng mốc ghi ở Global Constraints, tổng số test tăng thêm 13 ca mới.

- [ ] **Step 4: Commit**

```bash
git add docs/order-check/API-TRA-CUU-LOI.md
git commit -m "docs(api): huong dan cap token bang api:generate"
```

---

## Sau khi hoàn thành

Còn lại trong spec gốc `docs/superpowers/specs/2026-08-06-order-check-api-gop-loi-design.md`:

- **Ưu tiên 2:** nhiều client + scope + throttle theo client + limiter chống dò.
- **Ưu tiên 3:** giới hạn theo cơ sở KCB, audit log, endpoint gọi theo lô.
- **Riêng:** ép HTTPS, phụ thuộc hạ tầng từng nơi cài.

Một việc phát sinh phát hiện khi viết kế hoạch, **không thuộc phạm vi này**: lệnh
`config:add-keys` (`app/Console/Commands/AddConfigOrganizationKey.php`) ghi lại toàn bộ
`config/organization.php` bằng `var_export`, xoá sạch chú thích và định dạng. Chạy nhầm
lệnh đó sẽ làm mất phần chú thích hướng dẫn cấu hình cơ sở KCB. Nên xử lý riêng.
