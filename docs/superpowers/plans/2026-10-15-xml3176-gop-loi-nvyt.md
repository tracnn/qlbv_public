# Gộp lỗi định danh NVYT (XML3176) — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Thay 34 mã lỗi định danh nhân viên y tế rải theo từng XML bằng 3 mã `XMLComplete_NVYT_*` gom theo giá trị trong hồ sơ, tra danh mục NVYT trong bộ nhớ, kèm lệnh kiểm lại hàng loạt.

**Architecture:** `NvytLookup` (nạp `medical_staffs` vào bộ nhớ, TTL 600s) + `NvytRule` (luật thuần: phân loại giá trị theo vai trò, gom lỗi theo hồ sơ) + `NvytThuThap` (đọc các trường NVYT của mọi XML theo `ma_lk`, mang điều kiện "bắt buộc" của checker cũ). `Xml3176CompleteChecker` gọi chúng; các block cũ trong 12 checker bị gỡ; migration tắt mã cũ, nạp mã mới; lệnh `xml3176:kiem-lai-nvyt` chuyển hồ sơ cũ.

**Tech Stack:** PHP 7.4, Laravel 5.5, PHPUnit 6, SQLite bộ nhớ trong test.

**Spec:** `docs/superpowers/specs/2026-10-15-xml3176-gop-loi-nvyt-design.md`

## Global Constraints

- PHP 7.4 / Laravel 5.5: `setUp()` không `: void`; không type-hint tiêm service trên `Job::handle()`.
- Test: `DB_HOST=127.0.0.1 vendor/bin/phpunit <path>` (`.env` trỏ CSDL THẬT). Cấm `RefreshDatabase`/`DatabaseMigrations`; bảng test dựng bằng `Tests\Support\Xml3176RuleTestSupport::bootXml3176Sqlite([...])`. Không chạy migrate/seed/tinker/lệnh ghi lên CSDL thật.
- Ba mã mới (`xml = 'XMLComplete'`, nạp bằng migration `firstOrCreate`, `is_check = true`):
  - `XMLComplete_NVYT_THIEU` — `Thiếu mã nhân viên y tế` — `critical_error = true`
  - `XMLComplete_NVYT_KHONG_CO_TRONG_DM` — `Mã nhân viên y tế không có trong danh mục NVYT` — `critical_error = false`
  - `XMLComplete_NVYT_SAI_DINH_DANG` — `Mã nhân viên y tế sai định danh` — `critical_error = false`
- Vai trò: `TTDV` (thủ trưởng đơn vị) hợp lệ khi khớp `macchn` | `so_dinh_danh` | `ma_bhxh`; vai trò chuyên môn `BS_CHI_DINH`, `NGUOI_THUC_HIEN`, `BS_DOC_KQ` hợp lệ **chỉ** khi khớp `macchn`.
- Dạng CCHN/GPHN: `/^\d{4,7}\/[A-ZĐÐ]{2,4}-(CCHN|GPHN)$/iu` (Đ U+0110, Ð U+00D0). Dạng số BHXH/CCCD: `/^\d{10,12}$/`.
- So khớp: khoá = `mb_strtoupper(trim($v), 'UTF-8')`; mô tả in giá trị đã trim (giữ hoa thường gốc).
- Một lỗi = một hồ sơ × một giá trị đã chuẩn hoá (THIEU: một hồ sơ × một vai trò). Thứ tự vai trò trong mô tả: TTDV, BS chỉ định, người thực hiện, BS đọc KQ; XML số tăng dần.
- Danh mục NVYT trống/không đọc được → không báo `KHONG_CO_TRONG_DM`, `SAI_DINH_DANG`; vẫn báo `THIEU`; lỗi đọc ghi `Log::error`, không ném.
- Giữ nguyên `XML3_SAME_DOCTOR_ORDER_AND_EXECUTE`. Không lọc `tu_ngay/den_ngay/ma_cskcb`.
- Commit: `feat(xml3176): ...` tiếng Việt không dấu, kết thúc `Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>`. Comment mã tiếng Việt không dấu; chuỗi hiển thị có dấu.

34 mã cũ (hằng `NvytRule::MA_CU`, dùng ở migration và lệnh — Task 4 đối chiếu với mã thật bằng grep, lệch thì báo):

