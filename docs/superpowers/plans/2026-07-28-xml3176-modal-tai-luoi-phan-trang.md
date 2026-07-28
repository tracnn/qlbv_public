# Modal chi tiết XML3176 — tải lười và phân trang server — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Mở modal chi tiết chỉ tải phần người dùng đang xem; bảng nhiều dòng phân trang phía server, cỡ trang 100.

**Architecture:** Vỏ modal dựng thanh tab từ `withCount` + `pluck` (6 truy vấn, không phụ thuộc số dòng). Hai endpoint mới nạp nội dung theo nhu cầu: một cho nội dung tab, một cho một trang của một bảng.

**Tech Stack:** Laravel 5.5, PHP 7.4, PHPUnit 6.5, Blade, jQuery + Bootstrap 3 tabs.

## Global Constraints

- Spec: `docs/superpowers/specs/2026-07-28-xml3176-modal-tai-luoi-phan-trang-design.md`
- Cổng test: **`vendor/bin/phpunit --testsuite Unit`** và chỉ suite này. Mốc: **272 test xanh**.
- **Mỗi task phải để lại ứng dụng chạy được.** Thứ tự dưới đây được chọn cho mục đích đó: endpoint và tải lười xong trước, tối ưu truy vấn của vỏ modal xong sau cùng.
- **Huy hiệu và ẩn/hiện tab phải giống hệt trước khi sửa.** Ba ngữ nghĩa đếm giữ nguyên: `demLoi` (XML1), `demTheoStt` (XML2–XML5), `demTheoXml` (XML7–XML15).
- Cách nhóm tab con giữ nguyên, kể cả **XML3 nhóm theo `ma_nhom`** chứ không theo ngày.
- Tham số `{xml}` đến từ URL: luôn đối chiếu danh sách trắng, không khớp thì `abort(404)`. Không ghép tên bảng/tên cột từ tham số.
- Comment mã nguồn viết tiếng Việt **không dấu**.
- Sau mỗi task: `php -l` file PHP đã sửa, chạy suite Unit, commit.

---

### Task 1: Đăng ký bảng nhiều dòng và hàm tách nhóm

**Files:**
- Create: `app/Services/Xml3176/Xml3176DetailTabs.php`
- Create: `tests/Unit/Xml3176/Xml3176DetailTabsTest.php`

**Interfaces:**
- Consumes: không có.
- Produces:
  ```php
  Xml3176DetailTabs::BANG_NHIEU_DONG          // mang cau hinh 4 bang
  Xml3176DetailTabs::cauHinh($xml): array     // abort(404) neu $xml ngoai danh sach trang
  Xml3176DetailTabs::laBangNhieuDong($xml): bool
  Xml3176DetailTabs::khoaNhom($giaTri, $cat): array
  ```

- [ ] **Step 1: Viết test (sẽ đỏ)**

Tạo `tests/Unit/Xml3176/Xml3176DetailTabsTest.php`:

```php
<?php

namespace Tests\Unit\Xml3176;

use Tests\TestCase;
use Illuminate\Support\Collection;
use App\Services\Xml3176\Xml3176DetailTabs;

class Xml3176DetailTabsTest extends TestCase
{
    /** @test */
    public function khoa_nhom_cat_dung_so_ky_tu_khu_trung_lap_va_sap_tang()
    {
        $kq = Xml3176DetailTabs::khoaNhom(
            ['202607031200', '202607011000', '202607031800', '202607021500'],
            8
        );

        $this->assertEquals(['20260701', '20260702', '20260703'], $kq);
    }

    /** @test */
    public function khoa_nhom_giu_nguyen_gia_tri_khi_cat_bang_khong()
    {
        // XML3 nhom theo ma_nhom, khong phai theo ngay -> khong cat.
        $kq = Xml3176DetailTabs::khoaNhom(['2', '1', '2', '10'], 0);

        $this->assertEquals(['1', '10', '2'], $kq);
    }

    /** @test */
    public function khoa_nhom_loai_gia_tri_rong_va_null()
    {
        $kq = Xml3176DetailTabs::khoaNhom(['20260701', null, '', '20260702', '   '], 8);

        $this->assertEquals(['20260701', '20260702'], $kq);
    }

    /** @test */
    public function khoa_nhom_nhan_collection_va_danh_so_lai_tu_khong()
    {
        $kq = Xml3176DetailTabs::khoaNhom(new Collection(['20260702', '20260701']), 8);

        $this->assertEquals([0, 1], array_keys($kq));
    }

    /** @test */
    public function khoa_nhom_tra_mang_rong_khi_khong_co_gia_tri()
    {
        $this->assertEquals([], Xml3176DetailTabs::khoaNhom([], 8));
    }

    /** @test */
    public function dang_ky_phu_dung_bon_bang_nhieu_dong()
    {
        $this->assertEquals(
            ['XML2', 'XML3', 'XML4', 'XML5'],
            array_keys(Xml3176DetailTabs::BANG_NHIEU_DONG)
        );

        // XML3 nhom theo ma_nhom, cat = 0. Day la khac biet de bi lam sai nhat.
        $this->assertEquals('ma_nhom', Xml3176DetailTabs::BANG_NHIEU_DONG['XML3']['cot_nhom']);
        $this->assertEquals(0, Xml3176DetailTabs::BANG_NHIEU_DONG['XML3']['cat']);

        foreach (['XML2' => 'ngay_yl', 'XML4' => 'ngay_kq', 'XML5' => 'thoi_diem_dbls'] as $xml => $cot) {
            $this->assertEquals($cot, Xml3176DetailTabs::BANG_NHIEU_DONG[$xml]['cot_nhom']);
            $this->assertEquals(8, Xml3176DetailTabs::BANG_NHIEU_DONG[$xml]['cat']);
        }
    }

    /** @test */
    public function la_bang_nhieu_dong_tu_choi_gia_tri_ngoai_danh_sach()
    {
        $this->assertTrue(Xml3176DetailTabs::laBangNhieuDong('XML2'));
        $this->assertFalse(Xml3176DetailTabs::laBangNhieuDong('XML7'));
        $this->assertFalse(Xml3176DetailTabs::laBangNhieuDong('../../etc'));
        $this->assertFalse(Xml3176DetailTabs::laBangNhieuDong(''));
    }

    /** @test */
    public function cau_hinh_nem_404_khi_xml_ngoai_danh_sach_trang()
    {
        $this->expectException(\Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class);

        Xml3176DetailTabs::cauHinh('XML999');
    }
}
```

