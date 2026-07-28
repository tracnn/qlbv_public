# Import XML3176 — Giai đoạn 1: gộp hai đường import — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Nghiệp vụ nhập một hồ sơ XML3176 chỉ còn một bản cài đặt, dùng chung cho cả tải lên tay lẫn quét thư mục.

**Architecture:** Lớp `Xml3176Importer` nhận chuỗi XML và trả về `Xml3176ImportResult`. Bảng ánh xạ `LOAI_XML` thay khối `switch` 15–18 nhánh, phân biệt rõ "bỏ qua có chủ đích" (`null`) với "loại lạ" (không có trong bảng). Controller và console command đều gọi qua lớp này.

**Tech Stack:** Laravel 5.5, PHP 7.4, PHPUnit 6.5, SimpleXML.

## Global Constraints

- Spec: `docs/superpowers/specs/2026-07-28-xml3176-import-pha-1-gop-hai-duong-design.md`
- Cổng test: **`vendor/bin/phpunit --testsuite Unit`** và chỉ suite này. Mốc: **282 test xanh**.
- Đây là **tái cấu trúc**: cùng dữ liệu vào, cùng dữ liệu ra. Đúng **một** thay đổi hành vi có chủ đích — file hỏng không còn làm dừng lượt quét thư mục.
- **Sáu lỗi phải giữ nguyên**, không được "tiện tay sửa": không transaction; `try/catch` nuốt lỗi từng dòng trong `Xml3176Service`; `soluonghoso` dùng `count()` (luôn ra 1); file nguồn bị xoá sau xử lý; import chạy đồng bộ trong request; một job kiểm lỗi mỗi dòng. Chúng thuộc giai đoạn 2–4.
- Không đụng `Xml3176Service` (1.908 dòng) ngoài việc gọi phương thức của nó.
- Không sửa thứ tự `FILEHOSO`, không đụng việc chỉ xử lý `HOSO` đầu tiên — hai phát hiện chưa đủ căn cứ, đã ghi trong spec.
- Comment mã nguồn viết tiếng Việt **không dấu**.
- Sau mỗi task: `php -l` file đã sửa, chạy suite Unit, commit.

---

### Task 1: Lớp kết quả và bảng đăng ký loại XML

**Files:**
- Create: `app/Services/Xml3176/Xml3176ImportResult.php`
- Create: `app/Services/Xml3176/Xml3176Importer.php` (chỉ hằng + hàm tra cứu ở task này)
- Create: `tests/Unit/Xml3176/Xml3176ImporterRegistryTest.php`

**Interfaces:**
- Consumes: `App\Services\Xml3176Service` (chỉ để `method_exists`).
- Produces:
  ```php
  Xml3176Importer::LOAI_XML                    // array<string, string|null>
  Xml3176Importer::coTrongDangKy($loai): bool
  Xml3176Importer::handlerCho($loai): ?string
  Xml3176ImportResult::thanhCong(string $maLk, array $loaiDaXuLy): self
  Xml3176ImportResult::thatBai(string $lyDo): self
  ```

- [ ] **Step 1: Viết test (sẽ đỏ)**

Tạo `tests/Unit/Xml3176/Xml3176ImporterRegistryTest.php`:

```php
<?php

namespace Tests\Unit\Xml3176;

use Tests\TestCase;
use App\Services\Xml3176Service;
use App\Services\Xml3176\Xml3176Importer;
use App\Services\Xml3176\Xml3176ImportResult;

class Xml3176ImporterRegistryTest extends TestCase
{
    /** @test */
    public function bang_dang_ky_phu_du_xml1_den_xml18()
    {
        // Hop cua HAI ban cu: controller xu ly XML1-15, command xu ly XML1-18.
        // Thieu mot ma o day la danh roi mot nhanh khi gop.
        $mongDoi = [];
        for ($i = 1; $i <= 18; $i++) {
            $mongDoi[] = 'XML' . $i;
        }

        $this->assertEquals($mongDoi, array_keys(Xml3176Importer::LOAI_XML));
    }

    /** @test */
    public function cac_loai_bo_qua_co_chu_dich_anh_xa_null_chu_khong_vang_mat()
    {
        // Phan biet "bo qua co chu dich" voi "khong co trong bang" chinh la thu da mat
        // khi hai ban lech nhau. Vang mat -> ghi canh bao; null -> im lang bo qua.
        foreach (['XML12', 'XML16', 'XML17', 'XML18'] as $loai) {
            $this->assertArrayHasKey($loai, Xml3176Importer::LOAI_XML);
            $this->assertNull(Xml3176Importer::LOAI_XML[$loai], "$loai phai la bo qua co chu dich");
        }
    }

    /** @test */
    public function moi_handler_deu_la_phuong_thuc_co_that_tren_service()
    {
        foreach (Xml3176Importer::LOAI_XML as $loai => $handler) {
            if ($handler === null) {
                continue;
            }

            $this->assertTrue(
                method_exists(Xml3176Service::class, $handler),
                "Xml3176Service khong co phuong thuc $handler (khai bao cho $loai)"
            );
        }
    }

    /** @test */
    public function tra_cuu_phan_biet_ba_trang_thai()
    {
        $this->assertTrue(Xml3176Importer::coTrongDangKy('XML2'));
        $this->assertEquals('storeXml3176Xml2', Xml3176Importer::handlerCho('XML2'));

        $this->assertTrue(Xml3176Importer::coTrongDangKy('XML12'));
        $this->assertNull(Xml3176Importer::handlerCho('XML12'));

        $this->assertFalse(Xml3176Importer::coTrongDangKy('XML99'));
        $this->assertFalse(Xml3176Importer::coTrongDangKy(''));
    }

    /** @test */
    public function ket_qua_nhap_mang_du_thong_tin_hai_ben_goi_can()
    {
        $ok = Xml3176ImportResult::thanhCong('MALK1', ['XML1', 'XML2']);
        $this->assertTrue($ok->thanhCong);
        $this->assertEquals('MALK1', $ok->maLk);
        $this->assertEquals(['XML1', 'XML2'], $ok->loaiDaXuLy);
        $this->assertNull($ok->lyDoThatBai);

        $hong = Xml3176ImportResult::thatBai('Thieu MACSKCB');
        $this->assertFalse($hong->thanhCong);
        $this->assertNull($hong->maLk);
        $this->assertEquals('Thieu MACSKCB', $hong->lyDoThatBai);
    }
}
```

- [ ] **Step 2: Chạy test, xác nhận đỏ**

Run: `vendor/bin/phpunit --testsuite Unit --filter Xml3176ImporterRegistryTest`
Expected: FAIL — `Class 'App\Services\Xml3176\Xml3176Importer' not found`

- [ ] **Step 3: Viết `Xml3176ImportResult`**

```php
<?php

namespace App\Services\Xml3176;

/**
 * Ket qua nhap MOT ho so XML3176.
 *
 * Tra ve doi tuong thay vi bool: controller can thong diep loi de hien len giao dien,
 * con lenh console can biet co duoc xoa file nguon hay khong.
 */
class Xml3176ImportResult
{
    /** @var bool */
    public $thanhCong;

    /** @var string|null */
    public $maLk;

    /** @var array */
    public $loaiDaXuLy = [];

    /** @var string|null */
    public $lyDoThatBai;

    public static function thanhCong($maLk, array $loaiDaXuLy)
    {
        $kq = new self();
        $kq->thanhCong  = true;
        $kq->maLk       = $maLk;
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

- [ ] **Step 4: Viết khung `Xml3176Importer` với bảng đăng ký**

```php
<?php

namespace App\Services\Xml3176;

use App\Services\Xml3176Service;

