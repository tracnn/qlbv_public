# Tăng tốc quét kiểm XML3176 — Bước A (sửa nhanh, không đổi luật)

Ngày: 15/10/2026. Trạng thái: chờ duyệt. Bước B (kiểm theo cả hồ sơ) là spec riêng, làm sau khi A xong và đo lại.

## 1. Vì sao

Đo chỉ đọc trên CSDL thật (40 hồ sơ ngẫu nhiên nạp từ 01/10, chạy đủ 12 checker, đếm truy vấn qua `DB::listen`, đo từ máy dev):

| XML | Dòng | Truy vấn | ms SQL | Truy vấn/dòng |
|---|---|---|---|---|
| XML3 | 1.674 | 18.814 | 140.785 | 11,2 |
| XML4 | 3.418 | 11.894 | 101.203 | 3,5 |
| XML2 | 1.047 | 2.221 | 5.414 | 2,1 |
| XML5 | 375 | 753 | 3.147 | 2,0 |
| XML1 | 40 | 408 | 943 | 10,2 |

Điểm nóng (cùng lần đo):

| # | Truy vấn | Lần | ms | Nơi gọi |
|---|---|---|---|---|
| H1 | `service_catalogs` exists theo `cuaCoSo + ma_dich_vu` | XML4 3.418; XML3 997 | 71.300; 22.413 | `Xml3176Xml4Checker.php:136`, `Xml3176Xml3Checker.php:931,934` |
| H2 | `service_catalogs` get theo `cuaCoSo + ma_dich_vu + tu_ngay/den_ngay` | XML3 968 | 61.391 | `Xml3176Xml3Checker.php:951` |
| H3 | `xml3176_xml3s` trùng giường (`ma_khoa, ma_giuong, id !=, khoảng thời gian`) | XML3 197 | 17.992 | `Xml3176Xml3Checker.php:543` |
| H4 | `xml3176_xml3s` exists theo `ma_lk + ma_dich_vu + ma_nhom` | XML4 3.418 | 15.964 | `Xml3176Xml4Checker.php:149` |
| H5 | `xml3176_error_catalogs where error_code = ? limit 1` | XML3 8.375; XML4 1.640; … | 13.351; 3.311; … | `Xml3176ErrorService::getCriticalErrorStatus` (321 nơi gọi) |
| H6 | `xml3176_xml1s where ma_lk in (?)` (nạp lại quan hệ mỗi dòng) | XML4 3.418; XML3 1.674; XML2 1.047 | 10.627; 4.728; 2.408 | `->load()` vô điều kiện ở `Xml3176Xml2Checker.php:68`, `Xml3176Xml3Checker.php:182`; đọc thuộc tính lazy ở XML4/5/11 |
| H7 | danh mục nhỏ: `icd10_categories`, `dvkt_can_ma_may` (2 dạng), `equipment_catalogs`, `medical_organizations`, `administrative_units`, `job_categories` | hàng nghìn | ~12.000 | `CommonValidationService`, `Xml3176Xml3Checker.php:370,433` |
| H8 | `medicine_catalogs` (XML2), `medical_supply_catalogs` (XML3) | 1.010; 442 | 2.610; 1.688 | `Xml3176Xml2Checker.php:274…315`, `Xml3176Xml3Checker.php:788` |
| H9 | truy vấn theo `ma_lk` lặp mỗi dòng: chồng lấn DVKT (`Xml3:1053`), DVKT kèm VTYT (`Xml3:857`), diễn biến trùng XML5 (`Xml5:128`) | 293; 12; 375 | 3.470; 63; 1.860 | như trên |

Nguyên nhân H1/H2: điều kiện `(ma_cskcb is null or ma_cskcb = '' or ma_cskcb = ?)` làm MySQL chọn chỉ mục `service_catalogs_ma_cskcb_index` và quét ~20.000 dòng (EXPLAIN); bảng không có chỉ mục bắt đầu bằng `ma_dich_vu` dùng được (unique `(ma_dich_vu, ten_dich_vu, …)` không được chọn).

## 2. Mục tiêu / không làm / phản mục tiêu

