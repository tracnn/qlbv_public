# Màn quản trị bảng tra "Vật phẩm kèm DVKT mẹ" (XML3176)

**Ngày:** 2026-10-13
**Module:** XML3176 — tiền giám định
**Tiền đề:** `docs/superpowers/specs/2026-10-13-xml3176-vat-pham-kem-dvkt-design.md` (quy tắc R1/R2, bảng tra, lệnh học/nạp — đã triển khai, commit `8cd8cb2b`)
**Trạng thái:** Design đã duyệt, chờ lập plan

## 1. Mục tiêu

Hiện bảng tra `xml3176_vat_pham_kem_dvkt` chỉ quản lý được bằng dòng lệnh (`xml3176:hoc-vat-pham-kem-dvkt`, `xml3176:nap-vat-pham-kem-dvkt`) và vòng Excel. Người duyệt nghiệp vụ không có quyền vào máy chủ. Cần một màn hình để:

1. Xem, lọc, bật/tắt, ghi chú từng cặp vật phẩm → DVKT mẹ.
2. Thêm/xoá dòng bổ sung tay (`TAY`), có kiểm khớp với dữ liệu XML để dòng thêm vào thật sự có tác dụng.
3. Học lại từ giao diện (chạy nền), xuất/nhập Excel.
4. Duyệt **ứng viên** — vật phẩm gần đủ điều kiện học — để thêm thành dòng tay.
5. Ghi người và thời điểm sửa cuối (giải trình với BHXH).

## 2. Ràng buộc đã đo

| Ràng buộc | Hệ quả |
|---|---|
| Học lại mất 2–5 phút trên ~1,4 triệu dòng XML3; request web giới hạn 120 giây trên máy chủ | Học phải chạy nền qua hàng đợi |
| Tính ứng viên cần cùng truy vấn `GROUP BY` (1–2 phút) | Ứng viên phải lưu sẵn, tính trong lượt học |
| Bảng tra ~660 dòng | Xuất/nhập Excel chạy đồng bộ được |
| PHP Windows không có `pcntl` → `$timeout` của job không được thi hành | Phát hiện treo bằng mốc thời gian (khuôn `Xml3176TepXuatService::danhDauTreo`) |
| Màn danh mục BHYT hiện có: AdminLTE, yajra DataTables server-side, `checkrole:category-manager`, SweetAlert + toastr, menu tĩnh `config/adminlte.php` | Bám khuôn đó (`resources/views/category/bhyt/xml3176_error_catalog.blade.php`, `CategoryBHYTController`) |
| Worker `QLBV JobXuatTepXml3176` (kết nối `xuat_tep`, `retry_after` 3600, hàng đợi `JobXuatTepXml3176`) đã có và được `update.bat` khởi động lại | Dùng chung, không thêm dịch vụ NSSM |

## 3. Dữ liệu

### 3.1 Bảng tra `xml3176_vat_pham_kem_dvkt` — thêm cột (migration mới)

| Cột | Kiểu | Ý nghĩa |
|---|---|---|
| `nguoi_sua_id` | unsignedInteger, null | `users.id` của người sửa cuối qua màn hình; null khi do lệnh học/nạp dòng lệnh |
| `sua_luc` | timestamp, null | Thời điểm sửa cuối qua màn hình |

Ghi khi: bật/tắt, sửa ghi chú, thêm dòng tay (màn hình hoặc từ ứng viên), nạp Excel **từ màn hình**. Không ghi khi: lệnh học (`ghi()`), lệnh nạp dòng lệnh.

### 3.2 Bảng mới `xml3176_vpkd_lan_hoc`

| Cột | Kiểu | |
|---|---|---|
| `id` | increments | |
| `user_id` | unsignedInteger, null, index | null = chạy từ dòng lệnh |
| `trang_thai` | string(20), index | `CHO` \| `DANG_CHAY` \| `XONG` \| `LOI` |
| `ket_qua` | text (json), null | `{so_vat_pham:{VTYT,THUOC}, so_cap:{VTYT,THUOC}, them, cap_nhat, tat, bat_lai, so_ung_vien}` |
| `loi` | text, null | |
| `bat_dau_luc`, `xong_luc` | timestamp, null | |
| timestamps | | |

