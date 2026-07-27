# Thiết kế lại màn Tổng quan trên trình chiếu giao ban

Ngày: 2026-07-27
Phạm vi: `MetricSchema`, `MetricValidator`, `metric-builder.js`, `giaoban-present.blade.php`.

## Vấn đề

Màn Tổng quan hiện gần như trống. Nguyên nhân không phải cấu hình sai mà là **thiết kế**: `overviewSlide` cộng số theo mã chỉ tiêu **viết cứng trong code**.

| Thẻ KPI | Mã code tìm | Cấu hình thật đang có |
|---|---|---|
| Nội trú hiện có | `hien_co` | — |
| Khám ngoại trú | `kham_benh`, `kham` | — |
| Vào viện | `vao_vien` | có `bn_vao_thang` |
| Ra viện | `bn_ra_vien` | có `xin_ra_vien` |
| Chuyển viện | `bn_chuyen_vien` | có `chuyen_vien` |
| Tử vong | `bn_tu_vong` | có `tu_vong` |
| Cấp cứu | `bn_cap_cuu` | — |
| PT / Đẻ | `pt_cap_cuu`, `pt_phien`, `de_thuong` | có `danh_sach_mo_phien` (chỉ tiêu chuỗi) |

Không mã nào khớp. Hàm `kpi()` trả chuỗi rỗng khi giá trị `null`, nên **lưới KPI rỗng hoàn toàn** — màn chỉ còn donut giường, kíp trực, ghi chú chung.

Đây là hệ quả tất yếu của việc cho KHTH tự đặt mã và tên chỉ tiêu, trong khi màn Tổng quan vẫn giả định một bộ mã cố định. Sửa mã cứng cho khớp hôm nay thì lần sau đổi cấu hình lại trống, và không ai biết tại sao.

Vấn đề thứ hai, sâu hơn: kể cả khi các con số hiện đúng, màn này chỉ **lặp lại** số đã có ở các slide sau. Nó không trả lời câu hỏi mà người chủ trì cần: *hôm nay có gì bất thường*.

## Quyết định đã chốt

| Câu hỏi | Chốt |
|---|---|
| Cách chọn KPI | Đánh dấu trên từng chỉ tiêu, gộp theo nhãn |
| Thứ tự slide | **Tổng quan → Khoa (theo sort_order) → Công suất giường** |
| Donut công suất giường | Chuyển khỏi Tổng quan, gom về màn Công suất giường |

## Thiết kế

### 1. `COMMON_FIELDS` — ô dùng chung cho mọi loại chỉ tiêu

`MetricSchema` hiện khai `fields` / `filter` **theo từng loại**, còn `code` và `name` thì viết cứng trong `renderBody` của JS. Thêm khái niệm thứ ba: các ô mà chỉ tiêu nào cũng có.

```php
const COMMON_FIELDS = [
    'overview'       => ['widget' => 'bool', 'label' => 'Hiện ở màn Tổng quan'],
    'overview_label' => ['widget' => 'text', 'label' => 'Nhãn gộp trên Tổng quan', 'max' => 60,
                         'show_if' => ['overview' => [true]]],
];
```

Dùng lại `show_if` đã có: ô nhãn chỉ hiện sau khi tích. Hai khoá này nằm ở **cấp chỉ tiêu**, không nằm trong `input`, vì chúng áp cho cả chỉ tiêu tự động lẫn nhập tay.

### 2. Gộp theo nhãn, không theo mã

Màn Tổng quan lấy các chỉ tiêu có `overview === true`, gom theo `overview_label` (thiếu thì lấy `name`), cộng giá trị across các khoa, mỗi nhóm một thẻ KPI.

Gộp theo **nhãn** chứ không theo **mã** là điểm mấu chốt:

- Giữ được khả năng gộp mà bản cũ có (`pt_cap_cuu + pt_phien + de_thuong` → một thẻ "PT / Đẻ"), nhưng do KHTH quyết chứ không do lập trình viên đoán.
- Các khoa đặt mã khác nhau cho cùng một thứ vẫn cộng chung được, miễn là đặt cùng nhãn.
- **Không bao giờ trống lại vì đổi mã** — đó là toàn bộ lý do của thiết kế này.

Thứ tự thẻ: theo thứ tự xuất hiện đầu tiên khi duyệt khoa theo `sort_order`, rồi duyệt chỉ tiêu theo thứ tự khai báo.

### 3. Chặn tích `overview` cho chỉ tiêu chuỗi

Không cộng được văn bản. `MetricValidator` báo lỗi rõ ràng thay vì để màn Tổng quan âm thầm bỏ qua.

### 4. Ba khối mới trên màn Tổng quan

