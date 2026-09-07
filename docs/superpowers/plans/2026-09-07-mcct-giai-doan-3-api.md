# MCCT Giai đoạn 3 — API cho hệ thống ngoài: Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Mở `GET /api/mcct/tra-cuu` cho hệ thống ngoài (trước hết là HIS) lấy thông tin tiền
cùng chi trả của người bệnh, mặc định trả dữ liệu đã lưu trong dưới 100ms và chỉ gọi cổng
BHXH khi được yêu cầu.

**Architecture:** Tách lõi "gọi cổng → tính ngưỡng → lưu vết" từ `McctController` ra
`McctTraCuuChung` dùng chung cho web và API. Thêm hàm thuần `QuyetDinhGoiCong` quyết định có
gọi cổng hay không (chống bùng nổ lượt gọi). `McctApiController` chỉ đổi kết quả thành khuôn
JSON `{success, data, meta}` đã có tiền lệ trong dự án.

**Tech Stack:** Laravel 5.5 (PHP 7), PHPUnit 6.5.14, MySQL.

**Spec:** `docs/superpowers/specs/2026-09-07-mcct-giai-doan-3-api-design.md`

## Global Constraints

Áp dụng cho **mọi** task:

- **Chốt an toàn CSDL.** Không dùng `RefreshDatabase`, `DatabaseMigrations`, hay
  `migrate:fresh`. Bộ test chạy trên schema `qlbv_test`; CSDL `qlbv` là CSDL phát triển thật
  và đã từng bị xoá sạch vì một test dùng `DatabaseMigrations`. Test chạm CSDL phải tự dọn
  theo đúng id nó tạo ra.
- **Không test nào gọi cổng BHXH thật.** Bộ test của dự án này đã một lần vô tình gửi request
  đăng nhập thật lên cổng sản xuất. Nhánh gọi cổng đã được `McctXacThucTest` phủ ở giai đoạn 1
  bằng `Guzzle MockHandler`; kế hoạch này **không** thêm test nào chạm mạng.
- **Cú pháp test theo dự án:** `/** @test */`, tên phương thức tiếng Việt không dấu, kế thừa
  `Tests\TestCase`. Unit test đặt ở `tests/Unit/Mcct`, feature test ở `tests/Feature`.
- **Lệnh chạy test:** `php vendor/bin/phpunit --filter <TênLớp>` từ `C:\Users\tracnn\qlbv`.
- **Không bao giờ** ghi `accessToken` hoặc `passwordHash` vào log, và không để lộ thông điệp
  ngoại lệ ra ngoài API.
- **Hai tên trường bắt buộc trong `data`:** `du_nguong_6_thang_luong` (KHÔNG phải
  `du_dieu_kien_mien`) và `can_kiem_5_nam_lien_tuc` luôn có mặt. Điều kiện miễn gồm hai vế,
  API chỉ kiểm được một.
- Chú thích trong mã viết **tiếng Việt không dấu**, giải thích **vì sao**. Chuỗi hiện cho
  người dùng / trả qua API viết tiếng Việt **có dấu**.
- Commit message viết **tiếng Việt không dấu**, kết thúc bằng dòng
  `Co-Authored-By: Claude Opus 5 <noreply@anthropic.com>`.

## Cấu trúc tệp

| Tệp | Trách nhiệm | Task |
|---|---|---|
| `config/mcct.php` | *(sửa)* thêm `khoang_cho_lam_moi` | 1 |
| `app/Services/Mcct/QuyetDinhGoiCong.php` | Hàm thuần: có gọi cổng hay không, còn bao nhiêu giây | 1 |
| `app/Services/Mcct/McctTraCuuChung.php` | Lõi: gọi cổng → tính ngưỡng → lưu vết. Nơi **duy nhất** định nghĩa "một lần tra cứu" | 2 |
| `app/Http/Controllers/Insurance/Manager/McctController.php` | *(sửa)* bỏ `thucHien()` riêng, gọi `McctTraCuuChung` | 2 |
| `app/Http/Controllers/Api/McctApiController.php` | Đổi kết quả thành khuôn JSON của API. Không chứa logic nghiệp vụ | 3 |
| `routes/api.php` | *(sửa)* thêm một route vào nhóm sẵn có | 3 |

---

## Task 1: QuyetDinhGoiCong — chống bùng nổ lượt gọi

**Files:**
- Modify: `config/mcct.php`
- Create: `app/Services/Mcct/QuyetDinhGoiCong.php`
- Test: `tests/Unit/Mcct/QuyetDinhGoiCongTest.php`

**Interfaces:**
- Consumes: không có
- Produces: `QuyetDinhGoiCong::nen($lamMoi, $traLucGanNhat, $bayGio, $khauDoGiay): array`
  trả `['goi' => bool, 'con_lai' => int]`. `$traLucGanNhat` là chuỗi `Y-m-d H:i:s` hoặc
  `null`; `$bayGio` cùng dạng; `$khauDoGiay` là số nguyên giây.

- [ ] **Step 1: Thêm cấu hình**

Trong `config/mcct.php`, thêm ngay sau `'so_thang_luong_co_so' => 6,`:

```php
    /*
     * Khau do chan goi lai cong cho CUNG mot ma the, tinh bang giay.
     *
     * Cong co danh sach tai khoan bi han che tra cuu, nen so luot goi la tai nguyen co han.
     * Mot vong lap hong o he thong goi API co the lam tai khoan cua ca benh vien bi khoa.
     *
     * De 0 la TAT chan - moi lan lam_moi=1 deu goi cong that.
     */
    'khoang_cho_lam_moi' => 900,
```

- [ ] **Step 2: Viết test thất bại**

Tạo `tests/Unit/Mcct/QuyetDinhGoiCongTest.php`:

```php
<?php

namespace Tests\Unit\Mcct;

use App\Services\Mcct\QuyetDinhGoiCong;
use Tests\TestCase;

class QuyetDinhGoiCongTest extends TestCase
{
    const BAY_GIO = '2026-09-07 12:00:00';

    /** @test */
    public function khong_yeu_cau_lam_moi_thi_khong_goi_cong()
    {
        $ra = QuyetDinhGoiCong::nen(false, null, self::BAY_GIO, 900);

        $this->assertFalse($ra['goi']);
        $this->assertSame(0, $ra['con_lai']);
    }

    /** Chua tung tra the nay: phai goi, du dang bat lam_moi hay khong co ban ghi cu */
    /** @test */
    public function lam_moi_va_chua_tung_tra_thi_goi_cong()
    {
        $ra = QuyetDinhGoiCong::nen(true, null, self::BAY_GIO, 900);

        $this->assertTrue($ra['goi']);
        $this->assertSame(0, $ra['con_lai']);
    }

    /**
     * Vua tra 5 phut truoc, khau do 15 phut: KHONG goi lai. Con lai 600 giay.
     *
     * Day la nhanh quan trong nhat - no la thu chan mot vong lap hong o he thong goi lam
     * chay het han muc cua cong.
     */
    /** @test */
    public function vua_tra_trong_khau_do_thi_khong_goi_lai()
    {
        $ra = QuyetDinhGoiCong::nen(true, '2026-09-07 11:55:00', self::BAY_GIO, 900);

        $this->assertFalse($ra['goi']);
        $this->assertSame(600, $ra['con_lai']);
    }

    /** @test */
    public function tra_qua_khau_do_thi_goi_lai()
    {
        $ra = QuyetDinhGoiCong::nen(true, '2026-09-07 11:44:00', self::BAY_GIO, 900);

        $this->assertTrue($ra['goi']);
        $this->assertSame(0, $ra['con_lai']);
    }

    /** Dung BIEN khau do: 900 giay truoc thi da het khau do, duoc goi lai */
    /** @test */
    public function dung_bien_khau_do_thi_duoc_goi_lai()
    {
        $ra = QuyetDinhGoiCong::nen(true, '2026-09-07 11:45:00', self::BAY_GIO, 900);

        $this->assertTrue($ra['goi']);
    }

    /** Truoc bien mot giay thi van bi chan, con lai dung 1 giay */
    /** @test */
    public function truoc_bien_mot_giay_thi_van_bi_chan()
    {
        $ra = QuyetDinhGoiCong::nen(true, '2026-09-07 11:45:01', self::BAY_GIO, 900);

        $this->assertFalse($ra['goi']);
        $this->assertSame(1, $ra['con_lai']);
    }

    /** Khau do 0 = TAT chan: luon goi cong */
    /** @test */
    public function khau_do_bang_khong_thi_luon_goi()
    {
        $ra = QuyetDinhGoiCong::nen(true, self::BAY_GIO, self::BAY_GIO, 0);

        $this->assertTrue($ra['goi']);
    }

    /**
     * Moc tra cuu nam o TUONG LAI (dong ho lech, hoac du lieu hong): coi nhu vua tra xong -
     * chan lai. Cho goi la mo duong cho mot dong ho lech lam thung ca co che chan.
     */
    /** @test */
    public function moc_tra_o_tuong_lai_thi_van_chan()
    {
        $ra = QuyetDinhGoiCong::nen(true, '2026-09-07 13:00:00', self::BAY_GIO, 900);

        $this->assertFalse($ra['goi']);
        $this->assertSame(900, $ra['con_lai'], 'Chan tron khau do, khong tra so am');
    }

    /** Chuoi thoi gian rac thi coi nhu chua tra bao gio - khong duoc lam vo phep tinh */
    /** @test */
    public function moc_tra_khong_doc_duoc_thi_coi_nhu_chua_tra()
    {
        $ra = QuyetDinhGoiCong::nen(true, 'khong-phai-ngay-thang', self::BAY_GIO, 900);

        $this->assertTrue($ra['goi']);
    }
}
```

- [ ] **Step 3: Chạy test để xác nhận nó hỏng**

Chạy: `php vendor/bin/phpunit --filter QuyetDinhGoiCongTest`
Kỳ vọng: FAIL — `Class 'App\Services\Mcct\QuyetDinhGoiCong' not found`

- [ ] **Step 4: Viết cài đặt tối thiểu**

Tạo `app/Services/Mcct/QuyetDinhGoiCong.php`:

```php
<?php

namespace App\Services\Mcct;

/**
 * Quyet dinh mot loi goi API co duoc phep cham toi cong BHXH hay khong.
 *
 * VI SAO CAN: cong co danh sach tai khoan bi han che tra cuu, nen so luot goi la tai nguyen
 * co han. He thong goi API nam ngoai tam kiem soat cua qlbv - mot vong lap hong ben do co
 * the lam tai khoan cua ca benh vien bi khoa.
 *
 * Ham THUAN: nhan thoi diem lam tham so chu khong tu doc dong ho, nen kiem duoc moc bien ma
 * khong phai cho that 15 phut.
 */
class QuyetDinhGoiCong
{
    /**
     * @param bool $lamMoi ben goi co yeu cau goi cong that hay khong
     * @param string|null $traLucGanNhat 'Y-m-d H:i:s' cua lan tra gan nhat; null neu chua tra
     * @param string $bayGio 'Y-m-d H:i:s'
     * @param int $khauDoGiay 0 = tat chan
     * @return array ['goi' => bool, 'con_lai' => int so giay con phai cho]
     */
    public static function nen($lamMoi, $traLucGanNhat, $bayGio, $khauDoGiay)
    {
        if (!$lamMoi) {
            return ['goi' => false, 'con_lai' => 0];
        }

        $khauDo = (int) $khauDoGiay;

        if ($khauDo <= 0) {
            return ['goi' => true, 'con_lai' => 0];
        }

        $moc = self::mocThoiGian($traLucGanNhat);
        $now = self::mocThoiGian($bayGio);

        // Chua tung tra, hoac moc khong doc duoc: khong co gi de chan. Doan bua mot moc con
        // te hon la khong chan - no se chan nhung lan goi hop le mot cach ngau nhien.
        if ($moc === null || $now === null) {
            return ['goi' => true, 'con_lai' => 0];
        }

        $daQua = $now - $moc;

        // Moc nam o TUONG LAI (dong ho lech hoac du lieu hong): coi nhu vua tra xong. Cho goi
        // la mo duong cho mot dong ho lech lam thung ca co che chan.
        if ($daQua < 0) {
            return ['goi' => false, 'con_lai' => $khauDo];
        }

        if ($daQua >= $khauDo) {
            return ['goi' => true, 'con_lai' => 0];
        }

        return ['goi' => false, 'con_lai' => $khauDo - $daQua];
    }

    /** @return int|null dau thoi gian Unix; null neu khong doc duoc */
    private static function mocThoiGian($chuoi)
    {
        $chuoi = trim((string) $chuoi);

        if ($chuoi === '') {
            return null;
        }

        $t = strtotime($chuoi);

        return $t === false ? null : $t;
    }
}
```

