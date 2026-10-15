# Tăng tốc quét kiểm XML3176 — Bước A — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Giảm truy vấn và truy vấn chậm khi kiểm XML3176 (chủ yếu XML3/XML4) mà không đổi kết quả kiểm.

**Architecture:** Thêm bộ đệm tĩnh theo tiến trình TTL 600 giây (một lớp chung `Xml3176BoDem`) cho danh mục mã lỗi, danh mục nhỏ và tra DVKT/thuốc/VTYT; nạp dữ liệu theo hồ sơ một lần mỗi job; thêm 2 chỉ mục. Một lệnh đo/so sánh chỉ đọc (`xml3176:do-kiem`) được thêm **trước tiên** để lấy mốc kết quả trên prod, rồi so lại sau cùng.

**Tech Stack:** PHP 7.4, Laravel 5.5, PHPUnit 6, SQLite bộ nhớ trong test; prod MariaDB 10.4.27.

**Spec:** `docs/superpowers/specs/2026-10-15-xml3176-tang-toc-kiem-buoc-a-design.md`

## Global Constraints

- PHP 7.4 / Laravel 5.5; `setUp()` không `: void`; không type-hint tiêm service trên `Job::handle()`.
- Test: `DB_HOST=127.0.0.1 vendor/bin/phpunit <path>` (`.env` trỏ CSDL THẬT). Cấm `RefreshDatabase`/`DatabaseMigrations`. Không chạy migrate/seed/tinker/lệnh ghi lên CSDL thật.
- **Không đổi luật, câu chữ mô tả, mã lỗi, mức chặn.** Cùng dữ liệu → cùng tập lỗi `(xml, ma_lk, stt, error_code, description, critical_error)`.
- Bộ đệm: tĩnh (sống qua job trong cùng worker), TTL **600 giây**, trần **20.000** mục mỗi bộ đệm theo khoá (vượt → xoá bộ đệm đó), lỗi CSDL không được lưu thành kết quả, có hàm xoá dùng cho test.
- Mọi test dựng bảng bằng `Tests\Support\Xml3176RuleTestSupport::bootXml3176Sqlite` phải được xoá bộ đệm tự động (Task 2 thêm vào `bootXml3176Sqlite`).
- `Xml3176ErrorCatalog::createOrUpdate`: chỉ đặt `critical_error` khi **tạo mới**; dòng đã có không sửa `critical_error`.
- Chỉ mục trên `xml3176_xml3s`: thử `ALGORITHM=INPLACE, LOCK=NONE`, lỗi thì tạo cách thường; kiểm tồn tại trước khi tạo; migration chạy lại an toàn.
- Commit: `perf(xml3176): ...` / `feat(xml3176): ...` tiếng Việt không dấu, kết thúc `Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>`. Comment mã tiếng Việt không dấu.

## Review Focus

1. Mã DVKT có hậu tố (`_GT`, `_XX`) → đường tra theo mã gốc (`MaDvktMatcher::maGoc`, `Xml3176Xml3Checker.php:931-934`) vẫn cho đúng kết quả khi lấy từ bộ đệm (Task 4).
2. Cùng mã DVKT nhưng hồ sơ khác `ma_cskcb` hoặc khác `ngay_vao` → khoá bộ đệm phải gồm các yếu tố đó, không trả nhầm (Task 4).
3. Hai job liên tiếp của hai hồ sơ khác nhau trong cùng worker → dữ liệu theo hồ sơ (XML1, tập DVKT XML4, tập XML3/XML5 theo `ma_lk`) không rò sang hồ sơ sau (Task 5).
4. Admin đổi `critical_error` trên màn quản trị rồi worker ghi lỗi → giá trị admin không bị ghi đè (Task 2).
5. Danh mục trống/chưa nạp (ví dụ `dvkt_can_ma_may` rỗng → nhánh lùi theo nhóm) → bộ đệm giữ đúng nhánh (Task 3).

---

### Task 0: Mốc test

