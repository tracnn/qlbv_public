# Import XML3176 — Giai đoạn 2: an toàn dữ liệu

Ngày: 2026-07-28
Phạm vi: `App\Services\Xml3176\Xml3176Importer`, `App\Services\Xml3176Service` (15 khối `catch`), `Console\Commands\XML3176Import`

Giai đoạn 2 của bốn. Tiền đề: giai đoạn 1 đã gộp hai đường import về một chỗ, nên mọi
thay đổi dưới đây chỉ phải làm **một lần**.

## Vấn đề

### 1. Không có transaction — hồ sơ có thể mất cả dữ liệu cũ lẫn mới

`deleteExistingXml3176()` xoá sạch 13 bảng con của hồ sơ, rồi mới ghi lại từng phần.
Toàn bộ `Xml3176Service` (1.908 dòng) **không có một `DB::transaction` nào**.

Đứt giữa chừng là hồ sơ mất dữ liệu cũ mà chưa có dữ liệu mới, và không có gì phát hiện
ra. Đây không phải giả định: máy chủ mới đã giết request ở mốc 128 MB, và nghi can chưa
loại trừ được chính là `upload-data`.

### 2. Lỗi ghi bị nuốt — báo thành công trong khi thiếu dữ liệu

15 khối `catch` trong luồng import chỉ ghi log rồi đi tiếp:

| Vị trí | Số lượng | Hậu quả |
|---|---|---|
| **Trong vòng lặp dòng** — XML2, 3, 4, 5, 6, 9, 15 | 7 | Một dòng lỗi bị bỏ, các dòng khác vẫn ghi → hồ sơ **thiếu dòng** |
| **Quanh cả hàm** — XML1, 7, 8, 10, 11, 13, 14, Information | 8 | Cả một loại XML biến mất |

Cả hai trường hợp đều kết thúc bằng `Xml3176ImportResult::thanhCong()` và người dùng
nhận "File uploaded and processed successfully".

Với dữ liệu thanh toán BHYT, hồ sơ thiếu dòng **được xuất lên BHXH** là sai số liệu quyết
toán — tệ hơn hẳn việc không nhận.

### 3. `soluonghoso` luôn bằng 1

`count($xmldata->THONGTINHOSO->SOLUONGHOSO)` đếm số phần tử con của một node lá nên luôn
ra 1. Đã kiểm chứng: giá trị thật 37 → lưu 1.

### 4. Thứ tự `FILEHOSO` quyết định dữ liệu còn hay mất

`deleteExistingXml3176()` chỉ chạy khi gặp XML1. Nếu một file liệt kê XML2 trước XML1 thì
các dòng XML2 vừa ghi bị xoá ngay sau đó — im lặng.

Chưa quan sát được file thật nào như vậy, nhưng chi phí phòng là hai dòng mã.

### 5. File hỏng nằm lại thư mục quét, thử lại vô hạn

Giai đoạn 1 đã sửa việc một file hỏng làm tắc cả lượt quét. Nhưng file đó vẫn nằm nguyên
chỗ cũ, nên mỗi 3 giây lại được thử lại và lại ghi một dòng log lỗi.

## Quyết định của chủ đầu tư

**Chặt: một dòng hỏng thì từ chối cả hồ sơ.** Hồ sơ thiếu dòng mà vẫn xuất lên BHXH là
sai số liệu thanh toán.

**Rủi ro đã được nêu và chấp nhận:** nếu hiện đang có file thường xuyên hỏng vài dòng mà
không ai biết, sau thay đổi này chúng sẽ đồng loạt báo lỗi. Trông như vừa làm hỏng hệ
thống, thực ra là **lộ ra cái đã hỏng sẵn**. Cần theo dõi log ngay sau khi triển khai.

## Thiết kế

### Transaction bọc một hồ sơ

Trong `nhapTuChuoi()`, bọc **vòng lặp `FILEHOSO` + `storeXml3176Information()`** trong
`DB::transaction()`. Hỏng ở bất kỳ đâu là quay lui sạch, và vì `deleteExistingXml3176()`
cũng nằm trong đó nên **dữ liệu cũ của hồ sơ còn nguyên**.

`checkXml3176Complete()` và `exportXml3176()` gọi **sau khi transaction commit**. Hai hàm
này chỉ đẩy job, không ghi gì — đặt sau commit thì rollback không để lại job mồ côi.

Job kiểm lỗi từng dòng (`CheckXml3176ErrorsJob`) vẫn dispatch **bên trong** transaction.
Hàng đợi dùng driver `database` trên cùng connection nên rollback xoá luôn các job đó —
đúng như mong muốn.

Ngoại lệ thoát ra được bắt lại thành `Xml3176ImportResult::thatBai()` kèm thông điệp, để
người dùng thấy lý do thay vì một câu chung chung.

### 15 khối `catch` ném lại lỗi

Giữ nguyên dòng ghi log, thêm `throw $e;`. Thay đổi cơ học, mỗi chỗ một dòng.

**Không đụng hai khối ngoài luồng import:**

- `deleteXml3176XmlAndError()` dòng 74 — dùng cho chức năng xoá hồ sơ.
- `submitXmlToBHYT()` dòng 1886 — luồng gửi BHXH.

Ghi nhận riêng: khối ở dòng 74 viết `catch (Exception $e)` **không có dấu gạch chéo
ngược**, trong file khai `namespace App\Services` và không `use Exception`. Nó phân giải
thành `App\Services\Exception` — lớp không tồn tại — nên **không bao giờ khớp**, và
ngoại lệ vẫn thoát ra thay vì trả `false`. Lỗi tiềm ẩn, ngoài phạm vi giai đoạn này.

