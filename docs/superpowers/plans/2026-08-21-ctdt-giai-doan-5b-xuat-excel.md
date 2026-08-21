# Giai đoạn 5B — Xuất Excel: danh sách hồ sơ, bảng lỗi, nhật ký gửi

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Ba nút tải Excel trên màn danh sách chứng từ điện tử — danh sách hồ sơ để đối soát với BHXH, bảng lỗi để đưa người nhập liệu đi sửa, nhật ký gửi để dựng lại chuyện đã xảy ra.

**Architecture:** Ba lớp `App\Exports\*` theo đúng khuôn `KetQuaTraCuuTheExport` sẵn có: implement `FromQuery, WithHeadings, ShouldAutoSize, WithMapping, WithTitle`, **nhận thẳng đối tượng truy vấn đã lọc từ controller** chứ không tự dựng lại điều kiện. Bộ lọc được rút khỏi `fetchData()` thành một phương thức riêng để màn hình và tệp xuất không bao giờ lệch nhau.

**Tech Stack:** Laravel 5.5, PHP 7.4, PHPUnit 6, `maatwebsite/excel` ^3.1 (đã có trong `composer.json`).

## Global Constraints

- **PHP 7.4 / Laravel 5.5 / PHPUnit 6 là sàn cứng.** Không cú pháp PHP 8. Không `: void` trên `setUp()`.
- **Không có `Request::boolean()`.** Dùng `filter_var($x, FILTER_VALIDATE_BOOLEAN)`.
- **Nhãn trạng thái chỉ lấy từ `CtdtTrangThaiGui::nhan(CtdtTrangThaiGui::cua($hoSo))`.** Không gõ lại chuỗi tiếng Việt trong lớp Export.
- **Lớp Export nhận truy vấn đã lọc, không tự dựng bộ lọc.** Mỗi bên tự dựng thì thêm một ô lọc mà quên bên kia sẽ làm tệp xuất khác hẳn màn hình, và không có dấu hiệu gì cho tới lúc ai đó ngồi đối chiếu từng dòng.
- **`FromQuery` chứ không `FromCollection`.** Bảng này phình theo thời gian; nạp cả bảng vào bộ nhớ là cách chắc chắn để máy chủ mới (PHP 128MB) chết.
- ⛔ **TUYỆT ĐỐI KHÔNG dùng `RefreshDatabase` hay `DatabaseMigrations`.** Hai trait đó gọi `migrate:fresh` — `DROP` toàn bộ bảng. Ngày 2026-08-21 chuyện này đã xảy ra thật và xoá sạch CSDL phát triển `qlbv`. Dùng `Tests\Support\DungBangCtdtSqlite` thay thế; `ChotAnToanCsdlTest` sẽ đỏ nếu ai dùng lại hai trait đó.
- **Máy phát triển này đã bật `submit_enabled` và đã gửi thật.** Không chạy `SignCtdtJob`, `SubmitCtdtJob`, không chạy worker hàng đợi.
- **Baseline test:** `tests/Unit/Ctdt` → **545 test, đỏ đúng một** — `CtdtCauHinhTest::gui_len_cong_mac_dinh_tat` (đỏ có chủ đích, đừng đụng).
- Chú thích trong mã viết **không dấu**, tài liệu Markdown viết **có dấu**.

## Phụ thuộc — ĐÃ THOẢ

Bảng `ctdt_lich_su_gui` và model `App\Models\BHYT\Ctdt\CtdtLichSuGui` **đã có trên `main`** (Giai đoạn 5A). Task 3 làm được ngay.

## ⚠️ Bộ lọc ngày KHÔNG đến từ ô nhập

Cập nhật 2026-08-21 sau khi đọc lại `index.blade.php`: khoảng ngày **không** lấy từ `$('#tu_ngay').val()`. Nó đến từ biến `ctdtRange` (`index.blade.php:84`), do partial date-range đặt qua `partials.load_data_button`, dạng `'YYYY-MM-DD HH:mm:ss'`:

```javascript
d.tu_ngay  = ctdtRange.from;
d.den_ngay = ctdtRange.to;
```

**Mọi chỗ trong kế hoạch này nhắc `$('#tu_ngay')` / `$('#den_ngay')` đều SAI — dùng `ctdtRange.from` / `ctdtRange.to`.** Đọc sai chỗ này thì tệp xuất mang khoảng ngày khác hẳn màn hình đang hiện, và không có dấu hiệu gì cho tới lúc ai đó ngồi đối chiếu với bản của BHXH.

Ngoài ra `index.blade.php` đã đổi đáng kể ở Giai đoạn 5A (thêm khung modal chi tiết, `@include` partial JS dùng chung, hai cột `render` dựng thẻ bằng DOM API). **Đọc lại tệp trước khi sửa**, đừng dựa vào số dòng ghi trong kế hoạch này.

## File Structure

