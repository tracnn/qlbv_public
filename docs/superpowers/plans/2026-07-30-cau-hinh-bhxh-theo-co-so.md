# Cấu hình cổng BHXH theo cơ sở — nền tảng và đường kiểm thẻ

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Mỗi cơ sở KCB dùng tài khoản cổng BHXH của chính mình khi kiểm tra thẻ, và job gửi lên cổng đúng mã cơ sở điều trị thay vì nơi ĐKBĐ của bệnh nhân.

**Architecture:** Thêm khối cấu hình `BHYT_CO_SO` khoá theo mã cơ sở, giá trị khai thẳng trong `config/organization.php` (tệp đã nằm trong `.gitignore`). Một lớp thuần `CauHinhCoSo` phân giải cấu hình và suy ra mã tỉnh. `BHYTLoginService` nhận mã cơ sở và tách khoá cache theo cơ sở. Lệnh quét lấy mã cơ sở thật từ `his_branch`, truyền xuống job hai giá trị tách bạch.

**Tech Stack:** Laravel 5.5.50, PHP 7.4, PHPUnit 6.5, Oracle qua kết nối `HISPro` (chỉ đọc), Guzzle.

## Global Constraints

- Cổng kiểm thử: `vendor/bin/phpunit --testsuite Unit`. **KHÔNG** chạy `tests/Feature` — đỏ sẵn vì lý do môi trường, không liên quan.
- Comment trong code PHP viết tiếng Việt **không dấu**; chuỗi hiển thị cho người dùng viết **có dấu**.
- Kết nối Oracle `HISPro` và MySQL đều là **production thật của bệnh viện**. Chỉ `SELECT`/`count()`. **Không** `INSERT`/`UPDATE`/`DELETE` ngoài migration của chính plan này.
- **TUYỆT ĐỐI không gọi thật lên cổng BHXH** trong lúc phát triển hay kiểm thử. Không chạy `kiemtrathebhyt:day` ở chế độ thường; chỉ được chạy với cờ `--thu`.
- Cơ sở chưa khai tài khoản → **ném ngoại lệ**, không rơi về tài khoản mặc định.
- Khoá cache token **phải** kèm mã cơ sở.
- `ma_tinh` **không khai** — suy ra từ hai ký tự đầu của mã cơ sở.
- **Không** đụng đường gửi XML (`BHYTXmlSubmitService` và 4 nơi gọi nó), **không** đụng `ma_tinh` ở 5 nơi, **không** đụng `correct_facility_code` và ba bộ kiểm XML — tất cả thuộc spec thứ hai.
- **Không** đụng `JobBHYT`, `JobInpatient` (mã chết, không nơi nào dispatch).
- **Không** tự điền mật khẩu thật vào bất kỳ đâu — để rỗng, người vận hành điền trên máy chủ.
- **Không** thêm biến `.env` nào cho phần này; giá trị khai thẳng trong `config/organization.php`.

## Cấu trúc tệp

| Tệp | Trách nhiệm |
| --- | --- |
| `app/Services/BHYT/CauHinhCoSo.php` (tạo) | Hàm thuần: phân giải cấu hình theo cơ sở, suy mã tỉnh |
| `config/organization.php` (sửa) | Khối `BHYT_CO_SO`; xoá `ma_cskcb` chết |
| `app/Services/BHYTLoginService.php` (sửa) | Nhận mã cơ sở, tách khoá cache |
| `app/Console/Commands/HISProKiemTraTheBHYT.php` (sửa) | Join `his_branch`, tách `maCskcb`/`maDkbd`, cờ `--thu` |
| `app/Jobs/jobKtTheBHYT.php` (sửa) | Dùng tài khoản theo cơ sở, sửa phép so dòng ~152 |
| `app/Console/Commands/XML4210Import.php` (sửa) | Nơi dispatch job thứ hai — tách `maCskcb`/`maDkbd` |
| `app/BHYT.php` (chỉ chú thích) | Method tĩnh, 6 controller gọi — để spec thứ hai |
| `database/migrations/2026_07_30_140000_them_ma_cskcb_vao_check_hein_cards.php` (tạo) | Cột `ma_cskcb` |
| `tests/Unit/CauHinhCoSoTest.php` (tạo) | Kiểm hàm thuần |
| `tests/Unit/BHYTLoginServiceCacheTest.php` (tạo) | Kiểm khoá cache tách theo cơ sở |
| `tests/Unit/KiemTraTheCoSoTest.php` (tạo) | Canh lệnh quét và job không quay lại lỗi cũ |

---

### Task 1: Lớp thuần CauHinhCoSo và khối cấu hình

**Files:**
- Create: `app/Services/BHYT/CauHinhCoSo.php`
- Modify: `config/organization.php`
- Test: `tests/Unit/CauHinhCoSoTest.php`

**Interfaces:**
- Consumes: không có gì từ task khác.
- Produces: `App\Services\BHYT\CauHinhCoSo::cua($maCskcb, array $dsCoSo)` trả mảng có bốn khoá `username`, `password`, `ho_ten_cb`, `cccd_cb`; ném `InvalidArgumentException` khi không hợp lệ. `CauHinhCoSo::maTinh($maCskcb)` trả `string` hai ký tự. Khoá config `organization.BHYT_CO_SO`. Task 2, 3, 4 dùng cả hai.

- [ ] **Step 1: Viết test đỏ**

Tạo `tests/Unit/CauHinhCoSoTest.php`:

