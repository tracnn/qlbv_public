# Kế hoạch: tối ưu chức năng nhập danh mục

Spec: `docs/superpowers/specs/2026-07-28-toi-uu-nhap-danh-muc-design.md`

**Mục tiêu:** sửa lỗi chặn tính năng danh mục theo cơ sở, chặn bộ nhớ khi đọc tệp lớn, ghi
theo lô, và báo kết quả nhập thật thay vì nuốt lỗi.

**Kiến trúc:** giữ `CatalogImportService` làm điểm vào. Tách ba trách nhiệm ra lớp riêng để
kiểm được: đọc theo lô (`CatalogChunkImport`), ghi theo lô (`GhiTheoLo`), gom kết quả
(`KetQuaNhapDanhMuc`).

**Thứ tự có chủ đích:** Task 1 sửa lỗi ràng buộc UNIQUE **trước**. Nó đang chặn tính năng
vừa làm, và các task sau cần chèn được dòng của hai cơ sở mới kiểm chứng được.

## Ràng buộc chung

- Cổng: `vendor/bin/phpunit --testsuite Unit`. Chạy trước để ghi số nền.
- Bình luận mã nguồn viết tiếng Việt không dấu.
- Test dùng `/** @test */`.
- Test chèn dữ liệu thật phải dọn trong `finally`; `medicine_catalogs` có 9 cột NOT NULL
  không mặc định nên phải điền đủ.
- **Không** chuẩn hoá cột khoá thành `NOT NULL`, **không** dùng `ON DUPLICATE KEY UPDATE` —
  spec mục 4.3.
- Không commit cho tới khi người dùng yêu cầu.

---

## Task 1: Mở rộng ràng buộc UNIQUE cho `ma_cskcb`

**Tệp:**
- Tạo: `database/migrations/2026_07_28_140000_them_ma_cskcb_vao_unique_danh_muc.php`
- Test: `tests/Unit/NhapDanhMucUniqueTest.php`

**Interfaces:**
- Produces: ba ràng buộc UNIQUE có `ma_cskcb`

Đây là lỗi đang chặn tính năng danh mục theo cơ sở: cấu hình đã đưa `ma_cskcb` vào
`unique_keys` nhưng ràng buộc CSDL chưa có, nên cơ sở thứ hai bị từ chối và dòng bị bỏ im
lặng.

Index mới rộng hơn index cũ nên mọi dữ liệu đang hợp lệ vẫn hợp lệ — không cần dọn trước.

- [ ] **Bước 1: Viết test đỏ**

```php
<?php

namespace Tests\Unit;

use DB;
use Tests\TestCase;

class NhapDanhMucUniqueTest extends TestCase
{
    /** medicine_catalogs co 9 cot NOT NULL khong mac dinh */
    private function dongThuoc($maCskcb)
    {
        return [
            'ma_thuoc' => 'ZZUNQ', 'ten_hoat_chat' => 'X', 'ten_thuoc' => 'X',
            'don_vi_tinh' => 'Vien', 'ham_luong' => '1', 'duong_dung' => 'Uong',
            'ma_duong_dung' => '1', 'dang_bao_che' => 'Vien', 'so_dang_ky' => 'SDK',
            'don_gia_bh' => 10, 'tt_thau' => 'T', 'tu_ngay' => '20240101',
            'ma_cskcb' => $maCskcb,
        ];
    }

    /** @test */
    public function hai_co_so_cung_bo_khoa_cu_van_chen_duoc_hai_dong()
    {
        // Ca tai hien loi: rang buoc UNIQUE cu khong co ma_cskcb nen co so thu hai bi tu
        // choi, loi bi catch nuot va dong bi bo IM LANG.
        try {
            DB::table('medicine_catalogs')->insert($this->dongThuoc('01929'));
            DB::table('medicine_catalogs')->insert($this->dongThuoc('37470'));

            $this->assertSame(2, DB::table('medicine_catalogs')->where('ma_thuoc', 'ZZUNQ')->count());
        } finally {
            DB::table('medicine_catalogs')->where('ma_thuoc', 'ZZUNQ')->delete();
        }
    }

    /** @test */
    public function cung_co_so_cung_bo_khoa_thi_bi_chan()
    {
        try {
            DB::table('medicine_catalogs')->insert($this->dongThuoc('01929'));

            $nem = false;

            try {
                DB::table('medicine_catalogs')->insert($this->dongThuoc('01929'));
            } catch (\Exception $e) {
                $nem = true;
            }

            $this->assertTrue($nem, 'Trung hoan toan ma van chen duoc');
        } finally {
            DB::table('medicine_catalogs')->where('ma_thuoc', 'ZZUNQ')->delete();
        }
    }

    /** @test */
    public function ba_rang_buoc_unique_deu_co_ma_cskcb()
    {
        $canCo = [
            'medicine_catalogs' => 'unique_medicine_catalog',
            'medical_supply_catalogs' => 'unique_medical_supply',
        ];

        foreach ($canCo as $bang => $ten) {
            $cot = [];

            foreach (DB::select('SHOW INDEX FROM ' . $bang) as $i) {
                if ($i->Key_name === $ten) { $cot[] = $i->Column_name; }
            }

            $this->assertContains('ma_cskcb', $cot, "Rang buoc $ten chua co ma_cskcb");
        }

        // service_catalogs: ten index do Laravel tu dat, tim theo cot dan dau.
        $co = false;

        foreach (DB::select('SHOW INDEX FROM service_catalogs') as $i) {
            if ($i->Column_name === 'ma_cskcb' && !$i->Non_unique) { $co = true; }
        }

        $this->assertTrue($co, 'service_catalogs chua co ma_cskcb trong rang buoc UNIQUE');
    }
}
```

