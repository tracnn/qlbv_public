# Kế hoạch: order-check đối chiếu tên và hiệu lực danh mục BHXH

Spec: `docs/superpowers/specs/2026-07-28-order-check-doi-chieu-ten-danh-muc-design.md`

**Mục tiêu:** order-check đối chiếu cả **tên** BHXH lẫn **mã**, và chỉ đối chiếu với những
dòng danh mục **còn hiệu lực tại ngày chỉ định** của y lệnh.

**Kiến trúc:** giữ nguyên khung `Scanner` + `RuleHandler` đang có. Ba việc: (1) tách một
lớp thuần `NgayHieuLuc` để phân tích và so ngày, (2) mở rộng `CatalogLookup` từ "tập mã"
thành "mã → các dòng (tên, từ ngày, đến ngày)", (3) thêm một nhánh quy tắc tên kế thừa
đúng khung lọc của quy tắc mã.

**Công nghệ:** Laravel 5.5, PHP 7.4, PHPUnit 6.5.

## Ràng buộc chung

- **Không commit.** Người dùng yêu cầu tự review trước.
- Cổng kiểm thử là `vendor/bin/phpunit --testsuite Unit`. Bộ `tests/Feature` đỏ sẵn vì lý
  do môi trường, không dùng làm cổng.
- Mọi bình luận trong mã nguồn viết **tiếng Việt không dấu** (theo lệ của module này).
- Test quét mã nguồn phải dùng trait `Tests\Support\LocComment` để bỏ comment trước khi
  tìm chuỗi — nếu không sẽ đỗ giả.
- So tên **tuyệt đối**, chỉ `trim`. Không hạ chữ, không gộp khoảng trắng.
- Ba quy tắc mới seed `is_active = false`.
- Cột hiệu lực của vật tư dùng `den_ngay`, **không** dùng `den_ngay_hd`.
- Mốc thời gian y lệnh là `tdl_intruction_time`. `execute_time` rỗng 100%, không dùng.

---

## Task 1: `NgayHieuLuc` — phân tích và so ngày

**Tệp:**
- Tạo: `app/Services/OrderCheck/Support/NgayHieuLuc.php`
- Test: `tests/Unit/OrderCheck/NgayHieuLucTest.php`

**Giao diện:**
- Sản xuất: `NgayHieuLuc::phanTich($gt): ?int` trả `Ymd`; `NgayHieuLuc::conHieuLuc($tu, $den, $ngayYmd): bool`
- Tiêu thụ: không

Lớp thuần, không chạm cơ sở dữ liệu. Task 2 và 3 dùng nó.

- [ ] **Bước 1: Viết test đỏ**

```php
<?php

namespace Tests\Unit\OrderCheck;

use Tests\TestCase;
use App\Services\OrderCheck\Support\NgayHieuLuc;

class NgayHieuLucTest extends TestCase
{
    public function test_phan_tich_serial_excel()
    {
        $this->assertSame(20240101, NgayHieuLuc::phanTich(45292));
        $this->assertSame(20240101, NgayHieuLuc::phanTich('45292'));
        $this->assertSame(20240101, NgayHieuLuc::phanTich(45292.0));
    }

    public function test_phan_tich_cac_dang_chuoi()
    {
        $this->assertSame(20240101, NgayHieuLuc::phanTich('20240101'));
        $this->assertSame(20240101, NgayHieuLuc::phanTich('01/01/2024'));
        $this->assertSame(20240101, NgayHieuLuc::phanTich('2024-01-01'));
        $this->assertSame(20240101, NgayHieuLuc::phanTich('01-01-2024'));
        $this->assertSame(20240315, NgayHieuLuc::phanTich('15/03/2024'));
        $this->assertSame(20240315, NgayHieuLuc::phanTich(' 15/3/2024 '));
    }

    public function test_phan_tich_gia_tri_khong_hieu_tra_null()
    {
        $this->assertNull(NgayHieuLuc::phanTich(''));
        $this->assertNull(NgayHieuLuc::phanTich(null));
        $this->assertNull(NgayHieuLuc::phanTich('abc'));
        $this->assertNull(NgayHieuLuc::phanTich(0));
        $this->assertNull(NgayHieuLuc::phanTich('32/13/2024'));
    }

    public function test_con_hieu_luc_trong_khoang()
    {
        $this->assertTrue(NgayHieuLuc::conHieuLuc('20240101', '20241231', 20240601));
        $this->assertTrue(NgayHieuLuc::conHieuLuc('20240101', '20241231', 20240101));
        $this->assertTrue(NgayHieuLuc::conHieuLuc('20240101', '20241231', 20241231));
    }

    public function test_ngoai_khoang_thi_khong_con_hieu_luc()
    {
        $this->assertFalse(NgayHieuLuc::conHieuLuc('20240101', '20241231', 20231231));
        $this->assertFalse(NgayHieuLuc::conHieuLuc('20240101', '20241231', 20250101));
    }

    public function test_ngay_khong_doc_duoc_thi_coi_nhu_con_hieu_luc()
    {
        $this->assertTrue(NgayHieuLuc::conHieuLuc('', '20241231', 20200101));
        $this->assertTrue(NgayHieuLuc::conHieuLuc('20240101', '', 20990101));
        $this->assertTrue(NgayHieuLuc::conHieuLuc('abc', 'xyz', 20240601));
        $this->assertTrue(NgayHieuLuc::conHieuLuc(null, null, 20240601));
    }

    public function test_ngay_xet_khong_hop_le_thi_khong_loc()
    {
        $this->assertTrue(NgayHieuLuc::conHieuLuc('20240101', '20241231', null));
        $this->assertTrue(NgayHieuLuc::conHieuLuc('20240101', '20241231', 0));
    }

    public function test_tu_moc_his()
    {
        $this->assertSame(20260728, NgayHieuLuc::tuMocHis(20260728143015));
        $this->assertNull(NgayHieuLuc::tuMocHis(0));
        $this->assertNull(NgayHieuLuc::tuMocHis(null));
    }
}
```

- [ ] **Bước 2: Chạy để xác nhận đỏ**

```bash
vendor/bin/phpunit tests/Unit/OrderCheck/NgayHieuLucTest.php
```

Kỳ vọng: đỏ với `Class 'App\Services\OrderCheck\Support\NgayHieuLuc' not found`.

- [ ] **Bước 3: Viết lớp**

