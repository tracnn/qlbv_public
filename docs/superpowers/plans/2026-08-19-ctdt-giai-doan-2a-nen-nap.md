# Chứng từ điện tử PL02 — Giai đoạn 2A: Nền nạp — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Nạp được một gói XML chứng từ của cả ba dịch vụ vào 12 bảng đã dựng ở Giai đoạn 1 — kể cả khi nạp lại để ghi đè — với đúng một điểm vào duy nhất và kết quả trả về đủ chi tiết để Giai đoạn 2B hiển thị.

**Architecture:** `CtdtGoiParser` (hàm thuần, chuẩn hóa ba dịch vụ về cùng một dạng) → `CtdtMaHoSo` (suy khóa nghiệp vụ) → `CtdtLuuHoSo` (tầng CSDL, xóa-rồi-tạo-lại trong transaction) → `CtdtImporter` (điểm vào duy nhất, mỗi `HOSO` một transaction riêng). Mọi lỗi nạp mang chung một interface đánh dấu để importer bắt đúng loại mà không nuốt lỗi lạ.

**Tech Stack:** Laravel 5.5, PHP 7.4, PHPUnit 6, MySQL (production) / SQLite in-memory (test), SimpleXML.

## Global Constraints

- **Không dùng `: void` trên `setUp()`** — PHPUnit 6 khai `setUp()` không có kiểu trả về; thêm vào sẽ vỡ.
- **Không dùng cú pháp PHP 8** — không `match`, không nullsafe `?->`, không constructor promotion, không named arguments, không `new (expr)`.
- **Không dùng `RefreshDatabase`** — `.env` dự án trỏ `DB_DATABASE=qlbv` là CSDL phát triển thật. Dùng trait `Tests\Support\DungBangCtdtSqlite` (đã có từ Giai đoạn 1).
- **Không `git add -f`** các tệp trong `.gitignore`: `config/filesystems.php`, `config/database.php`, `config/auth.php`, `config/organization.php`.
- **Không đụng `truong()`, `$fillable`, hay migration cột nào** — ba nơi khai cột hiện khớp 100% và có lưới an toàn `tests/Unit/Ctdt/CtdtToanVenTest.php` canh. Giai đoạn 2A chỉ *đọc* chúng.
- **Giai đoạn 2A KHÔNG dispatch job nào.** `CheckCtdtJob` (Giai đoạn 3) và `SignCtdtJob` (Giai đoạn 4) chưa tồn tại. Bước 10 của đặc tả mục 5.3 để lại một chỗ móc có ghi chú, không gọi gì.
- **Giai đoạn 2A KHÔNG có controller, route, view.** Chúng thuộc Giai đoạn 2B.
- **Baseline test (2026-08-19):** `php vendor/bin/phpunit --testsuite Unit` cho `Errors: 4, Failures: 7`; `--testsuite Feature` cho `Errors: 8, Failures: 4`. Đỏ có sẵn của repo ở `NhapDanhMucUniqueTest`, `OrderCheck\CatalogLookupTest`, `BHYT\Xml3176ExportLocCoSoTest`, `Import\GhiTheoLoTest`, `Dashboard\*ControllerTest`, `ExampleTest`. `tests/Unit/Ctdt` hiện `OK (59 tests)`.
- **Đặc tả nguồn:** `docs/superpowers/specs/2026-08-19-chung-tu-dien-tu-pl02-design.md` (mục 5.2, 5.3 và mục 11).

---

## Hai điều chỉnh so với đặc tả

**(1) Gói giấy chứng sinh không có `MACSKCB`.** Đặc tả mục 5.3 bước 2 viết "thiếu `MACSKCB` → lỗi
nạp", đúng cho `HSCHUNGTU` (`THONGTINDONVI/MACSKCB`) và `HSDLGBT` (`GIAYBAOTU/MACSKCB`), nhưng
`HSDLGCS` **không mang mã cơ sở ở bất kỳ thẻ nào**. Thẻ `MA_TTDV` trông giống nhưng mục 6 phần IV
của PL02 định nghĩa là *"mã số BHXH của Thủ trưởng cơ sở KBCB cấp giấy chứng sinh"* — mã của một
con người, không phải của cơ sở. Điều này nhất quán với việc API gửi nhận `maCskcb` như một tham số
body riêng.

Vì `ctdt_ho_so.macskcb` là `NOT NULL`, chuỗi phân giải thành ba bước:
`XML` → `$tuyChon['macskcb']` (Giai đoạn 2B truyền từ bộ chọn cơ sở trên màn nạp) →
`config('organization.BHYT.ma_cskcb')`. Cạn cả ba mới ném lỗi nạp.

**(2) Nhánh lùi GUID phải kèm chỉ số hồ sơ.** Đặc tả mục 4.3 nói lùi về `Id` của `THONGTINHOSO`.
Nhưng một tệp có nhiều `HOSO` **dùng chung một `Id`** — hai hồ sơ cùng thiếu `MA_YTE` trong một tệp
sẽ nhận cùng khóa và cái thứ hai ghi đè cái thứ nhất, im lặng. Nên khóa lùi là
`<Id>#<chỉ số hồ sơ>`, ví dụ `Id-0ebba721-...#2`.

---

## File Structure

**Tạo mới:**

| Tệp | Trách nhiệm |
|---|---|
| `app/Services/Ctdt/Loi/CtdtLoiNap.php` | Interface đánh dấu mọi lỗi nạp có thể lường trước |
| `app/Services/Ctdt/Loi/LoaiKhongBietException.php` | `LOAIHOSO` không có trong registry |
| `app/Services/Ctdt/Loi/TheGocLechException.php` | `LOAIHOSO` khớp nhưng thẻ gốc bên trong lệch |
| `app/Services/Ctdt/Loi/GoiKhongDocDuocException.php` | XML hỏng, hoặc thẻ gốc không thuộc dịch vụ nào |
| `app/Services/Ctdt/Loi/ThieuMacskcbException.php` | Cạn cả ba nguồn mã cơ sở |
| `app/Services/Ctdt/Loi/KhongXacDinhDuocMaHoSoException.php` | Không suy được khóa nghiệp vụ lẫn khóa lùi |
| `app/Services/Ctdt/CtdtGoiParser.php` | Đọc gói, nhận diện dịch vụ, chuẩn hóa ba dịch vụ về cùng một dạng |
| `app/Services/Ctdt/CtdtMaHoSo.php` | Suy khóa hồ sơ từ danh sách chứng từ của một `HOSO` |
| `app/Services/Ctdt/CtdtImportResult.php` | Kết quả nạp một hồ sơ |
| `app/Services/Ctdt/CtdtImportFileResult.php` | Kết quả nạp một tệp, gộp nhiều hồ sơ |
| `app/Services/Ctdt/CtdtLuuHoSo.php` | Tầng CSDL: xóa bản cũ, ghi chứng từ + chi tiết + hồ sơ |
| `app/Services/Ctdt/CtdtImporter.php` | Điểm vào DUY NHẤT, mỗi `HOSO` một transaction |
| `tests/Support/GoiCtdtMau.php` | Trait dựng chuỗi XML gói mẫu cho cả ba dịch vụ |
| `tests/Unit/Ctdt/*Test.php` | 7 tệp test |

**Sửa:**

| Tệp | Sửa gì |
|---|---|
| `app/Services/Ctdt/CtdtLoaiRegistry.php` | Ném lớp ngoại lệ mới thay cho `\InvalidArgumentException` / `\RuntimeException` |

---

## Task 1: Cây ngoại lệ có gốc chung

**Files:**
- Create: `app/Services/Ctdt/Loi/CtdtLoiNap.php`
- Create: `app/Services/Ctdt/Loi/LoaiKhongBietException.php`
- Create: `app/Services/Ctdt/Loi/TheGocLechException.php`
- Create: `app/Services/Ctdt/Loi/GoiKhongDocDuocException.php`
- Create: `app/Services/Ctdt/Loi/ThieuMacskcbException.php`
- Create: `app/Services/Ctdt/Loi/KhongXacDinhDuocMaHoSoException.php`
- Modify: `app/Services/Ctdt/CtdtLoaiRegistry.php`
- Test: `tests/Unit/Ctdt/CtdtLoiNapTest.php`

**Interfaces:**
- Consumes: `App\Services\Ctdt\CtdtLoaiRegistry` (Giai đoạn 1)
- Produces: interface `App\Services\Ctdt\Loi\CtdtLoiNap` (không có phương thức nào — chỉ đánh dấu), và năm lớp ngoại lệ cài nó. `LoaiKhongBietException extends \InvalidArgumentException`; bốn lớp còn lại `extends \RuntimeException`.

**Vì sao task này đứng trước:** đặc tả mục 11 ghi `cho()` ném `InvalidArgumentException` còn
`xacNhanTheGoc()` ném `RuntimeException`. Importer bắt một loại để ghi lỗi rồi đi tiếp sẽ để loại
kia thoát ra và **kéo đổ cả gói XML** — trái nguyên tắc "một `HOSO` hỏng không kéo `HOSO` khác".
Interface đánh dấu gom chúng lại mà **không đổi lớp cha**, nên hai test của Giai đoạn 1 vẫn xanh.

- [ ] **Step 1: Viết test đỏ**

Tạo `tests/Unit/Ctdt/CtdtLoiNapTest.php`:

```php
<?php

namespace Tests\Unit\Ctdt;

use Tests\TestCase;
use App\Services\Ctdt\CtdtLoaiRegistry;
use App\Services\Ctdt\Loi\CtdtLoiNap;
use App\Services\Ctdt\Loi\LoaiKhongBietException;
use App\Services\Ctdt\Loi\TheGocLechException;
use App\Services\Ctdt\Loi\GoiKhongDocDuocException;
use App\Services\Ctdt\Loi\ThieuMacskcbException;
use App\Services\Ctdt\Loi\KhongXacDinhDuocMaHoSoException;

/**
 * Moi loi NAP luong truoc duoc mang chung mot dau hieu, de importer bat dung mot loai
 * ma khong nuot mat loi la.
 */
class CtdtLoiNapTest extends TestCase
{
    public function cacLop()
    {
        return [
            LoaiKhongBietException::class,
            TheGocLechException::class,
            GoiKhongDocDuocException::class,
            ThieuMacskcbException::class,
            KhongXacDinhDuocMaHoSoException::class,
        ];
    }

    /** @test */
    public function moi_loi_nap_deu_mang_dau_hieu_chung()
    {
        foreach ($this->cacLop() as $lop) {
            $this->assertContains(CtdtLoiNap::class, class_implements($lop),
                $lop . ' phai cai CtdtLoiNap');
            $this->assertInstanceOf(\Exception::class, new $lop('thu'));
        }
    }

    /** @test */
    public function loai_khong_biet_van_la_InvalidArgumentException()
    {
        // Giai doan 1 da co test khang dinh cho() nem InvalidArgumentException. Doi lop cha
        // se lam do test cu ma khong duoc gi - dau hieu chung la thu duy nhat can them.
        $this->assertInstanceOf(\InvalidArgumentException::class, new LoaiKhongBietException('thu'));
    }

    /** @test */
    public function the_goc_lech_van_la_RuntimeException()
    {
        $this->assertInstanceOf(\RuntimeException::class, new TheGocLechException('thu'));
    }

    /** @test */
    public function registry_nem_lop_moi_khi_loai_la()
    {
        $this->expectException(LoaiKhongBietException::class);

        CtdtLoaiRegistry::cho('CT99');
    }

    /** @test */
    public function registry_nem_lop_moi_khi_the_goc_lech()
    {
        $this->expectException(TheGocLechException::class);

        CtdtLoaiRegistry::xacNhanTheGoc('GIAYDIEUTRINOITRU', 'GIAYDIEUTRINOITRU');
    }

    /** @test */
    public function bat_duoc_ca_hai_bang_mot_menh_de_catch()
    {
        // Day chinh la ly do task nay ton tai: mot catch duy nhat trong importer.
        $daBat = 0;

        foreach ([['CT99', null], ['GIAYSUCKHOEME', 'GIAYSUCKHOEME']] as list($loai, $theGoc)) {
            try {
                if ($theGoc === null) {
                    CtdtLoaiRegistry::cho($loai);
                } else {
                    CtdtLoaiRegistry::xacNhanTheGoc($loai, $theGoc);
                }
            } catch (CtdtLoiNap $e) {
                $daBat++;
            }
        }

        $this->assertSame(2, $daBat, 'Mot catch CtdtLoiNap phai bat duoc ca hai loai loi');
    }
}
```

- [ ] **Step 2: Chạy test để xác nhận đỏ**

```bash
php vendor/bin/phpunit tests/Unit/Ctdt/CtdtLoiNapTest.php
```

Kỳ vọng: đỏ — `Class 'App\Services\Ctdt\Loi\CtdtLoiNap' not found`.

- [ ] **Step 3: Viết interface đánh dấu**

Tạo `app/Services/Ctdt/Loi/CtdtLoiNap.php`:

```php
<?php

namespace App\Services\Ctdt\Loi;

/**
 * Dau hieu chung cho moi loi NAP luong truoc duoc: file hong, loai la, the goc lech,
 * thieu ma co so, khong suy duoc khoa ho so.
 *
 * VI SAO LA INTERFACE DANH DAU chu khong phai mot lop cha: doi lop cha se lam do cac test
 * cua Giai doan 1 vang khang dinh cho() nem InvalidArgumentException. Interface them dau
 * hieu ma khong dong toi cay ke thua san co.
 *
 * VI SAO CAN: CtdtImporter bat MOT menh de `catch (CtdtLoiNap $e)` de ghi nhan ho so hong
 * roi di tiep sang ho so ke. Neu moi loi mot lop cha khac nhau, catch mot loai se de loai
 * kia thoat ra va KEO DO CA GOI - dung dieu ma "mot HOSO hong khong keo HOSO khac" cam.
 *
 * Loi KHONG luong truoc (loi lap trinh, mat ket noi CSDL) co y KHONG mang dau hieu nay:
 * chung phai noi len de nguoi van hanh thay, khong duoc ghi thanh "ho so nay hong".
 */
interface CtdtLoiNap
{
}
```

- [ ] **Step 4: Viết năm lớp ngoại lệ**

Tạo `app/Services/Ctdt/Loi/LoaiKhongBietException.php`:

```php
<?php

namespace App\Services\Ctdt\Loi;

/** Gia tri LOAIHOSO khong nam trong dang ky cua CtdtLoaiRegistry. */
class LoaiKhongBietException extends \InvalidArgumentException implements CtdtLoiNap
{
}
```

Tạo `app/Services/Ctdt/Loi/TheGocLechException.php`:

```php
<?php

namespace App\Services\Ctdt\Loi;

/** LOAIHOSO co trong dang ky nhung the goc ben trong base64 khong khop khai bao. */
class TheGocLechException extends \RuntimeException implements CtdtLoiNap
{
}
```

Tạo `app/Services/Ctdt/Loi/GoiKhongDocDuocException.php`:

```php
<?php

namespace App\Services\Ctdt\Loi;

/** Chuoi XML khong parse duoc, hoac the goc khong thuoc dich vu nao cua PL02. */
class GoiKhongDocDuocException extends \RuntimeException implements CtdtLoiNap
{
}
```

Tạo `app/Services/Ctdt/Loi/ThieuMacskcbException.php`:

```php
<?php

namespace App\Services\Ctdt\Loi;

/** Can ca ba nguon ma co so: XML, tuy chon truyen vao, cau hinh don vi. */
class ThieuMacskcbException extends \RuntimeException implements CtdtLoiNap
{
}
```

