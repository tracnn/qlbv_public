# Tăng tốc quét kiểm XML3176 — Bước B — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Một job kiểm cho mỗi hồ sơ, kiểm tổng thể đọc dữ liệu hồ sơ một lần, lệnh đo phản ánh đúng worker — không đổi kết quả kiểm.

**Architecture:** `DuLieuHoSo` nạp các bảng XML của hồ sơ một lần (mảng stdClass); `Xml3176CompleteChecker` và `NvytThuThap` tính trên dữ liệu đó. Service `KiemHoSoXml3176` gói toàn bộ việc kiểm một hồ sơ (12 loại + tổng thể, gom, xoá lỗi cũ, đóng dấu `checked_at`, so token trước mỗi lần ghi); `KiemHoSoXml3176Job` và lệnh `xml3176:do-kiem` cùng gọi service này. Chuỗi sau nạp: `KiemHoSoXml3176Job` → Export → Sign → Submit. Thêm chỉ mục best-effort cho "PTTT trùng bác sĩ".

**Tech Stack:** PHP 7.4 (bcmath), Laravel 5.5 (queue `database`), PHPUnit 6 + SQLite; prod MariaDB 10.4.

**Spec:** `docs/superpowers/specs/2026-10-15-xml3176-tang-toc-kiem-buoc-b-design.md`

## Global Constraints

- PHP 7.4 / Laravel 5.5; `setUp()` không `: void`; không type-hint tiêm service trên `Job::handle()`.
- Test: `DB_HOST=127.0.0.1 vendor/bin/phpunit <path>` (`.env` trỏ CSDL THẬT). Cấm `RefreshDatabase`/`DatabaseMigrations`; không chạy migrate/seed/tinker/lệnh ghi lên CSDL thật; không chạy `xml3176:do-kiem` lên CSDL thật (controller làm).
- **Kết quả kiểm giống hệt**: cùng dữ liệu → cùng tập `(xml, ma_lk, stt, error_code, description, critical_error)`.
- Job mới: `App\Jobs\KiemHoSoXml3176Job`, hàng đợi `config('xml3176.queue…')` giống `CheckXml3176TypeJob` (đọc `config/xml3176.php` — cùng tên hàng đợi `JobXml3176`), `$tries = 2`, `$timeout = 240`, dùng trait `ThuocChuoiXml3176`.
- Giữ nguyên lớp `CheckXml3176TypeJob`, `CheckCompleteXml3176RecordJob` (chữ ký, thuộc tính, hành vi) cho job đã xếp trước deploy; không còn nơi nào xếp mới chúng.
- So token **trước mỗi lần xoá/ghi lỗi** (từng loại XML và kiểm tổng thể); lệch → `catChuoi()` và dừng. Đóng dấu `checked_at` có điều kiện token (`ghiNeuConHieuLuc`). Job không token: giữ quy tắc hiện hành của `laJobCu()` (đọc kỹ `CheckCompleteXml3176RecordJob::handle` dòng ~40-64, quy tắc F1 của 37a2234b).
- Kiểm tổng thể trong job mới: trừ khi `organization.xml_3176_not_check`, **xoá lỗi `xml='XMLComplete'`** của hồ sơ rồi chạy ở **chế độ gom**. Không viết chuỗi `xml_3176_not_check` vào `app/Services/Xml3176/Xml3176Importer.php` (test cấm).
- Tổng tiền: `bcadd` thang 6, bỏ NULL; so sánh giữ nguyên biểu thức cũ (vd `$data->t_thuoc != round($tong, 2)` — truyền `(float)` của chuỗi bc vào `round` để giữ đúng kiểu so sánh cũ).
- So chuỗi theo ngữ nghĩa `utf8_general_ci`: dùng `App\Services\Xml3176\Support\NhoTheoHoSo::khoaChuoiSql` (hoặc hàm tương đương công khai) — không phân biệt hoa thường, bỏ khoảng trắng cuối.
- Chỉ mục mới best-effort như `database/migrations/2026_10_15_120000_them_chi_muc_trung_giuong_xml3176_xml3s.php` (thử INPLACE/LOCK=NONE → thường → `Log::warning`, không ném; kiểm tồn tại).
- Commit: `perf(xml3176): ...`/`feat(xml3176): ...` tiếng Việt không dấu + `Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>`.

## Review Focus

