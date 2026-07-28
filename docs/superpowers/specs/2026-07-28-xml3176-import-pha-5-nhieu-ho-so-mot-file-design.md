# Import XML3176 — Giai đoạn 5: một file nhiều hồ sơ

Ngày: 2026-07-28
Phạm vi: `App\Services\Xml3176\Xml3176Importer`, `Console\Commands\XML3176Import`

Giai đoạn 5, phát sinh ngoài kế hoạch bốn giai đoạn ban đầu.

## Vấn đề — mất dữ liệu đã được xác nhận trên file thật

Câu hỏi "một file `GIAMDINHHS` có được chứa nhiều `HOSO` không" đã treo suốt các giai đoạn
trước. Chủ đầu tư cung cấp file thật `DATA_XML_20260728_110149.xml`, và câu trả lời là **có**:

| | |
|---|---|
| `SOLUONGHOSO` khai báo | **2** |
| Số thẻ `<HOSO>` thực tế | **2** |
| Mỗi hồ sơ | 6 `FILEHOSO` — XML1, XML2, XML3, XML4, XML5, XML14 |
| Hai `ma_lk` | **khác nhau — hai bệnh nhân khác nhau** |
| Vòng lặp hiện tại duyệt được | **6** — chỉ hồ sơ đầu tiên |

**Hồ sơ thứ hai bị bỏ hoàn toàn**: không nhập, không lỗi, không một dòng log. Người dùng
nhận "processed successfully".

### Vì sao nó sống lâu như vậy

```php
foreach ($xmldata->THONGTINHOSO->DANHSACHHOSO->HOSO->FILEHOSO as $file_hs)
```

Trong SimpleXML, `->HOSO` trên một tập nhiều phần tử **tự lấy phần tử đầu**, không cảnh
báo, không lỗi. Cả hai bản cài đặt cũ đều viết như vậy, và bản gộp ở giai đoạn 1 **bê
nguyên** — vì đó là tái cấu trúc, cố ý không đổi hành vi.

Trường `SOLUONGHOSO` lẽ ra có thể phát hiện chênh lệch, nhưng nó luôn bằng 1 do lỗi
`count()` trên node lá, mãi tới giai đoạn 2 mới sửa.

### Mức độ nghiêm trọng

Đây là lỗi nặng nhất trong toàn bộ đợt rà soát. Hồ sơ không được nhập thì không được xuất
lên BHXH — **không được thanh toán**, và không ai biết để đòi.

## Thiết kế

### Duyệt mọi `HOSO`, mỗi hồ sơ một transaction riêng

Tách phần thân xử lý một hồ sơ thành phương thức riêng, rồi `nhapTuChuoi()` lặp qua từng
`HOSO`:

```php
private function nhapMotHoSo($hoSo, string $macskcb, int $soluonghoso, bool $choPhepXuat): Xml3176ImportResult
```

**Một hồ sơ hỏng không được kéo hồ sơ còn lại xuống theo.** Mỗi hồ sơ có transaction riêng
và kết quả riêng; hỏng thì ghi nhận rồi đi tiếp.

Việc dispatch job kiểm lỗi, `checkXml3176Complete` và `exportXml3176` cũng nằm trong phạm
vi một hồ sơ, ngay sau commit của chính nó.

### Kết quả cấp file

Lớp mới `Xml3176ImportFileResult`:

```php
public $thanhCong;     // moi ho so deu thanh cong VA so luong khop
public $lyDoThatBai;   // ly do gop, neu co
public $ketQua;        // array<Xml3176ImportResult> - tung ho so
public $soThanhCong;
public $soThatBai;
public $dsMaLk;        // cac ma_lk nhap thanh cong
```

Giữ đúng hai tên `thanhCong` và `lyDoThatBai` như `Xml3176ImportResult`, nên các nơi gọi
hiện tại và các test đã có **không phải sửa** vì lý do đổi kiểu.

### Đối chiếu `SOLUONGHOSO` — bất đối xứng có chủ đích

