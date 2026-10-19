# Nhập/xuất Excel Hồ sơ tổng hợp 01/BH — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Thêm kênh dự phòng lập bản 01/BH bằng nhập tệp `.xlsx` (định dạng `HSTH01BH.xlsx` của hisnguonmo), cùng xuất Excel các dòng của một bản và tệp mẫu.

**Architecture:** Tách phần ghi bản (số bản, transaction, chèn dòng, tổng tiền) khỏi `Bh01TaoBan` thành `Bh01GhiBan` nhận dòng đã chuẩn hoá từ một "nguồn" bất kỳ. Nguồn XML3176 giữ nguyên hành vi; nguồn Excel đọc tệp bằng `Tt12DocExcel` qua `Bh01DocExcel` (tiêu đề theo tên, chuẩn hoá ô, kiểm phạm vi). Xuất dùng ghi luồng Spout (`GhiExcelLuong`) với lớp export đọc `bh01_dong`.

**Tech Stack:** Laravel 5.5, PHP 7.x, Maatwebsite Excel 3.1 (`CatalogChunkImport`), Spout 3.3 (`GhiExcelLuong`), PhpSpreadsheet (chỉ để dựng tệp trong test), PHPUnit 6, SQLite in-memory.

**Spec:** `docs/superpowers/specs/2026-10-19-bh01-nhap-xuat-excel-design.md` (bổ sung cho `docs/superpowers/specs/2026-10-19-ho-so-tong-hop-01bh-design.md`) — đọc cả hai trước khi làm.

## Global Constraints

- Chạy test luôn bằng `DB_HOST=127.0.0.1 vendor/bin/phpunit ...` — `.env` máy dev trỏ CSDL THẬT. **Cấm `RefreshDatabase`**; bảng test dựng bằng `tests/Support/DungBangBh01Sqlite.php`. Không chạy `php artisan migrate` hay lệnh ghi nào vào CSDL thật.
- Không khai kiểu trả về trên phương thức public của service mới (Mockery vỡ). PHPUnit 6: `setUp()` không có `: void`; `/** @test */`, tên test snake_case tiếng Việt không dấu.
- Tiền tố `Bh01`; service `App\Services\Tt12\Bh01`, model `App\Models\BHYT\Tt12`, export `App\Exports`.
- Tên và thứ tự 20 cột: **chỉ** lấy từ `Bh01DacTa::the()` (một nguồn cho tiêu đề nhập/xuất/mẫu).
- Giá trị `nguon`: `'xml3176'` (mặc định) | `'excel'`.
- Sheet xuất tên `C79`; mọi ô xuất kiểu chữ (`'kieu_o' => 'chu'`); tên tệp xuất `<ma_ban>.xlsx`, tệp mẫu `HSTH01BH_mau.xlsx`.
- Tệp nguồn lưu trên đĩa `exportTt12` tại `BH01/nguon/<ma_ban>.xlsx`, chỉ sau khi transaction tạo bản commit.
- Tối đa `100000` dòng dữ liệu một tệp; tối đa 20 lỗi mẫu trong thông báo.
- Chú thích mã tiếng Việt không dấu, giải thích VÌ SAO. Chuỗi hiện cho người dùng tiếng Việt có dấu.
- Commit message kết thúc bằng dòng trống rồi `Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>`.
- Mốc `tests/Unit/Tt12`: chỉ được còn đúng một lỗi có sẵn `Tt12CotDanhMucTest`.

## Review Focus

1. **Nhập cùng một tệp hai lần** → hai bản số 1 và 2, không lỗi khoá trùng. Test ở Task 3.
2. **Tệp không phải xlsx hoặc hỏng** (đổi đuôi `.txt` thành `.xlsx`) → 422 "Không đọc được tệp Excel…", không 500. Test ở Task 5.
3. **Tệp vượt `upload_max_filesize`** (`UploadedFile` lỗi `UPLOAD_ERR_INI_SIZE`) → 422 nói rõ quá cỡ tệp. Test ở Task 5.
4. **Dòng trống thừa cuối sheet** (người dùng sửa tay) → bị bỏ qua, không thành lỗi "thiếu trường". Test ở Task 2.
5. **`NAM_QT` là số `2026` (float `2026.0` từ Excel)** → chuẩn hoá `'2026'`, không bị coi là khác kỳ. Test ở Task 2.

