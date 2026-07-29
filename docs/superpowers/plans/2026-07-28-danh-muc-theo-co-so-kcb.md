# Kế hoạch: danh mục theo cơ sở khám chữa bệnh

Spec: `docs/superpowers/specs/2026-07-28-danh-muc-theo-co-so-kcb-design.md`

**Mục tiêu:** hồ sơ của cơ sở nào được kiểm bằng danh mục dịch vụ / thuốc / vật tư của cơ sở
đó, ở cả XML3176 lẫn order-check.

**Kiến trúc:** dòng danh mục mang `ma_cskcb`; rỗng nghĩa là dùng chung mọi cơ sở. XML3176 lọc
bằng scope trên model; order-check lọc trong bộ nhớ ở `CatalogLookup` để giữ cam kết một truy
vấn cho cả phiếu.

**Thứ tự có chủ đích:** Task 1–2 sửa hai lỗi sẵn có ở khâu nhập liệu **trước**, vì không có
dữ liệu gắn mã cơ sở thì phần lọc ở Task 3–5 không kiểm chứng được.

## Ràng buộc chung

- Cổng: `vendor/bin/phpunit --testsuite Unit`. Chạy trước để ghi số nền.
- Bình luận mã nguồn viết tiếng Việt không dấu.
- Test dùng `/** @test */`.
- Test quét mã nguồn phải dùng trait `Tests\Support\LocComment`, nếu không sẽ đỗ giả.
- **Không đụng** QĐ130, `Xml2Checker`, `Xml3Checker` — ngoài phạm vi.
- **Không** viết migration sửa dữ liệu danh mục cũ.
- Không commit cho tới khi người dùng yêu cầu.

---

## Task 1: Cột `ma_cskcb` cho `service_catalogs`

**Tệp:**
- Tạo: `database/migrations/2026_07_28_130000_add_ma_cskcb_to_service_catalogs.php`
- Sửa: `app/Models/BHYT/ServiceCatalog.php`
- Test: `tests/Unit/DanhMucCoSoTest.php`

**Interfaces:**
- Produces: cột `service_catalogs.ma_cskcb`, `ServiceCatalog::$fillable` có `ma_cskcb`

Hai bảng kia đã có cột này; chỉ `service_catalogs` thiếu, khiến giá trị nhập vào bị bỏ im
lặng.

- [ ] **Bước 1: Viết test đỏ**

```php
<?php

namespace Tests\Unit;

use DB;
use Tests\TestCase;

class DanhMucCoSoTest extends TestCase
{
    /** @test */
    public function ba_danh_muc_deu_co_cot_ma_cskcb()
    {
        foreach (['service_catalogs', 'medicine_catalogs', 'medical_supply_catalogs'] as $bang) {
            $co = false;

            foreach (DB::select('SHOW COLUMNS FROM ' . $bang) as $c) {
                if ($c->Field === 'ma_cskcb') { $co = true; break; }
            }

            $this->assertTrue($co, "Bang $bang thieu cot ma_cskcb");
        }
    }

    /** @test */
    public function ba_model_deu_cho_ghi_ma_cskcb()
    {
        // ServiceCatalog truoc day khong co trong fillable nen gia tri nhap vao bi bo im lang.
        foreach ([
            \App\Models\BHYT\ServiceCatalog::class,
            \App\Models\BHYT\MedicineCatalog::class,
            \App\Models\BHYT\MedicalSupplyCatalog::class,
        ] as $lop) {
            $m = new $lop();

            $this->assertContains('ma_cskcb', $m->getFillable(), "$lop khong cho ghi ma_cskcb");
        }
    }
}
```

- [ ] **Bước 2: Chạy để xác nhận đỏ**

```bash
vendor/bin/phpunit tests/Unit/DanhMucCoSoTest.php
```

- [ ] **Bước 3: Viết migration**

