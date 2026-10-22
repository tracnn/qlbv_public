# Sinh XML3176 từ HIS — Đợt 1 (khung chạy + XML1–3) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Dựng module `SinhXml3176` gồm các bước sau, và mới chỉ ghi vào thư mục thử:
1. Quét Oracle HIS theo mốc `FEE_LOCK_TIME` / `OUT_TIME`.
2. Nạp dữ liệu từng hồ sơ.
3. Dựng XML1, XML2, XML3 đúng QĐ130/3176.
4. Gói thành phong bì `GIAMDINHHS`, ghi file tạm rồi đổi tên.
5. Ghi bảng theo dõi.

Kèm theo là lệnh so sánh với file HIS để nghiệm thu.

**Architecture:** Thư mục `app/Services/SinhXml3176/` có 4 lớp:
- **Nguồn**: lớp đọc Oracle, chỉ chạy `SELECT`.
- **Dựng**: hàm thuần, mỗi bảng một lớp. Không lớp nào truy vấn CSDL.
- **Phong bì / ghi tệp**.
- **Điều phối lượt quét**: nối ba lớp trên.

Lệnh `sinhxml3176:quet` chạy một lượt hoặc `--lien-tuc`, có khoá lượt kiểu `CtdtImport`. Module **không import bất kỳ lớp nào** của `App\Services\Xml3176*` hay `App\Models\BHYT\Xml3176*`.

**Tech Stack:** Laravel 5.5, PHP 7.4 (giữ cú pháp tương thích 7.0 như phần còn lại của repo: không typed property, không arrow function), `yajra/laravel-oci8`, PHPUnit 6, SQLite in-memory cho test.

**Spec:** `docs/superpowers/specs/2026-10-22-sinh-xml3176-tu-his-design.md`. Bản đồ dữ liệu là `docs/hisnguonmo-xml3176-anh-xa.md` (trong plan viết tắt **[AX]**, kèm số dòng).

**Ngoài phạm vi plan này:** XML4–5 (Đợt 2), XML6–15 (Đợt 3). Mỗi đợt có plan riêng, viết sau khi Đợt 1 chốt giao diện `DungBang`.

## Global Constraints

- **Chỉ đọc HIS:** mọi truy vấn qua `DB::connection($cauHinh->ketNoiHis())`, chỉ `select`. Không `insert/update/delete/statement` lên connection HIS.
- **Cách ly:** không `use App\Services\Xml3176...`, `App\Services\Xml3176Service`, `App\Models\BHYT\Xml3176...` trong `app/Services/SinhXml3176/**`. Có test kiểm (Task 1).
- **Cấu hình đơn vị** nằm ở khối `organization.sinh_xml3176` (`config/organization.php`, file trong `.gitignore`). Thiếu khối thì module tắt. Tham số kỹ thuật nằm ở `config/sinh_xml3176.php` (đi theo git).
- **Mặc định** (lấy nguyên từ spec mục 7): `enabled=false`, `ghi_thu_muc_that=false`, `thong_tuyen=false`, `ket_noi_his='HISPro'`, `thu_muc='D:\XML\3176'`, `thu_muc_tt='D:\XML\3176TT'`, `thu_muc_thu='D:\XML\SinhXml3176Thu'`.
- **Đợt 1 không bao giờ ghi vào thư mục thật.** `ghi_thu_muc_that=true` khi chưa đủ 14 bảng thì lệnh từ chối chạy (Task 9).
- **Tên tệp:** `QLBV_<yyyyMMddHHmmss>_<TREATMENT_CODE>.xml`; bản thông tuyến là `QLBV_<yyyyMMddHHmmss>_<TREATMENT_CODE>_TT.xml`.
- **`NOIDUNGFILE`** là base64 của XML con UTF-8 **không BOM**. `CHUKYDONVI` rỗng.
- **Thẻ gốc XML con:** XML1 `TONG_HOP`; XML2 `CHITIEU_CHITIET_THUOC/DSACH_CHI_TIET_THUOC/CHI_TIET_THUOC`; XML3 `CHITIEU_CHITIET_DVKT_VTYT/DSACH_CHI_TIET_DVKT/CHI_TIET_DVKT`.
- **Tiền** làm tròn 2 chữ số thập phân, kiểu half-up (`round($x, 2)` của PHP). **Không** làm tròn tỷ lệ về số nguyên trước khi nhân.
- **Test:** cấm `RefreshDatabase`/`DatabaseMigrations`. Giả HIS bằng cách ghi đè connection `HISPro` sang SQLite (mẫu: `tests/Support/DungBangHoSoHisSqlite.php`). Bảng qlbv cũng giả bằng cách ghi đè `mysql` sang SQLite (mẫu: `tests/Support/DungBangBh01Sqlite.php`). Chạy với `DB_HOST=127.0.0.1`.
- **Đặt tên:** tên lớp, hàm, chú thích bằng tiếng Việt không dấu, giống phần còn lại của repo.
- **Bộ nhớ:** server có `memory_limit` 128MB. Nạp theo lô (`kich_thuoc_lo`, mặc định 50 hồ sơ), `whereIn` tối đa 1000 phần tử mỗi lần.

## Review Focus

1. **Hồ sơ không có `PATIENT_TYPE_ALTER` hoặc thiếu danh mục đối tượng.** Trong DLL, trường hợp này ném NullReference và mất cả bảng ([AX]:588, 607). Ở đây hồ sơ phải bị loại với lý do rõ ràng, không được ném lỗi làm hỏng lượt. Test nằm ở Task 5.
2. **Giường cuối chưa có giờ ra** (`long.Parse("" + "00")` trong DLL, [AX]:608). NGAY_KQ phải rơi về `OUT_TIME` của hồ sơ. Nếu vẫn không có giá trị nào thì loại hồ sơ, không ném. Test nằm ở Task 7.
3. **Tệp đang ghi dở bị lệnh quét của tiền giám định đọc phải.** Tệp tạm phải có đuôi `.tmp`, khi ghi xong mới `rename`. Test nằm ở Task 9.
4. **Hồ sơ mở khoá rồi khoá lại với cùng `TREATMENT_CODE`.** Phải sinh lại khi `FEE_LOCK_TIME` đổi, và không sinh lại khi giá trị không đổi (lượt chồng lấn). Test nằm ở Task 2 và Task 10.
5. **Mã cấu hình không tồn tại trong HIS** (ví dụ gõ sai mã đối tượng). Phải báo lỗi cấu hình rồi dừng, không được lặng lẽ biến thành "không lọc". Test nằm ở Task 3.

