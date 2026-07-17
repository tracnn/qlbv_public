# Spec: Dashboard chuyên biệt cho module kiểm tra lỗi XML3176

**Date:** 2026-07-17
**Status:** Approved (chờ user review spec)

---

## 1. Mục tiêu

Xây dựng một dashboard chuyên biệt cho module kiểm tra lỗi XML3176 (hiện là `app/Http/Controllers/BHYT/BHYTXml3176Controller.php` — màn hình danh sách DataTables).

Dashboard phục vụ **đồng thời hai nhóm người dùng**:

- **Lãnh đạo/quản lý (KHTH/BHYT):** tầng KPI + biểu đồ tổng quan để giám sát tỷ lệ lỗi, tiến độ gửi hồ sơ, rủi ro tài chính.
- **Nhân viên nghiệp vụ:** drill-down từ mỗi con số xuống đúng danh sách hồ sơ tương ứng để xử lý.

Dashboard trả lời 4 câu hỏi (điểm đau do người dùng xác định):

1. Còn bao nhiêu hồ sơ lỗi tồn đọng chưa xử lý, cái nào cần ưu tiên?
2. Mã lỗi nào phổ biến nhất để xử lý tận gốc?
3. Pipeline (import → check → export → ký số → gửi BHXH) đang tắc ở khâu nào?
4. Bao nhiêu tiền BHYT đang bị treo vì hồ sơ lỗi chưa gửi được?

---

## 2. Phạm vi

### Trong phạm vi

- Trang dashboard mới tại `dashboard/xml3176` (màn hình riêng, không đụng màn hình danh sách hiện có).
- 5 thẻ KPI + 4 khối biểu đồ.
- Drill-down từ KPI/biểu đồ sang màn hình danh sách `bhyt/xml3176/index` có sẵn, bằng query param.
- Bổ sung khả năng prefill bộ lọc từ URL cho `resources/views/bhyt/xml3176/index.blade.php` (thay đổi nhỏ, chỉ JS).

### Ngoài phạm vi (có thể làm sau)

- Bộ lọc theo khoa (`ma_khoa`) trên màn hình danh sách → do đó **khối "Lỗi theo khoa" không drill-down** (quyết định D3, mục 11).
- Biểu đồ xu hướng theo thời gian (trend) — không nằm trong 4 điểm đau, bỏ theo nguyên tắc YAGNI.
- Bảng tổng hợp pre-aggregate / job chạy lịch — không cần ở quy mô hiện tại (xem mục 10).
- Tự động refresh (auto-refresh).

---

## 3. Kiến trúc & thành phần

Bám đúng pattern dashboard "thế hệ 3" đã chuẩn hoá trong repo (mẫu tốt nhất: `OperatingRoomController` + `OperatingRoomService`).

| Vai trò | File | Trách nhiệm |
|---|---|---|
| Controller | `app/Http/Controllers/Dashboard/Xml3176DashboardController.php` | Mỏng: validate → gọi service → `response()->json()`. Không chứa logic nghiệp vụ. |
| Service | `app/Services/Dashboard/Xml3176DashboardService.php` | Toàn bộ logic. Tách **hàm thuần** (tính %, phân nhóm tuổi, dựng Pareto) khỏi **hàm query** (lấy raw rows). |
| View | `resources/views/dashboard/xml3176.blade.php` | `@extends('adminlte::page')`, box AdminLTE, truyền route qua `window.XML3176_CFG`. |
| JS | `public/js/dashboard/xml3176.js` | IIFE + jQuery + Highcharts. Mỗi metric một hàm `load<Metric>()`. |
| Unit test | `tests/Unit/Dashboard/Xml3176DashboardServiceTest.php` | Test hàm thuần, không chạm DB. |
| Feature test | `tests/Feature/Dashboard/Xml3176DashboardControllerTest.php` | Mock service qua container; test JSON + validation 422. |
| Routes | `routes/web.php` | Trong group `middleware => checkrole:dashboard`, prefix `dashboard/xml3176/...` |
| **Sửa nhỏ** | `resources/views/bhyt/xml3176/index.blade.php` | ~15 dòng JS: đọc `URLSearchParams` → prefill bộ lọc → phục vụ drill-down. |

