# Spec: Dashboard TV — Phòng Khám Theo Trạng Thái

**Date:** 2026-05-06  
**Status:** Approved  

---

## Mục tiêu

Tạo một trang dashboard độc lập, không yêu cầu xác thực, hiển thị biểu đồ cột thống kê số lượng bệnh nhân theo từng phòng khám và trạng thái thực hiện. Trang được thiết kế để hiển thị trên TV/màn hình lớn tại bệnh viện.

---

## Yêu cầu

- Không cần đăng nhập để truy cập
- Hiển thị trên TV/màn hình lớn (fullscreen)
- Tự động refresh dữ liệu mỗi **5 phút** (AJAX, không tải lại trang)
- Hiển thị đồng hồ/ngày giờ thực cập nhật mỗi giây
- Hiển thị tên bệnh viện từ `config('organization.organization_name')`
- Biểu đồ cột nhóm theo phòng thực hiện với 3 trạng thái
- Hiển thị số liệu trực tiếp trên từng cột

---

## Routes (public)

Thêm vào `routes/web.php` trong khu vực public (cùng chỗ với `/dashboard` hiện tại, dòng 22–30):

```php
Route::get('phong-kham-tv', 'KHTH\KHTHController@phongKhamTv')->name('khth.phong-kham-tv');
Route::get('khth/chart-phong-kham', 'KHTH\KHTHController@chartPhongKham')->name('khth.chart-phong-kham');
```

---

## API: `chartPhongKham(Request $request)`

**URL:** `GET /khth/chart-phong-kham`  
**Auth:** Không yêu cầu  
**Điều kiện gọi:** Không cần AJAX check (khác với các endpoint cũ)

### Logic truy vấn

Một query duy nhất lấy tất cả phòng × trạng thái trong ngày hôm nay:

```sql
SELECT 
    his_execute_room.execute_room_name,
    his_service_req.service_req_stt_id,
    COUNT(*) as so_luong
FROM his_service_req
JOIN his_execute_room ON his_execute_room.room_id = his_service_req.execute_room_id
WHERE intruction_time >= [today_start]
  AND intruction_time <= [today_end]
  AND service_req_type_id = 1       -- Khám bệnh
  AND service_req_stt_id IN (1,2,3)
  AND is_active = 1
  AND is_delete = 0
GROUP BY execute_room_name, service_req_stt_id
ORDER BY execute_room_name
```

### Pivot trong PHP

Thu thập danh sách tất cả phòng duy nhất → với mỗi phòng, gán count cho từng status (mặc định 0 nếu không có dữ liệu) → đảm bảo 3 mảng data căn chỉnh theo cùng thứ tự labels.

### Response JSON

```json
{
  "labels": ["PK Da liễu P119", "Tầng 2 PK Mắt - P213", "..."],
  "chua_thuc_hien": [33, 9, 0, ...],
  "dang_thuc_hien": [0,  1, 0, ...],
  "da_thuc_hien":   [33, 71, 15, ...],
  "tong_luot_kham": 1294,
  "tong_so_phong":  33
}
```

- `labels`: tên phòng, sắp xếp alphabetically
- `chua_thuc_hien`: stt_id = 1
- `dang_thuc_hien`: stt_id = 2  
- `da_thuc_hien`: stt_id = 3
- `tong_luot_kham`: tổng toàn bộ 3 trạng thái
- `tong_so_phong`: số phòng có dữ liệu (unique execute_room_name)

---

## View: `phong-kham-tv.blade.php`

**Path:** `resources/views/phong-kham-tv.blade.php`  
**URL:** `/phong-kham-tv`

### Layout

```
┌────────────────────────────────────────────────────────────────────┐
│  [Tên BV]                              [Thứ Tư, 07/05/2026  08:42] │
│  Tổng số lượt khám: 1.294   •   Tổng số phòng thực hiện: 33        │
├────────────────────────────────────────────────────────────────────┤
│                                                                    │
│                  STACKED BAR CHART (Chart.js)                      │
│                  Chiếm phần lớn màn hình                           │
│                                                                    │
├────────────────────────────────────────────────────────────────────┤
│   ● Chưa thực hiện (đỏ)  ● Đang thực hiện (cam)  ● Đã thực hiện (xanh)│
└────────────────────────────────────────────────────────────────────┘
```

### Thư viện JS

- **Chart.js** v2 (dùng file local đã có tại `vendor/chart/js/Chart.min.js`)
- **chartjs-plugin-datalabels** v0.x (tương thích Chart.js v2) — load từ CDN hoặc thêm local
- jQuery (local: `vendor/adminlte/vendor/jquery/dist/jquery.min.js`)

### Chart config (Chart.js v2 stacked bar)

```javascript
{
  type: 'bar',
  data: {
    labels: [...],  // tên phòng
    datasets: [
      { label: 'Chưa thực hiện', backgroundColor: 'rgba(255,99,132,0.85)',  data: [...] },
      { label: 'Đang thực hiện', backgroundColor: 'rgba(255,159,64,0.85)',  data: [...] },
      { label: 'Đã thực hiện',   backgroundColor: 'rgba(75,192,100,0.85)',  data: [...] }
    ]
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    scales: {
      xAxes: [{ stacked: true }],
      yAxes: [{ stacked: true, ticks: { beginAtZero: true } }]
    },
    plugins: {
      datalabels: {
        display: function(context) { return context.dataset.data[context.dataIndex] > 0; },
        color: '#fff',
        font: { weight: 'bold', size: 11 }
      }
    },
    tooltips: {
      mode: 'index',
      callbacks: {
        title: function(items, data) { return data.labels[items[0].index]; },
        label: function(item, data) {
          return data.datasets[item.datasetIndex].label + ': ' + item.yLabel;
        }
      }
    }
  }
}
```

### Auto-refresh

```javascript
function loadChart() { /* gọi AJAX /khth/chart-phong-kham, destroy chart cũ, tạo chart mới */ }
$(document).ready(function() {
    loadChart();
    setInterval(loadChart, 300000); // 5 phút
});
```

### Đồng hồ thực

```javascript
function updateClock() {
    var now = new Date();
    // Format: "Thứ Tư, 07/05/2026  08:42:15"
    document.getElementById('clock').textContent = formatDateTime(now);
}
setInterval(updateClock, 1000);
updateClock();
```

---

## Controller: 2 methods mới trong `KHTHController`

### `phongKhamTv(Request $request)`
```php
public function phongKhamTv(Request $request)
{
    $organizationName = config('organization.organization_name', 'Bệnh viện');
    return view('phong-kham-tv', compact('organizationName'));
}
```

### `chartPhongKham(Request $request)`
- Query 1 lần như mô tả trên
- Pivot PHP: collect rooms → build 3 aligned arrays
- Return `response()->json([...])`
- Wrap trong try/catch, trả lỗi JSON nếu exception

---

## Files thay đổi

| File | Loại thay đổi |
|------|--------------|
| `routes/web.php` | Thêm 2 route public |
| `app/Http/Controllers/KHTH/KHTHController.php` | Thêm 2 method |
| `resources/views/phong-kham-tv.blade.php` | Tạo mới |

---

## Không trong scope

- Bộ lọc ngày (chỉ hiển thị hôm nay)
- Phân quyền / token bảo vệ
- Lưu lịch sử / log truy cập
- Responsive mobile (chỉ cần TV landscape)
