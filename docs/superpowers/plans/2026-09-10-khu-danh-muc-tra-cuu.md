# Khu "Danh mục tra cứu" — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Dựng khu vực màn hình tra cứu danh mục tách khỏi DM BHYT, lái bằng cấu hình — thêm danh mục về sau chỉ là thêm một mục trong sổ đăng ký.

**Architecture:** Một sổ đăng ký trong config làm nguồn sự thật duy nhất; một controller hai hàm (`index`/`fetch`) và một view Blade generic dựng cột từ sổ; menu sinh tự động bằng cách `require` thẳng sổ trong `config/adminlte.php`.

**Tech Stack:** Laravel 5.5, PHP 7.4, PHPUnit 6.5, Yajra Datatables, AdminLTE.

**Spec:** `docs/superpowers/specs/2026-09-10-khu-danh-muc-tra-cuu-design.md`

## Global Constraints

- PHP 7.4 / Laravel 5.5. PHPUnit 6.5: `setUp()` KHÔNG có `:void`; dùng `assertSame`/`assertTrue`/`assertCount`.
- **CẤM `RefreshDatabase`** và mọi thao tác xoá/migrate CSDL. Plan này KHÔNG tạo bảng, KHÔNG ghi CSDL — chỉ đọc.
- **CẤM đặt route vào prefix `category/`** — ở đó có route bắt-tất `{category}` (`routes/web.php:371`) sẽ nuốt route một đoạn. Dùng prefix mới `danh-muc-tra-cuu/`.
- **CẤM dùng `config('danh_muc_tra_cuu')` bên trong `config/adminlte.php`** — Laravel nạp config theo thứ tự chữ cái, `adminlte` chạy TRƯỚC nên khoá đó chưa tồn tại và trả `null`, menu sẽ RỖNG mà không báo lỗi. Phải `require __DIR__ . '/danh_muc_tra_cuu.php'`.
- Quyền: `checkrole:category-manager`.
- **Chỉ xem, không sửa** — không thêm route/nút POST, PUT, DELETE nào.
- Test controller phải dùng user giả override `hasRole()`/`can()` (khuôn `tests/Feature/Dashboard/Xml3176DashboardControllerTest.php`), KHÔNG dùng `factory(User::class)` vì `CheckRole` sẽ trả 403.
- Chạy test theo tệp/thư mục cụ thể, KHÔNG chạy toàn bộ `phpunit`.

---

## File Structure

- Create: `config/danh_muc_tra_cuu.php` — sổ đăng ký
- Create: `tests/Unit/DanhMucTraCuuSoDangKyTest.php`
- Create: `app/Http/Controllers/Category/DanhMucTraCuuController.php`
- Create: `resources/views/category/danh-muc-tra-cuu/index.blade.php`
- Modify: `routes/web.php` — thêm nhóm route mới
- Create: `tests/Feature/DanhMucTraCuuControllerTest.php`
- Modify: `config/adminlte.php` — require sổ + sinh submenu
- Create: `tests/Unit/DanhMucTraCuuMenuTest.php`

---

### Task 1: Sổ đăng ký danh mục

**Files:**
- Create: `config/danh_muc_tra_cuu.php`
- Test: `tests/Unit/DanhMucTraCuuSoDangKyTest.php`

**Interfaces:**
- Produces: `config('danh_muc_tra_cuu')` — mảng `slug => ['ten', 'model', 'cot', 'cot_tim', 'sap_xep']`. Task 2 và Task 3 đều đọc sổ này.

- [ ] **Step 1: Viết test thất bại**

Tạo `tests/Unit/DanhMucTraCuuSoDangKyTest.php`:

```php
<?php

namespace Tests\Unit;

use Illuminate\Database\Eloquent\Model;
use Tests\TestCase;

class DanhMucTraCuuSoDangKyTest extends TestCase
{
    private function so()
    {
        return config('danh_muc_tra_cuu');
    }

    /** @test */
    public function so_dang_ky_ton_tai_va_khong_rong()
    {
        $so = $this->so();

        $this->assertInternalType('array', $so);
        $this->assertNotEmpty($so, 'So dang ky danh muc tra cuu dang rong');
    }

    /** @test */
    public function moi_muc_khai_du_bon_khoa_bat_buoc()
    {
        foreach ($this->so() as $khoa => $dm) {
            foreach (['ten', 'model', 'cot', 'cot_tim'] as $bb) {
                $this->assertArrayHasKey($bb, $dm, "Muc '$khoa' thieu khoa '$bb'");
            }
            $this->assertNotEmpty($dm['cot'], "Muc '$khoa' khong khai cot nao");
        }
    }

    /** @test */
    public function model_khai_phai_ton_tai_va_la_eloquent()
    {
        foreach ($this->so() as $khoa => $dm) {
            $this->assertTrue(class_exists($dm['model']), "Muc '$khoa': khong co lop {$dm['model']}");
            $this->assertTrue(is_subclass_of($dm['model'], Model::class),
                "Muc '$khoa': {$dm['model']} khong phai Eloquent Model");
        }
    }

    /** @test */
    public function cot_tim_phai_nam_trong_danh_sach_cot()
    {
        foreach ($this->so() as $khoa => $dm) {
            foreach ($dm['cot_tim'] as $c) {
                $this->assertArrayHasKey($c, $dm['cot'],
                    "Muc '$khoa': cot tim '$c' khong co trong danh sach cot hien thi");
            }
        }
    }

    /** @test */
    public function co_danh_muc_dvkt_can_ma_may()
    {
        $so = $this->so();

        $this->assertArrayHasKey('dvkt_can_ma_may', $so);
        $this->assertSame(\App\Models\BHYT\DvktCanMaMay::class, $so['dvkt_can_ma_may']['model']);
    }
}
```

- [ ] **Step 2: Chạy test để xác nhận thất bại**

Run: `./vendor/bin/phpunit tests/Unit/DanhMucTraCuuSoDangKyTest.php`
Expected: FAIL — `config('danh_muc_tra_cuu')` trả `null` nên `assertInternalType('array', ...)` đỏ.

- [ ] **Step 3: Tạo sổ đăng ký**

Tạo `config/danh_muc_tra_cuu.php`:

```php
<?php

/**
 * So dang ky cac danh muc xem duoc o khu "Danh muc tra cuu".
 *
 * Day la NGUON SU THAT DUY NHAT cho ca man hinh lan menu: them mot muc vao day thi man
 * hinh, route va muc menu deu tu co, khong phai sua controller/view/route.
 *
 * Moi muc khai:
 *   ten     - ten hien thi tren menu va tieu de man hinh
 *   model   - lop Eloquent de truy van
 *   cot     - ten cot trong bang => nhan hien thi. CHI nhung cot khai o day duoc doc len,
 *             nen danh muc co cot nhay cam se khong bi lo vi nguoi khai quen giau.
 *   cot_tim - cac cot cho o tim kiem (phai nam trong 'cot')
 *   sap_xep - [ten cot, 'asc'|'desc']
 *
 * Khoa mang chinh la SLUG tren URL: /danh-muc-tra-cuu/<khoa>
 */
return [
    'dvkt_can_ma_may' => [
        'ten'     => 'DVKT cần mã máy',
        'model'   => App\Models\BHYT\DvktCanMaMay::class,
        'cot'     => [
            'ma_dvkt'   => 'Mã DVKT',
            'ten_dvkt'  => 'Tên DVKT',
            'is_active' => 'Đang dùng',
        ],
        'cot_tim' => ['ma_dvkt', 'ten_dvkt'],
        'sap_xep' => ['ma_dvkt', 'asc'],
    ],
];
```

- [ ] **Step 4: Chạy test để xác nhận đạt**

Run: `./vendor/bin/phpunit tests/Unit/DanhMucTraCuuSoDangKyTest.php`
Expected: PASS (5 test).

- [ ] **Step 5: Commit**

```bash
git add config/danh_muc_tra_cuu.php tests/Unit/DanhMucTraCuuSoDangKyTest.php
git commit -m "feat(danh-muc): so dang ky danh muc tra cuu"
```

