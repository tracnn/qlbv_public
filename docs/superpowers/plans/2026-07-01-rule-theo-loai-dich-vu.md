# Tổ chức luật cấp phiếu theo loại dịch vụ — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) hoặc superpowers:executing-plans. Steps dùng checkbox (`- [ ]`).

**Goal:** Tách phần chạy luật của `ServiceReqScanner` thành file luật chung (`CommonRules`) + 18 file theo từng loại dịch vụ (`Types/*Rules.php`), điều phối qua `ServiceReqRuleRegistry`, để lập trình viên biết chính xác nơi thêm luật mới.

**Architecture:** Handler cấp phiếu được gom qua `ServiceReqRuleRegistry::common()` (áp mọi loại) + `forType($typeId)` (áp riêng 1 loại). `ServiceReqScanner`, với mỗi phiếu, chạy `common + forType(loại của phiếu)`. Hành vi hiện tại giữ nguyên (mọi luật đang ở Common). Các class handler cũ không di chuyển.

**Tech Stack:** PHP 7 / Laravel 5.5, PHPUnit.

**Tham chiếu:** spec `docs/superpowers/specs/2026-07-01-rule-theo-loai-dich-vu-design.md`.

## Bối cảnh (KHÔNG tạo lại)
- Interface `App\Services\OrderCheck\Contracts\RuleHandler` { `code()`, `check(OrderContext)` }.
- Handler hiện có: `RuleHandlers\Structural\{DischargeBeforeAdmissionRule,OrderTimeOutOfStayRule,ExecuteBeforeOrderRule,DoctorPracticeCertRule}`, `RuleHandlers\Clinical\MissingDiagnosisRule`.
- Registry cũ (sẽ XÓA): `RuleHandlers\StructuralRuleRegistry`, `RuleHandlers\ClinicalServiceReqRuleRegistry`.
- `Scanners\ServiceReqScanner` dùng 2 registry cũ.
- `OrderContext->serviceReqTypeId` (int|null) đã có.

## File Structure
**Tạo mới:**
- `app/Services/OrderCheck/Contracts/TypeRules.php`
- `app/Services/OrderCheck/RuleHandlers/ServiceReq/CommonRules.php`
- `app/Services/OrderCheck/RuleHandlers/ServiceReq/ServiceReqRuleRegistry.php`
- `app/Services/OrderCheck/RuleHandlers/ServiceReq/Types/*Rules.php` (18 file)
- `tests/Unit/OrderCheck/ServiceReqRuleRegistryTest.php`

**Sửa:**
- `app/Services/OrderCheck/Scanners/ServiceReqScanner.php`
- `docs/order-check/HUONG-DAN-THEM-QUY-TAC.md`

**Xóa:**
- `app/Services/OrderCheck/RuleHandlers/StructuralRuleRegistry.php`
- `app/Services/OrderCheck/RuleHandlers/ClinicalServiceReqRuleRegistry.php`

---

## Task 1: Interface TypeRules

**Files:**
- Create: `app/Services/OrderCheck/Contracts/TypeRules.php`

- [ ] **Step 1: Tạo interface**

```php
<?php

namespace App\Services\OrderCheck\Contracts;

/**
 * Nhóm luật cấp phiếu áp cho MỘT loại dịch vụ (SERVICE_REQ_TYPE).
 */
interface TypeRules
{
    /** Id loại phiếu (HIS_SERVICE_REQ_TYPE). @return int */
    public function typeId();

    /** Handler CHỈ áp cho loại này. @return \App\Services\OrderCheck\Contracts\RuleHandler[] */
    public function handlers();
}
```

- [ ] **Step 2: Verify cú pháp**

Run: `php -l app/Services/OrderCheck/Contracts/TypeRules.php`
Expected: `No syntax errors detected`

- [ ] **Step 3: Commit** (bỏ qua nếu người điều phối yêu cầu không commit)

```bash
git add app/Services/OrderCheck/Contracts/TypeRules.php
git commit -m "feat(order-check): interface TypeRules (nhom luat theo loai dich vu)"
```

---

## Task 2: 18 file luật theo loại (Types/*Rules.php)

**Files:**
- Create: 18 file trong `app/Services/OrderCheck/RuleHandlers/ServiceReq/Types/`

Mỗi file theo **đúng template** sau (thay `<CLASS>`, `<ID>`, `<TEN>`):