| Tệp | Trách nhiệm |
|---|---|
| `app/Exports/CtdtDanhSachExport.php` | Một dòng = một hồ sơ, theo bộ lọc đang xem |
| `app/Exports/CtdtLoiExport.php` | Một dòng = một lỗi, để đi sửa dữ liệu |
| `app/Exports/CtdtNhatKyGuiExport.php` | Một dòng = một lần gọi cổng |
| `app/Http/Controllers/BHYT/BHYTCtdtController.php` | *(sửa)* rút bộ lọc ra dùng chung + ba hành động tải |
| `routes/web.php` | *(sửa)* ba route |
| `resources/views/bhyt/ctdt/index.blade.php` | *(sửa)* ba nút tải |

---

### Task 1: Rút bộ lọc dùng chung + xuất danh sách hồ sơ

**Files:**
- Create: `app/Exports/CtdtDanhSachExport.php`
- Modify: `app/Http/Controllers/BHYT/BHYTCtdtController.php` (`fetchData()` khoảng dòng 109-123, thêm `locTu()` và `xuatDanhSach()`)
- Modify: `routes/web.php` (cạnh `bhyt.ctdt.fetch-data`, khoảng dòng 600)
- Modify: `resources/views/bhyt/ctdt/index.blade.php`
- Test: `tests/Unit/Ctdt/CtdtXuatExcelTest.php`

**Interfaces:**
- Consumes: `CtdtDanhSach::truyVan(array $loc)` → `Illuminate\Database\Eloquent\Builder`; `CtdtTrangThaiGui::cua($hoSo)`, `::nhan($ma)`
- Produces:
  - `BHYTCtdtController::locTu(Request $request)` → `array` — đúng tám khoá `tu_ngay, den_ngay, dich_vu, loai_ho_so, macskcb, imported_by, tim, chi_con_loi, trang_thai_gui`
  - `CtdtDanhSachExport::__construct($truyVan)` — nhận `Builder` đã lọc
  - Route tên `bhyt.ctdt.xuat.danh-sach`

- [ ] **Step 1: Viết test đỏ trước**

Tạo `tests/Unit/Ctdt/CtdtXuatExcelTest.php`:

```php
<?php

namespace Tests\Unit\Ctdt;

use Tests\TestCase;
use App\Exports\CtdtDanhSachExport;
use App\Models\BHYT\Ctdt\CtdtHoSo;
use App\Services\Ctdt\CtdtTrangThaiGui;

/**
 * Tep xuat phai khop DUNG man hinh. Moi ben tu dung bo loc thi them mot o loc ma quen ben
 * kia se lam tep xuat khac han, va khong co dau hieu gi cho toi luc ai do ngoi doi chieu
 * tung dong voi ban cua BHXH.
 */
class CtdtXuatExcelTest extends TestCase
{
    private function hoSo(array $ghiDe = [])
    {
        $hoSo = new CtdtHoSo();

        foreach (array_merge([
            'ma_ho_so'   => 'YT001',
            'dich_vu'    => 'CT2025',
            'macskcb'    => '01001',
            'checked_at' => '2026-08-20 08:00:00',
            'so_loi'     => 0,
            'is_signed'  => true,
            'ma_ket_qua' => '200',
            'ma_gd'      => 'HS_CHUNGTU01929_094D388C-6DD7-4CA1-A3BE-6D5BF53FEE75',
        ], $ghiDe) as $cot => $giaTri) {
            $hoSo->{$cot} = $giaTri;
        }

        return $hoSo;
    }

    /** @test */
    public function so_cot_tieu_de_khop_so_o_moi_dong()
    {
        // Lech mot cot la moi gia tri tu do tro di nam duoi sai tieu de - va bang van trong
        // hoan toan binh thuong. Day la kieu loi khong ai phat hien bang mat.
        $xuat = new CtdtDanhSachExport(CtdtHoSo::query());

        $this->assertCount(
            count($xuat->headings()),
            $xuat->map($this->hoSo()),
            'So o moi dong phai bang so tieu de'
        );
    }

    /** @test */
    public function nhan_trang_thai_lay_tu_CtdtTrangThaiGui_chu_khong_go_lai()
    {
        // Go lai chuoi tieng Viet o day nghia la doi nhan tren man hinh se khong doi nhan
        // trong tep xuat - hai nguon su that cho cung mot khai niem.
        $hoSo = $this->hoSo(['ma_ket_qua' => null, 'is_signed' => false, 'signed_error' => 'USB token bi rut']);

        $dong = (new CtdtDanhSachExport(CtdtHoSo::query()))->map($hoSo);

        $this->assertContains(
            CtdtTrangThaiGui::nhan(CtdtTrangThaiGui::KY_HONG),
            $dong,
            'Phai dung dung nhan cua CtdtTrangThaiGui'
        );
    }

    /** @test */
    public function ma_gd_dai_khong_bi_cat_trong_tep_xuat()
    {
        // ma_gd la cot doi soat quan trong nhat voi BHXH. Cong tra ve 52 ky tu that.
        $dong = (new CtdtDanhSachExport(CtdtHoSo::query()))->map($this->hoSo());

        $this->assertContains(
            'HS_CHUNGTU01929_094D388C-6DD7-4CA1-A3BE-6D5BF53FEE75',
            $dong
        );
    }

    /** @test */
    public function duyet_theo_lo_chu_khong_nap_ca_bang()
    {
        // Bang nay phinh theo thoi gian, may chu moi gioi han PHP 128MB. FromCollection se
        // nap het vao bo nho.
        $this->assertInstanceOf(
            \Maatwebsite\Excel\Concerns\FromQuery::class,
            new CtdtDanhSachExport(CtdtHoSo::query())
        );
    }
}
```

