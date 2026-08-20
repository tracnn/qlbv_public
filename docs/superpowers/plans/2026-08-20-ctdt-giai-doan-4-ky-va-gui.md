# Chứng từ điện tử PL02 — Giai đoạn 4: Ký số và gửi lên cổng BHXH — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Người vận hành bấm một nút trên màn chi tiết, hồ sơ được dựng phong bì, ký số, gửi lên cổng BHXH, và `MaGD` cùng mã kết quả hiện ngay trên màn danh sách.

**Architecture:** Hai hàm thuần đứng trước mọi việc có tác dụng phụ — `CtdtQuyetDinhGui` quyết định có gửi hay không, `CtdtPhongBi` dựng gói XML một hồ sơ. Hai job tách rời: `SignCtdtJob` ký rồi lưu tệp, `SubmitCtdtJob` gửi rồi ghi kết quả. `CtdtSubmitService` lo giao thức PL02 (bảy trường trong body, retry-on-401 đọc từ **thân** phản hồi chứ không phải mã HTTP).

**Tech Stack:** Laravel 5.5, PHP 7.4, PHPUnit 6, Guzzle 6, MySQL / SQLite in-memory (test), hàng đợi database, `XMLSignService` (USB token ưu tiên, HSM sau).

## Global Constraints

- **Không dùng `: void` trên `setUp()`** — PHPUnit 6 khai `setUp()` không có kiểu trả về.
- **Không dùng cú pháp PHP 8** — không `match`, không `?->`, không constructor promotion, không named arguments, không `Foo::bar()::baz()` (gán qua biến trung gian). PHP 7.4 **có** typed properties và `?string`, được phép dùng.
- **Laravel 5.5 KHÔNG có `Request::boolean()`** — dùng `filter_var(..., FILTER_VALIDATE_BOOLEAN)`.
- **Không dùng `RefreshDatabase`** — `.env` trỏ `DB_DATABASE=qlbv` là CSDL phát triển thật. Dùng trait `Tests\Support\DungBangCtdtSqlite` (gọi `$this->chuanBiBangCtdt()` trong `setUp()`).
- **Test phải TỰ ĐẶT cấu hình** bằng `config([...])`, không phụ thuộc `config/organization.php` của máy.
- **Không `git add -f`** các tệp trong `.gitignore`: `config/organization.php`, `config/filesystems.php`, `config/database.php`, `config/auth.php`. Thư mục `.superpowers/` cũng bị gitignore.
- **Không sửa `truong()`, `$fillable`, hay migration nào** — ba nơi khai cột đang khớp và `tests/Unit/Ctdt/CtdtToanVenTest.php` canh. Mọi cột Giai đoạn 4 cần (`is_signed`, `sign_method`, `signed_at`, `signed_error`, `duong_dan_da_ky`, `submitted_at`, `submitted_by`, `submit_error`, `submitted_message`, `ma_gd`, `ma_ket_qua`, `thoi_gian_tiep_nhan`, `lich_su_gui`) **đã có sẵn từ Giai đoạn 1**.
- **Không sửa `App\Services\Xml3176\QuyetDinhGui`** — xem "Ba điều chỉnh" bên dưới.
- **An toàn hiển thị:** nội dung chứng từ và phản hồi của cổng đến từ bên ngoài. Trong blade dùng `{{ }}`, không `{!! !!}`. Trong JavaScript mọi giá trị nối vào HTML phải thoát. Nhánh này đã có ba lỗ hổng hiển thị bị bắt ở các giai đoạn trước.
- **`ma_ho_so` có thể chứa dấu `#`** (nhánh lùi GUID) — mọi chỗ ghép vào URL phải `encodeURIComponent`.
- **Chỉ thị Blade nằm trong chú thích JavaScript vẫn được Blade dịch** và sinh ra PHP hỏng. Đây là lỗi đã làm màn chi tiết không render được suốt nhiều ngày. `tests/Unit/Ctdt/CtdtBladeCompilesTest.php` canh cả `views/bhyt/ctdt/` lẫn `partials/` — đừng làm nó đỏ.
- **Baseline (2026-08-20):** `php vendor/bin/phpunit --testsuite Unit` cho `Errors: 4, Failures: 7`; `--testsuite Feature` cho `Errors: 8, Failures: 4`. `tests/Unit/Ctdt` hiện `OK (325 tests)`.
- **Đặc tả nguồn:** `docs/superpowers/specs/2026-08-19-chung-tu-dien-tu-pl02-design.md` mục 2.2, 5.5, 5.6, 5.7, 6.1, 6.2, 6.3, và mục 14 (ghi chú chuyển tiếp Giai đoạn 3).

---

## Ba điều chỉnh so với đặc tả

**(1) Viết `App\Services\Ctdt\CtdtQuyetDinhGui` mới, KHÔNG mở rộng `App\Services\Xml3176\QuyetDinhGui`.**
Đặc tả mục 5.5 nói "thêm một hằng và một nhánh vào lớp sẵn có". Nhưng `QuyetDinhGui::nen($guiBat, $daKy)`
đang được **hai** dịch vụ sản phẩm gọi với đúng hai tham số (`Xml3176Service.php:1820`,
`Qd130XmlService.php:1711`), và `tests/Unit/QuyetDinhGuiTest.php` khoá đủ mọi tổ hợp của chữ ký đó.
Thêm tham số bắt buộc là làm vỡ hai đường sản phẩm không liên quan; thêm tham số tuỳ chọn là nhét khái
niệm `so_loi` / `checked_at` — thứ XML3176 không có — vào một lớp dùng chung. Lớp mới rẻ hơn cả hai.

**(2) Cờ bật/tắt kiểm TRƯỚC, đúng như đặc tả mục 5.5 — khác thứ tự của `CtdtTrangThaiGui`.**
Hai lớp trả lời hai câu hỏi khác nhau: `CtdtQuyetDinhGui` trả lời "có gửi không" (cờ tắt thì không
làm gì, kể cả ghi lỗi — ghi `submit_error` khi chưa hề thử gửi là bịa), còn `CtdtTrangThaiGui` trả lời
"hiện chữ gì cho người đọc" (người đọc cần biết hồ sơ còn lỗi ngay cả khi chức năng gửi đang tắt).
Thứ tự khác nhau là **có chủ đích**, không phải mâu thuẫn. Mục 14 của đặc tả hẹn "Giai đoạn 4 phải chốt
lại thứ tự này" — đây là chốt.

**(3) Phong bì phải mang sẵn thẻ rỗng `<CHUKYDONVI/>`.**
Đặc tả không nói rõ ai tạo thẻ này. Đối chiếu mã đang chạy: `Xml3176Service.php:1146` và
`Qd130XmlService.php:1056` đều `addChild('CHUKYDONVI')` **trước** khi gọi ký — tức dịch vụ ký ghi chữ ký
vào một thẻ đã tồn tại (`tag_store_signature_value = 'CHUKYDONVI'`), không tự tạo. `CtdtPhongBi` làm y vậy.

---

## Bối cảnh Giai đoạn 3 để lại

**Tệp XML gốc KHÔNG được lưu lại.** Cột `duong_dan_goc` chỉ ghi *tên tệp người dùng thấy*
(`BHYTCtdtController.php:205`), không phải đường dẫn trên đĩa. Chữ ký `CHUKYDONVI` của bên gửi đã bị
loại bỏ ngay lúc nạp. Vì vậy **không có đường "gửi nguyên tệp gốc"** — Giai đoạn 4 bắt buộc dựng lại
phong bì từ dữ liệu đã lưu rồi ký mới. Đây là ràng buộc, không phải lựa chọn.

**`checked_at` và `so_loi` đã được reset khi nạp đè.** `CtdtLuuHoSo::ghiHoSo()` đặt `checked_at => null`,
`so_loi => 0`, `is_signed => false` cho cả nhánh tạo mới lẫn cập nhật, và `CtdtLuuHoSoTest:206` canh.
Ghi chú "ngoài phạm vi" ở cuối mục 14 của đặc tả nói ngược lại là **sai** — đừng làm lại việc này.

**Trạng thái `CHUA_KIEM` đã có** trong `CtdtTrangThaiGui` (`checked_at` rỗng), đặt trước mọi kiểm tra khác.

---

## File Structure

**Tạo mới:**

| Tệp | Trách nhiệm |
|---|---|
| `app/Services/Ctdt/CtdtQuyetDinhGui.php` | Hàm thuần: bốn tham số → một trong năm quyết định |
| `app/Services/Ctdt/CtdtPhongBi.php` | Hàm thuần: hồ sơ + chứng từ → chuỗi XML gói một `HOSO` |
| `app/Services/Ctdt/CtdtSubmitService.php` | HTTP: bảy trường body, retry-on-401 đọc từ thân phản hồi |
| `app/Jobs/SignCtdtJob.php` | Dựng phong bì, ký, lưu tệp, ghi `is_signed`/`sign_method`/`duong_dan_da_ky` |
| `app/Jobs/SubmitCtdtJob.php` | Quyết định → gửi → ghi `ma_gd`/`ma_ket_qua`/`thoi_gian_tiep_nhan` |
| `resources/views/bhyt/ctdt/partials/nut-ky-va-gui.blade.php` | Nút và hộp xác nhận trên màn chi tiết |
| `tests/Unit/Ctdt/*Test.php` | 7 tệp test |

**Sửa:**

| Tệp | Sửa gì |
|---|---|
| `routes/web.php` | Thêm `POST bhyt/ctdt/{ma_ho_so}/ky-va-gui` |
| `app/Http/Controllers/BHYT/BHYTCtdtController.php` | Thêm `kyVaGui()` |
| `resources/views/bhyt/ctdt/detail.blade.php` | Nhúng partial nút, thêm khối kết quả gửi |
| `app/Jobs/CheckCtdtJob.php` | Thêm `failed()` |
| `install_service.bat` | Thêm hai dịch vụ worker `JobSignCtdt`, `JobSubmitCtdt` |
| `docs/chung-tu-dien-tu-pl02.md` | Mục vận hành cho ký và gửi |

---

## Task 1: `CtdtQuyetDinhGui` — quyết định có gửi hay không

**Files:**
- Create: `app/Services/Ctdt/CtdtQuyetDinhGui.php`
- Test: `tests/Unit/Ctdt/CtdtQuyetDinhGuiTest.php`

**Interfaces:**
- Consumes: không gì (hàm thuần, không đọc config, không đọc CSDL)
- Produces:
  - Hằng `CtdtQuyetDinhGui::GUI = 'gui'`, `::CHUA_KIEM = 'chua_kiem'`, `::CON_LOI = 'con_loi'`, `::CHUA_KY = 'chua_ky'`, `::KHONG_GUI = 'khong_gui'`
  - `CtdtQuyetDinhGui::nen($guiBat, $daKiem, $soLoi, $daKy): string`

- [ ] **Step 1: Viết test đỏ**

Tạo `tests/Unit/Ctdt/CtdtQuyetDinhGuiTest.php`:

```php
<?php

namespace Tests\Unit\Ctdt;

use Tests\TestCase;
use App\Services\Ctdt\CtdtQuyetDinhGui;

/**
 * Ham THUAN: bon tham so vao, mot chuoi ra. Khong doc config, khong doc CSDL - nen kiem
 * duoc het cac to hop ma khong can dung mot bang nao.
 */
class CtdtQuyetDinhGuiTest extends TestCase
{
    /** @test */
    public function du_dieu_kien_thi_GUI()
    {
        $this->assertSame(
            CtdtQuyetDinhGui::GUI,
            CtdtQuyetDinhGui::nen(true, true, 0, true)
        );
    }

    /** @test */
    public function co_tat_thi_KHONG_GUI_du_moi_thu_khac_deu_dat()
    {
        $this->assertSame(
            CtdtQuyetDinhGui::KHONG_GUI,
            CtdtQuyetDinhGui::nen(false, true, 0, true)
        );
    }

    /** @test */
    public function co_tat_thang_moi_ly_do_khac()
    {
        // THU TU QUAN TRONG: khi chuc nang gui dang tat thi khong co lan gui nao dien ra,
        // nen ghi submit_error "con loi" hay "chua ky" la BIA - nguoi doc se tuong da thu
        // gui va that bai. Co phai duoc hoi TRUOC MOI thu khac.
        $this->assertSame(CtdtQuyetDinhGui::KHONG_GUI, CtdtQuyetDinhGui::nen(false, false, 0, false));
        $this->assertSame(CtdtQuyetDinhGui::KHONG_GUI, CtdtQuyetDinhGui::nen(false, true, 9, false));
        $this->assertSame(CtdtQuyetDinhGui::KHONG_GUI, CtdtQuyetDinhGui::nen(false, false, 9, true));
    }

    /** @test */
    public function chua_kiem_thi_CHUA_KIEM_du_so_loi_bang_khong()
    {
        // so_loi = 0 cua mot ho so CHUA KIEM khong co nghia la sach - no co nghia la chua
        // ai nhin. Gui len cong mot ho so chua qua bo kiem la dung thu ma ca Giai doan 3
        // ton tai de chan.
        $this->assertSame(
            CtdtQuyetDinhGui::CHUA_KIEM,
            CtdtQuyetDinhGui::nen(true, false, 0, true)
        );
    }

    /** @test */
    public function chua_kiem_thang_con_loi_va_chua_ky()
    {
        $this->assertSame(CtdtQuyetDinhGui::CHUA_KIEM, CtdtQuyetDinhGui::nen(true, false, 5, true));
        $this->assertSame(CtdtQuyetDinhGui::CHUA_KIEM, CtdtQuyetDinhGui::nen(true, false, 5, false));
    }

    /** @test */
    public function con_loi_thi_CON_LOI()
    {
        $this->assertSame(
            CtdtQuyetDinhGui::CON_LOI,
            CtdtQuyetDinhGui::nen(true, true, 1, true)
        );
    }

    /** @test */
    public function con_loi_thang_chua_ky()
    {
        // Da kiem, con loi, chua ky: viec can lam truoc la SUA HO SO, khong phai di ky.
        $this->assertSame(
            CtdtQuyetDinhGui::CON_LOI,
            CtdtQuyetDinhGui::nen(true, true, 3, false)
        );
    }

    /** @test */
    public function da_kiem_sach_nhung_chua_ky_thi_CHUA_KY()
    {
        $this->assertSame(
            CtdtQuyetDinhGui::CHUA_KY,
            CtdtQuyetDinhGui::nen(true, true, 0, false)
        );
    }

    /** @test */
    public function ep_kieu_long_cho_moi_tham_so()
    {
        // is_signed doc tu MySQL tinyint(1) ve dang 0/1; cau hinh co the la chuoi;
        // checked_at la chuoi ngay gio hoac null; so_loi co the ve dang chuoi.
        // So sanh nghiem ngat o day se phan nhanh sai mot cach im lang.
        $this->assertSame(CtdtQuyetDinhGui::GUI, CtdtQuyetDinhGui::nen(1, '2026-08-20 08:00:00', '0', 1));
        $this->assertSame(CtdtQuyetDinhGui::GUI, CtdtQuyetDinhGui::nen('1', 1, 0, '1'));
        $this->assertSame(CtdtQuyetDinhGui::KHONG_GUI, CtdtQuyetDinhGui::nen(0, 1, 0, 1));
        $this->assertSame(CtdtQuyetDinhGui::KHONG_GUI, CtdtQuyetDinhGui::nen(null, 1, 0, 1));
        $this->assertSame(CtdtQuyetDinhGui::CHUA_KIEM, CtdtQuyetDinhGui::nen(true, null, 0, true));
        $this->assertSame(CtdtQuyetDinhGui::CHUA_KIEM, CtdtQuyetDinhGui::nen(true, '', 0, true));
        $this->assertSame(CtdtQuyetDinhGui::CON_LOI, CtdtQuyetDinhGui::nen(true, 1, '2', true));
        $this->assertSame(CtdtQuyetDinhGui::CHUA_KY, CtdtQuyetDinhGui::nen(true, 1, 0, null));
        $this->assertSame(CtdtQuyetDinhGui::CHUA_KY, CtdtQuyetDinhGui::nen(true, 1, 0, '0'));
    }

    /** @test */
    public function so_loi_am_khong_duoc_coi_la_con_loi()
    {
        // Khong luong duoc so am tu luong that, nhung ep (int) roi so sanh > 0 la cach
        // duy nhat khong bao gio chan nham mot ho so sach vi mot gia tri rac.
        $this->assertSame(CtdtQuyetDinhGui::GUI, CtdtQuyetDinhGui::nen(true, 1, -1, true));
    }

    /** @test */
    public function KHONG_dung_lai_lop_QuyetDinhGui_cua_xml3176()
    {
        // QuyetDinhGui::nen($guiBat, $daKy) dang duoc Xml3176Service va Qd130XmlService goi
        // voi dung HAI tham so, va tests/Unit/QuyetDinhGuiTest.php khoa du moi to hop.
        // Them tham so bat buoc vao do la lam vo hai duong san pham khong lien quan.
        $ma = file_get_contents(base_path('app/Services/Ctdt/CtdtQuyetDinhGui.php'));

        $this->assertNotContains('Xml3176', $ma,
            'CtdtQuyetDinhGui phai doc lap voi lop cua xml3176');
    }
}
```

- [ ] **Step 2: Chạy test để xác nhận đỏ**

```bash
php vendor/bin/phpunit tests/Unit/Ctdt/CtdtQuyetDinhGuiTest.php
```

Kỳ vọng: đỏ — `Class 'App\Services\Ctdt\CtdtQuyetDinhGui' not found`.

- [ ] **Step 3: Viết `CtdtQuyetDinhGui`**

Tạo `app/Services/Ctdt/CtdtQuyetDinhGui.php`:

```php
<?php

namespace App\Services\Ctdt;

/**
 * Quyet dinh co gui mot ho so chung tu dien tu len cong BHXH hay khong.
 *
 * VI SAO LOP RIENG chu khong mo rong App\Services\Xml3176\QuyetDinhGui: lop do dang duoc
 * Xml3176Service va Qd130XmlService goi voi dung hai tham so, va tests/Unit/QuyetDinhGuiTest
 * khoa du moi to hop. Them tham so bat buoc la lam vo hai duong san pham khong lien quan;
 * them tham so tuy chon la nhet khai niem so_loi/checked_at - thu XML3176 khong co - vao mot
 * lop dung chung.
 *
 * THU TU UU TIEN quan trong, va KHAC thu tu cua CtdtTrangThaiGui mot cach co chu dich:
 *
 *   lop nay tra loi "CO GUI KHONG"         -> co bat/tat hoi TRUOC
 *   CtdtTrangThaiGui tra loi "HIEN CHU GI" -> tinh trang ho so hoi truoc
 *
 * Khi chuc nang gui dang tat thi khong co lan gui nao dien ra, nen ghi submit_error la BIA:
 * nguoi doc se tuong da thu gui va that bai. Nguoc lai, nguoi doc man danh sach van can biet
 * ho so con loi ngay ca khi chuc nang gui dang tat.
 *
 * Ham THUAN de kiem duoc: khong doc config, khong doc CSDL.
 */
class CtdtQuyetDinhGui
{
    /** Du dieu kien: dung phong bi, ky, gui */
    const GUI = 'gui';

    /** Bo kiem chua chay xong - khong biet ho so co sach hay khong */
    const CHUA_KIEM = 'chua_kiem';

    /** Da kiem va con loi muc chan: ghi submit_error, khong goi mang */
    const CON_LOI = 'con_loi';

    /** Sach nhung chua ky so: ghi submit_error, khong goi mang */
    const CHUA_KY = 'chua_ky';

    /** Chuc nang gui dang tat: khong lam gi ca, ke ca ghi loi */
    const KHONG_GUI = 'khong_gui';

    /**
     * @param mixed $guiBat  config submit_enabled
     * @param mixed $daKiem  checked_at - rong nghia la bo kiem chua chay xong
     * @param mixed $soLoi   so_loi (chi dem loi muc chan)
     * @param mixed $daKy    is_signed
     * @return string mot trong GUI / CHUA_KIEM / CON_LOI / CHUA_KY / KHONG_GUI
     */
    public static function nen($guiBat, $daKiem, $soLoi, $daKy)
    {
        // Ep ve bool o moi nhanh: cac gia tri nay den tu MySQL tinyint(1) (dang 0/1), tu
        // config (co the la chuoi), va tu cot timestamp (chuoi hoac null). So sanh nghiem
        // ngat se phan nhanh sai mot cach im lang.
        if (!(bool) $guiBat) {
            return self::KHONG_GUI;
        }

        // so_loi = 0 cua mot ho so CHUA KIEM khong co nghia la sach - no co nghia la chua ai
        // nhin. Phai hoi TRUOC so_loi, khong thi may chu chua chay worker JobCtdt se gui moi
        // ho so len cong ma khong ai kiem.
        if (empty($daKiem)) {
            return self::CHUA_KIEM;
        }

        if ((int) $soLoi > 0) {
            return self::CON_LOI;
        }

        return (bool) $daKy ? self::GUI : self::CHUA_KY;
    }
}
```

- [ ] **Step 4: Chạy test để xác nhận xanh**

```bash
php vendor/bin/phpunit tests/Unit/Ctdt/CtdtQuyetDinhGuiTest.php
```

Kỳ vọng: `OK (11 tests)`.

- [ ] **Step 5: Commit**

```bash
git add app/Services/Ctdt/CtdtQuyetDinhGui.php tests/Unit/Ctdt/CtdtQuyetDinhGuiTest.php
git commit -m "feat(ctdt): CtdtQuyetDinhGui - quyet dinh co gui hay khong"
```

---

## Task 2: `CtdtPhongBi` — dựng gói XML một hồ sơ

**Files:**
- Create: `app/Services/Ctdt/CtdtPhongBi.php`
- Test: `tests/Unit/Ctdt/CtdtPhongBiTest.php`

**Interfaces:**
- Consumes: `config('ctdt.dich_vu')` (Giai đoạn 1) — đọc `the_goc` của từng dịch vụ
- Produces: `CtdtPhongBi::dung(array $hoSo, array $chungTu): string`
  - `$hoSo` = mảng `['dich_vu' =>, 'macskcb' =>, 'id_goi_xml' =>, 'ngay_lap' =>]`
  - `$chungTu` = mảng các `['loai_ho_so' =>, 'noi_dung_goc' =>]`
  - trả chuỗi XML đầy đủ, có khai báo `<?xml ... ?>`, có thẻ rỗng `<CHUKYDONVI/>`
  - ném `\InvalidArgumentException` khi dịch vụ lạ, danh sách chứng từ rỗng, hoặc nội dung chứng từ không parse được

- [ ] **Step 1: Viết test đỏ**

Tạo `tests/Unit/Ctdt/CtdtPhongBiTest.php`:

```php
<?php

namespace Tests\Unit\Ctdt;

use Tests\TestCase;
use App\Services\Ctdt\CtdtPhongBi;

/**
 * Ham THUAN: mang vao, chuoi XML ra. Khong doc CSDL.
 *
 * Phong bi nay la thu THAT SU duoc gui len cong BHXH, nen moi khang dinh o day deu la mot
 * dieu kien cong dat ra - khong phai so thich cua ta.
 */
class CtdtPhongBiTest extends TestCase
{
    private function hoSoCt2025(array $ghiDe = [])
    {
        return array_merge([
            'dich_vu'    => 'CT2025',
            'macskcb'    => '01929',
            'id_goi_xml' => 'Id-abc-123',
            'ngay_lap'   => '20260820',
        ], $ghiDe);
    }

    private function chungTuCt03()
    {
        return [[
            'loai_ho_so'   => 'CT03',
            'noi_dung_goc' => '<CT03><MA_YTE>YT001</MA_YTE></CT03>',
        ]];
    }

    /** @test */
    public function CT2025_dung_the_goc_HSCHUNGTU()
    {
        $xml = CtdtPhongBi::dung($this->hoSoCt2025(), $this->chungTuCt03());
        $goi = simplexml_load_string($xml);

        $this->assertNotFalse($goi, 'Phong bi phai la XML hop le');
        $this->assertSame('HSCHUNGTU', $goi->getName());
    }

    /** @test */
    public function CT2025_co_du_bon_tang_the()
    {
        $goi = simplexml_load_string(CtdtPhongBi::dung($this->hoSoCt2025(), $this->chungTuCt03()));

        $this->assertSame('01929', (string) $goi->THONGTINDONVI->MACSKCB);
        $this->assertSame('20260820', (string) $goi->THONGTINHOSO->NGAYLAP);
        $this->assertSame('Id-abc-123', (string) $goi->THONGTINHOSO['Id']);
        $this->assertCount(1, $goi->THONGTINHOSO->DANHSACHHOSO->HOSO);
    }

    /** @test */
    public function SOLUONGHOSO_luon_bang_1()
    {
        // Goi goc co the chua nhieu HOSO, nhung ta gui TUNG ho so mot vi trang thai va MaGD
        // deu theo tung ho so. Ghi lai so cua goi goc la khai bao sai voi cong.
        $goi = simplexml_load_string(CtdtPhongBi::dung(
            $this->hoSoCt2025(['so_luong_ho_so' => 7]),
            $this->chungTuCt03()
        ));

        $this->assertSame('1', (string) $goi->THONGTINHOSO->SOLUONGHOSO);
    }

    /** @test */
    public function noi_dung_chung_tu_duoc_ma_hoa_base64_va_giai_ra_dung_nguyen_van()
    {
        $goc = '<CT03><MA_YTE>YT001</MA_YTE><HO_TEN>Nguyen Van Test</HO_TEN></CT03>';

        $goi = simplexml_load_string(CtdtPhongBi::dung($this->hoSoCt2025(), [
            ['loai_ho_so' => 'CT03', 'noi_dung_goc' => $goc],
        ]));

        $file = $goi->THONGTINHOSO->DANHSACHHOSO->HOSO->FILEHOSO;

        $this->assertSame('CT03', (string) $file->LOAIHOSO);
        $this->assertSame($goc, base64_decode((string) $file->NOIDUNGFILE));
    }

    /** @test */
    public function nhieu_chung_tu_thanh_nhieu_FILEHOSO_trong_MOT_HOSO()
    {
        // Mot ho so co the co nhieu chung tu (giay ra vien + tom tat benh an). Tach chung
        // ra thanh nhieu HOSO la bien mot ho so thanh nhieu ho so truoc mat cong.
        $goi = simplexml_load_string(CtdtPhongBi::dung($this->hoSoCt2025(), [
            ['loai_ho_so' => 'CT03', 'noi_dung_goc' => '<CT03/>'],
            ['loai_ho_so' => 'CT04', 'noi_dung_goc' => '<CT04/>'],
        ]));

        $this->assertCount(1, $goi->THONGTINHOSO->DANHSACHHOSO->HOSO);
        $this->assertCount(2, $goi->THONGTINHOSO->DANHSACHHOSO->HOSO->FILEHOSO);
    }

    /** @test */
    public function co_the_rong_CHUKYDONVI_cho_dich_vu_ky_ghi_vao()
    {
        // Dich vu ky GHI chu ky vao mot the DA TON TAI (tag_store_signature_value =
        // 'CHUKYDONVI'), khong tu tao. Xml3176Service.php:1146 va Qd130XmlService.php:1056
        // deu addChild('CHUKYDONVI') truoc khi goi ky - lam khac di la ky xong khong co
        // chu ky nao trong tep.
        $goi = simplexml_load_string(CtdtPhongBi::dung($this->hoSoCt2025(), $this->chungTuCt03()));

        $this->assertTrue(isset($goi->CHUKYDONVI), 'Phai co the CHUKYDONVI rong');
        $this->assertSame('', trim((string) $goi->CHUKYDONVI));
    }

    /** @test */
    public function GBT_dung_the_goc_HSDLGBT_va_phang()
    {
        // Goi GBT/GCS phang hon: the goc chua TRUC TIEP mot GIAYBAOTU, khong co
        // THONGTINDONVI/THONGTINHOSO/DANHSACHHOSO, khong base64.
        $xml = CtdtPhongBi::dung(
            ['dich_vu' => 'GBT', 'macskcb' => '01929', 'id_goi_xml' => 'Id-gbt', 'ngay_lap' => null],
            [['loai_ho_so' => 'GIAYBAOTU', 'noi_dung_goc' => '<GIAYBAOTU Id="Id-gbt"><MA_GBT>G1</MA_GBT></GIAYBAOTU>']]
        );

        $goi = simplexml_load_string($xml);

        $this->assertSame('HSDLGBT', $goi->getName());
        $this->assertSame('G1', (string) $goi->GIAYBAOTU->MA_GBT);
        $this->assertFalse(isset($goi->THONGTINHOSO), 'Goi GBT khong co THONGTINHOSO');
        $this->assertTrue(isset($goi->CHUKYDONVI));
    }

    /** @test */
    public function GBT_giu_nguyen_thuoc_tinh_Id_cua_chung_tu()
    {
        // Chu ky XMLDSig tro toi Id-*; mat thuoc tinh Id la chu ky khong tham chieu duoc
        // vao dau, va cong tra 205.
        $goi = simplexml_load_string(CtdtPhongBi::dung(
            ['dich_vu' => 'GBT', 'macskcb' => '01929', 'id_goi_xml' => 'Id-gbt', 'ngay_lap' => null],
            [['loai_ho_so' => 'GIAYBAOTU', 'noi_dung_goc' => '<GIAYBAOTU Id="Id-gbt-999"><MA_GBT>G1</MA_GBT></GIAYBAOTU>']]
        ));

        $this->assertSame('Id-gbt-999', (string) $goi->GIAYBAOTU['Id']);
    }

    /** @test */
    public function GCS_dung_the_goc_HSDLGCS()
    {
        $goi = simplexml_load_string(CtdtPhongBi::dung(
            ['dich_vu' => 'GCS', 'macskcb' => '01929', 'id_goi_xml' => 'Id-gcs', 'ngay_lap' => null],
            [['loai_ho_so' => 'GIAYCHUNGSINH', 'noi_dung_goc' => '<GIAYCHUNGSINH Id="Id-gcs"><MA_GCS>C1</MA_GCS></GIAYCHUNGSINH>']]
        ));

        $this->assertSame('HSDLGCS', $goi->getName());
        $this->assertSame('C1', (string) $goi->GIAYCHUNGSINH->MA_GCS);
    }

    /** @test */
    public function noi_dung_goc_co_khai_bao_XML_van_ghep_duoc()
    {
        // CtdtLuuHoSo luu noi_dung_goc bang asXML() tren mot tai lieu da parse, nen chuoi
        // co the mang san dong khai bao <?xml ... ?>. Ghep thang vao giua mot tai lieu khac
        // la XML hong - phai cat bo truoc.
        $khaiBao = '<' . '?xml version="1.0" encoding="UTF-8"?' . '>';
        $goc = $khaiBao . "\n" . '<GIAYBAOTU Id="Id-1"><MA_GBT>G1</MA_GBT></GIAYBAOTU>';

        $xml = CtdtPhongBi::dung(
            ['dich_vu' => 'GBT', 'macskcb' => '01929', 'id_goi_xml' => 'Id-1', 'ngay_lap' => null],
            [['loai_ho_so' => 'GIAYBAOTU', 'noi_dung_goc' => $goc]]
        );

        $goi = simplexml_load_string($xml);

        $this->assertNotFalse($goi, 'Phong bi phai la XML hop le du noi dung goc co khai bao');
        $this->assertSame('G1', (string) $goi->GIAYBAOTU->MA_GBT);
    }

    /** @test */
    public function dich_vu_la_thi_nem()
    {
        $this->expectException(\InvalidArgumentException::class);

        CtdtPhongBi::dung($this->hoSoCt2025(['dich_vu' => 'KHONG_TON_TAI']), $this->chungTuCt03());
    }

    /** @test */
    public function danh_sach_chung_tu_rong_thi_nem()
    {
        // Mot phong bi khong co chung tu nao la mot goi rong gui len cong: cong nhan, tra
        // MaGD, va ta tuong da gui thanh cong mot ho so von khong co gi ben trong.
        $this->expectException(\InvalidArgumentException::class);

        CtdtPhongBi::dung($this->hoSoCt2025(), []);
    }

    /** @test */
    public function noi_dung_chung_tu_hong_thi_nem_chu_khong_gui_goi_thieu()
    {
        // Neu bo qua mot chung tu hong roi van gui, cong nhan mot ho so THIEU chung tu va
        // tra MaGD - hong im lang, khong lo ra cho toi luc doi soat.
        $this->expectException(\InvalidArgumentException::class);

        CtdtPhongBi::dung(
            ['dich_vu' => 'GBT', 'macskcb' => '01929', 'id_goi_xml' => 'Id-1', 'ngay_lap' => null],
            [['loai_ho_so' => 'GIAYBAOTU', 'noi_dung_goc' => '<GIAYBAOTU>chua dong the']]
        );
    }

    /** @test */
    public function ngay_lap_rong_thi_bo_the_NGAYLAP_chu_khong_ghi_rong()
    {
        $goi = simplexml_load_string(CtdtPhongBi::dung(
            $this->hoSoCt2025(['ngay_lap' => null]),
            $this->chungTuCt03()
        ));

        $this->assertFalse(isset($goi->THONGTINHOSO->NGAYLAP),
            'Thieu NGAYLAP thi bo han the, dung ghi mot the rong');
    }

    /** @test */
    public function ky_tu_dac_biet_trong_ma_co_so_duoc_thoat()
    {
        // macskcb den tu XML ben ngoai. Ghep thang vao chuoi XML la mo duong cho mot gia
        // tri chua '<' pha vo ca tai lieu - hoac te hon, chen them the.
        $xml = CtdtPhongBi::dung($this->hoSoCt2025(['macskcb' => 'A&B']), $this->chungTuCt03());

        $goi = simplexml_load_string($xml);

        $this->assertNotFalse($goi, 'XML phai con hop le');
        $this->assertSame('A&B', (string) $goi->THONGTINDONVI->MACSKCB);
    }
}
```

- [ ] **Step 2: Chạy test để xác nhận đỏ**

```bash
php vendor/bin/phpunit tests/Unit/Ctdt/CtdtPhongBiTest.php
```

Kỳ vọng: đỏ — `Class 'App\Services\Ctdt\CtdtPhongBi' not found`.

- [ ] **Step 3: Viết `CtdtPhongBi`**

Tạo `app/Services/Ctdt/CtdtPhongBi.php`:

```php
<?php

namespace App\Services\Ctdt;

/**
 * Dung goi XML chua DUNG MOT ho so, san sang de ky va gui.
 *
 * VI SAO PHAI DUNG LAI thay vi gui tep goc: tep XML nguoi dung tai len KHONG duoc luu lai -
 * cot duong_dan_goc chi ghi TEN tep nguoi dung thay, khong phai duong dan tren dia. Chu ky
 * CHUKYDONVI cua ben gui da bi loai bo ngay luc nap. Khong co duong "gui nguyen tep goc".
 *
 * VI SAO MOI HO SO MOT GOI: goi goc co the chua nhieu HOSO, nhung trang thai gui, MaGD va ma
 * ket qua deu theo TUNG ho so. Gui ca goi thi mot MaGD ung voi nhieu ho so, va khong the noi
 * ho so nao bi tu choi.
 *
 * Dung DOMDocument chu khong noi chuoi: macskcb, id_goi_xml va noi dung chung tu deu den tu
 * XML ben ngoai. Noi chuoi la mo duong cho mot gia tri chua '<' pha vo ca tai lieu.
 *
 * Ham THUAN: khong doc CSDL, khong ghi tep.
 */
class CtdtPhongBi
{
    /**
     * @param array $hoSo    ['dich_vu', 'macskcb', 'id_goi_xml', 'ngay_lap']
     * @param array $chungTu Cac ['loai_ho_so', 'noi_dung_goc']
     * @return string XML day du, co khai bao va the rong CHUKYDONVI
     * @throws \InvalidArgumentException
     */
    public static function dung(array $hoSo, array $chungTu)
    {
        $dichVu = isset($hoSo['dich_vu']) ? $hoSo['dich_vu'] : null;
        $cauHinh = config('ctdt.dich_vu.' . $dichVu);

        if (empty($cauHinh['the_goc'])) {
            throw new \InvalidArgumentException('Dich vu khong biet: ' . (string) $dichVu);
        }

        if (empty($chungTu)) {
            // Mot phong bi rong van duoc cong nhan va tra ve MaGD, va ta se tuong da gui
            // thanh cong mot ho so von khong co gi ben trong.
            throw new \InvalidArgumentException('Ho so khong co chung tu nao de gui');
        }

        $doc = new \DOMDocument('1.0', 'UTF-8');
        $doc->formatOutput = false;

        $goc = $doc->createElement($cauHinh['the_goc']);
        $doc->appendChild($goc);

        if ($cauHinh['the_goc'] === 'HSCHUNGTU') {
            self::dungLongNhau($doc, $goc, $hoSo, $chungTu);
        } else {
            self::dungPhang($doc, $goc, $chungTu);
        }

        // The RONG, dat CUOI. Dich vu ky GHI chu ky vao mot the da ton tai
        // (tag_store_signature_value = 'CHUKYDONVI'), khong tu tao - giong het cach
        // Xml3176Service va Qd130XmlService dang lam truoc khi goi ky.
        $goc->appendChild($doc->createElement('CHUKYDONVI'));

        return $doc->saveXML();
    }

    /** Goi HSCHUNGTU: THONGTINDONVI + THONGTINHOSO > DANHSACHHOSO > HOSO > FILEHOSO* */
    private static function dungLongNhau(\DOMDocument $doc, \DOMElement $goc, array $hoSo, array $chungTu)
    {
        $donVi = $doc->createElement('THONGTINDONVI');
        $donVi->appendChild(self::the($doc, 'MACSKCB', (string) $hoSo['macskcb']));
        $goc->appendChild($donVi);

        $thongTin = $doc->createElement('THONGTINHOSO');

        if (!empty($hoSo['id_goi_xml'])) {
            $thongTin->setAttribute('Id', (string) $hoSo['id_goi_xml']);
        }

        if (!empty($hoSo['ngay_lap'])) {
            // Thieu thi BO HAN the. Mot the NGAYLAP rong la mot ngay khong hop le gui len
            // cong, con thieu the thi cong tu quyet dinh.
            $thongTin->appendChild(self::the($doc, 'NGAYLAP', (string) $hoSo['ngay_lap']));
        }

        // LUON bang 1: goi nay chua dung mot ho so. Ghi lai so cua goi goc la khai bao sai.
        $thongTin->appendChild(self::the($doc, 'SOLUONGHOSO', '1'));

        $danhSach = $doc->createElement('DANHSACHHOSO');
        $motHoSo = $doc->createElement('HOSO');

        foreach ($chungTu as $ct) {
            $file = $doc->createElement('FILEHOSO');
            $file->appendChild(self::the($doc, 'LOAIHOSO', (string) $ct['loai_ho_so']));
            $file->appendChild(self::the($doc, 'NOIDUNGFILE', base64_encode((string) $ct['noi_dung_goc'])));
            $motHoSo->appendChild($file);
        }

        $danhSach->appendChild($motHoSo);
        $thongTin->appendChild($danhSach);
        $goc->appendChild($thongTin);
    }

    /** Goi HSDLGBT / HSDLGCS: the goc chua TRUC TIEP mot chung tu, khong base64 */
    private static function dungPhang(\DOMDocument $doc, \DOMElement $goc, array $chungTu)
    {
        foreach ($chungTu as $ct) {
            $manh = new \DOMDocument('1.0', 'UTF-8');

            // Cat bo khai bao XML neu co: CtdtLuuHoSo luu noi_dung_goc bang asXML() tren mot
            // tai lieu da parse, nen chuoi co the mang san khai bao. Ghep thang vao giua mot
            // tai lieu khac la XML hong.
            $nguon = preg_replace('/^\s*<\?xml[^>]*\?>\s*/i', '', (string) $ct['noi_dung_goc']);

            $truoc = libxml_use_internal_errors(true);
            $ok = $manh->loadXML($nguon);
            libxml_clear_errors();
            libxml_use_internal_errors($truoc);

            if (!$ok || $manh->documentElement === null) {
                // NEM chu khong bo qua: bo qua roi van gui thi cong nhan mot ho so THIEU
                // chung tu va tra MaGD - hong im lang, khong lo ra cho toi luc doi soat.
                throw new \InvalidArgumentException(
                    'Noi dung chung tu ' . (string) $ct['loai_ho_so'] . ' khong phai XML hop le'
                );
            }

            // importNode voi deep = true giu nguyen ca cay con VA cac thuoc tinh - trong do
            // co Id, thu ma chu ky XMLDSig tro toi. Mat Id la chu ky khong tham chieu duoc
            // vao dau va cong tra 205.
            $goc->appendChild($doc->importNode($manh->documentElement, true));
        }
    }

    /** Tao mot the co noi dung van ban, de createTextNode lo phan thoat ky tu dac biet */
    private static function the(\DOMDocument $doc, $ten, $giaTri)
    {
        $the = $doc->createElement($ten);
        $the->appendChild($doc->createTextNode($giaTri));

        return $the;
    }
}
```

- [ ] **Step 4: Chạy test để xác nhận xanh**

```bash
php vendor/bin/phpunit tests/Unit/Ctdt/CtdtPhongBiTest.php
```

Kỳ vọng: `OK (16 tests)`.

- [ ] **Step 5: Commit**

```bash
git add app/Services/Ctdt/CtdtPhongBi.php tests/Unit/Ctdt/CtdtPhongBiTest.php
git commit -m "feat(ctdt): CtdtPhongBi - dung goi XML mot ho so"
```

---

## Task 3: `CtdtSubmitService` — giao thức gửi PL02

**Files:**
- Create: `app/Services/Ctdt/CtdtSubmitService.php`
- Test: `tests/Unit/Ctdt/CtdtSubmitServiceTest.php`

**Interfaces:**
- Consumes: `App\Services\BHYTLoginService` (`getAccessToken()`, `getIdToken()`, `username()`, `password()`, `logout()`); `config('ctdt.dich_vu')` (Giai đoạn 1)
- Produces: `CtdtSubmitService::__construct(BHYTLoginService $loginService = null)` và
  `gui($xmlDaKy, $dichVu, $maCskcb): array` trả mảng
  `['ma_ket_qua' => string, 'ma_gd' => string|null, 'thoi_gian_tiep_nhan' => string|null, 'thong_diep' => string, 'nguyen_van' => string]`
- Thuộc tính riêng tên `httpClient` — test thay bằng `ReflectionProperty` (khuôn của `CongDuLieuYTeDienBienXmlSubmitServiceTest`)

**Ba điểm giao thức khác `BHYTXmlSubmitService` — đọc kỹ:**

1. **Tất cả bảy trường nằm trong BODY**, không có header tùy biến: `maCskcb`, `token`, `id_token`,
   `username`, `password`, `loaiHs`, `fileBase64Str`. `BHYTXmlSubmitService` đặt xác thực ở header
   (`accessToken`, `tokenId`, `passwordHash`) và dùng tên trường khác — **không ép chung một hàm**.
2. **Mã `401` nằm trong THÂN phản hồi** (`MaKetQua`), không phải mã HTTP. Cổng trả HTTP 200 kèm
   `{"MaKetQua":"401"}`. Đây là khác biệt lớn nhất so với khuôn retry của `CongDuLieuYTeDienBienXmlSubmitService`
   (vốn bắt HTTP 401). Bắt nhầm chỗ nghĩa là **không bao giờ retry**.
3. **Tên trường phản hồi viết hoa**: `MaGD`, `MaKetQua`, `ThoiGianTiepNhan`.

**Bẫy khóa mảng — đã cảnh báo trong `config/ctdt.php`:** PHP ép khóa mảng dạng chuỗi số thành `int`,
nên `'200' => ...` thành `200 => ...`. `foreach ($cfg as $ma => $mo) if ($ma === $maTuCong)` **luôn trượt**.
Tra bằng `array_key_exists()` hoặc so sánh lỏng (`==`), tuyệt đối không `===` với chuỗi.

- [ ] **Step 1: Viết test đỏ**

Tạo `tests/Unit/Ctdt/CtdtSubmitServiceTest.php`:

```php
<?php

namespace Tests\Unit\Ctdt;

use Tests\TestCase;
use App\Services\Ctdt\CtdtSubmitService;
use App\Services\BHYTLoginService;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;

/**
 * Lop login gia, ke thua lop that de giu dung chu ky phuong thuc.
 *
 * KHONG dung createMock(): PHPUnit 6 sinh deprecation ReflectionType voi cac lop co kieu
 * tra ve khai bao - da lam do test o cho khac trong du an nay.
 */
class FakeCtdtLoginService extends BHYTLoginService
{
    /** @var string[] */
    public $tokenSequence = ['token-1'];
    public $tokenCallCount = 0;
    public $logoutCallCount = 0;

    public function __construct()
    {
        // Bo qua constructor cha de khong khoi tao Guzzle/Config that.
    }

    public function getAccessToken(): string
    {
        $token = isset($this->tokenSequence[$this->tokenCallCount])
            ? $this->tokenSequence[$this->tokenCallCount]
            : 'fallback';
        $this->tokenCallCount++;

        return $token;
    }

    public function getIdToken(): string
    {
        return 'id-token';
    }

    public function username(): string
    {
        return 'tk01929';
    }

    public function password(): string
    {
        return 'md5-cua-mat-khau';
    }

    public function logout(): void
    {
        $this->logoutCallCount++;
    }
}

class CtdtSubmitServiceTest extends TestCase
{
    private function dungDichVu()
    {
        config(['ctdt.dich_vu' => [
            'CT2025' => [
                'ten' => 'Chứng từ TT25/2025', 'the_goc' => 'HSCHUNGTU', 'loai_hs' => '39',
                'url' => 'https://vi-du.test/api/chungtugw/GuiHoSoChungTu2025',
            ],
            'GBT' => [
                'ten' => 'Giấy báo tử', 'the_goc' => 'HSDLGBT', 'loai_hs' => '60',
                'url' => 'https://vi-du.test/api/hososuckhoe/guiGiayToDienTu',
            ],
        ]]);
    }

    private function dungService(MockHandler $mock, BHYTLoginService $login)
    {
        $this->dungDichVu();

        $service = new CtdtSubmitService($login);

        $client = new Client(['handler' => HandlerStack::create($mock)]);
        $ref = new \ReflectionProperty(CtdtSubmitService::class, 'httpClient');
        $ref->setAccessible(true);
        $ref->setValue($service, $client);

        return $service;
    }

    private function phanHoi(array $than, $maHttp = 200)
    {
        return new Response($maHttp, [], json_encode($than));
    }

    /** @test */
    public function gui_thanh_cong_tra_du_ba_truong()
    {
        $mock = new MockHandler([$this->phanHoi([
            'MaGD' => 'GD-001', 'MaKetQua' => '200', 'ThoiGianTiepNhan' => '20260820083000',
        ])]);

        $kq = $this->dungService($mock, new FakeCtdtLoginService())->gui('<HSCHUNGTU/>', 'CT2025', '01929');

        $this->assertSame('200', $kq['ma_ket_qua']);
        $this->assertSame('GD-001', $kq['ma_gd']);
        $this->assertSame('20260820083000', $kq['thoi_gian_tiep_nhan']);
    }

    /** @test */
    public function bay_truong_deu_nam_trong_BODY_khong_o_header()
    {
        // PL02 dat TAT CA trong body. BHYTXmlSubmitService dat xac thuc o header voi ten
        // truong khac - ep chung mot ham la cong tu choi ma khong noi vi sao.
        $than = null;
        $mock = new MockHandler([function ($request) use (&$than) {
            $than = (string) $request->getBody();

            return new Response(200, [], json_encode(['MaGD' => 'G', 'MaKetQua' => '200']));
        }]);

        $this->dungService($mock, new FakeCtdtLoginService())->gui('<HSCHUNGTU/>', 'CT2025', '01929');

        parse_str($than, $truong);
        $khoa = array_keys($truong);
        sort($khoa);

        $this->assertSame(
            ['fileBase64Str', 'id_token', 'loaiHs', 'maCskcb', 'password', 'token', 'username'],
            $khoa
        );
        $this->assertSame('01929', $truong['maCskcb']);
    }

    /** @test */
    public function loai_hs_lay_theo_dich_vu()
    {
        // 39 / 60 / 61 la ba loai ho so khac nhau. Gui giay bao tu voi loaiHs 39 la cong
        // nhan vao dung hang doi sai.
        $than = [];
        $mock = new MockHandler([
            function ($request) use (&$than) {
                parse_str((string) $request->getBody(), $than);

                return new Response(200, [], json_encode(['MaKetQua' => '200']));
            },
        ]);

        $this->dungService($mock, new FakeCtdtLoginService())->gui('<HSDLGBT/>', 'GBT', '01929');

        $this->assertSame('60', $than['loaiHs']);
    }

    /** @test */
    public function fileBase64Str_giai_ra_dung_XML_da_ky()
    {
        $xml = '<HSCHUNGTU><CHUKYDONVI>chu-ky</CHUKYDONVI></HSCHUNGTU>';
        $than = [];
        $mock = new MockHandler([
            function ($request) use (&$than) {
                parse_str((string) $request->getBody(), $than);

                return new Response(200, [], json_encode(['MaKetQua' => '200']));
            },
        ]);

        $this->dungService($mock, new FakeCtdtLoginService())->gui($xml, 'CT2025', '01929');

        $this->assertSame($xml, base64_decode($than['fileBase64Str']));
    }

    /** @test */
    public function token_va_username_lay_tu_CUNG_MOT_loginService()
    {
        // Neu token va tai khoan trong body thuoc hai co so khac nhau thi cong van nhan, va
        // ho so bi ghi sai don vi gui - hong IM LANG, khong lo ra cho toi luc doi soat.
        $than = [];
        $mock = new MockHandler([
            function ($request) use (&$than) {
                parse_str((string) $request->getBody(), $than);

                return new Response(200, [], json_encode(['MaKetQua' => '200']));
            },
        ]);

        $login = new FakeCtdtLoginService();
        $login->tokenSequence = ['token-cua-01929'];

        $this->dungService($mock, $login)->gui('<HSCHUNGTU/>', 'CT2025', '01929');

        $this->assertSame('token-cua-01929', $than['token']);
        $this->assertSame('tk01929', $than['username']);
        $this->assertSame('id-token', $than['id_token']);
        $this->assertSame('md5-cua-mat-khau', $than['password']);
    }

    /** @test */
    public function ma_401_trong_THAN_phan_hoi_thi_dang_nhap_lai_va_gui_lai_DUNG_MOT_LAN()
    {
        // Cong tra HTTP 200 kem MaKetQua 401 - KHONG phai HTTP 401. Bat nham cho nghia la
        // khong bao gio retry, va moi token het han thanh mot ho so gui hong.
        $token = [];
        $mock = new MockHandler([
            function ($request) use (&$token) {
                parse_str((string) $request->getBody(), $t);
                $token[] = $t['token'];

                return new Response(200, [], json_encode(['MaKetQua' => '401']));
            },
            function ($request) use (&$token) {
                parse_str((string) $request->getBody(), $t);
                $token[] = $t['token'];

                return new Response(200, [], json_encode(['MaKetQua' => '200', 'MaGD' => 'GD-002']));
            },
        ]);

        $login = new FakeCtdtLoginService();
        $login->tokenSequence = ['token-het-han', 'token-moi'];

        $kq = $this->dungService($mock, $login)->gui('<HSCHUNGTU/>', 'CT2025', '01929');

        $this->assertSame('200', $kq['ma_ket_qua']);
        $this->assertSame('GD-002', $kq['ma_gd']);
        $this->assertSame(['token-het-han', 'token-moi'], $token);
        $this->assertSame(1, $login->logoutCallCount, 'Phai xoa cache token dung 1 lan');
        $this->assertSame(2, $login->tokenCallCount, 'Phai lay token 2 lan');
    }

    /** @test */
    public function ca_hai_lan_401_thi_dung_lai_khong_lap_vo_han()
    {
        $mock = new MockHandler([
            $this->phanHoi(['MaKetQua' => '401']),
            $this->phanHoi(['MaKetQua' => '401']),
        ]);

        $login = new FakeCtdtLoginService();
        $login->tokenSequence = ['cu', 'van-hong'];

        $kq = $this->dungService($mock, $login)->gui('<HSCHUNGTU/>', 'CT2025', '01929');

        $this->assertSame('401', $kq['ma_ket_qua']);
        $this->assertSame(1, $login->logoutCallCount, 'Chi duoc dang nhap lai dung mot lan');
    }

    /** @test */
    public function ma_khac_401_thi_KHONG_dang_nhap_lai()
    {
        foreach (['205', '500', '1001'] as $ma) {
            $login = new FakeCtdtLoginService();
            $mock = new MockHandler([$this->phanHoi(['MaKetQua' => $ma])]);

            $kq = $this->dungService($mock, $login)->gui('<HSCHUNGTU/>', 'CT2025', '01929');

            $this->assertSame($ma, $kq['ma_ket_qua']);
            $this->assertSame(0, $login->logoutCallCount, 'Ma ' . $ma . ' khong duoc re-auth');
        }
    }

    /** @test */
    public function ma_ket_qua_kieu_SO_van_nhan_ra_la_401()
    {
        // Cong co the tra so 401 thay vi chuoi '401'. So sanh nghiem ngat se truot va bo
        // qua ca duong retry.
        $mock = new MockHandler([
            $this->phanHoi(['MaKetQua' => 401]),
            $this->phanHoi(['MaKetQua' => 200, 'MaGD' => 'GD-003']),
        ]);

        $login = new FakeCtdtLoginService();

        $kq = $this->dungService($mock, $login)->gui('<HSCHUNGTU/>', 'CT2025', '01929');

        $this->assertSame(1, $login->logoutCallCount);
        $this->assertSame('GD-003', $kq['ma_gd']);
    }

    /** @test */
    public function thong_diep_tra_ve_lay_tu_danh_muc_ma_ket_qua()
    {
        config(['ctdt.ma_ket_qua' => ['200' => 'Thành công', '205' => 'fileBase64Str không hợp lệ']]);

        $mock = new MockHandler([$this->phanHoi(['MaKetQua' => '205'])]);

        $kq = $this->dungService($mock, new FakeCtdtLoginService())->gui('<HSCHUNGTU/>', 'CT2025', '01929');

        $this->assertContains('fileBase64Str', $kq['thong_diep']);
    }

    /** @test */
    public function ma_ket_qua_ngoai_danh_muc_van_co_thong_diep_doc_duoc()
    {
        // BHXH co the them ma moi. Hien mot o trong la nguoi van hanh khong biet chuyen gi.
        config(['ctdt.ma_ket_qua' => ['200' => 'Thành công']]);

        $mock = new MockHandler([$this->phanHoi(['MaKetQua' => '999'])]);

        $kq = $this->dungService($mock, new FakeCtdtLoginService())->gui('<HSCHUNGTU/>', 'CT2025', '01929');

        $this->assertNotEmpty($kq['thong_diep']);
        $this->assertContains('999', $kq['thong_diep']);
    }

    /** @test */
    public function giu_nguyen_van_phan_hoi_de_doi_soat()
    {
        // Khi cong bao 205, nguyen van phan hoi la thu duy nhat doi chieu duoc.
        $mock = new MockHandler([$this->phanHoi(['MaKetQua' => '205', 'ChiTiet' => 'sai the goc'])]);

        $kq = $this->dungService($mock, new FakeCtdtLoginService())->gui('<HSCHUNGTU/>', 'CT2025', '01929');

        $this->assertContains('sai the goc', $kq['nguyen_van']);
    }

    /** @test */
    public function dich_vu_la_thi_nem_truoc_khi_goi_mang()
    {
        $this->dungDichVu();

        $this->expectException(\InvalidArgumentException::class);

        $mock = new MockHandler([]);
        $this->dungService($mock, new FakeCtdtLoginService())->gui('<X/>', 'KHONG_TON_TAI', '01929');
    }

    /** @test */
    public function loi_mang_thi_nem_de_hang_doi_thu_lai()
    {
        // Mang chap la loi TAM THOI. Nuot no thanh mot ket qua "that bai" se lam ho so mat
        // co hoi duoc hang doi thu lai.
        $mock = new MockHandler([new \GuzzleHttp\Exception\ConnectException(
            'Connection refused',
            new \GuzzleHttp\Psr7\Request('POST', 'https://vi-du.test')
        )]);

        $this->expectException(\Exception::class);

        $this->dungService($mock, new FakeCtdtLoginService())->gui('<HSCHUNGTU/>', 'CT2025', '01929');
    }
}
```

