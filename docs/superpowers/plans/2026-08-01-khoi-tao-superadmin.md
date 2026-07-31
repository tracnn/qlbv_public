# Khởi tạo quản trị viên đầu tiên — Kế hoạch triển khai

> **Dành cho người thực thi (kể cả agent):** BẮT BUỘC dùng kèm skill
> `superpowers:subagent-driven-development` (khuyến nghị) hoặc
> `superpowers:executing-plans` để làm từng nhiệm vụ một. Các bước dùng ô đánh
> dấu `- [ ]` để theo dõi.

**Đặc tả gốc:** `docs/superpowers/specs/2026-08-01-khoi-tao-superadmin-design.md`

**Mục tiêu:** Thay middleware `CheckFirstLogin` — vốn tự gán
`superadministrator` cho bất kỳ ai đăng nhập đầu tiên, trên mọi route — bằng một
màn khởi tạo có chủ đích, chỉ mở khi hệ thống chưa có quản trị viên nào.

**Kiến trúc:** Một lớp dịch vụ `SuperAdminBootstrap` giữ toàn bộ luật (hệ thống
đã khởi tạo chưa, vai trò lấy ở đâu, gán thế nào). Một controller hai hành động
`GET`/`POST` dùng lớp đó. Một listener trên sự kiện `Login` đặt cờ session để
hiện dải cảnh báo. Middleware cũ bị xoá hẳn.

**Công nghệ:** Laravel 5.5, Laratrust 5.0, PHPUnit, Blade + AdminLTE.

## Ràng buộc toàn cục

Mọi nhiệm vụ đều ngầm chịu các ràng buộc sau.

- Nhánh làm việc: `fix/khoi-tao-superadmin`, đã tạo từ `main`. **Không** làm trên
  `upgrade/laravel-13`.
- Tên vai trò luôn là chuỗi `superadministrator`, khai báo một lần duy nhất tại
  hằng `SuperAdminBootstrap::TEN_VAI_TRO`. Không viết lại chuỗi này ở nơi khác
  trong mã sản xuất.
- `user_type` chỉ được lấy từ `SuperAdminBootstrap::userType()`. Không ghi cứng
  chuỗi `'App\CustomUser'` ở bất kỳ đâu.
- Không được dùng `Role::...->first()->id`. Truy vấn lấy khoá phải dùng
  `->value('id')` để không bao giờ tồn tại đối tượng `null` bị đọc thuộc tính.
  Đây chính là lỗi mà cả đợt thay đổi này nhằm loại bỏ.
- Cấp vai trò phải qua `$user->attachRole($role)`, **không** qua
  `RoleUser::create()`. Laratrust 5.0 cache vai trò theo từng người dùng;
  `attachRole()` tự gọi `flushCache()`, còn ghi thẳng vào bảng thì không.
- Sau khi hệ thống đã có quản trị viên, cả `GET` lẫn `POST` màn khởi tạo trả
  **404**, không phải 403.
- Không mock `SuperAdminBootstrap` trong test. Hạ tầng test của dự án có trục
  trặc giữa Mockery và phương thức khai báo kiểu trả về, mà lớp này khai báo
  `: bool`, `: Role`, `: void`. Test chạy thật vào cơ sở dữ liệu trong bộ nhớ.
- **Không dùng trait `RefreshDatabase`.** `.env` trỏ `DB_DATABASE=qlbv` là cơ sở
  dữ liệu phát triển thật; `RefreshDatabase` sẽ xoá sạch nó. Dùng trait
  `Tests\Support\DungBangPhanQuyenSqlite` tạo ở Nhiệm vụ 1.

## Bản đồ tệp

| Tệp | Trách nhiệm | Nhiệm vụ |
|---|---|---|
| `app/Exceptions/DaKhoiTaoException.php` | Tín hiệu "hệ thống đã có quản trị viên" | 1 |
| `app/Services/SuperAdminBootstrap.php` | Toàn bộ luật khởi tạo | 1 |
| `tests/Support/DungBangPhanQuyenSqlite.php` | Dựng bảng phân quyền trong bộ nhớ cho test | 1 |
| `tests/Unit/SuperAdminBootstrapTest.php` | Kiểm thử lớp dịch vụ | 1 |
| `app/Http/Controllers/SetupController.php` | Hai hành động màn khởi tạo | 2 |
| `resources/views/setup/quan-tri-dau-tien.blade.php` | Màn xác nhận | 2 |
| `routes/web.php` | Đăng ký hai route | 2 |
| `tests/Feature/KhoiTaoSuperAdminTest.php` | Kiểm thử qua HTTP | 2 |
| `app/Listeners/DanhDauCanKhoiTaoSuperAdmin.php` | Đặt cờ session lúc đăng nhập | 3 |
| `app/Providers/EventServiceProvider.php` | Nối listener vào sự kiện `Login` | 3 |
| `resources/views/vendor/adminlte/page.blade.php` | Dải cảnh báo | 3 |
| `app/Http/Middleware/CheckFirstLogin.php` | **Xoá** | 4 |
| `app/Http/Kernel.php` | Bỏ đăng ký `check.first.login` | 4 |

---

### Nhiệm vụ 0: Ghi mốc nền của bộ test

Bộ test của dự án đã có sẵn một số test đỏ không liên quan đến thay đổi này. Nếu
không ghi lại mốc nền trước, sẽ không phân biệt được test đỏ do mình gây ra với
test vốn đã đỏ. Đây là lý do nhiệm vụ này đứng trước mọi thứ khác.

