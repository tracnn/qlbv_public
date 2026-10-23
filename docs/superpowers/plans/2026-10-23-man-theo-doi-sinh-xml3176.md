# Màn theo dõi sinh XML3176 — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Màn quản trị cho module sinh XML3176 với các chức năng: lọc hồ sơ theo ngày, xem lịch sử từng lần sinh, xem XML đã lưu của từng lần dạng bảng có cột, so hai lần sinh, và cho phép `superadministrator` xoá bản lưu trữ.

**Architecture:**
- Module ghi thêm bản sao tệp vào `thu_muc_luu\<yyyyMMdd>\<id>_<tên tệp>` sau mỗi lần `da_ghi`. Việc này do lớp `LuuTruXml` đảm nhiệm.
- Logic đọc tệp GIAMDINHHS và ghép/so trường được tách khỏi lệnh `sinhxml3176:so-sanh` thành hai lớp dùng chung là `DocTepGiamDinh` và `SoSanhBang`.
- Màn hình là một controller `BHYT\SinhXml3176Controller`: blade AdminLTE, DataTables phía máy chủ, quyền `checkrole:xml-man`. Riêng route xoá dùng `checkrole:superadministrator`.

**Tech Stack:** Laravel 5.5, PHP 7.4 (cú pháp tương thích 7.0), blade + `adminlte::page` + Bootstrap 3 + DataTables, PHPUnit 6, SQLite in-memory.

**Spec:** `docs/superpowers/specs/2026-10-23-man-theo-doi-sinh-xml3176-design.md`. Đọc kỹ mục "Các điểm Claude tự chốt" trong spec, đó là quyết định ràng buộc.

## Global Constraints

- **Cách ly:**
  - Không `use App\Services\Xml3176...`, `App\Services\Xml3176Service` hay `App\Models\BHYT\Xml3176...` trong mã mới.
  - Không đọc bảng `xml3176_*` của tiền giám định.
  - Không dùng lại view `bhyt/xml3176/detail-xml-*`.
  - Chú thích không viết dính `QD130` hay `XML130`.
- **Không đọc HIS** khi hiển thị. Màn chỉ đọc bảng `sinh_xml3176_lan_sinh`, `sinh_xml3176_moc` và tệp lưu trữ.
- **Migration mới** `2026_10_23_100004_add_luu_tru_to_sinh_xml3176_lan_sinh.php`:
  - `tep_luu` string(255) nullable;
  - `xoa_luu_luc` timestamp nullable;
  - `xoa_luu_boi` unsignedInteger nullable;
  - chỉ mục trên `created_at`.

  Đăng ký migration này trong `tests/Support/DungBangHisSinhXml3176Sqlite.php`. Không sửa migration cũ.
- **Cấu hình `thu_muc_luu`** trong khối `sinh_xml3176`:
  - mẫu `'D:\XML\SinhXml3176LuuTru'`, mặc định trong mã là `null`, nghĩa là không lưu;
  - đọc qua `CauHinhSinhXml3176::thuMucLuu(): ?string`;
  - nếu trùng hoặc nằm trong `thu_muc`, `thu_muc_tt` hay `thu_muc_thu` thì là lỗi cấu hình, kiểm giống cách `thu_muc_thu` đang được kiểm;
  - ghi mẫu vào `docs/organization.php`, **không sửa** `config/organization.php`.
- **Tên bản sao:** `<thu_muc_luu>\<yyyyMMdd theo created_at của dòng>\<id dòng>_<ten_tep>`. Cột `tep_luu` lưu phần **tương đối** `<yyyyMMdd>\<id>_<ten_tep>`.
- **Quyền:**
  - mọi route trong nhóm `['middleware' => ['checkrole:xml-man']]`, tiền tố `bhyt/sinh-xml3176`, tên route `bhyt.sinh-xml3176.*`;
  - route xoá thêm `checkrole:superadministrator`.
- **Menu:** thêm mục "Theo dõi sinh XML3176" (icon `history`) vào nhóm XML3176 trong `config/adminlte.php`, ngay sau "Dashboard lỗi XML".
- **Test:**
  - cấm `RefreshDatabase`;
  - dùng `tests/Support/DungBangHisSinhXml3176Sqlite.php` như các test SinhXml3176;
  - test HTTP dùng `Tests\Support\UserGiaCoQuyen` (bẫy CheckRole với user factory);
  - chạy `cd /c/Users/tracnn/qlbv && DB_HOST=127.0.0.1 vendor/bin/phpunit tests/Unit/SinhXml3176 tests/Feature/SinhXml3176`.
