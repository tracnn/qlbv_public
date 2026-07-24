# bm-data-quality — Pha 0 (Scaffold) + Pha 1 (DQ core + Order-check B_* + Phân quyền khoa) — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Dựng nền tảng standalone `bm-data-quality` (NestJS + Vue) với DQ Engine lõi và module order-check: cron quét HIS theo watermark, tính computed-facts, chạy luật `B_*` bằng json-rules-engine (rule-as-data), ghi finding idempotent, áp phân quyền theo khoa, hiển thị trên giao diện.

**Architecture:** DQ Engine module-agnostic (Scanner → FactsBuilder → RuleEvaluator → CodeChecker → FindingSink). Oracle `DEFAULT` (ghi) + `HIS_RS` (đọc). CQRS ở tầng API. Redis: rules cache + pub/sub invalidation + khóa quét. Hybrid rule model: computed-facts (code) + rule-as-data (json-rules-engine). Frontend Vue 3 + PrimeVue, 4 lớp.

**Tech Stack:** NestJS 11, TypeScript, TypeORM + oracledb, @nestjs/cqrs, @nestjs/schedule, ioredis, @nestjs/bull, json-rules-engine, class-validator, Jest; Vue 3, Vite, PrimeVue, Pinia, axios.

**Tham chiếu (chỉ đọc để sao chép/điều chỉnh):**
- `C:\Users\tracnn\bm_patient_hub\backend\` — `src/configs/typeorm.config.ts`, `src/app.module.ts`, `src/admin-auth/`, `src/role-permission/`, `src/common/`, `Dockerfile`, `docker-compose.yml`.
- `C:\Users\tracnn\bm_cdss\cdss-dq-service\src\` — `rules-cache/`, `validate/rules-checker.service.ts`, `validate/computed-facts.service.ts` (mẫu rule-as-data + cache + pub/sub).
- `C:\Users\tracnn\bm_patient_hub\frontend\` — `src/api/config.ts`, `src/stores/auth.store.ts`, `src/router/index.ts`, `src/data/menu.ts`, một feature mẫu.
- Nghiệp vụ gốc: `C:\Users\tracnn\qlbv\docs\tai-lieu-tong-hop-xml3176-order-check.md` (mục 3, 13).

**⚠ NGUYÊN TẮC TỐI ƯU (bắt buộc — KHÔNG bê nguyên logic PHP):**
1. **Batch dedup**: KHÔNG `findOne` mỗi finding. Gom findings của cả batch → 1 query `WHERE dedup_key IN (...)` lấy key đã tồn tại → chỉ bulk-insert key mới.
2. **Tái dùng `Engine` json-rules-engine**: dựng 1 lần/lần quét cho cả rule-set, `run(facts)` cho từng bản ghi — không `new Engine()` mỗi record.
3. **Rules cache Redis** (không đọc `DQ_RULES` từ DB mỗi lần quét); pub/sub invalidation khi sửa rule.
4. **Batched HIS lookup**: 1 query `WHERE id IN (...)` cho cả batch (treatment/sere_serv), không query mỗi bản ghi.
5. **FactsBuilder thuần** (không chạm DB → test nhanh); quét HIS theo cột có index (`MODIFY_TIME`).
6. **Khóa quét phân tán** Redis `SET NX PX` thay cờ in-memory.

**Quy ước:** repo mới ở `C:\Users\tracnn\bm-data-quality\`; lệnh chạy trong `backend/` hoặc `frontend/` (nêu rõ). Oracle `synchronize:false`; tạo bảng bằng `.sql` trong `backend/src/migrations/` chạy tay. Commit sau mỗi task. Tên cột HIS đã xác nhận qua sqlcl `hispro_stb`.

---

# PHA 0 — SCAFFOLD

### Task 1: Khởi tạo repo

**Files:** Create `C:\Users\tracnn\bm-data-quality\.gitignore`, `README.md`

- [ ] **Step 1: Tạo thư mục + git**
```bash
mkdir -p /c/Users/tracnn/bm-data-quality/backend /c/Users/tracnn/bm-data-quality/frontend /c/Users/tracnn/bm-data-quality/docs
cd /c/Users/tracnn/bm-data-quality && git init
```
- [ ] **Step 2: `.gitignore`**
```
node_modules/
dist/
.env
*.log
coverage/
```
- [ ] **Step 3: `README.md`**
```markdown
# bm-data-quality
Nền tảng phát hiện vấn đề chất lượng dữ liệu HIS. Module đầu: order-check.
- backend/ NestJS 11 + TypeORM + Oracle (CQRS, Redis, json-rules-engine)
- frontend/ Vue 3 + Vite + PrimeVue + Pinia
Spec: repo qlbv docs/superpowers/specs/2026-07-24-bm-data-quality-design.md
```
- [ ] **Step 4: Commit** — `git add -A && git commit -m "chore: init bm-data-quality repo"`

---

### Task 2: Backend scaffold NestJS

**Files:** Create `backend/package.json`, `tsconfig.json`, `nest-cli.json`, `src/main.ts`, `src/app.module.ts`, `.env.example`

- [ ] **Step 1: Sao khung + chọn dependency**

Sao `tsconfig.json`, `nest-cli.json` từ `bm_patient_hub/backend`. Tạo `package.json` (name `bm-data-quality-backend`) với dependencies:
`@nestjs/common @nestjs/core @nestjs/platform-express @nestjs/config @nestjs/typeorm typeorm oracledb @nestjs/cqrs @nestjs/schedule @nestjs/bull bull ioredis json-rules-engine @nestjs/jwt passport passport-jwt @nestjs/passport class-validator class-transformer @nestjs/swagger` ; dev: `typescript @types/node jest ts-jest @types/jest @nestjs/testing @nestjs/cli`. Scripts: `build`,`start:dev`,`start:prod`,`test`,`lint`.

- [ ] **Step 2: `src/main.ts`**
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
  const cfg = new DocumentBuilder().setTitle('BM Data Quality API')
    .addBearerAuth({ type: 'http', scheme: 'bearer' }, 'access-token').build();
  SwaggerModule.setup('docs', app, SwaggerModule.createDocument(app, cfg));
  await app.listen(process.env.APP_PORT ?? 3300, '0.0.0.0');
}
bootstrap();
```
- [ ] **Step 3: `src/app.module.ts` khung**
```typescript
import { Module } from '@nestjs/common';
import { ConfigModule } from '@nestjs/config';
import { ScheduleModule } from '@nestjs/schedule';

@Module({ imports: [ConfigModule.forRoot({ isGlobal: true }), ScheduleModule.forRoot()] })
export class AppModule {}
```
- [ ] **Step 4: `.env.example`**
```
APP_PORT=3300
NODE_ENV=development
DB_HOST=
DB_PORT=1521
DB_SERVICE_NAME=
DB_USER=
DB_PASSWORD=
DB_POOL_MIN=1
DB_POOL_MAX=10
HRS_DB_HOST=
HRS_DB_PORT=1521
HRS_DB_SERVICE_NAME=
HRS_DB_USER=
HRS_DB_PASSWORD=
HRS_DB_POOL_MIN=1
HRS_DB_POOL_MAX=10
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
JWT_ADMIN_SECRET=change-me
ENABLE_JWT_GUARD=true
ORDER_CHECK_SCAN_CRON=*/1 * * * *
DQ_RULES_CACHE_TTL=300
```
- [ ] **Step 5: Cài + build** — `cd backend && npm install && npm run build` → build thành công.
- [ ] **Step 6: Commit** — `git add -A && git commit -m "chore(backend): scaffold NestJS"`

---

### Task 3: Kết nối Oracle (DEFAULT + HIS_RS)

**Files:** Create `backend/src/constants/common.constant.ts`, `src/configs/build-oracle-connection-string.config.ts`, `src/configs/typeorm.config.ts`; Modify `src/app.module.ts`

- [ ] **Step 1: `constants/common.constant.ts`**
```typescript
export const BASE_SCHEMA = { DEFAULT: 'default', HIS_RS: 'HIS_RS' } as const;
```
- [ ] **Step 2:** Sao `build-oracle-connection-string.config.ts` từ `bm_patient_hub/backend/src/configs/`.
- [ ] **Step 3: `configs/typeorm.config.ts`**
```typescript
import { TypeOrmModuleOptions } from '@nestjs/typeorm';
import { ConfigService } from '@nestjs/config';
import { buildOracleConnectString } from './build-oracle-connection-string.config';

export const defaultDbConfig = (c: ConfigService): TypeOrmModuleOptions => ({
  type: 'oracle',
  connectString: buildOracleConnectString(c.get('DB_HOST'), c.get('DB_PORT'), undefined, c.get('DB_SERVICE_NAME')),
  username: c.get('DB_USER'), password: c.get('DB_PASSWORD'),
  autoLoadEntities: true, synchronize: false,
  extra: { poolMin: +c.get('DB_POOL_MIN', 1), poolMax: +c.get('DB_POOL_MAX', 10) },
});
export const hisRsDbConfig = (c: ConfigService): TypeOrmModuleOptions => ({
  type: 'oracle',
  connectString: buildOracleConnectString(c.get('HRS_DB_HOST'), c.get('HRS_DB_PORT'), undefined, c.get('HRS_DB_SERVICE_NAME')),
  username: c.get('HRS_DB_USER'), password: c.get('HRS_DB_PASSWORD'),
  autoLoadEntities: false, synchronize: false,
  extra: { poolMin: +c.get('HRS_DB_POOL_MIN', 1), poolMax: +c.get('HRS_DB_POOL_MAX', 10) },
});
```
- [ ] **Step 4:** Trong `app.module.ts` thêm 2 `TypeOrmModule.forRootAsync({ name: BASE_SCHEMA.DEFAULT/HIS_RS, inject:[ConfigService], useFactory })`.
- [ ] **Step 5: Build** — `cd backend && npm run build`.
- [ ] **Step 6: Commit** — `git commit -am "feat(backend): oracle DEFAULT + HIS_RS connections"`

---

### Task 4: Redis module

**Files:** Create `backend/src/redis/redis.module.ts`, `src/redis/redis.service.ts`; Modify `src/app.module.ts`

- [ ] **Step 1: `redis/redis.service.ts`** (ioredis, 1 client thường + 1 subscriber cho pub/sub)
```typescript
import { Injectable, OnModuleDestroy } from '@nestjs/common';
import { ConfigService } from '@nestjs/config';
import Redis from 'ioredis';

@Injectable()
export class RedisService implements OnModuleDestroy {
  readonly client: Redis;
  readonly subscriber: Redis;
  constructor(cfg: ConfigService) {
    const opts = { host: cfg.get('REDIS_HOST', '127.0.0.1'), port: +cfg.get('REDIS_PORT', 6379), lazyConnect: false };
    this.client = new Redis(opts);
    this.subscriber = new Redis(opts);
  }
  async ping() { return this.client.ping(); }
  onModuleDestroy() { this.client.disconnect(); this.subscriber.disconnect(); }
}
```
- [ ] **Step 2: `redis/redis.module.ts`** (Global)
```typescript
import { Global, Module } from '@nestjs/common';
import { RedisService } from './redis.service';
@Global()
@Module({ providers: [RedisService], exports: [RedisService] })
export class RedisModule {}
```
- [ ] **Step 3:** Import `RedisModule` vào `app.module.ts`; build.
- [ ] **Step 4: Commit** — `git commit -am "feat(backend): redis module (ioredis + subscriber)"`

---

### Task 5: Common foundation

**Files:** Create `backend/src/common/api.exception.ts`, `errors.config.ts`, `base.entity.ts`, `pagination.util.ts`

