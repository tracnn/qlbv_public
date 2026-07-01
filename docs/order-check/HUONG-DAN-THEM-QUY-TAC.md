# Hướng dẫn thêm quy tắc mới — Module Kiểm tra sai sót y lệnh

Tài liệu cho nhà phát triển muốn bổ sung quy tắc (rule) bắt lỗi y lệnh mới. Đọc kỹ phần "Kiến trúc" rồi chọn đúng "loại quy tắc" để theo bộ bước tương ứng.

---

## 1. Tổng quan kiến trúc

Module **không gọi API HIS**, không trigger; mà **định kỳ quét (incremental) các bảng HIS** qua connection `HISPro` (chỉ SELECT), chạy các quy tắc, ghi vi phạm vào MySQL `qlbv`.

```
Command: php artisan kiemtraylenh:scan   (loop + sleep, chạy bằng nssm service)
   └─> OrderCheckEngine::run()
         └─> với mỗi Scanner trong ScannerRegistry::all():
               Scanner::scan(engine, limit)
                 1. đọc watermark của source (engine->getWatermark)
                 2. HisOrderSource::fetch...()  -> lấy bản ghi MỚI từ HIS
                 3. dựng OrderContext / lấy dữ liệu
                 4. chạy RuleHandler / logic -> Violation[]
                 5. engine->persist(violation, context, rule)   (idempotent qua dedup_key)
                 6. advance watermark
```

### Các thành phần chính (đường dẫn)
| Thành phần | File |
|---|---|
| Engine điều phối | `app/Services/OrderCheck/OrderCheckEngine.php` |
| Interface Scanner | `app/Services/OrderCheck/Contracts/Scanner.php` |
| Danh sách Scanner | `app/Services/OrderCheck/Scanners/ScannerRegistry.php` |
| Các Scanner | `app/Services/OrderCheck/Scanners/*.php` |
| Interface RuleHandler | `app/Services/OrderCheck/Contracts/RuleHandler.php` |
| Handler cấp phiếu (class logic) | `app/Services/OrderCheck/RuleHandlers/Structural/*`, `.../Clinical/*` |
| Điều phối luật cấp phiếu **theo loại DV** | `RuleHandlers/ServiceReq/{CommonRules, ServiceReqRuleRegistry}.php` + `ServiceReq/Types/*Rules.php` (18 loại) |
| Đọc HIS + dựng context + resolver tên | `app/Services/OrderCheck/HisOrderSource.php` |
| DTO | `app/Services/OrderCheck/Support/{OrderContext,OrderService,Violation,ViolationContext}.php` |
| Bảng cấu hình luật | `order_check_rules` (model `app/Models/OrderCheck/OrderCheckRule.php`) |
| Bảng watermark | `order_check_watermarks` |
| Bảng vi phạm | `order_check_violations` |
| Nhật ký mỗi lần chạy | `order_check_rule_logs` |
| Danh mục tham chiếu tự quản | `order_check_ref_service_restriction` (mẫu cho luật cần dữ liệu ngoài HIS) |

### Khái niệm cốt lõi
- **Rule là data-driven:** mỗi quy tắc có 1 bản ghi trong `order_check_rules` (`code`, `family`, `rule_type`, `name`, `severity`, `is_active`, `params`, `scope`). Scanner/handler **chỉ chạy khi `is_active = 1`** (kiểm qua `engine->activeRules()`). Tắt 1 luật = `UPDATE order_check_rules SET is_active=0 WHERE code='...'`.
- **Watermark (per source_key):** vị trí đã quét tới của từng bảng nguồn. Quét **chỉ bản ghi mới** kể từ watermark → không quét lại toàn bộ. Mốc khởi tạo = thời điểm deploy (không backfill lịch sử).
- **dedup_key:** `ruleCode:orderRefType:orderRefId:subKey`. `persist()` ghi idempotent theo key này → chạy lại không nhân đôi vi phạm; vi phạm đã `processed`/`false_positive` không bị "hồi sinh".
- **severity:** `info | warning | critical`.

---

## 2. Chọn loại quy tắc (bảng quyết định)

| Tình huống | Loại | Độ khó | Đi tới |
|---|---|---|---|
| Chỉ xét dữ liệu của **1 phiếu chỉ định** (các cột `HIS_SERVICE_REQ` + đợt điều trị) | **A. Handler cấp phiếu** | ⭐ dễ nhất | §3 |
| Xét **từng dòng** của bảng HIS khác (đơn thuốc, log tương tác...) | **B. Scanner nguồn mới** | ⭐⭐ | §4 |
| Cần gom **toàn bộ item của 1 đợt điều trị** (trùng DV, trùng hoạt chất...) | **C. Scanner cấp đợt** | ⭐⭐⭐ | §5 |
| Cần **dữ liệu tham chiếu không có trong HIS** (danh mục tự nhập) | **D. Reference-data** | ⭐⭐⭐ | §6 |

