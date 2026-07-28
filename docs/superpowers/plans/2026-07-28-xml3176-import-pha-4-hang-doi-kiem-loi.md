# Import XML3176 — Giai đoạn 4: hàng đợi kiểm lỗi — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Một job kiểm lỗi cho mỗi *(hồ sơ, loại XML)* thay vì mỗi dòng, mỗi job tự idempotent, và ghi lỗi theo lô thay vì ba truy vấn mỗi lỗi.

**Architecture:** Bảng đăng ký 12 cặp *model ↔ checker*; job mới nạp dòng theo `ma_lk` rồi chạy checker; `Xml3176ErrorService` có chế độ gom để ghi theo lô mà **không sửa một dòng nào trong checker**.

**Tech Stack:** Laravel 5.5, PHP 7.4, PHPUnit 6.5, queue driver `database`.

## Global Constraints

- Spec: `docs/superpowers/specs/2026-07-28-xml3176-import-pha-4-hang-doi-kiem-loi-design.md`
- Cổng test: **`vendor/bin/phpunit --testsuite Unit`**. Mốc: **311 test xanh**.
- **Không sửa một dòng nào trong 12 lớp `Xml3176Xml*Checker`** — trừ đúng một việc ở Task 4: bỏ lời gọi `deleteErrors()` khỏi `Xml3176Xml1Checker`. Đó là ruột các luật giám định.
- **Kết quả kiểm lỗi phải không đổi**: cùng mã lỗi, cùng số lượng, cùng `stt`, cùng `critical_error`.
- Thứ tự task là bắt buộc: Task 1–3 chỉ **thêm** thứ chưa ai dùng, ứng dụng vẫn chạy đường cũ. Task 4 mới là lúc chuyển sang.
- `install_service.bat` **không sửa**: nó ánh xạ worker theo tên hàng đợi, và job mới dùng đúng `JobXml3176` như cũ.
- Comment mã nguồn viết tiếng Việt **không dấu**.
- Sau mỗi task: `php -l` file đã sửa, chạy suite Unit, commit.

---

### Task 1: Bảng đăng ký loại ↔ model ↔ checker

**Files:**
- Create: `app/Services/Xml3176/Xml3176CheckTypes.php`
- Create: `tests/Unit/Xml3176/Xml3176CheckTypesTest.php`

**Interfaces:**
- Produces:
  ```php
  Xml3176CheckTypes::LOAI                      // array<string, array{model:string, checker:string}>
  Xml3176CheckTypes::coChecker($loai): bool
  Xml3176CheckTypes::cauHinh($loai): array     // nem InvalidArgumentException neu khong co
  ```

- [ ] **Step 1: Viết test (sẽ đỏ)**

```php
<?php

namespace Tests\Unit\Xml3176;

use Tests\TestCase;
use App\Services\Xml3176\Xml3176CheckTypes;

class Xml3176CheckTypesTest extends TestCase
{
    /** @test */
    public function bang_dang_ky_phu_dung_12_loai_ma_job_cu_xu_ly()
    {
        // Dung 12 loai co checker. XML6, XML12, XML15 KHONG co checker - dieu do la co
        // san, khong phai thieu sot cua dot nay.
        $mongDoi = ['XML1', 'XML2', 'XML3', 'XML4', 'XML5', 'XML7',
                    'XML8', 'XML9', 'XML10', 'XML11', 'XML13', 'XML14'];

        $this->assertEquals($mongDoi, array_keys(Xml3176CheckTypes::LOAI));
    }

    /** @test */
    public function moi_lop_model_va_checker_deu_ton_tai()
    {
        foreach (Xml3176CheckTypes::LOAI as $loai => $ch) {
            $this->assertTrue(class_exists($ch['model']), "Thieu model cho $loai: {$ch['model']}");
            $this->assertTrue(class_exists($ch['checker']), "Thieu checker cho $loai: {$ch['checker']}");
            $this->assertTrue(
                method_exists($ch['checker'], 'checkErrors'),
                "{$ch['checker']} khong co checkErrors"
            );
        }
    }

    /** @test */
    public function co_checker_tu_choi_loai_ngoai_danh_sach()
    {
        $this->assertTrue(Xml3176CheckTypes::coChecker('XML2'));
        $this->assertFalse(Xml3176CheckTypes::coChecker('XML6'));
        $this->assertFalse(Xml3176CheckTypes::coChecker('XMLComplete'));
        $this->assertFalse(Xml3176CheckTypes::coChecker(''));
    }

    /** @test */
    public function cau_hinh_nem_loi_khi_loai_ngoai_danh_sach()
    {
        $this->expectException(\InvalidArgumentException::class);

        Xml3176CheckTypes::cauHinh('XML99');
    }
}
```

