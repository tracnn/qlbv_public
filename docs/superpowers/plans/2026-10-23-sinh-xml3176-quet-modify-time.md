# Sinh XML3176: quét theo MODIFY_TIME — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Lượt quét tìm hồ sơ theo `HIS_TREATMENT.MODIFY_TIME` trong cửa sổ `OUT_TIME`. Chỉ ghi tệp khi dấu vân tay nội dung đổi, để hồ sơ mở khoá, sửa rồi khoá lại giữ nguyên `FEE_LOCK_TIME` vẫn được sinh lại.

**Architecture:**
- `HisUngVien` đổi cột mốc quét sang `modify_time` và thêm điều kiện cửa sổ `out_time`.
- `TheoDoiSinh` lưu `modify_time` và `noi_dung_hash` của lần xét cuối.
- `LuotQuet`:
  - bỏ qua sớm khi `modify_time` không đổi;
  - tính hash trước khi ghi;
  - trả trạng thái `khong_doi` khi nội dung giống lần ghi cuối.
- Chạy bù vẫn chọn theo `FEE_LOCK_TIME` / `OUT_TIME`.

**Tech Stack:** Laravel 5.5, PHP 7.4 (cú pháp tương thích 7.0), `yajra/laravel-oci8`, PHPUnit 6, SQLite in-memory.

**Spec:** `docs/superpowers/specs/2026-10-23-sinh-xml3176-quet-modify-time-design.md` (bổ sung cho `docs/superpowers/specs/2026-10-22-sinh-xml3176-tu-his-design.md`).

## Global Constraints

- **HIS chỉ đọc:** chỉ `select`. Không thêm chỉ mục và không sửa gì trên Oracle.
- **Không đổi cách dựng 14 bảng.** Mọi test `Xml*DungTest` giữ nguyên và phải xanh.
- **Cột mới nullable** trong migration mới `2026_10_23_100003_add_modify_time_hash_to_sinh_xml3176_lan_sinh.php`:
  - `modify_time` bigint nullable;
  - `noi_dung_hash` char(40) nullable.

  Không sửa migration cũ. Đăng ký migration mới trong `tests/Support/DungBangHisSinhXml3176Sqlite.php`.
- **Cấu hình:** khoá `cua_so_ra_vien_ngay` (int, mặc định `60`) trong `config/sinh_xml3176.php`, đọc qua `CauHinhSinhXml3176::cuaSoRaVienNgay()`. Giá trị mặc định trong mã cũng là `60`. Không sửa `config/organization.php`.
- **Dấu vân tay:** `sha1` của chuỗi ghép mã CSKCB và các cặp `loai` + XML con của mọi bảng khác rỗng, sắp theo số bảng tăng (giống `cac_bang`). Không gồm phong bì và tên tệp.
- **Trạng thái mới:** `khong_doi`. Không ghi dòng theo dõi mới, chỉ cập nhật `modify_time` của dòng cuối cùng loại bản.
  - Khoá kết quả lượt (`ketQuaRong`) có thêm `khong_doi`.
  - Lệnh in thêm `khong_doi=`.
- **Test:** cấm `RefreshDatabase`. Chạy bằng `cd /c/Users/tracnn/qlbv && DB_HOST=127.0.0.1 vendor/bin/phpunit tests/Unit/SinhXml3176`.
- **Đặt tên:** tiếng Việt không dấu. Chú thích không viết dính chữ `QD130` hay `XML130`.
- **Commit:** trên nhánh `feat/sinh-xml3176-quet-modify-time`, tách từ `main`, không dùng worktree. Dòng cuối thông điệp: `Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>`. Không push.

## Review Focus

