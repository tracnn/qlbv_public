# Chứng từ điện tử PL02 — Giai đoạn 3: Bộ kiểm lỗi — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Mỗi hồ sơ nạp vào được kiểm nội dung tự động, lỗi hiện trên màn danh sách và màn chi tiết, để người vận hành biết hồ sơ nào chưa đủ điều kiện gửi lên cổng BHXH.

**Architecture:** `CtdtChecker` là hàm thuần nhận một mảng `TÊN THẺ => giá trị` và trả mảng lỗi — không đọc CSDL, không biết Eloquent. Quy tắc theo **kiểu trường** nằm trong một bảng dùng chung (`CtdtQuyTac`, khóa theo tên thẻ, giống cách `CtdtNhanTruong` đã làm); quy tắc **bắt buộc** nằm trong một bảng theo loại chứng từ (`CtdtTruongBatBuoc`). `CheckCtdtJob` lo toàn bộ phần đọc/ghi và chạy trong hàng đợi.

**Tech Stack:** Laravel 5.5, PHP 7.4, PHPUnit 6, MySQL / SQLite in-memory (test), hàng đợi database.

## Global Constraints

- **Không dùng `: void` trên `setUp()`** — PHPUnit 6 khai `setUp()` không có kiểu trả về.
- **Không dùng cú pháp PHP 8** — không `match`, không `?->`, không constructor promotion, không named arguments, không `new (expr)`.
- **Laravel 5.5 KHÔNG có `Request::boolean()`** — dùng `filter_var(..., FILTER_VALIDATE_BOOLEAN)`.
- **Không dùng `RefreshDatabase`** — `.env` trỏ `DB_DATABASE=qlbv` là CSDL phát triển thật. Dùng trait `Tests\Support\DungBangCtdtSqlite`.
- **Test phải TỰ ĐẶT cấu hình** bằng `config([...])`, không phụ thuộc `config/organization.php` của máy.
- **Không `git add -f`** các tệp trong `.gitignore`: `config/filesystems.php`, `config/database.php`, `config/auth.php`, `config/organization.php`.
- **Không sửa `truong()`, `$fillable`, hay migration nào** — ba nơi khai cột đang khớp và có lưới an toàn `tests/Unit/Ctdt/CtdtToanVenTest.php` canh. Bảng `ctdt_loi` đã có sẵn từ Giai đoạn 1, **không cần migration mới**.
- **An toàn hiển thị:** nội dung chứng từ đến từ tệp XML bên ngoài. Trong blade dùng `{{ }}`, không `{!! !!}`. Trong JavaScript, mọi giá trị nối vào HTML phải thoát. Nhánh này đã có hai lỗ hổng XSS bị bắt ở các giai đoạn trước.
- **Baseline (2026-08-20):** `php vendor/bin/phpunit --testsuite Unit` cho `Errors: 4, Failures: 7`; `--testsuite Feature` cho `Errors: 8, Failures: 4`. `tests/Unit/Ctdt` hiện `OK (239 tests)`.
- **Đặc tả nguồn:** `docs/superpowers/specs/2026-08-19-chung-tu-dien-tu-pl02-design.md` mục 5.4, và mục 13.2 (rủi ro đã nhận diện trước).

---

## Ba điều chỉnh so với đặc tả mục 5.4

**(1) Gộp ba mã lỗi ngày thành một.** Đặc tả tách `CTDT002` (8 ký tự), `CTDT003` (12), `CTDT004` (14).
Nhưng **cùng một tên thẻ có độ dài khác nhau tùy loại chứng từ**: `NGAY_VAO` của CT03 trong XML mẫu
là `201912121200` (12 ký tự), còn của CT06 là `20251003` (8 ký tự). Không có bảng "tên thẻ → độ dài"
dùng chung nào đúng được. Quy tắc thực tế: **một trường ngày phải là 8, 12 hoặc 14 chữ số VÀ phải là
một ngày có thật**. Bắt được chữ lẫn trong ngày, tháng 13, ngày 32, độ dài lẻ — tức phần lớn lỗi thật.
Không bắt được ca "lẽ ra 12 nhưng ghi 8"; ghim độ dài theo từng loại để dành khi có dữ liệu thật.

**(2) Mã thẻ ở mức CẢNH BÁO, không phải chặn.** PL02 có thẻ `TEKT` (trẻ em không thẻ) với giá trị
`1` là hợp lệ. Đặt `MA_THE` ở mức chặn sẽ chặn nhầm mọi hồ sơ trẻ sơ sinh chưa có thẻ — đúng nhóm
mà giấy chứng sinh phục vụ.

**(3) Bỏ `CTDT010` (độ dài `username`/`password`/`macskcb`).** Ba trường đó là tham số API, không
phải nội dung chứng từ. `macskcb` đã được `CtdtMacskcb::phanGiai()` kiểm từ Giai đoạn 2B.

### Lỗi nội dung KHÔNG chặn nạp

Bộ kiểm **báo lỗi, không từ chối hồ sơ**. Đây là quyết định thiết kế, không phải thiếu sót — đừng
thêm nhánh ném lỗi vào `CtdtImporter` cho các mã `CTDT001`–`CTDT008`.

| | Chặn ở đâu | Ví dụ |
|---|---|---|
| Lỗi cấu trúc | Chặn ngay khi nạp (đã có từ Giai đoạn 2A) | base64 hỏng, `LOAIHOSO` không nhận ra, `<HOSO/>` rỗng |
| Lỗi nội dung (Giai đoạn 3) | Không chặn nạp — chặn ở bước **gửi** | thiếu `HO_TEN`, ngày sai định dạng, `MACSKCB` lệch |

Ba lý do:

1. **Không lưu thì không có gì để xem.** Người vận hành cần mở tab Lỗi, thấy đúng chứng từ nào và
   trường nào sai, rồi quay sang phần mềm sinh XML sửa. Chặn nạp thì bản ghi không tồn tại — họ chỉ
   nhận được một dòng thông báo và không tra cứu được gì.
2. **Một gói có nhiều hồ sơ.** Chặn cả gói vì một trường thiếu sẽ vứt luôn phần đúng.
3. **Chỗ gây hại thật là lúc gửi lên cổng BHXH, không phải lúc lưu vào máy mình.** Cửa chặn đã ở đúng
   chỗ đó: `so_loi > 0` → `CtdtTrangThaiGui::cua()` trả `CON_LOI` → Giai đoạn 4 không cho ký và gửi.

Test `trang_thai_gui_thanh_CON_LOI_khi_bo_kiem_bat_duoc_loi` (Task 7) canh chính bất biến này.

### Bộ mã lỗi cuối cùng

| Mã | Kiểm | Mức |
|---|---|---|
| `CTDT001` | Trường bắt buộc rỗng | chặn |
| `CTDT002` | Trường ngày không phải 8/12/14 chữ số, hoặc không phải ngày có thật | chặn |
| `CTDT003` | `GIOI_TINH` / `GIOI_TINH_CON` ngoài `{1,2,3}` | chặn |
| `CTDT004` | `LOAI_GIAYTO*` ngoài `{0,1,2,3,4}` | chặn |
| `CTDT005` | Trường cờ (`TEKT`, `IS_*`, `SINHCON_*`, `CAP_LAN_DAU`, `DINH_CHI_THAI_NGHEN`) ngoài `{0,1}` | cảnh báo |
| `CTDT006` | Ngày kết thúc sớm hơn ngày bắt đầu | chặn |
| `CTDT007` | `MACSKCB` trong chứng từ khác mã cơ sở của hồ sơ | chặn |
| `CTDT008` | Thiếu trường nên khuyến nghị có (`MA_THE`) | cảnh báo |

`so_loi` trên `ctdt_ho_so` đếm **chỉ lỗi mức chặn** — đó là con số quyết định hồ sơ có được gửi hay không.

---

## File Structure

**Tạo mới:**

| Tệp | Trách nhiệm |
|---|---|
| `app/Services/Ctdt/Kiem/CtdtQuyTac.php` | Bảng kiểu trường theo TÊN THẺ, dùng chung 9 loại |
| `app/Services/Ctdt/Kiem/CtdtTruongBatBuoc.php` | Danh sách trường bắt buộc / khuyến nghị theo loại |
| `app/Services/Ctdt/Kiem/CtdtChecker.php` | Hàm thuần: mảng dữ liệu → mảng lỗi |
| `app/Jobs/CheckCtdtJob.php` | Đọc hồ sơ, chạy checker, ghi `ctdt_loi` + `so_loi` trong một transaction |
| `resources/views/bhyt/ctdt/tab-loi.blade.php` | Tab Lỗi trên màn chi tiết |
| `tests/Unit/Ctdt/*Test.php` | 6 tệp test |

**Sửa:**

| Tệp | Sửa gì |
|---|---|
| `config/ctdt.php` | Thêm danh mục `ma_loi` (mã → mô tả) |
| `app/Services/Ctdt/CtdtImporter.php` | Dispatch `CheckCtdtJob` sau commit |
| `app/Services/Ctdt/CtdtDetailTabs.php` | Thêm tab `__LOI__` |
| `app/Http/Controllers/BHYT/BHYTCtdtController.php` | Nhánh tab Lỗi trong `detailTab()` |
| `tests/Unit/Ctdt/CtdtImporterTest.php`, `CtdtNapToanLuongTest.php`, `CtdtNhapTuTepTest.php` | Thêm `Queue::fake()` |
| `install_service.bat` | Thêm dịch vụ worker `JobCtdt` |

---

## Task 1: Danh mục mã lỗi và bảng kiểu trường

**Files:**
- Modify: `config/ctdt.php`
- Create: `app/Services/Ctdt/Kiem/CtdtQuyTac.php`
- Test: `tests/Unit/Ctdt/CtdtQuyTacTest.php`

**Interfaces:**
- Consumes: `App\Services\Ctdt\CtdtLoaiRegistry::tatCa()` và `$lop::truong()` (Giai đoạn 1)
- Produces:
  - `config('ctdt.ma_loi')` — mảng `mã => ['mo_ta' => string, 'muc_do' => 'chan'|'canh_bao']`
  - `App\Services\Ctdt\Kiem\CtdtQuyTac::kieuCua($tenThe)` → `'ngay'|'gioi_tinh'|'loai_giayto'|'co_khong'|null`
  - Hằng `CtdtQuyTac::CAP_NGAY` — mảng `[thẻ bắt đầu => thẻ kết thúc]` cho quy tắc `CTDT006`

- [ ] **Step 1: Viết test đỏ**

Tạo `tests/Unit/Ctdt/CtdtQuyTacTest.php`:

```php
<?php

namespace Tests\Unit\Ctdt;

use Tests\TestCase;
use App\Services\Ctdt\Kiem\CtdtQuyTac;
use App\Services\Ctdt\CtdtLoaiRegistry;

/**
 * Canh bang kieu truong. Khoa theo TEN THE va dung chung cho ca chin loai - giong cach
 * CtdtNhanTruong da lam, vi ten the lap lai rat nhieu giua cac loai.
 */
class CtdtQuyTacTest extends TestCase
{
    /** @test */
    public function nhan_dien_truong_ngay()
    {
        foreach (['NGAY_SINH', 'NGAY_VAO', 'NGAY_RA', 'NGAYCAP_CCCD', 'NGAY_TV',
                  'NGAYGIO_VV', 'TU_NGAY', 'DEN_NGAY', 'NGAY_SINH_CON', 'NGAYSINH_NND'] as $the) {
            $this->assertSame('ngay', CtdtQuyTac::kieuCua($the), $the . ' phai la truong ngay');
        }
    }

    /** @test */
    public function SO_NGAY_NGHIDUONGTHAI_KHONG_phai_truong_ngay()
    {
        // Day la ly do bang nay phai liet ke TUONG MINH thay vi doan theo ten: mot the
        // chua chu NGAY nhung la SO NGAY, doan theo ten se bat no phai la 'YYYYMMDD'.
        $this->assertNotSame('ngay', CtdtQuyTac::kieuCua('SO_NGAY_NGHIDUONGTHAI'));
    }

    /** @test */
    public function TUOI_THAI_va_SO_CON_khong_phai_truong_ngay()
    {
        $this->assertNull(CtdtQuyTac::kieuCua('TUOI_THAI'));
        $this->assertNull(CtdtQuyTac::kieuCua('SO_CON'));
    }

    /** @test */
    public function nhan_dien_gioi_tinh_va_loai_giay_to()
    {
        $this->assertSame('gioi_tinh', CtdtQuyTac::kieuCua('GIOI_TINH'));
        $this->assertSame('gioi_tinh', CtdtQuyTac::kieuCua('GIOI_TINH_CON'));

        foreach (['LOAI_GIAYTO', 'LOAI_GIAYTO_NND', 'LOAI_GIAYTO_MTH',
                  'LOAI_GIAYTO_CHA_MTH', 'LOAI_GIAYTO_CHA_NND'] as $the) {
            $this->assertSame('loai_giayto', CtdtQuyTac::kieuCua($the), $the);
        }
    }

    /** @test */
    public function nhan_dien_truong_co()
    {
        foreach (['TEKT', 'DINH_CHI_THAI_NGHEN', 'IS_NOI_KHOA', 'IS_NGHIDUONGTHAI',
                  'SINHCON_PHAUTHUAT', 'SINHCON_DUOI32TUAN', 'CAP_LAN_DAU'] as $the) {
            $this->assertSame('co_khong', CtdtQuyTac::kieuCua($the), $the);
        }
    }

    /** @test */
    public function the_khong_co_quy_tac_tra_null()
    {
        $this->assertNull(CtdtQuyTac::kieuCua('CHAN_DOAN'));
        $this->assertNull(CtdtQuyTac::kieuCua('THE_MOI_TINH'));
    }

    /** @test */
    public function moi_the_trong_bang_deu_ton_tai_o_it_nhat_mot_loai()
    {
        // Mot the khai trong bang quy tac ma khong loai nao dung nghia la go sai ten - va
        // quy tac do se khong bao gio chay, im lang.
        $coThat = [];

        foreach (CtdtLoaiRegistry::tatCa() as $lop) {
            foreach (array_keys($lop::truong()) as $the) {
                $coThat[$the] = true;
            }
        }

        foreach (array_keys(CtdtQuyTac::BANG) as $the) {
            $this->assertArrayHasKey($the, $coThat,
                'The "' . $the . '" khai trong CtdtQuyTac nhung khong loai nao co');
        }
    }

    /** @test */
    public function moi_cap_ngay_deu_ton_tai_o_it_nhat_mot_loai()
    {
        $coThat = [];

        foreach (CtdtLoaiRegistry::tatCa() as $lop) {
            foreach (array_keys($lop::truong()) as $the) {
                $coThat[$the] = true;
            }
        }

        foreach (CtdtQuyTac::CAP_NGAY as $dau => $cuoi) {
            $this->assertArrayHasKey($dau, $coThat, 'The bat dau "' . $dau . '" khong loai nao co');
            $this->assertArrayHasKey($cuoi, $coThat, 'The ket thuc "' . $cuoi . '" khong loai nao co');
        }
    }

    /** @test */
    public function danh_muc_ma_loi_du_tam_ma_va_deu_co_muc_do_hop_le()
    {
        $danhMuc = config('ctdt.ma_loi');

        $this->assertCount(8, $danhMuc);

        foreach (['CTDT001', 'CTDT002', 'CTDT003', 'CTDT004',
                  'CTDT005', 'CTDT006', 'CTDT007', 'CTDT008'] as $ma) {
            $this->assertArrayHasKey($ma, $danhMuc, 'Thieu ma loi ' . $ma);
            $this->assertNotEmpty($danhMuc[$ma]['mo_ta'], $ma . ' thieu mo ta');
            $this->assertContains($danhMuc[$ma]['muc_do'], ['chan', 'canh_bao'], $ma . ' sai muc do');
        }
    }

    /** @test */
    public function muc_do_khop_dung_thiet_ke()
    {
        $danhMuc = config('ctdt.ma_loi');

        // Chan: thieu truong bat buoc, ngay sai, gioi tinh sai, loai giay to sai, ngay
        // nguoc, ma co so lech. Canh bao: co 0/1 sai, thieu ma the.
        foreach (['CTDT001', 'CTDT002', 'CTDT003', 'CTDT004', 'CTDT006', 'CTDT007'] as $ma) {
            $this->assertSame('chan', $danhMuc[$ma]['muc_do'], $ma . ' phai la muc chan');
        }

        foreach (['CTDT005', 'CTDT008'] as $ma) {
            $this->assertSame('canh_bao', $danhMuc[$ma]['muc_do'], $ma . ' phai la muc canh bao');
        }
    }

    /** @test */
    public function ma_the_o_muc_canh_bao_vi_tre_em_khong_the_la_hop_le()
    {
        // PL02 co the TEKT (tre em khong the) voi gia tri 1 la hop le. Dat MA_THE o muc
        // chan se chan nham moi ho so tre so sinh - dung nhom ma giay chung sinh phuc vu.
        $this->assertSame('canh_bao', config('ctdt.ma_loi.CTDT008.muc_do'));
    }
}
```

- [ ] **Step 2: Chạy test để xác nhận đỏ**

```bash
php vendor/bin/phpunit tests/Unit/Ctdt/CtdtQuyTacTest.php
```

Kỳ vọng: đỏ — `Class 'App\Services\Ctdt\Kiem\CtdtQuyTac' not found`.

- [ ] **Step 3: Thêm danh mục mã lỗi vào `config/ctdt.php`**

Chèn vào mảng trả về, ngay sau khối `'ma_ket_qua_token' => [...]`:

```php
    // Danh muc ma loi cua bo kiem noi dung.
    //
    // VI SAO O CONFIG chu khong phai mot bang danh muc nhu xml3176_error_catalogs: ma loi
    // o day do TA tu dinh nghia tu dac ta PL02, khong phai do BHXH ban hanh va cap nhat
    // dinh ky. Mot bang danh muc chi co nghia khi co nguoi ngoai doi noi dung cua no.
    //
    // muc_do 'chan'    -> tinh vao ctdt_ho_so.so_loi, ho so khong duoc gui
    // muc_do 'canh_bao' -> hien cho nguoi doc, KHONG chan gui
    'ma_loi' => [
        'CTDT001' => ['mo_ta' => 'Thiếu trường bắt buộc',                    'muc_do' => 'chan'],
        'CTDT002' => ['mo_ta' => 'Trường ngày sai định dạng',                'muc_do' => 'chan'],
        'CTDT003' => ['mo_ta' => 'Giới tính ngoài giá trị cho phép',         'muc_do' => 'chan'],
        'CTDT004' => ['mo_ta' => 'Loại giấy tờ ngoài giá trị cho phép',      'muc_do' => 'chan'],
        'CTDT005' => ['mo_ta' => 'Trường cờ ngoài giá trị 0/1',              'muc_do' => 'canh_bao'],
        'CTDT006' => ['mo_ta' => 'Ngày kết thúc sớm hơn ngày bắt đầu',       'muc_do' => 'chan'],
        'CTDT007' => ['mo_ta' => 'Mã cơ sở trong chứng từ lệch với hồ sơ',   'muc_do' => 'chan'],
        'CTDT008' => ['mo_ta' => 'Thiếu mã thẻ BHYT',                        'muc_do' => 'canh_bao'],
    ],
```

- [ ] **Step 4: Viết `CtdtQuyTac`**

Tạo `app/Services/Ctdt/Kiem/CtdtQuyTac.php`:

```php
<?php

namespace App\Services\Ctdt\Kiem;

/**
 * Bang KIEU TRUONG, khoa theo ten the, dung chung cho ca chin loai chung tu.
 *
 * VI SAO MOT BANG CHUNG: ten the lap lai rat nhieu giua chin loai (NGAY_SINH, GIOI_TINH,
 * LOAI_GIAYTO... co mat o gan het). Khai rieng cho tung loai la chep cung mot quy tac chin
 * lan, va chin ban se lech nhau. Giong cach CtdtNhanTruong da lam voi nhan hien thi.
 *
 * VI SAO LIET KE TUONG MINH chu khong doan theo ten: 'SO_NGAY_NGHIDUONGTHAI' chua chu
 * NGAY nhung la MOT SO DEM, khong phai ngay. Doan theo ten se bat no phai co dang
 * 'YYYYMMDD' va sinh loi gia cho moi ho so nghi duong thai.
 */
class CtdtQuyTac
{
    /** @var array TEN THE => kieu truong */
    const BANG = [
        // ── Truong ngay ────────────────────────────────────────────────────────
        // Do dai KHONG dong nhat giua cac loai: NGAY_VAO cua CT03 la 12 ky tu con cua
        // CT06 la 8. Nen quy tac chi doi 8/12/14 chu so VA la mot ngay co that.
        'NGAY_SINH'               => 'ngay',
        'NGAY_VAO'                => 'ngay',
        'NGAY_RA'                 => 'ngay',
        'NGAY_CT'                 => 'ngay',
        'NGAY_CHUNG_TU'           => 'ngay',
        'NGAY_KCB'                => 'ngay',
        'NGAYCAP_CCCD'            => 'ngay',
        'NGOAITRU_TUNGAY'         => 'ngay',
        'NGOAITRU_DENNGAY'        => 'ngay',
        'NGAY_SINHCON'            => 'ngay',
        'NGAY_CHETCON'            => 'ngay',
        'TU_NGAY'                 => 'ngay',
        'DEN_NGAY'                => 'ngay',
        'NGAY_DINH_CHI_THAINGHEN' => 'ngay',
        'NGAY_CAP'                => 'ngay',
        'NGAYGIO_VV'              => 'ngay',
        'NGAY_TV'                 => 'ngay',
        'NGAY_CAPGIAYBT'          => 'ngay',
        'NGAY_SINH_CON'           => 'ngay',
        'NGAYSINH_NND'            => 'ngay',
        'NGAYCAP_CCCD_NND'        => 'ngay',
        'NGAYSINH_MTH'            => 'ngay',
        'NGAYCAP_CCCD_MTH'        => 'ngay',
        'NGAYSINH_CHA_MTH'        => 'ngay',
        'NGAYCAP_CCCD_CHA_MTH'    => 'ngay',
        'NGAYSINH_CHA_NND'        => 'ngay',
        'NGAYCAP_CCCD_CHA_NND'    => 'ngay',

        // ── Gioi tinh: 1 Nam, 2 Nu, 3 chua xac dinh ────────────────────────────
        'GIOI_TINH'     => 'gioi_tinh',
        'GIOI_TINH_CON' => 'gioi_tinh',

        // ── Loai giay to: 0 khong giay to, 1 CCCD, 2 CMND, 3 ho chieu, 4 dinh danh ──
        'LOAI_GIAYTO'          => 'loai_giayto',
        'LOAI_GIAYTO_NND'      => 'loai_giayto',
        'LOAI_GIAYTO_MTH'      => 'loai_giayto',
        'LOAI_GIAYTO_CHA_MTH'  => 'loai_giayto',
        'LOAI_GIAYTO_CHA_NND'  => 'loai_giayto',

        // ── Truong co: chi 0 hoac 1 ────────────────────────────────────────────
        'TEKT'                       => 'co_khong',
        'DINH_CHI_THAI_NGHEN'        => 'co_khong',
        'IS_NOI_KHOA'                => 'co_khong',
        'IS_PHAU_THUAT_THU_THUAT'    => 'co_khong',
        'IS_LAO_GIAI_DOAN_NANG'      => 'co_khong',
        'IS_XO_GAN_GIAI_DOAN_MAT_BU' => 'co_khong',
        'IS_NGHIDUONGTHAI'           => 'co_khong',
        'SINHCON_PHAUTHUAT'          => 'co_khong',
        'SINHCON_DUOI32TUAN'         => 'co_khong',
        'CAP_LAN_DAU'                => 'co_khong',
    ];

    /**
     * Cac cap ngay bat dau - ket thuc, cho quy tac CTDT006.
     *
     * So sanh tren TAM ky tu dau (phan ngay), vi hai the trong cung mot cap co the khac
     * do dai: NGAYGIO_VV la 12 ky tu con NGAY_TV cung 12, nhung NGAY_VAO/NGAY_RA cua CT06
     * lai la 8. So sanh ca chuoi se cho ket qua sai khi do dai lech.
     *
     * @var array THE BAT DAU => THE KET THUC
     */
    const CAP_NGAY = [
        'NGAY_VAO'   => 'NGAY_RA',
        'TU_NGAY'    => 'DEN_NGAY',
        'NGAYGIO_VV' => 'NGAY_TV',
    ];

    /** @return string|null 'ngay' | 'gioi_tinh' | 'loai_giayto' | 'co_khong' | null */
    public static function kieuCua($tenThe)
    {
        $tenThe = (string) $tenThe;

        return isset(self::BANG[$tenThe]) ? self::BANG[$tenThe] : null;
    }
}
```

- [ ] **Step 5: Chạy test để xác nhận xanh**

```bash
php vendor/bin/phpunit tests/Unit/Ctdt/CtdtQuyTacTest.php
```

Kỳ vọng: `OK (11 tests)`.

- [ ] **Step 6: Commit**

```bash
git add config/ctdt.php app/Services/Ctdt/Kiem tests/Unit/Ctdt/CtdtQuyTacTest.php
git commit -m "feat(ctdt): danh muc ma loi va bang kieu truong"
```

---

## Task 2: Bảng trường bắt buộc theo loại

**Files:**
- Create: `app/Services/Ctdt/Kiem/CtdtTruongBatBuoc.php`
- Test: `tests/Unit/Ctdt/CtdtTruongBatBuocTest.php`

**Interfaces:**
- Consumes: `CtdtLoaiRegistry::tatCa()` và `$lop::truong()` (Giai đoạn 1)
- Produces:
  - `CtdtTruongBatBuoc::cua($loaiHoSo): array` — danh sách tên thẻ bắt buộc (mức chặn)
  - `CtdtTruongBatBuoc::khuyenNghi($loaiHoSo): array` — danh sách tên thẻ khuyến nghị (mức cảnh báo)

