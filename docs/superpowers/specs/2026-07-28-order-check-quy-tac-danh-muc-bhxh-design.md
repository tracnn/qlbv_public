# Order-check — lọc đối tượng BHYT và bổ sung quy tắc theo danh mục BHXH

Ngày: 2026-07-28
Phạm vi: `App\Services\OrderCheck` (nguồn quét, ngữ cảnh, 4 rule handler mới), `config/order_check.php`, seed `order_check_rules`

## Bối cảnh

Module order-check quét phiếu chỉ định từ HIS gần thời gian thực và chạy 9 quy tắc: 4 quy
tắc cấu trúc (họ `B`) và 5 quy tắc lâm sàng (họ `A`).

Bộ kiểm lỗi XML3176 đối chiếu hồ sơ với danh mục BHXH — nhưng **chỉ sau khi đợt điều trị
kết thúc và đã xuất XML**. Chủ đầu tư muốn đưa phần đối chiếu danh mục đó lên **thời điểm
y lệnh**, để bắt lỗi khi bệnh nhân còn đang điều trị và còn sửa được.

## Phát hiện: tiền đề ban đầu không đúng với mã hiện tại

Yêu cầu nêu "order-check bây giờ chỉ kiểm tra các hồ sơ liên quan đến đối tượng BHYT".
Quét toàn bộ `app/Services/OrderCheck/`: **không có bất kỳ chỗ nào lọc theo đối tượng
BHYT**. Bộ lọc duy nhất là `exclude_treatment_type_ids` (loại điều trị), lấy từ `.env` và
**mặc định rỗng** — tức mặc định quét mọi hồ sơ, kể cả Viện phí, KSK, Yêu cầu, Vacxin.

Vì vậy việc lọc phải làm **trước**, không phải giả định là đã có.

## Chọn cột nhận biết đối tượng — đo trên dữ liệu thật

Đo trên **148.915 dòng dịch vụ** của 7 ngày gần nhất:

| Cột ứng cử | Có giá trị | BHYT (=1) | Kết luận |
|---|---|---|---|
| `his_sere_serv.patient_type_id` | 148.915 (100%) | **94.347** | **Dùng cột này** |
| `his_sere_serv.primary_patient_type_id` | 3.280 (2,2%) | **0** | Loại |
| `his_service_req.tdl_patient_type_id` | 148.915 (100%) | 137.312 | Loại |
| `his_treatment.tdl_patient_type_id` | — | 138.084 | Loại |

`primary_patient_type_id` chỉ được ghi khi có đổi đối tượng (2,2% số dòng) và **không một
dòng nào** mang giá trị BHYT, nên không dùng làm tín hiệu được. `JSON_PATIENT_TYPE_ALTER`
có ở 94.391 dòng — xấp xỉ đúng số dòng BHYT, tức nó ghi lại việc tách đối tượng; không
cần cho bộ lọc.

### Vì sao lọc ở mức hồ sơ là sai

Lọc theo `his_treatment.tdl_patient_type_id` cho kết quả **lệch 44.927 dòng (30,17%)** so
với lọc ở mức dòng.

Trường hợp lớn nhất: **43.264 dòng đối tượng Viện phí (02) nằm trong hồ sơ BHYT (01)** —
bệnh nhân có thẻ nhưng riêng dịch vụ đó tự chi trả. Lọc ở mức hồ sơ thì toàn bộ số đó bị
đem đối chiếu danh mục BHXH và bắt lỗi oan: khoảng **6.200 vi phạm giả mỗi ngày**.

Nhận biết theo `HIS_PATIENT_TYPE`: `id = 1`, mã `01` = BHYT. Bảy đối tượng còn lại (Viện
phí, KSK, Hợp đồng, Yêu cầu, Vacxin, Covid-19, Hợp đồng CLS) đều ngoài BHYT.

## Thiết kế

### A. Lọc hai tầng

**Tầng thô — mức phiếu.** Bỏ qua phiếu **không có dòng BHYT nào**. Rẻ, cắt phần lớn phiếu
tự nguyện thuần tuý, và **không đổi hành vi 9 quy tắc hiện có** với phiếu còn lại.

**Tầng tinh — mức dòng.** `OrderService` mang thêm `patientTypeId`. Các quy tắc danh mục
chỉ xét dòng có `patient_type_id` thuộc danh sách BHYT; dòng Viện phí trong cùng phiếu
được bỏ qua.

Ranh giới này có chủ đích: quy tắc mức phiếu (thiếu ICD, chứng chỉ hành nghề, giờ y lệnh)
giữ nguyên hành vi; chỉ quy tắc danh mục mới nhìn tới đối tượng của từng dòng.

Cấu hình mới:

```php
// config/order_check.php
'bhyt_patient_type_ids' => env('ORDER_CHECK_BHYT_PATIENT_TYPES', '1'),
```

Để trống thì **tắt cả tầng thô lẫn tầng tinh** — hành vi quay về đúng như hiện nay. Đây là
đường lùi nếu bộ lọc gây bất ngờ trên sản xuất.

### B. Bốn quy tắc danh mục

Cầu nối HIS ↔ BHXH là `his_service.hein_service_bhyt_code`.

| Mã | Nội dung | Đối chiếu | Mức độ |
|---|---|---|---|
| `A_BHYT_CODE_MISSING` | Dòng BHYT nhưng dịch vụ **không khai mã BHXH** trong HIS | không cần danh mục | warning |
| `A_BHYT_SERVICE_NOT_IN_CATALOG` | Có mã nhưng **không có trong** `service_catalogs` | `ma_dich_vu` | warning |
| `A_BHYT_DRUG_NOT_IN_CATALOG` | Thuốc không có trong `medicine_catalogs` | `ma_thuoc` | warning |
| `A_BHYT_SUPPLY_NOT_IN_CATALOG` | VTYT không có trong `medical_supply_catalogs` | `ma_vat_tu` | warning |

