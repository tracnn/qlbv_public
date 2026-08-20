# Chứng từ điện tử PL02 — Ba việc trước Giai đoạn 5 — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Người vận hành nhìn màn danh sách là biết hồ sơ đang kẹt ở đâu và vì sao; một lần bấm nhầm không sinh hai lần gửi thật; và bộ kiểm đòi đúng những trường công văn 2076 yêu cầu.

**Architecture:** Ba việc độc lập nhau, làm được theo bất kỳ thứ tự nào. Việc 1 mở rộng `CtdtTrangThaiGui` — lớp thuần — cùng bộ lọc SQL soi gương nó. Việc 2 dùng khoá cache trong controller, nhả ở cuối chuỗi job. Việc 3 chỉ sửa hai bảng dữ liệu, nhưng làm vỡ nhiều bộ dữ liệu mẫu.

**Tech Stack:** Laravel 5.5, PHP 7.4, PHPUnit 6, MySQL / SQLite in-memory (test), cache driver `file`.

## Global Constraints

- **Không dùng `: void` trên `setUp()`** — PHPUnit 6 khai `setUp()` không có kiểu trả về.
- **Không dùng cú pháp PHP 8** — không `match`, không `?->`, không constructor promotion, không named arguments, không `Foo::bar()::baz()`.
- **`Cache::add($khoa, $giaTri, $phut)` của Laravel 5.5 nhận PHÚT, không phải giây.**
- **Không dùng `RefreshDatabase`** — `.env` trỏ CSDL phát triển thật. Dùng `Tests\Support\DungBangCtdtSqlite`.
- **Test phải TỰ ĐẶT cấu hình** bằng `config([...])`, không phụ thuộc `config/organization.php` của máy.
- **Không `git add -f`** tệp trong `.gitignore`: `config/organization.php`, `config/filesystems.php`, `config/database.php`, `config/auth.php`. Thư mục `.superpowers/` cũng bị gitignore.
- **Không sửa `truong()`, `$fillable`, hay migration nào.**
- **Trong blade dùng `{{ }}`, không `{!! !!}`.** Chỉ thị Blade nằm trong chú thích JavaScript vẫn được Blade dịch và sinh PHP hỏng — dùng `@@if` nếu cần nhắc tới. Đừng viết chuỗi `?>` trong chú thích PHP.
- **Baseline:** `tests/Unit/Ctdt` hiện `448 test`, **đỏ đúng một** — `CtdtCauHinhTest::gui_len_cong_mac_dinh_tat`. Test đó **đỏ có chủ đích** trên máy đã bật gửi thật; **đừng sửa, đừng xoá**. Hai test đỏ trở lên mới là dấu hiệu vỡ thật. Suite `Unit` cho `Errors: 4, Failures: 7`; `Feature` cho `Errors: 8, Failures: 4`.

---

## Bối cảnh: hai nguồn sự thật phải khớp nhau

`CtdtTrangThaiGui::cua()` suy trạng thái bằng **PHP**, còn `CtdtDanhSach::locTrangThai()` suy cùng trạng thái đó bằng **SQL**. Không có gì trong ngôn ngữ buộc chúng khớp — chỉ có test tính chất
`CtdtDanhSachTest::bo_loc_trang_thai_khop_voi_CtdtTrangThaiGui_cho_moi_ho_so`.

**Mỗi trạng thái mới phải sửa hai chỗ VÀ thêm hồ sơ mẫu vào bộ dữ liệu của test đó.** Test ấy làm hai việc: đối chiếu từng bộ lọc với `cua()`, và khẳng định tổng các bộ lọc rời nhau bằng đúng tổng số hồ sơ (không trùng, không sót).

Cột `trang_thai_gui` trên màn danh sách và ô lọc trạng thái **đều suy từ `CtdtTrangThaiGui`** (`BHYTCtdtController:105-110` và `:51`), nên thêm hằng và nhánh là đủ — không phải sửa controller hay blade.

---

## File Structure

**Sửa:**

| Tệp | Sửa gì |
|---|---|
| `app/Services/Ctdt/CtdtTrangThaiGui.php` | Hai hằng `KY_HONG`, `GUI_HONG` + nhãn + hai nhánh trong `cua()` |
| `app/Services/Ctdt/CtdtDanhSach.php` | Hai nhánh tương ứng trong `locTrangThai()` |
| `app/Http/Controllers/BHYT/BHYTCtdtController.php` | Khoá chống bấm trùng trong `kyVaGui()` |
| `app/Jobs/SubmitCtdtJob.php` | Nhả khoá ở cuối `handle()` và trong `failed()` |
| `app/Jobs/SignCtdtJob.php` | Nhả khoá trong `failed()` |
| `app/Services/Ctdt/Kiem/CtdtTruongBatBuoc.php` | Bổ sung trường theo công văn 2076 |
| `tests/Unit/Ctdt/*Test.php` | Bộ dữ liệu mẫu và test mới |
| `docs/chung-tu-dien-tu-pl02.md` | Ghi lại ba thay đổi |

**Không tạo tệp mới nào.**

---

## Task 1: Màn danh sách phân biệt "ký hỏng" và "gửi hỏng"

**Files:**
- Modify: `app/Services/Ctdt/CtdtTrangThaiGui.php`
- Modify: `app/Services/Ctdt/CtdtDanhSach.php` (hàm `locTrangThai()`)
- Test: `tests/Unit/Ctdt/CtdtTrangThaiGuiTest.php`, `tests/Unit/Ctdt/CtdtDanhSachTest.php`

**Interfaces:**
- Consumes: cột `signed_error`, `submit_error`, `ma_ket_qua`, `is_signed`, `so_loi`, `checked_at` của `ctdt_ho_so` (đã có từ Giai đoạn 1)
- Produces:
  - Hằng `CtdtTrangThaiGui::KY_HONG = 'ky_hong'` và `::GUI_HONG = 'gui_hong'`
  - Hai mục tương ứng trong `CtdtTrangThaiGui::NHAN`
  - `cua()` trả hai giá trị mới; `CtdtDanhSach::truyVan(['trang_thai_gui' => ...])` lọc được chúng

