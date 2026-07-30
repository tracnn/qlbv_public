# Danh mục Khoa Phòng Giường: thêm TU_NGAY và MA_CSKCB

Ngày: 2026-07-30

## Mục tiêu

Danh mục Khoa Phòng Giường nhận thêm cột `TU_NGAY` từ tệp BHXH, và trở thành danh mục **theo
từng cơ sở KCB** như ba danh mục thuốc / vật tư / dịch vụ.

## Hiện trạng đo được

Tệp thật của người dùng (`01929_DM khoa phòng giường.xlsx`): header ở **dòng 1**, 91 dòng dữ
liệu, 15 cột. Cột `TU_NGAY` nằm giữa `LIEN_KHOA` và `DEN_NGAY`. **14/91 dòng có giá trị**,
định dạng chuỗi 8 ký tự `yyyymmdd` (ví dụ `20260513`). `DEN_NGAY` rỗng toàn bộ.

Bảng `department_bed_catalogs`:

- có `den_ngay varchar(255) NULL`, **không có** `tu_ngay`, **không có** `ma_cskcb`
- khoá duy nhất `department_bed_catalogs_ma_khoa_unique` chỉ gồm `ma_khoa`
- **0 dòng dữ liệu**

`config/catalog_import_mapping.php` khối `department_bed`: có `den_ngay`, thiếu `tu_ngay` và
`ma_cskcb`; `unique_keys => ['ma_khoa']`.

`DepartmentBedCatalog::$fillable` thiếu cả hai cột, và model **không có** scope `cuaCoSo`.

`CatalogImportService::DANH_MUC_THEO_CO_SO = ['medicine', 'medical_supply', 'service']` —
không có `department_bed`. `config/danh_muc_bhyt.php` khai `department_bed.theo_co_so = false`.

Hệ quả hiện nay: cột `TU_NGAY` trong tệp bị **bỏ qua im lặng** — nhập xong không báo lỗi gì,
chỉ là mất dữ liệu.

## Thiết kế

### 1. Bốn nơi phải sửa cho MA_CSKCB

Thiếu bất kỳ nơi nào cũng hỏng **im lặng**, không nơi nào báo lỗi:

| Nơi | Vai trò | Thiếu thì sao |
| --- | --- | --- |
| Migration | Lưu trữ | Không lưu được |
| `catalog_import_mapping.php` | Đọc cột từ tệp | Cột trong tệp bị bỏ qua |
| `CatalogImportService::DANH_MUC_THEO_CO_SO` | Áp cơ sở chọn trên màn nhập; tô màu cột trong biểu mẫu | Chọn cơ sở trên màn **không có tác dụng** |
| `config/danh_muc_bhyt.php` → `theo_co_so` | Chức năng xoá danh mục theo cơ sở | Xoá theo cơ sở sẽ xoá cả dữ liệu của cơ sở khác |

Cộng `DepartmentBedCatalog::$fillable`: thiếu thì Eloquent **âm thầm bỏ cột** khi
`updateOrCreate()`.

### 2. Khoá duy nhất — điểm rủi ro nhất

Khoá hiện tại chỉ gồm `ma_khoa`. Thêm cột `ma_cskcb` mà không sửa khoá thì cơ sở `01929` và
`37470` cùng mã khoa `K24` sẽ **đè lên nhau** — thêm cột không tự giải quyết việc này.

Khoá mới: `['ma_khoa', 'ma_cskcb']`, đổi ở **cả hai nơi** — migration và `unique_keys` trong
mapping.

Bảng đang 0 dòng nên đổi khoá không vướng dữ liệu trùng. Đây là thời điểm thuận lợi nhất; để
sau khi đã nhập dữ liệu thì phải xử lý trùng trước mới đổi được.

### 3. Kiểu cột

`tu_ngay varchar(255) NULL` đặt **sau** `lien_khoa` và **trước** `den_ngay`, khớp kiểu với
`den_ngay` sẵn có. Không dùng kiểu `date`: dữ liệu là chuỗi `yyyymmdd`, và dùng hai kiểu khác
nhau cho hai cột cùng bản chất sẽ gây lỗi so sánh về sau.

