# Màn Kết quả tra cứu thẻ: thông tin đã gửi và nút Tra lại — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Dòng lỗi trên `/bhyt/check-hein-card/index` luôn hiện số thẻ / họ tên / ngày sinh (lấy giá trị đã gửi lên cổng khi cổng không trả), có nút **Tra lại** trên từng dòng lỗi, dọn một lần thẻ tạm sơ sinh cũ, và sửa lỗi không lưu `gt_the_tumoi`/`gt_the_denmoi`.

**Architecture:** Lưu 4 cột `*_gui` vào `check_hein_cards` ngay khi job tra thẻ ghi kết quả, và bù dữ liệu cũ từ HIS bằng lệnh artisan. Model có hàm `hienThi()` chọn giá trị cổng hay giá trị đã gửi. Logic "tra lại thẻ" tách thành service `App\Services\BHYT\TraLaiThe`, dùng chung cho màn Tra cứu lỗi hồ sơ và màn Kết quả tra cứu thẻ.

**Tech Stack:** Laravel 5.5 · PHP 7.4 · yajra Datatables · Maatwebsite Excel 3 · PHPUnit 6 · SQLite in-memory trong test · Oracle (kết nối `HISPro`) cho HIS.

**Spec:** `docs/superpowers/specs/2026-09-24-man-ket-qua-tra-the-thong-tin-gui-va-tra-lai-design.md`

## Global Constraints

- `.env` dev trỏ **CSDL THẬT** (192.168.200.68/qlbv). Mọi lệnh test chạy với tiền tố `DB_HOST=127.0.0.1`. **CẤM** `RefreshDatabase`. Test nào cần bảng phải ghi đè chính kết nối `mysql` / `HISPro` sang SQLite `:memory:` (traits `Tests\Support\DungBangLoiDotDieuTriSqlite`, `Tests\Support\DungBangHoSoHisSqlite`).
- Hàng đợi tra thẻ tên đúng `JobKtTheBHYT`. Mọi `dispatch` phải `->onQueue('JobKtTheBHYT')`.
- Không type-hint service trên `Job::handle()`: container sẽ tiêm service rỗng (bẫy đã cắn 3 lần).
- Quy tắc "lỗi" hiển thị trên màn = `check_hein_card::chiLoi()`. Quy tắc "cần tra lại/xoá" của job = `config('qd130xml.hein_card_invalid')`. Không trộn hai quy tắc.
- Lệnh ghi hàng loạt mặc định **chỉ đếm**, phải có `--ghi` mới ghi/xoá.
- Cập nhật hàng loạt dùng `DB::table()->update()`, không chạm `updated_at` (cột "Thời gian tra cứu" và là trường lọc ngày).
- Dữ liệu từ cổng/HIS đưa vào DOM phải qua `$('<div>').text(x).html()`.
- Commit message ASCII không dấu theo kiểu repo, kết thúc bằng dòng `Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>`. Chỉ push `origin`, không push `public`.
- 5 lỗi test có sẵn không liên quan, không cần sửa: `Xml3176Xml3CheckerRuleTest` ×3, `Xml3176ErrorServiceGomTest` ×1, `KiemTraTheCoSoTest::bang_ket_qua_co_cot_ma_cskcb`. Thêm vào đó là các test trong `ManKetQuaTraCuuTheTest` / `XuatExcelKetQuaTraCuuTheTest` đang đọc bảng thật (bị chặn `appuser` khi `DB_HOST=127.0.0.1`).

## File Structure

| Tệp | Vai trò |
|---|---|
| `database/migrations/2026_09_24_100000_them_thong_tin_gui_vao_check_hein_cards.php` (tạo) | 4 cột `ma_the_gui`, `ho_ten_gui`, `ngay_sinh_gui`, `ma_dkbd_gui` |
| `app/Models/CheckBHYT/check_hein_card.php` (sửa) | `$fillable` đúng tên cột, hằng `TRUONG_HIEN`, hàm `hienThi()` |
| `tests/Support/DungBangLoiDotDieuTriSqlite.php` (sửa) | Bảng `check_hein_cards` SQLite đủ cột như thật |
| `app/Jobs/jobKtTheBHYT.php` (sửa) | Ghi 4 cột `_gui` khi lưu kết quả |
| `app/Services/BHYT/TraLaiThe.php` (tạo) | Kiểm hồ sơ HIS và đẩy job tra lại |
| `app/Http/Controllers/KHTH/TraCuuLoiHoSoController.php` (sửa) | `traLaiThe()` gọi service |
| `app/Http/Controllers/BHYT/CheckHeinCardController.php` (sửa) | `fetch()` thêm cột `hien_*`/`nguon_*`, tìm theo `_gui`, action `traLai()` |
| `routes/web.php` (sửa) | `POST bhyt/check-hein-card/tra-lai` |
| `resources/views/bhyt/check-hein-card/index.blade.php` (sửa) | Cột hiển thị, nút Tra lại, modal thêm 4 dòng |
| `app/Exports/KetQuaTraCuuTheExport.php` (sửa) | 4 cột "đã gửi" cuối tệp |
| `app/Console/Commands/BuThongTinGuiTheBhyt.php` (tạo) | `the-bhyt:bu-thong-tin-gui` |
| `app/Console/Commands/DonTheTamSoSinh.php` (tạo) | `the-bhyt:don-the-tam` |

Lệnh artisan tự nạp từ `app/Console/Commands` (`Kernel::commands()` gọi `$this->load(__DIR__.'/Commands')`), không cần đăng ký.

---

### Task 1: Dữ liệu: migration, model, bảng test

**Files:**
- Create: `database/migrations/2026_09_24_100000_them_thong_tin_gui_vao_check_hein_cards.php`
- Modify: `app/Models/CheckBHYT/check_hein_card.php`
- Modify: `tests/Support/DungBangLoiDotDieuTriSqlite.php` (khối `Schema::create('check_hein_cards', ...)`)
- Test: `tests/Unit/CheckHeinCardModelTest.php`

**Interfaces:**
- Produces:
  - `check_hein_card::TRUONG_HIEN = ['ma_the', 'ho_ten', 'ngay_sinh']`
  - `check_hein_card::hienThi(string $truong): array` trả về `[mixed $giaTri, string $nguon]`, `$nguon ∈ {'cong','gui',''}`
  - `$fillable` chứa `gt_the_tumoi`, `gt_the_denmoi`, `ma_the_gui`, `ho_ten_gui`, `ngay_sinh_gui`, `ma_dkbd_gui`
  - Bảng test SQLite `check_hein_cards` có đủ mọi cột thật + 4 cột `_gui`

- [ ] **Step 1: Viết test đỏ**

`tests/Unit/CheckHeinCardModelTest.php`:

```php
<?php

namespace Tests\Unit;

use App\Models\CheckBHYT\check_hein_card;
use Tests\TestCase;

/**
 * $fillable tung ghi 'gt_the_tu_moi'/'gt_the_den_moi' trong khi cot that la 'gt_the_tumoi'/
 * 'gt_the_denmoi': updateOrCreate lang le bo hai truong, 0/46.396 dong co du lieu.
 */
class CheckHeinCardModelTest extends TestCase
{
    /** @test */
    public function fillable_nhan_dung_ten_cot_the_moi_va_cot_da_gui()
    {
        $m = (new check_hein_card)->fill([
            'gt_the_tumoi' => '01/10/2026', 'gt_the_denmoi' => '30/09/2027',
            'ma_the_gui' => 'DN4010112345678', 'ho_ten_gui' => 'Nguyễn Văn A',
            'ngay_sinh_gui' => '20/02/1979', 'ma_dkbd_gui' => '01005',
        ]);

        $this->assertSame('01/10/2026', $m->gt_the_tumoi);
        $this->assertSame('30/09/2027', $m->gt_the_denmoi);
        $this->assertSame('DN4010112345678', $m->ma_the_gui);
        $this->assertSame('01005', $m->ma_dkbd_gui);
    }

    /** @test */
    public function hien_thi_uu_tien_gia_tri_cong()
    {
        $m = new check_hein_card(['ma_the' => 'DN4010112345678', 'ma_the_gui' => 'KHAC']);

        $this->assertSame(['DN4010112345678', 'cong'], $m->hienThi('ma_the'));
    }

    /** @test */
    public function hien_thi_lay_gia_tri_da_gui_khi_cong_rong()
    {
        $m = new check_hein_card(['ho_ten' => '  ', 'ho_ten_gui' => 'Nguyễn Văn A']);

        $this->assertSame(['Nguyễn Văn A', 'gui'], $m->hienThi('ho_ten'));
    }

    /** @test */
    public function hien_thi_rong_khi_ca_hai_rong()
    {
        $this->assertSame(['', ''], (new check_hein_card)->hienThi('ngay_sinh'));
    }

    /** @test */
    public function truong_hien_la_ba_cot_quan_sat()
    {
        $this->assertSame(['ma_the', 'ho_ten', 'ngay_sinh'], check_hein_card::TRUONG_HIEN);
    }
}
```

- [ ] **Step 2: Chạy, xác nhận đỏ**

Run: `DB_HOST=127.0.0.1 php vendor/bin/phpunit tests/Unit/CheckHeinCardModelTest.php`
Expected: FAIL. `gt_the_tumoi` là null (không nằm trong fillable), `hienThi` chưa có (`BadMethodCallException`), hằng `TRUONG_HIEN` chưa có.

- [ ] **Step 3: Sửa model**

Trong `app/Models/CheckBHYT/check_hein_card.php`, thay khối `$fillable` bằng:

```php
    // Ten truong PHAI trung ten cot that: tung ghi 'gt_the_tu_moi'/'gt_the_den_moi' nen
    // updateOrCreate lang le bo hai truong nay (0/46.396 dong co du lieu).
    // Bon cot *_gui la gia tri MINH DA GUI len cong - cong bao loi thuong khong tra so the,
    // ho ten, ngay sinh, khong luu thi dong loi chi con ma ho so.
    protected $fillable = [
        'ma_lk', 'ma_cskcb', 'ma_tracuu', 'ma_kiemtra', 'ma_ketqua', 'ghi_chu', 'ma_the', 'ho_ten', 'ngay_sinh',
        'dia_chi', 'ma_the_cu', 'ma_the_moi', 'ma_dkbd', 'cq_bhxh', 'gioi_tinh', 'gt_the_tu', 'gt_the_den',
        'ma_kv', 'ngay_du5nam', 'maso_bhxh', 'gt_the_tumoi', 'gt_the_denmoi', 'ma_dkbd_moi', 'ten_dkbd_moi',
        'ma_the_gui', 'ho_ten_gui', 'ngay_sinh_gui', 'ma_dkbd_gui',
    ];

    /** Ba truong can de quan sat mot dong - cong bao loi thuong bo trong ca ba. */
    const TRUONG_HIEN = ['ma_the', 'ho_ten', 'ngay_sinh'];
```

Thêm hàm ngay sau `scopeCuaCoSo()`:

```php
    /**
     * Gia tri de HIEN cho mot truong quan sat: gia tri cong neu co, khong thi gia tri da gui.
     *
     * @param string $truong mot trong TRUONG_HIEN
     * @return array [gia tri, nguon] voi nguon 'cong' | 'gui' | ''
     */
    public function hienThi($truong)
    {
        if (trim((string) $this->{$truong}) !== '') {
            return [$this->{$truong}, 'cong'];
        }

        $gui = $this->{$truong . '_gui'};

        return trim((string) $gui) !== '' ? [$gui, 'gui'] : ['', ''];
    }
```

- [ ] **Step 4: Tạo migration**

`database/migrations/2026_09_24_100000_them_thong_tin_gui_vao_check_hein_cards.php`:

```php
<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Gia tri DA GUI len cong BHXH khi tra the: so the, ho ten, ngay sinh, noi DKBD.
 *
 * Cong bao loi thuong khong tra ba truong dau - 1.213/1.525 dong loi chi con ma ho so. Khong
 * index: man tim bang LIKE %x% nen index khong dung toi, bang ~46 nghin dong.
 */
class ThemThongTinGuiVaoCheckHeinCards extends Migration
{
    const COT = ['ma_the_gui', 'ho_ten_gui', 'ngay_sinh_gui', 'ma_dkbd_gui'];

    public function up()
    {
        if (Schema::hasColumn('check_hein_cards', 'ma_the_gui')) {
            return;
        }

        Schema::table('check_hein_cards', function (Blueprint $t) {
            $t->string('ma_the_gui', 50)->nullable();
            $t->string('ho_ten_gui', 255)->nullable();
            $t->string('ngay_sinh_gui', 20)->nullable();
            $t->string('ma_dkbd_gui', 20)->nullable();
        });
    }

    public function down()
    {
        if (!Schema::hasColumn('check_hein_cards', 'ma_the_gui')) {
            return;
        }

        Schema::table('check_hein_cards', function (Blueprint $t) {
            $t->dropColumn(self::COT);
        });
    }
}
```

- [ ] **Step 5: Mở rộng bảng test SQLite cho giống bảng thật**

Trong `tests/Support/DungBangLoiDotDieuTriSqlite.php`, thay khối `Schema::create('check_hein_cards', ...)` bằng:

```php
        // DU cot nhu bang that: job ghi ca mang ket qua cong, thieu mot cot la SQL loi.
        Schema::create('check_hein_cards', function ($t) {
            $t->increments('id');
            $t->string('ma_lk', 100);
            $t->string('ma_cskcb', 20)->nullable();
            $t->string('ma_tracuu', 10);
            $t->string('ma_kiemtra', 10);
            $t->string('ma_ketqua', 255)->nullable();
            $t->text('ghi_chu')->nullable();
            foreach (['ma_the', 'ho_ten', 'ngay_sinh', 'dia_chi', 'ma_the_cu', 'ma_the_moi', 'ma_dkbd',
                      'cq_bhxh', 'gioi_tinh', 'gt_the_tu', 'gt_the_den', 'ma_kv', 'ngay_du5nam', 'maso_bhxh',
                      'gt_the_tumoi', 'gt_the_denmoi', 'ma_dkbd_moi', 'ten_dkbd_moi',
                      'ma_the_gui', 'ho_ten_gui', 'ngay_sinh_gui', 'ma_dkbd_gui'] as $cot) {
                $t->string($cot, 255)->nullable();
            }
            $t->timestamps();
        });
```

- [ ] **Step 6: Chạy test model và các test dùng trait này**

Run:
```bash
DB_HOST=127.0.0.1 php vendor/bin/phpunit tests/Unit/CheckHeinCardModelTest.php
DB_HOST=127.0.0.1 php vendor/bin/phpunit tests/Unit/JobKtTheBHYTTheTamTest.php
DB_HOST=127.0.0.1 php vendor/bin/phpunit tests/Feature/TraCuuLoiHoSoTest.php
```
Expected: `OK (5 tests...)`, `OK (4 tests...)`, `OK (20 tests...)`.

- [ ] **Step 7: Commit**

```bash
git add database/migrations/2026_09_24_100000_them_thong_tin_gui_vao_check_hein_cards.php app/Models/CheckBHYT/check_hein_card.php tests/Support/DungBangLoiDotDieuTriSqlite.php tests/Unit/CheckHeinCardModelTest.php
git commit -m "feat(the-bhyt): cot gia tri da gui, hienThi() va sua fillable gt_the_tumoi/denmoi

Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>"
```

---

### Task 2: Job ghi giá trị đã gửi

**Files:**
- Modify: `app/Jobs/jobKtTheBHYT.php` (hàm `addCheckHeinCard`)
- Test: `tests/Unit/JobKtTheBHYTGhiThongTinGuiTest.php`

**Interfaces:**
- Consumes: 4 cột `_gui` và `$fillable` từ Task 1; bảng test SQLite đủ cột từ Task 1.
- Produces: mọi dòng `check_hein_cards` job ghi đều có `ma_the_gui`, `ho_ten_gui`, `ngay_sinh_gui`, `ma_dkbd_gui` lấy từ `params` (`maThe`, `hoTen`, `ngaySinh`, `maDkbd`).

- [ ] **Step 1: Viết test đỏ**

`tests/Unit/JobKtTheBHYTGhiThongTinGuiTest.php`:

```php
<?php

namespace Tests\Unit;

use App\Jobs\jobKtTheBHYT;
use Illuminate\Support\Facades\DB;
use Tests\Support\DungBangLoiDotDieuTriSqlite;
use Tests\TestCase;

/**
 * Cong bao loi (vd 050 "The khong ton tai!") khong tra so the/ho ten/ngay sinh: dong loi chi
 * con ma ho so. Job phai luu gia tri MINH DA GUI de man ket qua con cai de nhin.
 */
class JobKtTheBHYTGhiThongTinGuiTest extends TestCase
{
    use DungBangLoiDotDieuTriSqlite;

    protected function setUp()
    {
        parent::setUp();
        $this->chuanBiBangLoi();
    }

    private function ketQuaCong(array $ghiDe = [])
    {
        $k = array_fill_keys(['maKetQua', 'ghiChu', 'maThe', 'hoTen', 'ngaySinh', 'diaChi', 'maTheCu',
            'maTheMoi', 'maDKBD', 'cqBHXH', 'gioiTinh', 'gtTheTu', 'gtTheDen', 'maKV', 'ngayDu5Nam',
            'maSoBHXH', 'gtTheTuMoi', 'gtTheDenMoi', 'maDKBDMoi', 'tenDKBDMoi'], null);

        return array_merge($k, $ghiDe);
    }

    private function ghi(array $ketQua)
    {
        $job = new jobKtTheBHYT([
            'maThe' => 'DN4010112345678', 'hoTen' => 'Nguyễn Văn A', 'ngaySinh' => '20/02/1979',
            'ma_lk' => 'HS1', 'maCskcb' => '01929', 'maDkbd' => '01005', 'gioiTinh' => 2,
        ]);
        $m = new \ReflectionMethod($job, 'addCheckHeinCard');
        $m->setAccessible(true);
        $m->invoke($job, 'HS1', $ketQua['maKetQua'], '11', $ketQua);

        return DB::table('check_hein_cards')->where('ma_lk', 'HS1')->first();
    }

    /** @test */
    public function cong_bao_loi_van_luu_gia_tri_da_gui()
    {
        $r = $this->ghi($this->ketQuaCong(['maKetQua' => '050', 'ghiChu' => 'Thẻ không tồn tại!']));

        $this->assertNull($r->ma_the);
        $this->assertSame('DN4010112345678', $r->ma_the_gui);
        $this->assertSame('Nguyễn Văn A', $r->ho_ten_gui);
        $this->assertSame('20/02/1979', $r->ngay_sinh_gui);
        $this->assertSame('01005', $r->ma_dkbd_gui);
    }

    /** @test */
    public function luu_duoc_han_the_moi()
    {
        $r = $this->ghi($this->ketQuaCong([
            'maKetQua' => '000', 'gtTheTuMoi' => '01/10/2026', 'gtTheDenMoi' => '30/09/2027',
        ]));

        $this->assertSame('01/10/2026', $r->gt_the_tumoi);
        $this->assertSame('30/09/2027', $r->gt_the_denmoi);
    }
}
```

- [ ] **Step 2: Chạy, xác nhận đỏ**

