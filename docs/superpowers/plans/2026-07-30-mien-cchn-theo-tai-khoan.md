# Miễn kiểm tra CCHN theo tài khoản thực hiện — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Quy tắc `B_DOCTOR_NO_PRACTICE_CERT` bỏ qua các tài khoản tích hợp máy móc (`mitalab`, `vietrad`, `sys`) — chúng không phải người nên không thể có chứng chỉ hành nghề.

**Architecture:** Thêm một khoá cấu hình CSV liệt kê tài khoản được miễn, tách phần đọc và so khớp thành hàm thuần `DsMienCchn` để kiểm thử được, rồi cho `DoctorPracticeCertRule` hỏi nó trước khi báo vi phạm.

**Tech Stack:** Laravel 5.5.50, PHP 7.4, PHPUnit 6.5.

## Global Constraints

- Cổng kiểm thử: `vendor/bin/phpunit --testsuite Unit`. **KHÔNG** chạy `tests/Feature` — đỏ sẵn vì lý do môi trường, không liên quan.
- Comment trong code PHP viết tiếng Việt **không dấu**; chuỗi hiển thị cho người dùng viết **có dấu**.
- Kết nối Oracle `HISPro` và MySQL đều là **production thật của bệnh viện**. Mọi phép đo chỉ được dùng `SELECT`/`count()`. **Không** `INSERT`/`UPDATE`/`DELETE`, không migration.
- Danh sách mặc định: `mitalab,vietrad,sys`. Giá trị **rỗng nghĩa là không miễn ai**.
- So khớp **không phân biệt hoa thường** và **cắt khoảng trắng hai đầu** ở cả hai vế.
- **Chỉ** áp cho `B_DOCTOR_NO_PRACTICE_CERT`. **Không** đụng `A_STAFF_CERT_NOT_IN_CATALOG` — người dùng đã chốt điều này ngày 2026-07-28.
- **Không** đụng `practice_cert_exclude_type_ids` đang có.
- **Không** xoá vi phạm cũ của ba tài khoản đó.
- **Không** bật/tắt quy tắc nào.

## Cấu trúc tệp

| Tệp | Trách nhiệm |
| --- | --- |
| `app/Services/OrderCheck/Support/DsMienCchn.php` (tạo) | Hàm thuần: đọc CSV, so khớp tài khoản |
| `config/order_check.php` (sửa) | Khoá `practice_cert_exclude_loginnames` |
| `app/Services/OrderCheck/RuleHandlers/Structural/DoctorPracticeCertRule.php` (sửa) | Hỏi `DsMienCchn` trước khi báo |
| `tests/Unit/DsMienCchnTest.php` (tạo) | Kiểm hàm thuần + giá trị mặc định của cấu hình |
| `tests/Unit/DoctorPracticeCertRuleTest.php` (tạo) | Kiểm hành vi quy tắc |

---

### Task 1: Hàm thuần DsMienCchn và cấu hình

**Files:**
- Create: `app/Services/OrderCheck/Support/DsMienCchn.php`
- Modify: `config/order_check.php`
- Test: `tests/Unit/DsMienCchnTest.php`

**Interfaces:**
- Consumes: không có gì từ task khác.
- Produces: `App\Services\OrderCheck\Support\DsMienCchn::doc($csv)` trả `array` các loginname đã hạ thường và cắt khoảng trắng; `DsMienCchn::duocMien($loginname, array $ds)` trả `bool`. Khoá config `order_check.practice_cert_exclude_loginnames`. Task 2 dùng cả hai.

- [ ] **Step 1: Viết test đỏ**

Tạo `tests/Unit/DsMienCchnTest.php`:

```php
<?php

namespace Tests\Unit;

use App\Services\OrderCheck\Support\DsMienCchn;
use Tests\TestCase;

class DsMienCchnTest extends TestCase
{
    /** @test */
    public function doc_csv_thanh_mang()
    {
        $this->assertSame(['mitalab', 'vietrad', 'sys'], DsMienCchn::doc('mitalab,vietrad,sys'));
    }

    /** @test */
    public function csv_rong_thi_mang_rong()
    {
        $this->assertSame([], DsMienCchn::doc(''));
        $this->assertSame([], DsMienCchn::doc(null));
        $this->assertSame([], DsMienCchn::doc('   '));
    }

    /** @test */
    public function doc_cat_khoang_trang_va_ha_thuong()
    {
        // HIS co tai khoan viet hoa lan lon (BHXHConnector, BMCS, PACS) nen phai chuan hoa,
        // neu khong nguoi dung khai 'pacs' ma he thong van bao vi pham cho 'PACS'.
        $this->assertSame(['mitalab', 'vietrad'], DsMienCchn::doc(' Mitalab , VIETRAD '));
    }

    /** @test */
    public function doc_bo_phan_tu_rong()
    {
        $this->assertSame(['mitalab', 'sys'], DsMienCchn::doc('mitalab,,sys,'));
    }

    /** @test */
    public function tai_khoan_trong_danh_sach_thi_duoc_mien()
    {
        $this->assertTrue(DsMienCchn::duocMien('mitalab', ['mitalab']));
    }

    /** @test */
    public function so_khop_khong_phan_biet_hoa_thuong()
    {
        $this->assertTrue(DsMienCchn::duocMien('MitaLab', ['mitalab']));
    }

    /** @test */
    public function so_khop_cat_khoang_trang()
    {
        $this->assertTrue(DsMienCchn::duocMien('  mitalab  ', ['mitalab']));
    }

    /**
     * Nguoi THAT van phai bi kiem. ntdh3 la Nguyen Thi Dieu Hang, vttq2 la Vo Thi Thuy
     * Quynh - ho thieu CCHN trong HIS, do la phat hien DUNG cua quy tac.
     */
    /** @test */
    public function nguoi_that_khong_duoc_mien()
    {
        $this->assertFalse(DsMienCchn::duocMien('ntdh3', ['mitalab', 'vietrad', 'sys']));
        $this->assertFalse(DsMienCchn::duocMien('vttq2', ['mitalab', 'vietrad', 'sys']));
    }

    /** @test */
    public function danh_sach_rong_thi_khong_mien_ai()
    {
        $this->assertFalse(DsMienCchn::duocMien('mitalab', []));
    }

    /** @test */
    public function loginname_rong_thi_khong_duoc_mien()
    {
        $this->assertFalse(DsMienCchn::duocMien(null, ['mitalab']));
        $this->assertFalse(DsMienCchn::duocMien('', ['mitalab']));
    }

    /** @test */
    public function cau_hinh_mac_dinh_co_ba_tai_khoan()
    {
        $ds = DsMienCchn::doc(config('order_check.practice_cert_exclude_loginnames'));

        sort($ds);

        $this->assertSame(['mitalab', 'sys', 'vietrad'], $ds);
    }
}
```

- [ ] **Step 2: Chạy test, xác nhận đỏ**

```bash
vendor/bin/phpunit tests/Unit/DsMienCchnTest.php
```

Kỳ vọng: mọi test FAIL với `Class 'App\Services\OrderCheck\Support\DsMienCchn' not found`.

- [ ] **Step 3: Viết lớp**

Tạo `app/Services/OrderCheck/Support/DsMienCchn.php`:

```php
<?php

namespace App\Services\OrderCheck\Support;

/**
 * Danh sach tai khoan duoc MIEN kiem tra chung chi hanh nghe.
 *
 * Vi sao can: mot so "nguoi thuc hien" trong HIS khong phai nguoi ma la tai khoan tich hop
 * may moc - mitalab (may xet nghiem), vietrad (chan doan hinh anh), sys (he thong). Chung
 * khong the co CCHN nen quy tac B_DOCTOR_NO_PRACTICE_CERT bao vi pham cho chung la vo nghia.
 *
 * Do ngay 30/07/2026: 5.422 vi pham cua quy tac nay thi mitalab 4.310, vietrad 1.066,
 * sys 4 - tuc 99,2% la nhieu. Phan con lai (ntdh3, vttq2 va hai nguoi khac) deu la NGUOI
 * THAT thieu CCHN trong HIS, tuc phat hien dung, khong duoc mien.
 *
 * Vi sao dung DANH SACH TUONG MINH chu khong tu nhan dien: da thu quy tac "tdl_username =
 * loginname" thi ra 32 tai khoan, lan lon ca tai khoan thu nghiem (demo1, ddtest), tai
 * khoan phong ban (noitru, vss - co diploma 'CNTT'), va admin/fpt. Tu dong se IM LANG bo
 * qua nhung thu khong nen bo, va nguoi bao tri sau khong co cach nao biet ai dang duoc mien.
 *
 * Ham THUAN de kiem duoc.
 */
class DsMienCchn
{
    /**
     * Doc CSV thanh mang loginname da chuan hoa.
     *
     * @param string|null $csv
     * @return array loginname da ha thuong, da cat khoang trang, bo phan tu rong
     */
    public static function doc($csv)
    {
        $csv = trim((string) $csv);

        if ($csv === '') {
            return [];
        }

        $ra = [];

        foreach (explode(',', $csv) as $ten) {
            $ten = mb_strtolower(trim($ten));

            if ($ten !== '') {
                $ra[] = $ten;
            }
        }

        return $ra;
    }

    /**
     * Loginname co duoc mien kiem tra CCHN khong.
     *
     * So khop KHONG phan biet hoa thuong va cat khoang trang ca hai ve: HIS co tai khoan
     * viet hoa lan lon (BHXHConnector, BMCS, PACS) nen so khop chat se bo sot IM LANG.
     *
     * @param string|null $loginname
     * @param array $ds danh sach da qua doc()
     * @return bool
     */
    public static function duocMien($loginname, array $ds)
    {
        if (empty($ds)) {
            return false;
        }

        $loginname = mb_strtolower(trim((string) $loginname));

        if ($loginname === '') {
            return false;
        }

        return in_array($loginname, $ds, true);
    }
}
```

> **Cập nhật sau khi triển khai (commit `31b9830`)**: khối `duocMien()` ở trên chỉ chuẩn hoá
> **một** vế (`$loginname`), còn `$ds` giữ nguyên. Bản đã triển khai chuẩn hoá **cả hai** vế
> (lặp qua `$ds` và `mb_strtolower(trim(...))` từng phần tử trước khi so khớp), vì gọi trực
> tiếp `duocMien()` với danh sách chưa qua `doc()` sẽ bỏ sót IM LẶNG. Xem bản thật tại
> `app/Services/OrderCheck/Support/DsMienCchn.php`.

- [ ] **Step 4: Thêm khoá cấu hình**

Trong `config/order_check.php`, thêm ngay **sau** dòng `'practice_cert_exclude_type_ids' => ...`:

```php

    // Tai khoan nguoi thuc hien KHONG bi kiem CCHN, CSV loginname. RONG = khong mien ai.
    //
    // Mac dinh mitalab (tich hop may xet nghiem), vietrad (chan doan hinh anh), sys (he
    // thong). Day khong phai nguoi nen khong the co CCHN.
    //
    // Do ngay 30/07/2026: 5.422 vi pham B_DOCTOR_NO_PRACTICE_CERT thi ba tai khoan nay
    // chiem 5.380 (99,2%). Phan con lai deu la NGUOI THAT thieu CCHN trong HIS - phat hien
    // dung, khong duoc mien.
    //
    // So khop khong phan biet hoa thuong. CHI ap cho B_DOCTOR_NO_PRACTICE_CERT.
    'practice_cert_exclude_loginnames' => env('ORDER_CHECK_PRACTICE_CERT_EXCLUDE_LOGINS', 'mitalab,vietrad,sys'),
```

- [ ] **Step 5: Chạy test, xác nhận xanh**

```bash
vendor/bin/phpunit tests/Unit/DsMienCchnTest.php
```

Kỳ vọng: PASS cả 11 test.

- [ ] **Step 6: Kiểm cú pháp và chạy suite**

```bash
php -l app/Services/OrderCheck/Support/DsMienCchn.php && php -l config/order_check.php && vendor/bin/phpunit --testsuite Unit
```

Kỳ vọng: không lỗi cú pháp; suite Unit OK.

**Lưu ý về số test:** cây làm việc có thể đang chứa công việc khác chưa commit (các tệp import danh mục), khiến tổng số test của suite lệch so với kỳ vọng. Con số đáng tin cho task này là kết quả chạy riêng `DsMienCchnTest`. Nếu suite tổng đỏ ở tệp **không** thuộc task này, ghi lại trong báo cáo và đi tiếp — đừng sửa tệp của người khác.

- [ ] **Step 7: Commit**

```bash
git add app/Services/OrderCheck/Support/DsMienCchn.php config/order_check.php tests/Unit/DsMienCchnTest.php
git commit -m "feat(order-check): danh sach tai khoan duoc mien kiem tra CCHN"
```