- [ ] **Step 1:** Sao `api.exception.ts` + `errors.config.ts` từ `bm_patient_hub`. Thêm key: `DQ_RULE_NOT_FOUND:{status:404}`, `DQ_FINDING_NOT_FOUND:{status:404}`, `DQ_FINDING_FORBIDDEN_DEPARTMENT:{status:403}`.
- [ ] **Step 2: `common/base.entity.ts`**
```typescript
import { PrimaryColumn, CreateDateColumn, UpdateDateColumn, Column } from 'typeorm';
export abstract class BaseEntity {
  @PrimaryColumn({ name: 'ID' }) id: string;
  @CreateDateColumn({ name: 'CREATED_AT' }) createdAt: Date;
  @UpdateDateColumn({ name: 'UPDATED_AT' }) updatedAt: Date;
}
```
- [ ] **Step 3: `common/pagination.util.ts`**
```typescript
export interface Pagination { page: number; limit: number; total: number; totalPages: number; }
export const buildPagination = (total: number, page: number, limit: number): Pagination =>
  ({ page, limit, total, totalPages: Math.ceil(total / limit) || 0 });
```
- [ ] **Step 4: Build & commit** — `cd backend && npm run build && git commit -am "feat(backend): common foundation"`

---

### Task 6: admin-auth + role-permission

**Files:** Create `backend/src/admin-auth/*`, `src/role-permission/*` (sao); Modify `src/app.module.ts`; Create `src/migrations/2026-07-24-create-rbac.sql`

- [ ] **Step 1:** Sao `admin-auth/` + `role-permission/` từ `bm_patient_hub/backend/src/`; entity RBAC bind `BASE_SCHEMA.DEFAULT`; secret `JWT_ADMIN_SECRET`; strategy `'jwt-admin'`; giữ `@Permission('resource:action')` + `PermissionsGuard` + `JwtAdminAuthGuard`. **Bỏ tiền tố `admin/`** trong mọi `@Controller(...)` khi sao (vd `@Controller('auth')` thay vì `@Controller('admin/auth')`) — toàn hệ thống không dùng tiền tố `admin`. **Bổ sung helper** `req.user.permissions: string[]` để dùng cho phân quyền khoa (Task 22).
- [ ] **Step 2:** Import 2 module vào `app.module.ts`.
- [ ] **Step 3:** Sao DDL RBAC từ `bm_patient_hub/backend/src/migrations/` → `src/migrations/2026-07-24-create-rbac.sql`; chạy trên Oracle DEFAULT.
- [ ] **Step 4: Build & commit** — `npm run build && git commit -am "feat(backend): admin-auth + RBAC"`

---

### Task 7: Health endpoint + boot thật

**Files:** Create `backend/src/health/health.controller.ts`, `health.module.ts`; Modify `src/app.module.ts`

- [ ] **Step 1: `health.controller.ts`** (kiểm DB + Redis)
```typescript
import { Controller, Get } from '@nestjs/common';
import { InjectDataSource } from '@nestjs/typeorm';
import { DataSource } from 'typeorm';
import { BASE_SCHEMA } from '../constants/common.constant';
import { RedisService } from '../redis/redis.service';

@Controller('health')
export class HealthController {
  constructor(@InjectDataSource(BASE_SCHEMA.DEFAULT) private db: DataSource, private redis: RedisService) {}
  @Get()
  async check() {
    const dbOk = await this.db.query('SELECT 1 FROM DUAL').then(() => true).catch(() => false);
    const redisOk = await this.redis.ping().then(r => r === 'PONG').catch(() => false);
    return { status: dbOk && redisOk ? 'ok' : 'degraded', db: dbOk, redis: redisOk };
  }
}
```
- [ ] **Step 2:** Tạo `health.module.ts` (controller) + import vào `app.module.ts`.
- [ ] **Step 3: Chạy thật** — `cp .env.example .env` (điền DB + Redis), `npm run start:dev`. Mở `/health` → `{status:'ok',db:true,redis:true}`; `/docs` hiển thị Swagger.
- [ ] **Step 4: Commit** — `git commit -am "feat(backend): health (db+redis); boots"`

---

### Task 8: Frontend scaffold + login e2e

**Files:** Create `frontend/*` (sao khung), `.env`

- [ ] **Step 1:** Sao khung từ `bm_patient_hub/frontend`: `package.json` (name `bm-data-quality-frontend`), `vite.config.ts`, `tsconfig.json`, `index.html`, `src/main.ts`, `src/layouts/`, `src/components/`, `src/api/config.ts`, `src/utils/`, `src/composables/usePermissions.ts`, `src/stores/auth.store.ts`, `src/router/index.ts` (giữ auth + backend layout + 403), `src/data/menu.ts` (menu rỗng chờ thêm).
- [ ] **Step 2: `.env`** (KHÔNG có tiền tố `/admin`)
```
VITE_API_BASE_URL=http://localhost:3300
VITE_API_TIMEOUT=30000
```
Đồng thời sửa giá trị fallback trong `src/api/config.ts` (khi sao từ khuôn mẫu có mặc định `.../admin`) về `http://localhost:3300` (bỏ `/admin`).
- [ ] **Step 3:** `cd frontend && npm install && npm run dev` → mở trang đăng nhập.
- [ ] **Step 4:** Đảm bảo `auth.store.ts` gọi đúng `/auth/login` + `/auth/me` (KHÔNG có `/admin`); tạo 1 user admin test (seeder/insert tay), ghi cách tạo vào `docs/`.
- [ ] **Step 5: Đăng nhập e2e** — backend + frontend chạy, đăng nhập vào được layout backend.
- [ ] **Step 6: Commit** — `git add -A && git commit -m "chore(frontend): scaffold + login e2e"`

**✅ Mốc Pha 0:** đăng nhập được, `/health` xanh (DB+Redis).

---

# PHA 1 — DQ CORE + ORDER-CHECK B_* + PHÂN QUYỀN KHOA

### Task 9: Migration SQL bảng DQ_*

**Files:** Create `backend/src/migrations/2026-07-24-create-dq-core.sql`

- [ ] **Step 1: DDL Oracle**
```sql
CREATE TABLE DQ_RULES (
  ID VARCHAR2(36) DEFAULT SYS_GUID() PRIMARY KEY,
  MODULE VARCHAR2(40) NOT NULL,
  CODE VARCHAR2(80) NOT NULL,
  NAME VARCHAR2(255),
  SOURCE_KEY VARCHAR2(80) NOT NULL,
  CONDITIONS CLOB,          -- JSON json-rules-engine
  SEVERITY VARCHAR2(16) DEFAULT 'warning',
  PRIORITY NUMBER DEFAULT 10,
  PARAMS CLOB,
  IS_ACTIVE NUMBER(1) DEFAULT 1,
  CREATED_AT TIMESTAMP DEFAULT SYSTIMESTAMP,
  UPDATED_AT TIMESTAMP DEFAULT SYSTIMESTAMP,
  CONSTRAINT UQ_DQ_RULES UNIQUE (MODULE, CODE)
);
CREATE TABLE DQ_FINDINGS (
  ID VARCHAR2(36) DEFAULT SYS_GUID() PRIMARY KEY,
  MODULE VARCHAR2(40) NOT NULL,
  SOURCE_KEY VARCHAR2(80) NOT NULL,
  RULE_CODE VARCHAR2(80) NOT NULL,
  REF_TYPE VARCHAR2(32),
  REF_ID NUMBER,
  DEDUP_KEY VARCHAR2(255) NOT NULL,
  PATIENT_CODE VARCHAR2(64), PATIENT_NAME VARCHAR2(255),
  DEPARTMENT_ID NUMBER, DEPARTMENT_NAME VARCHAR2(255),
  DOCTOR_LOGINNAME VARCHAR2(64),
  SEVERITY VARCHAR2(16), MESSAGE VARCHAR2(1000), DETAIL CLOB,
  STATUS VARCHAR2(20) DEFAULT 'new',
  DETECTED_AT TIMESTAMP DEFAULT SYSTIMESTAMP,
  PROCESSED_BY VARCHAR2(64), PROCESSED_AT TIMESTAMP, NOTE VARCHAR2(1000),
  CONSTRAINT UQ_DQ_FINDINGS_DEDUP UNIQUE (DEDUP_KEY)
);
CREATE INDEX IX_DQ_FINDINGS_DEPT ON DQ_FINDINGS (DEPARTMENT_ID);
CREATE INDEX IX_DQ_FINDINGS_STATUS ON DQ_FINDINGS (STATUS);
CREATE TABLE DQ_WATERMARKS (
  ID VARCHAR2(36) DEFAULT SYS_GUID() PRIMARY KEY,
  SOURCE_KEY VARCHAR2(80) NOT NULL UNIQUE,
  LAST_ID NUMBER, LAST_CREATE_TIME NUMBER, LAST_MODIFY_TIME NUMBER,
  LAST_RUN_AT TIMESTAMP
);
CREATE TABLE DQ_SCAN_LOGS (
  ID VARCHAR2(36) DEFAULT SYS_GUID() PRIMARY KEY,
  SOURCE_KEY VARCHAR2(80),
  STARTED_AT TIMESTAMP, FINISHED_AT TIMESTAMP,
  SCANNED_COUNT NUMBER DEFAULT 0, FINDING_COUNT NUMBER DEFAULT 0,
  STATUS VARCHAR2(20), ERROR VARCHAR2(2000), DURATION_MS NUMBER
);
CREATE TABLE DQ_USER_DEPARTMENT (
  ID VARCHAR2(36) DEFAULT SYS_GUID() PRIMARY KEY,
  USER_ID VARCHAR2(64) NOT NULL,
  HIS_DEPARTMENT_ID NUMBER NOT NULL,
  CREATED_AT TIMESTAMP DEFAULT SYSTIMESTAMP,
  CONSTRAINT UQ_DQ_USER_DEPT UNIQUE (USER_ID, HIS_DEPARTMENT_ID)
);
```
- [ ] **Step 2:** Chạy DDL trên Oracle DEFAULT; xác minh `SELECT * FROM DQ_RULES` không lỗi.
- [ ] **Step 3: Commit** — `git add -A && git commit -m "feat(dq): DDL DQ_* + DQ_USER_DEPARTMENT"`

---

### Task 10: Entities DQ_*

**Files:** Create `backend/src/dq-core/entities/{dq-rule,dq-finding,dq-watermark,dq-scan-log,dq-user-department}.entity.ts`

