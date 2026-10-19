# Thiết kế: Hồ sơ tổng hợp Mẫu 01/BH (TT12/2026/TT-BTC) — xuất có chữ ký và gửi cổng BHXH

Ngày: 2026-10-19
Trạng thái: chờ chủ dự án duyệt

## 1. Mục tiêu và lý do

Mỗi tháng, cơ sở KCB phải gửi **Bảng tổng hợp đề nghị thanh toán chi phí KCB BHYT** (Mẫu
số 01/BH) lên Cổng tiếp nhận dữ liệu BHXH, trong 15 ngày đầu tháng, có ký số (TT12/2026/TT-BTC
Điều 5 khoản 1 điểm b, Điều 9 khoản 1). Cổng **đối chiếu Bảng tổng hợp với Bảng kê chi tiết**
(bộ XML3176) và phản hồi trong 48 giờ nếu sai lệch (Điều 9 khoản 2 điểm c).

Tiền giám định đã giữ toàn bộ XML1 của XML3176 trong `xml3176_xml1s`. Hướng dẫn lập Mẫu
01/BH (Phụ lục I) chỉ đích danh mọi cột của 01/BH vào chỉ tiêu Bảng 1 QĐ3176 — tức 01/BH là
**bản chép các trường XML1**, không phải số liệu tính lại. Vì vậy Tiền giám định tự dựng 01/BH
từ chính XML1 đó: một nguồn số liệu, 01/BH khớp Bảng kê chi tiết theo cách dựng.

Phương án đã cân nhắc và bác: nhập tệp `HSTH01BH_*.xml` do HIS (hisnguonmo, plugin
`ExportXmlQD130`) sinh ra rồi ký/gửi. Bác vì HIS tính số liệu thẳng từ CSDL HIS chứ không từ
bộ XML đã gửi, nên có thể lệch Bảng kê chi tiết; tệp HIS không có `MA_LK` nên đối chiếu được
phải dò theo thẻ + ngày, và tệp có thể lẫn nhiều kỳ.

## 2. Căn cứ

- **TT12/2026/TT-BTC** ngày 10/02/2026 — Điều 5, Điều 9, Điều 17 và "Hướng dẫn lập Mẫu số
  01/BH" ở Phụ lục I. Toàn văn: <https://luatvietnam.vn/y-te/thong-tu-12-2026-tt-btc-quy-dinh-giam-dinh-chi-phi-kham-chua-benh-bao-hiem-y-te-426504-d1.html>.
- **Tài liệu kỹ thuật TT12** (BHXH), mục VIII "Hồ sơ tổng hợp mẫu 01/BH": endpoint, body, cấu
  trúc XML, đặc tả 20 thẻ. Tệp gốc: `Tài liệu kỹ thuật_TT12-2026-BTC.pdf` (trang 55–63).

### 2.1 Những gì văn bản nói rõ

| Nội dung | Nguồn |
|---|---|
| Lập hằng tháng, gửi trong 15 ngày đầu tháng, ký số | Điều 9 khoản 1 |
| Biểu mẫu áp dụng từ 01/04/2026; quý 1/2026 dùng mẫu cũ | Điều 17 khoản 1, 2 |
| Cổng phản hồi trong 48 giờ nếu 01/BH lệch Bảng kê chi tiết | Điều 9 khoản 2 điểm c |
| Từng cột lấy từ chỉ tiêu Bảng 1 QĐ3176 (bảng 2.2) | Hướng dẫn lập 01/BH, mục 4c |
| Chia hiển thị 4 mục theo `MA_LOAI_KCB`: I (01,06,07), II (02,05,08), III (04,09), IV (03) | Hướng dẫn lập 01/BH, mục 4b |
| Tiền **hiển thị** đến đơn vị đồng (trong XML vẫn 2 chữ số thập phân — tài liệu kỹ thuật) | Hướng dẫn lập 01/BH, mục 4d |

### 2.2 Ánh xạ cột (nguyên văn hướng dẫn lập)

