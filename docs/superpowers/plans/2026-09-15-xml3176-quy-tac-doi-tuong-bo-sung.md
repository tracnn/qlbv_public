# Quy tắc mã đối tượng bổ sung (1.1, 3.6, 1.17) — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Thêm ba quy tắc cảnh báo cho mã đối tượng KCB 1.1, 3.6, 1.17 và danh mục bệnh Phụ lục I Thông tư 01/2025/TT-BYT nhập được qua màn Nhập danh mục.

**Architecture:** Danh mục Phụ lục I là bảng mới `benh_pl1_cap_chuyen_sau`, đăng ký theo đúng khuôn `dvkt_can_ma_may` (nhập thay trọn bộ, xem ở Danh mục tra cứu). So khớp mã bệnh là lớp thuần `BenhPl1Matcher` (theo từng STT, có mã trừ và điều kiện tuổi). Ba quy tắc nằm trong `Xml3176Xml1Checker::checkDoiTuongKcb()`, bật bằng ba thuộc tính mới trong `config/doi_tuong_kcb.php`.

**Tech Stack:** Laravel 5.5, PHP 7.4, maatwebsite/excel 3.1.25, phpoffice/phpspreadsheet 1.30.4, PHPUnit 6.5, MySQL (dev DB `qlbv`), sqlite in-memory cho test checker.

**Spec:** `docs/superpowers/specs/2026-09-15-xml3176-quy-tac-doi-tuong-bo-sung-design.md`

## Global Constraints

- **CẤM `RefreshDatabase`** và mọi thao tác xoá/ghi dữ liệu hồ sơ trên CSDL dev `qlbv`. Test cần bảng thì dùng `Tests\Support\Xml3176RuleTestSupport::bootXml3176Sqlite()`.
- Được phép duy nhất hai loại ghi lên CSDL dev: `php artisan migrate` (Task 2, Task 5) và nhập tệp mẫu vào **riêng** bảng `benh_pl1_cap_chuyen_sau` qua `CatalogImportService` (Task 6).
- PHPUnit 6.5: `protected function setUp()` **không** có `: void`; dùng `assertContains`/`assertNotContains`.
- PHP 7.4: không `match`, không named arguments, không union type.
- Chú thích trong mã viết tiếng Việt **không dấu**, theo phong cách `app/Services/Xml3176Xml1Checker.php`.
- **Không thêm `use` trùng:** trước khi thêm dòng `use` vào một tệp, `grep` xem đã có chưa — `use` trùng là lỗi fatal "name already in use".
- Mã lỗi mới, `critical_error = false`, `is_check = true`:
  - `XML1_DOI_TUONG_KCB_DKBD_KHAC_CSKCB` (mã 1.1)
  - `XML1_DOI_TUONG_KCB_THIEU_MA_KHUVUC` (mã 3.6)
  - `XML1_DOI_TUONG_KCB_BENH_NGOAI_PL1` (mã 1.17)
- Thuộc tính mới trong `config/doi_tuong_kcb.php`: `dkbd_phai_la_cskcb` (1.1), `can_ma_khuvuc` (3.6), `benh_pl1` (1.17).
- Tên danh mục: khoá `benh_pl1_cap_chuyen_sau`, bảng `benh_pl1_cap_chuyen_sau`, model `App\Models\BHYT\BenhPl1CapChuyenSau`.
- Giá trị cột `loai` lưu trong CSDL: `bao_gom` / `tru`. Trong tệp nhập: `BAO_GOM` / `TRU`.
- Quy tắc 1.1: **mọi** mã trong `MA_DKBD` (tách theo `;`) phải bằng `MA_CSKCB`; `MA_DKBD` hoặc `MA_CSKCB` rỗng thì không báo.
- Quy tắc 1.17: danh mục rỗng hoặc `MA_BENH_CHINH` rỗng thì không báo; danh mục **không** lưu đệm giữa các hồ sơ.
- Số danh mục BHYT trong `config/danh_muc_bhyt.php`: **13**.
- Tệp mẫu: **186** dòng dữ liệu (182 `BAO_GOM`, 4 `TRU`), 62 STT.
- Commit message kết thúc bằng dòng `Co-Authored-By: Claude Opus 5 <noreply@anthropic.com>`.
- Test đỏ có chủ đích, **không sửa**: `Tests\Unit\Ctdt\CtdtCauHinhTest::gui_len_cong_mac_dinh_tat`.

## Điều chỉnh so với spec (đã quyết khi lập plan)

1. **Tệp mẫu đặt ở `docs/0000 - Danh muc/PL1_TT01_2025.xlsx`** thay cho `docs/danh-muc/…`. Thư mục `docs/0000 - Danh muc/` là nơi repo đang để các tệp danh mục mẫu (danh mục nghề nghiệp, hành chính 2 cấp).
2. **Danh mục tra cứu sắp xếp mặc định theo `stt`** (một cột), thay cho `stt, loai, ma_icd`. `config/danh_muc_tra_cuu.php` chỉ nhận một cặp `[cột, hướng]`.
3. **Tệp mẫu sinh bằng script** `scripts/tao-mau-danh-muc-pl1-tt01-2025.php` và commit cả script lẫn tệp. Dữ liệu 62 dòng Phụ lục I nằm trong script — một nguồn duy nhất để đối chiếu với văn bản; tạo lại tệp chỉ cần chạy script.
4. **`detect_keys` là `['MA_ICD', 'LOAI', 'TUOI_DUOI']`** thay cho `['STT', 'MA_ICD', 'LOAI']`. Cột `STT` có trong rất nhiều tệp Excel; `ExcelColumnMapper` so khớp mờ nên dễ nhận nhầm loại danh mục.

## Cấu trúc tệp

| Tệp | Việc | Task |
|---|---|---|
| `app/Services/Xml3176/Support/Xml3176DateHelper.php` | Sửa: thêm `tuoiDuNam()` | 1 |
| `app/Services/Xml3176/Support/BenhPl1Matcher.php` | Tạo: so khớp mã bệnh với danh mục | 1 |
| `database/migrations/2026_09_15_100000_create_benh_pl1_cap_chuyen_sau_table.php` | Tạo | 2 |
| `app/Models/BHYT/BenhPl1CapChuyenSau.php` | Tạo | 2 |
| `config/catalog_import_mapping.php`, `config/danh_muc_bhyt.php`, `config/danh_muc_tra_cuu.php` | Sửa: đăng ký danh mục | 2 |
| `app/Services/CatalogImportService.php` | Sửa: ghi theo lô, thay trọn bộ, tên bảng, chuẩn hoá dòng | 2 |
| `scripts/tao-mau-danh-muc-pl1-tt01-2025.php`, `docs/0000 - Danh muc/PL1_TT01_2025.xlsx` | Tạo | 3 |
| `config/doi_tuong_kcb.php`, `app/Services/CommonValidationService.php`, `app/Services/Xml3176Xml1Checker.php` | Sửa: ba quy tắc | 4 |
| `database/seeds/Xml3176ErrorCatalogDoiTuongKcbSeeder.php`, `database/migrations/2026_09_15_110000_nap_ma_loi_doi_tuong_kcb_bo_sung.php` | Sửa / tạo: mã lỗi | 5 |

---

### Task 1: `tuoiDuNam()` và `BenhPl1Matcher`

**Files:**
- Modify: `app/Services/Xml3176/Support/Xml3176DateHelper.php`
- Create: `app/Services/Xml3176/Support/BenhPl1Matcher.php`
- Test: `tests/Unit/Xml3176/Support/BenhPl1MatcherTest.php`
- Test: `tests/Unit/Xml3176/Support/Xml3176DateHelperTuoiTest.php`

**Interfaces:**
- Produces:
  - `Xml3176DateHelper::tuoiDuNam($ngaySinh, $ngayMoc): ?int`
  - `BenhPl1Matcher::BAO_GOM = 'bao_gom'`, `BenhPl1Matcher::TRU = 'tru'`
  - `BenhPl1Matcher::chuanHoaMa($ma): string` — trim, bỏ `†`, `*`, dấu cách, viết hoa
  - `BenhPl1Matcher::mauKhop($mau, $ma): bool`
  - `BenhPl1Matcher::kiemTra($maBenh, array $dongDanhMuc, $tuoi): array` — trả `['khop' => bool, 'stt_sai_tuoi' => int[]]`; mỗi phần tử `$dongDanhMuc` là `['stt' => int, 'ma_icd' => string, 'loai' => 'bao_gom'|'tru', 'tuoi_duoi' => int|null]`

- [ ] **Step 1: Viết test hỏng cho tuổi**

`tests/Unit/Xml3176/Support/Xml3176DateHelperTuoiTest.php`:

```php
<?php

namespace Tests\Unit\Xml3176\Support;

use App\Services\Xml3176\Support\Xml3176DateHelper;
use Tests\TestCase;

class Xml3176DateHelperTuoiTest extends TestCase
{
    /** @test */
    public function tuoi_du_nam_tinh_theo_ngay_sinh_nhat()
    {
        $this->assertSame(17, Xml3176DateHelper::tuoiDuNam('20080916', '20260915'));
        $this->assertSame(18, Xml3176DateHelper::tuoiDuNam('20080916', '20260916'));
    }

    /** @test */
    public function chi_dung_8_ky_tu_dau_cua_chuoi_ngay_gio()
    {
        $this->assertSame(18, Xml3176DateHelper::tuoiDuNam('200809160000', '202609161230'));
    }

    /** @test */
    public function ngay_khong_doc_duoc_thi_null()
    {
        // XML3176 ghi ngay sinh khong ro ngay/thang bang 00.
        $this->assertNull(Xml3176DateHelper::tuoiDuNam('20080000', '20260915'));
        $this->assertNull(Xml3176DateHelper::tuoiDuNam('', '20260915'));
        $this->assertNull(Xml3176DateHelper::tuoiDuNam(null, '20260915'));
        $this->assertNull(Xml3176DateHelper::tuoiDuNam('20080916', ''));
        $this->assertNull(Xml3176DateHelper::tuoiDuNam('20080231', '20260915'));
    }

    /** @test */
    public function ngay_sinh_sau_ngay_moc_thi_null()
    {
        $this->assertNull(Xml3176DateHelper::tuoiDuNam('20270101', '20260915'));
    }
}
```

- [ ] **Step 2: Viết test hỏng cho matcher**

`tests/Unit/Xml3176/Support/BenhPl1MatcherTest.php`:

```php
<?php

namespace Tests\Unit\Xml3176\Support;

use App\Services\Xml3176\Support\BenhPl1Matcher;
use Tests\TestCase;

/**
 * Spec muc 6. Du lieu la mot phan that cua Phu luc I Thong tu 01/2025/TT-BYT.
 */
class BenhPl1MatcherTest extends TestCase
{
    private function dong($stt, $ma, $loai = 'bao_gom', $tuoiDuoi = null)
    {
        return ['stt' => $stt, 'ma_icd' => $ma, 'loai' => $loai, 'tuoi_duoi' => $tuoiDuoi];
    }

    /** Dong 14, 16, 21, 22 (rut gon C00-C97 con C38/C50/C83), 23, 44, 62. */
    private function danhMuc()
    {
        return [
            $this->dong(14, 'C25'),
            $this->dong(16, 'C38'),
            $this->dong(16, 'C38.4', 'tru'),
            $this->dong(21, 'C79.3'),
            $this->dong(22, 'C38', 'bao_gom', 18),
            $this->dong(22, 'C50', 'bao_gom', 18),
            $this->dong(22, 'C83', 'bao_gom', 18),
            $this->dong(23, 'C83'),
            $this->dong(23, 'C83.5', 'tru'),
            $this->dong(44, 'I51.2'),
            $this->dong(44, 'L51.2'),
            $this->dong(62, 'Z94'),
            $this->dong(1, 'A17.0'),
        ];
    }

    private function khop($ma, $tuoi = 40)
    {
        return BenhPl1Matcher::kiemTra($ma, $this->danhMuc(), $tuoi)['khop'];
    }

    /** @test */
    public function ma_3_ky_tu_bao_gom_moi_ma_chi_tiet()
    {
        // Ghi chu 1 cua Phu luc I.
        $this->assertTrue($this->khop('C25.3'));
        $this->assertTrue($this->khop('C25'));
        $this->assertTrue($this->khop('Z94.0'));
    }

    /** @test */
    public function ma_4_ky_tu_phai_khop_dung()
    {
        // Ghi chu 2: co ma chi tiet 4 ky tu thi phai ghi ro.
        $this->assertTrue($this->khop('C79.3'));
        $this->assertFalse($this->khop('C79'));
        $this->assertFalse($this->khop('C79.1'));
    }

    /** @test */
    public function ma_tru_chi_loai_trong_chinh_dong_cua_no()
    {
        $this->assertFalse($this->khop('C38.4', 40), 'C38.4 bi tru o dong 16, dong 22 can duoi 18 tuoi');
        $this->assertTrue($this->khop('C38.1', 40));
        $this->assertTrue($this->khop('C38.4', 10), 'Dong 22 van bao gom C38.4 cho nguoi duoi 18 tuoi');
    }

    /** @test */
    public function c83_5_theo_tuoi()
    {
        $this->assertTrue($this->khop('C83.5', 10));

        $kq = BenhPl1Matcher::kiemTra('C83.5', $this->danhMuc(), 40);
        $this->assertFalse($kq['khop']);
        $this->assertSame([22], $kq['stt_sai_tuoi']);

        // Khong xac dinh duoc tuoi thi coi nhu khop - thieu can cu thi khong bao.
        $this->assertTrue($this->khop('C83.5', null));
    }

    /** @test */
    public function dung_18_tuoi_la_khong_con_duoi_18()
    {
        $this->assertTrue($this->khop('C50.9', 17));
        $this->assertFalse($this->khop('C50.9', 18));
        $this->assertTrue($this->khop('C50.9', 10));
    }

    /** @test */
    public function dong_44_nhan_ca_i51_2_lan_l51_2()
    {
        $this->assertTrue($this->khop('I51.2'));
        $this->assertTrue($this->khop('L51.2'));
    }

    /** @test */
    public function chuan_hoa_ma_truoc_khi_so()
    {
        $this->assertTrue($this->khop('a17.0†'));
        $this->assertTrue($this->khop(' Z94.0 '));
        $this->assertSame('A17.0', BenhPl1Matcher::chuanHoaMa(' a17.0† '));
        $this->assertSame('G01', BenhPl1Matcher::chuanHoaMa('G01*'));
    }

    /** @test */
    public function ma_ngoai_danh_muc_hoac_rong()
    {
        $kq = BenhPl1Matcher::kiemTra('G44.0', $this->danhMuc(), 40);
        $this->assertFalse($kq['khop']);
        $this->assertSame([], $kq['stt_sai_tuoi']);

        $this->assertFalse($this->khop(''));
    }

    /** @test */
    public function danh_muc_rong_thi_khong_khop()
    {
        $this->assertFalse(BenhPl1Matcher::kiemTra('C25', [], 40)['khop']);
    }
}
```

- [ ] **Step 3: Chạy hai test, xác nhận hỏng**

Run: `./vendor/bin/phpunit tests/Unit/Xml3176/Support/Xml3176DateHelperTuoiTest.php && ./vendor/bin/phpunit tests/Unit/Xml3176/Support/BenhPl1MatcherTest.php`
Expected: lỗi `Call to undefined method …tuoiDuNam()` và `Class 'App\Services\Xml3176\Support\BenhPl1Matcher' not found`

- [ ] **Step 4: Thêm `tuoiDuNam()` vào `Xml3176DateHelper`**

Thêm phương thức này vào cuối lớp (trước dấu `}` đóng lớp):

```php
    /**
     * Tuoi du nam tai ngay moc, chi doc 8 ky tu dau (Ymd) cua moi chuoi.
     *
     * Mot trong hai ngay khong doc duoc - rong, thang/ngay 00 (XML3176 ghi ngay sinh khong
     * ro ngay bang 00), ngay khong ton tai - hoac ngay sinh sau ngay moc thi tra null: quy
     * tac dung tuoi phai im lang khi thieu can cu, khong duoc doan.
     */
    public static function tuoiDuNam($ngaySinh, $ngayMoc): ?int
    {
        $sinh = self::toDateTime(substr(trim((string) $ngaySinh), 0, 8));
        $moc  = self::toDateTime(substr(trim((string) $ngayMoc), 0, 8));

        if ($sinh === null || $moc === null || $sinh > $moc) {
            return null;
        }

        return (int) $sinh->diff($moc)->y;
    }
```

- [ ] **Step 5: Tạo `BenhPl1Matcher`**

```php
<?php

namespace App\Services\Xml3176\Support;

/**
 * So khop MA_BENH_CHINH voi danh muc Phu luc I Thong tu 01/2025/TT-BYT (benh duoc tu den
 * KCB tai co so cap chuyen sau).
 *
 * Danh muc KHONG phai danh sach ma phang: moi STT cua Phu luc I la mot nhom dong mau ma,
 * co dong 'bao_gom' va dong 'tru'. Ma tru chi co hieu luc trong CHINH STT cua no - dong 23
 * tru C83.5 nhung dong 22 (C00-C97, nguoi duoi 18 tuoi) van bao gom C83.5. Gop thanh mot
 * danh sach tru chung se bao oan tre em ung thu.
 *
 * Hai ghi chu cua van ban quyet dinh cach khop mau:
 *   1. Ma 3 ky tu bao gom moi ma chi tiet 4 ky tu (C25 gom C25.0 ... C25.9).
 *   2. Co ma chi tiet 4 ky tu thi phai ghi ro 4 ky tu - ho so ghi C79 khong khop dong C79.3.
 *
 * Helper thuan - khong cham DB/model/config.
 */
class BenhPl1Matcher
{
    const BAO_GOM = 'bao_gom';
    const TRU = 'tru';

    /** Trim, bo ky hieu phan loai kep (dao †, sao *) va dau cach, viet hoa. */
    public static function chuanHoaMa($ma): string
    {
        return strtoupper(str_replace(['†', '*', ' '], '', trim((string) $ma)));
    }

    /** Mot mau trong danh muc co khop ma benh khong. */
    public static function mauKhop($mau, $ma): bool
    {
        $mau = self::chuanHoaMa($mau);
        $ma  = self::chuanHoaMa($ma);

        if ($mau === '' || $ma === '') {
            return false;
        }

        if (strlen($mau) === 3) {
            return $ma === $mau || strpos($ma, $mau . '.') === 0;
        }

        return $ma === $mau;
    }

    /**
     * @param string $maBenh MA_BENH_CHINH cua ho so
     * @param array $dongDanhMuc cac dong ['stt', 'ma_icd', 'loai', 'tuoi_duoi']
     * @param int|null $tuoi tuoi du nam tai NGAY_VAO; null neu khong xac dinh duoc
     * @return array ['khop' => bool, 'stt_sai_tuoi' => int[]]
     *   stt_sai_tuoi: STT khop ma, khong bi tru, nhung nguoi benh khong duoi tuoi dong yeu cau
     */
    public static function kiemTra($maBenh, array $dongDanhMuc, $tuoi): array
    {
        $theoStt = [];

        foreach ($dongDanhMuc as $d) {
            if (!self::mauKhop($d['ma_icd'], $maBenh)) {
                continue;
            }

            $stt = (int) $d['stt'];

            if ($d['loai'] === self::TRU) {
                $theoStt[$stt]['tru'] = true;
            } elseif ($d['loai'] === self::BAO_GOM) {
                $tuoiDuoi = ($d['tuoi_duoi'] === null || $d['tuoi_duoi'] === '') ? null : (int) $d['tuoi_duoi'];
                $theoStt[$stt]['bao_gom'][] = $tuoiDuoi;
            }
        }

        $sttSaiTuoi = [];

        foreach ($theoStt as $stt => $x) {
            if (empty($x['bao_gom']) || !empty($x['tru'])) {
                continue;
            }

            foreach ($x['bao_gom'] as $tuoiDuoi) {
                // Khong xac dinh duoc tuoi thi coi nhu thoa: thieu can cu thi khong bao loi.
                if ($tuoiDuoi === null || $tuoi === null || $tuoi < $tuoiDuoi) {
                    return ['khop' => true, 'stt_sai_tuoi' => []];
                }
            }

            $sttSaiTuoi[] = $stt;
        }

        sort($sttSaiTuoi);

        return ['khop' => false, 'stt_sai_tuoi' => $sttSaiTuoi];
    }
}
```

- [ ] **Step 6: Chạy hai test, xác nhận qua**

Run: `./vendor/bin/phpunit tests/Unit/Xml3176/Support/Xml3176DateHelperTuoiTest.php && ./vendor/bin/phpunit tests/Unit/Xml3176/Support/BenhPl1MatcherTest.php`
Expected: `OK (4 tests, …)` và `OK (9 tests, …)`

- [ ] **Step 7: Commit**

```bash
git add app/Services/Xml3176/Support/Xml3176DateHelper.php app/Services/Xml3176/Support/BenhPl1Matcher.php tests/Unit/Xml3176/Support/Xml3176DateHelperTuoiTest.php tests/Unit/Xml3176/Support/BenhPl1MatcherTest.php
git commit -m "feat(xml3176): so khop ma benh voi danh muc PL1 TT01/2025 va tinh tuoi du nam

Co-Authored-By: Claude Opus 5 <noreply@anthropic.com>"
```

---

### Task 2: Bảng và đăng ký danh mục `benh_pl1_cap_chuyen_sau`

**Files:**
- Create: `database/migrations/2026_09_15_100000_create_benh_pl1_cap_chuyen_sau_table.php`
- Create: `app/Models/BHYT/BenhPl1CapChuyenSau.php`
- Modify: `config/catalog_import_mapping.php`, `config/danh_muc_bhyt.php`, `config/danh_muc_tra_cuu.php`
- Modify: `app/Services/CatalogImportService.php` (hằng `GHI_THEO_LO`, `LAM_MOI_TRON_BO`, hàm `bangCua()`, hàm `xuLyLo()`, thêm `chuanHoaBenhPl1()`)
- Modify: `tests/Unit/SoDangKyDanhMucTest.php`, `tests/Unit/Import/NhapDanhMucLonTheoLoTest.php`
- Test: `tests/Unit/Import/DanhMucBenhPl1NhapTest.php`

