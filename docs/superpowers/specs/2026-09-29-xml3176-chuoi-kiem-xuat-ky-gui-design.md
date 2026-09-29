# Chuỗi kiểm → xuất → ký → gửi cho hồ sơ XML3176 — thiết kế

**Ngày:** 29/09/2026
**Phạm vi:** đường đi của một hồ sơ XML3176 từ lúc nạp xong tới lúc lên cổng BHXH
**Trạng thái:** đã duyệt thiết kế (4 phần), chờ duyệt spec

## 1. Vấn đề

### 1.1. Sự cố ngày 29/09/2026

Bản sửa ngày 28/09 (commit `7c7eb15a`) cho bước xuất **chờ bước kiểm theo thời gian**: chưa thấy dấu `checked_at` thì hoãn 15 giây, tối đa 10 lần (2,5 phút), hết lượt thì **không xuất** và ghi lý do.

Bản sửa được kiểm chứng trên 50 hồ sơ (bước kiểm xong trong 48 giây). Ngày 29/09 nạp lô 5.443 hồ sơ, hàng đợi kiểm tồn:

| Giờ nạp | Hồ sơ | Nạp → kiểm xong, trung bình | Tối đa | Hết lượt chờ |
|---|---:|---:|---:|---:|
| 09:00 | 4.120 | 2.751 s (46 phút) | 5.400 s (90 phút) | 3.771 |
| 14:00 | 1.323 | 883 s (15 phút) | 1.541 s | 1.272 |

**5.043 / 5.443 hồ sơ không được xuất**, trong đó **1.934 hồ sơ sạch lẽ ra phải lên cổng**. Không hồ sơ lỗi nào lọt — hướng "hỏng thì đóng" giữ được an toàn — nhưng giữ luôn cả hồ sơ sạch.

Bài học: **không thiết kế gì dựa trên "bước kiểm sẽ xong trong N giây"**. Độ trễ hàng đợi phụ thuộc lô nạp, không có trần.

### 1.2. Cấu trúc hiện tại

```
Nạp ──┬─► [JobXml3176]       Kiểm XML1 → Kiểm XML2 → … → Kiểm tổng thể (đặt checked_at)
      └─► [JobExportXml3176] Xuất: dựng XML → KÝ → ghi tệp → copy Trục DL / Điện Biên
                              → đẩy job Gửi                          (chờ checked_at 15 s × 10)
                                                     └─► [JobSubmitXml3176] Gửi cổng
```

Những điểm khảo sát được, mỗi điểm là một căn cứ thiết kế:

1. **Bước ký không phải bước riêng** — `Xml3176Service::processExportXml()` làm liền dựng XML → ký → ghi tệp → copy → đẩy job gửi.
2. **Thứ tự "kiểm tổng thể chạy sau cùng" dựa vào may mắn triển khai**: mỗi hàng đợi có đúng một dịch vụ NSSM, nên FIFO. Thêm worker thứ hai là vỡ.
3. **Các job kiểm từng loại chạy độc lập** — một job hỏng thì job kiểm tổng thể vẫn chạy.
4. **`queue:work` mặc định `--tries=0` (thử lại vô hạn)**, và `CheckXml3176TypeJob`, `CheckCompleteXml3176RecordJob`, `ExportXml3176Job` không khai `$tries`. Một hồ sơ độc chặn đứng cả hàng đợi (một worker). Việc từng có người viết lệnh `jobs:restart-stuck` (đặt lại `attempts >= 8`) cho thấy chuyện này đã xảy ra. Lệnh đó bị `update.bat` gọi sai tên (`job:restart-stuck`) nên chưa chạy lần nào.
5. **Người dùng nạp lại hồ sơ thường xuyên.** Nạp lại xoá sạch lỗi cũ (`deleteExistingXml3176()`) và đặt `checked_at = null`. Một job xuất của lần nạp trước còn nằm chờ, nếu chỉ hỏi "có lỗi nghiêm trọng không", sẽ thấy "không" và xuất dữ liệu mới chưa ai kiểm.
6. **Bộ nạp là nơi duy nhất** đẩy job kiểm và job xuất (`Xml3176Importer::nhapMotHoSo()`).
7. **CTĐT và TT12 đã làm đúng mẫu này**: ký và gửi là hai job, hai hàng đợi (`JobSignCtdt`/`JobSubmitCtdt`, `tt12-ky`/`tt12-gui`), nối bằng `withChain`, mỗi bước tự kiểm lại điều kiện lúc chạy. Lý do tách ký ghi trong `SignCtdtJob`: ký hỏng do lý do cục bộ (USB token, HSM), gửi hỏng do mạng — gộp lại thì mạng chập một lần là ký lại ba lần.
8. **Laravel 5.5.50**: job kế tiếp trong chuỗi chỉ được đẩy sau khi `handle()` chạy xong không ném (`CallQueuedHandler`, dòng 103); `Queueable::$chained` là thuộc tính công khai, đặt `[]` thì cắt phần còn lại. Không có `Str::uuid()`, không có `Queue::assertPushedWithChain()`.
9. `retry_after` của kết nối `database` là 300 s. Chú thích trong `config/queue.php` giải thích: `$timeout` lớn hơn thì hàng đợi giao lại job cho lượt thứ hai khi lượt đầu còn chạy — với job gửi là **hai lần POST thật lên cổng**.

