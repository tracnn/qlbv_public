# Cửa sổ quét order-check — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Thời gian mỗi lượt quét trở thành hằng số thay vì tỉ lệ với khoảng tồn, và bỏ qua nguồn quét khi danh mục của nó rỗng.

**Architecture:** Thêm chặn trên `id <= mốc + cửa_sổ` vào ba truy vấn theo mốc `id`, để Oracle chỉ phải sắp xếp tối đa một cửa sổ thay vì toàn bộ tồn. Quy tắc đẩy mốc — phần dễ sai nhất — tách thành hàm thuần `CuaSoQuet`. Riêng bộ quét giới hạn dịch vụ bỏ hẳn truy vấn HIS khi danh mục rỗng, nhưng vẫn đẩy mốc để không tích tồn.

**Tech Stack:** Laravel 5.5.50, PHP 7.4, PHPUnit 6.5, Oracle qua kết nối `HISPro` (chỉ đọc).

## Global Constraints

- Cổng kiểm thử: `vendor/bin/phpunit --testsuite Unit`. **KHÔNG** chạy `tests/Feature` — đỏ sẵn vì lý do môi trường, không liên quan.
- Comment trong code PHP viết tiếng Việt **không dấu**.
- Kết nối Oracle tên `HISPro`, **CHỈ ĐỌC**. Không ghi bất cứ gì sang Oracle.
- **Không đụng** `fetchServiceRequests()` — nguồn đó dùng mốc theo `modify_time`, ngữ nghĩa cửa sổ khác hẳn và chưa được khảo sát.
- **Không** đổi `limit` mỗi lượt (đang 500).
- **Không** bật/tắt quy tắc nào, **không** nhập dữ liệu vào `order_check_ref_service_restriction`.
- **Không** đụng cơ chế watermark của XML3176.
- Cửa sổ mặc định **50.000**, khoá config `scan_id_window`. Giá trị **0 nghĩa là không chặn** — giữ đường lui về hành vi cũ.
- `CuaSoQuet::mocMoi()` **không bao giờ trả về giá trị nhỏ hơn mốc hiện tại**.
- Kiểm danh mục rỗng bằng `exists()`, không `count()`.

## Cấu trúc tệp

| Tệp | Trách nhiệm |
| --- | --- |
| `app/Services/OrderCheck/Support/CuaSoQuet.php` (tạo) | Hàm thuần: cuối cửa sổ, và mốc mới sau một lượt |
| `config/order_check.php` (sửa) | Khoá `scan_id_window` |
| `app/Services/OrderCheck/HisOrderSource.php` (sửa) | Chặn trên cho 3 truy vấn theo mốc `id` |
| `app/Services/OrderCheck/Scanners/ServiceRestrictionScanner.php` (sửa) | Dùng `CuaSoQuet`; bỏ qua khi danh mục rỗng |
| `app/Services/OrderCheck/Scanners/MedicineScanner.php` (sửa) | Dùng `CuaSoQuet` |
| `app/Services/OrderCheck/Scanners/InteractionLogScanner.php` (sửa) | Dùng `CuaSoQuet` |
| `tests/Unit/CuaSoQuetTest.php` (tạo) | Kiểm hàm thuần |
| `tests/Unit/HisOrderSourceCuaSoTest.php` (tạo) | Chặn việc gỡ chặn trên, và chặn việc áp nhầm cho nguồn theo thời gian |

---

### Task 1: Hàm thuần CuaSoQuet

**Files:**
- Create: `app/Services/OrderCheck/Support/CuaSoQuet.php`
- Test: `tests/Unit/CuaSoQuetTest.php`

**Interfaces:**
- Consumes: không có gì từ task khác.
- Produces: `App\Services\OrderCheck\Support\CuaSoQuet::ketThuc($moc, $cuaSo)` trả `int`, và `CuaSoQuet::mocMoi($moc, $soDongLay, $limit, $maxIdTrongLo, $cuoiCuaSo)` trả `int`. Task 2 và 3 gọi chúng.

- [ ] **Step 1: Viết test đỏ**

Tạo `tests/Unit/CuaSoQuetTest.php`:

```php
<?php

namespace Tests\Unit;

use App\Services\OrderCheck\Support\CuaSoQuet;
use Tests\TestCase;

class CuaSoQuetTest extends TestCase
{
    /** @test */
    public function ket_thuc_la_moc_cong_cua_so()
    {
        $this->assertSame(51000, CuaSoQuet::ketThuc(1000, 50000));
    }

    /** @test */
    public function cua_so_bang_khong_nghia_la_khong_chan()
    {
        $this->assertSame(0, CuaSoQuet::ketThuc(1000, 0));
        $this->assertSame(0, CuaSoQuet::ketThuc(1000, -5));
    }

    /** @test */
    public function lay_du_limit_thi_tien_toi_id_lon_nhat_trong_lo()
    {
        // Cua so CHUA duyet het: con dong chua lay, chi duoc tien toi cho da lay den.
        $this->assertSame(1400, CuaSoQuet::mocMoi(1000, 500, 500, 1400, 51000));
    }

    /** @test */
    public function lay_it_hon_limit_thi_nhay_toi_cuoi_cua_so()
    {
        // Cua so DA duyet het: nhay qua ca khoang trong con lai.
        $this->assertSame(51000, CuaSoQuet::mocMoi(1000, 120, 500, 1400, 51000));
    }

    /**
     * Ca quan trong nhat: cua so RONG.
     *
     * Khong nhay thi luot sau lai hoi dung cua so do, lai 0 dong, va bo quet DUNG IM
     * VINH VIEN - im lang, khong loi nao bao ra.
     */
    /** @test */
    public function khong_lay_duoc_dong_nao_thi_van_nhay_toi_cuoi_cua_so()
    {
        $this->assertSame(51000, CuaSoQuet::mocMoi(1000, 0, 500, 0, 51000));
    }

    /** @test */
    public function khong_chan_cua_so_thi_luon_lay_id_lon_nhat_trong_lo()
    {
        // cuoiCuaSo = 0 nghia la khong chan: giu nguyen hanh vi cu.
        $this->assertSame(1400, CuaSoQuet::mocMoi(1000, 120, 500, 1400, 0));
        $this->assertSame(1400, CuaSoQuet::mocMoi(1000, 500, 500, 1400, 0));
    }

    /** @test */
    public function khong_bao_gio_lui_moc()
    {
        // Lo tra ve id nho hon moc (khong nen xay ra, nhung neu xay ra thi phai giu moc).
        $this->assertSame(1000, CuaSoQuet::mocMoi(1000, 500, 500, 900, 0));
        $this->assertSame(1000, CuaSoQuet::mocMoi(1000, 0, 500, 0, 0));
    }
}
```

- [ ] **Step 2: Chạy test, xác nhận đỏ**

```bash
vendor/bin/phpunit tests/Unit/CuaSoQuetTest.php
```

Kỳ vọng: cả 7 test FAIL với `Class 'App\Services\OrderCheck\Support\CuaSoQuet' not found`.

- [ ] **Step 3: Viết lớp**

Tạo `app/Services/OrderCheck/Support/CuaSoQuet.php`:

```php
<?php

namespace App\Services\OrderCheck\Support;

/**
 * Cua so quet co chan tren, va quy tac day moc sau moi luot.
 *
 * Vi sao can: Laravel sinh SQL dang
 *   select * from (select rownum rn, t1.* from (... order by id) t1) where rn <= 500
 * Truy van TRONG CUNG khong co gioi han, nen Oracle noi va sap xep MOI dong sau moc roi
 * moi cat 500 o tang ngoai. Do tren production, limit giu nguyen 500:
 *   ton    10.000 dong ->     68 ms
 *   ton   100.000 dong ->    582 ms
 *   ton 1.000.000 dong ->  4.849 ms
 *   ton 5.000.000 dong -> 21.356 ms
 * Tuyen tinh voi KHOANG TON, khong lien quan limit. Ton cang lon, duoi kip cang cham.
 *
 * Chan tren lam tap phai sap xep bi chan cung, thoi gian moi luot thanh hang so.
 *
 * Ham THUAN de kiem duoc.
 */
class CuaSoQuet
{
    /**
     * Cuoi cua so quet.
     *
     * @param int $moc   moc hien tai (last_id)
     * @param int $cuaSo do rong cua so; <= 0 nghia la KHONG chan
     * @return int 0 nghia la khong chan
     */
    public static function ketThuc($moc, $cuaSo)
    {
        $cuaSo = (int) $cuaSo;

        if ($cuaSo <= 0) {
            return 0;
        }

        return (int) $moc + $cuaSo;
    }

    /**
     * Moc moi sau mot luot quet.
     *
     * Lay DU limit  -> cua so chua duyet het -> chi tien toi id lon nhat da lay.
     * Lay IT hon    -> cua so da duyet het   -> nhay toi cuoi cua so.
     *
     * Ve thu hai la thu chua cai bay: cua so rong ma khong day moc thi bo quet dung im
     * vinh vien, im lang, khong loi nao bao ra.
     *
     * @param int $moc          moc hien tai
     * @param int $soDongLay    so dong lo vua tra ve
     * @param int $limit        gioi han moi luot
     * @param int $maxIdTrongLo id lon nhat trong lo (0 neu lo rong)
     * @param int $cuoiCuaSo    ket qua cua ketThuc(); 0 nghia la khong chan
     * @return int khong bao gio nho hon $moc
     */
    public static function mocMoi($moc, $soDongLay, $limit, $maxIdTrongLo, $cuoiCuaSo)
    {
        $moc = (int) $moc;
        $cuoiCuaSo = (int) $cuoiCuaSo;

        if ($cuoiCuaSo <= 0 || (int) $soDongLay >= (int) $limit) {
            return max($moc, (int) $maxIdTrongLo);
        }

        return max($moc, $cuoiCuaSo);
    }
}
```