- [ ] **Step 2: Chạy test cho chắc nó đỏ**

Chạy: `php vendor/bin/phpunit tests/Unit/Ctdt/CtdtXuatExcelTest.php`
Kỳ vọng: ĐỎ với `Class 'App\Exports\CtdtDanhSachExport' not found`.

- [ ] **Step 3: Viết lớp xuất**

Tạo `app/Exports/CtdtDanhSachExport.php`:

```php
<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use App\Services\Ctdt\CtdtTrangThaiGui;

/**
 * Xuat danh sach ho so chung tu dien tu theo DUNG bo loc dang chon tren man hinh.
 *
 * Nhan thang doi tuong truy van da loc tu controller, khong tu dung lai dieu kien: neu moi
 * ben tu dung thi them mot bo loc ma quen ben kia se lam tep xuat khac han man hinh, va
 * khong co dau hieu gi cho toi luc ai do ngoi doi chieu tung dong voi ban cua BHXH.
 *
 * FromQuery de Laravel Excel duyet THEO LO: bang nay phinh theo thoi gian, va may chu moi
 * gioi han PHP 128MB.
 */
class CtdtDanhSachExport implements FromQuery, WithHeadings, ShouldAutoSize, WithMapping, WithTitle
{
    /** @var \Illuminate\Database\Eloquent\Builder */
    protected $truyVan;

    protected $stt = 0;

    public function __construct($truyVan)
    {
        $this->truyVan = $truyVan;
    }

    public function query()
    {
        // Nap kem chung tu dau tien: cot Ho ten / So the lay tu do. Khong nap kem thi moi
        // dong la mot truy van rieng - 5000 dong thanh 5001 truy van.
        return $this->truyVan
            ->with(['chungTu' => function ($q) {
                $q->select('id', 'ho_so_id', 'ma_the', 'ho_ten')->orderBy('id');
            }])
            ->orderByDesc('imported_at');
    }

    public function title(): string
    {
        return 'Ho so';
    }

    public function headings(): array
    {
        return [
            'STT',
            'Mã hồ sơ',
            'Dịch vụ',
            'Loại HS',
            'Mã CSKCB',
            'Họ tên',
            'Số thẻ',
            'Số chứng từ',
            'Số lỗi',
            'Trạng thái gửi',
            'Mã giao dịch',
            'Mã kết quả',
            'Thời gian tiếp nhận',
            'Lỗi ký số',
            'Lỗi gửi',
            'Người nạp',
            'Thời điểm nạp',
            'Thời điểm gửi',
        ];
    }

    public function map($hoSo): array
    {
        $this->stt++;

        $dau = $hoSo->relationLoaded('chungTu') ? $hoSo->chungTu->first() : null;

        return [
            $this->stt,
            (string) $hoSo->ma_ho_so,
            (string) $hoSo->dich_vu,
            (string) $hoSo->loai_hs,
            (string) $hoSo->macskcb,
            $dau ? (string) $dau->ho_ten : '',
            $dau ? (string) $dau->ma_the : '',
            (int) $hoSo->so_chung_tu,
            (int) $hoSo->so_loi,
            // Nhan lay tu CtdtTrangThaiGui: go lai chuoi o day nghia la doi nhan tren man
            // hinh se khong doi nhan trong tep xuat.
            CtdtTrangThaiGui::nhan(CtdtTrangThaiGui::cua($hoSo)),
            (string) $hoSo->ma_gd,
            (string) $hoSo->ma_ket_qua,
            (string) $hoSo->thoi_gian_tiep_nhan,
            (string) $hoSo->signed_error,
            (string) $hoSo->submit_error,
            (string) $hoSo->imported_by,
            (string) $hoSo->imported_at,
            (string) $hoSo->submitted_at,
        ];
    }
}
```

- [ ] **Step 4: Chạy test cho chắc nó xanh**

Chạy: `php vendor/bin/phpunit tests/Unit/Ctdt/CtdtXuatExcelTest.php`
Kỳ vọng: `OK (4 tests)`.

