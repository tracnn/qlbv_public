# Kế hoạch triển khai màn tra cứu lỗi hồ sơ theo mã điều trị

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Một màn hình nội bộ cho phép nhập hoặc quét mã điều trị rồi hiện thông tin hồ sơ cùng ba nhóm lỗi (sai sót y lệnh, tra thẻ BHYT, XML3176).

**Architecture:** Controller mỏng `KHTH\TraCuuLoiHoSoController` gọi hai service: `TreatmentIssueService` (đã có, không sửa) lấy lỗi từ MySQL, và `TreatmentProfileService` (mới) lấy hồ sơ từ Oracle `HISPro`. Hai nguồn tách rời để một bên hỏng không kéo bên kia. Giao diện là một blade AdminLTE gọi AJAX.

**Tech Stack:** Laravel 5.5, PHP 7.0+, MySQL + Oracle (`yajra/laravel-oci8`), Laratrust 5.0, AdminLTE, jQuery, PHPUnit 6, `html5-qrcode` (vendor vào `public/js/`).

**Spec:** `docs/superpowers/specs/2026-08-17-tra-cuu-loi-ho-so-design.md`

## Global Constraints

- PHP 7.0 tối thiểu — **không** dùng cú pháp PHP 7.1+ (`void`, nullable type `?string`, hằng số có visibility, list keyed).
- Laravel 5.5: `Cache::put($key, $val, $minutes)` tính bằng **phút**; `$this->assertContains()` của PHPUnit 6 dùng cho chuỗi.
- Mockery vỡ khi mô phỏng phương thức có khai báo kiểu trả về — **không khai báo return type** trên phương thức public của service.
- Repo **đang có sẵn test đỏ**. Trước khi bắt đầu, chạy toàn bộ test và ghi lại danh sách test đỏ hiện có; chỉ so sánh với mốc đó, không coi "có test đỏ" là do mình.
- **Không** dùng `RefreshDatabase`: `.env` trỏ `DB_DATABASE=qlbv` là CSDL phát triển thật. Test dựng bảng trên SQLite bộ nhớ bằng cách ghi đè cấu hình kết nối.
- Tên bảng/cột Oracle viết **chữ thường** trong query builder, theo đúng mã hiện có.
- Chuỗi hiển thị cho người dùng viết tiếng Việt có dấu; chú thích trong mã nguồn viết **không dấu**, theo thông lệ repo.

---

## Cấu trúc tệp

| Tệp | Trách nhiệm |
|---|---|
| `app/Services/OrderCheck/TreatmentProfileService.php` | Chỉ một việc: đọc thông tin hồ sơ từ `HISPro`, trả mảng thuần hoặc `null` |
| `app/Http/Controllers/KHTH/TraCuuLoiHoSoController.php` | Đọc request, gọi hai service, trả JSON/blade. Không chứa truy vấn |
| `resources/views/khth/tra-cuu-loi-ho-so.blade.php` | Màn hình tra cứu |
| `resources/views/khth/tra-cuu-loi-ho-so-in.blade.php` | Bản in A4, không extend layout AdminLTE |
| `database/migrations/2026_08_17_090000_them_role_tra_cuu_loi_ho_so.php` | Tạo role, gán cho người đang có `order-check` |
| `tests/Support/DungBangHoSoHisSqlite.php` | Trait dựng 5 bảng `HISPro` trên SQLite bộ nhớ |
| `tests/Unit/TreatmentProfileServiceTest.php` | Test service hồ sơ |
| `tests/Feature/TraCuuLoiHoSoTest.php` | Test phân quyền + endpoint |
| `public/js/html5-qrcode.min.js` | Thư viện quét QR bằng camera |
| `routes/web.php` (sửa) | Nhóm route mới |
| `config/adminlte.php` (sửa) | Mục menu cấp 1 |

---

## Task 1: Trait dựng bảng HISPro trên SQLite

**Files:**
- Create: `tests/Support/DungBangHoSoHisSqlite.php`

**Interfaces:**
- Consumes: không
- Produces: trait `Tests\Support\DungBangHoSoHisSqlite` với `chuanBiBangHoSo()` và `themHoSo(array $ghiDe = [])`

- [ ] **Step 1: Viết trait**

Đây là hạ tầng test, không có test riêng; Task 2 dùng nó và sẽ chứng minh nó chạy.

Mẫu bám theo `tests/Support/DungBangLoiDotDieuTriSqlite.php` (đọc tệp đó trước).

```php
<?php

namespace Tests\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Dung 5 bang cua ket noi HISPro trong SQLite bo nho.
 *
 * VI SAO GHI DE CHINH KET NOI TEN 'HISPro': service goi
 * DB::connection('HISPro'), nen ghi de cau hinh cua dung ten do la cach duy nhat chan
 * duong ra Oracle that. Khong doi database.default o day - ket noi mac dinh 'mysql'
 * danh cho ba bang loi, hai trait co the dung chung trong mot test.
 */
trait DungBangHoSoHisSqlite
{
    protected function chuanBiBangHoSo()
    {
        config([
            'database.connections.HISPro' => [
                'driver'   => 'sqlite',
                'database' => ':memory:',
                'prefix'   => '',
            ],
        ]);

        DB::purge('HISPro');

        $s = Schema::connection('HISPro');

        $s->create('his_treatment', function ($t) {
            $t->increments('id');
            $t->string('treatment_code', 50);
            $t->string('tdl_patient_name', 200)->nullable();
            $t->string('tdl_patient_dob', 20)->nullable();
            $t->unsignedInteger('tdl_patient_gender_id')->nullable();
            $t->string('tdl_hein_card_number', 50)->nullable();
            $t->string('tdl_hein_medi_org_code', 20)->nullable();
            $t->string('tdl_hein_card_from_time', 20)->nullable();
            $t->string('tdl_hein_card_to_time', 20)->nullable();
            $t->unsignedInteger('branch_id')->nullable();
            $t->unsignedInteger('last_department_id')->nullable();
            $t->unsignedInteger('tdl_treatment_type_id')->nullable();
            $t->string('in_time', 20)->nullable();
            $t->string('out_time', 20)->nullable();
        });

        $s->create('his_gender', function ($t) {
            $t->increments('id');
            $t->string('gender_code', 10);
            $t->string('gender_name', 50);
        });

        $s->create('his_branch', function ($t) {
            $t->increments('id');
            $t->string('hein_medi_org_code', 20)->nullable();
        });

        $s->create('his_department', function ($t) {
            $t->increments('id');
            $t->string('department_name', 200);
        });

        $s->create('his_treatment_type', function ($t) {
            $t->increments('id');
            $t->string('treatment_type_name', 100);
        });

        DB::connection('HISPro')->table('his_gender')->insert([
            ['id' => 1, 'gender_code' => '1', 'gender_name' => 'Nam'],
            ['id' => 2, 'gender_code' => '2', 'gender_name' => 'Nữ'],
        ]);
        DB::connection('HISPro')->table('his_branch')->insert([
            ['id' => 10, 'hein_medi_org_code' => '01001'],
        ]);
        DB::connection('HISPro')->table('his_department')->insert([
            ['id' => 20, 'department_name' => 'Khoa Nội'],
        ]);
        DB::connection('HISPro')->table('his_treatment_type')->insert([
            ['id' => 30, 'treatment_type_name' => 'Nội trú'],
        ]);
    }

    /** Them mot ho so. $ghiDe ghi de bat ky cot nao. */
    protected function themHoSo(array $ghiDe = [])
    {
        DB::connection('HISPro')->table('his_treatment')->insert(array_merge([
            'treatment_code'          => '01013250800123',
            'tdl_patient_name'        => 'Nguyễn Văn A',
            'tdl_patient_dob'         => '19790220',
            'tdl_patient_gender_id'   => 1,
            'tdl_hein_card_number'    => 'DN4010112345678',
            'tdl_hein_medi_org_code'  => '01005',
            'tdl_hein_card_from_time' => '20260101',
            'tdl_hein_card_to_time'   => '20261231',
            'branch_id'               => 10,
            'last_department_id'      => 20,
            'tdl_treatment_type_id'   => 30,
            'in_time'                 => '202608050830',
            'out_time'                => null,
        ], $ghiDe));
    }
}
```

- [ ] **Step 2: Commit**

```bash
git add tests/Support/DungBangHoSoHisSqlite.php
git commit -m "test: them trait dung bang HISPro tren SQLite"
```

---

## Task 2: TreatmentProfileService