```php
<?php

namespace App\Services\OrderCheck\Support;

/**
 * Phan tich va so ngay hieu luc cua danh muc BHXH.
 *
 * Cot tu_ngay / den_ngay cua ba bang danh muc la varchar(255) ghi THO tu o Excel.
 * CatalogImportService khong chuan hoa gi, nen gia tri co the la serial Excel (45292),
 * chuoi Ymd, d/m/Y, Y-m-d hoac d-m-Y. Lop nay chap nhan ca nam dang.
 *
 * FAIL-SAFE: khong doc duoc ngay thi coi nhu CON hieu luc. Loi chat luong du lieu danh
 * muc khong duoc bien thanh mot tran lu vi pham gia.
 */
class NgayHieuLuc
{
    /** Serial Excel toi da chap nhan; 80000 tuong ung nam 2118 */
    const SERIAL_TOI_DA = 80000;

    /** Moc goc cua serial Excel: 1899-12-30 */
    const GOC_EXCEL = '1899-12-30';

    /**
     * @param mixed $gt
     * @return int|null so nguyen dang Ymd, null neu khong hieu
     */
    public static function phanTich($gt)
    {
        if ($gt === null) {
            return null;
        }

        $s = trim((string) $gt);

        if ($s === '') {
            return null;
        }

        if (is_numeric($s)) {
            $so = (float) $s;

            if ($so >= 1 && $so <= self::SERIAL_TOI_DA) {
                return self::tuSerialExcel($so);
            }

            if (preg_match('/^\d{8}$/', $s)) {
                return self::hopLe((int) substr($s, 0, 4), (int) substr($s, 4, 2), (int) substr($s, 6, 2));
            }

            return null;
        }

        if (preg_match('#^(\d{1,2})/(\d{1,2})/(\d{4})$#', $s, $m)) {
            return self::hopLe((int) $m[3], (int) $m[2], (int) $m[1]);
        }

        if (preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})$/', $s, $m)) {
            return self::hopLe((int) $m[1], (int) $m[2], (int) $m[3]);
        }

        if (preg_match('/^(\d{1,2})-(\d{1,2})-(\d{4})$/', $s, $m)) {
            return self::hopLe((int) $m[3], (int) $m[2], (int) $m[1]);
        }

        return null;
    }

    /** Moc thoi gian HIS dang YmdHis -> Ymd */
    public static function tuMocHis($moc)
    {
        $s = trim((string) $moc);

        if (!preg_match('/^\d{14}$/', $s)) {
            return null;
        }

        return self::hopLe((int) substr($s, 0, 4), (int) substr($s, 4, 2), (int) substr($s, 6, 2));
    }

    /**
     * Dong danh muc con hieu luc tai $ngayYmd khong.
     *
     * $ngayYmd rong -> khong loc (tra true), de lop goi tu quyet dinh bo qua dong do.
     */
    public static function conHieuLuc($tuNgay, $denNgay, $ngayYmd)
    {
        if (empty($ngayYmd)) {
            return true;
        }

        $tu = self::phanTich($tuNgay);

        if ($tu !== null && $ngayYmd < $tu) {
            return false;
        }

        $den = self::phanTich($denNgay);

        if ($den !== null && $ngayYmd > $den) {
            return false;
        }

        return true;
    }

    protected static function tuSerialExcel($so)
    {
        $ngay = new \DateTime(self::GOC_EXCEL);
        $ngay->modify('+' . (int) floor($so) . ' days');

        return (int) $ngay->format('Ymd');
    }

    protected static function hopLe($nam, $thang, $ngay)
    {
        if (!checkdate($thang, $ngay, $nam) || $nam < 1900 || $nam > 2999) {
            return null;
        }

        return $nam * 10000 + $thang * 100 + $ngay;
    }
}
```

- [ ] **Bước 4: Chạy lại, phải xanh**

```bash
vendor/bin/phpunit tests/Unit/OrderCheck/NgayHieuLucTest.php
```

---

## Task 2: `CatalogLookup` mang tên và hiệu lực

**Tệp:**
- Sửa: `app/Services/OrderCheck/Support/CatalogLookup.php`
- Test: `tests/Unit/OrderCheck/CatalogLookupTest.php` (đã có, bổ sung ca)

**Giao diện:**
- Tiêu thụ: `NgayHieuLuc::conHieuLuc` (Task 1)
- Sản xuất: `tenTheoMa($ma, $ngayYmd = null): array`, `coTrongDanhMuc($ma, $ngayYmd = null): bool`,
  hàm dựng `__construct($bang, $cot, $cotTen = null, $cotTu = 'tu_ngay', $cotDen = 'den_ngay')`

- [ ] **Bước 1: Viết test đỏ, thêm vào cuối `CatalogLookupTest`**

```php
    public function test_tra_ten_theo_ma()
    {
        $lk = new CatalogLookup('medicine_catalogs', 'ma_thuoc', 'ten_thuoc');
        $lk->datSanChoTest([], [
            '40.805' => [
                ['ten' => 'Wosulin 30/70', 'tu' => '20240101', 'den' => '20241231'],
                ['ten' => 'INSUNOVA - 30/70', 'tu' => '20240101', 'den' => '20241231'],
            ],
        ]);

        $this->assertSame(['Wosulin 30/70', 'INSUNOVA - 30/70'], $lk->tenTheoMa('40.805', 20240601));
        $this->assertSame([], $lk->tenTheoMa('99.999', 20240601));
    }

    public function test_ten_het_hieu_luc_bi_loai()
    {
        $lk = new CatalogLookup('medicine_catalogs', 'ma_thuoc', 'ten_thuoc');
        $lk->datSanChoTest([], [
            'A1' => [
                ['ten' => 'Ten cu', 'tu' => '20230101', 'den' => '20231231'],
                ['ten' => 'Ten moi', 'tu' => '20240101', 'den' => ''],
            ],
        ]);

        $this->assertSame(['Ten cu'], $lk->tenTheoMa('A1', 20230601));
        $this->assertSame(['Ten moi'], $lk->tenTheoMa('A1', 20240601));
    }

    public function test_ma_het_hieu_luc_coi_nhu_khong_co()
    {
        $lk = new CatalogLookup('medicine_catalogs', 'ma_thuoc', 'ten_thuoc');
        $lk->datSanChoTest([], [
            'A1' => [['ten' => 'X', 'tu' => '20230101', 'den' => '20231231']],
        ]);

        $this->assertTrue($lk->coTrongDanhMuc('A1', 20230601));
        $this->assertFalse($lk->coTrongDanhMuc('A1', 20240601));
        $this->assertTrue($lk->coTrongDanhMuc('A1'));
    }

    public function test_ten_trung_nhau_chi_tra_mot_lan()
    {
        $lk = new CatalogLookup('medicine_catalogs', 'ma_thuoc', 'ten_thuoc');
        $lk->datSanChoTest([], [
            'A1' => [
                ['ten' => 'X', 'tu' => '', 'den' => ''],
                ['ten' => ' X ', 'tu' => '', 'den' => ''],
            ],
        ]);

        $this->assertSame(['X'], $lk->tenTheoMa('A1', 20240601));
    }
```

- [ ] **Bước 2: Chạy để xác nhận đỏ**

```bash
vendor/bin/phpunit tests/Unit/OrderCheck/CatalogLookupTest.php
```

Kỳ vọng: đỏ vì `tenTheoMa` chưa tồn tại và hàm dựng chưa nhận tham số thứ ba.

- [ ] **Bước 3: Viết lại `CatalogLookup`**