```
XML1_INFO_ERROR_MA_TTDV XML1_INVALID_MEDICAL_STAFF_MA_TTDV
XML2_MISSING_MEDICAL_STAFF_CODE XML2_INVALID_MEDICAL_STAFF_CODE
XML3_INFO_ERROR_GROUP_CODE_NGUOI_THUC_HIEN XML3_INFO_ERROR_NGUOI_THUC_HIEN_NOT_FOUND XML3_INFO_ERROR_MA_BAC_SI XML3_INFO_ERROR_MA_BAC_SI_NOT_FOUND
XML4_INFO_ERROR_MA_BS_DOC_KQ XML4_INFO_ERROR_MA_BS_DOC_KQ_NOT_FOUND
XML5_INFO_ERROR_NGUOI_THUC_HIEN XML5_INFO_ERROR_NGUOI_THUC_HIEN_NOT_FOUND
XML7_INFO_ERROR_MA_TTDV XML7_INFO_ERROR_MA_TTDV_NOT_FOUND XML7_INFO_ERROR_MA_BS XML7_INFO_ERROR_MA_BS_NOT_FOUND
XML8_INFO_ERROR_MA_TTDV XML8_INFO_ERROR_MA_TTDV_NOT_FOUND
XML9_INFO_ERROR_MA_TTDV XML9_INVALID_MEDICAL_STAFF_MA_TTDV
XML10_INFO_ERROR_MA_TTDV XML10_INFO_ERROR_MA_TTDV_NOT_FOUND
XML11_INFO_ERROR_MA_BS XML11_INVALID_MEDICAL_STAFF_MA_BS XML11_INFO_ERROR_MA_TTDV XML11_INVALID_MEDICAL_STAFF_MA_TTDV
XML13_INFO_ERROR_MA_BAC_SI XML13_INVALID_MEDICAL_STAFF_MA_BAC_SI XML13_INFO_ERROR_MA_TTDV XML13_INVALID_MEDICAL_STAFF_MA_TTDV
XML14_INFO_ERROR_MA_BAC_SI XML14_INVALID_MEDICAL_STAFF_MA_BAC_SI XML14_INFO_ERROR_MA_TTDV XML14_INVALID_MEDICAL_STAFF_MA_TTDV
```

## Review Focus

1. Giá trị có khoảng trắng hai đầu hoặc viết thường (`' 027588/hno-cchn '`) → hợp lệ nếu danh mục có `027588/HNO-CCHN` (Task 1).
2. `nguoi_thuc_hien`/`ma_bac_si` XML3 dạng `'A; B;'` (khoảng trắng, `;` cuối) → tách, trim, bỏ mảnh rỗng; mảnh rỗng không sinh THIEU (Task 3).
3. Danh mục có `ma_bhxh`/`so_dinh_danh`/`macchn` rỗng (273 dòng `ma_bhxh` rỗng trên prod) → không tạo khoá `''`, giá trị rỗng không bao giờ "khớp" (Task 1).
4. Cùng một giá trị ở nhiều vai trò và nhiều XML → đúng **một** lỗi, liệt kê vai trò theo thứ tự cố định và số dòng mỗi chỗ (Task 2).
5. Hồ sơ rất nhiều dòng (XML4 hàng trăm dòng tài khoản `vietrad`) → mô tả cắt gọn danh sách tên (tối đa 3 + "và N … khác"), không phình (Task 2).

---

### Task 0: Mốc test

- [ ] Run `DB_HOST=127.0.0.1 vendor/bin/phpunit tests/Unit/Xml3176 2>&1 | tail -1`. Mốc hiện tại: 795 test, 4 lỗi có sẵn (`Xml3176Xml3CheckerRuleTest::tg_th_yl_trung_tg_kq`, `::thuc_hien_duoi_3_phut_nhom_1`, `::bac_si_vua_ra_vua_thuc_hien`, `Xml3176ErrorServiceGomTest::che_do_gom_bat_tat_dung`). Ghi lại; lỗi khác ngoài 4 lỗi này sau mỗi task là hồi quy.

---

### Task 1: Bảng tra NVYT và phân loại một giá trị

**Files:**
- Create: `app/Services/Xml3176/NvytLookup.php`, `app/Services/Xml3176/NvytRule.php`
- Test: `tests/Unit/Xml3176/Nvyt/NvytPhanLoaiTest.php` (thuần, không CSDL — dùng `NvytLookup::tuDong`)