- [ ] **Step 2: Chạy test, xác nhận đỏ**

Run: `vendor/bin/phpunit --testsuite Unit --filter Xml3176DetailTabsTest`
Expected: FAIL — `Class 'App\Services\Xml3176\Xml3176DetailTabs' not found`

- [ ] **Step 3: Viết lớp**

Tạo `app/Services/Xml3176/Xml3176DetailTabs.php`:

```php
<?php

namespace App\Services\Xml3176;

use App\Models\BHYT\Xml3176Xml2;
use App\Models\BHYT\Xml3176Xml3;
use App\Models\BHYT\Xml3176Xml4;
use App\Models\BHYT\Xml3176Xml5;

/**
 * Dang ky cac bang con nhieu dong cua man chi tiet XML3176.
 *
 * Bon bang nay duoc chia thanh tab con roi phan trang phia server. Cach nhom KHONG
 * dong nhat: XML2/4/5 nhom theo ngay (cat 8 ky tu dau cua cot thoi gian), rieng XML3
 * nhom theo ma_nhom (ma dich vu, giu nguyen gia tri).
 */
class Xml3176DetailTabs
{
    /** So dong moi trang. */
    const CO_TRANG = 100;

    const BANG_NHIEU_DONG = [
        'XML2' => ['model' => Xml3176Xml2::class, 'cot_nhom' => 'ngay_yl',        'cat' => 8],
        'XML3' => ['model' => Xml3176Xml3::class, 'cot_nhom' => 'ma_nhom',        'cat' => 0],
        'XML4' => ['model' => Xml3176Xml4::class, 'cot_nhom' => 'ngay_kq',        'cat' => 8],
        'XML5' => ['model' => Xml3176Xml5::class, 'cot_nhom' => 'thoi_diem_dbls', 'cat' => 8],
    ];

    public static function laBangNhieuDong($xml)
    {
        return is_string($xml) && isset(self::BANG_NHIEU_DONG[$xml]);
    }

    /**
     * Tham so {xml} den tu URL nen phai doi chieu danh sach trang truoc khi dung.
     */
    public static function cauHinh($xml)
    {
        if (!self::laBangNhieuDong($xml)) {
            abort(404);
        }

        return self::BANG_NHIEU_DONG[$xml];
    }

    /**
     * Bien danh sach gia tri cot thanh danh sach khoa nhom da sap xep.
     *
     * @param iterable $giaTri
     * @param int      $cat So ky tu dau lam khoa; 0 = giu nguyen gia tri
     * @return array Mang danh so lai tu 0
     */
    public static function khoaNhom($giaTri, $cat)
    {
        $khoa = [];

        foreach ($giaTri as $v) {
            if ($v === null) {
                continue;
            }

            $v = trim((string) $v);

            if ($v === '') {
                continue;
            }

            $khoa[] = $cat > 0 ? substr($v, 0, $cat) : $v;
        }

        $khoa = array_unique($khoa);
        sort($khoa);

        return array_values($khoa);
    }
}
```

- [ ] **Step 4: Chạy test, xác nhận xanh**

Run: `vendor/bin/phpunit --testsuite Unit --filter Xml3176DetailTabsTest`
Expected: PASS (8 test)

- [ ] **Step 5: Kiểm cú pháp và chạy toàn bộ suite**

Run: `php -l app/Services/Xml3176/Xml3176DetailTabs.php`
Run: `vendor/bin/phpunit --testsuite Unit`
Expected: PASS, **280 test**

- [ ] **Step 6: Commit**

```bash
git add app/Services/Xml3176/Xml3176DetailTabs.php tests/Unit/Xml3176/Xml3176DetailTabsTest.php
git commit -m "feat(xml3176): dang ky bang nhieu dong va ham tach nhom cho man chi tiet"
```