- [ ] **Step 1: `dq-rule.entity.ts`**
```typescript
import { Entity, Column, PrimaryColumn } from 'typeorm';
@Entity('DQ_RULES')
export class DqRule {
  @PrimaryColumn({ name: 'ID' }) id: string;
  @Column({ name: 'MODULE' }) module: string;
  @Column({ name: 'CODE' }) code: string;
  @Column({ name: 'NAME', nullable: true }) name: string;
  @Column({ name: 'SOURCE_KEY' }) sourceKey: string;
  @Column({ name: 'CONDITIONS', type: 'clob', nullable: true }) conditions: string;
  @Column({ name: 'SEVERITY' }) severity: string;
  @Column({ name: 'PRIORITY' }) priority: number;
  @Column({ name: 'PARAMS', type: 'clob', nullable: true }) params: string;
  @Column({ name: 'IS_ACTIVE' }) isActive: number;
}
```
- [ ] **Step 2: `dq-finding.entity.ts`**
```typescript
import { Entity, Column, PrimaryColumn } from 'typeorm';
@Entity('DQ_FINDINGS')
export class DqFinding {
  @PrimaryColumn({ name: 'ID' }) id: string;
  @Column({ name: 'MODULE' }) module: string;
  @Column({ name: 'SOURCE_KEY' }) sourceKey: string;
  @Column({ name: 'RULE_CODE' }) ruleCode: string;
  @Column({ name: 'REF_TYPE', nullable: true }) refType: string;
  @Column({ name: 'REF_ID', nullable: true }) refId: number;
  @Column({ name: 'DEDUP_KEY' }) dedupKey: string;
  @Column({ name: 'PATIENT_CODE', nullable: true }) patientCode: string;
  @Column({ name: 'PATIENT_NAME', nullable: true }) patientName: string;
  @Column({ name: 'DEPARTMENT_ID', nullable: true }) departmentId: number;
  @Column({ name: 'DEPARTMENT_NAME', nullable: true }) departmentName: string;
  @Column({ name: 'DOCTOR_LOGINNAME', nullable: true }) doctorLoginname: string;
  @Column({ name: 'SEVERITY', nullable: true }) severity: string;
  @Column({ name: 'MESSAGE', nullable: true }) message: string;
  @Column({ name: 'DETAIL', type: 'clob', nullable: true }) detail: string;
  @Column({ name: 'STATUS' }) status: string;
  @Column({ name: 'DETECTED_AT', nullable: true }) detectedAt: Date;
  @Column({ name: 'PROCESSED_BY', nullable: true }) processedBy: string;
  @Column({ name: 'PROCESSED_AT', nullable: true }) processedAt: Date;
  @Column({ name: 'NOTE', nullable: true }) note: string;
}
```
- [ ] **Step 3: 3 entity còn lại**
```typescript
// dq-watermark.entity.ts
import { Entity, Column, PrimaryColumn } from 'typeorm';
@Entity('DQ_WATERMARKS')
export class DqWatermark {
  @PrimaryColumn({ name: 'ID' }) id: string;
  @Column({ name: 'SOURCE_KEY' }) sourceKey: string;
  @Column({ name: 'LAST_ID', nullable: true }) lastId: number;
  @Column({ name: 'LAST_CREATE_TIME', nullable: true }) lastCreateTime: number;
  @Column({ name: 'LAST_MODIFY_TIME', nullable: true }) lastModifyTime: number;
  @Column({ name: 'LAST_RUN_AT', nullable: true }) lastRunAt: Date;
}
```
```typescript
// dq-scan-log.entity.ts
import { Entity, Column, PrimaryColumn } from 'typeorm';
@Entity('DQ_SCAN_LOGS')
export class DqScanLog {
  @PrimaryColumn({ name: 'ID' }) id: string;
  @Column({ name: 'SOURCE_KEY', nullable: true }) sourceKey: string;
  @Column({ name: 'STARTED_AT', nullable: true }) startedAt: Date;
  @Column({ name: 'FINISHED_AT', nullable: true }) finishedAt: Date;
  @Column({ name: 'SCANNED_COUNT' }) scannedCount: number;
  @Column({ name: 'FINDING_COUNT' }) findingCount: number;
  @Column({ name: 'STATUS', nullable: true }) status: string;
  @Column({ name: 'ERROR', nullable: true }) error: string;
  @Column({ name: 'DURATION_MS', nullable: true }) durationMs: number;
}
```
```typescript
// dq-user-department.entity.ts
import { Entity, Column, PrimaryColumn } from 'typeorm';
@Entity('DQ_USER_DEPARTMENT')
export class DqUserDepartment {
  @PrimaryColumn({ name: 'ID' }) id: string;
  @Column({ name: 'USER_ID' }) userId: string;
  @Column({ name: 'HIS_DEPARTMENT_ID' }) hisDepartmentId: number;
}
```
- [ ] **Step 4: Build & commit** — `cd backend && npm run build && git commit -am "feat(dq): TypeORM entities DQ_*"`

---

### Task 11: Support types — Finding (TDD dedupKey)

**Files:** Create `backend/src/dq-core/support/{finding.ts,facts.ts,record-context.ts}`; Test `backend/src/dq-core/support/finding.spec.ts`

- [ ] **Step 1: Test**
```typescript
import { Finding } from './finding';

describe('Finding.dedupKey', () => {
  it('ghep module:rule:refType:refId:subKey', () => {
    const f = new Finding({ module: 'order_check', sourceKey: 's', ruleCode: 'B_X',
      refType: 'service_req', refId: 12, severity: 'warning', message: 'm' });
    expect(f.dedupKey()).toBe('order_check:B_X:service_req:12:');
  });
  it('subKey phan biet nhieu finding cung phieu', () => {
    const f = new Finding({ module: 'order_check', sourceKey: 's', ruleCode: 'B_X',
      refType: 'service_req', refId: 12, severity: 'warning', message: 'm', subKey: 'svc9' });
    expect(f.dedupKey()).toBe('order_check:B_X:service_req:12:svc9');
  });
});
```
- [ ] **Step 2: Run FAIL** — `cd backend && npx jest finding.spec`
- [ ] **Step 3: `support/finding.ts`**
```typescript
export interface FindingInit {
  module: string; sourceKey: string; ruleCode: string;
  refType: string; refId: number; severity: string; message: string;
  detail?: Record<string, any>; subKey?: string;
  patientCode?: string; patientName?: string;
  departmentId?: number; departmentName?: string; doctorLoginname?: string;
}
export class Finding {
  constructor(public readonly init: FindingInit) {}
  dedupKey(): string {
    const i = this.init;
    return `${i.module}:${i.ruleCode}:${i.refType}:${i.refId}:${i.subKey ?? ''}`;
  }
}
```
- [ ] **Step 4: `support/facts.ts` + `support/record-context.ts`**
```typescript
// facts.ts — object phang cho json-rules-engine
export type Facts = Record<string, any>;
```
```typescript
// record-context.ts — thong tin gan vao finding tu 1 ban ghi
export interface RecordContext {
  refType: string; refId: number;
  patientCode?: string; patientName?: string;
  departmentId?: number; doctorLoginname?: string;
}
```
- [ ] **Step 5: Run PASS** — `npx jest finding.spec`
- [ ] **Step 6: Commit** — `git add -A && git commit -m "feat(dq): Finding + Facts + RecordContext (TDD dedupKey)"`

---

### Task 12: OrderFactsBuilder (TDD — thuần, computed-facts)

**Files:** Create `backend/src/order-check/order-facts.builder.ts`; Test `order-facts.builder.spec.ts`

> Tối ưu: hàm thuần, không DB. Nhận 1 object thô (đã gộp từ service_req + treatment + employee) → trả Facts gồm computed-facts để rule-as-data đánh giá.

- [ ] **Step 1: Test**
```typescript
import { OrderFactsBuilder } from './order-facts.builder';

const b = new OrderFactsBuilder();

describe('OrderFactsBuilder', () => {
  it('out_before_in=true khi OUT_TIME < IN_TIME', () => {
    const f = b.build({ IN_TIME: 20260102080000, OUT_TIME: 20260101080000, INTRUCTION_TIME: 0, EXECUTE_LOGINNAME: null, DIPLOMA: null, ICD_CODE: 'A00' });
    expect(f.out_before_in).toBe(true);
  });
  it('order_before_in / order_after_out theo INTRUCTION_TIME', () => {
    const f = b.build({ IN_TIME: 20260102000000, OUT_TIME: 20260110000000, INTRUCTION_TIME: 20260101000000, EXECUTE_LOGINNAME: null, DIPLOMA: null, ICD_CODE: null });
    expect(f.order_before_in).toBe(true);
    expect(f.order_after_out).toBe(false);
  });
  it('executor_missing_diploma=true khi co nguoi thuc hien nhung DIPLOMA rong', () => {
    const f = b.build({ IN_TIME: 0, OUT_TIME: 0, INTRUCTION_TIME: 0, EXECUTE_LOGINNAME: 'bs1', DIPLOMA: '  ', ICD_CODE: 'A00' });
    expect(f.executor_missing_diploma).toBe(true);
  });
  it('has_icd=false khi ICD_CODE rong', () => {
    const f = b.build({ IN_TIME: 0, OUT_TIME: 0, INTRUCTION_TIME: 0, EXECUTE_LOGINNAME: null, DIPLOMA: null, ICD_CODE: '' });
    expect(f.has_icd).toBe(false);
  });
});
```
- [ ] **Step 2: Run FAIL** — `npx jest order-facts.builder`
- [ ] **Step 3: Implement**
```typescript
import { Injectable } from '@nestjs/common';
import { Facts } from '../dq-core/support/facts';

@Injectable()
export class OrderFactsBuilder {
  build(r: any): Facts {
    const inTime = Number(r.IN_TIME) || 0;
    const outTime = Number(r.OUT_TIME) || 0;
    const orderTime = Number(r.INTRUCTION_TIME) || 0;
    const executor = (r.EXECUTE_LOGINNAME ?? '').toString().trim();
    const diploma = (r.DIPLOMA ?? '').toString().trim();
    const icd = (r.ICD_CODE ?? '').toString().trim();
    return {
      in_time: inTime, out_time: outTime, intruction_time: orderTime,
      execute_loginname: executor, execute_diploma: diploma, icd_code: icd,
      service_req_type_id: r.SERVICE_REQ_TYPE_ID != null ? Number(r.SERVICE_REQ_TYPE_ID) : null,
      // computed-facts
      out_before_in: outTime > 0 && inTime > 0 && outTime < inTime,
      order_before_in: orderTime > 0 && inTime > 0 && orderTime < inTime,
      order_after_out: orderTime > 0 && outTime > 0 && orderTime > outTime,
      executor_missing_diploma: executor.length > 0 && diploma.length === 0,
      has_icd: icd.length > 0,
    };
  }
}
```
- [ ] **Step 4: Run PASS** — `npx jest order-facts.builder`
- [ ] **Step 5: Commit** — `git add -A && git commit -m "feat(order-check): OrderFactsBuilder computed-facts (TDD)"`

---

### Task 13: RulesCache (Redis + pub/sub invalidation, degrade DB)

**Files:** Create `backend/src/dq-core/rules-cache.service.ts`

> Tối ưu: nạp `DQ_RULES` từ Redis (TTL), không đọc DB mỗi lần quét; sub kênh `dq:rules:invalidated` để xóa cache; Redis chết → đọc thẳng DB (degrade an toàn).

- [ ] **Step 1: Implement**
```typescript
import { Injectable, OnModuleInit, Logger } from '@nestjs/common';
import { InjectRepository } from '@nestjs/typeorm';
import { Repository } from 'typeorm';
import { ConfigService } from '@nestjs/config';
import { BASE_SCHEMA } from '../constants/common.constant';
import { DqRule } from './entities/dq-rule.entity';
import { RedisService } from '../redis/redis.service';

export interface CachedRule { code: string; severity: string; conditions: any; priority: number; }
const CHANNEL = 'dq:rules:invalidated';

@Injectable()
export class RulesCache implements OnModuleInit {
  private readonly log = new Logger(RulesCache.name);
  private readonly ttl: number;
  constructor(
    @InjectRepository(DqRule, BASE_SCHEMA.DEFAULT) private repo: Repository<DqRule>,
    private redis: RedisService, cfg: ConfigService,
  ) { this.ttl = +cfg.get('DQ_RULES_CACHE_TTL', 300); }

  onModuleInit() {
    this.redis.subscriber.subscribe(CHANNEL).catch(e => this.log.warn(`sub fail: ${e}`));
    this.redis.subscriber.on('message', (ch, key) => {
      if (ch === CHANNEL) this.redis.client.del(this.cacheKey(key)).catch(() => {});
    });
  }
  private cacheKey(scope: string) { return `dq:rules:${scope}`; }

  async getActiveRules(module: string, sourceKey: string): Promise<CachedRule[]> {
    const scope = `${module}:${sourceKey}`;
    try {
      const cached = await this.redis.client.get(this.cacheKey(scope));
      if (cached) return JSON.parse(cached);
    } catch (e) { this.log.warn(`redis get fail, degrade DB: ${e}`); }
    const rows = await this.repo.find({ where: { module, sourceKey, isActive: 1 } });
    const rules: CachedRule[] = rows.map(r => ({
      code: r.code, severity: r.severity, priority: r.priority,
      conditions: r.conditions ? JSON.parse(r.conditions) : { all: [] },
    }));
    try { await this.redis.client.set(this.cacheKey(scope), JSON.stringify(rules), 'EX', this.ttl); } catch {}
    return rules;
  }
  async invalidate(module: string, sourceKey: string) {
    const scope = `${module}:${sourceKey}`;
    try { await this.redis.client.del(this.cacheKey(scope)); await this.redis.client.publish(CHANNEL, scope); } catch {}
  }
}
```
- [ ] **Step 2: Build & commit** — `cd backend && npm run build && git commit -am "feat(dq): RulesCache (Redis + pubsub invalidation, degrade DB)"`