---

### Task 1: Cột nguồn + tách lớp ghi bản dùng chung

**Files:**
- Create: `database/migrations/2026_10_19_100005_them_nguon_vao_bh01_ban.php`
- Create: `app/Services/Tt12/Bh01/Bh01GhiBan.php`
- Modify: `app/Services/Tt12/Bh01/Bh01TaoBan.php` (bỏ `soBanKeTiep`/`laTrungKhoa`/`ghi`/vòng thử lại, gọi `Bh01GhiBan`)
- Modify: `app/Models/BHYT/Tt12/Bh01Ban.php` (`$fillable` + hằng `NGUON_XML3176 = 'xml3176'`, `NGUON_EXCEL = 'excel'`)
- Modify: `tests/Support/DungBangBh01Sqlite.php` (nạp migration 100005)
- Modify: `tests/Unit/Tt12/Bh01/Bh01TaoBanTest.php` (test `trung_so_ban_thi_thu_lai_lay_so_ke_tiep` và `dong_bi_chan_xuat_hien_giua_hai_luot_thi_rollback` đang ghi đè `soBanKeTiep` trên lớp con `Bh01TaoBan` — chuyển seam sang `Bh01GhiBan`)
- Test: `tests/Unit/Tt12/Bh01/Bh01GhiBanTest.php`

**Interfaces:**
- Produces:
  - Migration: `bh01_ban.nguon` string(10) default `'xml3176'`; `ten_tep_nguon` string nullable; `duong_dan_tep_nguon` string nullable.
  - `Bh01GhiBan::ghi($maCskcb, array $ky, $taoBoi, callable $duyetLo, array $thuocTinhBan = array())` → `Bh01Ban` đã lưu, trạng thái `cho_ky`.
    - `$duyetLo(callable $nhanLo)`: nguồn gọi `$nhanLo(array $cacDong)` cho từng lô; mỗi phần tử `['du_lieu' => <19 khoá thường đã chuẩn hoá>, 'ma_lk' => string|null, 'vi_tri' => string]` (`vi_tri` dùng trong thông báo lỗi, vd `'MA_LK 123'` hoặc `'Dòng 7'`).
    - Bên trong: thử lại tối đa 3 lần khi SQLSTATE 23000, mỗi lần `DB::transaction`; kiểm lại từng dòng bằng `Bh01Kiem::dong`, gặp `chan` → ném `Bh01KhongTaoDuocException('<vi_tri> bị chặn: <lý do>…')` (rollback); `canh_bao` như hiện nay; STT 1..n theo thứ tự nhận; chèn lô 500; tổng tiền từ dòng đã ghi.
    - `$thuocTinhBan` merge vào bản lúc tạo (dùng cho `nguon`, `ten_tep_nguon`).
  - `protected function soBanKeTiep($maCskcb, array $ky)` trên `Bh01GhiBan` (seam cho test).
  - `Bh01TaoBan::__construct(Bh01GhiBan $ghiBan = null)` — test truyền lớp con.

- [ ] **Step 1: Write the failing test** `Bh01GhiBanTest`:
  - `ghi_ban_tu_lo_dong_va_tinh_tong()` — nguồn 2 lô (2 + 1 dòng) → `so_dong = 3`, STT 1..3 đúng thứ tự, `t_bhtt` đúng, `nguon === 'xml3176'` khi không truyền thuộc tính.
  - `thuoc_tinh_ban_duoc_ghi()` — truyền `['nguon' => 'excel', 'ten_tep_nguon' => 'a.xlsx']` → bản có đúng hai giá trị; `ma_lk` của dòng `null`.
  - `dong_chan_thi_rollback_va_bao_vi_tri()` — dòng thứ 2 thiếu `ho_ten`, `vi_tri = 'Dòng 3'` → ném `Bh01KhongTaoDuocException` chứa `'Dòng 3'`; `Bh01Ban::count() === 0`, `Bh01Dong::count() === 0`.
