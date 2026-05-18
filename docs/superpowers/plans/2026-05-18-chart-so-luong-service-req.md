# Chart "Số lượng" → đếm phiếu his_service_req — Implementation Plan

> **For agentic workers:** REQUIRED: Use superpowers:subagent-driven-development (if subagents available) or superpowers:executing-plans to implement this plan. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Đổi cột "Số lượng" của chart "Doanh thu & Số lượng theo loại dịch vụ" từ tổng số dòng dịch vụ chi tiết (`sum(his_sere_serv.amount)`) sang số phiếu chỉ định riêng biệt (`count(distinct his_service_req.id)`).

**Architecture:** Thay đúng một biểu thức trong chuỗi `selectRaw` của method `doanhthuOverview()` trong `HomeController.php`. Không đổi cấu trúc query, không đổi xử lý PHP/JS phía sau.

**Tech Stack:** Laravel (PHP), Eloquent query builder, kết nối DB `HISPro`.

**Spec:** `docs/superpowers/specs/2026-05-18-chart-so-luong-service-req-design.md`

---

## Chunk 1: Sửa query và kiểm thử

### Task 1: Đổi biểu thức `so_luong` trong `doanhthuOverview()`

**Files:**
- Modify: `app/Http/Controllers/HomeController.php:342-343`

- [ ] **Step 1: Đọc method để xác nhận hiện trạng**

Đọc `app/Http/Controllers/HomeController.php` dòng 331-355. Xác nhận chuỗi `selectRaw` hiện chứa:

```php
->selectRaw('his_service_req.service_req_type_id,
             his_service_req_type.service_req_type_name,
             his_sere_serv.patient_type_id,
             his_patient_type.patient_type_name,
             sum(his_sere_serv.amount) as so_luong,
             sum(his_sere_serv.amount * his_sere_serv.price) as thanh_tien')
```

- [ ] **Step 2: Sửa biểu thức `so_luong`**

Thay đúng dòng `sum(his_sere_serv.amount) as so_luong,` thành `count(distinct his_service_req.id) as so_luong,`.

Kết quả chuỗi `selectRaw` sau khi sửa:

```php
->selectRaw('his_service_req.service_req_type_id,
             his_service_req_type.service_req_type_name,
             his_sere_serv.patient_type_id,
             his_patient_type.patient_type_name,
             count(distinct his_service_req.id) as so_luong,
             sum(his_sere_serv.amount * his_sere_serv.price) as thanh_tien')
```

Không đổi gì khác trong method: JOIN, `whereBetween`, `where`, `groupBy` giữ nguyên.

- [ ] **Step 3: Kiểm tra cú pháp PHP**

Run: `php -l app/Http/Controllers/HomeController.php`
Expected: `No syntax errors detected in app/Http/Controllers/HomeController.php`

- [ ] **Step 4: Commit**

```bash
git add app/Http/Controllers/HomeController.php
git commit -m "fix: chart so_luong dem phieu his_service_req thay vi sum his_sere_serv.amount"
```

### Task 2: Kiểm thử trên dashboard

**Files:** (không sửa file — chỉ verify)

- [ ] **Step 1: Mở dashboard**

Truy cập `http://localhost:8000/`. Tìm chart "Doanh thu & Số lượng theo loại dịch vụ".

- [ ] **Step 2: Xác nhận "Số lượng"**

Số tổng "Số lượng" ở header chart phải giảm so với trước (số phiếu < tổng số dòng dịch vụ). Mỗi datalabel cột loại dịch vụ phản ánh số phiếu của loại đó.

- [ ] **Step 3: Xác nhận "Doanh thu" không đổi**

Tổng "Doanh thu" và doanh thu theo từng cột phải giữ nguyên y như trước khi sửa.

- [ ] **Step 4: Xác nhận không có lỗi**

Mở DevTools Console + Network. Request `fetchDoanhthuOverview` trả về 200, JSON hợp lệ, không có lỗi SQL.