Model `App\Models\BHYT\Xml3176VpkdLanHoc`, hằng `CHO`, `DANG_CHAY`, `XONG`, `LOI`, cast `ket_qua` → array.

### 3.3 Bảng mới `xml3176_vpkd_ung_vien`

| Cột | Kiểu | |
|---|---|---|
| `id` | bigIncrements | |
| `lan_hoc_id` | unsignedInteger, null, index | |
| `loai` | string(10) | `VTYT` \| `THUOC` |
| `ma_vat_pham` | string(100) collation `utf8_bin` | |
| `ten_vat_pham` | string(1024), null | |
| `ten_khop` | string(191) collation `utf8_bin`, default `''` | Như bảng tra |
| `so_dong` | integer | Số dòng BHYT trả |
| `so_lien_ket` | integer | Số dòng có `ma_dich_vu` |
| `ty_le_gan` | decimal(5,4) | |
| `dvkt` | text (json) | `[{ma, ten, so_lan}]`, mã đã chuẩn hoá (`chuanHoaMaDvkt`), sắp `so_lan` giảm dần |
| timestamps | | |

Model `App\Models\BHYT\Xml3176VpkdUngVien`, cast `dvkt` → array. Bảng **thay toàn bộ** sau mỗi lượt học thành công (xoá hết rồi chèn, trong cùng giao dịch với `ghi()`).

### 3.4 Tiêu chí ứng viên

Vật phẩm (theo khoá khớp `VatPhamKemDvktKey::khoa`) là ứng viên khi:
- **không** được chọn vào bảng tra ở lượt học đó, **và**
- `so_lien_ket >= 1`, **và** một trong:
  - `nguong_ung_vien <= ty_le_gan < nguong`, hoặc
  - `ty_le_gan >= nguong` và `so_dong < toi_thieu`.

Config mới `xml3176.vat_pham_kem_dvkt.nguong_ung_vien = 0.5`.

## 4. Học nền

### 4.1 Learner

`VatPhamKemDvktLearner::hoc()` trả thêm khoá `'ung_vien'` — danh sách theo §3.3 (không gồm `id`, `lan_hoc_id`) — tính từ cùng hai truy vấn `GROUP BY` đang có (`chonVatPham` giữ toàn bộ thống kê, `capDaGan` mở rộng cho vật phẩm ứng viên). Không thêm lượt quét bảng XML nào.

Thêm `VatPhamKemDvktLearner::ghiUngVien(array $ungVien, $lanHocId = null): int` — xoá toàn bảng ứng viên, chèn danh sách mới, trả số dòng.

Lệnh `xml3176:hoc-vat-pham-kem-dvkt` (không `--thu`): sau `ghi()` gọi `ghiUngVien()`, và tạo một dòng `xml3176_vpkd_lan_hoc` (`user_id` null, `XONG`, kèm kết quả) để lịch sử hai đường học nhất quán. In thêm `Ứng viên: N`.

### 4.2 Service `App\Services\Xml3176\VatPhamKemDvktHocLaiService`

- `yeuCau(int $userId): array` → `['lan_hoc' => model, 'moi' => bool]`.
  - Gọi `danhDauTreo()` trước.
  - Đang có lượt `CHO`/`DANG_CHAY` → trả lượt đó, `moi = false` (không đẩy job).
  - Không có → tạo lượt `CHO`, đẩy `HocVatPhamKemDvktJob($id)` lên kết nối `xuat_tep`, hàng đợi `JobXuatTepXml3176`, `moi = true`.
- `danhDauTreo(): int` — lượt `DANG_CHAY` có `bat_dau_luc` quá **60 phút**, hoặc `CHO` có `created_at` quá **60 phút** → `LOI`, `loi = 'Treo quá 60 phút'`. Trả số lượt bị đánh dấu.
- `ganNhat(): ?Xml3176VpkdLanHoc`.

