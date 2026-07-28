# Kế hoạch: order-check đối chiếu ICD, ICD YHCT và CCHN

Spec: `docs/superpowers/specs/2026-07-28-order-check-icd-va-cchn-design.md`

**Mục tiêu:** ba luật mới cho order-check — mã bệnh ICD10, mã bệnh YHCT, và CCHN nhân viên
y tế — đều đối chiếu với danh mục trong ứng dụng.

**Kiến trúc:** giữ nguyên khung `Scanner` + `RuleHandler`. Hai luật ICD dùng chung một lớp
trừu tượng `IcdCatalogRule` (khuôn giống `BhytCatalogRule` đã có). Luật CCHN đứng riêng vì
tra hai cột khoá và có lọc hiệu lực. `CatalogLookup` được mở rộng hai điểm nhỏ để phục vụ
bảng không có cột ngày và bảng cần điều kiện `is_active`.

**Công nghệ:** Laravel 5.5, PHP 7.4, PHPUnit 6.5.

## Ràng buộc chung

- Cổng kiểm thử: `vendor/bin/phpunit --testsuite Unit`. Bộ `tests/Feature` đỏ sẵn vì lý do
  môi trường, **không** dùng làm cổng. Chạy trước khi bắt đầu để ghi lại số nền.
- Bình luận trong mã nguồn viết **tiếng Việt không dấu** (lệ của module này).
- Test dùng chú thích `/** @test */`, không dùng tiền tố `test_` — theo các tệp hiện có
  trong `tests/Unit/OrderCheck/`.
- Test quét mã nguồn phải dùng trait `Tests\Support\LocComment` để bỏ comment trước khi tìm
  chuỗi, nếu không sẽ **đỗ giả**.
- `Violation` có các thuộc tính `ruleCode`, `orderRefType`, `orderRefId`, `message`,
  `detail`, `subKey` — **không** phải `code`/`targetId`.
- Ba luật seed `is_active = false`.
- Ba luật ở **cấp phiếu**: `orderRefType = 'service_req'`, `orderRefId = serviceReqId`.
  Không lọc theo đối tượng BHYT.
- Tách chuỗi mã bệnh phải **bỏ phần tử rỗng** — `icd_sub_code` có dấu `;` dẫn đầu.
- Không commit cho tới khi người dùng yêu cầu.

---

## Task 1: `CatalogLookup` nhận bảng không có cột ngày và điều kiện lọc

**Tệp:**
- Sửa: `app/Services/OrderCheck/Support/CatalogLookup.php`
- Test: `tests/Unit/OrderCheck/CatalogLookupTest.php` (đã có, bổ sung ca)

**Interfaces:**
- Consumes: không
- Produces: `__construct($bang, $cot, $cotTen = null, $cotTu = 'tu_ngay', $cotDen = 'den_ngay', array $dieuKien = [])`,
  `datRongChoTest()`

Hai bảng ICD không có `tu_ngay`/`den_ngay` và cần lọc `is_active = 1`. Bảy luật BHYT đang
dùng lớp này phải **không đổi hành vi**.

**Bẫy đã kiểm chứng:** trên cơ sở dữ liệu này `icd10_categories` có **12.229 dòng** và
`icd_yhct_categories` có **4.144 dòng**. Nên **không** được viết test "danh mục rỗng" bằng
cách dựng `CatalogLookup` trỏ thẳng vào hai bảng đó rồi trông chờ `sanSang()` trả false —
nó sẽ trả true và test đỏ. Đó là lý do task này thêm `datRongChoTest()`: đối xứng với
`datSanChoTest()`, đặt thẳng trạng thái vào bộ nhớ thay vì phụ thuộc nội dung bảng.

`medical_staffs` hiện 0 dòng, nhưng cũng dùng `datRongChoTest()` — để test không vỡ vào
ngày đơn vị nạp danh mục.

- [ ] **Bước 1: Viết test đỏ, thêm vào cuối `CatalogLookupTest`**

```php
    /** @test */
    public function bang_khong_co_cot_ngay_thi_khong_loc_hieu_luc()
    {
        // icd10_categories khong co tu_ngay/den_ngay.
        $lk = new CatalogLookup('icd10_categories', 'icd_code', null, null, null);
        $lk->datSanChoTest(['A00']);

        $this->assertTrue($lk->coTrongDanhMuc('A00', 20240601));
        $this->assertTrue($lk->coTrongDanhMuc('A00', 19990101));
    }

    /** @test */
    public function dat_rong_cho_test_lam_san_sang_tra_false()
    {
        // Khong duoc dua vao noi dung bang that: icd10_categories dang co 12.229 dong.
        $lk = new CatalogLookup('icd10_categories', 'icd_code', null, null, null, ['is_active' => 1]);
        $lk->datRongChoTest();

        $this->assertFalse($lk->sanSang());
        $this->assertFalse($lk->coTrongDanhMuc('A00'));
    }
```

- [ ] **Bước 2: Bổ sung ca kiểm điều kiện lọc, dùng dữ liệu thật**

Hai ca dưới chèn dòng thật rồi dọn trong `finally`, vì `$dieuKien` chỉ có ý nghĩa khi chạm
cơ sở dữ liệu. Bảng dùng chung với test khác nên **bắt buộc** dọn kể cả khi khẳng định
thất bại. Mã `ZZ1`/`ZZ2` không tồn tại trong danh mục thật.

```php
    /** @test */
    public function dieu_kien_loc_duoc_ap_trong_san_sang()
    {
        // Bang co dong nhung KHONG dong nao thoa dieu kien -> PHAI tra false. Neu khong,
        // moi ma se thanh vi pham.
        DB::table('icd10_categories')->insert([
            ['icd_code' => 'ZZ1', 'icd_name' => 'Tat', 'is_active' => 0],
        ]);

        try {
            $lk = new CatalogLookup('icd_yhct_categories', 'icd_code', null, null, null, ['is_active' => 9]);

            $this->assertFalse($lk->sanSang(),
                'Dieu kien khong duoc ap trong sanSang');
        } finally {
            DB::table('icd10_categories')->where('icd_code', 'ZZ1')->delete();
        }
    }

    /** @test */
    public function dieu_kien_loc_duoc_ap_khi_nap()
    {
        DB::table('icd10_categories')->insert([
            ['icd_code' => 'ZZ1', 'icd_name' => 'Tat', 'is_active' => 0],
            ['icd_code' => 'ZZ2', 'icd_name' => 'Bat', 'is_active' => 1],
        ]);

        try {
            $lk = new CatalogLookup('icd10_categories', 'icd_code', null, null, null, ['is_active' => 1]);
            $lk->nap(['ZZ1', 'ZZ2']);

            $this->assertFalse($lk->coTrongDanhMuc('ZZ1'), 'Dong is_active=0 van duoc coi la co');
            $this->assertTrue($lk->coTrongDanhMuc('ZZ2'));
        } finally {
            DB::table('icd10_categories')->whereIn('icd_code', ['ZZ1', 'ZZ2'])->delete();
        }
    }
```

Thêm `use DB;` vào đầu tệp test nếu chưa có.