**Mục tiêu**
- Giảm truy vấn và truy vấn chậm khi kiểm, mọi XML; mục tiêu đo được: XML3 ≤ ~2 truy vấn/dòng, XML4 ≤ ~0,5 truy vấn/dòng, tổng ms SQL trên cùng mẫu giảm ≥ 5 lần.
- **Kết quả kiểm giữ nguyên**: cùng hồ sơ, cùng dữ liệu → cùng tập lỗi (xml, ma_lk, stt, error_code, description, critical_error).

**Không làm**
- Không đổi luật kiểm, câu chữ mô tả, mã lỗi, mức chặn.
- Không đổi kiến trúc kiểm từng dòng (đó là Bước B).
- Không đổi số worker hàng đợi.

**Phản mục tiêu**
- Một hồ sơ cho kết quả khác bản cũ (thiếu/thừa lỗi, mô tả khác) → hỏng dù nhanh.
- Bộ nhớ worker phình không giới hạn (worker `queue:work` chạy dài, không `--max-jobs`).
- Danh mục/mức chặn sửa xong mà worker giữ bản cũ **quá 10 phút**.
- Migration khoá bảng `xml3176_xml3s` (1,5 triệu dòng) khi `update.bat` tự migrate mỗi giờ.

## 3. Quyết định đã chốt

- Người dùng chấp nhận: danh mục (DVKT, thuốc, VTYT, mã máy, ICD, cơ sở KCB, hành chính, nghề nghiệp, DVKT cần mã máy) và mức chặn/`is_check` của mã lỗi có thể chậm **tối đa 10 phút** mới có hiệu lực trong worker đang chạy.
- Thứ tự: làm A, đo lại, rồi mới làm B.

Quyết định của người viết spec (sau vòng đọc của builder):
- **Không để worker ghi đè mức chặn admin vừa sửa.** Hiện `Xml3176ErrorCatalog::createOrUpdate` (gọi từ `ketThucGom`/`saveErrors`) ghi lại `critical_error` của dòng danh mục bằng giá trị checker vừa đọc. Khi `getCriticalErrorStatus` dùng bộ đệm 600 giây, một worker giữ bản cũ sẽ ghi giá trị cũ đè lên thay đổi admin vừa làm trên màn quản trị — mất vĩnh viễn, không phải chỉ chậm 10 phút. Vì vậy: `createOrUpdate` **chỉ đặt `critical_error` khi tạo mới dòng**; với dòng đã có thì không sửa `critical_error` (vẫn cập nhật `error_name` như cũ). Thay đổi này không đổi mức chặn nào đang có; nó chỉ bảo vệ chỉnh tay. Có test riêng.
- **Con số mục tiêu (≤ ~2 truy vấn/dòng XML3, ≤ ~0,5 XML4, ≥ 5 lần ms SQL) là hướng đi, không phải điều kiện nghiệm thu.** Điều kiện nghiệm thu bắt buộc là tương đương kết quả (mục 5). Bộ đo chạy trong **một tiến trình mới** (bộ đệm nguội, như lần đo gốc) trên cùng 40 `ma_lk`; báo số liệu trước/sau theo XML và liệt kê các mẫu truy vấn còn lại nhiều nhất làm đầu vào cho Bước B.
- **Chỉ mục trên `xml3176_xml3s`**: prod chạy MariaDB 10.4.27 (bảng 501 MB dữ liệu, 591 MB chỉ mục). Migration thử `ALGORITHM=INPLACE, LOCK=NONE`; nếu máy chủ từ chối cú pháp/thuật toán thì tạo chỉ mục cách thường (vẫn trong cùng migration). `update.bat` chạy migrate khi trang đang ở chế độ bảo trì, nên việc tạo chỉ mục kéo dài thời gian bảo trì của lượt deploy đó vài phút — chấp nhận. Migration kiểm chỉ mục đã tồn tại trước khi tạo (chạy lại an toàn, không làm hỏng auto-update ở đơn vị khác).

## 4. Thiết kế

