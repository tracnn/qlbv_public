# Thiết kế module Chứng từ điện tử BHXH (PL02)

Ngày: 2026-08-19
Trạng thái: đã thống nhất, chờ lập kế hoạch triển khai
Nguồn yêu cầu: `PL02 nâng cấp giám định BHYT` — Phụ lục 02 "Liên thông dữ liệu qua dịch vụ Web",
ban hành kèm công văn BHXH Việt Nam 2025.

---

## 1. Mục tiêu

Xây dựng module cho phép nạp, kiểm tra, ký số và gửi các chứng từ điện tử lên cổng BHXH
(`egw.baohiemxahoi.gov.vn`) theo đặc tả PL02, với trải nghiệm vận hành giống module XML3176
đang chạy: nạp tệp → xem danh sách → xem chi tiết → theo dõi trạng thái → xuất báo cáo.

Phạm vi đợt này gồm **cả ba dịch vụ gửi** và **đủ 9 loại chứng từ**.

### Quyết định đã chốt

| Vấn đề | Quyết định |
|---|---|
| Nguồn dữ liệu | Nạp tệp XML có sẵn (không sinh từ HIS) |
| Dạng tệp nạp | Gói `HSCHUNGTU` / `HSDLGBT` / `HSDLGCS` hoàn chỉnh, **chưa ký** |
| Phạm vi chứng từ | Đủ 9 loại: 7 chứng từ TT25 + giấy báo tử + giấy chứng sinh |
| Luồng gửi | Nạp → tự ký → tự gửi qua hàng đợi, có nút gửi lại thủ công |
| Nạp tự động | Có Console command quét thư mục |
| Lưu trữ | Bảng riêng cho từng loại chứng từ (9 bảng chi tiết) |
| Kiểm lỗi | Có — kiểm cấu trúc, trường bắt buộc, định dạng ngày theo PL02 |
| Nạp trùng | Ghi đè bản cũ theo khóa nghiệp vụ, giống `deleteExistingXml3176` |
| Nơi lưu tệp | Disk mới `exportCtdt` — `D:\XML\ChungTuDienTu` |

---

## 2. Đặc tả nguồn — tóm lược PL02

### 2.1. Dịch vụ lấy token (mục I)

`POST https://egw.baohiemxahoi.gov.vn/api/token/take`, `application/x-www-form-urlencoded`.

Vào: `username`, `password`. Ra: `maKetQua`, `APIKey.{access_token, id_token, token_type,
username, expires_in}`.

Mã kết quả: `200` thành công · `401` tài khoản không tồn tại · `402` mã cơ sở KCB không đúng ·
`403` tài khoản bị khóa · `500` lỗi hệ thống.

**Dịch vụ này đã được cài đặt trong `App\Services\BHYTLoginService` và dùng lại nguyên vẹn.**

### 2.2. Ba dịch vụ gửi

| Dịch vụ | Thẻ gốc | `loaiHs` | URL |
|---|---|---|---|
| Chứng từ TT25/2025 (mục II) | `HSCHUNGTU` | `39` | `/api/chungtugw/GuiHoSoChungTu2025` |
| Giấy báo tử TT25/2025 (mục III) | `HSDLGBT` | `60` | `/api/hososuckhoe/guiGiayToDienTu` |
| Giấy chứng sinh TT22/2025 (mục IV) | `HSDLGCS` | `61` | `/api/hososuckhoe/guiGiayToDienTu` |

Cả ba dùng chung khuôn:

- Body `application/x-www-form-urlencoded`, 7 trường:
  `maCskcb`, `token`, `id_token`, `username`, `password`, `loaiHs`, `fileBase64Str`.
- Phản hồi: `MaGD`, `MaKetQua`, `ThoiGianTiepNhan` (14 ký tự `YYYYMMDDHHmmss`).
- Mã kết quả: `200` thành công · `205` `fileBase64Str` không hợp lệ · `401` lỗi xác thực ·
  `500` lỗi server · `1001` file size quá dài.
- Gói XML có thẻ `CHUKYDONVI` chứa chữ ký XMLDSig: canonicalization `xml-c14n-20010315`,
  `rsa-sha256`, digest `sha256`, hai `Reference` (tới `Object-CHUKYDONVI-Id-*` và tới `Id-*`).

**Điểm khác biệt giao thức quan trọng:** `BHYTXmlSubmitService` hiện có đặt xác thực ở
**header** (`accessToken`, `tokenId`, `passwordHash`) và body dùng tên trường khác
(`loaiHoSo`, `maTinh`, `maCSKCB`, `fileHSBase64`). PL02 đặt **tất cả trong body** với tên khác.
Hai giao thức khác nhau — phải viết service gửi riêng, không ép chung một hàm.

### 2.3. Cấu trúc gói `HSCHUNGTU`

```
HSCHUNGTU
├── THONGTINDONVI
│     └── MACSKCB
├── THONGTINHOSO  @Id="Id-<guid>"
│     ├── NGAYLAP        (8 ký tự YYYYMMDD)
│     ├── SOLUONGHOSO
│     └── DANHSACHHOSO
│           └── HOSO  (có thể nhiều)
│                 └── FILEHOSO  (có thể nhiều)
│                       ├── LOAIHOSO
│                       └── NOIDUNGFILE   (base64 của XML chứng từ)
└── CHUKYDONVI
      └── Signature ...
```

Gói `HSDLGBT` và `HSDLGCS` phẳng hơn: thẻ gốc chứa trực tiếp một `GIAYBAOTU` /
`GIAYCHUNGSINH` (có thuộc tính `Id`) và một `CHUKYDONVI`.

### 2.4. Ba điểm tài liệu không nhất quán

**(a) Tên `LOAIHOSO` không trùng tên thẻ gốc của nội dung.** Ba loại lệch:

| `LOAIHOSO` | Thẻ gốc trong base64 |
|---|---|
| `GIAYDIEUTRINOITRU` | `CTGiayDieuTriNoiTru` |
| `GIAYDIEUTRIVOSINH` | `CTGiayDieuTriVoSinh` |
| `GIAYSUCKHOEME` | `CTGiaySucKhoeMe` |

Bốn loại còn lại trùng: `CT03`, `CT04`, `CT06`, `CT07`.

Nếu lấy tên thẻ gốc làm khóa tra bảng — cách tự nhiên nhất — thì ba loại này rơi vào nhánh
"loại lạ" một cách im lặng. Registry phải khai **cả hai tên** và đối chiếu chéo.

**(b) `FILEHOSO` có `MACSKCB` hay không.** Mục 8 mô tả `FILEHOSO` gồm ba trường
(`MACSKCB`, `LOAIHOSO`, `NOIDUNGFILE`) nhưng XML mẫu ở mục 5 chỉ có hai. Xử lý mềm: đọc nếu
có, thiếu thì lấy từ `THONGTINDONVI`, và ghi log để sau này biết cổng thực nhận theo kiểu nào.

**(c) `GHI_CHU` của CT03.** Mục 9.2 liệt kê `GHI_CHU` (số thứ tự 18) nhưng XML mẫu ở mục 9.1
không có thẻ này. Bảng `ctdt_ct03` **vẫn có cột `ghi_chu`** — thừa một cột rỗng thì vô hại,
thiếu một cột thì mất dữ liệu.