### 4.3 Job `App\Jobs\HocVatPhamKemDvktJob`

- `$tries = 1`, nhận `lanHocId` (int), không khai `$timeout`.
- Lượt không tồn tại hoặc không ở `CHO` → không làm gì.
- `DANG_CHAY` + `bat_dau_luc` → `set_time_limit(0)`, `memory_limit` 1024M → `hoc(nguong, toi_thieu)` theo config → trong một `DB::transaction`: `ghi()` + `ghiUngVien()` → `XONG`, `ket_qua`, `xong_luc`.
- Ngoại lệ → `LOI`, `loi` = thông báo, `xong_luc`; ném lại (vào `failed_jobs`).

## 5. Màn hình

View `resources/views/category/bhyt/vat_pham_kem_dvkt.blade.php`, extends `adminlte::page`, scripts trong `@push('after-scripts')`, mỗi request ajax tự gửi `_token`.

### 5.1 Thanh công cụ

- **Học lại** + nhãn trạng thái lượt gần nhất (`Xong dd/mm HH:ii bởi <tên>: thêm X, cập nhật Y, tắt Z, bật lại W, ứng viên N` / `Đang chạy từ HH:ii` / `Lỗi: …`). Đang `CHO`/`DANG_CHAY` → nút khoá, hỏi `trang-thai` mỗi 10 giây; chuyển `XONG` → toastr + tải lại hai bảng.
- **Xuất Excel** — tải trực tiếp, dùng `VatPhamKemDvktExport` với toàn bảng (cùng định dạng tệp lệnh dòng lệnh xuất).
- **Nhập Excel** — modal kéo-thả (Dropzone, `uploadMultiple: false`, chỉ `.xlsx`), dùng lại `VatPhamKemDvktImport` + `nap()` và ghi `nguoi_sua_id`/`sua_luc` cho dòng thêm/cập nhật. Phản hồi `Thêm X, cập nhật Y, lỗi Z`; có lỗi → nút tải tệp lỗi theo dòng (`Dòng Excel`, `Lý do`).

### 5.2 Tab "Bảng tra"

DataTable server-side trên `xml3176_vat_pham_kem_dvkt` left join `users`.

| Cột | |
|---|---|
| Loại, Mã vật phẩm, Tên vật phẩm, Mã DVKT, Tên DVKT, Nguồn, Số lần gắn, Tỷ lệ gắn (%) | chỉ đọc |
| Hiệu lực | công tắc |
| Ghi chú | văn bản + nút bút chì |
| Người sửa / lúc sửa | `users.name`, `dd/mm/yyyy HH:ii` |
| Thao tác | nút Xoá — chỉ hiện với `TAY` |

Lọc: Loại, Nguồn, Hiệu lực (select phía trên bảng, gửi kèm ajax). Ô tìm kiếm DataTables tìm trên `ma_vat_pham`, `ten_vat_pham`, `ma_dvkt`, `ten_dvkt`.

- **Bật/tắt:** SweetAlert có ô nhập ghi chú (điền sẵn ghi chú cũ). Tắt mà ô trống → không gửi; phía máy chủ trả 422 `Cần ghi lý do khi tắt`. Huỷ → trả công tắc về trạng thái cũ.
- **Sửa ghi chú:** SweetAlert ô nhập; chuỗi rỗng → `ghi_chu = null`.
- **Xoá (`TAY`):** SweetAlert "Bạn có chắc chắn?". Dòng không phải `TAY` → 422.

### 5.3 Modal "Thêm dòng tay"