```php
<?php

namespace App\Services\OrderCheck\RuleHandlers\ServiceReq\Types;

use App\Services\OrderCheck\Contracts\TypeRules;

/**
 * Luật CHỈ áp cho phiếu loại "<TEN>" (SERVICE_REQ_TYPE id=<ID>).
 */
class <CLASS> implements TypeRules
{
    public function typeId()
    {
        return <ID>;
    }

    public function handlers()
    {
        // Thêm luật CHỈ áp cho loại "<TEN>" vào đây, vd: return [new <Ten>Rule()];
        return [];
    }
}
```

- [ ] **Step 1: Tạo 18 file** — bảng (File | CLASS | ID | TEN):

| File | `<CLASS>` | `<ID>` | `<TEN>` |
|---|---|---|---|
| `KhamRules.php` | KhamRules | 1 | Khám |
| `XetNghiemRules.php` | XetNghiemRules | 2 | Xét nghiệm |
| `ChanDoanHinhAnhRules.php` | ChanDoanHinhAnhRules | 3 | Chẩn đoán hình ảnh |
| `ThuThuatRules.php` | ThuThuatRules | 4 | Thủ thuật |
| `ThamDoChucNangRules.php` | ThamDoChucNangRules | 5 | Thăm dò chức năng |
| `DonPhongKhamRules.php` | DonPhongKhamRules | 6 | Đơn phòng khám |
| `GiuongRules.php` | GiuongRules | 7 | Giường |
| `NoiSoiRules.php` | NoiSoiRules | 8 | Nội soi |
| `SieuAmRules.php` | SieuAmRules | 9 | Siêu âm |
| `PhauThuatRules.php` | PhauThuatRules | 10 | Phẫu thuật |
| `KhacRules.php` | KhacRules | 11 | Khác |
| `PhucHoiChucNangRules.php` | PhucHoiChucNangRules | 12 | Phục hồi chức năng |
| `GiaiPhauBenhRules.php` | GiaiPhauBenhRules | 13 | Giải phẫu bệnh |
| `DonTuTrucRules.php` | DonTuTrucRules | 14 | Đơn tủ trực |
| `DonDieuTriRules.php` | DonDieuTriRules | 15 | Đơn điều trị |
| `DonMauRules.php` | DonMauRules | 16 | Đơn máu |
| `SuatAnRules.php` | SuatAnRules | 17 | Suất ăn |
| `NgoaiKcbRules.php` | NgoaiKcbRules | 18 | Ngoài khám chữa bệnh |

Ví dụ file đầy đủ (`KhamRules.php`):
```php
<?php

namespace App\Services\OrderCheck\RuleHandlers\ServiceReq\Types;

use App\Services\OrderCheck\Contracts\TypeRules;

/**
 * Luật CHỈ áp cho phiếu loại "Khám" (SERVICE_REQ_TYPE id=1).
 */
class KhamRules implements TypeRules
{
    public function typeId()
    {
        return 1;
    }

    public function handlers()
    {
        // Thêm luật CHỈ áp cho loại "Khám" vào đây, vd: return [new KhamXxxRule()];
        return [];
    }
}
```

- [ ] **Step 2: Verify cú pháp toàn bộ**

Run: `for f in app/Services/OrderCheck/RuleHandlers/ServiceReq/Types/*.php; do php -l "$f" >/dev/null || echo "FAIL $f"; done; echo done`
Expected: in ra `done` không có dòng `FAIL`.

Run: `ls app/Services/OrderCheck/RuleHandlers/ServiceReq/Types/*.php | wc -l`
Expected: `18`

- [ ] **Step 3: Commit** (bỏ qua nếu được yêu cầu)

```bash
git add app/Services/OrderCheck/RuleHandlers/ServiceReq/Types/
git commit -m "feat(order-check): 18 file luat theo loai dich vu (rong san)"
```

---

## Task 3: CommonRules

**Files:**
- Create: `app/Services/OrderCheck/RuleHandlers/ServiceReq/CommonRules.php`

- [ ] **Step 1: Tạo CommonRules**

