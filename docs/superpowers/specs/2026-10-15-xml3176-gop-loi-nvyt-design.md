# Gộp lỗi định danh nhân viên y tế (CCHN / MA_BS / MA_TTDV) — XML3176

Ngày: 15/10/2026. Trạng thái: chờ duyệt.

## 1. Vì sao

Kiểm định danh nhân viên y tế (NVYT) đang rải ra **34 mã lỗi**: mỗi XML × mỗi trường × hai tình huống (trống / không có trong danh mục). Tên mã theo 3 họ khác nhau (`INFO_ERROR_*_NOT_FOUND`, `INVALID_MEDICAL_STAFF_*`, `MISSING/INVALID_MEDICAL_STAFF_CODE`), mô tả mỗi nơi một kiểu, mức chặn lẫn lộn, và mô tả "chưa được duyệt trên cổng" sai sự thật (bảng `medical_staffs` không có trạng thái duyệt — chỉ là "không có dòng").

Báo theo **từng dòng**: từ 01/09/2026 có 388.819 dòng lỗi trên 26.293 hồ sơ, trong khi người cần xử lý chỉ vài trăm mã. Riêng XML4 BS đọc KQ: 187.197 dòng / 21.243 HS, chủ yếu tài khoản máy `vietrad`.

Hiệu năng: `CommonValidationService::isMedicalStaffValid()` chạy một truy vấn cho mỗi ô, không đệm — đo trên 23.444 HS nạp từ 01/10: 4,33 triệu lượt tra, ~185 truy vấn/HS, ~3,5 ms/truy vấn (từ máy dev).

Lỗi lập trình kèm theo: `app/Services/Xml3176Xml3Checker.php:339` tra cả chuỗi `ma_bac_si` thay vì từng mã khi có nhiều mã nối `;`.

## 2. Mục tiêu / không làm / phản mục tiêu

**Mục tiêu**
- Thay 34 mã bằng **3 mã ở mức hồ sơ** (`XMLComplete_*`), mỗi hồ sơ **một lỗi cho mỗi giá trị** (một người), mô tả nêu vai trò, XML, số dòng.
- Tách lỗi tích hợp (tài khoản máy, CCCD gửi nhầm, số thiếu đuôi) khỏi lỗi danh mục để giao đúng đơn vị (CNTT vs TCCB/BHYT).
- Tra danh mục NVYT trong bộ nhớ: 0 truy vấn/ô.
- Lệnh kiểm lại hàng loạt chuyển hồ sơ cũ sang mã mới.

**Không làm**
- Không lọc theo `tu_ngay/den_ngay` hay `ma_cskcb` của danh mục (giữ ngữ nghĩa hiện tại: có dòng là hợp lệ).
- Không kiểm TTDV thuộc danh sách người được ủy quyền ký.
- Không đổi luật `XML3_SAME_DOCTOR_ORDER_AND_EXECUTE` (BS chỉ định = người thực hiện) — giữ nguyên ở checker XML3.
- Không đụng bộ kiểm y lệnh (`OrderCheck/StaffCertNotInCatalogRule`) và các checker Qd130.
- Không thêm kiểm cho trường chưa từng kiểm (XML15, `ma_nguoi_*`…).

**Phản mục tiêu** (làm vậy là hỏng dù chạy được)
- Hồ sơ bị chặn gửi vì `KHONG_CO_TRONG_DM` hoặc `SAI_DINH_DANG` (chỉ `NVYT_THIEU` được chặn).
- Danh mục NVYT trống/không đọc được mà báo lỗi hàng loạt.
- Mất lịch sử lỗi cũ trước khi hồ sơ được kiểm lại.
- Một người lặp lại thành nhiều lỗi trong cùng một hồ sơ.

## 3. Phạm vi trường kiểm (vai trò)

