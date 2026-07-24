# Thiết kế: bm-data-quality — Nền tảng phát hiện vấn đề chất lượng dữ liệu HIS

- **Ngày:** 2026-07-24
- **Trạng thái:** Đã duyệt thiết kế, chờ review spec
- **Thay thế cho:** `2026-07-23-tach-xml3176-order-check-nestjs-design.md` (bm-giamdinh-hub) — tái thiết kế theo hướng nền tảng hóa, đổi tên thành **bm-data-quality**.
- **Codebase tham chiếu:** `bm_cdss` (DQ engine — rule-as-data), `bm_patient_hub` (Oracle multi-conn + CQRS + cron), `bmc-backend-admin`/`bmc-frontend-admin` (quy ước), order-check gốc (Laravel QLBV).
- **Schema HIS:** đã xác nhận trực tiếp qua sqlcl kết nối `hispro_stb` (Oracle 12c).

---

## 1. Mục tiêu & định vị

Xây **một nền tảng standalone** phát hiện vấn đề chất lượng dữ liệu trên HIS. Lõi là **DQ Engine module-agnostic**; mỗi lĩnh vực kiểm tra là một **module cắm vào**. **Order-check** (kiểm tra sai sót y lệnh) là module đầu tiên; **XML3176** và các kiểm tra khác là module sau, dùng lại cùng engine mà không sửa bảng lõi.

**Khác biệt cốt lõi so với thiết kế bm-giamdinh-hub trước:** không coi 2 module là 2 khối riêng, mà trừu tượng hóa thành *engine lõi + module*. Điều này đến từ việc tham khảo `bm_cdss` (vốn là một DQ engine thực thụ).

**Kết hợp các project đã khảo sát:**

| Thành phần | Nguồn |
|---|---|
| Rule-as-data, computed-facts, json-rules-engine, findings/logs, cache Redis + Pub/Sub invalidation, tách CRUD-luật khỏi engine | `bm_cdss` (`cdss-dq-service`) |
| Oracle đa kết nối (DEFAULT ghi + HIS_RS đọc), CQRS, `@nestjs/schedule`, auth JWT + RBAC, seeder + migration `.sql` | `bm_patient_hub` |
| Scanner quét HIS theo watermark, dedup idempotent, workflow trạng thái finding | order-check gốc (Laravel) |
| FE 4 lớp (models/api/stores/views) + PrimeVue DataTable lazy | `bm_patient_hub` / `bmc-frontend-admin` |

**Quyết định đã chốt:**
- Mô hình project: repo **standalone fullstack** (backend NestJS + frontend Vue), không monorepo.
- Rule model: **Hybrid** — FactsBuilder (code) tính computed-facts; RuleEvaluator chạy **rule-as-data** (json-rules-engine) trên facts; luật phức tạp/xuyên bản ghi dùng **CodeChecker**.
- CSDL app: **Oracle** (DEFAULT ghi được) + **HIS_RS** đọc HIS; `synchronize:false`, migration `.sql` + seeder.
- **CQRS**: có, ngay từ đầu (tầng API).
- **Redis**: có, ngay từ đầu (cache rule + Pub/Sub invalidation + khóa quét phân tán; Bull cấu hình sẵn cho tương lai).
- Frontend: **Vue 3 + PrimeVue**.
- Thứ tự: **order-check trước**; module khác sau.

**Non-goals (pha này):** XML3176, ký số/gửi cổng BHXH, i18n, di trú dữ liệu lịch sử.

---

## 2. Kiến trúc tổng thể

### 2.1. Repo

```
bm-data-quality/
  backend/     # NestJS 11 + TypeORM + Oracle, CQRS, Redis, @nestjs/schedule
  frontend/    # Vue 3 + Vite + PrimeVue + Pinia
  docker/      # redis, compose
  docs/
```

### 2.2. Stack backend

NestJS 11, Node 24, TypeScript. TypeORM + `oracledb` (`synchronize:false`, migration `.sql` + seeder `typeorm-extension`). **CQRS** (`@nestjs/cqrs`). **`@nestjs/schedule`** (cron order-check). **Redis** (`ioredis`) + **`@nestjs/bull`** (cấu hình sẵn). `json-rules-engine`. `@nestjs/config`, `class-validator`, `@nestjs/swagger`. Auth `admin-auth` (JWT) + `role-permission` (RBAC). Test Jest.

### 2.3. Kết nối CSDL

