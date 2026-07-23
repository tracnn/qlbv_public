# Order-Check NestJS/Vue — Pha 0 (Scaffold) + Pha 1 (Order-Check lõi) — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Dựng project standalone fullstack `bm-giamdinh-hub` (NestJS + Vue) và triển khai lõi module Order-Check: cron quét HIS theo watermark, chạy nhóm luật cấu trúc `B_*`, ghi vi phạm idempotent, hiển thị trên giao diện danh sách.

**Architecture:** NestJS 11 + TypeORM + Oracle (schema `DEFAULT` ghi được + `HIS_RS` đọc HIS), CQRS, `@nestjs/schedule` cho cron. Rule handler là hàm thuần trên `OrderContext`, đăng ký qua Nest DI, engine lọc theo rule đang `is_active`. Frontend Vue 3 + PrimeVue + Pinia, cấu trúc 4 lớp. Nhân bản khuôn `bm_patient_hub`.

**Tech Stack:** NestJS 11, TypeScript, TypeORM, oracledb, @nestjs/cqrs, @nestjs/schedule, Jest; Vue 3, Vite, PrimeVue, Pinia, axios.

**Tham chiếu khuôn mẫu (chỉ đọc để sao chép/điều chỉnh):**
- Backend: `C:\Users\tracnn\bm_patient_hub\backend\` — đặc biệt `src/data-source.ts`, `src/configs/typeorm.config.ts`, `src/app.module.ts`, `src/health-metrics/` (mẫu module CQRS), `src/admin-auth/`, `src/role-permission/`, `src/common/`, `Dockerfile`, `docker-compose.yml`.
- Frontend: `C:\Users\tracnn\bm_patient_hub\frontend\` — `src/api/config.ts`, `src/stores/auth.store.ts`, `src/router/index.ts`, `src/data/menu.ts`, một feature mẫu bất kỳ (`src/{api,stores,models}/`, `src/views/backend/...`).
- Nghiệp vụ gốc: `C:\Users\tracnn\qlbv\docs\tai-lieu-tong-hop-xml3176-order-check.md` (mục 3, 13) và code Laravel `C:\Users\tracnn\qlbv\app\Services\OrderCheck\`.

**Quy ước chung:**
- Thư mục gốc project mới: `C:\Users\tracnn\bm-giamdinh-hub\` (đổi tên nếu cần — nếu đổi, thay đồng loạt trong plan).
- Mọi lệnh `npm`/`git` chạy trong `backend/` hoặc `frontend/` tương ứng (nêu rõ ở từng task).
- Git: khởi tạo repo riêng trong `bm-giamdinh-hub/`, commit sau mỗi task.
- Oracle: `synchronize:false`. Tạo bảng bằng file `.sql` trong `backend/src/migrations/` chạy tay (đúng khuôn `bm_patient_hub`).

---

## PHA 0 — SCAFFOLD

### Task 1: Khởi tạo repo & khung thư mục

**Files:**
- Create: `C:\Users\tracnn\bm-giamdinh-hub\.gitignore`
- Create: `C:\Users\tracnn\bm-giamdinh-hub\README.md`

- [ ] **Step 1: Tạo thư mục gốc và khởi tạo git**

```bash
mkdir -p /c/Users/tracnn/bm-giamdinh-hub/backend /c/Users/tracnn/bm-giamdinh-hub/frontend /c/Users/tracnn/bm-giamdinh-hub/docs
cd /c/Users/tracnn/bm-giamdinh-hub
git init
```

- [ ] **Step 2: Tạo `.gitignore`**

```
node_modules/
dist/
.env
*.log
coverage/
.DS_Store
```

- [ ] **Step 3: Tạo `README.md`**

```markdown
# bm-giamdinh-hub

Project standalone (NestJS + Vue) cho 2 module tách từ QLBV: kiểm tra sai sót y lệnh (order-check) và tiền giám định XML3176.

- `backend/` — NestJS 11 + TypeORM + Oracle (CQRS)
- `frontend/` — Vue 3 + Vite + PrimeVue + Pinia