1. **Loại** (VTYT / Thuốc).
2. **Mã vật phẩm** — select2 ajax tìm trong `medical_supply_catalogs` (`ma_vat_tu`, `ten_vat_tu`) hoặc thuốc (`MedicineCatalog`: `ma_thuoc`, `ten_thuoc`) theo loại; cho phép gõ mã tự do (tag).
3. **Kiểm khớp** — chọn mã xong gọi `kiem-khop`, hiện bảng các **tên xuất hiện trên XML** trong 12 tháng gần nhất cho mã đó: `tên | số dòng | số dòng BHYT trả | số hồ sơ`.
   - **VTYT mã nhóm chung** (`laMaNhomChung`): bắt buộc chọn một tên trong bảng (radio); không có tên nào → không cho lưu, báo `Mã nhóm chung chưa xuất hiện trên XML 12 tháng — không thể khớp`.
   - Trường hợp khác: tên điền sẵn tên đầu bảng; không có dòng XML → cảnh báo vàng `Chưa thấy trên XML 12 tháng`, vẫn cho lưu.
4. **DVKT mẹ** — select2 ajax đa chọn trong `service_catalogs` (`ma_dich_vu`, `ten_dich_vu`); cho phép gõ mã tự do.
5. **Ghi chú** (tuỳ chọn).

Lưu → mỗi DVKT một dòng `nguon = TAY`, `hieu_luc = 1`, `so_lan_gan = 0`, `ten_khop = tenKhop(loai, ma, ten)`, `ma_dvkt = chuanHoaMaDvkt(...)`, ghi `nguoi_sua_id`/`sua_luc`. Cặp đã tồn tại (theo khoá duy nhất) → bỏ qua và báo trong phản hồi `Đã có: …`; nếu tất cả đã có → 422.

### 5.4 Tab "Ứng viên chờ duyệt"

DataTable server-side trên `xml3176_vpkd_ung_vien`.

| Cột | |
|---|---|
| Loại, Mã, Tên, Số dòng BHYT, Số liên kết, Tỷ lệ gắn (%) | |
| DVKT từng gắn | danh sách ô tick `mã — tên (n lần)`; ô đã có trong bảng tra (bất kỳ nguồn) hiện đã tick, khoá, nhãn `đã có` |
| Thao tác | nút **Thêm thành dòng tay** |

Bấm nút → gửi `ung_vien_id` + danh sách mã DVKT đã tick → tạo dòng `TAY` như §5.3 với `ghi_chu = 'Duyệt từ ứng viên lượt #<lan_hoc_id>'`. Lọc: Loại. Sắp mặc định `so_dong` giảm dần.

## 6. Đường dẫn và quyền

Controller mới `App\Http\Controllers\Category\VatPhamKemDvktController` (không nhồi thêm vào `CategoryBHYTController` vốn đã lớn). Tất cả trong nhóm `prefix 'category/'`, `middleware checkrole:category-manager` (`routes/web.php` khối danh mục BHYT), tiền tố tên `category-bhyt.vat-pham-kem-dvkt.`:

| Phương thức | URL (sau `category/`) | Tên | Việc |
|---|---|---|---|
| GET | `bhyt/vat-pham-kem-dvkt` | `index` | Màn hình |
| GET | `bhyt/vat-pham-kem-dvkt/datatable` | `datatable` | Tab bảng tra |
| GET | `bhyt/vat-pham-kem-dvkt/ung-vien-datatable` | `ung-vien-datatable` | Tab ứng viên |
| POST | `bhyt/vat-pham-kem-dvkt/{id}/hieu-luc` | `hieu-luc` | `hieu_luc` (0/1), `ghi_chu` |
| POST | `bhyt/vat-pham-kem-dvkt/{id}/ghi-chu` | `ghi-chu` | `ghi_chu` |
| POST | `bhyt/vat-pham-kem-dvkt/{id}/xoa` | `xoa` | Chỉ `TAY` |
| GET | `bhyt/vat-pham-kem-dvkt/kiem-khop` | `kiem-khop` | `loai`, `ma` |
| GET | `bhyt/vat-pham-kem-dvkt/tim-vat-pham` | `tim-vat-pham` | select2: `loai`, `q` |
| GET | `bhyt/vat-pham-kem-dvkt/tim-dvkt` | `tim-dvkt` | select2: `q` |
| POST | `bhyt/vat-pham-kem-dvkt/them` | `them` | §5.3 |
| POST | `bhyt/vat-pham-kem-dvkt/ung-vien/{id}/them` | `ung-vien-them` | §5.4 |
| POST | `bhyt/vat-pham-kem-dvkt/hoc-lai` | `hoc-lai` | §4.2 `yeuCau` |
| GET | `bhyt/vat-pham-kem-dvkt/hoc-lai/trang-thai` | `trang-thai` | lượt gần nhất |
| GET | `bhyt/vat-pham-kem-dvkt/xuat` | `xuat` | tải `.xlsx` |
| POST | `bhyt/vat-pham-kem-dvkt/nhap` | `nhap` | Dropzone |
| POST | `bhyt/vat-pham-kem-dvkt/nhap-loi` | `nhap-loi` | tải tệp lỗi từ danh sách dòng lỗi gửi lên |

