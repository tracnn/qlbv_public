# Hồ sơ tổng hợp Mẫu 01/BH — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Tiền giám định dựng Bảng tổng hợp Mẫu 01/BH từ `xml3176_xml1s` theo (cơ sở, kỳ), ký số thành một "bản" đóng băng tải về được, và gửi bản đó lên cổng BHXH bằng nút bấm.

**Architecture:** Lớp nguồn truy vấn XML1 → lớp chuẩn hoá + kiểm (hàm thuần) → lớp tạo bản ghi ảnh chụp vào `bh01_ban`/`bh01_dong` → `SignBh01Job` dựng XML (hàm thuần, `XMLWriter`) và ký qua `XMLSignService` → người dùng bấm gửi → `SubmitBh01Job` gửi đúng byte tệp đã ký qua `Tt12SubmitService` mở rộng (`loaiHs=5` + `kyQT`). Một màn mới trong menu "Danh mục TT12".

**Tech Stack:** Laravel 5.5, PHP 7.x, MySQL (prod), SQLite in-memory (test), PHPUnit 6, Guzzle, AdminLTE + jQuery/DataTables.

**Spec:** `docs/superpowers/specs/2026-10-19-ho-so-tong-hop-01bh-design.md` — đọc trước khi làm bất kỳ task nào.

## Global Constraints

- Chạy test luôn bằng `DB_HOST=127.0.0.1 vendor/bin/phpunit ...` — `.env` máy dev trỏ CSDL THẬT. **Cấm `RefreshDatabase`**; bảng test dựng bằng trait SQLite như `tests/Support/DungBangTt12Sqlite.php`.
- Không khai kiểu trả về trên phương thức public của service/job mới (Mockery trong dự án vỡ với return type). Ghi đè phương thức của lớp đã có kiểu thì phải giữ đúng kiểu.
- Job **không** nhận service qua tham số có type-hint của `handle()` (bẫy container Laravel 5.5, xem chú thích `$submitServiceGia` trong `app/Jobs/SubmitTt12Job.php`). Dùng thuộc tính public `...Gia` cho test.
- Không dùng tên `Mau01` (đã là danh mục 01/DM). Tiền tố: `Bh01`. Namespace service `App\Services\Tt12\Bh01`, model `App\Models\BHYT\Tt12`.
- Tên thẻ cơ sở trong XML: `MA_CSKCB`. Tên trường body lấy từ `config('tt12.truong_body')`; thêm khoá `ky_qt => 'kyQT'`.
- `loai_hs` của 01/BH là chuỗi `'5'`; endpoint `/api/HoSoTongHop7980/GuiHoSoTongHop01BH`.
- Kỳ hợp lệ: từ `2026-04` trở đi (Điều 17). `nam_qt` 4 ký tự, `thang_qt` 2 ký tự có số 0 đứng đầu (`'09'`), so sánh chuỗi.
- Độ dài đo bằng ký tự (`mb_strlen`/`CHAR_LENGTH`), không bằng byte.
- Cờ dùng chung: `organization.tt12.sign_enabled`, `organization.tt12.submit_enabled`; hàng đợi `Tt12HangDoi::ky()` / `Tt12HangDoi::gui()`; đĩa `exportTt12`, thư mục con `BH01/`.
- Chú thích mã viết tiếng Việt không dấu, giải thích VÌ SAO, đúng giọng các tệp TT12 hiện có.
- Commit message kết thúc bằng `Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>`.

## Review Focus

1. **Bấm "Tạo bản" hai lần liền / hai người cùng lúc** → không được ra hai bản cùng `so_ban`; lần thứ hai nhận bản kế tiếp hoặc lỗi rõ ràng, không 500. Test ở Task 4.
2. **Bấm "Gửi cổng" hai lần liền** → chỉ một job gửi được xếp trong khi job trước chưa xong. Test ở Task 6.
3. **Kỳ không có hồ sơ BHYT nào** (vd 37470 kỳ 04/2026) → từ chối tạo bản với thông điệp, không tạo bản rỗng. Test ở Task 4.
4. **Tệp đã ký bị thay/xoá trên đĩa** → job gửi từ chối, ghi lỗi, không gửi. Test ở Task 6.
5. **Tháng nhập dạng `9` thay vì `09`** (form/AJAX) → chuẩn hoá về `'09'`, không trả về 0 hồ sơ. Test ở Task 3.

---

### Task 1: Bảng, model, trait test

**Files:**
- Create: `database/migrations/2026_10_19_100001_create_bh01_ban_table.php`
- Create: `database/migrations/2026_10_19_100002_create_bh01_dong_table.php`
- Create: `database/migrations/2026_10_19_100003_create_bh01_lich_su_gui_table.php`
- Create: `database/migrations/2026_10_19_100004_them_chi_muc_ky_qt_xml3176_xml1s.php`
- Create: `app/Models/BHYT/Tt12/Bh01Ban.php`, `Bh01Dong.php`, `Bh01LichSuGui.php`
- Create: `tests/Support/DungBangBh01Sqlite.php`
- Test: `tests/Unit/Tt12/Bh01/Bh01BangTest.php`

