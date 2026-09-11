# Quy tắc mã đối tượng KCB — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Thêm 10 quy tắc kiểm `MA_DOITUONG_KCB` theo danh mục mã đối tượng của Bộ Y tế, và vá một cấu hình khớp tiền tố đang làm hai quy tắc sẵn có hiểu sai ba mã `3.2`/`3.3`/`3.6`.

**Architecture:** Danh mục 27 mã khai trong một tệp config mới; một helper thuần đọc danh mục (nhận mảng truyền vào, không tự gọi `config()`); 7 quy tắc thêm vào `Xml3176Xml1Checker` (chỉ cần trường của XML1), 3 quy tắc thêm vào `Xml3176CompleteChecker` (cần `MUC_HUONG` của từng dòng XML2/XML3, mà bảng `xml3176_xml1s` không có cột đó); một migration gọi seeder nạp 10 mã lỗi.

**Tech Stack:** Laravel 5.5, PHP 7.4, PHPUnit 6.5, sqlite in-memory cho test chạm CSDL.

**Spec:** `docs/superpowers/specs/2026-09-11-xml3176-quy-tac-ma-doi-tuong-kcb-design.md`

## Global Constraints

- **CẤM `RefreshDatabase`** trong mọi test — nó xoá sạch CSDL dev `qlbv`. Dùng `Tests\Support\Xml3176RuleTestSupport::bootXml3176Sqlite(array $tenFileMigration)`.
- PHPUnit 6.5: `protected function setUp()` — **không** có kiểu trả về `: void`.
- Mọi đối tượng lỗi có đúng bốn khoá: `error_code`, `error_name`, `critical_error`, `description`. `error_code` luôn sinh bằng `$this->generateErrorCode($key)`; `critical_error` luôn lấy bằng `$this->xmlErrorService->getCriticalErrorStatus($errorCode)`.
- Helper trong `app/Services/Xml3176/Support/` là **thuần**: không chạm DB, model, config, `now()`. Danh mục do người gọi đọc từ config rồi **truyền vào**.
- **KHÔNG khớp tiền tố trên mã đối tượng.** Mã dùng dạng phân cấp có dấu chấm; khớp tiền tố chính là khiếm khuyết mà Task 2 đi vá. Luôn `in_array($ma, $ds, true)` hoặc so sánh `===`.
- **KHÔNG có `doctrine/dbal`** → migration không được dùng `->change()`.
- Chú thích mã nguồn viết **không dấu**; chuỗi `error_name`/`description` hiển thị cho người dùng thì **có dấu**.
- Danh mục có **27 mã**, không phải 28. Văn bản nguồn nhảy qua STT 22; **không có mã `7.1`** — đừng tự thêm.
- Sau mỗi task: `./vendor/bin/phpunit tests/Unit/Xml3176` phải xanh trước khi commit.

---

### Task 1: Danh mục 27 mã + helper thuần `DoiTuongKcbCatalog`

**Files:**
- Create: `config/doi_tuong_kcb.php`
- Create: `app/Services/Xml3176/Support/DoiTuongKcbCatalog.php`
- Test: `tests/Unit/Xml3176/Support/DoiTuongKcbCatalogTest.php`

**Interfaces:**
- Consumes: không có.
- Produces: `DoiTuongKcbCatalog::chuanHoa($ma): string`, `coTrongDanhMuc($ma, array $danhMuc): bool`, `thuocTinh($ma, array $danhMuc, string $khoa, $macDinh = null)`, `laTuDen($ma, array $danhMuc): bool` — tất cả `public static`. Khoá config `doi_tuong_kcb` trả mảng 27 mục.

- [ ] **Step 1: Viết test đỏ**

```php
<?php

namespace Tests\Unit\Xml3176\Support;

use App\Services\Xml3176\Support\DoiTuongKcbCatalog;
use Tests\TestCase;

class DoiTuongKcbCatalogTest extends TestCase
{
    private function danhMuc(): array
    {
        return [
            '1.1' => ['ten' => 'Dung noi dang ky ban dau', 'dung_dkbd' => true],
            '1.3' => ['ten' => 'Co phieu chuyen', 'can_noi_di' => true],
            '3.1' => ['ten' => 'Tu den chuyen sau', 'tu_den' => true, 'ngoai_tru_khong_huong' => true],
            '3.6' => ['ten' => 'Dan toc thieu so noi tru', 'tu_den' => true],
            '9'   => ['ten' => 'Khong KCB BHYT', 'khong_bhyt' => true],
        ];
    }

    /** @test */
    public function nhan_dien_ma_co_trong_danh_muc()
    {
        $dm = $this->danhMuc();
        $this->assertTrue(DoiTuongKcbCatalog::coTrongDanhMuc('1.1', $dm));
        $this->assertTrue(DoiTuongKcbCatalog::coTrongDanhMuc('9', $dm));
        $this->assertFalse(DoiTuongKcbCatalog::coTrongDanhMuc('1.9', $dm));
        $this->assertFalse(DoiTuongKcbCatalog::coTrongDanhMuc('4', $dm));
    }

    /** @test */
    public function KHONG_duoc_khop_tien_to()
    {
        // Day la ca khoa bai hoc cua ca dot: ma dang phan cap co dau cham, khop tien to
        // lam '3' nuot ca 3.1/3.2/3.6 - dung khiem khuyet ma Task 2 di va.
        $dm = $this->danhMuc();
        $this->assertFalse(DoiTuongKcbCatalog::coTrongDanhMuc('3', $dm));
        $this->assertFalse(DoiTuongKcbCatalog::coTrongDanhMuc('3.10', $dm));
        $this->assertFalse(DoiTuongKcbCatalog::laTuDen('3', $dm));
    }

    /** @test */
    public function doc_thuoc_tinh_co_va_khong_co()
    {
        $dm = $this->danhMuc();
        $this->assertTrue(DoiTuongKcbCatalog::thuocTinh('1.3', $dm, 'can_noi_di', false));
        $this->assertFalse(DoiTuongKcbCatalog::thuocTinh('1.1', $dm, 'can_noi_di', false));
        $this->assertFalse(DoiTuongKcbCatalog::thuocTinh('KHONG-CO', $dm, 'can_noi_di', false));
        $this->assertSame('Khong KCB BHYT', DoiTuongKcbCatalog::thuocTinh('9', $dm, 'ten'));
        $this->assertNull(DoiTuongKcbCatalog::thuocTinh('9', $dm, 'khong_ton_tai'));
    }

    /** @test */
    public function la_tu_den()
    {
        $dm = $this->danhMuc();
        $this->assertTrue(DoiTuongKcbCatalog::laTuDen('3.1', $dm));
        $this->assertTrue(DoiTuongKcbCatalog::laTuDen('3.6', $dm));
        $this->assertFalse(DoiTuongKcbCatalog::laTuDen('1.3', $dm));
        $this->assertFalse(DoiTuongKcbCatalog::laTuDen('9', $dm));
    }

    /** @test */
    public function chuan_hoa_chi_trim_khong_cat_gi()
    {
        $this->assertSame('3.1', DoiTuongKcbCatalog::chuanHoa('  3.1  '));
        $this->assertSame('1.17', DoiTuongKcbCatalog::chuanHoa('1.17'));
        $this->assertSame('', DoiTuongKcbCatalog::chuanHoa(null));
        $this->assertSame('', DoiTuongKcbCatalog::chuanHoa('   '));
    }

    /** @test */
    public function danh_muc_that_co_dung_27_ma()
    {
        // Van ban nguon nhay qua STT 22 nen chi co 27 ma, KHONG co ma '7.1'.
        $that = config('doi_tuong_kcb');
        $this->assertCount(27, $that);
        $this->assertArrayNotHasKey('7.1', $that);
        foreach (['1.1', '1.7', '1.11', '1.18', '2', '3.1', '3.6', '7', '7.2', '7.4', '8', '9', '10'] as $ma) {
            $this->assertArrayHasKey($ma, $that, "Thieu ma $ma");
        }
        foreach ($that as $ma => $muc) {
            $this->assertArrayHasKey('ten', $muc, "Muc '$ma' thieu khoa 'ten'");
        }
    }

    /** @test */
    public function danh_muc_that_khai_dung_cac_thuoc_tinh_quy_tac_dua_vao()
    {
        $that = config('doi_tuong_kcb');
        $this->assertTrue(DoiTuongKcbCatalog::thuocTinh('1.3', $that, 'can_noi_di', false));
        $this->assertTrue(DoiTuongKcbCatalog::thuocTinh('9', $that, 'khong_bhyt', false));
        $this->assertTrue(DoiTuongKcbCatalog::thuocTinh('3.1', $that, 'ngoai_tru_khong_huong', false));
        $this->assertSame(100, DoiTuongKcbCatalog::thuocTinh('1.2', $that, 'muc_huong_co_dinh'));
        $this->assertTrue(DoiTuongKcbCatalog::thuocTinh('7.2', $that, 'linh_thuoc_khong_kham', false));
        $this->assertTrue(DoiTuongKcbCatalog::laTuDen('1.15', $that));
        $this->assertFalse(DoiTuongKcbCatalog::laTuDen('1.5', $that));
    }
}
```