| Thẻ 01/BH | Cột XML1 | Chỉ tiêu QĐ3176 |
|---|---|---|
| HO_TEN, NGAY_SINH, GIOI_TINH, MA_THE_BHYT, MA_BENH_CHINH, NGAY_VAO, NGAY_VAO_NOI_TRU, NGAY_RA | cùng tên, chữ thường | Bảng 1 |
| SO_NGAY_DTRI | `so_ngay_dtri` | 39 |
| T_TONGCHI_BV | `t_tongchi_bv` | 47 |
| T_TONGCHI_BH | `t_tongchi_bh` | 48 |
| T_BNTT | `t_bntt` | 49 |
| T_BNCCT | `t_bncct` | 50 |
| T_BHTT | `t_bhtt` | 51 |
| T_NGUONKHAC | `t_nguonkhac` | 52 |
| MA_LOAI_KCB, MA_CSKCB, NAM_QT, THANG_QT | cùng tên | Bảng 1 |

### 2.3 Đặc tả 20 thẻ `CHITIET_HS01BH` (tài liệu kỹ thuật mục VIII.5, chép lại)

Đúng thứ tự in trong XML.

| # | Thẻ | Kiểu | Tối đa | Mô tả rút gọn |
|---|---|---|---|---|
| 1 | STT | Số | 10 | Tăng từ 1 đến hết trong một lần gửi |
| 2 | HO_TEN | Chuỗi | 255 | Họ tên người bệnh |
| 3 | NGAY_SINH | Chuỗi | 12 | `yyyyMMddHHmm` theo thẻ BHYT |
| 4 | GIOI_TINH | Chuỗi | 1 | 1 Nam, 2 Nữ, 3 Chưa xác định |
| 5 | MA_THE_BHYT | Chuỗi | 15 | Mã thẻ BHYT do BHXH cấp |
| 6 | MA_BENH_CHINH | Chuỗi | 7 | ICD-10 |
| 7 | NGAY_VAO | Chuỗi | 12 | `yyyyMMddHHmm` |
| 8 | NGAY_VAO_NOI_TRU | Chuỗi | 12 | `yyyyMMddHHmm`, nội trú/điều trị ban ngày |
| 9 | NGAY_RA | Chuỗi | 12 | `yyyyMMddHHmm` |
| 10 | SO_NGAY_DTRI | Chuỗi | 3 | Theo quy tắc QĐ3176 (đã tính sẵn trong XML1) |
| 11 | MA_LOAI_KCB | Số | 2 | Bộ mã DMDC |
| 12 | T_TONGCHI_BV | Số | 15 | 2 chữ số thập phân, dấu chấm |
| 13 | T_TONGCHI_BH | Số | 15 | như trên |
| 14 | T_BHTT | Số | 15 | như trên |
| 15 | T_BNCCT | Số | 15 | như trên |
| 16 | T_BNTT | Số | 15 | như trên |
| 17 | T_NGUONKHAC | Số | 15 | như trên |
| 18 | MA_CSKCB | Chuỗi | 5 | (tài liệu in nhầm `MA_CƠ SỞ KCB`, xem mục 6) |
| 19 | NAM_QT | Số | 4 | Năm đề nghị thanh toán |
| 20 | THANG_QT | Số | 2 | Tháng đề nghị thanh toán |

Mọi thẻ đều trùng định dạng với cột tương ứng của XML1 QĐ3176 (đã kiểm trên dữ liệu thật:
`ngay_sinh`, `ngay_vao` đều 12 ký tự ở 100% hồ sơ BHYT kỳ 09–10/2026), nên "chép nguyên"
không cần đổi định dạng ngày hay giới tính.

### 2.4 Những gì văn bản KHÔNG nói, và cách đã chốt

- **Hồ sơ nào thuộc tháng nào**: không có câu trực tiếp. `NAM_QT`/`THANG_QT` được định nghĩa
  là "năm/tháng cơ sở KBCB đề nghị cơ quan BHXH thanh toán". Đã chốt: bảng tổng hợp kỳ M gồm
  các hồ sơ XML1 có `nam_qt`/`thang_qt` = M (mục 4.1).
- **Gửi lại một kỳ thì cổng thay bản cũ hay cộng thêm**: không văn bản nào nói. Thiết kế không
  phụ thuộc điều này (mục 5.4).

## 3. Phạm vi

**Trong phạm vi**

1. Chức năng **Xuất 01/BH có chữ ký**: chọn cơ sở + kỳ → dựng XML → ký → tải tệp về. Mỗi lần
   xuất là một **bản** đóng băng, giữ lại trên hệ thống.
2. Chức năng **Gửi cổng**: nút bấm trên từng bản, gửi đúng tệp đã ký của bản đó.
3. Danh sách bản theo cơ sở/kỳ, nhật ký các lần gửi, xem các dòng của một bản.

