# Order-check API gộp lỗi — Kế hoạch thực thi (Phần A)

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** `GET /api/order-check/violations` trả về ba nhóm lỗi của một đợt điều trị (sai sót y lệnh, lỗi tra thẻ BHYT, lỗi XML3176) trong một lần gọi.

**Architecture:** Toàn bộ logic truy vấn nằm trong service mới `App\Services\OrderCheck\TreatmentIssueService`, trả mảng thuần và không phụ thuộc `Request`. Controller chỉ đọc tham số, gọi service, bọc JSON theo khuôn `{success, data, summary, meta}`. Ba nhóm lấy dữ liệu bằng ba hàm tách rời, đổi quy tắc một nguồn không đụng hai nguồn kia.

**Tech Stack:** Laravel 5.5, PHP 7.0, MySQL (`qlbv`), PHPUnit 6, SQLite in-memory cho test.

**Spec:** `docs/superpowers/specs/2026-08-06-order-check-api-gop-loi-design.md` (Phần A).

## Global Constraints

- **Cú pháp PHP 7.0**: không dùng kiểu trả về `void`/nullable type (`?string`), không typed property, không arrow function, không `str_contains`. Mảng dùng cú pháp `[]`.
- **Laravel 5.5**: không có `Str::of`; `TestResponse::json()` **không nhận tham số khoá** (chỉ có từ 5.6) — lấy cả mảng rồi tự đi xuống; `setUp()` trong test **không** có khai báo kiểu trả về (PHPUnit 6).
- **Chỉ thực thi Phần A của spec.** Phần B (bảo mật, hiệu năng, kể cả index `treatment_code`) **không** làm trong kế hoạch này — đã thống nhất chỉ dừng ở tài liệu.
- **Không sửa** `ApiAuthMiddleware`, `config/organization.php`, `routes/api.php`.
- **Không dùng `RefreshDatabase`** trong bất kỳ test nào: `.env` của dự án trỏ `DB_DATABASE=qlbv` là CSDL phát triển thật, `RefreshDatabase` sẽ xoá sạch nó. Test dựng bảng SQLite in-memory bằng cách ghi đè `database.connections.mysql` (xem Task 1).
- **Trần cứng 500 dòng/nhóm** — hằng `TreatmentIssueService::TRAN_MOI_NHOM`.
- Tên biến/hàm và chú thích tiếng Việt, theo đúng phong cách của tệp xung quanh.
- **Chạy toàn bộ test trước khi bắt đầu** để biết mốc: bộ test hiện có sẵn một số ca đỏ không liên quan. Không lấy trạng thái đỏ sẵn làm kết quả của thay đổi này.

## File Structure

| Tệp | Trách nhiệm |
|---|---|
| `app/Services/OrderCheck/TreatmentIssueService.php` (tạo) | Gộp ba nguồn lỗi theo một đợt điều trị. Không biết gì về HTTP. |
| `app/Http/Controllers/KHTH/OrderCheckController.php` (sửa `apiViolations`, thêm 2 hàm phụ) | Đọc tham số, gọi service, bọc JSON, khuôn lỗi thống nhất. |
| `tests/Support/DungBangLoiDotDieuTriSqlite.php` (tạo) | Trait dựng 4 bảng SQLite in-memory dùng chung cho unit test và feature test. |
| `tests/Unit/OrderCheck/TreatmentIssueServiceTest.php` (tạo) | Test service ở mức truy vấn và biến đổi dữ liệu. |
| `tests/Feature/OrderCheckApiTest.php` (tạo) | Test endpoint qua HTTP: khuôn response, mã lỗi, xác thực. |
| `docs/order-check/API-TRA-CUU-LOI.md` (tạo) | Tài liệu cho bên gọi (HIS). |

---

### Task 1: Hạ tầng test + nhóm `order_check`

**Files:**
- Create: `tests/Support/DungBangLoiDotDieuTriSqlite.php`
- Create: `tests/Unit/OrderCheck/TreatmentIssueServiceTest.php`
- Create: `app/Services/OrderCheck/TreatmentIssueService.php`

**Interfaces:**
- Consumes: `App\Models\OrderCheck\OrderCheckViolation` (Eloquent, `$table = 'order_check_violations'`, `$guarded = []`).
- Produces:
  - `Tests\Support\DungBangLoiDotDieuTriSqlite::chuanBiBangLoi()` — dựng 4 bảng SQLite.
  - `TreatmentIssueService::TRAN_MOI_NHOM = 500`
  - `TreatmentIssueService::cua($treatmentCode = null, $treatmentId = null, array $tuyChon = [])` → `['data' => ['treatment_code'=>string|null, 'order_check'=>array, 'hein_card'=>array, 'xml3176'=>array], 'summary' => array]`. Task 2–4 mở rộng chính hàm này.

- [ ] **Step 1: Tạo trait dựng bảng SQLite**

Tạo `tests/Support/DungBangLoiDotDieuTriSqlite.php`:

```php
<?php

namespace Tests\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Dung 4 bang lien quan toi API tra cuu loi cua mot dot dieu tri, trong SQLite bo nho.
 *
 * VI SAO KHONG DUNG RefreshDatabase: .env cua du an tro DB_DATABASE=qlbv - co so du lieu
 * phat trien that. RefreshDatabase se xoa sach no.
 *
 * VI SAO GHI DE KET NOI TEN 'mysql' chu khong doi database.default: cac model va cau
 * DB::table() deu di theo ket noi mac dinh, ma mac dinh do ten la 'mysql'. Ghi de chinh
 * ket noi ay la cach duy nhat chan moi duong ra CSDL that.
 *
 * KHONG chay migration that: thu muc database/migrations chua nhieu migration phu thuoc
 * cu phap MySQL va ket noi Oracle, khong chay duoc tren SQLite.
 */
trait DungBangLoiDotDieuTriSqlite
{
    protected function chuanBiBangLoi()
    {
        config([
            'database.default' => 'mysql',
            'database.connections.mysql' => [
                'driver'   => 'sqlite',
                'database' => ':memory:',
                'prefix'   => '',
            ],
        ]);

        DB::purge('mysql');

        Schema::create('order_check_violations', function ($t) {
            $t->increments('id');
            $t->string('rule_code', 100);
            $t->unsignedBigInteger('treatment_id')->nullable();
            $t->string('treatment_code', 50)->nullable();
            $t->string('order_ref_type', 30);
            $t->unsignedBigInteger('order_ref_id');
            $t->string('severity', 20)->default('warning');
            $t->text('message');
            $t->text('detail')->nullable();
            $t->string('dedup_key', 200);
            $t->string('status', 20)->default('new');
            $t->dateTime('detected_at');
            $t->timestamps();
        });

        Schema::create('check_hein_cards', function ($t) {
            $t->increments('id');
            $t->string('ma_lk', 100);
            $t->string('ma_tracuu', 10);
            $t->string('ma_kiemtra', 10);
            $t->string('ma_ketqua', 255)->nullable();
            $t->text('ghi_chu')->nullable();
            $t->string('ma_the', 255)->nullable();
            $t->timestamps();
        });

        Schema::create('xml3176_error_results', function ($t) {
            $t->increments('id');
            $t->string('xml');
            $t->string('ma_lk');
            $t->integer('stt');
            $t->string('ngay_yl')->nullable();
            $t->string('ngay_kq')->nullable();
            $t->string('error_code');
            $t->text('description')->nullable();
            $t->boolean('critical_error')->default(false);
            $t->timestamps();
        });

        Schema::create('xml3176_error_catalogs', function ($t) {
            $t->increments('id');
            $t->string('xml');
            $t->string('error_code');
            $t->string('error_name')->nullable();
            $t->text('description')->nullable();
            $t->boolean('critical_error')->default(false);
            $t->boolean('is_check')->default(true);
            $t->timestamps();
        });
    }

    /** Them mot vi pham y lenh. $ghiDe ghi de bat ky cot nao. */
    protected function themViPham(array $ghiDe = [])
    {
        static $dem = 0;
        $dem++;

        DB::table('order_check_violations')->insert(array_merge([
            'rule_code'      => 'REQ_TIME_INVALID',
            'treatment_id'   => 9001,
            'treatment_code' => '01013250800123',
            'order_ref_type' => 'service_req',
            'order_ref_id'   => 123456,
            'severity'       => 'warning',
            'message'        => 'Loi y lenh ' . $dem,
            'detail'         => null,
            'dedup_key'      => 'dedup-' . $dem,
            'status'         => 'new',
            'detected_at'    => '2026-08-06 09:00:00',
            'created_at'     => '2026-08-06 09:00:00',
            'updated_at'     => '2026-08-06 09:00:00',
        ], $ghiDe));
    }
}
```

- [ ] **Step 2: Viết test thất bại cho nhóm `order_check`**

Tạo `tests/Unit/OrderCheck/TreatmentIssueServiceTest.php`:

```php
<?php

namespace Tests\Unit\OrderCheck;

use App\Services\OrderCheck\TreatmentIssueService;
use Illuminate\Support\Facades\DB;
use Tests\Support\DungBangLoiDotDieuTriSqlite;
use Tests\TestCase;

class TreatmentIssueServiceTest extends TestCase
{
    use DungBangLoiDotDieuTriSqlite;

    protected function setUp()
    {
        parent::setUp();

        $this->chuanBiBangLoi();
    }

    protected function dichVu()
    {
        return new TreatmentIssueService();
    }

    /** @test */
    public function loc_vi_pham_theo_ma_dot_dieu_tri()
    {
        $this->themViPham(['treatment_code' => '01013250800123']);
        $this->themViPham(['treatment_code' => 'DOT-KHAC', 'treatment_id' => 9002]);

        $ketQua = $this->dichVu()->cua('01013250800123');

        $this->assertCount(1, $ketQua['data']['order_check']);
        $this->assertEquals('01013250800123', $ketQua['data']['treatment_code']);
    }

    /** @test */
    public function mac_dinh_bo_dong_false_positive()
    {
        $this->themViPham(['status' => 'new']);
        $this->themViPham(['status' => 'false_positive']);

        $ketQua = $this->dichVu()->cua('01013250800123');

        $this->assertCount(1, $ketQua['data']['order_check']);
        $this->assertEquals('new', $ketQua['data']['order_check'][0]['status']);
    }

    /** @test */
    public function truyen_status_tuong_minh_thi_lay_dung_trang_thai_do()
    {
        $this->themViPham(['status' => 'new']);
        $this->themViPham(['status' => 'false_positive']);

        $ketQua = $this->dichVu()->cua('01013250800123', null, ['status' => 'false_positive']);

        $this->assertCount(1, $ketQua['data']['order_check']);
        $this->assertEquals('false_positive', $ketQua['data']['order_check'][0]['status']);
    }

    /** @test */
    public function detail_json_duoc_giai_ma_thanh_mang()
    {
        $this->themViPham(['detail' => '{"ma_dv":"XN001"}']);

        $ketQua = $this->dichVu()->cua('01013250800123');

        $this->assertEquals(['ma_dv' => 'XN001'], $ketQua['data']['order_check'][0]['detail']);
    }

    /**
     * Mot dong detail hong khong duoc lam chet ca lan goi API.
     *
     * @test
     */
    public function detail_hong_thi_tra_null_chu_khong_nem_loi()
    {
        $this->themViPham(['detail' => '{khong-phai-json']);

        $ketQua = $this->dichVu()->cua('01013250800123');

        $this->assertNull($ketQua['data']['order_check'][0]['detail']);
    }

    /** @test */
    public function dot_sach_tra_ba_mang_rong()
    {
        $ketQua = $this->dichVu()->cua('KHONG-CO-DOT-NAY');

        $this->assertSame([], $ketQua['data']['order_check']);
        $this->assertSame([], $ketQua['data']['hein_card']);
        $this->assertSame([], $ketQua['data']['xml3176']);
    }
}
```

- [ ] **Step 3: Chạy test để xác nhận đỏ**

Run: `vendor/bin/phpunit tests/Unit/OrderCheck/TreatmentIssueServiceTest.php`
Expected: FAIL — `Class 'App\Services\OrderCheck\TreatmentIssueService' not found`.