```php
<?php

namespace App\Services\OrderCheck\RuleHandlers\ServiceReq;

use App\Services\OrderCheck\RuleHandlers\Structural\DischargeBeforeAdmissionRule;
use App\Services\OrderCheck\RuleHandlers\Structural\OrderTimeOutOfStayRule;
use App\Services\OrderCheck\RuleHandlers\Structural\ExecuteBeforeOrderRule;
use App\Services\OrderCheck\RuleHandlers\Structural\DoctorPracticeCertRule;
use App\Services\OrderCheck\RuleHandlers\Clinical\MissingDiagnosisRule;

/**
 * Luật cấp phiếu áp cho MỌI loại dịch vụ.
 * Thêm luật áp cho tất cả loại vào mảng handlers() dưới đây.
 */
class CommonRules
{
    /** @return \App\Services\OrderCheck\Contracts\RuleHandler[] */
    public static function handlers()
    {
        return [
            new DischargeBeforeAdmissionRule(),
            new OrderTimeOutOfStayRule(),
            new ExecuteBeforeOrderRule(),
            new DoctorPracticeCertRule(),
            new MissingDiagnosisRule(),
        ];
    }
}
```

- [ ] **Step 2: Verify**

Run: `php -l app/Services/OrderCheck/RuleHandlers/ServiceReq/CommonRules.php`
Expected: `No syntax errors detected`

- [ ] **Step 3: Commit** (bỏ qua nếu được yêu cầu)

```bash
git add app/Services/OrderCheck/RuleHandlers/ServiceReq/CommonRules.php
git commit -m "feat(order-check): CommonRules (luat ap moi loai phieu)"
```

---

## Task 4: ServiceReqRuleRegistry + test (TDD)

**Files:**
- Create: `app/Services/OrderCheck/RuleHandlers/ServiceReq/ServiceReqRuleRegistry.php`
- Test: `tests/Unit/OrderCheck/ServiceReqRuleRegistryTest.php`

- [ ] **Step 1: Viết test thất bại**

```php
<?php

namespace Tests\Unit\OrderCheck;

use Tests\TestCase;
use App\Services\OrderCheck\RuleHandlers\ServiceReq\ServiceReqRuleRegistry;
use App\Services\OrderCheck\Contracts\RuleHandler;

class ServiceReqRuleRegistryTest extends TestCase
{
    public function test_common_tra_ve_cac_handler_ap_moi_loai()
    {
        $handlers = ServiceReqRuleRegistry::common();
        $this->assertCount(5, $handlers);
        $codes = array_map(function (RuleHandler $h) { return $h->code(); }, $handlers);
        $this->assertContains('A_MISSING_DIAGNOSIS', $codes);
        $this->assertContains('B_DOCTOR_NO_PRACTICE_CERT', $codes);
    }

    public function test_for_type_chua_co_luat_rieng_tra_mang_rong()
    {
        $this->assertSame([], ServiceReqRuleRegistry::forType(2));   // Xét nghiệm
        $this->assertSame([], ServiceReqRuleRegistry::forType(999)); // không tồn tại
        $this->assertSame([], ServiceReqRuleRegistry::forType(null));
    }
}
```

- [ ] **Step 2: Chạy test → FAIL**

Run: `vendor/bin/phpunit --filter ServiceReqRuleRegistryTest`
Expected: FAIL với "Class '...ServiceReqRuleRegistry' not found"

- [ ] **Step 3: Cài đặt registry**

```php
<?php

namespace App\Services\OrderCheck\RuleHandlers\ServiceReq;

use App\Services\OrderCheck\RuleHandlers\ServiceReq\Types;

/**
 * Điều phối luật cấp phiếu:
 * - common(): áp cho MỌI loại (xem CommonRules).
 * - forType($id): CHỈ áp cho loại phiếu id (xem Types/<Loại>Rules.php).
 */
class ServiceReqRuleRegistry
{
    /** @var array<int,\App\Services\OrderCheck\Contracts\TypeRules>|null */
    protected static $typeMap;

    /** @return \App\Services\OrderCheck\Contracts\RuleHandler[] */
    public static function common()
    {
        return CommonRules::handlers();
    }

    /** @return \App\Services\OrderCheck\Contracts\RuleHandler[] */
    public static function forType($typeId)
    {
        if ($typeId === null) {
            return [];
        }
        if (self::$typeMap === null) {
            self::$typeMap = [];
            foreach (self::typeRules() as $tr) {
                self::$typeMap[$tr->typeId()] = $tr;
            }
        }
        $id = (int) $typeId;
        return isset(self::$typeMap[$id]) ? self::$typeMap[$id]->handlers() : [];
    }

    /**
     * Danh sách 18 nhóm luật theo loại. Thêm loại mới = thêm 1 dòng ở đây.
     * @return \App\Services\OrderCheck\Contracts\TypeRules[]
     */
    protected static function typeRules()
    {
        return [
            new Types\KhamRules(),
            new Types\XetNghiemRules(),
            new Types\ChanDoanHinhAnhRules(),
            new Types\ThuThuatRules(),
            new Types\ThamDoChucNangRules(),
            new Types\DonPhongKhamRules(),
            new Types\GiuongRules(),
            new Types\NoiSoiRules(),
            new Types\SieuAmRules(),
            new Types\PhauThuatRules(),
            new Types\KhacRules(),
            new Types\PhucHoiChucNangRules(),
            new Types\GiaiPhauBenhRules(),
            new Types\DonTuTrucRules(),
            new Types\DonDieuTriRules(),
            new Types\DonMauRules(),
            new Types\SuatAnRules(),
            new Types\NgoaiKcbRules(),
        ];
    }
}
```

