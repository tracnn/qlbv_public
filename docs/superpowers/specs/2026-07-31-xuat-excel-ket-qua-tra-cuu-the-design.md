# Xuất Excel màn Kết quả tra cứu thẻ BHYT

Ngày: 2026-07-31

## Mục tiêu

Màn Kết quả tra cứu thẻ có bộ lọc nhưng chưa xuất được ra tệp. Bổ sung nút xuất `.xlsx`
**đúng tập dữ liệu đang hiển thị**.

## Hiện trạng đo được

`CheckHeinCardController::fetch()` dựng truy vấn ngay trong thân hàm: lọc cơ sở, trạng thái,
khoảng ngày, ô tìm.

Khuôn xuất sẵn có trong dự án — `OrderCheckController::export()`:

```php
$fileName = 'sai_sot_y_lenh_' . Carbon::now()->format('YmdHis') . '.xlsx';
return Excel::download(new OrderCheckViolationExport($request->all()), $fileName);
```

Nút bấm gửi cùng tham số bộ lọc: `window.location = route + '?' + $.param(filters())`.

### `HeinCardErrorExport` — KHÔNG phải mã chết

Lần khảo sát đầu tôi kết luận lớp này mồ côi, **sai**: lệnh grep khi đó loại bỏ thư mục
`app/Exports/` khỏi kết quả.

Thực tế nó là **một sheet** bên trong hai bộ xuất nhiều sheet, cả hai đang chạy:

- `Qd130ErrorMultiSheetExport:34` ← `BHYTQd130Controller:640`
- `Xml3176ErrorMultiSheetExport:64` ← `BHYTXml3176Controller:689`

**Không xoá.** Xoá là hỏng hai chức năng xuất lỗi XML đang dùng.

Nó dùng quy tắc "lỗi" của job (`qd130xml.hein_card_invalid`), khác quy tắc `chiLoi()` của màn
mới. Đó **không** phải mâu thuẫn: nó là sheet trong bộ xuất lỗi XML nên dùng quy tắc của job
là hợp ngữ cảnh, còn bộ xuất mới không mang danh "lỗi" mà xuất đúng thứ bộ lọc đang chọn.

## Thiết kế

### 1. Một nơi dựng truy vấn

Tách phần lọc ra khỏi `fetch()` thành method dùng chung:

```php
protected function locTheoYeuCau(Request $request)
```

Cả `fetch()` lẫn `xuatExcel()` gọi nó.

Đây là điểm quan trọng nhất. Nếu mỗi bên tự dựng truy vấn thì thêm một bộ lọc mà quên bên kia
sẽ làm tệp xuất khác hẳn màn hình — và **không có dấu hiệu gì** cho tới lúc ai đó ngồi đối
chiếu từng dòng.

### 2. Nút xuất

Đặt cạnh bộ lọc, gửi **cùng tham số** mà DataTables đang dùng:

```js
window.location = "{{ route('bhyt.check-hein-card.export') }}?" + $.param(thamSoLoc());
```

Tách hàm `thamSoLoc()` ra khỏi `thamSo(d)` để cả DataTables lẫn nút xuất dùng chung một nguồn
— cùng lý do như trên, ở phía trình duyệt.

### 3. Cột xuất — nhiều hơn màn hình

Màn hình 10 cột cho dễ liếc. Tệp Excel để soi và đối chiếu nên xuất **đủ 25 trường** như modal
chi tiết, cộng cột STT.

Hai cột mã hiện **nhãn tiếng Việt** qua `NhanMaThe` — dùng lại hàm đã có, không tra bảng trực
tiếp.

Tên tệp: `ket_qua_tra_cuu_the_YYYYMMDDHHmmss.xlsx`.

### 4. Chống tệp lớn

Dùng `FromQuery`: Laravel Excel duyệt theo lô, không nạp cả bảng vào bộ nhớ. Bảng này phình
theo thời gian — mỗi hồ sơ một dòng.

Cộng `ini_set('memory_limit', '512M')` và `set_time_limit(600)` như các đường xuất khác.

**Không** giới hạn số dòng: giới hạn ngầm là cắt bớt im lặng mà người dùng tưởng đã xuất đủ.
Muốn ít hơn thì đã có bộ lọc ngày.

## Kiểm thử

Trong `tests/Unit`. Cổng: `vendor/bin/phpunit --testsuite Unit`.

`XuatExcelKetQuaTraCuuTheTest`:

1. `fetch()` và `xuatExcel()` **cùng gọi** `locTheoYeuCau` — không bên nào tự dựng truy vấn.
2. Route `bhyt.check-hein-card.export` tồn tại; blade có nút xuất trỏ đúng route đó.
3. Số phần tử `headings()` bằng số phần tử `map()` trả về.
4. `map()` hiện **nhãn** cho hai cột mã, không phải mã trần.
5. **Số dòng `query()` khớp `recordsFiltered` của `fetch()`** với cùng tham số, thử trên ba tổ
   hợp bộ lọc. Đây là bằng chứng trực tiếp cho "xuất đúng thứ đang nhìn thấy".
6. `HeinCardErrorExport` **vẫn tồn tại** và vẫn được hai bộ xuất nhiều sheet tham chiếu —
   chốt lại để không ai xoá nhầm như tôi suýt làm.

## Phạm vi không làm

- **Không** xoá `HeinCardErrorExport` — nó là sheet đang dùng trong hai bộ xuất lỗi XML.
- **Không** đổi quy tắc "lỗi" của `HeinCardErrorExport`.
- **Không** thêm định dạng màu/viền cho tệp xuất — dữ liệu để đối chiếu, không phải báo cáo trình bày.
- **Không** giới hạn số dòng xuất.