- [ ] **Step 4: Chạy test, xác nhận xanh**

```bash
vendor/bin/phpunit tests/Unit/CuaSoQuetTest.php
```

Kỳ vọng: PASS cả 7 test.

- [ ] **Step 5: Chạy suite Unit**

```bash
vendor/bin/phpunit --testsuite Unit
```

Kỳ vọng: OK, số test tăng 7.

- [ ] **Step 6: Commit**

```bash
git add app/Services/OrderCheck/Support/CuaSoQuet.php tests/Unit/CuaSoQuetTest.php
git commit -m "feat(order-check): ham thuan cua so quet co chan tren"
```

---

### Task 2: Chặn trên cho ba truy vấn theo mốc id

**Files:**
- Modify: `config/order_check.php`
- Modify: `app/Services/OrderCheck/HisOrderSource.php` (`fetchInteractions`, `fetchExpMestBatch`, `fetchSereServWithPatient`)
- Test: `tests/Unit/HisOrderSourceCuaSoTest.php`

**Interfaces:**
- Consumes: `App\Services\OrderCheck\Support\CuaSoQuet::ketThuc($moc, $cuaSo)` từ Task 1.
- Produces: ba method trên nhận thêm tham số cuối `$cuoiCuaSo = 0`; giá trị `0` nghĩa là không chặn. `HisOrderSource::cuaSo()` trả `int` — độ rộng cửa sổ từ config, để scanner dùng chung một nguồn.

- [ ] **Step 1: Viết test đỏ**

Tạo `tests/Unit/HisOrderSourceCuaSoTest.php`:

```php
<?php

namespace Tests\Unit;

use Tests\TestCase;
use Tests\Support\LocComment;

/**
 * Chan viec go chan tren khoi cac truy van theo moc id - go la quay lai canh moi luot
 * sap xep toan bo ton, cham dan theo do lon cua ton.
 *
 * Va chan viec ai do "cho dong bo" ma ap nham cua so cho fetchServiceRequests, von dung
 * moc theo modify_time chu khong phai id.
 */
class HisOrderSourceCuaSoTest extends TestCase
{
    use LocComment;

    protected function ma()
    {
        // Bo comment truoc khi quet: mot chuoi nam trong comment se lam test xanh gia.
        return $this->maKhongComment(base_path('app/Services/OrderCheck/HisOrderSource.php'));
    }

    /** Than cua mot method, cat tu dong khai bao den dau '}' o dau dong cung cap */
    protected function than($tenMethod)
    {
        $ma = $this->ma();
        $vt = strpos($ma, 'function ' . $tenMethod . '(');

        $this->assertNotFalse($vt, "Khong tim thay method $tenMethod");

        $ke = strpos($ma, "\n    public function ", $vt + 10);
        $ke = $ke === false ? strlen($ma) : $ke;

        return substr($ma, $vt, $ke - $vt);
    }

    /** @test */
    public function ba_truy_van_theo_moc_id_deu_co_chan_tren()
    {
        foreach (['fetchInteractions', 'fetchExpMestBatch', 'fetchSereServWithPatient'] as $m) {
            $than = $this->than($m);

            $this->assertContains('cuoiCuaSo', $than,
                "Method $m mat chan tren cua so quet");
        }
    }

    /** @test */
    public function truy_van_theo_thoi_gian_khong_bi_ap_cua_so()
    {
        // fetchServiceRequests dung moc theo modify_time: mot dong cu duoc sua lai se nhay
        // ve cuoi hang doi, nen cua so theo id khong co y nghia o day.
        $than = $this->than('fetchServiceRequests');

        $this->assertNotContains('cuoiCuaSo', $than,
            'fetchServiceRequests khong duoc ap cua so theo id');
    }

    /** @test */
    public function cau_hinh_cua_so_mac_dinh_la_50000()
    {
        $this->assertSame(50000, (int) config('order_check.scan_id_window'));
    }
}
```