- [ ] `DB_HOST=127.0.0.1 vendor/bin/phpunit tests/Unit/Xml3176 2>&1 | tail -1` → ghi mốc (hiện 841 test, 4 lỗi có sẵn: `Xml3176Xml3CheckerRuleTest::{tg_th_yl_trung_tg_kq, thuc_hien_duoi_3_phut_nhom_1, bac_si_vua_ra_vua_thuc_hien}`, `Xml3176ErrorServiceGomTest::che_do_gom_bat_tat_dung`).

---

### Task 1: Lệnh đo và so sánh kết quả (chỉ đọc)

**Files:** Create `app/Console/Commands/Xml3176DoKiem.php`; Test `tests/Unit/Xml3176/TangToc/DoKiemLenhTest.php`

**Interfaces — Produces:** `php artisan xml3176:do-kiem {--ma-lk-tep= : tệp mỗi dòng một ma_lk} {--so=40 : lấy ngẫu nhiên nếu không có tệp} {--tu=2026-10-01} {--ghi-ma-lk= : lưu danh sách ma_lk đã dùng} {--xuat-loi= : tệp jsonl danh sách lỗi} {--xuat-do= : tệp thống kê}`.

Hành vi:
- Câu đầu tiên sau khi khởi động: `DB::statement('SET SESSION TRANSACTION READ ONLY')` trên kết nối mặc định — lệnh **không bao giờ ghi**.
- Lấy `Xml3176ErrorService` từ container, `batDauGom()` và **không** gọi `ketThucGom()`; đọc bộ đệm gom (thêm accessor công khai `boDemGom(): array` vào `Xml3176ErrorService`, không đổi hành vi khác).
- Với mỗi `ma_lk`: chạy đủ 12 checker theo `Xml3176CheckTypes::LOAI` giống `CheckXml3176TypeJob::handle` (cùng truy vấn nạp dòng, cùng thứ tự) rồi `Xml3176CompleteChecker::checkErrors($ma_lk)`; bắt ngoại lệ từng checker, ghi vào thống kê.
- `--xuat-loi`: mỗi lỗi một dòng JSON `{xml, ma_lk, stt, error_code, description, critical_error}`, sắp theo `(ma_lk, xml, stt, error_code, description)`.
- `--xuat-do`: theo XML: số dòng, số truy vấn, ms SQL (`DB::listen`), ms tổng; top 40 mẫu SQL (chuẩn hoá số/chuỗi) theo ms.
- `--ghi-ma-lk`: ghi danh sách `ma_lk` đã dùng (để chạy lại đúng tập).

- [ ] **Step 1: Test (đỏ)** — SQLite (bảng XML1/XML3 + `xml3176_error_catalogs` + `service_catalogs` tối thiểu; checker giả hoặc thật với dữ liệu 1 hồ sơ):
  - `xuat_loi_sap_xep_on_dinh` — chạy với `--ma-lk-tep` 2 hồ sơ, tệp jsonl có các dòng sắp đúng khoá; chạy 2 lần cho tệp giống hệt.
  - `khong_ghi_csdl` — số dòng `xml3176_error_results` và `xml3176_error_catalogs` không đổi sau lệnh (SQLite không hỗ trợ `SET SESSION TRANSACTION READ ONLY`: lệnh bỏ qua câu này khi driver là `sqlite`, test chứng minh không ghi bằng đếm dòng).
  - `ghi_ma_lk_dung_lai_duoc` — `--ghi-ma-lk` rồi chạy lại với `--ma-lk-tep` cùng tệp → cùng danh sách.
- [ ] **Step 2:** chạy test → FAIL.
- [ ] **Step 3:** cài lệnh + accessor `boDemGom()`.
- [ ] **Step 4:** test → PASS; `tests/Unit/Xml3176` chỉ 4 lỗi mốc.
- [ ] **Step 5:** commit `feat(xml3176): lenh do va so sanh ket qua kiem chi doc`.

> **Controller sau Task 1 (không phải implementer):** chạy lệnh trên prod (chỉ đọc) từ commit này: `--so=40 --ghi-ma-lk=mau40.txt --xuat-do=do_truoc.txt`, và `--so=200 --ghi-ma-lk=mau200.txt --xuat-loi=loi_truoc.jsonl` (ưu tiên hồ sơ nội trú/nhiều XML4: builder có thể thêm `--uu-tien-noi-tru`), lưu ở scratchpad. Đây là mốc cho Task 7.