1. Hồ sơ bị nạp lại (token đổi) khi job đang chạy giữa các loại XML → không ghi thêm lỗi, không đóng dấu `checked_at` (Task 5).
2. Cột tiền có NULL, số thập phân lẻ (vd 0.1+0.2), giá trị âm → tổng và kết quả so sánh như `SUM` MySQL + `round(...,2)` cũ (Task 2–3).
3. Chuỗi khác hoa thường/khoảng trắng cuối trong `ma_nhom`/`ngay_kq <> ''`/`muc_huong` → lọc như MySQL (Task 2–3).
4. Hồ sơ không có loại XML nào có checker → job vẫn kiểm tổng thể và đóng dấu (Task 5).
5. Job cũ (`CheckXml3176TypeJob`, `CheckCompleteXml3176RecordJob`) còn trong hàng đợi sau deploy → chạy được, không tạo lỗi trùng ngoài hành vi cũ (Task 5).

---

### Task 0: Mốc test
- [ ] `DB_HOST=127.0.0.1 vendor/bin/phpunit tests/Unit/Xml3176 2>&1 | tail -1` → mốc hiện tại: 915 test, 4 lỗi có sẵn (`Xml3176Xml3CheckerRuleTest::{tg_th_yl_trung_tg_kq, thuc_hien_duoi_3_phut_nhom_1, bac_si_vua_ra_vua_thuc_hien}`, `Xml3176ErrorServiceGomTest::che_do_gom_bat_tat_dung`).

---

### Task 1: `xml3176:do-kiem` xả lỗi theo từng hồ sơ (B1)

**Files:** Modify `app/Console/Commands/Xml3176DoKiem.php`, `app/Services/Xml3176ErrorService.php`; Test `tests/Unit/Xml3176/TangToc/DoKiemLenhTest.php`

**Interfaces — Produces:** `Xml3176ErrorService::xoaBoDemGom(): void` (xoá bộ đệm gom, vẫn ở chế độ gom). Lệnh: sau mỗi hồ sơ lấy `boDemGom()`, lọc `is_check` (đường `chuanBiGhi` hiện có), **ghi nối** vào tệp tạm, `xoaBoDemGom()`; cuối lệnh đọc tệp tạm, sắp theo khoá hiện có, ghi tệp `--xuat-loi`. Thống kê thêm: thời gian tường mỗi hồ sơ (trung bình, p90, max) trong `--xuat-do` và dòng tóm tắt console.

- [ ] **Step 1: Test (đỏ)** — `xuat_loi_khong_doi_khi_xa_theo_ho_so` (2 hồ sơ, tệp kết quả giống hệt định dạng/sắp xếp cũ — so với chuỗi kỳ vọng tạo từ dữ liệu test); `bo_dem_gom_khong_tich_luy` (sau lệnh, `boDemGom()` rỗng; với 2 hồ sơ, đếm phần tử tối đa của bộ đệm trong khi chạy ≤ số lỗi của hồ sơ lớn nhất — dùng một hook/thống kê nội bộ); `xuat_do_co_thoi_gian_ho_so` (tệp `--xuat-do` chứa dòng `wall` với trung bình/p90).
- [ ] Step 2 FAIL → Step 3 cài → Step 4 `tests/Unit/Xml3176` chỉ 4 lỗi mốc → Step 5 commit `perf(xml3176): do-kiem xa loi theo tung ho so, them thoi gian tuong`.

> **Controller sau Task 1:** tạo worktree tách rời tại commit Task 1, chạy (chỉ đọc) `--ma-lk-tep=mau200.txt --xuat-loi=loi_b_truoc.jsonl --xuat-do=do_b_truoc.txt` và `--ma-lk-tep=mau40.txt --xuat-do=do40_b_truoc.txt` (tệp mẫu ở scratchpad `tangtoc/`) → mốc so sánh và đo cho Task 7.

---

### Task 2: `DuLieuHoSo` — nạp hồ sơ một lần + phép tính kiểu SQL

**Files:** Create `app/Services/Xml3176/DuLieuHoSo.php`; Test `tests/Unit/Xml3176/TangToc/DuLieuHoSoTest.php`