- [ ] **Step 2: Chạy test, xác nhận đỏ**

Run: `./vendor/bin/phpunit tests/Unit/Xml3176/Support/DoiTuongKcbCatalogTest.php`
Expected: FAIL — `Class 'App\Services\Xml3176\Support\DoiTuongKcbCatalog' not found`

- [ ] **Step 3: Viết helper**

```php
<?php

namespace App\Services\Xml3176\Support;

/**
 * Tra danh muc ma doi tuong den kham benh, chua benh (Phu luc 1 do Bo Y te ban hanh).
 *
 * Danh muc do NGUOI GOI doc tu config('doi_tuong_kcb') roi truyen vao, de lop nay giu
 * tinh thuan - cung loi TienTeCalculator da dung.
 *
 * KHONG khop tien to. Ma doi tuong dung dang phan cap co dau cham ('1.17', '3.1'), nen
 * khop tien to lam '3' nuot ca 3.1, 3.2, 3.3, 3.6 - dung khiem khuyet dang ton tai
 * trong config xml1.ma_doituong_kcb_trai_tuyen ma dot nay di va.
 *
 * Helper thuan - khong cham DB/model/config.
 */
class DoiTuongKcbCatalog
{
    /** Chi trim. KHONG cat hau to, KHONG khop tien to. */
    public static function chuanHoa($ma): string
    {
        return trim((string) $ma);
    }

    public static function coTrongDanhMuc($ma, array $danhMuc): bool
    {
        $ma = self::chuanHoa($ma);

        return $ma !== '' && array_key_exists($ma, $danhMuc);
    }

    /**
     * Doc mot thuoc tinh cua muc. Ma khong co trong danh muc, hoac muc khong khai thuoc
     * tinh do, deu tra $macDinh - khoa vang nghia la thuoc tinh do khong ap dung.
     */
    public static function thuocTinh($ma, array $danhMuc, string $khoa, $macDinh = null)
    {
        $ma = self::chuanHoa($ma);

        if (!array_key_exists($ma, $danhMuc) || !is_array($danhMuc[$ma])) {
            return $macDinh;
        }

        return array_key_exists($khoa, $danhMuc[$ma]) ? $danhMuc[$ma][$khoa] : $macDinh;
    }

    public static function laTuDen($ma, array $danhMuc): bool
    {
        return (bool) self::thuocTinh($ma, $danhMuc, 'tu_den', false);
    }
}
```

- [ ] **Step 4: Viết tệp danh mục**

Tạo `config/doi_tuong_kcb.php` với **đúng 27 mã**:

```php
<?php

/**
 * Danh muc ma doi tuong den kham benh, chua benh - Phu luc 1 do Bo truong Bo Y te ban
 * hanh (2025). Nguon: "1. Danh muc ma doi tuong KCB.signed.pdf".
 *
 * 27 MA, khong phai 28: so thu tu trong van ban chay 1->28 nhung NHAY QUA STT 22 (dong
 * 21 la ma '7', dong ke tiep la 23 voi ma '7.2'). Da kiem bang ca trich van ban lan
 * trich bang. KHONG tu them ma '7.1'.
 *
 * Khoa vang nghia la thuoc tinh do khong ap dung cho ma nay. Y nghia tung thuoc tinh:
 *   tu_den                 - nguoi benh tu den, khong qua chuyen co so
 *   can_noi_di             - phai co MA_NOI_DI (co so noi chuyen nguoi benh di)
 *   dung_dkbd              - den dung noi dang ky ban dau
 *   khong_bhyt             - khong KCB BHYT, khong duoc de nghi quy thanh toan
 *   muc_huong_co_dinh      - MUC_HUONG bat buoc, khong phu thuoc muc huong tren the
 *   ngoai_tru_khong_huong  - ngoai tru thi khong duoc huong BHYT
 *   muc_huong_theo_moc     - muc huong doi theo moc thoi gian
 *   linh_thuoc_khong_kham  - chi linh thuoc, khong kham benh
 *
 * Sua tep nay phai chay lai 'php artisan config:clear'.
 */
return [
    '1.1'  => ['ten' => 'Đến KCB đúng cơ sở nơi đăng ký KCB BHYT ban đầu', 'dung_dkbd' => true],
    '1.2'  => ['ten' => 'Đi KCB tại cơ sở KCB cấp ban đầu', 'muc_huong_co_dinh' => 100],
    '1.3'  => ['ten' => 'Đến KCB có phiếu chuyển cơ sở KCB', 'can_noi_di' => true],
    '1.4'  => ['ten' => 'KCB khi thay đổi nơi lưu trú, nơi cư trú'],
    '1.5'  => ['ten' => 'Đến KCB theo phiếu hẹn khám lại'],
    '1.6'  => ['ten' => 'Người đã hiến bộ phận cơ thể phải điều trị ngay sau khi hiến'],
    '1.7'  => ['ten' => 'Trẻ sơ sinh phải điều trị ngay sau khi sinh ra'],
    '1.11' => ['ten' => 'Tự đến KCB tại cơ sở KCB cấp ban đầu còn lại', 'tu_den' => true],
    '1.12' => ['ten' => 'Tự đến KCB ngoại trú tại cơ sở cấp cơ bản dưới 50 điểm', 'tu_den' => true],
    '1.13' => ['ten' => 'Tự đến KCB ngoại trú tại cơ sở cấp cơ bản 50-70 điểm', 'tu_den' => true,
               'muc_huong_theo_moc' => ['moc' => '2026-07-01', 'truoc_moc' => 0, 'tu_moc' => 50]],
    '1.14' => ['ten' => 'Tự đến KCB ngoại trú tại cơ sở cấp cơ bản trước đây là tuyến tỉnh hoặc trung ương', 'tu_den' => true,
               'muc_huong_theo_moc' => ['moc' => '2026-07-01', 'truoc_moc' => 0, 'tu_moc' => 50]],
    '1.15' => ['ten' => 'Tự đến KCB nội trú tại cơ sở KCB cấp cơ bản', 'tu_den' => true],
    '1.16' => ['ten' => 'Tự đến KCB tại cơ sở cấp cơ bản với bệnh thuộc Phụ lục II TT 01/2025', 'tu_den' => true],
    '1.17' => ['ten' => 'Tự đến KCB tại cơ sở cấp chuyên sâu với bệnh thuộc Phụ lục I TT 01/2025', 'tu_den' => true],
    '1.18' => ['ten' => 'Tự đến KCB ngoại trú tại cơ sở cấp chuyên sâu trước đây là tuyến tỉnh', 'tu_den' => true,
               'muc_huong_theo_moc' => ['moc' => '2026-07-01', 'truoc_moc' => 0, 'tu_moc' => 50]],
    '2'    => ['ten' => 'Cấp cứu'],
    '3.1'  => ['ten' => 'Tự đến KCB tại cơ sở cấp chuyên sâu trước đây là tuyến trung ương', 'tu_den' => true,
               'ngoai_tru_khong_huong' => true],
    '3.2'  => ['ten' => 'Tự đến KCB nội trú tại cơ sở cấp chuyên sâu trước đây là tuyến tỉnh', 'tu_den' => true],
    '3.3'  => ['ten' => 'Tự đến KCB tại cơ sở cấp cơ bản, cấp chuyên sâu trước đây là tuyến huyện', 'tu_den' => true],
    '3.6'  => ['ten' => 'Dân tộc thiểu số, hộ nghèo vùng khó khăn đến KCB nội trú tại cơ sở cấp chuyên sâu', 'tu_den' => true],
    '7'    => ['ten' => 'Lĩnh thuốc theo giấy hẹn trong dịch bệnh nhóm A hoặc bất khả kháng', 'linh_thuoc_khong_kham' => true],
    '7.2'  => ['ten' => 'Người bệnh uỷ quyền cho người khác đến lĩnh thuốc', 'linh_thuoc_khong_kham' => true],
    '7.3'  => ['ten' => 'Người bệnh lĩnh thuốc tại cơ sở KCB khác', 'linh_thuoc_khong_kham' => true],
    '7.4'  => ['ten' => 'Cơ sở KCB chuyển thuốc đến cho người bệnh', 'linh_thuoc_khong_kham' => true],
    '8'    => ['ten' => 'Thu hồi đề nghị thanh toán'],
    '9'    => ['ten' => 'Người bệnh không KCB BHYT', 'khong_bhyt' => true],
    '10'   => ['ten' => 'Đến lĩnh thuốc theo giấy hẹn (chỉ lĩnh thuốc, không khám bệnh)', 'linh_thuoc_khong_kham' => true],
];
```

- [ ] **Step 5: Chạy test, xác nhận xanh**

Run: `php -l config/doi_tuong_kcb.php && ./vendor/bin/phpunit tests/Unit/Xml3176/Support/DoiTuongKcbCatalogTest.php`
Expected: PASS (7 tests)

