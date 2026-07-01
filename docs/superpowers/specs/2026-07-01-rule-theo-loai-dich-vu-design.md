# Thiết kế: Tổ chức luật cấp phiếu theo loại dịch vụ (SERVICE_REQ_TYPE)

- **Ngày:** 2026-07-01
- **Module:** Kiểm tra sai sót y lệnh (order-check)
- **Mục tiêu:** Tách phần chạy luật cấp phiếu (`ServiceReqScanner`) thành các file theo **từng loại dịch vụ** (SERVICE_REQ_TYPE) để lập trình viên biết chính xác nơi thêm luật mới.

## 1. Vấn đề

Hiện `ServiceReqScanner` gộp `StructuralRuleRegistry` (Họ B) + `ClinicalServiceReqRuleRegistry` (Họ A) và chạy **tất cả** handler cho **mọi** phiếu, không phân biệt loại. Khi cần thêm luật riêng cho một loại (vd CĐHA, Xét nghiệm), không có "chỗ" rõ ràng để đặt.

## 2. Quyết định (đã chốt với người dùng)

- Trục phân loại: **SERVICE_REQ_TYPE** (Khám/XN/CĐHA/Thuốc...).
- Tạo sẵn **đủ 18 file** loại (phần lớn rỗng + comment hướng dẫn) + **1 file luật chung**.
- Handler áp cho một loại → mở file loại đó; handler áp mọi loại → file chung.

Danh mục 18 loại (HIS_SERVICE_REQ_TYPE): 1 Khám, 2 Xét nghiệm, 3 Chẩn đoán hình ảnh, 4 Thủ thuật, 5 Thăm dò chức năng, 6 Đơn phòng khám, 7 Giường, 8 Nội soi, 9 Siêu âm, 10 Phẫu thuật, 11 Khác, 12 Phục hồi chức năng, 13 Giải phẫu bệnh, 14 Đơn tủ trực, 15 Đơn điều trị, 16 Đơn máu, 17 Suất ăn, 18 Ngoài khám chữa bệnh.

## 3. Cấu trúc

```
app/Services/OrderCheck/
├── Contracts/TypeRules.php                 # interface: typeId(), handlers()
└── RuleHandlers/ServiceReq/
    ├── CommonRules.php                      # luật áp MỌI loại phiếu
    ├── ServiceReqRuleRegistry.php           # common() + forType($typeId)
    └── Types/
        ├── KhamRules.php            (1)
        ├── XetNghiemRules.php       (2)
        ├── ChanDoanHinhAnhRules.php (3)
        ├── ThuThuatRules.php        (4)
        ├── ThamDoChucNangRules.php  (5)
        ├── DonPhongKhamRules.php    (6)
        ├── GiuongRules.php          (7)
        ├── NoiSoiRules.php          (8)
        ├── SieuAmRules.php          (9)
        ├── PhauThuatRules.php       (10)
        ├── KhacRules.php            (11)
        ├── PhucHoiChucNangRules.php (12)
        ├── GiaiPhauBenhRules.php    (13)
        ├── DonTuTrucRules.php       (14)
        ├── DonDieuTriRules.php      (15)
        ├── DonMauRules.php          (16)
        ├── SuatAnRules.php          (17)
        └── NgoaiKcbRules.php        (18)
```

## 4. Thành phần & interface

- **`Contracts/TypeRules`**
  ```php
  interface TypeRules {
      public function typeId();      // int, id loại phiếu HIS
      public function handlers();    // RuleHandler[]
  }
  ```
- **`CommonRules::handlers()` (static)** → 5 handler hiện có, áp mọi loại:
  `DischargeBeforeAdmissionRule`, `OrderTimeOutOfStayRule`, `ExecuteBeforeOrderRule`, `DoctorPracticeCertRule`, `MissingDiagnosisRule`.
  *Các class handler vẫn nằm ở `RuleHandlers/Structural/` và `RuleHandlers/Clinical/`; CommonRules chỉ import + trả về instance.*
- **Mỗi `Types/*Rules.php`** implement `TypeRules`, `handlers()` **trả `[]`** kèm comment:
  ```php
  public function handlers()
  {
      // Thêm luật CHỈ áp cho phiếu "<Tên loại>" (id=<n>) vào đây, vd: new <Ten>Rule()
      return [];
  }
  ```
- **`ServiceReqRuleRegistry` (static)**
  - `common()` → `CommonRules::handlers()`.
  - `forType($typeId)` → tra map `id => TypeRules` (dựng 1 lần từ danh sách 18 instance), trả `handlers()` hoặc `[]`.
  - `typeRules()` → mảng 18 instance (khai báo tường minh).

## 5. Thay đổi `ServiceReqScanner`

Thay:
```php
$handlers = array_merge(StructuralRuleRegistry::handlers(), ClinicalServiceReqRuleRegistry::handlers());
// ... foreach row: foreach handlers ...
```
bằng:
```php
$common = ServiceReqRuleRegistry::common();
// ... foreach row:
$handlers = array_merge($common, ServiceReqRuleRegistry::forType($ctx->serviceReqTypeId));
foreach ($handlers as $handler) { /* nếu is_active -> check -> persist (như cũ) */ }
```
(`forType` cache theo typeId nên rẻ; hành vi hiện tại giữ nguyên vì mọi luật đang ở Common.)

## 6. Xóa / giữ

- **Xóa:** `RuleHandlers/StructuralRuleRegistry.php`, `RuleHandlers/ClinicalServiceReqRuleRegistry.php` (thay bằng CommonRules + ServiceReqRuleRegistry).
- **Giữ nguyên:** các class handler (`Structural/*`, `Clinical/*`); các scanner khác (`InteractionLogScanner`, `MedicineScanner`, `ServiceRestrictionScanner`) — theo nguồn HIS, không theo loại phiếu.

## 7. Kiểm thử

- Test handler thuần hiện có: **không đổi** (class không đổi).
- Thêm `tests/Unit/OrderCheck/ServiceReqRuleRegistryTest.php`:
  - `common()` trả đúng 5 handler (kiểm số lượng + có `A_MISSING_DIAGNOSIS`, `B_*`).
  - `forType(2)` (Xét nghiệm) trả `[]` (chưa có luật riêng).
  - `forType(999)` (không tồn tại) trả `[]`.
- Verify: `php artisan kiemtraylenh:scan --once` chạy như cũ, số vi phạm không đổi bất thường.

## 8. Tài liệu

Cập nhật `docs/order-check/HUONG-DAN-THEM-QUY-TAC.md` §3:
- Luật cho **một loại** → mở `RuleHandlers/ServiceReq/Types/<Loại>Rules.php`, thêm handler vào `handlers()`.
- Luật cho **mọi loại** → thêm vào `CommonRules::handlers()`.
- (Handler class type-specific đặt cùng `Types/` hoặc thư mục con tuỳ ý; đăng ký trong file loại tương ứng.)

## 9. Ngoài phạm vi (YAGNI)

- Không auto-discover file loại (khai báo tường minh 18 instance trong registry).
- Không di chuyển các class handler hiện có sang cấu trúc mới (giữ nguyên vị trí).
- Không đổi cơ chế `order_check_rules` / `is_active` / dedup.