> Nếu phân vân: phần lớn quy tắc mới rơi vào **loại A** (chỉ cần thêm 1 class handler + seed 1 dòng rule). Hãy ưu tiên loại A nếu dữ liệu nằm trên `HIS_SERVICE_REQ`/đợt điều trị.

---

## 3. Loại A — Handler cấp phiếu chỉ định (dễ nhất)

> **Tổ chức theo loại dịch vụ (SERVICE_REQ_TYPE):** handler cấp phiếu được điều phối qua `RuleHandlers/ServiceReq/ServiceReqRuleRegistry`:
> - Luật áp **mọi loại phiếu** → thêm vào `RuleHandlers/ServiceReq/CommonRules::handlers()`.
> - Luật **chỉ cho một loại** (vd CĐHA) → mở `RuleHandlers/ServiceReq/Types/<Loại>Rules.php` (đã tạo sẵn 18 file theo loại), thêm handler vào `handlers()` — vd `ChanDoanHinhAnhRules` (id=3). Không cần sửa scanner/engine.
>
> `ServiceReqScanner` tự chạy `common() + forType(loại của phiếu)` cho mỗi phiếu. (Registry cũ `StructuralRuleRegistry`/`ClinicalServiceReqRuleRegistry` đã bỏ.)

Chạy trong `ServiceReqScanner` (nguồn `his_service_req`, quét theo `MODIFY_TIME`). Handler nhận sẵn `OrderContext` (đã có patient, đợt điều trị, người chỉ định/thực hiện, ICD, thời gian vào/ra, danh sách dịch vụ con...).

### Bước 1 — Viết handler (TDD)
Tạo `app/Services/OrderCheck/RuleHandlers/Clinical/<TenRule>.php` (Họ A lâm sàng) **hoặc** `.../Structural/<TenRule>.php` (Họ B cấu trúc/thời gian):

```php
<?php
namespace App\Services\OrderCheck\RuleHandlers\Clinical;

use App\Services\OrderCheck\Contracts\RuleHandler;
use App\Services\OrderCheck\Support\OrderContext;
use App\Services\OrderCheck\Support\Violation;

class ViDuRule implements RuleHandler
{
    public function code()
    {
        return 'A_VI_DU';   // trùng với cột code trong order_check_rules
    }

    public function check(OrderContext $c)
    {
        // điều kiện sai sót...
        if (/* phát hiện lỗi */) {
            return [new Violation(
                $this->code(),
                'service_req',          // order_ref_type
                $c->serviceReqId,       // order_ref_id
                'Mô tả vi phạm...',
                ['key' => 'gia tri']    // detail (JSON), tuỳ chọn
                // , 'subkey'           // tham số 6: subKey — chỉ cần khi 1 phiếu có thể sinh >1 vi phạm cùng rule
            )];
        }
        return [];
    }
}
```

Viết test thuần tại `tests/Unit/OrderCheck/<TenRule>Test.php` (dựng `OrderContext` thủ công, không cần DB). Tham khảo `StructuralRulesTest.php` hoặc `MissingDiagnosisRuleTest.php`.

> **Cần một cột HIS chưa có trong `OrderContext`?** Xem §3.1.

### Bước 2 — Đăng ký handler (theo loại dịch vụ)
- Luật áp **mọi loại phiếu** → thêm vào `RuleHandlers/ServiceReq/CommonRules::handlers()`.
- Luật **chỉ cho một loại** (vd CĐHA id=3) → mở `RuleHandlers/ServiceReq/Types/ChanDoanHinhAnhRules.php`, thêm vào `handlers()`.

```php
// vd CommonRules.php (áp mọi loại) HOẶC Types/<Loại>Rules.php (áp 1 loại):
public function handlers()   // CommonRules dùng `public static function handlers()`
{
    return [
        // ...các handler cũ...
        new ViDuRule(),
    ];
}
```

### Bước 3 — Seed bản ghi rule
Tạo migration mới `database/migrations/<timestamp>_seed_rule_a_vi_du.php`:

```php
public function up()
{
    if (!DB::table('order_check_rules')->where('code', 'A_VI_DU')->exists()) {
        DB::table('order_check_rules')->insert([
            'code' => 'A_VI_DU', 'family' => 'A', 'rule_type' => 'ViDuRule',
            'name' => 'Tên hiển thị quy tắc', 'severity' => 'warning',
            'params' => null, 'scope' => null, 'is_active' => 1,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }
}
public function down()
{
    DB::table('order_check_rules')->where('code', 'A_VI_DU')->delete();
}
```

> Nếu **chưa deploy** và muốn gọn, có thể thêm thẳng dòng rule vào migration seed gốc `2026_06_30_100005_seed_order_check_rules.php` thay vì tạo migration mới.

### Bước 4 — Kiểm thử
```bash
vendor/bin/phpunit tests/Unit/OrderCheck      # test logic thuần
php artisan migrate                            # seed rule
php artisan kiemtraylenh:scan --once           # chạy thật 1 lần, không lỗi
```

### 3.1 — Bổ sung cột HIS vào OrderContext (khi cần)
Nếu handler cần một cột của `HIS_SERVICE_REQ` (hoặc bảng join) chưa có:
1. `HisOrderSource::fetchServiceRequests()` — thêm cột vào `selectRaw(...)`.
2. `app/Services/OrderCheck/Support/OrderContext.php` — khai báo thuộc tính mới.
3. `HisOrderSource::buildContext()` — gán `$c->thuocTinhMoi = $row->cot_moi;`.
4. (Nếu muốn lưu/hiển thị/filter trên dashboard) xem §7.

⚠️ **Hiệu năng:** đừng thêm `leftJoin` bảng lớn vào `fetchServiceRequests` (xem §8). Nếu cần dữ liệu bảng khác theo lô, dùng truy vấn batched `... WHERE id IN (...)` rồi map trong PHP (mẫu: `fetchServiceReqInfoByIds`, `fetchTreatmentInfo`).

---

## 4. Loại B — Scanner cho nguồn HIS mới (quét theo từng dòng)

Dùng khi quy tắc xét từng bản ghi của một bảng HIS khác (vd đơn thuốc `HIS_EXP_MEST_MEDICINE`, log tương tác `HIS_MEDICINE_INTERACTIVE`). Mẫu tham khảo: `InteractionLogScanner.php` (đơn giản nhất).

### Bước 1 — Thêm hàm đọc HIS
`HisOrderSource.php`, quét **theo ID** (PK, luôn có index):
```php
public function fetchXxxBatch($lastCreateTime, $lastId, $limit)
{
    return DB::connection($this->conn)
        ->table('his_xxx')
        ->where('is_delete', 0)
        ->where('id', '>', $lastId)        // KHÔNG dùng OR-keyset (làm mất index, xem §8)
        ->orderBy('id')->limit($limit)
        ->selectRaw('id, create_time, ...cot can...')
        ->get();
}
```

### Bước 2 — Viết Scanner
`app/Services/OrderCheck/Scanners/XxxScanner.php`:
```php
<?php
namespace App\Services\OrderCheck\Scanners;

use App\Services\OrderCheck\Contracts\Scanner;
use App\Services\OrderCheck\OrderCheckEngine;
use App\Services\OrderCheck\Support\Violation;
use App\Services\OrderCheck\Support\ViolationContext;

class XxxScanner implements Scanner
{
    const SOURCE_KEY = 'his_xxx';       // watermark riêng
    const RULE_CODE  = 'A_XXX';

    public function sourceKey() { return self::SOURCE_KEY; }

    public function scan(OrderCheckEngine $engine, $limit)
    {
        $rules  = $engine->activeRules();
        $active = isset($rules[self::RULE_CODE]);

        $source = $engine->source();
        $wm  = $engine->getWatermark(self::SOURCE_KEY);
        $rows = $source->fetchXxxBatch($wm->last_create_time, $wm->last_id, $limit);
        $scanned = $rows->count();
        $violations = 0;

        if ($scanned > 0) {
            $maxId = $wm->last_id;
            foreach ($rows as $row) {
                if ($active && /* điều kiện lỗi */) {
                    $vio  = new Violation(self::RULE_CODE, 'his_xxx', (int)$row->id, 'Mô tả...', [...]);
                    $vctx = ViolationContext::make([
                        'treatment_id' => (int)$row->tdl_treatment_id,
                        // ...các field hiển thị có sẵn...
                    ]);
                    if ($engine->persist($vio, $vctx, $rules[self::RULE_CODE])) $violations++;
                }
                if ((int)$row->id > $maxId) $maxId = (int)$row->id;
            }
            // quét theo id -> lưu last_id (giữ last_create_time cũ)
            $engine->saveWatermark(self::SOURCE_KEY, $wm->last_create_time, $maxId);
        }
        return ['scanned' => $scanned, 'violations' => $violations];
    }
}
```