- [ ] **Step 2: Chạy test, xác nhận đỏ**

```bash
vendor/bin/phpunit tests/Unit/HisOrderSourceCuaSoTest.php
```

Kỳ vọng: `ba_truy_van_theo_moc_id_deu_co_chan_tren` và `cau_hinh_cua_so_mac_dinh_la_50000` FAIL; `truy_van_theo_thoi_gian_khong_bi_ap_cua_so` PASS sẵn.

- [ ] **Step 3: Thêm khoá config**

Trong `config/order_check.php`, thêm ngay sau khoá `exclude_service_req_type_ids`:

```php
    // Do rong cua so quet theo id. 0 nghia la KHONG chan (hanh vi cu).
    //
    // Laravel sinh SQL dang "select * from (select rownum rn, t1.* from (... order by id) t1)
    // where rn <= 500": truy van trong cung khong co gioi han nen Oracle sap xep MOI dong
    // sau moc roi moi cat. Do tren production, limit giu nguyen 500:
    //   ton    10.000 ->     68 ms      ton 1.000.000 ->  4.849 ms
    //   ton   100.000 ->    582 ms      ton 5.000.000 -> 21.356 ms
    // Chan tren lam thoi gian moi luot thanh hang so. 50.000 roi vao khoang ~300 ms.
    'scan_id_window' => (int) env('ORDER_CHECK_SCAN_ID_WINDOW', 50000),
```

- [ ] **Step 4: Thêm hàm đọc cửa sổ vào HisOrderSource**

Trong `app/Services/OrderCheck/HisOrderSource.php`, thêm ngay sau hàm dựng (`__construct`):

```php
    /** Do rong cua so quet theo id; 0 nghia la khong chan */
    public function cuaSo()
    {
        return (int) config('order_check.scan_id_window', 0);
    }
```

- [ ] **Step 5: Thêm chặn trên cho fetchInteractions**

Đổi chữ ký và thêm điều kiện. Tìm theo nội dung, đừng neo theo số dòng:

```php
    public function fetchInteractions($lastCreateTime, $lastId, $limit, $cuoiCuaSo = 0)
    {
        $q = DB::connection($this->conn)
            ->table('his_medicine_interactive')
            ->where('is_delete', 0)
            ->where('id', '>', $lastId);

        // Chan tren de Oracle chi phai sap xep toi da mot cua so, khong phai toan bo ton.
        if ($cuoiCuaSo > 0) {
            $q->where('id', '<=', $cuoiCuaSo);
        }

        return $q->orderBy('id')
            ->limit($limit)
            ->selectRaw('id, create_time, treatment_id, request_loginname,
                request_department_id, icd_code, icd_name,
                medicine_type_id1, medicine_type_id2, interactive_grade_id')
            ->get();
    }
```

- [ ] **Step 6: Thêm chặn trên cho fetchExpMestBatch**

```php
    public function fetchExpMestBatch($lastCreateTime, $lastId, $limit, $cuoiCuaSo = 0)
    {
        $q = DB::connection($this->conn)
            ->table('his_exp_mest_medicine')
            ->where('is_delete', 0)
            ->where('id', '>', $lastId);

        // Chan tren de Oracle chi phai sap xep toi da mot cua so, khong phai toan bo ton.
        if ($cuoiCuaSo > 0) {
            $q->where('id', '<=', $cuoiCuaSo);
        }

        return $q->orderBy('id')->limit($limit)
            ->selectRaw('id, create_time, tdl_treatment_id, medicine_id, tdl_medicine_type_id,
                amount, day_count, morning, noon, afternoon, evening')
            ->get();
    }
```

- [ ] **Step 7: Thêm chặn trên cho fetchSereServWithPatient**