## 2. Các quyết định đã chốt với người dùng

| # | Câu hỏi | Quyết định |
|---|---|---|
| Q1 | Cơ chế nối | Chuỗi sự kiện: bước kiểm gọi bước xuất, bước xuất gọi bước ký, bước ký gọi bước gửi. Bỏ hẳn chờ theo thời gian |
| Q2 | Cách nối chuỗi | **Phương án A**: một chuỗi `withChain` cho cả hồ sơ, dựng bởi một lớp điều phối |
| Q3 | `export_xml_not_check = true` có còn chờ kiểm xong không | **Vẫn chờ.** Một đường duy nhất; cờ chỉ đổi việc xuất có xét lỗi nghiêm trọng hay không. Chấp nhận hồ sơ lên cổng chậm theo hàng đợi kiểm khi nạp lô lớn |
| Q4 | Cứu 5.043 hồ sơ đang kẹt | Lệnh artisan đẩy lại chuỗi từ bước xuất, không kiểm lại |
| Q5 | Chuỗi cũ bị nạp lại giữa chừng | Mã phiên xử lý (`chain_token`), job lệch mã tự thôi, không ghi gì |
| Q6 | Khe hở job gửi cũ đang giữa lúc gọi cổng khi nạp lại | Chấp nhận (vài giây; khoá hồ sơ suốt lúc gửi sẽ làm nạp lại chậm) |

## 3. Kiến trúc

### 3.1. Chuỗi

```
[JobXml3176]        Kiểm XML1 → Kiểm XML2 → … → Kiểm tổng thể
[JobExportXml3176]  → Xuất
[JobSignXml3176]    → Ký            ← hàng đợi MỚI, dịch vụ NSSM mới
[JobSubmitXml3176]  → Gửi cổng
```

Lớp điều phối mới **`App\Services\Xml3176\Xml3176ChuoiXuLy`** — theo khuôn `CtdtXepHangKyGui`:

- `sinhMa()` — sinh mã phiên mới (mục 3.2).
- `xepSauNap($maLk, $maPhien, array $loaiDaNap, $choPhepXuat)` — gọi từ bộ nạp **sau commit**, thay cho ba lời gọi hiện có (vòng `CheckXml3176TypeJob::dispatch`, `checkXml3176Complete()`, `exportXml3176()`). Mã phiên do bộ nạp sinh và **đã ghi trong transaction nạp** (mục 3.2); hàm này chỉ đẩy chuỗi mang mã đó.
- `xepTuXuat($maLk)` — gọi từ lệnh cứu (mục 7): tự sinh mã mới, ghi vào hồ sơ, rồi đẩy chuỗi.

Chuỗi gồm:

- một `CheckXml3176TypeJob` cho mỗi loại trong `$loaiDaNap` có checker (`Xml3176CheckTypes::coChecker()`), theo thứ tự hiện nay;
- `CheckCompleteXml3176RecordJob` — **luôn có mặt**, kể cả khi `xml_3176_not_check` bật (xem 4.2);
- nếu `$choPhepXuat && config('xml3176.export_xml3176_enabled')`: `ExportXml3176Job` → `SignXml3176Job` → `SubmitXml3176Job`. Không thì chuỗi dừng ở kiểm tổng thể — quyết định đưa ra lúc nạp, như hiện nay.

Mỗi job khai hàng đợi của chính nó (`onQueue(...)`) vì các bước nằm trên bốn hàng đợi khác nhau.

### 3.2. Mã phiên xử lý