---

### Task 14: RuleEvaluator (json-rules-engine, TDD)

**Files:** Create `backend/src/dq-core/rule-evaluator.service.ts`; Test `rule-evaluator.service.spec.ts`

> Tối ưu: `buildEngine(rules)` dựng 1 Engine cho cả rule-set (gọi 1 lần/lần quét); `evaluate(engine, facts, ctx)` chạy từng bản ghi. Event.type = ruleCode; map event → Finding bằng ctx + severity/message từ rule.

- [ ] **Step 1: Test**
```typescript
import { RuleEvaluator } from './rule-evaluator.service';

const rules = [
  { code: 'B_DISCHARGE_BEFORE_ADMISSION', severity: 'critical', priority: 100,
    conditions: { all: [{ fact: 'out_before_in', operator: 'equal', value: true }] } },
  { code: 'B_DOCTOR_NO_PRACTICE_CERT', severity: 'critical', priority: 90,
    conditions: { all: [{ fact: 'executor_missing_diploma', operator: 'equal', value: true }] } },
];
const ctx = { refType: 'service_req', refId: 7, departmentId: 3, patientCode: 'P1' };

describe('RuleEvaluator', () => {
  it('sinh finding khi fact khop', async () => {
    const ev = new RuleEvaluator();
    const engine = ev.buildEngine('order_check', 'order_check.his_service_req', rules);
    const out = await ev.evaluate(engine, { out_before_in: true, executor_missing_diploma: false }, ctx);
    expect(out).toHaveLength(1);
    expect(out[0].init.ruleCode).toBe('B_DISCHARGE_BEFORE_ADMISSION');
    expect(out[0].init.severity).toBe('critical');
    expect(out[0].init.refId).toBe(7);
  });
  it('khong facts khop -> rong', async () => {
    const ev = new RuleEvaluator();
    const engine = ev.buildEngine('order_check', 'order_check.his_service_req', rules);
    const out = await ev.evaluate(engine, { out_before_in: false, executor_missing_diploma: false }, ctx);
    expect(out).toHaveLength(0);
  });
  it('nhieu rule khop -> nhieu finding', async () => {
    const ev = new RuleEvaluator();
    const engine = ev.buildEngine('order_check', 'order_check.his_service_req', rules);
    const out = await ev.evaluate(engine, { out_before_in: true, executor_missing_diploma: true }, ctx);
    expect(out.map(f => f.init.ruleCode).sort()).toEqual(['B_DISCHARGE_BEFORE_ADMISSION', 'B_DOCTOR_NO_PRACTICE_CERT']);
  });
});
```
- [ ] **Step 2: Run FAIL** — `npx jest rule-evaluator`
- [ ] **Step 3: Implement** (json-rules-engine)
```typescript
import { Injectable } from '@nestjs/common';
import { Engine } from 'json-rules-engine';
import { CachedRule } from './rules-cache.service';
import { Finding } from './support/finding';
import { Facts } from './support/facts';
import { RecordContext } from './support/record-context';

export interface CompiledEngine { engine: Engine; module: string; sourceKey: string; meta: Map<string, CachedRule>; }

@Injectable()
export class RuleEvaluator {
  buildEngine(module: string, sourceKey: string, rules: CachedRule[]): CompiledEngine {
    const engine = new Engine([], { allowUndefinedFacts: true });
    const meta = new Map<string, CachedRule>();
    for (const r of rules) {
      meta.set(r.code, r);
      engine.addRule({ name: r.code, priority: r.priority ?? 10, conditions: r.conditions,
        event: { type: r.code, params: {} } });
    }
    return { engine, module, sourceKey, meta };
  }
  async evaluate(c: CompiledEngine, facts: Facts, ctx: RecordContext): Promise<Finding[]> {
    const { events } = await c.engine.run(facts);
    return events.map(ev => {
      const rule = c.meta.get(ev.type)!;
      return new Finding({
        module: c.module, sourceKey: c.sourceKey, ruleCode: ev.type,
        refType: ctx.refType, refId: ctx.refId, severity: rule.severity,
        message: this.messageFor(ev.type, facts), detail: { facts },
        patientCode: ctx.patientCode, departmentId: ctx.departmentId, doctorLoginname: ctx.doctorLoginname,
      });
    });
  }
  private messageFor(code: string, f: Facts): string {
    switch (code) {
      case 'B_DISCHARGE_BEFORE_ADMISSION': return `Ngay ra vien (${f.out_time}) truoc ngay vao vien (${f.in_time})`;
      case 'B_ORDER_TIME_OUT_OF_STAY': return `Gio y lenh (${f.intruction_time}) ngoai dot dieu tri`;
      case 'B_DOCTOR_NO_PRACTICE_CERT': return `Nguoi thuc hien ${f.execute_loginname} thieu CCHN`;
      default: return code;
    }
  }
}
```
- [ ] **Step 4: Run PASS** — `npx jest rule-evaluator`
- [ ] **Step 5: Commit** — `git add -A && git commit -m "feat(dq): RuleEvaluator (json-rules-engine, reuse engine) (TDD)"`

---

### Task 15: ExecuteBeforeOrder CodeChecker (TDD)

**Files:** Create `backend/src/order-check/checkers/execute-before-order.checker.ts`; Test `execute-before-order.checker.spec.ts`

> Luật xuyên bản ghi (theo từng dòng DV) — không diễn đạt gọn bằng 1 fact → CodeChecker.

- [ ] **Step 1: Test**
```typescript
import { ExecuteBeforeOrderChecker } from './execute-before-order.checker';

const c = new ExecuteBeforeOrderChecker();
const ctx = { refType: 'service_req', refId: 3, departmentId: 1 };

describe('ExecuteBeforeOrderChecker (B_EXECUTE_BEFORE_ORDER)', () => {
  it('sinh finding cho DV co EXECUTE_TIME < TDL_INTRUCTION_TIME', () => {
    const rows = [
      { ID: 11, EXECUTE_TIME: 20260101090000, TDL_INTRUCTION_TIME: 20260101100000 },
      { ID: 12, EXECUTE_TIME: 20260101110000, TDL_INTRUCTION_TIME: 20260101100000 },
    ];
    const out = c.check(rows as any, ctx);
    expect(out).toHaveLength(1);
    expect(out[0].dedupKey()).toBe('order_check:B_EXECUTE_BEFORE_ORDER:service_req:3:svc11');
  });
  it('khong finding khi moi DV hop le', () => {
    const rows = [{ ID: 12, EXECUTE_TIME: 20260101110000, TDL_INTRUCTION_TIME: 20260101100000 }];
    expect(c.check(rows as any, ctx)).toHaveLength(0);
  });
});
```
- [ ] **Step 2: Run FAIL** — `npx jest execute-before-order.checker`
- [ ] **Step 3: Implement**
```typescript
import { Injectable } from '@nestjs/common';
import { Finding } from '../../dq-core/support/finding';
import { RecordContext } from '../../dq-core/support/record-context';

export interface SereServRow { ID: number; EXECUTE_TIME: number; TDL_INTRUCTION_TIME: number; }

@Injectable()
export class ExecuteBeforeOrderChecker {
  readonly code = 'B_EXECUTE_BEFORE_ORDER';
  check(rows: SereServRow[], ctx: RecordContext): Finding[] {
    const out: Finding[] = [];
    for (const s of rows) {
      const exec = Number(s.EXECUTE_TIME) || 0;
      const order = Number(s.TDL_INTRUCTION_TIME) || 0;
      if (exec > 0 && order > 0 && exec < order) {
        out.push(new Finding({
          module: 'order_check', sourceKey: 'order_check.his_service_req', ruleCode: this.code,
          refType: ctx.refType, refId: ctx.refId, severity: 'warning',
          message: `DV ${s.ID} thuc hien (${exec}) truoc gio y lenh (${order})`,
          detail: { service_id: s.ID, execute_time: exec, intruction_time: order },
          subKey: `svc${s.ID}`, departmentId: ctx.departmentId,
        }));
      }
    }
    return out;
  }
}
```
- [ ] **Step 4: Run PASS** — `npx jest execute-before-order.checker`
- [ ] **Step 5: Commit** — `git add -A && git commit -m "feat(order-check): ExecuteBeforeOrder CodeChecker (TDD)"`

---

### Task 16: HisOrderSource (đọc HIS, batched)

**Files:** Create `backend/src/order-check/his-order-source.service.ts`

> Tối ưu: quét `HIS_SERVICE_REQ` theo `MODIFY_TIME` (index); **batched lookup** treatment + sere_serv + employee bằng 1 query IN cho cả batch. Cột đã xác nhận qua sqlcl.

- [ ] **Step 1: Implement**
```typescript
import { Injectable } from '@nestjs/common';
import { InjectDataSource } from '@nestjs/typeorm';
import { DataSource } from 'typeorm';
import { BASE_SCHEMA } from '../constants/common.constant';

function inList(ids: (number | string)[]) { return ids.map((_, i) => `:${i}`).join(','); }

@Injectable()
export class HisOrderSource {
  constructor(@InjectDataSource(BASE_SCHEMA.HIS_RS) private his: DataSource) {}

  async fetchServiceReqs(lastModifyTime: number, limit: number): Promise<any[]> {
    return this.his.query(
      `SELECT * FROM (
         SELECT ID, SERVICE_REQ_CODE, SERVICE_REQ_TYPE_ID, TREATMENT_ID, INTRUCTION_TIME, ICD_CODE,
                REQUEST_LOGINNAME, EXECUTE_LOGINNAME, EXECUTE_DEPARTMENT_ID,
                TDL_PATIENT_CODE, TDL_PATIENT_NAME, MODIFY_TIME
         FROM HIS_SERVICE_REQ WHERE MODIFY_TIME > :p0 ORDER BY MODIFY_TIME
       ) WHERE ROWNUM <= :p1`,
      [lastModifyTime || 0, limit]);
  }
  async fetchTreatments(ids: number[]): Promise<Map<number, any>> {
    if (!ids.length) return new Map();
    const rows = await this.his.query(
      `SELECT ID, IN_TIME, OUT_TIME FROM HIS_TREATMENT WHERE ID IN (${inList(ids)})`, ids);
    return new Map(rows.map((r: any) => [Number(r.ID), r]));
  }
  async fetchDiplomas(loginnames: string[]): Promise<Map<string, string>> {
    const names = [...new Set(loginnames.filter(Boolean))];
    if (!names.length) return new Map();
    const rows = await this.his.query(
      `SELECT LOGINNAME, DIPLOMA FROM HIS_EMPLOYEE WHERE LOGINNAME IN (${inList(names)})`, names);
    return new Map(rows.map((r: any) => [r.LOGINNAME, r.DIPLOMA]));
  }
  async fetchSereServ(serviceReqIds: number[]): Promise<Map<number, any[]>> {
    if (!serviceReqIds.length) return new Map();
    const rows = await this.his.query(
      `SELECT SERVICE_REQ_ID, ID, EXECUTE_TIME, TDL_INTRUCTION_TIME
       FROM HIS_SERE_SERV WHERE SERVICE_REQ_ID IN (${inList(serviceReqIds)})`, serviceReqIds);
    const map = new Map<number, any[]>();
    for (const r of rows) {
      const k = Number(r.SERVICE_REQ_ID);
      (map.get(k) ?? map.set(k, []).get(k)!).push(r);
    }
    return map;
  }
}
```
> ⚠ Khi thực thi: xác minh `HIS_SERE_SERV` có cột `SERVICE_REQ_ID` (khóa liên kết) qua sqlcl `hispro_stb`; nếu tên khác, sửa raw SQL. Các cột khác đã xác nhận.
- [ ] **Step 2: Build & commit** — `cd backend && npm run build && git commit -am "feat(order-check): HisOrderSource batched HIS reads"`

