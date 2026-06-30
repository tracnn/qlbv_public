# Thiết kế: Module Kiểm tra sai sót y lệnh tự động (Order Defect Check)

- **Ngày:** 2026-06-30
- **Dự án:** qlbv (Laravel 5.5, đọc HIS Oracle qua connection `HISPro`)
- **Trạng thái:** Đã duyệt thiết kế tổng thể, chờ viết plan triển khai

## 1. Mục tiêu

Tự động phát hiện sai sót trong y lệnh do bác sĩ ra trên hệ thống HIS (database `hispro_bvnn`), **không yêu cầu hãng HIS cung cấp API và không can thiệp vào schema HIS**. Hệ thống định kỳ quét dữ liệu y lệnh mới/đổi trong HIS, chạy bộ quy tắc bắt lỗi, lưu vi phạm và đưa ra cảnh báo (dashboard, thông báo, workflow xử lý, API cho tương lai).

### Hai họ luật

Module có **2 họ luật** với cách quản lý khác nhau:

- **Họ A — Luật lâm sàng (data-driven):** logic phức tạp + thay đổi thường xuyên, cấu hình trong DB.
- **Họ B — Luật hợp lệ cấu trúc/thời gian & hành nghề (hardcode):** logic cố định, deterministic, suy ra trực tiếp từ HIS; **chấp nhận hardcode**, có chỗ cập nhật riêng tách khỏi họ A.

### Phạm vi loại sai sót — Họ A (lâm sàng, data-driven)
1. **Tương tác / trùng thuốc**: tương tác thuốc–thuốc, kê trùng hoạt chất, trùng dịch vụ/chỉ định trong cùng đợt.
2. **Liều / đường dùng / tuổi**: liều bất thường, sai đường dùng, chống chỉ định theo tuổi/cân nặng, thuốc cho trẻ em/phụ nữ có thai.
3. **Logic chỉ định & chẩn đoán**: chỉ định không phù hợp ICD, lệch giới tính, thiếu chẩn đoán, chỉ định bị cấm theo phân tuyến.
4. **Quy tắc BHYT / thanh toán**: DV/thuốc không được BHYT chi trả, vượt định mức, sai điều kiện thanh toán, mã bệnh nhóm cảnh báo (kế thừa logic CheckBHYT hiện có).

### Phạm vi loại sai sót — Họ B (hợp lệ cấu trúc/thời gian & hành nghề, hardcode)
5. **Tính hợp lệ thời gian**: ngày ra viện < ngày vào viện; giờ y lệnh trước ngày vào hoặc sau ngày ra; giờ thực hiện trước giờ y lệnh; các mốc thời gian phi logic khác.
6. **Điều kiện hành nghề**: **người thực hiện** (`HIS_SERVICE_REQ.EXECUTE_LOGINNAME`, KHÔNG phải người chỉ định) không có/không hợp lệ chứng chỉ hành nghề (`HIS_EMPLOYEE.DIPLOMA` — số CCHN/GPHN). *Lưu ý: KHÔNG dùng `PRACTICE_SCOPE_DECISION` vì cột này trống 100% trong HIS.*

> Họ B dùng chung pipeline (engine → violation → dashboard/notify/workflow/API) với họ A, nhưng **logic nằm trong code** và được quản lý ở một thư mục/registry riêng (xem §6.1).

## 2. Quyết định kiến trúc (đã chốt với người dùng)

| Quyết định | Lựa chọn | Lý do |
|---|---|---|
| Cơ chế lấy dữ liệu | **Quét incremental theo watermark**, KHÔNG dùng trigger | DB kết nối là HIS production live; trigger trên schema hãng có rủi ro bảo hành/hiệu năng; hệ thống là hậu kiểm (không ghi ngược HIS) nên độ trễ vài phút chấp nhận được. Khớp kiến trúc app hiện có. |
| Nơi chạy engine | **Laravel console command trong qlbv** | Khớp pattern CheckBHYT/XmlErrorCheck; dễ test/version; không đụng HIS. |
| Lưu kết quả | **MySQL `qlbv`** | Cùng nơi với dữ liệu app hiện tại. |
| Quản lý luật | **Data-driven (cấu hình DB) + rule handler tái sử dụng** | Admin bật/tắt, đặt mức độ, ngưỡng, phạm vi qua DB; logic phức tạp nằm trong handler. |
| Đầu ra | Dashboard+Excel, Thông báo (email/SMS/Telegram), Workflow xử lý, API JSON read-only | Theo yêu cầu (chọn cả 4). |