---

### Task 2: Quy tắc hỏi danh sách miễn

**Files:**
- Modify: `app/Services/OrderCheck/RuleHandlers/Structural/DoctorPracticeCertRule.php`
- Test: `tests/Unit/DoctorPracticeCertRuleTest.php`

**Interfaces:**
- Consumes: `DsMienCchn::doc($csv)` và `DsMienCchn::duocMien($loginname, array $ds)` từ Task 1; khoá config `order_check.practice_cert_exclude_loginnames`.
- Produces: không có gì cho task sau.

**Bối cảnh lớp đang sửa:** hàm dựng hiện nhận `array $excludeTypeIds = null`; khi `null` thì tự đọc `order_check.practice_cert_exclude_type_ids`. Giữ nguyên khuôn đó cho danh sách mới — tham số thứ hai `array $excludeLoginnames = null`, `null` thì tự đọc config. Nhờ vậy test tiêm được danh sách mà không phải sửa config toàn cục.

- [ ] **Step 1: Viết test đỏ**

Tạo `tests/Unit/DoctorPracticeCertRuleTest.php`:

```php
<?php

namespace Tests\Unit;

use App\Services\OrderCheck\RuleHandlers\Structural\DoctorPracticeCertRule;
use App\Services\OrderCheck\Support\OrderContext;
use Tests\TestCase;

class DoctorPracticeCertRuleTest extends TestCase
{
    /** Ngu canh toi thieu de quy tac chay: co nguoi thuc hien, khong co CCHN */
    protected function ctx($loginname, $diploma = '')
    {
        $c = new OrderContext();
        $c->serviceReqId = 1;
        $c->serviceReqTypeId = 2;          // Xet nghiem - khong nam trong danh sach loai tru
        $c->executeLoginname = $loginname;
        $c->executeDiploma = $diploma;

        return $c;
    }

    /** @test */
    public function tai_khoan_duoc_mien_thi_khong_bao_vi_pham()
    {
        $rule = new DoctorPracticeCertRule([], ['mitalab', 'vietrad', 'sys']);

        $this->assertSame([], $rule->check($this->ctx('mitalab')));
        $this->assertSame([], $rule->check($this->ctx('vietrad')));
        $this->assertSame([], $rule->check($this->ctx('sys')));
    }

    /** @test */
    public function tai_khoan_duoc_mien_viet_hoa_van_duoc_mien()
    {
        $rule = new DoctorPracticeCertRule([], ['mitalab']);

        $this->assertSame([], $rule->check($this->ctx('MitaLab')));
    }

    /**
     * Nguoi THAT thieu CCHN van phai bi bao - do la phat hien dung cua quy tac.
     */
    /** @test */
    public function nguoi_khong_duoc_mien_thi_van_bao_vi_pham()
    {
        $rule = new DoctorPracticeCertRule([], ['mitalab', 'vietrad', 'sys']);

        $vi = $rule->check($this->ctx('ntdh3'));

        $this->assertCount(1, $vi);
        $this->assertSame('B_DOCTOR_NO_PRACTICE_CERT', $vi[0]->ruleCode);
    }

    /** @test */
    public function tai_khoan_duoc_mien_nhung_co_CCHN_thi_van_khong_bao()
    {
        // Khong doi hanh vi cu: co CCHN thi khong bao, du co nam trong danh sach mien.
        $rule = new DoctorPracticeCertRule([], ['mitalab']);

        $this->assertSame([], $rule->check($this->ctx('mitalab', 'CCHN-123')));
    }

    /** @test */
    public function danh_sach_mien_rong_thi_hanh_vi_y_het_truoc_day()
    {
        $rule = new DoctorPracticeCertRule([], []);

        $vi = $rule->check($this->ctx('mitalab'));

        $this->assertCount(1, $vi, 'Danh sach rong thi khong duoc mien ai');
    }

    /** @test */
    public function khong_co_nguoi_thuc_hien_thi_khong_bao()
    {
        $rule = new DoctorPracticeCertRule([], ['mitalab']);

        $this->assertSame([], $rule->check($this->ctx('')));
    }
}
```

- [ ] **Step 2: Chạy test, xác nhận đỏ**

