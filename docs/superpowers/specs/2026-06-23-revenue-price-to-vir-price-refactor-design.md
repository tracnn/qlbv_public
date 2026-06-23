# Spec: Đồng bộ doanh thu dùng `vir_price` (Phần B — refactor báo cáo cũ)

**Date:** 2026-06-23
**Status:** Approved (chờ user review spec)

> **Bối cảnh:** Phần A (report doanh thu theo khoa/phòng) đã dùng `vir_price`. Phần B này rà soát & đổi **tất cả** chỗ tính doanh thu / hiển thị đơn giá từ `his_sere_serv.price` → `his_sere_serv.vir_price` cho các báo cáo cũ, để toàn hệ thống nhất quán.

---

## 1. Mục tiêu

Mọi tính toán **doanh thu** và **đơn giá hiển thị** dựa trên `his_sere_serv` phải dùng **`vir_price`** (đơn giá ảo/thực thu) thay cho `price` (giá niêm yết). Áp dụng cho cả báo cáo web lẫn báo cáo chạy nền/email.

---

## 2. Cơ sở quyết định (đã phân tích dữ liệu thực)

- `vir_price` = đơn giá **thực thu** mỗi dòng dịch vụ. Khi có tính tiền (`vir_price > 0`), **`price = vir_price` y hệt** (0 dòng khác biệt trong tuần khảo sát). Ca "BHYT dùng giá yêu cầu" (vd Khoa khám Yêu cầu): `price = vir_price = 200.000` (giá yêu cầu cao) — **vir_price bắt đúng**.
- Khác biệt duy nhất giữa `price` và `vir_price`: **vật tư/thuốc bundled** có `vir_price = 0` (đã gộp vào giá DVKT/gói, không tính tiền riêng). Dùng `price` cho các dòng này = **trùng lặp** (+~597 Tr/tuần). Dùng `vir_price` = doanh thu thực, không trùng.
- Tổng tham chiếu tuần 01–07/06: `amount*price` ≈ 10.182 Tr → `amount*vir_price` ≈ **9.585 Tr** (giảm ~5,9%).
- `vir_price` cũng là trường các báo cáo kế toán sẵn có đang dùng (AccountantController, DoctorService, PatientController revenue, ReportDataService total...). Đổi sang vir_price → đồng bộ toàn hệ thống.

---

## 3. Phạm vi thay đổi (chỉ `his_sere_serv.price`)

> Quy tắc: `amount * price` → `amount * vir_price`; `his_sere_serv.price` (cột đơn giá hiển thị) → `his_sere_serv.vir_price`. Giữ nguyên alias (thanh_tien/So_tien/Don_gia/price/q...) để không vỡ chỗ đọc.

### 3.1. Doanh thu (SUM `amount * price` → `amount * vir_price`)

| File | Dòng (tham chiếu) | Ghi chú |
|---|---|---|
| `app/Http/Controllers/HomeController.php` | doanhthuByDepartment (`sum(...price)` + `havingRaw(...price > 0)`), doanhthu loại DV×ĐT, doanhthuOverview, biểu đồ top BS DVKT theo tiền (`sum(amount*price) as so_luong`) | đổi cả biểu thức trong `havingRaw`; alias `so_luong` của top-BS thực ra là tiền → giữ alias |
| `app/Http/Controllers/KHTH/KHTHController.php` | `sum(amount*price) as thanh_tien` (chi phí KCB), `sum(amount*price) as So_tien` (×2), `sum(amount*price) as thanh_tien` (DVKT), `ss.amount * ss.price AS q` (×2, thống kê DT) | |
| `app/Http/Controllers/ApiController.php` | `sum(amount*price) as thanh_tien` | |
| `app/Services/ReportDataService.php` | `ss.amount * ss.price AS q` (×2), pivot `SUM(... hss.amount * hss.price ...) AS tt{suffix}` | |
| `app/Console/Commands/HISProBaoCaoQuanTri.php` | `sum(amount*price) as thanh_tien` (×2) | báo cáo email — đổi |
| `app/Console/Commands/HISProBaoCaoCacKhoa.php` | `sum(amount*price) as thanh_tien` (×2) | báo cáo email — đổi |