Xem thiết kế: docs/ (spec gốc trong repo qlbv: docs/superpowers/specs/2026-07-23-tach-xml3176-order-check-nestjs-design.md).
```

- [ ] **Step 4: Commit**

```bash
git add -A && git commit -m "chore: init bm-giamdinh-hub repo skeleton"
```

---

### Task 2: Backend — khởi tạo NestJS + cấu hình cơ bản

**Files:**
- Create: `backend/package.json`, `backend/tsconfig.json`, `backend/nest-cli.json`, `backend/src/main.ts`, `backend/src/app.module.ts`, `backend/.env.example`

- [ ] **Step 1: Sao chép cấu hình nền từ khuôn mẫu**

Sao chép và điều chỉnh từ `bm_patient_hub/backend`: `package.json` (đổi `name` → `bm-giamdinh-backend`, bỏ dependency không dùng ở pha này: Puppeteer, MinIO, Meilisearch, Kafka, VNeID, Zalo/SMS; GIỮ: `@nestjs/*` core, `@nestjs/config`, `@nestjs/typeorm`, `typeorm`, `oracledb`, `@nestjs/cqrs`, `@nestjs/schedule`, `@nestjs/swagger`, `@nestjs/jwt`, `passport`/`passport-jwt`, `class-validator`, `class-transformer`, `typeorm-extension`, dev: `jest`, `ts-jest`, `@types/*`). Sao `tsconfig.json`, `nest-cli.json` nguyên bản.

- [ ] **Step 2: Tạo `src/main.ts`**

```typescript
import 'dotenv/config';
import { NestFactory } from '@nestjs/core';
import { ValidationPipe } from '@nestjs/common';
import { SwaggerModule, DocumentBuilder } from '@nestjs/swagger';
import { AppModule } from './app.module';

async function bootstrap() {
  const app = await NestFactory.create(AppModule);
  app.enableCors();
  app.useGlobalPipes(new ValidationPipe({ whitelist: true, transform: true }));
  const config = new DocumentBuilder()
    .setTitle('BM Giam Dinh Hub API')
    .addBearerAuth({ type: 'http', scheme: 'bearer' }, 'access-token')
    .build();
  SwaggerModule.setup('docs', app, SwaggerModule.createDocument(app, config));
  await app.listen(process.env.APP_PORT ?? 3200, '0.0.0.0');
}
bootstrap();
```

- [ ] **Step 3: Tạo `src/app.module.ts` khung tối thiểu**

```typescript
import { Module } from '@nestjs/common';
import { ConfigModule } from '@nestjs/config';
import { ScheduleModule } from '@nestjs/schedule';

@Module({
  imports: [
    ConfigModule.forRoot({ isGlobal: true }),
    ScheduleModule.forRoot(),
  ],
})
export class AppModule {}
```

- [ ] **Step 4: Tạo `.env.example`**

```
APP_PORT=3200
NODE_ENV=development

# Schema app (ghi duoc)
DB_HOST=
DB_PORT=1521
DB_SERVICE_NAME=
DB_USER=
DB_PASSWORD=
DB_POOL_MIN=1
DB_POOL_MAX=10

# HIS read-only
HRS_DB_HOST=
HRS_DB_PORT=1521
HRS_DB_SERVICE_NAME=
HRS_DB_USER=
HRS_DB_PASSWORD=
HRS_DB_POOL_MIN=1
HRS_DB_POOL_MAX=10

# Auth
JWT_ADMIN_SECRET=change-me
ENABLE_JWT_GUARD=true

# Order-check
ORDER_CHECK_SCAN_SLEEP=60
ORDER_CHECK_NOTIFY_ENABLED=false
```

- [ ] **Step 5: Cài đặt & build thử**

```bash
cd /c/Users/tracnn/bm-giamdinh-hub/backend && npm install && npm run build
```
Expected: build thành công, tạo `dist/`.

- [ ] **Step 6: Commit**

```bash
git add -A && git commit -m "chore(backend): scaffold NestJS app skeleton"
```

---

### Task 3: Backend — kết nối Oracle (DEFAULT + HIS_RS)

**Files:**
- Create: `backend/src/constants/common.constant.ts`, `backend/src/configs/build-oracle-connection-string.config.ts`, `backend/src/configs/typeorm.config.ts`
- Modify: `backend/src/app.module.ts`

- [ ] **Step 1: Tạo `src/constants/common.constant.ts`**

```typescript
export const BASE_SCHEMA = {
  DEFAULT: 'default',
  HIS_RS: 'HIS_RS',
} as const;
```

- [ ] **Step 2: Tạo `src/configs/build-oracle-connection-string.config.ts`**

Sao chép nguyên từ `bm_patient_hub/backend/src/configs/build-oracle-connection-string.config.ts` (hàm dựng connectString từ host/port/serviceName).

- [ ] **Step 3: Tạo `src/configs/typeorm.config.ts`**

```typescript
import { TypeOrmModuleOptions } from '@nestjs/typeorm';
import { ConfigService } from '@nestjs/config';
import { buildOracleConnectString } from './build-oracle-connection-string.config';

export function defaultDbConfig(cfg: ConfigService): TypeOrmModuleOptions {
  return {
    type: 'oracle',
    connectString: buildOracleConnectString(
      cfg.get('DB_HOST'), cfg.get('DB_PORT'), undefined, cfg.get('DB_SERVICE_NAME')),
    username: cfg.get('DB_USER'),
    password: cfg.get('DB_PASSWORD'),
    autoLoadEntities: true,
    synchronize: false,
    extra: { poolMin: +cfg.get('DB_POOL_MIN', 1), poolMax: +cfg.get('DB_POOL_MAX', 10) },
  };
}

export function hisRsDbConfig(cfg: ConfigService): TypeOrmModuleOptions {
  return {
    type: 'oracle',
    connectString: buildOracleConnectString(
      cfg.get('HRS_DB_HOST'), cfg.get('HRS_DB_PORT'), undefined, cfg.get('HRS_DB_SERVICE_NAME')),
    username: cfg.get('HRS_DB_USER'),
    password: cfg.get('HRS_DB_PASSWORD'),
    autoLoadEntities: false,
    synchronize: false,
    extra: { poolMin: +cfg.get('HRS_DB_POOL_MIN', 1), poolMax: +cfg.get('HRS_DB_POOL_MAX', 10) },
  };
}
```

- [ ] **Step 4: Đăng ký 2 kết nối trong `src/app.module.ts`**

```typescript
import { TypeOrmModule } from '@nestjs/typeorm';
import { ConfigService } from '@nestjs/config';
import { BASE_SCHEMA } from './constants/common.constant';
import { defaultDbConfig, hisRsDbConfig } from './configs/typeorm.config';

// them vao imports:
TypeOrmModule.forRootAsync({ name: BASE_SCHEMA.DEFAULT, inject: [ConfigService], useFactory: defaultDbConfig }),
TypeOrmModule.forRootAsync({ name: BASE_SCHEMA.HIS_RS, inject: [ConfigService], useFactory: hisRsDbConfig }),
```

- [ ] **Step 5: Build thử**

```bash
cd backend && npm run build
```
Expected: build thành công (chưa cần DB thật để build).

- [ ] **Step 6: Commit**

```bash
git add -A && git commit -m "feat(backend): oracle connections DEFAULT + HIS_RS"
```

---

### Task 4: Backend — nền tảng common

**Files:**
- Create: `backend/src/common/api.exception.ts`, `backend/src/common/errors.config.ts`, `backend/src/common/base.entity.ts`, `backend/src/common/pagination.util.ts`

- [ ] **Step 1: Sao chép `api.exception.ts` + `errors.config.ts`**

Sao từ `bm_patient_hub/backend/src/common/`. Trong `errors.config.ts` giữ các key chung + thêm key order-check dùng sau: `ORDER_CHECK_RULE_NOT_FOUND: { status: 404 }`, `ORDER_CHECK_VIOLATION_NOT_FOUND: { status: 404 }`.

- [ ] **Step 2: Tạo `src/common/base.entity.ts`**

```typescript
import { PrimaryGeneratedColumn, CreateDateColumn, UpdateDateColumn, Column } from 'typeorm';

export abstract class BaseEntity {
  @PrimaryGeneratedColumn('uuid')
  id: string;

  @CreateDateColumn({ name: 'CREATED_AT' })
  createdAt: Date;

  @UpdateDateColumn({ name: 'UPDATED_AT' })
  updatedAt: Date;

  @Column({ name: 'CREATED_BY', nullable: true })
  createdBy?: string;

  @Column({ name: 'UPDATED_BY', nullable: true })
  updatedBy?: string;
}
```

- [ ] **Step 3: Tạo `src/common/pagination.util.ts`**

```typescript
export interface Pagination { page: number; limit: number; total: number; totalPages: number; }

export function buildPagination(total: number, page: number, limit: number): Pagination {
  return { page, limit, total, totalPages: Math.ceil(total / limit) || 0 };
}
```

- [ ] **Step 4: Build & commit**

```bash
cd backend && npm run build && cd .. && git add -A && git commit -m "feat(backend): common foundation (exception, base entity, pagination)"
```

---

### Task 5: Backend — admin-auth + role-permission

**Files:**
- Create: `backend/src/admin-auth/*` (copy), `backend/src/role-permission/*` (copy)
- Modify: `backend/src/app.module.ts`

- [ ] **Step 1: Sao chép module auth từ khuôn mẫu**

Sao `bm_patient_hub/backend/src/admin-auth/` và `src/role-permission/` sang project mới. Điều chỉnh: entity RBAC bind vào kết nối `BASE_SCHEMA.DEFAULT`; secret đọc từ `JWT_ADMIN_SECRET`; strategy tên `'jwt-admin'`. Giữ `@Permission('resource:action')` + `PermissionsGuard` + `JwtAdminAuthGuard`.

- [ ] **Step 2: Import `AdminAuthModule` + `RolePermissionModule` vào `app.module.ts`**

- [ ] **Step 3: Tạo SQL bảng RBAC**

Sao mẫu DDL từ `bm_patient_hub/backend/src/migrations/` (các bảng roles/permissions/role_user/permission_role) vào `backend/src/migrations/2026-07-23-create-rbac.sql`.

- [ ] **Step 4: Build & commit**

```bash
cd backend && npm run build && cd .. && git add -A && git commit -m "feat(backend): admin-auth (JWT) + role-permission RBAC"
```

---

### Task 6: Backend — health endpoint + boot thật

**Files:**
- Create: `backend/src/health/health.controller.ts`, `backend/src/health/health.module.ts`
- Modify: `backend/src/app.module.ts`

- [ ] **Step 1: Tạo `health.controller.ts`**

```typescript
import { Controller, Get } from '@nestjs/common';

@Controller('health')
export class HealthController {
  @Get()
  check() {
    return { status: 'ok', ts: new Date().toISOString() };
  }
}
```

- [ ] **Step 2: Tạo `health.module.ts` và import vào `app.module.ts`**

```typescript
import { Module } from '@nestjs/common';
import { HealthController } from './health.controller';

@Module({ controllers: [HealthController] })
export class HealthModule {}
```

- [ ] **Step 3: Chạy dev với `.env` thật (điền DB) và kiểm tra**

```bash
cd backend && cp .env.example .env
# dien thong tin DB that vao .env, roi:
npm run start:dev
```
Mở `http://localhost:3200/health` → Expected: `{ "status": "ok", ... }`. Mở `http://localhost:3200/docs` → Swagger UI hiển thị.

- [ ] **Step 4: Commit**

```bash
git add -A && git commit -m "feat(backend): health endpoint; app boots against Oracle"
```

---

### Task 7: Frontend — scaffold Vue từ khuôn mẫu

**Files:**
- Create: `frontend/*` (copy khung), `frontend/src/api/config.ts`, `frontend/src/stores/auth.store.ts`, `frontend/src/router/index.ts`, `frontend/src/data/menu.ts`, `frontend/.env`

- [ ] **Step 1: Sao chép khung frontend**

Sao từ `bm_patient_hub/frontend`: `package.json` (đổi `name` → `bm-giamdinh-frontend`), `vite.config.ts`, `tsconfig.json`, `index.html`, `src/main.ts`, `src/layouts/`, `src/components/` (BaseBlock, BasePageHeading, BaseNavigation…), `src/api/config.ts`, `src/utils/`, `src/composables/usePermissions.ts`, `src/stores/auth.store.ts`, `src/router/index.ts` (giữ route auth + backend layout + 403), `src/data/menu.ts` (xóa các mục menu nghiệp vụ cũ, để mảng rỗng chờ thêm).

- [ ] **Step 2: Cấu hình `.env`**

```
VITE_API_BASE_URL=http://localhost:3200/admin
VITE_API_TIMEOUT=30000
```

Lưu ý: backend đặt prefix `admin` ở cấp `@Controller('admin/...')` (không set global prefix). Health nằm ở `/health` (không prefix). API nghiệp vụ nằm dưới `admin/`.

- [ ] **Step 3: Cài đặt & chạy dev**

```bash
cd /c/Users/tracnn/bm-giamdinh-hub/frontend && npm install && npm run dev
```
Expected: Vite chạy port 5173, mở được trang đăng nhập.

- [ ] **Step 4: Commit**

```bash
cd .. && git add -A && git commit -m "chore(frontend): scaffold Vue3 + PrimeVue from template"
```

---

### Task 8: Frontend — đăng nhập chạm backend

**Files:**
- Modify: `frontend/src/stores/auth.store.ts` (đảm bảo gọi `/admin/auth/login` + `/admin/auth/me`)

- [ ] **Step 1: Xác minh endpoint auth khớp backend**

Đảm bảo `auth.store.ts` gọi đúng path backend (`/admin/auth/login`, `/admin/auth/me`, `/admin/auth/refresh`). Điều chỉnh nếu khác.

- [ ] **Step 2: Tạo 1 tài khoản admin test**

Chạy seeder RBAC hoặc insert tay 1 user admin vào Oracle (theo cơ chế `role-permission`). Ghi lại cách tạo vào `docs/`.

- [ ] **Step 3: Kiểm tra đăng nhập end-to-end**

Chạy cả backend (`start:dev`) và frontend (`dev`). Đăng nhập bằng tài khoản test → vào được layout backend, không lỗi 401.

- [ ] **Step 4: Commit**

```bash
git add -A && git commit -m "feat: end-to-end login works (frontend <-> backend)"
```

**✅ Mốc Pha 0:** đăng nhập được, `/health` xanh, khung fullstack sẵn sàng.

---

## PHA 1 — ORDER-CHECK LÕI

> Từ đây làm việc trong `backend/src/order-check/` (module mới). Tham chiếu logic gốc: `qlbv/app/Services/OrderCheck/` và `docs/tai-lieu-tong-hop-xml3176-order-check.md` mục 3, 13.

### Task 9: Migration SQL cho bảng order_check_*

**Files:**
- Create: `backend/src/migrations/2026-07-23-create-order-check.sql`

- [ ] **Step 1: Viết DDL Oracle**

```sql
-- order_check_watermarks
CREATE TABLE ORDER_CHECK_WATERMARKS (
  ID VARCHAR2(36) DEFAULT SYS_GUID() PRIMARY KEY,
  SOURCE_KEY VARCHAR2(64) NOT NULL UNIQUE,
  LAST_ID NUMBER,
  LAST_CREATE_TIME NUMBER,
  LAST_MODIFY_TIME NUMBER,
  LAST_RUN_AT TIMESTAMP,
  CREATED_AT TIMESTAMP DEFAULT SYSTIMESTAMP,
  UPDATED_AT TIMESTAMP DEFAULT SYSTIMESTAMP
);

-- order_check_rules
CREATE TABLE ORDER_CHECK_RULES (
  ID VARCHAR2(36) DEFAULT SYS_GUID() PRIMARY KEY,
  CODE VARCHAR2(80) NOT NULL UNIQUE,
  FAMILY VARCHAR2(8) NOT NULL,
  RULE_TYPE VARCHAR2(120),
  NAME VARCHAR2(255),
  SEVERITY VARCHAR2(16) DEFAULT 'warning',
  PARAMS CLOB,
  IS_ACTIVE NUMBER(1) DEFAULT 1,
  CREATED_AT TIMESTAMP DEFAULT SYSTIMESTAMP,
  UPDATED_AT TIMESTAMP DEFAULT SYSTIMESTAMP
);

-- order_check_violations
CREATE TABLE ORDER_CHECK_VIOLATIONS (
  ID VARCHAR2(36) DEFAULT SYS_GUID() PRIMARY KEY,
  RULE_CODE VARCHAR2(80) NOT NULL,
  TREATMENT_ID NUMBER,
  TREATMENT_CODE VARCHAR2(64),
  SERVICE_REQ_CODE VARCHAR2(64),
  SERVICE_REQ_TYPE_ID NUMBER,
  PATIENT_CODE VARCHAR2(64),
  PATIENT_NAME VARCHAR2(255),
  DEPARTMENT_ID NUMBER,
  DEPARTMENT_NAME VARCHAR2(255),
  DOCTOR_LOGINNAME VARCHAR2(64),
  ORDER_REF_TYPE VARCHAR2(32),
  ORDER_REF_ID NUMBER,
  SEVERITY VARCHAR2(16),
  MESSAGE VARCHAR2(1000),
  DETAIL CLOB,
  DEDUP_KEY VARCHAR2(255) NOT NULL UNIQUE,
  STATUS VARCHAR2(20) DEFAULT 'new',
  DETECTED_AT TIMESTAMP DEFAULT SYSTIMESTAMP,
  PROCESSED_BY VARCHAR2(64),
  PROCESSED_AT TIMESTAMP,
  NOTE VARCHAR2(1000),
  CREATED_AT TIMESTAMP DEFAULT SYSTIMESTAMP,
  UPDATED_AT TIMESTAMP DEFAULT SYSTIMESTAMP
);
CREATE INDEX IX_OCV_STATUS ON ORDER_CHECK_VIOLATIONS (STATUS);
CREATE INDEX IX_OCV_RULE ON ORDER_CHECK_VIOLATIONS (RULE_CODE);

-- order_check_rule_logs
CREATE TABLE ORDER_CHECK_RULE_LOGS (
  ID VARCHAR2(36) DEFAULT SYS_GUID() PRIMARY KEY,
  SOURCE_KEY VARCHAR2(64),
  STARTED_AT TIMESTAMP,
  FINISHED_AT TIMESTAMP,
  SCANNED_COUNT NUMBER DEFAULT 0,
  VIOLATION_COUNT NUMBER DEFAULT 0,
  STATUS VARCHAR2(20),
  ERROR VARCHAR2(2000)
);
```

- [ ] **Step 2: Chạy DDL trên Oracle DEFAULT (bằng công cụ DB) và ghi chú lại**

Expected: 4 bảng được tạo. Xác minh `SELECT * FROM ORDER_CHECK_RULES` (rỗng) không lỗi.

- [ ] **Step 3: Commit**

```bash
git add -A && git commit -m "feat(order-check): DDL for order_check_* tables"
```

---

### Task 10: Entities TypeORM

**Files:**
- Create: `backend/src/order-check/entities/order-check-rule.entity.ts`, `order-check-violation.entity.ts`, `order-check-watermark.entity.ts`, `order-check-rule-log.entity.ts`

- [ ] **Step 1: Tạo `order-check-rule.entity.ts`**

```typescript
import { Entity, Column, PrimaryColumn } from 'typeorm';

@Entity('ORDER_CHECK_RULES')
export class OrderCheckRule {
  @PrimaryColumn({ name: 'ID' }) id: string;
  @Column({ name: 'CODE' }) code: string;
  @Column({ name: 'FAMILY' }) family: string;
  @Column({ name: 'RULE_TYPE', nullable: true }) ruleType: string;
  @Column({ name: 'NAME', nullable: true }) name: string;
  @Column({ name: 'SEVERITY' }) severity: string;
  @Column({ name: 'PARAMS', type: 'clob', nullable: true }) params: string;
  @Column({ name: 'IS_ACTIVE' }) isActive: number;
}
```

- [ ] **Step 2: Tạo `order-check-violation.entity.ts`**

```typescript
import { Entity, Column, PrimaryColumn } from 'typeorm';

@Entity('ORDER_CHECK_VIOLATIONS')
export class OrderCheckViolation {
  @PrimaryColumn({ name: 'ID' }) id: string;
  @Column({ name: 'RULE_CODE' }) ruleCode: string;
  @Column({ name: 'TREATMENT_ID', nullable: true }) treatmentId: number;
  @Column({ name: 'TREATMENT_CODE', nullable: true }) treatmentCode: string;
  @Column({ name: 'SERVICE_REQ_CODE', nullable: true }) serviceReqCode: string;
  @Column({ name: 'SERVICE_REQ_TYPE_ID', nullable: true }) serviceReqTypeId: number;
  @Column({ name: 'PATIENT_CODE', nullable: true }) patientCode: string;
  @Column({ name: 'PATIENT_NAME', nullable: true }) patientName: string;
  @Column({ name: 'DEPARTMENT_ID', nullable: true }) departmentId: number;
  @Column({ name: 'DEPARTMENT_NAME', nullable: true }) departmentName: string;
  @Column({ name: 'DOCTOR_LOGINNAME', nullable: true }) doctorLoginname: string;
  @Column({ name: 'ORDER_REF_TYPE' }) orderRefType: string;
  @Column({ name: 'ORDER_REF_ID', nullable: true }) orderRefId: number;
  @Column({ name: 'SEVERITY', nullable: true }) severity: string;
  @Column({ name: 'MESSAGE', nullable: true }) message: string;
  @Column({ name: 'DETAIL', type: 'clob', nullable: true }) detail: string;
  @Column({ name: 'DEDUP_KEY' }) dedupKey: string;
  @Column({ name: 'STATUS' }) status: string;
  @Column({ name: 'DETECTED_AT', nullable: true }) detectedAt: Date;
  @Column({ name: 'PROCESSED_BY', nullable: true }) processedBy: string;
  @Column({ name: 'PROCESSED_AT', nullable: true }) processedAt: Date;
  @Column({ name: 'NOTE', nullable: true }) note: string;
}
```

- [ ] **Step 3: Tạo `order-check-watermark.entity.ts` và `order-check-rule-log.entity.ts`**

```typescript
// order-check-watermark.entity.ts
import { Entity, Column, PrimaryColumn } from 'typeorm';
@Entity('ORDER_CHECK_WATERMARKS')
export class OrderCheckWatermark {
  @PrimaryColumn({ name: 'ID' }) id: string;
  @Column({ name: 'SOURCE_KEY' }) sourceKey: string;
  @Column({ name: 'LAST_ID', nullable: true }) lastId: number;
  @Column({ name: 'LAST_CREATE_TIME', nullable: true }) lastCreateTime: number;
  @Column({ name: 'LAST_MODIFY_TIME', nullable: true }) lastModifyTime: number;
  @Column({ name: 'LAST_RUN_AT', nullable: true }) lastRunAt: Date;
}
```

```typescript
// order-check-rule-log.entity.ts
import { Entity, Column, PrimaryColumn } from 'typeorm';
@Entity('ORDER_CHECK_RULE_LOGS')
export class OrderCheckRuleLog {
  @PrimaryColumn({ name: 'ID' }) id: string;
  @Column({ name: 'SOURCE_KEY', nullable: true }) sourceKey: string;
  @Column({ name: 'STARTED_AT', nullable: true }) startedAt: Date;
  @Column({ name: 'FINISHED_AT', nullable: true }) finishedAt: Date;
  @Column({ name: 'SCANNED_COUNT' }) scannedCount: number;
  @Column({ name: 'VIOLATION_COUNT' }) violationCount: number;
  @Column({ name: 'STATUS', nullable: true }) status: string;
  @Column({ name: 'ERROR', nullable: true }) error: string;
}
```

- [ ] **Step 4: Build & commit**

```bash
cd backend && npm run build && cd .. && git add -A && git commit -m "feat(order-check): TypeORM entities"
```

---

### Task 11: Support types — OrderContext & Violation (TDD)

**Files:**
- Create: `backend/src/order-check/support/order-context.ts`, `backend/src/order-check/support/violation.ts`
- Test: `backend/src/order-check/support/violation.spec.ts`

- [ ] **Step 1: Viết test cho `Violation.dedupKey()`**

```typescript
import { Violation } from './violation';

describe('Violation', () => {
  it('dedupKey ghep ruleCode:orderRefType:orderRefId:subKey', () => {
    const v = new Violation('B_TEST', 'service_req', 123, 'msg', { a: 1 });
    expect(v.dedupKey()).toBe('B_TEST:service_req:123:');
  });

  it('dedupKey co subKey khi nhieu vi pham/1 phieu', () => {
    const v = new Violation('B_TEST', 'service_req', 123, 'msg', {}, 'svc9');
    expect(v.dedupKey()).toBe('B_TEST:service_req:123:svc9');
  });
});
```

- [ ] **Step 2: Chạy test — kỳ vọng FAIL**

Run: `cd backend && npx jest src/order-check/support/violation.spec.ts`
Expected: FAIL ("Cannot find module './violation'").

- [ ] **Step 3: Tạo `support/violation.ts`**

```typescript
export class Violation {
  constructor(
    public ruleCode: string,
    public orderRefType: string,
    public orderRefId: number,
    public message: string,
    public detail: Record<string, any> = {},
    public subKey: string = '',
    public severity?: string,
  ) {}

  dedupKey(): string {
    return `${this.ruleCode}:${this.orderRefType}:${this.orderRefId}:${this.subKey}`;
  }
}
```

- [ ] **Step 4: Tạo `support/order-context.ts`**

```typescript
export class OrderContext {
  serviceReqId?: number;
  serviceReqCode?: string;
  serviceReqTypeId?: number;
  treatmentId?: number;
  treatmentCode?: string;
  patientCode?: string;
  patientName?: string;
  departmentId?: number;
  doctorLoginname?: string;
  executeLoginname?: string;
  executeDiploma?: string;
  intructionTime = 0;
  inTime = 0;
  outTime = 0;
  icdCode?: string;
  services: Array<{ id: number; executeTime?: number }> = [];
}
```

- [ ] **Step 5: Chạy test — kỳ vọng PASS**

Run: `cd backend && npx jest src/order-check/support/violation.spec.ts`
Expected: PASS (2 test).

- [ ] **Step 6: Commit**

```bash
git add -A && git commit -m "feat(order-check): OrderContext + Violation with dedupKey (TDD)"
```

---

### Task 12: RuleHandler interface + DischargeBeforeAdmissionRule (TDD)

**Files:**
- Create: `backend/src/order-check/rules/rule-handler.interface.ts`, `backend/src/order-check/rules/structural/discharge-before-admission.rule.ts`
- Test: `backend/src/order-check/rules/structural/discharge-before-admission.rule.spec.ts`

- [ ] **Step 1: Viết test**

```typescript
import { OrderContext } from '../../support/order-context';
import { DischargeBeforeAdmissionRule } from './discharge-before-admission.rule';

describe('DischargeBeforeAdmissionRule (B_DISCHARGE_BEFORE_ADMISSION)', () => {
  const rule = new DischargeBeforeAdmissionRule();

  it('sinh vi pham khi outTime < inTime', () => {
    const c = new OrderContext();
    c.inTime = 20260101080000; c.outTime = 20251231080000; c.treatmentId = 5;
    const out = rule.check(c);
    expect(out).toHaveLength(1);
    expect(out[0].ruleCode).toBe('B_DISCHARGE_BEFORE_ADMISSION');
    expect(out[0].orderRefType).toBe('treatment');
    expect(out[0].orderRefId).toBe(5);
  });

  it('khong sinh vi pham khi outTime >= inTime', () => {
    const c = new OrderContext();
    c.inTime = 20260101080000; c.outTime = 20260102080000;
    expect(rule.check(c)).toHaveLength(0);
  });

  it('khong sinh vi pham khi thieu du lieu (inTime=0)', () => {
    const c = new OrderContext();
    c.inTime = 0; c.outTime = 20260102080000;
    expect(rule.check(c)).toHaveLength(0);
  });
});
```

- [ ] **Step 2: Chạy test — kỳ vọng FAIL**

Run: `cd backend && npx jest discharge-before-admission`
Expected: FAIL (module chưa tồn tại).

- [ ] **Step 3: Tạo interface + rule**

```typescript
// rules/rule-handler.interface.ts
import { OrderContext } from '../support/order-context';
import { Violation } from '../support/violation';

export interface RuleHandler {
  code(): string;
  check(ctx: OrderContext): Violation[];
}

export const RULE_HANDLERS = Symbol('RULE_HANDLERS');
```

```typescript
// rules/structural/discharge-before-admission.rule.ts
import { Injectable } from '@nestjs/common';
import { RuleHandler } from '../rule-handler.interface';
import { OrderContext } from '../../support/order-context';
import { Violation } from '../../support/violation';

@Injectable()
export class DischargeBeforeAdmissionRule implements RuleHandler {
  code() { return 'B_DISCHARGE_BEFORE_ADMISSION'; }

  check(c: OrderContext): Violation[] {
    if (c.outTime > 0 && c.inTime > 0 && c.outTime < c.inTime) {
      return [new Violation(this.code(), 'treatment', c.treatmentId,
        `Ngay ra vien (${c.outTime}) truoc ngay vao vien (${c.inTime})`,
        { in_time: c.inTime, out_time: c.outTime })];
    }
    return [];
  }
}
```

- [ ] **Step 4: Chạy test — kỳ vọng PASS**

Run: `cd backend && npx jest discharge-before-admission`
Expected: PASS (3 test).

- [ ] **Step 5: Commit**

```bash
git add -A && git commit -m "feat(order-check): RuleHandler interface + DischargeBeforeAdmissionRule (TDD)"
```

---

### Task 13: OrderTimeOutOfStayRule (TDD)

**Files:**
- Create: `backend/src/order-check/rules/structural/order-time-out-of-stay.rule.ts`
- Test: `backend/src/order-check/rules/structural/order-time-out-of-stay.rule.spec.ts`

- [ ] **Step 1: Viết test**

```typescript
import { OrderContext } from '../../support/order-context';
import { OrderTimeOutOfStayRule } from './order-time-out-of-stay.rule';

describe('OrderTimeOutOfStayRule (B_ORDER_TIME_OUT_OF_STAY)', () => {
  const rule = new OrderTimeOutOfStayRule();

  it('vi pham khi gio y lenh truoc gio vao vien', () => {
    const c = new OrderContext();
    c.inTime = 20260101080000; c.outTime = 20260105080000;
    c.intructionTime = 20251231080000; c.serviceReqId = 7;
    const out = rule.check(c);
    expect(out).toHaveLength(1);
    expect(out[0].subKey).toBe('before_in');
  });

  it('vi pham khi gio y lenh sau gio ra vien', () => {
    const c = new OrderContext();
    c.inTime = 20260101080000; c.outTime = 20260105080000;
    c.intructionTime = 20260106080000; c.serviceReqId = 7;
    const out = rule.check(c);
    expect(out).toHaveLength(1);
    expect(out[0].subKey).toBe('after_out');
  });

  it('khong vi pham khi y lenh trong dot', () => {
    const c = new OrderContext();
    c.inTime = 20260101080000; c.outTime = 20260105080000;
    c.intructionTime = 20260103080000;
    expect(rule.check(c)).toHaveLength(0);
  });
});
```

- [ ] **Step 2: Chạy test — FAIL**

Run: `cd backend && npx jest order-time-out-of-stay`
Expected: FAIL.

- [ ] **Step 3: Implement**

```typescript
import { Injectable } from '@nestjs/common';
import { RuleHandler } from '../rule-handler.interface';
import { OrderContext } from '../../support/order-context';
import { Violation } from '../../support/violation';

@Injectable()
export class OrderTimeOutOfStayRule implements RuleHandler {
  code() { return 'B_ORDER_TIME_OUT_OF_STAY'; }

  check(c: OrderContext): Violation[] {
    if (!c.intructionTime) return [];
    if (c.inTime > 0 && c.intructionTime < c.inTime) {
      return [new Violation(this.code(), 'service_req', c.serviceReqId,
        `Gio y lenh (${c.intructionTime}) truoc gio vao vien (${c.inTime})`,
        { intruction_time: c.intructionTime, in_time: c.inTime }, 'before_in')];
    }
    if (c.outTime > 0 && c.intructionTime > c.outTime) {
      return [new Violation(this.code(), 'service_req', c.serviceReqId,
        `Gio y lenh (${c.intructionTime}) sau gio ra vien (${c.outTime})`,
        { intruction_time: c.intructionTime, out_time: c.outTime }, 'after_out')];
    }
    return [];
  }
}
```

- [ ] **Step 4: Chạy test — PASS**

Run: `cd backend && npx jest order-time-out-of-stay`
Expected: PASS (3 test).

- [ ] **Step 5: Commit**

```bash
git add -A && git commit -m "feat(order-check): OrderTimeOutOfStayRule (TDD)"
```

---

### Task 14: ExecuteBeforeOrderRule (TDD)

**Files:**
- Create: `backend/src/order-check/rules/structural/execute-before-order.rule.ts`
- Test: `backend/src/order-check/rules/structural/execute-before-order.rule.spec.ts`

- [ ] **Step 1: Viết test**

```typescript
import { OrderContext } from '../../support/order-context';
import { ExecuteBeforeOrderRule } from './execute-before-order.rule';

describe('ExecuteBeforeOrderRule (B_EXECUTE_BEFORE_ORDER)', () => {
  const rule = new ExecuteBeforeOrderRule();

  it('vi pham khi gio thuc hien DV < gio y lenh (1 vi pham/1 DV con)', () => {
    const c = new OrderContext();
    c.serviceReqId = 3; c.intructionTime = 20260101100000;
    c.services = [
      { id: 11, executeTime: 20260101090000 },
      { id: 12, executeTime: 20260101110000 },
    ];
    const out = rule.check(c);
    expect(out).toHaveLength(1);
    expect(out[0].subKey).toBe('svc11');
  });

  it('khong vi pham khi moi DV thuc hien sau y lenh', () => {
    const c = new OrderContext();
    c.intructionTime = 20260101100000;
    c.services = [{ id: 12, executeTime: 20260101110000 }];
    expect(rule.check(c)).toHaveLength(0);
  });
});
```

- [ ] **Step 2: Chạy test — FAIL**

Run: `cd backend && npx jest execute-before-order`
Expected: FAIL.

- [ ] **Step 3: Implement**

```typescript
import { Injectable } from '@nestjs/common';
import { RuleHandler } from '../rule-handler.interface';
import { OrderContext } from '../../support/order-context';
import { Violation } from '../../support/violation';

@Injectable()
export class ExecuteBeforeOrderRule implements RuleHandler {
  code() { return 'B_EXECUTE_BEFORE_ORDER'; }

  check(c: OrderContext): Violation[] {
    if (!c.intructionTime) return [];
    const out: Violation[] = [];
    for (const svc of c.services) {
      if (svc.executeTime && svc.executeTime > 0 && svc.executeTime < c.intructionTime) {
        out.push(new Violation(this.code(), 'service_req', c.serviceReqId,
          `DV ${svc.id} thuc hien (${svc.executeTime}) truoc gio y lenh (${c.intructionTime})`,
          { service_id: svc.id, execute_time: svc.executeTime, intruction_time: c.intructionTime },
          `svc${svc.id}`));
      }
    }
    return out;
  }
}
```

- [ ] **Step 4: Chạy test — PASS**

Run: `cd backend && npx jest execute-before-order`
Expected: PASS (2 test).

- [ ] **Step 5: Commit**

```bash
git add -A && git commit -m "feat(order-check): ExecuteBeforeOrderRule (TDD)"
```

---

### Task 15: DoctorPracticeCertRule (TDD)

**Files:**
- Create: `backend/src/order-check/rules/structural/doctor-practice-cert.rule.ts`
- Test: `backend/src/order-check/rules/structural/doctor-practice-cert.rule.spec.ts`

- [ ] **Step 1: Viết test**

```typescript
import { OrderContext } from '../../support/order-context';
import { DoctorPracticeCertRule } from './doctor-practice-cert.rule';

describe('DoctorPracticeCertRule (B_DOCTOR_NO_PRACTICE_CERT)', () => {
  const rule = new DoctorPracticeCertRule();

  it('vi pham khi co nguoi thuc hien nhung thieu CCHN (executeDiploma trong)', () => {
    const c = new OrderContext();
    c.serviceReqId = 8; c.executeLoginname = 'bs001'; c.executeDiploma = '';
    const out = rule.check(c);
    expect(out).toHaveLength(1);
    expect(out[0].ruleCode).toBe('B_DOCTOR_NO_PRACTICE_CERT');
  });

  it('khong vi pham khi co CCHN', () => {
    const c = new OrderContext();
    c.executeLoginname = 'bs001'; c.executeDiploma = 'CCHN-123';
    expect(rule.check(c)).toHaveLength(0);
  });

  it('khong vi pham khi khong co nguoi thuc hien', () => {
    const c = new OrderContext();
    c.executeLoginname = ''; c.executeDiploma = '';
    expect(rule.check(c)).toHaveLength(0);
  });
});
```

- [ ] **Step 2: Chạy test — FAIL**

Run: `cd backend && npx jest doctor-practice-cert`
Expected: FAIL.

- [ ] **Step 3: Implement**

```typescript
import { Injectable } from '@nestjs/common';
import { RuleHandler } from '../rule-handler.interface';
import { OrderContext } from '../../support/order-context';
import { Violation } from '../../support/violation';

@Injectable()
export class DoctorPracticeCertRule implements RuleHandler {
  code() { return 'B_DOCTOR_NO_PRACTICE_CERT'; }

  check(c: OrderContext): Violation[] {
    const hasExecutor = !!(c.executeLoginname && c.executeLoginname.trim());
    const hasDiploma = !!(c.executeDiploma && c.executeDiploma.trim());
    if (hasExecutor && !hasDiploma) {
      return [new Violation(this.code(), 'service_req', c.serviceReqId,
        `Nguoi thuc hien ${c.executeLoginname} thieu CCHN (DIPLOMA trong)`,
        { execute_loginname: c.executeLoginname })];
    }
    return [];
  }
}
```

- [ ] **Step 4: Chạy test — PASS**

Run: `cd backend && npx jest doctor-practice-cert`
Expected: PASS (3 test).

- [ ] **Step 5: Commit**

```bash
git add -A && git commit -m "feat(order-check): DoctorPracticeCertRule (TDD)"
```

---

### Task 16: Rule registry (Nest DI)

**Files:**
- Create: `backend/src/order-check/rules/common-rules.provider.ts`

- [ ] **Step 1: Tạo provider gom các handler `B_*`**

```typescript
import { Provider } from '@nestjs/common';
import { RULE_HANDLERS } from './rule-handler.interface';
import { DischargeBeforeAdmissionRule } from './structural/discharge-before-admission.rule';
import { OrderTimeOutOfStayRule } from './structural/order-time-out-of-stay.rule';
import { ExecuteBeforeOrderRule } from './structural/execute-before-order.rule';
import { DoctorPracticeCertRule } from './structural/doctor-practice-cert.rule';

export const ruleHandlerProviders: Provider[] = [
  DischargeBeforeAdmissionRule,
  OrderTimeOutOfStayRule,
  ExecuteBeforeOrderRule,
  DoctorPracticeCertRule,
  {
    provide: RULE_HANDLERS,
    useFactory: (r1, r2, r3, r4) => [r1, r2, r3, r4],
    inject: [DischargeBeforeAdmissionRule, OrderTimeOutOfStayRule, ExecuteBeforeOrderRule, DoctorPracticeCertRule],
  },
];
```

- [ ] **Step 2: Build & commit**

```bash
cd backend && npm run build && cd .. && git add -A && git commit -m "feat(order-check): rule handler DI registry (B_* rules)"
```

---

### Task 17: HisOrderSource — đọc HIS + dựng OrderContext

**Files:**
- Create: `backend/src/order-check/his-order-source.service.ts`

- [ ] **Step 1: Tạo service đọc HIS (raw SQL trên HIS_RS)**

```typescript
import { Injectable } from '@nestjs/common';
import { InjectDataSource } from '@nestjs/typeorm';
import { DataSource } from 'typeorm';
import { BASE_SCHEMA } from '../constants/common.constant';
import { OrderContext } from './support/order-context';

@Injectable()
export class HisOrderSource {
  constructor(@InjectDataSource(BASE_SCHEMA.HIS_RS) private his: DataSource) {}

  // Quet theo MODIFY_TIME co index; batched lookup DV con o buoc sau.
  async fetchServiceRequests(lastModifyTime: number, limit: number): Promise<any[]> {
    return this.his.query(
      `SELECT * FROM (
         SELECT sr.ID, sr.SERVICE_REQ_CODE, sr.SERVICE_REQ_TYPE_ID, sr.TREATMENT_ID,
                sr.EXECUTE_DEPARTMENT_ID, sr.REQUEST_LOGINNAME, sr.INTRUCTION_TIME,
                sr.MODIFY_TIME
         FROM HIS_SERVICE_REQ sr
         WHERE sr.MODIFY_TIME > :p0
         ORDER BY sr.MODIFY_TIME
       ) WHERE ROWNUM <= :p1`,
      [lastModifyTime || 0, limit],
    );
  }

  async fetchTreatmentInfo(treatmentIds: number[]): Promise<Map<number, any>> {
    if (!treatmentIds.length) return new Map();
    const rows = await this.his.query(
      `SELECT ID, TDL_PATIENT_CODE, TDL_PATIENT_NAME, IN_TIME, OUT_TIME
       FROM HIS_TREATMENT WHERE ID IN (${treatmentIds.map((_, i) => `:${i}`).join(',')})`,
      treatmentIds,
    );
    return new Map(rows.map((r: any) => [Number(r.ID), r]));
  }

  buildContext(sr: any, treatment: any): OrderContext {
    const c = new OrderContext();
    c.serviceReqId = Number(sr.ID);
    c.serviceReqCode = sr.SERVICE_REQ_CODE;
    c.serviceReqTypeId = sr.SERVICE_REQ_TYPE_ID != null ? Number(sr.SERVICE_REQ_TYPE_ID) : undefined;
    c.treatmentId = sr.TREATMENT_ID != null ? Number(sr.TREATMENT_ID) : undefined;
    c.departmentId = sr.EXECUTE_DEPARTMENT_ID != null ? Number(sr.EXECUTE_DEPARTMENT_ID) : undefined;
    c.doctorLoginname = sr.REQUEST_LOGINNAME;
    c.intructionTime = Number(sr.INTRUCTION_TIME) || 0;
    if (treatment) {
      c.patientCode = treatment.TDL_PATIENT_CODE;
      c.patientName = treatment.TDL_PATIENT_NAME;
      c.inTime = Number(treatment.IN_TIME) || 0;
      c.outTime = Number(treatment.OUT_TIME) || 0;
    }
    return c;
  }
}
```

> **Lưu ý:** tên cột HIS (`HIS_SERVICE_REQ`, `HIS_TREATMENT`) lấy theo mẫu bản gốc `qlbv/app/Services/OrderCheck/HisOrderSource.php`. Khi chạy thực tế cần đối chiếu tên cột đúng của HIS Bạch Mai; điều chỉnh raw SQL nếu lệch. Tuân thủ hiệu năng: chỉ quét cột có index, batched lookup `WHERE id IN (...)`.

- [ ] **Step 2: Build & commit**

```bash
cd backend && npm run build && cd .. && git add -A && git commit -m "feat(order-check): HisOrderSource reads HIS + builds OrderContext"
```

---

### Task 18: OrderCheckEngine.persist() idempotent (TDD)

**Files:**
- Create: `backend/src/order-check/order-check.engine.ts`
- Test: `backend/src/order-check/order-check.engine.spec.ts`

- [ ] **Step 1: Viết test cho persist idempotent (repo mock)**

```typescript
import { OrderCheckEngine } from './order-check.engine';
import { Violation } from './support/violation';

function makeRepo(existing: any[] = []) {
  const saved: any[] = [];
  return {
    saved,
    findOne: jest.fn(({ where }) => Promise.resolve(existing.find(e => e.dedupKey === where.dedupKey) || null)),
    save: jest.fn((v) => { saved.push(v); return Promise.resolve(v); }),
    create: jest.fn((v) => v),
  } as any;
}

describe('OrderCheckEngine.persist', () => {
  it('luu vi pham moi', async () => {
    const repo = makeRepo();
    const engine = new OrderCheckEngine(repo, {} as any, {} as any, {} as any);
    const v = new Violation('B_TEST', 'service_req', 1, 'msg', {}, '', 'warning');
    const ok = await engine.persist(v, { severity: 'warning' } as any);
    expect(ok).toBe(true);
    expect(repo.saved).toHaveLength(1);
    expect(repo.saved[0].dedupKey).toBe('B_TEST:service_req:1:');
  });

  it('bo qua khi dedup_key da ton tai (khong hoi sinh)', async () => {
    const repo = makeRepo([{ dedupKey: 'B_TEST:service_req:1:', status: 'processed' }]);
    const engine = new OrderCheckEngine(repo, {} as any, {} as any, {} as any);
    const v = new Violation('B_TEST', 'service_req', 1, 'msg');
    const ok = await engine.persist(v, { severity: 'warning' } as any);
    expect(ok).toBe(false);
    expect(repo.saved).toHaveLength(0);
  });
});
```

- [ ] **Step 2: Chạy test — FAIL**

Run: `cd backend && npx jest order-check.engine`
Expected: FAIL (module chưa có).

- [ ] **Step 3: Tạo `order-check.engine.ts` (đủ để persist chạy)**

```typescript
import { Injectable } from '@nestjs/common';
import { InjectRepository } from '@nestjs/typeorm';
import { Repository } from 'typeorm';
import { BASE_SCHEMA } from '../constants/common.constant';
import { OrderCheckViolation } from './entities/order-check-violation.entity';
import { OrderCheckRule } from './entities/order-check-rule.entity';
import { OrderCheckWatermark } from './entities/order-check-watermark.entity';
import { OrderCheckRuleLog } from './entities/order-check-rule-log.entity';
import { Violation } from './support/violation';

@Injectable()
export class OrderCheckEngine {
  constructor(
    @InjectRepository(OrderCheckViolation, BASE_SCHEMA.DEFAULT) private violations: Repository<OrderCheckViolation>,
    @InjectRepository(OrderCheckRule, BASE_SCHEMA.DEFAULT) private rules: Repository<OrderCheckRule>,
    @InjectRepository(OrderCheckWatermark, BASE_SCHEMA.DEFAULT) private watermarks: Repository<OrderCheckWatermark>,
    @InjectRepository(OrderCheckRuleLog, BASE_SCHEMA.DEFAULT) private logs: Repository<OrderCheckRuleLog>,
  ) {}

  async persist(v: Violation, rule: OrderCheckRule): Promise<boolean> {
    const key = v.dedupKey();
    const existing = await this.violations.findOne({ where: { dedupKey: key } });
    if (existing) return false; // idempotent: khong hoi sinh
    const row = this.violations.create({
      ruleCode: v.ruleCode,
      orderRefType: v.orderRefType,
      orderRefId: v.orderRefId,
      message: v.message,
      detail: JSON.stringify(v.detail),
      dedupKey: key,
      status: 'new',
      severity: v.severity ?? rule?.severity,
      detectedAt: new Date(),
    });
    await this.violations.save(row);
    return true;
  }
}
```

- [ ] **Step 4: Chạy test — PASS**

Run: `cd backend && npx jest order-check.engine`
Expected: PASS (2 test).

- [ ] **Step 5: Commit**

```bash
git add -A && git commit -m "feat(order-check): engine.persist idempotent by dedup_key (TDD)"
```

---

### Task 19: OrderCheckEngine.run() + ServiceReqScanner

**Files:**
- Modify: `backend/src/order-check/order-check.engine.ts`
- Create: `backend/src/order-check/scanners/service-req.scanner.ts`

- [ ] **Step 1: Thêm `run()` vào engine (nạp rule active + gọi scanner)**

```typescript
// Bo sung import:
import { Inject } from '@nestjs/common';
import { RULE_HANDLERS, RuleHandler } from './rules/rule-handler.interface';
import { ServiceReqScanner } from './scanners/service-req.scanner';

// Bo sung vao constructor:
//   @Inject(RULE_HANDLERS) private handlers: RuleHandler[],
//   private serviceReqScanner: ServiceReqScanner,

async run(limit = 500): Promise<{ scanned: number; violations: number }> {
  const active = await this.rules.find({ where: { isActive: 1 } });
  const activeCodes = new Set(active.map(r => r.code));
  const ruleByCode = new Map(active.map(r => [r.code, r]));
  const log = this.logs.create({ sourceKey: 'his_service_req', startedAt: new Date(), scannedCount: 0, violationCount: 0, status: 'running' });
  await this.logs.save(log);
  try {
    const res = await this.serviceReqScanner.scan(this, this.handlers, activeCodes, ruleByCode, limit);
    log.finishedAt = new Date(); log.scannedCount = res.scanned; log.violationCount = res.violations; log.status = 'success';
    await this.logs.save(log);
    return res;
  } catch (e: any) {
    log.finishedAt = new Date(); log.status = 'error'; log.error = String(e?.message ?? e);
    await this.logs.save(log);
    throw e;
  }
}

async getWatermark(sourceKey: string): Promise<OrderCheckWatermark> {
  let wm = await this.watermarks.findOne({ where: { sourceKey } });
  if (!wm) { wm = this.watermarks.create({ sourceKey, lastModifyTime: 0 }); await this.watermarks.save(wm); }
  return wm;
}
async saveWatermarkModify(sourceKey: string, lastModifyTime: number): Promise<void> {
  const wm = await this.getWatermark(sourceKey);
  wm.lastModifyTime = lastModifyTime; wm.lastRunAt = new Date();
  await this.watermarks.save(wm);
}
```

- [ ] **Step 2: Tạo `scanners/service-req.scanner.ts`**

```typescript
import { Injectable } from '@nestjs/common';
import { HisOrderSource } from '../his-order-source.service';
import { RuleHandler } from '../rules/rule-handler.interface';
import { OrderCheckRule } from '../entities/order-check-rule.entity';
import type { OrderCheckEngine } from '../order-check.engine';

@Injectable()
export class ServiceReqScanner {
  constructor(private source: HisOrderSource) {}

  async scan(engine: OrderCheckEngine, handlers: RuleHandler[], activeCodes: Set<string>,
             ruleByCode: Map<string, OrderCheckRule>, limit: number) {
    const sourceKey = 'his_service_req';
    const wm = await engine.getWatermark(sourceKey);
    const rows = await this.source.fetchServiceRequests(wm.lastModifyTime || 0, limit);
    let violations = 0, maxModify = wm.lastModifyTime || 0;

    const treatmentIds = [...new Set(rows.map(r => Number(r.TREATMENT_ID)).filter(Boolean))];
    const treatments = await this.source.fetchTreatmentInfo(treatmentIds);

    for (const sr of rows) {
      const ctx = this.source.buildContext(sr, treatments.get(Number(sr.TREATMENT_ID)));
      for (const h of handlers) {
        if (!activeCodes.has(h.code())) continue;
        for (const v of h.check(ctx)) {
          if (await engine.persist(v, ruleByCode.get(h.code()))) violations++;
        }
      }
      maxModify = Math.max(maxModify, Number(sr.MODIFY_TIME) || 0);
    }
    if (maxModify > (wm.lastModifyTime || 0)) await engine.saveWatermarkModify(sourceKey, maxModify);
    return { scanned: rows.length, violations };
  }
}
```

- [ ] **Step 3: Build & commit**

```bash
cd backend && npm run build && cd .. && git add -A && git commit -m "feat(order-check): engine.run() + ServiceReqScanner"
```

---

### Task 20: Cron quét theo lịch + khóa chống chạy trùng

**Files:**
- Create: `backend/src/order-check/order-check-scan.scheduler.ts`

- [ ] **Step 1: Tạo scheduler**

```typescript
import { Injectable, Logger } from '@nestjs/common';
import { Cron } from '@nestjs/schedule';
import { OrderCheckEngine } from './order-check.engine';

@Injectable()
export class OrderCheckScanScheduler {
  private readonly logger = new Logger(OrderCheckScanScheduler.name);
  private running = false;

  constructor(private engine: OrderCheckEngine) {}

  @Cron('*/1 * * * *') // moi 1 phut
  async handle() {
    if (this.running) { this.logger.warn('Bo qua: lan quet truoc chua xong'); return; }
    this.running = true;
    try {
      const res = await this.engine.run(500);
      this.logger.log(`Quet xong: scanned=${res.scanned} violations=${res.violations}`);
    } catch (e) {
      this.logger.error('Loi khi quet', e as any);
    } finally {
      this.running = false;
    }
  }
}
```

- [ ] **Step 2: Build & commit**

```bash
cd backend && npm run build && cd .. && git add -A && git commit -m "feat(order-check): @Cron scheduler with concurrency lock"
```

---

### Task 21: Seeder bộ quy tắc

**Files:**
- Create: `backend/src/seeders/order-check-rules.seeder.ts`
- Modify: `backend/src/data-source.ts` (nếu chưa có, sao từ khuôn mẫu)

- [ ] **Step 1: Tạo seeder seed 4 luật B_* (A_* seed ở Pha 2)**

```typescript
import { DataSource } from 'typeorm';
import { Seeder } from 'typeorm-extension';
import { OrderCheckRule } from '../order-check/entities/order-check-rule.entity';

