# Modal chi tiết XML3176 — cắt N+1 khi tô đỏ dòng lỗi — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Mở modal chi tiết hồ sơ không còn bắn một truy vấn cho mỗi dòng; số truy vấn về ~16 và không phụ thuộc số dòng.

**Architecture:** Một lớp thuần `Xml3176ErrorIndex` dựng chỉ mục lỗi trong bộ nhớ từ collection `Xml3176ErrorResult` vốn đã được nạp sẵn. Controller dựng chỉ mục một lần và truyền vào view; blade tra bảng trong bộ nhớ thay vì gọi `errorResult()`.

**Tech Stack:** Laravel 5.5, PHP 7.4, PHPUnit 6.5, Blade.

## Global Constraints

- Spec: `docs/superpowers/specs/2026-07-28-xml3176-modal-chi-tiet-n1-design.md`
- Cổng test là **`vendor/bin/phpunit --testsuite Unit`** và **chỉ** suite này. Toàn bộ `tests/Feature` đỏ sẵn vì lý do môi trường.
- Mốc trước khi bắt đầu: **260 test xanh, 706 assertion**. Không được giảm.
- **Huy hiệu trên tab phải giữ đúng con số như trước khi sửa.** Đây là ràng buộc cứng, không phải mong muốn. Màn hình đang dùng **ba ngữ nghĩa đếm khác nhau** và việc gộp chúng lại sẽ đổi con số người dùng nhìn thấy:

  | Phương thức | Tab | Đếm cái gì |
  |---|---|---|
  | `demLoi($xml)` | XML1 | số **bản ghi lỗi** |
  | `demTheoStt($items, $xml)` | XML2, 3, 4, 5 | số **dòng** có lỗi khớp `stt` của chính nó |
  | `demTheoXml($items, $xml)` | XML7…XML15 | có lỗi thuộc `$xml` thì **mọi** dòng được tính, không thì 0 |

- XML15 **có** cột `stt` nhưng huy hiệu hiện tại không dùng tới → dùng `demTheoXml`, không phải `demTheoStt`. Đây là chủ ý, không phải nhầm.
- Không sửa quan hệ `errorResult()` trong các model, không đụng màn QD130. Đã ghi thành nợ trong spec.
- Không đụng kích thước HTML hay 6 DataTable phía trình duyệt — chủ đầu tư đã chốt: sửa N+1 trước, đo lại rồi mới tính.
- Comment trong mã nguồn viết tiếng Việt **không dấu**, theo lệ các file xung quanh.
- Sau mỗi task: `php -l` file đã sửa, chạy suite Unit, rồi commit.

---

### Task 1: Lớp chỉ mục lỗi

Toàn bộ giá trị của đợt này nằm ở lớp này, và vì nó không chạm cơ sở dữ liệu nên test phủ được đầy đủ. Viết test trước.

**Files:**
- Create: `app/Services/Xml3176/Xml3176ErrorIndex.php`
- Create: `tests/Unit/Xml3176/Xml3176ErrorIndexTest.php`

**Interfaces:**
- Consumes: không có.
- Produces:
  ```php
  Xml3176ErrorIndex::tu($errors): self          // $errors: Collection|array cac ban ghi co ->xml, ->stt, ->description
  ->coLoi($xml, $stt = null): bool
  ->moTa($xml, $stt = null): string
  ->demLoi($xml): int
  ->demTheoStt($items, $xml): int
  ->demTheoXml($items, $xml): int
  ```

- [ ] **Step 1: Viết test (sẽ đỏ)**

Tạo `tests/Unit/Xml3176/Xml3176ErrorIndexTest.php`:

```php
<?php

namespace Tests\Unit\Xml3176;

use Tests\TestCase;
use Illuminate\Support\Collection;
use App\Services\Xml3176\Xml3176ErrorIndex;

class Xml3176ErrorIndexTest extends TestCase
{
    private function loi($xml, $stt, $moTa)
    {
        return (object) ['xml' => $xml, 'stt' => $stt, 'description' => $moTa];
    }

    private function chiMuc(array $loi)
    {
        return Xml3176ErrorIndex::tu(new Collection($loi));
    }

    /** @test */
    public function co_loi_phan_biet_dung_cap_xml_va_stt()
    {
        $ix = $this->chiMuc([
            $this->loi('XML2', 1, 'Sai ma thuoc'),
            $this->loi('XML3', 2, 'Sai ma dich vu'),
        ]);

        $this->assertTrue($ix->coLoi('XML2', 1));
        $this->assertFalse($ix->coLoi('XML2', 2), 'stt 2 khong co loi o XML2');
        $this->assertFalse($ix->coLoi('XML3', 1), 'khong duoc lan giua XML2 va XML3 cung stt');
        $this->assertTrue($ix->coLoi('XML3', 2));
    }

    /** @test */
    public function co_loi_khong_truyen_stt_hoi_o_muc_xml()
    {
        $ix = $this->chiMuc([$this->loi('XML13', 5, 'Thieu dien bien')]);

        $this->assertTrue($ix->coLoi('XML13'));
        $this->assertFalse($ix->coLoi('XML14'));
    }

    /** @test */
    public function mo_ta_noi_nhieu_loi_bang_dau_cham_phay_dung_thu_tu()
    {
        $ix = $this->chiMuc([
            $this->loi('XML2', 1, 'Loi mot'),
            $this->loi('XML2', 1, 'Loi hai'),
            $this->loi('XML2', 2, 'Loi khac'),
        ]);

        $this->assertEquals('Loi mot; Loi hai', $ix->moTa('XML2', 1));
        $this->assertEquals('Loi khac', $ix->moTa('XML2', 2));
    }

    /** @test */
    public function mo_ta_tra_chuoi_rong_khi_khong_co_loi()
    {
        $ix = $this->chiMuc([$this->loi('XML2', 1, 'Loi mot')]);

        $this->assertSame('', $ix->moTa('XML2', 9));
        $this->assertSame('', $ix->moTa('XML4', 1));
        $this->assertSame('', $this->chiMuc([])->moTa('XML2', 1));
    }

    /** @test */
    public function stt_so_nguyen_va_chuoi_van_khop_nhau()
    {
        // Driver PDO co the tra so nguyen duoi dang chuoi tuy cau hinh.
        $ix = $this->chiMuc([$this->loi('XML2', 7, 'Loi bay')]);

        $this->assertTrue($ix->coLoi('XML2', '7'));
        $this->assertTrue($ix->coLoi('XML2', 7));
        $this->assertEquals('Loi bay', $ix->moTa('XML2', '7'));
    }

    /** @test */
    public function dem_loi_dem_so_ban_ghi_khong_phai_so_dong()
    {
        $ix = $this->chiMuc([
            $this->loi('XML1', 1, 'Loi mot'),
            $this->loi('XML1', 1, 'Loi hai'),
            $this->loi('XML2', 1, 'Khong tinh'),
        ]);

        $this->assertEquals(2, $ix->demLoi('XML1'));
        $this->assertEquals(0, $ix->demLoi('XML5'));
    }

    /** @test */
    public function dem_theo_stt_dem_so_dong_co_loi_khong_phai_tong_so_loi()
    {
        $ix = $this->chiMuc([
            $this->loi('XML2', 1, 'Loi mot'),
            $this->loi('XML2', 1, 'Loi hai'),   // cung dong -> van tinh 1
            $this->loi('XML2', 3, 'Loi ba'),
        ]);

        $items = new Collection([
            (object) ['stt' => 1], (object) ['stt' => 2], (object) ['stt' => 3],
        ]);

        $this->assertEquals(2, $ix->demTheoStt($items, 'XML2'));
    }

    /** @test */
    public function dem_theo_xml_tra_toan_bo_so_dong_khi_co_loi()
    {
        // Giu dung ngu nghia hien tai cua bay tab khong co cot stt: chi hoi
        // "bang nay co loi khong", nen moi dong deu duoc tinh.
        $items = new Collection([(object) ['a' => 1], (object) ['a' => 2]]);

        $coLoi = $this->chiMuc([$this->loi('XML13', 1, 'Loi')]);
        $this->assertEquals(2, $coLoi->demTheoXml($items, 'XML13'));

        $khongLoi = $this->chiMuc([$this->loi('XML14', 1, 'Loi')]);
        $this->assertEquals(0, $khongLoi->demTheoXml($items, 'XML13'));
    }

    /** @test */
    public function chi_muc_rong_khong_no_o_bat_ky_phuong_thuc_nao()
    {
        $ix = $this->chiMuc([]);
        $items = new Collection([(object) ['stt' => 1]]);

        $this->assertFalse($ix->coLoi('XML2'));
        $this->assertFalse($ix->coLoi('XML2', 1));
        $this->assertEquals(0, $ix->demLoi('XML2'));
        $this->assertEquals(0, $ix->demTheoStt($items, 'XML2'));
        $this->assertEquals(0, $ix->demTheoXml($items, 'XML2'));
    }
}
```