### Khả năng mở rộng (đã trừ hao)
Engine đọc HIS qua một **source adapter** trừu tượng (`HisOrderSource`). Nếu sau này hãng HIS đồng ý hoặc cần độ trễ thấp hơn, có thể gắn cơ chế trigger→staging mà **không sửa rule engine**.

## 3. Nguồn dữ liệu HIS (đã xác minh)

Tất cả bảng dưới đều có `ID` (NUMBER), `CREATE_TIME`/`MODIFY_TIME` (NUMBER dạng `YYYYMMDDHH24MISS`), `IS_DELETE`, `IS_ACTIVE` → dùng làm watermark + xử lý sửa/hủy.

| Bảng HIS (owner `HIS_RS`) | Vai trò |
|---|---|
| `HIS_SERVICE_REQ` | Phiếu chỉ định = header y lệnh (`TREATMENT_ID`, `ICD_CODE`, `ICD_NAME`, `REQUEST_DEPARTMENT_ID`, `REQUEST_LOGINNAME`) |
| `HIS_SERE_SERV` | Chi tiết dịch vụ/CLS trong phiếu |
| `HIS_MEDICINE` | Chi tiết thuốc kê |
| `HIS_MEDICINE_INTERACTIVE` | Tham chiếu tương tác thuốc (`MEDICINE_TYPE_ID1/ID2`, `INTERACTIVE_GRADE_ID`, điều kiện ICD) |
| `HIS_TREATMENT`, `HIS_PATIENT` | Đợt điều trị / nhân khẩu BN (tuổi, giới tính); mốc thời gian `IN_TIME`, `OUT_TIME` cho luật họ B |
| `HIS_MEDICINE_TYPE` (+ nhóm/hoạt chất) | Danh mục thuốc, hoạt chất phục vụ phát hiện trùng |
| `HIS_EMPLOYEE` | Thông tin BS ra y lệnh; `DIPLOMA` (số chứng chỉ/giấy phép hành nghề) cho luật họ B |

**Cột thời gian phục vụ luật họ B** (đều NUMBER `YYYYMMDDHH24MISS`): `HIS_TREATMENT.IN_TIME`/`OUT_TIME`, `HIS_SERVICE_REQ.INTRUCTION_TIME`, `HIS_SERE_SERV.EXECUTE_TIME`/`TDL_INTRUCTION_TIME`.

> Lưu ý: quyền của `HIS_RS` chỉ dùng để **SELECT**. Module không tạo/sửa bất kỳ object nào trong HIS.

## 4. Luồng dữ liệu

```
HIS Oracle (HISPro, chỉ SELECT)
   HIS_SERVICE_REQ / HIS_SERE_SERV / HIS_MEDICINE (+ tham chiếu)
        │ (1) đọc bản ghi mới/đổi theo watermark
        ▼
qlbv (Laravel) — module Order Defect Check
   HisOrderSource ──► OrderContext (bệnh nhân, chẩn đoán, DV, thuốc)
        │
        ▼
   RuleEngine ──► [RuleHandlers] ◄── order_check_rules (cấu hình)
        │
        ▼
   order_check_violations (MySQL)
        ├──► Dashboard / Excel
        ├──► Notifier (email / SMS / Telegram)
        └──► API JSON read-only (cho HIS/màn hình khác sau này)

   Chạy bởi Laravel scheduler: php artisan order-check:scan (mỗi 1–5 phút)
```

