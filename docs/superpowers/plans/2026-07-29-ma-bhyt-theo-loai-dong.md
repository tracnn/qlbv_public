# Mã BHYT theo đúng loại dòng — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Quy tắc `A_BHYT_CODE_MISSING` lấy mã BHYT từ đúng nguồn theo loại dòng, để không còn báo sai 48.234 dòng thuốc mỗi 7 ngày.

**Architecture:** Thêm join tới danh mục thuốc trong truy vấn dòng dịch vụ của `HisOrderSource`, và chọn mã BHYT bằng một hàm thuần `MaBhytDong::cua()` — mã hoạt chất trước, mã dịch vụ sau. Lớp quy tắc không đổi vì nó chỉ đọc `$s->bhytCode`.

**Tech Stack:** Laravel 5.5.50, PHP 7.4, PHPUnit 6.5, Oracle qua kết nối `HISPro` (chỉ đọc).

## Global Constraints

- Cổng kiểm thử: `vendor/bin/phpunit --testsuite Unit`. **KHÔNG** chạy `tests/Feature` — đỏ sẵn vì lý do môi trường, không liên quan.
- Comment trong code PHP viết tiếng Việt **không dấu**.
- Kết nối Oracle tên `HISPro`, **CHỈ ĐỌC**. Không ghi bất cứ gì sang Oracle.
- **Không sửa** `app/Services/OrderCheck/RuleHandlers/Bhyt/BhytCodeMissingRule.php` — nó không có lỗi.
- **Không** thêm nhánh xử lý riêng cho vật tư: đo được 0/175.775 dòng vật tư BHYT thiếu mã, nên nhánh đó là code chết.
- **Không** loại trừ suất ăn hay bất kỳ loại dịch vụ nào khỏi phạm vi quy tắc.
- **Không** bật `A_BHYT_CODE_MISSING` (`is_active` giữ nguyên 0) — việc bật là quyết định nghiệp vụ, làm sau khi nghiệm thu số liệu.
- **Không** đụng `BhytCatalogRule` hay các quy tắc khác cùng họ BHYT.
- `MaBhytDong::cua()` trả **chuỗi rỗng** (không phải `null`) khi không có mã nào — vì `BhytCodeMissingRule` đang kiểm `trim((string) $s->bhytCode) !== ''`.

## Cấu trúc tệp

| Tệp | Trách nhiệm |
| --- | --- |
| `app/Services/OrderCheck/Support/MaBhytDong.php` (tạo) | Hàm thuần chọn mã BHYT giữa mã hoạt chất và mã dịch vụ |
| `app/Services/OrderCheck/HisOrderSource.php` (sửa) | Join danh mục thuốc, dùng `MaBhytDong` để gán `bhytCode` |
| `tests/Unit/MaBhytDongTest.php` (tạo) | Kiểm hàm thuần |
| `tests/Unit/HisOrderSourceMaBhytTest.php` (tạo) | Chống việc gỡ join hoặc quay lại gán thẳng cột cũ |

---

### Task 1: Hàm thuần chọn mã BHYT

**Files:**
- Create: `app/Services/OrderCheck/Support/MaBhytDong.php`
- Test: `tests/Unit/MaBhytDongTest.php`

**Interfaces:**
- Consumes: không có gì từ task khác.
- Produces: `App\Services\OrderCheck\Support\MaBhytDong::cua($maHoatChat, $maDichVu)` trả `string`. Task 2 gọi nó.

- [ ] **Step 1: Viết test đỏ**

Tạo `tests/Unit/MaBhytDongTest.php`:

```php
<?php

namespace Tests\Unit;

use App\Services\OrderCheck\Support\MaBhytDong;
use Tests\TestCase;

class MaBhytDongTest extends TestCase
{
    /** @test */
    public function co_ma_hoat_chat_thi_uu_tien_ma_hoat_chat()
    {
        // Dong THUOC: ma BHYT nam o his_medicine_type.active_ingr_bhyt_code, khong phai
        // o his_service.hein_service_bhyt_code.
        $this->assertSame('40.12', MaBhytDong::cua('40.12', 'DV001'));
    }

    /** @test */
    public function khong_co_ma_hoat_chat_thi_lay_ma_dich_vu()
    {
        // Dong VAT TU va DVKT khong join ra duoc danh muc thuoc nen roi ve ma dich vu.
        $this->assertSame('DV001', MaBhytDong::cua(null, 'DV001'));
        $this->assertSame('DV001', MaBhytDong::cua('', 'DV001'));
    }

    /** @test */
    public function ca_hai_rong_thi_tra_chuoi_rong()
    {
        // Tra CHUOI RONG chu khong phai null: BhytCodeMissingRule kiem
        // trim((string) $s->bhytCode) !== '' nen hai dang phai cho cung ket qua.
        $this->assertSame('', MaBhytDong::cua(null, null));
        $this->assertSame('', MaBhytDong::cua('', ''));
    }

    /** @test */
    public function cat_khoang_trang_hai_dau()
    {
        $this->assertSame('40.12', MaBhytDong::cua('  40.12  ', null));
        $this->assertSame('DV001', MaBhytDong::cua(null, "\tDV001\n"));
    }

    /** @test */
    public function ma_hoat_chat_chi_gom_khoang_trang_thi_coi_nhu_rong()
    {
        $this->assertSame('DV001', MaBhytDong::cua('   ', 'DV001'));
    }
}
```