/**
 * Diem vao DUY NHAT de nhap mot ho so XML3176.
 *
 * Truoc day nghiep vu nay duoc cai dat hai lan - trong BHYTXml3176Controller (tai len
 * tay) va trong Console\Commands\XML3176Import (quet thu muc) - va hai ban DA lech
 * nhau: controller xu ly XML1-15, command xu ly XML1-18 va co them chinh sach
 * exportable_tt. Cung mot ho so cho hai ket qua khac nhau tuy duong vao.
 */
class Xml3176Importer
{
    /**
     * Anh xa LOAIHOSO -> phuong thuc luu tren Xml3176Service.
     *
     * Ba trang thai KHAC NHAU, dung lan lon:
     *   - co khoa, gia tri chuoi : goi phuong thuc do
     *   - co khoa, gia tri null  : BO QUA CO CHU DICH, khong ghi log
     *   - khong co khoa          : loai la, ghi Log::warning
     *
     * Su nhap nhang giua hai truong hop dau va cuoi chinh la thu da mat khi hai ban
     * cai dat lech nhau.
     */
    const LOAI_XML = [
        'XML1'  => 'storeXml3176Xml1',
        'XML2'  => 'storeXml3176Xml2',
        'XML3'  => 'storeXml3176Xml3',
        'XML4'  => 'storeXml3176Xml4',
        'XML5'  => 'storeXml3176Xml5',
        'XML6'  => 'storeXml3176Xml6',
        'XML7'  => 'storeXml3176Xml7',
        'XML8'  => 'storeXml3176Xml8',
        'XML9'  => 'storeXml3176Xml9',
        'XML10' => 'storeXml3176Xml10',
        'XML11' => 'storeXml3176Xml11',
        'XML12' => null,
        'XML13' => 'storeXml3176Xml13',
        'XML14' => 'storeXml3176Xml14',
        'XML15' => 'storeXml3176Xml15',
        'XML16' => null,
        'XML17' => null,
        'XML18' => null,
    ];

    protected $xml3176Service;

    public function __construct(Xml3176Service $xml3176Service)
    {
        $this->xml3176Service = $xml3176Service;
    }

    public static function coTrongDangKy($loai)
    {
        return is_string($loai) && array_key_exists($loai, self::LOAI_XML);
    }

    /**
     * @return string|null Ten phuong thuc, hoac null neu bo qua co chu dich
     */
    public static function handlerCho($loai)
    {
        return self::coTrongDangKy($loai) ? self::LOAI_XML[$loai] : null;
    }
}
```

- [ ] **Step 5: Chạy test, xác nhận xanh**

Run: `vendor/bin/phpunit --testsuite Unit --filter Xml3176ImporterRegistryTest`
Expected: PASS (5 test)

- [ ] **Step 6: Kiểm cú pháp và chạy toàn bộ suite**

Run: `php -l app/Services/Xml3176/Xml3176Importer.php app/Services/Xml3176/Xml3176ImportResult.php`
Run: `vendor/bin/phpunit --testsuite Unit`
Expected: PASS, **287 test**

- [ ] **Step 7: Commit**

```bash
git add app/Services/Xml3176/Xml3176Importer.php app/Services/Xml3176/Xml3176ImportResult.php tests/Unit/Xml3176/Xml3176ImporterRegistryTest.php
git commit -m "feat(xml3176): bang dang ky loai XML va ket qua nhap dung chung"
```

---

### Task 2: Thân hàm `nhapTuChuoi`

**Files:**
- Modify: `app/Services/Xml3176/Xml3176Importer.php`
- Create: `tests/Unit/Xml3176/Xml3176ImporterParseTest.php`

**Interfaces:**
- Consumes: `Xml3176Importer::LOAI_XML`, `Xml3176ImportResult` (Task 1)
- Produces: `nhapTuChuoi(string $noiDungXml, array $tuyChon = []): Xml3176ImportResult`

- [ ] **Step 1: Viết test cho phần parse phong bì (sẽ đỏ)**

Chỉ phủ được các nhánh **thất bại sớm** — chúng dừng trước khi chạm cơ sở dữ liệu. Nhánh
thành công cần DB nên để nghiệm thu thủ công (DB dev trống cả bốn bảng `xml3176_*`).

Tạo `tests/Unit/Xml3176/Xml3176ImporterParseTest.php`:

```php
<?php

