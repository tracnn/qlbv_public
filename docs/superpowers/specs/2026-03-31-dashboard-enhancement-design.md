# Dashboard Enhancement Design

## Overview

Bổ sung các tính năng còn thiếu cho dashboard bệnh viện mắt, dựa trên checklist đánh giá trong file Dashboard.docx. Phát triển trên nền kiến trúc hiện tại: Laravel 5.5 + Blade + jQuery + Highcharts, kết nối Oracle HISPro.

## Scope

3 nhóm tính năng, 8 endpoints tổng cộng:

1. **Thống kê theo bác sĩ** — lượt khám, doanh thu, ca phẫu thuật theo từng bác sĩ
2. **Xu hướng & vận hành** — biểu đồ xu hướng (daily/monthly), số BN/giờ khám, cảnh báo quá tải
3. **Công suất phòng mổ** — số ca PT/phòng/ngày, % thời gian sử dụng (chỉ phẫu thuật, type=4)

## Architecture

### Tech Stack (giữ nguyên)

- Backend: Laravel 5.5, PHP
- Frontend: Blade templates + jQuery IIFE modules + Highcharts
- Database: Oracle HISPro (remote connection)
- Data tables: Yajra DataTables

### New Files

```
app/Http/Controllers/Dashboard/
├── DoctorStatsController.php
├── TrendAnalysisController.php
└── OperatingRoomController.php

app/Services/Dashboard/
├── DoctorService.php
├── TrendService.php
└── OperatingRoomService.php

Services inject vào Controller qua constructor injection (Laravel IoC container):
```php
class DoctorStatsController extends Controller {
    protected $doctorService;
    public function __construct(DoctorService $doctorService) {
        $this->doctorService = $doctorService;
    }
}
```

public/js/dashboard/
├── doctor-stats.js
├── trend-charts.js
└── operating-room.js

resources/views/dashboard/
├── doctor-stats.blade.php        (hoặc @include sections)
├── trend-analysis.blade.php
└── operating-room.blade.php
```

### Routes

Đặt trong `routes/web.php` — project dùng Laravel Auth session-based, jQuery AJAX gửi kèm session cookie + CSRF token. Middleware `web` group (session, CSRF) là phù hợp. Lưu ý: `MedicalCenterDashboardController` đang đặt trong `api.php` là legacy decision không nhất quán, không nên follow.

```php
Route::prefix('dashboard')->middleware(['auth'])->group(function () {
    // Nhóm 1: Thống kê theo bác sĩ
    Route::get('doctor-stats/examinations', 'Dashboard\DoctorStatsController@examinations');
    Route::get('doctor-stats/revenue', 'Dashboard\DoctorStatsController@revenue');
    Route::get('doctor-stats/surgeries', 'Dashboard\DoctorStatsController@surgeries');

    // Nhóm 2: Xu hướng & vận hành
    Route::get('trends/chart', 'Dashboard\TrendAnalysisController@trendChart');
    Route::get('trends/patients-per-hour', 'Dashboard\TrendAnalysisController@patientsPerHour');
    Route::get('trends/overload-alert', 'Dashboard\TrendAnalysisController@overloadAlert');

    // Nhóm 3: Công suất phòng mổ
    Route::get('operating-room/cases-per-room', 'Dashboard\OperatingRoomController@casesPerRoom');
    Route::get('operating-room/utilization', 'Dashboard\OperatingRoomController@utilization');
});
```

## Data Sources (Oracle HISPro)

### Key Tables & Fields

| Table | Key Fields | Purpose |
|-------|-----------|---------|
| `his_service_req` | `execute_loginname`, `execute_username`, `service_req_type_id`, `start_time`, `finish_time`, `intruction_time`, `execute_room_id` | Yêu cầu dịch vụ (khám, PT, TT) |
| `his_sere_serv` | `service_req_id`, `vir_total_price`, `amount`, `ekip_id` | Dịch vụ thực hiện + giá + ekip |
| `his_ekip_user` | `ekip_id`, `loginname`, `username`, `execute_role_id` | Thành viên ekip mổ |
| `his_execute_role` | `id`, `execute_role_name`, `is_surg_main` | Vai trò trong ekip (PTV chính, phụ mổ...) |
| `his_execute_room` | `room_id`, `execute_room_name` | Phòng thực hiện (JOIN key = `room_id`, không phải `id`) |

### Service Request Type IDs

| Type ID | Meaning |
|---------|---------|
| 1 | Khám bệnh |
| 4 | Phẫu thuật |
| 5 | Thủ thuật (không dùng cho công suất phòng mổ) |

### Time Format

Oracle HISPro lưu thời gian dạng NUMBER: `YYYYMMDDHHmmSS` (ví dụ: `20260331083100`).

