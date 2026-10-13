# Quy tắc XML3176: VTYT/thuốc thanh toán ngoài giá phải đi kèm DVKT mẹ được BHYT thanh toán

**Ngày:** 2026-10-13
**Module:** XML3176 — tiền giám định
**Nguồn nghiệp vụ:** QĐ 1519/QĐ-BYT 2026 phê duyệt giá KBCB BV Bạch Mai (PL3 cột "Ghi chú", PL4 cột "Ghi chú"); QĐ 130/4750/3176 Bảng 3 (`GOI_VTYT`, `MA_DICH_VU`); TT 04/2017/TT-BYT (VTYT chưa tính trong giá DVKT được thanh toán riêng)
**Trạng thái:** Design đã duyệt, chờ lập plan

## 1. Bài toán

Danh mục giá có khoảng 3.500 DVKT ghi chú "Chưa bao gồm …" (279 mẫu câu khác nhau): stent, bóng, quả lọc, buồng tiêm truyền, kim chọc tủy, dao siêu âm, dược chất phóng xạ, albumin… Các vật phẩm này được thanh toán **thêm**, nhưng chỉ khi DVKT mẹ được thanh toán.

Chuyên đề chủ động của BHXH phát hiện hồ sơ có VTYT/thuốc thuộc loại trên đề nghị quỹ thanh toán, trong khi DVKT mẹ:
- **không được kê** (quên kê), hoặc
- **có kê nhưng BN tự trả toàn bộ** (quỹ không thanh toán).

Hiện chưa quy tắc nào bắt trường hợp này. `goi_vtyt` được nhập/xuất nhưng không checker nào đọc. `XML3_DATE_MISMATCH` (`Xml3176Xml3Checker.php:902`) chỉ kiểm dòng VTYT **đã có** `ma_dich_vu`.

## 2. Hiện trạng đã đo (CSDL thật 192.168.200.68/qlbv, chỉ đọc, 13/10/2026)

XML3: 1.399.293 dòng (10/2023 – 10/2026).

| Phép đo | Kết quả | Hệ quả |
|---|---|---|
| Dòng VTYT có `goi_vtyt` + `ma_dich_vu` | 8.286 (8.060 có BHYT trả) | HIS chỉ gắn gói khi DVKT mẹ là BHYT |
| — trong đó DVKT mẹ thiếu / quỹ không trả / số gói > số lần DVKT | **0 / 0 / 0** | R2 là chốt chặn, hôm nay không thu lỗi |
| Dòng VTYT **không** liên kết | 257.316 (251.304 có BHYT trả) | Lỗi nằm ở đây; phần lớn là bơm kim, dây truyền hợp lệ |
| XML2 có `ma_dich_vu` (từ 01/2026, BHYT trả) | 4.593 dòng — DVKT mẹ thiếu/không trả: **0** | Như VTYT |
| Học thô cặp vật phẩm–DVKT từ dữ liệu có liên kết, không lọc | VTYT: 9.067 hồ sơ; thuốc: 16.265 hồ sơ bị đánh dấu | **Không dùng được**: bơm kim, NaCl, Meropenem… cũng từng được gắn DVKT |
| Lọc vật phẩm có tỷ lệ gắn ≥ 80%, ≥ 5 lần | VTYT: 356 vật phẩm → **6 hồ sơ**, ~7,4 tr; thuốc: 9 vật phẩm → **7 hồ sơ**, ~2,2 tr | Gọn, dùng được |
| Kiểm tay | 4/4 là lỗi thật (bảng dưới) | |

Ca kiểm tay:

| Hồ sơ | Vật phẩm BHYT trả | Thực tế hồ sơ |
|---|---|---|
| `000007339564` | Buồng tiêm truyền cấy dưới da 4.188.000đ | Không có DVKT `12.0448.1187` Đặt buồng tiêm truyền |
| `000007285039` | Kim chọc tủy xương | Không có DVKT chọc hút tủy/tủy đồ |
| `000007301718` | Ống dẫn lưu qua da 2.200.000đ | Không có DVKT dẫn lưu nào |
| `000007403605` | Omnipaque (cản quang) 363.632đ | Chỉ có CLVT sọ não **không tiêm** cản quang |

Ca **không** phải lỗi (đã loại đúng): `000007321137` stent đại tràng — DVKT `20.0053.0105` "Nội soi đặt stent thực quản, dạ dày, tá tràng, đại tràng, trực tràng" có trong hồ sơ.

**Giới hạn:** vật phẩm chưa từng được HIS gắn gói thì không học được. Bù bằng dòng bổ sung tay (§3.2). Sinh tự động từ ghi chú QĐ 1519 để đợt sau.

## 3. Thiết kế