---

### Task 2: Endpoint một trang bảng + tách bốn blade nặng + tải lười tab con

Task lớn nhất. Kết thúc task này XML2–XML5 đã tải lười và phân trang; các tab khác vẫn như cũ.

**Files:**
- Modify: `routes/web.php`
- Modify: `app/Http/Controllers/BHYT/BHYTXml3176Controller.php`
- Modify: `resources/views/bhyt/xml3176/detail-xml-2.blade.php` … `-5.blade.php`
- Create: `resources/views/bhyt/xml3176/detail-xml-2-rows.blade.php` … `-5-rows.blade.php`
- Modify: `resources/views/bhyt/xml3176/index.blade.php` (JS)

**Interfaces:**
- Consumes: `Xml3176DetailTabs::cauHinh()`, `::khoaNhom()`, `::CO_TRANG` (Task 1); `Xml3176ErrorIndex::tu()`, `->moTa()`.
- Produces: route `bhyt.xml3176.detail-xml.rows`; quy ước DOM `.xml3176-lazy[data-url]` mà Task 3 dùng lại.

- [ ] **Step 1: Thêm route**

Trong `routes/web.php`, ngay sau dòng `bhyt.xml3176.detail-xml`:

```php
        Route::get('xml3176/index/detail-xml/{ma_lk}/rows/{xml}', 'BHYT\BHYTXml3176Controller@detailXmlRows')->name('bhyt.xml3176.detail-xml.rows');
```

- [ ] **Step 2: Thêm action `detailXmlRows`**

Trong `BHYTXml3176Controller`, thêm `use App\Services\Xml3176\Xml3176DetailTabs;` vào khối `use`, rồi thêm ngay sau `detailXml()`:

```php
    /**
     * Mot trang cua mot nhom, cho cac bang nhieu dong (XML2..XML5).
     *
     * Tra ve dung mot <table> cong thanh phan trang - khong phai ca tab.
     */
    public function detailXmlRows(Request $request, $ma_lk, $xml)
    {
        $cauHinh = Xml3176DetailTabs::cauHinh($xml);   // abort(404) neu ngoai danh sach trang
        $model   = $cauHinh['model'];
        $cot     = $cauHinh['cot_nhom'];
        $cat     = $cauHinh['cat'];
        $nhom    = (string) $request->input('nhom', '');

        $truyVan = $model::where('ma_lk', $ma_lk);

        // Cot nhom lay tu dang ky (hang so), khong phai tu tham so URL.
        if ($cat > 0) {
            $truyVan->where($cot, 'like', $nhom . '%');
        } else {
            $truyVan->where($cot, $nhom);
        }

        $rows = $truyVan->orderBy('stt')->paginate(Xml3176DetailTabs::CO_TRANG);

        // Chi lay loi cua rieng xml nay - du de to do va dung tooltip.
        $chiMucLoi = Xml3176ErrorIndex::tu(
            Xml3176ErrorResult::where('ma_lk', $ma_lk)->where('xml', $xml)->get()
        );

        return view('bhyt.xml3176.detail-xml-' . substr($xml, 3) . '-rows', [
            'rows'      => $rows,
            'chiMucLoi' => $chiMucLoi,
            'urlTrang'  => route('bhyt.xml3176.detail-xml.rows', ['ma_lk' => $ma_lk, 'xml' => $xml])
                            . '?nhom=' . urlencode($nhom),
        ]);
    }
```

`substr($xml, 3)` biến `'XML2'` thành `'2'`. An toàn vì `$xml` đã qua danh sách trắng.

- [ ] **Step 3: Tạo `detail-xml-2-rows.blade.php`**

Cắt nguyên khối `<table>` từ `detail-xml-2.blade.php`, đổi vòng lặp sang `$rows`:

```blade
<table class="table table-hover responsive" cellspacing="0" width="100%">
    <thead>
        <tr>
            <th>STT</th>
            <th>Mã thuốc</th>
            <th>Tên thuốc</th>
            <th>Hàm lượng</th>
            <th>Số đăng ký</th>
            <th>Giá</th>
            <th>TT thầu</th>
            <th>SL</th>
            <th>Khoa</th>
            <th>Bác sĩ</th>
            <th>Mã bệnh</th>
            <th>Ngày YL</th>
        </tr>
    </thead>
    <tbody>
        @foreach($rows as $value_xml2)
        @php
            $errorDescriptions = $chiMucLoi->moTa('XML2', $value_xml2->stt);
        @endphp
        <tr @if($errorDescriptions) class="highlight-red" data-toggle="tooltip" title="{{ $errorDescriptions }}" @endif>
            <td align="right">{{ $value_xml2->stt }}</td>
            <td>{{ $value_xml2->ma_thuoc }}</td>
            <td>{{ $value_xml2->ten_thuoc }}</td>
            <td>{{ $value_xml2->ham_luong }}</td>
            <td>{{ $value_xml2->so_dang_ky }}</td>
            <td align="right">{{ number_format($value_xml2->don_gia) }}</td>
            <td>{{ $value_xml2->tt_thau }}</td>
            <td align="right">{{ $value_xml2->so_luong }}</td>
            <td>{{ $value_xml2->ma_khoa }}</td>
            <td>{{ $value_xml2->ma_bac_si }}</td>
            <td>{{ $value_xml2->ma_benh }}</td>
            <td>{{ strtodatetime($value_xml2->ngay_yl) }}</td>
        </tr>
        @endforeach
    </tbody>
</table>

@include('bhyt.xml3176.detail-xml-phan-trang')
```

