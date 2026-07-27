# Ẩn khoa không được phân công trên màn giao ban

Ngày: 2026-07-27
Phạm vi: `GiaoBanController`, `GiaoBanPermission`, `giaoban-index.blade.php`.

## Vấn đề

Màn giao ban hiển thị **mọi khoa** cho **mọi người**; khoa không được phân công chỉ bị đánh `readonly` ở ô nhập (`giaoban-index.blade.php`, biến `editable`).

Ba hệ quả:

1. `show()` trả số liệu toàn viện cho mọi người dùng. "Ẩn" mà chỉ sửa blade thì mở tab Network vẫn đọc được số của khoa khác — bảo mật bằng CSS.
2. `$report` được nạp kèm `with('cells')` rồi trả nguyên model, nên **toàn bộ ô số liệu đi theo trong `report.cells`** dù có lọc mảng `cells` riêng.
3. `export()` **không chặn quyền gì**: ai có vai trò `giaoban` cũng tải được Excel toàn viện.

## Quyết định

| Câu hỏi | Chốt |
|---|---|
| Người chưa được gán khoa nào | Màn trống + thông báo hướng dẫn liên hệ KHTH |
| Mức ẩn | Lọc từ server, không phải ẩn bằng giao diện |
| Trình chiếu + Xuất Excel | Chỉ `giaoban-admin` |
| Khối chung người khoa vẫn thấy | Kíp trực, ghi chú chung |
| Công suất giường | Ẩn (cả tổng lẫn chi tiết từng khoa) |

## Thiết kế

### Tách quyền xem khỏi quyền sửa

`GiaoBanPermission` nhận hai hàm thuần mới, đặt cạnh `canEditDept` đang có:

```php
visibleDeptConfigIds($isAdmin, array $assignedDeptIds, array $allActiveIds)
chuaPhanCongKhoa($isAdmin, array $assignedDeptIds)
```

`canEditDept` **giữ nguyên**. Quyền xem và quyền sửa là hai chuyện khác nhau; gộp lại thì sau này muốn cho ai đó xem-mà-không-sửa sẽ phải gỡ ra.

`visibleDeptConfigIds` duyệt theo `$allActiveIds` chứ không theo `$assignedDeptIds`, để hai tính chất luôn đúng: thứ tự hiển thị bám `sort_order`, và khoa đã tắt `is_active` không hiện dù vẫn còn bản ghi phân công.

### `show()`

- `configs`, `cells`, `balance_warnings` lọc theo `visibleDeptConfigIds`.
- Kíp trực, chức danh trực, ghi chú chung: giữ nguyên toàn viện.
- Công suất giường: chỉ tính khi `isAdmin` — tiện thể bỏ luôn một truy vấn HIS cho người dùng khoa.
- `report` trả **mảng tường minh** (`id`, `status`, `from_time`, `to_time`, `general_note`) thay vì cả model. Ngoài việc bịt rò `report.cells`, cách này còn chặn cột hay quan hệ thêm về sau lỡ đi ké.
- Thêm cờ `no_assignment` để blade phân biệt "chưa được phân công" với "chưa có số liệu hôm nay" — hai trạng thái đều ra màn trống nhưng lý do khác hẳn.

**Lưu ý phiên bản:** `unsetRelation()` không tồn tại ở Laravel 5.5 (thêm ở bản sau). Dùng nó sẽ fatal mọi lần gọi `show()`. Đó là lý do chọn mảng tường minh.

### Chặn quyền

`present()` và `export()` thêm `if (!$this->isAdmin()) abort(403);`.

Màn trình chiếu dùng chính endpoint `show`, nên nếu không chặn thì sau khi lọc nó cũng bị thu hẹp theo — mất công dụng phòng họp. Chặn ở mức admin giữ nguyên bức tranh toàn viện cho người chủ trì.

### Blade

- Kiểm `no_assignment` **trước** nhánh `!res.report`.
- Nút **Trình chiếu** và **Xuất Excel** chuyển vào trong `@if($isAdmin)` — để ngoài thì người khoa vẫn thấy nút rồi bấm vào ăn 403.
- Khối giường không cần sửa: màn nhập liệu chưa bao giờ render nó (chỉ màn trình chiếu dùng), dù trước đây vẫn nhận dữ liệu.

### Thanh công cụ của người dùng khoa

Sau khi gỡ Trình chiếu và Xuất Excel, người khoa chỉ còn một nút **Làm mới**, kèm hai ô **Từ thời điểm** / **Đến thời điểm** vốn chỉ là tham số cho "Lấy số liệu" — thao tác họ không có. Ba ô thì hai ô vô nghĩa.

