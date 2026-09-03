# Kế hoạch thực thi: MCCT Giai đoạn 1 — Tra cứu tiền cùng chi trả

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Cho phép người dùng tra cứu lũy kế số tiền cùng chi trả BHYT của một người bệnh
trên cổng BHXH, kết luận đã đủ điều kiện miễn cùng chi trả hay chưa, và lưu lại dấu vết mọi
lần tra.

**Architecture:** Một service riêng ở `app/Services/Mcct/` gọi hàm
`POST /api/TraCuuCCT/TraCuuTienMCCT` bằng ba HTTP header (`accessToken`, `tokenId`,
`passwordHash`) và body JSON, dùng lại `BHYTLoginService` sẵn có để lấy token theo từng cơ
sở. Bốn lớp tách bạch: `NguongMienCungChiTra` (hàm thuần), `KetQuaMcct` (DTO),
`McctLuuTraCuu` (CSDL), `McctTraCuuService` (nơi **duy nhất** chạm mạng). Không đụng
`App\BHYT` và sáu điểm gọi `checkInsuranceCard` đang chạy sản xuất.

**Tech Stack:** Laravel 5.5 (PHP 7), Guzzle 6.5, PHPUnit 6.5.14, MySQL, Blade + AdminLTE.

**Spec:** `docs/superpowers/specs/2026-09-03-mcct-giai-doan-1-design.md`

## Global Constraints

Áp dụng cho **mọi** task bên dưới:

- **Chốt an toàn CSDL — tuyệt đối không vi phạm.** Không dùng `RefreshDatabase`,
  `DatabaseMigrations`, hay `migrate:fresh` trong bất kỳ test nào. Bộ test chạy trên schema
  `qlbv_test` (khai trong `phpunit.xml`); CSDL `qlbv` là CSDL phát triển thật và đã từng bị
  xoá sạch vì một test dùng `DatabaseMigrations`.
- **Không dùng Mockery cho tầng HTTP.** Dùng `GuzzleHttp\Handler\MockHandler`. Mockery đã
  nhiều lần vỡ với các lớp khai báo kiểu trả về trong dự án này.
- **Cú pháp test theo dự án:** namespace `Tests\Unit`, kế thừa `Tests\TestCase`, đánh dấu
  bằng chú thích `/** @test */`, tên phương thức viết tiếng Việt không dấu
  (`bang_dung_nguong_thi_chua_du`). Không dùng annotation `@dataProvider` nếu không cần.
- **Lệnh chạy test:** `php vendor/bin/phpunit --filter <TênLớp>` từ thư mục
  `C:\Users\tracnn\qlbv`.
- **Không bao giờ** ghi `accessToken` hoặc `passwordHash` vào log.
- **Ngưỡng dùng dấu `>`** (lớn hơn), không phải `≥`. Lũy kế bằng đúng ngưỡng là **chưa** đủ
  điều kiện miễn cùng chi trả.
- **URL ghép qua `App\Services\BHYT\CongBhxh::url()`**, không khai URL đầy đủ ở đâu cả.
- Chú thích trong mã viết **tiếng Việt không dấu**, theo đúng kiểu các tệp
  `app/Services/BHYT/*.php` và `app/Services/Tt12/*.php` hiện có: giải thích **vì sao**,
  không mô tả lại điều mã đã nói.
- Commit message viết **tiếng Việt không dấu**, kết thúc bằng dòng
  `Co-Authored-By: Claude Opus 5 <noreply@anthropic.com>`.

## Cấu trúc tệp

| Tệp | Trách nhiệm | Task |
|---|---|---|
| `config/mcct.php` | Đường dẫn dịch vụ, số tháng lương cơ sở, bảng lương cơ sở theo mốc, timeout | 1 |
| `app/Services/Mcct/NguongMienCungChiTra.php` | Hàm thuần: ngày → lương cơ sở → ngưỡng → kết luận | 1 |
| `app/Services/Mcct/KetQuaMcct.php` | DTO: phân tích JSON cổng trả về thành đối tượng | 2 |
| `app/Services/BHYTLoginService.php` | *(sửa)* thêm `passwordHash()` | 3 |
| `app/Services/Mcct/McctTraCuuService.php` | Nơi duy nhất chạm mạng; ba header, body JSON, retry 401 | 3 |
| `database/migrations/…_create_mcct_tra_cuu_table.php` | Bảng phiên tra | 4 |
| `database/migrations/…_create_mcct_chi_phi_table.php` | Bảng dòng chi phí | 4 |
| `app/Models/Mcct/McctTraCuu.php`, `McctChiPhi.php` | Model Eloquent | 4 |
| `app/Services/Mcct/McctLuuTraCuu.php` | Ghi phiên tra + dòng chi phí vào CSDL | 4 |
| `app/Http/Requests/McctRequest.php` | Luật kiểm đầu vào | 5 |
| `app/Http/Controllers/Insurance/Manager/McctController.php` | Ghép luồng, ánh xạ lỗi ra thông báo | 6 |
| `resources/views/insurance/manager/mcct/{index,search,result}.blade.php` | Giao diện | 6 |
| `routes/web.php`, `routes/breadcrumbs.php`, `config/adminlte.php` | *(sửa)* route, breadcrumb, menu | 6 |
| `resources/views/insurance/manager/check-card/result.blade.php` | *(sửa)* một liên kết sang màn MCCT | 6 |

Thứ tự task đi từ trong ra ngoài: hai task đầu không chạm gì cả (thuần), task 3 chạm mạng
(mock được), task 4 chạm CSDL, task 5–6 chạm HTTP layer của Laravel. Mỗi task chạy test
được độc lập.

---

## Task 1: Ngưỡng miễn cùng chi trả (hàm thuần)

**Files:**
- Create: `config/mcct.php`
- Create: `app/Services/Mcct/NguongMienCungChiTra.php`
- Test: `tests/Unit/Mcct/NguongMienCungChiTraTest.php`

**Interfaces:**
- Consumes: không có (task đầu tiên)
- Produces:
  - `NguongMienCungChiTra::luongCoSoTaiNgay(string $ngay, array $bangLuong): int` — `$ngay`
    dạng `Y-m-d`; `$bangLuong` dạng `['2023-07-01' => 1800000, ...]`; trả 0 khi ngày nằm
    trước mọi mốc.
  - `NguongMienCungChiTra::nguong(string $ngay, array $bangLuong, int $soThang): float`
  - `NguongMienCungChiTra::duDieuKien(float $luyKe, float $nguong): bool` — dùng dấu `>`.

- [ ] **Step 1: Tạo tệp cấu hình**

Tạo `config/mcct.php`:

```php
<?php

/*
 * Tham so dich vu tra cuu tien cung chi tra (MCCT) tren cong BHXH.
 *
 * KHONG khai host o day. Host nam o organization.BHYT.base_url (tep rieng cua tung may,
 * trong .gitignore) va duoc ghep bang CongBhxh::url(). Khai URL day du o day nghia la mot
 * may doi $bhxhBaseUrl sang daotaoegw nhung rieng MCCT van goi cong THAT.
 */
return [
    // Hang so giao thuc do BHXH quy dinh, giong nhau o moi noi cai dat.
    'duong_dan' => '/api/TraCuuCCT/TraCuuTienMCCT',

    // Nguong mien cung chi tra = 6 thang luong co so (NĐ 188/2025/NĐ-CP).
    'so_thang_luong_co_so' => 6,

    /*
     * Luong co so theo MOC HIEU LUC, khong phai mot so.
     *
     * Khai mot so tran thi lan tang luong tiep theo se lang le tinh sai nguong cho toan bo
     * du lieu cu. Sap tang dan theo ngay.
     */
    'luong_co_so' => [
        '2023-07-01' => 1800000,
        '2024-07-01' => 2340000,
    ],

    'timeout_ket_noi' => 10,
    'timeout_tong' => 30,
];
```

- [ ] **Step 2: Viết test thất bại**

Tạo `tests/Unit/Mcct/NguongMienCungChiTraTest.php`:

```php
<?php

namespace Tests\Unit\Mcct;

use App\Services\Mcct\NguongMienCungChiTra;
use Tests\TestCase;

class NguongMienCungChiTraTest extends TestCase
{
    protected function bangLuong()
    {
        return [
            '2023-07-01' => 1800000,
            '2024-07-01' => 2340000,
        ];
    }

    /** @test */
    public function lay_dung_muc_luong_theo_moc_hieu_luc()
    {
        $this->assertSame(1800000,
            NguongMienCungChiTra::luongCoSoTaiNgay('2024-01-15', $this->bangLuong()));
        $this->assertSame(2340000,
            NguongMienCungChiTra::luongCoSoTaiNgay('2026-09-03', $this->bangLuong()));
    }

    /**
     * Dung NGAY doi luong: moc la '2024-07-01' nen chinh ngay do da ap muc moi.
     * Lech mot ngay o day nghia la tinh sai nguong cho ca mot ngay lam viec.
     */
    /** @test */
    public function dung_ngay_doi_luong_thi_ap_muc_moi()
    {
        $this->assertSame(2340000,
            NguongMienCungChiTra::luongCoSoTaiNgay('2024-07-01', $this->bangLuong()));
        $this->assertSame(1800000,
            NguongMienCungChiTra::luongCoSoTaiNgay('2024-06-30', $this->bangLuong()));
    }

    /**
     * Ngay truoc moi moc: tra 0 chu KHONG roi ve muc dau tien. Roi ve nghia la bia ra mot
     * nguong cho khoang thoi gian chua khai - sai im lang.
     */
    /** @test */
    public function ngay_truoc_moi_moc_thi_tra_khong()
    {
        $this->assertSame(0,
            NguongMienCungChiTra::luongCoSoTaiNgay('2020-01-01', $this->bangLuong()));
    }

    /** @test */
    public function nguong_bang_sau_thang_luong_co_so()
    {
        $this->assertSame(14040000.0,
            NguongMienCungChiTra::nguong('2026-09-03', $this->bangLuong(), 6));
    }

    /** @test */
    public function tren_nguong_thi_du_dieu_kien()
    {
        $this->assertTrue(NguongMienCungChiTra::duDieuKien(14040001, 14040000));
    }

    /**
     * NĐ 188/2025 dung cau chu "LON HON 6 thang luong co so": bang dung nguong la CHUA du.
     * Khac biet nay chi lo ra o dung mot truong hop, va luc lo ra thi da tra loi sai nguoi
     * benh roi.
     */
    /** @test */
    public function bang_dung_nguong_thi_chua_du()
    {
        $this->assertFalse(NguongMienCungChiTra::duDieuKien(14040000, 14040000));
    }

    /** @test */
    public function duoi_nguong_thi_chua_du()
    {
        $this->assertFalse(NguongMienCungChiTra::duDieuKien(12500000, 14040000));
    }

    /**
     * Bang luong rong (cau hinh thieu) thi nguong bang 0, va khi do KHONG duoc ket luan la
     * ai cung du dieu kien. duDieuKien() nhan nguong 0 phai tra false.
     */
    /** @test */
    public function nguong_khong_thi_khong_ket_luan_du()
    {
        $this->assertFalse(NguongMienCungChiTra::duDieuKien(12500000, 0));
    }
}
```

- [ ] **Step 3: Chạy test để xác nhận nó hỏng**

Chạy: `php vendor/bin/phpunit --filter NguongMienCungChiTraTest`
Kỳ vọng: FAIL — `Class 'App\Services\Mcct\NguongMienCungChiTra' not found`

- [ ] **Step 4: Viết cài đặt tối thiểu**

Tạo `app/Services/Mcct/NguongMienCungChiTra.php`:

```php
<?php

namespace App\Services\Mcct;

/**
 * Nguong mien cung chi tra: 6 thang luong co so tai thoi diem tra cuu.
 *
 * Ham THUAN - nhan bang luong lam THAM SO chu khong tu doc config, giong cach CoSoTraCuu
 * nhan $dsCoSo. Nho vay kiem duoc moi moc luong ma khong phai sua cau hinh that.
 */
class NguongMienCungChiTra
{
    /**
     * Muc luong co so co hieu luc tai mot ngay.
     *
     * Tra 0 khi ngay nam TRUOC moi moc. KHONG roi ve moc dau tien: roi ve nghia la bia ra
     * mot nguong cho khoang thoi gian chua khai, va cai sai do khong co dau hieu gi.
     *
     * @param string $ngay dang Y-m-d
     * @param array $bangLuong ['Y-m-d' => muc luong], config('mcct.luong_co_so')
     * @return int
     */
    public static function luongCoSoTaiNgay($ngay, array $bangLuong)
    {
        $ngay = trim((string) $ngay);
        $muc = 0;

        // Duyet theo thu tu moc tang dan, giu lai moc cuoi cung con <= ngay can tra.
        // Sap lai tai day chu khong tin thu tu nguoi khai go trong config.
        ksort($bangLuong);

        foreach ($bangLuong as $moc => $gia) {
            if (strcmp((string) $moc, $ngay) <= 0) {
                $muc = (int) $gia;
            }
        }

        return $muc;
    }

    /**
     * @param string $ngay dang Y-m-d
     * @param array $bangLuong ['Y-m-d' => muc luong]
     * @param int $soThang config('mcct.so_thang_luong_co_so')
     * @return float
     */
    public static function nguong($ngay, array $bangLuong, $soThang)
    {
        return (float) (self::luongCoSoTaiNgay($ngay, $bangLuong) * (int) $soThang);
    }

    /**
     * NĐ 188/2025/NĐ-CP dung cau chu "LON HON 6 thang luong co so" - dau > chu khong phai
     * >=. Bang dung nguong la CHUA du dieu kien.
     *
     * Nguong 0 (bang luong chua khai) luon tra false: khong co nguong thi khong ket luan
     * duoc, va ket luan "ai cung du" la sai theo huong te nhat.
     *
     * @param float $luyKe
     * @param float $nguong
     * @return bool
     */
    public static function duDieuKien($luyKe, $nguong)
    {
        if ((float) $nguong <= 0) {
            return false;
        }

        return (float) $luyKe > (float) $nguong;
    }
}
```

- [ ] **Step 5: Chạy test để xác nhận nó xanh**

Chạy: `php vendor/bin/phpunit --filter NguongMienCungChiTraTest`
Kỳ vọng: PASS — 8 test, 10 assertion

- [ ] **Step 6: Commit**

```bash
git add config/mcct.php app/Services/Mcct/NguongMienCungChiTra.php tests/Unit/Mcct/NguongMienCungChiTraTest.php
git commit -m "feat(mcct): nguong mien cung chi tra theo moc luong co so

Ham thuan, nhan bang luong lam tham so. Dau > chu khong phai >= theo cau
chu NĐ 188/2025. Nguong 0 (chua khai cau hinh) luon tra chua du.

Co-Authored-By: Claude Opus 5 <noreply@anthropic.com>"
```

---

## Task 2: DTO KetQuaMcct — phân tích JSON cổng trả về

**Files:**
- Create: `app/Services/Mcct/KetQuaMcct.php`
- Test: `tests/Unit/Mcct/McctPhanTichKetQuaTest.php`

**Interfaces:**
- Consumes: không có
- Produces:
  - `KetQuaMcct::tuMang(array $json): KetQuaMcct`
  - Thuộc tính công khai (đọc): `maKetQua` (string), `ghiChu` (string), `thongTinThe`
    (array — bốn khoá `ho_ten`, `ngay_sinh`, `ngay_ket_thuc`, `ma_bhxh`; mảng rỗng khi cổng
    trả `null`), `dong` (array các mảng con, mỗi mảng có khoá: `id_cong`, `ngay_tra_cuu`,
    `ma_the`, `ma_cskcb`, `ngay_vao`, `ngay_ra`, `ma_doi_tuong_kcb`, `t_bn_cct_mcct`,
    `t_bn_cct_luy_ke`, `ngay_nhan_cong`, `ngay_nhan`)
  - `KetQuaMcct::luyKeLonNhat(): float` — 0.0 khi không có dòng nào
  - `KetQuaMcct::thanhCong(): bool` — đúng khi `maKetQua === '200'`
  - Ngày trả về dạng `Y-m-d` hoặc `null`; tiền trả về `float`.

- [ ] **Step 1: Viết test thất bại**

Tạo `tests/Unit/Mcct/McctPhanTichKetQuaTest.php`:

```php
<?php

namespace Tests\Unit\Mcct;

use App\Services\Mcct\KetQuaMcct;
use Tests\TestCase;

class McctPhanTichKetQuaTest extends TestCase
{
    /** Nguyen van vi du trong phu luc mo ta API, khong rut gon */
    protected function jsonThanhCong()
    {
        return [
            'MaKetQua' => '200',
            'GhiChu' => 'Nguồn DL lấy từ các CSKCB đề nghị thanh toán KCB BHYT trên HTTTGĐ BHYT tính đến: 05/08/2026 17:30',
            'DataCCT' => [
                [
                    'Id' => 123456,
                    'ngayTraCuu' => '10/08/2026',
                    'maThe' => 'DN4010100000001',
                    'maCskcb' => '01001',
                    'ngayVao' => '02/04/2026',
                    'ngayRa' => '05/04/2026',
                    'maDoiTuongKCB' => 'DN',
                    'tBNCCTMCCT' => 250000,
                    'tBNCCTLuyKe' => 1250000,
                    'ngayNhanCong' => '10/04/2026',
                    'ngayNhan' => '10/04/2026',
                    'duPhong1' => '', 'duPhong2' => '', 'duPhong3' => '',
                    'duPhong4' => '', 'duPhong5' => '',
                ],
            ],
            'ThongTinSoThe' => [
                'hoTen' => 'Nguyễn Văn A',
                'ngaySinh' => '01/01/1990',
                'ngayKetThuc' => '31/12/2026',
                'maBhxh' => '0100000001',
                'duPhong1' => '', 'duPhong2' => '', 'duPhong3' => '',
                'duPhong4' => '', 'duPhong5' => '',
            ],
        ];
    }

    /** @test */
    public function doc_dung_ma_ket_qua_va_ghi_chu()
    {
        $kq = KetQuaMcct::tuMang($this->jsonThanhCong());

        $this->assertSame('200', $kq->maKetQua);
        $this->assertTrue($kq->thanhCong());
        $this->assertContains('tính đến: 05/08/2026 17:30', $kq->ghiChu);
    }

    /** @test */
    public function doc_dung_thong_tin_the()
    {
        $kq = KetQuaMcct::tuMang($this->jsonThanhCong());

        $this->assertSame('Nguyễn Văn A', $kq->thongTinThe['ho_ten']);
        $this->assertSame('01/01/1990', $kq->thongTinThe['ngay_sinh']);
        $this->assertSame('2026-12-31', $kq->thongTinThe['ngay_ket_thuc']);
        $this->assertSame('0100000001', $kq->thongTinThe['ma_bhxh']);
    }

    /** @test */
    public function doi_ngay_sang_dang_csdl_va_tien_sang_so()
    {
        $kq = KetQuaMcct::tuMang($this->jsonThanhCong());
        $d = $kq->dong[0];

        $this->assertSame(123456, $d['id_cong']);
        $this->assertSame('2026-04-02', $d['ngay_vao']);
        $this->assertSame('2026-04-05', $d['ngay_ra']);
        $this->assertSame(250000.0, $d['t_bn_cct_mcct']);
        $this->assertSame(1250000.0, $d['t_bn_cct_luy_ke']);
    }

    /** Nam truong duPhong luon rong theo phu luc - khong duoc mang vao DTO */
    /** @test */
    public function bo_qua_cac_truong_du_phong()
    {
        $kq = KetQuaMcct::tuMang($this->jsonThanhCong());

        $this->assertArrayNotHasKey('duPhong1', $kq->dong[0]);
        $this->assertArrayNotHasKey('duPhong1', $kq->thongTinThe);
    }

    /** Phu luc ghi ro: chuoi rong khi khong co gia tri. Phai thanh null, khong phai '0000-00-00' */
    /** @test */
    public function chuoi_ngay_rong_thanh_null()
    {
        $json = $this->jsonThanhCong();
        $json['DataCCT'][0]['ngayRa'] = '';

        $kq = KetQuaMcct::tuMang($json);

        $this->assertNull($kq->dong[0]['ngay_ra']);
    }

    /** Cong co the tra so duoi dang chuoi - khong duoc de lot xuong CSDL thanh chuoi */
    /** @test */
    public function tien_dang_chuoi_van_thanh_so()
    {
        $json = $this->jsonThanhCong();
        $json['DataCCT'][0]['tBNCCTLuyKe'] = '1250000.50';

        $kq = KetQuaMcct::tuMang($json);

        $this->assertSame(1250000.5, $kq->dong[0]['t_bn_cct_luy_ke']);
    }

    /**
     * Ma 204: phu luc ghi ro DataCCT va ThongTinSoThe deu tra ve NULL. Truoc day mot mang
     * null vao foreach la loi chet nguoi - phai chiu duoc.
     */
    /** @test */
    public function ma_204_voi_du_lieu_null_khong_no()
    {
        $kq = KetQuaMcct::tuMang([
            'MaKetQua' => '204',
            'GhiChu' => 'Không tìm thấy dữ liệu!',
            'DataCCT' => null,
            'ThongTinSoThe' => null,
        ]);

        $this->assertSame('204', $kq->maKetQua);
        $this->assertFalse($kq->thanhCong());
        $this->assertSame([], $kq->dong);
        $this->assertSame([], $kq->thongTinThe);
        $this->assertSame(0.0, $kq->luyKeLonNhat());
    }

    /** Phan hoi thieu han khoa (vd 401 than rong da duoc dung thanh mang) cung khong duoc no */
    /** @test */
    public function mang_rong_khong_no()
    {
        $kq = KetQuaMcct::tuMang([]);

        $this->assertSame('', $kq->maKetQua);
        $this->assertSame([], $kq->dong);
        $this->assertSame(0.0, $kq->luyKeLonNhat());
    }

    /** @test */
    public function luy_ke_lon_nhat_lay_gia_tri_max_chu_khong_lay_dong_dau()
    {
        $json = $this->jsonThanhCong();
        $json['DataCCT'][] = $json['DataCCT'][0];
        $json['DataCCT'][1]['tBNCCTLuyKe'] = 3000000;

        $kq = KetQuaMcct::tuMang($json);

        $this->assertSame(3000000.0, $kq->luyKeLonNhat());
    }
}
```

- [ ] **Step 2: Chạy test để xác nhận nó hỏng**

Chạy: `php vendor/bin/phpunit --filter McctPhanTichKetQuaTest`
Kỳ vọng: FAIL — `Class 'App\Services\Mcct\KetQuaMcct' not found`

- [ ] **Step 3: Viết cài đặt tối thiểu**

Tạo `app/Services/Mcct/KetQuaMcct.php`:

```php
<?php

namespace App\Services\Mcct;

/**
 * Ket qua mot lan tra cuu MCCT, da phan tich tu JSON cong tra ve.
 *
 * VI SAO CO LOP NAY thay vi dung thang mang JSON: cong tra ngay dang dd/MM/yyyy, tien co
 * the la chuoi, va DataCCT/ThongTinSoThe co the la NULL. De mang tho di xuyen qua controller
 * xuong CSDL nghia la moi noi dung deu phai tu doan lai nhung dieu do - va chi mot noi quen
 * la du lieu hong lang le.
 *
 * Lop nay KHONG biet gi ve CSDL va KHONG cham mang.
 */
class KetQuaMcct
{
    /** @var string Ma ket qua cong tra ve: 200/204/400/500; chuoi rong neu khong doc duoc */
    public $maKetQua = '';

    /** @var string GhiChu NGUYEN VAN - chua moc "du lieu tinh den ...", khong duoc rut gon */
    public $ghiChu = '';

    /** @var array bon khoa ho_ten/ngay_sinh/ngay_ket_thuc/ma_bhxh; rong khi cong tra null */
    public $thongTinThe = [];

    /** @var array cac dong DataCCT da chuan hoa */
    public $dong = [];

    /**
     * @param array $json mang da json_decode tu than phan hoi
     * @return self
     */
    public static function tuMang(array $json)
    {
        $kq = new self();

        $kq->maKetQua = isset($json['MaKetQua']) ? trim((string) $json['MaKetQua']) : '';
        $kq->ghiChu = isset($json['GhiChu']) ? (string) $json['GhiChu'] : '';

        $the = isset($json['ThongTinSoThe']) && is_array($json['ThongTinSoThe'])
            ? $json['ThongTinSoThe'] : [];

        if ($the !== []) {
            $kq->thongTinThe = [
                'ho_ten' => self::chuoi($the, 'hoTen'),
                'ngay_sinh' => self::chuoi($the, 'ngaySinh'),
                'ngay_ket_thuc' => self::ngay($the, 'ngayKetThuc'),
                'ma_bhxh' => self::chuoi($the, 'maBhxh'),
            ];
        }

        $ds = isset($json['DataCCT']) && is_array($json['DataCCT']) ? $json['DataCCT'] : [];

        foreach ($ds as $d) {
            if (!is_array($d)) {
                continue;
            }

            $kq->dong[] = [
                'id_cong' => isset($d['Id']) ? (int) $d['Id'] : null,
                'ngay_tra_cuu' => self::ngay($d, 'ngayTraCuu'),
                'ma_the' => self::chuoi($d, 'maThe'),
                'ma_cskcb' => self::chuoi($d, 'maCskcb'),
                'ngay_vao' => self::ngay($d, 'ngayVao'),
                'ngay_ra' => self::ngay($d, 'ngayRa'),
                'ma_doi_tuong_kcb' => self::chuoi($d, 'maDoiTuongKCB'),
                't_bn_cct_mcct' => self::tien($d, 'tBNCCTMCCT'),
                't_bn_cct_luy_ke' => self::tien($d, 'tBNCCTLuyKe'),
                'ngay_nhan_cong' => self::ngay($d, 'ngayNhanCong'),
                'ngay_nhan' => self::ngay($d, 'ngayNhan'),
            ];
        }

        return $kq;
    }

    /** @return bool */
    public function thanhCong()
    {
        return $this->maKetQua === '200';
    }

    /**
     * Lay MAX chu khong lay dong dau: cong sap giam dan theo NGAY RA VIEN, khong phai theo
     * so luy ke. Ho so nhan muon co the nam cuoi danh sach ma mang so luy ke lon nhat.
     *
     * @return float
     */
    public function luyKeLonNhat()
    {
        $max = 0.0;

        foreach ($this->dong as $d) {
            if ($d['t_bn_cct_luy_ke'] > $max) {
                $max = $d['t_bn_cct_luy_ke'];
            }
        }

        return $max;
    }

    private static function chuoi(array $m, $khoa)
    {
        return isset($m[$khoa]) ? trim((string) $m[$khoa]) : '';
    }

    /**
     * dd/MM/yyyy -> Y-m-d. Chuoi rong -> null.
     *
     * Tra null chu khong tra chuoi rong: cot CSDL kieu date nhan chuoi rong se thanh
     * '0000-00-00' tren mot so cau hinh MySQL - mot ngay khong ton tai, sap xep sai, va
     * khong the phan biet voi "chua co du lieu".
     */
    private static function ngay(array $m, $khoa)
    {
        $v = self::chuoi($m, $khoa);

        if ($v === '' || strlen($v) !== 10) {
            return null;
        }

        $p = explode('/', $v);

        if (count($p) !== 3) {
            return null;
        }

        return $p[2] . '-' . $p[1] . '-' . $p[0];
    }

    private static function tien(array $m, $khoa)
    {
        return isset($m[$khoa]) ? (float) $m[$khoa] : 0.0;
    }
}
```

- [ ] **Step 4: Chạy test để xác nhận nó xanh**

Chạy: `php vendor/bin/phpunit --filter McctPhanTichKetQuaTest`
Kỳ vọng: PASS — 9 test

- [ ] **Step 5: Commit**

```bash
git add app/Services/Mcct/KetQuaMcct.php tests/Unit/Mcct/McctPhanTichKetQuaTest.php
git commit -m "feat(mcct): DTO KetQuaMcct phan tich phan hoi cong BHXH

Doi ngay dd/MM/yyyy sang Y-m-d, tien sang float, bo qua truong du phong.
Chiu duoc DataCCT/ThongTinSoThe = null (ma 204) va mang rong.

luyKeLonNhat() lay MAX chu khong lay dong dau: cong sap theo ngay ra vien,
khong phai theo so luy ke.

Co-Authored-By: Claude Opus 5 <noreply@anthropic.com>"
```

---

## Task 3: McctTraCuuService — gọi cổng, ba header, retry 401

**Files:**
- Modify: `app/Services/BHYTLoginService.php` (thêm một phương thức ở cuối lớp, cạnh
  `password()`)
- Create: `app/Services/Mcct/McctTraCuuService.php`
- Test: `tests/Unit/Mcct/McctXacThucTest.php`

> **Sửa kế hoạch (2026-09-03, trong lúc thực thi).** Bộ test dưới đây mồi token vào cache rồi
> đưa một `BHYTLoginService` **thật** vào service. Cách đó hỏng: luồng 401 gọi `logout()` xoá
> cache, nên `getAccessToken()` lần hai kích hoạt **đăng nhập thật lên cổng BHXH sản xuất** —
> đã xảy ra một lần khi chạy test. `BHYTLoginService` tự tạo `Client` trong constructor nên
> không tiêm mock vào được.
>
> Thay bằng một lớp giả `LoginServiceGiaLap` kế thừa `BHYTLoginService`, khai ngay trong tệp
> test, ghi đè năm phương thức mà `McctTraCuuService` gọi (`getAccessToken`, `getIdToken`,
> `passwordHash`, `username`, `logout`). Không đụng `BHYTLoginService` ngoài việc thêm
> `passwordHash()`. Lớp giả đổi token `TOKEN-A` → `TOKEN-B` khi `logout()` được gọi, nhờ đó
> test 401 kiểm được thêm một điều test cũ không kiểm được: lần gọi thứ hai có thật sự mang
> token mới hay không.

**Interfaces:**
- Consumes: `KetQuaMcct::tuMang()` (Task 2); `BHYTLoginService::getAccessToken()`,
  `getIdToken()`, `username()`, `logout()` (đã có)
- Produces:
  - `BHYTLoginService::passwordHash(): string`
  - `new McctTraCuuService(string $maCskcb, \GuzzleHttp\Client $httpClient = null, \App\Services\BHYTLoginService $loginService = null)`
    — hai tham số sau chỉ để test tiêm vào; mã sản xuất chỉ truyền `$maCskcb`.
  - `McctTraCuuService::traCuu(string $maThe, string $hoTen, string $ngaySinh): KetQuaMcct`
  - Ném `App\Services\Mcct\McctXacThucException` khi 401 lần thứ hai.

- [ ] **Step 1: Viết test thất bại**

Tạo `tests/Unit/Mcct/McctXacThucTest.php`:

```php
<?php

namespace Tests\Unit\Mcct;

use App\Services\BHYTLoginService;
use App\Services\Mcct\McctTraCuuService;
use App\Services\Mcct\McctXacThucException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Tests\TestCase;

class McctXacThucTest extends TestCase
{
    /** @var array cac request that su da di ra ngoai */
    protected $daGui = [];

    protected function setUp()
    {
        parent::setUp();

        $this->daGui = [];

        config([
            'organization.BHYT.base_url' => 'https://egw.baohiemxahoi.gov.vn',
            'organization.BHYT_CO_SO' => [
                '01929' => [
                    'username' => '01929_BV',
                    'password' => 'bam-mat-khau-01929',
                    'ho_ten_cb' => 'Le Thanh Dao',
                    'cccd_cb' => '001083023215',
                ],
            ],
            'mcct.duong_dan' => '/api/TraCuuCCT/TraCuuTienMCCT',
            'mcct.timeout_ket_noi' => 10,
            'mcct.timeout_tong' => 30,
        ]);
    }

    /**
     * Guzzle gia lap, ghi lai moi request di ra.
     *
     * Dung MockHandler chu KHONG dung Mockery: Mockery da nhieu lan vo voi cac lop khai bao
     * kieu tra ve trong du an nay.
     *
     * @param array $phanHoi danh sach Response|Exception tra ve lan luot
     */
    protected function client(array $phanHoi)
    {
        $stack = HandlerStack::create(new MockHandler($phanHoi));
        $stack->push(Middleware::history($this->daGui));

        return new Client(['handler' => $stack]);
    }

    /** Token gia, khong dang nhap that */
    protected function login()
    {
        $login = new BHYTLoginService('01929');

        \Cache::put('bhyt_tokens:01929', [
            'access_token' => 'TOKEN-A',
            'id_token' => 'ID-A',
            'expires_in' => time() + 3600,
        ], 60);

        return $login;
    }

    protected function than200()
    {
        return json_encode([
            'MaKetQua' => '200',
            'GhiChu' => 'tính đến: 05/08/2026 17:30',
            'DataCCT' => [],
            'ThongTinSoThe' => null,
        ]);
    }

    /** @test */
    public function gui_dung_ba_header_xac_thuc()
    {
        $sv = new McctTraCuuService('01929',
            $this->client([new Response(200, [], $this->than200())]), $this->login());

        $sv->traCuu('DN4010100000001', 'Nguyen Van A', '01/01/1990');

        $req = $this->daGui[0]['request'];

        $this->assertSame('TOKEN-A', $req->getHeaderLine('accessToken'));
        $this->assertSame('ID-A', $req->getHeaderLine('tokenId'));
        $this->assertSame('bam-mat-khau-01929', $req->getHeaderLine('passwordHash'));
    }

    /** @test */
    public function gui_dung_url_ghep_tu_base_url_va_duong_dan()
    {
        $sv = new McctTraCuuService('01929',
            $this->client([new Response(200, [], $this->than200())]), $this->login());

        $sv->traCuu('DN4010100000001', 'Nguyen Van A', '01/01/1990');

        $this->assertSame(
            'https://egw.baohiemxahoi.gov.vn/api/TraCuuCCT/TraCuuTienMCCT',
            (string) $this->daGui[0]['request']->getUri()
        );
    }

    /** @test */
    public function body_json_dung_bon_truong()
    {
        $sv = new McctTraCuuService('01929',
            $this->client([new Response(200, [], $this->than200())]), $this->login());

        $sv->traCuu('DN4010100000001', 'Nguyen Van A', '01/01/1990');

        $body = json_decode((string) $this->daGui[0]['request']->getBody(), true);

        $this->assertSame(
            ['username', 'maThe', 'hoTen', 'ngaySinh'],
            array_keys($body)
        );
        $this->assertSame('01929_BV', $body['username']);
        $this->assertSame('DN4010100000001', $body['maThe']);
        $this->assertSame('Nguyen Van A', $body['hoTen']);
        $this->assertSame('01/01/1990', $body['ngaySinh']);
    }

    /**
     * Phien cong chi 10 phut VA khoa theo IP, nen 401 de gap hon han luong cu. Gap 401 thi
     * lam moi token roi goi lai DUNG MOT LAN.
     */
    /** @test */
    public function gap_401_thi_lam_moi_token_va_goi_lai_mot_lan()
    {
        $sv = new McctTraCuuService('01929', $this->client([
            new Response(401, [], ''),
            new Response(200, [], $this->than200()),
        ]), $this->login());

        $kq = $sv->traCuu('DN4010100000001', 'Nguyen Van A', '01/01/1990');

        $this->assertSame('200', $kq->maKetQua);
        $this->assertCount(2, $this->daGui, 'Phai goi dung hai lan');
    }

    /**
     * Lan hai van 401 thi DUNG - khong lap vo han. Nem ngoai le RIENG de controller phan
     * biet duoc voi loi mang, va noi duoc nguyen nhan (cong tra than RONG, tu no khong noi
     * duoc gi).
     */
    /** @test */
    public function lan_hai_van_401_thi_nem_ngoai_le_rieng()
    {
        $sv = new McctTraCuuService('01929', $this->client([
            new Response(401, [], ''),
            new Response(401, [], ''),
        ]), $this->login());

        $this->expectException(McctXacThucException::class);

        $sv->traCuu('DN4010100000001', 'Nguyen Van A', '01/01/1990');
    }

    /**
     * Ma 400/500 KHONG duoc goi lai: cong co danh sach tai khoan bi han che tra cuu, tu
     * nhan doi luot goi la tu chuoc lay no.
     */
    /** @test */
    public function ma_400_va_500_khong_goi_lai()
    {
        $sv = new McctTraCuuService('01929', $this->client([
            new Response(500, [], json_encode([
                'MaKetQua' => '500',
                'GhiChu' => 'Có lỗi xảy ra trong quá trình tra cứu!',
            ])),
        ]), $this->login());

        $kq = $sv->traCuu('DN4010100000001', 'Nguyen Van A', '01/01/1990');

        $this->assertSame('500', $kq->maKetQua);
        $this->assertCount(1, $this->daGui, 'Ma 500 khong duoc sinh lan goi thu hai');
    }

    /** Loi mang cung khong duoc goi lai - cung ly do voi ma 500 */
    /** @test */
    public function loi_mang_khong_goi_lai()
    {
        $sv = new McctTraCuuService('01929', $this->client([
            new ConnectException('Khong noi duoc', new Request('POST', '/')),
        ]), $this->login());

        $this->expectException(ConnectException::class);

        try {
            $sv->traCuu('DN4010100000001', 'Nguyen Van A', '01/01/1990');
        } finally {
            $this->assertCount(1, $this->daGui, 'Loi mang khong duoc sinh lan goi thu hai');
        }
    }

    /** @test */
    public function login_service_tra_dung_password_hash()
    {
        $this->assertSame('bam-mat-khau-01929', (new BHYTLoginService('01929'))->passwordHash());
    }
}
```