- [ ] **Step 5: Chạy test để xác nhận nó xanh**

Chạy: `php vendor/bin/phpunit --filter QuyetDinhGoiCongTest`
Kỳ vọng: PASS — 9 test

- [ ] **Step 6: Commit**

```bash
git add config/mcct.php app/Services/Mcct/QuyetDinhGoiCong.php tests/Unit/Mcct/QuyetDinhGoiCongTest.php
git commit -m "feat(mcct): ham thuan quyet dinh co goi cong hay khong

Chan goi lai cung mot ma the trong khau do 15 phut. Cong co danh sach tai
khoan bi han che tra cuu, va he thong goi API nam ngoai tam kiem soat cua
qlbv - mot vong lap hong ben do co the lam tai khoan ca benh vien bi khoa.

Moc tra o tuong lai (dong ho lech) thi VAN chan: cho goi la mo duong cho mot
dong ho lech lam thung ca co che chan.

Co-Authored-By: Claude Opus 5 <noreply@anthropic.com>"
```

---

## Task 2: McctTraCuuChung — lõi dùng chung cho web và API

**Files:**
- Create: `app/Services/Mcct/McctTraCuuChung.php`
- Modify: `app/Http/Controllers/Insurance/Manager/McctController.php`
- Test: không thêm test mới; các test sẵn có phải vẫn xanh

**Interfaces:**
- Consumes: `McctTraCuuService`, `NguongMienCungChiTra`, `McctLuuTraCuu`,
  `McctXacThucException` (đều đã có từ giai đoạn 1)
- Produces: `McctTraCuuChung::goiVaLuu(array $params, $nguon = 'thu_cong'): array` trả về
  `['loi' => string|null, 'ma_loi' => string|null, 'kq' => KetQuaMcct|null,
  'nguong' => float, 'du_dieu_kien' => bool|null, 'muc' => array|null,
  'loi_luu' => string|null]`. `$params` có bốn khoá `ma_cskcb`, `ma_the`, `ho_ten`,
  `ngay_sinh`. `ma_loi` nhận một trong: `XAC_THUC`, `CAU_HINH`, `HET_GIO`, `MANG`, `KHAC`.

- [ ] **Step 1: Tạo lớp lõi**

Tạo `app/Services/Mcct/McctTraCuuChung.php` — chép nguyên phần thân của
`McctController::thucHien()` hiện có, thêm khoá `ma_loi`:

```php
<?php

namespace App\Services\Mcct;

/**
 * Loi mot lan tra cuu MCCT: goi cong -> tinh nguong -> luu vet.
 *
 * VI SAO TACH RA KHOI CONTROLLER: ca man web lan API cho he thong ngoai deu can DUNG chuoi
 * nay. Chep doi nghia la co hai cho quyet dinh "mot lan tra cuu nghia la gi" - va du an nay
 * da bi can ba lan vi chep doi (hai con so timeout, hai bo dung ket qua javascript).
 *
 * Lop nay KHONG biet gi ve HTTP: khong doc Request, khong tra Response. Ai goi thi tu doi
 * ket qua sang khuon cua minh.
 */
class McctTraCuuChung
{
    /**
     * @param array $params bon khoa ma_cskcb, ma_the, ho_ten, ngay_sinh (da chuan hoa)
     * @param string $nguon ghi vao cot nguon: 'thu_cong' | 'hang_loat' | 'api_his'
     * @return array ['loi' => string|null, 'ma_loi' => string|null, 'kq' => KetQuaMcct|null,
     *                'nguong' => float, 'du_dieu_kien' => bool|null, 'muc' => array|null,
     *                'loi_luu' => string|null]
     */
    public static function goiVaLuu(array $params, $nguon = 'thu_cong')
    {
        $hong = function ($maLoi, $loi) {
            return ['loi' => $loi, 'ma_loi' => $maLoi, 'kq' => null, 'nguong' => 0.0,
                'du_dieu_kien' => null, 'muc' => null, 'loi_luu' => null];
        };

        try {
            $kq = (new McctTraCuuService($params['ma_cskcb']))
                ->traCuu($params['ma_the'], $params['ho_ten'], $params['ngay_sinh']);
        } catch (McctXacThucException $e) {
            return $hong('XAC_THUC', $e->getMessage());
        } catch (\InvalidArgumentException $e) {
            // CauHinhCoSo nem khi co so chua khai tai khoan. Noi ro khai o dau - thong bao
            // chung chung khien nguoi dung di do nham sang phia cong.
            return $hong('CAU_HINH', 'Cơ sở ' . $params['ma_cskcb'] . ' chưa khai tài khoản '
                . 'cổng BHXH trong config/organization.php, khối BHYT_CO_SO.');
        } catch (\GuzzleHttp\Exception\ConnectException $e) {
            // Tach rieng HET GIO khoi "khong ket noi duoc": hai chuyen khac han nhau. Het gio
            // nghia la cong CO song nhung tra loi qua cham - chi can thu lai, chu khong phai
            // di goi bo phan mang.
            if (mb_stripos($e->getMessage(), 'timed out') !== false
                || mb_stripos($e->getMessage(), 'timeout') !== false) {
                return $hong('HET_GIO', 'Cổng BHXH không trả lời sau '
                    . (int) config('mcct.timeout_tong', 60) . ' giây. Cổng đang quá tải; '
                    . 'chờ ít phút rồi tra lại.');
            }

            return $hong('MANG', 'Không kết nối được cổng BHXH: ' . $e->getMessage());
        } catch (\Exception $e) {
            // CongBhxh::baseUrl() va BHYTLoginService::login() nem loi CAU HINH (thieu
            // base_url, thieu tai khoan...), khong phai loi mang. Gan cung mot cau "khong ket
            // noi duoc" se day nguoi doc di do nham huong mang.
            return $hong('KHAC', 'Lỗi khi gọi cổng BHXH: ' . $e->getMessage());
        }

        $bangLuong = (array) config('mcct.luong_co_so', []);
        $soThang = (int) config('mcct.so_thang_luong_co_so', 6);

        $nguong = NguongMienCungChiTra::nguong(date('Y-m-d'), $bangLuong, $soThang);

        // Muc mien tinh THEO DUNG diem c khoan 2 Dieu 18 ND 188/2025: khi luong co so doi
        // giua nam, khong duoc lay thang 6 x luong hien hanh lam nguong.
        $muc = NguongMienCungChiTra::tinhTheoQuyDinh($kq->dong, date('Y-m-d'), $bangLuong, $soThang);

        $duDieuKien = $kq->thanhCong() ? $muc['du_dieu_kien'] : null;

        // Luu hong thi VAN tra ket qua: luot goi len cong da tieu roi, va cong co danh sach
        // tai khoan bi han che tra cuu nen khong duoc de mot loi ghi CSDL nuot mat ca ket qua.
        $loiLuu = null;

        try {
            McctLuuTraCuu::luu($kq, array_merge($params, [
                'nguon' => $nguon,
                'tra_boi' => \Auth::check() ? \Auth::user()->username : null,
                'nguong' => $nguong,
                'du_dieu_kien' => $duDieuKien,
                'so_tien_con_phai_dong' => $muc['so_tien_con_phai_dong'],
                'da_dong_truoc_moc' => $muc['da_dong_truoc_moc'],
            ]));
        } catch (\Exception $e) {
            \Log::error('MCCT khong luu duoc lich su tra cuu: ' . $e->getMessage());
            $loiLuu = 'Đã tra cứu được nhưng không lưu được lịch sử tra cứu.';
        }

        return ['loi' => null, 'ma_loi' => null, 'kq' => $kq, 'nguong' => $nguong,
            'du_dieu_kien' => $duDieuKien, 'muc' => $muc, 'loi_luu' => $loiLuu];
    }
}
```

