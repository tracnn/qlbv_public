# Dashboard độ phủ danh mục TT12 — Kế hoạch triển khai

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Một màn hình trả lời "sáu mẫu danh mục của từng cơ sở, cái nào đã được cổng BHXH tiếp nhận, cái nào chưa bao giờ gửi".

**Architecture:** Một controller mỏng gọi một service; service **không tự viết SQL đếm trạng thái** mà gọi lại `Tt12DanhSach::truyVan()`. View nạp một khối JSON duy nhất bằng AJAX rồi vẽ lưới 6 mẫu × N cơ sở cộng một dải hồ sơ đang dở dang.

**Tech Stack:** PHP 7.4, Laravel 5.5, PHPUnit 6.5.14, Blade + jQuery + AdminLTE, SweetAlert2.

**Spec:** `docs/superpowers/specs/2026-08-26-tt12-dashboard-do-phu-design.md`

## Global Constraints

- **PHP 7.4 / Laravel 5.5 / PHPUnit 6.5.14.** Dùng `assertContains` cho chuỗi (`assertStringContainsString` chỉ có từ PHPUnit 7.5). `setUp()` của Unit test **không** khai `: void`.
- **KHÔNG khai kiểu trả về** trên phương thức công khai của service TT12 mới. Mockery 0.9.11 vỡ khi reflect method có return type trên PHP 7.4.
- **KHÔNG dùng `RefreshDatabase` / `DatabaseMigrations`.** Test chạm CSDL dùng trait `Tests\Support\DungBangTt12Sqlite`.
- **KHÔNG nhận service qua tham số có type-hint của `handle()`/controller action.** Container Laravel 5.5 tiêm kể cả khi tham số khai `= null`. (Controller constructor injection thì an toàn — `CtdtDashboardController` đang dùng.)
- Chú thích trong mã: tiếng Việt **không dấu**. Chuỗi hiển thị cho người dùng: tiếng Việt **có dấu**.
- Mốc test hiện tại: `vendor/bin/phpunit` → **1912 test, đúng 1 đỏ** là `Tests\Unit\Ctdt\CtdtCauHinhTest::gui_len_cong_mac_dinh_tat` (đỏ **có chủ đích**, đừng sửa). Đối chiếu theo **danh sách tên** chứ không theo con số.

---

## File Structure

| Tệp | Trách nhiệm |
|---|---|
| `app/Services/Dashboard/Tt12DashboardService.php` (mới) | Toàn bộ truy vấn. Không biết gì về HTTP. |
| `app/Http/Controllers/Dashboard/Tt12DashboardController.php` (mới) | Hai action mỏng, không chứa truy vấn. |
| `resources/views/dashboard/tt12.blade.php` (mới) | Lưới + dải, nạp bằng AJAX. |
| `routes/web.php` (sửa) | Hai route trong nhóm `checkrole:xml-man` sẵn có. |
| `config/adminlte.php` (sửa) | Mục menu thứ ba trong nhóm "Danh mục TT12". |
| `tests/Unit/Tt12/Tt12DashboardServiceTest.php` (mới) | Test service. |
| `tests/Unit/Tt12/Tt12DashboardManHinhTest.php` (mới) | Test route, menu, view, và chốt "không tự viết SQL". |

---

### Task 1: Service — lưới độ phủ

**Files:**
- Create: `app/Services/Dashboard/Tt12DashboardService.php`
- Test: `tests/Unit/Tt12/Tt12DashboardServiceTest.php`

**Interfaces:**
- Consumes: `App\Services\Tt12\Tt12DanhSach::truyVan(array $loc)` trả `Illuminate\Database\Eloquent\Builder`; `App\Services\Tt12\Tt12MauRegistry::tatCa()` trả `[ma_mau => ten_lop]`; `App\Services\BHYT\DanhSachCoSo::danhSach()` trả `[ma_cskcb => nhan]`.
- Produces: `Tt12DashboardService::doPhu()` trả mảng có hai khoá `luoi` và `dang_do_dang`. Task 2 và 3 dùng đúng hai khoá này.

- [ ] **Step 1: Viết test đầu tiên — lưới đầy đủ khi chưa có hồ sơ nào**

Tạo `tests/Unit/Tt12/Tt12DashboardServiceTest.php`:

```php
<?php

namespace Tests\Unit\Tt12;

use Tests\TestCase;
use Tests\Support\DungBangTt12Sqlite;
use Illuminate\Support\Facades\Cache;
use App\Models\BHYT\Tt12\Tt12HoSo;
use App\Services\BHYT\DanhSachCoSo;
use App\Services\Dashboard\Tt12DashboardService;

/**
 * Man dashboard do phu danh muc TT12.
 *
 * DanhSachCoSo doc bang his_branch tren Oracle HIS qua Cache::remember. Nap san cache trong
 * setUp() de test khong phu thuoc ket noi HIS - driver cache khi test la 'array' nen khong
 * ro ri sang test khac. Day la khuon da dung o Tt12ControllerTest.
 */
class Tt12DashboardServiceTest extends TestCase
{
    use DungBangTt12Sqlite;

    protected function setUp()
    {
        parent::setUp();
        $this->dungBangTt12();

        Cache::put(DanhSachCoSo::KHOA_CACHE, array(
            '01929' => '01929 - Bach Mai',
            '37470' => '37470 - Ninh Binh',
        ), 60);
    }

    private function tao($ma, array $ghiDe = array())
    {
        return Tt12HoSo::create(array_merge(array(
            'ma_ho_so' => $ma, 'mau' => 'MAU_01', 'loai_hs' => '70',
            'ma_cskcb' => '01929', 'ten_tep' => $ma . '.xlsx', 'so_dong' => 10,
            'id_danh_sach' => 'Id-' . $ma,
            'checked_at' => '2026-08-26 10:00:00', 'so_loi' => 0,
        ), $ghiDe));
    }

    /** @test */
    public function chua_co_ho_so_nao_thi_luoi_van_du_sau_mau_nhan_hai_co_so()
    {
        // Luoi phai DAY DU ngay ca khi rong. Chi ve o co du lieu thi co so chua khai bien
        // mat - dung cai ma man hinh nay sinh ra de phat hien.
        $kq = (new Tt12DashboardService())->doPhu();

        $this->assertCount(6, $kq['luoi'], 'phai du sau mau');

        foreach ($kq['luoi'] as $maMau => $theoCoSo) {
            $this->assertCount(2, $theoCoSo, $maMau . ': phai du hai co so');

            foreach ($theoCoSo as $maCs => $o) {
                $this->assertFalse($o['da_tiep_nhan'], $maMau . '/' . $maCs);
                $this->assertNull($o['tiep_nhan_luc']);
                $this->assertSame(0, $o['so_dong']);
            }
        }
    }
}
```