- [ ] **Step 2: Chạy test để xác nhận nó hỏng**

Chạy: `php vendor/bin/phpunit --filter McctXacThucTest`
Kỳ vọng: FAIL — `Class 'App\Services\Mcct\McctTraCuuService' not found`

- [ ] **Step 3: Thêm `passwordHash()` vào BHYTLoginService**

Chèn vào cuối lớp `app/Services/BHYTLoginService.php`, ngay sau phương thức `password()`:

```php
    /**
     * Chuoi bam mat khau, gui qua header `passwordHash` cua ham TraCuuTienMCCT.
     *
     * Chinh la gia tri `password` trong BHYT_CO_SO - cau hinh von da luu mat khau o dang da
     * bam MD5. Khong bam lai o day: bam lan hai se ra mot chuoi khac va cong tu choi.
     *
     * @return string
     */
    public function passwordHash(): string
    {
        return $this->taiKhoan()['password'];
    }
```

- [ ] **Step 4: Tạo lớp ngoại lệ riêng**

Tạo `app/Services/Mcct/McctXacThucException.php`:

```php
<?php

namespace App\Services\Mcct;

/**
 * Cong tu choi xac thuc (HTTP 401) KE CA sau khi da lam moi token va goi lai.
 *
 * Can mot lop RIENG chu khong dung \Exception chung: phan hoi 401 cua cong co than RONG,
 * tu no khong noi duoc gi. Controller phai phan biet duoc truong hop nay voi loi mang de
 * hien dung nguyen nhan hay gap nhat - IP may goi khac IP luc lay token.
 */
class McctXacThucException extends \RuntimeException
{
}
```

- [ ] **Step 5: Viết McctTraCuuService**

Tạo `app/Services/Mcct/McctTraCuuService.php`:

```php
<?php

namespace App\Services\Mcct;

use App\Services\BHYT\CongBhxh;
use App\Services\BHYTLoginService;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;

/**
 * Goi ham tra cuu tien cung chi tra (MCCT) tren cong BHXH.
 *
 * Day la noi DUY NHAT trong luong MCCT cham mang. Nho vay bon lop con lai (KetQuaMcct,
 * NguongMienCungChiTra, McctLuuTraCuu, McctRequest) kiem duoc ma khong can mock gi.
 *
 * KHONG dung App\BHYT: lop do la static, co sau diem goi dang chay san xuat, va dang mang
 * san vet "roi ve tai khoan chot cung". Ham nay lai truyen token qua HEADER va body JSON,
 * khac han kieu query-string + form_params cua cac ham cu.
 */
class McctTraCuuService
{
    /** @var Client */
    private $httpClient;

    /** @var BHYTLoginService */
    private $loginService;

    /**
     * @param string $maCskcb ma co so KCB, quyet dinh dung tai khoan cong BHXH nao
     * @param Client|null $httpClient chi de kiem tiem vao; san xuat de null
     * @param BHYTLoginService|null $loginService chi de kiem tiem vao; san xuat de null
     */
    public function __construct($maCskcb, Client $httpClient = null, BHYTLoginService $loginService = null)
    {
        $this->loginService = $loginService ?: new BHYTLoginService($maCskcb);
        $this->httpClient = $httpClient ?: new Client();
    }

    /**
     * @param string $maThe da chuan hoa (bo khoang trang, viet hoa)
     * @param string $hoTen
     * @param string $ngaySinh dd/MM/yyyy hoac MM/yyyy hoac yyyy
     * @return KetQuaMcct
     * @throws McctXacThucException khi 401 ca hai lan
     * @throws \GuzzleHttp\Exception\GuzzleException khi loi mang
     */
    public function traCuu($maThe, $hoTen, $ngaySinh)
    {
        $phanHoi = $this->goi($maThe, $hoTen, $ngaySinh);

        // 401 lan dau: phien cong chi 10 phut, rat co the token trong cache da het han.
        // Xoa token roi dang nhap lai va goi lai DUNG MOT LAN.
        if ($phanHoi['ma_http'] === 401) {
            $this->loginService->logout();
            $phanHoi = $this->goi($maThe, $hoTen, $ngaySinh);

            if ($phanHoi['ma_http'] === 401) {
                throw new McctXacThucException(
                    'Không xác thực được với cổng BHXH sau khi đã lấy lại phiên. '
                    . 'Kiểm tra: phiên hết hạn, hoặc IP máy chủ gọi khác IP lúc lấy token '
                    . '(cổng khoá theo IP).'
                );
            }
        }

        $kq = KetQuaMcct::tuMang($phanHoi['than']);

        // KHONG ghi accessToken hay passwordHash vao log.
        Log::info('MCCT tra cuu', [
            'ma_the' => $maThe,
            'ma_ket_qua' => $kq->maKetQua,
            'ma_http' => $phanHoi['ma_http'],
        ]);

        // Ma 400 dang le KHONG the xay ra: McctRequest da kiem do dai ma the va dinh dang
        // ngay sinh truoc khi goi. Xay ra tuc la luat kiem cua minh lech voi cong - ghi lai
        // dung tham so da gui de doi chieu. Body chi co bon truong, khong chua bi mat nao
        // (accessToken/passwordHash di o HEADER, khong o day).
        if ($phanHoi['ma_http'] === 400) {
            Log::warning('MCCT bi cong tu choi 400 du da kiem dau vao', [
                'ma_the' => $maThe,
                'ho_ten' => $hoTen,
                'ngay_sinh' => $ngaySinh,
                'ghi_chu' => $kq->ghiChu,
            ]);
        }

        return $kq;
    }

    /**
     * Mot lan goi. Tra ve ca ma HTTP vi 401 khong co than de doc.
     *
     * @return array ['ma_http' => int, 'than' => array]
     */
    private function goi($maThe, $hoTen, $ngaySinh)
    {
        try {
            $res = $this->httpClient->post(CongBhxh::url(config('mcct.duong_dan')), [
                'headers' => [
                    'Content-Type' => 'application/json; charset=utf-8',
                    'accessToken' => $this->loginService->getAccessToken(),
                    'tokenId' => $this->loginService->getIdToken(),
                    'passwordHash' => $this->loginService->passwordHash(),
                ],
                'json' => [
                    'username' => $this->loginService->username(),
                    'maThe' => $maThe,
                    'hoTen' => $hoTen,
                    'ngaySinh' => $ngaySinh,
                ],
                'connect_timeout' => (int) config('mcct.timeout_ket_noi', 10),
                'timeout' => (int) config('mcct.timeout_tong', 30),
                // Doc than cua 4xx/5xx thay vi de Guzzle nem: ma 400/500 CO than JSON mang
                // thong tin phan biet duoc (vd tai khoan bi han che tra cuu). Nem di la vut
                // mat dung cai can doc.
                'http_errors' => false,
            ]);
        } catch (RequestException $e) {
            throw $e;
        }

        $than = json_decode((string) $res->getBody(), true);

        return [
            'ma_http' => $res->getStatusCode(),
            'than' => is_array($than) ? $than : [],
        ];
    }
}
```

- [ ] **Step 6: Chạy test để xác nhận nó xanh**

Chạy: `php vendor/bin/phpunit --filter McctXacThucTest`
Kỳ vọng: PASS — 8 test

- [ ] **Step 7: Chạy lại toàn bộ test của BHYTLoginService để chắc không làm hỏng cái cũ**

Chạy: `php vendor/bin/phpunit --filter BHYTLoginService`
Kỳ vọng: PASS, số test không đổi so với trước khi sửa

- [ ] **Step 8: Commit**

```bash
git add app/Services/Mcct/McctTraCuuService.php app/Services/Mcct/McctXacThucException.php app/Services/BHYTLoginService.php tests/Unit/Mcct/McctXacThucTest.php
git commit -m "feat(mcct): service goi cong BHXH bang ba header va body JSON

Ghep URL qua CongBhxh::url(), token lay tu BHYTLoginService theo tung co so.
passwordHash chinh la mat khau da bam trong BHYT_CO_SO, khong bam lai.

Gap 401 thi lam moi token va goi lai DUNG MOT LAN roi nem
McctXacThucException - phien cong chi 10 phut va khoa theo IP. Ma 400/500
va loi mang KHONG goi lai: cong co danh sach tai khoan bi han che tra cuu.

Co-Authored-By: Claude Opus 5 <noreply@anthropic.com>"
```

---

## Task 4: Bảng, Model và lớp lưu trữ

**Files:**
- Create: `database/migrations/2026_09_03_100001_create_mcct_tra_cuu_table.php`
- Create: `database/migrations/2026_09_03_100002_create_mcct_chi_phi_table.php`
- Create: `app/Models/Mcct/McctTraCuu.php`
- Create: `app/Models/Mcct/McctChiPhi.php`
- Create: `app/Services/Mcct/McctLuuTraCuu.php`
- Test: `tests/Unit/Mcct/McctLuuTraCuuTest.php`

**Interfaces:**
- Consumes: `KetQuaMcct` (Task 2), `NguongMienCungChiTra` (Task 1)
- Produces:
  - `McctLuuTraCuu::luu(KetQuaMcct $kq, array $thamSo): McctTraCuu` — `$thamSo` có các
    khoá: `ma_cskcb`, `ma_the`, `ho_ten`, `ngay_sinh`, `nguon`, `tra_boi`, `nguong`
    (float), `du_dieu_kien` (bool|null).
  - Model `App\Models\Mcct\McctTraCuu` với quan hệ `chiPhi()` (hasMany).

- [ ] **Step 1: Viết migration bảng phiên tra**

Tạo `database/migrations/2026_09_03_100001_create_mcct_tra_cuu_table.php`:

```php
<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Moi lan bam tra them mot dong, KE CA lan hong (204/400/401/500).
 *
 * Giu dau vet lan hong la giu dung thu can den khi di hoi cong: khong co no thi cau hoi
 * "hom qua tra thay gi" khong tra loi duoc.
 */
class CreateMcctTraCuuTable extends Migration
{
    public function up()
    {
        Schema::create('mcct_tra_cuu', function (Blueprint $table) {
            $table->increments('id');

            $table->string('ma_cskcb', 10)->index();
            $table->string('ma_the', 20)->index();
            $table->string('ho_ten')->nullable();
            $table->string('ngay_sinh', 10)->nullable();

            $table->string('ma_ket_qua', 10)->nullable()->index();

            // NGUYEN VAN GhiChu - chua moc "du lieu tinh den dd/MM/yyyy HH:mm". Thieu no thi
            // so luy ke luu lai khong giai thich duoc khi doi soat.
            $table->text('ghi_chu')->nullable();

            $table->string('the_ho_ten')->nullable();
            $table->string('the_ngay_sinh', 10)->nullable();
            $table->date('the_ngay_ket_thuc')->nullable();
            $table->string('the_ma_bhxh', 15)->nullable()->index();

            $table->decimal('luy_ke_lon_nhat', 15, 2)->nullable();

            // Nguong TAI THOI DIEM TRA. Khong tinh lai luc doc: luong co so se tang, va tinh
            // lai nghia la moi ban ghi cu dot ngot doi ket luan du/chua du.
            $table->decimal('nguong_ap_dung', 15, 2)->nullable();
            $table->boolean('du_dieu_kien_mien')->nullable();

            // thu_cong | hang_loat | api_his - chua san cho giai doan 2 va 3.
            $table->string('nguon', 20)->default('thu_cong')->index();

            $table->string('tra_boi')->nullable();
            $table->timestamp('tra_luc')->nullable();

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('mcct_tra_cuu');
    }
}
```