## Detailed Endpoint Specs

### Nhóm 1: DoctorStatsController

#### 1.1 GET /dashboard/doctor-stats/examinations

**Parameters:** `from` (number, YmdHis), `to` (number, YmdHis), `department_id` (optional)

**Query:**
```sql
SELECT sr.EXECUTE_LOGINNAME, sr.EXECUTE_USERNAME, COUNT(*) as total_exams
FROM HIS_SERVICE_REQ sr
WHERE sr.SERVICE_REQ_TYPE_ID = 1
  AND sr.IS_DELETE = 0 AND sr.IS_ACTIVE = 1
  AND sr.INTRUCTION_TIME BETWEEN :from AND :to
  AND (:department_id IS NULL OR sr.EXECUTE_ROOM_ID = :department_id)
GROUP BY sr.EXECUTE_LOGINNAME, sr.EXECUTE_USERNAME
ORDER BY total_exams DESC
```

**Response JSON:**
```json
{
  "data": [
    { "loginname": "vck", "username": "VŨ CÔNG KHANH", "total_exams": 450 }
  ]
}
```

**UI:** Bar chart ngang (top 10) + DataTable đầy đủ

#### 1.2 GET /dashboard/doctor-stats/revenue

**Parameters:** `from`, `to`, `department_id` (optional)

**Query:**
```sql
SELECT sr.EXECUTE_LOGINNAME, sr.EXECUTE_USERNAME,
       SUM(ss.VIR_TOTAL_PRICE) as total_revenue,
       COUNT(DISTINCT sr.TREATMENT_ID) as total_patients
FROM HIS_SERVICE_REQ sr
JOIN HIS_SERE_SERV ss ON ss.SERVICE_REQ_ID = sr.ID AND ss.IS_DELETE = 0 AND ss.IS_ACTIVE = 1
WHERE sr.IS_DELETE = 0 AND sr.IS_ACTIVE = 1
  AND sr.INTRUCTION_TIME BETWEEN :from AND :to
  AND (:department_id IS NULL OR sr.EXECUTE_ROOM_ID = :department_id)
GROUP BY sr.EXECUTE_LOGINNAME, sr.EXECUTE_USERNAME
ORDER BY total_revenue DESC
```

**Response JSON:**
```json
{
  "data": [
    { "loginname": "vck", "username": "VŨ CÔNG KHANH", "total_revenue": 125000000, "total_patients": 320 }
  ]
}
```

**UI:** Bar chart ngang (top 10) + DataTable

#### 1.3 GET /dashboard/doctor-stats/surgeries

**Parameters:** `from`, `to`, `surgery_type` (optional, default: chỉ PT type=4)

**Query:**
```sql
SELECT eu.LOGINNAME, eu.USERNAME, COUNT(*) as total_surgeries
FROM HIS_SERE_SERV ss
JOIN HIS_EKIP_USER eu ON eu.EKIP_ID = ss.EKIP_ID AND eu.IS_DELETE = 0
JOIN HIS_EXECUTE_ROLE er ON er.ID = eu.EXECUTE_ROLE_ID AND er.IS_SURG_MAIN = 1
JOIN HIS_SERVICE_REQ sr ON sr.ID = ss.SERVICE_REQ_ID AND sr.IS_DELETE = 0 AND sr.IS_ACTIVE = 1
WHERE ss.IS_DELETE = 0 AND ss.IS_ACTIVE = 1
  AND ss.EKIP_ID IS NOT NULL
  AND sr.SERVICE_REQ_TYPE_ID = 4
  AND sr.INTRUCTION_TIME BETWEEN :from AND :to
GROUP BY eu.LOGINNAME, eu.USERNAME
ORDER BY total_surgeries DESC
```

**Relationship chain:** `his_sere_serv.ekip_id` → `his_ekip.id` ← `his_ekip_user.ekip_id` (đã verify trên production data).

**UI:** Bar chart ngang + DataTable

### Nhóm 2: TrendAnalysisController

#### 2.1 GET /dashboard/trends/chart

**Parameters:** `from`, `to`, `mode` (daily|monthly), `metric` (examinations|revenue)

**Query (mode=daily, metric=examinations):**
```sql
SELECT TRUNC(sr.INTRUCTION_TIME / 1000000) as day_val, COUNT(*) as total
FROM HIS_SERVICE_REQ sr
WHERE sr.SERVICE_REQ_TYPE_ID = 1 AND sr.IS_DELETE = 0 AND sr.IS_ACTIVE = 1
  AND sr.INTRUCTION_TIME BETWEEN :from AND :to
GROUP BY TRUNC(sr.INTRUCTION_TIME / 1000000)
ORDER BY day_val
```

