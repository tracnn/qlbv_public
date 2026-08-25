# Thiết kế: Module danh mục TT12/2026/BTC — import Excel, kiểm, ký số, gửi cổng BHXH

Ngày: 2026-08-25
Trạng thái: đã thống nhất, chờ lập kế hoạch thực thi

## 1. Mục tiêu

Cho phép cơ sở KCB nạp sáu danh mục theo TT12/2026/BTC từ tệp Excel, lưu vào cơ sở dữ
liệu, kiểm tính đúng sai, dựng XML, ký số và gửi lên Cổng tiếp nhận dữ liệu Hệ thống
thông tin giám định BHYT. Dữ liệu đã được cổng tiếp nhận trở thành nguồn cho bước kiểm
XML3176 đang có.

Tài liệu gốc: *Tài liệu hướng dẫn kỹ thuật việc gửi, nhận tài liệu, dữ liệu điện tử trên
Cổng tiếp nhận dữ liệu Hệ thống thông tin giám định BHYT* (ban hành kèm Quyết định
/QĐ-BHXH năm 2026). Sáu tệp Excel mẫu: `Mau_MAU_01.xlsx` … `Mau_MAU_06.xlsx`.

## 2. Phạm vi

**Trong phạm vi** — sáu dịch vụ gửi danh mục:

| Mẫu | Tên | `loaiHs` | Endpoint (đuôi sau `/api/DanhMucGW/`) |
|---|---|---|---|
| MAU_01 | Bộ phận chuyên môn KBCB BHYT | `70` | `GuiDanhMuc01_BPCMKBCB` |
| MAU_02 | Nhân lực thực hiện KBCB BHYT | `71` | `GuiDanhMuc02_NLKCB` |
| MAU_03 | Thuốc, máu, chế phẩm máu | `10` | `GuiDanhMuc03_DMTHUOC` |
| MAU_04 | Thiết bị y tế (vật tư y tế) | `11` | `GuiDanhMuc04_DMVTYT` |
| MAU_05 | Dịch vụ khám bệnh, chữa bệnh | `12` | `GuiDanhMuc05_DVKT` |
| MAU_06 | Thiết bị y tế thực hiện DVKT | `72` | `GuiDanhMuc06_DMTBYT` |

Máy chủ: `https://egw.baohiemxahoi.gov.vn`. Lấy phiên làm việc:
`https://egw.baohiemxahoi.gov.vn/api/token/take` — dùng lại `BHYTLoginService` đang có.

**Ngoài phạm vi**, ghi lại để không quên:

- Mục VIII của tài liệu — Hồ sơ tổng hợp Mẫu 01/BH (`loaiHs=5`,
  `/api/HoSoTongHop7980/GuiHoSoTongHop01BH`). Nguồn dữ liệu là XML3176 chứ không phải
  Excel; đã có sẵn `app/Exports/Xml3176Xml7980aExport.php` làm điểm khởi đầu.
- Mục IX — Hồ sơ điều chỉnh Mẫu 09/BH (`loaiHs=73`, `/api/HSDCTT12/...`).
- Đổi các checker XML3176 sang tra "dòng danh mục có hiệu lực tại ngày KCB" thay vì dòng
  mới nhất. Việc đổi khoá duy nhất ở thiết kế này mở đường cho nó, nhưng không làm ở đây.
- Nhập danh mục bằng cách quét thư mục qua lệnh console. Module này **chỉ** có một đường
  vào: màn hình import trong web app.

## 3. Quyết định đã chốt

| # | Quyết định | Lý do |
|---|---|---|
| Đ1 | Mở rộng sáu bảng danh mục sẵn có làm nơi lưu trạng thái hiện hành, thêm lớp hồ sơ gửi riêng | `Xml3176Xml2/3/4Checker` và `OrderCheck` **đã** tra cứu chính các bảng đó; tạo bảng mới song song sẽ phải viết thêm lớp đồng bộ hoặc sửa checker |
| Đ2 | Chỉ làm sáu mẫu danh mục | Sáu mẫu chung một khuôn nên làm một lần được cả sáu; mục VIII/IX có nguồn dữ liệu và khuôn XML khác hẳn |
| Đ3 | Nới khoá duy nhất của bảng danh mục để chứa được cả dòng cũ (đã đóng) lẫn dòng mới | TT12 quy định khi thay đổi thì gửi hai dòng: dòng cũ mang `DEN_NGAY`, dòng mới để trống `DEN_NGAY` |
| Đ4 | Một tệp Excel = một hồ sơ = một lần gửi | Nội dung gửi lên cổng đúng bằng nội dung tệp đã nạp; một `maGiaoDich` ứng với một hồ sơ, đối soát được |
| Đ5 | Kiểm theo bảng đặc tả trường của tài liệu + nhất quán nội bộ; không đối chiếu danh mục dùng chung Bộ Y tế | Đủ để cổng không trả mã 205; không phụ thuộc vào việc các danh mục dùng chung có được cập nhật hay không |
| Đ6 | Chỉ đồng bộ sang bảng danh mục **sau khi cổng trả `maKetQua = 200`** | Danh mục dùng để kiểm XML3176 phải đúng bằng thứ BHXH đã nhận, vì giám định sẽ so với chính bản đó |
| Đ7 | Tổ chức mã theo Registry + lớp đặc tả từng mẫu | Nghiệp vụ nạp/kiểm/dựng XML/gửi viết **một lần**, chạy dữ liệu từ registry; sáu bản cài riêng là sáu bản sẽ lệch nhau |