Xoá dùng POST (khuôn các màn hiện có, không có `$.ajax` DELETE).

**Kiểm đầu vào (máy chủ):** `loai` ∈ {VTYT, THUOC}; `ma_vat_pham` 1–100 ký tự; mỗi `ma_dvkt` 1–50 ký tự sau chuẩn hoá; `ten_vat_pham` bắt buộc khi VTYT mã nhóm chung; `ghi_chu` ≤ 1024; `hieu_luc` ∈ {0,1}. Lỗi → 422 `{message}`. Không tìm thấy dòng → 404.

**`kiem-khop`:** truy vấn `xml3176_xml3s` (VTYT, theo `ma_vat_tu`, chỉ mục sẵn) hoặc `xml3176_xml2s` (thuốc, theo `ma_thuoc`) với `ngay_yl >= ` 12 tháng trước, `GROUP BY` tên → `[{ten, ten_khop, so_dong, so_dong_bhyt, so_ho_so}]`, sắp `so_dong` giảm dần, tối đa 50.

**Menu:** `config/adminlte.php`, khối Quản lý danh mục → BHYT, ngay sau mục "Danh mục lỗi Xml 3176": `text 'Vật phẩm kèm DVKT mẹ'`, `route 'category-bhyt.vat-pham-kem-dvkt.index'`, `active ['category/bhyt/vat-pham-kem-dvkt*']`.

## 7. Ngoài phạm vi

- Lịch sử đầy đủ từng lần sửa (chỉ ghi người/lúc sửa cuối).
- Học theo lịch tự động (cron) — vẫn học tay theo hướng dẫn vận hành.
- Sửa mã/tên của dòng đã có (muốn đổi thì xoá dòng `TAY` và thêm lại; dòng `HOC` thì tắt).
- Phân quyền chi tiết hơn `category-manager`.

## 8. Kiểm thử

SQLite bộ nhớ qua `Tests\Support\Xml3176RuleTestSupport::bootXml3176Sqlite`; **cấm `RefreshDatabase`**; chạy `DB_HOST=127.0.0.1 vendor/bin/phpunit`.