### 3.1. Hai điểm kỹ thuật quan trọng

**(1) Connection:** dữ liệu XML3176 nằm ở **connection mặc định** (bảng ứng dụng), **khác** với các dashboard khác đang dùng `DB::connection('HISPro')` (Oracle HIS). Service này không đụng Oracle.

**(2) Hai kiểu dữ liệu ngày** — đây là chỗ dễ sai nhất:

| Trường | Bảng | Kiểu |
|---|---|---|
| `ngay_vao`, `ngay_ra`, `ngay_ttoan` | `xml3176_xml1s` | **String** dạng `YmdHi` |
| `created_at`, `imported_at`, `exported_at`, `submitted_at` | `xml3176_xml1s` / `xml3176_informations` | **Timestamp** thật |

Service phải chuyển khoảng ngày lọc sang đúng định dạng cho từng loại — giống cách `BHYTXml3176Controller@fetchData` đang làm (`$formattedDateFromForFields` vs `$formattedDateFromForTimestamp`). Tách thành hàm thuần `normalizeDateRange()` để unit test được.

---

## 4. Nội dung dashboard

### 4.1. Hàng KPI (5 thẻ AdminLTE small-box)

| # | Thẻ | Nội dung | Màu |
|---|---|---|---|
| 1 | Tổng hồ sơ trong kỳ | Đếm `ma_lk` theo bộ lọc | Xanh dương |
| 2 | Hồ sơ lỗi nghiêm trọng | Số + % trên tổng | Đỏ |
| 3 | Hồ sơ lỗi thẻ BHYT | Số + % trên tổng | Vàng |
| 4 | Chi phí BHYT bị treo | Tổng tiền (VNĐ) | Cam |
| 5 | Đã gửi BHXH | Số + % trên tổng | Xanh lá |

### 4.2. Khối A — Phễu pipeline (điểm đau #3)

5 bậc: **Đã import → Không lỗi nghiêm trọng → Đã xuất XML → Đã ký số → Đã gửi BHXH**.
Mỗi bậc hiển thị số tuyệt đối + **% so với bậc liền trước** → nhìn ra ngay khâu nào rơi rụng nhiều nhất.

> **Dùng bar ngang (`bar`), KHÔNG dùng funnel chart.** Lý do: `public/vendor/highcharts/modules/` hiện chỉ có `accessibility.js`, `export-data.js`, `exporting.js` — **không có** `funnel.js`. Dùng bar ngang tránh phải thêm vendor asset mới, trực quan tương đương.

### 4.3. Khối B — Pareto top 15 mã lỗi (điểm đau #2)

- Cột = số **hồ sơ riêng biệt** (`COUNT(DISTINCT ma_lk)`) dính mã lỗi đó.
- Đường = **% tích luỹ**.
- Tên lỗi lấy từ `xml3176_error_catalogs.error_name`; màu phân biệt nghiêm trọng / cảnh báo theo `critical_error`.
- **Click cột → drill-down.**

Gom nhóm theo cặp `(xml, error_code)` — đúng khoá unique của `xml3176_error_catalogs`. Endpoint **phải trả kèm `catalog_id`** (`xml3176_error_catalogs.id`) cho mỗi cột, vì drill-down cần `xml3176_error_catalog=<id>` (màn hình danh sách lọc theo id danh mục, không theo `error_code`). Mã lỗi không có trong danh mục → bỏ qua khỏi Pareto (không thể drill-down, và trên thực tế `saveErrors()` luôn `createOrUpdate` vào danh mục nên ca này gần như không xảy ra).

### 4.4. Khối C — Tồn đọng theo tuổi hồ sơ (điểm đau #1)