**Ngoài phạm vi** (không làm ở phiên này)

- Quy trình **hồ sơ điều chỉnh** theo Điều 9 khoản 3 (văn bản ghi số liệu sửa, so sánh bản).
  Gửi lại chỉ là bấm gửi thêm lần nữa (mục 5.4).
- Mẫu 02/BH (báo cáo quyết toán quý), Mẫu 09/BH (bảng kê điều chỉnh).
- Gửi **tự động**: không xếp chuỗi sau kiểm/gửi XML3176, không lịch chạy.
- Gộp nhiều cơ sở trong một bảng (Phần A/B): 01929 và 37470 coi như **hai hợp đồng riêng**,
  mỗi cơ sở lập và gửi riêng.
- Kỳ trước 04/2026 (Điều 17).
- Nhập tệp 01/BH do HIS sinh.
- Việc Bảng kê chi tiết (XML3176) của kỳ đã lên cổng hay chưa, và gửi bằng đường nào (Tiền giám
  định hay HIS): **không phải mối quan tâm** của chức năng này.

**Chống mục tiêu** — coi là hỏng dù chạy được:

- Một `maGiaoDich` mà không chỉ ra được đúng tệp và đúng danh sách `ma_lk` đã gửi.
- Bấm gửi lần hai mà tệp gửi đi khác tệp đã gửi lần một của cùng bản.
- Token của cơ sở này đi kèm `maCSKCB`/dòng dữ liệu của cơ sở kia.

## 4. Dữ liệu

### 4.1 Tập hồ sơ của một bản

```
xml3176_xml1s
WHERE ma_cskcb = :co_so
  AND nam_qt   = :nam      -- chuỗi '2026'
  AND thang_qt = :thang    -- chuỗi '10', hai ký tự
  AND ma_the_bhyt <> ''    -- chỉ hồ sơ BHYT
```

**Vì sao lọc `ma_the_bhyt <> ''`** — *quyết định của người thiết kế*: `xml3176_xml1s` chứa cả
hồ sơ không BHYT. Đo trên CSDL thật ngày 19/10/2026, kỳ 09+10/2026: 01929 có 29.726/55.598 dòng
thẻ rỗng, 37470 có 8.187/15.186; mọi dòng thẻ rỗng đều không có `t_bhtt`. Mẫu 01/BH là bảng
tổng hợp "chi phí KCB của người bệnh BHYT". Bộ lọc này trùng với bộ lọc "Có thẻ BHYT" đang có
(`Xml3176LocDanhSach::apTheBhyt`, giá trị `has_hein_card`).

Không lọc theo trạng thái kiểm lỗi, trạng thái gửi XML3176, hay trạng thái ký (đã chốt với
chủ dự án).

Cỡ thật để định cỡ: hai kỳ 09+10/2026 gộp lại có 25.872 hồ sơ BHYT (01929) và 6.999 (37470);
một kỳ đơn lẻ của 01929 cỡ 15–20 nghìn dòng.

Cột `nam_qt`/`thang_qt` hiện **không có chỉ mục**. Thêm chỉ mục ghép
`(ma_cskcb, nam_qt, thang_qt)` trên `xml3176_xml1s` (bảng lớn — migration phải chạy được
trên prod mà không khoá bảng lâu; người làm chọn cách và ghi lại).

### 4.2 Chuẩn hoá giá trị khi dựng

Nguyên tắc: **chép nguyên XML1**, chỉ đổi định dạng khi đặc tả thẻ của tài liệu kỹ thuật đòi.
Lệch giá trị so với XML1 là lệch với Bảng kê chi tiết.

| Thẻ | Quy tắc |
|---|---|
| STT | 1..n liên tục trong **cả tệp** (tài liệu kỹ thuật: "tăng từ 1 đến hết trong một lần gửi"). Bản giấy đánh lại trong từng mục — đó là chuyện hiển thị của cổng |
| MA_THE_BHYT | XML1 có thể chứa nhiều thẻ nối bằng `;` (22 hồ sơ kỳ 09–10 của 01929). Lấy **thẻ đầu tiên** — *quyết định của người thiết kế*, giống `Xml3176Xml7980aExport` và hisnguonmo |
| T_* (6 cột tiền) | Số, tối đa 2 chữ số thập phân, dấu chấm, không phân cách hàng nghìn, bỏ số 0 thừa (`1234.5`, không `1.234,50`). Null → `0` |
| SO_NGAY_DTRI | Số nguyên dạng chuỗi |
| NGAY_VAO_NOI_TRU | Rỗng thì in thẻ rỗng `<NGAY_VAO_NOI_TRU/>` — không bỏ thẻ (cùng nguyên tắc `Tt12PhongBi`) |
| Còn lại | Nguyên văn |