- Cột mới `xml3176_informations.chain_token` (`string(32)`, nullable).
- Sinh bằng `bin2hex(random_bytes(16))` (Laravel 5.5 không có `Str::uuid()`).
- Mỗi lần **dựng chuỗi mới** — do nạp hay do lệnh cứu — đều sinh mã mới.
- **Lần nạp phải ghi mã trong cùng transaction nạp**, không được ghi sau commit. Ghi sau commit thì có một khoảnh khắc dữ liệu mới đã hiện ra (lỗi cũ đã xoá) mà mã cũ vẫn còn hiệu lực — một job xuất của chuỗi cũ chạy đúng lúc đó sẽ xuất dữ liệu chưa kiểm, chính là cuộc đua cần chặn. Ghi qua nhánh `'import'` của `storeXml3176Information()` (tham số mới) hoặc một lệnh `update` ngay sau nó, trong transaction.
- Mọi job trong chuỗi nhận mã qua constructor.
- **Việc đầu tiên** của mọi job: so mã mang theo với mã trên hồ sơ. Khác nhau → hồ sơ đã có chuỗi mới hơn → **cắt chuỗi, thoát, không ghi gì**. Hồ sơ thuộc về chuỗi mới, chuỗi cũ không được ghi đè trạng thái.

Lợi phụ: khi nạp lại hàng loạt, chuỗi cũ thoát ngay từ job kiểm đầu tiên, nên hàng đợi kiểm không phải kiểm mỗi hồ sơ hai lần.

### 3.3. Sản phẩm của từng bước

| Bước | Làm gì | Để lại | Dừng chuỗi (có chủ đích) khi |
|---|---|---|---|
| Kiểm từng loại | như hiện nay (tự xoá lỗi của loại mình, idempotent) | dòng lỗi | — |
| Kiểm tổng thể | như hiện nay; `xml_3176_not_check` bật thì bỏ phần kiểm nhưng vẫn đóng dấu | `checked_at` | — |
| **Xuất** | dựng XML (`getDataForXmlExport()`), ghi **tệp chờ ký** | tệp chờ ký, `exported_at` | ngày ra ở tương lai (mục 4.4) · còn lỗi nghiêm trọng mà `export_xml_not_check` tắt — ghi `export_error` "Không xuất: còn {n} lỗi nghiêm trọng" (hiện nay việc chặn này **im lặng**, người vận hành không phân biệt được "bị chặn" với "chưa tới lượt") |
| **Ký** | đọc tệp chờ ký → ký → ghi tệp **đúng chỗ và đúng tên như hiện nay** → copy Trục DL / Điện Biên → xoá tệp chờ ký | tệp, `is_signed`, `sign_method`, `signed_error`, **`signed_file_path`** | `QuyetDinhGui::nen()` trả `KHONG_GUI` (im lặng) hoặc `CHUA_KY` (ghi `submit_error` "Hồ sơ chưa ký số, không gửi lên cổng BHXH" như hiện nay) |
| **Gửi** | như hiện nay, nhưng đọc đường dẫn tệp từ `signed_file_path` | `submitted_at` hoặc `submit_error` | — |

**Tệp chờ ký** nằm trên disk `local` (`storage/app`, có sẵn ở mọi cơ sở), đường dẫn `xml3176-cho-ky/{ma_lk}.xml` — tên cố định theo hồ sơ nên ghi lại là đè, chạy lại an toàn. **Không** đặt trong thư mục của disk `exportXml3176` hay các disk mà dịch vụ quét Trục DL / Điện Biên đọc (`allFiles()`), để tệp chưa ký không bị nhặt nhầm. `config/filesystems.php` bị gitignore — mỗi cơ sở một bản — nên không thêm disk mới.

**Giữ nguyên có chủ ý** (lần này chỉ đổi cách nối, không đổi hành vi):

- Ký không được thì **vẫn ghi tệp chưa ký ra thư mục và vẫn copy** sang Trục DL / Điện Biên, chỉ không gửi cổng BHXH. CTĐT chọn khác; đổi là việc riêng.
- Tên tệp `{Y.m.d_H.i.s}_{ma_lk}.xml`, thư mục chia theo ngày / mã cơ sở (`export_to_directory_by_day`) — có thể có phần mềm khác đang đọc.
- `SubmitXml3176Job` tự dựng `BHYTXmlSubmitService` bằng mã cơ sở của hồ sơ — **không** nhận qua type-hint của `handle()` (bẫy tiêm container đã cắn ba lần).
- **Tên thuộc tính của các job giữ nguyên** (`maLk`/`xmlType`, `ma_lk`, `xmlFilePath`, `macskcb`) và chỉ **thêm** thuộc tính mã phiên — để job cũ đã serialize trong hàng đợi vẫn giải nén đúng (mục 5). Job gửi trong chuỗi mới để `xmlFilePath` rỗng và đọc `signed_file_path`; job gửi cũ mang sẵn `xmlFilePath`.

