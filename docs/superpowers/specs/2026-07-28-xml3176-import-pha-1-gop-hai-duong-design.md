# Import XML3176 — Giai đoạn 1: gộp hai đường import về một chỗ

Ngày: 2026-07-28
Phạm vi: `BHYTXml3176Controller@uploadData/processXmlData`, `Console\Commands\XML3176Import`, lớp mới `App\Services\Xml3176\Xml3176Importer`

Giai đoạn 1 của bốn giai đoạn. Xem "Bối cảnh" bên dưới để biết vì sao việc này phải làm trước.

## Vấn đề

Nghiệp vụ nhập một hồ sơ XML3176 — đọc phong bì `GIAMDINHHS`, duyệt từng `FILEHOSO`, giải
mã base64, phân loại `LOAIHOSO`, gọi đúng hàm lưu, rồi chạy kiểm tra tổng thể — được cài
đặt **hai lần**:

| | `BHYTXml3176Controller::processXmlData()` | `XML3176Import::importFilesFromDisk()` |
|---|---|---|
| Nguồn | file người dùng tải lên | thư mục đĩa (`xml3176`, `xml3176tt`) |
| Loại XML xử lý | XML1–XML15 | XML1–**XML18** |
| Cấu hình `exportable_tt` | không có | có |
| Gặp file hỏng | trả `false`, báo lên giao diện | `return false` — **dừng cả lượt quét** |

### Hai bản đã lệch nhau thật

Đây không còn là rủi ro lý thuyết. Hồ sơ chứa XML16/17/18 nhập bằng tay sẽ rơi vào nhánh
`default` và chỉ để lại một dòng cảnh báo trong log; nhập bằng luồng quét đĩa thì được
nhận (dưới dạng bỏ qua có chủ đích). Cùng một hồ sơ, hai kết quả khác nhau tuỳ đường vào.

### Một lỗi vận hành nghiêm trọng đi kèm

`importFilesFromDisk()` dùng `return false` ở ba chỗ khi gặp file hỏng, nhưng ba chỗ đó
nằm **giữa vòng lặp quét thư mục**. Hậu quả dây chuyền:

1. Một file XML sai cấu trúc làm dừng cả lượt quét — mọi file xếp sau bị bỏ qua.
2. File hỏng không bị xoá (lệnh `delete()` nằm sau, không tới được).
3. Lệnh chạy vòng lặp vô hạn với `sleep(3)`, nên lượt sau lại vấp đúng file đó.

Kết quả: **một file hỏng làm tắc vĩnh viễn luồng import tự động**, lặp lại mỗi 3 giây, và
dấu hiệu duy nhất là một dòng log.

## Bối cảnh — vì sao gộp phải làm trước

Ba giai đoạn sau đều sửa vào chính khối nghiệp vụ này:

- Giai đoạn 2 — bọc transaction, báo lỗi trung thực, chỉ xoá file nguồn khi chắc thành công
- Giai đoạn 3 — đưa import ra khỏi request HTTP
- Giai đoạn 4 — gộp job kiểm lỗi theo hồ sơ thay vì theo dòng

Nếu chưa gộp thì mỗi thay đổi phải làm hai lần, ở hai file, và hai bản sẽ tiếp tục lệch —
đúng cách chúng đã lệch tới hôm nay.

## Thiết kế

### Lớp `App\Services\Xml3176\Xml3176Importer`

Một điểm vào duy nhất cho cả hai đường:

```php
public function nhapTuChuoi(string $noiDungXml, array $tuyChon = []): Xml3176ImportResult
```

`$tuyChon` hiện chỉ có một khoá:

- `cho_phep_xuat` (bool, mặc định `true`) — có gọi `exportXml3176()` sau khi nhập xong không.

Chính sách riêng của luồng quét đĩa **ở lại trong lệnh console**, nơi nó thuộc về:

```php
'cho_phep_xuat' => !($disk === 'xml3176tt' && config('xml3176.exportable_tt') == false)
```

Lớp importer không biết gì về `$disk`.

### Giá trị trả về `Xml3176ImportResult`

Trả về đối tượng thay vì `bool`: controller cần thông điệp lỗi để hiện lên giao diện, còn
lệnh console cần biết có được xoá file nguồn hay không.

```php
class Xml3176ImportResult
{
    public $thanhCong;    // bool
    public $maLk;         // string|null
    public $loaiDaXuLy;   // array<string>
    public $lyDoThatBai;  // string|null
}
```

### Đăng ký loại XML

Thay khối `switch` 15–18 nhánh bằng một bảng ánh xạ. Đây là hợp của **cả hai** bản cũ:

```php
const LOAI_XML = [
    'XML1'  => 'storeXml3176Xml1',
    'XML2'  => 'storeXml3176Xml2',
    'XML3'  => 'storeXml3176Xml3',
    'XML4'  => 'storeXml3176Xml4',
    'XML5'  => 'storeXml3176Xml5',
    'XML6'  => 'storeXml3176Xml6',
    'XML7'  => 'storeXml3176Xml7',
    'XML8'  => 'storeXml3176Xml8',
    'XML9'  => 'storeXml3176Xml9',
    'XML10' => 'storeXml3176Xml10',
    'XML11' => 'storeXml3176Xml11',
    'XML12' => null,   // bo qua CO CHU DICH
    'XML13' => 'storeXml3176Xml13',
    'XML14' => 'storeXml3176Xml14',
    'XML15' => 'storeXml3176Xml15',
    'XML16' => null,   // bo qua CO CHU DICH
    'XML17' => null,   // bo qua CO CHU DICH
    'XML18' => null,   // bo qua CO CHU DICH
];
```

Đã đối chiếu với `Xml3176Service`: đúng 14 phương thức `storeXml3176XmlN` tồn tại
(XML1–XML11, XML13–XML15). Không có `storeXml3176Xml12` — khớp với việc XML12 ánh xạ
`null` ở cả hai bản cũ.

```php
```

`null` nghĩa là **bỏ qua có chủ đích**, khác hẳn "không có trong bảng" (loại lạ → ghi
cảnh báo). Phân biệt này quan trọng: nó là thứ đã mất khi hai bản lệch nhau.

### Luồng xử lý một hồ sơ

1. Parse chuỗi XML. Không parse được → `thanhCong = false`, nêu lý do.
2. Thiếu hoặc rỗng `THONGTINDONVI->MACSKCB` → `thanhCong = false`, nêu lý do.
3. Tính `$soluonghoso` **một lần** trước vòng lặp. Bản controller đang tính lại trong mỗi
   vòng `FILEHOSO` — giá trị không đổi nên đây là thay đổi bảo toàn hành vi.
4. Duyệt `FILEHOSO`: giải mã base64, parse, tra `LOAI_XML`.
   - XML1: kiểm cấu trúc bằng `XmlStructures::$expectedStructures3176`; sai → dừng hồ sơ
     này, `thanhCong = false`. Lấy `ma_lk`, gọi `deleteExistingXml3176()`, rồi lưu.
   - Loại có handler: gọi hàm tương ứng trên `Xml3176Service`.
   - Loại ánh xạ `null`: bỏ qua, không ghi log.
   - Loại lạ: ghi `Log::warning`, đi tiếp — **không** làm hỏng cả hồ sơ.
5. Có `ma_lk` và đã xử lý ít nhất một loại → `storeXml3176Information()`, rồi
   `checkXml3176Complete()` (trừ khi `organization.xml_3176_not_check`), rồi
   `exportXml3176()` nếu `xml3176.export_xml3176_enabled` **và** `cho_phep_xuat`.

### Sửa lỗi tắc luồng quét đĩa

`importFilesFromDisk()` chuyển sang: mỗi file là một lượt độc lập, file hỏng thì **bỏ qua
file đó và đi tiếp**, không dừng lượt quét.

Đây là **thay đổi hành vi duy nhất có chủ đích** của giai đoạn 1. Nó không phải "tiện tay
làm luôn": đoạn `return false` nằm đúng trong khối mã đang được di chuyển, và để nguyên
thì bản gộp sẽ mang theo một lỗi làm tắc hệ thống.

File hỏng vẫn **không bị xoá** — giữ nguyên hành vi hiện tại, để còn dữ liệu mà điều tra.
Hệ quả: file hỏng sẽ được thử lại mỗi lượt quét. Giai đoạn 2 xử lý việc chuyển nó sang
thư mục riêng.

## Không thuộc phạm vi giai đoạn này

Giai đoạn 1 là **tái cấu trúc**: cùng dữ liệu vào, cùng dữ liệu ra, trừ đúng một ngoại lệ
đã nêu ở trên. Những lỗi sau **được giữ nguyên có chủ đích** và xử lý ở giai đoạn sau:

1. **Không có transaction.** `deleteExistingXml3176()` xoá 13 bảng rồi ghi lại từng phần;
   đứt giữa chừng là mất dữ liệu cũ lẫn mới. → Giai đoạn 2.
2. **Lỗi ghi từng dòng bị nuốt.** `try/catch` trong `Xml3176Service` ghi log rồi đi tiếp;
   người dùng vẫn nhận "processed successfully". → Giai đoạn 2.