**Interfaces:**
- Produces:
  - Bảng `bh01_ban`: đúng các cột ở spec mục 7, cộng `dang_xu_ly_tu` (timestamp nullable — khoá chống gửi trùng, Task 6). `so_canh_bao` int default 0. `trang_thai_ky` string default `'cho_ky'`. Duy nhất `(ma_cskcb, nam_qt, thang_qt, so_ban)`; `ma_ban` unique.
  - Bảng `bh01_dong`: `ban_id`, `stt`, `ma_lk`, 20 cột tên thường của thẻ 01/BH (`ho_ten` … `thang_qt`, tất cả kiểu string vì lưu giá trị đã chuẩn hoá), `canh_bao` text nullable. Unique `(ban_id, stt)`, index `ma_lk`.
  - Bảng `bh01_lich_su_gui`: cột như spec mục 7.
  - Model `Bh01Ban` (`hasMany dong()`, `hasMany lichSuGui()`, hằng `KY_CHO='cho_ky'`, `KY_XONG='da_ky'`, `KY_LOI='ky_loi'`), `Bh01Dong`, `Bh01LichSuGui` — `$fillable` đủ cột.
  - Trait `DungBangBh01Sqlite::dungBangBh01()` — gọi lại cách nối SQLite của `DungBangTt12Sqlite`, nạp 3 migration bh01, và dựng bảng **tối thiểu** `xml3176_xml1s` (cột: `id`, `ma_lk`, `stt`, 20 cột nguồn của spec mục 2.2 với tên thường — `ma_the_bhyt`, `t_*` kiểu double, `so_ngay_dtri` int, `nam_qt`/`thang_qt` string). Không nạp migration thật của xml1s (cú pháp MySQL).
- Migration chỉ mục (100004): chỉ chạy trên MySQL (`DB::getDriverName() === 'mysql'`), dùng `ALTER TABLE xml3176_xml1s ADD INDEX idx_xml1_cskcb_ky (ma_cskcb, nam_qt, thang_qt), ALGORITHM=INPLACE, LOCK=NONE`; bỏ qua nếu chỉ mục đã có. `down()` xoá chỉ mục.

- [ ] **Step 1: Write the failing test** `Bh01BangTest`:
  - `dung_duoc_ba_bang_va_bang_nguon()` — `Schema::hasTable` đúng cho `bh01_ban`, `bh01_dong`, `bh01_lich_su_gui`, `xml3176_xml1s`.
  - `khong_cho_hai_ban_cung_so_trong_mot_ky()` — tạo `Bh01Ban` (`01929`,`2026`,`09`, `so_ban`=1) hai lần → lần hai ném `Illuminate\Database\QueryException`.
  - `cung_so_ban_khac_co_so_thi_duoc()` — `01929` và `37470` cùng kỳ, cùng `so_ban`=1 → cả hai tạo được.
- [ ] **Step 2: Run** `DB_HOST=127.0.0.1 vendor/bin/phpunit tests/Unit/Tt12/Bh01/Bh01BangTest.php` — Expected: FAIL (trait/bảng chưa có).
- [ ] **Step 3: Implement** migrations, models, trait theo Interfaces.
- [ ] **Step 4: Run** lệnh Step 2 — Expected: PASS (3 tests).
- [ ] **Step 5: Commit** `feat(bh01): bang ban/dong/lich su gui 01/BH va chi muc ky QT tren xml1s`

### Task 2: Chuẩn hoá và kiểm một dòng (hàm thuần)

**Files:**
- Create: `app/Services/Tt12/Bh01/Bh01DacTa.php`
- Create: `app/Services/Tt12/Bh01/Bh01ChuanHoa.php`
- Create: `app/Services/Tt12/Bh01/Bh01Kiem.php`
- Test: `tests/Unit/Tt12/Bh01/Bh01ChuanHoaTest.php`, `tests/Unit/Tt12/Bh01/Bh01KiemTest.php`

**Interfaces:**
- Produces:
  - `Bh01DacTa::the()` → mảng 20 phần tử theo đúng thứ tự spec mục 2.3: `['the' => 'STT', 'kieu' => 'so'|'chuoi'|'tien'|'ngay12', 'max' => int]`. Các thẻ tiền `T_*` kiểu `'tien'`; `NGAY_VAO`, `NGAY_VAO_NOI_TRU`, `NGAY_RA`, `NGAY_SINH` kiểu `'ngay12'`.
  - `Bh01DacTa::muc($maLoaiKcb)` → `1..4` theo spec 2.1 (`01,06,07`→1; `02,05,08`→2; `04,09`→3; `03`→4), `null` nếu không thuộc.
  - `Bh01ChuanHoa::dong($xml1)` (`$xml1`: object/array có cột tên thường của `xml3176_xml1s`) → mảng khoá tên thường 19 thẻ (trừ STT), giá trị chuỗi đã chuẩn hoá.
  - `Bh01ChuanHoa::tien($giaTri)` → chuỗi.
  - `Bh01Kiem::dong(array $daChuanHoa)` → `['chan' => string[], 'canh_bao' => string[]]` (mỗi phần tử một câu tiếng Việt nêu thẻ và lý do).

