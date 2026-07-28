# Import XML3176 — Giai đoạn 2: an toàn dữ liệu — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Nhập một hồ sơ là thao tác được-ăn-cả-ngã-về-không: hỏng ở đâu cũng quay lui sạch và báo đúng lý do, dữ liệu cũ còn nguyên.

**Architecture:** `nhapTuChuoi()` bọc một hồ sơ trong `DB::transaction`; 15 khối `catch` trong `Xml3176Service` ném lại lỗi thay vì nuốt; hai mẩu logic tách thành hàm thuần để kiểm thử được; file hỏng chuyển sang thư mục `loi/`.

**Tech Stack:** Laravel 5.5, PHP 7.4, PHPUnit 6.5, MySQL (InnoDB), queue driver `database`.

## Global Constraints

- Spec: `docs/superpowers/specs/2026-07-28-xml3176-import-pha-2-an-toan-du-lieu-design.md`
- Cổng test: **`vendor/bin/phpunit --testsuite Unit`**. Mốc: **295 test xanh**.
- **Thứ tự task dưới đây là bắt buộc.** Nếu để 15 khối `catch` ném lại lỗi *trước khi* có transaction thì một hồ sơ hỏng giữa chừng vừa mất dữ liệu vừa dừng — tệ hơn hiện tại. Transaction phải vào trước.
- Chủ đầu tư đã chốt phương án **chặt**: một dòng hỏng thì từ chối cả hồ sơ.
- **Không đụng hai khối `catch` ngoài luồng import**: `deleteXml3176XmlAndError()` dòng 74 và `submitXmlToBHYT()` dòng 1886.
- Không đụng: import chạy đồng bộ trong request (giai đoạn 3); một job kiểm lỗi mỗi dòng (giai đoạn 4); việc chỉ xử lý `HOSO` đầu tiên (chưa đủ căn cứ).
- Comment mã nguồn viết tiếng Việt **không dấu**.
- Sau mỗi task: `php -l` file đã sửa, chạy suite Unit, commit.

---

### Task 1: Hai hàm thuần

Tách hai mẩu logic ra khỏi `nhapTuChuoi()` để kiểm thử được — thân hàm đó cần cơ sở dữ liệu nên không test trực tiếp được.

**Files:**
- Modify: `app/Services/Xml3176/Xml3176Importer.php`
- Create: `tests/Unit/Xml3176/Xml3176ImporterThuanTest.php`

**Interfaces:**
- Consumes: không có.
- Produces:
  ```php
  Xml3176Importer::soLuongHoSo($xmldata): int
  Xml3176Importer::sapXml1LenDau(array $danhSachLoai): array   // tra ve mang CHI SO
  ```

- [ ] **Step 1: Viết test (sẽ đỏ)**

Tạo `tests/Unit/Xml3176/Xml3176ImporterThuanTest.php`:

```php
<?php

namespace Tests\Unit\Xml3176;

use Tests\TestCase;
use App\Services\Xml3176\Xml3176Importer;

class Xml3176ImporterThuanTest extends TestCase
{
    private function xml($ben_trong)
    {
        return simplexml_load_string('<GIAMDINHHS>' . $ben_trong . '</GIAMDINHHS>');
    }

    /** @test */
    public function so_luong_ho_so_doc_dung_gia_tri_that()
    {
        // Ban cu dung count() tren node la nen LUON ra 1, bat ke gia tri that.
        $x = $this->xml('<THONGTINHOSO><SOLUONGHOSO>37</SOLUONGHOSO></THONGTINHOSO>');

        $this->assertSame(37, Xml3176Importer::soLuongHoSo($x));
    }

    /** @test */
    public function so_luong_ho_so_tra_khong_khi_thieu_hoac_rong()
    {
        $this->assertSame(0, Xml3176Importer::soLuongHoSo($this->xml('<THONGTINHOSO></THONGTINHOSO>')));
        $this->assertSame(0, Xml3176Importer::soLuongHoSo($this->xml('')));
        $this->assertSame(0, Xml3176Importer::soLuongHoSo(
            $this->xml('<THONGTINHOSO><SOLUONGHOSO></SOLUONGHOSO></THONGTINHOSO>')
        ));
    }

    /** @test */
    public function sap_xml1_len_dau_va_giu_thu_tu_tuong_doi_phan_con_lai()
    {
        // deleteExistingXml3176 chi chay khi gap XML1. Neu XML2 duoc ghi truoc do thi
        // no bi xoa ngay sau - im lang.
        $kq = Xml3176Importer::sapXml1LenDau(['XML2', 'XML3', 'XML1', 'XML5']);

        $this->assertEquals([2, 0, 1, 3], $kq);
    }

    /** @test */
    public function sap_xml1_len_dau_giu_nguyen_khi_xml1_da_dung_dau()
    {
        $kq = Xml3176Importer::sapXml1LenDau(['XML1', 'XML2', 'XML3']);

        $this->assertEquals([0, 1, 2], $kq);
    }

    /** @test */
    public function sap_xml1_len_dau_giu_nguyen_khi_khong_co_xml1()
    {
        $kq = Xml3176Importer::sapXml1LenDau(['XML2', 'XML3']);

        $this->assertEquals([0, 1], $kq);
    }

    /** @test */
    public function sap_xml1_len_dau_khong_no_voi_mang_rong()
    {
        $this->assertEquals([], Xml3176Importer::sapXml1LenDau([]));
    }

    /** @test */
    public function sap_xml1_len_dau_chi_dua_xml1_dau_tien_len()
    {
        // File di thuong co hai XML1: van chi mot cai len dau, cai kia giu thu tu cu.
        $kq = Xml3176Importer::sapXml1LenDau(['XML2', 'XML1', 'XML1']);

        $this->assertEquals([1, 0, 2], $kq);
    }
}
```

- [ ] **Step 2: Chạy test, xác nhận đỏ**

Run: `vendor/bin/phpunit --testsuite Unit --filter Xml3176ImporterThuanTest`
Expected: FAIL — `Call to undefined method ... soLuongHoSo()`

- [ ] **Step 3: Viết hai hàm**

Thêm vào `Xml3176Importer`, ngay sau `handlerCho()`:

```php
    /**
     * So luong ho so khai trong phong bi.
     *
     * Ban cu dung count($xmldata->THONGTINHOSO->SOLUONGHOSO) - count() tren mot node la
     * dem so phan tu con nen LUON ra 1, bat ke gia tri that la bao nhieu.
     */
    public static function soLuongHoSo($xmldata)
    {
        if (!isset($xmldata->THONGTINHOSO->SOLUONGHOSO)) {
            return 0;
        }

        return (int) (string) $xmldata->THONGTINHOSO->SOLUONGHOSO;
    }

    /**
     * Thu tu duyet FILEHOSO, dua XML1 len dau.
     *
     * deleteExistingXml3176() chi chay khi gap XML1. Neu mot file liet ke XML2 truoc
     * XML1 thi cac dong XML2 vua ghi bi xoa ngay sau do - im lang.
     *
     * @param array $danhSachLoai Cac chuoi LOAIHOSO theo dung thu tu trong file
     * @return array Mang CHI SO theo thu tu can duyet
     */
    public static function sapXml1LenDau(array $danhSachLoai)
    {
        $dau = [];
        $sau = [];

        foreach ($danhSachLoai as $i => $loai) {
            // Chi dua XML1 DAU TIEN len; cai thu hai (neu co) giu thu tu cu.
            if ($loai === 'XML1' && empty($dau)) {
                $dau[] = $i;
            } else {
                $sau[] = $i;
            }
        }

        return array_merge($dau, $sau);
    }
```

- [ ] **Step 4: Chạy test, xác nhận xanh**

Run: `vendor/bin/phpunit --testsuite Unit --filter Xml3176ImporterThuanTest`
Expected: PASS (7 test)

- [ ] **Step 5: Kiểm cú pháp và chạy toàn bộ suite**