### 4.1 Chỉ mục (migration)
- `service_catalogs(ma_dich_vu, ma_cskcb)`. Sau khi thêm, builder kiểm bằng EXPLAIN (chỉ đọc trên prod) rằng truy vấn H1/H2 dùng chỉ mục mới; nếu MySQL vẫn chọn sai do `OR`, viết lại truy vấn trong scope/lookup cho dùng được chỉ mục (vd `where ma_dich_vu = ?` trước, lọc `ma_cskcb` sau) — kết quả phải giống hệt.
- Chỉ mục ghép cho H3 trên `xml3176_xml3s` phù hợp truy vấn trùng giường (vd `(ma_giuong, ma_khoa, ngay_th_yl)` — builder chọn theo EXPLAIN). Thêm bằng DDL trực tuyến không khoá ghi (`ALGORITHM=INPLACE, LOCK=NONE` với MySQL hỗ trợ); migration phải chạy lại được (kiểm tồn tại chỉ mục trước khi thêm) và an toàn dưới `migrate --force` hằng giờ.

### 4.2 Bộ nhớ đệm danh mục theo tiến trình (TTL 600 giây)
Một chỗ dùng chung (vd lớp `App\Services\Xml3176\DanhMucDem` hoặc mở rộng `CommonValidationService` bằng bộ nhớ tĩnh) theo đúng khuôn `VatPhamKemDvktLookup`/`NvytLookup`: bộ nhớ **tĩnh** (sống qua các job trong cùng worker), hết hạn sau 600 giây tính từ lần nạp/khởi tạo, có `xoaBoNho()` cho test, lỗi CSDL **không** được lưu thành kết quả rỗng.

- **Danh mục mã lỗi (H5)**: nạp cả `xml3176_error_catalogs` (248 dòng) một lần; `getCriticalErrorStatus($code)` trả `critical_error` từ bộ nhớ, mã chưa có → `true` (giữ hành vi cũ). Đường không gom của `saveErrors` (kiểm `is_check` từng lỗi) dùng cùng bộ nhớ. Khi `createOrUpdate` vừa tạo/sửa một mã trong tiến trình, cập nhật bộ nhớ cho mã đó để lần đọc kế tiếp nhất quán với CSDL.
- **Danh mục nhỏ (H7)**: tra theo khoá, nhớ kết quả boolean/giá trị theo khoá (ICD, mã máy, DVKT cần mã máy, cờ "có danh mục DVKT cần mã máy", cơ sở KCB và `tuyen_cmkt`, đơn vị hành chính, nghề nghiệp). Được phép nạp cả bảng nếu nhỏ, hoặc nhớ theo khoá — builder chọn theo kích thước bảng.
- **DVKT/thuốc/VTYT (H1, H2, H8)**: nhớ kết quả truy vấn theo đúng bộ khoá của truy vấn (vd `(ma_cskcb, ma_dich_vu)` cho exists; `(ma_cskcb, ma_dich_vu, ngay_vao)` cho tập dòng còn hiệu lực; `(ma_cskcb, ma_thuoc, ham_luong, so_dang_ky, tt_thau)` và các khoá dự phòng của XML2; `(ma_cskcb, ma_vat_tu)` cho VTYT). Phần so sánh với dữ liệu dòng (giá, tên, tỷ lệ) vẫn chạy trên kết quả nhớ, không đổi.
- **Giới hạn bộ nhớ**: mỗi bộ đệm theo khoá có trần số mục (vd 20.000); vượt trần thì xoá bộ đệm đó và bắt đầu lại. Hết 600 giây thì xoá toàn bộ.
- Ghi chú hiện có `CommonValidationService.php:93-97` ("KHONG luu dem" cho `danhMucBenhPl1` vì worker chạy dài) — được thay bằng TTL 600 giây theo quyết định ở mục 3; cập nhật ghi chú.