- [ ] **Step 2: Sửa `McctController` dùng lõi chung**

Trong `app/Http/Controllers/Insurance/Manager/McctController.php`:

1. **Xoá hẳn** phương thức `private function thucHien(array $params)` cùng toàn bộ thân của nó.
2. Trong `api()`, đổi dòng `$ra = $this->thucHien($params);` thành:

```php
        $ra = McctTraCuuChung::goiVaLuu($params, 'thu_cong');
```

3. Thêm `use App\Services\Mcct\McctTraCuuChung;` vào khối `use` ở đầu tệp.
4. Xoá các `use` giờ đã thừa nếu không còn chỗ nào trong tệp dùng tới:
   `McctLuuTraCuu`, `McctTraCuuService`, `McctXacThucException`, `NguongMienCungChiTra`.
   **Kiểm bằng `grep` trước khi xoá từng cái** — `McctPhanHoiJson`, `McctDungLaiKetQua`,
   `McctChiPhi`, `McctTraCuu`, `McctRequest`, `CoSoTraCuu` vẫn còn dùng.

- [ ] **Step 3: Chạy toàn bộ test MCCT**

Chạy: `php vendor/bin/phpunit --filter "Mcct|MienCungChiTra|Nguong"`
Kỳ vọng: PASS — số test không đổi so với trước khi sửa (89 test tại thời điểm viết kế hoạch).
Đây là bước quan trọng nhất của task: đổi cấu trúc mà không đổi hành vi.

- [ ] **Step 4: Kiểm tệp không còn tham chiếu chết**

Chạy: `php -l app/Http/Controllers/Insurance/Manager/McctController.php`
Chạy: `grep -n "thucHien" app/Http/Controllers/Insurance/Manager/McctController.php`
Kỳ vọng: không còn dòng nào khớp `thucHien`.

- [ ] **Step 5: Commit**

```bash
git add app/Services/Mcct/McctTraCuuChung.php app/Http/Controllers/Insurance/Manager/McctController.php
git commit -m "refactor(mcct): tach loi tra cuu ra McctTraCuuChung

Ca man web lan API cho he thong ngoai deu can dung chuoi goi cong -> tinh
nguong -> luu vet. Chep doi nghia la co hai cho quyet dinh 'mot lan tra cuu
nghia la gi' - du an nay da bi can ba lan vi chep doi.

Them khoa ma_loi (XAC_THUC/CAU_HINH/HET_GIO/MANG/KHAC) de ben goi doi sang ma
HTTP ma khong phai do chuoi thong bao.

Khong doi hanh vi: bo test MCCT giu nguyen so test va van xanh.

Co-Authored-By: Claude Opus 5 <noreply@anthropic.com>"
```

---

## Task 3: McctApiController + route

**Files:**
- Create: `app/Http/Controllers/Api/McctApiController.php`
- Modify: `routes/api.php`
- Test: `tests/Feature/McctApiTest.php`

**Interfaces:**
- Consumes: `QuyetDinhGoiCong::nen()` (Task 1); `McctTraCuuChung::goiVaLuu()` (Task 2);
  `McctDungLaiKetQua::tuBanGhi()` và `McctRequest::chuanHoaMaThe()` (đã có);
  Model `McctTraCuu`, `McctChiPhi` (đã có)
- Produces: route tên `api.mcct.tra-cuu` → URI `api/mcct/tra-cuu`

- [ ] **Step 1: Viết test thất bại**