---

## File Structure

| Tệp | Trách nhiệm |
|---|---|
| `config/sinh_xml3176.php` | Tham số kỹ thuật: `kich_thuoc_lo`, `nghi_giay`, `chong_lan_phut`, `khoa_luot_phut` |
| `app/Services/SinhXml3176/CauHinhSinhXml3176.php` | Đọc, gộp mặc định và kiểm tra cấu hình |
| `database/migrations/2026_10_22_100001_create_sinh_xml3176_lan_sinh_table.php` | Bảng theo dõi |
| `database/migrations/2026_10_22_100002_create_sinh_xml3176_moc_table.php` | Mốc quét theo loại bản |
| `app/Models/SinhXml3176/LanSinh.php`, `Moc.php` | Model |
| `app/Services/SinhXml3176/TheoDoiSinh.php` | Quyết định có sinh hay không; ghi kết quả; đọc/ghi mốc |
| `app/Services/SinhXml3176/Nguon/HisUngVien.php` | Chọn hồ sơ ứng viên theo mốc + bộ lọc; đổi mã sang ID |
| `app/Services/SinhXml3176/Nguon/HisDanhMuc.php` | Danh mục và `HIS_CONFIG` cho một lượt |
| `app/Services/SinhXml3176/Nguon/HisNapHoSo.php` | Nạp dữ liệu một lô hồ sơ |
| `app/Services/SinhXml3176/DuLieuHoSo.php` | Gói dữ liệu của một hồ sơ (thuộc tính public) |
| `app/Services/SinhXml3176/KiemDieuKien.php` | Lý do loại hồ sơ (spec 5.3) |
| `app/Services/SinhXml3176/Bang/DungBang.php` | Interface cho các lớp dựng bảng |
| `app/Services/SinhXml3176/Bang/Xml1Dung.php`, `Xml2Dung.php`, `Xml3Dung.php` | Dựng từng bảng |
| `app/Services/SinhXml3176/Bang/LoiDungBang.php` | Ngoại lệ "không dựng được bảng" |
| `app/Services/SinhXml3176/Bang/DangKyBang.php` | Danh sách bảng đã có; kiểm "đủ 14 bảng" |
| `app/Services/SinhXml3176/XmlCon.php` | Mảng dòng → chuỗi XML con |
| `app/Services/SinhXml3176/PhongBi.php` | Gói `GIAMDINHHS` |
| `app/Services/SinhXml3176/GhiTep.php` | Đặt tên, ghi tệp tạm, đổi tên |
| `app/Services/SinhXml3176/LuotQuet.php` | Điều phối một lượt |
| `app/Console/Commands/SinhXml3176Quet.php` | `sinhxml3176:quet` |
| `app/Console/Commands/SinhXml3176SoSanh.php` | `sinhxml3176:so-sanh` |
| `tests/Support/DungBangHisSinhXml3176Sqlite.php` | Bảng HIS giả + bảng theo dõi trên SQLite |
| `tests/Unit/SinhXml3176/*Test.php` | Test |
| `docs/trien-khai-sinh-xml3176.md` | Hướng dẫn triển khai, khối cấu hình mẫu, bảng "Khác biệt với HIS" |
| `install_service.bat` | Thêm dịch vụ NSSM `QLBV SinhXml3176` |

---

### Task 1: Cấu hình và chốt cách ly

**Files:**
- Create: `config/sinh_xml3176.php`, `app/Services/SinhXml3176/CauHinhSinhXml3176.php`
- Test: `tests/Unit/SinhXml3176/CauHinhSinhXml3176Test.php`, `tests/Unit/SinhXml3176/CachLyModuleTest.php`

**Interfaces:**
- Produces: `CauHinhSinhXml3176::tuConfig(): CauHinhSinhXml3176`, cùng các getter:
  - `batBat(): bool`, `thongTuyen(): bool`, `ghiThuMucThat(): bool`, `ketNoiHis(): string`
  - `coSo(): array`, `doiTuongBn(): array`, `doiTuongTt(): array`, `dienDieuTri(): array` (đều là mảng chuỗi mã)
  - `thuMuc(string $loaiBan): string`. `$loaiBan` là `'chinh_thuc'` hoặc `'thong_tuyen'`. Trả về thư mục thật tương ứng nếu `ghiThuMucThat()`, ngược lại trả `thu_muc_thu`.
  - `kichThuocLo(): int`, `nghiGiay(): int`, `chongLanPhut(): int`
- Hằng số `CauHinhSinhXml3176::CHINH_THUC = 'chinh_thuc'`, `THONG_TUYEN = 'thong_tuyen'`.

- [ ] **Step 1: Viết test hỏng**

  `CauHinhSinhXml3176Test`:
  - `test_thieu_khoi_thi_tat`: `config(['organization.sinh_xml3176' => null])`, khi đó `batBat()` là `false`.
  - `test_gop_mac_dinh`: khối chỉ có `['enabled' => true]`. Kỳ vọng `ketNoiHis()==='HISPro'`, `thuMuc('chinh_thuc')==='D:\XML\SinhXml3176Thu'` (vì `ghi_thu_muc_that` mặc định false), `doiTuongBn()===[]`.
  - `test_thu_muc_that`: có `ghi_thu_muc_that=true`, `thu_muc='X'`, `thu_muc_tt='Y'`. Kỳ vọng `thuMuc('chinh_thuc')==='X'`, `thuMuc('thong_tuyen')==='Y'`.
  - `test_mang_ma_chuan_hoa`: `co_so => [' 01929 ', '', '37470']` cho ra `['01929','37470']`.

  `CachLyModuleTest::test_khong_dung_module_xml3176`: duyệt mọi `.php` trong `app/Services/SinhXml3176` và hai tệp lệnh `SinhXml3176*.php`. Assert không tệp nào khớp regex `/\bApp\\\\(Services\\\\Xml3176|Models\\\\BHYT\\\\Xml3176)/`.

