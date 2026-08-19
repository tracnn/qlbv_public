# Chứng từ điện tử PL02 — Giai đoạn 2B: Ba màn hình — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Người vận hành nạp được gói XML chứng từ trên web, xem danh sách hồ sơ đã nạp với trạng thái rõ ràng, và mở chi tiết từng chứng từ — trên nền luồng nạp đã dựng ở Giai đoạn 2A.

**Architecture:** Một controller mỏng (`BHYTCtdtController`) gọi xuống các lớp thuần đã tách sẵn: `CtdtTrangThaiGui` (suy trạng thái), `CtdtDanhSach` (dựng truy vấn + bộ lọc), `CtdtDetailTabs` (sinh tab động), `CtdtNhanTruong` (từ điển nhãn). Màn chi tiết dùng **một blade chung** duyệt `truong()` của lớp loại thay vì chín blade gần giống nhau.

**Tech Stack:** Laravel 5.5, PHP 7.4, PHPUnit 6, Blade + AdminLTE, Yajra DataTables (server-side), Dropzone, SweetAlert2.

## Global Constraints

- **Không dùng `: void` trên `setUp()`** — PHPUnit 6 khai `setUp()` không có kiểu trả về.
- **Không dùng cú pháp PHP 8** — không `match`, không `?->`, không constructor promotion, không named arguments, không `new (expr)`.
- **Không dùng `RefreshDatabase`** — `.env` trỏ `DB_DATABASE=qlbv` là CSDL phát triển thật. Dùng trait `Tests\Support\DungBangCtdtSqlite`.
- **Test phải TỰ ĐẶT cấu hình** bằng `config([...])`, không phụ thuộc `config/organization.php` của máy. Một lỗi loại này đã bị bắt ở Giai đoạn 2A.
- **Không `git add -f`** các tệp trong `.gitignore`: `config/filesystems.php`, `config/database.php`, `config/auth.php`, `config/organization.php`.
- **Không sửa `truong()`, `$fillable`, hay migration nào** — ba nơi khai cột đang khớp 100% và có lưới an toàn `tests/Unit/Ctdt/CtdtToanVenTest.php` canh.
- **Không tạo role mới** — dùng lại `xml-man`, đúng đặc tả mục 6.2. Không cần migration quyền.
- **Giai đoạn 2B KHÔNG có job, không gọi mạng.** `CheckCtdtJob`, `SignCtdtJob`, `CtdtSubmitService` thuộc Giai đoạn 3–4. Ba route `export-xlsx`, `ky-va-gui`, `job-status` trong đặc tả mục 6.2 **để lại cho giai đoạn của chúng** — chưa có gì để nối vào.
- **Baseline (2026-08-19):** `php vendor/bin/phpunit --testsuite Unit` cho `Errors: 4, Failures: 7`; `--testsuite Feature` cho `Errors: 8, Failures: 4`. `tests/Unit/Ctdt` hiện `OK (145 tests)`.
- **Đặc tả nguồn:** `docs/superpowers/specs/2026-08-19-chung-tu-dien-tu-pl02-design.md` mục 6, và mục 12.2 (bảy rủi ro đã nhận diện).

---

## Hai quyết định đã chốt

**(0) Màn nạp xử lý ĐỒNG BỘ, lệch đặc tả mục 6.4.** Đặc tả chọn "chỉ lưu tệp rồi đẩy job" vì
máy chủ giới hạn PHP 128MB/120s. Nhưng `BHYTXml3176Controller::uploadData` đang parse ngay trong
request và **tự nâng giới hạn** bằng `ini_set('memory_limit', '512M')` + `set_time_limit(600)` —
lý do trong đặc tả yếu hơn tưởng. Đi đường đồng bộ thì kết quả hiện ngay, không cần bảng theo dõi
tiến độ. Task 6 nâng giới hạn đúng cách đó.

**(1) Tệp nạp hỏng ngay từ đầu chỉ hiện lỗi trên màn nạp**, không thêm bảng nhật ký. Giống XML3176
đang làm. Cái giá: đóng trình duyệt là mất dấu vết — chấp nhận ở giai đoạn này.

**(2) Nạp lại hồ sơ đã gửi thì cảnh báo rõ nhưng vẫn cho ghi đè.** Kết quả nạp phải nêu đích danh
hồ sơ nào đã gửi ngày nào với `MaGD` nào mà nay bị đè. Dấu vết cũ vẫn nằm trong `lich_su_gui`.

---

## File Structure

**Tạo mới:**

| Tệp | Trách nhiệm |
|---|---|
| `app/Services/Ctdt/CtdtMacskcb.php` | Phân giải mã cơ sở ba bước — công khai để màn nạp xem trước được |
| `app/Services/Ctdt/CtdtTrangThaiGui.php` | Suy trạng thái gửi của một hồ sơ (hàm thuần) |
| `app/Services/Ctdt/CtdtDanhSach.php` | Dựng truy vấn danh sách và áp bộ lọc |
| `app/Services/Ctdt/CtdtDetailTabs.php` | Sinh danh sách tab từ chứng từ hồ sơ thực có |
| `app/Services/Ctdt/CtdtNhanTruong.php` | Từ điển nhãn tiếng Việt cho tên thẻ PL02 |
| `app/Http/Controllers/BHYT/BHYTCtdtController.php` | Controller mỏng cho ba màn hình |
| `resources/views/bhyt/ctdt/index.blade.php` | Màn danh sách |
| `resources/views/bhyt/ctdt/import.blade.php` | Màn nạp tệp |
| `resources/views/bhyt/ctdt/detail.blade.php` | Khung màn chi tiết + thanh tab |
| `resources/views/bhyt/ctdt/tab-chung-tu.blade.php` | **Một** blade chung cho cả chín loại |
| `resources/views/bhyt/ctdt/tab-xml-goc.blade.php` | Tab xem XML nguyên văn |
| `resources/views/bhyt/ctdt/partials/search.blade.php` | Khối bộ lọc |
| `tests/Unit/Ctdt/*Test.php` | 6 tệp test |

**Sửa:**

| Tệp | Sửa gì |
|---|---|
| `app/Services/Ctdt/CtdtImporter.php` | Thêm `nhapTuTep()`; dùng `CtdtMacskcb`; ghi nhận hồ sơ đã gửi bị đè |
| `app/Services/Ctdt/CtdtImportResult.php` | Thêm `$maGdBiGhiDe` |
| `app/Services/Ctdt/CtdtImportFileResult.php` | Thêm `$dsGhiDeDaGui` |
| `routes/web.php` | Thêm 7 route vào group `checkrole:xml-man`, prefix `bhyt/` |
| `config/adminlte.php` | Thêm mục menu "Chứng từ điện tử" |

---

## Task 1: Nạp từ tệp, phân giải mã cơ sở công khai, cảnh báo ghi đè

**Files:**
- Create: `app/Services/Ctdt/CtdtMacskcb.php`
- Modify: `app/Services/Ctdt/CtdtImporter.php`
- Modify: `app/Services/Ctdt/CtdtImportResult.php`
- Modify: `app/Services/Ctdt/CtdtImportFileResult.php`
- Test: `tests/Unit/Ctdt/CtdtNhapTuTepTest.php`

**Interfaces:**
- Consumes: `CtdtImporter::nhapTuChuoi($xml, array $tuyChon)`, `CtdtGoiParser`, model `CtdtHoSo` (Giai đoạn 2A)
- Produces:
  - `App\Services\Ctdt\CtdtMacskcb::phanGiai($maTrongGoi, $maNguoiChon)` → `string`, ném `ThieuMacskcbException` khi cạn cả ba nguồn, ném `MacskcbKhongHopLeException` khi dài quá 5
  - `CtdtImporter::nhapTuTep($duongDan, array $tuyChon = [])` → `CtdtImportFileResult`
  - `CtdtImportResult::thanhCong($maHoSo, array $loaiDaXuLy, $maGdBiGhiDe = null)`, thuộc tính công khai `$maGdBiGhiDe`
  - `CtdtImportFileResult::$dsGhiDeDaGui` — mảng `['ma_ho_so' => string, 'ma_gd' => string]`

**Vì sao task này đứng đầu:** rủi ro #1 và #2 ở mục 12.2 của đặc tả. Nếu controller tự đọc tệp
thì lệnh Console ở Giai đoạn 5 sẽ viết lại bản thứ hai — đúng căn bệnh mà `CtdtImporter` sinh ra
để tránh. Và chuỗi phân giải mã cơ sở đang nằm trong một hàm private nên màn nạp không xem
trước được.

- [ ] **Step 1: Viết test đỏ**

Tạo `tests/Unit/Ctdt/CtdtNhapTuTepTest.php`:

```php
<?php

namespace Tests\Unit\Ctdt;

use Tests\TestCase;
use Tests\Support\DungBangCtdtSqlite;
use Tests\Support\GoiCtdtMau;
use App\Services\Ctdt\CtdtImporter;
use App\Services\Ctdt\CtdtMacskcb;
use App\Services\Ctdt\Loi\ThieuMacskcbException;
use App\Services\Ctdt\Loi\MacskcbKhongHopLeException;
use App\Models\BHYT\Ctdt\CtdtHoSo;

class CtdtNhapTuTepTest extends TestCase
{
    use DungBangCtdtSqlite;
    use GoiCtdtMau;

    /** @var CtdtImporter */
    private $importer;

    /** @var array duong dan tep tam da tao, de don o tearDown */
    private $tepTam = [];

    protected function setUp()
    {
        parent::setUp();
        $this->chuanBiBangCtdt();
        $this->importer = new CtdtImporter();
        config(['organization.BHYT.ma_cskcb' => '01013']);
    }

    protected function tearDown()
    {
        foreach ($this->tepTam as $duongDan) {
            if (file_exists($duongDan)) {
                unlink($duongDan);
            }
        }

        parent::tearDown();
    }

    private function tepTam($noiDung)
    {
        $duongDan = tempnam(sys_get_temp_dir(), 'ctdt') . '.xml';
        file_put_contents($duongDan, $noiDung);
        $this->tepTam[] = $duongDan;

        return $duongDan;
    }

    /** @test */
    public function nhap_tu_tep_cho_ket_qua_giong_nhap_tu_chuoi()
    {
        $xml = $this->goiCt2025([[$this->chungTu('CT03', ['MA_YTE' => 'YT001'])]]);

        $kq = $this->importer->nhapTuTep($this->tepTam($xml));

        $this->assertTrue($kq->thanhCong, (string) $kq->lyDoThatBai);
        $this->assertSame(['YT001'], $kq->dsMaHoSo);
        $this->assertSame(1, CtdtHoSo::count());
    }

    /** @test */
    public function nhap_tu_tep_ghi_duong_dan_goc_khi_khong_truyen_tuy_chon()
    {
        // Duong dan tep la thu duy nhat noi lai ho so nay den tu dau. Bat nguoi goi tu
        // truyen lai mot lan nua la moi khi mot noi goi quen mat dau vet.
        $xml = $this->goiCt2025([[$this->chungTu('CT03', ['MA_YTE' => 'YT001'])]]);
        $duongDan = $this->tepTam($xml);

        $this->importer->nhapTuTep($duongDan);

        $this->assertSame($duongDan, CtdtHoSo::first()->duong_dan_goc);
    }

    /** @test */
    public function tuy_chon_duong_dan_goc_truyen_vao_thi_thang()
    {
        $xml = $this->goiCt2025([[$this->chungTu('CT03', ['MA_YTE' => 'YT001'])]]);

        $this->importer->nhapTuTep($this->tepTam($xml), ['duong_dan_goc' => 'inbox/goi-1.xml']);

        $this->assertSame('inbox/goi-1.xml', CtdtHoSo::first()->duong_dan_goc);
    }

    /** @test */
    public function tep_khong_doc_duoc_thi_that_bai_som_khong_nem()
    {
        $kq = $this->importer->nhapTuTep('C:\\khong\\ton\\tai\\goi.xml');

        $this->assertFalse($kq->thanhCong);
        $this->assertContains('khong doc duoc', mb_strtolower((string) $kq->lyDoThatBai));
        $this->assertSame(0, CtdtHoSo::count());
    }

    /** @test */
    public function tep_rong_thi_that_bai_som()
    {
        $kq = $this->importer->nhapTuTep($this->tepTam(''));

        $this->assertFalse($kq->thanhCong);
        $this->assertSame(0, CtdtHoSo::count());
    }

    /** @test */
    public function phan_giai_ma_co_so_uu_tien_gia_tri_trong_goi()
    {
        $this->assertSame('01929', CtdtMacskcb::phanGiai('01929', '37470'));
    }

    /** @test */
    public function phan_giai_lui_ve_lua_chon_cua_nguoi_nap()
    {
        $this->assertSame('37470', CtdtMacskcb::phanGiai(null, '37470'));
    }

    /** @test */
    public function phan_giai_lui_ve_cau_hinh_don_vi()
    {
        config(['organization.BHYT.ma_cskcb' => '01013']);

        $this->assertSame('01013', CtdtMacskcb::phanGiai(null, null));
    }

    /** @test */
    public function can_ca_ba_nguon_thi_nem()
    {
        config(['organization.BHYT.ma_cskcb' => '']);

        $this->expectException(ThieuMacskcbException::class);

        CtdtMacskcb::phanGiai(null, null);
    }

    /** @test */
    public function ma_dai_qua_nam_ky_tu_thi_nem()
    {
        $this->expectException(MacskcbKhongHopLeException::class);

        CtdtMacskcb::phanGiai('ABCDEFGHIJ', null);
    }

    /** @test */
    public function nap_de_len_ho_so_da_gui_thi_ket_qua_neu_dich_danh()
    {
        // Nguoi dung duoc phep ghi de, nhung phai BIET minh vua xoa mat trang thai gui cua
        // ho so nao. Im lang o day nghia la mot ho so da doi soat voi BHXH bi mat dau vet
        // ma khong ai hay.
        $xml = $this->goiCt2025([[$this->chungTu('CT03', ['MA_YTE' => 'YT001'])]]);

        $this->importer->nhapTuChuoi($xml);
        CtdtHoSo::where('ma_ho_so', 'YT001')->update([
            'ma_gd'        => 'HS_123456',
            'ma_ket_qua'   => '200',
            'submitted_at' => '2026-08-19 10:00:00',
        ]);

        $kq = $this->importer->nhapTuChuoi($xml);

        $this->assertTrue($kq->thanhCong);
        $this->assertCount(1, $kq->dsGhiDeDaGui);
        $this->assertSame('YT001', $kq->dsGhiDeDaGui[0]['ma_ho_so']);
        $this->assertSame('HS_123456', $kq->dsGhiDeDaGui[0]['ma_gd']);
    }

    /** @test */
    public function ho_so_chua_tung_gui_thi_khong_vao_danh_sach_canh_bao()
    {
        $xml = $this->goiCt2025([[$this->chungTu('CT03', ['MA_YTE' => 'YT001'])]]);

        $this->importer->nhapTuChuoi($xml);
        $kq = $this->importer->nhapTuChuoi($xml);

        $this->assertSame([], $kq->dsGhiDeDaGui);
    }
}
```

- [ ] **Step 2: Chạy test để xác nhận đỏ**

```bash
php vendor/bin/phpunit tests/Unit/Ctdt/CtdtNhapTuTepTest.php
```

Kỳ vọng: đỏ — `Class 'App\Services\Ctdt\CtdtMacskcb' not found`.

- [ ] **Step 3: Viết `CtdtMacskcb`**

Tạo `app/Services/Ctdt/CtdtMacskcb.php`:

```php
<?php

namespace App\Services\Ctdt;

use App\Services\Ctdt\Loi\ThieuMacskcbException;
use App\Services\Ctdt\Loi\MacskcbKhongHopLeException;

/**
 * Phan giai ma co so KCB cho mot goi chung tu, theo ba bac.
 *
 * VI SAO LA LOP RIENG CONG KHAI chu khong phai ham private trong CtdtImporter: man nap tep
 * can hien "goi nay se dung ma co so X, dung khong?" TRUOC khi ghi. De logic trong importer
 * thi man hinh phai nhan ban no, va hai ban se lech nhau - dung cai benh ma module nay
 * sinh ra de tranh.
 *
 * VI SAO CAN BAC THU BA: goi HSDLGCS (giay chung sinh) KHONG mang ma co so o bat ky the nao.
 * The MA_TTDV trong giong nhung muc 6 phan IV cua PL02 dinh nghia la "ma so BHXH cua Thu
 * truong co so KBCB" - ma cua mot CON NGUOI.
 */
class CtdtMacskcb
{
    /** Do dai toi da, khop cot ctdt_ho_so.macskcb varchar(5). */
    const DAI_TOI_DA = 5;

    /**
     * @param string|null $maTrongGoi  Gia tri doc duoc tu XML (CtdtGoiParser::macskcb)
     * @param string|null $maNguoiChon Gia tri nguoi nap chon tren man hinh
     * @return string
     * @throws ThieuMacskcbException khi can ca ba nguon
     * @throws MacskcbKhongHopLeException khi dai qua gioi han cot
     */
    public static function phanGiai($maTrongGoi, $maNguoiChon)
    {
        $ma = trim((string) $maTrongGoi);

        if ($ma === '') {
            $ma = trim((string) $maNguoiChon);
        }

        if ($ma === '') {
            $ma = trim((string) config('organization.BHYT.ma_cskcb', ''));
        }

        if ($ma === '') {
            throw new ThieuMacskcbException(
                'Khong xac dinh duoc ma co so KCB: goi khong khai, nguoi nap khong chon,'
                . ' va cau hinh organization.BHYT.ma_cskcb dang trong'
            );
        }

        // SQLite khong cuong che do dai nen test khong bao gio bat duoc. Tren MySQL strict
        // thi QueryException KHONG mang CtdtLoiNap nen thoat khoi catch cua importer va do
        // ca lan nap; MySQL long thi cat cut IM LANG va ho so duoc gui len cong voi ma co
        // so SAI. Chan tai day.
        if (strlen($ma) > self::DAI_TOI_DA) {
            throw new MacskcbKhongHopLeException(
                'Ma co so KCB "' . $ma . '" dai ' . strlen($ma) . ' ky tu, toi da '
                . self::DAI_TOI_DA
            );
        }

        return $ma;
    }
}
```

- [ ] **Step 4: Thêm `$maGdBiGhiDe` vào `CtdtImportResult`**

Trong `app/Services/Ctdt/CtdtImportResult.php`, thêm thuộc tính và tham số:

```php
    /** @var string|null MaGD cua ban da gui vua bi ghi de, null neu chua tung gui */
    public $maGdBiGhiDe;

    public static function thanhCong($maHoSo, array $loaiDaXuLy, $maGdBiGhiDe = null)
    {
        $kq = new self();
        $kq->thanhCong    = true;
        $kq->maHoSo       = $maHoSo;
        $kq->loaiDaXuLy   = $loaiDaXuLy;
        $kq->maGdBiGhiDe  = $maGdBiGhiDe;

        return $kq;
    }
```

Giữ nguyên `thatBai()` và các thuộc tính khác.

- [ ] **Step 5: Thêm `$dsGhiDeDaGui` vào `CtdtImportFileResult`**

Trong `app/Services/Ctdt/CtdtImportFileResult.php`, thêm thuộc tính và gom trong `tu()`:

```php
    /** @var array Cac ho so DA TUNG GUI vua bi ghi de: ['ma_ho_so' =>, 'ma_gd' =>] */
    public $dsGhiDeDaGui = [];
```

Trong vòng lặp của `tu()`, ở nhánh `$r->thanhCong`, thêm ngay sau `$kq->dsMaHoSo[] = $r->maHoSo;`:

```php
                if (!empty($r->maGdBiGhiDe)) {
                    // Nguoi dung duoc phep ghi de, nhung man hinh phai neu dich danh ho so
                    // nao vua mat trang thai gui - im lang o day nghia la mot ho so da doi
                    // soat voi BHXH mat dau vet ma khong ai hay.
                    $kq->dsGhiDeDaGui[] = ['ma_ho_so' => $r->maHoSo, 'ma_gd' => $r->maGdBiGhiDe];
                }
```

- [ ] **Step 6: Sửa `CtdtImporter` — dùng `CtdtMacskcb`, thêm `nhapTuTep()`, ghi nhận ghi đè**

Trong `app/Services/Ctdt/CtdtImporter.php`:

Thêm `use App\Models\BHYT\Ctdt\CtdtHoSo;` và `use App\Services\Ctdt\CtdtMacskcb;` ở đầu tệp.

Thay **toàn bộ** hàm private `macskcb()` bằng lời gọi lớp mới. Chỗ gọi trong `nhapTuChuoi()` đổi thành:

```php
            $macskcb = CtdtMacskcb::phanGiai(
                CtdtGoiParser::macskcb($goi, $dichVu),
                isset($tuyChon['macskcb']) ? $tuyChon['macskcb'] : null
            );
```

Xóa hàm private `macskcb()` cũ — nó đã chuyển hết sang `CtdtMacskcb`.

Thêm phương thức công khai mới:

```php
    /**
     * Nhap tu mot tep tren dia.
     *
     * VI SAO NAM O DAY chu khong o controller: man tai len (Giai doan 2B) va lenh console
     * (Giai doan 5) deu can no. De o controller thi lenh console se viet lai ban thu hai, va
     * hai ban se lech nhau - dung dieu da xay ra that voi XML3176.
     *
     * @param string $duongDan
     * @param array  $tuyChon  macskcb, imported_by, duong_dan_goc - deu tuy chon
     * @return CtdtImportFileResult
     */
    public function nhapTuTep($duongDan, array $tuyChon = [])
    {
        if (!is_file($duongDan) || !is_readable($duongDan)) {
            return CtdtImportFileResult::thatBaiSom('Khong doc duoc tep: ' . $duongDan);
        }

        $noiDung = file_get_contents($duongDan);

        if ($noiDung === false) {
            return CtdtImportFileResult::thatBaiSom('Khong doc duoc tep: ' . $duongDan);
        }

        // Mac dinh ghi lai chinh duong dan da doc. Bat noi goi tu truyen lai mot lan nua la
        // moi khi mot noi goi quen mat dau vet nguon cua ho so.
        if (!array_key_exists('duong_dan_goc', $tuyChon)) {
            $tuyChon['duong_dan_goc'] = $duongDan;
        }

        return $this->nhapTuChuoi($noiDung, $tuyChon);
    }
```

Trong `nhapMotHoSo()`, ngay sau khi có `$maHoSo` và **trước** `DB::transaction`, đọc mã giao dịch cũ:

```php
            // Doc TRUOC transaction: sau khi luu() chay xong thi cot nay da bi reset ve null.
            $maGdBiGhiDe = CtdtHoSo::where('ma_ho_so', $maHoSo)->value('ma_gd');
```

Và đổi dòng trả về thành:

```php
            return CtdtImportResult::thanhCong(
                $maHoSo,
                array_column($chungTu, 'loai_ho_so'),
                $maGdBiGhiDe
            );
```

- [ ] **Step 7: Chạy test để xác nhận xanh**

```bash
php vendor/bin/phpunit tests/Unit/Ctdt
```

Kỳ vọng: xanh, số test tăng thêm 12 so với 145.

- [ ] **Step 8: Commit**

```bash
git add app/Services/Ctdt tests/Unit/Ctdt/CtdtNhapTuTepTest.php
git commit -m "feat(ctdt): nhap tu tep, phan giai ma co so cong khai, canh bao ghi de ho so da gui"
```

---

## Task 2: Route, menu và controller khung

**Files:**
- Create: `app/Http/Controllers/BHYT/BHYTCtdtController.php`
- Create: `resources/views/bhyt/ctdt/index.blade.php` (khung tối thiểu, Task 5 dựng đầy)
- Create: `resources/views/bhyt/ctdt/import.blade.php` (khung tối thiểu, Task 6 dựng đầy)
- Modify: `routes/web.php` (thêm vào group `checkrole:xml-man`, ngay sau khối route `xml3176/...`)
- Modify: `config/adminlte.php` (thêm mục menu sau khối `'text' => 'Xml 3176'`)
- Test: `tests/Unit/Ctdt/CtdtRouteTest.php`

**Interfaces:**
- Consumes: `App\Services\BHYT\DanhSachCoSo::danhSach()` (đã có, trả `[ma_cskcb => nhãn]`)
- Produces: 7 route đặt tên `bhyt.ctdt.index`, `bhyt.ctdt.fetch-data`, `bhyt.ctdt.import.index`, `bhyt.ctdt.upload`, `bhyt.ctdt.detail`, `bhyt.ctdt.detail.tab`, `bhyt.ctdt.delete`; controller `App\Http\Controllers\BHYT\BHYTCtdtController` với `index()`, `importIndex()`

- [ ] **Step 1: Viết test đỏ**

Tạo `tests/Unit/Ctdt/CtdtRouteTest.php`:

```php
<?php

namespace Tests\Unit\Ctdt;

use Tests\TestCase;
use Illuminate\Support\Facades\Route;

/**
 * Canh route va quyen cua module chung tu dien tu.
 *
 * VI SAO DOC QUA Route facade chu khong quet chuoi trong routes/web.php: quet chuoi se xanh
 * ca khi route nam ngoai group quyen, vi chuoi 'checkrole:xml-man' van xuat hien o cho khac
 * trong tep. Doc middleware da gom cua tung route la cach duy nhat noi dung ve quyen.
 */
class CtdtRouteTest extends TestCase
{
    public function cacRoute()
    {
        return [
            'bhyt.ctdt.index',
            'bhyt.ctdt.fetch-data',
            'bhyt.ctdt.import.index',
            'bhyt.ctdt.upload',
            'bhyt.ctdt.detail',
            'bhyt.ctdt.detail.tab',
            'bhyt.ctdt.delete',
        ];
    }

    /** @test */
    public function bay_route_deu_duoc_dang_ky()
    {
        foreach ($this->cacRoute() as $ten) {
            $this->assertNotNull(Route::getRoutes()->getByName($ten), 'Thieu route ' . $ten);
        }
    }

    /** @test */
    public function moi_route_deu_yeu_cau_quyen_xml_man()
    {
        // Quen mot route ngoai group quyen nghia la bat ky ai dang nhap cung xem duoc ho so
        // benh nhan - va khong co gi bao dong.
        foreach ($this->cacRoute() as $ten) {
            $middleware = Route::getRoutes()->getByName($ten)->gatherMiddleware();

            $this->assertContains('checkrole:xml-man', $middleware, $ten . ' thieu quyen xml-man');
        }
    }

    /** @test */
    public function xoa_ho_so_chi_danh_cho_superadministrator()
    {
        // Xoa mot ho so da co MaGD la xoa dau vet doi soat voi BHXH.
        $middleware = Route::getRoutes()->getByName('bhyt.ctdt.delete')->gatherMiddleware();

        $this->assertContains('checkrole:superadministrator', $middleware);
    }

    /** @test */
    public function route_tro_dung_controller()
    {
        $mongDoi = 'App\Http\Controllers\BHYT\BHYTCtdtController';

        foreach ($this->cacRoute() as $ten) {
            $action = Route::getRoutes()->getByName($ten)->getActionName();

            $this->assertStringStartsWith($mongDoi . '@', $action, $ten . ' tro sai controller');
        }
    }

    /** @test */
    public function route_xoa_dung_phuong_thuc_DELETE()
    {
        $this->assertContains('DELETE', Route::getRoutes()->getByName('bhyt.ctdt.delete')->methods());
    }

    /** @test */
    public function route_tai_len_dung_phuong_thuc_POST()
    {
        $this->assertContains('POST', Route::getRoutes()->getByName('bhyt.ctdt.upload')->methods());
    }

    /** @test */
    public function menu_khai_dung_quyen_va_dung_route()
    {
        // Menu di qua AppServiceProvider::filterMenu, ham do CHI kiem hasRole(). Khai thieu
        // checkrole thi moi nguoi dang nhap deu thay muc nay trong menu roi bam vao bi 403.
        $menu = config('adminlte.menu');

        $muc = $this->timMuc($menu, 'Chứng từ điện tử');

        $this->assertNotNull($muc, 'Thieu muc menu "Chung tu dien tu" trong config/adminlte.php');
        $this->assertSame('xml-man', $muc['checkrole']);

        $tenRoute = [];

        foreach ($muc['submenu'] as $con) {
            $tenRoute[] = $con['route'];
        }

        $this->assertContains('bhyt.ctdt.index', $tenRoute);
        $this->assertContains('bhyt.ctdt.import.index', $tenRoute);
    }

    private function timMuc($items, $nhan)
    {
        foreach ((array) $items as $item) {
            if (is_array($item) && isset($item['text']) && $item['text'] === $nhan) {
                return $item;
            }

            if (is_array($item) && isset($item['submenu'])) {
                $tim = $this->timMuc($item['submenu'], $nhan);

                if ($tim !== null) {
                    return $tim;
                }
            }
        }

        return null;
    }
}
```

- [ ] **Step 2: Chạy test để xác nhận đỏ**

```bash
php vendor/bin/phpunit tests/Unit/Ctdt/CtdtRouteTest.php
```

Kỳ vọng: đỏ — `Thieu route bhyt.ctdt.index`.

- [ ] **Step 3: Viết controller khung**

Tạo `app/Http/Controllers/BHYT/BHYTCtdtController.php`:

```php
<?php

namespace App\Http\Controllers\BHYT;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

use App\Services\BHYT\DanhSachCoSo;

/**
 * Ba man hinh cua module chung tu dien tu: danh sach, nap tep, chi tiet.
 *
 * Controller giu MONG: moi quyet dinh nghiep vu nam o cac lop thuan trong App\Services\Ctdt
 * de kiem duoc ma khong can dung HTTP. Bai hoc tu BHYTXml3176Controller (753 dong).
 */
class BHYTCtdtController extends Controller
{
    public function index()
    {
        return view('bhyt.ctdt.index', [
            'danhSachCoSo' => DanhSachCoSo::danhSach(),
        ]);
    }

    public function importIndex()
    {
        return view('bhyt.ctdt.import', [
            'danhSachCoSo' => DanhSachCoSo::danhSach(),
        ]);
    }
}
```

- [ ] **Step 4: Thêm 7 route**

Trong `routes/web.php`, chèn ngay **sau** dòng cuối của khối route `xml3176/...` (dòng có tên `bhyt.xml3176.export-7980a-data`), vẫn bên trong group `checkrole:xml-man`:

```php
        // ── Chứng từ điện tử theo Phụ lục 02 ───────────────────────────────────
        // Cùng quyền xml-man với XML3176: cùng nhóm người dùng, cùng nghiệp vụ liên
        // thông BHXH. Không tạo role mới - tách role chỉ thêm việc quản trị mà không
        // tách được trách nhiệm thực tế.
        Route::get('ctdt/index', 'BHYT\BHYTCtdtController@index')->name('bhyt.ctdt.index');
        Route::get('ctdt/index/fetch-data', 'BHYT\BHYTCtdtController@fetchData')->name('bhyt.ctdt.fetch-data');
        Route::get('ctdt/import', 'BHYT\BHYTCtdtController@importIndex')->name('bhyt.ctdt.import.index');
        Route::post('ctdt/import/upload', 'BHYT\BHYTCtdtController@uploadData')->name('bhyt.ctdt.upload');
        Route::get('ctdt/detail/{ma_ho_so}', 'BHYT\BHYTCtdtController@detail')->name('bhyt.ctdt.detail');
        Route::get('ctdt/detail/{ma_ho_so}/tab/{loai}', 'BHYT\BHYTCtdtController@detailTab')->name('bhyt.ctdt.detail.tab');
        Route::delete('ctdt/{ma_ho_so}', 'BHYT\BHYTCtdtController@delete')
        ->name('bhyt.ctdt.delete')
        ->middleware('checkrole:superadministrator');
```

⚠️ `{ma_ho_so}` có thể chứa dấu `#` ở nhánh khóa lùi (`Id-abc#1`). Bên gọi phải `encodeURIComponent`
trước khi ghép vào URL — Task 5 và Task 8 làm việc đó.

- [ ] **Step 5: Thêm mục menu**

Trong `config/adminlte.php`, chèn ngay **sau** khối `[ 'text' => 'Xml 3176', ... ]` (kết thúc bằng `],` sau `'submenu'`):

```php
                [
                    'text'      => 'Chứng từ điện tử',
                    'icon'      => 'file-text-o',
                    'checkrole' => 'xml-man',
                    'submenu'   => [
                        [
                            'text'   => 'Danh sách hồ sơ',
                            'icon'   => 'file',
                            'route'  => 'bhyt.ctdt.index',
                            'active' => ['bhyt/ctdt/index*'],
                        ],
                        [
                            'text'   => 'Nạp hồ sơ',
                            'icon'   => 'plus',
                            'route'  => 'bhyt.ctdt.import.index',
                            'active' => ['bhyt/ctdt/import*'],
                        ],
                    ],
                ],
```

- [ ] **Step 6: Viết hai blade khung tối thiểu**

Tạo `resources/views/bhyt/ctdt/index.blade.php`:

```blade
@extends('adminlte::page')

@section('title', 'Danh sách hồ sơ chứng từ điện tử')

@section('content_header')
<h1>Danh sách <small>hồ sơ chứng từ điện tử</small></h1>
@stop

@section('content')
@include('includes.message')
@stop
```

Tạo `resources/views/bhyt/ctdt/import.blade.php`:

```blade
@extends('adminlte::page')

@section('title', 'Nạp hồ sơ chứng từ điện tử')

@section('content_header')
<h1>Nạp <small>hồ sơ chứng từ điện tử</small></h1>
@stop

@section('content')
@include('includes.message')
@stop
```