Ca `dieu_kien_loc_duoc_ap_trong_san_sang` dùng `['is_active' => 9]` — một giá trị không
dòng nào thoả — thay vì trông chờ bảng rỗng. Đây là cách kiểm `$dieuKien` thật sự được đưa
vào câu truy vấn mà không phụ thuộc nội dung bảng. Chèn không cần `created_at`/`updated_at`
(đã kiểm: hai cột nhận null).

- [ ] **Bước 3: Chạy để xác nhận đỏ**

```bash
vendor/bin/phpunit tests/Unit/OrderCheck/CatalogLookupTest.php
```

Kỳ vọng: đỏ ở các ca mới. Ca cũ vẫn xanh.

- [ ] **Bước 4: Sửa `CatalogLookup`**

Đổi hàm dựng và ba chỗ dùng cột ngày:

```php
    protected $dieuKien;

    public function __construct(
        $bang,
        $cot,
        $cotTen = null,
        $cotTu = 'tu_ngay',
        $cotDen = 'den_ngay',
        array $dieuKien = []
    ) {
        $this->bang = $bang;
        $this->cot = $cot;
        $this->cotTen = $cotTen;
        $this->cotTu = $cotTu;
        $this->cotDen = $cotDen;
        $this->dieuKien = $dieuKien;
    }

    public function sanSang()
    {
        if ($this->sanSang === null) {
            // Dieu kien PHAI duoc ap o day: bang co 12.229 dong nhung tat ca is_active = 0
            // thi van la "chua co danh muc", khong the de moi ma thanh vi pham.
            $this->sanSang = DB::table($this->bang)
                ->where($this->dieuKien)
                ->limit(1)
                ->exists();
        }

        return $this->sanSang;
    }
```

Trong `nap()`:

```php
        $chon = [$this->cot];

        if ($this->cotTu !== null) {
            $chon[] = $this->cotTu;
        }

        if ($this->cotDen !== null) {
            $chon[] = $this->cotDen;
        }

        if ($this->cotTen !== null) {
            $chon[] = $this->cotTen;
        }

        $thay = DB::table($this->bang)
            ->whereIn($this->cot, $ma)
            ->where($this->dieuKien)
            ->select($chon)
            ->get();
```

và khi dựng dòng:

```php
            $this->dong[$khoa][] = [
                'ten' => $this->cotTen === null ? null : trim((string) $d[$this->cotTen]),
                'tu' => $this->cotTu === null ? null : $d[$this->cotTu],
                'den' => $this->cotDen === null ? null : $d[$this->cotDen],
            ];
```

`NgayHieuLuc::conHieuLuc(null, null, $ngay)` đã trả `true` nên không cần sửa
`dongConHieuLuc()`.

Thêm phương thức đối xứng với `datSanChoTest()`:

```php
    /**
     * Chi dung trong test: ep trang thai "danh muc chua nap" ma khong phu thuoc noi dung
     * bang. Can thiet vi icd10_categories tren DB that dang co 12.229 dong.
     */
    public function datRongChoTest()
    {
        $this->dong = [];
        $this->sanSang = false;
    }
```

- [ ] **Bước 5: Chạy lại toàn bộ bộ Unit**

```bash
vendor/bin/phpunit --testsuite Unit
```

Toàn bộ phải xanh — đặc biệt bảy luật BHYT không được đổi hành vi.

---

## Task 2: `OrderContext` và `HisOrderSource` mang thêm 4 trường

**Tệp:**
- Sửa: `app/Services/OrderCheck/Support/OrderContext.php`
- Sửa: `app/Services/OrderCheck/HisOrderSource.php`
- Test: `tests/Unit/OrderCheck/IcdCatalogRuleTest.php` (tạo ở Task 4, phần quét mã nguồn đặt tạm ở đây)

**Interfaces:**
- Consumes: không
- Produces: `OrderContext::$icdSubCode`, `$traditionalIcdCode`, `$traditionalIcdSubCode`, `$requestDiploma`

- [ ] **Bước 1: Viết test đỏ**

Tạo `tests/Unit/OrderCheck/OrderSourceIcdCchnTest.php`:

```php
<?php

namespace Tests\Unit\OrderCheck;

use Tests\TestCase;
use Tests\Support\LocComment;
use App\Services\OrderCheck\Support\OrderContext;

class OrderSourceIcdCchnTest extends TestCase
{
    use LocComment;

    /** @test */
    public function order_context_co_bon_truong_moi()
    {
        $c = new OrderContext();

        foreach (['icdSubCode', 'traditionalIcdCode', 'traditionalIcdSubCode', 'requestDiploma'] as $t) {
            $this->assertTrue(property_exists($c, $t), "Thieu thuoc tinh $t");
        }
    }

    /** @test */
    public function his_order_source_lay_ba_cot_icd_va_cchn_bac_si_chi_dinh()
    {
        $ma = $this->maKhongComment(app_path('Services/OrderCheck/HisOrderSource.php'));

        foreach ([
            'sr.icd_sub_code',
            'sr.traditional_icd_code',
            'sr.traditional_icd_sub_code',
        ] as $cot) {
            $this->assertContains($cot, $ma, "Chua select $cot");
        }

        // CCHN bac si chi dinh phai join rieng, khong dung chung alias voi nguoi thuc hien.
        $this->assertContains('request_loginname', $ma);
        $this->assertContains('request_diploma', $ma);
        $this->assertContains('requestDiploma', $ma);
    }
}
```

- [ ] **Bước 2: Chạy để xác nhận đỏ**

```bash
vendor/bin/phpunit tests/Unit/OrderCheck/OrderSourceIcdCchnTest.php
```

- [ ] **Bước 3: Thêm bốn thuộc tính vào `OrderContext`**

Thêm ngay sau `public $icdCode;`:

```php
    /** @var string|null Chan doan phu, chuoi nhieu ma ngan boi ';' va CO dau ';' dan dau */
    public $icdSubCode;

    /** @var string|null Chan doan YHCT chinh */
    public $traditionalIcdCode;

    /** @var string|null Chan doan YHCT phu, cung quy uoc chuoi ghep */
    public $traditionalIcdSubCode;

    /** @var string|null CCHN cua bac si CHI DINH (execute_diploma la cua nguoi thuc hien) */
    public $requestDiploma;
```

- [ ] **Bước 4: Sửa `HisOrderSource::fetchServiceRequests()`**

Thêm join thứ hai cho bác sĩ chỉ định. Alias hiện có là `e` cho `execute_loginname`, dùng
`re` cho `request_loginname`:

```php
            ->leftJoin('his_employee as e', 'sr.execute_loginname', '=', 'e.loginname')
            ->leftJoin('his_employee as re', 'sr.request_loginname', '=', 're.loginname')
```

và trong `selectRaw`, thêm vào sau `sr.icd_code, sr.icd_name,`:

```php
                sr.icd_sub_code, sr.traditional_icd_code, sr.traditional_icd_sub_code,
```

và ở cuối chuỗi select, sau `e.diploma as execute_diploma`:

```php
                , re.diploma as request_diploma
```

Chú ý dấu phẩy: đọc chuỗi `selectRaw` hiện có rồi chèn cho đúng, đừng chép nguyên đoạn
trên nếu nó tạo ra dấu phẩy thừa.