**Interfaces — Produces:**
- `NvytLookup::bang(): array` — nạp `medical_staffs` (`macchn, ma_bhxh, so_dinh_danh, ho_ten`), nhớ tĩnh TTL 600s, lỗi đọc → `Log::error` và trả bảng rỗng (không nhớ bảng rỗng do lỗi). `NvytLookup::tuDong(iterable $dong): array` (mỗi phần tử object/mảng có 4 cột trên). `NvytLookup::xoaBoNho(): void`. `NvytLookup::khoa($v): string` = `mb_strtoupper(trim((string)$v), 'UTF-8')`.
- Cấu trúc bảng: `['co_du_lieu' => bool, 'macchn' => [KHOA => nguoi], 'ma_bhxh' => [...], 'so_dinh_danh' => [...], 'so_cchn' => [phanSoKhongSo0Dau => [macchn, ...]]]`; `nguoi = ['ho_ten' => string, 'macchn' => string]`. Bỏ giá trị rỗng khi dựng chỉ mục.
- `NvytRule` hằng vai trò `TTDV`, `BS_CHI_DINH`, `NGUOI_THUC_HIEN`, `BS_DOC_KQ`; hằng `MA_CU` (34 mã ở Global Constraints); hằng `TEN = ['NVYT_THIEU' => ..., 'NVYT_KHONG_CO_TRONG_DM' => ..., 'NVYT_SAI_DINH_DANG' => ...]` (tên ở Global Constraints).
- `NvytRule::__construct(array $bang)`; `NvytRule::phanLoai(string $vaiTro, string $giaTri): ?array` — giá trị đã trim, không rỗng; trả `null` nếu hợp lệ, ngược lại `['key' => 'NVYT_KHONG_CO_TRONG_DM'|'NVYT_SAI_DINH_DANG', 'ly_do' => string]`. `co_du_lieu = false` → luôn `null`.

`ly_do` (đoạn sau giá trị trong mô tả; Task 2 ghép) theo spec mục 4:
- TTDV đúng dạng không khớp: `không có trong danh mục NVYT (đã tra theo CCHN, số định danh, mã BHXH)`
- chuyên môn, dạng CCHN không khớp: `không có trong danh mục NVYT`
- chuyên môn khớp `so_dinh_danh`: `là số định danh của {HO_TEN} — vai trò chuyên môn phải dùng mã CCHN {macchn}`; khớp `ma_bhxh`: `là mã BHXH của {HO_TEN} — …` ; người không có `macchn`: thay đuôi bằng `— người này chưa có CCHN trong danh mục NVYT`
- chuyên môn 10–12 số không khớp ai: `là mã BHXH/số định danh, không phải mã CCHN — vai trò chuyên môn phải dùng CCHN`
- có chữ cái: `là tài khoản/tên đăng nhập, không phải mã CCHN`
- toàn số < 10 chữ số: `số CCHN thiếu phần đuôi` + nếu `so_cchn[ltrim($v,'0')]` có đúng 1 mã: `, có thể là {macchn}`
- khác: `định dạng không hợp lệ`

- [ ] **Step 1: Test (đỏ)** — `NvytPhanLoaiTest` với bảng `tuDong` gồm: `{macchn:'027588/HNO-CCHN', ma_bhxh:'0101234567', so_dinh_danh:'042068003011', ho_ten:'NGUYỄN VĂN A'}`, `{macchn:'0010344/BYT-CCHN', ma_bhxh:'', so_dinh_danh:'', ho_ten:'TRẦN B'}`, `{macchn:'', ma_bhxh:'0109999999', so_dinh_danh:'001188031198', ho_ten:'LÊ C'}`, `{macchn:'008650/NĐ-CCHN', ...}`. Các test (tên snake_case, assert `key` và `assertContains` trên `ly_do`):
  - `hop_le_khop_cchn_khong_phan_biet_hoa_thuong_va_trim` — `phanLoai(BS_CHI_DINH, ' 027588/hno-cchn ')` === null.
  - `ttdv_hop_le_theo_cchn_dinh_danh_bhxh` — ba giá trị `'027588/HNO-CCHN'`, `'042068003011'`, `'0101234567'` đều null với `TTDV`.
  - `chuyen_mon_gui_dinh_danh_bao_sai_va_goi_y_cchn` — `NGUOI_THUC_HIEN`, `'042068003011'` → SAI_DINH_DANG, chứa `số định danh của NGUYỄN VĂN A` và `027588/HNO-CCHN`.
  - `chuyen_mon_gui_bhxh_nguoi_khong_co_cchn` — `BS_DOC_KQ`, `'0109999999'` → SAI_DINH_DANG, chứa `chưa có CCHN trong danh mục NVYT`.
  - `cchn_dung_dang_khong_co` — `BS_CHI_DINH`, `'040535/BYT-CCHN'` → KHONG_CO_TRONG_DM; với `TTDV` chứa `đã tra theo CCHN, số định danh, mã BHXH`.
  - `ttdv_12_so_khong_khop_la_khong_co_trong_dm` — `TTDV`, `'001188031199'` → KHONG_CO_TRONG_DM.
  - `chuyen_mon_12_so_khong_khop_la_sai_dinh_danh` — `NGUOI_THUC_HIEN`, `'001188031199'` → SAI_DINH_DANG chứa `không phải mã CCHN`.
  - `tai_khoan_may` — `BS_DOC_KQ`, `'vietrad'` → SAI_DINH_DANG chứa `tài khoản/tên đăng nhập`.
  - `so_thieu_duoi_goi_y_duy_nhat` — `BS_CHI_DINH`, `'10344'` → chứa `có thể là 0010344/BYT-CCHN`; thêm người thứ hai cùng phần số `10344` (`010344/HNO-CCHN`) → không chứa `có thể là`.
  - `chu_d_hai_kieu_dung_dang` — `'008650/NĐ-CCHN'` null; `'008651/NÐ-CCHN'` (U+00D0) → KHONG_CO_TRONG_DM (đúng dạng).
  - `gia_tri_rong_trong_danh_muc_khong_tao_khoa` — `tuDong` có dòng `ma_bhxh = ''`; `array_key_exists('', $bang['ma_bhxh'])` false.
  - `danh_muc_trong_khong_bao` — `new NvytRule(NvytLookup::tuDong([]))->phanLoai(BS_DOC_KQ, 'vietrad')` === null.
