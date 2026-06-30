# Order Check — Plan 4: Thông báo Email digest định kỳ

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) hoặc superpowers:executing-plans. Steps dùng checkbox (`- [ ]`).

**Goal:** Gửi email digest định kỳ tổng hợp các vi phạm y lệnh MỚI (chưa thông báo) tới danh sách người nhận cố định (QLCL/admin), đánh dấu đã gửi để không lặp lại.

**Architecture:** Một command service-loop `kiemtraylenh:notify` (nssm, loop+sleep) gọi `OrderCheckNotifier`: lấy violation `notified_at IS NULL` + `status='new'` + severity ≥ ngưỡng cấu hình → render 1 email digest (Blade) → gửi tới các email `active` trong `email_receive_report` (tái dùng hạ tầng đang gửi lỗi QĐ130) → set `notified_at`. Mặc định TẮT (`notify_enabled=false`) để an toàn khi chưa cấu hình.

**Tech Stack:** PHP 7 / Laravel 5.5, `Mail::send` + Blade template, Eloquent (MySQL), nssm service.

**Quyết định (đã chốt với người dùng):** Kênh = **Email**; người nhận = **danh sách cố định** (`email_receive_report` active=1); nhịp = **digest định kỳ**.

**Tham chiếu:** Plan 1–3 (đã commit). Mẫu gửi mail: `app/Console/Commands/sendQd130XmlErrors.php` (dùng `Mail::send('templates.mail-qd130-errors', ...)` + `email_receive_report::where('active',1)`). Mẫu loop service: `app/Console/Commands/TrucDuLieuYTeXmlScan.php`.

## Bối cảnh có sẵn (KHÔNG tạo lại)
- `App\Models\OrderCheck\OrderCheckViolation` (bảng `order_check_violations`), severity ∈ `info|warning|critical`, status ∈ `new|seen|processed|false_positive`.
- `App\Models\System\email_receive_report` (cột `email`, `active`).
- `config/order_check.php` (Plan 1) — sẽ thêm khóa notify.
- Thư mục `resources/views/templates/` đã có (vd `mail-qd130-errors`).
- `install_service.bat` + `update.bat` (Plan 1) — có khối ensure-install idempotent; sẽ thêm service notify.

## Ngoài phạm vi (Plan sau)
- SMS / Telegram (Plan 4b nếu cần) — đã chốt chỉ Email cho Plan 4.
- Gửi đích danh trưởng khoa / bác sĩ — Plan sau (cần map khoa→người nhận / dùng `HIS_EMPLOYEE.TDL_EMAIL`).

## File Structure (Plan 4)
**Tạo mới:**
- `database/migrations/2026_06_30_140000_add_notified_at_to_order_check_violations.php`
- `app/Services/OrderCheck/OrderCheckNotifier.php`
- `resources/views/templates/mail-order-check-digest.blade.php`
- `app/Console/Commands/HISProKiemTraYLenhNotify.php`
- `tests/Unit/OrderCheck/OrderCheckNotifierTest.php`
**Sửa:**
- `config/order_check.php` (thêm khóa notify)
- `install_service.bat` + `update.bat` (thêm service notify)
- `readme.md`

---

## Task 1: Migration thêm cột `notified_at`

**Files:**
- Create: `database/migrations/2026_06_30_140000_add_notified_at_to_order_check_violations.php`

- [ ] **Step 1: Tạo migration**

```php
<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddNotifiedAtToOrderCheckViolations extends Migration
{
    public function up()
    {
        Schema::table('order_check_violations', function (Blueprint $table) {
            $table->dateTime('notified_at')->nullable()->after('processed_at');
            $table->index('notified_at');
        });
    }

    public function down()
    {
        Schema::table('order_check_violations', function (Blueprint $table) {
            $table->dropIndex(['notified_at']);
            $table->dropColumn('notified_at');
        });
    }
}
```

- [ ] **Step 2: Chạy migrate**

Run: `php artisan migrate`
Expected: `Migrated: 2026_06_30_140000_add_notified_at_to_order_check_violations`

- [ ] **Step 3: Commit** (bỏ qua nếu được yêu cầu)

