# Đối chiếu danh mục không phân biệt hoa thường + CCHN theo cơ sở — Kế hoạch triển khai

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Mọi phép đối chiếu mã/tên với danh mục ở order-check và XML3176 không phân biệt hoa thường; quy tắc CCHN của order-check kiểm tra theo cơ sở KCB.

**Architecture:** Một bộ chuẩn hoá duy nhất `Xml3176\Support\TextNormalizer` (đã có) được thêm hàm `bang()`. `CatalogLookup` của order-check lưu và tra khoá mảng qua bộ chuẩn hoá đó, thêm hàm `coTen()`; quy tắc CCHN khai `cotCoSo = 'ma_cskcb'` như 6 quy tắc BHYT. Bốn phép so PHP của XML3176 đi qua cùng bộ chuẩn hoá.

**Tech Stack:** Laravel 5.5, PHP 7.4 trên máy phát triển (giữ cú pháp tương thích PHP 7.0), MySQL `utf8_general_ci`, PHPUnit 6.

**Spec:** `docs/superpowers/specs/2026-09-21-doi-chieu-danh-muc-khong-phan-biet-hoa-thuong-va-theo-co-so-design.md`

## Global Constraints

- **Chỉ dùng `mb_strtolower(…, 'UTF-8')` qua `TextNormalizer::chuan()`.** Tuyệt đối không dùng `strtolower`/`strtoupper` trên tên: `strtolower('ĐƯỜNG HUYẾT')` cho ra `��Ờng huyẾt`.
- **Không sửa SQL, không đổi collation.** SQL vốn đã không phân biệt hoa thường; lỗi nằm ở bước so bằng PHP.
- **Thông điệp vi phạm/lỗi giữ nguyên chữ gốc** ở cả hai phía (chữ người dùng khai và chữ trong danh mục). Chỉ phép **so** được chuẩn hoá.
- **`TT_THAU` của VTYT giữ phép so lỏng `==` sau chuẩn hoá** — xem spec mục 4.6. Tên thì so nghiêm ngặt qua `TextNormalizer::bang()`.
- **Cú pháp tương thích PHP 7.0**: không nullable type, không arrow function, không typed property. Được dùng anonymous class (có từ 7.0).
- **Test:** `setUp()` không khai báo kiểu trả về (PHPUnit 6); **cấm `RefreshDatabase`** (`.env` trỏ CSDL phát triển thật `qlbv`). Test chạm CSDL thật chỉ được chèn dòng mã `ZZ…` và **xoá trong `finally`**, đúng khuôn `CatalogLookupTest::dieu_kien_loc_duoc_ap_khi_nap`.
- **Ba test cũ được ĐẢO, không xoá:** `BhytNameRuleTest::lech_hoa_thuong_van_bao_vi_pham`, `BhytNameRuleTest::lech_khoang_trang_giua_chu_thi_van_bao`, `Xml3TenDichVuTest::lech_hoa_thuong_van_tinh_la_lech`.
- **Không đụng:** `Qd130Xml*Checker`, `Xml2Checker`, `Xml3Checker` (bộ cũ), `order_check_ref_service_restriction`, dữ liệu kết quả XML3176 đã lưu.

## File Structure

| Tệp | Trách nhiệm | Task |
|---|---|---|
| `app/Services/Xml3176/Support/TextNormalizer.php` | Thêm `bang($a, $b)` | 1 |
| `app/Services/OrderCheck/Support/CatalogLookup.php` | Khoá mảng chuẩn hoá; thêm `coTen()` | 2 |
| `app/Services/OrderCheck/RuleHandlers/Bhyt/BhytNameMismatchRule.php` | So tên qua `coTen()` | 3 |
| `app/Services/OrderCheck/RuleHandlers/Clinical/StaffCertNotInCatalogRule.php` | Lọc cơ sở cho CCHN | 4 |
| `app/Services/Xml3176Xml3Checker.php` | `tenPheDuyet`, `tenLechDanhMuc`, `ttThauKhop` (mới), tên VTYT | 5, 6 |
| `app/Services/Xml3176Xml2Checker.php` | Tên thuốc | 6 |
| `tests/Unit/Xml3176/Support/TextNormalizerTest.php` | test `bang()` | 1 |
| `tests/Unit/OrderCheck/CatalogLookupTest.php` | test khoá + `coTen()` | 2 |
| `tests/Unit/OrderCheck/BhytNameRuleTest.php` | đảo 2 ca, thêm ca tiếng Việt | 3 |
| `tests/Unit/OrderCheck/StaffCertRuleTest.php` | 6 ca cơ sở + hoa thường | 4 |
| `tests/Unit/Xml3176/Xml3TenDichVuTest.php` | đảo 1 ca, thêm ca | 5 |
| `tests/Unit/Xml3176/Xml3TtThauKhopTest.php` (tạo) | test `ttThauKhop()` | 6 |

---

## Chuẩn bị: lấy mốc trước khi sửa (BẮT BUỘC)

Không làm bước này thì không đo được hiệu quả, và không phân biệt được đỏ mới với đỏ sẵn có.

- [ ] **Bước A: Mốc bộ test**

Run: `vendor/bin/phpunit 2>&1 | tail -3`
Ghi lại dòng `Tests: …, Errors: …, Failures: …`. Tiêu chí nghiệm thu cuối: số Errors/Failures **không tăng**.

- [ ] **Bước B: Mốc số vi phạm order-check trên dữ liệu thật**

Run (lưu kết quả vào scratchpad, **không** vào repo):
```bash
php artisan kiemtraylenh:thu --ngay=7 --lo=2000 > "$SCRATCH/dryrun-truoc.txt" 2>&1; cat "$SCRATCH/dryrun-truoc.txt"
```
`$SCRATCH` là thư mục scratchpad của phiên. Lệnh chỉ đếm, không ghi vi phạm. Cần kết nối HIS Oracle.

- [ ] **Bước C: Tìm hồ sơ XML3176 để nghiệm thu tay ở Task 7**