- [ ] **Step 2: Chạy test để chắc nó đỏ**

Run: `./vendor/bin/phpunit tests/Unit/Tt12/Tt12DashboardServiceTest.php`
Expected: FAIL — `Class 'App\Services\Dashboard\Tt12DashboardService' not found`

- [ ] **Step 3: Viết service tối thiểu cho lưới rỗng**

Tạo `app/Services/Dashboard/Tt12DashboardService.php`:

```php
<?php

namespace App\Services\Dashboard;

use App\Services\Tt12\Tt12DanhSach;
use App\Services\Tt12\Tt12MauRegistry;
use App\Services\BHYT\DanhSachCoSo;

/**
 * Truy van cho man dashboard do phu danh muc TT12.
 *
 * NGUYEN TAC: lop nay KHONG viet lai bat ky luat dem nao da co noi khac. Dem theo trang
 * thai thi goi lai Tt12DanhSach::truyVan(['trang_thai' => ...]) - ban SQL do da duoc test
 * rang voi Tt12QuyetDinhGui. Viet ban SQL thu hai o day la tao ra mot man hinh bao so KHAC
 * voi chinh bo loc ngay ben canh no, va nguoi dung se thoi tin ca hai.
 *
 * KHONG co bo loc thoi gian, khac han dashboard CTDT. Cau hoi "mau nay da bao gio duoc gui
 * chua" la cau hoi tren TOAN BO thoi gian; mac dinh lui 30 ngay se lam mot mau gui ba thang
 * truoc hien thanh "chua gui" - sai dung vao dieu man hinh sinh ra de tra loi. Bo duoc ma
 * khong lo quet bang vi tt12_ho_so co MOT dong cho moi TEP Excel, khong phai moi dong du
 * lieu: do thuc te 20 dong tt12_ho_so so voi 2.076 dong tt12_dong.
 *
 * KHONG khai kieu tra ve: Mockery 0.9.11 vo khi mock phuong thuc co return type tren PHP 7.4.
 */
class Tt12DashboardService
{
    /**
     * @return array ['luoi' => [ma_mau => [ma_cskcb => o]], 'dang_do_dang' => [ma => so]]
     */
    public function doPhu()
    {
        return array(
            'luoi'         => $this->luoi(),
            'dang_do_dang' => array(),
        );
    }

    /**
     * Luoi DAY DU sau mau x moi co so, ke ca o chua co du lieu.
     *
     * Dung lai vong lap tu Tt12MauRegistry va DanhSachCoSo chu khong gom theo du lieu da co:
     * gom theo du lieu thi mau chua bao gio gui khong xuat hien, ma do chinh la thu can thay.
     */
    private function luoi()
    {
        $ra = array();

        foreach (array_keys(Tt12MauRegistry::tatCa()) as $maMau) {
            foreach (array_keys(DanhSachCoSo::danhSach()) as $maCs) {
                $ra[$maMau][$maCs] = $this->motO($maMau, $maCs);
            }
        }

        return $ra;
    }

    /**
     * @param string $maMau
     * @param string $maCs
     * @return array
     */
    private function motO($maMau, $maCs)
    {
        $hoSo = Tt12DanhSach::truyVan(array(
            'mau'        => $maMau,
            'ma_cskcb'   => $maCs,
            'trang_thai' => 'da_gui',
        ))
        ->orderBy('thoi_gian_tiep_nhan', 'desc')
        ->first();

        if ($hoSo === null) {
            return array('da_tiep_nhan' => false, 'tiep_nhan_luc' => null, 'so_dong' => 0);
        }

        return array(
            'da_tiep_nhan'  => true,
            'tiep_nhan_luc' => $hoSo->thoi_gian_tiep_nhan,
            'so_dong'       => (int) $hoSo->so_dong,
        );
    }
}
```

- [ ] **Step 4: Chạy test để chắc nó xanh**

Run: `./vendor/bin/phpunit tests/Unit/Tt12/Tt12DashboardServiceTest.php`
Expected: PASS (1 test)

- [ ] **Step 5: Commit**

```bash
git add app/Services/Dashboard/Tt12DashboardService.php tests/Unit/Tt12/Tt12DashboardServiceTest.php
git commit -m "feat(tt12): service dashboard - luoi do phu day du sau mau x co so"
```

---

### Task 2: Service — ô chỉ xanh khi cổng ĐÃ TIẾP NHẬN, số dòng lấy từ hồ sơ gần nhất

**Files:**
- Modify: `app/Services/Dashboard/Tt12DashboardService.php` (không đổi mã — Task 1 đã đúng; task này **khoá hành vi** bằng test)
- Test: `tests/Unit/Tt12/Tt12DashboardServiceTest.php`

**Interfaces:**
- Consumes: `Tt12DashboardService::doPhu()` từ Task 1.
- Produces: không có API mới.

- [ ] **Step 1: Viết ba test khoá hành vi**

Thêm vào `tests/Unit/Tt12/Tt12DashboardServiceTest.php`, trước dấu `}` cuối cùng:

```php
    /** @test */
    public function o_chi_xanh_khi_cong_DA_TIEP_NHAN_chu_khong_phai_chi_da_ky()
    {
        // Day la nham lan de xay ra nhat, va no noi doi theo huong nguy hiem: bao la xong
        // trong khi ho so chua he roi khoi may.
        $this->tao('A', array('is_signed' => true));                       // da ky, chua gui
        $this->tao('B', array('is_signed' => true, 'ma_ket_qua' => '500')); // gui loi

        $kq = (new Tt12DashboardService())->doPhu();

        $this->assertFalse($kq['luoi']['MAU_01']['01929']['da_tiep_nhan']);
    }

    /** @test */
    public function o_xanh_khi_co_ho_so_duoc_tiep_nhan()
    {
        $this->tao('A', array(
            'is_signed' => true, 'ma_ket_qua' => '200', 'ma_gd' => 'GD1',
            'thoi_gian_tiep_nhan' => '20260826104112',
        ));

        $o = (new Tt12DashboardService())->doPhu()['luoi']['MAU_01']['01929'];

        $this->assertTrue($o['da_tiep_nhan']);
        $this->assertSame('20260826104112', $o['tiep_nhan_luc']);
        $this->assertSame(10, $o['so_dong']);
    }

    /** @test */
    public function so_dong_lay_tu_ho_so_GAN_NHAT_chu_khong_cong_don()
    {
        // Lan gui sau THAY THE lan truoc chu khong them vao. Cong don la dem trung, va con
        // so do se lon dan mai theo so lan gui lai chu khong theo quy mo danh muc that.
        $this->tao('CU', array(
            'is_signed' => true, 'ma_ket_qua' => '200', 'so_dong' => 10,
            'thoi_gian_tiep_nhan' => '20260801080000',
        ));
        $this->tao('MOI', array(
            'is_signed' => true, 'ma_ket_qua' => '200', 'so_dong' => 25,
            'thoi_gian_tiep_nhan' => '20260826104112',
        ));

        $o = (new Tt12DashboardService())->doPhu()['luoi']['MAU_01']['01929'];

        $this->assertSame(25, $o['so_dong'], 'phai lay ho so moi nhat, khong cong 10 + 25');
        $this->assertSame('20260826104112', $o['tiep_nhan_luc']);
    }
```

- [ ] **Step 2: Chạy test**

Run: `./vendor/bin/phpunit tests/Unit/Tt12/Tt12DashboardServiceTest.php`
Expected: PASS (4 test). Nếu có test đỏ thì Task 1 sai — sửa `motO()` chứ đừng sửa test.

- [ ] **Step 3: Kiểm bằng đột biến — bỏ `orderBy` thì test có bắt được không**

Sửa tạm `app/Services/Dashboard/Tt12DashboardService.php`, xoá dòng `->orderBy('thoi_gian_tiep_nhan', 'desc')`.

Run: `./vendor/bin/phpunit tests/Unit/Tt12/Tt12DashboardServiceTest.php`
Expected: FAIL ở `so_dong_lay_tu_ho_so_GAN_NHAT_chu_khong_cong_don`.

Nếu nó **vẫn xanh** thì test đang xanh nhờ thứ tự chèn ngẫu nhiên chứ không nhờ `orderBy` — phải sửa test cho khác biệt lộ ra (ví dụ tạo `MOI` trước `CU`).

- [ ] **Step 4: Khôi phục dòng `orderBy` và chạy lại**

Run: `./vendor/bin/phpunit tests/Unit/Tt12/Tt12DashboardServiceTest.php`
Expected: PASS (4 test)

- [ ] **Step 5: Commit**

```bash
git add tests/Unit/Tt12/Tt12DashboardServiceTest.php
git commit -m "test(tt12): khoa hanh vi o luoi - chi xanh khi da tiep nhan, so dong lay ban moi nhat"
```

---

### Task 3: Service — dải "đang dở dang" và cơ sở ngoài danh sách HIS

**Files:**
- Modify: `app/Services/Dashboard/Tt12DashboardService.php`
- Test: `tests/Unit/Tt12/Tt12DashboardServiceTest.php`

**Interfaces:**
- Consumes: `Tt12DanhSach::cacTrangThai()` trả `['chua_kiem' => 'Chưa kiểm', 'con_loi' => ..., 'san_sang' => ..., 'da_ky' => ..., 'da_gui' => ..., 'loi_gui' => ...]`.
- Produces: khoá `dang_do_dang` trong `doPhu()` là mảng `[ma_trang_thai => so_luong]` với **năm** khoá (mọi trạng thái **trừ** `da_gui`). Task 4 dùng đúng năm khoá này.

- [ ] **Step 1: Viết test cho dải dở dang**

Thêm vào `tests/Unit/Tt12/Tt12DashboardServiceTest.php`:

```php
    /** @test */
    public function dai_do_dang_dem_ho_so_CHUA_duoc_tiep_nhan_theo_tung_trang_thai()
    {
        // Luoi chi noi ve thu DA XONG. Khong co dai nay thi mot ho so ket o "Gui loi" hoan
        // toan vo hinh - o van xam nhu the chua ai lam gi, trong khi thuc ra co nguoi da lam
        // va dang hong.
        $this->tao('A', array('checked_at' => null));                        // chua kiem
        $this->tao('B', array('so_loi' => 3));                               // con loi
        $this->tao('C');                                                      // san sang ky
        $this->tao('D', array('is_signed' => true));                          // da ky chua gui
        $this->tao('E', array('is_signed' => true, 'ma_ket_qua' => '500'));   // gui loi
        $this->tao('F', array('is_signed' => true, 'ma_ket_qua' => '200'));   // da gui

        $dai = (new Tt12DashboardService())->doPhu()['dang_do_dang'];

        $this->assertSame(1, $dai['chua_kiem']);
        $this->assertSame(1, $dai['con_loi']);
        $this->assertSame(1, $dai['san_sang']);
        $this->assertSame(1, $dai['da_ky']);
        $this->assertSame(1, $dai['loi_gui']);

        $this->assertArrayNotHasKey('da_gui', $dai,
            'da_gui khong thuoc dai DO DANG - no da xong, va luoi ben tren da noi roi');
    }

    /** @test */
    public function co_so_co_ho_so_ma_khong_con_trong_HIS_van_hien_trong_luoi()
    {
        // Xay ra khi mot co so ngung hoat dong sau khi da gui danh muc. Giau di la mat dau
        // vet mot bo danh muc DA THUC SU gui len cong BHXH.
        $this->tao('A', array(
            'ma_cskcb' => '99999', 'is_signed' => true, 'ma_ket_qua' => '200',
            'thoi_gian_tiep_nhan' => '20260801080000',
        ));

        $luoi = (new Tt12DashboardService())->doPhu()['luoi'];

        $this->assertArrayHasKey('99999', $luoi['MAU_01'],
            'co so ngoai danh sach HIS van phai hien');
        $this->assertTrue($luoi['MAU_01']['99999']['da_tiep_nhan']);
        $this->assertTrue($luoi['MAU_01']['99999']['ngoai_danh_sach'],
            'phai danh dau de nguoi doc biet co so nay khong con trong HIS');

        // Co so con trong HIS thi co la false, khong phai vang mat
        $this->assertFalse($luoi['MAU_01']['01929']['ngoai_danh_sach']);
    }
```

- [ ] **Step 2: Chạy test để chắc nó đỏ**

Run: `./vendor/bin/phpunit tests/Unit/Tt12/Tt12DashboardServiceTest.php`
Expected: FAIL — `dang_do_dang` rỗng, và khoá `99999` chưa có.

- [ ] **Step 3: Bổ sung service**

Trong `app/Services/Dashboard/Tt12DashboardService.php`, thay `doPhu()` và `luoi()`, thêm hai hàm mới. Thêm `use App\Models\BHYT\Tt12\Tt12HoSo;` vào đầu tệp.

```php
    public function doPhu()
    {
        return array(
            'luoi'         => $this->luoi(),
            'dang_do_dang' => $this->dangDoDang(),
        );
    }

    private function luoi()
    {
        $trongHis = array_keys(DanhSachCoSo::danhSach());

        // Co so co du lieu ma khong con trong HIS van phai hien - xem chu thich o motO().
        $coDuLieu = Tt12HoSo::distinct()->pluck('ma_cskcb')->all();

        $tatCaCoSo = array_values(array_unique(array_merge($trongHis, $coDuLieu)));
        sort($tatCaCoSo);

        $ra = array();

        foreach (array_keys(Tt12MauRegistry::tatCa()) as $maMau) {
            foreach ($tatCaCoSo as $maCs) {
                $ra[$maMau][$maCs] = $this->motO($maMau, $maCs, !in_array($maCs, $trongHis, true));
            }
        }

        return $ra;
    }

    /**
     * Dem ho so CHUA duoc tiep nhan, tach theo tung trang thai.
     *
     * Bo 'da_gui' ra khoi dai nay: no la trang thai DA XONG, va luoi ben tren da noi ve no
     * roi. De lai la mot con so bi doc hai lan o hai cho voi hai y nghia khac nhau.
     */
    private function dangDoDang()
    {
        $ra = array();

        foreach (array_keys(Tt12DanhSach::cacTrangThai()) as $ma) {
            if ($ma === 'da_gui') {
                continue;
            }

            $ra[$ma] = (int) Tt12DanhSach::truyVan(array('trang_thai' => $ma))->count();
        }

        return $ra;
    }
```

Và sửa `motO()` để nhận tham số thứ ba:

```php
    /**
     * @param string $maMau
     * @param string $maCs
     * @param bool   $ngoaiDanhSach co so khong con trong danh sach HIS hien hanh
     * @return array
     */
    private function motO($maMau, $maCs, $ngoaiDanhSach = false)
    {
        $hoSo = Tt12DanhSach::truyVan(array(
            'mau'        => $maMau,
            'ma_cskcb'   => $maCs,
            'trang_thai' => 'da_gui',
        ))
        ->orderBy('thoi_gian_tiep_nhan', 'desc')
        ->first();

        if ($hoSo === null) {
            return array(
                'da_tiep_nhan'    => false,
                'tiep_nhan_luc'   => null,
                'so_dong'         => 0,
                'ngoai_danh_sach' => $ngoaiDanhSach,
            );
        }

        return array(
            'da_tiep_nhan'    => true,
            'tiep_nhan_luc'   => $hoSo->thoi_gian_tiep_nhan,
            'so_dong'         => (int) $hoSo->so_dong,
            'ngoai_danh_sach' => $ngoaiDanhSach,
        );
    }
```

- [ ] **Step 4: Cập nhật test của Task 1 cho khớp khoá mới**

Trong `chua_co_ho_so_nao_thi_luoi_van_du_sau_mau_nhan_hai_co_so`, thêm sau `assertSame(0, $o['so_dong']);`:

```php
                $this->assertFalse($o['ngoai_danh_sach']);
```

- [ ] **Step 5: Chạy toàn bộ tệp test**

Run: `./vendor/bin/phpunit tests/Unit/Tt12/Tt12DashboardServiceTest.php`
Expected: PASS (6 test)

- [ ] **Step 6: Commit**

```bash
git add app/Services/Dashboard/Tt12DashboardService.php tests/Unit/Tt12/Tt12DashboardServiceTest.php
git commit -m "feat(tt12): dai ho so do dang va co so ngoai danh sach HIS"
```

---

### Task 4: Controller, route và menu

