# Miễn kiểm tra CCHN theo tài khoản thực hiện

Ngày: 2026-07-30

## Mục tiêu

Quy tắc `B_DOCTOR_NO_PRACTICE_CERT` đang báo vi phạm cho các tài khoản tích hợp máy móc —
những tài khoản không phải người nên không thể có chứng chỉ hành nghề. Thêm danh sách tài
khoản được miễn kiểm tra.

## Số liệu đo được

Đo lúc 30/07/2026, nhóm theo người thực hiện — **5.422** vi phạm
`B_DOCTOR_NO_PRACTICE_CERT`:

| Tài khoản | Vi phạm | Bản chất |
| --- | --- | --- |
| `mitalab` | 4.310 | Tích hợp máy xét nghiệm |
| `vietrad` | 1.066 | Tích hợp chẩn đoán hình ảnh |
| `sys` | 4 | Tài khoản hệ thống |
| `ntdh3` | 29 | **Người thật** — Nguyễn Thị Diệu Hằng |
| `vttq2` | 11 | **Người thật** — Võ Thị Thúy Quỳnh |
| 2 tài khoản khác | 2 | **Người thật** |

Ba tài khoản máy chiếm **5.380/5.422 = 99,2%**. Sau khi miễn, còn lại **42 vi phạm và tất
cả đều là người thật** — tức là danh sách dùng được ngay.

**Lưu ý khi nghiệm thu:** bộ quét chạy mỗi 60 giây nên các con số này **tăng dần**. Đừng
chốt cứng con số tuyệt đối; điều phải đúng là **không còn dòng nào** thuộc ba tài khoản
được miễn.

Tra `his_employee`: `mitalab`, `vietrad`, `sys` đều có `tdl_username` **trùng** `loginname`
và `diploma` là `NULL`. Còn `ntdh3`/`vttq2` có tên người thật — chúng thiếu CCHN trong HIS,
đó là phát hiện **đúng** của quy tắc, không được miễn.

## Phương án đã cân nhắc và loại bỏ

Tự nhận diện tài khoản máy bằng quy tắc `tdl_username = loginname` **không dùng được**. Đo
trên HIS: 6.931 nhân viên, **32** tài khoản thoả điều kiện đó — trong đó có `noitru`
(diploma = `CNTT`), `vss`, `tiencb`, `demo1`, `ddtest`, `admin`, `fpt`… lẫn lộn tài khoản
thử nghiệm, tài khoản phòng ban, và tài khoản **có** diploma.

Một quy tắc tự động sẽ **im lặng bỏ qua** những thứ không nên bỏ, và người bảo trì sau
không có cách nào biết ai đang được miễn. Danh sách tường minh thì đọc là biết, sửa là
thấy.

## Thiết kế

### 1. Cấu hình

Thêm khoá `practice_cert_exclude_loginnames` vào `config/order_check.php`, mặc định
`'mitalab,vietrad,sys'`, đổi được qua biến môi trường
`ORDER_CHECK_PRACTICE_CERT_EXCLUDE_LOGINS`.

Đặt ngay cạnh `practice_cert_exclude_type_ids` đã có — hai khoá cùng phục vụ một quy tắc,
để cạnh nhau thì người đọc thấy ngay cả hai chiều miễn trừ.

Giá trị **rỗng nghĩa là không miễn ai**.

### 2. So khớp

`DoctorPracticeCertRule` bỏ qua khi `executeLoginname` nằm trong danh sách.

So khớp **không phân biệt hoa thường** và **cắt khoảng trắng hai đầu** ở cả hai vế. Lý do:
HIS có tài khoản viết hoa lẫn lộn (`BHXHConnector`, `BMCS`, `PACS`), nên so khớp phân biệt
hoa thường sẽ bỏ sót một cách im lặng — người dùng khai `pacs` mà hệ thống vẫn báo vi phạm
cho `PACS`, và không có dấu hiệu gì cho biết vì sao.

### 3. Tách hàm thuần

Phần so khớp tách thành `App\Services\OrderCheck\Support\DsMienCchn`:

```php
/** Doc CSV thanh mang loginname da chuan hoa (thuong, da trim, bo phan tu rong) */
public static function doc($csv)

/** Loginname co duoc mien kiem tra CCHN khong */
public static function duocMien($loginname, array $ds)
```