- [ ] **Step 2: Chạy, thấy hỏng**
  Run: `vendor\bin\phpunit tests\Unit\SinhXml3176` (với `DB_HOST=127.0.0.1`). Expected: FAIL, class not found.

- [ ] **Step 3: Cài đặt** `config/sinh_xml3176.php` trả về `['kich_thuoc_lo' => 50, 'nghi_giay' => 60, 'chong_lan_phut' => 10, 'khoa_luot_phut' => 120]`. `CauHinhSinhXml3176` là lớp bất biến, dựng bằng `tuConfig()`: gộp `config('organization.sinh_xml3176', [])` với mặc định ở Global Constraints.

- [ ] **Step 4: Chạy, thấy qua**

- [ ] **Step 5: Commit** `feat(sinh-xml3176): cau hinh va chot cach ly module`

---

### Task 2: Bảng theo dõi, mốc quét, quyết định sinh

**Files:**
- Create: hai migration, hai model `App\Models\SinhXml3176\LanSinh` (`$table='sinh_xml3176_lan_sinh'`), `Moc` (`$table='sinh_xml3176_moc'`), và `app/Services/SinhXml3176/TheoDoiSinh.php`
- Create: `tests/Support/DungBangHisSinhXml3176Sqlite.php`: lúc này mới có hàm `dungBangTheoDoi()`, require hai migration lên `mysql` đã ghi đè sang SQLite
- Test: `tests/Unit/SinhXml3176/TheoDoiSinhTest.php`

**Schema:**
- `sinh_xml3176_lan_sinh`: `id`, `treatment_id` (bigint), `treatment_code` (string 50), `loai_ban` (string 20), `fee_lock_time` (bigint, nullable), `out_time` (bigint, nullable), `trang_thai` (string 20: `da_ghi`, `bi_loai`, `loi`), `ly_do` (text, nullable), `ten_tep` (string 255, nullable), `thu_muc` (string 255, nullable), `cac_bang` (string 100, nullable, ví dụ `XML1,XML2,XML3`), timestamps. Index `(treatment_id, loai_ban, id)`.
- `sinh_xml3176_moc`: `loai_ban` (string 20, primary), `moc` (bigint, mặc định 0), `updated_at`.

**Interfaces:**
- Produces: `TheoDoiSinh`
  - `canSinh(int $treatmentId, string $loaiBan, ?int $feeLockTime, ?int $outTime): bool`. Trả `false` khi và chỉ khi dòng **mới nhất** của `(treatment_id, loai_ban)` có trạng thái `da_ghi` **và** cùng `fee_lock_time` (với bản chính thức) hoặc cùng `out_time` (với bản thông tuyến). Dòng `bi_loai`/`loi` không chặn sinh lại khi mốc đổi. Khi mốc không đổi thì cũng không thử lại.
  - `ghi(array $ketQua): void`. Các khoá của `$ketQua` trùng tên cột.
  - `docMoc(string $loaiBan): int`, `datMoc(string $loaiBan, int $moc): void`.

- [ ] **Step 1: Viết test hỏng**
  - `test_chua_tung_sinh_thi_sinh`
  - `test_da_ghi_cung_fee_lock_thi_khong_sinh`
  - `test_khoa_lai_fee_lock_doi_thi_sinh_lai`: dòng `da_ghi` có `fee_lock_time=20261022100000`, gọi với `20261022150000` thì kết quả `true`.
  - `test_bi_loai_cung_moc_thi_khong_thu_lai`
  - `test_bi_loai_moc_doi_thi_thu_lai`
  - `test_thong_tuyen_so_theo_out_time`
  - `test_moc_mac_dinh_0_va_luu_duoc`

- [ ] **Step 2: Chạy, thấy hỏng**
- [ ] **Step 3: Cài đặt** migration, model và `TheoDoiSinh`.
- [ ] **Step 4: Chạy, thấy qua**
- [ ] **Step 5: Commit** `feat(sinh-xml3176): bang theo doi va moc quet`

---

### Task 3: Chọn hồ sơ ứng viên từ HIS

**Files:**
- Create: `app/Services/SinhXml3176/Nguon/HisUngVien.php`
- Modify: `tests/Support/DungBangHisSinhXml3176Sqlite.php`, thêm `dungBangHis()`. Hàm này ghi đè `HISPro` sang SQLite rồi tạo các bảng HIS mà Task 3–4 đọc. Cột đặt theo đúng tên Oracle, chữ thường.
- Test: `tests/Unit/SinhXml3176/HisUngVienTest.php`

**Bước 0 (bắt buộc, làm trên Oracle thật, chỉ đọc):** xác minh các view sau tồn tại và tài khoản `HIS_RS` đọc được: `V_HIS_SERE_SERV_2`, `V_HIS_PATIENT_TYPE_ALTER`, `V_HIS_TREATMENT_12`, `V_HIS_BED_LOG`. Mỗi view chạy `select * from <view> where rownum<=1`, chép kết quả (có hay không, danh sách cột) vào `docs/trien-khai-sinh-xml3176.md` mục "Nguồn HIS".
- View nào có: dùng view.
- View nào không có: join từ bảng gốc, theo các cột mà [AX] liệt kê.

Bước này quyết định câu SQL của Task 3–4. Test SQLite tạo bảng **cùng tên** với nguồn đã chọn.

