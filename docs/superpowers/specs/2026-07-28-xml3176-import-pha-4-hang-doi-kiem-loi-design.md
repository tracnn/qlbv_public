# Import XML3176 — Giai đoạn 4: hàng đợi kiểm lỗi

Ngày: 2026-07-28
Phạm vi: `Xml3176Service`, `Xml3176Xml1Checker`, `Xml3176ErrorService`, `Xml3176Importer`, một job mới

Giai đoạn 4 của bốn.

## Vấn đề

### 1. Một job kiểm lỗi cho mỗi dòng

`CheckXml3176ErrorsJob::dispatch($xml2, $xmlType)` nằm **trong vòng lặp từng dòng** của
12 phương thức `storeXml3176Xml*`. Hồ sơ 600 dòng sinh 600 job, mỗi job serialize cả một
model. Import 1.000 hồ sơ là hàng trăm nghìn dòng trong bảng `jobs`.

### 2. Xoá lỗi cũ nằm trong job, nên phụ thuộc thứ tự hàng đợi

`deleteErrors($ma_lk)` chỉ được gọi từ `Xml3176Xml1Checker::checkErrors()` — tức từ bên
trong một job — và nó xoá **toàn bộ** lỗi của hồ sơ, bất kể loại. `saveErrors()` thì luôn
`create()`, không bao giờ dọn trước.

Với một worker FIFO thì thứ tự thường đúng. Nhưng job XML1 chạy lại sau retry sẽ **xoá
sạch lỗi mà 11 job kia vừa tìm ra**. Đúng do may, không do thiết kế.

### 3. `saveErrors()` bắn ba truy vấn cho mỗi lỗi

```php
foreach ($errors as $error) {
    Xml3176ErrorCatalog::where('error_code', ...)->where('is_check', false)->exists();
    Xml3176ErrorResult::create($data);
    Xml3176ErrorCatalog::createOrUpdate($xmlType, $error->error_code, ...);
}
```

Hồ sơ 200 lỗi → **600 truy vấn**. Dòng thứ ba ghi đè **cùng một dòng danh mục** lặp lại:
50 lỗi cùng mã là 50 lần ghi y hệt nhau.

## Thiết kế

### A. Một job cho mỗi cặp *(hồ sơ, loại XML)*

Job mới `CheckXml3176TypeJob($maLk, $xmlType)`:

1. Tra bảng đăng ký lấy lớp model và lớp checker; loại lạ → ném lỗi.
2. Xoá lỗi **của riêng loại mình** cho hồ sơ đó.
3. Nạp các dòng của loại đó theo `ma_lk`, chạy checker từng dòng.

Bước 2 làm mỗi job **tự idempotent**: chạy lại bao nhiêu lần cũng ra một kết quả, không
phụ thuộc thứ tự hàng đợi hay retry. Đây mới là thứ thật sự dập tắt vấn đề 2.

Bảng đăng ký 12 cặp — đúng 12 loại mà `CheckXml3176ErrorsJob` đang xử lý (XML1–XML5,
XML7–XML11, XML13, XML14). XML6, XML12, XML15 **không có checker**, và điều đó là có sẵn,
không phải thiếu sót của đợt này.

`Xml3176Service` bỏ 12 lời gọi dispatch trong vòng lặp. `Xml3176Importer` dispatch **sau
commit**, một job cho mỗi loại đã xử lý **và** có trong bảng đăng ký, rồi mới tới
`checkXml3176Complete()` — giữ đúng thứ tự FIFO hiện nay (kiểm từng loại trước, kiểm tổng
thể sau).

### B. Xoá lỗi cũ đặt ở hai nơi, mỗi nơi một lý do

- **Trong transaction nhập**, cùng chỗ `deleteExistingXml3176()` xoá dữ liệu hồ sơ. Lỗi
  thuộc về hồ sơ; xoá dữ liệu thì xoá lỗi. Việc `deleteExistingXml3176()` hiện **không**
  xoá lỗi trong khi `deleteXml3176XmlAndError()` thì có, là một điểm bất nhất sẵn có.
  Chỗ này dọn lỗi của loại XML **không còn xuất hiện** sau khi nhập lại.
- **Đầu mỗi job**, xoá lỗi của riêng loại mình. Chỗ này lo tính idempotent.

Bỏ `deleteErrors()` khỏi `Xml3176Xml1Checker::checkErrors()`.

### C. Gom ghi lỗi ở tầng service, không đụng checker

`saveErrors()` được gọi **từng dòng** từ bên trong checker. Muốn gộp mà không sửa checker
thì phải gom ở `Xml3176ErrorService`:

```php
public function batDauGom(): void      // bat che do gom
public function ketThucGom(): void     // ghi tat ca roi tat che do gom
```

Khi đang gom, `saveErrors()` **chỉ đẩy vào bộ đệm trong bộ nhớ**, không chạm cơ sở dữ liệu.
Khi tắt gom:

1. Một truy vấn `whereIn('error_code', <cac ma khac nhau>)->where('is_check', false)` lấy
   danh sách mã bị tắt kiểm tra, rồi lọc bộ đệm. **Cùng kết quả** với việc hỏi từng lỗi.
2. Chèn hàng loạt bằng `insert()`.
3. `updateOrCreate` danh mục **chỉ cho các cặp *(xml, mã lỗi)* khác nhau**.

Từ 3N truy vấn còn khoảng 3 + số cặp khác nhau.

Job gọi `batDauGom()` trước vòng lặp dòng và `ketThucGom()` trong `finally` — hỏng giữa
chừng thì phần đã tìm được vẫn ghi, và không rò bộ đệm sang job sau.