- [ ] **Step 2: Run** `DB_HOST=127.0.0.1 vendor/bin/phpunit tests/Unit/Tt12/Bh01/Bh01GhiBanTest.php` — Expected: FAIL.
- [ ] **Step 3: Implement** migration, model, `Bh01GhiBan`, refactor `Bh01TaoBan::tao()` để truyền nguồn `Bh01Nguon::docTheoLo` (vi_tri `'MA_LK ' . ma_lk`) vào `Bh01GhiBan::ghi`. Hành vi `tao()`/`xemTruoc()` không đổi.
- [ ] **Step 4: Run** `DB_HOST=127.0.0.1 vendor/bin/phpunit tests/Unit/Tt12/Bh01` — Expected: PASS toàn bộ (gồm toàn bộ `Bh01TaoBanTest` cũ, đã chuyển seam).
- [ ] **Step 5: Commit** `refactor(bh01): tach Bh01GhiBan dung chung, them cot nguon cho ban`

### Task 2: Đọc và kiểm tệp Excel

**Files:**
- Create: `app/Services/Tt12/Bh01/Bh01DocExcel.php`
- Create: `tests/Support/TaoXlsxBh01.php` (trait dựng tệp `.xlsx` tạm bằng PhpSpreadsheet: `taoXlsx(array $hang, $tenSheet = 'C79')` → đường dẫn; tự xoá ở `tearDown`)
- Test: `tests/Unit/Tt12/Bh01/Bh01DocExcelTest.php`

**Interfaces:**
- Consumes: `Tt12DocExcel::doc($duongDan, callable $xuLy, callable $nhanHeader)` và `Tt12DocExcel::chuanHoaO($o)` (`app/Services/Tt12/Tt12DocExcel.php`); `Bh01DacTa::the()`; `Bh01ChuanHoa::tien()`; `Bh01Kiem::dong()`.
- Produces:
  - `Bh01DocExcel::kiem($duongDan, $maCskcb, array $ky)` → `['so_dong' => int, 'so_dong_canh_bao' => int, 'loi' => string[] (≤ 20), 'cot_thua' => string[], 't_tongchi_bv' => float, 't_bhtt' => float]`. `loi` rỗng nghĩa là nhập được. Không ném với lỗi dữ liệu; chỉ ném `\RuntimeException('Không đọc được tệp Excel…')` khi thư viện đọc thất bại.
  - `Bh01DocExcel::duyetLo($duongDan, callable $nhanLo)` — gọi `$nhanLo` với lô phần tử đúng hình dạng `Bh01GhiBan` (`du_lieu`, `ma_lk` = null, `vi_tri` = `'Dòng N'`). Chỉ gọi sau khi `kiem()` sạch.
  - `Bh01DocExcel::chuanHoaDong(array $theoTen)` → 19 khoá thường (public static, để test thuần).
- Quy tắc (spec 4.2–4.5), không lặp lại ở đây; thêm:
  - Số dòng báo lỗi: `N = sttBatDau + i + 1` (tính cả dòng tiêu đề). *Ruling:* `Tt12DocExcel` đã bỏ dòng trống, nên N lệch khi có dòng trống ở giữa — chấp nhận, ghi chú trong mã.
  - Lỗi phạm vi gom thành một câu: `'Tệp có hồ sơ khác cơ sở/kỳ đang chọn: 37470 kỳ 10/2026 (12 dòng), 01929 kỳ 09/2026 (3 dòng). Dòng đầu tiên: 5, 8, …'`.
  - Lỗi tiêu đề: `'Thiếu cột: T_BHTT, NAM_QT'` / `'Trùng cột: HO_TEN'`. Tệp rỗng: `'Tệp không có hồ sơ nào.'`. Quá trần: `'Tệp có hơn 100.000 dòng.'`.