- [ ] **Step 2:** chạy test, xác nhận đỏ (`Class ... not found`).

- [ ] **Step 3: Viết lớp**

```php
<?php

namespace App\Services\Xml3176;

/**
 * Dang ky cac loai XML co checker, kem model tuong ung.
 *
 * Dung 12 loai ma CheckXml3176ErrorsJob (job cu, mot job moi DONG) tung xu ly.
 * XML6, XML12, XML15 khong co checker - dieu do la co san.
 */
class Xml3176CheckTypes
{
    const LOAI = [
        'XML1'  => ['model' => \App\Models\BHYT\Xml3176Xml1::class,  'checker' => \App\Services\Xml3176Xml1Checker::class],
        'XML2'  => ['model' => \App\Models\BHYT\Xml3176Xml2::class,  'checker' => \App\Services\Xml3176Xml2Checker::class],
        'XML3'  => ['model' => \App\Models\BHYT\Xml3176Xml3::class,  'checker' => \App\Services\Xml3176Xml3Checker::class],
        'XML4'  => ['model' => \App\Models\BHYT\Xml3176Xml4::class,  'checker' => \App\Services\Xml3176Xml4Checker::class],
        'XML5'  => ['model' => \App\Models\BHYT\Xml3176Xml5::class,  'checker' => \App\Services\Xml3176Xml5Checker::class],
        'XML7'  => ['model' => \App\Models\BHYT\Xml3176Xml7::class,  'checker' => \App\Services\Xml3176Xml7Checker::class],
        'XML8'  => ['model' => \App\Models\BHYT\Xml3176Xml8::class,  'checker' => \App\Services\Xml3176Xml8Checker::class],
        'XML9'  => ['model' => \App\Models\BHYT\Xml3176Xml9::class,  'checker' => \App\Services\Xml3176Xml9Checker::class],
        'XML10' => ['model' => \App\Models\BHYT\Xml3176Xml10::class, 'checker' => \App\Services\Xml3176Xml10Checker::class],
        'XML11' => ['model' => \App\Models\BHYT\Xml3176Xml11::class, 'checker' => \App\Services\Xml3176Xml11Checker::class],
        'XML13' => ['model' => \App\Models\BHYT\Xml3176Xml13::class, 'checker' => \App\Services\Xml3176Xml13Checker::class],
        'XML14' => ['model' => \App\Models\BHYT\Xml3176Xml14::class, 'checker' => \App\Services\Xml3176Xml14Checker::class],
    ];

    public static function coChecker($loai)
    {
        return is_string($loai) && array_key_exists($loai, self::LOAI);
    }

    public static function cauHinh($loai)
    {
        if (!self::coChecker($loai)) {
            throw new \InvalidArgumentException('Loai XML khong co checker: ' . $loai);
        }

        return self::LOAI[$loai];
    }
}
```

- [ ] **Step 4:** chạy test, xác nhận xanh (4 test).
- [ ] **Step 5:** `php -l`, chạy suite Unit — **315 test**.
- [ ] **Step 6: Commit**

```bash
git add app/Services/Xml3176/Xml3176CheckTypes.php tests/Unit/Xml3176/Xml3176CheckTypesTest.php
git commit -m "feat(xml3176): bang dang ky loai XML co checker kem model"
```

---

### Task 2: Chế độ gom của `Xml3176ErrorService`

Phần rủi ro nhất của đợt này nằm ở đây — ba cái bẫy đã ghi trong spec. Vì vậy toàn bộ phần
quyết định được tách thành **hàm thuần** và kiểm thử đầy đủ, còn phương thức có I/O chỉ
làm đúng việc gọi hàm thuần rồi ghi.

**Files:**
- Modify: `app/Services/Xml3176ErrorService.php`
- Create: `tests/Unit/Xml3176/Xml3176ErrorServiceGomTest.php`

**Interfaces:**
- Produces:
  ```php
  batDauGom(): void
  ketThucGom(): void
  dangGom(): bool
  soDongTrongBoDem(): int
  static chuanBiGhi(array $boDem, array $maBiTat, string $thoiDiem): array
      // tra ['nhom' => array<string, array<array>>, 'danhMuc' => array<array{xml,error_code,error_name,critical_error}>]
  ```

- [ ] **Step 1: Viết test cho hàm thuần (sẽ đỏ)**

