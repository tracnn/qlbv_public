# Màn chi tiết chứng từ điện tử dạng modal — kế hoạch thực thi

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Xem chi tiết hồ sơ chứng từ điện tử ngay trên màn danh sách bằng modal, không phải chuyển trang rồi bấm quay lại — và trang chi tiết riêng vẫn sống, dùng chung đúng một bản thân.

**Architecture:** Thân chi tiết rút vào một partial **thuần đánh dấu**; toàn bộ JS gom vào một partial thứ hai dùng **uỷ nhiệm sự kiện** trên `document`. JS không biết nó đang ở trang riêng hay trong modal — xong việc nó chỉ phát sự kiện, chủ nhà tự quyết phản ứng.

**Tech Stack:** Laravel 5.5, PHP 7.4, PHPUnit 6, Blade, jQuery, Bootstrap 3 (AdminLTE 2), SweetAlert2, DataTables.

**Spec:** `docs/superpowers/specs/2026-08-21-ctdt-chi-tiet-dang-modal-design.md`

## Global Constraints

- **PHP 7.4 / Laravel 5.5 / PHPUnit 6 là sàn cứng.** Không cú pháp PHP 8 (không `match`, không thuộc tính nạp từ constructor, không `?->`, không dấu phẩy sau tham số cuối trong lời gọi hàm). Không `: void` trên `setUp()`.
- ⛔ **TUYỆT ĐỐI KHÔNG dùng `RefreshDatabase` hay `DatabaseMigrations`.** Chúng gọi `migrate:fresh` = DROP toàn bộ bảng, và ngày 2026-08-21 đã xoá sạch CSDL phát triển `qlbv` thật. Dùng `Tests\Support\DungBangCtdtSqlite`. `tests/Unit/ChotAnToanCsdlTest` sẽ đỏ nếu ai dùng lại chúng.
- **Máy này đã bật gửi thật lên cổng BHXH.** KHÔNG chạy `SignCtdtJob`, `SubmitCtdtJob`, `queue:work`, `php artisan migrate`.
- ⚠️ **Trong chú thích JavaScript, viết `@@if` (hai dấu a-còng), không phải một.** Blade dịch **mọi** chỉ thị nó thấy, kể cả trong chú thích JS; một chỉ thị không ngoặc sinh PHP hỏng và làm **cả trang** ném `Parse error`. Lỗi này đã làm `detail.blade.php` không render được suốt nhiều ngày mà không ai biết — trang chỉ vỡ khi có người mở nó.
- **`ma_ho_so` có thể chứa dấu `#`** (nhánh lùi GUID, ví dụ `Id-abc#1`). Mọi chỗ ghép vào URL phải `encodeURIComponent`.
- **Lớp `modal-xxl` đã có** trong `public/css/customize.css`. Dùng lại, đừng khai lớp mới.
- Chú thích trong mã viết **không dấu**; Markdown viết **có dấu**.
- **Baseline:** `php vendor/bin/phpunit tests/Unit/Ctdt` → **527 test, đỏ đúng một** (`CtdtCauHinhTest::gui_len_cong_mac_dinh_tat`, đỏ có chủ đích — đừng đụng).

## File Structure

| Tệp | Trách nhiệm |
|---|---|
| `resources/views/bhyt/ctdt/partials/than-chi-tiet.blade.php` | **Mới.** Toàn bộ thân chi tiết. Thuần đánh dấu, không một dòng `<script>`. |
| `resources/views/bhyt/ctdt/partials/js-chi-tiet.blade.php` | **Mới.** Một bản JS duy nhất, uỷ nhiệm sự kiện. |
| `resources/views/bhyt/ctdt/detail.blade.php` | Thành vỏ mỏng. |
| `resources/views/bhyt/ctdt/partials/nut-ky-va-gui.blade.php` | Bỏ khối `@push`. |
| `resources/views/bhyt/ctdt/index.blade.php` | Khung modal + nghe sự kiện. |
| `app/Http/Controllers/BHYT/BHYTCtdtController.php` | Thêm `detailThan()`. |
| `routes/web.php` | Thêm một route. |
| `tests/Unit/Ctdt/CtdtChiTietModalTest.php` | **Mới.** Chốt canh chống bẫy `@push` quay lại. |

---

### Task 1: Tách thân và JS thành hai partial dùng chung

**Phạm vi task này KHÔNG có modal.** Kết thúc task, trang chi tiết riêng phải hoạt động **y hệt như trước** — chỉ khác là ruột nó đã tách làm hai mảnh dùng lại được. Người duyệt có thể chấp nhận task này mà vẫn bác phần modal.

**Files:**
- Create: `resources/views/bhyt/ctdt/partials/than-chi-tiet.blade.php`
- Create: `resources/views/bhyt/ctdt/partials/js-chi-tiet.blade.php`
- Modify: `resources/views/bhyt/ctdt/detail.blade.php`
- Modify: `resources/views/bhyt/ctdt/partials/nut-ky-va-gui.blade.php`
- Test: `tests/Unit/Ctdt/CtdtChiTietModalTest.php`

**Interfaces:**
- Consumes: `$hoSo` (`App\Models\BHYT\Ctdt\CtdtHoSo`), `$tabs` (mảng từ `CtdtDetailTabs::cua($hoSo)`, mỗi phần tử có `ma`, `nhan`, `so_luong`)
- Produces:
  - `bhyt.ctdt.partials.than-chi-tiet` — nhận `$hoSo`, `$tabs`
  - `bhyt.ctdt.partials.js-chi-tiet` — không nhận biến nào
  - Hai sự kiện trên `document`: `ctdt:da-xep-hang`, `ctdt:da-xoa`
  - Phần tử bọc ngoài: `<div class="ctdt-chi-tiet" data-ma-ho-so="…" data-ma-gd="…">`

