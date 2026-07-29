# Danh mục BHYT: bổ sung 3 màn còn thiếu, xem chi tiết, và xoá toàn bộ

Ngày: 2026-07-29

## Mục tiêu

1. Bổ sung màn quản lý cho 3 bộ danh mục BHYT đã nhập được nhưng chưa xem được.
2. Thêm cột `MA_CSKCB` vào danh sách của 3 danh mục phân theo cơ sở.
3. Thêm màn xem chi tiết (chỉ đọc) cho cả 11 bộ.
4. Thêm chức năng xoá toàn bộ một danh mục cho `superadministrator`, để làm sạch trước khi nhập mới.

## Hiện trạng đo được

`config/catalog_import_mapping.php` hỗ trợ **11 bộ**. Menu quản lý BHYT chỉ có **8**.
Ba bộ đã có dữ liệu trong CSDL nhưng không có màn xem:

| Bộ | Bảng | Số dòng | Model |
| --- | --- | --- | --- |
| Đơn vị hành chính | `administrative_units` | 10.542 | `App\Models\BHYT\AdministrativeUnit` |
| Cơ sở KCB | `medical_organizations` | 13.754 | `App\Models\BHYT\MedicalOrganization` |
| Nghề nghiệp | `job_categories` | 835 | `App\Models\BHYT\JobCategory` |

Hai mục "DM lỗi Xml 4750" và "DM lỗi Xml 3176" trong menu BHYT **không** thuộc 11 bộ này —
chúng là danh mục mã lỗi. Giữ nguyên, không đụng.

`medicine_catalogs` có **26 cột** nhưng danh sách chỉ hiện 11 — đây là lý do màn chi tiết
có giá trị thật, không phải làm cho có.

## Cạm bẫy đã phát hiện: `ma_cskcb` mang hai nghĩa khác nhau

Bốn bảng có cột `ma_cskcb`, nhưng chỉ **ba** trong đó dùng nó để phân tách theo cơ sở:

| Bảng | Có cột `ma_cskcb` | Nghĩa |
| --- | --- | --- |
| `medicine_catalogs` | có | cơ sở sở hữu danh mục |
| `medical_supply_catalogs` | có | cơ sở sở hữu danh mục |
| `service_catalogs` | có | cơ sở sở hữu danh mục |
| `medical_organizations` | có | **khoá của chính danh mục** — mã của từng cơ sở trong danh sách |

Hệ quả bắt buộc: cờ `theo_co_so` phải được **khai báo tường minh**, và bài kiểm thử phải
**chốt cứng đúng ba bảng**. Suy ra `theo_co_so` từ sự tồn tại của cột `ma_cskcb` sẽ đánh
dấu nhầm `medical_organizations`, dẫn tới màn xoá hiện ô chọn cơ sở sai chỗ và lọc sai.

## Thiết kế

### 1. Sổ đăng ký 11 bộ danh mục

Hiện "11 bộ danh mục BHYT là những bộ nào" không được ghi ở đâu cả — nó nằm rải trong
`config/catalog_import_mapping.php` (11 khoá), trong menu (8 mục), và trong
`CategoryBHYTController` (8 cặp method). Cả ba tính năng của spec này đều cần danh sách đó.

Tạo `config/danh_muc_bhyt.php` làm nguồn duy nhất, khoá trùng khoá của
`catalog_import_mapping`:

```php
'medicine' => [
    'ten'        => 'DM thuốc BHYT',
    'model'      => App\Models\BHYT\MedicineCatalog::class,
    'bang'       => 'medicine_catalogs',
    'theo_co_so' => true,
],
```

Đủ 11 khoá: `medicine`, `medical_supply`, `service`, `icd10`, `icd_yhct`, `medical_staff`,
`department_bed`, `equipment`, `administrative_unit`, `medical_organization`,
`job_categories`.

`theo_co_so` chỉ đúng với `medicine`, `medical_supply`, `service`.

**Không viết lại 8 màn đã có.** Chúng giữ nguyên cặp method `index*`/`fetch*` hiện tại.
Sổ đăng ký chỉ phục vụ phần mới: 3 màn mới, màn chi tiết, và chức năng xoá.

### 2. Ba màn quản lý mới

Mỗi màn một cặp method trong `CategoryBHYTController` và một blade DataTable server-side,
sao đúng khuôn `resources/views/category/bhyt/icd10_catalog.blade.php`.