**Interfaces:**
- Consumes: `BenhPl1Matcher::chuanHoaMa($ma): string` (Task 1).
- Produces:
  - Bảng `benh_pl1_cap_chuyen_sau` (`stt`, `ten_benh`, `ma_icd`, `loai`, `tuoi_duoi`, `dieu_kien`, `is_active`, timestamps; unique `stt, ma_icd, loai`).
  - Model `App\Models\BHYT\BenhPl1CapChuyenSau`.
  - `CatalogImportService::chuanHoaBenhPl1(array $duLieu): ?array`.
  - Loại danh mục `benh_pl1_cap_chuyen_sau` nhận diện được từ tiêu đề `STT, TEN_BENH, MA_ICD, LOAI, TUOI_DUOI, DIEU_KIEN`.

- [ ] **Step 1: Viết test hỏng**

`tests/Unit/Import/DanhMucBenhPl1NhapTest.php`:

```php
<?php

namespace Tests\Unit\Import;

use App\Services\CatalogImportService;
use App\Services\ExcelColumnMapper;
use Tests\TestCase;

/**
 * Danh muc benh Phu luc I Thong tu 01/2025/TT-BYT - dang ky theo khuon dvkt_can_ma_may.
 */
class DanhMucBenhPl1NhapTest extends TestCase
{
    const TIEU_DE = ['STT', 'TEN_BENH', 'MA_ICD', 'LOAI', 'TUOI_DUOI', 'DIEU_KIEN'];

    /** @test */
    public function nhan_dien_dung_loai_tu_tieu_de_tep_mau()
    {
        $loai = app(ExcelColumnMapper::class)
            ->detectCatalogType(self::TIEU_DE, config('catalog_import_mapping'));

        $this->assertSame('benh_pl1_cap_chuyen_sau', $loai);
    }

    /** @test */
    public function khong_lam_nhan_nham_danh_muc_icd10_va_dvkt()
    {
        // MA_ICD rat gan MA_ICD10; ExcelColumnMapper co so khop mo.
        $m = app(ExcelColumnMapper::class);
        $cfg = config('catalog_import_mapping');

        $this->assertSame('icd10', $m->detectCatalogType(['MA_ICD10', 'TEN_ICD10', 'MA_CHUONG'], $cfg));
        $this->assertSame('dvkt_can_ma_may', $m->detectCatalogType(['MA_DVKT', 'TEN_DVKT_TT23'], $cfg));
    }

    /** @test */
    public function anh_xa_cot_va_khoa_duy_nhat()
    {
        $c = config('catalog_import_mapping.benh_pl1_cap_chuyen_sau');

        $this->assertSame(['stt', 'ma_icd', 'loai'], $c['required_fields']);
        $this->assertSame(['stt', 'ma_icd', 'loai'], $c['unique_keys']);
        $this->assertSame(['stt', 'ten_benh', 'ma_icd', 'loai', 'tuoi_duoi', 'dieu_kien'], array_keys($c['mapping']));
    }

    /** @test */
    public function ghi_theo_lo_va_lam_moi_tron_bo()
    {
        $this->assertContains('benh_pl1_cap_chuyen_sau', CatalogImportService::GHI_THEO_LO);
        $this->assertContains('benh_pl1_cap_chuyen_sau', CatalogImportService::LAM_MOI_TRON_BO);

        $svc = app(CatalogImportService::class);
        $ham = new \ReflectionMethod($svc, 'bangCua');
        $ham->setAccessible(true);
        $this->assertSame('benh_pl1_cap_chuyen_sau', $ham->invoke($svc, 'benh_pl1_cap_chuyen_sau'));
    }

    /** @test */
    public function chuan_hoa_loai_va_ma_icd()
    {
        $ra = CatalogImportService::chuanHoaBenhPl1(['stt' => 1, 'ma_icd' => ' a17.0† ', 'loai' => 'BAO_GOM']);
        $this->assertSame('bao_gom', $ra['loai']);
        $this->assertSame('A17.0', $ra['ma_icd']);

        $ra = CatalogImportService::chuanHoaBenhPl1(['stt' => 16, 'ma_icd' => 'C38.4', 'loai' => ' Tru ']);
        $this->assertSame('tru', $ra['loai']);
    }

    /** @test */
    public function loai_khong_hop_le_thi_bo_dong()
    {
        $this->assertNull(CatalogImportService::chuanHoaBenhPl1(['stt' => 1, 'ma_icd' => 'A17.0', 'loai' => 'CO']));
        $this->assertNull(CatalogImportService::chuanHoaBenhPl1(['stt' => 1, 'ma_icd' => 'A17.0', 'loai' => '']));
    }

    /** @test */
    public function xem_duoc_o_danh_muc_tra_cuu()
    {
        $m = config('danh_muc_tra_cuu.benh_pl1_cap_chuyen_sau');

        $this->assertSame(\App\Models\BHYT\BenhPl1CapChuyenSau::class, $m['model']);
        $this->assertSame(['is_active'], $m['cot_co_khong']);
        $this->assertSame(['stt', 'asc'], $m['sap_xep']);
    }
}
```

- [ ] **Step 2: Chạy test, xác nhận hỏng**

Run: `./vendor/bin/phpunit tests/Unit/Import/DanhMucBenhPl1NhapTest.php`
Expected: các test hỏng (loại nhận diện ra `null`, khoá cấu hình không tồn tại, `chuanHoaBenhPl1` chưa có).

- [ ] **Step 3: Tạo migration**

`database/migrations/2026_09_15_100000_create_benh_pl1_cap_chuyen_sau_table.php`:

```php
<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Danh muc benh Phu luc I Thong tu 01/2025/TT-BYT: benh duoc tu den KCB tai co so cap
 * chuyen sau (ma doi tuong KCB 1.17).
 *
 * Moi dong la MOT MAU MA thuoc mot STT cua Phu luc I; mot STT co nhieu dong 'bao_gom' va
 * co the co dong 'tru'. Danh muc QUOC GIA, nap theo kieu THAY TRON BO: cot is_active la bat
 * buoc vi CatalogImportService dung chinh cot nay.
 */
class CreateBenhPl1CapChuyenSauTable extends Migration
{
    public function up()
    {
        Schema::create('benh_pl1_cap_chuyen_sau', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedSmallInteger('stt');
            $table->string('ten_benh', 1024)->nullable();
            $table->string('ma_icd', 20);
            $table->string('loai', 10);
            $table->unsignedTinyInteger('tuoi_duoi')->nullable();
            $table->text('dieu_kien')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['stt', 'ma_icd', 'loai']);
            $table->index('ma_icd');
        });
    }

    public function down()
    {
        Schema::dropIfExists('benh_pl1_cap_chuyen_sau');
    }
}
```

- [ ] **Step 4: Tạo model**

`app/Models/BHYT/BenhPl1CapChuyenSau.php`:

```php
<?php

namespace App\Models\BHYT;

use Illuminate\Database\Eloquent\Model;

class BenhPl1CapChuyenSau extends Model
{
    protected $table = 'benh_pl1_cap_chuyen_sau';
    protected $fillable = ['stt', 'ten_benh', 'ma_icd', 'loai', 'tuoi_duoi', 'dieu_kien', 'is_active'];
}
```

- [ ] **Step 5: Đăng ký trong ba tệp cấu hình**

`config/catalog_import_mapping.php` — thêm mục ngay sau mục `'dvkt_can_ma_may' => [...]` (trước `];` cuối tệp):

```php
    // Danh muc benh Phu luc I Thong tu 01/2025/TT-BYT (ma doi tuong 1.17). Tep mau:
    // docs/0000 - Danh muc/PL1_TT01_2025.xlsx. KHONG dung 'STT' lam detect_keys: cot STT co
    // trong rat nhieu tep Excel va ExcelColumnMapper so khop mo.
    'benh_pl1_cap_chuyen_sau' => [
        'detect_keys' => ['MA_ICD', 'LOAI', 'TUOI_DUOI'],
        'mapping' => [
            'stt'       => ['STT'],
            'ten_benh'  => ['TEN_BENH'],
            'ma_icd'    => ['MA_ICD'],
            'loai'      => ['LOAI'],
            'tuoi_duoi' => ['TUOI_DUOI'],
            'dieu_kien' => ['DIEU_KIEN'],
        ],
        'required_fields' => ['stt', 'ma_icd', 'loai'],
        'unique_keys' => ['stt', 'ma_icd', 'loai'],
    ],
```

`config/danh_muc_bhyt.php` — thêm ngay sau mục `'dvkt_can_ma_may' => [...]`:

```php
    'benh_pl1_cap_chuyen_sau' => [
        'ten' => 'DM bệnh PL1 TT01 cấp chuyên sâu',
        'model' => App\Models\BHYT\BenhPl1CapChuyenSau::class,
        'bang' => 'benh_pl1_cap_chuyen_sau',
        'theo_co_so' => false,
    ],
```

`config/danh_muc_tra_cuu.php` — thêm ngay sau mục `'dvkt_can_ma_may' => [...]`:

```php
    'benh_pl1_cap_chuyen_sau' => [
        'ten'          => 'Bệnh PL1 TT01 cấp chuyên sâu',
        'model'        => App\Models\BHYT\BenhPl1CapChuyenSau::class,
        'cot'          => [
            'stt'       => 'STT',
            'ma_icd'    => 'Mã ICD',
            'loai'      => 'Loại',
            'tuoi_duoi' => 'Dưới tuổi',
            'ten_benh'  => 'Tên bệnh',
            'dieu_kien' => 'Điều kiện',
            'is_active' => 'Đang dùng',
        ],
        'cot_co_khong' => ['is_active'],
        'sap_xep'      => ['stt', 'asc'],
    ],
```

- [ ] **Step 6: Sửa `CatalogImportService`**

1. Hằng `GHI_THEO_LO`: thêm `'benh_pl1_cap_chuyen_sau'` vào cuối mảng.
2. Hằng `LAM_MOI_TRON_BO`: đổi thành `['administrative_unit', 'medical_organization', 'dvkt_can_ma_may', 'benh_pl1_cap_chuyen_sau']`.
3. Hàm `bangCua()`: thêm phần tử `'benh_pl1_cap_chuyen_sau' => 'benh_pl1_cap_chuyen_sau',` ngay sau `'dvkt_can_ma_may' => 'dvkt_can_ma_may',`.
4. Thêm hàm tĩnh ngay sau hàm `chuanHoaManTinh()`:

```php
    /**
     * Chuan hoa mot dong danh muc benh Phu luc I truoc khi ghi.
     *
     * LOAI trong tep viet hoa BAO_GOM / TRU cho de go; cot loai luu chu thuong. MA_ICD bo ky
     * hieu phan loai kep († *) va viet hoa - dung CUNG ham chuan hoa ma ma quy tac 1.17 dung
     * khi so khop, de hai ben khong lech nhau.
     *
     * Tra null khi LOAI khong hop le: dong do phai bi bo qua va bao ra, khong duoc ghi -
     * mot dong mau ma sai loai se lam quy tac bao oan hoac bo sot im lang.
     *
     * Ham THUAN de kiem duoc.
     */
    public static function chuanHoaBenhPl1(array $duLieu)
    {
        $loai = mb_strtolower(trim((string) (isset($duLieu['loai']) ? $duLieu['loai'] : '')));

        if (!in_array($loai, [\App\Services\Xml3176\Support\BenhPl1Matcher::BAO_GOM,
                              \App\Services\Xml3176\Support\BenhPl1Matcher::TRU], true)) {
            return null;
        }

        $duLieu['loai'] = $loai;

        if (array_key_exists('ma_icd', $duLieu)) {
            $duLieu['ma_icd'] = \App\Services\Xml3176\Support\BenhPl1Matcher::chuanHoaMa($duLieu['ma_icd']);
        }

        return $duLieu;
    }
```

5. Trong `xuLyLo()`, ngay **sau** dòng `$duLieu = self::chuanHoaManTinh($duLieu);` và **trước** `$duLieu = self::ganDangDung($duLieu, $tt['type']);`, chèn:

```php
            if ($tt['type'] === 'benh_pl1_cap_chuyen_sau') {
                $duLieu = self::chuanHoaBenhPl1($duLieu);

                if ($duLieu === null) {
                    $this->ketQua->themBoQua($dongExcel, 'LOAI phải là BAO_GOM hoặc TRU');
                    continue;
                }
            }
```

- [ ] **Step 7: Nâng hai test đang chốt cứng**

`tests/Unit/SoDangKyDanhMucTest.php`:
- Đổi tên hàm `du_12_bo_va_trung_khoa_voi_cau_hinh_nhap_khau` thành `du_13_bo_va_trung_khoa_voi_cau_hinh_nhap_khau`.
- Đổi `$this->assertCount(12, $so);` thành `$this->assertCount(13, $so);`.
- Trong docblock của hàm đó, thêm một dòng sau đoạn "11 -> 12 …": `12 -> 13 tu 15/09/2026: them 'benh_pl1_cap_chuyen_sau' (danh muc benh Phu luc I TT 01/2025).`

`tests/Unit/Import/NhapDanhMucLonTheoLoTest.php`, hàm `chi_ba_danh_muc_nay_lam_moi_tron_bo`:
- Đổi mảng kỳ vọng thành `['administrative_unit', 'medical_organization', 'dvkt_can_ma_may', 'benh_pl1_cap_chuyen_sau']`.
- Thêm chú thích: `// 15/09/2026 them 'benh_pl1_cap_chuyen_sau': danh muc quoc gia, bang co is_active, Bo Y te sua doi Phu luc I thi phai thay tron bo.`

- [ ] **Step 8: Chạy migration trên CSDL dev**

Nhiều test đang có (`SoDangKyDanhMucTest::moi_bang_deu_ton_tai_va_khop_model`, `NhapDanhMucLonTheoLoTest::chi_con_dung_nhung_truong_bi_bo_da_biet`) đọc bảng thật trên CSDL dev.

Run: `php artisan migrate`
Expected: `Migrated: 2026_09_15_100000_create_benh_pl1_cap_chuyen_sau_table`. Ghi nguyên văn vào báo cáo. Nếu lệnh liệt kê migration khác đang treo, **dừng** và báo — không chạy.

- [ ] **Step 9: Chạy test**

Run: `./vendor/bin/phpunit tests/Unit/Import && ./vendor/bin/phpunit tests/Unit/SoDangKyDanhMucTest.php && ./vendor/bin/phpunit tests/Unit/DanhMucTraCuuSoDangKyTest.php && ./vendor/bin/phpunit tests/Feature/DanhMucTraCuuControllerTest.php`
Expected: tất cả `OK`.

Nếu `khong_lam_nhan_nham_danh_muc_icd10_va_dvkt` hỏng vì tiêu đề icd10 giả định trong test không đúng cấu hình thật, mở `config/catalog_import_mapping.php`, lấy đúng `detect_keys` của `icd10` làm tiêu đề — **không** đổi `detect_keys` của danh mục có sẵn.

- [ ] **Step 10: Commit**

```bash
git add database/migrations/2026_09_15_100000_create_benh_pl1_cap_chuyen_sau_table.php app/Models/BHYT/BenhPl1CapChuyenSau.php config/catalog_import_mapping.php config/danh_muc_bhyt.php config/danh_muc_tra_cuu.php app/Services/CatalogImportService.php tests/Unit/Import/DanhMucBenhPl1NhapTest.php tests/Unit/SoDangKyDanhMucTest.php tests/Unit/Import/NhapDanhMucLonTheoLoTest.php
git commit -m "feat(danh-muc): danh muc benh Phu luc I TT01/2025 nhap qua man Nhap danh muc

Co-Authored-By: Claude Opus 5 <noreply@anthropic.com>"
```

---

### Task 3: Tệp mẫu 186 dòng

**Files:**
- Create: `scripts/tao-mau-danh-muc-pl1-tt01-2025.php`
- Create: `docs/0000 - Danh muc/PL1_TT01_2025.xlsx` (sinh bằng script)
- Test: `tests/Unit/Xml3176/MauDanhMucPl1Test.php`

**Interfaces:**
- Consumes: tiêu đề cột của Task 2: `STT, TEN_BENH, MA_ICD, LOAI, TUOI_DUOI, DIEU_KIEN`.
- Produces: tệp `docs/0000 - Danh muc/PL1_TT01_2025.xlsx` — Task 6 nhập tệp này.

Dữ liệu dưới đây đã đối chiếu từng dòng với Phụ lục I bản PDF chính thức. **Chép đúng**, không sửa mã.

- [ ] **Step 1: Viết test hỏng**

`tests/Unit/Xml3176/MauDanhMucPl1Test.php`:

```php
<?php

namespace Tests\Unit\Xml3176;

use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

/**
 * Chot tep mau danh muc Phu luc I Thong tu 01/2025/TT-BYT (spec muc 5.2, Phu luc A).
 * Chep sai mot ma o day la quy tac 1.17 bao oan hoac bo sot - test khoa cac cho de sai.
 */
class MauDanhMucPl1Test extends TestCase
{
    private static $dong;

    private function dong()
    {
        if (self::$dong === null) {
            $ws = IOFactory::load(base_path('docs/0000 - Danh muc/PL1_TT01_2025.xlsx'))->getActiveSheet();
            $bang = $ws->toArray(null, true, false, false);
            $tieuDe = array_shift($bang);
            $this->assertSame(['STT', 'TEN_BENH', 'MA_ICD', 'LOAI', 'TUOI_DUOI', 'DIEU_KIEN'], $tieuDe);

            self::$dong = array_map(function ($r) {
                return [
                    'stt' => (int) $r[0], 'ten' => (string) $r[1], 'ma' => (string) $r[2],
                    'loai' => (string) $r[3], 'tuoi' => ($r[4] === null || $r[4] === '') ? null : (int) $r[4],
                ];
            }, array_values(array_filter($bang, function ($r) { return $r[0] !== null && $r[0] !== ''; })));
        }

        return self::$dong;
    }

    private function cua($stt)
    {
        return array_values(array_filter($this->dong(), function ($d) use ($stt) { return $d['stt'] === $stt; }));
    }

    /** @test */
    public function du_186_dong_62_stt()
    {
        $d = $this->dong();
        $this->assertCount(186, $d);
        $this->assertSame(range(1, 62), array_values(array_unique(array_column($d, 'stt'))));
        $this->assertCount(182, array_filter($d, function ($x) { return $x['loai'] === 'BAO_GOM'; }));
    }

    /** @test */
    public function dung_4_dong_tru()
    {
        $tru = array_values(array_filter($this->dong(), function ($x) { return $x['loai'] === 'TRU'; }));
        $cap = array_map(function ($x) { return $x['stt'] . ':' . $x['ma']; }, $tru);
        sort($cap);
        $this->assertSame(['16:C38.4', '23:C83.5', '25:D61.9', '38:G04.2'], $cap);
    }

    /** @test */
    public function moi_ma_dung_dinh_dang_va_moi_loai_hop_le()
    {
        foreach ($this->dong() as $x) {
            $this->assertRegExp('/^[A-Z]\d{2}(\.\d)?$/', $x['ma'], 'STT ' . $x['stt']);
            $this->assertContains($x['loai'], ['BAO_GOM', 'TRU']);
            $this->assertNotSame('', trim($x['ten']), 'STT ' . $x['stt'] . ' thieu ten benh');
        }
    }

    /** @test */
    public function cac_khoang_ma_duoc_tach_du()
    {
        $ma22 = array_column($this->cua(22), 'ma');
        $this->assertCount(98, $ma22);
        $this->assertSame('C00', $ma22[0]);
        $this->assertSame('C97', end($ma22));

        $this->assertSame(
            ['C81', 'C82', 'C83', 'C84', 'C85', 'C86', 'C90', 'C91', 'C92', 'C93', 'C94', 'C95', 'C96', 'C83.5'],
            array_column($this->cua(23), 'ma')
        );
        $this->assertSame(['E74', 'E75', 'E76'], array_column($this->cua(33), 'ma'));
        $this->assertSame(['Q20', 'Q21', 'Q22', 'Q23', 'Q24', 'Q25', 'Q26', 'Q27', 'Q28'], array_column($this->cua(58), 'ma'));
    }

    /** @test */
    public function dong_44_co_ca_i51_2_va_l51_2()
    {
        $this->assertSame(['I51.2', 'L51.2'], array_column($this->cua(44), 'ma'));
    }

    /** @test */
    public function dieu_kien_tuoi_dung_5_stt()
    {
        $coTuoi = [];
        foreach ($this->dong() as $x) {
            if ($x['tuoi'] !== null) {
                $this->assertSame(18, $x['tuoi']);
                $coTuoi[$x['stt']] = true;
            }
        }
        $this->assertSame([22, 30, 31, 32, 58], array_keys($coTuoi));

        foreach ([22, 30, 31, 32, 58] as $stt) {
            foreach ($this->cua($stt) as $x) {
                $this->assertSame(18, $x['tuoi'], "Moi dong cua STT $stt phai co TUOI_DUOI = 18");
            }
        }
    }

    /** @test */
    public function mau_ma_cac_dong_don()
    {
        $moi = [];
        foreach ($this->dong() as $x) {
            if (count($this->cua($x['stt'])) === 1) {
                $moi[$x['stt']] = $x['ma'];
            }
        }

        $this->assertSame('A17.0', $moi[1]);
        $this->assertSame('C79.3', $moi[21]);
        $this->assertSame('E11.7', $moi[29]);
        $this->assertSame('I50', $moi[43]);
        $this->assertSame('M32.1', $moi[54]);
        $this->assertSame('Z94', $moi[62]);
    }
}
```

- [ ] **Step 2: Chạy test, xác nhận hỏng**

Run: `./vendor/bin/phpunit tests/Unit/Xml3176/MauDanhMucPl1Test.php`
Expected: lỗi không mở được tệp `PL1_TT01_2025.xlsx`.

- [ ] **Step 3: Viết script sinh tệp**

`scripts/tao-mau-danh-muc-pl1-tt01-2025.php`:

```php
<?php

/**
 * Sinh tep mau danh muc benh Phu luc I Thong tu 01/2025/TT-BYT de nhap qua man Nhap danh muc.
 *
 * NGUON: ban PDF co chu ky so cua Thong tu 01/2025/TT-BYT, Phu luc I (trang 19-25), doi
 * chieu voi ban go lai tai blogbhxh.com. Ky hieu † sau ma (A17.0†, B42.0†, M32.1†) la ky
 * hieu phan loai kep cua ICD-10, khong thuoc ma - da bo.
 *
 * Quy uoc tach dong (spec muc 5.2):
 *   - moi ma, hoac moi ma 3 ky tu trong mot khoang, la mot dong BAO_GOM cung STT;
 *   - moi ma tru la mot dong TRU cung STT;
 *   - dong 44 van ban in I51.2, ma ICD-10 dung cua benh la L51.2 - ghi ca hai;
 *   - TUOI_DUOI = 18 cho moi dong cua STT 22, 30, 31, 32, 58.
 *
 * Chay: php scripts/tao-mau-danh-muc-pl1-tt01-2025.php
 */

require __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$NGAY = 'Người bệnh được hưởng quyền lợi ngay trong lượt khám bệnh, chữa bệnh có kết quả chẩn đoán xác định mắc bệnh.';
$KDH  = 'Không áp dụng đối với trường hợp đã được chẩn đoán xác định nhưng không có chỉ định điều trị đặc hiệu.';
$NANG = 'Tình trạng tiến triển nặng theo hướng dẫn chẩn đoán, điều trị.';

/** Cac ma 3 ky tu tu $tu den $den cung chu cai, vd khoang('C', 0, 97). */
function khoang($chu, $tu, $den)
{
    $ra = [];
    for ($i = $tu; $i <= $den; $i++) {
        $ra[] = sprintf('%s%02d', $chu, $i);
    }
    return $ra;
}

// [STT, TEN_BENH, [ma BAO_GOM], [ma TRU], TUOI_DUOI, DIEU_KIEN]
$pl1 = [
    [1, 'Viêm màng não do lao (G01*)', ['A17.0'], [], null, ''],
    [2, 'U lao màng não (G07*)', ['A17.1'], [], null, ''],
    [3, 'Lao khác của hệ thần kinh', ['A17.8'], [], null, ''],
    [4, 'Lao hệ thần kinh, không xác định (G99.8*)', ['A17.9'], [], null, ''],
    [5, 'Nhiễm mycobacteria ở phổi', ['A31.0'], [], null, ''],
    [6, 'Nhiễm histoplasma capsulatum ở phổi cấp tính', ['B39.0'], [], null, ''],
    [7, 'Nhiễm nấm blastomyces ở phổi cấp tính', ['B40.0'], [], null, ''],
    [8, 'Nhiễm nấm paracoccidioides ở phổi', ['B41.0'], [], null, ''],
    [9, 'Nhiễm sporotrichum ở phổi (J99.8*)', ['B42.0'], [], null, ''],
    [10, 'Nhiễm aspergillus ở phổi xâm lấn', ['B44.0'], [], null, ''],
    [11, 'Nhiễm cryptococcus ở phổi', ['B45.0'], [], null, ''],
    [12, 'Nhiễm mucor ở phổi', ['B46.0'], [], null, ''],
    [13, 'Nhiễm mucor lan toả', ['B46.4'], [], null, ''],
    [14, 'U ác tụy', ['C25'], [], null, $NGAY],
    [15, 'U ác tuyến ức', ['C37'], [], null, $NGAY],
    [16, 'U ác của tim, trung thất và màng phổi', ['C38'], ['C38.4'], null, $NGAY],
    [17, 'U ác của xương và sụn khớp ở vị trí khác và không xác định', ['C41'], [], null, $NGAY],
    [18, 'U ác của màng não', ['C70'], [], null, $NGAY],
    [19, 'U ác của não', ['C71'], [], null, $NGAY],
    [20, 'U ác của tủy sống, dây thần kinh sọ và các phần khác của hệ thần kinh trung ương', ['C72'], [], null, $NGAY],
    [21, 'U ác thứ phát của não và màng não', ['C79.3'], [], null, $NGAY],
    [22, 'Nhóm u ác tính', khoang('C', 0, 97), [], 18,
        'Có đủ 02 điều kiện sau đây: - Người dưới 18 tuổi. - ' . $KDH],
    [23, 'U ác của hệ lympho, hệ tạo máu và các mô liên quan',
        array_merge(khoang('C', 81, 86), khoang('C', 90, 96)), ['C83.5'], null, $KDH],
    [24, 'Hội chứng loạn sản tủy xương', ['D46'], [], null, $KDH],
    [25, 'Các thể suy tủy xương khác', ['D61'], ['D61.9'], null, $KDH],
    [26, 'Bệnh tăng đông máu khác (Hội chứng kháng phospho lipid)', ['D68.6'], [], null, ''],
    [27, 'Hội chứng thực bào tế bào máu liên quan đến nhiễm trùng', ['D76.2'], [], null, ''],
    [28, 'Bệnh đái tháo đường phụ thuộc insuline (Có đa biến chứng)', ['E10.7'], [], null,
        'Có biến chứng loét bàn chân độ 2 hoặc có bệnh thận mạn giai đoạn 3 trở lên hoặc có ít nhất 02 trong số các biến chứng: tim mạch, mắt, thần kinh, mạch máu.'],
    [29, 'Bệnh đái tháo đường không phụ thuộc insuline (Có đa biến chứng)', ['E11.7'], [], null,
        'Có biến chứng loét bàn chân độ 2 hoặc có bệnh thận mạn giai đoạn 3 trở lên.'],
    [30, 'Rối loạn chuyển hóa acid amin thơm', ['E70'], [], 18, 'Người dưới 18 tuổi.'],
    [31, 'Rối loạn chuyển hóa acid amin chuỗi nhánh và rối loạn chuyển hóa acid béo', ['E71'], [], 18, 'Người dưới 18 tuổi.'],
    [32, 'Các rối loạn khác của chuyển hóa acid amin', ['E72'], [], 18, 'Người dưới 18 tuổi.'],
    [33, 'Nhóm rối loạn dự trữ thể tiêu bào (Bệnh Pompe, bệnh MPS, Bệnh Gaucher, Bệnh Fabry)', ['E74', 'E75', 'E76'], [], null,
        'Áp mã theo ICD-10 của WHO cập nhật năm 2021. ' . $NGAY],
    [34, 'Rối loạn chuyển hóa đồng (bao gồm cả bệnh Wilson)', ['E83.0'], [], null,
        'Bệnh Wilson có biến chứng (có một trong các biến chứng của xơ gan, suy gan cấp, tối cấp, suy thận cấp, rối loạn vận động, rối loạn vận ngôn, rối loạn tâm thần, sa sút trí tuệ, động kinh bệnh cơ tim, rối loạn nhịp tim).'],
    [35, 'Thoái hóa dạng bột', ['E85'], [], null, $KDH],
    [36, 'Rối loạn trầm cảm tái diễn', ['F33'], [], null, '- Kháng thuốc. - ' . $NGAY],
    [37, 'Rối loạn ám ảnh nghi thức', ['F42'], [], null, ''],
    [38, 'Viêm não, viêm tủy và viêm não-tủy', ['G04'], ['G04.2'], null, ''],
    [39, 'Xơ cứng rải rác', ['G35'], [], null, ''],
    [40, 'Viêm tủy thị thần kinh [Devic]', ['G36.0'], [], null, ''],
    [41, 'Nhược cơ', ['G70.0'], [], null, '- Trường hợp phải lọc máu, suy hô hấp. - ' . $NGAY],
    [42, 'Bệnh lý võng mạc của trẻ đẻ non', ['H35.1'], [], null, $NGAY],
    [43, 'Suy tim', ['I50'], [], null, 'Đã có kết luận chẩn đoán giai đoạn 3, giai đoạn 4.'],
    [44, 'Hoại tử thượng bì nhiễm độc (Lyell/Steven Johnson)', ['I51.2', 'L51.2'], [], null,
        '[Ghi chú danh mục] Văn bản in mã I51.2; mã ICD-10 đúng của bệnh là L51.2 - danh mục ghi cả hai.'],
    [45, 'Hội chứng sau mổ tim', ['I97.0'], [], null, ''],
    [46, 'Rối loạn chức năng khác sau phẫu thuật tim', ['I97.1'], [], null, ''],
    [47, 'Bệnh phổi mô kẽ khác', ['J84'], [], null, ''],
    [48, 'Áp xe phổi và trung thất', ['J85'], [], null, $NANG],
    [49, 'Mủ lồng ngực (nhiễm trùng nặng ở phổi)', ['J86'], [], null, $NANG],
    [50, 'Bệnh Crohn (viêm ruột từng vùng)', ['K50'], [], null,
        'Mức độ nặng theo thang điểm CDAI từ 450 điểm trở lên, hoặc có biến chứng như rò, thủng, áp xe trong ổ bụng, suy dinh dưỡng nặng.'],
    [51, 'Pemphigus', ['L10'], [], null,
        'Một trong các điều kiện sau đây: - Tổn thương da >10% diện tích cơ thể. - Tình trạng tiến triển bệnh nặng theo hướng dẫn chẩn đoán và điều trị. - Á u.'],
    [52, 'Viêm mạch mạng lưới', ['L95.0'], [], null, ''],
    [53, 'Bệnh da tăng bạch cầu trung tính có sốt [Hội chứng Sweet]', ['L98.2'], [], null, ''],
    [54, 'Bệnh Lupus ban đỏ hệ thống có tổn thương phủ tạng', ['M32.1'], [], null,
        '- Tổn thương tim hoặc phổi hoặc thận nặng, tiến triển, đe dọa tính mạng. - ' . $NGAY],
    [55, 'Đái tháo đường sơ sinh', ['P70.2'], [], null, $NGAY],
    [56, 'Dị tật bẩm sinh khác của não', ['Q04'], [], null, $NGAY],
    [57, 'Các dị tật bẩm sinh khác của tủy sống', ['Q06'], [], null, $NGAY],
    [58, 'Nhóm các dị tật bẩm sinh của hệ thống tuần hoàn', khoang('Q', 20, 28), [], 18,
        'Người dưới 18 tuổi thuộc một trong 02 trường hợp sau đây: - Phẫu thuật/can thiệp loại đặc biệt. - 03 phẫu thuật/can thiệp đồng thời trở lên.'],
    [59, 'Biến dạng bẩm sinh của khớp háng', ['Q65'], [], null, 'Có chỉ định thay khớp.'],
    [60, 'Kháng (các) thuốc chống lao', ['U84.3'], [], null, ''],
    [61, 'Di chứng của hoạt động chiến tranh (Di chứng do vết thương chiến tranh)', ['Y89.1'], [], null,
        'Áp dụng đối với thương binh, bệnh binh, người có công với cách mạng.'],
    [62, 'Tình trạng của mảnh ghép cơ quan và tổ chức', ['Z94'], [], null,
        'Áp dụng đối với người bệnh có ghép tạng và điều trị sau ghép tạng.'],
];

$wb = new Spreadsheet();
$ws = $wb->getActiveSheet();
$ws->setTitle('PL1_TT01_2025');
$ws->fromArray(['STT', 'TEN_BENH', 'MA_ICD', 'LOAI', 'TUOI_DUOI', 'DIEU_KIEN'], null, 'A1');

$r = 2;
foreach ($pl1 as $d) {
    list($stt, $ten, $baoGom, $tru, $tuoi, $dieuKien) = $d;

    foreach ([['BAO_GOM', $baoGom], ['TRU', $tru]] as $nhom) {
        foreach ($nhom[1] as $ma) {
            $ws->setCellValue('A' . $r, $stt);
            $ws->setCellValueExplicit('B' . $r, $ten, DataType::TYPE_STRING);
            $ws->setCellValueExplicit('C' . $r, $ma, DataType::TYPE_STRING);
            $ws->setCellValueExplicit('D' . $r, $nhom[0], DataType::TYPE_STRING);
            if ($tuoi !== null) {
                $ws->setCellValue('E' . $r, $tuoi);
            }
            $ws->setCellValueExplicit('F' . $r, $dieuKien, DataType::TYPE_STRING);
            $r++;
        }
    }
}

$dich = __DIR__ . '/../docs/0000 - Danh muc/PL1_TT01_2025.xlsx';
(new Xlsx($wb))->save($dich);

printf("so_dong=%d tep=%s\n", $r - 2, realpath($dich));
```