**Thứ tự dòng** — *quyết định của người thiết kế*: theo mục I→IV của `MA_LOAI_KCB` (mục 2.1),
trong mỗi mục theo `ngay_ra` rồi `ma_lk`. Thứ tự phải **xác định** (cùng dữ liệu → cùng tệp).

### 4.3 Kiểm trước khi ký

Kiểm theo đặc tả thẻ mục VIII.5 của tài liệu kỹ thuật. Độ dài đo bằng **ký tự**
(`mb_strlen`/`CHAR_LENGTH`), không bằng byte: mã ICD có ký tự `†` (vd `E11.4†`) dài 6 ký tự nhưng
8 byte — đo bằng byte báo nhầm ~2.100 hồ sơ kỳ 09–10.

Hai mức:

- **Chặn** (không cho ký bản): thiếu trường bắt buộc mà thiếu thì XML vô nghĩa — `HO_TEN`,
  `MA_THE_BHYT`, `NGAY_VAO`, `NGAY_RA`, `MA_LOAI_KCB`, `MA_CSKCB`; ngày không đủ 12 chữ số;
  tiền không phải số; `MA_LOAI_KCB` không thuộc `01`–`09` — *quyết định của người thiết kế*:
  không xếp được vào mục I–IV thì không có chỗ trên mẫu, và loại dòng đó ra sẽ lệch Bảng kê chi
  tiết. Dữ liệu thật kỳ 09–10/2026 chỉ có 01, 02, 03, 04, 08, 09.
- **Cảnh báo** (vẫn cho ký, hiện danh sách): vượt độ dài tối đa của đặc tả 01/BH mà **giá trị
  đúng bằng XML1 đã có**. Sửa ở 01/BH thì lệch Bảng kê chi tiết; muốn sửa phải sửa nguồn.

**Đã biết trước một ca cảnh báo**: 562 hồ sơ kỳ 09–10 có mã thẻ **17 ký tự** (vd
`HC438038070001032`), trong khi đặc tả 01/BH ghi `MA_THE_BHYT` tối đa 15. Không cắt — xem mục 9.

## 5. Luồng

```
[Xuất] chọn cơ sở + kỳ
   │  (1) Bh01DungBan: truy vấn 4.1 → chuẩn hoá 4.2 → kiểm 4.3
   │      lỗi mức "chặn" → dừng, hiện lỗi, KHÔNG tạo bản
   v
bh01_ban (trạng thái: chờ ký) + bh01_dong (ảnh chụp từng dòng)
   │  (2) SignBh01Job (hàng đợi ký): dựng XML từ bh01_dong → XMLSignService → ghi tệp
   v
bh01_ban (đã ký, có đường dẫn tệp)  ──► [Tải tệp] tải đúng tệp đã ký
   │  (3) người dùng bấm [Gửi cổng] → SubmitBh01Job (hàng đợi gửi)
   v
Tt12SubmitService (mở rộng cho loaiHs=5 + kyQT) → bh01_lich_su_gui + cập nhật bh01_ban
```

### 5.1 Xuất (tạo bản)

- Người dùng chọn **cơ sở** (bắt buộc, từ `DanhSachCoSo::danhSach()`, không có giá trị mặc
  định — cùng lý do ở `config/organization.php` khoá `tt12`) và **kỳ** (tháng/năm, từ 04/2026).
- Màn hiện **xem trước**: số hồ sơ, tổng 6 cột tiền, số hồ sơ theo 4 mục, số lỗi "chặn" và
  "cảnh báo". Người dùng bấm "Tạo bản" mới tạo.
- Tạo bản = một transaction: ghi `bh01_ban` + toàn bộ `bh01_dong`. `id_ds_chitiet`
  (`Id-<guid>`) sinh **một lần** ở đây.