```php
<?php

namespace Tests\Unit;

use App\Services\BHYT\CauHinhCoSo;
use Tests\TestCase;

class CauHinhCoSoTest extends TestCase
{
    protected function ds()
    {
        return [
            '01929' => [
                'username' => 'u1929', 'password' => 'p1929',
                'ho_ten_cb' => 'Nguyen Van A', 'cccd_cb' => '001',
            ],
            '37470' => [
                'username' => 'u37470', 'password' => 'p37470',
                'ho_ten_cb' => 'Tran Thi B', 'cccd_cb' => '002',
            ],
            '01283' => ['username' => 'u01283'],   // thieu password
        ];
    }

    /** @test */
    public function co_so_khai_du_thi_tra_dung_bo()
    {
        $c = CauHinhCoSo::cua('01929', $this->ds());

        $this->assertSame('u1929', $c['username']);
        $this->assertSame('p1929', $c['password']);
        $this->assertSame('Nguyen Van A', $c['ho_ten_cb']);
        $this->assertSame('001', $c['cccd_cb']);
    }

    /**
     * KHONG duoc roi ve tai khoan mac dinh: tra bang tai khoan cua co so khac chinh la
     * thu lam ket qua khong hop le.
     */
    /** @test */
    public function co_so_chua_khai_thi_nem_ngoai_le()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageRegExp('/99999/');

        CauHinhCoSo::cua('99999', $this->ds());
    }

    /** @test */
    public function thieu_mat_khau_thi_nem_ngoai_le()
    {
        $this->expectException(\InvalidArgumentException::class);

        CauHinhCoSo::cua('01283', $this->ds());
    }

    /**
     * Khoi BHYT_CO_SO xuat xuong voi cac o de RONG (nguoi van hanh dien sau). Chuoi rong phai
     * bi chan y het khoa thieu, neu khong he thong lang le goi cong bang tai khoan trong.
     */
    /** @test */
    public function mat_khau_rong_thi_nem_ngoai_le()
    {
        $this->expectException(\InvalidArgumentException::class);

        CauHinhCoSo::cua('01929', [
            '01929' => ['username' => 'u', 'password' => '', 'ho_ten_cb' => '', 'cccd_cb' => ''],
        ]);
    }

    /** @test */
    public function username_rong_thi_nem_ngoai_le()
    {
        $this->expectException(\InvalidArgumentException::class);

        CauHinhCoSo::cua('01929', [
            '01929' => ['username' => '', 'password' => 'p', 'ho_ten_cb' => '', 'cccd_cb' => ''],
        ]);
    }

    /** @test */
    public function ma_co_so_rong_thi_nem_ngoai_le()
    {
        $this->expectException(\InvalidArgumentException::class);

        CauHinhCoSo::cua('', $this->ds());
    }

    /** @test */
    public function ma_co_so_null_thi_nem_ngoai_le()
    {
        $this->expectException(\InvalidArgumentException::class);

        CauHinhCoSo::cua(null, $this->ds());
    }

    /** @test */
    public function ma_tinh_la_hai_ky_tu_dau()
    {
        // 37470 o Ninh Binh -> 37, khong phai 01 nhu cau hinh cu chot cung.
        $this->assertSame('37', CauHinhCoSo::maTinh('37470'));
        $this->assertSame('01', CauHinhCoSo::maTinh('01929'));
        $this->assertSame('01', CauHinhCoSo::maTinh('01283'));
    }

    /** @test */
    public function ma_qua_ngan_thi_nem_ngoai_le()
    {
        $this->expectException(\InvalidArgumentException::class);

        CauHinhCoSo::maTinh('1');
    }

    /** @test */
    public function thieu_ho_ten_cb_van_tra_ve_chuoi_rong_khong_nem()
    {
        // ho_ten_cb / cccd_cb thieu thi KHONG chan tra cuu - chung chi la thong tin can bo,
        // khong phai dieu kien dang nhap. Tra chuoi rong de goi vao cong khong bi null.
        $ds = ['01929' => ['username' => 'u', 'password' => 'p']];
        $c = CauHinhCoSo::cua('01929', $ds);

        $this->assertSame('', $c['ho_ten_cb']);
        $this->assertSame('', $c['cccd_cb']);
    }
}
```

- [ ] **Step 2: Chạy test, xác nhận đỏ**

```bash
vendor/bin/phpunit tests/Unit/CauHinhCoSoTest.php
```

Kỳ vọng: cả 10 test FAIL với `Class 'App\Services\BHYT\CauHinhCoSo' not found`.

- [ ] **Step 3: Viết lớp**

Tạo `app/Services/BHYT/CauHinhCoSo.php`:

```php
<?php

namespace App\Services\BHYT;

use InvalidArgumentException;

/**
 * Phan giai cau hinh cong BHXH theo tung co so KCB.
 *
 * Vi sao can: he thong phuc vu nhieu co so nhung moi loi goi cong BHXH truoc day dung MOT
 * tai khoan duy nhat chot cung. Ho so cua co so nao phai duoc tra bang tai khoan cua co so
 * do moi hop le.
 *
 * KHONG bao gio roi ve tai khoan mac dinh khi co so chua khai: tra bang tai khoan cua co so
 * khac chinh la thu lam ket qua khong hop le. Nem ngoai le de loi lo ra ngay, thay vi tra
 * cuu thanh cong nhung sai danh nghia.
 *
 * Ham THUAN de kiem duoc.
 */
class CauHinhCoSo
{
    /**
     * Cau hinh cua mot co so.
     *
     * @param string|null $maCskcb
     * @param array $dsCoSo config('organization.BHYT_CO_SO')
     * @return array bon khoa: username, password, ho_ten_cb, cccd_cb
     * @throws InvalidArgumentException khi ma rong, co so chua khai, hoac thieu tai khoan
     */
    public static function cua($maCskcb, array $dsCoSo)
    {
        $ma = trim((string) $maCskcb);

        if ($ma === '') {
            throw new InvalidArgumentException('Thieu ma co so KCB khi tra cau hinh cong BHXH');
        }

        if (!isset($dsCoSo[$ma]) || !is_array($dsCoSo[$ma])) {
            throw new InvalidArgumentException(
                'Chua khai tai khoan cong BHXH cho co so ' . $ma . ' trong organization.BHYT_CO_SO'
            );
        }

        $c = $dsCoSo[$ma];

        foreach (['username', 'password'] as $bat_buoc) {
            if (trim((string) (isset($c[$bat_buoc]) ? $c[$bat_buoc] : '')) === '') {
                throw new InvalidArgumentException(
                    'Co so ' . $ma . ' thieu ' . $bat_buoc . ' cong BHXH'
                );
            }
        }

        // ho_ten_cb / cccd_cb chi la thong tin can bo tra cuu, khong phai dieu kien dang
        // nhap - thieu thi tra chuoi rong chu khong chan.
        return [
            'username' => (string) $c['username'],
            'password' => (string) $c['password'],
            'ho_ten_cb' => isset($c['ho_ten_cb']) ? (string) $c['ho_ten_cb'] : '',
            'cccd_cb' => isset($c['cccd_cb']) ? (string) $c['cccd_cb'] : '',
        ];
    }

    /**
     * Ma tinh = hai ky tu dau cua ma co so.
     *
     * Suy ra thay vi khai rieng: cau hinh cu chot cung '01' trong khi co so 37470 o Ninh
     * Binh phai la '37'. Bot mot truong la bot mot cho co the khai sai.
     *
     * @throws InvalidArgumentException khi ma ngan hon 2 ky tu
     */
    public static function maTinh($maCskcb)
    {
        $ma = trim((string) $maCskcb);

        if (mb_strlen($ma) < 2) {
            throw new InvalidArgumentException('Ma co so khong hop le de suy ma tinh: ' . $ma);
        }

        return mb_substr($ma, 0, 2);
    }
}
```

- [ ] **Step 4: Thêm khối cấu hình**

Trong `config/organization.php`, thêm khối mới **ngay sau** khối `'BHYT' => [ ... ],`:

```php

    /*
     * Tai khoan cong BHXH RIENG cua tung co so KCB.
     *
     * Cau truc nam trong tep config de moi don vi trien khai sua danh sach ma co so cho
     * khop cua ho. GIA TRI khai THANG vao day, KHONG qua env(): tep nay da nam trong
     * .gitignore va chua tung duoc commit, nen no von la tep bi mat cua rieng tung lan cai
     * dat. Them mot tang env() o giua khong giau duoc gi them ma chi lam cau hinh chia doi -
     * nhin tep khong biet gia tri that, sai mot ten bien thi env() tra null lang le.
     *
     * KHONG khai ma_tinh: no luon la hai ky tu dau cua ma co so (01929 -> 01, 37470 -> 37).
     *
     * Co so chua khai o day thi khong tra cuu duoc - CauHinhCoSo::cua() nem ngoai le, KHONG
     * roi ve tai khoan mac dinh.
     */
    'BHYT_CO_SO' => [
        '01929' => [
            'username' => '',
            'password' => '',
            'ho_ten_cb' => '',
            'cccd_cb' => '',
        ],
        '37470' => [
            'username' => '',
            'password' => '',
            'ho_ten_cb' => '',
            'cccd_cb' => '',
        ],
        '01283' => [
            'username' => '',
            'password' => '',
            'ho_ten_cb' => '',
            'cccd_cb' => '',
        ],
    ],
```

**Để rỗng, không tự điền.** Người vận hành điền mật khẩu thật trên máy chủ. Bạn — người thực thi plan này — **không** được đi tìm mật khẩu ở đâu đó rồi điền vào.

Để rỗng là an toàn theo thiết kế: `CauHinhCoSo::cua()` ném ngoại lệ khi `username` hoặc `password` rỗng (Task 1 đã có test `thieu_mat_khau_thi_nem_ngoai_le`), nên chưa khai thì hỏng ngay và rõ, chứ không lặng lẽ gọi cổng bằng tài khoản trống.

Và **xoá** dòng `'ma_cskcb' => '01013', // ...` trong khối `BHYT` — đã kiểm, không nơi nào đọc khoá đó.

**Không** đụng `username`/`password`/`hoTenCb`/`cccdCb` cũ trong khối `BHYT` ở task này: `BHYTXmlSubmitService` và `app/BHYT.php` còn đang đọc chúng, sẽ chuyển ở task sau và ở spec thứ hai.

- [ ] **Step 5: Chạy test, xác nhận xanh**

```bash
vendor/bin/phpunit tests/Unit/CauHinhCoSoTest.php
```

Kỳ vọng: PASS cả 8 test.

- [ ] **Step 6: Kiểm cú pháp và chạy suite**

```bash
php -l app/Services/BHYT/CauHinhCoSo.php && php -l config/organization.php && vendor/bin/phpunit --testsuite Unit
```

Kỳ vọng: không lỗi cú pháp; suite Unit OK.

- [ ] **Step 7: Commit**

```bash
git add app/Services/BHYT/CauHinhCoSo.php config/organization.php tests/Unit/CauHinhCoSoTest.php
git commit -m "feat(bhxh): cau hinh cong BHXH rieng theo tung co so"
```

