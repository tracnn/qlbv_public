# Tối ưu chức năng nhập danh mục

Ngày: 2026-07-28
Trạng thái: đã chốt thiết kế

## 1. Bối cảnh

`CatalogImportService` nhập 11 loại danh mục từ Excel. Rà soát phát hiện một lỗi chặn tính
năng vừa làm, một lỗi nuốt im lặng, và hai nút thắt hiệu năng đo được.

## 2. Khảo sát

Đo ngày 2026-07-28.

### 2.1 Ràng buộc UNIQUE chưa có `ma_cskcb` — tính năng theo cơ sở đang hỏng

Đợt trước thêm `ma_cskcb` vào `unique_keys` trong `config/catalog_import_mapping.php`, nhưng
ràng buộc UNIQUE trong cơ sở dữ liệu vẫn là bộ khoá cũ:

```
medicine_catalogs        UNIQUE(ma_thuoc, ten_thuoc, ham_luong, so_dang_ky,
                                don_gia_bh, tt_thau, tu_ngay)
medical_supply_catalogs  UNIQUE(ma_vat_tu, ten_vat_tu, tt_thau, don_gia_bh, tu_ngay)
service_catalogs         UNIQUE(ma_dich_vu, ten_dich_vu, don_gia, quy_trinh, tu_ngay)
```

Không bộ nào có `ma_cskcb`. Tái hiện được: cơ sở 1 chèn được, cơ sở 2 báo

```
Duplicate entry 'ZZBUG-X-1-SDK-10.00-T-20240101' for key 'unique_medicine_catalog'
```

Lỗi bị `catch` nuốt rồi `continue`, **dòng bị bỏ im lặng**. Nghĩa là tính năng tách danh mục
theo cơ sở chưa chạy được cho tới khi sửa việc này.

### 2.2 Nhiều cột khoá cho phép NULL — ràng buộc UNIQUE vốn đã lỏng

| Bảng | Cột khoá cho phép NULL |
|---|---|
| `medicine_catalogs` | `don_gia_bh`, `tt_thau`, `tu_ngay`, `ma_cskcb` |
| `medical_supply_catalogs` | `ten_vat_tu`, `don_gia_bh`, `tu_ngay`, `ma_cskcb` |
| `service_catalogs` | `quy_trinh`, `ma_cskcb` |

MySQL coi hai `NULL` là **khác nhau**, nên hai dòng giống hệt mà có một cột khoá `NULL` đều
lọt qua ràng buộc. Hệ quả trực tiếp: **không được dựa vào ràng buộc CSDL để khử trùng** —
mục 4.3.

### 2.3 Bộ nhớ — nút thắt nghiêm trọng nhất

```
tệp 10.000 dòng x 23 cột = 1,3 MB
Excel::toCollection:
   thời gian   : 4,3 giây
   ĐỈNH bộ nhớ : 208 MB       <- gấp ~160 lần cỡ tệp
```

`Excel::toCollection(null, $filePath)` nạp **toàn bộ** tệp vào bộ nhớ, không đọc theo lô.
Dropzone đang cho tải tệp tới 10 MB.

### 2.4 Ghi từng dòng — 2 truy vấn mỗi dòng

```
2.000 dòng danh mục thuốc
  updateOrCreate từng dòng : 3,11 giây,  4.000 truy vấn
  chèn theo lô 500 dòng    : 0,21 giây,      4 truy vấn
                             nhanh hơn 15 lần
Suy ra 20.000 dòng: ~31 giây chỉ riêng phần ghi
```

### 2.5 Nuốt lỗi im lặng

Ba vòng lặp đều `catch { Log::error; continue; }`; `hasRequiredFields` cũng `continue` mà
không đếm; controller luôn trả `'File đã upload và xử lý thành công!'`. Một tệp có thể nhập
**0 dòng** mà giao diện vẫn báo thành công.

### 2.6 `getRowValue` đổi kiểu lặp lại

Mỗi lần gọi đều `$row->toArray()` trên Collection, mà mỗi dòng gọi ~30 lần (23 trường ánh
xạ + 6 khoá + kiểm bắt buộc). Đo: 5.000 dòng × 30 trường mất 0,20 giây so với 0,01 giây nếu
đổi kiểu một lần mỗi dòng — chậm 20 lần. Nhỏ về tuyệt đối nhưng sửa gần như miễn phí.

## 3. Phạm vi

### Có làm