- [ ] **Step 5: Rút bộ lọc ra khỏi `fetchData()`**

Trong `app/Http/Controllers/BHYT/BHYTCtdtController.php`, thay khối tạo `$loc` trong `fetchData()` (khoảng dòng 111-123) bằng:

```php
        $truyVan = CtdtDanhSach::truyVan($this->locTu($request));
```

và thêm phương thức mới ngay dưới `fetchData()`:

```php
    /**
     * Doc bo loc tu request. MOT ban duy nhat cho ca man hinh lan tep xuat.
     *
     * Hai ban se lech nhau: them mot o loc ma quen ben kia se lam tep xuat khac han man
     * hinh, va khong co dau hieu gi cho toi luc ai do ngoi doi chieu tung dong voi ban cua
     * BHXH.
     *
     * @return array
     */
    private function locTu(Request $request)
    {
        return [
            'tu_ngay'        => $request->input('tu_ngay'),
            'den_ngay'       => $request->input('den_ngay'),
            'dich_vu'        => $request->input('dich_vu'),
            'loai_ho_so'     => $request->input('loai_ho_so'),
            'macskcb'        => $request->input('macskcb'),
            'imported_by'    => $request->input('imported_by'),
            'tim'            => $request->input('tim'),
            // Laravel 5.5 KHONG co Request::boolean() (them tu 5.8). DataTables gui '0'/'1'
            // dang chuoi, ma (bool) '0' la TRUE - o loc se luon bat.
            'chi_con_loi'    => filter_var($request->input('chi_con_loi'), FILTER_VALIDATE_BOOLEAN),
            'trang_thai_gui' => $request->input('trang_thai_gui'),
        ];
    }
```

- [ ] **Step 6: Thêm hành động tải**

Thêm `use App\Exports\CtdtDanhSachExport;` và `use Maatwebsite\Excel\Facades\Excel;` vào khối `use` của controller, rồi thêm:

```php
    /** Tai danh sach ho so theo dung bo loc dang xem */
    public function xuatDanhSach(Request $request)
    {
        $ten = 'chung-tu-dien-tu-' . now()->format('Ymd-His') . '.xlsx';

        return Excel::download(
            new CtdtDanhSachExport(CtdtDanhSach::truyVan($this->locTu($request))),
            $ten
        );
    }
```

- [ ] **Step 7: Thêm route**

Trong `routes/web.php`, ngay dưới dòng `bhyt.ctdt.fetch-data` (khoảng dòng 600):

```php
        Route::get('ctdt/xuat/danh-sach', 'BHYT\BHYTCtdtController@xuatDanhSach')
            ->name('bhyt.ctdt.xuat.danh-sach');
```

- [ ] **Step 8: Thêm nút vào màn danh sách**

Trong `resources/views/bhyt/ctdt/index.blade.php`, cạnh các nút hiện có phía trên bảng:

```blade
<a href="#" id="btn-xuat-danh-sach" class="btn btn-success btn-sm">
    <i class="fa fa-file-excel-o"></i> Xuất danh sách
</a>
```

và trong khối `<script>`, thêm:

```javascript
    // Ghep DUNG bo loc dang xem vao URL tai: nut tai ma bo qua bo loc se cho ra mot tep
    // khac han bang dang hien, va nguoi dung se tuong man hinh sai.
    $('#btn-xuat-danh-sach').on('click', function (e) {
        e.preventDefault();
        window.location = '{{ route('bhyt.ctdt.xuat.danh-sach') }}?' + $.param(thamSoLoc());
    });
```

Trong đó `thamSoLoc()` là hàm đang dựng tham số cho DataTables. **Đọc mã hiện có trong tệp trước khi viết** — nếu tệp đang dựng tham số nội tuyến trong `ajax.data`, hãy rút nó thành hàm `thamSoLoc()` và cho cả hai nơi cùng gọi, chứ **đừng chép** khối tham số ra chỗ thứ hai.

- [ ] **Step 9: Kiểm bằng mắt**

Mở màn danh sách, đặt một bộ lọc bất kỳ (ví dụ trạng thái "Còn lỗi chặn"), bấm "Xuất danh sách". Mở tệp và đếm số dòng — phải khớp số bản ghi DataTables đang báo.

- [ ] **Step 10: Đột biến bắt buộc**

Commit trước, rồi lần lượt (hoàn nguyên từng tệp một):
1. Xoá một phần tử khỏi `headings()` → `so_cot_tieu_de_khop_so_o_moi_dong` phải ĐỎ.
2. Thay `CtdtTrangThaiGui::nhan(CtdtTrangThaiGui::cua($hoSo))` bằng chuỗi cứng `'Đã gửi'` → `nhan_trang_thai_lay_tu_CtdtTrangThaiGui_chu_khong_go_lai` phải ĐỎ.

- [ ] **Step 11: Commit**

