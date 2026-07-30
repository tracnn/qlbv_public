# Cô lập công tắc trong luồng XML 3176

Ngày: 2026-07-30

## Mục tiêu

Bật hay tắt một chặng trong luồng XML 3176 **không được ảnh hưởng chặng khác**, và tắt phải
có nghĩa là **không sinh việc**, chứ không phải sinh việc rồi bỏ.

Luồng: import → ký số → export → copy sang các cổng → gửi cổng BHXH.

## Hiện trạng đo được

### Bốn điểm rẽ nhánh

| Bước | Cờ | Nơi kiểm hiện tại |
| --- | --- | --- |
| Import → Export | `xml3176.export_xml3176_enabled` | `Xml3176Importer:279` — kiểm **trước** khi gọi |
| Export → Copy Trục dữ liệu Y Tế | `organization.truc_du_lieu_y_te.enabled` | bên trong `FileCopyService:58` |
| Export → Copy Điện Biên | **chưa có** | — |
| Export → Gửi cổng BHXH | `organization.BHYT.submit_xml_3176_enabled` | trong `SubmitXml3176Job::handle():68` |

### Ba vấn đề

**1. Tắt gửi BHXH vẫn sinh job.** `Xml3176Service::processExportXml():1810` dispatch **vô điều
kiện**; cổng kiểm nằm trong `handle()`. Mỗi hồ sơ export sinh một job vào hàng đợi
`JobSubmitXml3176`, worker nhận rồi thoát ngay kèm một dòng log. Tắt mà vẫn tốn việc.

**2. Dựng service trước cổng kiểm.** `SubmitXml3176Job:65` dựng
`new BHYTXmlSubmitService(new BHYTLoginService(...))` **trước** khi hỏi cờ `enabled`. Vô hại về
chức năng vì `BHYTLoginService` phân giải lười, nhưng sai thứ tự — làm việc trước khi hỏi có
được phép làm không. Lỗi này do đợt sửa cùng ngày gây ra.

**3. Đĩa Điện Biên không ai ghi vào.** `FileCopyService` chỉ có
`copyExportXml3176ToTrucDuLieuYTe()`. Lệnh `CongDuLieuYTeDienBienXmlScan` canh đĩa
`congDuLieuYTeDienBien` mà luồng 3176 không bao giờ ghi vào — chặng copy bị thiếu.

### Một điểm đã đúng, không đụng

`XMLSignService::signXml()` suy biến an toàn: không ký được thì trả
`['isSigned' => false, 'data' => $xmlContent, 'method' => null]` — nội dung gốc, không ném,
không chặn export. Thêm công tắc cho nó là thêm một chỗ có thể khai sai mà không giải quyết
vấn đề gì.

## Thiết kế

### 1. Nguyên tắc: kiểm cờ ở nơi rẽ nhánh

Cổng kiểm đặt tại nơi **quyết định có sinh việc hay không**, không phải nơi thực thi việc đó.

`processExportXml()` chỉ dispatch `SubmitXml3176Job` khi
`config('organization.BHYT.submit_xml_3176_enabled')` bật.

**Vẫn giữ cổng kiểm trong `handle()` làm lớp hai.** Hai lớp phục vụ hai tình huống khác nhau:
lớp ngoài chặn việc sinh job; lớp trong chặn job đã nằm sẵn trong hàng đợi từ trước khi cấu
hình bị tắt. Job có thể chờ hàng giờ trước khi chạy, nên đây không phải kiểm thừa.

Áp cùng cách cho `Qd130XmlService` và `SubmitQd130XmlJob` — mã hai đường đối xứng.

### 2. Dựng service sau cổng kiểm

Trong cả hai job, chuyển dòng dựng `BHYTXmlSubmitService` xuống **sau** khối kiểm `enabled`.

### 3. Bổ sung copy sang Điện Biên

Hai hàm copy sẽ giống hệt nhau trừ khoá cấu hình và tên đĩa mặc định. Gộp phần chung:

```php
/**
 * @param string $filePath      duong dan tren disk exportXml3176
 * @param string $khoaCauHinh   khoa trong config/organization.php
 * @param string $diskMacDinh   ten disk dung khi cau hinh khong khai
 * @return bool true neu copy thanh cong HOAC chuc nang chua bat
 */
private function copyExportXml3176ToDisk($filePath, $khoaCauHinh, $diskMacDinh)
```

Hai hàm mỏng gọi vào nó:

- `copyExportXml3176ToTrucDuLieuYTe()` → khoá `organization.truc_du_lieu_y_te`, đĩa
  `trucDuLieuYTe`
- `copyExportXml3176ToCongDuLieuYTeDienBien()` → khoá
  `organization.cong_du_lieu_y_te_dien_bien`, đĩa `congDuLieuYTeDienBien`

`processExportXml()` gọi cả hai. **Mỗi hàm tự kiểm cờ của mình**, nên tắt cái này không đụng
cái kia — đó chính là điều kiện cô lập mà spec này đặt ra.

Giữ nguyên hành vi hiện có: chưa bật thì trả `true` (không phải lỗi), và mọi lỗi copy đều
được `copy()` bắt và ghi log chứ không ném — một đĩa mạng hỏng không được phép làm chết
export.

## Kiểm thử

Trong `tests/Unit`. Cổng: `vendor/bin/phpunit --testsuite Unit`.

Giá trị nằm ở **canary chốt thứ tự** — quét mã nguồn đã bỏ comment bằng
`Tests\Support\LocComment`. Không viết hàm thuần chỉ để đọc boolean; test cho nó sẽ rỗng nghĩa.

`CoLapCongTacLuongTest`:

1. `Xml3176Service` hỏi `submit_xml_3176_enabled` **trước** vị trí `SubmitXml3176Job::dispatch`
   (so sánh vị trí chuỗi trong mã nguồn).
2. `Qd130XmlService` hỏi `submit_xml_enabled` **trước** `SubmitQd130XmlJob::dispatch`.
3. Trong `SubmitXml3176Job` và `SubmitQd130XmlJob`, chuỗi `submit_xml` (cổng kiểm) đứng
   **trước** `new BHYTXmlSubmitService`.
4. `FileCopyService` có đủ hai hàm `copyExportXml3176ToTrucDuLieuYTe` và
   `copyExportXml3176ToCongDuLieuYTeDienBien`.
5. `FileCopyService` đọc cả hai khoá cấu hình `organization.truc_du_lieu_y_te` và
   `organization.cong_du_lieu_y_te_dien_bien`.
6. `Xml3176Service::processExportXml` gọi cả hai hàm copy.

`FileCopyServiceCoLapTest` — kiểm cô lập bằng hành vi thật, dùng `Storage::fake()`, không
chạm đĩa thật và không chạm mạng:

1. Cả hai cờ tắt → cả hai hàm trả `true`, **không đĩa đích nào** có tệp.
2. Chỉ bật Trục dữ liệu Y Tế → đĩa Trục có tệp, đĩa Điện Biên **không** có.
3. Chỉ bật Điện Biên → ngược lại.
4. Bật cả hai → cả hai đĩa đều có tệp.

Đây là bằng chứng trực tiếp cho mục tiêu "bật/tắt cái này không ảnh hưởng cái kia".

## Nghiệm thu

- `vendor/bin/phpunit --testsuite Unit` xanh.
- Ma trận bật/tắt ở `FileCopyServiceCoLapTest` đúng cả bốn tổ hợp.

## Phạm vi không làm

- **Không** tối ưu tốc độ. "Tối ưu" ở spec này là cô lập cấu hình và không làm việc thừa.
  Thời gian chạy của import / ký số / export **chưa được đo**, nên không hứa gì về hiệu năng;
  muốn làm thì là một đợt riêng, đo trước rồi mới sửa.
- **Không** thêm công tắc cho ký số — `signXml()` đã suy biến an toàn.
- **Không** đụng logic nghiệp vụ của import, các bộ kiểm lỗi, hay nội dung XML sinh ra.
- **Không** bật bất kỳ cờ nào đang tắt.
- **Không** gộp bốn khối cấu hình về một chỗ — chúng thuộc bốn hệ thống khác nhau; gộp lại là
  đổi cấu trúc cấu hình của cả dự án, rủi ro hơn lợi.