- **Learner:** ứng viên theo đủ hai nhánh tiêu chí §3.4; vật phẩm đã vào bảng tra không là ứng viên; vật phẩm `so_lien_ket = 0` không là ứng viên; `dvkt` sắp đúng và đã chuẩn hoá `_GT`; `ghiUngVien` thay toàn bộ.
- **Service học nền:** đang `CHO`/`DANG_CHAY` → không đẩy job, trả lượt cũ (`Queue::fake`); không có → tạo `CHO` + đẩy đúng kết nối/hàng đợi; `danhDauTreo` đánh dấu lượt quá 60 phút, không đụng lượt mới.
- **Job:** thành công → `XONG`, `ket_qua` đủ khoá, bảng ứng viên được thay; lượt không ở `CHO` → không làm gì; lỗi → `LOI` + ném lại.
- **Lệnh học (không `--thu`):** tạo lượt `XONG` `user_id` null và ghi ứng viên.
- **Controller** (gọi thẳng method với `Request::create`, khuôn `XoaDanhMucControllerTest`, có user đăng nhập giả lập):
  - hiệu lực: bật → ghi `nguoi_sua_id`/`sua_luc`; tắt thiếu ghi chú → 422; id không có → 404;
  - ghi chú: chuỗi rỗng → null;
  - xoá: `HOC` → 422; `TAY` → xoá;
  - thêm: VTYT mã nhóm chung thiếu tên → 422; một mã nhiều DVKT → nhiều dòng `TAY`, `_GT` được chuẩn hoá; trùng toàn bộ → 422; trùng một phần → tạo phần còn lại và báo `Đã có`;
  - kiểm khớp: trả tên, số dòng, số dòng BHYT, số hồ sơ, chỉ trong 12 tháng;
  - ứng viên thêm: tạo đúng các dòng với ghi chú lượt; mã không thuộc `dvkt` của ứng viên → 422;
  - nhập: ghi `nguoi_sua_id`; trả số lỗi;
  - học lại / trạng thái: trả đúng JSON.
- **Route:** mọi route mới có `auth` và `checkrole:category-manager` (khuôn `tests/Unit/RouteXoaDanhMucTest.php`).
- **Menu:** cập nhật `tests/Unit/MenuDanhMucBhytTest.php`.
- **View:** biên dịch blade mới (khuôn `tests/Unit/Xml3176/Xml3176BladeCompilesTest.php`).
- **Thủ công:** mở màn trên máy dev (chỉ đọc CSDL thật: xem, lọc, kiểm khớp, tìm select2); thao tác ghi (bật/tắt, thêm, học lại) chỉ thử trên CSDL kiểm thử.

## 9. Triển khai

`update.bat` tự migrate (2 bảng mới + 2 cột) và khởi động lại `QLBV JobXuatTepXml3176`. Sau đó: vào màn, bấm **Học lại**, chờ `Xong`, duyệt hai tab. Cập nhật `docs/trien-khai-xml3176-vat-pham-kem-dvkt.md`: Bước 3–5 làm bằng màn hình; giữ cách dòng lệnh làm phương án dự phòng.

## 10. Tệp dự kiến

| Tệp | |
|---|---|
| `database/migrations/2026_10_14_*_them_nguoi_sua_vao_xml3176_vat_pham_kem_dvkt.php` | Mới |
| `database/migrations/2026_10_14_*_create_xml3176_vpkd_lan_hoc_table.php` | Mới |
| `database/migrations/2026_10_14_*_create_xml3176_vpkd_ung_vien_table.php` | Mới |
| `app/Models/BHYT/Xml3176VpkdLanHoc.php`, `Xml3176VpkdUngVien.php` | Mới |
| `app/Models/BHYT/Xml3176VatPhamKemDvkt.php` | Thêm fillable/cast 2 cột |
| `app/Services/Xml3176/VatPhamKemDvktLearner.php` | Ứng viên, `ghiUngVien`, `nap()` nhận người sửa |
| `app/Services/Xml3176/VatPhamKemDvktHocLaiService.php` | Mới |
| `app/Jobs/HocVatPhamKemDvktJob.php` | Mới |
| `app/Console/Commands/Xml3176HocVatPhamKemDvkt.php` | Ghi ứng viên + lượt học |
| `app/Http/Controllers/Category/VatPhamKemDvktController.php` | Mới |
| `resources/views/category/bhyt/vat_pham_kem_dvkt.blade.php` | Mới |
| `routes/web.php`, `config/adminlte.php`, `config/xml3176.php` | Thêm |
| `tests/Unit/Xml3176/VatPhamKemDvkt/*`, `tests/Unit/MenuDanhMucBhytTest.php` | Test |
| `docs/trien-khai-xml3176-vat-pham-kem-dvkt.md` | Cập nhật |