- [ ] **Step 1: Viết test đỏ trước**

Tạo `tests/Unit/Ctdt/CtdtChiTietModalTest.php`:

```php
<?php

namespace Tests\Unit\Ctdt;

use Tests\TestCase;

/**
 * Chot canh cho man chi tiet dang modal.
 *
 * VAN DE GOC: nut-ky-va-gui.blade.php tung tu day JS bang @push('after-scripts'). Chi thi
 * do CHI co tac dung khi view duoc render ben trong mot layout co @stack. Nap partial ay
 * bang AJAX vao modal thi khoi @push bi bo di KHONG MOT LOI BAO: nut "Ky va gui" van hien,
 * van bam duoc, va khong co gi xay ra.
 *
 * Do la hang loi te nhat trong module nay - im lang, va nam dung tren nut nguy hiem nhat.
 * Cac test duoi day canh cau truc chu khong dua vao ky luat nguoi sua sau.
 */
class CtdtChiTietModalTest extends TestCase
{
    /** @return string noi dung tho cua mot view blade */
    private function nguon($duongDanTuongDoi)
    {
        $duongDan = resource_path('views/bhyt/ctdt/' . $duongDanTuongDoi);

        $this->assertFileExists($duongDan);

        return file_get_contents($duongDan);
    }

    /** @test */
    public function than_chi_tiet_khong_chua_mot_dong_script_nao()
    {
        // Fragment khong co script thi khong co gi de roi. Day la ca chot canh chinh cua
        // task nay: no bien "dung quen @push" tu mot loi dan thanh mot bat bien co the kiem.
        $this->assertNotContains('<script', $this->nguon('partials/than-chi-tiet.blade.php'),
            'than-chi-tiet la fragment nap bang AJAX - script trong do se khong bao gio chay');
    }

    /** @test */
    public function than_chi_tiet_la_fragment_chu_khong_phai_trang()
    {
        $nguon = $this->nguon('partials/than-chi-tiet.blade.php');

        $this->assertNotContains('@extends', $nguon);
        $this->assertNotContains('@section', $nguon);
    }

    /** @test */
    public function nut_ky_va_gui_khong_con_push_after_scripts()
    {
        // Day dung la nguon cua loi. Neu @push quay lai day thi nut se im lang khi o trong
        // modal - va chi lo ra khi co nguoi bam that.
        $this->assertNotContains('@push', $this->nguon('partials/nut-ky-va-gui.blade.php'),
            'nut-ky-va-gui duoc nap bang AJAX vao modal; @push o day roi khong dau vet');
    }

    /** @test */
    public function trang_chi_tiet_dung_lai_hai_partial_chu_khong_chep_lai()
    {
        // Trang rieng va modal PHAI dung chung mot ban than. Hai ban se lech nhau, va khong
        // co dau hieu gi cho toi luc ai do doi chieu tung dong.
        $nguon = $this->nguon('detail.blade.php');

        $this->assertContains('bhyt.ctdt.partials.than-chi-tiet', $nguon);
        $this->assertContains('bhyt.ctdt.partials.js-chi-tiet', $nguon);
    }

    /** @test */
    public function js_dung_chung_khong_nhung_ma_ho_so_vao_ma()
    {
        // JS gan MOT LAN, luc trang tai - luc do chua biet ho so nao se duoc mo. Nhung
        // @json($hoSo->ma_ho_so) vao day nghia la moi ho so mo sau deu dung ma cua ho so
        // dau tien.
        $nguon = $this->nguon('partials/js-chi-tiet.blade.php');

        $this->assertNotContains('$hoSo->ma_ho_so', $nguon,
            'ma ho so phai doc tu DOM (data-ma-ho-so), khong nhung vao JS');
        $this->assertNotContains('$hoSo->ma_gd', $nguon);
    }

    /** @test */
    public function js_dung_chung_phat_su_kien_chu_khong_tu_quyet_dieu_huong()
    {
        // JS khong duoc biet no dang o trang rieng hay trong modal. Mot cho goi
        // location.reload() thang trong day la buoc modal phai nap lai ca trang - mat bo loc.
        $nguon = $this->nguon('partials/js-chi-tiet.blade.php');

        $this->assertContains('ctdt:da-xep-hang', $nguon);
        $this->assertContains('ctdt:da-xoa', $nguon);
        $this->assertNotContains('location.reload', $nguon,
            'chu nha quyet dieu huong, khong phai JS dung chung');
    }
}
```

- [ ] **Step 2: Chạy test cho chắc nó đỏ**

Chạy: `php vendor/bin/phpunit tests/Unit/Ctdt/CtdtChiTietModalTest.php`
Kỳ vọng: ĐỎ — `than-chi-tiet.blade.php` chưa tồn tại.

- [ ] **Step 3: Tạo `than-chi-tiet.blade.php`**

Chuyển **nguyên văn** phần thân của `detail.blade.php` sang tệp mới. Cụ thể: **dòng 12 đến dòng 102** của bản hiện tại — tức từ `<div class="panel panel-default">` tới thẻ đóng `</div>` của khối `nav-tabs-custom`.

**KHÔNG chuyển** dòng 10 (`@include('includes.message')`) — đó là thông điệp flash của phiên, chỉ thuộc về trang riêng. Trong modal nó sẽ hiện lại một thông báo cũ không liên quan tới hồ sơ đang xem.