- [ ] **Bước 5: Gán trong `buildContext()`**

Thêm ngay sau `$c->icdCode = $row->icd_code;`:

```php
        $c->icdSubCode = $row->icd_sub_code;
        $c->traditionalIcdCode = $row->traditional_icd_code;
        $c->traditionalIcdSubCode = $row->traditional_icd_sub_code;
        $c->requestDiploma = $row->request_diploma;
```

- [ ] **Bước 6: Chạy lại và kiểm trên HIS thật**

```bash
vendor/bin/phpunit --testsuite Unit
```

Rồi kiểm câu truy vấn chạy được trên Oracle và bốn trường có dữ liệu:

```bash
php artisan kiemtraylenh:thu --ngay=2 --lo=50
```

Lệnh phải chạy hết, không ném ngoại lệ. Nếu Oracle báo thiếu cột thì đọc lại tên cột bằng
`ALL_TAB_COLUMNS` trước khi sửa.

---

## Task 3: Hàm thuần tách chuỗi mã bệnh

**Tệp:**
- Tạo: `app/Services/OrderCheck/Support/MaBenh.php`
- Test: `tests/Unit/OrderCheck/MaBenhTest.php`

**Interfaces:**
- Consumes: không
- Produces: `MaBenh::tach($chuoi): string[]`, `MaBenh::gom($chinh, $phu): array`

Tách thành lớp thuần riêng vì đây là chỗ dễ sai nhất của cả đợt: quên bỏ phần tử rỗng thì
**mọi** phiếu có chẩn đoán phụ đều thành vi phạm. Lớp thuần thì kiểm được trực tiếp.

`gom()` trả về `mã => vị trí`, với vị trí là `'chinh'`, `'phu'` hoặc `'ca_hai'` — đủ để
dựng thông điệp mà không phải duyệt lại hai chuỗi.

- [ ] **Bước 1: Viết test đỏ**

```php
<?php

namespace Tests\Unit\OrderCheck;

use Tests\TestCase;
use App\Services\OrderCheck\Support\MaBenh;

class MaBenhTest extends TestCase
{
    /** @test */
    public function tach_chuoi_ma_don()
    {
        $this->assertSame(['A00'], MaBenh::tach('A00'));
        $this->assertSame(['A00'], MaBenh::tach('  A00  '));
    }

    /** @test */
    public function bo_phan_tu_rong_khi_co_dau_cham_phay_dan_dau()
    {
        // Du lieu that: icd_sub_code luon co dang ';A04.9;E87.8'. Khong bo phan tu rong
        // thi MOI phieu co chan doan phu deu thanh vi pham.
        $this->assertSame(['A04.9'], MaBenh::tach(';A04.9'));
        $this->assertSame(['A04.9', 'E87.8'], MaBenh::tach(';A04.9;E87.8'));
        $this->assertSame(['A04.9', 'J44.8', 'N17.9'], MaBenh::tach(';A04.9;J44.8;N17.9'));
    }

    /** @test */
    public function chuoi_rong_hoac_toan_dau_phan_cach_tra_mang_rong()
    {
        $this->assertSame([], MaBenh::tach(''));
        $this->assertSame([], MaBenh::tach(null));
        $this->assertSame([], MaBenh::tach(';'));
        $this->assertSame([], MaBenh::tach(';;;'));
        $this->assertSame([], MaBenh::tach('  ;  ;  '));
    }

    /** @test */
    public function bo_ma_trung_trong_cung_mot_chuoi()
    {
        $this->assertSame(['A00'], MaBenh::tach(';A00;A00'));
    }

    /** @test */
    public function gom_danh_dau_vi_tri_chinh_phu_va_ca_hai()
    {
        $this->assertSame(['A00' => 'chinh'], MaBenh::gom('A00', ''));
        $this->assertSame(['B00' => 'phu'], MaBenh::gom('', ';B00'));

        $this->assertSame(
            ['A00' => 'chinh', 'B00' => 'phu'],
            MaBenh::gom('A00', ';B00')
        );
    }

    /** @test */
    public function ma_xuat_hien_o_ca_hai_cho_chi_ke_mot_lan()
    {
        // Cung mot ma khai sai, bao hai lan khong giup nguoi sua.
        $this->assertSame(['A00' => 'ca_hai'], MaBenh::gom('A00', ';A00'));

        $this->assertSame(
            ['A00' => 'ca_hai', 'B00' => 'phu'],
            MaBenh::gom('A00', ';A00;B00')
        );
    }

    /** @test */
    public function gom_hai_chuoi_rong_tra_mang_rong()
    {
        $this->assertSame([], MaBenh::gom('', ''));
        $this->assertSame([], MaBenh::gom(null, null));
    }
}
```

- [ ] **Bước 2: Chạy để xác nhận đỏ**

```bash
vendor/bin/phpunit tests/Unit/OrderCheck/MaBenhTest.php
```

Kỳ vọng: `Class 'App\Services\OrderCheck\Support\MaBenh' not found`.

- [ ] **Bước 3: Viết lớp**

```php
<?php

namespace App\Services\OrderCheck\Support;

/**
 * Tach chuoi ma benh cua phieu chi dinh.
 *
 * his_service_req.icd_sub_code la chuoi nhieu ma ngan boi ';' va CO dau ';' DAN DAU:
 * ';A04.9', ';A04.9;E87.8'. Nen explode luon sinh mot phan tu rong dau tien. Khong bo no
 * thi moi phieu co chan doan phu deu thanh vi pham - 39.242 phieu moi 7 ngay.
 *
 * icd_code thi luon la ma don (0/61.003 phieu co dau ';'), nhung van tach cho dong nhat.
 */
class MaBenh
{
    /**
     * @param mixed $chuoi
     * @return string[] da trim, bo rong, bo trung, giu thu tu xuat hien
     */
    public static function tach($chuoi)
    {
        $ra = [];

        foreach (explode(';', (string) $chuoi) as $m) {
            $m = trim($m);

            if ($m !== '' && !in_array($m, $ra, true)) {
                $ra[] = $m;
            }
        }

        return $ra;
    }

    /**
     * Gom ma cua chan doan chinh va chan doan phu.
     *
     * Mot ma khai sai o ca hai cho van chi la MOT ma khai sai.
     *
     * @return array ma => 'chinh' | 'phu' | 'ca_hai', giu thu tu chinh truoc phu sau
     */
    public static function gom($chinh, $phu)
    {
        $ra = [];

        foreach (self::tach($chinh) as $m) {
            $ra[$m] = 'chinh';
        }

        foreach (self::tach($phu) as $m) {
            $ra[$m] = isset($ra[$m]) ? 'ca_hai' : 'phu';
        }

        return $ra;
    }

    /** Nhan hien thi cua vi tri */
    public static function nhanViTri($viTri)
    {
        $nhan = [
            'chinh' => 'chẩn đoán chính',
            'phu' => 'chẩn đoán phụ',
            'ca_hai' => 'chẩn đoán chính và phụ',
        ];

        return isset($nhan[$viTri]) ? $nhan[$viTri] : $viTri;
    }
}
```