- [ ] **Step 2:** `DB_HOST=127.0.0.1 vendor/bin/phpunit tests/Unit/Xml3176/Nvyt/NvytPhanLoaiTest.php` → FAIL (lớp chưa có).
- [ ] **Step 3:** Cài đặt `NvytLookup` (mẫu `app/Services/Xml3176/VatPhamKemDvktLookup.php`, kể cả `Schema::hasTable` guard) và `NvytRule::phanLoai` theo thứ tự xét ở spec mục 4.
- [ ] **Step 4:** Chạy lại → PASS.
- [ ] **Step 5:** Commit `feat(xml3176): bang tra NVYT va phan loai dinh danh theo vai tro`.

---

### Task 2: Gom lỗi một hồ sơ

**Files:**
- Modify: `app/Services/Xml3176/NvytRule.php`
- Test: `tests/Unit/Xml3176/Nvyt/NvytGomLoiTest.php` (thuần)

**Interfaces:**
- Consumes: Task 1.
- Produces: `NvytRule::kiem(array $o): array` — `$o` là list ô `['vai_tro' => string, 'xml' => int, 'gia_tri' => ?string, 'bat_buoc' => bool, 'nhan' => string]` (`nhan` = tên DVKT/dịch vụ của dòng, có thể `''`); `gia_tri` đã là MỘT mã (tách `;` ở Task 3). Trả list `['key' => string, 'ten' => string, 'mo_ta' => string]`.

Luật:
- Ô rỗng (`trim === ''`) và `bat_buoc` → gom vào THIEU theo vai trò; ô rỗng không bắt buộc → bỏ.
- Ô không rỗng → `phanLoai`; lỗi gom theo `khoa(gia_tri)`: một lỗi/khoá, `key` theo kết quả phân loại (cùng khoá luôn cùng kết quả với cùng vai trò; nếu một khoá cho kết quả khác nhau theo vai trò — vd hợp lệ ở TTDV nhưng sai ở chuyên môn — chỉ báo các vai trò có lỗi, `ly_do` lấy của vai trò đầu tiên theo thứ tự cố định).
- Mô tả không rỗng: `{giaTri} {ly_do} — {vaiTro} XML{n} ({k} dòng[: nhan1, nhan2, nhan3 và N mục khác]), …` — nhãn vai trò: `thủ trưởng đơn vị`, `BS chỉ định`, `người thực hiện`, `BS đọc KQ`; liệt kê `nhan` (khác rỗng, không trùng) chỉ khi vai trò là `NGUOI_THUC_HIEN` hoặc `BS_DOC_KQ`.
- Mô tả THIEU (một lỗi/vai trò): `Thiếu mã {nhãn vai trò} ở {k} dòng: XML{n} ({k1} dòng[: nhan…]), …` — với TTDV: `Thiếu mã thủ trưởng đơn vị ở XML1, XML14.`
- Danh sách nhãn tối đa 3 + `và N mục khác`.