Quy tắc đầu **không phụ thuộc danh mục** nên chạy được ngay và không thể sai vì danh mục
chưa nhập.

Ba quy tắc sau **tự bỏ qua khi bảng danh mục rỗng**, ghi một dòng log giải thích. Không có
cơ chế này thì đơn vị chưa nhập danh mục sẽ thấy **mọi dịch vụ** thành vi phạm — hỏng theo
kiểu tệ nhất: sai mà trông như đúng.

Cả bốn tra cứu theo lô một lần cho mỗi phiếu (`whereIn`), không tra từng dòng — đúng bài
học từ đợt XML3176 nơi checker tra danh mục 18 chỗ theo từng dòng.

### C. Seed ở trạng thái TẮT, kèm lệnh chạy thử

Bốn quy tắc seed với `is_active = false`.

Lệnh mới `kiemtraylenh:thu` chạy trên N ngày gần nhất, **đếm vi phạm mà không ghi gì**, in
bảng theo mã quy tắc và theo khoa.

Lý do: xem mục rủi ro bên dưới. Chủ đầu tư xem con số rồi mới bật từng quy tắc trên màn
Quản lý quy tắc sẵn có.

## Rủi ro lớn nhất

**Không đo được tỉ lệ khớp thật.** Ba bảng danh mục trên DB dev đều **0 dòng**, nên không
chạy thử được tại chỗ.

Con số duy nhất có: **21.778 dịch vụ HIS đang hoạt động, chỉ 10.552 khai mã BHXH (48%)**.
Nếu phần lớn 11.226 dịch vụ còn lại vẫn được chỉ định cho dòng BHYT thì
`A_BHYT_CODE_MISSING` sẽ đổ ra **hàng nghìn vi phạm ngày đầu**.

Có thể con số đó là **đúng** — dịch vụ thiếu mã BHXH mà vẫn tính cho BHYT là lỗi thật. Nhưng
không thể biết trước, và đổ hàng nghìn vi phạm vào màn hình là cách chắc chắn để người dùng
tắt tính năng. Vì vậy mới có mục C.

Cột `IS_USE_SERVICE_HEIN` **không dùng được**: cả 21.778 dịch vụ đều có giá trị 0.

## Không thuộc phạm vi

1. **Trần giá và tỉ lệ thanh toán** — `hein_limit_price`, `hein_limit_ratio`,
   `do_not_use_bhyt`, `bhyt_whitelist_codes` mở ra một nhóm quy tắc riêng. Để đợt sau, sau
   khi nhóm danh mục đã chạy ổn và đã biết mức nhiễu.
2. **Đối chiếu ICD** với `icd10_categories` / `icd_yhct_categories`.
3. **Sửa 9 quy tắc hiện có** — chúng chỉ hưởng tầng lọc thô, nội dung không đổi.
4. **Nhập danh mục BHXH** — đã có sẵn chức năng nhập, không thuộc đợt này.

## Kiểm chứng

**Tự động** — phần lớn logic mới là hàm thuần nên phủ được:

- Lọc dòng theo `patientTypeId`: dòng BHYT được xét, dòng Viện phí bị bỏ; danh sách cấu
  hình rỗng thì **không lọc gì** (đường lùi).
- Phiếu không có dòng BHYT nào bị bỏ ở tầng thô; phiếu có ít nhất một dòng thì đi tiếp.
- Mỗi quy tắc danh mục trả về đúng một vi phạm cho mỗi dòng không khớp, không trả gì cho
  dòng khớp.
- **Bảng danh mục rỗng → quy tắc trả về rỗng**, không phải trả vi phạm cho mọi dòng. Đây là
  test quan trọng nhất của cả đợt.
- Tra cứu theo lô: một lần `whereIn` cho mỗi phiếu, không phải mỗi dòng.
- Bốn mã quy tắc mới có trong seed và đều `is_active = false`.

Cổng: `vendor/bin/phpunit --testsuite Unit`. Mốc hiện tại **342 test xanh**.

**Thủ công** — bắt buộc, vì DB dev không có danh mục lẫn dữ liệu HIS cục bộ:

| # | Việc | Mong đợi |
|---|---|---|
| 1 | Chạy `kiemtraylenh:thu --ngay=7` trước khi bật gì | Ra bảng đếm; **không** ghi dòng nào vào `order_check_violations` |
| 2 | Đọc con số `A_BHYT_CODE_MISSING` | Nếu quá lớn, xem vài mẫu trước khi bật — có thể là lỗi dữ liệu thật |
| 3 | Bật lọc BHYT, chạy quét bình thường | Vi phạm của 9 quy tắc cũ **giảm** (bỏ phần ngoài BHYT), không tăng |
| 4 | Đối chiếu vài vi phạm mới với hồ sơ thật | Dòng bị báo đúng là dòng BHYT, không phải dòng Viện phí |
| 5 | Xoá rỗng một bảng danh mục rồi chạy | Quy tắc tương ứng **im lặng**, có dòng log giải thích |
| 6 | Đặt `ORDER_CHECK_BHYT_PATIENT_TYPES=` (rỗng) | Hành vi quay về đúng như trước đợt này |

**Mục 4 quan trọng nhất**: nó chứng minh bộ lọc chạy ở mức dòng chứ không phải mức hồ sơ —
đúng sai lệch 30% đã đo được ở trên.