**Vì sao cần:** `SignCtdtJob` ghi `signed_error` ở ba nơi và **không nơi nào đọc** trên màn danh sách. Ca thường gặp nhất khi triển khai — bật `sign_enabled` nhưng quên `usb_token_sign.enabled` — cho ra hồ sơ hiện **"Chưa ký số"**, và người vận hành đi tìm nút ký (đã bấm rồi) thay vì đi cắm lại USB token. Tương tự, hồ sơ gửi hỏng trước khi tới cổng hiện **"Chờ gửi"**, không phân biệt được với hồ sơ còn nằm trong hàng đợi.

**Thứ tự ưu tiên mới của `cua()`** — chèn hai nhánh, giữ nguyên phần còn lại:

```
1. checked_at rỗng                       -> CHUA_KIEM
2. so_loi > 0                            -> CON_LOI
3. !is_signed VÀ signed_error có nội dung -> KY_HONG      ← mới
4. !is_signed                            -> CHUA_KY
5. ma_ket_qua có                         -> DA_GUI | CONG_TU_CHOI
6. submit_error có nội dung              -> GUI_HONG      ← mới
7. cờ gửi tắt                            -> GUI_TAT
8. còn lại                               -> CHUA_GUI
```

**Hai vị trí này quan trọng:**

- `KY_HONG` **trước** `CHUA_KY`: cả hai đều `is_signed = false`; cái cụ thể hơn phải thắng, không thì lý do thật bị nuốt.
- `GUI_HONG` **sau** nhánh `ma_ket_qua` nhưng **trước** `GUI_TAT`: nếu cổng đã trả lời thì kết quả đó là sự thật, thắng mọi thứ; nhưng cờ gửi bị tắt **sau** một lần gửi hỏng thì hiện "đang tắt" là giấu mất lỗi.

- [ ] **Step 1: Viết test đỏ cho `CtdtTrangThaiGui`**

Thêm vào `tests/Unit/Ctdt/CtdtTrangThaiGuiTest.php`. Đọc `setUp()` và các hàm trợ giúp sẵn có của tệp đó rồi dùng lại, đừng dựng khuôn thứ hai:

```php
    /** @test */
    public function ky_hong_KHAC_chua_ky()
    {
        // SignCtdtJob ghi signed_error o ba noi va truoc day KHONG noi nao doc tren man danh
        // sach. Ca thuong gap nhat khi trien khai: bat sign_enabled nhung quen
        // usb_token_sign.enabled -> ho so hien "Chua ky so", va nguoi van hanh di tim nut ky
        // (da bam roi) thay vi di cam lai USB token.
        $hoSo = $this->hoSo([
            'checked_at' => '2026-08-20 08:00:00', 'so_loi' => 0,
            'is_signed' => false, 'signed_error' => 'Khong ket noi duoc dich vu ky cuc bo',
        ]);

        $this->assertSame(CtdtTrangThaiGui::KY_HONG, CtdtTrangThaiGui::cua($hoSo));
    }

    /** @test */
    public function chua_ky_VA_chua_thu_ky_van_la_CHUA_KY()
    {
        // Khong co signed_error nghia la chua ai bam ky. Phai phan biet duoc voi da bam va
        // hong - nguoc lai thi trang thai moi nuot mat trang thai cu.
        $hoSo = $this->hoSo([
            'checked_at' => '2026-08-20 08:00:00', 'so_loi' => 0,
            'is_signed' => false, 'signed_error' => null,
        ]);

        $this->assertSame(CtdtTrangThaiGui::CHUA_KY, CtdtTrangThaiGui::cua($hoSo));
    }

    /** @test */
    public function ky_lai_thanh_cong_thi_KHONG_con_la_ky_hong()
    {
        // SignCtdtJob xoa signed_error khi ky thanh cong. Neu nhanh KY_HONG chi hoi
        // signed_error ma khong hoi is_signed, mot ho so da ky lai duoc van hien "Ky hong".
        $hoSo = $this->hoSo([
            'checked_at' => '2026-08-20 08:00:00', 'so_loi' => 0,
            'is_signed' => true, 'signed_error' => null,
        ]);

        $this->assertNotSame(CtdtTrangThaiGui::KY_HONG, CtdtTrangThaiGui::cua($hoSo));
    }

    /** @test */
    public function gui_hong_KHAC_cho_gui()
    {
        // submit_error co nhung ma_ket_qua rong = da thu gui va hong TRUOC khi toi cong
        // (mat mang, khong tim thay tep da ky, job het luot thu). Khac han voi ho so con
        // nam trong hang doi.
        $hoSo = $this->hoSo([
            'checked_at' => '2026-08-20 08:00:00', 'so_loi' => 0, 'is_signed' => true,
            'ma_ket_qua' => null, 'submit_error' => 'Job gui that bai: Connection refused',
        ]);

        $this->assertSame(CtdtTrangThaiGui::GUI_HONG, CtdtTrangThaiGui::cua($hoSo));
    }

    /** @test */
    public function cong_da_tra_loi_thi_ket_qua_do_THANG_submit_error()
    {
        // Ho so tung gui hong roi gui lai thanh cong: submit_error cu con sot lai la chuyen
        // co that. Ket qua cua cong moi la su that cuoi cung.
        $hoSo = $this->hoSo([
            'checked_at' => '2026-08-20 08:00:00', 'so_loi' => 0, 'is_signed' => true,
            'ma_ket_qua' => '200', 'submit_error' => 'Loi cu con sot',
        ]);

        $this->assertSame(CtdtTrangThaiGui::DA_GUI, CtdtTrangThaiGui::cua($hoSo));
    }

    /** @test */
    public function gui_hong_THANG_co_gui_tat()
    {
        // Cau hinh bi tat SAU mot lan gui hong: hien "dang tat" la giau mat loi.
        config(['organization.chung_tu_dien_tu.submit_enabled' => false]);

        $hoSo = $this->hoSo([
            'checked_at' => '2026-08-20 08:00:00', 'so_loi' => 0, 'is_signed' => true,
            'ma_ket_qua' => null, 'submit_error' => 'Khong tim thay tep da ky',
        ]);

        $this->assertSame(CtdtTrangThaiGui::GUI_HONG, CtdtTrangThaiGui::cua($hoSo));
    }

    /** @test */
    public function moi_trang_thai_moi_deu_co_nhan_tieng_viet()
    {
        foreach ([CtdtTrangThaiGui::KY_HONG, CtdtTrangThaiGui::GUI_HONG] as $ma) {
            $this->assertArrayHasKey($ma, CtdtTrangThaiGui::NHAN, $ma);
            $this->assertNotEmpty(CtdtTrangThaiGui::NHAN[$ma], $ma);
        }
    }
```