```php
<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Them ma_cskcb cho service_catalogs.
 *
 * medicine_catalogs va medical_supply_catalogs da co cot nay tu truoc; rieng
 * service_catalogs thi khong, trong khi config/catalog_import_mapping.php VAN khai
 * 'ma_cskcb' cho danh muc dich vu - nen moi lan nhap, gia tri bi bo IM LANG.
 *
 * De nullable va KHONG dien gia tri cho dong cu: dong rong nghia la dung chung moi co so,
 * nho vay trien khai khong lam tat cac kiem tra danh muc dang chay.
 */
class AddMaCskcbToServiceCatalogs extends Migration
{
    public function up()
    {
        if (Schema::hasColumn('service_catalogs', 'ma_cskcb')) {
            return;
        }

        Schema::table('service_catalogs', function (Blueprint $t) {
            $t->string('ma_cskcb')->nullable()->after('ten_dich_vu');
            $t->index('ma_cskcb');
        });
    }

    public function down()
    {
        if (!Schema::hasColumn('service_catalogs', 'ma_cskcb')) {
            return;
        }

        Schema::table('service_catalogs', function (Blueprint $t) {
            $t->dropIndex(['ma_cskcb']);
            $t->dropColumn('ma_cskcb');
        });
    }
}
```

- [ ] **Bước 4: Thêm vào `fillable`**

Trong `app/Models/BHYT/ServiceCatalog.php`, thêm `'ma_cskcb',` vào mảng `$fillable`, ngay sau
`'ten_dich_vu'`.

- [ ] **Bước 5: Chạy migration và test**

```bash
php artisan migrate
```

```bash
vendor/bin/phpunit tests/Unit/DanhMucCoSoTest.php
```

---

## Task 2: Mã cơ sở vào khoá duy nhất khi nhập

**Tệp:**
- Sửa: `config/catalog_import_mapping.php`
- Test: `tests/Unit/DanhMucCoSoTest.php` (bổ sung ca)

**Interfaces:**
- Consumes: cột `ma_cskcb` (Task 1)

Không đưa mã cơ sở vào khoá thì nhập danh mục cơ sở thứ hai sẽ `updateOrCreate` **đè lên**
dòng của cơ sở thứ nhất.

`CatalogImportService` bỏ qua khoá có giá trị `null`, nên tệp Excel không có cột `MA_CSKCB`
vẫn giữ nguyên hành vi cũ — không cần thêm điều kiện nào.

- [ ] **Bước 1: Viết test đỏ, thêm vào `DanhMucCoSoTest`**

```php
    /** @test */
    public function ba_danh_muc_deu_co_ma_cskcb_trong_khoa_duy_nhat()
    {
        // Thieu khoa nay thi nhap danh muc co so thu hai se de len dong cua co so thu nhat.
        $cfg = config('catalog_import_mapping');

        foreach (['medicine', 'medical_supply', 'service'] as $loai) {
            $this->assertArrayHasKey($loai, $cfg, "Thieu cau hinh $loai");
            $this->assertContains('ma_cskcb', $cfg[$loai]['unique_keys'],
                "Danh muc $loai chua dua ma_cskcb vao unique_keys");
        }
    }

    /** @test */
    public function ba_danh_muc_deu_anh_xa_cot_ma_cskcb_tu_excel()
    {
        $cfg = config('catalog_import_mapping');

        foreach (['medicine', 'medical_supply', 'service'] as $loai) {
            $this->assertArrayHasKey('ma_cskcb', $cfg[$loai]['mapping'],
                "Danh muc $loai chua anh xa cot MA_CSKCB");
        }
    }
```

Đọc `config/catalog_import_mapping.php` để lấy **đúng khoá loại** (`medicine`,
`medical_supply`, `service` là phỏng đoán theo tên) trước khi chạy; sửa lại danh sách nếu
tên khác.

- [ ] **Bước 2: Chạy để xác nhận đỏ**

```bash
vendor/bin/phpunit tests/Unit/DanhMucCoSoTest.php
```