**Giữ nguyên mọi chú thích giải thích** đang có trong khối đó — chúng ghi lại vì sao nhãn là "Lỗi chặn gửi" chứ không phải "Số lỗi", vì sao `signed_error` hiện trước `submit_error`, và vì sao `submitted_message` tách riêng. Đừng viết lại gọn hơn.

```blade
{{-- Than man chi tiet ho so chung tu dien tu.

     Bien vao: $hoSo (CtdtHoSo), $tabs (CtdtDetailTabs::cua($hoSo))

     TEP NAY KHONG DUOC CHUA <script>. No duoc nap bang AJAX vao modal tren man danh sach,
     va moi khoi @push trong mot fragment nap kieu do se bi bo di KHONG MOT LOI BAO - nut
     van hien, van bam duoc, va khong co gi xay ra. Moi hanh vi nam o
     bhyt.ctdt.partials.js-chi-tiet, gan bang uy nhiem su kien.

     CtdtChiTietModalTest canh dieu do. --}}
<div class="ctdt-chi-tiet"
     data-ma-ho-so="{{ $hoSo->ma_ho_so }}"
     data-ma-gd="{{ $hoSo->ma_gd }}">

    {{-- ... nguyen van khoi panel + nav-tabs-custom chuyen tu detail.blade.php ... --}}

</div>
```

⚠️ **Chỉ có MỘT thân chi tiết trên mỗi trang.** Tệp này dùng định danh (`#noi-dung-tab`, `#ctdt-tabs`, `#btn-ky-va-gui`), nên nhúng nó hai lần trên cùng một trang sẽ làm tab nạp nhầm chỗ. Ghi cảnh báo đó vào chú thích đầu tệp.

- [ ] **Step 4: Bỏ `@push` khỏi `nut-ky-va-gui.blade.php`**

Xoá **toàn bộ** khối từ `@push('after-scripts')` tới `@endpush`. Tệp chỉ còn chú thích đầu và khối `<div class="row">` chứa nút.

Cập nhật chú thích đầu tệp cho đúng sự thật mới:

```blade
{{-- Nut "Ky va gui" tren man chi tiet.

     Bien vao: $hoSo — ban ghi CtdtHoSo

     TEP NAY CHI CHUA DANH DAU. Hanh vi cua nut nam o bhyt.ctdt.partials.js-chi-tiet.
     Truoc day tep nay tu day JS bang @push('after-scripts'), va do la mot cai bay: khi
     than chi tiet duoc nap bang AJAX vao modal, khoi @push bi bo di khong mot loi bao -
     nut van hien, van bam duoc, va khong co gi xay ra.

     LUU Y: moi chi thi Blade nam trong chu thich JavaScript VAN duoc Blade dich va sinh ra
     PHP hong. Trong chu thich JS, viet @@if neu can nhac toi chi thi. --}}
```

- [ ] **Step 5: Tạo `js-chi-tiet.blade.php`**

Gom JS từ hai nguồn — khối `@push` của `detail.blade.php` và khối `@push` vừa xoá khỏi `nut-ky-va-gui.blade.php` — thành một bản, đổi sang uỷ nhiệm sự kiện.

