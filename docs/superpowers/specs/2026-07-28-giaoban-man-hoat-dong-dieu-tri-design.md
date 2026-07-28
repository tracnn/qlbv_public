# Giao ban trình chiếu: màn Hoạt động điều trị

Ngày: 2026-07-28
Trạng thái: đã chốt thiết kế

## 1. Mục tiêu

Thêm một màn trình chiếu tổng hợp số liệu của **các khoa thuộc khối Điều trị nội trú**, dạng
bảng: mỗi khoa một dòng, mỗi chỉ tiêu một cột, kèm dòng TỔNG CỘNG.

Hiện trình chiếu chỉ có slide riêng cho từng khoa, người dự giao ban phải nhớ số của slide
trước để so sánh giữa các khoa.

## 2. Khảo sát

### 2.1 Bảng không thể có bộ cột cố định

Mỗi khoa khai một bộ chỉ tiêu khác nhau:

```
Nội TH          : bn_cu, bn_vao_thang, chuyen_vien
Ngoại tổng hợp  : bn_cu, xin_ra_vien, tu_vong, danh_sach_mo_phien, de_mo
Phụ sản         : bn_cu, de_thuong, de_mo
```

Chỉ `bn_cu` chung cả ba. Nên bảng là **báo cáo động**: cột là hợp của mọi chỉ tiêu các khoa
đã khai, khoa nào không khai thì ô bằng 0. Người dùng chốt hướng này ngày 2026-07-28.

Bằng chứng cách gộp chạy đúng trên dữ liệu hiện có: `de_mo` xuất hiện ở cả Ngoại tổng hợp
lẫn Phụ sản, `bn_cu` ở cả ba khoa.

### 2.2 Dữ liệu đã có sẵn, không cần tính gì mới

Payload của `GiaoBanController::show()` đã mang:

- `configs` — mỗi phần tử có `id`, `display_name`, `metrics` (mỗi chỉ tiêu có `code`,
  `name`, `type`, `input`).
- `cells` — `dept_config_id`, `metric_code`, `auto_value`, `manual_value`, `note`.

`cells` là ảnh chụp theo kỳ báo cáo (`from_time`/`to_time`, `data_fetched_at`), nên bảng tự
khớp với số liệu đã chốt mà không phải truy vấn HIS lần nữa.

Thiếu đúng **một trường**: `show()` chưa trả `block_type`, nên phía trình chiếu không biết
khoa nào thuộc khối điều trị.

### 2.3 Bài học đã có trong mã nguồn về khoá gộp

`MetricSchema::COMMON_FIELDS` có ghi chú của màn Tổng quan:

> gom theo NHÃN chứ không theo MÃ: các chỉ tiêu ở nhiều khoa cùng `overview_label` sẽ cộng
> chung thành một thẻ. Nhờ vậy màn đó không bao giờ trống lại khi KHTH đổi mã chỉ tiêu.

Bảng tổng hợp gặp đúng vấn đề đó, nên theo cùng cách: **khoá cột theo nhãn**.

## 3. Phạm vi

### Có làm

- Lớp thuần `BangDieuTri` dựng cấu trúc bảng; `show()` trả thêm `bang_dieu_tri`.
- Slide mới trên trình chiếu: bảng tổng hợp khối điều trị nội trú.
- Tự thu nhỏ cỡ chữ để bảng vừa một màn.
- Đưa khối **Kíp trực lãnh đạo** lên đầu slide Tổng quan.

### Không làm

- Bảng lưu ảnh chụp riêng — dữ liệu đã nằm trong `giaoban_report_cells`.
- Tính chỉ tiêu mới từ HIS — bảng chỉ xoay ngang số đã có.
- Thêm cờ khai báo "hiện ở bảng tổng hợp" — người dùng chọn lấy toàn bộ chỉ tiêu số.
- Đưa chỉ tiêu dạng chuỗi vào bảng — mục 4.2.
- Xuất Excel bảng này — chưa yêu cầu.