Tệ hơn, khi KHTH chưa lấy số liệu, màn báo *"(chưa có dữ liệu — bấm Lấy số liệu)"* — chỉ vào một nút không tồn tại trên màn của họ. Hai vấn đề này có từ trước; việc gỡ hai nút chỉ làm chúng lộ ra.

Đã chốt **không** mở quyền "Lấy số liệu" cho người khoa: thao tác đó quét HIS rồi ghi lại `auto_value` cho **toàn bộ** khoa theo mốc thời gian người bấm chọn, nên một khoa bấm là kéo số cho cả viện, và nhiều khoa bấm cùng lúc sẽ dội vào HIS. Nó đúng là việc của KHTH.

Thay vào đó:
- Ẩn hai ô thời gian với người khoa; cột nút giãn từ `col-md-6` sang `col-md-10`.
- Khi chưa có báo cáo, người khoa thấy hộp thông tin *"Phòng KHTH chưa lấy số liệu từ HIS cho ngày giao ban đã chọn"* kèm gợi ý bấm Làm mới hoặc chọn ngày khác. Admin vẫn thấy thông báo cũ vì với họ nút đó có thật.

### Khoa được gán nhưng đã tắt `is_active`

`chuaPhanCongKhoa` nhận danh sách **nhìn thấy** (kết quả `visibleDeptConfigIds`) chứ không phải danh sách **được gán**. Nếu nhận danh sách được gán thì người được gán một khoa vừa bị tắt sẽ có `assigned` không rỗng nhưng màn vẫn trống — và không nhận được thông báo nào. Hai trường hợp cùng ra một kết quả nên phải cùng một cờ.

## Hệ quả cần biết

Cảnh báo lệch cân đối của người dùng khoa thu hẹp theo — họ chỉ thấy cân đối khoa mình. Nếu trước giờ có ai dùng màn này để nhìn toàn viện thì phải chuyển sang màn trình chiếu, và cần quyền admin.

Hiện chỉ **1 tài khoản** được gán khoa trên 3 khoa đang cấu hình. Nghĩa là gần như mọi người dùng có vai trò `giaoban` sẽ thấy màn trống kèm thông báo cho tới khi KHTH gán khoa. **Cần gán khoa cho mọi người trước khi triển khai.**

## Kiểm thử

7 test mới trong `tests/Unit/GiaoBan/GiaoBanPermissionTest.php`: admin thấy tất; người khoa chỉ thấy khoa được gán; khoa đã tắt `is_active` không hiện dù còn bản ghi gán; chưa gán thì rỗng; thứ tự bám `sort_order` chứ không bám thứ tự bản ghi gán; `pluck()` trả chuỗi vẫn khớp; cờ `chuaPhanCongKhoa` đúng cho cả ba trường hợp.

Đối chiếu dữ liệu thật: khoa hoạt động `[2,3,4]`, user 3864 được gán `[4]` → thấy `[4]`; admin → `[2,3,4]`; chưa gán → `[]` kèm `no_assignment = true`.

Suite `Unit`: 228/228 xanh.

## Còn lại

Chưa kiểm trên trình duyệt (cùng lý do với đợt form builder — không có phiên đăng nhập). Cần nghiệm thu tay:

- [ ] Đăng nhập tài khoản **được gán 1 khoa** → chỉ thấy khoa đó; mở tab Network xem phản hồi `show` **không chứa** khoa khác.
- [ ] Tài khoản đó **không thấy** nút Trình chiếu và Xuất Excel.
- [ ] Gõ thẳng URL `khth/giao-ban/export` bằng tài khoản đó → **403**.
- [ ] Đăng nhập tài khoản **chưa được gán khoa** → thấy hộp vàng "Bạn chưa được phân công khoa nào".
- [ ] Tài khoản khoa **không thấy** hai ô Từ/Đến thời điểm; chỉ còn Ngày giao ban + Làm mới.
- [ ] Chọn một ngày **KHTH chưa lấy số liệu** bằng tài khoản khoa → thấy hộp xanh "Phòng KHTH chưa lấy số liệu…", **không** thấy dòng bảo bấm nút không có.
- [ ] Đăng nhập **admin** → thấy đủ 3 khoa, đủ hai ô thời gian, trình chiếu và xuất Excel chạy như cũ.
- [ ] Admin bấm Trình chiếu → vẫn hiện toàn viện kèm công suất giường.