---

### Task 2: BHYTLoginService nhận mã cơ sở

**Files:**
- Modify: `app/Services/BHYTLoginService.php`
- Test: `tests/Unit/BHYTLoginServiceCacheTest.php`

**Interfaces:**
- Consumes: `CauHinhCoSo::cua($maCskcb, array $dsCoSo)` từ Task 1; khoá config `organization.BHYT_CO_SO`.
- Produces: `new BHYTLoginService($maCskcb)`; khoá cache `bhyt_tokens:{maCskcb}`. Task 3 dùng.

**Bối cảnh lớp đang sửa:** `$cacheKey` là thuộc tính khởi tạo sẵn `private string $cacheKey = 'bhyt_tokens';` (dòng 17), dùng ở 5 chỗ trong lớp. Hàm dựng hiện đọc `Config::get('organization.BHYT')` vào `$this->config` và `login()` lấy `username`/`password` từ đó.

- [ ] **Step 1: Viết test đỏ**

Tạo `tests/Unit/BHYTLoginServiceCacheTest.php`:

```php
<?php

namespace Tests\Unit;

use App\Services\BHYTLoginService;
use Tests\TestCase;

/**
 * Khoa cache PHAI kem ma co so.
 *
 * Thiet ke cu dung mot khoa duy nhat 'bhyt_tokens' cho moi co so: token cua co so nay ghi
 * de co so kia, va moi loi goi sau do sai danh nghia ma KHONG co dau hieu gi. Day la kieu
 * hong im lang nguy hiem nhat cua ban cu.
 */
class BHYTLoginServiceCacheTest extends TestCase
{
    protected function setUp()
    {
        parent::setUp();

        config(['organization.BHYT_CO_SO' => [
            '01929' => ['username' => 'u1929', 'password' => 'p1929'],
            '37470' => ['username' => 'u37470', 'password' => 'p37470'],
        ]]);
    }

    /** Doc thuoc tinh private cacheKey qua Reflection */
    protected function khoa(BHYTLoginService $s)
    {
        $r = new \ReflectionProperty($s, 'cacheKey');
        $r->setAccessible(true);

        return $r->getValue($s);
    }

    /** @test */
    public function hai_co_so_co_khoa_cache_khac_nhau()
    {
        $a = $this->khoa(new BHYTLoginService('01929'));
        $b = $this->khoa(new BHYTLoginService('37470'));

        $this->assertNotSame($a, $b, 'Hai co so dung chung khoa cache - token se ghi de nhau');
    }

    /** @test */
    public function khoa_cache_co_chua_ma_co_so()
    {
        $this->assertContains('01929', $this->khoa(new BHYTLoginService('01929')));
    }

    /** @test */
    public function khong_truyen_ma_co_so_thi_nem_ngoai_le()
    {
        // Nem chu khong doan: doan nghia la quay lai dung loi dang sua.
        $this->expectException(\InvalidArgumentException::class);

        new BHYTLoginService();
    }

    /** @test */
    public function co_so_chua_khai_thi_nem_ngoai_le()
    {
        $this->expectException(\InvalidArgumentException::class);

        new BHYTLoginService('99999');
    }
}
```

- [ ] **Step 2: Chạy test, xác nhận đỏ**

```bash
vendor/bin/phpunit tests/Unit/BHYTLoginServiceCacheTest.php
```

Kỳ vọng: FAIL — hàm dựng hiện không nhận tham số và khoá cache giống nhau.

- [ ] **Step 3: Sửa hàm dựng và khoá cache**

Trong `app/Services/BHYTLoginService.php`:

Thêm `use App\Services\BHYT\CauHinhCoSo;` vào cụm `use` đầu tệp.

Đổi khai báo thuộc tính `private string $cacheKey = 'bhyt_tokens';` thành:

```php
    private string $cacheKey;
    private string $maCskcb;
    private array $taiKhoan;
```

Đổi hàm dựng thành:

```php
    /**
     * @param string|null $maCskcb ma co so KCB; BAT BUOC
     * @throws \InvalidArgumentException khi thieu ma hoac co so chua khai tai khoan
     */
    public function __construct($maCskcb = null)
    {
        $this->httpClient = new Client();
        $this->config = Config::get('organization.BHYT', []);

        // Tai khoan RIENG theo co so. Nem chu khong doan: doan nghia la quay lai dung loi
        // moi co so dung chung mot tai khoan.
        $this->taiKhoan = CauHinhCoSo::cua($maCskcb, Config::get('organization.BHYT_CO_SO', []));
        $this->maCskcb = trim((string) $maCskcb);

        // Khoa cache PHAI kem ma co so: mot khoa dung chung se lam token cua co so nay ghi
        // de co so kia, va moi loi goi sau do sai danh nghia ma khong co dau hieu gi.
        $this->cacheKey = 'bhyt_tokens:' . $this->maCskcb;
    }
```

- [ ] **Step 4: Dùng tài khoản theo cơ sở khi đăng nhập**

Trong `login()`, thay hai dòng lấy `username`/`password` từ `$this->config` bằng:

```php
        $username = $this->taiKhoan['username'];
        $password = $this->taiKhoan['password'];
```

Giữ nguyên `$loginUrl = $this->config['login_url'] ?? '';` — URL là phần dùng chung.

- [ ] **Step 5: Thêm hàm đọc thông tin cán bộ**

Thêm method công khai để job lấy được `hoTenCb`/`cccdCb` của cơ sở mà không phải tự tra config:

```php
    /** Thong tin can bo tra cuu cua co so nay; chuoi rong neu chua khai */
    public function hoTenCb(): string
    {
        return $this->taiKhoan['ho_ten_cb'];
    }

    /** @return string CCCD can bo tra cuu; chuoi rong neu chua khai */
    public function cccdCb(): string
    {
        return $this->taiKhoan['cccd_cb'];
    }
```

- [ ] **Step 6: Sửa hai nơi gọi còn lại cho khỏi vỡ**

`grep -rn "new BHYTLoginService" app/` cho ba nơi. `app/Jobs/jobKtTheBHYT.php` sẽ sửa ở Task 3.

Hai nơi còn lại — `app/Http/Controllers/Insurance/Manager/InsuranceController.php:26` và `app/Services/BHYTXmlSubmitService.php:25` — **thuộc spec thứ hai**, nhưng chúng sẽ ném ngoại lệ ngay khi khởi tạo nếu để nguyên.

Giải pháp tạm cho task này: truyền mã cơ sở mặc định của đơn vị, đọc từ `correct_facility_code[0]` (giá trị hiện hành), kèm chú thích `// TAM: spec thu hai se lay ma co so tu ho so`. Việc này giữ hai đường đó chạy y như trước cho tới khi spec thứ hai chuyển chúng.

Cụ thể, ở cả hai nơi thay `new BHYTLoginService()` bằng:

```php
new BHYTLoginService((string) (config('organization.correct_facility_code')[0] ?? ''))
```

- [ ] **Step 7: Chạy test, xác nhận xanh**

```bash
php -l app/Services/BHYTLoginService.php && vendor/bin/phpunit tests/Unit/BHYTLoginServiceCacheTest.php
```

Kỳ vọng: PASS cả 4 test.

- [ ] **Step 8: Chạy suite Unit**

```bash
vendor/bin/phpunit --testsuite Unit
```

Kỳ vọng: OK.

- [ ] **Step 9: Commit**

```bash
git add app/Services/BHYTLoginService.php app/Http/Controllers/Insurance/Manager/InsuranceController.php app/Services/BHYTXmlSubmitService.php tests/Unit/BHYTLoginServiceCacheTest.php
git commit -m "feat(bhxh): BHYTLoginService nhan ma co so, tach khoa cache token"
```

---

### Task 3: Lệnh quét và job dùng đúng cơ sở

**Files:**
- Modify: `app/Console/Commands/HISProKiemTraTheBHYT.php`
- Modify: `app/Console/Commands/XML4210Import.php`
- Modify: `app/Jobs/jobKtTheBHYT.php`
- Modify: `app/BHYT.php` (chỉ thêm chú thích)
- Create: `database/migrations/2026_07_30_140000_them_ma_cskcb_vao_check_hein_cards.php`
- Test: `tests/Unit/KiemTraTheCoSoTest.php`

**Interfaces:**
- Consumes: `new BHYTLoginService($maCskcb)`, `->hoTenCb()`, `->cccdCb()` từ Task 2; `CauHinhCoSo::cua()` từ Task 1.
- Produces: không có gì cho task sau.

**Điểm dễ sai nhất của cả plan:** `app/Jobs/jobKtTheBHYT.php:152` hiện là `if ($params['maCSKCB'] != $maDKBD)`, trong đó `$maDKBD` là giá trị **cổng trả về**. Sau khi tách hai khái niệm, phép so này phải dùng **`$params['maDkbd']`** — nó đang đối chiếu nơi ĐKBĐ của cổng với nơi ĐKBĐ trong HIS, không liên quan gì tới cơ sở điều trị.

- [ ] **Step 1: Viết test đỏ**

Tạo `tests/Unit/KiemTraTheCoSoTest.php`:

```php
<?php

namespace Tests\Unit;

use DB;
use Tests\TestCase;
use Tests\Support\LocComment;

/**
 * Canh hai lan quay lai loi cu:
 *  - lenh quet lay ma co so tu tdl_hein_medi_org_code (noi DKBD cua benh nhan) thay vi tu
 *    his_branch (co so dieu tri) - do duoc 99,5% loi goi khai sai co so;
 *  - phep so o job dung maCSKCB thay vi maDkbd.
 */
class KiemTraTheCoSoTest extends TestCase
{
    use LocComment;

    protected function maLenh()
    {
        // Bo comment truoc khi quet: mot chuoi nam trong comment se lam test xanh gia.
        return $this->maKhongComment(
            base_path('app/Console/Commands/HISProKiemTraTheBHYT.php')
        );
    }

    protected function maJob()
    {
        return $this->maKhongComment(base_path('app/Jobs/jobKtTheBHYT.php'));
    }

    protected function maNhapXml()
    {
        return $this->maKhongComment(base_path('app/Console/Commands/XML4210Import.php'));
    }

    /** @test */
    public function lenh_quet_join_his_branch()
    {
        $ma = $this->maLenh();

        $this->assertContains('his_branch', $ma, 'Lenh quet khong con join his_branch');
        $this->assertContains('hein_medi_org_code', $ma, 'Khong lay ma co so tu his_branch');
    }

    /** @test */
    public function lenh_quet_truyen_hai_gia_tri_tach_bach()
    {
        $ma = $this->maLenh();

        $this->assertContains("'maCskcb'", $ma, 'Thieu tham so maCskcb (co so dieu tri)');
        $this->assertContains("'maDkbd'", $ma, 'Thieu tham so maDkbd (noi DKBD benh nhan)');
    }

    /** @test */
    public function lenh_quet_co_co_thu()
    {
        // Che do chi dem, khong dispatch - de nghiem thu ma khong goi len cong BHXH.
        $this->assertContains('--thu', $this->maLenh(), 'Lenh quet thieu co --thu');
    }

    /** @test */
    public function job_so_sanh_bang_maDkbd_khong_phai_maCskcb()
    {
        $ma = $this->maJob();

        $this->assertContains("params['maDkbd']", $ma,
            'Phep so trong job phai dung maDkbd - no doi chieu noi DKBD, khong phai co so dieu tri');
    }

    /**
     * JobKtTheBHYT duoc dispatch tu HAI noi. Neu chi sua mot noi, noi kia gui thieu maCskcb
     * va job vo - loi chi lo ra luc chay that.
     */
    /** @test */
    public function nhap_xml_cung_truyen_hai_gia_tri_tach_bach()
    {
        $ma = $this->maNhapXml();

        $this->assertContains("'maCskcb'", $ma, 'XML4210Import thieu tham so maCskcb');
        $this->assertContains("'maDkbd'", $ma, 'XML4210Import thieu tham so maDkbd');
        $this->assertNotContains("'maCSKCB'", $ma,
            'XML4210Import con dung ten cu maCSKCB - job khong con doc khoa nay');
    }

    /** @test */
    public function bang_ket_qua_co_cot_ma_cskcb()
    {
        $co = false;

        foreach (DB::select('SHOW COLUMNS FROM check_hein_cards') as $c) {
            if ($c->Field === 'ma_cskcb') {
                $co = true;
                break;
            }
        }

        $this->assertTrue($co, 'Bang check_hein_cards thieu cot ma_cskcb');
    }
}
```