```bash
php -r "
require 'vendor/autoload.php'; \$app = require_once 'bootstrap/app.php';
\$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
\$r = Illuminate\Support\Facades\DB::select(\"
  SELECT x.id, x.ma_lk, x.ma_thuoc, x.ten_thuoc AS khai, c.ten_thuoc AS danh_muc
  FROM xml3176_xml2s x JOIN medicine_catalogs c ON c.ma_thuoc = x.ma_thuoc
  WHERE x.ten_thuoc COLLATE utf8_bin <> c.ten_thuoc COLLATE utf8_bin
    AND LOWER(x.ten_thuoc) COLLATE utf8_bin = LOWER(c.ten_thuoc) COLLATE utf8_bin
  LIMIT 5\");
foreach (\$r as \$d) { echo json_encode(\$d, JSON_UNESCAPED_UNICODE), PHP_EOL; }
echo 'So dong: ', count(\$r), PHP_EOL;
"
```
Dùng `COLLATE utf8_bin` vì `utf8_general_ci` còn **không phân biệt dấu** (`'a' = 'á'`) — so thường sẽ lẫn cả các cặp khác dấu. Ghi lại một `id` nếu có. **Không có dòng nào** thì ghi nhận điều đó; Task 7 bỏ phần nghiệm thu tay XML3176.

---

### Task 1: `TextNormalizer::bang()`

**Files:**
- Modify: `app/Services/Xml3176/Support/TextNormalizer.php`
- Test: `tests/Unit/Xml3176/Support/TextNormalizerTest.php`

**Interfaces:**
- Produces: `TextNormalizer::bang($a, $b): bool` — `true` khi `chuan($a) === chuan($b)`. Task 2, 5, 6 dùng.

- [ ] **Step 1: Viết test thất bại**

Thêm vào cuối class trong `tests/Unit/Xml3176/Support/TextNormalizerTest.php` (trước `}` cuối):

```php
    /** @test */
    public function bang_khong_phan_biet_hoa_thuong()
    {
        $this->assertTrue(TextNormalizer::bang('Paracetamol 500MG', 'paracetamol 500mg'));
    }

    /**
     * mb_strtolower chu khong phai strtolower: strtolower lam hong chu Viet co dau.
     *
     * @test
     */
    public function bang_dung_voi_chu_viet_co_dau_viet_hoa()
    {
        $this->assertTrue(TextNormalizer::bang('ĐƯỜNG HUYẾT MAO MẠCH', 'Đường huyết mao mạch'));
    }

    /** @test */
    public function bang_bo_qua_khoang_trang_thua()
    {
        $this->assertTrue(TextNormalizer::bang('  Thuoc   A ', 'Thuoc A'));
    }

    /** @test */
    public function bang_null_bang_chuoi_rong()
    {
        $this->assertTrue(TextNormalizer::bang(null, ''));
    }

    /** @test */
    public function khac_noi_dung_thi_khong_bang()
    {
        $this->assertFalse(TextNormalizer::bang('Thuoc A', 'Thuoc B'));
    }

    /**
     * So NGHIEM NGAT sau chuan hoa: '1e3' == '1000' la true trong PHP 7 neu so long.
     *
     * @test
     */
    public function khong_so_long_kieu_so_hoc()
    {
        $this->assertFalse(TextNormalizer::bang('1e3', '1000'));
    }

    /**
     * Chu khac dau KHONG duoc coi la bang - chi bo phan biet hoa thuong, khong bo dau.
     *
     * @test
     */
    public function khac_dau_thi_khong_bang()
    {
        $this->assertFalse(TextNormalizer::bang('Duong huyet', 'Đường huyết'));
    }
```

- [ ] **Step 2: Chạy để xác nhận đỏ**

Run: `vendor/bin/phpunit tests/Unit/Xml3176/Support/TextNormalizerTest.php`
Expected: ERROR — `Call to undefined method App\Services\Xml3176\Support\TextNormalizer::bang()`.

- [ ] **Step 3: Thêm hàm**

Trong `app/Services/Xml3176/Support/TextNormalizer.php`, thêm sau hàm `chuan()`:

```php
    /**
     * Hai chuoi co bang nhau sau khi chuan hoa khong.
     *
     * So NGHIEM NGAT (===). Phep so long (==) cua PHP 7 coi '1e3' == '1000' la bang.
     */
    public static function bang($a, $b): bool
    {
        return self::chuan($a) === self::chuan($b);
    }
```

- [ ] **Step 4: Chạy để xác nhận xanh**

Run: `vendor/bin/phpunit tests/Unit/Xml3176/Support/TextNormalizerTest.php`
Expected: PASS — 10 tests.

- [ ] **Step 5: Commit**

```bash
git add app/Services/Xml3176/Support/TextNormalizer.php tests/Unit/Xml3176/Support/TextNormalizerTest.php
git commit -m "feat(xml3176): TextNormalizer::bang so hai chuoi sau chuan hoa"
```

---

### Task 2: `CatalogLookup` — khoá chuẩn hoá và `coTen()`

**Files:**
- Modify: `app/Services/OrderCheck/Support/CatalogLookup.php`
- Test: `tests/Unit/OrderCheck/CatalogLookupTest.php`

**Interfaces:**
- Consumes: `TextNormalizer::chuan($s): string` (có sẵn).
- Produces: `CatalogLookup::coTen($ma, $ten, $ngayYmd = null, $maCskcb = null): bool` — Task 3 dùng. `coTrongDanhMuc()` / `tenTheoMa()` giữ nguyên chữ ký, nay không phân biệt hoa thường ở phần mã.

- [ ] **Step 1: Viết test thất bại**

Thêm vào cuối class trong `tests/Unit/OrderCheck/CatalogLookupTest.php` (trước `}` cuối):

