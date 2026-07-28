# Order-check — lọc BHYT và quy tắc danh mục BHXH — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Order-check chỉ đối chiếu danh mục BHXH trên đúng những dòng dịch vụ thuộc đối tượng BHYT, và có thêm bốn quy tắc danh mục seed ở trạng thái tắt.

**Architecture:** `OrderService` mang thêm `patientTypeId` đọc từ `his_sere_serv.patient_type_id`. Lọc hai tầng: tầng thô bỏ phiếu không có dòng BHYT nào, tầng tinh lọc từng dòng trong các quy tắc danh mục. Bốn rule handler mới nạp danh mục theo lô một lần mỗi phiếu và tự bỏ qua khi bảng danh mục rỗng.

**Tech Stack:** Laravel 5.5, PHP 7.4, PHPUnit 6.5, Oracle (HIS, chỉ SELECT) + MySQL (danh mục).

## Global Constraints

- Spec: `docs/superpowers/specs/2026-07-28-order-check-quy-tac-danh-muc-bhxh-design.md`
- Cổng test: **`vendor/bin/phpunit --testsuite Unit`**. Mốc: **342 test xanh**.
- **Không commit.** Chủ đầu tư review trước.
- **Không sửa 9 quy tắc hiện có.** Chúng chỉ hưởng tầng lọc thô, nội dung không đổi.
- Cột nhận biết BHYT là **`his_sere_serv.patient_type_id`**, không phải `primary_patient_type_id` (chỉ có ở 2,2% số dòng, không dòng nào là BHYT) và không phải mức phiếu/hồ sơ (lệch 30,17%).
- Bốn quy tắc mới seed `is_active = false`.
- Ba quy tắc phụ thuộc danh mục **phải tự bỏ qua khi bảng rỗng** — nếu không, đơn vị chưa nhập danh mục sẽ thấy mọi dịch vụ thành vi phạm.
- Tra danh mục **theo lô một lần cho mỗi phiếu**, không tra từng dòng.
- `ORDER_CHECK_BHYT_PATIENT_TYPES` để rỗng thì hành vi quay về đúng như trước đợt này.
- Comment mã nguồn viết tiếng Việt **không dấu**.
- Sau mỗi task: `php -l` file đã sửa, chạy suite Unit.

---

### Task 1: Đưa đối tượng của dòng dịch vụ vào ngữ cảnh

**Files:**
- Modify: `app/Services/OrderCheck/Support/OrderService.php`
- Modify: `app/Services/OrderCheck/HisOrderSource.php`
- Modify: `config/order_check.php`
- Create: `app/Services/OrderCheck/Support/BhytScope.php`
- Create: `tests/Unit/OrderCheck/BhytScopeTest.php`

**Interfaces:**
- Produces:
  ```php
  OrderService::$patientTypeId          // int|null
  BhytScope::dsDoiTuong(): array        // id doi tuong BHYT tu cau hinh, [] = khong loc
  BhytScope::laDongBhyt($patientTypeId): bool
  BhytScope::locDongBhyt(array $services): array
  BhytScope::coDongBhyt(array $services): bool
  ```

- [ ] **Step 1: Viết test (sẽ đỏ)**

Tạo `tests/Unit/OrderCheck/BhytScopeTest.php`:

```php
<?php

namespace Tests\Unit\OrderCheck;

use Tests\TestCase;
use App\Services\OrderCheck\Support\BhytScope;
use App\Services\OrderCheck\Support\OrderService;

class BhytScopeTest extends TestCase
{
    private function dv($patientTypeId)
    {
        $s = new OrderService();
        $s->sereServId = rand(1, 99999);
        $s->serviceCode = 'DV1';
        $s->patientTypeId = $patientTypeId;

        return $s;
    }

    private function datCauHinh($csv)
    {
        config(['order_check.bhyt_patient_type_ids' => $csv]);
    }

    /** @test */
    public function doc_danh_sach_doi_tuong_tu_cau_hinh()
    {
        $this->datCauHinh('1');
        $this->assertEquals([1], BhytScope::dsDoiTuong());

        $this->datCauHinh('1,5');
        $this->assertEquals([1, 5], BhytScope::dsDoiTuong());
    }

    /** @test */
    public function cau_hinh_rong_nghia_la_khong_loc()
    {
        // Duong lui: dat rong thi hanh vi quay ve dung nhu truoc dot nay.
        $this->datCauHinh('');

        $this->assertEquals([], BhytScope::dsDoiTuong());
        $this->assertTrue(BhytScope::laDongBhyt(42), 'Khong loc thi moi dong deu duoc xet');
        $this->assertTrue(BhytScope::laDongBhyt(null));

        $ds = [$this->dv(42), $this->dv(1)];
        $this->assertCount(2, BhytScope::locDongBhyt($ds));
        $this->assertTrue(BhytScope::coDongBhyt($ds));
    }

    /** @test */
    public function loc_dung_dong_bhyt_va_bo_dong_vien_phi()
    {
        // 43.264 dong Vien phi (02) nam trong ho so BHYT trong 7 ngay - neu khong loc o
        // muc DONG thi chung bi doi chieu danh muc BHXH va bat loi oan.
        $this->datCauHinh('1');

        $this->assertTrue(BhytScope::laDongBhyt(1));
        $this->assertFalse(BhytScope::laDongBhyt(42), 'Vien phi khong duoc xet');
        $this->assertFalse(BhytScope::laDongBhyt(null));

        $ds = [$this->dv(1), $this->dv(42), $this->dv(1)];
        $this->assertCount(2, BhytScope::locDongBhyt($ds));
    }

    /** @test */
    public function co_dong_bhyt_dung_cho_tang_loc_tho()
    {
        $this->datCauHinh('1');

        $this->assertTrue(BhytScope::coDongBhyt([$this->dv(42), $this->dv(1)]));
        $this->assertFalse(BhytScope::coDongBhyt([$this->dv(42), $this->dv(43)]));
        $this->assertFalse(BhytScope::coDongBhyt([]), 'Phieu khong co dong nao thi khong co dong BHYT');
    }

    /** @test */
    public function loc_dong_giu_nguyen_thu_tu_va_danh_so_lai()
    {
        $this->datCauHinh('1');

        $a = $this->dv(1);
        $b = $this->dv(42);
        $c = $this->dv(1);

        $kq = BhytScope::locDongBhyt([$a, $b, $c]);

        $this->assertSame([$a, $c], $kq);
        $this->assertEquals([0, 1], array_keys($kq));
    }
}
```

- [ ] **Step 2:** chạy test, xác nhận đỏ (`Class ... BhytScope not found`).

- [ ] **Step 3: Thêm khoá cấu hình**

Trong `config/order_check.php`, ngay sau `exclude_treatment_type_ids`:

```php
    // Doi tuong benh nhan duoc coi la BHYT, CSV id trong HIS_PATIENT_TYPE.
    // Mac dinh 1 = ma '01' BHYT. RONG = KHONG loc (hanh vi truoc 2026-07-28).
    //
    // LUU Y: loc phai o muc DONG DICH VU (his_sere_serv.patient_type_id), khong phai muc
    // ho so - do tren 7 ngay that thi hai cach lech 44.927 dong (30,17%), lon nhat la
    // 43.264 dong Vien phi nam trong ho so BHYT.
    'bhyt_patient_type_ids' => env('ORDER_CHECK_BHYT_PATIENT_TYPES', '1'),
```

- [ ] **Step 4: Thêm `patientTypeId` vào `OrderService`**

```php
    /** @var int|null Doi tuong cua RIENG dong nay (his_sere_serv.patient_type_id) */
    public $patientTypeId;
```

- [ ] **Step 5: Đọc cột đó trong `HisOrderSource::fetchServicesByReqIds()`**

Thêm `patient_type_id` vào `selectRaw`:

```php
            ->selectRaw('id, service_req_id, tdl_service_code, tdl_service_name, execute_time, tdl_intruction_time, patient_type_id')
```

và trong vòng lặp dựng `OrderService`:

```php
            $s->patientTypeId = $r->patient_type_id !== null ? (int) $r->patient_type_id : null;
```

- [ ] **Step 6: Viết `BhytScope`**

Tạo `app/Services/OrderCheck/Support/BhytScope.php`:

```php
<?php

namespace App\Services\OrderCheck\Support;

/**
 * Pham vi doi tuong BHYT cho order-check.
 *
 * Loc PHAI o muc DONG DICH VU (his_sere_serv.patient_type_id), khong phai muc phieu hay
 * muc ho so. Do tren 148.915 dong cua 7 ngay that: hai cach lech 44.927 dong (30,17%),
 * lon nhat la 43.264 dong Vien phi (02) nam trong ho so BHYT (01) - benh nhan co the
 * nhung rieng dich vu do tu chi tra. Loc sai cap se bat loi oan ~6.200 vi pham gia/ngay.
 *
 * Cot primary_patient_type_id KHONG dung duoc: chi co gia tri o 2,2% so dong va khong
 * mot dong nao mang gia tri BHYT.
 */
class BhytScope
{
    /**
     * @return array id doi tuong duoc coi la BHYT; mang RONG nghia la KHONG loc
     */
    public static function dsDoiTuong()
    {
        $csv = trim((string) config('order_check.bhyt_patient_type_ids', ''));

        if ($csv === '') {
            return [];
        }

        return array_values(array_map('intval', array_filter(explode(',', $csv), 'strlen')));
    }

    public static function laDongBhyt($patientTypeId)
    {
        $ds = self::dsDoiTuong();

        if (empty($ds)) {
            return true;   // khong loc
        }

        return $patientTypeId !== null && in_array((int) $patientTypeId, $ds, true);
    }

    /**
     * @param OrderService[] $services
     * @return OrderService[] danh so lai tu 0
     */
    public static function locDongBhyt(array $services)
    {
        if (empty(self::dsDoiTuong())) {
            return $services;
        }

        return array_values(array_filter($services, function ($s) {
            return self::laDongBhyt($s->patientTypeId);
        }));
    }

    /**
     * Tang loc THO: phieu co it nhat mot dong BHYT khong.
     */
    public static function coDongBhyt(array $services)
    {
        return !empty(self::locDongBhyt($services));
    }
}
```

- [ ] **Step 7:** chạy test, xác nhận xanh (5 test).
- [ ] **Step 8:** `php -l` ba file, chạy suite Unit — **347 test**.

---

### Task 2: Nạp danh mục theo lô, tự bỏ qua khi rỗng

Phần dễ sai nhất của cả đợt nằm ở đây, nên tách riêng và phủ test đầy đủ.

**Files:**
- Create: `app/Services/OrderCheck/Support/CatalogLookup.php`
- Create: `tests/Unit/OrderCheck/CatalogLookupTest.php`

**Interfaces:**
- Produces:
  ```php
  new CatalogLookup($bang, $cot)        // vd ('service_catalogs', 'ma_dich_vu')
  ->sanSang(): bool                     // false khi bang RONG -> quy tac phai bo qua
  ->nap(array $ma): void                // mot truy van whereIn cho ca lo
  ->coTrongDanhMuc($ma): bool
  ```

- [ ] **Step 1: Viết test (sẽ đỏ)**

Tạo `tests/Unit/OrderCheck/CatalogLookupTest.php`:

```php
<?php

namespace Tests\Unit\OrderCheck;

use Tests\TestCase;
use App\Services\OrderCheck\Support\CatalogLookup;

class CatalogLookupTest extends TestCase
{
    /** @test */
    public function bang_rong_thi_khong_san_sang()
    {
        // Day la phep kiem QUAN TRONG NHAT cua ca dot: don vi chua nhap danh muc ma quy
        // tac van chay thi MOI dich vu thanh vi pham - sai ma trong nhu dung.
        $lk = new CatalogLookup('service_catalogs', 'ma_dich_vu');

        $this->assertFalse($lk->sanSang(),
            'Bang danh muc dang rong ma van bao san sang - quy tac se bat loi oan toan bo');
    }

    /** @test */
    public function chua_nap_thi_khong_ma_nao_duoc_coi_la_co()
    {
        $lk = new CatalogLookup('service_catalogs', 'ma_dich_vu');

        $this->assertFalse($lk->coTrongDanhMuc('XYZ'));
    }

    /** @test */
    public function nap_lo_rong_khong_no()
    {
        $lk = new CatalogLookup('service_catalogs', 'ma_dich_vu');
        $lk->nap([]);

        $this->assertFalse($lk->coTrongDanhMuc('XYZ'));
    }

    /** @test */
    public function ma_rong_hoac_null_khong_bao_gio_duoc_coi_la_co()
    {
        $lk = new CatalogLookup('service_catalogs', 'ma_dich_vu');
        $lk->nap(['', null, '  ']);

        $this->assertFalse($lk->coTrongDanhMuc(''));
        $this->assertFalse($lk->coTrongDanhMuc(null));
        $this->assertFalse($lk->coTrongDanhMuc('  '));
    }

    /** @test */
    public function nap_hai_lan_thi_cong_don_chu_khong_xoa_lan_truoc()
    {
        // Moi phieu nap mot lo; lo sau khong duoc xoa ket qua lo truoc trong cung
        // vong doi doi tuong.
        $lk = new CatalogLookup('service_catalogs', 'ma_dich_vu');

        $lk->datSanChoTest(['A1']);
        $lk->datSanChoTest(['B2']);

        $this->assertTrue($lk->coTrongDanhMuc('A1'));
        $this->assertTrue($lk->coTrongDanhMuc('B2'));
    }

    /** @test */
    public function so_sanh_ma_khong_phan_biet_khoang_trang_thua()
    {
        $lk = new CatalogLookup('service_catalogs', 'ma_dich_vu');
        $lk->datSanChoTest(['A1']);

        $this->assertTrue($lk->coTrongDanhMuc(' A1 '));
    }
}
```