- **Commit:** trên nhánh `feat/man-theo-doi-sinh-xml3176`, tách từ `main`, không dùng worktree. Dòng cuối thông điệp: `Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>`. Không push.

## Review Focus

1. **Hai lần sinh trong cùng một giây của cùng hồ sơ:** hai bản sao khác tên, mỗi dòng trỏ đúng tệp của mình. Test ở Task 1.
2. **Ghi bản sao thất bại** (thư mục không ghi được): lần sinh vẫn là `da_ghi`, tệp chính vẫn có, `tep_luu` rỗng, có `Log::warning`. Test ở Task 1.
3. **`tep_luu` hoặc tham số ngày bị sửa thành `..\..\x`** ở xem, tải, xoá: bị chặn (404 hoặc 422), không đọc hay xoá tệp ngoài `thu_muc_luu`. Test ở Task 4 và Task 5.
4. **Tệp lưu trữ không còn trên đĩa, hoặc một `FILEHOSO` hỏng base64:** không trả 500. Tệp mất thì hiện "tệp không còn trên đĩa"; bảng hỏng thì báo lỗi ở đúng tab đó, các tab khác vẫn xem được. Test ở Task 4.
5. **Hồ sơ rất dài** (XML2 1.000 dòng): trang xem trả về dưới 3 giây trong test và nhóm dòng theo ngày y lệnh (`NGAY_YL` 8 ký tự đầu), mỗi nhóm một tab con như màn tiền giám định. Test ở Task 4.

---

## File Structure

| Tệp | Trách nhiệm |
|---|---|
| `database/migrations/2026_10_23_100004_add_luu_tru_to_sinh_xml3176_lan_sinh.php` (mới) | cột lưu trữ và chỉ mục `created_at` |
| `app/Services/SinhXml3176/LuuTruXml.php` (mới) | ghi, đọc, xoá bản sao; dựng và kiểm đường dẫn |
| `app/Services/SinhXml3176/SoSanh/DocTepGiamDinh.php`, `SoSanhBang.php` (mới) | đọc tệp ra `[loai => dòng[]]`; ghép dòng và so trường |
| `app/Services/SinhXml3176/TheoDoiSinh.php`, `LuotQuet.php`, `CauHinhSinhXml3176.php` (sửa) | `ghi()` trả `LanSinh`; lưu bản sao sau khi ghi; `thuMucLuu()` |
| `app/Console/Commands/SinhXml3176SoSanh.php` (sửa) | dùng hai lớp so sánh, hành vi không đổi |
| `app/Http/Controllers/BHYT/SinhXml3176Controller.php` (mới), `routes/web.php`, `routes/breadcrumbs.php`, `config/adminlte.php` (sửa) | màn hình |
| `resources/views/bhyt/sinh-xml3176/index.blade.php`, `lich-su.blade.php`, `xem.blade.php`, `so-sanh.blade.php`, `_bang.blade.php` (mới) | giao diện |
| `docs/trien-khai-sinh-xml3176.md`, `docs/organization.php` (sửa) | tài liệu |
| `tests/Unit/SinhXml3176/LuuTruXmlTest.php`, `SoSanhBangTest.php`, `DocTepGiamDinhTest.php` (mới); `tests/Feature/SinhXml3176/SinhXml3176ControllerTest.php` (mới); `LuotQuetTest.php`, `SinhXml3176SoSanhTest.php`, `CauHinhSinhXml3176Test.php` (sửa) | test |

---

### Task 1: Lưu bản sao mỗi lần sinh

**Files:** migration mới, `LuuTruXml.php`, `TheoDoiSinh.php`, `LuotQuet.php`, `CauHinhSinhXml3176.php`, `config/sinh_xml3176.php` (không thêm khoá: `thu_muc_luu` thuộc khối nghiệp vụ trong `organization`), `tests/Support/DungBangHisSinhXml3176Sqlite.php`, `LuuTruXmlTest.php`, `LuotQuetTest.php`, `CauHinhSinhXml3176Test.php`.