### `soluonghoso` đọc đúng giá trị

Tách thành hàm thuần để kiểm thử được:

```php
Xml3176Importer::soLuongHoSo($xmldata): int
```

Trả `(int)` giá trị của `THONGTINHOSO->SOLUONGHOSO`, hoặc `0` nếu thiếu.

**Đây là thay đổi dữ liệu nhìn thấy được**: cột `xml3176_informations.soluonghoso` của
hồ sơ nhập mới sẽ mang giá trị thật thay vì 1. Hồ sơ nhập trước đó vẫn giữ giá trị 1 —
đợt này **không** sửa dữ liệu cũ.

### XML1 xử lý trước

Gom `FILEHOSO` thành mảng rồi sắp XML1 lên đầu, giữ nguyên thứ tự tương đối của phần còn
lại. Tách thành hàm thuần:

```php
Xml3176Importer::sapXml1LenDau(array $danhSach): array
```

Nhận mảng chuỗi `LOAIHOSO`, trả mảng chỉ số theo thứ tự cần duyệt.

### File hỏng chuyển sang thư mục con `loi/`

Trong `importFilesFromDisk()`, khi nhập thất bại thì chuyển file sang `loi/<ten-file>`
trên **cùng disk**, thay vì để nguyên chỗ cũ.

Vì `Storage::allFiles()` quét đệ quy nên phải **bỏ qua mọi đường dẫn bắt đầu bằng `loi/`**
ở đầu vòng lặp, nếu không file hỏng lại được nhặt lên lần nữa.

Chọn thư mục con thay vì một disk mới: thêm disk đòi hỏi khai báo đường dẫn vật lý trong
`filesystems.php` và sửa cấu hình trên máy chủ. Thư mục con thì tự vận hành.

File tải lên qua giao diện vẫn bị xoá như hiện nay kể cả khi thất bại — đó chỉ là bản sao
tạm, bản gốc vẫn ở máy người dùng.

## Không thuộc phạm vi

1. **Import chạy đồng bộ trong request HTTP.** → Giai đoạn 3.
2. **Một job kiểm lỗi cho mỗi dòng**, và `deleteErrors()` nằm trong job XML1 nên trạng
   thái lỗi phụ thuộc thứ tự hàng đợi. → Giai đoạn 4.
3. **`catch (Exception)` thiếu gạch chéo ngược** ở `deleteXml3176XmlAndError()`.
4. **Chỉ hồ sơ `HOSO` đầu tiên được xử lý.** Cần đối chiếu quy định 3176 và file thật
   trước khi đụng tới — chưa đủ căn cứ, không sửa mò.
5. **Dữ liệu `soluonghoso` cũ** của các hồ sơ đã nhập vẫn là 1.

## Kiểm chứng

**Tự động:**

- `soLuongHoSo()` đọc đúng giá trị thật; thiếu thẻ → `0`; thẻ rỗng → `0`.
- `sapXml1LenDau()` đưa XML1 lên đầu, giữ nguyên thứ tự tương đối phần còn lại; không có
  XML1 thì giữ nguyên toàn bộ; mảng rỗng không nổ.
- **Hàng rào nguồn:** mọi khối `catch` bên trong các phương thức `storeXml3176*` của
  `Xml3176Service` đều phải có `throw` — nuốt lỗi trở lại là test đỏ.
- **Hàng rào nguồn:** `nhapTuChuoi()` phải chứa `DB::transaction`.
- Các test hiện có của `nhapTuChuoi` (chuỗi hỏng, thiếu MACSKCB, không có FILEHOSO) vẫn xanh.

Cổng: `vendor/bin/phpunit --testsuite Unit`. Mốc hiện tại 295 test xanh.

**Thủ công** — DB dev trống cả bốn bảng `xml3176_*`.

**Chuẩn bị: giữ bản sao vài file XML thật**, và **ghi lại số dòng từng bảng con của một
hồ sơ đã có** trước khi thử.

| # | Việc | Mong đợi |
|---|---|---|
| 1 | Nhập lại một hồ sơ đã có, bằng file hợp lệ | Số dòng từng bảng con khớp như cũ |
| 2 | Nhập một file hợp lệ mới | `xml3176_informations.soluonghoso` mang **giá trị thật**, không phải 1 |
| 3 | Nhập file có một dòng sai kiểu dữ liệu (ví dụ `SO_LUONG` là chữ) | **Từ chối cả hồ sơ**, báo lý do; dữ liệu cũ của hồ sơ đó **còn nguyên** |
| 4 | Sau mục 3, kiểm bảng `jobs` | Không có job kiểm lỗi mồ côi của hồ sơ vừa bị từ chối |
| 5 | Đặt file hỏng vào thư mục `xml3176` | File được chuyển sang `xml3176/loi/`, lượt quét sau **không** nhặt lại |
| 6 | Đặt vài file tốt cùng file hỏng | File tốt nhập bình thường, chỉ file hỏng bị chuyển đi |
| 7 | Theo dõi log một ngày sau khi triển khai | Đếm số hồ sơ bị từ chối — đây là con số **chưa từng nhìn thấy**, cần đối chiếu xem có phải lỗi dữ liệu có sẵn không |

Mục 3 và 7 là hai mục quan trọng nhất. Mục 3 chứng minh transaction thật sự bảo vệ dữ
liệu cũ. Mục 7 là cách duy nhất biết được rủi ro "lộ ra cái đã hỏng sẵn" lớn tới đâu.