| Màn | Route index | Route fetch | Blade | Cột hiển thị |
| --- | --- | --- | --- | --- |
| Đơn vị hành chính | `category-bhyt.administrative-unit` | `category-bhyt.fetch-administrative-unit` | `administrative_unit.blade.php` | `province_code`, `province_name`, `district_code`, `district_name`, `commune_code`, `commune_name` |
| Cơ sở KCB | `category-bhyt.medical-organization` | `category-bhyt.fetch-medical-organization` | `medical_organization.blade.php` | `ma_cskcb`, `ten_cskcb`, `dia_chi_cskcb` |
| Nghề nghiệp | `category-bhyt.job-category` | `category-bhyt.fetch-job-category` | `job_category.blade.php` | `job_code`, `job_name` |

Ba route nằm trong nhóm `category/` sẵn có với `checkrole:category-manager`, giống 8 màn kia.

Ba mục menu chèn vào khối `BHYT` của `config/adminlte.php`, **sau** "DM Trang thiết bị" và
**trước** "DM lỗi Xml 4750":

- `DM Đơn vị hành chính`
- `DM Cơ sở KCB`
- `DM Nghề nghiệp`

### 3. Cột MA_CSKCB trong danh sách

Thêm cột vào 3 blade: `medicine_catalog`, `medical_supply_catalog`, `service_catalog`.

Giá trị rỗng hoặc `NULL` hiển thị là **`Dùng chung`**. Để trống thì người xem không phân
biệt được "chưa gán cơ sở" với "dùng chung cho mọi cơ sở" — mà hiện tại `ma_cskcb = NULL`
đúng nghĩa là dùng chung.

Không thêm cột này vào `medical_organizations` dưới danh nghĩa "cơ sở sở hữu" — ở bảng đó
`ma_cskcb` đã là cột dữ liệu của chính danh mục và đã nằm trong danh sách hiển thị.

### 4. Màn chi tiết — chỉ xem

Một route dùng chung cho cả 11 bộ:

```
GET category/bhyt/chi-tiet/{loai}/{id}
name: category-bhyt.chi-tiet
middleware: checkrole:category-manager
```

`{loai}` là khoá trong sổ đăng ký. Controller tra sổ đăng ký, lấy `model`, tìm theo `id`,
trả JSON dạng:

```json
{ "ten": "DM thuốc BHYT", "truong": [ { "nhan": "MA_THUOC", "gia_tri": "..." }, ... ] }
```

`loai` không có trong sổ đăng ký → HTTP 404. `id` không tồn tại → HTTP 404.

**Nhãn trường:** lấy từ `config/catalog_import_mapping.php` — với mỗi trường, phần tử
**đầu tiên** của mảng tên cột chấp nhận được chính là tên chuẩn (ví dụ `ma_thuoc` →
`MA_THUOC`). Cột nào không có trong mapping (`id`, `ma_cskcb`, `created_at`,
`updated_at`…) thì dùng tên cột thô. Việc dựng bảng nhãn là **hàm thuần**, kiểm thử được
mà không cần CSDL.

**Giao diện:** một partial blade dùng chung chứa modal Bootstrap rỗng và đoạn JS dựng bảng
khoá–giá trị từ JSON. Cả 11 blade `@include` partial đó và thêm một cột nút "Xem" vào
DataTable. Không nhân bản modal 11 lần.

### 5. Xoá toàn bộ một danh mục

**Vị trí:** màn "Nhập khẩu danh mục" (`resources/views/category/bhyt/import.blade.php`),
trong một khối riêng có viền cảnh báo, **chỉ hiện với `superadministrator`**. Đặt ở đó vì
mục đích là làm sạch ngay trước khi nhập mới — cùng một chỗ, cùng một lượt thao tác.

**Luồng:**

1. Chọn loại danh mục — 11 lựa chọn lấy từ sổ đăng ký.
2. Nếu loại được chọn có `theo_co_so = true`, hiện thêm ô chọn cơ sở, nguồn là
   `App\Services\BHYT\DanhSachCoSo::danhSach()`, kèm lựa chọn **"Tất cả cơ sở"**. Loại
   dùng chung không hiện ô này.
3. Người dùng bấm "Đếm số dòng sẽ xoá" → hệ thống trả về **số dòng chính xác** sẽ bị xoá
   theo đúng điều kiện đã chọn.
4. Muốn bật được nút xoá, phải gõ đúng chữ `XOA` vào ô xác nhận.
5. Bấm xoá → thực thi, trả về số dòng đã xoá thật sự.

**Route:**

```
GET  category/bhyt/xoa-danh-muc/dem   name: category-bhyt.xoa-danh-muc-dem
POST category/bhyt/xoa-danh-muc       name: category-bhyt.xoa-danh-muc
middleware: checkrole:superadministrator
```

Hai route này nằm trong nhóm riêng, **không** dùng chung `checkrole:category-manager` với
các màn còn lại.

**Lõi nghiệp vụ tách thành hàm thuần** trong `app/Services/Category/XoaDanhMuc.php`:

```php
// Tra so dang ky, tra ve ['bang' => ..., 'dieu_kien' => [...]]; nem ngoai le neu loai sai.
public static function dieuKien($loai, $maCskcb, array $soDangKy)
```

Quy tắc:

- `loai` không có trong sổ đăng ký → ném `InvalidArgumentException`.
- `theo_co_so = false` → `dieu_kien` rỗng, **bỏ qua** `maCskcb` kể cả khi được truyền vào.
  Nếu không bỏ qua, một tham số lạc sẽ lọc theo cột không tồn tại và làm vỡ truy vấn.
- `theo_co_so = true` và `maCskcb` rỗng (nghĩa là "Tất cả cơ sở") → `dieu_kien` rỗng.
- `theo_co_so = true` và `maCskcb` có giá trị → `dieu_kien = ['ma_cskcb' => $maCskcb]`.

Controller chỉ ghép `DB::table($bang)->where($dieuKien)` rồi `count()` hoặc `delete()`.
Nhờ tách như vậy, phần dễ sai nhất — lọc theo cơ sở — kiểm thử được mà không đụng một dòng
dữ liệu nào.

`ma_cskcb` gửi lên phải được kiểm nằm trong `DanhSachCoSo::danhSach()`, theo đúng cách
`CategoryBHYTController` đang làm ở bước nhập khẩu (dòng 290). Không hợp lệ → HTTP 422.

**Cảnh báo bắt buộc hiện trên giao diện:** xoá sạch một danh mục mà chưa nhập lại thì
XML3176 và order-check sẽ báo **mọi** mã là sai. Đã đo trong phiên trước: ba danh mục rỗng
sinh khoảng 36.100 vi phạm giả. Khuyến nghị xoá và nhập lại liền tay, không để qua đêm.

## Kiểm thử

Toàn bộ trong `tests/Unit`. Cổng: `vendor/bin/phpunit --testsuite Unit`.

**Sổ đăng ký khớp thực tế** (`SoDangKyDanhMucTest`):

1. Sổ đăng ký có đúng 11 khoá, và tập khoá **bằng đúng** tập khoá của
   `config/catalog_import_mapping.php`.
2. Mọi `bang` khai báo đều tồn tại trong CSDL.
3. Mọi `model` đều là lớp tồn tại, và `(new $model)->getTable()` khớp `bang` đã khai.
4. Tập loại có `theo_co_so = true` **bằng đúng** `['medicine', 'medical_supply', 'service']`
   — chốt cứng, không suy ra từ cột `ma_cskcb`, vì `medical_organizations` cũng có cột đó
   nhưng mang nghĩa khác.

**Điều kiện xoá** (`XoaDanhMucTest`) — hàm thuần, không đụng CSDL:

1. Loại dùng chung (`icd10`) → `dieu_kien` rỗng.
2. Loại dùng chung + truyền `maCskcb` → vẫn rỗng (tham số bị bỏ qua).
3. Loại theo cơ sở + `maCskcb` rỗng → `dieu_kien` rỗng.
4. Loại theo cơ sở + `maCskcb = '01929'` → `['ma_cskcb' => '01929']`.
5. Loại không tồn tại → ném `InvalidArgumentException`.
6. `medical_organization` + truyền `maCskcb` → `dieu_kien` **rỗng**. Đây là bài kiểm chống
   chính cái bẫy đã nêu ở trên.

**Nhãn trường của màn chi tiết** (`NhanTruongTest`) — hàm thuần:

1. Trường có trong mapping → lấy tên chuẩn (`ma_thuoc` → `MA_THUOC`).
2. Trường không có trong mapping (`ma_cskcb`) → giữ tên cột thô.

**Menu và route** (`MenuDanhMucBhytTest`):

1. Khối `BHYT` có đủ 3 mục mới, đặt sau `DM Trang thiết bị` và trước `DM lỗi Xml 4750`.
2. Route chi tiết có middleware `checkrole:category-manager`.
3. Hai route xoá có middleware `checkrole:superadministrator` và **không** có
   `checkrole:category-manager`.
4. Cả ba route mới đều còn nằm trong nhóm `auth`.

## Phạm vi không làm

- Không viết lại 8 màn quản lý đã có.
- Không cho sửa dữ liệu danh mục trên giao diện — màn chi tiết chỉ đọc. Nguồn chuẩn của
  các danh mục này là tệp BHXH phát hành; sửa tay sẽ bị ghi đè ở lần nhập kế tiếp mà người
  sửa lại tưởng đã sửa xong.
- Không đụng hai danh mục mã lỗi XML.
- Không thêm nhật ký kiểm toán cho thao tác xoá.
- Không xoá theo điều kiện nào khác ngoài loại danh mục và cơ sở.