**Files:**
- Create: `app/Services/OrderCheck/TreatmentProfileService.php`
- Test: `tests/Unit/TreatmentProfileServiceTest.php`

**Interfaces:**
- Consumes: `Tests\Support\DungBangHoSoHisSqlite` (Task 1)
- Produces: `App\Services\OrderCheck\TreatmentProfileService::cua($treatmentCode)` → `array|null`. Khoá của mảng: `treatment_code`, `patient_name`, `patient_dob`, `patient_dob_text`, `gender_code`, `gender_name`, `hein_card_number`, `hein_medi_org_code`, `hein_card_from_time_text`, `hein_card_to_time_text`, `department_name`, `treatment_type_name`, `in_time_text`, `out_time_text`, `ma_cskcb`.

- [ ] **Step 1: Viết test thất bại**

Tạo `tests/Unit/TreatmentProfileServiceTest.php`:

```php
<?php

namespace Tests\Unit;

use App\Services\OrderCheck\TreatmentProfileService;
use Tests\Support\DungBangHoSoHisSqlite;
use Tests\TestCase;

class TreatmentProfileServiceTest extends TestCase
{
    use DungBangHoSoHisSqlite;

    protected function setUp()
    {
        parent::setUp();
        $this->chuanBiBangHoSo();
    }

    protected function service()
    {
        return new TreatmentProfileService();
    }

    /** @test */
    public function tra_du_thong_tin_ho_so()
    {
        $this->themHoSo();

        $ho = $this->service()->cua('01013250800123');

        $this->assertSame('01013250800123', $ho['treatment_code']);
        $this->assertSame('Nguyễn Văn A', $ho['patient_name']);
        $this->assertSame('20/02/1979', $ho['patient_dob_text']);
        $this->assertSame('Nam', $ho['gender_name']);
        $this->assertSame('1', (string) $ho['gender_code']);
        $this->assertSame('DN4010112345678', $ho['hein_card_number']);
        $this->assertSame('Khoa Nội', $ho['department_name']);
        $this->assertSame('Nội trú', $ho['treatment_type_name']);
        $this->assertSame('05/08/2026 08:30', $ho['in_time_text']);
        $this->assertNull($ho['out_time_text']);
    }

    /**
     * Canh quay lai loi cu: lay ma co so tu tdl_hein_medi_org_code (noi DKBD cua benh
     * nhan) thay vi tu his_branch (co so dieu tri).
     *
     * @test
     */
    public function ma_cskcb_lay_tu_his_branch_khong_phai_noi_dkbd()
    {
        $this->themHoSo();

        $ho = $this->service()->cua('01013250800123');

        $this->assertSame('01001', $ho['ma_cskcb']);
        $this->assertSame('01005', $ho['hein_medi_org_code']);
    }

    /** @test */
    public function ho_so_thieu_khoa_va_gioi_tinh_van_tra_ve()
    {
        $this->themHoSo([
            'treatment_code'        => 'HS-KHUYET',
            'last_department_id'    => null,
            'tdl_patient_gender_id' => null,
            'tdl_treatment_type_id' => null,
            'branch_id'             => null,
        ]);

        $ho = $this->service()->cua('HS-KHUYET');

        $this->assertNotNull($ho);
        $this->assertSame('HS-KHUYET', $ho['treatment_code']);
        $this->assertNull($ho['department_name']);
        $this->assertNull($ho['gender_code']);
        $this->assertNull($ho['ma_cskcb']);
    }

    /** @test */
    public function khong_tim_thay_thi_tra_null()
    {
        $this->assertNull($this->service()->cua('KHONG-CO'));
    }

    /** @test */
    public function ma_rong_tra_null_va_khong_truy_van()
    {
        $this->themHoSo();

        $this->assertNull($this->service()->cua(''));
        $this->assertNull($this->service()->cua('   '));
        $this->assertNull($this->service()->cua(null));
    }

    /** @test */
    public function ma_duoc_trim_truoc_khi_tra()
    {
        $this->themHoSo();

        $this->assertNotNull($this->service()->cua('  01013250800123  '));
    }
}
```

- [ ] **Step 2: Chạy test để chắc chắn nó đỏ**

```bash
vendor/bin/phpunit tests/Unit/TreatmentProfileServiceTest.php
```

Kỳ vọng: FAIL — `Class 'App\Services\OrderCheck\TreatmentProfileService' not found`.

- [ ] **Step 3: Viết service**

Tạo `app/Services/OrderCheck/TreatmentProfileService.php`:

```php
<?php

namespace App\Services\OrderCheck;

use Illuminate\Support\Facades\DB;

/**
 * Doc thong tin ho so cua mot dot dieu tri tu HIS (Oracle).
 *
 * Tach rieng khoi TreatmentIssueService co chu dich: hai nguon nam tren hai CSDL khac
 * nhau, Oracle hong khong duoc keo theo phan loi doc tu MySQL.
 */
class TreatmentProfileService
{
    /**
     * @param  string|null $treatmentCode
     * @return array|null  null khi ma rong hoac khong tim thay ho so
     */
    public function cua($treatmentCode)
    {
        $ma = trim((string) $treatmentCode);

        if ($ma === '') {
            return null;
        }

        $d = DB::connection('HISPro')->table('his_treatment')
            // TAT CA deu leftJoin: ho so khuyet du lieu chinh la ho so can soi. Inner
            // join lam no bien mat va nguoi dung tuong go sai ma.
            ->leftJoin('his_gender', 'his_gender.id', '=', 'his_treatment.tdl_patient_gender_id')
            ->leftJoin('his_branch', 'his_branch.id', '=', 'his_treatment.branch_id')
            ->leftJoin('his_department', 'his_department.id', '=', 'his_treatment.last_department_id')
            ->leftJoin('his_treatment_type', 'his_treatment_type.id', '=', 'his_treatment.tdl_treatment_type_id')
            ->where('his_treatment.treatment_code', $ma)
            ->select([
                'his_treatment.treatment_code',
                'his_treatment.tdl_patient_name',
                'his_treatment.tdl_patient_dob',
                'his_treatment.tdl_hein_card_number',
                'his_treatment.tdl_hein_medi_org_code',
                'his_treatment.tdl_hein_card_from_time',
                'his_treatment.tdl_hein_card_to_time',
                'his_treatment.in_time',
                'his_treatment.out_time',
                'his_gender.gender_code',
                'his_gender.gender_name',
                'his_department.department_name',
                'his_treatment_type.treatment_type_name',
                // Ma CO SO DIEU TRI. KHONG duoc thay bang tdl_hein_medi_org_code - cot do
                // la noi DKBD ghi tren the cua benh nhan. Do tren 45.995 ho so: hai gia
                // tri chi trung nhau 0,5%.
                'his_branch.hein_medi_org_code as ma_cskcb',
            ])
            ->first();

        if (!$d) {
            return null;
        }

        return [
            'treatment_code'           => $d->treatment_code,
            'patient_name'             => $d->tdl_patient_name,
            'patient_dob'              => $d->tdl_patient_dob,
            'patient_dob_text'         => $this->ngay($d->tdl_patient_dob, true),
            'gender_code'              => $d->gender_code,
            'gender_name'              => $d->gender_name,
            'hein_card_number'         => $d->tdl_hein_card_number,
            'hein_medi_org_code'       => $d->tdl_hein_medi_org_code,
            'hein_card_from_time_text' => $this->ngay($d->tdl_hein_card_from_time),
            'hein_card_to_time_text'   => $this->ngay($d->tdl_hein_card_to_time),
            'department_name'          => $d->department_name,
            'treatment_type_name'      => $d->treatment_type_name,
            'in_time_text'             => $this->ngay($d->in_time),
            'out_time_text'            => $this->ngay($d->out_time),
            'ma_cskcb'                 => $d->ma_cskcb,
        ];
    }

    /**
     * Chuoi ngay cua HIS (Ymd / YmdHi / YmdHis) sang dang nguoi doc.
     * Dung lai hai helper san co thay vi tu viet: chung da xu ly ca truong hop ngay sinh
     * chi co nam (0000 o giua).
     */
    protected function ngay($gia, $laNgaySinh = false)
    {
        $gia = trim((string) $gia);

        if ($gia === '') {
            return null;
        }

        return $laNgaySinh ? dob($gia) : strtodatetime($gia);
    }
}
```

- [ ] **Step 4: Chạy test để chắc chắn nó xanh**

```bash
vendor/bin/phpunit tests/Unit/TreatmentProfileServiceTest.php
```