- [ ] **Bước 3: Thêm `ma_cskcb` vào `unique_keys`**

Ba chỗ trong `config/catalog_import_mapping.php`:

```php
// thuoc
'unique_keys' => ['ma_thuoc', 'ten_thuoc', 'ham_luong', 'so_dang_ky', 'don_gia_bh', 'tt_thau', 'tu_ngay', 'ma_cskcb'],

// vat tu
'unique_keys' => ['ma_vat_tu', 'ten_vat_tu', 'tt_thau', 'don_gia_bh', 'tu_ngay', 'ma_cskcb'],

// dich vu
'unique_keys' => ['ma_dich_vu', 'ten_dich_vu', 'don_gia', 'quy_trinh', 'tu_ngay', 'ma_cskcb'],
```

Đặt `ma_cskcb` ở **cuối** danh sách để không đổi thứ tự các khoá cũ.

- [ ] **Bước 4: Chạy lại, phải xanh**

```bash
vendor/bin/phpunit --testsuite Unit
```

---

## Task 3: Scope `cuaCoSo` và áp vào 8 chỗ tra của XML3176

**Tệp:**
- Sửa: `app/Models/BHYT/ServiceCatalog.php`
- Sửa: `app/Models/BHYT/MedicineCatalog.php`
- Sửa: `app/Models/BHYT/MedicalSupplyCatalog.php`
- Sửa: `app/Services/Xml3176Xml2Checker.php`
- Sửa: `app/Services/Xml3176Xml3Checker.php`
- Sửa: `app/Services/Xml3176Xml4Checker.php`
- Test: `tests/Unit/Xml3176/Xml3176DanhMucCoSoTest.php`

**Interfaces:**
- Produces: `scopeCuaCoSo($q, $maCskcb)` trên ba model

- [ ] **Bước 1: Viết test đỏ**

```php
<?php

namespace Tests\Unit\Xml3176;

use Tests\TestCase;
use Tests\Support\LocComment;

class Xml3176DanhMucCoSoTest extends TestCase
{
    use LocComment;

    /** @test */
    public function ba_model_danh_muc_deu_co_scope_cua_co_so()
    {
        foreach ([
            \App\Models\BHYT\ServiceCatalog::class,
            \App\Models\BHYT\MedicineCatalog::class,
            \App\Models\BHYT\MedicalSupplyCatalog::class,
        ] as $lop) {
            $this->assertTrue(method_exists($lop, 'scopeCuaCoSo'), "$lop thieu scope cuaCoSo");
        }
    }

    /** @test */
    public function scope_khop_dong_rong_va_dong_dung_co_so()
    {
        // Dong rong = dung chung moi co so. Day la dieu kien de trien khai khong lam tat
        // cac kiem tra danh muc dang chay tren may chu that.
        $sql = \App\Models\BHYT\ServiceCatalog::cuaCoSo('01929')->toSql();

        $this->assertContains('ma_cskcb', $sql);
        $this->assertContains('is null', strtolower($sql));
    }

    /** @test */
    public function tam_cho_tra_danh_muc_cua_xml3176_deu_loc_theo_co_so()
    {
        $canCo = [
            'Xml3176Xml2Checker' => 4,
            'Xml3176Xml3Checker' => 3,
            'Xml3176Xml4Checker' => 1,
        ];

        foreach ($canCo as $tep => $so) {
            $ma = $this->maKhongComment(app_path('Services/' . $tep . '.php'));

            $this->assertSame($so, substr_count($ma, 'cuaCoSo('),
                "$tep phai co dung $so cho loc theo co so");
        }
    }
}
```

Ca thứ ba đếm **cứng** số lần xuất hiện. Có chủ đích: thêm một chỗ tra danh mục mới mà quên
lọc cơ sở thì test đỏ, thay vì lọt im lặng.

- [ ] **Bước 2: Chạy để xác nhận đỏ**