- [ ] **Step 2: Viết migration bảng dòng chi phí**

Tạo `database/migrations/2026_09_03_100002_create_mcct_chi_phi_table.php`:

```php
<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Cac dong DataCCT cua MOT phien tra.
 *
 * KHONG dat unique (ma_the, id_cong): moi lan tra la mot anh chup moi, trung id_cong giua
 * cac phien la chuyen duong nhien - do chinh la thu cho phep so sanh hai lan tra.
 *
 * Nam truong duPhong cua cong KHONG luu: phu luc ghi ro chung luon la chuoi rong.
 */
class CreateMcctChiPhiTable extends Migration
{
    public function up()
    {
        Schema::create('mcct_chi_phi', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('tra_cuu_id')->index();

            $table->bigInteger('id_cong')->nullable()->index();
            $table->string('ma_the', 20)->nullable();
            $table->string('ma_cskcb', 10)->nullable();

            $table->date('ngay_vao')->nullable();
            $table->date('ngay_ra')->nullable();
            $table->string('ma_doi_tuong_kcb', 10)->nullable();

            $table->decimal('t_bn_cct_mcct', 15, 2)->default(0);
            $table->decimal('t_bn_cct_luy_ke', 15, 2)->default(0);

            $table->date('ngay_nhan_cong')->nullable();
            $table->date('ngay_nhan')->nullable();
            $table->date('ngay_tra_cuu')->nullable();

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('mcct_chi_phi');
    }
}
```

- [ ] **Step 3: Chạy migration lên schema test và schema phát triển**

```bash
php artisan migrate --database=mysql
```

Kỳ vọng: hai dòng `Migrated: 2026_09_03_100001_create_mcct_tra_cuu_table` và
`…_100002_create_mcct_chi_phi_table`.

Sau đó tạo hai bảng trong schema test `qlbv_test` để `McctLuuTraCuuTest` chạy được.
`--env=testing` **không** đọc khối `<php>` của `phpunit.xml`, nên phải đặt biến môi trường
trực tiếp. Trong PowerShell:

```bash
$env:DB_DATABASE='qlbv_test'; php artisan migrate; $env:DB_DATABASE=$null
```

Kỳ vọng: hai dòng `Migrated` như trên. Xác nhận bằng
`$env:DB_DATABASE='qlbv_test'; php artisan migrate:status; $env:DB_DATABASE=$null`.

**Không** dùng `migrate:fresh`, `migrate:reset` hay `migrate:refresh` ở bất kỳ đâu, kể cả
trên `qlbv_test`.

- [ ] **Step 4: Viết test thất bại**

Tạo `tests/Unit/Mcct/McctLuuTraCuuTest.php`:

```php
<?php

namespace Tests\Unit\Mcct;

use App\Models\Mcct\McctChiPhi;
use App\Models\Mcct\McctTraCuu;
use App\Services\Mcct\KetQuaMcct;
use App\Services\Mcct\McctLuuTraCuu;
use Tests\TestCase;

class McctLuuTraCuuTest extends TestCase
{
    /**
     * Don dep bang tay theo dung ban ghi minh tao ra.
     *
     * KHONG dung RefreshDatabase hay DatabaseMigrations: ngay 2026-08-21 mot test dung
     * DatabaseMigrations da DROP sach CSDL phat trien. Xoa theo id la du.
     */
    protected $daTao = [];

    protected function tearDown()
    {
        foreach ($this->daTao as $id) {
            McctChiPhi::where('tra_cuu_id', $id)->delete();
            McctTraCuu::where('id', $id)->delete();
        }

        parent::tearDown();
    }

    protected function thamSo()
    {
        return [
            'ma_cskcb' => '01929',
            'ma_the' => 'DN4010100000001',
            'ho_ten' => 'NGUYEN VAN A',
            'ngay_sinh' => '01/01/1990',
            'nguon' => 'thu_cong',
            'tra_boi' => 'kiemthu',
            'nguong' => 14040000.0,
            'du_dieu_kien' => false,
        ];
    }

    protected function ketQua200()
    {
        return KetQuaMcct::tuMang([
            'MaKetQua' => '200',
            'GhiChu' => 'Nguồn DL ... tính đến: 05/08/2026 17:30',
            'DataCCT' => [
                [
                    'Id' => 123456, 'ngayTraCuu' => '10/08/2026',
                    'maThe' => 'DN4010100000001', 'maCskcb' => '01001',
                    'ngayVao' => '02/04/2026', 'ngayRa' => '05/04/2026',
                    'maDoiTuongKCB' => 'DN',
                    'tBNCCTMCCT' => 250000, 'tBNCCTLuyKe' => 1250000,
                    'ngayNhanCong' => '10/04/2026', 'ngayNhan' => '10/04/2026',
                ],
                [
                    'Id' => 123457, 'ngayTraCuu' => '10/08/2026',
                    'maThe' => 'DN4010100000001', 'maCskcb' => '01001',
                    'ngayVao' => '01/02/2026', 'ngayRa' => '03/02/2026',
                    'maDoiTuongKCB' => 'DN',
                    'tBNCCTMCCT' => 100000, 'tBNCCTLuyKe' => 900000,
                    'ngayNhanCong' => '05/02/2026', 'ngayNhan' => '05/02/2026',
                ],
            ],
            'ThongTinSoThe' => [
                'hoTen' => 'Nguyễn Văn A', 'ngaySinh' => '01/01/1990',
                'ngayKetThuc' => '31/12/2026', 'maBhxh' => '0100000001',
            ],
        ]);
    }

    /** @test */
    public function luu_phien_tra_va_du_cac_dong_chi_phi()
    {
        $ban = McctLuuTraCuu::luu($this->ketQua200(), $this->thamSo());
        $this->daTao[] = $ban->id;

        $this->assertSame('200', $ban->ma_ket_qua);
        $this->assertSame('01929', $ban->ma_cskcb);
        $this->assertSame('0100000001', $ban->the_ma_bhxh);
        $this->assertSame(2, McctChiPhi::where('tra_cuu_id', $ban->id)->count());
    }

    /** GhiChu phai luu NGUYEN VAN - no chua moc thoi gian cua du lieu */
    /** @test */
    public function luu_ghi_chu_nguyen_van()
    {
        $ban = McctLuuTraCuu::luu($this->ketQua200(), $this->thamSo());
        $this->daTao[] = $ban->id;

        $this->assertContains('tính đến: 05/08/2026 17:30', $ban->ghi_chu);
    }

    /** @test */
    public function luu_luy_ke_lon_nhat_chu_khong_lay_dong_dau()
    {
        $ban = McctLuuTraCuu::luu($this->ketQua200(), $this->thamSo());
        $this->daTao[] = $ban->id;

        $this->assertEquals(1250000, $ban->luy_ke_lon_nhat);
    }

    /**
     * Nguong duoc GHI VAO BANG, khong tinh lai luc doc. Doi bang luong trong config sau do
     * doc lai ban ghi cu phai ra dung con so cu.
     */
    /** @test */
    public function ghi_nguong_vao_bang_chu_khong_tinh_lai()
    {
        $ban = McctLuuTraCuu::luu($this->ketQua200(), $this->thamSo());
        $this->daTao[] = $ban->id;

        config(['mcct.luong_co_so' => ['2023-07-01' => 99999999]]);

        $docLai = McctTraCuu::find($ban->id);

        $this->assertEquals(14040000, $docLai->nguong_ap_dung);
        $this->assertSame(0, (int) $docLai->du_dieu_kien_mien);
    }

    /**
     * Ma 204 van phai co mot dong phien tra - khong co dong chi phi nao. Bo qua lan hong la
     * mat dung thu can den khi di hoi cong.
     */
    /** @test */
    public function ma_204_van_luu_phien_tra_khong_co_dong_nao()
    {
        $kq = KetQuaMcct::tuMang([
            'MaKetQua' => '204',
            'GhiChu' => 'Không tìm thấy dữ liệu!',
            'DataCCT' => null,
            'ThongTinSoThe' => null,
        ]);

        $thamSo = $this->thamSo();
        $thamSo['du_dieu_kien'] = null;

        $ban = McctLuuTraCuu::luu($kq, $thamSo);
        $this->daTao[] = $ban->id;

        $this->assertSame('204', $ban->ma_ket_qua);
        $this->assertNull($ban->the_ma_bhxh);
        $this->assertSame(0, McctChiPhi::where('tra_cuu_id', $ban->id)->count());
    }
}
```

- [ ] **Step 5: Chạy test để xác nhận nó hỏng**

Chạy: `php vendor/bin/phpunit --filter McctLuuTraCuuTest`
Kỳ vọng: FAIL — `Class 'App\Models\Mcct\McctTraCuu' not found`

- [ ] **Step 6: Viết hai Model**

Tạo `app/Models/Mcct/McctTraCuu.php`:

```php
<?php

namespace App\Models\Mcct;

use Illuminate\Database\Eloquent\Model;

class McctTraCuu extends Model
{
    protected $table = 'mcct_tra_cuu';

    protected $fillable = [
        'ma_cskcb', 'ma_the', 'ho_ten', 'ngay_sinh',
        'ma_ket_qua', 'ghi_chu',
        'the_ho_ten', 'the_ngay_sinh', 'the_ngay_ket_thuc', 'the_ma_bhxh',
        'luy_ke_lon_nhat', 'nguong_ap_dung', 'du_dieu_kien_mien',
        'nguon', 'tra_boi', 'tra_luc',
    ];

    protected $dates = ['tra_luc'];

    public function chiPhi()
    {
        return $this->hasMany(McctChiPhi::class, 'tra_cuu_id');
    }
}
```

Tạo `app/Models/Mcct/McctChiPhi.php`:

```php
<?php

namespace App\Models\Mcct;

use Illuminate\Database\Eloquent\Model;

class McctChiPhi extends Model
{
    protected $table = 'mcct_chi_phi';

    protected $fillable = [
        'tra_cuu_id', 'id_cong', 'ma_the', 'ma_cskcb',
        'ngay_vao', 'ngay_ra', 'ma_doi_tuong_kcb',
        't_bn_cct_mcct', 't_bn_cct_luy_ke',
        'ngay_nhan_cong', 'ngay_nhan', 'ngay_tra_cuu',
    ];

    public function traCuu()
    {
        return $this->belongsTo(McctTraCuu::class, 'tra_cuu_id');
    }
}
```

- [ ] **Step 7: Viết McctLuuTraCuu**

Tạo `app/Services/Mcct/McctLuuTraCuu.php`:

```php
<?php

namespace App\Services\Mcct;

use App\Models\Mcct\McctChiPhi;
use App\Models\Mcct\McctTraCuu;

/**
 * Ghi mot phien tra cuu MCCT xuong CSDL.
 *
 * Tach khoi McctTraCuuService de kiem duoc ma khong cham mang, va de giai doan 2 (tra hang
 * loat) dung lai nguyen ma khong keo theo tang HTTP.
 */
class McctLuuTraCuu
{
    /**
     * @param KetQuaMcct $kq
     * @param array $thamSo ma_cskcb, ma_the, ho_ten, ngay_sinh, nguon, tra_boi, nguong,
     *                      du_dieu_kien
     * @return McctTraCuu
     */
    public static function luu(KetQuaMcct $kq, array $thamSo)
    {
        $the = $kq->thongTinThe;

        $ban = McctTraCuu::create([
            'ma_cskcb' => isset($thamSo['ma_cskcb']) ? $thamSo['ma_cskcb'] : '',
            'ma_the' => isset($thamSo['ma_the']) ? $thamSo['ma_the'] : '',
            'ho_ten' => isset($thamSo['ho_ten']) ? $thamSo['ho_ten'] : null,
            'ngay_sinh' => isset($thamSo['ngay_sinh']) ? $thamSo['ngay_sinh'] : null,

            'ma_ket_qua' => $kq->maKetQua,
            'ghi_chu' => $kq->ghiChu,

            'the_ho_ten' => isset($the['ho_ten']) ? $the['ho_ten'] : null,
            'the_ngay_sinh' => isset($the['ngay_sinh']) ? $the['ngay_sinh'] : null,
            'the_ngay_ket_thuc' => isset($the['ngay_ket_thuc']) ? $the['ngay_ket_thuc'] : null,
            'the_ma_bhxh' => isset($the['ma_bhxh']) ? $the['ma_bhxh'] : null,

            'luy_ke_lon_nhat' => $kq->luyKeLonNhat(),

            // Nguong TINH SAN o tren truyen xuong, khong tinh lai o day va cung khong tinh
            // lai luc doc: luong co so se tang, va ban ghi cu phai giu nguyen ket luan cu.
            'nguong_ap_dung' => isset($thamSo['nguong']) ? $thamSo['nguong'] : null,
            'du_dieu_kien_mien' => isset($thamSo['du_dieu_kien']) ? $thamSo['du_dieu_kien'] : null,

            'nguon' => isset($thamSo['nguon']) ? $thamSo['nguon'] : 'thu_cong',
            'tra_boi' => isset($thamSo['tra_boi']) ? $thamSo['tra_boi'] : null,
            'tra_luc' => date('Y-m-d H:i:s'),
        ]);

        foreach ($kq->dong as $d) {
            $d['tra_cuu_id'] = $ban->id;
            McctChiPhi::create($d);
        }

        return $ban;
    }
}
```