const RULES = [
  { code: 'B_DISCHARGE_BEFORE_ADMISSION', family: 'B', ruleType: 'DischargeBeforeAdmissionRule', name: 'Ngay ra vien truoc ngay vao vien', severity: 'critical' },
  { code: 'B_ORDER_TIME_OUT_OF_STAY', family: 'B', ruleType: 'OrderTimeOutOfStayRule', name: 'Gio y lenh ngoai dot dieu tri', severity: 'warning' },
  { code: 'B_EXECUTE_BEFORE_ORDER', family: 'B', ruleType: 'ExecuteBeforeOrderRule', name: 'Gio thuc hien truoc gio y lenh', severity: 'warning' },
  { code: 'B_DOCTOR_NO_PRACTICE_CERT', family: 'B', ruleType: 'DoctorPracticeCertRule', name: 'Nguoi thuc hien thieu CCHN', severity: 'critical' },
];

export default class OrderCheckRulesSeeder implements Seeder {
  async run(dataSource: DataSource): Promise<void> {
    const repo = dataSource.getRepository(OrderCheckRule);
    for (const r of RULES) {
      const exists = await repo.findOne({ where: { code: r.code } });
      if (!exists) await repo.save(repo.create({ ...r, isActive: 1 }));
    }
  }
}
```

- [ ] **Step 2: Chạy seeder + khởi tạo watermark = hiện tại**

```bash
cd backend && npm run seed
```
Sau đó khởi tạo watermark để không backfill (chạy SQL 1 lần):
```sql
INSERT INTO ORDER_CHECK_WATERMARKS (SOURCE_KEY, LAST_MODIFY_TIME, LAST_RUN_AT)
VALUES ('his_service_req', TO_NUMBER(TO_CHAR(SYSTIMESTAMP,'YYYYMMDDHH24MISS')), SYSTIMESTAMP);
```
Expected: `SELECT COUNT(*) FROM ORDER_CHECK_RULES` = 4.

- [ ] **Step 3: Commit**

```bash
cd .. && git add -A && git commit -m "feat(order-check): seed B_* rules + init watermark"
```

---

### Task 22: API danh sách vi phạm + summary (CQRS + RBAC)

**Files:**
- Create: `backend/src/order-check/dto/list-violations.dto.ts`, `backend/src/order-check/queries/list-violations.query.ts`, `list-violations.handler.ts`, `backend/src/order-check/order-check.controller.ts`, `order-check.service.ts`
- Test: `backend/src/order-check/queries/list-violations.handler.spec.ts`

- [ ] **Step 1: Tạo DTO**

```typescript
import { ApiPropertyOptional } from '@nestjs/swagger';
import { IsOptional, IsInt, IsString, Min } from 'class-validator';
import { Type } from 'class-transformer';

