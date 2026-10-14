# Quy tắc R3: DVKT bắt buộc kèm vật phẩm nhưng hồ sơ không có vật phẩm (XML3176)

**Ngày:** 2026-10-14
**Module:** XML3176 — tiền giám định
**Tiền đề:**
- `docs/superpowers/specs/2026-10-13-xml3176-vat-pham-kem-dvkt-design.md` — bảng tra vật phẩm → DVKT mẹ, quy tắc R1/R2.
- `docs/superpowers/specs/2026-10-13-xml3176-vat-pham-kem-dvkt-quan-tri-design.md` — màn quản trị, học nền.
- Bản sửa 14/10/2026 (`17b33427`, `2362ec1c`): chỉ học từ dòng BHYT trả; bỏ DVKT mẹ sai (mã thuốc, "không tiêm/không có thuốc cản quang").

**Trạng thái:** Design đã duyệt, chờ lập plan

## 1. Bài toán

R1/R2 kiểm một chiều: **có vật phẩm → phải có DVKT mẹ**. Chiều ngược chưa kiểm: hồ sơ được BHYT trả một DVKT mà giá **chưa bao gồm** vật phẩm (ví dụ "Chụp CLVT … có tiêm thuốc cản quang"), nhưng không có vật phẩm đó. Nguyên nhân thường gặp:

- chụp không cản quang mà kê mã có cản quang → **thanh toán sai giá** (18.0219.0041 = 522.960đ so với 18.0219.0040 = 401.920đ);
- vật phẩm kê dịch vụ / quỹ không trả;
- vật phẩm không xuất ra XML.

Không áp được cho mọi DVKT mẹ: MRI "có tiêm chất tương phản" chỉ 37% hồ sơ có thuốc tương phản (giá nhiều khả năng đã bao gồm thuốc) — áp cứng thì báo nhầm phần lớn. Danh sách DVKT bắt buộc phải **học từ dữ liệu**.

## 2. Đo trên CSDL thật (chỉ đọc, hồ sơ nạp từ 01/09/2026, bảng tra lượt học #4)

| DVKT | Hồ sơ BHYT trả | Thiếu thuốc cản quang | Tỷ lệ có kèm |
|---|---|---|---|
| 18.0219.0041 CLVT tầng trên ổ bụng có cản quang | 897 | 12 | 98,7% |
| 18.0192.0041 CLVT lồng ngực có tiêm cản quang | 624 | 5 | 99,2% |
| 18.0169.0042 CLVT mạch máu não | 548 | 6 | 98,9% |
| 18.0220.0041 CLVT bụng-tiểu khung | 590 | 3 | 99,5% |
| 18.0320.0065 MRI tầng bụng có tiêm chất tương phản | 344 | 218 | 36,6% |
| 18.0299.0065 MRI não–mạch não có tiêm chất tương phản | 215 | 133 | 38,1% |

Toàn nhóm DVKT mẹ của thuốc cản quang: 552 hồ sơ thiếu thuốc, phần lớn là MRI. Với ngưỡng §3.3 chỉ CLVT có cản quang vào danh sách → ước tính ~30 hồ sơ R3.

## 3. Dữ liệu và học

### 3.1 Bảng mới `xml3176_vpkd_dvkt_bat_buoc`

| Cột | Kiểu | Ý nghĩa |
|---|---|---|
| `id` | increments | |
| `ma_dvkt` | string(50) `utf8_bin`, unique | Mã đã chuẩn hoá (`chuanHoaMaDvkt`) |
| `ten_dvkt` | string(1024) null | |
| `so_ho_so` | integer | Số hồ sơ có DVKT được BHYT trả |
| `so_ho_so_co_kem` | integer | Trong đó có ≥1 vật phẩm cùng nhóm được BHYT trả |
| `ty_le` | decimal(5,4) null | `so_ho_so_co_kem / so_ho_so` |
| `nguon` | string(10) | `HOC` \| `TAY` |
| `hieu_luc` | boolean default true | |
| `ghi_chu` | string(1024) null | |
| `nguoi_sua_id`, `sua_luc` | null | Ghi vết như bảng tra |
| timestamps | | |

Model `App\Models\BHYT\Xml3176VpkdDvktBatBuoc` (hằng nguồn dùng lại của `Xml3176VatPhamKemDvkt`).

Cùng migration chèn mã lỗi `XMLComplete_DVKT_THIEU_VAT_PHAM_KEM` vào `xml3176_error_catalogs` (`xml = 'XMLComplete'`, `critical_error = false`, `is_check = true`, idempotent: chỉ chèn nếu chưa có) — `update.bat` chạy migrate nên không cần seeder thủ công, và mã không bị mặc định "nghiêm trọng" (`getCriticalErrorStatus` trả `true` khi mã chưa có trong danh mục).

### 3.2 Nhóm vật phẩm của một DVKT