- [ ] **Step 1: Write the failing tests**
  - `Bh01ChuanHoaTest`:
    - `tien_hai_chu_so_thap_phan_dau_cham()` — `tien(1234.5)`→`'1234.5'`; `tien(1234.567)`→`'1234.57'`; `tien(1000)`→`'1000'`; `tien(null)`→`'0'`; `tien(0.1+0.2)`→`'0.3'`.
    - `lay_the_dau_tien_khi_nhieu_the()` — `ma_the_bhyt = 'GD4010000000001;GD4010000000002'` → `'GD4010000000001'`.
    - `ngay_vao_noi_tru_rong_giu_chuoi_rong()` — null → `''`.
    - `cac_truong_con_lai_nguyen_van()` — `ho_ten='Nguyễn Văn A & B'`, `ma_benh_chinh='E11.4†'` giữ nguyên.
  - `Bh01KiemTest`:
    - `dong_hop_le_khong_chan_khong_canh_bao()`.
    - `thieu_truong_bat_buoc_thi_chan()` — lần lượt rỗng `ho_ten`, `ma_the_bhyt`, `ngay_vao`, `ngay_ra`, `ma_loai_kcb`, `ma_cskcb` → `chan` không rỗng.
    - `ngay_khong_du_12_chu_so_thi_chan()` — `ngay_ra='20261001'`.
    - `ma_loai_kcb_ngoai_01_09_thi_chan()` — `'10'` và `'1'` đều chặn; `'09'` không chặn.
    - `the_17_ky_tu_chi_canh_bao()` — `'HC438038070001032'` → `chan` rỗng, `canh_bao` có 1 phần tử nhắc `MA_THE_BHYT`.
    - `ma_benh_co_dau_dao_khong_bi_bao_dai()` — `'E11.4†'` → không cảnh báo (6 ký tự).
- [ ] **Step 2: Run** `DB_HOST=127.0.0.1 vendor/bin/phpunit tests/Unit/Tt12/Bh01` — Expected: FAIL.
- [ ] **Step 3: Implement.** `tien()`: làm tròn 2 chữ số (`round($x, 2)`), in bằng `number_format(..., 2, '.', '')` rồi bỏ `0` thừa và dấu chấm thừa. Vượt `max` mà là thẻ chép từ XML1 → `canh_bao`, không `chan` (spec 4.3).
- [ ] **Step 4: Run** lệnh Step 2 — Expected: PASS.
- [ ] **Step 5: Commit** `feat(bh01): chuan hoa va kiem dong 01/BH theo dac ta muc VIII.5`

### Task 3: Nguồn dữ liệu và tổng hợp kỳ

**Files:**
- Create: `app/Services/Tt12/Bh01/Bh01Ky.php`
- Create: `app/Services/Tt12/Bh01/Bh01Nguon.php`
- Test: `tests/Unit/Tt12/Bh01/Bh01NguonTest.php`

**Interfaces:**
- Consumes: `Bh01DacTa::muc()` (Task 2), trait `DungBangBh01Sqlite` (Task 1).
- Produces:
  - `Bh01Ky::tu($nam, $thang)` → `['nam' => 'YYYY', 'thang' => 'MM', 'ky_qt' => 'YYYYMM']`; ném `\InvalidArgumentException('Kỳ ... trước 04/2026 ...')` nếu < 202604 hoặc tháng ngoài 1..12.
  - `Bh01Nguon::truyVan($maCskcb, array $ky)` → Query Builder trên `xml3176_xml1s` với 4 điều kiện spec 4.1, sắp xếp theo mục I→IV (biểu thức `CASE ma_loai_kcb ...`), rồi `ngay_ra`, rồi `ma_lk`.
  - `Bh01Nguon::tongHop($maCskcb, array $ky)` → `['so_ho_so' => int, 'theo_muc' => [1=>int,2=>int,3=>int,4=>int,'khac'=>int], 't_tongchi_bv' => float, 't_tongchi_bh' => float, 't_bhtt' => float, 't_bncct' => float, 't_bntt' => float, 't_nguonkhac' => float]` — một truy vấn gom nhóm, không kéo dòng về PHP.