- [ ] **Bước 2: Chạy để xác nhận đỏ**

```bash
vendor/bin/phpunit tests/Unit/NhapDanhMucUniqueTest.php
```

Kỳ vọng: ca đầu đỏ với `Duplicate entry ... for key 'unique_medicine_catalog'`.

- [ ] **Bước 3: Viết migration**

Đọc tên index thật bằng `SHOW INDEX` trước khi viết — tên của `service_catalogs` do Laravel
tự đặt và rất dài.

```php
<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Them ma_cskcb vao rang buoc UNIQUE cua ba danh muc theo co so.
 *
 * Dot truoc da them ma_cskcb vao unique_keys trong config/catalog_import_mapping.php nhung
 * KHONG mo rong rang buoc CSDL, nen nhap danh muc cua co so thu hai bi tu choi:
 *   Duplicate entry '...' for key 'unique_medicine_catalog'
 * Loi do bi catch nuot trong CatalogImportService roi continue - dong bi bo IM LANG.
 *
 * Index moi RONG HON index cu nen moi to hop dang hop le van hop le; khong can don du lieu.
 */
class ThemMaCskcbVaoUniqueDanhMuc extends Migration
{
    public function up()
    {
        Schema::table('medicine_catalogs', function (Blueprint $t) {
            $t->dropUnique('unique_medicine_catalog');
            $t->unique(['ma_thuoc', 'ten_thuoc', 'ham_luong', 'so_dang_ky', 'don_gia_bh',
                'tt_thau', 'tu_ngay', 'ma_cskcb'], 'unique_medicine_catalog');
        });

        Schema::table('medical_supply_catalogs', function (Blueprint $t) {
            $t->dropUnique('unique_medical_supply');
            $t->unique(['ma_vat_tu', 'ten_vat_tu', 'tt_thau', 'don_gia_bh', 'tu_ngay',
                'ma_cskcb'], 'unique_medical_supply');
        });

        Schema::table('service_catalogs', function (Blueprint $t) {
            $t->dropUnique('service_catalogs_ma_dich_vu_don_gia_quy_trinh_tu_ngay_unique');
            $t->unique(['ma_dich_vu', 'ten_dich_vu', 'don_gia', 'quy_trinh', 'tu_ngay',
                'ma_cskcb'], 'unique_service_catalog');
        });
    }

    public function down()
    {
        Schema::table('medicine_catalogs', function (Blueprint $t) {
            $t->dropUnique('unique_medicine_catalog');
            $t->unique(['ma_thuoc', 'ten_thuoc', 'ham_luong', 'so_dang_ky', 'don_gia_bh',
                'tt_thau', 'tu_ngay'], 'unique_medicine_catalog');
        });

        Schema::table('medical_supply_catalogs', function (Blueprint $t) {
            $t->dropUnique('unique_medical_supply');
            $t->unique(['ma_vat_tu', 'ten_vat_tu', 'tt_thau', 'don_gia_bh', 'tu_ngay'],
                'unique_medical_supply');
        });

        Schema::table('service_catalogs', function (Blueprint $t) {
            $t->dropUnique('unique_service_catalog');
            $t->unique(['ma_dich_vu', 'ten_dich_vu', 'don_gia', 'quy_trinh', 'tu_ngay'],
                'service_catalogs_ma_dich_vu_don_gia_quy_trinh_tu_ngay_unique');
        });
    }
}
```

- [ ] **Bước 4: Chạy migration và test**