Ngoài chế độ gom, `saveErrors()` giữ **nguyên hành vi cũ**, để mọi nơi gọi khác không đổi.

#### Ba cái bẫy phải xử lý đúng

**Dấu thời gian.** `insert()` **không** tự điền `created_at`/`updated_at` như `create()`.
Bảng `xml3176_error_results` có `timestamps()` và **có index trên cả hai cột** — quên là
dữ liệu sai và các bộ lọc theo ngày hỏng theo. Phải tự điền.

**Số cột không đồng nhất.** `saveErrors()` nhận `$additionalData` tuỳ nơi gọi (có nơi
truyền `ngay_yl`/`ngay_kq`, có nơi không). `insert()` nhiều dòng lấy tên cột từ **dòng đầu
tiên**, nên trộn lẫn các dòng khác bộ cột sẽ lệch dữ liệu. Phải **gom theo bộ cột** rồi
chèn từng nhóm.

**Kích thước lô.** Chèn mỗi nhóm theo lô 500 dòng, tránh câu lệnh quá lớn và chạm giới hạn
tham số của driver.

### D. Giữ lại `CheckXml3176ErrorsJob` dù không còn ai dispatch

Xoá file đi thì sạch hơn, nhưng **không xoá**: tại thời điểm deploy, hàng đợi sản xuất gần
như chắc chắn còn job cũ đang chờ. Mất lớp là chúng không unserialize được và chết hàng
loạt, kéo theo mất kết quả kiểm lỗi của những hồ sơ vừa nhập.

Đánh dấu không dùng nữa trong chú thích lớp; xoá ở một đợt sau, khi hàng đợi đã rút cạn.

## Không thuộc phạm vi

1. **Checker tra danh mục cho từng dòng** — 18 chỗ, tập trung ở XML3 (10), XML2 (5),
   XML4 (3), đúng ba bảng nhiều dòng nhất. Riêng XML2 gọi `MedicineCatalog::where('ma_thuoc', ...)`
   bốn lần cho một dòng. Đây là ruột các luật giám định: sai một nhánh là hồ sơ được duyệt
   sai hoặc bị bắt lỗi oan, mà đó là tiền thanh toán BHYT. **Xứng đáng có spec riêng**, với
   ràng buộc cứng "chỉ đổi nguồn tra cứu, không đổi một điều kiện nào" và test đối chiếu
   kết quả trước/sau trên dữ liệu thật.
2. **Nút "Kiểm tra lại" một hồ sơ.** Cấu trúc mới làm việc này gần như miễn phí — mỗi job
   đã tự idempotent và nhận `(mã hồ sơ, loại XML)`. Nhưng đó là **tính năng mới**, không
   phải sửa lỗi; để chủ đầu tư quyết riêng.
3. Các nợ đã ghi ở các đợt trước.

## Kiểm chứng

**Tự động:**

- Bảng đăng ký phủ đúng 12 loại mà `CheckXml3176ErrorsJob` đang xử lý — không thừa, không thiếu.
- Mọi lớp model và checker trong bảng đăng ký đều tồn tại (`class_exists`), và checker có
  phương thức `checkErrors`.
- `Xml3176Service` **không còn** dispatch trong vòng lặp dòng.
- `Xml3176Xml1Checker` **không còn** gọi `deleteErrors`.
- `deleteExistingXml3176()` có xoá `Xml3176ErrorResult`.
- Chế độ gom: bật gom rồi `saveErrors` nhiều lần thì **không** chạm cơ sở dữ liệu cho tới
  khi tắt gom; tắt gom hai lần không nổ; bộ đệm được dọn sau khi tắt.
- Gom theo bộ cột: các dòng có `$additionalData` khác nhau được tách nhóm đúng.
- Dòng chèn có `created_at` và `updated_at`.

Cổng: `vendor/bin/phpunit --testsuite Unit`. Mốc hiện tại **311 test xanh**.

**Thủ công** — DB dev trống cả bốn bảng `xml3176_*`.

**Chuẩn bị: ghi lại danh sách lỗi của một hồ sơ (mã lỗi + số lượng) trước khi thử.**

| # | Việc | Mong đợi |
|---|---|---|
| 1 | Nhập lại một hồ sơ đã có lỗi | Danh sách lỗi **giống hệt trước**: cùng mã, cùng số lượng, cùng `stt` |
| 2 | Đếm dòng trong bảng `jobs` ngay sau khi nhập một hồ sơ nhiều dòng | Vài job thay vì hàng trăm |
| 3 | Kiểm `created_at` của các dòng lỗi mới | Có giá trị, không rỗng |
| 4 | Nhập một hồ sơ mà lần trước có lỗi XML3, lần này file không còn phần XML3 | Lỗi XML3 cũ **biến mất**, không sót lại |
| 5 | Bảng `xml3176_error_catalogs` sau khi nhập | Không sinh dòng trùng; nội dung như trước |
| 6 | Lọc danh sách hồ sơ theo mã lỗi | Vẫn ra đúng hồ sơ như trước |
| 7 | Sau khi deploy, xem hàng đợi `JobXml3176` | Job cũ còn tồn đọng vẫn chạy được, không lỗi unserialize |

**Mục 1 là mục quan trọng nhất** và cũng dễ trôi nhất: cả ba thay đổi trên đều đụng vào
đường ghi lỗi, nên phải chứng minh kết quả **không đổi một chút nào**. Không có ảnh chụp
danh sách lỗi trước khi sửa thì không kiểm được.

Mục 7 kiểm đúng lý do giữ lại `CheckXml3176ErrorsJob`.
