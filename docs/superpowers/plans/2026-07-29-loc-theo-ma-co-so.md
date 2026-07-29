# Lọc theo mã cơ sở ở XML3176 và order-check — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Thêm bộ lọc theo mã cơ sở KCB vào màn danh sách hồ sơ XML3176 và màn vi phạm y lệnh (order-check).

**Architecture:** XML3176 đã có sẵn cột `ma_cskcb` nên chỉ cần áp điều kiện — nhưng phải áp cho cả ba nhánh dựng truy vấn, nên tách thành một lớp dùng chung có kiểm giá trị. Order-check chưa có cột: thêm cột, nối lại mắt xích đã đứt (`OrderContext` đã mang `maCskcb` nhưng `ViolationContext` bỏ rơi nó), và vá ngược 1.065 dòng cũ bằng cách tra `treatment_id` sang HIS.

**Tech Stack:** Laravel 5.5.50, PHP 7.4, PHPUnit 6.5, Blade, jQuery DataTables (server-side), MySQL (dữ liệu ứng dụng) + Oracle qua kết nối `HISPro` (chỉ đọc).

## Global Constraints

- Cổng kiểm thử: `vendor/bin/phpunit --testsuite Unit`. **KHÔNG** chạy `tests/Feature` — đỏ sẵn vì lý do môi trường, không liên quan.
- Comment trong code PHP/JS viết tiếng Việt **không dấu**; chuỗi hiển thị cho người dùng viết **có dấu**.
- Nguồn danh sách cơ sở là `App\Services\BHYT\DanhSachCoSo::danhSach()` — trả mảng `['01929' => 'nhãn', ...]`. Không tự truy vấn `his_branch` chỗ khác.
- Ô chọn luôn có lựa chọn đầu **"Tất cả cơ sở"** (giá trị rỗng). Rỗng thì **không lọc**.
- Không sửa `app/Http/Middleware/CheckRole.php` hay `app/Providers/AppServiceProvider.php`.
- Không thêm bộ lọc vào dashboard XML3176 (`dashboard/xml3176`).
- Không đổi cách phân quyền xem hồ sơ theo `imported_by` đang có trong `BHYTXml3176Controller`.
- Không gán mã cơ sở mặc định cho những dòng vi phạm không tra ra được — để trống.
- Kết nối Oracle dùng tên `HISPro`, **chỉ đọc**. Mệnh đề `IN` của Oracle giới hạn 1000 phần tử → chia lô 900.

## Cấu trúc tệp

| Tệp | Trách nhiệm |
| --- | --- |
| `database/migrations/2026_07_29_120000_them_ma_cskcb_vao_order_check_violations.php` (tạo) | Thêm cột + index, vá ngược từ HIS |
| `app/Services/OrderCheck/Support/ViolationContext.php` (sửa) | Mang thêm `maCskcb` |
| `app/Services/OrderCheck/OrderCheckEngine.php` (sửa) | Ghi `ma_cskcb` khi lưu vi phạm |
| `app/Services/OrderCheck/ViolationQueryService.php` (sửa) | Thêm mệnh đề lọc |
| `app/Http/Controllers/KHTH/OrderCheckController.php` (sửa) | Truyền `danhSachCoSo` xuống view |
| `resources/views/partials/ma_cskcb.blade.php` (tạo) | Ô chọn cơ sở, dùng chung hai màn |
| `resources/views/khth/order-check.blade.php` (sửa) | Gắn ô chọn + gửi tham số |
| `app/Services/BHYT/LocCoSo.php` (tạo) | Kiểm mã hợp lệ và áp điều kiện cho XML3176 |
| `app/Http/Controllers/BHYT/BHYTXml3176Controller.php` (sửa) | Áp lọc cho cả 3 nhánh, truyền `danhSachCoSo` |
| `resources/views/bhyt/xml3176/partials/search.blade.php` (sửa) | Gắn ô chọn |
| `resources/views/bhyt/xml3176/index.blade.php` (sửa) | Gửi tham số DataTables |

---

### Task 1: Cột `ma_cskcb` cho vi phạm và đường ghi

**Files:**
- Create: `database/migrations/2026_07_29_120000_them_ma_cskcb_vao_order_check_violations.php`
- Modify: `app/Services/OrderCheck/Support/ViolationContext.php`
- Modify: `app/Services/OrderCheck/OrderCheckEngine.php` (trong `persist()`)
- Test: `tests/Unit/ViPhamMaCoSoTest.php`