```php
    public function fetchSereServWithPatient($lastCreateTime, $lastId, $limit, $cuoiCuaSo = 0)
    {
        $q = DB::connection($this->conn)
            ->table('his_sere_serv as ss')
            ->leftJoin('his_treatment as t', 'ss.tdl_treatment_id', '=', 't.id')
            // Ma CSKCB de gan vao vi pham, giong cach lam cua fetchServiceRequests().
            ->leftJoin('his_branch as br', 'br.id', '=', 't.branch_id')
            ->where('ss.is_delete', 0)
            ->where('ss.id', '>', $lastId);

        // Chan tren de Oracle chi phai sap xep toi da mot cua so, khong phai toan bo ton.
        // Day la truy van nang nhat: bang 168 trieu id, hai LEFT JOIN.
        if ($cuoiCuaSo > 0) {
            $q->where('ss.id', '<=', $cuoiCuaSo);
        }

        return $q->orderBy('ss.id')->limit($limit)
            ->selectRaw('ss.id, ss.create_time, ss.service_req_id, ss.tdl_treatment_id, ss.tdl_service_code, ss.tdl_service_name,
                t.treatment_code, t.tdl_patient_code, t.tdl_patient_name,
                t.tdl_patient_gender_id, t.tdl_patient_dob, br.hein_medi_org_code as ma_cskcb')
            ->get();
    }
```

- [ ] **Step 8: Kiểm cú pháp và chạy test**

```bash
php -l app/Services/OrderCheck/HisOrderSource.php && php -l config/order_check.php && vendor/bin/phpunit tests/Unit/HisOrderSourceCuaSoTest.php
```

Kỳ vọng: không lỗi cú pháp; PASS cả 3 test.

- [ ] **Step 9: Nghiệm thu bằng số — bắt buộc**

Đo lại chính phép đo đã phát hiện lỗi, so bản không chặn với bản có chặn:

```bash
php -r "require 'vendor/autoload.php'; \$a=require 'bootstrap/app.php'; \$a->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap(); \$src = new App\Services\OrderCheck\HisOrderSource(); \$max = DB::connection('HISPro')->table('his_sere_serv')->max('id'); foreach([1000000, 5000000] as \$lui){ \$moc = \$max - \$lui; \$t=microtime(true); \$src->fetchSereServWithPatient(0, \$moc, 500, 0); \$cu=round((microtime(true)-\$t)*1000); \$t=microtime(true); \$src->fetchSereServWithPatient(0, \$moc, 500, \$moc + 50000); \$moi=round((microtime(true)-\$t)*1000); printf('ton %9s dong: khong chan %6s ms | co chan %6s ms'.PHP_EOL, number_format(\$lui), \$cu, \$moi); }"
```

Kỳ vọng: cột "có chặn" **dưới 500 ms** ở cả hai mức tồn, và **không tăng** khi tồn tăng từ 1 triệu lên 5 triệu. Đây là bằng chứng duy nhất cho thấy bản sửa giải quyết đúng vấn đề.

Nếu cột "có chặn" vẫn tăng theo tồn, **đừng sửa số cho khớp** — báo lại con số thật.

- [ ] **Step 10: Chạy suite Unit**

```bash
vendor/bin/phpunit --testsuite Unit
```

Kỳ vọng: OK, số test tăng 3 so với sau Task 1.

- [ ] **Step 11: Commit**

```bash
git add config/order_check.php app/Services/OrderCheck/HisOrderSource.php tests/Unit/HisOrderSourceCuaSoTest.php
git commit -m "perf(order-check): chan tren cua so quet cho ba truy van theo moc id"
```

---

### Task 3: Ba scanner dùng cửa sổ và bỏ qua khi danh mục rỗng

**Files:**
- Modify: `app/Services/OrderCheck/Scanners/ServiceRestrictionScanner.php`
- Modify: `app/Services/OrderCheck/Scanners/MedicineScanner.php`
- Modify: `app/Services/OrderCheck/Scanners/InteractionLogScanner.php`
- Test: `tests/Unit/ScannerCuaSoTest.php`

**Interfaces:**
- Consumes: `CuaSoQuet::ketThuc($moc, $cuaSo)` và `CuaSoQuet::mocMoi($moc, $soDongLay, $limit, $maxIdTrongLo, $cuoiCuaSo)` từ Task 1; `HisOrderSource::cuaSo()` và tham số thứ tư `$cuoiCuaSo` của ba method fetch từ Task 2.
- Produces: không có gì cho task sau.

**Điểm mấu chốt của task này:** lệnh `saveWatermark` phải nằm **NGOÀI** khối `if ($scanned > 0)`. Đó chính là thứ chữa cái bẫy cửa sổ rỗng. Để nguyên bên trong thì cửa sổ rỗng → không lưu mốc → lượt sau hỏi lại đúng cửa sổ đó → **đứng im vĩnh viễn**, im lặng. Vì `$maxCreate`/`$maxId` được dùng ở lệnh lưu, chúng phải được khai **trước** khối `if`.