**Interfaces — Produces:**
- `DuLieuHoSo::nap(string $maLk, array $loai = null): self` — đọc bằng `DB::table('xml3176_xmlNs')->where('ma_lk', $maLk)->orderBy('id')->get()` mỗi bảng một truy vấn (mặc định 12 bảng xml1, 2, 3, 4, 5, 7, 8, 9, 10, 11, 13, 14; cột: tất cả cột — đơn giản, đúng; builder có thể thu hẹp nếu chứng minh đủ).
- `xml1(): ?object` (dòng đầu theo `id`), `dong(string $loai): array` (vd `'xml3'`), `co(string $loai, callable $loc = null): bool`, `dau(string $loai, callable $loc = null): ?object`, `dem(...)`, `loc(string $loai, callable $loc): array`.
- `static tong(array $dong, string $cot): string` — `bcadd` thang 6, bỏ `null`; chuỗi số rỗng/không phải số xử lý như MySQL (`''` → 0). `static max(array $dong, string $cot)` — bỏ `null`, so sánh số như MySQL trên cột kiểu thật (đọc migration để biết kiểu cột `muc_huong`).
- `static bang(?string $a, ?string $b): bool` — so chuỗi theo `utf8_general_ci` (dùng `NhoTheoHoSo::khoaChuoiSql`); `static trongTap(?string $v, array $tap): bool` — `IN (...)` theo cùng ngữ nghĩa (chú ý `ma_nhom` có thể là số trong config: so như MySQL so chuỗi với số — đọc config và kiểu cột).
- Không phụ thuộc Eloquent.

- [ ] **Step 1: Test (đỏ)** — `tong_bo_null_va_chinh_xac_thap_phan` (`[0.1, 0.2, null, '1.005']` → `'1.305000'`), `tong_rong_bang_0`, `max_bo_null`, `bang_khong_phan_biet_hoa_thuong_va_khoang_trang_cuoi`, `trong_tap_so_va_chuoi` (cột `'08'`? — theo kiểu cột thật; viết test theo hành vi MySQL được ghi rõ trong comment), `nap_moi_bang_mot_truy_van` (đếm `DB::listen` = số bảng), `dong_sap_theo_id`.
- [ ] Steps 2–4; Step 5 commit `perf(xml3176): DuLieuHoSo nap ho so mot lan va phep tinh kieu SQL`.

---

### Task 3: Kiểm tổng thể và NVYT tính trên `DuLieuHoSo`

**Files:** Modify `app/Services/Xml3176CompleteChecker.php`, `app/Services/Xml3176/NvytThuThap.php`; Test `tests/Unit/Xml3176/TangToc/KiemTongTheTuDuLieuTest.php`

**Interfaces:**
- Consumes Task 2.
- Produces: `Xml3176CompleteChecker::checkErrors($ma_lk, DuLieuHoSo $du = null): void` (null → tự `DuLieuHoSo::nap`); các hàm `check*` riêng nhận/đọc `$du` thay cho truy vấn (danh sách truy vấn phải thay ở spec §6 — đối chiếu mã thật; mỗi chỗ ghi chú dòng cũ bằng comment ngắn). `kiemNvyt($ma_lk, DuLieuHoSo $du = null)`. `NvytThuThap::tuHoSo(string $maLk, DuLieuHoSo $du = null): array` (null → nạp như cũ; giữ cho `xml3176:kiem-lai-nvyt`).
- Các truy vấn danh mục/bộ đệm (`MedicalOrganization`, `VatPhamKemDvktLookup`, `NvytLookup`, `getCriticalErrorStatus`) giữ nguyên.
- `checkVatPhamKemDvkt` truyền cho `VatPhamKemDvktRule::kiem` các dòng xml3/xml2 có **đúng các thuộc tính** mà truy vấn cũ chọn (stdClass thay model là được nếu Rule chỉ đọc thuộc tính — kiểm).

- [ ] **Step 1: Test (đỏ, viết trước và chạy XANH trên mã cũ để ghim hành vi)** — SQLite, mỗi nhóm kiểm có ≥ 1 ca lỗi + 1 ca không lỗi: tiền (`INVALID_EXPENSE_*` với NULL và thập phân lẻ), ngày giường (3 hàm dùng tổng ngày giường), khám (`ERROR_MULTIPLE_EXAMINATION_FEES_INPATIENT`, `DUPLICATE_EXAMINATION_SERVICE`, …), tái khám/XML14, cân nặng con/XML9, mức hưởng (`mucHuongKhacVoi`, `checkMucHuong` max), `XML4_NGAY_KQ_MISMATCH_XML3`, `SECOND_SURGERY_FULL_PAYMENT`, vật phẩm kèm DVKT, NVYT. So **mã lỗi + mô tả** với kỳ vọng. Thêm `so_truy_van_ho_so` (đếm truy vấn tới bảng `xml3176_xml*` khi gọi `checkErrors` với `DuLieuHoSo` đã nạp → 0; khi null → đúng số bảng nạp).
- [ ] Step 2: chạy test trên mã cũ → các test hành vi XANH, test đếm truy vấn ĐỎ (ghi vào báo cáo).
- [ ] Step 3: chuyển đổi; Step 4: tất cả xanh, `tests/Unit/Xml3176` chỉ 4 lỗi mốc (gồm `Xml3176Complete*Test`, `Nvyt/*`). Step 5 commit `perf(xml3176): kiem tong the doc du lieu ho so mot lan`.

