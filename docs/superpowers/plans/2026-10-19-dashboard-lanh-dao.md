# Dashboard lãnh đạo — Kế hoạch triển khai

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Trang `/dashboard/lanh-dao` mới: hàng thẻ số + 4 tab (Thanh toán · Điều trị & giường · Khám & thuốc · Hệ ngoại), lọc theo khoảng ngày + cơ sở, "khoa" = đơn vị giường phê duyệt.

**Architecture:** Theo mẫu dashboard con (`OperatingRoomController` + service). Mỗi phần (thẻ số, 4 tab) là một lớp riêng trong `App\Services\Dashboard\LanhDao\`: hàm `truyVan*` lấy dòng thô từ Oracle, hàm tĩnh `dinhDang(...)` thuần biến dòng thô thành JSON — unit test chỉ test hàm thuần. `LanhDaoService::lay($phan, $ky)` điều phối + cache. Công thức ngày điều trị và khám mới/cũ được tách khỏi `HomeController` sang lớp dùng chung để trang chủ và trang mới không lệch.

**Tech Stack:** Laravel 5.5, PHP 7.4, PHPUnit 6.5, Oracle `HISPro` (chỉ đọc), MySQL `qlbv`, AdminLTE 2, Highcharts (đã nạp sẵn như `operating-room.blade.php`).

**Spec:** `docs/superpowers/specs/2026-10-19-dashboard-lanh-dao-design.md` — người làm đọc cả spec; định nghĩa chỉ số ở §5 spec là chuẩn, plan không chép lại.

## Global Constraints

- Không sửa kết quả trang chủ `/home`; chỉ chuyển công thức sang lớp dùng chung (Task 2).
- Chỉ đọc HIS. Mọi truy vấn HIS có điều kiện khoảng thời gian trên cột thời gian.
- Áp loại trừ đối tượng BN `config('organization.dashboard.exclude_patient_type_codes')` cho mọi chỉ số tính trên hồ sơ (dùng trait `ExcludesDashboardPatientTypes`).
- Cơ sở: `hn` → branch `[1]`, `nb` → `[81]`, `2cs` → `[1, 81]`. Branch 61 không bao giờ tính.
- Khoảng lọc tối đa **92 ngày**; `to ≥ from`.
- Cache: `Cache::remember` tham số 3 là **PHÚT** (Laravel 5.5): 5 phút; khoảng kết thúc trước hôm nay → 60 phút. Danh mục đơn vị: 60 phút.
- Mọi mã cấu hình nằm ở `config/organization.php` mục `dashboard_lanh_dao` (Task 1).
- Test: `setUp()` không có `: void` (Unit), `tearDown(): void` (Feature). Không `RefreshDatabase`. Không Mockery trên lớp có kiểu trả về — dùng lớp giả kế thừa (mẫu `tests/Feature/Dashboard/OperatingRoomControllerTest.php`). Chuỗi: `assertContains`, không `assertStringContainsString`.
- Gate test: `vendor/bin/phpunit --testsuite Unit` (+ file Feature của trang này). Suite đầy đủ đỏ sẵn 1 ca có chủ đích (`CtdtCauHinhTest::gui_len_cong_mac_dinh_tat`).
- Thời gian HIS là số `YYYYMMDDHH24MISS`.

## Review Focus

1. **Khoảng chưa kết thúc (`to` = hôm nay)** — lượt nằm đang diễn ra cắt tại *bây giờ*, và số ngày của kỳ trong công thức công suất cũng chỉ tính đến bây giờ (phần ngày), nếu không công suất hôm nay bị chia cho cả ngày → thấp giả. Test ở Task 1 (`KyLoc::soNgayThucTe`) và Task 5.
2. **Đơn vị có mẫu số 0** (không giường PD, không giao dịch, không hoàn ứng) — trả `null`, không chia cho 0, không hiện cột 0% giả. Test ở Task 1 (`tyLe`, `congSuat`) và Task 4.
3. **Lọc 2CS, cùng mã K ở hai cơ sở** — phải là hai cột "Viện Tim mạch (HN)" / "Khoa Tim mạch (NB)", không gộp. Test ở Task 3.
4. **BN nằm ghép hai giường / chuyển giường trong cùng đơn vị** — khoảng chồng chỉ tính một lần. Test ở Task 1 (`gopKhoang`) và Task 5.
5. **Khoa HIS không có `bhyt_code`, `KTEST`, hoặc mã không có trong danh mục giường** — không làm vỡ trang; bị bỏ hoặc hiện với tên HIS như spec §4. Test ở Task 3.

---

### Task 1: Cấu hình, `KyLoc` và hàm tính thuần

**Files:**
- Modify: `config/organization.php` (thêm khoá `dashboard_lanh_dao` cạnh `dashboard`, dòng ~232)
- Create: `app/Services/Dashboard/LanhDao/KyLoc.php`
- Create: `app/Services/Dashboard/LanhDao/TinhToan.php`
- Test: `tests/Unit/Dashboard/LanhDao/KyLocTest.php`, `tests/Unit/Dashboard/LanhDao/TinhToanTest.php`

**Interfaces:**
- Produces:
  - config `organization.dashboard_lanh_dao`:
    ```php
    'dashboard_lanh_dao' => [
        'co_so' => ['hn' => ['branch_id' => 1, 'ma_cskcb' => '01929', 'nhan' => 'HN'],
                    'nb' => ['branch_id' => 81, 'ma_cskcb' => '37470', 'nhan' => 'NB']],
        'pay_form_qr' => ['08', '07', '19'],
        'pay_form_hoan_online' => ['02'],
        'khoa_ngoai' => ['hn' => ['K19','K20','K21','K22','K23','K24','K52'], 'nb' => ['K19']],
        'phong_can_thiep_tim_mach' => ['CLS.CT0001','CLS.CT0002','CLS.CT0003','CLS.CT0004','CLS.CT0005','CLS.CT0006','CLS.CT0007',
                                       'NBKP03.K19','NBKP03.K20','NBKP03.K21','NBKP03.K22','NBKP03.K23'],
        'ma_khoa_bo_qua' => ['KTEST'],
        'so_ngay_toi_da' => 92,
    ],
    ```
  - `KyLoc::tuRequest(array $input, ?Carbon $now = null): KyLoc` — đọc `from`, `to` (Y-m-d), `co_so` (`hn|nb|2cs`, mặc định `2cs`); sai → ném `Illuminate\Validation\ValidationException::withMessages([...])` (Laravel 5.5 không có closure rule).
  - Thuộc tính public chỉ đọc: `$from` (`'YmdHis'` 00:00:00), `$to` (`'YmdHis'` 23:59:59), `$coSo` (`'hn'|'nb'|'2cs'`).
  - `branchIds(): array` (int[]), `denThucTe(): string` (min(`to`, now) dạng YmdHis), `soNgayThucTe(): float` (giây từ `from` đến `denThucTe()` ÷ 86400), `daKetThuc(): bool` (`to` < đầu ngày hôm nay), `khoaCache(string $phan): string` = `"lanh_dao:{$phan}:{$from}:{$to}:{$coSo}"`.
  - `TinhToan` (static, thuần):
    - `tyLe(float $tu, float $mau): ?float` — phần trăm, làm tròn 1 chữ số; `$mau <= 0` → `null`.
    - `trungVi(array $so): ?float` — rỗng → `null`.
    - `congSuat(float $ngayGiuong, int $giuongPd, float $soNgay): ?float` — `%`, 1 chữ số; `$giuongPd <= 0 || $soNgay <= 0` → `null`.
    - `gopKhoang(array $khoang): array` — vào `[[batDau, ketThuc], ...]` (giây epoch, chưa sắp), ra các khoảng không chồng, đã sắp.
    - `giayTrongKy(array $khoang, int $tu, int $den): int` — tổng giây của các khoảng (đã gộp) cắt theo `[$tu, $den]`.
    - `sapGiam(array $cot, string $truong): array` — sắp mảng các phần tử (mảng kết hợp) giảm dần theo `$truong`, `null` xuống cuối.
    - `const NHOM_PHUT = ['<10' => [0,10], '10–20' => [10,20], '20–30' => [20,30], '30–60' => [30,60], '60–120' => [60,120], '>120' => [120, null]]` (dùng cho Task 6 và SQL CASE).

- [ ] **Step 1: Viết test thất bại** `KyLocTest`:
  - `tu_request_doi_ngay_sang_ymdhis`: `from=2026-10-01,to=2026-10-19,co_so=nb` → `$from === '20261001000000'`, `$to === '20261019235959'`, `branchIds() === [81]`.
  - `co_so_mac_dinh_2cs_gom_hai_branch`: không có `co_so` → `[1, 81]`.
  - `qua_92_ngay_nem_validation`: `from=2026-01-01,to=2026-06-30` → `expectException(ValidationException::class)`.
  - `to_truoc_from_nem_validation`, `co_so_la_nem_validation` (`co_so=xyz`).
  - `den_thuc_te_cat_tai_now`: now = `2026-10-19 10:00:00`, `to=2026-10-19` → `denThucTe() === '20261019100000'`; `soNgayThucTe()` với `from=2026-10-19` ≈ `10/24` (delta 0.001).
  - `da_ket_thuc`: now `2026-10-19`, `to=2026-10-18` → `true`; `to=2026-10-19` → `false`.
- [ ] **Step 2: Viết test thất bại** `TinhToanTest`:
  - `ty_le_mau_0_tra_null`: `tyLe(3, 0) === null`; `tyLe(1, 3) === 33.3`.
  - `trung_vi`: `[5,1,3] → 3.0`, `[1,2,3,4] → 2.5`, `[] → null`.
  - `cong_suat`: `congSuat(30, 10, 3) === 100.0`; `congSuat(5, 0, 3) === null`.
  - `gop_khoang_chong_chi_tinh_mot`: `[[10,20],[15,30],[40,50]] → [[10,30],[40,50]]`; khoảng chạm nhau `[[10,20],[20,30]] → [[10,30]]`.
  - `giay_trong_ky_cat_hai_dau`: `giayTrongKy([[0,100]], 20, 50) === 30`; khoảng ngoài kỳ → 0.
  - `sap_giam_null_xuong_cuoi`.
- [ ] **Step 3: Chạy** `vendor/bin/phpunit tests/Unit/Dashboard/LanhDao` → FAIL (class not found).
- [ ] **Step 4: Cài đặt** config + `KyLoc` + `TinhToan` theo Interfaces.
- [ ] **Step 5: Chạy lại** → PASS.
- [ ] **Step 6: Commit** `feat(lanh-dao): cau hinh, KyLoc va ham tinh thuan`.

---

### Task 2: Tách công thức dùng chung khỏi `HomeController`

**Files:**
- Create: `app/Services/Dashboard/ChiSoDungChung.php`
- Modify: `app/Http/Controllers/HomeController.php` — `getDetailDayCountInpatient` (~dòng 155), `newpatient` (~1499), `reExamination` (~1561)
- Test: `tests/Unit/Dashboard/ChiSoDungChungTest.php`

**Interfaces:**
- Produces (static, trả `Illuminate\Database\Query\Builder` trên kết nối `HISPro`, **chưa** select/groupBy):
  - `const SQL_SO_NGAY_DIEU_TRI` — chuỗi biểu thức `CASE ... END` **chép nguyên văn** từ `getDetailDayCountInpatient` (không alias).
  - `noiTruRaVien(string $from, string $to, array $loaiTruIds, array $branchIds = []): Builder` — `his_treatment`, `out_time` between, `tdl_treatment_type_id = 3`, loại trừ `tdl_patient_type_id`, `branchIds` rỗng = không lọc.
  - `benhNhanMoi(string $from, string $to, array $loaiTruIds, array $branchIds = []): Builder` — đúng join/where của `newpatient` hiện tại (join `his_patient`, `his_branch`; `in_time` và `his_patient.create_time` between; `his_patient.is_delete = 0`).
  - `taiKham(string $from, string $to, array $loaiTruIds, array $branchIds = []): Builder` — đúng `reExamination` hiện tại (`create_time < $from`, `his_treatment.is_delete = 0`).
- `HomeController` giữ nguyên select/groupBy cũ, chỉ thay phần nền bằng các hàm trên, truyền `$this->excludedPatientTypeIds()` và `[]` cho branch.

- [ ] **Step 1: Viết test thất bại** `ChiSoDungChungTest` (đọc `->wheres` và `getBindings()` như `tests/Unit/BHYT/Xml3176ExportLocCoSoTest.php`, không `assertContains` tên cột trên `toSql()`):
  - `noi_tru_ra_vien_loc_out_time_va_loai_3`: có where `between` trên `out_time` với bindings `['20261001000000','20261019235959']`, where `tdl_treatment_type_id = 3`, **không** có where `branch_id` khi `branchIds=[]`.
  - `loc_branch_khi_co`: `branchIds=[81]` → có where `In` trên `his_treatment.branch_id` với `[81]`.
  - `loai_tru_doi_tuong`: `loaiTruIds=[5]` → where `NotIn` trên `tdl_patient_type_id`.
  - `benh_nhan_moi_va_tai_kham_giu_dieu_kien_cu`: kiểm điều kiện `create_time` between (mới) và `<` (tái khám).
  - `bieu_thuc_so_ngay_khong_doi`: `ChiSoDungChung::SQL_SO_NGAY_DIEU_TRI` chứa `'< 4 THEN 0'` và `'FLOOR('`.
- [ ] **Step 2: Chạy** → FAIL.
- [ ] **Step 3: Cài đặt** `ChiSoDungChung`; sửa 3 hàm của `HomeController` dùng nó.
- [ ] **Step 4: Chạy** `vendor/bin/phpunit tests/Unit/Dashboard` → PASS.
- [ ] **Step 5: Đối chiếu trang chủ không đổi**: trước khi sửa (stash) và sau khi sửa, gọi `fetchAverageDayInpatient` / `fetchNewpatient` cho `01/10–19/10` bằng script tinker trong scratchpad trên HIS thật; hai kết quả phải giống hệt. Dán số vào commit message.
- [ ] **Step 6: Commit** `refactor(home): tach cong thuc ngay dieu tri, kham moi/cu sang ChiSoDungChung`.

---

### Task 3: Danh mục đơn vị (khoa HIS → đơn vị giường phê duyệt)

**Files:**
- Create: `app/Services/Dashboard/LanhDao/DanhMucDonVi.php`
- Test: `tests/Unit/Dashboard/LanhDao/DanhMucDonViTest.php`

**Interfaces:**
- Consumes: config `organization.dashboard_lanh_dao.co_so`, `.ma_khoa_bo_qua`.
- Produces:
  - `static dung(array $khoaHis, array $dongGiuong, string $ngayYmd): array` — thuần.
    - `$khoaHis`: object có `id`, `department_name`, `bhyt_code`, `branch_id`.
    - `$dongGiuong`: object từ `department_bed_catalogs`: `ma_khoa`, `ma_cskcb`, `ten_khoa`, `giuong_pd`, `tu_ngay`, `den_ngay`.
    - Ra `['khoa' => [department_id => khoaDonVi], 'don_vi' => [khoaDonVi => ['ma','ten','branch_id','giuong_pd'(int|null)]]]`, `khoaDonVi = "{branch_id}|{ma}"`.
    - Quy tắc theo spec §4: bỏ `bhyt_code` rỗng và mã trong `ma_khoa_bo_qua`; bỏ branch không có trong `co_so`; chọn dòng giường còn hiệu lực tại `$ngayYmd` (tu_ngay rỗng hoặc ≤, den_ngay rỗng hoặc ≥), nhiều dòng → `tu_ngay` lớn nhất; tên = `ten_khoa` của dòng giường, không có → `department_name` ngắn nhất trong nhóm.
  - `static nhan(array $donVi, string $coSo): string` — `2cs` → `"{ten} (HN)"`/`"(NB)"` theo `co_so.*.nhan`; còn lại → `ten`.
  - `tai(string $ngayYmd): array` — đọc `his_department` (`is_active = 1`) và `department_bed_catalogs`, gọi `dung`, `Cache::remember('lanh_dao:don_vi:'.$ngayYmd, 60, ...)`.
- [ ] **Step 1: Viết test thất bại**:
  - `gop_khoa_con_cung_bhyt_code`: 3 khoa HN `K04` → cùng `"1|K04"`, tên lấy `ten_khoa` "Viện Tim mạch", `giuong_pd = 402`.
  - `cung_ma_hai_co_so_la_hai_don_vi`: `K04` branch 1 và 81 → `"1|K04"`, `"81|K04"`; `nhan(..., '2cs')` → `"Viện Tim mạch (HN)"` / `"Khoa Tim mạch (NB)"`; `nhan(..., 'hn')` không hậu tố.
  - `bo_ktest_rong_va_branch_61`.
  - `ma_khong_co_trong_danh_muc_giuong`: `K01` → có đơn vị, `giuong_pd === null`, tên = tên HIS ngắn nhất.
  - `chon_dong_giuong_con_hieu_luc`: hai dòng `K39` (`tu_ngay` 20250101 và 20260601), ngày `20261019` → lấy dòng 20260601; ngày `20260301` → dòng 20250101; dòng có `den_ngay` 20260101 bị bỏ khi ngày 20261019.
- [ ] **Step 2: Chạy** → FAIL. **Step 3: Cài đặt.** **Step 4: Chạy** → PASS.
- [ ] **Step 5: Commit** `feat(lanh-dao): danh muc don vi theo giuong phe duyet`.

---

### Task 4: Tab Thanh toán

**Files:**
- Create: `app/Services/Dashboard/LanhDao/TabThanhToan.php`
- Test: `tests/Unit/Dashboard/LanhDao/TabThanhToanTest.php`

**Interfaces:**
- Consumes: `KyLoc`, `DanhMucDonVi`, `TinhToan`, config `pay_form_qr`, `pay_form_hoan_online`.
- Produces:
  - `__construct(DanhMucDonVi $danhMuc)`; `lay(KyLoc $ky): array`.
  - `protected truyVan(KyLoc $ky): array` — một truy vấn: `his_transaction t` join `his_treatment tr` (`tr.id = t.treatment_id`) join `his_pay_form pf`; `t.transaction_time` between; `t.is_delete = 0`; `t.is_cancel IS NULL`; `t.transaction_type_id IN (1,2,3)`; `tr.tdl_patient_type_id` loại trừ; group by `tr.last_department_id`, `nhom` (`'thu'` nếu type 1/3, `'hoan'` nếu 2), `la_online` (1 nếu `pf.pay_form_code` thuộc danh sách QR với nhóm thu / danh sách hoàn online với nhóm hoàn); select `count(*) so_gd`, `sum(t.amount) tien`. Lọc branch theo **khoa** (`his_department.branch_id` qua `DanhMucDonVi`) làm ở `dinhDang`, không ở SQL.
  - `static dinhDang(array $dong, array $danhMuc, KyLoc $ky): array` →
    ```
    ['qr' => Chuoi, 'hoan_online' => Chuoi]
    Chuoi = ['categories' => string[], 'ty_le' => (float|null)[], 'so_gd' => int[], 'tong_gd' => int[], 'tien' => float[], 'tong_tien' => float[]]
    ```
    gộp dòng theo `khoaDonVi`, bỏ khoa không có trong danh mục hoặc ngoài `branchIds()`, bỏ đơn vị `tong_gd = 0`, sắp giảm theo `ty_le`.
- [ ] **Step 1: Viết test thất bại** với dòng giả:
  - `gop_khoa_con_va_tinh_ty_le_qr`: 2 khoa con của `1|K04` (thu: online 30/50, online 20/50) → một cột, `ty_le = 50.0`, `so_gd = 50`, `tong_gd = 100`.
  - `hoan_online_tach_rieng_qr`: dòng `nhom='hoan'` không ảnh hưởng chuỗi `qr`.
  - `loc_co_so_theo_khoa`: `co_so=nb` → không có cột đơn vị branch 1.
  - `don_vi_khong_co_giao_dich_hoan_khong_hien`.
  - `sap_giam_theo_ty_le`.
- [ ] **Step 2: Chạy** → FAIL. **Step 3: Cài đặt.** **Step 4: Chạy** → PASS.
- [ ] **Step 5: Commit** `feat(lanh-dao): tab thanh toan - ty le QR va hoan online theo don vi`.

---

### Task 5: Tab Điều trị & giường

**Files:**
- Create: `app/Services/Dashboard/LanhDao/TabDieuTriGiuong.php`
- Test: `tests/Unit/Dashboard/LanhDao/TabDieuTriGiuongTest.php`

**Interfaces:**
- Consumes: `KyLoc`, `DanhMucDonVi`, `TinhToan`, `ChiSoDungChung::noiTruRaVien`, `SQL_SO_NGAY_DIEU_TRI`.
- Produces:
  - `lay(KyLoc $ky): array`; `tongCoSo(KyLoc $ky): array` (dùng cho thẻ số — Task 8). Thẻ số ngày ĐT gom theo `his_treatment.branch_id` (spec §4: thẻ không chia khoa theo hồ sơ); thẻ công suất = Σngày-giường các đơn vị của cơ sở ÷ (Σ`giuong_pd` × `soNgayThucTe`).
  - `protected truyVanNgayDieuTri(KyLoc $ky): array` — `ChiSoDungChung::noiTruRaVien(...)` select `last_department_id`, `branch_id`, `SUM(<SQL_SO_NGAY_DIEU_TRI>) tong_ngay`, `COUNT(*) so_ho_so`, group by hai cột.
  - `protected truyVanLuotNam(KyLoc $ky): iterable` — `his_treatment_bed_room tbr` join `his_bed_room br` join `his_room r` join `his_treatment t`; left join `his_co_treatment ct` loại `ct.id IS NOT NULL`; `tbr.is_delete = 0`; `t.tdl_treatment_type_id IN (3,4)`; loại trừ đối tượng; **chồng kỳ**: `tbr.add_time <= denThucTe` và (`tbr.remove_time IS NULL` hoặc `tbr.remove_time >= from`); select `tbr.treatment_id`, `r.department_id`, `tbr.add_time`, `tbr.remove_time`; `orderBy treatment_id, department_id, add_time`; trả `->cursor()` để không nạp hết vào RAM (prod 128MB).
  - `protected truyVanDangNam(): array` — đúng điều kiện "đang dùng" của `HomeController::bedStatusByDepartment` (~dòng 396), group by `r.department_id`.
  - `static ngayGiuongTheoDonVi(iterable $luot, array $khoaDonVi, int $tu, int $den): array` — thuần, chạy **một lượt** trên dữ liệu đã sắp: gom khoảng theo (`treatment_id`, đơn vị), `remove_time` null → `$den`, gọi `TinhToan::gopKhoang` + `giayTrongKy`, cộng vào đơn vị; trả `[khoaDonVi => ngayGiuong(float)]`.
  - `static dinhDang(array $ngayDieuTri, array $ngayGiuong, array $dangNam, array $danhMuc, KyLoc $ky): array` →
    ```
    ['ngay_dieu_tri' => ['categories'=>[], 'gia_tri'=>float[] (2 chữ số), 'so_ho_so'=>int[]],
     'cong_suat'     => ['categories'=>[], 'gia_tri'=>float[], 'ngay_giuong'=>float[], 'giuong_pd'=>int[]],
     'giuong_hien_tai' => [['don_vi'=>string,'giuong_pd'=>int,'dang_nam'=>int,'con_trong'=>int,'cong_suat'=>float|null], ...]]
    ```
    Ngày ĐT TB đơn vị = `Σtong_ngay / Σso_ho_so`. Công suất dùng `TinhToan::congSuat($ngayGiuong, $giuongPd, $ky->soNgayThucTe())`; chỉ đơn vị `giuong_pd > 0`. `con_trong` có thể âm. Mọi mảng sắp giảm theo `gia_tri`/`cong_suat`.
- [ ] **Step 1: Viết test thất bại**:
  - `ngay_giuong_cat_theo_ky`: lượt `[ky.from − 1 ngày, ky.from + 12h]` → 0.5 ngày.
  - `nam_ghep_hai_giuong_tinh_mot`: cùng treatment, cùng đơn vị, hai lượt chồng hoàn toàn 24h → 1.0 (không phải 2.0).
  - `chuyen_khoa_trong_ngay_chia_dung_phan`: 6h ở `1|K04` rồi 18h ở `1|K05` → 0.25 / 0.75.
  - `luot_dang_nam_cat_tai_den_thuc_te`: `remove_time` null, `$den` = now.
  - `cong_suat_hom_nay_chia_theo_phan_ngay`: 10 giường PD, 10 BN nằm từ 00:00 đến now = 10:00, kỳ = hôm nay → 100.0 (không phải ~41.7).
  - `ngay_dieu_tri_tb_gop_khoa_con_theo_trong_so`: (tong 10/so 5) + (tong 30/so 5) → 4.0.
  - `don_vi_khong_giuong_pd_khong_vao_cong_suat`.
- [ ] **Step 2: Chạy** → FAIL. **Step 3: Cài đặt.** **Step 4: Chạy** → PASS.
- [ ] **Step 5: Commit** `feat(lanh-dao): tab dieu tri va giuong - ngay DT TB, cong suat theo thoi luong`.

---

### Task 6: Tab Khám & thuốc

**Files:**
- Create: `app/Services/Dashboard/LanhDao/TabKhamThuoc.php`
- Test: `tests/Unit/Dashboard/LanhDao/TabKhamThuocTest.php`

**Interfaces:**
- Consumes: `KyLoc`, `TinhToan::NHOM_PHUT`, `ChiSoDungChung::benhNhanMoi`, `taiKham`.
- Produces:
  - `lay(KyLoc $ky): array`; `tongCoSo(KyLoc $ky): array` (thẻ số).
  - `protected truyVanThoiGianKham(KyLoc $ky): array` — `his_treatment`, `tdl_treatment_type_id = 1`, `out_time` between, `is_delete = 0`, branch in `[1,81]`, loại trừ đối tượng; phút = `(TO_DATE(out_time) − TO_DATE(in_time)) * 1440`, chỉ `> 0` và `<= 1440`; select `branch_id`, `gio` = `TO_NUMBER(SUBSTR(in_time, 9, 2))`, `MEDIAN(phut) trung_vi`, `COUNT(*) so_ho_so`; `GROUP BY GROUPING SETS ((branch_id, gio), (branch_id), (gio), ())` — trung vị 2CS phải tính trên dữ liệu gộp, **không** lấy trung bình hai trung vị.
  - `protected truyVanKeDonMuaThuoc(KyLoc $ky): array` — `his_exp_mest e` join `his_exp_mest_type ty` với `ty.exp_mest_type_code = '08'` (hằng `MA_LOAI_BAN = '08'`, đã tra trên HIS: id 8 "Bán"; không khớp theo tên) join `his_service_req sr` on `sr.id = e.prescription_id` join `his_treatment tr` on `tr.id = sr.treatment_id` (`his_service_req` không có cột branch → cơ sở = `tr.branch_id`, loại trừ đối tượng trên `tr.tdl_patient_type_id`); `e.finish_time` between; phút = `finish_time − sr.intruction_time`, chỉ `>= 0`; select `branch_id`, `nhom` (CASE theo `NHOM_PHUT`), `COUNT(*)`, và `MEDIAN(phut)` qua `GROUPING SETS ((branch_id, nhom), (branch_id), ())`.
  - `protected truyVanMoiCu(KyLoc $ky): array` — `benhNhanMoi`/`taiKham` với `branchIds = [1,81]`, group by `his_treatment.branch_id`.
  - `static dinhDang(array $kham, array $thuoc, array $moiCu, KyLoc $ky): array` →
    ```
    ['tg_kham_theo_gio' => ['gio' => [6..17], 'trung_vi' => (float|null)[], 'so_ho_so' => int[]],
     'ke_don_mua_thuoc' => ['nhom' => array_keys(NHOM_PHUT), 'so_phieu' => int[]],
     'moi_cu' => ['co_so' => ['HN','NB'], 'moi' => int[], 'cu' => int[]]]
    ```
    Dòng của biểu đồ giờ lấy theo cơ sở đang lọc (`2cs` → dòng `branch_id` NULL). Giờ ngoài 6–17 không hiện nhưng vẫn tính trong thẻ số.
- [ ] **Step 1: Viết test thất bại**:
  - `bieu_do_gio_lay_dong_gop_khi_2cs`: dòng GROUPING SETS giả có `branch_id = null, gio = 8` → dùng dòng đó; `co_so=hn` → dùng dòng `branch_id = 1`.
  - `gio_thieu_du_lieu_la_null`: giờ 9 không có dòng → `trung_vi[3] === null`, `so_ho_so[3] === 0`.
  - `nhom_thuoc_du_6_nhom_dung_thu_tu`.
  - `moi_cu_theo_co_so`.
- [ ] **Step 2: Chạy** → FAIL. **Step 3: Cài đặt.** **Step 4: Chạy** → PASS.
- [ ] **Step 5: Commit** `feat(lanh-dao): tab kham va thuoc - trung vi thoi gian kham, ke don den mua thuoc`.

---

### Task 7: Tab Hệ ngoại

**Files:**
- Create: `app/Services/Dashboard/LanhDao/TabHeNgoai.php`
- Test: `tests/Unit/Dashboard/LanhDao/TabHeNgoaiTest.php`

**Interfaces:**
- Consumes: `KyLoc`, `DanhMucDonVi`, config `khoa_ngoai`, `phong_can_thiep_tim_mach`.
- Produces:
  - `lay(KyLoc $ky): array`; `tongCoSo(KyLoc $ky): array` (thẻ số: `y_lenh_pt`, `ca_pt`, `can_thiep_tm` theo branch).
  - `protected truyVanPhauThuat(KyLoc $ky): array` — `his_service_req`, `service_req_type_id = 10`, `intruction_time` between, `is_delete = 0`; group by `request_department_id`, `service_req_stt_id`; `count(*)`.
  - `protected truyVanCanThiep(KyLoc $ky): array` — `his_service_req sr` join `his_execute_room er` on `er.room_id = sr.execute_room_id`; `er.execute_room_code IN (config)`; `sr.service_req_stt_id = 3`; `intruction_time` between; `is_delete = 0`; join `his_room ro` on `ro.id = er.room_id` join `his_department d` on `d.id = ro.department_id`; group by `er.execute_room_code`, `er.execute_room_name`, `d.branch_id`; `count(*)`.
  - `static dinhDang(array $pt, array $canThiep, array $danhMuc, KyLoc $ky): array` →
    ```
    ['theo_khoa' => ['categories'=>[], 'y_lenh'=>int[], 'da_thuc_hien'=>int[]],
     'can_thiep' => ['categories'=>[], 'so_ca'=>int[]]]
    ```
    Chỉ đơn vị có mã trong `khoa_ngoai[hn|nb]` đúng cơ sở; `da_thuc_hien` = dòng `service_req_stt_id = 3`; sắp giảm theo `y_lenh`.
- [ ] **Step 1: Viết test thất bại**:
  - `chi_khoa_ngoai_dung_co_so`: `K19` branch 81 có, `K20` branch 81 (không trong `khoa_ngoai.nb`) bị bỏ; `K26` (gây mê) không xuất hiện.
  - `y_lenh_gom_moi_trang_thai_da_thuc_hien_chi_stt_3`.
  - `can_thiep_loc_co_so`: `co_so=hn` bỏ phòng `NBKP03.*`.
- [ ] **Step 2: Chạy** → FAIL. **Step 3: Cài đặt.** **Step 4: Chạy** → PASS.
- [ ] **Step 5: Commit** `feat(lanh-dao): tab he ngoai - y lenh PT, ca PT, can thiep tim mach`.

---

### Task 8: Thẻ số, `LanhDaoService`, controller, route, cache

**Files:**
- Create: `app/Services/Dashboard/LanhDao/TheSo.php`
- Create: `app/Services/Dashboard/LanhDao/LanhDaoService.php`
- Create: `app/Http/Controllers/Dashboard/LanhDaoController.php`
- Modify: `routes/web.php` (trong nhóm `checkrole:dashboard`, sau route `dashboard.operating-room.utilization` ~dòng 169)
- Test: `tests/Unit/Dashboard/LanhDao/TheSoTest.php`, `tests/Feature/Dashboard/LanhDaoControllerTest.php`

**Interfaces:**
- Consumes: `tongCoSo()` của Task 5, 6, 7; `ChiSoDungChung::noiTruRaVien`.
- Produces:
  - `TheSo::lay(KyLoc $ky): array`; `static dinhDang(array $phan, KyLoc $ky): array` →
    ```
    ['ngay_dieu_tri'      => ['gia_tri'=>?float, 'hn'=>?float, 'nb'=>?float],
     'cong_suat_giuong'   => [... cùng dạng],
     'tg_kham_phut'       => [... + 'so_ho_so'=>int],
     'ke_don_mua_thuoc_phut' => [... + 'so_phieu'=>int],
     'kham_moi_cu'        => ['moi'=>int, 'cu'=>int, 'ty_le_moi'=>?float],
     'he_ngoai'           => ['y_lenh_pt'=>int, 'ca_pt'=>int, 'can_thiep_tm'=>int]]
    ```
    `gia_tri` = theo `coSo` đang lọc (`2cs` → giá trị gộp, không trung bình hai cơ sở); `hn`/`nb` luôn có.
  - `LanhDaoService::PHAN = ['the-so','thanh-toan','dieu-tri-giuong','kham-thuoc','he-ngoai']`; `lay(string $phan, KyLoc $ky): array` — `Cache::remember($ky->khoaCache($phan), $ky->daKetThuc() ? 60 : 5, ...)`, phân phối tới lớp tương ứng (lấy qua container).
  - Route (tên):
    - `GET dashboard/lanh-dao` → `LanhDaoController@index`, `dashboard.lanh-dao`
    - `GET dashboard/lanh-dao/{phan}` → `LanhDaoController@phan`, `dashboard.lanh-dao.phan`, `->where('phan', 'the-so|thanh-toan|dieu-tri-giuong|kham-thuoc|he-ngoai')`
  - `LanhDaoController::phan(Request $request, string $phan)` — `KyLoc::tuRequest($request->all())` (ValidationException → 422 JSON tự động với AJAX) → `response()->json($service->lay($phan, $ky))`.
- [ ] **Step 1: Viết test thất bại** `TheSoTest`: `gia_tri_2cs_lay_so_gop_khong_trung_binh` (hn 10 phút/100 hồ sơ, nb 30 phút/10 hồ sơ, gộp 12 → `gia_tri === 12.0`), `co_so_nb_gia_tri_bang_nb`, `ty_le_moi_mau_0_null`.
- [ ] **Step 2: Viết test thất bại** `LanhDaoControllerTest` (lớp giả `FakeLanhDaoService extends LanhDaoService`, đếm lần gọi + ghi tham số; user `Tests\Support\UserGiaCoQuyen::tao()`):
  - `moi_phan_tra_json_va_goi_dung_phan`: 5 phần, mỗi phần gọi `lay` đúng 1 lần với `$phan` đúng và `KyLoc` có `from = '20261001000000'`, `coSo = 'nb'`.
  - `phan_la_404`: `/dashboard/lanh-dao/xyz` → 404.
  - `qua_92_ngay_422`, `thieu_from_422`.
  - `trang_index_200_va_co_cau_hinh_route`: thân trả về chứa `LANH_DAO_CFG`.
  - Kiểm đột biến (bẫy test-infra 2b): tạm hoán `$phan` hoặc bỏ `tuRequest` trong controller → ít nhất một ca đỏ; khôi phục.
- [ ] **Step 3: Chạy** → FAIL. **Step 4: Cài đặt** `TheSo`, `LanhDaoService`, controller, route. **Step 5: Chạy** → PASS.
- [ ] **Step 6: Commit** `feat(lanh-dao): the so, service dieu phoi + cache, controller va route`.

---

### Task 9: Giao diện, menu và nghiệm thu trên HIS thật

**Files:**
- Create: `resources/views/dashboard/lanh-dao.blade.php` (mẫu `resources/views/dashboard/operating-room.blade.php`: `@extends('adminlte::page')`, nạp Highcharts như mẫu)
- Create: `public/js/dashboard/lanh-dao.js`
- Modify: `config/adminlte.php` — mục `'Dashboard lãnh đạo'`, icon `line-chart`, `checkrole => 'dashboard'`, `route => 'dashboard.lanh-dao'`, `active => ['dashboard/lanh-dao*']`, đặt sau `'Công suất phòng mổ'` (~dòng 291)

**Interfaces:**
- Consumes: route `dashboard.lanh-dao.phan`; JSON của Task 4–8.
- Produces: `window.LANH_DAO_CFG = { url: '{{ url('dashboard/lanh-dao') }}' }`; JS gọi `url + '/' + phan + '?from=&to=&co_so='`.

Hành vi bắt buộc:
- Bộ lọc: hai `input type=date` (mặc định đầu tháng → hôm nay), `select` cơ sở (`2cs` mặc định, `hn`, `nb`), nút Xem.
- Hàng 6 thẻ (AdminLTE `small-box`/`info-box`): số lớn = `gia_tri`, dòng nhỏ `HN x · NB y`; `null` → "—". Thẻ kê đơn có ghi chú "chỉ nhà thuốc BV".
- 4 tab `nav-tabs`; tab nạp lần đầu khi mở (`shown.bs.tab`); đổi bộ lọc → nạp lại thẻ số + tab đang mở, đánh dấu các tab khác "bẩn" để nạp khi mở.
- Biểu đồ cột theo đơn vị: tooltip theo spec §5; công suất có `plotLines` 100%, cột > 100 màu cảnh báo; `null` không vẽ cột.
- Bảng giường hiện tại: `con_trong < 0` chữ đỏ.
- Lỗi 422/500: hiện thông báo trong box tương ứng, không để spinner quay mãi.

- [ ] **Step 1: Viết view, JS, menu.**
- [ ] **Step 2: Chạy trang** bằng preview `artisan-serve` (`.claude/launch.json`), đăng nhập user có quyền `dashboard`, mở `/dashboard/lanh-dao`. Kiểm `read_console_messages` không lỗi; mở đủ 4 tab với `01/10–19/10`, cả 3 lựa chọn cơ sở.
- [ ] **Step 3: Đối chiếu số** với truy vấn trực tiếp qua sqlcl `hispro_stb` (chỉ đọc) cho `01/10–19/10`:
  - Tỷ lệ QR toàn HN = đếm trực tiếp GD thu `pay_form_code IN ('08','07','19')` / tổng GD thu (branch khoa = 1).
  - Ngày ĐT TB thẻ số (`2cs` + loại branch 61) = giá trị trang chủ khi trang chủ chỉ có HN+NB (chênh nếu có branch 61 — ghi lại).
  - Khám mới/cũ khớp trang chủ cùng phạm vi.
  - Công suất giường HN nằm trong khoảng hợp lý (≈ 80–110%); nếu ngoài khoảng, điều tra trước khi báo xong.
- [ ] **Step 4: Đo thời gian** từng endpoint lần đầu (không cache) cho khoảng 92 ngày; endpoint nào > 15s thì ghi lại và báo (prod giới hạn 120s, 128MB).
- [ ] **Step 5: Chạy gate** `vendor/bin/phpunit --testsuite Unit` và `vendor/bin/phpunit tests/Feature/Dashboard/LanhDaoControllerTest.php` → xanh.
- [ ] **Step 6: Commit** `feat(lanh-dao): giao dien dashboard lanh dao - the so + 4 tab nap luoi, menu`.