- Cột theo nhóm tuổi: **0–7 / 8–15 / 16–30 / >30 ngày**.
- Chỉ tính hồ sơ **chưa gửi được**.
- **Click cột → drill-down.**
- ⚠️ **Khối này KHÔNG chịu bộ lọc kỳ** — luôn tính theo `ngay_ra` so với hôm nay (quyết định **D11**, mục 11). Biểu đồ phải ghi nhãn rõ điều này để người dùng không tưởng nó hỏng khi đổi khoảng ngày mà số không đổi.

### 4.5. Khối D — Lỗi theo khoa (điểm đau #2)

- Bar ngang, top khoa theo số hồ sơ lỗi.
- Join `DepartmentBedCatalog` (`ma_khoa` → `ten_khoa`) để hiển thị tên khoa thay vì mã. Bảng này nằm ở DB ứng dụng.
- **Không drill-down** (xem quyết định D3, mục 11).

---

## 5. Định nghĩa số liệu (chốt)

| Chỉ số | Định nghĩa chính xác |
|---|---|
| **Hồ sơ lỗi nghiêm trọng** | `ma_lk` có ≥1 dòng `xml3176_error_results` với `critical_error = true` |
| **Hồ sơ lỗi thẻ BHYT** | `ma_lk` có quan hệ `check_hein_card` (hasOne `App\Models\CheckBHYT\check_hein_card` theo `ma_lk`) thoả: `ma_kiemtra IN config('xml3176.hein_card_invalid.check_code')` **OR** `ma_tracuu IN config('xml3176.hein_card_invalid.result_code')` |
| **Chi phí BHYT bị treo** | `SUM(xml3176_xml1s.t_bhtt)` của hồ sơ **có lỗi nghiêm trọng VÀ chưa gửi** (`submitted_at IS NULL`) |
| **Đã gửi BHXH** | `xml3176_informations.submitted_at IS NOT NULL` (**không** xét `submit_error` — xem D10) |
| **Đã xuất XML** | `exported_at IS NOT NULL` |
| **Đã ký số** | `is_signed = true` |
| **Tuổi hồ sơ** | Số ngày từ `ngay_ra` đến hôm nay, **chỉ với hồ sơ chưa gửi**. Hồ sơ **chưa có `ngay_ra`** (chưa ra viện) bị **loại** khỏi khối C vì chưa đủ điều kiện gửi. |

### 5.1. Ghi chú quan trọng

**Không cần lọc `is_check`.** `Xml3176ErrorService::saveErrors()` đã bỏ qua các mã lỗi có `is_check = false` ngay khi ghi — nên `xml3176_error_results` chỉ chứa lỗi đang được kiểm tra. Thêm điều kiện `is_check` vào dashboard là thừa và gây hiểu nhầm.

**Không có "hạn gửi" cứng.** Đơn vị không quy định hạn cứng → dùng **tuổi hồ sơ** làm thước đo ưu tiên, không cần thêm config hạn gửi.

**"Đã gửi BHXH" cố ý KHÔNG xét `submit_error`** (quyết định D10). Lý do: bộ lọc `xml_submit_status=has_submit` của màn hình danh sách chỉ kiểm tra `whereNotNull('submitted_at')`. Nếu KPI thêm điều kiện `submit_error IS NULL` thì **số trên thẻ sẽ không khớp số dòng trong danh sách sau khi click** — lỗi tin cậy nghiêm trọng với người dùng. Nguyên tắc xuyên suốt spec: **mọi con số click được phải khớp chính xác danh sách hiện ra**.

Hồ sơ gửi bị lỗi không bị bỏ sót: người dùng xem riêng bằng bộ lọc `xml_submit_status=has_submit_error` đã có sẵn trên màn hình danh sách.

---

## 6. API endpoints

| Route | Tên route | Trả về |
|---|---|---|
| `GET dashboard/xml3176` | `dashboard.xml3176.index` | View |
| `GET dashboard/xml3176/overview` | `dashboard.xml3176.overview` | 5 thẻ KPI + 5 bậc phễu |
| `GET dashboard/xml3176/top-errors` | `dashboard.xml3176.top-errors` | Pareto top 15 |
| `GET dashboard/xml3176/aging` | `dashboard.xml3176.aging` | Tồn đọng theo nhóm tuổi |
| `GET dashboard/xml3176/by-department` | `dashboard.xml3176.by-department` | Lỗi theo khoa |