Run: `DB_HOST=127.0.0.1 php vendor/bin/phpunit tests/Unit/JobKtTheBHYTGhiThongTinGuiTest.php`
Expected: `cong_bao_loi_van_luu_gia_tri_da_gui` FAIL (`ma_the_gui` null). `luu_duoc_han_the_moi` PASS nhờ Task 1; đó là bằng chứng lỗi fillable đã được sửa.

- [ ] **Step 3: Sửa job**

Trong `app/Jobs/jobKtTheBHYT.php`, trong mảng thứ hai của `check_hein_card::updateOrCreate(...)` ở `addCheckHeinCard`, thêm sau dòng `'ten_dkbd_moi' => $result_check['tenDKBDMoi'],`:

```php
                // Gia tri MINH DA GUI: cong bao loi thuong bo trong so the/ho ten/ngay sinh.
                'ma_the_gui' => $this->thamSo('maThe'),
                'ho_ten_gui' => $this->thamSo('hoTen'),
                'ngay_sinh_gui' => $this->thamSo('ngaySinh'),
                'ma_dkbd_gui' => $this->thamSo('maDkbd'),
```

Thêm hàm private cuối lớp:

```php
    /** Mot tham so cua job, thieu khoa thi null (job cu trong hang doi co the thieu). */
    private function thamSo($khoa)
    {
        return isset($this->params[$khoa]) ? $this->params[$khoa] : null;
    }
```

- [ ] **Step 4: Chạy lại**

Run:
```bash
DB_HOST=127.0.0.1 php vendor/bin/phpunit tests/Unit/JobKtTheBHYTGhiThongTinGuiTest.php
DB_HOST=127.0.0.1 php vendor/bin/phpunit tests/Unit/JobKtTheBHYTTheTamTest.php
DB_HOST=127.0.0.1 php vendor/bin/phpunit tests/Unit/JobKtTheBHYTLichSuKcbTest.php
```
Expected: cả ba `OK`.

- [ ] **Step 5: Commit**

```bash
git add app/Jobs/jobKtTheBHYT.php tests/Unit/JobKtTheBHYTGhiThongTinGuiTest.php
git commit -m "feat(the-bhyt): job luu so the, ho ten, ngay sinh, noi DKBD da gui len cong

Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>"
```

---

### Task 3: Service `TraLaiThe` dùng chung + màn Tra cứu lỗi hồ sơ gọi service

**Files:**
- Create: `app/Services/BHYT/TraLaiThe.php`
- Modify: `app/Http/Controllers/KHTH/TraCuuLoiHoSoController.php` (hàm `traLaiThe`, `gioiTinhCongBhxh`, các `use`)
- Modify: `tests/Feature/TraCuuLoiHoSoTest.php` (test `tra_lai_the_chan_the_tam_so_sinh_va_bao_ro_ly_do`)
- Test: `tests/Unit/TraLaiTheServiceTest.php`

**Interfaces:**
- Consumes: `App\Services\OrderCheck\TreatmentProfileService::cua(string): ?array` (các khoá `treatment_code`, `patient_name`, `patient_dob`, `gender_code`, `hein_card_number`, `hein_medi_org_code`, `ma_cskcb`); `App\Services\Xml3176\Support\TheTamSoSinh::la($maThe, $maDkbd): bool`.
- Produces: `App\Services\BHYT\TraLaiThe::gui($treatmentCode): array` trả về `['ok' => bool, 'message' => string]`. Lấy từ container: `app(TraLaiThe::class)` hoặc tiêm vào action controller.

- [ ] **Step 1: Viết test đỏ cho service**

`tests/Unit/TraLaiTheServiceTest.php`:

```php
<?php

namespace Tests\Unit;

use App\Jobs\jobKtTheBHYT;
use App\Services\BHYT\TraLaiThe;
use App\Services\OrderCheck\TreatmentProfileService;
use Illuminate\Support\Facades\Queue;
use Tests\Support\DungBangHoSoHisSqlite;
use Tests\TestCase;

class TraLaiTheServiceTest extends TestCase
{
    use DungBangHoSoHisSqlite;

    const MA = '01013250800123';

    protected function setUp()
    {
        parent::setUp();
        $this->chuanBiBangHoSo();
        Queue::fake();
        config(['organization.BHYT_CO_SO' => ['01001' => ['username' => 'u']]]);
    }

    private function gui($ma)
    {
        return app(TraLaiThe::class)->gui($ma);
    }

    private function thamSoJob($job)
    {
        $r = new \ReflectionProperty(get_class($job), 'params');
        $r->setAccessible(true);

        return $r->getValue($job);
    }

    /** @test */
    public function ma_rong()
    {
        $this->assertSame(['ok' => false, 'message' => 'Chưa nhập mã điều trị'], $this->gui('  '));
        Queue::assertNotPushed(jobKtTheBHYT::class);
    }

    /** @test */
    public function khong_co_ho_so()
    {
        $this->assertSame(['ok' => false, 'message' => 'Không tìm thấy hồ sơ với mã này trên HIS'], $this->gui('KHONG-CO'));
    }

    /** @test */
    public function his_loi()
    {
        $this->app->instance(TreatmentProfileService::class, new class extends TreatmentProfileService {
            public function cua($treatmentCode)
            {
                throw new \Exception('Oracle sap');
            }
        });

        $this->assertSame(['ok' => false, 'message' => 'Không lấy được thông tin từ HIS'], $this->gui(self::MA));
    }

    /** @test */
    public function thieu_the()
    {
        $this->themHoSo(['tdl_hein_card_number' => null]);
        $this->assertSame(['ok' => false, 'message' => 'Hồ sơ không có mã thẻ BHYT'], $this->gui(self::MA));
    }

    /** @test */
    public function thieu_gioi_tinh()
    {
        $this->themHoSo(['tdl_patient_gender_id' => null]);
        $this->assertSame(['ok' => false, 'message' => 'Hồ sơ thiếu giới tính'], $this->gui(self::MA));
    }

    /** @test */
    public function co_so_khong_co_trong_cau_hinh()
    {
        config(['organization.BHYT_CO_SO' => ['09999' => ['username' => 'u']]]);
        $this->themHoSo();
        $this->assertSame(['ok' => false, 'message' => 'Không xác định được cơ sở của hồ sơ'], $this->gui(self::MA));
        Queue::assertNotPushed(jobKtTheBHYT::class);
    }

    /** @test */
    public function hop_le_day_dung_mot_job_dung_hang_doi_dung_tham_so()
    {
        $this->themHoSo();

        $kq = $this->gui(self::MA);

        $this->assertTrue($kq['ok']);
        $this->assertSame('Đã gửi yêu cầu tra lại thẻ, bấm Tra cứu lại sau ít giây để xem kết quả', $kq['message']);
        Queue::assertPushed(jobKtTheBHYT::class, 1);
        Queue::assertPushedOn('JobKtTheBHYT', jobKtTheBHYT::class);
        Queue::assertPushed(jobKtTheBHYT::class, function ($job) {
            $p = $this->thamSoJob($job);
            $this->assertSame(2, $p['gioiTinh']);          // HIS 1 = Nam -> cong 2
            $this->assertSame('01001', $p['maCskcb']);
            $this->assertSame('01005', $p['maDkbd']);
            $this->assertSame('DN4010112345678', $p['maThe']);
            $this->assertSame('20/02/1979', $p['ngaySinh']);

            return true;
        });
    }

    /** @test */
    public function the_tam_van_day_job_de_xoa_ket_qua_loi_cu()
    {
        // Job gap the tam se xoa dong loi cu thay vi goi cong: nut bam cung la cach don tung dong.
        $this->themHoSo(['tdl_hein_card_number' => 'TE1010000012345', 'tdl_hein_medi_org_code' => '01000']);

        $kq = $this->gui(self::MA);

        $this->assertTrue($kq['ok']);
        $this->assertSame('Thẻ tạm trẻ sơ sinh (nơi ĐKBĐ 01000), không tra cổng BHXH — đã gửi yêu cầu xoá kết quả lỗi cũ', $kq['message']);
        Queue::assertPushedOn('JobKtTheBHYT', jobKtTheBHYT::class);
    }
}
```

- [ ] **Step 2: Chạy, xác nhận đỏ**

Run: `DB_HOST=127.0.0.1 php vendor/bin/phpunit tests/Unit/TraLaiTheServiceTest.php`
Expected: mọi test Error: `Class 'App\Services\BHYT\TraLaiThe' does not exist`.

- [ ] **Step 3: Tạo service**

`app/Services/BHYT/TraLaiThe.php`:

```php
<?php

namespace App\Services\BHYT;

use App\Jobs\jobKtTheBHYT;
use App\Services\OrderCheck\TreatmentProfileService;
use App\Services\Xml3176\Support\TheTamSoSinh;
use Illuminate\Support\Facades\Log;

/**
 * Gui yeu cau tra lai the BHYT cho MOT ho so - dung chung cho man Tra cuu loi ho so va man
 * Ket qua tra cuu the. Mot noi duy nhat kiem ho so HIS truoc khi day job: tung co hai ban
 * tu viet, sua mot ben quen ben kia.
 */
class TraLaiThe
{
    protected $hoSo;

    public function __construct(TreatmentProfileService $hoSo)
    {
        $this->hoSo = $hoSo;
    }

    /**
     * @param string $treatmentCode ma dieu tri (= ma_lk)
     * @return array ['ok' => bool, 'message' => string]
     */
    public function gui($treatmentCode)
    {
        $ma = trim((string) $treatmentCode);

        if ($ma === '') {
            return $this->kq(false, 'Chưa nhập mã điều trị');
        }

        try {
            $hoSo = $this->hoSo->cua($ma);
        } catch (\Exception $e) {
            Log::error('Tra lai the: loi doc HIS', ['treatment_code' => $ma, 'loi' => $e->getMessage()]);

            return $this->kq(false, 'Không lấy được thông tin từ HIS');
        }

        if (!$hoSo) {
            return $this->kq(false, 'Không tìm thấy hồ sơ với mã này trên HIS');
        }

        if (trim((string) $hoSo['hein_card_number']) === '') {
            return $this->kq(false, 'Hồ sơ không có mã thẻ BHYT');
        }

        // The tam: VAN day job - job khong goi cong ma xoa ket qua loi cu cua ho so, nen
        // nut bam cung la cach don tung dong. Khong can gioi tinh/co so vi job thoat truoc.
        if (TheTamSoSinh::la($hoSo['hein_card_number'], $hoSo['hein_medi_org_code'])) {
            $this->day($hoSo);

            return $this->kq(true, 'Thẻ tạm trẻ sơ sinh (nơi ĐKBĐ ' . trim((string) $hoSo['hein_medi_org_code'])
                . '), không tra cổng BHXH — đã gửi yêu cầu xoá kết quả lỗi cũ');
        }

        // Left join his_gender (xem TreatmentProfileService) nen gioi tinh co the rong.
        // Gui rong len cong chi doi mot loi ro rang lay mot ket qua sai.
        if (trim((string) $hoSo['gender_code']) === '') {
            return $this->kq(false, 'Hồ sơ thiếu giới tính');
        }

        $maCskcb = trim((string) $hoSo['ma_cskcb']);
        $dsCoSo = config('organization.BHYT_CO_SO', []);

        if ($maCskcb === '' || !isset($dsCoSo[$maCskcb])) {
            return $this->kq(false, 'Không xác định được cơ sở của hồ sơ');
        }

        $this->day($hoSo);

        return $this->kq(true, 'Đã gửi yêu cầu tra lại thẻ, bấm Tra cứu lại sau ít giây để xem kết quả');
    }

    protected function day(array $hoSo)
    {
        jobKtTheBHYT::dispatch([
            'maThe'    => $hoSo['hein_card_number'],
            'hoTen'    => $hoSo['patient_name'],
            'ngaySinh' => dob($hoSo['patient_dob']),
            'ma_lk'    => $hoSo['treatment_code'],
            // maCskcb = co so DIEU TRI (chon tai khoan cong); maDkbd = noi DKBD tren THE.
            'maCskcb'  => trim((string) $hoSo['ma_cskcb']),
            'maDkbd'   => $hoSo['hein_medi_org_code'],
            'gioiTinh' => $this->gioiTinhCongBhxh($hoSo['gender_code']),
        // checkOldValue = false: de mac dinh true thi job thay ket qua cu con hop le va
        // thoat ngay - dung nghia "bam nut xong khong co gi xay ra".
        ], false)->onQueue('JobKtTheBHYT');
    }

    /**
     * HIS dung gender_code 1 = Nam, 2 = Nu; cong BHXH dung quy uoc nguoc lai. Lenh quet
     * HISProKiemTraTheBHYT dao o cung cho nay - bo qua thi cong tra ve ket qua sai.
     */
    protected function gioiTinhCongBhxh($genderCode)
    {
        $g = (int) $genderCode;

        if ($g === 1) {
            return 2;
        }

        if ($g === 2) {
            return 1;
        }

        return $g;
    }

    protected function kq($ok, $message)
    {
        return ['ok' => $ok, 'message' => $message];
    }
}
```

- [ ] **Step 4: Chạy test service**

Run: `DB_HOST=127.0.0.1 php vendor/bin/phpunit tests/Unit/TraLaiTheServiceTest.php`
Expected: `OK (8 tests, ...)`.

- [ ] **Step 5: Sửa test màn Tra cứu lỗi hồ sơ theo hành vi mới của thẻ tạm (đỏ)**

Trong `tests/Feature/TraCuuLoiHoSoTest.php`, thay toàn bộ test `tra_lai_the_chan_the_tam_so_sinh_va_bao_ro_ly_do` bằng:

```php
    /** @test */
    public function tra_lai_the_the_tam_van_gui_job_de_xoa_ket_qua_loi_cu()
    {
        // Job gap the tam khong goi cong ma xoa dong loi cu - nut bam la cach don tung dong.
        Queue::fake();
        config(['organization.BHYT_CO_SO' => ['01001' => ['username' => 'u']]]);
        $this->themHoSo([
            'treatment_code' => 'HS-THE-TAM',
            'tdl_hein_card_number' => 'TE1010000012345',
            'tdl_hein_medi_org_code' => '01000',
        ]);

        $this->traLaiThe('HS-THE-TAM')
            ->assertStatus(200)
            ->assertJsonFragment(['message' => 'Thẻ tạm trẻ sơ sinh (nơi ĐKBĐ 01000), không tra cổng BHXH — đã gửi yêu cầu xoá kết quả lỗi cũ']);

        Queue::assertPushedOn('JobKtTheBHYT', jobKtTheBHYT::class);
    }
```

Run: `DB_HOST=127.0.0.1 php vendor/bin/phpunit --filter the_tam tests/Feature/TraCuuLoiHoSoTest.php`
Expected: FAIL (controller cũ trả 422).

- [ ] **Step 6: Controller gọi service**

Trong `app/Http/Controllers/KHTH/TraCuuLoiHoSoController.php`:
- Thay toàn bộ thân `traLaiThe` bằng:

```php
    public function traLaiThe(Request $request, TraLaiThe $traLai)
    {
        // Moi buoc kiem ho so HIS nam trong TraLaiThe - dung chung voi man Ket qua tra cuu the.
        $kq = $traLai->gui($this->layMa($request));

        return response()->json(['message' => $kq['message']], $kq['ok'] ? 200 : 422);
    }
```

- Xoá hàm `gioiTinhCongBhxh()` (đã chuyển vào service).
- Ở các dòng `use`: xoá `use App\Jobs\jobKtTheBHYT;` và `use App\Services\Xml3176\Support\TheTamSoSinh;`, thêm `use App\Services\BHYT\TraLaiThe;`. Chạy `grep -n "jobKtTheBHYT\|TheTamSoSinh\|Log::" app/Http/Controllers/KHTH/TraCuuLoiHoSoController.php` để chắc không còn chỗ nào dùng hai lớp đã xoá. Giữ `use ...Log;` nếu hàm khác còn dùng.

- [ ] **Step 7: Chạy lại toàn bộ test màn Tra cứu lỗi hồ sơ + service**

Run:
```bash
DB_HOST=127.0.0.1 php vendor/bin/phpunit tests/Feature/TraCuuLoiHoSoTest.php
DB_HOST=127.0.0.1 php vendor/bin/phpunit tests/Unit/TraLaiTheServiceTest.php
```
Expected: `OK (20 tests, ...)` và `OK (8 tests, ...)`.

- [ ] **Step 8: Commit**

```bash
git add app/Services/BHYT/TraLaiThe.php app/Http/Controllers/KHTH/TraCuuLoiHoSoController.php tests/Unit/TraLaiTheServiceTest.php tests/Feature/TraCuuLoiHoSoTest.php
git commit -m "refactor(the-bhyt): tach service TraLaiThe dung chung; the tam van day job de xoa loi cu

Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>"
```

---

### Task 4: Route + controller màn Kết quả tra cứu thẻ

**Files:**
- Modify: `routes/web.php` (sau route `bhyt.check-hein-card.export`, trong nhóm `checkrole:xml-man`)
- Modify: `app/Http/Controllers/BHYT/CheckHeinCardController.php`
- Test: `tests/Feature/KetQuaTraCuuTheTraLaiTest.php`

**Interfaces:**
- Consumes: `TraLaiThe::gui()` (Task 3); `check_hein_card::hienThi()`, `TRUONG_HIEN` (Task 1).
- Produces:
  - Route `POST bhyt/check-hein-card/tra-lai`, tên `bhyt.check-hein-card.tra-lai`, tham số `ma_lk`; JSON `{message}`, 200 hoặc 422.
  - JSON `fetch-data` có thêm `hien_ma_the`, `hien_ho_ten`, `hien_ngay_sinh`, `nguon_ma_the`, `nguon_ho_ten`, `nguon_ngay_sinh`, cùng 4 cột `*_gui` (có sẵn vì là cột bảng).

- [ ] **Step 1: Viết test đỏ**