```php
<?php

namespace App\Services\OrderCheck\Support;

use DB;

/**
 * Tra danh muc BHXH theo LO cho mot phieu.
 *
 * Bai hoc tu dot XML3176: cac checker o do tra danh muc 18 cho theo TUNG DONG, khien mot
 * ho so sinh hang nghin truy van. O day nap mot lan cho ca phieu bang whereIn.
 *
 * Loc hieu luc lam TRONG BO NHO chu khong trong SQL: mot lo y lenh co nhieu ngay chi dinh
 * khac nhau, loc ngay trong SQL thi moi ngay thanh mot truy van.
 */
class CatalogLookup
{
    protected $bang;
    protected $cot;
    protected $cotTen;
    protected $cotTu;
    protected $cotDen;

    /** @var array ma => [ ['ten'=>?string, 'tu'=>?string, 'den'=>?string], ... ] */
    protected $dong = [];

    /** @var bool|null null = chua kiem */
    protected $sanSang;

    public function __construct($bang, $cot, $cotTen = null, $cotTu = 'tu_ngay', $cotDen = 'den_ngay')
    {
        $this->bang = $bang;
        $this->cot = $cot;
        $this->cotTen = $cotTen;
        $this->cotTu = $cotTu;
        $this->cotDen = $cotDen;
    }

    /**
     * Bang danh muc co du lieu khong.
     *
     * Bang RONG -> tra false -> quy tac goi PHAI bo qua. Neu khong, don vi chua nhap danh
     * muc se thay MOI dich vu thanh vi pham - sai ma trong nhu dung.
     */
    public function sanSang()
    {
        if ($this->sanSang === null) {
            $this->sanSang = DB::table($this->bang)->limit(1)->exists();
        }

        return $this->sanSang;
    }

    /**
     * Nap mot lo ma bang MOT truy van. Goi nhieu lan thi cong don.
     */
    public function nap(array $ma)
    {
        $ma = array_values(array_unique(array_filter(array_map(function ($m) {
            return trim((string) $m);
        }, $ma), 'strlen')));

        if (empty($ma) || !$this->sanSang()) {
            return;
        }

        $chon = [$this->cot, $this->cotTu, $this->cotDen];

        if ($this->cotTen !== null) {
            $chon[] = $this->cotTen;
        }

        $thay = DB::table($this->bang)
            ->whereIn($this->cot, $ma)
            ->select($chon)
            ->get();

        foreach ($thay as $d) {
            $d = (array) $d;
            $khoa = trim((string) $d[$this->cot]);

            if ($khoa === '') {
                continue;
            }

            if (!isset($this->dong[$khoa])) {
                $this->dong[$khoa] = [];
            }

            $this->dong[$khoa][] = [
                'ten' => $this->cotTen === null ? null : trim((string) $d[$this->cotTen]),
                'tu' => $d[$this->cotTu],
                'den' => $d[$this->cotDen],
            ];
        }
    }

    /**
     * @param string $ma
     * @param int|null $ngayYmd null = khong loc hieu luc
     */
    public function coTrongDanhMuc($ma, $ngayYmd = null)
    {
        return !empty($this->dongConHieuLuc($ma, $ngayYmd));
    }

    /**
     * Ten cua cac dong danh muc mang ma nay va CON hieu luc tai $ngayYmd.
     *
     * @return string[] da trim, da bo trung, giu thu tu xuat hien
     */
    public function tenTheoMa($ma, $ngayYmd = null)
    {
        $ten = [];

        foreach ($this->dongConHieuLuc($ma, $ngayYmd) as $d) {
            $t = (string) $d['ten'];

            if ($t !== '' && !in_array($t, $ten, true)) {
                $ten[] = $t;
            }
        }

        return $ten;
    }

    protected function dongConHieuLuc($ma, $ngayYmd)
    {
        $ma = trim((string) $ma);

        if ($ma === '' || !isset($this->dong[$ma])) {
            return [];
        }

        return array_values(array_filter($this->dong[$ma], function ($d) use ($ngayYmd) {
            return NgayHieuLuc::conHieuLuc($d['tu'], $d['den'], $ngayYmd);
        }));
    }

    /**
     * Chi dung trong test: nap thang vao bo nho, khong cham co so du lieu.
     *
     * @param array $ma cac ma khong quan tam ten/ngay
     * @param array $dong ma => [ ['ten'=>, 'tu'=>, 'den'=>], ... ]
     */
    public function datSanChoTest(array $ma, array $dong = [])
    {
        foreach ($ma as $m) {
            $this->dong[trim((string) $m)][] = ['ten' => null, 'tu' => null, 'den' => null];
        }

        foreach ($dong as $m => $ds) {
            foreach ($ds as $d) {
                $this->dong[trim((string) $m)][] = [
                    'ten' => isset($d['ten']) ? trim((string) $d['ten']) : null,
                    'tu' => isset($d['tu']) ? $d['tu'] : null,
                    'den' => isset($d['den']) ? $d['den'] : null,
                ];
            }
        }

        $this->sanSang = true;
    }
}
```

- [ ] **Bước 4: Chạy lại, phải xanh**

```bash
vendor/bin/phpunit tests/Unit/OrderCheck/CatalogLookupTest.php tests/Unit/OrderCheck/NgayHieuLucTest.php
```

---

## Task 3: `OrderService` và `HisOrderSource` mang loại dịch vụ và tên BHXH

**Tệp:**
- Sửa: `app/Services/OrderCheck/Support/OrderService.php`
- Sửa: `app/Services/OrderCheck/HisOrderSource.php`
- Test: `tests/Unit/OrderCheck/BhytSeedTest.php` (bổ sung ca quét mã nguồn)

**Giao diện:**
- Sản xuất: `OrderService::$serviceTypeId` (int|null), `OrderService::$bhytName` (string|null)

- [ ] **Bước 1: Viết test đỏ**

Thêm vào `tests/Unit/OrderCheck/BhytSeedTest.php`. Lớp này đã dùng trait
`Tests\Support\LocComment`; nếu chưa thì thêm `use LocComment;` vào thân lớp và
`use Tests\Support\LocComment;` ở đầu tệp.

```php
    public function test_his_order_source_lay_them_loai_dich_vu_va_ten_bhyt()
    {
        $ma = $this->maKhongComment(app_path('Services/OrderCheck/HisOrderSource.php'));

        $this->assertContains('sv.service_type_id', $ma);
        $this->assertContains('sv.hein_service_bhyt_name', $ma);
        $this->assertContains('serviceTypeId', $ma);
        $this->assertContains('bhytName', $ma);
    }

    public function test_order_service_co_hai_thuoc_tinh_moi()
    {
        $s = new \App\Services\OrderCheck\Support\OrderService();

        $this->assertTrue(property_exists($s, 'serviceTypeId'));
        $this->assertTrue(property_exists($s, 'bhytName'));
    }
```

- [ ] **Bước 2: Chạy để xác nhận đỏ**

```bash
vendor/bin/phpunit tests/Unit/OrderCheck/BhytSeedTest.php
```

- [ ] **Bước 3: Thêm hai thuộc tính**

Trong `app/Services/OrderCheck/Support/OrderService.php`, thêm sau `$bhytCode`:

```php
    /** @var int|null Loai dich vu (his_service.service_type_id): 6 Thuoc, 7 Vat tu */
    public $serviceTypeId;

    /** @var string|null Ten BHXH cua dich vu (his_service.hein_service_bhyt_name) */
    public $bhytName;
```

- [ ] **Bước 4: Lấy thêm hai cột trong `HisOrderSource`**

