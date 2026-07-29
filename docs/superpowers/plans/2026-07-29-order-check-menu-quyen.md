# Order-check: menu cấp 1 và quyền riêng — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Đưa order-check thành mục menu cấp 1 đặt trên "Hồ sơ XML", và chuyển quyền truy cập từ role `administrator` sang role mới `order-check`.

**Architecture:** Ba thay đổi độc lập nhau: một migration tạo role và gán cho người đang có `xml-man`; tách 15 route order-check ra một nhóm `Route::group` riêng với `checkrole:order-check` nhưng **giữ nguyên URL và tên route**; chuyển khối menu từ tầng 3 lên tầng 1 trong `config/adminlte.php`. Không sửa middleware hay bộ lọc menu.

**Tech Stack:** Laravel 5.5.50, PHP 7.4, Laratrust 5.0, PHPUnit 6.5, AdminLTE.

## Global Constraints

- Cổng kiểm thử: `vendor/bin/phpunit --testsuite Unit`. **Không** chạy `tests/Feature` — đang đỏ sẵn vì lý do môi trường, không liên quan thay đổi này.
- **Không đổi URL** của bất kỳ route order-check nào. Blade hardcode `url('khth/order-check-ref-index')` và `url('khth/order-check-rule-index')` ở 4 chỗ; đổi prefix là vỡ.
- **Không đổi tên route.** Toàn bộ giữ dạng `khth.order-check-*`.
- **Không sửa** `app/Http/Middleware/CheckRole.php` hay `app/Providers/AppServiceProvider.php`.
- Tên role: `order-check`. Display name và description: `Kiểm tra sai sót y lệnh`.
- Migration phải chạy lại được nhiều lần không vỡ (idempotent).
- Comment và chuỗi trong code PHP viết **không dấu**; chuỗi hiển thị cho người dùng (display_name, text menu) viết **có dấu**, theo đúng lệ hiện có trong `config/adminlte.php`.

---

### Task 1: Tách route sang nhóm quyền riêng

**Files:**
- Modify: `routes/web.php:628-647` (cắt khỏi nhóm administrator) và chèn nhóm mới sau `routes/web.php:649`
- Test: `tests/Unit/RouteOrderCheckTest.php` (tạo mới)

**Interfaces:**
- Consumes: không có gì từ task khác.
- Produces: 15 route tên `khth.order-check-*` nằm trong nhóm middleware `checkrole:order-check`. Task 3 (menu) tham chiếu 3 trong số đó: `khth.order-check-index`, `khth.order-check-ref-index`, `khth.order-check-rule-index`.

- [ ] **Step 1: Viết test đỏ**

Tạo `tests/Unit/RouteOrderCheckTest.php`:

```php
<?php

namespace Tests\Unit;

use Route;
use Tests\TestCase;

class RouteOrderCheckTest extends TestCase
{
    /**
     * Ten route => URI. Chot cung de chan viec vo tinh doi URL.
     *
     * Cac blade hardcode url('khth/order-check-ref-index') va
     * url('khth/order-check-rule-index'), khong chi dung route(). Doi URL la vo.
     */
    protected function banDo()
    {
        return [
            'khth.order-check-index' => 'khth/order-check-index',
            'khth.order-check-summary' => 'khth/order-check-index/summary',
            'khth.order-check-scan-stats' => 'khth/order-check-index/scan-stats',
            'khth.order-check-fetch' => 'khth/order-check-index/fetch',
            'khth.order-check-update-status' => 'khth/order-check-index/update-status',
            'khth.order-check-export' => 'khth/order-check-index/export',
            'khth.order-check-ref-index' => 'khth/order-check-ref-index',
            'khth.order-check-ref-fetch' => 'khth/order-check-ref-index/fetch',
            'khth.order-check-ref-store' => 'khth/order-check-ref-index',
            'khth.order-check-ref-update' => 'khth/order-check-ref-index/{id}',
            'khth.order-check-ref-destroy' => 'khth/order-check-ref-index/{id}',
            'khth.order-check-rule-index' => 'khth/order-check-rule-index',
            'khth.order-check-rule-fetch' => 'khth/order-check-rule-index/fetch',
            'khth.order-check-rule-update' => 'khth/order-check-rule-index/{id}',
            'khth.order-check-rule-toggle' => 'khth/order-check-rule-index/{id}/toggle',
        ];
    }

    /** @test */
    public function du_15_route_va_url_khong_doi()
    {
        foreach ($this->banDo() as $ten => $uri) {
            $r = Route::getRoutes()->getByName($ten);

            $this->assertNotNull($r, "Thieu route $ten");
            $this->assertSame($uri, $r->uri(), "Route $ten bi doi URL");
        }
    }

    /** @test */
    public function moi_route_deu_dung_quyen_order_check()
    {
        foreach (array_keys($this->banDo()) as $ten) {
            $mw = Route::getRoutes()->getByName($ten)->gatherMiddleware();

            $this->assertContains('checkrole:order-check', $mw,
                "Route $ten chua chuyen sang quyen order-check");
            $this->assertNotContains('checkrole:administrator', $mw,
                "Route $ten van con quyen administrator");
        }
    }

    /** @test */
    public function van_nam_trong_nhom_xac_thuc()
    {
        // Nhom ngoai cung cua web.php la ['auth', 'check.first.login']. Neu chen nhom moi
        // ra ngoai nham thi route thanh cong khai - loi bao mat im lang.
        foreach (array_keys($this->banDo()) as $ten) {
            $mw = Route::getRoutes()->getByName($ten)->gatherMiddleware();

            $this->assertContains('auth', $mw, "Route $ten mat xac thuc");
        }
    }
}
```

