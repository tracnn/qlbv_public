# Order-check: loại trừ kiểm CCHN người thực hiện theo loại phiếu

Ngày: 2026-07-28
Trạng thái: đã chốt thiết kế

## 1. Vấn đề

Ba loại phiếu đơn thuốc — Đơn phòng khám (6), Đơn tủ trực (14), Đơn điều trị (15) — đang
bị luật `B_DOCTOR_NO_PRACTICE_CERT` bắt lỗi theo **người thực hiện**. Người thực hiện của
các phiếu này là dược sĩ hoặc điều dưỡng cấp phát, không phải người mà nghiệp vụ đòi chứng
chỉ hành nghề theo nghĩa của luật này.

Ba loại đó không có luật riêng nào; chúng nhận `B_DOCTOR_NO_PRACTICE_CERT` từ
`CommonRules::handlers()` áp cho mọi loại phiếu.

## 2. Khảo sát

Rà 16 luật hiện có, chỉ hai luật xét con người:

| Luật | Người chỉ định | Người thực hiện | Trạng thái |
|---|---|---|---|
| `B_DOCTOR_NO_PRACTICE_CERT` | — | có (CCHN rỗng) | BẬT |
| `A_STAFF_CERT_NOT_IN_CATALOG` | có | có (CCHN ngoài danh mục) | TẮT |

14 luật còn lại không xét người nào.

### 2.1 Quy mô, 7 ngày

Điều kiện của `B_DOCTOR_NO_PRACTICE_CERT` (có người thực hiện nhưng người đó không có
`DIPLOMA` trong `his_employee`), tách theo loại phiếu:

```
Tổng theo NGƯỜI THỰC HIỆN : 4.247
  id=6  Đơn phòng khám : 3.846
  id=15 Đơn điều trị   :   400
  id=14 Đơn tủ trực    :     0
  id=2  Xét nghiệm     :     1
  mọi loại khác        :     0

Nếu bắt theo NGƯỜI CHỈ ĐỊNH : 923
  id=1  Khám           :   922
  id=6/14/15           :     0
```

Ba điều rút ra:

1. **Đơn tủ trực không sinh vi phạm nào** — 14.624 phiếu, 0 lỗi. Đưa vào danh sách loại trừ
   là phòng xa, không đổi con số hôm nay.
2. **3.846 vi phạm của Đơn phòng khám do đúng một người**: `tranghth-kd` (Hoàng Thị Hoài
   Trang), 3.846 phiếu, cột `DIPLOMA` bỏ trống trong HIS. Con số khớp tuyệt đối. Đây là một
   ô dữ liệu chưa khai, không phải khiếm khuyết của luật.
3. **Loại trừ ba loại này thì luật còn đúng 1 vi phạm mỗi tuần.** `B_DOCTOR_NO_PRACTICE_CERT`
   hiện chỉ sống nhờ ba loại đó.

Điểm 2 và 3 là lý do tài liệu này ghi rõ ở mục 6 rằng loại trừ **che** vấn đề chứ không sửa
gốc.

## 3. Phạm vi

### Có làm

- Khoá cấu hình `practice_cert_exclude_type_ids`, mặc định `6,14,15`.
- `DoctorPracticeCertRule` bỏ qua loại phiếu trong danh sách.

### Không làm

- **Áp cho `A_STAFF_CERT_NOT_IN_CATALOG`.** Bản đầu của tài liệu này có áp, người dùng chốt
  bỏ ngày 2026-07-28 sau khi đã triển khai. Luật đó vẫn xét cả hai vai trò ở mọi loại phiếu.
  Nó đang TẮT và danh mục `medical_staffs` đang rỗng nên chưa sinh vi phạm nào; nếu sau này
  bật lên mà thấy phiền ở ba loại đơn thuốc thì mở lại quyết định này.
- Đổi luật sang bắt theo người chỉ định — người dùng đã chốt bỏ phương án này.
- Cấu hình bắt theo người nào tuỳ từng loại phiếu — chưa có nhu cầu.
- Sửa việc vi phạm gắn sai tên người (mục 6).
- Khai bổ sung CCHN trong HIS — việc vận hành, không phải mã nguồn.

## 4. Thiết kế

```php
// config/order_check.php
'practice_cert_exclude_type_ids' => env('ORDER_CHECK_PRACTICE_CERT_EXCLUDE_TYPES', '6,14,15'),
```

Chỉ `DoctorPracticeCertRule` đọc khoá này, theo đúng khuôn
`missing_diagnosis_exclude_type_ids` đã có trong `MissingDiagnosisRule`: hàm dựng nhận
`array $excludeTypeIds = null`, null thì đọc config. Test truyền thẳng mảng, không phụ
thuộc config.

Chuỗi rỗng nghĩa là **không loại trừ loại nào** — giữ đường lui để đơn vị bật lại toàn bộ
mà không phải sửa mã.

### 4.1 `B_DOCTOR_NO_PRACTICE_CERT`

```
loại phiếu ∈ danh sách loại trừ  ->  bỏ qua cả phiếu
```

### 4.2 `A_STAFF_CERT_NOT_IN_CATALOG` — không đổi

Luật này **không** đọc khoá cấu hình trên. Nó vẫn xét cả hai vai trò ở mọi loại phiếu.

## 5. Kiểm thử

Cổng: `vendor/bin/phpunit --testsuite Unit`.

### `B_DOCTOR_NO_PRACTICE_CERT`

| Ca | Kỳ vọng |
|---|---|
| Loại 6/14/15, người thực hiện thiếu CCHN | không vi phạm |
| Loại 2 (không loại trừ), người thực hiện thiếu CCHN | 1 vi phạm |
| Danh sách loại trừ rỗng, loại 6 thiếu CCHN | 1 vi phạm |
| `serviceReqTypeId` null | vẫn xét như cũ |

### `A_STAFF_CERT_NOT_IN_CATALOG`

| Ca | Kỳ vọng |
|---|---|
| Loại 6/14/15/2/null, cả hai vai trò sai | 2 vi phạm ở **mọi** loại — khoá cấu hình không áp cho luật này |

## 6. Điều tài liệu này cố ý không giải quyết

**Loại trừ che vấn đề, không sửa gốc.** 3.846/4.246 vi phạm là do `tranghth-kd` chưa được
khai CCHN trong HIS. Sau thay đổi này con số biến mất khỏi màn hình, nhưng ô dữ liệu vẫn
trống. Cần khai bổ sung song song.

**Vi phạm gắn sai tên người.** `OrderCheckEngine` ghi `doctor_loginname` là người **chỉ
định** cho mọi dòng vi phạm, kể cả khi luật bắt lỗi của người thực hiện. Trên dashboard,
lỗi thiếu CCHN của điều dưỡng hiển thị dưới tên bác sĩ ra y lệnh. Sửa việc này động vào cột
dùng chung của mọi luật nên tách ra làm riêng.