- [ ] **Step 1: Test (đỏ)** — dùng bảng Task 1:
  - `mot_gia_tri_nhieu_vai_tro_mot_loi` — ô: `('040535/BYT-CCHN', NGUOI_THUC_HIEN, XML3)` ×5 với nhãn khác nhau, `(BS_CHI_DINH, XML2)` ×2, `(NGUOI_THUC_HIEN, XML5)` ×1 → đúng 1 lỗi KHONG_CO_TRONG_DM; `mo_ta` chứa `BS chỉ định XML2 (2 dòng)`, `người thực hiện XML3 (5 dòng`, `người thực hiện XML5 (1 dòng`; vị trí `BS chỉ định` đứng trước `người thực hiện`; chứa `và 2 mục khác`.
  - `hoa_thuong_khac_nhau_van_mot_loi` — `'vietrad'` và `'VIETRAD '` ở BS_DOC_KQ → 1 lỗi SAI_DINH_DANG, `(2 dòng`.
  - `thieu_gom_theo_vai_tro` — TTDV rỗng ở XML1 và XML14 (bat_buoc) → 1 lỗi NVYT_THIEU, `mo_ta` chứa `thủ trưởng đơn vị` và `XML1` trước `XML14`; ô rỗng `bat_buoc = false` → không lỗi.
  - `hop_le_khong_loi` — `'027588/HNO-CCHN'` ở mọi vai trò → `[]`.
  - `ttdv_hop_le_chuyen_mon_sai_cung_gia_tri` — `'042068003011'` ở TTDV XML1 và NGUOI_THUC_HIEN XML3 → 1 lỗi SAI_DINH_DANG chỉ nêu `người thực hiện XML3`, không chứa `thủ trưởng`.
  - `danh_muc_trong_chi_con_thieu` — bảng rỗng; ô `'vietrad'` + ô TTDV rỗng bắt buộc → chỉ 1 lỗi NVYT_THIEU.
  - `ten_loi_theo_hang_TEN` — `ten` của mỗi lỗi === `NvytRule::TEN[key]`.
- [ ] **Step 2:** Chạy `tests/Unit/Xml3176/Nvyt/NvytGomLoiTest.php` → FAIL.
- [ ] **Step 3:** Cài `kiem()` như luật trên.
- [ ] **Step 4:** Chạy `tests/Unit/Xml3176/Nvyt` → PASS.
- [ ] **Step 5:** Commit `feat(xml3176): gom loi NVYT theo gia tri trong ho so`.

---

### Task 3: Thu thập trường NVYT, nối vào XMLComplete, mã lỗi

**Files:**
- Create: `app/Services/Xml3176/NvytThuThap.php`; `database/migrations/2026_10_15_100000_gop_ma_loi_nvyt_xml3176.php`
- Modify: `app/Services/Xml3176CompleteChecker.php` (thêm `kiemNvyt`, gọi trong `checkErrors`), `config/xml3176_suggestions.php`
- Test: `tests/Unit/Xml3176/Nvyt/NvytThuThapTest.php`, `tests/Unit/Xml3176/Nvyt/NvytMaLoiTest.php`

**Interfaces:**
- Consumes: Task 1–2.
- Produces:
  - `NvytThuThap::tuHoSo(string $ma_lk): array` — list ô cho `NvytRule::kiem` (đọc mỗi bảng XML một truy vấn theo `ma_lk`, chỉ cột cần).
  - `Xml3176CompleteChecker::kiemNvyt(string $ma_lk): \Illuminate\Support\Collection` (public) — object lỗi `error_code` (`XMLComplete_` + key), `error_name`, `critical_error` (qua `getCriticalErrorStatus`), `description`; mẫu `checkVatPhamKemDvkt` (`Xml3176CompleteChecker.php:1033`). `checkErrors()` merge kết quả này.
  - Migration: `firstOrCreate` 3 mã mới; `Xml3176ErrorCatalog::whereIn('error_code', NvytRule::MA_CU)->update(['is_check' => false])`; `down()` bật lại `is_check = true` cho `MA_CU` (không xoá mã mới).

Bảng trường (spec mục 3). **Điều kiện `bat_buoc` của từng ô phải sao đúng block cũ** — đọc block tương ứng trước khi viết (block cũ còn nguyên tới Task 4):