**Query (mode=monthly, metric=revenue):**
```sql
SELECT TRUNC(sr.INTRUCTION_TIME / 100000000) as month_val,
       SUM(ss.VIR_TOTAL_PRICE) as total
FROM HIS_SERVICE_REQ sr
JOIN HIS_SERE_SERV ss ON ss.SERVICE_REQ_ID = sr.ID AND ss.IS_DELETE = 0 AND ss.IS_ACTIVE = 1
WHERE sr.IS_DELETE = 0 AND sr.IS_ACTIVE = 1
  AND sr.INTRUCTION_TIME BETWEEN :from AND :to
GROUP BY TRUNC(sr.INTRUCTION_TIME / 100000000)
ORDER BY month_val
```

**Response JSON:**
```json
{
  "labels": ["01/03", "02/03", "03/03"],
  "current": [120, 135, 110],
  "previous": [100, 125, 115]
}
```

`previous`: dữ liệu kỳ trước — hiển thị nét đứt để so sánh.
- `mode=daily`: `previous` = cùng khoảng ngày nhưng của tháng trước (ví dụ: from 01/03→31/03, previous = 01/02→28/02)
- `mode=monthly`: `previous` = cùng khoảng tháng nhưng năm trước

Service layer chạy 2 query cùng cấu trúc: 1 cho `current` period, 1 cho `previous` period (shift thời gian tương ứng).

**UI:** Line chart (Highcharts) với toggle button Daily/Monthly. Đường current = nét liền, đường previous = nét đứt.

#### 2.2 GET /dashboard/trends/patients-per-hour

**Parameters:** `from`, `to`, `department_id` (optional)

**Query:**
```sql
SELECT FLOOR(MOD(sr.START_TIME, 1000000) / 10000) as hour_of_day,
       COUNT(*) as total_patients
FROM HIS_SERVICE_REQ sr
WHERE sr.SERVICE_REQ_TYPE_ID = 1 AND sr.IS_DELETE = 0 AND sr.IS_ACTIVE = 1
  AND sr.START_TIME IS NOT NULL
  AND sr.INTRUCTION_TIME BETWEEN :from AND :to
  AND (:department_id IS NULL OR sr.EXECUTE_ROOM_ID = :department_id)
GROUP BY FLOOR(MOD(sr.START_TIME, 1000000) / 10000)
ORDER BY hour_of_day
```

**KPI tổng hợp:** Tổng lượt khám ÷ tổng số ngày làm việc ÷ 8h = BN/giờ trung bình

**Response JSON:**
```json
{
  "average_per_hour": 15.2,
  "by_hour": [
    { "hour": 7, "count": 45 },
    { "hour": 8, "count": 120 },
    { "hour": 9, "count": 135 }
  ]
}
```

**UI:** KPI card lớn (số BN/giờ TB) + Bar chart theo khung giờ (7h-16h)

#### 2.3 GET /dashboard/trends/overload-alert

**Parameters:** `date` (number, YmdHis format cho ngày cần check)

**Query:**
```sql
-- Đếm lượt khám ngày hiện tại
SELECT COUNT(*) as today_count
FROM HIS_SERVICE_REQ sr
WHERE sr.SERVICE_REQ_TYPE_ID = 1
  AND sr.IS_DELETE = 0 AND sr.IS_ACTIVE = 1
  AND TRUNC(sr.INTRUCTION_TIME / 1000000) = :date_val

-- Trung bình 30 ngày gần nhất
SELECT COUNT(*) / 30 as avg_30d
FROM HIS_SERVICE_REQ sr
WHERE sr.SERVICE_REQ_TYPE_ID = 1
  AND sr.IS_DELETE = 0 AND sr.IS_ACTIVE = 1
  AND sr.INTRUCTION_TIME BETWEEN :from_30d AND :to_30d
```

**Logic:**
1. Đếm lượt khám ngày `date` (type=1)
2. Tính trung bình 30 ngày gần nhất (`:from_30d` = date - 30 ngày, `:to_30d` = date - 1 ngày)
3. So sánh: ratio = ngày hiện tại ÷ trung bình

**Ngưỡng:**
- ratio > 1.2 → `status: "overload"` (đỏ)
- ratio < 0.8 → `status: "underload"` (vàng)
- 0.8 ≤ ratio ≤ 1.2 → `status: "normal"` (xanh)

**Response JSON:**
```json
{
  "today_count": 180,
  "average_30d": 150,
  "ratio": 1.2,
  "status": "overload"
}
```

**UI:** Alert card trên đầu dashboard — icon + màu nền + text mô tả

### Nhóm 3: OperatingRoomController

#### 3.1 GET /dashboard/operating-room/cases-per-room

**Parameters:** `from`, `to`