Các bước mỗi lần quét:
1. Đọc `order_check_watermarks` để biết vị trí quét lần trước của từng bảng nguồn.
2. Lấy bản ghi có `CREATE_TIME`/`MODIFY_TIME` > watermark (kèm `ID` để chống bỏ sót cùng mốc thời gian), gom theo `TREATMENT_ID` thành `OrderContext`.
3. Nạp luật `is_active` khớp `scope`, dispatch tới rule handler, thu thập `Violation[]`.
4. Ghi `order_check_violations` với `dedup_key` (unique) để idempotent.
5. Đẩy ra các kênh đầu ra; cập nhật watermark + `order_check_rule_logs`.

## 5. Mô hình dữ liệu (MySQL trong qlbv)

| Bảng | Cột chính | Vai trò |
|---|---|---|
| `order_check_watermarks` | `source_key`, `last_create_time`, `last_modify_time`, `last_id`, `last_run_at` | Vị trí quét mỗi bảng nguồn |
| `order_check_rules` | `code`, `rule_type`, `name`, `severity`(info/warning/critical), `params`(JSON), `scope`(JSON: khoa/nhóm DV/đối tượng), `is_active` | Định nghĩa luật, cấu hình được |
| `order_check_violations` | `rule_id`, `treatment_id`, `patient_code`, `patient_name`, `doctor_loginname`, `department_id`, `order_ref_type`, `order_ref_id`, `severity`, `message`, `detail`(JSON), `dedup_key`(unique), `status`, `detected_at`, `processed_by`, `processed_at`, `note` | Bản ghi vi phạm + workflow |
| `order_check_rule_logs` | `started_at`, `finished_at`, `scanned_count`, `violation_count`, `status`, `error` | Nhật ký mỗi lần chạy (giám sát sức khỏe) |
| `order_check_ref_*` (tùy chọn) | tùy luật | Danh mục tham chiếu tự định nghĩa không có trong HIS (vd ngưỡng liều, quy tắc BHYT bổ sung) |

**Trạng thái workflow** (`order_check_violations.status`): `new` → `seen` → `processed` | `false_positive`.

## 6. Engine & Rule Handlers

- **`HisOrderSource`** (Service): đọc HIS theo watermark, trả `OrderContext` đã chuẩn hóa (không để logic HIS rò rỉ vào handler).
- **`RuleEngine`**: nạp luật active khớp scope → gọi handler → gom `Violation[]` → ghi DB chống trùng → ghi log.
- **`RuleHandler`** (interface chung cho cả 2 họ): nhận `OrderContext`, trả về `Violation[]` (hoặc rỗng). Engine không phân biệt họ A/B khi điều phối — chỉ khác cách định nghĩa.

### 6.1 Họ A — Handler lâm sàng (data-driven)

Mỗi *loại luật* là một class đọc `params`/`scope`/`severity` từ bản ghi `order_check_rules`. Danh sách (đánh số = ưu tiên giai đoạn 1):
1. `DrugInteractionRule` — dùng `HIS_MEDICINE_INTERACTIVE`.
2. `DuplicateDrugRule` — trùng hoạt chất/biệt dược cùng đợt.
3. `DuplicateServiceRule` — trùng DV/CLS cùng đợt/ngày.
4. `MissingDiagnosisRule` — phiếu thiếu chẩn đoán ICD.
5. `BhytPayabilityRule` — không được BHYT chi trả / mã bệnh nhóm cảnh báo (kế thừa CheckBHYT).
6. `GenderDiagnosisRule` — DV/CĐ lệch giới tính.
7. `AgeWeightContraindicationRule` — chống chỉ định theo tuổi/cân nặng.
8. `DoseRouteRule` — liều/đường dùng bất thường theo ngưỡng cấu hình.
9. `IcdServiceConsistencyRule` — DV không phù hợp ICD.

> "Data-driven": admin bật/tắt, đặt `severity`, ngưỡng (`params`), phạm vi (`scope`) cho từng luật trong DB qua UI. Thêm biến thể luật (cặp tương tác, ngưỡng liều...) = thêm bản ghi `order_check_rules`, không cần deploy.