Trong `fetchServicesByReqIds()`, đổi `selectRaw` thành:

```php
->selectRaw('ss.id, ss.service_req_id, ss.tdl_service_code, ss.tdl_service_name,
    ss.execute_time, ss.tdl_intruction_time, ss.patient_type_id,
    sv.hein_service_bhyt_code, sv.hein_service_bhyt_name, sv.service_type_id')
```

và trong vòng dựng `OrderService`, gán thêm:

```php
$s->serviceTypeId = $r->service_type_id === null ? null : (int) $r->service_type_id;
$s->bhytName = $r->hein_service_bhyt_name;
```

Lưu ý: cột Oracle trả về có thể viết hoa tuỳ driver. Đọc đoạn gán hiện có của
`hein_service_bhyt_code` và bám đúng cách nó truy cập thuộc tính.

- [ ] **Bước 5: Chạy lại, phải xanh**

```bash
vendor/bin/phpunit --testsuite Unit
```

---

## Task 4: `BhytCatalogRule` lọc theo loại dịch vụ và hiệu lực

**Tệp:**
- Sửa: `app/Services/OrderCheck/RuleHandlers/Bhyt/BhytCatalogRule.php`
- Sửa: `app/Services/OrderCheck/RuleHandlers/Bhyt/BhytServiceCatalogRule.php`
- Sửa: `app/Services/OrderCheck/RuleHandlers/Bhyt/BhytDrugCatalogRule.php`
- Sửa: `app/Services/OrderCheck/RuleHandlers/Bhyt/BhytSupplyCatalogRule.php`
- Test: `tests/Unit/OrderCheck/BhytRuleTest.php` (đã có, bổ sung ca)

**Giao diện:**
- Tiêu thụ: `NgayHieuLuc::tuMocHis` (Task 1), `CatalogLookup::coTrongDanhMuc($ma, $ngay)` (Task 2),
  `OrderService::$serviceTypeId` (Task 3)
- Sản xuất: `BhytCatalogRule::loaiDichVu(): array|null` (null = phần bù),
  `BhytCatalogRule::dongTrongPhamVi(OrderContext): OrderService[]`

**Đây là task sửa lỗi.** Không có bộ lọc loại, quy tắc thuốc đang đối chiếu cả xét nghiệm
với `medicine_catalogs` — 53.288 dòng bắt oan mỗi tuần theo số liệu spec mục 3.

Ánh xạ loại: `medicine_catalogs` → `[6]`, `medical_supply_catalogs` → `[7]`,
`service_catalogs` → `null` nghĩa là **mọi loại trừ 6 và 7**.

- [ ] **Bước 1: Viết test đỏ, thêm vào `BhytRuleTest`**

```php
    private function dong($id, $ma, $loai, $ten = null, $moc = 20240601080000)
    {
        $s = new \App\Services\OrderCheck\Support\OrderService();
        $s->sereServId = $id;
        $s->serviceCode = 'SV' . $id;
        $s->serviceName = 'Dich vu ' . $id;
        $s->patientTypeId = 1;
        $s->bhytCode = $ma;
        $s->bhytName = $ten;
        $s->serviceTypeId = $loai;
        $s->tdlIntructionTime = $moc;

        return $s;
    }

    private function phieu(array $dong)
    {
        $c = new \App\Services\OrderCheck\Support\OrderContext();
        $c->serviceReqCode = 'PH001';
        $c->services = $dong;

        return $c;
    }

    public function test_quy_tac_thuoc_bo_qua_dong_xet_nghiem()
    {
        $lk = new \App\Services\OrderCheck\Support\CatalogLookup('medicine_catalogs', 'ma_thuoc', 'ten_thuoc');
        $lk->datSanChoTest([]);

        $r = new \App\Services\OrderCheck\RuleHandlers\Bhyt\BhytDrugCatalogRule($lk);
        $vi = $r->check($this->phieu([$this->dong(1, 'XN01', 2)]));

        $this->assertCount(0, $vi);
    }

    public function test_quy_tac_thuoc_van_bat_dong_thuoc()
    {
        $lk = new \App\Services\OrderCheck\Support\CatalogLookup('medicine_catalogs', 'ma_thuoc', 'ten_thuoc');
        $lk->datSanChoTest([]);

        $r = new \App\Services\OrderCheck\RuleHandlers\Bhyt\BhytDrugCatalogRule($lk);
        $vi = $r->check($this->phieu([$this->dong(1, 'TH01', 6)]));

        $this->assertCount(1, $vi);
        $this->assertSame('A_BHYT_DRUG_NOT_IN_CATALOG', $vi[0]->code);
    }

    public function test_quy_tac_dich_vu_bo_qua_thuoc_va_vat_tu()
    {
        $lk = new \App\Services\OrderCheck\Support\CatalogLookup('service_catalogs', 'ma_dich_vu', 'ten_dich_vu');
        $lk->datSanChoTest([]);

        $r = new \App\Services\OrderCheck\RuleHandlers\Bhyt\BhytServiceCatalogRule($lk);
        $vi = $r->check($this->phieu([
            $this->dong(1, 'TH01', 6),
            $this->dong(2, 'VT01', 7),
            $this->dong(3, 'XN01', 2),
        ]));

        $this->assertCount(1, $vi);
        $this->assertSame(3, $vi[0]->targetId);
    }

    public function test_ma_het_hieu_luc_truoc_ngay_chi_dinh_bi_bat()
    {
        $lk = new \App\Services\OrderCheck\Support\CatalogLookup('medicine_catalogs', 'ma_thuoc', 'ten_thuoc');
        $lk->datSanChoTest([], [
            'TH01' => [['ten' => 'Thuoc A', 'tu' => '20230101', 'den' => '20231231']],
        ]);

        $r = new \App\Services\OrderCheck\RuleHandlers\Bhyt\BhytDrugCatalogRule($lk);

        $this->assertCount(0, $r->check($this->phieu([$this->dong(1, 'TH01', 6, 'Thuoc A', 20230601080000)])));
        $this->assertCount(1, $r->check($this->phieu([$this->dong(1, 'TH01', 6, 'Thuoc A', 20240601080000)])));
    }

    public function test_dong_khong_co_moc_chi_dinh_thi_bo_qua()
    {
        $lk = new \App\Services\OrderCheck\Support\CatalogLookup('medicine_catalogs', 'ma_thuoc', 'ten_thuoc');
        $lk->datSanChoTest([]);

        $r = new \App\Services\OrderCheck\RuleHandlers\Bhyt\BhytDrugCatalogRule($lk);
        $vi = $r->check($this->phieu([$this->dong(1, 'TH01', 6, null, 0)]));

        $this->assertCount(0, $vi);
    }
```

Nếu `BhytRuleTest` đã có hàm trợ giúp tên `dong`/`phieu`, đọc bản hiện có và dùng lại thay
vì thêm bản trùng tên — trùng tên hàm trong cùng lớp là lỗi cú pháp.

- [ ] **Bước 2: Chạy để xác nhận đỏ**

```bash
vendor/bin/phpunit tests/Unit/OrderCheck/BhytRuleTest.php
```