- [ ] **Step 4: Sinh tệp**

Run: `php scripts/tao-mau-danh-muc-pl1-tt01-2025.php`
Expected: `so_dong=186 tep=…PL1_TT01_2025.xlsx`

- [ ] **Step 5: Chạy test, xác nhận qua**

Run: `./vendor/bin/phpunit tests/Unit/Xml3176/MauDanhMucPl1Test.php`
Expected: `OK (7 tests, …)`

Nếu hỏng: sửa **dữ liệu trong script** cho khớp Phụ lục A của spec, chạy lại Step 4. Không sửa test để khớp tệp.

- [ ] **Step 6: Commit**

```bash
git add scripts/tao-mau-danh-muc-pl1-tt01-2025.php "docs/0000 - Danh muc/PL1_TT01_2025.xlsx" tests/Unit/Xml3176/MauDanhMucPl1Test.php
git commit -m "feat(danh-muc): tep mau 186 dong danh muc benh Phu luc I TT01/2025 va script sinh tep

Co-Authored-By: Claude Opus 5 <noreply@anthropic.com>"
```

---

### Task 4: Ba quy tắc trong `checkDoiTuongKcb()`

**Files:**
- Modify: `config/doi_tuong_kcb.php`
- Modify: `app/Services/CommonValidationService.php`
- Modify: `app/Services/Xml3176Xml1Checker.php`
- Test: `tests/Unit/Xml3176/Checker/Xml3176Xml1DoiTuongKcbBoSungTest.php`

**Interfaces:**
- Consumes: `BenhPl1Matcher::kiemTra()`, `Xml3176DateHelper::tuoiDuNam()` (Task 1); model `BenhPl1CapChuyenSau`, migration `2026_09_15_100000_create_benh_pl1_cap_chuyen_sau_table.php` (Task 2); `DanhSachPhanCachParser::tach($chuoi): array` và `DoiTuongKcbCatalog::thuocTinh($ma, $danhMuc, $khoa, $macDinh)` (có sẵn).
- Produces: `CommonValidationService::danhMucBenhPl1(): array` — mảng các dòng `['stt' => int, 'ma_icd' => string, 'loai' => string, 'tuoi_duoi' => int|null]`, chỉ dòng `is_active = 1`; ba mã lỗi ở Global Constraints.

- [ ] **Step 1: Viết test hỏng**

`tests/Unit/Xml3176/Checker/Xml3176Xml1DoiTuongKcbBoSungTest.php`:

```php
<?php

namespace Tests\Unit\Xml3176\Checker;

use App\Models\BHYT\Xml3176Xml1;
use App\Services\Xml3176Xml1Checker;
use Illuminate\Support\Facades\DB;
use Tests\Support\Xml3176RuleTestSupport;
use Tests\TestCase;

/**
 * Quy tac bo sung theo tep "quy tac doi tuong.xlsx": ma 1.1, 3.6, 1.17.
 * Spec: docs/superpowers/specs/2026-09-15-xml3176-quy-tac-doi-tuong-bo-sung-design.md muc 7.
 */
class Xml3176Xml1DoiTuongKcbBoSungTest extends TestCase
{
    use Xml3176RuleTestSupport;

    protected function setUp()
    {
        parent::setUp();
        $this->bootXml3176Sqlite([
            '2026_01_09_152817_create_xml3176_xml1s_table.php',
            '2026_09_15_100000_create_benh_pl1_cap_chuyen_sau_table.php',
        ]);
    }

    private function codes(array $ghiDe): array
    {
        $dong = new Xml3176Xml1(array_merge([
            'ma_lk' => 'A', 'stt' => 1,
            'ma_the_bhyt' => 'DN4010112345678',
            'ma_cskcb' => '01929',
            'ma_dkbd' => '01929',
            'ma_loai_kcb' => '03',
            'ma_khuvuc' => 'K1',
            'ngay_sinh' => '198001010000',
            'ngay_vao' => '202609150800',
        ], $ghiDe));

        return $this->errorCodes($this->invokePrivate(
            $this->makeChecker(Xml3176Xml1Checker::class), 'checkDoiTuongKcb', $dong
        ));
    }

    private function napPl1()
    {
        DB::table('benh_pl1_cap_chuyen_sau')->insert([
            ['stt' => 22, 'ma_icd' => 'C50', 'loai' => 'bao_gom', 'tuoi_duoi' => 18, 'is_active' => 1],
            ['stt' => 62, 'ma_icd' => 'Z94', 'loai' => 'bao_gom', 'tuoi_duoi' => null, 'is_active' => 1],
            ['stt' => 99, 'ma_icd' => 'G44', 'loai' => 'bao_gom', 'tuoi_duoi' => null, 'is_active' => 0],
        ]);
    }

    // ─── 1.1 ────────────────────────────────────────────────────────────────

    /** @test */
    public function ma_11_dkbd_bang_cskcb_thi_sach()
    {
        $this->assertNotContains('XML1_DOI_TUONG_KCB_DKBD_KHAC_CSKCB',
            $this->codes(['ma_doituong_kcb' => '1.1']));
    }

    /** @test */
    public function ma_11_co_mot_ma_dkbd_khac_cskcb_thi_loi()
    {
        $this->assertContains('XML1_DOI_TUONG_KCB_DKBD_KHAC_CSKCB',
            $this->codes(['ma_doituong_kcb' => '1.1', 'ma_dkbd' => '36001']));

        // Nguoi dung chot nghia CHAT: doi the giua dot sang noi DKBD khac cung bao.
        $this->assertContains('XML1_DOI_TUONG_KCB_DKBD_KHAC_CSKCB',
            $this->codes(['ma_doituong_kcb' => '1.1', 'ma_dkbd' => '01929;37470']));

        $this->assertNotContains('XML1_DOI_TUONG_KCB_DKBD_KHAC_CSKCB',
            $this->codes(['ma_doituong_kcb' => '1.1', 'ma_dkbd' => '01929; 01929']));
    }

    /** @test */
    public function ma_11_thieu_can_cu_thi_im_lang()
    {
        $this->assertNotContains('XML1_DOI_TUONG_KCB_DKBD_KHAC_CSKCB',
            $this->codes(['ma_doituong_kcb' => '1.1', 'ma_dkbd' => '']));
        $this->assertNotContains('XML1_DOI_TUONG_KCB_DKBD_KHAC_CSKCB',
            $this->codes(['ma_doituong_kcb' => '1.1', 'ma_dkbd' => '36001', 'ma_cskcb' => '']));
    }

    /** @test */
    public function ma_khac_11_khong_kiem_dkbd_bang_cskcb()
    {
        foreach (['1.2', '1.3', '1.5', '2'] as $ma) {
            $this->assertNotContains('XML1_DOI_TUONG_KCB_DKBD_KHAC_CSKCB',
                $this->codes(['ma_doituong_kcb' => $ma, 'ma_dkbd' => '36001']), "ma $ma");
        }
    }

    // ─── 3.6 ────────────────────────────────────────────────────────────────

    /** @test */
    public function ma_36_thieu_ma_khuvuc_thi_loi()
    {
        $this->assertContains('XML1_DOI_TUONG_KCB_THIEU_MA_KHUVUC',
            $this->codes(['ma_doituong_kcb' => '3.6', 'ma_khuvuc' => '']));
        $this->assertContains('XML1_DOI_TUONG_KCB_THIEU_MA_KHUVUC',
            $this->codes(['ma_doituong_kcb' => '3.6', 'ma_khuvuc' => null]));
        $this->assertNotContains('XML1_DOI_TUONG_KCB_THIEU_MA_KHUVUC',
            $this->codes(['ma_doituong_kcb' => '3.6', 'ma_khuvuc' => 'K2']));
    }

    /** @test */
    public function ma_khac_36_khong_doi_ma_khuvuc()
    {
        foreach (['1.1', '3.1', '3.2', '1.17'] as $ma) {
            $this->assertNotContains('XML1_DOI_TUONG_KCB_THIEU_MA_KHUVUC',
                $this->codes(['ma_doituong_kcb' => $ma, 'ma_khuvuc' => '']), "ma $ma");
        }
    }

    // ─── 1.17 ───────────────────────────────────────────────────────────────

    /** @test */
    public function ma_117_danh_muc_rong_thi_im_lang()
    {
        $this->assertNotContains('XML1_DOI_TUONG_KCB_BENH_NGOAI_PL1',
            $this->codes(['ma_doituong_kcb' => '1.17', 'ma_benh_chinh' => 'G44.0']));
    }

    /** @test */
    public function ma_117_benh_trong_danh_muc_thi_sach()
    {
        $this->napPl1();

        $this->assertNotContains('XML1_DOI_TUONG_KCB_BENH_NGOAI_PL1',
            $this->codes(['ma_doituong_kcb' => '1.17', 'ma_benh_chinh' => 'Z94.0']));
        $this->assertNotContains('XML1_DOI_TUONG_KCB_BENH_NGOAI_PL1',
            $this->codes(['ma_doituong_kcb' => '1.17', 'ma_benh_chinh' => 'C50.9', 'ngay_sinh' => '201501010000']));
    }

    /** @test */
    public function ma_117_benh_ngoai_danh_muc_hoac_dong_da_tat_thi_loi()
    {
        $this->napPl1();

        // G44 co trong bang nhung is_active = 0 - khong duoc tinh.
        $this->assertContains('XML1_DOI_TUONG_KCB_BENH_NGOAI_PL1',
            $this->codes(['ma_doituong_kcb' => '1.17', 'ma_benh_chinh' => 'G44.0']));
    }

    /** @test */
    public function ma_117_sai_tuoi_thi_loi_va_mo_ta_neu_ro_dong()
    {
        $this->napPl1();

        $dong = new Xml3176Xml1([
            'ma_lk' => 'A', 'stt' => 1, 'ma_doituong_kcb' => '1.17', 'ma_benh_chinh' => 'C50.9',
            'ngay_sinh' => '198001010000', 'ngay_vao' => '202609150800',
            'ma_cskcb' => '01929', 'ma_dkbd' => '01929', 'ma_the_bhyt' => 'DN4010112345678',
        ]);

        $loi = collect($this->invokePrivate(
            $this->makeChecker(Xml3176Xml1Checker::class), 'checkDoiTuongKcb', $dong
        ))->firstWhere('error_code', 'XML1_DOI_TUONG_KCB_BENH_NGOAI_PL1');

        $this->assertNotNull($loi);
        $this->assertContains('C50.9', $loi->description);
        $this->assertContains('dòng 22', $loi->description);
        $this->assertContains('46 tuổi', $loi->description);
    }

    /** @test */
    public function ma_117_thieu_ma_benh_chinh_thi_im_lang()
    {
        $this->napPl1();

        $this->assertNotContains('XML1_DOI_TUONG_KCB_BENH_NGOAI_PL1',
            $this->codes(['ma_doituong_kcb' => '1.17', 'ma_benh_chinh' => '']));
    }

    /** @test */
    public function ma_khac_117_khong_kiem_pl1()
    {
        $this->napPl1();

        foreach (['1.16', '1.1', '3.6'] as $ma) {
            $this->assertNotContains('XML1_DOI_TUONG_KCB_BENH_NGOAI_PL1',
                $this->codes(['ma_doituong_kcb' => $ma, 'ma_benh_chinh' => 'G44.0']), "ma $ma");
        }
    }
}
```

