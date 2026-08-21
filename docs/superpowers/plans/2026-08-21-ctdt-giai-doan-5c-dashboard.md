# Giai đoạn 5C — Dashboard chứng từ điện tử

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Một màn hình trả lời ba câu hỏi: hệ thống có đang chạy không, sản lượng ra sao, và dữ liệu sai ở đâu.

**Architecture:** Theo đúng khuôn `dashboard/xml3176` đã có — một `Service` chứa truy vấn, một `Controller` mỏng trả JSON, một Blade dựng khung, một tệp JS vẽ Highcharts. Phần đếm theo trạng thái **không tự viết SQL**: nó gọi lại `CtdtDanhSach::truyVan(['trang_thai_gui' => ...])`, tức bản SQL đã được một test tính chất ràng với `CtdtTrangThaiGui::cua()`. Viết bản SQL thứ ba là tạo ra một màn hình báo số khác với chính bộ lọc ngay bên cạnh nó.

**Tech Stack:** Laravel 5.5, PHP 7.4, PHPUnit 6, Highcharts (`public/vendor/highcharts/highcharts.js` đã có), AdminLTE, jQuery.

## Global Constraints

- **PHP 7.4 / Laravel 5.5 / PHPUnit 6 là sàn cứng.** Không cú pháp PHP 8. Không `: void` trên `setUp()`.
- **Không có `Request::boolean()`.** Dùng `filter_var($x, FILTER_VALIDATE_BOOLEAN)`.
- **Không viết bản SQL thứ ba cho luật trạng thái.** Dùng `CtdtDanhSach::truyVan(['trang_thai_gui' => $ma])`.
- **Không viết lại luật "đã kiểm và sạch".** Chỉ `CtdtQuyetDinhGui::nenKy($daKiem, $soLoi)`.
- **Nhãn tiếng Việt của trạng thái chỉ lấy từ `CtdtTrangThaiGui::nhan()`.** Không gõ lại.
- **Highcharts nạp từ `asset('vendor/highcharts/highcharts.js')`**, không dùng CDN — máy chủ bệnh viện không ra được Internet.
- ⛔ **TUYỆT ĐỐI KHÔNG dùng `RefreshDatabase` hay `DatabaseMigrations`.** Hai trait đó gọi `migrate:fresh` — `DROP` toàn bộ bảng. Ngày 2026-08-21 chuyện này đã xảy ra thật và xoá sạch CSDL phát triển `qlbv`. Dùng `Tests\Support\DungBangCtdtSqlite` thay thế; `ChotAnToanCsdlTest` sẽ đỏ nếu ai dùng lại hai trait đó.
- **Máy phát triển này đã bật `submit_enabled` và đã gửi thật.** Không chạy `SignCtdtJob`, `SubmitCtdtJob`, không chạy worker hàng đợi.
- **Baseline test:** `tests/Unit/Ctdt` → **545 test, đỏ đúng một** — `CtdtCauHinhTest::gui_len_cong_mac_dinh_tat` (đỏ có chủ đích, đừng đụng).
- Chú thích trong mã viết **không dấu**, tài liệu Markdown viết **có dấu**.

## File Structure

| Tệp | Trách nhiệm |
|---|---|
| `app/Services/Dashboard/CtdtDashboardService.php` | Toàn bộ truy vấn của ba khối |
| `app/Http/Controllers/Dashboard/CtdtDashboardController.php` | Controller mỏng, trả JSON |
| `resources/views/dashboard/ctdt.blade.php` | Khung màn hình |
| `public/js/dashboard/ctdt.js` | Vẽ biểu đồ |
| `routes/web.php` | *(sửa)* một route trang + ba route dữ liệu |

---

### Task 1: Khối sức khoẻ vận hành

**Vì sao khối này đi trước:** đây là thứ bắt được worker chết — vấn đề đã nêu đi nêu lại suốt bốn giai đoạn. Cột "Số lỗi" bằng `0` khi worker `JobCtdt` không chạy trông y hệt như mọi hồ sơ đều sạch. Hai khối còn lại chỉ là báo cáo; khối này là báo động.

**Files:**
- Create: `app/Services/Dashboard/CtdtDashboardService.php`
- Create: `app/Http/Controllers/Dashboard/CtdtDashboardController.php`
- Create: `resources/views/dashboard/ctdt.blade.php`
- Create: `public/js/dashboard/ctdt.js`
- Modify: `routes/web.php` (cạnh nhóm `dashboard/xml3176`, khoảng dòng 179)
- Test: `tests/Unit/Ctdt/CtdtDashboardTest.php`

**Interfaces:**
- Consumes: `CtdtDanhSach::truyVan(array $loc)` → `Builder`; `CtdtTrangThaiGui::NHAN` (mảng mã ⇒ nhãn), `::nhan($ma)`; `CtdtHangDoi::kiem()`, `::ky()`, `::gui()`
- Produces:
  - `CtdtDashboardService::sucKhoe(array $loc)` → mảng ba khoá:
    - `theo_trang_thai`: mảng các `['ma' => string, 'nhan' => string, 'so_luong' => int]`, đủ cả 9 trạng thái kể cả số 0
    - `hang_doi`: mảng các `['ten' => string, 'so_job' => int|null]` — `null` nghĩa là không đếm được
    - `ton_dong`: `['so_ho_so' => int, 'cu_nhat_ngay' => int|null]`
  - `CtdtDashboardController::index()` → view `dashboard.ctdt`; `::sucKhoe(Request)` → JSON
  - Route `bhyt.ctdt.dashboard` và `bhyt.ctdt.dashboard.suc-khoe`

- [ ] **Step 1: Viết test đỏ trước**

Tạo `tests/Unit/Ctdt/CtdtDashboardTest.php`:

```php
<?php

namespace Tests\Unit\Ctdt;

use Tests\TestCase;
use Tests\Support\DungBangCtdtSqlite;
use App\Models\BHYT\Ctdt\CtdtHoSo;
use App\Services\Ctdt\CtdtTrangThaiGui;
use App\Services\Dashboard\CtdtDashboardService;

/**
 * Cot "So loi" bang 0 khi worker JobCtdt khong chay trong Y HET nhu moi ho so deu sach.
 * Man hinh nay la thu duy nhat bat duoc chuyen do - nen chinh no khong duoc noi doi.
 */
class CtdtDashboardTest extends TestCase
{
    // KHONG DatabaseMigrations: trait do goi migrate:fresh, tuc DROP toan bo bang cua CSDL
    // phat trien. Da xay ra that ngay 2026-08-21.
    use DungBangCtdtSqlite;

    /**
     * BAT BUOC: trait chi CUNG CAP ham dung bang, no khong tu chay. Quen goi
     * chuanBiBangCtdt() thi test ghi thang vao CSDL that ma phpunit.xml dang tro toi.
     */
    protected function setUp()
    {
        parent::setUp();
        $this->chuanBiBangCtdt();
    }

    private function hoSo(array $ghiDe = [])
    {
        return CtdtHoSo::create(array_merge([
            'ma_ho_so'    => 'YT' . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT),
            'dich_vu'     => 'CT2025',
            'loai_hs'     => '39',
            'macskcb'     => '01001',
            'imported_at' => '2026-08-20 08:00:00',
            'so_loi'      => 0,
        ], $ghiDe));
    }

    /** @test */
    public function liet_ke_DU_CHIN_trang_thai_ke_ca_cai_bang_khong()
    {
        // Trang thai bien mat khoi bieu do khi bang 0 la trang thai khong ai theo doi duoc.
        // "Ky so that bai: 0" la mot thong tin; mot cot vang la mot cau hoi.
        $kq = (new CtdtDashboardService())->sucKhoe([]);

        $this->assertCount(
            count(CtdtTrangThaiGui::NHAN),
            $kq['theo_trang_thai'],
            'Phai liet ke du moi trang thai, ke ca cai dang bang 0'
        );
    }

    /** @test */
    public function dem_theo_trang_thai_khop_voi_CtdtTrangThaiGui()
    {
        // Man hinh bao mot dang, bo loc ngay ben canh bao mot neo la nguoi dung thoi tin ca
        // hai. Day la ly do khong duoc viet ban SQL thu ba cho luat trang thai.
        $this->hoSo(['checked_at' => null]);                                   // CHUA_KIEM
        $this->hoSo(['checked_at' => '2026-08-20 08:00:00', 'so_loi' => 3]);   // CON_LOI

        $kq = (new CtdtDashboardService())->sucKhoe([]);
        $dem = [];

        foreach ($kq['theo_trang_thai'] as $dong) {
            $dem[$dong['ma']] = $dong['so_luong'];
        }

        $this->assertSame(1, $dem[CtdtTrangThaiGui::CHUA_KIEM]);
        $this->assertSame(1, $dem[CtdtTrangThaiGui::CON_LOI]);
    }

    /** @test */
    public function nhan_lay_tu_CtdtTrangThaiGui_chu_khong_go_lai()
    {
        $kq = (new CtdtDashboardService())->sucKhoe([]);

        foreach ($kq['theo_trang_thai'] as $dong) {
            $this->assertSame(
                CtdtTrangThaiGui::nhan($dong['ma']),
                $dong['nhan'],
                'Nhan phai lay tu CtdtTrangThaiGui, khong duoc go lai'
            );
        }
    }

    /** @test */
    public function liet_ke_du_ba_hang_doi()
    {
        // Thieu worker nao thi moi ho so dung khung o buoc do. Ba hang doi deu BAT BUOC, nen
        // ca ba deu phai co mat tren man hinh - ke ca khi khong dem duoc.
        $kq = (new CtdtDashboardService())->sucKhoe([]);

        $this->assertCount(3, $kq['hang_doi']);
    }

    /** @test */
    public function khong_dem_duoc_hang_doi_thi_tra_null_chu_KHONG_tra_0()
    {
        // Hang doi dung driver khac 'database' thi khong co bang jobs de dem. Tra 0 o day la
        // NOI DOI: nguoi doc se thay "0 job dang cho" va yen tam, trong khi that ra man hinh
        // khong biet gi ca.
        config(['queue.default' => 'sync']);

        $kq = (new CtdtDashboardService())->sucKhoe([]);

        foreach ($kq['hang_doi'] as $hd) {
            $this->assertNull($hd['so_job'],
                'Khong dem duoc phai tra null, khong duoc tra 0');
        }
    }

    /** @test */
    public function ton_dong_dem_ho_so_chua_len_duoc_cong()
    {
        // "Ho so cu nhat chua gui da nam bao lau" la cau hoi bat duoc mot hang doi chet
        // cham - thu ma bieu do so luong khong bao gio chi ra.
        $this->hoSo(['checked_at' => null, 'imported_at' => '2026-08-01 08:00:00']);

        $kq = (new CtdtDashboardService())->sucKhoe([]);

        $this->assertSame(1, $kq['ton_dong']['so_ho_so']);
        $this->assertNotNull($kq['ton_dong']['cu_nhat_ngay']);
    }
}
```

- [ ] **Step 2: Chạy test cho chắc nó đỏ**

Chạy: `php vendor/bin/phpunit tests/Unit/Ctdt/CtdtDashboardTest.php`
Kỳ vọng: ĐỎ với `Class 'App\Services\Dashboard\CtdtDashboardService' not found`.

- [ ] **Step 3: Viết service (phần sức khoẻ)**

Tạo `app/Services/Dashboard/CtdtDashboardService.php`:

```php
<?php

namespace App\Services\Dashboard;

use Illuminate\Support\Facades\DB;
use App\Models\BHYT\Ctdt\CtdtHoSo;
use App\Services\Ctdt\CtdtDanhSach;
use App\Services\Ctdt\CtdtHangDoi;
use App\Services\Ctdt\CtdtTrangThaiGui;

/**
 * Truy van cho man hinh dashboard chung tu dien tu.
 *
 * NGUYEN TAC: khong lop nay viet lai bat ky luat nao da co noi khac. Dem theo trang thai thi
 * goi lai CtdtDanhSach::truyVan(['trang_thai_gui' => ...]) - ban SQL da duoc mot test tinh
 * chat rang voi CtdtTrangThaiGui::cua(). Viet ban SQL thu ba o day la tao ra mot man hinh
 * bao so khac voi chinh bo loc ngay ben canh no, va nguoi dung se thoi tin ca hai.
 *
 * Chin truy van dem thay vi mot cau GROUP BY: doi lay viec KHONG chep luat. Ca chin deu
 * chay tren cot co index (checked_at, so_loi, is_signed, ma_ket_qua, submit_error).
 */
class CtdtDashboardService
{
    /**
     * @param  array $loc cung dinh dang bo loc cua CtdtDanhSach::truyVan()
     * @return array theo_trang_thai, hang_doi, ton_dong
     */
    public function sucKhoe(array $loc)
    {
        return [
            'theo_trang_thai' => $this->demTheoTrangThai($loc),
            'hang_doi'        => $this->doSauHangDoi(),
            'ton_dong'        => $this->tonDong($loc),
        ];
    }

    /**
     * Liet ke DU moi trang thai, ke ca cai dang bang 0.
     *
     * Trang thai bien mat khoi bieu do khi bang 0 la trang thai khong ai theo doi duoc:
     * "Ky so that bai: 0" la mot thong tin, mot cot vang la mot cau hoi.
     */
    protected function demTheoTrangThai(array $loc)
    {
        $ket = [];

        foreach (array_keys(CtdtTrangThaiGui::NHAN) as $ma) {
            $ket[] = [
                'ma'       => $ma,
                'nhan'     => CtdtTrangThaiGui::nhan($ma),
                'so_luong' => (int) CtdtDanhSach::truyVan(
                    array_merge($loc, ['trang_thai_gui' => $ma])
                )->count(),
            ];
        }

        return $ket;
    }

    /**
     * Do sau ba hang doi.
     *
     * Tra null - KHONG phai 0 - khi khong dem duoc. Tra 0 la noi doi: nguoi doc se thay
     * "0 job dang cho" va yen tam, trong khi that ra man hinh khong biet gi ca.
     */
    protected function doSauHangDoi()
    {
        $ten = [CtdtHangDoi::kiem(), CtdtHangDoi::ky(), CtdtHangDoi::gui()];
        $demDuoc = config('queue.default') === 'database';

        $ket = [];

        foreach ($ten as $hd) {
            $soJob = null;

            if ($demDuoc) {
                try {
                    $soJob = (int) DB::table('jobs')->where('queue', $hd)->count();
                } catch (\Exception $e) {
                    // Bang jobs chua migrate - van la "khong dem duoc", khong phai "bang 0".
                    $soJob = null;
                }
            }

            $ket[] = ['ten' => $hd, 'so_job' => $soJob];
        }

        return $ket;
    }

    /**
     * Ho so da nap nhung chua len duoc cong, va cai cu nhat da nam bao nhieu ngay.
     *
     * "Cu nhat da nam bao lau" la cau hoi bat duoc mot hang doi chet CHAM - thu ma bieu do
     * so luong khong bao gio chi ra, vi so luong nap moi ngay van binh thuong.
     */
    protected function tonDong(array $loc)
    {
        $q = CtdtDanhSach::truyVan($loc)
            ->where(function ($q2) {
                $q2->whereNull('ma_ket_qua')->orWhere('ma_ket_qua', '');
            });

        $soHoSo = (int) $q->count();

        if ($soHoSo === 0) {
            return ['so_ho_so' => 0, 'cu_nhat_ngay' => null];
        }

        $cuNhat = CtdtDanhSach::truyVan($loc)
            ->where(function ($q2) {
                $q2->whereNull('ma_ket_qua')->orWhere('ma_ket_qua', '');
            })
            ->min('imported_at');

        return [
            'so_ho_so'     => $soHoSo,
            'cu_nhat_ngay' => $cuNhat === null
                ? null
                : (int) now()->diffInDays(\Carbon\Carbon::parse($cuNhat)),
        ];
    }
}
```

- [ ] **Step 4: Chạy test cho chắc nó xanh**

Chạy: `php vendor/bin/phpunit tests/Unit/Ctdt/CtdtDashboardTest.php`
Kỳ vọng: `OK (6 tests)`.

- [ ] **Step 5: Viết controller**

Tạo `app/Http/Controllers/Dashboard/CtdtDashboardController.php`:

```php
<?php

namespace App\Http\Controllers\Dashboard;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\Dashboard\CtdtDashboardService;

/**
 * Controller MONG: moi truy van nam trong CtdtDashboardService. Xem chu thich lop do ve vi
 * sao khong tu viet SQL trang thai.
 */
class CtdtDashboardController extends Controller
{
    /** @var CtdtDashboardService */
    protected $service;

    public function __construct(CtdtDashboardService $service)
    {
        $this->service = $service;
    }

    public function index()
    {
        return view('dashboard.ctdt');
    }

    public function sucKhoe(Request $request)
    {
        return response()->json($this->service->sucKhoe($this->locTu($request)));
    }

    /**
     * Bo loc dung DUNG dinh dang cua CtdtDanhSach::truyVan(), de man dashboard va man danh
     * sach luon noi cung mot thu.
     *
     * @return array
     */
    protected function locTu(Request $request)
    {
        return [
            'tu_ngay'  => $request->input('tu_ngay'),
            'den_ngay' => $request->input('den_ngay'),
            'dich_vu'  => $request->input('dich_vu'),
            'macskcb'  => $request->input('macskcb'),
        ];
    }
}
```

- [ ] **Step 6: Thêm route**

Trong `routes/web.php`, cạnh nhóm `dashboard/xml3176` (khoảng dòng 179), theo đúng middleware của nhóm đó:

```php
        Route::get('dashboard/ctdt', 'Dashboard\CtdtDashboardController@index')
            ->name('bhyt.ctdt.dashboard');
        Route::get('dashboard/ctdt/suc-khoe', 'Dashboard\CtdtDashboardController@sucKhoe')
            ->name('bhyt.ctdt.dashboard.suc-khoe');
```

- [ ] **Step 7: Viết khung màn hình**

Tạo `resources/views/dashboard/ctdt.blade.php`:

```blade
@extends('adminlte::page')

@section('title', 'Dashboard chứng từ điện tử')

@section('content_header')
<h1>Dashboard chứng từ điện tử <small>PL02 &mdash; sức khoẻ vận hành</small></h1>
@endsection

@push('after-styles')
<style>
    .filter-row { margin-bottom: 15px; }
    .chart-box  { min-height: 350px; }
    .hang-doi-chet { color: #dd4b39; font-weight: bold; }
</style>
@endpush

@section('content')
<div class="row filter-row">
    <div class="col-md-2">
        <label>Từ ngày</label>
        <input type="date" id="tu-ngay" class="form-control" value="{{ date('Y-m-01') }}">
    </div>
    <div class="col-md-2">
        <label>Đến ngày</label>
        <input type="date" id="den-ngay" class="form-control" value="{{ date('Y-m-d') }}">
    </div>
    <div class="col-md-2">
        <label>Dịch vụ</label>
        <select id="dich-vu" class="form-control">
            <option value="">Tất cả</option>
            <option value="CT2025">CT2025</option>
            <option value="GBT">GBT</option>
            <option value="GCS">GCS</option>
        </select>
    </div>
    <div class="col-md-2">
        <label>&nbsp;</label>
        <button id="btn-xem" class="btn btn-primary form-control">
            <i class="fa fa-search"></i> Xem
        </button>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="box box-danger">
            <div class="box-header with-border"><h3 class="box-title">Ba hàng đợi</h3></div>
            <div class="box-body" id="khoi-hang-doi">Đang tải…</div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="box box-primary">
            <div class="box-header with-border"><h3 class="box-title">Hồ sơ theo trạng thái</h3></div>
            <div class="box-body"><div id="chart-trang-thai" class="chart-box"></div></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="box box-warning">
            <div class="box-header with-border"><h3 class="box-title">Tồn đọng</h3></div>
            <div class="box-body" id="khoi-ton-dong">Đang tải…</div>
        </div>
    </div>
</div>
@endsection

@push('after-scripts')
<script src="{{ asset('vendor/highcharts/highcharts.js') }}"></script>
<script>
    window.CTDT_DASHBOARD_CFG = {
        routes: {
            sucKhoe: '{{ route('bhyt.ctdt.dashboard.suc-khoe') }}'
        }
    };
</script>
<script src="{{ asset('js/dashboard/ctdt.js') }}"></script>
@endpush
```

⚠️ **Không đặt `@if`, `@foreach` hay `{{ }}` bên trong chú thích JavaScript.** Blade biên dịch trước, không biết gì về chú thích JS — lỗi này đã làm `detail.blade.php` chết suốt từ Giai đoạn 2B mà không ai biết, vì trang chỉ vỡ khi có người mở nó.

✅ **Tên stack đã kiểm:** `xml3176.blade.php` dùng `@push('after-styles')` cho CSS và `@push('after-scripts')` cho JS. Dùng đúng hai tên đó.

- [ ] **Step 8: Viết JS**

Tạo `public/js/dashboard/ctdt.js`:

```javascript
(function (win, $) {
    'use strict';

    Highcharts.setOptions({ accessibility: { enabled: false } });

    var CFG = win.CTDT_DASHBOARD_CFG || {};
    var R = CFG.routes || {};

    function thamSo() {
        return {
            tu_ngay: $('#tu-ngay').val(),
            den_ngay: $('#den-ngay').val(),
            dich_vu: $('#dich-vu').val()
        };
    }

    function veHangDoi(ds) {
        var html = '<div class="row">';

        $.each(ds, function (i, hd) {
            var soJob;

            if (hd.so_job === null) {
                // Khong dem duoc KHAC voi bang 0. Bao "0 job" o day la noi doi voi nguoi doc.
                soJob = '<span class="text-muted">không đếm được</span>';
            } else if (hd.so_job > 0) {
                soJob = '<span class="hang-doi-chet">' + hd.so_job + ' job đang chờ</span>';
            } else {
                soJob = '<span class="text-green">trống</span>';
            }

            html += '<div class="col-md-4"><strong>' + hd.ten + '</strong><br>' + soJob + '</div>';
        });

        $('#khoi-hang-doi').html(html + '</div>'
            + '<p class="text-muted" style="margin-top:10px">'
            + 'Hàng đợi đầy mà không vơi nghĩa là worker của nó chưa chạy. Cả ba đều bắt buộc.'
            + '</p>');
    }

    function veTrangThai(ds) {
        Highcharts.chart('chart-trang-thai', {
            chart: { type: 'bar' },
            title: { text: null },
            xAxis: { categories: $.map(ds, function (d) { return d.nhan; }) },
            yAxis: { title: { text: 'Số hồ sơ' }, allowDecimals: false },
            legend: { enabled: false },
            credits: { enabled: false },
            series: [{
                name: 'Hồ sơ',
                data: $.map(ds, function (d) { return d.so_luong; })
            }]
        });
    }

    function veTonDong(t) {
        if (!t.so_ho_so) {
            $('#khoi-ton-dong').html('<p class="text-green">Không có hồ sơ tồn đọng.</p>');
            return;
        }

        $('#khoi-ton-dong').html(
            '<h3>' + t.so_ho_so + '</h3>'
            + '<p>hồ sơ đã nạp nhưng chưa lên được cổng.</p>'
            + (t.cu_nhat_ngay === null ? ''
                : '<p>Cái cũ nhất đã nằm <strong>' + t.cu_nhat_ngay + ' ngày</strong>.</p>')
        );
    }

    function tai() {
        $.getJSON(R.sucKhoe, thamSo())
            .done(function (kq) {
                veHangDoi(kq.hang_doi);
                veTrangThai(kq.theo_trang_thai);
                veTonDong(kq.ton_dong);
            })
            .fail(function () {
                $('#khoi-hang-doi').html('<div class="text-danger">Không tải được dữ liệu</div>');
            });
    }

    $(function () {
        $('#btn-xem').on('click', tai);
        tai();
    });
})(window, jQuery);
```

- [ ] **Step 9: Kiểm blade biên dịch và chạy cả bộ test**

Chạy: `php vendor/bin/phpunit tests/Unit/Ctdt`
Kỳ vọng: đỏ **đúng một** — `CtdtCauHinhTest::gui_len_cong_mac_dinh_tat`.

`CtdtBladeCompilesTest` chỉ quét `resources/views/bhyt/ctdt/`. Tệp mới nằm ở `resources/views/dashboard/`, nên nó **không** được quét. Thêm một test riêng vào `tests/Unit/Ctdt/CtdtDashboardTest.php`:

```php
    /** @test */
    public function blade_dashboard_bien_dich_duoc()
    {
        // Trang blade chi vo khi co nguoi MO no. detail.blade.php da chet suot tu Giai doan
        // 2B vi mot @if lot vao trong chu thich JavaScript, va khong test nao bat duoc.
        $noiDung = file_get_contents(resource_path('views/dashboard/ctdt.blade.php'));

        \Illuminate\Support\Facades\Blade::compileString($noiDung);

        $this->assertTrue(true, 'compileString() nem thi test do o dong tren');
    }
```

- [ ] **Step 10: Kiểm bằng mắt**

Mở `/dashboard/ctdt`. Kỳ vọng: ba hàng đợi hiện đúng tên (`JobCtdt`, `JobSignCtdt`, `JobSubmitCtdt`), biểu đồ trạng thái có đủ 9 thanh (kể cả thanh bằng 0), khối tồn đọng có số.

Đối chiếu: tổng các thanh trong biểu đồ phải bằng tổng số hồ sơ trên màn danh sách với cùng bộ lọc. Lệch nghĩa là một trạng thái đang bị đếm hai lần hoặc bị bỏ sót.

- [ ] **Step 11: Đột biến bắt buộc**

Commit trước, rồi lần lượt (hoàn nguyên từng tệp một):
1. Trong `doSauHangDoi()`, đổi `$soJob = null;` thành `$soJob = 0;` → `khong_dem_duoc_hang_doi_thi_tra_null_chu_KHONG_tra_0` phải ĐỎ.
2. Trong `demTheoTrangThai()`, thêm `if ($soLuong === 0) { continue; }` để bỏ trạng thái rỗng → `liet_ke_DU_CHIN_trang_thai_ke_ca_cai_bang_khong` phải ĐỎ.

- [ ] **Step 12: Commit**

```bash
git add app/Services/Dashboard/CtdtDashboardService.php app/Http/Controllers/Dashboard/CtdtDashboardController.php resources/views/dashboard/ctdt.blade.php public/js/dashboard/ctdt.js routes/web.php tests/Unit/Ctdt/CtdtDashboardTest.php
git commit -m "feat(ctdt): dashboard khoi suc khoe van hanh"
```

---

### Task 2: Khối sản lượng theo thời gian

**Files:**
- Modify: `app/Services/Dashboard/CtdtDashboardService.php` (thêm `sanLuong()`)
- Modify: `app/Http/Controllers/Dashboard/CtdtDashboardController.php`, `routes/web.php`, `resources/views/dashboard/ctdt.blade.php`, `public/js/dashboard/ctdt.js`
- Test: `tests/Unit/Ctdt/CtdtDashboardTest.php` (thêm ca)

**Interfaces:**
- Consumes: `CtdtDanhSach::truyVan(array $loc)`
- Produces: `CtdtDashboardService::sanLuong(array $loc)` → `['ngay' => array<string>, 'chuoi' => array<['ten' => string, 'du_lieu' => array<int>]>]` — mỗi chuỗi là một dịch vụ; route `bhyt.ctdt.dashboard.san-luong`

- [ ] **Step 1: Viết test đỏ trước**

Thêm vào `tests/Unit/Ctdt/CtdtDashboardTest.php`:

```php
    /** @test */
    public function san_luong_khong_bo_trong_ngay_khong_co_ho_so()
    {
        // GROUP BY chi tra ve ngay CO du lieu. Ve thang len bieu do duong thi mot ngay he
        // thong chet hoan toan se bien mat khoi truc - duong noi lien tu ngay truoc sang
        // ngay sau, trong y het nhu khong co gi xay ra.
        $this->hoSo(['imported_at' => '2026-08-01 08:00:00']);
        $this->hoSo(['imported_at' => '2026-08-03 08:00:00']);

        $kq = (new CtdtDashboardService())->sanLuong([
            'tu_ngay' => '2026-08-01', 'den_ngay' => '2026-08-03',
        ]);

        $this->assertSame(['2026-08-01', '2026-08-02', '2026-08-03'], $kq['ngay'],
            'Ngay khong co ho so van phai co mat, voi gia tri 0');
    }

    /** @test */
    public function san_luong_tach_theo_dich_vu()
    {
        // Ba dich vu CT2025 / GBT / GCS di ba duong khac nhau len cong. Gop chung mot duong
        // thi mot dich vu chet han cung khong nhin ra.
        $this->hoSo(['dich_vu' => 'CT2025', 'imported_at' => '2026-08-01 08:00:00']);
        $this->hoSo(['dich_vu' => 'GBT', 'imported_at' => '2026-08-01 09:00:00']);

        $kq = (new CtdtDashboardService())->sanLuong([
            'tu_ngay' => '2026-08-01', 'den_ngay' => '2026-08-01',
        ]);

        $ten = array_column($kq['chuoi'], 'ten');

        $this->assertContains('CT2025', $ten);
        $this->assertContains('GBT', $ten);
    }

    /** @test */
    public function san_luong_do_dai_moi_chuoi_bang_so_ngay()
    {
        // Lech mot phan tu la moi diem tu do tro di roi sai ngay tren truc - va bieu do van
        // trong hoan toan binh thuong.
        $this->hoSo(['imported_at' => '2026-08-02 08:00:00']);

        $kq = (new CtdtDashboardService())->sanLuong([
            'tu_ngay' => '2026-08-01', 'den_ngay' => '2026-08-05',
        ]);

        foreach ($kq['chuoi'] as $chuoi) {
            $this->assertCount(count($kq['ngay']), $chuoi['du_lieu']);
        }
    }
```

- [ ] **Step 2: Chạy test cho chắc nó đỏ**

Chạy: `php vendor/bin/phpunit tests/Unit/Ctdt/CtdtDashboardTest.php`
Kỳ vọng: ba test mới ĐỎ với `Call to undefined method ...::sanLuong()`.

- [ ] **Step 3: Viết `sanLuong()`**

Thêm vào `app/Services/Dashboard/CtdtDashboardService.php`:

```php
    /**
     * So ho so nap moi ngay, tach theo dich vu.
     *
     * DUNG KHUNG NGAY DAY DU chu khong chi nhung ngay co du lieu: GROUP BY chi tra ve ngay
     * CO ban ghi, va ve thang len bieu do duong thi mot ngay he thong chet hoan toan se bien
     * mat khoi truc - duong noi lien tu ngay truoc sang ngay sau, trong y het nhu khong co
     * gi xay ra.
     *
     * @param  array $loc
     * @return array ngay, chuoi
     */
    public function sanLuong(array $loc)
    {
        $tuNgay  = !empty($loc['tu_ngay']) ? $loc['tu_ngay'] : now()->subDays(29)->format('Y-m-d');
        $denNgay = !empty($loc['den_ngay']) ? $loc['den_ngay'] : now()->format('Y-m-d');

        $ngay = $this->khungNgay($tuNgay, $denNgay);

        $tho = CtdtDanhSach::truyVan(array_merge($loc, [
                'tu_ngay' => $tuNgay, 'den_ngay' => $denNgay,
            ]))
            ->select(
                'dich_vu',
                DB::raw('DATE(imported_at) as ngay'),
                DB::raw('COUNT(*) as so_luong')
            )
            ->groupBy('dich_vu', DB::raw('DATE(imported_at)'))
            ->get();

        // Gom ve dang [dich_vu][ngay] => so_luong de tra cuu O(1) khi to khung.
        $bang = [];

        foreach ($tho as $dong) {
            $bang[(string) $dong->dich_vu][(string) $dong->ngay] = (int) $dong->so_luong;
        }

        $chuoi = [];

        foreach ($bang as $dichVu => $theoNgay) {
            $duLieu = [];

            foreach ($ngay as $n) {
                $duLieu[] = isset($theoNgay[$n]) ? $theoNgay[$n] : 0;
            }

            $chuoi[] = ['ten' => $dichVu, 'du_lieu' => $duLieu];
        }

        return ['ngay' => $ngay, 'chuoi' => $chuoi];
    }

    /**
     * Moi ngay tu $tuNgay den $denNgay, ke ca ngay khong co du lieu.
     *
     * @return array chuoi 'Y-m-d'
     */
    protected function khungNgay($tuNgay, $denNgay)
    {
        $moc = \Carbon\Carbon::parse($tuNgay)->startOfDay();
        $het = \Carbon\Carbon::parse($denNgay)->startOfDay();
        $ngay = [];

        // Tran cung 366 ngay: mot khoang ngay go nham (vd. 2020-2026) se sinh hang nghin
        // diem va lam trinh duyet dung hinh - tren may chu gioi han PHP 128MB thi con truoc
        // do nua.
        $dem = 0;

        while ($moc->lte($het) && $dem < 366) {
            $ngay[] = $moc->format('Y-m-d');
            $moc = $moc->copy()->addDay();
            $dem++;
        }

        return $ngay;
    }
```

⚠️ **`DATE(imported_at)` chạy được trên cả MySQL lẫn SQLite.** Nếu test đỏ vì định dạng ngày SQLite trả về kèm giờ, đổi sang so sánh bằng `substr((string) $dong->ngay, 0, 10)` khi dựng `$bang` — **đừng** đổi sang hàm riêng của MySQL, môi trường test dùng SQLite.

- [ ] **Step 4: Chạy test cho chắc nó xanh**

Chạy: `php vendor/bin/phpunit tests/Unit/Ctdt/CtdtDashboardTest.php`
Kỳ vọng: `OK (10 tests)`.

- [ ] **Step 5: Nối controller, route, view, JS**

Controller:

```php
    public function sanLuong(Request $request)
    {
        return response()->json($this->service->sanLuong($this->locTu($request)));
    }
```

`routes/web.php`:

```php
        Route::get('dashboard/ctdt/san-luong', 'Dashboard\CtdtDashboardController@sanLuong')
            ->name('bhyt.ctdt.dashboard.san-luong');
```

Trong blade, thêm route vào `window.CTDT_DASHBOARD_CFG.routes`:

```blade
            sanLuong: '{{ route('bhyt.ctdt.dashboard.san-luong') }}',
```

và thêm một khối biểu đồ:

```blade
<div class="row">
    <div class="col-md-12">
        <div class="box box-info">
            <div class="box-header with-border"><h3 class="box-title">Sản lượng theo ngày</h3></div>
            <div class="box-body"><div id="chart-san-luong" class="chart-box"></div></div>
        </div>
    </div>
</div>
```

Trong `ctdt.js`, thêm hàm và gọi trong `tai()`:

```javascript
    function veSanLuong(kq) {
        Highcharts.chart('chart-san-luong', {
            chart: { type: 'line' },
            title: { text: null },
            xAxis: { categories: kq.ngay },
            yAxis: { title: { text: 'Số hồ sơ' }, allowDecimals: false },
            credits: { enabled: false },
            series: $.map(kq.chuoi, function (c) {
                return { name: c.ten, data: c.du_lieu };
            })
        });
    }
```

```javascript
        $.getJSON(R.sanLuong, thamSo()).done(veSanLuong);
```

- [ ] **Step 6: Đột biến bắt buộc**

Commit trước, rồi trong `sanLuong()` thay `$ngay` bằng `array_keys($theoNgay)` (chỉ ngày có dữ liệu) → `san_luong_khong_bo_trong_ngay_khong_co_ho_so` phải ĐỎ. Hoàn nguyên bằng `git checkout -- app/Services/Dashboard/CtdtDashboardService.php`.

- [ ] **Step 7: Commit**

```bash
git add app/Services/Dashboard/CtdtDashboardService.php app/Http/Controllers/Dashboard/CtdtDashboardController.php routes/web.php resources/views/dashboard/ctdt.blade.php public/js/dashboard/ctdt.js tests/Unit/Ctdt/CtdtDashboardTest.php
git commit -m "feat(ctdt): dashboard khoi san luong theo ngay"
```

---

### Task 3: Khối chất lượng dữ liệu

**Files:**
- Modify: `app/Services/Dashboard/CtdtDashboardService.php` (thêm `chatLuong()`)
- Modify: `app/Http/Controllers/Dashboard/CtdtDashboardController.php`, `routes/web.php`, `resources/views/dashboard/ctdt.blade.php`, `public/js/dashboard/ctdt.js`
- Modify: `docs/chung-tu-dien-tu-pl02.md`
- Test: `tests/Unit/Ctdt/CtdtDashboardTest.php` (thêm ca)