## 4. Kiến trúc

### 4.1 Luồng

```
Excel (.xlsx) qua màn hình import
   |  (1) Tt12Importer — nhận diện mẫu từ header, đọc theo lô
   v
tt12_ho_so + tt12_dong (+ tt12_dong_thuoc_px)        <- ẢNH CHỤP nguyên văn
   |  (2) CheckTt12Job -> Tt12Kiem
   v
tt12_loi — xem trên màn hình / xuất Excel lỗi
   |  (3) SignTt12Job: Tt12PhongBi dựng HSDANHMUC -> XMLSignService ký CHUKYDONVI
   v
XML đã ký (ghi tệp, đường dẫn lưu vào tt12_ho_so)
   |  (4) SubmitTt12Job -> Tt12SubmitService (DanhMucGW)
   v
maKetQua = 200 --(5)--> Tt12DongBoDanhMuc --> medicine_catalogs / service_catalogs / ...
                                                       |
                                                       v
                                    Xml3176Xml2/3/4Checker, OrderCheck (đã có sẵn)
```

### 4.2 Vì sao XML dựng từ `tt12_dong` chứ không từ bảng danh mục

Bảng danh mục là **trạng thái hợp nhất của nhiều lần gửi**; hồ sơ gửi là **một lần gửi cụ
thể**. Dựng XML từ truy vấn danh mục thì lần gửi lại (sau khi cổng trả 500, hoặc khi đối
soát) sẽ ra nội dung **khác** với nội dung đã ký lần đầu. Cùng một `maGiaoDich` mà hai nội
dung khác nhau là thứ không giải thích được với cơ quan BHXH. `CtdtPhongBi` đã ghi lại
đúng bài học này: tệp gốc không được giữ nên phải dựng lại được y nguyên từ CSDL.

### 4.3 Cây tệp

```
config/tt12.php                          hằng số giao thức: url, loaiHs, tên trường body,
                                         tên thẻ, bảng mã kết quả
config/organization.php  (khoá tt12)     bật/tắt gửi, thư mục XML đã ký, tên hàng đợi
config/filesystems.php   (đĩa exportTt12)

app/Services/Tt12/
  Tt12MauRegistry.php                    MAU_01..06 -> lớp   (điểm tra cứu DUY NHẤT)
  Mau/Mau01.php .. Mau06.php             đặc tả: thẻ, loaiHs, cột, danh mục đích
  Tt12DocExcel.php                       đọc .xlsx theo lô, chuẩn hoá ô
  Tt12Importer.php                       điểm vào DUY NHẤT
  Tt12LuuHoSo.php                        ghi tt12_ho_so + tt12_dong trong một transaction
  Tt12MaHoSo.php                         sinh mã hồ sơ nghiệp vụ
  Kiem/Tt12Kiem.php + Kiem/Luat/*.php    sinh tt12_loi
  Tt12PhongBi.php                        dựng HSDANHMUC + CHUKYDONVI rỗng (DOMDocument)
  Tt12SubmitService.php                  giao thức DanhMucGW
  Tt12DongBoDanhMuc.php                  200 -> upsert sang bảng catalog
  Tt12DuDieuKienGui.php
  Tt12HangDoi.php
  Tt12QuyetDinhGui.php
  Tt12DanhSach.php
  Tt12DetailTabs.php

app/Jobs/{CheckTt12Job, SignTt12Job, SubmitTt12Job}.php
app/Models/BHYT/Tt12/{Tt12HoSo, Tt12Dong, Tt12DongThuocPx, Tt12Loi, Tt12LichSuGui}.php
app/Exports/{Tt12DanhSachExport, Tt12LoiExport}.php
app/Http/Controllers/BHYT/BHYTTt12Controller.php
resources/views/bhyt/tt12/*
database/migrations/2026_08_25_*
```

**Dùng lại nguyên vẹn, không sửa:** `XMLSignService`, `BHYTLoginService`,
`App\Services\Import\GhiTheoLo`, `ExcelColumnMapper`.