- [ ] **Step 6: Chạy hồi quy rồi commit**

```bash
./vendor/bin/phpunit tests/Unit/Xml3176
git add config/doi_tuong_kcb.php app/Services/Xml3176/Support/DoiTuongKcbCatalog.php tests/Unit/Xml3176/Support/DoiTuongKcbCatalogTest.php
git commit -m "feat(xml3176): danh muc 27 ma doi tuong KCB va helper tra danh muc"
```

---

### Task 2: Vá cấu hình khớp tiền tố (hai quy tắc sẵn có)

**Files:**
- Modify: `config/xml3176.php` (khoá `xml1.ma_doituong_kcb_trai_tuyen`)
- Modify: `app/Services/Xml3176Xml1Checker.php` (khối `ADMIN_INFO_ERROR_MA_DOITUONG_KCB_INVALID`, khoảng dòng 329–337)
- Modify: `app/Services/Xml3176CompleteChecker.php` (khối trái tuyến trong `checkMucHuong`, khoảng dòng 377–385)
- Test: `tests/Unit/Xml3176/Checker/Xml3176CompleteCheckerRuleTest.php` (thêm ca)

**Interfaces:**
- Consumes: không có (không dùng helper Task 1 — đây là hai đoạn mã sẵn có, sửa tại chỗ).
- Produces: cấu hình `xml3176.xml1.ma_doituong_kcb_trai_tuyen` nay là `['3.1']` và **cả hai** nơi dùng đều khớp đúng bằng.

**Vì sao:** danh mục quy định chỉ `3.1` bị giảm mức hưởng (40% nội trú, 0% ngoại trú); `3.2`, `3.3`, `3.6` đều hưởng **100%**. Khớp tiền tố `'3'` gom cả bốn. Chưa báo oan vì `checkMucHuong` còn chốt `tuyen_cmkt` mà danh mục cơ sở chưa nạp đủ — sẽ nổ ngay khi nạp xong.

- [ ] **Step 1: Viết test đỏ**

Thêm vào `tests/Unit/Xml3176/Checker/Xml3176CompleteCheckerRuleTest.php`, ngay trước dấu `}` cuối lớp:

```php
    /** @test */
    public function ma_36_huong_100_khong_bi_coi_la_trai_tuyen()
    {
        // Danh muc: chi 3.1 bi giam muc huong. 3.2/3.3/3.6 deu huong 100%.
        // Config cu khai ['3'] va khop TIEN TO nen gom ca bon ma.
        config(['xml3176.xml1.ma_doituong_kcb_trai_tuyen' => ['3.1']]);

        $x1 = Xml3176Xml1::create([
            'ma_lk' => 'M36', 'stt' => 1,
            'ma_doituong_kcb' => '3.6',
            'ma_loai_kcb' => '03',
            'ma_the_bhyt' => 'DN4010112345678',
            'ma_cskcb' => '01929',
            'ngay_vao' => '202609010800',
            't_tongchi_bh' => 5000000,
        ]);

        $codes = $this->errorCodes($this->invokePrivate($this->checker(), 'checkMucHuong', $x1));

        $this->assertNotContains('XMLComplete_MUC_HUONG_TRAI_TUYEN_TW', $codes);
    }

    /** @test */
    public function ma_31_van_duoc_coi_la_trai_tuyen()
    {
        // Ca doi xung: ban va khong duoc lam 3.1 lot luoi.
        config(['xml3176.xml1.ma_doituong_kcb_trai_tuyen' => ['3.1']]);

        $src = file_get_contents(app_path('Services/Xml3176CompleteChecker.php'));
        $this->assertNotContains('strpos($maDoiTuong', $src,
            'checkMucHuong van con khop tien to - phai doi sang khop dung bang');

        $src1 = file_get_contents(app_path('Services/Xml3176Xml1Checker.php'));
        $this->assertNotContains("strpos(\$data->ma_doituong_kcb", $src1,
            'Xml3176Xml1Checker van con khop tien to - phai doi sang khop dung bang');
    }
```

- [ ] **Step 2: Chạy test, xác nhận đỏ**

Run: `./vendor/bin/phpunit tests/Unit/Xml3176/Checker/Xml3176CompleteCheckerRuleTest.php`
Expected: FAIL — hai ca mới đỏ vì mã vẫn khớp tiền tố

- [ ] **Step 3: Sửa cấu hình**

Trong `config/xml3176.php`, thay dòng khoá `ma_doituong_kcb_trai_tuyen` trong khối `'xml1'`:

```php
        // Ma doi tuong bi giam muc huong (trai tuyen). Theo danh muc ma doi tuong KCB do
        // Bo Y te ban hanh, CHI ma '3.1' bi giam (40% noi tru, 0% ngoai tru); cac ma
        // '3.2', '3.3', '3.6' deu huong 100%.
        // Khai DAY DU ma va khop DUNG BANG, khong khop tien to: gia tri cu la ['3'] va
        // hai noi dung no deu dung strpos()===0 nen gom ca bon ma 3.x.
        'ma_doituong_kcb_trai_tuyen' => ['3.1'],
```

- [ ] **Step 4: Sửa `Xml3176Xml1Checker`**

Thay khối vòng lặp khớp tiền tố (khoảng dòng 329–337):

```php
            $is_ma_doituong_kcb_invalid = false;

            foreach (config('xml3176.xml1.ma_doituong_kcb_trai_tuyen') as $ma_doituong) {
                if (strpos($data->ma_doituong_kcb, (string)$ma_doituong) === 0) {
                    $is_ma_doituong_kcb_invalid = true;
                    break;
                }
            }
```

bằng:

```php
            // Khop DUNG BANG, khong khop tien to: ma doi tuong dung dang phan cap co dau
            // cham nen strpos()===0 lam '3' nuot ca 3.1, 3.2, 3.3, 3.6 - trong khi danh
            // muc chi giam muc huong cho 3.1, ba ma kia huong 100%.
            $is_ma_doituong_kcb_invalid = in_array(
                trim((string) $data->ma_doituong_kcb),
                (array) config('xml3176.xml1.ma_doituong_kcb_trai_tuyen', []),
                true
            );
```

- [ ] **Step 5: Sửa `Xml3176CompleteChecker::checkMucHuong`**

Thay khối (khoảng dòng 377–385):

```php
        $traiTuyenPrefixes = (array) config('xml3176.xml1.ma_doituong_kcb_trai_tuyen', []);
        $maDoiTuong = (string) $data->ma_doituong_kcb;
        $traiTuyen = false;
        foreach ($traiTuyenPrefixes as $prefix) {
            if ($prefix !== '' && strpos($maDoiTuong, (string) $prefix) === 0) {
                $traiTuyen = true;
                break;
            }
        }
```

bằng:

```php
        // Khop DUNG BANG, khong khop tien to - xem chu thich tai khoa cau hinh.
        $maDoiTuong = trim((string) $data->ma_doituong_kcb);
        $traiTuyen = in_array(
            $maDoiTuong,
            (array) config('xml3176.xml1.ma_doituong_kcb_trai_tuyen', []),
            true
        );
```

- [ ] **Step 6: Chạy test, xác nhận xanh**

Run: `php -l config/xml3176.php && ./vendor/bin/phpunit tests/Unit/Xml3176/Checker/Xml3176CompleteCheckerRuleTest.php && ./vendor/bin/phpunit tests/Unit/Xml3176`
Expected: PASS

- [ ] **Step 7: Commit**

```bash
git add config/xml3176.php app/Services/Xml3176Xml1Checker.php app/Services/Xml3176CompleteChecker.php tests/Unit/Xml3176/Checker/Xml3176CompleteCheckerRuleTest.php
git commit -m "fix(xml3176): ma doi tuong trai tuyen khop dung bang thay vi khop tien to"
```

---

### Task 3: Bảy quy tắc ở `Xml3176Xml1Checker`

**Files:**
- Modify: `app/Services/Xml3176Xml1Checker.php`
- Test: `tests/Unit/Xml3176/Checker/Xml3176Xml1DoiTuongKcbTest.php`

**Interfaces:**
- Consumes: `DoiTuongKcbCatalog::coTrongDanhMuc()`, `thuocTinh()`, `laTuDen()`, `chuanHoa()` (Task 1); `DanhSachPhanCachParser::tach()` (đã có sẵn trong `app/Services/Xml3176/Support/`); `config('doi_tuong_kcb')`; `config('xml3176.treatment_type_inpatient')` (= `['03','04','09']`).
- Produces: hàm private `checkDoiTuongKcb(Xml3176Xml1 $data): Collection` sinh 7 mã: `XML1_DOI_TUONG_KCB_NGOAI_DANH_MUC`, `..._THIEU_NOI_DI`, `..._TU_DEN_CO_NOI_DI`, `..._THIEU_THE_BHYT`, `..._KHONG_BHYT_CO_THE`, `..._DUNG_DKBD_SAI_MA`, `..._31_NGOAI_TRU_CO_BHTT`.