Kiểm thử được mà không cần CSDL. `duocMien` với danh sách rỗng luôn trả `false`.

### 4. Phạm vi

Chỉ áp cho `B_DOCTOR_NO_PRACTICE_CERT`.

**Không** áp cho `A_STAFF_CERT_NOT_IN_CATALOG` — người dùng đã chốt điều này trong một lần
trao đổi trước ("không sửa A_STAFF_CERT_NOT_IN_CATALOG nhé"). Hai quy tắc hỏi hai câu khác
nhau: một cái hỏi "người thực hiện có CCHN trong HIS không", cái kia hỏi "CCHN đó có trong
danh mục BHXH không".

## Kiểm thử

Trong `tests/Unit`. Cổng: `vendor/bin/phpunit --testsuite Unit`.

**Hàm thuần** (`DsMienCchnTest`):

1. `doc('mitalab,vietrad,sys')` → `['mitalab', 'vietrad', 'sys']`.
2. `doc('')` và `doc(null)` → mảng rỗng.
3. `doc(' Mitalab , VIETRAD ')` → `['mitalab', 'vietrad']` (đã cắt và hạ thường).
4. `duocMien('mitalab', ['mitalab'])` → `true`.
5. `duocMien('MitaLab', ['mitalab'])` → `true` (không phân biệt hoa thường).
6. `duocMien(' mitalab ', ['mitalab'])` → `true` (cắt khoảng trắng).
7. `duocMien('ntdh3', ['mitalab', 'vietrad', 'sys'])` → `false` — người thật vẫn bị kiểm.
8. `duocMien('mitalab', [])` → `false` — danh sách rỗng thì không miễn ai.
9. `duocMien(null, ['mitalab'])` → `false`.

**Quy tắc** (`DoctorPracticeCertRuleTest` — tệp mới, dự án chưa có test nào cho quy tắc này):

1. Người thực hiện nằm trong danh sách miễn, không có CCHN → **không** sinh vi phạm.
2. Người thực hiện không nằm trong danh sách, không có CCHN → **vẫn** sinh vi phạm.
3. Người thực hiện nằm trong danh sách nhưng **có** CCHN → không sinh vi phạm (không đổi
   hành vi cũ).
4. Danh sách miễn rỗng → hành vi y hệt trước khi có tính năng này.

**Cấu hình** (`DsMienCchnTest` hoặc tệp cấu hình chung):

1. `config('order_check.practice_cert_exclude_loginnames')` mặc định chứa đúng ba tài khoản
   `mitalab`, `vietrad`, `sys`.

## Nghiệm thu bằng số

Đếm lại số vi phạm **sẽ còn** nếu áp danh sách miễn lên dữ liệu hiện có:

- Đếm tổng vi phạm `B_DOCTOR_NO_PRACTICE_CERT` trước khi lọc (lúc viết spec: 5.422, và
  con số này tăng theo thời gian vì bộ quét chạy mỗi 60 giây).
- Đếm lại sau khi loại `mitalab`, `vietrad`, `sys`: phải **không còn dòng nào** thuộc ba tài
  khoản đó, và phần còn lại phải **toàn bộ là người thật** (lúc viết spec: 42 dòng).

Điều kiện nghiệm thu là **vế "không còn dòng nào"**, không phải con số tuyệt đối.

Đây là nghiệm thu bắt buộc — nó chứng minh danh sách lọc đúng thứ cần lọc và không lọc oan.

## Phạm vi không làm

- **Không xoá 5.380 vi phạm cũ** của ba tài khoản đó. Chúng là lịch sử đã ghi; xoá là mất
  dấu vết. Từ lần quét sau sẽ không sinh thêm. Muốn dọn thì làm riêng, có chủ đích.
- Không đụng `A_STAFF_CERT_NOT_IN_CATALOG`.
- Không đụng `practice_cert_exclude_type_ids` đang có.
- Không thêm giao diện quản lý danh sách — cấu hình qua `.env` là đủ cho nhu cầu hiện tại.
- Không tự nhận diện tài khoản máy theo `tdl_username = loginname`.