- [ ] **Step 4: Viết service tối thiểu**

Tạo `app/Services/OrderCheck/TreatmentIssueService.php`:

```php
<?php

namespace App\Services\OrderCheck;

use App\Models\OrderCheck\OrderCheckViolation;

/**
 * Gop ba nguon loi cua CUNG MOT dot dieu tri: sai sot y lenh, loi tra the BHYT, loi
 * XML3176. Ba bang khoa theo cung mot gia tri (ma_lk = treatment_code).
 *
 * Khong phu thuoc Request va khong tu boc HTTP: test duoc khong can goi HTTP, va man
 * hinh noi bo khac dung lai duoc.
 */
class TreatmentIssueService
{
    /** Tran cung so dong moi nhom - may chu gioi han PHP 128MB/120s. */
    const TRAN_MOI_NHOM = 500;

    /** Trang thai nguoi dung da xac nhan khong phai loi; day sang HIS chi gay nhieu. */
    const BO_QUA = 'false_positive';

    /**
     * @param  string|null $treatmentCode Ma dot dieu tri (= ma_lk)
     * @param  int|string|null $treatmentId ID dot dieu tri tren HIS
     * @param  array $tuyChon ['status' => string|null]
     * @return array ['data' => [...], 'summary' => [...]]
     */
    public function cua($treatmentCode = null, $treatmentId = null, array $tuyChon = [])
    {
        $status = isset($tuyChon['status']) ? $tuyChon['status'] : null;

        $viPham = $this->viPhamYLenh($treatmentCode, $treatmentId, $status);

        return [
            'data' => [
                'treatment_code' => $this->rong($treatmentCode) ? null : $treatmentCode,
                'order_check' => $viPham,
                'hein_card' => [],
                'xml3176' => [],
            ],
            'summary' => [],
        ];
    }

    protected function viPhamYLenh($treatmentCode, $treatmentId, $status)
    {
        $q = OrderCheckViolation::query();

        if (!$this->rong($treatmentCode)) {
            $q->where('treatment_code', $treatmentCode);
        }
        if (!$this->rong($treatmentId)) {
            $q->where('treatment_id', $treatmentId);
        }

        if ($this->rong($status)) {
            $q->where('status', '!=', self::BO_QUA);
        } else {
            $q->where('status', $status);
        }

        $dong = $q->orderBy('detected_at', 'desc')
            ->limit(self::TRAN_MOI_NHOM)
            ->get([
                'id', 'rule_code', 'severity', 'order_ref_type', 'order_ref_id',
                'message', 'detail', 'status', 'detected_at',
            ]);

        $ra = [];

        foreach ($dong as $v) {
            $ra[] = [
                'id' => (int) $v->id,
                'rule_code' => $v->rule_code,
                'severity' => $v->severity,
                'order_ref_type' => $v->order_ref_type,
                'order_ref_id' => (int) $v->order_ref_id,
                'message' => $v->message,
                'detail' => $this->giaiMaChiTiet($v->detail),
                'status' => $v->status,
                'detected_at' => (string) $v->detected_at,
            ];
        }

        return $ra;
    }

    /** JSON hong o MOT dong khong duoc lam chet ca lan goi: tra null cho rieng dong do. */
    protected function giaiMaChiTiet($detail)
    {
        if ($this->rong($detail)) {
            return null;
        }

        if (is_array($detail)) {
            return $detail;
        }

        $giaiMa = json_decode($detail, true);

        return json_last_error() === JSON_ERROR_NONE && is_array($giaiMa) ? $giaiMa : null;
    }

    protected function rong($gt)
    {
        return $gt === null || $gt === '';
    }
}
```

- [ ] **Step 5: Chạy test để xác nhận xanh**

Run: `vendor/bin/phpunit tests/Unit/OrderCheck/TreatmentIssueServiceTest.php`
Expected: PASS — 6 tests.

- [ ] **Step 6: Commit**

```bash
git add tests/Support/DungBangLoiDotDieuTriSqlite.php tests/Unit/OrderCheck/TreatmentIssueServiceTest.php app/Services/OrderCheck/TreatmentIssueService.php
git commit -m "feat(order-check): service gop loi dot dieu tri - nhom y lenh"
```

---

### Task 2: Nhóm `hein_card` (lỗi tra thẻ, tối thiểu PII)

**Files:**
- Modify: `app/Services/OrderCheck/TreatmentIssueService.php`
- Modify: `tests/Unit/OrderCheck/TreatmentIssueServiceTest.php`

**Interfaces:**
- Consumes: `App\Models\CheckBHYT\check_hein_card` với scope sẵn có `scopeChiLoi()` (`ma_tracuu != '000'` HOẶC `ma_kiemtra != '00'`). **Dùng lại scope, không viết lại điều kiện** — quy tắc này đã được cân nhắc và ghi chú kỹ trong model.
- Produces: `TreatmentIssueService::cheMaThe($maThe)` → `string|null` (public static, dùng lại được ở nơi khác).

- [ ] **Step 1: Viết test thất bại**

Thêm vào `tests/Unit/OrderCheck/TreatmentIssueServiceTest.php` (trong class, sau các test có sẵn):