- [ ] **Step 7: Chạy test để xác nhận xanh**

```bash
php vendor/bin/phpunit tests/Unit/Ctdt/CtdtRouteTest.php
```

Kỳ vọng: `OK (8 tests)`.

Nếu đỏ ở `route_tro_dung_controller` với thông báo về `fetchData`/`uploadData`/`detail`/`detailTab`/`delete` chưa tồn tại: đó là bình thường ở bước này — Laravel chỉ phân giải phương thức khi có request thật, không phải lúc đăng ký route. Nếu đỏ vì lý do khác thì sửa.

- [ ] **Step 8: Commit**

```bash
git add app/Http/Controllers/BHYT/BHYTCtdtController.php routes/web.php config/adminlte.php resources/views/bhyt/ctdt tests/Unit/Ctdt/CtdtRouteTest.php
git commit -m "feat(ctdt): route, menu va controller khung cho ba man hinh"
```

---

## Task 3: `CtdtTrangThaiGui` — bốn nhánh trạng thái

**Files:**
- Create: `app/Services/Ctdt/CtdtTrangThaiGui.php`
- Test: `tests/Unit/Ctdt/CtdtTrangThaiGuiTest.php`

**Interfaces:**
- Consumes: model `App\Models\BHYT\Ctdt\CtdtHoSo` (Giai đoạn 1)
- Produces: `CtdtTrangThaiGui::cua($hoSo)` → một trong sáu hằng `CHUA_KY`, `CON_LOI`, `GUI_TAT`, `CONG_TU_CHOI`, `DA_GUI`, `CHUA_GUI`; và `CtdtTrangThaiGui::nhan($ma)` → nhãn tiếng Việt

**Vì sao là lớp riêng:** đặc tả mục 6.1 đòi cột trạng thái gửi phân biệt **bốn thứ khác nhau**,
không gộp thành "chưa gửi". Gộp lại là cách nhanh nhất để người vận hành ngồi chờ một hồ sơ
vĩnh viễn không bao giờ được gửi. Tách thành hàm thuần để kiểm được mà không cần CSDL.

- [ ] **Step 1: Viết test đỏ**

Tạo `tests/Unit/Ctdt/CtdtTrangThaiGuiTest.php`:

```php
<?php

namespace Tests\Unit\Ctdt;

use Tests\TestCase;
use App\Services\Ctdt\CtdtTrangThaiGui;
use App\Models\BHYT\Ctdt\CtdtHoSo;

/**
 * Bon ly do "chua gui" la BON chuyen khac nhau, va nguoi van hanh phai phan biet duoc:
 * chua ky thi di ky, con loi thi di sua, chuc nang gui dang tat thi bao quan tri, con cong
 * tu choi thi phai doc ma loi. Gop lai thanh "chua gui" la de nguoi ta ngoi cho mot ho so
 * vinh vien khong bao gio duoc gui.
 */
class CtdtTrangThaiGuiTest extends TestCase
{
    private function hoSo(array $thuocTinh)
    {
        $hoSo = new CtdtHoSo();

        foreach ($thuocTinh as $cot => $giaTri) {
            $hoSo->{$cot} = $giaTri;
        }

        return $hoSo;
    }

    /** @test */
    public function con_loi_chan_duoc_bao_truoc_moi_thu()
    {
        // Ho so con loi thi du co ky cung khong gui duoc - bao ly do gan nhat truoc.
        $hoSo = $this->hoSo(['so_loi' => 3, 'is_signed' => false]);

        $this->assertSame(CtdtTrangThaiGui::CON_LOI, CtdtTrangThaiGui::cua($hoSo));
    }

    /** @test */
    public function chua_ky_khi_khong_con_loi()
    {
        $hoSo = $this->hoSo(['so_loi' => 0, 'is_signed' => false]);

        $this->assertSame(CtdtTrangThaiGui::CHUA_KY, CtdtTrangThaiGui::cua($hoSo));
    }

    /** @test */
    public function chuc_nang_gui_dang_tat()
    {
        config(['organization.chung_tu_dien_tu.submit_enabled' => false]);

        $hoSo = $this->hoSo(['so_loi' => 0, 'is_signed' => true]);

        $this->assertSame(CtdtTrangThaiGui::GUI_TAT, CtdtTrangThaiGui::cua($hoSo));
    }

    /** @test */
    public function da_gui_thanh_cong()
    {
        config(['organization.chung_tu_dien_tu.submit_enabled' => true]);

        $hoSo = $this->hoSo([
            'so_loi' => 0, 'is_signed' => true, 'ma_gd' => 'HS_1', 'ma_ket_qua' => '200',
        ]);

        $this->assertSame(CtdtTrangThaiGui::DA_GUI, CtdtTrangThaiGui::cua($hoSo));
    }

    /** @test */
    public function cong_tu_choi_khi_ma_ket_qua_khac_200()
    {
        config(['organization.chung_tu_dien_tu.submit_enabled' => true]);

        $hoSo = $this->hoSo([
            'so_loi' => 0, 'is_signed' => true, 'ma_gd' => 'HS_1', 'ma_ket_qua' => '205',
        ]);

        $this->assertSame(CtdtTrangThaiGui::CONG_TU_CHOI, CtdtTrangThaiGui::cua($hoSo));
    }

    /** @test */
    public function ma_ket_qua_so_200_cung_duoc_coi_la_thanh_cong()
    {
        // Khoa mang trong config bi PHP ep thanh int, va cong co the tra ve so. So sanh
        // nghiem ngat voi chuoi '200' se coi mot ho so THANH CONG la bi tu choi.
        config(['organization.chung_tu_dien_tu.submit_enabled' => true]);

        $hoSo = $this->hoSo([
            'so_loi' => 0, 'is_signed' => true, 'ma_gd' => 'HS_1', 'ma_ket_qua' => 200,
        ]);

        $this->assertSame(CtdtTrangThaiGui::DA_GUI, CtdtTrangThaiGui::cua($hoSo));
    }

    /** @test */
    public function da_ky_gui_dang_bat_nhung_chua_gui()
    {
        config(['organization.chung_tu_dien_tu.submit_enabled' => true]);

        $hoSo = $this->hoSo(['so_loi' => 0, 'is_signed' => true]);

        $this->assertSame(CtdtTrangThaiGui::CHUA_GUI, CtdtTrangThaiGui::cua($hoSo));
    }

    /** @test */
    public function is_signed_kieu_so_1_van_duoc_coi_la_da_ky()
    {
        // MySQL tinyint(1) doc ve dang 0/1. So sanh === true se coi moi ho so la chua ky.
        config(['organization.chung_tu_dien_tu.submit_enabled' => true]);

        $hoSo = $this->hoSo(['so_loi' => 0, 'is_signed' => 1]);

        $this->assertSame(CtdtTrangThaiGui::CHUA_GUI, CtdtTrangThaiGui::cua($hoSo));
    }

    /** @test */
    public function moi_trang_thai_deu_co_nhan_tieng_viet()
    {
        $ma = [
            CtdtTrangThaiGui::CON_LOI, CtdtTrangThaiGui::CHUA_KY, CtdtTrangThaiGui::GUI_TAT,
            CtdtTrangThaiGui::DA_GUI, CtdtTrangThaiGui::CONG_TU_CHOI, CtdtTrangThaiGui::CHUA_GUI,
        ];

        $this->assertCount(6, array_unique($ma), 'Sau hang phai khac nhau');

        foreach ($ma as $m) {
            $this->assertNotEmpty(CtdtTrangThaiGui::nhan($m), 'Thieu nhan cho ' . $m);
        }
    }

    /** @test */
    public function nhan_cua_ma_la_khong_nem()
    {
        $this->assertNotEmpty(CtdtTrangThaiGui::nhan('khong_ton_tai'));
    }
}
```

- [ ] **Step 2: Chạy test để xác nhận đỏ**

```bash
php vendor/bin/phpunit tests/Unit/Ctdt/CtdtTrangThaiGuiTest.php
```

Kỳ vọng: đỏ — `Class 'App\Services\Ctdt\CtdtTrangThaiGui' not found`.

- [ ] **Step 3: Viết `CtdtTrangThaiGui`**

Tạo `app/Services/Ctdt/CtdtTrangThaiGui.php`:

```php
<?php

namespace App\Services\Ctdt;

use App\Models\BHYT\Ctdt\CtdtHoSo;

/**
 * Suy trang thai gui cua mot ho so, de man danh sach hien mot cot duy nhat.
 *
 * BON LY DO "chua gui" la BON chuyen khac nhau, va nguoi van hanh phai phan biet duoc:
 *   con loi   -> di sua ho so
 *   chua ky   -> di ky so
 *   gui tat   -> bao quan tri bat cau hinh
 *   cong tu choi -> doc ma loi cua cong
 * Gop lai thanh mot chu "chua gui" la de nguoi ta ngoi cho mot ho so vinh vien khong bao
 * gio duoc gui.
 *
 * Ham THUAN: nhan mot ban ghi, tra mot chuoi. Khong truy van gi them.
 */
class CtdtTrangThaiGui
{
    const CON_LOI      = 'con_loi';
    const CHUA_KY      = 'chua_ky';
    const GUI_TAT      = 'gui_tat';
    const DA_GUI       = 'da_gui';
    const CONG_TU_CHOI = 'cong_tu_choi';
    const CHUA_GUI     = 'chua_gui';

    const NHAN = [
        self::CON_LOI      => 'Còn lỗi chặn',
        self::CHUA_KY      => 'Chưa ký số',
        self::GUI_TAT      => 'Chức năng gửi đang tắt',
        self::DA_GUI       => 'Đã gửi',
        self::CONG_TU_CHOI => 'Cổng từ chối',
        self::CHUA_GUI     => 'Chờ gửi',
    ];

    /**
     * THU TU quan trong: bao ly do GAN NHAT truoc. Mot ho so vua con loi vua chua ky thi
     * viec can lam truoc la sua loi.
     */
    public static function cua($hoSo)
    {
        if ((int) $hoSo->so_loi > 0) {
            return self::CON_LOI;
        }

        // Ep ve bool: is_signed doc tu MySQL tinyint(1) ve dang 0/1. So sanh === true se
        // coi MOI ho so la chua ky.
        if (!(bool) $hoSo->is_signed) {
            return self::CHUA_KY;
        }

        // Da co ket qua tu cong thi ket qua do thang cau hinh: cau hinh co the vua bi tat
        // sau khi ho so da gui xong, va luc do hien "dang tat" la noi sai.
        if (!empty($hoSo->ma_ket_qua)) {
            // So sanh LONG: cong co the tra ve so 200 thay vi chuoi '200'. So sanh nghiem
            // ngat se coi mot ho so THANH CONG la bi tu choi.
            return (string) $hoSo->ma_ket_qua == '200' ? self::DA_GUI : self::CONG_TU_CHOI;
        }

        if (!(bool) config('organization.chung_tu_dien_tu.submit_enabled', false)) {
            return self::GUI_TAT;
        }

        return self::CHUA_GUI;
    }

    /** @return string Nhan tieng Viet; ma la thi tra chinh ma de khong bao gio hien o trong */
    public static function nhan($ma)
    {
        return isset(self::NHAN[$ma]) ? self::NHAN[$ma] : (string) $ma;
    }
}
```

- [ ] **Step 4: Chạy test để xác nhận xanh**

```bash
php vendor/bin/phpunit tests/Unit/Ctdt/CtdtTrangThaiGuiTest.php
```

Kỳ vọng: `OK (10 tests)`.

- [ ] **Step 5: Commit**

```bash
git add app/Services/Ctdt/CtdtTrangThaiGui.php tests/Unit/Ctdt/CtdtTrangThaiGuiTest.php
git commit -m "feat(ctdt): suy trang thai gui, bon ly do chua gui khong bi gop"
```

---

## Task 4: `CtdtDanhSach` — truy vấn và bộ lọc

**Files:**
- Create: `app/Services/Ctdt/CtdtDanhSach.php`
- Test: `tests/Unit/Ctdt/CtdtDanhSachTest.php`

**Interfaces:**
- Consumes: model `CtdtHoSo`, `CtdtChungTu` (Giai đoạn 1); `CtdtTrangThaiGui` (Task 3)
- Produces: `CtdtDanhSach::truyVan(array $loc)` → `Illuminate\Database\Eloquent\Builder` trên `CtdtHoSo`. Các khóa lọc nhận được: `tu_ngay`, `den_ngay` (lọc theo `imported_at`), `dich_vu`, `loai_ho_so`, `macskcb`, `tim` (mã hồ sơ / mã thẻ / họ tên), `chi_con_loi` (bool), `trang_thai_gui`

- [ ] **Step 1: Viết test đỏ**

Tạo `tests/Unit/Ctdt/CtdtDanhSachTest.php`:

```php
<?php

namespace Tests\Unit\Ctdt;

use Tests\TestCase;
use Tests\Support\DungBangCtdtSqlite;
use App\Services\Ctdt\CtdtDanhSach;
use App\Services\Ctdt\CtdtTrangThaiGui;
use App\Models\BHYT\Ctdt\CtdtHoSo;
use App\Models\BHYT\Ctdt\CtdtChungTu;

class CtdtDanhSachTest extends TestCase
{
    use DungBangCtdtSqlite;

    protected function setUp()
    {
        parent::setUp();
        $this->chuanBiBangCtdt();
        config(['organization.chung_tu_dien_tu.submit_enabled' => true]);
    }

    private function taoHoSo(array $hoSo, array $chungTu = [])
    {
        $ban = CtdtHoSo::create(array_merge([
            'ma_ho_so'    => 'YT001',
            'dich_vu'     => 'CT2025',
            'loai_hs'     => '39',
            'macskcb'     => '01929',
            'imported_at' => '2026-08-19 08:00:00',
            'so_chung_tu' => 1,
        ], $hoSo));

        CtdtChungTu::create(array_merge([
            'ho_so_id'     => $ban->id,
            'loai_ho_so'   => 'CT03',
            'ma_chung_tu'  => $ban->ma_ho_so,
            'ma_the'       => 'DN123',
            'ho_ten'       => 'Nguyen Van Test',
            'noi_dung_goc' => '<CT03/>',
        ], $chungTu));

        return $ban;
    }

    /** @test */
    public function khong_loc_thi_tra_tat_ca()
    {
        $this->taoHoSo(['ma_ho_so' => 'YT001']);
        $this->taoHoSo(['ma_ho_so' => 'YT002']);

        $this->assertSame(2, CtdtDanhSach::truyVan([])->count());
    }

    /** @test */
    public function loc_theo_khoang_ngay_nap()
    {
        $this->taoHoSo(['ma_ho_so' => 'YT001', 'imported_at' => '2026-08-01 10:00:00']);
        $this->taoHoSo(['ma_ho_so' => 'YT002', 'imported_at' => '2026-08-19 10:00:00']);

        $kq = CtdtDanhSach::truyVan(['tu_ngay' => '2026-08-15', 'den_ngay' => '2026-08-20'])->get();

        $this->assertCount(1, $kq);
        $this->assertSame('YT002', $kq->first()->ma_ho_so);
    }

    /** @test */
    public function den_ngay_bao_gom_ca_ngay_do()
    {
        // Loc <= '2026-08-19' tren cot datetime se BO HET ho so nap trong ngay do tru dung
        // 00:00:00. Nguoi dung chon "den 19/8" thi mong doi thay ho so nap luc 15h ngay 19.
        $this->taoHoSo(['ma_ho_so' => 'YT001', 'imported_at' => '2026-08-19 15:30:00']);

        $kq = CtdtDanhSach::truyVan(['tu_ngay' => '2026-08-19', 'den_ngay' => '2026-08-19'])->get();

        $this->assertCount(1, $kq);
    }

    /** @test */
    public function loc_theo_dich_vu()
    {
        $this->taoHoSo(['ma_ho_so' => 'YT001', 'dich_vu' => 'CT2025']);
        $this->taoHoSo(['ma_ho_so' => 'GBT-1', 'dich_vu' => 'GBT']);

        $kq = CtdtDanhSach::truyVan(['dich_vu' => 'GBT'])->get();

        $this->assertCount(1, $kq);
        $this->assertSame('GBT-1', $kq->first()->ma_ho_so);
    }

    /** @test */
    public function loc_theo_ma_co_so()
    {
        $this->taoHoSo(['ma_ho_so' => 'YT001', 'macskcb' => '01929']);
        $this->taoHoSo(['ma_ho_so' => 'YT002', 'macskcb' => '01013']);

        $kq = CtdtDanhSach::truyVan(['macskcb' => '01013'])->get();

        $this->assertCount(1, $kq);
        $this->assertSame('YT002', $kq->first()->ma_ho_so);
    }

    /** @test */
    public function loc_theo_loai_chung_tu_di_qua_bang_chung_tu()
    {
        $this->taoHoSo(['ma_ho_so' => 'YT001'], ['loai_ho_so' => 'CT03']);
        $this->taoHoSo(['ma_ho_so' => 'YT002'], ['loai_ho_so' => 'CT04']);

        $kq = CtdtDanhSach::truyVan(['loai_ho_so' => 'CT04'])->get();

        $this->assertCount(1, $kq);
        $this->assertSame('YT002', $kq->first()->ma_ho_so);
    }

    /** @test */
    public function loc_theo_loai_khong_tra_ho_so_trung_lap()
    {
        // Mot ho so co HAI chung tu CT03 thi join se sinh hai dong. Man danh sach hien mot
        // ho so hai lan la loi de nguoi dung thay nhat.
        $ban = $this->taoHoSo(['ma_ho_so' => 'YT001'], ['loai_ho_so' => 'CT03']);

        CtdtChungTu::create([
            'ho_so_id'     => $ban->id,
            'loai_ho_so'   => 'CT03',
            'ma_chung_tu'  => 'YT001',
            'noi_dung_goc' => '<CT03/>',
        ]);

        $this->assertSame(1, CtdtDanhSach::truyVan(['loai_ho_so' => 'CT03'])->count());
    }

    /** @test */
    public function tim_theo_ma_ho_so()
    {
        $this->taoHoSo(['ma_ho_so' => 'YT001']);
        $this->taoHoSo(['ma_ho_so' => 'YT999']);

        $kq = CtdtDanhSach::truyVan(['tim' => 'YT999'])->get();

        $this->assertCount(1, $kq);
        $this->assertSame('YT999', $kq->first()->ma_ho_so);
    }

    /** @test */
    public function tim_theo_ma_the_va_ho_ten_cua_chung_tu()
    {
        $this->taoHoSo(['ma_ho_so' => 'YT001'], ['ma_the' => 'DN123', 'ho_ten' => 'Nguyen Van A']);
        $this->taoHoSo(['ma_ho_so' => 'YT002'], ['ma_the' => 'GD456', 'ho_ten' => 'Tran Thi B']);

        $this->assertSame('YT002', CtdtDanhSach::truyVan(['tim' => 'GD456'])->first()->ma_ho_so);
        $this->assertSame('YT002', CtdtDanhSach::truyVan(['tim' => 'Tran Thi'])->first()->ma_ho_so);
    }

    /** @test */
    public function chi_con_loi()
    {
        $this->taoHoSo(['ma_ho_so' => 'YT001', 'so_loi' => 0]);
        $this->taoHoSo(['ma_ho_so' => 'YT002', 'so_loi' => 3]);

        $kq = CtdtDanhSach::truyVan(['chi_con_loi' => true])->get();

        $this->assertCount(1, $kq);
        $this->assertSame('YT002', $kq->first()->ma_ho_so);
    }

    /** @test */
    public function loc_theo_trang_thai_chua_ky()
    {
        $this->taoHoSo(['ma_ho_so' => 'YT001', 'so_loi' => 0, 'is_signed' => false]);
        $this->taoHoSo(['ma_ho_so' => 'YT002', 'so_loi' => 0, 'is_signed' => true]);

        $kq = CtdtDanhSach::truyVan(['trang_thai_gui' => CtdtTrangThaiGui::CHUA_KY])->get();

        $this->assertCount(1, $kq);
        $this->assertSame('YT001', $kq->first()->ma_ho_so);
    }

    /** @test */
    public function loc_theo_trang_thai_cong_tu_choi()
    {
        $this->taoHoSo([
            'ma_ho_so' => 'YT001', 'so_loi' => 0, 'is_signed' => true,
            'ma_gd' => 'HS_1', 'ma_ket_qua' => '200',
        ]);
        $this->taoHoSo([
            'ma_ho_so' => 'YT002', 'so_loi' => 0, 'is_signed' => true,
            'ma_gd' => 'HS_2', 'ma_ket_qua' => '205',
        ]);

        $kq = CtdtDanhSach::truyVan(['trang_thai_gui' => CtdtTrangThaiGui::CONG_TU_CHOI])->get();

        $this->assertCount(1, $kq);
        $this->assertSame('YT002', $kq->first()->ma_ho_so);
    }

    /** @test */
    public function loc_rong_va_null_bi_bo_qua_khong_lam_mat_ket_qua()
    {
        // Form gui len chuoi rong cho moi o khong chon. Coi chuoi rong la mot gia tri loc
        // se lam man hinh trong tron ma khong ai hieu tai sao.
        $this->taoHoSo(['ma_ho_so' => 'YT001']);

        $loc = [
            'tu_ngay' => '', 'den_ngay' => null, 'dich_vu' => '', 'loai_ho_so' => '',
            'macskcb' => '', 'tim' => '   ', 'chi_con_loi' => false, 'trang_thai_gui' => '',
        ];

        $this->assertSame(1, CtdtDanhSach::truyVan($loc)->count());
    }
}
```

- [ ] **Step 2: Chạy test để xác nhận đỏ**

```bash
php vendor/bin/phpunit tests/Unit/Ctdt/CtdtDanhSachTest.php
```

Kỳ vọng: đỏ — `Class 'App\Services\Ctdt\CtdtDanhSach' not found`.

- [ ] **Step 3: Viết `CtdtDanhSach`**

Tạo `app/Services/Ctdt/CtdtDanhSach.php`:

```php
<?php

namespace App\Services\Ctdt;

use App\Models\BHYT\Ctdt\CtdtHoSo;

/**
 * Dung truy van cho man danh sach ho so.
 *
 * TACH KHOI CONTROLLER co chu dich: bo loc la cho de sai nhat cua mot man danh sach, va
 * kiem duoc no ma khong can dung HTTP thi moi kiem het duoc cac nhanh.
 *
 * Loc theo loai chung tu va tim theo ma the / ho ten phai di qua bang ctdt_chung_tu. Dung
 * whereHas chu khong join: mot ho so co nhieu chung tu cung loai se sinh nhieu dong khi
 * join, va man danh sach hien mot ho so hai lan la loi de nguoi dung thay nhat.
 */
class CtdtDanhSach
{
    /**
     * @param array $loc tu_ngay, den_ngay, dich_vu, loai_ho_so, macskcb, tim,
     *                   chi_con_loi, trang_thai_gui - tat ca deu tuy chon
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function truyVan(array $loc)
    {
        $q = CtdtHoSo::query();

        if (self::coGiaTri($loc, 'tu_ngay')) {
            $q->where('imported_at', '>=', trim($loc['tu_ngay']) . ' 00:00:00');
        }

        if (self::coGiaTri($loc, 'den_ngay')) {
            // Phai la 23:59:59, khong phai '<= ngay'. So sanh voi chuoi ngay tran tren cot
            // datetime se bo het ho so nap trong chinh ngay do tru dung luc 00:00:00.
            $q->where('imported_at', '<=', trim($loc['den_ngay']) . ' 23:59:59');
        }

        if (self::coGiaTri($loc, 'dich_vu')) {
            $q->where('dich_vu', trim($loc['dich_vu']));
        }

        if (self::coGiaTri($loc, 'macskcb')) {
            $q->where('macskcb', trim($loc['macskcb']));
        }

        if (self::coGiaTri($loc, 'loai_ho_so')) {
            $loai = trim($loc['loai_ho_so']);

            $q->whereHas('chungTu', function ($con) use ($loai) {
                $con->where('loai_ho_so', $loai);
            });
        }

        if (self::coGiaTri($loc, 'tim')) {
            $tim = trim($loc['tim']);

            $q->where(function ($ngoai) use ($tim) {
                $ngoai->where('ma_ho_so', 'like', '%' . $tim . '%')
                    ->orWhereHas('chungTu', function ($con) use ($tim) {
                        $con->where('ma_the', 'like', '%' . $tim . '%')
                            ->orWhere('ho_ten', 'like', '%' . $tim . '%');
                    });
            });
        }

        if (!empty($loc['chi_con_loi'])) {
            $q->where('so_loi', '>', 0);
        }

        if (self::coGiaTri($loc, 'trang_thai_gui')) {
            self::locTrangThai($q, trim($loc['trang_thai_gui']));
        }

        return $q;
    }

    /**
     * Dich mot trang thai thanh dieu kien SQL.
     *
     * Phai GIU DUNG thu tu uu tien cua CtdtTrangThaiGui::cua(): neu o day "chua ky" khong
     * loai tru "con loi" thi mot ho so vua con loi vua chua ky se hien o ca hai bo loc, va
     * tong so cac bo loc khong bang tong so ho so - nguoi dung se khong tin man hinh nua.
     */
    private static function locTrangThai($q, $trangThai)
    {
        if ($trangThai === CtdtTrangThaiGui::CON_LOI) {
            return $q->where('so_loi', '>', 0);
        }

        // Moi trang thai con lai deu ngu y "khong con loi".
        $q->where('so_loi', '<=', 0);

        if ($trangThai === CtdtTrangThaiGui::CHUA_KY) {
            return $q->where('is_signed', false);
        }

        $q->where('is_signed', true);

        if ($trangThai === CtdtTrangThaiGui::DA_GUI) {
            return $q->where('ma_ket_qua', '200');
        }

        if ($trangThai === CtdtTrangThaiGui::CONG_TU_CHOI) {
            return $q->whereNotNull('ma_ket_qua')->where('ma_ket_qua', '<>', '200');
        }

        // GUI_TAT va CHUA_GUI cung la "da ky, chua co ket qua tu cong"; phan biet chung
        // bang CAU HINH chu khong bang du lieu, nen khong the loc bang SQL rieng.
        return $q->whereNull('ma_ket_qua');
    }

    private static function coGiaTri(array $loc, $khoa)
    {
        return isset($loc[$khoa]) && trim((string) $loc[$khoa]) !== '';
    }
}
```

- [ ] **Step 4: Chạy test để xác nhận xanh**

```bash
php vendor/bin/phpunit tests/Unit/Ctdt/CtdtDanhSachTest.php
```

Kỳ vọng: `OK (13 tests)`.

- [ ] **Step 5: Commit**

```bash
git add app/Services/Ctdt/CtdtDanhSach.php tests/Unit/Ctdt/CtdtDanhSachTest.php
git commit -m "feat(ctdt): truy van va bo loc man danh sach"
```

---

## Task 5: Màn danh sách — `fetchData` và blade

**Files:**
- Modify: `app/Http/Controllers/BHYT/BHYTCtdtController.php` (thêm hằng `DATATABLE_COLUMNS` và `fetchData()`)
- Modify: `resources/views/bhyt/ctdt/index.blade.php` (dựng đầy)
- Create: `resources/views/bhyt/ctdt/partials/search.blade.php`
- Test: `tests/Unit/Ctdt/CtdtDatatableCotTest.php`

**Interfaces:**
- Consumes: `CtdtDanhSach::truyVan()` (Task 4), `CtdtTrangThaiGui` (Task 3), `DanhSachCoSo` (đã có)
- Produces: `BHYTCtdtController::DATATABLE_COLUMNS` (mảng tên cột được phép ra JSON), `fetchData(Request $request)` trả JSON DataTables

- [ ] **Step 1: Viết test đỏ**

Tạo `tests/Unit/Ctdt/CtdtDatatableCotTest.php`:

```php
<?php

namespace Tests\Unit\Ctdt;

use Tests\TestCase;
use App\Http\Controllers\BHYT\BHYTCtdtController;

/**
 * Khoa danh sach cot di ra ngoai trong JSON cua DataTables.
 *
 * Danh sach TRANG, khong phai danh sach den: quan he them vao truy van sau nay se khong tu
 * dong lot ra ngoai lam payload phinh lai - va khong vo tinh day du lieu benh nhan ra mot
 * endpoint ma man hinh khong he hien.
 *
 * Test nay con khoa hai danh sach khop nhau: cot khai trong controller va cot blade doc.
 * Lech nhau thi mot cot hien trong o trong ma khong bao gi ca.
 */
class CtdtDatatableCotTest extends TestCase
{
    /** @test */
    public function danh_sach_cot_khong_rong_va_khong_trung()
    {
        $cot = BHYTCtdtController::DATATABLE_COLUMNS;

        $this->assertNotEmpty($cot);
        $this->assertSame(count($cot), count(array_unique($cot)), 'Co cot bi khai hai lan');
    }

    /** @test */
    public function co_du_cac_cot_dac_ta_doi_hoi()
    {
        // Dac ta muc 6.1: ma ho so, dich vu, ma CSKCB, ho ten, ma the, so chung tu, so loi,
        // trang thai ky, trang thai gui, MaGD, thoi diem tiep nhan.
        foreach ([
            'ma_ho_so', 'dich_vu', 'macskcb', 'ho_ten', 'ma_the', 'so_chung_tu', 'so_loi',
            'is_signed', 'trang_thai_gui', 'ma_gd', 'thoi_gian_tiep_nhan',
        ] as $ten) {
            $this->assertContains($ten, BHYTCtdtController::DATATABLE_COLUMNS,
                'Thieu cot ' . $ten);
        }
    }

    /** @test */
    public function blade_doc_dung_nhung_cot_da_khai()
    {
        // Doc thang tep blade: moi "data": "<ten>" trong khoi columns phai nam trong danh
        // sach trang. Mot cot blade doc ma controller khong tra se hien o trong vinh vien.
        $blade = file_get_contents(base_path('resources/views/bhyt/ctdt/index.blade.php'));

        $this->assertNotFalse($blade, 'Khong doc duoc index.blade.php');

        preg_match_all('/"data"\s*:\s*"([a-z0-9_]+)"/i', $blade, $khop);

        $this->assertNotEmpty($khop[1], 'Khong tim thay cot nao trong blade');

        foreach (array_unique($khop[1]) as $ten) {
            $this->assertContains($ten, BHYTCtdtController::DATATABLE_COLUMNS,
                'Blade doc cot "' . $ten . '" khong co trong DATATABLE_COLUMNS');
        }
    }

    /** @test */
    public function khong_lo_cot_nhay_cam_ra_ngoai()
    {
        // noi_dung_goc la XML nguyen van cua chung tu - hang chuc KB moi dong. Lot vao danh
        // sach thi moi lan tai 200 dong la vai MB qua mang, va du lieu benh nhan di ra mot
        // endpoint khong ai doc no.
        foreach (['noi_dung_goc', 'lich_su_gui', 'submitted_message'] as $cam) {
            $this->assertNotContains($cam, BHYTCtdtController::DATATABLE_COLUMNS,
                'Cot ' . $cam . ' khong duoc ra JSON danh sach');
        }
    }
}
```

- [ ] **Step 2: Chạy test để xác nhận đỏ**

```bash
php vendor/bin/phpunit tests/Unit/Ctdt/CtdtDatatableCotTest.php
```

Kỳ vọng: đỏ — `Undefined constant ... DATATABLE_COLUMNS`.

- [ ] **Step 3: Thêm hằng và `fetchData()` vào controller**

Trong `app/Http/Controllers/BHYT/BHYTCtdtController.php`, thêm `use` ở đầu tệp:

```php
use Yajra\Datatables\Datatables;
use App\Services\Ctdt\CtdtDanhSach;
use App\Services\Ctdt\CtdtTrangThaiGui;
use App\Models\BHYT\Ctdt\CtdtChungTu;
```

Thêm hằng ngay đầu lớp:

```php
    /**
     * Cac cot duoc phep di ra ngoai trong JSON cua DataTables.
     *
     * Danh sach TRANG, khong phai danh sach den: quan he them vao truy van sau nay se khong
     * tu dong lot ra ngoai. CtdtDatatableCotTest khoa danh sach nay khop dung cac cot blade
     * doc, va chan noi_dung_goc (XML nguyen van, hang chuc KB moi dong) lot vao.
     */
    const DATATABLE_COLUMNS = [
        'ma_ho_so', 'dich_vu', 'macskcb', 'ho_ten', 'ma_the', 'so_chung_tu', 'so_loi',
        'is_signed', 'trang_thai_gui', 'trang_thai_nhan', 'ma_gd', 'ma_ket_qua',
        'thoi_gian_tiep_nhan', 'imported_at', 'imported_by', 'khong_co_ma_yte', 'action',
    ];
```

Thêm phương thức:

```php
    public function fetchData(Request $request)
    {
        $truyVan = CtdtDanhSach::truyVan([
            'tu_ngay'        => $request->input('tu_ngay'),
            'den_ngay'       => $request->input('den_ngay'),
            'dich_vu'        => $request->input('dich_vu'),
            'loai_ho_so'     => $request->input('loai_ho_so'),
            'macskcb'        => $request->input('macskcb'),
            'tim'            => $request->input('tim'),
            // Laravel 5.5 KHONG co Request::boolean() (them tu 5.8). DataTables gui '0'/'1'
            // dang chuoi, ma (bool) '0' la TRUE - o loc se luon bat.
            'chi_con_loi'    => filter_var($request->input('chi_con_loi'), FILTER_VALIDATE_BOOLEAN),
            'trang_thai_gui' => $request->input('trang_thai_gui'),
        ]);

        // Nap kem chung tu: cot ho ten / ma the lay tu chung tu DAU TIEN cua ho so. Khong
        // nap kem thi moi dong la mot truy van rieng - 200 dong thanh 201 truy van.
        $truyVan->with(['chungTu' => function ($q) {
            $q->select('id', 'ho_so_id', 'ma_the', 'ho_ten')->orderBy('id');
        }]);

        return Datatables::of($truyVan)
            ->addColumn('ho_ten', function ($hoSo) {
                $dau = $hoSo->chungTu->first();

                return $dau ? (string) $dau->ho_ten : '';
            })
            ->addColumn('ma_the', function ($hoSo) {
                $dau = $hoSo->chungTu->first();

                return $dau ? (string) $dau->ma_the : '';
            })
            ->addColumn('trang_thai_gui', function ($hoSo) {
                return CtdtTrangThaiGui::cua($hoSo);
            })
            ->addColumn('trang_thai_nhan', function ($hoSo) {
                return CtdtTrangThaiGui::nhan(CtdtTrangThaiGui::cua($hoSo));
            })
            ->addColumn('khong_co_ma_yte', function ($hoSo) {
                // Ho so roi vao nhanh lui GUID: nap lai se tao ban ghi MOI chu khong ghi de.
                // Nguoi van hanh phai biet truoc, khong phai phat hien sau khi da nap hai lan.
                return strpos((string) $hoSo->ma_ho_so, '#') !== false;
            })
            ->addColumn('action', function ($hoSo) {
                return $hoSo->ma_ho_so;
            })
            ->rawColumns([])
            ->make(true);
    }
```

- [ ] **Step 4: Viết khối bộ lọc**

Tạo `resources/views/bhyt/ctdt/partials/search.blade.php`:

```blade
{{-- Bo loc man danh sach chung tu dien tu.

     Bien vao: $danhSachCoSo — mang ma => nhan, tu DanhSachCoSo::danhSach() --}}
<div class="panel panel-default">
    <div class="panel-body">
        <div class="col-sm-12">
            <div class="form-group row">
                <div class="col-sm-2">
                    <label for="tu_ngay">Từ ngày nạp</label>
                    <input type="date" id="tu_ngay" class="form-control">
                </div>
                <div class="col-sm-2">
                    <label for="den_ngay">Đến ngày nạp</label>
                    <input type="date" id="den_ngay" class="form-control">
                </div>
                <div class="col-sm-2">
                    <label for="dich_vu">Dịch vụ</label>
                    <select id="dich_vu" class="form-control">
                        <option value="">Tất cả dịch vụ</option>
                        @foreach (config('ctdt.dich_vu') as $ma => $cauHinh)
                            <option value="{{ $ma }}">{{ $cauHinh['ten'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-sm-2">
                    <label for="loai_ho_so">Loại chứng từ</label>
                    <select id="loai_ho_so" class="form-control">
                        <option value="">Tất cả loại</option>
                        @foreach ($danhSachLoai as $ma => $ten)
                            <option value="{{ $ma }}">{{ $ten }}</option>
                        @endforeach
                    </select>
                </div>
                @include('partials.ma_cskcb', ['danhSachCoSo' => $danhSachCoSo])
                <div class="col-sm-2">
                    <label for="trang_thai_gui">Trạng thái gửi</label>
                    <select id="trang_thai_gui" class="form-control">
                        <option value="">Tất cả trạng thái</option>
                        @foreach ($danhSachTrangThai as $ma => $nhan)
                            <option value="{{ $ma }}">{{ $nhan }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
        <div class="col-sm-12">
            <div class="form-group row">
                <div class="col-sm-4">
                    <label for="tim">Tìm mã hồ sơ / mã thẻ / họ tên</label>
                    <input type="text" id="tim" class="form-control" placeholder="Nhập rồi bấm Tải dữ liệu">
                </div>
                <div class="col-sm-2">
                    <label for="chi_con_loi">&nbsp;</label>
                    <div class="checkbox">
                        <label><input type="checkbox" id="chi_con_loi"> Chỉ hồ sơ còn lỗi</label>
                    </div>
                </div>
                <div class="col-sm-2">
                    <label for="btn_tai_du_lieu">&nbsp;</label>
                    <button id="btn_tai_du_lieu" class="btn btn-primary form-control">
                        <i class="fa fa-refresh"></i> Tải dữ liệu
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
```

- [ ] **Step 5: Dựng đầy `index.blade.php`**

Thay **toàn bộ** `resources/views/bhyt/ctdt/index.blade.php`:

```blade
@extends('adminlte::page')

@section('title', 'Danh sách hồ sơ chứng từ điện tử')

@section('content_header')
<h1>Danh sách <small>hồ sơ chứng từ điện tử</small></h1>
@stop

@push('after-styles')
<style>
    .table-responsive { display: block; width: 100%; overflow-x: auto; }
    .nhan-canh-bao { color: #b94a48; font-weight: bold; }
</style>
@endpush

@section('content')
@include('includes.message')
@include('bhyt.ctdt.partials.search')

<div class="panel panel-default">
    <div class="panel-body">
        <div class="table-responsive">
            <table class="table table-hover table-bordered" id="ctdt-list" style="width:100%">
                <thead>
                    <tr>
                        <th>Mã hồ sơ</th>
                        <th>Dịch vụ</th>
                        <th>Mã CSKCB</th>
                        <th>Họ tên</th>
                        <th>Mã thẻ</th>
                        <th>Số CT</th>
                        <th>Số lỗi</th>
                        <th>Ký số</th>
                        <th>Trạng thái gửi</th>
                        <th>MaGD</th>
                        <th>Thời điểm tiếp nhận</th>
                        <th>Nạp lúc</th>
                        <th></th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>
@stop

@push('after-scripts')
<script>
$(function () {
    var bang = null;

    function nhanDichVu(ma) {
        var nhan = @json(collect(config('ctdt.dich_vu'))->map(function ($c) { return $c['ten']; }));

        return nhan[ma] ? nhan[ma] : ma;
    }

    function taiDuLieu() {
        if (bang) {
            bang.ajax.reload();
            return;
        }

        bang = $('#ctdt-list').DataTable({
            processing: true,
            serverSide: true,
            scrollX: true,
            order: [[11, 'desc']],
            lengthMenu: [[10, 25, 50, 100, 200], [10, 25, 50, 100, 200]],
            ajax: {
                url: "{{ route('bhyt.ctdt.fetch-data') }}",
                data: function (d) {
                    d.tu_ngay        = $('#tu_ngay').val();
                    d.den_ngay       = $('#den_ngay').val();
                    d.dich_vu        = $('#dich_vu').val();
                    d.loai_ho_so     = $('#loai_ho_so').val();
                    d.macskcb        = $('#ma_cskcb').val();
                    d.tim            = $('#tim').val();
                    d.chi_con_loi    = $('#chi_con_loi').is(':checked') ? 1 : 0;
                    d.trang_thai_gui = $('#trang_thai_gui').val();
                }
            },
            columns: [
                {
                    "data": "ma_ho_so",
                    render: function (data, type, row) {
                        // ma_ho_so o nhanh lui chua dau '#' (vd Id-abc#1). Khong ma hoa thi
                        // trinh duyet cat tu dau '#' va URL tro sai ho so.
                        var url = "{{ route('bhyt.ctdt.detail', ['ma_ho_so' => '__MA__']) }}"
                                  .replace('__MA__', encodeURIComponent(data));
                        var canhBao = row.khong_co_ma_yte
                            ? ' <span class="nhan-canh-bao" title="Hồ sơ không có mã y tế — nạp lại sẽ tạo bản ghi mới, không ghi đè">⚠</span>'
                            : '';

                        return '<a href="' + url + '">' + $('<div>').text(data).html() + '</a>' + canhBao;
                    }
                },
                { "data": "dich_vu", render: function (d) { return nhanDichVu(d); } },
                { "data": "macskcb" },
                { "data": "ho_ten" },
                { "data": "ma_the" },
                { "data": "so_chung_tu" },
                {
                    "data": "so_loi",
                    render: function (d) {
                        return Number(d) > 0 ? '<span class="nhan-canh-bao">' + d + '</span>' : d;
                    }
                },
                { "data": "is_signed", render: function (d) { return Number(d) ? 'Đã ký' : '—'; } },
                { "data": "trang_thai_nhan" },
                { "data": "ma_gd" },
                { "data": "thoi_gian_tiep_nhan" },
                { "data": "imported_at" },
                {
                    "data": "action",
                    orderable: false,
                    searchable: false,
                    render: function (data) {
                        var url = "{{ route('bhyt.ctdt.detail', ['ma_ho_so' => '__MA__']) }}"
                                  .replace('__MA__', encodeURIComponent(data));

                        return '<a class="btn btn-xs btn-default" href="' + url + '">Chi tiết</a>';
                    }
                }
            ],
            columnDefs: [{ targets: '_all', defaultContent: '' }]
        });
    }

    $('#btn_tai_du_lieu').on('click', function (e) {
        e.preventDefault();
        taiDuLieu();
    });

    taiDuLieu();
});
</script>
@endpush
```

- [ ] **Step 6: Truyền hai danh sách còn thiếu vào view**

Trong `BHYTCtdtController::index()`, đổi thành:

```php
    public function index()
    {
        return view('bhyt.ctdt.index', [
            'danhSachCoSo'     => DanhSachCoSo::danhSach(),
            'danhSachLoai'     => $this->danhSachLoai(),
            'danhSachTrangThai' => CtdtTrangThaiGui::NHAN,
        ]);
    }

    /**
     * Chin loai chung tu de do vao o loc. Lay tu registry chu khong go tay: go tay thi mot
     * ngay nao do registry them loai moi ma o loc khong co, va khong ai phat hien.
     *
     * @return array LOAIHOSO => nhan tab
     */
    private function danhSachLoai()
    {
        $ds = [];

        foreach (\App\Services\Ctdt\CtdtLoaiRegistry::tatCa() as $ma => $lop) {
            $ds[$ma] = $lop::tenTab();
        }

        return $ds;
    }
```

- [ ] **Step 7: Chạy test để xác nhận xanh**

```bash
php vendor/bin/phpunit tests/Unit/Ctdt/CtdtDatatableCotTest.php
```

Kỳ vọng: `OK (4 tests)`.

- [ ] **Step 8: Commit**

```bash
git add app/Http/Controllers/BHYT/BHYTCtdtController.php resources/views/bhyt/ctdt tests/Unit/Ctdt/CtdtDatatableCotTest.php
git commit -m "feat(ctdt): man danh sach ho so voi bo loc va trang thai gui"
```

---

## Task 6: Màn nạp tệp

**Files:**
- Modify: `app/Http/Controllers/BHYT/BHYTCtdtController.php` (thêm `uploadData()`)
- Modify: `resources/views/bhyt/ctdt/import.blade.php` (dựng đầy)
- Test: `tests/Unit/Ctdt/CtdtUploadTest.php`

**Interfaces:**
- Consumes: `CtdtImporter::nhapTuTep($duongDan, $tuyChon)` và `CtdtImportFileResult` với `$dsGhiDeDaGui` (Task 1)
- Produces: `uploadData(Request $request)` trả JSON `['thanh_cong' => bool, 'thong_diep' => string, 'chi_tiet' => array]`; mỗi phần tử `chi_tiet` là `['tep' => string, 'thanh_cong' => bool, 'so_thanh_cong' => int, 'so_that_bai' => int, 'ly_do' => string|null, 'ghi_de_da_gui' => array]`

- [ ] **Step 1: Viết test đỏ**

Tạo `tests/Unit/Ctdt/CtdtUploadTest.php`:

```php
<?php

namespace Tests\Unit\Ctdt;

use Tests\TestCase;
use Tests\Support\DungBangCtdtSqlite;
use Tests\Support\GoiCtdtMau;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use App\Http\Controllers\BHYT\BHYTCtdtController;
use App\Models\BHYT\Ctdt\CtdtHoSo;

class CtdtUploadTest extends TestCase
{
    use DungBangCtdtSqlite;
    use GoiCtdtMau;

    /** @var BHYTCtdtController */
    private $controller;

    private $tepTam = [];

    protected function setUp()
    {
        parent::setUp();
        $this->chuanBiBangCtdt();
        $this->controller = new BHYTCtdtController();
        config(['organization.BHYT.ma_cskcb' => '01013']);
    }

    protected function tearDown()
    {
        foreach ($this->tepTam as $duongDan) {
            if (file_exists($duongDan)) {
                unlink($duongDan);
            }
        }

        parent::tearDown();
    }

    private function tepTaiLen($tenHienThi, $noiDung)
    {
        $duongDan = tempnam(sys_get_temp_dir(), 'up') . '.xml';
        file_put_contents($duongDan, $noiDung);
        $this->tepTam[] = $duongDan;

        // Tham so cuoi = true: bo qua kiem tra "da tai len that qua HTTP chua".
        return new UploadedFile($duongDan, $tenHienThi, 'text/xml', filesize($duongDan), null, true);
    }

    private function yeuCau(array $tep, array $thamSo = [])
    {
        $request = Request::create('/bhyt/ctdt/import/upload', 'POST', $thamSo);
        $request->files->set('xmls', $tep);

        return $request;
    }

    private function json($phanHoi)
    {
        return json_decode($phanHoi->getContent(), true);
    }

    /** @test */
    public function tai_len_mot_tep_hop_le()
    {
        $xml = $this->goiCt2025([[$this->chungTu('CT03', ['MA_YTE' => 'YT001'])]]);

        $kq = $this->json($this->controller->uploadData(
            $this->yeuCau([$this->tepTaiLen('goi-1.xml', $xml)])
        ));

        $this->assertTrue($kq['thanh_cong']);
        $this->assertCount(1, $kq['chi_tiet']);
        $this->assertSame('goi-1.xml', $kq['chi_tiet'][0]['tep']);
        $this->assertSame(1, $kq['chi_tiet'][0]['so_thanh_cong']);
        $this->assertSame(1, CtdtHoSo::count());
    }

    /** @test */
    public function bao_ket_qua_theo_TUNG_tep_khong_gop_thanh_mot_cau()
    {
        // Tai len 5 tep ma chi bao "co loi" thi nguoi dung phai mo tung tep ra doan.
        $tot = $this->goiCt2025([[$this->chungTu('CT03', ['MA_YTE' => 'YT001'])]]);

        $kq = $this->json($this->controller->uploadData($this->yeuCau([
            $this->tepTaiLen('tot.xml', $tot),
            $this->tepTaiLen('hong.xml', '<HSCHUNGTU><chua dong'),
        ])));

        $this->assertFalse($kq['thanh_cong']);
        $this->assertCount(2, $kq['chi_tiet']);

        $theoTen = [];

        foreach ($kq['chi_tiet'] as $ct) {
            $theoTen[$ct['tep']] = $ct;
        }

        $this->assertTrue($theoTen['tot.xml']['thanh_cong']);
        $this->assertFalse($theoTen['hong.xml']['thanh_cong']);
        $this->assertNotEmpty($theoTen['hong.xml']['ly_do']);
        $this->assertSame(1, CtdtHoSo::count(), 'Tep tot van phai vao duoc');
    }

    /** @test */
    public function canh_bao_dich_danh_ho_so_da_gui_bi_ghi_de()
    {
        $xml = $this->goiCt2025([[$this->chungTu('CT03', ['MA_YTE' => 'YT001'])]]);

        $this->controller->uploadData($this->yeuCau([$this->tepTaiLen('lan1.xml', $xml)]));

        CtdtHoSo::where('ma_ho_so', 'YT001')->update([
            'ma_gd' => 'HS_123456', 'ma_ket_qua' => '200',
        ]);

        $kq = $this->json($this->controller->uploadData(
            $this->yeuCau([$this->tepTaiLen('lan2.xml', $xml)])
        ));

        $this->assertTrue($kq['thanh_cong']);
        $this->assertCount(1, $kq['chi_tiet'][0]['ghi_de_da_gui']);
        $this->assertSame('YT001', $kq['chi_tiet'][0]['ghi_de_da_gui'][0]['ma_ho_so']);
        $this->assertSame('HS_123456', $kq['chi_tiet'][0]['ghi_de_da_gui'][0]['ma_gd']);
    }

    /** @test */
    public function ma_co_so_nguoi_nap_chon_duoc_truyen_xuong()
    {
        // Goi giay chung sinh khong mang ma co so; o chon tren man nap la duong duy nhat.
        $xml = $this->goiGcs(['MA_GCS' => 'GCS-1']);

        $this->controller->uploadData($this->yeuCau(
            [$this->tepTaiLen('gcs.xml', $xml)],
            ['macskcb' => '01929']
        ));

        $this->assertSame('01929', CtdtHoSo::first()->macskcb);
    }

    /** @test */
    public function khong_co_tep_nao_thi_bao_loi_khong_nem()
    {
        $kq = $this->json($this->controller->uploadData($this->yeuCau([])));

        $this->assertFalse($kq['thanh_cong']);
        $this->assertNotEmpty($kq['thong_diep']);
    }

    /** @test */
    public function ghi_nhan_nguoi_nap()
    {
        $xml = $this->goiCt2025([[$this->chungTu('CT03', ['MA_YTE' => 'YT001'])]]);

        $this->controller->uploadData($this->yeuCau([$this->tepTaiLen('goi.xml', $xml)]));

        // Khong dang nhap trong test don vi nen imported_by de trong, nhung cot phai ton tai
        // va khong lam vo luong nap.
        $this->assertSame(1, CtdtHoSo::count());
    }
}
```