Kỳ vọng: PASS, 6 test.

- [ ] **Step 5: Commit**

```bash
git add app/Services/OrderCheck/TreatmentProfileService.php tests/Unit/TreatmentProfileServiceTest.php
git commit -m "feat(tra-cuu-loi): service doc thong tin ho so tu HIS"
```

---

## Task 3: Migration tạo role `tra-cuu-loi-ho-so`

**Files:**
- Create: `database/migrations/2026_08_17_090000_them_role_tra_cuu_loi_ho_so.php`

**Interfaces:**
- Consumes: không
- Produces: role tên `tra-cuu-loi-ho-so` trong bảng `roles`

- [ ] **Step 1: Viết migration**

Đọc `database/migrations/2026_07_29_090000_them_role_order_check.php` trước — migration này là bản sao có chỉnh nguồn gán role, mọi chú thích ở đó vẫn còn hiệu lực.

```php
<?php

use App\Role;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Migrations\Migration;

/**
 * Role rieng cho man tra cuu loi ho so theo ma dieu tri.
 *
 * Vi sao la ROLE chu khong phai PERMISSION: menu di qua
 * AppServiceProvider::filterMenu, ham do CHI kiem hasRole(), khong co nhanh can(). Cap
 * bang permission thi route cho vao nhung menu van an.
 *
 * Vi sao gan theo order-check: do la nhom dang lam viec voi chinh ba nguon loi nay.
 * CheckRole KHONG mien tru superadministrator, nen superadmin nao thieu role se thay
 * menu nhung bam vao la 403 - ho nam trong nhom order-check nen duoc gan o day.
 */
class ThemRoleTraCuuLoiHoSo extends Migration
{
    const TEN = 'tra-cuu-loi-ho-so';

    public function up()
    {
        $role = Role::where('name', self::TEN)->first();

        if (!$role) {
            $role = Role::create([
                'name' => self::TEN,
                'display_name' => 'Tra cứu lỗi hồ sơ',
                'description' => 'Tra cứu lỗi hồ sơ theo mã điều trị',
            ]);
        }

        $nguon = Role::where('name', 'order-check')->first();

        if (!$nguon) {
            return;
        }

        foreach (DB::table('role_user')->where('role_id', $nguon->id)->get() as $r) {
            $daCo = DB::table('role_user')
                ->where('role_id', $role->id)
                ->where('user_id', $r->user_id)
                ->where('user_type', $r->user_type)
                ->exists();

            if ($daCo) {
                continue;
            }

            DB::table('role_user')->insert([
                'role_id'   => $role->id,
                'user_id'   => $r->user_id,
                'user_type' => $r->user_type,
            ]);

            // BAT BUOC: Laratrust cache hasRole() 60 PHUT, va insert qua query builder
            // khong ban su kien nen cache khong tu xoa. Thieu dong nay thi nguoi dang
            // dang nhap thay menu nhung bam vao bi 403 toi 60 phut.
            Cache::forget('laratrust_roles_for_user_' . $r->user_id);
        }
    }

    public function down()
    {
        $role = Role::where('name', self::TEN)->first();

        if (!$role) {
            return;
        }

        $userIds = DB::table('role_user')->where('role_id', $role->id)->pluck('user_id');

        DB::table('role_user')->where('role_id', $role->id)->delete();

        // KHONG dung $role->delete(): su kien "deleting" cua Laratrust goi quan he
        // users() toi App\CustomUser von nam tren ket noi Oracle ACS_RS, lam Laravel tim
        // bang role_user trong Oracle -> ORA-00942.
        DB::table('roles')->where('id', $role->id)->delete();

        foreach ($userIds as $userId) {
            Cache::forget('laratrust_roles_for_user_' . $userId);
        }
    }
}
```

- [ ] **Step 2: Chạy migration và kiểm tra bằng mắt**

```bash
php artisan migrate
```

Kỳ vọng: chạy xong không lỗi. Kiểm tra:

```bash
php artisan tinker --execute="echo App\Role::where('name','tra-cuu-loi-ho-so')->count();"
```

Kỳ vọng: in ra `1`.

- [ ] **Step 3: Kiểm tra rollback chạy được rồi migrate lại**

```bash
php artisan migrate:rollback --step=1
php artisan migrate
```

Kỳ vọng: cả hai lệnh không lỗi (đặc biệt lệnh rollback — đây là chỗ ORA-00942 từng xảy ra).

- [ ] **Step 4: Commit**

```bash
git add database/migrations/2026_08_17_090000_them_role_tra_cuu_loi_ho_so.php
git commit -m "feat(tra-cuu-loi): them role tra-cuu-loi-ho-so"
```

---

## Task 4: Route, controller, endpoint tra cứu

**Files:**
- Create: `app/Http/Controllers/KHTH/TraCuuLoiHoSoController.php`
- Create: `resources/views/khth/tra-cuu-loi-ho-so.blade.php` (khung tối thiểu, Task 5 dựng đủ)
- Modify: `routes/web.php` (thêm nhóm ngay sau nhóm `checkrole:order-check`, khoảng dòng 691)
- Modify: `config/adminlte.php` (thêm mục menu)
- Test: `tests/Feature/TraCuuLoiHoSoTest.php`

**Interfaces:**
- Consumes: `TreatmentProfileService::cua()` (Task 2), role từ Task 3, `App\Services\OrderCheck\TreatmentIssueService::cua($treatmentCode, array $tuyChon = [])` → `['data' => [...], 'summary' => [...]]` (đã có sẵn)
- Produces: route `khth.tra-cuu-loi-ho-so` (GET, trả blade) và `khth.tra-cuu-loi-ho-so-tra-cuu` (GET, trả JSON `{profile, profile_error, data, summary}`)

- [ ] **Step 1: Viết test thất bại**

Tạo `tests/Feature/TraCuuLoiHoSoTest.php`:

```php
<?php

namespace Tests\Feature;

use Tests\Support\DungBangHoSoHisSqlite;
use Tests\Support\DungBangLoiDotDieuTriSqlite;
use Tests\TestCase;

/** Nguoi dung gia: hasRole() tra true dung cho danh sach role duoc cap. */
class NguoiDungCoRole extends \App\User
{
    public $roles = [];

    public function hasRole($role, $team = null, $requireAll = false)
    {
        return in_array($role, $this->roles);
    }

    public function can($permission, $team = null, $requireAll = false)
    {
        return false;
    }
}

class TraCuuLoiHoSoTest extends TestCase
{
    use DungBangLoiDotDieuTriSqlite;
    use DungBangHoSoHisSqlite;

    const MA = '01013250800123';

    protected function setUp()
    {
        parent::setUp();
        $this->chuanBiBangLoi();
        $this->chuanBiBangHoSo();
    }

    protected function nguoiDung(array $roles)
    {
        $u = new NguoiDungCoRole();
        $u->id = 1;
        $u->roles = $roles;

        return $u;
    }

    protected function traCuu($ma, array $roles = ['tra-cuu-loi-ho-so'])
    {
        return $this->actingAs($this->nguoiDung($roles))
            ->getJson('/khth/tra-cuu-loi-ho-so/tra-cuu?treatment_code=' . urlencode($ma));
    }

    /** @test */
    public function khong_co_role_thi_403()
    {
        $this->actingAs($this->nguoiDung(['xml-man']))
            ->get('/khth/tra-cuu-loi-ho-so')
            ->assertStatus(403);
    }

    /** @test */
    public function co_role_thi_mo_duoc_man_hinh()
    {
        $this->actingAs($this->nguoiDung(['tra-cuu-loi-ho-so']))
            ->get('/khth/tra-cuu-loi-ho-so')
            ->assertStatus(200);
    }

    /** @test */
    public function ho_so_co_loi_tra_du_ho_so_va_ba_nhom()
    {
        $this->themHoSo();
        $this->themViPham(['treatment_code' => self::MA, 'severity' => 'critical']);

        $this->traCuu(self::MA)
            ->assertStatus(200)
            ->assertJson([
                'profile' => ['patient_name' => 'Nguyễn Văn A', 'ma_cskcb' => '01001'],
                'summary' => ['order_check' => 1, 'has_error' => true],
            ])
            ->assertJsonStructure([
                'profile', 'profile_error',
                'data' => ['treatment_code', 'order_check', 'hein_card', 'xml3176'],
                'summary' => ['total', 'critical', 'has_error'],
            ]);
    }

    /** @test */
    public function ho_so_sach_van_tra_200_va_khong_co_loi()
    {
        $this->themHoSo();

        $res = $this->traCuu(self::MA)->assertStatus(200);

        $res->assertJson(['summary' => ['total' => 0, 'has_error' => false]]);
        $this->assertSame([], $res->json()['data']['order_check']);
    }

    /** @test */
    public function khong_co_ho_so_tren_his_nhung_van_tra_loi_cua_mysql()
    {
        $this->themViPham(['treatment_code' => self::MA]);

        $res = $this->traCuu(self::MA)->assertStatus(200);

        $this->assertNull($res->json()['profile']);
        $this->assertCount(1, $res->json()['data']['order_check']);
    }

    /** @test */
    public function ma_rong_tra_422()
    {
        $this->traCuu('')
            ->assertStatus(422)
            ->assertJson(['message' => 'Chưa nhập mã điều trị']);
    }
}
```

