# Triển khai prod: quy tắc VTYT/thuốc ngoài giá phải đi kèm DVKT mẹ

**Áp dụng cho:** commit `8cd8cb2b` trở về sau trên nhánh `main`
**Thiết kế:** `docs/superpowers/specs/2026-10-13-xml3176-vat-pham-kem-dvkt-design.md`
**Người thực hiện:** quản trị máy chủ QLBV, mở `cmd` tại thư mục dự án trên máy chủ.

---

## 0. Tóm tắt trước khi làm

Quy tắc mới gồm hai phần, bật theo hai cách khác nhau:

| Phần | Bắt lỗi gì | Khi nào bắt đầu chạy |
|---|---|---|
| **R2** — mã `XMLComplete_GOI_VTYT_KHONG_KHOP`, `XMLComplete_THUOC_DVKT_ME_KHONG_KHOP` | Dòng VTYT/thuốc **đã ghi** `MA_DICH_VU`/`GOI_VTYT` nhưng DVKT mẹ thiếu, quỹ không trả, hoặc số gói vượt số lần DVKT | **Tự động** ngay khi `update.bat` kéo mã mới về. Không cần bảng tra. Đo ngày 13/10/2026: 0 ca. |
| **R1** — mã `XMLComplete_VTYT_KHONG_CO_DVKT_ME`, `XMLComplete_THUOC_KHONG_CO_DVKT_ME` | VTYT/thuốc **không ghi** DVKT mẹ, quỹ trả, nhưng hồ sơ không có DVKT mẹ được quỹ trả (quên kê, hoặc BN tự trả DVKT) | **Chỉ sau khi** học và nạp bảng tra (Bước 3–5). Bảng còn rỗng thì R1 im lặng. |

`update.bat` chạy tự động mỗi giờ và đã tự làm: `git pull` → `php artisan migrate --force` (tạo bảng `xml3176_vat_pham_kem_dvkt`) → `config:cache` → khởi động lại các dịch vụ NSSM. **Bạn không phải làm lại những việc đó.** Phần bạn phải làm tay là Bước 2–6.

Thời gian ước tính: 30–60 phút, trong đó phần lâu nhất là duyệt Excel.

---

## Làm bằng màn hình (khuyến nghị, từ commit có màn quản trị)

Menu **Quản lý danh mục → BHYT → DM vật phẩm kèm DVKT mẹ** (quyền `category-manager`) thay cho Bước 3–5:

| Việc | Trên màn hình |
|---|---|
| Học bảng tra (Bước 3) | Nút **Học lại** — chạy nền qua dịch vụ `QLBV JobXuatTepXml3176` 2–5 phút; nhãn cạnh nút hiện kết quả. Đang có lượt chạy thì nút khoá. Lượt quá 60 phút tự chuyển "Lỗi: Treo quá 60 phút". |
| Duyệt (Bước 4) | Tab **Bảng tra**: lọc, tắt cặp sai (bắt buộc ghi lý do), sửa ghi chú, xoá dòng TAY. Nút **Thêm dòng tay**: chọn mã → bảng tên thật trên XML 12 tháng → chọn tên (bắt buộc với VTYT mã nhóm chung) → chọn DVKT mẹ. Tab **Ứng viên chờ duyệt**: tick DVKT đúng → **Thêm thành dòng tay**. |
| Excel (Bước 3.2, 5) | Nút **Xuất Excel** / **Nhập Excel** (kéo-thả, có tệp lỗi theo dòng). |

Bật/tắt và thêm dòng trên màn hình có hiệu lực với quy tắc kiểm tra sau tối đa 10 phút (thời hạn bộ nhớ đệm của bảng tra). Bật lại một dòng HOC đã bị lệnh học tự tắt (dưới ngưỡng) sẽ chuyển dòng đó thành TAY để lần học sau không tắt lại.

Mọi thao tác trên màn hình ghi người và thời điểm sửa cuối (cột "Người sửa"). Dòng lệnh ở Bước 3–5 vẫn dùng được làm phương án dự phòng; lệnh học (không `--thu`) cũng cập nhật lịch sử lượt học và tab ứng viên.

Sau khi `update.bat` chạy, kiểm `php artisan migrate:status | findstr "vpkd them_nguoi_sua_vao_xml3176"` phải thấy đúng 3 dòng, cả 3 đều `Y`: 2 migration `create_xml3176_vpkd_*` và `them_nguoi_sua_vao_xml3176_vat_pham_kem_dvkt`.