**Không dùng lại `BHYTXmlSubmitService`:** dù khuôn giống (xác thực ở header, body
form-urlencoded), tên trường body khác (`loaiHs`/`maCSKCB`/`fileHsBase64` so với
`loaiHoSo`/`maCSKCB`/`fileHSBase64`) và mã lỗi TT12 nằm trong thân phản hồi. Ép chung một
hàm sẽ lặp lại đúng cái bẫy mà `CtdtSubmitService` đã ghi chú.

### 4.4 Đặc tả mẫu — thẻ XML

Bất biến quan trọng: **thứ tự cột trong tệp Excel mẫu trùng đúng thứ tự thẻ trong XML** ở
cả sáu mẫu. Lớp `Mau0x` khai một danh sách cột duy nhất, dùng cho cả đọc Excel lẫn dựng XML.

| Mẫu | Thẻ danh sách | Thẻ dòng | Số cột | Danh mục đích |
|---|---|---|---|---|
| MAU_01 | `DANHSACH_DMBOPHANCHUYENMON` | `DMBOPHANCHUYENMON` | 11 | `department_bed` |
| MAU_02 | `DANHSACH_DMNHANLUCKBCB` | `DMNHANLUCKBCB` | 24 | `medical_staff` |
| MAU_03 | `DANHSACH_DMTHUOCMAUCHEPHAMMAU` | `DMTHUOCMAUCHEPHAMMAU` | 36 | `medicine` |
| MAU_04 | `DSACH_TBYT` | `DM_TBYT` | 26 | `medical_supply` |
| MAU_05 | `DANHSACH_DMDICHVUKBCB` | `DMDICHVUKBCB` | 15 + bảng con | `service` |
| MAU_06 | `DSACH_TBYTTHDV` | `DM_TBYTTHDV` | 14 | `equipment` |

Lưu ý MAU_04 và MAU_06 dùng tiền tố `DSACH_` chứ không phải `DANHSACH_`, và tên thẻ dòng
có gạch dưới (`DM_TBYT`, `DM_TBYTTHDV`). Bốn mẫu còn lại theo quy ước `DANHSACH_` +
`DM...`. Đây là điểm dễ viết nhầm theo quán tính, nên tên thẻ khai tường minh trong từng
lớp `Mau0x`, không sinh bằng ghép chuỗi.

MAU_05 có bảng con `DS_THUOCPX > TT_THUOCPX` (thuốc phóng xạ) với 11 thẻ; trong Excel các
cột này mang tiền tố `THUOCPX_`.

Kiểu, độ dài tối đa và tính bắt buộc của từng thẻ lấy từ mục 5 của mỗi phần trong tài liệu
kỹ thuật và khai thẳng vào lớp `Mau0x`.

### 4.5 Lỗi đã biết của tài liệu gốc

Tệp PDF bị lỗi tìm–thay thế hàng loạt: cụm `CSKCB` bị thay bằng `Cơ sở KCB` ở khắp nơi,
kể cả bên trong tên thẻ XML và tên tham số. Do đó tài liệu viết `<MA_CƠ SỞ KCB>`,
`<CƠ SỞ KCB_CGKT>`, `<MA_CƠ SỞ KCB_TBYT>` và tham số `maCơ sở KCB`. Bảng đặc tả trường
(mục 5) và các tệp Excel mẫu mới là bản đúng: `MA_CSKCB`, `CSKCB_CGKT`, `MA_CSKCB_TBYT`,
`maCSKCB`.

Hệ quả thiết kế: **tên trường body HTTP và tên thẻ XML để trong `config/tt12.php`**, đổi
được mà không sửa mã, và phải nghiệm thu bằng một hồ sơ một dòng gửi thật trước khi gửi
hàng nghìn dòng.

## 5. Mô hình dữ liệu

### 5.1 Hiện trạng khoá duy nhất của sáu bảng danh mục

| Bảng | Khoá duy nhất hiện tại | Việc phải làm |
|---|---|---|
| `medicine_catalogs` | `ma_thuoc, ten_thuoc, ham_luong, so_dang_ky, don_gia_bh, tt_thau, tu_ngay, ma_cskcb` | giữ nguyên |
| `medical_supply_catalogs` | `ma_vat_tu, ten_vat_tu, tt_thau, don_gia_bh, tu_ngay, ma_cskcb` | giữ nguyên |
| `service_catalogs` | `ma_dich_vu, ten_dich_vu, don_gia, quy_trinh, tu_ngay, ma_cskcb` | giữ nguyên |
| `department_bed_catalogs` | `ma_khoa, ma_cskcb` | thêm `tu_ngay` |
| `medical_staffs` | `ma_bhxh` | đổi thành `so_dinh_danh, ma_khoa, ma_cskcb, tu_ngay` |
| `equipment_catalogs` | `ma_may` | thêm cột `ma_cskcb`; khoá thành `ma_may, ma_cskcb, tu_ngay` |