**Interfaces:**
- Consumes: `App\Services\OrderCheck\Support\OrderContext` đã có sẵn trường `public $maCskcb` (dòng 29) — **không** phải thêm.
- Produces: cột `order_check_violations.ma_cskcb`; `ViolationContext::$maCskcb`; khoá mảng `'ma_cskcb'` cho `ViolationContext::make()`. Task 2 lọc trên cột này.

- [ ] **Step 1: Viết test đỏ**

Tạo `tests/Unit/ViPhamMaCoSoTest.php`:

```php
<?php

namespace Tests\Unit;

use DB;
use App\Services\OrderCheck\Support\OrderContext;
use App\Services\OrderCheck\Support\ViolationContext;
use Tests\TestCase;

class ViPhamMaCoSoTest extends TestCase
{
    /** @test */
    public function bang_vi_pham_co_cot_ma_cskcb()
    {
        $co = false;

        foreach (DB::select('SHOW COLUMNS FROM order_check_violations') as $c) {
            if ($c->Field === 'ma_cskcb') {
                $co = true;
                break;
            }
        }

        $this->assertTrue($co, 'Bang order_check_violations thieu cot ma_cskcb');
    }

    /** @test */
    public function violation_context_giu_duoc_ma_cskcb()
    {
        $c = ViolationContext::make(['ma_cskcb' => '01929']);

        $this->assertSame('01929', $c->maCskcb);
    }

    /** @test */
    public function khong_truyen_ma_cskcb_thi_la_null()
    {
        $c = ViolationContext::make([]);

        $this->assertNull($c->maCskcb);
    }

    /**
     * Mat xich de quen nhat: fromOrderContext la mot danh sach khoa CHEP TAY. Them truong
     * vao ViolationContext ma quen them o day thi ma co so im lang khong bao gio duoc ghi,
     * va bo loc se rong tren moi vi pham moi.
     */
    /** @test */
    public function from_order_context_chuyen_duoc_ma_cskcb()
    {
        $o = new OrderContext();
        $o->maCskcb = '37470';

        $c = ViolationContext::fromOrderContext($o);

        $this->assertSame('37470', $c->maCskcb);
    }
}
```

- [ ] **Step 2: Chạy test, xác nhận đỏ**

```bash
vendor/bin/phpunit tests/Unit/ViPhamMaCoSoTest.php
```

Kỳ vọng: cả 4 test FAIL — test 1 báo thiếu cột, ba test còn lại báo `maCskcb` không tồn tại hoặc null.

- [ ] **Step 3: Sửa ViolationContext**

Trong `app/Services/OrderCheck/Support/ViolationContext.php`, thêm khai báo trường sau `public $serviceName;`:

```php

    /**
     * @var string|null Ma CSKCB (his_branch.hein_medi_org_code) cua co so xu ly ho so.
     *                  Luu xuong de man danh sach loc duoc theo co so: vi pham nam o
     *                  MySQL con HIS o Oracle nen KHONG the join luc truy van.
     */
    public $maCskcb;
```

Trong `make()`, thêm sau dòng gán `serviceName`:

```php
        $c->maCskcb = isset($a['ma_cskcb']) ? $a['ma_cskcb'] : null;
```

Trong `fromOrderContext()`, thêm vào mảng truyền cho `make()`:

```php
            'ma_cskcb' => $o->maCskcb,
```

- [ ] **Step 4: Ghi cột khi lưu vi phạm**

Trong `app/Services/OrderCheck/OrderCheckEngine.php`, trong `persist()`, thêm ngay sau dòng `$row->treatment_code = $ctx->treatmentCode;`:

```php
        $row->ma_cskcb = $ctx->maCskcb;
```

- [ ] **Step 5: Viết migration**

Tạo `database/migrations/2026_07_29_120000_them_ma_cskcb_vao_order_check_violations.php`:

```php
<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Them cot ma_cskcb vao vi pham y lenh, va va nguoc du lieu cu tu HIS.
 *
 * Vi sao phai va nguoc: bo quet chi chay TOI TRUOC theo moc thoi gian, khong bao gio quay
 * lai ho so cu. Khong va thi 1.065 dong dang co se vinh vien trong va bo loc bo sot chung.
 *
 * Duong tra: order_check_violations.treatment_id -> HIS_TREATMENT.BRANCH_ID
 *            -> HIS_BRANCH.HEIN_MEDI_ORG_CODE
 *
 * Do truoc khi viet: 890 treatment_id phan biet, tra ra 829, khong tra ra 61 (dot dieu tri
 * da bien mat khoi his_treatment) — ung voi 72/1.065 dong se de TRONG. Khong gan mac dinh
 * cho 72 dong do: gan bua thi chung trong nhu da biet chac thuoc co so nao, trong khi
 * khong ai kiem chung duoc nua.
 */
class ThemMaCskcbVaoOrderCheckViolations extends Migration
{
    /** Menh de IN cua Oracle gioi han 1000 phan tu */
    const CO_LO = 900;

    public function up()
    {
        if (!Schema::hasColumn('order_check_violations', 'ma_cskcb')) {
            Schema::table('order_check_violations', function (Blueprint $t) {
                $t->string('ma_cskcb', 20)->nullable()->after('treatment_code');
                $t->index('ma_cskcb');
            });
        }

        // Loi HIS khong duoc lam migration chet giua chung: cot da them ma du lieu chua va.
        // De trong roi chay lai migration sau van va tiep duoc, vi buoc nay chi dung dong
        // dang NULL.
        try {
            $this->vaNguoc();
        } catch (\Exception $e) {
            echo '  [canh bao] Khong va nguoc duoc ma co so tu HIS: ' . $e->getMessage() . PHP_EOL;
            echo '  Chay lai migration nay sau khi HIS truy cap duoc.' . PHP_EOL;
        }
    }

    protected function vaNguoc()
    {
        $ids = DB::table('order_check_violations')
            ->whereNull('ma_cskcb')
            ->whereNotNull('treatment_id')
            ->distinct()
            ->pluck('treatment_id')
            ->all();

        if (empty($ids)) {
            return;
        }

        $theoMa = [];

        foreach (array_chunk($ids, self::CO_LO) as $lo) {
            $rows = DB::connection('HISPro')
                ->table('his_treatment as t')
                ->leftJoin('his_branch as br', 'br.id', '=', 't.branch_id')
                ->whereIn('t.id', $lo)
                ->select('t.id', DB::raw('br.hein_medi_org_code ma'))
                ->get();

            foreach ($rows as $r) {
                if ($r->ma === null || $r->ma === '') {
                    continue;
                }

                $theoMa[$r->ma][] = $r->id;
            }
        }

        $daVa = 0;

        // Cap nhat GOM NHOM theo ma co so: moi ma mot cau UPDATE thay vi mot cau moi dong.
        foreach ($theoMa as $ma => $dsId) {
            foreach (array_chunk($dsId, self::CO_LO) as $lo) {
                $daVa += DB::table('order_check_violations')
                    ->whereNull('ma_cskcb')
                    ->whereIn('treatment_id', $lo)
                    ->update(['ma_cskcb' => $ma]);
            }
        }

        $conTrong = DB::table('order_check_violations')->whereNull('ma_cskcb')->count();

        echo '  Da va nguoc ' . $daVa . ' dong; con trong ' . $conTrong . ' dong.' . PHP_EOL;
    }

    public function down()
    {
        if (!Schema::hasColumn('order_check_violations', 'ma_cskcb')) {
            return;
        }

        Schema::table('order_check_violations', function (Blueprint $t) {
            $t->dropIndex(['ma_cskcb']);
            $t->dropColumn('ma_cskcb');
        });
    }
}
```

- [ ] **Step 6: Chạy migration**

```bash
php artisan migrate
```

Kỳ vọng: `Migrated: 2026_07_29_120000_them_ma_cskcb_vao_order_check_violations`, kèm dòng `Da va nguoc 993 dong; con trong 72 dong.`

Con số có thể lệch chút nếu bộ quét vừa ghi thêm vi phạm mới. Điều **phải** đúng: số dòng còn trống bằng đúng số dòng có `treatment_id` không tra được trong HIS, chứ không phải toàn bộ bảng.

- [ ] **Step 7: Kiểm chứng bằng truy vấn**