- [ ] **Bước 3: Sửa `BhytCatalogRule`**

Thay toàn bộ thân lớp bằng:

```php
<?php

namespace App\Services\OrderCheck\RuleHandlers\Bhyt;

use App\Services\OrderCheck\Contracts\RuleHandler;
use App\Services\OrderCheck\Support\OrderContext;
use App\Services\OrderCheck\Support\Violation;
use App\Services\OrderCheck\Support\BhytScope;
use App\Services\OrderCheck\Support\CatalogLookup;
use App\Services\OrderCheck\Support\NgayHieuLuc;

/**
 * Khung chung cho cac quy tac doi chieu danh muc BHXH.
 *
 * Bang danh muc RONG thi quy tac IM LANG. Khong the thi don vi chua nhap danh muc se
 * thay MOI dich vu thanh vi pham - sai ma trong nhu dung.
 *
 * Loc theo LOAI dich vu la bat buoc: khong loc thi quy tac thuoc doi chieu ca xet nghiem
 * voi medicine_catalogs, do ra 53.288 dong bat oan moi tuan tren so lieu that.
 */
abstract class BhytCatalogRule implements RuleHandler
{
    /** Loai Thuoc trong his_service_type */
    const LOAI_THUOC = 6;

    /** Loai Vat tu trong his_service_type */
    const LOAI_VAT_TU = 7;

    /** @var CatalogLookup */
    protected $danhMuc;

    public function __construct(CatalogLookup $danhMuc = null)
    {
        $this->danhMuc = $danhMuc ?: new CatalogLookup($this->bang(), $this->cot(), $this->cotTen());
    }

    /** Ten bang danh muc cuc bo */
    abstract protected function bang();

    /** Cot chua ma BHXH trong bang do */
    abstract protected function cot();

    /** Cot chua ten trong bang do */
    abstract protected function cotTen();

    /** Nhan hien thi trong thong diep vi pham */
    abstract protected function nhan();

    /**
     * Loai dich vu thuoc pham vi quy tac.
     *
     * Tra null nghia la PHAN BU: moi loai TRU Thuoc va Vat tu. Dung phan bu de loai dich
     * vu moi phat sinh trong HIS van duoc xet, thay vi lang le roi ra ngoai.
     *
     * @return array|null
     */
    abstract protected function loaiDichVu();

    protected function trongPhamViLoai($loai)
    {
        $ds = $this->loaiDichVu();

        if ($ds === null) {
            return !in_array((int) $loai, [self::LOAI_THUOC, self::LOAI_VAT_TU], true);
        }

        return in_array((int) $loai, $ds, true);
    }

    /**
     * Dong BHYT, dung loai, co ma BHXH, co moc chi dinh doc duoc.
     *
     * @return array danh sach [OrderService, int ngayYmd]
     */
    protected function dongTrongPhamVi(OrderContext $c)
    {
        $ra = [];

        foreach (BhytScope::locDongBhyt($c->services) as $s) {
            if (trim((string) $s->bhytCode) === '') {
                continue;
            }

            if (!$this->trongPhamViLoai($s->serviceTypeId)) {
                continue;
            }

            $ngay = NgayHieuLuc::tuMocHis($s->tdlIntructionTime);

            if ($ngay === null) {
                continue;
            }

            $ra[] = [$s, $ngay];
        }

        return $ra;
    }

    public function check(OrderContext $c)
    {
        if (!$this->danhMuc->sanSang()) {
            return [];   // danh muc chua nhap - im lang thay vi bao oan toan bo
        }

        $dong = $this->dongTrongPhamVi($c);

        if (empty($dong)) {
            return [];
        }

        // Mot truy van cho ca phieu, khong tra tung dong.
        $this->danhMuc->nap(array_map(function ($d) {
            return $d[0]->bhytCode;
        }, $dong));

        $vi = [];

        foreach ($dong as $d) {
            list($s, $ngay) = $d;

            if ($this->danhMuc->coTrongDanhMuc($s->bhytCode, $ngay)) {
                continue;
            }

            $vi[] = new Violation(
                $this->code(),
                'sere_serv',
                $s->sereServId,
                $this->nhan() . ' không có trong danh mục BHXH còn hiệu lực: ' . $s->bhytCode
                    . ' (' . $s->serviceName . ')',
                [
                    'service_req_code' => $c->serviceReqCode,
                    'service_code' => $s->serviceCode,
                    'bhyt_code' => $s->bhytCode,
                    'ngay_chi_dinh' => $ngay,
                ],
                (string) $s->sereServId
            );
        }

        return $vi;
    }
}
```

- [ ] **Bước 4: Khai `cotTen()` và `loaiDichVu()` cho ba lớp con**

`BhytDrugCatalogRule.php`:

```php
<?php

namespace App\Services\OrderCheck\RuleHandlers\Bhyt;

/** Ma thuoc BHXH cua dong BHYT khong co trong danh muc thuoc con hieu luc. */
class BhytDrugCatalogRule extends BhytCatalogRule
{
    public function code()          { return 'A_BHYT_DRUG_NOT_IN_CATALOG'; }
    protected function bang()       { return 'medicine_catalogs'; }
    protected function cot()        { return 'ma_thuoc'; }
    protected function cotTen()     { return 'ten_thuoc'; }
    protected function nhan()       { return 'Mã thuốc'; }
    protected function loaiDichVu() { return [self::LOAI_THUOC]; }
}
```

`BhytSupplyCatalogRule.php`:

```php
<?php

namespace App\Services\OrderCheck\RuleHandlers\Bhyt;

/** Ma vat tu BHXH cua dong BHYT khong co trong danh muc vat tu con hieu luc. */
class BhytSupplyCatalogRule extends BhytCatalogRule
{
    public function code()          { return 'A_BHYT_SUPPLY_NOT_IN_CATALOG'; }
    protected function bang()       { return 'medical_supply_catalogs'; }
    protected function cot()        { return 'ma_vat_tu'; }
    protected function cotTen()     { return 'ten_vat_tu'; }
    protected function nhan()       { return 'Mã vật tư'; }
    protected function loaiDichVu() { return [self::LOAI_VAT_TU]; }
}
```

`BhytServiceCatalogRule.php`:

```php
<?php

namespace App\Services\OrderCheck\RuleHandlers\Bhyt;

/** Ma DVKT BHXH cua dong BHYT khong co trong danh muc dich vu con hieu luc. */
class BhytServiceCatalogRule extends BhytCatalogRule
{
    public function code()          { return 'A_BHYT_SERVICE_NOT_IN_CATALOG'; }
    protected function bang()       { return 'service_catalogs'; }
    protected function cot()        { return 'ma_dich_vu'; }
    protected function cotTen()     { return 'ten_dich_vu'; }
    protected function nhan()       { return 'Mã dịch vụ'; }
    protected function loaiDichVu() { return null; }   // phan bu: moi loai tru Thuoc va Vat tu
}
```

- [ ] **Bước 5: Chạy lại, phải xanh**

```bash
vendor/bin/phpunit --testsuite Unit
```

---

## Task 5: Ba quy tắc đối chiếu tên