Tạo `tests/Feature/McctApiTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\Mcct\McctChiPhi;
use App\Models\Mcct\McctTraCuu;
use Tests\TestCase;

class McctApiTest extends TestCase
{
    const TOKEN = 'token-thu-nghiem';
    const MA_THE = 'HT9999999999999';

    /** @var array id cac ban ghi da tao, de don dep */
    protected $daTao = [];

    protected function setUp()
    {
        parent::setUp();

        config(['organization.api.access_token' => hash('sha256', self::TOKEN)]);
        config(['mcct.khoang_cho_lam_moi' => 900]);
    }

    /**
     * Don dep theo dung id minh tao ra.
     *
     * KHONG dung RefreshDatabase: ngay 2026-08-21 mot test dung DatabaseMigrations da DROP
     * sach CSDL phat trien.
     */
    protected function tearDown()
    {
        foreach ($this->daTao as $id) {
            McctChiPhi::where('tra_cuu_id', $id)->delete();
            McctTraCuu::where('id', $id)->delete();
        }

        parent::tearDown();
    }

    protected function goi(array $thamSo, $token = self::TOKEN)
    {
        return $this->getJson(
            '/api/mcct/tra-cuu?' . http_build_query($thamSo),
            ['Authorization' => 'Bearer ' . $token]
        );
    }

    /** Tao mot phien tra cuu thanh cong da luu, kem mot dot KCB */
    protected function taoBanGhi($traLuc = '2026-09-07 11:45:11')
    {
        $ban = McctTraCuu::create([
            'ma_cskcb' => '01929',
            'ma_the' => self::MA_THE,
            'ho_ten' => 'NGUYEN VAN TEST',
            'ngay_sinh' => '01/01/1980',
            'ma_ket_qua' => '200',
            'ghi_chu' => 'Nguồn DL ... tính đến: 14/08/2026 14:41',
            'the_ho_ten' => 'Nguyễn Văn Test',
            'the_ngay_sinh' => '01/01/1980',
            'the_ngay_ket_thuc' => '2027-06-30',
            'the_ma_bhxh' => '0100000009',
            'luy_ke_lon_nhat' => 1396758,
            'nguong_ap_dung' => 15180000,
            'du_dieu_kien_mien' => 0,
            'nguon' => 'thu_cong',
            'tra_boi' => 'kiemthu',
            'tra_luc' => $traLuc,
        ]);

        $this->daTao[] = $ban->id;

        McctChiPhi::create([
            'tra_cuu_id' => $ban->id,
            'id_cong' => 3090063339,
            'ma_the' => self::MA_THE,
            'ma_cskcb' => '01929',
            'ngay_vao' => '2026-06-12',
            'ngay_ra' => '2026-06-23',
            'ma_doi_tuong_kcb' => '1.5',
            't_bn_cct_mcct' => 1120157,
            't_bn_cct_luy_ke' => 1396758,
            'ngay_nhan_cong' => '2026-06-23',
            'ngay_nhan' => '2026-06-23',
            'ngay_tra_cuu' => '2026-09-07',
        ]);

        return $ban;
    }

    /** @test */
    public function thieu_token_thi_tra_401()
    {
        $this->getJson('/api/mcct/tra-cuu?ma_the=' . self::MA_THE)
            ->assertStatus(401)
            ->assertJson(['success' => false]);
    }

    /** @test */
    public function sai_token_thi_tra_401()
    {
        $this->goi(['ma_the' => self::MA_THE], 'token-sai')
            ->assertStatus(401)
            ->assertJson(['success' => false]);
    }

    /** @test */
    public function thieu_ma_the_thi_tra_422_dung_khuon()
    {
        $this->goi([])
            ->assertStatus(422)
            ->assertJson([
                'success' => false,
                'error' => ['code' => 'VALIDATION_ERROR'],
            ]);
    }

    /** @test */
    public function ma_the_sai_do_dai_thi_tra_422()
    {
        $this->goi(['ma_the' => 'ABC123'])
            ->assertStatus(422)
            ->assertJson(['success' => false, 'error' => ['code' => 'VALIDATION_ERROR']]);
    }

    /**
     * lam_moi=1 doi ba tham so kia. Chan o day chu khong de cong tra 400 - vua tiet kiem
     * luot goi, vua bao loi dung cho sai.
     */
    /** @test */
    public function lam_moi_ma_thieu_ho_ten_thi_tra_422()
    {
        $this->goi([
            'ma_the' => self::MA_THE,
            'lam_moi' => 1,
            'ngay_sinh' => '01/01/1980',
            'ma_cskcb' => '01929',
        ])->assertStatus(422)
          ->assertJson(['success' => false, 'error' => ['code' => 'VALIDATION_ERROR']]);
    }

    /** Chua tung tra: KHONG phai loi. data = null, kem trang thai trong meta. */
    /** @test */
    public function chua_tung_tra_thi_tra_data_null()
    {
        $this->goi(['ma_the' => 'HT0000000000000'])
            ->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => null,
                'meta' => ['trang_thai' => 'chua_tra_lan_nao'],
            ]);
    }

    /** @test */
    public function co_ban_ghi_thi_tra_du_khuon_data()
    {
        $this->taoBanGhi();

        $ra = $this->goi(['ma_the' => self::MA_THE])->assertStatus(200);

        $ra->assertJson([
            'success' => true,
            'data' => [
                'ma_the' => self::MA_THE,
                'nguon' => 'da_luu',
                'tra_luc' => '2026-09-07 11:45:11',
            ],
        ]);

        $data = $ra->json('data');

        foreach (['ma_the', 'nguon', 'tra_luc', 'ghi_chu', 'thong_tin_the',
            'luy_ke_cung_chi_tra', 'nguong_ca_nam', 'con_thieu',
            'du_nguong_6_thang_luong', 'can_kiem_5_nam_lien_tuc', 'chi_tiet'] as $khoa) {
            $this->assertArrayHasKey($khoa, $data, "Thieu khoa $khoa trong data");
        }

        $this->assertContains('tính đến: 14/08/2026 14:41', $data['ghi_chu']);
        $this->assertCount(1, $data['chi_tiet']);
        $this->assertEquals(1396758, $data['luy_ke_cung_chi_tra']);
    }

    /**
     * Dieu kien mien gom HAI ve, API chi kiem duoc mot. Ten truong phai la
     * du_nguong_6_thang_luong chu KHONG phai du_dieu_kien_mien, va co
     * can_kiem_5_nam_lien_tuc phai luon co mat - neu khong, ben goi se hien thang cho can bo
     * la "du dieu kien", dung cai sai vua sua tren man hinh qlbv.
     */
    /** @test */
    public function khong_duoc_co_truong_du_dieu_kien_mien()
    {
        $this->taoBanGhi();

        $data = $this->goi(['ma_the' => self::MA_THE])->json('data');

        $this->assertArrayNotHasKey('du_dieu_kien_mien', $data);
        $this->assertTrue($data['can_kiem_5_nam_lien_tuc']);
        $this->assertFalse($data['du_nguong_6_thang_luong']);
    }

    /**
     * lam_moi=1 nhung vua tra xong: bo qua, tra ban da luu kem co bao ro. Tra DU LIEU chu
     * khong tra 429 - mot vong lap hong ben goi se khong sinh them vong thu lai.
     *
     * Test nay KHONG cham cong: moc tra_luc dat ngay bay gio nen nhanh goi cong khong bao gio
     * duoc chay toi.
     */
    /** @test */
    public function lam_moi_trong_khau_do_thi_bo_qua_va_khong_goi_cong()
    {
        $this->taoBanGhi(date('Y-m-d H:i:s'));

        $ra = $this->goi([
            'ma_the' => self::MA_THE,
            'lam_moi' => 1,
            'ho_ten' => 'NGUYEN VAN TEST',
            'ngay_sinh' => '01/01/1980',
            'ma_cskcb' => '01929',
        ])->assertStatus(200);

        $ra->assertJson([
            'success' => true,
            'data' => ['nguon' => 'da_luu'],
            'meta' => ['bo_qua_lam_moi' => true],
        ]);

        $this->assertGreaterThan(0, $ra->json('meta.lam_moi_duoc_sau'));
    }
}
```