**Interfaces:**
- Consumes: bảng `ctdt_loi` (`ho_so_id`, `ma_loi`, `ten_truong`, `muc_do`), `CtdtDanhSach::truyVan(array $loc)`
- Produces: `CtdtDashboardService::chatLuong(array $loc, $soDong = 15)` → `['theo_ma_loi' => array<['ma_loi'=>string,'ten_truong'=>string,'muc_do'=>string,'so_luong'=>int]>, 'theo_cskcb' => array<['macskcb'=>string,'so_loi'=>int,'so_ho_so'=>int]>]`; route `bhyt.ctdt.dashboard.chat-luong`

- [ ] **Step 1: Viết test đỏ trước**

Thêm vào `tests/Unit/Ctdt/CtdtDashboardTest.php`:

```php
    /** @test */
    public function chat_luong_khong_gop_chan_voi_canh_bao_vao_mot_hang()
    {
        // Mot ma loi muc CANH BAO xep tren mot ma loi muc CHAN se dua nguoi ta di sua sai
        // cho: canh bao khong chan ho so nao ca, con chan thi co.
        $kq = (new CtdtDashboardService())->chatLuong([]);

        $this->assertArrayHasKey('theo_ma_loi', $kq);

        foreach ($kq['theo_ma_loi'] as $dong) {
            $this->assertArrayHasKey('muc_do', $dong,
                'Moi hang phai noi ro muc do, khong duoc gop chung');
        }
    }

    /** @test */
    public function chat_luong_co_tran_so_dong()
    {
        // Xep hang khong tran thi mot he thong co 400 ma loi se do het ra bieu do va khong
        // ai doc duoc gi.
        $nguon = file_get_contents(base_path('app/Services/Dashboard/CtdtDashboardService.php'));

        $this->assertContains('limit(', $nguon,
            'Xep hang ma loi phai co tran so dong');
    }
```

- [ ] **Step 2: Chạy test cho chắc nó đỏ**

Chạy: `php vendor/bin/phpunit tests/Unit/Ctdt/CtdtDashboardTest.php`
Kỳ vọng: hai test mới ĐỎ với `Call to undefined method ...::chatLuong()`.

- [ ] **Step 3: Viết `chatLuong()`**

Thêm vào `app/Services/Dashboard/CtdtDashboardService.php`:

```php
    /**
     * Ma loi hay gap nhat, va co so nao sai nhieu nhat.
     *
     * TACH muc chan khoi muc canh bao trong tung hang: mot ma loi muc CANH BAO xep tren mot
     * ma loi muc CHAN se dua nguoi ta di sua sai cho - canh bao khong chan ho so nao ca,
     * con chan thi co.
     *
     * @param  array $loc
     * @param  int   $soDong tran so hang, tranh do ca tram ma loi ra bieu do
     * @return array theo_ma_loi, theo_cskcb
     */
    public function chatLuong(array $loc, $soDong = 15)
    {
        // Lay danh sach id ho so tu bo loc man hinh roi moi join sang bang loi: bo loc la bo
        // loc theo HO SO (ngay nap, dich vu, co so), khong ap thang len ctdt_loi duoc.
        $idHoSo = CtdtDanhSach::truyVan($loc)->select('ctdt_ho_so.id');

        $theoMaLoi = DB::table('ctdt_loi')
            ->whereIn('ho_so_id', $idHoSo)
            ->select(
                'ma_loi',
                'ten_truong',
                'muc_do',
                DB::raw('COUNT(*) as so_luong')
            )
            ->groupBy('ma_loi', 'ten_truong', 'muc_do')
            ->orderByDesc('so_luong')
            ->limit($soDong)
            ->get();

        $theoCskcb = CtdtDanhSach::truyVan($loc)
            ->where('so_loi', '>', 0)
            ->select(
                'macskcb',
                DB::raw('SUM(so_loi) as so_loi'),
                DB::raw('COUNT(*) as so_ho_so')
            )
            ->groupBy('macskcb')
            ->orderByDesc(DB::raw('SUM(so_loi)'))
            ->limit($soDong)
            ->get();

        return [
            'theo_ma_loi' => array_map(function ($d) {
                return [
                    'ma_loi'     => (string) $d->ma_loi,
                    'ten_truong' => (string) $d->ten_truong,
                    'muc_do'     => (string) $d->muc_do,
                    'so_luong'   => (int) $d->so_luong,
                ];
            }, $theoMaLoi->all()),

            'theo_cskcb' => array_map(function ($d) {
                return [
                    'macskcb'  => (string) $d->macskcb,
                    'so_loi'   => (int) $d->so_loi,
                    'so_ho_so' => (int) $d->so_ho_so,
                ];
            }, $theoCskcb->all()),
        ];
    }
```

⚠️ **`whereIn` với một truy vấn con cần `select()` chỉ định rõ cột.** Nếu `CtdtDanhSach::truyVan()` đã gắn `with()` hay chọn nhiều cột thì truy vấn con sẽ trả nhiều cột và MySQL sẽ ném. Chạy thử trước; nếu ném thì đổi sang lấy mảng id: `$idHoSo = CtdtDanhSach::truyVan($loc)->pluck('id')->all();` — **nhưng** khi đó phải chặn trần số phần tử (ví dụ 20000) và ghi rõ trong chú thích rằng vượt trần là cắt, không được cắt im lặng.

- [ ] **Step 4: Chạy test cho chắc nó xanh**

Chạy: `php vendor/bin/phpunit tests/Unit/Ctdt/CtdtDashboardTest.php`
Kỳ vọng: `OK (12 tests)`.

- [ ] **Step 5: Nối controller, route, view, JS**

Controller:

```php
    public function chatLuong(Request $request)
    {
        return response()->json($this->service->chatLuong($this->locTu($request)));
    }
```

`routes/web.php`:

```php
        Route::get('dashboard/ctdt/chat-luong', 'Dashboard\CtdtDashboardController@chatLuong')
            ->name('bhyt.ctdt.dashboard.chat-luong');
```

Blade — thêm route vào `CTDT_DASHBOARD_CFG.routes` (`chatLuong: '{{ route('bhyt.ctdt.dashboard.chat-luong') }}'`) và hai khối:

```blade
<div class="row">
    <div class="col-md-7">
        <div class="box box-danger">
            <div class="box-header with-border"><h3 class="box-title">Mã lỗi hay gặp</h3></div>
            <div class="box-body"><div id="chart-ma-loi" class="chart-box"></div></div>
        </div>
    </div>
    <div class="col-md-5">
        <div class="box box-default">
            <div class="box-header with-border"><h3 class="box-title">Cơ sở sai nhiều nhất</h3></div>
            <div class="box-body"><div id="chart-cskcb" class="chart-box"></div></div>
        </div>
    </div>
</div>
```