### 3.2. Đơn giá hiển thị từng dòng (`his_sere_serv.price` → `his_sere_serv.vir_price`)

| File | Chỗ | Ghi chú |
|---|---|---|
| `app/Http/Controllers/KHTH/KHTHController.php` | `'his_sere_serv.price as Don_gia'`; `'his_sere_serv.price'` trong select chi tiết (báo cáo NVYT chỉ định y lệnh, ~dòng 755) | đổi `his_sere_serv.vir_price as Don_gia` / `his_sere_serv.vir_price as price` |
| `app/Exports/DVKTExport.php` | `'his_sere_serv.price'` | đổi `his_sere_serv.vir_price as price` (giữ alias `price` để khớp cột DataTables `data:"price"` ở `dich-vu-ky-thuat-index.blade`) |
| `app/Http/Controllers/PatientController.php` | `'s.price as price'` (×2; `s` = his_sere_serv) | đổi `s.vir_price as price` |

> **Lưu ý cột DataTables:** view `dich-vu-ky-thuat-index.blade.php` map `{data:"price"}`. Giữ alias `price` khi đổi nguồn → cột tự lấy giá trị vir_price, không cần sửa view. Cần **xác minh fetch của DVKT index** (controller) lấy cùng nguồn — nếu cũng `his_sere_serv.price` thì đổi theo.

---

## 4. CỐ Ý GIỮ NGUYÊN (không đổi)

- **Filter theo giá niêm yết** trong `HISProBaoCaoQuanTri.php`: `where('price', '<>', 0)` (lọc dòng có giá) và `where('price', 0)` (×2 — đếm dịch vụ **miễn phí**). Đây là **điều kiện nghiệp vụ** nhận diện dịch vụ miễn/có phí theo giá niêm yết; đổi sang vir_price sẽ lẫn vật tư bundled (vir=0) → SAI ngữ nghĩa. **Giữ nguyên `price`.**
- **Giá catalog** (không thuộc `his_sere_serv`): `his_service` / `his_service_price` / `sp.price` (HisServicePriceSearchService, category service-price, patient view-guide `i.price`), nhãn ngôn ngữ `__('...price')` (insurance.php) — **ngoài phạm vi**.
- Các trường đã đúng `vir_*` sẵn (AccountantController, DoctorService, TrendService, ReportDataService total, CheckEmrService, PatientController total) — **không đụng**.

---

## 5. Kiến trúc / cách làm

- Đây là **refactor cơ học** trong chuỗi SQL (`selectRaw`/raw SQL/query builder `select`), **không** thêm lớp trừu tượng (mỗi chỗ là 1 chuỗi SQL riêng; YAGNI). Thay đúng `price` → `vir_price` theo danh sách mục 3.
- **Không có logic thuần mới** để unit-test. Kiểm chứng bằng so sánh tổng trước/sau (mục 6).
- Giữ nguyên alias cột để không vỡ tầng đọc (controller/JS/view/export).

---

## 6. Kiểm chứng (verification)

Với mỗi báo cáo đổi, kiểm qua oci8/tinker hoặc HTTP trên 1 khoảng ngày cố định (vd 01–07/06/2026):
- **Tổng doanh thu giảm ~5,9%** so với trước (price→vir), khớp tham chiếu (10.182 → 9.585 Tr cho toàn bộ; từng báo cáo theo tỷ lệ tương ứng phạm vi của nó).
- Ca BHYT-yêu-cầu vẫn ra **200.000** (không bị tụt xuống giá BHYT).
- Vật tư bundled (vir=0) không còn cộng vào doanh thu.
- Không lỗi cú pháp SQL; báo cáo vẫn chạy (HTTP 200 / tinker OK).
- Đơn giá hiển thị: dòng có tính tiền không đổi; dòng bundled hiện Đơn giá = 0.

---

## 7. Out of scope (YAGNI)

- Không đổi giá catalog (danh mục dịch vụ).
- Không đổi các filter `where('price',...)` (giữ nguyên — mục 4).
- Không tạo helper/abstraction chung cho công thức doanh thu.
- Không đổi cấu trúc báo cáo, chỉ đổi trường giá.