### 4.3 Dữ liệu theo hồ sơ, nạp một lần mỗi job
- **XML1 (H6)**: `CheckXml3176TypeJob` nạp các dòng kèm quan hệ XML1 (`with('Xml3176Xml1')` hoặc gán `setRelation` từ một lần đọc); thay `->load()` vô điều kiện ở `Xml3176Xml2Checker.php:68` và `Xml3176Xml3Checker.php:182` bằng `loadMissing`. Áp cho mọi model có quan hệ `Xml3176Xml1` (XML2/3/4/5/11).
- **XML4 (H4)**: tập `ma_dich_vu` của XML3 thuộc `ma_nhom` cần kết luận trong hồ sơ được nạp một lần theo `ma_lk` (nhớ theo `ma_lk` trong checker, đổi `ma_lk` thì nạp lại).
- **H9**: các truy vấn theo `ma_lk` lặp mỗi dòng (chồng lấn DVKT, DVKT kèm VTYT, diễn biến trùng XML5) nạp một lần theo `ma_lk` rồi lọc trong PHP, **cùng điều kiện** (vd `id >`, `stt <`, `thoi_diem_dbls` cùng ngày, thứ tự `stt desc`).
- Bộ nhớ theo hồ sơ không được rò sang hồ sơ khác: khoá theo `ma_lk`, chỉ giữ hồ sơ hiện tại.
- Truy vấn chéo hồ sơ (H3 trùng giường, `Xml3:1387` PTTT trùng bác sĩ) **giữ nguyên truy vấn**, chỉ tăng tốc bằng chỉ mục.

## 5. Kiểm chứng

- **Tương đương kết quả (bắt buộc)**: công cụ chạy đủ 12 checker trên một tập hồ sơ thật với `Xml3176ErrorService` ở chế độ gom (không ghi), phiên CSDL `READ ONLY`, xuất danh sách lỗi đã sắp `(xml, ma_lk, stt, error_code, description, critical_error)`. Chạy bản trước (main hiện tại) và bản mới trên **cùng** tập hồ sơ (≥ 200 hồ sơ, gồm nhiều nội trú và nhiều XML4) → hai danh sách phải giống hệt. Khác biệt nào cũng phải giải thích được bằng dữ liệu đổi giữa hai lần chạy, nếu không là lỗi.
- **Đo**: chạy lại bộ đo ở mục 1 trên cùng 40 hồ sơ (danh sách `ma_lk` cố định, lưu lại) trước/sau; báo truy vấn/dòng và ms SQL theo XML.
- **Test đơn vị**: bộ đệm (TTL, trần, `xoaBoNho`, không lưu lỗi CSDL thành rỗng, mã lỗi chưa có → true, `createOrUpdate` cập nhật bộ nhớ); nạp theo `ma_lk` cho kết quả giống truy vấn cũ (điều kiện biên `id >`, `stt <`, cùng ngày); migration chỉ mục chạy lại không lỗi. Test SQLite hiện có phải xanh; mọi test dùng bảng danh mục gọi `xoaBoNho()` trong `setUp` (bộ nhớ tĩnh sống qua các test).
- `tests/Unit/Xml3176/Xml3176DanhMucCoSoTest.php` đếm số chỗ gọi `cuaCoSo(` mỗi checker — cập nhật nếu chỗ gọi dời sang lớp đệm, giữ ý nghĩa (mọi tra danh mục vẫn lọc theo cơ sở).

## 6. Ràng buộc

- Laravel 5.5 / PHP 7.4; PHPUnit 6, SQLite bộ nhớ qua `Tests\Support\Xml3176RuleTestSupport`; cấm `RefreshDatabase`/`DatabaseMigrations`; test chạy với `DB_HOST=127.0.0.1` (`.env` dev trỏ CSDL thật); công cụ đo/so sánh chỉ đọc (`SET SESSION TRANSACTION READ ONLY`), để ngoài repo hoặc là lệnh artisan không ghi.
- Worker: `queue:work --queue=JobXml3176` qua NSSM, chạy dài; `update.bat` cài lại dịch vụ khi deploy (bộ nhớ tĩnh tự xoá).
- Không type-hint tiêm service trên `Job::handle()`.

## 7. Để builder quyết
Tên lớp, cấu trúc bộ đệm, trần số mục, cột chỉ mục H3 (theo EXPLAIN), cách viết lại truy vấn H1/H2 nếu chỉ mục chưa đủ, chia task.
