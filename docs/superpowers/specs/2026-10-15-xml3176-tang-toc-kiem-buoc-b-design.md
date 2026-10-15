# Tăng tốc quét kiểm XML3176 — Bước B (một job mỗi hồ sơ, gộp truy vấn kiểm tổng thể)

Ngày: 15/10/2026. Trạng thái: chờ duyệt. Tiếp nối Bước A (`docs/superpowers/specs/2026-10-15-xml3176-tang-toc-kiem-buoc-a-design.md`, đã deploy prod 15/10).

## 1. Vì sao

Đo chỉ đọc trên prod sau khi deploy Bước A (có chỉ mục), cùng mẫu 200 hồ sơ:
- Tổng ms SQL: 1.251 s (gốc) → **85 s (~0,43 s/hồ sơ)**, kết quả 23.738 lỗi giống hệt.
- Lượt đo này bị méo: lệnh `xml3176:do-kiem` giữ toàn bộ bộ đệm gom (24 nghìn lỗi) tới cuối → bộ nhớ đỉnh 102 MB > ngưỡng 64 MB của `Xml3176BoDem` → bộ đệm danh mục bị xoá liên tục (ICD10 tra 5.191 lần, danh mục mã lỗi nạp lại 1.256 lần). Worker thật không gặp.
- Phần còn lại lớn nhất: **kiểm tổng thể (`Xml3176CompleteChecker`) ~41 truy vấn/hồ sơ** (17 s/200 hồ sơ); truy vấn chéo hồ sơ "PTTT trùng bác sĩ" (`Xml3176Xml3Checker.php:~1404`) 5,6 s, chưa có chỉ mục phù hợp.
- **Chi phí hàng đợi**: mỗi hồ sơ sinh một chuỗi gồm 1 `CheckXml3176TypeJob` cho mỗi loại XML có checker (tối đa 12) + `CheckCompleteXml3176RecordJob` + (nếu bật xuất) `ExportXml3176Job` → `SignXml3176Job` → `SubmitXml3176Job`. Mỗi job trên driver `database` tốn lấy/khoá/xoá dòng `jobs`, giải nén payload, kiểm `chain_token`, nạp lại dữ liệu hồ sơ. Với lô lớn, hồ sơ được kiểm rải rác (khoảng cách lỗi đầu–cuối của một hồ sơ trung vị ~75 phút trong lô sáng 15/10).

Người dùng đã duyệt hướng B thu hẹp (không viết lại 12 checker).

## 2. Mục tiêu / không làm / phản mục tiêu

**Mục tiêu**
- **B1** — `xml3176:do-kiem` đo đúng như worker thật (ghi lỗi ra tệp và xả bộ đệm gom sau mỗi hồ sơ).
- **B2** — Kiểm tổng thể đọc mỗi bảng XML của hồ sơ **một lần** (từ ~41 truy vấn xuống ~12 hoặc ít hơn), tính tổng/lọc trong PHP; thêm chỉ mục cho "PTTT trùng bác sĩ".
- **B3** — **Một job kiểm cho mỗi hồ sơ** (`KiemHoSoXml3176Job`) thay cho chuỗi n job kiểm loại XML + job kiểm tổng thể; phần xuất → ký → gửi giữ nguyên.
- Kết quả kiểm **giống hệt** bản hiện tại (cùng tập `(xml, ma_lk, stt, error_code, description, critical_error)`).

**Không làm**
- Không viết lại logic kiểm của 12 checker theo loại XML.
- Không đổi job xuất/ký/gửi, `chain_token`, lệnh cứu `xml3176:chay-lai-tu-xuat`.
- Không đổi số worker/dịch vụ NSSM.

**Phản mục tiêu**
- Hồ sơ kiểm ra khác bản cũ.
- Job cũ đang nằm trong hàng đợi lúc deploy (`CheckXml3176TypeJob`, `CheckCompleteXml3176RecordJob`) không giải nén/chạy được, hoặc chạy chồng tạo lỗi trùng.
- Hồ sơ bị nạp lại giữa chừng mà chuỗi cũ vẫn ghi đè (mất cơ chế `chain_token`).
- Một job vượt `retry_after` (300 s) → chạy trùng.

## 3. Thiết kế

### 3.1 B1 — `xml3176:do-kiem` đo như worker
- Sau mỗi hồ sơ: lấy bộ đệm gom, lọc `is_check` như `ketThucGom` (đã có), **ghi nối** vào tệp `--xuat-loi`, xoá bộ đệm gom. Cuối lệnh sắp xếp lại tệp (đọc–sắp–ghi) để giữ định dạng ổn định dùng so sánh.
- Lệnh gọi **cùng đường kiểm** với job mới (3.3) thay vì tự lặp lại vòng kiểm, để đo đúng thứ chạy trên prod; vẫn chế độ gom không ghi CSDL, phiên `READ ONLY`.
- Báo thêm thời gian tường (wall) mỗi hồ sơ (trung bình, p90) và bộ nhớ đỉnh.