```blade
{{-- Hanh vi cua man chi tiet ho so, dung chung cho TRANG RIENG va MODAL.

     UY NHIEM SU KIEN tren document: gan MOT LAN luc trang tai, va van chay voi than chi
     tiet duoc nap bang AJAX sau do. Do la ly do chon uy nhiem chu khong gan truc tiep.

     TEP NAY KHONG BIET no dang o trang rieng hay trong modal. Xong viec no chi PHAT SU KIEN:

       ctdt:da-xep-hang  - may chu da nhan lenh ky va gui
       ctdt:da-xoa       - ho so da bi xoa

     Chu nha tu quyet phan ung: trang rieng nap lai trang, man danh sach dong modal va nap
     lai rieng bang. KHONG truyen co $trongModal xuong day - mot tham so nhu the buoc moi
     hanh vi them sau nay phai re nhanh theo no, va so nhanh chi co tang.

     LUU Y: moi chi thi Blade nam trong chu thich JavaScript VAN duoc Blade dich. Trong chu
     thich JS, viet @@if neu can nhac toi chi thi. --}}
<script>
$(function () {
    var mauUrlTab = "{{ route('bhyt.ctdt.detail.tab', ['ma_ho_so' => '__MA__', 'loai' => '__LOAI__']) }}";
    var mauUrlGui = "{{ route('bhyt.ctdt.ky-va-gui', ['ma_ho_so' => '__MA__']) }}";
    var mauUrlXoa = "{{ route('bhyt.ctdt.delete', ['ma_ho_so' => '__MA__']) }}";
    var token = "{{ csrf_token() }}";

    /** Ma ho so doc tu DOM, khong nhung vao JS: luc gan handler chua biet ho so nao se mo. */
    function maHoSoCua(phanTu) {
        return $(phanTu).closest('.ctdt-chi-tiet').data('ma-ho-so');
    }

    function maGdCua(phanTu) {
        return $(phanTu).closest('.ctdt-chi-tiet').data('ma-gd');
    }

    // ma_ho_so co the chua dau '#' (nhanh lui GUID). Khong ma hoa thi trinh duyet cat tu
    // dau '#' va yeu cau tro sai ho so.
    function ghepUrl(mau, maHoSo) {
        return mau.replace('__MA__', encodeURIComponent(maHoSo));
    }

    function napTab(maHoSo, loai) {
        $('#noi-dung-tab').html('<p class="text-muted">Đang tải…</p>');

        $.get(ghepUrl(mauUrlTab, maHoSo).replace('__LOAI__', encodeURIComponent(loai)))
            .done(function (html) { $('#noi-dung-tab').html(html); })
            .fail(function () {
                $('#noi-dung-tab').html('<p class="text-danger">Không tải được nội dung tab.</p>');
            });
    }

    $(document).on('click', '#ctdt-tabs a', function (e) {
        e.preventDefault();
        $('#ctdt-tabs li').removeClass('active');
        $(this).closest('li').addClass('active');
        napTab(maHoSoCua(this), $(this).data('loai'));
    });

    /**
     * Nap tab dau tien. Chu nha goi ham nay sau khi da do than vao DOM - tren trang rieng
     * la ngay luc tai, trong modal la sau khi AJAX ve.
     */
    window.ctdtNapTabDau = function () {
        var dau = $('#ctdt-tabs a').first();

        if (dau.length) {
            napTab(maHoSoCua(dau), dau.data('loai'));
        }
    };

    $(document).on('click', '#btn-ky-va-gui', function () {
        var nut = $(this);
        var maHoSo = maHoSoCua(this);
        var maGd = maGdCua(this);

        // Swal.fire 'text' hien thi nhu van ban thuan (khong dien giai HTML), nen maHoSo va
        // maGd - von la du lieu tu XML va tu phan hoi cong - khong the bien thanh the HTML.
        var noiDung = 'Ký số và gửi hồ sơ ' + maHoSo + ' lên cổng BHXH? ' +
            (maGd ? 'Hồ sơ này đã gửi (MaGD ' + maGd + '); gửi lại sẽ ghi đè kết quả cũ. ' : '') +
            'Cổng nhận là nhận thật, việc này không hoàn tác được.';

        Swal.fire({
            title: 'Xác nhận gửi',
            text: noiDung,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Ký và gửi',
            cancelButtonText: 'Hủy'
        }).then(function (kq) {
            if (!kq.value) {
                return;
            }

            var url = ghepUrl(mauUrlGui, maHoSo);

            // xacNhanGuiLai = 1 o lan goi THU HAI. May chu tra can_xac_nhan khi ho so da
            // tung duoc gui len cong nhung dau vet (ma_gd) da bi mot lan nap lai xoa.
            function gui(xacNhanGuiLai) {
                nut.prop('disabled', true);

                var duLieu = { _token: token };

                if (xacNhanGuiLai) {
                    duLieu.xac_nhan_gui_lai = 1;
                }

                $.post(url, duLieu)
                    .done(function (data) {
                        // Hop xac nhan THU HAI: chi hien khi may chu doi xac nhan va lan goi
                        // nay chua mang co. Khong kiem xacNhanGuiLai thi mot phan hoi
                        // can_xac_nhan lap lai se sinh vong hoi vo tan.
                        if (data.can_xac_nhan && !xacNhanGuiLai) {
                            Swal.fire({
                                title: 'Hồ sơ đã từng được gửi lên cổng',
                                text: data.thong_diep,
                                icon: 'warning',
                                showCancelButton: true,
                                confirmButtonText: 'Vẫn gửi lại',
                                cancelButtonText: 'Hủy'
                            }).then(function (kq2) {
                                if (kq2.value) {
                                    gui(true);
                                }
                            });

                            return;
                        }

                        Swal.fire({
                            title: data.thanh_cong ? 'Đã xếp hàng' : 'Chưa gửi được',
                            text: data.thong_diep,
                            icon: data.thanh_cong ? 'success' : 'warning'
                        }).then(function () {
                            if (data.thanh_cong) {
                                $(document).trigger('ctdt:da-xep-hang', [maHoSo]);
                            }
                        });
                    })
                    .fail(function () {
                        Swal.fire('Lỗi', 'Không gọi được máy chủ. Thử lại sau.', 'error');
                    })
                    .always(function () {
                        nut.prop('disabled', false);
                    });
            }

            gui(false);
        });
    });

    // Nut "Xoa ho so" chi hien voi superadministrator, nhung van hoi lai truoc khi xoa:
    // xoa nham mot ho so co MaGD la mat dau vet doi soat voi BHXH.
    $(document).on('click', '#btn-xoa-ho-so', function () {
        var maHoSo = maHoSoCua(this);
        var maGd = maGdCua(this);

        var noiDung = 'Xóa hồ sơ ' + maHoSo + '? ' +
            (maGd ? 'Nếu hồ sơ đã gửi lên cổng BHXH thì dấu vết đối soát (MaGD ' + maGd + ') cũng mất theo. ' : '') +
            'Việc này không hoàn tác được.';

        Swal.fire({
            title: 'Xác nhận xóa hồ sơ',
            text: noiDung,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Xóa',
            cancelButtonText: 'Hủy',
            confirmButtonColor: '#d33'
        }).then(function (result) {
            if (!result.isConfirmed) {
                return;
            }

            $.ajax({
                url: ghepUrl(mauUrlXoa, maHoSo),
                type: 'DELETE',
                data: { _token: token },
                success: function () {
                    $(document).trigger('ctdt:da-xoa', [maHoSo]);
                },
                error: function () {
                    Swal.fire('Có lỗi xảy ra', 'Không xóa được hồ sơ. Vui lòng thử lại.', 'error');
                }
            });
        });
    });
});
</script>
```

⚠️ **Chú ý chỗ đổi lời văn:** tiêu đề hộp xác nhận đổi từ `'Hồ sơ đã từng được tiếp nhận'` sang `'Hồ sơ đã từng được gửi lên cổng'`. Lý do: `CtdtLuuHoSo::noiLichSu()` ghi dòng lịch sử khi `ma_gd` **hoặc** `ma_ket_qua` khác rỗng, nên một hồ sơ từng bị cổng **từ chối** cũng rơi vào nhánh này. Nói "đã được tiếp nhận" ở đó là nói sai với người vận hành. Thông điệp máy chủ đã sửa từ trước; đây là nửa còn lại.