| XML (bảng) | Trường | Vai trò | Block cũ |
|---|---|---|---|
| 1 `xml3176_xml1s` | `ma_ttdv` | TTDV | `Xml3176Xml1Checker.php:~506–515` |
| 2 `xml3176_xml2s` | `ma_bac_si` | BS_CHI_DINH | `Xml3176Xml2Checker.php:~205–214` |
| 3 `xml3176_xml3s` | `ma_bac_si` (tách `;`) | BS_CHI_DINH | `Xml3176Xml3Checker.php:~328–345` |
| 3 | `nguoi_thuc_hien` (tách `;`) | NGUOI_THUC_HIEN — bắt buộc khi `ma_nhom ∈ config('xml3176.group_code_with_executor')` | `Xml3176Xml3Checker.php:~281–300` |
| 4 `xml3176_xml4s` | `ma_bs_doc_kq` | BS_DOC_KQ | `Xml3176Xml4Checker.php:~236–245` |
| 5 `xml3176_xml5s` | `nguoi_thuc_hien` | NGUOI_THUC_HIEN | `Xml3176Xml5Checker.php:~111–120` |
| 7 | `ma_ttdv`; `ma_bs` | TTDV; BS_CHI_DINH | `Xml3176Xml7Checker.php:~76–105` |
| 8 | `ma_ttdv` | TTDV | `Xml3176Xml8Checker.php:~104–113` |
| 9 | `ma_ttdv` | TTDV | `Xml3176Xml9Checker.php:~333–342` |
| 10 | `ma_ttdv` | TTDV | `Xml3176Xml10Checker.php:~142–151` |
| 11 | `ma_bs`; `ma_ttdv` | BS_CHI_DINH; TTDV | `Xml3176Xml11Checker.php:~284–313` |
| 13 | `ma_bac_si`; `ma_ttdv` | BS_CHI_DINH; TTDV | `Xml3176Xml13Checker.php:~343–372` |
| 14 | `ma_bac_si`; `ma_ttdv` | BS_CHI_DINH; TTDV | `Xml3176Xml14Checker.php:~168–197` |

`nhan`: XML3 `ten_dich_vu` (dòng vật tư: `ten_vat_tu`), XML4 tên dịch vụ của dòng nếu bảng có cột (`ten_chi_so`/`ma_dich_vu` — chọn cột hiển thị được), XML5 `''`, XML2 `ten_thuoc`, còn lại `''`. Nếu block cũ chỉ chạy cho một tập dòng (vd chỉ dòng có điều kiện riêng), giữ đúng tập đó.

- [ ] **Step 1: Test (đỏ)** — `NvytThuThapTest` (SQLite, migration các bảng `xml3176_xml1s…xml14s` cần + `medical_staffs`: `2024_06_13_094552_create_nhan_vien_y_te_table.php`, `2026_04_09_100000_add_so_dinh_danh_to_medical_staffs_table.php`, `2026_04_09_100001_make_ma_bhxh_nullable_in_medical_staffs_table.php`; `NvytLookup::xoaBoNho()` trong `setUp`):
  - `xml3_tach_cham_phay_trim_bo_manh_rong` — `nguoi_thuc_hien = 'A; B;'` → 2 ô `A`, `B`, không ô rỗng.
  - `xml3_nguoi_thuc_hien_bat_buoc_theo_nhom` — `ma_nhom` trong cấu hình + rỗng → ô `bat_buoc = true`; nhóm ngoài cấu hình + rỗng → `bat_buoc = false`.
  - `moi_xml_mot_o_dung_vai_tro` — mỗi bảng 1 dòng → ô đúng `vai_tro`, `xml`.
  - `kiem_nvyt_ghi_ma_xmlcomplete_va_muc_chan` — qua `kiemNvyt`: hồ sơ thiếu `ma_ttdv` XML1 + `ma_bs_doc_kq = 'vietrad'` → 2 lỗi `XMLComplete_NVYT_THIEU` (`critical_error` true), `XMLComplete_NVYT_SAI_DINH_DANG` (false) — chạy migration Task 3 để có danh mục mã.
- [ ] `NvytMaLoiTest`: `ba_ma_moi_dung_muc_chan` (3 mã, `critical_error` đúng, `is_check` true); `ma_cu_bi_tat` (tạo trước 2 mã trong `MA_CU` với `is_check = true`, chạy migration → false; mã khác không đổi); `ma_cu_du_34` (`count(NvytRule::MA_CU) === 34`, không trùng).
- [ ] **Step 2:** Chạy 2 test → FAIL.
- [ ] **Step 3:** Cài `NvytThuThap`, `kiemNvyt` + gọi trong `checkErrors`, migration; `config/xml3176_suggestions.php`: bỏ khoá `XML3_INFO_ERROR_MA_BAC_SI_NOT_FOUND`, thêm `XMLComplete_NVYT_THIEU` → `Bổ sung mã nhân viên y tế trên HIS (thủ trưởng đơn vị, bác sĩ, người thực hiện, BS đọc kết quả).`, `XMLComplete_NVYT_KHONG_CO_TRONG_DM` → `Phòng TCCB đăng ký NVYT lên cổng BHXH và cập nhật danh mục NVYT vào phần mềm.`, `XMLComplete_NVYT_SAI_DINH_DANG` → `CNTT/hãng PACS-LIS ánh xạ tài khoản, CCCD, mã BHXH sang mã CCHN khi xuất XML.`
- [ ] **Step 4:** Chạy `tests/Unit/Xml3176/Nvyt` và `tests/Unit/Xml3176/Checker` → xanh (trừ mốc).
- [ ] **Step 5:** Commit `feat(xml3176): kiem NVYT o muc ho so va nap 3 ma loi moi`.