```bash
php artisan migrate
```

```bash
vendor/bin/phpunit tests/Unit/NhapDanhMucUniqueTest.php
```

---

## Task 2: Lớp kết quả nhập

**Tệp:**
- Tạo: `app/Services/Import/KetQuaNhapDanhMuc.php`
- Test: `tests/Unit/Import/KetQuaNhapDanhMucTest.php`

**Interfaces:**
- Produces: `KetQuaNhapDanhMuc` với `themNhap()`, `themCapNhat()`, `themKhongDoi()`,
  `themBoQua($dongExcel, $lyDo)`, `themLoi($dongExcel, $lyDo)`, `toArray()`, `tomTat()`

Làm trước vì các task sau đều ghi vào nó.

- [ ] **Bước 1: Viết test đỏ**

```php
<?php

namespace Tests\Unit\Import;

use Tests\TestCase;
use App\Services\Import\KetQuaNhapDanhMuc;

class KetQuaNhapDanhMucTest extends TestCase
{
    /** @test */
    public function moi_tao_thi_moi_so_deu_bang_khong()
    {
        $k = new KetQuaNhapDanhMuc();
        $a = $k->toArray();

        foreach (['so_da_nhap', 'so_da_cap_nhat', 'so_khong_doi', 'so_bo_qua', 'so_loi'] as $x) {
            $this->assertSame(0, $a[$x], $x);
        }

        $this->assertSame([], $a['dong_loi']);
    }

    /** @test */
    public function dem_dung_tung_loai()
    {
        $k = new KetQuaNhapDanhMuc();
        $k->themNhap();
        $k->themNhap();
        $k->themCapNhat();
        $k->themKhongDoi();
        $k->themBoQua(5, 'Thieu MA_THUOC');
        $k->themLoi(9, 'Loi ghi');

        $a = $k->toArray();

        $this->assertSame(2, $a['so_da_nhap']);
        $this->assertSame(1, $a['so_da_cap_nhat']);
        $this->assertSame(1, $a['so_khong_doi']);
        $this->assertSame(1, $a['so_bo_qua']);
        $this->assertSame(1, $a['so_loi']);
    }

    /** @test */
    public function ghi_so_dong_excel_de_nguoi_dung_mo_tep_sua_duoc()
    {
        $k = new KetQuaNhapDanhMuc();
        $k->themLoi(42, 'Trung khoa');

        $a = $k->toArray();

        $this->assertSame([['dong' => 42, 'ly_do' => 'Trung khoa']], $a['dong_loi']);
    }

    /** @test */
    public function cat_danh_sach_dong_loi_nhung_van_dem_du()
    {
        $k = new KetQuaNhapDanhMuc();

        for ($i = 1; $i <= 50; $i++) {
            $k->themLoi($i, 'Loi ' . $i);
        }

        $a = $k->toArray();

        $this->assertSame(50, $a['so_loi'], 'Phai dem du');
        $this->assertCount(KetQuaNhapDanhMuc::TOI_DA_DONG_LOI, $a['dong_loi']);
    }

    /** @test */
    public function tom_tat_doc_duoc_va_khong_bao_thanh_cong_suong_khi_nhap_0_dong()
    {
        $rong = new KetQuaNhapDanhMuc();
        $this->assertContains('0', $rong->tomTat());

        $co = new KetQuaNhapDanhMuc();
        $co->themNhap();
        $this->assertContains('1', $co->tomTat());
    }

    /** @test */
    public function co_ghi_nhan_gi_khong()
    {
        $rong = new KetQuaNhapDanhMuc();
        $this->assertFalse($rong->coGhi());

        $co = new KetQuaNhapDanhMuc();
        $co->themCapNhat();
        $this->assertTrue($co->coGhi());
    }
}
```

- [ ] **Bước 2: Chạy để xác nhận đỏ**

```bash
vendor/bin/phpunit tests/Unit/Import/KetQuaNhapDanhMucTest.php
```

- [ ] **Bước 3: Viết lớp**