### 3.1 Bảng `xml3176_vat_pham_kem_dvkt`

Một bảng, phân biệt bằng `loai` (thay vì hai bảng riêng — cùng cấu trúc, cùng lệnh học).

| Cột | Kiểu | Ý nghĩa |
|---|---|---|
| `id` | bigint | |
| `loai` | string(10) | `VTYT` \| `THUOC` |
| `ma_vat_pham` | string(255) | `ma_vat_tu` (XML3) hoặc `ma_thuoc` (XML2) |
| `ten_vat_pham` | string(1024) null | Tên hiển thị lấy từ dữ liệu |
| `ten_khop` | string(255), default `''` | Tên chuẩn hoá khi VTYT dùng mã nhóm chung; `''` với mọi trường hợp khác |
| `ma_dvkt` | string(255) | DVKT mẹ hợp lệ, đã chuẩn hoá (bỏ hậu tố `_GT`) |
| `ten_dvkt` | string(1024) null | |
| `nguon` | string(10) | `HOC` \| `TAY` |
| `so_lan_gan` | int | Số dòng cặp này được HIS gắn |
| `ty_le_gan` | decimal(5,4) null | Tỷ lệ gắn của vật phẩm lúc học |
| `hieu_luc` | boolean, default true | Tắt thì quy tắc bỏ qua; lần học lại không bật lại |
| `ghi_chu` | string(1024) null | |
| timestamps | | |

Khoá duy nhất: (`loai`, `ma_vat_pham`, `ten_khop`, `ma_dvkt`).

**Khoá khớp vật phẩm** (`VatPhamKemDvktKey::cua($loai, $ma, $ten)`):
- `THUOC`: theo `ma_thuoc`.
- `VTYT` có mã đầy đủ (nhiều hơn 3 đoạn, ví dụ `N08.00.150.xxxx.xxx.xxxx`): theo `ma_vat_tu`.
- `VTYT` mã nhóm chung (≤ 3 đoạn, ví dụ `N04.04.010`): theo `ma_vat_tu` + tên chuẩn hoá (trim, gộp khoảng trắng, chữ thường). Lý do đo được: `N04.04.010` vừa là "Ống dẫn lưu đuôi lợn" vừa là "Catheter chẩn đoán 4 cực".

**Chuẩn hoá mã DVKT** (`chuanHoaMaDvkt`): bỏ hậu tố `_GT`, trim. Áp dụng cả khi học lẫn khi kiểm.

Model: `App\Models\BHYT\Xml3176VatPhamKemDvkt`.

### 3.2 Lệnh học và duyệt

**`php artisan xml3176:hoc-vat-pham-kem-dvkt`**

| Tuỳ chọn | Mặc định | |
|---|---|---|
| `--nguong` | `config('xml3176.vat_pham_kem_dvkt.nguong')` = 0.8 | Tỷ lệ gắn tối thiểu |
| `--toi-thieu` | `config(...toi_thieu)` = 5 | Số dòng BHYT trả tối thiểu của vật phẩm |
| `--tu-ngay` | rỗng (toàn bộ) | Giới hạn `ngay_yl` |
| `--thu` | | Chỉ in báo cáo, không ghi bảng |
| `--xuat=` | | Xuất Excel bảng sau khi học |

Thuật toán, cho mỗi `loai`:
1. Thống kê theo khoá khớp trên các dòng `t_bhtt > 0` (VTYT: `ma_vat_tu <> ''` trong XML3; thuốc: XML2): `n` tổng dòng, `lk` số dòng có `ma_dich_vu <> ''`.
2. Chọn vật phẩm có `n >= toi_thieu` và `lk / n >= nguong`.
3. Với vật phẩm được chọn, lấy **mọi** `ma_dich_vu` (chuẩn hoá) từng gắn → một dòng `HOC` mỗi cặp, `so_lan_gan` = số dòng.
4. Ghi (`updateOrCreate` theo khoá duy nhất): chỉ chạm dòng `nguon = HOC`; **giữ nguyên** `hieu_luc` và `ghi_chu` đã có; dòng `HOC` cũ không còn được chọn thì đặt `hieu_luc = false` với `ghi_chu = 'Học lại: dưới ngưỡng'` (trừ khi người dùng đã sửa `ghi_chu`). Dòng `TAY` không bao giờ bị sửa.
5. In báo cáo: số vật phẩm, số cặp, số dòng thêm/cập nhật/tắt.

Truy vấn phải chịu được bảng 1,4 triệu dòng: gom bằng SQL `GROUP BY`, không kéo dòng chi tiết về PHP.