- [ ] **Step 2: Chạy test để xác nhận nó hỏng**

Chạy: `php vendor/bin/phpunit --filter McctApiTest`
Kỳ vọng: FAIL — mọi test trả 404 vì route chưa tồn tại.

- [ ] **Step 3: Thêm route**

Trong `routes/api.php`, thêm vào **cuối** nhóm `Route::middleware(['throttle:60,1', 'api.auth'])->group(...)`, ngay sau dòng đăng ký `order-check/violations`:

```php
    // Tra cuu tien cung chi tra (MCCT) cho he thong ngoai. Mac dinh doc du lieu da luu
    // (<100ms); lam_moi=1 moi goi cong BHXH va co the mat toi 60 giay.
    Route::get('mcct/tra-cuu', 'Api\McctApiController@traCuu')->name('api.mcct.tra-cuu');
```

- [ ] **Step 4: Viết controller**

Tạo `app/Http/Controllers/Api/McctApiController.php`:

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\McctRequest;
use App\Models\Mcct\McctChiPhi;
use App\Models\Mcct\McctTraCuu;
use App\Services\BHYT\CoSoTraCuu;
use App\Services\Mcct\McctDungLaiKetQua;
use App\Services\Mcct\McctTraCuuChung;
use App\Services\Mcct\QuyetDinhGoiCong;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * API tra cuu tien cung chi tra (MCCT) cho he thong ngoai.
 *
 * VI SAO TACH KHOI McctController: man web va API cho he thong ngoai co ranh gioi xac thuc
 * khac nhau (mot ben la phien dang nhap, mot ben la token Bearer). De chung mot lop la moi
 * goi nham lan ve sau.
 *
 * Lop nay KHONG chua logic nghiep vu - chi doi ket qua cua McctTraCuuChung va
 * McctDungLaiKetQua sang khuon JSON {success, data, meta} da co tien le trong du an.
 */
class McctApiController extends Controller
{
    /** Doi ma loi cua McctTraCuuChung sang ma HTTP + ma loi cua API */
    private static $banDoLoi = [
        'XAC_THUC' => ['GATEWAY_ERROR', 502],
        'MANG' => ['GATEWAY_ERROR', 502],
        'HET_GIO' => ['GATEWAY_TIMEOUT', 504],
        // Co so chua khai tai khoan la CAU HINH THIEU cua qlbv, khong phai cong hong. Tra
        // 502 se day ben goi di hoi nham phia BHXH.
        'CAU_HINH' => ['INTERNAL_ERROR', 500],
        'KHAC' => ['INTERNAL_ERROR', 500],
    ];

    public function traCuu(Request $request)
    {
        $maThe = McctRequest::chuanHoaMaThe($request->get('ma_the'));
        $lamMoi = (string) $request->get('lam_moi') === '1';

        if ($maThe === '') {
            return $this->loiApi('VALIDATION_ERROR', 'Thiếu tham số bắt buộc',
                'Cần truyền ma_the', 422);
        }

        if (!preg_match('/^[A-Za-z0-9]{10}$|^[A-Za-z0-9]{12}$|^[A-Za-z0-9]{15}$/', $maThe)) {
            return $this->loiApi('VALIDATION_ERROR', 'Mã thẻ không hợp lệ',
                'ma_the phải có 10, 12 hoặc 15 ký tự sau khi bỏ khoảng trắng', 422);
        }

        $params = [
            'ma_cskcb' => trim((string) $request->get('ma_cskcb')),
            'ma_the' => $maThe,
            'ho_ten' => mb_strtoupper(trim((string) $request->get('ho_ten'))),
            'ngay_sinh' => trim((string) $request->get('ngay_sinh')),
        ];

        if ($lamMoi) {
            $thieu = $this->thieuThamSoLamMoi($params);

            if ($thieu !== null) {
                return $this->loiApi('VALIDATION_ERROR', 'Thiếu tham số bắt buộc', $thieu, 422);
            }
        }

        // Ban ghi gan nhat BAT KE ma ket qua - mot lan tra ve 204 cung da tieu mot luot goi
        // cong, nen no van phai tinh vao khau do chan. Khac voi truy van trong
        // tuDuLieuDaLuu() ben duoi: cho do chi lay lan THANH CONG de co so lieu that ma hien.
        try {
            $phienCu = McctTraCuu::where('ma_the', $maThe)->orderBy('id', 'desc')->first();
        } catch (\Exception $e) {
            \Log::error('MCCT API khong doc duoc ban ghi cu: ' . $e->getMessage());

            return $this->loiApi('INTERNAL_ERROR', 'Lỗi hệ thống',
                'Vui lòng thử lại sau', 500);
        }

        $quyetDinh = QuyetDinhGoiCong::nen(
            $lamMoi,
            $phienCu === null ? null : (string) $phienCu->tra_luc,
            date('Y-m-d H:i:s'),
            (int) config('mcct.khoang_cho_lam_moi', 900)
        );

        if ($quyetDinh['goi']) {
            return $this->goiCong($params);
        }

        return $this->tuDuLieuDaLuu($maThe, $quyetDinh, $lamMoi);
    }

    /** @return string|null cau bao loi; null neu du tham so */
    private function thieuThamSoLamMoi(array $params)
    {
        foreach (['ho_ten' => 'ho_ten', 'ngay_sinh' => 'ngay_sinh', 'ma_cskcb' => 'ma_cskcb']
            as $khoa => $ten) {
            if ($params[$khoa] === '') {
                return 'lam_moi=1 cần truyền thêm ' . $ten;
            }
        }

        if (!in_array($params['ma_cskcb'],
            CoSoTraCuu::maDangChuoi(CoSoTraCuu::tuCauHinh()), true)) {
            return 'ma_cskcb không thuộc danh sách cơ sở đã khai tài khoản cổng BHXH';
        }

        return null;
    }