```bash
php -r "require 'vendor/autoload.php'; \$a=require 'bootstrap/app.php'; \$a->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap(); foreach(DB::table('order_check_violations')->select('ma_cskcb', DB::raw('count(*) n'))->groupBy('ma_cskcb')->get() as \$r) printf('%-10s %s'.PHP_EOL, \$r->ma_cskcb === null ? '(trong)' : \$r->ma_cskcb, \$r->n);"
```

Kỳ vọng: dòng `01929` với gần 1.000 dòng, và dòng `(trong)` khoảng 72.

- [ ] **Step 8: Kiểm tính chạy lại được**

```bash
php artisan migrate:rollback --step=1 && php artisan migrate
```

Kỳ vọng: rollback rồi migrate lại thành công; chạy lại lệnh ở Step 7 cho kết quả **giống hệt**, không nhân đôi, không mất dòng.

- [ ] **Step 9: Chạy test**

```bash
vendor/bin/phpunit tests/Unit/ViPhamMaCoSoTest.php && vendor/bin/phpunit --testsuite Unit
```

Kỳ vọng: 4 test mới PASS; suite Unit OK.

- [ ] **Step 10: Commit**

```bash
git add database/migrations/2026_07_29_120000_them_ma_cskcb_vao_order_check_violations.php app/Services/OrderCheck/Support/ViolationContext.php app/Services/OrderCheck/OrderCheckEngine.php tests/Unit/ViPhamMaCoSoTest.php
git commit -m "feat(loc co so): them cot ma_cskcb vao vi pham y lenh va va nguoc tu HIS"
```

---

### Task 2: Bộ lọc cơ sở trên màn order-check

**Files:**
- Create: `resources/views/partials/ma_cskcb.blade.php`
- Modify: `app/Services/OrderCheck/ViolationQueryService.php` (trong `filtered()`)
- Modify: `app/Http/Controllers/KHTH/OrderCheckController.php` (trong `index()`)
- Modify: `resources/views/khth/order-check.blade.php`
- Test: `tests/Unit/ViolationQueryCoSoTest.php`

**Interfaces:**
- Consumes: cột `order_check_violations.ma_cskcb` từ Task 1.
- Produces: partial `resources/views/partials/ma_cskcb.blade.php` nhận biến `$danhSachCoSo` (mảng `mã => nhãn`), id thẻ `<select>` là `ma_cskcb`. Task 3 dùng lại partial này.

- [ ] **Step 1: Viết test đỏ**

Tạo `tests/Unit/ViolationQueryCoSoTest.php`:

```php
<?php

namespace Tests\Unit;

use App\Services\OrderCheck\ViolationQueryService;
use Illuminate\Http\Request;
use Tests\TestCase;

class ViolationQueryCoSoTest extends TestCase
{
    protected function sql(array $tham)
    {
        $q = (new ViolationQueryService())->filtered(Request::create('/', 'GET', $tham));

        return ['sql' => $q->toSql(), 'bind' => $q->getBindings()];
    }

    /** @test */
    public function khong_chon_co_so_thi_khong_loc()
    {
        $r = $this->sql([]);

        $this->assertNotContains('ma_cskcb', $r['sql']);
    }

    /** @test */
    public function chon_co_so_rong_thi_khong_loc()
    {
        // "Tat ca co so" gui len chuoi rong; filled() phai coi day la khong loc.
        $r = $this->sql(['ma_cskcb' => '']);

        $this->assertNotContains('ma_cskcb', $r['sql']);
    }

    /** @test */
    public function chon_co_so_thi_loc_dung_ma_do()
    {
        $r = $this->sql(['ma_cskcb' => '01929']);

        $this->assertContains('ma_cskcb', $r['sql']);
        $this->assertContains('01929', $r['bind']);
    }
}
```

- [ ] **Step 2: Chạy test, xác nhận đỏ**

```bash
vendor/bin/phpunit tests/Unit/ViolationQueryCoSoTest.php
```

Kỳ vọng: `chon_co_so_thi_loc_dung_ma_do` FAIL (SQL không chứa `ma_cskcb`); hai test kia PASS sẵn.

- [ ] **Step 3: Thêm mệnh đề lọc**

Trong `app/Services/OrderCheck/ViolationQueryService.php`, trong `filtered()`, thêm ngay sau khối `department_id`:

```php
        if ($request->filled('ma_cskcb')) {
            $q->where('ma_cskcb', $request->input('ma_cskcb'));
        }
```

Đặt trong `filtered()` là đủ cho cả ba đường `fetch`, `summary`, `export` — cả ba đều gọi method này.

- [ ] **Step 4: Chạy test, xác nhận xanh**

```bash
vendor/bin/phpunit tests/Unit/ViolationQueryCoSoTest.php
```

Kỳ vọng: PASS cả 3 test.

- [ ] **Step 5: Tạo partial ô chọn dùng chung**

Tạo `resources/views/partials/ma_cskcb.blade.php`:

```blade
{{-- O chon co so KCB. Dung chung cho man XML3176 va man order-check.
     Bien vao: $danhSachCoSo — mang ma => nhan, tu DanhSachCoSo::danhSach(). --}}
<div class="col-sm-2">
    <div class="form-group row">
        <label for="ma_cskcb">Cơ sở KCB</label>
        <select id="ma_cskcb" class="form-control select2">
            <option value="">Tất cả cơ sở</option>
            @foreach ($danhSachCoSo as $ma => $nhan)
                <option value="{{ $ma }}">{{ $nhan }}</option>
            @endforeach
        </select>
    </div>
</div>
```

Dùng `col-sm-2` cho khớp họ partial lọc của XML3176 (`partials/imported_by.blade.php` cùng dạng); màn order-check dùng `col-md-2` nhưng hai lớp này không xung đột trong Bootstrap 3.

- [ ] **Step 6: Truyền danh sách cơ sở xuống view order-check**

Trong `app/Http/Controllers/KHTH/OrderCheckController.php`, sửa `index()`:

```php
    public function index()
    {
        $rules = OrderCheckRule::orderBy('code')->get(['code', 'name']);
        $danhSachCoSo = \App\Services\BHYT\DanhSachCoSo::danhSach();

        return view('khth.order-check', compact('rules', 'danhSachCoSo'));
    }
```

- [ ] **Step 7: Gắn ô chọn vào blade order-check**

Trong `resources/views/khth/order-check.blade.php`, thêm ngay sau khối `<div class="col-md-2">` chứa `id="department_id"`:

```blade
      @include('partials.ma_cskcb')
```

- [ ] **Step 8: Gửi tham số lên máy chủ**

Trong cùng tệp, sửa hàm `filters()` (dòng 80) — thêm `ma_cskcb` vào đối tượng trả về:

```javascript
function filters(){
  return { date_from:$('#date_from').val(), date_to:$('#date_to').val(), severity:$('#severity').val(), status:$('#status').val(), rule_code:$('#rule_code').val(), service_req_type_id:$('#service_req_type_id').val(), department_id:$('#department_id').val(), ma_cskcb:$('#ma_cskcb').val(), keyword:$('#keyword').val() };
}
```

Đây là **nơi duy nhất** gom tham số; bảng, phần tổng hợp và nút xuất Excel đều gọi nó, nên sửa một chỗ là đủ cho cả ba.

- [ ] **Step 9: Kiểm cú pháp và chạy suite**

```bash
php -l app/Services/OrderCheck/ViolationQueryService.php && php -l app/Http/Controllers/KHTH/OrderCheckController.php && vendor/bin/phpunit --testsuite Unit
```

Kỳ vọng: không lỗi cú pháp; suite Unit OK.

- [ ] **Step 10: Commit**

```bash
git add app/Services/OrderCheck/ViolationQueryService.php app/Http/Controllers/KHTH/OrderCheckController.php resources/views/partials/ma_cskcb.blade.php resources/views/khth/order-check.blade.php tests/Unit/ViolationQueryCoSoTest.php
git commit -m "feat(loc co so): bo loc co so tren man vi pham y lenh"
```

---

### Task 3: Bộ lọc cơ sở trên màn XML3176

**Files:**
- Create: `app/Services/BHYT/LocCoSo.php`
- Modify: `app/Http/Controllers/BHYT/BHYTXml3176Controller.php` (`index()` và cả 3 nhánh của `fetchData()`)
- Modify: `resources/views/bhyt/xml3176/partials/search.blade.php`
- Modify: `resources/views/bhyt/xml3176/index.blade.php`
- Test: `tests/Unit/LocCoSoTest.php`

