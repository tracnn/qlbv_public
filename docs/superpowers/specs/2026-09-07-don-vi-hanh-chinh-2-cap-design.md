# Đơn vị hành chính 2 cấp — thiết kế lại quy tắc kiểm XML3176

**Ngày:** 2026-09-07
**Module:** XML3176 — kiểm thông tin cư trú (XML1)
**Trạng thái:** Design đã duyệt, chờ lập plan

## 1. Mục tiêu

Chuyển quy tắc kiểm đơn vị hành chính của XML3176 từ mô hình **3 cấp (Tỉnh/Huyện/Xã)** sang **2 cấp (Tỉnh/Xã)** theo thay đổi địa giới hành chính, và bổ sung quy tắc **xã phải thuộc tỉnh** — quan hệ lồng nhau duy nhất còn lại sau khi bỏ cấp huyện.

## 2. Phạm vi — đã chốt với người dùng

**Trong phạm vi:**
- Danh mục `administrative_units` chuyển sang dữ liệu 2 cấp.
- Quy tắc kiểm cư trú của `Xml3176Xml1Checker`.
- Thêm một quy tắc mới: xã thuộc tỉnh.

**Ngoài phạm vi (quyết định của người dùng, ghi lại để không ai làm lại):**
- **Không** cần kiểm đúng hồ sơ cũ trước sáp nhập → **không** làm cơ chế danh mục có hiệu lực theo thời gian. Đây là phần nặng nhất đã được cắt bỏ.
- **Không** sửa module QĐ130 (không còn dùng). Checker QĐ130 vẫn kiểm 3 cấp; nếu ai đó nhập hồ sơ vào đó sau khi đổi danh mục thì sẽ báo lỗi cấp huyện — đã chấp nhận.
- **Không** đụng `mahuyen_cu_tru`: giữ nguyên hiện trạng — không kiểm, vẫn ghi ra tệp XML như cũ.

## 3. Hiện trạng (đã kiểm chứng trên mã và dữ liệu)

| Thành phần | Thực tế |
|---|---|
| Bảng `administrative_units` | Phẳng, mỗi dòng một xã kèm mã/tên huyện và tỉnh. **10.542 xã / 699 huyện / 63 tỉnh** — toàn bộ là danh mục **trước sáp nhập**; `is_active` tất cả = 1 |
| `district_code`, `district_name` | Khai **NOT NULL** |
| Quy tắc XML3176 hiện có | 4 kiểm tra tồn tại rời rạc: mã tỉnh rỗng / không có trong DM; mã xã rỗng / không có trong DM. **Không có kiểm tra lồng nhau** |
| `CommonValidationService` | Mọi hàm tra danh mục **đã lọc sẵn `is_active = true`** — tầng kiểm tra không cần sửa để hỗ trợ nghỉ hưu dòng cũ |
| `detect_keys` của mapping | `['Tỉnh Thành Phố', 'Mã TP', 'Quận Huyện']` — **bắt buộc có cột huyện** mới nhận diện được tệp |
| `required_fields` | Bao gồm `district_name`, `district_code` |
| Cơ chế thay dữ liệu | Import chỉ **upsert** theo `unique_keys = ['commune_code']`; `administrative_unit` không thuộc nhóm "xoá theo cơ sở" → **chưa có đường cho dòng cũ nghỉ hưu** |

## 4. Kiến trúc

Giữ nguyên bảng phẳng và toàn bộ khung nhập danh mục sẵn có; dùng cột `is_active` (vốn đã có nhưng chưa dùng) làm ranh giới giữa danh mục cũ và mới.

### 4.1 Migration — nới cột huyện

`district_code` và `district_name` chuyển sang **nullable**. Không xoá cột: chúng còn giữ dữ liệu của các dòng đã nghỉ hưu.

### 4.2 Mapping import — nhận được tệp 2 cấp

Trong `config/catalog_import_mapping.php`, khoá `administrative_unit`:
- `detect_keys`: bỏ `'Quận Huyện'`, còn `['Tỉnh Thành Phố', 'Mã TP', 'Phường Xã']` — nhận diện bằng cột chắc chắn có ở cả tệp 2 cấp.
- `required_fields`: bỏ `district_name`, `district_code`.
- `mapping`: **giữ nguyên** hai dòng district (tệp cũ vẫn nạp được; tệp mới không có cột thì bị bỏ qua tự nhiên).
- `unique_keys`: giữ `['commune_code']` — sau khi bỏ huyện, mã xã chính là khoá tự nhiên.