---

## 3. Kiến trúc

### 3.1. Sơ đồ luồng

```
Nạp tệp (web upload | Console ctdt:import)
        ↓
CtdtImporter          — điểm vào DUY NHẤT
        ↓
CtdtGoiParser         — nhận diện dịch vụ theo thẻ gốc, giải base64 từng FILEHOSO
        ↓
LoaiChungTu registry  — 9 lớp tự mô tả: bảng nào, thẻ nào, quy tắc kiểm nào, tab nào
        ↓
CSDL: ctdt_ho_so (1) ──< ctdt_chung_tu ──1:1── 9 bảng chi tiết
                     └──< ctdt_loi
        ↓
CheckCtdtJob          — kiểm nội dung, ghi ctdt_loi
        ↓
SignCtdtJob           — XMLSignService (USB token ưu tiên, HSM sau)
        ↓
SubmitCtdtJob         — CtdtSubmitService (body form-urlencoded PL02)
        ↓
Ghi MaGD / MaKetQua / ThoiGianTiepNhan vào ctdt_ho_so
```

### 3.2. Nguyên tắc thiết kế

**Một khung, ba dịch vụ.** Ba dịch vụ gửi giống nhau tới 90%: cùng lấy token, cùng
form-urlencoded, cùng bộ phản hồi, cùng cơ chế ký. Khác biệt gói gọn trong bảng cấu hình
`config('ctdt.dich_vu')` gồm ba dòng. Viết khung một lần là đúng với thực tế đó — và BHXH
còn sẽ bổ sung giấy tờ theo TT25, khuôn mở sẵn sẽ đỡ về sau.

**Mỗi loại chứng từ là một lớp nhỏ tự mô tả.** Thêm loại mới = thêm 1 lớp + 1 migration,
không đụng vào khung. Đây là điểm khác có chủ đích so với `Xml3176Service.php` (1843 dòng,
chứa toàn bộ `storeXxx()` của 15 loại).

**Điểm vào nạp là duy nhất.** Bài học đã ghi trong `Xml3176Importer`: nghiệp vụ nạp từng được
cài hai lần (controller upload và Console command) và hai bản **đã lệch nhau** — cùng một hồ sơ
cho kết quả khác nhau tùy đường vào.

**Mọi lớp quyết định là hàm thuần.** Theo tiền lệ tốt của `QuyetDinhGui` và `CauHinhCoSo`:
tách logic quyết định ra khỏi I/O để test không cần CSDL, không cần mạng.

### 3.3. Tái sử dụng và viết mới

**Dùng lại nguyên vẹn:**

| Thành phần | Vai trò |
|---|---|
| `App\Services\BHYTLoginService` | Lấy token, cache theo mã cơ sở, 3 tầng bảo vệ |
| `App\Services\BHYT\CauHinhCoSo` | Phân giải tài khoản theo mã cơ sở (đa cơ sở) |
| `App\Services\XMLSignService` | Ký số — tự chọn USB token trước, HSM sau, trả `sign_method` |
| `App\Services\Xml3176\QuyetDinhGui` | Logic bật/tắt gửi + chặn hồ sơ chưa ký (mở rộng thêm nhánh) |
| `resources/views/partials/ma_cskcb.blade.php` | Bộ lọc mã cơ sở |

**Viết mới:** `CtdtSubmitService` — vì khác giao thức (mục 2.2).

---

## 4. Lược đồ dữ liệu

### 4.1. Nguyên tắc

**Lưu đúng nguyên văn thẻ XML, không "thông minh hoá".** PL02 khai mọi trường là *Chuỗi ký tự*,
kể cả `NGAY_VAO` (12 ký tự), `GIOI_TINH`, `TEKT`, `CAN_NANG_CON`. Ép sang `date`/`int` khi nạp
rồi dựng lại khi gửi thì mỗi vòng đổi kiểu là một cơ hội làm lệch dữ liệu: `201912121200` mất
phần phút, `01` mất số 0 đầu. Do đó **cột `string`, giữ nguyên chuỗi cổng quy định**; việc hiểu
ngày tháng là của lớp hiển thị và lớp kiểm lỗi, không phải của lớp lưu trữ.

### 4.2. Ba tầng bảng

```
ctdt_ho_so         1 HOSO = 1 bản ghi = 1 đơn vị ký/gửi/ghi đè
   ├──< ctdt_chung_tu    mỗi giấy tờ trong hồ sơ
   │        └──1:1── bảng chi tiết theo loại
   └──< ctdt_loi         lỗi kiểm tra trước khi gửi
```

**Vì sao một `HOSO` là một bản ghi, không phải một tệp.** Một tệp `HSCHUNGTU` có thể chứa nhiều
`HOSO`. Nếu lấy cả tệp làm đơn vị giao dịch thì không ghi đè, không gửi lại, không tra trạng thái
ở mức từng hồ sơ được. XML3176 cũng chọn cách này (một tệp nhiều `HOSO` → nhiều `ma_lk` độc lập).
Khi gửi, dựng lại phong bì `HSCHUNGTU` chứa đúng một `HOSO`.

### 4.3. Khóa hồ sơ `ma_ho_so`

Ghi đè phải khóa theo **nghiệp vụ**, không theo danh tính tệp — bản sửa gửi lại sẽ mang `Id`
GUID mới, khóa theo GUID thì không bao giờ đụng bản cũ.

Thứ tự lấy khóa:

1. `MA_GBT` (giấy báo tử) hoặc `MA_GCS` (giấy chứng sinh) — luôn có.
2. `MA_YTE` của chứng từ đầu tiên trong `HOSO` có thẻ này.
3. Lùi về `Id` GUID của `THONGTINHOSO`.

**Rủi ro đã chấp nhận:** `CT04`, `CT06`, `CT07` **không có** `MA_YTE`. Một `HOSO` chỉ gồm ba loại
này sẽ rơi vào nhánh (3) và **không ghi đè được** — nạp lại tạo bản ghi thứ hai. Chấp nhận và
cảnh báo trên màn danh sách ("hồ sơ không có mã y tế"), thay vì bịa khóa từ `MA_THE + NGAY_VAO`.
Bịa khóa mang rủi ro ngược lại và nặng hơn: hai hồ sơ khác nhau bị coi là một, mất dữ liệu im lặng.

### 4.4. `ctdt_ho_so`