**Interfaces (Produces):**
- `CauHinhSinhXml3176::thuMucLuu(): ?string`. Chuỗi rỗng hoặc khoảng trắng thì trả `null`. `loiCauHinh()` (hoặc hàm kiểm tương đương đang có) báo lỗi khi `thu_muc_luu` nằm trong thư mục quét hay thư mục thử.
- `TheoDoiSinh::ghi(array $ketQua): LanSinh` trả về dòng vừa tạo. Nơi gọi cũ bỏ qua giá trị trả về.
- `LuuTruXml::__construct(?string $goc)`
  - `luu(LanSinh $dong, string $noiDung): ?string` ghi bản sao, trả đường tương đối hoặc `null` khi `$goc` rỗng hay ghi lỗi (lỗi thì `Log::warning`, không ném).
  - `duongTuyetDoi(string $tuongDoi): ?string` trả `null` nếu đường không hợp lệ: có `..`, là đường tuyệt đối, hoặc sau `realpath` nằm ngoài `$goc`.
  - `doc(LanSinh $dong): ?string`
  - `coTep(LanSinh $dong): bool`
  - `xoa(LanSinh $dong, int $userId): bool`: xoá tệp, điền `xoa_luu_luc`/`xoa_luu_boi`, xoá thư mục ngày nếu rỗng.
- `LuotQuet`:
  - kết quả `motHoSo` mang thêm khoá tạm `_noi_dung` (chuỗi phong bì đã ghi), **bỏ khoá này trước khi `ghi`**;
  - sau `ghi()` của dòng `da_ghi`, ở cả lượt quét lẫn chạy bù, gọi `luu()` rồi cập nhật `tep_luu` khi có giá trị.

- [ ] **Step 1: test hỏng:**
  - `LuuTruXmlTest`:
    - `test_luu_theo_thu_muc_ngay_va_id`;
    - `test_hai_lan_cung_giay_khac_ten` (Review Focus 1);
    - `test_goc_rong_khong_luu`;
    - `test_duong_chua_cham_cham_bi_chan`;
    - `test_xoa_dien_nguoi_xoa_va_don_thu_muc_rong`;
    - `test_doc_tep_mat_tra_null`.
  - `LuotQuetTest`:
    - `test_da_ghi_co_ban_luu_tru`;
    - `test_luu_tru_loi_van_da_ghi` (Review Focus 2, thư mục gốc là một **tệp** để ghi thất bại);
    - `test_bi_loai_khong_luu`.
  - `CauHinhSinhXml3176Test::test_thu_muc_luu_trong_thu_muc_quet_la_loi`.
- [ ] **Step 2–4:** chạy hỏng → cài đặt → qua cả thư mục.
- [ ] **Step 5: Commit** `feat(sinh-xml3176): luu ban sao XML moi lan sinh theo thu muc ngay`

---

### Task 2: Tách lớp đọc tệp và so sánh

**Files:** `SoSanh/DocTepGiamDinh.php`, `SoSanh/SoSanhBang.php`, `SinhXml3176SoSanh.php`, `DocTepGiamDinhTest.php`, `SoSanhBangTest.php`, `SinhXml3176SoSanhTest.php` (không sửa assertion cũ).

**Interfaces (Produces):**
- `DocTepGiamDinh::doc(string $noiDung): array` trả `['bang' => [loai => [dòng (mảng tên trường => chuỗi)...]], 'loi' => [loai => thông điệp]]`. Một `FILEHOSO` hỏng thì ghi vào `loi[loai]`, không ném. Đây là phần đang nằm trong `docTep`, `dong`, `truong`, `dongTatCa` của lệnh.
- `SoSanhBang::so(string $loai, array $dongA, array $dongB): array` trả danh sách mục `['khoa' => string, 'kieu' => 'chi_co_a'|'chi_co_b'|'khac', 'truong' => ?string, 'a' => ?string, 'b' => ?string]`. Giữ nguyên khoá ghép từng bảng, chuẩn hoá số, quy tắc rỗng bằng 0 ở cột số và đánh số khoá trùng (`#1`, `#2`) của lệnh hiện tại. Khai `SoSanhBang::CAC_BANG` để thay cho `self::$bang` của lệnh.
- Lệnh `sinhxml3176:so-sanh` dùng hai lớp này. CSV đầu ra **giữ nguyên từng byte**: test cũ của lệnh xanh mà không sửa.

- [ ] **Step 1: test hỏng:**
  - `DocTepGiamDinhTest`: `test_doc_du_bang`, `test_filehoso_hong_bao_loi_dung_bang` (Review Focus 4);
  - `SoSanhBangTest`: `test_dong_chi_co_mot_ben`, `test_truong_khac_gia_tri`, `test_so_17000_bang_17000_00`, `test_rong_bang_0_cot_so`, `test_khoa_trung_danh_so`.