- [ ] **Step 4: Chạy test → PASS**

Run: `vendor/bin/phpunit --filter ServiceReqRuleRegistryTest`
Expected: PASS (2 tests)

- [ ] **Step 5: Commit** (bỏ qua nếu được yêu cầu)

```bash
git add app/Services/OrderCheck/RuleHandlers/ServiceReq/ServiceReqRuleRegistry.php tests/Unit/OrderCheck/ServiceReqRuleRegistryTest.php
git commit -m "feat(order-check): ServiceReqRuleRegistry (common + forType) + test"
```

---

## Task 5: Refactor ServiceReqScanner + xóa registry cũ

**Files:**
- Modify: `app/Services/OrderCheck/Scanners/ServiceReqScanner.php`
- Delete: `app/Services/OrderCheck/RuleHandlers/StructuralRuleRegistry.php`
- Delete: `app/Services/OrderCheck/RuleHandlers/ClinicalServiceReqRuleRegistry.php`

- [ ] **Step 1: Sửa import trong ServiceReqScanner**

Trong `app/Services/OrderCheck/Scanners/ServiceReqScanner.php`, THAY 2 dòng use:
```php
use App\Services\OrderCheck\RuleHandlers\StructuralRuleRegistry;
use App\Services\OrderCheck\RuleHandlers\ClinicalServiceReqRuleRegistry;
```
bằng:
```php
use App\Services\OrderCheck\RuleHandlers\ServiceReq\ServiceReqRuleRegistry;
```

- [ ] **Step 2: Sửa phần dựng danh sách handler**

Tìm đoạn (đầu method `scan`):
```php
        $source = $engine->source();
        $rulesByCode = $engine->activeRules();
        $handlers = array_merge(
            StructuralRuleRegistry::handlers(),
            ClinicalServiceReqRuleRegistry::handlers()
        );
```
THAY bằng (bỏ `$handlers` ở đây, tính theo từng phiếu):
```php
        $source = $engine->source();
        $rulesByCode = $engine->activeRules();
        $commonHandlers = ServiceReqRuleRegistry::common();
```

- [ ] **Step 3: Sửa vòng lặp để gộp luật theo loại**

Tìm trong vòng `foreach ($rows as $row)`:
```php
                $ctx = $source->buildContext($row, isset($servicesMap[(int) $row->id]) ? $servicesMap[(int) $row->id] : []);
                $vctx = ViolationContext::fromOrderContext($ctx);

                foreach ($handlers as $handler) {
```
THAY bằng:
```php
                $ctx = $source->buildContext($row, isset($servicesMap[(int) $row->id]) ? $servicesMap[(int) $row->id] : []);
                $vctx = ViolationContext::fromOrderContext($ctx);

                $handlers = array_merge($commonHandlers, ServiceReqRuleRegistry::forType($ctx->serviceReqTypeId));
                foreach ($handlers as $handler) {
```

- [ ] **Step 4: Xóa 2 registry cũ**

```bash
rm app/Services/OrderCheck/RuleHandlers/StructuralRuleRegistry.php
rm app/Services/OrderCheck/RuleHandlers/ClinicalServiceReqRuleRegistry.php
```

- [ ] **Step 5: Verify không còn tham chiếu + cú pháp**