```bash
vendor/bin/phpunit tests/Unit/Xml3176/Xml3176DanhMucCoSoTest.php
```

- [ ] **Bước 3: Thêm scope vào ba model**

Thêm y hệt vào cả ba tệp:

```php
    /**
     * Loc dong danh muc theo co so kham chua benh.
     *
     * Dong co ma_cskcb RONG (null hoac chuoi rong) dung chung cho MOI co so. Nho vay du
     * lieu danh muc cu - von chua gan ma co so - van tiep tuc chay, khong gay thoai lui
     * khi trien khai.
     *
     * @param string|null $maCskcb null = khong loc
     */
    public function scopeCuaCoSo($q, $maCskcb)
    {
        $maCskcb = trim((string) $maCskcb);

        if ($maCskcb === '') {
            return $q;
        }

        return $q->where(function ($w) use ($maCskcb) {
            $w->whereNull('ma_cskcb')
              ->orWhere('ma_cskcb', '')
              ->orWhere('ma_cskcb', $maCskcb);
        });
    }
```

- [ ] **Bước 4: Áp vào `Xml3176Xml2Checker` (4 chỗ)**

Đầu `checkDrugCatalog()` (hoặc hàm chứa các dòng 297–320), lấy mã cơ sở một lần:

```php
        $maCskcb = $data->Xml3176Xml2 ?? null;   // doc lai ten quan he thuc te truoc khi viet
```

**Đọc mã nguồn để lấy đúng tên quan hệ tới XML1.** `Xml3176Xml3Checker` dùng
`$data->Xml3176Xml1`. Nếu `Xml3176Xml2Checker` chưa `load('Xml3176Xml1')` thì thêm vào đầu
`checkErrors()` giống `Xml3176Xml3Checker:134`.

Rồi chèn `->cuaCoSo($maCskcb)` vào cả bốn truy vấn:

```php
$medicine = MedicineCatalog::cuaCoSo($maCskcb)->where('ma_thuoc', $data->ma_thuoc)
```

```php
if (!MedicineCatalog::cuaCoSo($maCskcb)->where('ma_thuoc', $data->ma_thuoc)->exists()) {
```

```php
} elseif (!MedicineCatalog::cuaCoSo($maCskcb)->where('ma_thuoc', $data->ma_thuoc)->where('ham_luong', $data->ham_luong)->exists()) {
```

```php
} elseif (!MedicineCatalog::cuaCoSo($maCskcb)->where('ma_thuoc', $data->ma_thuoc)->where('ham_luong', $data->ham_luong)->where('so_dang_ky', $data->so_dang_ky)->exists()) {
```

- [ ] **Bước 5: Áp vào `Xml3176Xml3Checker` (3 chỗ)**

Tệp này đã `load('Xml3176Xml1')`. Lấy mã cơ sở:

```php
$maCskcb = $data->Xml3176Xml1 ? $data->Xml3176Xml1->ma_cskcb : null;
```

Chèn vào ba truy vấn ở các dòng 747, 864, 877:

```php
$medicalSupplies = MedicalSupplyCatalog::cuaCoSo($maCskcb)->where('ma_vat_tu', $data->ma_vat_tu)->get();
```

```php
$serviceExists = ServiceCatalog::cuaCoSo($maCskcb)->where('ma_dich_vu', $data->ma_dich_vu)->exists();
```

```php
$validServiceExists = ServiceCatalog::cuaCoSo($maCskcb)->where('ma_dich_vu', $data->ma_dich_vu)
```

- [ ] **Bước 6: Áp vào `Xml3176Xml4Checker` (1 chỗ)**

Dòng 91. Kiểm tệp có nạp quan hệ XML1 chưa; chưa thì thêm.

- [ ] **Bước 7: Chạy lại, phải xanh**

```bash
vendor/bin/phpunit --testsuite Unit
```

---

## Task 4: `CatalogLookup` lọc theo cơ sở