`ctdt.js`:

```javascript
    function veChatLuong(kq) {
        Highcharts.chart('chart-ma-loi', {
            chart: { type: 'bar' },
            title: { text: null },
            xAxis: {
                categories: $.map(kq.theo_ma_loi, function (d) {
                    // Ghep muc do vao nhan: mot ma loi CANH BAO xep tren mot ma loi CHAN se
                    // dua nguoi ta di sua sai cho.
                    return d.ma_loi + ' · ' + d.ten_truong
                        + (d.muc_do === 'chan' ? ' (chặn)' : ' (cảnh báo)');
                })
            },
            yAxis: { title: { text: 'Số lỗi' }, allowDecimals: false },
            legend: { enabled: false },
            credits: { enabled: false },
            series: [{
                name: 'Số lỗi',
                data: $.map(kq.theo_ma_loi, function (d) { return d.so_luong; })
            }]
        });

        Highcharts.chart('chart-cskcb', {
            chart: { type: 'column' },
            title: { text: null },
            xAxis: { categories: $.map(kq.theo_cskcb, function (d) { return d.macskcb; }) },
            yAxis: { title: { text: 'Số lỗi' }, allowDecimals: false },
            legend: { enabled: false },
            credits: { enabled: false },
            series: [{
                name: 'Số lỗi',
                data: $.map(kq.theo_cskcb, function (d) { return d.so_loi; })
            }]
        });
    }
```

```javascript
        $.getJSON(R.chatLuong, thamSo()).done(veChatLuong);
```

- [ ] **Step 6: Chạy cả bộ test**

Chạy: `php vendor/bin/phpunit tests/Unit/Ctdt`
Kỳ vọng: đỏ **đúng một** — `CtdtCauHinhTest::gui_len_cong_mac_dinh_tat`.

- [ ] **Step 7: Ghi vào tài liệu vận hành**

Thêm vào `docs/chung-tu-dien-tu-pl02.md`:

````markdown
## Dashboard `/dashboard/ctdt`

Ba khối, trả lời ba câu hỏi khác nhau:

| Khối | Câu hỏi | Đọc thế nào |
|---|---|---|
| Ba hàng đợi | Hệ thống có đang chạy không | Hàng đợi **đầy mà không vơi** = worker của nó chưa chạy. "không đếm được" nghĩa là hàng đợi không dùng driver `database` — **không** phải bằng 0. |
| Hồ sơ theo trạng thái | Hồ sơ đang kẹt ở đâu | Đủ **9 thanh**, kể cả thanh bằng 0. Tổng phải khớp màn danh sách với cùng bộ lọc. |
| Tồn đọng | Có gì kẹt lâu không | "Cái cũ nhất đã nằm N ngày" bắt được hàng đợi **chết chậm** — thứ mà biểu đồ sản lượng không chỉ ra, vì số nạp mỗi ngày vẫn bình thường. |
| Sản lượng theo ngày | Nạp/gửi được bao nhiêu | Ngày không có hồ sơ vẫn có mặt trên trục với giá trị 0. Một ngày hệ thống chết sẽ là một hố trên đường, không phải một đoạn bị nuốt mất. |
| Mã lỗi hay gặp | Đi sửa dữ liệu ở đâu | Nhãn có ghi **(chặn)** hay **(cảnh báo)**. Chỉ mã mức *chặn* mới thực sự giữ hồ sơ lại. |

Số đếm theo trạng thái dùng lại `CtdtDanhSach::truyVan(['trang_thai_gui' => ...])` — cùng bản
SQL với bộ lọc trên màn danh sách, nên hai màn hình không bao giờ nói khác nhau.
````

- [ ] **Step 8: Kiểm bằng mắt**

Mở `/dashboard/ctdt` với một khoảng ngày có dữ liệu thật. Kiểm: biểu đồ mã lỗi có nhãn "(chặn)"/"(cảnh báo)", và tổng số lỗi mức chặn khớp con số hồ sơ "Còn lỗi chặn" ở biểu đồ trạng thái theo hướng hợp lý (một hồ sơ nhiều lỗi nên tổng lỗi ≥ số hồ sơ).

- [ ] **Step 9: Đột biến bắt buộc**

Commit trước, rồi bỏ `'muc_do'` khỏi `groupBy` và khỏi mảng trả về → `chat_luong_khong_gop_chan_voi_canh_bao_vao_mot_hang` phải ĐỎ. Hoàn nguyên bằng `git checkout -- app/Services/Dashboard/CtdtDashboardService.php`.

- [ ] **Step 10: Commit**

```bash
git add app/Services/Dashboard/CtdtDashboardService.php app/Http/Controllers/Dashboard/CtdtDashboardController.php routes/web.php resources/views/dashboard/ctdt.blade.php public/js/dashboard/ctdt.js docs/chung-tu-dien-tu-pl02.md tests/Unit/Ctdt/CtdtDashboardTest.php
git commit -m "feat(ctdt): dashboard khoi chat luong du lieu"
```

---

## Việc phải nghiệm thu bằng tay

1. **Đối chiếu tổng.** Tổng chín thanh trạng thái phải bằng tổng số hồ sơ màn danh sách báo với cùng bộ lọc. Lệch nghĩa là một trạng thái bị đếm hai lần hoặc bị bỏ sót — và cũng nghĩa là test tính chất ràng `cua()` với `locTrangThai()` đang có lỗ.
2. **Dừng một worker rồi xem màn hình.** Tắt dịch vụ `QLBV JobCtdt`, nạp một tệp, mở dashboard. Hàng đợi `JobCtdt` phải hiện số job đang chờ và **không vơi**. Đây là kịch bản màn hình này sinh ra để bắt.
3. **Đo thời gian tải** trên dữ liệu thật. Chín truy vấn đếm cộng ba truy vấn nhóm; máy chủ mới giới hạn 120 giây. Nếu chậm, chỗ phải xử trước là `demTheoTrangThai()` — nhưng **đừng** thay bằng một câu `GROUP BY` tự viết: cách đúng là thêm chỉ mục, hoặc đưa phép đếm vào bộ nhớ đệm ngắn hạn.