```bash
git add app/Exports/CtdtDanhSachExport.php app/Http/Controllers/BHYT/BHYTCtdtController.php routes/web.php resources/views/bhyt/ctdt/index.blade.php tests/Unit/Ctdt/CtdtXuatExcelTest.php
git commit -m "feat(ctdt): xuat Excel danh sach ho so theo bo loc dang xem"
```

---

### Task 2: Xuất bảng lỗi

**Files:**
- Create: `app/Exports/CtdtLoiExport.php`
- Modify: `app/Http/Controllers/BHYT/BHYTCtdtController.php` (thêm `xuatLoi()`)
- Modify: `routes/web.php`, `resources/views/bhyt/ctdt/index.blade.php`
- Test: `tests/Unit/Ctdt/CtdtXuatExcelTest.php` (thêm ca)

**Interfaces:**
- Consumes: `CtdtDanhSach::truyVan(array $loc)`; bảng `ctdt_loi` với các cột `ho_so_id`, `chung_tu_id`, `ma_loi`, `ten_truong`, `mo_ta`, `muc_do` (`chan` | `canh_bao`)
- Produces: `CtdtLoiExport::__construct($truyVan)` nhận `Builder` **của `ctdt_ho_so`** (không phải `ctdt_loi`); route `bhyt.ctdt.xuat.loi`

- [ ] **Step 1: Viết test đỏ trước**

Thêm vào `tests/Unit/Ctdt/CtdtXuatExcelTest.php`:

```php
    /** @test */
    public function bang_loi_xuat_ca_muc_chan_lan_canh_bao()
    {
        // Chi xuat muc chan la giau mat nua cong viec cua nguoi nhap lieu: canh bao hom nay
        // la loi chan cua dot siet sau. Nguoi doc phai thay ca hai va tu quyet uu tien.
        $nguon = file_get_contents(base_path('app/Exports/CtdtLoiExport.php'));

        $this->assertNotContains("where('muc_do', 'chan')", $nguon,
            'Khong duoc loc bo canh bao');
        $this->assertContains('Mức độ', $nguon,
            'Phai co cot Muc do de nguoi doc tu quyet uu tien');
    }

    /** @test */
    public function bang_loi_giu_ma_ho_so_o_moi_dong()
    {
        // Mot dong loi khong co ma ho so la mot dong khong ai di sua duoc. Bang nay duoc in
        // ra dua cho nguoi nhap lieu, tach hoan toan khoi man hinh.
        $xuat = new \App\Exports\CtdtLoiExport(\App\Models\BHYT\Ctdt\CtdtHoSo::query());

        $this->assertContains('Mã hồ sơ', $xuat->headings());
    }
```

- [ ] **Step 2: Chạy test cho chắc nó đỏ**

Chạy: `php vendor/bin/phpunit tests/Unit/Ctdt/CtdtXuatExcelTest.php`
Kỳ vọng: hai test mới ĐỎ với `Class 'App\Exports\CtdtLoiExport' not found`.

- [ ] **Step 3: Viết lớp xuất**

Tạo `app/Exports/CtdtLoiExport.php`:

```php
<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

/**
 * Mot dong = mot loi. Bang nay duoc in ra dua cho NGUOI NHAP LIEU di sua, tach hoan toan
 * khoi man hinh - nen moi dong phai tu du thong tin de tim lai ho so, khong duoc dua vao
 * viec nguoi doc dang mo man danh sach.
 *
 * Nhan truy van cua ctdt_ho_so (da loc theo man hinh) chu khong truy van ctdt_loi: bo loc
 * cua man hinh la bo loc theo HO SO (ngay nap, dich vu, co so). Loc thang tren ctdt_loi se
 * khong ap dung duoc nhung dieu kien do.
 *
 * XUAT CA muc chan LAN canh bao: chi xuat muc chan la giau mat nua cong viec - canh bao hom
 * nay la loi chan cua dot siet sau.
 */
class CtdtLoiExport implements FromQuery, WithHeadings, ShouldAutoSize, WithMapping, WithTitle
{
    /** @var \Illuminate\Database\Eloquent\Builder truy van ctdt_ho_so da loc */
    protected $truyVan;

    protected $stt = 0;

    public function __construct($truyVan)
    {
        $this->truyVan = $truyVan;
    }

    public function query()
    {
        // Chi lay ho so CO loi: mot bang loi day nhung dong "khong loi" la bang khong ai doc.
        return $this->truyVan
            ->where('so_loi', '>', 0)
            ->with(['loi', 'chungTu' => function ($q) {
                $q->select('id', 'ho_so_id', 'ma_the', 'ho_ten', 'loai_ho_so')->orderBy('id');
            }])
            ->orderByDesc('imported_at');
    }

    public function title(): string
    {
        return 'Loi';
    }

    public function headings(): array
    {
        return [
            'STT',
            'Mã hồ sơ',
            'Mã CSKCB',
            'Họ tên',
            'Số thẻ',
            'Loại chứng từ',
            'Mã lỗi',
            'Trường',
            'Mức độ',
            'Mô tả',
        ];
    }

    /**
     * Mot HO SO cho ra NHIEU dong - mot dong moi loi. WithMapping cho phep tra ve mang cua
     * mang de sinh nhieu dong tu mot ban ghi.
     */
    public function map($hoSo): array
    {
        $dau = $hoSo->relationLoaded('chungTu') ? $hoSo->chungTu->first() : null;
        $dong = [];

        foreach ($hoSo->loi as $loi) {
            $this->stt++;

            $dong[] = [
                $this->stt,
                (string) $hoSo->ma_ho_so,
                (string) $hoSo->macskcb,
                $dau ? (string) $dau->ho_ten : '',
                $dau ? (string) $dau->ma_the : '',
                $dau ? (string) $dau->loai_ho_so : '',
                (string) $loi->ma_loi,
                (string) $loi->ten_truong,
                // Giu nguyen ma 'chan'/'canh_bao' cua CSDL nhung doc duoc: nguoi nhap lieu
                // tu quyet uu tien, khong phai lop nay quyet ho.
                $loi->muc_do === 'chan' ? 'Chặn' : 'Cảnh báo',
                (string) $loi->mo_ta,
            ];
        }

        return $dong;
    }
}
```