| Vai trò | XML.trường | Bắt buộc khi |
|---|---|---|
| Thủ trưởng đơn vị | XML1/7/8/9/10/11/13/14 `ma_ttdv` | luôn (như hiện nay, khi dòng XML đó tồn tại) |
| Bác sĩ chỉ định | XML2 `ma_bac_si`, XML3 `ma_bac_si`, XML13 `ma_bac_si`, XML14 `ma_bac_si`, XML7 `ma_bs`, XML11 `ma_bs` | luôn (như hiện nay) |
| Người thực hiện | XML3 `nguoi_thuc_hien` | `ma_nhom` ∈ `config('xml3176.group_code_with_executor')` (như hiện nay) |
| Người thực hiện | XML5 `nguoi_thuc_hien` | luôn (như hiện nay) |
| BS đọc kết quả | XML4 `ma_bs_doc_kq` | luôn (như hiện nay) |

Điều kiện "bắt buộc" của từng trường **giữ đúng như checker hiện tại** (builder đọc từng block cũ trước khi gỡ). Giá trị có `;` (XML3 `ma_bac_si`, `nguoi_thuc_hien`) tách thành từng mã. Mọi giá trị `trim()` hai đầu trước khi xét.

**So khớp không phân biệt hoa thường** (quyết định của người viết spec): truy vấn hiện tại chạy trên collation `utf8_general_ci` nên không phân biệt hoa thường và bỏ khoảng trắng cuối; bảng tra trong bộ nhớ phải giữ đúng hành vi đó — chuẩn hoá khoá bằng `mb_strtoupper(trim(...), 'UTF-8')` khi dựng chỉ mục và khi tra. Mô tả lỗi in giá trị gốc (đã trim).

**Mức chặn hiện tại của mã "thiếu"** (đã tra prod 15/10/2026, chỉ đọc): mọi mã "thiếu" cũ có trong danh mục đều `critical_error = 1`, `is_check = 1` (XML1/7/8/11/13/14 TTDV, XML2 `MISSING_MEDICAL_STAFF_CODE`, XML3 `MA_BAC_SI` và `GROUP_CODE_NGUOI_THUC_HIEN`, XML4 `MA_BS_DOC_KQ`, XML5 `NGUOI_THUC_HIEN`, XML7 `MA_BS`, XML13/14 `MA_BAC_SI`); các mã chưa có dòng danh mục (XML9/10 TTDV, XML11 `MA_BS`) mặc định chặn. Vậy `NVYT_THIEU` chặn gửi **không chặn thêm** hồ sơ nào so với hiện nay. Mọi mã "không có trong danh mục" cũ đều `is_check = 1`.

## 4. Luật phân loại một giá trị

Danh mục NVYT = bảng `medical_staffs` (5.087 dòng; cột `macchn`, `ma_bhxh`, `so_dinh_danh`, `ho_ten`).

Cột tra theo vai trò (quyết định của người dùng 15/10/2026):

| Vai trò | Định danh hợp lệ | Hợp lệ khi khớp |
|---|---|---|
| Thủ trưởng đơn vị | CCHN/GPHN, CCCD hoặc mã BHXH | `macchn` hoặc `so_dinh_danh` hoặc `ma_bhxh` |
| Vai trò chuyên môn: BS chỉ định, người thực hiện, BS đọc KQ (kể cả XML7/XML11 `ma_bs`) | **chỉ CCHN/GPHN** — không có chứng chỉ hành nghề thì không được làm chuyên môn | `macchn` |

(Prod 01/10–15/10: các trường chuyên môn gần như toàn ghi dạng CCHN, kể cả XML11 `ma_bs`; ngoại lệ là tài khoản máy ở XML4.)

Dạng CCHN/GPHN: `^\d{4,7}/[A-ZĐÐ]{2,4}-(CCHN|GPHN)$` (không phân biệt hoa thường; Đ U+0110 và Ð U+00D0 đều chấp nhận).