### 4.3 Lệnh chuyển đổi một lần

`php artisan hanh-chinh:chuyen-2-cap {tep} [--force]`

Toàn bộ trong **một giao dịch**, theo đúng thứ tự sau (thứ tự này là điểm mấu chốt về tính đúng):

1. Đọc tệp, thu tập hợp `commune_code` có trong tệp; in số liệu: số dòng đang có trong bảng, số dòng trong tệp.
2. Hỏi xác nhận (bỏ qua nếu có `--force`).
3. `is_active = 0` cho **toàn bộ** dòng đang có.
4. Nạp tệp qua `CatalogImportService` (upsert theo `commune_code`).
5. Với các `commune_code` có trong tệp: `is_active = 1`, đồng thời `district_code = NULL`, `district_name = NULL`.
6. In số liệu sau: số dòng đang hoạt động, số tỉnh, số xã.

**Vì sao phải có bước 5 mà không dựa vào giá trị mặc định:** dòng nào trùng `commune_code` với danh mục cũ sẽ bị **cập nhật** chứ không chèn mới, mà `is_active` không nằm trong mapping nên nó **giữ nguyên giá trị 0** vừa đặt ở bước 3 — không có bước 5 thì đúng những xã trùng mã sẽ nằm im ở trạng thái nghỉ hưu và bị báo "không tồn tại". Cùng lý do, district của dòng trùng vẫn còn giá trị cũ nên phải xoá tường minh.

**Vì sao không xoá dòng cũ:** dữ liệu danh mục là thứ người vận hành phải nhìn thấy trước khi mất. Nghỉ hưu đảo ngược được; xoá thì không.

### 4.4 Quy tắc mới — xã thuộc tỉnh

Thêm vào `CommonValidationService`:

```php
public function isAdministrativeUnitWardInProvinceValid($province_code, $commune_code)
{
    return AdministrativeUnit::where('province_code', $province_code)
    ->where('commune_code', $commune_code)
    ->where('is_active', true)
    ->exists();
}
```

Trong `Xml3176Xml1Checker`, sau hai khối kiểm tỉnh và xã hiện có, thêm khối thứ ba **chỉ chạy khi đã đủ căn cứ**:

```
nếu matinh_cu_tru KHÔNG rỗng
   VÀ maxa_cu_tru KHÔNG rỗng
   VÀ mã tỉnh tồn tại trong danh mục
   VÀ mã xã tồn tại trong danh mục
   VÀ !isAdministrativeUnitWardInProvinceValid(matinh, maxa)
→ XML1_ADMIN_INFO_ERROR_MAXA_NOT_IN_MATINH
   "Mã xã <maxa> không thuộc tỉnh <matinh>"
```

Điều kiện "cả hai đã hợp lệ riêng lẻ" là cố ý: nếu mã tỉnh sai thì lỗi đó đã được báo rồi, báo thêm lỗi lồng nhau chỉ là nhiễu trên cùng một nguyên nhân.

### 4.5 Error code & seeder

Seeder `Xml3176ErrorCatalogHanhChinhSeeder`, idempotent:

| xml | error_code | error_name | critical_error | is_check |
|---|---|---|---|---|
| XML1 | `XML1_ADMIN_INFO_ERROR_MAXA_NOT_IN_MATINH` | Mã xã không thuộc tỉnh cư trú | **false** | **false** |

**`is_check = false` — tắt sẵn, cố ý.** Quy tắc này chỉ đúng khi danh mục đã là 2 cấp thuần. Bật lúc danh mục còn cũ hoặc còn lẫn lộn sẽ báo oan hàng loạt. Người vận hành bật bằng ô tích "Có kiểm tra" ở màn **Danh mục mã lỗi XML 3176** sau khi đã nạp danh mục mới và rà thử một lô hồ sơ.

`critical_error = false`: cơ quan bảo hiểm xếp các lỗi mã tỉnh/xã vào loại "BÁO LỖI", không phải khoản trừ tiền — không chặn xuất hồ sơ.

## 5. Luồng dữ liệu