---

## Bước 1 — Xác nhận máy chủ đã nhận mã mới

```bat
git log --oneline -1
```

Kết quả phải là `8cd8cb2b` hoặc commit mới hơn. Nếu còn cũ, chạy tay `update.bat` (hoặc chờ lượt tự động kế tiếp).

Kiểm tra bảng đã được tạo:

```bat
php artisan migrate:status | findstr vat_pham_kem_dvkt
```

Dòng `2026_10_13_090000_create_xml3176_vat_pham_kem_dvkt_table` phải có cột `Ran?` là `Y`. Nếu là `N` hoặc không có dòng nào, chạy `php artisan migrate --force` và đọc thông báo lỗi.

---

## Bước 2 — Đăng ký 4 mã lỗi vào danh mục lỗi

Seeder là class mới, Laravel 5.5 phải nạp lại autoload mới thấy:

```bat
composer dump-autoload
php artisan db:seed --class=Xml3176ErrorCatalogVatPhamKemDvktSeeder
```

Kiểm tra: vào màn **Danh mục lỗi Xml 3176**, lọc `XMLComplete_` — phải thấy 4 mã mới, cùng ở mức **nghiêm trọng**, **đang kiểm**.

**Nếu máy chủ không có `composer`:** bỏ qua bước này. Mã lỗi tự đăng ký vào danh mục ở lần đầu phát sinh (mặc định nghiêm trọng), chỉ thiếu phần mô tả nghiệp vụ.

> **Chỉ chạy seeder một lần.** Chạy lại sẽ đặt lại `is_check` và `critical_error` về bật — nếu bạn đã tắt mã nào trên màn danh mục lỗi thì nó bị bật lại.

---

## Bước 3 — Học bảng tra từ dữ liệu

### 3.1 Chạy thử (không ghi gì)

```bat
php artisan xml3176:hoc-vat-pham-kem-dvkt --thu
```

Lệnh chỉ đọc, chạy 2–5 phút trên khoảng 1,4 triệu dòng XML3. Kết quả mong đợi (đo trên CSDL ngày 13/10/2026):

```
VTYT: ~207 vật phẩm, ~539 cặp
THUOC: ~6 vật phẩm, ~122 cặp
Chế độ thử: không ghi bảng.
```

Chênh vài đơn vị là bình thường vì dữ liệu tăng mỗi ngày. Nếu ra **0 vật phẩm** thì dừng lại — kiểm tra kết nối CSDL trong `.env`.

### 3.2 Chạy thật và xuất Excel để duyệt

```bat
php artisan xml3176:hoc-vat-pham-kem-dvkt --xuat=vat_pham_kem_dvkt.xlsx
```

Lệnh ghi bảng, in `Ghi: thêm N, cập nhật 0, tắt 0, bật lại 0` và `Đã xuất: ...\storage\app\vat_pham_kem_dvkt.xlsx`.