- [ ] **Step 2: Chạy test, xác nhận nó đỏ**

```bash
vendor/bin/phpunit tests/Unit/RouteOrderCheckTest.php
```

Kỳ vọng: `du_15_route_va_url_khong_doi` và `van_nam_trong_nhom_xac_thuc` **PASS** (route đã tồn tại sẵn), `moi_route_deu_dung_quyen_order_check` **FAIL** với thông báo `Route khth.order-check-index chua chuyen sang quyen order-check`.

- [ ] **Step 3: Cắt 15 route ra khỏi nhóm administrator**

Trong `routes/web.php`, xoá nguyên khối từ dòng bắt đầu bằng `/* Kiểm tra sai sót y lệnh */` (dòng 628) đến dòng `->name('khth.order-check-rule-toggle');` (dòng 647), tức là cả ba cụm comment `/* Kiểm tra sai sót y lệnh */`, `/* Danh muc gioi han DV (gioi tinh/tuoi) */`, `/* Quản lý quy tắc kiểm tra y lệnh */`.

- [ ] **Step 4: Chèn nhóm mới**

Ngay sau dấu đóng `});` của nhóm administrator (dòng 649 cũ) và **trước** comment `// Báo cáo giao ban — quyền riêng (giaoban)`, chèn:

```php
    // Kiem tra sai sot y lenh — quyen rieng (order-check), khong yeu cau administrator.
    // Giu nguyen prefix 'khth/' va ten route: cac blade hardcode
    // url('khth/order-check-ref-index') va url('khth/order-check-rule-index'), doi URL la vo.
    Route::group(['prefix' => 'khth/', 'middleware' => ['checkrole:order-check']], function () {
        /* Danh sach vi pham */
        Route::get('order-check-index', 'KHTH\OrderCheckController@index')->name('khth.order-check-index');
        Route::get('order-check-index/summary', 'KHTH\OrderCheckController@summary')->name('khth.order-check-summary');
        Route::get('order-check-index/scan-stats', 'KHTH\OrderCheckController@scanStats')->name('khth.order-check-scan-stats');
        Route::get('order-check-index/fetch', 'KHTH\OrderCheckController@fetch')->name('khth.order-check-fetch');
        Route::post('order-check-index/update-status', 'KHTH\OrderCheckController@updateStatus')->name('khth.order-check-update-status');
        Route::get('order-check-index/export', 'KHTH\OrderCheckController@export')->name('khth.order-check-export');

        /* Danh muc gioi han DV (gioi tinh/tuoi) */
        Route::get('order-check-ref-index', 'KHTH\OrderCheckRefController@index')->name('khth.order-check-ref-index');
        Route::get('order-check-ref-index/fetch', 'KHTH\OrderCheckRefController@fetch')->name('khth.order-check-ref-fetch');
        Route::post('order-check-ref-index', 'KHTH\OrderCheckRefController@store')->name('khth.order-check-ref-store');
        Route::post('order-check-ref-index/{id}', 'KHTH\OrderCheckRefController@update')->name('khth.order-check-ref-update');
        Route::delete('order-check-ref-index/{id}', 'KHTH\OrderCheckRefController@destroy')->name('khth.order-check-ref-destroy');

        /* Quan ly quy tac kiem tra y lenh */
        Route::get('order-check-rule-index', 'KHTH\OrderCheckRuleController@index')->name('khth.order-check-rule-index');
        Route::get('order-check-rule-index/fetch', 'KHTH\OrderCheckRuleController@fetch')->name('khth.order-check-rule-fetch');
        Route::post('order-check-rule-index/{id}', 'KHTH\OrderCheckRuleController@update')->name('khth.order-check-rule-update');
        Route::post('order-check-rule-index/{id}/toggle', 'KHTH\OrderCheckRuleController@toggle')->name('khth.order-check-rule-toggle');
    });

```