Run: `php -l app/Services/Xml3176/Xml3176Importer.php`
Run: `vendor/bin/phpunit --testsuite Unit`
Expected: PASS, **302 test**

- [ ] **Step 6: Commit**

```bash
git add app/Services/Xml3176/Xml3176Importer.php tests/Unit/Xml3176/Xml3176ImporterThuanTest.php
git commit -m "feat(xml3176): hai ham thuan cho so luong ho so va thu tu duyet FILEHOSO"
```

---

### Task 2: Transaction bọc một hồ sơ

Phải vào **trước** Task 3. Sau task này, ngoại lệ thoát ra từ tầng lưu sẽ quay lui sạch — nhưng 15 khối `catch` vẫn đang nuốt nên thực tế chưa mấy ngoại lệ nào thoát ra. Đó là chủ đích: transaction có mặt sẵn trước khi mở van.

**Files:**
- Modify: `app/Services/Xml3176/Xml3176Importer.php`
- Create: `tests/Unit/Xml3176/Xml3176ImporterTransactionTest.php`

**Interfaces:**
- Consumes: `soLuongHoSo()`, `sapXml1LenDau()` (Task 1)
- Produces: không có.

- [ ] **Step 1: Viết hàng rào nguồn (sẽ đỏ)**

Tạo `tests/Unit/Xml3176/Xml3176ImporterTransactionTest.php`:

```php
<?php

namespace Tests\Unit\Xml3176;

use Tests\TestCase;

class Xml3176ImporterTransactionTest extends TestCase
{
    /** @test */
    public function nhap_mot_ho_so_phai_nam_trong_transaction()
    {
        // deleteExistingXml3176() xoa 13 bang roi moi ghi lai tung phan. Khong co
        // transaction thi dut giua chung la mat ca du lieu cu lan moi.
        $src = file_get_contents(app_path('Services/Xml3176/Xml3176Importer.php'));

        $this->assertContains('DB::transaction', $src,
            'nhapTuChuoi khong con boc transaction - ho so co the mat du lieu cu lan moi');
    }

    /** @test */
    public function day_job_kiem_tra_va_xuat_nam_ngoai_transaction()
    {
        // checkXml3176Complete/exportXml3176 chi day job. Dat SAU commit de rollback
        // khong de lai job mo coi tro toi du lieu khong ton tai.
        $src = file_get_contents(app_path('Services/Xml3176/Xml3176Importer.php'));

        $viTriTransaction = strpos($src, 'DB::transaction');
        $viTriCheck       = strpos($src, 'checkXml3176Complete');
        $viTriExport      = strpos($src, 'exportXml3176');

        $this->assertNotFalse($viTriTransaction);
        $this->assertNotFalse($viTriCheck);
        $this->assertNotFalse($viTriExport);

        $this->assertGreaterThan($viTriTransaction, $viTriCheck,
            'checkXml3176Complete phai nam sau khoi transaction');
        $this->assertGreaterThan($viTriTransaction, $viTriExport,
            'exportXml3176 phai nam sau khoi transaction');
    }
}
```

- [ ] **Step 2: Chạy test, xác nhận đỏ**

Run: `vendor/bin/phpunit --testsuite Unit --filter Xml3176ImporterTransactionTest`
Expected: FAIL — chưa có `DB::transaction`

- [ ] **Step 3: Thêm `use DB;`**

Trong `Xml3176Importer`, thêm vào khối `use` đầu file:

```php
use DB;
```

- [ ] **Step 4: Viết lại phần thân từ `$soluonghoso` tới hết**

Thay đoạn từ dòng tính `$soluonghoso` cho tới `return Xml3176ImportResult::thanhCong(...)` bằng:

```php
        $soluonghoso = self::soLuongHoSo($xmldata);

        if (!isset($xmldata->THONGTINHOSO->DANHSACHHOSO->HOSO->FILEHOSO)) {
            return Xml3176ImportResult::thatBai('Khong tim thay FILEHOSO trong file');
        }

        // Gom thanh mang de sap duoc thu tu: XML1 phai duoc xu ly TRUOC, vi
        // deleteExistingXml3176() chi chay khi gap no.
        $danhSachFile = [];
        $danhSachLoai = [];

        foreach ($xmldata->THONGTINHOSO->DANHSACHHOSO->HOSO->FILEHOSO as $file_hs) {
            $danhSachFile[] = $file_hs;
            $danhSachLoai[] = (string) $file_hs->LOAIHOSO;
        }

        $ma_lk = null;
        $processedFileTypes = [];

        try {
            // Mot ho so = mot transaction. Hong o dau cung quay lui sach, va vi
            // deleteExistingXml3176() nam trong day nen DU LIEU CU CON NGUYEN.
            //
            // Job kiem loi tung dong duoc dispatch BEN TRONG day la co chu dich:
            // hang doi dung driver database tren cung connection nen rollback xoa
            // luon cac job do.
            DB::transaction(function () use (
                $danhSachFile, $danhSachLoai, $macskcb, $soluonghoso, &$ma_lk, &$processedFileTypes
            ) {
                foreach (self::sapXml1LenDau($danhSachLoai) as $i) {
                    $file_hs  = $danhSachFile[$i];
                    $fileType = $danhSachLoai[$i];

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
                        throw new \RuntimeException('Noi dung ' . $fileType . ' khong doc duoc');
                    }

                    if ($fileType === 'XML1') {
                        $expectedStructure = XmlStructures::$expectedStructures3176[$fileType] ?? [];

                        if (!empty($expectedStructure) && !validateDataStructure($data, $expectedStructure)) {
                            throw new \RuntimeException('Sai cau truc du lieu ' . $fileType);
                        }

                        $ma_lk = (string) $data->MA_LK;
                        $this->xml3176Service->deleteExistingXml3176($ma_lk);
                    }

                    $processedFileTypes[] = $fileType;
                    $this->xml3176Service->{$handler}($data, $fileType);
                }

                if ($ma_lk === null || empty($processedFileTypes)) {
                    throw new \RuntimeException('Khong tim thay du lieu ho so hop le trong file');
                }

                $this->xml3176Service->storeXml3176Information($ma_lk, $macskcb, 'import', $soluonghoso);
            });
        } catch (\Exception $e) {
            \Log::error('Import that bai' . ($ma_lk ? ' (' . $ma_lk . ')' : '') . ': ' . $e->getMessage());

            return Xml3176ImportResult::thatBai($e->getMessage());
        }

        // Sau commit: hai ham nay chi day job, dat o day de rollback khong de lai
        // job mo coi tro toi du lieu khong ton tai.
        if (!config('organization.xml_3176_not_check', false)) {
            $this->xml3176Service->checkXml3176Complete($ma_lk);
        }

        if ($choPhepXuat && config('xml3176.export_xml3176_enabled')) {
            $this->xml3176Service->exportXml3176($ma_lk);
        }

        return Xml3176ImportResult::thanhCong($ma_lk, $processedFileTypes);
```

- [ ] **Step 5: Chạy test, xác nhận xanh**

Run: `vendor/bin/phpunit --testsuite Unit --filter Xml3176ImporterTransactionTest`
Expected: PASS (2 test)

- [ ] **Step 6: Kiểm cú pháp và chạy toàn bộ suite**

Run: `php -l app/Services/Xml3176/Xml3176Importer.php`
Run: `vendor/bin/phpunit --testsuite Unit`
Expected: PASS, **304 test**. Các test `Xml3176ImporterParseTest` phải **vẫn xanh** — chuỗi hỏng, thiếu MACSKCB, không có FILEHOSO đều thoát trước khi vào transaction.

- [ ] **Step 7: Commit**

```bash
git add app/Services/Xml3176/Xml3176Importer.php tests/Unit/Xml3176/Xml3176ImporterTransactionTest.php
git commit -m "fix(xml3176): boc mot ho so trong transaction, XML1 xu ly truoc"
```

---

### Task 3: 15 khối `catch` ném lại lỗi