Thứ tự xét một giá trị không rỗng:
1. Khớp cột tra của vai trò → **hợp lệ**.
2. **Vai trò chuyên môn** mà giá trị khớp `so_dinh_danh` hoặc `ma_bhxh` của một người → **`SAI_DINH_DANG`**, loại "số định danh" / "mã BHXH", nêu tên người và gợi ý mã CCHN của người đó; nếu người đó không có `macchn` trong danh mục thì ghi "người này chưa có CCHN trong danh mục NVYT".
3. Đúng dạng của vai trò nhưng không khớp → **`KHONG_CO_TRONG_DM`**:
   - TTDV: dạng CCHN/GPHN hoặc 10–12 chữ số; mô tả thêm "(đã tra theo CCHN, số định danh, mã BHXH)";
   - vai trò chuyên môn: chỉ dạng CCHN/GPHN.
4. Còn lại → **`SAI_DINH_DANG`**, kèm loại:
   - vai trò chuyên môn, 10–12 chữ số (không khớp ai ở bước 2) → "mã BHXH/số định danh, không phải mã CCHN — vai trò chuyên môn phải dùng CCHN";
   - có chữ cái (không theo dạng CCHN) → "tài khoản/tên đăng nhập, không phải mã CCHN" (vd `vietrad`, `pqa1`, `sys`);
   - toàn chữ số, < 10 chữ số → "số CCHN thiếu phần đuôi"; nếu đúng **một** người trong danh mục có phần số trước `/` của `macchn` bằng giá trị (so sánh sau khi bỏ số 0 đầu ở cả hai phía) → gợi ý mã đầy đủ;
   - khác → "định dạng không hợp lệ".

Giá trị rỗng ở trường bắt buộc → **`NVYT_THIEU`**.

**Chặn khi danh mục trống**: nếu `medical_staffs` không có dòng nào (hoặc không đọc được), bước kiểm **không báo** `KHONG_CO_TRONG_DM` và `SAI_DINH_DANG` (vẫn báo `NVYT_THIEU`, vì không phụ thuộc danh mục). Lỗi đọc danh mục được ghi log, không làm hỏng các kiểm khác của `XMLComplete`.

## 5. Ba mã lỗi mới

Đặt dưới `xml = 'XMLComplete'`, nạp bằng migration `firstOrCreate` (không ghi đè chỉnh tay), `is_check = true`.

| Mã | Tên | `critical_error` |
|---|---|---|
| `XMLComplete_NVYT_THIEU` | Thiếu mã nhân viên y tế | **true** (chặn gửi) |
| `XMLComplete_NVYT_KHONG_CO_TRONG_DM` | Mã nhân viên y tế không có trong danh mục NVYT | false |
| `XMLComplete_NVYT_SAI_DINH_DANG` | Mã nhân viên y tế sai định danh | false |

**Một lỗi = một hồ sơ × một giá trị** (với `THIEU`: một hồ sơ × một vai trò). Khoá gom là giá trị đã chuẩn hoá (mục 3). Hai giá trị khác nhau cùng trỏ về một người (vd CCCD ở XML3 và số thiếu đuôi ở XML2) vẫn là **hai lỗi** — mỗi giá trị là một chỗ sửa riêng trên HIS (quyết định của người viết spec). Phản mục tiêu "một người lặp nhiều lỗi" hiểu là: cùng một giá trị không sinh nhiều lỗi. Mô tả (mẫu — builder giữ cấu trúc, chỉnh câu chữ được):

- `KHONG_CO_TRONG_DM`: `027588/HNO-CCHN không có trong danh mục NVYT — người thực hiện XML3 (5 dòng), BS chỉ định XML2 (2 dòng), người thực hiện XML5 (1 dòng).`
  Với TTDV thêm "(đã tra theo CCHN, số định danh, mã BHXH)".