---

### Task 2: Bộ đệm chung + danh mục mã lỗi

**Files:** Create `app/Services/Xml3176/Xml3176BoDem.php`; Modify `app/Services/Xml3176ErrorService.php`, `app/Models/BHYT/Xml3176ErrorCatalog.php`, `tests/Support/Xml3176RuleTestSupport.php`; Test `tests/Unit/Xml3176/TangToc/BoDemTest.php`, `tests/Unit/Xml3176/TangToc/DanhMucMaLoiDemTest.php`

**Interfaces — Produces:**
- `Xml3176BoDem::nho(string $ten, string $khoa, callable $tinh)` — trả giá trị đã nhớ hoặc gọi `$tinh()` rồi nhớ (kể cả `false`/`null`; ngoại lệ thì không nhớ, ném lại). TTL 600 giây tính từ lần tạo bộ đệm `$ten`; trần 20.000 mục/`$ten`.
- `Xml3176BoDem::bang(string $ten, callable $nap): array` — nạp cả bảng một lần cho `$ten` (TTL như trên).
- `Xml3176BoDem::dat(string $ten, string $khoa, $giaTri): void`; `Xml3176BoDem::xoaTatCa(): void`.
- `Xml3176ErrorService::getCriticalErrorStatus($code)` đọc từ `bang('ma_loi', …)` (map `error_code => ['critical_error'=>bool,'is_check'=>bool]`); mã chưa có → `true`. Đường `saveErrors` không gom kiểm `is_check` từ cùng bảng.
- `Xml3176ErrorCatalog::createOrUpdate(...)`: dòng mới → đặt `critical_error`; dòng đã có → chỉ cập nhật `error_name`/`description` như cũ, **không** sửa `critical_error`. Sau khi ghi, `Xml3176BoDem::dat('ma_loi', ...)` với giá trị thực trong CSDL.
- `Xml3176RuleTestSupport::bootXml3176Sqlite` gọi `Xml3176BoDem::xoaTatCa()` (cùng các `xoaBoNho()` sẵn có nếu cần).

- [ ] **Step 1: Test (đỏ)** — `BoDemTest`: `nho_goi_tinh_mot_lan`, `nho_ca_false_va_null`, `ngoai_le_khong_nho`, `het_ttl_tinh_lai` (giả lập thời gian: lớp có `static $bayGio` callable hoặc dùng `Carbon::setTestNow`), `vuot_tran_xoa_bo_dem`, `xoa_tat_ca`. `DanhMucMaLoiDemTest` (SQLite): `muc_chan_doc_tu_bo_dem_mot_truy_van` (đếm truy vấn bằng `DB::listen` khi gọi `getCriticalErrorStatus` 50 lần với 5 mã → ≤ 1 truy vấn), `ma_chua_co_tra_true`, `create_or_update_khong_de_muc_chan_admin` (dòng có `critical_error=false`; `createOrUpdate(..., true)` → vẫn `false`; `error_name` cập nhật), `create_or_update_tao_moi_dat_muc_chan`, `save_errors_khong_gom_ton_trong_is_check`.
- [ ] **Step 2:** FAIL. **Step 3:** cài. **Step 4:** `tests/Unit/Xml3176` chỉ 4 lỗi mốc (sửa test cũ nào phụ thuộc hành vi ghi đè `critical_error` — liệt kê trong báo cáo kèm lý do). **Step 5:** commit `perf(xml3176): bo dem danh muc ma loi, khong ghi de muc chan admin`.

---

### Task 3: Danh mục nhỏ qua bộ đệm

**Files:** Modify `app/Services/CommonValidationService.php`, `app/Services/Xml3176Xml3Checker.php` (`:370` ICD trực tiếp, `:433` mã máy), `app/Services/Xml3176CompleteChecker.php:474` (`tuyen_cmkt`); Test `tests/Unit/Xml3176/TangToc/DanhMucNhoDemTest.php`