> - `--xuat` nhận **tên tệp**, tệp luôn nằm trong `storage\app\`. Đừng ghi `--xuat=storage/app/...` (tệp sẽ rơi vào `storage\app\storage\app\`).
> - **Không** dùng `--thu` cùng `--xuat` để lấy tệp duyệt: tệp đó có cột `nguon` rỗng, nạp lại sẽ biến mọi dòng thành dòng tay (`TAY`) và lệnh học không còn quản lý được chúng.
> - **Ngưỡng mặc định** (`config/xml3176.php`, mục `vat_pham_kem_dvkt`): tỷ lệ gắn ≥ 0,8, tối thiểu 5 dòng. Không đổi nếu chưa có lý do; đổi xong phải `php artisan config:cache`.

---

## Bước 4 — Duyệt tệp Excel

Chép `storage\app\vat_pham_kem_dvkt.xlsx` ra máy làm việc và mở bằng Excel. Mỗi dòng là một cặp **vật phẩm → DVKT mẹ được phép**.

| Cột | Ý nghĩa | Được sửa? |
|---|---|---|
| `loai` | `VTYT` hoặc `THUOC` | Chỉ khi thêm dòng mới |
| `ma_vat_pham`, `ten_vat_pham` | Mã + tên VTYT (XML3) hoặc thuốc (XML2) | Chỉ khi thêm dòng mới |
| `ma_dvkt`, `ten_dvkt` | DVKT mẹ được phép | Chỉ khi thêm dòng mới |
| `nguon` | `HOC` (tự học) / `TAY` (bổ sung tay) | Không — lệnh nạp tự đặt |
| `hieu_luc` | `1` = dùng, `0` = tắt | **Có** |
| `so_lan_gan`, `ty_le_gan` | Số lần HIS gắn cặp này; tỷ lệ gắn của vật phẩm | Không — chỉ để tham khảo |
| `ghi_chu` | Lý do sửa/tắt | **Có** |

### 4.1 Tắt các cặp sai

Lọc các dòng có `so_lan_gan` nhỏ (1–3) và xem DVKT mẹ có hợp lý không. Cặp sai thì đặt `hieu_luc = 0`, ghi lý do vào `ghi_chu`.

Ví dụ đã thấy khi rà soát: thuốc cản quang **Xenetix (40.642)** có DVKT mẹ `18.0149.0040` *"Chụp CLVT sọ não **không tiêm** thuốc cản quang"*. Cặp này sai về chuyên môn — để nguyên thì hồ sơ có Xenetix mà chỉ chụp CLVT không cản quang sẽ lọt. → `hieu_luc = 0`.

### 4.2 Thêm dòng tay cho vật phẩm chưa học được

Vật phẩm mà HIS chưa từng gắn gói thì lệnh học không biết. Thêm một dòng mới cho mỗi cặp:

- `loai`, `ma_vat_pham`, `ten_vat_pham`, `ma_dvkt`, `ten_dvkt` theo đúng mã trên XML;
- `nguon` để trống, `hieu_luc = 1`;
- **VTYT mã nhóm chung** (≤ 3 đoạn, ví dụ `N08.00.150`) **bắt buộc có `ten_vat_pham`** đúng như trên XML — hệ thống khớp theo cả mã lẫn tên.

### 4.3 Bắt buộc về định dạng

- Cột mã (`ma_vat_pham`, `ma_dvkt`) phải ở **định dạng Text**. Gõ `40.540` vào ô kiểu số sẽ thành `40.54` → lệnh nạp báo lỗi dòng đó.
- **Không xoá dòng** để tắt — lệnh nạp không xoá gì cả. Muốn tắt thì đặt `hieu_luc = 0`.
- Giữ nguyên dòng tiêu đề (dòng 1).

Lưu tệp, chép lại vào `storage\app\vat_pham_kem_dvkt.xlsx` trên máy chủ (ghi đè được).

---

## Bước 5 — Nạp tệp đã duyệt

```bat
php artisan xml3176:nap-vat-pham-kem-dvkt storage\app\vat_pham_kem_dvkt.xlsx
```

Kết quả: `Thêm X, cập nhật Y, lỗi Z`, kèm danh sách `Dòng N: lý do` nếu có lỗi.

- `lỗi 0` → xong.
- Có lỗi → sửa đúng các dòng được báo trong Excel rồi chạy lại lệnh. Chạy lại an toàn: dòng đã nạp chỉ được cập nhật, không nhân đôi. Lệnh trả mã thoát 1 khi còn dòng lỗi.

> `update.bat` có `git clean -df` nhưng **không** xoá `storage\` (thư mục bị git bỏ qua), nên tệp Excel không mất khi máy chủ tự cập nhật.

---

## Bước 6 — Chờ worker nhận bảng tra

Không cần khởi động lại dịch vụ. Worker `QLBV JobXml3176` tự nạp lại bảng tra **sau tối đa 10 phút**.

Muốn áp dụng ngay:

```bat
nssm restart "QLBV JobXml3176"
```

---

## Bước 7 — Kiểm tra sau triển khai

1. **Bảng tra có dữ liệu:** chạy `php artisan tinker`, gõ dòng dưới rồi Enter, sau đó gõ `exit`:
   ```php
   App\Models\BHYT\Xml3176VatPhamKemDvkt::where('hieu_luc', 1)->count()
   ```
   Phải lớn hơn 0 (khoảng 660 dòng nếu chưa tắt dòng nào).
2. **Hồ sơ mới có bị kiểm:** sau 1–2 ngày nạp XML, mở màn kết quả lỗi XML3176, lọc mã `XMLComplete_VTYT_KHONG_CO_DVKT_ME` và `XMLComplete_THUOC_KHONG_CO_DVKT_ME`. Ước lượng theo lần quét ngày 13/10/2026 (13 hồ sơ trong khoảng 22/09–12/10): khoảng **3–5 hồ sơ mỗi tuần**. Nhiều hơn hẳn (hàng chục/ngày) là dấu hiệu bảng tra có cặp sai → quay lại Bước 4.
3. **Kiểm tay** vài hồ sơ đầu tiên: mở hồ sơ, xem DVKT mẹ có thật sự thiếu hoặc BN tự trả không. Tệp `storage/app/kiem_tay_vat_pham_kem_dvkt_13_ho_so.xlsx` (máy dev) là mẫu 13 hồ sơ đã rà ngày 13/10/2026.

> **Hồ sơ đã nạp trước khi triển khai không tự kiểm lại.** Đừng nạp lại hàng loạt XML cũ chỉ để kiểm: nạp lại sẽ kéo theo chuỗi xuất → ký → gửi cổng. Hồ sơ cũ cần rà soát thì dùng danh sách kiểm tay.

---

## Vận hành định kỳ

- **Học lại mỗi tháng** (hoặc khi HIS có VTYT/thuốc mới): làm lại Bước 3.2 → 4 → 5. Lần học sau:
  - không đụng dòng `TAY`;
  - giữ nguyên dòng bạn đã tắt (`hieu_luc = 0` kèm ghi chú của bạn);
  - tự tắt dòng `HOC` rơi dưới ngưỡng (ghi chú `Học lại: dưới ngưỡng`) và tự bật lại khi vượt ngưỡng trở lại.
- **Giữ cùng một cửa sổ học.** Mặc định học trên toàn bộ dữ liệu. Nếu dùng `--tu-ngay`, lần nào cũng dùng cùng giá trị — học với cửa sổ hẹp hơn sẽ tự tắt các dòng chỉ xuất hiện ngoài cửa sổ.
- Không chạy lệnh học/nạp vào đúng phút `update.bat` đang chạy (ứng dụng đang ở chế độ bảo trì).

---

## Tắt khẩn cấp / quay lui

Không cần quay lui mã. Chọn mức phù hợp:

| Tình huống | Cách làm |
|---|---|
| Một cặp vật phẩm–DVKT báo sai | Excel: `hieu_luc = 0` cho dòng đó → Bước 5 |
| Toàn bộ R1 báo sai | Màn **Danh mục lỗi Xml 3176**: tắt *kiểm* (`is_check`) 2 mã `XMLComplete_VTYT_KHONG_CO_DVKT_ME`, `XMLComplete_THUOC_KHONG_CO_DVKT_ME` |
| Muốn giữ báo lỗi nhưng không chặn hồ sơ | Màn danh mục lỗi: bỏ *nghiêm trọng* (`critical_error`) của mã tương ứng |
| R2 báo sai | Tắt *kiểm* 2 mã `XMLComplete_GOI_VTYT_KHONG_KHOP`, `XMLComplete_THUOC_DVKT_ME_KHONG_KHOP` |

Thay đổi trên màn danh mục lỗi có hiệu lực với các lượt kiểm sau đó; kết quả lỗi đã ghi của hồ sơ cũ không tự mất.

---

## Danh sách kiểm (in ra tích)

- [ ] B1. `git log` ≥ `8cd8cb2b`; `migrate:status` báo migration bảng tra đã chạy
- [ ] B2. `composer dump-autoload` + seeder; thấy 4 mã trên màn danh mục lỗi
- [ ] B3. `--thu` ra ~207 VTYT / ~6 thuốc; chạy thật + `--xuat=vat_pham_kem_dvkt.xlsx`
- [ ] B4. Duyệt Excel: tắt cặp sai (có Xenetix ↔ CLVT không tiêm), thêm dòng tay nếu cần, cột mã định dạng Text
- [ ] B5. Nạp: `lỗi 0`
- [ ] B6. Chờ 10 phút hoặc `nssm restart "QLBV JobXml3176"`
- [ ] B7. Đếm bảng tra > 0; theo dõi lỗi mới 1–2 ngày; kiểm tay vài hồ sơ đầu