**Nếu tệp test đó chưa có hàm trợ giúp `hoSo()`**, đọc cách nó đang dựng bản ghi rồi dùng đúng cách ấy — đừng thêm một khuôn mới.

- [ ] **Step 2: Chạy test để xác nhận đỏ**

```bash
php vendor/bin/phpunit tests/Unit/Ctdt/CtdtTrangThaiGuiTest.php
```

Kỳ vọng: đỏ — `Undefined constant ... KY_HONG`.

- [ ] **Step 3: Thêm hai trạng thái vào `CtdtTrangThaiGui`**

Thêm hai hằng cạnh các hằng sẵn có:

```php
    /** Da bam ky va ky HONG - khac han voi chua ai bam */
    const KY_HONG = 'ky_hong';

    /** Da thu gui va hong TRUOC khi toi cong - khac han voi con nam trong hang doi */
    const GUI_HONG = 'gui_hong';
```

Thêm hai nhãn vào `NHAN`, đặt cạnh nhãn cùng nhóm:

```php
        self::KY_HONG      => 'Ký số thất bại',
        self::GUI_HONG     => 'Gửi thất bại',
```

Trong `cua()`, chèn nhánh `KY_HONG` **ngay trước** nhánh `CHUA_KY`:

```php
        // TRUOC CHUA_KY: ca hai deu is_signed = false, cai CU THE HON phai thang. Khong tach
        // ra thi ly do that (USB token bi rut, HSM khong phan hoi, chuc nang ky chua bat) chi
        // nam trong laravel.log, con man hinh bao "Chua ky so" - nguoi van hanh di tim nut ky
        // da bam roi.
        if (!(bool) $hoSo->is_signed && !empty($hoSo->signed_error)) {
            return self::KY_HONG;
        }
```

và chèn nhánh `GUI_HONG` **sau** khối `ma_ket_qua`, **trước** khối `submit_enabled`:

```php
        // SAU ma_ket_qua: cong da tra loi thi ket qua do la su that cuoi cung, ke ca khi
        // submit_error cu con sot lai tu mot lan gui hong truoc do.
        //
        // TRUOC GUI_TAT: co gui bi tat SAU mot lan gui hong thi hien "dang tat" la giau mat
        // loi - nguoi van hanh se di bat cau hinh thay vi doc ly do that.
        if (!empty($hoSo->submit_error)) {
            return self::GUI_HONG;
        }
```

- [ ] **Step 4: Chạy test để xác nhận xanh**

```bash
php vendor/bin/phpunit tests/Unit/Ctdt/CtdtTrangThaiGuiTest.php
```

Kỳ vọng: xanh.

- [ ] **Step 5: Chạy `CtdtDanhSachTest` để thấy test tính chất đỏ**

```bash
php vendor/bin/phpunit tests/Unit/Ctdt/CtdtDanhSachTest.php
```

Kỳ vọng: **đỏ** ở `bo_loc_trang_thai_khop_voi_CtdtTrangThaiGui_cho_moi_ho_so`. Đây là **đúng** — test tính chất vừa phát hiện `cua()` và `locTrangThai()` đã lệch nhau. Nếu nó **không** đỏ, dừng lại và báo: nghĩa là bộ dữ liệu của test chưa có hồ sơ nào rơi vào hai trạng thái mới, và test đang không canh gì.

- [ ] **Step 6: Bổ sung hồ sơ mẫu vào test tính chất**

Trong `tests/Unit/Ctdt/CtdtDanhSachTest.php`, thêm vào bộ dữ liệu của
`bo_loc_trang_thai_khop_voi_CtdtTrangThaiGui_cho_moi_ho_so`, đặt cạnh các hồ sơ sẵn có:

```php
        // N, O: hai trang thai moi. N nguy trang thanh CHUA_KY (cung is_signed = false),
        // O nguy trang thanh CHUA_GUI (cung chua co ma_ket_qua) - neu bo loc SQL khong loai
        // tru chung, hai ho so nay se hien o bo loc sai va ly do that bien mat khoi man hinh.
        $this->taoHoSo(['ma_ho_so' => 'N_KY_HONG', 'so_loi' => 0, 'is_signed' => false,
            'signed_error' => 'USB token bi rut']);
        $this->taoHoSo(['ma_ho_so' => 'O_GUI_HONG', 'so_loi' => 0, 'is_signed' => true,
            'submit_error' => 'Connection refused']);

        // P: da gui hong roi gui lai duoc. submit_error cu con sot lai, nhung ket qua cua
        // cong moi la su that - phai la DA_GUI, khong phai GUI_HONG.
        $this->taoHoSo(['ma_ho_so' => 'P_GUI_LAI_THANH_CONG', 'so_loi' => 0, 'is_signed' => true,
            'ma_ket_qua' => '200', 'submit_error' => 'Loi cu con sot']);
```

Và thêm hai trạng thái mới vào **cả hai** danh sách trong test đó — `$cacTrangThaiCanKiem` và `$cacBoLocRoiNhau`:

```php
            CtdtTrangThaiGui::KY_HONG,
            CtdtTrangThaiGui::GUI_HONG,
```

- [ ] **Step 7: Thêm hai nhánh vào `locTrangThai()`**

Trong `app/Services/Ctdt/CtdtDanhSach.php`, chèn nhánh `KY_HONG` **ngay trước** nhánh `CHUA_KY`:

```php
        // Soi guong dung thu tu cua CtdtTrangThaiGui::cua(): KY_HONG truoc CHUA_KY vi ca hai
        // deu is_signed = false.
        if ($trangThai === CtdtTrangThaiGui::KY_HONG) {
            return $q->where('is_signed', false)
                ->whereNotNull('signed_error')
                ->where('signed_error', '<>', '');
        }

        if ($trangThai === CtdtTrangThaiGui::CHUA_KY) {
            // CHUA_KY phai LOAI TRU ky hong, khong thi mot ho so hien o ca hai bo loc va
            // tong cac bo loc khong con bang tong so ho so.
            return $q->where('is_signed', false)
                ->where(function ($q2) {
                    $q2->whereNull('signed_error')->orWhere('signed_error', '');
                });
        }
```

(thay thế hẳn nhánh `CHUA_KY` cũ)

Rồi chèn nhánh `GUI_HONG` **sau** nhánh `CONG_TU_CHOI`, **trước** khối cuối:

```php
        if ($trangThai === CtdtTrangThaiGui::GUI_HONG) {
            // Da ky, cong CHUA tra loi, nhung co submit_error: da thu gui va hong truoc khi
            // toi cong.
            return $q->where(function ($q2) {
                    $q2->whereNull('ma_ket_qua')->orWhere('ma_ket_qua', '')->orWhere('ma_ket_qua', '0');
                })
                ->whereNotNull('submit_error')
                ->where('submit_error', '<>', '');
        }
```

Và trong khối cuối (GUI_TAT / CHUA_GUI), thêm điều kiện loại trừ `submit_error`:

```php
        // GUI_TAT va CHUA_GUI cung la "da ky, chua co ket qua tu cong VA chua tung gui hong".
        return $q->where(function ($q2) {
                $q2->whereNull('ma_ket_qua')->orWhere('ma_ket_qua', '')->orWhere('ma_ket_qua', '0');
            })
            ->where(function ($q2) {
                $q2->whereNull('submit_error')->orWhere('submit_error', '');
            });
```

- [ ] **Step 8: Chạy test để xác nhận xanh**

```bash
php vendor/bin/phpunit tests/Unit/Ctdt/CtdtDanhSachTest.php
```

Kỳ vọng: xanh. Nếu test tính chất còn đỏ, thông điệp của nó nêu đích danh trạng thái nào lệch và tập `ma_ho_so` mong đợi so với thực tế — sửa nhánh SQL cho khớp `cua()`, **đừng sửa `cua()` cho khớp SQL**.

- [ ] **Step 9: Chứng minh hai nhánh mới có răng**

Commit trước, rồi lần lượt:

- Bỏ nhánh `KY_HONG` khỏi `cua()` → `CtdtTrangThaiGuiTest` phải ĐỎ.
- Đổi thứ tự: đặt `KY_HONG` **sau** `CHUA_KY` → phải ĐỎ.
- Bỏ nhánh `GUI_HONG` khỏi `locTrangThai()` → test tính chất phải ĐỎ.

Mỗi lần hoàn nguyên bằng `git checkout -- <đúng đường dẫn tệp đó>`. **Chỉ đúng tệp đó — KHÔNG `git checkout -- .`, KHÔNG `git stash`.**

- [ ] **Step 10: Chạy cả thư mục**

```bash
php vendor/bin/phpunit tests/Unit/Ctdt
```

Kỳ vọng: đỏ **đúng một** — `CtdtCauHinhTest::gui_len_cong_mac_dinh_tat`.

- [ ] **Step 11: Commit**

```bash
git add app/Services/Ctdt/CtdtTrangThaiGui.php app/Services/Ctdt/CtdtDanhSach.php tests/Unit/Ctdt
git commit -m "feat(ctdt): man danh sach phan biet ky hong va gui hong"
```

---

## Task 2: Chống bấm hai lần nút "Ký và gửi"

**Files:**
- Modify: `app/Http/Controllers/BHYT/BHYTCtdtController.php` (hàm `kyVaGui()`)
- Modify: `app/Jobs/SubmitCtdtJob.php` (nhả khoá ở cuối `handle()` và trong `failed()`)
- Modify: `app/Jobs/SignCtdtJob.php` (nhả khoá trong `failed()`)
- Test: `tests/Unit/Ctdt/CtdtKyVaGuiTest.php`

**Interfaces:**
- Consumes: `Illuminate\Support\Facades\Cache`
- Produces: hằng công khai `BHYTCtdtController::KHOA_XU_LY = 'ctdt:dang-xu-ly:'` — tiền tố khoá cache, để hai job nhả đúng khoá đó

**Bằng chứng đây là chuyện thật:** trên CSDL thật đã có một hồ sơ bị bấm **ba lần**, sinh ba chuỗi job và để lại ba job gửi nằm chờ trong hàng đợi. Lần đó vô hại vì hồ sơ chưa ký; với hồ sơ ký được thì thành **ba lần POST thật lên cổng BHXH**, mà PL02 không có mã giao dịch phía client nên cổng không khử trùng được.

**Vì sao khoá cache chứ không thêm cột:** thêm cột là một migration trên CSDL đang chạy cho một trạng thái **tạm thời**; cột đó còn phải có đường dọn khi tiến trình chết giữa chừng. Khoá cache tự hết hạn. Cache driver của dự án là `file` (một máy chủ) nên đủ dùng.

**`Cache::add()` của Laravel 5.5 nhận PHÚT.** `Cache::add($khoa, true, 10)` là mười **phút**, không phải mười giây. Nó trả `true` nếu đặt được, `false` nếu khoá đã tồn tại — đó chính là phép thử "đã có người bấm chưa".

- [ ] **Step 1: Viết test đỏ**

Thêm vào `tests/Unit/Ctdt/CtdtKyVaGuiTest.php`. Thêm `use Illuminate\Support\Facades\Cache;` ở đầu tệp, và `Cache::flush();` vào cuối `setUp()` — nếu không, khoá của test trước sẽ chặn test sau:

```php
    /** @test */
    public function bam_lan_hai_khi_lan_mot_dang_chay_thi_TU_CHOI()
    {
        // Da xay ra that: mot ho so bi bam ba lan, sinh ba chuoi job. Voi ho so ky duoc thi
        // thanh BA lan POST that len cong - va PL02 khong co ma giao dich phia client nen
        // cong khong khu trung duoc.
        $this->hoSo();

        $lan1 = $this->layJson($this->controller->kyVaGui('YT001'));
        $lan2 = $this->layJson($this->controller->kyVaGui('YT001'));

        $this->assertTrue($lan1['thanh_cong']);
        $this->assertFalse($lan2['thanh_cong'], 'Lan bam thu hai phai bi tu choi');
        $this->assertContains('đang xử lý', $lan2['thong_diep']);

        Queue::assertPushed(SignCtdtJob::class, 1);
    }

    /** @test */
    public function ho_so_KHAC_van_bam_duoc_binh_thuong()
    {
        // Khoa phai theo TUNG ho so. Khoa chung se bien mot lan bam thanh mot hang doi mot
        // nguoi - ca phong khong ai gui duoc trong luc mot ho so dang chay.
        $this->hoSo();
        $this->hoSo(['ma_ho_so' => 'YT002']);

        $this->controller->kyVaGui('YT001');
        $kq = $this->layJson($this->controller->kyVaGui('YT002'));

        $this->assertTrue($kq['thanh_cong']);
        Queue::assertPushed(SignCtdtJob::class, 2);
    }

    /** @test */
    public function ho_so_bi_TU_CHOI_thi_KHONG_giu_khoa()
    {
        // Bi tu choi nghia la khong co chuoi job nao chay, nen khong co gi de nha khoa. Giu
        // khoa o day se khoa nguoi dung ra ngoai muoi phut vi mot lan bam khong lam gi ca.
        config(['organization.chung_tu_dien_tu.submit_enabled' => false]);
        $this->hoSo();

        $this->controller->kyVaGui('YT001');

        config(['organization.chung_tu_dien_tu.submit_enabled' => true]);
        $kq = $this->layJson($this->controller->kyVaGui('YT001'));

        $this->assertTrue($kq['thanh_cong'], 'Lan bam bi tu choi khong duoc giu khoa');
    }

    /** @test */
    public function job_gui_nha_khoa_khi_xong()
    {
        // Khong nha thi nguoi dung phai cho het han khoa moi gui lai duoc - ke ca khi lan
        // gui truoc da xong tu lau.
        $this->hoSo();
        $this->controller->kyVaGui('YT001');

        // Dung Cache::has() de tham do, KHONG dung Cache::add(): add() se TU DAT khoa khi
        // no chua ton tai, tuc phep do lam thay doi thu no dang do.
        $this->assertTrue(Cache::has(BHYTCtdtController::KHOA_XU_LY . 'YT001'),
            'Khoa phai dang giu sau khi bam');

        $job = new \App\Jobs\SubmitCtdtJob('YT001', 'tracnn');
        $job->submitServiceGia = new \Tests\Support\FakeCtdtSubmitService();
        $job->handle();

        $this->assertFalse(Cache::has(BHYTCtdtController::KHOA_XU_LY . 'YT001'),
            'Job gui xong phai nha khoa');
    }
```

**Lưu ý:** hồ sơ trong `CtdtKyVaGuiTest::hoSo()` mặc định `is_signed = false` và chưa có `duong_dan_da_ky`, nên `SubmitCtdtJob::handle()` sẽ đi vào nhánh "chưa ký" hoặc "không tìm thấy tệp đã ký" — **đó vẫn là một lần chạy xong và vẫn phải nhả khoá**. Nếu hàm trợ giúp `hoSo()` chưa nhận `$ghiDe` cho `ma_ho_so`, sửa nó nhận, đừng chép thành hàm thứ hai.

- [ ] **Step 2: Chạy test để xác nhận đỏ**

```bash
php vendor/bin/phpunit tests/Unit/Ctdt/CtdtKyVaGuiTest.php
```

Kỳ vọng: đỏ — `Undefined constant ... KHOA_XU_LY`.

- [ ] **Step 3: Thêm khoá vào controller**

Trong `app/Http/Controllers/BHYT/BHYTCtdtController.php`, thêm `use Illuminate\Support\Facades\Cache;` ở đầu tệp và hằng cạnh `DATATABLE_COLUMNS`:

```php
    /**
     * Tien to khoa cache chong bam trung nut "Ky va gui".
     *
     * VI SAO CACHE chu khong phai mot cot moi: day la trang thai TAM THOI. Mot cot phai co
     * duong don khi tien trinh chet giua chung; khoa cache tu het han. Cache driver cua du
     * an la 'file' (mot may chu) nen du dung.
     *
     * Hai job nha khoa nay khi chay xong - xem SubmitCtdtJob va SignCtdtJob.
     */
    const KHOA_XU_LY = 'ctdt:dang-xu-ly:';

    /** Muoi PHUT - Cache::add() cua Laravel 5.5 nhan phut, khong phai giay. Chi la luoi
     *  chan cuoi: duong nha khoa binh thuong la o cuoi chuoi job. */
    const KHOA_XU_LY_PHUT = 10;
```

Trong `kyVaGui()`, đặt khoá **ngay trước** lời gọi dispatch — **sau** mọi nhánh từ chối, để một lần bấm bị từ chối không giữ khoá:

```php
        // Dat khoa NGAY TRUOC dispatch, sau moi nhanh tu choi: mot lan bam bi tu choi khong
        // lam gi ca, giu khoa se khoa nguoi dung ra ngoai muoi phut ma khong duoc gi.
        //
        // Cache::add() tra false khi khoa da ton tai - do chinh la phep thu "da co nguoi bam
        // chua". Khoa theo TUNG ma ho so, khong phai mot khoa chung.
        if (!Cache::add(self::KHOA_XU_LY . $ma_ho_so, true, self::KHOA_XU_LY_PHUT)) {
            return response()->json([
                'thanh_cong' => false,
                'thong_diep' => 'Hồ sơ này đang xử lý. Chờ ít phút rồi tải lại trang để xem kết quả.',
            ]);
        }
```

- [ ] **Step 4: Nhả khoá ở cuối chuỗi job**

Trong `app/Jobs/SubmitCtdtJob.php`, thêm `use Illuminate\Support\Facades\Cache;` và `use App\Http\Controllers\BHYT\BHYTCtdtController;` ở đầu tệp, rồi thêm một hàm riêng:

```php
    /**
     * Nha khoa chong bam trung. Goi o MOI duong ra cua job, ke ca duong that bai.
     *
     * Khong nha thi nguoi dung phai cho het han khoa moi gui lai duoc - ke ca khi lan gui
     * truoc da xong tu lau.
     */
    private function nhaKhoa()
    {
        Cache::forget(BHYTCtdtController::KHOA_XU_LY . $this->maHoSo);
    }
```

Gọi `$this->nhaKhoa();` ở **mọi** đường ra của `handle()` — nhánh không tìm thấy hồ sơ, nhánh `KHONG_GUI`, nhánh `ghiLoi()`, nhánh không tìm thấy tệp đã ký, và sau `ghiKetQua()` — cũng như trong `failed()`.

**Không** gọi trong nhánh ném của `$submitService->gui()`: ở đó job cố ý để ngoại lệ bay ra cho hàng đợi thử lại, và lần thử sau vẫn thuộc cùng một lượt xử lý. `failed()` sẽ nhả khoá khi hết lượt.

Trong `app/Jobs/SignCtdtJob.php`, thêm cùng hai `use` và cùng hàm `nhaKhoa()`, nhưng **chỉ gọi trong `failed()`** — đường bình thường thì `SubmitCtdtJob` nối sau sẽ nhả, còn `failed()` của job ký nghĩa là chuỗi đứt ở đó và không ai nhả nữa.