| Cột | Kiểu | Ghi chú |
|---|---|---|
| `id` | increments | |
| `ma_ho_so` | string(100), **unique** | Khóa nghiệp vụ (mục 4.3) |
| `id_goi_xml` | string(64), nullable | Thuộc tính `Id` gốc, lưu để đối chiếu |
| `dich_vu` | string(10), index | `CT2025` \| `GBT` \| `GCS` |
| `loai_hs` | string(2) | `39` \| `60` \| `61` |
| `macskcb` | string(5), index | |
| `ngay_lap` | string(8), nullable | `NGAYLAP` (chỉ `HSCHUNGTU`) |
| `so_luong_ho_so` | integer, nullable | `SOLUONGHOSO` khai báo |
| `so_chung_tu` | integer | Số `FILEHOSO` thực tế |
| `duong_dan_goc` | string, nullable | Trên disk `exportCtdt` |
| `duong_dan_da_ky` | string, nullable | |
| `imported_at` | timestamp, nullable | |
| `imported_by` | string, nullable, index | |
| `import_error` | text, nullable | |
| `checked_at` | timestamp, nullable | |
| `so_loi` | integer, default 0, index | Số lỗi mức chặn |
| `is_signed` | boolean, default false | |
| `sign_method` | string, nullable | `usb_token` \| `hsm` |
| `signed_at` | timestamp, nullable | |
| `signed_error` | text, nullable | |
| `submitted_at` | timestamp, nullable, index | |
| `submitted_by` | string, nullable, index | |
| `submit_error` | string, nullable, index | |
| `submitted_message` | text, nullable | Nguyên văn phản hồi |
| `ma_gd` | string(50), nullable, index | `MaGD` — cột tra cứu quan trọng nhất khi đối soát |
| `ma_ket_qua` | string(10), nullable, index | `200`/`205`/`401`/`500`/`1001` |
| `thoi_gian_tiep_nhan` | string(14), nullable | |
| `lich_su_gui` | text, nullable | Nối thêm một dòng mỗi lần gửi, giữ dấu vết sau khi ghi đè |
| `timestamps` | | |

### 4.5. `ctdt_chung_tu`

| Cột | Kiểu | Ghi chú |
|---|---|---|
| `id` | increments | |
| `ho_so_id` | FK → `ctdt_ho_so` | |
| `loai_ho_so` | string(30), index | Giá trị `LOAIHOSO` nguyên văn |
| `ma_chung_tu` | string(100), nullable, index | `MA_YTE` / `MA_GBT` / `MA_GCS` |
| `ma_the` | string(20), nullable, index | Cột rút gọn |
| `ho_ten` | string(255), nullable, index | Cột rút gọn |
| `ngay_sinh` | string(14), nullable | Cột rút gọn |
| `ngay_vao` | string(14), nullable | Cột rút gọn |
| `ngay_ra` | string(14), nullable | Cột rút gọn |
| `noi_dung_goc` | longtext | XML nguyên văn đã giải base64 |
| `timestamps` | | |

**Cột rút gọn là cố tình trùng lặp dữ liệu.** Cái giá là phải đồng bộ khi sửa; cái được là màn
danh sách và bộ lọc chỉ đọc một bảng. Với 9 loại chứng từ tên trường khác nhau (`NGAY_SINH` của
người bệnh, `NGAYSINH_NND` của người mẹ, `NGAY_SINH_CON` của con), không rút gọn thì mọi truy vấn
danh sách đều thành `UNION` 9 nhánh.

### 4.6. Chín bảng chi tiết

Mỗi bảng: `id` + `chung_tu_id` (FK, unique) + đúng các thẻ PL02, tên cột = tên thẻ viết thường,
kiểu `string` (dài `text` cho các trường mô tả tự do) + `timestamps`.

**`ctdt_ct03`** — Giấy ra viện (Mẫu 02 - TT25), 33 trường:

```
so_luu_tru, ma_yte, ma_khoa, ma_bhxh, ma_the, ho_ten, ngay_sinh, gioi_tinh,
ma_dantoc, nghe_nghiep, dia_chi, ngay_vao, ngay_ra, dinh_chi_thai_nghen,
tuoi_thai, chan_doan, pp_dieutri, ghi_chu, thu_truong_dvi, ma_cchn_truongkhoa,
ten_truongkhoa, ngay_chung_tu, tekt, ho_ten_cha, ho_ten_me, ngoaitru_tungay,
ngoaitru_denngay, loai_giayto, so_cccd, ngaycap_cccd, noicap_cccd,
benhicd10_id, tenbenhnicd10
```

Lưu ý tên thẻ ICD của CT03 khác các loại khác: `BENHICD10_ID` / `TENBENHNICD10`
(không phải `BENH_ICD10_ID` / `BENH_ICD10_TEN`). Giữ nguyên, không "sửa cho đều".

**`ctdt_ct04`** — Bản tóm tắt hồ sơ bệnh án (Mẫu 03 - TT25), 45 trường:

```
ma_ct, so_seri, ma_bhxh, ma_the, ho_ten, ngay_sinh, gioi_tinh, ma_dantoc,
dia_chi, nghe_nghiep, ho_ten_cha, ho_ten_me, nguoi_giam_ho, ten_donvi,
nguoi_dai_dien, ngay_ct, ngay_vao, ngay_ra, chan_doan_vao, chan_doan_ra,
qt_benhly, tomtat_kq, pp_dieutri, ngay_sinhcon, ngay_chetcon, so_conchet,
tt_ravien, ghi_chu, tekt, loai_giayto, so_cccd, ngaycap_cccd, noicap_cccd,
lydo_vvien, tien_su_benh, dau_hieu_lam_sang, noi_khoa, is_noi_khoa,
phau_thuat_thu_thuat, is_phau_thuat_thu_thuat, huong_dieu_tri,
benh_icd10_id, benh_icd10_ten, is_lao_giai_doan_nang, is_xo_gan_giai_doan_mat_bu
```

Các trường `qt_benhly`, `tomtat_kq`, `tien_su_benh`, `dau_hieu_lam_sang`, `noi_khoa`,
`phau_thuat_thu_thuat`, `huong_dieu_tri` để kiểu `text`.

**`ctdt_ct06`** — Giấy xác nhận nghỉ dưỡng thai (Mẫu 11 - TT25), 24 trường:

```
ma_bhxh, ma_the, ho_ten, ngay_sinh, ngay_vao, ngay_ra, chan_doan,
nguoi_dai_dien, ma_bs, ten_bs, ten_dvi, so_kcb, ngay_ct, so_seri, ma_ct,
loai_giayto, so_cccd, ngaycap_cccd, noi_cu_tru_nnd, matinh_cu_tru,
maxa_cu_tru, tuoi_thai, benh_icd10_id, benh_icd10_ten
```

**`ctdt_ct07`** — Giấy chứng nhận nghỉ việc hưởng BHXH (Mẫu 07 - TT25), 27 trường:

```
ma_ct, mau_so, so_seri, so_kcb, ma_bhxh, ma_the, ho_ten, ngay_sinh, gioi_tinh,
don_vi, chandoan_dieutri, tu_ngay, den_ngay, ho_ten_cha, ho_ten_me,
thu_truong_dv, ma_cchn, ten_nguoi_hanh_nghe, ngay_chung_tu, tekt,
loai_giayto, so_cccd, ngaycap_cccd, noicap_cccd, ngay_kcb,
benh_icd10_id, benh_icd10_ten
```

**`ctdt_dieu_tri_noi_tru`** — Giấy xác nhận quá trình điều trị nội trú (Mẫu 06 - TT25),
thẻ gốc `CTGiayDieuTriNoiTru`, 36 trường:

```
so_luu_tru, ma_yte, ma_bhxh, ma_the, ho_ten, ngay_sinh, gioi_tinh, ma_khoa,
ten_dan_toc, ma_dan_toc, nghe_nghiep, dia_chi, ngay_vao, ngay_ra, chan_doan,
pp_dieutri, mo_ta, ghi_chu, dai_dien_dvi, ma_cchn_bs, ten_bs, loai_giayto,
so_cccd, ngaycap_cccd, noicap_cccd, benh_icd10_ma, benh_icd10_ten, ma_ct,
ngay_ct, so_seri, tuoi_thai, loai_phuong_phap, loai_pp_dieu_tri_vosinh,
ngay_dinh_chi_thainghen, is_nghiduongthai, so_ngay_nghiduongthai
```

Lưu ý: loại này dùng `MA_DAN_TOC` (có gạch dưới giữa DAN và TOC) khác CT03 dùng `MA_DANTOC`,
và dùng `BENH_ICD10_MA` (mã) thay vì `BENH_ICD10_ID`. Giữ nguyên.

**`ctdt_dieu_tri_vo_sinh`** — Giấy xác nhận quá trình điều trị vô sinh (Mẫu 09 - TT25),
thẻ gốc `CTGiayDieuTriVoSinh`, 29 trường:

```
so_luu_tru, ma_yte, ma_bhxh, ma_the, ho_ten, ngay_sinh, ma_khoa,
ma_tinhcutru, ma_xacutru, nghe_nghiep, dia_chi, ngay_vao, ngay_ra, chan_doan,
pp_dieutri, ghi_chu, dai_dien_dvi, ma_cchn_bs, ten_bs, loai_giayto, so_cccd,
ngaycap_cccd, noicap_cccd, benh_icd10_ma, benh_icd10_ten, ma_ct, ngay_ct,
so_seri, loai_phuong_phap
```

**`ctdt_suc_khoe_me`** — Giấy xác nhận người mẹ không đủ sức khỏe chăm sóc con
(Mẫu 10 - TT25), thẻ gốc `CTGiaySucKhoeMe`, 29 trường:

```
so_luu_tru, ma_yte, ma_bhxh, ma_the, ho_ten, ngay_sinh, ma_khoa,
ma_tinhcutru, ma_xacutru, nghe_nghiep, dia_chi, ngay_vao, ngay_ra, chan_doan,
pp_dieutri, ket_luan, tinhtrangbenhhientai, dai_dien_dvi, ma_cchn_bs, ten_bs,
loai_giayto, so_cccd, ngaycap_cccd, noicap_cccd, benh_icd10_ma,
benh_icd10_ten, ma_ct, ngay_ct, so_seri
```

**`ctdt_giay_bao_tu`** — thẻ gốc `GIAYBAOTU`, 38 trường:

```
ma_gbt, ma_bn, ma_hsba, ho_ten, ngay_sinh, gioi_tinh, ma_the, ma_dantoc,
ma_quoctich, dchi_thuongtru, matinh_thuongtru, mahuyen_thuongtru,
maxa_thuongtru, dchi_hientai, matinh_hientai, mahuyen_hientai, maxa_hientai,
loai_giayto, so_giayto, ngay_cap, noi_cap, ngaygio_vv, ngay_tv,
tinh_trang_tv, nguyennhan_tv, nguoi_ghigiay, nguoi_thanthich, ttruong_dvi,
so_baotu, quyen_so, ngay_capgiaybt, so_baotu_bd, quyen_so_bd, macskcb,
diachi_cskcb, ma_bhxh, benh_icd10_id, benh_icd10_ten
```

**`ctdt_giay_chung_sinh`** — thẻ gốc `GIAYCHUNGSINH`, 69 trường:

```
-- Định danh và người mẹ (NND = người này đẻ)
ma_gcs, ma_bn, ma_ct, so_seri, ma_bhxh_nnd, ma_the_nnd, hoten_nnd,
ngaysinh_nnd, ma_dantoc_nnd, ma_quoctich_nnd, loai_giayto_nnd, so_cccd_nnd,
ngaycap_cccd_nnd, noicap_cccd_nnd, noi_cu_tru_nnd, matinh_cu_tru,
mahuyen_cu_tru, maxa_cu_tru, ho_ten_cha, ma_the_tam,
-- Thông tin con
ten_con, gioi_tinh_con, so_con, lan_sinh, so_con_song, can_nang_con,
ngay_sinh_con, noi_sinh_con, tinh_trang_con, sinhcon_phauthuat,
sinhcon_duoi32tuan, ghi_chu,
-- Người lập phiếu và đơn vị
nguoi_do_de, nguoi_ghi_phieu, ma_ttdv, thu_truong_dvi, ngay_ct, so, quyen_so,
-- Mẹ thay thế (MTH) — mang thai hộ
ma_bhxh_mth, ma_the_mth, hoten_mth, ngaysinh_mth, ma_dantoc_mth,
ma_quoctich_mth, loai_giayto_mth, so_cccd_mth, ngaycap_cccd_mth,
noicap_cccd_mth, noi_cu_tru_mth, matinh_cu_tru_mth, maxa_cu_tru_mth,
ho_ten_cha_mth, ngaysinh_cha_mth, ma_dantoc_cha_mth, noi_cu_tru_cha_mth,
matinh_cu_tru_cha_mth, maxa_cu_tru_cha_mth, loai_giayto_cha_mth,
so_cccd_cha_mth, ngaycap_cccd_cha_mth, noicap_cccd_cha_mth,
-- Cha của người đẻ
ngaysinh_cha_nnd, ma_dantoc_cha_nnd, loai_giayto_cha_nnd, so_cccd_cha_nnd,
ngaycap_cccd_cha_nnd, noicap_cccd_cha_nnd,
cap_lan_dau
```

Lưu ý `NGAY_SINH_CON` dài **14 ký tự** (`20251021000000`) khác các trường ngày 8 hoặc 12 ký tự.

### 4.7. `ctdt_loi`

| Cột | Kiểu |
|---|---|
| `id` | increments |
| `ho_so_id` | FK, index |
| `chung_tu_id` | FK, nullable, index |
| `ma_loi` | string(20), index |
| `ten_truong` | string(50), nullable |
| `mo_ta` | string(255) |
| `muc_do` | string(10), index — `chan` \| `canh_bao` |
| `timestamps` | |

Danh mục mã lỗi để trong `config/ctdt.php`, **không** làm bảng danh mục như
`xml3176_error_catalogs`: mã lỗi ở đây do ta tự định nghĩa từ đặc tả PL02, không phải do BHXH
ban hành và cập nhật định kỳ.

---

## 5. Các lớp

### 5.1. Registry `LoaiChungTu`

```php
namespace App\Services\Ctdt\Loai;

interface LoaiChungTu
{
    public static function maLoaiHoSo(): string;   // 'GIAYDIEUTRINOITRU'
    public static function theGoc(): string;       // 'CTGiayDieuTriNoiTru'
    public static function bang(): string;         // 'ctdt_dieu_tri_noi_tru'
    public static function model(): string;
    public static function truong(): array;        // danh sách thẻ → cột
    public static function quyTacKiem(): array;    // quy tắc cho CtdtChecker
    public static function tenTab(): string;       // nhãn hiển thị
    public static function maChungTu($xml): ?string; // MA_YTE / MA_GBT / MA_GCS
    public static function rutGon($xml): array;    // ma_the, ho_ten, ngay_sinh, ngay_vao, ngay_ra
}
```