```php
    protected function themTraThe(array $ghiDe = [])
    {
        DB::table('check_hein_cards')->insert(array_merge([
            'ma_lk'      => '01013250800123',
            'ma_tracuu'  => '000',
            'ma_kiemtra' => '00',
            'ma_ketqua'  => 'Hop le',
            'ghi_chu'    => null,
            'ma_the'     => 'DN4010112345678',
            'created_at' => '2026-08-05 14:00:00',
            'updated_at' => '2026-08-05 14:03:00',
        ], $ghiDe));
    }

    /** @test */
    public function the_hop_le_thi_nhom_tra_the_rong()
    {
        $this->themTraThe(['ma_tracuu' => '000', 'ma_kiemtra' => '00']);

        $ketQua = $this->dichVu()->cua('01013250800123');

        $this->assertSame([], $ketQua['data']['hein_card']);
    }

    /** @test */
    public function the_bat_thuong_thi_tra_ve_mot_dong()
    {
        $this->themTraThe(['ma_tracuu' => '005', 'ma_ketqua' => 'The het han']);

        $ketQua = $this->dichVu()->cua('01013250800123');

        $this->assertCount(1, $ketQua['data']['hein_card']);
        $this->assertEquals('005', $ketQua['data']['hein_card'][0]['ma_tracuu']);
        $this->assertEquals('The het han', $ketQua['data']['hein_card'][0]['ma_ketqua']);
        $this->assertEquals('2026-08-05 14:03:00', $ketQua['data']['hein_card'][0]['checked_at']);
    }

    /**
     * HIS da co san thong tin benh nhan; day them PII sang chi lam tang be mat lo lot.
     *
     * @test
     */
    public function khong_tra_ve_thong_tin_dinh_danh_benh_nhan()
    {
        $this->themTraThe(['ma_kiemtra' => '01']);

        $dong = $this->dichVu()->cua('01013250800123')['data']['hein_card'][0];

        foreach (['ho_ten', 'ngay_sinh', 'dia_chi', 'maso_bhxh', 'ma_the'] as $cot) {
            $this->assertArrayNotHasKey($cot, $dong);
        }

        $this->assertEquals('****5678', $dong['ma_the_masked']);
    }

    /** @test */
    public function che_ma_the_xu_ly_the_rong_va_the_ngan()
    {
        $this->assertNull(TreatmentIssueService::cheMaThe(null));
        $this->assertNull(TreatmentIssueService::cheMaThe('   '));
        $this->assertEquals('****AB', TreatmentIssueService::cheMaThe('AB'));
    }
```

- [ ] **Step 2: Chạy test để xác nhận đỏ**

Run: `vendor/bin/phpunit tests/Unit/OrderCheck/TreatmentIssueServiceTest.php`
Expected: FAIL — `the_bat_thuong_thi_tra_ve_mot_dong` báo mảng rỗng, và `Call to undefined method ...::cheMaThe()`.

- [ ] **Step 3: Bổ sung service**

Trong `app/Services/OrderCheck/TreatmentIssueService.php`, thêm `use` ở đầu tệp:

```php
use App\Models\CheckBHYT\check_hein_card;
```

Trong `cua()`, thay `'hein_card' => [],` bằng:

```php
                'hein_card' => $this->rong($treatmentCode) ? [] : $this->loiTraThe($treatmentCode),
```

Thêm hai hàm vào class:

```php
    /**
     * Chi tra dong CO BAT THUONG. Dung lai scope chiLoi() cua model: quy tac "khac 000
     * HOAC khac 00" da duoc can nhac va ghi chu ky trong do, viet lai o day la nhan doi
     * mot quy tac de lech.
     */
    protected function loiTraThe($maLk)
    {
        $dong = check_hein_card::where('ma_lk', $maLk)->chiLoi()->get();

        $ra = [];

        foreach ($dong as $t) {
            $ra[] = [
                'ma_tracuu' => $t->ma_tracuu,
                'ma_kiemtra' => $t->ma_kiemtra,
                'ma_ketqua' => $t->ma_ketqua,
                'ghi_chu' => $t->ghi_chu,
                'ma_the_masked' => self::cheMaThe($t->ma_the),
                'checked_at' => $t->updated_at ? (string) $t->updated_at : null,
            ];
        }

        return $ra;
    }

    /** Chi giu 4 ky tu cuoi - du de doi chieu, khong du de tai su dung. */
    public static function cheMaThe($maThe)
    {
        $maThe = trim((string) $maThe);

        return $maThe === '' ? null : '****' . substr($maThe, -4);
    }
```

- [ ] **Step 4: Chạy test để xác nhận xanh**

Run: `vendor/bin/phpunit tests/Unit/OrderCheck/TreatmentIssueServiceTest.php`
Expected: PASS — 10 tests.

- [ ] **Step 5: Commit**

```bash
git add app/Services/OrderCheck/TreatmentIssueService.php tests/Unit/OrderCheck/TreatmentIssueServiceTest.php
git commit -m "feat(order-check): them nhom loi tra the BHYT, che bot ma the"
```

---

### Task 3: Nhóm `xml3176` (join danh mục theo cặp `xml` + `error_code`)

**Files:**
- Modify: `app/Services/OrderCheck/TreatmentIssueService.php`
- Modify: `tests/Unit/OrderCheck/TreatmentIssueServiceTest.php`

**Interfaces:**
- Consumes: bảng `xml3176_error_results` (khoá `ma_lk`) và `xml3176_error_catalogs` (**unique `(xml, error_code)`**).
- Produces: khoá `xml3176` trong `data`, mỗi phần tử có `xml`, `stt`, `error_code`, `error_name`, `description`, `critical_error`, `ngay_yl`, `ngay_kq`.

**Lưu ý bắt buộc:** join theo **cả hai cột** `xml` và `error_code`. Quan hệ `hasOne` sẵn có trong `App\Models\BHYT\Xml3176ErrorResult` chỉ nối `error_code`, sẽ nhân dòng khi một mã lỗi tồn tại ở nhiều loại XML — **không dùng lại quan hệ đó**.

- [ ] **Step 1: Viết test thất bại**

Thêm vào `tests/Unit/OrderCheck/TreatmentIssueServiceTest.php`:

```php
    protected function themLoiXml(array $ghiDe = [])
    {
        DB::table('xml3176_error_results')->insert(array_merge([
            'xml'            => 'XML1',
            'ma_lk'          => '01013250800123',
            'stt'            => 1,
            'ngay_yl'        => '20260805',
            'ngay_kq'        => '20260805',
            'error_code'     => 'L001',
            'description'    => 'Chi tiet loi',
            'critical_error' => 1,
            'created_at'     => '2026-08-05 15:00:00',
            'updated_at'     => '2026-08-05 15:00:00',
        ], $ghiDe));
    }

    protected function themDanhMucLoi(array $ghiDe = [])
    {
        DB::table('xml3176_error_catalogs')->insert(array_merge([
            'xml'            => 'XML1',
            'error_code'     => 'L001',
            'error_name'     => 'Sai ma the BHYT',
            'description'    => null,
            'critical_error' => 1,
            'is_check'       => 1,
            'created_at'     => '2026-01-09 00:00:00',
            'updated_at'     => '2026-01-09 00:00:00',
        ], $ghiDe));
    }

    /** @test */
    public function lay_loi_xml3176_kem_ten_loi_tu_danh_muc()
    {
        $this->themDanhMucLoi();
        $this->themLoiXml();

        $dong = $this->dichVu()->cua('01013250800123')['data']['xml3176'];

        $this->assertCount(1, $dong);
        $this->assertEquals('Sai ma the BHYT', $dong[0]['error_name']);
        $this->assertTrue($dong[0]['critical_error']);
        $this->assertEquals('20260805', $dong[0]['ngay_yl']);
    }

    /**
     * xml3176_error_catalogs unique theo CAP (xml, error_code). Join thieu cot xml se
     * nhan mot dong loi thanh nhieu dong khi ma loi do ton tai o nhieu loai XML.
     *
     * @test
     */
    public function cung_ma_loi_o_hai_loai_xml_thi_khong_nhan_dong()
    {
        $this->themDanhMucLoi(['xml' => 'XML1', 'error_name' => 'Ten cua XML1']);
        $this->themDanhMucLoi(['xml' => 'XML2', 'error_name' => 'Ten cua XML2']);
        $this->themLoiXml(['xml' => 'XML1']);

        $dong = $this->dichVu()->cua('01013250800123')['data']['xml3176'];

        $this->assertCount(1, $dong);
        $this->assertEquals('Ten cua XML1', $dong[0]['error_name']);
    }

    /** @test */
    public function loi_khong_co_trong_danh_muc_van_duoc_tra_ve()
    {
        $this->themLoiXml(['error_code' => 'L999']);

        $dong = $this->dichVu()->cua('01013250800123')['data']['xml3176'];

        $this->assertCount(1, $dong);
        $this->assertNull($dong[0]['error_name']);
    }

    /** @test */
    public function loi_xml_sap_xep_theo_xml_roi_toi_stt()
    {
        $this->themLoiXml(['xml' => 'XML2', 'stt' => 1]);
        $this->themLoiXml(['xml' => 'XML1', 'stt' => 2]);
        $this->themLoiXml(['xml' => 'XML1', 'stt' => 1]);

        $dong = $this->dichVu()->cua('01013250800123')['data']['xml3176'];

        $this->assertEquals(['XML1', 1], [$dong[0]['xml'], $dong[0]['stt']]);
        $this->assertEquals(['XML1', 2], [$dong[1]['xml'], $dong[1]['stt']]);
        $this->assertEquals(['XML2', 1], [$dong[2]['xml'], $dong[2]['stt']]);
    }
```

- [ ] **Step 2: Chạy test để xác nhận đỏ**

Run: `vendor/bin/phpunit tests/Unit/OrderCheck/TreatmentIssueServiceTest.php`
Expected: FAIL — `lay_loi_xml3176_kem_ten_loi_tu_danh_muc` nhận mảng rỗng.

- [ ] **Step 3: Bổ sung service**

Thêm `use` ở đầu tệp:

```php
use Illuminate\Support\Facades\DB;
```

Trong `cua()`, thay `'xml3176' => [],` bằng:

```php
                'xml3176' => $this->rong($treatmentCode) ? [] : $this->loiXml3176($treatmentCode),
```

Thêm hàm vào class:

```php
    /**
     * JOIN THEO CAP (xml, error_code) - danh muc unique theo cap nay. Noi chi bang
     * error_code se nhan mot dong loi thanh nhieu dong khi ma loi ton tai o nhieu loai
     * XML. Quan he hasOne trong model Xml3176ErrorResult noi thieu cot xml nen khong
     * dung lai duoc o day.
     *
     * critical_error lay tu ban ghi ket qua (gia tri tai thoi diem kiem), khong lay tu
     * danh muc - danh muc co the da doi sau do.
     */
    protected function loiXml3176($maLk)
    {
        $dong = DB::table('xml3176_error_results as r')
            ->leftJoin('xml3176_error_catalogs as c', function ($j) {
                $j->on('c.xml', '=', 'r.xml')
                  ->on('c.error_code', '=', 'r.error_code');
            })
            ->where('r.ma_lk', $maLk)
            ->orderBy('r.xml')
            ->orderBy('r.stt')
            ->limit(self::TRAN_MOI_NHOM)
            ->get([
                'r.xml', 'r.stt', 'r.error_code', 'c.error_name', 'r.description',
                'r.critical_error', 'r.ngay_yl', 'r.ngay_kq',
            ]);

        $ra = [];

        foreach ($dong as $d) {
            $ra[] = [
                'xml' => $d->xml,
                'stt' => (int) $d->stt,
                'error_code' => $d->error_code,
                'error_name' => $d->error_name,
                'description' => $d->description,
                'critical_error' => (bool) $d->critical_error,
                'ngay_yl' => $d->ngay_yl,
                'ngay_kq' => $d->ngay_kq,
            ];
        }

        return $ra;
    }
```

- [ ] **Step 4: Chạy test để xác nhận xanh**

Run: `vendor/bin/phpunit tests/Unit/OrderCheck/TreatmentIssueServiceTest.php`
Expected: PASS — 14 tests.

- [ ] **Step 5: Commit**

```bash
git add app/Services/OrderCheck/TreatmentIssueService.php tests/Unit/OrderCheck/TreatmentIssueServiceTest.php
git commit -m "feat(order-check): them nhom loi XML3176, join danh muc theo cap xml+error_code"
```

---

### Task 4: `summary`, trần 500 dòng, và suy ra `ma_lk` từ `treatment_id`

**Files:**
- Modify: `app/Services/OrderCheck/TreatmentIssueService.php`
- Modify: `tests/Unit/OrderCheck/TreatmentIssueServiceTest.php`

**Interfaces:**
- Produces: `summary` gồm `total`, `order_check`, `hein_card`, `xml3176`, `critical`, `has_error`, `truncated` — Task 5 trả thẳng khối này ra JSON.

- [ ] **Step 1: Viết test thất bại**