**Interfaces:**
- Consumes: `CauHinhSinhXml3176`
- Produces: `HisUngVien`
  - `__construct(CauHinhSinhXml3176 $cauHinh)`
  - `doiMa(): void`: đổi mã sang ID, gọi một lần mỗi lượt.
    - `co_so` tra `his_branch.hein_medi_org_code`
    - `doi_tuong_bn` / `doi_tuong_tt` tra `his_patient_type.patient_type_code`
    - `dien_dieu_tri` tra `his_treatment_type.treatment_type_code`

    Mã nào không tìm thấy thì ném `\InvalidArgumentException("Ma cau hinh khong ton tai trong HIS: <khoa>=<ma>")`.
  - `idDoiTuongTt(): array`: dùng ở Task 4.
  - `layLo(string $loaiBan, int $tuMoc, int $gioiHan): array`: trả mảng `stdClass{id, treatment_code, fee_lock_time, out_time}`.
    - Bản chính thức: `fee_lock_time > $tuMoc` và `out_time is not null`, sắp theo `fee_lock_time, id`.
    - Bản thông tuyến: `out_time > $tuMoc` và `fee_lock_time is null`, sắp theo `out_time, id`.
    - Cả hai: `is_delete=0`. Có bộ lọc nào khác rỗng thì áp `whereIn` tương ứng (`branch_id`, `tdl_patient_type_id`, `tdl_treatment_type_id`).

- [ ] **Step 1: Viết test hỏng**
  - `test_chinh_thuc_lay_theo_fee_lock_va_sap_xep`
  - `test_thong_tuyen_chi_lay_chua_thanh_toan`
  - `test_loc_co_so_doi_tuong_dien`
  - `test_bo_loc_rong_la_khong_loc`
  - `test_ma_sai_thi_nem_loi_cau_hinh` (Review Focus 5)
  - `test_bo_ho_so_da_xoa`

- [ ] **Step 2–4:** chạy hỏng, cài đặt, chạy qua.
- [ ] **Step 5: Commit** `feat(sinh-xml3176): chon ho so ung vien tu HIS`

---

### Task 4: Nạp dữ liệu hồ sơ và danh mục

**Files:**
- Create: `app/Services/SinhXml3176/DuLieuHoSo.php`, `Nguon/HisDanhMuc.php`, `Nguon/HisNapHoSo.php`
- Modify: `tests/Support/DungBangHisSinhXml3176Sqlite.php`, thêm các bảng của task này
- Test: `tests/Unit/SinhXml3176/HisNapHoSoTest.php`

**Interfaces:**
- Produces: `DuLieuHoSo`. Thuộc tính public, mỗi dòng là `stdClass`:
  - `$hoSo`: bản ghi treatment, có đủ cột mà [AX] liệt kê cho XML1 ở dòng 167–249
  - `$dienDieuTri`: mảng PTA, đã sắp `log_time desc, id desc`
  - `$dichVu`: mảng sere_serv, **đã lọc theo `idDoiTuongTt()`**
  - `$ekip`, `$giuong`, `$ketQuaCls`, `$pttt`, `$dhst`, `$soSinh`: mảng, trong Đợt 1 chỉ dùng tới mức XML1–3 cần
- Produces: `HisDanhMuc::napChoLuot(string $ketNoi): HisDanhMuc`. Danh mục nạp một lần mỗi lượt, gồm:
  - `config(string $key): ?string`: đọc `his_config` với `is_active=1`, lấy theo `key`
  - `nhanVien(string $loginname): ?stdClass`
  - `icd(string $ma): ?stdClass`
  - `doiTuong(int $id): ?stdClass`
  - `noiKcb(string $ma): ?stdClass`
  - `idDoiTuongBhyt(): ?int`: lấy mã từ key `MOS.HIS_PATIENT_TYPE.PATIENT_TYPE_CODE.BHYT`, **đổi mã sang ID qua danh mục**. Đây là sửa lỗi [AX]:586, 606.
- Produces: `HisNapHoSo::__construct(CauHinhSinhXml3176, HisUngVien)`. Hàm `nap(array $treatmentIds): array<int, DuLieuHoSo>` trả mảng có khoá là `treatment_id`. Mỗi bảng con chỉ tốn một truy vấn cho cả lô, chia nhóm 1000 ID mỗi lần.

- [ ] **Step 1: Viết test hỏng**
  - `test_nap_gom_dung_ho_so`: 2 hồ sơ, mỗi hồ sơ 3 dịch vụ, mỗi dịch vụ gắn đúng hồ sơ của nó.
  - `test_loc_dich_vu_theo_doi_tuong_tt`: dịch vụ có đối tượng ngoài danh sách bị loại khỏi `$dichVu`, nhưng hồ sơ vẫn có trong kết quả.
  - `test_ma_doi_tuong_bhyt_khong_phai_so`: mã `'BHYT'`, ID 1. `idDoiTuongBhyt()` phải trả `1`.
  - `test_moi_bang_con_mot_truy_van`: bật `DB::connection('HISPro')->enableQueryLog()`, nạp 2 hồ sơ. Số truy vấn bằng số bảng con và không đổi khi tăng số hồ sơ.

- [ ] **Step 2–4:** chạy hỏng, cài đặt, chạy qua.
- [ ] **Step 5: Commit** `feat(sinh-xml3176): nap du lieu ho so va danh muc HIS`

---

### Task 5: Kiểm điều kiện sinh

**Files:**
- Create: `app/Services/SinhXml3176/KiemDieuKien.php`
- Test: `tests/Unit/SinhXml3176/KiemDieuKienTest.php`

**Interfaces:**
- Produces: phương thức của đối tượng `(new KiemDieuKien())->lyDoLoai(DuLieuHoSo $d, string $loaiBan, HisDanhMuc $dm): ?string`. Trả `null` nghĩa là được sinh.
  - **Bản chính thức:** dùng các điều kiện của `CreateXmlProcessor.Check()` (`CreateXmlProcessor.cs` dòng 241–371, mã dịch ngược). **Bỏ** các điều kiện `HEIN_LOCK_TIME` (spec quyết định 2).
  - **Bản thông tuyến:** dùng `CheckCollinearXml()` (từ dòng 462).

  Câu lý do lấy nguyên văn tiếng Việt từ DLL. Ví dụ: `"Hồ sơ chưa có thời gian khóa viện phí"`, `"Thời gian ra viện lớn hơn thời gian thanh toán"`, `"Không xác định được thông tin diện điều trị"`, `"Hồ sơ không có dịch vụ thanh toán"`.