- [ ] **Step 8: Chạy test để xác nhận nó xanh**

Chạy: `php vendor/bin/phpunit --filter McctLuuTraCuuTest`
Kỳ vọng: PASS — 5 test

- [ ] **Step 9: Commit**

```bash
git add database/migrations/2026_09_03_100001_create_mcct_tra_cuu_table.php database/migrations/2026_09_03_100002_create_mcct_chi_phi_table.php app/Models/Mcct app/Services/Mcct/McctLuuTraCuu.php tests/Unit/Mcct/McctLuuTraCuuTest.php
git commit -m "feat(mcct): hai bang luu vet va lop McctLuuTraCuu

Luu MOI lan tra, ke ca 204/400/500 - dau vet lan hong la thu can den khi di
hoi cong. GhiChu luu nguyen van vi no chua moc thoi gian cua du lieu.

nguong_ap_dung ghi vao bang chu khong tinh lai luc doc: luong co so se tang
va ban ghi cu phai giu nguyen ket luan cu.

Test don dep theo id, khong dung RefreshDatabase.

Co-Authored-By: Claude Opus 5 <noreply@anthropic.com>"
```

---

## Task 5: Luật kiểm đầu vào

**Files:**
- Create: `app/Http/Requests/McctRequest.php`
- Test: `tests/Unit/Mcct/McctValidateTest.php`

**Interfaces:**
- Consumes: `CoSoTraCuu::maDangChuoi()`, `CoSoTraCuu::tuCauHinh()` (đã có)
- Produces:
  - `McctRequest::rules(): array` — khoá: `ma_cskcb`, `ma_the`, `ho_ten`, `ngay_sinh`
  - `McctRequest::chuanHoaMaThe(string $maThe): string` — bỏ mọi khoảng trắng, viết hoa

- [ ] **Step 1: Viết test thất bại**

Tạo `tests/Unit/Mcct/McctValidateTest.php`:

```php
<?php

namespace Tests\Unit\Mcct;

use App\Http\Requests\McctRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class McctValidateTest extends TestCase
{
    protected function setUp()
    {
        parent::setUp();

        config(['organization.BHYT_CO_SO' => [
            '01929' => ['username' => 'u', 'password' => 'p'],
            '37470' => ['username' => 'u', 'password' => 'p'],
        ]]);
    }

    protected function hopLe($ghiDe = [])
    {
        return array_merge([
            'ma_cskcb' => '01929',
            'ma_the' => 'DN4010100000001',
            'ho_ten' => 'NGUYEN VAN A',
            'ngay_sinh' => '01/01/1990',
        ], $ghiDe);
    }

    protected function kiem(array $du_lieu)
    {
        return Validator::make($du_lieu, (new McctRequest())->rules());
    }

    /** @test */
    public function du_lieu_hop_le_thi_qua()
    {
        $this->assertTrue($this->kiem($this->hopLe())->passes());
    }

    /**
     * Phu luc ghi ro: sau khi bo khoang trang, do dai hop le la 10, 12 hoac 15.
     * Chan tai day chu khong de cong tra 400 - vua tiet kiem luot goi (cong co danh sach
     * tai khoan bi han che), vua bao loi dung cho sai.
     */
    /** @test */
    public function ma_the_dung_do_dai_10_12_15_thi_qua()
    {
        foreach ([10, 12, 15] as $doDai) {
            $ma = str_repeat('A', $doDai);

            $this->assertTrue($this->kiem($this->hopLe(['ma_the' => $ma]))->passes(),
                "Ma the $doDai ky tu phai qua");
        }
    }

    /** @test */
    public function ma_the_sai_do_dai_thi_bi_chan()
    {
        foreach ([9, 11, 13, 14, 16] as $doDai) {
            $ma = str_repeat('A', $doDai);

            $this->assertTrue($this->kiem($this->hopLe(['ma_the' => $ma]))->fails(),
                "Ma the $doDai ky tu phai bi chan");
        }
    }

    /** @test */
    public function ba_dinh_dang_ngay_sinh_deu_qua()
    {
        foreach (['01/01/1990', '01/1990', '1990'] as $ns) {
            $this->assertTrue($this->kiem($this->hopLe(['ngay_sinh' => $ns]))->passes(),
                "Ngay sinh $ns phai qua");
        }
    }

    /** @test */
    public function ngay_sinh_sai_dinh_dang_thi_bi_chan()
    {
        foreach (['1/1/1990', '1990-01-01', '01-01-1990', '32/01/1990'] as $ns) {
            $this->assertTrue($this->kiem($this->hopLe(['ngay_sinh' => $ns]))->fails(),
                "Ngay sinh $ns phai bi chan");
        }
    }

    /**
     * O chon va localStorage deu sua duoc tu phia nguoi dung - ma ngoai danh sach phai bi
     * chan TRUOC khi cham toi cong BHXH.
     */
    /** @test */
    public function ma_co_so_ngoai_danh_sach_bi_chan()
    {
        $this->assertTrue($this->kiem($this->hopLe(['ma_cskcb' => '99999']))->fails());
    }

    /**
     * ma_cskcb de trong thi KHONG bao loi: controller dung lai o man da dien san de nguoi
     * dung chon. Giong hanh vi da co o InsuranceRequest.
     */
    /** @test */
    public function ma_co_so_de_trong_thi_khong_bao_loi()
    {
        $this->assertTrue($this->kiem($this->hopLe(['ma_cskcb' => '']))->passes());
    }

    /** @test */
    public function thieu_ho_ten_thi_bi_chan()
    {
        $this->assertTrue($this->kiem($this->hopLe(['ho_ten' => '']))->fails());
    }

    /** @test */
    public function chuan_hoa_ma_the_bo_khoang_trang_va_viet_hoa()
    {
        $this->assertSame('DN4010100000001',
            McctRequest::chuanHoaMaThe('  dn4 0101 000 00001 '));
    }
}
```

- [ ] **Step 2: Chạy test để xác nhận nó hỏng**

Chạy: `php vendor/bin/phpunit --filter McctValidateTest`
Kỳ vọng: FAIL — `Class 'App\Http\Requests\McctRequest' not found`

- [ ] **Step 3: Viết McctRequest**

Tạo `app/Http/Requests/McctRequest.php`:

```php
<?php

namespace App\Http\Requests;

use App\Services\BHYT\CoSoTraCuu;
use Illuminate\Foundation\Http\FormRequest;

class McctRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        // KHONG tin trinh duyet: o chon sua duoc tu phia nguoi dung. Ma ngoai danh sach phai
        // bi chan o day, truoc khi cham toi cong BHXH.
        $maHopLe = CoSoTraCuu::maDangChuoi(CoSoTraCuu::tuCauHinh());

        return [
            // ma_cskcb 'nullable' chu KHONG 'required', giong InsuranceRequest: thieu ma thi
            // controller dung lai o man da dien san cho nguoi dung chon, khong bao loi do.
            // Con ma SAI thi van bi chan o day.
            'ma_cskcb' => 'nullable|in:' . implode(',', $maHopLe),

            // Phu luc: do dai hop le sau khi bo khoang trang la 10, 12 hoac 15.
            'ma_the' => 'required|regex:/^[A-Za-z0-9]{10}$|^[A-Za-z0-9]{12}$|^[A-Za-z0-9]{15}$/',

            'ho_ten' => 'required',

            // Ba dinh dang cong chap nhan: dd/MM/yyyy, MM/yyyy, yyyy.
            'ngay_sinh' => 'required|date_format:d/m/Y,m/Y,Y',
        ];
    }

    public function messages()
    {
        return [
            'ma_cskcb.in' => 'Cơ sở khám chữa bệnh không hợp lệ.',
            'ma_the.required' => 'Chưa nhập mã thẻ BHYT.',
            'ma_the.regex' => 'Mã thẻ BHYT phải có 10, 12 hoặc 15 ký tự.',
            'ho_ten.required' => 'Chưa nhập họ và tên.',
            'ngay_sinh.required' => 'Chưa nhập ngày sinh.',
            'ngay_sinh.date_format' => 'Ngày sinh phải theo dd/mm/yyyy, mm/yyyy hoặc yyyy.',
        ];
    }

    /**
     * Bo MOI khoang trang va viet hoa.
     *
     * Cong tu bo khoang trang truoc khi do do dai, nen mot ma go co dau cach van hop le voi
     * cong nhung se truot luat regex o tren neu khong chuan hoa truoc.
     *
     * @param string $maThe
     * @return string
     */
    public static function chuanHoaMaThe($maThe)
    {
        return mb_strtoupper(preg_replace('/\s+/', '', (string) $maThe));
    }
}
```

- [ ] **Step 4: Chạy test để xác nhận nó xanh**

Chạy: `php vendor/bin/phpunit --filter McctValidateTest`
Kỳ vọng: PASS — 9 test

Nếu `date_format:d/m/Y,m/Y,Y` không chấp nhận nhiều định dạng trên Laravel 5.5, thay bằng
luật `regex` tương đương và giữ nguyên bộ test:
`'ngay_sinh' => 'required|regex:#^(\d{2}/\d{2}/\d{4}|\d{2}/\d{4}|\d{4})$#'`, đồng thời bổ
sung kiểm ngày thực (`checkdate`) trong controller cho nhánh 10 ký tự để `32/01/1990` vẫn bị
chặn.

- [ ] **Step 5: Commit**

```bash
git add app/Http/Requests/McctRequest.php tests/Unit/Mcct/McctValidateTest.php
git commit -m "feat(mcct): luat kiem dau vao truoc khi cham cong BHXH

Ma the 10/12/15 ky tu, ngay sinh ba dinh dang, ma co so phai thuoc
BHYT_CO_SO. Chan tai day chu khong de cong tra 400: tiet kiem luot goi va
bao loi dung cho sai.

ma_cskcb nullable chu khong required, giong InsuranceRequest.

Co-Authored-By: Claude Opus 5 <noreply@anthropic.com>"
```

---

## Task 6: Controller, route, menu và giao diện

**Files:**
- Create: `app/Http/Controllers/Insurance/Manager/McctController.php`
- Create: `resources/views/insurance/manager/mcct/index.blade.php`
- Create: `resources/views/insurance/manager/mcct/search.blade.php`
- Create: `resources/views/insurance/manager/mcct/result.blade.php`
- Modify: `routes/web.php` (trong nhóm `insurance/`, ngay sau dòng
  `insurance.check-card.getqrcode`)
- Modify: `routes/breadcrumbs.php`
- Modify: `config/adminlte.php` (nhóm menu "Thẻ BHYT", sau mục "Tra cứu thẻ BHYT")
- Modify: `resources/views/insurance/manager/check-card/result.blade.php`
- Test: `tests/Unit/Mcct/RouteMcctTest.php`, `tests/Unit/Mcct/MenuMcctTest.php`

**Interfaces:**
- Consumes: `McctRequest` (Task 5), `McctTraCuuService` (Task 3), `McctLuuTraCuu` (Task 4),
  `NguongMienCungChiTra` (Task 1), `KetQuaMcct` (Task 2)