- Mở rộng ràng buộc UNIQUE của ba danh mục để có `ma_cskcb`.
- Đọc tệp theo lô, chặn bộ nhớ.
- Ghi theo lô: tra một lần, chèn theo lô, chỉ cập nhật dòng thực sự đổi.
- Trả kết quả nhập thật: số dòng đã nhập / bỏ qua / lỗi, kèm số dòng Excel cụ thể.
- `getRowValue` đổi kiểu một lần mỗi dòng.

### Không làm

- Chuyển sang hàng đợi — người dùng chốt giữ đồng bộ trong HTTP.
- Chuẩn hoá cột khoá thành `NOT NULL` — mục 4.3.
- Dùng `INSERT ... ON DUPLICATE KEY UPDATE` — mục 4.3.
- Từ chối cả tệp khi có dòng hỏng — người dùng chốt nhập dòng tốt, báo dòng hỏng.
- Chuẩn hoá `tu_ngay`/`den_ngay` lúc nhập — việc riêng, đã ghi ở spec danh mục theo cơ sở.
- Sửa 8 loại danh mục còn lại (ICD, nhân viên y tế…) — chúng dùng chung khung nên hưởng lợi
  từ mục 4.4–4.6, nhưng không có ràng buộc UNIQUE nào phải sửa.

## 4. Thiết kế

### 4.1 Ràng buộc UNIQUE thêm `ma_cskcb`

Migration bỏ index cũ, tạo lại có `ma_cskcb` ở cuối.

An toàn với dữ liệu đang có: index mới **rộng hơn** index cũ, mọi tổ hợp đang hợp lệ vẫn hợp
lệ. Không cần dọn dữ liệu trước.

### 4.2 Đọc theo lô

Thay `Excel::toCollection(null, $file)` bằng một lớp Import thật, cài `ToCollection` và
`WithChunkReading`, lô 1.000 dòng.

Dòng tiêu đề chỉ có ở lô đầu: lớp Import giữ trạng thái `$fieldMapping`; lô đầu tách dòng
tiêu đề ra để nhận diện loại và dựng ánh xạ, rồi xử lý phần còn lại; các lô sau xử lý trọn
lô.

Nhận diện loại danh mục và kiểm cột bắt buộc vẫn ở lô đầu, nên tệp sai cấu trúc vẫn bị từ
chối ngay chứ không ghi được nửa chừng.

### 4.3 Ghi theo lô — tra trong PHP, không dựa vào ràng buộc CSDL

Với mỗi lô:

```
1. Dựng khoá tra cho từng dòng          (mảng trong bộ nhớ)
2. MỘT truy vấn SELECT lấy dòng đã có   (whereIn theo cột khoá dẫn đầu)
3. So khớp đủ bộ khoá trong bộ nhớ
4. Dòng chưa có   -> gom, chèn theo lô  (1 truy vấn)
5. Dòng đã có     -> so nội dung; CHỈ cập nhật dòng thực sự đổi
```

Chi phí mỗi lô 500 dòng: 1 SELECT + 1 INSERT + số truy vấn cập nhật bằng đúng số dòng đổi.
Nhập lại đúng tệp cũ mà không sửa gì: **1 truy vấn mỗi lô, không ghi gì**.

**Vì sao không dùng `INSERT ... ON DUPLICATE KEY UPDATE`** dù nó chỉ tốn một truy vấn: nó
dựa hoàn toàn vào ràng buộc UNIQUE, mà mục 2.2 cho thấy ràng buộc đó lỏng vì nhiều cột khoá
cho phép `NULL`. Muốn dùng được thì phải đổi các cột đó thành `NOT NULL DEFAULT ''` trên dữ
liệu sản xuất — rủi ro lớn hơn hẳn lợi ích, vì chênh lệch chỉ là 3 truy vấn so với 1 truy
vấn mỗi 500 dòng.

Khử trùng **trong cùng tệp**: giữ tập khoá đã gặp xuyên suốt lần nhập; dòng sau trùng khoá
dòng trước thì ghi đè giá trị của dòng sau. 20.000 khoá trong bộ nhớ là không đáng kể.

### 4.4 So nội dung trước khi cập nhật

Chỉ cập nhật khi có ít nhất một trường khác giá trị đang lưu. So bằng chuỗi sau khi
`trim`, vì giá trị từ Excel và từ CSDL có thể khác kiểu (`'10'` với `10`).

### 4.5 Kết quả nhập

Lớp kết quả trả về:

```php
soDaNhap    // dong chen moi
soDaCapNhat // dong co san va co thay doi
soKhongDoi  // dong co san, khong doi
soBoQua     // thieu truong bat buoc
soLoi       // nem ngoai le khi ghi
dongLoi     // toi da 20 phan tu: [so dong Excel, ly do]
```

Controller trả các số này trong JSON; màn nhập hiện thành một dòng tóm tắt. Số dòng Excel
tính theo vị trí thật trong tệp (dòng tiêu đề là 1) để người dùng mở tệp sửa được ngay.

Giữ hành vi hiện tại là **bỏ qua dòng hỏng, không từ chối cả tệp** — người dùng chốt
2026-07-28. Khác biệt là nay có con số và số dòng, thay vì im lặng.

### 4.6 `getRowValue` đổi kiểu một lần mỗi dòng

Chuyển Collection sang mảng **một lần** ở đầu vòng lặp mỗi dòng, rồi truyền mảng vào các
hàm đọc trường.

## 5. Thay đổi mã nguồn

| Tệp | Việc |
|---|---|
| `database/migrations/…_them_ma_cskcb_vao_unique_danh_muc.php` | **mới** — mở rộng 3 ràng buộc UNIQUE |
| `app/Imports/CatalogChunkImport.php` | **mới** — đọc theo lô, giữ ánh xạ giữa các lô |
| `app/Services/Import/KetQuaNhapDanhMuc.php` | **mới** — gom số liệu kết quả |
| `app/Services/Import/GhiTheoLo.php` | **mới** — tra/chèn/cập nhật theo lô, lớp thuần phần dựng khoá |
| `app/Services/CatalogImportService.php` | dùng ba lớp trên; `getRowValue` nhận mảng |
| `app/Http/Controllers/Category/CategoryBHYTController.php` | trả kết quả nhập trong JSON |
| `resources/views/category/bhyt/import.blade.php` | hiện tóm tắt kết quả |

## 6. Kiểm thử

Cổng: `vendor/bin/phpunit --testsuite Unit`.

Phần dựng khoá, so nội dung và gom kết quả tách thành **hàm thuần** để kiểm được mà không
cần tệp Excel hay cơ sở dữ liệu.

### Dựng khoá và khử trùng

| Ca | Kỳ vọng |
|---|---|
| Hai dòng cùng bộ khoá trong một tệp | một dòng, giá trị của dòng **sau** |
| Khoá có giá trị `null` | vẫn dựng được, không nổ |
| Khoá có khoảng trắng thừa | `trim` trước khi so |
| Dòng thiếu trường bắt buộc | không vào lô ghi, tính vào `soBoQua` |

### So nội dung

| Ca | Kỳ vọng |
|---|---|
| Mọi trường giống hệt | không cập nhật |
| Một trường đổi | có cập nhật |
| `'10'` với `10` | coi là giống, không cập nhật |
| `null` với `''` | coi là giống |

### Kết quả nhập

| Ca | Kỳ vọng |
|---|---|
| Tệp 0 dòng hợp lệ | `soDaNhap = 0`, giao diện **không** báo thành công suông |
| Tệp có 3 dòng hỏng | `soLoi = 3`, `dongLoi` có số dòng Excel đúng |
| Quá 20 dòng lỗi | `dongLoi` cắt còn 20, `soLoi` vẫn đếm đủ |

### Ràng buộc UNIQUE

| Ca | Kỳ vọng |
|---|---|
| Cùng bộ khoá cũ, khác `ma_cskcb` | **hai** dòng, không báo trùng — ca tái hiện lỗi 2.1 |
| Cùng bộ khoá cũ, cùng `ma_cskcb` | một dòng |

## 7. Rủi ro

| Rủi ro | Xử lý |
|---|---|
| Đọc theo lô làm hỏng việc nhận diện loại danh mục | Nhận diện vẫn ở lô đầu; có ca kiểm tệp sai cấu trúc bị từ chối |
| Ràng buộc UNIQUE lỏng vì cột khoá cho phép NULL | Khử trùng làm trong PHP, không dựa vào CSDL (4.3) |
| Dữ liệu sản xuất có dòng trùng sẵn do NULL | Migration chỉ mở rộng index nên không vấp; khử trùng mới chỉ áp cho lần nhập sau |
| Tệp rất lớn vẫn quá 5 phút của Dropzone | Ngoài phạm vi; nếu xảy ra thì mới tính chuyện hàng đợi |
| Cập nhật từng dòng đổi vẫn tốn nhiều truy vấn khi sửa hàng loạt | Chấp nhận: trường hợp thường gặp là nhập lại tệp gần như không đổi |