```php
<?php

namespace App\Services\Import;

/**
 * Gom ket qua mot lan nhap danh muc.
 *
 * Ly do ton tai: truoc day ba vong lap nhap deu catch { Log::error; continue; } va
 * controller luon tra 'File da upload va xu ly thanh cong!'. Mot tep co the nhap 0 dong ma
 * giao dien van bao thanh cong.
 */
class KetQuaNhapDanhMuc
{
    /** So dong loi giu lai de hien thi; van dem du o soLoi */
    const TOI_DA_DONG_LOI = 20;

    protected $soDaNhap = 0;
    protected $soDaCapNhat = 0;
    protected $soKhongDoi = 0;
    protected $soBoQua = 0;
    protected $soLoi = 0;

    /** @var array [['dong' => int, 'ly_do' => string], ...] */
    protected $dongLoi = [];

    public function themNhap()      { $this->soDaNhap++; }
    public function themCapNhat()   { $this->soDaCapNhat++; }
    public function themKhongDoi()  { $this->soKhongDoi++; }

    /** @param int $dongExcel vi tri that trong tep, dong tieu de la 1 */
    public function themBoQua($dongExcel, $lyDo)
    {
        $this->soBoQua++;
        $this->ghiDongLoi($dongExcel, $lyDo);
    }

    public function themLoi($dongExcel, $lyDo)
    {
        $this->soLoi++;
        $this->ghiDongLoi($dongExcel, $lyDo);
    }

    protected function ghiDongLoi($dongExcel, $lyDo)
    {
        if (count($this->dongLoi) >= self::TOI_DA_DONG_LOI) {
            return;
        }

        $this->dongLoi[] = ['dong' => (int) $dongExcel, 'ly_do' => (string) $lyDo];
    }

    /** Co ghi duoc gi vao co so du lieu khong */
    public function coGhi()
    {
        return $this->soDaNhap > 0 || $this->soDaCapNhat > 0;
    }

    public function toArray()
    {
        return [
            'so_da_nhap' => $this->soDaNhap,
            'so_da_cap_nhat' => $this->soDaCapNhat,
            'so_khong_doi' => $this->soKhongDoi,
            'so_bo_qua' => $this->soBoQua,
            'so_loi' => $this->soLoi,
            'dong_loi' => $this->dongLoi,
        ];
    }

    public function tomTat()
    {
        return sprintf(
            'Đã thêm %d, cập nhật %d, không đổi %d, bỏ qua %d, lỗi %d.',
            $this->soDaNhap, $this->soDaCapNhat, $this->soKhongDoi, $this->soBoQua, $this->soLoi
        );
    }
}
```

- [ ] **Bước 4: Chạy lại, phải xanh**

```bash
vendor/bin/phpunit tests/Unit/Import/KetQuaNhapDanhMucTest.php
```

---

## Task 3: Ghi theo lô

**Tệp:**
- Tạo: `app/Services/Import/GhiTheoLo.php`
- Test: `tests/Unit/Import/GhiTheoLoTest.php`

**Interfaces:**
- Consumes: `KetQuaNhapDanhMuc` (Task 2)
- Produces: `GhiTheoLo::khoaDong(array $dong, array $cotKhoa): string`,
  `GhiTheoLo::coThayDoi(array $moi, $cu): bool`, và phương thức `ghi(array $loDong)`

Phần dựng khoá và so nội dung là **hàm thuần tĩnh** để kiểm được mà không cần cơ sở dữ liệu.
Phần chạm cơ sở dữ liệu để riêng.

- [ ] **Bước 1: Viết test đỏ cho hai hàm thuần**