- [ ] **Step 1: Viết test đỏ**

```php
<?php

namespace Tests\Unit\Xml3176\Checker;

use App\Models\BHYT\Xml3176Xml1;
use App\Services\Xml3176Xml1Checker;
use Tests\Support\Xml3176RuleTestSupport;
use Tests\TestCase;

class Xml3176Xml1DoiTuongKcbTest extends TestCase
{
    use Xml3176RuleTestSupport;

    protected function setUp()
    {
        parent::setUp();
        $this->bootXml3176Sqlite(['2026_01_09_152817_create_xml3176_xml1s_table.php']);
    }

    private function codes(array $ghiDe): array
    {
        $dong = new Xml3176Xml1(array_merge([
            'ma_lk' => 'A', 'stt' => 1,
            'ma_the_bhyt' => 'DN4010112345678',
            'ma_cskcb' => '01929',
            'ma_dkbd' => '36001',
            'ma_loai_kcb' => '03',
        ], $ghiDe));

        return $this->errorCodes($this->invokePrivate(
            $this->makeChecker(Xml3176Xml1Checker::class), 'checkDoiTuongKcb', $dong
        ));
    }

    /** @test */
    public function ma_rong_thi_im_lang()
    {
        // Da co ADMIN_INFO_ERROR_MA_DOITUONG_KCB lo truong hop rong.
        $this->assertSame([], $this->codes(['ma_doituong_kcb' => null]));
        $this->assertSame([], $this->codes(['ma_doituong_kcb' => '']));
    }

    /** @test */
    public function ma_ngoai_danh_muc()
    {
        $this->assertContains('XML1_DOI_TUONG_KCB_NGOAI_DANH_MUC', $this->codes(['ma_doituong_kcb' => '1.9']));
        $this->assertContains('XML1_DOI_TUONG_KCB_NGOAI_DANH_MUC', $this->codes(['ma_doituong_kcb' => '4']));
        // '3' khong phai ma hop le, chi 3.1/3.2/3.3/3.6 moi la
        $this->assertContains('XML1_DOI_TUONG_KCB_NGOAI_DANH_MUC', $this->codes(['ma_doituong_kcb' => '3']));
        $this->assertNotContains('XML1_DOI_TUONG_KCB_NGOAI_DANH_MUC', $this->codes(['ma_doituong_kcb' => '1.5']));
    }

    /** @test */
    public function ma_13_thieu_ma_noi_di()
    {
        $this->assertContains('XML1_DOI_TUONG_KCB_THIEU_NOI_DI',
            $this->codes(['ma_doituong_kcb' => '1.3', 'ma_noi_di' => null]));

        $this->assertNotContains('XML1_DOI_TUONG_KCB_THIEU_NOI_DI',
            $this->codes(['ma_doituong_kcb' => '1.3', 'ma_noi_di' => '36001']));

        // Ma khac khong doi MA_NOI_DI
        $this->assertNotContains('XML1_DOI_TUONG_KCB_THIEU_NOI_DI',
            $this->codes(['ma_doituong_kcb' => '1.5', 'ma_noi_di' => null]));
    }

    /** @test */
    public function ma_tu_den_khong_duoc_co_ma_noi_di()
    {
        $this->assertContains('XML1_DOI_TUONG_KCB_TU_DEN_CO_NOI_DI',
            $this->codes(['ma_doituong_kcb' => '3.1', 'ma_noi_di' => '36001']));

        $this->assertContains('XML1_DOI_TUONG_KCB_TU_DEN_CO_NOI_DI',
            $this->codes(['ma_doituong_kcb' => '1.17', 'ma_noi_di' => '36001']));

        $this->assertNotContains('XML1_DOI_TUONG_KCB_TU_DEN_CO_NOI_DI',
            $this->codes(['ma_doituong_kcb' => '3.1', 'ma_noi_di' => null]));
    }

    /** @test */
    public function khong_co_the_bhyt_ma_van_de_nghi_quy_thanh_toan()
    {
        $this->assertContains('XML1_DOI_TUONG_KCB_THIEU_THE_BHYT',
            $this->codes(['ma_doituong_kcb' => '2', 'ma_the_bhyt' => '', 't_bhtt' => 500000]));
    }

    /** @test */
    public function khong_co_the_nhung_chua_de_nghi_quy_tra_thi_im_lang()
    {
        // Cap cuu la ngoai le da biet: chuan cho phep tra cuu the truoc khi ra vien.
        // Chi mau thuan that khi doi quy tra ma khong co the.
        $this->assertNotContains('XML1_DOI_TUONG_KCB_THIEU_THE_BHYT',
            $this->codes(['ma_doituong_kcb' => '2', 'ma_the_bhyt' => '', 't_bhtt' => null]));

        $this->assertNotContains('XML1_DOI_TUONG_KCB_THIEU_THE_BHYT',
            $this->codes(['ma_doituong_kcb' => '2', 'ma_the_bhyt' => '', 't_bhtt' => 0]));
    }

    /** @test */
    public function ma_9_khong_KCB_BHYT_thi_khong_duoc_co_the()
    {
        $this->assertContains('XML1_DOI_TUONG_KCB_KHONG_BHYT_CO_THE',
            $this->codes(['ma_doituong_kcb' => '9', 'ma_the_bhyt' => 'DN4010112345678']));

        $this->assertNotContains('XML1_DOI_TUONG_KCB_KHONG_BHYT_CO_THE',
            $this->codes(['ma_doituong_kcb' => '9', 'ma_the_bhyt' => '']));

        // Ma 9 khong the dong thoi bi bao THIEU_THE_BHYT
        $this->assertNotContains('XML1_DOI_TUONG_KCB_THIEU_THE_BHYT',
            $this->codes(['ma_doituong_kcb' => '9', 'ma_the_bhyt' => '', 't_bhtt' => 500000]));
    }

    /** @test */
    public function dung_noi_dang_ky_ban_dau_ma_khai_sai_ma()
    {
        $this->assertContains('XML1_DOI_TUONG_KCB_DUNG_DKBD_SAI_MA',
            $this->codes(['ma_doituong_kcb' => '1.5', 'ma_dkbd' => '01929', 'ma_cskcb' => '01929']));

        $this->assertNotContains('XML1_DOI_TUONG_KCB_DUNG_DKBD_SAI_MA',
            $this->codes(['ma_doituong_kcb' => '1.1', 'ma_dkbd' => '01929', 'ma_cskcb' => '01929']));

        $this->assertNotContains('XML1_DOI_TUONG_KCB_DUNG_DKBD_SAI_MA',
            $this->codes(['ma_doituong_kcb' => '1.2', 'ma_dkbd' => '01929', 'ma_cskcb' => '01929']));
    }

    /** @test */
    public function ma_dkbd_nhieu_ma_ngan_boi_dau_cham_phay()
    {
        // Chuan cho phep MA_DKBD chua nhieu ma khi nguoi benh doi the giua dot.
        // So chuoi tho se bo sot ca nay.
        $this->assertContains('XML1_DOI_TUONG_KCB_DUNG_DKBD_SAI_MA',
            $this->codes(['ma_doituong_kcb' => '1.5', 'ma_dkbd' => '36001;01929', 'ma_cskcb' => '01929']));
    }

    /** @test */
    public function ma_31_ngoai_tru_thi_khong_duoc_huong()
    {
        // Danh muc: ma 3.1 huong 40% noi tru, 0% ngoai tru.
        $this->assertContains('XML1_DOI_TUONG_KCB_31_NGOAI_TRU_CO_BHTT',
            $this->codes(['ma_doituong_kcb' => '3.1', 'ma_loai_kcb' => '01', 't_bhtt' => 300000]));

        // Noi tru thi quy tac nay im lang - da co checkMucHuong lo muc 40%.
        $this->assertNotContains('XML1_DOI_TUONG_KCB_31_NGOAI_TRU_CO_BHTT',
            $this->codes(['ma_doituong_kcb' => '3.1', 'ma_loai_kcb' => '03', 't_bhtt' => 300000]));

        // Ngoai tru ma khong de nghi quy tra thi khong sai.
        $this->assertNotContains('XML1_DOI_TUONG_KCB_31_NGOAI_TRU_CO_BHTT',
            $this->codes(['ma_doituong_kcb' => '3.1', 'ma_loai_kcb' => '01', 't_bhtt' => 0]));
    }

    /** @test */
    public function ho_so_dung_hoan_toan_khong_sinh_loi_nao()
    {
        $this->assertSame([], $this->codes([
            'ma_doituong_kcb' => '1.3', 'ma_noi_di' => '36001',
            'ma_dkbd' => '36001', 'ma_cskcb' => '01929', 't_bhtt' => 500000,
        ]));
    }
}
```

- [ ] **Step 2: Chạy test, xác nhận đỏ**

Run: `./vendor/bin/phpunit tests/Unit/Xml3176/Checker/Xml3176Xml1DoiTuongKcbTest.php`
Expected: FAIL — `ReflectionException: Method ...::checkDoiTuongKcb() does not exist`