---

### Task 4: Gỡ block cũ khỏi 12 checker

**Files:**
- Modify: `app/Services/Xml3176Xml{1,2,3,4,5,7,8,9,10,11,13,14}Checker.php`
- Test: `tests/Unit/Xml3176/Nvyt/NvytGoMaCuTest.php`

**Interfaces:** Consumes `NvytRule::MA_CU`.

- [ ] **Step 1: Test (đỏ)** — `khong_checker_nao_con_sinh_ma_cu`: với mỗi tệp checker trên, đọc nguồn; với mỗi mã trong `MA_CU` thuộc XML đó, phần đuôi sau `XMLn_` không còn xuất hiện trong `generateErrorCode('…')`. `checker_khong_con_goi_isMedicalStaffValid`: 12 tệp không chứa `isMedicalStaffValid`. `same_doctor_van_con`: `Xml3176Xml3Checker.php` vẫn chứa `SAME_DOCTOR_ORDER_AND_EXECUTE`.
- [ ] **Step 2:** Chạy → FAIL.
- [ ] **Step 3:** Gỡ các block (cặp trống/không có trong danh mục) theo bảng Task 3; gỡ biến/`use`/inject `CommonValidationService` nếu không còn dùng trong tệp đó. Không xoá `CommonValidationService::isMedicalStaffValid` (còn nơi khác tham chiếu). Trước khi gỡ, `grep -n "generateErrorCode" ` từng tệp đối chiếu với `MA_CU`; mã nào lệch → dừng, báo NEEDS_CONTEXT.
- [ ] **Step 4:** Chạy `tests/Unit/Xml3176` → chỉ 4 lỗi mốc; test cũ nào assert mã cũ thì chuyển sang assert qua `kiemNvyt` hoặc bỏ nếu trùng phạm vi Task 3 (ghi rõ trong báo cáo).
- [ ] **Step 5:** Commit `feat(xml3176): go kiem NVYT cu khoi cac checker theo XML`.

---

### Task 5: Lệnh kiểm lại hàng loạt

**Files:**
- Create: `app/Console/Commands/Xml3176KiemLaiNvyt.php` (đăng ký trong `app/Console/Kernel.php` nếu dự án đăng ký tay)
- Test: `tests/Unit/Xml3176/Nvyt/NvytKiemLaiLenhTest.php`

**Interfaces:** Consumes `Xml3176CompleteChecker::kiemNvyt`, `NvytRule::MA_CU`, `Xml3176ErrorService::saveErrors`.

Signature: `xml3176:kiem-lai-nvyt {--tu= : Y-m-d, theo xml3176_xml1s.created_at} {--den=} {--thu : chi dem} {--lo=500}`.
Mỗi hồ sơ, trong `DB::transaction`: ghi nhận trước có lỗi `critical_error = true` không; xoá `xml3176_error_results` của `ma_lk` có `error_code ∈ MA_CU ∪ {3 mã mới}`; `saveErrors('XMLComplete', ma_lk, xml1.stt, kiemNvyt(ma_lk))`; ghi nhận sau. Duyệt theo `ma_lk` tăng dần, `chunkById`/lô `--lo`. In tiến độ mỗi lô; cuối: số HS, số dòng mã cũ đã xoá, số lỗi mới theo mã, số HS hết lỗi chặn; ghi `ma_lk` hết chặn ra `storage/logs/kiem-lai-nvyt-{YmdHis}.txt`. `--thu`: không ghi, chỉ in số đếm (lỗi mới tính bằng `kiemNvyt`). Không đụng `export_error` hay luồng xuất.