- `SAI_DINH_DANG`:
  - `vietrad là tài khoản/tên đăng nhập, không phải mã CCHN — BS đọc KQ XML4 (12 dòng: Siêu âm ổ bụng, X-quang ngực thẳng, …).`
  - `042068003011 là số định danh của NGUYỄN DUY NHẠN — người thực hiện phải dùng mã CCHN 0010344/BYT-CCHN (XML3, 4 dòng).`
  - `035470: số CCHN thiếu phần đuôi, có thể là 035470/BYT-CCHN — BS chỉ định XML3 (3 dòng).`
- `NVYT_THIEU`: `Thiếu người thực hiện ở 3 DVKT (XML3): Siêu âm ổ bụng, Siêu âm hạch vùng cổ, … ` / `Thiếu mã thủ trưởng đơn vị ở XML1, XML14.` / `Thiếu mã BS đọc kết quả ở 4 dòng XML4: …`

Danh sách tên trong mô tả cắt gọn (vài mục đầu + "và N … khác") để mô tả không quá dài; mô tả không vượt độ dài cột `description` của `xml3176_error_results`.

Thứ tự vai trò trong mô tả cố định (TTDV, BS chỉ định, người thực hiện, BS đọc KQ); XML theo số tăng dần.

## 6. Kiến trúc

- **Bảng tra trong bộ nhớ**: lớp mới (vd `App\Services\Xml3176\NvytLookup`) nạp toàn bộ `medical_staffs` (các cột cần) một lần, nhớ tĩnh TTL 600 giây, cùng kiểu `VatPhamKemDvktLookup` (có `xoaBoNho()`, có `tuDong(...)` dựng từ mảng cho test). Ba chỉ mục: `macchn`, `ma_bhxh`, `so_dinh_danh` (bỏ giá trị rỗng), cộng chỉ mục phần số CCHN cho gợi ý.
- **Luật thuần**: lớp mới (vd `NvytRule`) nhận bảng tra + các dòng XML của hồ sơ, trả mảng lỗi `['key','ten','mo_ta']` — không truy cập CSDL, test được không cần SQLite.
- **Nối vào `Xml3176CompleteChecker::checkErrors()`**: một `checkNvyt($ma_lk)` mới, đọc các trường cần của XML1/2/3/4/5/7/8/9/10/11/13/14 theo `ma_lk`, gọi luật, `critical_error` lấy qua `getCriticalErrorStatus($code)` như `checkVatPhamKemDvkt`.
- **Gỡ khỏi các checker XML1/2/3/4/5/7/8/9/10/11/13/14** toàn bộ các block sinh 34 mã cũ (cả cặp trống/không có trong danh mục). Các checker không còn gọi `isMedicalStaffValid()` cho mục đích này (giữ hàm nếu nơi khác còn dùng; nếu không còn ai dùng thì xoá). Lỗi ở `Xml3Checker.php:339` biến mất theo block bị gỡ. `XML3_SAME_DOCTOR_ORDER_AND_EXECUTE` giữ nguyên.
- **Mã cũ trong danh mục lỗi**: migration đặt `is_check = false` cho 34 mã cũ (theo danh sách tường minh, không dùng regex), ghi chú trong `description` hoặc tên là "Đã thay bằng XMLComplete_NVYT_* (15/10/2026)". Không xoá dòng `xml3176_error_results` cũ trong migration.
- **Gợi ý sửa**: `config/xml3176_suggestions.php` — bỏ khoá `XML3_INFO_ERROR_MA_BAC_SI_NOT_FOUND`, thêm gợi ý cho 3 mã mới (THIEU: bổ sung trên HIS; KHONG_CO_TRONG_DM: TCCB đăng ký NVYT + cập nhật DM; SAI_DINH_DANG: CNTT/hãng ánh xạ tài khoản → CCHN).

## 7. Lệnh kiểm lại hàng loạt

`php artisan xml3176:kiem-lai-nvyt {--tu=} {--den=} {--thu} {--lo=500}`