```php
<?php

namespace Tests\Unit\Import;

use Tests\TestCase;
use App\Services\Import\GhiTheoLo;

class GhiTheoLoTest extends TestCase
{
    /** @test */
    public function khoa_dong_gom_dung_cac_cot_khoa()
    {
        $a = GhiTheoLo::khoaDong(['ma' => 'A', 'ten' => 'X', 'gia' => 10], ['ma', 'ten']);
        $b = GhiTheoLo::khoaDong(['ma' => 'A', 'ten' => 'X', 'gia' => 99], ['ma', 'ten']);

        $this->assertSame($a, $b, 'Cot ngoai khoa khong duoc anh huong');
    }

    /** @test */
    public function khoa_dong_phan_biet_khi_mot_cot_khoa_khac()
    {
        $a = GhiTheoLo::khoaDong(['ma' => 'A', 'cs' => '01929'], ['ma', 'cs']);
        $b = GhiTheoLo::khoaDong(['ma' => 'A', 'cs' => '37470'], ['ma', 'cs']);

        $this->assertNotSame($a, $b);
    }

    /** @test */
    public function khoa_dong_bo_khoang_trang_thua()
    {
        $a = GhiTheoLo::khoaDong(['ma' => '  A  '], ['ma']);
        $b = GhiTheoLo::khoaDong(['ma' => 'A'], ['ma']);

        $this->assertSame($a, $b);
    }

    /** @test */
    public function khoa_dong_coi_null_va_chuoi_rong_la_mot()
    {
        $a = GhiTheoLo::khoaDong(['ma' => 'A', 'cs' => null], ['ma', 'cs']);
        $b = GhiTheoLo::khoaDong(['ma' => 'A', 'cs' => ''], ['ma', 'cs']);

        $this->assertSame($a, $b);
    }

    /** @test */
    public function khoa_dong_thieu_cot_thi_khong_no()
    {
        $this->assertInternalType('string', GhiTheoLo::khoaDong(['ma' => 'A'], ['ma', 'khong_co']));
    }

    /** @test */
    public function khong_thay_doi_thi_khong_can_cap_nhat()
    {
        $cu = (object) ['ten' => 'X', 'gia' => '10'];

        $this->assertFalse(GhiTheoLo::coThayDoi(['ten' => 'X', 'gia' => '10'], $cu));
    }

    /** @test */
    public function so_sanh_khong_phan_biet_kieu_so_va_chuoi()
    {
        // Gia tri tu Excel la chuoi, tu CSDL co the la so.
        $cu = (object) ['gia' => 10];

        $this->assertFalse(GhiTheoLo::coThayDoi(['gia' => '10'], $cu));
    }

    /** @test */
    public function so_sanh_coi_null_va_chuoi_rong_la_mot()
    {
        $cu = (object) ['ghi_chu' => null];

        $this->assertFalse(GhiTheoLo::coThayDoi(['ghi_chu' => ''], $cu));
    }

    /** @test */
    public function mot_truong_doi_thi_can_cap_nhat()
    {
        $cu = (object) ['ten' => 'X', 'gia' => '10'];

        $this->assertTrue(GhiTheoLo::coThayDoi(['ten' => 'Y', 'gia' => '10'], $cu));
    }

    /** @test */
    public function truong_moi_chua_co_ben_cu_thi_can_cap_nhat()
    {
        $cu = (object) ['ten' => 'X'];

        $this->assertTrue(GhiTheoLo::coThayDoi(['ten' => 'X', 'gia' => '10'], $cu));
    }

    /** @test */
    public function chi_so_cac_truong_duoc_nhap_khong_so_cot_khac_cua_ban_ghi()
    {
        // Ban ghi cu co created_at, id... khong duoc coi la "thay doi".
        $cu = (object) ['id' => 7, 'ten' => 'X', 'created_at' => '2024-01-01'];

        $this->assertFalse(GhiTheoLo::coThayDoi(['ten' => 'X'], $cu));
    }
}
```

- [ ] **Bước 2: Chạy để xác nhận đỏ**

```bash
vendor/bin/phpunit tests/Unit/Import/GhiTheoLoTest.php
```

- [ ] **Bước 3: Viết lớp**