- [ ] **Bước 4: Chạy lại, phải xanh**

```bash
vendor/bin/phpunit tests/Unit/OrderCheck/MaBenhTest.php
```

---

## Task 4: Hai luật ICD

**Tệp:**
- Tạo: `app/Services/OrderCheck/RuleHandlers/Clinical/IcdCatalogRule.php`
- Tạo: `app/Services/OrderCheck/RuleHandlers/Clinical/IcdNotInCatalogRule.php`
- Tạo: `app/Services/OrderCheck/RuleHandlers/Clinical/IcdYhctNotInCatalogRule.php`
- Test: `tests/Unit/OrderCheck/IcdCatalogRuleTest.php`

**Interfaces:**
- Consumes: `MaBenh::gom` (Task 3), `CatalogLookup` với `$dieuKien` (Task 1),
  `OrderContext::$icdSubCode` và hai trường YHCT (Task 2)
- Produces: `A_ICD_NOT_IN_CATALOG`, `A_ICD_YHCT_NOT_IN_CATALOG`

- [ ] **Bước 1: Viết test đỏ**

```php
<?php

namespace Tests\Unit\OrderCheck;

use DB;
use Tests\TestCase;
use App\Services\OrderCheck\Support\OrderContext;
use App\Services\OrderCheck\Support\CatalogLookup;
use App\Services\OrderCheck\RuleHandlers\Clinical\IcdNotInCatalogRule;
use App\Services\OrderCheck\RuleHandlers\Clinical\IcdYhctNotInCatalogRule;

class IcdCatalogRuleTest extends TestCase
{
    private function ctx($chinh, $phu = null, $yhctChinh = null, $yhctPhu = null)
    {
        $c = new OrderContext();
        $c->serviceReqId = 111;
        $c->serviceReqCode = 'PK001';
        $c->icdCode = $chinh;
        $c->icdSubCode = $phu;
        $c->traditionalIcdCode = $yhctChinh;
        $c->traditionalIcdSubCode = $yhctPhu;

        return $c;
    }

    private function tra(array $ma)
    {
        $lk = new CatalogLookup('icd10_categories', 'icd_code', null, null, null, ['is_active' => 1]);
        $lk->datSanChoTest($ma);

        return new IcdNotInCatalogRule($lk);
    }

    /** @test */
    public function danh_muc_rong_thi_im_lang()
    {
        // Phep kiem quan trong nhat: danh muc chua nap KHONG duoc bien moi ma thanh vi pham.
        // Dung datRongChoTest chu KHONG dua vao bang that - icd10_categories dang co
        // 12.229 dong nen sanSang() se tra true.
        $lk = new CatalogLookup('icd10_categories', 'icd_code', null, null, null, ['is_active' => 1]);
        $lk->datRongChoTest();

        $r = new IcdNotInCatalogRule($lk);

        $this->assertCount(0, $r->check($this->ctx('X99')));
    }

    /** @test */
    public function ma_chinh_dung_thi_khong_vi_pham()
    {
        $r = $this->tra(['A00']);

        $this->assertCount(0, $r->check($this->ctx('A00')));
    }

    /** @test */
    public function ma_chinh_sai_thi_bao_va_ghi_ro_vi_tri()
    {
        $r = $this->tra(['A00']);
        $vi = $r->check($this->ctx('X99'));

        $this->assertCount(1, $vi);
        $this->assertEquals('A_ICD_NOT_IN_CATALOG', $vi[0]->ruleCode);
        $this->assertEquals('service_req', $vi[0]->orderRefType);
        $this->assertEquals(111, $vi[0]->orderRefId);
        $this->assertContains('X99', $vi[0]->message);
        $this->assertContains('chẩn đoán chính', $vi[0]->message);
    }

    /** @test */
    public function chuoi_chan_doan_phu_co_dau_cham_phay_dan_dau_khong_gay_bao_oan()
    {
        // Ca chan loi nghiem trong nhat cua ca dot.
        $r = $this->tra(['A00']);

        $this->assertCount(0, $r->check($this->ctx(null, ';A00')));
        $this->assertCount(0, $r->check($this->ctx('A00', ';A00')));
        $this->assertCount(0, $r->check($this->ctx(null, ';;;')));
    }

    /** @test */
    public function ma_phu_sai_thi_bao_va_ghi_ro_vi_tri()
    {
        $r = $this->tra(['A00']);
        $vi = $r->check($this->ctx('A00', ';A00;B99'));

        $this->assertCount(1, $vi);
        $this->assertContains('B99', $vi[0]->message);
        $this->assertContains('chẩn đoán phụ', $vi[0]->message);
    }

    /** @test */
    public function ma_sai_o_ca_hai_cho_chi_sinh_mot_vi_pham()
    {
        $r = $this->tra(['A00']);
        $vi = $r->check($this->ctx('X99', ';X99'));

        $this->assertCount(1, $vi);
        $this->assertContains('chẩn đoán chính và phụ', $vi[0]->message);
    }

    /** @test */
    public function nhieu_ma_sai_cho_nhieu_vi_pham_khong_bi_gop()
    {
        $r = $this->tra(['A00']);
        $vi = $r->check($this->ctx('X99', ';Y88'));

        $this->assertCount(2, $vi);
        $this->assertNotEquals($vi[0]->dedupKey(), $vi[1]->dedupKey());
    }

    /** @test */
    public function phieu_khong_co_ma_nao_thi_khong_vi_pham()
    {
        $r = $this->tra(['A00']);

        $this->assertCount(0, $r->check($this->ctx(null, null)));
        $this->assertCount(0, $r->check($this->ctx('', '')));
    }

    /** @test */
    public function phieu_chi_co_chan_doan_phu_van_duoc_xet()
    {
        $r = $this->tra(['A00']);

        $this->assertCount(1, $r->check($this->ctx(null, ';X99')));
    }

    /** @test */
    public function ma_dinh_khoang_trang_tra_dung_va_thong_diep_sach()
    {
        $r = $this->tra(['A00']);

        $this->assertCount(0, $r->check($this->ctx('  A00  ')));

        $vi = $r->check($this->ctx('  X99  '));
        $this->assertCount(1, $vi);
        $this->assertNotContains('  X99', $vi[0]->message);
        $this->assertSame('X99', $vi[0]->detail['ma_benh']);
    }

    /** @test */
    public function luat_yhct_tra_bang_rieng_khong_bac_cau_sang_icd10()
    {
        // Ma nam trong ICD10 nhung khong nam trong YHCT van la vi pham cua luat YHCT.
        $lk = new CatalogLookup('icd_yhct_categories', 'icd_code', null, null, null, ['is_active' => 1]);
        $lk->datSanChoTest(['Y01']);

        $r = new IcdYhctNotInCatalogRule($lk);
        $vi = $r->check($this->ctx('A00', ';A00', 'A00'));

        $this->assertCount(1, $vi);
        $this->assertEquals('A_ICD_YHCT_NOT_IN_CATALOG', $vi[0]->ruleCode);
        $this->assertContains('ICD YHCT', $vi[0]->message);
    }

    /** @test */
    public function luat_yhct_chi_doc_truong_yhct()
    {
        $lk = new CatalogLookup('icd_yhct_categories', 'icd_code', null, null, null, ['is_active' => 1]);
        $lk->datSanChoTest(['Y01']);

        $r = new IcdYhctNotInCatalogRule($lk);

        // Ma ICD10 sai nam o truong thuong -> luat YHCT khong quan tam.
        $this->assertCount(0, $r->check($this->ctx('X99', ';Z88')));

        // Ma YHCT sai o truong YHCT phu -> co bao.
        $this->assertCount(1, $r->check($this->ctx('X99', ';Z88', 'Y01', ';Y99')));
    }

    /** @test */
    public function hai_luat_doc_lap_nhau()
    {
        $lk10 = new CatalogLookup('icd10_categories', 'icd_code', null, null, null, ['is_active' => 1]);
        $lk10->datSanChoTest(['A00']);
        $lkYhct = new CatalogLookup('icd_yhct_categories', 'icd_code', null, null, null, ['is_active' => 1]);
        $lkYhct->datSanChoTest(['Y01']);

        $c = $this->ctx('X99', null, 'Y99');

        $vi10 = (new IcdNotInCatalogRule($lk10))->check($c);
        $viYhct = (new IcdYhctNotInCatalogRule($lkYhct))->check($c);

        $this->assertCount(1, $vi10);
        $this->assertCount(1, $viYhct);
        $this->assertNotEquals($vi10[0]->dedupKey(), $viYhct[0]->dedupKey());
    }

    /** @test */
    public function dong_is_active_0_khong_duoc_coi_la_co_trong_danh_muc()
    {
        DB::table('icd10_categories')->insert([
            ['icd_code' => 'ZZ9', 'icd_name' => 'Tat', 'is_active' => 0],
            ['icd_code' => 'ZZ8', 'icd_name' => 'Bat', 'is_active' => 1],
        ]);

        try {
            $lk = new CatalogLookup('icd10_categories', 'icd_code', null, null, null, ['is_active' => 1]);
            $r = new IcdNotInCatalogRule($lk);

            $this->assertCount(0, $r->check($this->ctx('ZZ8')));
            $this->assertCount(1, $r->check($this->ctx('ZZ9')));
        } finally {
            DB::table('icd10_categories')->whereIn('icd_code', ['ZZ8', 'ZZ9'])->delete();
        }
    }
}
```