**Quan trọng:** các ô `<td>` ở trên là bản mẫu. Khi thực hiện, **sao chép nguyên văn** các
`<td>` đang có trong `detail-xml-2.blade.php` thay vì gõ lại — tên cột phải khớp tuyệt đối.
Bỏ `class="datatable"` khỏi `<table>` (lớp đó chưa từng được khởi tạo, giữ lại chỉ gây hiểu nhầm).

- [ ] **Step 4: Tạo partial thanh phân trang dùng chung**

Tạo `resources/views/bhyt/xml3176/detail-xml-phan-trang.blade.php`:

```blade
@if($rows->lastPage() > 1)
<div class="text-center" style="margin-top:8px;">
    <ul class="pagination" style="margin:0;">
        <li class="{{ $rows->currentPage() <= 1 ? 'disabled' : '' }}">
            <a href="javascript:void(0);" class="xml3176-trang"
               data-url="{{ $urlTrang }}&page={{ max(1, $rows->currentPage() - 1) }}">&laquo;</a>
        </li>
        <li class="disabled">
            <a href="javascript:void(0);">Trang {{ $rows->currentPage() }}/{{ $rows->lastPage() }}
                &mdash; {{ $rows->total() }} dòng</a>
        </li>
        <li class="{{ $rows->currentPage() >= $rows->lastPage() ? 'disabled' : '' }}">
            <a href="javascript:void(0);" class="xml3176-trang"
               data-url="{{ $urlTrang }}&page={{ min($rows->lastPage(), $rows->currentPage() + 1) }}">&raquo;</a>
        </li>
    </ul>
</div>
@endif
```

Tự dựng thay vì dùng `$rows->links()`: bản phân trang mặc định của Laravel 5.5 render thẻ
`<a href>` thật, bấm vào sẽ điều hướng cả trang thay vì nạp trong modal.

- [ ] **Step 5: Tạo ba blade rows còn lại**

`detail-xml-3-rows.blade.php`, `detail-xml-4-rows.blade.php`, `detail-xml-5-rows.blade.php`
theo đúng khuôn Step 3, mỗi file:

- sao chép nguyên văn `<thead>` và các `<td>` từ blade gốc tương ứng,
- biến vòng lặp giữ đúng tên cũ (`$value_xml3`, `$value_xml4`, `$value_xml5`),
- chuỗi xml trong `moTa()` khớp số hiệu file (`'XML3'`, `'XML4'`, `'XML5'`),
- kết thúc bằng `@include('bhyt.xml3176.detail-xml-phan-trang')`.

- [ ] **Step 6: Viết lại `detail-xml-2.blade.php` thành thanh tab con + khung rỗng**

```blade
@php
    $nhomXml2 = App\Services\Xml3176\Xml3176DetailTabs::khoaNhom(
        $dsNhom['XML2'], App\Services\Xml3176\Xml3176DetailTabs::BANG_NHIEU_DONG['XML2']['cat']
    );
@endphp

<div id="menu2" class="tab-pane fade">
    <ul class="nav nav-tabs">
        @foreach($nhomXml2 as $i => $ngay_yl)
            <li class="{{ $i === 0 ? 'active' : '' }}">
                <a data-toggle="tab" href="#tab_xml2_{{ $i }}">Ngày: {{ strtodate($ngay_yl) }}</a>
            </li>
        @endforeach
    </ul>

    <div class="tab-content">
        @foreach($nhomXml2 as $i => $ngay_yl)
            <div id="tab_xml2_{{ $i }}"
                 class="tab-pane fade xml3176-lazy {{ $i === 0 ? 'in active' : '' }}"
                 data-url="{{ route('bhyt.xml3176.detail-xml.rows', ['ma_lk' => $xml1->ma_lk, 'xml' => 'XML2']) }}?nhom={{ urlencode($ngay_yl) }}">
                <div class="panel panel-default">
                    <div class="panel-body table-responsive">
                        <i class="fa fa-spinner fa-spin"></i> Đang tải…
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>
```

Id tab con đổi sang `tab_xml2_{{ $i }}` (theo chỉ số) thay vì nhúng thẳng khoá nhóm: khoá
ngày an toàn nhưng `ma_nhom` của XML3 thì không có gì bảo đảm, và dùng chỉ số thì cả bốn
bảng theo cùng một quy ước. Nhãn hiển thị không đổi.

Biến `$dsNhom` do vỏ modal truyền xuống — Task 4 mới đổi cách tính; **ở task này** vỏ modal
tạm truyền `$dsNhom['XML2'] = $xml1->Xml3176Xml2->pluck('ngay_yl')` để không phải sửa hai
nơi cùng lúc.