**`medical_staffs` là ca nặng nhất.** Mẫu 02 của TT12 không còn cột `MA_BHXH`, mà khoá duy
nhất của bảng lại đúng là `ma_bhxh` (đã cho phép NULL từ migration
`2026_04_09_100001`). Import TT12 sẽ cho `ma_bhxh = NULL` ở mọi dòng; MySQL cho phép nhiều
NULL trong unique index, nên **mỗi lần nhập lại sẽ chèn trùng toàn bộ, âm thầm**.
`config/catalog_import_mapping.php` đã có `unique_keys_alt = ['so_dinh_danh']` để né ở tầng
ứng dụng, nhưng ràng buộc CSDL vẫn sai. Một người có thể làm ở hai khoa nên `ma_khoa` phải
nằm trong khoá.

### 5.2 Cột bổ sung cho bảng danh mục

| Bảng | Cột thêm |
|---|---|
| `medicine_catalogs` | `ma_dvkt, tccl, bo_phan_vt, ten_khoa_hoc, nguon_goc, pp_chebien, ma_dl_nhap, ma_dl_cb, tlhh_cb, tlhh_bq, ma_cskcb_thuoc, tu_ngay_hd, den_ngay_hd` |
| `medical_supply_catalogs` | `so_luu_hanh, tinhnang_kt, tu_ngay_hd, ma_cskcb_tbyt` |
| `service_catalogs` | `ten_dvkt_gia, so_luong_cgkt, qd_dvkt, qd_pd_gia, ghi_chu, gia_thanh_toan` |
| `equipment_catalogs` | `ma_cskcb` |
| `medical_staffs` | `ma_cskcb` |
| `department_bed_catalogs` | không thiếu cột nào |

**Lỗi tiềm ẩn phát hiện khi khảo sát:** `medical_staffs` **chưa hề có cột `ma_cskcb`**,
trong khi `config/catalog_import_mapping.php` đã ánh xạ `ma_cskcb` cho danh mục
`medical_staff` và `CatalogImportService::DANH_MUC_THEO_CO_SO` đã liệt kê `medical_staff`
là danh mục theo từng cơ sở. Nghĩa là luồng import thủ công hiện tại đang **âm thầm đánh
rơi mã cơ sở của nhân viên y tế**, và chức năng xoá danh mục theo cơ sở không lọc đúng
được. Migration ở giai đoạn 2 sửa luôn việc này.

Ba bảng đầu hiện dùng chung `tu_ngay`/`den_ngay` cho cả hợp đồng lẫn hiệu lực danh mục,
trong khi TT12 tách đôi: `TU_NGAY_HD`/`DEN_NGAY_HD` là hợp đồng, `TU_NGAY`/`DEN_NGAY` là
hiệu lực dòng. Migration **giữ nguyên ý nghĩa cột cũ là hiệu lực** (đúng với cách
`Xml3176Xml3Checker` đang dùng) và thêm cặp `_hd` mới, để không phải sửa checker.

Ghi chú: `medical_supply_catalogs` hiện đã có cột `den_ngay_hd` nhưng chưa có `tu_ngay_hd`
— đây là dấu vết của cùng sự nhập nhằng nói trên.

### 5.3 Năm bảng mới

**`tt12_ho_so`** — một tệp Excel = một bản ghi = một đơn vị ký / gửi / ghi đè

```
ma_ho_so       unique     ví dụ TT12_MAU03_01929_20260825_001
mau            index      MAU_01..MAU_06
loai_hs                   '70'|'71'|'10'|'11'|'12'|'72'  — kiểu CHUỖI
ma_cskcb       index
ten_tep, so_dong, id_danh_sach
                          id_danh_sach = GUID gắn vào thuộc tính Id của <DANHSACH_*>,
                          sinh MỘT LẦN lúc import
imported_at, imported_by, import_error
checked_at, so_loi (index)
is_signed, sign_method, signed_at, signed_error, duong_dan_da_ky
submitted_at, submitted_by, submit_error, submitted_message
ma_gd (index), ma_ket_qua (index), thoi_gian_tiep_nhan
dong_bo_at, dong_bo_so_dong
lich_su_gui
timestamps
```

`loai_hs` để kiểu chuỗi: `'10'` khác `10` khi so sánh nghiêm ngặt trong mã.

**`tt12_dong`** — ảnh chụp nguyên văn từng dòng

```
ho_so_id (index), stt (int)          unique(ho_so_id, stt)
du_lieu   longText, cast 'array'     { "MA_THUOC": "...", "TU_NGAY": "20260101", ... }
ma, ten, tu_ngay, den_ngay           bốn cột rút ra để lọc/tìm trên màn hình
```