- [ ] **Step 2: Chạy test để chắc chắn nó đỏ**

```bash
vendor/bin/phpunit tests/Feature/TraCuuLoiHoSoTest.php
```

Kỳ vọng: FAIL — route không tồn tại (404 thay vì 403/200).

- [ ] **Step 3: Thêm nhóm route**

Trong `routes/web.php`, ngay sau nhóm `checkrole:order-check` (kết thúc khoảng dòng 691), **bên trong** nhóm `['middleware' => ['auth']]` mở ở dòng 71:

```php
    // Tra cuu loi ho so theo ma dieu tri — quyen rieng, de khoa quet ma tra nhanh ma
    // khong phai mo quyen quan tri y lenh.
    Route::group(['prefix' => 'khth/', 'middleware' => ['checkrole:tra-cuu-loi-ho-so']], function () {
        Route::get('tra-cuu-loi-ho-so', 'KHTH\TraCuuLoiHoSoController@index')
            ->name('khth.tra-cuu-loi-ho-so');
        Route::get('tra-cuu-loi-ho-so/tra-cuu', 'KHTH\TraCuuLoiHoSoController@traCuu')
            ->name('khth.tra-cuu-loi-ho-so-tra-cuu');
    });
```

- [ ] **Step 4: Viết controller**

Tạo `app/Http/Controllers/KHTH/TraCuuLoiHoSoController.php`:

```php
<?php

namespace App\Http\Controllers\KHTH;

use App\Http\Controllers\Controller;
use App\Services\OrderCheck\TreatmentIssueService;
use App\Services\OrderCheck\TreatmentProfileService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TraCuuLoiHoSoController extends Controller
{
    protected $loi;
    protected $hoSo;

    public function __construct(TreatmentIssueService $loi, TreatmentProfileService $hoSo)
    {
        $this->loi = $loi;
        $this->hoSo = $hoSo;
    }

    public function index()
    {
        return view('khth.tra-cuu-loi-ho-so');
    }

    public function traCuu(Request $request)
    {
        $ma = trim((string) $request->input('treatment_code'));

        if ($ma === '') {
            return response()->json(['message' => 'Chưa nhập mã điều trị'], 422);
        }

        $ketQua = $this->loi->cua($ma);

        // Oracle hong khong duoc keo theo phan loi doc tu MySQL: bat rieng o day, tra
        // profile_error de man hinh hien mot dong canh bao thay vi trang trang.
        $hoSo = null;
        $loiHoSo = null;

        try {
            $hoSo = $this->hoSo->cua($ma);
        } catch (\Exception $e) {
            $loiHoSo = 'Không lấy được thông tin từ HIS';
            Log::error('Tra cuu loi ho so: loi doc HIS', [
                'treatment_code' => $ma,
                'loi' => $e->getMessage(),
            ]);
        }

        return response()->json([
            'profile' => $hoSo,
            'profile_error' => $loiHoSo,
            'data' => $ketQua['data'],
            'summary' => $ketQua['summary'],
        ]);
    }
}
```

- [ ] **Step 5: Tạo blade khung tối thiểu**

Tạo `resources/views/khth/tra-cuu-loi-ho-so.blade.php` — đủ để route `index` trả 200; Task 5 dựng đủ:

```blade
@extends('adminlte::page')
@section('title', 'Tra cứu lỗi hồ sơ')
@section('content_header')<h1>Tra cứu lỗi hồ sơ</h1>@stop

@section('content')
<div class="box box-primary"><div class="box-body">
  <div id="tclhs-app"></div>
</div></div>
@stop
```

- [ ] **Step 6: Thêm mục menu**

Trong `config/adminlte.php`, thêm mục **cấp 1** (ngang hàng với mục `'text' => 'Kiểm tra sai sót y lệnh'`, không phải mục con của nó):

```php
        [
            'text'      => 'Tra cứu lỗi hồ sơ',
            'icon'      => 'barcode',
            'checkrole' => 'tra-cuu-loi-ho-so',
            'route'     => 'khth.tra-cuu-loi-ho-so',
            'active'    => ['khth/tra-cuu-loi-ho-so*'],
        ],
```

Không đặt làm mục con của "Kiểm tra sai sót y lệnh": mục cha đó có `checkrole: order-check` nên người khoa sẽ không nhìn thấy mục con.

- [ ] **Step 7: Chạy test để chắc chắn nó xanh**

```bash
vendor/bin/phpunit tests/Feature/TraCuuLoiHoSoTest.php
```

Kỳ vọng: PASS, 6 test.

Nếu riêng `co_role_thi_mo_duoc_man_hinh` đỏ vì layout `adminlte::page` đụng CSDL khi dựng
thanh menu (kết nối `mysql` lúc này là SQLite bộ nhớ chỉ có ba bảng lỗi): **không** đổi
blade để né. Dựng thêm bảng mà layout cần vào trait `DungBangLoiDotDieuTriSqlite`, hoặc
nếu bảng đó nằm trên Oracle thì đổi test thành `assertStatus(200)` trên endpoint
`tra-cuu` và kiểm 403 của route `index` là đủ — 403 xảy ra ở middleware, trước khi layout
được dựng, nên nó vẫn kiểm đúng thứ cần kiểm. Ghi lại lựa chọn vào chú thích của test.

- [ ] **Step 8: Commit**

```bash
git add app/Http/Controllers/KHTH/TraCuuLoiHoSoController.php resources/views/khth/tra-cuu-loi-ho-so.blade.php routes/web.php config/adminlte.php tests/Feature/TraCuuLoiHoSoTest.php
git commit -m "feat(tra-cuu-loi): route, controller va endpoint tra cuu"
```

---

## Task 5: Giao diện tra cứu

**Files:**
- Modify: `resources/views/khth/tra-cuu-loi-ho-so.blade.php` (thay toàn bộ khung ở Task 4)

**Interfaces:**
- Consumes: JSON `{profile, profile_error, data: {order_check, hein_card, xml3176}, summary}` từ `khth.tra-cuu-loi-ho-so-tra-cuu` (Task 4); route sẵn có `khth.order-check-update-status` nhận `{id, status, note?}` cho **một** vi phạm
- Produces: không (tầng ngoài cùng)

Tập cột mỗi nhóm lấy đúng những gì `TreatmentIssueService` trả — đọc mục A2 của
`docs/superpowers/specs/2026-08-06-order-check-api-gop-loi-design.md` trước khi dựng bảng.

- [ ] **Step 1: Viết blade đầy đủ**