- [ ] **Step 7: Vỏ modal truyền `$dsNhom`**

Trong `detailXml()`, thêm vào mảng dữ liệu truyền cho view:

```php
            'dsNhom'    => [
                'XML2' => $xml1->Xml3176Xml2->pluck('ngay_yl'),
                'XML3' => $xml1->Xml3176Xml3->pluck('ma_nhom'),
                'XML4' => $xml1->Xml3176Xml4->pluck('ngay_kq'),
                'XML5' => $xml1->Xml3176Xml5->pluck('thoi_diem_dbls'),
            ],
```

Task 4 thay bốn dòng này bằng truy vấn `pluck` trực tiếp, bỏ hẳn việc nạp collection.

- [ ] **Step 8: Viết lại ba blade tab con còn lại**

`detail-xml-3/4/5.blade.php` theo đúng khuôn Step 6. Khác biệt bắt buộc:

- XML3: id `tab_xml3_{{ $i }}`, nhãn `Nhóm: {{ config('__tech.pl6_4210')[$ma_nhom] }}`, và
  khoá nhóm **không cắt** (`cat` = 0).
- XML4: nhãn `Ngày: {{ strtodate($ngay_kq) }}`.
- XML5: nhãn `Ngày: {{ strtodate($date) }}`.

- [ ] **Step 9: JS tải lười và phân trang**

Trong `index.blade.php`, thêm vào khối `<script>`:

```js
    // ── Tai luoi noi dung modal chi tiet ────────────────────────────────────
    // Khung nao co data-url thi noi dung duoc nap khi no thuc su hien ra.
    function napKhung($khung) {
        if (!$khung.length || !$khung.data('url')) { return; }
        if ($khung.data('daNap') || $khung.data('dangNap')) { return; }

        $khung.data('dangNap', true);

        $.get($khung.data('url'))
            .done(function (html) {
                $khung.html(html);
                $khung.data('daNap', true);
                napKhungDangHien($khung);
            })
            .fail(function () {
                $khung.html('<div class="alert alert-danger">Không tải được nội dung. Vui lòng thử lại.</div>');
            })
            .always(function () {
                $khung.data('dangNap', false);
            });
    }

    // Khung dau tien cua moi cap tab mang san class "active" nen khong bao gio phat
    // shown.bs.tab - phai tu nap sau khi noi dung cha duoc chen vao.
    function napKhungDangHien($goc) {
        $goc.find('.tab-pane.active[data-url]').each(function () {
            napKhung($(this));
        });
    }

    $(document).on('shown.bs.tab', '#infoModal a[data-toggle="tab"]', function () {
        napKhung($($(this).attr('href')));
    });

    $(document).on('click', '#infoModal .xml3176-trang', function () {
        var $khung = $(this).closest('[data-url]');
        $khung.data('url', $(this).data('url')).data('daNap', false);
        napKhung($khung);
    });
```

Trong handler `dblclick` (nạp modal), sau `$('#modalContent').html(response);` thêm:

```js
                    napKhungDangHien($('#modalContent'));
```

- [ ] **Step 10: Kiểm cú pháp, chạy suite**

Run: `php -l app/Http/Controllers/BHYT/BHYTXml3176Controller.php`
Run: `vendor/bin/phpunit --testsuite Unit`
Expected: PASS, 280 test. `Xml3176BladeCompilesTest` phải xanh — nó phủ cả các blade mới.

- [ ] **Step 11: Commit**

```bash
git add routes/web.php app/Http/Controllers/BHYT/BHYTXml3176Controller.php resources/views/bhyt/xml3176/ 
git commit -m "perf(xml3176): tai luoi va phan trang server cho bon bang nhieu dong"
```

---

### Task 3: Endpoint nội dung tab + tải lười 11 tab còn lại

**Files:**
- Modify: `routes/web.php`
- Modify: `app/Http/Controllers/BHYT/BHYTXml3176Controller.php`
- Modify: `resources/views/bhyt/xml3176/detail-xml.blade.php`
- Modify: `resources/views/bhyt/xml3176/index.blade.php` (JS)

**Interfaces:**
- Consumes: quy ước `.xml3176-lazy[data-url]` và hàm `napKhung` (Task 2).
- Produces: route `bhyt.xml3176.detail-xml.tab`.

- [ ] **Step 1: Thêm route**

```php
        Route::get('xml3176/index/detail-xml/{ma_lk}/tab/{xml}', 'BHYT\BHYTXml3176Controller@detailXmlTab')->name('bhyt.xml3176.detail-xml.tab');
```

- [ ] **Step 2: Thêm hằng danh sách tab lười và action `detailXmlTab`**

Trong `BHYTXml3176Controller`, thêm hằng cạnh `DATATABLE_COLUMNS`:

```php
    /**
     * Cac tab duoc nap khi nguoi dung bam vao, khong nap san cung vo modal.
     *
     * Gia tri la ten blade trong bhyt/xml3176. Danh sach TRANG: {xml} den tu URL.
     */
    const TAB_TAI_LUOI = [
        'XML7'  => 'detail-xml-7',   'XML8'  => 'detail-xml-8',
        'XML9'  => 'detail-xml-9',   'XML10' => 'detail-xml-10',
        'XML11' => 'detail-xml-11',  'XML13' => 'detail-xml-13',
        'XML14' => 'detail-xml-14',  'XML15' => 'detail-xml-15',
        'HEIN'  => 'detail-xml-hein-card',
        'ERR'   => 'detail-xml-errors',
    ];
```

và action:

```php
    /**
     * Noi dung mot tab cua modal chi tiet.
     */
    public function detailXmlTab($ma_lk, $xml)
    {
        if (!isset(self::TAB_TAI_LUOI[$xml])) {
            abort(404);
        }

        $xml1 = Xml3176Xml1::with('Xml3176ErrorResult')
        ->where('ma_lk', $ma_lk)
        ->firstOrFail();

        return view('bhyt.xml3176.' . self::TAB_TAI_LUOI[$xml], [
            'xml1'      => $xml1,
            'chiMucLoi' => Xml3176ErrorIndex::tu($xml1->Xml3176ErrorResult),
        ]);
    }
```

- [ ] **Step 3: Đổi 10 lời gọi `@include` thành khung rỗng**

Trong `detail-xml.blade.php`, khối `<div class="tab-content">` hiện gọi 15 `@include`.
Giữ nguyên `detail-xml-1` (tab luôn hiển thị) và bốn blade `detail-xml-2/3/4/5` (đã là
thanh tab con nhẹ từ Task 2). Mười cái còn lại đổi thành khung rỗng.

Ví dụ thay `@include('bhyt.xml3176.detail-xml-7')` bằng:

```blade
    <div id="menu7" class="tab-pane fade xml3176-lazy"
         data-url="{{ route('bhyt.xml3176.detail-xml.tab', ['ma_lk' => $xml1->ma_lk, 'xml' => 'XML7']) }}">
        <i class="fa fa-spinner fa-spin"></i> Đang tải…
    </div>
```

Làm tương tự cho XML8, XML9, XML10, XML11, XML13, XML14, XML15, và:

- Thẻ BHYT → `id="menu-hein-card"`, `'xml' => 'HEIN'`
- Lỗi XML → `id="menu-xml-errors"`, `'xml' => 'ERR'`

Id khung phải khớp `href` của thẻ `<a data-toggle="tab">` tương ứng ở thanh tab phía trên.

- [ ] **Step 4: Chuyển khởi tạo DataTable của tab Thẻ BHYT**

`#checkHeinCard` giờ chỉ tồn tại sau khi tab Thẻ BHYT được nạp. Trong `index.blade.php`,
thay:

```js
    var XML3176_MODAL_TABLES = ['#thuocvt', '#dvkt', '#cls', '#dienbien',
                                '#checkHeinCard', '#xmlErrorChecks'];

    function initializeModalDataTables() {
        XML3176_MODAL_TABLES.forEach(function (sel) {
            $(sel).DataTable();
        });
    }
```

bằng:

```js
    // Chi con #checkHeinCard la bang that. Nam id cu (#thuocvt, #dvkt, #cls, #dienbien,
    // #xmlErrorChecks) khong ton tai trong bat ky blade nao - da bo.
    var XML3176_MODAL_TABLES = ['#checkHeinCard'];

    function initializeModalDataTables($goc) {
        var $pham_vi = $goc || $('#modalContent');
        XML3176_MODAL_TABLES.forEach(function (sel) {
            var el = $pham_vi.find(sel);
            if (el.length && !$.fn.DataTable.isDataTable(el)) {
                el.DataTable();
            }
        });
    }
```

Trong `napKhung()` (Task 2), sau `$khung.data('daNap', true);` thêm:

```js
                initializeModalDataTables($khung);
```

- [ ] **Step 5: Kiểm cú pháp, chạy suite**

Run: `php -l app/Http/Controllers/BHYT/BHYTXml3176Controller.php`
Run: `vendor/bin/phpunit --testsuite Unit`
Expected: PASS, 280 test

- [ ] **Step 6: Commit**

```bash
git add routes/web.php app/Http/Controllers/BHYT/BHYTXml3176Controller.php resources/views/bhyt/xml3176/detail-xml.blade.php resources/views/bhyt/xml3176/index.blade.php
git commit -m "perf(xml3176): tai luoi 10 tab con lai cua modal chi tiet"
```

---

### Task 4: Vỏ modal thôi nạp collection

Đến đây không blade nào của vỏ modal còn cần dữ liệu dòng. Task này bỏ hẳn việc nạp
collection — đây mới là chỗ cắt bộ nhớ và thời gian thật sự.

**Files:**
- Modify: `app/Services/Xml3176/Xml3176ErrorIndex.php` (`demTheoStt`)
- Modify: `tests/Unit/Xml3176/Xml3176ErrorIndexTest.php`
- Modify: `app/Http/Controllers/BHYT/BHYTXml3176Controller.php` (`detailXml`)
- Modify: `resources/views/bhyt/xml3176/detail-xml.blade.php` (13 huy hiệu + 12 điều kiện ẩn/hiện)