namespace Tests\Unit\Xml3176;

use Tests\TestCase;
use App\Services\Xml3176\Xml3176Importer;

class Xml3176ImporterParseTest extends TestCase
{
    private function importer()
    {
        return app(Xml3176Importer::class);
    }

    /** @test */
    public function chuoi_khong_parse_duoc_thi_that_bai_co_ly_do()
    {
        $kq = $this->importer()->nhapTuChuoi('day khong phai xml <<<');

        $this->assertFalse($kq->thanhCong);
        $this->assertNotEmpty($kq->lyDoThatBai);
    }

    /** @test */
    public function chuoi_rong_thi_that_bai_co_ly_do()
    {
        $kq = $this->importer()->nhapTuChuoi('');

        $this->assertFalse($kq->thanhCong);
        $this->assertNotEmpty($kq->lyDoThatBai);
    }

    /** @test */
    public function thieu_macskcb_thi_that_bai_co_ly_do()
    {
        $xml = '<GIAMDINHHS><THONGTINDONVI></THONGTINDONVI>'
             . '<THONGTINHOSO><SOLUONGHOSO>1</SOLUONGHOSO></THONGTINHOSO></GIAMDINHHS>';

        $kq = $this->importer()->nhapTuChuoi($xml);

        $this->assertFalse($kq->thanhCong);
        $this->assertContains('MACSKCB', $kq->lyDoThatBai);
    }

    /** @test */
    public function macskcb_rong_cung_bi_coi_la_thieu()
    {
        $xml = '<GIAMDINHHS><THONGTINDONVI><MACSKCB></MACSKCB></THONGTINDONVI>'
             . '<THONGTINHOSO><SOLUONGHOSO>1</SOLUONGHOSO></THONGTINHOSO></GIAMDINHHS>';

        $kq = $this->importer()->nhapTuChuoi($xml);

        $this->assertFalse($kq->thanhCong);
        $this->assertContains('MACSKCB', $kq->lyDoThatBai);
    }