- [ ] **Step 2:** chạy test, xác nhận đỏ.

- [ ] **Step 3: Viết `CatalogLookup`**

```php
<?php

namespace App\Services\OrderCheck\Support;

use DB;

/**
 * Tra danh muc BHXH theo LO cho mot phieu.
 *
 * Bai hoc tu dot XML3176: cac checker o do tra danh muc 18 cho theo TUNG DONG, khien mot
 * ho so sinh hang nghin truy van. O day nap mot lan cho ca phieu bang whereIn.
 */
class CatalogLookup
{
    protected $bang;
    protected $cot;
    protected $co = [];        // ma da xac nhan co trong danh muc
    protected $sanSang;        // null = chua kiem

    public function __construct($bang, $cot)
    {
        $this->bang = $bang;
        $this->cot = $cot;
    }

    /**
     * Bang danh muc co du lieu khong.
     *
     * Bang RONG -> tra false -> quy tac goi PHAI bo qua. Neu khong, don vi chua nhap danh
     * muc se thay MOI dich vu thanh vi pham.
     */
    public function sanSang()
    {
        if ($this->sanSang === null) {
            $this->sanSang = DB::table($this->bang)->limit(1)->exists();
        }

        return $this->sanSang;
    }

    /**
     * Nap mot lo ma bang MOT truy van. Goi nhieu lan thi cong don.
     */
    public function nap(array $ma)
    {
        $ma = array_values(array_unique(array_filter(array_map(function ($m) {
            return trim((string) $m);
        }, $ma), 'strlen')));

        if (empty($ma) || !$this->sanSang()) {
            return;
        }

        $thay = DB::table($this->bang)
            ->whereIn($this->cot, $ma)
            ->distinct()
            ->pluck($this->cot)
            ->all();

        foreach ($thay as $m) {
            $this->co[trim((string) $m)] = true;
        }
    }

    public function coTrongDanhMuc($ma)
    {
        $ma = trim((string) $ma);

        return $ma !== '' && isset($this->co[$ma]);
    }

    /** Chi dung trong test: nap thang vao bo nho, khong cham co so du lieu. */
    public function datSanChoTest(array $ma)
    {
        foreach ($ma as $m) {
            $this->co[trim((string) $m)] = true;
        }

        $this->sanSang = true;
    }
}
```

- [ ] **Step 4:** chạy test, xác nhận xanh (6 test).
- [ ] **Step 5:** `php -l`, chạy suite Unit — **353 test**.

---

### Task 3: Bốn quy tắc danh mục

**Files:**
- Modify: `app/Services/OrderCheck/HisOrderSource.php` (thêm hàm lấy mã BHXH của dịch vụ)
- Create: `app/Services/OrderCheck/RuleHandlers/Bhyt/BhytCodeMissingRule.php`
- Create: `app/Services/OrderCheck/RuleHandlers/Bhyt/BhytCatalogRule.php` (lớp cha)
- Create: `.../Bhyt/BhytServiceCatalogRule.php`, `.../Bhyt/BhytDrugCatalogRule.php`, `.../Bhyt/BhytSupplyCatalogRule.php`
- Modify: `app/Services/OrderCheck/RuleHandlers/ServiceReq/CommonRules.php`
- Create: `tests/Unit/OrderCheck/BhytRuleTest.php`