Thêm vào `tests/Unit/OrderCheck/TreatmentIssueServiceTest.php`:

```php
    /** @test */
    public function tom_tat_dem_du_ba_nhom()
    {
        $this->themViPham(['severity' => 'critical']);
        $this->themViPham(['severity' => 'warning']);
        $this->themTraThe(['ma_tracuu' => '005']);
        $this->themLoiXml(['critical_error' => 1]);
        $this->themLoiXml(['stt' => 2, 'critical_error' => 0]);

        $tomTat = $this->dichVu()->cua('01013250800123')['summary'];

        $this->assertEquals(5, $tomTat['total']);
        $this->assertEquals(2, $tomTat['order_check']);
        $this->assertEquals(1, $tomTat['hein_card']);
        $this->assertEquals(2, $tomTat['xml3176']);
        $this->assertTrue($tomTat['has_error']);
        $this->assertFalse($tomTat['truncated']);
    }

    /**
     * critical gop hai nguon: severity=critical cua y lenh va critical_error cua XML3176.
     * Nhom tra the khong co khai niem muc do nen khong tinh vao critical, nhung van tinh
     * vao total.
     *
     * @test
     */
    public function critical_gop_y_lenh_va_xml3176()
    {
        $this->themViPham(['severity' => 'critical']);
        $this->themViPham(['severity' => 'warning']);
        $this->themLoiXml(['critical_error' => 1]);
        $this->themTraThe(['ma_tracuu' => '005']);

        $tomTat = $this->dichVu()->cua('01013250800123')['summary'];

        $this->assertEquals(2, $tomTat['critical']);
    }

    /** @test */
    public function dot_sach_thi_has_error_bang_false()
    {
        $tomTat = $this->dichVu()->cua('KHONG-CO-DOT-NAY')['summary'];

        $this->assertEquals(0, $tomTat['total']);
        $this->assertFalse($tomTat['has_error']);
    }

    /**
     * Tran cung de mot dot dieu tri dai khong lam vo gioi han 128MB cua may chu.
     *
     * @test
     */
    public function cham_tran_thi_cat_bot_va_bat_co_truncated()
    {
        for ($i = 0; $i < TreatmentIssueService::TRAN_MOI_NHOM + 5; $i++) {
            $this->themLoiXml(['stt' => $i + 1]);
        }

        $ketQua = $this->dichVu()->cua('01013250800123');

        $this->assertCount(TreatmentIssueService::TRAN_MOI_NHOM, $ketQua['data']['xml3176']);
        $this->assertTrue($ketQua['summary']['truncated']);
    }

    /**
     * Chi truyen treatment_id thi van phai ra duoc hai nhom kia - chung khoa theo ma_lk.
     *
     * @test
     */
    public function chi_truyen_treatment_id_van_suy_ra_duoc_ma_lk()
    {
        $this->themViPham(['treatment_id' => 9001, 'treatment_code' => '01013250800123']);
        $this->themLoiXml();

        $ketQua = $this->dichVu()->cua(null, 9001);

        $this->assertEquals('01013250800123', $ketQua['data']['treatment_code']);
        $this->assertCount(1, $ketQua['data']['xml3176']);
    }

    /** @test */
    public function treatment_id_khong_co_vi_pham_thi_hai_nhom_kia_rong()
    {
        $this->themLoiXml();

        $ketQua = $this->dichVu()->cua(null, 7777);

        $this->assertNull($ketQua['data']['treatment_code']);
        $this->assertSame([], $ketQua['data']['xml3176']);
    }
```

- [ ] **Step 2: Chạy test để xác nhận đỏ**

Run: `vendor/bin/phpunit tests/Unit/OrderCheck/TreatmentIssueServiceTest.php`
Expected: FAIL — `summary` đang là mảng rỗng nên `$tomTat['total']` báo undefined index.

- [ ] **Step 3: Sửa `cua()` và thêm hai hàm**

Thay toàn bộ thân hàm `cua()` bằng:

```php
    public function cua($treatmentCode = null, $treatmentId = null, array $tuyChon = [])
    {
        $status = isset($tuyChon['status']) ? $tuyChon['status'] : null;

        $viPham = $this->viPhamYLenh($treatmentCode, $treatmentId, $status);

        // Hai nhom kia khoa theo ma_lk. Ben goi chi dua treatment_id thi suy nguoc tu
        // dong vi pham; khong suy ra duoc thi de rong chu KHONG truy HIS - mot lan goi
        // API khong dang doi mot vong sang Oracle.
        $maLk = $this->rong($treatmentCode) ? $this->suyRaMaLk($treatmentId) : $treatmentCode;

        $traThe = $this->rong($maLk) ? [] : $this->loiTraThe($maLk);
        $xml = $this->rong($maLk) ? [] : $this->loiXml3176($maLk);

        return [
            'data' => [
                'treatment_code' => $this->rong($maLk) ? null : $maLk,
                'order_check' => $viPham,
                'hein_card' => $traThe,
                'xml3176' => $xml,
            ],
            'summary' => $this->tomTat($viPham, $traThe, $xml),
        ];
    }
```

Thêm hai hàm vào class:

```php
    protected function suyRaMaLk($treatmentId)
    {
        if ($this->rong($treatmentId)) {
            return null;
        }

        $ma = OrderCheckViolation::where('treatment_id', $treatmentId)
            ->whereNotNull('treatment_code')
            ->value('treatment_code');

        return $this->rong($ma) ? null : $ma;
    }

    protected function tomTat(array $viPham, array $traThe, array $xml)
    {
        $critical = 0;

        foreach ($viPham as $d) {
            if ($d['severity'] === 'critical') {
                $critical++;
            }
        }

        foreach ($xml as $d) {
            if ($d['critical_error']) {
                $critical++;
            }
        }

        $tong = count($viPham) + count($traThe) + count($xml);

        return [
            'total' => $tong,
            'order_check' => count($viPham),
            'hein_card' => count($traThe),
            'xml3176' => count($xml),
            'critical' => $critical,
            'has_error' => $tong > 0,
            // Nhom tra the unique theo ma_lk nen khong bao gio cham tran.
            'truncated' => count($viPham) >= self::TRAN_MOI_NHOM
                || count($xml) >= self::TRAN_MOI_NHOM,
        ];
    }
```