**Bỏ đi:** vòng chờ trong `ExportXml3176Job` (`SO_LAN_CHO_TOI_DA`, `GIAY_CHO_MOI_LAN`, `phaiChoKiemLoi()`); phần ký / ghi tệp / copy / đẩy job gửi trong `processExportXml()` chuyển sang `SignXml3176Job`; `Xml3176Service::exportXml3176()` và `checkXml3176Complete()` không còn ai gọi thì xoá.

## 4. Hành vi chi tiết

### 4.1. Hai loại "không đi tiếp"

| | Ví dụ | Xử lý |
|---|---|---|
| **Dừng có chủ đích** | lỗi nghiêm trọng · tắt gửi · chưa ký · ngày ra tương lai · lệch mã phiên | **Không ném.** Ghi lý do vào hồ sơ (trừ lệch mã phiên), đặt `$this->chained = []`, thoát bình thường. Không tốn lượt thử, không vào `failed_jobs` |
| **Hỏng thật** | mất CSDL · HSM không phản hồi · lỗi mã | Ném → Laravel thử lại theo `$tries` → hết lượt gọi `failed()`, Laravel tự bỏ phần sau của chuỗi |

### 4.2. `xml_3176_not_check`

Cờ này chỉ tắt `Xml3176CompleteChecker`. `CheckCompleteXml3176RecordJob` vẫn có mặt trong chuỗi và vẫn đóng `checked_at` — nó là mốc "bước kiểm đã xong" của chuỗi. Không có job này thì không có gì đứng giữa bước kiểm và bước xuất.

### 4.3. `export_xml_not_check`

Bước xuất vẫn chỉ chạy sau khi chuỗi kiểm xong (Q3). Cờ bật thì bước xuất **không xét** lỗi nghiêm trọng.

### 4.4. Ngày ra ở tương lai

Hiện nay `ExportXml3176Job` thoát **im lặng** khi `ngay_ra` sau thời điểm hiện tại — hồ sơ không bao giờ được xuất và không ai biết. Nay ghi `export_error` "Không xuất: ngày ra (…) sau thời điểm xuất" rồi cắt chuỗi. Khi ngày ra đã qua, lệnh cứu (mục 7) đẩy lại được vì hồ sơ vẫn thoả tiêu chí "đã kiểm, chưa xuất".

### 4.5. Thử lại và thời hạn

| Job | `$tries` | `$timeout` |
|---|---:|---:|
| `CheckXml3176TypeJob` | 2 | 240 |
| `CheckCompleteXml3176RecordJob` | 2 | 240 |
| `ExportXml3176Job` | 2 | 120 |
| `SignXml3176Job` | 2 | 120 |
| `SubmitXml3176Job` | 3 | 60 (giữ nguyên) |

**Ràng buộc cứng: mọi `$timeout` < `retry_after` (300).** Có test chốt.

### 4.6. `failed()` ghi lý do vào hồ sơ

Mỗi job trong chuỗi có `failed()`. **Chỉ ghi khi mã phiên còn khớp.**

| Job hỏng | Cột | Nội dung |
|---|---|---|
| Kiểm từng loại | `export_error` | "Không xuất: bước kiểm {XMLn} lỗi — {thông điệp}" |
| Kiểm tổng thể | `export_error` | "Không xuất: bước kiểm tổng thể lỗi — {thông điệp}" |
| Xuất | `export_error` | "Xuất lỗi — {thông điệp}" |
| Ký | `signed_error` | "Ký lỗi — {thông điệp}" |
| Gửi | `submit_error` | "Gửi lỗi — {thông điệp}" (hiện `failed()` chỉ ghi log) |

Bước kiểm hỏng ghi vào cột **xuất** vì hậu quả người vận hành thấy là hồ sơ không được xuất.

### 4.7. Nạp lại xoá trạng thái chuỗi

Nhánh `'import'` của `storeXml3176Information()` đã đặt `exported_at = null`, `checked_at = null`. Thêm `signed_file_path = null`. `submitted_at` **giữ nguyên** — là dấu vết hồ sơ đã từng lên cổng, cần cho đối soát (đã chốt 28/09).