Dùng `longText` + cast `array`, **không** dùng `$table->json()`: dự án chạy Laravel 5.5 /
PHP 7.0, chưa migration nào dùng kiểu `json`, và bộ test chạy trên SQLite. `longText` hoạt
động giống nhau ở mọi nơi.

Vì sao một cột `du_lieu` chứ không sáu bảng riêng: sáu mẫu có 11–36 cột khác nhau hoàn
toàn, và bảng danh mục mới là nơi dữ liệu có hình thù để truy vấn. `tt12_dong` chỉ có một
nhiệm vụ — dựng lại đúng XML đã gửi. Sáu bảng nữa chỉ để phục vụ mỗi việc đó là sáu bảng
phải sửa mỗi lần BHXH đổi mẫu.

**`tt12_dong_thuoc_px`** — bảng con `DS_THUOCPX` của MAU_05. Quan hệ 1-n, không nhét vào
JSON của dòng cha vì cần dựng thẻ lồng có thứ tự.

```
dong_id (index), stt, ma_thuoc, ten_thuoc, so_dang_ky, don_vi_tinh, tt_thau,
don_gia_thuoc, dm_nsx_cdd, dm_thucte_cdd, lieu_bq_px, tl_thucte_bq_px, thanh_tien_thuoc
```

**`tt12_loi`**

```
ho_so_id (index), stt_dong, cot, ma_loi (index), muc_do ('loi'|'canh_bao'), mo_ta
```

**`tt12_lich_su_gui`** — mỗi lần bấm gửi thêm một dòng, kể cả lần hỏng; giữ dấu vết đối
soát sau khi ghi đè. Nhân nguyên khuôn `ctdt_lich_su_gui`.

## 6. Bước kiểm — `Tt12Kiem`

Nguồn luật là bảng đặc tả cột trong lớp `Mau0x`, không phải một mớ `if` rải rác. Mỗi cột
khai `['the' => 'TU_NGAY', 'kieu' => 'ngay8', 'max' => 8, 'batBuoc' => true]`.

1. **Cấu trúc tệp** — nhận diện được mẫu từ header; không thiếu cột bắt buộc; sheet `DATA`
   tồn tại. Hỏng ở đây thì hồ sơ dừng luôn, không tạo dòng nào.
2. **Theo ô** (sinh tự động từ đặc tả) — bắt buộc mà rỗng; kiểu `Số` mà có chữ; vượt độ
   dài tối đa; `ngay8` không đúng `yyyymmdd` hoặc không phải ngày có thật (`20260231`).
3. **Theo dòng** — `DEN_NGAY >= TU_NGAY`; `MA_CSKCB` khớp cơ sở đang thao tác; đơn giá và
   số lượng không âm.
4. **Theo hồ sơ** — `STT` không trùng, không rỗng; **cặp dòng cũ/mới**: hai dòng cùng mã
   trong một tệp thì đúng một dòng được để trống `DEN_NGAY`, dòng còn lại phải có
   `DEN_NGAY`, và `TU_NGAY` của dòng mới phải sau `DEN_NGAY` của dòng cũ.
5. **Riêng từng mẫu** — MAU_03: `LOAI_THUOC` quyết định nhóm cột dược liệu
   (`TEN_KHOA_HOC`, `NGUON_GOC`, `PP_CHEBIEN`, `TLHH_CB`, `TLHH_BQ`) có bắt buộc hay
   không. MAU_05: nếu dòng có bất kỳ cột `THUOCPX_*` thì `THUOCPX_MA_THUOC` và
   `THUOCPX_DON_GIA_THUOC` thành bắt buộc.

**Hai mức:** `loi` chặn ký/gửi, `canh_bao` không chặn. `tt12_ho_so.so_loi` **chỉ đếm mức
`loi`** — đếm cả cảnh báo thì hồ sơ sạch vẫn không gửi được và người dùng không hiểu vì sao.

Mã lỗi để làm hằng trong lớp luật, **không** tạo bảng `tt12_error_catalogs`. Bảng danh mục
lỗi ở XML3176 tồn tại để bật/tắt từng luật khi giám định thay đổi cách bắt lỗi; ở đây luật
đến từ đặc tả trường cố định của TT12, chưa có nhu cầu đó.

## 7. Dựng XML — `Tt12PhongBi`

Hàm thuần: không đọc CSDL, không ghi tệp.

```xml
<HSDANHMUC xmlns:xsd="http://www.w3.org/2001/XMLSchema"
           xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
  <DANHSACH_DMTHUOCMAUCHEPHAMMAU Id="Id-{id_danh_sach}">
    <DMTHUOCMAUCHEPHAMMAU>... các thẻ theo ĐÚNG thứ tự đặc tả ...</DMTHUOCMAUCHEPHAMMAU>
    ...
  </DANHSACH_DMTHUOCMAUCHEPHAMMAU>
  <CHUKYDONVI/>
</HSDANHMUC>
```