**Lệch có chủ ý so với spec:** spec đề nghị kiểm nhánh "danh mục rỗng" bằng test double cho `HisOrderSource`. Plan này thay bằng quét mã nguồn (Step 1) cộng chạy thật một lượt trong giao dịch rồi hoàn nguyên (Step 9). Lý do: dự án đã biết Mockery vỡ khi lớp có kiểu trả về khai báo, và chạy thật kiểm được đúng điều quan trọng nhất — mốc có tiến hay không — mà test double không kiểm được.

- [ ] **Step 1: Viết test đỏ**

Tạo `tests/Unit/ScannerCuaSoTest.php`:

```php
<?php

namespace Tests\Unit;

use Tests\TestCase;
use Tests\Support\LocComment;

/**
 * Chan hai kieu thut lui:
 *  - scanner khong con truyen cua so xuong truy van (mat het loi ich hieu nang);
 *  - scanner tu day moc bang tay thay vi dung CuaSoQuet (de tai lap cai bay cua so rong
 *    khien bo quet dung im vinh vien).
 */
class ScannerCuaSoTest extends TestCase
{
    use LocComment;

    protected function ma($ten)
    {
        // Bo comment truoc khi quet: mot chuoi nam trong comment se lam test xanh gia.
        return $this->maKhongComment(
            base_path('app/Services/OrderCheck/Scanners/' . $ten . '.php')
        );
    }

    /** @test */
    public function ba_scanner_deu_dung_cua_so_quet()
    {
        foreach (['ServiceRestrictionScanner', 'MedicineScanner', 'InteractionLogScanner'] as $s) {
            $ma = $this->ma($s);

            $this->assertContains('CuaSoQuet::ketThuc', $ma, "$s khong tinh cuoi cua so");
            $this->assertContains('CuaSoQuet::mocMoi', $ma, "$s khong dung CuaSoQuet de day moc");
        }
    }

    /** @test */
    public function scanner_gioi_han_dv_bo_qua_khi_danh_muc_rong()
    {
        $ma = $this->ma('ServiceRestrictionScanner');

        // exists() chu khong count(): chi can biet co hay khong.
        $this->assertContains('exists()', $ma,
            'Khong thay phep kiem danh muc rong bang exists()');
    }
}
```

- [ ] **Step 2: Chạy test, xác nhận đỏ**

```bash
vendor/bin/phpunit tests/Unit/ScannerCuaSoTest.php
```

Kỳ vọng: cả 2 test FAIL.

- [ ] **Step 3: Sửa MedicineScanner**

Trong `app/Services/OrderCheck/Scanners/MedicineScanner.php`, thay đoạn lấy dữ liệu và đẩy mốc.

Đổi dòng gọi fetch:

```php
        $cuoiCuaSo = \App\Services\OrderCheck\Support\CuaSoQuet::ketThuc($wm->last_id, $source->cuaSo());
        $rows = $source->fetchExpMestBatch($wm->last_create_time, $wm->last_id, $limit, $cuoiCuaSo);
```

Giữ nguyên vòng lặp tính `$maxId`. Thay dòng lưu mốc (đang là `saveWatermark(self::SOURCE_KEY, $wm->last_create_time, $maxId)`) bằng:

```php
            $engine->saveWatermark(
                self::SOURCE_KEY,
                $wm->last_create_time,
                \App\Services\OrderCheck\Support\CuaSoQuet::mocMoi(
                    $wm->last_id, $scanned, $limit, $maxId, $cuoiCuaSo
                )
            );
```

Rồi **chuyển việc lưu mốc ra ngoài khối `if ($scanned > 0)`** — cửa sổ rỗng cũng phải đẩy mốc, nếu không bộ quét đứng im. Khi `$scanned == 0` thì `$maxId` chưa được gán, nên khai `$maxId = $wm->last_id;` **trước** khối `if`.

- [ ] **Step 4: Sửa InteractionLogScanner**

Trong `app/Services/OrderCheck/Scanners/InteractionLogScanner.php`, đổi dòng gọi fetch:

```php
        $cuoiCuaSo = \App\Services\OrderCheck\Support\CuaSoQuet::ketThuc($wm->last_id, $source->cuaSo());
        $rows = $source->fetchInteractions($wm->last_create_time, $wm->last_id, $limit, $cuoiCuaSo);
```

Khai `$maxCreate = $wm->last_create_time;` và `$maxId = $wm->last_id;` **trước** khối `if ($scanned > 0)`.

Thay dòng lưu mốc bằng, và chuyển nó ra **ngoài** khối `if`:

```php
        $engine->saveWatermark(
            self::SOURCE_KEY,
            $maxCreate,
            \App\Services\OrderCheck\Support\CuaSoQuet::mocMoi(
                $wm->last_id, $scanned, $limit, $maxId, $cuoiCuaSo
            )
        );
```

- [ ] **Step 5: Sửa ServiceRestrictionScanner — cửa sổ**

Trong `app/Services/OrderCheck/Scanners/ServiceRestrictionScanner.php`, đổi dòng gọi fetch:

```php
        $cuoiCuaSo = \App\Services\OrderCheck\Support\CuaSoQuet::ketThuc($wm->last_id, $source->cuaSo());
        $rows = $source->fetchSereServWithPatient($wm->last_create_time, $wm->last_id, $limit, $cuoiCuaSo);
```

Khai `$maxCreate = $wm->last_create_time;` và `$maxId = $wm->last_id;` **trước** khối `if ($scanned > 0)`, và chuyển lệnh lưu mốc ra **ngoài** khối đó:

```php
        $engine->saveWatermark(
            self::SOURCE_KEY,
            $maxCreate,
            \App\Services\OrderCheck\Support\CuaSoQuet::mocMoi(
                $wm->last_id, $scanned, $limit, $maxId, $cuoiCuaSo
            )
        );
```

- [ ] **Step 6: Sửa ServiceRestrictionScanner — bỏ qua khi danh mục rỗng**

Ngay **trước** dòng gọi `fetchSereServWithPatient`, chèn:

```php
        // Danh muc gioi han rong thi hai quy tac deu KHONG THE sinh vi pham - khong truy
        // van HIS lam gi. Do tren production: 24.402 dong da quet, 0 vi pham, ma van ton
        // 43 phut tong cong.
        //
        // Van phai DAY MOC, neu khong den luc nhap danh muc se ton dong ca chuc trieu dong
        // va roi lai dung van de hieu nang nay. Nhung dong bi bo qua trong luc danh muc
        // rong se khong duoc kiem lai - khong mat gi so voi hien tai, vi hom nay chung van
        // duoc quet nhung luon cho ket qua rong, va bo quet von chi chay toi truoc.
        if (!OrderCheckRefServiceRestriction::where('is_active', true)->exists()) {
            $maxIdHis = (int) $source->maxSereServId();
            $mocMoi = $cuoiCuaSo > 0 ? min($cuoiCuaSo, $maxIdHis) : $maxIdHis;

            $engine->saveWatermark(self::SOURCE_KEY, $wm->last_create_time, max((int) $wm->last_id, $mocMoi));

            return ['scanned' => 0, 'violations' => 0];
        }
```

- [ ] **Step 7: Thêm hàm maxSereServId vào HisOrderSource**

Trong `app/Services/OrderCheck/HisOrderSource.php`, thêm ngay sau `fetchSereServWithPatient`:

```php
    /** id lon nhat cua his_sere_serv; dung de day moc khi bo qua vong quet */
    public function maxSereServId()
    {
        return (int) DB::connection($this->conn)->table('his_sere_serv')->max('id');
    }
```

- [ ] **Step 8: Kiểm cú pháp và chạy test**

```bash
php -l app/Services/OrderCheck/Scanners/ServiceRestrictionScanner.php && php -l app/Services/OrderCheck/Scanners/MedicineScanner.php && php -l app/Services/OrderCheck/Scanners/InteractionLogScanner.php && php -l app/Services/OrderCheck/HisOrderSource.php && vendor/bin/phpunit tests/Unit/ScannerCuaSoTest.php
```

Kỳ vọng: không lỗi cú pháp; PASS cả 2 test.

- [ ] **Step 9: Chạy thật một lượt quét, không đụng mốc production**

Bọc trong giao dịch rồi hoàn nguyên để mốc thật không bị thay đổi:

```bash
php -r "require 'vendor/autoload.php'; \$a=require 'bootstrap/app.php'; \$a->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap(); DB::beginTransaction(); \$engine = app(App\Services\OrderCheck\OrderCheckEngine::class); \$s = new App\Services\OrderCheck\Scanners\ServiceRestrictionScanner(); \$t=microtime(true); \$kq = \$s->scan(\$engine, 500); printf('scan: %s ms, ket qua %s'.PHP_EOL, round((microtime(true)-\$t)*1000), json_encode(\$kq)); \$w = DB::table('order_check_watermarks')->where('source_key','his_sere_serv_restriction')->first(); echo 'moc sau khi quet: '.\$w->last_id.PHP_EOL; DB::rollBack();"
```