3. **`soluonghoso` luôn bằng 1.** Dùng `count()` trên node lá thay vì ép kiểu. Đã kiểm
   chứng: giá trị thật 37 → lưu 1. → Giai đoạn 2.
4. **File nguồn bị xoá sau khi xử lý, ngoài mọi transaction.** Xử lý hỏng dở dang mà file
   vẫn mất → không còn đường phục hồi. → Giai đoạn 2.
5. **Import chạy đồng bộ trong request HTTP.** → Giai đoạn 3.
6. **Một job kiểm lỗi cho mỗi dòng**, và `deleteErrors()` nằm trong job XML1 nên trạng
   thái lỗi phụ thuộc thứ tự hàng đợi. → Giai đoạn 4.

### Hai phát hiện thêm, ghi lại để không thất lạc

**a. `deleteExistingXml3176()` phụ thuộc thứ tự `FILEHOSO`.** Nó chỉ chạy khi gặp XML1.
Nếu một file liệt kê XML2 trước XML1 thì các dòng XML2 vừa ghi sẽ bị xoá ngay sau đó.
Chưa có bằng chứng file thực tế nào như vậy — bộ xuất của đơn vị nhiều khả năng luôn đặt
XML1 đầu tiên — nên đây là **rủi ro chưa xác nhận**, không phải lỗi đã quan sát được.
Cách chữa (sắp XML1 lên đầu trước khi duyệt) thuộc giai đoạn 2.

**b. Chỉ hồ sơ `HOSO` đầu tiên được xử lý.** Cả hai bản đều duyệt
`DANHSACHHOSO->HOSO->FILEHOSO`, mà SimpleXML lấy phần tử đầu. Tên thẻ `DANHSACHHOSO`
(số nhiều) và trường `SOLUONGHOSO` gợi ý cấu trúc cho phép nhiều `HOSO` trong một file.
Nếu đúng vậy thì mọi hồ sơ từ thứ hai trở đi **đang bị bỏ im lặng**. Cần đối chiếu với
quy định 3176 và với file thật trước khi đụng tới — không sửa mò.

## Kiểm chứng

**Tự động** — trọng tâm là chứng minh phép gộp không đánh rơi gì:

- Bảng `LOAI_XML` phủ đủ XML1…XML18 — không thiếu mã nào so với **hợp** của hai bản cũ.
- XML12, XML16, XML17, XML18 ánh xạ `null` (bỏ qua có chủ đích), **không** vắng mặt.
- Mọi handler khác `null` là phương thức public có thật trên `Xml3176Service`
  (`method_exists`) — bắt lỗi gõ sai tên hàm.
- `nhapTuChuoi` với chuỗi không parse được → `thanhCong = false`, có `lyDoThatBai`.
- `nhapTuChuoi` với XML thiếu `MACSKCB` → `thanhCong = false`, có `lyDoThatBai`.
- Không controller/command nào còn chứa `case 'XML` — chỉ còn một nơi biết bảng ánh xạ.

Cổng: `vendor/bin/phpunit --testsuite Unit`. Mốc hiện tại 282 test xanh.

**Thủ công** — DB dev trống cả bốn bảng `xml3176_*` nên không chạy được import thật tại chỗ:

| # | Việc | Mong đợi |
|---|---|---|
| 1 | Tải lên một file XML hợp lệ qua giao diện | Nhập thành công, hồ sơ hiện trong danh sách như trước |
| 2 | Tải lên file sai cấu trúc | Báo lỗi trên giao diện, nêu tên file |
| 3 | Tải lên nhiều file, trong đó một file hỏng | Các file còn lại vẫn được nhập; chỉ file hỏng bị báo |
| 4 | Đặt một file hỏng vào thư mục `xml3176` | Lượt quét **bỏ qua nó và xử lý tiếp các file khác** — trước đây tắc toàn bộ |
| 5 | Đặt file hợp lệ vào `xml3176tt` với `exportable_tt = false` | Nhập vào nhưng **không** xuất XML — đúng như trước |
| 6 | Đặt file hợp lệ vào `xml3176` | Nhập vào **và** xuất XML nếu `export_xml3176_enabled` |
| 7 | Hồ sơ chứa XML16/17/18 nhập bằng tay | Không còn cảnh báo "Unknown XML type" trong log |
| 8 | So số dòng từng bảng con của một hồ sơ nhập trước/sau | Bằng nhau |

Mục 4 và 5 là hai mục dễ trôi nhất: mục 4 là thay đổi hành vi có chủ đích, mục 5 là chính
sách riêng của luồng đĩa mà bản gộp phải giữ được.