- [ ] **Step 1: Viết test hỏng.** Mỗi điều kiện một test, gồm cả những ca sau:
  - `test_khong_co_dien_dieu_tri_thi_loai_khong_nem` (Review Focus 1)
  - `test_khong_doi_hein_lock_time`: hồ sơ BHYT, `hein_lock_time=null`, `fee_lock_time` có giá trị. Kết quả là `null`.
  - `test_fee_lock_nho_hon_out_time_thi_loai`
  - `test_thong_tuyen_khong_can_fee_lock`
  - `test_so_sinh_thieu_the_tam_thi_loai`

- [ ] **Step 2–4:** chạy hỏng, cài đặt, chạy qua.
- [ ] **Step 5: Commit** `feat(sinh-xml3176): kiem dieu kien sinh ho so`

---

### Task 6: Dựng XML2 (thuốc)

**Files:**
- Create: `app/Services/SinhXml3176/Bang/DungBang.php`, `LoiDungBang.php`, `Xml2Dung.php`
- Test: `tests/Unit/SinhXml3176/Xml2DungTest.php`

**Interfaces:**
- Produces: `interface DungBang`
  - `loai(): string`: ví dụ `'XML2'`
  - `coTrongHoSo(DuLieuHoSo $d): bool`
  - `dung(DuLieuHoSo $d, HisDanhMuc $dm, array $daDung): array`: trả mảng các dòng. Mỗi dòng là mảng kết hợp, **thứ tự khoá đúng thứ tự chỉ tiêu**. `$daDung` là các bảng đã dựng trước đó, có khoá `loai`.
  - Gặp dữ liệu không dựng được thì ném `LoiDungBang($loai, $moTa)`.
- `class LoiDungBang extends \RuntimeException`, có `loaiBang(): string`.

**Nội dung:**
- Trường và thứ tự lấy theo [AX]:368–411. Nguồn cột theo cùng bảng đó.
- Quy tắc chọn dòng lấy theo [AX]:352–366. Tiền theo [AX]:421–428. `LIEU_DUNG` theo [AX]:412–420. Key cấu hình theo [AX]:433–447, đọc **chỉ** qua `HisDanhMuc::config()`, tức là sửa [AX]:598.
- Sửa các lỗi DLL sau. Mỗi sửa đổi là một dòng trong bảng "Khác biệt với HIS" (Task 11):
  - [AX]:586: lấy ID đối tượng BHYT qua `idDoiTuongBhyt()`.
  - [AX]:588: thiếu danh mục đối tượng thì ném `LoiDungBang`.
  - [AX]:589–591: có quy đổi đơn vị thì `SO_LUONG` làm tròn 3 chữ số, `DON_GIA` làm tròn 3 chữ số. Khi tách dòng theo lần dùng cũng phải nhân `CONVERT_RATIO`.
  - [AX]:594: dùng tỷ lệ nguyên giá trị, không làm tròn.
  - [AX]:596: `T_NGUONKHAC` không âm.
  - [AX]:602: chuỗi rỗng `""` coi như null khi chọn TT_THAU.

  Độ dài và độ chính xác của từng trường lấy theo QĐ130 Bảng 2 (sửa đổi bởi QĐ3176). Trường nào [AX] và QĐ cho kết quả khác nhau mà người làm không chắc thì giữ như [AX], ghi vào bảng khác biệt với trạng thái **"cần xác nhận"**.

- [ ] **Step 1: Viết test hỏng.** Dùng dữ liệu giả dựng tay, không cần SQLite:
  - `test_dong_thuoc_bhyt_co_ban`: SL 10, đơn giá 1.500, tỷ lệ 100%, mức hưởng 80%. Kỳ vọng `THANH_TIEN_BV=15000.00`, `THANH_TIEN_BH=15000.00`, `T_BHTT=12000.00`, `T_BNCCT=3000.00`, `T_BNTT=0`.
  - `test_ty_le_le_khong_lam_tron_truoc`: tỷ lệ 33,33%, `THANH_TIEN_BH = round(BV*0.3333, 2)`.
  - `test_quy_doi_don_vi_lam_tron`: `CONVERT_RATIO=3`, `DON_GIA` có đúng 3 chữ số thập phân.
  - `test_nguon_khac_khong_am`
  - `test_thieu_danh_muc_doi_tuong_nem_loi_dung_bang`
  - `test_thu_tu_khoa_dung_chi_tieu`: `array_keys` của dòng đầu đúng danh sách 39 trường theo [AX]:368–411.

- [ ] **Step 2–4:** chạy hỏng, cài đặt, chạy qua.
- [ ] **Step 5: Commit** `feat(sinh-xml3176): dung XML2`

---

### Task 7: Dựng XML3 (DVKT + VTYT)

**Files:**
- Create: `app/Services/SinhXml3176/Bang/Xml3Dung.php`
- Test: `tests/Unit/SinhXml3176/Xml3DungTest.php`

**Interfaces:** cài `DungBang`, `loai()==='XML3'`.