## 4. Thiết kế

### 4.1 Dòng

Các cấu hình khoa thoả **cả ba**: `block_type = 'dieu_tri'`, `is_active = 1`, và nằm trong
danh sách khoa người xem được phép thấy (`GiaoBanPermission::visibleDeptConfigIds`).

Sắp theo `sort_order`, đúng thứ tự đang dùng cho các slide khoa.

Dòng cuối là **TỔNG CỘNG**.

Không có khoa nào thoả thì **không dựng slide**, thay vì hiện bảng rỗng.

### 4.2 Cột — chỉ chỉ tiêu dạng số

Chỉ tiêu vào bảng khi **không phải** kiểu nhập tay dạng văn bản:

```
la so  <=>  KHONG ( type == 'manual' && input.value_type == 'text' )
```

`value_type` chỉ có bốn giá trị: `int`, `decimal`, `percent`, `text`. Mọi loại chỉ tiêu khác
(`census_from`, `census_to`, `movement_in`, `movement_transfer_in`, `movement_transfer_out`,
`end_type`, `bed_count`, `exam_visit`, `service_count`, `admission`) đều là số.

Hiện có đúng một chỉ tiêu bị loại: `danh_sach_mo_phien` của Ngoại tổng hợp. Nó vẫn hiển thị
ở slide riêng của khoa, không mất đi đâu.

Phép kiểm này dùng lại `laChiTieuChuoi()` đã có trong `giaoban-present.blade.php` — cùng một
định nghĩa với chỗ dựng slide khoa, không viết lại.

### 4.3 Khoá cột theo nhãn

```
khoaCot(m) = trim(m.name) nếu khác rỗng, ngược lại m.code
```

Hai chỉ tiêu cùng nhãn ở hai khoa khác nhau gộp thành một cột. Lý do chọn nhãn thay vì mã:
mục 2.3.

Đánh đổi đã lường: khoa đổi **nhãn** thì cột tách đôi; đổi **mã** thì không sao. Ngược hẳn
với cách khoá theo mã. Dữ liệu hiện tại nhãn và mã trùng khớp 1:1 nên hai cách cho cùng kết
quả; khác biệt chỉ lộ khi KHTH sửa khai báo.

### 4.4 Thứ tự cột

Theo lần xuất hiện đầu tiên khi duyệt các khoa theo `sort_order`, rồi duyệt chỉ tiêu theo
thứ tự khai trong khoa đó.

Ổn định, và đưa chỉ tiêu dùng chung (`BN cũ`) lên đầu vì khoa nào cũng khai.

### 4.5 Giá trị ô

```
gia tri = manual_value nếu khác null, ngược lại auto_value
khoa không khai chỉ tiêu đó  ->  0
khai nhưng ô chưa có số      ->  0
```

Dùng lại `cellVal()` đã có; `null` quy về 0.

Người dùng chốt: khoa không có thuộc tính thì hiện **0**, không phải để trống hay gạch ngang.

### 4.6 Dòng TỔNG CỘNG

Cộng theo cột, **trừ cột kiểu `percent`**: cộng phần trăm lại là vô nghĩa, ô tổng của cột đó
để `—`.

Một cột bị coi là `percent` khi **mọi** khai báo góp vào cột đó đều có
`input.value_type == 'percent'`. Cột trộn lẫn phần trăm với số đếm là lỗi khai báo, khi đó
vẫn cộng và để đơn vị tự phát hiện qua con số vô lý.

Hiện chưa khoa nào dùng `percent`, nhưng schema cho phép nên phải xử lý trước.

### 4.7 Bề rộng bảng

Tự thu nhỏ cỡ chữ theo số cột để bảng vừa một màn, có **sàn tối thiểu** để không nhỏ tới mức
không đọc được. Ba khoa hiện tại cho khoảng 8 cột; bảy khoa như mẫu người dùng gửi có thể
lên hơn 20 cột.