**Interfaces:**
- Consumes: `BhytScope` (Task 1), `CatalogLookup` (Task 2)
- Produces: bốn handler với `code()` là `A_BHYT_CODE_MISSING`, `A_BHYT_SERVICE_NOT_IN_CATALOG`, `A_BHYT_DRUG_NOT_IN_CATALOG`, `A_BHYT_SUPPLY_NOT_IN_CATALOG`

- [ ] **Step 1: Viết test (sẽ đỏ)**

Test dùng handler với `CatalogLookup` đã `datSanChoTest()` nên **không chạm cơ sở dữ liệu**:

```php
<?php

namespace Tests\Unit\OrderCheck;

use Tests\TestCase;
use App\Services\OrderCheck\Support\OrderContext;
use App\Services\OrderCheck\Support\OrderService;
use App\Services\OrderCheck\Support\CatalogLookup;
use App\Services\OrderCheck\RuleHandlers\Bhyt\BhytCodeMissingRule;
use App\Services\OrderCheck\RuleHandlers\Bhyt\BhytServiceCatalogRule;

class BhytRuleTest extends TestCase
{
    protected function setUp()
    {
        parent::setUp();
        config(['order_check.bhyt_patient_type_ids' => '1']);
    }

    private function ctx(array $dv)
    {
        $c = new OrderContext();
        $c->serviceReqId = 111;
        $c->serviceReqCode = 'PK001';
        $c->services = $dv;

        return $c;
    }

    private function dv($id, $ma, $patientTypeId, $maBhyt = null)
    {
        $s = new OrderService();
        $s->sereServId = $id;
        $s->serviceCode = $ma;
        $s->serviceName = 'DV ' . $ma;
        $s->patientTypeId = $patientTypeId;
        $s->bhytCode = $maBhyt;

        return $s;
    }

    /** @test */
    public function thieu_ma_bhyt_chi_bao_tren_dong_bhyt()
    {
        $r = new BhytCodeMissingRule();

        $vi = $r->check($this->ctx([
            $this->dv(1, 'DV1', 1,  null),   // BHYT, thieu ma -> vi pham
            $this->dv(2, 'DV2', 42, null),   // Vien phi, thieu ma -> BO QUA
            $this->dv(3, 'DV3', 1,  'BH3'),  // BHYT, co ma -> khong sao
        ]));

        $this->assertCount(1, $vi);
        $this->assertEquals('A_BHYT_CODE_MISSING', $vi[0]->ruleCode);
        $this->assertEquals(1, $vi[0]->orderRefId);
    }

    /** @test */
    public function moi_dong_vi_pham_co_subkey_rieng_de_khong_bi_gop()
    {
        $r = new BhytCodeMissingRule();

        $vi = $r->check($this->ctx([
            $this->dv(1, 'DV1', 1, null),
            $this->dv(2, 'DV2', 1, null),
        ]));

        $this->assertCount(2, $vi);
        $this->assertNotEquals($vi[0]->dedupKey(), $vi[1]->dedupKey());
    }

    /** @test */
    public function danh_muc_rong_thi_quy_tac_im_lang()
    {
        // Phep kiem quan trong nhat: danh muc chua nhap KHONG duoc bien moi dich vu
        // thanh vi pham.
        $lk = new CatalogLookup('service_catalogs', 'ma_dich_vu');   // bang dang rong
        $r = new BhytServiceCatalogRule($lk);

        $vi = $r->check($this->ctx([$this->dv(1, 'DV1', 1, 'BH1')]));

        $this->assertCount(0, $vi, 'Danh muc rong ma van bao vi pham');
    }

    /** @test */
    public function chi_bao_dong_co_ma_khong_khop_danh_muc()
    {
        $lk = new CatalogLookup('service_catalogs', 'ma_dich_vu');
        $lk->datSanChoTest(['BH1']);

        $r = new BhytServiceCatalogRule($lk);

        $vi = $r->check($this->ctx([
            $this->dv(1, 'DV1', 1,  'BH1'),   // khop -> khong sao
            $this->dv(2, 'DV2', 1,  'BH9'),   // khong khop -> vi pham
            $this->dv(3, 'DV3', 42, 'BH9'),   // Vien phi -> BO QUA
            $this->dv(4, 'DV4', 1,  null),    // thieu ma -> de quy tac kia lo
        ]));

        $this->assertCount(1, $vi);
        $this->assertEquals(2, $vi[0]->orderRefId);
    }

    /** @test */
    public function phieu_khong_co_dong_bhyt_nao_thi_khong_bao_gi()
    {
        $r = new BhytCodeMissingRule();

        $vi = $r->check($this->ctx([
            $this->dv(1, 'DV1', 42, null),
            $this->dv(2, 'DV2', 43, null),
        ]));

        $this->assertCount(0, $vi);
    }
}
```