**Interfaces:**
- Consumes: `Xml3176DetailTabs::BANG_NHIEU_DONG` (Task 1)
- Produces: biến view `$soDong` (mảng `['XML2' => int, ...]`), `$dsStt`, `$dsNhom`

- [ ] **Step 1: Đổi test của `demTheoStt` sang danh sách số (sẽ đỏ)**

Thay test `dem_theo_stt_dem_so_dong_co_loi_khong_phai_tong_so_loi` bằng:

```php
    /** @test */
    public function dem_theo_stt_nhan_danh_sach_so_stt_va_dem_so_dong_co_loi()
    {
        $ix = $this->chiMuc([
            $this->loi('XML2', 1, 'Loi mot'),
            $this->loi('XML2', 1, 'Loi hai'),   // cung dong -> van tinh 1
            $this->loi('XML2', 3, 'Loi ba'),
        ]);

        // Nhan thang danh sach stt (tu pluck), khong phai danh sach model.
        $this->assertEquals(2, $ix->demTheoStt([1, 2, 3], 'XML2'));
        $this->assertEquals(2, $ix->demTheoStt(new Collection(['1', '2', '3']), 'XML2'));
        $this->assertEquals(0, $ix->demTheoStt([2, 4], 'XML2'));
        $this->assertEquals(0, $ix->demTheoStt([], 'XML2'));
    }
```

Trong test `chi_muc_rong_khong_no_o_bat_ky_phuong_thuc_nao`, đổi
`$ix->demTheoStt($items, 'XML2')` thành `$ix->demTheoStt([1], 'XML2')`.

- [ ] **Step 2: Chạy test, xác nhận đỏ**

Run: `vendor/bin/phpunit --testsuite Unit --filter Xml3176ErrorIndexTest`
Expected: FAIL — `demTheoStt` đang đọc `$item->stt` trên số nguyên

- [ ] **Step 3: Đổi `demTheoStt`**

```php
    /**
     * So DONG co loi. Nhan thang danh sach so stt (tu pluck) chu khong phai danh sach
     * model - vo modal khong con nap collection nua.
     */
    public function demTheoStt($dsStt, $xml)
    {
        $dem = 0;

        foreach ($dsStt as $stt) {
            if ($this->coLoi($xml, $stt)) {
                $dem++;
            }
        }

        return $dem;
    }
```

- [ ] **Step 4: Chạy test, xác nhận xanh**

Run: `vendor/bin/phpunit --testsuite Unit --filter Xml3176ErrorIndexTest`
Expected: PASS

- [ ] **Step 5: Viết lại `detailXml()`**

```php
    public function detailXml($ma_lk)
    {
        // withCount thay vi with: vo modal chi can BIET co bao nhieu dong (de an/hien tab
        // va tinh huy hieu), khong can noi dung dong. Noi dung nap theo tab.
        $demQuanHe = [];
        foreach ([2, 3, 4, 5, 7, 8, 9, 10, 11, 13, 14, 15] as $n) {
            $demQuanHe[] = 'Xml3176Xml' . $n;
        }

        $xml1 = Xml3176Xml1::with('Xml3176ErrorResult')
        ->withCount($demQuanHe)
        ->where('ma_lk', $ma_lk)
        ->firstOrFail();

        $soDong = [];
        foreach ([2, 3, 4, 5, 7, 8, 9, 10, 11, 13, 14, 15] as $n) {
            $soDong['XML' . $n] = (int) $xml1->{'xml3176_xml' . $n . '_count'};
        }

        // Mot truy van moi bang nhieu dong cho ra CA hai thu: danh sach stt (huy hieu)
        // va cac khoa nhom (thanh tab con). Chi lay so/chuoi, khong dung model.
        $dsStt = [];
        $dsNhom = [];
        foreach (Xml3176DetailTabs::BANG_NHIEU_DONG as $xml => $ch) {
            $model = $ch['model'];
            $map = $model::where('ma_lk', $ma_lk)->pluck($ch['cot_nhom'], 'stt');
            $dsStt[$xml]  = $map->keys();
            $dsNhom[$xml] = $map->values();
        }

        return view('bhyt.xml3176.detail-xml', [
            'xml1'      => $xml1,
            'chiMucLoi' => Xml3176ErrorIndex::tu($xml1->Xml3176ErrorResult),
            'soDong'    => $soDong,
            'dsStt'     => $dsStt,
            'dsNhom'    => $dsNhom,
        ]);
    }
```

- [ ] **Step 6: Đổi 12 điều kiện ẩn/hiện tab**

Trong `detail-xml.blade.php`, thay mọi `@if($xml1->Xml3176XmlN->isNotEmpty())` bằng
`@if($soDong['XMLN'] > 0)`. Mười hai chỗ: XML2, 3, 4, 5, 7, 8, 9, 10, 11, 13, 14, 15.

- [ ] **Step 7: Đổi 12 huy hiệu**

Bốn bảng nhiều dòng:

```php
                $errorCountXml = $chiMucLoi->demTheoStt($dsStt['XML2'], 'XML2');
```