- [ ] **Step 2: Chạy test, xác nhận hỏng**

Run: `./vendor/bin/phpunit tests/Unit/Xml3176/Checker/Xml3176Xml1DoiTuongKcbBoSungTest.php`
Expected: các test `assertContains` của ba mã lỗi mới hỏng; các test `assertNotContains` có thể đã qua.

- [ ] **Step 3: Thêm ba thuộc tính vào `config/doi_tuong_kcb.php`**

Trong docblock đầu tệp, ngay sau dòng ` *   can_giay_chuyen_tuyen  - phai ghi so phieu o truong GIAY_CHUYEN_TUYEN`, thêm:

```php
 *   dkbd_phai_la_cskcb     - moi ma trong MA_DKBD phai bang MA_CSKCB
 *   can_ma_khuvuc          - MA_KHUVUC khong duoc de trong
 *   benh_pl1               - MA_BENH_CHINH phai thuoc danh muc benh Phu luc I TT 01/2025
```

Sửa ba dòng:

```php
    '1.1'  => ['ten' => 'Đến KCB đúng cơ sở nơi đăng ký KCB BHYT ban đầu', 'dung_dkbd' => true,
               'dkbd_phai_la_cskcb' => true],
```

```php
    '1.17' => ['ten' => 'Tự đến KCB tại cơ sở cấp chuyên sâu với bệnh thuộc Phụ lục I TT 01/2025', 'tu_den' => true,
               'benh_pl1' => true],
```

```php
    '3.6'  => ['ten' => 'Dân tộc thiểu số, hộ nghèo vùng khó khăn đến KCB nội trú tại cơ sở cấp chuyên sâu', 'tu_den' => true,
               'can_ma_khuvuc' => true],
```

Giữ nguyên tên và các thuộc tính cũ; chỉ thêm thuộc tính mới.

- [ ] **Step 4: Thêm `danhMucBenhPl1()` vào `CommonValidationService`**

Kiểm `grep -n "BenhPl1CapChuyenSau" app/Services/CommonValidationService.php`. Nếu chưa có, thêm dòng `use App\Models\BHYT\BenhPl1CapChuyenSau;` ngay sau `use App\Models\BHYT\DvktCanMaMay;`.

Thêm phương thức ngay sau `coDanhMucDvktCanMaMay()`:

```php
    /**
     * Cac dong dang dung cua danh muc benh Phu luc I Thong tu 01/2025/TT-BYT.
     *
     * KHONG luu dem: queue worker song lau, dem se giu danh muc cu sau khi nguoi dung nap lai.
     * Chi ho so ma doi tuong 1.17 moi goi ham nay nen mot truy van moi ho so la khong dang ke.
     *
     * @return array cac dong ['stt' => int, 'ma_icd' => string, 'loai' => string, 'tuoi_duoi' => int|null]
     */
    public function danhMucBenhPl1()
    {
        return BenhPl1CapChuyenSau::where('is_active', true)
            ->get(['stt', 'ma_icd', 'loai', 'tuoi_duoi'])
            ->map(function ($d) {
                return [
                    'stt'       => (int) $d->stt,
                    'ma_icd'    => (string) $d->ma_icd,
                    'loai'      => (string) $d->loai,
                    'tuoi_duoi' => $d->tuoi_duoi === null ? null : (int) $d->tuoi_duoi,
                ];
            })
            ->all();
    }
```

- [ ] **Step 5: Thêm ba quy tắc vào `Xml3176Xml1Checker::checkDoiTuongKcb()`**

Kiểm `grep -n "^use " app/Services/Xml3176Xml1Checker.php`. Tệp đã có `DanhSachPhanCachParser`, `DoiTuongKcbCatalog`, `Xml3176DateHelper`. Chỉ thêm `use App\Services\Xml3176\Support\BenhPl1Matcher;` nếu chưa có, đặt ngay sau dòng `use App\Models\BHYT\Xml3176Xml1;`.

Tìm dòng chú thích `        // Tu den thi khong the co co so chuyen di.` trong `checkDoiTuongKcb()`, chèn **ngay trước** dòng đó:

```php
        // Ma 1.1 (den dung noi DKBD): MOI ma trong MA_DKBD phai bang MA_CSKCB. Nguoi dung chot
        // nghia CHAT - ho so doi the giua dot sang noi DKBD khac cung bi bao (muc canh bao).
        // MA_DKBD hoac MA_CSKCB rong thi im lang: thieu can cu.
        if (DoiTuongKcbCatalog::thuocTinh($ma, $danhMuc, 'dkbd_phai_la_cskcb', false)) {
            $maCoSoKcb = trim((string) $data->ma_cskcb);
            $dkbdLech = array_values(array_filter(
                DanhSachPhanCachParser::tach($data->ma_dkbd),
                function ($m) use ($maCoSoKcb) {
                    return $m !== $maCoSoKcb;
                }
            ));

            if ($maCoSoKcb !== '' && !empty($dkbdLech)) {
                $errorCode = $this->generateErrorCode('DOI_TUONG_KCB_DKBD_KHAC_CSKCB');
                $errors->push((object)[
                    'error_code' => $errorCode,
                    'error_name' => 'Đến đúng nơi đăng ký ban đầu nhưng MA_DKBD khác MA_CSKCB',
                    'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                    'description' => 'Mã đối tượng ' . $ma . ' (' . DoiTuongKcbCatalog::thuocTinh($ma, $danhMuc, 'ten')
                        . ') nhưng MA_DKBD có mã khác MA_CSKCB ' . $maCoSoKcb . ': ' . implode(', ', $dkbdLech),
                ]);
            }
        }

        // Ma 3.6: MA_KHUVUC bat buoc. Gia tri khac K1/K2/K3 van do ADMIN_INFO_ERROR_MA_KHUVUC lo.
        if (DoiTuongKcbCatalog::thuocTinh($ma, $danhMuc, 'can_ma_khuvuc', false)
            && trim((string) $data->ma_khuvuc) === '') {
            $errorCode = $this->generateErrorCode('DOI_TUONG_KCB_THIEU_MA_KHUVUC');
            $errors->push((object)[
                'error_code' => $errorCode,
                'error_name' => 'Thiếu mã khu vực',
                'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                'description' => 'Mã đối tượng ' . $ma . ' (' . DoiTuongKcbCatalog::thuocTinh($ma, $danhMuc, 'ten')
                    . ') nhưng MA_KHUVUC để trống',
            ]);
        }

        // Ma 1.17: MA_BENH_CHINH phai thuoc danh muc benh Phu luc I Thong tu 01/2025/TT-BYT.
        // Chua nap danh muc hoac thieu ma benh chinh thi im lang - khong co can cu ket luan.
        if (DoiTuongKcbCatalog::thuocTinh($ma, $danhMuc, 'benh_pl1', false)) {
            $maBenh = trim((string) $data->ma_benh_chinh);
            $dmPl1 = $maBenh === '' ? [] : $this->commonValidationService->danhMucBenhPl1();

            if (!empty($dmPl1)) {
                $tuoi = Xml3176DateHelper::tuoiDuNam($data->ngay_sinh, $data->ngay_vao);
                $kq = BenhPl1Matcher::kiemTra($maBenh, $dmPl1, $tuoi);

                if (!$kq['khop']) {
                    $moTa = 'Mã đối tượng ' . $ma . ' nhưng MA_BENH_CHINH = ' . $maBenh
                        . ' không thuộc Phụ lục I Thông tư 01/2025/TT-BYT';

                    if (!empty($kq['stt_sai_tuoi'])) {
                        $moTa .= ' (thuộc dòng ' . implode(', ', $kq['stt_sai_tuoi'])
                            . ' nhưng người bệnh ' . $tuoi . ' tuổi, dòng này chỉ áp dụng người dưới 18 tuổi)';
                    }

                    $errorCode = $this->generateErrorCode('DOI_TUONG_KCB_BENH_NGOAI_PL1');
                    $errors->push((object)[
                        'error_code' => $errorCode,
                        'error_name' => 'Tự đến cơ sở cấp chuyên sâu nhưng bệnh không thuộc Phụ lục I TT 01/2025',
                        'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                        'description' => $moTa,
                    ]);
                }
            }
        }

```

- [ ] **Step 6: Chạy test, xác nhận qua**

Run: `./vendor/bin/phpunit tests/Unit/Xml3176/Checker/Xml3176Xml1DoiTuongKcbBoSungTest.php`
Expected: `OK (13 tests, …)`

Nếu `ma_117_sai_tuoi_thi_loi_va_mo_ta_neu_ro_dong` hỏng ở `46 tuổi`: ngày sinh `19800101` tới ngày vào `20260915` là 46 tuổi đủ. Sửa mã, không sửa test.

- [ ] **Step 7: Chạy các test đang có của checker và danh mục mã đối tượng**

Run: `./vendor/bin/phpunit tests/Unit/Xml3176`
Expected: `OK`. `Xml3176Xml1DoiTuongKcbTest` hiện có phải vẫn xanh — test đó dùng mã 1.1 và 1.17 nhưng không khẳng định danh sách lỗi rỗng cho hai mã này, và không có `ma_benh_chinh` nên quy tắc 1.17 không truy vấn bảng.

- [ ] **Step 8: Commit**

```bash
git add config/doi_tuong_kcb.php app/Services/CommonValidationService.php app/Services/Xml3176Xml1Checker.php tests/Unit/Xml3176/Checker/Xml3176Xml1DoiTuongKcbBoSungTest.php
git commit -m "feat(xml3176): quy tac ma doi tuong 1.1 DKBD bang CSKCB, 3.6 thieu ma khu vuc, 1.17 benh ngoai PL1

Co-Authored-By: Claude Opus 5 <noreply@anthropic.com>"
```