**Interfaces:** Consumes `Xml3176BoDem::nho/bang`. Các phương thức `CommonValidationService` sau giữ nguyên chữ ký và kết quả, nhưng nhớ theo khoá (`$ten` riêng mỗi phương thức): `isMedicalOrganizationValid`, `isIcd10CategoryValid`, `isIcdYhctCategoryValid`, `isIcdYhctCategoryValue`, `isAdministrativeUnit*Valid` (6 hàm), `coDanhMucDvktCanMaMay` (khoá cố định), `isDvktCanMaMay`, `isJobCategoryValid`, `danhMucBenhPl1` (cập nhật ghi chú `:93-97` thành TTL 600 giây theo spec). Không đổi `isMedicalStaffValid` (Qd130 dùng).

- [ ] **Step 1: Test (đỏ)** — SQLite: với mỗi nhóm (ICD10, cơ sở KCB, hành chính tỉnh/xã/xã-trong-tỉnh, nghề nghiệp, DVKT cần mã máy, mã máy) — `goi_lap_lai_chi_mot_truy_van` (gọi 20 lần cùng khoá → 1 truy vấn) và `ket_qua_giong_truy_van_goc` (đúng/sai như trước, gồm khoá không tồn tại); `danh_muc_can_ma_may_rong_giu_nhanh_lui` (bảng rỗng → `coDanhMucDvktCanMaMay()` false, sau `xoaTatCa` + thêm dòng → true).
- [ ] Steps 2–4 như mẫu; `DvktCanMaMayTest`, `HanhChinhWardInProvinceTest` phải xanh. **Step 5:** commit `perf(xml3176): bo dem danh muc nho trong kiem XML3176`.

---

### Task 4: DVKT/thuốc/VTYT qua bộ đệm + chỉ mục `service_catalogs`

**Files:** Create `database/migrations/2026_10_15_110000_them_chi_muc_ma_dich_vu_service_catalogs.php`; Modify `app/Services/Xml3176Xml3Checker.php` (`:931,934,951,788`), `app/Services/Xml3176Xml4Checker.php:136`, `app/Services/Xml3176Xml2Checker.php` (`:274,285,290,307,315`); có thể tạo `app/Services/Xml3176/DanhMucTraCuu.php` gom các hàm tra; Test `tests/Unit/Xml3176/TangToc/DanhMucTraCuuDemTest.php`; cập nhật `tests/Unit/Xml3176/Xml3176DanhMucCoSoTest.php` nếu số chỗ gọi `cuaCoSo(` đổi (giữ ý: mọi tra vẫn lọc theo cơ sở).

**Interfaces:** Consumes `Xml3176BoDem::nho`. Khoá bộ đệm (spec §4.2):
- DVKT tồn tại: `(trim(ma_cskcb), ma_dich_vu)`; tập DVKT còn hiệu lực: `(trim(ma_cskcb), ma_dich_vu, ngay_vao Ymd)` — giá trị nhớ là mảng/Collection các dòng (dùng lại cho `isEmpty()`, `every()`, tên phê duyệt).
- Thuốc: `(ma_cskcb, ma_thuoc, ham_luong, so_dang_ky, tt_thau)` cho `first()`; các nhánh dự phòng theo đúng tập khoá của chúng.
- VTYT: `(ma_cskcb, ma_vat_tu)` → Collection.
- Migration: `Schema::table('service_catalogs')` thêm chỉ mục `service_catalogs_ma_dich_vu_ma_cskcb_index (ma_dich_vu, ma_cskcb)` nếu chưa có; `down()` xoá nếu có.

- [ ] **Step 1: Test (đỏ)** — `DanhMucTraCuuDemTest` (SQLite): `dvkt_ton_tai_nho_theo_co_so_va_ma` (hai `ma_cskcb` khác → hai kết quả khác, không lẫn), `dvkt_hieu_luc_nho_theo_ngay_vao` (hai ngày vào khác → kết quả khác), `ma_hau_to_tra_ma_goc_dung` (mã `X_GT` không có, mã gốc có → kết quả như cũ), `thuoc_va_vtyt_nho_theo_khoa`, `lap_lai_mot_truy_van`. `migration_chi_muc_chay_lai_an_toan` (chạy `up()` hai lần không lỗi).
- [ ] Steps 2–4: `Checker/Xml3176Xml3DanhMucDvktTest.php`, `Checker/Xml3176Xml3TenDichVuTest.php`, `Checker/Xml3176Xml2DanhMucThuocTest.php`, `Xml3TtThauKhopTest.php` phải xanh. **Step 5:** commit `perf(xml3176): bo dem tra DVKT thuoc VTYT va chi muc service_catalogs`.