- [ ] **Step 2: Chạy test để xác nhận đỏ**

```bash
php vendor/bin/phpunit tests/Unit/Ctdt/CtdtUploadTest.php
```

Kỳ vọng: đỏ — `Call to undefined method ...::uploadData()`.

- [ ] **Step 3: Thêm `uploadData()` vào controller**

Thêm `use App\Services\Ctdt\CtdtImporter;` ở đầu tệp, rồi thêm phương thức:

```php
    /**
     * Nhan tep tai len, nap dong bo va tra ket qua theo TUNG tep.
     *
     * Nap dong bo (khong day job) giong BHYTXml3176Controller: ket qua hien ngay, va noi
     * gioi han bo nho tai cho thay vi phu thuoc mac dinh 128MB cua may chu.
     */
    public function uploadData(Request $request)
    {
        // Mot goi chung tu duoc phep toi 100MB. Giai base64 roi dung SimpleXML cho tung
        // phan lam bo nho phinh gap nhieu lan kich thuoc tep, ma may chu chi cho 128MB.
        // KHONG dung muc 4096M nhu cac lop Exports/: day la endpoint web ma Dropzone ban
        // nhieu request song song, cho moi request 4GB co the lam can RAM that.
        set_time_limit(600);
        ini_set('memory_limit', '512M');

        $tep = $request->file('xmls');

        if (empty($tep)) {
            return response()->json([
                'thanh_cong' => false,
                'thong_diep' => 'Không nhận được tệp nào.',
                'chi_tiet'   => [],
            ], 400);
        }

        $tep = is_array($tep) ? $tep : [$tep];

        $importer = new CtdtImporter();
        $tuyChon = [
            'macskcb'     => $request->input('macskcb'),
            'imported_by' => $request->user() ? $request->user()->loginname : null,
        ];

        $chiTiet = [];
        $tatCaThanhCong = true;

        foreach ($tep as $mot) {
            $ten = $mot->getClientOriginalName();

            // duong_dan_goc ghi TEN NGUOI DUNG THAY, khong phai duong dan tam cua PHP: tep
            // tam bi xoa ngay sau request nen luu duong dan do la luu mot con tro chet.
            $kq = $importer->nhapTuTep($mot->getRealPath(), array_merge($tuyChon, [
                'duong_dan_goc' => $ten,
            ]));

            $tatCaThanhCong = $tatCaThanhCong && $kq->thanhCong;

            $chiTiet[] = [
                'tep'           => $ten,
                'thanh_cong'    => (bool) $kq->thanhCong,
                'so_thanh_cong' => (int) $kq->soThanhCong,
                'so_that_bai'   => (int) $kq->soThatBai,
                'ly_do'         => $kq->lyDoThatBai,
                'ghi_de_da_gui' => $kq->dsGhiDeDaGui,
            ];
        }

        return response()->json([
            'thanh_cong' => $tatCaThanhCong,
            'thong_diep' => $tatCaThanhCong
                ? 'Đã nạp xong ' . count($chiTiet) . ' tệp.'
                : 'Có tệp không nạp được, xem chi tiết bên dưới.',
            'chi_tiet'   => $chiTiet,
        ]);
    }
```

- [ ] **Step 4: Dựng đầy `import.blade.php`**

Thay **toàn bộ** `resources/views/bhyt/ctdt/import.blade.php`:

```blade
@extends('adminlte::page')

@section('title', 'Nạp hồ sơ chứng từ điện tử')

@section('content_header')
<h1>Nạp <small>hồ sơ chứng từ điện tử</small></h1>
@stop

@push('after-styles')
<style>
    .canh-bao-ghi-de { color: #b94a48; }
</style>
@endpush

@section('content')
@include('includes.message')

<div class="panel panel-default">
    <div class="panel-body">
        <div class="row">
            <div class="col-sm-4">
                <label for="macskcb_nap">Cơ sở KCB</label>
                <select id="macskcb_nap" class="form-control">
                    <option value="">Lấy theo gói, hoặc cấu hình đơn vị</option>
                    @foreach ($danhSachCoSo as $ma => $nhan)
                        <option value="{{ $ma }}">{{ $nhan }}</option>
                    @endforeach
                </select>
                <p class="help-block">
                    Gói giấy chứng sinh không mang mã cơ sở — chọn ở đây nếu muốn ghi đè
                    giá trị mặc định của đơn vị.
                </p>
            </div>
        </div>
        <form action="{{ route('bhyt.ctdt.upload') }}" class="dropzone" id="ctdtUploadForm">
            {{ csrf_field() }}
        </form>
    </div>
</div>

<div class="panel panel-default">
    <div class="panel-body">
        <div class="h4">Kết quả nạp</div>
        <div class="table-responsive">
            <table class="table table-bordered table-hover" id="ketQuaNap">
                <thead>
                    <tr>
                        <th>Tệp</th>
                        <th>Hồ sơ vào được</th>
                        <th>Hồ sơ hỏng</th>
                        <th>Ghi chú</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>
@stop

@push('after-scripts')
<script src="{{ asset('js/dropzone.min.js') }}"></script>
<script>
Dropzone.options.ctdtUploadForm = {
    paramName: "xmls",
    maxFilesize: 100,
    acceptedFiles: ".xml",
    timeout: 600000,
    previewTemplate: '<div></div>',
    init: function () {
        var dz = this;

        dz.on("sending", function (file, xhr, formData) {
            formData.append("macskcb", $('#macskcb_nap').val());
        });

        dz.on("success", function (file, phanHoi) {
            veKetQua(phanHoi);
        });

        dz.on("error", function (file, loi, xhr) {
            // Loi tang HTTP (413, 500, het gio): Dropzone dua vao day chu khong vao success.
            // Bo qua thi nguoi dung thay bang trong va tuong tep da vao.
            var thongDiep = (loi && loi.thong_diep) ? loi.thong_diep
                : (typeof loi === 'string' ? loi : 'Tải lên thất bại');

            themDong(file.name, 0, 0, thongDiep, []);
        });
    }
};

function veKetQua(phanHoi) {
    if (!phanHoi || !phanHoi.chi_tiet) {
        return;
    }

    phanHoi.chi_tiet.forEach(function (ct) {
        themDong(ct.tep, ct.so_thanh_cong, ct.so_that_bai, ct.ly_do, ct.ghi_de_da_gui);
    });
}

function themDong(tep, soThanhCong, soThatBai, lyDo, ghiDe) {
    var ghiChu = lyDo ? $('<div>').text(lyDo).html() : '';

    if (ghiDe && ghiDe.length) {
        // Nguoi dung duoc phep ghi de, nhung phai BIET ho so nao vua mat trang thai gui.
        var dong = ghiDe.map(function (g) {
            return $('<div>').text('Hồ sơ ' + g.ma_ho_so + ' đã gửi (MaGD ' + g.ma_gd
                + ') vừa bị ghi đè').html();
        }).join('<br>');

        ghiChu += (ghiChu ? '<br>' : '') + '<span class="canh-bao-ghi-de">' + dong + '</span>';
    }

    $('#ketQuaNap tbody').append(
        '<tr>'
        + '<td>' + $('<div>').text(tep).html() + '</td>'
        + '<td>' + soThanhCong + '</td>'
        + '<td>' + soThatBai + '</td>'
        + '<td>' + ghiChu + '</td>'
        + '</tr>'
    );
}
</script>
@endpush
```

- [ ] **Step 5: Chạy test để xác nhận xanh**

```bash
php vendor/bin/phpunit tests/Unit/Ctdt/CtdtUploadTest.php
```

Kỳ vọng: `OK (6 tests)`.

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/BHYT/BHYTCtdtController.php resources/views/bhyt/ctdt/import.blade.php tests/Unit/Ctdt/CtdtUploadTest.php
git commit -m "feat(ctdt): man nap tep, ket qua theo tung tep va canh bao ghi de"
```

---

## Task 7: `CtdtNhanTruong` và `CtdtDetailTabs`

**Files:**
- Create: `app/Services/Ctdt/CtdtNhanTruong.php`
- Create: `app/Services/Ctdt/CtdtDetailTabs.php`
- Test: `tests/Unit/Ctdt/CtdtDetailTabsTest.php`

**Interfaces:**
- Consumes: `CtdtLoaiRegistry` (Giai đoạn 1), model `CtdtHoSo`, `CtdtChungTu`
- Produces:
  - `CtdtNhanTruong::cua($tenThe)` → nhãn tiếng Việt, lùi về chính tên thẻ nếu chưa có trong từ điển
  - `CtdtDetailTabs::cua(CtdtHoSo $hoSo)` → mảng `['ma' => string, 'nhan' => string, 'so_luong' => int]`, gồm các loại chứng từ hồ sơ **thực có**, cộng tab `__XML__` ở cuối
  - `CtdtDetailTabs::hopLe($hoSo, $ma)` → bool

- [ ] **Step 1: Viết test đỏ**

Tạo `tests/Unit/Ctdt/CtdtDetailTabsTest.php`:

```php
<?php

namespace Tests\Unit\Ctdt;

use Tests\TestCase;
use Tests\Support\DungBangCtdtSqlite;
use App\Services\Ctdt\CtdtDetailTabs;
use App\Services\Ctdt\CtdtNhanTruong;
use App\Models\BHYT\Ctdt\CtdtHoSo;
use App\Models\BHYT\Ctdt\CtdtChungTu;

class CtdtDetailTabsTest extends TestCase
{
    use DungBangCtdtSqlite;

    protected function setUp()
    {
        parent::setUp();
        $this->chuanBiBangCtdt();
    }

    private function hoSoVoi(array $loai)
    {
        $hoSo = CtdtHoSo::create([
            'ma_ho_so' => 'YT001', 'dich_vu' => 'CT2025', 'loai_hs' => '39',
            'macskcb' => '01929', 'so_chung_tu' => count($loai),
        ]);

        foreach ($loai as $l) {
            CtdtChungTu::create([
                'ho_so_id' => $hoSo->id, 'loai_ho_so' => $l, 'noi_dung_goc' => '<' . $l . '/>',
            ]);
        }

        return $hoSo->fresh();
    }

    /** @test */
    public function chi_hien_tab_cua_loai_ho_so_THUC_CO()
    {
        // Khac XML3176 von co dinh XML1-15: mot ho so chung tu hiem khi co du chin loai, va
        // chin tab trong la chin lan nguoi dung bam vao roi thay khong co gi.
        $tabs = CtdtDetailTabs::cua($this->hoSoVoi(['CT03', 'CT04']));

        $ma = array_column($tabs, 'ma');

        $this->assertContains('CT03', $ma);
        $this->assertContains('CT04', $ma);
        $this->assertNotContains('CT06', $ma);
        $this->assertNotContains('GIAYBAOTU', $ma);
    }

    /** @test */
    public function luon_co_tab_xml_goc_o_cuoi()
    {
        // Khi cong bao 205 (fileBase64Str khong hop le), xem XML nguyen van la cach duy nhat
        // doi chieu.
        $tabs = CtdtDetailTabs::cua($this->hoSoVoi(['CT03']));

        $cuoi = end($tabs);

        $this->assertSame('__XML__', $cuoi['ma']);
        $this->assertNotEmpty($cuoi['nhan']);
    }

    /** @test */
    public function moi_tab_co_nhan_tieng_viet_lay_tu_lop_loai()
    {
        $tabs = CtdtDetailTabs::cua($this->hoSoVoi(['CT03']));

        $this->assertSame('Giấy ra viện', $tabs[0]['nhan']);
    }

    /** @test */
    public function dem_so_chung_tu_cung_loai()
    {
        $tabs = CtdtDetailTabs::cua($this->hoSoVoi(['CT03', 'CT03', 'CT04']));

        $theoMa = [];

        foreach ($tabs as $t) {
            $theoMa[$t['ma']] = $t['so_luong'];
        }

        $this->assertSame(2, $theoMa['CT03']);
        $this->assertSame(1, $theoMa['CT04']);
    }

    /** @test */
    public function ho_so_khong_co_chung_tu_nao_van_co_tab_xml_goc()
    {
        $tabs = CtdtDetailTabs::cua($this->hoSoVoi([]));

        $this->assertCount(1, $tabs);
        $this->assertSame('__XML__', $tabs[0]['ma']);
    }

    /** @test */
    public function hop_le_chan_tab_ho_so_khong_co()
    {
        // Tham so {loai} den tu URL. Khong doi chieu thi bat ky ai cung ep duoc controller
        // truy van mot bang khong lien quan toi ho so dang xem.
        $hoSo = $this->hoSoVoi(['CT03']);

        $this->assertTrue(CtdtDetailTabs::hopLe($hoSo, 'CT03'));
        $this->assertTrue(CtdtDetailTabs::hopLe($hoSo, '__XML__'));
        $this->assertFalse(CtdtDetailTabs::hopLe($hoSo, 'CT04'));
        $this->assertFalse(CtdtDetailTabs::hopLe($hoSo, 'khong_ton_tai'));
    }

    /** @test */
    public function nhan_truong_dich_cac_the_pho_bien()
    {
        $this->assertSame('Họ tên', CtdtNhanTruong::cua('HO_TEN'));
        $this->assertSame('Mã thẻ BHYT', CtdtNhanTruong::cua('MA_THE'));
        $this->assertSame('Ngày vào', CtdtNhanTruong::cua('NGAY_VAO'));
    }

    /** @test */
    public function the_chua_co_trong_tu_dien_thi_lui_ve_chinh_ten_the()
    {
        // BHXH them the moi truoc khi ta kip cap nhat tu dien la chuyen se xay ra. Hien ten
        // the con hon hien o trong.
        $this->assertSame('THE_MOI_TINH', CtdtNhanTruong::cua('THE_MOI_TINH'));
    }
}
```

- [ ] **Step 2: Chạy test để xác nhận đỏ**

```bash
php vendor/bin/phpunit tests/Unit/Ctdt/CtdtDetailTabsTest.php
```

Kỳ vọng: đỏ — `Class 'App\Services\Ctdt\CtdtDetailTabs' not found`.

- [ ] **Step 3: Viết `CtdtNhanTruong`**

Tạo `app/Services/Ctdt/CtdtNhanTruong.php`:

```php
<?php

namespace App\Services\Ctdt;

/**
 * Tu dien nhan tieng Viet cho ten the PL02.
 *
 * VI SAO MOT TU DIEN CHUNG chu khong phai nhan rieng cho tung loai: ten the lap lai rat
 * nhieu giua chin loai (MA_THE, HO_TEN, NGAY_SINH, NGAY_VAO... co mat o gan het). Khai
 * rieng cho tung loai la chep lai cung mot nhan chin lan, va chin ban se lech nhau.
 *
 * LUI VE CHINH TEN THE khi chua co trong tu dien: BHXH them the moi truoc khi ta kip cap
 * nhat la chuyen se xay ra, va hien "SO_NGAY_MOI" con hon hien mot o trong.
 */