**`php artisan xml3176:nap-vat-pham-kem-dvkt {tep}`**
- Đọc Excel cùng định dạng tệp xuất (cột: `loai, ma_vat_pham, ten_vat_pham, ma_dvkt, ten_dvkt, nguon, hieu_luc, ghi_chu`).
- Dòng có trong bảng → cập nhật `hieu_luc`, `ghi_chu`.
- Dòng chưa có → thêm với `nguon = TAY`.
- Kiểm `loai` hợp lệ, `ma_vat_pham`/`ma_dvkt` không rỗng; báo dòng lỗi, không dừng cả tệp.
- Không xoá dòng nào.

Config mới trong `config/xml3176.php`:

```php
'vat_pham_kem_dvkt' => [
    'nguong' => 0.8,   // ty le dong BHYT co lien ket ma_dich_vu; duoi nguong la vat tu/thuoc dung chung (bom kim, NaCl...)
    'toi_thieu' => 5,  // so dong toi thieu de hoc, loai ca nhieu kieu 2/3
],
```

### 3.3 Quy tắc kiểm tra

Đặt trong `Xml3176CompleteChecker` (cấp hồ sơ, `xmlType = 'XMLComplete'`), mỗi quy tắc là một `private function ...(string $ma_lk): Collection` và một dòng `merge` trong `checkErrors()`, theo mẫu `checkSecondSurgeryFullPayment`.

Bảng tra nạp một lần mỗi tiến trình (thuộc tính tĩnh/memo trong một lớp tra cứu `VatPhamKemDvktLookup`), chỉ dòng `hieu_luc = true`, dạng `[loai][khoa] => [ma_dvkt chuẩn hoá => ten_dvkt]`.

Dữ liệu dùng chung cho một hồ sơ: các dòng DVKT XML3 (`ma_vat_tu` rỗng/NULL) gom theo mã chuẩn hoá → `{tong_bhtt, tong_so_luong_bhyt (so_luong của dòng t_bhtt > 0), ten}`.

**R1 — `VTYT_KHONG_CO_DVKT_ME`, `THUOC_KHONG_CO_DVKT_ME`**
- Dòng xét: XML3 có `ma_vat_tu <> ''` (hoặc XML2), `t_bhtt > 0`, **`ma_dich_vu` rỗng**, khoá khớp có trong bảng tra.
- Lỗi khi không có mã nào trong danh sách mẹ của vật phẩm mà hồ sơ có DVKT với `tong_bhtt > 0`.
- Gộp một lỗi cho mỗi vật phẩm trong hồ sơ. Mô tả:
  - trường hợp không có mã mẹ nào trong hồ sơ: `"{ten} ({ma}) BHYT trả {tien}đ, YL {ngày đầu}: không có DVKT mẹ trong hồ sơ. Cần một trong: {ma_dvkt ten_dvkt}, …"`
  - trường hợp có mã mẹ nhưng `tong_bhtt = 0`: `"… DVKT mẹ {ma} {ten} có trong hồ sơ nhưng quỹ BHYT không thanh toán."`
  - Danh sách "cần một trong" cắt tối đa 5 mã, thêm "và N mã khác".
- Không ràng buộc ngày.

**R2 — `GOI_VTYT_KHONG_KHOP`, `THUOC_DVKT_ME_KHONG_KHOP`**
- Dòng xét: VTYT/thuốc `t_bhtt > 0`, **`ma_dich_vu` khác rỗng**. Không cần bảng tra.
- Lỗi khi (theo `ma_dich_vu` chuẩn hoá):
  - hồ sơ không có DVKT mã đó; hoặc
  - có nhưng `tong_bhtt = 0`; hoặc
  - (chỉ VTYT) số giá trị `goi_vtyt` khác nhau của mã đó > `tong_so_luong_bhyt`.
- Gộp một lỗi cho mỗi `ma_dich_vu`; mô tả nêu rõ nhánh vi phạm, tên vật phẩm, số gói, số lần DVKT.

**Đăng ký mã lỗi:** seeder `Xml3176ErrorCatalogVatPhamKemDvktSeeder` (`updateOrCreate`, `xml = 'XMLComplete'`, `critical_error = true`, `is_check = true`), kèm mô tả nghiệp vụ dẫn chiếu ghi chú "Chưa bao gồm" của QĐ giá. Điều chỉnh mức độ qua màn danh mục lỗi sẵn có.

Mã lưu thực tế có tiền tố `XMLComplete_` (`generateErrorCode` = `$this->xmlType . '_' . $key`), ví dụ `XMLComplete_VTYT_KHONG_CO_DVKT_ME`. Seeder và test dùng đúng mã đầy đủ này.

### 3.4 Ngoài phạm vi