export class ListViolationsDto {
  @ApiPropertyOptional() @IsOptional() @Type(() => Number) @IsInt() @Min(1) page = 1;
  @ApiPropertyOptional() @IsOptional() @Type(() => Number) @IsInt() @Min(1) limit = 20;
  @ApiPropertyOptional() @IsOptional() @IsString() status?: string;
  @ApiPropertyOptional() @IsOptional() @IsString() severity?: string;
  @ApiPropertyOptional() @IsOptional() @IsString() ruleCode?: string;
}
```

- [ ] **Step 2: Viết test cho handler (repo/queryBuilder mock)**

```typescript
import { ListViolationsHandler } from './list-violations.handler';

function mockRepo(items: any[], total: number) {
  const qb: any = {
    andWhere: jest.fn().mockReturnThis(),
    orderBy: jest.fn().mockReturnThis(),
    skip: jest.fn().mockReturnThis(),
    take: jest.fn().mockReturnThis(),
    getManyAndCount: jest.fn().mockResolvedValue([items, total]),
  };
  return { createQueryBuilder: jest.fn(() => qb) } as any;
}

describe('ListViolationsHandler', () => {
  it('tra ve data + pagination', async () => {
    const repo = mockRepo([{ id: '1' }], 1);
    const handler = new ListViolationsHandler(repo);
    const res = await handler.execute({ dto: { page: 1, limit: 20 } } as any);
    expect(res.data).toHaveLength(1);
    expect(res.pagination.total).toBe(1);
  });

  it('ap filter status', async () => {
    const repo = mockRepo([], 0);
    const qb = repo.createQueryBuilder();
    const handler = new ListViolationsHandler(repo);
    await handler.execute({ dto: { page: 1, limit: 20, status: 'new' } } as any);
    expect(qb.andWhere).toHaveBeenCalled();
  });
});
```

- [ ] **Step 3: Chạy test — FAIL**

Run: `cd backend && npx jest list-violations.handler`
Expected: FAIL.

- [ ] **Step 4: Tạo query + handler**

```typescript
// queries/list-violations.query.ts
export class ListViolationsQuery {
  constructor(public dto: any) {}
}
```

```typescript
// queries/list-violations.handler.ts
import { IQueryHandler, QueryHandler } from '@nestjs/cqrs';
import { InjectRepository } from '@nestjs/typeorm';
import { Repository } from 'typeorm';
import { BASE_SCHEMA } from '../../constants/common.constant';
import { OrderCheckViolation } from '../entities/order-check-violation.entity';
import { ListViolationsQuery } from './list-violations.query';
import { buildPagination } from '../../common/pagination.util';