⚠️ **Kiểm tra tên quan hệ trước khi viết.** `$hoSo->loi` phải là tên quan hệ thật trên model `CtdtHoSo`. Chạy `grep -n "function loi\|hasMany" app/Models/BHYT/Ctdt/CtdtHoSo.php` và dùng đúng tên tìm được. Nếu chưa có quan hệ nào tới `ctdt_loi`, thêm vào model:

```php
    public function loi()
    {
        return $this->hasMany(CtdtLoi::class, 'ho_so_id');
    }
```

- [ ] **Step 4: Chạy test cho chắc nó xanh**

Chạy: `php vendor/bin/phpunit tests/Unit/Ctdt/CtdtXuatExcelTest.php`
Kỳ vọng: `OK (6 tests)`.

- [ ] **Step 5: Thêm hành động, route, nút**

Controller — thêm `use App\Exports\CtdtLoiExport;` và:

```php
    /** Tai bang loi de dua nguoi nhap lieu di sua */
    public function xuatLoi(Request $request)
    {
        $ten = 'loi-chung-tu-dien-tu-' . now()->format('Ymd-His') . '.xlsx';

        return Excel::download(
            new CtdtLoiExport(CtdtDanhSach::truyVan($this->locTu($request))),
            $ten
        );
    }
```

`routes/web.php`:

```php
        Route::get('ctdt/xuat/loi', 'BHYT\BHYTCtdtController@xuatLoi')
            ->name('bhyt.ctdt.xuat.loi');
```

`index.blade.php` — nút và trình xử lý, theo đúng khuôn của Task 1:

```blade
<a href="#" id="btn-xuat-loi" class="btn btn-warning btn-sm">
    <i class="fa fa-file-excel-o"></i> Xuất bảng lỗi
</a>
```

```javascript
    $('#btn-xuat-loi').on('click', function (e) {
        e.preventDefault();
        window.location = '{{ route('bhyt.ctdt.xuat.loi') }}?' + $.param(thamSoLoc());
    });
```

- [ ] **Step 6: Kiểm bằng mắt**

Lọc trạng thái "Còn lỗi chặn", bấm "Xuất bảng lỗi". Mở tệp: số dòng phải bằng **tổng số lỗi**, không phải số hồ sơ — một hồ sơ 5 lỗi cho ra 5 dòng.

- [ ] **Step 7: Đột biến bắt buộc**

Commit trước, rồi thêm `->where('muc_do', 'chan')` vào `query()` → `bang_loi_xuat_ca_muc_chan_lan_canh_bao` phải ĐỎ. Hoàn nguyên bằng `git checkout -- app/Exports/CtdtLoiExport.php`.

- [ ] **Step 8: Commit**

```bash
git add app/Exports/CtdtLoiExport.php app/Http/Controllers/BHYT/BHYTCtdtController.php app/Models/BHYT/Ctdt/CtdtHoSo.php routes/web.php resources/views/bhyt/ctdt/index.blade.php tests/Unit/Ctdt/CtdtXuatExcelTest.php
git commit -m "feat(ctdt): xuat Excel bang loi de di sua du lieu"
```

---

### Task 3: Xuất nhật ký gửi

**Phụ thuộc: bảng `ctdt_lich_su_gui` từ Giai đoạn 5A Task 1.** Nếu bảng chưa có, dừng task này lại và báo — **đừng** thay bằng cách bóc tách cột văn bản `ctdt_ho_so.lich_su_gui`: cột đó là văn bản tự do do hai nơi cùng ghi với hai định dạng khác nhau, bóc tách nó là dựng một nguồn sự thật thứ hai trên nền cát.