(tương tự XML3, XML4, XML5 với đúng khoá của nó)

Tám bảng một dòng — `demTheoXml` nhận số nguyên thay cho collection:

```php
                $errorCountXml = $chiMucLoi->demTheoXml($soDong['XML7'], 'XML7');
```

(tương tự XML8, XML9, XML10, XML11, XML13, XML14, XML15)

Huy hiệu XML1 giữ nguyên `$chiMucLoi->demLoi('XML1')`.

- [ ] **Step 8: `demTheoXml` nhận cả số nguyên**

Trong `Xml3176ErrorIndex`, thay:

```php
        // count() nhan ca mang lan Collection (Collection implement Countable).
        return count($items);
```

bằng:

```php
        // Nhan ca so nguyen (tu withCount) lan mang/Collection.
        return is_int($items) ? $items : count($items);
```

Thêm test:

```php
    /** @test */
    public function dem_theo_xml_nhan_ca_so_nguyen_tu_with_count()
    {
        $ix = $this->chiMuc([$this->loi('XML13', 1, 'Loi')]);

        $this->assertEquals(5, $ix->demTheoXml(5, 'XML13'));
        $this->assertEquals(0, $ix->demTheoXml(5, 'XML14'));
    }
```

- [ ] **Step 9: Bỏ `$dsNhom` tạm ở blade tab con**

Bốn blade `detail-xml-2/3/4/5.blade.php` đã đọc `$dsNhom['XMLn']` từ Task 2 — không cần
sửa, nay nguồn của nó là `pluck` thay vì collection.

Xác nhận vỏ modal không còn truy cập collection nào:

Run: `grep -n "Xml3176Xml[0-9]*->" resources/views/bhyt/xml3176/detail-xml.blade.php`
Expected: **không có kết quả**

- [ ] **Step 10: Kiểm cú pháp, chạy suite**

Run: `php -l app/Http/Controllers/BHYT/BHYTXml3176Controller.php app/Services/Xml3176/Xml3176ErrorIndex.php`
Run: `vendor/bin/phpunit --testsuite Unit`
Expected: PASS, **281 test**

- [ ] **Step 11: Commit**

```bash
git add app/ resources/views/bhyt/xml3176/detail-xml.blade.php tests/
git commit -m "perf(xml3176): vo modal chi tiet thoi nap collection, dung withCount va pluck"
```

---

## Nghiệm thu thủ công (bắt buộc)

DB dev trống cả bốn bảng `xml3176_*`; không có hạ tầng test JS. Toàn bộ phần tải lười chỉ kiểm được bằng tay.

**Trước khi deploy: chụp màn hình modal của một hồ sơ dài ngày, thấy rõ mọi huy hiệu và mọi tab con.** Mục 3 và 4 không kiểm được nếu thiếu ảnh này.

| # | Việc | Mong đợi |
|---|---|---|
| 1 | Mở modal hồ sơ dài ngày, xem tab Network | Phản hồi đầu nhỏ hơn hẳn; thời gian mở giảm rõ |
| 2 | Bấm lần lượt từng tab | Mỗi tab nạp một request; bấm lại **không** gọi lại |
| 3 | So huy hiệu từng tab với ảnh chụp cũ | Giống hệt từng con số |
| 4 | So tab con XML2/XML4/XML5 với ảnh chụp cũ | Đủ và **đúng thứ tự** |
| 4b | So tab con XML3 với ảnh chụp cũ | **Đủ**; thứ tự nay sắp tăng theo `ma_nhom` (trước đây không xác định) — thay đổi có chủ ý, xem spec |
| 5 | Bấm một tab con | Bảng hiện đủ dòng của nhóm, tối đa 100 dòng mỗi trang |
| 6 | Nhóm quá 100 dòng (thường là XML3) | Thanh phân trang hiện; bấm sang trang nạp đúng phần tiếp |
| 7 | Dòng có lỗi | Vẫn tô đỏ, tooltip đúng mô tả, ở mọi trang |
| 8 | Tab Thẻ BHYT | Bảng vẫn sắp xếp/tìm kiếm được |
| 9 | Hồ sơ không có lỗi | Không huy hiệu, không dòng đỏ |
| 10 | Đóng modal, mở hồ sơ khác | Không sót nội dung hồ sơ trước |
| 11 | Bấm nhanh liên tiếp vào một tab | Chỉ một request được gửi |
| 12 | Ngắt mạng rồi bấm một tab | Hiện thông báo lỗi, không treo vòng quay mãi |

## Nợ kỹ thuật ghi nhận

1. `config('__tech.pl6_4210')[$ma_nhom]` trong `detail-xml-3` truy cập mảng không kiểm tồn tại — `ma_nhom` lạ sẽ ném lỗi.
2. Các nợ đã ghi ở đợt trước: `exportXml()` dựng 2000 file trong một request; endpoint xuất nhận thiếu bộ lọc; `config/datatables.php` đặt `'escape' => '*'` toàn app; `whereColumn('stt','stt')` trong các model `Qd130*`; màn QD130 nhiều khả năng mắc cùng lỗi N+1, chưa kiểm.