- [ ] **Step 6: Rút gọn `detail.blade.php`**

```blade
@extends('adminlte::page')

@section('title', e('Chi tiết hồ sơ ' . $hoSo->ma_ho_so))

@section('content_header')
<h1>Chi tiết hồ sơ <small>{{ $hoSo->ma_ho_so }}</small></h1>
@stop

@section('content')
@include('includes.message')
@include('bhyt.ctdt.partials.than-chi-tiet', ['hoSo' => $hoSo, 'tabs' => $tabs])
@stop

@push('after-scripts')
@include('bhyt.ctdt.partials.js-chi-tiet')
<script>
$(function () {
    // THU TU QUAN TRONG: @include o tren dat window.ctdtNapTabDau ben trong mot $(function)
    // cua rieng no. jQuery chay cac ham san sang theo DUNG thu tu dang ky, va @include dung
    // truoc khoi nay, nen toi day ham da ton tai. Dao hai khoi la goi mot ham chua co.
    window.ctdtNapTabDau();

    // Trang rieng tu quyet dieu huong - xem chu thich trong js-chi-tiet ve vi sao JS dung
    // chung khong tu lam viec nay.
    $(document).on('ctdt:da-xep-hang', function () {
        location.reload();
    });

    $(document).on('ctdt:da-xoa', function () {
        window.location.href = "{{ route('bhyt.ctdt.index') }}";
    });
});
</script>
@endpush
```

- [ ] **Step 7: Chạy test cho chắc nó xanh**

Chạy: `php vendor/bin/phpunit tests/Unit/Ctdt/CtdtChiTietModalTest.php`
Kỳ vọng: `OK (6 tests)`.

Chạy: `php vendor/bin/phpunit tests/Unit/Ctdt`
Kỳ vọng: **527 test, đỏ đúng một** (`CtdtCauHinhTest::gui_len_cong_mac_dinh_tat`).

`CtdtBladeCompilesTest` phải xanh — nó quét cả `partials/`, nên hai tệp mới tự động được canh biên dịch. Nếu nó đỏ, gần như chắc chắn có chỉ thị Blade lọt vào chú thích JavaScript.

- [ ] **Step 8: Kiểm bằng mắt trang riêng**

Mở `/bhyt/ctdt/index`, bấm một hồ sơ để vào trang chi tiết. Xác nhận: khối thông tin hiện đủ, tab đầu tự nạp, bấm tab khác nạp được, nút "Ký và gửi" **có phản ứng** (hiện hộp xác nhận — rồi **bấm Hủy**, đừng gửi thật).

- [ ] **Step 9: Đột biến bắt buộc**

**Commit trước**, rồi lần lượt (hoàn nguyên bằng `git checkout -- <đường dẫn cụ thể>`, từng tệp một):

1. Thêm lại `@push('after-scripts')` vào `nut-ky-va-gui.blade.php` → `nut_ky_va_gui_khong_con_push_after_scripts` phải ĐỎ.
2. Thêm một `<script></script>` rỗng vào `than-chi-tiet.blade.php` → `than_chi_tiet_khong_chua_mot_dong_script_nao` phải ĐỎ.
3. Thay `$(document).trigger('ctdt:da-xep-hang', …)` bằng `location.reload()` trong `js-chi-tiet.blade.php` → `js_dung_chung_phat_su_kien_chu_khong_tu_quyet_dieu_huong` phải ĐỎ.

- [ ] **Step 10: Commit**

```bash
git add resources/views/bhyt/ctdt/partials/than-chi-tiet.blade.php resources/views/bhyt/ctdt/partials/js-chi-tiet.blade.php resources/views/bhyt/ctdt/detail.blade.php resources/views/bhyt/ctdt/partials/nut-ky-va-gui.blade.php tests/Unit/Ctdt/CtdtChiTietModalTest.php
git commit -m "refactor(ctdt): tach than va JS man chi tiet thanh partial dung chung"
```

---

### Task 2: Route trả thân chi tiết

**Files:**
- Modify: `app/Http/Controllers/BHYT/BHYTCtdtController.php`
- Modify: `routes/web.php`
- Test: `tests/Unit/Ctdt/CtdtChiTietModalTest.php` (thêm ca)

**Interfaces:**
- Consumes: `CtdtDetailTabs::cua($hoSo)`; `bhyt.ctdt.partials.than-chi-tiet` (Task 1)
- Produces: `BHYTCtdtController::detailThan($ma_ho_so)`; route tên `bhyt.ctdt.detail.than`, đường dẫn `ctdt/detail/{ma_ho_so}/than`

- [ ] **Step 1: Viết test đỏ trước**

Thêm vào `tests/Unit/Ctdt/CtdtChiTietModalTest.php`:

```php
    /** @test */
    public function route_than_duoc_khai_bao()
    {
        // Route nay la thu duy nhat modal goi. Thieu no thi modal mo ra rong, va loi chi lo
        // ra trong console cua trinh duyet.
        $nguon = file_get_contents(base_path('routes/web.php'));

        $this->assertContains("bhyt.ctdt.detail.than", $nguon);
        $this->assertContains('ctdt/detail/{ma_ho_so}/than', $nguon);
    }

    /** @test */
    public function detailThan_tra_ve_than_chi_tiet_chu_khong_phai_ca_trang()
    {
        // Tra ca trang thi modal se chua mot ban AdminLTE thu hai - menu long trong menu.
        $nguon = file_get_contents(
            base_path('app/Http/Controllers/BHYT/BHYTCtdtController.php')
        );

        $this->assertContains("bhyt.ctdt.partials.than-chi-tiet", $nguon,
            'detailThan() phai tra partial, khong tra view bhyt.ctdt.detail');
    }
```