**Danh sách đã được chủ dự án duyệt.** Nguyên tắc: chỉ những trường mà thiếu là hồ sơ vô nghĩa
hoặc cổng chắc chắn từ chối. Mã thẻ nằm ở **khuyến nghị**, không phải bắt buộc — PL02 có thẻ `TEKT`
(trẻ em không thẻ) với giá trị `1` là hợp lệ.

- [ ] **Step 1: Viết test đỏ**

Tạo `tests/Unit/Ctdt/CtdtTruongBatBuocTest.php`:

```php
<?php

namespace Tests\Unit\Ctdt;

use Tests\TestCase;
use App\Services\Ctdt\Kiem\CtdtTruongBatBuoc;
use App\Services\Ctdt\CtdtLoaiRegistry;

class CtdtTruongBatBuocTest extends TestCase
{
    /** @test */
    public function moi_loai_deu_co_danh_sach_bat_buoc_khong_rong()
    {
        foreach (array_keys(CtdtLoaiRegistry::tatCa()) as $loai) {
            $this->assertNotEmpty(CtdtTruongBatBuoc::cua($loai),
                $loai . ' phai co it nhat mot truong bat buoc');
        }
    }

    /** @test */
    public function moi_the_bat_buoc_deu_ton_tai_trong_truong_cua_loai_do()
    {
        // Bat buoc mot the ma loai do khong co nghia la MOI ho so loai do deu bao loi -
        // va khong ai sua duoc, vi the do khong bao gio ton tai.
        foreach (CtdtLoaiRegistry::tatCa() as $loai => $lop) {
            $truong = $lop::truong();

            foreach (CtdtTruongBatBuoc::cua($loai) as $the) {
                $this->assertArrayHasKey($the, $truong,
                    $loai . ': the bat buoc "' . $the . '" khong co trong truong()');
            }

            foreach (CtdtTruongBatBuoc::khuyenNghi($loai) as $the) {
                $this->assertArrayHasKey($the, $truong,
                    $loai . ': the khuyen nghi "' . $the . '" khong co trong truong()');
            }
        }
    }

    /** @test */
    public function khoa_nghiep_vu_la_bat_buoc_o_nhung_loai_co_no()
    {
        $this->assertContains('MA_YTE', CtdtTruongBatBuoc::cua('CT03'));
        $this->assertContains('MA_YTE', CtdtTruongBatBuoc::cua('GIAYDIEUTRINOITRU'));
        $this->assertContains('MA_GBT', CtdtTruongBatBuoc::cua('GIAYBAOTU'));
        $this->assertContains('MA_GCS', CtdtTruongBatBuoc::cua('GIAYCHUNGSINH'));
    }

    /** @test */
    public function ba_loai_khong_co_MA_YTE_thi_khong_doi_no()
    {
        // CT04, CT06, CT07 khong co the MA_YTE trong dac ta.
        foreach (['CT04', 'CT06', 'CT07'] as $loai) {
            $this->assertNotContains('MA_YTE', CtdtTruongBatBuoc::cua($loai));
        }
    }

    /** @test */
    public function ho_ten_va_ngay_sinh_bat_buoc_o_moi_loai()
    {
        foreach (CtdtLoaiRegistry::tatCa() as $loai => $lop) {
            $batBuoc = CtdtTruongBatBuoc::cua($loai);
            $truong = $lop::truong();

            $coHoTen = array_key_exists('HO_TEN', $truong) ? 'HO_TEN' : 'HOTEN_NND';
            $coNgaySinh = array_key_exists('NGAY_SINH', $truong) ? 'NGAY_SINH' : 'NGAYSINH_NND';

            $this->assertContains($coHoTen, $batBuoc, $loai . ' phai bat buoc ho ten');
            $this->assertContains($coNgaySinh, $batBuoc, $loai . ' phai bat buoc ngay sinh');
        }
    }

    /** @test */
    public function ma_the_o_muc_khuyen_nghi_khong_phai_bat_buoc()
    {
        // Tre em khong the (TEKT = 1) la hop le va khong co MA_THE. Dat o muc bat buoc se
        // chan nham dung nhom ma giay chung sinh phuc vu.
        $this->assertNotContains('MA_THE', CtdtTruongBatBuoc::cua('CT03'));
        $this->assertContains('MA_THE', CtdtTruongBatBuoc::khuyenNghi('CT03'));
    }

    /** @test */
    public function loai_la_tra_mang_rong_khong_nem()
    {
        // Loai la duoc CtdtChecker bo qua; nem o day se lam ca ho so hong vi mot loai
        // ma bo kiem chua biet den.
        $this->assertSame([], CtdtTruongBatBuoc::cua('KHONG_TON_TAI'));
        $this->assertSame([], CtdtTruongBatBuoc::khuyenNghi('KHONG_TON_TAI'));
    }
}
```

- [ ] **Step 2: Chạy test để xác nhận đỏ**

```bash
php vendor/bin/phpunit tests/Unit/Ctdt/CtdtTruongBatBuocTest.php
```

Kỳ vọng: đỏ — `Class 'App\Services\Ctdt\Kiem\CtdtTruongBatBuoc' not found`.

- [ ] **Step 3: Viết `CtdtTruongBatBuoc`**

Tạo `app/Services/Ctdt/Kiem/CtdtTruongBatBuoc.php`:

```php
<?php

namespace App\Services\Ctdt\Kiem;

/**
 * Truong bat buoc va truong khuyen nghi, theo tung loai chung tu.
 *
 * VI SAO KHONG LAY TU DAC TA: PL02 chi danh dau cot "Bat buoc" cho THAM SO API (muc 2),
 * con cac bang mo ta the cua CT03/CT04/... (muc 9.2, 10.2...) khong co cot do. Danh sach
 * duoi day la quyet dinh nghiep vu, da duoc chu du an duyet.
 *
 * NGUYEN TAC: chi dua vao muc BAT BUOC nhung truong ma thieu la ho so vo nghia hoac cong
 * chac chan tu choi. Bat buoc rong tay se sinh mot bien lo, va nguoi van hanh se hoc cach
 * bo qua ca cot so loi.
 *
 * MA_THE nam o KHUYEN NGHI chu khong phai bat buoc: PL02 co the TEKT (tre em khong the)
 * voi gia tri 1 la hop le. Dat o muc bat buoc se chan nham moi ho so tre so sinh - dung
 * nhom ma giay chung sinh phuc vu.
 */
class CtdtTruongBatBuoc
{
    /** @var array LOAIHOSO => danh sach the bat buoc (muc chan) */
    const BAT_BUOC = [
        'CT03'              => ['MA_YTE', 'HO_TEN', 'NGAY_SINH', 'NGAY_VAO', 'NGAY_RA'],
        'CT04'              => ['HO_TEN', 'NGAY_SINH', 'NGAY_VAO', 'NGAY_RA'],
        'CT06'              => ['HO_TEN', 'NGAY_SINH', 'NGAY_VAO', 'NGAY_RA'],
        'CT07'              => ['HO_TEN', 'NGAY_SINH', 'TU_NGAY', 'DEN_NGAY'],
        'GIAYDIEUTRINOITRU' => ['MA_YTE', 'HO_TEN', 'NGAY_SINH', 'NGAY_VAO', 'NGAY_RA'],
        'GIAYDIEUTRIVOSINH' => ['MA_YTE', 'HO_TEN', 'NGAY_SINH', 'NGAY_VAO', 'NGAY_RA'],
        'GIAYSUCKHOEME'     => ['MA_YTE', 'HO_TEN', 'NGAY_SINH', 'NGAY_VAO', 'NGAY_RA'],
        'GIAYBAOTU'         => ['MA_GBT', 'HO_TEN', 'NGAY_SINH', 'NGAY_TV'],
        'GIAYCHUNGSINH'     => ['MA_GCS', 'HOTEN_NND', 'NGAYSINH_NND', 'NGAY_SINH_CON'],
    ];

    /** @var array LOAIHOSO => danh sach the khuyen nghi (muc canh bao) */
    const KHUYEN_NGHI = [
        'CT03'              => ['MA_THE'],
        'CT04'              => ['MA_THE'],
        'CT06'              => ['MA_THE'],
        'CT07'              => ['MA_THE'],
        'GIAYDIEUTRINOITRU' => ['MA_THE'],
        'GIAYDIEUTRIVOSINH' => ['MA_THE'],
        'GIAYSUCKHOEME'     => ['MA_THE'],
        'GIAYBAOTU'         => ['MA_THE'],
        'GIAYCHUNGSINH'     => ['MA_THE_NND'],
    ];

    /** @return array Mang rong voi loai la - loai do do CtdtChecker bo qua, khong nem */
    public static function cua($loaiHoSo)
    {
        return isset(self::BAT_BUOC[$loaiHoSo]) ? self::BAT_BUOC[$loaiHoSo] : [];
    }

    /** @return array */
    public static function khuyenNghi($loaiHoSo)
    {
        return isset(self::KHUYEN_NGHI[$loaiHoSo]) ? self::KHUYEN_NGHI[$loaiHoSo] : [];
    }
}
```

- [ ] **Step 4: Chạy test để xác nhận xanh**

```bash
php vendor/bin/phpunit tests/Unit/Ctdt/CtdtTruongBatBuocTest.php
```

Kỳ vọng: `OK (7 tests)`.

- [ ] **Step 5: Commit**

```bash
git add app/Services/Ctdt/Kiem/CtdtTruongBatBuoc.php tests/Unit/Ctdt/CtdtTruongBatBuocTest.php
git commit -m "feat(ctdt): bang truong bat buoc va khuyen nghi theo loai"
```

---

## Task 3: `CtdtChecker` — hàm thuần sinh lỗi

**Files:**
- Create: `app/Services/Ctdt/Kiem/CtdtChecker.php`
- Test: `tests/Unit/Ctdt/CtdtCheckerTest.php`

**Interfaces:**
- Consumes: `CtdtQuyTac::kieuCua()`, `CtdtQuyTac::CAP_NGAY` (Task 1); `CtdtTruongBatBuoc::cua()`, `::khuyenNghi()` (Task 2); `config('ctdt.ma_loi')` (Task 1)
- Produces: `CtdtChecker::kiem($loaiHoSo, array $duLieu, $macskcbHoSo): array` — mảng các lỗi, mỗi lỗi là `['ma_loi' => string, 'ten_truong' => string|null, 'mo_ta' => string, 'muc_do' => 'chan'|'canh_bao']`. `$duLieu` là mảng `TÊN THẺ => giá trị chuỗi`.

- [ ] **Step 1: Viết test đỏ**

Tạo `tests/Unit/Ctdt/CtdtCheckerTest.php`:

```php
<?php

namespace Tests\Unit\Ctdt;

use Tests\TestCase;
use App\Services\Ctdt\Kiem\CtdtChecker;

/**
 * Bo kiem la HAM THUAN: nhan mang du lieu, tra mang loi. Khong doc CSDL, khong biet
 * Eloquent - nen kiem duoc het cac nhanh ma khong can dung mot bang nao.
 */
class CtdtCheckerTest extends TestCase
{
    /** Bo du lieu CT03 hop le, de tung test chi thay doi dung thu no dang kiem. */
    private function ct03HopLe(array $ghiDe = [])
    {
        return array_merge([
            'MA_YTE'    => 'YT001',
            'HO_TEN'    => 'Nguyen Van Test',
            'NGAY_SINH' => '19950914',
            'NGAY_VAO'  => '201912121200',
            'NGAY_RA'   => '201912180001',
            'MA_THE'    => 'DN1234567890',
            'GIOI_TINH' => '1',
        ], $ghiDe);
    }

    private function maLoi(array $loi)
    {
        return array_column($loi, 'ma_loi');
    }

    /** @test */
    public function ho_so_hop_le_khong_sinh_loi_nao()
    {
        $this->assertSame([], CtdtChecker::kiem('CT03', $this->ct03HopLe(), '01929'));
    }

    /** @test */
    public function thieu_truong_bat_buoc_sinh_CTDT001_muc_chan()
    {
        $loi = CtdtChecker::kiem('CT03', $this->ct03HopLe(['HO_TEN' => '']), '01929');

        $this->assertContains('CTDT001', $this->maLoi($loi));
        $this->assertSame('HO_TEN', $loi[0]['ten_truong']);
        $this->assertSame('chan', $loi[0]['muc_do']);
        $this->assertContains('HO_TEN', $loi[0]['mo_ta'], 'Mo ta phai neu ten truong');
    }

    /** @test */
    public function truong_bat_buoc_vang_han_cung_sinh_CTDT001()
    {
        $duLieu = $this->ct03HopLe();
        unset($duLieu['MA_YTE']);

        $this->assertContains('CTDT001', $this->maLoi(CtdtChecker::kiem('CT03', $duLieu, '01929')));
    }

    /** @test */
    public function truong_bat_buoc_chi_co_khoang_trang_van_la_thieu()
    {
        $loi = CtdtChecker::kiem('CT03', $this->ct03HopLe(['HO_TEN' => '   ']), '01929');

        $this->assertContains('CTDT001', $this->maLoi($loi));
    }

    /** @test */
    public function thieu_ma_the_chi_la_canh_bao()
    {
        // Tre em khong the (TEKT = 1) la hop le. Chan o day se chan nham ho so tre so sinh.
        $loi = CtdtChecker::kiem('CT03', $this->ct03HopLe(['MA_THE' => '']), '01929');

        $ma = $this->maLoi($loi);
        $this->assertContains('CTDT008', $ma);
        $this->assertNotContains('CTDT001', $ma, 'Ma the KHONG duoc la loi muc chan');
        $this->assertSame('canh_bao', $loi[0]['muc_do']);
    }

    /** @test */
    public function ngay_chap_nhan_ca_ba_do_dai()
    {
        foreach (['20251003', '202510031530', '20251003153045'] as $ngay) {
            $loi = CtdtChecker::kiem('CT03', $this->ct03HopLe(['NGAY_VAO' => $ngay, 'NGAY_RA' => $ngay]), '01929');

            $this->assertSame([], $loi, 'Ngay ' . $ngay . ' phai hop le');
        }
    }

    /** @test */
    public function ngay_co_chu_sinh_CTDT002()
    {
        $loi = CtdtChecker::kiem('CT03', $this->ct03HopLe(['NGAY_SINH' => '1995091X']), '01929');

        $this->assertContains('CTDT002', $this->maLoi($loi));
    }

    /** @test */
    public function ngay_do_dai_le_sinh_CTDT002()
    {
        $loi = CtdtChecker::kiem('CT03', $this->ct03HopLe(['NGAY_SINH' => '1995091']), '01929');

        $this->assertContains('CTDT002', $this->maLoi($loi));
    }

    /** @test */
    public function ngay_khong_co_that_sinh_CTDT002()
    {
        // Thang 13 va ngay 32: dung do dai, dung chu so, nhung khong ton tai tren lich.
        foreach (['19951301', '19950932'] as $ngay) {
            $loi = CtdtChecker::kiem('CT03', $this->ct03HopLe(['NGAY_SINH' => $ngay]), '01929');

            $this->assertContains('CTDT002', $this->maLoi($loi), $ngay . ' phai bi bat');
        }
    }

    /** @test */
    public function ngay_29_thang_2_nam_khong_nhuan_bi_bat()
    {
        $loi = CtdtChecker::kiem('CT03', $this->ct03HopLe(['NGAY_SINH' => '19950229']), '01929');

        $this->assertContains('CTDT002', $this->maLoi($loi));
    }

    /** @test */
    public function ngay_29_thang_2_nam_nhuan_hop_le()
    {
        $loi = CtdtChecker::kiem('CT03', $this->ct03HopLe(['NGAY_SINH' => '19960229']), '01929');

        $this->assertSame([], $loi);
    }

    /** @test */
    public function truong_ngay_rong_khong_bi_kiem_dinh_dang()
    {
        // Truong ngay KHONG bat buoc va de trong la hop le. Bat dinh dang o o trong se
        // sinh mot bien loi gia cho moi ho so.
        $loi = CtdtChecker::kiem('CT03', $this->ct03HopLe(['NGOAITRU_TUNGAY' => '']), '01929');

        $this->assertSame([], $loi);
    }

    /** @test */
    public function gioi_tinh_ngoai_mien_sinh_CTDT003()
    {
        $loi = CtdtChecker::kiem('CT03', $this->ct03HopLe(['GIOI_TINH' => '4']), '01929');

        $this->assertContains('CTDT003', $this->maLoi($loi));
    }

    /** @test */
    public function gioi_tinh_1_2_3_deu_hop_le()
    {
        foreach (['1', '2', '3'] as $g) {
            $this->assertSame([], CtdtChecker::kiem('CT03', $this->ct03HopLe(['GIOI_TINH' => $g]), '01929'));
        }
    }

    /** @test */
    public function loai_giay_to_ngoai_mien_sinh_CTDT004()
    {
        $loi = CtdtChecker::kiem('CT03', $this->ct03HopLe(['LOAI_GIAYTO' => '9']), '01929');

        $this->assertContains('CTDT004', $this->maLoi($loi));
    }

    /** @test */
    public function loai_giay_to_0_den_4_deu_hop_le()
    {
        foreach (['0', '1', '2', '3', '4'] as $l) {
            $this->assertSame([], CtdtChecker::kiem('CT03', $this->ct03HopLe(['LOAI_GIAYTO' => $l]), '01929'));
        }
    }

    /** @test */
    public function truong_co_ngoai_0_1_chi_la_canh_bao()
    {
        $loi = CtdtChecker::kiem('CT03', $this->ct03HopLe(['TEKT' => '2']), '01929');

        $this->assertContains('CTDT005', $this->maLoi($loi));
        $this->assertSame('canh_bao', $loi[0]['muc_do']);
    }

    /** @test */
    public function ngay_ra_som_hon_ngay_vao_sinh_CTDT006()
    {
        $loi = CtdtChecker::kiem('CT03', $this->ct03HopLe([
            'NGAY_VAO' => '201912181200',
            'NGAY_RA'  => '201912120001',
        ]), '01929');

        $this->assertContains('CTDT006', $this->maLoi($loi));
    }

    /** @test */
    public function ngay_ra_cung_ngay_voi_ngay_vao_la_hop_le()
    {
        // Kham roi ra trong ngay la chuyen thuong. So sanh phai la "som hon", khong phai
        // "khong lon hon".
        $loi = CtdtChecker::kiem('CT03', $this->ct03HopLe([
            'NGAY_VAO' => '201912120800',
            'NGAY_RA'  => '201912121600',
        ]), '01929');

        $this->assertSame([], $loi);
    }

    /** @test */
    public function cap_ngay_lech_do_dai_van_so_sanh_dung()
    {
        // CT06 dung NGAY_VAO/NGAY_RA dang 8 ky tu, CT03 dang 12. So sanh ca chuoi se cho
        // ket qua sai khi hai the trong cung mot cap khac do dai.
        $loi = CtdtChecker::kiem('CT06', [
            'HO_TEN' => 'Tran Thi Test', 'NGAY_SINH' => '19480826',
            'NGAY_VAO' => '20251030', 'NGAY_RA' => '202510031530',
        ], '01929');

        $this->assertContains('CTDT006', $this->maLoi($loi), 'NGAY_RA 03/10 som hon NGAY_VAO 30/10');
    }

    /** @test */
    public function chi_mot_ve_cua_cap_ngay_co_gia_tri_thi_khong_so_sanh()
    {
        $loi = CtdtChecker::kiem('CT03', $this->ct03HopLe(['NGAY_RA' => '']), '01929');

        // Van co CTDT001 vi NGAY_RA bat buoc, nhung KHONG duoc co CTDT006.
        $this->assertNotContains('CTDT006', $this->maLoi($loi));
    }

    /** @test */
    public function macskcb_trong_chung_tu_lech_voi_ho_so_sinh_CTDT007()
    {
        $loi = CtdtChecker::kiem('GIAYBAOTU', [
            'MA_GBT' => 'GBT-1', 'HO_TEN' => 'Nguyen Van Test',
            'NGAY_SINH' => '20220101', 'NGAY_TV' => '202510070200',
            'MACSKCB' => '37470',
        ], '01929');

        $this->assertContains('CTDT007', $this->maLoi($loi));
    }

    /** @test */
    public function macskcb_khop_thi_khong_sinh_loi()
    {
        $loi = CtdtChecker::kiem('GIAYBAOTU', [
            'MA_GBT' => 'GBT-1', 'HO_TEN' => 'Nguyen Van Test',
            'NGAY_SINH' => '20220101', 'NGAY_TV' => '202510070200',
            'MACSKCB' => '01929', 'MA_THE' => 'DN1',
        ], '01929');

        $this->assertSame([], $loi);
    }

    /** @test */
    public function chung_tu_khong_khai_macskcb_thi_khong_kiem()
    {
        // Chi CT2025 va giay bao tu mang MACSKCB. Cac loai khac khong co the do.
        $loi = CtdtChecker::kiem('CT03', $this->ct03HopLe(), '01929');

        $this->assertNotContains('CTDT007', $this->maLoi($loi));
    }

    /** @test */
    public function loai_la_tra_mang_rong_khong_nem()
    {
        // Loai chua co trong bang bat buoc: bo kiem khong biet doi gi, nen khong doi gi.
        // Nem o day se lam ca job kiem do vi mot loai moi cua BHXH.
        $this->assertSame([], CtdtChecker::kiem('CT99', ['GI_DO' => 'x'], '01929'));
    }

    /** @test */
    public function moi_loi_deu_co_du_bon_khoa()
    {
        $loi = CtdtChecker::kiem('CT03', $this->ct03HopLe([
            'HO_TEN' => '', 'NGAY_SINH' => 'xxxx', 'GIOI_TINH' => '9', 'TEKT' => '5',
        ]), '01929');

        $this->assertNotEmpty($loi);

        foreach ($loi as $mot) {
            $this->assertSame(['ma_loi', 'ten_truong', 'mo_ta', 'muc_do'], array_keys($mot));
            $this->assertNotEmpty($mot['mo_ta']);
        }
    }

    /** @test */
    public function moi_ma_loi_sinh_ra_deu_co_trong_danh_muc_config()
    {
        // Sinh mot ma khong co trong danh muc thi man hinh se hien mot dong loi khong ai
        // tra cuu duoc.
        $loi = CtdtChecker::kiem('CT03', $this->ct03HopLe([
            'HO_TEN' => '', 'NGAY_SINH' => 'xxxx', 'GIOI_TINH' => '9',
            'LOAI_GIAYTO' => '9', 'TEKT' => '5', 'MA_THE' => '',
        ]), '01929');

        $danhMuc = config('ctdt.ma_loi');

        foreach ($loi as $mot) {
            $this->assertArrayHasKey($mot['ma_loi'], $danhMuc, 'Ma la: ' . $mot['ma_loi']);
            $this->assertSame($danhMuc[$mot['ma_loi']]['muc_do'], $mot['muc_do'],
                $mot['ma_loi'] . ': muc do phai lay tu danh muc, khong go tay');
        }
    }
}
```

- [ ] **Step 2: Chạy test để xác nhận đỏ**

```bash
php vendor/bin/phpunit tests/Unit/Ctdt/CtdtCheckerTest.php
```

Kỳ vọng: đỏ — `Class 'App\Services\Ctdt\Kiem\CtdtChecker' not found`.

- [ ] **Step 3: Viết `CtdtChecker`**

Tạo `app/Services/Ctdt/Kiem/CtdtChecker.php`:

```php
<?php

namespace App\Services\Ctdt\Kiem;

/**
 * Kiem noi dung MOT chung tu. HAM THUAN: nhan mang du lieu, tra mang loi.
 *
 * Khong doc CSDL, khong biet Eloquent, khong ghi gi. Phan doc/ghi la viec cua
 * CheckCtdtJob. Nho vay kiem duoc het cac nhanh ma khong can dung mot bang nao.
 *
 * MUC DO khong go tay o tung cho sinh loi ma LAY TU config('ctdt.ma_loi'): hai nguon su
 * that cho cung mot muc do la cach chac chan de mot ngay nao do man hinh hien "canh bao"
 * cho mot loi dang duoc tinh vao so_loi.
 */
class CtdtChecker
{
    /**
     * @param string $loaiHoSo    Gia tri LOAIHOSO
     * @param array  $duLieu      TEN THE => gia tri chuoi
     * @param string $macskcbHoSo Ma co so cua ho so, de doi chieu voi the MACSKCB
     * @return array Mang ['ma_loi' =>, 'ten_truong' =>, 'mo_ta' =>, 'muc_do' =>]
     */
    public static function kiem($loaiHoSo, array $duLieu, $macskcbHoSo)
    {
        $loi = [];

        self::kiemBatBuoc($loaiHoSo, $duLieu, $loi);
        self::kiemKhuyenNghi($loaiHoSo, $duLieu, $loi);
        self::kiemKieuTruong($duLieu, $loi);
        self::kiemCapNgay($duLieu, $loi);
        self::kiemMacskcb($duLieu, $macskcbHoSo, $loi);

        return $loi;
    }

    private static function kiemBatBuoc($loaiHoSo, array $duLieu, array &$loi)
    {
        foreach (CtdtTruongBatBuoc::cua($loaiHoSo) as $the) {
            if (self::trong($duLieu, $the)) {
                $loi[] = self::loi('CTDT001', $the, 'Thiếu trường bắt buộc ' . $the);
            }
        }
    }

    private static function kiemKhuyenNghi($loaiHoSo, array $duLieu, array &$loi)
    {
        foreach (CtdtTruongBatBuoc::khuyenNghi($loaiHoSo) as $the) {
            if (self::trong($duLieu, $the)) {
                $loi[] = self::loi('CTDT008', $the, 'Thiếu ' . $the
                    . ' (chấp nhận được nếu là trẻ em không thẻ)');
            }
        }
    }

    /**
     * O TRONG KHONG BI KIEM DINH DANG. Truong khong bat buoc de trong la hop le; bat dinh
     * dang o o trong se sinh mot bien loi gia cho moi ho so, va nguoi van hanh se hoc cach
     * bo qua ca cot so loi.
     */
    private static function kiemKieuTruong(array $duLieu, array &$loi)
    {
        foreach ($duLieu as $the => $giaTri) {
            $giaTri = trim((string) $giaTri);

            if ($giaTri === '') {
                continue;
            }

            $kieu = CtdtQuyTac::kieuCua($the);

            if ($kieu === 'ngay' && !self::ngayHopLe($giaTri)) {
                $loi[] = self::loi('CTDT002', $the,
                    $the . ' = "' . $giaTri . '" không phải ngày hợp lệ'
                    . ' (cần 8, 12 hoặc 14 chữ số và là ngày có thật)');
            } elseif ($kieu === 'gioi_tinh' && !in_array($giaTri, ['1', '2', '3'], true)) {
                $loi[] = self::loi('CTDT003', $the,
                    $the . ' = "' . $giaTri . '" ngoài giá trị cho phép (1 Nam, 2 Nữ, 3 chưa xác định)');
            } elseif ($kieu === 'loai_giayto' && !in_array($giaTri, ['0', '1', '2', '3', '4'], true)) {
                $loi[] = self::loi('CTDT004', $the,
                    $the . ' = "' . $giaTri . '" ngoài giá trị cho phép (0 đến 4)');
            } elseif ($kieu === 'co_khong' && !in_array($giaTri, ['0', '1'], true)) {
                $loi[] = self::loi('CTDT005', $the,
                    $the . ' = "' . $giaTri . '" ngoài giá trị 0/1');
            }
        }
    }

    /**
     * So sanh tren TAM ky tu dau (phan ngay).
     *
     * Hai the trong cung mot cap co the khac do dai: CT06 dung NGAY_VAO/NGAY_RA dang 8 ky
     * tu con CT03 dang 12. So sanh ca chuoi se cho ket qua sai khi do dai lech.
     */
    private static function kiemCapNgay(array $duLieu, array &$loi)
    {
        foreach (CtdtQuyTac::CAP_NGAY as $theDau => $theCuoi) {
            if (self::trong($duLieu, $theDau) || self::trong($duLieu, $theCuoi)) {
                continue;
            }

            $dau   = trim((string) $duLieu[$theDau]);
            $cuoi  = trim((string) $duLieu[$theCuoi]);

            if (!self::ngayHopLe($dau) || !self::ngayHopLe($cuoi)) {
                // Da co CTDT002 cho cai sai dinh dang; so sanh tiep chi sinh them nhieu.
                continue;
            }

            if (substr($cuoi, 0, 8) < substr($dau, 0, 8)) {
                $loi[] = self::loi('CTDT006', $theCuoi,
                    $theCuoi . ' (' . $cuoi . ') sớm hơn ' . $theDau . ' (' . $dau . ')');
            }
        }
    }

    private static function kiemMacskcb(array $duLieu, $macskcbHoSo, array &$loi)
    {
        if (self::trong($duLieu, 'MACSKCB')) {
            return;
        }

        $trongChungTu = trim((string) $duLieu['MACSKCB']);
        $cuaHoSo = trim((string) $macskcbHoSo);

        if ($cuaHoSo !== '' && $trongChungTu !== $cuaHoSo) {
            $loi[] = self::loi('CTDT007', 'MACSKCB',
                'MACSKCB trong chứng từ (' . $trongChungTu . ') khác mã cơ sở của hồ sơ ('
                . $cuaHoSo . ')');
        }
    }

    /**
     * Ngay hop le: 8, 12 hoac 14 CHU SO, va phan ngay phai co that tren lich.
     *
     * KHONG ghim do dai theo tung the: cung mot ten the co do dai khac nhau tuy loai -
     * NGAY_VAO cua CT03 la 12 ky tu con cua CT06 la 8.
     */
    private static function ngayHopLe($giaTri)
    {
        if (!preg_match('/^\d{8}$|^\d{12}$|^\d{14}$/', $giaTri)) {
            return false;
        }

        $nam   = (int) substr($giaTri, 0, 4);
        $thang = (int) substr($giaTri, 4, 2);
        $ngay  = (int) substr($giaTri, 6, 2);

        // checkdate lo luon nam nhuan: 29/02/1995 sai, 29/02/1996 dung.
        return checkdate($thang, $ngay, $nam);
    }

    private static function trong(array $duLieu, $the)
    {
        return !array_key_exists($the, $duLieu) || trim((string) $duLieu[$the]) === '';
    }

    /** Muc do LAY TU danh muc, khong go tay - tranh hai nguon su that. */
    private static function loi($maLoi, $tenTruong, $moTa)
    {
        return [
            'ma_loi'     => $maLoi,
            'ten_truong' => $tenTruong,
            'mo_ta'      => $moTa,
            'muc_do'     => (string) config('ctdt.ma_loi.' . $maLoi . '.muc_do', 'chan'),
        ];
    }
}
```

- [ ] **Step 4: Chạy test để xác nhận xanh**

```bash
php vendor/bin/phpunit tests/Unit/Ctdt/CtdtCheckerTest.php
```

Kỳ vọng: `OK (27 tests)`.

- [ ] **Step 5: Commit**

```bash
git add app/Services/Ctdt/Kiem/CtdtChecker.php tests/Unit/Ctdt/CtdtCheckerTest.php
git commit -m "feat(ctdt): bo kiem noi dung chung tu, ham thuan"
```

---

## Task 4: `CheckCtdtJob` — chạy bộ kiểm và ghi kết quả

**Files:**
- Create: `app/Jobs/CheckCtdtJob.php`
- Test: `tests/Unit/Ctdt/CheckCtdtJobTest.php`

**Interfaces:**
- Consumes: `CtdtChecker::kiem()` (Task 3); `CtdtLoaiRegistry::co()`, `::cho()`, `$lop::truong()`, `$lop::model()` (Giai đoạn 1); model `CtdtHoSo`, `CtdtChungTu`, `CtdtLoi`
- Produces: `App\Jobs\CheckCtdtJob` — `__construct($maHoSo)`, `handle()`. Ghi `ctdt_loi`, cập nhật `ctdt_ho_so.so_loi` (đếm **chỉ** lỗi mức `chan`) và `checked_at`.

- [ ] **Step 1: Viết test đỏ**

Tạo `tests/Unit/Ctdt/CheckCtdtJobTest.php`:

```php
<?php

namespace Tests\Unit\Ctdt;

use Tests\TestCase;
use Tests\Support\DungBangCtdtSqlite;
use App\Jobs\CheckCtdtJob;
use App\Models\BHYT\Ctdt\CtdtHoSo;
use App\Models\BHYT\Ctdt\CtdtChungTu;
use App\Models\BHYT\Ctdt\CtdtLoi;
use App\Models\BHYT\Ctdt\CtdtCt03;

class CheckCtdtJobTest extends TestCase
{
    use DungBangCtdtSqlite;

    protected function setUp()
    {
        parent::setUp();
        $this->chuanBiBangCtdt();
    }

    /**
     * Dung mot ho so CT03 voi cac gia tri truyen vao, tra ban ghi ho so.
     */
    private function hoSoCt03(array $chiTiet, array $ghiDeHoSo = [])
    {
        $hoSo = CtdtHoSo::create(array_merge([
            'ma_ho_so' => 'YT001', 'dich_vu' => 'CT2025', 'loai_hs' => '39',
            'macskcb' => '01929', 'so_chung_tu' => 1,
        ], $ghiDeHoSo));

        $chungTu = CtdtChungTu::create([
            'ho_so_id' => $hoSo->id, 'loai_ho_so' => 'CT03',
            'ma_chung_tu' => 'YT001', 'noi_dung_goc' => '<CT03/>',
        ]);

        CtdtCt03::create(array_merge(['chung_tu_id' => $chungTu->id], $chiTiet));

        return $hoSo->fresh();
    }

    private function chay($maHoSo = 'YT001')
    {
        (new CheckCtdtJob($maHoSo))->handle();
    }

    /** @test */
    public function ho_so_hop_le_khong_sinh_loi_va_so_loi_bang_khong()
    {
        $this->hoSoCt03([
            'ma_yte' => 'YT001', 'ho_ten' => 'Nguyen Van Test', 'ngay_sinh' => '19950914',
            'ngay_vao' => '201912121200', 'ngay_ra' => '201912180001', 'ma_the' => 'DN1',
        ]);

        $this->chay();

        $this->assertSame(0, CtdtLoi::count());
        $this->assertSame(0, (int) CtdtHoSo::first()->so_loi);
        $this->assertNotNull(CtdtHoSo::first()->checked_at);
    }

    /** @test */
    public function ghi_loi_kem_ho_so_id_va_chung_tu_id()
    {
        $this->hoSoCt03(['ma_yte' => 'YT001', 'ho_ten' => '', 'ngay_sinh' => '19950914',
            'ngay_vao' => '201912121200', 'ngay_ra' => '201912180001', 'ma_the' => 'DN1']);

        $this->chay();

        $loi = CtdtLoi::first();

        $this->assertNotNull($loi);
        $this->assertSame('CTDT001', $loi->ma_loi);
        $this->assertSame('HO_TEN', $loi->ten_truong);
        // ho_so_id PHAI co: CtdtLuuHoSo::xoaHoSoCu() xoa ctdt_loi theo ho_so_id. Ban ghi
        // loi khong co ho_so_id se song sot qua lan nap lai.
        $this->assertNotNull($loi->ho_so_id);
        $this->assertNotNull($loi->chung_tu_id);
    }

    /** @test */
    public function so_loi_chi_dem_muc_chan()
    {
        // Thieu ho ten (chan) + thieu ma the (canh bao) + co sai (canh bao) = 3 ban ghi
        // loi nhung so_loi phai la 1. so_loi la con so quyet dinh ho so co duoc gui hay
        // khong; dem ca canh bao vao se chan nham nhung ho so hop le.
        $this->hoSoCt03(['ma_yte' => 'YT001', 'ho_ten' => '', 'ngay_sinh' => '19950914',
            'ngay_vao' => '201912121200', 'ngay_ra' => '201912180001',
            'ma_the' => '', 'tekt' => '7']);

        $this->chay();

        $this->assertSame(3, CtdtLoi::count());
        $this->assertSame(1, (int) CtdtHoSo::first()->so_loi);
        $this->assertSame(2, CtdtLoi::where('muc_do', 'canh_bao')->count());
    }

    /** @test */
    public function chay_lai_khong_nhan_doi_loi()
    {
        // Job phai tu idempotent: hang doi co the giao lai sau khi that bai giua chung.
        $this->hoSoCt03(['ma_yte' => 'YT001', 'ho_ten' => '', 'ngay_sinh' => '19950914',
            'ngay_vao' => '201912121200', 'ngay_ra' => '201912180001', 'ma_the' => 'DN1']);

        $this->chay();
        $this->chay();
        $this->chay();

        $this->assertSame(1, CtdtLoi::count());
        $this->assertSame(1, (int) CtdtHoSo::first()->so_loi);
    }

    /** @test */
    public function sua_du_lieu_roi_chay_lai_thi_loi_cu_bien_mat()
    {
        $hoSo = $this->hoSoCt03(['ma_yte' => 'YT001', 'ho_ten' => '', 'ngay_sinh' => '19950914',
            'ngay_vao' => '201912121200', 'ngay_ra' => '201912180001', 'ma_the' => 'DN1']);

        $this->chay();
        $this->assertSame(1, CtdtLoi::count());

        CtdtCt03::first()->update(['ho_ten' => 'Nguyen Van Test']);
        $this->chay();

        $this->assertSame(0, CtdtLoi::count());
        $this->assertSame(0, (int) CtdtHoSo::first()->so_loi);
    }

    /** @test */
    public function truyen_ma_co_so_cua_ho_so_xuong_bo_kiem()
    {
        // Quy tac CTDT007 doi chieu MACSKCB trong chung tu voi ma co so cua HO SO. Truyen
        // nham gia tri o day thi quy tac do bat nham hoac khong bat gi.
        $hoSo = CtdtHoSo::create([
            'ma_ho_so' => 'GBT-1', 'dich_vu' => 'GBT', 'loai_hs' => '60',
            'macskcb' => '01929', 'so_chung_tu' => 1,
        ]);

        $chungTu = CtdtChungTu::create([
            'ho_so_id' => $hoSo->id, 'loai_ho_so' => 'GIAYBAOTU',
            'ma_chung_tu' => 'GBT-1', 'noi_dung_goc' => '<GIAYBAOTU/>',
        ]);

        \App\Models\BHYT\Ctdt\CtdtGiayBaoTu::create([
            'chung_tu_id' => $chungTu->id, 'ma_gbt' => 'GBT-1',
            'ho_ten' => 'Nguyen Van Test', 'ngay_sinh' => '20220101',
            'ngay_tv' => '202510070200', 'ma_the' => 'DN1',
            'macskcb' => '37470',
        ]);

        (new CheckCtdtJob('GBT-1'))->handle();

        $this->assertSame(1, CtdtLoi::where('ma_loi', 'CTDT007')->count());
    }

    /** @test */
    public function ho_so_khong_ton_tai_thi_khong_nem()
    {
        // Job co the nam cho trong hang doi rat lau; giua luc do ho so co the da bi xoa.
        // Nem o day chi lam job that bai va thu lai ba lan cho cung mot ket qua.
        $this->chay('KHONG_TON_TAI');

        $this->assertSame(0, CtdtLoi::count());
    }

    /** @test */
    public function chung_tu_khong_co_ban_ghi_chi_tiet_thi_bo_qua()
    {
        $hoSo = CtdtHoSo::create([
            'ma_ho_so' => 'YT001', 'dich_vu' => 'CT2025', 'loai_hs' => '39',
            'macskcb' => '01929', 'so_chung_tu' => 1,
        ]);

        CtdtChungTu::create([
            'ho_so_id' => $hoSo->id, 'loai_ho_so' => 'CT03',
            'ma_chung_tu' => 'YT001', 'noi_dung_goc' => '<CT03/>',
        ]);

        $this->chay();

        $this->assertSame(0, CtdtLoi::count());
        $this->assertNotNull(CtdtHoSo::first()->checked_at);
    }

    /** @test */
    public function loai_la_trong_CSDL_thi_bo_qua_khong_nem()
    {
        // Registry co the bi thu hep sau khi du lieu da duoc ghi. Nem o day thi mot loai
        // da go se lam moi lan kiem cua moi ho so cu deu that bai.
        $hoSo = CtdtHoSo::create([
            'ma_ho_so' => 'YT001', 'dich_vu' => 'CT2025', 'loai_hs' => '39',
            'macskcb' => '01929', 'so_chung_tu' => 1,
        ]);

        CtdtChungTu::create([
            'ho_so_id' => $hoSo->id, 'loai_ho_so' => 'LOAI_DA_GO',
            'noi_dung_goc' => '<LOAI_DA_GO/>',
        ]);

        $this->chay();

        $this->assertSame(0, CtdtLoi::count());
        $this->assertNotNull(CtdtHoSo::first()->checked_at);
    }
}
```