    private function goiCong(array $params)
    {
        $ra = McctTraCuuChung::goiVaLuu($params, 'api_his');

        if ($ra['loi'] !== null) {
            $ban = isset(self::$banDoLoi[$ra['ma_loi']])
                ? self::$banDoLoi[$ra['ma_loi']] : ['INTERNAL_ERROR', 500];

            // Thong diep cua McctTraCuuChung noi ro nguyen nhan va khong chua bi mat nao
            // (accessToken/passwordHash di o header, khong o day), nen dua thang ra details.
            return $this->loiApi($ban[0], 'Không tra cứu được', $ra['loi'], $ban[1]);
        }

        $kq = $ra['kq'];
        $muc = $ra['muc'];

        return $this->traData([
            'ma_the' => $params['ma_the'],
            'nguon' => 'cong_bhxh',
            'tra_luc' => date('Y-m-d H:i:s'),
            'ghi_chu' => $kq->ghiChu,
            'thong_tin_the' => $kq->thongTinThe,
            'luy_ke_cung_chi_tra' => $muc['luy_ke_tong'],
            'nguong_ca_nam' => $muc['tong_nguong_ca_nam'],
            'con_thieu' => $muc['con_thieu'],
            'du_nguong_6_thang_luong' => (bool) $muc['du_dieu_kien'],
            'can_kiem_5_nam_lien_tuc' => true,
            'chi_tiet' => $kq->dong,
        ], ['ma_ket_qua_cong' => $kq->maKetQua]);
    }

    private function tuDuLieuDaLuu($maThe, array $quyetDinh, $lamMoi)
    {
        try {
            $phien = McctTraCuu::where('ma_the', $maThe)
                ->where('ma_ket_qua', '200')
                ->orderBy('id', 'desc')->first();

            if ($phien === null) {
                return $this->traData(null, ['trang_thai' => 'chua_tra_lan_nao']);
            }

            $dong = McctChiPhi::where('tra_cuu_id', $phien->id)->orderBy('id')->get()->toArray();
        } catch (\Exception $e) {
            \Log::error('MCCT API khong doc duoc du lieu da luu: ' . $e->getMessage());

            return $this->loiApi('INTERNAL_ERROR', 'Lỗi hệ thống', 'Vui lòng thử lại sau', 500);
        }

        $cache = McctDungLaiKetQua::tuBanGhi($phien->toArray(), $dong,
            (array) config('mcct.luong_co_so', []),
            (int) config('mcct.so_thang_luong_co_so', 6));

        $muc = $cache['muc'];

        $meta = ['ma_ket_qua_cong' => $cache['ma_ket_qua']];

        // Bo qua lam_moi vi con trong khau do: bao ro chu khong im lang. Tra DU LIEU chu
        // khong tra 429 - mot vong lap hong ben goi se khong sinh them vong thu lai.
        if ($lamMoi) {
            $meta['bo_qua_lam_moi'] = true;
            $meta['lam_moi_duoc_sau'] = $quyetDinh['con_lai'];
        }

        return $this->traData([
            'ma_the' => $maThe,
            'nguon' => 'da_luu',
            'tra_luc' => $cache['tra_luc'],
            'ghi_chu' => $cache['ghi_chu'],
            'thong_tin_the' => $cache['thong_tin_the'],
            'luy_ke_cung_chi_tra' => $muc['luy_ke_tong'],
            'nguong_ca_nam' => $muc['tong_nguong_ca_nam'],
            'con_thieu' => $muc['con_thieu'],
            'du_nguong_6_thang_luong' => (bool) $muc['du_dieu_kien'],
            'can_kiem_5_nam_lien_tuc' => true,
            'chi_tiet' => $cache['dong'],
        ], $meta);
    }

    private function traData($data, array $themMeta = [])
    {
        return response()->json([
            'success' => true,
            'data' => $data,
            'meta' => array_merge($this->metaApi(), $themMeta),
        ]);
    }

    /** Khuon loi thong nhat voi ApiAuthMiddleware. Khong lo thong diep ngoai le ra ngoai. */
    private function loiApi($code, $message, $details, $status)
    {
        return response()->json([
            'success' => false,
            'error' => ['code' => $code, 'message' => $message, 'details' => $details],
            'meta' => $this->metaApi(),
        ], $status);
    }

    private function metaApi()
    {
        return [
            'timestamp' => Carbon::now()->format('YmdHis'),
            'request_id' => uniqid('req_'),
        ];
    }
}
```

- [ ] **Step 5: Chạy test để xác nhận nó xanh**

Chạy: `php vendor/bin/phpunit --filter McctApiTest`
Kỳ vọng: PASS — 9 test

Nếu test `chua_tung_tra_thi_tra_data_null` đỏ vì bảng `mcct_tra_cuu` chưa có trên `qlbv_test`,
**dừng và báo lên** — không tự chạy `migrate` lên CSDL nào khác.

- [ ] **Step 6: Chạy toàn bộ bộ test**

Chạy: `php vendor/bin/phpunit`
Kỳ vọng: số test đỏ **không tăng**. Mốc đỏ đã biết trước: đúng **một** test cấu hình CTĐT đỏ
có chủ đích (`CtdtCauHinhTest::gui_len_cong_mac_dinh_tat`) — không sửa nó.

- [ ] **Step 7: Commit**

```bash
git add app/Http/Controllers/Api/McctApiController.php routes/api.php tests/Feature/McctApiTest.php
git commit -m "feat(mcct): API tra cuu MCCT cho he thong ngoai

GET /api/mcct/tra-cuu trong nhom throttle:60,1 + api.auth san co. Mac dinh
tra du lieu da luu trong <100ms; lam_moi=1 moi goi cong BHXH.

lam_moi=1 cho cung mot ma the trong khau do 15 phut se bo qua, tra ban da luu
kem meta.bo_qua_lam_moi va meta.lam_moi_duoc_sau. Tra DU LIEU chu khong tra
429: mot vong lap hong ben goi se khong sinh them vong thu lai.