- [ ] **Step 2: Chạy test cho chắc nó đỏ**

Chạy: `php vendor/bin/phpunit tests/Unit/Ctdt/CtdtChiTietModalTest.php`
Kỳ vọng: hai test mới ĐỎ.

- [ ] **Step 3: Thêm `detailThan()` vào controller**

Đặt ngay dưới `detail()`:

```php
    /**
     * Chi THAN cua man chi tiet, khong layout - de modal tren man danh sach nap bang AJAX.
     *
     * VI SAO KHONG dung lai detail(): detail() tra view co @extends('adminlte::page'), nap
     * vao modal se long mot ban AdminLTE thu hai vao trong ban dang chay - menu trong menu,
     * va hai bo JS cua cung mot thu viện chay song song.
     */
    public function detailThan($ma_ho_so)
    {
        $hoSo = CtdtHoSo::with('chungTu')->where('ma_ho_so', $ma_ho_so)->firstOrFail();

        return view('bhyt.ctdt.partials.than-chi-tiet', [
            'hoSo' => $hoSo,
            'tabs' => CtdtDetailTabs::cua($hoSo),
        ]);
    }
```

- [ ] **Step 4: Thêm route**

Trong `routes/web.php`, **ngay dưới** dòng `bhyt.ctdt.detail` (khoảng dòng 603):

```php
        Route::get('ctdt/detail/{ma_ho_so}/than', 'BHYT\BHYTCtdtController@detailThan')
            ->name('bhyt.ctdt.detail.than');
```

⚠️ **Đặt SAU `bhyt.ctdt.detail` nhưng TRƯỚC `bhyt.ctdt.detail.tab`** không bắt buộc về mặt khớp đường dẫn (`{ma_ho_so}` không nuốt dấu `/`), nhưng giữ ba route chi tiết cạnh nhau để người đọc thấy chúng là một nhóm.

- [ ] **Step 5: Chạy test cho chắc nó xanh**

Chạy: `php vendor/bin/phpunit tests/Unit/Ctdt/CtdtChiTietModalTest.php`
Kỳ vọng: `OK (8 tests)`.

- [ ] **Step 6: Kiểm bằng mắt**

Mở thẳng `/bhyt/ctdt/detail/<một mã hồ sơ>/than` trên trình duyệt. Kỳ vọng: **chỉ thấy thân trần**, không menu, không thanh bên, không tiêu đề trang. Nếu thấy giao diện AdminLTE đầy đủ thì partial còn `@extends`.

- [ ] **Step 7: Đột biến bắt buộc**

Commit trước, rồi đổi `view('bhyt.ctdt.partials.than-chi-tiet', …)` thành `view('bhyt.ctdt.detail', …)` → `detailThan_tra_ve_than_chi_tiet_chu_khong_phai_ca_trang` phải ĐỎ. Hoàn nguyên bằng `git checkout -- app/Http/Controllers/BHYT/BHYTCtdtController.php`.

- [ ] **Step 8: Commit**

```bash
git add app/Http/Controllers/BHYT/BHYTCtdtController.php routes/web.php tests/Unit/Ctdt/CtdtChiTietModalTest.php
git commit -m "feat(ctdt): route tra than chi tiet cho modal"
```

---

### Task 3: Modal trên màn danh sách

**Files:**
- Modify: `resources/views/bhyt/ctdt/index.blade.php`
- Test: `tests/Unit/Ctdt/CtdtChiTietModalTest.php` (thêm ca)

**Interfaces:**
- Consumes: route `bhyt.ctdt.detail.than` (Task 2); `window.ctdtNapTabDau()`, sự kiện `ctdt:da-xep-hang` / `ctdt:da-xoa` (Task 1)
- Produces: không có API mới

- [ ] **Step 1: Viết test đỏ trước**

Thêm vào `tests/Unit/Ctdt/CtdtChiTietModalTest.php`:

```php
    /** @test */
    public function man_danh_sach_co_khung_modal_va_nap_js_dung_chung()
    {
        $nguon = $this->nguon('index.blade.php');

        $this->assertContains('id="modal-ctdt"', $nguon, 'Thieu khung modal');
        $this->assertContains('bhyt.ctdt.partials.js-chi-tiet', $nguon,
            'Khong nap JS dung chung thi nut trong modal se im lang');
        $this->assertContains('modal-xxl', $nguon,
            'Dung lai lop modal-xxl da co trong public/css/customize.css');
    }

    /** @test */
    public function man_danh_sach_nghe_ca_hai_su_kien()
    {
        // Thieu mot trong hai thi modal van mo duoc, van gui duoc, nhung bang khong bao gio
        // cap nhat - nguoi dung se bam gui lan hai.
        $nguon = $this->nguon('index.blade.php');

        $this->assertContains('ctdt:da-xep-hang', $nguon);
        $this->assertContains('ctdt:da-xoa', $nguon);
    }

    /** @test */
    public function nut_chi_tiet_van_la_the_a_co_href_that()
    {
        // Ctrl+click va chuot giua phai mo duoc tab moi. <a href="#"> hoac <button> se giet
        // hanh vi do - va do la thoi quen cua dung nhung nguoi dung man nay nhieu nhat.
        $nguon = $this->nguon('index.blade.php');

        $this->assertNotContains('href="#"', $nguon,
            'Nut mo chi tiet phai tro URL that de ctrl+click con mo duoc tab moi');
    }
```