**Tệp:**
- Tạo: `docs/superpowers/plans/moc-nen-test-2026-08-01.txt`

> **CẢNH BÁO — đọc trước khi gõ lệnh.** `tests/Feature/EmailReceiveReportTest.php`
> dùng trait `RefreshDatabase`. Dự án không có `.env.testing`, nên bộ test chạy
> với `DB_CONNECTION=mysql` và `DB_DATABASE=qlbv` lấy từ `.env` — tức **cơ sở dữ
> liệu phát triển thật**. `RefreshDatabase` gọi `migrate:fresh`, thao tác này
> **xoá sạch mọi bảng** trong `qlbv`.
>
> Vì vậy mốc nền dưới đây **chỉ chạy bộ Unit**, vốn không đụng tới cơ sở dữ liệu
> đó. Chỉ chạy bộ Feature sau khi đã sao lưu `qlbv`, hoặc sau khi tạo
> `.env.testing` trỏ sang một cơ sở dữ liệu vứt đi. Việc đó nằm ngoài phạm vi kế
> hoạch này — nhưng đáng làm thành một việc riêng.
>
> Các test Feature mà kế hoạch này thêm vào **không** dùng `RefreshDatabase`;
> chúng chạy trên SQLite trong bộ nhớ nên an toàn, và chỉ định đích danh tệp khi
> chạy thì không kéo theo `EmailReceiveReportTest`.

- [ ] **Bước 1: Chạy bộ Unit và lưu kết quả**

```bash
./vendor/bin/phpunit --testsuite Unit > docs/superpowers/plans/moc-nen-test-2026-08-01.txt 2>&1; tail -20 docs/superpowers/plans/moc-nen-test-2026-08-01.txt
```

- [ ] **Bước 2: Ghi lại con số**

Mở tệp vừa tạo, ghi ra dòng tổng kết cuối (dạng `Tests: N, Assertions: M,
Failures: X, Errors: Y`). Từ đây về sau, **chỉ** những test đỏ mới xuất hiện
ngoài danh sách này mới tính là do thay đổi của mình.

- [ ] **Bước 3: Commit mốc nền**

```bash
git add docs/superpowers/plans/moc-nen-test-2026-08-01.txt
git commit -m "test: ghi moc nen bo test truoc khi sua khoi tao superadmin"
```

---

### Nhiệm vụ 1: Lớp dịch vụ `SuperAdminBootstrap`

**Tệp:**
- Tạo: `app/Exceptions/DaKhoiTaoException.php`
- Tạo: `app/Services/SuperAdminBootstrap.php`
- Tạo: `tests/Support/DungBangPhanQuyenSqlite.php`
- Test: `tests/Unit/SuperAdminBootstrapTest.php`

**Giao diện:**
- Dùng của nhiệm vụ trước: không có.
- Cung cấp cho nhiệm vụ sau:
  - `App\Services\SuperAdminBootstrap::TEN_VAI_TRO` — hằng chuỗi `'superadministrator'`
  - `chuaKhoiTao(): bool`
  - `vaiTro(): \App\Role`
  - `gan(\App\CustomUser $nguoiDung): void` — ném `App\Exceptions\DaKhoiTaoException`
  - `App\Exceptions\DaKhoiTaoException` — lớp con của `\RuntimeException`
  - `Tests\Support\DungBangPhanQuyenSqlite::chuanBiBangPhanQuyen()` — không khai
    báo kiểu trả về, để trait dùng được cả trong `setUp()` của PHPUnit cũ
  - `Tests\Support\DungBangPhanQuyenSqlite::nguoiDungGia($id = 1001)` — trả về
    `\App\CustomUser` chưa lưu, chỉ có khoá chính và `loginname`

- [ ] **Bước 1: Tạo trait dựng bảng phân quyền trong bộ nhớ**

Trait này trỏ kết nối `mysql` sang SQLite trong bộ nhớ rồi chạy **đúng một**
migration — tệp tạo bảng của Laratrust. Không chạy `migrate` toàn bộ vì kho có
nhiều migration phụ thuộc cú pháp MySQL và các kết nối Oracle khác.

Model `App\Role` và `App\RoleUser` ghim cứng `protected $connection = 'mysql'`,
nên phải thay cấu hình của chính kết nối tên `mysql`, không thể chỉ đổi
`database.default`.

Tạo `tests/Support/DungBangPhanQuyenSqlite.php`:

```php
<?php

namespace Tests\Support;

use App\CustomUser;
use Illuminate\Support\Facades\DB;

/**
 * Dung bang phan quyen (roles, role_user, permissions, ...) trong SQLite bo nho.
 *
 * VI SAO KHONG DUNG RefreshDatabase: .env cua du an tro DB_DATABASE=qlbv - co so du
 * lieu phat trien that. RefreshDatabase se xoa sach no. Trait nay khong dung toi co
 * so du lieu that.
 *
 * VI SAO PHAI GHI DE KET NOI TEN 'mysql' chu khong doi database.default: App\Role va
 * App\RoleUser ghim cung protected $connection = 'mysql', nen doi mac dinh khong co
 * tac dung gi voi chung.
 */
trait DungBangPhanQuyenSqlite
{
    protected function chuanBiBangPhanQuyen()
    {
        config([
            'database.default' => 'mysql',
            'database.connections.mysql' => [
                'driver'   => 'sqlite',
                'database' => ':memory:',
                'prefix'   => '',
            ],
        ]);

        DB::purge('mysql');

        $this->artisan('migrate', [
            '--database' => 'mysql',
            '--path'     => 'database/migrations/2017_10_23_052501_laratrust_setup_tables.php',
        ]);
    }

    /**
     * CustomUser khong luu vao Oracle. Chi can khoa chinh de attachRole() ghi duoc
     * ban ghi pivot; quan he morphToMany dung ket noi cua App\Role (mysql), khong
     * cham toi ACS_RS.
     */
    protected function nguoiDungGia($id = 1001)
    {
        $nguoiDung = new CustomUser();
        $nguoiDung->id = $id;
        $nguoiDung->loginname = 'nguoicai' . $id;
        $nguoiDung->exists = true;

        return $nguoiDung;
    }
}
```