- [ ] **Step 2–4:** chạy hỏng → cài đặt → qua cả thư mục, kể cả `SinhXml3176SoSanhTest`.
- [ ] **Step 5: Commit** `refactor(sinh-xml3176): tach doc tep va so sanh bang dung chung`

---

### Task 3: Danh sách hồ sơ và lịch sử

**Files:** `SinhXml3176Controller.php` (các action `index`, `fetchData`, `lichSu`), `routes/web.php`, `routes/breadcrumbs.php`, `config/adminlte.php`, views `index.blade.php`, `lich-su.blade.php`, `tests/Feature/SinhXml3176/SinhXml3176ControllerTest.php`.

**Interfaces:**
- `GET bhyt/sinh-xml3176` → `index` (`bhyt.sinh-xml3176.index`).
- `GET bhyt/sinh-xml3176/fetch-data` → `fetchData`, trả JSON DataTables kiểu `BHYTBh01Controller::fetchData`.
  - Tham số: `tu`, `den` (Y-m-d, mặc định hôm nay); `kieu_ngay` ∈ {`sinh`, `ra_vien`, `khoa_vp`}; `trang_thai` ∈ {'', `da_ghi`, `bi_loai`, `loi`}; `loai_ban` ∈ {'', `chinh_thuc`, `thong_tuyen`}; `ma`. Sai giá trị thì 422.
  - Khoảng ngày tối đa 31 ngày (spec để builder; dùng 31).
  - Ngữ nghĩa lọc theo mục 3 "Các điểm Claude tự chốt" trong spec.
  - Mỗi dòng: `treatment_id`, `treatment_code`, thông tin lần mới nhất (`id`, `created_at`, `loai_ban`, `trang_thai`, `ly_do`, `cac_bang`, `thu_muc` hiện là "thử" hoặc "thật"), `so_lan_sinh` (số dòng `da_ghi` của hồ sơ), `out_time`, `fee_lock_time`.
  - Kèm `tong_hop`: số hồ sơ theo trạng thái mới nhất trong khoảng lọc, và hai mốc quét.
- `GET bhyt/sinh-xml3176/ho-so/{treatmentId}` → `lichSu`.
  - Liệt kê mọi dòng của hồ sơ theo `id` tăng: số thứ tự, giờ, loại bản, trạng thái, lý do, bảng, thư mục, tình trạng XML ("có", "không có XML lưu trữ", "đã xoá lúc … bởi …", "tệp không còn trên đĩa").
  - Nút "So với lần trước" theo mục 2 "Các điểm Claude tự chốt".
  - Có ghi chú về lần `khong_doi`.
- Không có dòng nào cho `treatmentId` thì 404.

- [ ] **Step 1: test hỏng:**
  - `test_index_200`;
  - `test_mot_dong_mot_ho_so_lan_moi_nhat`;
  - `test_so_lan_sinh_chi_dem_da_ghi`;
  - `test_loc_ngay_theo_dong_trong_khoang_trang_thai_theo_lan_moi_nhat`;
  - `test_loc_ra_vien_va_khoa_vp_theo_khoang_14_so`;
  - `test_tham_so_sai_422`;
  - `test_qua_31_ngay_422`;
  - `test_tong_hop_va_moc`;
  - `test_lich_su_gop_hai_loai_ban_theo_id`;
  - `test_lich_su_404`.
- [ ] **Step 2–4:** chạy hỏng → cài đặt → qua.
- [ ] **Step 5: Commit** `feat(sinh-xml3176): man danh sach ho so va lich su lan sinh`

---

### Task 4: Xem XML, tải tệp, so hai lần sinh

**Files:** `SinhXml3176Controller.php` (các action `xem`, `tai`, `soSanh`), routes, views `xem.blade.php`, `_bang.blade.php`, `so-sanh.blade.php`, `SinhXml3176ControllerTest.php`.

**Interfaces:**
- Consumes `LuuTruXml` (Task 1), `DocTepGiamDinh` và `SoSanhBang` (Task 2).
- `GET bhyt/sinh-xml3176/lan/{id}/xem` → `xem`:
  - tab theo bảng có mặt;
  - bảng nhiều dòng hiện hàng × cột, cột theo thứ tự phần tử trong XML;
  - XML2, XML3, XML4 nhóm theo 8 ký tự đầu của `NGAY_YL` (XML4 theo `NGAY_KQ`), mỗi nhóm một tab con;
  - bảng phẳng hiện hai cột trường / giá trị;
  - mỗi bảng có tab "XML thô" (`htmlspecialchars`).