```php
<?php

namespace Tests\Unit\Xml3176;

use Tests\TestCase;
use App\Services\Xml3176ErrorService;

class Xml3176ErrorServiceGomTest extends TestCase
{
    private function dong($xml, $ma, $stt, $code, $moTa = 'x', $critical = false, $them = [])
    {
        return [
            'xml' => $xml, 'ma_lk' => $ma, 'stt' => $stt,
            'error_code' => $code, 'description' => $moTa,
            'critical_error' => $critical,
            'error_name' => $code . '-ten',
            'them' => $them,
        ];
    }

    /** @test */
    public function loc_bo_cac_ma_bi_tat_kiem_tra()
    {
        $kq = Xml3176ErrorService::chuanBiGhi(
            [$this->dong('XML2', 'A', 1, 'E1'), $this->dong('XML2', 'A', 2, 'E2')],
            ['E2'],
            '2026-07-28 10:00:00'
        );

        $tatCa = call_user_func_array('array_merge', array_values($kq['nhom']));
        $this->assertCount(1, $tatCa);
        $this->assertEquals('E1', $tatCa[0]['error_code']);
    }

    /** @test */
    public function moi_dong_deu_co_dau_thoi_gian()
    {
        // insert() KHONG tu dien created_at/updated_at nhu create(), ma bang co index
        // tren ca hai cot - quen la du lieu sai va bo loc theo ngay hong theo.
        $kq = Xml3176ErrorService::chuanBiGhi([$this->dong('XML2', 'A', 1, 'E1')], [], '2026-07-28 10:00:00');

        $dong = array_values($kq['nhom'])[0][0];
        $this->assertEquals('2026-07-28 10:00:00', $dong['created_at']);
        $this->assertEquals('2026-07-28 10:00:00', $dong['updated_at']);
    }

    /** @test */
    public function gom_theo_bo_cot_khi_additional_data_khac_nhau()
    {
        // insert() nhieu dong lay ten cot tu DONG DAU TIEN - tron lan cac dong khac bo
        // cot se lech du lieu am tham.
        $kq = Xml3176ErrorService::chuanBiGhi(
            [
                $this->dong('XML2', 'A', 1, 'E1'),
                $this->dong('XML3', 'A', 1, 'E2', 'x', false, ['ngay_yl' => '202607011000']),
            ],
            [],
            '2026-07-28 10:00:00'
        );

        $this->assertCount(2, $kq['nhom'], 'Hai bo cot khac nhau phai thanh hai nhom');

        foreach ($kq['nhom'] as $nhom) {
            $bo = array_keys($nhom[0]);
            foreach ($nhom as $d) {
                $this->assertEquals($bo, array_keys($d), 'Trong mot nhom moi dong phai cung bo cot');
            }
        }
    }

    /** @test */
    public function danh_muc_chi_lay_cac_cap_khac_nhau()
    {
        // Ban cu goi updateOrCreate cho TUNG loi: 50 loi cung ma la 50 lan ghi y het nhau.
        $kq = Xml3176ErrorService::chuanBiGhi(
            [
                $this->dong('XML2', 'A', 1, 'E1'),
                $this->dong('XML2', 'A', 2, 'E1'),
                $this->dong('XML2', 'A', 3, 'E1'),
                $this->dong('XML3', 'A', 1, 'E1'),
            ],
            [],
            '2026-07-28 10:00:00'
        );

        $this->assertCount(2, $kq['danhMuc'], 'Chi con hai cap (xml, ma loi) khac nhau');
    }

    /** @test */
    public function bo_dem_rong_khong_no()
    {
        $kq = Xml3176ErrorService::chuanBiGhi([], [], '2026-07-28 10:00:00');

        $this->assertEquals([], $kq['nhom']);
        $this->assertEquals([], $kq['danhMuc']);
    }

    /** @test */
    public function cot_them_khong_lot_vao_ban_ghi_loi()
    {
        // Khoa 'them' va 'error_name' la du lieu noi bo cua bo dem, khong phai cot cua
        // bang xml3176_error_results.
        $kq = Xml3176ErrorService::chuanBiGhi(
            [$this->dong('XML2', 'A', 1, 'E1', 'x', false, ['ngay_yl' => '202607011000'])],
            [],
            '2026-07-28 10:00:00'
        );

        $dong = array_values($kq['nhom'])[0][0];
        $this->assertArrayNotHasKey('them', $dong);
        $this->assertArrayNotHasKey('error_name', $dong);
        $this->assertEquals('202607011000', $dong['ngay_yl']);
    }

    /** @test */
    public function che_do_gom_bat_tat_dung()
    {
        $svc = app(Xml3176ErrorService::class);

        $this->assertFalse($svc->dangGom());

        $svc->batDauGom();
        $this->assertTrue($svc->dangGom());
        $this->assertEquals(0, $svc->soDongTrongBoDem());

        $svc->saveErrors('XML2', 'A', 1, collect([
            (object) ['error_code' => 'E1', 'description' => 'x', 'critical_error' => false],
        ]));

        $this->assertEquals(1, $svc->soDongTrongBoDem(), 'Dang gom thi phai vao bo dem, khong ghi thang');

        $svc->ketThucGom();
        $this->assertFalse($svc->dangGom());
        $this->assertEquals(0, $svc->soDongTrongBoDem(), 'Bo dem phai duoc don sau khi ghi');
    }

    /** @test */
    public function ket_thuc_gom_hai_lan_khong_no()
    {
        $svc = app(Xml3176ErrorService::class);

        $svc->batDauGom();
        $svc->ketThucGom();
        $svc->ketThucGom();

        $this->assertFalse($svc->dangGom());
    }
}
```