**Tệp:**
- Sửa: `app/Services/OrderCheck/Support/CatalogLookup.php`
- Test: `tests/Unit/OrderCheck/CatalogLookupTest.php` (đã có, bổ sung ca)

**Interfaces:**
- Produces: tham số hàm dựng thứ bảy `$cotCoSo`; `sanSang($maCskcb = null)`,
  `coTrongDanhMuc($ma, $ngayYmd = null, $maCskcb = null)`,
  `tenTheoMa($ma, $ngayYmd = null, $maCskcb = null)`

Lọc **trong bộ nhớ**, không lọc trong SQL: một lô y lệnh có thể thuộc nhiều cơ sở, lọc trong
SQL thì mỗi cơ sở một truy vấn, phá vỡ cam kết một truy vấn cho cả phiếu.

`sanSang()` chuyển từ "bảng có dữ liệu không" sang "cơ sở này có dữ liệu không" nên phải nhớ
kết quả **theo từng cơ sở**.

- [ ] **Bước 1: Viết test đỏ, thêm vào `CatalogLookupTest`**

```php
    private function traCoSo(array $dong)
    {
        $lk = new CatalogLookup('medicine_catalogs', 'ma_thuoc', 'ten_thuoc',
            'tu_ngay', 'den_ngay', [], 'ma_cskcb');
        $lk->datSanChoTest([], $dong);

        return $lk;
    }

    /** @test */
    public function dong_rong_ma_co_so_dung_chung_moi_co_so()
    {
        $lk = $this->traCoSo(['A1' => [['ten' => 'X', 'tu' => '', 'den' => '', 'cs' => '']]]);

        $this->assertTrue($lk->coTrongDanhMuc('A1', null, '01929'));
        $this->assertTrue($lk->coTrongDanhMuc('A1', null, '37470'));
    }

    /** @test */
    public function dong_co_ma_co_so_chi_khop_dung_co_so_do()
    {
        $lk = $this->traCoSo(['A1' => [['ten' => 'X', 'tu' => '', 'den' => '', 'cs' => '01929']]]);

        $this->assertTrue($lk->coTrongDanhMuc('A1', null, '01929'));
        $this->assertFalse($lk->coTrongDanhMuc('A1', null, '37470'));
    }

    /** @test */
    public function khong_truyen_co_so_thi_khong_loc()
    {
        $lk = $this->traCoSo(['A1' => [['ten' => 'X', 'tu' => '', 'den' => '', 'cs' => '01929']]]);

        $this->assertTrue($lk->coTrongDanhMuc('A1'));
    }

    /** @test */
    public function ten_theo_ma_cung_loc_theo_co_so()
    {
        $lk = $this->traCoSo(['A1' => [
            ['ten' => 'Ten BM', 'tu' => '', 'den' => '', 'cs' => '01929'],
            ['ten' => 'Ten NB', 'tu' => '', 'den' => '', 'cs' => '37470'],
        ]]);

        $this->assertSame(['Ten BM'], $lk->tenTheoMa('A1', null, '01929'));
        $this->assertSame(['Ten NB'], $lk->tenTheoMa('A1', null, '37470'));
    }

    /** @test */
    public function bang_khong_co_khai_niem_co_so_thi_khong_loc()
    {
        // icd10_categories, medical_staffs khong co cot ma_cskcb.
        $lk = new CatalogLookup('icd10_categories', 'icd_code', null, null, null, [], null);
        $lk->datSanChoTest(['A00']);

        $this->assertTrue($lk->coTrongDanhMuc('A00', null, '01929'));
    }
```

- [ ] **Bước 2: Viết test đỏ cho `sanSang` theo cơ sở**

