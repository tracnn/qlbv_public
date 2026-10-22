# Sinh XML3176 từ HIS — Đợt 3 (XML6–XML11, XML13–XML15) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Thêm 9 bảng còn lại (XML6, 7, 8, 9, 10, 11, 13, 14, 15) để module `SinhXml3176` đủ 14 bảng, khớp hồ sơ HIS trong tiền giám định, rồi chuẩn bị điều kiện cho phép ghi thư mục thật.

**Architecture:** Mỗi bảng có một lớp `XmlNDung` cài `DungBang`. Bảng nào chung logic thì gom vào một task (giấy ra viện/tóm tắt bệnh án; hai loại giấy nghỉ; chuyển tuyến/hẹn khám). Nguồn HIS mới được nạp trong `HisNapHoSo`. Đăng ký vào `DangKyBang`/`XmlCon` ở một task riêng. Sau Đợt 3, `DangKyBang::daDu()` trả true; khi đó chốt "đủ 14 bảng" không còn chặn, và việc bật ghi thư mục thật vẫn chỉ do `ghi_thu_muc_that === true` quyết định.

**Tech Stack:** Laravel 5.5, PHP 7.4 (cú pháp tương thích 7.0 như repo), `yajra/laravel-oci8`, PHPUnit 6, SQLite in-memory cho test.

**Spec:** `docs/superpowers/specs/2026-10-22-sinh-xml3176-tu-his-design.md`. Bản đồ dữ liệu: `docs/hisnguonmo-xml3176-anh-xa.md` (viết tắt **[AX]**):
- XML6: dòng 768–840; XML7: 841–899; XML8: 900–983; tổng hợp 984–1009
- quy ước XML9–15: 1014–1047; XML9: 1048–1100; XML10: 1101–1132; XML11: 1133–1175; XML13: 1231–1297; XML14: 1298–1346; XML15: 1347–1386; tóm tắt IS_3176: 1387–1402
- điều kiện ở lõi: 113–134