- [ ] **Step 1: Write the failing tests** `Bh01DocExcelTest` (tệp dựng bằng trait; hàng 1 = 20 tên thẻ từ `Bh01DacTa::the()`):
  - `nhan_tep_dung_dinh_dang_his()` — 2 dòng hợp lệ (giá trị chữ) → `loi === []`, `so_dong === 2`.
  - `tieu_de_khong_phan_biet_hoa_thuong_va_dao_thu_tu()` — tiêu đề viết thường, có khoảng trắng, đảo thứ tự, thêm cột `GHI_CHU` → `loi === []`, `cot_thua === ['GHI_CHU']`.
  - `thieu_cot_va_trung_cot_thi_bao_loi()` — thiếu `T_BHTT` → `loi[0]` chứa `'Thiếu cột: T_BHTT'`; tệp khác có `HO_TEN` hai lần → chứa `'Trùng cột: HO_TEN'`.
  - `lan_ky_hoac_co_so_thi_tu_choi_ca_tep()` — 1 dòng `37470`, 1 dòng tháng `09` → một lỗi chứa `'37470 kỳ 10/2026 (1 dòng)'` và `'01929 kỳ 09/2026 (1 dòng)'`.
  - `o_so_mat_so_0_dau_duoc_dem_lai()` — ô số `1929`, `1`, `9` cho `MA_CSKCB`/`MA_LOAI_KCB`/`THANG_QT` → `chuanHoaDong` ra `'01929'`, `'01'`, `'09'`; `loi === []`.
  - `nam_qt_so_thuc_khong_bi_coi_la_khac_ky()` — Review Focus 5: ô `NAM_QT` = `2026.0` → `loi === []`.
  - `tien_so_thuc_lam_tron_hai_chu_so()` — `1234.567` → `'1234.57'`.
  - `o_ngay_kieu_date_bi_chan()` — `NGAY_RA` là `DateTime` → `loi[0]` chứa `'Dòng 2'`.
  - `stt_trong_tep_bi_bo_qua()` — `STT` = `99`, `5` → `duyetLo` trả dòng không có khoá `stt` (STT do `Bh01GhiBan` đánh).
  - `dong_trong_cuoi_sheet_bi_bo_qua()` — Review Focus 4: 2 dòng dữ liệu + 5 dòng trống → `so_dong === 2`, `loi === []`.
  - `tep_chi_co_tieu_de_thi_tu_choi()`.
- [ ] **Step 2: Run** `DB_HOST=127.0.0.1 vendor/bin/phpunit tests/Unit/Tt12/Bh01/Bh01DocExcelTest.php` — Expected: FAIL.
- [ ] **Step 3: Implement** `Bh01DocExcel` theo Interfaces và spec 4.2–4.5.
- [ ] **Step 4: Run** lệnh Step 2 — Expected: PASS.
- [ ] **Step 5: Commit** `feat(bh01): doc va kiem tep Excel 01/BH (tieu de theo ten, chuan hoa o, kiem pham vi)`

### Task 3: Nhập tệp thành bản

**Files:**
- Create: `app/Services/Tt12/Bh01/Bh01NhapExcel.php`
- Test: `tests/Unit/Tt12/Bh01/Bh01NhapExcelTest.php`

**Interfaces:**
- Consumes: `Bh01DocExcel::kiem/duyetLo` (Task 2), `Bh01GhiBan::ghi` (Task 1), `Bh01KhongTaoDuocException`.
- Produces: `Bh01NhapExcel::nhap($duongDanTam, $tenTepGoc, $maCskcb, array $ky, $taoBoi)` → `['ban' => Bh01Ban, 'cot_thua' => string[]]`. Ném `Bh01KhongTaoDuocException` với thông điệp gộp các `loi` (xuống dòng giữa các lỗi) khi `kiem()` có lỗi. Sau khi `ghi` trả về (đã commit): chép tệp tạm vào `exportTt12` `BH01/nguon/<ma_ban>.xlsx`, cập nhật `duong_dan_tep_nguon`. Không xếp job ký (controller làm, như `tao`).