```blade
@extends('adminlte::page')
@section('title', 'Tra cứu lỗi hồ sơ')
@section('content_header')<h1>Tra cứu lỗi hồ sơ</h1>@stop

@section('content')
<div class="box box-primary">
  <div class="box-body">
    <div class="row">
      <div class="col-md-6">
        <label>Mã điều trị</label>
        <div class="input-group">
          <input type="text" id="ma-dieu-tri" class="form-control input-lg"
                 placeholder="Nhập hoặc quét mã điều trị" autofocus autocomplete="off">
          <span class="input-group-btn">
            <button id="btn-tra-cuu" class="btn btn-primary btn-lg"><i class="fa fa-search"></i> Tra cứu</button>
          </span>
        </div>
        <p class="help-block" id="loi-nhap" style="color:#dd4b39"></p>
      </div>
    </div>
  </div>
</div>

<div id="ket-qua" style="display:none">
  <div class="box box-solid">
    <div class="box-header with-border"><h3 class="box-title">Thông tin hồ sơ</h3></div>
    <div class="box-body" id="khoi-ho-so"></div>
  </div>

  <div class="callout callout-success" id="khong-loi" style="display:none">
    <h4>Không phát hiện lỗi trên hồ sơ này</h4>
  </div>

  <div class="box box-danger">
    <div class="box-header with-border">
      <h3 class="box-title">Sai sót y lệnh <span class="badge" id="dem-order-check">0</span></h3>
    </div>
    <div class="box-body table-responsive" id="khoi-order-check"></div>
  </div>

  <div class="box box-warning">
    <div class="box-header with-border">
      <h3 class="box-title">Lỗi tra thẻ BHYT <span class="badge" id="dem-hein-card">0</span></h3>
    </div>
    <div class="box-body table-responsive" id="khoi-hein-card"></div>
  </div>

  <div class="box box-warning">
    <div class="box-header with-border">
      <h3 class="box-title">Lỗi XML3176 <span class="badge" id="dem-xml3176">0</span></h3>
    </div>
    <div class="box-body table-responsive" id="khoi-xml3176"></div>
  </div>
</div>
@stop

@section('js')
<script>
$(function () {
  var URL_TRA_CUU = '{{ route('khth.tra-cuu-loi-ho-so-tra-cuu') }}';
  var URL_DOI_TRANG_THAI = '{{ route('khth.order-check-update-status') }}';
  var DUOC_DOI_TRANG_THAI = {{ Auth::user()->hasRole('order-check') ? 'true' : 'false' }};
  var maHienTai = '';

  function thoat(s) {
    return $('<div>').text(s === null || s === undefined ? '' : s).html();
  }

  function bang(cot, dong, veDong) {
    if (!dong.length) { return '<p class="text-muted">Không có</p>'; }
    var h = '<table class="table table-bordered table-condensed"><thead><tr>';
    cot.forEach(function (c) { h += '<th>' + thoat(c) + '</th>'; });
    h += '</tr></thead><tbody>';
    dong.forEach(function (d) { h += veDong(d); });
    return h + '</tbody></table>';
  }

  function veHoSo(ho, loi) {
    if (loi) { return '<p style="color:#dd4b39">' + thoat(loi) + '</p>'; }
    if (!ho) { return '<p style="color:#dd4b39">Không tìm thấy hồ sơ với mã này trên HIS</p>'; }

    var truong = [
      ['Mã điều trị', ho.treatment_code], ['Họ tên', ho.patient_name],
      ['Ngày sinh', ho.patient_dob_text], ['Giới tính', ho.gender_name],
      ['Mã thẻ BHYT', ho.hein_card_number], ['Nơi ĐKBĐ', ho.hein_medi_org_code],
      ['Hạn thẻ từ', ho.hein_card_from_time_text], ['Hạn thẻ đến', ho.hein_card_to_time_text],
      ['Khoa', ho.department_name], ['Loại điều trị', ho.treatment_type_name],
      ['Vào lúc', ho.in_time_text], ['Ra lúc', ho.out_time_text],
      ['Cơ sở KCB', ho.ma_cskcb]
    ];

    var h = '<div class="row">';
    truong.forEach(function (t) {
      h += '<div class="col-md-4"><strong>' + thoat(t[0]) + ':</strong> ' +
           thoat(t[1] || '—') + '</div>';
    });
    return h + '</div>';
  }

  function veOrderCheck(dong) {
    var cot = ['Mức độ', 'Luật', 'Nội dung', 'Phát hiện lúc', 'Trạng thái'];
    if (DUOC_DOI_TRANG_THAI) { cot.push('Xử lý'); }

    return bang(cot, dong, function (d) {
      var nhan = d.severity === 'critical'
        ? '<span class="label label-danger">Nghiêm trọng</span>'
        : '<span class="label label-warning">' + thoat(d.severity) + '</span>';
      var h = '<tr><td>' + nhan + '</td><td>' + thoat(d.rule_code) + '</td><td>' +
              thoat(d.message) + '</td><td>' + thoat(d.detected_at) + '</td><td>' +
              thoat(d.status) + '</td>';
      if (DUOC_DOI_TRANG_THAI) {
        h += '<td><select class="form-control input-sm doi-trang-thai" data-id="' + d.id + '">' +
             '<option value="">— đổi —</option><option value="seen">Đã xem</option>' +
             '<option value="processed">Đã xử lý</option>' +
             '<option value="false_positive">Bỏ qua</option></select></td>';
      }
      return h + '</tr>';
    });
  }

  function veHeinCard(dong) {
    return bang(['Mã tra cứu', 'Mã kiểm tra', 'Kết quả', 'Ghi chú', 'Mã thẻ', 'Tra lúc'], dong, function (d) {
      return '<tr><td>' + thoat(d.ma_tracuu) + '</td><td>' + thoat(d.ma_kiemtra) +
             '</td><td>' + thoat(d.ma_ketqua) + '</td><td>' + thoat(d.ghi_chu) +
             '</td><td>' + thoat(d.ma_the_masked) + '</td><td>' + thoat(d.checked_at) + '</td></tr>';
    });
  }

  function veXml3176(dong) {
    return bang(['XML', 'STT', 'Mã lỗi', 'Tên lỗi', 'Mô tả', 'Ngày YL'], dong, function (d) {
      var ma = d.critical_error
        ? '<span class="label label-danger">' + thoat(d.error_code) + '</span>'
        : thoat(d.error_code);
      return '<tr><td>' + thoat(d.xml) + '</td><td>' + thoat(d.stt) + '</td><td>' + ma +
             '</td><td>' + thoat(d.error_name) + '</td><td>' + thoat(d.description) +
             '</td><td>' + thoat(d.ngay_yl) + '</td></tr>';
    });
  }

  function traCuu() {
    var ma = $.trim($('#ma-dieu-tri').val());
    $('#loi-nhap').text('');

    if (!ma) { $('#loi-nhap').text('Chưa nhập mã điều trị'); return; }

    $.getJSON(URL_TRA_CUU, { treatment_code: ma })
      .done(function (r) {
        maHienTai = ma;
        $('#khoi-ho-so').html(veHoSo(r.profile, r.profile_error));
        $('#khoi-order-check').html(veOrderCheck(r.data.order_check));
        $('#khoi-hein-card').html(veHeinCard(r.data.hein_card));
        $('#khoi-xml3176').html(veXml3176(r.data.xml3176));
        $('#dem-order-check').text(r.summary.order_check);
        $('#dem-hein-card').text(r.summary.hein_card);
        $('#dem-xml3176').text(r.summary.xml3176);
        $('#khong-loi').toggle(!r.summary.has_error);
        $('#ket-qua').show();
        // Boi den de luot quet ke tiep ghi de: may quet barcode go chuoi roi gui Enter.
        $('#ma-dieu-tri').focus().select();
      })
      .fail(function (x) {
        var t = (x.responseJSON && x.responseJSON.message) || 'Không tra cứu được';
        $('#loi-nhap').text(t);
      });
  }

  $('#btn-tra-cuu').on('click', traCuu);
  $('#ma-dieu-tri').on('keydown', function (e) {
    if (e.which === 13) { e.preventDefault(); traCuu(); }
  });

  $(document).on('change', '.doi-trang-thai', function () {
    var $s = $(this), status = $s.val();
    if (!status) { return; }

    $.post(URL_DOI_TRANG_THAI, {
      _token: '{{ csrf_token() }}', id: $s.data('id'), status: status
    }).done(function () {
      $('#ma-dieu-tri').val(maHienTai);
      traCuu();
    }).fail(function () {
      alert('Không đổi được trạng thái');
      $s.val('');
    });
  });
});
</script>
@stop
```

- [ ] **Step 2: Kiểm tra bằng trình duyệt**

Mở `/khth/tra-cuu-loi-ho-so` bằng tài khoản có role, nhập một mã điều trị đã biết là có lỗi. Kỳ vọng:

- Hồ sơ hiện đủ 13 trường.
- Ba khối hiện đúng số dòng, badge khớp.
- Sau khi tra xong, con trỏ nằm trong ô nhập và nội dung được bôi đen.
- Nhập mã của hồ sơ sạch → hiện dải xanh "Không phát hiện lỗi".
- Đăng nhập bằng tài khoản chỉ có `tra-cuu-loi-ho-so` (không có `order-check`) → cột "Xử lý" không xuất hiện.

- [ ] **Step 3: Chạy lại test của Task 4 để chắc chắn blade không làm hỏng route**

```bash
vendor/bin/phpunit tests/Feature/TraCuuLoiHoSoTest.php
```