| Kết nối | Vai trò | Ghi/Đọc |
|---|---|---|
| `DEFAULT` | Schema app (`DQ_*` + bảng module) | Ghi |
| `HIS_RS` | Đọc HIS (`HIS_SERVICE_REQ`…) | Chỉ đọc (raw SQL) |

### 2.4. DQ Engine lõi (module-agnostic)

```
@Cron → ScanOrchestrator (lấy khóa Redis SET NX chống chạy trùng đa-instance)
  → với mỗi Scanner đã đăng ký (module cung cấp):
      Scanner.fetch(): kéo bản ghi HIS mới theo watermark (HIS_RS, raw SQL, batched)
        → FactsBuilder(module): bản ghi thô → facts + computed-facts
        → RuleEvaluator: nạp DQ_RULES (từ cache Redis) → json-rules-engine chạy trên facts
        → CodeChecker(tùy chọn): luật xuyên bản ghi / phức tạp
        → FindingSink: upsert DQ_FINDINGS idempotent theo dedup_key
             (KHÔNG hồi sinh finding đã processed/false_positive)
      → ghi DQ_SCAN_LOGS + cập nhật DQ_WATERMARKS
```

**Thành phần lõi (interface rõ ràng, test độc lập được):**
- `Scanner` — `sourceKey()`, `fetch(watermark, limit): RawRecord[]`, `watermarkFieldOf(rec)`.
- `FactsBuilder` — `module()`, `build(raw): Facts` (gồm computed-facts như `_has_<field>`, `out_before_in`, `order_before_in`, `order_after_out`, `executor_missing_diploma`).
- `RuleEvaluator` — nạp rule theo `(module, sourceKey)` từ cache, chạy json-rules-engine, trả `Finding[]`.
- `CodeChecker` — `check(facts, raw): Finding[]` cho luật không diễn đạt được bằng data.
- `FindingSink` — `persist(finding): boolean` idempotent theo `dedup_key`.
- `RulesCache` — nạp/cache `DQ_RULES` trong Redis (TTL), lắng nghe Pub/Sub `dq:rules:invalidated` để xóa cache.

**Nguyên tắc hybrid:** phần tính toán khó (so sánh thời gian, join CCHN) nằm ở **FactsBuilder (code)**; ngưỡng/kích hoạt nằm ở **rule-as-data**. Ví dụ luật `B_DISCHARGE_BEFORE_ADMISSION`:
```json
{ "all": [ { "fact": "out_before_in", "operator": "equal", "value": true } ] }
```

### 2.5. CQRS (tầng API)

Controller mỏng → service dispatch `QueryBus`/`CommandBus`:
- Queries: `ListFindingsQuery`, `FindingsSummaryQuery`, `ListScanLogsQuery`, `ListRulesQuery`.
- Commands: `UpdateFindingStatusCommand`, `ToggleRuleCommand`, `UpdateRuleConditionsCommand` (command sửa rule sẽ **publish `dq:rules:invalidated`** để xóa cache).

Scanner/engine chạy nền do `ScanOrchestrator` (scheduled) gọi trực tiếp service — không qua bus.

### 2.6. Redis

1. **Rules cache**: `RulesCache` giữ `DQ_RULES` theo key `dq:rules:<module>:<sourceKey>` (TTL 300s); engine đọc từ cache. Degrade an toàn khi Redis chết → đọc thẳng DB.
2. **Pub/Sub invalidation**: kênh `dq:rules:invalidated`; command sửa rule publish → mọi instance xóa cache.
3. **Khóa quét phân tán**: `SET dq:scan:lock:<sourceKey> NX PX <ttl>` trước mỗi lần scan; giải phóng khi xong.
4. **Bull**: `BullModule` cấu hình sẵn (ioredis) cho xử lý nặng bất đồng bộ pha sau (parse XML3176). Order-check dùng cron, chưa dùng Bull.

---

## 3. Mô hình dữ liệu (Oracle, schema DEFAULT) — generic