- [ ] **Step 1: Write the failing tests** (chèn dữ liệu mẫu vào `xml3176_xml1s` SQLite):
  - `chi_lay_ho_so_bhyt_dung_co_so_dung_ky()` — 5 dòng: 2 hợp lệ, 1 thẻ rỗng, 1 khác cơ sở, 1 khác tháng → `truyVan()->count() === 2`.
  - `thu_tu_theo_muc_roi_ngay_ra_roi_ma_lk()` — dòng `03` (mục IV), `01` (mục I), `04` (mục III), hai dòng `01` cùng `ngay_ra` khác `ma_lk` → `pluck('ma_lk')` đúng thứ tự mong đợi.
  - `tong_hop_dung_so_lieu()` — kiểm `so_ho_so`, `theo_muc`, `t_bhtt`.
  - `thang_mot_chu_so_duoc_dem_so_khong()` — `Bh01Ky::tu(2026, 9)['thang'] === '09'` và `Bh01Ky::tu('2026', '9')['ky_qt'] === '202609'`.
  - `ky_truoc_04_2026_bi_tu_choi()` — `tu(2026, 3)` ném `\InvalidArgumentException`.
- [ ] **Step 2: Run** `DB_HOST=127.0.0.1 vendor/bin/phpunit tests/Unit/Tt12/Bh01/Bh01NguonTest.php` — Expected: FAIL.
- [ ] **Step 3: Implement** theo Interfaces. Lọc thẻ: `where('ma_the_bhyt', '<>', '')` (giống `Xml3176LocDanhSach::apTheBhyt`).
- [ ] **Step 4: Run** lệnh Step 2 — Expected: PASS.
- [ ] **Step 5: Commit** `feat(bh01): nguon XML1 theo co so + ky va tong hop xem truoc`

### Task 4: Tạo bản (ảnh chụp đóng băng)

**Files:**
- Create: `app/Services/Tt12/Bh01/Bh01TaoBan.php`
- Create: `app/Services/Tt12/Bh01/Bh01KhongTaoDuocException.php`
- Test: `tests/Unit/Tt12/Bh01/Bh01TaoBanTest.php`

**Interfaces:**
- Consumes: `Bh01Ky`, `Bh01Nguon::truyVan`, `Bh01ChuanHoa::dong`, `Bh01Kiem::dong`, model `Bh01Ban`/`Bh01Dong`.
- Produces:
  - `Bh01TaoBan::xemTruoc($maCskcb, array $ky)` → `tongHop()` cộng `['so_dong_chan' => int, 'so_dong_canh_bao' => int, 'loi_chan_mau' => string[] (tối đa 20 dòng đầu, dạng "MA_LK x: lý do")]`. Đọc theo lô (`chunk`), không giữ cả kỳ trong bộ nhớ.
  - `Bh01TaoBan::tao($maCskcb, array $ky, $taoBoi)` → `Bh01Ban` đã lưu (`trang_thai_ky = 'cho_ky'`). Ném `Bh01KhongTaoDuocException` khi: kỳ 0 hồ sơ; có dòng `chan`.
  - `ma_ban` = `sprintf('BH01_%s_%s_%02d', $maCskcb, $ky['ky_qt'], $soBan)`; `id_ds_chitiet` = `'Id-' . \Ramsey\Uuid\Uuid::uuid4()->toString()` (như `Tt12LuuHoSo:48` — `Str::uuid()` không có trên Laravel 5.5).

- [ ] **Step 1: Write the failing tests**
  - `tao_ban_dau_tien_so_ban_1_va_chep_dong()` — 3 hồ sơ hợp lệ → `so_ban=1`, `so_dong=3`, `bh01_dong` có 3 dòng, `stt` 1..3 theo thứ tự `truyVan`, tổng tiền khớp, `id_ds_chitiet` bắt đầu `'Id-'`.
  - `ban_thu_hai_cung_ky_la_so_2()`.
  - `ky_khong_co_ho_so_thi_nem()` — Review Focus 3.
  - `co_dong_bi_chan_thi_nem_va_khong_ghi_gi()` — sau ném, `Bh01Ban::count() === 0`, `Bh01Dong::count() === 0`.
  - `dong_canh_bao_van_tao_va_ghi_canh_bao()` — thẻ 17 ký tự → `so_canh_bao=1`, `bh01_dong.canh_bao` không rỗng.
  - `du_lieu_nguon_doi_sau_khi_tao_khong_lam_doi_ban()` — sửa `t_bhtt` trong xml1s sau khi tạo → `bh01_dong` giữ giá trị cũ.
  - `trung_so_ban_thi_thu_lai_lay_so_ke_tiep()` — Review Focus 1: chèn sẵn một `Bh01Ban` `so_ban=1` bằng tay *sau* khi `tao()` đã đọc `max(so_ban)` (giả lập bằng cách ghi đè một phương thức protected `soBanKeTiep()` trong lớp con test trả về 1 ở lần đầu) → bản được tạo có `so_ban=2`.