- [ ] **Step 2: Chạy test để xác nhận đỏ**

```bash
php vendor/bin/phpunit tests/Unit/Ctdt/CtdtSubmitServiceTest.php
```

Kỳ vọng: đỏ — `Class 'App\Services\Ctdt\CtdtSubmitService' not found`.

- [ ] **Step 3: Viết `CtdtSubmitService`**

Tạo `app/Services/Ctdt/CtdtSubmitService.php`:

```php
<?php

namespace App\Services\Ctdt;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;

use App\Services\BHYTLoginService;

/**
 * Gui mot goi chung tu dien tu da ky len cong BHXH theo Phu luc 02.
 *
 * VI SAO KHONG DUNG LAI BHYTXmlSubmitService: hai giao thuc khac nhau that su, khong phai
 * khac tham so. BHYTXmlSubmitService dat xac thuc o HEADER (accessToken, tokenId,
 * passwordHash) va body dung ten truong khac (loaiHoSo, maTinh, maCSKCB, fileHSBase64).
 * PL02 dat CA BAY TRUONG trong body voi ten khac han. Ep chung mot ham la cong tu choi ma
 * khong noi vi sao.
 *
 * BAY LON NHAT CUA GIAO THUC NAY: ma 401 nam trong THAN phan hoi (MaKetQua), khong phai ma
 * HTTP. Cong tra HTTP 200 kem {"MaKetQua":"401"}. Khuon retry cua
 * CongDuLieuYTeDienBienXmlSubmitService bat HTTP 401 - chep y nguyen sang day la khong bao
 * gio retry, va moi lan token het han thanh mot ho so gui hong.
 */
class CtdtSubmitService
{
    /** @var Client */
    private $httpClient;

    /** @var BHYTLoginService */
    private $loginService;

    public function __construct(BHYTLoginService $loginService = null)
    {
        $this->httpClient = new Client([
            'timeout'         => 60,
            'connect_timeout' => 5,
        ]);

        $this->loginService = $loginService ?: new BHYTLoginService();
    }

    /**
     * @param string $xmlDaKy Noi dung XML da ky
     * @param string $dichVu  CT2025 | GBT | GCS
     * @param string $maCskcb Ma co so cua CHINH ho so - phai cung co so voi token
     * @return array ['ma_ket_qua', 'ma_gd', 'thoi_gian_tiep_nhan', 'thong_diep', 'nguyen_van']
     * @throws \InvalidArgumentException|\Exception
     */
    public function gui($xmlDaKy, $dichVu, $maCskcb)
    {
        $cauHinh = config('ctdt.dich_vu.' . $dichVu);

        if (empty($cauHinh['url']) || empty($cauHinh['loai_hs'])) {
            throw new \InvalidArgumentException('Dich vu khong biet: ' . (string) $dichVu);
        }

        $base64 = base64_encode($xmlDaKy);

        // Ghi kich thuoc moi lan gui: tai lieu khong noi nguong cua ma 1001 (file size qua
        // dai), phai tu do tu thuc te.
        Log::info('CTDT gui ho so', [
            'dich_vu'    => $dichVu,
            'loai_hs'    => $cauHinh['loai_hs'],
            'so_ky_tu_base64' => strlen($base64),
        ]);

        $ketQua = $this->motLanGui($cauHinh, $base64, $maCskcb);

        // Ma 401 = token bi tu choi. Xoa cache token, dang nhap lai, thu lai DUNG MOT LAN.
        // BHYTLoginService cache token theo co so va token co the het han giua chung.
        if ($this->la401($ketQua['ma_ket_qua'])) {
            Log::warning('CTDT: token bi tu choi (MaKetQua 401), dang nhap lai va gui lai', [
                'dich_vu' => $dichVu,
            ]);

            $this->loginService->logout();

            $ketQua = $this->motLanGui($cauHinh, $base64, $maCskcb);
        }

        return $ketQua;
    }

    /** Mot lan goi mang, khong retry */
    private function motLanGui(array $cauHinh, $base64, $maCskcb)
    {
        // Token VA tai khoan lay tu CUNG mot loginService. Neu chung thuoc hai co so khac
        // nhau thi cong van nhan, va ho so bi ghi sai don vi gui - hong im lang, khong co
        // dau hieu gi cho toi luc doi soat.
        $body = [
            'maCskcb'       => (string) $maCskcb,
            'token'         => $this->loginService->getAccessToken(),
            'id_token'      => $this->loginService->getIdToken(),
            'username'      => $this->loginService->username(),
            'password'      => $this->loginService->password(),
            'loaiHs'        => (string) $cauHinh['loai_hs'],
            'fileBase64Str' => $base64,
        ];

        try {
            $response = $this->httpClient->post($cauHinh['url'], [
                'headers'     => ['Content-Type' => 'application/x-www-form-urlencoded'],
                'form_params' => $body,
            ]);

            $nguyenVan = (string) $response->getBody()->getContents();
        } catch (GuzzleException $e) {
            // NEM chu khong nuot: mang chap la loi TAM THOI. Bien no thanh mot ket qua
            // "that bai" se lam ho so mat co hoi duoc hang doi thu lai.
            Log::error('CTDT gui that bai (loi mang): ' . $e->getMessage(), [
                'url' => $cauHinh['url'],
            ]);

            throw new \Exception('Loi goi cong BHXH: ' . $e->getMessage(), 0, $e);
        }

        return $this->docPhanHoi($nguyenVan);
    }

    private function docPhanHoi($nguyenVan)
    {
        $than = json_decode($nguyenVan, true);

        if (!is_array($than)) {
            $than = [];
        }

        $maKetQua = isset($than['MaKetQua']) ? (string) $than['MaKetQua'] : '';

        return [
            'ma_ket_qua'          => $maKetQua,
            'ma_gd'               => isset($than['MaGD']) ? (string) $than['MaGD'] : null,
            'thoi_gian_tiep_nhan' => isset($than['ThoiGianTiepNhan'])
                ? (string) $than['ThoiGianTiepNhan'] : null,
            'thong_diep'          => $this->moTa($maKetQua),
            'nguyen_van'          => $nguyenVan,
        ];
    }

    /**
     * BAY KHOA MANG: PHP ep khoa mang dang chuoi so thanh int, vd '200' thanh int(200).
     * array_key_exists('200', $mang) van dung (PHP tu ep chuoi truy van thanh int), nhung
     * so sanh === giua khoa va chuoi thi LUON truot. Da canh bao trong config/ctdt.php.
     */
    private function moTa($maKetQua)
    {
        if ($maKetQua === '') {
            return 'Cong khong tra ve MaKetQua';
        }

        $danhMuc = config('ctdt.ma_ket_qua', []);

        if (array_key_exists($maKetQua, $danhMuc)) {
            return 'Mã ' . $maKetQua . ': ' . $danhMuc[$maKetQua];
        }

        // Ma ngoai danh muc van phai doc duoc: BHXH co the them ma moi, va hien mot o trong
        // la nguoi van hanh khong biet chuyen gi da xay ra.
        return 'Mã ' . $maKetQua . ': không có trong danh mục';
    }

    /** So sanh LONG: cong co the tra so 401 thay vi chuoi '401' */
    private function la401($maKetQua)
    {
        return $maKetQua !== '' && $maKetQua == '401';
    }
}
```

- [ ] **Step 4: Chạy test để xác nhận xanh**

```bash
php vendor/bin/phpunit tests/Unit/Ctdt/CtdtSubmitServiceTest.php
```

Kỳ vọng: `OK (14 tests)`.

- [ ] **Step 5: Commit**

```bash
git add app/Services/Ctdt/CtdtSubmitService.php tests/Unit/Ctdt/CtdtSubmitServiceTest.php
git commit -m "feat(ctdt): CtdtSubmitService - giao thuc PL02, retry-on-401 doc tu than phan hoi"
```

---

## Task 4: `SignCtdtJob` — dựng phong bì, ký, lưu tệp

**Files:**
- Create: `app/Jobs/SignCtdtJob.php`
- Test: `tests/Unit/Ctdt/SignCtdtJobTest.php`

**Interfaces:**
- Consumes: `CtdtPhongBi::dung()` (Task 2); `App\Services\XMLSignService::signXml($xml)` trả
  `['isSigned' => bool, 'data' => string, 'method' => string|null, 'error' => string|null]`;
  model `CtdtHoSo`, `CtdtChungTu`; disk `exportCtdt`
- Produces: `App\Jobs\SignCtdtJob::__construct($maHoSo)`, `handle(XMLSignService $signService)`.
  Ghi `is_signed`, `sign_method`, `signed_at`, `signed_error`, `duong_dan_da_ky`.

**Vì sao tách ký khỏi gửi:** ký hỏng do lý do cục bộ (USB token bị rút, HSM không phản hồi) còn gửi
hỏng do mạng. Gộp lại thì `tries = 3` sẽ ký lại ba lần chỉ vì mạng chập, mà ký là thao tác tốn thời
gian nhất trong chuỗi.

- [ ] **Step 1: Viết test đỏ**

Tạo `tests/Unit/Ctdt/SignCtdtJobTest.php`:

```php
<?php

namespace Tests\Unit\Ctdt;

use Tests\TestCase;
use Tests\Support\DungBangCtdtSqlite;
use Illuminate\Support\Facades\Storage;
use App\Jobs\SignCtdtJob;
use App\Services\XMLSignService;
use App\Models\BHYT\Ctdt\CtdtHoSo;
use App\Models\BHYT\Ctdt\CtdtChungTu;

/**
 * Lop ky gia, ke thua lop that de giu dung chu ky phuong thuc.
 * KHONG dung createMock(): PHPUnit 6 sinh deprecation ReflectionType voi lop co kieu tra ve.
 */
class FakeXMLSignService extends XMLSignService
{
    public $ketQua = ['isSigned' => true, 'data' => '<DAKY/>', 'method' => 'USB Token'];
    public $xmlNhanDuoc = null;
    public $soLanGoi = 0;

    public function __construct()
    {
        // Bo qua constructor cha de khong khoi tao Guzzle/Config that.
    }

    public function signXml($xmlContent)
    {
        $this->soLanGoi++;
        $this->xmlNhanDuoc = $xmlContent;

        return $this->ketQua;
    }
}

class SignCtdtJobTest extends TestCase
{
    use DungBangCtdtSqlite;

    protected function setUp()
    {
        parent::setUp();
        $this->chuanBiBangCtdt();
        Storage::fake('exportCtdt');
        config(['organization.chung_tu_dien_tu.sign_enabled' => true]);
    }

    private function hoSo(array $ghiDe = [])
    {
        $hoSo = CtdtHoSo::create(array_merge([
            'ma_ho_so' => 'YT001', 'dich_vu' => 'CT2025', 'loai_hs' => '39',
            'macskcb' => '01929', 'id_goi_xml' => 'Id-1', 'ngay_lap' => '20260820',
            'so_chung_tu' => 1, 'checked_at' => '2026-08-20 08:00:00', 'so_loi' => 0,
        ], $ghiDe));

        CtdtChungTu::create([
            'ho_so_id' => $hoSo->id, 'loai_ho_so' => 'CT03', 'ma_chung_tu' => 'YT001',
            'noi_dung_goc' => '<CT03><MA_YTE>YT001</MA_YTE></CT03>',
        ]);

        return $hoSo->fresh();
    }

    private function chay($ky, $maHoSo = 'YT001')
    {
        (new SignCtdtJob($maHoSo))->handle($ky);
    }

    /** @test */
    public function ky_thanh_cong_thi_ghi_du_bon_cot()
    {
        $this->hoSo();
        $ky = new FakeXMLSignService();

        $this->chay($ky);

        $hoSo = CtdtHoSo::first();

        $this->assertTrue((bool) $hoSo->is_signed);
        $this->assertSame('USB Token', $hoSo->sign_method);
        $this->assertNotNull($hoSo->signed_at);
        $this->assertNull($hoSo->signed_error);
        $this->assertNotEmpty($hoSo->duong_dan_da_ky);
    }

    /** @test */
    public function luu_tep_da_ky_len_disk_exportCtdt()
    {
        $this->hoSo();
        $ky = new FakeXMLSignService();
        $ky->ketQua = ['isSigned' => true, 'data' => '<HSCHUNGTU>DA-KY</HSCHUNGTU>', 'method' => 'HSM'];

        $this->chay($ky);

        $duongDan = CtdtHoSo::first()->duong_dan_da_ky;

        Storage::disk('exportCtdt')->assertExists($duongDan);
        $this->assertSame('<HSCHUNGTU>DA-KY</HSCHUNGTU>', Storage::disk('exportCtdt')->get($duongDan));
    }

    /** @test */
    public function ky_dung_phong_bi_dung_tu_du_lieu_da_luu()
    {
        $this->hoSo();
        $ky = new FakeXMLSignService();

        $this->chay($ky);

        $goi = simplexml_load_string($ky->xmlNhanDuoc);

        $this->assertNotFalse($goi, 'Phai dua XML hop le cho dich vu ky');
        $this->assertSame('HSCHUNGTU', $goi->getName());
        $this->assertTrue(isset($goi->CHUKYDONVI), 'Phai co the CHUKYDONVI de dich vu ky ghi vao');
        $this->assertSame('YT001', (string) simplexml_load_string(base64_decode(
            (string) $goi->THONGTINHOSO->DANHSACHHOSO->HOSO->FILEHOSO->NOIDUNGFILE
        ))->MA_YTE);
    }

    /** @test */
    public function ky_that_bai_thi_ghi_signed_error_va_KHONG_dat_is_signed()
    {
        $this->hoSo();
        $ky = new FakeXMLSignService();
        $ky->ketQua = ['isSigned' => false, 'data' => '<X/>', 'method' => 'HSM', 'error' => 'HSM khong phan hoi'];

        $this->chay($ky);

        $hoSo = CtdtHoSo::first();

        $this->assertFalse((bool) $hoSo->is_signed);
        $this->assertContains('HSM khong phan hoi', (string) $hoSo->signed_error);
        $this->assertEmpty($hoSo->duong_dan_da_ky, 'Ky that bai thi khong duoc de lai duong dan');
    }

    /** @test */
    public function ky_that_bai_thi_KHONG_ghi_tep_nao()
    {
        // Ghi mot tep chua ky vao duong da ky la de lai mot qua bom: lan gui sau doc dung
        // tep do va gui len cong mot goi khong co chu ky.
        $this->hoSo();
        $ky = new FakeXMLSignService();
        $ky->ketQua = ['isSigned' => false, 'data' => '<CHUA-KY/>', 'method' => null, 'error' => 'loi'];

        $this->chay($ky);

        $this->assertEmpty(Storage::disk('exportCtdt')->allFiles());
    }

    /** @test */
    public function co_sign_enabled_tat_thi_khong_lam_gi()
    {
        // Job co the nam cho trong hang doi rat lau; giua luc do cau hinh co the da bi tat.
        config(['organization.chung_tu_dien_tu.sign_enabled' => false]);
        $this->hoSo();
        $ky = new FakeXMLSignService();

        $this->chay($ky);

        $this->assertSame(0, $ky->soLanGoi);
        $this->assertFalse((bool) CtdtHoSo::first()->is_signed);
        $this->assertNull(CtdtHoSo::first()->signed_error, 'Co tat thi khong ghi loi - ghi la bia');
    }

    /** @test */
    public function ho_so_con_loi_chan_thi_khong_ky()
    {
        // Ky mot ho so con loi la ton mot thao tac dat nhat trong chuoi cho mot ho so chac
        // chan khong duoc gui.
        $this->hoSo(['so_loi' => 3]);
        $ky = new FakeXMLSignService();

        $this->chay($ky);

        $this->assertSame(0, $ky->soLanGoi);
        $this->assertFalse((bool) CtdtHoSo::first()->is_signed);
    }

    /** @test */
    public function ho_so_chua_kiem_thi_khong_ky()
    {
        // so_loi = 0 cua mot ho so chua kiem khong co nghia la sach.
        $this->hoSo(['checked_at' => null]);
        $ky = new FakeXMLSignService();

        $this->chay($ky);

        $this->assertSame(0, $ky->soLanGoi);
    }

    /** @test */
    public function ho_so_khong_ton_tai_thi_khong_nem()
    {
        // Ho so co the bi xoa trong luc job cho trong hang doi. Nem chi lam job that bai va
        // thu lai hai lan cho cung mot ket qua.
        $ky = new FakeXMLSignService();

        $this->chay($ky, 'KHONG_TON_TAI');

        $this->assertSame(0, $ky->soLanGoi);
    }

    /** @test */
    public function ho_so_khong_co_chung_tu_thi_ghi_loi_chu_khong_nem()
    {
        CtdtHoSo::create([
            'ma_ho_so' => 'YT002', 'dich_vu' => 'CT2025', 'loai_hs' => '39',
            'macskcb' => '01929', 'so_chung_tu' => 0,
            'checked_at' => '2026-08-20 08:00:00', 'so_loi' => 0,
        ]);

        $ky = new FakeXMLSignService();

        $this->chay($ky, 'YT002');

        $hoSo = CtdtHoSo::where('ma_ho_so', 'YT002')->first();

        $this->assertFalse((bool) $hoSo->is_signed);
        $this->assertNotEmpty($hoSo->signed_error);
        $this->assertSame(0, $ky->soLanGoi);
    }

    /** @test */
    public function chay_lai_thi_ghi_de_chu_khong_nhan_doi_tep()
    {
        // Hang doi co the giao lai job sau khi that bai giua chung.
        $this->hoSo();
        $ky = new FakeXMLSignService();

        $this->chay($ky);
        $this->chay($ky);

        $this->assertCount(1, Storage::disk('exportCtdt')->allFiles());
    }

    /** @test */
    public function duong_dan_khong_chua_ky_tu_nguy_hiem_cua_ma_ho_so()
    {
        // ma_ho_so co the chua '#' (nhanh lui GUID) va den tu XML ben ngoai. Ghep thang vao
        // duong dan tep la mo duong cho '../' di ra khoi thu muc.
        $hoSo = $this->hoSo(['ma_ho_so' => '../../hiem/YT#003']);

        $ky = new FakeXMLSignService();
        $this->chay($ky, '../../hiem/YT#003');

        $duongDan = CtdtHoSo::where('ma_ho_so', '../../hiem/YT#003')->first()->duong_dan_da_ky;

        $this->assertNotContains('..', (string) $duongDan);
        $this->assertNotContains('#', (string) $duongDan);
    }
}
```