- [ ] **Step 5: Chạy test để xác nhận xanh**

```bash
php vendor/bin/phpunit tests/Unit/Ctdt/CtdtKyVaGuiTest.php
```

Kỳ vọng: xanh.

- [ ] **Step 6: Chứng minh khoá có răng**

Commit trước, rồi:

- Bỏ nhánh `Cache::add(...)` trong `kyVaGui()` → test bấm hai lần phải ĐỎ.
- Chuyển lời gọi `Cache::add()` lên **trước** các nhánh từ chối → test "bị từ chối không giữ khoá" phải ĐỎ.
- Bỏ `nhaKhoa()` ở cuối `SubmitCtdtJob::handle()` → test nhả khoá phải ĐỎ.
- Đổi khoá thành một chuỗi cố định (không kèm `$ma_ho_so`) → test "hồ sơ khác vẫn bấm được" phải ĐỎ.

Mỗi lần hoàn nguyên bằng `git checkout -- <đúng đường dẫn tệp đó>`.

- [ ] **Step 7: Chạy cả thư mục**

```bash
php vendor/bin/phpunit tests/Unit/Ctdt
```

Kỳ vọng: đỏ **đúng một** — `CtdtCauHinhTest::gui_len_cong_mac_dinh_tat`. Nếu có test khác đỏ vì khoá cache còn sót giữa các test, thêm `Cache::flush()` vào `setUp()` của tệp test đó chứ đừng bỏ khoá.

- [ ] **Step 8: Commit**

```bash
git add app/Http/Controllers/BHYT/BHYTCtdtController.php app/Jobs/SubmitCtdtJob.php app/Jobs/SignCtdtJob.php tests/Unit/Ctdt/CtdtKyVaGuiTest.php
git commit -m "feat(ctdt): chong bam trung nut Ky va gui bang khoa cache theo ho so"
```

---

## Task 3: Bổ sung trường bắt buộc theo công văn 2076

**Files:**
- Modify: `app/Services/Ctdt/Kiem/CtdtTruongBatBuoc.php`
- Test: `tests/Unit/Ctdt/CtdtTruongBatBuocTest.php` và các tệp test có bộ dữ liệu mẫu

**Interfaces:**
- Consumes: `CtdtTruongBatBuoc::BAT_BUOC`, `::KHUYEN_NGHI` (đã có)
- Produces: không có giao diện mới — chỉ đổi nội dung hai bảng

**Số đo trên dữ liệu thật (2026-08-20, 1074 hồ sơ / 3047 chứng từ)** — đây là căn cứ chia hai mức:

| Loại | Trường | Rỗng | Quyết định |
|---|---|---|---|
| CT03 | `MA_KHOA`, `GIOI_TINH`, `DIA_CHI` | 0/1050 | **Chặn** |
| CT04 | `GIOI_TINH`, `DIA_CHI`, `CHAN_DOAN_VAO`, `CHAN_DOAN_RA`, `QT_BENHLY`, `TOMTAT_KQ`, `TT_RAVIEN`, `NGAY_CT` | 0/1050 | **Chặn** |
| CT04 | `MA_DANTOC` | 6/1050 | Cảnh báo |
| CT04 | `PP_DIEUTRI` | 79/1050 | Cảnh báo |
| CT06 | `SO_KCB`, `TEN_DVI`, `CHAN_DOAN`, `TEN_BS`, `MA_BS`, `NGAY_CT` | chưa có dữ liệu | Cảnh báo |
| CT07 | `SO_KCB`, `GIOI_TINH`, `DON_VI`, `CHANDOAN_DIEUTRI`, `MA_CCHN`, `TEN_NGUOI_HANH_NGHE`, `TEKT` | 0/24 | **Chặn** |

**Nguyên tắc:** trường nào dữ liệu thật đã đủ thì chặn được ngay mà không khoá lại hồ sơ nào; trường nào còn thiếu một phần hoặc chưa có dữ liệu để đối chiếu thì cảnh báo trước — người vận hành thấy để đi sửa nguồn, nhưng hồ sơ vẫn gửi được. **Luôn đo trước khi thêm một trường bắt buộc** — bài học từ `MA_YTE`, thứ từng chặn 97% hồ sơ vì một quy tắc sai.

**Tên thẻ dễ gõ nhầm:** là `TEN_NGUOI_HANH_NGHE` (có gạch dưới giữa `HANH` và `NGHE`), không phải `TEN_NGUOI_HANHNGHE`. Và `CHANDOAN_DIEUTRI` (không có gạch dưới giữa `CHANDOAN` và `DIEUTRI`). Có sẵn một test canh việc mọi thẻ khai trong hai bảng đều tồn tại trong `truong()` của loại tương ứng — nó sẽ bắt lỗi gõ, nhưng đọc `truong()` trước vẫn nhanh hơn.

- [ ] **Step 1: Viết test đỏ**

Thêm vào `tests/Unit/Ctdt/CtdtTruongBatBuocTest.php`:

```php
    /** @test */
    public function bo_sung_truong_bat_buoc_theo_cong_van_2076()
    {
        // Chi CHAN nhung truong ma du lieu that da du (do ngay 2026-08-20 tren 3047 chung tu):
        // rong 0% thi chan duoc ma khong khoa lai ho so nao.
        $mongDoi = [
            'CT03' => ['MA_KHOA', 'GIOI_TINH', 'DIA_CHI'],
            'CT04' => ['GIOI_TINH', 'DIA_CHI', 'CHAN_DOAN_VAO', 'CHAN_DOAN_RA',
                       'QT_BENHLY', 'TOMTAT_KQ', 'TT_RAVIEN', 'NGAY_CT'],
            'CT07' => ['SO_KCB', 'GIOI_TINH', 'DON_VI', 'CHANDOAN_DIEUTRI',
                       'MA_CCHN', 'TEN_NGUOI_HANH_NGHE', 'TEKT'],
        ];

        foreach ($mongDoi as $loai => $cac) {
            foreach ($cac as $the) {
                $this->assertContains($the, CtdtTruongBatBuoc::cua($loai), $loai . '/' . $the);
            }
        }
    }

    /** @test */
    public function truong_du_lieu_chua_du_thi_chi_CANH_BAO()
    {
        // MA_DANTOC rong 6/1050, PP_DIEUTRI rong 79/1050 - chan se khoa lai dung nhung ho so
        // dang gui duoc. CT06 chua co mot chung tu nao de doi chieu, chan la doan mo.
        $mongDoi = [
            'CT04' => ['MA_DANTOC', 'PP_DIEUTRI'],
            'CT06' => ['SO_KCB', 'TEN_DVI', 'CHAN_DOAN', 'TEN_BS', 'MA_BS', 'NGAY_CT'],
        ];

        foreach ($mongDoi as $loai => $cac) {
            foreach ($cac as $the) {
                $this->assertContains($the, CtdtTruongBatBuoc::khuyenNghi($loai), $loai . '/' . $the);
                $this->assertNotContains($the, CtdtTruongBatBuoc::cua($loai),
                    $loai . '/' . $the . ': chua du can cu de CHAN');
            }
        }
    }
```