- `so_ban` tăng dần trong từng (cơ sở, kỳ): bản 1, bản 2… Hai người bấm cùng lúc không được ra
  hai bản cùng số (ràng buộc duy nhất `(ma_cskcb, nam_qt, thang_qt, so_ban)`).
- Ký chạy ở hàng đợi vì tệp cỡ chục MB và dịch vụ ký (HSM/USB Token) chậm; màn hình tự làm mới
  trạng thái. Ký hỏng → bản ở trạng thái "ký lỗi", có nút "Ký lại" (dựng lại từ `bh01_dong`,
  cùng `id_ds_chitiet` → cùng nội dung).

### 5.2 Bản là ảnh chụp đóng băng

Ký lại, tải lại, gửi lại đều đi từ `bh01_dong`/tệp đã ký của bản, **không truy vấn lại XML1**.
Đây là bài học đã ghi ở `docs/superpowers/specs/2026-08-25-tt12-danh-muc-design.md` mục 4.2:
cùng một mã giao dịch mà hai nội dung khác nhau thì không giải thích được với BHXH.

### 5.3 Gửi cổng

- Nút "Gửi cổng" chỉ hiện với bản **đã ký**. Bấm → xếp `SubmitBh01Job`; không có đường nào
  khác xếp job này.
- Gửi **đúng byte tệp đã ký** (base64 của tệp trên đĩa), không dựng lại, không ký lại.
- Body: `username`, `loaiHs=5`, `maTinh` (suy từ mã cơ sở bằng `CauHinhCoSo::maTinh`),
  `maCSKCB` (tên trường lấy từ `config('tt12.truong_body')`), `fileHsBase64`, **`kyQT`** =
  `yyyymm` của bản. Header: `accessToken`, `tokenId`, `passwordHash` như TT12 danh mục.
- Token lấy theo **mã cơ sở của bản**.
- Mã 401 trong thân phản hồi → đăng nhập lại, thử lại đúng một lần (hành vi sẵn có của
  `Tt12SubmitService`). Lỗi mạng → ném, để hàng đợi thử lại.
- Mỗi lần gửi ghi một dòng `bh01_lich_su_gui`; `bh01_ban` giữ kết quả lần gửi gần nhất.

### 5.4 Gửi lại

- Gửi lại **cùng bản** (lần trước lỗi mạng, 500, 401 sau khi đã thử lại): bấm "Gửi cổng" lần
  nữa.
- Gửi **bản mới** của cùng kỳ: xuất bản mới rồi bấm gửi. Nếu kỳ đó đã có bản được cổng trả
  `200`, hiện hộp xác nhận: "Kỳ MM/YYYY của cơ sở X đã được cổng tiếp nhận ở bản N (mã GD …).
  Gửi bản M?". Không đòi nhập lý do (điều chỉnh nằm ngoài phạm vi).
- Gửi lại chính bản đã được trả `200`: cũng qua hộp xác nhận như trên.

### 5.5 Cảnh báo dữ liệu đã thay đổi

Trên danh sách, với mỗi (cơ sở, kỳ) có bản: so **số hồ sơ** và **tổng `t_tongchi_bv`,
`t_bhtt`** hiện tại của truy vấn 4.1 với bản mới nhất. Khác → hiện "Dữ liệu XML3176 của kỳ đã
thay đổi so với bản N". Chỉ cảnh báo, không tự làm gì.

## 6. XML

```xml
<?xml version="1.0" encoding="UTF-8"?>
<HSTH01BH>
  <DS_CHITIET Id="Id-<guid>">
    <CHITIET_HS01BH>
      <STT>1</STT> … 20 thẻ theo đúng thứ tự mục VIII.5 … <THANG_QT>10</THANG_QT>
    </CHITIET_HS01BH>
    …
  </DS_CHITIET>
  <CHUKYDONVI/>
</HSTH01BH>
```

- Tên thẻ cơ sở là **`MA_CSKCB`**. Tài liệu in `MA_CƠ SỞ KCB` do lỗi tìm-thay-thế hàng loạt —
  không phải tên thẻ XML hợp lệ (cùng lỗi đã ghi ở `config/tt12.php`, khoá `truong_body`).
  hisnguonmo cũng dùng `MA_CSKCB`.
- `CHUKYDONVI` **rỗng, đặt cuối**: `XMLSignService` ghi chữ ký vào thẻ có sẵn
  (`tag_store_signature_value`), không tự tạo.
