# Hồ sơ chưa ký số thì không gửi lên cổng BHXH

Ngày: 2026-07-30

## Mục tiêu

Hồ sơ chưa ký số mà gửi lên cổng BHXH thì cổng từ chối. Chặn tại chỗ và ghi nhận trạng thái
"chưa ký" **đúng hình dạng một lỗi gửi**, thay vì tốn một vòng gọi lên cổng để nhận lời từ chối.

## Hiện trạng đo được

`XMLSignService::signXml()` suy biến an toàn: không ký được thì trả
`['isSigned' => false, 'data' => $xmlContent, 'method' => null]` — nội dung gốc, không ném.
`processExportXml()` vẫn ghi tệp và vẫn dispatch job gửi. Không có quy tắc nào chặn.

Trên dữ liệu thật, `xml3176_informations` có **210 hồ sơ, cả 210 đều `is_signed = 0`**, và
`signed_error` rỗng ở tất cả — tức là chưa từng có lần ký nào **thất bại**, mà là chưa từng ký.

Lý do: cả hai phương thức ký đều tắt.

- `organization.usb_token_sign.enabled = false`
- `organization.xml_sign.enabled = false`

`signXml()` ghi log `XML signing is disabled` rồi trả nội dung chưa ký.

**Hệ quả phải nói rõ:** với cấu hình hiện tại, quy tắc này chặn **100%** hồ sơ. Hôm nay chưa
thấy gì vì `submit_xml_3176_enabled` cũng đang tắt. Nhưng ngày bật gửi mà chưa bật ký thì
không hồ sơ nào đi được. Đây là hành vi người dùng đã xác nhận muốn — nó biến **bật ký số
thành điều kiện bắt buộc để gửi**, không còn là tuỳ chọn.

## Thiết kế

### 1. Hàm thuần quyết định

`App\Services\Xml3176\QuyetDinhGui`:

```php
/**
 * @param bool $guiBat co gui len cong dang bat khong
 * @param bool $daKy   ho so da ky so chua
 * @return string 'gui' | 'chua_ky' | 'khong_gui'
 */
public static function nen($guiBat, $daKy)
```

Ba nhánh, tên trả về nói rõ ý định:

- `khong_gui` — chức năng gửi đang tắt. **Không làm gì cả**, kể cả ghi lỗi.
- `chua_ky` — gửi đang bật nhưng hồ sơ chưa ký. Không dispatch, ghi nhận lỗi.
- `gui` — dispatch bình thường.

Thứ tự ưu tiên quan trọng: **kiểm `guiBat` trước**. Khi chức năng gửi đang tắt thì không có
lần gửi nào diễn ra, nên ghi `submit_error` là bịa — người đọc sẽ tưởng đã thử gửi và thất bại.

Tách hàm thuần vì đây là logic ba nhánh có thứ tự ưu tiên, kiểm được không cần CSDL. (Nếu chỉ
là đọc một boolean thì không tách — test cho nó sẽ rỗng nghĩa.)

### 2. Áp vào hai luồng export

`Xml3176Service::processExportXml()` và `Qd130XmlService::processExportXml()` đều đã có
`$isSigned` trong biến cục bộ ngay phía trên, và cờ gửi đã được kiểm ở đó từ đợt trước.

```php
$quyetDinh = QuyetDinhGui::nen(
    config('organization.BHYT.submit_xml_3176_enabled', false),
    $isSigned
);

if ($quyetDinh === 'gui') {
    SubmitXml3176Job::dispatch($ma_lk, $filePath, $macskcb)
        ->onQueue(config('xml3176.submit_queue_name', 'JobSubmitXml3176'));
} elseif ($quyetDinh === 'chua_ky') {
    $this->storeXml3176Information($ma_lk, $macskcb, 'submit', 1,
        'Hồ sơ chưa ký số, không gửi lên cổng BHXH');
}
```

Đường QĐ130 dùng cờ `submit_xml_enabled` và `storeQd130XmlInfomation()`.

### 3. Ghi nhận qua cơ chế sẵn có

Nhánh `'submit'` của `storeXml3176Information()` khi `$error` khác null sẽ đặt `submit_error`
và để `submitted_at = null` — **đúng hình dạng của hồ sơ bị cổng từ chối**. Không cần cột mới,
không cần trạng thái mới.

Cột `submit_error` là `varchar(255)`; thông điệp dài 41 ký tự, thừa chỗ.

`BHYTXml3176Controller` đã select sẵn `submit_error` và `is_signed`, nên trạng thái hiện ra ở
màn danh sách mà không phải sửa giao diện.

### 4. Không thêm lớp chặn thứ hai trong job

Khác với cấu hình — thứ có thể đổi trong lúc job nằm chờ trong hàng đợi — trạng thái ký là
thuộc tính của **tệp đã ghi ra đĩa**, xác định xong ngay trước đó và không tự đổi. Thêm một
truy vấn CSDL mỗi job để kiểm lại thứ không thể đổi là việc thừa.

## Kiểm thử

Trong `tests/Unit`. Cổng: `vendor/bin/phpunit --testsuite Unit`.

**Hàm thuần** (`QuyetDinhGuiTest`):

1. `nen(true, true)` → `'gui'`.
2. `nen(true, false)` → `'chua_ky'`.
3. `nen(false, false)` → `'khong_gui'` — tắt gửi thì **không** ra `'chua_ky'`, vì không có lần
   gửi nào để mà lỗi.
4. `nen(false, true)` → `'khong_gui'`.
5. Nhận giá trị không phải boolean (`0`, `1`, `null`, `''`) vẫn phân nhánh đúng — `is_signed`
   đọc từ MySQL `tinyint(1)` về dạng `0`/`1`, và cấu hình có thể là chuỗi.

**Canary quét mã nguồn** (`ChuaKyKhongGuiTest`, dùng `Tests\Support\LocComment`):

1. `Xml3176Service` và `Qd130XmlService` đều gọi `QuyetDinhGui::nen`.
2. Trong cả hai, `QuyetDinhGui::nen` xuất hiện **trước** dòng dispatch tương ứng.
3. Cả hai chứa chuỗi `'chua_ky'` — tức có nhánh ghi nhận, không chỉ bỏ qua im lặng.

**Không test nào gọi cổng BHXH.**

## Nghiệm thu

- `vendor/bin/phpunit --testsuite Unit` xanh.
- `QuyetDinhGui::nen()` đúng cả bốn tổ hợp của hai cờ.

## Phạm vi không làm

- **Không** bật ký số. Bật `usb_token_sign` hay `xml_sign` là quyết định vận hành, cần USB
  token cắm sẵn hoặc thông tin HSM đúng.
- **Không** bật `submit_xml_3176_enabled`.
- **Không** vá ngược 210 hồ sơ đang `is_signed = 0` — chúng chưa từng được gửi
  (`submitted_at` rỗng toàn bộ), không có gì để sửa.
- **Không** đụng `XMLSignService`. Việc nó suy biến an toàn khi không ký được là đúng: quyết
  định "có gửi hay không" thuộc về luồng export, không thuộc về lớp ký.
- **Không** thêm cột hay trạng thái mới vào `xml3176_informations`.