**Nội dung:**
- Trường theo [AX]:468–519. Chọn dòng theo [AX]:450–467.
- Tiền: DVKT theo [AX]:520–527, VTYT theo [AX]:528–539.
- `NGUOI_THUC_HIEN` theo [AX]:540–550, cộng với quy tắc QĐ3176: khi người đó không có `DIPLOMA` thì dùng CCCD. `NGAY_KQ` theo [AX]:551–558. `MA_MAY` theo [AX]:559–563, dạng QĐ3176 `MACHINE_GROUP_CODE.SOURCE_CODE[SOURCE_NAME khi SOURCE_CODE="3"].SERIAL_NUMBER`.
- Key cấu hình theo [AX]:564–582.
- Sửa các lỗi DLL sau:
  - [AX]:605: bỏ dòng giá 0 trừ khi key `HIS.QD_130_BYT.LAY_CA_DVU_0_DONG == "1"`; chỉ giữ dòng thuộc đối tượng thanh toán đã lọc.
  - [AX]:606–607: tra ID đối tượng qua danh mục; thiếu danh mục thì ném `LoiDungBang`.
  - [AX]:608: NGAY_KQ của giường cuối rơi về `OUT_TIME` của hồ sơ; vẫn rỗng thì ném `LoiDungBang` (Review Focus 2).
  - [AX]:609, 611: VTYT có `THANH_TIEN_BH = round(SL*DG_BH*TYLE_BH/100, 2)`, sau đó tính lại `T_BHTT` theo giá trị mới.
  - [AX]:612: tổng các nguồn khác con bằng `T_NGUONKHAC`.
  - [AX]:613: dòng `_TB` có `THANH_TIEN_BV=0` thì `T_NGUONKHAC=0`.
  - [AX]:615: cân đối `T_BNTT+T_BNCCT+T_BHTT+T_NGUONKHAC = THANH_TIEN_BV` như XML2.
  - [AX]:616: không làm tròn tỷ lệ.
  - [AX]:623: bỏ `;` thừa ở TT_THAU.
  - [AX]:624: bỏ trùng theo `sere_serv.id`.

- [ ] **Step 1: Viết test hỏng**
  - `test_dvkt_co_ban_can_doi_tong`
  - `test_vtyt_ty_le_bh_50_phan_tram`: SL 2, DG_BH 100.000, TYLE_BH 50. Kỳ vọng `THANH_TIEN_BH=100000.00`, và `T_BHTT` tính theo giá trị này.
  - `test_bo_dong_gia_0_mac_dinh` và `test_lay_dong_gia_0_khi_bat_key`
  - `test_giuong_cuoi_khong_gio_ra_dung_out_time` (Review Focus 2)
  - `test_giuong_cuoi_khong_co_gio_nao_nem_loi_dung_bang`
  - `test_ma_may_dang_3176_source_3_co_ten`: chuỗi `'MG.3[Ten nguon].SN01'`
  - `test_nguoi_thuc_hien_khong_cchn_dung_cccd`
  - `test_dong_trung_id_chi_mot_lan`
  - `test_thu_tu_khoa_dung_chi_tieu`: 47 trường

- [ ] **Step 2–4:** chạy hỏng, cài đặt, chạy qua.
- [ ] **Step 5: Commit** `feat(sinh-xml3176): dung XML3`

---

### Task 8: Dựng XML1 (tổng hợp)

**Files:**
- Create: `app/Services/SinhXml3176/Bang/Xml1Dung.php`
- Test: `tests/Unit/SinhXml3176/Xml1DungTest.php`

**Interfaces:**
- Cài `DungBang`, `loai()==='XML1'`. Trả về đúng **một** dòng.
- **Consumes** `$daDung['XML2']` và `$daDung['XML3']`: các tổng tiền là tổng các dòng của hai bảng này. Vì vậy `LuotQuet` phải dựng XML2 và XML3 trước XML1.

**Nội dung:**
- Trường theo [AX]:167–249, key cấu hình theo [AX]:250–269.
- Áp các khác biệt QĐ3176 ở [AX]:21–37, hàng XML1: SO_CCCD, MA_NGHE_NGHIEP, CHAN_DOAN_VAO, MA_BENH_YHCT, MA_TTDV, DU_PHONG.
- `MA_TTDV` xử lý theo spec 5.6: key `MA_TTDV_FROM_PROFILE_XML` là lựa chọn bệnh viện được phép thì tôn trọng; khi không cấu hình thì dùng `DIPLOMA` của (phó) giám đốc.
- Sửa các lỗi DLL sau:
  - [AX]:296: cắt độ dài theo QĐ.
  - [AX]:297: CHAN_DOAN_RV không còn dấu `;` thừa.
  - [AX]:298: chuỗi rỗng coi như null.
  - [AX]:300–304: không ném lỗi; thiếu dữ liệu bắt buộc thì ném `LoiDungBang` có mô tả.
  - [AX]:305: các danh sách thẻ phải giữ đúng vị trí tương ứng với nhau.
  - [AX]:311: MA_BENH_YHCT tách theo `;`, bỏ mã trùng.
  - [AX]:314: DU_PHONG làm tròn 2 chữ số.

  [AX]:308–309 (MA_LOAI_KCB "100" của KSK) và [AX]:313 (SO_NGAY_DTRI) phải đối chiếu với QĐ. Không chắc thì giữ như DLL và ghi trạng thái **"cần xác nhận"**.

- [ ] **Step 1: Viết test hỏng**
  - `test_tong_tien_bang_tong_xml2_xml3`
  - `test_chan_doan_rv_khong_dau_cham_phay_thua`
  - `test_the_bhyt_nhieu_the_giu_dung_vi_tri`: 2 thẻ, thẻ thứ hai không có nơi ĐKBĐ. Kỳ vọng `MA_DKBD` là `"01929;"`, không bị dồn vị trí.
  - `test_ma_benh_yhct_tach_va_bo_trung`
  - `test_so_cccd_roi_ve_cmnd_ho_chieu`
  - `test_thieu_icd_danh_muc_nem_loi_dung_bang_khong_nem_null`
  - `test_thu_tu_khoa_dung_chi_tieu`

- [ ] **Step 2–4:** chạy hỏng, cài đặt, chạy qua.
- [ ] **Step 5: Commit** `feat(sinh-xml3176): dung XML1`

---

### Task 9: XML con, phong bì, ghi tệp, chặn thiếu bảng

**Files:**
- Create: `app/Services/SinhXml3176/XmlCon.php`, `PhongBi.php`, `GhiTep.php`, `Bang/DangKyBang.php`
- Test: `tests/Unit/SinhXml3176/PhongBiTest.php`, `GhiTepTest.php`, `DangKyBangTest.php`