- [ ] **Bước 2: Chạy để xác nhận đỏ**

```bash
vendor/bin/phpunit tests/Unit/OrderCheck/IcdCatalogRuleTest.php
```

- [ ] **Bước 3: Viết lớp trừu tượng**

```php
<?php

namespace App\Services\OrderCheck\RuleHandlers\Clinical;

use App\Services\OrderCheck\Contracts\RuleHandler;
use App\Services\OrderCheck\Support\OrderContext;
use App\Services\OrderCheck\Support\Violation;
use App\Services\OrderCheck\Support\CatalogLookup;
use App\Services\OrderCheck\Support\MaBenh;

/**
 * Khung chung cho hai luat doi chieu ma benh voi danh muc.
 *
 * Bang danh muc RONG thi quy tac IM LANG - cung ly do voi BhytCatalogRule. Day chinh la la
 * chan ma XML3176 thieu: no dang sinh 31.492 vi pham gia vi medical_staffs rong.
 *
 * Khong loc theo ngay hieu luc: hai bang ICD chi co is_active, khong co tu_ngay/den_ngay.
 */
abstract class IcdCatalogRule implements RuleHandler
{
    /** @var CatalogLookup */
    protected $danhMuc;

    public function __construct(CatalogLookup $danhMuc = null)
    {
        $this->danhMuc = $danhMuc ?: new CatalogLookup(
            $this->bang(), 'icd_code', null, null, null, ['is_active' => 1]
        );
    }

    /** Ten bang danh muc */
    abstract protected function bang();

    /** Nhan hien thi trong thong diep, vi du 'danh mục ICD10' */
    abstract protected function nhan();

    /** Ma benh chinh cua phieu */
    abstract protected function maChinh(OrderContext $c);

    /** Chuoi ma benh phu cua phieu */
    abstract protected function maPhu(OrderContext $c);

    public function check(OrderContext $c)
    {
        if (!$this->danhMuc->sanSang()) {
            return [];   // danh muc chua nap - im lang thay vi bao oan toan bo
        }

        $ma = MaBenh::gom($this->maChinh($c), $this->maPhu($c));

        if (empty($ma)) {
            return [];
        }

        // Mot truy van cho ca phieu.
        $this->danhMuc->nap(array_keys($ma));

        $vi = [];

        foreach ($ma as $m => $viTri) {
            if ($this->danhMuc->coTrongDanhMuc($m)) {
                continue;
            }

            $vi[] = new Violation(
                $this->code(),
                'service_req',
                $c->serviceReqId,
                'Mã bệnh không có trong ' . $this->nhan() . ': ' . $m
                    . ' (' . MaBenh::nhanViTri($viTri) . ')',
                [
                    'service_req_code' => $c->serviceReqCode,
                    'ma_benh' => $m,
                    'vi_tri' => $viTri,
                ],
                (string) $m
            );
        }

        return $vi;
    }
}
```

- [ ] **Bước 4: Viết hai lớp con**

`IcdNotInCatalogRule.php`:

```php
<?php

namespace App\Services\OrderCheck\RuleHandlers\Clinical;

use App\Services\OrderCheck\Support\OrderContext;

/** Ma benh ICD10 cua phieu chi dinh khong co trong danh muc ICD10 dang hoat dong. */
class IcdNotInCatalogRule extends IcdCatalogRule
{
    public function code()    { return 'A_ICD_NOT_IN_CATALOG'; }
    protected function bang() { return 'icd10_categories'; }
    protected function nhan() { return 'danh mục ICD10'; }

    protected function maChinh(OrderContext $c) { return $c->icdCode; }
    protected function maPhu(OrderContext $c)   { return $c->icdSubCode; }
}
```

`IcdYhctNotInCatalogRule.php`:

```php
<?php

namespace App\Services\OrderCheck\RuleHandlers\Clinical;

use App\Services\OrderCheck\Support\OrderContext;

/**
 * Ma benh YHCT cua phieu chi dinh khong co trong danh muc ICD YHCT.
 *
 * Do tren 7 ngay that: 1.199 phieu co ma YHCT, 41 ma phan biet, KHONG ma nao sai. Luat nay
 * se IM LANG sau khi bat - do la dung, khong phai hong. Van viet vi day la so 0 TINH CO
 * (ma hien dung ngau nhien deu hop le) chu khong phai so 0 CAU TRUC.
 *
 * KHONG bac cau sang icd10_categories. Xml3176Xml3Checker co lam viec do de goi y ma tuong
 * duong, nhung nguoi dung da chot bo o dot nay.
 */
class IcdYhctNotInCatalogRule extends IcdCatalogRule
{
    public function code()    { return 'A_ICD_YHCT_NOT_IN_CATALOG'; }
    protected function bang() { return 'icd_yhct_categories'; }
    protected function nhan() { return 'danh mục ICD YHCT'; }

    protected function maChinh(OrderContext $c) { return $c->traditionalIcdCode; }
    protected function maPhu(OrderContext $c)   { return $c->traditionalIcdSubCode; }
}
```