### 6.2 Họ B — Handler hợp lệ cấu trúc/thời gian & hành nghề (hardcode)

- **Chỗ cập nhật riêng:** tất cả luật họ B đặt trong một thư mục/namespace riêng, ví dụ `app/Services/OrderCheck/RuleHandlers/Structural/`, kèm một **registry** (mảng khai báo) liệt kê các check đang bật. Thêm/sửa một luật họ B = thêm một class + một dòng trong registry (chấp nhận hardcode), **không lẫn** với cấu hình data-driven của họ A.
- Mỗi luật họ B vẫn có một bản ghi tối thiểu trong `order_check_rules` (`rule_type` = tên class, `severity`, `is_active`) để dùng chung dashboard/notify/workflow và cho phép bật/tắt — nhưng **toàn bộ logic nằm trong code**, không phụ thuộc `params`.

Danh sách check họ B (giai đoạn 1):
- `DischargeBeforeAdmissionRule` — `OUT_TIME` < `IN_TIME`.
- `OrderTimeOutOfStayRule` — `INTRUCTION_TIME` trước `IN_TIME` hoặc sau `OUT_TIME`.
- `ExecuteBeforeOrderRule` — `EXECUTE_TIME` (hoặc `TDL_INTRUCTION_TIME`) trước `INTRUCTION_TIME`.
- `DoctorPracticeCertRule` — BS ra y lệnh thiếu/không hợp lệ `PRACTICE_SCOPE_DECISION`.
- (mở rộng dễ dàng: các mốc thời gian phi logic khác chỉ cần thêm class + 1 dòng registry.)

## 7. Đầu ra

- **Dashboard + báo cáo**: route/controller/view + DataTables; lọc theo khoa, bác sĩ, loại luật, mức độ, khoảng ngày; export Excel — theo khuôn các báo cáo hiện có.
- **Thông báo chủ động**: `Notifier` đẩy email/SMS/Telegram theo `severity`/khoa, dùng hạ tầng `Mail`/`SendSms` sẵn có; có ngưỡng/gộp để tránh spam.
- **Workflow xử lý**: chuyển `status`, ghi người duyệt + ghi chú; phục vụ QLCL/bình bệnh án.
- **API JSON read-only**: endpoint trả vi phạm theo `treatment_id`/bệnh nhân để HIS hoặc màn hình khác hiển thị pop-up trong tương lai.

## 8. Lập lịch & vận hành

- `php artisan order-check:scan` chạy mỗi 1–5 phút qua Laravel scheduler (hoặc Windows Task vì app chạy bằng nssm service).
- Job làm mới cache dữ liệu tham chiếu (tương tác thuốc, danh mục thuốc/ICD) định kỳ.
- **Idempotent**: watermark + `dedup_key` đảm bảo chạy lại không nhân đôi cảnh báo.
- **Xử lý sửa/hủy lệnh**: dùng `MODIFY_TIME` + `IS_DELETE` để cập nhật/đóng violation khi y lệnh bị sửa hoặc hủy.
- Mỗi lần chạy ghi `order_check_rule_logs` để theo dõi: số bản ghi quét, số vi phạm, thời gian, lỗi.

## 9. Nguyên tắc thiết kế & ranh giới

- Module **chỉ đọc** HIS; mọi dữ liệu ghi đều nằm trong MySQL `qlbv`.
- Tách bạch: `HisOrderSource` (lấy dữ liệu) ⟂ `RuleEngine` (điều phối) ⟂ `RuleHandler` (logic từng luật) ⟂ `Notifier`/UI/API (đầu ra). Mỗi phần test độc lập.
- Kế thừa logic sẵn có (CheckBHYT, XmlErrorCheck) thay vì viết lại.

## 10. Ngoài phạm vi (YAGNI giai đoạn 1)

- Trigger/staging trong HIS (để mở ngỏ qua source adapter, chưa triển khai).
- Ghi ngược cảnh báo vào HIS / pop-up trực tiếp trong HIS (chờ hãng HIS).
- Học máy/khai phá bất thường tự động (chỉ làm luật tường minh trước).