@QueryHandler(ListViolationsQuery)
export class ListViolationsHandler implements IQueryHandler<ListViolationsQuery> {
  constructor(@InjectRepository(OrderCheckViolation, BASE_SCHEMA.DEFAULT) private repo: Repository<OrderCheckViolation>) {}

  async execute({ dto }: ListViolationsQuery) {
    const { page = 1, limit = 20, status, severity, ruleCode } = dto;
    const qb = this.repo.createQueryBuilder('v');
    if (status) qb.andWhere('v.status = :status', { status });
    if (severity) qb.andWhere('v.severity = :severity', { severity });
    if (ruleCode) qb.andWhere('v.ruleCode = :ruleCode', { ruleCode });
    qb.orderBy('v.detectedAt', 'DESC').skip((page - 1) * limit).take(limit);
    const [data, total] = await qb.getManyAndCount();
    return { data, pagination: buildPagination(total, page, limit) };
  }
}
```

- [ ] **Step 5: Chạy test — PASS**

Run: `cd backend && npx jest list-violations.handler`
Expected: PASS (2 test).

- [ ] **Step 6: Tạo service + controller (RBAC)**

```typescript
// order-check.service.ts
import { Injectable } from '@nestjs/common';
import { QueryBus } from '@nestjs/cqrs';
import { ListViolationsQuery } from './queries/list-violations.query';