```php
    /** @test */
    public function tra_ma_khong_phan_biet_hoa_thuong()
    {
        $lk = new CatalogLookup('service_catalogs', 'ma_dich_vu');
        $lk->datSanChoTest(['ABC01']);

        $this->assertTrue($lk->coTrongDanhMuc('abc01'));
        $this->assertTrue($lk->coTrongDanhMuc('Abc01'));
    }

    /**
     * Duong THAT cua loi: SQL (utf8_general_ci) keo ve dong 'ZZ3' khi hoi 'zz3', nhung ban
     * cu luu khoa mang theo NGUYEN chu trong DB roi tra bang NGUYEN chu cua y lenh - nen
     * 'zz3' khong bao gio thay 'ZZ3'. datSanChoTest khong di qua nap() nen khong bat duoc.
     *
     * @test
     */
    public function nap_tu_csdl_tra_duoc_bang_chu_khac_hoa_thuong()
    {
        DB::table('icd10_categories')->insert([
            ['icd_code' => 'ZZ3', 'icd_name' => 'Thu hoa thuong', 'is_active' => 1],
        ]);

        try {
            $lk = new CatalogLookup('icd10_categories', 'icd_code', null, null, null, ['is_active' => 1]);
            $lk->nap(['zz3']);

            $this->assertTrue($lk->coTrongDanhMuc('zz3'), 'SQL tim thay nhung PHP tra truot');
            $this->assertTrue($lk->coTrongDanhMuc('ZZ3'));
        } finally {
            DB::table('icd10_categories')->where('icd_code', 'ZZ3')->delete();
        }
    }

    /** @test */
    public function co_ten_khong_phan_biet_hoa_thuong_va_khoang_trang()
    {
        $lk = $this->traThuoc(['BH1' => [['ten' => 'Paracetamol 500mg', 'tu' => '', 'den' => '']]]);

        $this->assertTrue($lk->coTen('BH1', 'PARACETAMOL 500MG'));
        $this->assertTrue($lk->coTen('bh1', '  paracetamol   500mg '));
    }

    /** @test */
    public function co_ten_dung_voi_chu_viet_co_dau()
    {
        $lk = $this->traThuoc(['BH1' => [['ten' => 'Đường huyết mao mạch', 'tu' => '', 'den' => '']]]);

        $this->assertTrue($lk->coTen('BH1', 'ĐƯỜNG HUYẾT MAO MẠCH'));
    }

    /** @test */
    public function co_ten_khac_noi_dung_thi_false()
    {
        $lk = $this->traThuoc(['BH1' => [['ten' => 'Thuoc A', 'tu' => '', 'den' => '']]]);

        $this->assertFalse($lk->coTen('BH1', 'Thuoc B'));
    }

    /** @test */
    public function co_ten_rong_thi_false()
    {
        $lk = $this->traThuoc(['BH1' => [['ten' => 'Thuoc A', 'tu' => '', 'den' => '']]]);

        $this->assertFalse($lk->coTen('BH1', '   '));
        $this->assertFalse($lk->coTen('BH1', null));
    }

    /** @test */
    public function co_ten_ton_trong_loc_co_so()
    {
        $lk = $this->traCoSo(['A1' => [['ten' => 'Ten A', 'tu' => '', 'den' => '', 'cs' => '01929']]]);

        $this->assertTrue($lk->coTen('A1', 'TEN A', null, '01929'));
        $this->assertFalse($lk->coTen('A1', 'TEN A', null, '37470'));
    }

    /**
     * Chi phep SO duoc chuan hoa - ten tra ra de hien trong thong diep phai la chu goc.
     *
     * @test
     */
    public function ten_theo_ma_van_tra_chu_goc()
    {
        $lk = $this->traThuoc(['BH1' => [['ten' => 'Paracetamol 500MG', 'tu' => '', 'den' => '']]]);

        $this->assertSame(['Paracetamol 500MG'], $lk->tenTheoMa('bh1'));
    }
```

- [ ] **Step 2: Chạy để xác nhận đỏ**

Run: `vendor/bin/phpunit tests/Unit/OrderCheck/CatalogLookupTest.php`
Expected: FAIL/ERROR — `tra_ma_khong_phan_biet_hoa_thuong` và `nap_tu_csdl_tra_duoc_bang_chu_khac_hoa_thuong` thất bại (`false` thay vì `true`); các ca `co_ten_*` báo `Call to undefined method …::coTen()`.

- [ ] **Step 3: Sửa `CatalogLookup`**

Trong `app/Services/OrderCheck/Support/CatalogLookup.php`:

(a) Thêm `use` sau `use DB;`:

```php
use App\Services\Xml3176\Support\TextNormalizer;
```

(b) Trong `nap()`, thay dòng

```php
            $khoa = trim((string) $d[$this->cot]);
```

bằng

```php
            $khoa = $this->khoa($d[$this->cot]);
```

Giữ nguyên đoạn đầu `nap()` chuẩn bị mảng `$ma` gửi vào `whereIn` (chỉ `trim`) — SQL collation CI đã tìm đúng dòng; chỉ khoá **lưu** mới cần chuẩn hoá.

(c) Trong `dongConHieuLuc()`, thay dòng

```php
        $ma = trim((string) $ma);
```

bằng

```php
        $ma = $this->khoa($ma);
```

(d) Trong `datSanChoTest()`, thay cả hai chỗ `$this->dong[trim((string) $m)]` bằng `$this->dong[$this->khoa($m)]`.

(e) Thêm hai hàm sau `tenTheoMa()`:

```php
    /**
     * Ten khai co trung MOT dong danh muc con hieu luc cua ma nay khong.
     *
     * So dang CHUAN HOA (hoa thuong, khoang trang) qua TextNormalizer - thong nhat voi
     * INVALID_DRUG_NAME / INVALID_MATERIAL_NAME / INVALID_TEN_DICH_VU ben XML3176.
     * Muon ten goc de hien thong diep thi dung tenTheoMa().
     */
    public function coTen($ma, $ten, $ngayYmd = null, $maCskcb = null)
    {
        $can = $this->khoa($ten);

        if ($can === '') {
            return false;
        }

        foreach ($this->dongConHieuLuc($ma, $ngayYmd, $maCskcb) as $d) {
            if ($this->khoa($d['ten']) === $can) {
                return true;
            }
        }

        return false;
    }

    /**
     * Khoa so khop: trim, gop khoang trang, ha chu thuong (UTF-8).
     *
     * SQL da khong phan biet hoa thuong (utf8_general_ci) nen whereIn keo ve dung dong.
     * Nhung neu luu khoa theo NGUYEN chu trong DB roi tra bang NGUYEN chu cua y lenh thi
     * 'abc01' khong bao gio thay 'ABC01'. MOI khoa mang phai di qua ham nay.
     */
    protected function khoa($s)
    {
        return TextNormalizer::chuan($s);
    }
```

- [ ] **Step 4: Chạy để xác nhận xanh**

Run: `vendor/bin/phpunit tests/Unit/OrderCheck/CatalogLookupTest.php`
Expected: PASS — toàn bộ, gồm cả ca cũ `so_sanh_ma_khong_phan_biet_khoang_trang_thua`.