## 5. Job cũ trong hàng đợi lúc triển khai

Job không mang mã phiên (`chain_token` null trên đối tượng job) = job serialize bởi mã cũ. Quy tắc: **không thuộc chuỗi nào — chỉ làm phần việc của mình, không đẩy bước sau.**

| Job cũ | Xử lý |
|---|---|
| Kiểm từng loại / tổng thể | kiểm như thường, đóng `checked_at`, không có gì phía sau |
| Xuất (dạng cũ, có `soLanCho`) | thoát, không làm gì |
| Gửi (dạng cũ, mang `xmlFilePath`) | **gửi như cũ** — tệp đã qua đủ cửa theo mã cũ |

Hồ sơ bị bỏ dở do đó được lệnh cứu gom lại.

## 6. Triển khai

- **Push lên `main` là triển khai prod**: máy chủ tự chạy `update.bat` mỗi giờ. Chỉ merge khi người dùng yêu cầu.
- `update.bat` chạy theo thứ tự: bảo trì → `git pull` → `migrate` → cài dịch vụ còn thiếu → **dừng mọi dịch vụ** → dọn / tạo cache cấu hình → bật lại → mở lại ứng dụng. Migration chạy trước khi worker khởi động lại — đúng thứ tự cần.
- **Dịch vụ mới `QLBV JobSignXml3176`** (`artisan queue:work --queue=JobSignXml3176`) thêm vào `update.bat`, `install_service.bat`, `remove_service.bat` theo khuôn `JobSignTt12`: khối cài có `nssm status` kiểm trước (idempotent), dòng `stop` và `start`.
- **Bẫy `update.bat`**: cmd đọc tiếp tệp mới theo vị trí byte sau `git pull`. **Không đổi một byte nào từ dòng 1 tới hết dòng `git pull origin main`.** Có test chốt so byte phần đầu tệp.
- `config/xml3176.php` (git theo dõi) thêm `'sign_queue_name' => 'JobSignXml3176'`.
- **Dịch vụ ký không chạy thì mọi chuỗi dừng ở bước Ký** (job nằm chờ, không hỏng). Sau triển khai phải kiểm trạng thái dịch vụ.
- **Quay lui**: revert commit → `update.bat` tự chạy mã cũ. Dịch vụ ký và hai cột mới còn lại, vô hại.

## 7. Lệnh cứu `xml3176:chay-lai-tu-xuat`

```
php artisan xml3176:chay-lai-tu-xuat                 # chỉ đếm
php artisan xml3176:chay-lai-tu-xuat --thuc-hien     # đẩy chuỗi thật
php artisan xml3176:chay-lai-tu-xuat --ma-lk=A --ma-lk=B [--thuc-hien]
```

- **Mặc định chỉ đếm và in**: tổng số, chia sạch / có lỗi nghiêm trọng. Phải có `--thuc-hien` mới đẩy.
- **Tiêu chí chọn mặc định**: `checked_at IS NOT NULL AND exported_at IS NULL`.
  - Tự loại hồ sơ nạp trước 28/09 (cột `checked_at` sinh ngày 28/09, hồ sơ cũ để null) và hồ sơ đang kiểm dở (nạp lại đặt `checked_at = null`).
  - Ngày 29/09 tiêu chí này bắt 5.043 hồ sơ; 3.109 hồ sơ có lỗi nghiêm trọng sẽ lại bị bước xuất chặn — đúng.
- `--ma-lk=` chỉ định từng hồ sơ, bỏ qua tiêu chí mặc định — dùng khi ký lại sau sự cố HSM.
- **Không chọn đại trà "đã xuất mà chưa ký"**: ở cơ sở không bật ký số, mọi hồ sơ đều thuộc nhóm đó; chạy lại hàng loạt sẽ **copy trùng sang Trục DL / Điện Biên**.
- Mỗi hồ sơ được đẩy nhận **mã phiên mới** (`Xml3176ChuoiXuLy::xepTuXuat()`) — chuỗi cũ còn sống của hồ sơ đó tự thôi. Thiếu điều này, hai chuỗi cùng mã có thể gửi trùng lên cổng.

**Trình tự trên prod sau triển khai:** chờ lượt tự cập nhật → kiểm `QLBV JobSignXml3176` đang chạy → `xml3176:chay-lai-tu-xuat` (đọc số) → `--thuc-hien` → theo dõi số hồ sơ mang lỗi "Không xuất: chờ…" giảm về 0.

## 8. Kiểm thử