- **Chữ ký**: mẫu trong tài liệu vẽ hai `Reference`. Thực tế cổng nhận **một**
  `Reference URI=""` enveloped (đo ngày 26/08/2026 trên CTĐT và TT12 danh mục). Dịch vụ ký
  quyết định dạng chữ ký; một `Reference` là đúng, đừng sửa.
- Không có khai báo namespace `xsd`/`xsi` trên gốc (mẫu tài liệu không có; khác `HSDANHMUC`).
- **Dựng tăng dần, không giữ cả cây trong bộ nhớ** (`XMLWriter` hoặc tương đương) — một bản
  20 nghìn dòng; máy chủ `qlbv_public` có `memory_limit` 128 MB. Thoát ký tự đặc biệt bắt buộc
  (họ tên có thể có `&`). Hàm dựng là hàm thuần: cùng đầu vào → cùng chuỗi byte.

## 7. Lưu trữ

Bảng **mới**, không dùng chung `tt12_ho_so`/`tt12_dong` — *quyết định của người thiết kế*.
`tt12_ho_so` gắn với luồng nạp Excel → kiểm → ký+gửi → **đồng bộ sang bảng danh mục**; dashboard
độ phủ và job đồng bộ đều đọc nó. Một hồ sơ 01/BH lọt vào đó sẽ bị coi là danh mục. Ngoài ra
01/BH có khoá nghiệp vụ khác hẳn (cơ sở + kỳ + số bản) và cần `ma_lk` từng dòng.

**`bh01_ban`** — một dòng = một bản
`id`, `ma_ban` (duy nhất, vd `BH01_01929_202610_02`), `ma_cskcb`, `nam_qt`, `thang_qt`,
`so_ban`, `so_dong`, 6 cột tổng tiền, `so_canh_bao`, `id_ds_chitiet`, `tao_boi`, `tao_luc`;
ký: `trang_thai_ky` (cho_ky | da_ky | ky_loi), `sign_method`, `signed_at`, `signed_error`,
`duong_dan_da_ky`, `sha256_tep`; gửi gần nhất: `submitted_at`, `submitted_by`, `ma_ket_qua`,
`ma_gd`, `thoi_gian_tiep_nhan`, `thong_diep`.
Duy nhất: `(ma_cskcb, nam_qt, thang_qt, so_ban)`.

**`bh01_dong`** — ảnh chụp từng dòng
`ban_id`, `stt`, `ma_lk`, 20 trường của 01/BH **đã chuẩn hoá** (đúng giá trị in ra XML),
`canh_bao` (text, nullable). Chỉ mục `(ban_id, stt)` duy nhất, `ma_lk`.

**`bh01_lich_su_gui`** — mỗi lần bấm gửi một dòng
`ban_id`, `gui_luc`, `gui_boi`, `ma_ket_qua`, `ma_gd`, `thoi_gian_tiep_nhan`, `thong_diep`,
`loi`, `nguyen_van` (phản hồi gốc, cắt ngắn hợp lý).

Tệp đã ký: đĩa `exportTt12`, thư mục con riêng cho 01/BH. `sha256_tep` ghi lúc ký; job gửi
kiểm lại băm trước khi gửi — tệp bị thay trên đĩa thì từ chối gửi.

Không xoá bản qua giao diện (không cần ở phiên này). Bản đã gửi là dấu vết đối soát.

## 8. Mã nguồn

### 8.1 Dùng lại

| Thành phần | Cách dùng |
|---|---|
| `XMLSignService::signXml` | ký, nguyên vẹn. Một chứng thư cho cả hai cơ sở là **đã kiểm chứng**: ngày 26/08/2026 hồ sơ CTĐT của cả 01929 (`HS_CHUNGTU01929_AE6612D6…`) lẫn 37470 (`HS_CHUNGTU37470_8C7A4C84…`) ký bằng cấu hình ký hiện tại đều được cổng trả `200` |
| `BHYTLoginService` | token theo mã cơ sở, nguyên vẹn |
| `Tt12SubmitService` | **mở rộng**: hiện đang khoá vào `Tt12MauRegistry` (MAU_01..06) và không gửi `kyQT`. Thêm đường gửi hồ sơ tổng hợp nhận `loaiHs`/URL từ cấu hình và `kyQT`; giữ nguyên luồng 401, đọc phản hồi, bảng mã kết quả. Đường gửi danh mục **không được đổi hành vi** |
| `config/tt12.php` | thêm khối cấu hình hồ sơ tổng hợp: `loai_hs = '5'`, `duong_dan = '/api/HoSoTongHop7980/GuiHoSoTongHop01BH'`; thêm `ky_qt => 'kyQT'` vào `truong_body` |
| `config/organization.php` khoá `tt12` | `sign_enabled`, `submit_enabled`, hàng đợi `tt12-ky`/`tt12-gui` dùng chung |
| `DanhSachCoSo`, `CauHinhCoSo::maTinh` | chọn cơ sở, suy mã tỉnh |