```bash
vendor/bin/phpunit tests/Unit/DoctorPracticeCertRuleTest.php
```

Kỳ vọng: FAIL — hàm dựng hiện chỉ nhận một tham số nên các ca truyền hai tham số sẽ hỏng, và `tai_khoan_duoc_mien_thi_khong_bao_vi_pham` sẽ thấy vi phạm được sinh ra.

- [ ] **Step 3: Sửa quy tắc**

Trong `app/Services/OrderCheck/RuleHandlers/Structural/DoctorPracticeCertRule.php`:

Thêm `use App\Services\OrderCheck\Support\DsMienCchn;` vào cụm `use` ở đầu tệp.

Thêm thuộc tính sau `protected $excludeTypeIds;`:

```php
    /** @var string[] loginname nguoi thuc hien duoc mien kiem CCHN, da chuan hoa */
    protected $excludeLoginnames;
```

Đổi hàm dựng thành:

```php
    public function __construct(array $excludeTypeIds = null, array $excludeLoginnames = null)
    {
        if ($excludeTypeIds === null) {
            $csv = trim((string) config('order_check.practice_cert_exclude_type_ids', ''));
            $excludeTypeIds = $csv === '' ? [] : array_map('intval', array_filter(explode(',', $csv), 'strlen'));
        }

        if ($excludeLoginnames === null) {
            $excludeLoginnames = DsMienCchn::doc(config('order_check.practice_cert_exclude_loginnames'));
        }

        $this->excludeTypeIds = $excludeTypeIds;
        // Chuan hoa ca danh sach tiem tu ngoai vao, de test truyen 'MitaLab' van khop.
        $this->excludeLoginnames = DsMienCchn::doc(implode(',', $excludeLoginnames));
    }
```

Trong `check()`, thêm ngay **sau** khối kiểm `excludeTypeIds` và **trước** dòng `$hasExecutor = ...`:

```php
        // Tai khoan tich hop may moc (mitalab, vietrad, sys) khong phai nguoi nen khong the
        // co CCHN. Do 30/07/2026: chung chiem 99,2% vi pham cua quy tac nay.
        if (DsMienCchn::duocMien($c->executeLoginname, $this->excludeLoginnames)) {
            return [];
        }
```

- [ ] **Step 4: Chạy test, xác nhận xanh**

```bash
vendor/bin/phpunit tests/Unit/DoctorPracticeCertRuleTest.php
```

Kỳ vọng: PASS cả 6 test.

- [ ] **Step 5: Nghiệm thu bằng số — bắt buộc**

Đếm trên dữ liệu thật xem danh sách lọc đúng thứ cần lọc. **Chỉ `SELECT`**, không sửa gì:

```bash
php -r "require 'vendor/autoload.php'; \$a=require 'bootstrap/app.php'; \$a->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap(); \$ds = App\Services\OrderCheck\Support\DsMienCchn::doc(config('order_check.practice_cert_exclude_loginnames')); \$tong = DB::table('order_check_violations')->where('rule_code','B_DOCTOR_NO_PRACTICE_CERT')->count(); \$con = 0; \$conDs = []; foreach(DB::table('order_check_violations')->where('rule_code','B_DOCTOR_NO_PRACTICE_CERT')->select('detail')->get() as \$r){ \$d = json_decode(\$r->detail, true); \$ten = isset(\$d['execute_loginname']) ? \$d['execute_loginname'] : null; if (!App\Services\OrderCheck\Support\DsMienCchn::duocMien(\$ten, \$ds)) { \$con++; \$conDs[strtolower(trim((string)\$ten))] = true; } } printf('Tong vi pham hien co : %s'.PHP_EOL, number_format(\$tong)); printf('Con lai sau khi mien : %s'.PHP_EOL, number_format(\$con)); printf('Tai khoan con lai    : %s'.PHP_EOL, implode(', ', array_keys(\$conDs)));"
```

Kỳ vọng: danh sách tài khoản còn lại **không chứa** `mitalab`, `vietrad`, `sys`. Con số tuyệt đối sẽ lớn hơn lúc viết spec (5.422 / 42) vì bộ quét chạy mỗi 60 giây — **đừng chốt cứng con số**, điều phải đúng là ba tài khoản kia đã biến mất khỏi phần còn lại.

Chép nguyên văn output vào báo cáo.