- [ ] **Bước 5: Chạy lại, phải xanh**

```bash
vendor/bin/phpunit --testsuite Unit
```

---

## Task 5: Luật CCHN nhân viên y tế

**Tệp:**
- Tạo: `app/Services/OrderCheck/RuleHandlers/Clinical/StaffCertNotInCatalogRule.php`
- Test: `tests/Unit/OrderCheck/StaffCertRuleTest.php`

**Interfaces:**
- Consumes: `CatalogLookup` (Task 1), `NgayHieuLuc::tuMocHis` (đã có),
  `OrderContext::$requestDiploma` (Task 2)
- Produces: `A_STAFF_CERT_NOT_IN_CATALOG`

Tra **hai cột khoá** `macchn` và `ma_bhxh` bằng **hai** thực thể `CatalogLookup` — giữ
`CatalogLookup` đơn giản, không tổng quát hoá thành nhiều khoá.

- [ ] **Bước 1: Viết test đỏ**

```php
<?php

namespace Tests\Unit\OrderCheck;

use Tests\TestCase;
use App\Services\OrderCheck\Support\OrderContext;
use App\Services\OrderCheck\Support\CatalogLookup;
use App\Services\OrderCheck\RuleHandlers\Clinical\StaffCertNotInCatalogRule;

class StaffCertRuleTest extends TestCase
{
    private function ctx($cchnBacSi, $cchnNguoiTh, $moc = 20240601080000)
    {
        $c = new OrderContext();
        $c->serviceReqId = 222;
        $c->serviceReqCode = 'PK002';
        $c->requestDiploma = $cchnBacSi;
        $c->executeDiploma = $cchnNguoiTh;
        $c->intructionTime = $moc;

        return $c;
    }

    /**
     * @param array $macchn ma => [ ['tu'=>, 'den'=>], ... ]
     * @param array $maBhxh nhu tren
     */
    private function tra(array $macchn, array $maBhxh = [])
    {
        $lkCchn = new CatalogLookup('medical_staffs', 'macchn');
        $lkCchn->datSanChoTest([], $macchn);

        $lkBhxh = new CatalogLookup('medical_staffs', 'ma_bhxh');
        $lkBhxh->datSanChoTest([], $maBhxh);

        return new StaffCertNotInCatalogRule($lkCchn, $lkBhxh);
    }

    /** @test */
    public function danh_muc_rong_thi_im_lang()
    {
        // medical_staffs dang 0 dong. XML3176 thieu la chan nay va dang sinh 31.492 vi
        // pham gia - 100% so dong XML3.
        //
        // Van dung datRongChoTest chu khong dua vao bang dang rong: den ngay don vi nap
        // danh muc thi test nay se vo neu phu thuoc noi dung bang.
        $lkCchn = new CatalogLookup('medical_staffs', 'macchn');
        $lkCchn->datRongChoTest();
        $lkBhxh = new CatalogLookup('medical_staffs', 'ma_bhxh');
        $lkBhxh->datRongChoTest();

        $r = new StaffCertNotInCatalogRule($lkCchn, $lkBhxh);

        $this->assertCount(0, $r->check($this->ctx('X1', 'X2')));
    }

    /** @test */
    public function khop_macchn_thi_khong_vi_pham()
    {
        $r = $this->tra(['C1' => [['tu' => '', 'den' => '']]]);

        $this->assertCount(0, $r->check($this->ctx('C1', 'C1')));
    }

    /** @test */
    public function khop_ma_bhxh_thi_khong_vi_pham()
    {
        $r = $this->tra([], ['B1' => [['tu' => '', 'den' => '']]]);

        $this->assertCount(0, $r->check($this->ctx('B1', 'B1')));
    }

    /** @test */
    public function khong_khop_cot_nao_thi_vi_pham()
    {
        $r = $this->tra(['C1' => [['tu' => '', 'den' => '']]]);
        $vi = $r->check($this->ctx('X9', null));

        $this->assertCount(1, $vi);
        $this->assertEquals('A_STAFF_CERT_NOT_IN_CATALOG', $vi[0]->ruleCode);
        $this->assertEquals('service_req', $vi[0]->orderRefType);
        $this->assertEquals(222, $vi[0]->orderRefId);
        $this->assertContains('X9', $vi[0]->message);
        $this->assertContains('chỉ định', $vi[0]->message);
    }

    /** @test */
    public function het_hieu_luc_tai_ngay_chi_dinh_thi_vi_pham()
    {
        $r = $this->tra(['C1' => [['tu' => '20230101', 'den' => '20231231']]]);

        $this->assertCount(0, $r->check($this->ctx('C1', null, 20230601080000)));
        $this->assertCount(1, $r->check($this->ctx('C1', null, 20240601080000)));
    }

    /** @test */
    public function cchn_rong_thi_im_lang()
    {
        // Thieu CCHN da la viec cua B_DOCTOR_NO_PRACTICE_CERT.
        $r = $this->tra(['C1' => [['tu' => '', 'den' => '']]]);

        $this->assertCount(0, $r->check($this->ctx(null, null)));
        $this->assertCount(0, $r->check($this->ctx('', '   ')));
    }

    /** @test */
    public function ca_hai_vai_tro_deu_sai_cho_hai_vi_pham()
    {
        $r = $this->tra(['C1' => [['tu' => '', 'den' => '']]]);
        $vi = $r->check($this->ctx('X1', 'X2'));

        $this->assertCount(2, $vi);
        $this->assertNotEquals($vi[0]->dedupKey(), $vi[1]->dedupKey());
    }

    /** @test */
    public function hai_vai_tro_cung_mot_cchn_sai_van_cho_hai_vi_pham()
    {
        // Hai vai tro khac nhau, nguoi sua phai xu ly ca hai cho.
        $r = $this->tra(['C1' => [['tu' => '', 'den' => '']]]);
        $vi = $r->check($this->ctx('X1', 'X1'));

        $this->assertCount(2, $vi);
        $this->assertNotEquals($vi[0]->dedupKey(), $vi[1]->dedupKey());
    }

    /** @test */
    public function khong_doc_duoc_moc_chi_dinh_thi_im_lang()
    {
        $r = $this->tra(['C1' => [['tu' => '', 'den' => '']]]);

        $this->assertCount(0, $r->check($this->ctx('X1', 'X2', 0)));
    }
}
```

- [ ] **Bước 2: Chạy để xác nhận đỏ**

```bash
vendor/bin/phpunit tests/Unit/OrderCheck/StaffCertRuleTest.php
```

- [ ] **Bước 3: Viết lớp**