**Files:**
- Modify: `app/Services/Xml3176Service.php` (15 chỗ)
- Create: `tests/Unit/Xml3176/Xml3176ServiceKhongNuotLoiTest.php`

**Interfaces:**
- Consumes: transaction từ Task 2.
- Produces: không có.

- [ ] **Step 1: Viết hàng rào nguồn (sẽ đỏ)**

Tạo `tests/Unit/Xml3176/Xml3176ServiceKhongNuotLoiTest.php`:

```php
<?php

namespace Tests\Unit\Xml3176;

use Tests\TestCase;

class Xml3176ServiceKhongNuotLoiTest extends TestCase
{
    /** @test */
    public function moi_khoi_catch_trong_cac_ham_luu_deu_nem_lai_loi()
    {
        // Nuot loi o day nghia la: mot dong hong bi bo, cac dong khac van ghi, va nguoi
        // dung nhan "processed successfully" trong khi ho so thieu du lieu. Voi so lieu
        // thanh toan BHYT thi ho so thieu dong duoc xuat len BHXH la sai quyet toan.
        $lines = file(app_path('Services/Xml3176Service.php'));

        $fn = '';
        $viPham = [];

        foreach ($lines as $i => $l) {
            if (preg_match('/^    (public|protected|private) function (\w+)/', $l, $m)) {
                $fn = $m[2];
            }

            if (strpos($l, 'catch (\Exception $e) {') === false) {
                continue;
            }

            if (strpos($fn, 'storeXml3176') !== 0) {
                continue;   // ngoai luong import - khong thuoc pham vi
            }

            $than = '';
            for ($j = $i + 1; $j < min($i + 8, count($lines)); $j++) {
                if (trim($lines[$j]) === '}') {
                    break;
                }
                $than .= $lines[$j];
            }

            if (strpos($than, 'throw') === false) {
                $viPham[] = 'dong ' . ($i + 1) . ' trong ' . $fn;
            }
        }

        $this->assertEmpty(
            $viPham,
            "Cac khoi catch sau van nuot loi:\n" . implode("\n", $viPham)
        );
    }

    /** @test */
    public function hang_rao_that_su_dem_duoc_cac_khoi_catch()
    {
        // Chung minh phep kiem tren khong rong: neu regex hong thi no se khong tim thay
        // khoi nao va tu dong xanh ma khong kiem gi.
        $src = file_get_contents(app_path('Services/Xml3176Service.php'));

        $this->assertGreaterThanOrEqual(
            15,
            substr_count($src, 'catch (\Exception $e) {'),
            'Khong con du 15 khoi catch trong luong import - hang rao co the dang vo dung'
        );
    }
}
```

- [ ] **Step 2: Chạy test, xác nhận đỏ và liệt kê đủ 15 chỗ**

Run: `vendor/bin/phpunit --testsuite Unit --filter Xml3176ServiceKhongNuotLoiTest`
Expected: FAIL, liệt kê 15 dòng: 201, 276, 361, 410, 456, 540, 599, 652, 724, 774, 828, 896, 950, 1019, 1074

- [ ] **Step 3: Thêm `throw $e;` vào 15 khối**

Cả 15 khối dùng **đúng một khuôn ba dòng** (đã kiểm chứng — chỉ khác thụt lề và thông điệp):

```php
        } catch (\Exception $e) {
            \Log::error('Error in <ten>: ' . $e->getMessage());
        }
```

Thêm một dòng `throw $e;` ngay sau dòng `\Log::error(...)`, **cùng thụt lề với dòng đó**:

```php
        } catch (\Exception $e) {
            \Log::error('Error in <ten>: ' . $e->getMessage());
            throw $e;
        }
```

Làm ở đúng 15 chỗ: dòng 201, 276, 361, 410, 456, 540, 599, 652, 724, 774, 828, 896, 950,
1019, 1074 (số dòng của bản trước khi sửa; mỗi lần chèn sẽ đẩy các dòng sau xuống một).

**Không đụng** dòng 74 (`deleteXml3176XmlAndError`) và 1886 (`submitXmlToBHYT`).

- [ ] **Step 4: Chạy test, xác nhận xanh**