- [ ] **Step 2: Chạy test cho chắc nó đỏ**

Chạy: `php vendor/bin/phpunit tests/Unit/Ctdt/CtdtChiTietModalTest.php`
Kỳ vọng: ba test mới ĐỎ.

- [ ] **Step 3: Thêm khung modal**

Trong `index.blade.php`, cuối khối `@section('content')`, **sau** thẻ đóng của panel chứa bảng:

```blade
{{-- Modal chi tiet ho so. Than duoc nap bang AJAX tu bhyt.ctdt.detail.than.

     Chi co MOT than chi tiet tren trang nay - than dung dinh danh (#ctdt-tabs,
     #noi-dung-tab, #btn-ky-va-gui) nen khong duoc mo hai modal chi tiet cung luc. --}}
<div class="modal fade" id="modal-ctdt" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-xxl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Đóng">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title">Chi tiết hồ sơ <small id="modal-ctdt-ma"></small></h4>
            </div>
            <div class="modal-body" id="modal-ctdt-than"></div>
        </div>
    </div>
</div>
```

- [ ] **Step 4: Nạp JS dùng chung và nối dây**

Trong khối `@push('after-scripts')` của `index.blade.php`, **trước** đoạn script hiện có:

```blade
@include('bhyt.ctdt.partials.js-chi-tiet')
```

Rồi thêm vào cuối khối script hiện có (bên trong `$(function () { … })` đang có, hoặc một khối `$(function(){})` mới):

```javascript
    // Mo modal chi tiet. Chan click THUONG thoi - the <a> van giu href that nen ctrl+click
    // va chuot giua van mo tab moi nhu cu.
    $(document).on('click', '.ctdt-mo-chi-tiet', function (e) {
        if (e.ctrlKey || e.metaKey || e.shiftKey || e.which === 2) {
            return;
        }

        e.preventDefault();

        var maHoSo = $(this).data('ma-ho-so');

        // Xoa sach than cu TRUOC khi goi mang: de nguyen la moi nguoi dung doc nham ho so
        // truoc trong luc cho.
        $('#modal-ctdt-than').html('<p class="text-muted">Đang tải…</p>');
        $('#modal-ctdt-ma').text(maHoSo);
        $('#modal-ctdt').modal('show');

        $.get($(this).attr('href') + '/than')
            .done(function (html) {
                $('#modal-ctdt-than').html(html);
                window.ctdtNapTabDau();
            })
            .fail(function (xhr) {
                var loi = xhr.status === 404
                    ? 'Không tìm thấy hồ sơ này. Có thể nó vừa bị xóa.'
                    : 'Không tải được chi tiết hồ sơ. Thử lại sau.';

                $('#modal-ctdt-than').html('<p class="text-danger">' + loi + '</p>');
            });
    });

    // Man danh sach tu quyet phan ung - xem chu thich trong js-chi-tiet.
    // ajax.reload(null, false): GIU nguyen bo loc va trang dang xem. Truyen true (hoac bo
    // tham so) se nhay ve trang 1, va nguoi xu nhieu ho so lien tiep phai loc lai tu dau.
    $(document).on('ctdt:da-xep-hang ctdt:da-xoa', function () {
        $('#modal-ctdt').modal('hide');
        // Dung lai bien ctdtBang da co san trong tep nay (gan o khoang dong 82). Neu no
        // khong con trong pham vi thi dung $('#ctdt-list').DataTable() - cung mot doi tuong.
        ctdtBang.ajax.reload(null, false);
    });
```

⚠️ **`$(this).attr('href') + '/than'` chỉ đúng vì route thân là đường dẫn chi tiết cộng `/than`.** Nếu đường dẫn đổi, sửa cả hai chỗ. Cách này tránh phải nhúng thêm một mẫu URL nữa và tự lo `encodeURIComponent` — `href` do Blade sinh đã mã hoá sẵn.

- [ ] **Step 5: Gắn lớp và dữ liệu vào hai chỗ mở chi tiết**

Trong hàm `render` của cột `ma_ho_so` (khoảng dòng 125) và cột `action` (khoảng dòng 165), thêm `class="ctdt-mo-chi-tiet"` và `data-ma-ho-so`:

```javascript
                    var an = $('<div>').text(data).html();

                    return '<a class="ctdt-mo-chi-tiet" href="' + url + '" data-ma-ho-so="'
                        + an + '">' + an + '</a>' + canhBao;
```

```javascript
                    var an = $('<div>').text(data).html();

                    return '<a class="btn btn-xs btn-default ctdt-mo-chi-tiet" href="' + url
                        + '" data-ma-ho-so="' + an + '">Chi tiết</a>';
```

⚠️ **`ma_ho_so` đọc thẳng từ tệp XML bên ngoài.** Giá trị đó phải qua `$('<div>').text(x).html()` trước khi nối vào chuỗi HTML — đây là quy tắc đã có trong tệp, và nó áp cho **cả thuộc tính `data-ma-ho-so`**, không chỉ phần văn bản.

- [ ] **Step 6: Chạy test**

Chạy: `php vendor/bin/phpunit tests/Unit/Ctdt/CtdtChiTietModalTest.php`
Kỳ vọng: `OK (11 tests)`.

Chạy: `php vendor/bin/phpunit tests/Unit/Ctdt`
Kỳ vọng: **527 test, đỏ đúng một**.

`CtdtDatatableCotTest` và `CtdtBoLocDungPartialsTest` phải còn xanh — chúng canh cấu trúc bảng và bộ lọc của chính tệp này.