**Gộp KPI + phễu vào chung `overview`** vì cả hai tính từ cùng một tập đếm cơ sở → tránh chạy lặp query.

### 6.1. Tham số & validation

Mọi endpoint JSON nhận chung: `date_type`, `date_from`, `date_to`.

```php
$request->validate([
    'date_type' => 'required|in:date_in,date_out,date_payment,date_create',
    'date_from' => 'required|date',
    'date_to'   => 'required|date|after_or_equal:date_from',
]);
```

Sai → **422**.

`date_type` dùng đúng 4 giá trị của màn hình danh sách hiện có (`BHYTXml3176Controller@fetchData`): `date_in` → `ngay_vao`, `date_out` → `ngay_ra`, `date_payment` → `ngay_ttoan`, `date_create` → `created_at`.

---

## 7. Luồng dữ liệu

```
resources/views/dashboard/xml3176.blade.php
  └─ window.XML3176_CFG.routes   (render sẵn bằng route(), KHÔNG hard-code URL)
       └─ public/js/dashboard/xml3176.js → loadAll()
            ├─ $.ajax(overview)      → render 5 KPI + bar ngang phễu
            ├─ $.ajax(topErrors)     → render Pareto (cột + đường tích luỹ)
            ├─ $.ajax(aging)         → render cột nhóm tuổi
            └─ $.ajax(byDepartment)  → render bar ngang theo khoa

Xml3176DashboardController (validate)
  └─ Xml3176DashboardService (hàm query + hàm thuần)
       └─ DB connection mặc định
```

Quy ước JS theo `operating-room.js`: IIFE `(function (win, $) { 'use strict'; ... })(window, jQuery);`, `Highcharts.setOptions({ accessibility: { enabled: false } });`, `credits: { enabled: false }`, bind `$('#btn-load').on('click', loadAll); loadAll();`.

---

## 8. Drill-down

Click → mở `bhyt.xml3176.index` kèm query param. Màn hình danh sách đọc `URLSearchParams`, prefill bộ lọc rồi nạp DataTables.

| Click vào | Query param |
|---|---|
| KPI Tổng hồ sơ | *(không thêm filter — chỉ truyền khoảng ngày)* |
| KPI Lỗi nghiêm trọng | `xml_filter_status=has_error_critical` |
| KPI Lỗi thẻ BHYT | `xml_filter_status=has_error_hein_card` |
| KPI Chi phí BHYT treo | `xml_filter_status=has_error_critical` + `xml_submit_status=not_submit` |
| KPI Đã gửi | `xml_submit_status=has_submit` |
| Bậc phễu 1 — Đã import | *(không thêm filter)* |
| Bậc phễu 2 — Không lỗi nghiêm trọng | `xml_filter_status=no_error_critical` |
| Bậc phễu 3 — Đã xuất XML | `xml_export_status=has_export` |
| Bậc phễu 4 — Đã ký số | `xml_sign_status=has_sign` |
| Bậc phễu 5 — Đã gửi BHXH | `xml_submit_status=has_submit` |
| Cột Pareto | `xml3176_error_catalog=<catalog_id>` |
| Nhóm tuổi hồ sơ | `date_type=date_out` + `date_from`/`date_to` = **đúng khoảng ngày của nhóm tuổi** + `xml_submit_status=not_submit` — **không** kèm bộ lọc kỳ của dashboard (D11) |

**Cả 5 thẻ KPI và cả 5 bậc phễu đều click được** (bảng trên là danh sách đầy đủ). Chỉ khối D — Lỗi theo khoa là không click được (quyết định D3).

**Mọi link đều kèm `date_type`, `date_from`, `date_to` hiện hành** để danh sách khớp đúng con số vừa click. Riêng nhóm tuổi hồ sơ ghi đè `date_type=date_out` vì tuổi hồ sơ được định nghĩa theo `ngay_ra` (mục 5).

