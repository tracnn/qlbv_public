# Thiết kế: Tách module XML3176 & Order-Check thành project NestJS/Vue độc lập

- **Ngày:** 2026-07-23
- **Trạng thái:** Đã duyệt thiết kế, chờ review spec
- **Nguồn gốc:** Hai module hiện có trong QLBV (Laravel/PHP): tiền giám định XML3176 và kiểm tra sai sót y lệnh (order-check). Xem `docs/tai-lieu-tong-hop-xml3176-order-check.md`.
- **Codebase tham chiếu:** `bm_patient_hub` (khuôn mẫu standalone fullstack), `bmc-backend-admin` + `bmc-frontend-admin` (quy ước NestJS/Vue).

---

## 1. Mục tiêu & bối cảnh

Tách hai module giám sát/tiền kiểm ra khỏi hệ QLBV Laravel, xây lại thành **một project standalone fullstack** bằng NestJS (backend) + Vue (frontend), theo đúng khuôn mẫu `bm_patient_hub`.

**Quyết định nền tảng (đã chốt):**
- **Mô hình:** repo NestJS/Vue **tách rời hoàn toàn** (không phải workspace trong monorepo `bmc-platform`), tự quản auth/DB/config. Học phong cách từ `bm_patient_hub`, không phụ thuộc `@bmc/common`/`@bmc/domain`.
- **CSDL:** Oracle — schema riêng ghi được (kết nối `DEFAULT`) chứa bảng `xml3176_*` và `order_check_*`; đọc HIS qua kết nối `HIS_RS` (read-only). Migration bằng file `.sql` thủ công + seeder `typeorm-extension` (đúng khuôn mẫu, `synchronize:false`).
- **Phạm vi XML3176:** làm **lõi trước** (import, kiểm tra lỗi, danh mục, dashboard). Ký số + gửi cổng BHXH + tra thẻ online + auto-import thư mục để **pha sau**.
- **Giao:** fullstack — backend NestJS + frontend Vue cho cả hai module.
- **Hướng kiến trúc:** **Port trung thực, hai module độc lập, chia pha** (Hướng A). Không gộp thành rule-engine hợp nhất.
- **Thứ tự:** **Order-check làm trước** (ít phụ thuộc ngoài, chỉ đọc HIS + cron, không cần parse XML), sau đó XML3176.

**Non-goals (pha này):**
- Ký số (USB token/HSM), gửi cổng BHXH, tra thẻ BHYT online, xuất XML đã ký.
- Auto-import XML3176 theo thư mục.
- Backfill/di trú dữ liệu lịch sử từ MySQL qlbv.
- Đa ngôn ngữ (i18n) — viết tiếng Việt trực tiếp.

---

## 2. Kiến trúc tổng thể

### 2.1. Cấu trúc repo

```
bm-giamdinh-hub/                 # tên đề xuất (có thể đổi)
  backend/                       # NestJS 11 + TypeORM + Oracle
  frontend/                      # Vue 3 + Vite + PrimeVue + Pinia
  docker/                        # redis (+ phụ trợ nếu cần), compose
  docs/
```

Hai project npm độc lập cạnh nhau (không root `package.json`), mỗi bên có `Dockerfile`/`docker-compose.yml`/`nginx.conf` riêng — đúng mô hình `bm_patient_hub`.

### 2.2. Stack backend

- NestJS 11, Node 24, TypeScript.
- TypeORM + `oracledb`; `synchronize:false`; migration `.sql` thủ công trong `src/migrations/`; seeder qua `typeorm-extension` (`npm run seed`).
- **CQRS** (`@nestjs/cqrs`) làm mặc định: controller mỏng → service dispatch → command/query handler chứa logic.
- **`@nestjs/schedule`** (cron) cho scanner order-check + email digest.
- **Bull + Redis** cho kiểm lỗi XML3176 bất đồng bộ.
- `@nestjs/config`, `class-validator`/`class-transformer`, `@nestjs/swagger`.
- Auth: `admin-auth` (JWT, secret `JWT_ADMIN_SECRET`) + `role-permission` (RBAC) bê từ khuôn mẫu.
- Test: Jest (unit colocated `*.spec.ts`).

### 2.3. Stack frontend