    /** @test */
    public function khong_co_filehoso_nao_thi_that_bai_khong_no()
    {
        // Khong co FILEHOSO -> khong co ma_lk -> khong duoc coi la thanh cong,
        // va tuyet doi khong duoc nem loi.
        $xml = '<GIAMDINHHS><THONGTINDONVI><MACSKCB>01234</MACSKCB></THONGTINDONVI>'
             . '<THONGTINHOSO><SOLUONGHOSO>0</SOLUONGHOSO>'
             . '<DANHSACHHOSO></DANHSACHHOSO></THONGTINHOSO></GIAMDINHHS>';

        $kq = $this->importer()->nhapTuChuoi($xml);

        $this->assertFalse($kq->thanhCong);
        $this->assertNotEmpty($kq->lyDoThatBai);
    }
}
```

- [ ] **Step 2: Chạy test, xác nhận đỏ**

Run: `vendor/bin/phpunit --testsuite Unit --filter Xml3176ImporterParseTest`
Expected: FAIL — `Call to undefined method ... nhapTuChuoi()`

- [ ] **Step 3: Viết `nhapTuChuoi`**

Thêm vào `Xml3176Importer`:

```php
    /**
     * Nhap MOT ho so tu chuoi XML.
     *
     * @param string $noiDungXml Noi dung file GIAMDINHHS
     * @param array  $tuyChon    ['cho_phep_xuat' => bool] - mac dinh true
     */
    public function nhapTuChuoi($noiDungXml, array $tuyChon = [])
    {
        $choPhepXuat = array_key_exists('cho_phep_xuat', $tuyChon)
            ? (bool) $tuyChon['cho_phep_xuat']
            : true;

        // simplexml_load_string phat warning voi chuoi hong; tat di va tu bao loi.
        $truocDo = libxml_use_internal_errors(true);
        $xmldata = @simplexml_load_string($noiDungXml);
        libxml_clear_errors();
        libxml_use_internal_errors($truocDo);

        if ($xmldata === false) {
            return Xml3176ImportResult::thatBai('Khong doc duoc noi dung XML');
        }

        if (!isset($xmldata->THONGTINDONVI->MACSKCB)
            || trim((string) $xmldata->THONGTINDONVI->MACSKCB) === '') {
            \Log::error('MACSKCB not found or is empty in XML data');

            return Xml3176ImportResult::thatBai('Thieu MACSKCB trong noi dung XML');
        }

        $macskcb = (string) $xmldata->THONGTINDONVI->MACSKCB;

        // Tinh MOT lan truoc vong lap. Ban controller cu tinh lai trong moi vong
        // FILEHOSO - gia tri khong doi nen day la thay doi bao toan hanh vi.
        // LUU Y: count() tren node la luon ra 1. Day la loi CO SAN, giai doan 2 sua.
        $soluonghoso = count($xmldata->THONGTINHOSO->SOLUONGHOSO);

        $ma_lk = null;
        $processedFileTypes = [];

        foreach ($xmldata->THONGTINHOSO->DANHSACHHOSO->HOSO->FILEHOSO as $file_hs) {
            $fileType = (string) $file_hs->LOAIHOSO;

            if (!self::coTrongDangKy($fileType)) {
                \Log::warning('Unknown XML type: ' . $fileType);
                continue;
            }

            $handler = self::handlerCho($fileType);

            if ($handler === null) {
                continue;   // bo qua co chu dich
            }

            $data = simplexml_load_string(base64_decode($file_hs->NOIDUNGFILE));

            if ($data === false) {
                \Log::error('Khong doc duoc noi dung file ' . $fileType);

                return Xml3176ImportResult::thatBai('Noi dung ' . $fileType . ' khong doc duoc');
            }

            if ($fileType === 'XML1') {
                $expectedStructure = XmlStructures::$expectedStructures3176[$fileType] ?? [];

                if (!empty($expectedStructure) && !validateDataStructure($data, $expectedStructure)) {
                    \Log::error('Invalid data structure for ' . $fileType);

                    return Xml3176ImportResult::thatBai('Sai cau truc du lieu ' . $fileType);
                }

                $ma_lk = (string) $data->MA_LK;
                $this->xml3176Service->deleteExistingXml3176($ma_lk);
            }

            $processedFileTypes[] = $fileType;
            $this->xml3176Service->{$handler}($data, $fileType);
        }

        if ($ma_lk === null || empty($processedFileTypes)) {
            return Xml3176ImportResult::thatBai('Khong tim thay du lieu ho so hop le trong file');
        }

        $this->xml3176Service->storeXml3176Information($ma_lk, $macskcb, 'import', $soluonghoso);

        if (!config('organization.xml_3176_not_check', false)) {
            $this->xml3176Service->checkXml3176Complete($ma_lk);
        }

        if ($choPhepXuat && config('xml3176.export_xml3176_enabled')) {
            $this->xml3176Service->exportXml3176($ma_lk);
        }

        return Xml3176ImportResult::thanhCong($ma_lk, $processedFileTypes);
    }