```bash
git add database/migrations/2026_06_30_140000_add_notified_at_to_order_check_violations.php
git commit -m "feat(order-check): them cot notified_at cho violations"
```

---

## Task 2: Config khóa notify

**Files:**
- Modify: `config/order_check.php`

- [ ] **Step 1: Thêm khóa**

Thêm các dòng sau vào mảng trả về của `config/order_check.php` (trước dấu `];` cuối):

```php
    // ===== Thông báo email digest =====
    // Bật/tắt gửi email (mặc định TẮT cho an toàn, bật khi đã cấu hình người nhận)
    'notify_enabled' => (bool) env('ORDER_CHECK_NOTIFY_ENABLED', false),

    // Ngưỡng mức độ tối thiểu được thông báo: info | warning | critical
    'notify_min_severity' => env('ORDER_CHECK_NOTIFY_MIN_SEVERITY', 'warning'),

    // Khoảng nghỉ giữa 2 lần gửi digest khi chạy service nssm (giây). Mặc định 1 giờ.
    'notify_sleep_interval' => (int) env('ORDER_CHECK_NOTIFY_SLEEP', 3600),
```

- [ ] **Step 2: Verify**

Run: `php -l config/order_check.php`
Expected: `No syntax errors detected`

- [ ] **Step 3: Commit** (bỏ qua nếu được yêu cầu)

```bash
git add config/order_check.php
git commit -m "feat(order-check): config notify email digest"
```

---

## Task 3: OrderCheckNotifier + test ngưỡng severity

**Files:**
- Create: `app/Services/OrderCheck/OrderCheckNotifier.php`
- Test: `tests/Unit/OrderCheck/OrderCheckNotifierTest.php`

- [ ] **Step 1: Viết test thất bại (ngưỡng severity)**

```php
<?php

namespace Tests\Unit\OrderCheck;

use Tests\TestCase;
use App\Services\OrderCheck\OrderCheckNotifier;

class OrderCheckNotifierTest extends TestCase
{
    public function test_nguong_warning_lay_warning_va_critical()
    {
        $n = new OrderCheckNotifier();
        $s = $n->severitiesToNotify('warning');
        $this->assertContains('warning', $s);
        $this->assertContains('critical', $s);
        $this->assertNotContains('info', $s);
    }

    public function test_nguong_critical_chi_lay_critical()
    {
        $n = new OrderCheckNotifier();
        $this->assertSame(['critical'], array_values($n->severitiesToNotify('critical')));
    }

    public function test_nguong_info_lay_tat_ca()
    {
        $n = new OrderCheckNotifier();
        $s = $n->severitiesToNotify('info');
        $this->assertContains('info', $s);
        $this->assertContains('warning', $s);
        $this->assertContains('critical', $s);
    }

    public function test_nguong_la_default_warning_neu_khong_hop_le()
    {
        $n = new OrderCheckNotifier();
        $s = $n->severitiesToNotify('xyz');
        $this->assertContains('warning', $s);
        $this->assertNotContains('info', $s);
    }
}
```

- [ ] **Step 2: Chạy test, xác nhận FAIL**

Run: `vendor/bin/phpunit --filter OrderCheckNotifierTest`
Expected: FAIL ("Class '...OrderCheckNotifier' not found")

- [ ] **Step 3: Cài đặt notifier**