- [ ] **Step 5: Chạy cả nhóm order-check để chắc không vỡ quy tắc nào**

Run: `vendor/bin/phpunit tests/Unit/OrderCheck`
Expected: **toàn bộ xanh**. Ở bước này `BhytNameMismatchRule` chưa đổi (vẫn so tên tuyệt đối bằng `in_array`), nên hai test khoá hành vi cũ trong `BhytNameRuleTest` vẫn phải xanh. Có ca đỏ nào thì dừng lại điều tra trước khi commit.

- [ ] **Step 6: Commit**

```bash
git add app/Services/OrderCheck/Support/CatalogLookup.php tests/Unit/OrderCheck/CatalogLookupTest.php
git commit -m "fix(order-check): tra danh muc khong phan biet hoa thuong, them coTen()"
```

---

### Task 3: Quy tắc tên BHYT so qua `coTen()`

**Files:**
- Modify: `app/Services/OrderCheck/RuleHandlers/Bhyt/BhytNameMismatchRule.php`
- Test: `tests/Unit/OrderCheck/BhytNameRuleTest.php`

**Interfaces:**
- Consumes: `CatalogLookup::coTen($ma, $ten, $ngayYmd, $maCskcb): bool` (Task 2).

- [ ] **Step 1: Đảo hai test cũ và thêm một ca**

Trong `tests/Unit/OrderCheck/BhytNameRuleTest.php`, thay nguyên hàm

```php
    /** @test */
    public function lech_hoa_thuong_van_bao_vi_pham()
    {
        // So TUYET DOI, thong nhat voi Xml3176Xml2Checker.
        $r = $this->traThuoc(['BH1' => [['ten' => 'Thuoc A', 'tu' => '', 'den' => '']]]);

        $this->assertCount(1, $r->check($this->ctx([$this->dv(1, 'BH1', 6, 'THUOC A')])));
    }
```

bằng

```php
    /** @test */
    public function lech_hoa_thuong_khong_bao_vi_pham()
    {
        // So da chuan hoa, thong nhat voi INVALID_DRUG_NAME ben Xml3176Xml2Checker.
        // Truoc 2026-09-21 hai ben cung so TUYET DOI; nguoi dung chot bo phan biet hoa thuong.
        $r = $this->traThuoc(['BH1' => [['ten' => 'Thuoc A', 'tu' => '', 'den' => '']]]);

        $this->assertCount(0, $r->check($this->ctx([$this->dv(1, 'BH1', 6, 'THUOC A')])));
    }
```

và thay nguyên hàm

```php
    /** @test */
    public function lech_khoang_trang_giua_chu_thi_van_bao()
    {
        $r = $this->traThuoc(['BH1' => [['ten' => 'Thuoc A', 'tu' => '', 'den' => '']]]);

        $this->assertCount(1, $r->check($this->ctx([$this->dv(1, 'BH1', 6, 'Thuoc  A')])));
    }
```

bằng

```php
    /** @test */
    public function lech_khoang_trang_giua_chu_khong_bao()
    {
        // TextNormalizer gop khoang trang - nguoi dung chot dung lai bo chuan hoa co san.
        $r = $this->traThuoc(['BH1' => [['ten' => 'Thuoc A', 'tu' => '', 'den' => '']]]);

        $this->assertCount(0, $r->check($this->ctx([$this->dv(1, 'BH1', 6, 'Thuoc  A')])));
    }

    /** @test */
    public function chu_viet_co_dau_khac_hoa_thuong_khong_bao()
    {
        $r = $this->traThuoc(['BH1' => [['ten' => 'Đường huyết mao mạch', 'tu' => '', 'den' => '']]]);

        $this->assertCount(0, $r->check($this->ctx([$this->dv(1, 'BH1', 6, 'ĐƯỜNG HUYẾT MAO MẠCH')])));
    }
```

- [ ] **Step 2: Chạy để xác nhận đỏ**

Run: `vendor/bin/phpunit tests/Unit/OrderCheck/BhytNameRuleTest.php`
Expected: FAIL — ba ca trên báo `Failed asserting that actual size 1 matches expected size 0`.

- [ ] **Step 3: Sửa quy tắc**

Trong `app/Services/OrderCheck/RuleHandlers/Bhyt/BhytNameMismatchRule.php`, thay

```php
            if (in_array($tenKhai, $tenDanhMuc, true)) {
                continue;
            }
```

bằng

```php
            // So dang CHUAN HOA (hoa thuong, khoang trang), thong nhat voi INVALID_DRUG_NAME
            // ben XML3176. $tenDanhMuc van giu chu goc de hien trong thong diep ben duoi.
            if ($this->danhMuc->coTen($ma, $tenKhai, $ngay, $c->maCskcb)) {
                continue;
            }
```

Giữ nguyên lời gọi `tenTheoMa()` ngay phía trên: nó vẫn quyết định "mã không có hoặc hết hiệu lực thì im lặng", và cấp tên gốc cho thông điệp.

- [ ] **Step 4: Chạy để xác nhận xanh**

Run: `vendor/bin/phpunit tests/Unit/OrderCheck/BhytNameRuleTest.php`
Expected: PASS — toàn bộ, kể cả `ten_lech_thi_bao_vi_pham_va_neu_ca_hai_ten` (thông điệp vẫn có `Thuoc A`, `Thuoc B` nguyên chữ).

- [ ] **Step 5: Commit**

```bash
git add app/Services/OrderCheck/RuleHandlers/Bhyt/BhytNameMismatchRule.php tests/Unit/OrderCheck/BhytNameRuleTest.php
git commit -m "fix(order-check): so ten danh muc BHYT khong phan biet hoa thuong"
```

---

### Task 4: Quy tắc CCHN kiểm tra theo cơ sở KCB

**Files:**
- Modify: `app/Services/OrderCheck/RuleHandlers/Clinical/StaffCertNotInCatalogRule.php`
- Test: `tests/Unit/OrderCheck/StaffCertRuleTest.php`

**Interfaces:**
- Consumes: `CatalogLookup::__construct($bang, $cot, $cotTen, $cotTu, $cotDen, array $dieuKien, $cotCoSo)`, `sanSang($maCskcb)`, `coTrongDanhMuc($ma, $ngayYmd, $maCskcb)` — đều có sẵn; `OrderContext::$maCskcb` (có sẵn).

