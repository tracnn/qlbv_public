# Bộ lọc cơ sở KCB cho bốn màn danh mục theo cơ sở

Ngày: 2026-07-30

## Mục tiêu

Bốn danh mục theo cơ sở — thuốc, vật tư y tế, dịch vụ kỹ thuật, khoa phòng giường — hiện liệt
kê toàn bộ dòng của mọi cơ sở lẫn nhau. Thêm ô lọc theo cơ sở KCB để tìm cho dễ.

## Ngữ nghĩa của bộ lọc

Chọn cơ sở `01929` hiện **danh mục có hiệu lực cho cơ sở đó**: dòng của `01929` **cộng** các
dòng có `ma_cskcb` trống (dùng chung mọi cơ sở).

Đây **không** phải "chỉ dòng nào ghi đúng `01929`". Lý do: đó chính là tập dòng mà hệ thống áp
dụng khi kiểm hồ sơ của `01929`, qua `scopeCuaCoSo`. Lọc theo cách khác sẽ cho người dùng một
danh sách **không khớp** với thứ thực tế đang được dùng — sai lệch nguy hiểm hơn là không có bộ
lọc.

Vì vậy dùng lại đúng `scopeCuaCoSo` của từng model thay vì viết điều kiện riêng: một nguồn sự
thật, không thể lệch nhau khi ai đó sửa quy tắc.

## Hiện trạng đo được

Bốn cặp `index*Catalog()` / `fetch*Catalog()` trong `CategoryBHYTController` có cấu trúc giống
hệt nhau: `index` trả view không kèm biến, `fetch` gọi `Model::query()` rồi `Datatables::of()`.

Cả bốn model đã có `scopeCuaCoSo` (`MedicineCatalog`, `MedicalSupplyCatalog`, `ServiceCatalog`,
`DepartmentBedCatalog`).

`resources/views/partials/ma_cskcb.blade.php` đã tồn tại, có sẵn mục "Tất cả cơ sở" và tham số
hoá khuôn cột (`$colClass`, `$formGroup`) — đang dùng ở màn XML3176 và order-check.

## Thiết kế

### 1. Ba chỗ sửa cho mỗi màn

| Chỗ | Việc |
| --- | --- |
| `index*Catalog()` | Truyền `$danhSachCoSo = DanhSachCoSo::danhSach()` xuống view |
| `fetch*Catalog()` | `Model::cuaCoSo($request->get('ma_cskcb'))` thay cho `Model::query()` |
| Blade | Chèn `@include('partials.ma_cskcb')`, gửi `ma_cskcb` trong ajax, nạp lại khi đổi |

`fetch*` nhận `Request $request` — hiện chưa nhận tham số nào.

### 2. Dùng lại partial sẵn có

Không viết ô chọn mới. Partial đã có "Tất cả cơ sở" (giá trị rỗng) — và `scopeCuaCoSo` với mã
rỗng trả về query nguyên vẹn, nên "Tất cả cơ sở" hoạt động đúng mà không cần nhánh điều kiện
nào.

Dùng `$colClass = 'col-md-3'`, `$formGroup = false` — khuôn của màn order-check, vì bốn màn
danh mục cũng đặt ô lọc là con trực tiếp của một hàng. Bọc `row` bên trong một cột sẽ sinh
margin âm hai bên và làm vỡ hàng.

### 3. Danh sách cơ sở

Lấy từ `DanhSachCoSo::danhSach()` — mọi cơ sở đang hoạt động trong HIS, giống bộ lọc ở màn
XML3176 và order-check. Nhất quán với phần còn lại của hệ thống.

Không dùng `CoSoTraCuu::tuCauHinh()` (chỉ cơ sở đã khai tài khoản cổng BHXH): danh mục là dữ
liệu nội bộ, không liên quan tới việc có tài khoản cổng hay không.

## Kiểm thử

Trong `tests/Unit`. Cổng: `vendor/bin/phpunit --testsuite Unit`.

`LocCoSoManDanhMucTest` — quét mã nguồn đã bỏ comment bằng `Tests\Support\LocComment`:

1. Bốn `fetch*Catalog()` đều gọi `cuaCoSo(` và đều nhận `Request $request`.
2. Bốn `index*Catalog()` đều truyền `danhSachCoSo`.
3. Bốn blade đều `@include('partials.ma_cskcb')`.
4. Bốn blade đều gửi `ma_cskcb` trong dữ liệu ajax.
5. **Không** thêm ô lọc vào các màn danh mục dùng chung (ICD10, ICD YHCT, trang thiết bị) —
   chúng không có cột `ma_cskcb`, thêm ô lọc vào là gây hiểu nhầm.

Kiểm hành vi trên dữ liệu thật (`DepartmentBedCatalog` đang có dữ liệu của `01929`):

1. `cuaCoSo('01929')->count()` bằng tổng số dòng của `01929` cộng số dòng dùng chung.
2. `cuaCoSo('')->count()` bằng tổng số dòng — mã rỗng nghĩa là không lọc.

## Nghiệm thu

- `vendor/bin/phpunit --testsuite Unit` xanh.
- Số `<th>` vẫn khớp số phần tử `columns` ở cả bốn màn (test đã có từ đợt trước).

## Phạm vi không làm

- **Không** thêm bộ lọc cho ICD10, ICD YHCT, trang thiết bị, nhân viên y tế và các danh mục
  dùng chung khác.
- **Không** thêm lựa chọn "chỉ xem dòng dùng chung". Nghe tiện cho việc dọn dữ liệu nhưng làm
  ngữ nghĩa ô lọc phức tạp hơn hẳn, và người dùng chưa nêu nhu cầu đó.
- **Không** sửa `partials/ma_cskcb.blade.php`; dùng nguyên như đang có.
- **Không** đổi cột hiển thị của bốn màn.