```php
<?php

namespace App\Services\Import;

use DB;

/**
 * Ghi mot lo dong danh muc: tra mot lan, chen theo lo, chi cap nhat dong thuc su doi.
 *
 * KHONG dung INSERT ... ON DUPLICATE KEY UPDATE du no chi ton mot truy van: cach do dua
 * hoan toan vao rang buoc UNIQUE, ma nhieu cot khoa cua ba danh muc cho phep NULL
 * (don_gia_bh, tt_thau, tu_ngay, quy_trinh, ma_cskcb) - MySQL coi hai NULL la KHAC NHAU nen
 * rang buoc do khong chan duoc. Muon dung thi phai doi cac cot do thanh NOT NULL tren du
 * lieu san xuat: rui ro lon hon han loi ich, vi chenh lech chi la 3 truy van so voi 1 truy
 * van moi 500 dong.
 *
 * Chi phi moi lo: 1 SELECT + 1 INSERT + so truy van cap nhat bang dung so dong DOI.
 * Nhap lai dung tep cu khong sua gi: 1 truy van moi lo, khong ghi gi.
 */
class GhiTheoLo
{
    /** Ky tu ngan cac phan cua khoa; khong xuat hien trong du lieu danh muc */
    const NGAN = "\x1F";

    protected $bang;
    protected $cotKhoa;
    protected $ketQua;

    /** @var array khoa => true, giu xuyen suot lan nhap de khu trung TRONG CUNG TEP */
    protected $daGap = [];

    public function __construct($bang, array $cotKhoa, KetQuaNhapDanhMuc $ketQua)
    {
        $this->bang = $bang;
        $this->cotKhoa = $cotKhoa;
        $this->ketQua = $ketQua;
    }

    /**
     * Khoa so khop cua mot dong, dung cho ca tra CSDL lan khu trung trong tep.
     *
     * Ham THUAN. Chuan hoa: trim, null va chuoi rong la mot.
     */
    public static function khoaDong(array $dong, array $cotKhoa)
    {
        $phan = [];

        foreach ($cotKhoa as $c) {
            $v = isset($dong[$c]) ? $dong[$c] : null;
            $phan[] = trim((string) $v);
        }

        return implode(self::NGAN, $phan);
    }

    /**
     * Dong moi co khac ban ghi dang luu khong.
     *
     * Ham THUAN. Chi so cac truong duoc nhap; id/created_at cua ban ghi cu khong tinh.
     * So bang chuoi sau trim vi gia tri tu Excel la chuoi con tu CSDL co the la so.
     */
    public static function coThayDoi(array $moi, $cu)
    {
        $cu = (array) $cu;

        foreach ($moi as $cot => $v) {
            $vCu = array_key_exists($cot, $cu) ? $cu[$cot] : null;

            if (trim((string) $v) !== trim((string) $vCu)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array $loDong [['dong_excel' => int, 'du_lieu' => array], ...]
     */
    public function ghi(array $loDong)
    {
        if (empty($loDong)) {
            return;
        }

        // Khu trung TRONG CUNG TEP: dong sau trung khoa dong truoc thi ghi de.
        $theoKhoa = [];

        foreach ($loDong as $x) {
            $theoKhoa[self::khoaDong($x['du_lieu'], $this->cotKhoa)] = $x;
        }

        $daCo = $this->traDaCo(array_keys($theoKhoa), $theoKhoa);

        $chen = [];

        foreach ($theoKhoa as $khoa => $x) {
            if (isset($this->daGap[$khoa]) && !isset($daCo[$khoa])) {
                // Da chen o lo truoc trong cung lan nhap; coi nhu da co.
                $this->ketQua->themKhongDoi();
                continue;
            }

            if (!isset($daCo[$khoa])) {
                $chen[] = $x['du_lieu'] + ['created_at' => now(), 'updated_at' => now()];
                $this->daGap[$khoa] = true;
                continue;
            }

            $cu = $daCo[$khoa];

            if (!self::coThayDoi($x['du_lieu'], $cu)) {
                $this->ketQua->themKhongDoi();
                continue;
            }

            try {
                DB::table($this->bang)->where('id', $cu->id)
                    ->update($x['du_lieu'] + ['updated_at' => now()]);
                $this->ketQua->themCapNhat();
            } catch (\Exception $e) {
                $this->ketQua->themLoi($x['dong_excel'], $e->getMessage());
            }
        }

        $this->chenTheoLo($chen, $theoKhoa);
    }

    /** Mot truy van cho ca lo: loc theo cot khoa DAN DAU roi so du bo khoa trong bo nho. */
    protected function traDaCo(array $khoa, array $theoKhoa)
    {
        $cotDau = $this->cotKhoa[0];
        $giaTriDau = [];

        foreach ($theoKhoa as $x) {
            $v = isset($x['du_lieu'][$cotDau]) ? trim((string) $x['du_lieu'][$cotDau]) : '';

            if ($v !== '') {
                $giaTriDau[$v] = true;
            }
        }

        if (empty($giaTriDau)) {
            return [];
        }

        $rows = DB::table($this->bang)->whereIn($cotDau, array_keys($giaTriDau))->get();

        $ra = [];

        foreach ($rows as $r) {
            $ra[self::khoaDong((array) $r, $this->cotKhoa)] = $r;
        }

        return $ra;
    }

    protected function chenTheoLo(array $chen, array $theoKhoa)
    {
        if (empty($chen)) {
            return;
        }

        try {
            DB::table($this->bang)->insert($chen);

            for ($i = 0; $i < count($chen); $i++) {
                $this->ketQua->themNhap();
            }
        } catch (\Exception $e) {
            // Lo hong thi ghi lai tung dong de biet dong nao loi, thay vi mat ca lo.
            foreach ($chen as $d) {
                try {
                    DB::table($this->bang)->insert($d);
                    $this->ketQua->themNhap();
                } catch (\Exception $e2) {
                    $khoa = self::khoaDong($d, $this->cotKhoa);
                    $dong = isset($theoKhoa[$khoa]) ? $theoKhoa[$khoa]['dong_excel'] : 0;
                    $this->ketQua->themLoi($dong, $e2->getMessage());
                }
            }
        }
    }
}
```

- [ ] **Bước 4: Chạy lại, phải xanh**

```bash
vendor/bin/phpunit tests/Unit/Import/GhiTheoLoTest.php
```

- [ ] **Bước 5: Thêm ca kiểm chạm cơ sở dữ liệu**

