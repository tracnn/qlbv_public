# Đồng bộ doanh thu dùng `vir_price` — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Đổi mọi tính toán doanh thu (`amount*price`) và đơn giá hiển thị (`his_sere_serv.price`) sang `vir_price` trên toàn bộ báo cáo cũ, để nhất quán với report Phần A.

**Architecture:** Refactor cơ học trong các chuỗi SQL (`selectRaw` / raw SQL / query builder `select`). Mỗi chỗ là một chuỗi SQL độc lập — không thêm lớp trừu tượng (YAGNI). Giữ nguyên alias cột (`thanh_tien`/`So_tien`/`Don_gia`/`price`/`q`/`so_luong`) để không vỡ tầng đọc (controller/JS/view/export). Cố ý GIỮ NGUYÊN các filter `where('price',...)` và mọi giá catalog (his_service/his_service_price).

**Tech Stack:** Laravel 5.5, Oracle 12c (yajra/laravel-oci8), Maatwebsite Excel, Yajra Datatables.

**Spec:** `docs/superpowers/specs/2026-06-23-revenue-price-to-vir-price-refactor-design.md`

**Verification note:** Không có hạ tầng test khả thi cho các chuỗi SQL chạy trực tiếp trên Oracle 35M dòng (live DB, không seed được). Kiểm chứng = (a) grep xác nhận đã đổi đúng phạm vi & filter còn nguyên; (b) smoke `php artisan tinker` so tổng `amount*price` vs `amount*vir_price` trên một kỳ cố định; (c) gọi báo cáo HTTP 200. Mỗi task có bước grep kiểm tra cục bộ.

---

### Task 1: HomeController — doanh thu (5 chỗ)

**Files:**
- Modify: `app/Http/Controllers/HomeController.php` (dòng 315, 321, 559, 1476, 1771)

- [ ] **Step 1: Đổi doanhthuByDepartment (315 + havingRaw 321)**

Dòng 315 — old:
```php
                         sum(his_sere_serv.amount * his_sere_serv.price) as thanh_tien')
```
new:
```php
                         sum(his_sere_serv.amount * his_sere_serv.vir_price) as thanh_tien')
```

Dòng 321 — old:
```php
            ->havingRaw('sum(his_sere_serv.amount * his_sere_serv.price) > 0') // loại khoa không có doanh thu
```
new:
```php
            ->havingRaw('sum(his_sere_serv.amount * his_sere_serv.vir_price) > 0') // loại khoa không có doanh thu
```

- [ ] **Step 2: Đổi doanhthu loại DV × đối tượng (559)**

old:
```php
                     sum(his_sere_serv.amount * his_sere_serv.price) as thanh_tien')
```
new:
```php
                     sum(his_sere_serv.amount * his_sere_serv.vir_price) as thanh_tien')
```
> Lưu ý: dòng 315 và 559 có cùng nội dung. Dùng context (thụt đầu dòng khác nhau, hoặc edit lần lượt) để sửa đúng cả hai. Cả hai đều đổi sang `vir_price`.

- [ ] **Step 3: Đổi 1476 + 1771**

Dòng 1476 — old:
```php
        ->selectRaw('sum(his_sere_serv.amount*his_sere_serv.price) as thanh_tien,
```
new:
```php
        ->selectRaw('sum(his_sere_serv.amount*his_sere_serv.vir_price) as thanh_tien,
```

Dòng 1771 (top BS DVKT theo tiền — alias `so_luong` thực ra là tiền, giữ alias) — old:
```php
            ->selectRaw('sum(amount*price) as so_luong,tdl_request_username')
```
new:
```php
            ->selectRaw('sum(amount*vir_price) as so_luong,tdl_request_username')
```

- [ ] **Step 4: Verify grep**

Run: `grep -nE "amount ?\* ?(his_sere_serv\.)?price" app/Http/Controllers/HomeController.php`
Expected: không còn dòng nào (0 kết quả).

- [ ] **Step 5: Commit**