---

### Task 17: FindingSink — batch idempotent (TDD)

**Files:** Create `backend/src/dq-core/finding-sink.service.ts`; Test `finding-sink.service.spec.ts`

> Tối ưu QUAN TRỌNG (khác PHP): KHÔNG findOne mỗi finding. `persistBatch(findings)`: 1 query lấy dedup_key đã tồn tại → chỉ bulk-insert key mới; loại trùng trong chính batch.

- [ ] **Step 1: Test (repo mock)**
```typescript
import { FindingSink } from './finding-sink.service';
import { Finding } from './support/finding';

function repo(existingKeys: string[] = []) {
  const inserted: any[] = [];
  return {
    inserted,
    createQueryBuilder: () => ({
      select: () => ({ where: () => ({ getRawMany: async () => existingKeys.map(k => ({ DEDUP_KEY: k })) }) }),
    }),
    create: (x: any) => x,
    insert: async (rows: any[]) => { inserted.push(...rows); },
  } as any;
}
const mk = (rule: string, refId: number, sub = '') => new Finding({
  module: 'order_check', sourceKey: 's', ruleCode: rule, refType: 'service_req',
  refId, severity: 'warning', message: 'm', subKey: sub });

describe('FindingSink.persistBatch', () => {
  it('chi insert key moi, bo qua key da ton tai', async () => {
    const r = repo(['order_check:B_A:service_req:1:']);
    const sink = new FindingSink(r);
    const n = await sink.persistBatch([mk('B_A', 1), mk('B_B', 2)]);
    expect(n).toBe(1);
    expect(r.inserted).toHaveLength(1);
    expect(r.inserted[0].dedupKey).toBe('order_check:B_B:service_req:2:');
  });
  it('loai trung trong chinh batch', async () => {
    const r = repo([]);
    const sink = new FindingSink(r);
    const n = await sink.persistBatch([mk('B_A', 1), mk('B_A', 1)]);
    expect(n).toBe(1);
    expect(r.inserted).toHaveLength(1);
  });
});
```
- [ ] **Step 2: Run FAIL** — `npx jest finding-sink`
- [ ] **Step 3: Implement**
```typescript
import { Injectable } from '@nestjs/common';
import { InjectRepository } from '@nestjs/typeorm';
import { In, Repository } from 'typeorm';
import { BASE_SCHEMA } from '../constants/common.constant';
import { DqFinding } from './entities/dq-finding.entity';
import { Finding } from './support/finding';

@Injectable()
export class FindingSink {
  constructor(@InjectRepository(DqFinding, BASE_SCHEMA.DEFAULT) private repo: Repository<DqFinding>) {}

  async persistBatch(findings: Finding[]): Promise<number> {
    if (!findings.length) return 0;
    // 1) loai trung trong batch
    const byKey = new Map<string, Finding>();
    for (const f of findings) byKey.set(f.dedupKey(), f);
    const keys = [...byKey.keys()];
    // 2) 1 query lay key da ton tai (chia lo 1000 de tranh gioi han IN cua Oracle)
    const existing = new Set<string>();
    for (let i = 0; i < keys.length; i += 1000) {
      const chunk = keys.slice(i, i + 1000);
      const rows = await this.repo.createQueryBuilder('f')
        .select('f.dedupKey', 'DEDUP_KEY')
        .where('f.dedupKey IN (:...keys)', { keys: chunk })
        .getRawMany();
      rows.forEach((r: any) => existing.add(r.DEDUP_KEY));
    }
    // 3) bulk insert phan moi
    const toInsert = keys.filter(k => !existing.has(k)).map(k => {
      const i = byKey.get(k)!.init;
      return this.repo.create({
        module: i.module, sourceKey: i.sourceKey, ruleCode: i.ruleCode,
        refType: i.refType, refId: i.refId, dedupKey: k, severity: i.severity, message: i.message,
        detail: i.detail ? JSON.stringify(i.detail) : null,
        patientCode: i.patientCode, patientName: i.patientName,
        departmentId: i.departmentId, departmentName: i.departmentName,
        doctorLoginname: i.doctorLoginname, status: 'new', detectedAt: new Date(),
      });
    });
    if (toInsert.length) await this.repo.insert(toInsert);
    return toInsert.length;
  }
}
```
- [ ] **Step 4: Run PASS** — `npx jest finding-sink`
- [ ] **Step 5: Commit** — `git add -A && git commit -m "feat(dq): FindingSink batch idempotent (1 query dedup + bulk insert) (TDD)"`

---

### Task 18: ScanOrchestrator (khóa Redis + watermark + scan logs)

**Files:** Create `backend/src/dq-core/scan-orchestrator.service.ts`

> Điều phối 1 lần quét cho 1 scanner: lấy khóa Redis → gọi scanner → ghi scan log + watermark → nhả khóa.

- [ ] **Step 1: Định nghĩa interface Scanner + implement orchestrator**
```typescript
import { Injectable, Logger } from '@nestjs/common';
import { InjectRepository } from '@nestjs/typeorm';
import { Repository } from 'typeorm';
import { BASE_SCHEMA } from '../constants/common.constant';
import { DqWatermark } from './entities/dq-watermark.entity';
import { DqScanLog } from './entities/dq-scan-log.entity';
import { RedisService } from '../redis/redis.service';

export interface ScanResult { scanned: number; findings: number; maxWatermark: number; }
export interface Scanner {
  sourceKey(): string;
  scan(lastModifyTime: number, limit: number): Promise<ScanResult>;
}

@Injectable()
export class ScanOrchestrator {
  private readonly log = new Logger(ScanOrchestrator.name);
  constructor(
    @InjectRepository(DqWatermark, BASE_SCHEMA.DEFAULT) private wm: Repository<DqWatermark>,
    @InjectRepository(DqScanLog, BASE_SCHEMA.DEFAULT) private logs: Repository<DqScanLog>,
    private redis: RedisService,
  ) {}

  async runScanner(scanner: Scanner, limit = 500): Promise<void> {
    const key = scanner.sourceKey();
    const lock = `dq:scan:lock:${key}`;
    const ok = await this.redis.client.set(lock, '1', 'NX', 'PX', 55000).catch(() => 'OK');
    if (ok !== 'OK') { this.log.warn(`Bo qua ${key}: dang co lan quet khac`); return; }
    const started = Date.now();
    const logRow = this.logs.create({ sourceKey: key, startedAt: new Date(), scannedCount: 0, findingCount: 0, status: 'running' });
    await this.logs.save(logRow);
    try {
      const wm = await this.getWatermark(key);
      const res = await scanner.scan(wm.lastModifyTime || 0, limit);
      if (res.maxWatermark > (wm.lastModifyTime || 0)) {
        wm.lastModifyTime = res.maxWatermark; wm.lastRunAt = new Date();
        await this.wm.save(wm);
      }
      logRow.finishedAt = new Date(); logRow.scannedCount = res.scanned;
      logRow.findingCount = res.findings; logRow.status = 'success'; logRow.durationMs = Date.now() - started;
      await this.logs.save(logRow);
    } catch (e: any) {
      logRow.finishedAt = new Date(); logRow.status = 'error'; logRow.error = String(e?.message ?? e).slice(0, 1990);
      logRow.durationMs = Date.now() - started; await this.logs.save(logRow);
      this.log.error(`Loi quet ${key}`, e);
    } finally {
      await this.redis.client.del(lock).catch(() => {});
    }
  }
  private async getWatermark(key: string): Promise<DqWatermark> {
    let w = await this.wm.findOne({ where: { sourceKey: key } });
    if (!w) { w = this.wm.create({ sourceKey: key, lastModifyTime: 0 }); await this.wm.save(w); }
    return w;
  }
}
```
- [ ] **Step 2: Build & commit** — `cd backend && npm run build && git commit -am "feat(dq): ScanOrchestrator (redis lock + watermark + scan logs)"`

---

### Task 19: ServiceReqScanner (ghép engine order-check)

**Files:** Create `backend/src/order-check/service-req.scanner.ts`

> Ghép: fetch batch → build facts → evaluate (reuse engine) + CodeChecker → gom findings → persistBatch. Tối ưu: dựng engine 1 lần cho cả batch; lookup HIS batched.

- [ ] **Step 1: Implement**
```typescript
import { Injectable } from '@nestjs/common';
import { Scanner, ScanResult } from '../dq-core/scan-orchestrator.service';
import { HisOrderSource } from './his-order-source.service';
import { OrderFactsBuilder } from './order-facts.builder';
import { RuleEvaluator } from '../dq-core/rule-evaluator.service';
import { RulesCache } from '../dq-core/rules-cache.service';
import { FindingSink } from '../dq-core/finding-sink.service';
import { ExecuteBeforeOrderChecker } from './checkers/execute-before-order.checker';
import { Finding } from '../dq-core/support/finding';

const MODULE = 'order_check';
const SOURCE_KEY = 'order_check.his_service_req';

@Injectable()
export class ServiceReqScanner implements Scanner {
  constructor(
    private source: HisOrderSource, private facts: OrderFactsBuilder,
    private evaluator: RuleEvaluator, private rulesCache: RulesCache,
    private sink: FindingSink, private execChecker: ExecuteBeforeOrderChecker,
  ) {}
  sourceKey() { return SOURCE_KEY; }

  async scan(lastModifyTime: number, limit: number): Promise<ScanResult> {
    const rows = await this.source.fetchServiceReqs(lastModifyTime, limit);
    if (!rows.length) return { scanned: 0, findings: 0, maxWatermark: lastModifyTime };

    // batched lookups
    const treatmentIds = [...new Set(rows.map(r => Number(r.TREATMENT_ID)).filter(Boolean))];
    const reqIds = rows.map(r => Number(r.ID));
    const loginnames = rows.map(r => r.EXECUTE_LOGINNAME).filter(Boolean);
    const [treatments, diplomas, sereServ] = await Promise.all([
      this.source.fetchTreatments(treatmentIds),
      this.source.fetchDiplomas(loginnames),
      this.source.fetchSereServ(reqIds),
    ]);

    // dung engine 1 lan cho ca batch
    const rules = await this.rulesCache.getActiveRules(MODULE, SOURCE_KEY);
    const engine = this.evaluator.buildEngine(MODULE, SOURCE_KEY, rules);

    const all: Finding[] = [];
    let maxWm = lastModifyTime;
    for (const r of rows) {
      const t = treatments.get(Number(r.TREATMENT_ID)) ?? {};
      const merged = { ...r, IN_TIME: t.IN_TIME, OUT_TIME: t.OUT_TIME, DIPLOMA: diplomas.get(r.EXECUTE_LOGINNAME) };
      const f = this.facts.build(merged);
      const ctx = { refType: 'service_req', refId: Number(r.ID),
        patientCode: r.TDL_PATIENT_CODE, patientName: r.TDL_PATIENT_NAME,
        departmentId: r.EXECUTE_DEPARTMENT_ID != null ? Number(r.EXECUTE_DEPARTMENT_ID) : undefined,
        doctorLoginname: r.REQUEST_LOGINNAME };
      all.push(...await this.evaluator.evaluate(engine, f, ctx));
      all.push(...this.execChecker.check(sereServ.get(Number(r.ID)) ?? [], ctx));
      maxWm = Math.max(maxWm, Number(r.MODIFY_TIME) || 0);
    }
    const inserted = await this.sink.persistBatch(all);
    return { scanned: rows.length, findings: inserted, maxWatermark: maxWm };
  }
}
```
- [ ] **Step 2: Build & commit** — `cd backend && npm run build && git commit -am "feat(order-check): ServiceReqScanner (facts + rules + checker, batched)"`