- [ ] **Step 5: Chạy test, xác nhận xanh**

```bash
vendor/bin/phpunit tests/Unit/RouteOrderCheckTest.php
```

Kỳ vọng: PASS cả 3 test.

- [ ] **Step 6: Chạy cả suite Unit**

```bash
vendor/bin/phpunit --testsuite Unit
```

Kỳ vọng: OK, không có test nào đỏ thêm so với trước.

- [ ] **Step 7: Commit**

```bash
git add routes/web.php tests/Unit/RouteOrderCheckTest.php
git commit -m "refactor(order-check): tach route sang nhom quyen order-check"
```

---

### Task 2: Migration tạo role và gán cho người đang có xml-man

**Files:**
- Create: `database/migrations/2026_07_29_090000_them_role_order_check.php`
- Test: kiểm bằng cách chạy migration và truy vấn (mô tả ở Step 3-4). Không viết PHPUnit test cho migration — dự án không có tiền lệ nào test migration, và test đó sẽ đụng dữ liệu thật.

**Interfaces:**
- Consumes: role `xml-man` phải đã tồn tại trong bảng `roles` (được tạo bởi `database/migrations/2024_10_02_114652_add_xml_role_to_roles_table.php`).
- Produces: bản ghi role tên `order-check` trong bảng `roles`, và các bản ghi `role_user` tương ứng.

- [ ] **Step 1: Ghi lại trạng thái trước khi chạy**

```bash
php -r "require 'vendor/autoload.php'; \$a=require 'bootstrap/app.php'; \$a->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap(); echo 'role order-check: '.DB::table('roles')->where('name','order-check')->count().PHP_EOL; \$x=DB::table('roles')->where('name','xml-man')->first(); echo 'so nguoi co xml-man: '.DB::table('role_user')->where('role_id',\$x->id)->count().PHP_EOL;"
```

Kỳ vọng trước khi chạy migration: `role order-check: 0`, `so nguoi co xml-man: 1`.

- [ ] **Step 2: Viết migration**

Tạo `database/migrations/2026_07_29_090000_them_role_order_check.php`:

```php
<?php

use App\Role;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Migrations\Migration;

/**
 * Role rieng cho module kiem tra sai sot y lenh (order-check).
 *
 * Vi sao la ROLE chu khong phai PERMISSION: menu di qua AppServiceProvider::filterMenu,
 * ham do CHI kiem hasRole(), khong co nhanh can(). Cap bang permission thi route cho vao
 * nhung menu van an.
 *
 * Vi sao PHAI gan role: filterMenu cho superadministrator xem toan bo menu khong loc,
 * nhung middleware CheckRole KHONG co ngoai le cho superadministrator. Superadmin thieu
 * role nay se THAY menu nhung bam vao la 403.
 */
class ThemRoleOrderCheck extends Migration
{
    const TEN = 'order-check';

    public function up()
    {
        $role = Role::where('name', self::TEN)->first();

        if (!$role) {
            $role = Role::create([
                'name' => self::TEN,
                'display_name' => 'Kiểm tra sai sót y lệnh',
                'description' => 'Kiểm tra sai sót y lệnh',
            ]);
        }

        $xmlMan = Role::where('name', 'xml-man')->first();

        if (!$xmlMan) {
            return;
        }

        // Gan cho dung nhung nguoi dang co xml-man, giu nguyen user_type cua ban ghi goc.
        foreach (DB::table('role_user')->where('role_id', $xmlMan->id)->get() as $r) {
            $daCo = DB::table('role_user')
                ->where('role_id', $role->id)
                ->where('user_id', $r->user_id)
                ->where('user_type', $r->user_type)
                ->exists();

            if ($daCo) {
                continue;
            }

            DB::table('role_user')->insert([
                'role_id' => $role->id,
                'user_id' => $r->user_id,
                'user_type' => $r->user_type,
            ]);
        }
    }

    public function down()
    {
        $role = Role::where('name', self::TEN)->first();

        if (!$role) {
            return;
        }

        DB::table('role_user')->where('role_id', $role->id)->delete();
        $role->delete();
    }
}
```