**Interfaces:**
- Consumes: partial `resources/views/partials/ma_cskcb.blade.php` từ Task 2 (nhận `$danhSachCoSo`, tuỳ chọn `$idMaCskcb`).
- Produces: `App\Services\BHYT\LocCoSo::maHopLe($ma, array $danhSach)` trả string (mã hợp lệ hoặc `''`), và `LocCoSo::ap($query, $ma, array $danhSach)` trả về chính `$query`.

**Lệch có chủ ý so với spec:** spec viết `protected function locTheoCoSo($query, Request $request)` nằm trong controller. Plan này đổi thành lớp riêng `App\Services\BHYT\LocCoSo` với method tĩnh, vì chính spec cũng yêu cầu kiểm thử nó bằng cách "gọi trên một query dựng sẵn" — mà method `protected` của controller thì không gọi được từ test nếu không dùng reflection. Hành vi giữ nguyên đúng như spec mô tả.

**Vì sao XML3176 kiểm giá trị còn order-check thì không:** `fetchData` có ba nhánh dựng truy vấn riêng biệt, nên điều kiện lọc phải nằm ở một chỗ dùng chung — đã tách ra lớp riêng thì kiểm giá trị luôn là gần như miễn phí. Bên order-check chỉ có một chỗ lọc và mã sai chỉ dẫn tới 0 dòng, vô hại.

- [ ] **Step 1: Viết test đỏ**

Tạo `tests/Unit/LocCoSoTest.php`:

```php
<?php

namespace Tests\Unit;

use DB;
use App\Services\BHYT\LocCoSo;
use Tests\TestCase;

class LocCoSoTest extends TestCase
{
    protected function danhSach()
    {
        return ['01929' => 'Co so A', '37470' => 'Co so B'];
    }

    /** @test */
    public function ma_rong_thi_khong_loc()
    {
        $this->assertSame('', LocCoSo::maHopLe('', $this->danhSach()));
        $this->assertSame('', LocCoSo::maHopLe(null, $this->danhSach()));
    }

    /** @test */
    public function ma_ngoai_danh_sach_thi_khong_loc()
    {
        $this->assertSame('', LocCoSo::maHopLe('99999', $this->danhSach()));
    }

    /** @test */
    public function ma_hop_le_thi_giu_nguyen()
    {
        $this->assertSame('01929', LocCoSo::maHopLe('01929', $this->danhSach()));
        $this->assertSame('01929', LocCoSo::maHopLe('  01929  ', $this->danhSach()));
    }

    /** @test */
    public function ap_ma_hop_le_thi_them_dieu_kien()
    {
        $q = DB::table('xml3176_xml1s');
        LocCoSo::ap($q, '01929', $this->danhSach());

        $this->assertContains('ma_cskcb', $q->toSql());
        $this->assertContains('01929', $q->getBindings());
    }

    /** @test */
    public function ap_ma_khong_hop_le_thi_khong_them_gi()
    {
        $q = DB::table('xml3176_xml1s');
        LocCoSo::ap($q, '99999', $this->danhSach());

        $this->assertNotContains('ma_cskcb', $q->toSql());
        $this->assertSame([], $q->getBindings());
    }
}
```

- [ ] **Step 2: Chạy test, xác nhận đỏ**

```bash
vendor/bin/phpunit tests/Unit/LocCoSoTest.php
```

Kỳ vọng: cả 5 test FAIL với `Class 'App\Services\BHYT\LocCoSo' not found`.

- [ ] **Step 3: Viết lớp LocCoSo**

Tạo `app/Services/BHYT/LocCoSo.php`:

```php
<?php

namespace App\Services\BHYT;

/**
 * Loc danh sach ho so XML3176 theo ma co so KCB.
 *
 * Tach rieng vi BHYTXml3176Controller::fetchData co BA nhanh dung truy van khac nhau
 * (theo ma ho so / theo ma benh nhan / theo khoang ngay). Neu chi them dieu kien vao mot
 * nhanh thi hai nhanh kia bo qua bo loc IM LANG: van ra ket qua, chi la sai pham vi, khong
 * co dau hieu gi bao.
 */
class LocCoSo
{
    /**
     * Ma dung de loc, hoac chuoi rong nghia la KHONG loc.
     *
     * Ham THUAN. Ma khong nam trong danh sach thi coi nhu khong loc thay vi nem loi: day
     * la man DANH SACH, khong phai thao tac ghi.
     *
     * @param string|null $ma
     * @param array $danhSach mang ma => nhan
     * @return string
     */
    public static function maHopLe($ma, array $danhSach)
    {
        $ma = trim((string) $ma);

        if ($ma === '' || !array_key_exists($ma, $danhSach)) {
            return '';
        }

        return $ma;
    }

    /**
     * Ap dieu kien loc vao query neu ma hop le.
     *
     * @param mixed $query Eloquent Builder hoac Query Builder
     * @param string|null $ma
     * @param array $danhSach mang ma => nhan
     * @param string $cot ten cot chua ma co so
     * @return mixed chinh $query
     */
    public static function ap($query, $ma, array $danhSach, $cot = 'ma_cskcb')
    {
        $ma = self::maHopLe($ma, $danhSach);

        if ($ma !== '') {
            $query->where($cot, $ma);
        }

        return $query;
    }
}
```

- [ ] **Step 4: Chạy test, xác nhận xanh**

```bash
vendor/bin/phpunit tests/Unit/LocCoSoTest.php
```

Kỳ vọng: PASS cả 5 test.

- [ ] **Step 5: Áp lọc cho cả ba nhánh của fetchData**

Trong `app/Http/Controllers/BHYT/BHYTXml3176Controller.php`, trong `fetchData()`:

Thêm vào cụm đọc tham số ở đầu method, ngay sau dòng đọc `$imported_by`:

```php
        $ma_cskcb = $request->input('ma_cskcb');
        $danhSachCoSo = \App\Services\BHYT\DanhSachCoSo::danhSach();
```

Rồi trong **cả ba** nhánh, ngay sau khối `if (!\Auth::user()->hasRole([...]))` tương ứng (tức sau khi query đã dựng xong ở nhánh đó), thêm:

```php
                \App\Services\BHYT\LocCoSo::ap($result, $ma_cskcb, $danhSachCoSo);
```

Ba nhánh là: nhánh `if ($treatment_code)`, nhánh `if ($patient_code)`, và nhánh `else` cuối. Neo theo nội dung, đừng neo theo số dòng.

Sau khi sửa, tự đối chiếu: `grep -c "LocCoSo::ap" app/Http/Controllers/BHYT/BHYTXml3176Controller.php` phải cho **3**.

- [ ] **Step 6: Truyền danh sách cơ sở xuống view**

Trong cùng tệp, sửa `index()`:

```php
    public function index()
    {
        return view('bhyt.xml3176.index', [
            'danhSachCoSo' => \App\Services\BHYT\DanhSachCoSo::danhSach(),
        ]);
    }
```

- [ ] **Step 7: Gắn ô chọn vào form lọc**

Trong `resources/views/bhyt/xml3176/partials/search.blade.php`, thêm vào **khối `form-group row` thứ hai**, ngay sau `@include('partials.treatment_type_fillter')`:

```blade
                @include('partials.ma_cskcb')
```

- [ ] **Step 8: Gửi tham số DataTables**

Trong `resources/views/bhyt/xml3176/index.blade.php`, trong khối `ajax.data`, thêm sau dòng `d.imported_by = ...`:

```javascript
                    d.ma_cskcb = $('#ma_cskcb').val();
```

- [ ] **Step 9: Kiểm cú pháp và chạy suite**

```bash
php -l app/Services/BHYT/LocCoSo.php && php -l app/Http/Controllers/BHYT/BHYTXml3176Controller.php && vendor/bin/phpunit --testsuite Unit
```

Kỳ vọng: không lỗi cú pháp; suite Unit OK.

- [ ] **Step 10: Commit**

```bash
git add app/Services/BHYT/LocCoSo.php app/Http/Controllers/BHYT/BHYTXml3176Controller.php resources/views/bhyt/xml3176/ tests/Unit/LocCoSoTest.php
git commit -m "feat(loc co so): bo loc co so tren man danh sach XML3176"
```

---

### Task 4: Cập nhật tài liệu

**Files:**
- Modify: `docs/tai-lieu-tong-hop-xml3176-order-check.md`

**Interfaces:**
- Consumes: kết quả Task 1-3.
- Produces: không có gì.