> **Lưu ý về KPI Chi phí BHYT treo:** con số trên thẻ là **tổng tiền**, còn danh sách sau drill-down là **các hồ sơ** cấu thành số tiền đó (cùng tập `ma_lk`). Đây là hành vi đúng và đã được cân nhắc — màn hình danh sách không hiển thị tổng tiền.

### 8.1. Prefill trên màn hình danh sách

Bổ sung vào `resources/views/bhyt/xml3176/index.blade.php`, chạy **trước** khi khởi tạo DataTables:

- Đọc `URLSearchParams` từ `window.location.search`.
- Set các `<select>` theo id sẵn có: `#date_type`, `#xml_filter_status`, `#xml3176_error_catalog`, `#xml_export_status`, `#xml_submit_status`, `#xml_sign_status`, `#imported_by`.
- Set khoảng ngày qua daterangepicker: `$('#date_range').data('daterangepicker').setStartDate(...)` / `.setEndDate(...)` — format `YYYY-MM-DD HH:mm:ss`.
- Chỉ set khi param có mặt; không có param → giữ nguyên hành vi mặc định hiện tại (không được làm hỏng luồng dùng trực tiếp).

---

## 9. Xử lý lỗi

**Backend** — giữ pattern gen3, controller mỏng, không try/catch thừa:
- Tham số sai → `$request->validate()` trả **422**.
- Lỗi ngoài dự kiến → để Laravel handler trả 500 + log; không nuốt lỗi.

**Frontend** — mỗi biểu đồ có vùng lỗi riêng (mẫu `showError(containerId, msg)` trong `operating-room.js`): một endpoint hỏng chỉ làm khối đó báo lỗi, các khối khác vẫn hiển thị. Dashboard không trắng trang.

### 9.1. Ca biên bắt buộc xử lý đúng

| Ca biên | Hành vi đúng |
|---|---|
| Kỳ không có hồ sơ (tổng = 0) | Tính % trả **0**, không chia cho 0 / `NaN` |
| Bậc phễu trước = 0 | % so bậc trước = **0** |
| `t_bhtt` NULL | Coi như **0** khi cộng chi phí treo |
| `ngay_ra` rỗng / sai định dạng | **Loại** khỏi khối tuổi hồ sơ; không để Carbon ném exception |
| Không có dữ liệu | Trả mảng rỗng → biểu đồ hiện "Không có dữ liệu" (không phải thông báo lỗi) |

---

## 10. Hiệu năng

Quy mô thực tế: **20.000–100.000 hồ sơ/tháng**.

Quyết định: **query trực tiếp (live)**, không pre-aggregate, không cache. Lý do:
- Các bảng đã có index sẵn: `xml3176_error_results` có index trên `xml`, `ma_lk`, `error_code`, `created_at`; `xml3176_informations` có index trên `macskcb`, `imported_by`, `submitted_at`, `submit_error`; `xml3176_xml1s` có index trên `ma_lk`.
- Số liệu luôn tươi, không có rủi ro lệch do dữ liệu trễ.

Nguyên tắc khi viết query (theo `OperatingRoomService`):
- Lấy raw rows rồi **aggregate phức tạp trong PHP**, không nhồi hết vào SQL — giúp tách hàm thuần để test.
- Tránh join nặng không cần thiết; chỉ join `xml3176_xml1s` khi cần `t_bhtt` / `ma_khoa` / `ngay_ra`.

Nếu về sau dữ liệu vượt ngưỡng, có thể thêm cache ngắn (vài phút) mà không đổi kiến trúc.

---

## 11. Nhật ký quyết định