### Bước 3 — Đăng ký Scanner
`ScannerRegistry::all()` — thêm `new XxxScanner(),`.

### Bước 4 — Seed rule (§3 bước 3) + **khởi tạo watermark**
Với nguồn MỚI, phải set mốc = hiện tại để **không backfill** lịch sử. Thêm vào migration (mẫu `2026_06_30_100006_init_order_check_watermarks.php`):
```php
$maxId = (int) DB::connection(config('order_check.his_connection'))->table('his_xxx')->max('id');
DB::table('order_check_watermarks')->updateOrInsert(
    ['source_key' => 'his_xxx'],
    ['last_id' => $maxId, 'last_modify_time' => 0, 'last_create_time' => 0, 'last_run_at' => now(), 'created_at' => now(), 'updated_at' => now()]
);
```

> **Quét theo `MODIFY_TIME`** (để bắt cả bản ghi bị SỬA, vd người thực hiện gán sau): CHỈ làm khi cột `modify_time` của bảng đó **có index** (kiểm tra `all_ind_columns`). Khi đó dùng `where('modify_time','>',$lastModifyTime)->orderBy('modify_time')->orderBy('id')`, lưu bằng `engine->saveWatermarkModify(...)`, và mốc init = `MAX(modify_time)`. Mẫu: `ServiceReqScanner` + `fetchServiceRequests` (có index hint, xem §8).

---

## 5. Loại C — Scanner cấp đợt điều trị (nâng cao)

Dùng khi cần xét **toàn bộ item của 1 đợt** (vd trùng dịch vụ, trùng hoạt chất). Pattern: quét bản ghi mới theo id → gom `treatment_id` vừa phát sinh → với mỗi đợt, nạp **toàn bộ** item đang hoạt động → so trùng/đối chiếu.

Điểm cần lưu ý:
- Lấy item toàn đợt theo cột đã index (`tdl_treatment_id` thường có index) — mẫu các hàm `fetchTreatmentServices`/`fetchTreatmentMedicines` (đã từng tồn tại, xem git history commit loại bỏ A2/A3).
- `dedup_key` dùng `subKey` để mỗi cặp/nhóm trùng là 1 vi phạm riêng (vd `subKey = 'svc'.$serviceId`).
- Lấy thông tin đợt (mã đợt, BN, khoa) bằng truy vấn batched `fetchTreatmentInfo($treatmentIds)`.

Tham khảo lịch sử git (commit `387be4b` "Plan 5") để xem mẫu `DuplicateServiceScanner` / `MedicineScanner` cấp đợt. (A2/A3 đã được gỡ khỏi đợt hiện tại nhưng pattern còn nguyên giá trị.)

---

## 6. Loại D — Quy tắc cần dữ liệu tham chiếu ngoài HIS

Khi HIS **không có sẵn** dữ liệu để phán đoán (vd HIS không nhập giới hạn giới tính/tuổi của DV) → tự xây bảng danh mục trong `qlbv` + màn nhập + luật đọc bảng đó. Mẫu **đầy đủ** đã có: luật giới tính/tuổi.

Các phần của mẫu (tham khảo trực tiếp):
1. **Bảng:** `order_check_ref_service_restriction` (migration `..._100004_*`).
2. **Model:** `app/Models/OrderCheck/OrderCheckRefServiceRestriction.php`.
3. **Rule thuần (có test):** `RuleHandlers/Clinical/GenderRestrictionRule.php`, `AgeRestrictionRule.php`.
4. **Scanner đối chiếu danh mục:** `Scanners/ServiceRestrictionScanner.php` (nạp catalog 1 lần vào bộ nhớ, key theo mã DV).
5. **Màn nhập danh mục (CRUD):** `app/Http/Controllers/KHTH/OrderCheckRefController.php` + view `resources/views/khth/order-check-ref.blade.php` + route nhóm `khth/` + menu `config/adminlte.php`.

Làm tương tự cho danh mục mới: tạo bảng `order_check_ref_<...>` → rule đọc bảng → scanner → CRUD (nếu cần người dùng tự nhập).

---

## 7. Hiển thị / lọc vi phạm trên dashboard (tuỳ chọn)