Kỳ vọng: chạy không lỗi, thời gian **dưới 1 giây**, và mốc **đã tiến** (lớn hơn 168473038) dù danh mục đang rỗng — đó là bằng chứng nhánh bỏ qua vẫn đẩy mốc.

Chép nguyên văn output vào báo cáo.

- [ ] **Step 10: Chạy suite Unit**

```bash
vendor/bin/phpunit --testsuite Unit
```

Kỳ vọng: OK, số test tăng 2 so với sau Task 2.

- [ ] **Step 11: Commit**

```bash
git add app/Services/OrderCheck/Scanners/ app/Services/OrderCheck/HisOrderSource.php tests/Unit/ScannerCuaSoTest.php
git commit -m "perf(order-check): scanner dung cua so quet, bo qua khi danh muc rong"
```

---

### Task 4: Cập nhật tài liệu

**Files:**
- Modify: `docs/tai-lieu-tong-hop-xml3176-order-check.md`

**Interfaces:**
- Consumes: kết quả Task 1-3.
- Produces: không có gì.

- [ ] **Step 1: Thêm ghi chú vào mục lưu ý kỹ thuật của order-check**

Trong `docs/tai-lieu-tong-hop-xml3176-order-check.md`, tìm mục `### 3.6. Lưu ý kỹ thuật` và chèn đoạn dưới đây vào **cuối mục đó**, ngay trước tiêu đề mục kế tiếp:

```markdown
> **Cửa sổ quét có chặn trên** (từ 30/07/2026): Laravel sinh SQL dạng
> `select * from (select rownum rn, t1.* from (… order by id) t1) where rn <= 500`. Truy vấn
> **trong cùng không có giới hạn**, nên Oracle nối và sắp xếp **mọi dòng sau mốc** rồi mới
> cắt ở tầng ngoài. Đo trên production, `limit` giữ nguyên 500: tồn 10.000 → 68 ms;
> 100.000 → 582 ms; 1.000.000 → 4.849 ms; 5.000.000 → 21.356 ms. Tuyến tính với **khoảng
> tồn**, không liên quan `limit` — tồn càng lớn thì đuổi kịp càng chậm.
>
> Ba nguồn dùng mốc `id` nay có chặn trên `id <= mốc + cửa_sổ`
> (`config/order_check.php` khoá `scan_id_window`, mặc định 50.000; đặt 0 để bỏ chặn).
> Nguồn `his_service_req` **không** áp vì nó dùng mốc theo `modify_time` — một dòng cũ được
> sửa lại sẽ nhảy về cuối hàng đợi, ngữ nghĩa cửa sổ khác hẳn.
>
> Quy tắc đẩy mốc nằm ở `App\Services\OrderCheck\Support\CuaSoQuet::mocMoi()`: lấy **đủ**
> `limit` thì cửa sổ chưa duyệt hết, chỉ tiến tới id lớn nhất đã lấy; lấy **ít hơn** thì
> cửa sổ đã duyệt hết, nhảy tới cuối cửa sổ. Vế thứ hai là thứ chống cái bẫy **cửa sổ
> rỗng**: không nhảy thì lượt sau lại hỏi đúng cửa sổ đó, lại 0 dòng, và bộ quét **đứng im
> vĩnh viễn** mà không lỗi nào báo ra.
>
> `ServiceRestrictionScanner` còn bỏ hẳn truy vấn HIS khi
> `order_check_ref_service_restriction` không có dòng nào `is_active` — khi đó hai quy tắc
> giới tính/tuổi không thể sinh vi phạm. Đo trước khi sửa: 24.402 dòng đã quét, **0 vi
> phạm**, mà vẫn tốn 43 phút tổng cộng. Nhánh bỏ qua **vẫn đẩy mốc**, nếu không đến lúc
> nhập danh mục sẽ tồn đọng cả chục triệu dòng.
```

- [ ] **Step 2: Commit**

```bash
git add docs/tai-lieu-tong-hop-xml3176-order-check.md
git commit -m "docs(order-check): ghi lai cua so quet co chan tren"
```

---

## Nghiệm thu cuối

- [ ] `vendor/bin/phpunit --testsuite Unit` — OK, không đỏ.
- [ ] Phép đo ở Task 2 Step 9: cột "có chặn" dưới 500 ms ở cả tồn 1 triệu lẫn 5 triệu, và không tăng theo tồn.
- [ ] Lượt quét thật ở Task 3 Step 9 chạy dưới 1 giây và mốc vẫn tiến.
- [ ] `php artisan config:clear` trên máy chủ sau khi triển khai — đã sửa `config/order_check.php`.