@Injectable()
export class OrderCheckService {
  constructor(private queryBus: QueryBus) {}
  listViolations(dto: any) { return this.queryBus.execute(new ListViolationsQuery(dto)); }
}
```

```typescript
// order-check.controller.ts
import { Controller, Get, Query, UseGuards } from '@nestjs/common';
import { ApiTags, ApiBearerAuth } from '@nestjs/swagger';
import { JwtAdminAuthGuard } from '../admin-auth/jwt-admin-auth.guard';
import { PermissionsGuard } from '../role-permission/guards/permissions.guard';
import { Permission } from '../role-permission/decorators/permission.decorator';
import { OrderCheckService } from './order-check.service';
import { ListViolationsDto } from './dto/list-violations.dto';

@ApiTags('order-check')
@ApiBearerAuth('access-token')
@UseGuards(JwtAdminAuthGuard, PermissionsGuard)
@Controller('admin/order-check')
export class OrderCheckController {
  constructor(private service: OrderCheckService) {}

  @Get('violations')
  @Permission('order_check:read')
  list(@Query() dto: ListViolationsDto) {
    return this.service.listViolations(dto);
  }
}
```

- [ ] **Step 7: Commit**

```bash
git add -A && git commit -m "feat(order-check): list violations API (CQRS + RBAC) (TDD)"
```

---

### Task 23: Module wiring + đăng ký vào AppModule

**Files:**
- Create: `backend/src/order-check/order-check.module.ts`
- Modify: `backend/src/app.module.ts`

- [ ] **Step 1: Tạo `order-check.module.ts`**

```typescript
import { Module } from '@nestjs/common';
import { CqrsModule } from '@nestjs/cqrs';
import { TypeOrmModule } from '@nestjs/typeorm';
import { BASE_SCHEMA } from '../constants/common.constant';
import { OrderCheckRule } from './entities/order-check-rule.entity';
import { OrderCheckViolation } from './entities/order-check-violation.entity';
import { OrderCheckWatermark } from './entities/order-check-watermark.entity';
import { OrderCheckRuleLog } from './entities/order-check-rule-log.entity';
import { HisOrderSource } from './his-order-source.service';
import { ServiceReqScanner } from './scanners/service-req.scanner';
import { OrderCheckEngine } from './order-check.engine';
import { OrderCheckScanScheduler } from './order-check-scan.scheduler';
import { OrderCheckController } from './order-check.controller';
import { OrderCheckService } from './order-check.service';
import { ListViolationsHandler } from './queries/list-violations.handler';
import { ruleHandlerProviders } from './rules/common-rules.provider';
import { AdminAuthModule } from '../admin-auth/admin-auth.module';
import { RolePermissionModule } from '../role-permission/role-permission.module';