- [ ] **Step 2: Chạy test để xác nhận đỏ**

```bash
php vendor/bin/phpunit tests/Unit/Ctdt/CtdtTruongBatBuocTest.php
```

Kỳ vọng: đỏ.

- [ ] **Step 3: Bổ sung vào hai bảng**

Trong `app/Services/Ctdt/Kiem/CtdtTruongBatBuoc.php`, sửa `BAT_BUOC` thành:

```php
        'CT03'              => ['MA_BHXH', 'MA_KHOA', 'HO_TEN', 'NGAY_SINH', 'GIOI_TINH',
                                'DIA_CHI', 'NGAY_VAO', 'NGAY_RA'],
        'CT04'              => ['MA_BHXH', 'HO_TEN', 'NGAY_SINH', 'GIOI_TINH', 'DIA_CHI',
                                'NGAY_VAO', 'NGAY_RA', 'CHAN_DOAN_VAO', 'CHAN_DOAN_RA',
                                'QT_BENHLY', 'TOMTAT_KQ', 'TT_RAVIEN', 'NGAY_CT'],
        'CT07'              => ['MA_BHXH', 'SO_KCB', 'HO_TEN', 'NGAY_SINH', 'GIOI_TINH',
                                'DON_VI', 'CHANDOAN_DIEUTRI', 'TU_NGAY', 'DEN_NGAY',
                                'MA_CCHN', 'TEN_NGUOI_HANH_NGHE', 'TEKT'],
```

(giữ nguyên `CT06`, `GIAYDIEUTRINOITRU`, `GIAYDIEUTRIVOSINH`, `GIAYSUCKHOEME`, `GIAYBAOTU`, `GIAYCHUNGSINH`)

và `KHUYEN_NGHI`:

```php
        'CT04'              => ['MA_DANTOC', 'PP_DIEUTRI'],
        'CT06'              => ['SO_KCB', 'TEN_DVI', 'CHAN_DOAN', 'TEN_BS', 'MA_BS', 'NGAY_CT'],
```

(các loại khác giữ nguyên giá trị đang có)

Thêm vào docblock của lớp:

```php
 * Danh sach mo rong ngay 2026-08-20 theo cong van 2076/BHXH-CNTT PL02, muc 3.2-3.6. Nguyen
 * tac chia hai muc: DO TRUOC tren du lieu that, truong nao rong 0% thi chan duoc ngay ma
 * khong khoa lai ho so nao; truong nao con thieu mot phan (CT04: MA_DANTOC rong 6/1050,
 * PP_DIEUTRI rong 79/1050) hoac chua co du lieu de doi chieu (CT06 chua co chung tu nao) thi
 * canh bao truoc - nguoi van hanh thay de di sua nguon, nhung ho so van gui duoc.
 *
 * LUON DO TRUOC khi them mot truong bat buoc. MA_YTE tung duoc them ma khong do, va no chan
 * 97% ho so trong nhieu ngay.
```

- [ ] **Step 4: Chạy cả thư mục và chờ nhiều test đỏ**

```bash
php vendor/bin/phpunit tests/Unit/Ctdt
```

Kỳ vọng: **nhiều test đỏ** — mọi bộ dữ liệu mẫu dựng "hồ sơ hợp lệ" giờ thiếu các trường mới. Đây là **đúng**, không phải hỏng: siết một quy tắc thì các mẫu phải tuân theo quy tắc đó.

**Sửa bộ dữ liệu mẫu, KHÔNG nới quy tắc.** Các nơi cần bổ sung giá trị:

- `tests/Unit/Ctdt/CtdtCheckerTest.php` — hàm trợ giúp `ct03HopLe()`
- `tests/Unit/Ctdt/CheckCtdtJobTest.php` — các lời gọi `hoSoCt03()`
- `tests/Unit/Ctdt/CtdtGuiToanLuongTest.php` và `CtdtKiemToanLuongTest.php` — các gói mẫu

Giá trị mẫu gợi ý (dùng giá trị **hợp lệ theo quy tắc kiểu trường**, đừng dùng chuỗi bất kỳ):
`GIOI_TINH => '1'`, `MA_KHOA => 'K01'`, `DIA_CHI => 'Ha Noi'`, `MA_DANTOC => '01'`,
`CHAN_DOAN_VAO => 'J18'`, `CHAN_DOAN_RA => 'J18'`, `QT_BENHLY => 'On dinh'`,
`TOMTAT_KQ => 'Khoi'`, `PP_DIEUTRI => 'Noi khoa'`, `TT_RAVIEN => '1'`, `NGAY_CT => '20260820'`,
`SO_KCB => 'KCB01'`, `DON_VI => 'Benh vien'`, `CHANDOAN_DIEUTRI => 'J18'`,
`MA_CCHN => 'CCHN01'`, `TEN_NGUOI_HANH_NGHE => 'BS Nguyen Van A'`, `TEKT => '0'`.

**Lưu ý `TEKT` là trường cờ** (chỉ nhận `'0'`/`'1'`) và `GIOI_TINH` chỉ nhận `'1'/'2'/'3'` — dùng giá trị khác sẽ sinh thêm lỗi và làm test đỏ vì lý do khác.

- [ ] **Step 5: Chạy lại cho tới khi chỉ còn một test đỏ**

```bash
php vendor/bin/phpunit tests/Unit/Ctdt
```

Kỳ vọng: đỏ **đúng một** — `CtdtCauHinhTest::gui_len_cong_mac_dinh_tat`.