- [ ] **Step 3: Thêm hai dòng `use` vào `Xml3176Xml1Checker`**

Tệp đã có sẵn `use Illuminate\Support\Collection;` và `use App\Models\BHYT\Xml3176Xml1;` — **chỉ thêm hai dòng này**, kiểm khối `use` trước khi thêm (khai trùng tên là lỗi PHP nghiêm trọng):

```php
use App\Services\Xml3176\Support\DoiTuongKcbCatalog;
use App\Services\Xml3176\Support\DanhSachPhanCachParser;
```

- [ ] **Step 4: Thêm hàm `checkDoiTuongKcb`**

Đặt ngay sau hàm `checkMaKhuVuc`:

```php
    /**
     * Kiem MA_DOITUONG_KCB theo danh muc ma doi tuong den KCB do Bo Y te ban hanh.
     *
     * Ma rong thi im lang - da co ADMIN_INFO_ERROR_MA_DOITUONG_KCB lo viec do.
     */
    private function checkDoiTuongKcb(Xml3176Xml1 $data): Collection
    {
        $errors = collect();
        $ma = DoiTuongKcbCatalog::chuanHoa($data->ma_doituong_kcb);

        if ($ma === '') {
            return $errors;
        }

        $danhMuc = (array) config('doi_tuong_kcb', []);

        if (!DoiTuongKcbCatalog::coTrongDanhMuc($ma, $danhMuc)) {
            $errorCode = $this->generateErrorCode('DOI_TUONG_KCB_NGOAI_DANH_MUC');
            $errors->push((object)[
                'error_code' => $errorCode,
                'error_name' => 'Mã đối tượng KCB ngoài danh mục',
                'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                'description' => 'Mã đối tượng KCB "' . $ma . '" không có trong danh mục mã đối tượng '
                    . 'đến khám bệnh, chữa bệnh do Bộ Y tế ban hành',
            ]);

            return $errors; // ma la thi moi kiem tra dua tren thuoc tinh deu vo nghia
        }

        $coNoiDi = !empty($data->ma_noi_di);
        $coThe   = !empty($data->ma_the_bhyt);
        $tBhtt   = (float) $data->t_bhtt;

        // Ma doi hoi phai co co so noi chuyen nguoi benh di (hien chi ma 1.3).
        if (DoiTuongKcbCatalog::thuocTinh($ma, $danhMuc, 'can_noi_di', false) && !$coNoiDi) {
            $errorCode = $this->generateErrorCode('DOI_TUONG_KCB_THIEU_NOI_DI');
            $errors->push((object)[
                'error_code' => $errorCode,
                'error_name' => 'Đến KCB có phiếu chuyển nhưng thiếu mã nơi chuyển đi',
                'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                'description' => 'Mã đối tượng ' . $ma . ' (' . DoiTuongKcbCatalog::thuocTinh($ma, $danhMuc, 'ten')
                    . ') nhưng MA_NOI_DI để trống',
            ]);
        }

        // Tu den thi khong the co co so chuyen di.
        if (DoiTuongKcbCatalog::laTuDen($ma, $danhMuc) && $coNoiDi) {
            $errorCode = $this->generateErrorCode('DOI_TUONG_KCB_TU_DEN_CO_NOI_DI');
            $errors->push((object)[
                'error_code' => $errorCode,
                'error_name' => 'Người bệnh tự đến nhưng lại có mã nơi chuyển đi',
                'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                'description' => 'Mã đối tượng ' . $ma . ' (' . DoiTuongKcbCatalog::thuocTinh($ma, $danhMuc, 'ten')
                    . ') nhưng MA_NOI_DI = ' . $data->ma_noi_di,
            ]);
        }

        $khongBhyt = (bool) DoiTuongKcbCatalog::thuocTinh($ma, $danhMuc, 'khong_bhyt', false);

        if ($khongBhyt && $coThe) {
            $errorCode = $this->generateErrorCode('DOI_TUONG_KCB_KHONG_BHYT_CO_THE');
            $errors->push((object)[
                'error_code' => $errorCode,
                'error_name' => 'Khai không KCB BHYT nhưng vẫn có mã thẻ',
                'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                'description' => 'Mã đối tượng ' . $ma . ' là người bệnh không KCB BHYT '
                    . 'nhưng MA_THE_BHYT = ' . $data->ma_the_bhyt,
            ]);
        }

        // Doi quy thanh toan ma khong co the moi la mau thuan. Cap cuu chua xuat trinh
        // the la ngoai le da biet - chuan cho phep tra cuu the truoc khi nguoi benh ra
        // vien - nen chi bao khi T_BHTT > 0.
        if (!$khongBhyt && !$coThe && $tBhtt > 0) {
            $errorCode = $this->generateErrorCode('DOI_TUONG_KCB_THIEU_THE_BHYT');
            $errors->push((object)[
                'error_code' => $errorCode,
                'error_name' => 'Đề nghị quỹ BHYT thanh toán nhưng không có mã thẻ',
                'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                'description' => 'Mã đối tượng ' . $ma . ' đề nghị quỹ thanh toán '
                    . number_format($tBhtt) . ' đồng nhưng MA_THE_BHYT để trống',
            ]);
        }

        // MA_DKBD co the chua nhieu ma ngan boi ';' khi nguoi benh doi the giua dot, nen
        // phai tach roi moi so - so chuoi tho se bo sot.
        $dkbd = DanhSachPhanCachParser::tach($data->ma_dkbd);
        $cskcb = trim((string) $data->ma_cskcb);

        if ($cskcb !== '' && in_array($cskcb, $dkbd, true) && !in_array($ma, ['1.1', '1.2'], true)) {
            $errorCode = $this->generateErrorCode('DOI_TUONG_KCB_DUNG_DKBD_SAI_MA');
            $errors->push((object)[
                'error_code' => $errorCode,
                'error_name' => 'Đến đúng nơi đăng ký ban đầu nhưng khai mã đối tượng khác',
                'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                'description' => 'MA_CSKCB ' . $cskcb . ' nằm trong MA_DKBD (' . $data->ma_dkbd
                    . ') nhưng mã đối tượng khai là ' . $ma . ', không phải 1.1 hoặc 1.2',
            ]);
        }

        // Ma 3.1: 40% noi tru, 0% ngoai tru. Nhanh noi tru da co
        // Xml3176CompleteChecker::checkMucHuong() lo, o day chi bu nhanh ngoai tru.
        $noiTru = in_array($data->ma_loai_kcb, (array) config('xml3176.treatment_type_inpatient', []));

        if (DoiTuongKcbCatalog::thuocTinh($ma, $danhMuc, 'ngoai_tru_khong_huong', false)
            && !$noiTru && $tBhtt > 0) {
            $errorCode = $this->generateErrorCode('DOI_TUONG_KCB_31_NGOAI_TRU_CO_BHTT');
            $errors->push((object)[
                'error_code' => $errorCode,
                'error_name' => 'Đối tượng này khám ngoại trú không được quỹ BHYT thanh toán',
                'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                'description' => 'Mã đối tượng ' . $ma . ' khám ngoại trú (MA_LOAI_KCB = '
                    . $data->ma_loai_kcb . ') thì mức hưởng là 0% nhưng T_BHTT = '
                    . number_format($tBhtt) . ' đồng',
            ]);
        }

        return $errors;
    }
```

- [ ] **Step 5: Đấu hàm vào `checkErrors`**

Trong `Xml3176Xml1Checker::checkErrors()`, thêm ngay sau dòng `$errors = $errors->merge($this->checkMaKhuVuc($data));`:

```php
        $errors = $errors->merge($this->checkDoiTuongKcb($data));
```

- [ ] **Step 6: Chạy test, xác nhận xanh**

Run: `./vendor/bin/phpunit tests/Unit/Xml3176/Checker/Xml3176Xml1DoiTuongKcbTest.php && ./vendor/bin/phpunit tests/Unit/Xml3176`
Expected: PASS

- [ ] **Step 7: Commit**

```bash
git add app/Services/Xml3176Xml1Checker.php tests/Unit/Xml3176/Checker/Xml3176Xml1DoiTuongKcbTest.php
git commit -m "feat(xml3176): 7 quy tac ma doi tuong KCB tren XML1"
```

---

### Task 4: Ba quy tắc ở `Xml3176CompleteChecker`

**Files:**
- Modify: `app/Services/Xml3176CompleteChecker.php`
- Test: `tests/Unit/Xml3176/Checker/Xml3176CompleteDoiTuongKcbTest.php`

**Interfaces:**
- Consumes: `DoiTuongKcbCatalog::thuocTinh()`, `chuanHoa()` (Task 1); `Xml3176DateHelper::toDateTime()` (đã có sẵn, trả `DateTime|null` từ chuỗi `yyyymmddHHMM`); `config('xml3176.examination_group_code')` (= `[13]`).
- Produces: hàm private `checkDoiTuongKcbMucHuong(Xml3176Xml1 $data): Collection` sinh 3 mã: `XMLComplete_DOI_TUONG_KCB_MUC_HUONG_CO_DINH`, `..._MUC_HUONG_THEO_MOC`, `..._LINH_THUOC_CO_TIEN_KHAM`.