- **`DOMDocument`, không nối chuỗi.** Tên thuốc, tên khoa đến từ Excel người dùng; một dấu
  `&` trong tên nhà sản xuất là đủ phá vỡ cả tài liệu.
- **`id_danh_sach` sinh một lần lúc import** và lưu vào `tt12_ho_so`. Sinh lại mỗi lần ký
  thì bản ký lần hai khác bản lần một, mà chữ ký XMLDSig tham chiếu chính `#Id` này.
- **Thẻ rỗng giữ nguyên `<DEN_NGAY/>`**, không bỏ thẻ. Tài liệu viết như vậy ở cả sáu mẫu.
- `CHUKYDONVI` rỗng đặt cuối vì `XMLSignService` **ghi chữ ký vào thẻ đã tồn tại**
  (`tag_store_signature_value = 'CHUKYDONVI'`), không tự tạo — giống `Xml3176Service`,
  `Qd130XmlService` và `CtdtPhongBi`.

## 8. Ký — `SignTt12Job`

Dùng lại `XMLSignService` nguyên vẹn. Job tách riêng khỏi job gửi, đúng lý do đã ghi trong
`SignCtdtJob`: ký hỏng là lý do cục bộ (rút USB token, HSM không phản hồi), gửi hỏng là do
mạng; gộp lại thì `tries = 3` sẽ ký lại ba lần chỉ vì mạng chập, mà ký là thao tác tốn
thời gian nhất trong chuỗi.

Job nhận `ma_ho_so` chứ không nhận model — job có thể nằm chờ trong hàng đợi rất lâu, và
một model serialize sẵn sẽ mang theo dữ liệu đã cũ.

**Chỉ ghi tệp khi `isSigned = true`.** Ghi tệp chưa ký vào đường "đã ký" là để lại một quả
bom: lần gửi sau đọc đúng tệp đó và gửi lên cổng một gói không có chữ ký. Đĩa `exportTt12`
khai trong `config/filesystems.php`.

**Điểm phải nghiệm thu tay:** chữ ký TT12 có hai `<Reference>` — một trỏ tới
`#Object-CHUKYDONVI-...`, một trỏ tới `#Id-{id_danh_sach}` của thẻ `DANHSACH_*`. Phần sinh
`Reference` nằm bên dịch vụ HSM/USB bên ngoài nên **không xác minh được từ mã nguồn**.
Giai đoạn 5 của kế hoạch triển khai có một bước riêng: ký thử một hồ sơ **một dòng**, mở
XML kết quả đọc bằng mắt hai thẻ `Reference`, rồi mới gửi thật.

## 9. Gửi — `Tt12SubmitService`

```
POST  {url theo mẫu}                   Content-Type: application/x-www-form-urlencoded
Header  accessToken, tokenId, passwordHash
Body    username, loaiHs, maTinh, maCSKCB, fileHsBase64
```

- **`BHYTLoginService` khởi tạo theo `ma_cskcb` của chính hồ sơ.** Nếu token và `maCSKCB`
  trong body thuộc hai cơ sở khác nhau thì cổng vẫn nhận và hồ sơ bị ghi sai đơn vị gửi —
  hỏng im lặng, không lộ ra cho tới lúc đối soát.
- **Đọc `maKetQua` trong THÂN phản hồi**, không phải mã HTTP. Cổng trả `HTTP 200` kèm
  `{"maKetQua":"401"}`. Khuôn retry bắt HTTP 401 của
  `CongDuLieuYTeDienBienXmlSubmitService` chép sang đây là không bao giờ retry.
- `401` → `logout()`, đăng nhập lại, gửi lại **đúng một lần**.
- Lỗi mạng thì **ném**, không nuốt: mạng chập là lỗi tạm thời, biến nó thành kết quả "thất
  bại" sẽ làm hồ sơ mất cơ hội được hàng đợi thử lại.
- Mỗi lần gửi thêm một dòng `tt12_lich_su_gui`, kể cả lần hỏng.
- Bảng mã kết quả để trong `config/tt12.php`, **tra bằng `array_key_exists()` hoặc so sánh
  lỏng, tuyệt đối không dùng `===` với chuỗi**: PHP ép khoá mảng dạng chuỗi số thành int,
  nên `foreach ($cfg as $ma => $mo) if ($ma === '200')` luôn trượt. Bẫy này đã được ghi lại
  trong `config/ctdt.php`.
- Ghi log kích thước base64 mỗi lần gửi: tài liệu không nói ngưỡng của mã `1001`
  (file size quá dài), phải tự dò từ thực tế.

Bảng mã kết quả (mục I.4 của tài liệu):

```
200                      Thao tác thành công
401, 402, 403            Mã cơ sở KCB chưa đúng, tài khoản hoặc token hết hạn,
                         hoặc tài khoản không có quyền truy cập
500                      Lỗi hệ thống
123,124,125,202,204,205  Lỗi liên quan đến nội dung file XML
```