```php
    /** @test */
    public function san_sang_tinh_rieng_cho_tung_co_so()
    {
        DB::table('medicine_catalogs')->insert([
            ['ma_thuoc' => 'ZZTH1', 'ten_thuoc' => 'X', 'ma_cskcb' => '01929'],
        ]);

        try {
            $lk = new CatalogLookup('medicine_catalogs', 'ma_thuoc', 'ten_thuoc',
                'tu_ngay', 'den_ngay', [], 'ma_cskcb');

            $this->assertTrue($lk->sanSang('01929'));
            $this->assertFalse($lk->sanSang('37470'),
                'Co so chua nhap danh muc ma van bao san sang');
        } finally {
            DB::table('medicine_catalogs')->where('ma_thuoc', 'ZZTH1')->delete();
        }
    }

    /** @test */
    public function dong_dung_chung_lam_moi_co_so_san_sang()
    {
        DB::table('medicine_catalogs')->insert([
            ['ma_thuoc' => 'ZZTH2', 'ten_thuoc' => 'X', 'ma_cskcb' => null],
        ]);

        try {
            $lk = new CatalogLookup('medicine_catalogs', 'ma_thuoc', 'ten_thuoc',
                'tu_ngay', 'den_ngay', [], 'ma_cskcb');

            $this->assertTrue($lk->sanSang('37470'));
        } finally {
            DB::table('medicine_catalogs')->where('ma_thuoc', 'ZZTH2')->delete();
        }
    }
```

Chèn dữ liệu thật rồi dọn trong `finally` — bảng dùng chung với test khác. Mã `ZZTH1`/`ZZTH2`
không có trong danh mục thật.

- [ ] **Bước 3: Chạy để xác nhận đỏ**

```bash
vendor/bin/phpunit tests/Unit/OrderCheck/CatalogLookupTest.php
```

- [ ] **Bước 4: Sửa `CatalogLookup`**

Thêm thuộc tính và tham số hàm dựng thứ bảy:

```php
    /** @var string|null ten cot ma co so; null = bang khong co khai niem co so */
    protected $cotCoSo;

    /** @var array maCskcb => bool; sanSang tinh RIENG cho tung co so */
    protected $sanSangCoSo = [];
```

Hàm dựng nhận thêm `$cotCoSo = null` ở cuối.

`sanSang()`:

```php
    /**
     * Co so nay da co danh muc chua.
     *
     * Dong co ma co so RONG duoc tinh cho MOI co so - xem scopeCuaCoSo.
     */
    public function sanSang($maCskcb = null)
    {
        $maCskcb = trim((string) $maCskcb);

        if ($this->cotCoSo === null || $maCskcb === '') {
            if ($this->sanSang === null) {
                $this->sanSang = DB::table($this->bang)->where($this->dieuKien)->limit(1)->exists();
            }

            return $this->sanSang;
        }

        if (!array_key_exists($maCskcb, $this->sanSangCoSo)) {
            $cot = $this->cotCoSo;
            $this->sanSangCoSo[$maCskcb] = DB::table($this->bang)
                ->where($this->dieuKien)
                ->where(function ($w) use ($cot, $maCskcb) {
                    $w->whereNull($cot)->orWhere($cot, '')->orWhere($cot, $maCskcb);
                })
                ->limit(1)
                ->exists();
        }

        return $this->sanSangCoSo[$maCskcb];
    }
```

Trong `nap()`: thêm `$this->cotCoSo` vào danh sách cột chọn khi khác null, và lưu vào dòng:

```php
                'cs' => $this->cotCoSo === null ? null : $d[$this->cotCoSo],
```

`dongConHieuLuc()` nhận thêm `$maCskcb` và lọc:

```php
    protected function dongConHieuLuc($ma, $ngayYmd, $maCskcb = null)
    {
        $ma = trim((string) $ma);

        if ($ma === '' || !isset($this->dong[$ma])) {
            return [];
        }

        $cs = trim((string) $maCskcb);

        return array_values(array_filter($this->dong[$ma], function ($d) use ($ngayYmd, $cs) {
            if (!NgayHieuLuc::conHieuLuc($d['tu'], $d['den'], $ngayYmd)) {
                return false;
            }

            if ($cs === '') {
                return true;   // khong loc co so
            }

            $dongCs = trim((string) $d['cs']);

            // Dong rong dung chung moi co so.
            return $dongCs === '' || $dongCs === $cs;
        }));
    }
```