Mã DLL dịch ngược: `C:\Users\tracnn\AppData\Local\Temp\claude\C--Users-tracnn-qlbv\46be3a4a-4c25-4488-bfb9-47a8fb9acb0f\scratchpad\xml130\His.Bhyt.ExportXml.XML130.XML6\` … `XML15\` và lõi `...XML130\HoSoProcessor.cs` (dòng 590–790). Nếu thư mục đã mất, dịch lại bằng `ilspycmd -p -o <out> -r D:\his-x64\ReferencedAssemblies D:\his-x64\ReferencedAssemblies\His.Bhyt.ExportXml.XML130.XML<n>.dll`. Plugin: `C:\Users\tracnn\hisnguonmo\HIS\Plugins\HIS.Desktop.Plugins.ExportXmlQD130\UCExportXml.cs` (dòng 1180–1460).

**Nền có sẵn trên `main` (d96e8cf4):**
- `DungBang`, `LoiDungBang`, `GiaTriBang`, `ThoiGianHis`, `DichVuTongHop` (list2), `DoiTuongKcbXml1` (suy `MA_DOITUONG_KCB`)
- `Xml1Dung`…`Xml5Dung`
- `HisNapHoSo` (lọc đầu vào như plugin), `HisDanhMuc`, `XmlCon`, `PhongBi`, `DangKyBang`, `LuotQuet`
- lệnh `sinhxml3176:quet`, `sinhxml3176:so-sanh` (có `--csdl`)

**Ngoài phạm vi:** XML12 (giám định y khoa; tiền giám định không nạp). Push và bật ghi thư mục thật trên máy chủ do chủ dự án làm.

## Global Constraints

- **Nguyên tắc so với HIS** (giữ từ Đợt 2): "HIS" là đầu ra thật của **plugin + DLL**. Port đúng logic DLL trên đầu vào đã lọc như plugin. Chỉ sửa:
  - lỗi làm vỡ: NullReference, IndexOutOfRange, `int.Parse` hỏng, `.First()` trên danh sách rỗng;
  - lỗi làm sai dữ liệu rõ ràng: dòng lặp, ký tự `;` thừa, tên cha/mẹ bị đảo do thứ tự ưu tiên toán tử, cắt chuỗi đứt giữa ký tự.

  Lệch về nghĩa thì **giữ như DLL** và ghi vào bảng "Khác biệt với HIS" với trạng thái `cần xác nhận`. Mỗi chỗ sửa có một dòng docblock `Sua loi DLL [AX]:<dong>: ...`.
- **QĐ3176 áp vô điều kiện**: dùng nhánh `IS_3176` của DLL.
- **MA_TTDV** ở mọi bảng theo đúng quyết định chủ dự án đã chốt cho XML1: key `HIS.QD_130_BYT.MA_TTDV_FROM_PROFILE_XML` có giá trị thì đi trọn nhánh QĐ130 của DLL (mẫu chung ở [AX]:1025, kiểm "chứa số hiệu bảng" như DLL từng bảng); key rỗng thì dùng `DIPLOMA` của (phó) giám đốc như nhánh IS_3176. Dùng chung một helper, không chép lại logic.
- **Chọn bản ghi như plugin:**
  - HIV: plugin sắp `HIS_HIV_TREATMENT` theo `ID` rồi ghi đè vào từ điển theo `TREATMENT_ID`, nên lấy **ID lớn nhất**.
  - Lao: plugin ghi đè `HIS_TUBERCULOSIS_TREAT` theo thứ tự trả về, nên lấy **bản ghi cuối theo `id` tăng**.
- **Chỉ đọc HIS**: chỉ `select` qua `DB::connection($cauHinh->ketNoiHis())`. Kiểm Oracle chỉ đọc **metadata** (`all_views`, `all_tab_columns`), trừ Task 7.
- **Cách ly**: không `use App\Services\Xml3176...`, `App\Services\Xml3176Service`, `App\Models\BHYT\Xml3176...` trong `app/Services/SinhXml3176/**` và `app/Console/Commands/SinhXml3176*.php`. Chú thích không viết dính `QD130` hay `XML130`; viết `QD 130`, `XML 130` (test chốt `KhongConXmlCuTest`).
- **Thẻ gốc** lấy đúng theo [AX] từng bảng. Bảng phẳng (XML7, 8, 10, 11, 13, 14) có một bản ghi và thẻ gốc chính là bản ghi. Trường, thứ tự trường và CDATA lấy theo lớp `XML<n>Data`/`XML<n>DetailData` của DLL, đối chiếu cột migration `xml3176_xml<n>s`.
- **Phần tử null**: chỗ nào DLL gán `null` (XmlSerializer bỏ phần tử) thì module xuất phần tử rỗng. So sánh coi hai cách là như nhau. Ghi chung một dòng trong bảng khác biệt.
- **Cắt độ dài** theo ký tự (`mb_substr`), chỉ ở trường mà [AX] ghi có cắt.
- **Test**: cấm `RefreshDatabase`. Giả HIS bằng `tests/Support/DungBangHisSinhXml3176Sqlite.php`. Chạy `cd /c/Users/tracnn/qlbv && DB_HOST=127.0.0.1 vendor/bin/phpunit tests/Unit/SinhXml3176`. Đặt tên tiếng Việt không dấu.
- **Commit** trên nhánh `feat/sinh-xml3176-dot-3`, tách từ `main`, không dùng worktree. Thông điệp kết thúc bằng một dòng trống rồi `Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>`.

## Review Focus

1. **Hồ sơ có nhiều bản ghi HIV hoặc nhiều bản ghi lao.** Phải chọn đúng bản như plugin: HIV lấy ID lớn nhất, lao lấy bản cuối. Không được sinh nhiều dòng. Test ở Task 2.
2. **XML11 có ICD_TEXT ít phần tử hơn ICD_SUB_CODE.** DLL ném IndexOutOfRange và mất cả bảng. Module ghép đến đâu được đến đó, không ném. Test ở Task 4.
3. **XML13 hồ sơ chuyển viện không có dòng dịch vụ nào trong list2.** DLL ném lỗi LINQ và mất cả bảng. Module vẫn sinh XML13, các trường dịch vụ để rỗng. Test ở Task 5.
4. **Hồ sơ không thuộc loại nào của XML7–15** (khám ngoại trú bình thường, không sinh, không chuyển, không hẹn). Mọi lớp dựng mới trả `[]`; phong bì chỉ có XML1–5. Test ở Task 6.
5. **`OUT_CODE` ngắn hơn 6 ký tự khi gặp placeholder `<#OUT_CODE_NUM_…;>`** (XML13). DLL `int.Parse(Substring)` vỡ. Module giữ nguyên chuỗi gốc và không ném. Test ở Task 5.

---

## File Structure

| Tệp | Trách nhiệm |
|---|---|
| `app/Services/SinhXml3176/Nguon/HisNapHoSo.php` (sửa) | Nguồn `hivTreatment` (HIS_HIV_TREATMENT), `dieuTriLao` (HIS_TUBERCULOSIS_TREAT); thêm cột hồ sơ/PTA/sơ sinh mà XML6–15 cần |
| `app/Services/SinhXml3176/DuLieuHoSo.php` (sửa) | `$hivTreatment` (?stdClass, chọn ID lớn nhất), `$dieuTriLao` (?stdClass, bản cuối) |
| `app/Services/SinhXml3176/Bang/MaTtdv.php` (mới) | `MaTtdv::cua(DuLieuHoSo, HisDanhMuc, int $soBang): string`. `Xml1Dung` chuyển sang dùng helper này, hành vi không đổi |
| `app/Services/SinhXml3176/Bang/Xml6Dung.php` … `Xml15Dung.php` (mới, trừ 12) | Dựng từng bảng |
| `app/Services/SinhXml3176/Bang/DangKyBang.php`, `XmlCon.php` (sửa) | Đăng ký và tuần tự hoá 9 bảng |
| `app/Console/Commands/SinhXml3176SoSanh.php` (sửa) | Khoá ghép XML6–15 |
| `docs/trien-khai-sinh-xml3176.md` (sửa) | Bảng khác biệt XML6–15, nghiệm thu Đợt 3, danh sách kiểm trước khi bật thư mục thật |
| `tests/Unit/SinhXml3176/Xml{6..15}DungTest.php`, `MaTtdvTest.php` (mới); `HisNapHoSoTest`, `DangKyBangTest`, `PhongBiTest`, `LuotQuetTest`, `SinhXml3176QuetTest`, `SinhXml3176SoSanhTest`, `Xml1DungTest` (sửa) | Test |

---

### Task 1: Nguồn dữ liệu và helper MA_TTDV

**Files:** sửa `Nguon/HisNapHoSo.php`, `DuLieuHoSo.php`, `Bang/Xml1Dung.php`, `tests/Unit/SinhXml3176/HisNapHoSoTest.php`, `Xml1DungTest.php`; tạo `Bang/MaTtdv.php`, `tests/Unit/SinhXml3176/MaTtdvTest.php`; ghi mục "Nguồn HIS".

**Interfaces:**
- Produces `DuLieuHoSo::$hivTreatment` (`?stdClass`) và `DuLieuHoSo::$dieuTriLao` (`?stdClass`), mặc định `null`, chọn đúng như Global Constraints.
- Produces hằng cột bổ sung trên `COT_HO_SO`, `COT_DIEN_DIEU_TRI`, `COT_SO_SINH`: hợp các cột mà [AX] XML6–15 đọc. Ví dụ: `treatment_end_type_ext_id`, `treatment_end_type_id`, `tran_pati_*`, `appointment_*`, `is_chronic`, `issued_date`, `out_code`, `sick_*`, `end_dept_subs_head_loginname`, các trường thai sản/nghỉ việc, mẹ/cha. Mỗi cột phải ứng với một trường [AX] cụ thể; ghi bảng ánh xạ trong báo cáo.
- Produces `MaTtdv::cua(DuLieuHoSo $d, HisDanhMuc $dm, int $soBang): string` theo Global Constraints. Phần "key chứa số hiệu bảng" so đúng như DLL của từng bảng; Xml1Dung truyền `1`.

- [ ] **Step 0 (Oracle, chỉ metadata):** kiểm `HIS_HIV_TREATMENT`, `HIS_TUBERCULOSIS_TREAT` và mọi cột mới trên `V_HIS_TREATMENT_12`, `V_HIS_PATIENT_TYPE_ALTER`, `V_HIS_BABY`. Cột nào không có thì tìm đúng đối tượng. Ghi kết quả vào mục "Nguồn HIS".
- [ ] **Step 1: test hỏng**
  - `test_hiv_lay_id_lon_nhat` (Review Focus 1)
  - `test_lao_lay_ban_cuoi` (Review Focus 1)
  - `test_khong_co_hiv_lao_la_null`
  - test "mỗi bảng con một truy vấn" cập nhật số nguồn (+2)
  - `MaTtdvTest`: `test_key_rong_dung_diploma_giam_doc`, `test_key_chua_so_bang_dung_ma_bhxh`, `test_key_khong_chua_so_bang_dung_representative`, `test_khong_co_login_dung_representative`
  - mọi test `Xml1DungTest` hiện có giữ nguyên và phải qua
- [ ] **Step 2–4:** chạy hỏng → cài đặt → qua cả thư mục.
- [ ] **Step 5: Commit** `feat(sinh-xml3176): nguon HIV lao va helper MA_TTDV`

---

### Task 2: XML6 (HIV/AIDS) và XML15 (điều trị lao)

**Files:** tạo `Bang/Xml6Dung.php`, `Bang/Xml15Dung.php` cùng test tương ứng.

**Interfaces:** cài `DungBang`, `loai()` là `'XML6'` và `'XML15'`. Đầu vào là `$d->hivTreatment`, `$d->dieuTriLao`, `$d->hoSo`, `HisDanhMuc`.

**Nội dung:**
- XML6 theo [AX]:768–840. Điều kiện giữ như DLL: `TREATMENT_ID != ID`, [AX]:832, ghi `cần xác nhận`. Bản 3176 dùng `SO_CCCD = CCCD ?? CMND ?? hộ chiếu`.
- XML15 theo [AX]:1347–1386, điều kiện là có bản ghi lao với `ID != 0`. Sửa so với DLL:
  - lặp dòng `new List { x, x }`: **sinh đúng 1 dòng**;
  - `STT` gán cứng `"15"`: **`STT = 1`**.

  Bản 3176: `SO_CCCD` như XML6.

- [ ] **Step 1: test hỏng**
  - `test_xml6_mot_dong_tu_hiv`
  - `test_xml6_khong_hiv_tra_rong`
  - `test_xml6_dieu_kien_treatment_id_khac_id_giu_nhu_dll`
  - `test_xml15_mot_dong_stt_1`
  - `test_xml15_khong_lao_tra_rong`
  - `test_so_cccd_roi_ve_cmnd_ho_chieu`
  - `test_thu_tu_khoa_dung_chi_tieu` cho từng bảng
- [ ] **Step 2–4:** chạy hỏng → cài đặt → qua cả thư mục.
- [ ] **Step 5: Commit** `feat(sinh-xml3176): dung XML6 XML15`

---

### Task 3: XML7 (giấy ra viện) và XML8 (tóm tắt hồ sơ bệnh án)

**Files:** tạo `Bang/Xml7Dung.php`, `Bang/Xml8Dung.php` cùng test.

**Nội dung:**
- **XML7** theo [AX]:841–899. Điều kiện: `TDL_TREATMENT_TYPE_ID ∈ {2,3,4}`. PTA dùng cho XML7 chọn như lõi HIS ([AX]:126): ưu tiên đối tượng BHYT theo key `HIS.HIS_PATIENT_TYPE.PATIENT_TYPE_CODE.BHYT`, sau đó `LOG_TIME/ID` mới nhất.
- **XML8** theo [AX]:900–983. Điều kiện như [AX]:905. `MA_LOAI_KCB` lấy theo bảng ở [AX]:908.
- Sửa so với DLL:
  - XML8 `HO_TEN_CHA/HO_TEN_ME` bị đảo do thứ tự ưu tiên toán tử: làm đúng chiều như XML7.
  - XML8 `TOMTAT_KQ` cắt theo byte: cắt 2000 **ký tự**.
  - `CHAN_DOAN_RV` có `;` thừa khi `ICD_TEXT` null: bỏ `;` thừa, như đã làm ở XML1.
  - `.First()` / NullReference ở các danh sách thì trả rỗng, không ném.
- Giữ như DLL và ghi `cần xác nhận`: `SO_NGAY_NGHI` không cộng 1; XML7 so config `"7"` không trim; phạm vi XML7 rộng hơn XML8.

- [ ] **Step 1: test hỏng**
  - `test_xml7_chi_ngoai_noi_tru_ban_ngay`
  - `test_xml7_pta_uu_tien_bhyt`
  - `test_xml7_ten_cha_me`
  - `test_xml8_loai_ngoai_tru_man_tinh`
  - `test_xml8_ma_loai_kcb_theo_bang`: mỗi nhánh một ca, kể cả "09" là nội trú dưới 4 giờ
  - `test_xml8_ten_cha_me_khong_bi_dao`
  - `test_xml8_tomtat_kq_cat_theo_ky_tu`
  - `test_chan_doan_rv_khong_dau_cham_phay_thua`
  - `test_khong_dieu_kien_tra_rong`
  - `test_thu_tu_khoa_dung_chi_tieu`
- [ ] **Step 2–4:** chạy hỏng → cài đặt → qua cả thư mục.
- [ ] **Step 5: Commit** `feat(sinh-xml3176): dung XML7 XML8`

---

### Task 4: XML9 (giấy chứng sinh), XML10 (nghỉ dưỡng thai), XML11 (nghỉ việc hưởng BHXH)

**Files:** tạo `Bang/Xml9Dung.php`, `Xml10Dung.php`, `Xml11Dung.php` cùng test.

**Nội dung:**
- **XML9** theo [AX]:1048–1100, mỗi `V_HIS_BABY` của hồ sơ một dòng.
- **XML10** theo [AX]:1101–1132, điều kiện `TREATMENT_END_TYPE_EXT_ID == 2`.
- **XML11** theo [AX]:1133–1175, điều kiện `TREATMENT_END_TYPE_EXT_ID == 1`, `MAU_SO = "CT07"`.
- Sửa so với DLL: XML11 ghép ICD phụ với tên không ném IndexOutOfRange, thiếu tên thì để trống (Review Focus 2); `.First()` trên PTA thì trả rỗng, không ném.
- Giữ như DLL và ghi `cần xác nhận`:
  - XML9 `NGAY_CT` lấy `IN_DATE` (đè `ISSUED_DATE`);
  - XML10 bản 3176 gán cứng `CHAN_DOAN_RV = "dưỡng thai"`;
  - XML10 `MA_BS` / `TEN_BS` có thể là hai người khác nhau;
  - XML11 `NGAY_CT` là ngày vào viện.

- [ ] **Step 1: test hỏng**
  - `test_xml9_moi_so_sinh_mot_dong`
  - `test_xml9_gioi_tinh_doi_ma`: `GENDER_ID` 1→"2", 2→"1", khác→"3"
  - `test_xml10_chi_khi_end_type_ext_2`
  - `test_xml10_chan_doan_duong_thai_giu_nhu_dll`
  - `test_xml11_chi_khi_end_type_ext_1_mau_ct07`
  - `test_xml11_icd_text_thieu_khong_vo` (Review Focus 2)
  - `test_ma_ttdv_dung_helper` cho từng bảng
  - `test_thu_tu_khoa_dung_chi_tieu` cho từng bảng
- [ ] **Step 2–4:** chạy hỏng → cài đặt → qua cả thư mục.
- [ ] **Step 5: Commit** `feat(sinh-xml3176): dung XML9 XML10 XML11`

---

### Task 5: XML13 (giấy chuyển tuyến) và XML14 (giấy hẹn khám lại)

**Files:** tạo `Bang/Xml13Dung.php`, `Xml14Dung.php` cùng test.

**Nội dung:**
- **XML13** theo [AX]:1231–1297. Điều kiện ở lõi: `TREATMENT_END_TYPE_ID == 2`. Bản 3176:
  - `MA_NOI_DI`, `MA_NOI_DEN`, `TEN_DICH_VU`, `TEN_THUOC`, `PP_DIEU_TRI` gán null: giữ như DLL, ghi `cần xác nhận`;
  - thêm `MA_CSKCB_DI`, `MA_CSKCB_DEN`, `TINH_TRANG_CT` ở đúng vị trí như lớp `XML13Data`.

  Danh sách dịch vụ đầu vào là `DichVuTongHop::loc`. Sửa so với DLL:
  - list2 rỗng không làm mất bảng (Review Focus 3);
  - `int.Parse(OUT_CODE.Substring(0,6))` không ném (Review Focus 5);
  - hai danh sách `MA_THE_BHYT` và `GT_THE_DEN` giữ khớp vị trí, giống cách đã sửa ở XML1.
- **XML14** theo [AX]:1298–1346. Điều kiện ở lõi: `TREATMENT_END_TYPE_ID == 3`. `MA_DOITUONG_KCB` dùng lại `DoiTuongKcbXml1` (cùng luật và cùng danh mục `his_medi_org`), không chép logic. Bản 3176 có thêm `NGAY_HEN_KL` và `MA_BENH_YHCT` (13 mã).

- [ ] **Step 1: test hỏng**
  - `test_xml13_chi_khi_chuyen_vien`
  - `test_xml13_3176_bo_nam_truong_va_them_cskcb`
  - `test_xml13_khong_dich_vu_van_sinh` (Review Focus 3)
  - `test_xml13_out_code_ngan_khong_vo` (Review Focus 5)
  - `test_xml13_the_bhyt_khop_vi_tri`
  - `test_xml14_chi_khi_end_type_3`
  - `test_xml14_ma_doituong_kcb_dung_lai_logic_xml1`
  - `test_xml14_ngay_hen_kl`
  - `test_thu_tu_khoa_dung_chi_tieu` cho từng bảng
- [ ] **Step 2–4:** chạy hỏng → cài đặt → qua cả thư mục.
- [ ] **Step 5: Commit** `feat(sinh-xml3176): dung XML13 XML14`

---

### Task 6: Đăng ký đủ 14 bảng, so sánh XML6–15, chốt bật thư mục thật

**Files:** sửa `Bang/DangKyBang.php`, `XmlCon.php`, `app/Console/Commands/SinhXml3176SoSanh.php`, `tests/Unit/SinhXml3176/DangKyBangTest.php`, `PhongBiTest.php`, `LuotQuetTest.php`, `SinhXml3176QuetTest.php`, `SinhXml3176SoSanhTest.php`.

**Interfaces:**
- `DangKyBang::macDinh()` có đủ 14 lớp dựng; XML1 vẫn dựng sau cùng. `daDu()` trả true, `thieu()` trả `[]`.
- `XmlCon` có thẻ gốc, thẻ danh sách, thẻ bản ghi và CDATA cho XML6–15 theo DLL. Đối chiếu với `app/Services/Xml3176Service.php` `storeXml3176Xml6..15` (chỉ đọc, không tham chiếu từ mã module).
- So sánh: khoá ghép
  - XML9: `MA_LK + SO_CHUNG_SINH` (rỗng thì `+ NGAY_SINH_CON`)
  - XML6, 15: `MA_LK + STT`
  - bảng phẳng: `MA_LK`

- [ ] **Step 1: test hỏng**
  - `DangKyBangTest::test_dot_3_du_14_bang`: `daDu() === true`, `thieu() === []`
  - `PhongBiTest::test_xml6_den_15_cau_truc_importer_doc_duoc`: mỗi bảng parse lại được và có đúng thẻ
  - `LuotQuetTest::test_ho_so_kham_thuong_chi_xml1_den_5` (Review Focus 4)
  - `LuotQuetTest::test_ho_so_chuyen_vien_co_xml13`
  - `SinhXml3176QuetTest`: test chặn thiếu bảng đổi thành `test_ghi_thu_muc_that_cho_phep_khi_du_bang`, tức `ghi_thu_muc_that = true` với cấu hình hợp lệ thì lệnh chạy và ghi vào `thu_muc` (thư mục tạm của test); `ghi_thu_muc_that` khác `true` vẫn ghi vào `thu_muc_thu`
  - `SinhXml3176SoSanhTest::test_xml9_xml13_ghep_dung_khoa`
- [ ] **Step 2–4:** chạy hỏng → cài đặt → qua cả thư mục.
- [ ] **Step 5: Commit** `feat(sinh-xml3176): du 14 bang, so sanh XML6-15`

---

### Task 7: Nghiệm thu thật, đo hiệu năng, tài liệu bật thư mục thật

Chạy trên máy dev. Khối `sinh_xml3176` đã có, `ghi_thu_muc_that=false`. Bước này chỉ `SELECT` lên HIS và các bảng `xml3176_*`; ghi duy nhất vào bảng theo dõi riêng của module và thư mục thử.

- [ ] **Step 1:** chọn hồ sơ phủ các bảng. Chạy `SELECT` chỉ lấy `treatment_code` (không lấy dữ liệu bệnh nhân) để tìm, trong 30 ngày gần đây có `FEE_LOCK_TIME` và đã có trong `xml3176_informations`, ít nhất mỗi loại một hồ sơ:
  - chuyển viện (XML13)
  - hẹn khám (XML14)
  - có sơ sinh (XML9)
  - nghỉ dưỡng thai (XML10)
  - nghỉ việc (XML11)
  - HIV (XML6)
  - lao (XML15)
  - nội trú (XML7, XML8)

  Loại nào không tìm được thì ghi lại.
- [ ] **Step 2:** chạy `php artisan sinhxml3176:quet --ma-dieu-tri=<…>` cho các hồ sơ đó, cộng 3 hồ sơ đã nghiệm thu ở Đợt 2. Đo `memory_get_peak_usage(true)` và thời gian bằng log mà lệnh đã ghi. Thêm một lượt lô 20 hồ sơ nội trú lớn nhất trong 7 ngày gần đây để đo bộ nhớ và thời gian khi có CLOB (khuyến nghị từ review Đợt 2).
- [ ] **Step 3:** chạy
  ```bash
  php artisan sinhxml3176:so-sanh --csdl --qlbv=D:\XML\SinhXml3176Thu --ra=storage\app\so-sanh-dot3.csv --bang=XML1,XML2,XML3,XML4,XML5,XML6,XML7,XML8,XML9,XML10,XML11,XML13,XML14,XML15
  ```
  Phân loại từng dòng lệch như Đợt 2:
  1. lỗi DLL đã biết;
  2. dòng có sẵn trong bảng khác biệt;
  3. **lỗi module**: sửa có test, commit `fix(sinh-xml3176): ...`, rồi lặp lại.

  Lệch về nghĩa thì không sửa mã, ghi lại để controller quyết.
- [ ] **Step 4:** cập nhật `docs/trien-khai-sinh-xml3176.md`:
  - mục XML6–XML15 trong bảng "Khác biệt với HIS";
  - kết quả nghiệm thu Đợt 3, kèm số đo bộ nhớ và thời gian;
  - **"Danh sách kiểm trước khi bật `ghi_thu_muc_that`"**:
    - đối chiếu bộ lọc với màn "Thiết lập đồng bộ" của máy trạm HIS;
    - `CACHE_DRIVER` không phải `array`;
    - PHP 64-bit;
    - đếm `is_delete is null` trên `v_his_treatment_12`;
    - đã chạy migration bảng theo dõi trên máy chủ;
    - đã chạy `config:cache`;
    - `memory_limit` và kích thước lô theo số đo ở Step 2;
    - thông tuyến tắt cho tới khi đo độ trễ nhập `OUT_TIME`;
    - chạy song song với HIS, bản vào sau thắng;
    - cách tắt nhanh: `enabled=false` rồi `config:cache`.
- [ ] **Step 5: Commit** `docs(sinh-xml3176): nghiem thu dot 3 va danh sach kiem truoc khi bat`