- [ ] **Step 2: Chạy test để xác nhận đỏ**

```bash
php vendor/bin/phpunit tests/Unit/Ctdt/CheckCtdtJobTest.php
```

Kỳ vọng: đỏ — `Class 'App\Jobs\CheckCtdtJob' not found`.

- [ ] **Step 3: Viết `CheckCtdtJob`**

Tạo `app/Jobs/CheckCtdtJob.php`:

```php
<?php

namespace App\Jobs;

use DB;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

use App\Models\BHYT\Ctdt\CtdtHoSo;
use App\Models\BHYT\Ctdt\CtdtLoi;
use App\Services\Ctdt\CtdtLoaiRegistry;
use App\Services\Ctdt\Kiem\CtdtChecker;

/**
 * Kiem noi dung MOT ho so chung tu dien tu.
 *
 * Nhan MA HO SO chu khong nhan model: job co the nam cho trong hang doi rat lau, va mot
 * model serialize san se mang theo du lieu da cu.
 *
 * TU IDEMPOTENT: xoa het loi cu cua ho so roi ghi lai tu dau, nen chay bao nhieu lan cung
 * ra mot ket qua - hang doi giao lai sau khi that bai giua chung khong lam nhan doi loi.
 */
class CheckCtdtJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** @var int */
    public $tries = 3;

    /** @var int */
    public $timeout = 120;

    /** @var string */
    protected $maHoSo;

    public function __construct($maHoSo)
    {
        $this->maHoSo = $maHoSo;
    }

    public function handle()
    {
        $hoSo = CtdtHoSo::with('chungTu')->where('ma_ho_so', $this->maHoSo)->first();

        if ($hoSo === null) {
            // Ho so co the da bi xoa trong luc job cho trong hang doi. Nem o day chi lam
            // job that bai va thu lai ba lan cho cung mot ket qua.
            \Log::info('CheckCtdtJob: khong tim thay ho so ' . $this->maHoSo);

            return;
        }

        $loi = [];

        foreach ($hoSo->chungTu as $chungTu) {
            foreach ($this->kiemMotChungTu($hoSo, $chungTu) as $mot) {
                $mot['ho_so_id'] = $hoSo->id;
                $mot['chung_tu_id'] = $chungTu->id;
                $loi[] = $mot;
            }
        }

        $soChan = 0;

        foreach ($loi as $mot) {
            if ($mot['muc_do'] === 'chan') {
                $soChan++;
            }
        }

        // MOT transaction cho ca ba viec. so_loi, cac ban ghi ctdt_loi va checked_at la ba
        // cach dien dat cung mot ket qua kiem; ghi roi ra thi mot lan hong giua chung se
        // de man danh sach, bo loc "chi ho so con loi" va tab Loi noi ba dieu khac nhau.
        DB::transaction(function () use ($hoSo, $loi, $soChan) {
            CtdtLoi::where('ho_so_id', $hoSo->id)->delete();

            foreach ($loi as $mot) {
                CtdtLoi::create($mot);
            }

            $hoSo->update([
                'so_loi'     => $soChan,
                'checked_at' => now(),
            ]);
        });
    }

    /**
     * @return array Cac loi cua mot chung tu, chua gan ho_so_id/chung_tu_id
     */
    private function kiemMotChungTu(CtdtHoSo $hoSo, $chungTu)
    {
        if (!CtdtLoaiRegistry::co($chungTu->loai_ho_so)) {
            // Registry co the bi thu hep sau khi du lieu da duoc ghi. Bo qua thay vi nem:
            // nem thi mot loai da go se lam moi lan kiem cua moi ho so cu deu that bai.
            \Log::warning('CheckCtdtJob: loai la trong CSDL - ' . $chungTu->loai_ho_so);

            return [];
        }

        $lop = CtdtLoaiRegistry::cho($chungTu->loai_ho_so);
        $tenModel = $lop::model();
        $chiTiet = $tenModel::where('chung_tu_id', $chungTu->id)->first();

        if ($chiTiet === null) {
            return [];
        }

        // Dung lai mang TEN THE => gia tri de bo kiem khong phai biet ten cot.
        $duLieu = [];

        foreach ($lop::truong() as $the => $cot) {
            $duLieu[$the] = $chiTiet->{$cot};
        }

        return CtdtChecker::kiem($chungTu->loai_ho_so, $duLieu, $hoSo->macskcb);
    }
}
```

- [ ] **Step 4: Chạy test để xác nhận xanh**

```bash
php vendor/bin/phpunit tests/Unit/Ctdt/CheckCtdtJobTest.php
```

Kỳ vọng: `OK (9 tests)`.

- [ ] **Step 5: Commit**

```bash
git add app/Jobs/CheckCtdtJob.php tests/Unit/Ctdt/CheckCtdtJobTest.php
git commit -m "feat(ctdt): CheckCtdtJob - chay bo kiem va ghi ket qua trong mot transaction"
```

---

## Task 5: Nạp xong thì tự kiểm

**Files:**
- Modify: `app/Services/Ctdt/CtdtImporter.php`
- Modify: `tests/Unit/Ctdt/CtdtImporterTest.php` (thêm `Queue::fake()` và một test mới)
- Modify: `tests/Unit/Ctdt/CtdtNapToanLuongTest.php` (thêm `Queue::fake()`)
- Modify: `tests/Unit/Ctdt/CtdtNhapTuTepTest.php` (thêm `Queue::fake()`)
- Modify: `tests/Unit/Ctdt/CtdtUploadTest.php` (thêm `Queue::fake()`)

**Interfaces:**
- Consumes: `App\Jobs\CheckCtdtJob` (Task 4)
- Produces: `CtdtImporter` dispatch `CheckCtdtJob` cho từng hồ sơ nạp thành công, **sau commit**, lên hàng đợi `config('organization.chung_tu_dien_tu.queue_name')`

**Vì sao `Queue::fake()` trong test cũ:** `phpunit.xml` đặt `QUEUE_DRIVER=sync`, nên không fake thì
mọi test nạp sẽ chạy luôn bộ kiểm — chậm hơn, và các test đó bỗng phụ thuộc vào bảng quy tắc của
Task 1–3. Test nạp phải kiểm việc nạp, không phải việc kiểm.

- [ ] **Step 1: Viết test đỏ**

Thêm vào `tests/Unit/Ctdt/CtdtImporterTest.php` — thêm `use Illuminate\Support\Facades\Queue;` ở đầu tệp, thêm `Queue::fake();` vào cuối `setUp()`, và thêm hai test:

```php
    /** @test */
    public function nap_xong_thi_day_job_kiem_loi()
    {
        $this->importer->nhapTuChuoi($this->goiCt2025([[
            $this->chungTu('CT03', ['MA_YTE' => 'YT001']),
        ]]));

        Queue::assertPushed(\App\Jobs\CheckCtdtJob::class);
    }

    /** @test */
    public function moi_ho_so_mot_job_rieng()
    {
        $this->importer->nhapTuChuoi($this->goiCt2025([
            [$this->chungTu('CT03', ['MA_YTE' => 'YT001'])],
            [$this->chungTu('CT03', ['MA_YTE' => 'YT002'])],
        ]));

        Queue::assertPushed(\App\Jobs\CheckCtdtJob::class, 2);
    }

    /** @test */
    public function ho_so_hong_KHONG_day_job_kiem()
    {
        // Ho so hong khong co gi de kiem, va job se chi tim thay mot ma ho so khong ton tai.
        $xml = '<?xml version="1.0" encoding="utf-8"?><HSCHUNGTU>'
            . '<THONGTINDONVI><MACSKCB>01929</MACSKCB></THONGTINDONVI>'
            . '<THONGTINHOSO Id="Id-abc"><SOLUONGHOSO>1</SOLUONGHOSO><DANHSACHHOSO><HOSO>'
            . '<FILEHOSO><LOAIHOSO>CT03</LOAIHOSO><NOIDUNGFILE>'
            . base64_encode('<CT04><MA_YTE>YT001</MA_YTE></CT04>') . '</NOIDUNGFILE></FILEHOSO>'
            . '</HOSO></DANHSACHHOSO></THONGTINHOSO></HSCHUNGTU>';

        $this->importer->nhapTuChuoi($xml);

        Queue::assertNotPushed(\App\Jobs\CheckCtdtJob::class);
    }
```

Thêm `use Illuminate\Support\Facades\Queue;` và `Queue::fake();` trong `setUp()` của cả ba tệp còn lại: `CtdtNapToanLuongTest.php`, `CtdtNhapTuTepTest.php`, `CtdtUploadTest.php`.

- [ ] **Step 2: Chạy test để xác nhận đỏ**

```bash
php vendor/bin/phpunit tests/Unit/Ctdt/CtdtImporterTest.php
```

Kỳ vọng: đỏ — `The expected [App\Jobs\CheckCtdtJob] job was not pushed.`

- [ ] **Step 3: Dispatch job trong `CtdtImporter`**

Thêm `use App\Jobs\CheckCtdtJob;` ở đầu tệp.

Trong `nhapMotHoSo()`, ngay **sau** khối `DB::transaction(...)` và **trước** dòng `return CtdtImportResult::thanhCong(...)`:

```php
            // SAU COMMIT, khong phai trong transaction: job dat trong transaction se tro
            // toi du lieu chua ton tai neu rollback. Ghi chu nay da co trong Xml3176Importer
            // va van dung nguyen o day.
            //
            // Moi ho so MOT job rieng: mot ho so hong khong lam mat ket qua kiem cua cac
            // ho so con lai trong cung mot tep.
            CheckCtdtJob::dispatch($maHoSo)
                ->onQueue(config('organization.chung_tu_dien_tu.queue_name', 'JobCtdt'));
```

- [ ] **Step 4: Chạy test để xác nhận xanh**

```bash
php vendor/bin/phpunit tests/Unit/Ctdt
```

Kỳ vọng: xanh.

- [ ] **Step 5: Commit**

```bash
git add app/Services/Ctdt/CtdtImporter.php tests/Unit/Ctdt
git commit -m "feat(ctdt): nap xong tu day job kiem loi, moi ho so mot job"
```

---

## Task 6: Tab Lỗi trên màn chi tiết

**Files:**
- Modify: `app/Services/Ctdt/CtdtDetailTabs.php`
- Modify: `app/Http/Controllers/BHYT/BHYTCtdtController.php` (nhánh tab Lỗi trong `detailTab()`)
- Create: `resources/views/bhyt/ctdt/tab-loi.blade.php`
- Test: `tests/Unit/Ctdt/CtdtTabLoiTest.php`

**Interfaces:**
- Consumes: `CtdtDetailTabs::cua()`, `::hopLe()` (Giai đoạn 2B); model `CtdtLoi`; `CtdtNhanTruong::cua()` (Giai đoạn 2B); `config('ctdt.ma_loi')` (Task 1)
- Produces: hằng `CtdtDetailTabs::TAB_LOI = '__LOI__'`; tab Lỗi đứng **trước** tab XML gốc; `detailTab()` trả view `bhyt.ctdt.tab-loi` với biến `$loi` (Collection `CtdtLoi`, đã nạp kèm `chungTu`)