- [ ] **Step 2: Chạy test, xác nhận đỏ**

```bash
vendor/bin/phpunit tests/Unit/MaBhytDongTest.php
```

Kỳ vọng: cả 5 test FAIL với `Class 'App\Services\OrderCheck\Support\MaBhytDong' not found`.

- [ ] **Step 3: Viết lớp**

Tạo `app/Services/OrderCheck/Support/MaBhytDong.php`:

```php
<?php

namespace App\Services\OrderCheck\Support;

/**
 * Chon ma BHYT cua mot dong dich vu theo dung nguon.
 *
 * Vi sao can: quy tac A_BHYT_CODE_MISSING truoc day luon doc
 * his_service.hein_service_bhyt_code, ma cot do CHI duoc duy tri cho dich vu ky thuat.
 * Voi thuoc, ma BHYT nam o his_medicine_type.active_ingr_bhyt_code.
 *
 * Do tren 7 ngay that: 48.234 dong thuoc BHYT thieu hein_service_bhyt_code, va 100% so do
 * DA khai active_ingr_bhyt_code. Vat tu 0/175.775 va DVKT 0/352.206 khong thieu - nen chi
 * can them nguon thuoc, khong can nhanh rieng cho vat tu.
 *
 * Ham THUAN de kiem duoc.
 */
class MaBhytDong
{
    /**
     * @param string|null $maHoatChat his_medicine_type.active_ingr_bhyt_code
     * @param string|null $maDichVu   his_service.hein_service_bhyt_code
     * @return string ma dau tien khac rong sau khi trim; CHUOI RONG neu khong co cai nao
     */
    public static function cua($maHoatChat, $maDichVu)
    {
        foreach ([$maHoatChat, $maDichVu] as $ma) {
            $ma = trim((string) $ma);

            if ($ma !== '') {
                return $ma;
            }
        }

        return '';
    }
}
```

- [ ] **Step 4: Chạy test, xác nhận xanh**

```bash
vendor/bin/phpunit tests/Unit/MaBhytDongTest.php
```

Kỳ vọng: PASS cả 5 test.

- [ ] **Step 5: Chạy suite Unit**

```bash
vendor/bin/phpunit --testsuite Unit
```

Kỳ vọng: OK, số test tăng 5 so với trước.

- [ ] **Step 6: Commit**

```bash
git add app/Services/OrderCheck/Support/MaBhytDong.php tests/Unit/MaBhytDongTest.php
git commit -m "feat(order-check): ham thuan chon ma BHYT theo nguon"
```

---

### Task 2: Nối nguồn mã thuốc vào truy vấn dòng dịch vụ

**Files:**
- Modify: `app/Services/OrderCheck/HisOrderSource.php` (trong `fetchServicesByReqIds()`)
- Test: `tests/Unit/HisOrderSourceMaBhytTest.php`

**Interfaces:**
- Consumes: `App\Services\OrderCheck\Support\MaBhytDong::cua($maHoatChat, $maDichVu)` từ Task 1.
- Produces: `$s->bhytCode` của mỗi `OrderService` nay mang mã đúng theo loại dòng. `BhytCodeMissingRule` đọc nó, không phải sửa.

- [ ] **Step 1: Viết test đỏ**

Tạo `tests/Unit/HisOrderSourceMaBhytTest.php`:

```php
<?php

namespace Tests\Unit;

use Tests\TestCase;
use Tests\Support\LocComment;

/**
 * Chan viec ai do sau nay go join danh muc thuoc hoac quay lai gan thang
 * hein_service_bhyt_code - hai viec do deu lam quy tac A_BHYT_CODE_MISSING bao sai lai
 * toan bo dong thuoc, ma khong test nao khac bat duoc.
 */
class HisOrderSourceMaBhytTest extends TestCase
{
    use LocComment;

    protected function ma()
    {
        // Bo comment truoc khi quet: mot chuoi nam trong comment se lam test xanh gia.
        return $this->maKhongComment(base_path('app/Services/OrderCheck/HisOrderSource.php'));
    }

    /** @test */
    public function co_join_toi_danh_muc_thuoc()
    {
        $ma = $this->ma();

        $this->assertContains('his_medicine', $ma, 'Mat join his_medicine');
        $this->assertContains('his_medicine_type', $ma, 'Mat join his_medicine_type');
    }

    /** @test */
    public function co_chon_cot_ma_hoat_chat()
    {
        $this->assertContains('active_ingr_bhyt_code', $this->ma(),
            'Truy van khong con chon cot ma hoat chat BHYT');
    }

    /** @test */
    public function dung_ma_bhyt_dong_de_chon_ma()
    {
        $this->assertContains('MaBhytDong', $this->ma(),
            'Khong con dung MaBhytDong - co the da quay lai gan thang hein_service_bhyt_code');
    }
}
```

Trait `Tests\Support\LocComment` đã có sẵn; method cần dùng là `maKhongComment(string $duongDan): string` (`tests/Support/LocComment.php:22`) — đã đối chiếu, dùng đúng tên đó trong test trên.

- [ ] **Step 2: Chạy test, xác nhận đỏ**

```bash
vendor/bin/phpunit tests/Unit/HisOrderSourceMaBhytTest.php
```

Kỳ vọng: `co_join_toi_danh_muc_thuoc`, `co_chon_cot_ma_hoat_chat`, `dung_ma_bhyt_dong_de_chon_ma` đều FAIL.

- [ ] **Step 3: Thêm join và cột chọn**

Trong `app/Services/OrderCheck/HisOrderSource.php`, method `fetchServicesByReqIds()`, thêm hai join ngay sau join `his_service as sv`:

```php
            // Ma BHYT cua THUOC nam o danh muc thuoc, khong nam o danh muc dich vu: cot
            // sv.hein_service_bhyt_code chi duoc duy tri cho dich vu ky thuat.
            ->leftJoin('his_medicine as md', 'md.id', '=', 'ss.medicine_id')
            ->leftJoin('his_medicine_type as mdt', 'mdt.id', '=', 'md.medicine_type_id')
```

Và thêm cột vào `selectRaw`, ngay sau `sv.service_type_id`:

```
, mdt.active_ingr_bhyt_code
```

- [ ] **Step 4: Dùng MaBhytDong khi gán**

Trong cùng method, thay dòng:

```php
            $s->bhytCode = $r->hein_service_bhyt_code;
```

bằng:

```php
            // Thuoc lay ma hoat chat; vat tu va DVKT khong join ra duoc danh muc thuoc nen
            // roi ve ma dich vu nhu cu.
            $s->bhytCode = \App\Services\OrderCheck\Support\MaBhytDong::cua(
                $r->active_ingr_bhyt_code,
                $r->hein_service_bhyt_code
            );
```

- [ ] **Step 5: Kiểm cú pháp và chạy test**

```bash
php -l app/Services/OrderCheck/HisOrderSource.php && vendor/bin/phpunit tests/Unit/HisOrderSourceMaBhytTest.php
```

Kỳ vọng: không lỗi cú pháp; PASS cả 3 test.

- [ ] **Step 6: Chứng minh truy vấn thật chạy được**

Join mới có thể sai tên bảng hoặc tên cột mà test quét mã nguồn không phát hiện. Chạy thật một lô nhỏ qua Oracle:

```bash
php -r "require 'vendor/autoload.php'; \$a=require 'bootstrap/app.php'; \$a->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap(); \$src = app(App\Services\OrderCheck\HisOrderSource::class); \$ids = DB::connection('HISPro')->table('his_sere_serv')->where('is_delete',0)->whereNotNull('medicine_id')->orderBy('id','desc')->limit(5)->pluck('service_req_id')->unique()->values()->all(); \$m = \$src->fetchServicesByReqIds(\$ids); \$n=0; foreach(\$m as \$ds) foreach(\$ds as \$s){ \$n++; printf('%-40s bhytCode=%s'.PHP_EOL, mb_substr((string)\$s->serviceName,0,40), var_export(\$s->bhytCode,true)); if(\$n>=8) break 2; }"
```

Kỳ vọng: chạy không lỗi Oracle, và các dòng thuốc hiện `bhytCode` khác chuỗi rỗng.

- [ ] **Step 7: Nghiệm thu bằng số — bắt buộc**

Đo lại đúng phép đo đã dùng để phát hiện lỗi, nhưng theo logic mới:

```bash
php -r "require 'vendor/autoload.php'; \$a=require 'bootstrap/app.php'; \$a->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap(); \$r = DB::connection('HISPro')->table('his_sere_serv as ss')->leftJoin('his_service as sv','sv.id','=','ss.service_id')->leftJoin('his_medicine as md','md.id','=','ss.medicine_id')->leftJoin('his_medicine_type as mdt','mdt.id','=','md.medicine_type_id')->where('ss.is_delete',0)->where('ss.patient_type_id',1)->where('ss.tdl_intruction_time','>=',20260722000000)->whereNull('sv.hein_service_bhyt_code')->whereNull('mdt.active_ingr_bhyt_code')->count(); echo 'Con lai sau khi sua: '.number_format(\$r).' dong (truoc khi sua: 48.234)'.PHP_EOL;"
```

Kỳ vọng: **0 dòng**. Đây là bằng chứng duy nhất cho thấy bản sửa giải quyết đúng vấn đề đã đo — không được bỏ qua.

Nếu con số khác 0, **đừng sửa cho khớp** — báo lại kèm con số thật.

- [ ] **Step 8: Chạy suite Unit**

```bash
vendor/bin/phpunit --testsuite Unit
```

Kỳ vọng: OK, số test tăng 3 so với sau Task 1.

- [ ] **Step 9: Commit**

```bash
git add app/Services/OrderCheck/HisOrderSource.php tests/Unit/HisOrderSourceMaBhytTest.php
git commit -m "fix(order-check): lay ma BHYT cua thuoc tu danh muc thuoc"
```

---

### Task 3: Cập nhật tài liệu

**Files:**
- Modify: `docs/tai-lieu-tong-hop-xml3176-order-check.md`

**Interfaces:**
- Consumes: kết quả Task 1-2.
- Produces: không có gì.

- [ ] **Step 1: Thêm ghi chú vào mục danh mục quy tắc order-check**

Trong `docs/tai-lieu-tong-hop-xml3176-order-check.md`, tìm mục `### 3.4. Danh mục quy tắc (seed)` và chèn đoạn dưới đây vào **cuối mục đó**, ngay trước tiêu đề mục kế tiếp:

```markdown
> **`A_BHYT_CODE_MISSING` — nguồn mã BHYT theo loại dòng** (sửa 29/07/2026): quy tắc này
> trước đây luôn đọc `his_service.hein_service_bhyt_code`, nhưng cột đó **chỉ được duy trì
> cho dịch vụ kỹ thuật**. Với thuốc, mã BHYT nằm ở
> `his_medicine_type.active_ingr_bhyt_code`.
>
> Đo trên 7 ngày thật, chỉ tính dòng thuộc đối tượng BHYT: **48.234 dòng thuốc** thiếu
> `hein_service_bhyt_code`, và **100% số đó đã khai** `active_ingr_bhyt_code` — tức là nếu
> bật quy tắc sẽ sinh ~6.900 cảnh báo sai mỗi ngày, không cái nào đúng. Vật tư
> (0/175.775) và DVKT (0/352.206) không thiếu dòng nào.
>
> Nay `HisOrderSource::fetchServicesByReqIds()` join thêm `his_medicine` →
> `his_medicine_type` và chọn mã qua `App\Services\OrderCheck\Support\MaBhytDong::cua()`:
> **mã hoạt chất trước, mã dịch vụ sau**. Gỡ join đó hoặc quay lại gán thẳng
> `hein_service_bhyt_code` sẽ làm quy tắc báo sai lại toàn bộ dòng thuốc —
> `tests/Unit/HisOrderSourceMaBhytTest.php` canh đúng hai việc này.
>
> **Không** thêm nhánh riêng cho vật tư: số đo cho thấy vật tư đang đúng, thêm nhánh là
> viết code chết. Quy tắc vẫn đang `is_active = 0`; bật hay không là quyết định nghiệp vụ.
```

Nếu không tìm thấy mục `### 3.4. Danh mục quy tắc (seed)`, chèn vào cuối mục
`## 3. Module Order-Check — Kiểm tra sai sót y lệnh`, ngay trước tiêu đề `## 4.`.

- [ ] **Step 2: Commit**

```bash
git add docs/tai-lieu-tong-hop-xml3176-order-check.md
git commit -m "docs(order-check): ghi lai nguon ma BHYT theo loai dong"
```

---

## Nghiệm thu cuối

- [ ] `vendor/bin/phpunit --testsuite Unit` — OK, không đỏ.
- [ ] Phép đo ở Task 2 Step 7 cho **0 dòng** (trước khi sửa là 48.234).
- [ ] `A_BHYT_CODE_MISSING` vẫn `is_active = 0` — kiểm bằng:

```bash
php -r "require 'vendor/autoload.php'; \$a=require 'bootstrap/app.php'; \$a->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap(); echo 'is_active='.DB::table('order_check_rules')->where('code','A_BHYT_CODE_MISSING')->value('is_active').PHP_EOL;"
```