**Interfaces:**
- `XmlCon::chuoi(string $loai, array $dong): string`: dùng `XMLWriter`, khai báo `<?xml version="1.0" encoding="utf-8"?>`, thẻ gốc theo Global Constraints. Các trường văn bản tự do bọc trong CDATA: CHAN_DOAN_*, DIA_CHI, LIEU_DUNG, KET_LUAN, GHI_CHU, PP_DIEU_TRI.
- `PhongBi::chuoi(string $maCskcb, array $xmlCon): string`. `$xmlCon` có dạng `['XML1' => '<...>', ...]`, theo thứ tự XML1, XML2, XML3… Hàm sinh `GIAMDINHHS/THONGTINDONVI/MACSKCB` và `THONGTINHOSO/NGAYLAP` (`yyyyMMdd`) / `SOLUONGHOSO=1` / `DANHSACHHOSO/HOSO/FILEHOSO{LOAIHOSO,NOIDUNGFILE}`, cùng `CHUKYDONVI` rỗng.
- `GhiTep::ghi(string $thuMuc, string $treatmentCode, string $loaiBan, string $noiDung, \DateTimeInterface $luc): string`. Hàm tạo thư mục nếu chưa có, ghi `<ten>.tmp` rồi `rename` sang `<ten>.xml`, và trả về tên tệp.
- `DangKyBang`:
  - `__construct(array $cacDung)`
  - `cacDung(): array`: theo thứ tự dựng `[Xml2Dung, Xml3Dung, Xml1Dung]`
  - `daDu(): bool`: đủ 14 loại XML1–11 và XML13–15
  - `thieu(): array`
  - `macDinh(): DangKyBang`: nhà máy, dựng danh sách Đợt 1

- [ ] **Step 1: Viết test hỏng**
  - `PhongBiTest::test_importer_doc_duoc_cau_truc`. Parse lại bằng `simplexml_load_string`, rồi kiểm:
    - có `THONGTINDONVI->MACSKCB`
    - đếm được `FILEHOSO`
    - `base64_decode(NOIDUNGFILE)` không bắt đầu bằng `"\xEF\xBB\xBF"` và parse được
    - XML1 có gốc `TONG_HOP`
  - `GhiTepTest::test_ten_tep_chinh_thuc_va_tt`: kiểm `QLBV_20261022153000_000123.xml` và `..._TT.xml`.
  - `GhiTepTest::test_khong_de_lai_tep_tmp_va_tep_xml_day_du` (Review Focus 3): sau `ghi()` trong thư mục chỉ còn đúng một tệp `.xml`, nội dung bằng `$noiDung`.
  - `DangKyBangTest::test_dot_1_chua_du`: `daDu()===false`, `thieu()` có 11 loại.

- [ ] **Step 2–4:** chạy hỏng, cài đặt, chạy qua.
- [ ] **Step 5: Commit** `feat(sinh-xml3176): phong bi GIAMDINHHS va ghi tep an toan`

---

### Task 10: Lượt quét và lệnh `sinhxml3176:quet`

**Files:**
- Create: `app/Services/SinhXml3176/LuotQuet.php`, `app/Console/Commands/SinhXml3176Quet.php`
(Không sửa `app/Console/Kernel.php`: Kernel tự nạp mọi lệnh trong `app/Console/Commands` bằng `$this->load(...)`.)
- Test: `tests/Unit/SinhXml3176/LuotQuetTest.php`, `tests/Unit/SinhXml3176/SinhXml3176QuetTest.php`

**Interfaces:**
- `LuotQuet::__construct(CauHinhSinhXml3176, HisUngVien, HisNapHoSo, TheoDoiSinh, DangKyBang, KiemDieuKien)`
- `LuotQuet::chay(string $loaiBan): array`. Trả `['ung_vien'=>int,'da_ghi'=>int,'bi_loai'=>int,'loi'=>int,'bo_qua'=>int]`.
  1. Gọi `HisDanhMuc::napChoLuot($cauHinh->ketNoiHis())` và `$ungVien->doiMa()` một lần ở đầu lượt.
  2. Đọc mốc. Mốc có dạng `yyyyMMddHHmmss`. Lùi mốc `chongLanPhut()` phút bằng `DateTime::createFromFormat('YmdHis', ...)` rồi `->modify('-N minutes')`, sau đó đổi về số. Không trừ số học. Mốc bằng 0 thì giữ nguyên 0. Gọi `layLo(loai, mocDaLui, kichThuocLo)`.
  3. Lọc ứng viên bằng `canSinh()`. Số bị lọc cộng vào `bo_qua`.
  4. `nap()` cả lô.
  5. Với từng hồ sơ: `$kiem->lyDoLoai()` → dựng các bảng theo `DangKyBang` → `PhongBi` → `GhiTep` → `ghi()`. `MACSKCB` của phong bì lấy là `hein_medi_org_code` của cơ sở trên hồ sơ. **Bọc `try/catch` từng hồ sơ:** `LoiDungBang` ghi trạng thái `loi` cùng tên bảng và mô tả; `\Throwable` khác cũng ghi `loi` kèm thông điệp. Lỗi của một hồ sơ không làm dừng lượt.
  6. Đặt mốc bằng giá trị lớn nhất của lô **chỉ sau khi** đã xử lý hết lô.
- `LuotQuet::chayChoMa(array $treatmentCodes, string $loaiBan): array` và `chayChoKhoangNgay(int $tu, int $den, string $loaiBan): array` dùng cho chạy bù. Hai hàm này **không đụng mốc** và bỏ qua `canSinh()`.
- Lệnh `sinhxml3176:quet {--lien-tuc} {--nghi=} {--so-vong=1000} {--ma-dieu-tri=*} {--tu-ngay=} {--den-ngay=} {--go-khoa}`:
  - Tắt module thì in `"Module sinh XML3176 dang tat"` và trả 0.
  - Khoá lượt dùng `Cache::add('sinhxml3176:quet:dang-chay', true, khoa_luot_phut)`, theo mẫu `CtdtImport::handle()`.
  - **Chặn thiếu bảng:** `ghiThuMucThat() && !DangKyBang::macDinh()->daDu()` thì in lỗi kèm danh sách `thieu()` và trả 1, không quét gì.
  - Mỗi vòng chạy `chinh_thuc`, và chạy thêm `thong_tuyen` nếu `thongTuyen()`.