- [ ] **Step 2: Run** `DB_HOST=127.0.0.1 vendor/bin/phpunit tests/Unit/Tt12/Bh01/Bh01TaoBanTest.php` — Expected: FAIL.
- [ ] **Step 3: Implement.** Hai lượt: lượt 1 duyệt `chunk` để kiểm (dừng sớm nếu có `chan`); lượt 2 trong `DB::transaction` ghi `bh01_ban` rồi chèn `bh01_dong` theo lô 500 dòng. `soBanKeTiep($maCskcb, $ky)` protected = `max(so_ban)+1`; bắt `QueryException` vi phạm duy nhất → thử lại tối đa 3 lần.
- [ ] **Step 4: Run** lệnh Step 2 — Expected: PASS.
- [ ] **Step 5: Commit** `feat(bh01): tao ban 01/BH dong bang tu XML1`

### Task 5: Dựng XML (hàm thuần)

**Files:**
- Create: `app/Services/Tt12/Bh01/Bh01PhongBi.php`
- Test: `tests/Unit/Tt12/Bh01/Bh01PhongBiTest.php`

**Interfaces:**
- Consumes: `Bh01DacTa::the()`.
- Produces: `Bh01PhongBi::dung($idDsChiTiet, $cacDong)` → chuỗi XML. `$cacDong`: iterable các mảng khoá tên thường gồm `stt` + 19 thẻ (đúng hình dạng `bh01_dong`). Ném `\InvalidArgumentException` khi `$idDsChiTiet` rỗng hoặc không có dòng nào.

- [ ] **Step 1: Write the failing test** `Bh01PhongBiTest`:
  - `dung_dung_tung_byte()` — 2 dòng cố định → so `assertSame` với chuỗi mong đợi viết tay:
    `<?xml version="1.0" encoding="UTF-8"?>\n<HSTH01BH><DS_CHITIET Id="Id-x"><CHITIET_HS01BH><STT>1</STT>…<THANG_QT>09</THANG_QT></CHITIET_HS01BH>…</DS_CHITIET><CHUKYDONVI/></HSTH01BH>` — không thụt lề, 20 thẻ theo đúng thứ tự spec 2.3, thẻ rỗng in `<NGAY_VAO_NOI_TRU/>`.
  - `thoat_ky_tu_dac_biet()` — `ho_ten = 'A & B <C>'` → có `A &amp; B &lt;C&gt;`; `simplexml_load_string` đọc lại ra đúng `'A & B <C>'`.
  - `giu_nguyen_dau_dao_icd()` — `E11.4†` đọc lại đúng.
  - `goc_khong_co_namespace_xsd_xsi()`.
  - `cung_dau_vao_cung_dau_ra()` — gọi hai lần, `assertSame`.
  - `khong_co_dong_thi_nem()`, `thieu_id_thi_nem()`.
- [ ] **Step 2: Run** `DB_HOST=127.0.0.1 vendor/bin/phpunit tests/Unit/Tt12/Bh01/Bh01PhongBiTest.php` — Expected: FAIL.
- [ ] **Step 3: Implement** bằng `XMLWriter` (`openMemory`, `setIndent(false)`), thẻ rỗng dùng `startElement`+`endElement` (ra `<X/>`), `CHUKYDONVI` rỗng đặt cuối.
- [ ] **Step 4: Run** lệnh Step 2 — Expected: PASS.
- [ ] **Step 5: Commit** `feat(bh01): dung XML HSTH01BH bang XMLWriter`

### Task 6: Gửi cổng — mở rộng service, hai job, khoá chống trùng

**Files:**
- Modify: `config/tt12.php` (thêm `'ho_so_tong_hop' => ['BH_01' => ['loai_hs' => '5', 'duong_dan' => '/api/HoSoTongHop7980/GuiHoSoTongHop01BH']]`, thêm `'ky_qt' => 'kyQT'` vào `truong_body`)
- Modify: `app/Services/Tt12/Tt12SubmitService.php`
- Modify: `tests/Support/FakeTt12SubmitService.php`
- Create: `app/Jobs/SignBh01Job.php`, `app/Jobs/SubmitBh01Job.php`
- Create: `app/Services/Tt12/Bh01/Bh01XepHang.php`
- Test: `tests/Unit/Tt12/Tt12SubmitServiceTest.php` (thêm test), `tests/Unit/Tt12/Bh01/SignBh01JobTest.php`, `tests/Unit/Tt12/Bh01/SubmitBh01JobTest.php`, `tests/Unit/Tt12/Bh01/Bh01XepHangTest.php`