Tạo `app/Services/Ctdt/Loi/KhongXacDinhDuocMaHoSoException.php`:

```php
<?php

namespace App\Services\Ctdt\Loi;

/** Khong suy duoc khoa nghiep vu lan khoa lui tu Id cua goi. */
class KhongXacDinhDuocMaHoSoException extends \RuntimeException implements CtdtLoiNap
{
}
```

- [ ] **Step 5: Đổi ngoại lệ trong registry**

Trong `app/Services/Ctdt/CtdtLoaiRegistry.php`, thêm hai `use` và đổi hai chỗ `throw`:

```php
use App\Services\Ctdt\Loi\LoaiKhongBietException;
use App\Services\Ctdt\Loi\TheGocLechException;
```

Trong `cho()`:

```php
        if (!self::co($loaiHoSo)) {
            throw new LoaiKhongBietException('Loai ho so khong nam trong dang ky: ' . $loaiHoSo);
        }
```

Trong `xacNhanTheGoc()`:

```php
        if ($mongDoi !== $theGocThucTe) {
            throw new TheGocLechException(
                'LOAIHOSO ' . $loaiHoSo . ' mong doi the goc <' . $mongDoi
                . '> nhung noi dung la <' . $theGocThucTe . '>'
            );
        }
```

Giữ nguyên phần còn lại của registry.

- [ ] **Step 6: Chạy test để xác nhận xanh, và test Giai đoạn 1 không vỡ**

```bash
php vendor/bin/phpunit tests/Unit/Ctdt
```

Kỳ vọng: `OK (65 tests)` — 59 cũ + 6 mới. Đặc biệt `CtdtLoaiRegistryTest` phải **vẫn xanh**: đó là bằng chứng việc đổi ngoại lệ không phá hợp đồng cũ.

- [ ] **Step 7: Commit**

```bash
git add app/Services/Ctdt tests/Unit/Ctdt/CtdtLoiNapTest.php
git commit -m "feat(ctdt): cay ngoai le co goc chung cho luong nap"
```

---

## Task 2: `CtdtGoiParser` — đọc gói và nhận diện

**Files:**
- Create: `app/Services/Ctdt/CtdtGoiParser.php`
- Test: `tests/Unit/Ctdt/CtdtGoiParserNhanDienTest.php`

**Interfaces:**
- Consumes: `App\Services\Ctdt\Loi\GoiKhongDocDuocException` (Task 1); `config('ctdt.dich_vu')` (Giai đoạn 1)
- Produces: lớp `App\Services\Ctdt\CtdtGoiParser` với các phương thức tĩnh:
  - `doc(string $noiDungXml): \SimpleXMLElement` — ném `GoiKhongDocDuocException` khi XML hỏng
  - `nhanDienDichVu(\SimpleXMLElement $goi): string` — `'CT2025'` | `'GBT'` | `'GCS'`
  - `macskcb(\SimpleXMLElement $goi, string $dichVu)` — trả `string|null`
  - `soLuongHoSo(\SimpleXMLElement $goi): int`
  - `ngayLap(\SimpleXMLElement $goi)` — trả `string|null`
  - `idGoi(\SimpleXMLElement $goi, string $dichVu)` — trả `string|null`

- [ ] **Step 1: Viết test đỏ**

Tạo `tests/Unit/Ctdt/CtdtGoiParserNhanDienTest.php`:

```php
<?php

namespace Tests\Unit\Ctdt;

use Tests\TestCase;
use App\Services\Ctdt\CtdtGoiParser;
use App\Services\Ctdt\Loi\GoiKhongDocDuocException;

/**
 * Nhan dien goi: dich vu nao, ma co so nao, bao nhieu ho so.
 */
class CtdtGoiParserNhanDienTest extends TestCase
{
    /** @test */
    public function xml_hong_thi_nem_chu_khong_tra_false()
    {
        // simplexml_load_string tra ve false va phat warning. De nguyen thi warning do
        // chui vao log dung dinh dang mot su co that, con false thi tro thanh loi
        // "Call to a member function on boolean" o tan dau do.
        $this->expectException(GoiKhongDocDuocException::class);

        CtdtGoiParser::doc('<HSCHUNGTU><chua dong the');
    }

    /** @test */
    public function chuoi_rong_cung_nem()
    {
        $this->expectException(GoiKhongDocDuocException::class);

        CtdtGoiParser::doc('');
    }

    /** @test */
    public function nhan_dien_ba_dich_vu_theo_the_goc()
    {
        $bo = [
            'CT2025' => '<HSCHUNGTU/>',
            'GBT'    => '<HSDLGBT/>',
            'GCS'    => '<HSDLGCS/>',
        ];

        foreach ($bo as $mongDoi => $xml) {
            $this->assertSame($mongDoi, CtdtGoiParser::nhanDienDichVu(CtdtGoiParser::doc($xml)));
        }
    }

    /** @test */
    public function the_goc_la_thi_nem_chu_khong_doan()
    {
        // Doan nghia la mot dinh dang khac cua BHXH se bi nap vao SAI DICH VU va gui toi
        // SAI ENDPOINT ma khong bao gi ca.
        $this->expectException(GoiKhongDocDuocException::class);

        CtdtGoiParser::nhanDienDichVu(CtdtGoiParser::doc('<GIAMDINHHS/>'));
    }

    /** @test */
    public function macskcb_cua_ct2025_lay_o_thongtindonvi()
    {
        $goi = CtdtGoiParser::doc(
            '<HSCHUNGTU><THONGTINDONVI><MACSKCB>01929</MACSKCB></THONGTINDONVI></HSCHUNGTU>'
        );

        $this->assertSame('01929', CtdtGoiParser::macskcb($goi, 'CT2025'));
    }

    /** @test */
    public function macskcb_cua_giay_bao_tu_lay_trong_the_giaybaotu()
    {
        $goi = CtdtGoiParser::doc('<HSDLGBT><GIAYBAOTU><MACSKCB>01013</MACSKCB></GIAYBAOTU></HSDLGBT>');

        $this->assertSame('01013', CtdtGoiParser::macskcb($goi, 'GBT'));
    }

    /** @test */
    public function giay_chung_sinh_khong_co_macskcb_nen_tra_null()
    {
        // PL02 phan IV khong khai MACSKCB o dau ca. MA_TTDV trong giong nhung muc 6 dinh
        // nghia la "ma so BHXH cua Thu truong co so KBCB" - ma cua mot CON NGUOI. Lay nham
        // no lam ma co so thi moi ho so GCS deu mang ma sai.
        $goi = CtdtGoiParser::doc('<HSDLGCS><GIAYCHUNGSINH><MA_TTDV>8901234567890</MA_TTDV></GIAYCHUNGSINH></HSDLGCS>');

        $this->assertNull(CtdtGoiParser::macskcb($goi, 'GCS'));
    }

    /** @test */
    public function macskcb_rong_coi_nhu_khong_co()
    {
        $goi = CtdtGoiParser::doc('<HSCHUNGTU><THONGTINDONVI><MACSKCB>   </MACSKCB></THONGTINDONVI></HSCHUNGTU>');

        $this->assertNull(CtdtGoiParser::macskcb($goi, 'CT2025'));
    }

    /** @test */
    public function so_luong_ho_so_doc_gia_tri_that_khong_dem_node()
    {
        // Ban cu cua XML3176 dung count($node) - dem so phan tu CON nen LUON ra 1, bat ke
        // gia tri that. Loi do da tung lam mot tep khai 5 ho so duoc coi la khai 1.
        $goi = CtdtGoiParser::doc(
            '<HSCHUNGTU><THONGTINHOSO><SOLUONGHOSO>5</SOLUONGHOSO></THONGTINHOSO></HSCHUNGTU>'
        );

        $this->assertSame(5, CtdtGoiParser::soLuongHoSo($goi));
    }

    /** @test */
    public function thieu_so_luong_ho_so_tra_khong()
    {
        $this->assertSame(0, CtdtGoiParser::soLuongHoSo(CtdtGoiParser::doc('<HSCHUNGTU/>')));
    }

    /** @test */
    public function ngay_lap_chi_co_o_ct2025()
    {
        $ct = CtdtGoiParser::doc('<HSCHUNGTU><THONGTINHOSO><NGAYLAP>20251101</NGAYLAP></THONGTINHOSO></HSCHUNGTU>');

        $this->assertSame('20251101', CtdtGoiParser::ngayLap($ct));
        $this->assertNull(CtdtGoiParser::ngayLap(CtdtGoiParser::doc('<HSDLGBT/>')));
    }

    /** @test */
    public function id_goi_lay_dung_cho_tung_dich_vu()
    {
        $ct = CtdtGoiParser::doc('<HSCHUNGTU><THONGTINHOSO Id="Id-abc"/></HSCHUNGTU>');
        $gbt = CtdtGoiParser::doc('<HSDLGBT><GIAYBAOTU Id="Id-def"/></HSDLGBT>');
        $gcs = CtdtGoiParser::doc('<HSDLGCS><GIAYCHUNGSINH Id="Id-ghi"/></HSDLGCS>');

        $this->assertSame('Id-abc', CtdtGoiParser::idGoi($ct, 'CT2025'));
        $this->assertSame('Id-def', CtdtGoiParser::idGoi($gbt, 'GBT'));
        $this->assertSame('Id-ghi', CtdtGoiParser::idGoi($gcs, 'GCS'));
    }

    /** @test */
    public function thieu_id_goi_tra_null_khong_nem()
    {
        // Thieu Id khong phai loi: chi lam mat duong lui cua khoa ho so, va cho do da co
        // ngoai le rieng.
        $this->assertNull(CtdtGoiParser::idGoi(CtdtGoiParser::doc('<HSCHUNGTU><THONGTINHOSO/></HSCHUNGTU>'), 'CT2025'));
    }
}
```

- [ ] **Step 2: Chạy test để xác nhận đỏ**

```bash
php vendor/bin/phpunit tests/Unit/Ctdt/CtdtGoiParserNhanDienTest.php
```

Kỳ vọng: đỏ — `Class 'App\Services\Ctdt\CtdtGoiParser' not found`.

- [ ] **Step 3: Viết `CtdtGoiParser` phần nhận diện**

Tạo `app/Services/Ctdt/CtdtGoiParser.php`:

```php
<?php

namespace App\Services\Ctdt;

use App\Services\Ctdt\Loi\GoiKhongDocDuocException;

/**
 * Doc mot goi XML chung tu dien tu va chuan hoa BA DICH VU ve cung mot dang.
 *
 * Ba dich vu co cau truc khac han nhau: HSCHUNGTU long ba tang (DANHSACHHOSO > HOSO >
 * FILEHOSO, noi dung base64), con HSDLGBT/HSDLGCS phang mot tang va noi dung nam thang
 * trong the. Neu de su khac biet do lot xuong CtdtImporter thi importer se co ba nhanh
 * if lon - va moi lan BHXH them mot dich vu la them mot nhanh. Chuan hoa o day, importer
 * chi thay MOT dang duy nhat.
 *
 * Ham THUAN tren chuoi/SimpleXML: khong doc CSDL, khong ghi file, test khong can gi ngoai
 * Laravel de doc config('ctdt.dich_vu').
 */
class CtdtGoiParser
{
    /**
     * @throws GoiKhongDocDuocException khi chuoi khong parse duoc
     */
    public static function doc($noiDungXml)
    {
        // simplexml_load_string phat warning voi chuoi hong. De nguyen thi warning do chui
        // vao storage/logs dung dinh dang mot su co that, va nguoi doc log mat cong dieu
        // tra mot loi khong ton tai. Tat di va tu bao loi.
        $truocDo = libxml_use_internal_errors(true);
        $goi = @simplexml_load_string((string) $noiDungXml);
        libxml_clear_errors();
        libxml_use_internal_errors($truocDo);

        if ($goi === false) {
            throw new GoiKhongDocDuocException('Khong doc duoc noi dung XML cua goi');
        }

        return $goi;
    }

    /**
     * @return string 'CT2025' | 'GBT' | 'GCS'
     * @throws GoiKhongDocDuocException khi the goc khong thuoc dich vu nao
     */
    public static function nhanDienDichVu(\SimpleXMLElement $goi)
    {
        $theGoc = $goi->getName();

        foreach ((array) config('ctdt.dich_vu') as $ma => $cauHinh) {
            if (isset($cauHinh['the_goc']) && $cauHinh['the_goc'] === $theGoc) {
                return $ma;
            }
        }

        // Doan nghia la mot dinh dang khac cua BHXH bi nap vao SAI DICH VU va gui toi SAI
        // endpoint, ma khong co dau hieu gi.
        throw new GoiKhongDocDuocException(
            'The goc <' . $theGoc . '> khong thuoc dich vu nao khai trong config ctdt.dich_vu'
        );
    }

    /**
     * Ma co so KCB khai TRONG GOI.
     *
     * GCS khong mang ma co so o bat ky the nao (MA_TTDV la ma so BHXH cua Thu truong co so,
     * theo muc 6 phan IV cua PL02 - ma cua mot con nguoi). Tra null de CtdtImporter lui ve
     * nguon khac, thay vi bia mot gia tri.
     *
     * @return string|null
     */
    public static function macskcb(\SimpleXMLElement $goi, $dichVu)
    {
        if ($dichVu === 'CT2025') {
            return self::chuoi($goi->THONGTINDONVI, 'MACSKCB');
        }

        if ($dichVu === 'GBT') {
            return self::chuoi($goi->GIAYBAOTU, 'MACSKCB');
        }

        return null;
    }

    /**
     * So luong ho so KHAI BAO trong goi.
     *
     * Phai doc bang (int)(string). count() tren mot node SimpleXML dem so phan tu CON nen
     * LUON tra 1 bat ke gia tri that - loi da tung co trong XML3176.
     */
    public static function soLuongHoSo(\SimpleXMLElement $goi)
    {
        if (!isset($goi->THONGTINHOSO->SOLUONGHOSO)) {
            return 0;
        }

        return (int) (string) $goi->THONGTINHOSO->SOLUONGHOSO;
    }

    /** @return string|null Chi HSCHUNGTU co NGAYLAP */
    public static function ngayLap(\SimpleXMLElement $goi)
    {
        return self::chuoi($goi->THONGTINHOSO, 'NGAYLAP');
    }

    /**
     * Thuoc tinh Id cua the mang chu ky - duong lui cuoi cung cho khoa ho so.
     *
     * @return string|null
     */
    public static function idGoi(\SimpleXMLElement $goi, $dichVu)
    {
        $the = null;

        if ($dichVu === 'CT2025' && isset($goi->THONGTINHOSO)) {
            $the = $goi->THONGTINHOSO;
        } elseif ($dichVu === 'GBT' && isset($goi->GIAYBAOTU)) {
            $the = $goi->GIAYBAOTU;
        } elseif ($dichVu === 'GCS' && isset($goi->GIAYCHUNGSINH)) {
            $the = $goi->GIAYCHUNGSINH;
        }

        if ($the === null || !isset($the['Id'])) {
            return null;
        }

        $id = trim((string) $the['Id']);

        return $id === '' ? null : $id;
    }

    /**
     * Doc mot the con ve chuoi hoac null. Chuoi rong va "khong khai" phai cho cung ket qua
     * o day: ca hai deu nghia la khong co gia tri de dung.
     *
     * @return string|null
     */
    private static function chuoi($cha, $ten)
    {
        if ($cha === null || !isset($cha->{$ten})) {
            return null;
        }

        $giaTri = trim((string) $cha->{$ten});

        return $giaTri === '' ? null : $giaTri;
    }
}
```