```php
<?php

namespace App\Services\OrderCheck;

use App\Models\OrderCheck\OrderCheckViolation;
use App\Models\System\email_receive_report;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class OrderCheckNotifier
{
    /** Thứ hạng mức độ (lớn hơn = nặng hơn). */
    const SEVERITY_RANK = ['info' => 1, 'warning' => 2, 'critical' => 3];

    /**
     * Danh sách severity ≥ ngưỡng. Ngưỡng không hợp lệ → mặc định 'warning'.
     * @return string[]
     */
    public function severitiesToNotify($min)
    {
        $minRank = isset(self::SEVERITY_RANK[$min]) ? self::SEVERITY_RANK[$min] : self::SEVERITY_RANK['warning'];
        $out = [];
        foreach (self::SEVERITY_RANK as $sev => $rank) {
            if ($rank >= $minRank) {
                $out[] = $sev;
            }
        }
        return $out;
    }

    /** Vi phạm mới chưa thông báo, theo ngưỡng cấu hình. */
    public function pendingViolations()
    {
        $severities = $this->severitiesToNotify(config('order_check.notify_min_severity'));
        return OrderCheckViolation::whereNull('notified_at')
            ->where('status', 'new')
            ->whereIn('severity', $severities)
            ->orderByRaw("FIELD(severity,'critical','warning','info')")
            ->orderBy('detected_at')
            ->get();
    }

    /** Email người nhận (active). */
    public function recipients()
    {
        return email_receive_report::where('active', 1)
            ->whereNotNull('email')
            ->pluck('email')
            ->filter()
            ->values()
            ->all();
    }

    /**
     * Gửi 1 lượt digest. Trả mảng tóm tắt.
     * @return array
     */
    public function run()
    {
        if (!config('order_check.notify_enabled')) {
            return ['status' => 'disabled', 'count' => 0, 'recipients' => 0];
        }

        $vios = $this->pendingViolations();
        if ($vios->isEmpty()) {
            return ['status' => 'empty', 'count' => 0, 'recipients' => 0];
        }

        $emails = $this->recipients();
        if (empty($emails)) {
            // Không có người nhận → KHÔNG đánh dấu, để gửi khi đã cấu hình.
            return ['status' => 'no_recipients', 'count' => $vios->count(), 'recipients' => 0];
        }

        $data = [
            'violations' => $vios,
            'total' => $vios->count(),
            'critical' => $vios->where('severity', 'critical')->count(),
            'warning' => $vios->where('severity', 'warning')->count(),
            'info' => $vios->where('severity', 'info')->count(),
            'generatedAt' => Carbon::now()->format('d/m/Y H:i'),
        ];

        $subject = '[Sai sót y lệnh] ' . $data['total'] . ' vi phạm mới (' . $data['generatedAt'] . ')';

        foreach ($emails as $email) {
            Mail::send('templates.mail-order-check-digest', $data, function ($message) use ($email, $subject) {
                $message->to($email)->subject($subject);
            });
        }

        OrderCheckViolation::whereIn('id', $vios->pluck('id')->all())
            ->update(['notified_at' => Carbon::now()]);

        Log::info('Order check digest sent', ['count' => $data['total'], 'recipients' => count($emails)]);

        return ['status' => 'sent', 'count' => $data['total'], 'recipients' => count($emails)];
    }
}
```

- [ ] **Step 4: Chạy test, xác nhận PASS**

Run: `vendor/bin/phpunit --filter OrderCheckNotifierTest`
Expected: PASS (4 tests)

- [ ] **Step 5: Commit** (bỏ qua nếu được yêu cầu)

```bash
git add app/Services/OrderCheck/OrderCheckNotifier.php tests/Unit/OrderCheck/OrderCheckNotifierTest.php
git commit -m "feat(order-check): OrderCheckNotifier (digest email theo nguong severity)"
```

---

## Task 4: Blade template digest

**Files:**
- Create: `resources/views/templates/mail-order-check-digest.blade.php`

- [ ] **Step 1: Tạo template**

```blade
<h2>Cảnh báo sai sót y lệnh</h2>
<p>Tổng hợp lúc {{ $generatedAt }} — <b>{{ $total }}</b> vi phạm mới
  (Nghiêm trọng: <b style="color:#dd4b39">{{ $critical }}</b>,
   Cảnh báo: <b style="color:#f39c12">{{ $warning }}</b>,
   Thông tin: {{ $info }}).</p>

<table border="1" cellpadding="6" cellspacing="0" style="border-collapse:collapse;font-size:13px">
  <thead>
    <tr style="background:#f4f4f4">
      <th>Thời điểm</th><th>Mức độ</th><th>Mã ĐT</th><th>Bệnh nhân</th>
      <th>Bác sĩ</th><th>Khoa (ID)</th><th>Nội dung</th>
    </tr>
  </thead>
  <tbody>
    @foreach($violations as $v)
    <tr>
      <td>{{ $v->detected_at }}</td>
      <td>{{ $v->severity }}</td>
      <td>{{ $v->treatment_code }}</td>
      <td>{{ $v->patient_name }} ({{ $v->patient_code }})</td>
      <td>{{ $v->doctor_username ?: $v->doctor_loginname }}</td>
      <td>{{ $v->department_id }}</td>
      <td>{{ $v->message }}</td>
    </tr>
    @endforeach
  </tbody>
</table>

<p style="color:#888;font-size:12px">Email tự động từ hệ thống Kiểm tra sai sót y lệnh. Vui lòng đăng nhập phần mềm (KHTH → Kiểm tra sai sót y lệnh) để xử lý.</p>
```