---

### Task 5: Dữ liệu theo hồ sơ nạp một lần mỗi job

**Files:** Modify `app/Jobs/CheckXml3176TypeJob.php`, `app/Console/Commands/Xml3176DoKiem.php` (nạp dòng giống job), `app/Services/Xml3176Xml2Checker.php:68`, `app/Services/Xml3176Xml3Checker.php:182,857,1053`, `app/Services/Xml3176Xml4Checker.php:133,149`, `app/Services/Xml3176Xml5Checker.php:91,128`, `app/Services/Xml3176Xml11Checker.php`; Test `tests/Unit/Xml3176/TangToc/TheoHoSoTest.php`

**Interfaces:**
- Job (và lệnh đo): nạp dòng kèm quan hệ XML1 cho model có quan hệ `Xml3176Xml1` (XML2/3/4/5/11) — `->with('Xml3176Xml1')` trước `chunk`. Checker đổi `->load('Xml3176Xml1')` thành `->loadMissing(...)`.
- Checker giữ bộ nhớ **theo `ma_lk` hiện tại** (thuộc tính instance `$hoSoMaLk` + mảng dữ liệu; đổi `ma_lk` thì xoá và nạp lại):
  - XML4 `:149`: tập `ma_dich_vu` của XML3 cùng `ma_lk` có `ma_nhom ∈ config('xml3176.xml4.xml3_ma_nhom_require_ket_luan')` (nạp một truy vấn), thay `exists()` từng dòng. So khớp chuỗi theo đúng ngữ nghĩa MySQL cũ (không phân biệt hoa thường, bỏ khoảng trắng cuối) — chuẩn hoá khoá bằng `mb_strtoupper(rtrim(...))`.
  - XML3 `:1053`: tập dòng `ma_lk` có `ma_nhom ∈ $groups`, `ngay_th_yl`, `ngay_kq` không null (một truy vấn, sắp theo `id`), lọc `id > $data->id` trong PHP, giữ thứ tự như truy vấn cũ.
  - XML3 `:857`: dòng DVKT `ma_vat_tu = ''` theo `ma_lk` (một truy vấn), lọc `ma_dich_vu` trong PHP (cùng ngữ nghĩa so sánh).
  - XML5 `:128`: toàn bộ dòng XML5 của `ma_lk` (một truy vấn), lọc `id !=`, `stt <`, `thoi_diem_dbls` bắt đầu bằng `$ngay`, sắp `stt desc` trong PHP.
- Không đổi truy vấn chéo hồ sơ (`Xml3:543` trùng giường, `Xml3:1387` PTTT trùng BS).

- [ ] **Step 1: Test (đỏ)** — `TheoHoSoTest` (SQLite, checker thật + `FakeXml3176ErrorService`): với mỗi chỗ đổi, `ket_qua_giong_truy_van_cu` (dữ liệu có biên: `id` bằng/nhỏ/lớn, `stt` bằng, cùng ngày/khác ngày, hoa thường khác nhau, khoảng trắng cuối) — so mã lỗi + mô tả với kỳ vọng viết tay; `doi_ma_lk_khong_ro_du_lieu` (hai hồ sơ liên tiếp trên cùng checker → kết quả hồ sơ 2 không chứa dữ liệu hồ sơ 1); `xml1_nap_mot_lan` (đếm truy vấn `xml3176_xml1s` khi job xử lý 50 dòng XML3 → 1).
- [ ] Steps 2–4: `CheckXml3176TypeJobTest.php`, `Checker/*` phải xanh (chỉ 4 lỗi mốc). **Step 5:** commit `perf(xml3176): nap du lieu theo ho so mot lan moi job`.