- [ ] **Step 4: Chạy test để xác nhận xanh**

```bash
php vendor/bin/phpunit tests/Unit/Ctdt/CtdtGoiParserNhanDienTest.php
```

Kỳ vọng: `OK (13 tests)`.

- [ ] **Step 5: Commit**

```bash
git add app/Services/Ctdt/CtdtGoiParser.php tests/Unit/Ctdt/CtdtGoiParserNhanDienTest.php
git commit -m "feat(ctdt): CtdtGoiParser - doc goi va nhan dien dich vu"
```

---

## Task 3: `CtdtGoiParser::danhSachHoSo()` và trait gói mẫu

**Files:**
- Modify: `app/Services/Ctdt/CtdtGoiParser.php` (thêm `danhSachHoSo()` và hai hàm riêng)
- Create: `tests/Support/GoiCtdtMau.php`
- Test: `tests/Unit/Ctdt/CtdtGoiParserDanhSachTest.php`

**Interfaces:**
- Consumes: `CtdtGoiParser::doc()`, `nhanDienDichVu()` (Task 2); `App\Services\Ctdt\Loi\GoiKhongDocDuocException` (Task 1)
- Produces:
  - `CtdtGoiParser::danhSachHoSo(\SimpleXMLElement $goi, string $dichVu): array` — mảng các `HOSO`; mỗi `HOSO` là mảng các `['loai_ho_so' => string, 'noi_dung' => \SimpleXMLElement]`, giữ **đúng thứ tự xuất hiện** trong tệp.
  - Trait `Tests\Support\GoiCtdtMau` với các phương thức:
    - `goiCt2025(array $hoSo, array $tuyChon = []): string`
    - `goiGbt(array $truong, array $tuyChon = []): string`
    - `goiGcs(array $truong, array $tuyChon = []): string`
    - `chungTu(string $loaiHoSo, array $truong): array`

- [ ] **Step 1: Viết trait dựng gói mẫu**

Tạo `tests/Support/GoiCtdtMau.php`:

```php
<?php

namespace Tests\Support;

use App\Services\Ctdt\CtdtLoaiRegistry;

/**
 * Dung chuoi XML goi chung tu dien tu cho test.
 *
 * VI SAO DUNG BANG MA chu khong de tep fixture: moi test can mot bien the khac nhau
 * (thieu the, sai the goc, hai ho so, ho so khong co MA_YTE). Voi tep fixture thi moi
 * bien the la mot tep, va sua dac ta se phai sua muoi may tep. Ham dung cho phep test
 * noi ro NO KHAC gi so voi goi hop le - dieu ma mot tep fixture khong noi duoc.
 */
trait GoiCtdtMau
{
    /**
     * Mot chung tu de dua vao goiCt2025().
     *
     * @param string $loaiHoSo gia tri LOAIHOSO, vd 'CT03'
     * @param array  $truong   [TEN_THE => gia tri]
     */
    protected function chungTu($loaiHoSo, array $truong)
    {
        return ['loai_ho_so' => $loaiHoSo, 'truong' => $truong];
    }

    /**
     * Goi HSCHUNGTU.
     *
     * @param array $hoSo      Mang cac ho so; moi ho so la mang ket qua cua chungTu()
     * @param array $tuyChon   macskcb, ngay_lap, so_luong_ho_so, id, the_goc_ghi_de
     */
    protected function goiCt2025(array $hoSo, array $tuyChon = [])
    {
        $macskcb = array_key_exists('macskcb', $tuyChon) ? $tuyChon['macskcb'] : '01929';
        $ngayLap = array_key_exists('ngay_lap', $tuyChon) ? $tuyChon['ngay_lap'] : '20251101';
        $id      = array_key_exists('id', $tuyChon) ? $tuyChon['id'] : 'Id-goi-mau';
        $soLuong = array_key_exists('so_luong_ho_so', $tuyChon)
            ? $tuyChon['so_luong_ho_so']
            : count($hoSo);

        $khoiHoSo = '';

        foreach ($hoSo as $mot) {
            $khoiFile = '';

            foreach ($mot as $ct) {
                $theGoc = array_key_exists('the_goc_ghi_de', $tuyChon)
                    ? $tuyChon['the_goc_ghi_de']
                    : $this->theGocCua($ct['loai_ho_so']);

                $noiDung = $this->theChungTu($theGoc, $ct['truong']);

                $khoiFile .= '<FILEHOSO>'
                    . '<LOAIHOSO>' . $ct['loai_ho_so'] . '</LOAIHOSO>'
                    . '<NOIDUNGFILE>' . base64_encode($noiDung) . '</NOIDUNGFILE>'
                    . '</FILEHOSO>';
            }

            $khoiHoSo .= '<HOSO>' . $khoiFile . '</HOSO>';
        }

        $khoiDonVi = $macskcb === null
            ? '<THONGTINDONVI/>'
            : '<THONGTINDONVI><MACSKCB>' . $macskcb . '</MACSKCB></THONGTINDONVI>';

        return '<?xml version="1.0" encoding="utf-8"?>'
            . '<HSCHUNGTU>'
            . $khoiDonVi
            . '<THONGTINHOSO Id="' . $id . '">'
            . '<NGAYLAP>' . $ngayLap . '</NGAYLAP>'
            . '<SOLUONGHOSO>' . $soLuong . '</SOLUONGHOSO>'
            . '<DANHSACHHOSO>' . $khoiHoSo . '</DANHSACHHOSO>'
            . '</THONGTINHOSO>'
            . '</HSCHUNGTU>';
    }

    /** Goi HSDLGBT - mot giay bao tu, noi dung nam thang trong the, khong base64. */
    protected function goiGbt(array $truong, array $tuyChon = [])
    {
        $id = array_key_exists('id', $tuyChon) ? $tuyChon['id'] : 'Id-gbt-mau';

        return '<?xml version="1.0" encoding="utf-8"?>'
            . '<HSDLGBT>'
            . $this->theChungTu('GIAYBAOTU', $truong, ' Id="' . $id . '"')
            . '</HSDLGBT>';
    }

    /** Goi HSDLGCS - mot giay chung sinh. */
    protected function goiGcs(array $truong, array $tuyChon = [])
    {
        $id = array_key_exists('id', $tuyChon) ? $tuyChon['id'] : 'Id-gcs-mau';

        return '<?xml version="1.0" encoding="utf-8"?>'
            . '<HSDLGCS>'
            . $this->theChungTu('GIAYCHUNGSINH', $truong, ' Id="' . $id . '"')
            . '</HSDLGCS>';
    }

    /** Ten the goc that su cua mot LOAIHOSO - lay tu chinh registry de test khong lech. */
    protected function theGocCua($loaiHoSo)
    {
        $lop = CtdtLoaiRegistry::cho($loaiHoSo);

        return $lop::theGoc();
    }

    private function theChungTu($theGoc, array $truong, $thuocTinh = '')
    {
        $ben = '';

        foreach ($truong as $ten => $giaTri) {
            $ben .= '<' . $ten . '>' . htmlspecialchars((string) $giaTri, ENT_XML1) . '</' . $ten . '>';
        }

        return '<' . $theGoc . $thuocTinh . '>' . $ben . '</' . $theGoc . '>';
    }
}
```

- [ ] **Step 2: Viết test đỏ**

Tạo `tests/Unit/Ctdt/CtdtGoiParserDanhSachTest.php`:

```php
<?php

namespace Tests\Unit\Ctdt;

use Tests\TestCase;
use Tests\Support\GoiCtdtMau;
use App\Services\Ctdt\CtdtGoiParser;
use App\Services\Ctdt\Loi\GoiKhongDocDuocException;

/**
 * Chuan hoa ba dich vu ve CUNG MOT dang: mang cac HOSO, moi HOSO la mang chung tu.
 */
class CtdtGoiParserDanhSachTest extends TestCase
{
    use GoiCtdtMau;

    /** @test */
    public function ct2025_mot_ho_so_nhieu_chung_tu()
    {
        $xml = $this->goiCt2025([[
            $this->chungTu('CT03', ['MA_YTE' => 'YT001', 'HO_TEN' => 'Nguyen Van Test']),
            $this->chungTu('CT04', ['MA_CT' => 'CT-1']),
        ]]);

        $goi = CtdtGoiParser::doc($xml);
        $ds = CtdtGoiParser::danhSachHoSo($goi, 'CT2025');

        $this->assertCount(1, $ds, 'Mot HOSO');
        $this->assertCount(2, $ds[0], 'Hai chung tu trong ho so do');

        $this->assertSame('CT03', $ds[0][0]['loai_ho_so']);
        $this->assertSame('CT03', $ds[0][0]['noi_dung']->getName());
        $this->assertSame('YT001', (string) $ds[0][0]['noi_dung']->MA_YTE);

        $this->assertSame('CT04', $ds[0][1]['loai_ho_so']);
    }

    /** @test */
    public function ct2025_NHIEU_ho_so_khong_bi_bo_sot()
    {
        // Ban cu cua XML3176 duyet ->HOSO->FILEHOSO. Trong SimpleXML, ->HOSO tren mot tap
        // nhieu phan tu TU LAY PHAN TU DAU, nen ho so thu hai tro di bi bo HOAN TOAN -
        // khong loi, khong log, va nguoi dung van nhan "thanh cong".
        $xml = $this->goiCt2025([
            [$this->chungTu('CT03', ['MA_YTE' => 'YT001'])],
            [$this->chungTu('CT03', ['MA_YTE' => 'YT002'])],
            [$this->chungTu('CT03', ['MA_YTE' => 'YT003'])],
        ]);

        $ds = CtdtGoiParser::danhSachHoSo(CtdtGoiParser::doc($xml), 'CT2025');

        $this->assertCount(3, $ds);
        $this->assertSame('YT002', (string) $ds[1][0]['noi_dung']->MA_YTE);
        $this->assertSame('YT003', (string) $ds[2][0]['noi_dung']->MA_YTE);
    }

    /** @test */
    public function giu_dung_thu_tu_xuat_hien_trong_tep()
    {
        // Thu tu quyet dinh chung tu nao duoc lay lam khoa ho so (chung tu dau tien co
        // MA_YTE thang). Sap xep lai o day se doi khoa cua ho so mot cach im lang.
        $xml = $this->goiCt2025([[
            $this->chungTu('CT04', ['MA_CT' => 'CT-1']),
            $this->chungTu('CT03', ['MA_YTE' => 'YT001']),
            $this->chungTu('CT06', ['MA_BHXH' => 'BH-1']),
        ]]);

        $ds = CtdtGoiParser::danhSachHoSo(CtdtGoiParser::doc($xml), 'CT2025');

        $this->assertSame(['CT04', 'CT03', 'CT06'], array_column($ds[0], 'loai_ho_so'));
    }

    /** @test */
    public function giay_bao_tu_chuan_hoa_thanh_mot_ho_so_mot_chung_tu()
    {
        $xml = $this->goiGbt(['MA_GBT' => '00002.GBT.XXXX.25', 'HO_TEN' => 'Nguyen Van Test']);

        $ds = CtdtGoiParser::danhSachHoSo(CtdtGoiParser::doc($xml), 'GBT');

        $this->assertCount(1, $ds);
        $this->assertCount(1, $ds[0]);
        $this->assertSame('GIAYBAOTU', $ds[0][0]['loai_ho_so']);
        $this->assertSame('GIAYBAOTU', $ds[0][0]['noi_dung']->getName());
        $this->assertSame('00002.GBT.XXXX.25', (string) $ds[0][0]['noi_dung']->MA_GBT);
    }

    /** @test */
    public function giay_chung_sinh_chuan_hoa_thanh_mot_ho_so_mot_chung_tu()
    {
        $xml = $this->goiGcs(['MA_GCS' => '00005.GCS.XXXXX.25', 'HOTEN_NND' => 'Pham Minh Test']);

        $ds = CtdtGoiParser::danhSachHoSo(CtdtGoiParser::doc($xml), 'GCS');

        $this->assertCount(1, $ds);
        $this->assertSame('GIAYCHUNGSINH', $ds[0][0]['loai_ho_so']);
        $this->assertSame('00005.GCS.XXXXX.25', (string) $ds[0][0]['noi_dung']->MA_GCS);
    }

    /** @test */
    public function ct2025_khong_co_hoso_nao_thi_tra_mang_rong_khong_nem()
    {
        // Goi rong la chuyen cua tang tren quyet dinh (CtdtImporter ghi "khong co ho so"),
        // khong phai loi cu phap de parser tu nem.
        $goi = CtdtGoiParser::doc('<HSCHUNGTU><THONGTINHOSO><DANHSACHHOSO/></THONGTINHOSO></HSCHUNGTU>');

        $this->assertSame([], CtdtGoiParser::danhSachHoSo($goi, 'CT2025'));
    }

    /** @test */
    public function ct2025_ho_so_khong_co_filehoso_thi_la_ho_so_rong()
    {
        $goi = CtdtGoiParser::doc(
            '<HSCHUNGTU><THONGTINHOSO><DANHSACHHOSO><HOSO/></DANHSACHHOSO></THONGTINHOSO></HSCHUNGTU>'
        );

        $ds = CtdtGoiParser::danhSachHoSo($goi, 'CT2025');

        $this->assertCount(1, $ds);
        $this->assertSame([], $ds[0]);
    }

    /** @test */
    public function noi_dung_base64_hong_thi_nem()
    {
        $goi = CtdtGoiParser::doc(
            '<HSCHUNGTU><THONGTINHOSO><DANHSACHHOSO><HOSO><FILEHOSO>'
            . '<LOAIHOSO>CT03</LOAIHOSO><NOIDUNGFILE>' . base64_encode('<CT03><chua dong') . '</NOIDUNGFILE>'
            . '</FILEHOSO></HOSO></DANHSACHHOSO></THONGTINHOSO></HSCHUNGTU>'
        );

        $this->expectException(GoiKhongDocDuocException::class);

        CtdtGoiParser::danhSachHoSo($goi, 'CT2025');
    }

    /** @test */
    public function bao_tu_thieu_the_noi_dung_thi_tra_ho_so_rong()
    {
        $this->assertSame([[]], CtdtGoiParser::danhSachHoSo(CtdtGoiParser::doc('<HSDLGBT/>'), 'GBT'));
    }
}
```

- [ ] **Step 3: Chạy test để xác nhận đỏ**

```bash
php vendor/bin/phpunit tests/Unit/Ctdt/CtdtGoiParserDanhSachTest.php
```

Kỳ vọng: đỏ — `Call to undefined method ...::danhSachHoSo()`.

- [ ] **Step 4: Thêm `danhSachHoSo()` vào `CtdtGoiParser`**

Thêm vào `app/Services/Ctdt/CtdtGoiParser.php`, sau `idGoi()`:

```php
    /**
     * Chuan hoa ca ba dich vu ve CUNG MOT dang.
     *
     * @return array Mang cac HOSO; moi HOSO la mang cac
     *               ['loai_ho_so' => string, 'noi_dung' => \SimpleXMLElement],
     *               GIU DUNG thu tu xuat hien trong tep.
     * @throws GoiKhongDocDuocException khi noi dung base64 cua mot FILEHOSO khong parse duoc
     */
    public static function danhSachHoSo(\SimpleXMLElement $goi, $dichVu)
    {
        if ($dichVu === 'CT2025') {
            return self::hoSoCuaCt2025($goi);
        }

        return self::hoSoCuaGoiPhang($goi, $dichVu === 'GBT' ? 'GIAYBAOTU' : 'GIAYCHUNGSINH');
    }

    /**
     * HSCHUNGTU: DANHSACHHOSO > nhieu HOSO > nhieu FILEHOSO, noi dung base64.
     */
    private static function hoSoCuaCt2025(\SimpleXMLElement $goi)
    {
        if (!isset($goi->THONGTINHOSO->DANHSACHHOSO->HOSO)) {
            return [];
        }

        $ketQua = [];

        // PHAI foreach tren tap. Truy cap ->HOSO->FILEHOSO tren mot tap nhieu phan tu se
        // TU LAY PHAN TU DAU va bo im lang cac ho so con lai - loi da tung co that.
        foreach ($goi->THONGTINHOSO->DANHSACHHOSO->HOSO as $hoSo) {
            $chungTu = [];

            if (isset($hoSo->FILEHOSO)) {
                foreach ($hoSo->FILEHOSO as $file) {
                    $loai = trim((string) $file->LOAIHOSO);
                    $noiDung = self::doc(base64_decode((string) $file->NOIDUNGFILE));

                    $chungTu[] = ['loai_ho_so' => $loai, 'noi_dung' => $noiDung];
                }
            }

            $ketQua[] = $chungTu;
        }

        return $ketQua;
    }

    /**
     * HSDLGBT / HSDLGCS: mot the con duy nhat, noi dung nam thang trong the, KHONG base64.
     *
     * Van tra ve dang long hai tang de importer chi biet MOT dang.
     */
    private static function hoSoCuaGoiPhang(\SimpleXMLElement $goi, $tenThe)
    {
        if (!isset($goi->{$tenThe})) {
            return [[]];
        }

        return [[['loai_ho_so' => $tenThe, 'noi_dung' => $goi->{$tenThe}]]];
    }
```

- [ ] **Step 5: Chạy test để xác nhận xanh**

```bash
php vendor/bin/phpunit tests/Unit/Ctdt/CtdtGoiParserDanhSachTest.php tests/Unit/Ctdt/CtdtGoiParserNhanDienTest.php
```

Kỳ vọng: `OK (22 tests)`.

- [ ] **Step 6: Commit**

```bash
git add app/Services/Ctdt/CtdtGoiParser.php tests/Support/GoiCtdtMau.php tests/Unit/Ctdt/CtdtGoiParserDanhSachTest.php
git commit -m "feat(ctdt): chuan hoa ba dich vu ve mot dang danh sach ho so"
```

---

## Task 4: `CtdtMaHoSo` — suy khóa hồ sơ

**Files:**
- Create: `app/Services/Ctdt/CtdtMaHoSo.php`
- Test: `tests/Unit/Ctdt/CtdtMaHoSoTest.php`

**Interfaces:**
- Consumes: `CtdtLoaiRegistry::cho()` và `$lop::maChungTu()` (Giai đoạn 1); `App\Services\Ctdt\Loi\KhongXacDinhDuocMaHoSoException` (Task 1)
- Produces: `App\Services\Ctdt\CtdtMaHoSo::cua(array $chungTu, $idGoi, $chiSoHoSo): string` — `$chungTu` là mảng `['loai_ho_so' => , 'noi_dung' => ]` của **một** `HOSO` (dạng do `danhSachHoSo()` trả về), `$chiSoHoSo` đếm từ 1.

- [ ] **Step 1: Viết test đỏ**

Tạo `tests/Unit/Ctdt/CtdtMaHoSoTest.php`:

```php
<?php

namespace Tests\Unit\Ctdt;

use Tests\TestCase;
use Tests\Support\GoiCtdtMau;
use App\Services\Ctdt\CtdtGoiParser;
use App\Services\Ctdt\CtdtMaHoSo;
use App\Services\Ctdt\Loi\KhongXacDinhDuocMaHoSoException;

/**
 * Khoa ho so quyet dinh viec GHI DE khi nap lai. Sai khoa co hai kieu, va ca hai deu im
 * lang: khoa qua hep thi nap lai de ra ban ghi thu hai; khoa qua rong thi hai ho so khac
 * nhau bi coi la mot va mat du lieu.
 */
class CtdtMaHoSoTest extends TestCase
{
    use GoiCtdtMau;

    private function hoSoDau($xml, $dichVu)
    {
        $ds = CtdtGoiParser::danhSachHoSo(CtdtGoiParser::doc($xml), $dichVu);

        return $ds[0];
    }

    /** @test */
    public function lay_ma_yte_cua_chung_tu_dau_tien_co_the_do()
    {
        $xml = $this->goiCt2025([[
            $this->chungTu('CT04', ['MA_CT' => 'CT-1']),
            $this->chungTu('CT03', ['MA_YTE' => 'YT001']),
            $this->chungTu('GIAYDIEUTRINOITRU', ['MA_YTE' => 'YT999']),
        ]]);

        $this->assertSame('YT001', CtdtMaHoSo::cua($this->hoSoDau($xml, 'CT2025'), 'Id-goi-mau', 1));
    }

    /** @test */
    public function giay_bao_tu_lay_ma_gbt()
    {
        $xml = $this->goiGbt(['MA_GBT' => '00002.GBT.XXXX.25']);

        $this->assertSame('00002.GBT.XXXX.25', CtdtMaHoSo::cua($this->hoSoDau($xml, 'GBT'), 'Id-gbt-mau', 1));
    }

    /** @test */
    public function giay_chung_sinh_lay_ma_gcs()
    {
        $xml = $this->goiGcs(['MA_GCS' => '00005.GCS.XXXXX.25']);

        $this->assertSame('00005.GCS.XXXXX.25', CtdtMaHoSo::cua($this->hoSoDau($xml, 'GCS'), 'Id-gcs-mau', 1));
    }

    /** @test */
    public function ho_so_chi_gom_CT04_CT06_CT07_thi_lui_ve_id_goi_kem_chi_so()
    {
        // Ba loai nay KHONG co MA_YTE - han che da biet, ghi trong dac ta muc 4.3.
        $xml = $this->goiCt2025([[
            $this->chungTu('CT04', ['MA_CT' => 'CT-1']),
            $this->chungTu('CT07', ['MA_CT' => 'CT-2']),
        ]], ['id' => 'Id-abc']);

        $this->assertSame('Id-abc#1', CtdtMaHoSo::cua($this->hoSoDau($xml, 'CT2025'), 'Id-abc', 1));
    }

    /** @test */
    public function hai_ho_so_cung_thieu_ma_yte_trong_MOT_tep_khong_duoc_trung_khoa()
    {
        // Ca hai HOSO dung chung mot Id cua THONGTINHOSO. Neu khoa lui chi la Id thi ho so
        // thu hai GHI DE ho so thu nhat ngay trong cung mot lan nap - mat du lieu im lang.
        $xml = $this->goiCt2025([
            [$this->chungTu('CT04', ['MA_CT' => 'CT-1'])],
            [$this->chungTu('CT04', ['MA_CT' => 'CT-2'])],
        ], ['id' => 'Id-abc']);

        $ds = CtdtGoiParser::danhSachHoSo(CtdtGoiParser::doc($xml), 'CT2025');

        $khoa1 = CtdtMaHoSo::cua($ds[0], 'Id-abc', 1);
        $khoa2 = CtdtMaHoSo::cua($ds[1], 'Id-abc', 2);

        $this->assertNotSame($khoa1, $khoa2, 'Hai ho so trong cung mot tep phai co khoa khac nhau');
        $this->assertSame('Id-abc#1', $khoa1);
        $this->assertSame('Id-abc#2', $khoa2);
    }

    /** @test */
    public function ma_yte_thang_hon_id_goi()
    {
        $xml = $this->goiCt2025([[$this->chungTu('CT03', ['MA_YTE' => 'YT001'])]], ['id' => 'Id-abc']);

        $this->assertSame('YT001', CtdtMaHoSo::cua($this->hoSoDau($xml, 'CT2025'), 'Id-abc', 1));
    }

    /** @test */
    public function khong_co_ma_yte_lan_id_goi_thi_nem()
    {
        // Bia mot khoa tu MA_THE + NGAY_VAO se lam hai ho so khac nhau bi coi la mot.
        // Nem de nguoi van hanh biet ngay tai buoc nap.
        $xml = $this->goiCt2025([[$this->chungTu('CT04', ['MA_CT' => 'CT-1'])]]);

        $this->expectException(KhongXacDinhDuocMaHoSoException::class);

        CtdtMaHoSo::cua($this->hoSoDau($xml, 'CT2025'), null, 1);
    }

    /** @test */
    public function ho_so_rong_thi_nem()
    {
        $this->expectException(KhongXacDinhDuocMaHoSoException::class);

        CtdtMaHoSo::cua([], null, 1);
    }

    /** @test */
    public function ho_so_rong_nhung_co_id_goi_thi_van_lui_duoc()
    {
        $this->assertSame('Id-abc#3', CtdtMaHoSo::cua([], 'Id-abc', 3));
    }

    /** @test */
    public function ma_yte_rong_khong_duoc_coi_la_co()
    {
        $xml = $this->goiCt2025([[$this->chungTu('CT03', ['MA_YTE' => ''])]], ['id' => 'Id-abc']);

        $this->assertSame('Id-abc#1', CtdtMaHoSo::cua($this->hoSoDau($xml, 'CT2025'), 'Id-abc', 1));
    }

    /** @test */
    public function loai_la_khong_lam_do_ca_ho_so_khi_con_loai_khac_cho_duoc_khoa()
    {
        // Loai la se bi CtdtImporter bat rieng. O buoc suy khoa thi bo qua no va di tiep,
        // vi tu choi ca ho so chi vi mot loai la se lam mat mot ho so hop le.
        $chungTu = [
            ['loai_ho_so' => 'CT99', 'noi_dung' => simplexml_load_string('<CT99/>')],
            ['loai_ho_so' => 'CT03', 'noi_dung' => simplexml_load_string('<CT03><MA_YTE>YT001</MA_YTE></CT03>')],
        ];

        $this->assertSame('YT001', CtdtMaHoSo::cua($chungTu, 'Id-abc', 1));
    }
}
```

- [ ] **Step 2: Chạy test để xác nhận đỏ**

```bash
php vendor/bin/phpunit tests/Unit/Ctdt/CtdtMaHoSoTest.php
```

Kỳ vọng: đỏ — `Class 'App\Services\Ctdt\CtdtMaHoSo' not found`.

- [ ] **Step 3: Viết `CtdtMaHoSo`**

Tạo `app/Services/Ctdt/CtdtMaHoSo.php`:

```php
<?php

namespace App\Services\Ctdt;

use App\Services\Ctdt\Loi\KhongXacDinhDuocMaHoSoException;

/**
 * Suy khoa nghiep vu cua MOT ho so.
 *
 * Khoa nay quyet dinh viec GHI DE khi nap lai, nen sai o day hong theo hai kieu va ca hai
 * deu im lang:
 *   - khoa qua HEP  -> nap lai de ra ban ghi thu hai, khong ai ghi de ai
 *   - khoa qua RONG -> hai ho so khac nhau bi coi la mot, ban sau xoa ban truoc
 *
 * Thu tu uu tien:
 *   1. Khoa nghiep vu cua chung tu DAU TIEN co (MA_YTE / MA_GBT / MA_GCS)
 *   2. Id cua goi kem CHI SO ho so
 *
 * VI SAO PHAI KEM CHI SO o buoc 2: mot tep HSCHUNGTU co nhieu HOSO dung CHUNG mot Id cua
 * THONGTINHOSO. Neu khoa lui chi la Id thi hai ho so cung thieu MA_YTE trong cung mot tep
 * se nhan cung khoa, va cai thu hai ghi de cai thu nhat NGAY TRONG mot lan nap.
 *
 * VI SAO KHONG bia khoa tu MA_THE + NGAY_VAO khi ca hai buoc deu can: rui ro nguoc lai va
 * nang hon. Nem de nguoi van hanh biet ngay tai buoc nap.
 */
class CtdtMaHoSo
{
    /**
     * @param array       $chungTu    Mang ['loai_ho_so' =>, 'noi_dung' =>] cua MOT ho so
     * @param string|null $idGoi      Thuoc tinh Id cua the mang chu ky
     * @param int         $chiSoHoSo  Vi tri ho so trong tep, dem tu 1
     * @return string
     * @throws KhongXacDinhDuocMaHoSoException
     */
    public static function cua(array $chungTu, $idGoi, $chiSoHoSo)
    {
        foreach ($chungTu as $ct) {
            if (!isset($ct['loai_ho_so']) || !CtdtLoaiRegistry::co($ct['loai_ho_so'])) {
                // Loai la duoc CtdtImporter bat rieng. Tu choi ca ho so tai day chi vi mot
                // loai la se lam mat mot ho so von hop le.
                continue;
            }

            $lop = CtdtLoaiRegistry::cho($ct['loai_ho_so']);
            $ma  = $lop::maChungTu($ct['noi_dung']);

            if ($ma !== null && $ma !== '') {
                return $ma;
            }
        }

        if ($idGoi !== null && $idGoi !== '') {
            return $idGoi . '#' . $chiSoHoSo;
        }

        throw new KhongXacDinhDuocMaHoSoException(
            'Ho so #' . $chiSoHoSo . ': khong co MA_YTE/MA_GBT/MA_GCS va goi cung khong co Id'
        );
    }
}
```

- [ ] **Step 4: Chạy test để xác nhận xanh**

```bash
php vendor/bin/phpunit tests/Unit/Ctdt/CtdtMaHoSoTest.php
```

Kỳ vọng: `OK (11 tests)`.

- [ ] **Step 5: Commit**

```bash
git add app/Services/Ctdt/CtdtMaHoSo.php tests/Unit/Ctdt/CtdtMaHoSoTest.php
git commit -m "feat(ctdt): suy khoa ho so, khoa lui kem chi so chong trung"
```

---

## Task 5: Hai lớp kết quả nạp

**Files:**
- Create: `app/Services/Ctdt/CtdtImportResult.php`
- Create: `app/Services/Ctdt/CtdtImportFileResult.php`
- Test: `tests/Unit/Ctdt/CtdtImportResultTest.php`

**Interfaces:**
- Consumes: không có
- Produces:
  - `CtdtImportResult` — thuộc tính công khai `$thanhCong` (bool), `$maHoSo` (string|null), `$loaiDaXuLy` (array), `$lyDoThatBai` (string|null); hai hàm dựng tĩnh `thanhCong($maHoSo, array $loaiDaXuLy)` và `thatBai($lyDo)`.
  - `CtdtImportFileResult` — thuộc tính `$thanhCong`, `$lyDoThatBai`, `$ketQua` (array `CtdtImportResult`), `$soThanhCong`, `$soThatBai`, `$dsMaHoSo` (array); hai hàm dựng tĩnh `thatBaiSom($lyDo)` và `tu(array $ketQua, $soKhaiBao, $soThucTe)`.

Giữ **đúng hai tên** `thanhCong` và `lyDoThatBai` ở cả hai lớp, như `Xml3176ImportResult` /
`Xml3176ImportFileResult` — controller ở Giai đoạn 2B đọc chung một cách bất kể nhận lớp nào.

- [ ] **Step 1: Viết test đỏ**

Tạo `tests/Unit/Ctdt/CtdtImportResultTest.php`:

```php
<?php

namespace Tests\Unit\Ctdt;

use Tests\TestCase;
use App\Services\Ctdt\CtdtImportResult;
use App\Services\Ctdt\CtdtImportFileResult;

class CtdtImportResultTest extends TestCase
{
    /** @test */
    public function ket_qua_mot_ho_so_thanh_cong()
    {
        $kq = CtdtImportResult::thanhCong('YT001', ['CT03', 'CT04']);

        $this->assertTrue($kq->thanhCong);
        $this->assertSame('YT001', $kq->maHoSo);
        $this->assertSame(['CT03', 'CT04'], $kq->loaiDaXuLy);
        $this->assertNull($kq->lyDoThatBai);
    }

    /** @test */
    public function ket_qua_mot_ho_so_that_bai_giu_ly_do()
    {
        // Tra doi tuong thay vi bool: giao dien can ly do CU THE de hien, chu khong phai
        // mot cau chung chung nhu "cau truc khong hop le".
        $kq = CtdtImportResult::thatBai('Thieu MA_YTE');

        $this->assertFalse($kq->thanhCong);
        $this->assertSame('Thieu MA_YTE', $kq->lyDoThatBai);
        $this->assertNull($kq->maHoSo);
    }

    /** @test */
    public function gop_ket_qua_tat_ca_thanh_cong()
    {
        $kq = CtdtImportFileResult::tu([
            CtdtImportResult::thanhCong('YT001', ['CT03']),
            CtdtImportResult::thanhCong('YT002', ['CT03']),
        ], 2, 2);

        $this->assertTrue($kq->thanhCong);
        $this->assertNull($kq->lyDoThatBai);
        $this->assertSame(2, $kq->soThanhCong);
        $this->assertSame(0, $kq->soThatBai);
        $this->assertSame(['YT001', 'YT002'], $kq->dsMaHoSo);
    }

    /** @test */
    public function gop_ket_qua_co_ho_so_hong_thi_neu_ro_ho_so_thu_may()
    {
        $kq = CtdtImportFileResult::tu([
            CtdtImportResult::thanhCong('YT001', ['CT03']),
            CtdtImportResult::thatBai('The goc lech'),
        ], 2, 2);

        $this->assertFalse($kq->thanhCong);
        $this->assertSame(1, $kq->soThanhCong);
        $this->assertSame(1, $kq->soThatBai);
        $this->assertContains('Ho so #2', $kq->lyDoThatBai);
        $this->assertContains('The goc lech', $kq->lyDoThatBai);
        $this->assertSame(['YT001'], $kq->dsMaHoSo, 'Ho so hong khong duoc vao danh sach thanh cong');
    }

    /** @test */
    public function thuc_te_it_hon_khai_bao_thi_tu_choi_ca_tep()
    {
        // Tep co the bi cat cut. Nhap mot phan roi bao thanh cong la kieu hong nguy hiem
        // nhat: khong ai biet phan con thieu ton tai.
        $kq = CtdtImportFileResult::tu([CtdtImportResult::thanhCong('YT001', ['CT03'])], 3, 1);

        $this->assertFalse($kq->thanhCong);
        $this->assertContains('SOLUONGHOSO', $kq->lyDoThatBai);
        $this->assertContains('3', $kq->lyDoThatBai);
        $this->assertContains('1', $kq->lyDoThatBai);
    }

    /** @test */
    public function thuc_te_NHIEU_hon_khai_bao_thi_khong_chan()
    {
        // Bat doi xung CO CHU DICH: metadata sai nhung du lieu du. Chan o day la chan nham
        // mot tep von day du.
        $kq = CtdtImportFileResult::tu([
            CtdtImportResult::thanhCong('YT001', ['CT03']),
            CtdtImportResult::thanhCong('YT002', ['CT03']),
        ], 1, 2);

        $this->assertTrue($kq->thanhCong);
    }

    /** @test */
    public function hong_ngay_tu_dau_tep()
    {
        $kq = CtdtImportFileResult::thatBaiSom('Khong doc duoc noi dung XML cua goi');

        $this->assertFalse($kq->thanhCong);
        $this->assertSame('Khong doc duoc noi dung XML cua goi', $kq->lyDoThatBai);
        $this->assertSame([], $kq->ketQua);
        $this->assertSame(0, $kq->soThanhCong);
    }

    /** @test */
    public function hai_lop_dung_chung_ten_thuoc_tinh()
    {
        // Controller doc $kq->thanhCong va $kq->lyDoThatBai ma khong quan tam nhan duoc lop
        // nao. Doi ten mot ben se lam giao dien im lang bao sai.
        foreach ([CtdtImportResult::thatBai('x'), CtdtImportFileResult::thatBaiSom('x')] as $kq) {
            $this->assertObjectHasAttribute('thanhCong', $kq);
            $this->assertObjectHasAttribute('lyDoThatBai', $kq);
        }
    }
}
```

- [ ] **Step 2: Chạy test để xác nhận đỏ**

```bash
php vendor/bin/phpunit tests/Unit/Ctdt/CtdtImportResultTest.php
```

Kỳ vọng: đỏ — `Class 'App\Services\Ctdt\CtdtImportResult' not found`.

- [ ] **Step 3: Viết `CtdtImportResult`**

Tạo `app/Services/Ctdt/CtdtImportResult.php`:

```php
<?php

namespace App\Services\Ctdt;

/**
 * Ket qua nhap MOT ho so.
 *
 * Tra doi tuong thay vi bool: giao dien can ly do CU THE de hien len, con lenh console
 * can biet co duoc chuyen tep nguon sang thu muc "da nhap" hay khong.
 */
class CtdtImportResult
{
    /** @var bool */
    public $thanhCong;

    /** @var string|null */
    public $maHoSo;

    /** @var array Cac gia tri LOAIHOSO da xu ly duoc */
    public $loaiDaXuLy = [];

    /** @var string|null */
    public $lyDoThatBai;

    public static function thanhCong($maHoSo, array $loaiDaXuLy)
    {
        $kq = new self();
        $kq->thanhCong  = true;
        $kq->maHoSo     = $maHoSo;
        $kq->loaiDaXuLy = $loaiDaXuLy;

        return $kq;
    }

    public static function thatBai($lyDo)
    {
        $kq = new self();
        $kq->thanhCong   = false;
        $kq->lyDoThatBai = $lyDo;

        return $kq;
    }
}
```

- [ ] **Step 4: Viết `CtdtImportFileResult`**

Tạo `app/Services/Ctdt/CtdtImportFileResult.php`:

```php
<?php

namespace App\Services\Ctdt;

/**
 * Ket qua nhap MOT TEP - mot goi HSCHUNGTU co the chua NHIEU ho so.
 *
 * Giu dung hai ten thuoc tinh 'thanhCong' va 'lyDoThatBai' nhu CtdtImportResult, nen noi
 * goi khong phai phan biet minh dang nhan lop nao.
 */
class CtdtImportFileResult
{
    /** @var bool Moi ho so deu thanh cong VA so luong khong thieu so voi khai bao */
    public $thanhCong;

    /** @var string|null Ly do gop */
    public $lyDoThatBai;

    /** @var array<CtdtImportResult> Ket qua tung ho so */
    public $ketQua = [];

    /** @var int */
    public $soThanhCong = 0;

    /** @var int */
    public $soThatBai = 0;

    /** @var array Cac ma_ho_so nhap thanh cong */
    public $dsMaHoSo = [];

    /** Hong ngay tu dau tep, chua xu ly ho so nao. */
    public static function thatBaiSom($lyDo)
    {
        $kq = new self();
        $kq->thanhCong   = false;
        $kq->lyDoThatBai = $lyDo;

        return $kq;
    }

    /**
     * @param array $ketQua    CtdtImportResult cho tung ho so
     * @param int   $soKhaiBao Gia tri SOLUONGHOSO trong goi
     * @param int   $soThucTe  So the HOSO dem duoc
     */
    public static function tu(array $ketQua, $soKhaiBao, $soThucTe)
    {
        $kq = new self();
        $kq->ketQua = $ketQua;

        $lyDo = [];

        foreach ($ketQua as $i => $r) {
            if ($r->thanhCong) {
                $kq->soThanhCong++;
                $kq->dsMaHoSo[] = $r->maHoSo;
            } else {
                $kq->soThatBai++;
                $lyDo[] = 'Ho so #' . ($i + 1) . ': ' . $r->lyDoThatBai;
            }
        }

        // Bat doi xung CO CHU DICH:
        //  - thuc te IT hon khai bao -> tep co the bi cat cut, tu choi ca tep. Nhap mot
        //    phan roi bao thanh cong la kieu hong nguy hiem nhat.
        //  - thuc te NHIEU hon khai bao -> metadata sai nhung du lieu du, chan o day la
        //    chan nham.
        if ((int) $soThucTe < (int) $soKhaiBao) {
            array_unshift(
                $lyDo,
                'SOLUONGHOSO khai bao ' . $soKhaiBao . ' nhung tep chi co ' . $soThucTe . ' ho so'
            );
        }

        $kq->thanhCong   = empty($lyDo);
        $kq->lyDoThatBai = empty($lyDo) ? null : implode('; ', $lyDo);

        return $kq;
    }
}
```

- [ ] **Step 5: Chạy test để xác nhận xanh**

```bash
php vendor/bin/phpunit tests/Unit/Ctdt/CtdtImportResultTest.php
```

Kỳ vọng: `OK (8 tests)`.

- [ ] **Step 6: Commit**

```bash
git add app/Services/Ctdt/CtdtImportResult.php app/Services/Ctdt/CtdtImportFileResult.php tests/Unit/Ctdt/CtdtImportResultTest.php
git commit -m "feat(ctdt): hai lop ket qua nap"
```

---

## Task 6: `CtdtLuuHoSo` — tầng ghi CSDL

**Files:**
- Create: `app/Services/Ctdt/CtdtLuuHoSo.php`
- Test: `tests/Unit/Ctdt/CtdtLuuHoSoTest.php`

**Interfaces:**
- Consumes: `CtdtLoaiRegistry::cho()`, `xacNhanTheGoc()`, `$lop::truong()`, `$lop::rutGon()`, `$lop::model()`, `$lop::maChungTu()` (Giai đoạn 1); model `CtdtHoSo`, `CtdtChungTu`, `CtdtLoi` (Giai đoạn 1); `TheGocLechException`, `LoaiKhongBietException` (Task 1)
- Produces: `App\Services\Ctdt\CtdtLuuHoSo` với hai phương thức thực thể:
  - `xoaHoSoCu($maHoSo): void` — xóa `ctdt_chung_tu` (kéo theo chi tiết) và `ctdt_loi` của hồ sơ, **giữ** bản ghi `ctdt_ho_so`
  - `luu(array $hoSo): \App\Models\BHYT\Ctdt\CtdtHoSo` — nhận mảng mô tả một hồ sơ, trả bản ghi đã ghi

Hình dạng `$hoSo` mà `luu()` nhận:

```php
[
    'ma_ho_so'       => 'YT001',
    'id_goi_xml'     => 'Id-abc',      // hoac null
    'dich_vu'        => 'CT2025',
    'loai_hs'        => '39',
    'macskcb'        => '01929',
    'ngay_lap'       => '20251101',    // hoac null
    'so_luong_ho_so' => 1,             // hoac null
    'imported_by'    => 'nguoidung',   // hoac null
    'duong_dan_goc'  => null,
    'chung_tu'       => [ ['loai_ho_so' => 'CT03', 'noi_dung' => <SimpleXMLElement>], ... ],
]
```

- [ ] **Step 1: Viết test đỏ**

Tạo `tests/Unit/Ctdt/CtdtLuuHoSoTest.php`:

```php
<?php

namespace Tests\Unit\Ctdt;

use Tests\TestCase;
use Tests\Support\DungBangCtdtSqlite;
use Tests\Support\GoiCtdtMau;
use App\Services\Ctdt\CtdtGoiParser;
use App\Services\Ctdt\CtdtLuuHoSo;
use App\Services\Ctdt\Loi\TheGocLechException;
use App\Models\BHYT\Ctdt\CtdtHoSo;
use App\Models\BHYT\Ctdt\CtdtChungTu;
use App\Models\BHYT\Ctdt\CtdtLoi;
use App\Models\BHYT\Ctdt\CtdtCt03;
use App\Models\BHYT\Ctdt\CtdtCt04;

class CtdtLuuHoSoTest extends TestCase
{
    use DungBangCtdtSqlite;
    use GoiCtdtMau;

    /** @var CtdtLuuHoSo */
    private $luu;

    protected function setUp()
    {
        parent::setUp();
        $this->chuanBiBangCtdt();
        $this->luu = new CtdtLuuHoSo();
    }

    private function moTaHoSo(array $chungTu, array $ghiDe = [])
    {
        return array_merge([
            'ma_ho_so'       => 'YT001',
            'id_goi_xml'     => 'Id-abc',
            'dich_vu'        => 'CT2025',
            'loai_hs'        => '39',
            'macskcb'        => '01929',
            'ngay_lap'       => '20251101',
            'so_luong_ho_so' => 1,
            'imported_by'    => 'nguoinap',
            'duong_dan_goc'  => null,
            'chung_tu'       => $chungTu,
        ], $ghiDe);
    }

    private function chungTuTu($loaiHoSo, array $truong)
    {
        $xml = $this->goiCt2025([[$this->chungTu($loaiHoSo, $truong)]]);
        $ds = CtdtGoiParser::danhSachHoSo(CtdtGoiParser::doc($xml), 'CT2025');

        return $ds[0];
    }

    /** @test */
    public function ghi_ho_so_chung_tu_va_chi_tiet()
    {
        $ct = $this->chungTuTu('CT03', [
            'MA_YTE'    => 'YT001',
            'MA_THE'    => 'DN123',
            'HO_TEN'    => 'Nguyen Van Test',
            'NGAY_SINH' => '19950914',
            'NGAY_VAO'  => '201912121200',
            'NGAY_RA'   => '201912180001',
            'CHAN_DOAN' => 'Dau bung',
        ]);

        $hoSo = $this->luu->luu($this->moTaHoSo($ct));

        $this->assertSame('YT001', $hoSo->ma_ho_so);
        $this->assertSame('CT2025', $hoSo->dich_vu);
        $this->assertSame('01929', $hoSo->macskcb);
        $this->assertSame(1, (int) $hoSo->so_chung_tu);
        $this->assertNotNull($hoSo->imported_at);
        $this->assertSame('nguoinap', $hoSo->imported_by);

        $this->assertSame(1, CtdtChungTu::count());
        $ctdt = CtdtChungTu::first();
        $this->assertSame('CT03', $ctdt->loai_ho_so);
        $this->assertSame('YT001', $ctdt->ma_chung_tu);

        $this->assertSame(1, CtdtCt03::count());
        $this->assertSame('Dau bung', CtdtCt03::first()->chan_doan);
    }

    /** @test */
    public function cot_rut_gon_duoc_sao_len_ctdt_chung_tu()
    {
        // Khong co cot rut gon thi man danh sach phai UNION 9 bang chi tiet, vi chin loai
        // dat ten truong khac nhau.
        $ct = $this->chungTuTu('CT03', [
            'MA_YTE'    => 'YT001',
            'MA_THE'    => 'DN123',
            'HO_TEN'    => 'Nguyen Van Test',
            'NGAY_SINH' => '19950914',
            'NGAY_VAO'  => '201912121200',
            'NGAY_RA'   => '201912180001',
        ]);

        $this->luu->luu($this->moTaHoSo($ct));

        $ctdt = CtdtChungTu::first();
        $this->assertSame('DN123', $ctdt->ma_the);
        $this->assertSame('Nguyen Van Test', $ctdt->ho_ten);
        $this->assertSame('19950914', $ctdt->ngay_sinh);
        $this->assertSame('201912121200', $ctdt->ngay_vao);
        $this->assertSame('201912180001', $ctdt->ngay_ra);
    }

    /** @test */
    public function giu_nguyen_xml_goc_de_doi_chieu()
    {
        // Khi cong bao 205 (fileBase64Str khong hop le), thu duy nhat giup doi chieu la
        // noi dung nguyen van da gui.
        $ct = $this->chungTuTu('CT03', ['MA_YTE' => 'YT001']);

        $this->luu->luu($this->moTaHoSo($ct));

        $this->assertContains('<MA_YTE>YT001</MA_YTE>', CtdtChungTu::first()->noi_dung_goc);
    }

    /** @test */
    public function nhieu_loai_chung_tu_trong_mot_ho_so()
    {
        $xml = $this->goiCt2025([[
            $this->chungTu('CT03', ['MA_YTE' => 'YT001']),
            $this->chungTu('CT04', ['MA_CT' => 'CT-1']),
        ]]);
        $ds = CtdtGoiParser::danhSachHoSo(CtdtGoiParser::doc($xml), 'CT2025');

        $hoSo = $this->luu->luu($this->moTaHoSo($ds[0]));

        $this->assertSame(2, (int) $hoSo->so_chung_tu);
        $this->assertSame(1, CtdtCt03::count());
        $this->assertSame(1, CtdtCt04::count());
    }

    /** @test */
    public function nap_lai_ghi_de_sach_chung_tu_cu()
    {
        $this->luu->luu($this->moTaHoSo($this->chungTuTu('CT03', [
            'MA_YTE' => 'YT001', 'CHAN_DOAN' => 'Chan doan CU',
        ])));

        $this->luu->luu($this->moTaHoSo($this->chungTuTu('CT03', [
            'MA_YTE' => 'YT001', 'CHAN_DOAN' => 'Chan doan MOI',
        ])));

        $this->assertSame(1, CtdtHoSo::count(), 'Van chi mot ho so');
        $this->assertSame(1, CtdtChungTu::count(), 'Chung tu cu phai bi xoa');
        $this->assertSame(1, CtdtCt03::count(), 'Chi tiet cu phai bi xoa');
        $this->assertSame('Chan doan MOI', CtdtCt03::first()->chan_doan);
    }

    /** @test */
    public function nap_lai_xoa_ca_loi_cu()
    {
        $hoSo = $this->luu->luu($this->moTaHoSo($this->chungTuTu('CT03', ['MA_YTE' => 'YT001'])));

        CtdtLoi::create([
            'ho_so_id'    => $hoSo->id,
            'chung_tu_id' => CtdtChungTu::first()->id,
            'ma_loi'      => 'CTDT002',
            'mo_ta'       => 'Sai dinh dang ngay',
            'muc_do'      => 'chan',
        ]);

        $this->luu->luu($this->moTaHoSo($this->chungTuTu('CT03', ['MA_YTE' => 'YT001'])));

        // Loi cu tro toi chung tu da bi xoa. Giu lai thi lan kiem truoc se hien len tab chi
        // tiet cua mot chung tu KHAC - MySQL cap lai dung nhung AUTO_INCREMENT vua giai phong.
        $this->assertSame(0, CtdtLoi::count());
    }

    /** @test */
    public function nap_lai_reset_trang_thai_ky_va_gui()
    {
        $hoSo = $this->luu->luu($this->moTaHoSo($this->chungTuTu('CT03', ['MA_YTE' => 'YT001'])));

        $hoSo->update([
            'is_signed'           => true,
            'sign_method'         => 'usb_token',
            'signed_at'           => '2026-08-19 10:00:00',
            'submitted_at'        => '2026-08-19 10:05:00',
            'ma_gd'               => 'HS_123456',
            'ma_ket_qua'          => '200',
            'thoi_gian_tiep_nhan' => '20260819100500',
            'so_loi'              => 3,
            'checked_at'          => '2026-08-19 09:00:00',
        ]);

        $moi = $this->luu->luu($this->moTaHoSo($this->chungTuTu('CT03', ['MA_YTE' => 'YT001'])));

        // Noi dung da doi thi chu ky cu khong con ung voi noi dung moi, va ket qua gui cu
        // noi ve mot ban khac. Giu lai la noi doi voi nguoi doc man danh sach.
        $this->assertFalse((bool) $moi->is_signed);
        $this->assertNull($moi->sign_method);
        $this->assertNull($moi->signed_at);
        $this->assertNull($moi->submitted_at);
        $this->assertNull($moi->ma_gd);
        $this->assertNull($moi->ma_ket_qua);
        $this->assertNull($moi->thoi_gian_tiep_nhan);
        $this->assertNull($moi->checked_at);
        $this->assertSame(0, (int) $moi->so_loi);
    }

    /** @test */
    public function nap_lai_giu_dau_vet_lan_gui_truoc_trong_lich_su()
    {
        $hoSo = $this->luu->luu($this->moTaHoSo($this->chungTuTu('CT03', ['MA_YTE' => 'YT001'])));

        $hoSo->update([
            'ma_gd'               => 'HS_123456',
            'ma_ket_qua'          => '200',
            'thoi_gian_tiep_nhan' => '20260819100500',
        ]);

        $moi = $this->luu->luu($this->moTaHoSo($this->chungTuTu('CT03', ['MA_YTE' => 'YT001'])));

        // Xoa sach dau vet gui la xoa kha nang giai trinh khi BHXH doi soat.
        $this->assertContains('HS_123456', $moi->lich_su_gui);
        $this->assertContains('200', $moi->lich_su_gui);
    }

    /** @test */
    public function chua_tung_gui_thi_khong_ghi_dong_lich_su_rong()
    {
        $this->luu->luu($this->moTaHoSo($this->chungTuTu('CT03', ['MA_YTE' => 'YT001'])));
        $moi = $this->luu->luu($this->moTaHoSo($this->chungTuTu('CT03', ['MA_YTE' => 'YT001'])));

        $this->assertNull($moi->lich_su_gui);
    }

    /** @test */
    public function the_goc_lech_thi_nem_va_khong_ghi_gi()
    {
        $xml = $this->goiCt2025(
            [[$this->chungTu('CT03', ['MA_YTE' => 'YT001'])]],
            ['the_goc_ghi_de' => 'CT04']
        );
        $ds = CtdtGoiParser::danhSachHoSo(CtdtGoiParser::doc($xml), 'CT2025');

        try {
            $this->luu->luu($this->moTaHoSo($ds[0]));
            $this->fail('Phai nem TheGocLechException');
        } catch (TheGocLechException $e) {
            // Mong doi
        }

        // luu() KHONG tu mo transaction - CtdtImporter bao ngoai. Nhung ban ghi chi tiet
        // khong duoc ghi truoc khi phat hien lech, vi kiem the goc dung TRUOC khi ghi.
        $this->assertSame(0, CtdtCt03::count());
    }

    /** @test */
    public function loai_la_thi_nem()
    {
        $chungTu = [[
            'loai_ho_so' => 'CT99',
            'noi_dung'   => simplexml_load_string('<CT99/>'),
        ]];

        $this->expectException(\App\Services\Ctdt\Loi\LoaiKhongBietException::class);

        $this->luu->luu($this->moTaHoSo($chungTu));
    }

    /** @test */
    public function the_khong_khai_trong_truong_thi_bo_qua_khong_nem()
    {
        // BHXH them the moi vao dac ta truoc khi ta kip cap nhat la chuyen se xay ra. Nem
        // o day se lam ca ho so hong chi vi mot the thua ma ta chua biet den.
        $ct = $this->chungTuTu('CT03', ['MA_YTE' => 'YT001', 'THE_MOI_TINH' => 'gi do']);

        $hoSo = $this->luu->luu($this->moTaHoSo($ct));

        $this->assertSame('YT001', $hoSo->ma_ho_so);
        $this->assertSame(1, CtdtCt03::count());
    }

    /** @test */
    public function giay_bao_tu_luu_dung_bang_va_dich_vu()
    {
        $xml = $this->goiGbt(['MA_GBT' => '00002.GBT.XXXX.25', 'HO_TEN' => 'Nguyen Van Test']);
        $ds = CtdtGoiParser::danhSachHoSo(CtdtGoiParser::doc($xml), 'GBT');

        $hoSo = $this->luu->luu($this->moTaHoSo($ds[0], [
            'ma_ho_so' => '00002.GBT.XXXX.25',
            'dich_vu'  => 'GBT',
            'loai_hs'  => '60',
            'ngay_lap' => null,
        ]));

        $this->assertSame('GBT', $hoSo->dich_vu);
        $this->assertSame('60', $hoSo->loai_hs);
        $this->assertSame(1, \App\Models\BHYT\Ctdt\CtdtGiayBaoTu::count());
        $this->assertSame('Nguyen Van Test', CtdtChungTu::first()->ho_ten);
    }
}
```

- [ ] **Step 2: Chạy test để xác nhận đỏ**

```bash
php vendor/bin/phpunit tests/Unit/Ctdt/CtdtLuuHoSoTest.php
```

Kỳ vọng: đỏ — `Class 'App\Services\Ctdt\CtdtLuuHoSo' not found`.

- [ ] **Step 3: Viết `CtdtLuuHoSo`**

Tạo `app/Services/Ctdt/CtdtLuuHoSo.php`:

```php
<?php

namespace App\Services\Ctdt;

use App\Models\BHYT\Ctdt\CtdtHoSo;
use App\Models\BHYT\Ctdt\CtdtChungTu;
use App\Models\BHYT\Ctdt\CtdtLoi;

/**
 * Tang ghi CSDL cua luong nap. TACH KHOI CtdtImporter co chu dich: importer lo viec phan
 * ra ho so va bat loi tung ho so, lop nay lo viec ghi dung mot ho so - hai trach nhiem
 * kiem duoc doc lap.
 *
 * KHONG tu mo transaction: CtdtImporter bao MOT transaction quanh MOI ho so, va long
 * transaction trong nhau tren MySQL khong cho savepoint nhu nguoi ta tuong.
 */
class CtdtLuuHoSo
{
    /**
     * Xoa du lieu cu cua mot ho so, GIU LAI ban ghi ctdt_ho_so.
     *
     * Giu ctdt_ho_so vi no mang lich_su_gui, ma_gd, ma_ket_qua - dau vet doi soat voi BHXH.
     * Xoa ctdt_loi TRUOC ctdt_chung_tu de khong bao gio ton tai mot khoanh khac ma loi tro
     * toi chung tu da bien mat.
     */
    public function xoaHoSoCu($maHoSo)
    {
        $hoSo = CtdtHoSo::where('ma_ho_so', $maHoSo)->first();

        if ($hoSo === null) {
            return;
        }

        CtdtLoi::where('ho_so_id', $hoSo->id)->delete();

        foreach (CtdtChungTu::where('ho_so_id', $hoSo->id)->get() as $chungTu) {
            $this->xoaChiTiet($chungTu);
            $chungTu->delete();
        }
    }

    /**
     * Ghi mot ho so. Ghi de sach ban cu neu da co.
     *
     * @param array $hoSo Xem khoi Interfaces cua Task 6 trong ke hoach
     * @return CtdtHoSo
     */
    public function luu(array $hoSo)
    {
        // Kiem TOAN BO loai va the goc TRUOC khi ghi bat cu gi. Phat hien lech o giua chung
        // se de lai mot ho so nap do dang neu noi goi quen bao transaction.
        foreach ($hoSo['chung_tu'] as $ct) {
            CtdtLoaiRegistry::xacNhanTheGoc($ct['loai_ho_so'], $ct['noi_dung']->getName());
        }

        $this->xoaHoSoCu($hoSo['ma_ho_so']);

        $banGhi = $this->ghiHoSo($hoSo);

        foreach ($hoSo['chung_tu'] as $ct) {
            $this->ghiChungTu($banGhi, $ct);
        }

        return $banGhi->fresh();
    }

    /**
     * Tao moi hoac cap nhat ctdt_ho_so, reset trang thai ky/gui va noi lich su.
     */
    private function ghiHoSo(array $hoSo)
    {
        $cu = CtdtHoSo::where('ma_ho_so', $hoSo['ma_ho_so'])->first();

        $thuocTinh = [
            'id_goi_xml'     => $hoSo['id_goi_xml'],
            'dich_vu'        => $hoSo['dich_vu'],
            'loai_hs'        => $hoSo['loai_hs'],
            'macskcb'        => $hoSo['macskcb'],
            'ngay_lap'       => $hoSo['ngay_lap'],
            'so_luong_ho_so' => $hoSo['so_luong_ho_so'],
            'so_chung_tu'    => count($hoSo['chung_tu']),
            'duong_dan_goc'  => $hoSo['duong_dan_goc'],
            'imported_at'    => now(),
            'imported_by'    => $hoSo['imported_by'],
            'import_error'   => null,

            // Noi dung da doi thi chu ky cu khong con ung voi noi dung moi, va ket qua gui
            // cu noi ve mot ban khac. Giu lai la noi doi voi nguoi doc man danh sach.
            'checked_at'          => null,
            'so_loi'              => 0,
            'is_signed'           => false,
            'sign_method'         => null,
            'signed_at'           => null,
            'signed_error'        => null,
            'submitted_at'        => null,
            'submitted_by'        => null,
            'submit_error'        => null,
            'submitted_message'   => null,
            'ma_gd'               => null,
            'ma_ket_qua'          => null,
            'thoi_gian_tiep_nhan' => null,
        ];

        if ($cu === null) {
            $thuocTinh['ma_ho_so'] = $hoSo['ma_ho_so'];

            return CtdtHoSo::create($thuocTinh);
        }

        $lichSu = $this->noiLichSu($cu);

        if ($lichSu !== null) {
            $thuocTinh['lich_su_gui'] = $lichSu;
        }

        $cu->update($thuocTinh);

        return $cu;
    }

    /**
     * Noi mot dong vao lich_su_gui neu ban cu DA TUNG duoc gui.
     *
     * @return string|null null khi chua tung gui - khong ghi dong rong lam nhieu
     */
    private function noiLichSu(CtdtHoSo $cu)
    {
        if (empty($cu->ma_gd) && empty($cu->ma_ket_qua)) {
            return null;
        }

        $dong = '[' . now()->format('Y-m-d H:i:s') . '] nap lai, ban truoc:'
            . ' MaGD=' . (string) $cu->ma_gd
            . ' MaKetQua=' . (string) $cu->ma_ket_qua
            . ' ThoiGianTiepNhan=' . (string) $cu->thoi_gian_tiep_nhan;

        return empty($cu->lich_su_gui) ? $dong : $cu->lich_su_gui . "\n" . $dong;
    }

    /**
     * Ghi mot chung tu: ban ghi chung + cot rut gon + ban ghi chi tiet theo loai.
     */
    private function ghiChungTu(CtdtHoSo $hoSo, array $ct)
    {
        $lop = CtdtLoaiRegistry::cho($ct['loai_ho_so']);
        $xml = $ct['noi_dung'];

        $rutGon = $lop::rutGon($xml);

        $chungTu = CtdtChungTu::create(array_merge($rutGon, [
            'ho_so_id'     => $hoSo->id,
            'loai_ho_so'   => $ct['loai_ho_so'],
            'ma_chung_tu'  => $lop::maChungTu($xml),
            'noi_dung_goc' => $xml->asXML(),
        ]));

        $chiTiet = ['chung_tu_id' => $chungTu->id];

        foreach ($lop::truong() as $the => $cot) {
            if (!isset($xml->{$the})) {
                continue;
            }

            $chiTiet[$cot] = (string) $xml->{$the};
        }

        // The co trong XML nhung KHONG khai trong truong() bi bo qua co chu dich: BHXH them
        // the moi truoc khi ta kip cap nhat la chuyen se xay ra, va nem o day se lam ca ho
        // so hong chi vi mot the ta chua biet den. Luoi an toan CtdtToanVenTest canh chieu
        // nguoc lai - cot co trong bang ma khong khai trong truong().
        $tenModel = $lop::model();
        $tenModel::create($chiTiet);
    }

    /** Xoa ban ghi chi tiet ung voi mot chung tu, o dung bang cua loai do. */
    private function xoaChiTiet(CtdtChungTu $chungTu)
    {
        if (!CtdtLoaiRegistry::co($chungTu->loai_ho_so)) {
            // Loai la trong CSDL nghia la dang ky da bi thu hep sau khi du lieu duoc ghi.
            // Khong biet xoa o bang nao thi de lai con hon xoa nham bang khac.
            return;
        }

        $lop = CtdtLoaiRegistry::cho($chungTu->loai_ho_so);
        $tenModel = $lop::model();

        $tenModel::where('chung_tu_id', $chungTu->id)->delete();
    }
}
```