| Bảng | Cột chính |
|---|---|
| `DQ_RULES` | `id, module, code`(unique theo module), `name, source_key, conditions`(CLOB JSON — cú pháp json-rules-engine), `severity`(info/warning/critical), `priority, params`(CLOB JSON), `is_active`, timestamps |
| `DQ_FINDINGS` | `id, module, source_key, rule_code, ref_type, ref_id, dedup_key`(unique), `patient_code, patient_name, department_id, department_name, doctor_loginname, severity, message, detail`(CLOB JSON), `status`(new/seen/processed/false_positive), `detected_at, processed_by, processed_at, note` |
| `DQ_WATERMARKS` | `source_key`(unique), `last_id, last_create_time, last_modify_time, last_run_at` |
| `DQ_SCAN_LOGS` | `source_key, started_at, finished_at, scanned_count, finding_count, status, error, duration_ms` |

Order-check = các dòng `DQ_RULES` (`module='order_check'`) + Scanner/FactsBuilder riêng. XML3176 sau = thêm module, **không đụng bảng lõi**. Bảng phụ riêng module (vd `ORDER_CHECK_REF_SERVICE_RESTRICTION` cho luật giới tính/tuổi) đặt riêng khi tới Pha 2.

`dedup_key` = `module:rule_code:ref_type:ref_id:sub_key` — idempotent.

---

## 4. Module Order-Check (đầu tiên)

### 4.1. Scanner (bám schema HIS thật đã xác nhận qua sqlcl)

`ServiceReqScanner` (`source_key='order_check.his_service_req'`): quét `HIS_SERVICE_REQ` theo `MODIFY_TIME` (cột NUMBER, có index). Lấy sẵn từ chính bản ghi: `ID, SERVICE_REQ_CODE, SERVICE_REQ_TYPE_ID, TREATMENT_ID, INTRUCTION_TIME, ICD_CODE, REQUEST_LOGINNAME, EXECUTE_LOGINNAME, EXECUTE_DEPARTMENT_ID, TDL_PATIENT_CODE, TDL_PATIENT_NAME`. Batched lookup:
- `HIS_TREATMENT` theo `TREATMENT_ID` → `IN_TIME, OUT_TIME`.
- `HIS_SERE_SERV` theo service_req → `EXECUTE_TIME, TDL_INTRUCTION_TIME` (cho luật execute-before-order theo từng dòng DV).
- `HIS_EMPLOYEE` theo `LOGINNAME` (= `EXECUTE_LOGINNAME`) → `DIPLOMA` (CCHN).

> Ghi chú: `TDL_PATIENT_CODE/NAME` có sẵn trên `HIS_SERVICE_REQ` → không cần join lấy tên bệnh nhân. Tuân thủ hiệu năng: quét theo cột có index, batched `WHERE id IN (...)`, không JOIN bảng lớn trong truy vấn watermark.

### 4.2. FactsBuilder — computed-facts

Từ bản ghi + lookup, dựng facts gồm: giá trị thô (`in_time, out_time, intruction_time, execute_loginname, execute_diploma, icd_code, service_req_type_id`) + **computed-facts**:
- `out_before_in` = `out_time>0 && in_time>0 && out_time<in_time`
- `order_before_in` = `intruction_time>0 && in_time>0 && intruction_time<in_time`
- `order_after_out` = `intruction_time>0 && out_time>0 && intruction_time>out_time`
- `executor_missing_diploma` = có `execute_loginname` nhưng `execute_diploma` rỗng
- `has_icd` = `icd_code` không rỗng
- `_has_<field>` cho các trường quan trọng.

### 4.3. Luật MVP

Rule-as-data (json-rules-engine trên computed-facts), seed vào `DQ_RULES`:
- `B_DISCHARGE_BEFORE_ADMISSION` (critical) — `out_before_in = true`.
- `B_ORDER_TIME_OUT_OF_STAY` (warning) — `order_before_in = true` OR `order_after_out = true` (2 sub-finding qua sub_key).
- `B_DOCTOR_NO_PRACTICE_CERT` (critical) — `executor_missing_diploma = true`.

CodeChecker:
- `B_EXECUTE_BEFORE_ORDER` (warning) — duyệt từng dòng `HIS_SERE_SERV`, sinh sub-finding khi `EXECUTE_TIME < TDL_INTRUCTION_TIME` (không diễn đạt gọn bằng 1 fact).

Luật lâm sàng `A_*` (tương tác thuốc, thiếu ICD, sai liều, giới tính/tuổi) → **Pha 2** (thêm scanner/nguồn + ref tương ứng).

### 4.4. Watermark

Khởi tạo = thời điểm hiện tại (không backfill lịch sử).

---

## 5. API (backend, prefix `admin/data-quality`, RBAC `data_quality:*`)