### 8.2 Tên gọi

**Không dùng `Mau01`**: tên đó đã là danh mục 01/DM (bộ phận chuyên môn) trong
`app/Services/Tt12/Mau/Mau01.php`. Dùng tiền tố `Bh01` (`App\Services\Tt12\Bh01\...`,
`App\Models\BHYT\Tt12\Bh01Ban`…). Người làm chọn tên cụ thể.

### 8.3 Màn hình và quyền

- Một màn mới trong menu TT12: "Hồ sơ tổng hợp 01/BH". Gồm khối chọn cơ sở + kỳ + xem trước,
  và danh sách bản (lọc theo cơ sở/kỳ) với cột trạng thái ký, kết quả gửi gần nhất, mã GD, cảnh
  báo 5.5, các nút Tải tệp / Gửi cổng / Ký lại / Xem dòng / Nhật ký gửi.
- Xem dòng của một bản: bảng phân trang theo `bh01_dong`, có cột cảnh báo.
- Quyền: như các thao tác ký/gửi TT12 danh mục hiện tại (`kyVaGui` không có middleware quyền
  riêng) — *quyết định của người thiết kế*.
- `submit_enabled = false` → nút "Gửi cổng" hiện nhưng bị khoá kèm lời giải thích, không ẩn.

## 9. Rủi ro và việc còn mở

1. **Mã thẻ 17 ký tự** (562 hồ sơ kỳ 09–10) vượt giới hạn 15 của đặc tả 01/BH. Thiết kế gửi
   nguyên văn (khớp Bảng kê chi tiết) và chỉ cảnh báo. Có thể cổng trả lỗi 205 cho các dòng
   này. Chỉ gửi thật mới biết.
2. **Tên trường body `maCSKCB`**: tài liệu lỗi tìm-thay-thế; hisnguonmo dùng `maCsKCB`, qlbv
   dùng `maCSKCB` (giữ trong `config/tt12.php`). Danh mục TT12 đã gửi được với cấu hình hiện
   tại; nếu 01/BH bị từ chối vì trường này thì chỉ sửa cấu hình.
3. **Cỡ tệp**: chưa biết ngưỡng mã 1001 (tệp quá lớn). Ghi số ký tự base64 mỗi lần gửi (như
   `Tt12SubmitService` đã làm) để đo. Không chia nhỏ bản trong phiên này.
4. **Cổng xử lý gửi lại**: không biết bản sau thay hay cộng bản trước (mục 2.4).
5. Bộ test chạy trên máy dev phải ép `DB_HOST=127.0.0.1`; cấm `RefreshDatabase` (chốt an toàn
   CSDL test của dự án).

## 10. Nghiệm thu

- Dựng XML từ một tập dòng cố định ra đúng chuỗi byte mong đợi (test hàm thuần), gồm: thẻ rỗng,
  ký tự `&`/`<` trong họ tên, mã ICD có `†`, tiền có/không phần thập phân, nhiều thẻ `;`.
- Truy vấn 4.1 trên dữ liệu mẫu: loại hồ sơ thẻ rỗng, đúng cơ sở, đúng kỳ, thứ tự xác định.
- Ký lại một bản ra cùng `id_ds_chitiet` và cùng nội dung trước chữ ký.
- Gửi dùng đúng byte tệp đã ký; `kyQT` = kỳ của bản; token và `maCSKCB` cùng cơ sở; đường gửi
  danh mục TT12 không đổi hành vi (test hiện có vẫn xanh).
- Hộp xác nhận xuất hiện khi kỳ đã có bản được trả `200`.
- Nghiệm thu tay: xuất bản kỳ 09/2026 của một cơ sở, mở tệp đã ký, đối chiếu tay vài dòng với
  XML1; gửi thật **chỉ khi chủ dự án cho phép**.