---

### Task 6: Chỉ mục trùng giường trên `xml3176_xml3s`

**Files:** Create `database/migrations/2026_10_15_120000_them_chi_muc_trung_giuong_xml3176_xml3s.php`; Test `tests/Unit/Xml3176/TangToc/ChiMucTrungGiuongTest.php`

**Interfaces:** chỉ mục `xml3176_xml3s_giuong_khoa_th_yl_index (ma_giuong, ma_khoa, ngay_th_yl)`. `up()`: nếu chưa có — với driver `mysql` chạy `ALTER TABLE xml3176_xml3s ADD INDEX ... , ALGORITHM=INPLACE, LOCK=NONE`; bắt `QueryException` → chạy `ADD INDEX` thường; driver khác (SQLite test) dùng `Schema::table`. `down()`: xoá nếu có. Kiểm tồn tại: MySQL `SHOW INDEX FROM ... WHERE Key_name = ?`; SQLite `PRAGMA index_list`.

- [ ] **Step 1: Test (đỏ)** — `chay_lai_khong_loi` (SQLite: `up()` 2 lần, chỉ mục tồn tại 1), `down_xoa_chi_muc`.
- [ ] Steps 2–4. **Step 5:** commit `perf(xml3176): chi muc kiem trung giuong xml3s`.

> Controller (chỉ đọc, sau Task 6): không tạo chỉ mục trên prod được từ dev — đánh giá bằng EXPLAIN sau deploy (Task 7 ghi vào tài liệu).

---

### Task 7: Nghiệm thu tương đương + đo + tài liệu

**Files:** Create `docs/trien-khai-xml3176-tang-toc-kiem-buoc-a.md`.

- [ ] **Step 1:** `tests/Unit/Xml3176` → chỉ 4 lỗi mốc.
- [ ] **Step 2 (controller chạy, chỉ đọc trên prod):** từ HEAD, `xml3176:do-kiem --ma-lk-tep=mau200.txt --xuat-loi=loi_sau.jsonl` và `--ma-lk-tep=mau40.txt --xuat-do=do_sau.txt`; so `loi_truoc.jsonl` với `loi_sau.jsonl` (phải giống hệt; dòng khác → kiểm dữ liệu hồ sơ có đổi giữa hai lần chạy không, nếu không là lỗi → quay lại task tương ứng). Bảng so sánh truy vấn/dòng và ms SQL theo XML; top mẫu truy vấn còn lại → đầu vào Bước B.
- [ ] **Step 3:** tài liệu: 2 migration chỉ mục (thời gian bảo trì của lượt deploy có thể dài thêm vài phút); bộ đệm 600 giây (danh mục và mức chặn mới có hiệu lực tối đa sau 10 phút trong worker đang chạy; khởi động lại dịch vụ hàng đợi để có hiệu lực ngay); `createOrUpdate` không ghi đè mức chặn admin; cách dùng `xml3176:do-kiem`; sau deploy chạy EXPLAIN (chỉ đọc) hai truy vấn H1/H3 để xác nhận dùng chỉ mục mới.
- [ ] **Step 4:** commit `docs(xml3176): huong dan tang toc kiem buoc A`.

---

## Self-review (đã chạy)

- Spec §4.1 → Task 4 (service_catalogs), Task 6 (xml3s); §4.2 → Task 2 (mã lỗi, createOrUpdate), Task 3 (danh mục nhỏ), Task 4 (DVKT/thuốc/VTYT); §4.3 → Task 5; §5 → Task 1 (công cụ), Task 7 (so sánh, đo), test từng task; §3 quyết định → Global Constraints.
- Tên nhất quán: `Xml3176BoDem::{nho,bang,dat,xoaTatCa}`, `Xml3176ErrorService::boDemGom`, lệnh `xml3176:do-kiem`.
- Quyết định của plan: lệnh đo nằm trong repo (để chạy được trên cả commit mốc và commit cuối); mốc lấy ngay sau Task 1; chuẩn hoá so khớp chuỗi trong PHP theo ngữ nghĩa collation MySQL (`mb_strtoupper(rtrim())`).