`tests/Feature/KetQuaTraCuuTheTraLaiTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Jobs\jobKtTheBHYT;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Tests\Support\DungBangHoSoHisSqlite;
use Tests\Support\DungBangLoiDotDieuTriSqlite;
use Tests\TestCase;

/** Nguoi dung gia: hasRole() dung cho role duoc cap (factory that bi CheckRole chan 403). */
class NguoiDungKetQuaTraThe extends \App\User
{
    public $roles = [];

    public function hasRole($role, $team = null, $requireAll = false)
    {
        return in_array($role, $this->roles);
    }

    public function can($permission, $team = null, $requireAll = false)
    {
        return false;
    }
}

class KetQuaTraCuuTheTraLaiTest extends TestCase
{
    use DungBangLoiDotDieuTriSqlite;
    use DungBangHoSoHisSqlite;

    protected function setUp()
    {
        parent::setUp();
        $this->chuanBiBangLoi();
        $this->chuanBiBangHoSo();
    }

    private function nguoiDung(array $roles = ['xml-man'])
    {
        $u = new NguoiDungKetQuaTraThe();
        $u->id = 1;
        $u->roles = $roles;

        return $u;
    }

    private function themKetQua(array $ghiDe = [])
    {
        DB::table('check_hein_cards')->insert(array_merge([
            'ma_lk' => '01013250800123', 'ma_cskcb' => '01001', 'ma_tracuu' => '050', 'ma_kiemtra' => '11',
            'ghi_chu' => 'Thẻ không tồn tại!', 'ma_the' => null, 'ho_ten' => null, 'ngay_sinh' => null,
            'ma_the_gui' => 'DN4010112345678', 'ho_ten_gui' => 'Nguyễn Văn A', 'ngay_sinh_gui' => '20/02/1979',
            'created_at' => '2026-09-24 08:00:00', 'updated_at' => '2026-09-24 08:00:00',
        ], $ghiDe));
    }

    private function fetch(array $q = [])
    {
        return $this->actingAs($this->nguoiDung())
            ->getJson(route('bhyt.check-hein-card.fetch-data', array_merge([
                'draw' => 1, 'start' => 0, 'length' => 10,
                'tu_ngay' => '2026-09-01', 'den_ngay' => '2026-09-30',
            ], $q)))
            ->assertStatus(200)
            ->json();
    }

    /** @test */
    public function tra_lai_can_quyen_xml_man()
    {
        $this->actingAs($this->nguoiDung(['khac']))
            ->postJson(route('bhyt.check-hein-card.tra-lai'), ['ma_lk' => '01013250800123'])
            ->assertStatus(403);
    }

    /** @test */
    public function tra_lai_day_dung_mot_job_dung_hang_doi()
    {
        Queue::fake();
        config(['organization.BHYT_CO_SO' => ['01001' => ['username' => 'u']]]);
        $this->themHoSo();

        $this->actingAs($this->nguoiDung())
            ->postJson(route('bhyt.check-hein-card.tra-lai'), ['ma_lk' => '01013250800123'])
            ->assertStatus(200)
            ->assertJsonFragment(['message' => 'Đã gửi yêu cầu tra lại thẻ, bấm Tra cứu lại sau ít giây để xem kết quả']);

        Queue::assertPushed(jobKtTheBHYT::class, 1);
        Queue::assertPushedOn('JobKtTheBHYT', jobKtTheBHYT::class);
    }

    /** @test */
    public function tra_lai_thieu_ma_tra_422()
    {
        Queue::fake();

        $this->actingAs($this->nguoiDung())
            ->postJson(route('bhyt.check-hein-card.tra-lai'), [])
            ->assertStatus(422)
            ->assertJsonFragment(['message' => 'Chưa nhập mã điều trị']);

        Queue::assertNotPushed(jobKtTheBHYT::class);
    }

    /** @test */
    public function fetch_hien_gia_tri_da_gui_khi_cong_bo_trong()
    {
        $this->themKetQua();

        $d = $this->fetch()['data'][0];

        $this->assertSame('DN4010112345678', $d['hien_ma_the']);
        $this->assertSame('gui', $d['nguon_ma_the']);
        $this->assertSame('Nguyễn Văn A', $d['hien_ho_ten']);
        $this->assertSame('20/02/1979', $d['hien_ngay_sinh']);
    }

    /** @test */
    public function fetch_uu_tien_gia_tri_cong()
    {
        $this->themKetQua(['ma_the' => 'DN4010199999999', 'ma_tracuu' => '000', 'ma_kiemtra' => '09']);

        $d = $this->fetch()['data'][0];

        $this->assertSame('DN4010199999999', $d['hien_ma_the']);
        $this->assertSame('cong', $d['nguon_ma_the']);
    }

    /** @test */
    public function tim_theo_ho_ten_da_gui()
    {
        $this->themKetQua();
        $this->themKetQua(['ma_lk' => 'HS-KHAC', 'ho_ten_gui' => 'Trần Thị B', 'ma_the_gui' => 'HT3010000000001']);

        $this->assertSame(1, $this->fetch(['tim' => 'Trần Thị'])['recordsFiltered']);
        $this->assertSame(1, $this->fetch(['tim' => 'HT301000'])['recordsFiltered']);
    }
}
```

- [ ] **Step 2: Chạy, xác nhận đỏ**

Run: `DB_HOST=127.0.0.1 php vendor/bin/phpunit tests/Feature/KetQuaTraCuuTheTraLaiTest.php`
Expected: test route lỗi `Route [bhyt.check-hein-card.tra-lai] not defined`; test fetch lỗi thiếu khoá `hien_ma_the`; test tìm ra 0.

- [ ] **Step 3: Thêm route**

Trong `routes/web.php`, ngay sau:
```php
        Route::get('check-hein-card/export', 'BHYT\CheckHeinCardController@xuatExcel')
        ->name('bhyt.check-hein-card.export');
```
thêm:
```php
        Route::post('check-hein-card/tra-lai', 'BHYT\CheckHeinCardController@traLai')
        ->name('bhyt.check-hein-card.tra-lai');
```

- [ ] **Step 4: Sửa controller**

Trong `app/Http/Controllers/BHYT/CheckHeinCardController.php`:

Thêm `use App\Services\BHYT\TraLaiThe;` vào khối `use`.

Trong `locTheoYeuCau()`, thay khối `tim` bằng:
```php
        if ($tim = trim((string) $request->get('tim'))) {
            // Tim ca gia tri DA GUI: dong loi thuong khong co so the/ho ten tu cong.
            $q->where(function ($w) use ($tim) {
                $w->where('ma_lk', 'like', '%' . $tim . '%')
                  ->orWhere('ma_the', 'like', '%' . $tim . '%')
                  ->orWhere('ho_ten', 'like', '%' . $tim . '%')
                  ->orWhere('ma_the_gui', 'like', '%' . $tim . '%')
                  ->orWhere('ho_ten_gui', 'like', '%' . $tim . '%');
            });
        }
```

Thay toàn bộ `fetch()` bằng:
```php
    public function fetch(Request $request)
    {
        $q = $this->locTheoYeuCau($request);

        $dt = Datatables::of($q)
            // Nhan tieng Viet: ma tran khong noi gi cho nguoi doc. NhanMaThe tra ma tran khi
            // gap ma la thay vi nem "Undefined index" nhu cac blade cu.
            ->addColumn('nhan_tracuu', function ($r) {
                return NhanMaThe::traCuu($r->ma_tracuu);
            })
            ->addColumn('nhan_kiemtra', function ($r) {
                return NhanMaThe::kiemTra($r->ma_kiemtra);
            })
            // De blade to mau dong loi ma khong phai lap lai dieu kien o phia trinh duyet.
            ->addColumn('co_loi', function ($r) {
                return $r->ma_tracuu !== check_hein_card::TRA_CUU_SACH
                    || $r->ma_kiemtra !== check_hein_card::KIEM_TRA_SACH;
            });

        // Gia tri de hien cho ba truong quan sat + nguon cua TUNG truong (mot dong co the co
        // so the tu cong nhung ho ten phai lay gia tri da gui).
        foreach (check_hein_card::TRUONG_HIEN as $truong) {
            $dt->addColumn('hien_' . $truong, function ($r) use ($truong) {
                return $r->hienThi($truong)[0];
            })->addColumn('nguon_' . $truong, function ($r) use ($truong) {
                return $r->hienThi($truong)[1];
            });
        }

        return $dt->make(true);
    }

    /** Tra lai the cua MOT ho so - cung service voi man Tra cuu loi ho so. */
    public function traLai(Request $request, TraLaiThe $traLai)
    {
        $kq = $traLai->gui((string) $request->input('ma_lk'));

        return response()->json(['message' => $kq['message']], $kq['ok'] ? 200 : 422);
    }
```

- [ ] **Step 5: Chạy lại**

Run:
```bash
DB_HOST=127.0.0.1 php vendor/bin/phpunit tests/Feature/KetQuaTraCuuTheTraLaiTest.php
DB_HOST=127.0.0.1 php vendor/bin/phpunit --filter fetch_va_xuat_dung_chung_mot_nguon_truy_van tests/Unit/XuatExcelKetQuaTraCuuTheTest.php
```
Expected: `OK (6 tests, ...)`. Test thứ hai vẫn `OK` (vẫn đúng 2 lần `$this->locTheoYeuCau($request)` và 1 lần `check_hein_card::query()`).

- [ ] **Step 6: Commit**

```bash
git add routes/web.php app/Http/Controllers/BHYT/CheckHeinCardController.php tests/Feature/KetQuaTraCuuTheTraLaiTest.php
git commit -m "feat(the-bhyt): man ket qua tra the - cot hien gia tri da gui, tim theo gia tri gui, route tra lai

Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>"
```

---

### Task 5: Giao diện + xuất Excel

**Files:**
- Modify: `resources/views/bhyt/check-hein-card/index.blade.php`
- Modify: `app/Exports/KetQuaTraCuuTheExport.php`
- Test: `tests/Unit/ManKetQuaTraCuuTheGiaoDienTest.php`

**Interfaces:**
- Consumes: route `bhyt.check-hein-card.tra-lai` (Task 4); khoá JSON `hien_*`, `nguon_*`, `co_loi`, `*_gui` (Task 4).
- Produces: giao diện hoàn chỉnh; tệp Excel có 30 cột (26 cũ + 4 cột "đã gửi").

- [ ] **Step 1: Viết test đỏ**

`tests/Unit/ManKetQuaTraCuuTheGiaoDienTest.php`:

```php
<?php

namespace Tests\Unit;

use App\Exports\KetQuaTraCuuTheExport;
use App\Models\CheckBHYT\check_hein_card;
use Tests\TestCase;

class ManKetQuaTraCuuTheGiaoDienTest extends TestCase
{
    private function blade()
    {
        return file_get_contents(base_path('resources/views/bhyt/check-hein-card/index.blade.php'));
    }

    /** @test */
    public function ba_cot_quan_sat_lay_tu_cot_hien_va_khong_sap_xep()
    {
        $b = $this->blade();

        foreach (['hien_ma_the', 'hien_ho_ten', 'hien_ngay_sinh'] as $cot) {
            $this->assertContains('"data": "' . $cot . '"', $b);
        }
        $this->assertContains('Theo HIS (cổng không trả về)', $b);
    }

    /** @test */
    public function co_nut_tra_lai_goi_dung_route_va_tai_lai_giu_trang()
    {
        $b = $this->blade();

        $this->assertContains("route('bhyt.check-hein-card.tra-lai')", $b);
        $this->assertContains('nut-tra-lai', $b);
        $this->assertContains('table.ajax.reload(null, false)', $b);
        $this->assertContains('csrf_token()', $b);
    }

    /** @test */
    public function modal_co_bon_dong_da_gui()
    {
        $b = $this->blade();

        foreach (['ma_the_gui', 'ho_ten_gui', 'ngay_sinh_gui', 'ma_dkbd_gui'] as $cot) {
            $this->assertContains("['" . $cot . "'", $b);
        }
    }

    /** @test */
    public function xuat_excel_so_tieu_de_khop_so_cot_va_co_cot_da_gui()
    {
        // Khong doc bang that: dung mot dong dung tay.
        $x = new KetQuaTraCuuTheExport(check_hein_card::query());
        $r = new check_hein_card(['ma_lk' => 'HS1', 'ma_the_gui' => 'DN4010112345678', 'ma_dkbd_gui' => '01005']);

        $this->assertSame(count($x->headings()), count($x->map($r)));
        $this->assertSame(['Số thẻ đã gửi', 'Họ tên đã gửi', 'Ngày sinh đã gửi', 'Nơi ĐKBĐ đã gửi'],
            array_slice($x->headings(), -4));
        $this->assertSame('DN4010112345678', array_slice($x->map($r), -4)[0]);
    }
}
```

- [ ] **Step 2: Chạy, xác nhận đỏ**

Run: `DB_HOST=127.0.0.1 php vendor/bin/phpunit tests/Unit/ManKetQuaTraCuuTheGiaoDienTest.php`
Expected: 4 FAIL.

- [ ] **Step 3: Sửa export**

Trong `app/Exports/KetQuaTraCuuTheExport.php`:
- `headings()`: sau `'Thời gian tra cứu',` thêm:
```php
            // Gia tri DA GUI len cong - them CUOI de khong xe dich cot nguoi dung da quen.
            'Số thẻ đã gửi',
            'Họ tên đã gửi',
            'Ngày sinh đã gửi',
            'Nơi ĐKBĐ đã gửi',
```
- `map()`: sau `(string) $r->updated_at,` thêm:
```php
            $r->ma_the_gui,
            $r->ho_ten_gui,
            $r->ngay_sinh_gui,
            $r->ma_dkbd_gui,
```

- [ ] **Step 4: Sửa blade**

Trong `resources/views/bhyt/check-hein-card/index.blade.php`:

a) Tiêu đề cột cuối: đổi `<th>Xem</th>` thành `<th>Thao tác</th>`.

b) Trong `TRUONG_CHI_TIET`, sau dòng `['ngay_sinh', 'Ngày sinh'],` thêm:
```js
        ['ma_the_gui', 'Số thẻ đã gửi'],
        ['ho_ten_gui', 'Họ tên đã gửi'],
        ['ngay_sinh_gui', 'Ngày sinh đã gửi'],
        ['ma_dkbd_gui', 'Nơi ĐKBĐ đã gửi'],
```

c) Ngay trước `function fetchData(startDate, endDate) {` thêm:
```js
    // Render cot quan sat: gia tri lay tu gia tri DA GUI (cong khong tra) thi in nghieng xam de
    // khong nham la du lieu cong xac nhan. .text() truoc .html(): du lieu tu cong/HIS.
    function hienGiaTri(cotNguon) {
        return function (d, type, row) {
            var t = $('<div>').text(d === null || d === undefined ? '' : d).html();

            return row[cotNguon] === 'gui'
                ? '<i class="text-muted" title="Theo HIS (cổng không trả về)">' + t + '</i>'
                : t;
        };
    }
```

d) Trong `"columns": [...]`, thay ba dòng:
```js
                { "data": "ma_the" },
                { "data": "ho_ten" },
                { "data": "ngay_sinh" },
```
bằng:
```js
                // Khong phai cot SQL: sap xep theo chung se lam truy van Datatables vo.
                { "data": "hien_ma_the", "orderable": false, "searchable": false, "render": hienGiaTri('nguon_ma_the') },
                { "data": "hien_ho_ten", "orderable": false, "searchable": false, "render": hienGiaTri('nguon_ho_ten') },
                { "data": "hien_ngay_sinh", "orderable": false, "searchable": false, "render": hienGiaTri('nguon_ngay_sinh') },
```
và thay cột cuối (`{ "data": "id", ... nut-xem ... }`) bằng:
```js
                { "data": "id", "orderable": false, "searchable": false, "render": function (d, type, row) {
                    var h = '<button type="button" class="btn btn-xs btn-default nut-xem" data-id="' + d + '">Xem</button>';

                    // Chi dong LOI moi co nut tra lai - dong hop le tra lai chi ton luot goi cong.
                    if (row.co_loi) {
                        h += ' <button type="button" class="btn btn-xs btn-warning nut-tra-lai">Tra lại</button>';
                    }

                    return h;
                } }
```

e) Trong `$(document).ready(...)`, sau handler `.nut-xem`, thêm:
```js
        $('#check-hein-card-list tbody').on('click', '.nut-tra-lai', function () {
            var nut = $(this);
            var d = table.row(nut.closest('tr')).data();

            nut.prop('disabled', true).text('Đang gửi...');

            $.post("{{ route('bhyt.check-hein-card.tra-lai') }}", {
                _token: '{{ csrf_token() }}', ma_lk: d.ma_lk
            }).done(function (r) {
                alert(r.message);
                // Doi job chay xong roi nap lai DUNG trang dang xem, giu bo loc (null, false).
                setTimeout(function () { table.ajax.reload(null, false); }, 5000);
            }).fail(function (x) {
                alert((x.responseJSON && x.responseJSON.message) || 'Không gửi được yêu cầu tra lại');
                nut.prop('disabled', false).text('Tra lại');
            });
        });
```

- [ ] **Step 5: Chạy lại**

Run:
```bash
DB_HOST=127.0.0.1 php vendor/bin/phpunit tests/Unit/ManKetQuaTraCuuTheGiaoDienTest.php
DB_HOST=127.0.0.1 php vendor/bin/phpunit --filter "route_va_nut_xuat_ton_tai|nut_xuat_dung_chung_tham_so_voi_datatables" tests/Unit/XuatExcelKetQuaTraCuuTheTest.php
```
Expected: `OK (4 tests, ...)` và `OK (2 tests, ...)`.

- [ ] **Step 6: Kiểm tra bằng trình duyệt (dev, chỉ đọc)**

Mở `/bhyt/check-hein-card/index` trên dev (`php artisan serve`, người dùng tự đăng nhập). Chọn Trạng thái = Lỗi và kiểm:
- cột số thẻ in nghiêng xám ở dòng 050;
- nút "Tra lại" chỉ hiện ở dòng lỗi;
- modal có 4 dòng "đã gửi" (sẽ trống cho tới khi chạy lệnh bù ở Task 6);
- console trình duyệt không có lỗi.

**Không bấm "Tra lại" trên CSDL thật** nếu người dùng chưa đồng ý: nút đó gọi cổng BHXH thật.

- [ ] **Step 7: Commit**

```bash
git add resources/views/bhyt/check-hein-card/index.blade.php app/Exports/KetQuaTraCuuTheExport.php tests/Unit/ManKetQuaTraCuuTheGiaoDienTest.php
git commit -m "feat(the-bhyt): man ket qua tra the - hien gia tri da gui, nut Tra lai tung dong, xuat them cot da gui

Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>"
```

---

### Task 6: Lệnh bù giá trị đã gửi cho dữ liệu cũ

**Files:**
- Create: `app/Console/Commands/BuThongTinGuiTheBhyt.php`
- Test: `tests/Unit/BuThongTinGuiTheBhytTest.php`

**Interfaces:**
- Consumes: cột `_gui` (Task 1); `check_hein_card::chiLoi()`; helper toàn cục `dob()` (`app/Http/Controllers/app-helpers.php`).
- Produces: lệnh `the-bhyt:bu-thong-tin-gui {--ghi} {--tat-ca}`. Không `--ghi` thì in `Se bu: N | Khong thay tren HIS: M`, ghi 0 dòng. Có `--ghi` thì in `Da bu: N | Khong thay tren HIS: M`.

- [ ] **Step 1: Viết test đỏ**

`tests/Unit/BuThongTinGuiTheBhytTest.php`:

```php
<?php

namespace Tests\Unit;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\Support\DungBangHoSoHisSqlite;
use Tests\Support\DungBangLoiDotDieuTriSqlite;
use Tests\TestCase;

class BuThongTinGuiTheBhytTest extends TestCase
{
    use DungBangLoiDotDieuTriSqlite;
    use DungBangHoSoHisSqlite;

    protected function setUp()
    {
        parent::setUp();
        $this->chuanBiBangLoi();
        $this->chuanBiBangHoSo();
        $this->themHoSo(); // 01013250800123, DN4010112345678, 19790220, DKBD 01005

        DB::table('check_hein_cards')->insert([
            ['ma_lk' => '01013250800123', 'ma_tracuu' => '050', 'ma_kiemtra' => '11',
             'created_at' => '2026-08-01 08:00:00', 'updated_at' => '2026-08-01 08:00:00'],
            ['ma_lk' => 'KHONG-CO-TREN-HIS', 'ma_tracuu' => '050', 'ma_kiemtra' => '11',
             'created_at' => '2026-08-01 08:00:00', 'updated_at' => '2026-08-01 08:00:00'],
            ['ma_lk' => 'HOP-LE', 'ma_tracuu' => '000', 'ma_kiemtra' => '00',
             'created_at' => '2026-08-01 08:00:00', 'updated_at' => '2026-08-01 08:00:00'],
        ]);
    }

    private function dong($maLk)
    {
        return DB::table('check_hein_cards')->where('ma_lk', $maLk)->first();
    }

    /** @test */
    public function mac_dinh_chi_dem_khong_ghi()
    {
        Artisan::call('the-bhyt:bu-thong-tin-gui');

        $this->assertContains('Se bu: 1 | Khong thay tren HIS: 1', Artisan::output());
        $this->assertNull($this->dong('01013250800123')->ma_the_gui);
    }

    /** @test */
    public function co_ghi_thi_bu_dung_va_khong_cham_updated_at()
    {
        Artisan::call('the-bhyt:bu-thong-tin-gui', ['--ghi' => true]);

        $r = $this->dong('01013250800123');
        $this->assertSame('DN4010112345678', $r->ma_the_gui);
        $this->assertSame('Nguyễn Văn A', $r->ho_ten_gui);
        $this->assertSame(dob('19790220'), $r->ngay_sinh_gui);
        $this->assertSame('01005', $r->ma_dkbd_gui);
        $this->assertSame('2026-08-01 08:00:00', $r->updated_at);
        $this->assertContains('Da bu: 1 | Khong thay tren HIS: 1', Artisan::output());
    }

    /** @test */
    public function mac_dinh_bo_qua_dong_hop_le_tat_ca_thi_xet_ca()
    {
        $this->themHoSo(['treatment_code' => 'HOP-LE']);

        Artisan::call('the-bhyt:bu-thong-tin-gui', ['--ghi' => true]);
        $this->assertNull($this->dong('HOP-LE')->ma_the_gui);

        Artisan::call('the-bhyt:bu-thong-tin-gui', ['--ghi' => true, '--tat-ca' => true]);
        $this->assertSame('DN4010112345678', $this->dong('HOP-LE')->ma_the_gui);
    }

    /** @test */
    public function chay_lai_khong_xet_dong_da_bu()
    {
        Artisan::call('the-bhyt:bu-thong-tin-gui', ['--ghi' => true]);
        Artisan::call('the-bhyt:bu-thong-tin-gui');

        $this->assertContains('Se bu: 0 | Khong thay tren HIS: 1', Artisan::output());
    }
}
```

- [ ] **Step 2: Chạy, xác nhận đỏ**

Run: `DB_HOST=127.0.0.1 php vendor/bin/phpunit tests/Unit/BuThongTinGuiTheBhytTest.php`
Expected: Error `The command "the-bhyt:bu-thong-tin-gui" does not exist.`

- [ ] **Step 3: Tạo lệnh**

`app/Console/Commands/BuThongTinGuiTheBhyt.php`:

```php
<?php

namespace App\Console\Commands;

use App\Models\CheckBHYT\check_hein_card;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Bu gia tri DA GUI (so the, ho ten, ngay sinh, noi DKBD) cho ket qua tra the cu, lay tu HIS.
 *
 * Tu Task 2 job tu ghi cac cot nay; lenh chi can chay MOT lan cho du lieu truoc do. Mac dinh
 * chi dem - co --ghi moi ghi.
 */
class BuThongTinGuiTheBhyt extends Command
{
    protected $signature = 'the-bhyt:bu-thong-tin-gui
        {--ghi : Ghi that; khong co thi chi dem}
        {--tat-ca : Xet ca dong hop le, khong chi dong loi}';

    protected $description = 'Bu so the/ho ten/ngay sinh/noi DKBD da gui cho ket qua tra the cu (lay tu HIS)';

    /** Oracle gioi han 1000 phan tu trong IN (...). */
    const LO = 900;

    public function handle()
    {
        $ghi = (bool) $this->option('ghi');
        $bang = (new check_hein_card)->getTable();

        $q = check_hein_card::query()
            ->where(function ($w) {
                $w->whereNull('ma_the_gui')->orWhere('ma_the_gui', '');
            });

        if (!$this->option('tat-ca')) {
            $q->chiLoi();
        }

        $thay = 0;
        $khongThay = 0;

        try {
            // chunkById (khong phai chunk): dong vua ghi roi khoi dieu kien loc, chunk theo
            // offset se nhay coc bo sot dong.
            $q->select('id', 'ma_lk')->chunkById(self::LO, function ($lo) use ($ghi, $bang, &$thay, &$khongThay) {
                $his = DB::connection('HISPro')->table('his_treatment')
                    ->whereIn('treatment_code', $lo->pluck('ma_lk')->unique()->values()->all())
                    ->get(['treatment_code', 'tdl_hein_card_number', 'tdl_patient_name',
                           'tdl_patient_dob', 'tdl_hein_medi_org_code'])
                    ->keyBy('treatment_code');

                foreach ($lo as $r) {
                    $h = $his->get($r->ma_lk);

                    if (!$h) {
                        $khongThay++;
                        continue;
                    }

                    $thay++;

                    if ($ghi) {
                        // DB::table: KHONG cham updated_at - do la "thoi gian tra cuu" tren man.
                        DB::table($bang)->where('id', $r->id)->update([
                            'ma_the_gui'    => $h->tdl_hein_card_number,
                            'ho_ten_gui'    => $h->tdl_patient_name,
                            // Cung ham dob() lenh quet dung: du lieu bu giong du lieu ghi moi.
                            'ngay_sinh_gui' => $h->tdl_patient_dob ? dob($h->tdl_patient_dob) : null,
                            'ma_dkbd_gui'   => $h->tdl_hein_medi_org_code,
                        ]);
                    }
                }
            });
        } catch (\Exception $e) {
            Log::error('the-bhyt:bu-thong-tin-gui loi', ['loi' => $e->getMessage()]);
            $this->error('Loi: ' . $e->getMessage() . ' (lo da ghi van giu, chay lai duoc)');

            return 1;
        }

        $this->info(($ghi ? 'Da bu: ' : 'Se bu: ') . $thay . ' | Khong thay tren HIS: ' . $khongThay);

        return 0;
    }
}
```

- [ ] **Step 4: Chạy lại**

Run: `DB_HOST=127.0.0.1 php vendor/bin/phpunit tests/Unit/BuThongTinGuiTheBhytTest.php`
Expected: `OK (4 tests, ...)`.

- [ ] **Step 5: Chạy thử chỉ đếm trên CSDL thật (không `--ghi`)**

Run: `php artisan the-bhyt:bu-thong-tin-gui`
Expected: khoảng `Se bu: 1213 | Khong thay tren HIS: 0` (số liệu 24/09/2026). Lệnh không ghi gì. **Không chạy `--ghi` trên CSDL thật** nếu người dùng chưa đồng ý.

- [ ] **Step 6: Commit**

```bash
git add app/Console/Commands/BuThongTinGuiTheBhyt.php tests/Unit/BuThongTinGuiTheBhytTest.php
git commit -m "feat(the-bhyt): lenh the-bhyt:bu-thong-tin-gui bu gia tri da gui tu HIS (mac dinh chi dem)

Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>"
```

---

### Task 7: Lệnh dọn thẻ tạm sơ sinh cũ

**Files:**
- Create: `app/Console/Commands/DonTheTamSoSinh.php`
- Test: `tests/Unit/DonTheTamSoSinhTest.php`

**Interfaces:**
- Consumes: `TheTamSoSinh::la()`; `config('qd130xml.hein_card_invalid.check_code' | '.result_code')`; cột `ma_the_gui`, `ma_dkbd_gui` (Task 1).
- Produces: lệnh `the-bhyt:don-the-tam {--ghi}`. Không `--ghi` thì in `Xet: N | The tam: T | Khong thay tren HIS: M` và không xoá. Có `--ghi` thì in thêm `Da xoa: T`.

- [ ] **Step 1: Viết test đỏ**

`tests/Unit/DonTheTamSoSinhTest.php`:

```php
<?php

namespace Tests\Unit;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\Support\DungBangHoSoHisSqlite;
use Tests\Support\DungBangLoiDotDieuTriSqlite;
use Tests\TestCase;

/**
 * 1.149 dong loi cu cua the tam (TE1 + DKBD XX000) cua benh nhan da ra vien - lenh quet
 * hang ngay chi quet BN dang nam nen job khong bao gio xoa duoc chung.
 */
class DonTheTamSoSinhTest extends TestCase
{
    use DungBangLoiDotDieuTriSqlite;
    use DungBangHoSoHisSqlite;

    protected function setUp()
    {
        parent::setUp();
        $this->chuanBiBangLoi();
        $this->chuanBiBangHoSo();

        // The tam, lay tu HIS (chua co _gui)
        $this->themHoSo(['treatment_code' => 'TAM-HIS', 'tdl_hein_card_number' => 'TE1010000012345', 'tdl_hein_medi_org_code' => '01000']);
        // The thuong co loi that
        $this->themHoSo(['treatment_code' => 'THUONG']);

        $moc = ['created_at' => '2026-08-01 08:00:00', 'updated_at' => '2026-08-01 08:00:00'];
        DB::table('check_hein_cards')->insert([
            array_merge(['ma_lk' => 'TAM-HIS', 'ma_tracuu' => '050', 'ma_kiemtra' => '11'], $moc),
            // The tam, da co _gui, khong co tren HIS
            array_merge(['ma_lk' => 'TAM-GUI', 'ma_tracuu' => '050', 'ma_kiemtra' => '11',
                'ma_the_gui' => 'TE1373700012345', 'ma_dkbd_gui' => '37000'], $moc),
            array_merge(['ma_lk' => 'THUONG', 'ma_tracuu' => '050', 'ma_kiemtra' => '11'], $moc),
            // The tam nhung ket qua HOP LE: khong dung toi
            array_merge(['ma_lk' => 'TAM-HOP-LE', 'ma_tracuu' => '000', 'ma_kiemtra' => '00',
                'ma_the_gui' => 'TE1010000054321', 'ma_dkbd_gui' => '01000'], $moc),
            array_merge(['ma_lk' => 'KHONG-THAY', 'ma_tracuu' => '050', 'ma_kiemtra' => '11'], $moc),
        ]);
    }

    private function con($maLk)
    {
        return DB::table('check_hein_cards')->where('ma_lk', $maLk)->exists();
    }

    /** @test */
    public function mac_dinh_chi_dem()
    {
        Artisan::call('the-bhyt:don-the-tam');

        $this->assertContains('Xet: 4 | The tam: 2 | Khong thay tren HIS: 1', Artisan::output());
        $this->assertTrue($this->con('TAM-HIS'));
        $this->assertTrue($this->con('TAM-GUI'));
    }

    /** @test */
    public function co_ghi_chi_xoa_the_tam_loi()
    {
        Artisan::call('the-bhyt:don-the-tam', ['--ghi' => true]);

        $this->assertFalse($this->con('TAM-HIS'));
        $this->assertFalse($this->con('TAM-GUI'));
        $this->assertTrue($this->con('THUONG'));
        $this->assertTrue($this->con('TAM-HOP-LE'));
        $this->assertTrue($this->con('KHONG-THAY'));
        $this->assertContains('Da xoa: 2', Artisan::output());
    }
}
```

- [ ] **Step 2: Chạy, xác nhận đỏ**

Run: `DB_HOST=127.0.0.1 php vendor/bin/phpunit tests/Unit/DonTheTamSoSinhTest.php`
Expected: Error `The command "the-bhyt:don-the-tam" does not exist.`

- [ ] **Step 3: Tạo lệnh**

`app/Console/Commands/DonTheTamSoSinh.php`:

```php
<?php

namespace App\Console\Commands;

use App\Models\CheckBHYT\check_hein_card;
use App\Services\Xml3176\Support\TheTamSoSinh;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Xoa ket qua tra LOI cu cua the tam tre so sinh (TE1 + noi DKBD XX000).
 *
 * jobKtTheBHYT chi xoa khi ho so duoc tra lai, ma lenh quet hang ngay chi quet BN dang nam -
 * dong cua BN da ra vien nam mai. Dung DUNG dieu kien "loi" cua job (hein_card_invalid) de
 * hai noi xoa cung mot tap. Mac dinh chi dem - co --ghi moi xoa.
 */
class DonTheTamSoSinh extends Command
{
    protected $signature = 'the-bhyt:don-the-tam
        {--ghi : Xoa that; khong co thi chi dem}';

    protected $description = 'Xoa ket qua tra the loi cu cua the tam tre so sinh (TE1 + DKBD XX000)';

    /** Oracle gioi han 1000 phan tu trong IN (...). */
    const LO = 900;

    public function handle()
    {
        $ghi = (bool) $this->option('ghi');
        $xet = 0;
        $tam = 0;
        $khongThay = 0;
        $daXoa = 0;

        $q = check_hein_card::query()->where(function ($w) {
            $w->whereIn('ma_kiemtra', config('qd130xml.hein_card_invalid.check_code'))
              ->orWhereIn('ma_tracuu', config('qd130xml.hein_card_invalid.result_code'));
        });

        try {
            $q->select('id', 'ma_lk', 'ma_the_gui', 'ma_dkbd_gui')
              ->chunkById(self::LO, function ($lo) use ($ghi, &$xet, &$tam, &$khongThay, &$daXoa) {
                $xet += $lo->count();

                // Uu tien gia tri da gui; chi hoi HIS cho dong chua co.
                $canHis = $lo->filter(function ($r) {
                    return trim((string) $r->ma_the_gui) === '';
                })->pluck('ma_lk')->unique()->values()->all();

                $his = empty($canHis) ? collect() : DB::connection('HISPro')->table('his_treatment')
                    ->whereIn('treatment_code', $canHis)
                    ->get(['treatment_code', 'tdl_hein_card_number', 'tdl_hein_medi_org_code'])
                    ->keyBy('treatment_code');

                $xoa = [];

                foreach ($lo as $r) {
                    if (trim((string) $r->ma_the_gui) !== '') {
                        $the = $r->ma_the_gui;
                        $dkbd = $r->ma_dkbd_gui;
                    } elseif ($h = $his->get($r->ma_lk)) {
                        $the = $h->tdl_hein_card_number;
                        $dkbd = $h->tdl_hein_medi_org_code;
                    } else {
                        $khongThay++;
                        continue;
                    }

                    if (TheTamSoSinh::la($the, $dkbd)) {
                        $tam++;
                        $xoa[] = $r->id;
                    }
                }

                if ($ghi && $xoa) {
                    $daXoa += DB::table((new check_hein_card)->getTable())->whereIn('id', $xoa)->delete();
                }
            });
        } catch (\Exception $e) {
            Log::error('the-bhyt:don-the-tam loi', ['loi' => $e->getMessage()]);
            $this->error('Loi: ' . $e->getMessage() . ' (lo da xoa van giu, chay lai duoc)');

            return 1;
        }

        $this->info('Xet: ' . $xet . ' | The tam: ' . $tam . ' | Khong thay tren HIS: ' . $khongThay);

        if ($ghi) {
            $this->info('Da xoa: ' . $daXoa);
        }

        return 0;
    }
}
```

- [ ] **Step 4: Chạy lại**

Run: `DB_HOST=127.0.0.1 php vendor/bin/phpunit tests/Unit/DonTheTamSoSinhTest.php`
Expected: `OK (2 tests, ...)`.

- [ ] **Step 5: Chạy thử chỉ đếm trên CSDL thật (không `--ghi`)**

Run: `php artisan the-bhyt:don-the-tam`
Expected: `The tam:` khoảng 1.149 (số liệu 24/09/2026). Lệnh không xoá gì. **Không chạy `--ghi` trên CSDL thật.**

- [ ] **Step 6: Commit**

```bash
git add app/Console/Commands/DonTheTamSoSinh.php tests/Unit/DonTheTamSoSinhTest.php
git commit -m "feat(the-bhyt): lenh the-bhyt:don-the-tam xoa ket qua loi cu cua the tam so sinh (mac dinh chi dem)

Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>"
```

---

### Task 8: Nghiệm thu tổng

**Files:** không sửa mã.

- [ ] **Step 1: Chạy các bộ test liên quan**

```bash
DB_HOST=127.0.0.1 php vendor/bin/phpunit tests/Unit/CheckHeinCardModelTest.php
DB_HOST=127.0.0.1 php vendor/bin/phpunit tests/Unit/JobKtTheBHYTGhiThongTinGuiTest.php
DB_HOST=127.0.0.1 php vendor/bin/phpunit tests/Unit/JobKtTheBHYTTheTamTest.php
DB_HOST=127.0.0.1 php vendor/bin/phpunit tests/Unit/JobKtTheBHYTLichSuKcbTest.php
DB_HOST=127.0.0.1 php vendor/bin/phpunit tests/Unit/TraLaiTheServiceTest.php
DB_HOST=127.0.0.1 php vendor/bin/phpunit tests/Unit/ManKetQuaTraCuuTheGiaoDienTest.php
DB_HOST=127.0.0.1 php vendor/bin/phpunit tests/Unit/BuThongTinGuiTheBhytTest.php
DB_HOST=127.0.0.1 php vendor/bin/phpunit tests/Unit/DonTheTamSoSinhTest.php
DB_HOST=127.0.0.1 php vendor/bin/phpunit tests/Unit/NhanMaTheTest.php
DB_HOST=127.0.0.1 php vendor/bin/phpunit tests/Feature/TraCuuLoiHoSoTest.php
DB_HOST=127.0.0.1 php vendor/bin/phpunit tests/Feature/KetQuaTraCuuTheTraLaiTest.php
DB_HOST=127.0.0.1 php vendor/bin/phpunit tests/Unit/Xml3176
```
Expected: tất cả `OK`, trừ các lỗi đã biết nêu trong Global Constraints (Xml3176: `Tests: N, Errors: 4`).

- [ ] **Step 2: Đối chiếu spec**

Mở spec, dò từng mục 4.1–4.8, xác nhận mỗi mục có commit tương ứng. Đặc biệt kiểm:
- `$fillable` có `gt_the_tumoi` / `gt_the_denmoi`;
- màn Tra cứu lỗi hồ sơ với thẻ tạm trả 200;
- ba cột `hien_*` có `orderable: false`.

- [ ] **Step 3: Push**

```bash
git push origin main
```

- [ ] **Step 4: Ghi hướng dẫn deploy vào báo cáo cho người dùng**

1. Deploy code, rồi `php artisan migrate`.
2. `php artisan config:cache` nếu prod cache cấu hình.
3. Khởi động lại worker hàng đợi `JobKtTheBHYT`.
4. `php artisan the-bhyt:bu-thong-tin-gui`: xem số, rồi thêm `--ghi`.
5. Sao lưu bảng `check_hein_cards`.
6. `php artisan the-bhyt:don-the-tam`: xem số, rồi thêm `--ghi`.
