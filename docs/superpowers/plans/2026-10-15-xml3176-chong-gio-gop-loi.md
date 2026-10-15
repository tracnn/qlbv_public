# XML3176 – Gộp lỗi chồng giờ PTTT, tách lỗi thời gian dài bất thường, chặn trần lỗi Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Lỗi `XML3_OVERLAPPING_SERVICE_EXECUTION` không còn tăng theo bình phương số dòng (000007038497: 372.807 cặp → tràn 512 MB), dòng PTTT dài bất thường báo lỗi riêng, và không quy tắc nào làm phình bộ đệm lỗi được nữa.

**Architecture:** Helper thuần `PtttChongGio` (ngưỡng theo mã + quét một lượt sắp theo giờ bắt đầu). Checker XML3 tính một lần mỗi hồ sơ qua `theoHoSo`, báo mỗi dòng một lỗi. `Xml3176ErrorService::saveErrors` (chế độ gom) chặn trần mỗi (ma_lk, error_code). Migration nạp mã mới; lệnh `xml3176:kiem-lai-chong-gio` thay lỗi cũ trên prod.

**Tech Stack:** Laravel 5.5, PHP 7.4, PHPUnit 6.5, SQLite bộ nhớ trong test.

**Spec:** mô tả 5 mục đã duyệt trong chat ngày 15/10/2026 (tóm tắt ở Global Constraints).

## Global Constraints

- Test chạy `DB_HOST=127.0.0.1 vendor/bin/phpunit`; dựng bảng bằng `bootXml3176Sqlite`; cấm RefreshDatabase/DatabaseMigrations; không chạy migrate.
- Nhóm áp dụng: `config('xml3176.xml3.overlap_execution_groups')` (mặc định [8, 18]).
- `xml3176.xml3.execution_max_hours` = 24; `execution_max_hours_theo_ma` = `['01.0181' => 72, '01.0182' => 72, '01.0183' => 72, '11.0118' => 48]` (tiền tố mã, tiền tố dài nhất thắng). Vượt = số phút thực hiện > ngưỡng × 60.
- Mã mới `XML3_EXECUTION_TIME_TOO_LONG`, tên "Thời gian thực hiện dịch vụ dài bất thường", critical_error = false (cảnh báo, như mã chồng giờ trên prod — đổi 15/10 sau khi đo danh mục prod), is_check = true.
- Dòng vượt ngưỡng KHÔNG tham gia so chồng giờ. Dòng có khoảng ngược/rỗng (đến ≤ từ) cũng không (giữ như `chongNhau`).
- Chồng giờ: mỗi dòng một lỗi, đối xứng hai phía, liệt kê tối đa 5 đối tác theo (giờ bắt đầu, id) tăng dần.
- Trần: 2.000 dòng / (ma_lk, error_code) trong chế độ gom, cộng một dòng tóm tắt khi có lược.

## Review Focus

- Hai dòng cùng giờ bắt đầu, hoặc nối tiếp đúng mốc (kết thúc = bắt đầu) → nối tiếp KHÔNG chồng (biên nửa mở) — test ở Task 2.
- Dòng đúng bằng ngưỡng (24 giờ chẵn) → không báo dài bất thường — test ở Task 1.
- Mã có hậu tố (`01.0181.0118_GT`) vẫn khớp ngưỡng riêng theo tiền tố — test ở Task 1.
- Hồ sơ đổi giữa chừng (ma_lk khác) không dùng nhầm kết quả quét của hồ sơ trước — test ở Task 2.
- Bộ đệm qua nhiều hồ sơ: trần tính riêng từng ma_lk, bắt đầu gom lại thì đếm lại từ 0 — test ở Task 3.

---

### Task 1: Ngưỡng + lỗi `XML3_EXECUTION_TIME_TOO_LONG`