Cả ba dùng dữ liệu **đã có sẵn trong payload** — không thêm truy vấn, không sửa controller.

**Trạng thái báo cáo.** Nháp hay đã chốt, thành nhãn rõ ràng thay vì lẫn trong dòng thời gian nhỏ ở góc.

**Cảnh báo lệch cân đối.** `balance_warnings` hiện chỉ là một icon nhỏ trên slide từng khoa — người ngồi họp không thấy. Đưa lên tổng quan dạng danh sách: *"2 khoa lệch cân đối: Nội TH (3), Ngoại TH (1)"*. Không khoa nào lệch thì hiện dòng xác nhận, đừng để trống — trống thì không phân biệt được "không lệch" với "chưa tính".

**Ô bắt buộc còn trống.** Duyệt các chỉ tiêu có `input.required`, đối chiếu giá trị ô, liệt kê khoa còn thiếu. Với chỉ tiêu số thì thiếu là `manual_value` rỗng **hoặc** đang mang cờ `carried_over` (kế thừa nhưng khoa chưa xác nhận); với chỉ tiêu chuỗi là `note` rỗng.

Hai khối sau là lý do thật sự để có màn tổng quan: chúng trả lời "hôm nay có gì bất thường" chứ không lặp lại số của slide sau. Người chủ trì cần biết **trước** khi bắt đầu đọc, không phải phát hiện giữa chừng.

### 5. Thứ tự slide và donut

`build()` đổi thành: **Tổng quan → từng khoa (theo `sort_order`) → Công suất giường**.

Donut chuyển từ `overviewSlide` sang `capacityDeptSlide`, gom toàn bộ nội dung về giường vào một màn: donut tổng viện + biểu đồ theo khoa.

Thanh điều hướng theo tên khoa (`deptNames`) phải cập nhật chỉ số theo thứ tự mới — nếu quên, bấm tên khoa sẽ nhảy sai slide.

## Kiểm thử

Bổ sung vào `tests/Unit/GiaoBan/`:

- `MetricSchemaTest`: `COMMON_FIELDS` có đúng hai khoá; `overview_label` khai `show_if` trỏ tới `overview`.
- `MetricValidatorTest`:
  - `overview: true` + `overview_label: "PT / Đẻ"` → hợp lệ;
  - `overview` không phải bool → lỗi `overview`;
  - `overview_label` dài quá 60 ký tự → lỗi;
  - khai `overview_label` mà không bật `overview` → lỗi;
  - chỉ tiêu chuỗi tích `overview` → lỗi `overview`;
  - chỉ tiêu **tự động** (census, exam_visit…) tích `overview` → **hợp lệ**, vì hai khoá này áp cho mọi loại.

Gate: `vendor/bin/phpunit --testsuite Unit` xanh sạch (hiện 245).

## Nghiệm thu tay

Phần trình chiếu không có test tự động.

- [ ] Cấu hình: mở một chỉ tiêu số bất kỳ → thấy ô **Hiện ở màn Tổng quan**; tích vào → hiện thêm ô **Nhãn gộp**; bỏ tích → ô nhãn biến mất.
- [ ] Tích `overview` cho một chỉ tiêu chuỗi rồi Lưu → 422, card tô đỏ, báo lỗi tiếng Việt.
- [ ] Tích cho hai chỉ tiêu ở **hai khoa khác nhau**, đặt **cùng một nhãn** → trình chiếu hiện **một thẻ** với giá trị bằng tổng hai khoa.
- [ ] Bỏ nhãn, chỉ tích → thẻ lấy tên chỉ tiêu làm nhãn.
- [ ] Không tích chỉ tiêu nào → lưới KPI trống nhưng màn vẫn có trạng thái, cảnh báo, kíp trực, ghi chú (không phải màn trắng).
- [ ] Slide đầu tiên là **Tổng quan**; slide thứ hai là khoa có `sort_order` nhỏ nhất; slide cuối là **Công suất giường** và có donut.
- [ ] Bấm tên khoa trên thanh điều hướng → nhảy đúng slide của khoa đó.
- [ ] Sửa một ô để khoa lệch cân đối → màn Tổng quan liệt kê đúng khoa đó kèm số lệch.
- [ ] Để trống một ô bắt buộc → màn Tổng quan liệt kê đúng khoa đó.

## Không làm trong đợt này

- Không đụng `GiaoBanController` hay `GiaoBanMetricService` — toàn bộ dữ liệu cần đã có trong payload của `show()`.
- Không làm bảng phòng khám, bảng tổng hợp theo khối, gộp KKB + Sơn Lương (nhóm C, đang tạm dừng theo quyết định 2026-07-27).