Nếu một test đỏ vì lý do **không phải** thiếu trường mẫu, dừng lại và BÁO LẠI kèm tên test và thông điệp — đừng đoán.

- [ ] **Step 6: Chứng minh quy tắc mới có răng**

Commit trước, rồi:

- Bỏ `CHAN_DOAN_VAO` khỏi `BAT_BUOC` của CT04 → test bổ sung phải ĐỎ.
- Nâng `MA_DANTOC` của CT04 lên `BAT_BUOC` → test "chỉ cảnh báo" phải ĐỎ.

Hoàn nguyên bằng `git checkout -- app/Services/Ctdt/Kiem/CtdtTruongBatBuoc.php`.

- [ ] **Step 7: Commit**

```bash
git add app/Services/Ctdt/Kiem/CtdtTruongBatBuoc.php tests/Unit/Ctdt
git commit -m "feat(ctdt): bo sung truong bat buoc theo cong van 2076, do truoc khi siet"
```

---

## Task 4: Tài liệu và đối chiếu trên dữ liệu thật

**Files:**
- Modify: `docs/chung-tu-dien-tu-pl02.md`

**Interfaces:**
- Consumes: mọi thứ của Task 1–3
- Produces: không có mã sản phẩm mới

- [ ] **Step 1: Chạy lại bộ kiểm trên toàn bộ dữ liệu thật**

Task 3 đổi quy tắc, nên `so_loi` và `ctdt_loi` của 1074 hồ sơ đang **cũ**. Chạy lại:

```bash
php -r 'require "vendor/autoload.php"; $a=require "bootstrap/app.php"; $a->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap(); foreach (DB::table("ctdt_ho_so")->orderBy("id")->pluck("ma_ho_so") as $m) { (new App\Jobs\CheckCtdtJob($m))->handle(); } echo "xong\n";'
```

Rồi đếm:

```bash
php -r 'require "vendor/autoload.php"; $a=require "bootstrap/app.php"; $a->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap(); printf("chan: %d, canh bao: %d, san sang gui: %d\n", DB::table("ctdt_ho_so")->where("so_loi",">",0)->count(), DB::table("ctdt_loi")->where("muc_do","canh_bao")->count(), DB::table("ctdt_ho_so")->where("so_loi",0)->whereNotNull("checked_at")->count());'
```

**Ghi lại ba con số này** — chúng vào tài liệu ở bước sau. Kỳ vọng theo số đo lúc lập kế hoạch: **0 hồ sơ bị chặn**, khoảng **85 cảnh báo** (6 `MA_DANTOC` + 79 `PP_DIEUTRI`), **1074 sẵn sàng gửi**.

Nếu số hồ sơ bị chặn **lớn hơn 0**, dừng lại và BÁO LẠI kèm phân bố lỗi:

```bash
php -r 'require "vendor/autoload.php"; $a=require "bootstrap/app.php"; $a->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap(); foreach (DB::table("ctdt_loi")->select(DB::raw("ma_loi, ten_truong, muc_do, COUNT(*) n"))->groupBy("ma_loi","ten_truong","muc_do")->orderBy(DB::raw("COUNT(*)"),"desc")->get() as $r) { printf("  %-8s %-20s %-9s %d\n", $r->ma_loi, $r->ten_truong ?: "-", $r->muc_do, $r->n); }'
```

Nghĩa là số đo lúc lập kế hoạch đã cũ hoặc một trường bị gõ nhầm tên — **đừng nới quy tắc để cho qua**.

- [ ] **Step 2: Cập nhật tài liệu**

Trong `docs/chung-tu-dien-tu-pl02.md`:

**(a)** Cập nhật số test ở bảng đầu và ở phần "Kiểm thử" cho khớp con số thật sau Task 1–3.

**(b)** Thêm một mục về hai trạng thái mới, đại ý: `Ký số thất bại` phân biệt với `Chưa ký số` (hồ sơ đã bấm ký và hỏng, lý do nằm ở `signed_error`); `Gửi thất bại` phân biệt với `Chờ gửi` (đã thử gửi và hỏng trước khi tới cổng). Nêu rõ thứ tự ưu tiên và **vì sao** mỗi vị trí quan trọng — chép từ chú thích trong `cua()`.

**(c)** Thêm một đoạn về chống bấm trùng: khoá cache theo từng mã hồ sơ, hết hạn sau 10 phút, nhả ở cuối chuỗi job. Nêu bằng chứng thật (một hồ sơ từng bị bấm ba lần) và hậu quả nếu không có (tối đa ba lần POST thật lên cổng, PL02 không có mã giao dịch phía client nên cổng không khử trùng được).

**(d)** Cập nhật mục về trường bắt buộc: bảng số đo của Task 3, và nguyên tắc **"đo trước khi siết"**.

- [ ] **Step 3: Chạy hai suite, đối chiếu baseline**

```bash
php vendor/bin/phpunit --testsuite Unit
```

Kỳ vọng: `Errors: 4, Failures: 7`.

```bash
php vendor/bin/phpunit --testsuite Feature
```

Kỳ vọng: `Errors: 8, Failures: 4`.

- [ ] **Step 4: Commit**

```bash
git add docs/chung-tu-dien-tu-pl02.md
git commit -m "docs(ctdt): ghi lai hai trang thai moi, chong bam trung, va so do truong bat buoc"
```

---

## Hoàn tất

Màn danh sách nói được hồ sơ kẹt ở đâu và vì sao — không còn phải mở từng hồ sơ để biết USB token bị rút hay mạng chập. Một lần bấm nhầm không sinh hai lần gửi thật. Bộ kiểm đòi đúng những trường công văn 2076 yêu cầu, ở mức mà dữ liệu thật chịu được.

**Việc cần kiểm bằng tay sau khi triển khai:**

1. Rút USB token, bấm Ký và gửi một hồ sơ, xem màn **danh sách** có hiện "Ký số thất bại" không (trước đây hiện "Chưa ký số").
2. Bấm nút hai lần liên tiếp thật nhanh — lần thứ hai phải báo "đang xử lý", và `SELECT COUNT(*) FROM jobs WHERE queue = 'JobSignCtdt'` chỉ tăng một.
3. Lọc theo trạng thái "Ký số thất bại" và "Gửi thất bại" trên màn danh sách, xác nhận ra đúng tập hồ sơ.

**Sau ba việc này mới tới Giai đoạn 5** (Console `ctdt:import`, xuất Excel, dashboard) — xem đặc tả mục 9.