Ten truong la du_nguong_6_thang_luong chu KHONG phai du_dieu_kien_mien, va
luon kem co can_kiem_5_nam_lien_tuc. Dieu kien mien gom HAI ve ma API chi kiem
duoc mot; mot ten truong nhu du_dieu_kien_mien se duoc ben goi hien thang cho
can bo la 'du dieu kien'.

Co so chua khai tai khoan tra 500 chu khong 502: do la cau hinh thieu cua
qlbv, khong phai cong hong - tra 502 se day ben goi di hoi nham phia BHXH.

Co-Authored-By: Claude Opus 5 <noreply@anthropic.com>"
```

---

## Task 4: Tài liệu bàn giao cho bên gọi

**Files:**
- Create: `docs/api-mcct.md`

**Interfaces:**
- Consumes: route và khuôn phản hồi từ Task 3
- Produces: không có mã

- [ ] **Step 1: Viết tài liệu**

Tạo `docs/api-mcct.md`:

```markdown
# API tra cứu tiền cùng chi trả (MCCT)

`GET /api/mcct/tra-cuu`

## Xác thực

Header `Authorization: Bearer {token}`. Token do quản trị qlbv cấp.

## Tham số

| Tham số | Bắt buộc | Ghi chú |
|---|---|---|
| `ma_the` | luôn | 10, 12 hoặc 15 ký tự sau khi bỏ khoảng trắng |
| `lam_moi` | không | `1` = gọi cổng BHXH. Mặc định `0` = đọc dữ liệu đã lưu |
| `ho_ten` | khi `lam_moi=1` | |
| `ngay_sinh` | khi `lam_moi=1` | `dd/mm/yyyy`, `mm/yyyy` hoặc `yyyy` |
| `ma_cskcb` | khi `lam_moi=1` | Mã cơ sở đã khai tài khoản cổng BHXH trong qlbv |

## Ba điều phải biết trước khi viết mã gọi

1. **`lam_moi=0` trả về dưới 100ms. `lam_moi=1` có thể mất tới 60 giây.** Đặt timeout phía
   bạn **≥ 70 giây** cho `lam_moi=1`. Đặt 30 giây (mặc định của phần lớn thư viện HTTP) là
   tự cắt giữa chừng, và lượt gọi lên cổng BHXH **vẫn bị tiêu**.
2. **`lam_moi=1` cho cùng một mã thẻ trong vòng 15 phút sẽ bị bỏ qua** — bạn nhận dữ liệu đã
   lưu kèm `meta.bo_qua_lam_moi = true` và `meta.lam_moi_duoc_sau` (số giây còn phải chờ).
   Đây không phải lỗi. Cổng BHXH giới hạn số lượt tra cứu của mỗi tài khoản.
3. **`du_nguong_6_thang_luong = true` CHƯA đủ để kết luận người bệnh được miễn cùng chi
   trả.** Điều kiện miễn gồm hai vế: tham gia BHYT đủ 5 năm liên tục **và** lũy kế cùng chi
   trả lớn hơn 6 tháng lương cơ sở. Hàm của cổng BHXH không trả về vế thứ nhất — cờ
   `can_kiem_5_nam_lien_tuc` có mặt ở mọi phản hồi để nhắc điều đó.

Luồng khuyến nghị lúc tiếp đón: gọi `lam_moi=0` trước để hiện ngay; chỉ gọi `lam_moi=1` khi
cán bộ chủ động yêu cầu.

## Phản hồi thành công

```json
{
  "success": true,
  "data": {
    "ma_the": "HT3382797052765",
    "nguon": "da_luu",
    "tra_luc": "2026-09-07 11:45:11",
    "ghi_chu": "Nguồn DL ... tính đến: 14/08/2026 14:41",
    "thong_tin_the": { "ho_ten": "...", "ngay_sinh": "...", "ngay_ket_thuc": "...", "ma_bhxh": "..." },
    "luy_ke_cung_chi_tra": 4762612,
    "nguong_ca_nam": 15066588,
    "con_thieu": 10303976,
    "du_nguong_6_thang_luong": false,
    "can_kiem_5_nam_lien_tuc": true,
    "chi_tiet": [ { "ma_cskcb": "...", "ngay_vao": "...", "ngay_ra": "...", "t_bn_cct_mcct": 0, "t_bn_cct_luy_ke": 0 } ]
  },
  "meta": { "timestamp": "20260907114511", "request_id": "req_..." }
}
```

`nguon` nhận `da_luu` hoặc `cong_bhxh`.

Hai mốc thời gian **khác nhau**, đừng gộp làm một:

- `tra_luc` — thời điểm qlbv hỏi cổng BHXH.
- Cụm "tính đến …" trong `ghi_chu` — mốc dữ liệu của chính cổng. Những đợt khám chữa bệnh mà
  cơ sở chưa gửi hồ sơ đề nghị thanh toán thì chưa được tính vào lũy kế.

## Chưa từng tra thẻ này

```json
{ "success": true, "data": null, "meta": { "trang_thai": "chua_tra_lan_nao", "...": "..." } }
```

Không có dữ liệu không phải lỗi. Gọi lại với `lam_moi=1` để tra thật.

## Lỗi

```json
{ "success": false, "error": { "code": "...", "message": "...", "details": "..." }, "meta": {} }
```

| HTTP | `code` | Nghĩa |
|---|---|---|
| 401 | | Thiếu hoặc sai token |
| 422 | `VALIDATION_ERROR` | Thiếu tham số hoặc mã thẻ sai độ dài |
| 429 | | Vượt hạn mức 60 request/phút |
| 502 | `GATEWAY_ERROR` | Cổng BHXH báo lỗi hoặc không kết nối được |
| 504 | `GATEWAY_TIMEOUT` | Cổng BHXH không trả lời kịp |
| 500 | `INTERNAL_ERROR` | Lỗi phía qlbv |
```

- [ ] **Step 2: Commit**

```bash
git add docs/api-mcct.md
git commit -m "docs(mcct): tai lieu API tra cuu MCCT cho ben goi

Ba dieu ben goi phai biet truoc khi viet ma: timeout >= 70 giay cho lam_moi=1,
khau do 15 phut khong phai loi, va du_nguong_6_thang_luong CHUA du de ket luan
duoc mien cung chi tra.

Co-Authored-By: Claude Opus 5 <noreply@anthropic.com>"
```