- [ ] **Step 4: Chạy test để xác nhận xanh**

```bash
php vendor/bin/phpunit tests/Unit/Ctdt/CtdtLuuHoSoTest.php
```

Kỳ vọng: `OK (13 tests)`.

- [ ] **Step 5: Commit**

```bash
git add app/Services/Ctdt/CtdtLuuHoSo.php tests/Unit/Ctdt/CtdtLuuHoSoTest.php
git commit -m "feat(ctdt): tang ghi CSDL, ghi de sach va giu dau vet gui"
```

---

## Task 7: `CtdtImporter` — điểm vào duy nhất

**Files:**
- Create: `app/Services/Ctdt/CtdtImporter.php`
- Test: `tests/Unit/Ctdt/CtdtImporterTest.php`

**Interfaces:**
- Consumes: `CtdtGoiParser` (Task 2, 3), `CtdtMaHoSo` (Task 4), `CtdtImportResult` / `CtdtImportFileResult` (Task 5), `CtdtLuuHoSo` (Task 6), `CtdtLoiNap` và các lớp ngoại lệ (Task 1)
- Produces: `App\Services\Ctdt\CtdtImporter`
  - constructor `__construct(CtdtLuuHoSo $luu = null)`
  - `nhapTuChuoi($noiDungXml, array $tuyChon = []): CtdtImportFileResult`
  - `$tuyChon` nhận ba khóa, tất cả tùy chọn: `'macskcb'` (mã cơ sở do người nạp chọn), `'imported_by'` (tên đăng nhập), `'duong_dan_goc'` (đường dẫn tệp đã lưu)

- [ ] **Step 1: Viết test đỏ**

Tạo `tests/Unit/Ctdt/CtdtImporterTest.php`:

```php
<?php

namespace Tests\Unit\Ctdt;

use Tests\TestCase;
use Tests\Support\DungBangCtdtSqlite;
use Tests\Support\GoiCtdtMau;
use App\Services\Ctdt\CtdtImporter;
use App\Models\BHYT\Ctdt\CtdtHoSo;
use App\Models\BHYT\Ctdt\CtdtChungTu;
use App\Models\BHYT\Ctdt\CtdtCt03;

class CtdtImporterTest extends TestCase
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
    }

    /** @test */
    public function nhap_mot_ho_so_ct2025()
    {
        $xml = $this->goiCt2025([[
            $this->chungTu('CT03', ['MA_YTE' => 'YT001', 'HO_TEN' => 'Nguyen Van Test']),
        ]]);

        $kq = $this->importer->nhapTuChuoi($xml);

        $this->assertTrue($kq->thanhCong, (string) $kq->lyDoThatBai);
        $this->assertSame(1, $kq->soThanhCong);
        $this->assertSame(['YT001'], $kq->dsMaHoSo);
        $this->assertSame(1, CtdtHoSo::count());
        $this->assertSame('39', CtdtHoSo::first()->loai_hs);
    }

    /** @test */
    public function nhap_giay_bao_tu_va_giay_chung_sinh()
    {
        $kqGbt = $this->importer->nhapTuChuoi($this->goiGbt(['MA_GBT' => 'GBT-1']));
        $kqGcs = $this->importer->nhapTuChuoi(
            $this->goiGcs(['MA_GCS' => 'GCS-1']),
            ['macskcb' => '01929']
        );

        $this->assertTrue($kqGbt->thanhCong, (string) $kqGbt->lyDoThatBai);
        $this->assertTrue($kqGcs->thanhCong, (string) $kqGcs->lyDoThatBai);

        $this->assertSame('60', CtdtHoSo::where('ma_ho_so', 'GBT-1')->first()->loai_hs);
        $this->assertSame('61', CtdtHoSo::where('ma_ho_so', 'GCS-1')->first()->loai_hs);
    }

    /** @test */
    public function giay_chung_sinh_lay_macskcb_tu_tuy_chon()
    {
        // Goi GCS khong mang ma co so o bat ky the nao.
        $kq = $this->importer->nhapTuChuoi(
            $this->goiGcs(['MA_GCS' => 'GCS-1']),
            ['macskcb' => '01929']
        );

        $this->assertTrue($kq->thanhCong, (string) $kq->lyDoThatBai);
        $this->assertSame('01929', CtdtHoSo::first()->macskcb);
    }

    /** @test */
    public function giay_chung_sinh_lui_ve_cau_hinh_don_vi_khi_khong_truyen_tuy_chon()
    {
        config(['organization.BHYT.ma_cskcb' => '01013']);

        $kq = $this->importer->nhapTuChuoi($this->goiGcs(['MA_GCS' => 'GCS-2']));

        $this->assertTrue($kq->thanhCong, (string) $kq->lyDoThatBai);
        $this->assertSame('01013', CtdtHoSo::first()->macskcb);
    }

    /** @test */
    public function can_ca_ba_nguon_ma_co_so_thi_tu_choi_ca_tep()
    {
        config(['organization.BHYT.ma_cskcb' => '']);

        $kq = $this->importer->nhapTuChuoi($this->goiGcs(['MA_GCS' => 'GCS-3']));

        $this->assertFalse($kq->thanhCong);
        $this->assertContains('ma co so', $kq->lyDoThatBai);
        $this->assertSame(0, CtdtHoSo::count());
    }

    /** @test */
    public function xml_hong_thi_that_bai_som_khong_nem_ra_ngoai()
    {
        $kq = $this->importer->nhapTuChuoi('<HSCHUNGTU><chua dong');

        $this->assertFalse($kq->thanhCong);
        $this->assertNotEmpty($kq->lyDoThatBai);
        $this->assertSame([], $kq->ketQua);
    }

    /** @test */
    public function the_goc_la_thi_that_bai_som()
    {
        $kq = $this->importer->nhapTuChuoi('<GIAMDINHHS/>');

        $this->assertFalse($kq->thanhCong);
        $this->assertContains('GIAMDINHHS', $kq->lyDoThatBai);
    }

    /** @test */
    public function thieu_macskcb_o_goi_ct2025_thi_that_bai_som()
    {
        config(['organization.BHYT.ma_cskcb' => '']);

        $xml = $this->goiCt2025(
            [[$this->chungTu('CT03', ['MA_YTE' => 'YT001'])]],
            ['macskcb' => null]
        );

        $kq = $this->importer->nhapTuChuoi($xml);

        $this->assertFalse($kq->thanhCong);
        $this->assertSame(0, CtdtHoSo::count());
    }

    /** @test */
    public function mot_ho_so_hong_KHONG_keo_cac_ho_so_con_lai_xuong()
    {
        // Day la nguyen tac quan trong nhat cua importer. Ho so #2 co the goc lech.
        $xml = '<?xml version="1.0" encoding="utf-8"?><HSCHUNGTU>'
            . '<THONGTINDONVI><MACSKCB>01929</MACSKCB></THONGTINDONVI>'
            . '<THONGTINHOSO Id="Id-abc"><NGAYLAP>20251101</NGAYLAP><SOLUONGHOSO>3</SOLUONGHOSO>'
            . '<DANHSACHHOSO>'
            . '<HOSO><FILEHOSO><LOAIHOSO>CT03</LOAIHOSO><NOIDUNGFILE>'
            . base64_encode('<CT03><MA_YTE>YT001</MA_YTE></CT03>') . '</NOIDUNGFILE></FILEHOSO></HOSO>'
            . '<HOSO><FILEHOSO><LOAIHOSO>CT03</LOAIHOSO><NOIDUNGFILE>'
            . base64_encode('<CT04><MA_YTE>YT002</MA_YTE></CT04>') . '</NOIDUNGFILE></FILEHOSO></HOSO>'
            . '<HOSO><FILEHOSO><LOAIHOSO>CT03</LOAIHOSO><NOIDUNGFILE>'
            . base64_encode('<CT03><MA_YTE>YT003</MA_YTE></CT03>') . '</NOIDUNGFILE></FILEHOSO></HOSO>'
            . '</DANHSACHHOSO></THONGTINHOSO></HSCHUNGTU>';

        $kq = $this->importer->nhapTuChuoi($xml);

        $this->assertFalse($kq->thanhCong, 'Ca tep khong duoc bao thanh cong khi co ho so hong');
        $this->assertSame(2, $kq->soThanhCong);
        $this->assertSame(1, $kq->soThatBai);
        $this->assertSame(['YT001', 'YT003'], $kq->dsMaHoSo);
        $this->assertContains('Ho so #2', $kq->lyDoThatBai);

        $this->assertSame(2, CtdtHoSo::count(), 'Hai ho so lanh phai duoc ghi');
    }

    /** @test */
    public function ho_so_hong_khong_de_lai_du_lieu_do_dang()
    {
        // Ho so co hai chung tu, cai thu hai the goc lech. Transaction phai quay lui SACH.
        $xml = '<?xml version="1.0" encoding="utf-8"?><HSCHUNGTU>'
            . '<THONGTINDONVI><MACSKCB>01929</MACSKCB></THONGTINDONVI>'
            . '<THONGTINHOSO Id="Id-abc"><SOLUONGHOSO>1</SOLUONGHOSO><DANHSACHHOSO><HOSO>'
            . '<FILEHOSO><LOAIHOSO>CT03</LOAIHOSO><NOIDUNGFILE>'
            . base64_encode('<CT03><MA_YTE>YT001</MA_YTE></CT03>') . '</NOIDUNGFILE></FILEHOSO>'
            . '<FILEHOSO><LOAIHOSO>CT04</LOAIHOSO><NOIDUNGFILE>'
            . base64_encode('<CT06><MA_BHXH>x</MA_BHXH></CT06>') . '</NOIDUNGFILE></FILEHOSO>'
            . '</HOSO></DANHSACHHOSO></THONGTINHOSO></HSCHUNGTU>';

        $kq = $this->importer->nhapTuChuoi($xml);

        $this->assertFalse($kq->thanhCong);
        $this->assertSame(0, CtdtHoSo::count());
        $this->assertSame(0, CtdtChungTu::count());
        $this->assertSame(0, CtdtCt03::count());
    }

    /** @test */
    public function nap_lai_ghi_de_qua_importer()
    {
        $this->importer->nhapTuChuoi($this->goiCt2025([[
            $this->chungTu('CT03', ['MA_YTE' => 'YT001', 'CHAN_DOAN' => 'CU']),
        ]]));

        $this->importer->nhapTuChuoi($this->goiCt2025([[
            $this->chungTu('CT03', ['MA_YTE' => 'YT001', 'CHAN_DOAN' => 'MOI']),
        ]]));

        $this->assertSame(1, CtdtHoSo::count());
        $this->assertSame(1, CtdtCt03::count());
        $this->assertSame('MOI', CtdtCt03::first()->chan_doan);
    }

    /** @test */
    public function ghi_nhan_nguoi_nap_va_duong_dan_goc()
    {
        $this->importer->nhapTuChuoi(
            $this->goiCt2025([[$this->chungTu('CT03', ['MA_YTE' => 'YT001'])]]),
            ['imported_by' => 'nguoinap', 'duong_dan_goc' => 'inbox/goi-1.xml']
        );

        $hoSo = CtdtHoSo::first();
        $this->assertSame('nguoinap', $hoSo->imported_by);
        $this->assertSame('inbox/goi-1.xml', $hoSo->duong_dan_goc);
    }

    /** @test */
    public function so_luong_khai_bao_nhieu_hon_thuc_te_thi_tu_choi_ca_tep()
    {
        $xml = $this->goiCt2025(
            [[$this->chungTu('CT03', ['MA_YTE' => 'YT001'])]],
            ['so_luong_ho_so' => 3]
        );

        $kq = $this->importer->nhapTuChuoi($xml);

        $this->assertFalse($kq->thanhCong);
        $this->assertContains('SOLUONGHOSO', $kq->lyDoThatBai);
    }

    /** @test */
    public function loi_lap_trinh_KHONG_bi_nuot_thanh_ho_so_hong()
    {
        // Chi loi mang dau hieu CtdtLoiNap moi duoc ghi thanh "ho so nay hong". Loi khac
        // phai noi len de nguoi van hanh thay - nuot no di la cach chac chan de mot bug
        // song hang thang duoi vo boc "vai ho so khong nap duoc".
        $luuHong = new class extends \App\Services\Ctdt\CtdtLuuHoSo {
            public function luu(array $hoSo)
            {
                throw new \LogicException('bug that su');
            }
        };

        $importer = new CtdtImporter($luuHong);

        $this->expectException(\LogicException::class);

        $importer->nhapTuChuoi($this->goiCt2025([[$this->chungTu('CT03', ['MA_YTE' => 'YT001'])]]));
    }
}
```

- [ ] **Step 2: Chạy test để xác nhận đỏ**

```bash
php vendor/bin/phpunit tests/Unit/Ctdt/CtdtImporterTest.php
```

Kỳ vọng: đỏ — `Class 'App\Services\Ctdt\CtdtImporter' not found`.

- [ ] **Step 3: Viết `CtdtImporter`**

Tạo `app/Services/Ctdt/CtdtImporter.php`:

```php
<?php

namespace App\Services\Ctdt;

use DB;
use App\Services\Ctdt\Loi\CtdtLoiNap;
use App\Services\Ctdt\Loi\ThieuMacskcbException;

/**
 * Diem vao DUY NHAT de nhap mot goi chung tu dien tu.
 *
 * VI SAO DUY NHAT: trong XML3176, nghiep vu nay tung duoc cai HAI lan - mot lan trong
 * controller tai len tay, mot lan trong lenh console quet thu muc - va hai ban DA LECH
 * NHAU. Cung mot ho so cho hai ket qua khac nhau tuy duong vao. Man tai len (Giai doan 2B)
 * va lenh console (Giai doan 5) deu goi ham nay.
 */
class CtdtImporter
{
    /** @var CtdtLuuHoSo */
    private $luu;

    public function __construct(CtdtLuuHoSo $luu = null)
    {
        $this->luu = $luu ?: new CtdtLuuHoSo();
    }

    /**
     * @param string $noiDungXml Noi dung goi HSCHUNGTU / HSDLGBT / HSDLGCS
     * @param array  $tuyChon    macskcb, imported_by, duong_dan_goc - deu tuy chon
     * @return CtdtImportFileResult
     */
    public function nhapTuChuoi($noiDungXml, array $tuyChon = [])
    {
        try {
            $goi     = CtdtGoiParser::doc($noiDungXml);
            $dichVu  = CtdtGoiParser::nhanDienDichVu($goi);
            $macskcb = $this->macskcb($goi, $dichVu, $tuyChon);
            $danhSach = CtdtGoiParser::danhSachHoSo($goi, $dichVu);
        } catch (CtdtLoiNap $e) {
            // Hong ngay tu dau tep: chua xu ly ho so nao.
            return CtdtImportFileResult::thatBaiSom($e->getMessage());
        }

        $idGoi   = CtdtGoiParser::idGoi($goi, $dichVu);
        $ngayLap = CtdtGoiParser::ngayLap($goi);
        $soKhaiBao = CtdtGoiParser::soLuongHoSo($goi);

        $ketQua = [];

        foreach ($danhSach as $i => $chungTu) {
            $ketQua[] = $this->nhapMotHoSo(
                $chungTu, $i + 1, $dichVu, $macskcb, $idGoi, $ngayLap, $soKhaiBao, $tuyChon
            );
        }

        return CtdtImportFileResult::tu($ketQua, $soKhaiBao, count($danhSach));
    }

    /**
     * Nhap MOT ho so. Moi ho so mot transaction RIENG.
     *
     * Mot ho so hong khong duoc keo cac ho so con lai xuong: mot tep 200 ho so ma mot cai
     * sai chinh ta ngay thang thi 199 cai kia van phai vao duoc.
     */
    private function nhapMotHoSo(
        array $chungTu, $chiSo, $dichVu, $macskcb, $idGoi, $ngayLap, $soKhaiBao, array $tuyChon
    ) {
        try {
            $maHoSo = CtdtMaHoSo::cua($chungTu, $idGoi, $chiSo);

            $moTa = [
                'ma_ho_so'       => $maHoSo,
                'id_goi_xml'     => $idGoi,
                'dich_vu'        => $dichVu,
                'loai_hs'        => (string) config('ctdt.dich_vu.' . $dichVu . '.loai_hs'),
                'macskcb'        => $macskcb,
                'ngay_lap'       => $ngayLap,
                'so_luong_ho_so' => $soKhaiBao,
                'imported_by'    => isset($tuyChon['imported_by']) ? $tuyChon['imported_by'] : null,
                'duong_dan_goc'  => isset($tuyChon['duong_dan_goc']) ? $tuyChon['duong_dan_goc'] : null,
                'chung_tu'       => $chungTu,
            ];

            // Mot ho so = mot transaction. Hong o dau cung quay lui sach, va viec xoa ban cu
            // nam BEN TRONG day nen du lieu cu con nguyen khi nap that bai.
            DB::transaction(function () use ($moTa) {
                $this->luu->luu($moTa);
            });

            return CtdtImportResult::thanhCong($maHoSo, array_column($chungTu, 'loai_ho_so'));
        } catch (CtdtLoiNap $e) {
            // CHI bat loi mang dau hieu CtdtLoiNap. Loi lap trinh va loi ha tang phai noi
            // len - nuot chung thanh "ho so nay hong" la cach chac chan de mot bug song
            // hang thang ma khong ai biet.
            \Log::warning('CTDT nhap that bai (ho so #' . $chiSo . '): ' . $e->getMessage());

            return CtdtImportResult::thatBai($e->getMessage());
        }
    }

    /**
     * Ma co so KCB, theo thu tu: XML -> tuy chon nguoi nap -> cau hinh don vi.
     *
     * Goi HSDLGCS khong mang ma co so o bat ky the nao (MA_TTDV la ma so BHXH cua Thu truong
     * co so, khong phai ma co so), nen chuoi lui nay la duong duy nhat cho GCS.
     *
     * @throws ThieuMacskcbException khi can ca ba nguon
     */
    private function macskcb(\SimpleXMLElement $goi, $dichVu, array $tuyChon)
    {
        $ma = CtdtGoiParser::macskcb($goi, $dichVu);

        if ($ma === null && !empty($tuyChon['macskcb'])) {
            $ma = trim((string) $tuyChon['macskcb']);
        }

        if ($ma === null || $ma === '') {
            $ma = trim((string) config('organization.BHYT.ma_cskcb', ''));
        }

        if ($ma === '') {
            throw new ThieuMacskcbException(
                'Khong xac dinh duoc ma co so KCB: goi khong khai, nguoi nap khong chon,'
                . ' va cau hinh organization.BHYT.ma_cskcb dang trong'
            );
        }

        return $ma;
    }
}
```

- [ ] **Step 4: Chạy test để xác nhận xanh**

```bash
php vendor/bin/phpunit tests/Unit/Ctdt/CtdtImporterTest.php
```

Kỳ vọng: `OK (14 tests)`.

- [ ] **Step 5: Commit**

```bash
git add app/Services/Ctdt/CtdtImporter.php tests/Unit/Ctdt/CtdtImporterTest.php
git commit -m "feat(ctdt): CtdtImporter - diem vao duy nhat cua luong nap"
```

---

## Task 8: Lưới an toàn toàn luồng

**Files:**
- Test: `tests/Unit/Ctdt/CtdtNapToanLuongTest.php`

**Interfaces:**
- Consumes: mọi thứ của Task 1–7
- Produces: không có mã sản phẩm mới — đây là lưới an toàn cho cả Giai đoạn 2A

**Vì sao cần:** bảy task trước mỗi cái kiểm một mắt xích. Task này kiểm **chuỗi** — thứ chỉ hỏng
khi các mắt xích ráp lại, và là thứ Giai đoạn 2B sẽ dựa lên.

- [ ] **Step 1: Viết test**

Tạo `tests/Unit/Ctdt/CtdtNapToanLuongTest.php`:

```php
<?php

namespace Tests\Unit\Ctdt;

use Tests\TestCase;
use Tests\Support\DungBangCtdtSqlite;
use Tests\Support\GoiCtdtMau;
use App\Services\Ctdt\CtdtImporter;
use App\Services\Ctdt\CtdtLoaiRegistry;
use App\Models\BHYT\Ctdt\CtdtHoSo;
use App\Models\BHYT\Ctdt\CtdtChungTu;

/**
 * Luoi an toan cho ca Giai doan 2A: nap that, qua het chuoi, xuong CSDL that.
 */
class CtdtNapToanLuongTest extends TestCase
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
    }

    /**
     * Mot bo truong toi thieu de chung tu nao cung nap duoc: khoa nghiep vu neu loai do co.
     */
    private function truongToiThieu($lop)
    {
        $truong = $lop::truong();
        $bo = [];

        foreach (['MA_YTE', 'MA_GBT', 'MA_GCS'] as $khoa) {
            if (array_key_exists($khoa, $truong)) {
                $bo[$khoa] = 'KEY-' . $lop::maLoaiHoSo();
            }
        }

        // Mot truong van ban de chac chan anh xa the -> cot chay that, khong chi chay rong.
        if (array_key_exists('HO_TEN', $truong)) {
            $bo['HO_TEN'] = 'Nguoi Benh Test';
        } elseif (array_key_exists('HOTEN_NND', $truong)) {
            $bo['HOTEN_NND'] = 'Nguoi Me Test';
        }

        return $bo;
    }

    /** @test */
    public function bay_loai_tt25_deu_nap_duoc_va_xuong_dung_bang()
    {
        foreach (CtdtLoaiRegistry::cuaDichVu('CT2025') as $loai => $lop) {
            $xml = $this->goiCt2025([[$this->chungTu($loai, $this->truongToiThieu($lop))]]);

            $kq = $this->importer->nhapTuChuoi($xml, ['macskcb' => '01929']);

            $this->assertTrue($kq->thanhCong, $loai . ': ' . (string) $kq->lyDoThatBai);

            $tenModel = $lop::model();
            $this->assertSame(1, $tenModel::count(), $loai . ': chi tiet phai xuong bang ' . $lop::bang());

            // Don sach de vong sau dem lai tu dau.
            CtdtHoSo::query()->delete();
            $tenModel::query()->delete();
            CtdtChungTu::query()->delete();
        }
    }

    /** @test */
    public function ba_dich_vu_deu_nap_duoc_va_ghi_dung_loai_hs()
    {
        $this->importer->nhapTuChuoi(
            $this->goiCt2025([[$this->chungTu('CT03', ['MA_YTE' => 'YT001'])]]),
            ['macskcb' => '01929']
        );
        $this->importer->nhapTuChuoi($this->goiGbt(['MA_GBT' => 'GBT-1']));
        $this->importer->nhapTuChuoi($this->goiGcs(['MA_GCS' => 'GCS-1']));

        $this->assertSame(3, CtdtHoSo::count());

        $this->assertSame('39', CtdtHoSo::where('ma_ho_so', 'YT001')->first()->loai_hs);
        $this->assertSame('60', CtdtHoSo::where('ma_ho_so', 'GBT-1')->first()->loai_hs);
        $this->assertSame('61', CtdtHoSo::where('ma_ho_so', 'GCS-1')->first()->loai_hs);

        $this->assertSame('CT2025', CtdtHoSo::where('ma_ho_so', 'YT001')->first()->dich_vu);
        $this->assertSame('GBT', CtdtHoSo::where('ma_ho_so', 'GBT-1')->first()->dich_vu);
        $this->assertSame('GCS', CtdtHoSo::where('ma_ho_so', 'GCS-1')->first()->dich_vu);
    }

    /** @test */
    public function tep_nhieu_ho_so_nap_du_khong_sot_cai_nao()
    {
        $hoSo = [];

        for ($i = 1; $i <= 10; $i++) {
            $hoSo[] = [$this->chungTu('CT03', ['MA_YTE' => 'YT' . str_pad($i, 3, '0', STR_PAD_LEFT)])];
        }

        $kq = $this->importer->nhapTuChuoi($this->goiCt2025($hoSo), ['macskcb' => '01929']);

        $this->assertTrue($kq->thanhCong, (string) $kq->lyDoThatBai);
        $this->assertSame(10, $kq->soThanhCong);
        $this->assertSame(10, CtdtHoSo::count());
        $this->assertNotNull(CtdtHoSo::where('ma_ho_so', 'YT010')->first(), 'Ho so cuoi cung khong duoc bo sot');
    }

    /** @test */
    public function hai_ho_so_khong_co_ma_yte_trong_mot_tep_thanh_hai_ban_ghi()
    {
        $xml = $this->goiCt2025([
            [$this->chungTu('CT04', ['MA_CT' => 'CT-1'])],
            [$this->chungTu('CT04', ['MA_CT' => 'CT-2'])],
        ], ['id' => 'Id-abc']);

        $kq = $this->importer->nhapTuChuoi($xml, ['macskcb' => '01929']);

        $this->assertTrue($kq->thanhCong, (string) $kq->lyDoThatBai);
        $this->assertSame(2, CtdtHoSo::count(), 'Khoa lui phai kem chi so, khong duoc trung');
        $this->assertSame(['Id-abc#1', 'Id-abc#2'], $kq->dsMaHoSo);
    }

    /** @test */
    public function nap_lai_ba_lan_van_chi_mot_ban_ghi()
    {
        for ($i = 1; $i <= 3; $i++) {
            $kq = $this->importer->nhapTuChuoi($this->goiCt2025([[
                $this->chungTu('CT03', ['MA_YTE' => 'YT001', 'CHAN_DOAN' => 'Lan ' . $i]),
            ]]), ['macskcb' => '01929']);

            $this->assertTrue($kq->thanhCong, 'Lan ' . $i . ': ' . (string) $kq->lyDoThatBai);
        }

        $this->assertSame(1, CtdtHoSo::count());
        $this->assertSame(1, CtdtChungTu::count());
        $this->assertSame('Lan 3', \App\Models\BHYT\Ctdt\CtdtCt03::first()->chan_doan);
    }

    /** @test */
    public function noi_dung_goc_du_de_dung_lai_khoa_nghiep_vu()
    {
        // Khi cong bao 205, thu duy nhat de doi chieu la noi dung nguyen van. Neu no khong
        // du de tim lai ho so thi viec luu no chang giup duoc gi.
        $this->importer->nhapTuChuoi($this->goiCt2025([[
            $this->chungTu('CT03', ['MA_YTE' => 'YT001', 'HO_TEN' => 'Nguyen Van Test']),
        ]]), ['macskcb' => '01929']);

        $goc = CtdtChungTu::first()->noi_dung_goc;
        $lai = simplexml_load_string($goc);

        $this->assertNotFalse($lai, 'noi_dung_goc phai parse lai duoc');
        $this->assertSame('YT001', (string) $lai->MA_YTE);
        $this->assertSame('CT03', $lai->getName());
    }
}
```

- [ ] **Step 2: Chạy test**

```bash
php vendor/bin/phpunit tests/Unit/Ctdt/CtdtNapToanLuongTest.php
```

Kỳ vọng: `OK (6 tests)`. Nếu đỏ ở `bay_loai_tt25_deu_nap_duoc_va_xuong_dung_bang`, thông điệp sẽ nêu đúng loại nào hỏng — sửa nơi lệch thật, đừng nới test.

- [ ] **Step 3: Chạy toàn bộ test của module**

```bash
php vendor/bin/phpunit tests/Unit/Ctdt
```

Kỳ vọng: `OK (139 tests)` — 59 của Giai đoạn 1 cộng 80 của Giai đoạn 2A (6 + 13 + 9 + 11 + 8 + 13 + 14 + 6).

Nếu con số lệch, đếm lại theo từng tệp trước khi kết luận có gì hỏng — số test là chỉ dấu, không phải điều kiện.

- [ ] **Step 4: Chạy toàn bộ Unit suite, đối chiếu baseline**

```bash
php vendor/bin/phpunit --testsuite Unit
```

Kỳ vọng: `Errors: 4, Failures: 7` — **đúng bằng baseline**. Nhiều hơn nghĩa là Giai đoạn 2A làm hỏng thứ khác.

- [ ] **Step 5: Commit**

```bash
git add tests/Unit/Ctdt/CtdtNapToanLuongTest.php
git commit -m "test(ctdt): luoi an toan toan luong nap"
```

---

## Hoàn tất Giai đoạn 2A

Sẵn sàng cho Giai đoạn 2B (ba màn hình):

- `CtdtImporter::nhapTuChuoi($xml, ['macskcb' =>, 'imported_by' =>, 'duong_dan_goc' =>])` → `CtdtImportFileResult`
- `CtdtImportFileResult` mang `$thanhCong`, `$lyDoThatBai`, `$soThanhCong`, `$soThatBai`, `$dsMaHoSo`, `$ketQua[]` — đủ để hiển thị kết quả theo từng tệp và từng hồ sơ
- Dữ liệu đã xuống 12 bảng; cột rút gọn trên `ctdt_chung_tu` đủ cho DataTable mà không phải `UNION`

**Chưa có và cố ý chưa có:** controller, route, view, `CheckCtdtJob`, `SignCtdtJob`,
`CtdtSubmitService`, lệnh Console. Giai đoạn 2A không gọi mạng, không có giao diện.

**Chỗ móc để lại cho Giai đoạn 3–4:** sau `DB::transaction` trong `CtdtImporter::nhapMotHoSo()`
là nơi dispatch `CheckCtdtJob` rồi `SignCtdtJob` — **sau commit**, không phải trong transaction,
vì job đặt trong transaction sẽ trỏ tới dữ liệu chưa tồn tại nếu rollback.

**Việc cần làm khi cập nhật tài liệu sau giai đoạn này:** mục 2 của
`docs/chung-tu-dien-tu-pl02.md` đang ghi "Chưa có: parser gói XML, importer, ..." — bỏ hai mục đầu.