- [ ] **Step 1: Viết test thất bại**

Thêm vào cuối class trong `tests/Unit/OrderCheck/StaffCertRuleTest.php` (trước `}` cuối):

```php
    /** Ngu canh co ma co so; $cs = null nghia la ho so khong xac dinh duoc co so */
    private function ctxCs($cs, $cchnBacSi, $cchnNguoiTh = '')
    {
        $c = $this->ctx($cchnBacSi, $cchnNguoiTh);
        $c->maCskcb = $cs;

        return $c;
    }

    /** @param array $macchn ma => [ ['tu'=>, 'den'=>, 'cs'=>], ... ] */
    private function traCoSo(array $macchn)
    {
        $lkCchn = new CatalogLookup('medical_staffs', 'macchn', null, 'tu_ngay', 'den_ngay', [], 'ma_cskcb');
        $lkCchn->datSanChoTest([], $macchn);

        $lkBhxh = new CatalogLookup('medical_staffs', 'ma_bhxh', null, 'tu_ngay', 'den_ngay', [], 'ma_cskcb');
        $lkBhxh->datSanChoTest([], []);

        return new StaffCertNotInCatalogRule($lkCchn, $lkBhxh);
    }

    /** @test */
    public function cchn_o_dung_co_so_thi_khong_vi_pham()
    {
        $r = $this->traCoSo(['C1' => [['tu' => '', 'den' => '', 'cs' => '01929']]]);

        $this->assertCount(0, $r->check($this->ctxCs('01929', 'C1')));
    }

    /**
     * CCHN phai dang ky tai CHINH co so phat sinh ho so - nguoi dung chot 2026-09-21.
     *
     * @test
     */
    public function cchn_chi_o_co_so_khac_thi_vi_pham()
    {
        $r = $this->traCoSo(['C1' => [['tu' => '', 'den' => '', 'cs' => '01929']]]);

        $vi = $r->check($this->ctxCs('37470', 'C1'));

        $this->assertCount(1, $vi);
        $this->assertEquals('A_STAFF_CERT_NOT_IN_CATALOG', $vi[0]->ruleCode);
    }

    /** @test */
    public function dong_bo_trong_ma_co_so_dung_chung_moi_co_so()
    {
        $r = $this->traCoSo(['C1' => [['tu' => '', 'den' => '', 'cs' => '']]]);

        $this->assertCount(0, $r->check($this->ctxCs('37470', 'C1')));
        $this->assertCount(0, $r->check($this->ctxCs('01929', 'C1')));
    }

    /**
     * Co so chua nhap danh muc nhan vien thi quy tac IM LANG - khong duoc bao oan toan bo
     * chi vi bang co du lieu cua co so khac. Kiem luon rang ma co so duoc TRUYEN xuong
     * sanSang(): neu quy tac goi sanSang() khong doi so thi ca 01929 cung im lang.
     *
     * @test
     */
    public function co_so_chua_nhap_danh_muc_thi_im_lang()
    {
        $chiSanSang01929 = new class('medical_staffs', 'macchn', null, 'tu_ngay', 'den_ngay', [], 'ma_cskcb') extends CatalogLookup {
            public function sanSang($maCskcb = null)
            {
                return trim((string) $maCskcb) === '01929';
            }
        };

        $bhxh = new CatalogLookup('medical_staffs', 'ma_bhxh', null, 'tu_ngay', 'den_ngay', [], 'ma_cskcb');
        $bhxh->datRongChoTest();

        $r = new StaffCertNotInCatalogRule($chiSanSang01929, $bhxh);

        $this->assertCount(0, $r->check($this->ctxCs('01283', 'X9')), 'Co so chua co danh muc phai im lang');
        $this->assertCount(1, $r->check($this->ctxCs('01929', 'X9')), 'Co so da co danh muc phai xet');
    }

    /**
     * Ho so khong xac dinh duoc co so -> khong loc co so (spec 2026-07-28 muc 4.7).
     *
     * @test
     */
    public function khong_xac_dinh_co_so_thi_khong_loc()
    {
        $r = $this->traCoSo(['C1' => [['tu' => '', 'den' => '', 'cs' => '01929']]]);

        $this->assertCount(0, $r->check($this->ctxCs(null, 'C1')));
    }

    /** @test */
    public function cchn_khac_hoa_thuong_thi_khong_vi_pham()
    {
        $r = $this->traCoSo(['cchn-001/hno' => [['tu' => '', 'den' => '', 'cs' => '01929']]]);

        $this->assertCount(0, $r->check($this->ctxCs('01929', 'CCHN-001/HNO')));
    }
```

- [ ] **Step 2: Chạy để xác nhận đỏ**

Run: `vendor/bin/phpunit tests/Unit/OrderCheck/StaffCertRuleTest.php`
Expected: FAIL — `cchn_chi_o_co_so_khac_thi_vi_pham` (0 thay vì 1) và `co_so_chua_nhap_danh_muc_thi_im_lang` (ca 01929 ra 0 thay vì 1, vì quy tắc chưa truyền mã cơ sở vào `sanSang()`). Các ca còn lại có thể đã xanh — đúng, chúng khoá hành vi phải giữ.

- [ ] **Step 3: Sửa quy tắc**

Trong `app/Services/OrderCheck/RuleHandlers/Clinical/StaffCertNotInCatalogRule.php`:

(a) Thay thân hàm dựng:

```php
        $this->traCchn = $traCchn ?: new CatalogLookup('medical_staffs', 'macchn');
        $this->traMaBhxh = $traMaBhxh ?: new CatalogLookup('medical_staffs', 'ma_bhxh');
```

bằng

```php
        // Danh muc nhan vien cap theo CO SO: CCHN phai dang ky tai chinh co so phat sinh ho
        // so (nguoi dung chot 2026-09-21). Dong bo trong ma_cskcb dung chung moi co so.
        $this->traCchn = $traCchn ?: new CatalogLookup(
            'medical_staffs', 'macchn', null, 'tu_ngay', 'den_ngay', [], 'ma_cskcb'
        );
        $this->traMaBhxh = $traMaBhxh ?: new CatalogLookup(
            'medical_staffs', 'ma_bhxh', null, 'tu_ngay', 'den_ngay', [], 'ma_cskcb'
        );
```