```bash
git add app/Http/Controllers/HomeController.php
git commit -m "refactor: doanh thu Home dung vir_price thay price"
```

---

### Task 2: KHTHController — doanh thu (6 chỗ) + đơn giá hiển thị (2 chỗ)

**Files:**
- Modify: `app/Http/Controllers/KHTH/KHTHController.php` (320, 825, 829, 1196, 1892, 1937 = doanh thu; 755, 848 = hiển thị)

- [ ] **Step 1: Doanh thu — selectRaw (320, 1196)**

Dòng 320 — old:
```php
            ->selectRaw('sum(amount*price) as thanh_tien,service_type_name')
```
new:
```php
            ->selectRaw('sum(amount*vir_price) as thanh_tien,service_type_name')
```

Dòng 1196 — old:
```php
                ->selectRaw('sum(amount*price) as thanh_tien, sum(amount) as so_luong, service_type_name')
```
new:
```php
                ->selectRaw('sum(amount*vir_price) as thanh_tien, sum(amount) as so_luong, service_type_name')
```

- [ ] **Step 2: Doanh thu — So_tien (825, 829)**

Cả hai dòng giống nhau — old (mỗi chỗ):
```php
                        sum(his_sere_serv.amount * his_sere_serv.price) as So_tien');
```
new:
```php
                        sum(his_sere_serv.amount * his_sere_serv.vir_price) as So_tien');
```
> Sửa cả 2 lần xuất hiện (825 và 829), cùng nội dung.

- [ ] **Step 3: Doanh thu — raw `q` (1892, 1937)**

Cả hai dòng giống nhau — old (mỗi chỗ):
```php
                ss.amount * ss.price AS q
```
new:
```php
                ss.amount * ss.vir_price AS q
```
> Sửa cả 2 lần (1892 và 1937).

- [ ] **Step 4: Hiển thị — get_dvkt cột price (755)**

old (trong danh sách select của `get_dvkt`):
```php
				'his_sere_serv.tdl_request_username', 'his_sere_serv.amount', 'his_sere_serv.price', 
```
new:
```php
				'his_sere_serv.tdl_request_username', 'his_sere_serv.amount', 'his_sere_serv.vir_price as price', 
```
> Giữ alias `price` để khớp cột DataTables `{data:"price"}` ở `dich-vu-ky-thuat-index.blade.php`.

- [ ] **Step 5: Hiển thị — Don_gia (848)**

old:
```php
                        'his_sere_serv.price as Don_gia');
```
new:
```php
                        'his_sere_serv.vir_price as Don_gia');
```

- [ ] **Step 6: Verify grep**

Run: `grep -nE "amount ?\* ?price|ss\.price|his_sere_serv\.price" app/Http/Controllers/KHTH/KHTHController.php`
Expected: 0 kết quả.

- [ ] **Step 7: Commit**

```bash
git add app/Http/Controllers/KHTH/KHTHController.php
git commit -m "refactor: doanh thu + don gia KHTH dung vir_price"
```

---

### Task 3: ApiController — doanh thu (1 chỗ)

**Files:**
- Modify: `app/Http/Controllers/ApiController.php` (505)

- [ ] **Step 1: Đổi 505**

old:
```php
        ->selectRaw('sum(amount) as so_luong,sum(amount*price) as thanh_tien,tdl_service_type_id,service_type_name')
```
new:
```php
        ->selectRaw('sum(amount) as so_luong,sum(amount*vir_price) as thanh_tien,tdl_service_type_id,service_type_name')
```

- [ ] **Step 2: Verify grep**

Run: `grep -nE "amount\*price" app/Http/Controllers/ApiController.php`
Expected: 0 kết quả.

- [ ] **Step 3: Commit**

```bash
git add app/Http/Controllers/ApiController.php
git commit -m "refactor: doanh thu Api dung vir_price"
```

---

### Task 4: ReportDataService — doanh thu (3 chỗ)