- [ ] **Step 3: Chạy migration**

```bash
php artisan migrate
```

Kỳ vọng: `Migrated: 2026_07_29_090000_them_role_order_check`.

- [ ] **Step 4: Kiểm chứng kết quả**

```bash
php -r "require 'vendor/autoload.php'; \$a=require 'bootstrap/app.php'; \$a->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap(); \$r=DB::table('roles')->where('name','order-check')->first(); echo 'role id: '.\$r->id.' | '.\$r->display_name.PHP_EOL; foreach(DB::table('role_user')->where('role_id',\$r->id)->get() as \$x) echo '  user '.\$x->user_id.' ('.\$x->user_type.')'.PHP_EOL;"
```

Kỳ vọng: in ra role vừa tạo và **đúng một** dòng `user 14874 (App\CustomUser)` — khớp với số người đang có `xml-man` đo được ở Step 1.

- [ ] **Step 5: Kiểm tính chạy lại được**

```bash
php artisan migrate:rollback --step=1 && php artisan migrate
```

Kỳ vọng: rollback rồi migrate lại thành công, và chạy lại lệnh kiểm chứng ở Step 4 cho ra **đúng một** dòng user, không nhân đôi.

- [ ] **Step 6: Chạy suite Unit**

```bash
vendor/bin/phpunit --testsuite Unit
```

Kỳ vọng: OK.

- [ ] **Step 7: Commit**

```bash
git add database/migrations/2026_07_29_090000_them_role_order_check.php
git commit -m "feat(order-check): them role order-check va gan cho nguoi dang co xml-man"
```

---

### Task 3: Chuyển menu lên cấp 1

**Files:**
- Modify: `config/adminlte.php:217-244` (xoá khối cũ) và chèn khối mới ngay trước `config/adminlte.php:460` (mục `Hồ sơ XML`)
- Test: `tests/Unit/MenuOrderCheckTest.php` (tạo mới)

**Interfaces:**
- Consumes: tên route `khth.order-check-index`, `khth.order-check-ref-index`, `khth.order-check-rule-index` từ Task 1; tên role `order-check` từ Task 2.
- Produces: không có gì cho task sau.

- [ ] **Step 1: Viết test đỏ**

Tạo `tests/Unit/MenuOrderCheckTest.php`:

```php
<?php

namespace Tests\Unit;

use Tests\TestCase;

class MenuOrderCheckTest extends TestCase
{
    const TEN_MUC = 'Kiểm tra sai sót y lệnh';
    const TEN_XML = 'Hồ sơ XML';

    /** Menu goc doc thang tu config, chua qua bo loc quyen */
    protected function menu()
    {
        return config('adminlte.menu');
    }

    /** Chi so cua mot muc CAP 1 theo text; -1 neu khong co */
    protected function viTriCap1($text)
    {
        foreach (array_values($this->menu()) as $i => $item) {
            if (is_array($item) && isset($item['text']) && $item['text'] === $text) {
                return $i;
            }
        }

        return -1;
    }

    /** @test */
    public function la_muc_cap_1_va_dung_tren_ho_so_xml()
    {
        $viTri = $this->viTriCap1(self::TEN_MUC);
        $viTriXml = $this->viTriCap1(self::TEN_XML);

        $this->assertNotSame(-1, $viTri, 'Khong tim thay muc cap 1 "' . self::TEN_MUC . '"');
        $this->assertNotSame(-1, $viTriXml, 'Khong tim thay muc cap 1 "' . self::TEN_XML . '"');
        $this->assertLessThan($viTriXml, $viTri, 'Muc order-check phai nam TREN "' . self::TEN_XML . '"');
    }

    /** @test */
    public function chi_xuat_hien_dung_mot_lan_o_cap_1()
    {
        $dem = 0;

        foreach ($this->menu() as $item) {
            if (is_array($item) && isset($item['text']) && $item['text'] === self::TEN_MUC) {
                $dem++;
            }
        }

        $this->assertSame(1, $dem, 'Muc "' . self::TEN_MUC . '" phai xuat hien dung mot lan o cap 1');
    }

    /** @test */
    public function ca_muc_cha_va_3_muc_con_deu_dung_quyen_order_check()
    {
        $muc = null;

        foreach ($this->menu() as $item) {
            if (is_array($item) && isset($item['text']) && $item['text'] === self::TEN_MUC) {
                $muc = $item;
                break;
            }
        }

        $this->assertNotNull($muc);
        $this->assertSame('order-check', $muc['checkrole']);
        $this->assertCount(3, $muc['submenu']);

        foreach ($muc['submenu'] as $con) {
            $this->assertSame('order-check', $con['checkrole'],
                'Muc con "' . $con['text'] . '" chua chuyen sang quyen order-check');
        }

        $this->assertSame(
            ['khth.order-check-index', 'khth.order-check-ref-index', 'khth.order-check-rule-index'],
            array_column($muc['submenu'], 'route')
        );
    }

    /** @test */
    public function khong_con_dau_vet_trong_ke_hoach_tong_hop()
    {
        $khth = null;

        foreach ($this->menu() as $item) {
            if (is_array($item) && isset($item['text']) && $item['text'] === 'Kế hoạch tổng hợp') {
                $khth = $item;
                break;
            }
        }

        $this->assertNotNull($khth, 'Khong tim thay muc "Ke hoach tong hop"');
        $this->assertSame([], $this->routeOrderCheck($khth),
            'Van con muc order-check nam trong "Ke hoach tong hop"');
    }

    /** Gom moi 'route' bat dau bang khth.order-check trong mot nhanh menu */
    protected function routeOrderCheck($item)
    {
        $ra = [];

        if (isset($item['route']) && strpos($item['route'], 'khth.order-check') === 0) {
            $ra[] = $item['route'];
        }

        if (isset($item['submenu']) && is_array($item['submenu'])) {
            foreach ($item['submenu'] as $con) {
                $ra = array_merge($ra, $this->routeOrderCheck($con));
            }
        }

        return $ra;
    }
}
```

- [ ] **Step 2: Chạy test, xác nhận nó đỏ**

```bash
vendor/bin/phpunit tests/Unit/MenuOrderCheckTest.php
```

Kỳ vọng: 4 test, **cả 4 FAIL**. `la_muc_cap_1_va_dung_tren_ho_so_xml` báo `Khong tim thay muc cap 1 "Kiểm tra sai sót y lệnh"`; `khong_con_dau_vet_trong_ke_hoach_tong_hop` báo còn 3 route trong "Kế hoạch tổng hợp".

- [ ] **Step 3: Xoá khối cũ khỏi "Kế hoạch tổng hợp"**

Trong `config/adminlte.php`, xoá nguyên phần tử mảng từ dòng 217 (`[` mở đầu, ngay sau khối `Kiểm soát nghiệp vụ`) đến dòng 244 (`],` đóng) — tức là toàn bộ phần tử có `'text' => 'Kiểm tra sai sót y lệnh'` cùng 3 mục con của nó.

- [ ] **Step 4: Chèn mục cấp 1 mới**

Ngay **trước** phần tử `[ 'text' => 'Hồ sơ XML', ... ]` (dòng 460 cũ), chèn:

```php
        [
            'text'      => 'Kiểm tra sai sót y lệnh',
            'icon'      => 'stethoscope',
            'checkrole' => 'order-check',
            'submenu'   => [
                [
                    'text'      => 'Danh sách vi phạm',
                    'icon'      => 'list',
                    'checkrole' => 'order-check',
                    'route'     => 'khth.order-check-index',
                    'active'    => ['khth/order-check-index*'],
                ],
                [
                    'text'      => 'Danh mục giới hạn DV',
                    'icon'      => 'venus-mars',
                    'checkrole' => 'order-check',
                    'route'     => 'khth.order-check-ref-index',
                    'active'    => ['khth/order-check-ref-index*'],
                ],
                [
                    'text'      => 'Quản lý quy tắc kiểm tra',
                    'icon'      => 'sliders',
                    'checkrole' => 'order-check',
                    'route'     => 'khth.order-check-rule-index',
                    'active'    => ['khth/order-check-rule-index*'],
                ],
            ],
        ],
```