---

### Task 4: Chỉ mục "PTTT trùng bác sĩ"

**Files:** Create `database/migrations/2026_10_15_140000_them_chi_muc_pttt_trung_bac_si_xml3176_xml3s.php`; Test `tests/Unit/Xml3176/TangToc/ChiMucPtttTrungBacSiTest.php`

**Interfaces:** chỉ mục `xml3176_xml3s_bs_ngay_yl_th_kq_index` trên `ma_bac_si(100), ngay_yl(14), ngay_th_yl(14), ngay_kq(14)` (MySQL: câu `ALTER TABLE … ADD INDEX … (ma_bac_si(100), ngay_yl(14), ngay_th_yl(14), ngay_kq(14)), ALGORITHM=INPLACE, LOCK=NONE`; SQLite: `Schema` không tiền tố). Cùng khuôn best-effort/kiểm tồn tại/`down()` như migration `2026_10_15_120000`.

- [ ] Test: `chay_lai_khong_loi`, `loi_tao_khong_nem` (lớp con ném `QueryException` ở cả hai lần), `down_xoa`. Steps 2–4; commit `perf(xml3176): chi muc kiem PTTT trung bac si`.

> Controller (chỉ đọc, trước Task 7): kiểm độ dài thật lớn nhất của `ma_bac_si`, `ngay_yl`, `ngay_th_yl`, `ngay_kq` trên prod; nếu > tiền tố → báo, điều chỉnh.

---

### Task 5: Service `KiemHoSoXml3176` + job một-hồ-sơ + chuỗi mới

**Files:** Create `app/Services/Xml3176/KiemHoSoXml3176.php`, `app/Jobs/KiemHoSoXml3176Job.php`; Modify `app/Services/Xml3176/Xml3176ChuoiXuLy.php`, `app/Console/Commands/Xml3176DoKiem.php` (dùng service), tests trong `tests/Unit/Xml3176/Chuoi/*` và `tests/Unit/Xml3176/CheckXml3176TypeJobTest.php` nếu thay đổi chuỗi làm đỏ; Test `tests/Unit/Xml3176/TangToc/KiemHoSoJobTest.php`

**Interfaces:**
- `KiemHoSoXml3176::chay(string $maLk, array $loaiXml, callable $conHieuLuc, bool $ghi = true): bool` — trả `false` nếu dừng vì `$conHieuLuc()` false.
  - `$ghi = true` (job): `dongBoBoDem()`; với mỗi `$loai` theo thứ tự `Xml3176CheckTypes::LOAI`: kiểm `$conHieuLuc()` → xoá lỗi `(ma_lk, xml=$loai)` → `batDauGom` → nạp dòng `Xml3176CheckTypes::truyVanDong` + chunk 500 như `CheckXml3176TypeJob` → `ketThucGom` trong `finally`. Rồi (trừ khi `organization.xml_3176_not_check`): kiểm `$conHieuLuc()` → xoá lỗi `xml='XMLComplete'` → `batDauGom` → `DuLieuHoSo::nap` + `Xml3176CompleteChecker::checkErrors($maLk, $du)` → `ketThucGom` trong `finally`. Trả `true`.
  - `$ghi = false` (do-kiem): không xoá, không `ketThucGom` (bộ đệm gom để lệnh đọc), không đóng dấu.
  - Không tự đóng dấu `checked_at` (job làm, có token).
- `KiemHoSoXml3176Job($maLk, array $loaiXml, $chainToken = null)` — `handle()`: quy tắc token/job cũ như `CheckCompleteXml3176RecordJob` (đọc mã); gọi `chay(..., function () { return $this->laJobCu() || $this->conHieuLuc($this->maLk); })`; `false` → `catChuoi()`; `true` → đóng dấu `checked_at` qua `ghiNeuConHieuLuc` (job có token) hoặc như cũ (job không token — giữ quy tắc F1). `failed()` → `ghiNeuConHieuLuc(['export_error' => 'Không xuất: bước kiểm hồ sơ lỗi — ' . $e->getMessage()])` (khuôn như hai job cũ).
- `Xml3176ChuoiXuLy::xepSauNap`: chuỗi = `[KiemHoSoXml3176Job($maLk, $loaiCoChecker, $token)]` + (nếu cho phép xuất) Export, Sign, Submit như cũ; `$loaiCoChecker` = các loại đã nạp có checker, đã khử trùng, thứ tự `LOAI`. `xepTuXuat` không đổi.
- `Xml3176DoKiem`: thay vòng kiểm tự viết bằng `KiemHoSoXml3176::chay($maLk, <mọi loại có dòng>, fn => true, false)`, rồi xả bộ đệm như Task 1.

