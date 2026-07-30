# Gửi XML lên cổng BHXH theo từng cơ sở

Ngày: 2026-07-30

## Mục tiêu

Đường gửi hồ sơ XML lên cổng BHXH đang dùng **mã tỉnh chốt cứng** và **tài khoản chốt cứng**
của một cơ sở duy nhất. Hệ thống phục vụ nhiều cơ sở KCB; hồ sơ của cơ sở nào phải gửi bằng
tài khoản và mã tỉnh của cơ sở đó.

Áp cho cả hai đường: XML 3176 và QĐ130/4750.

## Hiện trạng đo được

`xml3176_informations` có 210 hồ sơ: **166** của `01929`, **44** của `37470`. Cột `macskcb`
không rỗng dòng nào.

Đường truyền mã cơ sở **đã đúng sẵn**: `xml3176_informations.macskcb` →
`Xml3176Service::processExportXml()` → `SubmitXml3176Job::dispatch($ma_lk, $filePath, $macskcb)`
→ `submitXml(..., $this->macskcb)`. `BHYTXmlSubmitService::submitXml()` cũng đã có sẵn hai tham
số `$maTinh` và `$maCSKCB`.

Ba chỗ còn sai:

1. **`ma_tinh` chốt cứng.** `SubmitXml3176Job:76` và `SubmitQd130XmlJob` truyền
   `config('organization.BHYT.ma_tinh')` — đang là `'01'` — cho **mọi** cơ sở. Cơ sở `37470` ở
   Ninh Bình phải là `'37'`. Sai **44/210 = 21%** hồ sơ.

2. **Service đăng nhập không biết cơ sở.** Hai job nhận `BHYTXmlSubmitService` qua container,
   nên constructor của nó chạy `new BHYTLoginService()` **không tham số**. Bật
   `submit_xml_3176_enabled` lên là ném `Thiếu ma co so KCB` ngay lần gửi đầu.

3. **Tài khoản trong body là của khối cũ.** `submitXml():60-61` đọc `$this->config['username']`
   và `['password']` từ khối `BHYT` (`01013_BV`), không phải tài khoản của cơ sở.

Cờ hiện tại: `submit_xml_3176_enabled = false`, `submit_xml_enabled = true`. Bảng
`qd130_informations` **không tồn tại** nên đường QĐ130 chưa chạy ở bản triển khai này — nhưng
mã của nó đối xứng gần như từng dòng với đường 3176, nên sửa cùng lượt.

## Thiết kế

### 1. Mã tỉnh suy từ mã cơ sở

Hai job bỏ `config('organization.BHYT.ma_tinh')`, thay bằng
`CauHinhCoSo::maTinh($this->macskcb)` (đã có sẵn, có test).

Bớt một khoá cấu hình là bớt một chỗ có thể khai sai — và ở đây nó **đang** khai sai.

Khoá `ma_tinh` trong config **giữ nguyên**: `BHYTXmlSubmitService::getMaTinhFromConfig()` còn
dùng nó làm dự phòng, và `CongDuLieuYTeDienBienXmlSubmitService` cũng đọc khối `BHYT`.

### 2. Dựng service với đúng cơ sở

Hai job bỏ tham số inject khỏi chữ ký `handle()`, dựng tường minh:

```php
$xmlSubmitService = new BHYTXmlSubmitService(new BHYTLoginService($this->macskcb));
```

Container không biết hồ sơ này thuộc cơ sở nào, nên để nó dựng hộ là sai về nguyên tắc chứ
không chỉ sai về kết quả.

`Xml3176Service` / `Qd130XmlService` vẫn được inject bình thường — chúng không cần cơ sở.

### 3. Tài khoản trong body lấy theo cơ sở

`submitXml()` đổi hai dòng:

```php
$passwordHash = $this->loginService->password();
$username = $this->loginService->username();
```