Chín lớp cài đặt: `Ct03`, `Ct04`, `Ct06`, `Ct07`, `DieuTriNoiTru`, `DieuTriVoSinh`,
`SucKhoeMe`, `GiayBaoTu`, `GiayChungSinh`.

`CtdtLoaiRegistry` tra theo `LOAIHOSO`, và **đối chiếu chéo** `theGoc()` với thẻ gốc thực tế
trong nội dung base64. Lệch → ném lỗi nạp, không đoán.

### 5.2. `CtdtGoiParser`

Hàm thuần trên chuỗi XML:

- `nhanDienDichVu($xml): string` — thẻ gốc `HSCHUNGTU`→`CT2025`, `HSDLGBT`→`GBT`,
  `HSDLGCS`→`GCS`. Thẻ lạ → ném lỗi.
- `macskcb($xml): string` — thiếu → ném lỗi (bài học XML3176).
- `danhSachHoSo($xml): array` — chuẩn hóa cả ba dịch vụ về cùng một dạng: mảng các `HOSO`,
  mỗi `HOSO` là mảng các `['loai_ho_so' => ..., 'noi_dung' => <XML đã giải base64>]`.
  Với `GBT`/`GCS` thì mảng có đúng một `HOSO` gồm một chứng từ.

`SOLUONGHOSO` phải đọc bằng `(int)(string)`, **không** dùng `count()` trên node —
`count()` trên một node SimpleXML luôn trả 1 bất kể giá trị thật (lỗi đã từng có trong XML3176).

Duyệt `HOSO` phải dùng `foreach ($x->DANHSACHHOSO->HOSO as ...)`; truy cập `->HOSO->FILEHOSO`
trên tập nhiều phần tử sẽ tự lấy phần tử đầu và bỏ im lặng các hồ sơ còn lại.

### 5.3. `CtdtImporter`

```php
CtdtImporter::nhapTuChuoi(string $noiDungXml, array $tuyChon = []): CtdtImportFileResult
```

Các bước:

1. `CtdtGoiParser::nhanDienDichVu()` — không nhận ra → lỗi nạp.
2. `macskcb()` — thiếu → lỗi nạp.
3. Duyệt từng `HOSO`. **Mỗi `HOSO` một transaction riêng** — một hồ sơ hỏng không kéo các hồ sơ
   còn lại xuống.
4. Với mỗi `FILEHOSO`: giải base64, `simplexml_load_string` bọc trong
   `libxml_use_internal_errors(true)` (tắt warning, tự báo lỗi).
5. Đối chiếu `LOAIHOSO` ↔ thẻ gốc qua registry. Lệch → lỗi.
6. Xác định `ma_ho_so`, gọi `xoaHoSoCu($maHoSo)` — **trong transaction**, hỏng thì dữ liệu cũ
   còn nguyên.
7. Lưu chi tiết vào bảng của từng loại, ghi `ctdt_chung_tu` với cột rút gọn.
8. `updateOrCreate` `ctdt_ho_so`, reset trạng thái ký/gửi, nối `lich_su_gui`.
9. **commit**
10. Sau commit: dispatch `CheckCtdtJob`, rồi `SignCtdtJob`.

Bước 9–10 đặt sau commit là có chủ đích — job đặt trong transaction sẽ trỏ tới dữ liệu chưa
tồn tại nếu rollback (ghi chú đã có trong `Xml3176Importer`).

Kết quả trả về theo khuôn `Xml3176ImportResult` / `Xml3176ImportFileResult`: lớp
`CtdtImportResult` (một hồ sơ) và `CtdtImportFileResult` (một tệp, gộp nhiều hồ sơ).

### 5.4. `CtdtChecker`

Quy tắc chung, áp cho mọi loại:

| Mã lỗi | Kiểm | Mức |
|---|---|---|
| `CTDT001` | Trường bắt buộc rỗng | chặn |
| `CTDT002` | `NGAY_*` 8 ký tự — sai định dạng `YYYYMMDD` hoặc ngày không tồn tại | chặn |
| `CTDT003` | `NGAY_*` 12 ký tự — sai `YYYYMMDDHHmm` | chặn |
| `CTDT004` | `NGAY_*` 14 ký tự — sai `YYYYMMDDHHmmss` | chặn |
| `CTDT005` | `GIOI_TINH` ngoài `{1,2,3}` | chặn |
| `CTDT006` | `LOAI_GIAYTO` ngoài `{0,1,2,3,4}` | chặn |
| `CTDT007` | Trường cờ (`TEKT`, `DINH_CHI_THAI_NGHEN`, `IS_*`, `CAP_LAN_DAU`, `SINHCON_*`) ngoài `{0,1}` | cảnh báo |
| `CTDT008` | `NGAY_RA` < `NGAY_VAO` | chặn |
| `CTDT009` | `MACSKCB` trong chứng từ khác `MACSKCB` của gói | chặn |
| `CTDT010` | Độ dài vượt đặc tả (`username` 5, `password` 6–10, `macskcb` 5) | cảnh báo |

Quy tắc riêng khai trong `quyTacKiem()` của từng lớp loại chứng từ.

`CtdtChecker` là **hàm thuần** nhận mảng dữ liệu, trả mảng lỗi — không đọc CSDL. `CheckCtdtJob`
lo phần đọc/ghi.

### 5.5. `QuyetDinhGui` mở rộng

Thêm một hằng và một nhánh vào lớp sẵn có:

```
config gửi đang tắt        → KHONG_GUI   (không làm gì, kể cả ghi lỗi)
hồ sơ còn lỗi mức "chặn"   → CON_LOI     (ghi submit_error, không gọi mạng)   ← mới
hồ sơ chưa ký              → CHUA_KY     (ghi submit_error, không gọi mạng)
còn lại                    → GUI
```

**Thứ tự quan trọng: kiểm cờ bật/tắt trước.** Khi chức năng gửi đang tắt mà vẫn ghi
`submit_error` thì đó là bịa — người đọc sẽ tưởng đã thử gửi và thất bại. Ghi chú này có sẵn
trong `QuyetDinhGui`, giữ nguyên tinh thần.

### 5.6. `CtdtSubmitService`

```php
public function gui(string $xmlDaKy, string $dichVu, string $maCskcb): array
```

```php
form_params: [
    'maCskcb'       => $maCskcb,
    'token'         => $loginService->getAccessToken(),
    'id_token'      => $loginService->getIdToken(),
    'username'      => $loginService->username(),
    'password'      => $loginService->password(),   // đã MD5 sẵn
    'loaiHs'        => $loaiHs,                     // 39 | 60 | 61
    'fileBase64Str' => base64_encode($xmlDaKy),
]
```

Không header tùy biến.

**`token` và `username` phải lấy từ cùng một `$loginService`.** Nếu token và tài khoản trong body
thuộc hai cơ sở khác nhau, cổng vẫn nhận và hồ sơ bị ghi sai đơn vị gửi — hỏng im lặng, không lộ
ra cho tới lúc đối soát. Cảnh báo này đã ghi trong `BHYTXmlSubmitService`.