- [ ] **Step 5: Kiểm cú pháp PHP**

```bash
php -l config/adminlte.php
```

Kỳ vọng: `No syntax errors detected`.

- [ ] **Step 6: Chạy test, xác nhận xanh**

```bash
vendor/bin/phpunit tests/Unit/MenuOrderCheckTest.php
```

Kỳ vọng: PASS cả 4 test.

- [ ] **Step 7: Chạy cả suite Unit**

```bash
vendor/bin/phpunit --testsuite Unit
```

Kỳ vọng: OK.

- [ ] **Step 8: Xoá cache cấu hình**

```bash
php artisan config:clear
```

Cần thiết vì `config/adminlte.php` có thể đang bị cache; không xoá thì menu trên trình duyệt vẫn là bản cũ.

- [ ] **Step 9: Commit**

```bash
git add config/adminlte.php tests/Unit/MenuOrderCheckTest.php
git commit -m "feat(order-check): chuyen menu len cap 1, dat tren Ho so XML"
```

---

### Task 4: Cập nhật readme

**Files:**
- Modify: `docs/tai-lieu-tong-hop-xml3176-order-check.md:49`

**Interfaces:**
- Consumes: kết quả của Task 1, 2, 3.
- Produces: không có gì.

- [ ] **Step 1: Sửa dòng phân quyền trong bảng so sánh**

Trong `docs/tai-lieu-tong-hop-xml3176-order-check.md`, dòng 49 hiện là:

```markdown
| Phân quyền | `checkrole:xml-man` | `checkrole:administrator` |
```

Đổi thành:

```markdown
| Phân quyền | `checkrole:xml-man` | `checkrole:order-check` |
```

- [ ] **Step 2: Thêm ghi chú về menu và triển khai**

Ngay sau bảng so sánh (sau dòng `| Độ nhạy cảm con người | ... |`), chèn một đoạn mới:

```markdown
> **Menu và quyền của Order-Check** (từ 29/07/2026): mục `Kiểm tra sai sót y lệnh` là
> mục **cấp 1**, đặt ngay **trên** `Hồ sơ XML` — trước đây nằm trong `Kế hoạch tổng hợp`.
> Quyền là role riêng `order-check`, không còn dùng `administrator`.
>
> Khi triển khai **bắt buộc chạy `php artisan migrate`** để tạo role. Không chạy thì
> **không ai vào được**, kể cả superadmin: `AppServiceProvider::filterMenu` cho
> superadministrator xem toàn bộ menu không lọc, nhưng middleware `CheckRole` **không có**
> ngoại lệ cho superadministrator — kết quả là thấy menu nhưng bấm vào bị 403.
```

- [ ] **Step 3: Commit**

```bash
git add docs/tai-lieu-tong-hop-xml3176-order-check.md
git commit -m "docs(order-check): ghi lai quyen order-check va vi tri menu moi"
```

---

## Nghiệm thu cuối

- [ ] Chạy `vendor/bin/phpunit --testsuite Unit` — OK, không đỏ.
- [ ] Đăng nhập bằng tài khoản user_id 14874, xác nhận menu `Kiểm tra sai sót y lệnh` hiện ở **cấp 1**, nằm **trên** `Hồ sơ XML`, và bấm được vào cả 3 mục con không bị 403.
- [ ] Xác nhận menu cũ trong `Kế hoạch tổng hợp` đã biến mất.

## Lưu ý khi triển khai lên máy chủ

1. `php artisan migrate` — bắt buộc, nếu không thì role chưa tồn tại và không ai vào được.
2. `php artisan config:clear` — nếu không, menu vẫn là bản cũ đang bị cache.
3. `php artisan route:clear` — chỉ cần khi máy chủ có cache route (`bootstrap/cache/routes.php`).
   Máy phát triển hiện **không** có tệp này nên bước 3 là không cần ở local, nhưng bỏ qua
   trên máy chủ có cache thì middleware quyền cũ vẫn còn hiệu lực.