`ma_cskcb varchar(20) NULL`, có index, khớp với ba danh mục đã làm.

### 4. Scope lọc theo cơ sở

Thêm `scopeCuaCoSo` vào `DepartmentBedCatalog`, **chép đúng ngữ nghĩa** của
`MedicineCatalog::scopeCuaCoSo`: dòng có `ma_cskcb` rỗng (null hoặc chuỗi rỗng) dùng chung cho
**mọi** cơ sở. Nhờ vậy dữ liệu nhập trước khi có tính năng này vẫn chạy, không gây thoái lui.

Mã cơ sở rỗng truyền vào = không lọc.

### 5. Biểu mẫu không phải sửa tay

`CatalogTemplateExport` sinh header từ chính `catalog_import_mapping`, và tô màu xanh riêng cho
cột mã cơ sở dựa trên `DANH_MUC_THEO_CO_SO`. Sửa hai chỗ đó là biểu mẫu tự có `TU_NGAY` và
`MA_CSKCB` kèm chú thích "Bỏ trống = dùng chung cho MỌI cơ sở".

Không tạo tệp biểu mẫu tĩnh nào.

### 6. Hai nguồn sự thật cho cùng một khái niệm

`DANH_MUC_THEO_CO_SO` (hằng số trong mã) và `theo_co_so` (config) nói cùng một điều ở hai nơi.
Đợt này cập nhật cả hai, và **thêm test chốt chúng luôn khớp nhau** — lần sau ai thêm danh mục
theo cơ sở mà quên một bên thì test đỏ ngay, thay vì hỏng im lặng ở chức năng xoá.

**Không hợp nhất chúng ở đợt này**: đó là thay đổi lan sang tất cả các danh mục khác, rủi ro
hơn lợi trong phạm vi đang cần.

## Kiểm thử

Trong `tests/Unit`. Cổng: `vendor/bin/phpunit --testsuite Unit`.

`KhoaPhongGiuongTheoCoSoTest`:

1. Bảng `department_bed_catalogs` có cột `tu_ngay` và `ma_cskcb`.
2. Khoá duy nhất của bảng gồm **cả** `ma_khoa` và `ma_cskcb`.
3. Mapping `department_bed` có `tu_ngay` và `ma_cskcb`; `tu_ngay` nhận cả ba biến thể
   `TU_NGAY` / `Từ ngày` / `TU NGAY`.
4. `unique_keys` trong mapping gồm `ma_cskcb`.
5. `DepartmentBedCatalog::$fillable` chứa `tu_ngay` và `ma_cskcb`.
6. `scopeCuaCoSo` tồn tại.
7. `department_bed` có trong `DANH_MUC_THEO_CO_SO`.
8. **Hai nguồn sự thật khớp nhau**: tập danh mục có `theo_co_so = true` trong
   `config/danh_muc_bhyt.php` bằng đúng `DANH_MUC_THEO_CO_SO`.
9. Biểu mẫu sinh ra cho `department_bed` chứa header `TU_NGAY` và `MA_CSKCB`, và `MA_CSKCB`
   nằm trong `facilityHeaders()` (tức được tô màu riêng).

## Nghiệm thu bằng số

Nhập tệp thật `01929_DM khoa phòng giường.xlsx` với cơ sở `01929`, rồi đếm:

- tổng số dòng trong bảng = **91**
- số dòng có `tu_ngay` khác rỗng = **14**
- số dòng có `ma_cskcb = '01929'` = **91**

Đây là nghiệm thu bắt buộc: nó chứng minh cả hai cột mới thực sự được ghi, chứ không bị bỏ
qua im lặng.

## Phạm vi không làm

- **Không** đụng ba danh mục theo cơ sở đã có.
- **Không** hợp nhất `DANH_MUC_THEO_CO_SO` với `theo_co_so`.
- **Không** thêm `tu_ngay` cho các danh mục khác đang thiếu — chỉ làm đúng danh mục được yêu cầu.
- **Không** tạo tệp biểu mẫu tĩnh; biểu mẫu vẫn sinh từ cấu hình.