**Files:**
- Modify: `app/Services/ReportDataService.php` (461, 588, 727)

- [ ] **Step 1: Đổi raw `q` (461, 588)**

Cả hai dòng giống nhau — old (mỗi chỗ):
```php
                    ss.amount * ss.price AS q
```
new:
```php
                    ss.amount * ss.vir_price AS q
```
> Sửa cả 2 lần (461 và 588).

- [ ] **Step 2: Đổi pivot (727)**

old:
```php
                $pivotCols[] = "SUM(CASE WHEN hpt.id = {$ptId} AND htt.id = {$ttId} THEN hss.amount * hss.price ELSE 0 END) AS tt{$suffix}";
```
new:
```php
                $pivotCols[] = "SUM(CASE WHEN hpt.id = {$ptId} AND htt.id = {$ttId} THEN hss.amount * hss.vir_price ELSE 0 END) AS tt{$suffix}";
```

- [ ] **Step 3: Verify grep**

Run: `grep -nE "ss\.amount \* ss\.price|hss\.amount \* hss\.price" app/Services/ReportDataService.php`
Expected: 0 kết quả.

- [ ] **Step 4: Commit**

```bash
git add app/Services/ReportDataService.php
git commit -m "refactor: doanh thu ReportDataService dung vir_price"
```

---

### Task 5: Console commands báo cáo email — doanh thu (4 chỗ)

**Files:**
- Modify: `app/Console/Commands/HISProBaoCaoQuanTri.php` (121, 136)
- Modify: `app/Console/Commands/HISProBaoCaoCacKhoa.php` (82, 99)

- [ ] **Step 1: HISProBaoCaoQuanTri (121, 136)**

Dòng 121 — old:
```php
        ->selectRaw('sum(amount) as so_luong,sum(amount*price) as thanh_tien,tdl_service_type_id,service_type_name')
```
new:
```php
        ->selectRaw('sum(amount) as so_luong,sum(amount*vir_price) as thanh_tien,tdl_service_type_id,service_type_name')
```

Dòng 136 — old:
```php
        ->selectRaw('sum(amount*price) as thanh_tien,patient_type_name,branch_name')
```
new:
```php
        ->selectRaw('sum(amount*vir_price) as thanh_tien,patient_type_name,branch_name')
```
> KHÔNG đụng `where('price','<>',0)` (139) và `where('price',0)` (389, 470) — filter nghiệp vụ, giữ nguyên.

- [ ] **Step 2: HISProBaoCaoCacKhoa (82, 99)**

Dòng 82 — old:
```php
            ->selectRaw('sum(amount) as so_luong,sum(amount*price) as thanh_tien,tdl_service_name')
```
new:
```php
            ->selectRaw('sum(amount) as so_luong,sum(amount*vir_price) as thanh_tien,tdl_service_name')
```

Dòng 99 — old:
```php
            ->selectRaw('sum(amount) as so_luong,sum(amount*price) as thanh_tien,tdl_service_name,tdl_service_code')
```
new:
```php
            ->selectRaw('sum(amount) as so_luong,sum(amount*vir_price) as thanh_tien,tdl_service_name,tdl_service_code')
```

- [ ] **Step 3: Verify grep (chỉ selectRaw đổi, filter còn nguyên)**

Run: `grep -nE "amount\*price|amount\*vir_price|where\('price'" app/Console/Commands/HISProBaoCaoQuanTri.php app/Console/Commands/HISProBaoCaoCacKhoa.php`
Expected: thấy `amount*vir_price` (4 chỗ) và `where('price'...` (3 chỗ HISProBaoCaoQuanTri); KHÔNG còn `amount*price`.

- [ ] **Step 4: Commit**

```bash
git add app/Console/Commands/HISProBaoCaoQuanTri.php app/Console/Commands/HISProBaoCaoCacKhoa.php
git commit -m "refactor: doanh thu bao cao email dung vir_price"
```

---

### Task 6: DVKTExport + PatientController — đơn giá hiển thị (3 chỗ)