---

### Task 2: Controller, view và route

**Files:**
- Create: `app/Http/Controllers/Category/DanhMucTraCuuController.php`
- Create: `resources/views/category/danh-muc-tra-cuu/index.blade.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/DanhMucTraCuuControllerTest.php`

**Interfaces:**
- Consumes: `config('danh_muc_tra_cuu')` (Task 1).
- Produces: route `danh-muc-tra-cuu.index` (`GET danh-muc-tra-cuu/{khoa}`) và `danh-muc-tra-cuu.fetch` (`GET danh-muc-tra-cuu/{khoa}/du-lieu`). Task 3 dựng URL menu theo đúng slug này.

- [ ] **Step 1: Viết test thất bại**

Tạo `tests/Feature/DanhMucTraCuuControllerTest.php`:

```php
<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * User gia thoa CheckRole middleware ma khong truy van bang roles trong DB.
 * (Theo pattern cua tests/Feature/Dashboard/Xml3176DashboardControllerTest.php)
 */
class FakeCategoryManagerUser extends \App\User
{
    public function hasRole($role, $team = null, $requireAll = false) { return true; }
    public function can($permission, $team = null, $requireAll = false) { return true; }
}

class DanhMucTraCuuControllerTest extends TestCase
{
    private function nguoiDung()
    {
        $u = new FakeCategoryManagerUser();
        $u->id = 1;
        return $u;
    }

    /** @test */
    public function man_hinh_mo_duoc_voi_khoa_hop_le()
    {
        $this->actingAs($this->nguoiDung())
             ->get('/danh-muc-tra-cuu/dvkt_can_ma_may')
             ->assertStatus(200)
             ->assertSee('DVKT cần mã máy');
    }

    /** @test */
    public function khoa_la_tra_404_o_man_hinh()
    {
        $this->actingAs($this->nguoiDung())
             ->get('/danh-muc-tra-cuu/khong-co-that')
             ->assertStatus(404);
    }

    /** @test */
    public function khoa_la_tra_404_o_duong_du_lieu()
    {
        $this->actingAs($this->nguoiDung())
             ->get('/danh-muc-tra-cuu/khong-co-that/du-lieu')
             ->assertStatus(404);
    }

    /** @test */
    public function duong_du_lieu_tra_json_dung_cau_truc()
    {
        $this->actingAs($this->nguoiDung())
             ->getJson('/danh-muc-tra-cuu/dvkt_can_ma_may/du-lieu')
             ->assertStatus(200)
             ->assertJsonStructure(['data']);
    }
}
```

- [ ] **Step 2: Chạy test để xác nhận thất bại**

Run: `./vendor/bin/phpunit tests/Feature/DanhMucTraCuuControllerTest.php`
Expected: FAIL — route chưa tồn tại nên trả 404 ở cả bốn test (test `man_hinh_mo_duoc` và `duong_du_lieu` đỏ vì mong 200).

- [ ] **Step 3: Tạo controller**

Tạo `app/Http/Controllers/Category/DanhMucTraCuuController.php`:

```php
<?php

namespace App\Http\Controllers\Category;

use App\Http\Controllers\Controller;
use Yajra\Datatables\Datatables;

/**
 * Man hinh tra cuu danh muc, LAI BANG CAU HINH.
 *
 * Toan bo khu vuc chi co MOT controller va MOT view: them danh muc moi la them mot muc
 * trong config/danh_muc_tra_cuu.php, khong phai them ham/view/route.
 *
 * CHI XEM. Cac danh muc o day nap tu tep theo kieu thay tron bo, nen sua tay tren man
 * hinh se bi lan nap sau xoa sach - vi vay khong co duong ghi nao o day.
 */
class DanhMucTraCuuController extends Controller
{
    /** Cau hinh cua mot danh muc; khoa la thi 404. */
    private function cauHinh($khoa)
    {
        $dm = config('danh_muc_tra_cuu.' . $khoa);

        if (empty($dm)) {
            abort(404, 'Không có danh mục: ' . $khoa);
        }

        return $dm;
    }

    public function index($khoa)
    {
        $dm = $this->cauHinh($khoa);

        return view('category.danh-muc-tra-cuu.index', [
            'khoa' => $khoa,
            'dm'   => $dm,
        ]);
    }

    public function fetch($khoa)
    {
        $dm = $this->cauHinh($khoa);

        // CHI select cac cot da khai (cong id de DataTables co khoa on dinh): danh muc ve
        // sau co cot nhay cam se khong bi lo chi vi nguoi khai quen giau.
        $cot = array_keys($dm['cot']);
        $model = $dm['model'];
        $query = $model::query()->select(array_merge(['id'], $cot));

        if (!empty($dm['sap_xep'])) {
            $query->orderBy($dm['sap_xep'][0], $dm['sap_xep'][1]);
        }

        return Datatables::of($query)->make(true);
    }
}
```