`coTrongDanhMuc()` và `tenTheoMa()` nhận thêm tham số thứ ba và chuyển xuống.

`datSanChoTest()` đọc thêm khoá `cs` của mỗi dòng, mặc định `null`. Đồng thời cho phép
`datRongChoTest()` xoá luôn `$this->sanSangCoSo`.

- [ ] **Bước 5: Chạy lại, phải xanh**

```bash
vendor/bin/phpunit --testsuite Unit
```

Bảy luật BHYT hiện có phải **không đổi hành vi** — chúng chưa truyền mã cơ sở.

---

## Task 5: `OrderContext` mang mã cơ sở, sáu luật danh mục dùng nó

**Tệp:**
- Sửa: `app/Services/OrderCheck/Support/OrderContext.php`
- Sửa: `app/Services/OrderCheck/HisOrderSource.php`
- Sửa: `app/Services/OrderCheck/RuleHandlers/Bhyt/BhytCatalogRule.php`
- Sửa: `app/Services/OrderCheck/RuleHandlers/Bhyt/BhytNameMismatchRule.php`
- Sửa: ba lớp con `Bhyt*CatalogRule` (khai `cotCoSo()`)
- Test: `tests/Unit/OrderCheck/BhytRuleTest.php`, `BhytNameRuleTest.php` (bổ sung ca)

**Interfaces:**
- Consumes: `CatalogLookup` có cơ sở (Task 4)
- Produces: `OrderContext::$maCskcb`

- [ ] **Bước 1: Viết test đỏ**

Thêm vào `BhytRuleTest`:

```php
    /** @test */
    public function chi_tra_danh_muc_cua_co_so_cua_ho_so()
    {
        $lk = new CatalogLookup('medicine_catalogs', 'ma_thuoc', 'ten_thuoc',
            'tu_ngay', 'den_ngay', [], 'ma_cskcb');
        $lk->datSanChoTest([], [
            'BH1' => [['ten' => 'Thuoc A', 'tu' => '', 'den' => '', 'cs' => '01929']],
        ]);

        $r = new BhytDrugCatalogRule($lk);

        $c1 = $this->ctx([$this->dv(1, 'TH1', 1, 'BH1', 6)]);
        $c1->maCskcb = '01929';
        $this->assertCount(0, $r->check($c1), 'Ma cua chinh co so minh ma van bao');

        $c2 = $this->ctx([$this->dv(1, 'TH1', 1, 'BH1', 6)]);
        $c2->maCskcb = '37470';
        $this->assertCount(1, $r->check($c2), 'Ma cua co so khac ma khong bao');
    }

    /** @test */
    public function dong_danh_muc_dung_chung_khop_moi_co_so()
    {
        $lk = new CatalogLookup('medicine_catalogs', 'ma_thuoc', 'ten_thuoc',
            'tu_ngay', 'den_ngay', [], 'ma_cskcb');
        $lk->datSanChoTest([], [
            'BH1' => [['ten' => 'Thuoc A', 'tu' => '', 'den' => '', 'cs' => '']],
        ]);

        $r = new BhytDrugCatalogRule($lk);

        $c = $this->ctx([$this->dv(1, 'TH1', 1, 'BH1', 6)]);
        $c->maCskcb = '37470';

        $this->assertCount(0, $r->check($c));
    }

    /** @test */
    public function phieu_khong_co_ma_co_so_thi_khong_loc()
    {
        $lk = new CatalogLookup('medicine_catalogs', 'ma_thuoc', 'ten_thuoc',
            'tu_ngay', 'den_ngay', [], 'ma_cskcb');
        $lk->datSanChoTest([], [
            'BH1' => [['ten' => 'Thuoc A', 'tu' => '', 'den' => '', 'cs' => '01929']],
        ]);

        $r = new BhytDrugCatalogRule($lk);

        $c = $this->ctx([$this->dv(1, 'TH1', 1, 'BH1', 6)]);
        $c->maCskcb = null;

        $this->assertCount(0, $r->check($c));
    }
```