Kỳ vọng: PASS, 6 test.

- [ ] **Step 4: Commit**

```bash
git add resources/views/khth/tra-cuu-loi-ho-so.blade.php
git commit -m "feat(tra-cuu-loi): giao dien tra cuu va doi trang thai vi pham"
```

---

## Task 6: Quét QR bằng camera

**Files:**
- Create: `public/js/html5-qrcode.min.js`
- Modify: `resources/views/khth/tra-cuu-loi-ho-so.blade.php`

**Interfaces:**
- Consumes: hàm `traCuu()` và ô `#ma-dieu-tri` (Task 5)
- Produces: không

- [ ] **Step 1: Vendor thư viện vào public/js**

```bash
curl -L -o public/js/html5-qrcode.min.js https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js
```

Kiểm tra tệp tải về đúng là JavaScript, không phải trang lỗi HTML:

```bash
head -c 200 public/js/html5-qrcode.min.js
```

Không nhúng CDN: các màn khác của app đều vendor tại chỗ, và máy chủ nội bộ không chắc ra được internet. Nếu máy đang làm việc cũng không ra được internet, tải tệp ở máy khác rồi chép vào.

- [ ] **Step 2: Thêm nút và vùng camera vào blade**

Chèn ngay sau khối `<div class="col-md-6">` chứa ô nhập, trong cùng `<div class="row">`:

```blade
      <div class="col-md-3">
        <label>&nbsp;</label>
        <div>
          <button id="btn-camera" class="btn btn-default btn-lg" style="display:none">
            <i class="fa fa-camera"></i> Quét bằng camera
          </button>
          <p class="help-block" id="camera-khong-san-sang" style="display:none">
            Trình duyệt không cho dùng camera ở trang này (cần HTTPS).
          </p>
        </div>
      </div>
```

Và vùng hiển thị camera, đặt ngay dưới `<div class="row">` đó:

```blade
    <div class="row" id="vung-camera" style="display:none; margin-top:10px">
      <div class="col-md-6">
        <div id="khung-camera"></div>
        <button id="btn-dong-camera" class="btn btn-default" style="margin-top:5px">Đóng camera</button>
      </div>
    </div>
```

- [ ] **Step 3: Thêm script**

Nạp thư viện trước khối `<script>` hiện có trong `@section('js')`:

```blade
<script src="{{ asset('js/html5-qrcode.min.js') }}"></script>
```

Và thêm vào cuối hàm `$(function () { ... })` của Task 5, trước dấu đóng:

```javascript
  // Camera chi kha dung tren HTTPS (hoac localhost). Tren HTTP thuan
  // navigator.mediaDevices khong ton tai -> hien chu thich thay vi mot nut bam khong an.
  var coCamera = !!(navigator.mediaDevices && navigator.mediaDevices.getUserMedia);
  $(coCamera ? '#btn-camera' : '#camera-khong-san-sang').show();

  var mayQuet = null;

  function dongCamera() {
    if (!mayQuet) { return; }
    mayQuet.stop().then(function () {
      mayQuet.clear();
      mayQuet = null;
      $('#vung-camera').hide();
    });
  }

  $('#btn-camera').on('click', function () {
    if (mayQuet) { return; }

    $('#vung-camera').show();
    mayQuet = new Html5Qrcode('khung-camera');
    mayQuet.start(
      { facingMode: 'environment' },
      { fps: 10, qrbox: 250 },
      function (ma) {
        $('#ma-dieu-tri').val(ma);
        dongCamera();
        traCuu();
      },
      function () { /* moi khung hinh khong doc duoc deu goi vao day - bo qua */ }
    ).catch(function () {
      $('#vung-camera').hide();
      mayQuet = null;
      alert('Không mở được camera');
    });
  });

  $('#btn-dong-camera').on('click', dongCamera);
```

- [ ] **Step 4: Kiểm tra bằng trình duyệt**

Mở màn hình trên máy có camera qua HTTPS: bấm "Quét bằng camera" → khung hình hiện, quét một mã QR chứa mã điều trị → camera đóng, kết quả hiện ngay.

Mở qua HTTP thuần: nút không hiện, thay bằng dòng chú thích.

- [ ] **Step 5: Commit**

```bash
git add public/js/html5-qrcode.min.js resources/views/khth/tra-cuu-loi-ho-so.blade.php
git commit -m "feat(tra-cuu-loi): quet ma bang camera"
```

---

## Task 7: In phiếu lỗi

**Files:**
- Create: `resources/views/khth/tra-cuu-loi-ho-so-in.blade.php`
- Modify: `app/Http/Controllers/KHTH/TraCuuLoiHoSoController.php` (thêm `in()`)
- Modify: `routes/web.php` (thêm route `tra-cuu-loi-ho-so/in`)
- Modify: `resources/views/khth/tra-cuu-loi-ho-so.blade.php` (thêm nút In)
- Test: `tests/Feature/TraCuuLoiHoSoTest.php` (thêm test)

**Interfaces:**
- Consumes: `TreatmentProfileService::cua()`, `TreatmentIssueService::cua()`
- Produces: route `khth.tra-cuu-loi-ho-so-in` (GET `?treatment_code=`) trả HTML

- [ ] **Step 1: Viết test thất bại**

Thêm vào `tests/Feature/TraCuuLoiHoSoTest.php`:

```php
    /** @test */
    public function trang_in_hien_ho_so_va_loi()
    {
        $this->themHoSo();
        $this->themViPham(['treatment_code' => self::MA, 'message' => 'Loi y lenh in thu']);

        $res = $this->actingAs($this->nguoiDung(['tra-cuu-loi-ho-so']))
            ->get('/khth/tra-cuu-loi-ho-so/in?treatment_code=' . self::MA);

        $res->assertStatus(200);
        $res->assertSee('Nguyễn Văn A');
        $res->assertSee('Loi y lenh in thu');
    }

    /** @test */
    public function trang_in_thieu_ma_thi_422()
    {
        $this->actingAs($this->nguoiDung(['tra-cuu-loi-ho-so']))
            ->get('/khth/tra-cuu-loi-ho-so/in')
            ->assertStatus(422);
    }
```

- [ ] **Step 2: Chạy test để chắc chắn nó đỏ**

```bash
vendor/bin/phpunit tests/Feature/TraCuuLoiHoSoTest.php --filter trang_in
```

Kỳ vọng: FAIL — 404 vì route chưa có.

- [ ] **Step 3: Thêm route**

Trong nhóm `checkrole:tra-cuu-loi-ho-so` của `routes/web.php`:

```php
        Route::get('tra-cuu-loi-ho-so/in', 'KHTH\TraCuuLoiHoSoController@in')
            ->name('khth.tra-cuu-loi-ho-so-in');
```

- [ ] **Step 4: Thêm phương thức `in()` vào controller**

```php
    public function in(Request $request)
    {
        $ma = trim((string) $request->input('treatment_code'));

        if ($ma === '') {
            return response('Chưa nhập mã điều trị', 422);
        }

        $ketQua = $this->loi->cua($ma);

        $hoSo = null;

        try {
            $hoSo = $this->hoSo->cua($ma);
        } catch (\Exception $e) {
            Log::error('Tra cuu loi ho so: loi doc HIS khi in', [
                'treatment_code' => $ma,
                'loi' => $e->getMessage(),
            ]);
        }

        return view('khth.tra-cuu-loi-ho-so-in', [
            'ma' => $ma,
            'hoSo' => $hoSo,
            'data' => $ketQua['data'],
            'summary' => $ketQua['summary'],
        ]);
    }
```

- [ ] **Step 5: Viết blade in**

Tạo `resources/views/khth/tra-cuu-loi-ho-so-in.blade.php` — **không** extend layout AdminLTE:

```blade
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="utf-8">
<title>Phiếu lỗi hồ sơ {{ $ma }}</title>
<style>
  @page { size: A4 portrait; margin: 12mm; }
  body { font-family: "Times New Roman", serif; font-size: 13px; color: #000; }
  h1 { font-size: 16px; text-align: center; margin: 0 0 4px; }
  h2 { font-size: 14px; margin: 14px 0 4px; }
  table { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
  th, td { border: 1px solid #000; padding: 3px 5px; vertical-align: top; }
  th { background: #eee; }
  .ho-so td { border: none; padding: 2px 4px; }
  .chan { margin-top: 10px; text-align: right; font-style: italic; }
</style>
</head>
<body onload="window.print()">

<h1>PHIẾU LỖI HỒ SƠ</h1>
<p style="text-align:center">Mã điều trị: <strong>{{ $ma }}</strong></p>

<h2>Thông tin hồ sơ</h2>
@if ($hoSo)
<table class="ho-so">
  <tr><td>Họ tên: <strong>{{ $hoSo['patient_name'] }}</strong></td>
      <td>Ngày sinh: {{ $hoSo['patient_dob_text'] }}</td>
      <td>Giới tính: {{ $hoSo['gender_name'] }}</td></tr>
  <tr><td>Mã thẻ BHYT: {{ $hoSo['hein_card_number'] }}</td>
      <td>Nơi ĐKBĐ: {{ $hoSo['hein_medi_org_code'] }}</td>
      <td>Cơ sở KCB: {{ $hoSo['ma_cskcb'] }}</td></tr>
  <tr><td>Khoa: {{ $hoSo['department_name'] }}</td>
      <td>Loại điều trị: {{ $hoSo['treatment_type_name'] }}</td>
      <td>Vào/ra: {{ $hoSo['in_time_text'] }} — {{ $hoSo['out_time_text'] }}</td></tr>
</table>
@else
<p><em>Không tìm thấy hồ sơ với mã này trên HIS.</em></p>
@endif

<h2>Sai sót y lệnh ({{ $summary['order_check'] }})</h2>
@if (count($data['order_check']))
<table>
  <tr><th>Mức độ</th><th>Luật</th><th>Nội dung</th><th>Phát hiện lúc</th><th>Trạng thái</th></tr>
  @foreach ($data['order_check'] as $d)
  <tr><td>{{ $d['severity'] }}</td><td>{{ $d['rule_code'] }}</td><td>{{ $d['message'] }}</td>
      <td>{{ $d['detected_at'] }}</td><td>{{ $d['status'] }}</td></tr>
  @endforeach
</table>
@else<p><em>Không có</em></p>@endif

<h2>Lỗi tra thẻ BHYT ({{ $summary['hein_card'] }})</h2>
@if (count($data['hein_card']))
<table>
  <tr><th>Mã tra cứu</th><th>Mã kiểm tra</th><th>Kết quả</th><th>Ghi chú</th><th>Tra lúc</th></tr>
  @foreach ($data['hein_card'] as $d)
  <tr><td>{{ $d['ma_tracuu'] }}</td><td>{{ $d['ma_kiemtra'] }}</td><td>{{ $d['ma_ketqua'] }}</td>
      <td>{{ $d['ghi_chu'] }}</td><td>{{ $d['checked_at'] }}</td></tr>
  @endforeach
</table>
@else<p><em>Không có</em></p>@endif

<h2>Lỗi XML3176 ({{ $summary['xml3176'] }})</h2>
@if (count($data['xml3176']))
<table>
  <tr><th>XML</th><th>STT</th><th>Mã lỗi</th><th>Tên lỗi</th><th>Mô tả</th></tr>
  @foreach ($data['xml3176'] as $d)
  <tr><td>{{ $d['xml'] }}</td><td>{{ $d['stt'] }}</td><td>{{ $d['error_code'] }}</td>
      <td>{{ $d['error_name'] }}</td><td>{{ $d['description'] }}</td></tr>
  @endforeach
</table>
@else<p><em>Không có</em></p>@endif

<p class="chan">In lúc {{ date('d/m/Y H:i') }}</p>
</body>
</html>
```

- [ ] **Step 6: Thêm nút In vào màn tra cứu**

Trong `resources/views/khth/tra-cuu-loi-ho-so.blade.php`, thêm vào `<div id="ket-qua">` ngay trước box "Thông tin hồ sơ":

```blade
  <p><a id="btn-in" class="btn btn-default" target="_blank"><i class="fa fa-print"></i> In phiếu lỗi</a></p>
```

Và trong hàm `traCuu()`, ngay sau `$('#ket-qua').show();`:

```javascript
        $('#btn-in').attr('href',
          '{{ route('khth.tra-cuu-loi-ho-so-in') }}?treatment_code=' + encodeURIComponent(ma));
```

- [ ] **Step 7: Chạy test để chắc chắn nó xanh**

```bash
vendor/bin/phpunit tests/Feature/TraCuuLoiHoSoTest.php
```

Kỳ vọng: PASS, 8 test.

- [ ] **Step 8: Commit**

```bash
git add resources/views/khth/tra-cuu-loi-ho-so-in.blade.php resources/views/khth/tra-cuu-loi-ho-so.blade.php app/Http/Controllers/KHTH/TraCuuLoiHoSoController.php routes/web.php tests/Feature/TraCuuLoiHoSoTest.php
git commit -m "feat(tra-cuu-loi): in phieu loi ho so"
```

---

## Task 8: Tra lại thẻ BHYT

**Files:**
- Modify: `app/Http/Controllers/KHTH/TraCuuLoiHoSoController.php` (thêm `traLaiThe()`)
- Modify: `routes/web.php` (thêm route có `throttle:5,1`)
- Modify: `resources/views/khth/tra-cuu-loi-ho-so.blade.php` (thêm nút)
- Test: `tests/Feature/TraCuuLoiHoSoTest.php` (thêm test)

**Interfaces:**
- Consumes: `TreatmentProfileService::cua()`; `App\Jobs\jobKtTheBHYT::__construct(array $params, $checkOldValue = true)` với `$params` gồm `maThe`, `hoTen`, `ngaySinh`, `ma_lk`, `maCskcb`, `maDkbd`, `gioiTinh`
- Produces: route `khth.tra-cuu-loi-ho-so-tra-lai-the` (POST `{treatment_code}`) trả JSON `{message}` (200) hoặc `{message}` (422)

- [ ] **Step 1: Viết test thất bại**

Thêm vào `tests/Feature/TraCuuLoiHoSoTest.php`. Bổ sung `use App\Jobs\jobKtTheBHYT;` và `use Illuminate\Support\Facades\Queue;` ở đầu tệp:

```php
    protected function traLaiThe($ma)
    {
        return $this->actingAs($this->nguoiDung(['tra-cuu-loi-ho-so']))
            ->postJson('/khth/tra-cuu-loi-ho-so/tra-lai-the', ['treatment_code' => $ma]);
    }

    /** @test */
    public function tra_lai_the_dispatch_job_mot_lan_voi_tham_so_dung()
    {
        Queue::fake();
        config(['organization.BHYT_CO_SO' => ['01001' => ['username' => 'u']]]);
        $this->themHoSo();

        $this->traLaiThe(self::MA)->assertStatus(200);

        Queue::assertPushed(jobKtTheBHYT::class, 1);
        Queue::assertPushed(jobKtTheBHYT::class, function ($job) {
            $p = $this->thamSoJob($job);

            // gender_code cua ho so mau la '1' (Nam); cong BHXH dung quy uoc nguoc lai
            // nen phai gui 2. Sai cho nay thi cong tra ve ket qua sai gioi tinh.
            $this->assertSame(2, $p['gioiTinh']);
            $this->assertSame('01001', $p['maCskcb']);     // co so dieu tri, tu his_branch
            $this->assertSame('01005', $p['maDkbd']);      // noi DKBD, tu the benh nhan
            $this->assertSame('DN4010112345678', $p['maThe']);
            $this->assertSame(self::MA, $p['ma_lk']);
            $this->assertFalse($this->coDungKetQuaCu($job));

            return true;
        });
    }

    /** Doc thuoc tinh protected cua job de kiem tham so da dong goi. */
    protected function thamSoJob($job)
    {
        $r = new \ReflectionProperty(get_class($job), 'params');
        $r->setAccessible(true);

        return $r->getValue($job);
    }

    protected function coDungKetQuaCu($job)
    {
        $r = new \ReflectionProperty(get_class($job), 'checkOldValue');
        $r->setAccessible(true);

        return $r->getValue($job);
    }

    /** @test */
    public function tra_lai_the_chan_khi_co_so_khong_nam_trong_cau_hinh()
    {
        Queue::fake();
        config(['organization.BHYT_CO_SO' => ['09999' => ['username' => 'u']]]);
        $this->themHoSo();

        $this->traLaiThe(self::MA)->assertStatus(422);

        Queue::assertNotPushed(jobKtTheBHYT::class);
    }

    /** @test */
    public function tra_lai_the_chan_khi_thieu_ma_the()
    {
        Queue::fake();
        config(['organization.BHYT_CO_SO' => ['01001' => ['username' => 'u']]]);
        $this->themHoSo(['treatment_code' => 'HS-KHONG-THE', 'tdl_hein_card_number' => null]);

        $this->traLaiThe('HS-KHONG-THE')->assertStatus(422);

        Queue::assertNotPushed(jobKtTheBHYT::class);
    }

    /** @test */
    public function tra_lai_the_chan_khi_thieu_gioi_tinh()
    {
        Queue::fake();
        config(['organization.BHYT_CO_SO' => ['01001' => ['username' => 'u']]]);
        $this->themHoSo(['treatment_code' => 'HS-KHONG-GT', 'tdl_patient_gender_id' => null]);

        $this->traLaiThe('HS-KHONG-GT')->assertStatus(422);

        Queue::assertNotPushed(jobKtTheBHYT::class);
    }

    /** @test */
    public function tra_lai_the_chan_khi_khong_co_ho_so()
    {
        Queue::fake();

        $this->traLaiThe('KHONG-CO')->assertStatus(422);

        Queue::assertNotPushed(jobKtTheBHYT::class);
    }
```