1. **Cửa sổ chồng lấn 10 phút** (`chong_lan_phut`): cùng một hồ sơ có `modify_time` không đổi qua nhiều lượt thì chỉ được nạp và dựng một lần. Test ở Task 3.
2. **Hồ sơ mở khoá, sửa, khoá lại giữ nguyên `FEE_LOCK_TIME`:** `modify_time` mới hơn và nội dung khác thì phải ghi tệp mới. Test ở Task 3.
3. **HIS chỉ chạm vào hồ sơ, nội dung không đổi:** `modify_time` mới hơn nhưng hash bằng thì không ghi tệp và không thêm dòng. Test ở Task 3.
4. **Dòng theo dõi cũ chưa có hash hoặc `modify_time`** (trước triển khai): coi như phải dựng, và nội dung khác thì ghi. Không được ném lỗi. Test ở Task 1 và Task 3.
5. **Hồ sơ chuyển từ thông tuyến sang chính thức:** hai loại bản theo dõi riêng, nên bản chính thức đầu tiên luôn được ghi dù cùng nội dung với bản thông tuyến. Test ở Task 3.

---

## File Structure

| Tệp | Trách nhiệm |
|---|---|
| `database/migrations/2026_10_23_100003_add_modify_time_hash_to_sinh_xml3176_lan_sinh.php` (mới) | hai cột mới |
| `app/Services/SinhXml3176/TheoDoiSinh.php`, `app/Models/SinhXml3176/LanSinh.php` (sửa) | đọc dòng cuối, quyết định bỏ qua sớm, cập nhật `modify_time` |
| `app/Services/SinhXml3176/Nguon/HisUngVien.php`, `CauHinhSinhXml3176.php`, `config/sinh_xml3176.php` (sửa) | cột mốc `modify_time`, cửa sổ `out_time`, khoá cấu hình |
| `app/Services/SinhXml3176/LuotQuet.php`, `app/Console/Commands/SinhXml3176Quet.php` (sửa) | bỏ qua sớm, hash, `khong_doi`, chống trùng `bi_loai` |
| `docs/trien-khai-sinh-xml3176.md`, `docs/organization.php` (sửa) | tài liệu vận hành |
| `tests/Support/DungBangHisSinhXml3176Sqlite.php` (sửa: migration mới, cột `modify_time` của `his_treatment` giả), `tests/Unit/SinhXml3176/TheoDoiSinhTest.php`, `HisUngVienTest.php`, `CauHinhSinhXml3176Test.php`, `LuotQuetTest.php`, `SinhXml3176QuetTest.php` (sửa) | test |

---

### Task 1: Bảng theo dõi lưu modify_time và hash

**Files:** migration mới, `TheoDoiSinh.php`, `LanSinh.php`, `tests/Support/DungBangHisSinhXml3176Sqlite.php`, `TheoDoiSinhTest.php`.

**Interfaces (Produces):**
- `TheoDoiSinh::dongCuoi(int $treatmentId, string $loaiBan): ?LanSinh` — dòng cuối theo `id` giảm dần.
- `TheoDoiSinh::canXet(int $treatmentId, string $loaiBan, $modifyTime): bool`
  - `false` chỉ khi dòng cuối có `modify_time` khác null và bằng `(int) $modifyTime`;
  - không có dòng, `modify_time` null hoặc khác thì `true`.
- `TheoDoiSinh::capNhatModify(LanSinh $dong, $modifyTime): void` — cập nhật `modify_time` của dòng đó.
- `TheoDoiSinh::ghi(array $ketQua)` giữ nguyên chữ ký. `$ketQua` nay có thêm khoá `modify_time` và `noi_dung_hash`, có thể null.
- `canSinh()` cũ chỉ còn dùng ở chạy bù, hoặc gỡ nếu không còn chỗ gọi. Nếu gỡ thì gỡ cả test.

- [ ] **Step 1: test hỏng** trong `TheoDoiSinhTest`:
  - `test_can_xet_khi_chua_co_dong`;
  - `test_khong_xet_khi_modify_time_bang`;
  - `test_xet_khi_modify_time_khac`;
  - `test_xet_khi_dong_cu_chua_co_modify_time` (Review Focus 4);
  - `test_cap_nhat_modify_khong_them_dong`;
  - `test_ghi_luu_hash_va_modify_time`.