- [ ] **Step 1: Viết test đỏ**

Tạo `tests/Unit/Ctdt/CtdtTabLoiTest.php`:

```php
<?php

namespace Tests\Unit\Ctdt;

use Tests\TestCase;
use Tests\Support\DungBangCtdtSqlite;
use App\Http\Controllers\BHYT\BHYTCtdtController;
use App\Services\Ctdt\CtdtDetailTabs;
use App\Models\BHYT\Ctdt\CtdtHoSo;
use App\Models\BHYT\Ctdt\CtdtChungTu;
use App\Models\BHYT\Ctdt\CtdtLoi;

class CtdtTabLoiTest extends TestCase
{
    use DungBangCtdtSqlite;

    /** @var BHYTCtdtController */
    private $controller;

    protected function setUp()
    {
        parent::setUp();
        $this->chuanBiBangCtdt();
        $this->controller = new BHYTCtdtController();
    }

    private function hoSoCoLoi(array $cacLoi = [])
    {
        // so_loi chi dem muc CHAN, dung nhu CheckCtdtJob lam. Dat bang count($cacLoi)
        // se lam khoi tom tat tren tab hien so canh bao am.
        $soChan = 0;

        foreach ($cacLoi as $mot) {
            if (!isset($mot['muc_do']) || $mot['muc_do'] === 'chan') {
                $soChan++;
            }
        }

        $hoSo = CtdtHoSo::create([
            'ma_ho_so' => 'YT001', 'dich_vu' => 'CT2025', 'loai_hs' => '39',
            'macskcb' => '01929', 'so_chung_tu' => 1, 'so_loi' => $soChan,
        ]);

        $chungTu = CtdtChungTu::create([
            'ho_so_id' => $hoSo->id, 'loai_ho_so' => 'CT03',
            'ma_chung_tu' => 'YT001', 'noi_dung_goc' => '<CT03/>',
        ]);

        foreach ($cacLoi as $mot) {
            CtdtLoi::create(array_merge([
                'ho_so_id' => $hoSo->id, 'chung_tu_id' => $chungTu->id,
                'ma_loi' => 'CTDT001', 'ten_truong' => 'HO_TEN',
                'mo_ta' => 'Thiếu trường bắt buộc HO_TEN', 'muc_do' => 'chan',
            ], $mot));
        }

        return $hoSo->fresh();
    }

    /** @test */
    public function tab_loi_luon_co_ke_ca_khi_khong_co_loi()
    {
        // Hien tab rong de nguoi dung XAC NHAN duoc "ho so nay khong co loi". An tab di
        // thi khong phan biet duoc "khong loi" voi "chua kiem".
        $tabs = CtdtDetailTabs::cua($this->hoSoCoLoi());

        $this->assertContains(CtdtDetailTabs::TAB_LOI, array_column($tabs, 'ma'));
    }

    /** @test */
    public function tab_loi_dung_TRUOC_tab_xml_goc()
    {
        $ma = array_column(CtdtDetailTabs::cua($this->hoSoCoLoi()), 'ma');

        $viTriLoi = array_search(CtdtDetailTabs::TAB_LOI, $ma);
        $viTriXml = array_search(CtdtDetailTabs::TAB_XML, $ma);

        $this->assertNotFalse($viTriLoi);
        $this->assertNotFalse($viTriXml);
        $this->assertLessThan($viTriXml, $viTriLoi, 'Tab Loi phai dung truoc tab XML goc');
    }

    /** @test */
    public function so_luong_tren_tab_loi_dem_ca_canh_bao()
    {
        // Nhan tren tab la "co bao nhieu dong trong tab nay", khac voi so_loi (chi dem muc
        // chan). Hai con so khac nhau la dung, mien la moi con so noi dung viec cua no.
        $tabs = CtdtDetailTabs::cua($this->hoSoCoLoi([
            ['muc_do' => 'chan'],
            ['muc_do' => 'canh_bao', 'ma_loi' => 'CTDT008'],
        ]));

        foreach ($tabs as $tab) {
            if ($tab['ma'] === CtdtDetailTabs::TAB_LOI) {
                $this->assertSame(2, $tab['so_luong']);

                return;
            }
        }

        $this->fail('Khong tim thay tab Loi');
    }

    /** @test */
    public function hop_le_chap_nhan_tab_loi()
    {
        $this->assertTrue(CtdtDetailTabs::hopLe($this->hoSoCoLoi(), CtdtDetailTabs::TAB_LOI));
    }

    /** @test */
    public function controller_tra_view_tab_loi_kem_danh_sach_loi()
    {
        $this->hoSoCoLoi([
            ['muc_do' => 'chan'],
            ['muc_do' => 'canh_bao', 'ma_loi' => 'CTDT008', 'ten_truong' => 'MA_THE',
             'mo_ta' => 'Thiếu MA_THE'],
        ]);

        $view = $this->controller->detailTab('YT001', CtdtDetailTabs::TAB_LOI);
        $duLieu = $view->getData();

        $this->assertSame('bhyt.ctdt.tab-loi', $view->getName());
        $this->assertCount(2, $duLieu['loi']);
    }

    /** @test */
    public function loi_muc_chan_hien_truoc_loi_canh_bao()
    {
        // Nguoi doc can thay ngay thu chan minh gui, khong phai loc bang mat qua mot danh
        // sach tron lan.
        $this->hoSoCoLoi([
            ['muc_do' => 'canh_bao', 'ma_loi' => 'CTDT008'],
            ['muc_do' => 'chan', 'ma_loi' => 'CTDT001'],
        ]);

        $loi = $this->controller->detailTab('YT001', CtdtDetailTabs::TAB_LOI)->getData()['loi'];

        $this->assertSame('chan', $loi->first()->muc_do);
    }

    /** @test */
    public function tab_loi_render_duoc_va_thoat_noi_dung()
    {
        // mo_ta chua gia tri trich tu the XML ben ngoai. Mot mo_ta dang
        // '<img src=x onerror=...>' phai hien ra thanh chu, khong duoc chay.
        $this->hoSoCoLoi([
            ['mo_ta' => 'Sai dinh dang <img src=x onerror=alert(1)>'],
        ]);

        $html = $this->controller->detailTab('YT001', CtdtDetailTabs::TAB_LOI)->render();

        $this->assertNotContains('<img src=x', $html, 'Noi dung phai duoc thoat');
        $this->assertContains('&lt;img', $html);
    }

    /** @test */
    public function ho_so_chua_kiem_bao_ro_la_chua_kiem()
    {
        // 'Chua kiem' va 'khong co loi' la hai chuyen khac nhau. Hien giong nhau se lam
        // nguoi dung tuong ho so da qua kiem trong khi job con nam trong hang doi.
        $this->hoSoCoLoi();

        $html = $this->controller->detailTab('YT001', CtdtDetailTabs::TAB_LOI)->render();

        $this->assertContains('chưa được kiểm', $html);
    }

    /** @test */
    public function ho_so_da_kiem_va_khong_loi_bao_khac_voi_chua_kiem()
    {
        $hoSo = $this->hoSoCoLoi();
        $hoSo->update(['checked_at' => '2026-08-20 08:00:00']);

        $html = $this->controller->detailTab('YT001', CtdtDetailTabs::TAB_LOI)->render();

        $this->assertNotContains('chưa được kiểm', $html);
        $this->assertContains('Không có lỗi', $html);
    }
}
```

- [ ] **Step 2: Chạy test để xác nhận đỏ**

```bash
php vendor/bin/phpunit tests/Unit/Ctdt/CtdtTabLoiTest.php
```

Kỳ vọng: đỏ — `Undefined constant ... TAB_LOI`.

- [ ] **Step 3: Thêm tab Lỗi vào `CtdtDetailTabs`**

Trong `app/Services/Ctdt/CtdtDetailTabs.php`, thêm `use App\Models\BHYT\Ctdt\CtdtLoi;` ở đầu tệp và hằng mới cạnh `TAB_XML`:

```php
    /** Tab liet ke loi kiem, luon dung TRUOC tab XML goc. */
    const TAB_LOI = '__LOI__';
```

Trong `cua()`, **trước** dòng thêm tab `TAB_XML`, chèn:

```php
        // Tab Loi LUON co, ke ca khi khong co loi: hien tab rong de nguoi dung xac nhan
        // duoc "ho so nay khong co loi". An tab di thi khong phan biet duoc "khong loi"
        // voi "chua kiem".
        $tabs[] = [
            'ma'       => self::TAB_LOI,
            'nhan'     => 'Lỗi',
            'so_luong' => CtdtLoi::where('ho_so_id', $hoSo->id)->count(),
        ];
```

- [ ] **Step 4: Thêm nhánh tab Lỗi vào controller**

Trong `app/Http/Controllers/BHYT/BHYTCtdtController.php`, thêm `use App\Models\BHYT\Ctdt\CtdtLoi;` ở đầu tệp, rồi trong `detailTab()` — **trước** nhánh `TAB_XML` và trước lời gọi `CtdtLoaiRegistry::co()`:

```php
        if ($loai === CtdtDetailTabs::TAB_LOI) {
            // Loi muc chan hien TRUOC: nguoi doc can thay ngay thu dang chan minh gui,
            // khong phai loc bang mat qua mot danh sach tron lan.
            $loi = CtdtLoi::with('chungTu')
                ->where('ho_so_id', $hoSo->id)
                ->orderByRaw("CASE WHEN muc_do = 'chan' THEN 0 ELSE 1 END")
                ->orderBy('id')
                ->get();

            return view('bhyt.ctdt.tab-loi', [
                'hoSo' => $hoSo,
                'loi'  => $loi,
            ]);
        }
```

- [ ] **Step 5: Viết blade tab Lỗi**

Tạo `resources/views/bhyt/ctdt/tab-loi.blade.php`:

```blade
{{-- Danh sach loi kiem cua mot ho so.

     Bien vao:
       $hoSo — ban ghi CtdtHoSo (dung checked_at de phan biet "chua kiem" voi "khong loi")
       $loi  — Collection CtdtLoi, da sap loi muc chan len truoc

     mo_ta chua gia tri trich tu the XML ben ngoai nen moi cho hien deu dung {{ }}. --}}
@if ($loi->isEmpty())
    @if (empty($hoSo->checked_at))
        <p class="text-muted">
            Hồ sơ <strong>chưa được kiểm</strong> — công việc kiểm còn nằm trong hàng đợi.
        </p>
    @else
        <p class="text-success">
            <i class="fa fa-check"></i>
            Không có lỗi. Kiểm lúc {{ $hoSo->checked_at }}.
        </p>
    @endif
@else
    <p class="text-muted">
        Kiểm lúc {{ $hoSo->checked_at }} —
        <strong>{{ $hoSo->so_loi }}</strong> lỗi chặn gửi,
        {{ $loi->count() - $hoSo->so_loi }} cảnh báo.
    </p>
    <table class="table table-condensed table-bordered">
        <thead>
            <tr>
                <th style="width:10%">Mức</th>
                <th style="width:10%">Mã lỗi</th>
                <th style="width:15%">Chứng từ</th>
                <th style="width:15%">Trường</th>
                <th>Mô tả</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($loi as $mot)
            <tr>
                <td>
                    @if ($mot->muc_do === 'chan')
                        <span class="label label-danger">Chặn gửi</span>
                    @else
                        <span class="label label-warning">Cảnh báo</span>
                    @endif
                </td>
                <td>{{ $mot->ma_loi }}</td>
                <td>{{ $mot->chungTu ? $mot->chungTu->loai_ho_so : '—' }}</td>
                <td>{{ $mot->ten_truong ?: '—' }}</td>
                <td>{{ $mot->mo_ta }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
@endif
```

- [ ] **Step 6: Chạy test để xác nhận xanh**

```bash
php vendor/bin/phpunit tests/Unit/Ctdt/CtdtTabLoiTest.php
```

Kỳ vọng: `OK (9 tests)`.

- [ ] **Step 7: Commit**

```bash
git add app/Services/Ctdt/CtdtDetailTabs.php app/Http/Controllers/BHYT/BHYTCtdtController.php resources/views/bhyt/ctdt/tab-loi.blade.php tests/Unit/Ctdt/CtdtTabLoiTest.php
git commit -m "feat(ctdt): tab Loi tren man chi tiet"
```

---

## Task 7: Worker hàng đợi và lưới an toàn toàn luồng

**Files:**
- Modify: `install_service.bat`
- Modify: `docs/chung-tu-dien-tu-pl02.md`
- Test: `tests/Unit/Ctdt/CtdtKiemToanLuongTest.php`

**Interfaces:**
- Consumes: mọi thứ của Task 1–6
- Produces: dịch vụ nssm `QLBV JobCtdt`; không có mã sản phẩm mới