**Interfaces:**
- Consumes: `Bh01PhongBi::dung`, model `Bh01Ban`/`Bh01Dong`/`Bh01LichSuGui`, `XMLSignService::signXml`, `Tests\Support\FakeXMLSignService`.
- Produces:
  - `Tt12SubmitService::guiHoSoTongHop($xmlDaKy, $loai, $maCskcb, $kyQt)` — `$loai` khoá trong `config('tt12.ho_so_tong_hop')` (`'BH_01'`). Cùng luồng `gui()`: login theo `$maCskcb` qua `taoLogin()`, 401 → `logout()` + thử lại một lần, đọc phản hồi bằng `docPhanHoi()`. Body thêm `kyQT`. Ném `\InvalidArgumentException` nếu `$loai` lạ hoặc `$kyQt` không khớp `/^\d{6}$/`. Refactor `motLanGui()` nhận thêm mảng trường body phụ; `gui()` cũ không đổi hành vi.
  - `FakeTt12SubmitService::guiHoSoTongHop(...)` ghi `kyQtNhanDuoc`, `loaiNhanDuoc`, `maCskcbNhanDuoc`, `xmlNhanDuoc`, tăng `soLanGoi`.
  - `SignBh01Job($maBan)` — `$tries = 2`, `$timeout = 600`; thuộc tính `public $signServiceGia = null`. `sign_enabled` tắt → return không ghi gì. Dựng XML từ `bh01_dong` (con trỏ theo `stt`), ký, ghi tệp `BH01/{nam}{thang}/{ma_ban}.xml` trên đĩa `exportTt12`, cập nhật `trang_thai_ky='da_ky'`, `sign_method`, `signed_at`, `duong_dan_da_ky`, `sha256_tep` (= `hash('sha256', $noiDungDaKy)`). Ký hỏng → `trang_thai_ky='ky_loi'`, `signed_error`, không ghi tệp.
  - `SubmitBh01Job($maBan, $guiBoi)` — `$tries = 3`, `$timeout = 300`; `public $submitServiceGia = null`. `submit_enabled` tắt → nhả khoá, return. Chỉ gửi khi `trang_thai_ky === 'da_ky'`. Đọc tệp, so `sha256` với `sha256_tep`; lệch hoặc mất tệp → ghi `bh01_lich_su_gui` với `loi` mô tả, cập nhật `thong_diep` trên bản, không gửi. Gửi bằng `guiHoSoTongHop($xml, 'BH_01', $ban->ma_cskcb, $ban->nam_qt . $ban->thang_qt)`; ghi kết quả lên `bh01_ban` + một dòng `bh01_lich_su_gui`. Nhả khoá ở cuối và trong `failed()`; **không** nhả khoá giữa các lần thử lại (cùng khuôn `SubmitTt12Job`).
  - `Bh01XepHang::xepGui($maBan, $guiBoi)` → `bool` (false khi đang có lượt gửi khác). Giữ khoá bằng `UPDATE bh01_ban SET dang_xu_ly_tu = now WHERE ma_ban = ? AND (dang_xu_ly_tu IS NULL OR dang_xu_ly_tu < now - KHOA_PHUT)`; `KHOA_PHUT = 20` (≥ 3 × 300s của job gửi + biên). `Bh01XepHang::xepKy($maBan)` xếp `SignBh01Job` vào hàng đợi ký (không khoá — ký lại dựng cùng nội dung). `Bh01XepHang::nhaKhoa($maBan)`.
  - `Bh01XepHang::daDuocTiepNhan($maCskcb, $nam, $thang)` → `Bh01Ban|null`: bản mới nhất của kỳ có `ma_ket_qua = '200'` (dùng cho hộp xác nhận, Task 7). So `ma_ket_qua` qua `Tt12QuyetDinhGui::daTiepNhan()`.

- [ ] **Step 1: Write the failing tests**
  - Trong `Tt12SubmitServiceTest`:
    - `gui_ho_so_tong_hop_body_co_ky_qt_va_loai_5()` — `parse_str` body: `loaiHs === '5'`, `kyQT === '202609'`, `maCSKCB === '01929'`, `maTinh === '01'`; URL kết thúc `/api/HoSoTongHop7980/GuiHoSoTongHop01BH`.
    - `gui_ho_so_tong_hop_401_dang_nhap_lai_mot_lan()` — phản hồi 1 `{"maKetQua":"401"}`, phản hồi 2 `200` → `soLanLogout === 1`, kết quả `200`.
    - `ky_qt_sai_dang_thi_nem()` — `'2026-09'`.
    - Các test `gui()` hiện có giữ nguyên và vẫn xanh.
  - `SignBh01JobTest`: `ky_xong_ghi_tep_va_sha256()`; `xml_gui_vao_dich_vu_ky_dung_bang_phong_bi()` (so `FakeXMLSignService::$xmlNhanDuoc` với `Bh01PhongBi::dung` cùng dữ liệu); `ky_hong_ghi_ky_loi_khong_ghi_tep()`; `co_ky_tat_thi_khong_lam_gi()`; `ky_lai_cung_noi_dung_truoc_chu_ky()`.
  - `SubmitBh01JobTest`: `gui_dung_byte_tep_da_ky_va_ky_qt()`; `ghi_ket_qua_va_lich_su_moi_lan_gui()` (gửi hai lần → 2 dòng lịch sử); `tep_bi_sua_thi_khong_gui()` (Review Focus 4: sửa tệp trên `Storage::fake` → `soLanGoi === 0`, lịch sử có `loi`); `chua_ky_thi_khong_gui()`; `co_gui_tat_thi_khong_gui_va_nha_khoa()`; `handle_khong_co_tham_so_type_hint()` (Reflection: `handle()` không có tham số).
  - `Bh01XepHangTest`: `bam_gui_hai_lan_lien_chi_xep_mot()` (Review Focus 2; `Queue::fake()`, gọi `xepGui` hai lần → `true`, `false`, `Queue::assertPushed(SubmitBh01Job::class, 1)`); `khoa_qua_han_thi_xep_lai_duoc()`; `da_duoc_tiep_nhan_tra_ban_200_moi_nhat()`.