```

Thêm `use App\Services\XmlStructures;` vào khối `use` đầu file.

**Khác biệt có chủ đích so với hai bản cũ**, ngoài phần đã nêu trong spec: bản cũ chỉ đẩy
`$processedFileTypes[] = $fileType` cho XML1, khiến biến này luôn chỉ chứa `['XML1']`.
Bản gộp ghi nhận mọi loại đã xử lý. Điều kiện dùng nó (`!empty($processedFileTypes)`)
cho kết quả **giống hệt** vì XML1 là bắt buộc để có `ma_lk`.

- [ ] **Step 4: Chạy test, xác nhận xanh**

Run: `vendor/bin/phpunit --testsuite Unit --filter Xml3176ImporterParseTest`
Expected: PASS (5 test)

- [ ] **Step 5: Kiểm cú pháp và chạy toàn bộ suite**

Run: `php -l app/Services/Xml3176/Xml3176Importer.php`
Run: `vendor/bin/phpunit --testsuite Unit`
Expected: PASS, **292 test**

- [ ] **Step 6: Commit**

```bash
git add app/Services/Xml3176/Xml3176Importer.php tests/Unit/Xml3176/Xml3176ImporterParseTest.php
git commit -m "feat(xml3176): than ham nhapTuChuoi cho diem vao import dung chung"
```

---

### Task 3: Controller dùng importer

**Files:**
- Modify: `app/Http/Controllers/BHYT/BHYTXml3176Controller.php`

**Interfaces:**
- Consumes: `Xml3176Importer::nhapTuChuoi()` (Task 2)
- Produces: không có.

- [ ] **Step 1: Tiêm importer vào controller**

Thêm `use App\Services\Xml3176\Xml3176Importer;` vào khối `use`, thêm thuộc tính và tham
số constructor:

```php
    protected $xml3176Service;
    protected $importer;

    public function __construct(Xml3176Service $xml3176Service, Xml3176Importer $importer)
    {
        $this->xml3176Service = $xml3176Service;
        $this->importer = $importer;
    }
```

- [ ] **Step 2: Đổi `uploadData` sang dùng importer**

Thay khối trong vòng lặp file:

```php
                    $xmldata = simplexml_load_file($fileFullPath);
                    
                    if (!$this->processXmlData($xmldata)) {
                        $errors[] = "File {$fileName} has invalid structure.";
                    }
```

bằng:

```php
                    $kq = $this->importer->nhapTuChuoi(file_get_contents($fileFullPath));

                    if (!$kq->thanhCong) {
                        $errors[] = "File {$fileName}: {$kq->lyDoThatBai}";
                    }
```

Thông điệp lỗi nay nêu **lý do cụ thể** thay vì luôn là "has invalid structure" — bản cũ
báo cùng một câu cho mọi nguyên nhân.

- [ ] **Step 3: Xoá `processXmlData` khỏi controller**

Xoá toàn bộ phương thức `private function processXmlData($xmldata)` (khoảng 100 dòng, từ
dòng ~596). Nghiệp vụ của nó nay nằm trong `Xml3176Importer::nhapTuChuoi()`.

- [ ] **Step 4: Xác nhận không còn khối switch trong controller**

Run: `grep -n "case 'XML\|processXmlData" app/Http/Controllers/BHYT/BHYTXml3176Controller.php`
Expected: **không có kết quả**

- [ ] **Step 5: Kiểm cú pháp và chạy suite**

Run: `php -l app/Http/Controllers/BHYT/BHYTXml3176Controller.php`
Run: `vendor/bin/phpunit --testsuite Unit`
Expected: PASS, 292 test

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/BHYT/BHYTXml3176Controller.php
git commit -m "refactor(xml3176): controller dung importer dung chung thay vi khoi switch rieng"
```

---

### Task 4: Console command dùng importer, và bỏ lỗi tắc lượt quét

Đây là task chứa **thay đổi hành vi có chủ đích** duy nhất của giai đoạn 1.

**Files:**
- Modify: `app/Console/Commands/XML3176Import.php`
- Create: `tests/Unit/Xml3176/Xml3176ImportSourceTest.php`

**Interfaces:**
- Consumes: `Xml3176Importer::nhapTuChuoi()` (Task 2)
- Produces: không có.

- [ ] **Step 1: Viết test canh gác (sẽ đỏ)**

Tạo `tests/Unit/Xml3176/Xml3176ImportSourceTest.php`:

```php
<?php

namespace Tests\Unit\Xml3176;

use Tests\TestCase;

class Xml3176ImportSourceTest extends TestCase
{
    /** @test */
    public function nghiep_vu_phan_loai_xml_chi_con_o_mot_noi()
    {
        // Bang anh xa loai XML chi duoc ton tai trong Xml3176Importer. Hai ban cu tung
        // lech nhau (controller XML1-15, command XML1-18) chinh vi co hai noi biet.
        $noiKhongDuocBiet = [
            app_path('Http/Controllers/BHYT/BHYTXml3176Controller.php'),
            app_path('Console/Commands/XML3176Import.php'),
        ];

        foreach ($noiKhongDuocBiet as $file) {
            $this->assertNotContains(
                "case 'XML",
                file_get_contents($file),
                basename($file) . ' van con khoi switch phan loai XML'
            );
        }
    }

    /** @test */
    public function vong_lap_quet_thu_muc_khong_con_return_false_giua_chung()
    {
        // 'return false' giua vong lap lam DUNG ca luot quet: moi file xep sau bi bo
        // qua, file hong khong bi xoa, va lenh chay lai moi 3 giay -> tac vinh vien.
        $src = file_get_contents(app_path('Console/Commands/XML3176Import.php'));

        $vitri = strpos($src, 'function importFilesFromDisk');
        $this->assertNotFalse($vitri, 'Khong tim thay importFilesFromDisk');

        $this->assertNotContains(
            'return false',
            substr($src, $vitri),
            'importFilesFromDisk van con return false - mot file hong se lam tac ca luot quet'
        );
    }
}
```

- [ ] **Step 2: Chạy test, xác nhận đỏ cả hai**

Run: `vendor/bin/phpunit --testsuite Unit --filter Xml3176ImportSourceTest`
Expected: FAIL — command còn `case 'XML` và còn `return false`

- [ ] **Step 3: Tiêm importer vào command**

```php
use App\Services\Xml3176\Xml3176Importer;
```

```php
    protected $importer;

    public function __construct(Xml3176Service $xml3176Service, Xml3176Importer $importer)
    {
        parent::__construct();
        $this->xml3176Service = $xml3176Service;
        $this->importer = $importer;
    }
```

- [ ] **Step 4: Viết lại `importFilesFromDisk`**

Thay toàn bộ thân phương thức bằng:

```php
    protected function importFilesFromDisk($disk)
    {
        // Chinh sach rieng cua luong quet dia O LAI DAY - importer khong biet gi ve $disk.
        $choPhepXuat = !($disk === 'xml3176tt' && config('xml3176.exportable_tt') == false);

        try {
            $files = Storage::disk($disk)->allFiles();
        } catch (\Exception $e) {
            \Log::error('Khong doc duoc thu muc ' . $disk . ': ' . $e->getMessage());

            return;
        }

        foreach ($files as $file) {
            // Moi file mot luot doc lap: file hong thi BO QUA FILE DO va di tiep.
            // Truoc day dung 'return false' nen mot file hong lam dung ca luot quet,
            // ma no lai khong bi xoa, nen luot sau lai vap dung no - tac vinh vien.
            try {
                if (Storage::disk($disk)->mimeType($file) != 'text/xml') {
                    continue;
                }

                $kq = $this->importer->nhapTuChuoi(
                    Storage::disk($disk)->get($file),
                    ['cho_phep_xuat' => $choPhepXuat]
                );

                if (!$kq->thanhCong) {
                    // Giu nguyen hanh vi hien tai: KHONG xoa file hong, de con du lieu
                    // ma dieu tra. Giai doan 2 chuyen no sang thu muc rieng.
                    \Log::error('Import that bai ' . $disk . '/' . $file . ': ' . $kq->lyDoThatBai);
                    continue;
                }

                $this->info($kq->maLk);

                Storage::disk($disk)->delete($file);
            } catch (\Exception $e) {
                \Log::error('Loi khi xu ly ' . $disk . '/' . $file . ': ' . $e->getMessage());
            }
        }
    }
```

`array_chunk($files, 100)` bị bỏ: nó chỉ chia vòng lặp làm hai tầng mà không thay đổi
điều gì — không có xử lý theo lô, không giải phóng bộ nhớ giữa các lô.