Đây là điểm quan trọng nhất. Token và tài khoản trong body mà khác cơ sở thì cổng có thể vẫn
nhận, và hồ sơ bị ghi sai đơn vị gửi — **hỏng im lặng**, không có dấu hiệu gì cho tới lúc đối
soát. Lấy cả hai từ cùng một `loginService` khiến chúng không thể lệch nhau.

### 4. Dọn mã chết

`submitXmlToBHYT()` trong `Xml3176Service` và `Qd130XmlService` là **private và không nơi nào
gọi** — đường gửi thật đi qua `SubmitXml3176Job`. Xoá nó, cùng thuộc tính `$xmlSubmitService`
và dòng `new BHYTXmlSubmitService()` trong constructor của hai service.

Việc này gỡ đúng nút thắt đã buộc phải làm phân giải lười ở đợt trước: `Xml3176Service` không
còn dựng service đăng nhập chỉ để đọc XML.

**Phân giải lười trong `BHYTLoginService` vẫn giữ** — nó đúng về thiết kế, và còn nơi khác dựa
vào (`InsuranceController` dựng service trước khi biết có gọi cổng hay không).

## Kiểm thử

Trong `tests/Unit`. Cổng: `vendor/bin/phpunit --testsuite Unit`.

**Canary quét mã nguồn** (`GuiXmlTheoCoSoTest`, dùng `Tests\Support\LocComment`):

1. `SubmitXml3176Job` và `SubmitQd130XmlJob` **không còn** chuỗi `organization.BHYT.ma_tinh`.
2. Cả hai job có `CauHinhCoSo::maTinh($this->macskcb)`.
3. Cả hai job có `new BHYTLoginService($this->macskcb)`.
4. Cả hai job **không còn** nhận `BHYTXmlSubmitService` trong chữ ký `handle()`.
5. `BHYTXmlSubmitService::submitXml()` lấy tài khoản từ `$this->loginService`, **không còn**
   đọc `$this->config['username']`.
6. `submitXmlToBHYT` **không còn** trong cả hai service.
7. `Xml3176Service` và `Qd130XmlService` **không còn** `new BHYTXmlSubmitService()`.

**Nghiệm thu bằng số** (`GuiXmlTheoCoSoTest`, không chạm mạng):

1. `CauHinhCoSo::maTinh('37470')` trả `'37'`, `maTinh('01929')` trả `'01'` — chốt rằng cơ sở
   Ninh Bình không còn bị gán mã tỉnh `'01'`.
2. Với cấu hình thật, `BHYTLoginService('37470')->username()` **khác** `01013_BV` của khối cũ.

**Không test nào gọi cổng BHXH.** `submit_xml_3176_enabled` giữ `false`.

## Nghiệm thu

- `vendor/bin/phpunit --testsuite Unit` xanh.
- `Xml3176Service` dựng được mà không cần cấu hình cơ sở (chứng minh đã gỡ nút thắt).

**Giới hạn phải nói rõ:** chứng minh được tham số dựng đúng, **không** chứng minh được cổng
BHXH chấp nhận hồ sơ. Việc đó cần bật cờ và gửi thật — nằm ngoài phạm vi, người vận hành tự
quyết.

## Phạm vi không làm

- **Không** bật `submit_xml_3176_enabled`.
- **Không** đụng `CongDuLieuYTeDienBienXmlSubmitService` (cổng khác, tài khoản khác).
- **Không** xoá khoá `ma_tinh`, `username`, `password` khỏi khối `BHYT` — còn nơi đọc.
- **Không** đụng `correct_facility_code` và ba bộ kiểm XML dùng nó đúng nghĩa "nơi ĐKBĐ đúng
  tuyến". Hai hàm dự phòng `getMaTinhFromConfig()`/`getMaCSKCBFromConfig()` giữ nguyên: nơi gọi
  luôn truyền đủ hai tham số nên chúng không với tới, xoá là thay đổi thừa.
- **Không** sửa `BHYTController:287` (truyền 12 tham số cho constructor nhận 2, hỏng sẵn từ
  trước) — việc riêng, cần biết nó vốn định làm gì.
