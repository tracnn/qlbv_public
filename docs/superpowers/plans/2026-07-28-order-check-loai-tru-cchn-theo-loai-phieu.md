# Kế hoạch: loại trừ kiểm CCHN người thực hiện theo loại phiếu

Spec: `docs/superpowers/specs/2026-07-28-order-check-loai-tru-cchn-theo-loai-phieu-design.md`

**Mục tiêu:** hai luật kiểm CCHN không còn bắt lỗi người thực hiện ở ba loại đơn thuốc
(6, 14, 15), và danh sách loại trừ khai báo được qua config.

**Kiến trúc:** không thêm lớp mới. Một khoá config dùng chung, hai luật đọc nó theo đúng
khuôn `MissingDiagnosisRule` đã có.

## Ràng buộc chung

- Cổng: `vendor/bin/phpunit --testsuite Unit`. Chạy trước để ghi số nền.
- Bình luận mã nguồn viết tiếng Việt không dấu.
- Test dùng `/** @test */`.
- `Violation` có `ruleCode`, `orderRefType`, `orderRefId`, `message`, `detail`, `subKey`.
- Test **truyền thẳng mảng loại trừ** vào hàm dựng, không dựa vào config — để test không vỡ
  khi đơn vị đổi `.env`.
- Commit + push lên `main` sau khi xong.

---

## Task 1: Khoá cấu hình

**Tệp:**
- Sửa: `config/order_check.php`

- [ ] **Bước 1: Thêm khoá**

Đặt ngay dưới `missing_diagnosis_exclude_type_ids` cho cùng nhóm:

```php
    // Loai phieu KHONG ap luat kiem CCHN nguoi thuc hien, CSV id (HIS_SERVICE_REQ_TYPE).
    // Mac dinh 6 Don phong kham, 14 Don tu truc, 15 Don dieu tri: nguoi thuc hien cua cac
    // phieu nay la duoc si / dieu duong cap phat, khong phai nguoi can CCHN theo nghia
    // cua luat. RONG = khong loai tru loai nao.
    //
    // Ap cho ca B_DOCTOR_NO_PRACTICE_CERT lan nua "nguoi thuc hien" cua
    // A_STAFF_CERT_NOT_IN_CATALOG.
    'practice_cert_exclude_type_ids' => env('ORDER_CHECK_PRACTICE_CERT_EXCLUDE_TYPES', '6,14,15'),
```

---

## Task 2: `B_DOCTOR_NO_PRACTICE_CERT` bỏ qua loại phiếu loại trừ

**Tệp:**
- Sửa: `app/Services/OrderCheck/RuleHandlers/Structural/DoctorPracticeCertRule.php`
- Test: `tests/Unit/OrderCheck/StructuralRulesTest.php` (đã có, bổ sung ca)

**Interfaces:**
- Consumes: `config('order_check.practice_cert_exclude_type_ids')` (Task 1)
- Produces: `DoctorPracticeCertRule::__construct(array $excludeTypeIds = null)`

- [ ] **Bước 1: Viết test đỏ**

Đọc `StructuralRulesTest` hiện có để dùng lại hàm trợ giúp dựng `OrderContext` nếu có;
nếu không thì thêm hàm riêng. Thêm các ca:

```php
    /** @test */
    public function bo_qua_loai_phieu_trong_danh_sach_loai_tru()
    {
        // Nguoi thuc hien cua don thuoc la duoc si / dieu duong cap phat.
        $r = new DoctorPracticeCertRule([6, 14, 15]);

        foreach ([6, 14, 15] as $loai) {
            $c = new OrderContext();
            $c->serviceReqId = 1;
            $c->serviceReqTypeId = $loai;
            $c->executeLoginname = 'nguoith';
            $c->executeDiploma = null;

            $this->assertCount(0, $r->check($c), "Loai $loai van bi bat");
        }
    }

    /** @test */
    public function loai_phieu_ngoai_danh_sach_van_bi_bat()
    {
        $r = new DoctorPracticeCertRule([6, 14, 15]);

        $c = new OrderContext();
        $c->serviceReqId = 1;
        $c->serviceReqTypeId = 2;
        $c->executeLoginname = 'nguoith';
        $c->executeDiploma = null;

        $this->assertCount(1, $r->check($c));
    }

    /** @test */
    public function danh_sach_loai_tru_rong_thi_bat_moi_loai()
    {
        $r = new DoctorPracticeCertRule([]);

        $c = new OrderContext();
        $c->serviceReqId = 1;
        $c->serviceReqTypeId = 6;
        $c->executeLoginname = 'nguoith';
        $c->executeDiploma = null;

        $this->assertCount(1, $r->check($c));
    }

    /** @test */
    public function loai_phieu_null_van_duoc_xet()
    {
        $r = new DoctorPracticeCertRule([6, 14, 15]);

        $c = new OrderContext();
        $c->serviceReqId = 1;
        $c->serviceReqTypeId = null;
        $c->executeLoginname = 'nguoith';
        $c->executeDiploma = null;

        $this->assertCount(1, $r->check($c));
    }
```

- [ ] **Bước 2: Chạy để xác nhận đỏ**

```bash
vendor/bin/phpunit tests/Unit/OrderCheck/StructuralRulesTest.php
```

- [ ] **Bước 3: Sửa lớp**

```php
<?php

namespace App\Services\OrderCheck\RuleHandlers\Structural;

use App\Services\OrderCheck\Contracts\RuleHandler;
use App\Services\OrderCheck\Support\OrderContext;
use App\Services\OrderCheck\Support\Violation;

class DoctorPracticeCertRule implements RuleHandler
{
    /** @var int[] loai phieu khong ap luat nay */
    protected $excludeTypeIds;

    public function __construct(array $excludeTypeIds = null)
    {
        if ($excludeTypeIds === null) {
            $csv = trim((string) config('order_check.practice_cert_exclude_type_ids', ''));
            $excludeTypeIds = $csv === '' ? [] : array_map('intval', array_filter(explode(',', $csv), 'strlen'));
        }

        $this->excludeTypeIds = $excludeTypeIds;
    }

    public function code()
    {
        return 'B_DOCTOR_NO_PRACTICE_CERT';
    }

    public function check(OrderContext $c)
    {
        // Don thuoc (Don phong kham, Don tu truc, Don dieu tri): nguoi thuc hien la duoc si
        // hoac dieu duong cap phat, khong phai nguoi can CCHN theo nghia cua luat nay.
        if ($c->serviceReqTypeId !== null
            && in_array((int) $c->serviceReqTypeId, $this->excludeTypeIds, true)) {
            return [];
        }

        $hasExecutor = !empty(trim((string) $c->executeLoginname));
        $noCert = empty(trim((string) $c->executeDiploma));

        if ($hasExecutor && $noCert) {
            return [new Violation(
                $this->code(), 'service_req', $c->serviceReqId,
                'Người thực hiện (' . $c->executeLoginname . ') chưa có/không hợp lệ chứng chỉ hành nghề',
                ['execute_loginname' => $c->executeLoginname]
            )];
        }

        return [];
    }
}
```

- [ ] **Bước 4: Chạy lại, phải xanh**

```bash
vendor/bin/phpunit --testsuite Unit
```

---

## Task 3: `A_STAFF_CERT_NOT_IN_CATALOG` bỏ qua riêng vai trò người thực hiện

**Tệp:**
- Sửa: `app/Services/OrderCheck/RuleHandlers/Clinical/StaffCertNotInCatalogRule.php`
- Test: `tests/Unit/OrderCheck/StaffCertRuleTest.php` (đã có, bổ sung ca)

**Interfaces:**
- Consumes: cùng khoá config với Task 2
- Produces: `__construct(CatalogLookup $traCchn = null, CatalogLookup $traMaBhxh = null, array $excludeTypeIds = null)`

Khác Task 2: bỏ qua **riêng vai trò `th`**, giữ vai trò `bs`. Bác sĩ ra đơn thuốc vẫn phải
có CCHN hợp lệ.

- [ ] **Bước 1: Viết test đỏ, thêm vào `StaffCertRuleTest`**

Hàm trợ giúp `ctx()` hiện có chưa đặt `serviceReqTypeId`; thêm tham số thứ tư và hàm `tra()`
nhận thêm danh sách loại trừ:

```php
    /** @test */
    public function loai_phieu_loai_tru_chi_bo_qua_nguoi_thuc_hien()
    {
        $r = $this->tra(['C1' => [['tu' => '', 'den' => '']]], [], [6, 14, 15]);

        $c = $this->ctx('X1', 'X2', 20240601080000);
        $c->serviceReqTypeId = 6;

        $vi = $r->check($c);

        $this->assertCount(1, $vi, 'Phai con lai dung vi pham cua bac si chi dinh');
        $this->assertSame('bs', $vi[0]->detail['vai_tro']);
    }

    /** @test */
    public function loai_phieu_loai_tru_chi_nguoi_thuc_hien_sai_thi_im_lang()
    {
        $r = $this->tra(['C1' => [['tu' => '', 'den' => '']]], [], [6, 14, 15]);

        $c = $this->ctx('C1', 'X2', 20240601080000);
        $c->serviceReqTypeId = 14;

        $this->assertCount(0, $r->check($c));
    }

    /** @test */
    public function loai_phieu_ngoai_danh_sach_van_xet_ca_hai_vai_tro()
    {
        $r = $this->tra(['C1' => [['tu' => '', 'den' => '']]], [], [6, 14, 15]);

        $c = $this->ctx('X1', 'X2', 20240601080000);
        $c->serviceReqTypeId = 2;

        $this->assertCount(2, $r->check($c));
    }

    /** @test */
    public function loai_phieu_null_van_xet_ca_hai_vai_tro()
    {
        $r = $this->tra(['C1' => [['tu' => '', 'den' => '']]], [], [6, 14, 15]);

        $c = $this->ctx('X1', 'X2', 20240601080000);
        $c->serviceReqTypeId = null;

        $this->assertCount(2, $r->check($c));
    }

    /** @test */
    public function loai_tru_het_cchn_can_tra_thi_khong_cham_danh_muc()
    {
        // Bac si chi dinh khong co CCHN, nguoi thuc hien bi loai tru -> khong con gi de tra.
        // Danh muc de o trang thai chua nap: neu luat cham toi no, sanSang() se hoi CSDL.
        $lkCchn = new CatalogLookup('medical_staffs', 'macchn');
        $lkBhxh = new CatalogLookup('medical_staffs', 'ma_bhxh');
        $lkCchn->datSanChoTest([], ['C1' => [['tu' => '', 'den' => '']]]);
        $lkBhxh->datSanChoTest([]);

        $r = new StaffCertNotInCatalogRule($lkCchn, $lkBhxh, [6]);

        $c = $this->ctx(null, 'X2', 20240601080000);
        $c->serviceReqTypeId = 6;

        $this->assertCount(0, $r->check($c));
    }
```

Sửa hai hàm trợ giúp:

```php
    private function ctx($cchnBacSi, $cchnNguoiTh, $moc = 20240601080000, $loaiPhieu = null)
    {
        $c = new OrderContext();
        $c->serviceReqId = 222;
        $c->serviceReqCode = 'PK002';
        $c->serviceReqTypeId = $loaiPhieu;
        $c->requestDiploma = $cchnBacSi;
        $c->executeDiploma = $cchnNguoiTh;
        $c->intructionTime = $moc;

        return $c;
    }

    private function tra(array $macchn, array $maBhxh = [], array $loaiTru = [])
    {
        $lkCchn = new CatalogLookup('medical_staffs', 'macchn');
        $lkCchn->datSanChoTest([], $macchn);

        $lkBhxh = new CatalogLookup('medical_staffs', 'ma_bhxh');
        $lkBhxh->datSanChoTest([], $maBhxh);

        return new StaffCertNotInCatalogRule($lkCchn, $lkBhxh, $loaiTru);
    }
```

Chín ca kiểm hiện có gọi `tra(...)` với một hoặc hai tham số nên không phải sửa — tham số
thứ ba mặc định `[]` nghĩa là không loại trừ.

- [ ] **Bước 2: Chạy để xác nhận đỏ**

```bash
vendor/bin/phpunit tests/Unit/OrderCheck/StaffCertRuleTest.php
```