- [ ] **Step 1: Test (đỏ)** — SQLite với `xml3176_xml1s`, `xml3176_xml4s`, `xml3176_error_results`, `xml3176_error_catalogs`, `medical_staffs` + migration Task 3:
  - `thu_khong_ghi` — 1 HS có dòng lỗi `XML4_INFO_ERROR_MA_BS_DOC_KQ_NOT_FOUND` ×3 → `--thu` xong số dòng `xml3176_error_results` không đổi; output chứa số dòng mã cũ `3`.
  - `chay_that_thay_ma_cu_bang_ma_moi_giu_loi_khac` — có thêm dòng lỗi `XML3_NGAY_TH_YL_EQUALS_NGAY_KQ` → sau lệnh: không còn mã cũ, có 1 `XMLComplete_NVYT_SAI_DINH_DANG`, lỗi `XML3_NGAY_…` còn nguyên.
  - `chay_lai_cung_ket_qua` — chạy 2 lần → số dòng như lần 1.
  - `het_chan_ghi_tep` — HS có `XML1_INFO_ERROR_MA_TTDV` (critical true) nhưng `ma_ttdv` nay đã có giá trị hợp lệ → sau lệnh không còn lỗi chặn; tệp `storage/logs/kiem-lai-nvyt-*.txt` chứa `ma_lk` (dọn tệp trong `tearDown`).
  - `loc_theo_ngay` — 2 HS khác `created_at`; `--tu` chỉ xử lý HS sau mốc.
- [ ] **Step 2:** Chạy → FAIL.
- [ ] **Step 3:** Cài lệnh.
- [ ] **Step 4:** Chạy `tests/Unit/Xml3176/Nvyt` → PASS.
- [ ] **Step 5:** Commit `feat(xml3176): lenh kiem lai NVYT hang loat`.

---

### Task 6: Hồi quy, nghiệm thu chỉ đọc, tài liệu

**Files:** Modify `docs/trien-khai-xml3176-vat-pham-kem-dvkt.md` hoặc tạo `docs/trien-khai-xml3176-gop-loi-nvyt.md` (một trang).

- [ ] **Step 1:** `DB_HOST=127.0.0.1 vendor/bin/phpunit tests/Unit/Xml3176` → chỉ 4 lỗi mốc.
- [ ] **Step 2: Nghiệm thu chỉ đọc** — script trong scratchpad (ngoài repo), dòng đầu `DB::statement('SET SESSION TRANSACTION READ ONLY');`; với HS nạp từ 2026-09-01: chạy `NvytThuThap::tuHoSo` + `NvytRule::kiem` (bảng `NvytLookup::bang()`), KHÔNG gọi `saveErrors`/lệnh. In: số lỗi theo 3 mã, số HS có lỗi NVYT, so với số dòng mã cũ hiện có (kỳ vọng ~389 nghìn dòng cũ → cỡ vài chục nghìn lỗi mới), số HS hiện bị chặn bởi mã cũ nhưng không bị chặn theo cách mới, 5 ví dụ mỗi mã (`ma_lk` + `mo_ta`), thời gian trung bình/HS. Lệch kỳ vọng → báo, không chỉnh luật.
- [ ] **Step 3: Tài liệu** — trang triển khai: 3 mã mới và mức chặn; mã cũ bị tắt; sau khi `update.bat` chạy: `php artisan xml3176:kiem-lai-nvyt --tu=2026-09-01 --thu` rồi chạy thật; danh sách HS hết chặn ở `storage/logs/kiem-lai-nvyt-*.txt` dùng với `Xml3176ChayLaiTuXuat` khi cần.
- [ ] **Step 4:** Commit `docs(xml3176): huong dan gop loi NVYT`.

---

## Self-review (đã chạy)

- Spec §2 mục tiêu → Task 1–5; §3 trường/bắt buộc → Task 3 (bảng + sao điều kiện cũ); §4 luật → Task 1; §5 mã/mô tả/gom → Task 2–3; §6 kiến trúc (lookup, rule thuần, gỡ block, tắt mã cũ, gợi ý) → Task 1, 3, 4; §7 lệnh → Task 5; §9 nghiệm thu → Task 6.
- Tên nhất quán: `NvytLookup::{bang,tuDong,xoaBoNho,khoa}`, `NvytRule::{phanLoai,kiem,MA_CU,TEN}`, `NvytThuThap::tuHoSo`, `Xml3176CompleteChecker::kiemNvyt`, lệnh `xml3176:kiem-lai-nvyt`.
- Quyết định của plan (ngoài spec): `kiemNvyt` public để lệnh dùng lại; `ly_do` khi một giá trị lỗi ở nhiều vai trò lấy theo vai trò đầu; nhãn dòng chỉ liệt kê cho người thực hiện/BS đọc KQ; migration `down()` bật lại mã cũ.
