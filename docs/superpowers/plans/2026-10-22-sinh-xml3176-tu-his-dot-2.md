# Sinh XML3176 từ HIS — Đợt 2 (XML4 kết quả CLS + XML5 diễn biến) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Thêm XML4 (chi tiết kết quả cận lâm sàng) và XML5 (diễn biến lâm sàng) vào module `SinhXml3176`, sao cho đầu ra khớp hồ sơ HIS đang nạp trong tiền giám định.

**Architecture:** Hai lớp dựng mới `Xml4Dung`, `Xml5Dung` cài interface `DungBang` sẵn có. Dữ liệu nguồn mới được nạp thêm trong `HisNapHoSo`. Danh sách dịch vụ "tổng hợp" (HIS gọi là `list2`, dùng cho XML1/4/5) được tách thành helper dùng chung. Đăng ký hai lớp mới vào `DangKyBang` và `XmlCon`. Lệnh so sánh được mở rộng cho XML4/XML5 và thêm chế độ so với CSDL tiền giám định.

**Tech Stack:** Laravel 5.5, PHP 7.4 (cú pháp tương thích 7.0 như repo), `yajra/laravel-oci8`, PHPUnit 6, SQLite in-memory cho test.

**Spec:** `docs/superpowers/specs/2026-10-22-sinh-xml3176-tu-his-design.md`. Bản đồ dữ liệu: `docs/hisnguonmo-xml3176-anh-xa.md` (viết tắt **[AX]**): XML4 ở dòng 647–702, XML5 ở dòng 705–764, điều kiện ở lõi ở dòng 113–134. Mã DLL dịch ngược nằm tại `C:\Users\tracnn\AppData\Local\Temp\claude\C--Users-tracnn-qlbv\46be3a4a-4c25-4488-bfb9-47a8fb9acb0f\scratchpad\xml130\His.Bhyt.ExportXml.XML130.XML4\`, `...XML5\` và lõi `...XML130\HoSoProcessor.cs` (XML4 ở dòng 545–570, XML5 ở dòng 571–592). Nếu thư mục này đã mất, dịch lại bằng `ilspycmd -p -o <out> -r D:\his-x64\ReferencedAssemblies D:\his-x64\ReferencedAssemblies\His.Bhyt.ExportXml.XML130.XML4.dll` (XML5 làm tương tự).

**Nền có sẵn trên `main`** (Đợt 1 và các bản sửa sau nghiệm thu): `DungBang`, `LoiDungBang`, `GiaTriBang`, `ThoiGianHis`, `DuLieuHoSo`, `HisNapHoSo` (đã lọc đầu vào như plugin HIS bằng `thanhToanNhuHis`), `HisDanhMuc`, `XmlCon`, `PhongBi`, `DangKyBang`, `LuotQuet`, lệnh `sinhxml3176:quet` và `sinhxml3176:so-sanh`.

**Ngoài phạm vi:** XML6–XML15 (Đợt 3). Sau Đợt 2, chốt "đủ 14 bảng" vẫn **chặn** ghi thư mục thật, vì còn thiếu 9 bảng.

## Global Constraints

- **Nguyên tắc so với HIS (theo quyết định của chủ dự án sau nghiệm thu Đợt 1):** "HIS" là đầu ra thật của **plugin + DLL**, không chỉ DLL. Port đúng logic DLL, trên đầu vào đã lọc như plugin. Chỉ sửa:
  - lỗi làm vỡ (NullReference, phân tích số hỏng);
  - lỗi làm sai dữ liệu rõ ràng, ví dụ ký tự `;` thừa.

  Lệch về nghĩa thì **giữ như DLL** và ghi vào bảng "Khác biệt với HIS" với trạng thái `cần xác nhận`.
- **Chỉ đọc HIS:** chỉ `select` qua `DB::connection($cauHinh->ketNoiHis())`. Mọi kiểm tra trên Oracle thật chỉ đọc **metadata** (`all_views`, `all_tab_columns`), không đọc dòng dữ liệu, trừ Task 7 (nghiệm thu).
- **Cách ly:** không `use App\Services\Xml3176...`, `App\Services\Xml3176Service`, `App\Models\BHYT\Xml3176...` trong `app/Services/SinhXml3176/**` và `app/Console/Commands/SinhXml3176*.php`. `CachLyModuleTest` kiểm điều này.
- **Thẻ gốc XML con:**
  - XML4: `CHITIEU_CHITIET_DICHVUCANLAMSANG/DSACH_CHI_TIET_CLS/CHI_TIET_CLS`
  - XML5: `CHITIEU_CHITIET_DIENBIENLAMSANG/DSACH_CHI_TIET_DIEN_BIEN_BENH/CHI_TIET_DIEN_BIEN_BENH`
- **Trường và thứ tự:**
  - XML4: `MA_LK, STT, MA_DICH_VU, MA_CHI_SO, TEN_CHI_SO, GIA_TRI, DON_VI_DO, MO_TA, KET_LUAN, NGAY_KQ, MA_BS_DOC_KQ, DU_PHONG`
  - XML5: `MA_LK, STT, DIEN_BIEN_LS, GIAI_DOAN_BENH, HOI_CHAN, PHAU_THUAT, THOI_DIEM_DBLS, NGUOI_THUC_HIEN, DU_PHONG`

  Danh sách này khớp cột của `xml3176_xml4s` và `xml3176_xml5s`.
- **CDATA:**
  - XML4: `TEN_CHI_SO, GIA_TRI, DON_VI_DO, MO_TA, KET_LUAN`
  - XML5: `DIEN_BIEN_LS, GIAI_DOAN_BENH, HOI_CHAN, PHAU_THUAT`
- **Cắt độ dài** theo **số ký tự** (`mb_substr`, UTF-8), tối đa 1024, áp cho đúng các trường mà [AX] ghi "cắt 1024": XML4 `MO_TA`, `KET_LUAN`; XML5 `DIEN_BIEN_LS`, `HOI_CHAN`, `PHAU_THUAT`.
- **Thời gian:** `yyyyMMddHHmm`, tức 12 ký tự đầu của số HIS, qua `ThoiGianHis`.
- **Key cấu hình** chỉ đọc qua `HisDanhMuc::config()`. Key XML4 `HIS.QD_130_BYT.XML4.LAY_DVU_PTTT` trong DLL đọc từ cấu hình toàn cục máy trạm; module đọc từ `HIS_CONFIG`.
- **Test:** cấm `RefreshDatabase`. Giả HIS bằng `tests/Support/DungBangHisSinhXml3176Sqlite.php` (bảng SQLite được sinh từ các hằng `COT_*`). Chạy bằng `cd /c/Users/tracnn/qlbv && DB_HOST=127.0.0.1 vendor/bin/phpunit tests/Unit/SinhXml3176`.
- **Đặt tên** tiếng Việt không dấu; mỗi chỗ sửa so với DLL có một dòng docblock `Sua loi DLL [AX]:<dong>: ...`.
- **Commit** trên nhánh `feat/sinh-xml3176-dot-2`, tách từ `main`, không dùng worktree (bẫy junction `vendor`). Thông điệp kết thúc bằng một dòng trống rồi `Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>`.

## Review Focus

1. **Hồ sơ nội trú có tờ điều trị nhưng mọi tờ đều rỗng `CONTENT`.** DLL ra 0 dòng XML5 và không rơi về dữ liệu khám ([AX]:755). Module phải ra **0 dòng** giống HIS, không ném lỗi, và `dung()` trả `[]` để không có `FILEHOSO` XML5. Test ở Task 4.
2. **Nhân viên có `LOGINNAME` null trong danh mục.** DLL ném NullReference và mất cả XML5 ([AX]:759). Module bỏ qua dòng nhân viên đó; `NGUOI_THUC_HIEN` vẫn ra đúng. Test ở Task 4.
3. **Chỉ số XN có giá trị `VALUE` rỗng.** DLL vẫn sinh dòng ([AX]:659). Module cũng sinh dòng, `GIA_TRI` rỗng. Test ở Task 3.
4. **Dịch vụ CĐHA vừa có Suin vừa không có Tein.** Chỉ sinh dòng ở khối C, không sinh thêm dòng ở khối B ([AX]:660). Test ở Task 3.
5. **Dịch vụ CLS là dòng BHYT có tiền BH = 0 và nguồn khác = 0.** Dòng này nằm ngoài `list2` ([AX]:134), nên **không** vào XML4. Test ở Task 2 (helper) và Task 3.

---

## File Structure

| Tệp | Trách nhiệm |
|---|---|
| `app/Services/SinhXml3176/Nguon/HisNapHoSo.php` (sửa) | Thêm nguồn `theoDoi` (HIS_TRACKING), `hoiChan` (HIS_DEBATE), `chiSoCdha` (V_HIS_SERE_SERV_SUIN); thêm cột cho XML4/XML5 vào các hằng `COT_*` |
| `app/Services/SinhXml3176/Nguon/HisDanhMuc.php` (sửa) | Danh mục dịch vụ có `SUIM_INDEX_CODE/NAME`: thêm cột vào danh mục dịch vụ sẵn có, hoặc thêm `dichVuDanhMuc(int $serviceId)` |
| `app/Services/SinhXml3176/DuLieuHoSo.php` (sửa) | Thuộc tính public mới `$theoDoi`, `$hoiChan`, `$chiSoCdha`, mặc định `[]` |
| `app/Services/SinhXml3176/Bang/DichVuTongHop.php` (mới) | `list2` của lõi HIS, dùng chung cho XML1/4/5 |
| `app/Services/SinhXml3176/Bang/Xml1Dung.php` (sửa) | Dùng `DichVuTongHop` thay cho đoạn lọc nội bộ, hành vi không đổi |
| `app/Services/SinhXml3176/Bang/Xml4Dung.php` (mới) | Dựng XML4 |
| `app/Services/SinhXml3176/Bang/Xml5Dung.php` (mới) | Dựng XML5 |
| `app/Services/SinhXml3176/Bang/DangKyBang.php` (sửa) | `macDinh()` thêm Xml4Dung, Xml5Dung |
| `app/Services/SinhXml3176/XmlCon.php` (sửa) | Thẻ gốc và CDATA cho XML4/XML5 |
| `app/Console/Commands/SinhXml3176SoSanh.php` (sửa) | Khoá ghép XML4/XML5; chế độ `--csdl` so với bảng `xml3176_*` của tiền giám định (chỉ đọc) |
| `docs/trien-khai-sinh-xml3176.md` (sửa) | Bảng "Khác biệt với HIS" thêm mục XML4, XML5; mục "Nguồn HIS"; kết quả nghiệm thu |
| `tests/Unit/SinhXml3176/DichVuTongHopTest.php`, `Xml4DungTest.php`, `Xml5DungTest.php` (mới); `HisNapHoSoTest.php`, `DangKyBangTest.php`, `PhongBiTest.php`, `SinhXml3176SoSanhTest.php`, `Xml1DungTest.php` (sửa) | Test |

---

### Task 1: Nguồn dữ liệu cho XML4/XML5

**Files:** sửa `Nguon/HisNapHoSo.php`, `Nguon/HisDanhMuc.php`, `DuLieuHoSo.php`, `tests/Support/DungBangHisSinhXml3176Sqlite.php` (nếu cần), `tests/Unit/SinhXml3176/HisNapHoSoTest.php`; ghi mục "Nguồn HIS" trong `docs/trien-khai-sinh-xml3176.md`.

**Interfaces:**
- Produces: thuộc tính `DuLieuHoSo::$theoDoi` (dòng HIS_TRACKING, gắn theo `treatment_id`, sắp `tracking_time, id`), `$hoiChan` (HIS_DEBATE, theo `treatment_id`), `$chiSoCdha` (V_HIS_SERE_SERV_SUIN, theo `tdl_treatment_id`; nếu view không có cột này thì gắn qua `sere_serv_id` của `$dichVu`).
- Produces: `HisDanhMuc::dichVuDanhMuc($serviceId): ?stdClass`, có ít nhất `suim_index_code`, `suim_index_name`.
- Hằng cột phải bổ sung:
  - `COT_DICH_VU`: `description`, `conclude`, `subclinical_result_loginname`, `execute_loginname`, `result_read_time`, `end_time`, `finish_time`, `start_time`, `tracking_id`, `dhst_id`, `tdl_is_main_exam`, `tdl_service_type_id`, `tdl_intruction_time`, `service_id`, và các trường khám mà nhánh 2 XML5 đọc (`hospitalization_reason`, `pathological_process`, `pathological_history`, `pathological_history_family`, `full_exam`, `part_exam`, `subclinical`, `treatment_instruction`).
  - `COT_KET_QUA_CLS` (tein): `bhyt_code`, `bhyt_name`, `antibiotic_resistance_code`, `antibiotic_resistance_name`, `value`, `test_index_unit_name`, `result_description`.
  - `COT_DHST`: các chỉ số CSST ở [AX]:723 (`pulse, temperature, breath_rate, spo2, weight, height, belly, chest, urine, blood_pressure_max, blood_pressure_min, capillary_blood_glucose`) cùng `tracking_id`.

  Cột nào đã có thì giữ nguyên.

- [ ] **Step 0 (Oracle, chỉ metadata):** kiểm `all_views` / `all_tab_columns` cho `HIS_TRACKING`, `HIS_DEBATE`, `V_HIS_SERE_SERV_SUIN`, cùng các cột liệt kê ở trên trên `V_HIS_SERE_SERV_2`, `V_HIS_SERE_SERV_TEIN`, `HIS_DHST`, và nguồn `SUIM_INDEX_CODE/NAME` (`V_HIS_SERVICE` hoặc `HIS_SERVICE`). Cột nào **không có** thì tìm cột đúng (ví dụ trường khám có thể nằm trên `HIS_SERVICE_REQ`; khi đó nạp qua `service_req_id`). Ghi kết quả vào mục "Nguồn HIS".
- [ ] **Step 1: test hỏng** trong `HisNapHoSoTest`:
  - `test_nap_theo_doi_hoi_chan_chi_so_cdha`: mỗi nguồn mới gắn đúng hồ sơ.
  - `test_theo_doi_sap_theo_tracking_time`
  - `test_dich_vu_danh_muc_co_suim_index`
  - Test "mỗi bảng con một truy vấn" hiện có phải cập nhật số nguồn (thêm 3).
- [ ] **Step 2–4:** chạy hỏng → cài đặt → qua cả thư mục.
- [ ] **Step 5: Commit** `feat(sinh-xml3176): nap nguon du lieu cho XML4/XML5`

---

### Task 2: Danh sách dịch vụ tổng hợp (`list2`) dùng chung

**Files:** mới `Bang/DichVuTongHop.php`, `tests/Unit/SinhXml3176/DichVuTongHopTest.php`; sửa `Bang/Xml1Dung.php`.

**Interfaces:**
- Produces: `DichVuTongHop::loc(DuLieuHoSo $d, HisDanhMuc $dm): array`, trả các dòng `$d->dichVu` (giữ thứ tự) thoả lõi HIS `HoSoProcessor.cs:325–355` ([AX]:134):
  - `IS_NO_EXECUTE != 1` và `PRICE > 0`;
  - và một trong ba: dòng không phải BHYT, **hoặc** BHYT có `VIR_TOTAL_HEIN_PRICE > 0`, **hoặc** BHYT có `VIR_TOTAL_HEIN_PRICE == 0` mà `OTHER_SOURCE_PRICE > 0`;
  - cộng thêm mọi dòng `IS_NO_EXECUTE == 1`.

  BHYT nghĩa là `patient_type_id === $dm->idDoiTuongBhyt()`.
- `Xml1Dung` thay đoạn lọc nội bộ (`Xml1Dung.php` khoảng dòng 355–370) bằng `DichVuTongHop::loc`. **Mọi test `Xml1DungTest` hiện có giữ nguyên và phải qua.**

- [ ] **Step 1: test hỏng**
  - `test_bo_bhyt_khong_tien_bh_khong_nguon_khac`
  - `test_giu_bhyt_tien_bh_0_co_nguon_khac`
  - `test_giu_dong_khong_phai_bhyt`
  - `test_bo_gia_0`
  - `test_giu_thu_tu` (Review Focus 5)
- [ ] **Step 2–4:** chạy hỏng → cài đặt, chuyển Xml1Dung sang dùng helper → qua cả thư mục.
- [ ] **Step 5: Commit** `refactor(sinh-xml3176): tach danh sach dich vu tong hop (list2) dung chung`

---

### Task 3: Dựng XML4

**Files:** mới `Bang/Xml4Dung.php`, `tests/Unit/SinhXml3176/Xml4DungTest.php`.

**Interfaces:** cài `DungBang`, `loai() === 'XML4'`. Đầu vào là `DichVuTongHop::loc($d, $dm)`, `$d->ketQuaCls`, `$d->chiSoCdha`, `HisDanhMuc::dichVuDanhMuc`, `HisDanhMuc::nhanVien`. Không có `IS_3176` ([AX]:690).

**Nội dung:** port đúng [AX]:647–681 và mã `Xml4Processor.cs`:
- Bước lọc 1, cùng ba khối A (Tein), B (không chỉ số), C (Suin).
- STT tăng liên tục qua cả ba khối.
- Nguồn từng trường theo bảng [AX]:666–679, `NGAY_KQ` theo quy tắc `GetNgayKetQua`.

Sửa so với DLL:
- [AX]:695: `SUBCLINICAL_RESULT_LOGINNAME` là chuỗi rỗng thì coi như null, rơi về `EXECUTE_LOGINNAME`.
- [AX]:699: danh sách null hoặc rỗng không được ném lỗi.

Giữ như DLL và ghi `cần xác nhận`:
- [AX]:694: `MA_BS_DOC_KQ` rơi về login name thô;
- [AX]:696: mã và tên chỉ số chọn độc lập, có thể lệch cặp;
- [AX]:697: nguồn `NGAY_KQ` khác nhau giữa khối A/C và khối B.

- [ ] **Step 1: test hỏng**
  - `test_khoi_a_mot_dong_moi_tein_sap_theo_id`
  - `test_ma_chi_so_roi_ve_khang_sinh_do`
  - `test_tein_value_rong_van_sinh_dong` (Review Focus 3)
  - `test_khoi_b_dich_vu_khong_chi_so_lay_suim_index_danh_muc`
  - `test_co_suin_khong_sinh_khoi_b` (Review Focus 4)
  - `test_stt_lien_tuc_qua_ba_khoi`
  - `test_ngay_kq_cdha_uu_tien_result_read_time`
  - `test_lay_dvu_pttt_0_1_2`: option 1 loại PTTT có `tracking_id`
  - `test_ma_bs_doc_kq_lay_diploma_nguoi_dau_tien_co`
  - `test_ma_bs_doc_kq_rong_roi_ve_execute`
  - `test_mo_ta_ket_luan_cat_1024_ky_tu`
  - `test_bhyt_khong_tien_khong_vao_xml4` (Review Focus 5)
  - `test_khong_co_dich_vu_cls_tra_rong`
  - `test_thu_tu_khoa_dung_chi_tieu`
- [ ] **Step 2–4:** chạy hỏng → cài đặt → qua cả thư mục.
- [ ] **Step 5: Commit** `feat(sinh-xml3176): dung XML4`

---

### Task 4: Dựng XML5

**Files:** mới `Bang/Xml5Dung.php`, `tests/Unit/SinhXml3176/Xml5DungTest.php`.

**Interfaces:** cài `DungBang`, `loai() === 'XML5'`. Đầu vào là `DichVuTongHop::loc`, `$d->theoDoi`, `$d->dhst`, `$d->hoiChan`, `$d->pttt`, `HisDanhMuc::nhanVien`, `HisDanhMuc::config`.

**Nội dung:** port đúng [AX]:710–752 và mã `Xml5Processor.cs`:
- dịch vụ khám chính;
- nhánh 1 (tờ điều trị có `CONTENT`, sắp theo `TRACKING_TIME`);
- nhánh 2 (không có tờ điều trị nào, có khám chính → 1 dòng);
- chuỗi CSST đúng thứ tự `M, T, NT, SP, W, H, B, C, U, HA, CBG` nối bằng `_`, các mục `DIEN_BIEN_LS` nối bằng `"; "` với đúng tiền tố tiếng Việt như [AX]:723 và 737;
- `THOI_DIEM_DBLS` của nhánh 2 theo key `XML.EXPORT.4210.XML3.NGAY_YL_OPTION`;
- nhánh 2 bản 3176: `NGUOI_THUC_HIEN = DIPLOMA ?? IDENTIFICATION_NUMBER`.

Sửa so với DLL:
- [AX]:759: nhân viên có `LOGINNAME` null không làm vỡ.
- [AX]:761: `PHAU_THUAT` bỏ `DESCRIPTION` null, không có `;` thừa.
- [AX]:764: danh sách null hoặc rỗng không ném lỗi.

Giữ như DLL và ghi `cần xác nhận`:
- [AX]:755–756: tờ điều trị rỗng `CONTENT` thì ra 0 dòng;
- [AX]:757: ưu tiên `MODIFIER` trước `CREATOR`;
- [AX]:758: `??` không bắt chuỗi rỗng;
- [AX]:760: option "2" với `START_TIME` null cho giá trị rỗng;
- [AX]:762: `TDL_IS_MAIN_EXAM` không xét `IS_NO_EXECUTE`.

- [ ] **Step 1: test hỏng**
  - `test_nhanh_theo_doi_mot_dong_moi_to_co_content_sap_theo_thoi_gian`
  - `test_csst_dung_thu_tu_va_tien_to`: chuỗi kỳ vọng ví dụ `CSST<M80_T37_HA120/80>`; định dạng HA lấy đúng như DLL
  - `test_hoi_chan_distinct_noi_cham_phay`
  - `test_phau_thuat_chi_dich_vu_co_pttt_bo_mo_ta_null`
  - `test_nguoi_thuc_hien_uu_tien_modifier`
  - `test_moi_to_rong_content_ra_0_dong` (Review Focus 1)
  - `test_nhanh_kham_khi_khong_co_to_dieu_tri`
  - `test_nhanh_kham_3176_diploma_roi_ve_cccd`
  - `test_thoi_diem_dbls_nhanh_kham_theo_option`
  - `test_nhan_vien_loginname_null_khong_vo` (Review Focus 2)
  - `test_khong_kham_khong_to_tra_rong`
  - `test_dien_bien_cat_1024`
  - `test_thu_tu_khoa_dung_chi_tieu`
- [ ] **Step 2–4:** chạy hỏng → cài đặt → qua cả thư mục.
- [ ] **Step 5: Commit** `feat(sinh-xml3176): dung XML5`

---

### Task 5: Đăng ký XML4/XML5 vào phong bì

**Files:** sửa `Bang/DangKyBang.php`, `XmlCon.php`, `tests/Unit/SinhXml3176/DangKyBangTest.php`, `PhongBiTest.php`, `LuotQuetTest.php` (và helper gieo hồ sơ trong `tests/Support/` nếu cần thêm tờ điều trị/CLS).

**Interfaces:**
- `DangKyBang::macDinh()` trả `[Xml2Dung, Xml3Dung, Xml4Dung, Xml5Dung, Xml1Dung]`.
- `thieu()` sau Đợt 2 có 9 loại: XML6–XML11, XML13–XML15.
- `XmlCon::chuoi('XML4'|'XML5', ...)` sinh đúng thẻ gốc và CDATA ở Global Constraints.

- [ ] **Step 1: test hỏng**
  - `DangKyBangTest::test_dot_2_chua_du`: `thieu()` bằng đúng `['XML6','XML7','XML8','XML9','XML10','XML11','XML13','XML14','XML15']`
  - `PhongBiTest::test_xml4_xml5_cau_truc_importer_doc_duoc`: parse lại, có `DSACH_CHI_TIET_CLS->CHI_TIET_CLS` và `DSACH_CHI_TIET_DIEN_BIEN_BENH->CHI_TIET_DIEN_BIEN_BENH`, CDATA giữ được `]]>`
  - `LuotQuetTest`: một hồ sơ có CLS và tờ điều trị thì `cac_bang` là `XML1,XML2,XML3,XML4,XML5`; hồ sơ không có CLS thì không có `XML4`
- [ ] **Step 2–4:** chạy hỏng → cài đặt → qua cả thư mục. Chốt "đủ 14 bảng" vẫn chặn ghi thư mục thật; test sẵn có `test_tu_choi_ghi_thu_muc_that_khi_thieu_bang` vẫn qua, output có `XML6`.
- [ ] **Step 5: Commit** `feat(sinh-xml3176): dang ky XML4 XML5 vao phong bi`

---

### Task 6: So sánh XML4/XML5 và chế độ so với CSDL tiền giám định

**Files:** sửa `app/Console/Commands/SinhXml3176SoSanh.php`, `tests/Unit/SinhXml3176/SinhXml3176SoSanhTest.php`.

**Interfaces:**
- Khoá ghép dòng:
  - XML4: `MA_LK + MA_DICH_VU + MA_CHI_SO + NGAY_KQ`
  - XML5: `MA_LK + THOI_DIEM_DBLS`

  Trùng khoá thì thêm hậu tố `#n` như các bảng hiện có.
- Tuỳ chọn mới `--csdl`. Khi có tuỳ chọn này, phía HIS lấy từ bảng `xml3176_xml{n}s` và `xml3176_informations` của CSDL qlbv, qua connection mặc định, **chỉ `select`**, đúng các `MA_LK` có trong `--qlbv`. Khi đó không cần `--his`. Đọc bằng query builder theo tên bảng; **không** dùng model hay service của module tiền giám định (`CachLyModuleTest`).
- Chuẩn hoá trước khi so (học từ nghiệm thu Đợt 1):
  - rỗng/null và `0` ở cột số coi là như nhau;
  - chỉ cắt số 0 ở **phần thập phân**, không cắt ở số nguyên;
  - cột tiền so theo giá trị số.

- [ ] **Step 1: test hỏng**
  - `test_xml4_xml5_ghep_dung_khoa`
  - `test_che_do_csdl_doc_bang_tien_giam_dinh`: SQLite giả bảng `xml3176_xml1s` / `xml3176_xml4s`, có một trường lệch thì ra đúng một dòng CSV
  - `test_so_nguyen_khong_bi_cat_so_0`: `17000` và `17000.00` bằng nhau; `17000` và `17` khác nhau
  - `test_rong_bang_0_o_cot_so`
- [ ] **Step 2–4:** chạy hỏng → cài đặt → qua cả thư mục.
- [ ] **Step 5: Commit** `feat(sinh-xml3176): so sanh XML4 XML5 va che do --csdl`

---

### Task 7: Nghiệm thu với hồ sơ thật và cập nhật tài liệu

Chạy trên máy dev. Khối `sinh_xml3176` trong `config/organization.php` đã có sẵn (`ghi_thu_muc_that=false`). Bước này đọc dữ liệu HIS thật (`SELECT`) và CSDL qlbv (`SELECT`), chỉ ghi vào thư mục thử.

- [ ] **Step 1:** sinh lại 2 hồ sơ đã nghiệm thu ở Đợt 1:
  ```bash
  php artisan sinhxml3176:quet --ma-dieu-tri=000007130131 --ma-dieu-tri=000007436231
  ```
  Expected: `da_ghi=2`, file có `FILEHOSO` XML4 và XML5 khi hồ sơ có dữ liệu.
- [ ] **Step 2:** so với tiền giám định:
  ```bash
  php artisan sinhxml3176:so-sanh --csdl --qlbv=D:\XML\SinhXml3176Thu --ra=storage\app\so-sanh-dot2.csv --bang=XML1,XML2,XML3,XML4,XML5
  ```
- [ ] **Step 3:** xếp từng dòng lệch vào một trong ba loại:
  1. lỗi DLL đã biết;
  2. dòng có sẵn trong bảng "Khác biệt với HIS";
  3. **lỗi module**: sửa, có test, rồi lặp lại Step 1–2.

  Đạt khi không còn dòng thuộc loại 3. Với lệch XML4/XML5 chưa rõ nguyên nhân, tra dữ liệu HIS bằng `SELECT` như cách đã làm ở Đợt 1 (cờ dòng, thời gian), rồi ghi kết luận.
- [ ] **Step 4:** cập nhật `docs/trien-khai-sinh-xml3176.md`:
  - thêm mục "XML4" và "XML5" trong bảng "Khác biệt với HIS", gồm mọi dòng `đã sửa` / `cần xác nhận` / `giữ như DLL` của Task 3–4 và các lệch đo được ở Step 3;
  - ghi kết quả nghiệm thu Đợt 2 (số hồ sơ, số dòng lệch theo loại);
  - ghi việc `LAY_DVU_PTTT` nay đọc từ `HIS_CONFIG`.
- [ ] **Step 5: Commit** `docs(sinh-xml3176): nghiem thu dot 2 va bang khac biet XML4 XML5`