### 3.2 B2 — Kiểm tổng thể đọc dữ liệu hồ sơ một lần
- Một đối tượng dữ liệu hồ sơ (vd `App\Services\Xml3176\DuLieuHoSo`) nạp **một lần** các bảng XML cần cho `Xml3176CompleteChecker` và `NvytThuThap` theo `ma_lk` (xml1, 2, 3, 4, 5, 7, 8, 9, 10, 11, 13, 14 — chỉ cột cần, dạng mảng/stdClass, không hydrate Eloquent).
- Mọi truy vấn trong `checkErrors` (danh sách ở mục 6) được thay bằng phép tính trên dữ liệu đã nạp, **cùng ngữ nghĩa**:
  - `SUM` bỏ NULL; cộng bằng `bcadd` (bcmath có sẵn) với thang 6 rồi so sánh như cũ (`round(x, 2)`); `max()` bỏ NULL; `count/exists/first` như cũ.
  - Lọc chuỗi theo ngữ nghĩa collation `utf8_general_ci` (không phân biệt hoa thường, bỏ khoảng trắng cuối) — dùng lại `NhoTheoHoSo::khoaChuoiSql` hoặc tương đương; `<> ''`, `IS NOT NULL` y như SQL; thứ tự như `orderBy` cũ.
  - Thay `sum()` trên chính dòng XML1 (`:618-619`) bằng giá trị của `$data` (đã tra prod 15/10: không có `ma_lk` nào có nhiều hơn một dòng XML1 trên 67.729 hồ sơ, nên tương đương; nếu sau này có, vẫn lấy theo `$data`).
- `NvytThuThap::tuHoSo` nhận dữ liệu đã nạp (giữ chữ ký cũ cho lệnh `xml3176:kiem-lai-nvyt`).
- **Chỉ mục** `xml3176_xml3s (ma_bac_si(100), ngay_yl(14), ngay_th_yl(14), ngay_kq(14))` — migration best-effort như Bước A (`ALGORITHM=INPLACE, LOCK=NONE`, lỗi → thử thường → lỗi → `Log::warning`, không ném; kiểm tồn tại trước khi tạo). Builder xác nhận độ dài tiền tố đủ phân biệt với dữ liệu thật (đọc chỉ đọc) và chỉnh nếu cần.

### 3.3 B3 — Một job kiểm cho mỗi hồ sơ
- Job mới `App\Jobs\KiemHoSoXml3176Job($maLk, $loaiXml[], $chainToken)` trên hàng đợi `JobXml3176`, `$tries = 2`, `$timeout = 240` (< `retry_after` 300). Dùng trait `ThuocChuoiXml3176`.
- `handle()`:
  1. Như các job chuỗi hiện có: job cũ/không còn hiệu lực → `catChuoi()` và thoát (giữ nguyên quy tắc `laJobCu`/`conHieuLuc`, kể cả quy tắc F1 của 37a2234b cho job không token).
  2. `Xml3176PhienBanDanhMuc::dongBoBoDem()` một lần.
  3. Với **mỗi loại XML** trong danh sách (cùng thứ tự `Xml3176CheckTypes::LOAI`): làm **đúng** những gì `CheckXml3176TypeJob::handle` làm hiện nay — xoá lỗi của loại đó, `batDauGom`, nạp dòng (`Xml3176CheckTypes::truyVanDong`), chạy checker, `ketThucGom` trong `finally`.
  4. Kiểm tổng thể: trừ khi `organization.xml_3176_not_check`, xoá lỗi `xml = 'XMLComplete'` của hồ sơ rồi chạy `Xml3176CompleteChecker::checkErrors` **ở chế độ gom** (`batDauGom` … `ketThucGom`). (Sửa luôn lỗi tiềm ẩn: job tổng thể hiện không xoá lỗi XMLComplete cũ trước khi ghi, retry có thể nhân đôi.)
  5. Đóng dấu `checked_at` như `CheckCompleteXml3176RecordJob` hiện làm, **nhưng có điều kiện token** (`where('chain_token', $token)` — dùng `ghiNeuConHieuLuc`).