(b) Thay

```php
        if (!$this->traCchn->sanSang()) {
```

bằng

```php
        // Tinh RIENG theo co so: co so chua nhap danh muc nhan vien thi im lang.
        if (!$this->traCchn->sanSang($c->maCskcb)) {
```

(c) Thay

```php
            if ($this->traCchn->coTrongDanhMuc($v['cchn'], $ngay)
                || $this->traMaBhxh->coTrongDanhMuc($v['cchn'], $ngay)) {
```

bằng

```php
            if ($this->traCchn->coTrongDanhMuc($v['cchn'], $ngay, $c->maCskcb)
                || $this->traMaBhxh->coTrongDanhMuc($v['cchn'], $ngay, $c->maCskcb)) {
```

- [ ] **Step 4: Chạy để xác nhận xanh**

Run: `vendor/bin/phpunit tests/Unit/OrderCheck/StaffCertRuleTest.php`
Expected: PASS — toàn bộ, kể cả các ca cũ (chúng dựng `CatalogLookup` không có `cotCoSo` và `ctx()` không đặt `maCskcb`, nên hành vi cũ giữ nguyên).

- [ ] **Step 5: Commit**

```bash
git add app/Services/OrderCheck/RuleHandlers/Clinical/StaffCertNotInCatalogRule.php tests/Unit/OrderCheck/StaffCertRuleTest.php
git commit -m "feat(order-check): quy tac CCHN kiem tra theo co so KCB"
```

---

### Task 5: XML3176 — tên DVKT không phân biệt hoa thường

**Files:**
- Modify: `app/Services/Xml3176Xml3Checker.php` (phần `use`; hàm `tenPheDuyet()`, `tenLechDanhMuc()` quanh dòng 69–114)
- Test: `tests/Unit/Xml3176/Xml3TenDichVuTest.php`

**Interfaces:**
- Consumes: `TextNormalizer::chuan()`.
- Produces: `Xml3176Xml3Checker::tenPheDuyet($dsDanhMuc): array` và `tenLechDanhMuc($tenKhai, array $tenPheDuyet): bool` — chữ ký giữ nguyên. Thêm `use TextNormalizer` mà Task 6 cũng dùng.

- [ ] **Step 1: Đảo test cũ và thêm ca**

Trong `tests/Unit/Xml3176/Xml3TenDichVuTest.php`, thay nguyên hàm

```php
    /** @test */
    public function lech_hoa_thuong_van_tinh_la_lech()
    {
        // So TUYET DOI, thong nhat voi INVALID_DRUG_NAME va INVALID_MATERIAL_NAME.
        $this->assertTrue(Xml3176Xml3Checker::tenLechDanhMuc('a', ['A']));
    }
```

bằng

```php
    /** @test */
    public function lech_hoa_thuong_khong_tinh_la_lech()
    {
        // So da chuan hoa, thong nhat voi INVALID_DRUG_NAME, INVALID_MATERIAL_NAME va
        // A_BHYT_SERVICE_NAME_MISMATCH ben order-check. Truoc 2026-09-21 ca bon so TUYET DOI.
        $this->assertFalse(Xml3176Xml3Checker::tenLechDanhMuc('a', ['A']));
    }

    /** @test */
    public function khoang_trang_giua_khong_tinh_la_lech()
    {
        $this->assertFalse(Xml3176Xml3Checker::tenLechDanhMuc('Do  dien   tim', ['Do dien tim']));
    }

    /** @test */
    public function chu_viet_co_dau_khac_hoa_thuong_khong_lech()
    {
        $this->assertFalse(Xml3176Xml3Checker::tenLechDanhMuc('ĐO ĐIỆN TIM', ['Đo điện tim']));
    }

    /** @test */
    public function khac_noi_dung_van_la_lech()
    {
        $this->assertTrue(Xml3176Xml3Checker::tenLechDanhMuc('Sieu am', ['Do dien tim']));
    }

    /**
     * Bo trung theo dang chuan hoa nhung GIU chu goc cua lan dau de hien trong mo ta loi.
     *
     * @test
     */
    public function gom_ten_bo_trung_theo_dang_chuan_hoa_giu_chu_goc()
    {
        $ra = Xml3176Xml3Checker::tenPheDuyet($this->danhMuc(['Đo A', 'đo a', 'ĐO  A', 'B']));

        $this->assertSame(['Đo A', 'B'], $ra);
    }
```

- [ ] **Step 2: Chạy để xác nhận đỏ**

Run: `vendor/bin/phpunit tests/Unit/Xml3176/Xml3TenDichVuTest.php`
Expected: FAIL — `lech_hoa_thuong_khong_tinh_la_lech`, `khoang_trang_giua_khong_tinh_la_lech`, `chu_viet_co_dau_khac_hoa_thuong_khong_lech`, `gom_ten_bo_trung_theo_dang_chuan_hoa_giu_chu_goc`.

- [ ] **Step 3: Sửa `Xml3176Xml3Checker`**

(a) Thêm `use` sau dòng `use App\Services\Xml3176\Support\TienTeCalculator;`:

```php
use App\Services\Xml3176\Support\TextNormalizer;
```

(b) Thay nguyên hàm `tenPheDuyet()` (docblock + thân) bằng:

```php
    /**
     * Gom ten phe duyet tu cac dong danh muc con hieu luc.
     *
     * Bo trung theo dang CHUAN HOA (hoa thuong, khoang trang) nhung giu chu GOC cua lan
     * xuat hien dau tien - de mo ta loi hien dung chu trong danh muc.
     *
     * @param \Illuminate\Support\Collection|array $dsDanhMuc cac dong ServiceCatalog
     * @return string[] da trim, bo rong, bo trung, giu thu tu
     */
    public static function tenPheDuyet($dsDanhMuc): array
    {
        $ten = [];
        $daCo = [];

        foreach ($dsDanhMuc as $d) {
            $t = trim((string) (is_object($d) ? $d->ten_dich_vu : $d));
            $khoa = TextNormalizer::chuan($t);

            if ($khoa !== '' && !isset($daCo[$khoa])) {
                $daCo[$khoa] = true;
                $ten[] = $t;
            }
        }

        return $ten;
    }
```