**Cảnh báo vận hành:** `Xml3176CompleteChecker` chỉ chạy khi `organization.xml_3176_not_check = false`. Trên máy dev khoá này từng là `true` và làm 26 quy tắc `XMLComplete_` nằm im. Ba quy tắc này sẽ câm nếu khoá đó bật.

- [ ] **Step 1: Viết test đỏ**

```php
<?php

namespace Tests\Unit\Xml3176\Checker;

use App\Models\BHYT\Xml3176Xml1;
use App\Models\BHYT\Xml3176Xml3;
use App\Services\Xml3176CompleteChecker;
use Tests\Support\FakeXml3176ErrorService;
use Tests\Support\Xml3176RuleTestSupport;
use Tests\TestCase;

class Xml3176CompleteDoiTuongKcbTest extends TestCase
{
    use Xml3176RuleTestSupport;

    protected function setUp()
    {
        parent::setUp();
        $this->bootXml3176Sqlite([
            '2026_01_09_152817_create_xml3176_xml1s_table.php',
            '2026_01_09_152826_create_xml3176_xml2s_table.php',
            '2026_01_09_152832_create_xml3176_xml3s_table.php',
        ]);
    }

    private function checker(): Xml3176CompleteChecker
    {
        return new Xml3176CompleteChecker(new FakeXml3176ErrorService());
    }

    private function codes(Xml3176Xml1 $x1): array
    {
        return $this->errorCodes($this->invokePrivate($this->checker(), 'checkDoiTuongKcbMucHuong', $x1));
    }

    /** @test */
    public function ma_12_bat_buoc_muc_huong_100()
    {
        $x1 = Xml3176Xml1::create(['ma_lk' => 'A', 'stt' => 1, 'ma_doituong_kcb' => '1.2', 'ngay_vao' => '202609010800']);
        Xml3176Xml3::create(['ma_lk' => 'A', 'stt' => 1, 'ma_dich_vu' => 'DV1', 'muc_huong' => 80]);

        $this->assertContains('XMLComplete_DOI_TUONG_KCB_MUC_HUONG_CO_DINH', $this->codes($x1));
    }

    /** @test */
    public function ma_12_khai_dung_100_thi_im_lang()
    {
        $x1 = Xml3176Xml1::create(['ma_lk' => 'B', 'stt' => 1, 'ma_doituong_kcb' => '1.2', 'ngay_vao' => '202609010800']);
        Xml3176Xml3::create(['ma_lk' => 'B', 'stt' => 1, 'ma_dich_vu' => 'DV1', 'muc_huong' => 100]);

        $this->assertNotContains('XMLComplete_DOI_TUONG_KCB_MUC_HUONG_CO_DINH', $this->codes($x1));
    }

    /** @test */
    public function ma_113_tu_moc_01_07_2026_phai_huong_50()
    {
        $x1 = Xml3176Xml1::create(['ma_lk' => 'C', 'stt' => 1, 'ma_doituong_kcb' => '1.13', 'ngay_vao' => '202609010800']);
        Xml3176Xml3::create(['ma_lk' => 'C', 'stt' => 1, 'ma_dich_vu' => 'DV1', 'muc_huong' => 80]);

        $this->assertContains('XMLComplete_DOI_TUONG_KCB_MUC_HUONG_THEO_MOC', $this->codes($x1));
    }

    /** @test */
    public function ma_113_khai_dung_50_thi_im_lang()
    {
        $x1 = Xml3176Xml1::create(['ma_lk' => 'D', 'stt' => 1, 'ma_doituong_kcb' => '1.13', 'ngay_vao' => '202609010800']);
        Xml3176Xml3::create(['ma_lk' => 'D', 'stt' => 1, 'ma_dich_vu' => 'DV1', 'muc_huong' => 50]);

        $this->assertNotContains('XMLComplete_DOI_TUONG_KCB_MUC_HUONG_THEO_MOC', $this->codes($x1));
    }

    /** @test */
    public function ma_113_truoc_moc_thi_khong_duoc_huong()
    {
        // Truoc 01/7/2026: khong duoc huong BHYT, nen T_BHTT phai bang 0.
        $x1 = Xml3176Xml1::create(['ma_lk' => 'E', 'stt' => 1, 'ma_doituong_kcb' => '1.13',
            'ngay_vao' => '202601150800', 't_bhtt' => 500000]);

        $this->assertContains('XMLComplete_DOI_TUONG_KCB_MUC_HUONG_THEO_MOC', $this->codes($x1));
    }

    /** @test */
    public function ngay_vao_khong_doc_duoc_thi_im_lang()
    {
        // Thieu can cu thi khong ket luan.
        $x1 = Xml3176Xml1::create(['ma_lk' => 'F', 'stt' => 1, 'ma_doituong_kcb' => '1.13',
            'ngay_vao' => '', 't_bhtt' => 500000]);

        $this->assertNotContains('XMLComplete_DOI_TUONG_KCB_MUC_HUONG_THEO_MOC', $this->codes($x1));
    }

    /** @test */
    public function linh_thuoc_khong_kham_ma_van_co_tien_cong_kham()
    {
        $x1 = Xml3176Xml1::create(['ma_lk' => 'G', 'stt' => 1, 'ma_doituong_kcb' => '7', 'ngay_vao' => '202609010800']);
        Xml3176Xml3::create(['ma_lk' => 'G', 'stt' => 1, 'ma_dich_vu' => 'KHAM', 'ma_nhom' => 13, 'muc_huong' => 100]);

        $this->assertContains('XMLComplete_DOI_TUONG_KCB_LINH_THUOC_CO_TIEN_KHAM', $this->codes($x1));
    }

    /** @test */
    public function linh_thuoc_khong_co_dong_kham_thi_im_lang()
    {
        $x1 = Xml3176Xml1::create(['ma_lk' => 'H', 'stt' => 1, 'ma_doituong_kcb' => '10', 'ngay_vao' => '202609010800']);
        Xml3176Xml3::create(['ma_lk' => 'H', 'stt' => 1, 'ma_dich_vu' => 'XN1', 'ma_nhom' => 1, 'muc_huong' => 100]);

        $this->assertNotContains('XMLComplete_DOI_TUONG_KCB_LINH_THUOC_CO_TIEN_KHAM', $this->codes($x1));
    }

    /** @test */
    public function ma_thuong_khong_sinh_loi_nao()
    {
        $x1 = Xml3176Xml1::create(['ma_lk' => 'I', 'stt' => 1, 'ma_doituong_kcb' => '1.5', 'ngay_vao' => '202609010800']);
        Xml3176Xml3::create(['ma_lk' => 'I', 'stt' => 1, 'ma_dich_vu' => 'KHAM', 'ma_nhom' => 13, 'muc_huong' => 80]);

        $this->assertSame([], $this->codes($x1));
    }

    /** @test */
    public function ma_rong_hoac_ngoai_danh_muc_thi_im_lang()
    {
        $x1 = Xml3176Xml1::create(['ma_lk' => 'J', 'stt' => 1, 'ma_doituong_kcb' => '', 'ngay_vao' => '202609010800']);
        $this->assertSame([], $this->codes($x1));

        $x2 = Xml3176Xml1::create(['ma_lk' => 'K', 'stt' => 1, 'ma_doituong_kcb' => '9.9', 'ngay_vao' => '202609010800']);
        $this->assertSame([], $this->codes($x2));
    }
}
```

- [ ] **Step 2: Chạy test, xác nhận đỏ**

Run: `./vendor/bin/phpunit tests/Unit/Xml3176/Checker/Xml3176CompleteDoiTuongKcbTest.php`
Expected: FAIL — `ReflectionException: Method ...::checkDoiTuongKcbMucHuong() does not exist`

- [ ] **Step 3: Thêm một dòng `use` vào `Xml3176CompleteChecker`**

Tệp đã có sẵn `use` cho `Xml3176Xml1`, `Xml3176Xml2`, `Xml3176Xml3`, `Xml3176DateHelper`, `DanhSachPhanCachParser`, `Collection`. **Chỉ thêm đúng một dòng**, kiểm khối `use` trước khi thêm:

```php
use App\Services\Xml3176\Support\DoiTuongKcbCatalog;
```

- [ ] **Step 4: Thêm hàm `checkDoiTuongKcbMucHuong`**

Đặt ngay sau hàm `checkCanNangCon`:

```php
    /**
     * Muc huong bat buoc theo ma doi tuong KCB.
     *
     * Nam o checker tong the vi MUC_HUONG chi co o tung dong XML2/XML3 - bang
     * xml3176_xml1s khong co cot do.
     *
     * Can cu la cot MUC_HUONG cua danh muc ma doi tuong. Ba quy tac nay chua co ho so
     * nao de chay tren du lieu hien tai (khong ma nao trong 1.2, 1.13, 1.14, 1.18, 7,
     * 7.2, 7.3, 7.4, 10 xuat hien), nen chua duoc kiem chung thuc te.
     */
    private function checkDoiTuongKcbMucHuong(Xml3176Xml1 $data): Collection
    {
        $errors = collect();
        $ma = DoiTuongKcbCatalog::chuanHoa($data->ma_doituong_kcb);
        $danhMuc = (array) config('doi_tuong_kcb', []);

        if ($ma === '' || !DoiTuongKcbCatalog::coTrongDanhMuc($ma, $danhMuc)) {
            return $errors; // ma rong hoac la: da co quy tac rieng o XML1 lo
        }

        $ten = DoiTuongKcbCatalog::thuocTinh($ma, $danhMuc, 'ten');

        // 1) Muc huong co dinh, khong phu thuoc muc huong tren the (ma 1.2 = 100).
        $coDinh = DoiTuongKcbCatalog::thuocTinh($ma, $danhMuc, 'muc_huong_co_dinh');

        if ($coDinh !== null) {
            $lech = $this->mucHuongKhacVoi($data->ma_lk, (float) $coDinh);

            if ($lech !== null) {
                $code = $this->generateErrorCode('DOI_TUONG_KCB_MUC_HUONG_CO_DINH');
                $errors->push((object)[
                    'error_code' => $code,
                    'error_name' => 'Mức hưởng không đúng quy định của mã đối tượng',
                    'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($code),
                    'description' => 'Mã đối tượng ' . $ma . ' (' . $ten . ') phải có mức hưởng '
                        . $coDinh . '% không phụ thuộc thẻ BHYT, nhưng có dòng khai ' . $lech . '%',
                ]);
            }
        }

        // 2) Muc huong doi theo moc thoi gian (ma 1.13, 1.14, 1.18).
        $theoMoc = DoiTuongKcbCatalog::thuocTinh($ma, $danhMuc, 'muc_huong_theo_moc');

        if (is_array($theoMoc)) {
            $vao = Xml3176DateHelper::toDateTime($data->ngay_vao);

            if ($vao !== null) {
                $tuMoc = $vao->format('Y-m-d') >= $theoMoc['moc'];

                if ($tuMoc) {
                    $lech = $this->mucHuongKhacVoi($data->ma_lk, (float) $theoMoc['tu_moc']);

                    if ($lech !== null) {
                        $code = $this->generateErrorCode('DOI_TUONG_KCB_MUC_HUONG_THEO_MOC');
                        $errors->push((object)[
                            'error_code' => $code,
                            'error_name' => 'Mức hưởng không đúng mốc thời gian của mã đối tượng',
                            'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($code),
                            'description' => 'Mã đối tượng ' . $ma . ' (' . $ten . ') từ ngày '
                                . $theoMoc['moc'] . ' có mức hưởng ' . $theoMoc['tu_moc']
                                . '%, nhưng có dòng khai ' . $lech . '%',
                        ]);
                    }
                } elseif ((float) $data->t_bhtt > 0) {
                    $code = $this->generateErrorCode('DOI_TUONG_KCB_MUC_HUONG_THEO_MOC');
                    $errors->push((object)[
                        'error_code' => $code,
                        'error_name' => 'Mức hưởng không đúng mốc thời gian của mã đối tượng',
                        'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($code),
                        'description' => 'Mã đối tượng ' . $ma . ' (' . $ten . ') trước ngày '
                            . $theoMoc['moc'] . ' không được hưởng BHYT, nhưng T_BHTT = '
                            . number_format((float) $data->t_bhtt) . ' đồng',
                    ]);
                }
            }
        }

        // 3) Chi linh thuoc, khong kham benh (ma 7, 7.2, 7.3, 7.4, 10).
        if (DoiTuongKcbCatalog::thuocTinh($ma, $danhMuc, 'linh_thuoc_khong_kham', false)) {
            $nhomKham = (array) config('xml3176.examination_group_code', []);

            $soDongKham = Xml3176Xml3::where('ma_lk', $data->ma_lk)
                ->whereIn('ma_nhom', $nhomKham)
                ->count();

            if ($soDongKham > 0) {
                $code = $this->generateErrorCode('DOI_TUONG_KCB_LINH_THUOC_CO_TIEN_KHAM');
                $errors->push((object)[
                    'error_code' => $code,
                    'error_name' => 'Chỉ lĩnh thuốc nhưng vẫn có tiền công khám',
                    'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($code),
                    'description' => 'Mã đối tượng ' . $ma . ' (' . $ten . ') là trường hợp chỉ lĩnh thuốc, '
                        . 'không khám bệnh, nhưng XML3 có ' . $soDongKham . ' dòng thuộc nhóm dịch vụ khám',
                ]);
            }
        }

        return $errors;
    }

    /**
     * Tra ve muc huong dau tien khac $mong doi trong cac dong XML2/XML3 cua ho so, hoac
     * null neu khong dong nao lech. Dong khong khai MUC_HUONG thi bo qua - thieu can cu.
     */
    private function mucHuongKhacVoi($ma_lk, float $mongDoi)
    {
        foreach ([Xml3176Xml2::class, Xml3176Xml3::class] as $model) {
            $gt = $model::where('ma_lk', $ma_lk)
                ->whereNotNull('muc_huong')
                ->where('muc_huong', '<>', '')
                ->where('muc_huong', '<>', $mongDoi)
                ->value('muc_huong');

            if ($gt !== null) {
                return $gt;
            }
        }

        return null;
    }
```

- [ ] **Step 5: Đấu hàm vào `checkErrors`**

Trong `Xml3176CompleteChecker::checkErrors()`, thêm ngay sau dòng `$errors = $errors->merge($this->checkCanNangCon($data));`:

```php
            $errors = $errors->merge($this->checkDoiTuongKcbMucHuong($data));
```

- [ ] **Step 6: Chạy test, xác nhận xanh**

Run: `./vendor/bin/phpunit tests/Unit/Xml3176/Checker/Xml3176CompleteDoiTuongKcbTest.php && ./vendor/bin/phpunit tests/Unit/Xml3176`
Expected: PASS

- [ ] **Step 7: Commit**

```bash
git add app/Services/Xml3176CompleteChecker.php tests/Unit/Xml3176/Checker/Xml3176CompleteDoiTuongKcbTest.php
git commit -m "feat(xml3176): 3 quy tac muc huong theo ma doi tuong KCB"
```

---

### Task 5: Seeder 10 mã lỗi + migration tự nạp

**Files:**
- Create: `database/seeds/Xml3176ErrorCatalogDoiTuongKcbSeeder.php`
- Create: `database/migrations/2026_09_11_120000_nap_danh_muc_ma_loi_doi_tuong_kcb.php`
- Test: `tests/Unit/Xml3176/Xml3176ErrorCatalogDoiTuongKcbSeederTest.php`

**Interfaces:**
- Consumes: 10 mã lỗi sinh ở Task 3 và Task 4.
- Produces: seeder idempotent ghi 10 dòng vào `xml3176_error_catalogs`; migration gọi seeder.

**Vì sao bắt buộc:** thiếu dòng danh mục thì `getCriticalErrorStatus()` trả mặc định `true`; quy tắc nổ lần đầu sẽ khiến `Xml3176ErrorService` **tự ghi dòng danh mục ở mức nghiêm trọng**, và `ExportXml3176Job` chặn xuất XML cả lô — chạy seeder sau đó cũng không gỡ được các dòng lỗi đã ghi.

- [ ] **Step 1: Viết test đỏ**

```php
<?php

namespace Tests\Unit\Xml3176;

use Tests\TestCase;

class Xml3176ErrorCatalogDoiTuongKcbSeederTest extends TestCase
{
    private function nguon(): string
    {
        return file_get_contents(database_path('seeds/Xml3176ErrorCatalogDoiTuongKcbSeeder.php'));
    }

    /** @test */
    public function seeder_khai_du_10_ma_loi()
    {
        $src = $this->nguon();

        $ma = [
            'XML1_DOI_TUONG_KCB_NGOAI_DANH_MUC',
            'XML1_DOI_TUONG_KCB_THIEU_NOI_DI',
            'XML1_DOI_TUONG_KCB_TU_DEN_CO_NOI_DI',
            'XML1_DOI_TUONG_KCB_THIEU_THE_BHYT',
            'XML1_DOI_TUONG_KCB_KHONG_BHYT_CO_THE',
            'XML1_DOI_TUONG_KCB_DUNG_DKBD_SAI_MA',
            'XML1_DOI_TUONG_KCB_31_NGOAI_TRU_CO_BHTT',
            'XMLComplete_DOI_TUONG_KCB_MUC_HUONG_CO_DINH',
            'XMLComplete_DOI_TUONG_KCB_MUC_HUONG_THEO_MOC',
            'XMLComplete_DOI_TUONG_KCB_LINH_THUOC_CO_TIEN_KHAM',
        ];

        $this->assertCount(10, $ma);

        foreach ($ma as $m) {
            $this->assertContains($m, $src, "Seeder thieu ma $m");
        }
    }

    /** @test */
    public function moi_ma_deu_khong_chan_xuat_xml()
    {
        $src = $this->nguon();
        $this->assertNotContains("'critical_error' => true", $src);
        $this->assertContains("'critical_error' => false", $src);
    }

    /** @test */
    public function seeder_idempotent()
    {
        $this->assertContains('updateOrCreate', $this->nguon());
    }

    /** @test */
    public function migration_goi_seeder_va_khong_dung_change()
    {
        $files = glob(database_path('migrations/*nap_danh_muc_ma_loi_doi_tuong_kcb.php'));
        $this->assertCount(1, $files, 'Khong tim thay migration nap danh muc');

        $src = file_get_contents($files[0]);
        $this->assertContains('Xml3176ErrorCatalogDoiTuongKcbSeeder', $src);
        $this->assertNotContains('->change()', $src, 'Du an khong co doctrine/dbal');
    }
}
```