**Retry-on-401:** mã `401` → xóa cache token của cơ sở, đăng nhập lại, thử lại **đúng một lần**.
`BHYTLoginService` cache token theo cơ sở và token có thể hết hạn giữa chừng. Module này làm
đúng ngay từ đầu (Trục dữ liệu y tế và BHYT hiện chưa có).

Ghi log kích thước `fileBase64Str` mỗi lần gửi — tài liệu không nói ngưỡng của mã `1001`, phải
tự dò từ thực tế.

### 5.7. Ba job

| Job | Việc | `tries` | `timeout` |
|---|---|---|---|
| `CheckCtdtJob($maHoSo)` | Chạy `CtdtChecker`, ghi `ctdt_loi`, cập nhật `so_loi`, `checked_at` | 3 | 120 |
| `SignCtdtJob($maHoSo)` | Dựng phong bì một `HOSO`, `XMLSignService::signXml()`, lưu tệp đã ký, ghi `is_signed`/`sign_method` | 2 | 120 |
| `SubmitCtdtJob($maHoSo)` | `QuyetDinhGui` → `CtdtSubmitService`, ghi `ma_gd`/`ma_ket_qua`/`thoi_gian_tiep_nhan` | 3 | 60 |

**Tách ký và gửi thành hai job** (XML3176 gộp trong `SubmitXml3176Job`) vì: ký hỏng do lý do cục
bộ (USB token bị rút, HSM không phản hồi) còn gửi hỏng do mạng — gộp lại thì `tries = 3` sẽ ký
lại ba lần chỉ vì mạng chập, mà ký lại là thao tác tốn thời gian nhất trong chuỗi.

Mỗi job kiểm lại cờ cấu hình ở đầu `handle()` — job có thể nằm chờ trong hàng đợi rất lâu, giữa
lúc đó cấu hình có thể đã bị tắt.

---

## 6. Giao diện, route, phân quyền

### 6.1. Ba màn hình

**Danh sách hồ sơ** — `bhyt/ctdt/index`. DataTable phía máy chủ. Cột: mã hồ sơ · dịch vụ ·
mã CSKCB · họ tên · mã thẻ · số chứng từ · số lỗi · trạng thái ký · trạng thái gửi · `MaGD` ·
thời điểm tiếp nhận.

Bộ lọc (`partials/search.blade.php`): khoảng ngày nạp · dịch vụ · loại chứng từ · mã CSKCB
(dùng lại `partials/ma_cskcb.blade.php`) · trạng thái gửi · chỉ hồ sơ còn lỗi.

**Cột trạng thái gửi phải phân biệt bốn thứ khác nhau**, không gộp thành "chưa gửi":

| Trạng thái | Nghĩa |
|---|---|
| Chưa ký | `is_signed = false` |
| Còn lỗi chặn | `so_loi > 0` |
| Chức năng gửi đang tắt | `config('organization.chung_tu_dien_tu.submit_enabled') = false` |
| Cổng từ chối | `ma_ket_qua ≠ 200`, hiện kèm mã |

Gộp lại là cách nhanh nhất để người vận hành ngồi chờ một hồ sơ vĩnh viễn không bao giờ được gửi.

Hồ sơ rơi vào nhánh lùi GUID (mục 4.3) hiện thêm nhãn cảnh báo "không có mã y tế — nạp lại sẽ
tạo bản ghi mới".

**Nạp tệp** — `bhyt/ctdt/import`. Upload nhiều tệp. Màn upload **chỉ lưu tệp rồi dispatch job
nạp**, không parse tại chỗ (mục 6.4). Kết quả hiển thị theo từng tệp và từng `HOSO`.

**Chi tiết hồ sơ** — `bhyt/ctdt/detail/{ma_ho_so}`. Tab động: **chỉ hiện tab của loại chứng từ
hồ sơ thực có** (khác XML3176 vốn cố định XML1–15). `CtdtDetailTabs` sinh danh sách tab từ
`ctdt_chung_tu`; mỗi tab nạp lười qua `detail/{ma_ho_so}/tab/{loai}` — 9 loại × tới 69 trường mà
nạp hết một lượt thì trang nặng vô ích. Thêm tab **"Lỗi"** và tab **"XML gốc"** (xem nguyên văn,
phục vụ đối chiếu khi cổng báo `205`).

### 6.2. Route

Đặt trong **cùng group `checkrole:xml-man`, prefix `bhyt/`** với `xml3176` — cùng nhóm người dùng,
cùng nghiệp vụ liên thông BHXH.

```
GET    bhyt/ctdt/index                          bhyt.ctdt.index
GET    bhyt/ctdt/index/fetch-data               bhyt.ctdt.fetch-data
GET    bhyt/ctdt/import                         bhyt.ctdt.import.index
POST   bhyt/ctdt/import/upload                  bhyt.ctdt.upload
GET    bhyt/ctdt/detail/{ma_ho_so}              bhyt.ctdt.detail
GET    bhyt/ctdt/detail/{ma_ho_so}/tab/{loai}   bhyt.ctdt.detail.tab
POST   bhyt/ctdt/{ma_ho_so}/ky-va-gui           bhyt.ctdt.ky-va-gui
DELETE bhyt/ctdt/{ma_ho_so}                     bhyt.ctdt.delete      [checkrole:superadministrator]
GET    bhyt/ctdt/export-xlsx                    bhyt.ctdt.export-xlsx
GET    bhyt/ctdt/job-status                     bhyt.ctdt.jobs.status
```

**Không tạo role mới** — dùng lại `xml-man`. Tách role chỉ tạo thêm việc quản trị mà không tách
được trách nhiệm thực tế. Quyền khai bằng migration seed theo khuôn
`database/migrations/2026_07_29_090000_them_role_order_check.php`.

Nút xóa để `superadministrator` (như `bhyt.xml3176.export-xml` đang làm): xóa hồ sơ đã có `MaGD`
là xóa dấu vết đối soát với BHXH.

### 6.3. Cấu hình

Cấu hình chia làm **hai tệp theo một seam duy nhất**: thứ giống nhau ở mọi cơ sở nằm trong
`config/ctdt.php` (được track), thứ đổi theo nơi cài đặt nằm trong `config/organization.php`
(`.gitignore`, mỗi máy một bản) — cùng chỗ với `BHYT.submit_xml_3176_enabled` của XML3176.
**Module không dùng biến `.env` nào.**

`config/organization.php` — tham số theo từng cơ sở:

```php
'chung_tu_dien_tu' => [
    'submit_enabled' => false,   // MẶC ĐỊNH TẮT
    'import_enabled' => true,
    'sign_enabled'   => true,
    'import_path'    => 'D:\XML\ChungTuDienTu\inbox',
    'queue_name'        => 'JobCtdt',
    'sign_queue_name'   => 'JobSignCtdt',
    'submit_queue_name' => 'JobSubmitCtdt',
],
```

`config/ctdt.php` — hằng số giao thức PL02, song song `config/xml3176.php`:

```php
return [
    'token_url'      => 'https://egw.baohiemxahoi.gov.vn/api/token/take',
    'dich_vu' => [
        'CT2025' => [
            'ten'     => 'Chứng từ TT25/2025',
            'the_goc' => 'HSCHUNGTU',
            'loai_hs' => '39',
            'url'     => 'https://egw.baohiemxahoi.gov.vn/api/chungtugw/GuiHoSoChungTu2025',
        ],
        'GBT' => [
            'ten'     => 'Giấy báo tử',
            'the_goc' => 'HSDLGBT',
            'loai_hs' => '60',
            'url'     => 'https://egw.baohiemxahoi.gov.vn/api/hososuckhoe/guiGiayToDienTu',
        ],
        'GCS' => [
            'ten'     => 'Giấy chứng sinh',
            'the_goc' => 'HSDLGCS',
            'loai_hs' => '61',
            'url'     => 'https://egw.baohiemxahoi.gov.vn/api/hososuckhoe/guiGiayToDienTu',
        ],
    ],
    'ma_ket_qua' => [
        '200'  => 'Thành công',
        '205'  => 'fileBase64Str không hợp lệ',
        '401'  => 'Lỗi xác thực tài khoản',
        '500'  => 'Lỗi server',
        '1001' => 'File size quá dài',
    ],
];
```

`submit_enabled` **mặc định tắt**: cổng thật của BHXH nhận là nhận thật. Bật sau khi đã chạy thử
và đối chiếu.

Disk mới trong `config/filesystems.php`:

```php
'exportCtdt' => [
    'driver' => 'local',
    'root'   => 'D:\XML\ChungTuDienTu',
],
```

### 6.4. Ràng buộc hạ tầng

Máy chủ `qlbv_public` giới hạn PHP **128MB / 120s**. Giấy chứng sinh có 69 trường và tệp nạp có
thể chứa nhiều `HOSO`. Vì thế **mọi việc nặng nằm trong job hàng đợi**; màn upload chỉ lưu tệp
rồi dispatch, không parse tại chỗ. Đây là điểm khác có chủ đích so với XML3176 (đang parse ngay
trong request upload).

### 6.5. Console command

`ctdt:import` — quét `config('organization.chung_tu_dien_tu.import_path')`, mỗi tệp gọi `CtdtImporter`, chuyển tệp đã xử lý
sang thư mục con `da-nhap/` hoặc `loi/`. Cùng khuôn `App\Console\Commands\XML3176Import`.

---

## 7. Kiểm thử

Theo tiền lệ tốt sẵn có (`QuyetDinhGui` + `ChuaKyKhongGuiTest`, `CauHinhCoSo` +
`CauHinhCoSoTest`): tách logic quyết định thành hàm thuần để test không cần CSDL, không cần mạng.

| Test | Loại | Kiểm điều gì |
|---|---|---|
| `CtdtNhanDienDichVuTest` | thuần | Thẻ gốc → dịch vụ; thẻ lạ → ném lỗi, không đoán |
| `CtdtDoiChieuLoaiHoSoTest` | thuần | `GIAYDIEUTRINOITRU` ↔ `CTGiayDieuTriNoiTru` khớp; lệch → lỗi |
| `CtdtMaHoSoTest` | thuần | Thứ tự khóa: `MA_GBT`/`MA_GCS` → `MA_YTE` → GUID; hồ sơ chỉ có CT04 rơi đúng nhánh lùi |
| `CtdtSoLuongHoSoTest` | thuần | `SOLUONGHOSO` đọc bằng `(int)(string)`, không phải `count()` |
| `CtdtQuyetDinhGuiTest` | thuần | 4 nhánh, **đúng thứ tự** — tắt gửi phải thắng "còn lỗi" và "chưa ký" |
| `CtdtCheckerTest` | thuần | 10 quy tắc chung + quy tắc riêng từng loại |
| `CtdtSubmitBodyTest` | thuần | Body đủ 7 khóa PL02; `token` và `username` cùng một nguồn |
| `CtdtRetry401Test` | thuần | `401` → xóa cache token, thử lại đúng một lần, không lặp vô hạn |
| `CtdtImporterTest` | tích hợp | Nạp lại ghi đè sạch; một `HOSO` hỏng không kéo `HOSO` khác |

### Bẫy hạ tầng test đã biết

- Mockery vỡ khi mock phương thức có khai báo kiểu trả về.
- `Cache::put` trong dự án này tính bằng **phút**.
- Repo **đang có sẵn test đỏ** — chạy `phpunit` trước khi bắt đầu để phân biệt đỏ cũ với đỏ mới.
- `route:list` đang chết — không dùng để kiểm tra route đã đăng ký.

---

## 8. Rủi ro

| Rủi ro | Mức | Cách xử |
|---|---|---|
| Hồ sơ chỉ có CT04/CT06/CT07 không ghi đè được (không có `MA_YTE`) | Cao | Chấp nhận, cảnh báo trên màn danh sách. Không bịa khóa. |
| PL02 **không mô tả dịch vụ tra cứu kết quả** — chỉ có `MaGD` trả lúc gửi | Cao | Ta chỉ biết cổng *đã nhận*, không biết cổng *đã duyệt*. Lưu `MaGD` để tra thủ công. **Cần hỏi BHXH có API tra cứu không.** |
| Chưa nghiệm thu tay lần nào trên máy chủ mới (vấn đề đang treo của XML3176) | Cao | Bắt buộc có bước nghiệm thu tay ở Giai đoạn 4 |
| Tài liệu tự mâu thuẫn về `MACSKCB` trong `FILEHOSO` | Trung bình | Đọc nếu có, lùi về `THONGTINDONVI`, ghi log |
| `1001` — tệp quá dài, tài liệu không nói ngưỡng | Trung bình | Ghi kích thước base64 mỗi lần gửi để tự dò ngưỡng |
| Ký số phụ thuộc USB token cắm trên máy chủ | Trung bình | `sign_method` ghi lại cách ký; job ký tách riêng |
| Giới hạn 128MB/120s của máy chủ | Trung bình | Mọi việc nặng nằm trong job (mục 6.4) |

---

## 9. Thứ tự triển khai

Năm giai đoạn, mỗi giai đoạn chạy được và kiểm được. Có thể dừng ở bất kỳ giai đoạn nào mà hệ
thống vẫn dùng được. Giai đoạn 1–3 không đụng tới cổng BHXH nên rủi ro bằng 0.

| GĐ | Nội dung | Sản phẩm |
|---|---|---|
| 1 | **Nền dữ liệu** — 12 migration, 12 model, registry `LoaiChungTu` (9 lớp), `config/ctdt.php`, disk `exportCtdt` | Test thuần cho registry xanh. Chưa có giao diện. |
| 2 | **Nạp** — `CtdtGoiParser`, `CtdtImporter`, `CtdtImportResult`, màn upload, danh sách, chi tiết | Nạp và xem được. Chưa ký chưa gửi. |
| 3 | **Kiểm lỗi** — `CtdtChecker` (chung + 9 bộ riêng), `CheckCtdtJob`, bảng `ctdt_loi`, tab Lỗi, cột số lỗi | Biết hồ sơ nào đủ điều kiện gửi. |
| 4 | **Ký & gửi** — `CtdtSubmitService`, `SignCtdtJob`, `SubmitCtdtJob`, `QuyetDinhGui` mở rộng, retry-on-401, nút gửi lại | **Nghiệm thu tay trên môi trường thật với `submit_enabled` bật cho một hồ sơ.** |
| 5 | **Vận hành** — Console `ctdt:import`, xuất Excel, dashboard | Chạy tự động không cần người trực. |