- [ ] **Step 1: Viết test hỏng.** Dùng SQLite HIS giả và thư mục tạm `sys_get_temp_dir()`:
  - `test_luot_ghi_tep_va_theo_doi`
  - `test_luot_chong_lan_khong_sinh_lai` (Review Focus 4)
  - `test_khoa_lai_doi_fee_lock_sinh_lai` (Review Focus 4)
  - `test_mot_ho_so_loi_khong_dung_luot`: 3 hồ sơ, hồ sơ giữa thiếu ICD danh mục. Kết quả `da_ghi=2`, `loi=1`.
  - `test_moc_chi_tien_sau_khi_xong_lo`
  - `SinhXml3176QuetTest::test_tu_choi_ghi_thu_muc_that_khi_thieu_bang`: exit code 1, output chứa `XML4`.
  - `SinhXml3176QuetTest::test_module_tat_thi_thoat_em`

- [ ] **Step 2–4:** chạy hỏng, cài đặt, chạy qua.
- [ ] **Step 5: Commit** `feat(sinh-xml3176): luot quet va lenh sinhxml3176:quet`

---

### Task 11: Lệnh so sánh với file HIS và tài liệu triển khai

**Files:**
- Create: `app/Console/Commands/SinhXml3176SoSanh.php`, `docs/trien-khai-sinh-xml3176.md`
- Modify: `install_service.bat`: thêm dịch vụ `QLBV SinhXml3176` chạy `php artisan sinhxml3176:quet --lien-tuc`, theo mẫu các dịch vụ đang có trong tệp
- Test: `tests/Unit/SinhXml3176/SinhXml3176SoSanhTest.php`

**Interfaces:**
- Lệnh `sinhxml3176:so-sanh {--his= : thu muc file HIS goc} {--qlbv= : thu muc file module sinh} {--ra= : tep CSV ket qua} {--bang=XML1,XML2,XML3}`.
  1. Ghép hồ sơ giữa hai bên theo `MA_LK` của XML1.
  2. Ghép dòng trong từng bảng:
     - XML1: theo `MA_LK`
     - XML2: theo `MA_LK` + `MA_THUOC` + `NGAY_YL`
     - XML3: theo `MA_LK` + `MA_DICH_VU` + `MA_VAT_TU` + `NGAY_YL`
  3. Ghi CSV các cột `ma_lk, bang, khoa_dong, truong, gia_tri_his, gia_tri_qlbv`. Có thêm các dòng `chi_co_his` / `chi_co_qlbv` cho dòng hoặc hồ sơ chỉ có ở một bên.

  Lệnh chỉ đọc tệp, không đụng CSDL.
- `docs/trien-khai-sinh-xml3176.md` gồm:
  - khối cấu hình mẫu (spec mục 7)
  - bước đối chiếu bộ lọc với màn "Thiết lập đồng bộ" trên máy trạm HIS (spec 5.5)
  - mục "Nguồn HIS" (kết quả bước 0 của Task 3)
  - cách cài dịch vụ
  - các hệ quả vận hành (spec 9a)
  - quy trình nghiệm thu (spec mục 9)
  - bảng **"Khác biệt với HIS"** (`bảng/trường | HIS làm gì | module làm gì | căn cứ | trạng thái`), có đủ mọi dòng phát sinh từ Task 6–8

- [ ] **Step 1: Viết test hỏng**
  - `test_giong_nhau_thi_csv_rong`
  - `test_lech_mot_truong_xml2`
  - `test_dong_chi_co_mot_ben`

  Mỗi test dựng hai thư mục tạm, mỗi thư mục một tệp `GIAMDINHHS` tự tạo bằng `PhongBi`.
- [ ] **Step 2–4:** chạy hỏng, cài đặt, chạy qua.
- [ ] **Step 5: Chạy toàn bộ** `vendor\bin\phpunit tests\Unit\SinhXml3176`. Expected: mọi test đều qua.
- [ ] **Step 6: Commit** `feat(sinh-xml3176): lenh so sanh va tai lieu trien khai`

---

### Task 12: Chạy thử trên dữ liệu thật (thư mục thử) và so với HIS

Task này chạy trên máy dev đang trỏ CSDL thật. Không ghi thư mục thật.

- [ ] **Step 1:** Trong `config/organization.php` của máy dev, khai khối `sinh_xml3176` với `enabled=true`, `ghi_thu_muc_that=false`, bộ lọc chép từ máy trạm HIS.
- [ ] **Step 2:** Chọn **10 mã điều trị** có sẵn file HIS gốc, do chủ dự án cung cấp từ thư mục phụ (spec mục 9). Chạy `php artisan sinhxml3176:quet --ma-dieu-tri=<ma1> --ma-dieu-tri=<ma2> ...`. Expected: 10 tệp trong `thu_muc_thu`, hoặc lý do loại hay lỗi trong `sinh_xml3176_lan_sinh`.
- [ ] **Step 3:** Chạy `php artisan sinhxml3176:so-sanh --his=<thu muc phu> --qlbv=D:\XML\SinhXml3176Thu --ra=storage\app\so-sanh-dot1.csv --bang=XML1,XML2,XML3`.
- [ ] **Step 4:** Với từng dòng lệch trong CSV, xếp vào một trong ba nhóm:
  - lỗi DLL đã biết trong [AX]
  - dòng có sẵn trong bảng "Khác biệt với HIS"
  - **lỗi module**: sửa, rồi chạy lại Step 2–3

  Đạt khi không còn dòng nào thuộc nhóm thứ ba. Ghi số liệu (số hồ sơ, số dòng lệch theo từng nhóm) vào `docs/trien-khai-sinh-xml3176.md`.
- [ ] **Step 5:** Nạp thử vào tiền giám định trên môi trường test. Chép 10 tệp vào thư mục quét của máy test, chạy `xml3176import:day` một vòng. Expected: cả 10 tệp được nạp, không tệp nào rơi vào `loi/`.
- [ ] **Step 6: Commit** `docs(sinh-xml3176): ket qua chay thu dot 1`