@Module({
  imports: [
    CqrsModule,
    TypeOrmModule.forFeature(
      [OrderCheckRule, OrderCheckViolation, OrderCheckWatermark, OrderCheckRuleLog],
      BASE_SCHEMA.DEFAULT,
    ),
    AdminAuthModule,
    RolePermissionModule,
  ],
  controllers: [OrderCheckController],
  providers: [
    OrderCheckService, OrderCheckEngine, HisOrderSource, ServiceReqScanner,
    OrderCheckScanScheduler, ListViolationsHandler, ...ruleHandlerProviders,
  ],
})
export class OrderCheckModule {}
```

- [ ] **Step 2: Import `OrderCheckModule` vào `app.module.ts`, build**

```bash
cd backend && npm run build
```
Expected: build thành công.

- [ ] **Step 3: Chạy dev, xác minh cron chạy**

```bash
npm run start:dev
```
Expected: log `Quet xong: scanned=... violations=...` xuất hiện trong vòng 1 phút (nếu HIS có dữ liệu mới sau watermark). `GET /admin/order-check/violations` (kèm Bearer token) trả `{ data, pagination }`.

- [ ] **Step 4: Commit**

```bash
cd .. && git add -A && git commit -m "feat(order-check): wire module into app; cron + API live"
```

---

### Task 24: Frontend — model + service + store

**Files:**
- Create: `frontend/src/models/order-check.model.ts`, `frontend/src/api/order-check.service.ts`, `frontend/src/stores/order-check.store.ts`

- [ ] **Step 1: Tạo model**

```typescript
export interface OrderCheckViolation {
  id: string;
  ruleCode: string;
  serviceReqCode?: string;
  patientCode?: string;
  patientName?: string;
  departmentName?: string;
  doctorLoginname?: string;
  severity?: string;
  message?: string;
  status: string;
  detectedAt?: string;
}
export interface ListViolationsParams { page?: number; limit?: number; status?: string; severity?: string; ruleCode?: string; }
export interface Pagination { page: number; limit: number; total: number; totalPages: number; }
export interface ListViolationsResponse { data: OrderCheckViolation[]; pagination: Pagination; }
```

- [ ] **Step 2: Tạo service**

```typescript
import apiClient from './config';
import type { ListViolationsParams, ListViolationsResponse } from '../models/order-check.model';