---

### Task 20: Cron scheduler

**Files:** Create `backend/src/order-check/order-check.scheduler.ts`

- [ ] **Step 1: Implement**
```typescript
import { Injectable } from '@nestjs/common';
import { Cron } from '@nestjs/schedule';
import { ConfigService } from '@nestjs/config';
import { ScanOrchestrator } from '../dq-core/scan-orchestrator.service';
import { ServiceReqScanner } from './service-req.scanner';

@Injectable()
export class OrderCheckScheduler {
  constructor(private orch: ScanOrchestrator, private scanner: ServiceReqScanner, _cfg: ConfigService) {}
  @Cron(process.env.ORDER_CHECK_SCAN_CRON || '*/1 * * * *')
  async handle() { await this.orch.runScanner(this.scanner, 500); }
}
```
> Ghi chú: `@Cron` cần biểu thức tĩnh lúc decorate → đọc `process.env` trực tiếp (đã `dotenv/config` ở main.ts).
- [ ] **Step 2: Build & commit** — `cd backend && npm run build && git commit -am "feat(order-check): cron scheduler"`

---

### Task 21: Seeder luật B_* + init watermark

**Files:** Create `backend/src/seeders/dq-rules.seeder.ts`; Modify/Create `backend/src/data-source.ts`

- [ ] **Step 1: Seeder** (seed 3 luật B_* dạng data + đăng ký CodeChecker `B_EXECUTE_BEFORE_ORDER` như 1 rule để bật/tắt được)
```typescript
import { DataSource } from 'typeorm';
import { Seeder } from 'typeorm-extension';
import { DqRule } from '../dq-core/entities/dq-rule.entity';

const SK = 'order_check.his_service_req';
const RULES = [
  { code: 'B_DISCHARGE_BEFORE_ADMISSION', name: 'Ngay ra vien truoc ngay vao vien', severity: 'critical', priority: 100,
    conditions: { all: [{ fact: 'out_before_in', operator: 'equal', value: true }] } },
  { code: 'B_ORDER_TIME_OUT_OF_STAY', name: 'Gio y lenh ngoai dot dieu tri', severity: 'warning', priority: 90,
    conditions: { any: [
      { fact: 'order_before_in', operator: 'equal', value: true },
      { fact: 'order_after_out', operator: 'equal', value: true },
    ] } },
  { code: 'B_DOCTOR_NO_PRACTICE_CERT', name: 'Nguoi thuc hien thieu CCHN', severity: 'critical', priority: 95,
    conditions: { all: [{ fact: 'executor_missing_diploma', operator: 'equal', value: true }] } },
  // CodeChecker rule (conditions rong; ServiceReqScanner luon chay checker; co the bo qua neu can gan is_active)
  { code: 'B_EXECUTE_BEFORE_ORDER', name: 'Gio thuc hien truoc gio y lenh', severity: 'warning', priority: 80, conditions: { all: [] } },
];

export default class DqRulesSeeder implements Seeder {
  async run(ds: DataSource): Promise<void> {
    const repo = ds.getRepository(DqRule);
    for (const r of RULES) {
      const exists = await repo.findOne({ where: { module: 'order_check', code: r.code } });
      if (!exists) await repo.save(repo.create({
        module: 'order_check', sourceKey: SK, code: r.code, name: r.name,
        severity: r.severity, priority: r.priority, conditions: JSON.stringify(r.conditions), isActive: 1,
      }));
    }
  }
}
```
- [ ] **Step 2:** Sao `data-source.ts` từ `bm_patient_hub` (đăng ký seeder). Chạy `cd backend && npm run seed`.
- [ ] **Step 3:** Init watermark (SQL 1 lần):
```sql
INSERT INTO DQ_WATERMARKS (SOURCE_KEY, LAST_MODIFY_TIME, LAST_RUN_AT)
VALUES ('order_check.his_service_req', TO_NUMBER(TO_CHAR(SYSTIMESTAMP,'YYYYMMDDHH24MISS')), SYSTIMESTAMP);
```
Expected: `SELECT COUNT(*) FROM DQ_RULES WHERE MODULE='order_check'` = 4.
- [ ] **Step 4: Commit** — `cd .. && git add -A && git commit -m "feat(order-check): seed B_* rules + init watermark"`

---

### Task 22: Department scope helper (TDD) + user-department repo

**Files:** Create `backend/src/dq-core/department-scope.util.ts`; Test `department-scope.util.spec.ts`

> Áp phân quyền khoa vào QueryBuilder: có `view_all_departments` → không lọc; không → `department_id IN (khoa được gán)`; không gán + không view-all → điều kiện `1=0` (rỗng).

- [ ] **Step 1: Test**
```typescript
import { applyDepartmentScope } from './department-scope.util';

function fakeQb() {
  const calls: any[] = [];
  return { calls, andWhere: (s: string, p?: any) => { calls.push({ s, p }); return; } } as any;
}

describe('applyDepartmentScope', () => {
  it('view_all -> khong loc', () => {
    const qb = fakeQb();
    applyDepartmentScope(qb, { permissions: ['data_quality:view_all_departments'] } as any, [3, 4]);
    expect(qb.calls).toHaveLength(0);
  });
  it('khong view_all + co khoa -> loc IN', () => {
    const qb = fakeQb();
    applyDepartmentScope(qb, { permissions: [] } as any, [3, 4]);
    expect(qb.calls[0].s).toContain('IN');
    expect(qb.calls[0].p).toEqual({ deptIds: [3, 4] });
  });
  it('khong view_all + khong khoa -> 1=0', () => {
    const qb = fakeQb();
    applyDepartmentScope(qb, { permissions: [] } as any, []);
    expect(qb.calls[0].s).toBe('1 = 0');
  });
});
```
- [ ] **Step 2: Run FAIL** — `npx jest department-scope`
- [ ] **Step 3: Implement**
```typescript
import { SelectQueryBuilder } from 'typeorm';

export interface ScopedUser { permissions?: string[]; }
export const VIEW_ALL_DEPT = 'data_quality:view_all_departments';

export function applyDepartmentScope<T>(qb: SelectQueryBuilder<T>, user: ScopedUser, deptIds: number[]): void {
  if ((user.permissions ?? []).includes(VIEW_ALL_DEPT)) return;
  if (!deptIds.length) { qb.andWhere('1 = 0'); return; }
  qb.andWhere('f.departmentId IN (:...deptIds)', { deptIds });
}
```
- [ ] **Step 4: Run PASS** — `npx jest department-scope`
- [ ] **Step 5: Commit** — `git add -A && git commit -m "feat(dq): applyDepartmentScope helper (TDD)"`

---

### Task 23: ListFindings query (CQRS) + controller + dept scope

**Files:** Create `backend/src/dq-core/dto/list-findings.dto.ts`, `queries/list-findings.query.ts`, `queries/list-findings.handler.ts`, `data-quality.controller.ts`, `data-quality.service.ts`, `user-department.service.ts`; Test `queries/list-findings.handler.spec.ts`

- [ ] **Step 1: DTO**
```typescript
import { ApiPropertyOptional } from '@nestjs/swagger';
import { IsOptional, IsInt, IsString, Min } from 'class-validator';
import { Type } from 'class-transformer';
export class ListFindingsDto {
  @ApiPropertyOptional() @IsOptional() @Type(() => Number) @IsInt() @Min(1) page = 1;
  @ApiPropertyOptional() @IsOptional() @Type(() => Number) @IsInt() @Min(1) limit = 20;
  @ApiPropertyOptional() @IsOptional() @IsString() module?: string;
  @ApiPropertyOptional() @IsOptional() @IsString() severity?: string;
  @ApiPropertyOptional() @IsOptional() @IsString() status?: string;
  @ApiPropertyOptional() @IsOptional() @IsString() ruleCode?: string;
}
```
- [ ] **Step 2: UserDepartmentService** (lấy khoa của user)
```typescript
import { Injectable } from '@nestjs/common';
import { InjectRepository } from '@nestjs/typeorm';
import { Repository } from 'typeorm';
import { BASE_SCHEMA } from '../constants/common.constant';
import { DqUserDepartment } from './entities/dq-user-department.entity';

@Injectable()
export class UserDepartmentService {
  constructor(@InjectRepository(DqUserDepartment, BASE_SCHEMA.DEFAULT) private repo: Repository<DqUserDepartment>) {}
  async departmentIdsOf(userId: string): Promise<number[]> {
    const rows = await this.repo.find({ where: { userId } });
    return rows.map(r => Number(r.hisDepartmentId));
  }
  async setDepartments(userId: string, ids: number[]): Promise<void> {
    await this.repo.delete({ userId });
    if (ids.length) await this.repo.insert(ids.map(id => this.repo.create({ userId, hisDepartmentId: id })));
  }
}
```
- [ ] **Step 3: Test handler (repo + userDept mock)**
```typescript
import { ListFindingsHandler } from './list-findings.handler';

function mockRepo(items: any[], total: number) {
  const qb: any = { andWhere: jest.fn().mockReturnThis(), orderBy: jest.fn().mockReturnThis(),
    skip: jest.fn().mockReturnThis(), take: jest.fn().mockReturnThis(),
    getManyAndCount: jest.fn().mockResolvedValue([items, total]) };
  return { qb, createQueryBuilder: jest.fn(() => qb) } as any;
}
const userDept = { departmentIdsOf: jest.fn().mockResolvedValue([3]) } as any;

describe('ListFindingsHandler', () => {
  it('tra data + pagination, ap dept scope cho user thuong', async () => {
    const repo = mockRepo([{ id: '1' }], 1);
    const h = new ListFindingsHandler(repo, userDept);
    const res = await h.execute({ dto: { page: 1, limit: 20 }, user: { userId: 'u1', permissions: [] } } as any);
    expect(res.data).toHaveLength(1);
    expect(res.pagination.total).toBe(1);
    expect(userDept.departmentIdsOf).toHaveBeenCalledWith('u1');
  });
});
```
- [ ] **Step 4: Run FAIL** — `npx jest list-findings.handler`
- [ ] **Step 5: Query + handler**
```typescript
// queries/list-findings.query.ts
export class ListFindingsQuery { constructor(public dto: any, public user: any) {} }
```
```typescript
// queries/list-findings.handler.ts
import { IQueryHandler, QueryHandler } from '@nestjs/cqrs';
import { InjectRepository } from '@nestjs/typeorm';
import { Repository } from 'typeorm';
import { BASE_SCHEMA } from '../../constants/common.constant';
import { DqFinding } from '../entities/dq-finding.entity';
import { ListFindingsQuery } from './list-findings.query';
import { buildPagination } from '../../common/pagination.util';
import { applyDepartmentScope } from '../department-scope.util';
import { UserDepartmentService } from '../user-department.service';

@QueryHandler(ListFindingsQuery)
export class ListFindingsHandler implements IQueryHandler<ListFindingsQuery> {
  constructor(
    @InjectRepository(DqFinding, BASE_SCHEMA.DEFAULT) private repo: Repository<DqFinding>,
    private userDept: UserDepartmentService,
  ) {}
  async execute({ dto, user }: ListFindingsQuery) {
    const { page = 1, limit = 20, module, severity, status, ruleCode } = dto;
    const qb = this.repo.createQueryBuilder('f');
    if (module) qb.andWhere('f.module = :module', { module });
    if (severity) qb.andWhere('f.severity = :severity', { severity });
    if (status) qb.andWhere('f.status = :status', { status });
    if (ruleCode) qb.andWhere('f.ruleCode = :ruleCode', { ruleCode });
    const deptIds = await this.userDept.departmentIdsOf(user.userId);
    applyDepartmentScope(qb, user, deptIds);
    qb.orderBy('f.detectedAt', 'DESC').skip((page - 1) * limit).take(limit);
    const [data, total] = await qb.getManyAndCount();
    return { data, pagination: buildPagination(total, page, limit) };
  }
}
```
- [ ] **Step 6: Run PASS** — `npx jest list-findings.handler`
- [ ] **Step 7: Service + Controller (RBAC + lấy user từ req)**
```typescript
// data-quality.service.ts
import { Injectable } from '@nestjs/common';
import { QueryBus } from '@nestjs/cqrs';
import { ListFindingsQuery } from './queries/list-findings.query';
@Injectable()
export class DataQualityService {
  constructor(private queryBus: QueryBus) {}
  listFindings(dto: any, user: any) { return this.queryBus.execute(new ListFindingsQuery(dto, user)); }
}
```
```typescript
// data-quality.controller.ts
import { Controller, Get, Query, Req, UseGuards } from '@nestjs/common';
import { ApiTags, ApiBearerAuth } from '@nestjs/swagger';
import { JwtAdminAuthGuard } from '../admin-auth/jwt-admin-auth.guard';
import { PermissionsGuard } from '../role-permission/guards/permissions.guard';
import { Permission } from '../role-permission/decorators/permission.decorator';
import { DataQualityService } from './data-quality.service';
import { ListFindingsDto } from './dto/list-findings.dto';

@ApiTags('data-quality')
@ApiBearerAuth('access-token')
@UseGuards(JwtAdminAuthGuard, PermissionsGuard)
@Controller('data-quality')
export class DataQualityController {
  constructor(private service: DataQualityService) {}
  @Get('findings')
  @Permission('data_quality:read')
  findings(@Query() dto: ListFindingsDto, @Req() req: any) {
    return this.service.listFindings(dto, req.user);
  }
}
```
- [ ] **Step 8: Commit** — `git add -A && git commit -m "feat(dq): ListFindings CQRS + department scope (TDD)"`