- [ ] **Step 2: Run** `DB_HOST=127.0.0.1 vendor/bin/phpunit tests/Unit/Tt12` — Expected: các test mới FAIL, test TT12 cũ PASS.
- [ ] **Step 3: Implement** theo Interfaces.
- [ ] **Step 4: Run** lệnh Step 2 — Expected: toàn bộ `tests/Unit/Tt12` PASS.
- [ ] **Step 5: Commit** `feat(bh01): ky va gui 01/BH qua Tt12SubmitService (loaiHs 5 + kyQT), khoa chong gui trung`

### Task 7: Màn hình, route, menu

**Files:**
- Create: `app/Http/Controllers/BHYT/BHYTBh01Controller.php`
- Create: `resources/views/bhyt/tt12/bh01/index.blade.php`, `resources/views/bhyt/tt12/bh01/dong.blade.php`
- Modify: `routes/web.php` (khối TT12, ~dòng 682–730), `config/adminlte.php` (submenu "Danh mục TT12", ~dòng 584–607)
- Test: `tests/Unit/Tt12/Bh01/Bh01ControllerTest.php`

**Interfaces:**
- Consumes: `Bh01Ky`, `Bh01TaoBan`, `Bh01Nguon::tongHop`, `Bh01XepHang`, models, `DanhSachCoSo::danhSach()`.
- Produces routes (đặt các route tĩnh TRƯỚC route có tham số, như chú thích hiện có):

| Method | URI (`bhyt/…`) | Action | Name |
|---|---|---|---|
| GET | `tt12/bh01` | `index` | `bhyt.tt12.bh01.index` |
| GET | `tt12/bh01/xem-truoc` | `xemTruoc` (JSON) | `bhyt.tt12.bh01.xem-truoc` |
| GET | `tt12/bh01/fetch-data` | `fetchData` (DataTables JSON, lọc cơ sở/kỳ) | `bhyt.tt12.bh01.fetch-data` |
| POST | `tt12/bh01/tao` | `tao` | `bhyt.tt12.bh01.tao` |
| POST | `tt12/bh01/{ma_ban}/ky-lai` | `kyLai` | `bhyt.tt12.bh01.ky-lai` |
| POST | `tt12/bh01/{ma_ban}/gui` | `gui` | `bhyt.tt12.bh01.gui` |
| GET | `tt12/bh01/{ma_ban}/tai` | `tai` | `bhyt.tt12.bh01.tai` |
| GET | `tt12/bh01/{ma_ban}/dong` | `dong` (view, phân trang 200) | `bhyt.tt12.bh01.dong` |
| GET | `tt12/bh01/{ma_ban}/lich-su` | `lichSu` (JSON) | `bhyt.tt12.bh01.lich-su` |