- [ ] **Step 1: Write the failing tests** (`Storage::fake('exportTt12')`):
  - `nhap_thanh_cong_tao_ban_nguon_excel()` — `nguon === 'excel'`, `ten_tep_nguon === 'HSTH01BH.xlsx'`, `so_dong`, STT 1..n theo thứ tự tệp (không sắp lại theo mục), `ma_lk` null, tệp tồn tại tại `BH01/nguon/<ma_ban>.xlsx`.
  - `nhap_hai_lan_cung_tep_ra_hai_ban()` — Review Focus 1 → `so_ban` 1 và 2.
  - `chung_day_so_voi_ban_xml3176()` — có sẵn bản `so_ban = 1` nguồn xml3176 cùng kỳ → bản nhập là `so_ban = 2`.
  - `tep_loi_thi_nem_va_khong_luu_gi()` — tệp lẫn kỳ → ném; `Bh01Ban::count() === 0`; không có tệp nào dưới `BH01/nguon`.
- [ ] **Step 2: Run** `DB_HOST=127.0.0.1 vendor/bin/phpunit tests/Unit/Tt12/Bh01/Bh01NhapExcelTest.php` — Expected: FAIL.
- [ ] **Step 3: Implement** `Bh01NhapExcel::nhap`.
- [ ] **Step 4: Run** lệnh Step 2 — Expected: PASS.
- [ ] **Step 5: Commit** `feat(bh01): nhap tep Excel thanh ban 01/BH nguon excel`

### Task 4: Xuất Excel và tệp mẫu

**Files:**
- Create: `app/Exports/Bh01DongExport.php`
- Create: `app/Services/Tt12/Bh01/Bh01XuatExcel.php`
- Test: `tests/Unit/Tt12/Bh01/Bh01XuatExcelTest.php`

**Interfaces:**
- Consumes: `GhiExcelLuong::ghi(array $exports, string $dich)` (`app/Services/ExcelLuong/GhiExcelLuong.php`; khuôn lớp export: `app/Exports/DmKhoaGiuongSheetExport.php` — `title()`, `headings()`, `generator()`, `map()`, `dinhDangLuong()`), `Bh01DacTa::the()`.
- Produces:
  - `Bh01DongExport($banId = null)` — `title()` = `'C79'`; `headings()` = 20 tên thẻ; `generator()` đọc `DB::table('bh01_dong')->where('ban_id', …)->orderBy('stt')->cursor()` (null → không dòng, dùng cho tệp mẫu); `map()` trả 20 giá trị theo thứ tự thẻ (`stt` ở cột 1); `dinhDangLuong()` với `'kieu_o' => 'chu'`.
  - `Bh01XuatExcel::xuatBan(Bh01Ban $ban)` → đường dẫn tuyệt đối tệp tạm `.xlsx`; `Bh01XuatExcel::tepMau()` → đường dẫn tệp tạm. Controller trả bằng `response()->download($duongDan, $ten)->deleteFileAfterSend(true)`.

- [ ] **Step 1: Write the failing tests**:
  - `xuat_ban_dung_tieu_de_va_dong()` — đọc lại tệp bằng PhpSpreadsheet: sheet `C79`, hàng 1 = 20 tên thẻ, hàng 2..n = giá trị `bh01_dong` theo `stt`.
  - `moi_o_la_chu_giu_so_0_dau()` — `ma_cskcb '01929'`, `thang_qt '09'` đọc lại là chuỗi `'01929'`, `'09'` (kiểu ô string).
  - `tep_mau_chi_co_tieu_de()`.
  - `vong_xuat_nhap_khong_mat_du_lieu()` — tạo bản nguồn xml3176 (qua `Bh01GhiBan`), xuất, nhập lại bằng `Bh01NhapExcel::nhap` → bản mới có 19 trường từng dòng y hệt bản cũ.