- [ ] **Step 2:** chạy test, xác nhận đỏ.

- [ ] **Step 3: Thêm `bhytCode` vào `OrderService` và đọc từ HIS**

Trong `OrderService`:

```php
    /** @var string|null Ma BHXH cua dich vu (his_service.hein_service_bhyt_code) */
    public $bhytCode;
```

Trong `HisOrderSource::fetchServicesByReqIds()`, đổi sang join `his_service`:

```php
        $rows = DB::connection($this->conn)
            ->table('his_sere_serv as ss')
            // Ma BHXH nam tren danh muc dich vu HIS, khong nam tren dong y lenh.
            ->leftJoin('his_service as sv', 'sv.id', '=', 'ss.service_id')
            ->where('ss.is_delete', 0)
            ->whereIn('ss.service_req_id', $reqIds)
            ->selectRaw('ss.id, ss.service_req_id, ss.tdl_service_code, ss.tdl_service_name,
                ss.execute_time, ss.tdl_intruction_time, ss.patient_type_id,
                sv.hein_service_bhyt_code')
            ->get();
```

và gán `$s->bhytCode = $r->hein_service_bhyt_code;`.

- [ ] **Step 4: Viết `BhytCodeMissingRule`**

```php
<?php

namespace App\Services\OrderCheck\RuleHandlers\Bhyt;

use App\Services\OrderCheck\Contracts\RuleHandler;
use App\Services\OrderCheck\Support\OrderContext;
use App\Services\OrderCheck\Support\Violation;
use App\Services\OrderCheck\Support\BhytScope;

/**
 * Dong dich vu thuoc doi tuong BHYT nhung dich vu KHONG khai ma BHXH trong HIS.
 *
 * Quy tac nay KHONG phu thuoc danh muc da nhap nen chay duoc ngay va khong the sai vi
 * danh muc thieu.
 *
 * LUU Y ve quy mo: 21.778 dich vu HIS dang hoat dong, chi 10.552 khai ma BHXH (48%).
 * Chay thu bang lenh kiemtraylenh:thu truoc khi bat.
 */
class BhytCodeMissingRule implements RuleHandler
{
    public function code()
    {
        return 'A_BHYT_CODE_MISSING';
    }

    public function check(OrderContext $c)
    {
        $vi = [];

        foreach (BhytScope::locDongBhyt($c->services) as $s) {
            if (trim((string) $s->bhytCode) !== '') {
                continue;
            }

            $vi[] = new Violation(
                $this->code(),
                'sere_serv',
                $s->sereServId,
                'Dịch vụ chỉ định cho đối tượng BHYT nhưng chưa khai mã BHXH: ' . $s->serviceName,
                [
                    'service_req_code' => $c->serviceReqCode,
                    'service_code' => $s->serviceCode,
                ],
                (string) $s->sereServId
            );
        }

        return $vi;
    }
}
```

- [ ] **Step 5: Viết lớp cha `BhytCatalogRule`**

```php
<?php

namespace App\Services\OrderCheck\RuleHandlers\Bhyt;

use App\Services\OrderCheck\Contracts\RuleHandler;
use App\Services\OrderCheck\Support\OrderContext;
use App\Services\OrderCheck\Support\Violation;
use App\Services\OrderCheck\Support\BhytScope;
use App\Services\OrderCheck\Support\CatalogLookup;

/**
 * Khung chung cho cac quy tac doi chieu ma BHXH voi danh muc da nhap.
 *
 * Bang danh muc RONG thi quy tac IM LANG. Khong the thi don vi chua nhap danh muc se
 * thay MOI dich vu thanh vi pham - sai ma trong nhu dung.
 */
abstract class BhytCatalogRule implements RuleHandler
{
    /** @var CatalogLookup */
    protected $danhMuc;

    public function __construct(CatalogLookup $danhMuc = null)
    {
        $this->danhMuc = $danhMuc ?: new CatalogLookup($this->bang(), $this->cot());
    }

    abstract protected function bang();
    abstract protected function cot();
    abstract protected function nhan();

    public function check(OrderContext $c)
    {
        if (!$this->danhMuc->sanSang()) {
            return [];   // danh muc chua nhap
        }

        $dong = array_values(array_filter(BhytScope::locDongBhyt($c->services), function ($s) {
            return trim((string) $s->bhytCode) !== '';
        }));

        if (empty($dong)) {
            return [];
        }

        // Mot truy van cho ca phieu, khong tra tung dong.
        $this->danhMuc->nap(array_map(function ($s) {
            return $s->bhytCode;
        }, $dong));

        $vi = [];

        foreach ($dong as $s) {
            if ($this->danhMuc->coTrongDanhMuc($s->bhytCode)) {
                continue;
            }

            $vi[] = new Violation(
                $this->code(),
                'sere_serv',
                $s->sereServId,
                $this->nhan() . ' không có trong danh mục BHXH: ' . $s->bhytCode
                    . ' (' . $s->serviceName . ')',
                [
                    'service_req_code' => $c->serviceReqCode,
                    'service_code' => $s->serviceCode,
                    'bhyt_code' => $s->bhytCode,
                ],
                (string) $s->sereServId
            );
        }

        return $vi;
    }
}
```