(c) Trong docblock của `tenLechDanhMuc()`, thay dòng

```php
     * So TUYET DOI, chi trim - giong INVALID_DRUG_NAME va INVALID_MATERIAL_NAME.
```

bằng

```php
     * So dang CHUAN HOA qua TextNormalizer::chuan() (hoa thuong, khoang trang) - giong
     * INVALID_DRUG_NAME, INVALID_MATERIAL_NAME va A_BHYT_*_NAME_MISMATCH ben order-check.
```

(d) Thay thân hàm `tenLechDanhMuc()`:

```php
        $tenKhai = trim((string) $tenKhai);

        if ($tenKhai === '' || empty($tenPheDuyet)) {
            return false;   // thieu ten la viec cua quy tac khac; danh muc khong co ten thi khong co gi de so
        }

        return !in_array($tenKhai, $tenPheDuyet, true);
```

bằng

```php
        $tenKhai = TextNormalizer::chuan($tenKhai);

        if ($tenKhai === '' || empty($tenPheDuyet)) {
            return false;   // thieu ten la viec cua quy tac khac; danh muc khong co ten thi khong co gi de so
        }

        foreach ($tenPheDuyet as $t) {
            if (TextNormalizer::chuan($t) === $tenKhai) {
                return false;
            }
        }

        return true;
```

- [ ] **Step 4: Chạy để xác nhận xanh**

Run: `vendor/bin/phpunit tests/Unit/Xml3176/Xml3TenDichVuTest.php`
Expected: PASS — toàn bộ, kể cả ca cũ `gom_ten_phe_duyet_da_trim_va_bo_trung` (`['  A  ', 'A', 'B', '', null]` → `['A', 'B']`).

- [ ] **Step 5: Commit**

```bash
git add app/Services/Xml3176Xml3Checker.php tests/Unit/Xml3176/Xml3TenDichVuTest.php
git commit -m "fix(xml3176): so ten DVKT voi danh muc khong phan biet hoa thuong"
```

---

### Task 6: XML3176 — tên thuốc, tên VTYT, `TT_THAU` VTYT

**Files:**
- Modify: `app/Services/Xml3176Xml3Checker.php` (thêm `ttThauKhop()`; dòng ~783 và ~796)
- Modify: `app/Services/Xml3176Xml2Checker.php:350`
- Create: `tests/Unit/Xml3176/Xml3TtThauKhopTest.php`

**Interfaces:**
- Consumes: `TextNormalizer::bang()` (Task 1), `TextNormalizer::chuan()`, `use TextNormalizer` trong `Xml3176Xml3Checker` (Task 5).
- Produces: `Xml3176Xml3Checker::ttThauKhop(array $danhMuc, array $hoSo): bool`.

- [ ] **Step 1: Viết test thất bại cho `ttThauKhop`**

Tạo `tests/Unit/Xml3176/Xml3TtThauKhopTest.php`:

```php
<?php

namespace Tests\Unit\Xml3176;

use Tests\TestCase;
use App\Services\Xml3176Xml3Checker;

/**
 * Bon phan TT_THAU cua VTYT (quyet dinh; goi thau; nhom; nam) so voi danh muc.
 *
 * Ban cu so bang == nen phan biet hoa thuong, trong khi TT_THAU cua THUOC o XML2 so bang
 * SQL LIKE (khong phan biet). Nay bo phan biet hoa thuong - nhung GIU phep so long, vi
 * '01' == '1' tung la khop va yeu cau khong doi dieu do.
 */
class Xml3TtThauKhopTest extends TestCase
{
    /** @test */
    public function khac_hoa_thuong_van_khop()
    {
        $this->assertTrue(Xml3176Xml3Checker::ttThauKhop(
            ['123/QĐ-BV', 'G1', 'N1', '2024'],
            ['123/qđ-bv', 'g1', 'n1', '2024']
        ));
    }

    /**
     * Giu ngu nghia cu: '01' == '1' la true trong PHP. Doi sang === se am tham sinh loi
     * MEDICAL_SUPPLY_NOT_IN_CATALOG moi cho ho so truoc day van qua.
     *
     * @test
     */
    public function so_dang_so_hoc_van_khop_nhu_cu()
    {
        $this->assertTrue(Xml3176Xml3Checker::ttThauKhop(
            ['123/QĐ-BV', '01', 'N1', '2024'],
            ['123/QĐ-BV', '1', 'N1', '2024']
        ));
    }

    /** @test */
    public function khac_noi_dung_thi_khong_khop()
    {
        $this->assertFalse(Xml3176Xml3Checker::ttThauKhop(
            ['123/QĐ-BV', 'G1', 'N1', '2024'],
            ['123/QĐ-BV', 'G2', 'N1', '2024']
        ));
    }

    /** @test */
    public function thieu_phan_thi_khong_khop()
    {
        $this->assertFalse(Xml3176Xml3Checker::ttThauKhop(
            ['123/QĐ-BV', 'G1', 'N1'],
            ['123/QĐ-BV', 'G1', 'N1', '2024']
        ));
    }
}
```

- [ ] **Step 2: Chạy để xác nhận đỏ**

Run: `vendor/bin/phpunit tests/Unit/Xml3176/Xml3TtThauKhopTest.php`
Expected: ERROR — `Call to undefined method App\Services\Xml3176Xml3Checker::ttThauKhop()`.

- [ ] **Step 3: Thêm `ttThauKhop()` vào `Xml3176Xml3Checker`**

Thêm ngay sau hàm `neuTenPheDuyet()`:

```php
    /**
     * Bon phan TT_THAU cua VTYT (quyet dinh; goi thau; nhom; nam) co khop danh muc khong.
     *
     * Bo phan biet hoa thuong qua TextNormalizer, thong nhat voi TT_THAU cua THUOC o XML2
     * (so bang SQL LIKE, von khong phan biet).
     *
     * So LONG (==) sau chuan hoa, CO CHU DICH: ban cu so == nen '01' va '1' la khop. Yeu
     * cau chi la bo phan biet hoa thuong - khong duoc am tham doi luon ngu nghia do.
     *
     * @param array $danhMuc 4 phan tach tu tt_thau cua dong danh muc
     * @param array $hoSo 4 phan tach tu tt_thau cua ho so
     */
    public static function ttThauKhop(array $danhMuc, array $hoSo): bool
    {
        for ($i = 0; $i < 4; $i++) {
            if (!array_key_exists($i, $danhMuc) || !array_key_exists($i, $hoSo)) {
                return false;
            }

            if (TextNormalizer::chuan($danhMuc[$i]) != TextNormalizer::chuan($hoSo[$i])) {
                return false;
            }
        }

        return true;
    }
```