Run: `vendor/bin/phpunit --testsuite Unit --filter Xml3176ServiceKhongNuotLoiTest`
Expected: PASS (2 test)

- [ ] **Step 5: Xác nhận không đụng nhầm hai khối ngoài phạm vi**

Run: `sed -n '74,78p;1890,1896p' app/Services/Xml3176Service.php`
Expected: cả hai khối **không** có `throw $e;`

- [ ] **Step 6: Kiểm cú pháp và chạy toàn bộ suite**

Run: `php -l app/Services/Xml3176Service.php`
Run: `vendor/bin/phpunit --testsuite Unit`
Expected: PASS, **306 test**

- [ ] **Step 7: Commit**

```bash
git add app/Services/Xml3176Service.php tests/Unit/Xml3176/Xml3176ServiceKhongNuotLoiTest.php
git commit -m "fix(xml3176): 15 khoi catch trong luong import nem lai loi thay vi nuot"
```

---

### Task 4: File hỏng chuyển sang thư mục `loi/`

**Files:**
- Modify: `app/Console/Commands/XML3176Import.php`
- Create: `tests/Unit/Xml3176/Xml3176ImportThuMucLoiTest.php`

**Interfaces:**
- Consumes: `Xml3176ImportResult::$thanhCong` (giai đoạn 1)
- Produces: quy ước thư mục `loi/` trên cùng disk.

- [ ] **Step 1: Viết hàng rào nguồn (sẽ đỏ)**

Tạo `tests/Unit/Xml3176/Xml3176ImportThuMucLoiTest.php`:

```php
<?php

namespace Tests\Unit\Xml3176;

use Tests\TestCase;

class Xml3176ImportThuMucLoiTest extends TestCase
{
    /** @test */
    public function file_hong_duoc_chuyen_sang_thu_muc_loi()
    {
        $src = file_get_contents(app_path('Console/Commands/XML3176Import.php'));

        $this->assertContains(self::THU_MUC_LOI, $src,
            'File hong van nam lai thu muc quet, se duoc thu lai moi 3 giay');
        $this->assertContains('->move(', $src);
    }

    /** @test */
    public function thu_muc_loi_bi_loai_khoi_luot_quet()
    {
        // Storage::allFiles() quet DE QUY. Khong bo qua loi/ thi file hong lai duoc
        // nhat len lan nua - dung cai vong lap ma task nay muon cat.
        $src = file_get_contents(app_path('Console/Commands/XML3176Import.php'));

        $this->assertContains('starts_with', $src,
            'Khong thay phep loai thu muc loi khoi luot quet');
    }

    const THU_MUC_LOI = 'loi/';
}
```

- [ ] **Step 2: Chạy test, xác nhận đỏ**

Run: `vendor/bin/phpunit --testsuite Unit --filter Xml3176ImportThuMucLoiTest`
Expected: FAIL (2 test)

- [ ] **Step 3: Sửa vòng lặp trong `importFilesFromDisk`**

Thêm hằng vào lớp `XML3176Import`:

```php
    /** Thu muc con chua file nhap that bai, tren cung disk. */
    const THU_MUC_LOI = 'loi/';
```

Ngay đầu thân vòng lặp `foreach ($files as $file)`, trước `try`:

```php
            // Storage::allFiles() quet DE QUY nen phai loai thu muc loi ra, khong thi
            // file hong lai duoc nhat len o luot sau.
            if (starts_with($file, self::THU_MUC_LOI)) {
                continue;
            }
```

Thay nhánh thất bại:

```php
                if (!$kq->thanhCong) {
                    // Giu nguyen hanh vi hien tai: KHONG xoa file hong, de con du lieu
                    // ma dieu tra. Giai doan 2 chuyen no sang thu muc rieng.
                    \Log::error('Import that bai ' . $disk . '/' . $file . ': ' . $kq->lyDoThatBai);
                    continue;
                }
```

bằng:

```php
                if (!$kq->thanhCong) {
                    // Chuyen sang thu muc loi thay vi de nguyen cho cu: de nguyen thi
                    // moi 3 giay lai thu lai va lai ghi mot dong log.
                    \Log::error('Import that bai ' . $disk . '/' . $file . ': ' . $kq->lyDoThatBai);

                    $dich = self::THU_MUC_LOI . basename($file);

                    if (Storage::disk($disk)->exists($dich)) {
                        Storage::disk($disk)->delete($dich);
                    }

                    Storage::disk($disk)->move($file, $dich);
                    continue;
                }
```

- [ ] **Step 4: Chạy test, xác nhận xanh**

Run: `vendor/bin/phpunit --testsuite Unit --filter Xml3176ImportThuMucLoiTest`
Expected: PASS (2 test)

- [ ] **Step 5: Kiểm cú pháp và chạy toàn bộ suite**

Run: `php -l app/Console/Commands/XML3176Import.php`
Run: `vendor/bin/phpunit --testsuite Unit`
Expected: PASS, **308 test**

- [ ] **Step 6: Commit**

```bash
git add app/Console/Commands/XML3176Import.php tests/Unit/Xml3176/Xml3176ImportThuMucLoiTest.php
git commit -m "fix(xml3176): file nhap that bai chuyen sang thu muc loi thay vi thu lai vo han"
```

---

## Nghiệm thu thủ công (bắt buộc)

DB dev trống cả bốn bảng `xml3176_*` nên không chạy được import thật tại chỗ.

**Chuẩn bị: giữ bản sao vài file XML thật, và ghi lại số dòng từng bảng con của một hồ sơ
đã có** trước khi thử.

| # | Việc | Mong đợi |
|---|---|---|
| 1 | Nhập lại một hồ sơ đã có, bằng file hợp lệ | Số dòng từng bảng con khớp như cũ |
| 2 | Nhập một file hợp lệ mới | `xml3176_informations.soluonghoso` mang **giá trị thật**, không phải 1 |
| 3 | Nhập file có một dòng sai kiểu dữ liệu | **Từ chối cả hồ sơ**, báo lý do; dữ liệu cũ của hồ sơ đó **còn nguyên** |
| 4 | Sau mục 3, kiểm bảng `jobs` | Không có job kiểm lỗi mồ côi của hồ sơ vừa bị từ chối |
| 5 | Đặt file hỏng vào thư mục `xml3176` | File được chuyển sang `xml3176/loi/`, lượt quét sau **không** nhặt lại |
| 6 | Đặt vài file tốt cùng file hỏng | File tốt nhập bình thường, chỉ file hỏng bị chuyển đi |
| 7 | Đặt hai file hỏng **trùng tên** vào thư mục quét, cách nhau vài lượt | Không nổ; file sau ghi đè file trước trong `loi/` |
| 8 | **Theo dõi log một ngày sau khi triển khai** | Đếm số hồ sơ bị từ chối |

**Mục 3 và mục 8 quan trọng nhất.** Mục 3 chứng minh transaction thật sự bảo vệ dữ liệu
cũ. Mục 8 là cách duy nhất đo được rủi ro đã chấp nhận: nếu con số bị từ chối cao bất
thường thì đó là **dữ liệu vốn đã hỏng nay mới lộ ra**, không phải đợt sửa này gây ra.
Cần xem log trước khi kết luận.

## Giai đoạn sau

- **Giai đoạn 3** — đưa import ra khỏi request HTTP.
- **Giai đoạn 4** — một job kiểm lỗi mỗi *(hồ sơ, loại XML)* thay vì mỗi dòng; đưa `deleteErrors()` ra khỏi job.
- **Chưa đủ căn cứ**: một file `GIAMDINHHS` có được chứa nhiều `HOSO` không. Nếu có thì mọi hồ sơ từ thứ hai trở đi đang bị bỏ im lặng.
- **Ghi nhận**: `catch (Exception $e)` thiếu gạch chéo ngược ở `deleteXml3176XmlAndError()` dòng 74 — không bao giờ khớp, ngoại lệ vẫn thoát ra thay vì trả `false`.