- [ ] **Step 2: Chạy test, xác nhận đỏ**

Run: `vendor/bin/phpunit --testsuite Unit --filter Xml3176ErrorIndexTest`
Expected: FAIL — `Class 'App\Services\Xml3176\Xml3176ErrorIndex' not found`

- [ ] **Step 3: Viết lớp**

Tạo `app/Services/Xml3176/Xml3176ErrorIndex.php`:

```php
<?php

namespace App\Services\Xml3176;

/**
 * Chi muc loi XML3176 trong bo nho.
 *
 * Truoc day blade chi tiet hoi CO SO DU LIEU mot lan cho MOI dong (hai luot: mot lan
 * cho huy hieu tren tab, mot lan khi dung than bang), trong khi toan bo tap loi da nam
 * san trong $xml1->Xml3176ErrorResult. Lop nay dung chi muc mot lan roi tra cuu trong
 * bo nho -> khong ton them truy van nao.
 *
 * Lop khong cham co so du lieu: nhan vao mot collection, tra ra gia tri.
 */
class Xml3176ErrorIndex
{
    /** @var array [xml][stt] => [mo ta, ...] */
    private $theoStt = [];

    /** @var array [xml] => so ban ghi loi */
    private $demTheoNhom = [];

    /**
     * @param iterable $errors Cac ban ghi co ->xml, ->stt, ->description
     */
    public static function tu($errors)
    {
        $ix = new self();

        foreach ($errors as $loi) {
            $xml = (string) $loi->xml;
            $stt = (string) $loi->stt;

            $ix->theoStt[$xml][$stt][] = (string) $loi->description;

            $ix->demTheoNhom[$xml] = isset($ix->demTheoNhom[$xml])
                ? $ix->demTheoNhom[$xml] + 1
                : 1;
        }

        return $ix;
    }

    /**
     * Khong truyen $stt: hoi o muc xml ("bang nay co loi nao khong").
     */
    public function coLoi($xml, $stt = null)
    {
        $xml = (string) $xml;

        if ($stt === null) {
            return isset($this->theoStt[$xml]);
        }

        return isset($this->theoStt[$xml][(string) $stt]);
    }

    /**
     * Chuoi mo ta noi bang '; ', tra '' khi khong co loi.
     */
    public function moTa($xml, $stt = null)
    {
        $xml = (string) $xml;

        if ($stt === null) {
            if (!isset($this->theoStt[$xml])) {
                return '';
            }

            $gop = [];
            foreach ($this->theoStt[$xml] as $moTa) {
                $gop = array_merge($gop, $moTa);
            }

            return implode('; ', $gop);
        }

        $stt = (string) $stt;

        return isset($this->theoStt[$xml][$stt])
            ? implode('; ', $this->theoStt[$xml][$stt])
            : '';
    }

    /**
     * So BAN GHI loi thuoc $xml. Dung cho huy hieu tab XML1.
     */
    public function demLoi($xml)
    {
        $xml = (string) $xml;

        return isset($this->demTheoNhom[$xml]) ? $this->demTheoNhom[$xml] : 0;
    }

    /**
     * So DONG co loi khop stt cua chinh no. Dung cho XML2, XML3, XML4, XML5.
     */
    public function demTheoStt($items, $xml)
    {
        $dem = 0;

        foreach ($items as $item) {
            if ($this->coLoi($xml, isset($item->stt) ? $item->stt : null)) {
                $dem++;
            }
        }

        return $dem;
    }

    /**
     * Co loi thuoc $xml thi MOI dong duoc tinh, khong thi 0.
     *
     * Day la ngu nghia hien tai cua bay tab khong co cot stt (XML7..XML14) va cua
     * XML15 (co cot stt nhung huy hieu khong dung toi). Giu nguyen co chu dich:
     * doi cach dem la doi con so nguoi dung nhin thay.
     */
    public function demTheoXml($items, $xml)
    {
        if (!$this->coLoi($xml)) {
            return 0;
        }

        // count() nhan ca mang lan Collection (Collection implement Countable).
        return count($items);
    }
}
```

- [ ] **Step 4: Chạy test, xác nhận xanh**

Run: `vendor/bin/phpunit --testsuite Unit --filter Xml3176ErrorIndexTest`
Expected: PASS (9 test)