- [ ] **Step 2:** chạy test, xác nhận đỏ.

- [ ] **Step 3: Viết hàm thuần `chuanBiGhi`**

Thêm vào `Xml3176ErrorService`:

```php
    /** Kich thuoc lo khi chen. */
    const CO_LO = 500;

    /** @var bool */
    private $dangGom = false;

    /** @var array */
    private $boDem = [];

    /**
     * Bien bo dem thanh du lieu san sang ghi.
     *
     * Tach rieng thanh ham THUAN vi ba cai bay deu nam o day:
     *   - insert() khong tu dien created_at/updated_at nhu create()
     *   - insert() nhieu dong lay ten cot tu DONG DAU TIEN, nen phai gom theo BO COT
     *   - danh muc chi can ghi mot lan cho moi cap (xml, ma loi) khac nhau
     *
     * @param array  $boDem   Cac phan tu co: xml, ma_lk, stt, error_code, description,
     *                        critical_error, error_name, them
     * @param array  $maBiTat Cac ma loi bi tat kiem tra
     * @param string $thoiDiem Dau thoi gian dung cho ca lo
     * @return array ['nhom' => array<string, array>, 'danhMuc' => array]
     */
    public static function chuanBiGhi(array $boDem, array $maBiTat, string $thoiDiem): array
    {
        $maBiTat = array_flip($maBiTat);
        $nhom = [];
        $danhMuc = [];

        foreach ($boDem as $d) {
            if (isset($maBiTat[$d['error_code']])) {
                continue;
            }

            $dong = [
                'xml'            => $d['xml'],
                'ma_lk'          => $d['ma_lk'],
                'stt'            => $d['stt'],
                'error_code'     => $d['error_code'],
                'description'    => $d['description'],
                'critical_error' => $d['critical_error'],
            ];

            if (!empty($d['them'])) {
                $dong = array_merge($dong, $d['them']);
            }

            $dong['created_at'] = $thoiDiem;
            $dong['updated_at'] = $thoiDiem;

            $khoaNhom = implode(',', array_keys($dong));
            $nhom[$khoaNhom][] = $dong;

            $khoaDanhMuc = $d['xml'] . '|' . $d['error_code'];

            if (!isset($danhMuc[$khoaDanhMuc])) {
                $danhMuc[$khoaDanhMuc] = [
                    'xml'            => $d['xml'],
                    'error_code'     => $d['error_code'],
                    'error_name'     => $d['error_name'],
                    'critical_error' => $d['critical_error'],
                ];
            }
        }

        return ['nhom' => $nhom, 'danhMuc' => array_values($danhMuc)];
    }
```

- [ ] **Step 4: Viết chế độ gom**

```php
    public function batDauGom(): void
    {
        $this->dangGom = true;
        $this->boDem = [];
    }

    public function dangGom(): bool
    {
        return $this->dangGom;
    }

    public function soDongTrongBoDem(): int
    {
        return count($this->boDem);
    }

    /**
     * Ghi toan bo bo dem roi tat che do gom.
     *
     * Goi hai lan lien tiep khong nem loi - job goi trong finally nen phai chiu duoc.
     */
    public function ketThucGom(): void
    {
        $boDem = $this->boDem;
        $this->boDem = [];
        $this->dangGom = false;

        if (empty($boDem)) {
            return;
        }

        $ma = array_values(array_unique(array_column($boDem, 'error_code')));

        // Mot truy van thay cho mot truy van moi loi.
        $maBiTat = Xml3176ErrorCatalog::whereIn('error_code', $ma)
            ->where('is_check', false)
            ->pluck('error_code')
            ->all();

        $sanSang = self::chuanBiGhi($boDem, $maBiTat, now()->toDateTimeString());

        foreach ($sanSang['nhom'] as $dsDong) {
            foreach (array_chunk($dsDong, self::CO_LO) as $lo) {
                Xml3176ErrorResult::insert($lo);
            }
        }

        foreach ($sanSang['danhMuc'] as $dm) {
            Xml3176ErrorCatalog::createOrUpdate(
                $dm['xml'], $dm['error_code'], $dm['error_name'], $dm['critical_error']
            );
        }
    }
```