- [ ] **Step 4: Chạy để xác nhận xanh**

Run: `vendor/bin/phpunit tests/Unit/Xml3176/Xml3TtThauKhopTest.php`
Expected: PASS — 4 tests.

- [ ] **Step 5: Dùng `ttThauKhop()` và `bang()` tại ba điểm so**

(a) Trong `app/Services/Xml3176Xml3Checker.php`, thay dòng

```php
                        if ($supplyDecision == $dataDecision && $supplyPackage == $dataPackage && $supplyGroup == $dataGroup && $supplyYear == $dataYear) {
```

bằng

```php
                        if (self::ttThauKhop(
                            [$supplyDecision, $supplyPackage, $supplyGroup, $supplyYear],
                            [$dataDecision, $dataPackage, $dataGroup, $dataYear]
                        )) {
```

(b) Cùng tệp, thay dòng

```php
                            if ($data->ten_vat_tu != $supply->ten_vat_tu) {
```

bằng

```php
                            // So dang chuan hoa (hoa thuong, khoang trang); mo ta loi van giu chu goc.
                            if (!TextNormalizer::bang($data->ten_vat_tu, $supply->ten_vat_tu)) {
```

(c) Trong `app/Services/Xml3176Xml2Checker.php` (đã có sẵn `use …TextNormalizer;`), thay dòng

```php
                        if ($data->ten_thuoc != $medicine->ten_thuoc) {
```

bằng

```php
                        // So dang chuan hoa (hoa thuong, khoang trang); mo ta loi van giu chu goc.
                        if (!TextNormalizer::bang($data->ten_thuoc, $medicine->ten_thuoc)) {
```

- [ ] **Step 6: Kiểm tra không còn phép so cũ và cú pháp hợp lệ**

Run:
```bash
grep -nE "ten_(thuoc|vat_tu) != |supplyDecision == " app/Services/Xml3176Xml2Checker.php app/Services/Xml3176Xml3Checker.php; echo "--- khong co dong nao la dung ---"; php -l app/Services/Xml3176Xml2Checker.php && php -l app/Services/Xml3176Xml3Checker.php
```
Expected: không có dòng nào trước vạch; hai dòng `No syntax errors detected`.

- [ ] **Step 7: Chạy nhóm test XML3176**

Run: `vendor/bin/phpunit tests/Unit/Xml3176`
Expected: PASS toàn bộ. Có ca đỏ thì đối chiếu với mốc ở Bước A — không được có ca đỏ mới trong nhóm này.

- [ ] **Step 8: Commit**

```bash
git add app/Services/Xml3176Xml2Checker.php app/Services/Xml3176Xml3Checker.php tests/Unit/Xml3176/Xml3TtThauKhopTest.php
git commit -m "fix(xml3176): so ten thuoc, ten VTYT, TT_THAU VTYT khong phan biet hoa thuong"
```

---

### Task 7: Nghiệm thu

**Files:** không sửa code.

- [ ] **Step 1: Toàn bộ bộ test**

Run: `vendor/bin/phpunit 2>&1 | tail -3`
Expected: số Tests tăng (thêm ca mới); **Errors và Failures bằng đúng mốc Bước A**. Có ca đỏ mới thì liệt kê tên lớp (`vendor/bin/phpunit 2>&1 | grep -E "^[0-9]+\) " | sed 's/::.*//' | sort | uniq -c`) và sửa trước khi đi tiếp.

- [ ] **Step 2: Đo lại order-check trên dữ liệu thật và so với mốc**

Run:
```bash
php artisan kiemtraylenh:thu --ngay=7 --lo=2000 > "$SCRATCH/dryrun-sau.txt" 2>&1; diff "$SCRATCH/dryrun-truoc.txt" "$SCRATCH/dryrun-sau.txt"
```
Expected:
- Mã/tên thuốc, DV, VTYT và ICD: **giảm hoặc giữ nguyên**. Tăng là sai — dừng lại điều tra.
- CCHN (`A_STAFF_CERT_NOT_IN_CATALOG`): **có thể tăng** — hệ quả chủ đích của việc lọc theo cơ sở. Ghi lại con số để báo người dùng.

- [ ] **Step 3: Nghiệm thu tay XML3176** (bỏ qua nếu Bước C không tìm được dòng nào)

Thay `ID_DONG` bằng `id` ghi ở Bước C:
```bash
php -r "
require 'vendor/autoload.php'; \$app = require_once 'bootstrap/app.php';
\$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
\$d = App\Models\BHYT\Xml3176Xml2::with('Xml3176Xml1')->find(ID_DONG);
app(App\Services\Xml3176Xml2Checker::class)->checkErrors(\$d);
\$loi = App\Models\BHYT\Xml3176ErrorResult::where('ma_lk', \$d->ma_lk)->where('xml', 'XML2')->where('stt', \$d->stt)->pluck('error_code')->all();
echo 'Loi sau khi kiem lai: ', json_encode(\$loi), PHP_EOL;
"
```
Expected: không còn mã lỗi kết thúc bằng `INVALID_DRUG_NAME`. Nếu `with('Xml3176Xml1')` báo sai tên quan hệ, mở `app/Models/BHYT/Xml3176Xml2.php` lấy đúng tên quan hệ mà `Xml3176Xml2Checker` truy cập (`$data->Xml3176Xml1`).

- [ ] **Step 4: Báo kết quả cho người dùng**

Báo: số test trước/sau, bảng so sánh dry-run trước/sau theo từng quy tắc, kết quả nghiệm thu tay XML3176, và nhắc hai điều ở spec mục 6: **kết quả XML3176 đã lưu không tự cập nhật** cho tới khi kiểm lại hồ sơ; **số vi phạm CCHN có thể tăng** — đơn vị cần bổ sung dòng cho bác sĩ làm ở nhiều cơ sở, hoặc bỏ trống `MA_CSKCB` cho dòng dùng chung.