**Files:**
- Create: `app/Services/Xml3176/Support/PtttChongGio.php`
- Modify: `config/xml3176.php` (khối `xml3`), `app/Services/Xml3176Xml3Checker.php`
- Test: `tests/Unit/Xml3176/Support/PtttChongGioTest.php`, `tests/Unit/Xml3176/Checker/ThoiGianPtttTest.php`

**Interfaces — Produces:**
- `PtttChongGio::nguongGio(string $maDichVu, int $macDinh, array $theoMa): int`
- `Xml3176Xml3Checker::checkExecutionTimeTooLong(Xml3176Xml3 $data): Collection` (private)
- `Xml3176Xml3Checker::vuotNguong(Xml3176Xml3|object $dong): bool` (private, dùng lại ở Task 2)

- [ ] Test `nguongGio`: '01.0181.0118' → 72; '01.0181.0118_GT' → 72; '11.0118.1159' → 48; '02.0150.0114' → 24; khoá '01' => 10 và '01.0181' => 72 cùng có thì '01.0181.0118' → 72.
- [ ] Test checker: nhóm 18, 202601010800 → 202601020800 (đúng 24 giờ) không lỗi; → 202601020801 có lỗi, mô tả `'Dịch vụ 02.0150.0114 (01/01/2026 08:00 - 02/01/2026 08:01) kéo dài 24 giờ 1 phút, vượt ngưỡng 24 giờ. Kiểm tra lại NGAY_TH_YL / NGAY_KQ.'`; nhóm 3 không xét; CVVHDF 48 giờ không lỗi; thiếu mốc không lỗi.
- [ ] Chạy đỏ → cài → xanh → commit.

### Task 2: Chồng giờ theo dòng, quét một lượt

**Files:**
- Modify: `PtttChongGio.php`, `Xml3176Xml3Checker.php::checkOverlappingServiceExecution`
- Test: `PtttChongGioTest.php`, sửa `tests/Unit/Xml3176/TangToc/TheoHoSoTest.php` (ghim hành vi mới — thay đổi có chủ đích)

**Interfaces — Produces:**
- `PtttChongGio::quet(array $dong, int $toiDaNeu): array` — `$dong` là list `['id'=>int,'tu'=>int,'den'=>int]` (timestamp); trả `[id => ['so' => int, 'neu' => int[] /* id đối tác */]]` chỉ cho dòng có chồng.

Thuật toán: lọc `den > tu`; sắp theo (tu, id); giữ danh sách đang mở theo thứ tự đã thêm; với mỗi dòng i bỏ khỏi danh sách mở các dòng có `den <= tu_i`, mọi dòng còn lại là đối tác (đếm hai phía, nối id vào `neu` hai phía nếu chưa đủ `toiDaNeu`), rồi thêm i.

- [ ] Test `quet`: A 8:00–9:00, B 8:30–9:30, C 8:45–8:50, F 10:00–11:00 → A{so 2, neu [B,C]}, B{2,[A,C]}, C{2,[A,B]}, F không có; nối tiếp 8–9 & 9–10 → rỗng; 8 dòng chồng nhau, toiDaNeu 5 → so 7, neu 5 id đầu.
- [ ] Sửa `TheoHoSoTest::xml3_chong_thoi_gian_ket_qua_giong_truy_van_cu` → kỳ vọng mới: A: `'Dịch vụ A (01/01/2026 08:00 - 01/01/2026 09:00) chồng thời gian thực hiện với 2 dịch vụ khác trong cùng hồ sơ: B (01/01/2026 08:30 - 01/01/2026 09:30); C (01/01/2026 08:45 - 01/01/2026 08:50).'`; C cũng có lỗi (đối xứng). Một đối tác: giữ nguyên câu cũ `'… chồng thời gian thực hiện với dịch vụ B (…) trong cùng hồ sơ.'`. Quá 5: thêm `'; và K dịch vụ khác'` trước dấu chấm.
- [ ] Test: dòng vượt ngưỡng không gây chồng cho dòng khác; 900 dòng cùng NGAY_KQ (kiểu 7038497) → mỗi dòng ≤1 lỗi chồng giờ.
- [ ] Giữ test "nạp một lần" (`xml3_tap_theo_ho_so_nap_mot_lan_cho_50_dong`) xanh.
- [ ] Đỏ → cài → xanh → commit.