**Tệp:**
- Tạo: `app/Services/OrderCheck/RuleHandlers/Bhyt/BhytNameMismatchRule.php`
- Tạo: `app/Services/OrderCheck/RuleHandlers/Bhyt/BhytServiceNameRule.php`
- Tạo: `app/Services/OrderCheck/RuleHandlers/Bhyt/BhytDrugNameRule.php`
- Tạo: `app/Services/OrderCheck/RuleHandlers/Bhyt/BhytSupplyNameRule.php`
- Test: `tests/Unit/OrderCheck/BhytNameRuleTest.php`

**Giao diện:**
- Tiêu thụ: `BhytCatalogRule::dongTrongPhamVi()` (Task 4), `CatalogLookup::tenTheoMa()` (Task 2)
- Sản xuất: ba mã quy tắc `A_BHYT_SERVICE_NAME_MISMATCH`, `A_BHYT_DRUG_NAME_MISMATCH`,
  `A_BHYT_SUPPLY_NAME_MISMATCH`

- [ ] **Bước 1: Viết test đỏ**

```php
<?php

namespace Tests\Unit\OrderCheck;

use Tests\TestCase;
use App\Services\OrderCheck\Support\OrderContext;
use App\Services\OrderCheck\Support\OrderService;
use App\Services\OrderCheck\Support\CatalogLookup;
use App\Services\OrderCheck\RuleHandlers\Bhyt\BhytDrugNameRule;
use App\Services\OrderCheck\RuleHandlers\Bhyt\BhytServiceNameRule;

class BhytNameRuleTest extends TestCase
{
    private function dong($id, $ma, $loai, $ten, $moc = 20240601080000)
    {
        $s = new OrderService();
        $s->sereServId = $id;
        $s->serviceCode = 'SV' . $id;
        $s->serviceName = 'Dich vu ' . $id;
        $s->patientTypeId = 1;
        $s->bhytCode = $ma;
        $s->bhytName = $ten;
        $s->serviceTypeId = $loai;
        $s->tdlIntructionTime = $moc;

        return $s;
    }

    private function phieu(array $dong)
    {
        $c = new OrderContext();
        $c->serviceReqCode = 'PH001';
        $c->services = $dong;

        return $c;
    }

    private function traThuoc(array $dong)
    {
        $lk = new CatalogLookup('medicine_catalogs', 'ma_thuoc', 'ten_thuoc');
        $lk->datSanChoTest([], $dong);

        return new BhytDrugNameRule($lk);
    }

    public function test_ten_khop_thi_khong_vi_pham()
    {
        $r = $this->traThuoc(['TH01' => [['ten' => 'Thuoc A', 'tu' => '', 'den' => '']]]);

        $this->assertCount(0, $r->check($this->phieu([$this->dong(1, 'TH01', 6, 'Thuoc A')])));
    }

    public function test_ten_lech_thi_bao_vi_pham_va_neu_ca_hai_ten()
    {
        $r = $this->traThuoc(['TH01' => [['ten' => 'Thuoc A', 'tu' => '', 'den' => '']]]);
        $vi = $r->check($this->phieu([$this->dong(1, 'TH01', 6, 'Thuoc B')]));

        $this->assertCount(1, $vi);
        $this->assertSame('A_BHYT_DRUG_NAME_MISMATCH', $vi[0]->code);
        $this->assertContains('Thuoc B', $vi[0]->message);
        $this->assertContains('Thuoc A', $vi[0]->message);
    }

    public function test_khop_bat_ky_ten_nao_cua_ma_deu_dat()
    {
        $r = $this->traThuoc(['TH01' => [
            ['ten' => 'Wosulin 30/70', 'tu' => '', 'den' => ''],
            ['ten' => 'INSUNOVA - 30/70', 'tu' => '', 'den' => ''],
        ]]);

        $this->assertCount(0, $r->check($this->phieu([$this->dong(1, 'TH01', 6, 'INSUNOVA - 30/70')])));
    }

    public function test_lech_hoa_thuong_van_bao_vi_pham()
    {
        $r = $this->traThuoc(['TH01' => [['ten' => 'Thuoc A', 'tu' => '', 'den' => '']]]);

        $this->assertCount(1, $r->check($this->phieu([$this->dong(1, 'TH01', 6, 'THUOC A')])));
    }

    public function test_lech_khoang_trang_duoi_thi_bo_qua()
    {
        $r = $this->traThuoc(['TH01' => [['ten' => 'Thuoc A', 'tu' => '', 'den' => '']]]);

        $this->assertCount(0, $r->check($this->phieu([$this->dong(1, 'TH01', 6, '  Thuoc A  ')])));
    }

    public function test_ma_khong_co_trong_danh_muc_thi_im_lang()
    {
        $r = $this->traThuoc(['TH99' => [['ten' => 'X', 'tu' => '', 'den' => '']]]);

        $this->assertCount(0, $r->check($this->phieu([$this->dong(1, 'TH01', 6, 'Thuoc B')])));
    }

    public function test_ten_khai_rong_thi_im_lang()
    {
        $r = $this->traThuoc(['TH01' => [['ten' => 'Thuoc A', 'tu' => '', 'den' => '']]]);

        $this->assertCount(0, $r->check($this->phieu([$this->dong(1, 'TH01', 6, '')])));
    }

    public function test_ten_khop_dong_da_het_hieu_luc_van_bao_vi_pham()
    {
        $r = $this->traThuoc(['TH01' => [
            ['ten' => 'Ten cu', 'tu' => '20230101', 'den' => '20231231'],
            ['ten' => 'Ten moi', 'tu' => '20240101', 'den' => ''],
        ]]);

        $vi = $r->check($this->phieu([$this->dong(1, 'TH01', 6, 'Ten cu', 20240601080000)]));

        $this->assertCount(1, $vi);
        $this->assertContains('Ten moi', $vi[0]->message);
    }

    public function test_chi_neu_toi_da_ba_ten_danh_muc()
    {
        $r = $this->traThuoc(['TH01' => [
            ['ten' => 'T1', 'tu' => '', 'den' => ''],
            ['ten' => 'T2', 'tu' => '', 'den' => ''],
            ['ten' => 'T3', 'tu' => '', 'den' => ''],
            ['ten' => 'T4', 'tu' => '', 'den' => ''],
        ]]);

        $vi = $r->check($this->phieu([$this->dong(1, 'TH01', 6, 'X')]));

        $this->assertCount(1, $vi);
        $this->assertContains('…', $vi[0]->message);
        $this->assertNotContains('T4', $vi[0]->message);
    }

    public function test_quy_tac_ten_thuoc_bo_qua_dong_xet_nghiem()
    {
        $r = $this->traThuoc(['XN01' => [['ten' => 'Xet nghiem A', 'tu' => '', 'den' => '']]]);

        $this->assertCount(0, $r->check($this->phieu([$this->dong(1, 'XN01', 2, 'Sai ten')])));
    }

    public function test_quy_tac_ten_dich_vu_bo_qua_thuoc()
    {
        $lk = new CatalogLookup('service_catalogs', 'ma_dich_vu', 'ten_dich_vu');
        $lk->datSanChoTest([], ['TH01' => [['ten' => 'A', 'tu' => '', 'den' => '']]]);

        $r = new BhytServiceNameRule($lk);

        $this->assertCount(0, $r->check($this->phieu([$this->dong(1, 'TH01', 6, 'Sai ten')])));
    }

    public function test_danh_muc_rong_thi_im_lang()
    {
        $lk = new CatalogLookup('medicine_catalogs', 'ma_thuoc', 'ten_thuoc');
        $r = new BhytDrugNameRule($lk);

        // sanSang() chua duoc dat -> se hoi CSDL; bang rong tren moi truong test
        $this->assertCount(0, $r->check($this->phieu([$this->dong(1, 'TH01', 6, 'X')])));
    }
}
```