- `GET /findings` — danh sách (filter module/severity/status/rule/khoa/ngày; phân trang lazy) — `ListFindingsQuery`.
- `GET /findings/summary` — KPI/thống kê — `FindingsSummaryQuery`.
- `POST /findings/:id/status` — cập nhật trạng thái + ghi chú — `UpdateFindingStatusCommand`.
- `GET /findings/export` — xuất Excel.
- `GET /rules`, `POST /rules/:id/toggle`, `PUT /rules/:id` — quản lý rule (sửa `conditions`/severity; publish invalidation) — CQRS commands.
- `GET /scan-logs` — nhật ký quét — `ListScanLogsQuery`.

---

## 6. Frontend (Vue 3 + PrimeVue)

`views/backend/data-quality/`: **Findings** (Management + Filter + Table lazy + DetailDialog + workflow trạng thái), **Rules** (quản lý luật, sửa `conditions` JSON, bật/tắt), **ScanLogs**, **Dashboard**. Cấu trúc 4 lớp `models/data-quality.model.ts → api/data-quality.service.ts → stores/data-quality.store.ts → views`. Route + menu + `requiresPermission`. Không i18n.

---

## 7. Chia pha

| Pha | Nội dung | Kiểm chứng |
|---|---|---|
| **0. Scaffold** | Repo backend+frontend; Oracle DEFAULT+HIS_RS; Redis; auth+RBAC; health+login; docker | Đăng nhập được, `/health` xanh, Redis ping ok |
| **1. DQ core + Order-check B_*** | Bảng `DQ_*`; RulesCache (Redis+PubSub); RuleEvaluator (json-rules-engine); FindingSink idempotent; ScanOrchestrator + khóa Redis; ScheduleModule cron; ServiceReqScanner + OrderFactsBuilder; seed luật `B_*` (data) + `B_EXECUTE_BEFORE_ORDER` (CodeChecker); API `findings` + `scan-logs` (CQRS); FE Findings list | Cron quét HIS, sinh finding `B_*` idempotent, hiển thị trên giao diện; sửa rule → cache invalidate |
| **2. Order-check đầy đủ** | Luật `A_*` + scanner/nguồn tương ứng (tương tác thuốc, thiếu ICD, sai liều, giới tính/tuổi + ref restriction); quản lý rule UI; dashboard; export; email digest | Đủ bộ luật bật/tắt được; dashboard + export |
| **3+. Module khác** | XML3176 (dùng Bull parse) + module DQ khác cắm lên cùng engine | (plan riêng) |

---

## 8. Kiểm thử & rủi ro

**Kiểm thử (Jest):**
- `OrderFactsBuilder` — computed-facts từ bản ghi dựng sẵn (không DB).
- `RuleEvaluator` — chạy json-rules-engine trên facts với rule mẫu → đúng finding/severity.
- `FindingSink` — idempotent theo dedup_key (repo mock): finding mới lưu; trùng bị bỏ; đã processed không hồi sinh.
- `ServiceReqScanner`/`HisOrderSource` — test với dữ liệu HIS mock hoặc integration nhẹ.

**Rủi ro & giảm thiểu:**
- *Hiệu năng quét HIS (bảng lớn)* → quét theo `MODIFY_TIME` (index), batched lookup, không JOIN bảng lớn; giữ 5 quy tắc hiệu năng.
- *Redis chết* → degrade đọc rule thẳng DB; khóa quét mất → chấp nhận (cron 1 phút, idempotent chống trùng).
- *Cron chạy trùng đa-instance* → khóa Redis SET NX.
- *conditions JSON sai cú pháp khi admin sửa* → validate bằng schema json-rules-engine trước khi lưu (command).
- *Phình phạm vi* → giữ đúng ranh giới lõi + order-check; XML3176/A_* để pha sau.

---

## 9. Câu hỏi mở (khi lập plan)

- Tên repo chính thức (`bm-data-quality`).
- Có tách `admin-service` (CRUD rule) khỏi `engine` như bm_cdss không, hay một app modular monolith (đề xuất: monolith cho MVP).
- Mailer cho email digest (chốt ở Pha 2).
- Danh sách computed-facts đầy đủ cần cho `A_*` (chốt ở Pha 2).

---

*Thiết kế nền tảng hóa (DQ Engine + module), hybrid rule model, Oracle + CQRS + Redis, order-check trước. Sau khi review sẽ chuyển sang writing-plans (Pha 0 + Pha 1).*