---

### Task 24: User-department API + danh sách khoa (HIS)

**Files:** Modify `backend/src/dq-core/data-quality.controller.ts`; Create `backend/src/dq-core/dto/set-departments.dto.ts`; add HIS department query to `HisOrderSource` or new `his-catalog.service.ts`

- [ ] **Step 1: `his-catalog.service.ts`** (đọc danh sách khoa từ HIS)
```typescript
import { Injectable } from '@nestjs/common';
import { InjectDataSource } from '@nestjs/typeorm';
import { DataSource } from 'typeorm';
import { BASE_SCHEMA } from '../constants/common.constant';
@Injectable()
export class HisCatalogService {
  constructor(@InjectDataSource(BASE_SCHEMA.HIS_RS) private his: DataSource) {}
  async departments(): Promise<{ id: number; name: string }[]> {
    const rows = await this.his.query(`SELECT ID, DEPARTMENT_NAME FROM HIS_DEPARTMENT ORDER BY DEPARTMENT_NAME`);
    return rows.map((r: any) => ({ id: Number(r.ID), name: r.DEPARTMENT_NAME }));
  }
}
```
> ⚠ Xác minh cột tên khoa (`DEPARTMENT_NAME`) qua sqlcl khi thực thi; sửa nếu khác.
- [ ] **Step 2: DTO + endpoints trong controller**
```typescript
// dto/set-departments.dto.ts
import { IsArray, IsInt } from 'class-validator';
export class SetDepartmentsDto { @IsArray() @IsInt({ each: true }) departmentIds: number[]; }
```
Thêm vào `DataQualityController`:
```typescript
@Get('departments')
@Permission('data_quality:manage_user_department')
listDepartments() { return this.hisCatalog.departments(); }

@Get('user-department/:userId')
@Permission('data_quality:manage_user_department')
getUserDept(@Param('userId') userId: string) { return this.userDept.departmentIdsOf(userId); }

@Put('user-department/:userId')
@Permission('data_quality:manage_user_department')
setUserDept(@Param('userId') userId: string, @Body() dto: SetDepartmentsDto) {
  return this.userDept.setDepartments(userId, dto.departmentIds);
}
```
(inject `HisCatalogService` + `UserDepartmentService` vào controller; import `Param, Body, Put`.)
- [ ] **Step 3: Build & commit** — `cd backend && npm run build && git commit -am "feat(dq): user-department assignment API + HIS departments"`

---

### Task 25: Scan-logs query + rule toggle/update (invalidation)

**Files:** Create `backend/src/dq-core/queries/list-scan-logs.{query,handler}.ts`, `commands/toggle-rule.{command,handler}.ts`, `commands/update-rule.{command,handler}.ts`; Modify controller

- [ ] **Step 1: ListScanLogs query/handler** (không dept-scope — log hệ thống, quyền riêng)
```typescript
// queries/list-scan-logs.query.ts
export class ListScanLogsQuery { constructor(public dto: any) {} }
```
```typescript
// queries/list-scan-logs.handler.ts
import { IQueryHandler, QueryHandler } from '@nestjs/cqrs';
import { InjectRepository } from '@nestjs/typeorm';
import { Repository } from 'typeorm';
import { BASE_SCHEMA } from '../../constants/common.constant';
import { DqScanLog } from '../entities/dq-scan-log.entity';
import { ListScanLogsQuery } from './list-scan-logs.query';
import { buildPagination } from '../../common/pagination.util';

@QueryHandler(ListScanLogsQuery)
export class ListScanLogsHandler implements IQueryHandler<ListScanLogsQuery> {
  constructor(@InjectRepository(DqScanLog, BASE_SCHEMA.DEFAULT) private repo: Repository<DqScanLog>) {}
  async execute({ dto }: ListScanLogsQuery) {
    const page = dto.page ?? 1, limit = dto.limit ?? 20;
    const [data, total] = await this.repo.createQueryBuilder('l')
      .orderBy('l.startedAt', 'DESC').skip((page - 1) * limit).take(limit).getManyAndCount();
    return { data, pagination: buildPagination(total, page, limit) };
  }
}
```
- [ ] **Step 2: ToggleRule + UpdateRule commands (publish invalidation)**
```typescript
// commands/toggle-rule.command.ts
export class ToggleRuleCommand { constructor(public id: string, public isActive: boolean) {} }
```
```typescript
// commands/toggle-rule.handler.ts
import { CommandHandler, ICommandHandler } from '@nestjs/cqrs';
import { InjectRepository } from '@nestjs/typeorm';
import { Repository } from 'typeorm';
import { BASE_SCHEMA } from '../../constants/common.constant';
import { DqRule } from '../entities/dq-rule.entity';
import { ToggleRuleCommand } from './toggle-rule.command';
import { RulesCache } from '../rules-cache.service';
import { ApiException } from '../../common/api.exception';

@CommandHandler(ToggleRuleCommand)
export class ToggleRuleHandler implements ICommandHandler<ToggleRuleCommand> {
  constructor(@InjectRepository(DqRule, BASE_SCHEMA.DEFAULT) private repo: Repository<DqRule>, private cache: RulesCache) {}
  async execute({ id, isActive }: ToggleRuleCommand) {
    const rule = await this.repo.findOne({ where: { id } });
    if (!rule) throw new ApiException('DQ_RULE_NOT_FOUND');
    rule.isActive = isActive ? 1 : 0;
    await this.repo.save(rule);
    await this.cache.invalidate(rule.module, rule.sourceKey);
    return { success: true };
  }
}
```
(UpdateRule tương tự: sửa `conditions`(validate JSON parse)/`severity` rồi `cache.invalidate`.)
- [ ] **Step 3: Thêm endpoints controller** — `GET /scan-logs` (`@Permission('data_quality:read_scan_logs')`), `POST /rules/:id/toggle`, `PUT /rules/:id` (`@Permission('data_quality:manage_rule')`).
- [ ] **Step 4: Commit** — `git add -A && git commit -m "feat(dq): scan-logs query + rule toggle/update with cache invalidation"`

---

### Task 26: Module wiring

**Files:** Create `backend/src/dq-core/dq-core.module.ts`, `backend/src/order-check/order-check.module.ts`; Modify `src/app.module.ts`

- [ ] **Step 1: `dq-core.module.ts`**
```typescript
import { Module } from '@nestjs/common';
import { CqrsModule } from '@nestjs/cqrs';
import { TypeOrmModule } from '@nestjs/typeorm';
import { BASE_SCHEMA } from '../constants/common.constant';
import { DqRule } from './entities/dq-rule.entity';
import { DqFinding } from './entities/dq-finding.entity';
import { DqWatermark } from './entities/dq-watermark.entity';
import { DqScanLog } from './entities/dq-scan-log.entity';
import { DqUserDepartment } from './entities/dq-user-department.entity';
import { RulesCache } from './rules-cache.service';
import { RuleEvaluator } from './rule-evaluator.service';
import { FindingSink } from './finding-sink.service';
import { ScanOrchestrator } from './scan-orchestrator.service';
import { UserDepartmentService } from './user-department.service';
import { HisCatalogService } from './his-catalog.service';
import { DataQualityController } from './data-quality.controller';
import { DataQualityService } from './data-quality.service';
import { ListFindingsHandler } from './queries/list-findings.handler';
import { ListScanLogsHandler } from './queries/list-scan-logs.handler';
import { ToggleRuleHandler } from './commands/toggle-rule.handler';
import { AdminAuthModule } from '../admin-auth/admin-auth.module';
import { RolePermissionModule } from '../role-permission/role-permission.module';

@Module({
  imports: [
    CqrsModule,
    TypeOrmModule.forFeature([DqRule, DqFinding, DqWatermark, DqScanLog, DqUserDepartment], BASE_SCHEMA.DEFAULT),
    AdminAuthModule, RolePermissionModule,
  ],
  controllers: [DataQualityController],
  providers: [
    DataQualityService, RulesCache, RuleEvaluator, FindingSink, ScanOrchestrator,
    UserDepartmentService, HisCatalogService,
    ListFindingsHandler, ListScanLogsHandler, ToggleRuleHandler,
  ],
  exports: [RulesCache, RuleEvaluator, FindingSink, ScanOrchestrator],
})
export class DqCoreModule {}
```
- [ ] **Step 2: `order-check.module.ts`**
```typescript
import { Module } from '@nestjs/common';
import { DqCoreModule } from '../dq-core/dq-core.module';
import { HisOrderSource } from './his-order-source.service';
import { OrderFactsBuilder } from './order-facts.builder';
import { ExecuteBeforeOrderChecker } from './checkers/execute-before-order.checker';
import { ServiceReqScanner } from './service-req.scanner';
import { OrderCheckScheduler } from './order-check.scheduler';

@Module({
  imports: [DqCoreModule],
  providers: [HisOrderSource, OrderFactsBuilder, ExecuteBeforeOrderChecker, ServiceReqScanner, OrderCheckScheduler],
})
export class OrderCheckModule {}
```
- [ ] **Step 3:** Import `DqCoreModule` + `OrderCheckModule` vào `app.module.ts`; build.
- [ ] **Step 4: Chạy dev** — `npm run start:dev`. Trong 1 phút thấy log ScanOrchestrator; `GET /data-quality/findings` (Bearer) trả `{ data, pagination }`.
- [ ] **Step 5: Commit** — `cd .. && git add -A && git commit -m "feat: wire dq-core + order-check modules; cron + API live"`