- [ ] **Step 2: Run** `DB_HOST=127.0.0.1 vendor/bin/phpunit tests/Unit/Tt12/Bh01/Bh01XuatExcelTest.php` — Expected: FAIL.
- [ ] **Step 3: Implement** export + `Bh01XuatExcel`.
- [ ] **Step 4: Run** lệnh Step 2 — Expected: PASS.
- [ ] **Step 5: Commit** `feat(bh01): xuat Excel cac dong cua ban va tep mau (ghi luong Spout, o chu)`

### Task 5: Màn hình, route, cảnh báo và xác nhận theo nguồn

**Files:**
- Modify: `app/Http/Controllers/BHYT/BHYTBh01Controller.php` (`nhapExcel`, `xuatExcel`, `tepMau`; `kyDaDoi`; `gui`; `dongDanhSach`)
- Modify: `routes/web.php` (khối bh01, ~dòng 685–702)
- Modify: `resources/views/bhyt/tt12/bh01/index.blade.php`
- Test: `tests/Unit/Tt12/Bh01/Bh01ControllerTest.php` (thêm test)

**Interfaces:**
- Consumes: `Bh01NhapExcel::nhap`, `Bh01XuatExcel::xuatBan/tepMau`, `Bh01XepHang::xepKyBanMoi`, `Bh01XepHang::daDuocTiepNhan`.
- Produces routes (tĩnh trước `{ma_ban}`):

| Method | URI (`bhyt/…`) | Action | Name |
|---|---|---|---|
| POST | `tt12/bh01/nhap-excel` | `nhapExcel` (multipart `tep`, `ma_cskcb`, `nam`, `thang`) | `bhyt.tt12.bh01.nhap-excel` |
| GET | `tt12/bh01/tep-mau` | `tepMau` | `bhyt.tt12.bh01.tep-mau` |
| GET | `tt12/bh01/{ma_ban}/xuat-excel` | `xuatExcel` | `bhyt.tt12.bh01.xuat-excel` |

- `nhapExcel`: cùng chốt với `tao` (ký tắt → 422; `docCoSoVaKy`); thiếu tệp → 422 `'Chưa chọn tệp Excel.'`; `UPLOAD_ERR_INI_SIZE`/`UPLOAD_ERR_FORM_SIZE` → 422 `'Tệp vượt cỡ cho phép (…).'` (đọc `ini_get('upload_max_filesize')`); đuôi khác `xlsx` → 422; `\RuntimeException` đọc tệp → 422 `'Không đọc được tệp Excel. Hãy lưu lại dạng .xlsx rồi thử lại.'`; `Bh01KhongTaoDuocException` → 422 kèm thông điệp. Thành công: `xepKyBanMoi`, JSON `thong_diep` nêu mã bản, số hồ sơ, số hồ sơ cảnh báo, tổng chi BV, tổng BHTT (spec 4.1: số liệu tổng hiện ngay sau khi nhập), và cột thừa nếu có.
- `kyDaDoi`: bản `nguon = excel` → `false`; "bản mới nhất" = `max(so_ban)` trong các bản `nguon = xml3176` của kỳ.
- `gui` 409: câu xác nhận nêu nguồn cả hai bản: `'… đã được cổng tiếp nhận ở bản N (nguồn XML3176, mã GD …). Gửi bản M (nguồn Excel)?'` (nhãn nguồn: `xml3176` → `XML3176`, `excel` → `Excel`).
- `dongDanhSach` thêm `nguon`, `ten_tep_nguon`.
- View: khối "Lập bản mới" thêm `<input type="file" accept=".xlsx">`, nút "Nhập từ Excel" (gửi `FormData` + `_token`, dùng `batDauChay` hiện có với chữ `'Đang nhập tệp Excel…'`), liên kết "Tải tệp mẫu"; bảng thêm cột "Nguồn" (text qua `render.text()`, tên tệp trong `title` gán bằng `.attr()`), nút "Xuất Excel" mỗi dòng (liên kết `encodeURIComponent(ma_ban)`). Lỗi nhiều dòng hiện giữ xuống dòng (`white-space: pre-line`).