| Tình huống | Xử lý | Lý do |
|---|---|---|
| thực tế **ít hơn** khai báo | **Từ chối cả file** | File có thể bị cắt cụt; nhập một phần rồi báo thành công chính là lỗi mà đợt này đi chữa |
| thực tế **nhiều hơn** khai báo | Ghi cảnh báo, vẫn nhập | Metadata sai nhưng dữ liệu đủ; chặn ở đây là chặn nhầm |
| bằng nhau | Không nói gì | |

Sự bất đối xứng này là có chủ đích: chỉ chặn khi **có khả năng mất dữ liệu**, không chặn
vì một con số metadata lệch.

**Cần theo dõi sau khi triển khai.** Tôi chỉ có **một** file mẫu để suy ra rằng
`SOLUONGHOSO` đáng tin trong quy trình của đơn vị. Nếu bộ xuất nào đó ghi trường này sai
thì luật trên sẽ chặn nhầm — log sẽ cho biết.

### Nơi gọi

`XML3176Import` in ra `$kq->maLk`; đổi thành danh sách mã đã nhập. Controller không phải
sửa vì hai tên thuộc tính giữ nguyên.

## Không thuộc phạm vi

1. **Dữ liệu đã mất trong quá khứ.** Đợt này chặn việc mất tiếp, **không** phục hồi những
   hồ sơ đã bị bỏ. Muốn biết thiệt hại thì đếm `<HOSO>` trong các file nguồn còn giữ —
   file nào có nhiều hơn 1 là đã mất hồ sơ từ thứ hai trở đi.
2. Checker tra danh mục cho từng dòng (18 chỗ) — giai đoạn riêng.
3. Các nợ đã ghi ở các đợt trước.

## Kiểm chứng

**Tự động:**

- `demHoSo()` đếm đúng số `HOSO`; thiếu thẻ → 0.
- Tổng hợp kết quả cấp file: mọi hồ sơ thành công → `thanhCong` đúng; một hồ sơ hỏng →
  `thanhCong` sai nhưng `soThanhCong` vẫn đếm đủ phần chạy được.
- Thực tế ít hơn khai báo → từ chối, lý do nêu rõ hai con số.
- Thực tế nhiều hơn khai báo → vẫn thành công.
- **Hàng rào nguồn:** importer không còn dùng `->HOSO->FILEHOSO` trực tiếp — chính là hình
  dạng của lỗi.
- Các test `Xml3176ImporterParseTest` hiện có vẫn xanh.

Cổng: `vendor/bin/phpunit --testsuite Unit`. Mốc hiện tại **333 test xanh**.

**Thủ công** — dùng chính file `DATA_XML_20260728_110149.xml`:

| # | Việc | Mong đợi |
|---|---|---|
| 1 | Nhập file mẫu qua giao diện | **Cả hai** hồ sơ vào cơ sở dữ liệu, không phải một |
| 2 | Tìm hai `ma_lk` trong màn danh sách | Cả hai đều có mặt |
| 3 | Mở chi tiết từng hồ sơ | Đủ 6 loại XML mỗi hồ sơ |
| 4 | Nhập file chỉ có một hồ sơ | Vẫn như trước, không hồi quy |
| 5 | Sửa `SOLUONGHOSO` thành 3 rồi nhập lại | **Từ chối cả file**, lý do nêu 3 và 2 |
| 6 | Sửa `SOLUONGHOSO` thành 1 rồi nhập lại | Vẫn nhập cả hai, có cảnh báo trong log |
| 7 | Làm hỏng hồ sơ thứ hai, giữ nguyên hồ sơ đầu | Hồ sơ đầu vẫn vào; chỉ hồ sơ thứ hai bị báo |
| 8 | Theo dõi log vài ngày | Có file nào bị từ chối vì lệch `SOLUONGHOSO` không |

**Mục 1 là mục chứng minh cả đợt này.** Mục 7 chứng minh một hồ sơ hỏng không kéo hồ sơ
còn lại xuống theo. Mục 8 là cách duy nhất biết luật đối chiếu có chặn nhầm hay không.