- [ ] **Bước 2: Viết test thất bại**

Tạo `tests/Unit/SuperAdminBootstrapTest.php`:

```php
<?php

namespace Tests\Unit;

use App\Exceptions\DaKhoiTaoException;
use App\Role;
use App\RoleUser;
use App\Services\SuperAdminBootstrap;
use Tests\Support\DungBangPhanQuyenSqlite;
use Tests\TestCase;

class SuperAdminBootstrapTest extends TestCase
{
    use DungBangPhanQuyenSqlite;

    protected $bootstrap;

    protected function setUp()
    {
        parent::setUp();

        $this->chuanBiBangPhanQuyen();
        $this->bootstrap = new SuperAdminBootstrap();
    }

    /**
     * Day chinh la kich ban gay loi cu: bang roles rong vi ban cai moi chua chay
     * laratrust:seeder. Ma cu doc ->first()->id tren null o day.
     *
     * @test
     */
    public function bang_roles_rong_thi_coi_nhu_chua_khoi_tao()
    {
        $this->assertSame(0, Role::count());
        $this->assertTrue($this->bootstrap->chuaKhoiTao());
    }

    /** @test */
    public function co_vai_tro_nhung_chua_gan_ai_thi_van_chua_khoi_tao()
    {
        Role::create([
            'name'         => 'superadministrator',
            'display_name' => 'Super Administrator',
            'description'  => 'Highest level administrator',
        ]);

        $this->assertTrue($this->bootstrap->chuaKhoiTao());
    }

    /** @test */
    public function da_gan_cho_mot_nguoi_thi_da_khoi_tao()
    {
        $this->bootstrap->gan($this->nguoiDungGia());

        $this->assertFalse($this->bootstrap->chuaKhoiTao());
    }

    /** @test */
    public function vai_tro_duoc_tao_neu_bang_roles_chua_co()
    {
        $vaiTro = $this->bootstrap->vaiTro();

        $this->assertSame('superadministrator', $vaiTro->name);
        $this->assertSame(1, Role::where('name', 'superadministrator')->count());
    }

    /** @test */
    public function goi_vai_tro_hai_lan_khong_tao_ban_ghi_trung()
    {
        $this->bootstrap->vaiTro();
        $this->bootstrap->vaiTro();

        $this->assertSame(1, Role::where('name', 'superadministrator')->count());
    }

    /**
     * Ban ghi phai nam tren ket noi mysql voi dung user_type. Day cung la bang
     * chung cho diem "phai kiem chung" trong dac ta: attachRole() tren model ghim
     * Oracle van ghi dung vao bang pivot ben mysql.
     *
     * @test
     */
    public function gan_ghi_dung_ban_ghi_role_user()
    {
        $nguoiDung = $this->nguoiDungGia(2002);

        $this->bootstrap->gan($nguoiDung);

        $roleId = Role::where('name', 'superadministrator')->value('id');

        $this->assertSame(1, RoleUser::where('role_id', $roleId)
            ->where('user_id', 2002)
            ->where('user_type', 'App\CustomUser')
            ->count());
    }

    /** @test */
    public function nguoi_thu_hai_bi_tu_choi()
    {
        $this->bootstrap->gan($this->nguoiDungGia(3003));

        $this->expectException(DaKhoiTaoException::class);

        $this->bootstrap->gan($this->nguoiDungGia(4004));
    }

    /** @test */
    public function nguoi_thu_hai_bi_tu_choi_khong_de_lai_ban_ghi()
    {
        $this->bootstrap->gan($this->nguoiDungGia(3003));

        try {
            $this->bootstrap->gan($this->nguoiDungGia(4004));
        } catch (DaKhoiTaoException $e) {
            // mong doi
        }

        $this->assertSame(0, RoleUser::where('user_id', 4004)->count());
    }
}
```

- [ ] **Bước 3: Chạy test để chắc chắn nó đỏ**

```bash
./vendor/bin/phpunit tests/Unit/SuperAdminBootstrapTest.php
```

Kỳ vọng: ĐỎ, với `Class 'App\Services\SuperAdminBootstrap' not found`.

- [ ] **Bước 4: Viết ngoại lệ**

Tạo `app/Exceptions/DaKhoiTaoException.php`:

```php
<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * He thong da co quan tri vien - cong khoi tao da dong vinh vien.
 */
class DaKhoiTaoException extends RuntimeException
{
    //
}
```

- [ ] **Bước 5: Viết lớp dịch vụ**

Tạo `app/Services/SuperAdminBootstrap.php`:

```php
<?php

namespace App\Services;

use App\CustomUser;
use App\Exceptions\DaKhoiTaoException;
use App\Role;
use App\RoleUser;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Cho duy nhat giu luat khoi tao quan tri vien dau tien.
 *
 * Thay cho middleware CheckFirstLogin cu, von chay tren moi route da xac thuc va
 * gan superadministrator cho bat ky ai dang nhap dau tien. Vi App\CustomUser tro
 * vao acs_user cua HIS nen "nguoi dau tien" la bat ky nhan vien nao, khong phai
 * nguoi cai dat.
 */
class SuperAdminBootstrap
{
    const TEN_VAI_TRO = 'superadministrator';

    /**
     * Dung value('id') chu khong first()->id: ban cai moi chua chay
     * laratrust:seeder thi bang roles rong, va ma cu doc thuoc tinh tren null o
     * dung cho nay.
     */
    public function chuaKhoiTao(): bool
    {
        $roleId = Role::where('name', self::TEN_VAI_TRO)->value('id');

        if (! $roleId) {
            return true;
        }

        return ! RoleUser::where('role_id', $roleId)
            ->where('user_type', $this->userType())
            ->exists();
    }

    /**
     * Tu tao vai tro neu thieu: nguoi cai o benh vien khong chay duoc artisan nen
     * khong the yeu cau ho chay laratrust:seeder. Chi tao vai tro, khong tao
     * permission - phan do van thuoc seeder.
     */
    public function vaiTro(): Role
    {
        return Role::firstOrCreate(
            ['name' => self::TEN_VAI_TRO],
            [
                'display_name' => 'Super Administrator',
                'description'  => 'Highest level administrator',
            ]
        );
    }

    /**
     * Kiem tra lai ben trong transaction: co session chi de hien thi, ranh gioi
     * bao mat that nam o day.
     */
    public function gan(CustomUser $nguoiDung): void
    {
        DB::connection('mysql')->transaction(function () use ($nguoiDung) {
            if (! $this->chuaKhoiTao()) {
                throw new DaKhoiTaoException('He thong da co quan tri vien.');
            }

            $nguoiDung->attachRole($this->vaiTro());
        });

        Log::info('Khoi tao quan tri vien dau tien', [
            'user_id'    => $nguoiDung->getKey(),
            'loginname'  => $nguoiDung->loginname,
            'ip'         => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Mot nguon duy nhat cho user_type, dung o ca luc doc lan luc ghi. Ma cu ghi
     * cung 'App\CustomUser' khi doc nhung lay config khi ghi - hai ben lech nguon.
     *
     * getMorphClass() chu khong phai ten lop tho: day dung la gia tri ma
     * attachRole() ghi vao cot user_type, nen hai ben chac chan khop.
     */
    private function userType(): string
    {
        $lop = config('auth.providers.users.model');

        return (new $lop)->getMorphClass();
    }
}
```

- [ ] **Bước 6: Chạy test để chắc chắn nó xanh**

```bash
./vendor/bin/phpunit tests/Unit/SuperAdminBootstrapTest.php
```

Kỳ vọng: XANH, 8 test.

Nếu test `gan_ghi_dung_ban_ghi_role_user` đỏ vì `attachRole()` không ghi được
sang kết nối `mysql`, đó chính là tình huống đặc tả đã dự phòng. Khi đó đổi thân
`gan()` thành:

```php
RoleUser::create([
    'role_id'   => $this->vaiTro()->id,
    'user_id'   => $nguoiDung->getKey(),
    'user_type' => $this->userType(),
]);

$nguoiDung->flushCache();
```

và ghi chú lý do ngay trên chỗ sửa. Không bỏ qua việc gọi `flushCache()`.

- [ ] **Bước 7: Commit**

```bash
git add app/Exceptions/DaKhoiTaoException.php app/Services/SuperAdminBootstrap.php tests/Support/DungBangPhanQuyenSqlite.php tests/Unit/SuperAdminBootstrapTest.php
git commit -m "feat: lop dich vu SuperAdminBootstrap giu luat khoi tao quan tri vien"
```

---

### Nhiệm vụ 2: Màn khởi tạo (controller, route, view)

**Tệp:**
- Tạo: `app/Http/Controllers/SetupController.php`
- Tạo: `resources/views/setup/quan-tri-dau-tien.blade.php`
- Sửa: `routes/web.php` — chèn ngay **trước** dòng 58
- Test: `tests/Feature/KhoiTaoSuperAdminTest.php`

**Giao diện:**
- Dùng của nhiệm vụ trước: `App\Services\SuperAdminBootstrap` (`chuaKhoiTao()`,
  `gan()`), `App\Exceptions\DaKhoiTaoException`,
  `Tests\Support\DungBangPhanQuyenSqlite`.
- Cung cấp cho nhiệm vụ sau: tên route `setup.quan-tri-dau-tien` (GET) và
  `setup.quan-tri-dau-tien.gan` (POST); khoá session `setup.can_khoi_tao`.

- [ ] **Bước 1: Viết test thất bại**