## 10. Đồng bộ danh mục — `Tt12DongBoDanhMuc`

Chỉ chạy khi `maKetQua = 200`. Đọc `tt12_dong` → ánh xạ sang cột bảng danh mục theo
`Mau0x::anhXaDanhMuc()` → upsert theo khoá duy nhất mới, **chạy theo lô** dùng lại
`App\Services\Import\GhiTheoLo`. Ghi `dong_bo_at`, `dong_bo_so_dong`.

Chạy theo lô là bắt buộc chứ không phải tối ưu: ghi chú trong `CatalogImportService` ghi
lại rằng tệp danh mục thật của cổng BHXH (52.551 dòng × 15 cột) ngốn 128 MB và 346 giây
**chỉ riêng phần đọc**, trong khi máy chủ đặt PHP 128 MB / 120 giây.

**Không** tắt `is_active` của dòng cũ: TT12 đóng một dòng bằng `DEN_NGAY`, không bằng cờ.
Cơ chế `LAM_MOI_TRON_BO` của `CatalogImportService` chỉ dành cho hai danh mục dùng chung
toàn quốc; đưa danh mục TT12 vào đó là tắt dữ liệu cũ mà không bật lại.

## 11. Luật quyết định — `Tt12QuyetDinhGui`

Một nơi duy nhất trả lời; cả job lẫn nút bấm trên màn hình đều hỏi nó.

- `nenKy()` → `KY` khi `checked_at` khác rỗng **và** `so_loi = 0`.
- `nenGui()` → `GUI` khi `is_signed = true` **và** (`ma_ket_qua` chưa có **hoặc** khác `200`).

Hồ sơ đã `200` thì **không gửi lại** — muốn sửa thì import tệp mới. Đây là thứ ngăn một cú
bấm nhầm sinh hai `maGiaoDich` cho cùng một danh mục.

## 12. Màn hình

Nhân đúng khuôn CTĐT (`resources/views/bhyt/ctdt/`), người dùng đã quen.

| Route | Việc |
|---|---|
| `bhyt/tt12/import` | Chọn mẫu (hoặc để tự nhận diện từ header) + tải `.xlsx`. Tải mẫu Excel rỗng cho từng mẫu. |
| `bhyt/tt12/index` + `fetch-data` | Danh sách hồ sơ (DataTable server-side): mẫu, tên tệp, mã CSKCB, số dòng, số lỗi, đã ký, mã GD, mã kết quả, thời gian tiếp nhận, đã đồng bộ. Lọc theo mẫu / trạng thái / khoảng ngày. |
| `bhyt/tt12/detail/{ma_ho_so}` + `tab/{loai}` | Ba tab: Dòng dữ liệu, Lỗi, XML đã ký. |
| `bhyt/tt12/{ma_ho_so}/ky-va-gui`, `bhyt/tt12/ky-va-gui-nhieu` | Một hồ sơ hoặc tích chọn nhiều. |
| `bhyt/tt12/xuat/danh-sach`, `bhyt/tt12/xuat/loi` | Xuất Excel. |
| `bhyt/tt12/{ma_ho_so}` (DELETE) | Xoá hồ sơ chưa gửi thành công. Đã có `maGiaoDich` thì không cho xoá. |

Tab "Dòng dữ liệu" có **cột động** vì sáu mẫu có 11–36 cột khác nhau: `Tt12DetailTabs` hỏi
`Mau0x::cot()` để dựng đầu bảng, đọc `tt12_dong.du_lieu`. Cùng cơ chế `Xml3176DetailTabs`
và `CtdtDetailTabs` đang dùng.

## 13. Kiểm thử

Ba chốt an toàn đã có trong dự án: **không dùng `RefreshDatabase`** (bộ test từng xoá sạch
CSDL `qlbv`), dựng bảng SQLite bằng lớp hỗ trợ riêng
(`tests/Support/DungBangTt12Sqlite.php`, nhân từ `DungBangCtdtSqlite`), và
`tests/Support/FakeTt12SubmitService.php` thay cho gọi mạng thật.

1. `Tt12PhongBiTest` — hàm thuần, rủi ro cao nhất. Thứ tự thẻ đúng đặc tả; `<DEN_NGAY/>`
   rỗng chứ không mất thẻ; thuộc tính `Id` khớp `id_danh_sach`; `CHUKYDONVI` rỗng nằm
   cuối; tên có ký tự `&`/`<` không phá vỡ tài liệu; MAU_05 lồng đúng
   `DS_THUOCPX > TT_THUOCPX`; MAU_04/MAU_06 dùng đúng tiền tố `DSACH_`.