class CtdtNhanTruong
{
    const TU_DIEN = [
        // Dinh danh ho so
        'SO_LUU_TRU'      => 'Số lưu trữ',
        'MA_YTE'          => 'Mã y tế',
        'MA_BHXH'         => 'Mã số BHXH',
        'MA_THE'          => 'Mã thẻ BHYT',
        'MA_CT'           => 'Mã chứng từ',
        'SO_SERI'         => 'Số seri',
        'MA_KHOA'         => 'Mã khoa',
        'MA_BN'           => 'Mã bệnh nhân',
        'MA_HSBA'         => 'Mã hồ sơ bệnh án',
        'MACSKCB'         => 'Mã cơ sở KCB',
        'DIACHI_CSKCB'    => 'Địa chỉ cơ sở KCB',
        'SO_KCB'          => 'Số khám chữa bệnh',
        'MAU_SO'          => 'Mẫu số',

        // Nhan than
        'HO_TEN'          => 'Họ tên',
        'NGAY_SINH'       => 'Ngày sinh',
        'GIOI_TINH'       => 'Giới tính',
        'MA_DANTOC'       => 'Mã dân tộc',
        'MA_DAN_TOC'      => 'Mã dân tộc',
        'TEN_DAN_TOC'     => 'Tên dân tộc',
        'MA_QUOCTICH'     => 'Mã quốc tịch',
        'NGHE_NGHIEP'     => 'Nghề nghiệp',
        'DIA_CHI'         => 'Địa chỉ',
        'HO_TEN_CHA'      => 'Họ tên cha',
        'HO_TEN_ME'       => 'Họ tên mẹ',
        'NGUOI_GIAM_HO'   => 'Người giám hộ',
        'NGUOI_THANTHICH' => 'Người thân thích',

        // Giay to tuy than
        'LOAI_GIAYTO'     => 'Loại giấy tờ',
        'SO_CCCD'         => 'Số giấy tờ',
        'SO_GIAYTO'       => 'Số giấy tờ',
        'NGAYCAP_CCCD'    => 'Ngày cấp giấy tờ',
        'NGAY_CAP'        => 'Ngày cấp',
        'NOICAP_CCCD'     => 'Nơi cấp giấy tờ',
        'NOI_CAP'         => 'Nơi cấp',

        // Cu tru
        'NOI_CU_TRU_NND'      => 'Nơi cư trú',
        'MATINH_CU_TRU'       => 'Mã tỉnh cư trú',
        'MAHUYEN_CU_TRU'      => 'Mã huyện cư trú',
        'MAXA_CU_TRU'         => 'Mã xã cư trú',
        'MA_TINHCUTRU'        => 'Mã tỉnh cư trú',
        'MA_XACUTRU'          => 'Mã xã cư trú',
        'DCHI_THUONGTRU'      => 'Địa chỉ thường trú',
        'MATINH_THUONGTRU'    => 'Mã tỉnh thường trú',
        'MAHUYEN_THUONGTRU'   => 'Mã huyện thường trú',
        'MAXA_THUONGTRU'      => 'Mã xã thường trú',
        'DCHI_HIENTAI'        => 'Địa chỉ hiện tại',
        'MATINH_HIENTAI'      => 'Mã tỉnh hiện tại',
        'MAHUYEN_HIENTAI'     => 'Mã huyện hiện tại',
        'MAXA_HIENTAI'        => 'Mã xã hiện tại',

        // Dot dieu tri
        'NGAY_VAO'            => 'Ngày vào',
        'NGAY_RA'             => 'Ngày ra',
        'NGAYGIO_VV'          => 'Ngày giờ vào viện',
        'TU_NGAY'             => 'Từ ngày',
        'DEN_NGAY'            => 'Đến ngày',
        'NGAY_KCB'            => 'Ngày khám chữa bệnh',
        'NGOAITRU_TUNGAY'     => 'Ngoại trú từ ngày',
        'NGOAITRU_DENNGAY'    => 'Ngoại trú đến ngày',
        'CHAN_DOAN'           => 'Chẩn đoán',
        'CHAN_DOAN_VAO'       => 'Chẩn đoán vào',
        'CHAN_DOAN_RA'        => 'Chẩn đoán ra',
        'CHANDOAN_DIEUTRI'    => 'Chẩn đoán điều trị',
        'PP_DIEUTRI'          => 'Phương pháp điều trị',
        'QT_BENHLY'           => 'Quá trình bệnh lý',
        'TOMTAT_KQ'           => 'Tóm tắt kết quả',
        'MO_TA'               => 'Mô tả',
        'KET_LUAN'            => 'Kết luận',
        'GHI_CHU'             => 'Ghi chú',
        'TT_RAVIEN'           => 'Tình trạng ra viện',
        'LYDO_VVIEN'          => 'Lý do vào viện',
        'TIEN_SU_BENH'        => 'Tiền sử bệnh',
        'DAU_HIEU_LAM_SANG'   => 'Dấu hiệu lâm sàng',
        'HUONG_DIEU_TRI'      => 'Hướng điều trị',
        'NOI_KHOA'            => 'Điều trị nội khoa',
        'IS_NOI_KHOA'         => 'Có điều trị nội khoa',
        'PHAU_THUAT_THU_THUAT'    => 'Phẫu thuật, thủ thuật',
        'IS_PHAU_THUAT_THU_THUAT' => 'Có phẫu thuật, thủ thuật',
        'TINHTRANGBENHHIENTAI'    => 'Tình trạng bệnh hiện tại',

        // Chan doan ICD
        'BENH_ICD10_ID'   => 'Mã bệnh ICD10',
        'BENH_ICD10_MA'   => 'Mã bệnh ICD10',
        'BENH_ICD10_TEN'  => 'Tên bệnh ICD10',
        'BENHICD10_ID'    => 'Mã bệnh ICD10',
        'TENBENHNICD10'   => 'Tên bệnh ICD10',

        // Thai san
        'DINH_CHI_THAI_NGHEN'     => 'Đình chỉ thai nghén',
        'TUOI_THAI'               => 'Tuổi thai',
        'NGAY_SINHCON'            => 'Ngày sinh con',
        'NGAY_CHETCON'            => 'Ngày chết con',
        'SO_CONCHET'              => 'Số con chết',
        'IS_NGHIDUONGTHAI'        => 'Có nghỉ dưỡng thai',
        'SO_NGAY_NGHIDUONGTHAI'   => 'Số ngày nghỉ dưỡng thai',
        'NGAY_DINH_CHI_THAINGHEN' => 'Ngày đình chỉ thai nghén',
        'LOAI_PHUONG_PHAP'        => 'Loại phương pháp',
        'LOAI_PP_DIEU_TRI_VOSINH' => 'Loại phương pháp điều trị vô sinh',

        // Nguoi hanh nghe, don vi
        'THU_TRUONG_DVI'      => 'Thủ trưởng đơn vị',
        'THU_TRUONG_DV'       => 'Thủ trưởng đơn vị',
        'TTRUONG_DVI'         => 'Thủ trưởng đơn vị',
        'DAI_DIEN_DVI'        => 'Đại diện đơn vị',
        'NGUOI_DAI_DIEN'      => 'Người đại diện',
        'TEN_DONVI'           => 'Tên đơn vị',
        'TEN_DVI'             => 'Tên đơn vị',
        'DON_VI'              => 'Đơn vị',
        'MA_CCHN'             => 'Mã chứng chỉ hành nghề',
        'MA_CCHN_BS'          => 'Mã chứng chỉ hành nghề bác sĩ',
        'MA_CCHN_TRUONGKHOA'  => 'Mã chứng chỉ hành nghề trưởng khoa',
        'TEN_TRUONGKHOA'      => 'Tên trưởng khoa',
        'TEN_NGUOI_HANH_NGHE' => 'Tên người hành nghề',
        'MA_BS'               => 'Mã bác sĩ',
        'TEN_BS'              => 'Tên bác sĩ',
        'NGUOI_GHIGIAY'       => 'Người ghi giấy',
        'NGUOI_GHI_PHIEU'     => 'Người ghi phiếu',
        'NGUOI_DO_DE'         => 'Người đỡ đẻ',
        'MA_TTDV'             => 'Mã định danh thủ trưởng cơ sở',

        // Chung tu
        'NGAY_CT'             => 'Ngày chứng từ',
        'NGAY_CHUNG_TU'       => 'Ngày chứng từ',
        'TEKT'                => 'Trẻ em không thẻ',
        'IS_LAO_GIAI_DOAN_NANG'      => 'Lao giai đoạn nặng',
        'IS_XO_GAN_GIAI_DOAN_MAT_BU' => 'Xơ gan giai đoạn mất bù',

        // Giay bao tu
        'MA_GBT'          => 'Mã giấy báo tử',
        'NGAY_TV'         => 'Ngày tử vong',
        'TINH_TRANG_TV'   => 'Tình trạng tử vong',
        'NGUYENNHAN_TV'   => 'Nguyên nhân tử vong',
        'SO_BAOTU'        => 'Số báo tử',
        'QUYEN_SO'        => 'Quyển số',
        'NGAY_CAPGIAYBT'  => 'Ngày cấp giấy báo tử',
        'SO_BAOTU_BD'     => 'Số báo tử ban đầu',
        'QUYEN_SO_BD'     => 'Quyển số ban đầu',

        // Giay chung sinh
        'MA_GCS'            => 'Mã giấy chứng sinh',
        'TEN_CON'           => 'Tên con',
        'GIOI_TINH_CON'     => 'Giới tính con',
        'SO_CON'            => 'Số con',
        'LAN_SINH'          => 'Lần sinh',
        'SO_CON_SONG'       => 'Số con sống',
        'CAN_NANG_CON'      => 'Cân nặng con (gam)',
        'NGAY_SINH_CON'     => 'Ngày sinh con',
        'NOI_SINH_CON'      => 'Nơi sinh con',
        'TINH_TRANG_CON'    => 'Tình trạng con',
        'SINHCON_PHAUTHUAT' => 'Sinh con phẫu thuật',
        'SINHCON_DUOI32TUAN'=> 'Sinh con dưới 32 tuần',
        'MA_THE_TAM'        => 'Mã thẻ tạm',
        'SO'                => 'Số',
        'CAP_LAN_DAU'       => 'Cấp lần đầu',
    ];

    /**
     * Hau to phan biet BON nhom nguoi trong giay chung sinh. Ghep vao sau nhan goc thay vi
     * khai rieng 60 dong: cung mot the SO_CCCD xuat hien o ca bon nhom.
     */
    const HAU_TO = [
        '_CHA_MTH' => ' (cha của mẹ thay thế)',
        '_CHA_NND' => ' (cha của người đẻ)',
        '_MTH'     => ' (mẹ thay thế)',
        '_NND'     => ' (người đẻ)',
    ];

    /** @return string */
    public static function cua($tenThe)
    {
        $tenThe = (string) $tenThe;

        if (isset(self::TU_DIEN[$tenThe])) {
            return self::TU_DIEN[$tenThe];
        }

        // Thu bo hau to nhom nguoi roi tra lai. Thu tu DUYET quan trong: '_CHA_MTH' phai
        // duoc thu TRUOC '_MTH', khong thi 'HO_TEN_CHA_MTH' se bi cat thanh 'HO_TEN_CHA'
        // va gan nhan cua nhom sai.
        foreach (self::HAU_TO as $hauTo => $themVao) {
            if (substr($tenThe, -strlen($hauTo)) === $hauTo) {
                $goc = substr($tenThe, 0, -strlen($hauTo));

                if (isset(self::TU_DIEN[$goc])) {
                    return self::TU_DIEN[$goc] . $themVao;
                }
            }
        }

        return $tenThe;
    }
}
```

- [ ] **Step 4: Viết `CtdtDetailTabs`**

Tạo `app/Services/Ctdt/CtdtDetailTabs.php`:

```php
<?php

namespace App\Services\Ctdt;

use App\Models\BHYT\Ctdt\CtdtHoSo;

/**
 * Sinh danh sach tab cua man chi tiet, tu chung tu ho so THUC CO.
 *
 * KHAC XML3176 von co dinh XML1-15: mot ho so chung tu hiem khi co du chin loai, va chin
 * tab trong la chin lan nguoi dung bam vao roi thay khong co gi.
 */
class CtdtDetailTabs
{
    /** Tab xem XML nguyen van, luon dung cuoi. */
    const TAB_XML = '__XML__';

    /**
     * @return array Mang ['ma' =>, 'nhan' =>, 'so_luong' =>], tab XML goc o cuoi
     */
    public static function cua(CtdtHoSo $hoSo)
    {
        $dem = [];

        foreach ($hoSo->chungTu as $chungTu) {
            $loai = $chungTu->loai_ho_so;
            $dem[$loai] = isset($dem[$loai]) ? $dem[$loai] + 1 : 1;
        }

        $tabs = [];

        foreach ($dem as $loai => $soLuong) {
            $tabs[] = [
                'ma'       => $loai,
                'nhan'     => self::nhanLoai($loai),
                'so_luong' => $soLuong,
            ];
        }

        // Khi cong bao 205 (fileBase64Str khong hop le), xem XML nguyen van la cach duy nhat
        // doi chieu xem minh da gui gi.
        $tabs[] = ['ma' => self::TAB_XML, 'nhan' => 'XML gốc', 'so_luong' => count($hoSo->chungTu)];

        return $tabs;
    }

    /**
     * Tham so {loai} den tu URL nen PHAI doi chieu truoc khi dung. Khong doi chieu thi bat
     * ky ai cung ep duoc controller truy van mot bang khong lien quan toi ho so dang xem.
     */
    public static function hopLe(CtdtHoSo $hoSo, $ma)
    {
        foreach (self::cua($hoSo) as $tab) {
            if ($tab['ma'] === $ma) {
                return true;
            }
        }

        return false;
    }

    /** Nhan lay tu chinh lop loai, khong go tay lai lan thu hai. */
    private static function nhanLoai($loai)
    {
        if (!CtdtLoaiRegistry::co($loai)) {
            return $loai;
        }

        $lop = CtdtLoaiRegistry::cho($loai);

        return $lop::tenTab();
    }
}
```

- [ ] **Step 5: Chạy test để xác nhận xanh**

```bash
php vendor/bin/phpunit tests/Unit/Ctdt/CtdtDetailTabsTest.php
```

Kỳ vọng: `OK (8 tests)`.

- [ ] **Step 6: Commit**

```bash
git add app/Services/Ctdt/CtdtNhanTruong.php app/Services/Ctdt/CtdtDetailTabs.php tests/Unit/Ctdt/CtdtDetailTabsTest.php
git commit -m "feat(ctdt): tu dien nhan truong va tab dong cua man chi tiet"
```

---

## Task 8: Màn chi tiết, xóa hồ sơ, và lưới an toàn

**Files:**
- Modify: `app/Http/Controllers/BHYT/BHYTCtdtController.php` (thêm `detail()`, `detailTab()`, `delete()`)
- Create: `resources/views/bhyt/ctdt/detail.blade.php`
- Create: `resources/views/bhyt/ctdt/tab-chung-tu.blade.php`
- Create: `resources/views/bhyt/ctdt/tab-xml-goc.blade.php`
- Test: `tests/Unit/Ctdt/CtdtChiTietTest.php`

**Interfaces:**
- Consumes: `CtdtDetailTabs`, `CtdtNhanTruong` (Task 7); `CtdtLoaiRegistry` (Giai đoạn 1)
- Produces: `detail($ma_ho_so)`, `detailTab($ma_ho_so, $loai)`, `delete($ma_ho_so)`

**Vì sao một blade chung, không phải chín blade:** mỗi lớp loại đã khai `truong()` là ánh xạ
`TÊN THẺ => cột`. Chín blade gần giống nhau là chín bản sẽ trôi khỏi nhau, và thêm loại chứng từ
mới sẽ phải viết blade thứ mười. Một blade duyệt `truong()` cộng một từ điển nhãn dùng chung
làm được cùng việc với ít mã hơn hẳn.

- [ ] **Step 1: Viết test đỏ**

Tạo `tests/Unit/Ctdt/CtdtChiTietTest.php`:

```php
<?php

namespace Tests\Unit\Ctdt;

use Tests\TestCase;
use Tests\Support\DungBangCtdtSqlite;
use Tests\Support\GoiCtdtMau;
use App\Http\Controllers\BHYT\BHYTCtdtController;
use App\Services\Ctdt\CtdtImporter;
use App\Models\BHYT\Ctdt\CtdtHoSo;
use App\Models\BHYT\Ctdt\CtdtChungTu;
use App\Models\BHYT\Ctdt\CtdtCt03;

class CtdtChiTietTest extends TestCase
{
    use DungBangCtdtSqlite;
    use GoiCtdtMau;

    /** @var BHYTCtdtController */
    private $controller;

    protected function setUp()
    {
        parent::setUp();
        $this->chuanBiBangCtdt();
        $this->controller = new BHYTCtdtController();
        config(['organization.BHYT.ma_cskcb' => '01013']);
    }

    private function napMau()
    {
        $xml = $this->goiCt2025([[
            $this->chungTu('CT03', [
                'MA_YTE' => 'YT001', 'HO_TEN' => 'Nguyen Van Test', 'CHAN_DOAN' => 'Dau bung',
            ]),
            $this->chungTu('CT04', ['MA_CT' => 'CT-1']),
        ]]);

        (new CtdtImporter())->nhapTuChuoi($xml);

        return CtdtHoSo::where('ma_ho_so', 'YT001')->firstOrFail();
    }

    /** @test */
    public function man_chi_tiet_tra_ho_so_va_danh_sach_tab()
    {
        $this->napMau();

        $view = $this->controller->detail('YT001');
        $duLieu = $view->getData();

        $this->assertSame('YT001', $duLieu['hoSo']->ma_ho_so);
        $this->assertSame('bhyt.ctdt.detail', $view->getName());

        $ma = array_column($duLieu['tabs'], 'ma');
        $this->assertContains('CT03', $ma);
        $this->assertContains('CT04', $ma);
        $this->assertContains('__XML__', $ma);
    }