- [ ] **Step 5: Cho `saveErrors` đẩy vào bộ đệm khi đang gom**

Thêm vào **đầu** vòng lặp trong `saveErrors()`, thay cho phần thân hiện tại khi đang gom.
Giữ nguyên hoàn toàn nhánh không gom:

```php
    public function saveErrors(string $xmlType, string $ma_lk, int $stt, Collection $errors,  array $additionalData = []): void
    {
        if ($this->dangGom) {
            foreach ($errors as $error) {
                $this->boDem[] = [
                    'xml'            => $xmlType,
                    'ma_lk'          => $ma_lk,
                    'stt'            => $stt,
                    'error_code'     => $error->error_code,
                    'description'    => $error->description,
                    'critical_error' => $error->critical_error ?? false,
                    'error_name'     => $error->error_name ?? null,
                    'them'           => $additionalData,
                ];
            }

            return;
        }

        // ... giu nguyen toan bo phan than cu tu day tro xuong ...
```

- [ ] **Step 6:** chạy test, xác nhận xanh (8 test).
- [ ] **Step 7:** `php -l`, chạy suite Unit — **323 test**.
- [ ] **Step 8: Commit**

```bash
git add app/Services/Xml3176ErrorService.php tests/Unit/Xml3176/Xml3176ErrorServiceGomTest.php
git commit -m "feat(xml3176): che do gom ghi loi theo lo cho Xml3176ErrorService"
```

---

### Task 3: Job kiểm lỗi theo *(hồ sơ, loại XML)*

**Files:**
- Create: `app/Jobs/CheckXml3176TypeJob.php`
- Create: `tests/Unit/Xml3176/CheckXml3176TypeJobTest.php`

**Interfaces:**
- Consumes: `Xml3176CheckTypes` (Task 1), `batDauGom`/`ketThucGom` (Task 2)
- Produces: `CheckXml3176TypeJob::dispatch($maLk, $xmlType)`

- [ ] **Step 1: Viết test (sẽ đỏ)**

```php
<?php

namespace Tests\Unit\Xml3176;

use Tests\TestCase;
use App\Jobs\CheckXml3176TypeJob;

class CheckXml3176TypeJobTest extends TestCase
{
    /** @test */
    public function job_ton_tai_va_nhan_hai_tham_so()
    {
        $job = new CheckXml3176TypeJob('MALK1', 'XML2');

        $this->assertInstanceOf(CheckXml3176TypeJob::class, $job);
    }

    /** @test */
    public function job_tu_xoa_loi_cua_rieng_loai_minh_truoc_khi_ghi()
    {
        // Day la thu lam moi job TU IDEMPOTENT: chay lai bao nhieu lan cung ra mot ket
        // qua, khong phu thuoc thu tu hang doi hay retry.
        $src = file_get_contents(app_path('Jobs/CheckXml3176TypeJob.php'));

        $this->assertContains("where('xml'", $src);
        $this->assertContains('delete()', $src);
    }

    /** @test */
    public function job_dung_che_do_gom_va_dong_lai_trong_finally()
    {
        // Hong giua chung thi phan da tim duoc van ghi, va khong ro bo dem sang job sau.
        $src = file_get_contents(app_path('Jobs/CheckXml3176TypeJob.php'));

        $this->assertContains('batDauGom', $src);
        $this->assertContains('ketThucGom', $src);
        $this->assertContains('finally', $src);
    }
}
```

- [ ] **Step 2:** chạy test, xác nhận đỏ.

- [ ] **Step 3: Viết job**