- Chọn hồ sơ theo khoảng ngày nạp (`xml3176_xml1s.created_at`, định dạng `Y-m-d`); không truyền thì toàn bộ.
- `--thu`: chỉ đếm (số HS, số lỗi mới theo mã, số dòng mã cũ sẽ xoá), không ghi.
- Mỗi hồ sơ, trong một giao dịch: xoá dòng `xml3176_error_results` của hồ sơ có `error_code` thuộc **34 mã cũ** hoặc **3 mã mới**, rồi chạy `NvytRule` và ghi lỗi mới (qua `Xml3176ErrorService`, tôn trọng `is_check`). Không đụng lỗi khác.
- Chạy theo lô, in tiến độ; chạy lại được (idempotent) và tiếp tục được khi bị dừng (vd `--tu-ma-lk=` hoặc dựa vào idempotent là đủ — builder chọn).
- Chạy ở dòng lệnh trên máy chủ (không đẩy vào hàng đợi). Trạng thái "có lỗi chặn gửi" tự đúng vì được tính trực tiếp từ `xml3176_error_results.critical_error`.
- **Không đụng luồng xuất/gửi** (quyết định của người viết spec): lệnh không xoá `export_error` cũ (vd "Không xuất: còn N lỗi xuất toán") và không đẩy chuỗi xuất → ký → gửi. Cuối lượt chạy, lệnh in **số hồ sơ hết lỗi chặn** và ghi danh sách `ma_lk` của chúng ra tệp trong `storage/logs` để người vận hành chạy `Xml3176ChayLaiTuXuat` nếu muốn (gửi cổng hiện đang dừng từ 29/09).

## 8. Ràng buộc

- Laravel 5.5 / PHP 7.4; test PHPUnit 6, SQLite bộ nhớ qua `Tests\Support\Xml3176RuleTestSupport`; **cấm** `RefreshDatabase`/`DatabaseMigrations`; chạy test với `DB_HOST=127.0.0.1` (`.env` dev trỏ CSDL thật).
- `update.bat` tự deploy mỗi giờ (`migrate --force`): migration phải an toàn chạy lại, không khoá bảng lớn lâu.
- Hàng đợi kiểm chạy các checker theo từng XML song song với `XMLComplete`; sau khi gỡ block cũ, hồ sơ nạp mới chỉ còn mã mới.
- Không type-hint tiêm service trên `Job::handle()`.

## 9. Kiểm thử và nghiệm thu

- Test luật thuần: từng nhánh mục 4 (khớp từng cột; TTDV chấp nhận `so_dinh_danh`; vai trò khác gặp `so_dinh_danh` → SAI_DINH_DANG có gợi ý; CCHN đúng dạng không có; 10/11/12 số; tài khoản; số thiếu đuôi có/không gợi ý duy nhất; `;` tách; trim; gom một lỗi cho một giá trị nhiều vai trò/XML; THIEU theo điều kiện bắt buộc từng trường; danh mục trống).
- Test checker cũ: không còn sinh 34 mã cũ; `XML3_SAME_DOCTOR_ORDER_AND_EXECUTE` vẫn chạy.
- Test migration: 3 mã mới đúng `critical_error`; 34 mã cũ `is_check = false`.
- Test lệnh: `--thu` không ghi; chạy thật xoá đúng mã cũ/mới của hồ sơ, giữ lỗi khác, chạy lại cho cùng kết quả.
- Nghiệm thu chỉ đọc trên CSDL thật: chạy `NvytRule` trên HS nạp từ 01/09, so số lỗi mới với số cũ (kỳ vọng ~26 nghìn lỗi thay ~389 nghìn dòng), liệt kê ví dụ mỗi loại, đếm HS hết bị chặn.

## 10. Để builder quyết

Tên lớp/tệp; cách đọc dữ liệu XML theo `ma_lk` (một truy vấn mỗi bảng); độ dài cắt danh sách trong mô tả; cơ chế tiếp tục của lệnh; có xoá `isMedicalStaffValid()` hay không (tuỳ còn nơi dùng).