Tạo `tests/Feature/KhoiTaoSuperAdminTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Role;
use App\RoleUser;
use App\Services\SuperAdminBootstrap;
use Tests\Support\DungBangPhanQuyenSqlite;
use Tests\TestCase;

class KhoiTaoSuperAdminTest extends TestCase
{
    use DungBangPhanQuyenSqlite;

    protected function setUp()
    {
        parent::setUp();

        $this->chuanBiBangPhanQuyen();
    }

    /** @test */
    public function he_thong_trong_thi_man_khoi_tao_mo()
    {
        $this->actingAs($this->nguoiDungGia())
            ->get('/setup/quan-tri-dau-tien')
            ->assertStatus(200);
    }

    /** @test */
    public function chua_dang_nhap_thi_chuyen_ve_trang_dang_nhap()
    {
        $this->get('/setup/quan-tri-dau-tien')
            ->assertRedirect('/login');
    }

    /**
     * 404 chu khong phai 403: sau khi cong dong, khong de lo su ton tai cua man
     * nay trong suot vong doi con lai cua ban cai.
     *
     * @test
     */
    public function da_khoi_tao_thi_get_tra_404()
    {
        app(SuperAdminBootstrap::class)->gan($this->nguoiDungGia(5005));

        $this->actingAs($this->nguoiDungGia(6006))
            ->get('/setup/quan-tri-dau-tien')
            ->assertStatus(404);
    }

    /** @test */
    public function da_khoi_tao_thi_post_tra_404_va_khong_them_ban_ghi()
    {
        app(SuperAdminBootstrap::class)->gan($this->nguoiDungGia(5005));

        $this->actingAs($this->nguoiDungGia(6006))
            ->post('/setup/quan-tri-dau-tien')
            ->assertStatus(404);

        $this->assertSame(0, RoleUser::where('user_id', 6006)->count());
    }

    /** @test */
    public function post_gan_quyen_cho_nguoi_dang_dang_nhap()
    {
        $this->actingAs($this->nguoiDungGia(7007))
            ->post('/setup/quan-tri-dau-tien')
            ->assertRedirect('/home');

        $roleId = Role::where('name', 'superadministrator')->value('id');

        $this->assertSame(1, RoleUser::where('role_id', $roleId)
            ->where('user_id', 7007)
            ->where('user_type', 'App\CustomUser')
            ->count());
    }

    /** @test */
    public function post_lan_hai_khong_tao_ban_ghi_trung()
    {
        $nguoiDung = $this->nguoiDungGia(8008);

        $this->actingAs($nguoiDung)->post('/setup/quan-tri-dau-tien');
        $this->actingAs($nguoiDung)->post('/setup/quan-tri-dau-tien')
            ->assertStatus(404);

        $this->assertSame(1, RoleUser::where('user_id', 8008)->count());
    }
}
```

- [ ] **Bước 2: Chạy test để chắc chắn nó đỏ**

```bash
./vendor/bin/phpunit tests/Feature/KhoiTaoSuperAdminTest.php
```

Kỳ vọng: ĐỎ, với 404 ở mọi ca vì route chưa tồn tại.

- [ ] **Bước 3: Viết controller**

Tạo `app/Http/Controllers/SetupController.php`:

```php
<?php

namespace App\Http\Controllers;

use App\Exceptions\DaKhoiTaoException;
use App\Services\SuperAdminBootstrap;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Man khoi tao quan tri vien dau tien.
 *
 * Cong chi mo khi va chi khi he thong chua co superadministrator nao. Sau lan gan
 * dau tien, ca hai hanh dong tra 404 vinh vien.
 */
class SetupController extends Controller
{
    protected $bootstrap;

    public function __construct(SuperAdminBootstrap $bootstrap)
    {
        $this->bootstrap = $bootstrap;
    }

    public function hienThi()
    {
        abort_unless($this->bootstrap->chuaKhoiTao(), 404);

        return view('setup.quan-tri-dau-tien', [
            'nguoiDung' => Auth::user(),
        ]);
    }

    public function gan(Request $request)
    {
        try {
            $this->bootstrap->gan(Auth::user());
        } catch (DaKhoiTaoException $e) {
            abort(404);
        }

        $request->session()->forget('setup.can_khoi_tao');

        return redirect('/home')
            ->with('success', 'Da cap quyen quan tri cao nhat cho tai khoan cua ban.');
    }
}
```

- [ ] **Bước 4: Đăng ký route**

Trong `routes/web.php`, chèn khối sau **ngay trước** dòng
`Route::group(['middleware' => ['auth', 'check.first.login']], function () {`
(hiện là dòng 58):

```php
/*
 * Man khoi tao quan tri vien dau tien. Nam trong nhom 'auth' nhung KHONG nam
 * trong nhom lon ben duoi: nhom do se mat middleware check.first.login o Nhiem vu 4,
 * va man nay phai truy cap duoc ke ca khi he thong chua co quan tri vien nao.
 */
Route::group(['middleware' => ['auth']], function () {
    Route::get('/setup/quan-tri-dau-tien', 'SetupController@hienThi')
        ->name('setup.quan-tri-dau-tien');
    Route::post('/setup/quan-tri-dau-tien', 'SetupController@gan')
        ->name('setup.quan-tri-dau-tien.gan');
});
```

- [ ] **Bước 5: Viết view**

Tạo `resources/views/setup/quan-tri-dau-tien.blade.php`:

```blade
@extends('adminlte::page')

@section('title', 'Khởi tạo quản trị viên')

@section('content_header')
    <h1>Khởi tạo quản trị viên đầu tiên</h1>
@stop

@section('content')
<div class="row">
    <div class="col-md-8">
        <div class="box box-warning">
            <div class="box-header with-border">
                <h3 class="box-title">Hệ thống chưa có quản trị viên</h3>
            </div>

            <div class="box-body">
                <p>
                    Chưa tài khoản nào được cấp quyền quản trị cao nhất
                    (<code>superadministrator</code>). Nếu bạn là người phụ trách
                    cài đặt, hãy khởi tạo ngay bây giờ.
                </p>

                <p>
                    Tài khoản sẽ được cấp quyền:
                    <strong>{{ $nguoiDung->loginname }}</strong>
                </p>

                <div class="callout callout-danger">
                    <h4>Bước này chỉ làm được một lần</h4>
                    <p>
                        Sau khi xác nhận, màn hình này đóng vĩnh viễn. Việc cấp
                        quyền cho người khác về sau phải làm qua mục
                        <em>Quản lý người dùng</em>.
                    </p>
                </div>
            </div>

            <div class="box-footer">
                <form method="POST" action="{{ route('setup.quan-tri-dau-tien.gan') }}">
                    {{ csrf_field() }}
                    <button type="submit" class="btn btn-warning">
                        Cấp quyền quản trị cho tài khoản này
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@stop
```

- [ ] **Bước 6: Chạy test để chắc chắn nó xanh**

```bash
./vendor/bin/phpunit tests/Feature/KhoiTaoSuperAdminTest.php
```

Kỳ vọng: XANH, 6 test.

Nếu ca `post_*` đỏ vì lỗi CSRF (419): kiểm tra
`app/Http/Middleware/VerifyCsrfToken.php` xem có ghi đè `handle()` không. Lớp cơ
sở của Laravel tự bỏ qua kiểm tra khi `runningUnitTests()`; nếu dự án ghi đè và
làm mất nhánh đó thì thêm `$this->withoutMiddleware(VerifyCsrfToken::class)`
vào các ca `post_*`, **không** sửa middleware.

- [ ] **Bước 7: Commit**

```bash
git add app/Http/Controllers/SetupController.php resources/views/setup/quan-tri-dau-tien.blade.php routes/web.php tests/Feature/KhoiTaoSuperAdminTest.php
git commit -m "feat: man khoi tao quan tri vien dau tien, dong vinh vien sau lan dau"
```

---

### Nhiệm vụ 3: Cờ session và dải cảnh báo

Người của bệnh viện chỉ dùng trình duyệt và không được cho biết trước đường dẫn.
Listener kiểm tra **một lần mỗi phiên đăng nhập** — đây là chỗ đổi *2 truy vấn ×
mọi request* của middleware cũ thành *1 truy vấn × mỗi lần đăng nhập*.

**Tệp:**
- Tạo: `app/Listeners/DanhDauCanKhoiTaoSuperAdmin.php`
- Sửa: `app/Providers/EventServiceProvider.php:15-19`
- Sửa: `resources/views/vendor/adminlte/page.blade.php:129` (ngay trước `@yield('content')`)
- Test: `tests/Feature/KhoiTaoSuperAdminTest.php` (bổ sung 2 ca)

**Giao diện:**
- Dùng của nhiệm vụ trước: `SuperAdminBootstrap::chuaKhoiTao()`, tên route
  `setup.quan-tri-dau-tien`.
- Cung cấp cho nhiệm vụ sau: không có.

- [ ] **Bước 1: Viết test thất bại**

Thêm hai phương thức vào cuối `tests/Feature/KhoiTaoSuperAdminTest.php`, và thêm
`use Illuminate\Auth\Events\Login;` vào phần `use` đầu tệp:

```php
    /** @test */
    public function dang_nhap_khi_he_thong_trong_thi_bat_co_session()
    {
        $this->withSession([]);

        event(new Login('web', $this->nguoiDungGia(9009), false));

        $this->assertTrue(session('setup.can_khoi_tao'));
    }

    /** @test */
    public function dang_nhap_khi_da_khoi_tao_thi_co_tat()
    {
        app(SuperAdminBootstrap::class)->gan($this->nguoiDungGia(5005));

        $this->withSession([]);

        event(new Login('web', $this->nguoiDungGia(9009), false));

        $this->assertFalse(session('setup.can_khoi_tao'));
    }
```

- [ ] **Bước 2: Chạy test để chắc chắn nó đỏ**

```bash
./vendor/bin/phpunit tests/Feature/KhoiTaoSuperAdminTest.php --filter dang_nhap_khi
```

Kỳ vọng: ĐỎ — `session('setup.can_khoi_tao')` trả `null`.

Nếu báo lỗi số tham số của `Login` (Laravel 5.5 dùng `__construct($guard, $user,
$remember)` từ 5.6 trở đi; 5.5 chỉ có `($user, $remember)`), bỏ tham số `'web'`
ở cả hai ca. Kiểm tra bằng:

```bash
grep -n "__construct" vendor/laravel/framework/src/Illuminate/Auth/Events/Login.php
```

- [ ] **Bước 3: Viết listener**

Tạo `app/Listeners/DanhDauCanKhoiTaoSuperAdmin.php`:

```php
<?php

namespace App\Listeners;

use App\Services\SuperAdminBootstrap;
use Illuminate\Auth\Events\Login;

/**
 * Dat co session mot lan moi phien dang nhap.
 *
 * Middleware CheckFirstLogin cu hoi co so du lieu 2 lan tren MOI request de tra
 * loi mot cau chi doi mot lan trong ca vong doi ban cai. Dat o day thi chi con 1
 * truy van moi lan dang nhap.
 */
class DanhDauCanKhoiTaoSuperAdmin
{
    protected $bootstrap;

    public function __construct(SuperAdminBootstrap $bootstrap)
    {
        $this->bootstrap = $bootstrap;
    }

    public function handle(Login $event)
    {
        session(['setup.can_khoi_tao' => $this->bootstrap->chuaKhoiTao()]);
    }
}
```