```php
<?php

namespace App\Services\OrderCheck\RuleHandlers\Clinical;

use App\Services\OrderCheck\Contracts\RuleHandler;
use App\Services\OrderCheck\Support\OrderContext;
use App\Services\OrderCheck\Support\Violation;
use App\Services\OrderCheck\Support\CatalogLookup;
use App\Services\OrderCheck\Support\NgayHieuLuc;

/**
 * CCHN cua bac si chi dinh / nguoi thuc hien khong co trong danh muc nhan vien y te.
 *
 * Tra hai cot khoa macchn HOAC ma_bhxh - giu nguyen ngu nghia cua
 * CommonValidationService::isMedicalStaffValid de order-check va XML3176 khong cho hai ket
 * luan khac nhau tren cung mot ho so.
 *
 * Dung HAI thuc the CatalogLookup thay vi tong quat hoa lop do thanh nhieu khoa: doi lai
 * hai truy van moi lo, nhung giu CatalogLookup don gian va khong dung toi bay luat BHYT
 * dang dung no.
 *
 * KHAC XML3176 hai diem, ca hai la sua chu khong phai lech chuan:
 *   1. CO loc hieu luc theo tu_ngay/den_ngay; isMedicalStaffValid chi exists().
 *   2. Danh muc rong thi IM LANG; XML3176 thieu la chan nay va dang sinh 31.492 vi pham
 *      gia - dung bang 100% so dong xml3176_xml3s.
 */
class StaffCertNotInCatalogRule implements RuleHandler
{
    /** @var CatalogLookup */
    protected $traCchn;

    /** @var CatalogLookup */
    protected $traMaBhxh;

    public function __construct(CatalogLookup $traCchn = null, CatalogLookup $traMaBhxh = null)
    {
        $this->traCchn = $traCchn ?: new CatalogLookup('medical_staffs', 'macchn');
        $this->traMaBhxh = $traMaBhxh ?: new CatalogLookup('medical_staffs', 'ma_bhxh');
    }

    public function code()
    {
        return 'A_STAFF_CERT_NOT_IN_CATALOG';
    }

    public function check(OrderContext $c)
    {
        if (!$this->traCchn->sanSang()) {
            return [];   // danh muc chua nap - im lang thay vi bao oan toan bo
        }

        $ngay = NgayHieuLuc::tuMocHis($c->intructionTime);

        if ($ngay === null) {
            return [];
        }

        $vaiTro = [
            'bs' => ['nhan' => 'bác sĩ chỉ định', 'cchn' => trim((string) $c->requestDiploma)],
            'th' => ['nhan' => 'người thực hiện', 'cchn' => trim((string) $c->executeDiploma)],
        ];

        $can = [];

        foreach ($vaiTro as $v) {
            if ($v['cchn'] !== '') {
                $can[] = $v['cchn'];
            }
        }

        if (empty($can)) {
            return [];   // thieu CCHN da la viec cua B_DOCTOR_NO_PRACTICE_CERT
        }

        // Mot truy van moi bang tra, cho ca phieu.
        $this->traCchn->nap($can);
        $this->traMaBhxh->nap($can);

        $vi = [];

        foreach ($vaiTro as $khoa => $v) {
            if ($v['cchn'] === '') {
                continue;
            }

            if ($this->traCchn->coTrongDanhMuc($v['cchn'], $ngay)
                || $this->traMaBhxh->coTrongDanhMuc($v['cchn'], $ngay)) {
                continue;
            }

            $vi[] = new Violation(
                $this->code(),
                'service_req',
                $c->serviceReqId,
                'CCHN ' . $v['nhan'] . ' không có trong danh mục nhân viên y tế còn hiệu lực: '
                    . $v['cchn'],
                [
                    'service_req_code' => $c->serviceReqCode,
                    'vai_tro' => $khoa,
                    'cchn' => $v['cchn'],
                    'ngay_chi_dinh' => $ngay,
                ],
                $khoa . ':' . $v['cchn']
            );
        }

        return $vi;
    }
}
```

- [ ] **Bước 4: Chạy lại, phải xanh**

```bash
vendor/bin/phpunit --testsuite Unit
```

---

## Task 6: Đăng ký ba luật và migration seed

**Tệp:**
- Sửa: `app/Services/OrderCheck/RuleHandlers/ServiceReq/CommonRules.php`
- Sửa: `tests/Unit/OrderCheck/ServiceReqRuleRegistryTest.php`
- Tạo: `database/migrations/2026_07_28_120000_seed_order_check_icd_staff_rules.php`
- Test: `tests/Unit/OrderCheck/IcdStaffSeedTest.php`

**Interfaces:**
- Consumes: ba mã luật từ Task 4 và Task 5

- [ ] **Bước 1: Viết test đỏ cho seed**

```php
<?php

namespace Tests\Unit\OrderCheck;

use Tests\TestCase;
use Tests\Support\LocComment;

class IcdStaffSeedTest extends TestCase
{
    use LocComment;

    private function nguonSeed()
    {
        $file = glob(database_path('migrations/*seed_order_check_icd_staff_rules.php'));
        $this->assertNotEmpty($file, 'Chua co migration seed');

        return $this->maKhongComment($file[0]);
    }

    /** @test */
    public function ba_quy_tac_deu_co_trong_seed()
    {
        $ma = $this->nguonSeed();

        foreach ([
            'A_ICD_NOT_IN_CATALOG',
            'A_ICD_YHCT_NOT_IN_CATALOG',
            'A_STAFF_CERT_NOT_IN_CATALOG',
        ] as $code) {
            $this->assertContains($code, $ma, "Thieu quy tac $code");
        }

        foreach ([
            'IcdNotInCatalogRule',
            'IcdYhctNotInCatalogRule',
            'StaffCertNotInCatalogRule',
        ] as $t) {
            $this->assertContains($t, $ma, "Thieu rule_type $t");
        }
    }

    /** @test */
    public function ba_quy_tac_seed_o_trang_thai_TAT()
    {
        $ma = $this->nguonSeed();

        $this->assertContains("'is_active' => false", $ma);
        $this->assertNotContains("'is_active' => true", $ma, 'Co quy tac seed o trang thai BAT');
    }

    /** @test */
    public function seed_khong_ghi_de_quy_tac_da_ton_tai()
    {
        $ma = $this->nguonSeed();

        $this->assertContains('exists()', $ma, 'Seed khong kiem quy tac da ton tai truoc khi chen');
    }
}
```

- [ ] **Bước 2: Cập nhật `ServiceReqRuleRegistryTest`**

Thêm ba mã vào danh sách khẳng định hiện có:

```php
            'A_ICD_NOT_IN_CATALOG',
            'A_ICD_YHCT_NOT_IN_CATALOG',
            'A_STAFF_CERT_NOT_IN_CATALOG',
```

- [ ] **Bước 3: Chạy để xác nhận đỏ**

```bash
vendor/bin/phpunit tests/Unit/OrderCheck/IcdStaffSeedTest.php tests/Unit/OrderCheck/ServiceReqRuleRegistryTest.php
```

- [ ] **Bước 4: Đăng ký ba handler trong `CommonRules`**

Thêm `use` cho ba lớp, rồi thêm vào cuối mảng `handlers()`:

```php
            // Doi chieu danh muc trong ung dung. Ba luat deu tu im lang khi bang danh muc
            // con rong.
            new IcdNotInCatalogRule(),
            new IcdYhctNotInCatalogRule(),
            new StaffCertNotInCatalogRule(),
```

- [ ] **Bước 5: Viết migration**