- [ ] **Step 4: Tạo view generic**

Tạo `resources/views/category/danh-muc-tra-cuu/index.blade.php`:

```blade
@extends('adminlte::page')

@section('title', $dm['ten'])

@section('content_header')
  <h1>
    Danh mục tra cứu
    <small>{{ $dm['ten'] }}</small>
  </h1>
@stop

@section('content')
@include('includes.message')

<div class="panel panel-default">
    <div class="panel-body table-responsive">
        <table id="bang-danh-muc" class="table display table-hover responsive nowrap datatable dtr-inline" width="100%">
            <thead>
                <tr>
                    @foreach ($dm['cot'] as $nhan)
                        <th>{{ $nhan }}</th>
                    @endforeach
                </tr>
            </thead>
        </table>
    </div>
</div>
@stop

@push('after-scripts')
<script type="text/javascript">
    $(document).ready(function () {
        $('#bang-danh-muc').DataTable({
            "processing": true,
            "serverSide": true,
            "destroy": true,
            "responsive": true,
            "scrollX": true,
            "ajax": { url: "{{ route('danh-muc-tra-cuu.fetch', ['khoa' => $khoa]) }}" },
            "columns": [
                @foreach (array_keys($dm['cot']) as $ten)
                    @if ($ten === 'is_active')
                        { "data": "{{ $ten }}", "render": function (d) { return Number(d) === 1 ? 'Đang dùng' : 'Ngừng'; } },
                    @else
                        { "data": "{{ $ten }}" },
                    @endif
                @endforeach
            ],
        });
    });
</script>
@endpush
```

- [ ] **Step 5: Thêm nhóm route**

Trong `routes/web.php`, tìm nhóm route của khu `danh-muc/` (kết thúc bằng dòng route `dm-khoa-phong` rồi `});`):

```php
        Route::get('dm-khoa-phong','Category\Manager\CategoryController@dmKhoaphong')->name('danh-muc.dm-khoa-phong');       
    });
```

Thêm NGAY SAU đoạn trên:

```php

    /*
        Danh muc tra cuu - man hinh lai bang cau hinh (config/danh_muc_tra_cuu.php).
        CO Y khong dat vao prefix 'category/': o do co route bat-tat {category} se nuot
        moi route mot doan. Cung khong dung prefix 'danh-muc/' vi khu do quyen superadmin.
    */
    Route::group(['prefix' => 'danh-muc-tra-cuu/', 'middleware' => ['checkrole:category-manager']], function () {
        Route::get('{khoa}', 'Category\DanhMucTraCuuController@index')->name('danh-muc-tra-cuu.index');
        Route::get('{khoa}/du-lieu', 'Category\DanhMucTraCuuController@fetch')->name('danh-muc-tra-cuu.fetch');
    });
```

- [ ] **Step 6: Chạy test để xác nhận đạt**

Run: `./vendor/bin/phpunit tests/Feature/DanhMucTraCuuControllerTest.php`
Expected: PASS (4 test).

- [ ] **Step 7: Commit**

```bash
git add app/Http/Controllers/Category/DanhMucTraCuuController.php resources/views/category/danh-muc-tra-cuu/index.blade.php routes/web.php tests/Feature/DanhMucTraCuuControllerTest.php
git commit -m "feat(danh-muc): man hinh tra cuu danh muc lai bang cau hinh"
```