- [ ] **Step 2: Chạy test, xác nhận đỏ**

```bash
vendor/bin/phpunit tests/Unit/KiemTraTheCoSoTest.php
```

Kỳ vọng: cả 6 test đầu FAIL; `bang_ket_qua_co_cot_ma_cskcb` cũng FAIL vì chưa có cột.

- [ ] **Step 3: Viết migration**

Tạo `database/migrations/2026_07_30_140000_them_ma_cskcb_vao_check_hein_cards.php`:

```php
<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Cot ma co so KCB cho ket qua kiem tra the BHYT.
 *
 * Bang dang RONG nen khong can va nguoc.
 */
class ThemMaCskcbVaoCheckHeinCards extends Migration
{
    public function up()
    {
        if (Schema::hasColumn('check_hein_cards', 'ma_cskcb')) {
            return;
        }

        Schema::table('check_hein_cards', function (Blueprint $t) {
            $t->string('ma_cskcb', 20)->nullable()->after('ma_lk');
            $t->index('ma_cskcb');
        });
    }

    public function down()
    {
        if (!Schema::hasColumn('check_hein_cards', 'ma_cskcb')) {
            return;
        }

        Schema::table('check_hein_cards', function (Blueprint $t) {
            $t->dropIndex(['ma_cskcb']);
            $t->dropColumn('ma_cskcb');
        });
    }
}
```

- [ ] **Step 4: Chạy migration**

```bash
php artisan migrate
```

Kỳ vọng: `Migrated: 2026_07_30_140000_them_ma_cskcb_vao_check_hein_cards`.

- [ ] **Step 5: Sửa lệnh quét**

Trong `app/Console/Commands/HISProKiemTraTheBHYT.php`:

Đổi `$signature` thành:

```php
    protected $signature = 'kiemtrathebhyt:day
        {--thu : Chi dem va thong ke, KHONG dispatch job - dung de nghiem thu ma khong goi len cong BHXH}';
```

Thêm join vào truy vấn, ngay sau `->join('his_treatment', ...)`:

```php
            ->leftJoin('his_branch','his_branch.id','=','his_treatment.branch_id')
```

Thêm cột vào `selectRaw`, sau `his_department.department_name`:

```
, his_branch.hein_medi_org_code as ma_cskcb
```

Thay toàn bộ thân vòng lặp `foreach` bằng:

```php
        $dsCoSo = config('organization.BHYT_CO_SO', []);
        $thu = (bool) $this->option('thu');
        $boQua = 0;
        $demTheoCoSo = [];

        foreach ($count_noitru_bed_room as $key => $value) {
            $maCskcb = trim((string) $value->ma_cskcb);
            $ma_lk = $value->treatment_code;

            // Co so dieu tri lay tu his_branch, KHONG phai tdl_hein_medi_org_code - cot do
            // la noi DKBD cua BENH NHAN. Do 45.995 ho so: 4.194 gia tri DKBD khac nhau so
            // voi 2 co so dieu tri, trung nhau chi 0,5%.
            if ($maCskcb === '' || !isset($dsCoSo[$maCskcb])) {
                $boQua++;
                \Log::warning('Kiem tra the BHYT: bo qua ho so vi khong xac dinh duoc co so', [
                    'ma_lk' => $ma_lk,
                    'ma_cskcb' => $maCskcb,
                ]);
                continue;
            }

            $gioiTinh = (int) $value->gender_code;

            if ($gioiTinh === 1) {
                $gioiTinh = 2;
            } elseif ($gioiTinh === 2) {
                $gioiTinh = 1;
            }

            $params = [
                'maThe' => $value->tdl_hein_card_number,
                'hoTen' => $value->tdl_patient_name,
                'ngaySinh' => dob($value->tdl_patient_dob),
                'ma_lk' => $ma_lk,
                'maCskcb' => $maCskcb,
                'maDkbd' => $value->tdl_hein_medi_org_code,
                'gioiTinh' => $gioiTinh,
            ];

            $demTheoCoSo[$maCskcb] = isset($demTheoCoSo[$maCskcb]) ? $demTheoCoSo[$maCskcb] + 1 : 1;

            if ($thu) {
                continue;
            }

            JobKtTheBHYT::dispatch($params)->onQueue('JobKtTheBHYT');
        }

        foreach ($demTheoCoSo as $ma => $n) {
            $this->info('Co so ' . $ma . ': ' . $n . ' ho so');
        }

        $this->info('Bo qua vi khong xac dinh duoc co so: ' . $boQua);
        $this->info($this->description);
```