### Task 3: Chặn trần trong `saveErrors`

**Files:** Modify `app/Services/Xml3176ErrorService.php`; Test `tests/Unit/Xml3176/Xml3176ErrorServiceGomTest.php`

**Interfaces — Produces:** `const TRAN_MOI_MA = 2000;` đếm `[ma_lk|error_code]`; vượt thì không đẩy vào bộ đệm, đếm lược; dòng tóm tắt được thêm vào bộ đệm khi `ketThucGom` và hiện trong `boDemGom()` (do-kiem thấy).

Dòng tóm tắt: cùng xml/ma_lk/error_code/critical/error_name của lỗi lược đầu tiên, stt của lỗi lược đầu tiên, `them` = [], mô tả `'Đã lược bớt K lỗi cùng mã <MA> của hồ sơ (chỉ giữ 2.000 dòng đầu).'`.

- [ ] Test (không chạm DB, gọi `saveErrors` + `boDemGom`): 2.005 lỗi cùng mã một hồ sơ → bộ đệm 2.000 + 1 tóm tắt "Đã lược bớt 5 lỗi…"; hồ sơ khác không bị ảnh hưởng; `batDauGom` lại thì đếm lại.
- [ ] Đỏ → cài → xanh → commit.

### Task 4: Migration danh mục + lệnh kiểm lại

**Files:**
- Create: `database/migrations/2026_10_15_120000_nap_ma_loi_thoi_gian_pttt_dai.php` (updateOrCreate mã mới, critical false — ép như migration NVYT vì mã có thể tự sinh ở mức nghiêm trọng trước khi migrate)
- Modify: `database/seeds/Xml3176ErrorCatalogOverlapServiceSeeder.php` (thêm mã mới)
- Modify: `Xml3176Xml3Checker` — public `kiemThoiGianPttt(Xml3176Xml3 $data): Collection` (gộp hai quy tắc) và public `thongTinThem(Xml3176Xml3 $data): array` (additionalData ngay_yl/ngay_kq, tách từ `checkErrors`)
- Create: `app/Console/Commands/Xml3176KiemLaiChongGio.php` — `xml3176:kiem-lai-chong-gio {--tu=} {--den=} {--thu} {--lo=500}`; duyệt xml1 theo ma_lk tăng dần, chỉ hồ sơ có dòng XML3 nhóm cấu hình; mỗi hồ sơ một transaction: xoá hai mã → `batDauGom` → `saveErrors('XML3', …)` từng dòng → `ketThucGom`; dừng nếu thiếu mã mới trong danh mục; in số xoá / số mới theo mã.
- Test: `tests/Unit/Xml3176/MigrationMaLoiThoiGianPtttDaiTest.php`, `tests/Unit/Xml3176/KiemLaiChongGioTest.php`

- [ ] Test migration: nạp critical 0, is_check 1; dòng tự sinh critical 1 bị ép về 0, không nhân đôi.
- [ ] Test lệnh: hồ sơ có 3 lỗi chồng cũ theo cặp → sau lệnh còn lỗi theo dòng mới; `--thu` không ghi; lỗi mã khác giữ nguyên.
- [ ] Đỏ → cài → xanh → commit.

### Task 5: Xác minh

- [ ] `DB_HOST=127.0.0.1 php vendor/phpunit/phpunit/phpunit tests/Unit/Xml3176` — chỉ còn 4 lỗi nền (cần MySQL cục bộ).
- [ ] Đo chỉ đọc trên CSDL thật: `php artisan xml3176:do-kiem --ma-lk-tep=<tệp 2 hồ sơ>` → 000007038497 chạy hết dưới 512 MB; đếm hai mã mới.
- [ ] Cập nhật readme (mục hạn chế đã biết) + commit.