- **Kiểm token xuyên suốt** (quyết định của người viết spec, sau vòng đọc của builder): một job nay bao trùm 12 loại XML + kiểm tổng thể, nên kẽ hở giữa lần so token đầu job và lần ghi cuối dài hơn chuỗi cũ. Nếu hồ sơ bị nạp lại trong kẽ hở đó, job cũ có thể ghi lỗi/đóng dấu `checked_at` lên dữ liệu mới chưa kiểm → dữ liệu chưa kiểm có thể đi xuất/gửi (phản mục tiêu). Vì vậy: so token **trước mỗi lần xoá/ghi lỗi** của từng loại XML và của kiểm tổng thể (lệch → dừng, `catChuoi()`, không ghi tiếp), và đóng dấu `checked_at` có điều kiện token. Job không token (job cũ trước chuỗi) giữ quy tắc hiện hành. Service dùng chung với `do-kiem` nhận một "người canh" (callable) để job truyền phép so token, `do-kiem` truyền phép luôn-đúng.
- Phần lõi bước 3–5 nằm trong một service (vd `App\Services\Xml3176\KiemHoSoXml3176`) để `xml3176:do-kiem` (B1) và job dùng chung.
- `failed()`: ghi `export_error` có hiệu lực như hai job cũ, tên bước rõ (vd `Không xuất: bước kiểm hồ sơ lỗi — …`), chỉ khi còn hiệu lực (`ghiNeuConHieuLuc`).
- `Xml3176ChuoiXuLy::xepSauNap` dựng chuỗi: `KiemHoSoXml3176Job` → (nếu cho phép xuất) Export → Sign → Submit. Hồ sơ không có loại XML nào có checker vẫn có `KiemHoSoXml3176Job` (danh sách loại rỗng → chỉ kiểm tổng thể + đóng dấu), như chuỗi hiện bắt đầu từ job tổng thể.
- **Tương thích ngược**: giữ nguyên lớp `CheckXml3176TypeJob` và `CheckCompleteXml3176RecordJob` (chữ ký, thuộc tính) để job đã xếp trước deploy vẫn chạy đúng; không còn nơi nào xếp mới chúng.

### 3.4 Kiểm chứng
- **Tương đương**: như Bước A — chạy `xml3176:do-kiem` bản trước (main hiện tại, worktree riêng cố định) và bản mới trên cùng 200 hồ sơ (`mau200.txt` của Bước A ở scratchpad), so sánh tập lỗi đã chuẩn hoá (`critical_error` bool, `stt` int) → phải giống hệt. Lưu ý: bản mới xoá lỗi XMLComplete cũ trước khi ghi — `do-kiem` không ghi CSDL nên so sánh không bị ảnh hưởng.
- **Đo**: wall/hồ sơ, truy vấn/hồ sơ, ms SQL theo XML và cho kiểm tổng thể; bộ nhớ đỉnh.
- **Hàng đợi**: sau deploy, đo thông lượng thật (số hồ sơ kiểm xong/phút từ mốc `checked_at` hoặc lỗi XMLComplete) trên lô kế tiếp, so với lô trước.
- **Test đơn vị**: chuỗi mới (`Xml3176ChuoiXuLyTest` cập nhật thứ tự: `KiemHoSo` → Export → Sign → Submit); job mới (token cũ → cắt chuỗi; danh sách loại rỗng; xoá lỗi theo loại + XMLComplete; gom; `checked_at`; `failed()`); job cũ còn chạy được; kiểm tổng thể trên dữ liệu nạp sẵn cho cùng kết quả với truy vấn cũ (tổng có NULL, số thập phân, chuỗi hoa thường/khoảng trắng cuối, `<> ''`); migration chỉ mục best-effort.

## 4. Ràng buộc
- Laravel 5.5 / PHP 7.4; PHPUnit 6 + SQLite (`Tests\Support\Xml3176RuleTestSupport`); cấm `RefreshDatabase`/`DatabaseMigrations`; test với `DB_HOST=127.0.0.1` (`.env` dev trỏ CSDL thật); đo/so sánh chỉ đọc.
- Hàng đợi `database`, `retry_after` 300; NSSM chạy `queue:work` dài hạn, `update.bat` deploy mỗi giờ (`migrate --force`, cài lại dịch vụ).
- Không type-hint tiêm service trên `Job::handle()`.
- Test `Xml3176KhongConCongBoQuaTest` cấm chuỗi `xml_3176_not_check` trong `Xml3176Importer.php`.

## 5. Để builder quyết
Tên lớp/tệp; cấu trúc `DuLieuHoSo`; cách xả/sắp tệp của `do-kiem`; độ dài tiền tố chỉ mục (theo dữ liệu); chia task.

## 6. Phụ lục — truy vấn của kiểm tổng thể (`app/Services/Xml3176CompleteChecker.php`)
XML1 đọc 4 lần (`:83`, `:206`, `sum` `:618-619`); XML7 exists `:219`; tổng ngày giường XML3 3 lần (`:259`, `:294`, `:363`); 7 tổng XML2 (`:534`, `:558` trùng `:534`, `:573`, `:588`, `:603`, `:633`, `:659`); 7 tổng XML3 (`:546`, `:559`, `:574`, `:589`, `:604`, `:634`, `:660`); khám XML3 `:118` và `:906`; XML14 `:749`; XML9 `:811`; mức hưởng `:935` (×2 lần gọi × 2 bảng); XML3/XML4 `ngay_kq` `:955-957`; phẫu thuật `:1002`; max mức hưởng `:505-506`; vật phẩm kèm DVKT `:1042-1044`; NVYT 12 bảng (`NvytThuThap.php:154`). Số dòng là xấp xỉ — builder đối chiếu mã thật.