**Files:**
- Create: `app/Http/Controllers/Dashboard/Tt12DashboardController.php`
- Modify: `routes/web.php` (chèn ngay trước dòng `Route::get('tt12/detail/{ma_ho_so}'`)
- Modify: `config/adminlte.php` (trong khối `'text' => 'Danh mục TT12'`, sau mục "Nạp danh mục")
- Test: `tests/Unit/Tt12/Tt12DashboardManHinhTest.php`

**Interfaces:**
- Consumes: `Tt12DashboardService::doPhu()` từ Task 3.
- Produces: hai route tên `bhyt.tt12.dashboard` và `bhyt.tt12.dashboard.do-phu`. Task 5 dùng tên route thứ hai trong view.

- [ ] **Step 1: Viết test cho route và menu**

Tạo `tests/Unit/Tt12/Tt12DashboardManHinhTest.php`:

```php
<?php

namespace Tests\Unit\Tt12;

use Tests\TestCase;

/**
 * Chot canh cho man dashboard do phu: route, menu, va nguyen tac khong tu viet SQL.
 */
class Tt12DashboardManHinhTest extends TestCase
{
    /** @test */
    public function hai_route_dashboard_duoc_khai_bao()
    {
        $this->assertSame(
            route('bhyt.tt12.dashboard') . '/do-phu',
            route('bhyt.tt12.dashboard.do-phu'),
            'route do-phu phai la duong dan dashboard cong "/do-phu"'
        );
    }

    /** @test */
    public function route_dashboard_nam_trong_chot_quyen_xml_man()
    {
        // Cung quyen voi hai man TT12 kia. Thieu chot nay thi bat ky tai khoan dang nhap nao
        // cung doc duoc do phu danh muc cua don vi.
        $duong = parse_url(route('bhyt.tt12.dashboard'), PHP_URL_PATH);
        $duong = ltrim($duong, '/');

        foreach (app('router')->getRoutes() as $r) {
            if ($r->uri() === $duong) {
                $this->assertContains('checkrole:xml-man', $r->gatherMiddleware());

                return;
            }
        }

        $this->fail('khong tim thay route ' . $duong);
    }

    /** @test */
    public function menu_co_muc_dashboard_trong_nhom_TT12()
    {
        // Man hinh khong co loi vao tren menu la man hinh khong ai dung.
        $noiDung = file_get_contents(base_path('config/adminlte.php'));

        $this->assertContains("'route'  => 'bhyt.tt12.dashboard'", $noiDung);
    }

    /** @test */
    public function service_KHONG_tu_viet_SQL_dem_trang_thai()
    {
        // Dem theo trang thai phai goi Tt12DanhSach::truyVan(). Viet ban SQL thu hai o day la
        // tao ra mot man hinh bao so KHAC voi chinh bo loc ngay ben canh no, va nguoi dung se
        // thoi tin ca hai.
        $nguon = file_get_contents(
            base_path('app/Services/Dashboard/Tt12DashboardService.php')
        );

        foreach (array("where('ma_ket_qua'", "where('is_signed'", "where('checked_at'",
                       "where('so_loi'", 'whereNotNull') as $cam) {
            $this->assertNotContains($cam, $nguon,
                'Tt12DashboardService tu viet luat trang thai (' . $cam
                . ') thay vi goi Tt12DanhSach::truyVan()');
        }
    }
}
```

- [ ] **Step 2: Chạy test để chắc nó đỏ**

Run: `./vendor/bin/phpunit tests/Unit/Tt12/Tt12DashboardManHinhTest.php`
Expected: FAIL — `Route [bhyt.tt12.dashboard] not defined.`

- [ ] **Step 3: Tạo controller**

Tạo `app/Http/Controllers/Dashboard/Tt12DashboardController.php`:

```php
<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Services\Dashboard\Tt12DashboardService;

/**
 * Controller MONG: moi truy van nam trong Tt12DashboardService. Xem chu thich lop do ve vi
 * sao khong tu viet SQL trang thai.
 *
 * KHONG nhan tham so loc: man nay khong co bo loc thoi gian - xem muc 7.1 cua spec. Nhan mot
 * tham so khong duong nao dat duoc chi lam nguoi doc sau tuong man hinh co bo loc.
 */
class Tt12DashboardController extends Controller
{
    /** @var Tt12DashboardService */
    protected $service;

    public function __construct(Tt12DashboardService $service)
    {
        $this->service = $service;
    }

    public function index()
    {
        return view('dashboard.tt12');
    }

    public function doPhu()
    {
        return response()->json($this->service->doPhu());
    }
}
```

- [ ] **Step 4: Thêm hai route**

Trong `routes/web.php`, chèn **ngay trước** dòng `Route::get('tt12/detail/{ma_ho_so}', 'BHYT\BHYTTt12Controller@detail')`:

```php
        // Dashboard do phu danh muc. Dat TRUOC khoi 'tt12/detail/{ma_ho_so}' cho de doc;
        // hai duong dan khong dam nhau nen thu tu o day khong bat buoc.
        Route::get('tt12/dashboard', 'Dashboard\Tt12DashboardController@index')
            ->name('bhyt.tt12.dashboard');
        Route::get('tt12/dashboard/do-phu', 'Dashboard\Tt12DashboardController@doPhu')
            ->name('bhyt.tt12.dashboard.do-phu');
```

- [ ] **Step 5: Thêm mục menu**

Trong `config/adminlte.php`, bên trong khối `'text' => 'Danh mục TT12'`, thêm **sau** mục "Nạp danh mục" (tức trước dòng `],` đóng `'submenu'`):

```php
                        [
                            'text'   => 'Dashboard danh mục',
                            'icon'   => 'dashboard',
                            'route'  => 'bhyt.tt12.dashboard',
                            'active' => ['bhyt/tt12/dashboard*'],
                        ],
```

- [ ] **Step 6: Chạy test**

Run: `./vendor/bin/phpunit tests/Unit/Tt12/Tt12DashboardManHinhTest.php`
Expected: PASS (4 test)