- Vue 3 + TS + Vite; PrimeVue 4 (Aura) + Bootstrap 5 (layout); Pinia; vue-router (hash).
- axios `apiClient` tập trung (`src/api/config.ts`) với interceptor gắn Bearer token + auto refresh.
- Cấu trúc 4 lớp mỗi domain: `models/<m>.model.ts → api/<m>.service.ts → stores/<m>.store.ts → views/backend/<m>/*.vue`.
- Bảng: PrimeVue DataTable lazy server-side. Biểu đồ: ApexCharts/Chart.js. Không i18n.

### 2.4. Kết nối CSDL (backend)

Đăng ký qua `TypeOrmModule.forRootAsync({ name })` với hằng `BASE_SCHEMA`:

| Tên kết nối | Vai trò | Ghi/Đọc |
|---|---|---|
| `DEFAULT` | Schema app (bảng `xml3176_*`, `order_check_*`) | Ghi được |
| `HIS_RS` | Đọc HIS (HIS_SERVICE_REQ, HIS_SERE_SERV…) | Chỉ đọc (raw SQL) |

Mọi kết nối `synchronize:false`, `autoLoadEntities:true`, pool `poolMin/poolMax`. Đọc HIS dùng `@InjectDataSource('HIS_RS')` + raw SQL (mẫu `bach-mai-award` / `his-rs-module`).

### 2.5. Nền tảng dùng chung (`backend/src/common`, `src/constants`, `src/configs`)

- `ApiException` + `errors.config.ts` (bảng error key → HTTP status).
- `buildPagination`, `query-builder.util`, `build-date-range`.
- `BaseEntity` (uuid id, createdAt/updatedAt, createdBy/updatedBy).
- `typeorm.config.ts`, `build-oracle-connection-string.config.ts`, `common.constant.ts` (`BASE_SCHEMA`).
- Global `ValidationPipe({ whitelist:true, transform:true })`; không dùng interceptor bao envelope (trả body thô + `{ data, pagination }`).

---

## 3. Module Order-Check (ưu tiên 1)

### 3.1. Nguyên tắc port

Giữ nguyên kiến trúc khái niệm của bản PHP: quét incremental theo watermark, chạy bộ quy tắc bật/tắt được, ghi vi phạm idempotent. Chỉ đổi cơ chế thực thi: PHP command loop → NestJS `@Cron`; PHP RuleHandler → NestJS injectable + Nest DI registry.

### 3.2. Luồng chạy

```
@Cron (mỗi 60s) → OrderCheckScanCommand → OrderCheckEngine.run()
  1. Nạp order_check_rules where is_active=true (cache theo code)
  2. Duyệt Scanner (ScannerRegistry)
       - đọc watermark → fetch bản ghi mới từ HIS (HIS_RS, raw SQL)
       - dựng OrderContext → chạy RuleHandler.check() → Violation[]
       - engine.persist() idempotent theo dedup_key
       - ghi order_check_rule_logs, cập nhật watermark
```

### 3.3. Thành phần

- **`OrderCheckEngine`** (service): điều phối, `persist()` idempotent (bỏ qua vi phạm đã `processed`/`false_positive`), resolve tên khoa/loại DV qua cache 1 lần/run.
- **`HisOrderSource`** (service): các hàm fetch HIS theo watermark + batched lookup (`WHERE id IN (...)`); dựng `OrderContext`.
- **Scanner** (interface + provider): MVP = `ServiceReqScanner` (đọc `HIS_SERVICE_REQ`). Về sau: `InteractionLogScanner`, `MedicineScanner`, `ServiceRestrictionScanner`.
- **`RuleHandler`** (interface `{ code(): string; check(ctx: OrderContext): Violation[] }`): đăng ký qua Nest DI (mảng provider inject bằng token), engine lọc theo rule đang active. MVP = nhóm cấu trúc `B_*`; bổ sung dần lâm sàng `A_*`.
- **DTO/Support:** `OrderContext`, `Violation` (dedupKey = `ruleCode:orderRefType:orderRefId:subKey`).

### 3.4. Bảng dữ liệu (schema DEFAULT)

| Bảng | Vai trò |
|---|---|
| `order_check_watermarks` | Mốc quét theo `source_key` (id/create_time/modify_time) |
| `order_check_rules` | Danh mục quy tắc: `code`, `family` (A/B), `rule_type`, `name`, `severity`, `params`(JSON), `is_active` |
| `order_check_violations` | Vi phạm: khóa nghiệp vụ + `dedup_key`(unique) + `status` (new/seen/processed/false_positive) + `detail`(JSON) |
| `order_check_rule_logs` | Nhật ký mỗi lần quét (scanned/violation count, status) |
| `order_check_ref_service_restriction` | Danh mục giới hạn DV theo giới tính/tuổi |