- [ ] **Step 2–4:** chạy hỏng → cài đặt → qua cả thư mục.
- [ ] **Step 5: Commit** `feat(sinh-xml3176): bang theo doi luu modify_time va hash noi dung`

---

### Task 2: Ứng viên theo MODIFY_TIME trong cửa sổ OUT_TIME

**Files:** `HisUngVien.php`, `CauHinhSinhXml3176.php`, `config/sinh_xml3176.php`, `tests/Support/DungBangHisSinhXml3176Sqlite.php` (cột `modify_time` bigint nullable trên `his_treatment` giả), `HisUngVienTest.php`, `CauHinhSinhXml3176Test.php`.

**Interfaces (Produces):**
- `CauHinhSinhXml3176::cuaSoRaVienNgay(): int` — mặc định 60, nhỏ hơn 1 thì dùng 1.
- `HisUngVien::layLo($loaiBan, $tuMoc, $gioiHan, $sauId = 0, $denMoc = null, $theoNgay = false)`:
  - `$theoNgay = false` (lượt quét):
    - keyset trên `modify_time`;
    - thêm `out_time >= (hôm nay 00:00 − cuaSoRaVienNgay ngày)` ở dạng `yyyyMMddHHmmss`;
    - lấy hôm nay qua một điểm có thể ghi đè trong test.
  - `$theoNgay = true` (chạy bù `--tu-ngay`): giữ nguyên cột `fee_lock_time` / `out_time` như hiện nay, không có cửa sổ.
- `select` thêm cột `modify_time`. `layTheoMa` cũng trả `modify_time`.
- `HisUngVien::cotMoc($loaiBan)` giữ nghĩa cột ngày dùng cho chạy bù. Thêm `const COT_QUET = 'modify_time'`.

- [ ] **Step 1: test hỏng:**
  - `test_lay_lo_theo_modify_time_va_keyset`;
  - `test_cua_so_ra_vien_loai_ho_so_ra_vien_cu`;
  - `test_chay_bu_theo_ngay_van_dung_fee_lock_time` (và `out_time` cho thông tuyến);
  - `test_dieu_kien_loai_ban_giu_nguyen`;
  - `CauHinhSinhXml3176Test::test_cua_so_ra_vien_mac_dinh_60`.
- [ ] **Step 2–4:** chạy hỏng → cài đặt → qua cả thư mục.
- [ ] **Step 5: Commit** `feat(sinh-xml3176): ung vien theo MODIFY_TIME trong cua so OUT_TIME`

---

### Task 3: Lượt quét bỏ qua sớm, ghi theo hash, trạng thái khong_doi

**Files:** `LuotQuet.php`, `SinhXml3176Quet.php`, `LuotQuetTest.php`, `SinhXml3176QuetTest.php`.

**Interfaces:**
- Consumes `canXet`, `dongCuoi`, `capNhatModify` (Task 1) và `layLo(..., $theoNgay)` (Task 2).
- `LuotQuet::chay($loaiBan)`:
  - lọc bằng `canXet(id, loai, modify_time)`; hồ sơ không qua thì đếm `bo_qua`;
  - mốc tiến theo `max(modify_time)` của trang.
- `motHoSo`:
  - dựng `$xmlCon` như hiện nay rồi tính `noi_dung_hash` theo Global Constraints;
  - dòng cuối cùng loại bản có `trang_thai = da_ghi` và hash bằng (chỉ khi chạy theo lượt, không phải chạy bù): trả `khong_doi`, không gọi `GhiTep`;
  - kết quả luôn mang `modify_time` và `noi_dung_hash`.
- `xuLyLo`:
  - `khong_doi`: `capNhatModify(dongCuoi, modify_time)`, không `ghi`;
  - `bi_loai` mà dòng cuối cũng là `bi_loai` cùng `ly_do`: `capNhatModify`, không `ghi`;
  - còn lại: `ghi` như cũ.