Nếu vi phạm cần thêm thông tin hiển thị/filter (mã phiếu, loại DV, khoa...):
1. **Lưu:** thêm cột vào `order_check_violations` (migration); thêm field vào `ViolationContext` + `ViolationContext::make()`/`fromOrderContext()`; gán trong `engine->persist()`. (Tên khoa/loại được engine resolve qua cache `HisOrderSource::departmentInfo()` / `serviceReqTypeName()`.)
2. **Hiển thị:** thêm cột vào DataTable trong `resources/views/khth/order-check.blade.php` (mảng `columns`) + `<thead>`; nếu cần định dạng đặc biệt thì `addColumn(...)` trong `OrderCheckController::fetch()`.
3. **Filter:** thêm điều kiện trong `app/Services/OrderCheck/ViolationQueryService::filtered()` + ô filter trong view + `filters()` JS. Lọc theo danh mục khoa/phòng: dùng route `category-his.fetch-department-catalog` (mẫu partial `resources/views/partials/department_catalog.blade.php`).
4. **Export Excel:** thêm cột vào `app/Exports/OrderCheckViolationExport.php` (`headings()` + `map()`).

---

## 8. Quy tắc HIỆU NĂNG (bắt buộc tuân thủ)

Các bảng HIS rất lớn (hàng triệu dòng). Sai một trong các điểm sau sẽ khiến mỗi lần quét **full table scan** (đã từng gây 121–127s/lần):

1. **Quét theo cột CÓ INDEX:** mặc định dùng `id` (PK, luôn có index). Chỉ dùng `modify_time`/`create_time` nếu đã xác nhận có index (`SELECT ... FROM all_ind_columns WHERE table_name=...`).
2. **KHÔNG dùng OR-keyset** kiểu `WHERE t > :t OR (t = :t AND id > :id)` — mệnh đề OR làm Oracle bỏ index. Dùng `WHERE id > :id` (đơn giản) hoặc `WHERE modify_time > :mt` (nếu có index).
3. **Tránh JOIN bảng lớn trong truy vấn có watermark.** Với bind variable `?`, Oracle dễ chọn plan hash-join full-scan. Thay vì join: lấy id ở truy vấn chính rồi **batched lookup** `WHERE id IN (...)` ở truy vấn phụ (mẫu `fetchServiceReqInfoByIds`, `fetchTreatmentInfo`). Nếu buộc phải join + cần ép index: dùng hint đặt ngay sau `SELECT`, vd `->selectRaw('/*+ INDEX(sr (MODIFY_TIME)) */ ...')` (mẫu `fetchServiceRequests`).
4. **Resolve tên (khoa, loại) qua cache** nạp 1 lần/run (`HisOrderSource::departmentInfo`), không join.
5. **Luôn đo lại:** sau khi thêm scanner, kiểm `order_check_rule_logs` (cột thời gian) hoặc dashboard "Thống kê quét" để chắc mỗi nguồn vẫn nhanh.

---

## 9. Checklist khi thêm 1 quy tắc

- [ ] Đặt `code` (`A_*` lâm sàng / `B_*` cấu trúc), `family`, `severity`.
- [ ] Viết logic: handler (loại A) hoặc scanner (loại B/C/D) — **kèm unit test cho logic thuần**.
- [ ] Đăng ký: registry handler **hoặc** `ScannerRegistry`.
- [ ] Seed bản ghi `order_check_rules` (migration).
- [ ] Nếu nguồn mới: **init watermark = hiện tại** (migration) để không backfill.
- [ ] Nếu cần dữ liệu ngoài HIS: tạo bảng `order_check_ref_*` + (tuỳ) CRUD.
- [ ] (Tuỳ) thêm cột hiển thị/filter/export (§7).
- [ ] Tuân thủ §8 (hiệu năng) — đo lại bằng `order_check_rule_logs`.
- [ ] Verify: `vendor/bin/phpunit tests/Unit/OrderCheck` + `php artisan kiemtraylenh:scan --once`.
- [ ] Triển khai: `update.bat` trên server tự `git pull` + `migrate` + chạy lại service `QLBV KiemTraYLenh`.

---

## 10. Mẹo vận hành nhanh
- **Tắt 1 luật:** `UPDATE order_check_rules SET is_active=0 WHERE code='...';`
- **Đổi mức độ:** `UPDATE order_check_rules SET severity='critical' WHERE code='...';`
- **Quét lại từ mốc cũ (debug):** chỉnh `order_check_watermarks.last_id` / `last_modify_time` về giá trị nhỏ hơn (cẩn thận: nguồn lớn + cột không index sẽ chậm).
- **Chạy thủ công 1 lần:** `php artisan kiemtraylenh:scan --once` (có `--limit=N`).
- **Xem sức khỏe quét:** dashboard KHTH → Kiểm tra sai sót y lệnh → box "Thống kê quét", hoặc bảng `order_check_rule_logs`.