- [ ] **Step 5: Chạy test canh gác, xác nhận xanh**

Run: `vendor/bin/phpunit --testsuite Unit --filter Xml3176ImportSourceTest`
Expected: PASS (2 test)

- [ ] **Step 6: Xác nhận command không còn tự dựng nghiệp vụ**

Run: `grep -c "storeXml3176Xml\|deleteExistingXml3176\|validateDataStructure" app/Console/Commands/XML3176Import.php`
Expected: `0`

- [ ] **Step 7: Kiểm cú pháp và chạy toàn bộ suite**

Run: `php -l app/Console/Commands/XML3176Import.php`
Run: `vendor/bin/phpunit --testsuite Unit`
Expected: PASS, **294 test**

- [ ] **Step 8: Commit**

```bash
git add app/Console/Commands/XML3176Import.php tests/Unit/Xml3176/Xml3176ImportSourceTest.php
git commit -m "refactor(xml3176): lenh quet thu muc dung importer, file hong khong con lam tac luot quet"
```

---

## Nghiệm thu thủ công (bắt buộc)

DB dev trống cả bốn bảng `xml3176_*` nên không chạy được import thật tại chỗ. Toàn bộ
phần dưới phải làm trên môi trường có dữ liệu.

**Chuẩn bị: giữ lại một bản sao vài file XML thật trước khi thử**, vì luồng quét đĩa xoá
file sau khi nhập thành công.

| # | Việc | Mong đợi |
|---|---|---|
| 1 | Tải lên một file XML hợp lệ qua giao diện | Nhập thành công; hồ sơ hiện trong danh sách như trước |
| 2 | So số dòng từng bảng con của hồ sơ đó với một hồ sơ nhập trước khi sửa | Bằng nhau |
| 3 | Tải lên file sai cấu trúc | Báo lỗi trên giao diện, **nêu lý do cụ thể** thay vì "has invalid structure" |
| 4 | Tải lên nhiều file, trong đó có một file hỏng | Các file còn lại vẫn nhập được; chỉ file hỏng bị báo |
| 5 | Đặt một file hỏng vào thư mục `xml3176`, kèm vài file tốt | Lượt quét **bỏ qua file hỏng và xử lý tiếp** — trước đây tắc toàn bộ. File hỏng vẫn nằm lại thư mục |
| 6 | Đặt file hợp lệ vào `xml3176tt` với `exportable_tt = false` | Nhập vào nhưng **không** xuất XML |
| 7 | Đặt file hợp lệ vào `xml3176` | Nhập vào **và** xuất XML nếu `export_xml3176_enabled` |
| 8 | Hồ sơ chứa XML16/17/18 nhập bằng tay | Không còn cảnh báo "Unknown XML type" trong log |
| 9 | Xem log sau một lượt quét bình thường | Không có cảnh báo lạ mới |

Mục 5 và 6 là hai mục dễ trôi nhất: mục 5 là thay đổi hành vi có chủ đích, mục 6 là chính
sách riêng của luồng đĩa mà bản gộp phải giữ được.

## Giai đoạn sau

- **Giai đoạn 2** — transaction mỗi hồ sơ; báo lỗi trung thực thay vì nuốt; chỉ xoá file nguồn khi chắc thành công (và chuyển file hỏng sang thư mục riêng); `soluonghoso`; sắp XML1 lên đầu trước khi duyệt `FILEHOSO`.
- **Giai đoạn 3** — đưa import ra khỏi request HTTP.
- **Giai đoạn 4** — một job kiểm lỗi mỗi *(hồ sơ, loại XML)* thay vì mỗi dòng; đưa `deleteErrors()` ra khỏi job.
- **Chưa đủ căn cứ, cần khảo sát**: có phải một file `GIAMDINHHS` được phép chứa nhiều `HOSO` không. Nếu có thì cả hai bản cũ lẫn bản gộp đều đang bỏ im lặng mọi hồ sơ từ thứ hai trở đi.