**Files:**
- Create: `app/Exports/CtdtNhatKyGuiExport.php`
- Modify: `app/Http/Controllers/BHYT/BHYTCtdtController.php`, `routes/web.php`, `resources/views/bhyt/ctdt/index.blade.php`
- Test: `tests/Unit/Ctdt/CtdtXuatExcelTest.php` (thêm ca)

**Interfaces:**
- Consumes: model `App\Models\BHYT\Ctdt\CtdtLichSuGui` với các cột `ma_ho_so`, `nguoi_gui`, `nguon` (`man_hinh` | `console`), `ma_gd`, `ma_ket_qua`, `thoi_gian_tiep_nhan`, `thanh_cong`, `thong_diep`, `created_at`
- Produces: `CtdtNhatKyGuiExport::__construct($tuNgay, $denNgay)`; route `bhyt.ctdt.xuat.nhat-ky`

- [ ] **Step 1: Viết test đỏ trước**

Thêm vào `tests/Unit/Ctdt/CtdtXuatExcelTest.php`:

```php
    /** @test */
    public function nhat_ky_phan_biet_nguoi_bam_voi_lenh_nen()
    {
        // Cau hoi van hanh dau tien khi co su co: "dem qua LENH NEN gui bao nhieu ho so".
        // Khong phan biet duoc nguon thi cau hoi do khong tra loi duoc.
        $xuat = new \App\Exports\CtdtNhatKyGuiExport('2026-08-01', '2026-08-31');

        $this->assertContains('Nguồn', $xuat->headings());
    }

    /** @test */
    public function nhat_ky_bat_buoc_co_khoang_ngay()
    {
        // Bang nay chi tang, khong bao gio giam. Xuat khong gioi han khoang la mot truy van
        // toan bang tren may chu gioi han PHP 128MB.
        $nguon = file_get_contents(base_path('app/Exports/CtdtNhatKyGuiExport.php'));

        $this->assertContains('whereBetween', $nguon,
            'Phai gioi han theo khoang ngay, khong duoc xuat toan bang');
    }
```

- [ ] **Step 2: Chạy test cho chắc nó đỏ**

Chạy: `php vendor/bin/phpunit tests/Unit/Ctdt/CtdtXuatExcelTest.php`
Kỳ vọng: hai test mới ĐỎ.

- [ ] **Step 3: Viết lớp xuất**

Tạo `app/Exports/CtdtNhatKyGuiExport.php`:

```php
<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use App\Models\BHYT\Ctdt\CtdtLichSuGui;

/**
 * Mot dong = mot lan goi cong BHXH. Dung khi can dung lai chuyen da xay ra.
 *
 * KHONG doc cot van ban ctdt_ho_so.lich_su_gui: cot do do HAI noi cung ghi voi HAI dinh
 * dang khac nhau (CtdtLuuHoSo::noiLichSu ghi khi nap de, SubmitCtdtJob::noiLichSu ghi khi
 * gui), va boc tach van ban tu do la dung mot nguon su that thu hai tren nen cat.
 *
 * BUOC phai co khoang ngay: bang nay chi tang, khong bao gio giam. Xuat toan bang tren may
 * chu gioi han PHP 128MB la cach chac chan de het bo nho.
 */
class CtdtNhatKyGuiExport implements FromQuery, WithHeadings, ShouldAutoSize, WithMapping, WithTitle
{
    protected $tuNgay;
    protected $denNgay;
    protected $stt = 0;

    /**
     * @param string $tuNgay  'Y-m-d'
     * @param string $denNgay 'Y-m-d'
     */
    public function __construct($tuNgay, $denNgay)
    {
        $this->tuNgay = $tuNgay;
        $this->denNgay = $denNgay;
    }

    public function query()
    {
        return CtdtLichSuGui::query()
            ->whereBetween('created_at', [
                $this->tuNgay . ' 00:00:00',
                $this->denNgay . ' 23:59:59',
            ])
            ->orderBy('created_at');
    }

    public function title(): string
    {
        return 'Nhat ky gui';
    }

    public function headings(): array
    {
        return [
            'STT',
            'Thời điểm',
            'Mã hồ sơ',
            'Nguồn',
            'Người gửi',
            'Kết quả',
            'Mã kết quả',
            'Mã giao dịch',
            'Thời gian tiếp nhận',
            'Phản hồi của cổng',
        ];
    }

    public function map($dong): array
    {
        $this->stt++;

        return [
            $this->stt,
            (string) $dong->created_at,
            (string) $dong->ma_ho_so,
            // Cau hoi van hanh dau tien khi co su co: "dem qua LENH NEN gui bao nhieu".
            $dong->nguon === CtdtLichSuGui::NGUON_CONSOLE ? 'Lệnh nền' : 'Người bấm',
            (string) $dong->nguoi_gui,
            $dong->thanh_cong ? 'Cổng nhận' : 'Cổng từ chối',
            (string) $dong->ma_ket_qua,
            (string) $dong->ma_gd,
            (string) $dong->thoi_gian_tiep_nhan,
            (string) $dong->thong_diep,
        ];
    }
}
```