```php
    /** @test */
    public function chen_moi_roi_nhap_lai_thi_khong_ghi_them()
    {
        $kq = new \App\Services\Import\KetQuaNhapDanhMuc();
        $cotKhoa = ['ma_thuoc', 'ten_thuoc', 'ham_luong', 'so_dang_ky', 'don_gia_bh',
                    'tt_thau', 'tu_ngay', 'ma_cskcb'];

        $d = [
            'ma_thuoc' => 'ZZLO1', 'ten_hoat_chat' => 'X', 'ten_thuoc' => 'X',
            'don_vi_tinh' => 'Vien', 'ham_luong' => '1', 'duong_dung' => 'Uong',
            'ma_duong_dung' => '1', 'dang_bao_che' => 'Vien', 'so_dang_ky' => 'SDK',
            'don_gia_bh' => 10, 'tt_thau' => 'T', 'tu_ngay' => '20240101', 'ma_cskcb' => '01929',
        ];

        try {
            $g = new GhiTheoLo('medicine_catalogs', $cotKhoa, $kq);
            $g->ghi([['dong_excel' => 2, 'du_lieu' => $d]]);

            $this->assertSame(1, $kq->toArray()['so_da_nhap']);

            $kq2 = new \App\Services\Import\KetQuaNhapDanhMuc();
            $g2 = new GhiTheoLo('medicine_catalogs', $cotKhoa, $kq2);
            $g2->ghi([['dong_excel' => 2, 'du_lieu' => $d]]);

            $a = $kq2->toArray();
            $this->assertSame(0, $a['so_da_nhap'], 'Nhap lai ma van chen them');
            $this->assertSame(1, $a['so_khong_doi']);
        } finally {
            DB::table('medicine_catalogs')->where('ma_thuoc', 'ZZLO1')->delete();
        }
    }
```

Thêm `use DB;` vào đầu tệp test.

---

## Task 4: Đọc theo lô

**Tệp:**
- Tạo: `app/Imports/CatalogChunkImport.php`
- Sửa: `app/Services/CatalogImportService.php`
- Test: `tests/Unit/Import/DocTheoLoTest.php`

**Interfaces:**
- Consumes: `GhiTheoLo`, `KetQuaNhapDanhMuc`
- Produces: `CatalogChunkImport` cài `ToCollection`, `WithChunkReading`

Đây là task chặn bộ nhớ: tệp 1,3 MB đang làm đỉnh 208 MB.

- [ ] **Bước 1: Viết test đỏ**

```php
<?php

namespace Tests\Unit\Import;

use Tests\TestCase;
use App\Imports\CatalogChunkImport;

class DocTheoLoTest extends TestCase
{
    /** @test */
    public function lop_nhap_cai_dat_doc_theo_lo()
    {
        $this->assertInstanceOf(
            \Maatwebsite\Excel\Concerns\WithChunkReading::class,
            new CatalogChunkImport()
        );
    }

    /** @test */
    public function co_lo_du_nho_de_khong_vo_bo_nho()
    {
        // Tep 10.000 dong x 23 cot lam dinh bo nho 208 MB khi doc mot lan.
        $co = (new CatalogChunkImport())->chunkSize();

        $this->assertLessThanOrEqual(2000, $co);
        $this->assertGreaterThan(0, $co);
    }

    /** @test */
    public function catalog_import_service_khong_con_doc_ca_tep_mot_lan()
    {
        $ma = file_get_contents(app_path('Services/CatalogImportService.php'));

        $this->assertNotContains('Excel::toCollection', $ma,
            'Van con doc ca tep mot lan - dinh bo nho 208 MB voi tep 1,3 MB');
    }
}
```

- [ ] **Bước 2: Chạy để xác nhận đỏ**

```bash
vendor/bin/phpunit tests/Unit/Import/DocTheoLoTest.php
```

- [ ] **Bước 3: Viết lớp đọc theo lô**

Điểm khó duy nhất: dòng tiêu đề chỉ có ở lô đầu. Lớp giữ trạng thái `$fieldMapping`; lô đầu
tách dòng tiêu đề ra nhận diện loại rồi xử lý phần còn lại, các lô sau xử lý trọn lô.