- [ ] **Step 7: Kiểm bằng mắt — đây là phần chính của task**

Mở `/bhyt/ctdt/index`, rồi:

1. Bấm một mã hồ sơ → modal mở, thân hiện, tab đầu tự nạp.
2. Bấm tab khác trong modal → nạp được.
3. Đóng modal, bấm hồ sơ **khác** → thân phải là hồ sơ mới, **không** thấy thoáng qua hồ sơ cũ.
4. **Ctrl+click** một mã hồ sơ → mở tab mới sang trang riêng, modal **không** bật lên.
5. Bấm "Ký và gửi" trong modal → hộp xác nhận hiện ra → **bấm Hủy**. (Đừng gửi thật trừ khi anh chủ động muốn.)
6. Đặt một bộ lọc, sang trang 2, mở modal rồi đóng → bộ lọc và trang **giữ nguyên**.

- [ ] **Step 8: Đột biến bắt buộc**

Commit trước, rồi lần lượt (hoàn nguyên từng tệp một):

1. Đổi `ajax.reload(null, false)` thành `ajax.reload()` → **không test nào bắt được**; đây là **lỗ hổng đã biết** của bộ test này (mất bộ lọc chỉ nhìn thấy bằng mắt). Ghi vào báo cáo, đừng lặng lẽ bỏ qua.
2. Xoá `@include('bhyt.ctdt.partials.js-chi-tiet')` khỏi `index.blade.php` → `man_danh_sach_co_khung_modal_va_nap_js_dung_chung` phải ĐỎ.

- [ ] **Step 9: Commit**

```bash
git add resources/views/bhyt/ctdt/index.blade.php tests/Unit/Ctdt/CtdtChiTietModalTest.php
git commit -m "feat(ctdt): xem chi tiet ho so bang modal ngay tren man danh sach"
```

---

## Việc phải nghiệm thu bằng tay — không test nào thay được

**Không có test JS hay test trình duyệt nào trong dự án này.** Toàn bộ bộ test của màn danh sách là khớp chuỗi mã nguồn Blade — cách đó về nguyên tắc không thấy được lỗi thoát ký tự trong ngữ cảnh thuộc tính, cũng không thấy được một cuộc đua thời gian. Cả hai lỗi Critical của nhánh này đều lọt qua ba vòng review từng task vì đúng lý do đó.

Làm các bước 1–9 trên môi trường có **`submit_enabled = false`**. Chỉ bước 10 mới chạm cổng thật.

1. **Modal mở được.** Bấm mã hồ sơ → modal mở, thân đủ khối thông tin và dải tab, tab đầu tự nạp. Bấm nút "Chi tiết" ở cột cuối: y hệt.
2. **Không lồng layout.** Trong modal không có menu trái AdminLTE, không có tiêu đề trang thứ hai. Console DevTools **trống**.
3. **Ctrl+click và chuột giữa** vẫn mở tab mới trỏ trang chi tiết riêng, ở **cả hai** chỗ mở.
4. **Chuyển tab trong modal**: bấm lần lượt hết các tab, nội dung đổi theo, thẻ tab sáng đúng cái vừa bấm.
5. **Hồ sơ có `#` trong mã** (nhánh lùi GUID): mở modal → đúng hồ sơ đó, tab nạp được. Tab Network: URL có `%23`, không bị cắt.
6. **Đổi hồ sơ.** Mở A, đóng, mở B → thân là B, tiêu đề là B, `data-ma-ho-so` trong DevTools là B.
7. **Nghiệm thu chống đua (Critical 2).** DevTools → Network → *Slow 3G*. Bấm A, đợi "Đang tải…", đóng modal, bấm B ngay. Thân cuối cùng phải là **B**, không bao giờ nhảy về A.
8. **Nghiệm thu chống chèn HTML (Critical 1).** Nạp một tệp XML có `MA_YTE` chứa dấu `"` (ví dụ `A" x="1`) → mở màn danh sách → không cửa sổ lạ, Console không lỗi, DOM cho thấy thuộc tính đóng đúng chỗ.
9. **Xoá hồ sơ** (`superadministrator`): nút hiện trong modal → hộp xác nhận nêu đúng `MaGD` → xác nhận → báo "Đã xóa", modal đóng, bảng nạp lại, **bộ lọc và trang giữ nguyên**, hồ sơ biến mất. Đăng nhập `xml-man` thường: nút không hiện.
10. **Ký và gửi — chạm cổng thật.** Chọn **một** hồ sơ. Hộp xác nhận phải nêu **đúng mã hồ sơ đang xem** → xác nhận → nút xám lại → "Đã xếp hàng" → modal đóng, bảng nạp lại giữ nguyên bộ lọc và trang. Sau đó **kiểm cổng BHXH: đúng MỘT giao dịch**, không phải hai.
11. **Đường xác nhận gửi lại:** hồ sơ có `lich_su_gui` nhưng `ma_gd` rỗng → hộp thứ hai ghi *"Hồ sơ đã từng được gửi lên cổng"* (không phải "đã từng được tiếp nhận"). Bấm Hủy → **không** POST thêm lần nào (kiểm tab Network).
12. **Đường lỗi:** xoá một hồ sơ ở tab khác rồi mở nó trong modal → hiện "Không tìm thấy hồ sơ này. Có thể nó vừa bị xóa.", không treo "Đang tải…".
13. **Trang chi tiết riêng vẫn sống:** mở thẳng `ctdt/detail/{ma}` → tab đầu tự nạp; ký-gửi xong thì **nạp lại trang**; xoá xong thì **về màn danh sách**. Đây là đường lùi khi modal hỏng.