`nhóm(D)` = mọi vật phẩm (khoá khớp `VatPhamKemDvktKey::khoa`) có dòng **đang hiệu lực** trong `xml3176_vat_pham_kem_dvkt` với `ma_dvkt = D` (cả `HOC` lẫn `TAY`). Ví dụ `nhóm(18.0219.0041)` = {Xenetix, Omnipaque, Iopamiro, …}.

### 3.3 Học (trong cùng lượt học, sau khi đã ghi bảng tra)

`VatPhamKemDvktLearner::hocDvktBatBuoc(float $nguong, int $toiThieu, $tuNgay = null): array` — đọc bảng tra **vừa ghi** (dòng hiệu lực), với mọi DVKT có trong bảng tra:

1. Hồ sơ có DVKT `D` được BHYT trả: dòng XML3 `ma_vat_tu` rỗng, `chuanHoaMaDvkt(ma_dich_vu) = D`, `t_bhtt > 0` → tập `HS(D)`.
2. Vật phẩm BHYT trả trong các hồ sơ đó: XML3 (`ma_vat_tu <> ''`) và XML2, `t_bhtt > 0`, gom `GROUP BY ma_lk, mã, tên` → khoá khớp.
3. `so_ho_so_co_kem(D)` = số hồ sơ trong `HS(D)` có ≥1 vật phẩm thuộc `nhóm(D)`.
4. Chọn `D` khi `|HS(D)| ≥ toi_thieu` **và** `ty_le ≥ nguong`.

Gom bằng SQL `GROUP BY`; lọc `ma_dich_vu` theo lô (≤ 500 mã) để chịu được 1,4 triệu dòng XML3.

`ghiDvktBatBuoc(array $dong): array` — cùng ngữ nghĩa `ghi()` của bảng tra: thêm `HOC` mới; cập nhật số liệu dòng `HOC` (giữ `hieu_luc`/`ghi_chu` người dùng đặt); tắt dòng `HOC` không còn đạt với ghi chú `Học lại: dưới ngưỡng`, tự bật lại khi đạt trở lại; không đụng `TAY`. Trả `['them','cap_nhat','tat','bat_lai']`.

`VatPhamKemDvktHocLaiService::thucHien` gọi hai bước trên trong cùng giao dịch với `ghi()`/`ghiUngVien()`; `ket_qua` lượt học thêm `so_dvkt_bat_buoc` (số dòng hiệu lực sau khi ghi).

Config mới trong `xml3176.vat_pham_kem_dvkt`:

```php
'dvkt_bat_buoc_nguong' => 0.9,     // ty le ho so co kem vat pham
'dvkt_bat_buoc_toi_thieu' => 20,   // so ho so toi thieu
```

## 4. Quy tắc R3 — `XMLComplete_DVKT_THIEU_VAT_PHAM_KEM`

Trong `VatPhamKemDvktRule` (lớp thuần), dữ liệu bảng tra mở rộng:

- `VatPhamKemDvktLookup::bang()` trả thêm khoá `'DVKT_BAT_BUOC' => [ma_dvkt => ten_dvkt]` (dòng hiệu lực của bảng mới; bảng chưa migrate → rỗng) và `'TEN_VAT_PHAM' => [khoa => ten]` để nêu tên trong thông điệp.

Với mỗi `D` trong `DVKT_BAT_BUOC` mà hồ sơ có DVKT `D` **tổng `t_bhtt` > 0**:

- Lấy `nhóm(D)` từ bảng tra (đảo `VTYT`/`THUOC` → `khoa ⇒ {ma_dvkt}`).
- Hồ sơ có ≥1 dòng vật phẩm (XML3 VTYT hoặc XML2) thuộc `nhóm(D)` với **tổng `t_bhtt` > 0** → không lỗi.
- Ngược lại → một lỗi cho `D`:
  - có vật phẩm thuộc nhóm nhưng quỹ không trả: `"{D} {ten} BHYT trả {tien}, YL {ngày}: có {ten vật phẩm} nhưng quỹ BHYT không thanh toán vật phẩm."`
  - không có: `"{D} {ten} BHYT trả {tien}, YL {ngày}: hồ sơ không có vật phẩm đi kèm (cần một trong: {tối đa 5 tên} …). Kiểm tra kê đúng mã DVKT (vd có/không cản quang)."`
- `error_name`: `DVKT bắt buộc kèm vật phẩm nhưng không có vật phẩm`.

Mã `_GT` chuẩn hoá như R1/R2. Không ràng buộc ngày.

## 5. Màn quản trị

Màn **DM vật phẩm kèm DVKT mẹ** thêm tab **"DVKT bắt buộc kèm vật phẩm"**:

- DataTable server-side: Mã DVKT, Tên, Số HS, Số HS có kèm, Tỷ lệ (%), Nguồn, Hiệu lực (công tắc — tắt bắt buộc lý do, như tab Bảng tra), Ghi chú (bút chì), Người sửa / lúc sửa, Xoá (chỉ `TAY`).
- Lọc: Nguồn, Hiệu lực.
- Nút **Thêm DVKT tay**: chọn mã DVKT (select2 từ danh mục DVKT) + ghi chú. Từ chối (422) nếu `nhóm(D)` rỗng: `DVKT chưa là DVKT mẹ của vật phẩm nào trong bảng tra — không có gì để kiểm`. Trùng → 422 `Đã có`.
- Nhãn trạng thái lượt học thêm `, DVKT bắt buộc {n}`.