| # | Quyết định | Lựa chọn | Lý do |
|---|---|---|---|
| D1 | Người dùng đích | Cả quản lý + nhân viên | Tầng KPI cho quản lý, drill-down cho nhân viên |
| D2 | Kiến trúc | **Phương án A**: dashboard riêng theo pattern gen3 | Không nhồi thêm vào `BHYTXml3176Controller` (~670 dòng, đã quá tải); nhất quán pattern; test được |
| D3 | Drill-down khối "Lỗi theo khoa" | **Không drill-down** | Màn hình danh sách chưa có filter `ma_khoa`; thêm filter phải sửa controller đang quá tải. Khối này vẫn đủ dùng để biết khoa nào hay sai |
| D4 | Trục thời gian | Cho chọn `date_type` (như hiện tại) | Nhất quán, drill-down giữ nguyên bộ lọc |
| D5 | Thước đo ưu tiên | Tuổi hồ sơ | Đơn vị không có hạn gửi cứng |
| D6 | Cách drill-down | Link sang màn hình danh sách + query param | Tái dùng 100% bảng/bộ lọc/xuất Excel đã có |
| D7 | Lỗi thẻ BHYT | **Có tính**, tách KPI riêng | Nhất quán với màn hình danh sách hiện có |
| D8 | Biểu đồ phễu | Bar ngang | Repo chưa có `funnel.js`; tránh thêm vendor asset |
| D9 | Hiệu năng | Query live | Đủ nhanh ở quy mô 20k–100k/tháng; pre-aggregate là tối ưu hoá sớm |
| D10 | "Đã gửi BHXH" có xét `submit_error`? | **Không xét** | Bộ lọc `has_submit` của màn hình danh sách chỉ xét `submitted_at`. Thêm điều kiện sẽ khiến số trên KPI không khớp danh sách sau drill-down. Hồ sơ gửi lỗi xem riêng qua `has_submit_error` |
| D11 | Khối tuổi hồ sơ có chịu bộ lọc kỳ không? | **Không** — luôn theo `ngay_ra` vs hôm nay | Phát hiện khi triển khai Task 5: khối này lọc **hai** điều kiện ngày (kỳ theo `date_type` + `ngay_ra` theo nhóm tuổi), trong khi `fetchData` chỉ nhận **một** `date_type` + một khoảng → drill-down không thể tái hiện, số sẽ lệch với danh sách. Bỏ ràng buộc kỳ đi thì chỉ còn một điều kiện trên `ngay_ra` → khớp tuyệt đối. Cũng đúng nghiệp vụ: tồn đọng là câu hỏi "hiện tại", không phải theo kỳ. Đánh đổi: khối này không đổi khi user đổi khoảng ngày → **bắt buộc ghi nhãn trên biểu đồ** |

---

## 12. Test

### 12.1. Unit test — `tests/Unit/Dashboard/Xml3176DashboardServiceTest.php`

Chỉ test **hàm thuần**, không chạm DB:

- `calcPercent()`: mẫu số 0 → trả 0.
- `buildFunnelSteps()`: % so bậc trước đúng; bậc trước = 0 → 0.
- `bucketAgingDays()`: đúng biên nhóm (7/8, 15/16, 30/31).
- `buildParetoData()`: sắp xếp giảm dần, % tích luỹ đúng, cắt top 15.
- `normalizeDateRange()`: phân biệt trường string `YmdHi` và timestamp (mục 3.1).
- Tuổi hồ sơ: `ngay_ra` rỗng / sai định dạng → bị loại, không crash.

### 12.2. Feature test — `tests/Feature/Dashboard/Xml3176DashboardControllerTest.php`

**Mock service qua container**, không chạm DB:

- 4 endpoint JSON đều trả **200** + đúng `assertJsonStructure`.
- Mỗi endpoint có test validation → **422** (thiếu `date_from`; `date_to` < `date_from`; `date_type` không hợp lệ).
- Quy ước repo: `factory(\App\User::class)->make(['id' => 1])` + `actingAs`; `tearDown()` gọi `Mockery::close()`.

### 12.3. Lưu ý PHPUnit

Repo dùng **PHPUnit 6.5** → `protected function setUp()` **không có** `: void` (khác PHPUnit 8+). Sai chỗ này test lỗi signature.

**Không viết test chạm DB** — đúng quy ước sẵn có của dự án.