export const orderCheckService = {
  async getViolations(params: ListViolationsParams): Promise<ListViolationsResponse> {
    const res = await apiClient.get('/order-check/violations', { params });
    return res.data;
  },
};
```

> Lưu ý: `apiClient` baseURL đã là `.../admin`, nên path là `/order-check/violations`.

- [ ] **Step 3: Tạo store (Pinia options)**

```typescript
import { defineStore } from 'pinia';
import { orderCheckService } from '../api/order-check.service';
import type { OrderCheckViolation, Pagination, ListViolationsParams } from '../models/order-check.model';

export const useOrderCheckStore = defineStore('orderCheck', {
  state: () => ({
    violations: [] as OrderCheckViolation[],
    loading: false,
    pagination: { page: 1, limit: 20, total: 0, totalPages: 0 } as Pagination,
  }),
  actions: {
    async fetchViolations(params: ListViolationsParams) {
      this.loading = true;
      try {
        const res = await orderCheckService.getViolations(params);
        this.violations = res.data;
        this.pagination = res.pagination;
      } finally {
        this.loading = false;
      }
    },
  },
});
```

- [ ] **Step 4: Commit**

```bash
git add -A && git commit -m "feat(frontend): order-check model + service + store"
```

---

### Task 25: Frontend — màn danh sách + route + menu

**Files:**
- Create: `frontend/src/views/backend/order-check/OrderCheckManagement.vue`, `OrderCheckTable.vue`
- Modify: `frontend/src/router/index.ts`, `frontend/src/data/menu.ts`

- [ ] **Step 1: Tạo `OrderCheckTable.vue` (PrimeVue DataTable lazy)**

```vue
<script setup lang="ts">
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Tag from 'primevue/tag';
import type { OrderCheckViolation, Pagination } from '../../../models/order-check.model';

defineProps<{ items: OrderCheckViolation[]; loading: boolean; pagination: Pagination }>();
const emit = defineEmits<{ (e: 'page', ev: any): void }>();

function severityColor(s?: string) {
  return s === 'critical' ? 'danger' : s === 'warning' ? 'warn' : 'info';
}
</script>

<template>
  <DataTable :value="items" :loading="loading" lazy paginator
    :rows="pagination.limit" :totalRecords="pagination.total"
    :first="(pagination.page - 1) * pagination.limit"
    @page="emit('page', $event)">
    <Column field="ruleCode" header="Mã luật" />
    <Column field="patientName" header="Bệnh nhân" />
    <Column field="serviceReqCode" header="Phiếu" />
    <Column field="departmentName" header="Khoa" />
    <Column header="Mức độ">
      <template #body="{ data }"><Tag :value="data.severity" :severity="severityColor(data.severity)" /></template>
    </Column>
    <Column field="message" header="Nội dung" />
    <Column field="status" header="Trạng thái" />
  </DataTable>
</template>
```

- [ ] **Step 2: Tạo `OrderCheckManagement.vue`**

```vue
<script setup lang="ts">
import { onMounted } from 'vue';
import { storeToRefs } from 'pinia';
import { useOrderCheckStore } from '../../../stores/order-check.store';
import OrderCheckTable from './OrderCheckTable.vue';

const store = useOrderCheckStore();
const { violations, loading, pagination } = storeToRefs(store);

function load(page = 1) {
  store.fetchViolations({ page, limit: pagination.value.limit });
}
function onPage(ev: any) {
  load(Math.floor(ev.first / ev.rows) + 1);
}
onMounted(() => load(1));
</script>

<template>
  <div class="content">
    <h2>Kiểm tra sai sót y lệnh</h2>
    <OrderCheckTable :items="violations" :loading="loading" :pagination="pagination" @page="onPage" />
  </div>
</template>
```

- [ ] **Step 3: Thêm route trong `router/index.ts`**

Thêm vào nhánh `path: '/backend'` (children):
```typescript
{
  path: 'order-check',
  name: 'order-check',
  component: () => import('../views/backend/order-check/OrderCheckManagement.vue'),
  meta: { requiresAuth: true, requiresPermission: 'order_check:read' },
},
```

- [ ] **Step 4: Thêm mục menu trong `data/menu.ts`**

```typescript
{ name: 'order-check', to: 'order-check', icon: 'si si-check', permission: 'order_check:read' },
```

- [ ] **Step 5: Chạy frontend, kiểm tra**

```bash
cd frontend && npm run dev
```
Đăng nhập (tài khoản có quyền `order_check:read`) → menu "Kiểm tra sai sót y lệnh" hiện → mở ra thấy bảng vi phạm (dữ liệu từ backend). Nếu chưa có vi phạm, bảng rỗng nhưng không lỗi.

- [ ] **Step 6: Commit**

```bash
cd .. && git add -A && git commit -m "feat(frontend): order-check list screen + route + menu"
```

---

### Task 26: Kiểm chứng end-to-end Pha 1

- [ ] **Step 1: Chạy toàn bộ test backend**

Run: `cd backend && npx jest`
Expected: PASS toàn bộ (violation, 4 rule handler, engine, list handler).

- [ ] **Step 2: Kiểm tra luồng thật**

1. Backend `start:dev` + frontend `dev` cùng chạy.
2. Chờ cron chạy (hoặc tạo dữ liệu HIS test có `OUT_TIME < IN_TIME` để kích hoạt `B_DISCHARGE_BEFORE_ADMISSION`).
3. `SELECT * FROM ORDER_CHECK_VIOLATIONS` có bản ghi; `ORDER_CHECK_RULE_LOGS` ghi lần quét `success`.
4. Trên giao diện: màn "Kiểm tra sai sót y lệnh" hiển thị vi phạm.
5. Chạy quét 2 lần → không sinh trùng (idempotent theo `dedup_key`).

- [ ] **Step 3: Ghi chú kết quả kiểm chứng vào `docs/`**

- [ ] **Step 4: Commit**

```bash
git add -A && git commit -m "test(order-check): end-to-end verification phase 1"
```

**✅ Mốc Pha 1:** cron quét HIS, sinh vi phạm `B_*` idempotent, hiển thị trên giao diện danh sách.

---

## Self-review notes (đã kiểm)

- **Spec coverage (Pha 0+1):** scaffold repo/backend/frontend (Task 1–8) ✔; kết nối DEFAULT+HIS_RS (Task 3) ✔; auth+RBAC (Task 5) ✔; bảng order_check_* + entities (Task 9–10) ✔; OrderContext/Violation/dedup (Task 11) ✔; luật B_* (Task 12–16) ✔; đọc HIS (Task 17) ✔; engine idempotent + run (Task 18–19) ✔; cron + lock (Task 20) ✔; seeder + init watermark (Task 21) ✔; API list (Task 22) ✔; wiring (Task 23) ✔; frontend list (Task 24–25) ✔; kiểm chứng (Task 26) ✔. **Pha 2–4 (luật A_*, quản lý quy tắc, danh mục giới hạn DV, dashboard, export, email digest, toàn bộ XML3176) sẽ có plan riêng.**
- **Type consistency:** `RuleHandler.check()` trả `Violation[]` nhất quán; `Violation.dedupKey()` dùng ở engine.persist; `OrderContext` field dùng thống nhất trong các rule; `BASE_SCHEMA.DEFAULT`/`HIS_RS` nhất quán.
- **Lưu ý phụ thuộc thực tế:** tên cột HIS trong Task 17 theo mẫu bản gốc — cần đối chiếu schema HIS Bạch Mai khi chạy; các file copy từ `bm_patient_hub` (Task 2,3,5,7) cần đọc file gốc để lấy nội dung chính xác khi thực thi.

---

*Plan cho Pha 0 + Pha 1. Sau khi hoàn tất, lập plan tiếp cho Pha 2 (order-check đầy đủ) rồi Pha 3–4 (XML3176).*