```
Tệp Excel danh mục 2 cấp (Tỉnh, Mã TP, Phường Xã, Mã PX)
   │  lệnh hanh-chinh:chuyen-2-cap
   ▼
administrative_units — dòng cũ is_active=0, dòng mới is_active=1 và district=NULL
   │  CommonValidationService (đã lọc sẵn is_active)
   ▼
Xml3176Xml1Checker — 4 kiểm tra tồn tại (giữ nguyên) + 1 kiểm tra xã∈tỉnh (mới, tắt sẵn)
```

## 6. Xử lý biên / guard

1. `matinh_cu_tru` hoặc `maxa_cu_tru` rỗng → đã có quy tắc riêng; **không** chạy kiểm tra lồng nhau.
2. Một trong hai mã không tồn tại trong danh mục → đã có quy tắc riêng; **không** chạy kiểm tra lồng nhau.
3. Danh mục chưa nạp bản 2 cấp → quy tắc mới vẫn **tắt** (`is_check = false`), không sinh lỗi nào.

## 7. Kế hoạch kiểm thử

**Unit — `CommonValidationService::isAdministrativeUnitWardInProvinceValid`** (sqlite in-memory, dựng bảng `administrative_units`, không đụng CSDL thật):
- xã thuộc đúng tỉnh, `is_active=1` → true.
- xã thuộc tỉnh khác → false.
- xã đúng tỉnh nhưng `is_active=0` (dòng đã nghỉ hưu) → false.
- mã rỗng → false.

**Hồi quy:** toàn bộ `tests/Unit/Xml3176` phải xanh (hiện 197).

**Kiểm chứng lệnh chuyển đổi** — thủ công, có số liệu, trên bản sao dữ liệu:
- Trước: 10.542 dòng, 63 tỉnh, tất cả hoạt động.
- Sau: số dòng hoạt động = số dòng trong tệp; số tỉnh hoạt động = 34; dòng cũ còn nguyên nhưng `is_active=0`.
- Chọn ngẫu nhiên vài xã trùng mã giữa hai danh mục, khẳng định chúng **đang hoạt động** và `district_code` đã rỗng — đây chính là ca mà bước 5 sinh ra để xử lý.

## 8. Thứ tự triển khai (bắt buộc đúng thứ tự)

1. Chạy migration (nới cột huyện).
2. Chạy `php artisan db:seed --class=Xml3176ErrorCatalogHanhChinhSeeder` — nạp mã lỗi ở trạng thái **tắt**.
3. Chạy `php artisan hanh-chinh:chuyen-2-cap <tệp danh mục mới>`; đối chiếu số liệu in ra.
4. Rà một lô hồ sơ mới: các lỗi "mã tỉnh/mã xã không tồn tại" phải **không** tăng bất thường. Nếu tăng → danh mục nạp thiếu, dừng lại, **chưa** sang bước 5.
5. Bật ô tích "Có kiểm tra" cho `XML1_ADMIN_INFO_ERROR_MAXA_NOT_IN_MATINH` ở màn Danh mục mã lỗi.
6. Rà tiếp một lô nữa để xem tỷ lệ lỗi xã∉tỉnh có hợp lý không.

Đảo bước 3 và 5 sẽ làm mọi hồ sơ báo lỗi xã∉tỉnh, vì danh mục cũ 3 cấp có cặp tỉnh–xã khác hẳn danh mục mới.

## 9. Rủi ro

- **Hồ sơ cũ nhập lại sẽ báo sai.** Hệ quả trực tiếp của quyết định không làm hiệu lực theo thời gian. Nếu về sau phát sinh nhu cầu quyết toán bổ sung kỳ cũ, phải quay lại làm cơ chế tra danh mục theo ngày khám chữa bệnh — thiết kế hiện tại không cản trở việc đó (dòng cũ vẫn còn trong bảng, chỉ ở trạng thái nghỉ hưu).
- **Module QĐ130 sẽ báo lỗi cấp huyện** sau khi đổi danh mục. Đã chấp nhận vì không còn dùng; nếu có người dùng lại thì phải gỡ ba kiểm tra cấp huyện của nó.
- **Tệp danh mục mới phải đủ và đúng.** Lệnh in số liệu trước/sau chính là để phát hiện tệp thiếu; bước 4 trong quy trình triển khai là chốt chặn thứ hai.