- [ ] **Bước 3: Sửa lớp**

Thêm thuộc tính và tham số hàm dựng:

```php
    /** @var int[] loai phieu khong xet vai tro nguoi thuc hien */
    protected $excludeTypeIds;

    public function __construct(
        CatalogLookup $traCchn = null,
        CatalogLookup $traMaBhxh = null,
        array $excludeTypeIds = null
    ) {
        $this->traCchn = $traCchn ?: new CatalogLookup('medical_staffs', 'macchn');
        $this->traMaBhxh = $traMaBhxh ?: new CatalogLookup('medical_staffs', 'ma_bhxh');

        if ($excludeTypeIds === null) {
            $csv = trim((string) config('order_check.practice_cert_exclude_type_ids', ''));
            $excludeTypeIds = $csv === '' ? [] : array_map('intval', array_filter(explode(',', $csv), 'strlen'));
        }

        $this->excludeTypeIds = $excludeTypeIds;
    }
```

Trong `check()`, dựng mảng vai trò rồi bỏ vai trò `th` nếu loại phiếu bị loại trừ. Đặt đoạn
này **trước** khối `$can` để danh sách CCHN cần tra cũng thu hẹp theo:

```php
        $vaiTro = [
            'bs' => ['nhan' => 'bác sĩ chỉ định', 'cchn' => trim((string) $c->requestDiploma)],
            'th' => ['nhan' => 'người thực hiện', 'cchn' => trim((string) $c->executeDiploma)],
        ];

        // Don thuoc: nguoi thuc hien la duoc si / dieu duong cap phat. Chi bo vai tro do,
        // bac si ra don van phai co CCHN hop le.
        if ($c->serviceReqTypeId !== null
            && in_array((int) $c->serviceReqTypeId, $this->excludeTypeIds, true)) {
            unset($vaiTro['th']);
        }
```

Phần còn lại của `check()` giữ nguyên: nó đã duyệt `$vaiTro` nên tự đúng.

Lưu ý thứ tự: khối `if (!$this->traCchn->sanSang())` hiện nằm **đầu** hàm. Chuyển nó xuống
**sau** khi tính `$can` và kiểm `empty($can)`, để ca "loại trừ hết CCHN cần tra" không chạm
cơ sở dữ liệu. Thứ tự mới:

1. Tính `$ngay`, trả về sớm nếu null.
2. Dựng `$vaiTro`, bỏ `th` nếu loại trừ.
3. Gom `$can`, trả về sớm nếu rỗng.
4. `if (!$this->traCchn->sanSang()) return [];`
5. `nap()` rồi duyệt.

- [ ] **Bước 4: Chạy lại, phải xanh**

```bash
vendor/bin/phpunit --testsuite Unit
```

---

## Task 4: Kiểm trên HIS thật rồi commit

- [ ] **Bước 1: Chạy thử**

```bash
php artisan kiemtraylenh:thu --ngay=7 --lo=2000
```

Lệnh này không chạy `B_DOCTOR_NO_PRACTICE_CERT` (chỉ chạy nhóm luật danh mục), nên dùng để
xác nhận **không có gì vỡ**, không phải để đo hiệu quả loại trừ.

- [ ] **Bước 2: Đo hiệu quả loại trừ**

Kiểm bằng cách chạy trực tiếp luật trên một lô phiếu thật, đếm trước và sau loại trừ. Viết
script tạm trong thư mục scratchpad, không thêm tệp vào dự án.

Kỳ vọng: trước loại trừ ~4.247 điều kiện đúng trên 7 ngày; sau loại trừ còn ~1.

- [ ] **Bước 3: Chạy toàn bộ bộ Unit lần cuối**

```bash
vendor/bin/phpunit --testsuite Unit
```

- [ ] **Bước 4: Commit và push**

```bash
git add config/order_check.php app/Services/OrderCheck docs/superpowers tests/Unit/OrderCheck
```

Commit message ghi rõ: loại trừ **che** vấn đề chứ không sửa gốc, 3.846/4.246 vi phạm là do
`tranghth-kd` chưa khai CCHN trong HIS.