- [ ] **Step 5: Kiểm cú pháp và chạy toàn bộ suite**

Run: `php -l app/Services/Xml3176/Xml3176ErrorIndex.php`
Expected: `No syntax errors detected`

Run: `vendor/bin/phpunit --testsuite Unit`
Expected: PASS, **269 test** (260 + 9)

- [ ] **Step 6: Commit**

```bash
git add app/Services/Xml3176/Xml3176ErrorIndex.php tests/Unit/Xml3176/Xml3176ErrorIndexTest.php
git commit -m "feat(xml3176): lop chi muc loi trong bo nho cho man chi tiet"
```

---

### Task 2: Controller dựng chỉ mục + 13 huy hiệu trên tab

**Files:**
- Modify: `app/Http/Controllers/BHYT/BHYTXml3176Controller.php` (`detailXml`)
- Modify: `resources/views/bhyt/xml3176/detail-xml.blade.php`

**Interfaces:**
- Consumes: `Xml3176ErrorIndex::tu()`, `->demLoi()`, `->demTheoStt()`, `->demTheoXml()` (Task 1)
- Produces: biến view `$chiMucLoi` — Task 3 dùng lại.

- [ ] **Step 1: Controller dựng và truyền chỉ mục**

Thêm `use App\Services\Xml3176\Xml3176ErrorIndex;` vào khối `use` đầu file, rồi thay:

```php
    public function detailXml($ma_lk)
    {
        $xml1 = Xml3176Xml1::where('ma_lk', $ma_lk)
        ->firstOrFail();

        return view('bhyt.xml3176.detail-xml',  compact('xml1')); 
    }
```

bằng:

```php
    public function detailXml($ma_lk)
    {
        $xml1 = Xml3176Xml1::with('Xml3176ErrorResult')
        ->where('ma_lk', $ma_lk)
        ->firstOrFail();

        // Dung chi muc MOT lan tu tap loi da nap. Truoc day blade hoi co so du lieu
        // mot lan cho moi dong, hai luot -> hang nghin truy van moi lan mo modal.
        return view('bhyt.xml3176.detail-xml', [
            'xml1'      => $xml1,
            'chiMucLoi' => Xml3176ErrorIndex::tu($xml1->Xml3176ErrorResult),
        ]);
    }
```

- [ ] **Step 2: Huy hiệu XML1**

Trong `detail-xml.blade.php`, thay:

```php
                $errorCountXml = $xml1->Xml3176ErrorResult()
                    ->where('xml', 'XML1')
                    ->count();
```

bằng:

```php
                $errorCountXml = $chiMucLoi->demLoi('XML1');
```

- [ ] **Step 3: Bốn huy hiệu có `stt` — XML2, XML3, XML4, XML5**

Bốn khối này là bốn khối **duy nhất** có `->where('stt', $item->stt)`. Thay từng khối
`@php ... @endphp` bằng một dòng. Ví dụ khối XML2:

```php
                $errorCountXml = $chiMucLoi->demTheoStt($xml1->Xml3176Xml2, 'XML2');
```

Làm tương tự cho XML3, XML4, XML5 với đúng collection và đúng chuỗi xml của nó:

```php
                $errorCountXml = $chiMucLoi->demTheoStt($xml1->Xml3176Xml3, 'XML3');
                $errorCountXml = $chiMucLoi->demTheoStt($xml1->Xml3176Xml4, 'XML4');
                $errorCountXml = $chiMucLoi->demTheoStt($xml1->Xml3176Xml5, 'XML5');
```

- [ ] **Step 4: Tám huy hiệu không có `stt` — XML7, 8, 9, 10, 11, 13, 14, 15**

```php
                $errorCountXml = $chiMucLoi->demTheoXml($xml1->Xml3176Xml7, 'XML7');
                $errorCountXml = $chiMucLoi->demTheoXml($xml1->Xml3176Xml8, 'XML8');
                $errorCountXml = $chiMucLoi->demTheoXml($xml1->Xml3176Xml9, 'XML9');
                $errorCountXml = $chiMucLoi->demTheoXml($xml1->Xml3176Xml10, 'XML10');
                $errorCountXml = $chiMucLoi->demTheoXml($xml1->Xml3176Xml11, 'XML11');
                $errorCountXml = $chiMucLoi->demTheoXml($xml1->Xml3176Xml13, 'XML13');
                $errorCountXml = $chiMucLoi->demTheoXml($xml1->Xml3176Xml14, 'XML14');
                $errorCountXml = $chiMucLoi->demTheoXml($xml1->Xml3176Xml15, 'XML15');
```