- [ ] **Step 4: Chạy test cho chắc nó xanh**

Chạy: `php vendor/bin/phpunit tests/Unit/Ctdt/CtdtXuatExcelTest.php`
Kỳ vọng: `OK (8 tests)`.

- [ ] **Step 5: Thêm hành động, route, nút**

Controller — thêm `use App\Exports\CtdtNhatKyGuiExport;` và:

```php
    /** Tai nhat ky gui trong mot khoang ngay */
    public function xuatNhatKy(Request $request)
    {
        // Lui ve 30 ngay gan nhat khi nguoi dung khong chon: bang nay chi tang, khong bao
        // gio giam, nen mac dinh "tat ca" la mot truy van toan bang.
        $tuNgay  = $request->input('tu_ngay') ?: now()->subDays(30)->format('Y-m-d');
        $denNgay = $request->input('den_ngay') ?: now()->format('Y-m-d');

        $ten = 'nhat-ky-gui-ctdt-' . $tuNgay . '-den-' . $denNgay . '.xlsx';

        return Excel::download(new CtdtNhatKyGuiExport($tuNgay, $denNgay), $ten);
    }
```

`routes/web.php`:

```php
        Route::get('ctdt/xuat/nhat-ky', 'BHYT\BHYTCtdtController@xuatNhatKy')
            ->name('bhyt.ctdt.xuat.nhat-ky');
```

`index.blade.php`:

```blade
<a href="#" id="btn-xuat-nhat-ky" class="btn btn-default btn-sm">
    <i class="fa fa-history"></i> Xuất nhật ký gửi
</a>
```

```javascript
    // Nhat ky chi nhan khoang ngay, KHONG nhan cac o loc khac: bang nay khong co cot dich
    // vu / co so, nen truyen chung vao chi tao ao giac da loc.
    $('#btn-xuat-nhat-ky').on('click', function (e) {
        e.preventDefault();
        window.location = '{{ route('bhyt.ctdt.xuat.nhat-ky') }}'
            + '?tu_ngay=' + encodeURIComponent(ctdtRange.from || '')
            + '&den_ngay=' + encodeURIComponent(ctdtRange.to || '');
    });
```

⚠️ **Khoảng ngày lấy từ `ctdtRange`, không từ ô nhập** — xem khối cảnh báo ở đầu kế hoạch.

- [ ] **Step 6: Chạy cả bộ test và kiểm blade biên dịch**

Chạy: `php vendor/bin/phpunit tests/Unit/Ctdt`
Kỳ vọng: đỏ **đúng một** — `CtdtCauHinhTest::gui_len_cong_mac_dinh_tat`.

`CtdtBladeCompilesTest` phải xanh. Nếu đỏ, nhiều khả năng có `@if`/`{{ }}` lọt vào trong chú thích JavaScript — lỗi này đã xảy ra một lần với chính tệp `detail.blade.php` và làm màn hình chết suốt từ Giai đoạn 2B mà không ai biết.

- [ ] **Step 7: Đột biến bắt buộc**

Commit trước, rồi bỏ `->whereBetween(...)` khỏi `query()` → `nhat_ky_bat_buoc_co_khoang_ngay` phải ĐỎ. Hoàn nguyên bằng `git checkout -- app/Exports/CtdtNhatKyGuiExport.php`.

- [ ] **Step 8: Commit**

```bash
git add app/Exports/CtdtNhatKyGuiExport.php app/Http/Controllers/BHYT/BHYTCtdtController.php routes/web.php resources/views/bhyt/ctdt/index.blade.php tests/Unit/Ctdt/CtdtXuatExcelTest.php
git commit -m "feat(ctdt): xuat Excel nhat ky gui theo khoang ngay"
```

---

## Việc phải nghiệm thu bằng tay

1. **Xuất danh sách với một bộ lọc hẹp**, đếm dòng, so với số DataTables đang báo. Lệch một dòng nghĩa là bộ lọc đã tách làm hai bản.
2. **Mở tệp bảng lỗi bằng Excel thật** (không phải LibreOffice) và kiểm tiếng Việt không bị vỡ mã.
3. **Xuất một khoảng ngày rộng** (ví dụ 90 ngày) trên máy chủ thật, canh thời gian và bộ nhớ. Máy chủ mới giới hạn PHP 128MB / 120 giây — nếu chạm trần thì đây là chỗ phải phân trang, và phải xử trước khi ai đó gặp lỗi trắng màn hình.