- [ ] **Step 2: Commit** (bỏ qua nếu được yêu cầu)

```bash
git add resources/views/templates/mail-order-check-digest.blade.php
git commit -m "feat(order-check): template email digest"
```

---

## Task 5: Command `kiemtraylenh:notify` (loop+sleep)

**Files:**
- Create: `app/Console/Commands/HISProKiemTraYLenhNotify.php`

- [ ] **Step 1: Tạo command**

```php
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use App\Services\OrderCheck\OrderCheckNotifier;

class HISProKiemTraYLenhNotify extends Command
{
    protected $signature = 'kiemtraylenh:notify {--once : Chay 1 lan roi thoat (mac dinh lap lien tuc cho nssm service)}';

    protected $description = 'Gui email digest cac vi pham y lenh moi (theo chu ky)';

    public function handle(OrderCheckNotifier $notifier)
    {
        if ($this->option('once')) {
            $this->runOnce($notifier);
            return 0;
        }

        $sleep = (int) config('order_check.notify_sleep_interval', 3600);
        $this->info("Bat dau gui digest dinh ky, sleep {$sleep}s");

        do {
            try {
                $this->runOnce($notifier);
            } catch (\Exception $e) {
                $this->error('Loi: ' . $e->getMessage());
                Log::error('Order check notify error', ['error' => $e->getMessage()]);
            }
            sleep($sleep);
        } while (true);
    }

    protected function runOnce(OrderCheckNotifier $notifier)
    {
        $r = $notifier->run();
        $this->info(sprintf('Digest: %s, %d vi pham, %d nguoi nhan', $r['status'], $r['count'], $r['recipients']));
    }
}
```

- [ ] **Step 2: Verify command đăng ký**

Run: `php -l app/Console/Commands/HISProKiemTraYLenhNotify.php`
Expected: `No syntax errors detected`

Run: `php artisan list 2>&1 | grep kiemtraylenh`
Expected: thấy cả `kiemtraylenh:scan` và `kiemtraylenh:notify`.

- [ ] **Step 3: Chạy thử (mặc định TẮT nên trả 'disabled')**

Run: `php artisan kiemtraylenh:notify --once`
Expected: in `Digest: disabled, 0 vi pham, 0 nguoi nhan` (vì `notify_enabled=false`). Không gửi email, không lỗi.

- [ ] **Step 4: Commit** (bỏ qua nếu được yêu cầu)

```bash
git add app/Console/Commands/HISProKiemTraYLenhNotify.php
git commit -m "feat(order-check): command kiemtraylenh:notify (loop+sleep)"
```

---

## Task 6: Đăng ký service nssm (install_service.bat + update.bat)

**Files:**
- Modify: `install_service.bat`
- Modify: `update.bat`

- [ ] **Step 1: Thêm vào `install_service.bat`**

Sau khối install service "QLBV KiemTraYLenh" (đã có), thêm:

```bat
:: Tạo dịch vụ cho kiemtraylenh:notify (Gửi email digest sai sót y lệnh)
%NSSM_PATH%\nssm install "QLBV KiemTraYLenhNotify" %PHP_PATH% "%LARAVEL_PATH%artisan kiemtraylenh:notify"
%NSSM_PATH%\nssm set "QLBV KiemTraYLenhNotify" AppDirectory %LARAVEL_PATH%
```

Và thêm dòng start (cạnh `nssm start "QLBV KiemTraYLenh"`):

```bat
%NSSM_PATH%\nssm start "QLBV KiemTraYLenhNotify"
```

- [ ] **Step 2: Thêm khối ensure-install vào `update.bat`**

Trong `update.bat`, sau khối ensure-install của "QLBV KiemTraYLenh", thêm:

```bat
%NSSM_PATH%\nssm status "QLBV KiemTraYLenhNotify" >nul 2>&1
if errorlevel 1 (
    echo Installing service QLBV KiemTraYLenhNotify...
    %NSSM_PATH%\nssm install "QLBV KiemTraYLenhNotify" %PHP_PATH% "%LARAVEL_PATH%artisan kiemtraylenh:notify"
    %NSSM_PATH%\nssm set "QLBV KiemTraYLenhNotify" AppDirectory %LARAVEL_PATH%
)
```

Và thêm stop/start cho service này (cạnh các dòng stop/start "QLBV KiemTraYLenh"):
- Trong khối stop: `%NSSM_PATH%\nssm stop "QLBV KiemTraYLenhNotify"`
- Trong khối start: `%NSSM_PATH%\nssm start "QLBV KiemTraYLenhNotify"`

- [ ] **Step 3: Commit** (bỏ qua nếu được yêu cầu)

```bash
git add install_service.bat update.bat
git commit -m "feat(deploy): them service nssm kiemtraylenh:notify"
```

---

## Task 7: Regression + readme

**Files:**
- Modify: `readme.md`

- [ ] **Step 1: Regression Unit OrderCheck**

Run: `vendor/bin/phpunit tests/Unit/OrderCheck`
Expected: PASS toàn bộ (19 cũ + OrderCheckNotifier: 4 = 23 tests).

- [ ] **Step 2: Cập nhật readme**

Chèn vào đầu `readme.md` (trên khối ngày gần nhất):

```markdown
# 30/06/2026 (cập nhật 3)

- Module Kiểm tra sai sót y lệnh (giai đoạn 4): gửi email digest định kỳ các vi phạm mới tới danh sách người nhận (email_receive_report), theo ngưỡng mức độ cấu hình; chạy bằng service `kiemtraylenh:notify`. Mặc định TẮT (bật qua ORDER_CHECK_NOTIFY_ENABLED).

```

- [ ] **Step 3: Commit** (bỏ qua nếu được yêu cầu)

```bash
git add readme.md
git commit -m "docs(order-check): readme giai doan 4 (email digest)"
```

---

## Hướng dẫn bật thông báo (vận hành)
1. Thêm người nhận vào bảng `email_receive_report` (`email`, `active=1`).
2. Đảm bảo `config/mail.php` đã cấu hình SMTP (đang dùng cho email QĐ130).
3. Set env: `ORDER_CHECK_NOTIFY_ENABLED=true` (và tùy chọn `ORDER_CHECK_NOTIFY_MIN_SEVERITY`, `ORDER_CHECK_NOTIFY_SLEEP`).
4. `update.bat` sẽ tự cài + chạy service `QLBV KiemTraYLenhNotify`.

## Self-Review (đã thực hiện khi viết plan)

**1. Spec coverage (Plan 4 = thông báo, đã chốt Email/digest/danh sách cố định):**
- Email digest định kỳ → Task 3 Notifier + Task 5 command loop. ✅
- Danh sách cố định → `email_receive_report` (Task 3 recipients). ✅
- Chống lặp/spam → `notified_at` (Task 1) + đánh dấu sau khi gửi (Task 3) + gộp 1 email/chu kỳ. ✅
- Ngưỡng mức độ cấu hình → Task 2 config + Task 3 severitiesToNotify (có test). ✅
- An toàn mặc định → `notify_enabled=false`. ✅
- Tự cài service → Task 6. ✅

**2. Placeholder scan:** mọi step có code/lệnh + kỳ vọng. Không stub.

**3. Type consistency:** `OrderCheckNotifier::run()` trả `['status','count','recipients']` — khớp command (Task 5). `severitiesToNotify()` (Task 3) khớp test (Task 3). Cột `notified_at` (Task 1) dùng ở Notifier (Task 3). Template biến `$violations,$total,$critical,$warning,$info,$generatedAt` (Task 3 data) khớp blade (Task 4). ✅

**4. Lưu ý:** `orderByRaw FIELD(...)` là cú pháp MySQL (đúng — violations ở MySQL). `notify_enabled=false` nên Task 5 Step 3 trả 'disabled' (không gửi thật khi test). Gửi email thật chỉ verify khi bật + có SMTP + người nhận (vận hành), không tự động trong CI.