- [ ] **Step 1: Test (đỏ)** — `KiemHoSoJobTest` (SQLite + `Queue::fake` khi cần): `kiem_du_loai_va_tong_the_ghi_loi_gom` (lỗi các loại + XMLComplete có trong `xml3176_error_results`, mỗi loại xoá lỗi cũ của mình, XMLComplete cũ bị xoá); `token_doi_giua_chung_thi_dung` (`$conHieuLuc` trả true lần 1, false lần 2 → loại thứ 2 không bị xoá/ghi, không có XMLComplete mới, `checked_at` không đổi, chuỗi bị cắt); `danh_sach_loai_rong_van_kiem_tong_the_va_dong_dau`; `dong_dau_checked_at_co_dieu_kien_token`; `failed_ghi_export_error_khi_con_hieu_luc`; `xml_3176_not_check_bo_qua_tong_the_van_dong_dau`; `job_cu_van_chay` (dispatch `CheckXml3176TypeJob` và `CheckCompleteXml3176RecordJob` theo chữ ký cũ, chạy được); `chuoi_moi_thu_tu` (`xepSauNap`: `KiemHoSo` → Export → Sign → Submit, cùng hàng đợi/token như test chuỗi hiện có; không xuất → chỉ `KiemHoSo`). Cập nhật `Xml3176ChuoiXuLyTest` và các test chuỗi khác theo chuỗi mới, ghi lý do.
- [ ] Steps 2–4: `tests/Unit/Xml3176` chỉ 4 lỗi mốc. Step 5 commit `feat(xml3176): mot job kiem moi ho so`.

---

### Task 6: Nghiệm thu + tài liệu

**Files:** Create `docs/trien-khai-xml3176-tang-toc-kiem-buoc-b.md`.

- [ ] **Step 1:** `tests/Unit/Xml3176` → chỉ 4 lỗi mốc.
- [ ] **Step 2 (controller, chỉ đọc trên prod):** từ HEAD chạy `xml3176:do-kiem` trên `mau200.txt` (`--xuat-loi=loi_b_sau.jsonl --xuat-do=do_b_sau.txt`) và `mau40.txt`; so tập lỗi đã chuẩn hoá với `loi_b_truoc.jsonl` → giống hệt (khác → kiểm dữ liệu đổi giữa hai lần chạy, nếu không là lỗi → quay lại task). Bảng so sánh wall/hồ sơ, truy vấn/hồ sơ, ms SQL (đặc biệt kiểm tổng thể và XML3).
- [ ] **Step 3:** tài liệu: chuỗi mới (một job kiểm/hồ sơ, rồi xuất/ký/gửi), job cũ trong hàng đợi vẫn chạy được, migration chỉ mục mới (best-effort, kiểm log), kiểm token xuyên suốt; sau deploy đo thông lượng hàng đợi trên lô kế tiếp (câu SQL chỉ đọc đếm hồ sơ có `checked_at` theo phút) và so với lô trước.
- [ ] **Step 4:** commit `docs(xml3176): huong dan tang toc kiem buoc B`.

---

## Self-review (đã chạy)

- Spec §3.1 → Task 1 (+ Task 5 dùng service); §3.2 → Task 2, 3, 4; §3.3 → Task 5; §3.4 → Task 1 (mốc), Task 6; quyết định token/XML1 → Global Constraints + Task 5.
- Tên nhất quán: `DuLieuHoSo::{nap,xml1,dong,co,dau,dem,loc,tong,max,bang,trongTap}`, `KiemHoSoXml3176::chay`, `KiemHoSoXml3176Job`, `Xml3176ErrorService::xoaBoDemGom`.
- Quyết định của plan: mốc so sánh lấy sau Task 1 (lệnh đo đã xả theo hồ sơ, nên số đo trước/sau cùng điều kiện bộ nhớ); `DuLieuHoSo` nạp tất cả cột (đúng trước, thu hẹp sau nếu cần); thứ tự loại theo `Xml3176CheckTypes::LOAI`.