```php
<?php

namespace App\Jobs;

use App\Models\BHYT\Xml3176ErrorResult;
use App\Services\Xml3176ErrorService;
use App\Services\Xml3176\Xml3176CheckTypes;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

/**
 * Kiem loi MOT loai XML cua MOT ho so.
 *
 * Thay cho CheckXml3176ErrorsJob (mot job moi DONG): ho so 600 dong sinh 600 job, moi
 * job serialize ca mot model.
 *
 * Job TU XOA loi cua rieng loai minh truoc khi ghi, nen tu idempotent: chay lai bao
 * nhieu lan cung ra mot ket qua, khong phu thuoc thu tu hang doi hay retry.
 */
class CheckXml3176TypeJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** So dong nap moi lan, tranh giu ca bang trong bo nho. */
    const CO_LO = 500;

    protected $maLk;
    protected $xmlType;

    public function __construct($maLk, $xmlType)
    {
        $this->maLk = $maLk;
        $this->xmlType = $xmlType;
    }

    public function handle()
    {
        $cauHinh = Xml3176CheckTypes::cauHinh($this->xmlType);
        $model   = $cauHinh['model'];

        // Xoa loi CUA RIENG LOAI NAY. Khong dung deleteErrors() vi ham do xoa TOAN BO
        // loi cua ho so - dung no o day thi job nay se xoa mat ket qua cua 11 job kia.
        Xml3176ErrorResult::where('ma_lk', $this->maLk)
            ->where('xml', $this->xmlType)
            ->delete();

        $checker = app($cauHinh['checker']);
        $loi     = app(Xml3176ErrorService::class);

        $loi->batDauGom();

        try {
            $model::where('ma_lk', $this->maLk)
                ->chunk(self::CO_LO, function ($dong) use ($checker) {
                    foreach ($dong as $d) {
                        $checker->checkErrors($d);
                    }
                });
        } finally {
            // Hong giua chung thi phan da tim duoc van ghi, va bo dem khong ro sang job sau.
            $loi->ketThucGom();
        }
    }
}
```

**Lưu ý về `app(Xml3176ErrorService::class)`:** checker nhận service qua constructor
injection. Vì Laravel mặc định **không** dùng singleton cho lớp thường, `app()` ở đây có
thể trả về **một thực thể khác** với cái checker đang giữ — khi đó bộ đệm sẽ nằm ở thực
thể sai và `saveErrors` vẫn ghi thẳng. Step 4 xử lý việc này.

- [ ] **Step 4: Đăng ký `Xml3176ErrorService` là singleton**

Trong `app/Providers/AppServiceProvider.php`, phương thức `register()`:

```php
        // Che do gom cua Xml3176ErrorService chi hoat dong khi job va checker dung CHUNG
        // mot thuc the: bo dem la trang thai cua doi tuong.
        $this->app->singleton(\App\Services\Xml3176ErrorService::class);
```

- [ ] **Step 5: Viết test chứng minh dùng chung một thực thể**

Thêm vào `CheckXml3176TypeJobTest`:

```php
    /** @test */
    public function error_service_la_singleton_de_job_va_checker_dung_chung_bo_dem()
    {
        // Bo dem la trang thai cua doi tuong. Neu job va checker nhan hai thuc the khac
        // nhau thi che do gom im lang khong co tac dung, va so truy van van nhu cu.
        $a = app(\App\Services\Xml3176ErrorService::class);
        $b = app(\App\Services\Xml3176ErrorService::class);

        $this->assertSame($a, $b);
    }
```

- [ ] **Step 6:** chạy test, xác nhận xanh (4 test).
- [ ] **Step 7:** `php -l` hai file, chạy suite Unit — **327 test**.
- [ ] **Step 8: Commit**

```bash
git add app/Jobs/CheckXml3176TypeJob.php app/Providers/AppServiceProvider.php tests/Unit/Xml3176/CheckXml3176TypeJobTest.php
git commit -m "feat(xml3176): job kiem loi theo (ho so, loai XML), tu idempotent"
```

---

### Task 4: Chuyển sang đường mới và xoá job cũ

**Files:**
- Modify: `app/Services/Xml3176Service.php` (bỏ 12 dispatch, thêm xoá lỗi vào `deleteExistingXml3176`)
- Modify: `app/Services/Xml3176Xml1Checker.php` (bỏ `deleteErrors`)
- Modify: `app/Services/Xml3176/Xml3176Importer.php` (dispatch sau commit)
- Delete: `app/Jobs/CheckXml3176ErrorsJob.php`
- Create: `tests/Unit/Xml3176/Xml3176ChuyenDoiJobTest.php`

**Interfaces:**
- Consumes: `CheckXml3176TypeJob` (Task 3), `Xml3176CheckTypes` (Task 1)

- [ ] **Step 1: Viết hàng rào (sẽ đỏ)**