2. `Tt12KiemTest` — mỗi luật một test, đặc biệt luật cặp dòng cũ/mới và luật `LOAI_THUOC`.
3. `Tt12ImporterTest` — nhận diện đúng sáu mẫu từ header; một dòng hỏng không kéo cả tệp
   xuống; import lại cùng tệp không sinh hồ sơ trùng.
4. `Tt12DongBoDanhMucTest` — chỉ chạy khi `200`; hai dòng cũ/mới cùng mã tồn tại song song
   sau đồng bộ (test này chứng minh việc đổi khoá duy nhất thật sự có tác dụng).
5. `Tt12QuyetDinhGuiTest`, `ChuaKyKhongGuiTt12Test` — hồ sơ còn lỗi không ký; chưa ký
   không gửi; đã `200` không gửi lại.
6. `Tt12SubmitServiceTest` — `maKetQua` `401` trong thân phản hồi kích hoạt đăng nhập lại
   và gửi lại đúng một lần; lỗi mạng thì ném chứ không nuốt.
7. `Tt12CauHinhTest` — sáu mẫu trong registry khớp sáu khối trong `config/tt12.php`, không
   thừa không thiếu.
8. `Tt12BladeCompilesTest` — các blade biên dịch được.

Hai lưu ý từ hạ tầng test hiện có: Mockery vỡ khi mock phương thức có khai báo kiểu trả về
(nên `Tt12SubmitService` **không** khai `: array`), và bộ test đang có sẵn một số test đỏ
có chủ đích — chạy toàn bộ trước khi bắt đầu để chốt mốc, và chỉ so sánh với mốc đó.

## 14. Thứ tự triển khai

| GĐ | Nội dung | Nghiệm thu |
|---|---|---|
| 1 | `config/tt12.php`, `Tt12MauRegistry`, sáu lớp `Mau0x` | `Tt12CauHinhTest` xanh; đối chiếu sáu lớp với sáu tệp Excel mẫu |
| 2 | Migration: năm bảng mới + cột bổ sung + đổi khoá `medical_staffs`, `department_bed_catalogs`, `equipment_catalogs` | `migrate` chạy được trên bản sao CSDL thật; xử lý trùng trước khi đổi khoá |
| 3 | `Tt12DocExcel`, `Tt12Importer`, `Tt12LuuHoSo`, màn hình import | Import cả sáu tệp mẫu, xem được số dòng |
| 4 | `Tt12Kiem`, `CheckTt12Job`, tab Lỗi, xuất Excel lỗi | Cố tình làm hỏng tệp mẫu, thấy đúng lỗi |
| 5 | `Tt12PhongBi`, `SignTt12Job`, tab XML | Ký thử hồ sơ một dòng, mở XML đọc mắt hai thẻ `Reference` |
| 6 | `Tt12SubmitService`, `SubmitTt12Job`, màn danh sách, ký/gửi hàng loạt | Gửi thật hồ sơ một dòng, xác nhận tên trường body và nhận `maGiaoDich` |
| 7 | `Tt12DongBoDanhMuc` | Sau khi `200`, dữ liệu vào đúng bảng danh mục và `Xml3176Xml3Checker` tra được |

**Giai đoạn 2 là chỗ rủi ro nhất, không phải giai đoạn 6.** Đổi khoá duy nhất trên bảng
đang có dữ liệu thật sẽ vướng dòng trùng. Trước khi viết kế hoạch thực thi phải chạy truy
vấn đếm trùng trên CSDL thật cho ba bảng nói trên; migration phải **đếm và báo cáo trùng
trước**, không im lặng xoá.

## 15. Rủi ro còn mở

| Rủi ro | Cách xử lý |
|---|---|
| Tên trường body HTTP (`maCSKCB` hay `maCơ sở KCB`, `loaiHs` hay `loaiHoSo`) chưa chắc chắn do lỗi tìm–thay thế trong PDF | Để trong `config/tt12.php`; nghiệm thu bằng một hồ sơ một dòng ở giai đoạn 6 |
| Dịch vụ ký HSM/USB có sinh đúng `Reference` trỏ tới `#Id` của thẻ `DANHSACH_*` hay không — không xác minh được từ mã nguồn | Bước đọc mắt XML đã ký ở giai đoạn 5, trước khi gửi thật |
| Ngưỡng kích thước gây mã lỗi `1001` không được tài liệu công bố | Ghi log kích thước base64 mỗi lần gửi; khi gặp `1001` thì báo người dùng tách tệp |
| Dữ liệu trùng cản việc đổi khoá duy nhất trên ba bảng danh mục | Đếm trước khi viết kế hoạch; migration báo cáo, không tự xoá |
| Máy chủ `qlbv_public` giới hạn PHP 128 MB / 120 giây | Đọc Excel và ghi danh mục đều theo lô; hồ sơ lớn đẩy qua hàng đợi |