Run: `grep -rn "StructuralRuleRegistry\|ClinicalServiceReqRuleRegistry" app/ tests/`
Expected: KHÔNG có kết quả (rỗng).

Run: `php -l app/Services/OrderCheck/Scanners/ServiceReqScanner.php`
Expected: `No syntax errors detected`

- [ ] **Step 6: Commit** (bỏ qua nếu được yêu cầu)

```bash
git add app/Services/OrderCheck/Scanners/ServiceReqScanner.php app/Services/OrderCheck/RuleHandlers/StructuralRuleRegistry.php app/Services/OrderCheck/RuleHandlers/ClinicalServiceReqRuleRegistry.php
git commit -m "refactor(order-check): ServiceReqScanner dung ServiceReqRuleRegistry (common + forType); xoa 2 registry cu"
```

---

## Task 6: Regression + verify e2e + tài liệu

**Files:**
- Modify: `docs/order-check/HUONG-DAN-THEM-QUY-TAC.md`

- [ ] **Step 1: Regression toàn bộ**

Run: `vendor/bin/phpunit tests/Unit/OrderCheck`
Expected: PASS toàn bộ (các test cũ + ServiceReqRuleRegistryTest 2 = 37 tests).

- [ ] **Step 2: Verify engine chạy thật (hành vi không đổi)**

Run: `php artisan kiemtraylenh:scan --once`
Expected: in `Quet xong: N phieu, M vi pham moi, X.XXs` không lỗi (các luật Họ B + thiếu CĐ vẫn chạy vì nằm ở Common).

- [ ] **Step 3: Cập nhật tài liệu hướng dẫn**

Trong `docs/order-check/HUONG-DAN-THEM-QUY-TAC.md`, phần §3 (Loại A — Handler cấp phiếu), thêm ghi chú sau ngay dưới tiêu đề §3:

```markdown
> **Tổ chức theo loại dịch vụ:** handler cấp phiếu được điều phối qua
> `RuleHandlers/ServiceReq/ServiceReqRuleRegistry`:
> - Luật áp **mọi loại phiếu** → thêm vào `RuleHandlers/ServiceReq/CommonRules::handlers()`.
> - Luật **chỉ cho một loại** (vd CĐHA) → mở `RuleHandlers/ServiceReq/Types/<Loại>Rules.php`,
>   thêm handler vào `handlers()` (vd `ChanDoanHinhAnhRules`). Không cần sửa scanner.
> Registry cũ `StructuralRuleRegistry`/`ClinicalServiceReqRuleRegistry` đã bỏ.
```

- [ ] **Step 4: Commit** (bỏ qua nếu được yêu cầu)

```bash
git add docs/order-check/HUONG-DAN-THEM-QUY-TAC.md
git commit -m "docs(order-check): huong dan them luat theo loai dich vu"
```

---

## Self-Review (đã thực hiện khi viết plan)

**1. Spec coverage:**
- Interface TypeRules → Task 1. ✅
- 18 file loại (rỗng + comment) → Task 2. ✅
- CommonRules (5 handler) → Task 3. ✅
- ServiceReqRuleRegistry (common + forType, khai báo 18 instance) → Task 4. ✅
- Refactor ServiceReqScanner + xóa 2 registry cũ → Task 5. ✅
- Test registry, giữ test handler cũ → Task 4 + Task 6. ✅
- Cập nhật tài liệu → Task 6. ✅
- Không đụng scanner khác → không có task nào chạm. ✅

**2. Placeholder scan:** template Task 2 có bảng 18 dòng đầy đủ (CLASS/ID/TEN) + ví dụ file hoàn chỉnh → không phải placeholder. Mọi step có code/lệnh + output kỳ vọng.

**3. Type consistency:** `TypeRules::typeId()/handlers()` (Task 1) khớp cách dùng ở Task 2 (18 file implement) và Task 4 (registry gọi `->typeId()`/`->handlers()`). `ServiceReqRuleRegistry::common()/forType()` (Task 4) khớp cách gọi ở ServiceReqScanner (Task 5). `CommonRules::handlers()` (Task 3) khớp registry (Task 4). `$ctx->serviceReqTypeId` (đã có sẵn) dùng ở Task 5. ✅

**Lưu ý PHP 7:** interface không khai báo return type (theo style `RuleHandler` hiện có). `forType` xử lý `null` (phiếu có thể chưa có loại) → trả `[]`.