- [ ] **Bước 4: Nối listener vào sự kiện**

Trong `app/Providers/EventServiceProvider.php`, đổi mảng `$listen` thành:

```php
    protected $listen = [
        'App\Events\MedicalRegister' => [
            'App\Listeners\SendMailMedicalRegister',
        ],
        'Illuminate\Auth\Events\Login' => [
            'App\Listeners\DanhDauCanKhoiTaoSuperAdmin',
        ],
    ];
```

- [ ] **Bước 5: Chạy test để chắc chắn nó xanh**

```bash
./vendor/bin/phpunit tests/Feature/KhoiTaoSuperAdminTest.php
```

Kỳ vọng: XANH, 8 test.

- [ ] **Bước 6: Thêm dải cảnh báo vào layout**

Trong `resources/views/vendor/adminlte/page.blade.php`, chèn ngay **trước** dòng
`@yield('content')` (hiện là dòng 130):

```blade
                @if(session('setup.can_khoi_tao'))
                    {{-- He thong chua co quan tri vien. Co duoc dat boi
                         App\Listeners\DanhDauCanKhoiTaoSuperAdmin luc dang nhap. --}}
                    <div class="callout callout-warning">
                        <h4>Hệ thống chưa có quản trị viên</h4>
                        <p>
                            Chưa tài khoản nào được cấp quyền quản trị cao nhất.
                            Nếu bạn phụ trách cài đặt, hãy khởi tạo ngay trước khi
                            thông báo đường dẫn hệ thống cho nhân viên.
                        </p>
                        <a href="{{ route('setup.quan-tri-dau-tien') }}" class="btn btn-warning">
                            Khởi tạo quản trị viên
                        </a>
                    </div>
                @endif

```

- [ ] **Bước 7: Kiểm tra bằng mắt trên trình duyệt**

Đây là thay đổi hiển thị, test không phủ được. Đăng nhập vào ứng dụng với cơ sở
dữ liệu đã có quản trị viên (tức môi trường phát triển bình thường) và xác nhận
**không** thấy dải cảnh báo nào — nếu thấy, cờ session đang bị đặt sai.

- [ ] **Bước 8: Commit**

```bash
git add app/Listeners/DanhDauCanKhoiTaoSuperAdmin.php app/Providers/EventServiceProvider.php resources/views/vendor/adminlte/page.blade.php tests/Feature/KhoiTaoSuperAdminTest.php
git commit -m "feat: co session va dai canh bao dan toi man khoi tao quan tri vien"
```

---

### Nhiệm vụ 4: Xoá middleware `CheckFirstLogin`

Đây là nhiệm vụ khiến lỗi đọc thuộc tính trên `null` biến mất — không phải bằng
cách vá thêm nhánh rẽ, mà vì đoạn mã đó không còn tồn tại.

**Tệp:**
- Xoá: `app/Http/Middleware/CheckFirstLogin.php`
- Sửa: `app/Http/Kernel.php:65`
- Sửa: `routes/web.php:58`
- Sửa: `tests/Unit/RouteOrderCheckTest.php:101` (chỉ phần chú thích)
- Test: `tests/Unit/RouteKhongConCheckFirstLoginTest.php` (tạo mới)

**Giao diện:**
- Dùng của nhiệm vụ trước: không có.
- Cung cấp cho nhiệm vụ sau: không có. Đây là nhiệm vụ cuối.

- [ ] **Bước 1: Viết test canh gác thất bại**

Tạo `tests/Unit/RouteKhongConCheckFirstLoginTest.php`:

```php
<?php

namespace Tests\Unit;

use Route;
use Tests\TestCase;

/**
 * Canh gac: middleware CheckFirstLogin da bi xoa vi no gan superadministrator cho
 * bat ky ai dang nhap dau tien. Neu ai do khoi phuc lai, test nay do.
 */
class RouteKhongConCheckFirstLoginTest extends TestCase
{
    /** @test */
    public function khong_route_nao_con_middleware_check_first_login()
    {
        $pham = [];

        foreach (Route::getRoutes() as $route) {
            if (in_array('check.first.login', $route->gatherMiddleware())) {
                $pham[] = $route->uri();
            }
        }

        $this->assertSame([], $pham,
            'Con route dung check.first.login: ' . implode(', ', $pham));
    }

    /** @test */
    public function lop_middleware_khong_con_ton_tai()
    {
        $this->assertFalse(class_exists(\App\Http\Middleware\CheckFirstLogin::class),
            'CheckFirstLogin da duoc khoi phuc - xem docs/superpowers/specs/2026-08-01-khoi-tao-superadmin-design.md');
    }

    /**
     * Man khoi tao phai con song sau khi go middleware, va van yeu cau dang nhap.
     *
     * @test
     */
    public function man_khoi_tao_van_nam_trong_nhom_xac_thuc()
    {
        $route = Route::getRoutes()->getByName('setup.quan-tri-dau-tien');

        $this->assertNotNull($route, 'Mat route man khoi tao');
        $this->assertContains('auth', $route->gatherMiddleware());
    }
}
```

- [ ] **Bước 2: Chạy test để chắc chắn nó đỏ**

```bash
./vendor/bin/phpunit tests/Unit/RouteKhongConCheckFirstLoginTest.php
```

Kỳ vọng: ĐỎ ở hai ca đầu — middleware vẫn còn.

- [ ] **Bước 3: Gỡ middleware khỏi nhóm route**