```php
<?php

namespace Tests\Unit\Xml3176;

use Tests\TestCase;

class Xml3176ChuyenDoiJobTest extends TestCase
{
    /** @test */
    public function khong_con_dispatch_theo_tung_dong()
    {
        $src = file_get_contents(app_path('Services/Xml3176Service.php'));

        $this->assertNotContains('CheckXml3176ErrorsJob', $src,
            'Van con dispatch mot job moi dong');
    }

    /** @test */
    public function khong_con_cho_nao_nhac_toi_job_cu()
    {
        $this->assertFileNotExists(app_path('Jobs/CheckXml3176ErrorsJob.php'));

        foreach ([
            app_path('Services/Xml3176Service.php'),
            app_path('Services/Xml3176/Xml3176Importer.php'),
        ] as $file) {
            $this->assertNotContains('CheckXml3176ErrorsJob', file_get_contents($file),
                basename($file) . ' con nhac toi lop da xoa');
        }
    }

    /** @test */
    public function xoa_du_lieu_ho_so_thi_xoa_ca_loi()
    {
        // deleteExistingXml3176 truoc day KHONG xoa loi, trong khi deleteXml3176XmlAndError
        // thi co - mot diem bat nhat co san. Nay dong lai de nhap lai khong con sot loi
        // cua loai XML khong con xuat hien.
        $src = file_get_contents(app_path('Services/Xml3176Service.php'));

        $dau = strpos($src, 'function deleteExistingXml3176');
        $this->assertNotFalse($dau);

        $than = substr($src, $dau, 1600);
        $this->assertContains('Xml3176ErrorResult', $than);
    }

    /** @test */
    public function checker_xml1_khong_con_tu_xoa_loi()
    {
        // deleteErrors() xoa TOAN BO loi cua ho so. Nam trong job XML1 thi mot lan retry
        // se xoa sach ket qua ma 11 job kia vua tim ra.
        $src = file_get_contents(app_path('Services/Xml3176Xml1Checker.php'));

        $this->assertNotContains('deleteErrors', $src);
    }

    /** @test */
    public function importer_dispatch_job_theo_loai_sau_commit()
    {
        $src = file_get_contents(app_path('Services/Xml3176/Xml3176Importer.php'));

        $viTriTransaction = strpos($src, 'DB::transaction');
        $viTriDispatch    = strpos($src, 'CheckXml3176TypeJob');

        $this->assertNotFalse($viTriDispatch, 'Importer chua dispatch job theo loai');
        $this->assertGreaterThan($viTriTransaction, $viTriDispatch,
            'Phai dispatch SAU khoi transaction');

        // Kiem loai truoc, kiem tong the sau - giu dung thu tu FIFO hien nay.
        $this->assertLessThan(strpos($src, 'checkXml3176Complete'), $viTriDispatch);
    }
}
```

- [ ] **Step 2:** chạy test, xác nhận đỏ cả 5.

- [ ] **Step 3: Bỏ 12 dispatch khỏi `Xml3176Service`**

Mỗi chỗ là một khối ba dòng đồng dạng:

```php
                    //Đẩy công việc kiểm tra vào hàng đợi
                    CheckXml3176ErrorsJob::dispatch($xmlN, $xmlType)
                    ->onQueue($this->queueName);
```

Xoá cả ba dòng ở 12 chỗ, và xoá dòng `use App\Jobs\CheckXml3176ErrorsJob;` ở đầu file.

Sau khi xoá, biến `$xmlN` nhận kết quả `updateOrCreate` có thể không còn ai dùng — **giữ
nguyên phép gán**, không dọn thêm, để diff chỉ chứa đúng việc cần làm.

- [ ] **Step 4: `deleteExistingXml3176` xoá cả lỗi**

Thêm vào cuối thân hàm, trước dấu đóng:

```php
        // Loi thuoc ve ho so: xoa du lieu thi xoa loi. Truoc day ham nay KHONG xoa loi
        // trong khi deleteXml3176XmlAndError thi co - mot diem bat nhat co san.
        Xml3176ErrorResult::where('ma_lk', $ma_lk)->delete();
```

`Xml3176ErrorResult` đã có trong khối `use` của file.

- [ ] **Step 5: Bỏ `deleteErrors` khỏi `Xml3176Xml1Checker`**

Xoá hai dòng:

```php
        // Delete errors to xml_error_checks table
        $this->xmlErrorService->deleteErrors($data->ma_lk);
```

Đây là **thay đổi duy nhất** được phép trong 12 lớp checker.

- [ ] **Step 6: Importer dispatch job theo loại sau commit**