- [ ] **Step 2: Chạy test để chắc chắn nó đỏ**

```bash
vendor/bin/phpunit tests/Feature/TraCuuLoiHoSoTest.php --filter tra_lai_the
```

Kỳ vọng: FAIL — 404 vì route chưa có.

- [ ] **Step 3: Thêm route**

Trong nhóm `checkrole:tra-cuu-loi-ho-so`:

```php
        Route::post('tra-cuu-loi-ho-so/tra-lai-the', 'KHTH\TraCuuLoiHoSoController@traLaiThe')
            ->name('khth.tra-cuu-loi-ho-so-tra-lai-the')
            ->middleware('throttle:5,1');
```

- [ ] **Step 4: Thêm phương thức `traLaiThe()`**

Bổ sung `use App\Jobs\jobKtTheBHYT;` ở đầu controller.

```php
    public function traLaiThe(Request $request)
    {
        $ma = trim((string) $request->input('treatment_code'));

        if ($ma === '') {
            return response()->json(['message' => 'Chưa nhập mã điều trị'], 422);
        }

        try {
            $hoSo = $this->hoSo->cua($ma);
        } catch (\Exception $e) {
            Log::error('Tra lai the: loi doc HIS', ['treatment_code' => $ma, 'loi' => $e->getMessage()]);

            return response()->json(['message' => 'Không lấy được thông tin từ HIS'], 422);
        }

        if (!$hoSo) {
            return response()->json(['message' => 'Không tìm thấy hồ sơ với mã này trên HIS'], 422);
        }

        if (trim((string) $hoSo['hein_card_number']) === '') {
            return response()->json(['message' => 'Hồ sơ không có mã thẻ BHYT'], 422);
        }

        // Left join his_gender (xem TreatmentProfileService) nen gioi tinh co the rong.
        // Gui rong len cong chi doi mot loi ro rang lay mot ket qua sai.
        if (trim((string) $hoSo['gender_code']) === '') {
            return response()->json(['message' => 'Hồ sơ thiếu giới tính'], 422);
        }

        $maCskcb = trim((string) $hoSo['ma_cskcb']);
        $dsCoSo = config('organization.BHYT_CO_SO', []);

        if ($maCskcb === '' || !isset($dsCoSo[$maCskcb])) {
            return response()->json(['message' => 'Không xác định được cơ sở của hồ sơ'], 422);
        }

        jobKtTheBHYT::dispatch([
            'maThe'    => $hoSo['hein_card_number'],
            'hoTen'    => $hoSo['patient_name'],
            'ngaySinh' => dob($hoSo['patient_dob']),
            'ma_lk'    => $hoSo['treatment_code'],
            'maCskcb'  => $maCskcb,
            // maDkbd la noi DKBD ghi tren THE, khac maCskcb la co so DIEU TRI. Job dung
            // maCskcb de chon tai khoan cong BHXH va maDkbd de doi chieu ket qua tra ve.
            'maDkbd'   => $hoSo['hein_medi_org_code'],
            'gioiTinh' => $this->gioiTinhCongBhxh($hoSo['gender_code']),
        // checkOldValue = false: de mac dinh true thi job thay ket qua cu con hop le va
        // thoat ngay - dung nghia "bam nut xong khong co gi xay ra".
        ], false)->onQueue('JobKtTheBHYT');

        return response()->json([
            'message' => 'Đã gửi yêu cầu tra lại thẻ, bấm Tra cứu lại sau ít giây để xem kết quả',
        ]);
    }

    /**
     * HIS dung gender_code 1 = Nam, 2 = Nu; cong BHXH dung quy uoc nguoc lai. Lenh quet
     * HISProKiemTraTheBHYT dao o cung cho nay - bo qua thi cong tra ve ket qua sai.
     */
    protected function gioiTinhCongBhxh($genderCode)
    {
        $g = (int) $genderCode;

        if ($g === 1) {
            return 2;
        }

        if ($g === 2) {
            return 1;
        }

        return $g;
    }
```

- [ ] **Step 5: Chạy test để chắc chắn nó xanh**

```bash
vendor/bin/phpunit tests/Feature/TraCuuLoiHoSoTest.php
```

Kỳ vọng: PASS, 13 test.

- [ ] **Step 6: Thêm nút vào giao diện**

Trong `resources/views/khth/tra-cuu-loi-ho-so.blade.php`, cạnh nút In:

```blade
  <p>
    <a id="btn-in" class="btn btn-default" target="_blank"><i class="fa fa-print"></i> In phiếu lỗi</a>
    <button id="btn-tra-lai-the" class="btn btn-default"><i class="fa fa-refresh"></i> Tra lại thẻ BHYT</button>
    <span id="ket-qua-tra-lai-the" style="margin-left:8px"></span>
  </p>
```

Và trong `$(function () { ... })`:

```javascript
  $('#btn-tra-lai-the').on('click', function () {
    if (!maHienTai) { return; }

    var $b = $(this).prop('disabled', true);
    $('#ket-qua-tra-lai-the').text('');

    $.post('{{ route('khth.tra-cuu-loi-ho-so-tra-lai-the') }}', {
      _token: '{{ csrf_token() }}', treatment_code: maHienTai
    }).done(function (r) {
      // Job chay bat dong bo: KHONG tu nap lai roi hien nhu the da co ket qua moi.
      $('#ket-qua-tra-lai-the').css('color', '#00a65a').text(r.message);
    }).fail(function (x) {
      var t = (x.responseJSON && x.responseJSON.message) || 'Không gửi được yêu cầu';
      $('#ket-qua-tra-lai-the').css('color', '#dd4b39').text(t);
    }).always(function () {
      $b.prop('disabled', false);
    });
  });
```

- [ ] **Step 7: Kiểm tra bằng trình duyệt**

Tra một hồ sơ có thẻ BHYT → bấm "Tra lại thẻ BHYT" → hiện dòng xanh báo đã gửi. Kiểm tra hàng đợi có job:

```bash
php artisan queue:work --queue=JobKtTheBHYT --once
```

Bấm nút 6 lần liên tiếp → lần thứ 6 trả 429 (throttle).

- [ ] **Step 8: Chạy toàn bộ test và so với mốc đầu**

```bash
vendor/bin/phpunit
```

Kỳ vọng: không có test đỏ nào ngoài danh sách đỏ sẵn đã ghi ở bước chuẩn bị.

- [ ] **Step 9: Commit**

```bash
git add app/Http/Controllers/KHTH/TraCuuLoiHoSoController.php routes/web.php resources/views/khth/tra-cuu-loi-ho-so.blade.php tests/Feature/TraCuuLoiHoSoTest.php
git commit -m "feat(tra-cuu-loi): tra lai the BHYT cho ho so dang xem"
```

---

## Kiểm tra cuối

- [ ] Đăng nhập bằng tài khoản chỉ có role `tra-cuu-loi-ho-so`: thấy menu "Tra cứu lỗi hồ sơ", vào được, **không** thấy cột "Xử lý", vào `/khth/order-check-index` bị 403.
- [ ] Đăng nhập bằng tài khoản có cả `order-check`: thấy cột "Xử lý", đổi trạng thái một vi phạm rồi tra lại thấy trạng thái mới.
- [ ] Tra một mã không tồn tại: khối hồ sơ báo không tìm thấy, ba khối lỗi vẫn hiển thị (rỗng), không có lỗi 500.
- [ ] `vendor/bin/phpunit` không thêm test đỏ nào so với mốc đầu.