- [ ] **Step 7: Commit**

```bash
git add app/Http/Controllers/Dashboard/Tt12DashboardController.php routes/web.php config/adminlte.php tests/Unit/Tt12/Tt12DashboardManHinhTest.php
git commit -m "feat(tt12): controller, route va menu cho dashboard do phu"
```

---

### Task 5: View — lưới và dải

**Files:**
- Create: `resources/views/dashboard/tt12.blade.php`
- Modify: `tests/Unit/Tt12/Tt12DashboardManHinhTest.php`

**Interfaces:**
- Consumes: route `bhyt.tt12.dashboard.do-phu` (Task 4); JSON có `luoi` và `dang_do_dang` (Task 3); `Tt12MauRegistry::tatCa()` và `DanhSachCoSo::danhSach()` để tra nhãn.
- Produces: không có API mới.

- [ ] **Step 1: Viết test cho view**

Thêm vào `tests/Unit/Tt12/Tt12DashboardManHinhTest.php`, trước dấu `}` cuối:

```php
    /** @test */
    public function view_bien_dich_duoc()
    {
        $duongDan = resource_path('views/dashboard/tt12.blade.php');

        $this->assertFileExists($duongDan);

        $php = app('blade.compiler')->compileString(file_get_contents($duongDan));

        $tam = tempnam(sys_get_temp_dir(), 'blade') . '.php';
        file_put_contents($tam, $php);

        exec('php -l ' . escapeshellarg($tam), $ra, $ma);
        unlink($tam);

        $this->assertSame(0, $ma, implode(PHP_EOL, $ra));
    }

    /** @test */
    public function view_bao_ro_khi_khong_doc_duoc_danh_sach_co_so()
    {
        // DanhSachCoSo tra mang RONG khi HIS hong. Hien mot luoi trong nhu the moi thu chua
        // khai la noi doi: "khong biet" va "chua khai" la hai chuyen khac han nhau ma cung
        // trong giong nhau.
        $noiDung = file_get_contents(resource_path('views/dashboard/tt12.blade.php'));

        $this->assertContains('Không đọc được danh sách cơ sở', $noiDung);
    }

    /** @test */
    public function view_nap_du_lieu_qua_route_do_phu()
    {
        $noiDung = file_get_contents(resource_path('views/dashboard/tt12.blade.php'));

        $this->assertContains("route('bhyt.tt12.dashboard.do-phu')", $noiDung);
    }
```

- [ ] **Step 2: Chạy test để chắc nó đỏ**

Run: `./vendor/bin/phpunit tests/Unit/Tt12/Tt12DashboardManHinhTest.php`
Expected: FAIL — `Failed asserting that file ... exists`

- [ ] **Step 3: Tạo view**

Tạo `resources/views/dashboard/tt12.blade.php`:

```blade
@extends('adminlte::page')

@section('title', 'Dashboard danh mục TT12')

@section('content_header')
<h1>Độ phủ danh mục TT12</h1>
@stop

@section('content')
@include('includes.message')

{{-- Nhan hien thi tra tu registry ngay trong view: service chi tra MA, de doi ten hien thi
     khong phai sua service. --}}
@php
    $tenMau = [];
    foreach (\App\Services\Tt12\Tt12MauRegistry::tatCa() as $ma => $lop) {
        $tenMau[$ma] = $lop::ten();
    }
    $tenCoSo = \App\Services\BHYT\DanhSachCoSo::danhSach();
@endphp

@if (empty($tenCoSo))
{{-- DanhSachCoSo tra mang rong khi HIS hong. Phai noi ro thay vi hien luoi trong: "khong
     biet" va "chua khai" la hai chuyen khac han nhau ma cung trong giong nhau. --}}
<div class="alert alert-warning">
    <strong>Không đọc được danh sách cơ sở</strong> từ phần mềm quản lý bệnh viện.
    Lưới bên dưới có thể thiếu cột. Báo bộ phận công nghệ thông tin kiểm tra kết nối HIS.
</div>
@endif

<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title">Sáu mẫu × các cơ sở</h3>
    </div>
    <div class="box-body table-responsive">
        <table class="table table-bordered" id="bang-do-phu">
            <thead><tr><th>Mẫu</th></tr></thead>
            <tbody><tr><td><p class="text-muted">Đang tải…</p></td></tr></tbody>
        </table>
    </div>
</div>

<div class="box box-default">
    <div class="box-header with-border">
        <h3 class="box-title">Hồ sơ đang dở dang</h3>
    </div>
    <div class="box-body" id="dai-do-dang">
        <p class="text-muted">Đang tải…</p>
    </div>
</div>
@stop

@push('after-scripts')
<script>
$(function () {
    var tenMau  = @json($tenMau);
    var tenCoSo = @json($tenCoSo);
    var nhanTrangThai = @json(\App\Services\Tt12\Tt12DanhSach::cacTrangThai());
    var urlDanhSach = "{{ route('bhyt.tt12.index') }}";

    $.get("{{ route('bhyt.tt12.dashboard.do-phu') }}")
        .done(function (kq) {
            veLuoi(kq.luoi);
            veDai(kq.dang_do_dang);
        })
        .fail(function () {
            $('#bang-do-phu tbody').html(
                '<tr><td class="text-danger">Không tải được dữ liệu. Thử lại sau.</td></tr>');
            $('#dai-do-dang').html('<p class="text-danger">Không tải được dữ liệu.</p>');
        });

    function veLuoi(luoi) {
        var maMau = Object.keys(luoi);

        if (!maMau.length) {
            $('#bang-do-phu tbody').html('<tr><td class="text-muted">Chưa có dữ liệu.</td></tr>');
            return;
        }

        // Truc co so lay tu chinh du lieu tra ve, khong tu tenCoSo: co so ngoai danh sach HIS
        // van phai co cot rieng - giau di la mat dau vet mot bo danh muc da gui len cong.
        var maCoSo = Object.keys(luoi[maMau[0]]);

        var $th = $('<tr>').append($('<th>').text('Mẫu'));

        $.each(maCoSo, function (i, ma) {
            $th.append($('<th>').text(tenCoSo[ma] || ma));
        });

        var $tb = $('<tbody>');

        $.each(maMau, function (i, mau) {
            var $tr = $('<tr>').append($('<td>').text(tenMau[mau] || mau));

            $.each(maCoSo, function (j, cs) {
                $tr.append(veO(luoi[mau][cs], mau, cs));
            });

            $tb.append($tr);
        });

        $('#bang-do-phu thead').html($th);
        $('#bang-do-phu tbody').replaceWith($tb);
    }

    function veO(o, mau, cs) {
        var $td = $('<td>');

        // .text() chu KHONG noi chuoi vao HTML: tiep_nhan_luc den tu phan hoi cua cong BHXH,
        // tuc du lieu ngoai.
        var $a = $('<a>')
            .attr('href', urlDanhSach + '?mau=' + encodeURIComponent(mau)
                          + '&ma_cskcb=' + encodeURIComponent(cs));

        if (o.da_tiep_nhan) {
            $a.append($('<span>').addClass('label label-success').text('Đã tiếp nhận'));
            $a.append($('<div>').addClass('small').text(
                doiNgay(o.tiep_nhan_luc) + ' · ' + o.so_dong + ' dòng'));
        } else {
            $a.append($('<span>').addClass('label label-default').text('Chưa gửi'));
        }

        $td.append($a);

        if (o.ngoai_danh_sach) {
            $td.append($('<div>').addClass('small text-muted')
                .text('(cơ sở không còn trong danh sách hiện hành)'));
        }

        return $td;
    }

    /** Cong tra dang YYYYMMDDHHmmss. Khong dung Date() de tranh lech mui gio. */
    function doiNgay(s) {
        if (!s || s.length < 8) {
            return s || '';
        }

        return s.substr(6, 2) + '/' + s.substr(4, 2) + '/' + s.substr(0, 4);
    }

    function veDai(dai) {
        var $ul = $('<ul>').addClass('list-inline');
        var tong = 0;

        $.each(dai, function (ma, so) {
            tong += so;

            $ul.append($('<li>').append(
                $('<a>').attr('href', urlDanhSach + '?trang_thai=' + encodeURIComponent(ma))
                    .text((nhanTrangThai[ma] || ma) + ': ' + so)
            ));
        });

        $('#dai-do-dang').html(tong === 0
            ? '<p class="text-muted">Không có hồ sơ nào đang dở dang.</p>'
            : $ul);
    }
});
</script>
@endpush
```