Trong `Xml3176Importer`, thêm `use App\Jobs\CheckXml3176TypeJob;` và chèn **ngay sau** khối
`try/catch` của transaction, **trước** `checkXml3176Complete`:

```php
        // Mot job cho moi loai da xu ly, thay vi mot job moi dong. Dat sau commit de
        // job khong tro toi du lieu chua ton tai.
        foreach (array_unique($processedFileTypes) as $loai) {
            if (Xml3176CheckTypes::coChecker($loai)) {
                CheckXml3176TypeJob::dispatch($ma_lk, $loai)
                    ->onQueue(config('xml3176.queue_name'));
            }
        }
```

Thêm `use App\Services\Xml3176\Xml3176CheckTypes;` — cùng namespace nên chỉ cần tên lớp,
nhưng khai báo tường minh cho dễ đọc.

- [ ] **Step 7: Xoá lớp job cũ**

```bash
git rm app/Jobs/CheckXml3176ErrorsJob.php
```

- [ ] **Step 8: Quét toàn bộ mã nguồn tìm chỗ sót**

Run: `grep -rn "CheckXml3176ErrorsJob" app/ config/ routes/ resources/ database/`
Expected: **không có kết quả**

- [ ] **Step 9:** chạy test, xác nhận xanh (5 test).
- [ ] **Step 10:** `php -l` ba file, chạy suite Unit — **332 test**.
- [ ] **Step 11: Commit**

```bash
git add -A app/ tests/
git commit -m "perf(xml3176): mot job kiem loi moi (ho so, loai XML) thay vi moi dong"
```

---

## Nghiệm thu thủ công (bắt buộc)

DB dev trống cả bốn bảng `xml3176_*`.

**Chuẩn bị: ghi lại danh sách lỗi của một hồ sơ (mã lỗi + số lượng + `stt`) trước khi thử.**
Không có nó thì mục 1 không kiểm được, mà mục 1 là mục quan trọng nhất.

### Điều kiện tiên quyết khi triển khai

Job cũ đã bị xoá. Trước khi deploy **bắt buộc**:

1. Dừng dịch vụ `QLBV XMLImport3176`, không tải file qua giao diện.
2. Chờ `SELECT COUNT(*) FROM jobs WHERE queue = 'JobXml3176'` về 0.
3. Deploy.
4. Bật lại dịch vụ.

Bỏ qua bước này là các job cũ còn trong hàng đợi **không unserialize được** và chết hàng
loạt, mất kết quả kiểm lỗi của những hồ sơ vừa nhập.

`install_service.bat` **không cần sửa** — nó ánh xạ worker theo tên hàng đợi, và job mới
dùng đúng `JobXml3176` như cũ.

| # | Việc | Mong đợi |
|---|---|---|
| 1 | Nhập lại một hồ sơ đã có lỗi | Danh sách lỗi **giống hệt trước**: cùng mã, cùng số lượng, cùng `stt`, cùng `critical_error` |
| 2 | Đếm `jobs` ngay sau khi nhập một hồ sơ nhiều dòng | Vài job thay vì hàng trăm |
| 3 | `created_at` của các dòng lỗi mới | Có giá trị, không rỗng |
| 4 | Nhập hồ sơ mà lần trước có lỗi XML3, lần này file không còn phần XML3 | Lỗi XML3 cũ **biến mất** |
| 5 | Bảng `xml3176_error_catalogs` | Không sinh dòng trùng; nội dung như trước |
| 6 | Lọc danh sách hồ sơ theo mã lỗi | Vẫn ra đúng hồ sơ như trước |
| 7 | Bảng `failed_jobs` sau khi deploy | Trống, không có lỗi thiếu lớp |
| 8 | Hồ sơ có lỗi tổng thể (`XMLComplete`) | Vẫn còn sau khi các job theo loại chạy xong |

**Mục 1 và mục 8** là hai mục dễ trôi nhất. Mục 1 vì cả ba thay đổi đều đụng đường ghi lỗi.
Mục 8 vì nó chứng minh việc xoá theo loại không lấn sang lỗi tổng thể.

## Ngoài phạm vi, ghi nhận

1. **Checker tra danh mục cho từng dòng** — 18 chỗ, tập trung ở XML3 (10), XML2 (5), XML4 (3). Xứng đáng một giai đoạn riêng với ràng buộc "chỉ đổi nguồn tra cứu, không đổi điều kiện" và test đối chiếu trên dữ liệu thật.
2. **Nút "Kiểm tra lại" một hồ sơ** — cấu trúc mới làm việc này gần như miễn phí, nhưng là tính năng mới.
3. Các nợ đã ghi ở các đợt trước.