Route mới trong nhóm `category-bhyt.vat-pham-kem-dvkt.` (cùng quyền `checkrole:category-manager`): `bat-buoc-datatable`, `bat-buoc-hieu-luc`, `bat-buoc-ghi-chu`, `bat-buoc-xoa`, `bat-buoc-them`. Thao tác dùng lại `VatPhamKemDvktQuanTri` (thêm method `datHieuLucBatBuoc`, `datGhiChuBatBuoc`, `xoaBatBuoc`, `themBatBuoc`) — cùng thông điệp lỗi đã có (`Cần ghi lý do khi tắt`, …).

## 6. Ngoài phạm vi

- Xuất/nhập Excel cho danh sách DVKT bắt buộc (bảng nhỏ, sửa trên màn hình).
- Kiểm số lượng (vd thuốc cản quang đủ liều cho số lần chụp).
- Ràng buộc ngày giữa DVKT và vật phẩm.

## 7. Kiểm thử

SQLite bộ nhớ (`bootXml3176Sqlite`), cấm `RefreshDatabase`, chạy `DB_HOST=127.0.0.1 vendor/bin/phpunit`.

- **Học:** DVKT đạt ngưỡng vào danh sách, số liệu đúng; DVKT dưới tỷ lệ (kiểu MRI 37%) bị loại; dưới số hồ sơ tối thiểu bị loại; chỉ đếm dòng `t_bhtt > 0` cả phía DVKT lẫn vật phẩm; `_GT` chuẩn hoá; VTYT mã nhóm chung khớp theo mã+tên; `ghiDvktBatBuoc` giữ `TAY`, giữ dòng người dùng tắt, tự tắt/tự bật lại.
- **Lượt học:** `ket_qua` có `so_dvkt_bat_buoc`; lỗi giữa chừng rollback cả bảng mới.
- **R3:** có DVKT + vật phẩm BHYT → không lỗi; không vật phẩm → lỗi "không có"; có vật phẩm quỹ không trả → lỗi "quỹ không thanh toán"; DVKT quỹ không trả → im lặng; DVKT không trong danh sách → im lặng; dòng danh sách tắt → im lặng; bảng chưa migrate → im lặng.
- **Migration:** mã lỗi được chèn `critical_error = false`, chạy lại không nhân đôi.
- **Màn hình:** route có `auth` + `checkrole:category-manager`; thêm DVKT có nhóm rỗng → 422; trùng → 422; tắt thiếu lý do → 422; xoá `HOC` → 422; blade biên dịch được.
- **Nghiệm thu chỉ đọc trên CSDL thật:** học trong bộ nhớ → danh sách gồm CLVT có cản quang, **không** có MRI có tiêm chất tương phản; chạy R3 trên hồ sơ từ 01/09 → ~30 hồ sơ, kiểm tay mẫu.

## 8. Triển khai

`update.bat` tự migrate (bảng mới + mã lỗi). Sau đó vào màn quản trị bấm **Học lại** một lần để sinh danh sách DVKT bắt buộc; R3 có hiệu lực sau tối đa 10 phút. Cập nhật `docs/trien-khai-xml3176-vat-pham-kem-dvkt.md`.

## 9. Tệp dự kiến

| Tệp | |
|---|---|
| `database/migrations/2026_10_15_*_create_xml3176_vpkd_dvkt_bat_buoc_table.php` | Mới (bảng + mã lỗi) |
| `app/Models/BHYT/Xml3176VpkdDvktBatBuoc.php` | Mới |
| `app/Services/Xml3176/VatPhamKemDvktLearner.php` | `hocDvktBatBuoc`, `ghiDvktBatBuoc` |
| `app/Services/Xml3176/VatPhamKemDvktHocLaiService.php` | Gọi hai bước, `so_dvkt_bat_buoc`, mô tả lượt học |
| `app/Services/Xml3176/VatPhamKemDvktLookup.php` | `DVKT_BAT_BUOC`, `TEN_VAT_PHAM` |
| `app/Services/Xml3176/VatPhamKemDvktRule.php` | R3 |
| `app/Services/Xml3176/VatPhamKemDvktQuanTri.php` | Thao tác bảng mới |
| `app/Http/Controllers/Category/VatPhamKemDvktController.php`, `routes/web.php` | 5 route |
| `resources/views/category/bhyt/vat_pham_kem_dvkt.blade.php` | Tab mới |
| `config/xml3176.php` | 2 khoá |
| `tests/Unit/Xml3176/VatPhamKemDvkt/*` | Test |
| `docs/trien-khai-xml3176-vat-pham-kem-dvkt.md` | Cập nhật |