- [ ] **Step 6: Sửa job**

Trong `app/Jobs/jobKtTheBHYT.php`:

Trong hàm dựng, bỏ hai dòng đọc `username`/`password` từ config (không còn dùng), giữ `check_card_url`.

Trong `handle()`, thay chỗ dựng login service bằng:

```php
            $this->loginService = new BHYTLoginService($this->params['maCskcb']);
```

Trong `checkInsuranceCard()`, thay hai dòng `hoTenCb`/`cccdCb` bằng:

```php
                    'hoTenCb' => $this->loginService->hoTenCb(),
                    'cccdCb' => $this->loginService->cccdCb(),
```

Ở dòng ~152, đổi phép so:

```php
        if ($params['maDkbd'] != $maDKBD) {
```

Ở chỗ ghi bản ghi `check_hein_cards`, thêm cột:

```php
                'ma_cskcb' => $this->params['maCskcb'],
```

- [ ] **Step 7: Sửa nơi dispatch thứ hai — XML4210Import**

`JobKtTheBHYT` được dispatch từ **hai** nơi. Task này vừa đổi tham số của job, nên nơi thứ hai phải sửa cùng lượt, nếu không nó gửi thiếu `maCskcb` và job vỡ.

Trong `app/Console/Commands/XML4210Import.php`, khối dựng `$params` (khoảng dòng 91-99) hiện có `'maCSKCB' => $maDKBD` — đúng cùng một lỗi lẫn hai khái niệm.

Đổi thành:

```php
                            $params = [
                                'maThe' => $maThe,
                                'hoTen' => (string)$data->HO_TEN,
                                'ngaySinh' => $ngaySinhFormatted,
                                'ma_lk' => (string)$data->MA_LK,
                                'maCskcb' => trim((string)$data->MA_CSKCB),
                                'maDkbd' => $maDKBD,
                                'gioiTinh' => (string)$data->GIOI_TINH,
                            ];

                            if ($params['maCskcb'] === '') {
                                $this->warn('Bo qua MA_LK ' . $params['ma_lk'] . ': thieu MA_CSKCB');
                                continue;
                            }
```

`MA_CSKCB` là trường chuẩn của XML1 theo Quyết định 4210 — nơi khám chữa bệnh phát sinh hồ sơ, đúng nghĩa cơ sở điều trị. `MA_DKBD` là nơi đăng ký ban đầu ghi trên thẻ, hoàn toàn khác.

Bỏ qua khi thiếu `MA_CSKCB` là cùng một quy tắc đã áp ở Step 4 cho lệnh quét HIS: không đoán cơ sở, vì đoán sai thì gửi sai tài khoản lên cổng.

**Nếu tệp XML thật không có trường `MA_CSKCB`**, đừng tự chọn trường thay thế — dừng lại và báo cáo. Chọn nhầm nguồn mã cơ sở là đúng loại lỗi mà cả spec này sinh ra để dập.

- [ ] **Step 8: Để nguyên app/BHYT.php**

**Không sửa `app/BHYT.php` ở task này.** Chỉ thêm chú thích vào nhánh `else` của `check_by_user` (khoảng dòng 44-45), ngay trên dòng đọc `config('organization.BHYT.hoTenCb')`:

```php
            // TAM: loginBHYT() va checkInsuranceCard() la method tinh khong co tham so co so,
            // duoc goi tu 6 controller. Doi chu ky ham lan sang ca 6 noi nen de spec thu hai
            // lam cung luot voi BHYTXmlSubmitService. Cac khoa cu trong khoi BHYT van con.
```

Task 1 giữ nguyên `username`/`password`/`hoTenCb`/`cccdCb` trong khối `BHYT`, nên đường này chạy y như trước — không hỏng gì.

- [ ] **Step 9: Kiểm cú pháp và chạy test**

```bash
php -l app/Console/Commands/HISProKiemTraTheBHYT.php && php -l app/Console/Commands/XML4210Import.php && php -l app/Jobs/jobKtTheBHYT.php && vendor/bin/phpunit tests/Unit/KiemTraTheCoSoTest.php
```

Kỳ vọng: không lỗi cú pháp; PASS cả 7 test.

- [ ] **Step 10: Nghiệm thu bằng số — bắt buộc**

**Chỉ chạy với cờ `--thu`.** Không được chạy chế độ thường: nó gọi thật lên cổng BHXH.

Bước này chạy được **dù `BHYT_CO_SO` còn để rỗng**: `--thu` chỉ đếm và chỉ hỏi `isset($dsCoSo[$maCskcb])` — có khoá là đủ, không đụng tới `username`/`password`. Không cần mật khẩu thật để nghiệm thu.

```bash
php artisan kiemtrathebhyt:day --thu
```