Thêm ca tương ứng cho luật tên vào `BhytNameRuleTest`.

Thêm ca quét mã nguồn vào `BhytSeedTest`:

```php
    /** @test */
    public function his_order_source_lay_ma_co_so_kcb()
    {
        $ma = $this->maKhongComment(app_path('Services/OrderCheck/HisOrderSource.php'));

        $this->assertContains('his_branch', $ma);
        $this->assertContains('hein_medi_org_code', $ma);
        $this->assertContains('maCskcb', $ma);
    }
```

- [ ] **Bước 2: Chạy để xác nhận đỏ**

```bash
vendor/bin/phpunit tests/Unit/OrderCheck/
```

- [ ] **Bước 3: Thêm trường vào `OrderContext`**

```php
    /** @var string|null Ma CSKCB cua co so (his_branch.hein_medi_org_code theo branch cua ho so) */
    public $maCskcb;
```

- [ ] **Bước 4: Lấy mã cơ sở trong `HisOrderSource`**

`fetchServiceRequests()` đã join `his_treatment as t`. Thêm join và cột:

```php
            ->leftJoin('his_branch as br', 'br.id', '=', 't.branch_id')
```

Trong `selectRaw`, thêm:

```php
                br.hein_medi_org_code as ma_cskcb,
```

Trong `buildContext()`:

```php
        $c->maCskcb = $row->ma_cskcb;
```

Đọc chuỗi `selectRaw` hiện có rồi chèn cho đúng dấu phẩy.

- [ ] **Bước 5: Truyền mã cơ sở xuống `CatalogLookup`**

Trong `BhytCatalogRule`:

- Thêm `abstract protected function cotCoSo();` — ba lớp con danh mục trả `'ma_cskcb'`.
- Hàm dựng mặc định truyền `$this->cotCoSo()` làm tham số thứ bảy.
- `check()` gọi `sanSang($c->maCskcb)` thay vì `sanSang()`, và
  `coTrongDanhMuc($ma, $ngay, $c->maCskcb)`.

Trong `BhytNameMismatchRule::check()`: `sanSang($c->maCskcb)` và
`tenTheoMa($ma, $ngay, $c->maCskcb)`.

Sáu lớp con (`BhytServiceCatalogRule`, `BhytDrugCatalogRule`, `BhytSupplyCatalogRule` và ba
lớp tên) khai `cotCoSo()` trả `'ma_cskcb'`.

`BhytCodeMissingRule` không kế thừa `BhytCatalogRule` nên không đổi.

- [ ] **Bước 6: Chạy lại toàn bộ**

```bash
vendor/bin/phpunit --testsuite Unit
```

---

## Task 6: Kiểm trên HIS thật

- [ ] **Bước 1: Xác nhận mã cơ sở chảy tới `OrderContext`**

Viết script tạm trong thư mục scratchpad, không thêm tệp vào dự án: đọc một lô phiếu qua
`HisOrderSource`, đếm phân bố `maCskcb`.

Kỳ vọng: chỉ hai giá trị `01929` và `37470`, không có dòng rỗng.

- [ ] **Bước 2: Chạy lệnh đếm thử**

```bash
php artisan kiemtraylenh:thu --ngay=2 --lo=200
```

Không được ném ngoại lệ. Ba danh mục đang rỗng nên các luật danh mục vẫn im lặng — bước này
chỉ xác nhận không vỡ đường chạy.

- [ ] **Bước 3: Bộ Unit lần cuối**

```bash
vendor/bin/phpunit --testsuite Unit
```

Ghi lại số test trước và sau. Báo cho người dùng, **không commit** cho tới khi được yêu cầu.