```php
<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Illuminate\Support\Collection;

/**
 * Doc tep danh muc THEO LO.
 *
 * Excel::toCollection nap toan bo tep: do duoc tep 10.000 dong x 23 cot (1,3 MB) lam DINH
 * bo nho 208 MB - gap ~160 lan co tep. May chu trien khai gan day gioi han PHP 128 MB.
 *
 * Dong tieu de CHI co o lo dau: lop giu $fieldMapping giua cac lo, lo dau tach dong tieu de
 * ra de nhan dien loai danh muc roi xu ly phan con lai.
 */
class CatalogChunkImport implements ToCollection, WithChunkReading
{
    const CO_LO = 1000;

    /** @var callable|null nhan (array $rows, int $dongDau, bool $laLoDau) */
    protected $xuLy;

    /** @var int dem dong da doc, de tinh so dong Excel that */
    protected $daDoc = 0;

    public function __construct(callable $xuLy = null)
    {
        $this->xuLy = $xuLy;
    }

    public function chunkSize(): int
    {
        return self::CO_LO;
    }

    public function collection(Collection $rows)
    {
        $laLoDau = $this->daDoc === 0;
        $dongDau = $this->daDoc + 1;   // dong Excel dau tien cua lo nay
        $this->daDoc += $rows->count();

        if ($this->xuLy !== null) {
            call_user_func($this->xuLy, $rows, $dongDau, $laLoDau);
        }
    }
}
```

- [ ] **Bước 4: Nối vào `CatalogImportService`**

Thay `Excel::toCollection(null, $filePath)` bằng `Excel::import($import, $filePath)` với
`$import` là `CatalogChunkImport` mang hàm xử lý:

- Lô đầu: lấy dòng 1 làm tiêu đề, `detectCatalogType`, `createFieldMapping`, kiểm cột bắt
  buộc (ném ngoại lệ nếu thiếu), rồi xử lý các dòng còn lại của lô.
- Lô sau: xử lý trọn lô.

Mỗi dòng: đổi Collection sang mảng **một lần** (mục 4.6 của spec), kiểm trường bắt buộc,
dựng `$uniqueKeys` + `$updateData`, gom vào lô ghi. Đủ 500 dòng thì gọi `GhiTheoLo::ghi()`.

Số dòng Excel = `$dongDau + $chiSoTrongLo`.

Ba hàm `importMedicine` / `importMedicalSupply` / `importService` gộp về một luồng chung vì
chúng chỉ khác nhau ở tên bảng và bộ khoá — cả hai đều đã nằm trong `$config`. Tám loại còn
lại giữ nguyên đường cũ trong đợt này.

- [ ] **Bước 5: `getRowValue` nhận mảng**

Đổi chữ ký thành `getRowValue(array $row, $field, array $fieldMapping)` và bỏ đoạn
`if ($row instanceof Collection) { $row = $row->toArray(); }`. Người gọi đổi kiểu một lần
mỗi dòng.

`hasRequiredFields` đổi tương ứng.

- [ ] **Bước 6: Chạy lại**

```bash
vendor/bin/phpunit --testsuite Unit
```

---

## Task 5: Trả kết quả nhập ra giao diện

**Tệp:**
- Sửa: `app/Http/Controllers/Category/CategoryBHYTController.php`
- Sửa: `resources/views/category/bhyt/import.blade.php`

- [ ] **Bước 1: Controller trả kết quả**

`import()` gom `KetQuaNhapDanhMuc` của từng tệp, trả JSON:

```php
return response()->json([
    'message' => $tomTat,
    'ket_qua' => $ketQua->toArray(),
], 200);
```

Khi `!$ketQua->coGhi()` thì thông điệp phải nói rõ **không ghi được dòng nào**, không dùng
lại câu "File đã upload và xử lý thành công!".

- [ ] **Bước 2: Màn nhập hiện tóm tắt**

Trong `this.on("success", ...)` của Dropzone, đọc `response.ket_qua` và hiện dòng tóm tắt +
danh sách tối đa 20 dòng lỗi kèm số dòng Excel.

- [ ] **Bước 3: Kiểm bằng tay**

Tạo một tệp Excel nhỏ có ba loại dòng — hợp lệ, thiếu trường bắt buộc, trùng khoá — tải lên
và xác nhận các con số khớp.

---

## Task 6: Đo lại và báo cáo

- [ ] **Bước 1: Đo bộ nhớ sau khi sửa**

Sinh tệp 10.000 dòng × 23 cột trong thư mục scratchpad, chạy nhập, ghi lại đỉnh bộ nhớ. So
với 208 MB trước khi sửa.

- [ ] **Bước 2: Đo thời gian và số truy vấn**

Đếm truy vấn bằng `DB::listen`. So với 4.000 truy vấn / 2.000 dòng trước khi sửa.

- [ ] **Bước 3: Bộ Unit lần cuối**

```bash
vendor/bin/phpunit --testsuite Unit
```

Ghi lại số test trước và sau. Báo cho người dùng, **không commit** cho tới khi được yêu cầu.