- Produces: route `insurance.mcct` → `insurance/mcct`; route `insurance.mcct.search` →
  `insurance/mcct/search`

- [ ] **Step 1: Viết test route thất bại**

Tạo `tests/Unit/Mcct/RouteMcctTest.php`:

```php
<?php

namespace Tests\Unit\Mcct;

use Route;
use Tests\TestCase;

class RouteMcctTest extends TestCase
{
    /** Ten route => URI. Chot cung de chan viec vo tinh doi URL. */
    protected function banDo()
    {
        return [
            'insurance.mcct' => 'insurance/mcct',
            'insurance.mcct.search' => 'insurance/mcct/search',
        ];
    }

    /** @test */
    public function du_hai_route_va_url_khong_doi()
    {
        foreach ($this->banDo() as $ten => $uri) {
            $r = Route::getRoutes()->getByName($ten);

            $this->assertNotNull($r, "Thieu route $ten");
            $this->assertSame($uri, $r->uri(), "Route $ten bi doi URL");
        }
    }

    /**
     * Nhom ngoai cung cua web.php la ['auth']. Chen nham ra ngoai nhom do thi route thanh
     * cong khai - loi bao mat im lang.
     */
    /** @test */
    public function van_nam_trong_nhom_xac_thuc()
    {
        foreach (array_keys($this->banDo()) as $ten) {
            $mw = Route::getRoutes()->getByName($ten)->gatherMiddleware();

            $this->assertContains('auth', $mw, "Route $ten mat xac thuc");
        }
    }

    /**
     * Cung muc quyen voi man tra cuu the BHYT ngay canh no. Siet rieng MCCT trong khi man
     * tra cuu the van mo la mot su bat nhat kho giai thich.
     */
    /** @test */
    public function cung_muc_quyen_voi_man_tra_cuu_the()
    {
        $cuaThe = Route::getRoutes()->getByName('insurance.check-card')->gatherMiddleware();

        foreach (array_keys($this->banDo()) as $ten) {
            $mw = Route::getRoutes()->getByName($ten)->gatherMiddleware();

            $this->assertSame(array_values($cuaThe), array_values($mw),
                "Route $ten khong cung muc quyen voi man tra cuu the");
        }
    }
}
```

- [ ] **Step 2: Viết test menu thất bại**

Tạo `tests/Unit/Mcct/MenuMcctTest.php`:

```php
<?php

namespace Tests\Unit\Mcct;

use Tests\TestCase;

class MenuMcctTest extends TestCase
{
    const TEN_NHOM = 'Thẻ BHYT';
    const TEN_MUC = 'Tra cứu tiền cùng chi trả';

    /** Menu goc doc thang tu config, chua qua bo loc quyen */
    protected function menu()
    {
        return config('adminlte.menu');
    }

    /** Cac muc con cua nhom "The BHYT"; mang rong neu khong tim thay nhom */
    protected function mucConCuaNhom()
    {
        foreach ($this->menu() as $item) {
            if (is_array($item) && isset($item['text']) && $item['text'] === self::TEN_NHOM) {
                return isset($item['submenu']) ? $item['submenu'] : [];
            }
        }

        return [];
    }

    /** @test */
    public function nam_trong_nhom_the_bhyt()
    {
        $ten = [];

        foreach ($this->mucConCuaNhom() as $m) {
            if (isset($m['text'])) {
                $ten[] = $m['text'];
            }
        }

        $this->assertContains(self::TEN_MUC, $ten,
            'Khong tim thay muc "' . self::TEN_MUC . '" trong nhom "' . self::TEN_NHOM . '"');
    }

    /** @test */
    public function tro_dung_route_va_chi_xuat_hien_mot_lan()
    {
        $dem = 0;

        foreach ($this->mucConCuaNhom() as $m) {
            if (isset($m['text']) && $m['text'] === self::TEN_MUC) {
                $dem++;
                $this->assertSame('insurance.mcct', $m['route']);
                $this->assertSame(['insurance/mcct*'], $m['active']);
            }
        }

        $this->assertSame(1, $dem, 'Muc phai xuat hien dung mot lan');
    }
}
```

- [ ] **Step 3: Chạy hai test để xác nhận chúng hỏng**

Chạy: `php vendor/bin/phpunit --filter "RouteMcctTest|MenuMcctTest"`
Kỳ vọng: FAIL — `Thieu route insurance.mcct` và `Khong tim thay muc "Tra cứu tiền cùng chi trả"`

- [ ] **Step 4: Thêm route**

Trong `routes/web.php`, chèn ngay sau dòng đăng ký `insurance.check-card.getqrcode` (trong
nhóm `Route::group(['prefix' => 'insurance/'], ...)`):

```php
        // Tra cuu tien cung chi tra (MCCT). Nam trong dung nhom insurance/ de cung muc
        // quyen voi man tra cuu the BHYT ngay canh no.
        Route::get('mcct', 'Insurance\Manager\McctController@index')->name('insurance.mcct');
        Route::get('mcct/search', 'Insurance\Manager\McctController@search')->name('insurance.mcct.search');
```

- [ ] **Step 5: Thêm mục menu**

Trong `config/adminlte.php`, trong nhóm `'text' => 'Thẻ BHYT'`, thêm vào `submenu` ngay sau
mục "Tra cứu thẻ BHYT":

```php
                [
                    'text'  => 'Tra cứu tiền cùng chi trả',
                    'icon'  => 'money',
                    'route'   => 'insurance.mcct',
                    'active'=> ['insurance/mcct*'],
                ],
```

- [ ] **Step 6: Thêm breadcrumb**

Trong `routes/breadcrumbs.php`, thêm:

```php
Breadcrumbs::register('insurance.mcct', function ($breadcrumbs) {
    $breadcrumbs->parent('home');
    $breadcrumbs->push('Tra cứu tiền cùng chi trả', route('insurance.mcct'));
});
```

- [ ] **Step 7: Viết controller**

Tạo `app/Http/Controllers/Insurance/Manager/McctController.php`:

```php
<?php

namespace App\Http\Controllers\Insurance\Manager;

use App\Http\Controllers\Controller;
use App\Http\Requests\McctRequest;
use App\Models\Mcct\McctTraCuu;
use App\Services\BHYT\CoSoTraCuu;
use App\Services\Mcct\McctLuuTraCuu;
use App\Services\Mcct\McctTraCuuService;
use App\Services\Mcct\McctXacThucException;
use App\Services\Mcct\NguongMienCungChiTra;
use Illuminate\Http\Request;

class McctController extends Controller
{
    public function index(Request $request)
    {
        return view('insurance.manager.mcct.index', [
            'params' => $this->thamSoRong(),
            'danhSachCoSo' => CoSoTraCuu::tuCauHinh(),
        ]);
    }

    public function search(McctRequest $request)
    {
        $params = [
            'ma_cskcb' => trim((string) $request->get('ma_cskcb')),
            'ma_the' => McctRequest::chuanHoaMaThe($request->get('ma_the')),
            'ho_ten' => mb_strtoupper(trim((string) $request->get('ho_ten'))),
            'ngay_sinh' => trim((string) $request->get('ngay_sinh')),
        ];

        $duLieu = [
            'params' => $params,
            'danhSachCoSo' => CoSoTraCuu::tuCauHinh(),
        ];

        // Thieu ma co so thi DUNG LAI o man da dien san chu khong bao loi: nguoi dung khong
        // lam gi sai, va cung chua biet dung tai khoan cua co so nao de goi.
        if ($params['ma_cskcb'] === '') {
            flash('Chọn cơ sở khám chữa bệnh rồi bấm Tra cứu')->warning();

            return view('insurance.manager.mcct.index', $duLieu);
        }

        try {
            $kq = (new McctTraCuuService($params['ma_cskcb']))
                ->traCuu($params['ma_the'], $params['ho_ten'], $params['ngay_sinh']);
        } catch (McctXacThucException $e) {
            flash($e->getMessage())->error();

            return view('insurance.manager.mcct.index', $duLieu);
        } catch (\InvalidArgumentException $e) {
            // CauHinhCoSo nem khi co so chua khai tai khoan. Noi ro khai o dau - thong bao
            // chung chung khien nguoi dung di do nham sang phia cong.
            flash('Cơ sở ' . $params['ma_cskcb'] . ' chưa khai tài khoản cổng BHXH trong '
                . 'config/organization.php, khối BHYT_CO_SO.')->error();

            return view('insurance.manager.mcct.index', $duLieu);
        } catch (\Exception $e) {
            flash('Không kết nối được cổng BHXH: ' . $e->getMessage())->error();

            return view('insurance.manager.mcct.index', $duLieu);
        }

        $nguong = NguongMienCungChiTra::nguong(
            date('Y-m-d'),
            (array) config('mcct.luong_co_so', []),
            (int) config('mcct.so_thang_luong_co_so', 6)
        );

        $duDieuKien = $kq->thanhCong()
            ? NguongMienCungChiTra::duDieuKien($kq->luyKeLonNhat(), $nguong)
            : null;

        McctLuuTraCuu::luu($kq, array_merge($params, [
            'nguon' => 'thu_cong',
            'tra_boi' => \Auth::check() ? \Auth::user()->username : null,
            'nguong' => $nguong,
            'du_dieu_kien' => $duDieuKien,
        ]));

        $this->baoTrangThai($kq);

        return view('insurance.manager.mcct.index', array_merge($duLieu, [
            'ketQua' => $kq,
            'nguong' => $nguong,
            'duDieuKien' => $duDieuKien,
            'lichSu' => McctTraCuu::where('ma_the', $params['ma_the'])
                ->orderBy('id', 'desc')->take(5)->get(),
        ]));
    }

    /**
     * Doi ma ket qua cua cong thanh thong bao.
     *
     * Ma 500 duoc TACH LAM HAI theo noi dung GhiChu: "loi trong qua trinh tra cuu" nghia la
     * tai khoan bi han che tra cuu - van de tai khoan, khong phai loi he thong. Gop chung se
     * day nguoi doc di do nham huong hang gio.
     */
    private function baoTrangThai($kq)
    {
        if ($kq->maKetQua === '200') {
            return;
        }

        if ($kq->maKetQua === '204') {
            flash('Không tìm thấy dữ liệu. Có thể do sai thông tin thẻ, hoặc thẻ chưa phát '
                . 'sinh chi phí cùng chi trả.')->warning();

            return;
        }

        if ($kq->maKetQua === '500' && mb_strpos($kq->ghiChu, 'quá trình tra cứu') !== false) {
            flash('Tài khoản cổng BHXH của cơ sở đang bị hạn chế tra cứu. Liên hệ BHXH tỉnh '
                . 'để được mở.')->error();

            return;
        }

        flash('Cổng BHXH báo lỗi (' . $kq->maKetQua . '): ' . $kq->ghiChu)->error();
    }

    private function thamSoRong()
    {
        return [
            'ma_cskcb' => '',
            'ma_the' => '',
            'ho_ten' => '',
            'ngay_sinh' => '',
        ];
    }
}
```

- [ ] **Step 8: Viết ba blade**

Tạo `resources/views/insurance/manager/mcct/index.blade.php`:

```blade
@extends('adminlte::page')

@section('title', 'Tra cứu tiền cùng chi trả')

@section('content_header')
<h1>
    Tra cứu
    <small>tiền cùng chi trả / miễn cùng chi trả</small>
</h1>
@stop

@section('content')
@include('includes.message')
@include('insurance.manager.mcct.search')
@include('insurance.manager.mcct.result')
@stop
```

Tạo `resources/views/insurance/manager/mcct/search.blade.php`:

```blade
<div class="panel panel-default">
    <div class="panel-body">
        <form type="GET" action="{{ route('insurance.mcct.search') }}">
            <div class="col-sm-3">
                <div class="form-group">
                    <label for="ma_cskcb">Cơ sở KCB</label>
                    <select class="form-control" name="ma_cskcb" id="ma_cskcb">
                        <option value="">-- Chọn cơ sở --</option>
                        @foreach ($danhSachCoSo as $ma => $nhan)
                            <option value="{{ $ma }}" {{ (string) $params['ma_cskcb'] === (string) $ma ? 'selected' : '' }}>{{ $nhan }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="col-sm-3">
                <div class="form-group">
                    <label for="ma_the">Mã thẻ BHYT</label>
                    <input class="form-control" type="text" name="ma_the" value="{{ $params['ma_the'] }}" placeholder="10, 12 hoặc 15 ký tự">
                </div>
            </div>

            <div class="col-sm-3">
                <div class="form-group">
                    <label for="ho_ten">Họ và tên</label>
                    <input class="form-control" type="text" name="ho_ten" value="{{ $params['ho_ten'] }}">
                </div>
            </div>

            <div class="col-sm-2">
                <div class="form-group">
                    <label for="ngay_sinh">Ngày sinh</label>
                    <input class="form-control" type="text" name="ngay_sinh" value="{{ $params['ngay_sinh'] }}" placeholder="dd/mm/yyyy">
                </div>
            </div>

            <div class="col-sm-1">
                <div class="form-group">
                    <label>&nbsp;</label>
                    <button type="submit" class="btn btn-primary form-control">Tra cứu</button>
                </div>
            </div>
        </form>
    </div>
</div>
```

Tạo `resources/views/insurance/manager/mcct/result.blade.php`:

```blade
@if (isset($ketQua) && $ketQua->thanhCong())
<div class="panel panel-default">
    <div class="panel-body">
        <div class="form-group"><b>Thông tin thẻ</b></div>
        <table class="table table-condensed">
            <tr>
                <td class="col-md-3">Họ tên: <b>{{ array_get($ketQua->thongTinThe, 'ho_ten') }}</b></td>
                <td class="col-md-3">Ngày sinh: {{ array_get($ketQua->thongTinThe, 'ngay_sinh') }}</td>
                <td class="col-md-3">Mã số BHXH: {{ array_get($ketQua->thongTinThe, 'ma_bhxh') }}</td>
                <td class="col-md-3">Thẻ hết hạn: {{ array_get($ketQua->thongTinThe, 'ngay_ket_thuc') }}</td>
            </tr>
        </table>
    </div>
</div>

<div class="panel {{ $duDieuKien ? 'panel-success' : 'panel-warning' }}">
    <div class="panel-body">
        <table class="table table-condensed">
            <tr>
                <td class="col-md-4">Lũy kế cùng chi trả:
                    <b>{{ number_format($ketQua->luyKeLonNhat(), 0, ',', '.') }} đ</b></td>
                <td class="col-md-4">Ngưỡng {{ config('mcct.so_thang_luong_co_so') }} tháng lương cơ sở:
                    <b>{{ number_format($nguong, 0, ',', '.') }} đ</b></td>
                <td class="col-md-4">
                    @if ($duDieuKien)
                        <span class="label label-success">ĐỦ ĐIỀU KIỆN MIỄN CÙNG CHI TRẢ</span>
                    @else
                        <span class="label label-warning">CÒN THIẾU
                            {{ number_format(max(0, $nguong - $ketQua->luyKeLonNhat()), 0, ',', '.') }} đ</span>
                    @endif
                </td>
            </tr>
        </table>

        {{-- GhiChu NGUYEN VAN: no ghi du lieu cong "tinh den" thoi diem nao. So lieu cong co
             do tre, nguoi dung phai thay moc do TRUOC khi ket luan voi nguoi benh. --}}
        <small class="text-muted">{{ $ketQua->ghiChu }}</small>
    </div>
</div>

<div class="panel panel-default">
    <div class="panel-body">
        <div class="form-group"><b>Chi tiết các đợt khám chữa bệnh</b></div>
        <table class="table table-condensed table-hover">
            <tr>
                <th>Mã CSKCB</th>
                <th>Ngày vào</th>
                <th>Ngày ra</th>
                <th>Đối tượng</th>
                <th class="text-right">Tiền CCT thuộc diện miễn</th>
                <th class="text-right">Lũy kế</th>
                <th>Ngày nhận</th>
            </tr>
            {{-- Giu NGUYEN thu tu cong tra (da giam dan theo ngay ra vien), khong sap lai --}}
            @foreach ($ketQua->dong as $d)
            <tr>
                <td>{{ $d['ma_cskcb'] }}</td>
                <td>{{ $d['ngay_vao'] }}</td>
                <td>{{ $d['ngay_ra'] }}</td>
                <td>{{ $d['ma_doi_tuong_kcb'] }}</td>
                <td class="text-right">{{ number_format($d['t_bn_cct_mcct'], 0, ',', '.') }}</td>
                <td class="text-right">{{ number_format($d['t_bn_cct_luy_ke'], 0, ',', '.') }}</td>
                <td>{{ $d['ngay_nhan'] }}</td>
            </tr>
            @endforeach
        </table>
    </div>
</div>
@endif

@if (isset($lichSu) && count($lichSu) > 0)
<div class="panel panel-default">
    <div class="panel-body">
        <div class="form-group"><b>Lịch sử tra cứu thẻ này</b></div>
        <table class="table table-condensed">
            <tr>
                <th>Thời điểm</th>
                <th>Cơ sở</th>
                <th>Kết quả</th>
                <th class="text-right">Lũy kế</th>
                <th class="text-right">Ngưỡng khi tra</th>
                <th>Người tra</th>
            </tr>
            @foreach ($lichSu as $ls)
            <tr>
                <td>{{ $ls->tra_luc }}</td>
                <td>{{ $ls->ma_cskcb }}</td>
                <td>{{ $ls->ma_ket_qua }}</td>
                <td class="text-right">{{ number_format($ls->luy_ke_lon_nhat, 0, ',', '.') }}</td>
                <td class="text-right">{{ number_format($ls->nguong_ap_dung, 0, ',', '.') }}</td>
                <td>{{ $ls->tra_boi }}</td>
            </tr>
            @endforeach
        </table>
    </div>
</div>
@endif
```

- [ ] **Step 9: Thêm liên kết từ màn tra cứu thẻ**

Trong `resources/views/insurance/manager/check-card/result.blade.php`, chèn ngay trước dòng
`@include('insurance.manager.check-card.includes.detail_history_medical')`:

```blade
{{-- Chi mot the <a>, KHONG them form: form cua man nay co rang buoc - o chon co so phai nam
     trong form vi luong quet QR tu goi $('#target').submit(). Them form thu hai la cach
     chac chan lam hong luong quet QR dang chay. --}}
<div class="form-group">
    <a class="btn btn-default" href="{{ route('insurance.mcct.search', [
        'ma_cskcb' => $params['ma_cskcb'],
        'ma_the' => $params['card-number'],
        'ho_ten' => $params['name'],
        'ngay_sinh' => $params['birthday'],
    ]) }}">Tra tiền cùng chi trả</a>
</div>
```

- [ ] **Step 10: Chạy test route và menu**

Chạy: `php vendor/bin/phpunit --filter "RouteMcctTest|MenuMcctTest"`
Kỳ vọng: PASS — 5 test

- [ ] **Step 11: Chạy toàn bộ bộ test để chắc không làm hỏng gì**

Chạy: `php vendor/bin/phpunit`
Kỳ vọng: số test hỏng **không tăng** so với trước Task 1. Mốc đỏ đã biết trước: đúng **một**
test cấu hình CTĐT đỏ có chủ đích — không sửa nó.

- [ ] **Step 12: Commit**

```bash
git add app/Http/Controllers/Insurance/Manager/McctController.php resources/views/insurance/manager/mcct routes/web.php routes/breadcrumbs.php config/adminlte.php resources/views/insurance/manager/check-card/result.blade.php tests/Unit/Mcct/RouteMcctTest.php tests/Unit/Mcct/MenuMcctTest.php
git commit -m "feat(mcct): man tra cuu tien cung chi tra

Man rieng trong nhom insurance/, cung muc quyen voi man tra cuu the. Khong
lam tab trong man tra cuu the: form o do co rang buoc quet QR tu submit, them
form thu hai la lam hong luong dang chay - chi them mot lien ket sang day.

Hien GhiChu nguyen van duoi khoi ket luan: so lieu cong co do tre, nguoi dung
phai thay moc 'tinh den' truoc khi ket luan voi nguoi benh.

Ma 500 tach lam hai theo GhiChu: tai khoan bi han che tra cuu la van de tai
khoan, khong phai loi he thong.

Co-Authored-By: Claude Opus 5 <noreply@anthropic.com>"
```

---

## Task 7: Nghiệm thu trên môi trường chính thức

Không có test nào thay được bước này. Làm bằng tay, ghi lại kết quả.

**Files:** không sửa mã trừ khi bước 2 hoặc 3 thất bại.

- [ ] **Step 1: Xác nhận cấu hình**

Kiểm `config/organization.php`: `$bhxhBaseUrl` đang là `https://egw.baohiemxahoi.gov.vn`, và
cơ sở định tra đã khai đủ `username` / `password` trong `BHYT_CO_SO`.

- [ ] **Step 2: Tra thật một thẻ có phát sinh chi phí**

Mở màn "Tra cứu tiền cùng chi trả", chọn cơ sở, nhập một thẻ BHYT **đã có đợt KCB trong
năm**, bấm Tra cứu.

Kỳ vọng: `MaKetQua = 200`, hiện thông tin thẻ, khối kết luận và bảng chi tiết.

**Nếu trả `500` hoặc `400` liên quan tới tài khoản:** đây chính là điểm chưa chắc chắn ở mục
5 của spec — cổng có thể đòi `username` là **mã CSKCB** thay vì tài khoản đăng nhập. Sửa
một dòng trong `McctTraCuuService::goi()`:

```php
                    'username' => $this->loginService->username(),
```

thành:

```php
                    // Cong doi MA CSKCB o truong nay, khong phai tai khoan dang nhap - xac
                    // nhan bang nghiem thu that (ghi ngay nghiem thu vao day).
                    'username' => $maCskcb,
```

(thêm `private $maCskcb;` gán trong constructor), cập nhật `McctXacThucTest` cho khớp, rồi
chạy lại `php vendor/bin/phpunit --filter McctXacThucTest` và tra lại.

- [ ] **Step 3: Xác nhận IP và giao thức**

Nếu bước 2 trả `401` ngay cả sau khi service đã tự lấy lại phiên: kiểm địa chỉ IP mà máy chủ
qlbv dùng để gọi cổng có trùng với IP đã đăng ký với BHXH không (cổng khoá theo IP).

Nếu lỗi là từ chối kết nối TLS: phụ lục viết URL dạng `http://`, còn `base_url` chung đang là
`https://`. Thử đổi `$bhxhBaseUrl` tạm sang `http://` để xác nhận nguyên nhân, rồi ghi kết
quả lại — **không** commit việc hạ giao thức nếu `https` vẫn chạy được.

- [ ] **Step 4: Đối chiếu số liệu**

Ghi lại nguyên văn `GhiChu` cổng trả về và số lũy kế hiển thị. Kiểm ba điều:

1. Số "Lũy kế cùng chi trả" trên khối kết luận bằng giá trị `tBNCCTLuyKe` **lớn nhất** trong
   bảng chi tiết, không phải giá trị của dòng đầu.
2. Ngưỡng hiển thị = 6 × lương cơ sở hiện hành.
3. Trong CSDL, `select ma_ket_qua, luy_ke_lon_nhat, nguong_ap_dung, du_dieu_kien_mien,
   ghi_chu from mcct_tra_cuu order by id desc limit 1;` trả về đúng các giá trị vừa nhìn
   thấy trên màn.

- [ ] **Step 5: Tra một thẻ chưa phát sinh chi phí**

Kỳ vọng: thông báo vàng "Không tìm thấy dữ liệu…", và trong CSDL **vẫn có** một dòng
`mcct_tra_cuu` với `ma_ket_qua = '204'` và không có dòng `mcct_chi_phi` nào.

- [ ] **Step 6: Ghi kết quả nghiệm thu vào spec**

Thêm một mục ngắn vào cuối `docs/superpowers/specs/2026-09-03-mcct-giai-doan-1-design.md`
ghi ngày nghiệm thu, giá trị `username` mà cổng chấp nhận, giao thức dùng được, và nguyên
văn một `GhiChu` mẫu. Commit.

```bash
git add docs/superpowers/specs/2026-09-03-mcct-giai-doan-1-design.md
git commit -m "docs(mcct): ghi ket qua nghiem thu giai doan 1 tren moi truong chinh thuc

Co-Authored-By: Claude Opus 5 <noreply@anthropic.com>"
```