- [ ] **Step 6: Kiểm cú pháp và chạy suite**

```bash
php -l app/Services/OrderCheck/RuleHandlers/Structural/DoctorPracticeCertRule.php && vendor/bin/phpunit --testsuite Unit
```

Kỳ vọng: không lỗi cú pháp; suite Unit OK. Nếu có tệp đỏ **không** thuộc task này (công việc import đang dở trong cây làm việc), ghi lại và đi tiếp — đừng sửa tệp của người khác.

- [ ] **Step 7: Commit**

```bash
git add app/Services/OrderCheck/RuleHandlers/Structural/DoctorPracticeCertRule.php tests/Unit/DoctorPracticeCertRuleTest.php
git commit -m "feat(order-check): mien kiem CCHN cho tai khoan tich hop may moc"
```

---

### Task 3: Cập nhật tài liệu

**Files:**
- Modify: `docs/tai-lieu-tong-hop-xml3176-order-check.md`

**Interfaces:**
- Consumes: kết quả Task 1-2.
- Produces: không có gì.

- [ ] **Step 1: Thêm ghi chú vào mục danh mục quy tắc**

Trong `docs/tai-lieu-tong-hop-xml3176-order-check.md`, tìm mục `### 3.4. Danh mục quy tắc (seed)` và chèn đoạn dưới đây vào **cuối mục đó**, ngay trước tiêu đề mục kế tiếp:

```markdown
> **`B_DOCTOR_NO_PRACTICE_CERT` — hai chiều miễn trừ** (cập nhật 30/07/2026): quy tắc này
> bỏ qua theo **loại phiếu** (`practice_cert_exclude_type_ids`, mặc định 6/14/15 — đơn
> phòng khám, đơn tủ trực, đơn điều trị) và theo **tài khoản người thực hiện**
> (`practice_cert_exclude_loginnames`, mặc định `mitalab,vietrad,sys`).
>
> Lý do chiều thứ hai: `mitalab` là tài khoản tích hợp máy xét nghiệm, `vietrad` là chẩn
> đoán hình ảnh, `sys` là tài khoản hệ thống — chúng **không phải người** nên không thể có
> chứng chỉ hành nghề. Đo ngày 30/07/2026: trong 5.422 vi phạm của quy tắc, ba tài khoản
> này chiếm **5.380 (99,2%)**. Phần còn lại đều là **người thật** thiếu CCHN trong HIS —
> đó là phát hiện đúng, không được miễn.
>
> So khớp **không phân biệt hoa thường** vì HIS có tài khoản viết hoa lẫn lộn
> (`BHXHConnector`, `BMCS`, `PACS`); so khớp chặt sẽ bỏ sót im lặng.
>
> **Không dùng cách tự nhận diện** tài khoản máy bằng `tdl_username = loginname`: đo được
> 32 tài khoản thoả điều kiện đó, lẫn lộn tài khoản thử nghiệm (`demo1`, `ddtest`), tài
> khoản phòng ban (`noitru`, `vss` — có diploma `CNTT`), `admin`, `fpt`. Tự động sẽ im lặng
> bỏ qua thứ không nên bỏ, và người bảo trì sau không biết ai đang được miễn.
>
> Cả hai khoá **chỉ** áp cho `B_DOCTOR_NO_PRACTICE_CERT`. `A_STAFF_CERT_NOT_IN_CATALOG`
> không bị ảnh hưởng — người dùng chốt ngày 2026-07-28.
```

- [ ] **Step 2: Commit**

```bash
git add docs/tai-lieu-tong-hop-xml3176-order-check.md
git commit -m "docs(order-check): ghi lai danh sach tai khoan duoc mien kiem CCHN"
```

---

## Nghiệm thu cuối

- [ ] `vendor/bin/phpunit tests/Unit/DsMienCchnTest.php` và `tests/Unit/DoctorPracticeCertRuleTest.php` — xanh.
- [ ] Phép đo ở Task 2 Step 5: danh sách tài khoản còn lại **không chứa** `mitalab`, `vietrad`, `sys`.
- [ ] `php artisan config:clear` trên máy chủ sau khi triển khai — đã thêm khoá vào `config/order_check.php`.
- [ ] Vi phạm cũ của ba tài khoản đó **vẫn còn** trong bảng (cố ý giữ lịch sử); từ lần quét sau không sinh thêm.