- [ ] **Step 4: Chạy test để xác nhận xanh**

Run: `vendor/bin/phpunit tests/Unit/OrderCheck/TreatmentIssueServiceTest.php`
Expected: PASS — 20 tests.

- [ ] **Step 5: Commit**

```bash
git add app/Services/OrderCheck/TreatmentIssueService.php tests/Unit/OrderCheck/TreatmentIssueServiceTest.php
git commit -m "feat(order-check): tom tat ba nhom, tran 500 dong, suy ma_lk tu treatment_id"
```

---

### Task 5: Controller + khuôn response + feature test qua HTTP

**Files:**
- Modify: `app/Http/Controllers/KHTH/OrderCheckController.php:196-218` (hàm `apiViolations`)
- Create: `tests/Feature/OrderCheckApiTest.php`

**Interfaces:**
- Consumes: `TreatmentIssueService::cua()` (Task 1–4).
- Produces: response JSON `{success, data, summary, meta}` cho HTTP 200; `{success:false, error:{code,message,details}, meta}` cho 422 và 500.

- [ ] **Step 1: Viết feature test thất bại**

Tạo `tests/Feature/OrderCheckApiTest.php`:

```php
<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\Support\DungBangLoiDotDieuTriSqlite;
use Tests\TestCase;

class OrderCheckApiTest extends TestCase
{
    use DungBangLoiDotDieuTriSqlite;

    const TOKEN = 'token-thu-nghiem';

    protected function setUp()
    {
        parent::setUp();

        $this->chuanBiBangLoi();

        config(['organization.api.access_token' => self::TOKEN]);
    }

    protected function goi(array $thamSo, $token = self::TOKEN)
    {
        return $this->getJson(
            '/api/order-check/violations?' . http_build_query($thamSo),
            ['Authorization' => 'Bearer ' . $token]
        );
    }

    /** @test */
    public function thieu_token_thi_tra_401()
    {
        $this->getJson('/api/order-check/violations?treatment_code=X')
            ->assertStatus(401)
            ->assertJson(['success' => false]);
    }

    /** @test */
    public function thieu_ca_hai_tham_so_thi_tra_422_dung_khuon()
    {
        $this->goi([])
            ->assertStatus(422)
            ->assertJson([
                'success' => false,
                'error' => ['code' => 'VALIDATION_ERROR'],
            ])
            ->assertJsonStructure([
                'success',
                'error' => ['code', 'message', 'details'],
                'meta' => ['timestamp', 'request_id'],
            ]);
    }

    /** @test */
    public function dot_co_du_ba_nhom_loi_tra_ve_day_du()
    {
        $this->themViPham(['severity' => 'critical']);

        DB::table('check_hein_cards')->insert([
            'ma_lk' => '01013250800123', 'ma_tracuu' => '005', 'ma_kiemtra' => '00',
            'ma_ketqua' => 'The het han', 'ghi_chu' => null, 'ma_the' => 'DN4010112345678',
            'created_at' => '2026-08-05 14:00:00', 'updated_at' => '2026-08-05 14:03:00',
        ]);

        DB::table('xml3176_error_results')->insert([
            'xml' => 'XML1', 'ma_lk' => '01013250800123', 'stt' => 1,
            'ngay_yl' => '20260805', 'ngay_kq' => '20260805', 'error_code' => 'L001',
            'description' => 'Chi tiet loi', 'critical_error' => 1,
            'created_at' => '2026-08-05 15:00:00', 'updated_at' => '2026-08-05 15:00:00',
        ]);

        $phanHoi = $this->goi(['treatment_code' => '01013250800123']);

        $phanHoi->assertStatus(200)
            ->assertJson([
                'success' => true,
                'summary' => [
                    'total' => 3, 'order_check' => 1, 'hein_card' => 1, 'xml3176' => 1,
                    'critical' => 2, 'has_error' => true, 'truncated' => false,
                ],
            ])
            ->assertJsonStructure([
                'success',
                'data' => ['treatment_code', 'order_check', 'hein_card', 'xml3176'],
                'summary',
                'meta' => ['timestamp', 'request_id'],
            ]);

        // Laravel 5.5: TestResponse::json() KHONG nhan tham so khoa (chi co tu 5.6),
        // nen phai lay ca mang roi tu di xuong.
        $than = $phanHoi->json();

        $this->assertEquals('****5678', $than['data']['hein_card'][0]['ma_the_masked']);
    }

    /**
     * Khong dung 404: HIS goi cho MOI dot dieu tri, "khong co loi" la ket qua hop le
     * chu khong phai tai nguyen khong ton tai.
     *
     * @test
     */
    public function dot_sach_tra_200_voi_ba_mang_rong()
    {
        $this->goi(['treatment_code' => 'KHONG-CO-DOT-NAY'])
            ->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => ['order_check' => [], 'hein_card' => [], 'xml3176' => []],
                'summary' => ['total' => 0, 'has_error' => false],
            ]);
    }

    /** @test */
    public function loc_theo_treatment_id_van_hoat_dong()
    {
        $this->themViPham(['treatment_id' => 9001]);

        $this->goi(['treatment_id' => 9001])
            ->assertStatus(200)
            ->assertJson(['summary' => ['order_check' => 1]]);
    }
}
```

- [ ] **Step 2: Chạy test để xác nhận đỏ**

Run: `vendor/bin/phpunit tests/Feature/OrderCheckApiTest.php`
Expected: FAIL — `thieu_ca_hai_tham_so_thi_tra_422_dung_khuon` nhận khuôn lỗi mặc định của Laravel (`{"treatment_code": [...]}`) chứ không có khoá `error.code`.

- [ ] **Step 3: Viết lại `apiViolations` và thêm hai hàm phụ**

Trong `app/Http/Controllers/KHTH/OrderCheckController.php`, thêm `use` ở đầu tệp:

```php
use App\Services\OrderCheck\TreatmentIssueService;
```

Thay toàn bộ hàm `apiViolations` (dòng 195–218) bằng:

```php
    /**
     * API JSON chi doc: tra cuu TOAN BO loi cua mot dot dieu tri - sai sot y lenh, loi
     * tra the BHYT, loi XML3176 - trong mot lan goi.
     */
    public function apiViolations(Request $request, TreatmentIssueService $issueService)
    {
        $treatmentCode = trim((string) $request->input('treatment_code'));
        $treatmentId = trim((string) $request->input('treatment_id'));

        // Kiem tham so thu cong thay vi $request->validate(): validate() tra khuon loi
        // mac dinh cua Laravel, khac han khuon {success,error,meta} cua ApiAuthMiddleware,
        // buoc ben goi phai xu ly hai dinh dang.
        if ($treatmentCode === '' && $treatmentId === '') {
            return $this->loiApi(
                'VALIDATION_ERROR',
                'Thiếu tham số bắt buộc',
                'Cần truyền treatment_code hoặc treatment_id',
                422
            );
        }

        try {
            $ketQua = $issueService->cua(
                $treatmentCode !== '' ? $treatmentCode : null,
                $treatmentId !== '' ? $treatmentId : null,
                ['status' => $request->input('status')]
            );
        } catch (\Exception $e) {
            \Log::error('Loi API tra cuu loi dot dieu tri', [
                'treatment_code' => $treatmentCode,
                'treatment_id' => $treatmentId,
                'loi' => $e->getMessage(),
            ]);

            return $this->loiApi(
                'INTERNAL_ERROR',
                'Lỗi hệ thống',
                'Vui lòng thử lại sau',
                500
            );
        }

        return response()->json([
            'success' => true,
            'data' => $ketQua['data'],
            'summary' => $ketQua['summary'],
            'meta' => $this->metaApi(),
        ]);
    }

    /** Khuon loi thong nhat voi ApiAuthMiddleware. Khong lo thong diep ngoai le ra ngoai. */
    protected function loiApi($code, $message, $details, $status)
    {
        return response()->json([
            'success' => false,
            'error' => [
                'code' => $code,
                'message' => $message,
                'details' => $details,
            ],
            'meta' => $this->metaApi(),
        ], $status);
    }

    protected function metaApi()
    {
        return [
            'timestamp' => Carbon::now()->format('YmdHis'),
            'request_id' => uniqid('req_'),
        ];
    }
```

- [ ] **Step 4: Chạy feature test để xác nhận xanh**

Run: `vendor/bin/phpunit tests/Feature/OrderCheckApiTest.php`
Expected: PASS — 5 tests.

- [ ] **Step 5: Chạy cả hai bộ test của tính năng**

Run: `vendor/bin/phpunit tests/Feature/OrderCheckApiTest.php tests/Unit/OrderCheck/TreatmentIssueServiceTest.php`
Expected: PASS — 25 tests.

- [ ] **Step 6: Chạy toàn bộ bộ test và so với mốc đã ghi ở đầu**

Run: `vendor/bin/phpunit`
Expected: số ca đỏ **không tăng** so với mốc chạy trước khi bắt đầu. Nếu tăng, sửa trước khi commit.

- [ ] **Step 7: Commit**

```bash
git add app/Http/Controllers/KHTH/OrderCheckController.php tests/Feature/OrderCheckApiTest.php
git commit -m "feat(order-check): API tra ve ba nhom loi, khuon response thong nhat"
```

---

### Task 6: Tài liệu API cho bên gọi

**Files:**
- Create: `docs/order-check/API-TRA-CUU-LOI.md`

**Interfaces:**
- Consumes: khuôn response chốt ở Task 5.

- [ ] **Step 1: Viết tài liệu**

Tạo `docs/order-check/API-TRA-CUU-LOI.md` gồm đúng các mục sau:

1. **Endpoint và xác thực** — `GET /api/order-check/violations`, header `Authorization: Bearer {token}`, giới hạn 60 request/phút.
2. **Tham số** — bảng `treatment_code` / `treatment_id` / `status`, ghi rõ **nên truyền `treatment_code`**: chỉ truyền `treatment_id` thì hai nhóm tra thẻ và XML3176 chỉ ra được khi đợt đó đã có ít nhất một vi phạm y lệnh.
3. **Ví dụ response 200** — sao chép nguyên khối JSON ở mục A2 của spec `docs/superpowers/specs/2026-08-06-order-check-api-gop-loi-design.md`.
4. **Giải thích từng trường** của ba nhóm và của `summary` (nêu rõ: `critical` gộp y lệnh + XML3176, nhóm tra thẻ không tính vào `critical` nhưng có tính vào `total`; `truncated` bật khi một nhóm chạm trần 500 dòng).
5. **Quy tắc lọc** — mặc định bỏ `false_positive`; nhóm tra thẻ chỉ trả dòng bất thường; `ma_the` chỉ trả 4 ký tự cuối.
6. **Bảng mã lỗi HTTP** — 200 (kể cả khi không có lỗi nào), 401 `UNAUTHORIZED`, 422 `VALIDATION_ERROR`, 429 quá giới hạn, 500 `INTERNAL_ERROR`.
7. **Ghi chú thay đổi** — response trước đây là mảng thuần, nay là đối tượng bọc `{success, data, summary, meta}`; bên gọi cũ phải sửa.

- [ ] **Step 2: Kiểm tra tài liệu khớp code**

Đọc lại `apiViolations` và `TreatmentIssueService`, đối chiếu từng tên trường trong tài liệu với tên trường thật trong mảng trả về. Sai một tên là bên gọi hỏng.

- [ ] **Step 3: Commit**

```bash
git add docs/order-check/API-TRA-CUU-LOI.md
git commit -m "docs(order-check): tai lieu API tra cuu loi dot dieu tri"
```

---

## Sau khi hoàn thành

Phần B của spec (bảo mật, hiệu năng) chưa thực thi — còn nguyên trong
`docs/superpowers/specs/2026-08-06-order-check-api-gop-loi-design.md` mục B3 với thứ tự
triển khai đề xuất. Việc đầu tiên của giai đoạn sau là nhóm "ưu tiên cao, chi phí thấp":
đổi token mạnh, `hash_equals`, hạ mức log, và index `treatment_code`.