---

### Task 5: Nạp 3 mã lỗi vào danh mục mã lỗi

**Files:**
- Modify: `database/seeds/Xml3176ErrorCatalogDoiTuongKcbSeeder.php`
- Create: `database/migrations/2026_09_15_110000_nap_ma_loi_doi_tuong_kcb_bo_sung.php`
- Modify: `tests/Unit/Xml3176/Xml3176ErrorCatalogDoiTuongKcbSeederTest.php`

**Interfaces:**
- Consumes: ba mã lỗi của Task 4.
- Produces: 13 dòng mã lỗi nhóm mã đối tượng trong `xml3176_error_catalogs`, cả 13 `critical_error = false`.

- [ ] **Step 1: Sửa test seeder (hỏng trước)**

Trong `tests/Unit/Xml3176/Xml3176ErrorCatalogDoiTuongKcbSeederTest.php`, hàm `seeder_khai_du_10_ma_loi`:
- Đổi tên hàm thành `seeder_khai_du_13_ma_loi`.
- Thêm ba phần tử vào mảng `$ma`, ngay sau `'XML1_DOI_TUONG_KCB_THIEU_GIAY_CHUYEN_TUYEN',`:

```php
            'XML1_DOI_TUONG_KCB_DKBD_KHAC_CSKCB',
            'XML1_DOI_TUONG_KCB_THIEU_MA_KHUVUC',
            'XML1_DOI_TUONG_KCB_BENH_NGOAI_PL1',
```

- Đổi `$this->assertCount(10, $ma);` thành `$this->assertCount(13, $ma);`.

Thêm test vào cuối lớp:

```php
    /** @test */
    public function migration_bo_sung_goi_seeder()
    {
        $files = glob(database_path('migrations/*nap_ma_loi_doi_tuong_kcb_bo_sung.php'));
        $this->assertCount(1, $files, 'Khong tim thay migration nap ma loi bo sung');

        $src = file_get_contents($files[0]);
        $this->assertContains('Xml3176ErrorCatalogDoiTuongKcbSeeder', $src);
        $this->assertNotContains('->change()', $src, 'Du an khong co doctrine/dbal');
    }
```

- [ ] **Step 2: Chạy test, xác nhận hỏng**

Run: `./vendor/bin/phpunit tests/Unit/Xml3176/Xml3176ErrorCatalogDoiTuongKcbSeederTest.php`
Expected: `seeder_khai_du_13_ma_loi` hỏng (thiếu mã), `migration_bo_sung_goi_seeder` hỏng (không tìm thấy migration).

- [ ] **Step 3: Sửa seeder**

Trong `database/seeds/Xml3176ErrorCatalogDoiTuongKcbSeeder.php`:
- Docblock: đổi `Nap 10 error_code` thành `Nap 13 error_code`, và `cho ca 10 ma` thành `cho ca 13 ma`.
- Trong mảng `$danhMuc`, ngay sau dòng `['XML1', 'XML1_DOI_TUONG_KCB_THIEU_GIAY_CHUYEN_TUYEN', …],`, thêm:

```php
            ['XML1', 'XML1_DOI_TUONG_KCB_DKBD_KHAC_CSKCB', 'Đến đúng nơi đăng ký ban đầu nhưng MA_DKBD khác MA_CSKCB', 'Mã 1.1: mọi mã trong MA_DKBD phải bằng MA_CSKCB'],
            ['XML1', 'XML1_DOI_TUONG_KCB_THIEU_MA_KHUVUC', 'Thiếu mã khu vực', 'Mã 3.6: MA_KHUVUC không được để trống'],
            ['XML1', 'XML1_DOI_TUONG_KCB_BENH_NGOAI_PL1', 'Tự đến cơ sở cấp chuyên sâu nhưng bệnh không thuộc Phụ lục I TT 01/2025', 'Mã 1.17: MA_BENH_CHINH phải thuộc danh mục bệnh Phụ lục I Thông tư 01/2025/TT-BYT, kể cả điều kiện người dưới 18 tuổi'],
```

- [ ] **Step 4: Tạo migration**

`database/migrations/2026_09_15_110000_nap_ma_loi_doi_tuong_kcb_bo_sung.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;

/**
 * Nap them 3 ma loi ma doi tuong KCB bo sung (1.1, 3.6, 1.17) vao danh muc ma loi.
 *
 * Cac migration nap danh muc truoc do da chay tren moi truong da trien khai nen khong chay
 * lai; can migration rieng de dong moi den duoc CSDL that.
 *
 * BAT BUOC chay TRUOC khi quy tac no lan dau: thieu dong danh muc thi
 * getCriticalErrorStatus() tra mac dinh TRUE va chan xuat XML.
 *
 * Seeder idempotent (updateOrCreate) nen chay lai ca 13 dong la an toan.
 */
class NapMaLoiDoiTuongKcbBoSung extends Migration
{
    public function up()
    {
        require_once database_path('seeds/Xml3176ErrorCatalogDoiTuongKcbSeeder.php');

        (new Xml3176ErrorCatalogDoiTuongKcbSeeder())->run();
    }

    public function down()
    {
        // Co Y KHONG lui: xoa dong danh muc se lam getCriticalErrorStatus() quay ve mac
        // dinh TRUE va chan xuat XML.
    }
}
```

- [ ] **Step 5: Chạy test, xác nhận qua**

Run: `./vendor/bin/phpunit tests/Unit/Xml3176/Xml3176ErrorCatalogDoiTuongKcbSeederTest.php`
Expected: `OK (5 tests, …)`

- [ ] **Step 6: Chạy migration trên CSDL dev và kiểm dòng danh mục**

Run: `php artisan migrate`
Expected: `Migrated: 2026_09_15_110000_nap_ma_loi_doi_tuong_kcb_bo_sung`. Nếu lệnh liệt kê migration khác đang treo ngoài migration này, **dừng** và báo.

Run:

```bash
php -r "require 'vendor/autoload.php'; \$app = require 'bootstrap/app.php'; \$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap(); foreach (DB::table('xml3176_error_catalogs')->whereIn('error_code', ['XML1_DOI_TUONG_KCB_DKBD_KHAC_CSKCB','XML1_DOI_TUONG_KCB_THIEU_MA_KHUVUC','XML1_DOI_TUONG_KCB_BENH_NGOAI_PL1'])->get(['error_code','critical_error','is_check']) as \$r) echo \$r->error_code, ' critical=', \$r->critical_error, ' is_check=', \$r->is_check, PHP_EOL;"
```

Expected: đúng 3 dòng, mỗi dòng `critical=0 is_check=1`.

- [ ] **Step 7: Commit**

```bash
git add database/seeds/Xml3176ErrorCatalogDoiTuongKcbSeeder.php database/migrations/2026_09_15_110000_nap_ma_loi_doi_tuong_kcb_bo_sung.php tests/Unit/Xml3176/Xml3176ErrorCatalogDoiTuongKcbSeederTest.php
git commit -m "feat(xml3176): nap 3 ma loi ma doi tuong bo sung, muc canh bao

Co-Authored-By: Claude Opus 5 <noreply@anthropic.com>"
```

---

### Task 6: Kiểm trên dữ liệu thật

Không sửa mã ứng dụng, không commit. Mục đích: xác nhận tệp mẫu nhập được qua đúng đường `CatalogImportService` và quy tắc cho ra số lỗi dự kiến trên hồ sơ thật (spec §8).

**Files:** không có.

**Interfaces:**
- Consumes: tất cả Task 1–5; tệp `docs/0000 - Danh muc/PL1_TT01_2025.xlsx`.

- [ ] **Step 1: Nhập tệp mẫu vào CSDL dev qua `CatalogImportService`**

Đây là thao tác ghi duy nhất được phép ngoài migration, và chỉ vào bảng `benh_pl1_cap_chuyen_sau`.

```bash
php -r "require 'vendor/autoload.php'; \$app = require 'bootstrap/app.php'; \$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap(); app(App\Services\CatalogImportService::class)->import(base_path('docs/0000 - Danh muc/PL1_TT01_2025.xlsx')); echo 'tong=', DB::table('benh_pl1_cap_chuyen_sau')->count(), ' dang_dung=', DB::table('benh_pl1_cap_chuyen_sau')->where('is_active', 1)->count(), ' tru=', DB::table('benh_pl1_cap_chuyen_sau')->where('loai', 'tru')->count(), ' co_tuoi=', DB::table('benh_pl1_cap_chuyen_sau')->whereNotNull('tuoi_duoi')->count(), PHP_EOL;"
```

Expected: `tong=186 dang_dung=186 tru=4 co_tuoi=110` (110 = 98 dòng STT 22 + 1 dòng mỗi STT 30, 31, 32 + 9 dòng STT 58). Số khác thì báo kèm đầu ra.

Chạy lại lệnh một lần nữa để kiểm nhập lại thay trọn bộ: kỳ vọng vẫn `tong=186 dang_dung=186`, không nhân đôi dòng.

- [ ] **Step 2: Chạy checker trên hồ sơ thật mã 1.17, 1.1, 3.6**

```bash
php -r "require 'vendor/autoload.php'; \$app = require 'bootstrap/app.php'; \$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap(); \$c = app(App\Services\Xml3176Xml1Checker::class); \$m = new ReflectionMethod(\$c, 'checkDoiTuongKcb'); \$m->setAccessible(true); \$dem = []; foreach (App\Models\BHYT\Xml3176Xml1::whereIn('ma_doituong_kcb', ['1.1','3.6','1.17'])->get() as \$h) { foreach (\$m->invoke(\$c, \$h) as \$e) { if (in_array(\$e->error_code, ['XML1_DOI_TUONG_KCB_DKBD_KHAC_CSKCB','XML1_DOI_TUONG_KCB_THIEU_MA_KHUVUC','XML1_DOI_TUONG_KCB_BENH_NGOAI_PL1'])) { \$dem[\$e->error_code] = (isset(\$dem[\$e->error_code]) ? \$dem[\$e->error_code] : 0) + 1; echo \$h->ma_lk, ' ', \$h->ma_doituong_kcb, ' ', \$e->error_code, ' | ', \$e->description, PHP_EOL; } } } print_r(\$dem);"
```

Expected (dữ liệu 15/09/2026): đúng 3 dòng `BENH_NGOAI_PL1` cho các mã bệnh `G44.0`, `N18.5`, `B44.9`; 0 dòng `DKBD_KHAC_CSKCB`; 0 dòng `THIEU_MA_KHUVUC`. Lệnh này **không** ghi lỗi vào CSDL — chỉ gọi hàm kiểm. Ghi nguyên văn đầu ra vào báo cáo; số khác vì dữ liệu đã nạp lại thì ghi số thật và giải thích.

- [ ] **Step 3: Chạy toàn bộ test**

Run: `./vendor/bin/phpunit`
Expected: đúng **1** thất bại — `Tests\Unit\Ctdt\CtdtCauHinhTest::gui_len_cong_mac_dinh_tat` (có chủ đích, không sửa). Mọi thất bại khác phải báo kèm đầu ra.

Báo cáo task **bắt buộc** có: đầu ra Step 1 (hai lần nhập), đầu ra Step 2, dòng tổng kết Step 3.