- [ ] **Step 2: Chạy test, xác nhận đỏ**

Run: `./vendor/bin/phpunit tests/Unit/Xml3176/Xml3176ErrorCatalogDoiTuongKcbSeederTest.php`
Expected: FAIL — `file_get_contents(...): failed to open stream`

- [ ] **Step 3: Viết seeder**

```php
<?php

use Illuminate\Database\Seeder;
use App\Models\BHYT\Xml3176ErrorCatalog;

/**
 * Nap 10 error_code cua bo quy tac ma doi tuong den KCB (Phu luc 1 do Bo Y te ban hanh).
 *
 * BAT BUOC chay TRUOC khi bat quy tac: thieu dong danh muc thi
 * getCriticalErrorStatus() tra mac dinh TRUE, quy tac no lan dau se TU GHI dong danh muc
 * o muc nghiem trong va chan xuat XML ca lo - chay seeder sau do cung khong go duoc cac
 * dong loi da ghi. Vi vay co mot migration goi seeder nay.
 *
 * critical_error = false cho ca 10 ma: dot nay do duoc 5 ho so vi pham tren 1.213, ba
 * quy tac o XMLComplete chua co ho so nao de chay. Nguoi van hanh tu bat len qua man
 * danh muc ma loi sau khi quan sat.
 *
 * Idempotent (updateOrCreate) - chay lai an toan.
 */
class Xml3176ErrorCatalogDoiTuongKcbSeeder extends Seeder
{
    public function run()
    {
        $danhMuc = [
            ['XML1', 'XML1_DOI_TUONG_KCB_NGOAI_DANH_MUC', 'Mã đối tượng KCB ngoài danh mục', 'Mã đối tượng KCB phải thuộc danh mục 27 mã do Bộ Y tế ban hành'],
            ['XML1', 'XML1_DOI_TUONG_KCB_THIEU_NOI_DI', 'Đến KCB có phiếu chuyển nhưng thiếu mã nơi chuyển đi', 'Mã 1.3 là đến KCB có phiếu chuyển cơ sở nên MA_NOI_DI không được để trống'],
            ['XML1', 'XML1_DOI_TUONG_KCB_TU_DEN_CO_NOI_DI', 'Người bệnh tự đến nhưng lại có mã nơi chuyển đi', 'Các mã tự đến (1.11-1.18, 3.1, 3.2, 3.3, 3.6) thì MA_NOI_DI phải để trống'],
            ['XML1', 'XML1_DOI_TUONG_KCB_THIEU_THE_BHYT', 'Đề nghị quỹ BHYT thanh toán nhưng không có mã thẻ', 'Chỉ báo khi T_BHTT > 0; cấp cứu chưa xuất trình thẻ là ngoại lệ đã biết'],
            ['XML1', 'XML1_DOI_TUONG_KCB_KHONG_BHYT_CO_THE', 'Khai không KCB BHYT nhưng vẫn có mã thẻ', 'Mã 9 là người bệnh không KCB BHYT nên MA_THE_BHYT phải để trống'],
            ['XML1', 'XML1_DOI_TUONG_KCB_DUNG_DKBD_SAI_MA', 'Đến đúng nơi đăng ký ban đầu nhưng khai mã đối tượng khác', 'MA_CSKCB nằm trong MA_DKBD thì mã đối tượng phải là 1.1 hoặc 1.2'],
            ['XML1', 'XML1_DOI_TUONG_KCB_31_NGOAI_TRU_CO_BHTT', 'Đối tượng này khám ngoại trú không được quỹ BHYT thanh toán', 'Mã 3.1 hưởng 40% nội trú và 0% ngoại trú'],
            ['XMLComplete', 'XMLComplete_DOI_TUONG_KCB_MUC_HUONG_CO_DINH', 'Mức hưởng không đúng quy định của mã đối tượng', 'Mã 1.2 hưởng 100% không phụ thuộc mức hưởng trên thẻ BHYT'],
            ['XMLComplete', 'XMLComplete_DOI_TUONG_KCB_MUC_HUONG_THEO_MOC', 'Mức hưởng không đúng mốc thời gian của mã đối tượng', 'Mã 1.13, 1.14, 1.18: không được hưởng đến 30/6/2026, hưởng 50% từ 01/7/2026'],
            ['XMLComplete', 'XMLComplete_DOI_TUONG_KCB_LINH_THUOC_CO_TIEN_KHAM', 'Chỉ lĩnh thuốc nhưng vẫn có tiền công khám', 'Mã 7, 7.2, 7.3, 7.4, 10 là chỉ lĩnh thuốc, không khám bệnh'],
        ];

        foreach ($danhMuc as $dong) {
            list($xml, $maLoi, $ten, $moTa) = $dong;

            Xml3176ErrorCatalog::updateOrCreate(
                ['xml' => $xml, 'error_code' => $maLoi],
                [
                    'error_name'     => $ten,
                    'description'    => $moTa,
                    'critical_error' => false,
                    'is_check'       => true,
                ]
            );
        }
    }
}
```

- [ ] **Step 4: Viết migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;

/**
 * Nap danh muc 10 ma loi cua bo quy tac ma doi tuong KCB.
 *
 * Nap bang migration chu khong phai lenh chay tay: dieu kien "nho chay seeder truoc" ma
 * chi ton tai trong tri nho nguoi trien khai da tung gay hau qua khong lui lai duoc -
 * quy tac no lan dau khi thieu dong danh muc se tu ghi dong o muc nghiem trong va chan
 * xuat XML ca lo.
 */
class NapDanhMucMaLoiDoiTuongKcb extends Migration
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

- [ ] **Step 5: Chạy test, xác nhận xanh**

Run: `php -l database/seeds/Xml3176ErrorCatalogDoiTuongKcbSeeder.php && php -l database/migrations/2026_09_11_120000_nap_danh_muc_ma_loi_doi_tuong_kcb.php && ./vendor/bin/phpunit tests/Unit/Xml3176/Xml3176ErrorCatalogDoiTuongKcbSeederTest.php`
Expected: PASS (4 tests)

- [ ] **Step 6: Chạy toàn bộ hồi quy rồi commit**

**KHÔNG chạy `php artisan migrate`** — CSDL dev là dữ liệu thật của người dùng, để họ tự quyết.

```bash
./vendor/bin/phpunit tests/Unit/Xml3176
./vendor/bin/phpunit tests/Unit/Import
git add database/seeds/Xml3176ErrorCatalogDoiTuongKcbSeeder.php database/migrations/2026_09_11_120000_nap_danh_muc_ma_loi_doi_tuong_kcb.php tests/Unit/Xml3176/Xml3176ErrorCatalogDoiTuongKcbSeederTest.php
git commit -m "feat(xml3176): seeder va migration nap 10 ma loi ma doi tuong KCB"
```

---

## Sau khi hoàn thành plan — việc của người vận hành

1. `php artisan migrate` — migration tự nạp 10 mã lỗi.
2. `php artisan config:clear` — có tệp config mới (`doi_tuong_kcb.php`) và một khoá đổi giá trị (`ma_doituong_kcb_trai_tuyen`).
3. **`php artisan queue:restart`** — worker là tiến trình thường trú, giữ cả mã lẫn cấu hình trong bộ nhớ. Bỏ bước này thì quy tắc mới im lặng chạy bằng mã cũ, không dấu hiệu gì.
4. Rà lại một lô hồ sơ. Đối chiếu với số đo của spec: `DOI_TUONG_KCB_THIEU_NOI_DI` khoảng **3 hồ sơ**, `DOI_TUONG_KCB_DUNG_DKBD_SAI_MA` khoảng **2**, các mã còn lại **0**.
5. Mã nào báo trên **20% số hồ sơ** thì dừng xem xét — gần như chắc chắn là báo oan hoặc bộ xuất sai hệ thống.
6. Ba quy tắc `XMLComplete_` chỉ chạy khi `organization.xml_3176_not_check = false`. Kiểm khoá này trước khi kết luận "0 lỗi".
