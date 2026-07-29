# A_BHYT_CODE_MISSING: lấy mã BHYT theo đúng loại dòng

Ngày: 2026-07-29

## Mục tiêu

Quy tắc `A_BHYT_CODE_MISSING` đang lấy mã BHYT từ một cột chỉ dành cho dịch vụ kỹ thuật,
nên báo sai toàn bộ với dòng thuốc. Sửa để lấy đúng nguồn theo loại dòng.

## Vấn đề đo được

Quy tắc đọc `his_service.hein_service_bhyt_code`
(`app/Services/OrderCheck/HisOrderSource.php:81`). Cột đó chỉ được duy trì cho dịch vụ kỹ
thuật. Với thuốc, mã BHYT nằm ở `his_medicine_type.active_ingr_bhyt_code`.

Đo trên 7 ngày gần nhất, chỉ tính **dòng thuộc đối tượng BHYT**
(`his_sere_serv.patient_type_id = 1`):

| Loại dòng | Tổng dòng BHYT | Thiếu `hein_service_bhyt_code` |
| --- | --- | --- |
| Thuốc | 359.507 | **48.234** |
| Vật tư | 175.775 | 0 |
| DVKT | 352.206 | 0 |

Toàn bộ 48.234 cảnh báo tiềm năng đều là thuốc, và **100% số đó đã khai**
`active_ingr_bhyt_code`. Ví dụ cụ thể: `Lidogel 2% 10g` (`medicine_id = 115143`) có
`hein_service_bhyt_code = NULL` nhưng `active_ingr_bhyt_code = '40.12'`.

Nếu bật quy tắc, hệ thống sinh khoảng **6.900 cảnh báo sai mỗi ngày**, không cái nào đúng.

Hiện `A_BHYT_CODE_MISSING` đang `is_active = 0` và bảng vi phạm có 0 dòng của nó trên môi
trường khảo sát.

### Đính chính một phép đo trước đó

Số "vật tư 69% thiếu mã" đo trên **danh mục** `his_service` (23.653 dịch vụ loại 7, 16.249
thiếu). Ở mức **dòng thực tế được chỉ định cho bệnh nhân BHYT** thì con số là **0/175.775**.
Những dịch vụ thiếu mã trong danh mục đơn giản không bao giờ được dùng cho đối tượng BHYT.
Mọi quyết định trong spec này dựa trên số đo ở mức dòng.

## Thiết kế

### 1. Lấy mã theo loại dòng

Sửa `HisOrderSource::fetchServicesByReqIds()` — thêm hai `leftJoin` để với tới danh mục
thuốc, và chọn thêm cột mã hoạt chất:

```php
->leftJoin('his_medicine as md', 'md.id', '=', 'ss.medicine_id')
->leftJoin('his_medicine_type as mdt', 'mdt.id', '=', 'md.medicine_type_id')
```

Cột chọn thêm: `mdt.active_ingr_bhyt_code`.

Gán `$s->bhytCode` bằng mã đầu tiên khác rỗng theo thứ tự: **mã hoạt chất trước, mã dịch
vụ sau**. Dòng thuốc luôn có mã hoạt chất nên lấy nó; dòng vật tư và DVKT không join ra
được nên rơi về mã dịch vụ như cũ.

`BhytCodeMissingRule` **không phải sửa** — nó chỉ đọc `$s->bhytCode`.

### 2. Hàm thuần để kiểm thử

Tách phần chọn mã thành `App\Services\OrderCheck\Support\MaBhytDong`:

```php
/**
 * @return string ma dau tien khac rong sau khi trim; chuoi rong neu khong co cai nao
 */
public static function cua($maHoatChat, $maDichVu)
```

Quy tắc: `trim()` từng giá trị, trả về giá trị đầu tiên khác chuỗi rỗng, theo thứ tự
`$maHoatChat` rồi `$maDichVu`; không có cái nào thì trả chuỗi rỗng.

Trả **chuỗi rỗng** chứ không phải `null` để `BhytCodeMissingRule` (đang kiểm
`trim((string) $s->bhytCode) !== ''`) hoạt động không đổi.

### 3. Không làm gì với vật tư

Dữ liệu cho thấy vật tư đang đúng: 0/175.775 dòng thiếu mã. Thêm nhánh xử lý riêng cho vật
tư là viết code chết. Nếu sau này vật tư phát sinh dòng thiếu mã, quy tắc sẽ báo — đúng
như mong muốn của quy tắc.

### 4. Không loại trừ suất ăn

Ở mức dòng BHYT, suất ăn không sinh cảnh báo nào (nằm trong con số DVKT = 0). Loại trừ nó
là giải quyết vấn đề không tồn tại.

## Kiểm thử

Trong `tests/Unit`. Cổng: `vendor/bin/phpunit --testsuite Unit`.

**Hàm thuần** (`MaBhytDongTest`):

1. Có mã hoạt chất và có mã dịch vụ → trả mã hoạt chất.
2. Mã hoạt chất rỗng/`null`, có mã dịch vụ → trả mã dịch vụ.
3. Cả hai rỗng/`null` → trả chuỗi rỗng.
4. Mã dính khoảng trắng hai đầu → trả bản đã cắt.
5. Mã hoạt chất chỉ gồm khoảng trắng → coi như rỗng, rơi về mã dịch vụ.

**Truy vấn** (`HisOrderSourceMaBhytTest`):

1. Đọc mã nguồn `HisOrderSource::fetchServicesByReqIds`, khẳng định có join tới
   `his_medicine_type` và có chọn cột `active_ingr_bhyt_code`. Đây là bài kiểm chống việc
   ai đó sau này gỡ join mà quên rằng quy tắc phụ thuộc vào nó.
2. Khẳng định `MaBhytDong` được dùng trong `HisOrderSource` — chống việc quay lại gán
   thẳng `hein_service_bhyt_code`.

Dùng trait `Tests\Support\LocComment` để loại chú thích trước khi quét mã nguồn, theo đúng
lệ đã có trong dự án — nếu không, một chuỗi nằm trong comment sẽ làm test xanh giả.

## Nghiệm thu bằng số

Sau khi sửa, chạy lại đúng phép đo ở phần "Vấn đề đo được" nhưng lấy mã theo logic mới.
Kỳ vọng: số dòng BHYT không có mã BHYT rơi từ **48.234 xuống 0**.

Đây là nghiệm thu bắt buộc, không phải tuỳ chọn — nó là bằng chứng duy nhất cho thấy bản
sửa giải quyết đúng vấn đề đã đo.

## Phạm vi không làm

- Không sửa `BhytCodeMissingRule` — nó không có lỗi.
- Không thêm nhánh xử lý riêng cho vật tư.
- Không loại trừ suất ăn hay bất kỳ loại dịch vụ nào khỏi phạm vi quy tắc.
- Không bật `A_BHYT_CODE_MISSING`. Việc bật là quyết định nghiệp vụ, làm sau khi đã nghiệm
  thu số liệu.
- Không đụng các quy tắc khác cùng họ BHYT (`BhytCatalogRule`).