- [ ] **Step 2: Chạy test để xác nhận đỏ**

```bash
php vendor/bin/phpunit tests/Unit/Ctdt/SignCtdtJobTest.php
```

Kỳ vọng: đỏ — `Class 'App\Jobs\SignCtdtJob' not found`.

- [ ] **Step 3: Viết `SignCtdtJob`**

Tạo `app/Jobs/SignCtdtJob.php`:

```php
<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

use App\Models\BHYT\Ctdt\CtdtHoSo;
use App\Services\Ctdt\CtdtPhongBi;
use App\Services\XMLSignService;

/**
 * Dung phong bi mot ho so, ky so, luu tep da ky.
 *
 * VI SAO TACH KHOI JOB GUI: ky hong do ly do CUC BO (USB token bi rut, HSM khong phan hoi)
 * con gui hong do MANG. Gop lai thi tries = 3 se ky lai ba lan chi vi mang chap, ma ky la
 * thao tac ton thoi gian nhat trong chuoi.
 *
 * Nhan MA HO SO chu khong nhan model: job co the nam cho trong hang doi rat lau, va mot
 * model serialize san se mang theo du lieu da cu.
 */
class SignCtdtJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** @var int Ky lai it lan hon gui: hong ky thuong la ly do cuc bo, thu lai it giup */
    public $tries = 2;

    /** @var int */
    public $timeout = 120;

    /** @var string */
    protected $maHoSo;

    public function __construct($maHoSo)
    {
        $this->maHoSo = $maHoSo;
    }

    public function handle(XMLSignService $signService)
    {
        // Kiem co TRUOC khi lam bat cu viec gi. Noi dispatch cung da kiem, nhung job co the
        // nam cho trong hang doi rat lau, giua luc do cau hinh co the da bi tat. Co tat thi
        // KHONG ghi signed_error - ghi la bia, nguoi doc se tuong da thu ky va that bai.
        if (!(bool) config('organization.chung_tu_dien_tu.sign_enabled', false)) {
            Log::info('SignCtdtJob: chuc nang ky dang tat, bo qua ' . $this->maHoSo);

            return;
        }

        $hoSo = CtdtHoSo::with('chungTu')->where('ma_ho_so', $this->maHoSo)->first();

        if ($hoSo === null) {
            // Ho so co the da bi xoa trong luc job cho trong hang doi.
            Log::info('SignCtdtJob: khong tim thay ho so ' . $this->maHoSo);

            return;
        }

        if (empty($hoSo->checked_at) || (int) $hoSo->so_loi > 0) {
            // Ky mot ho so chua kiem hoac con loi la ton thao tac dat nhat trong chuoi cho
            // mot ho so chac chan khong duoc gui.
            Log::info('SignCtdtJob: ho so chua du dieu kien ky', [
                'ma_ho_so' => $this->maHoSo,
                'da_kiem'  => !empty($hoSo->checked_at),
                'so_loi'   => (int) $hoSo->so_loi,
            ]);

            return;
        }

        try {
            $phongBi = CtdtPhongBi::dung([
                'dich_vu'    => $hoSo->dich_vu,
                'macskcb'    => $hoSo->macskcb,
                'id_goi_xml' => $hoSo->id_goi_xml,
                'ngay_lap'   => $hoSo->ngay_lap,
            ], $this->chungTu($hoSo));
        } catch (\InvalidArgumentException $e) {
            // Du lieu ho so khong dung phong bi duoc - ghi lai de nguoi van hanh doc, dung
            // nem: nem chi lam hang doi thu lai hai lan cho cung mot ket qua.
            $this->ghiLoi($hoSo, 'Khong dung duoc phong bi: ' . $e->getMessage());

            return;
        }

        $ketQua = $signService->signXml($phongBi);

        if (empty($ketQua['isSigned'])) {
            $this->ghiLoi(
                $hoSo,
                isset($ketQua['error']) ? (string) $ketQua['error'] : 'Ky so that bai khong ro ly do'
            );

            return;
        }

        // Chi ghi tep KHI DA KY THANH CONG. Ghi mot tep chua ky vao duong "da ky" la de lai
        // mot qua bom: lan gui sau doc dung tep do va gui len cong mot goi khong co chu ky.
        $duongDan = $this->duongDan($hoSo);
        Storage::disk('exportCtdt')->put($duongDan, $ketQua['data']);

        $hoSo->update([
            'is_signed'       => true,
            'sign_method'     => isset($ketQua['method']) ? $ketQua['method'] : null,
            'signed_at'       => now(),
            'signed_error'    => null,
            'duong_dan_da_ky' => $duongDan,
        ]);

        Log::info('SignCtdtJob: ky thanh cong', [
            'ma_ho_so' => $this->maHoSo,
            'phuong_thuc' => isset($ketQua['method']) ? $ketQua['method'] : null,
        ]);
    }

    /** @return array Cac ['loai_ho_so', 'noi_dung_goc'] theo dung thu tu da luu */
    private function chungTu(CtdtHoSo $hoSo)
    {
        $ketQua = [];

        foreach ($hoSo->chungTu as $ct) {
            $ketQua[] = [
                'loai_ho_so'   => $ct->loai_ho_so,
                'noi_dung_goc' => $ct->noi_dung_goc,
            ];
        }

        return $ketQua;
    }

    /**
     * Ten tep an toan tu ma ho so.
     *
     * ma_ho_so den tu the XML ben ngoai va co the chua '#' (nhanh lui GUID) hoac '../'.
     * Ghep thang vao duong dan tep la mo duong di ra khoi thu muc.
     */
    private function duongDan(CtdtHoSo $hoSo)
    {
        $ten = preg_replace('/[^A-Za-z0-9._-]/', '_', (string) $hoSo->ma_ho_so);

        if ($ten === '' || $ten === null) {
            $ten = 'ho-so-' . $hoSo->id;
        }

        return 'da-ky/' . $hoSo->dich_vu . '/' . $ten . '.xml';
    }

    private function ghiLoi(CtdtHoSo $hoSo, $loi)
    {
        Log::warning('SignCtdtJob: ' . $loi, ['ma_ho_so' => $this->maHoSo]);

        $hoSo->update([
            'is_signed'    => false,
            'sign_method'  => null,
            'signed_at'    => null,
            'signed_error' => $loi,
        ]);
    }

    public function failed(\Throwable $exception)
    {
        // Het luot thu ma khong ghi gi thi ho so o lai "chua ky" vinh vien, khong dau vet
        // tren man hinh - nguoi van hanh ngoi cho mot viec da chet.
        Log::error('SignCtdtJob that bai sau moi luot thu: ' . $exception->getMessage(), [
            'ma_ho_so' => $this->maHoSo,
        ]);

        $hoSo = CtdtHoSo::where('ma_ho_so', $this->maHoSo)->first();

        if ($hoSo !== null) {
            $hoSo->update(['signed_error' => 'Job ky that bai: ' . $exception->getMessage()]);
        }
    }
}
```

- [ ] **Step 4: Chạy test để xác nhận xanh**

```bash
php vendor/bin/phpunit tests/Unit/Ctdt/SignCtdtJobTest.php
```

Kỳ vọng: `OK (12 tests)`.

- [ ] **Step 5: Commit**

```bash
git add app/Jobs/SignCtdtJob.php tests/Unit/Ctdt/SignCtdtJobTest.php
git commit -m "feat(ctdt): SignCtdtJob - dung phong bi, ky so, luu tep"
```

---

## Task 5: `SubmitCtdtJob` — gửi lên cổng và ghi kết quả

**Files:**
- Create: `app/Jobs/SubmitCtdtJob.php`
- Test: `tests/Unit/Ctdt/SubmitCtdtJobTest.php`

**Interfaces:**
- Consumes: `CtdtQuyetDinhGui::nen()` (Task 1); `CtdtSubmitService::gui($xmlDaKy, $dichVu, $maCskcb)` (Task 3);
  `App\Services\BHYTLoginService`; model `CtdtHoSo`; disk `exportCtdt`
- Produces: `App\Jobs\SubmitCtdtJob::__construct($maHoSo, $nguoiGui = null)`,
  `handle(CtdtSubmitService $submitService = null)`. Ghi `ma_gd`, `ma_ket_qua`, `thoi_gian_tiep_nhan`,
  `submitted_at`, `submitted_by`, `submit_error`, `submitted_message`, nối `lich_su_gui`.

**KHÔNG nhận `CtdtSubmitService` qua container.** Container không biết hồ sơ này thuộc cơ sở nào nên
sẽ dựng `BHYTLoginService` **không mã cơ sở**, và lần gửi đầu tiên sẽ ném. `SubmitXml3176Job.php:70-73`
đã dính đúng bẫy này và có sẵn chú thích cảnh báo. Job phải tự dựng service bằng mã cơ sở của **chính
hồ sơ**; tham số `$submitService` chỉ để test tiêm bản giả vào.

- [ ] **Step 1: Viết test đỏ**

Tạo `tests/Unit/Ctdt/SubmitCtdtJobTest.php`:

```php
<?php

namespace Tests\Unit\Ctdt;

use Tests\TestCase;
use Tests\Support\DungBangCtdtSqlite;
use Illuminate\Support\Facades\Storage;
use App\Jobs\SubmitCtdtJob;
use App\Services\Ctdt\CtdtSubmitService;
use App\Models\BHYT\Ctdt\CtdtHoSo;

/**
 * Dich vu gui gia, ke thua lop that de giu dung chu ky phuong thuc.
 */
class FakeCtdtSubmitService extends CtdtSubmitService
{
    public $ketQua = [
        'ma_ket_qua' => '200', 'ma_gd' => 'GD-001',
        'thoi_gian_tiep_nhan' => '20260820083000',
        'thong_diep' => 'Mã 200: Thành công', 'nguyen_van' => '{"MaKetQua":"200"}',
    ];
    public $nem = null;
    public $soLanGoi = 0;
    public $xmlNhanDuoc = null;
    public $dichVuNhanDuoc = null;

    public function __construct()
    {
        // Bo qua constructor cha de khong khoi tao Guzzle that.
    }

    public $maCskcbNhanDuoc = null;

    public function gui($xmlDaKy, $dichVu, $maCskcb)
    {
        $this->soLanGoi++;
        $this->xmlNhanDuoc = $xmlDaKy;
        $this->dichVuNhanDuoc = $dichVu;
        $this->maCskcbNhanDuoc = $maCskcb;

        if ($this->nem !== null) {
            throw $this->nem;
        }

        return $this->ketQua;
    }
}

class SubmitCtdtJobTest extends TestCase
{
    use DungBangCtdtSqlite;

    protected function setUp()
    {
        parent::setUp();
        $this->chuanBiBangCtdt();
        Storage::fake('exportCtdt');
        config(['organization.chung_tu_dien_tu.submit_enabled' => true]);
    }

    private function hoSo(array $ghiDe = [], $noiDungDaKy = '<HSCHUNGTU>DA-KY</HSCHUNGTU>')
    {
        $duongDan = 'da-ky/CT2025/YT001.xml';

        if ($noiDungDaKy !== null) {
            Storage::disk('exportCtdt')->put($duongDan, $noiDungDaKy);
        }

        return CtdtHoSo::create(array_merge([
            'ma_ho_so' => 'YT001', 'dich_vu' => 'CT2025', 'loai_hs' => '39',
            'macskcb' => '01929', 'so_chung_tu' => 1,
            'checked_at' => '2026-08-20 08:00:00', 'so_loi' => 0,
            'is_signed' => true, 'sign_method' => 'HSM',
            'duong_dan_da_ky' => $duongDan,
        ], $ghiDe))->fresh();
    }

    private function chay($gui, $maHoSo = 'YT001', $nguoiGui = 'tracnn')
    {
        (new SubmitCtdtJob($maHoSo, $nguoiGui))->handle($gui);
    }

    /** @test */
    public function gui_thanh_cong_ghi_du_ma_gd_ma_ket_qua_va_thoi_gian()
    {
        $this->hoSo();
        $gui = new FakeCtdtSubmitService();

        $this->chay($gui);

        $hoSo = CtdtHoSo::first();

        $this->assertSame('GD-001', $hoSo->ma_gd);
        $this->assertSame('200', $hoSo->ma_ket_qua);
        $this->assertSame('20260820083000', $hoSo->thoi_gian_tiep_nhan);
        $this->assertNotNull($hoSo->submitted_at);
        $this->assertSame('tracnn', $hoSo->submitted_by);
        $this->assertNull($hoSo->submit_error);
    }

    /** @test */
    public function gui_dung_noi_dung_tep_DA_KY_chu_khong_dung_lai_phong_bi()
    {
        // Dung lai phong bi o day la gui mot goi KHONG co chu ky, va cong tra 205 - hoac
        // te hon, nhan mot ho so khong co gia tri phap ly.
        $this->hoSo([], '<HSCHUNGTU><CHUKYDONVI>chu-ky-that</CHUKYDONVI></HSCHUNGTU>');
        $gui = new FakeCtdtSubmitService();

        $this->chay($gui);

        $this->assertContains('chu-ky-that', $gui->xmlNhanDuoc);
        $this->assertSame('CT2025', $gui->dichVuNhanDuoc);
    }

    /** @test */
    public function truyen_ma_co_so_cua_CHINH_ho_so_xuong_dich_vu_gui()
    {
        // Neu token va tai khoan trong body thuoc hai co so khac nhau thi cong van nhan, va
        // ho so bi ghi sai don vi gui - hong IM LANG cho toi luc doi soat. Day la bay ma
        // SubmitXml3176Job da dinh mot lan roi.
        $this->hoSo(['macskcb' => '37470']);
        $gui = new FakeCtdtSubmitService();

        $this->chay($gui);

        $this->assertSame('37470', $gui->maCskcbNhanDuoc);
    }

    /** @test */
    public function cong_tu_choi_thi_ghi_submit_error_va_giu_nguyen_van()
    {
        $this->hoSo();
        $gui = new FakeCtdtSubmitService();
        $gui->ketQua = [
            'ma_ket_qua' => '205', 'ma_gd' => null, 'thoi_gian_tiep_nhan' => null,
            'thong_diep' => 'Mã 205: fileBase64Str không hợp lệ',
            'nguyen_van' => '{"MaKetQua":"205","ChiTiet":"sai the goc"}',
        ];

        $this->chay($gui);

        $hoSo = CtdtHoSo::first();

        $this->assertSame('205', $hoSo->ma_ket_qua);
        $this->assertNotEmpty($hoSo->submit_error);
        $this->assertContains('sai the goc', (string) $hoSo->submitted_message);
    }

    /** @test */
    public function co_submit_enabled_tat_thi_KHONG_ghi_gi_ca()
    {
        // Khi chuc nang gui dang tat thi khong co lan gui nao dien ra, nen ghi submit_error
        // la BIA - nguoi doc se tuong da thu gui va that bai.
        config(['organization.chung_tu_dien_tu.submit_enabled' => false]);
        $this->hoSo();
        $gui = new FakeCtdtSubmitService();

        $this->chay($gui);

        $hoSo = CtdtHoSo::first();

        $this->assertSame(0, $gui->soLanGoi);
        $this->assertNull($hoSo->submit_error);
        $this->assertNull($hoSo->submitted_at);
        $this->assertNull($hoSo->ma_ket_qua);
    }

    /** @test */
    public function ho_so_chua_kiem_thi_ghi_loi_va_khong_goi_mang()
    {
        $this->hoSo(['checked_at' => null]);
        $gui = new FakeCtdtSubmitService();

        $this->chay($gui);

        $this->assertSame(0, $gui->soLanGoi);
        $this->assertContains('chưa kiểm', mb_strtolower((string) CtdtHoSo::first()->submit_error));
    }

    /** @test */
    public function ho_so_con_loi_thi_ghi_loi_va_khong_goi_mang()
    {
        $this->hoSo(['so_loi' => 2]);
        $gui = new FakeCtdtSubmitService();

        $this->chay($gui);

        $this->assertSame(0, $gui->soLanGoi);
        $this->assertNotEmpty(CtdtHoSo::first()->submit_error);
    }

    /** @test */
    public function ho_so_chua_ky_thi_ghi_loi_va_khong_goi_mang()
    {
        // Gui len cong thi cong cung tu choi. Chan tai cho vua khong ton mot vong goi mang,
        // vua cho thong bao ro hon thong bao cua cong.
        $this->hoSo(['is_signed' => false]);
        $gui = new FakeCtdtSubmitService();

        $this->chay($gui);

        $this->assertSame(0, $gui->soLanGoi);
        $this->assertNotEmpty(CtdtHoSo::first()->submit_error);
    }

    /** @test */
    public function tep_da_ky_bien_mat_thi_ghi_loi_chu_khong_nem()
    {
        // Tep tren dia co the bi don dep. Nem chi lam hang doi thu lai ba lan cho cung mot
        // ket qua, va nguoi van hanh khong biet vi sao.
        $this->hoSo([], null);
        $gui = new FakeCtdtSubmitService();

        $this->chay($gui);

        $this->assertSame(0, $gui->soLanGoi);
        $this->assertNotEmpty(CtdtHoSo::first()->submit_error);
    }

    /** @test */
    public function loi_mang_thi_NEM_de_hang_doi_thu_lai()
    {
        // Mang chap la loi TAM THOI - phai de hang doi thu lai. Nuot no thanh mot dong
        // submit_error la ho so mat co hoi duoc gui lai tu dong.
        $this->hoSo();
        $gui = new FakeCtdtSubmitService();
        $gui->nem = new \Exception('Loi goi cong BHXH: Connection refused');

        $this->expectException(\Exception::class);

        $this->chay($gui);
    }

    /** @test */
    public function ho_so_khong_ton_tai_thi_khong_nem()
    {
        $gui = new FakeCtdtSubmitService();

        $this->chay($gui, 'KHONG_TON_TAI');

        $this->assertSame(0, $gui->soLanGoi);
    }

    /** @test */
    public function gui_lai_lan_hai_thi_noi_them_lich_su_chu_khong_xoa()
    {
        // MaGD cu la dau vet doi soat voi BHXH. Ghi de ma khong giu lai la mat dau vet cua
        // mot lan gui da that su xay ra.
        $this->hoSo();
        $gui = new FakeCtdtSubmitService();

        $this->chay($gui);

        $gui->ketQua['ma_gd'] = 'GD-002';
        $this->chay($gui);

        $hoSo = CtdtHoSo::first();

        $this->assertSame('GD-002', $hoSo->ma_gd);
        $this->assertContains('GD-001', (string) $hoSo->lich_su_gui, 'Phai giu dau vet lan gui truoc');
    }

    /** @test */
    public function lich_su_gui_giu_ca_ba_lan()
    {
        $this->hoSo();
        $gui = new FakeCtdtSubmitService();

        foreach (['GD-A', 'GD-B', 'GD-C'] as $ma) {
            $gui->ketQua['ma_gd'] = $ma;
            $this->chay($gui);
        }

        $lichSu = (string) CtdtHoSo::first()->lich_su_gui;

        $this->assertContains('GD-A', $lichSu);
        $this->assertContains('GD-B', $lichSu);
        $this->assertSame('GD-C', CtdtHoSo::first()->ma_gd);
    }
}
```