**Files:**
- Modify: `app/Exports/DVKTExport.php` (57)
- Modify: `app/Http/Controllers/PatientController.php` (377, 410)

- [ ] **Step 1: DVKTExport (57)**

old:
```php
                'his_sere_serv.price'
```
new:
```php
                'his_sere_serv.vir_price as price'
```
> Giữ alias `price` để khớp cột export & DataTables.

- [ ] **Step 2: PatientController (377, 410)**

Dòng 377 — old:
```php
            's.price as price'
```
new:
```php
            's.vir_price as price'
```

Dòng 410 — old:
```php
            's.price as price',
```
new:
```php
            's.vir_price as price',
```
> `s` = alias của `his_sere_serv` (join `his_sere_serv as s`). Giữ alias `price`.

- [ ] **Step 3: Verify grep**

Run: `grep -nE "his_sere_serv\.price|s\.price as" app/Exports/DVKTExport.php app/Http/Controllers/PatientController.php`
Expected: 0 kết quả (chỉ còn `s.vir_price as price`).

- [ ] **Step 4: Commit**

```bash
git add app/Exports/DVKTExport.php app/Http/Controllers/PatientController.php
git commit -m "refactor: don gia DVKTExport + Patient dung vir_price"
```

---

### Task 7: Verify toàn cục + smoke + push

**Files:** (không sửa code, chỉ kiểm tra)

- [ ] **Step 1: Grep toàn cục xác nhận hết phạm vi**

Run: `grep -rnE "amount ?\* ?(his_sere_serv\.|ss\.|hss\.)?price|his_sere_serv\.price|s\.price as price" app/ --include=*.php`
Expected: 0 kết quả trong app/ (mọi doanh thu/đơn giá his_sere_serv đã sang vir_price). Nếu còn → đối chiếu spec mục 3, sửa nốt.

- [ ] **Step 2: Xác nhận filter & catalog còn nguyên (không bị đổi nhầm)**

Run: `grep -rnE "where\('price'|sp\.price|->where\('price'" app/ --include=*.php`
Expected: vẫn còn `where('price'...)` ở HISProBaoCaoQuanTri (3 chỗ) và `sp.price` ở HisServicePriceSearchService — ĐÚNG, không đổi.

- [ ] **Step 3: Lint PHP các file đã sửa**

Run:
```bash
for f in app/Http/Controllers/HomeController.php app/Http/Controllers/KHTH/KHTHController.php app/Http/Controllers/ApiController.php app/Services/ReportDataService.php app/Console/Commands/HISProBaoCaoQuanTri.php app/Console/Commands/HISProBaoCaoCacKhoa.php app/Exports/DVKTExport.php app/Http/Controllers/PatientController.php; do php -l "$f"; done
```
Expected: `No syntax errors detected` cho từng file.

- [ ] **Step 4: Smoke so tổng price vs vir_price (tinker)**

Run: `php artisan tinker`, rồi (kỳ cố định 01–07/06/2026):
```php
$r = DB::connection('HISPro')->table('his_sere_serv')
  ->whereBetween('tdl_intruction_date', ['20260601000000','20260607235959'])
  ->where('is_active',1)->where('is_delete',0)
  ->selectRaw('sum(amount*price) as p, sum(amount*vir_price) as v')->first();
echo "price={$r->p} vir={$r->v} delta=".round(($r->p-$r->v)/$r->p*100,2)."%";
```
Expected: `vir < price`, delta ~5–6% (khớp tham chiếu ~5,9%). Nếu delta=0 → DB kỳ này không có vật tư bundled, vẫn hợp lệ.

- [ ] **Step 5: Push**

```bash
git push origin main
```

---

## Hoàn tất

Sau khi 7 task xong: mọi doanh thu & đơn giá dựa trên `his_sere_serv` dùng `vir_price`; filter nghiệp vụ và giá catalog giữ nguyên; alias cột không đổi nên view/JS/export không cần sửa.