- Chạy bù (`chayChoMa`, `chayChoKhoangNgay`) luôn ghi tệp và luôn `ghi` dòng có hash. `chayChoKhoangNgay` gọi `layLo(..., true)`.
- `ketQuaRong()` có thêm `'khong_doi' => 0`. Lệnh in thêm `khong_doi=` sau `bo_qua=`.

- [ ] **Step 1: test hỏng** trong `LuotQuetTest`:
  - `test_modify_time_khong_doi_khong_nap_lai` (Review Focus 1): hai lượt liền, lượt hai không gọi nạp hồ sơ;
  - `test_mo_khoa_sua_khoa_lai_giu_fee_lock_time_van_sinh_lai` (Review Focus 2);
  - `test_chi_cham_ho_so_noi_dung_khong_doi_khong_ghi_tep` (Review Focus 3): đếm tệp trong thư mục và dòng theo dõi;
  - `test_dong_cu_khong_hash_thi_ghi` (Review Focus 4);
  - `test_thong_tuyen_sang_chinh_thuc_van_ghi` (Review Focus 5);
  - `test_bi_loai_cung_ly_do_khong_them_dong`;
  - `test_chay_bu_luon_ghi_va_luu_hash`;
  - `test_moc_tien_theo_modify_time`.

  Trong `SinhXml3176QuetTest`: dòng tổng kết có `khong_doi=`.
- [ ] **Step 2–4:** chạy hỏng → cài đặt → qua cả thư mục.
- [ ] **Step 5: Commit** `feat(sinh-xml3176): quet theo MODIFY_TIME, chi ghi khi noi dung doi`

---

### Task 4: Kiểm thật trên dev và tài liệu

Chạy trên máy dev với `ghi_thu_muc_that = false`. HIS chỉ `SELECT`; chỉ ghi vào bảng theo dõi của module và thư mục thử. Chỉ in mã điều trị, không in dữ liệu bệnh nhân.

- [ ] **Step 1:** `php artisan migrate --path=database/migrations/2026_10_23_100003_add_modify_time_hash_to_sinh_xml3176_lan_sinh.php`. Chỉ migration của module; xác nhận với controller trước khi chạy vì `.env` dev trỏ CSDL thật.
- [ ] **Step 2:** chọn bằng SELECT 3 hồ sơ khoá viện phí 15–22/10 có dịch vụ `modify_time > fee_lock_time`.
  - Chạy bù `--ma-dieu-tri` để có dòng theo dõi có hash.
  - Đặt mốc chính thức lùi về trước `modify_time` của các hồ sơ đó: chỉ cập nhật bảng `sinh_xml3176_moc` của module, ghi lại giá trị cũ để trả lại.
  - Chạy một lượt `sinhxml3176:quet`. Kỳ vọng: ba hồ sơ `khong_doi`, vì nội dung không đổi kể từ lần chạy bù.
  - Trả mốc về giá trị cũ.
- [ ] **Step 3:** đo một lượt quét thường từ mốc hiện tại: số ứng viên, `bo_qua`, `khong_doi`, `da_ghi`, thời gian, bộ nhớ đỉnh. Chạy hai lượt liền để thấy `bo_qua` do bỏ qua sớm.
- [ ] **Step 4:** cập nhật `docs/trien-khai-sinh-xml3176.md`:
  - mục "Lần chạy đầu" và "Lưu ý vận hành": quét theo `MODIFY_TIME`, cửa sổ `cua_so_ra_vien_ngay`, chuyển tiếp mốc, trạng thái `khong_doi`;
  - cấu hình mẫu và `docs/organization.php` nếu có liệt kê khoá kỹ thuật;
  - danh sách kiểm: thêm "chạy migration `2026_10_23_100003`".
- [ ] **Step 5: Commit** `docs(sinh-xml3176): quet theo MODIFY_TIME va ket qua kiem tren dev`