---

### Task 3: Menu sinh tự động từ sổ đăng ký

**Files:**
- Modify: `config/adminlte.php`
- Test: `tests/Unit/DanhMucTraCuuMenuTest.php`

**Interfaces:**
- Consumes: sổ đăng ký (Task 1); slug URL `danh-muc-tra-cuu/<khoa>` (Task 2).

- [ ] **Step 1: Viết test thất bại**

Tạo `tests/Unit/DanhMucTraCuuMenuTest.php`:

```php
<?php

namespace Tests\Unit;

use Tests\TestCase;

/**
 * Luoi an toan cho BAY THU TU NAP CONFIG.
 *
 * Laravel nap cac tep config theo thu tu chu cai, 'adminlte' chay TRUOC
 * 'danh_muc_tra_cuu'. Neu ai do doi 'require' thanh config('danh_muc_tra_cuu') trong
 * config/adminlte.php thi khoa do chua ton tai, tra ve null, va submenu thanh RONG -
 * khong loi, khong canh bao. Test nay do len khi dieu do xay ra.
 */
class DanhMucTraCuuMenuTest extends TestCase
{
    /** Tim de quy mot muc menu theo 'text'. */
    private function timMuc(array $menu, $text)
    {
        foreach ($menu as $muc) {
            if (is_array($muc) && isset($muc['text']) && $muc['text'] === $text) {
                return $muc;
            }

            if (is_array($muc) && !empty($muc['submenu'])) {
                $tim = $this->timMuc($muc['submenu'], $text);
                if ($tim !== null) {
                    return $tim;
                }
            }
        }

        return null;
    }

    /** @test */
    public function co_submenu_danh_muc_tra_cuu()
    {
        $muc = $this->timMuc(config('adminlte.menu'), 'Danh mục tra cứu');

        $this->assertNotNull($muc, 'Khong tim thay submenu "Danh muc tra cuu" trong adminlte.menu');
        $this->assertArrayHasKey('submenu', $muc);
    }

    /** @test */
    public function submenu_sinh_du_tu_so_dang_ky()
    {
        $so = config('danh_muc_tra_cuu');
        $muc = $this->timMuc(config('adminlte.menu'), 'Danh mục tra cứu');

        $this->assertCount(count($so), $muc['submenu'],
            'So muc submenu khong khop so dang ky - kiem tra lai cach doc so trong adminlte.php');
    }

    /** @test */
    public function moi_muc_submenu_tro_dung_slug()
    {
        $so = config('danh_muc_tra_cuu');
        $muc = $this->timMuc(config('adminlte.menu'), 'Danh mục tra cứu');

        $url = array_column($muc['submenu'], 'url');

        foreach ($so as $khoa => $dm) {
            $this->assertContains('danh-muc-tra-cuu/' . $khoa, $url,
                "Thieu muc menu cho danh muc '$khoa'");
        }
    }
}
```

- [ ] **Step 2: Chạy test để xác nhận thất bại**

Run: `./vendor/bin/phpunit tests/Unit/DanhMucTraCuuMenuTest.php`
Expected: FAIL — chưa có submenu nên `assertNotNull` đỏ.

- [ ] **Step 3: Đọc sổ đăng ký trong `config/adminlte.php`**

Trong `config/adminlte.php`, tìm dòng khai `$logoImg` (gần đầu tệp):

```php
$logoImg = '<img src="/images/logo.png" alt="GĐBHYT" style="height: 50px;">';
```

Thêm NGAY SAU dòng đó:

```php

// Menu khu "Danh muc tra cuu" sinh tu so dang ky, de them danh muc moi chi phai sua MOT
// cho (config/danh_muc_tra_cuu.php).
//
// PHAI dung require chu KHONG duoc dung config('danh_muc_tra_cuu'): Laravel nap cac tep
// config theo thu tu chu cai, 'adminlte' chay TRUOC nen khoa do chua ton tai va se tra ve
// null - submenu se RONG ma khong ai phat hien. Cung ly do da ghi cho 'organization' o tren.
$danhMucTraCuu = require __DIR__ . '/danh_muc_tra_cuu.php';

$menuDanhMucTraCuu = [];
foreach ($danhMucTraCuu as $khoaDanhMuc => $dmTraCuu) {
    $menuDanhMucTraCuu[] = [
        'text'   => $dmTraCuu['ten'],
        'icon'   => 'book',
        'url'    => 'danh-muc-tra-cuu/' . $khoaDanhMuc,
        'active' => ['danh-muc-tra-cuu/' . $khoaDanhMuc . '*'],
    ];
}
```

- [ ] **Step 4: Chèn submenu vào menu**

Trong cùng tệp, tìm đoạn kết thúc của submenu "BHYT" (mục cuối là DM lỗi Xml 3176):

```php
                            'text'  => 'DM lỗi Xml 3176',
                            'icon'  => 'book',
                            'route'   => 'category-bhyt.xml3176-error-catalog',
                            'active'=> ['category/bhyt/xml3176-error-catalog*'],
                        ],
                    ],
                ],
```

Thêm NGAY SAU đoạn trên:

```php
                [
                    'text'    => 'Danh mục tra cứu',
                    'icon'    => 'book',
                    'submenu' => $menuDanhMucTraCuu,
                ],
```

- [ ] **Step 5: Chạy test để xác nhận đạt**

Run: `./vendor/bin/phpunit tests/Unit/DanhMucTraCuuMenuTest.php`
Expected: PASS (3 test).

- [ ] **Step 6: Chạy hồi quy các bộ test danh mục**

Run: `./vendor/bin/phpunit tests/Unit/Import`
Expected: PASS toàn bộ (63 test).

Run: `./vendor/bin/phpunit tests/Unit/CatalogTemplateSelfDetectTest.php`
Expected: PASS.

- [ ] **Step 7: Commit**

```bash
git add config/adminlte.php tests/Unit/DanhMucTraCuuMenuTest.php
git commit -m "feat(danh-muc): menu khu tra cuu sinh tu dong tu so dang ky"
```

---

## Self-Review

**1. Spec coverage:**
- §4.1 sổ đăng ký → Task 1.
- §4.2 controller hai hàm, 404 khoá lạ, chỉ select cột đã khai → Task 2 Step 3 + test Step 1.
- §4.3 view generic dựng cột từ sổ, `is_active` hiển thị dạng chữ, không kèm hộp thoại chi tiết → Task 2 Step 4.
- §4.4 route prefix mới + quyền `category-manager` → Task 2 Step 5.
- §4.5 menu sinh tự động + né bẫy thứ tự nạp bằng `require` → Task 3 Step 3-4.
- §5 "thêm danh mục = một mục config" → được bảo chứng bởi test menu (Task 3) và test sổ đăng ký (Task 1).
- §6 guard: khoá lạ 404 (Task 2 test), chỉ select cột đã khai (Task 2 Step 3), quyền (Task 2 Step 5). Guard "sổ rỗng → submenu rỗng" không cần mã riêng: vòng lặp chạy 0 lần.
- §7 kiểm thử: sổ đăng ký → Task 1; menu → Task 3; controller → Task 2; hồi quy → Task 3 Step 6.

**2. Placeholder scan:** không có TBD/TODO; mọi bước có mã hoặc lệnh cụ thể kèm kết quả mong đợi.

**3. Type consistency:** khoá sổ `dvkt_can_ma_may` dùng thống nhất ở Task 1 (config), Task 2 (test URL) và Task 3 (test menu). Tên route `danh-muc-tra-cuu.fetch` khai ở Task 2 Step 5 và gọi ở view Task 2 Step 4. Biến view `$khoa`/`$dm` truyền ở controller Step 3 và dùng ở view Step 4. Biến `$menuDanhMucTraCuu` tạo ở Task 3 Step 3 và dùng ở Step 4. Tên lớp model `App\Models\BHYT\DvktCanMaMay` khớp lớp đã tạo ở đợt trước.