**XML15 dùng `demTheoXml` chứ không phải `demTheoStt`** — nó có cột `stt` nhưng huy
hiệu hiện tại không dùng tới, và đổi cách đếm là đổi con số người dùng nhìn thấy.

- [ ] **Step 5: Xác nhận không còn lời gọi truy vấn nào trong file**

Run: `grep -n "errorResult()\|Xml3176ErrorResult()" resources/views/bhyt/xml3176/detail-xml.blade.php`
Expected: **không có kết quả**. (Lưu ý: `$xml1->Xml3176ErrorResult` **không** có dấu
ngoặc vẫn còn ở dòng dựng tab "Lỗi XML" — đó là truy cập collection đã nạp, không phải
truy vấn, giữ nguyên.)

- [ ] **Step 6: Kiểm cú pháp và chạy suite**

Run: `php -l app/Http/Controllers/BHYT/BHYTXml3176Controller.php`
Expected: `No syntax errors detected`

Run: `vendor/bin/phpunit --testsuite Unit`
Expected: PASS, 269 test

- [ ] **Step 7: Commit**

```bash
git add app/Http/Controllers/BHYT/BHYTXml3176Controller.php resources/views/bhyt/xml3176/detail-xml.blade.php
git commit -m "perf(xml3176): huy hieu tab modal chi tiet tra chi muc thay vi truy van tung dong"
```

---

### Task 3: Bốn thân bảng XML2–XML5

Đây là lượt truy vấn thứ hai, và là lượt nặng nhất vì chạy trên chính bốn bảng nhiều dòng.

**Files:**
- Modify: `resources/views/bhyt/xml3176/detail-xml-2.blade.php`
- Modify: `resources/views/bhyt/xml3176/detail-xml-3.blade.php`
- Modify: `resources/views/bhyt/xml3176/detail-xml-4.blade.php`
- Modify: `resources/views/bhyt/xml3176/detail-xml-5.blade.php`
- Create: `tests/Unit/Xml3176/Xml3176DetailBladeTest.php`

**Interfaces:**
- Consumes: `$chiMucLoi` (Task 2), `->moTa()` (Task 1)
- Produces: không có.

- [ ] **Step 1: Viết test canh gác (sẽ đỏ)**

Tạo `tests/Unit/Xml3176/Xml3176DetailBladeTest.php`:

```php
<?php

namespace Tests\Unit\Xml3176;

use Tests\TestCase;

class Xml3176DetailBladeTest extends TestCase
{
    /** @test */
    public function khong_blade_chi_tiet_nao_con_ban_truy_van_trong_vong_lap()
    {
        $thuMuc = resource_path('views/bhyt/xml3176');
        $viPham = [];

        foreach (glob($thuMuc . '/detail-xml*.blade.php') as $file) {
            $noiDung = file_get_contents($file);

            // errorResult() CO dau ngoac = query builder moi moi lan goi, khong bao gio
            // duoc cache. Trong vong lap thi thanh mot truy van cho moi dong.
            if (strpos($noiDung, 'errorResult()') !== false) {
                $viPham[] = basename($file) . ' -> errorResult()';
            }

            // Xml3176ErrorResult() CO dau ngoac cung vay. Ban khong ngoac la truy cap
            // collection da nap, hoan toan hop le.
            if (strpos($noiDung, 'Xml3176ErrorResult()') !== false) {
                $viPham[] = basename($file) . ' -> Xml3176ErrorResult()';
            }
        }

        $this->assertEmpty(
            $viPham,
            "Blade chi tiet ban truy van khi render, se thanh N+1: \n" . implode("\n", $viPham)
        );
    }
}
```

- [ ] **Step 2: Chạy test, xác nhận đỏ và đỏ đúng bốn file**

Run: `vendor/bin/phpunit --testsuite Unit --filter Xml3176DetailBladeTest`
Expected: FAIL, liệt kê `detail-xml-2/3/4/5.blade.php -> errorResult()`
(Nếu `detail-xml.blade.php` cũng bị liệt kê thì Task 2 chưa xong — quay lại làm nốt.)

- [ ] **Step 3: Sửa `detail-xml-2.blade.php`**

Thay:

```php
                                @php
                                    $errorDescriptions = $value_xml2
                                    ->errorResult()
                                    ->where('stt', $value_xml2->stt)
                                    ->pluck('description')
                                    ->implode('; ');
                                @endphp
```

bằng:

```php
                                @php
                                    $errorDescriptions = $chiMucLoi->moTa('XML2', $value_xml2->stt);
                                @endphp
```

- [ ] **Step 4: Sửa `detail-xml-3.blade.php`**

```php
                                @php
                                    $errorDescriptions = $chiMucLoi->moTa('XML3', $value_xml3->stt);
                                @endphp
```

- [ ] **Step 5: Sửa `detail-xml-4.blade.php`**

```php
                                @php
                                    $errorDescriptions = $chiMucLoi->moTa('XML4', $value_xml4->stt);
                                @endphp
```

- [ ] **Step 6: Sửa `detail-xml-5.blade.php`**

```php
                                @php
                                    $errorDescriptions = $chiMucLoi->moTa('XML5', $value_xml5->stt);
                                @endphp
```

Lưu ý tên biến vòng lặp khác nhau ở từng file (`$value_xml2` … `$value_xml5`) — dùng
đúng tên của file đang sửa, và chuỗi xml phải khớp số hiệu file.

- [ ] **Step 7: Chạy test canh gác, xác nhận xanh**

Run: `vendor/bin/phpunit --testsuite Unit --filter Xml3176DetailBladeTest`
Expected: PASS

- [ ] **Step 8: Chạy toàn bộ suite**

Run: `vendor/bin/phpunit --testsuite Unit`
Expected: PASS, **270 test**

- [ ] **Step 9: Commit**

```bash
git add resources/views/bhyt/xml3176/detail-xml-2.blade.php resources/views/bhyt/xml3176/detail-xml-3.blade.php resources/views/bhyt/xml3176/detail-xml-4.blade.php resources/views/bhyt/xml3176/detail-xml-5.blade.php tests/Unit/Xml3176/Xml3176DetailBladeTest.php
git commit -m "perf(xml3176): than bang XML2-5 tra chi muc thay vi truy van tung dong"
```

---

## Nghiệm thu thủ công (bắt buộc)

DB dev trống cả bốn bảng `xml3176_*`, nên không đo được trước/sau tại chỗ.

**Trước khi deploy: chụp màn hình modal của một hồ sơ có lỗi, thấy rõ các huy hiệu.**
Không có ảnh này thì mục 4 không kiểm được, và đó là mục dễ trôi nhất.

| # | Việc | Mong đợi |
|---|---|---|
| 1 | Mở modal một hồ sơ nội trú nhiều dòng | Thời gian tải giảm rõ rệt |
| 2 | Dòng có lỗi ở tab XML2, XML3, XML4, XML5 | Vẫn tô đỏ (`highlight-red`) |
| 3 | Rê chuột lên dòng đỏ | Tooltip hiện đúng mô tả; nhiều lỗi vẫn nối bằng `; ` |
| 4 | So huy hiệu từng tab với ảnh chụp trước khi sửa | **Giống hệt từng con số** |
| 5 | Hồ sơ không có lỗi nào | Không tab nào hiện huy hiệu, không dòng nào đỏ |
| 6 | Hồ sơ chỉ có lỗi ở XML13 (bảng không có `stt`) | Tab XML13 hiện huy hiệu bằng đúng số dòng của bảng, như trước |

## Nợ kỹ thuật ghi nhận, không làm trong đợt này

1. **Kích thước HTML và 6 DataTable phía trình duyệt.** Chủ đầu tư đã chốt: sửa N+1 trước, đo lại bằng tab Network, rồi mới quyết. Nếu sau đợt này vẫn chậm thì đây là chỗ tiếp theo.
2. **`whereColumn('stt', 'stt')`** trong `errorResult()` của Xml3176Xml2/3/4/5/15 so cột `stt` của bảng lỗi với chính nó — luôn đúng, một no-op. Sau đợt này `errorResult()` không còn được gọi ở đâu trong luồng XML3176. Cùng lỗi có ở cả 12 model `Qd130*`, nơi **vẫn đang được dùng**.
3. **Màn QD130** có cấu trúc blade tương tự và nhiều khả năng mắc đúng N+1 này. Chưa kiểm.
4. Các nợ của đợt trước vẫn còn: `exportXml()` dựng 2000 file trong một request; các endpoint xuất nhận thiếu bộ lọc; `config/datatables.php` đặt `'escape' => '*'` cho toàn app.