- [ ] **Step 6: Viết ba lớp con**

```php
class BhytServiceCatalogRule extends BhytCatalogRule
{
    public function code()      { return 'A_BHYT_SERVICE_NOT_IN_CATALOG'; }
    protected function bang()   { return 'service_catalogs'; }
    protected function cot()    { return 'ma_dich_vu'; }
    protected function nhan()   { return 'Mã dịch vụ'; }
}
```

Tương tự: `BhytDrugCatalogRule` → `A_BHYT_DRUG_NOT_IN_CATALOG` / `medicine_catalogs` /
`ma_thuoc` / `'Mã thuốc'`; `BhytSupplyCatalogRule` → `A_BHYT_SUPPLY_NOT_IN_CATALOG` /
`medical_supply_catalogs` / `ma_vat_tu` / `'Mã vật tư'`.

- [ ] **Step 7: Đăng ký bốn handler vào `CommonRules`**

Thêm bốn `new ...Rule()` vào mảng `handlers()`. Chúng chỉ chạy khi quy tắc tương ứng
`is_active = true` — engine đã lọc sẵn theo `code()`.

- [ ] **Step 8:** chạy test, xác nhận xanh (5 test).
- [ ] **Step 9:** `php -l` các file, chạy suite Unit — **358 test**.

---

### Task 4: Tầng lọc thô, seed quy tắc, lệnh chạy thử

**Files:**
- Modify: `app/Services/OrderCheck/Scanners/ServiceReqScanner.php`
- Create: `database/migrations/2026_07_28_100000_seed_order_check_bhyt_catalog_rules.php`
- Create: `app/Console/Commands/OrderCheckDryRun.php`
- Modify: `app/Console/Kernel.php`
- Create: `tests/Unit/OrderCheck/BhytSeedTest.php`

**Interfaces:**
- Consumes: mọi thứ ở Task 1–3.

- [ ] **Step 1: Viết test seed (sẽ đỏ)**

```php
<?php

namespace Tests\Unit\OrderCheck;

use Tests\TestCase;

class BhytSeedTest extends TestCase
{
    /** @test */
    public function bon_quy_tac_moi_duoc_seed_o_trang_thai_TAT()
    {
        // Khong do duoc ti le khop that (ba bang danh muc tren DB dev deu 0 dong), va
        // 21.778 dich vu HIS chi 10.552 khai ma BHXH. Bat san la co the do ra hang nghin
        // vi pham ngay dau.
        $file = glob(database_path('migrations/*seed_order_check_bhyt_catalog_rules.php'));
        $this->assertNotEmpty($file, 'Chua co migration seed');

        $src = file_get_contents($file[0]);

        foreach ([
            'A_BHYT_CODE_MISSING',
            'A_BHYT_SERVICE_NOT_IN_CATALOG',
            'A_BHYT_DRUG_NOT_IN_CATALOG',
            'A_BHYT_SUPPLY_NOT_IN_CATALOG',
        ] as $ma) {
            $this->assertContains($ma, $src, "Thieu quy tac $ma trong seed");
        }

        $this->assertNotContains("'is_active' => true", $src,
            'Co quy tac seed o trang thai BAT');
    }
}
```

- [ ] **Step 2:** chạy test, xác nhận đỏ.

- [ ] **Step 3: Tầng lọc thô trong `ServiceReqScanner`**