**Query:**
```sql
SELECT sr.EXECUTE_ROOM_ID,
       er.EXECUTE_ROOM_NAME,
       TRUNC(sr.START_TIME / 1000000) as day_val,
       COUNT(*) as total_cases
FROM HIS_SERVICE_REQ sr
JOIN HIS_EXECUTE_ROOM er ON er.ROOM_ID = sr.EXECUTE_ROOM_ID
WHERE sr.SERVICE_REQ_TYPE_ID = 4
  AND sr.IS_DELETE = 0 AND sr.IS_ACTIVE = 1
  AND sr.START_TIME IS NOT NULL
  AND sr.INTRUCTION_TIME BETWEEN :from AND :to
GROUP BY sr.EXECUTE_ROOM_ID, er.EXECUTE_ROOM_NAME, TRUNC(sr.START_TIME / 1000000)
ORDER BY er.EXECUTE_ROOM_NAME, day_val
```

**Response JSON:**
```json
{
  "rooms": ["Phòng mổ 1", "Phòng mổ 2"],
  "dates": ["28/03", "29/03", "30/03"],
  "matrix": [[5, 3, 7], [4, 6, 2]]
}
```

**UI:** Heatmap chart (Highcharts) — trục X = ngày, trục Y = phòng, màu = số ca. DataTable chi tiết bên dưới.

#### 3.2 GET /dashboard/operating-room/utilization

**Parameters:** `from`, `to`

**Query:**
```sql
SELECT sr.EXECUTE_ROOM_ID,
       er.EXECUTE_ROOM_NAME,
       sr.START_TIME,
       sr.FINISH_TIME
FROM HIS_SERVICE_REQ sr
JOIN HIS_EXECUTE_ROOM er ON er.ROOM_ID = sr.EXECUTE_ROOM_ID
WHERE sr.SERVICE_REQ_TYPE_ID = 4
  AND sr.IS_DELETE = 0 AND sr.IS_ACTIVE = 1
  AND sr.START_TIME IS NOT NULL AND sr.FINISH_TIME IS NOT NULL
  AND sr.INTRUCTION_TIME BETWEEN :from AND :to
ORDER BY er.EXECUTE_ROOM_NAME, sr.START_TIME
```

**Time calculation (trong Service layer — PHP):**
Không dùng phép trừ NUMBER trực tiếp vì format YmdHis cho kết quả vô nghĩa.
Thay vào đó, parse từng record trong PHP:
```php
$start = Carbon::createFromFormat('YmdHis', $row->START_TIME);
$end = Carbon::createFromFormat('YmdHis', $row->FINISH_TIME);
$minutes = $start->diffInMinutes($end);
```
Sau đó group by `room_id` + ngày, sum minutes trong PHP.

**Utilization calculation (trong Service):**
```
utilization_pct = (total_minutes_used / (working_days * 480)) * 100
```
Trong đó 480 = 8h × 60 phút.

**Response JSON:**
```json
{
  "data": [
    {
      "room_name": "Phòng mổ 1",
      "total_cases": 45,
      "total_minutes": 2160,
      "working_days": 22,
      "utilization_pct": 20.45,
      "status": "underload"
    }
  ]
}
```

**Ngưỡng:**
- \>100% → `"overload"` (đỏ)
- 70-100% → `"optimal"` (xanh)
- <70% → `"underload"` (vàng)

**UI:** Bar chart ngang — mỗi phòng 1 bar hiển thị %, đường ngưỡng 100% đỏ, vùng 70-100% xanh nhạt. DataTable chi tiết bên dưới.

## UI Layout

Các tính năng mới sẽ được tổ chức thành **tabs hoặc sections** trong trang dashboard, phù hợp với layout AdminLTE hiện tại:

1. **Tab "Theo bác sĩ"** — 3 cards: khám / doanh thu / phẫu thuật
2. **Tab "Xu hướng"** — alert card trên cùng + line chart + bar chart BN/giờ
3. **Tab "Phòng mổ"** — heatmap + bar chart công suất

Mỗi tab có bộ filter chung: date range picker (from/to) + department dropdown (nếu áp dụng).

## Error Handling

- Oracle connection timeout: retry 1 lần, sau đó trả JSON `{ "error": "Không thể kết nối HIS" }`
- Không có dữ liệu: trả mảng rỗng, UI hiển thị "Không có dữ liệu trong khoảng thời gian này"
- Invalid parameters: validate trong Controller, trả HTTP 422

## Performance Considerations

- Oracle queries trên bảng lớn (`his_service_req`, `his_sere_serv` — hàng triệu records): cần filter `INTRUCTION_TIME` (indexed) trước
- Không cache vì dữ liệu realtime
- DataTable server-side pagination cho danh sách chi tiết
- Chart data giới hạn aggregation (không trả raw records)