Mọi test tuân quy ước repo: **cấm `RefreshDatabase`**; SQLite in-memory qua `Xml3176RuleTestSupport::bootXml3176Sqlite()`; `setUp()` không `: void` (PHPUnit 6.5).

1. **Thứ tự chuỗi** (`Xml3176ChuoiXuLy`): `Queue::fake()`, lấy job đầu, `unserialize` từng phần tử `$job->chained`, khẳng định lớp, hàng đợi, và mã phiên của từng job. Các biến thể: có / không cho xuất; `xml_3176_not_check` bật vẫn có job kiểm tổng thể; chỉ các loại có checker.
2. **Mã phiên**: mỗi job, mã lệch → không ghi gì và `chained` bị cắt; mã khớp → làm việc bình thường.
3. **Dừng có chủ đích** của Xuất (lỗi nghiêm trọng, ngày ra tương lai, `export_xml_not_check`) và Ký (`KHONG_GUI`, `CHUA_KY`): ghi đúng cột, `chained` rỗng, không ném.
4. **`failed()`**: ghi đúng cột khi mã khớp; không ghi khi mã lệch.
5. **Chốt `$timeout` < `retry_after`**: đọc `$timeout` của 5 job, so với `config('queue.connections.database.retry_after')`.
6. **Chốt `$tries` khác 0** cho cả 5 job (chống thử lại vô hạn).
7. **Job cũ** (mã phiên null): kiểm vẫn chạy và không đẩy gì; xuất cũ thoát; gửi cũ có `xmlFilePath` vẫn gửi.
8. **`SubmitXml3176Job::handle()` không có tham số type-hint dịch vụ gửi** (reflection) — giữ khuôn phòng bẫy tiêm container.
9. **Lệnh cứu**: mặc định không đẩy gì; `--thuc-hien` đẩy đúng tập theo tiêu chí; `--ma-lk` bỏ tiêu chí; mỗi hồ sơ nhận mã phiên mới.
10. **`update.bat`**: phần đầu tới hết dòng `git pull origin main` giữ nguyên byte so với bản trước sửa; có khối cài `QLBV JobSignXml3176`, có dòng stop và start.
11. **Nạp** (`Xml3176Importer`): chỉ gọi `Xml3176ChuoiXuLy::xepSauNap()`, không còn `CheckXml3176TypeJob::dispatch`, `checkXml3176Complete`, `exportXml3176`; mã phiên ghi trong transaction nạp.
12. Test cũ của cơ chế chờ (`XuatChoKiemXongTest`) được thay bằng các test trên — chỉ giữ phần còn đúng (`checked_at` đóng bởi kiểm tổng thể; nạp lại xoá `checked_at`).

**Xác minh toàn bộ**: chạy full suite hai lượt (`git stash -u` gốc vs có sửa) với `DB_HOST=127.0.0.1`, so **tên** test đỏ. Nền hiện tại: 102 test đỏ do môi trường.

**Xác minh trên prod** — bài học 29/09: không dừng ở lô vài chục hồ sơ. Sau khi cứu 5.043 hồ sơ, đo đủ cả lô: hồ sơ sạch lên cổng, hồ sơ lỗi bị chặn, không hồ sơ lỗi nào lọt, độ trễ từng bước.

## 9. Ngoài phạm vi

- **Nút xuất tay** (`bhyt.xml3176.export-xml`) dựng XML chưa ký rồi nén zip cho tải về; nó gọi `getDataForXmlExport()` nên **đánh dấu `exported_at`** dù không có gì lên cổng. Sau thay đổi này, hồ sơ đã tải tay sẽ **không** được lệnh cứu chọn (vì `exported_at` có giá trị). Ghi nhận, sửa riêng.
- Dịch vụ worker bị dừng: job chỉ nằm chờ, không `failed()`. Cần theo dõi ở tầng dịch vụ Windows.
- `update.bat` gọi sai tên `job:restart-stuck`. Với `$tries` rõ ràng, job XML3176 không còn chạm mốc 8 lần.
- Ký không được thì không ghi tệp (hướng của CTĐT).

## 10. Việc đi kèm

- `readme.md`: mục mới, gồm trình tự trên prod ở mục 7.
- Tài liệu quy trình vận hành (`docs/quy-trinh-van-hanh/Quy-trinh-van-hanh-XML3176.docx`, nguồn `_nguon/build.js`): phụ lục dịch vụ / công tắc cho CNTT thêm hàng đợi ký và lệnh cứu.