- [ ] **Step 2: Chạy test để xác nhận đỏ**

```bash
php vendor/bin/phpunit tests/Unit/Ctdt/SubmitCtdtJobTest.php
```

Kỳ vọng: đỏ — `Class 'App\Jobs\SubmitCtdtJob' not found`.

- [ ] **Step 3: Viết `SubmitCtdtJob`**

Tạo `app/Jobs/SubmitCtdtJob.php`:

```php
<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

use App\Models\BHYT\Ctdt\CtdtHoSo;
use App\Services\BHYTLoginService;
use App\Services\Ctdt\CtdtQuyetDinhGui;
use App\Services\Ctdt\CtdtSubmitService;

/**
 * Gui mot ho so da ky len cong BHXH va ghi lai ket qua.
 *
 * Nhan MA HO SO chu khong nhan model: job co the nam cho trong hang doi rat lau, va mot
 * model serialize san se mang theo du lieu da cu.
 */
class SubmitCtdtJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** @var int */
    public $tries = 3;

    /** @var int */
    public $timeout = 60;

    /** @var string */
    protected $maHoSo;

    /** @var string|null */
    protected $nguoiGui;

    public function __construct($maHoSo, $nguoiGui = null)
    {
        $this->maHoSo = $maHoSo;
        $this->nguoiGui = $nguoiGui;
    }

    /**
     * @param CtdtSubmitService|null $submitService Chi de test tiem ban gia. Khi null, job tu
     *        dung service bang ma co so cua CHINH ho so - xem chu thich trong than ham.
     */
    public function handle(CtdtSubmitService $submitService = null)
    {
        $hoSo = CtdtHoSo::where('ma_ho_so', $this->maHoSo)->first();

        if ($hoSo === null) {
            // Ho so co the da bi xoa trong luc job cho trong hang doi.
            Log::info('SubmitCtdtJob: khong tim thay ho so ' . $this->maHoSo);

            return;
        }

        $quyetDinh = CtdtQuyetDinhGui::nen(
            config('organization.chung_tu_dien_tu.submit_enabled', false),
            $hoSo->checked_at,
            $hoSo->so_loi,
            $hoSo->is_signed
        );

        if ($quyetDinh === CtdtQuyetDinhGui::KHONG_GUI) {
            // KHONG ghi gi ca, ke ca submit_error. Khi chuc nang gui dang tat thi khong co
            // lan gui nao dien ra - ghi loi la bia, nguoi doc se tuong da thu gui va that bai.
            Log::info('SubmitCtdtJob: chuc nang gui dang tat, bo qua ' . $this->maHoSo);

            return;
        }

        if ($quyetDinh !== CtdtQuyetDinhGui::GUI) {
            $this->ghiLoi($hoSo, $this->lyDo($quyetDinh));

            return;
        }

        $duongDan = (string) $hoSo->duong_dan_da_ky;

        if ($duongDan === '' || !Storage::disk('exportCtdt')->exists($duongDan)) {
            // Tep tren dia co the bi don dep. Ghi lai de nguoi van hanh doc, dung nem: nem
            // chi lam hang doi thu lai ba lan cho cung mot ket qua.
            $this->ghiLoi($hoSo, 'Khong tim thay tep da ky: ' . ($duongDan === '' ? '(trong)' : $duongDan));

            return;
        }

        // Doc noi dung DA KY tren dia, KHONG dung lai phong bi: dung lai la gui mot goi
        // khong co chu ky, va cong tra 205 - hoac te hon, nhan mot ho so khong co gia tri
        // phap ly.
        $xmlDaKy = Storage::disk('exportCtdt')->get($duongDan);

        if ($submitService === null) {
            // KHONG nhan service qua container: container khong biet ho so nay thuoc co so
            // nao nen se dung BHYTLoginService KHONG ma co so, va lan gui dau tien se nem.
            // SubmitXml3176Job.php:70-73 da dinh dung bay nay va co san chu thich canh bao.
            // Dung tuong minh bang ma co so cua CHINH ho so, de token va tai khoan trong body
            // khong the thuoc hai co so khac nhau.
            $submitService = new CtdtSubmitService(new BHYTLoginService($hoSo->macskcb));
        }

        // KHONG bat exception o day: loi mang la loi TAM THOI va phai de hang doi thu lai.
        // Nuot no thanh mot dong submit_error la ho so mat co hoi duoc gui lai tu dong.
        $ketQua = $submitService->gui($xmlDaKy, $hoSo->dich_vu, $hoSo->macskcb);

        $this->ghiKetQua($hoSo, $ketQua);
    }

    private function ghiKetQua(CtdtHoSo $hoSo, array $ketQua)
    {
        // So sanh LONG: cong co the tra so 200 thay vi chuoi '200'. So sanh nghiem ngat se
        // coi mot ho so THANH CONG la bi tu choi.
        $thanhCong = isset($ketQua['ma_ket_qua']) && $ketQua['ma_ket_qua'] == '200';

        $thuocTinh = [
            'ma_gd'               => isset($ketQua['ma_gd']) ? $ketQua['ma_gd'] : null,
            'ma_ket_qua'          => isset($ketQua['ma_ket_qua']) ? $ketQua['ma_ket_qua'] : null,
            'thoi_gian_tiep_nhan' => isset($ketQua['thoi_gian_tiep_nhan']) ? $ketQua['thoi_gian_tiep_nhan'] : null,
            'submitted_at'        => now(),
            'submitted_by'        => $this->nguoiGui,
            'submitted_message'   => isset($ketQua['nguyen_van']) ? $ketQua['nguyen_van'] : null,
            'submit_error'        => $thanhCong ? null : (isset($ketQua['thong_diep']) ? $ketQua['thong_diep'] : 'Cong tu choi'),
        ];

        $lichSu = $this->noiLichSu($hoSo);

        if ($lichSu !== null) {
            $thuocTinh['lich_su_gui'] = $lichSu;
        }

        $hoSo->update($thuocTinh);

        Log::info('SubmitCtdtJob: da gui', [
            'ma_ho_so'   => $this->maHoSo,
            'ma_ket_qua' => isset($ketQua['ma_ket_qua']) ? $ketQua['ma_ket_qua'] : null,
            'ma_gd'      => isset($ketQua['ma_gd']) ? $ketQua['ma_gd'] : null,
        ]);
    }

    /**
     * Noi mot dong vao lich su TRUOC khi ghi de.
     *
     * MaGD cu la dau vet doi soat voi BHXH. Ghi de ma khong giu lai la mat dau vet cua mot
     * lan gui da that su xay ra, va tranh chap doi soat se khong co gi de tra.
     */
    private function noiLichSu(CtdtHoSo $hoSo)
    {
        if (empty($hoSo->ma_gd) && empty($hoSo->ma_ket_qua)) {
            return null;
        }

        $dong = '[' . now()->format('Y-m-d H:i:s') . '] MaGD=' . (string) $hoSo->ma_gd
              . ' MaKetQua=' . (string) $hoSo->ma_ket_qua;

        return trim((string) $hoSo->lich_su_gui . "\n" . $dong);
    }

    private function lyDo($quyetDinh)
    {
        if ($quyetDinh === CtdtQuyetDinhGui::CHUA_KIEM) {
            return 'Hồ sơ chưa kiểm — công việc kiểm còn nằm trong hàng đợi';
        }

        if ($quyetDinh === CtdtQuyetDinhGui::CON_LOI) {
            return 'Hồ sơ còn lỗi chặn gửi — sửa nguồn rồi nạp lại';
        }

        return 'Hồ sơ chưa ký số';
    }

    private function ghiLoi(CtdtHoSo $hoSo, $loi)
    {
        Log::info('SubmitCtdtJob: ' . $loi, ['ma_ho_so' => $this->maHoSo]);

        $hoSo->update(['submit_error' => $loi]);
    }

    public function failed(\Throwable $exception)
    {
        // Het luot thu ma khong ghi gi thi ho so o lai khong dau vet gi tren man hinh -
        // nguoi van hanh ngoi cho mot viec da chet.
        Log::error('SubmitCtdtJob that bai sau moi luot thu: ' . $exception->getMessage(), [
            'ma_ho_so' => $this->maHoSo,
        ]);

        $hoSo = CtdtHoSo::where('ma_ho_so', $this->maHoSo)->first();

        if ($hoSo !== null) {
            $hoSo->update(['submit_error' => 'Job gửi thất bại: ' . $exception->getMessage()]);
        }
    }
}
```

- [ ] **Step 4: Chạy test để xác nhận xanh**

```bash
php vendor/bin/phpunit tests/Unit/Ctdt/SubmitCtdtJobTest.php
```

Kỳ vọng: `OK (14 tests)`.

- [ ] **Step 5: Commit**

```bash
git add app/Jobs/SubmitCtdtJob.php tests/Unit/Ctdt/SubmitCtdtJobTest.php
git commit -m "feat(ctdt): SubmitCtdtJob - gui len cong va ghi ket qua"
```

---

## Task 6: Route, controller và nút "Ký và gửi"

**Files:**
- Modify: `routes/web.php` (chèn sau route `bhyt.ctdt.detail.tab`, dòng ~604)
- Modify: `app/Http/Controllers/BHYT/BHYTCtdtController.php` (thêm `kyVaGui()`)
- Create: `resources/views/bhyt/ctdt/partials/nut-ky-va-gui.blade.php`
- Modify: `resources/views/bhyt/ctdt/detail.blade.php` (nhúng partial, thêm dòng lỗi gửi)
- Test: `tests/Unit/Ctdt/CtdtKyVaGuiTest.php`

**Interfaces:**
- Consumes: `App\Jobs\SignCtdtJob` (Task 4), `App\Jobs\SubmitCtdtJob` (Task 5),
  `CtdtQuyetDinhGui` (Task 1); model `CtdtHoSo`
- Produces:
  - Route `POST bhyt/ctdt/{ma_ho_so}/ky-va-gui` tên `bhyt.ctdt.ky-va-gui`, trong **cùng group** `checkrole:xml-man` với các route ctdt khác
  - `BHYTCtdtController::kyVaGui($ma_ho_so)` trả JSON `['thanh_cong' => bool, 'thong_diep' => string]`

**Vì sao xâu chuỗi bằng `->chain()`:** ký xong mới gửi được. Đẩy hai job độc lập lên hai hàng đợi thì
job gửi có thể chạy trước job ký và luôn thấy `is_signed = false`.

**Không tạo role mới** — dùng lại `xml-man` như các route ctdt khác.

- [ ] **Step 1: Viết test đỏ**

Tạo `tests/Unit/Ctdt/CtdtKyVaGuiTest.php`:

```php
<?php

namespace Tests\Unit\Ctdt;

use Tests\TestCase;
use Tests\Support\DungBangCtdtSqlite;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BHYT\BHYTCtdtController;
use App\Jobs\SignCtdtJob;
use App\Models\BHYT\Ctdt\CtdtHoSo;
use App\Models\BHYT\Ctdt\CtdtChungTu;

class CtdtKyVaGuiTest extends TestCase
{
    use DungBangCtdtSqlite;

    /** @var BHYTCtdtController */
    private $controller;

    protected function setUp()
    {
        parent::setUp();
        $this->chuanBiBangCtdt();
        Queue::fake();
        $this->controller = new BHYTCtdtController();

        config([
            'organization.chung_tu_dien_tu.sign_enabled'   => true,
            'organization.chung_tu_dien_tu.submit_enabled' => true,
            'organization.chung_tu_dien_tu.sign_queue_name'   => 'JobSignCtdt',
            'organization.chung_tu_dien_tu.submit_queue_name' => 'JobSubmitCtdt',
        ]);
    }

    private function hoSo(array $ghiDe = [])
    {
        $hoSo = CtdtHoSo::create(array_merge([
            'ma_ho_so' => 'YT001', 'dich_vu' => 'CT2025', 'loai_hs' => '39',
            'macskcb' => '01929', 'so_chung_tu' => 1,
            'checked_at' => '2026-08-20 08:00:00', 'so_loi' => 0, 'is_signed' => false,
        ], $ghiDe));

        CtdtChungTu::create([
            'ho_so_id' => $hoSo->id, 'loai_ho_so' => 'CT03', 'ma_chung_tu' => 'YT001',
            'noi_dung_goc' => '<CT03/>',
        ]);

        return $hoSo->fresh();
    }

    private function layJson($response)
    {
        return json_decode($response->getContent(), true);
    }

    /** @test */
    public function ho_so_du_dieu_kien_thi_day_job_ky()
    {
        $this->hoSo();

        $kq = $this->layJson($this->controller->kyVaGui('YT001'));

        $this->assertTrue($kq['thanh_cong']);
        Queue::assertPushed(SignCtdtJob::class);
    }

    /** @test */
    public function job_ky_di_dung_hang_doi_lay_tu_cau_hinh()
    {
        // Day sai hang doi thi worker khong bao gio nhan duoc, va ho so nam mai o "chua ky"
        // ma khong co dau hieu gi.
        config(['organization.chung_tu_dien_tu.sign_queue_name' => 'HangDoiRieng']);
        $this->hoSo();

        $this->controller->kyVaGui('YT001');

        Queue::assertPushed(SignCtdtJob::class, function ($job) {
            return $job->queue === 'HangDoiRieng';
        });
    }

    /** @test */
    public function job_gui_duoc_xau_chuoi_SAU_job_ky()
    {
        // Ky xong moi gui duoc. Day hai job doc lap thi job gui co the chay truoc va luon
        // thay is_signed = false.
        $this->hoSo();

        $this->controller->kyVaGui('YT001');

        Queue::assertPushed(SignCtdtJob::class, function ($job) {
            return !empty($job->chained);
        });
    }

    /** @test */
    public function ho_so_chua_kiem_thi_tu_choi_va_KHONG_day_job()
    {
        $this->hoSo(['checked_at' => null]);

        $kq = $this->layJson($this->controller->kyVaGui('YT001'));

        $this->assertFalse($kq['thanh_cong']);
        $this->assertContains('chưa kiểm', mb_strtolower($kq['thong_diep']));
        Queue::assertNotPushed(SignCtdtJob::class);
    }

    /** @test */
    public function ho_so_con_loi_thi_tu_choi_va_KHONG_day_job()
    {
        $this->hoSo(['so_loi' => 4]);

        $kq = $this->layJson($this->controller->kyVaGui('YT001'));

        $this->assertFalse($kq['thanh_cong']);
        Queue::assertNotPushed(SignCtdtJob::class);
    }

    /** @test */
    public function chuc_nang_gui_dang_tat_thi_tu_choi_va_noi_ro_ly_do()
    {
        // Nguoi bam nut phai biet VI SAO khong co gi xay ra, khong thi ho bam lai mai.
        config(['organization.chung_tu_dien_tu.submit_enabled' => false]);
        $this->hoSo();

        $kq = $this->layJson($this->controller->kyVaGui('YT001'));

        $this->assertFalse($kq['thanh_cong']);
        $this->assertNotEmpty($kq['thong_diep']);
        Queue::assertNotPushed(SignCtdtJob::class);
    }

    /** @test */
    public function ho_so_da_ky_roi_thi_van_day_duoc_de_gui_lai()
    {
        // Gui lai mot ho so da ky la viec hop le (cong tra 500 lan truoc chang han).
        $this->hoSo(['is_signed' => true]);

        $kq = $this->layJson($this->controller->kyVaGui('YT001'));

        $this->assertTrue($kq['thanh_cong']);
        Queue::assertPushed(SignCtdtJob::class);
    }

    /** @test */
    public function ho_so_khong_ton_tai_thi_nem_ModelNotFound()
    {
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        $this->controller->kyVaGui('KHONG_TON_TAI');
    }

    /** @test */
    public function route_ky_va_gui_ton_tai_va_nam_trong_nhom_xml_man()
    {
        // Dat ngoai nhom checkrole la mo mot duong gui len cong BHXH cho bat ky ai dang nhap.
        $route = Route::getRoutes()->getByName('bhyt.ctdt.ky-va-gui');

        $this->assertNotNull($route, 'Thieu route bhyt.ctdt.ky-va-gui');
        $this->assertContains('POST', $route->methods());
        $this->assertContains('checkrole:xml-man', $route->gatherMiddleware());
    }

    /** @test */
    public function man_chi_tiet_render_duoc_va_co_nut_ky_va_gui()
    {
        $hoSo = $this->hoSo();

        $html = view('bhyt.ctdt.detail', [
            'hoSo' => $hoSo->fresh(),
            'tabs' => \App\Services\Ctdt\CtdtDetailTabs::cua($hoSo->fresh()),
        ])->render();

        $this->assertContains('btn-ky-va-gui', $html);
    }

    /** @test */
    public function noi_dung_phan_hoi_cua_cong_duoc_thoat_khi_hien()
    {
        // submitted_message la nguyen van phan hoi cua cong - du lieu ben ngoai. Bo thoat la
        // mot lo hong XSS luu tru, dung lop loi da bi bat ba lan trong module nay.
        $hoSo = $this->hoSo([
            'submit_error'      => 'Lỗi <img src=x onerror=alert(1)>',
            'submitted_message' => '{"ChiTiet":"<script>alert(2)</script>"}',
        ]);

        $html = view('bhyt.ctdt.detail', [
            'hoSo' => $hoSo->fresh(),
            'tabs' => \App\Services\Ctdt\CtdtDetailTabs::cua($hoSo->fresh()),
        ])->render();

        $this->assertNotContains('<img src=x', $html);
        $this->assertNotContains('<script>alert(2)', $html);
        $this->assertContains('&lt;img', $html);
    }
}
```

- [ ] **Step 2: Chạy test để xác nhận đỏ**

```bash
php vendor/bin/phpunit tests/Unit/Ctdt/CtdtKyVaGuiTest.php
```

Kỳ vọng: đỏ — `Method ... kyVaGui does not exist`.

- [ ] **Step 3: Thêm route**

Trong `routes/web.php`, chèn ngay **sau** dòng route `bhyt.ctdt.detail.tab` (khoảng dòng 604) và
**trước** khối `Route::delete('ctdt/{ma_ho_so}', ...)`:

```php
        Route::post('ctdt/{ma_ho_so}/ky-va-gui', 'BHYT\BHYTCtdtController@kyVaGui')
        ->name('bhyt.ctdt.ky-va-gui');
```

Giữ nguyên trong group `checkrole:xml-man` sẵn có — **không** thêm middleware riêng, **không** tạo
role mới. Đặt ngoài group là mở một đường gửi lên cổng BHXH cho bất kỳ ai đăng nhập.

- [ ] **Step 4: Thêm `kyVaGui()` vào controller**

Trong `app/Http/Controllers/BHYT/BHYTCtdtController.php`, thêm ở đầu tệp:

```php
use App\Jobs\SignCtdtJob;
use App\Jobs\SubmitCtdtJob;
use App\Services\Ctdt\CtdtQuyetDinhGui;
```

rồi thêm phương thức, đặt **trước** `delete()`:

```php
    /**
     * Ky so roi gui mot ho so len cong BHXH.
     *
     * Kiem dieu kien NGAY TAI DAY thay vi de job tu tu choi: nguoi bam nut phai biet VI SAO
     * khong co gi xay ra, khong thi ho bam lai mai. Job van kiem lai lan nua vi no co the
     * nam cho trong hang doi rat lau, giua luc do cau hinh hoac ho so co the da doi.
     */
    public function kyVaGui($ma_ho_so)
    {
        $hoSo = CtdtHoSo::where('ma_ho_so', $ma_ho_so)->firstOrFail();

        $quyetDinh = CtdtQuyetDinhGui::nen(
            config('organization.chung_tu_dien_tu.submit_enabled', false),
            $hoSo->checked_at,
            $hoSo->so_loi,
            // KHONG truyen $hoSo->is_signed: ho so chua ky la binh thuong o day - ta sap ky
            // no. Truyen gia tri that se lam moi ho so chua ky bi tu choi ngay tai nut.
            true
        );

        if ($quyetDinh !== CtdtQuyetDinhGui::GUI) {
            return response()->json([
                'thanh_cong' => false,
                'thong_diep' => $this->lyDoKhongGui($quyetDinh),
            ]);
        }

        // XAU CHUOI chu khong day hai job doc lap: ky xong moi gui duoc. Hai job doc lap thi
        // job gui co the chay truoc job ky va luon thay is_signed = false.
        $nguoiGui = auth()->check() ? auth()->user()->username : null;

        SignCtdtJob::withChain([
            (new SubmitCtdtJob($ma_ho_so, $nguoiGui))
                ->onQueue(config('organization.chung_tu_dien_tu.submit_queue_name', 'JobSubmitCtdt')),
        ])
        ->dispatch($ma_ho_so)
        ->onQueue(config('organization.chung_tu_dien_tu.sign_queue_name', 'JobSignCtdt'));

        return response()->json([
            'thanh_cong' => true,
            'thong_diep' => 'Đã xếp hàng ký số và gửi. Tải lại trang sau ít phút để xem kết quả.',
        ]);
    }

    /** Ly do doc duoc cho nguoi bam nut, khong phai ma trang thai */
    private function lyDoKhongGui($quyetDinh)
    {
        if ($quyetDinh === CtdtQuyetDinhGui::KHONG_GUI) {
            return 'Chức năng gửi đang tắt trong cấu hình. Liên hệ quản trị để bật.';
        }

        if ($quyetDinh === CtdtQuyetDinhGui::CHUA_KIEM) {
            return 'Hồ sơ chưa kiểm — công việc kiểm còn nằm trong hàng đợi. Thử lại sau ít phút.';
        }

        if ($quyetDinh === CtdtQuyetDinhGui::CON_LOI) {
            return 'Hồ sơ còn lỗi chặn gửi. Xem tab Lỗi, sửa ở phần mềm sinh XML rồi nạp lại.';
        }

        return 'Hồ sơ chưa đủ điều kiện gửi.';
    }
```

**Nếu `auth()->user()->username` không tồn tại trong dự án này** (kiểm bằng cách xem model
`App\User` dùng cột nào để đăng nhập), thay bằng cột đúng và ghi lại trong báo cáo. Đừng đoán.

- [ ] **Step 5: Viết partial nút**

Tạo `resources/views/bhyt/ctdt/partials/nut-ky-va-gui.blade.php`:

```blade
{{-- Nut "Ky va gui" tren man chi tiet.

     Bien vao: $hoSo — ban ghi CtdtHoSo

     LUU Y: tep nay chua khoi <script>. Moi chi thi Blade nam trong chu thich JavaScript
     VAN duoc Blade dich va sinh ra PHP hong - dung lop loi da lam man chi tiet khong render
     duoc suot nhieu ngay. Trong chu thich JS, viet @@if neu can nhac toi chi thi. --}}
<div class="row" style="margin-top:8px">
    <div class="col-sm-12 text-right">
        <button type="button" id="btn-ky-va-gui" class="btn btn-primary btn-sm">
            <i class="fa fa-paper-plane"></i>
            {{ $hoSo->ma_gd ? 'Ký và gửi lại' : 'Ký và gửi' }}
        </button>
    </div>
</div>

@push('after-scripts')
<script>
$(function () {
    $('#btn-ky-va-gui').on('click', function () {
        var maHoSo = @json($hoSo->ma_ho_so);
        var maGd = @json($hoSo->ma_gd);

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

            var nut = $('#btn-ky-va-gui');
            nut.prop('disabled', true);

            // ma_ho_so co the chua dau '#' (nhanh lui GUID). Khong ma hoa thi trinh duyet
            // cat tu dau '#' va yeu cau tro sai ho so.
            var url = "{{ route('bhyt.ctdt.ky-va-gui', ['ma_ho_so' => '__MA__']) }}"
                      .replace('__MA__', encodeURIComponent(maHoSo));

            $.post(url, { _token: "{{ csrf_token() }}" })
                .done(function (data) {
                    Swal.fire({
                        title: data.thanh_cong ? 'Đã xếp hàng' : 'Chưa gửi được',
                        text: data.thong_diep,
                        icon: data.thanh_cong ? 'success' : 'warning'
                    }).then(function () {
                        if (data.thanh_cong) {
                            location.reload();
                        }
                    });
                })
                .fail(function () {
                    Swal.fire('Lỗi', 'Không gọi được máy chủ. Thử lại sau.', 'error');
                })
                .always(function () {
                    nut.prop('disabled', false);
                });
        });
    });
});
</script>
@endpush
```

- [ ] **Step 6: Nhúng partial và thêm dòng lỗi gửi vào màn chi tiết**

Trong `resources/views/bhyt/ctdt/detail.blade.php`:

Thêm **ngay sau** khối `@if ($hoSo->lich_su_gui) ... @endif` một khối hiện lỗi gửi:

```blade
        @if ($hoSo->submit_error)
        <div class="row" style="margin-top:8px">
            <div class="col-sm-12">
                {{-- submit_error va submitted_message chua nguyen van phan hoi cua cong -
                     du lieu ben ngoai. Moi cho hien deu dung {{ }}. --}}
                <div class="alert alert-warning" style="margin-bottom:4px">
                    <strong>Lỗi gửi:</strong> {{ $hoSo->submit_error }}
                </div>
                @if ($hoSo->submitted_message)
                <pre style="white-space:pre-wrap">{{ $hoSo->submitted_message }}</pre>
                @endif
            </div>
        </div>
        @endif
```

Thêm **ngay trước** khối `@if (auth()->check() && auth()->user()->hasRole('superadministrator'))`
(khối nút Xóa hồ sơ):

```blade
        @include('bhyt.ctdt.partials.nut-ky-va-gui', ['hoSo' => $hoSo])
```

**Đừng xoá dòng nào khác.** Tệp này có hai chú thích quan trọng về mã hoá URL và dấu `#` trong
`ma_ho_so` — cả hai phải còn nguyên.

- [ ] **Step 7: Chạy test để xác nhận xanh**

```bash
php vendor/bin/phpunit tests/Unit/Ctdt/CtdtKyVaGuiTest.php
```

Kỳ vọng: `OK (11 tests)`.

Rồi chạy cả thư mục để chắc không làm vỡ test cũ (đặc biệt `CtdtBladeCompilesTest` và `CtdtRouteTest`):

```bash
php vendor/bin/phpunit tests/Unit/Ctdt
```

Kỳ vọng: xanh hoàn toàn.

- [ ] **Step 8: Commit**

```bash
git add routes/web.php app/Http/Controllers/BHYT/BHYTCtdtController.php resources/views/bhyt/ctdt tests/Unit/Ctdt/CtdtKyVaGuiTest.php
git commit -m "feat(ctdt): route, controller va nut Ky va gui tren man chi tiet"
```

---

## Task 7: Worker, `failed()` cho job kiểm, tài liệu và lưới an toàn toàn luồng

**Files:**
- Modify: `app/Jobs/CheckCtdtJob.php` (thêm `failed()`)
- Modify: `install_service.bat` (thêm `JobSignCtdt`, `JobSubmitCtdt`)
- Modify: `docs/chung-tu-dien-tu-pl02.md`
- Test: `tests/Unit/Ctdt/CtdtGuiToanLuongTest.php`
- Test: bổ sung vào `tests/Unit/Ctdt/CheckCtdtJobTest.php`

**Interfaces:**
- Consumes: mọi thứ của Task 1–6
- Produces: `CheckCtdtJob::failed(\Throwable $exception)`; hai dịch vụ nssm; không có mã sản phẩm nào khác

**Vì sao `failed()` cho `CheckCtdtJob` nằm ở đây:** job kiểm hết ba lượt thử sẽ rơi vào `failed_jobs`
và hồ sơ ở lại `checked_at = null` **vĩnh viễn**, không dấu vết nào trên màn hình. Câu SQL đếm hàng đợi
trong tài liệu không phát hiện được ca này vì hàng đợi vẫn rỗng. Từ Giai đoạn 4 trở đi hậu quả nặng
hơn: một hồ sơ "chưa kiểm" là một hồ sơ không bao giờ gửi được, và không ai biết vì sao.

- [ ] **Step 1: Viết test cho `CheckCtdtJob::failed()`**

Thêm vào `tests/Unit/Ctdt/CheckCtdtJobTest.php`:

```php
    /** @test */
    public function het_luot_thu_thi_ghi_lai_dau_vet_tren_ho_so()
    {
        // Job het ba luot thu roi roi vao failed_jobs, va ho so o lai checked_at = null
        // VINH VIEN. Cau SQL dem hang doi khong phat hien duoc ca nay vi hang doi van rong.
        // Tu Giai doan 4, mot ho so "chua kiem" la mot ho so khong bao gio gui duoc.
        $this->hoSoCt03([
            'ma_yte' => 'YT001', 'ho_ten' => 'Nguyen Van Test', 'ngay_sinh' => '19950914',
            'ngay_vao' => '201912121200', 'ngay_ra' => '201912180001', 'ma_the' => 'DN1',
        ]);

        (new CheckCtdtJob('YT001'))->failed(new \Exception('CSDL mat ket noi'));

        $hoSo = CtdtHoSo::first();

        $this->assertNotEmpty($hoSo->import_error, 'Phai de lai dau vet doc duoc tren man hinh');
        $this->assertContains('CSDL mat ket noi', (string) $hoSo->import_error);
        $this->assertNull($hoSo->checked_at, 'Khong duoc gia vo la da kiem');
    }

    /** @test */
    public function failed_voi_ho_so_khong_ton_tai_thi_khong_nem()
    {
        (new CheckCtdtJob('KHONG_TON_TAI'))->failed(new \Exception('loi gi do'));

        $this->assertSame(0, CtdtHoSo::count());
    }
```

- [ ] **Step 2: Chạy để xác nhận đỏ**

```bash
php vendor/bin/phpunit tests/Unit/Ctdt/CheckCtdtJobTest.php
```

Kỳ vọng: đỏ — `Call to undefined method ... failed()`.

- [ ] **Step 3: Thêm `failed()` vào `CheckCtdtJob`**

Trong `app/Jobs/CheckCtdtJob.php`, thêm ở cuối lớp:

```php
    /**
     * Het luot thu ma khong ghi gi thi ho so o lai checked_at = null VINH VIEN, khong dau
     * vet nao tren man hinh. Cau SQL dem hang doi khong phat hien duoc ca nay vi hang doi
     * van rong - nguoi van hanh se thay mot ho so mai mai "Chua kiem" ma khong hieu vi sao.
     *
     * Ghi vao import_error chu khong tao cot moi: day la cot da co, da duoc man chi tiet
     * doc, va noi dung "vi sao ho so nay khong dung duoc" dung la viec cua no.
     */
    public function failed(\Throwable $exception)
    {
        \Log::error('CheckCtdtJob that bai sau moi luot thu: ' . $exception->getMessage(), [
            'ma_ho_so' => $this->maHoSo,
        ]);

        $hoSo = CtdtHoSo::where('ma_ho_so', $this->maHoSo)->first();

        if ($hoSo === null) {
            return;
        }

        $hoSo->update([
            'import_error' => 'Job kiểm thất bại: ' . $exception->getMessage(),
        ]);
    }
```

- [ ] **Step 4: Viết lưới an toàn toàn luồng**

Tạo `tests/Unit/Ctdt/CtdtGuiToanLuongTest.php`:

```php
<?php

namespace Tests\Unit\Ctdt;

use Tests\TestCase;
use Tests\Support\DungBangCtdtSqlite;
use Tests\Support\GoiCtdtMau;
use Illuminate\Support\Facades\Storage;
use App\Jobs\CheckCtdtJob;
use App\Jobs\SignCtdtJob;
use App\Jobs\SubmitCtdtJob;
use App\Services\Ctdt\CtdtImporter;
use App\Services\Ctdt\CtdtTrangThaiGui;
use App\Models\BHYT\Ctdt\CtdtHoSo;

/**
 * Luoi an toan cho ca Giai doan 4: nap that -> kiem that -> ky that -> gui that -> con so
 * hien dung tren man danh sach.
 *
 * KHONG dung Queue::fake() o day: day chinh la cho phai chay ca ba job THAT.
 * Chi gia lap hai thu khong the goi that: dich vu ky va cong BHXH.
 */
class CtdtGuiToanLuongTest extends TestCase
{
    use DungBangCtdtSqlite;
    use GoiCtdtMau;

    /** @var CtdtImporter */
    private $importer;

    protected function setUp()
    {
        parent::setUp();
        $this->chuanBiBangCtdt();
        Storage::fake('exportCtdt');
        $this->importer = new CtdtImporter();

        config([
            'organization.BHYT.ma_cskcb'                   => '01929',
            'organization.chung_tu_dien_tu.sign_enabled'   => true,
            'organization.chung_tu_dien_tu.submit_enabled' => true,
        ]);
    }

    private function goiHopLe()
    {
        return $this->goiCt2025([[
            $this->chungTu('CT03', [
                'MA_YTE' => 'YT001', 'HO_TEN' => 'Nguyen Van Test',
                'NGAY_SINH' => '19950914', 'NGAY_VAO' => '201912121200',
                'NGAY_RA' => '201912180001', 'MA_THE' => 'DN1234567890',
            ]),
        ]]);
    }

    private function napVaKiem($xml)
    {
        $kq = $this->importer->nhapTuChuoi($xml, ['macskcb' => '01929']);

        foreach ($kq->dsMaHoSo as $maHoSo) {
            (new CheckCtdtJob($maHoSo))->handle();
        }

        return $kq;
    }

    private function ky($maHoSo = 'YT001')
    {
        $kyGia = new FakeXMLSignService();
        (new SignCtdtJob($maHoSo))->handle($kyGia);

        return $kyGia;
    }

    private function gui($maHoSo = 'YT001', array $ketQua = null)
    {
        $guiGia = new FakeCtdtSubmitService();

        if ($ketQua !== null) {
            $guiGia->ketQua = $ketQua;
        }

        (new SubmitCtdtJob($maHoSo, 'tracnn'))->handle($guiGia);

        return $guiGia;
    }

    /** @test */
    public function nap_kiem_ky_gui_thanh_cong_thi_MaGD_hien_tren_ho_so()
    {
        $this->napVaKiem($this->goiHopLe());
        $this->ky();
        $this->gui();

        $hoSo = CtdtHoSo::first();

        $this->assertSame(0, (int) $hoSo->so_loi);
        $this->assertTrue((bool) $hoSo->is_signed);
        $this->assertSame('GD-001', $hoSo->ma_gd);
        $this->assertSame('200', $hoSo->ma_ket_qua);
        $this->assertSame(CtdtTrangThaiGui::DA_GUI, CtdtTrangThaiGui::cua($hoSo));
    }

    /** @test */
    public function ho_so_con_loi_KHONG_duoc_ky_va_KHONG_duoc_gui()
    {
        // Day la ly do ca Giai doan 3 va 4 ton tai: mot ho so con loi khong duoc di tiep.
        $this->napVaKiem($this->goiCt2025([[$this->chungTu('CT03', ['MA_YTE' => 'YT001'])]]));

        $kyGia = $this->ky();
        $guiGia = $this->gui();

        $hoSo = CtdtHoSo::first();

        $this->assertGreaterThan(0, (int) $hoSo->so_loi);
        $this->assertSame(0, $kyGia->soLanGoi, 'Ho so con loi khong duoc ky');
        $this->assertSame(0, $guiGia->soLanGoi, 'Ho so con loi khong duoc gui');
        $this->assertNull($hoSo->ma_gd);
        $this->assertSame(CtdtTrangThaiGui::CON_LOI, CtdtTrangThaiGui::cua($hoSo));
    }

    /** @test */
    public function ho_so_CHUA_KIEM_khong_duoc_ky_va_khong_duoc_gui()
    {
        // May chu chua cai worker JobCtdt roi vao dung tinh huong nay. Neu cua chan khong
        // dong o day thi moi ho so tren may do se duoc gui len cong ma khong ai kiem.
        $this->importer->nhapTuChuoi($this->goiHopLe(), ['macskcb' => '01929']);

        $kyGia = $this->ky();
        $guiGia = $this->gui();

        $hoSo = CtdtHoSo::first();

        $this->assertNull($hoSo->checked_at);
        $this->assertSame(0, $kyGia->soLanGoi);
        $this->assertSame(0, $guiGia->soLanGoi);
        $this->assertSame(CtdtTrangThaiGui::CHUA_KIEM, CtdtTrangThaiGui::cua($hoSo));
    }

    /** @test */
    public function goi_gui_len_cong_la_tep_DA_KY_chu_khong_phai_phong_bi_tran()
    {
        $this->napVaKiem($this->goiHopLe());

        $kyGia = $this->ky();
        $kyGia->ketQua = ['isSigned' => true, 'data' => '<HSCHUNGTU>CO-CHU-KY</HSCHUNGTU>', 'method' => 'HSM'];
        (new SignCtdtJob('YT001'))->handle($kyGia);

        $guiGia = $this->gui();

        $this->assertContains('CO-CHU-KY', $guiGia->xmlNhanDuoc);
    }

    /** @test */
    public function nap_lai_sau_khi_da_gui_thi_reset_trang_thai_va_giu_lich_su()
    {
        // Noi dung da doi thi chu ky cu khong con ung voi noi dung moi, va ket qua gui cu
        // noi ve mot ban khac. Nhung MaGD cu la dau vet doi soat - phai giu lai.
        $this->napVaKiem($this->goiHopLe());
        $this->ky();
        $this->gui();

        $this->assertSame('GD-001', CtdtHoSo::first()->ma_gd);

        $this->napVaKiem($this->goiHopLe());

        $hoSo = CtdtHoSo::first();

        $this->assertNull($hoSo->ma_gd, 'Nap lai phai reset ket qua gui cu');
        $this->assertFalse((bool) $hoSo->is_signed, 'Nap lai phai reset trang thai ky');
        $this->assertContains('GD-001', (string) $hoSo->lich_su_gui, 'Phai giu dau vet lan gui truoc');
    }

    /** @test */
    public function cong_tu_choi_thi_trang_thai_thanh_CONG_TU_CHOI()
    {
        $this->napVaKiem($this->goiHopLe());
        $this->ky();
        $this->gui('YT001', [
            'ma_ket_qua' => '205', 'ma_gd' => null, 'thoi_gian_tiep_nhan' => null,
            'thong_diep' => 'Mã 205: fileBase64Str không hợp lệ', 'nguyen_van' => '{"MaKetQua":"205"}',
        ]);

        $this->assertSame(CtdtTrangThaiGui::CONG_TU_CHOI, CtdtTrangThaiGui::cua(CtdtHoSo::first()));
    }

    /** @test */
    public function ba_dich_vu_deu_ky_va_gui_duoc()
    {
        $this->napVaKiem($this->goiHopLe());
        $this->napVaKiem($this->goiGbt([
            'MA_GBT' => 'GBT-1', 'HO_TEN' => 'Tran Thi Test',
            'NGAY_SINH' => '19480826', 'NGAY_TV' => '202510070200', 'MA_THE' => 'DN1',
        ]));
        $this->napVaKiem($this->goiGcs([
            'MA_GCS' => 'GCS-1', 'HOTEN_NND' => 'Le Thi Test',
            'NGAYSINH_NND' => '19950101', 'NGAY_SINH_CON' => '202601011200', 'MA_THE_NND' => 'DN2',
        ]));

        $this->assertSame(3, CtdtHoSo::count());

        foreach (CtdtHoSo::all() as $hoSo) {
            $this->ky($hoSo->ma_ho_so);
            $this->gui($hoSo->ma_ho_so);
        }

        foreach (CtdtHoSo::all() as $hoSo) {
            $this->assertTrue((bool) $hoSo->is_signed, $hoSo->ma_ho_so . ' chua ky duoc');
            $this->assertSame('GD-001', $hoSo->ma_gd, $hoSo->ma_ho_so . ' chua gui duoc');
        }
    }
}
```

**Ghi chú:** hai lớp giả `FakeXMLSignService` và `FakeCtdtSubmitService` đã khai trong
`tests/Unit/Ctdt/SignCtdtJobTest.php` và `SubmitCtdtJobTest.php`, cùng namespace `Tests\Unit\Ctdt`,
nên dùng lại được mà không cần khai lại. Nếu PHPUnit chạy riêng lẻ một tệp mà không nạp tệp kia thì
autoload sẽ không tìm thấy — khi đó **tách hai lớp giả ra hai tệp riêng** trong `tests/Support/`
(`FakeXMLSignService.php`, `FakeCtdtSubmitService.php`, namespace `Tests\Support`) và cập nhật cả ba
tệp test dùng chúng. Đừng chép lớp giả thành hai bản.

Nếu bộ dữ liệu mẫu của `goiGbt()` / `goiGcs()` dùng tên thẻ khác với những gì viết ở trên, đọc
`tests/Support/GoiCtdtMau.php` rồi dùng đúng tên thẻ mà nó sinh ra — mục đích của test này là ba dịch
vụ đều đi hết dây chuyền, không phải khoá tên thẻ.

- [ ] **Step 5: Chạy lưới an toàn**

```bash
php vendor/bin/phpunit tests/Unit/Ctdt/CtdtGuiToanLuongTest.php
```

Kỳ vọng: `OK (7 tests)`. Nếu đỏ, thông điệp sẽ nêu đúng mắt xích nào đứt — sửa nơi lệch thật,
đừng nới test.

- [ ] **Step 6: Thêm hai dịch vụ worker vào `install_service.bat`**

Chèn sau khối `QLBV JobCtdt` (thêm ở Giai đoạn 3), bám đúng phong cách các khối sẵn có — **đọc tệp
trước**, tệp này dùng kết thúc dòng CRLF, đừng làm hỏng:

```bat
:: Tạo dịch vụ cho JobSignCtdt (ký số chứng từ điện tử)
%NSSM_PATH%\nssm install "QLBV JobSignCtdt" %PHP_PATH% "%LARAVEL_PATH%artisan queue:work --queue=JobSignCtdt"
%NSSM_PATH%\nssm set "QLBV JobSignCtdt" AppDirectory %LARAVEL_PATH%

:: Tạo dịch vụ cho JobSubmitCtdt (gửi chứng từ điện tử lên cổng BHXH)
%NSSM_PATH%\nssm install "QLBV JobSubmitCtdt" %PHP_PATH% "%LARAVEL_PATH%artisan queue:work --queue=JobSubmitCtdt"
%NSSM_PATH%\nssm set "QLBV JobSubmitCtdt" AppDirectory %LARAVEL_PATH%
```

Và hai dòng khởi động ở khối cuối tệp, sau `nssm start "QLBV JobCtdt"`:

```bat
%NSSM_PATH%\nssm start "QLBV JobSignCtdt"
%NSSM_PATH%\nssm start "QLBV JobSubmitCtdt"
```

- [ ] **Step 7: Cập nhật tài liệu vận hành**

Trong `docs/chung-tu-dien-tu-pl02.md`:

**(a)** Mục "Hiện tại (sau Giai đoạn 3)" → **"(sau Giai đoạn 4)"**. Trong danh sách "Chưa có", bỏ
mục ký số và gửi; giữ lại xuất Excel, lệnh Console `ctdt:import`, dashboard (Giai đoạn 5).

**(b)** Khối "Khi Giai đoạn 3–4 hoàn tất" — hai dịch vụ `JobSignCtdt` / `JobSubmitCtdt` **giờ đã có
sẵn** trong `install_service.bat`, nên bỏ khối hướng dẫn thêm tay và viết lại thành ghi chú rằng cả
ba worker đều được cài bởi script.

**(c)** Thêm một mục mới **"Ký số và gửi"** với nội dung:

```markdown
## Ký số và gửi

Người vận hành mở màn chi tiết một hồ sơ và bấm **Ký và gửi**. Hệ thống xếp hai công việc nối
tiếp: `SignCtdtJob` (hàng đợi `JobSignCtdt`) rồi `SubmitCtdtJob` (hàng đợi `JobSubmitCtdt`).

**Ba cửa chặn, theo đúng thứ tự này:**

| Điều kiện | Nút báo gì | Vì sao chặn |
|---|---|---|
| `submit_enabled = false` | "Chức năng gửi đang tắt" | Không lần gửi nào diễn ra, nên không ghi lỗi — ghi là bịa |
| `checked_at` rỗng | "Hồ sơ chưa kiểm" | `so_loi = 0` của hồ sơ chưa kiểm không có nghĩa là sạch |
| `so_loi > 0` | "Hồ sơ còn lỗi chặn gửi" | Cổng cũng sẽ từ chối; chặn tại chỗ cho thông báo rõ hơn |

**Tệp gửi lên cổng là tệp ĐÃ KÝ trên disk `exportCtdt`**, đường dẫn `da-ky/<dịch vụ>/<mã hồ sơ>.xml`,
không phải phong bì dựng lại lúc gửi. Dựng lại lúc gửi là gửi một gói không có chữ ký.

**Phong bì được dựng lại từ dữ liệu đã lưu, không phải tệp gốc.** Tệp XML người dùng tải lên không
được giữ trên đĩa (cột `duong_dan_goc` chỉ ghi tên tệp), nên chữ ký `CHUKYDONVI` của bên gửi đã mất
từ lúc nạp. Mỗi hồ sơ được đóng thành một gói riêng với `SOLUONGHOSO = 1`.

**Gửi lại được.** Nút đổi thành "Ký và gửi lại" khi hồ sơ đã có `MaGD`. `MaGD` cũ được nối vào cột
`lich_su_gui` trước khi ghi đè — đó là dấu vết đối soát với BHXH, mất nó là tranh chấp không có gì để tra.

**Ba worker phải chạy đủ:**

```sql
SELECT queue, COUNT(*) FROM jobs GROUP BY queue;
```

`JobCtdt`, `JobSignCtdt`, `JobSubmitCtdt` — hàng nào tăng dần mà không giảm nghĩa là worker đó chưa chạy.

**`submit_enabled` mặc định TẮT.** Cổng thật của BHXH nhận là nhận thật. Bật sau khi đã chạy thử và
đối chiếu vài hồ sơ bằng tay.
```

- [ ] **Step 8: Chạy toàn bộ test của module**

```bash
php vendor/bin/phpunit tests/Unit/Ctdt
```

Kỳ vọng: xanh hoàn toàn. Số test cộng dồn của Giai đoạn 4 khoảng 11 + 16 + 14 + 12 + 14 + 11 + 9 = 87,
cộng 325 của các giai đoạn trước → khoảng 412. Con số là chỉ dấu, không phải điều kiện.

- [ ] **Step 9: Chạy hai suite, đối chiếu baseline**

```bash
php vendor/bin/phpunit --testsuite Unit
```

Kỳ vọng: `Errors: 4, Failures: 7` — **đúng bằng baseline**.

```bash
php vendor/bin/phpunit --testsuite Feature
```

Kỳ vọng: `Errors: 8, Failures: 4` — **đúng bằng baseline**.

- [ ] **Step 10: Commit**

```bash
git add app/Jobs/CheckCtdtJob.php install_service.bat docs/chung-tu-dien-tu-pl02.md tests/Unit/Ctdt
git commit -m "feat(ctdt): worker ky/gui, failed() cho job kiem, luoi an toan toan luong"
```

---

## Hoàn tất Giai đoạn 4

Một hồ sơ đi trọn đường: nạp → kiểm → ký → gửi → `MaGD` hiện trên màn danh sách. Ba cửa chặn đứng
đúng chỗ, và cửa đầu tiên — chức năng gửi đang tắt — không để lại một dòng lỗi bịa nào.

**Chưa có và cố ý chưa có:** gửi hàng loạt theo bộ lọc, màn theo dõi tiến độ hàng đợi
(`bhyt.ctdt.jobs.status`), xuất Excel, lệnh Console `ctdt:import`, dashboard. Gửi hàng loạt để dành
đến khi đã chạy tay vài tuần — một lần bấm nhầm gửi hàng trăm hồ sơ sai lên cổng là việc không hoàn
tác được.

**Việc phải kiểm bằng tay sau khi triển khai — không test nào thay được:**

1. **Ký số thật.** Cắm USB token (hoặc bật HSM), bật `sign_enabled`, bấm Ký và gửi với
   `submit_enabled` vẫn **tắt**. Mở tệp trong `D:\XML\ChungTuDienTu\da-ky\CT2025\` và xác nhận thẻ
   `CHUKYDONVI` đã có chữ ký bên trong. Đây là điểm cả kế hoạch dựa trên suy luận từ mã XML3176 —
   phải nhìn tận mắt.
2. **Một hồ sơ thật lên cổng thử.** Bật `submit_enabled`, gửi **đúng một** hồ sơ, đọc `ma_ket_qua`.
   Nếu là `205` thì phong bì sai — mở tab XML gốc đối chiếu.
3. **Đo kích thước.** Xem log dòng `CTDT gui ho so` để biết `so_ky_tu_base64` của hồ sơ lớn nhất,
   đối chiếu với ngưỡng mã `1001` — tài liệu PL02 không nói ngưỡng đó là bao nhiêu.
4. **Cắt mạng giữa chừng** để xác nhận job gửi được hàng đợi thử lại chứ không nuốt lỗi.
5. **Ba worker.** `SELECT queue, COUNT(*) FROM jobs GROUP BY queue` phải về 0 sau khi xong.

**Ghi chú chuyển tiếp cho Giai đoạn 5:** `CtdtTrangThaiGui` và `CtdtDanhSach::locTrangThai()` vẫn là
**hai nguồn sự thật** về trạng thái, chỉ có test tính chất
`bo_loc_trang_thai_khop_voi_CtdtTrangThaiGui_cho_moi_ho_so` buộc chúng khớp. Mỗi trạng thái mới phải
sửa hai chỗ và thêm ca vào bộ dữ liệu của test đó.