- `GET bhyt/sinh-xml3176/lan/{id}/tai` → `tai`: tải tệp lưu trữ với tên gốc `ten_tep`.
- `GET bhyt/sinh-xml3176/so-sanh?a={id}&b={id}` → `soSanh`:
  - hai lần phải cùng `treatment_id`, nếu không thì 422;
  - kết quả theo bảng từ `SoSanhBang::so`;
  - bảng không khác thì hiện "giống nhau";
  - khác loại bản thì có ghi chú;
  - bảng chỉ có ở một bên thì toàn bộ dòng của bảng đó là `chi_co_*`.
- Tệp không còn hoặc `tep_luu` rỗng hay không hợp lệ: `xem`/`soSanh` hiện thông báo với mã 200; `tai` trả 404.

- [ ] **Step 1: test hỏng:**
  - `test_xem_hien_cac_bang_va_xml_tho`;
  - `test_xem_tep_mat_bao_khong_con_tren_dia` (Review Focus 4);
  - `test_xem_bang_hong_bao_loi_tab_do_cac_tab_khac_van_hien` (Review Focus 4);
  - `test_tai_tep_ten_goc`;
  - `test_tai_tep_luu_bi_sua_duong_404` (Review Focus 3);
  - `test_so_sanh_chi_ra_truong_khac`;
  - `test_so_sanh_khac_ho_so_422`;
  - `test_xem_xml2_1000_dong_nhom_theo_ngay_duoi_3_giay` (Review Focus 5).
- [ ] **Step 2–4:** chạy hỏng → cài đặt → qua.
- [ ] **Step 5: Commit** `feat(sinh-xml3176): xem XML luu tru dang bang va so hai lan sinh`

---

### Task 5: Xoá bản lưu trữ (superadministrator) và tài liệu

**Files:** `SinhXml3176Controller.php` (các action `xoa`, `xoaTheoNgay`), routes trong nhóm thêm `checkrole:superadministrator`, nút xoá và hộp xác nhận trong views, `SinhXml3176ControllerTest.php`, `docs/trien-khai-sinh-xml3176.md`, `docs/organization.php`.

**Interfaces:**
- `POST bhyt/sinh-xml3176/xoa` (`ids[]`) → `xoa`: gọi `LuuTruXml::xoa` cho từng dòng có tệp, trả JSON `{xoa: n, bo_qua: m}`.
- `POST bhyt/sinh-xml3176/xoa-theo-ngay` (`ngay` Y-m-d) → `xoaTheoNgay`:
  - xoá mọi bản lưu trữ có `created_at` trong ngày đó;
  - `ngay` sai định dạng thì 422;
  - trả số tệp đã xoá.
- `GET bhyt/sinh-xml3176/dem-theo-ngay?ngay=` (quyền `xml-man`): đếm số tệp sẽ xoá, để hộp xác nhận hiện con số.
- Mỗi lần xoá ghi `Log::info` kèm user id và số tệp. Nút xoá chỉ hiện khi người dùng có quyền `superadministrator`.

- [ ] **Step 1: test hỏng:**
  - `test_xoa_dien_nguoi_xoa_giu_dong`;
  - `test_xoa_theo_ngay_chi_ngay_do`;
  - `test_ngay_sai_422`;
  - `test_ngay_cham_cham_422` (Review Focus 3);
  - `test_xml_man_khong_xoa_duoc_403`. Test này dùng một user giả chỉ có `xml-man`: một lớp con kiểu `UserGiaCoQuyen` mà `hasRole` trả true chỉ với `xml-man`, đặt ở `tests/Support`.
- [ ] **Step 2–4:** chạy hỏng → cài đặt → qua.
- [ ] **Step 5:** cập nhật tài liệu.
  - `docs/trien-khai-sinh-xml3176.md` thêm mục "Màn theo dõi sinh XML3176":
    - đường vào, quyền, khoá `thu_muc_luu`;
    - dung lượng khoảng 300MB mỗi ngày với 1.800 hồ sơ, chưa kể lần sinh lại;
    - không tự xoá;
    - lần sinh trước khi có tính năng thì không có XML;
    - migration `2026_10_23_100004` thêm vào danh sách kiểm.
  - `docs/organization.php` thêm `'thu_muc_luu' => 'D:\XML\SinhXml3176LuuTru'` kèm chú thích.
- [ ] **Step 6: Commit** `feat(sinh-xml3176): xoa ban luu tru va tai lieu man theo doi`