- Menu: mục mới "Hồ sơ tổng hợp 01/BH", icon `file-text`, `route` `bhyt.tt12.bh01.index`, `active` `['bhyt/tt12/bh01*']`, trong submenu "Danh mục TT12" (đã có `checkrole: xml-man`). Không thêm middleware quyền riêng.
- `tao`: tạo bản rồi `Bh01XepHang::xepKy()`; `Bh01KhongTaoDuocException` / `\InvalidArgumentException` → JSON 422 kèm thông điệp. Cơ sở phải nằm trong `DanhSachCoSo::danhSach()`, không có mặc định.
- `gui`: bản phải `da_ky`. Nếu `Bh01XepHang::daDuocTiepNhan()` trả bản và request không có `xac_nhan=1` → JSON 409 `{'can_xac_nhan': true, 'thong_diep': 'Kỳ MM/YYYY của cơ sở X đã được cổng tiếp nhận ở bản N (mã GD …). Gửi bản M?'}`. `submit_enabled` tắt → 422 "Chức năng gửi cổng đang tắt". `xepGui` false → 409 "Bản đang được gửi".
- `tai`: `Storage::disk('exportTt12')->download($duong_dan_da_ky, $ma_ban . '.xml')`; chưa ký → 404.
- `fetchData` mỗi dòng: `ma_ban`, kỳ, `so_ban`, `so_dong`, `t_bhtt`, `so_canh_bao`, trạng thái ký, `ma_ket_qua` + `thong_diep`, `ma_gd`, `thoi_gian_tiep_nhan`, cờ `du_lieu_da_doi` (spec 5.5: chỉ tính cho bản mới nhất của mỗi kỳ, so `so_ho_so`, `t_tongchi_bv`, `t_bhtt` của `Bh01Nguon::tongHop` với bản; làm tròn 2 chữ số trước khi so).
- View `index`: khối chọn cơ sở (select, có ô trống mặc định) + tháng/năm + nút "Xem trước" → hiện tổng hợp và lỗi chặn mẫu → nút "Tạo bản" (tắt nếu có chặn hoặc 0 hồ sơ); bảng DataTables các bản với nút Tải tệp / Gửi cổng / Ký lại (chỉ khi `ky_loi`) / Xem dòng / Nhật ký; nút Gửi cổng tắt kèm tooltip khi `submit_enabled` tắt; trạng thái "chờ ký" tự làm mới 5 giây/lần tới khi đổi; xử lý 409 `can_xac_nhan` bằng `confirm()` rồi gửi lại với `xac_nhan=1`. Bám bố cục và JS của `resources/views/bhyt/tt12/index.blade.php`.

- [ ] **Step 1: Write the failing tests** `Bh01ControllerTest` (gọi thẳng phương thức controller với `Request` dựng tay, như `Tt12ControllerTest`; `Cache::put(DanhSachCoSo::KHOA_CACHE, ['01929'=>'…','37470'=>'…'], 60)`; `Queue::fake()`; `Storage::fake('exportTt12')`):
  - `tao_khong_chon_co_so_thi_422()`; `tao_co_so_ngoai_danh_sach_thi_422()`; `tao_thanh_cong_xep_job_ky()`.
  - `gui_ky_da_tiep_nhan_chua_xac_nhan_thi_409_can_xac_nhan()`; `gui_co_xac_nhan_thi_xep_job()`; `gui_ban_chua_ky_thi_422()`; `gui_khi_co_gui_tat_thi_422()`.
  - `tai_tra_dung_noi_dung_tep_da_ky()`.
  - `fetch_data_bao_du_lieu_da_doi_khi_xml1_thay_doi()`.
  - `route_tinh_khong_bi_tham_so_nuot()` — `route('bhyt.tt12.bh01.xem-truoc')` khớp action `xemTruoc` qua `app('router')->getRoutes()->match(Request::create(...))`.
- [ ] **Step 2: Run** `DB_HOST=127.0.0.1 vendor/bin/phpunit tests/Unit/Tt12/Bh01/Bh01ControllerTest.php` — Expected: FAIL.
- [ ] **Step 3: Implement** controller, routes, menu, hai view.
- [ ] **Step 4: Run** lệnh Step 2 — Expected: PASS.
- [ ] **Step 5: Commit** `feat(bh01): man ho so tong hop 01/BH - xem truoc, tao ban, tai tep, gui cong`

### Task 8: Kiểm toàn bộ và nghiệm thu trên máy dev

**Files:** không tạo mới (sửa lỗi nếu lộ ra).

- [ ] **Step 1: Mốc test.** Chạy `DB_HOST=127.0.0.1 vendor/bin/phpunit --log-junit <scratchpad>/sau.xml` trên nhánh và trên `main` (`git worktree` hoặc checkout tạm), so **danh sách tên test đỏ**. Expected: không có test đỏ mới ngoài các test đỏ sẵn của `main` (gồm `CtdtCauHinhTest::gui_len_cong_mac_dinh_tat` đỏ có chủ đích).
- [ ] **Step 2: Migrate trên CSDL dev** — **hỏi chủ dự án trước**: `.env` dev trỏ CSDL thật `192.168.200.68/qlbv`. Chỉ chạy `php artisan migrate` khi được đồng ý.
- [ ] **Step 3: Nghiệm thu tay** (sau Step 2, có worker `tt12-ky`): mở `bhyt/tt12/bh01`, chọn `01929` kỳ 09/2026 → Xem trước: số hồ sơ = kết quả `SELECT COUNT(*) FROM xml3176_xml1s WHERE ma_cskcb='01929' AND nam_qt='2026' AND thang_qt='09' AND ma_the_bhyt<>''`; Tạo bản → ký xong → Tải tệp; mở tệp: gốc `HSTH01BH`, có `DS_CHITIET Id=`, `CHUKYDONVI` có `Signature`, đối chiếu tay 3 dòng với XML1.
- [ ] **Step 4: Không bấm "Gửi cổng"** trừ khi chủ dự án cho phép rõ ràng (cổng nhận là nhận thật).
- [ ] **Step 5: Commit** các sửa lỗi nếu có: `fix(bh01): ...`