---

## 10. Phụ lục — đối chiếu với XML3176

Những chỗ module này **cố tình làm khác**, kèm lý do:

| Điểm | XML3176 | Module này | Lý do |
|---|---|---|---|
| Tổ chức lớp | Một `Xml3176Service` 1843 dòng chứa mọi `storeXxx()` | Mỗi loại là một lớp nhỏ tự mô tả | Ba dịch vụ × 9 loại; gộp sẽ phình gấp bội |
| Trạng thái | `xml3176_informations` gộp hồ sơ và trạng thái vào `ma_lk` | Tách `ctdt_ho_so` (trạng thái) và `ctdt_chung_tu` (nội dung) | Cổng cấp `MaGD` cho gói, nội dung thuộc từng chứng từ |
| Tab chi tiết | Cố định XML1–15 | Sinh động theo loại thực có | Một hồ sơ hiếm khi có đủ 9 loại |
| Ký và gửi | Gộp trong `SubmitXml3176Job` | Tách `SignCtdtJob` + `SubmitCtdtJob` | Hai loại lỗi khác nhau, không nên chia chung `tries` |
| Parse lúc upload | Parse ngay trong request | Lưu tệp rồi dispatch | Giới hạn 128MB/120s |
| Retry 401 | Không có | Có, đúng một lần | Token cache theo cơ sở, hết hạn giữa chừng là thường |
| Danh mục lỗi | Bảng `xml3176_error_catalogs` (BHXH ban hành) | `config/ctdt.php` | Mã lỗi ở đây do ta tự định nghĩa |

Những chỗ **giữ nguyên khuôn** vì đã được kiểm chứng: điểm vào nạp duy nhất · một hồ sơ một
transaction · dispatch job sau commit · `QuyetDinhGui` kiểm cờ trước · lớp kết quả nạp
(`ImportResult`/`ImportFileResult`) · bộ ba màn hình · route trong group `checkrole:xml-man`.

---

## 11. Ghi chú chuyển tiếp — kết thúc Giai đoạn 1

Giai đoạn 1 hoàn tất trên nhánh `feature/ctdt-pl02-giai-doan-1` (12 commit, `f2e0b75..bf0c0e8`):
12 bảng, 12 model, 9 lớp loại chứng từ, registry, cấu hình, disk `exportCtdt`, và 57 test đơn vị
xanh. Suite `Unit` giữ đúng mức đỏ có sẵn của repo (4 lỗi + 7 đỏ, không liên quan module này).

### 11.1. Điểm đã hoãn có chủ đích

| Điểm | Vì sao hoãn |
|---|---|
| `CtdtLoaiRegistry::cho()` ném `InvalidArgumentException` còn `xacNhanTheGoc()` ném `RuntimeException` | Giai đoạn 2 nên gom về một `CtdtLoaiException` chung. Nếu importer chỉ bắt một loại, loại kia sẽ thoát ra và **kéo đổ cả gói XML** — trái nguyên tắc "một `HOSO` hỏng không kéo `HOSO` khác" ở mục 5.3. Quyết định lúc viết importer, không phải bây giờ. |
| `DocThe` nằm trong namespace `App\Services\Ctdt\Loai` | Khi Giai đoạn 2 thêm namespace parser thật, tiện ích đọc XML này sẽ lạc chỗ. Dời cùng lúc, tránh đổi import hai lần. |
| `config/ctdt.php` chưa có danh mục `ma_loi` | Thuộc Giai đoạn 3 (bộ kiểm), theo mục 4.7. |
| `ctdt_loi.ma_loi` và `ctdt_loi.muc_do` để `NOT NULL` | Hai cột phân loại do chính bộ kiểm sinh ra, không phải dữ liệu nạp từ XML. `NULL` ở đó nghĩa là bộ kiểm có lỗi — chặn ở tầng CSDL là đúng chỗ. |
| Khóa ngoại chưa được kiểm bằng test | Laravel 5.5 không bật `PRAGMA foreign_keys` cho SQLite (tùy chọn `foreign_key_constraints` có từ 5.7), nên ràng buộc khóa ngoại **không được thực thi trong test**. Ràng buộc `unique` thì có kiểm thật. Đừng viết test khẳng định cascade — nó cho cảm giác an tâm giả. |

### 11.2. Sáu rủi ro cần xử khi viết Giai đoạn 2

1. **Nhận dạng chứng từ khi nạp lại — nặng nhất.** `ctdt_chung_tu` không có ràng buộc unique nào
   ngoài `id`, và ba trên chín loại (CT04, CT06, CT07) trả `maChungTu() === null`. Do đó **không
   dùng được `updateOrCreate` theo `ma_chung_tu`**; chiến lược an toàn duy nhất là xóa sạch chứng
   từ của hồ sơ rồi tạo lại, trong một transaction, xóa `ctdt_loi` của hồ sơ đó trước.
2. **Không có cột trạng thái duy nhất.** Trạng thái trải trên `imported_at` / `checked_at` /
   `is_signed` + `signed_at` / `submitted_at` / `so_loi`, và `is_signed` có thể mâu thuẫn với
   `signed_at` khi ký hỏng giữa chừng. Chốt **một** scope/hàm định nghĩa trạng thái trước khi
   viết importer, đừng để mỗi màn tự suy diễn.
3. **`LoaiChungTu` là hợp đồng tĩnh hoàn toàn.** Nếu parser cần bơm phụ thuộc (bảng ánh xạ ICD,
   cấu hình cơ sở, logger) thì không có chỗ bơm. Để parser là **lớp riêng nhận mô tả từ**
   `LoaiChungTu`, đừng thêm `parse()` vào chính interface.
4. **`noi_dung_goc` nhân đôi lưu trữ.** Mỗi chứng từ giữ XML nguyên văn trong CSDL, đồng thời
   tệp gốc nằm trên disk `exportCtdt`. Với giới hạn PHP 128MB/120s của máy chủ mới, phải chunk
   và giải phóng `SimpleXMLElement` sau mỗi `FILEHOSO`.
5. **`submit_error` là `string(255)` có index, MySQL strict mode.** Một `ConnectException` của
   Guzzle dài quá 255 ký tự đưa thẳng vào cột này sẽ ném `Data too long` — tức là **việc ghi
   lại lỗi lại tự nó thất bại**, và hồ sơ mất luôn dấu vết. Toàn văn phản hồi phải vào
   `submitted_message` (`text`); `submit_error` chỉ nhận mã hoặc tóm tắt đã `mb_substr`.
6. **Khóa mảng `config('ctdt.ma_ket_qua')` bị PHP ép thành `int`.** `'200' => ...` thành khóa
   `int(200)`, nên `$ma === $phanHoi['MaKetQua']` luôn trượt. Tra bằng `array_key_exists()`.
   Cảnh báo đã ghi ngay trên mảng trong `config/ctdt.php`.