- [ ] **Step 1: Thêm mục mô tả bộ lọc cơ sở**

Trong `docs/tai-lieu-tong-hop-xml3176-order-check.md`, chèn đoạn dưới đây vào **cuối mục `## 4. So sánh & điểm chung hai module`**, ngay **sau** tiểu mục `### 4.1` đã có và **trước** dòng tiêu đề `## 5. Tóm tắt chuẩn bị`:

```markdown
### 4.2. Lọc theo cơ sở KCB (cập nhật 29/07/2026)

Cả màn danh sách hồ sơ XML3176 lẫn màn vi phạm y lệnh đều có ô chọn **Cơ sở KCB**; để
trống là xem tất cả. Danh sách cơ sở lấy từ `App\Services\BHYT\DanhSachCoSo::danhSach()` —
cùng nguồn với màn nhập khẩu danh mục, nên ba nơi luôn hiện giống nhau.

**XML3176** lọc trên `xml3176_xml1s.ma_cskcb` (đã có sẵn dữ liệu). Lưu ý cho người bảo trì:
`BHYTXml3176Controller::fetchData` có **ba nhánh** dựng truy vấn khác nhau (theo mã hồ sơ /
theo mã bệnh nhân / theo khoảng ngày). Điều kiện lọc được áp qua `App\Services\BHYT\LocCoSo::ap()`
ở **cả ba**. Thêm nhánh mới mà quên gọi nó thì bộ lọc bị bỏ qua **im lặng** — vẫn ra kết
quả, chỉ là sai phạm vi.

**Order-check** lọc trên cột `order_check_violations.ma_cskcb`, thêm vào ngày 29/07/2026.
Không thể nối bảng lúc truy vấn vì vi phạm nằm ở MySQL còn HIS ở Oracle. Mã cơ sở được ghi
lúc quét, đi theo đường `HisOrderSource` → `OrderContext::$maCskcb` →
`ViolationContext::$maCskcb` → `OrderCheckEngine::persist()`. `ViolationContext::fromOrderContext()`
là một danh sách khoá **chép tay** — thêm trường mà quên chỗ đó thì giá trị im lặng không
bao giờ được ghi.

Dữ liệu cũ đã được vá ngược một lần trong migration `2026_07_29_120000` bằng cách tra
`treatment_id` sang HIS. Khoảng **72 dòng** không tra ra được (đợt điều trị đã biến mất
khỏi `his_treatment`) nên để **trống** mã cơ sở — chúng không hiện khi lọc theo một cơ sở
cụ thể, chỉ hiện khi để trống ô chọn. Cố ý không gán mã mặc định: gán bừa thì chúng trông
như đã biết chắc thuộc cơ sở nào đó, trong khi không ai kiểm chứng được nữa.
```

- [ ] **Step 2: Commit**

```bash
git add docs/tai-lieu-tong-hop-xml3176-order-check.md
git commit -m "docs(loc co so): ghi lai bo loc co so cho hai man"
```

---

## Nghiệm thu cuối

- [ ] `vendor/bin/phpunit --testsuite Unit` — OK, không đỏ.
- [ ] `php artisan config:clear` (không bắt buộc lần này vì không sửa tệp config, nhưng vô hại).
- [ ] Mở màn danh sách XML3176: ô **Cơ sở KCB** hiện ra với hai lựa chọn 01929 và 37470.
- [ ] Chọn `01929` rồi bấm tải: chỉ ra hồ sơ của cơ sở đó. Đối chiếu tổng số với truy vấn `SELECT ma_cskcb, count(*) FROM xml3176_xml1s GROUP BY ma_cskcb` — kỳ vọng 166 với 01929, 44 với 37470.
- [ ] Trên màn XML3176, nhập một mã hồ sơ vào ô **Mã hồ sơ** *đồng thời* chọn một cơ sở **không** chứa hồ sơ đó → phải ra **rỗng**. Đây là bài kiểm quan trọng nhất: nếu ra kết quả thì nhánh `treatment_code` đang bỏ qua bộ lọc.
- [ ] Mở màn vi phạm y lệnh: ô **Cơ sở KCB** hiện ra; chọn `01929` thì cả bảng, bốn ô số tổng hợp và nút xuất Excel đều đổi theo.