- [ ] **Step 4: Chạy test**

Run: `./vendor/bin/phpunit tests/Unit/Tt12/Tt12DashboardManHinhTest.php`
Expected: PASS (7 test)

- [ ] **Step 5: Kiểm cú pháp JS thật bằng Node**

Test Blade chỉ kiểm phần PHP biên dịch ra, không kiểm JavaScript. Chạy:

```bash
node -e "
const fs=require('fs'),vm=require('vm');
let s=fs.readFileSync('resources/views/dashboard/tt12.blade.php','utf8');
s=s.replace(/\{\{--[\s\S]*?--\}\}/g,'').replace(/\{\{[\s\S]*?\}\}/g,'0');
s=s.replace(/@json\(.*\)/g,'null');
const js=/<script>([\s\S]*?)<\/script>/.exec(s)[1];
new vm.Script(js); console.log('JS hop le');
"
```

Expected: in ra `JS hop le`. Nếu ném `SyntaxError` thì sửa view rồi chạy lại.

- [ ] **Step 6: Commit**

```bash
git add resources/views/dashboard/tt12.blade.php tests/Unit/Tt12/Tt12DashboardManHinhTest.php
git commit -m "feat(tt12): view dashboard do phu - luoi va dai do dang"
```

---

### Task 6: Nghiệm thu toàn bộ và tài liệu

**Files:**
- Modify: `docs/huong-dan-su-dung/_nguon/part7.js`
- Modify: `docs/huong-dan-su-dung/_nguon/version.js`
- Modify: `docs/huong-dan-su-dung/_nguon/front.js`
- Modify: `docs/huong-dan-su-dung/Huong-dan-su-dung-XML3176-OrderCheck-TheBHYT-DanhMuc.docx`

**Interfaces:**
- Consumes: mọi thứ từ Task 1–5.
- Produces: không có API mới.

- [ ] **Step 1: Chạy toàn bộ bộ test và đối chiếu mốc**

```bash
./vendor/bin/phpunit 2>&1 | tail -4
./vendor/bin/phpunit 2>&1 | grep -E "^[0-9]+\) Tests\\\\" | sed 's/^[0-9]*) //'
```

Expected: đúng **một** tên đỏ là `Tests\Unit\Ctdt\CtdtCauHinhTest::gui_len_cong_mac_dinh_tat`. Bất kỳ tên nào khác là do task này gây ra — sửa trước khi đi tiếp.

- [ ] **Step 2: Thêm mục 7.12 vào tài liệu**

Trong `docs/huong-dan-su-dung/_nguon/part7.js`, thêm **ngay trước** dòng `forIt(` ở cuối hàm:

```javascript
    h2('7.12. Dashboard độ phủ danh mục'),
    p('Mở Hồ sơ XML → Danh mục TT12 → Dashboard danh mục. Màn hình trả lời một câu hỏi mà màn Danh sách hồ sơ không trả lời được: trong sáu mẫu của từng cơ sở, cái nào đã được cổng Bảo hiểm xã hội tiếp nhận và cái nào chưa bao giờ gửi.'),
    p('Lưới có sáu hàng là sáu mẫu và mỗi cột là một cơ sở khám chữa bệnh. Ô ghi Đã tiếp nhận kèm ngày và số dòng của lần gửi gần nhất; ô ghi Chưa gửi nghĩa là chưa có hồ sơ nào của mẫu đó được cổng chấp nhận. Bấm vào ô nào sẽ mở màn Danh sách hồ sơ đã lọc sẵn theo mẫu và cơ sở tương ứng.'),
    p('Số dòng hiển thị là của riêng lần gửi gần nhất, không cộng dồn qua các lần gửi. Lần gửi sau thay thế lần trước chứ không thêm vào, nên cộng dồn sẽ ra một con số không có ý nghĩa.'),
    p('Khối Hồ sơ đang dở dang bên dưới đếm những hồ sơ đã nạp nhưng chưa được tiếp nhận, tách theo từng trạng thái. Cần đọc cả khối này: lưới chỉ nói về việc đã xong, nên một hồ sơ đang kẹt ở Gửi lỗi sẽ không hiện ra ở lưới.'),
    note('Lưu ý:', 'Màn hình này không có bộ lọc thời gian, và đó là cố ý. Câu hỏi "đã bao giờ gửi chưa" là câu hỏi trên toàn bộ thời gian; giới hạn theo khoảng ngày sẽ làm một mẫu gửi từ lâu hiện thành chưa gửi. Cũng vì Thông tư 12 không quy định chu kỳ gửi cố định — danh mục chỉ gửi khi có thay đổi — nên màn hình không đánh dấu "quá hạn" cho bất kỳ ô nào.'),
```

- [ ] **Step 3: Nâng phiên bản tài liệu**

Trong `docs/huong-dan-su-dung/_nguon/version.js`, sửa `PHIEN_BAN` thành `'1.6'` và thêm vào **đầu** mảng `LICH_SU`:

```javascript
  {
    ban: '1.6',
    ngay: '26/08/2026',
    noi_dung:
      'Danh mục TT12: thêm màn hình Dashboard danh mục cho biết sáu mẫu của từng cơ sở, cái nào đã được cổng Bảo hiểm xã hội tiếp nhận và cái nào chưa bao giờ gửi, kèm khối hồ sơ đang dở dang.',
    lien_quan: 'Phần VII, mục 7.12 (mới); Chương 0 cập nhật bản đồ menu.',
  },
```

- [ ] **Step 4: Cập nhật bản đồ menu ở Chương 0**

Trong `docs/huong-dan-su-dung/_nguon/front.js`, sửa dòng bản đồ menu của TT12:

```javascript
        ['Hồ sơ XML', 'Danh mục TT12 (Danh sách hồ sơ, Nạp danh mục, Dashboard danh mục)', 'xml-man'],
```

- [ ] **Step 5: Dựng lại tệp .docx**

```bash
npm install docx --no-save --no-package-lock
cd docs/huong-dan-su-dung/_nguon && node build.js ../Huong-dan-su-dung-XML3176-OrderCheck-TheBHYT-DanhMuc.docx
cd ../../.. && rm -rf node_modules
```

Expected: in ra `OK -> ...docx` kèm kích thước.

Hai cờ `--no-save --no-package-lock` là **bắt buộc**: dự án cố ý không có `package.json`, thiếu chúng thì npm tự tạo hai tệp đó ở thư mục gốc.

- [ ] **Step 6: Xác minh .docx thật sự chứa mục mới**

```bash
python3 -c "
import zipfile, re
z = zipfile.ZipFile('docs/huong-dan-su-dung/Huong-dan-su-dung-XML3176-OrderCheck-TheBHYT-DanhMuc.docx')
t = re.sub(r'<[^>]+>', '', z.read('word/document.xml').decode('utf-8'))
for tim in ['7.12. Dashboard', '1.6', 'Dashboard danh mục']:
    print(('CO   ' if tim in t else 'THIEU') + '  ' + tim)
"
```

Expected: cả ba đều `CO`.

- [ ] **Step 7: Commit**

```bash
git add docs/huong-dan-su-dung
git commit -m "docs(huong-dan): bo sung muc 7.12 Dashboard do phu, tai lieu len ban 1.6"
```

- [ ] **Step 8: Báo lại việc thủ công còn lại**

Mục lục của `.docx` là trường tự động. Báo cho chủ dự án: mở tệp bằng Word, `Ctrl+A` rồi `F9`, chọn **"Update entire table"**, lưu lại và commit đè. Chưa làm bước này thì mục 7.12 chưa hiện trong mục lục.

---

## Self-Review

**Spec coverage:**

| Mục spec | Task |
|---|---|
| 5.1 Lưới độ phủ | Task 1, 2 |
| 5.2 Dải đang dở dang | Task 3 |
| 5.3 Bấm vào ô → màn danh sách | Task 5 |
| 6 Không viết lại luật đếm | Task 4 (test chốt) |
| 7 Cây tệp | Task 1, 4, 5 |
| 7.1 Không có bộ lọc thời gian | Task 1 (chú thích), Task 4 (controller không nhận tham số) |
| 8 Hình dạng dữ liệu | Task 1, 3 |
| 9 HIS không đọc được | Task 5 |
| 9 Cơ sở ngoài danh sách HIS | Task 3 |
| 10 Bảy khẳng định test | Task 1–5, đủ cả bảy |

**Không có mục spec nào thiếu task.**

**Type consistency:** `doPhu()` không tham số ở cả Task 1, 3, 4. Khoá ô gồm bốn trường `da_tiep_nhan`, `tiep_nhan_luc`, `so_dong`, `ngoai_danh_sach` — Task 3 thêm trường thứ tư và **Step 4 của Task 3 cập nhật lại test của Task 1** cho khớp. `dang_do_dang` có năm khoá ở cả Task 3 và Task 5.

**Placeholder scan:** không có "TBD"/"TODO"/"tương tự Task N". Mọi bước có mã đều kèm mã đầy đủ.