Seeder `typeorm-extension`: seed bộ 9 quy tắc khởi đầu (`B_DISCHARGE_BEFORE_ADMISSION`, `B_ORDER_TIME_OUT_OF_STAY`, `B_EXECUTE_BEFORE_ORDER`, `B_DOCTOR_NO_PRACTICE_CERT`, `A_DRUG_INTERACTION`, `A_MISSING_DIAGNOSIS`, `A_DOSE_MISMATCH`, `A_GENDER_MISMATCH`, `A_AGE_OUT_OF_RANGE`). Watermark khởi tạo = thời điểm hiện tại (không backfill).

### 3.5. API (backend, prefix `admin/order-check`, RBAC `order_check:*`)

- `GET /violations` — danh sách (filter ngày/khoa/mức độ/loại luật/trạng thái, phân trang lazy).
- `GET /summary` — KPI/thống kê.
- `GET /scan-stats` — trạng thái/nhật ký quét.
- `POST /violations/:id/status` — cập nhật trạng thái + ghi chú.
- `GET /violations/export` — xuất Excel.
- `GET/POST/PUT /rules` — quản lý quy tắc (bật/tắt `is_active`, sửa `severity`).
- `GET/POST/PUT/DELETE /ref-service-restriction` — danh mục giới hạn DV.
- Email digest: `@Cron` riêng, mặc định tắt (env `ORDER_CHECK_NOTIFY_ENABLED=false`).

### 3.6. Frontend (`views/backend/order-check/`)

- `OrderCheckManagement.vue` (container state phân trang/sort/filter) + `OrderCheckFilter.vue` + `OrderCheckTable.vue` (DataTable lazy + Tag trạng thái) + `OrderCheckDetailDialog.vue`.
- `OrderCheckRule.vue` (quản lý quy tắc), `OrderCheckRef.vue` (danh mục giới hạn DV).
- `stores/order-check.store.ts`, `api/order-check.service.ts`, `models/order-check.model.ts`.
- Route trong `router/index.ts` + mục menu `data/menu.ts` với `requiresPermission`.

### 3.7. Hiệu năng (bắt buộc — bảng HIS hàng triệu dòng)

Giữ nguyên 5 quy tắc: quét theo cột có index (mặc định `id`); không OR-keyset; tránh JOIN bảng lớn (batched lookup); resolve danh mục qua cache 1 lần/run; `check()` thuần in-memory, không query DB.

---

## 4. Module XML3176 (ưu tiên 2, chỉ lõi)

### 4.1. Luồng lõi

```
Upload .xml (multer) → parse gói QĐ3176 (base64 → XML1..15) → lưu entity Oracle
   → Bull queue theo loại XML → CheckerService từng loại → saveErrors()
        (lọc is_check, ghi xml3176_error_results, upsert xml3176_error_catalogs)
   → CompleteChecker (kiểm tra chéo XML1 ↔ XML2/XML3)
   → hiển thị danh sách / chi tiết / dashboard
```

### 4.2. Thành phần

- **Import service:** nhận file upload, giải mã base64 từng `FILEHOSO`, `switch(LOAIHOSO)` XML1..15 → lưu entity; khi gặp XML1 xóa XML2..15 cũ (giữ XML1/XML12). Khóa `ma_lk`.
- **Checker service từng loại XML:** port từ `Xml3176Xml{N}Checker`; sinh object lỗi 4 khóa (`error_code`, `error_name`, `critical_error`, `description`), `error_code` theo prefix loại XML.
- **`Xml3176ErrorService.saveErrors()`:** lọc catalog `is_check=false`; ghi `xml3176_error_results`; upsert `xml3176_error_catalogs`; `critical_error` lấy từ catalog (mặc định true nếu chưa có).
- **`CompleteChecker`:** kiểm tra chéo toàn hồ sơ (khớp tiền, số ngày giường, thiếu XML7…).
- **Queue:** Bull — dispatch job kiểm lỗi sau khi lưu từng loại XML.

### 4.3. Bảng dữ liệu (schema DEFAULT)

`xml3176_xml1 … xml3176_xml15` (khóa `ma_lk`), `xml3176_informations` (trạng thái vòng đời import/…; các cột export/sign/submit để trống cho pha sau), `xml3176_error_results`, `xml3176_error_catalogs` (`is_check`/`critical_error`, unique `xml+error_code`).