Ngay sau `$ctx = $source->buildContext(...)`, trước `ViolationContext::fromOrderContext`:

```php
                // Tang loc THO: bo phieu khong co dong BHYT nao. Khong doi hanh vi cac
                // quy tac cu voi phieu con lai - chung van chay day du.
                if (!empty($ctx->services) && !BhytScope::coDongBhyt($ctx->services)) {
                    continue;
                }
```

Thêm `use App\Services\OrderCheck\Support\BhytScope;`.

Điều kiện `!empty($ctx->services)` là có chủ đích: phiếu **chưa có dòng dịch vụ nào** vẫn
phải đi qua các quy tắc mức phiếu (thiếu ICD, chứng chỉ hành nghề), không được bỏ.

**Lưu ý:** vòng lặp vẫn phải cập nhật watermark cho phiếu bị bỏ, nếu không lần quét sau
lại đọc lại nó mãi. Đặt `continue` **sau** đoạn tính `$maxModify`/`$maxId`, hoặc chuyển
đoạn đó lên trước `continue`.

- [ ] **Step 4: Viết migration seed**

Theo đúng khuôn `2026_06_30_100005_seed_order_check_rules.php`, bốn dòng với
`'family' => 'A'`, `'severity' => 'warning'`, `'is_active' => false`, `rule_type` là tên
lớp handler.

- [ ] **Step 5: Viết lệnh `kiemtraylenh:thu`**

`OrderCheckDryRun`, signature `kiemtraylenh:thu {--ngay=7}`:

- Quét phiếu trong N ngày gần nhất theo `intruction_time`.
- Chạy **chỉ bốn** handler mới, **bỏ qua** `is_active` (mục đích là xem trước).
- **Không ghi gì** vào `order_check_violations`.
- In bảng: theo mã quy tắc, và top 10 khoa nhiều vi phạm nhất.
- In cảnh báo rõ ràng cho quy tắc có danh mục rỗng.

Đăng ký vào `app/Console/Kernel.php`.

- [ ] **Step 6:** chạy test, xác nhận xanh.
- [ ] **Step 7:** `php -l` các file, chạy suite Unit — **359 test**.
- [ ] **Step 8:** **KHÔNG commit.** Báo lại để chủ đầu tư review.

---

## Nghiệm thu thủ công (bắt buộc)

DB dev không có danh mục lẫn dữ liệu HIS cục bộ, nên mọi mục dưới đây phải chạy trên môi
trường thật.

**Chạy migration seed trước, rồi chạy thử — đừng bật quy tắc nào cho tới sau mục 2.**

| # | Việc | Mong đợi |
|---|---|---|
| 1 | `php artisan kiemtraylenh:thu --ngay=7` | Ra bảng đếm; `order_check_violations` **không thêm dòng nào** |
| 2 | Đọc số `A_BHYT_CODE_MISSING` | Nếu quá lớn, mở vài mẫu xem trước — có thể là lỗi dữ liệu thật |
| 3 | Bật lọc BHYT, chạy quét bình thường vài giờ | Vi phạm của 9 quy tắc cũ **giảm**, không tăng |
| 4 | Đối chiếu vài vi phạm mới với hồ sơ thật | Dòng bị báo đúng là dòng BHYT, **không phải** dòng Viện phí |
| 5 | Chạy khi một bảng danh mục còn rỗng | Quy tắc tương ứng **im lặng**, không đổ vi phạm |
| 6 | Đặt `ORDER_CHECK_BHYT_PATIENT_TYPES=` (rỗng) rồi quét | Hành vi quay về đúng như trước đợt này |
| 7 | Kiểm watermark sau khi có phiếu bị lọc thô | Watermark vẫn tiến, không quét lại phiếu cũ mãi |

**Mục 4 quan trọng nhất** — chứng minh bộ lọc chạy ở mức dòng chứ không phải mức hồ sơ,
đúng sai lệch 30,17% đã đo. **Mục 7** bắt đúng cái bẫy đã ghi ở Task 4 Step 3.

## Ngoài phạm vi

1. Trần giá / tỉ lệ thanh toán (`hein_limit_price`, `hein_limit_ratio`, `do_not_use_bhyt`).
2. Đối chiếu ICD với `icd10_categories` / `icd_yhct_categories`.
3. Sửa nội dung 9 quy tắc hiện có.
4. Nhập danh mục BHXH — đã có chức năng sẵn.