- [ ] **Bước 2: Chạy để xác nhận đỏ**

```bash
vendor/bin/phpunit tests/Unit/OrderCheck/BhytNameRuleTest.php
```

- [ ] **Bước 3: Viết lớp trừu tượng**

```php
<?php

namespace App\Services\OrderCheck\RuleHandlers\Bhyt;

use App\Services\OrderCheck\Support\OrderContext;
use App\Services\OrderCheck\Support\Violation;

/**
 * Doi chieu TEN BHXH cua dong y lenh voi ten trong danh muc con hieu luc.
 *
 * BHXH tu choi ca khi ten lech, khong chi khi ma sai. Bo kiem XML3176 da bat viec nay
 * (INVALID_DRUG_NAME, INVALID_MATERIAL_NAME) nhung chi sau khi ho so da khoa va xuat XML.
 *
 * KHAC XML3176 o mot diem cot loi: XML3176 khoa duoc DUNG MOT dong danh muc bang bon khoa
 * ma_thuoc + ham_luong + so_dang_ky + tt_thau roi so ten cua dong do. O muc y lenh, HIS chi
 * co ma va ten - khong co ham luong, so dang ky, TT thau. Nen phep so duy nhat dung la:
 * ten khai phai trung ten cua IT NHAT MOT dong danh muc mang ma do. Do tren HIS that,
 * 593 ma BHXH dang duoc nhieu dich vu HIS dung chung voi ten khac nhau, ca biet mot ma
 * co 226 ten - so voi "dong duy nhat" se bao sai hang loat.
 *
 * So TUYET DOI, chi trim. Thong nhat voi Xml3176Xml2Checker.
 */
abstract class BhytNameMismatchRule extends BhytCatalogRule
{
    /** So ten danh muc toi da liet ke trong mo ta; co ma mang toi 226 ten */
    const TOI_DA_NEU_TEN = 3;

    public function check(OrderContext $c)
    {
        if (!$this->danhMuc->sanSang()) {
            return [];
        }

        $dong = $this->dongTrongPhamVi($c);

        if (empty($dong)) {
            return [];
        }

        $this->danhMuc->nap(array_map(function ($d) {
            return $d[0]->bhytCode;
        }, $dong));

        $vi = [];

        foreach ($dong as $d) {
            list($s, $ngay) = $d;

            $tenKhai = trim((string) $s->bhytName);

            if ($tenKhai === '') {
                continue;   // da quyet dinh khong lam quy tac "thieu ten": do duoc 0 dong
            }

            $tenDanhMuc = $this->danhMuc->tenTheoMa($s->bhytCode, $ngay);

            if (empty($tenDanhMuc)) {
                continue;   // ma khong co / het hieu luc -> quy tac MA lo, khong bao chong
            }

            if (in_array($tenKhai, $tenDanhMuc, true)) {
                continue;
            }

            $vi[] = new Violation(
                $this->code(),
                'sere_serv',
                $s->sereServId,
                $this->nhan() . ' lệch danh mục BHXH. Mã ' . $s->bhytCode
                    . '; khai "' . $tenKhai . '"; danh mục: ' . $this->neuTen($tenDanhMuc),
                [
                    'service_req_code' => $c->serviceReqCode,
                    'service_code' => $s->serviceCode,
                    'bhyt_code' => $s->bhytCode,
                    'bhyt_name' => $tenKhai,
                    'ngay_chi_dinh' => $ngay,
                ],
                (string) $s->sereServId
            );
        }

        return $vi;
    }

    protected function neuTen(array $ten)
    {
        $cat = array_slice($ten, 0, self::TOI_DA_NEU_TEN);
        $chuoi = '"' . implode('", "', $cat) . '"';

        if (count($ten) > self::TOI_DA_NEU_TEN) {
            $chuoi .= ' …';
        }

        return $chuoi;
    }
}
```

- [ ] **Bước 4: Viết ba lớp con**

`BhytDrugNameRule.php`:

```php
<?php

namespace App\Services\OrderCheck\RuleHandlers\Bhyt;

/** Ten thuoc khai o HIS lech ten trong danh muc thuoc BHXH con hieu luc. */
class BhytDrugNameRule extends BhytNameMismatchRule
{
    public function code()          { return 'A_BHYT_DRUG_NAME_MISMATCH'; }
    protected function bang()       { return 'medicine_catalogs'; }
    protected function cot()        { return 'ma_thuoc'; }
    protected function cotTen()     { return 'ten_thuoc'; }
    protected function nhan()       { return 'Tên thuốc'; }
    protected function loaiDichVu() { return [self::LOAI_THUOC]; }
}
```

`BhytSupplyNameRule.php`:

```php
<?php

namespace App\Services\OrderCheck\RuleHandlers\Bhyt;

/** Ten vat tu khai o HIS lech ten trong danh muc vat tu BHXH con hieu luc. */
class BhytSupplyNameRule extends BhytNameMismatchRule
{
    public function code()          { return 'A_BHYT_SUPPLY_NAME_MISMATCH'; }
    protected function bang()       { return 'medical_supply_catalogs'; }
    protected function cot()        { return 'ma_vat_tu'; }
    protected function cotTen()     { return 'ten_vat_tu'; }
    protected function nhan()       { return 'Tên vật tư'; }
    protected function loaiDichVu() { return [self::LOAI_VAT_TU]; }
}
```

`BhytServiceNameRule.php`:

```php
<?php

namespace App\Services\OrderCheck\RuleHandlers\Bhyt;

/** Ten DVKT khai o HIS lech ten trong danh muc dich vu BHXH con hieu luc. */
class BhytServiceNameRule extends BhytNameMismatchRule
{
    public function code()          { return 'A_BHYT_SERVICE_NAME_MISMATCH'; }
    protected function bang()       { return 'service_catalogs'; }
    protected function cot()        { return 'ma_dich_vu'; }
    protected function cotTen()     { return 'ten_dich_vu'; }
    protected function nhan()       { return 'Tên dịch vụ'; }
    protected function loaiDichVu() { return null; }   // phan bu: moi loai tru Thuoc va Vat tu
}
```

- [ ] **Bước 5: Chạy lại, phải xanh**

```bash
vendor/bin/phpunit --testsuite Unit
```

---

## Task 6: Migration seed ba quy tắc tên

**Tệp:**
- Tạo: `database/migrations/2026_07_28_110000_seed_order_check_bhyt_name_rules.php`
- Test: `tests/Unit/OrderCheck/BhytSeedTest.php` (bổ sung ca)