### 4.4. API (prefix `admin/xml3176`, RBAC `xml3176:*`)

- `GET /records` — danh sách hồ sơ (filter).
- `GET /records/:ma_lk/detail` — chi tiết + từng loại XML.
- `POST /upload` — upload & import.
- `GET/POST /error-catalog` — danh mục mã lỗi (bật/tắt `is_check`, đặt `critical_error`).
- `GET /dashboard/*` — overview/top-errors/aging/by-department.
- `GET /jobs/status` — số job kiểm lỗi đang chờ.

**Ngoài phạm vi pha này (để lại interface/stub):** ký số, gửi cổng BHXH, tra thẻ online, xuất XML đã ký, auto-import thư mục.

### 4.5. Frontend (`views/backend/xml3176/`)

`Xml3176Management.vue` + Filter + Table + các dialog chi tiết theo loại XML + `Xml3176ErrorCatalog.vue` + `Xml3176Dashboard.vue` (biểu đồ). Cùng khuôn 4 lớp + route + menu + permission.

---

## 5. Chia pha thực thi

| Pha | Nội dung | Kết quả kiểm chứng |
|---|---|---|
| **0. Scaffold** | Dựng repo backend+frontend từ khuôn `bm_patient_hub`; auth+RBAC; kết nối `DEFAULT`+`HIS_RS`; khung common/config; health + login; docker/compose | Đăng nhập được, `/health` xanh, gọi 1 API mẫu |
| **1. Order-check lõi** | Engine + watermark + `ServiceReqScanner` + luật `B_*`; bảng violations/logs/rules (+seeder); API `GET /violations` + `/summary`; frontend list tối thiểu | Cron quét, sinh vi phạm `B_*`, hiển thị trên bảng |
| **2. Order-check đầy đủ** | Scanner còn lại + luật `A_*`; quản lý quy tắc; danh mục giới hạn DV; dashboard; export Excel; email digest | Đủ 9 luật bật/tắt được, dashboard + export chạy |
| **3. XML3176 lõi (BE)** | Upload + parse + entity XML1..15; checker các loại chính + CompleteChecker; catalog; Bull queue; API list/detail/catalog/dashboard | Upload 1 hồ sơ → sinh lỗi → catalog + dashboard có số liệu |
| **4. XML3176 (FE)** | List + detail theo loại XML + catalog + dashboard biểu đồ | Thao tác trọn vẹn trên giao diện |
| **5. Tương lai (ngoài phạm vi)** | Ký số + gửi cổng BHXH + tra thẻ + auto-import + di trú dữ liệu lịch sử | (kế hoạch riêng sau) |

---

## 6. Kiểm thử & rủi ro

**Kiểm thử:**
- Unit (Jest) cho rule handler order-check và checker XML3176: hàm thuần trên context/dữ liệu dựng sẵn (không DB) — bám cách test bản gốc.
- Query/command handler quan trọng: test với repository mock.
- E2e: tùy chọn, khung sẵn.

**Rủi ro & giảm thiểu:**
- *Khác biệt SQL Oracle vs MySQL* (bản gốc order-check đã là Oracle HIS; XML3176 gốc là MySQL) → viết lại truy vấn theo Oracle; kiểm thử kỹ phần kiểm tra chéo tiền tệ (so khớp số thập phân).
- *Hiệu năng quét HIS* → tuân thủ 5 quy tắc hiệu năng ngay từ Pha 1.
- *Cron trùng lần chạy* (một lần quét chưa xong đã tới nhịp sau) → khóa chạy (in-memory flag / advisory) trong engine.
- *Phạm vi phình* → giữ đúng ranh giới lõi; ký số/gửi cổng để Pha 5.

---

## 7. Câu hỏi mở / cần xác nhận khi lập plan

- Tên repo chính thức (`bm-giamdinh-hub` hay khác).
- Có tái dùng module RBAC/`role-permission` đầy đủ hay chỉ single JWT admin realm.
- Danh sách loại XML "chính" cần checker ở Pha 3 (ưu tiên XML1/2/3 + CompleteChecker trước?).
- Cơ chế mailer cho email digest (SMTP/service ngoài) — chốt khi tới Pha 2.

---

*Thiết kế theo Hướng A (port trung thực, chia pha). Sau khi review spec sẽ chuyển sang writing-plans để lập kế hoạch chi tiết từng pha.*
