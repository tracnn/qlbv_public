# Spec: Loại patient_type khỏi thống kê dashboard (KSK đoàn/từ thiện)

**Date:** 2026-06-24
**Status:** Approved (chờ user review spec)

---

## 1. Mục tiêu

Dashboard Home phục vụ thống kê khám chữa bệnh (KCB). Một số loại đối tượng (`patient_type`) như **Khám sức khỏe (KSK)** — gồm khám đoàn & từ thiện — không được tính vào thống kê KCB. Cho phép **cấu hình danh sách patient_type cần loại**, áp dụng **nhất quán trên toàn bộ chỉ số dashboard**.

**Quyết định đã chốt với user:**
- Nhận diện bằng **nguyên `patient_type`** (theo id), không cần trường phân biệt mịn hơn.
- Danh sách mặc định = **KSK (id 43, code 03)**. Cấu hình được để mở rộng sau.
- Áp cho **toàn bộ** chỉ số dashboard (doanh thu, đếm BN, DVKT, đơn thuốc, viện phí, giường, giao dịch...).

---

## 2. Cơ sở dữ liệu (đã khảo sát)

Bảng `his_patient_type` chỉ có 8 loại; **một** loại KSK duy nhất (id 43, code 03) — khám đoàn & từ thiện nằm chung trong đó (không tách ở tầng patient_type). Các loại còn lại: BHYT(1), Viện Phí(42), Hợp đồng CLS(142), Vacxin(102), Yêu cầu(82), Hợp đồng(62), Covid-19(122, inactive).

Cột patient_type theo bảng nguồn (đã xác minh):
- `his_sere_serv.patient_type_id` — có.
- `his_treatment.tdl_patient_type_id` — có.
- `his_service_req.tdl_patient_type_id` — có (đã xác minh).
- `his_transaction` — **không** có; lọc qua join `his_treatment.tdl_patient_type_id`.

---

## 3. Kiến trúc: Config + helper dùng chung (Hướng A)

Thêm điều kiện loại-trừ vào từng query dashboard qua một helper chung, danh sách lấy từ config. Không tách Service riêng (refactor lớn, ngoài phạm vi); không làm UI quản lý (YAGNI).

### 3.1. Config
Thêm khối vào `config/organization.php`:
```php
'dashboard' => [
    // patient_type_id KHÔNG tính vào thống kê KCB của dashboard Home (KSK đoàn/từ thiện...)
    'exclude_patient_type_ids' => [43], // 43 = KSK (code 03)
],
```
- Dùng **id** (không dùng code): mọi query lọc trực tiếp trên cột id, không cần join/lookup.
- Mảng rỗng ⇒ không loại gì (mặc định an toàn, không đổi hành vi).

### 3.2. Helper (private, trong `HomeController`)
```php
private function excludedPatientTypeIds(): array
{
    return array_map('intval', config('organization.dashboard.exclude_patient_type_ids', []));
}

/** Gắn whereNotIn patient_type nếu config có danh sách. Trả lại chính $query để chain. */
private function applyExcludePatientType($query, string $column)
{
    $ids = $this->excludedPatientTypeIds();
    if (!empty($ids)) {
        $query->whereNotIn($column, $ids);
    }
    return $query;
}
```

### 3.3. Điểm áp lọc (theo method → cột)

> Quy tắc: gọi `$this->applyExcludePatientType($query, '<cột>')` ngay trong builder, trước `->get()`. Method raw SQL chèn mệnh đề chuỗi.

**Nhóm `his_sere_serv.patient_type_id`:**
- `fetchServiceByMachine` (dịch vụ theo máy)
- `doanhthuByDepartment`
- `doanhthuOverview`
- `doanhthu`
- `getExamAndParraclinical` (khám & cận lâm sàng)
- `getDiagnoticImaging` (CĐHA)
- `serviceByType` (dịch vụ theo loại)
- 2 chart top bác sĩ theo tiền/số lượng (các query `his_sere_serv` ~dòng 1729, 1771)

**Nhóm `his_treatment.tdl_patient_type_id`:**
- `getDetailDayCountInpatient` (ngày điều trị nội trú TB)
- `getPrescription` (đơn thuốc — base `his_service_req` join `his_treatment`; lọc trên `his_treatment.tdl_patient_type_id`)
- `getFee` (viện phí)
- `treatmentsByTreatmentEndType`
- `getTransactionDetail` (giao dịch — `his_transaction` join `his_treatment`)
- `getTreatmentByTreatmentType`
- `newpatient`
- `inTreatment`
- `reExamination`
- `outTreatment`
- `getPatientInRoomByTreatmentType` (BN trong buồng ngoại trú)
- các query đếm điều trị khác trên `his_treatment` (~dòng 1648, 1688, 1813, 1856)

**Nhóm `his_service_req.tdl_patient_type_id`:**
- `fetchKhamByRoom` (khám theo phòng — base `his_service_req`)

**Method raw SQL — `bedStatusByDepartment`:**
- Chèn `AND t.tdl_patient_type_id NOT IN (<ids>)` vào CTE `dang` (giường đang dùng). `<ids>` dựng từ `excludedPatientTypeIds()` (đã `intval`, an toàn để nội suy; hoặc dùng bind `:excl_pt`). CTE `tong` (tổng giường = năng lực giường) **không** đụng.
- Nếu danh sách rỗng ⇒ không chèn mệnh đề.

> Khi implement: mỗi method sau khi sửa phải xác minh cột patient_type nằm đúng bảng đang query (tránh ORA-00904). Với method chỉ đếm giường năng lực (`tong` trong bedStatus) hoặc không liên quan BN thì bỏ qua.

---

## 4. Xử lý lỗi & biên

- Config thiếu key / rỗng ⇒ `excludedPatientTypeIds()` trả `[]` ⇒ helper không thêm mệnh đề ⇒ dashboard giữ nguyên hành vi cũ (không vỡ).
- Giá trị id không hợp lệ (không tồn tại) ⇒ `whereNotIn` vô hại (không khớp dòng nào).
- Không đổi alias/cột trả về ⇒ JS/chart không cần sửa.

---

## 5. Kiểm thử

**Unit test** (`tests/Unit/...`), không cần DB:
- `applyExcludePatientType` với config `[43]` ⇒ query có `whereNotIn('col',[43])` (kiểm qua `$query->toSql()` / bindings trên một query builder giả lập kết nối, hoặc test thuần logic tách riêng).
- Config rỗng ⇒ không thêm mệnh đề (SQL không chứa `not in`).
- `excludedPatientTypeIds` ép kiểu int đúng.

**Smoke trên Oracle** (kỳ cố định, vd 01–23/06/2026):
- So một chỉ số (tổng lượt khám hoặc doanh thu) **trước** và **sau** khi loại KSK ⇒ sau nhỏ hơn đúng bằng phần KSK; các patient_type khác không đổi.
- Không lỗi SQL; các fetch dashboard trả HTTP 200.

---

## 6. Out of scope (YAGNI)

- Không tạo UI/CRUD quản lý danh sách loại-trừ (chỉnh trực tiếp trong config).
- Không áp cho báo cáo KHTH hay các màn khác — chỉ dashboard Home.
- Không tách patient_type mịn hơn (đoàn vs từ thiện) — loại nguyên loại theo quyết định đã chốt.
- Không đổi công thức các chỉ số, chỉ thêm điều kiện loại patient_type.