**Giao diện:**
- Tiêu thụ: ba mã quy tắc từ Task 5
- Sản xuất: ba dòng `order_check_rules`

- [ ] **Bước 1: Viết test đỏ, thêm vào `BhytSeedTest`**

```php
    public function test_migration_seed_ba_quy_tac_ten()
    {
        $ma = $this->maKhongComment(
            database_path('migrations/2026_07_28_110000_seed_order_check_bhyt_name_rules.php')
        );

        foreach (['A_BHYT_SERVICE_NAME_MISMATCH', 'A_BHYT_DRUG_NAME_MISMATCH', 'A_BHYT_SUPPLY_NAME_MISMATCH'] as $code) {
            $this->assertContains($code, $ma);
        }

        foreach (['BhytServiceNameRule', 'BhytDrugNameRule', 'BhytSupplyNameRule'] as $t) {
            $this->assertContains($t, $ma);
        }

        $this->assertContains("'is_active' => false", $ma);
    }
```

- [ ] **Bước 2: Chạy để xác nhận đỏ**

```bash
vendor/bin/phpunit tests/Unit/OrderCheck/BhytSeedTest.php
```

- [ ] **Bước 3: Viết migration**

```php
<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Migrations\Migration;

/**
 * Ba quy tac doi chieu TEN theo danh muc BHYT cho order-check.
 *
 * SEED O TRANG THAI TAT (is_active = false) - co chu dich, cung ly do voi dot quy tac ma:
 * ba bang danh muc tren moi truong phat trien deu 0 dong nen khong do duoc ti le lech ten
 * that truoc khi trien khai. Phep so la TUYET DOI (chi trim), nen so vi pham ban dau co
 * the rat cao.
 *
 * Quy trinh: nap du ba bang danh muc, chay `php artisan kiemtraylenh:thu --ngay=7` de dem
 * truoc ma khong ghi gi, xem con so, roi bat tung quy tac tren man Quan ly quy tac.
 */
class SeedOrderCheckBhytNameRules extends Migration
{
    public function up()
    {
        $now = now();

        $rules = [
            [
                'code' => 'A_BHYT_SERVICE_NAME_MISMATCH',
                'rule_type' => 'BhytServiceNameRule',
                'name' => 'Tên dịch vụ lệch danh mục BHYT',
            ],
            [
                'code' => 'A_BHYT_DRUG_NAME_MISMATCH',
                'rule_type' => 'BhytDrugNameRule',
                'name' => 'Tên thuốc lệch danh mục BHYT',
            ],
            [
                'code' => 'A_BHYT_SUPPLY_NAME_MISMATCH',
                'rule_type' => 'BhytSupplyNameRule',
                'name' => 'Tên vật tư lệch danh mục BHYT',
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
            'A_BHYT_SERVICE_NAME_MISMATCH',
            'A_BHYT_DRUG_NAME_MISMATCH',
            'A_BHYT_SUPPLY_NAME_MISMATCH',
        ])->delete();
    }
}
```

- [ ] **Bước 4: Chạy lại, phải xanh**

```bash
vendor/bin/phpunit tests/Unit/OrderCheck/BhytSeedTest.php
```

---

## Task 7: Lệnh đếm thử biết ba quy tắc mới và cảnh báo định dạng ngày

**Tệp:**
- Sửa: `app/Console/Commands/OrderCheckDryRun.php`
- Sửa: `tests/Unit/OrderCheck/ServiceReqRuleRegistryTest.php`

**Giao diện:**
- Tiêu thụ: bảy lớp handler, `NgayHieuLuc::phanTich`

- [ ] **Bước 1: Cập nhật test đăng ký quy tắc**

Đọc `ServiceReqRuleRegistryTest` hiện có. Nó khẳng định theo mã quy tắc và tính duy nhất,
không đếm cứng. Thêm ba mã mới vào danh sách mong đợi:

```php
        'A_BHYT_SERVICE_NAME_MISMATCH',
        'A_BHYT_DRUG_NAME_MISMATCH',
        'A_BHYT_SUPPLY_NAME_MISMATCH',
```

- [ ] **Bước 2: Chạy để xác nhận đỏ**

```bash
vendor/bin/phpunit tests/Unit/OrderCheck/ServiceReqRuleRegistryTest.php
```

- [ ] **Bước 3: Đăng ký ba handler mới**

Trong nơi ánh xạ `rule_type` → lớp handler (đọc `ServiceReqScanner` hoặc registry tương
ứng), thêm ba dòng cho `BhytServiceNameRule`, `BhytDrugNameRule`, `BhytSupplyNameRule` theo
đúng cách bốn quy tắc mã đang được đăng ký.

- [ ] **Bước 4: Thêm ba handler vào lệnh đếm thử và in cảnh báo ngày**

Trong `OrderCheckDryRun::handle()`, thêm ba lớp mới vào mảng handler chạy thử. Sau khi đếm
xong, thêm phần kiểm định dạng ngày:

```php
    /**
     * Danh muc ghi tu_ngay tho tu o Excel, khong chuan hoa. Neu ti le doc duoc thap thi
     * loc hieu luc tu vo hieu hoa mot cach im lang - phai bao cho nguoi chay biet.
     */
    protected function canhBaoDinhDangNgay()
    {
        $bang = [
            'service_catalogs' => 'tu_ngay',
            'medicine_catalogs' => 'tu_ngay',
            'medical_supply_catalogs' => 'tu_ngay',
        ];

        foreach ($bang as $t => $cot) {
            $mau = DB::table($t)->limit(500)->pluck($cot);

            if ($mau->isEmpty()) {
                $this->warn(sprintf('  %-24s bang RONG - quy tac lien quan se im lang', $t));
                continue;
            }

            $doc = 0;

            foreach ($mau as $gt) {
                if (NgayHieuLuc::phanTich($gt) !== null) {
                    $doc++;
                }
            }

            $ti = $doc / $mau->count() * 100;
            $dong = sprintf('  %-24s doc duoc %d/%d (%.1f%%)', $t, $doc, $mau->count(), $ti);

            if ($ti < 50) {
                $this->error($dong . ' - LOC HIEU LUC GAN NHU VO HIEU');
            } else {
                $this->line($dong);
            }
        }
    }
```

Gọi `$this->canhBaoDinhDangNgay();` ở đầu `handle()`, trước phần đếm, dưới tiêu đề
`$this->info('Kiem dinh dang ngay hieu luc cua danh muc:');`.

Thêm `use App\Services\OrderCheck\Support\NgayHieuLuc;` và `use DB;` nếu chưa có.

- [ ] **Bước 5: Chạy toàn bộ bộ Unit**

```bash
vendor/bin/phpunit --testsuite Unit
```

Toàn bộ phải xanh. Ghi lại số test trước và sau để đối chiếu.

- [ ] **Bước 6: Chạy thử trên HIS thật**

```bash
php artisan kiemtraylenh:thu --ngay=7 --lo=2000
```

Kỳ vọng: ba bảng danh mục báo RỖNG, bảy quy tắc đều đếm 0 trừ `A_BHYT_CODE_MISSING`.
Không được ném ngoại lệ.

**Không commit.** Báo lại cho người dùng để review.