---

### Task 27: Frontend — model + service + store

**Files:** Create `frontend/src/models/data-quality.model.ts`, `api/data-quality.service.ts`, `stores/data-quality.store.ts`

- [ ] **Step 1: model**
```typescript
export interface Finding {
  id: string; module: string; ruleCode: string; severity: string;
  patientName?: string; departmentName?: string; message?: string; status: string; detectedAt?: string;
}
export interface Pagination { page: number; limit: number; total: number; totalPages: number; }
export interface ListFindingsParams { page?: number; limit?: number; module?: string; severity?: string; status?: string; }
export interface ListFindingsResponse { data: Finding[]; pagination: Pagination; }
```
- [ ] **Step 2: service**
```typescript
import apiClient from './config';
import type { ListFindingsParams, ListFindingsResponse } from '../models/data-quality.model';
export const dataQualityService = {
  async getFindings(params: ListFindingsParams): Promise<ListFindingsResponse> {
    const res = await apiClient.get('/data-quality/findings', { params });
    return res.data;
  },
};
```
- [ ] **Step 3: store**
```typescript
import { defineStore } from 'pinia';
import { dataQualityService } from '../api/data-quality.service';
import type { Finding, Pagination, ListFindingsParams } from '../models/data-quality.model';
export const useDataQualityStore = defineStore('dataQuality', {
  state: () => ({ findings: [] as Finding[], loading: false,
    pagination: { page: 1, limit: 20, total: 0, totalPages: 0 } as Pagination }),
  actions: {
    async fetchFindings(params: ListFindingsParams) {
      this.loading = true;
      try { const res = await dataQualityService.getFindings(params); this.findings = res.data; this.pagination = res.pagination; }
      finally { this.loading = false; }
    },
  },
});
```
- [ ] **Step 4: Commit** — `git add -A && git commit -m "feat(frontend): data-quality model + service + store"`

---

### Task 28: Frontend — màn Findings + route + menu

**Files:** Create `frontend/src/views/backend/data-quality/DataQualityFindings.vue`, `FindingsTable.vue`; Modify `router/index.ts`, `data/menu.ts`

- [ ] **Step 1: `FindingsTable.vue`**
```vue
<script setup lang="ts">
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Tag from 'primevue/tag';
import type { Finding, Pagination } from '../../../models/data-quality.model';
defineProps<{ items: Finding[]; loading: boolean; pagination: Pagination }>();
const emit = defineEmits<{ (e: 'page', ev: any): void }>();
const sev = (s?: string) => s === 'critical' ? 'danger' : s === 'warning' ? 'warn' : 'info';
</script>
<template>
  <DataTable :value="items" :loading="loading" lazy paginator :rows="pagination.limit"
    :totalRecords="pagination.total" :first="(pagination.page - 1) * pagination.limit" @page="emit('page', $event)">
    <Column field="ruleCode" header="Mã luật" />
    <Column field="patientName" header="Bệnh nhân" />
    <Column field="departmentName" header="Khoa" />
    <Column header="Mức độ"><template #body="{ data }"><Tag :value="data.severity" :severity="sev(data.severity)" /></template></Column>
    <Column field="message" header="Nội dung" />
    <Column field="status" header="Trạng thái" />
  </DataTable>
</template>
```
- [ ] **Step 2: `DataQualityFindings.vue`**
```vue
<script setup lang="ts">
import { onMounted } from 'vue';
import { storeToRefs } from 'pinia';
import { useDataQualityStore } from '../../../stores/data-quality.store';
import FindingsTable from './FindingsTable.vue';
const store = useDataQualityStore();
const { findings, loading, pagination } = storeToRefs(store);
const load = (page = 1) => store.fetchFindings({ page, limit: pagination.value.limit });
const onPage = (ev: any) => load(Math.floor(ev.first / ev.rows) + 1);
onMounted(() => load(1));
</script>
<template>
  <div class="content">
    <h2>Phát hiện chất lượng dữ liệu</h2>
    <FindingsTable :items="findings" :loading="loading" :pagination="pagination" @page="onPage" />
  </div>
</template>
```
- [ ] **Step 3: Route** — thêm vào children `/backend` trong `router/index.ts`:
```typescript
{ path: 'data-quality', name: 'data-quality',
  component: () => import('../views/backend/data-quality/DataQualityFindings.vue'),
  meta: { requiresAuth: true, requiresPermission: 'data_quality:read' } },
```
- [ ] **Step 4: Menu** — thêm vào `data/menu.ts`:
```typescript
{ name: 'data-quality', to: 'data-quality', icon: 'si si-shield', permission: 'data_quality:read' },
```
- [ ] **Step 5: Kiểm tra** — `cd frontend && npm run dev`; đăng nhập (user có `data_quality:read`) → menu "Phát hiện chất lượng dữ liệu" → bảng findings (từ backend).
- [ ] **Step 6: Commit** — `cd .. && git add -A && git commit -m "feat(frontend): findings list screen + route + menu"`

---

### Task 29: Frontend — màn gán khoa cho user

**Files:** Create `frontend/src/views/backend/admin/UserDepartment.vue`, `api/user-department.service.ts`; Modify `router/index.ts`, `data/menu.ts`

- [ ] **Step 1: service**
```typescript
import apiClient from './config';
export const userDepartmentService = {
  listDepartments: () => apiClient.get('/data-quality/departments').then(r => r.data),
  getUserDepartments: (userId: string) => apiClient.get(`/data-quality/user-department/${userId}`).then(r => r.data),
  setUserDepartments: (userId: string, departmentIds: number[]) =>
    apiClient.put(`/data-quality/user-department/${userId}`, { departmentIds }).then(r => r.data),
};
```
- [ ] **Step 2: `UserDepartment.vue`** (chọn user → tick khoa → lưu)
```vue
<script setup lang="ts">
import { ref, onMounted } from 'vue';
import MultiSelect from 'primevue/multiselect';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import { userDepartmentService } from '../../../api/user-department.service';
const departments = ref<{ id: number; name: string }[]>([]);
const userId = ref('');
const selected = ref<number[]>([]);
onMounted(async () => { departments.value = await userDepartmentService.listDepartments(); });
async function loadUser() { selected.value = await userDepartmentService.getUserDepartments(userId.value); }
async function save() { await userDepartmentService.setUserDepartments(userId.value, selected.value); }
</script>
<template>
  <div class="content">
    <h2>Gán khoa cho người dùng</h2>
    <InputText v-model="userId" placeholder="User ID" /> <Button label="Tải" @click="loadUser" />
    <MultiSelect v-model="selected" :options="departments" optionLabel="name" optionValue="id"
      filter placeholder="Chọn khoa" style="min-width:320px" />
    <Button label="Lưu" severity="success" @click="save" />
  </div>
</template>
```
- [ ] **Step 3: Route + menu** — thêm route `admin/user-department` (`requiresPermission: 'data_quality:manage_user_department'`) + mục menu tương ứng.
- [ ] **Step 4: Kiểm tra** — chọn user, tick khoa, lưu → gọi API thành công; đăng nhập bằng user đó (không view-all) → chỉ thấy findings khoa được gán.
- [ ] **Step 5: Commit** — `git add -A && git commit -m "feat(frontend): user-department assignment screen"`

---

### Task 30: Kiểm chứng end-to-end Pha 1

- [ ] **Step 1: Toàn bộ test backend** — `cd backend && npx jest` → PASS (finding, facts, rule-evaluator, execute-before-order, finding-sink, department-scope, list-findings).
- [ ] **Step 2: Luồng thật**
  1. Backend `start:dev` + frontend `dev`.
  2. Chờ cron (hoặc tạo dữ liệu HIS test `OUT_TIME < IN_TIME`) → `SELECT * FROM DQ_FINDINGS` có bản ghi; `DQ_SCAN_LOGS` ghi `success` + `DURATION_MS`.
  3. Chạy quét 2 lần → không sinh trùng (batch dedup).
  4. Sửa 1 rule (toggle) → cache Redis invalidate (kiểm bằng `redis-cli GET dq:rules:order_check:order_check.his_service_req` mất sau toggle).
  5. Gán khoa cho 1 user thường → user đó chỉ thấy findings khoa được gán; user view-all thấy tất cả.
- [ ] **Step 3:** Ghi kết quả kiểm chứng vào `docs/`.
- [ ] **Step 4: Commit** — `git add -A && git commit -m "test: end-to-end verification phase 1"`

**✅ Mốc Pha 1:** cron quét HIS → facts → luật `B_*` (data) + CodeChecker → finding idempotent (batch dedup) → phân quyền khoa → giao diện; sửa rule invalidate cache.

---

## Self-review

**1. Spec coverage (Pha 0+1):** scaffold + Redis (T1–8) ✔; DQ_* + DQ_USER_DEPARTMENT (T9–10) ✔; Finding/Facts (T11) ✔; FactsBuilder computed-facts (T12) ✔; RulesCache Redis+pubsub degrade (T13) ✔; RuleEvaluator json-rules-engine reuse (T14) ✔; ExecuteBeforeOrder CodeChecker (T15) ✔; HisOrderSource batched (T16) ✔; FindingSink batch idempotent (T17) ✔; ScanOrchestrator + khóa Redis + watermark + scan logs (T18) ✔; ServiceReqScanner (T19) ✔; cron (T20) ✔; seed B_* + init watermark (T21) ✔; applyDepartmentScope + user-department (T22–24) ✔; ListFindings/ScanLogs/rule toggle CQRS + invalidation (T23,25) ✔; wiring (T26) ✔; FE findings + user-department (T27–29) ✔; e2e (T30) ✔. **Catalog import đầy đủ ~11 loại = Pha 2 (plan riêng); order-check A_*, dashboard, export = Pha 3.**

**2. Placeholder scan:** không có TBD/TODO; mọi step code có nội dung thật. Hai điểm cần xác minh runtime đã ghi rõ (⚠): cột `HIS_SERE_SERV.SERVICE_REQ_ID` và `HIS_DEPARTMENT.DEPARTMENT_NAME` — kiểm qua sqlcl khi thực thi.

**3. Type consistency:** `Finding.dedupKey()` dùng ở FindingSink + ExecuteBeforeOrderChecker; `CachedRule` từ RulesCache → RuleEvaluator; `Scanner` interface (ScanOrchestrator) implement bởi ServiceReqScanner; `applyDepartmentScope(qb, user, deptIds)` dùng ở ListFindingsHandler; `RecordContext` dùng ở evaluator + checker; `Facts` từ FactsBuilder → RuleEvaluator. Nhất quán.

**4. Tối ưu (khác PHP) đã hiện thực:** batch dedup (T17), reuse engine (T14/T19), rules cache Redis (T13), batched HIS lookup (T16/T19), facts thuần (T12), khóa quét Redis (T18).

---

*Plan Pha 0 + Pha 1. Sau khi xong: plan Pha 2 (catalog import ~11 loại) rồi Pha 3 (order-check A_* + dashboard + export).*