- [ ] **Step 1: Write the failing tests** (thêm vào `Bh01ControllerTest`; tệp dựng bằng trait Task 2, gói vào `UploadedFile(..., null, null, true)`):
  - `nhap_excel_thanh_cong_xep_job_ky()` — `Queue::assertPushed(SignBh01Job::class)`, bản `nguon = excel`.
  - `nhap_excel_tep_hong_thi_422()` — Review Focus 2: tệp nội dung văn bản đặt tên `.xlsx` → 422, thông điệp chứa `'Không đọc được tệp Excel'`.
  - `nhap_excel_qua_co_thi_422()` — Review Focus 3: `UploadedFile` lỗi `UPLOAD_ERR_INI_SIZE` → 422 chứa `'vượt cỡ'`.
  - `nhap_excel_khi_tat_ky_thi_422()`; `nhap_excel_khong_co_tep_thi_422()`.
  - `ban_excel_khong_bao_du_lieu_da_doi()` — bản excel mới nhất của kỳ, XML1 kỳ đó khác → `du_lieu_da_doi === false`; đồng thời bản xml3176 cũ hơn của cùng kỳ vẫn được tính cờ (là bản xml3176 mới nhất).
  - `gui_xac_nhan_neu_nguon_ca_hai_ban()` — kỳ có bản 1 xml3176 trả `200`; gửi bản 2 excel → 409, thông điệp chứa `'nguồn XML3176'` và `'nguồn Excel'`.
  - `xuat_excel_tra_tep_ten_ma_ban()` — header `Content-Disposition` chứa `<ma_ban>.xlsx`.
  - `route_tinh_moi_khong_bi_tham_so_nuot()` — `nhap-excel`, `tep-mau` khớp đúng action.
- [ ] **Step 2: Run** `DB_HOST=127.0.0.1 vendor/bin/phpunit tests/Unit/Tt12/Bh01/Bh01ControllerTest.php` — Expected: FAIL.
- [ ] **Step 3: Implement** controller, routes, view.
- [ ] **Step 4: Run** `DB_HOST=127.0.0.1 vendor/bin/phpunit tests/Unit/Tt12` — Expected: chỉ còn lỗi có sẵn `Tt12CotDanhMucTest`. Kiểm JS: trích `<script>` của view, thay `{{…}}`, `node --check` → không lỗi cú pháp.
- [ ] **Step 5: Commit** `feat(bh01): man nhap/xuat Excel, cot nguon, xac nhan gui neu nguon`

### Task 6: Kiểm toàn bộ và đo

**Files:** không tạo mới (sửa lỗi nếu lộ ra).

- [ ] **Step 1: Mốc test.** Chạy `DB_HOST=127.0.0.1 vendor/bin/phpunit --log-junit <scratchpad>/sau.xml` trên nhánh và trên `main`, so **danh sách tên test đỏ**. Expected: không có test đỏ mới.
- [ ] **Step 2: Đo (chỉ đọc CSDL thật):** dựng trong scratchpad một tệp `.xlsx` ~21.000 dòng từ `xml3176_xml1s` 01929 kỳ 10/2026 (đọc bằng `Bh01Nguon::docTheoLo` + `Bh01ChuanHoa::dong`), chạy `Bh01DocExcel::kiem` và ghi thời gian + bộ nhớ đỉnh. Expected: < 60 giây, < 128 MB. Không chạy `nhap()` (ghi CSDL thật).
- [ ] **Step 3: Migrate trên CSDL dev — hỏi chủ dự án trước** (CSDL thật).
- [ ] **Step 4: Commit** các sửa lỗi nếu có: `fix(bh01): ...`