**Vì sao worker nằm ở task này:** cho tới trước Task 5, hàng đợi `JobCtdt` chưa ai đẩy vào nên dựng
worker chỉ tạo một tiến trình chạy không. Từ Task 5 trở đi thì **không có worker nghĩa là không hồ
sơ nào được kiểm** — cột Số lỗi sẽ vĩnh viễn bằng 0 và trông y như mọi hồ sơ đều sạch.

- [ ] **Step 1: Viết test lưới an toàn**

Tạo `tests/Unit/Ctdt/CtdtKiemToanLuongTest.php`:

```php
<?php

namespace Tests\Unit\Ctdt;

use Tests\TestCase;
use Tests\Support\DungBangCtdtSqlite;
use Tests\Support\GoiCtdtMau;
use App\Jobs\CheckCtdtJob;
use App\Services\Ctdt\CtdtImporter;
use App\Services\Ctdt\CtdtDanhSach;
use App\Services\Ctdt\CtdtTrangThaiGui;
use App\Models\BHYT\Ctdt\CtdtHoSo;
use App\Models\BHYT\Ctdt\CtdtLoi;

/**
 * Luoi an toan cho ca Giai doan 3: nap that -> kiem that -> con so hien dung o ca man
 * danh sach lan bo loc.
 *
 * KHONG dung Queue::fake() o day: day chinh la cho phai chay job THAT.
 */
class CtdtKiemToanLuongTest extends TestCase
{
    use DungBangCtdtSqlite;
    use GoiCtdtMau;

    /** @var CtdtImporter */
    private $importer;

    protected function setUp()
    {
        parent::setUp();
        $this->chuanBiBangCtdt();
        $this->importer = new CtdtImporter();
        config(['organization.BHYT.ma_cskcb' => '01013']);
        config(['organization.chung_tu_dien_tu.submit_enabled' => true]);
    }

    private function napVaKiem($xml)
    {
        $kq = $this->importer->nhapTuChuoi($xml, ['macskcb' => '01929']);

        foreach ($kq->dsMaHoSo as $maHoSo) {
            (new CheckCtdtJob($maHoSo))->handle();
        }

        return $kq;
    }

    /** @test */
    public function ho_so_du_truong_thi_khong_co_loi_chan()
    {
        $this->napVaKiem($this->goiCt2025([[
            $this->chungTu('CT03', [
                'MA_YTE' => 'YT001', 'HO_TEN' => 'Nguyen Van Test',
                'NGAY_SINH' => '19950914', 'NGAY_VAO' => '201912121200',
                'NGAY_RA' => '201912180001', 'MA_THE' => 'DN1234567890',
            ]),
        ]]));

        $this->assertSame(0, (int) CtdtHoSo::first()->so_loi);
        $this->assertNotNull(CtdtHoSo::first()->checked_at);
    }

    /** @test */
    public function ho_so_thieu_truong_thi_so_loi_len_va_bo_loc_bat_duoc()
    {
        $this->napVaKiem($this->goiCt2025([[
            $this->chungTu('CT03', ['MA_YTE' => 'YT001', 'MA_THE' => 'DN1']),
        ]]));

        $hoSo = CtdtHoSo::first();

        $this->assertGreaterThan(0, (int) $hoSo->so_loi);
        $this->assertSame(1, CtdtDanhSach::truyVan(['chi_con_loi' => true])->count());
    }

    /** @test */
    public function trang_thai_gui_thanh_CON_LOI_khi_bo_kiem_bat_duoc_loi()
    {
        // Day la ly do ca Giai doan 3 ton tai: mot ho so con loi khong duoc di tiep sang
        // duong ky va gui.
        $this->napVaKiem($this->goiCt2025([[
            $this->chungTu('CT03', ['MA_YTE' => 'YT001']),
        ]]));

        $hoSo = CtdtHoSo::first();
        $hoSo->update(['is_signed' => true]);

        $this->assertSame(CtdtTrangThaiGui::CON_LOI, CtdtTrangThaiGui::cua($hoSo->fresh()));
    }

    /** @test */
    public function nap_lai_ban_da_sua_thi_loi_cu_bien_mat()
    {
        // Nap lai xoa sach ctdt_loi va reset so_loi (CtdtLuuHoSo::xoaHoSoCu). Neu bo kiem
        // khong chay lai sau moi lan nap thi ho so hong vua nap lai se hien "0 loi" -
        // trong y het da duoc sua.
        $this->napVaKiem($this->goiCt2025([[
            $this->chungTu('CT03', ['MA_YTE' => 'YT001']),
        ]]));

        $this->assertGreaterThan(0, (int) CtdtHoSo::first()->so_loi);

        $this->napVaKiem($this->goiCt2025([[
            $this->chungTu('CT03', [
                'MA_YTE' => 'YT001', 'HO_TEN' => 'Nguyen Van Test',
                'NGAY_SINH' => '19950914', 'NGAY_VAO' => '201912121200',
                'NGAY_RA' => '201912180001', 'MA_THE' => 'DN1',
            ]),
        ]]));

        $this->assertSame(0, (int) CtdtHoSo::first()->so_loi);
        $this->assertSame(0, CtdtLoi::count());
    }

    /** @test */
    public function moi_ban_ghi_loi_deu_co_ho_so_id()
    {
        // Bat bien nay giu cho nap lai don sach duoc: CtdtLuuHoSo::xoaHoSoCu() xoa ctdt_loi
        // theo ho_so_id. Mot ban ghi loi khong co ho_so_id se song sot mai mai.
        $this->napVaKiem($this->goiCt2025([[
            $this->chungTu('CT03', ['MA_YTE' => 'YT001']),
        ]]));

        $this->assertGreaterThan(0, CtdtLoi::count());
        $this->assertSame(0, CtdtLoi::whereNull('ho_so_id')->count());
    }

    /** @test */
    public function ba_dich_vu_deu_kiem_duoc()
    {
        $this->napVaKiem($this->goiCt2025([[$this->chungTu('CT03', ['MA_YTE' => 'YT001'])]]));
        $this->napVaKiem($this->goiGbt(['MA_GBT' => 'GBT-1']));
        $this->napVaKiem($this->goiGcs(['MA_GCS' => 'GCS-1']));

        $this->assertSame(3, CtdtHoSo::count());

        foreach (CtdtHoSo::all() as $hoSo) {
            $this->assertNotNull($hoSo->checked_at, $hoSo->ma_ho_so . ' chua duoc kiem');
        }
    }

    /** @test */
    public function bay_loai_TT25_deu_chay_qua_bo_kiem_khong_nem()
    {
        // Mot loai lam bo kiem nem se lam ca job do, va moi ho so chua loai do khong bao
        // gio co ket qua kiem.
        foreach (\App\Services\Ctdt\CtdtLoaiRegistry::cuaDichVu('CT2025') as $loai => $lop) {
            $truong = $lop::truong();
            $bo = [];

            foreach (['MA_YTE', 'HO_TEN', 'HOTEN_NND'] as $the) {
                if (array_key_exists($the, $truong)) {
                    $bo[$the] = 'GT-' . $the;
                }
            }

            $kq = $this->napVaKiem($this->goiCt2025([[$this->chungTu($loai, $bo)]]));

            $this->assertTrue($kq->thanhCong, $loai . ': ' . (string) $kq->lyDoThatBai);

            foreach (CtdtHoSo::all() as $hoSo) {
                $this->assertNotNull($hoSo->checked_at, $loai . ': chua duoc kiem');
            }

            CtdtLoi::query()->delete();
            \App\Models\BHYT\Ctdt\CtdtChungTu::query()->delete();
            CtdtHoSo::query()->delete();
            $lop::model()::query()->delete();
        }
    }
}
```

- [ ] **Step 2: Chạy test**

```bash
php vendor/bin/phpunit tests/Unit/Ctdt/CtdtKiemToanLuongTest.php
```

Kỳ vọng: `OK (7 tests)`. Nếu đỏ, thông điệp sẽ nêu đúng loại nào hỏng — sửa nơi lệch thật, đừng nới test.

**Lưu ý cú pháp:** `$lop::model()::query()` cần PHP 8. Trên PHP 7.4 viết hai dòng:

```php
            $tenModel = $lop::model();
            $tenModel::query()->delete();
```

- [ ] **Step 3: Thêm dịch vụ worker vào `install_service.bat`**

Chèn sau khối `QLBV JobSubmitXml3176`:

```bat
:: Tạo dịch vụ cho JobCtdt (kiểm lỗi chứng từ điện tử)
%NSSM_PATH%\nssm install "QLBV JobCtdt" %PHP_PATH% "%LARAVEL_PATH%artisan queue:work --queue=JobCtdt"
%NSSM_PATH%\nssm set "QLBV JobCtdt" AppDirectory %LARAVEL_PATH%
```

Và thêm vào khối khởi động ở cuối tệp, sau dòng `nssm start "QLBV JobSubmitXml3176"`:

```bat
%NSSM_PATH%\nssm start "QLBV JobCtdt"
```

- [ ] **Step 4: Cập nhật tài liệu vận hành**

Trong `docs/chung-tu-dien-tu-pl02.md`, mục 7 ("Triển khai"), thêm vào cuối phần "Hiện tại" một khối:

```markdown
### Worker hàng đợi — BẮT BUỘC từ Giai đoạn 3

Bộ kiểm lỗi chạy trong hàng đợi `JobCtdt`. **Không có worker nghĩa là không hồ sơ nào được kiểm**,
và cột "Số lỗi" sẽ vĩnh viễn bằng `0` — trông y như mọi hồ sơ đều sạch.

Cài dịch vụ (đã có sẵn trong `install_service.bat`):

```bat
nssm install "QLBV JobCtdt" php.exe "<đường dẫn dự án>artisan queue:work --queue=JobCtdt"
nssm start "QLBV JobCtdt"
```

Kiểm hàng đợi có đang chạy không:

```sql
SELECT COUNT(*) FROM jobs WHERE queue = 'JobCtdt';
```

Con số này tăng dần mà không giảm nghĩa là worker chưa chạy.
```

- [ ] **Step 5: Chạy toàn bộ test của module**

```bash
php vendor/bin/phpunit tests/Unit/Ctdt
```

Kỳ vọng: xanh. Số test cộng dồn của Giai đoạn 3 là 11 + 7 + 27 + 9 + 3 + 9 + 7 = 73, cộng 239 của các giai đoạn trước → khoảng 312. Con số là chỉ dấu, không phải điều kiện.

- [ ] **Step 6: Chạy hai suite, đối chiếu baseline**

```bash
php vendor/bin/phpunit --testsuite Unit
```

Kỳ vọng: `Errors: 4, Failures: 7` — **đúng bằng baseline**.

```bash
php vendor/bin/phpunit --testsuite Feature
```

Kỳ vọng: `Errors: 8, Failures: 4` — **đúng bằng baseline**.

- [ ] **Step 7: Commit**

```bash
git add install_service.bat docs/chung-tu-dien-tu-pl02.md tests/Unit/Ctdt/CtdtKiemToanLuongTest.php
git commit -m "feat(ctdt): worker hang doi JobCtdt va luoi an toan toan luong kiem"
```

---

## Hoàn tất Giai đoạn 3

Hồ sơ nạp vào được kiểm tự động; cột "Số lỗi" trên màn danh sách có nghĩa thật; bộ lọc "chỉ hồ sơ
còn lỗi" hoạt động; tab Lỗi trên màn chi tiết nêu đích danh trường nào sai và sai thế nào; và
`CtdtTrangThaiGui::cua()` trả `CON_LOI` cho hồ sơ chưa đủ điều kiện — tức đường ký và gửi ở Giai
đoạn 4 đã có sẵn cửa chặn đầu tiên.

**Chưa có và cố ý chưa có:** ký số, `CtdtSubmitService`, nút "Ký và gửi", theo dõi hàng đợi trên
giao diện (Giai đoạn 4); xuất Excel, lệnh Console `ctdt:import`, dashboard (Giai đoạn 5).

**Việc cần kiểm bằng tay sau khi triển khai:**

1. Cài và khởi động dịch vụ `QLBV JobCtdt`, rồi nạp một gói thật. Sau vài giây mở lại màn danh
   sách — cột "Số lỗi" phải có giá trị và `checked_at` phải được điền.
2. Nạp một gói cố ý thiếu `HO_TEN`, mở tab Lỗi, xác nhận dòng lỗi nêu đúng `CTDT001` và `HO_TEN`.
3. `SELECT COUNT(*) FROM jobs WHERE queue = 'JobCtdt'` — phải về 0 sau khi nạp xong.
4. Sửa hồ sơ ở phần mềm sinh XML rồi nạp lại, xác nhận lỗi cũ biến mất chứ không cộng dồn.

**Ghi chú chuyển tiếp cho Giai đoạn 4:** `so_loi` giờ là dữ liệu thật, nên `QuyetDinhGui` mở rộng
(đặc tả mục 5.5) đã có nguồn cho nhánh `CON_LOI`. Thứ tự kiểm bắt buộc phải là **cờ bật/tắt trước,
rồi còn lỗi, rồi chưa ký** — khi chức năng gửi đang tắt mà vẫn ghi `submit_error` thì đó là bịa.