Chạm sàn mà vẫn tràn thì cho cuộn ngang trong khung, giống cách các bảng dài đang cuộn dọc
trong `.slide`.

### 4.8 Vị trí trong chuỗi slide

Chèn **ngay sau slide Tổng quan**, trước các slide khoa: xem bức tranh toàn khối trước rồi
mới đi vào từng khoa.

Tên slide đăng ký trong thanh điều hướng: `Hoạt động điều trị`.

## 5. Thay đổi mã nguồn

| Tệp | Việc |
|---|---|
| `app/Services/GiaoBan/BangDieuTri.php` | **mới** — lớp thuần dựng cấu trúc bảng |
| `app/Http/Controllers/KHTH/GiaoBanController.php` | `show()` gọi `BangDieuTri`, trả thêm `bang_dieu_tri` |
| `resources/views/khth/giaoban-present.blade.php` | hàm vẽ slide bảng + chèn vào `build()` + CSS |

Không migration, không truy vấn HIS.

Máy chủ dựng sẵn cấu trúc bảng nên **không cần** đẩy `block_type` ra client: việc lọc khối,
lọc quyền, gộp cột và tính tổng đều xong ở PHP. Blade chỉ nhận `{cot, dong, tong}` và vẽ.

## 6. Kiểm thử

Cổng: `vendor/bin/phpunit --testsuite Unit`.

Phần dựng bảng nằm trong JavaScript của blade nên không kiểm được bằng PHPUnit. Tách phần
quyết định thành hàm thuần PHP để kiểm được:

`App\Services\GiaoBan\BangDieuTri` — nhận danh sách config và danh sách ô, trả ra cấu trúc
bảng (`cot`, `dong`, `tong`). Blade chỉ còn việc vẽ. Đây là cách duy nhất để các quy tắc ở
mục 4.2–4.6 có kiểm thử thật thay vì tin vào mắt người.

| Ca | Kỳ vọng |
|---|---|
| Không khoa `dieu_tri` nào | trả cấu trúc rỗng, blade không dựng slide |
| Khoa `kham` / `can_lam_sang` | không lọt vào bảng |
| Khoa `is_active = 0` | không lọt vào bảng |
| Hai khoa cùng nhãn chỉ tiêu | một cột |
| Hai khoa cùng mã nhưng khác nhãn | **hai** cột — hệ quả của 4.3 |
| Chỉ tiêu `manual` + `value_type = text` | không thành cột |
| Chỉ tiêu `manual` + `value_type = int` | thành cột |
| Khoa không khai chỉ tiêu của cột | ô bằng 0 |
| Ô có `manual_value` lẫn `auto_value` | lấy `manual_value` |
| Ô `manual_value = null` | lấy `auto_value` |
| Cả hai null | 0 |
| Dòng tổng | bằng tổng các dòng trên từng cột |
| Cột toàn `percent` | ô tổng là `null` (blade hiện `—`) |
| Cột trộn `percent` và `int` | vẫn cộng |
| Thứ tự cột | theo `sort_order` khoa rồi thứ tự khai trong khoa |
| Chỉ tiêu trùng nhãn ở khoa sau | không đẩy cột lên trước |

## 7. Rủi ro

| Rủi ro | Xử lý |
|---|---|
| Khoa đổi nhãn chỉ tiêu → cột tách đôi | Đã lường ở 4.3; đổi lại được lợi khi đổi mã |
| Quá nhiều cột → chữ quá nhỏ | Sàn cỡ chữ + cuộn ngang khi chạm sàn (4.7) |
| Hai khoa cùng nhãn nhưng khai kiểu tính khác nhau | Cột vẫn gộp; là lỗi khai báo, ngoài phạm vi màn hình này |
| Người xem chỉ được thấy vài khoa → bảng thiếu dòng, tổng không khớp toàn viện | Lọc quyền giữ nguyên như các slide khác; trình chiếu vốn chỉ admin (`present` đã `abort(403)`) |