Trong `routes/web.php` dòng 58, đổi:

```php
Route::group(['middleware' => ['auth', 'check.first.login']], function () {
```

thành:

```php
Route::group(['middleware' => ['auth']], function () {
```

- [ ] **Bước 4: Bỏ đăng ký trong Kernel**

Trong `app/Http/Kernel.php`, xoá dòng 65:

```php
        'check.first.login' => \App\Http\Middleware\CheckFirstLogin::class,
```

- [ ] **Bước 5: Xoá tệp middleware**

```bash
git rm app/Http/Middleware/CheckFirstLogin.php
```

- [ ] **Bước 6: Cập nhật chú thích của test cũ**

Trong `tests/Unit/RouteOrderCheckTest.php`, đổi dòng 101 từ:

```php
        // Nhom ngoai cung cua web.php la ['auth', 'check.first.login']. Neu chen nhom moi
```

thành:

```php
        // Nhom ngoai cung cua web.php la ['auth']. Neu chen nhom moi
```

Test đó chỉ khẳng định `'auth'` nên nó vẫn xanh; chỉ chú thích là sai sự thật.

- [ ] **Bước 6b: Cập nhật hai tài liệu cũ nhắc tới nhóm middleware**

Hai tệp sau mô tả nhóm route ngoài cùng là `['auth', 'check.first.login']`:

- `docs/superpowers/specs/2026-07-29-order-check-menu-quyen-design.md:75`
- `docs/superpowers/plans/2026-07-29-order-check-menu-quyen.md:101`

Thêm vào **cuối** mỗi tệp một dòng, **không** sửa nội dung cũ (đó là ghi chép
lịch sử của một đợt làm khác, viết lại là làm mất dấu vết):

```markdown
> Cập nhật 2026-08-01: nhóm route ngoài cùng nay chỉ còn `['auth']` — middleware
> `check.first.login` đã bị xoá, xem
> `docs/superpowers/specs/2026-08-01-khoi-tao-superadmin-design.md`.
```

- [ ] **Bước 7: Chạy test canh gác để chắc chắn nó xanh**

```bash
./vendor/bin/phpunit tests/Unit/RouteKhongConCheckFirstLoginTest.php tests/Unit/RouteOrderCheckTest.php
```

Kỳ vọng: XANH cả hai tệp.

- [ ] **Bước 8: Chạy lại bộ Unit và đối chiếu mốc nền**

Vẫn **chỉ** bộ Unit, vì lý do đã nêu ở cảnh báo trong Nhiệm vụ 0 — bộ Feature
chứa `EmailReceiveReportTest` dùng `RefreshDatabase` và sẽ xoá sạch cơ sở dữ
liệu `qlbv`.

```bash
./vendor/bin/phpunit --testsuite Unit > /tmp/sau-khi-sua.txt 2>&1; tail -20 /tmp/sau-khi-sua.txt
./vendor/bin/phpunit tests/Feature/KhoiTaoSuperAdminTest.php
```

So dòng tổng kết của lệnh đầu với
`docs/superpowers/plans/moc-nen-test-2026-08-01.txt` ghi ở Nhiệm vụ 0. Kỳ vọng:
số lỗi và số test đỏ **không tăng**; số test bộ Unit tăng đúng 11 (8 của Nhiệm
vụ 1 + 3 của Nhiệm vụ 4). Lệnh thứ hai chạy riêng 8 test Feature mới, phải xanh
toàn bộ.

Nếu có test đỏ mới nằm ngoài danh sách mốc nền, dừng lại và xử lý trước khi
commit. Không được kết luận "đã xong" khi chưa đối chiếu xong bước này.

- [ ] **Bước 9: Commit**

```bash
git add -A
git commit -m "refactor: xoa middleware CheckFirstLogin, thay bang man khoi tao co chu dich

CheckFirstLogin chay tren moi route da xac thuc va gan superadministrator cho
nguoi dang nhap dau tien. Vi CustomUser tro vao acs_user cua HIS nen do la bat
ky nhan vien nao, khong phai nguoi cai dat. No cung doc thuoc tinh tren null
khi bang roles rong - dieu chac chan xay ra o ban cai moi chua chay
laratrust:seeder - va ton 2 truy van moi request cho mot khoi ma da chet.

Thay bang man /setup/quan-tri-dau-tien: cong mo khi va chi khi he thong chua co
superadministrator, dong vinh vien sau lan dau."
```

---

## Sau khi xong

- [ ] Chạy `git log --oneline main..fix/khoi-tao-superadmin` để rà lại 5 commit.
- [ ] Dùng skill `superpowers:requesting-code-review` trước khi gộp.
- [ ] Dùng skill `superpowers:finishing-a-development-branch` để quyết cách gộp
      vào `main`, và cân nhắc thứ tự so với nhánh `upgrade/laravel-13` (nhánh đó
      đang đi trước `main` 26 commit; gộp nhánh này vào `main` trước sẽ khiến
      `upgrade/laravel-13` phải rebase hoặc merge lại).

## Ngoài phạm vi

- Không đụng `database/seeds/LaratrustSeederSuperUser.php` (đang ghim cứng
  `User::find(473)`).
- Không đổi `CheckRole` hay cách `AppServiceProvider::filterMenu` xử lý
  `superadministrator`.
- Không thêm biến `.env` hay mã cài đặt nào. Nếu sau này muốn siết, phương án
  `SETUP_TOKEN` đã mô tả trong đặc tả là đường nâng cấp sẵn có.