Kỳ vọng: in ra thống kê theo cơ sở, và **mọi mã cơ sở phải nằm trong `BHYT_CO_SO`** — với dữ liệu hiện tại là `01929` và `37470`. Trước khi sửa, tham số này có **4.194** giá trị phân biệt.

Kèm số hồ sơ bị bỏ qua vì không xác định được cơ sở.

Chép nguyên văn output vào báo cáo. Nếu có mã lạ xuất hiện, **đừng sửa cho khớp** — báo lại.

- [ ] **Step 11: Chạy suite Unit**

```bash
vendor/bin/phpunit --testsuite Unit
```

Kỳ vọng: OK.

- [ ] **Step 12: Commit**

```bash
git add app/Console/Commands/HISProKiemTraTheBHYT.php app/Console/Commands/XML4210Import.php app/Jobs/jobKtTheBHYT.php app/BHYT.php database/migrations/2026_07_30_140000_them_ma_cskcb_vao_check_hein_cards.php tests/Unit/KiemTraTheCoSoTest.php
git commit -m "fix(bhxh): kiem the dung tai khoan va ma co so cua co so dieu tri"
```

---

### Task 4: Cập nhật tài liệu và readme

**Files:**
- Modify: `readme.md`
- Modify: `docs/tai-lieu-tong-hop-xml3176-order-check.md`

**Interfaces:**
- Consumes: kết quả Task 1-3.
- Produces: không có gì.

- [ ] **Step 1: Thêm mục vào readme**

Trong `readme.md`, thêm mục ngày 30/07/2026 theo đúng khuôn các mục có sẵn (đọc vài mục trước để bắt giọng), nêu:

- Mỗi cơ sở KCB nay dùng tài khoản cổng BHXH riêng, khai thẳng trong `config/organization.php` khoá `BHYT_CO_SO`, mỗi mã cơ sở một khối `username`/`password`/`ho_ten_cb`/`cccd_cb`. Tệp này nằm trong `.gitignore` nên không lên kho mã.
- Cơ sở chưa khai tài khoản thì hồ sơ của cơ sở đó **bị bỏ qua và ghi log**, không tra cứu bằng tài khoản của cơ sở khác.
- Sửa lỗi job kiểm thẻ gửi sai mã cơ sở: trước đây gửi nơi ĐKBĐ của bệnh nhân (4.194 giá trị khác nhau), nay gửi cơ sở điều trị thật (2 giá trị). Trước khi sửa, 99,5% lời gọi khai sai cơ sở.
- Cần chạy `php artisan migrate` và `php artisan config:clear` khi triển khai, sau khi đã điền tài khoản cho từng cơ sở.

- [ ] **Step 2: Thêm mục vào tài liệu tổng hợp**

Trong `docs/tai-lieu-tong-hop-xml3176-order-check.md`, tìm mục `### 2.5. Công tắc cấu hình quan trọng` và chèn vào **cuối mục đó**:

```markdown
> **Tài khoản cổng BHXH theo từng cơ sở** (từ 30/07/2026): `config/organization.php` khoá
> `BHYT_CO_SO` khai tài khoản riêng cho từng mã cơ sở KCB, giá trị điền thẳng vào tệp. Hồ sơ của
> cơ sở nào phải tra bằng tài khoản của cơ sở đó mới hợp lệ.
>
> `App\Services\BHYT\CauHinhCoSo::cua()` **ném ngoại lệ** khi cơ sở chưa khai — cố ý không
> rơi về tài khoản mặc định, vì tra bằng tài khoản cơ sở khác chính là thứ làm kết quả
> không hợp lệ.
>
> Khoá cache token là `bhyt_tokens:{mã cơ sở}`. Bản cũ dùng một khoá `bhyt_tokens` duy nhất
> nên token cơ sở này ghi đè cơ sở kia và mọi lời gọi sau đó sai danh nghĩa mà **không có
> dấu hiệu gì** — kiểu hỏng im lặng nguy hiểm nhất.
>
> `ma_tinh` **không khai nữa**: luôn là hai ký tự đầu của mã cơ sở
> (`CauHinhCoSo::maTinh()`). Cấu hình cũ chốt cứng `'01'` trong khi 37470 ở Ninh Bình phải
> là `'37'`.
>
> Lệnh `kiemtrathebhyt:day` có cờ `--thu`: chạy trọn phần quét nhưng **không dispatch job**,
> dùng để kiểm mà không gọi lên cổng BHXH.
>
> **Còn lại cho đợt sau:** đường gửi XML (`BHYTXmlSubmitService` và 4 nơi gọi), `ma_tinh` ở
> 5 nơi, và việc tách `correct_facility_code` thành hai khái niệm (mã cơ sở của mình / danh
> sách ĐKBĐ đúng tuyến) vẫn đang dùng giá trị chốt cứng `01013`.
```

- [ ] **Step 3: Commit**

```bash
git add readme.md docs/tai-lieu-tong-hop-xml3176-order-check.md
git commit -m "docs(bhxh): ghi lai cau hinh cong BHXH theo tung co so"
```

---

## Nghiệm thu cuối

- [ ] `vendor/bin/phpunit --testsuite Unit` — OK, không đỏ.
- [ ] `php artisan kiemtrathebhyt:day --thu` in ra **chỉ** các mã cơ sở có trong `BHYT_CO_SO`.
- [ ] `check_hein_cards` có cột `ma_cskcb`.
- [ ] **Không** có lời gọi thật nào lên cổng BHXH trong suốt quá trình.
- [ ] Khi triển khai: điền tài khoản từng cơ sở vào `config/organization.php`, rồi `php artisan migrate` và `php artisan config:clear`.