- Sinh nhóm vật phẩm từ ghi chú QĐ 1519 (phân tích văn bản) — đợt sau.
- Màn hình quản trị bảng tra — đợt này duyệt bằng Excel.
- Ghi chú chung "Chưa bao gồm thuốc và oxy": không tạo ràng buộc, không kiểm.
- Ràng buộc ngày giữa vật phẩm và DVKT mẹ ở R1.

## 4. Kiểm thử

Unit test theo mẫu `tests/Support/Xml3176RuleTestSupport.php` (SQLite; **cấm `RefreshDatabase`**; ép `DB_HOST=127.0.0.1` theo chốt an toàn CSDL test).

**Khoá & chuẩn hoá**
- Mã VTYT đầy đủ → khoá theo mã; mã nhóm chung → khoá mã + tên; tên khác hoa/thường/khoảng trắng vẫn trùng khoá.
- `12.0448.1187_GT` → `12.0448.1187`.

**R1**
- Vật phẩm trong bảng, không có DVKT mẹ → lỗi, mô tả "không có DVKT mẹ".
- DVKT mẹ có, `t_bhtt = 0` → lỗi, mô tả "quỹ BHYT không thanh toán".
- DVKT mẹ có BHYT trả → không lỗi.
- DVKT mẹ dạng `_GT` → khớp, không lỗi.
- Vật phẩm `t_bhtt = 0` → không lỗi.
- Vật phẩm không có trong bảng / dòng `hieu_luc = false` → không lỗi.
- Mã nhóm chung trùng mã nhưng khác tên → không khớp, không lỗi.
- Dòng có `ma_dich_vu` → R1 bỏ qua (thuộc R2).
- Thuốc XML2: một ca lỗi, một ca không lỗi.

**R2**
- DVKT mẹ thiếu → lỗi; có nhưng `t_bhtt = 0` → lỗi; G1, G2 với một DVKT `so_luong = 1` → lỗi; G1, G2 với `so_luong = 2` → không lỗi.

**Lệnh học**
- Vật phẩm dưới ngưỡng hoặc dưới mức tối thiểu → không vào bảng.
- Dòng `TAY` không bị sửa; `hieu_luc = false` do người dùng đặt được giữ qua lần học lại; dòng `HOC` rơi dưới ngưỡng bị tắt.
- `--thu` không ghi.

**Lệnh nạp**
- Thêm dòng mới thành `TAY`; cập nhật `hieu_luc`; dòng thiếu mã bị báo lỗi, các dòng khác vẫn nạp.

**Nghiệm thu trên CSDL thật (chỉ đọc)**
- `xml3176:hoc-vat-pham-kem-dvkt --thu` báo khoảng 356 VTYT / 9 thuốc với ngưỡng mặc định.
- Chạy quy tắc trên các hồ sơ đã nêu ở §2: 4 ca lỗi thật phải bị bắt; `000007321137` không bị bắt.
- Kiểm tay toàn bộ 13 hồ sơ bị đánh dấu trước khi bật trên prod.

## 5. Triển khai

1. `php artisan migrate`
2. `php artisan db:seed --class=Xml3176ErrorCatalogVatPhamKemDvktSeeder`
3. `php artisan xml3176:hoc-vat-pham-kem-dvkt --xuat=storage/app/vat_pham_kem_dvkt.xlsx` → gửi người dùng duyệt
4. `php artisan xml3176:nap-vat-pham-kem-dvkt storage/app/vat_pham_kem_dvkt.xlsx`
5. `php artisan config:cache`
6. Chạy lại kiểm hồ sơ cho các tháng cần rà soát; học lại định kỳ (thủ công) khi HIS có vật phẩm mới.

## 6. Tệp dự kiến

| Tệp | Thay đổi |
|---|---|
| `database/migrations/2026_10_13_*_create_xml3176_vat_pham_kem_dvkt_table.php` | Mới |
| `app/Models/BHYT/Xml3176VatPhamKemDvkt.php` | Mới |
| `app/Services/Xml3176/VatPhamKemDvktKey.php` | Mới — khoá khớp, chuẩn hoá mã |
| `app/Services/Xml3176/VatPhamKemDvktLookup.php` | Mới — nạp bảng tra |
| `app/Services/Xml3176/VatPhamKemDvktLearner.php` | Mới — thuật toán học |
| `app/Console/Commands/Xml3176HocVatPhamKemDvkt.php` | Mới |
| `app/Console/Commands/Xml3176NapVatPhamKemDvkt.php` | Mới |
| `app/Services/Xml3176CompleteChecker.php` | Thêm R1, R2 |
| `config/xml3176.php` | Thêm `vat_pham_kem_dvkt` |
| `database/seeds/Xml3176ErrorCatalogVatPhamKemDvktSeeder.php` | Mới |
| `tests/Unit/Xml3176/...` | Test như §4 |