```php
<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Migrations\Migration;

/**
 * Ba quy tac doi chieu danh muc ICD, ICD YHCT va nhan vien y te cho order-check.
 *
 * SEED O TRANG THAI TAT (is_active = false), moi quy tac mot ly do khac nhau:
 *
 *   A_ICD_NOT_IN_CATALOG        Quy mo lon: 11.887 dong vi pham moi 7 ngay do 287 ma gay
 *                               ra (9.962/60.682 phieu, 16,42%). Danh muc da co 12.229
 *                               dong nen bat la chay that ngay - can xac nhan con so tren
 *                               may chu that truoc.
 *   A_ICD_YHCT_NOT_IN_CATALOG   Do duoc 0 vi pham tren 1.199 phieu co ma YHCT. Bat cung
 *                               khong doi gi. Luat nay se IM LANG - do la DUNG, khong
 *                               phai hong.
 *   A_STAFF_CERT_NOT_IN_CATALOG medical_staffs dang 0 dong.
 *
 * Quy trinh: chay `php artisan kiemtraylenh:thu --ngay=7` de dem truoc ma khong ghi gi,
 * xem con so, roi bat tung quy tac tren man Quan ly quy tac.
 */
class SeedOrderCheckIcdStaffRules extends Migration
{
    public function up()
    {
        $now = now();

        $rules = [
            [
                'code' => 'A_ICD_NOT_IN_CATALOG',
                'rule_type' => 'IcdNotInCatalogRule',
                'name' => 'Mã bệnh không có trong danh mục ICD10',
            ],
            [
                'code' => 'A_ICD_YHCT_NOT_IN_CATALOG',
                'rule_type' => 'IcdYhctNotInCatalogRule',
                'name' => 'Mã bệnh YHCT không có trong danh mục ICD YHCT',
            ],
            [
                'code' => 'A_STAFF_CERT_NOT_IN_CATALOG',
                'rule_type' => 'StaffCertNotInCatalogRule',
                'name' => 'CCHN không có trong danh mục nhân viên y tế',
            ],
        ];

        foreach ($rules as $r) {
            if (DB::table('order_check_rules')->where('code', $r['code'])->exists()) {
                continue;
            }

            DB::table('order_check_rules')->insert([
                'code' => $r['code'],
                'family' => 'A',
                'rule_type' => $r['rule_type'],
                'name' => $r['name'],
                'severity' => 'warning',
                'params' => null,
                'scope' => null,
                'is_active' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down()
    {
        DB::table('order_check_rules')->whereIn('code', [
            'A_ICD_NOT_IN_CATALOG',
            'A_ICD_YHCT_NOT_IN_CATALOG',
            'A_STAFF_CERT_NOT_IN_CATALOG',
        ])->delete();
    }
}
```

- [ ] **Bước 6: Chạy migration và toàn bộ bộ Unit**

```bash
php artisan migrate
```

```bash
vendor/bin/phpunit --testsuite Unit
```

---

## Task 7: Lệnh đếm thử biết ba luật mới

**Tệp:**
- Sửa: `app/Console/Commands/OrderCheckDryRun.php`

**Interfaces:**
- Consumes: ba handler từ Task 4 và Task 5

Lệnh hiện chỉ cảnh báo ba bảng danh mục BHYT rỗng. Phải mở rộng cho ba bảng mới, nếu không
người chạy sẽ thấy luật đếm 0 mà không biết vì bảng rỗng.

- [ ] **Bước 1: Thêm ba handler**

Thêm `use` cho ba lớp, rồi thêm vào mảng `$handlers`:

```php
            new IcdNotInCatalogRule(),
            new IcdYhctNotInCatalogRule(),
            new StaffCertNotInCatalogRule(),
```

- [ ] **Bước 2: Mở rộng `canhBaoDanhMucRong()`**

Đổi mảng `$bang` thành:

```php
        $bang = [
            'service_catalogs' => ['ma_dich_vu', []],
            'medicine_catalogs' => ['ma_thuoc', []],
            'medical_supply_catalogs' => ['ma_vat_tu', []],
            'icd10_categories' => ['icd_code', ['is_active' => 1]],
            'icd_yhct_categories' => ['icd_code', ['is_active' => 1]],
            'medical_staffs' => ['macchn', []],
        ];

        foreach ($bang as $b => $c) {
            list($cot, $dieuKien) = $c;

            if (!(new CatalogLookup($b, $cot, null, 'tu_ngay', 'den_ngay', $dieuKien))->sanSang()) {
                $this->warn('Danh muc ' . $b . ' dang RONG -> quy tac tuong ung se im lang.');
            }
        }
```

Với hai bảng ICD, `$cotTu`/`$cotDen` không tồn tại nhưng `sanSang()` **không** đụng tới hai
cột đó nên truyền gì cũng được. Chỉ `nap()` mới dùng, mà ở đây không gọi `nap()`.

- [ ] **Bước 3: Chạy thử trên HIS thật**

```bash
php artisan kiemtraylenh:thu --ngay=7 --lo=2000
```

Kỳ vọng:
- Cảnh báo rỗng cho `service_catalogs`, `medicine_catalogs`, `medical_supply_catalogs`,
  `medical_staffs`. **Không** cảnh báo cho hai bảng ICD.
- `A_ICD_NOT_IN_CATALOG` ra số dương, cỡ **300–350** vi phạm.
- `A_ICD_YHCT_NOT_IN_CATALOG` ra **0** — đúng như đo được.
- `A_STAFF_CERT_NOT_IN_CATALOG` ra 0 vì danh mục rỗng.
- Không ném ngoại lệ.

Cách ra con số 300–350: 11.887 vi phạm / 60.682 phiếu ≈ 0,196 vi phạm mỗi phiếu; lượt chạy
trước cho 1.658 phiếu được xét trên 2.000 phiếu đọc được, nên ≈ 325.

**Lưu ý con số này là chặn dưới.** `OrderCheckDryRun` bỏ qua phiếu không có dòng BHYT nào
(`BhytScope::coDongBhyt`) — bộ lọc dựng cho nhóm bảy luật BHYT. Ba luật mới **không** giới
hạn theo đối tượng BHYT, nên số thật khi chạy trong `ServiceReqScanner` sẽ cao hơn. Đây là
hạn chế của lệnh đếm thử, không phải của luật; ghi lại để người đọc con số không hiểu nhầm.

Nếu `A_ICD_YHCT_NOT_IN_CATALOG` ra số dương thì dừng lại: hoặc phép đo ban đầu sai, hoặc
luật đang đọc nhầm trường. Điều tra trước khi đi tiếp.

Nếu `A_ICD_NOT_IN_CATALOG` ra **0** thì cũng dừng: nhiều khả năng `$dieuKien` chưa được
truyền nên `sanSang()` sai, hoặc `icd_code` chưa được đọc vào `OrderContext`.

- [ ] **Bước 4: Chạy toàn bộ bộ Unit lần cuối**

```bash
vendor/bin/phpunit --testsuite Unit
```

Ghi lại số test trước và sau để đối chiếu. Báo lại cho người dùng, **không commit** cho tới
khi được yêu cầu.