    /** @test */
    public function ma_ho_so_khong_ton_tai_thi_nem()
    {
        // firstOrFail() nem ModelNotFoundException; Laravel chi doi no thanh 404 o tang xu ly
        // ngoai le cua HTTP, ma test nay goi thang controller nen thay ngoai le goc.
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        $this->controller->detail('KHONG_TON_TAI');
    }

    /** @test */
    public function tab_chung_tu_tra_du_nhan_va_gia_tri()
    {
        $this->napMau();

        $duLieu = $this->controller->detailTab('YT001', 'CT03')->getData();

        $this->assertSame('CT03', $duLieu['loai']);
        $this->assertCount(1, $duLieu['banGhi']);

        $dong = $duLieu['banGhi'][0];

        // Moi dong la ['nhan' => nhan tieng Viet, 'gia_tri' => gia tri]
        $theoNhan = [];

        foreach ($dong as $o) {
            $theoNhan[$o['nhan']] = $o['gia_tri'];
        }

        $this->assertSame('Nguyen Van Test', $theoNhan['Họ tên']);
        $this->assertSame('Dau bung', $theoNhan['Chẩn đoán']);
        $this->assertSame('YT001', $theoNhan['Mã y tế']);
    }

    /** @test */
    public function tab_chung_tu_bo_qua_o_trong_de_khoi_lam_nhieu_man_hinh()
    {
        // CT03 co 33 truong; mot ho so that thuong chi dien mot phan. Hien du 33 dong trong
        // do lam nguoi doc phai loc bang mat.
        $this->napMau();

        $duLieu = $this->controller->detailTab('YT001', 'CT03')->getData();

        foreach ($duLieu['banGhi'][0] as $o) {
            $this->assertNotSame('', (string) $o['gia_tri'], 'O trong khong duoc hien');
        }
    }

    /** @test */
    public function tab_xml_goc_tra_noi_dung_nguyen_van()
    {
        $this->napMau();

        $duLieu = $this->controller->detailTab('YT001', '__XML__')->getData();

        $this->assertCount(2, $duLieu['chungTu']);
        $this->assertContains('<MA_YTE>YT001</MA_YTE>', $duLieu['chungTu'][0]->noi_dung_goc);
    }

    /** @test */
    public function tab_ho_so_khong_co_thi_404()
    {
        // Tham so {loai} den tu URL. Khong doi chieu thi ep duoc controller truy van mot
        // bang khong lien quan toi ho so dang xem.
        $this->napMau();

        $this->expectException(\Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class);

        $this->controller->detailTab('YT001', 'GIAYBAOTU');
    }

    /** @test */
    public function xoa_ho_so_don_sach_ca_chung_tu_va_chi_tiet()
    {
        $this->napMau();

        $this->controller->delete('YT001');

        $this->assertSame(0, CtdtHoSo::count());
        $this->assertSame(0, CtdtChungTu::count());
        $this->assertSame(0, CtdtCt03::count());
    }

    /** @test */
    public function xoa_ho_so_khong_ton_tai_thi_nem()
    {
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        $this->controller->delete('KHONG_TON_TAI');
    }

    /** @test */
    public function ma_ho_so_co_dau_thang_van_tra_dung_ho_so()
    {
        // Ho so roi vao nhanh lui GUID co ma dang 'Id-abc#1'. Neu URL khong ma hoa hoac
        // controller cat sai thi man chi tiet tra 404 cho dung nhung ho so kho tim nhat.
        (new CtdtImporter())->nhapTuChuoi(
            $this->goiCt2025([[$this->chungTu('CT04', ['MA_CT' => 'CT-1'])]], ['id' => 'Id-abc'])
        );

        $view = $this->controller->detail('Id-abc#1');

        $this->assertSame('Id-abc#1', $view->getData()['hoSo']->ma_ho_so);
    }
}
```

- [ ] **Step 2: Chạy test để xác nhận đỏ**

```bash
php vendor/bin/phpunit tests/Unit/Ctdt/CtdtChiTietTest.php
```

Kỳ vọng: đỏ — `Call to undefined method ...::detail()`.

- [ ] **Step 3: Thêm ba phương thức vào controller**

Thêm `use` ở đầu tệp:

```php
use App\Services\Ctdt\CtdtDetailTabs;
use App\Services\Ctdt\CtdtNhanTruong;
use App\Services\Ctdt\CtdtLoaiRegistry;
use App\Services\Ctdt\CtdtLuuHoSo;
use App\Models\BHYT\Ctdt\CtdtHoSo;
```

Thêm ba phương thức:

```php
    public function detail($ma_ho_so)
    {
        $hoSo = CtdtHoSo::with('chungTu')->where('ma_ho_so', $ma_ho_so)->firstOrFail();

        return view('bhyt.ctdt.detail', [
            'hoSo' => $hoSo,
            'tabs' => CtdtDetailTabs::cua($hoSo),
        ]);
    }

    /**
     * Mot tab, nap luoi khi nguoi dung bam vao.
     *
     * Chin loai x toi 69 truong ma nap het mot luot thi trang nang vo ich - phan lon tab
     * khong bao gio duoc mo.
     */
    public function detailTab($ma_ho_so, $loai)
    {
        $hoSo = CtdtHoSo::with('chungTu')->where('ma_ho_so', $ma_ho_so)->firstOrFail();

        if (!CtdtDetailTabs::hopLe($hoSo, $loai)) {
            abort(404);
        }

        if ($loai === CtdtDetailTabs::TAB_XML) {
            return view('bhyt.ctdt.tab-xml-goc', [
                'hoSo'    => $hoSo,
                'chungTu' => $hoSo->chungTu->values(),
            ]);
        }

        $lop = CtdtLoaiRegistry::cho($loai);
        $truong = $lop::truong();
        $tenModel = $lop::model();

        $banGhi = [];

        foreach ($hoSo->chungTu->where('loai_ho_so', $loai) as $chungTu) {
            $chiTiet = $tenModel::where('chung_tu_id', $chungTu->id)->first();

            if ($chiTiet === null) {
                continue;
            }

            $dong = [];

            foreach ($truong as $the => $cot) {
                $giaTri = $chiTiet->{$cot};

                // Bo qua o trong: CT03 co 33 truong, giay chung sinh 69, ma mot ho so that
                // thuong chi dien mot phan. Hien du ca truong trong lam nguoi doc phai loc
                // bang mat.
                if ($giaTri === null || trim((string) $giaTri) === '') {
                    continue;
                }

                $dong[] = ['nhan' => CtdtNhanTruong::cua($the), 'gia_tri' => (string) $giaTri];
            }

            $banGhi[] = $dong;
        }

        return view('bhyt.ctdt.tab-chung-tu', [
            'hoSo'   => $hoSo,
            'loai'   => $loai,
            'nhan'   => $lop::tenTab(),
            'banGhi' => $banGhi,
        ]);
    }

    /**
     * Xoa han mot ho so. Route da gioi han checkrole:superadministrator - xoa mot ho so da
     * co MaGD la xoa dau vet doi soat voi BHXH.
     */
    public function delete($ma_ho_so)
    {
        $hoSo = CtdtHoSo::where('ma_ho_so', $ma_ho_so)->firstOrFail();

        // Dung lai CtdtLuuHoSo::xoaHoSoCu(): no biet xoa ban ghi chi tiet o dung bang cua
        // tung loai. Dua vao khoa ngoai cascade thi tren SQLite (va tren may chu neu bang
        // khong phai InnoDB) se de lai rac ma khong ai phat hien.
        $luu = new CtdtLuuHoSo();
        $luu->xoaHoSoCu($ma_ho_so);

        $hoSo->delete();

        return response()->json(['thanh_cong' => true]);
    }
```

- [ ] **Step 4: Viết `detail.blade.php`**

Tạo `resources/views/bhyt/ctdt/detail.blade.php`:

```blade
@extends('adminlte::page')

@section('title', 'Chi tiết hồ sơ ' . $hoSo->ma_ho_so)

@section('content_header')
<h1>Chi tiết hồ sơ <small>{{ $hoSo->ma_ho_so }}</small></h1>
@stop

@section('content')
@include('includes.message')

<div class="panel panel-default">
    <div class="panel-body">
        <div class="row">
            <div class="col-sm-3"><strong>Dịch vụ:</strong>
                {{ array_get(config('ctdt.dich_vu'), $hoSo->dich_vu . '.ten', $hoSo->dich_vu) }}
            </div>
            <div class="col-sm-2"><strong>Mã CSKCB:</strong> {{ $hoSo->macskcb }}</div>
            <div class="col-sm-2"><strong>Số chứng từ:</strong> {{ $hoSo->so_chung_tu }}</div>
            <div class="col-sm-2"><strong>Số lỗi:</strong> {{ $hoSo->so_loi }}</div>
            <div class="col-sm-3"><strong>Nạp lúc:</strong> {{ $hoSo->imported_at }}</div>
        </div>
        <div class="row" style="margin-top:8px">
            <div class="col-sm-3"><strong>Ký số:</strong> {{ $hoSo->is_signed ? 'Đã ký' : 'Chưa ký' }}</div>
            <div class="col-sm-3"><strong>MaGD:</strong> {{ $hoSo->ma_gd ?: '—' }}</div>
            <div class="col-sm-3"><strong>Mã kết quả:</strong> {{ $hoSo->ma_ket_qua ?: '—' }}</div>
            <div class="col-sm-3"><strong>Tiếp nhận:</strong> {{ $hoSo->thoi_gian_tiep_nhan ?: '—' }}</div>
        </div>
        @if ($hoSo->lich_su_gui)
        <div class="row" style="margin-top:8px">
            <div class="col-sm-12">
                <strong>Lịch sử gửi:</strong>
                <pre style="white-space:pre-wrap">{{ $hoSo->lich_su_gui }}</pre>
            </div>
        </div>
        @endif
    </div>
</div>

<div class="nav-tabs-custom">
    <ul class="nav nav-tabs" id="ctdt-tabs">
        @foreach ($tabs as $i => $tab)
        <li class="{{ $i === 0 ? 'active' : '' }}">
            <a href="#" data-loai="{{ $tab['ma'] }}">
                {{ $tab['nhan'] }}
                @if ($tab['so_luong'] > 1)<span class="badge">{{ $tab['so_luong'] }}</span>@endif
            </a>
        </li>
        @endforeach
    </ul>
    <div class="tab-content">
        <div id="noi-dung-tab"><p class="text-muted">Đang tải…</p></div>
    </div>
</div>
@stop

@push('after-scripts')
<script>
$(function () {
    // ma_ho_so co the chua dau '#' (nhanh lui GUID). Khong ma hoa thi trinh duyet cat tu
    // dau '#' va yeu cau tro sai ho so.
    var goc = "{{ route('bhyt.ctdt.detail.tab', ['ma_ho_so' => '__MA__', 'loai' => '__LOAI__']) }}"
              .replace('__MA__', encodeURIComponent(@json($hoSo->ma_ho_so)));

    function napTab(loai) {
        $('#noi-dung-tab').html('<p class="text-muted">Đang tải…</p>');

        $.get(goc.replace('__LOAI__', encodeURIComponent(loai)))
            .done(function (html) { $('#noi-dung-tab').html(html); })
            .fail(function () {
                $('#noi-dung-tab').html('<p class="text-danger">Không tải được nội dung tab.</p>');
            });
    }

    $('#ctdt-tabs a').on('click', function (e) {
        e.preventDefault();
        $('#ctdt-tabs li').removeClass('active');
        $(this).closest('li').addClass('active');
        napTab($(this).data('loai'));
    });

    var dau = $('#ctdt-tabs a').first();

    if (dau.length) {
        napTab(dau.data('loai'));
    }
});
</script>
@endpush
```

- [ ] **Step 5: Viết hai blade tab**

Tạo `resources/views/bhyt/ctdt/tab-chung-tu.blade.php`:

```blade
{{-- MOT blade chung cho ca chin loai chung tu.

     Moi lop loai da khai truong() la anh xa TEN THE => cot, nen chin blade gan giong nhau
     la chin ban se troi khoi nhau. Bien vao:
       $nhan   — nhan loai chung tu
       $banGhi — mang cac chung tu; moi chung tu la mang ['nhan' =>, 'gia_tri' =>] --}}
@if (empty($banGhi))
    <p class="text-muted">Không có dữ liệu cho {{ $nhan }}.</p>
@else
    @foreach ($banGhi as $i => $dong)
    <div class="panel panel-default">
        <div class="panel-heading">
            {{ $nhan }}@if (count($banGhi) > 1) — bản {{ $i + 1 }}/{{ count($banGhi) }}@endif
        </div>
        <div class="panel-body">
            <table class="table table-condensed table-bordered">
                <tbody>
                    @foreach ($dong as $o)
                    <tr>
                        <th style="width:35%">{{ $o['nhan'] }}</th>
                        <td>{{ $o['gia_tri'] }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endforeach
@endif
```

Tạo `resources/views/bhyt/ctdt/tab-xml-goc.blade.php`:

```blade
{{-- XML nguyen van cua tung chung tu.

     Khi cong bao 205 (fileBase64Str khong hop le), doi chieu noi dung da gui la cach duy
     nhat tim ra minh sai o dau. --}}
@if ($chungTu->isEmpty())
    <p class="text-muted">Hồ sơ không có chứng từ nào.</p>
@else
    @foreach ($chungTu as $ct)
    <div class="panel panel-default">
        <div class="panel-heading">{{ $ct->loai_ho_so }} — {{ $ct->ma_chung_tu ?: '(không có mã)' }}</div>
        <div class="panel-body">
            <pre style="white-space:pre-wrap; max-height:400px; overflow:auto">{{ $ct->noi_dung_goc }}</pre>
        </div>
    </div>
    @endforeach
@endif
```

- [ ] **Step 6: Chạy test để xác nhận xanh**

```bash
php vendor/bin/phpunit tests/Unit/Ctdt/CtdtChiTietTest.php
```

Kỳ vọng: `OK (9 tests)`.

- [ ] **Step 7: Chạy toàn bộ test của module**

```bash
php vendor/bin/phpunit tests/Unit/Ctdt
```

Kỳ vọng: xanh. Số test cộng dồn của 2B là 12 + 8 + 10 + 13 + 4 + 6 + 8 + 9 = 70, cộng 145 của các giai đoạn trước → khoảng 215. Con số là chỉ dấu, không phải điều kiện: nếu lệch, đếm lại theo từng tệp trước khi kết luận có gì hỏng.

- [ ] **Step 8: Chạy toàn bộ hai suite, đối chiếu baseline**

```bash
php vendor/bin/phpunit --testsuite Unit
```

Kỳ vọng: `Errors: 4, Failures: 7` — **đúng bằng baseline**.

```bash
php vendor/bin/phpunit --testsuite Feature
```

Kỳ vọng: `Errors: 8, Failures: 4` — **đúng bằng baseline**. Nhiều hơn nghĩa là route hoặc menu mới làm hỏng thứ khác.

- [ ] **Step 9: Commit**

```bash
git add app/Http/Controllers/BHYT/BHYTCtdtController.php resources/views/bhyt/ctdt tests/Unit/Ctdt/CtdtChiTietTest.php
git commit -m "feat(ctdt): man chi tiet voi tab dong, xem XML goc va xoa ho so"
```

---

## Hoàn tất Giai đoạn 2B

Người vận hành làm được trọn vòng: nạp gói XML trên web → xem danh sách với trạng thái phân biệt
rõ bốn lý do chưa gửi → mở chi tiết từng chứng từ → xem XML nguyên văn khi cần đối chiếu.

**Chưa có và cố ý chưa có:** `CtdtChecker` và `CheckCtdtJob` (Giai đoạn 3); ký số, `CtdtSubmitService`,
nút "Ký và gửi", theo dõi hàng đợi (Giai đoạn 4); xuất Excel, lệnh Console `ctdt:import`, dashboard
(Giai đoạn 5). Cột **Số lỗi** trên màn danh sách sẽ luôn là `0` cho tới khi Giai đoạn 3 xong — đó
là đúng, không phải hỏng.

**Việc cần làm khi cập nhật tài liệu sau giai đoạn này:** mục 2 của `docs/chung-tu-dien-tu-pl02.md`
đang ghi "Chưa có: controller, view, ..." — bỏ hai mục đó, và thêm ba màn hình vào bảng "Đã có".

**Việc cần kiểm bằng tay sau khi triển khai** (test không thay thế được):

1. Vào `bhyt/ctdt/import`, nạp một gói `HSCHUNGTU` thật, kiểm bảng kết quả hiện đúng tên tệp và
   số hồ sơ.
2. Nạp lại chính gói đó, kiểm cảnh báo ghi đè **không** xuất hiện (vì hồ sơ chưa gửi lần nào).
3. Nạp một gói `HSDLGCS` mà **không** chọn cơ sở, kiểm `macskcb` lấy đúng giá trị cấu hình đơn vị.
4. Mở chi tiết một hồ sơ có mã dạng `Id-...#1`, xác nhận URL không bị cắt ở dấu `#`.
