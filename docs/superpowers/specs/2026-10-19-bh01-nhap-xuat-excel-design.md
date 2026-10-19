# Thiết kế: Nhập/xuất Excel cho Hồ sơ tổng hợp Mẫu 01/BH — kênh dự phòng

Ngày: 2026-10-19
Trạng thái: chờ chủ dự án duyệt
Bổ sung cho: `docs/superpowers/specs/2026-10-19-ho-so-tong-hop-01bh-design.md` (gọi tắt "thiết kế
gốc"). Mọi điều thiết kế gốc đã chốt vẫn giữ, trừ chỗ ghi rõ bên dưới.

## 1. Mục tiêu và lý do

Thiết kế gốc chỉ có **một** nguồn lập bản 01/BH: dựng từ `xml3176_xml1s`. Chủ dự án cần một
**kênh dự phòng** để lập bản khi không muốn lấy từ XML3176, rồi ký và gửi cổng như thường.

Kênh dự phòng là **nhập một tệp `.xlsx`**. Trong thực tế tệp thường do HIS (hisnguonmo, chức năng
xuất `HSTH01BH.xlsx`) sinh ra, nhưng hệ thống **coi như người dùng tự soạn**: không giả định gì về
nguồn gốc, kiểm như dữ liệu tay.

Kèm theo là **xuất Excel** các dòng của một bản, cùng định dạng mà nhập nhận được — để xem, đối
chiếu, hoặc sửa rồi nhập lại thành bản mới.

**Rủi ro chủ dự án chấp nhận:** bản nhập từ Excel không bám XML3176, nên có thể **không khớp
Bảng kê chi tiết** đã gửi cổng; cổng sẽ phản hồi sai lệch (TT12 Điều 9 khoản 2 điểm c). Trách
nhiệm khớp số liệu chuyển sang người lập tệp.

## 2. Phạm vi

**Trong phạm vi**

1. **Nhập Excel → tạo bản** cho (cơ sở, kỳ) đang chọn. Bản tạo ra đi tiếp đúng luồng ký / tải
   tệp / gửi cổng / xác nhận kỳ đã nhận của thiết kế gốc.
2. **Xuất Excel** các dòng của một bản bất kỳ (nguồn XML3176 hay Excel).
3. **Tải tệp mẫu** chỉ có dòng tiêu đề.
4. Danh sách bản hiện **nguồn** của từng bản.

**Ngoài phạm vi**

- Sửa dòng trực tiếp trên màn hình.
- Nhập tệp `.xls`, `.csv`, hay tệp XML đã ký từ HIS.
- Gộp/lọc tệp lẫn nhiều kỳ hoặc nhiều cơ sở (bị từ chối — mục 4.3).
- Hồ sơ điều chỉnh (vẫn ngoài phạm vi như thiết kế gốc).

**Chống mục tiêu**

- Tệp lẫn kỳ hoặc lẫn cơ sở mà vẫn tạo được bản (dù chỉ phần đúng).
- Mã có số 0 đứng đầu (`01929`, `01`, `09`) bị Excel biến thành số rồi đi vào XML dưới dạng
  `1929`, `1`, `9`.
- Bản nguồn Excel bị gắn cảnh báo "dữ liệu XML3176 đã thay đổi" (vô nghĩa với nó).

## 3. Định dạng tệp

Một định dạng duy nhất cho cả nhập, xuất và tệp mẫu — **trùng tệp `HSTH01BH.xlsx` của
hisnguonmo** (đã đọc mã nguồn nhánh `origin/Develop`, `UCExportXml_ExcelTT12.cs`):

- Sheet đầu tiên. hisnguonmo đặt tên `C79`; nhập **không** kiểm tên sheet, xuất đặt tên `C79`.
- **Dòng 1 là tiêu đề**: đúng 20 tên thẻ của thiết kế gốc mục 2.3, thứ tự
  `STT, HO_TEN, NGAY_SINH, GIOI_TINH, MA_THE_BHYT, MA_BENH_CHINH, NGAY_VAO, NGAY_VAO_NOI_TRU,
  NGAY_RA, SO_NGAY_DTRI, MA_LOAI_KCB, T_TONGCHI_BV, T_TONGCHI_BH, T_BHTT, T_BNCCT, T_BNTT,
  T_NGUONKHAC, MA_CSKCB, NAM_QT, THANG_QT`.
- Từ dòng 2: mỗi dòng một hồ sơ.

**Khi xuất**, mọi ô ghi dạng **chữ** (text), kể cả cột số — để mở bằng Excel rồi lưu lại không
mất số 0 đứng đầu, không đổi tiền sang ký hiệu khoa học.

## 4. Nhập

### 4.1 Luồng

```
Chọn cơ sở + kỳ (khối hiện có) → chọn tệp .xlsx → [Nhập từ Excel]
   │  (1) đọc tiêu đề, ánh xạ cột theo TÊN
   │  (2) đọc theo lô, chuẩn hoá ô (4.4), kiểm (4.5)
   │      kiểm phạm vi cơ sở/kỳ (4.3) trên TỪNG dòng
   │  lỗi → dừng, báo lỗi, KHÔNG tạo bản
   v
bh01_ban (nguon = excel, ten_tep_nguon, trạng thái chờ ký) + bh01_dong (ma_lk rỗng)
   │  (3) xếp job ký như bản tạo từ XML3176 (Bh01XepHang::xepKyBanMoi)
   v
luồng ký / tải / gửi của thiết kế gốc, không đổi
```

- Không có bước "xem trước" riêng cho nhập: tệp đã nằm trong tay người dùng; kết quả kiểm và
  số liệu tổng hiện ngay trong thông báo sau khi nhập (thành công hay thất bại).
- Giữ nguyên các chốt của nút "Tạo bản": chức năng ký tắt → từ chối; cơ sở phải thuộc
  `DanhSachCoSo::danhSach()`; kỳ ≥ 04/2026; hiện trạng thái "đang xử lý" trong lúc chạy.

### 4.2 Tiêu đề

- So khớp tên cột **không phân biệt hoa thường, bỏ khoảng trắng hai đầu**.
- Phải có đủ 20 tên thẻ, không trùng tên. Thiếu hoặc trùng → từ chối, nêu tên cột thiếu/trùng.
- Cột thừa (tên khác 20 thẻ) → bỏ qua, nêu trong thông báo thành công.
- Thứ tự cột tuỳ ý (nhận diện theo tên).

### 4.3 Phạm vi cơ sở và kỳ

Sau chuẩn hoá (4.4), **mọi dòng** phải có `MA_CSKCB` = cơ sở đang chọn, `NAM_QT` + `THANG_QT` =
kỳ đang chọn. Có dù một dòng khác → **từ chối cả tệp**. Thông báo liệt kê các cặp
(cơ sở, kỳ) lạ tìm thấy kèm số dòng mỗi cặp và tối đa 20 số dòng Excel đầu tiên — đủ để người
dùng mở tệp ra sửa.

(Quyết định của chủ dự án: lẫn thì từ chối, không lọc.)

### 4.4 Chuẩn hoá ô

Dùng `Tt12DocExcel` (đọc theo lô qua `CatalogChunkImport`, đã đo trên tệp thật 52 nghìn dòng)
và `Tt12DocExcel::chuanHoaO()` cho từng ô, rồi thêm:

| Thẻ | Quy tắc thêm | Lý do |
|---|---|---|
| `MA_CSKCB` | toàn chữ số và < 5 ký tự → đệm `0` bên trái đủ 5 | Excel đổi `01929` thành `1929` |
| `MA_LOAI_KCB`, `THANG_QT` | toàn chữ số và 1 ký tự → đệm đủ 2 | `01`→`1`, `09`→`9` |
| 6 thẻ `T_*` | qua `Bh01ChuanHoa::tien()` khi là số; không phải số → giữ nguyên chuỗi để bộ kiểm **chặn** | ô tiền có thể là số thực |
| `MA_THE_BHYT` | lấy thẻ đầu tiên trước `;` (như thiết kế gốc 4.2) | thống nhất hai nguồn |
| `NGAY_*` | `chuanHoaO` đã đổi số thực nguyên thành chuỗi; ô kiểu Ngày của Excel → chặn (không đoán giờ phút) | ngày 01/BH là 12 ký tự `yyyyMMddHHmm` |
| `STT` | **bỏ qua giá trị trong tệp**; đánh lại 1..n theo thứ tự dòng trong tệp | đặc tả: STT tăng liên tục trong một lần gửi |

*Quyết định của người thiết kế*: **giữ thứ tự dòng của tệp**, không sắp lại theo mục I–IV —
người soạn tệp chủ động thứ tự; sắp lại sẽ làm STT trên bản khác STT họ thấy trong tệp.

Ghi chú: `chuanHoaO` đổi `DateTime` thành `Ymd` (8 ký tự) — vì vậy ô kiểu Ngày cho ra 8 ký tự và
bị luật "ngày không đủ 12 chữ số" chặn, đúng ý ở bảng trên.

### 4.5 Kiểm

Dùng **đúng** `Bh01Kiem::dong()` như bản dựng từ XML3176 (chặn / cảnh báo, ruling R1 của thiết kế
gốc). Có dòng bị chặn → từ chối cả tệp, báo tối đa 20 lỗi đầu tiên dạng
`"Dòng <số dòng Excel>: <lý do>"`. Kiểm phạm vi 4.3 tính là lỗi chặn.

Tệp không có dòng dữ liệu nào → từ chối ("Tệp không có hồ sơ nào").

Giới hạn: tối đa **100.000 dòng** dữ liệu một tệp (kỳ lớn nhất thật hiện ~21 nghìn). Tệp lớn hơn
`upload_max_filesize` (40 MB trên máy dev) bị PHP chặn trước — thông báo phải nói rõ quá cỡ tệp.

### 4.6 Ghi

- Hai lượt như `Bh01TaoBan` của thiết kế gốc: lượt 1 đọc + kiểm toàn tệp (không ghi gì); lượt 2
  trong **một transaction** ghi `bh01_ban` + `bh01_dong` theo lô. Đọc tệp hai lần được vì tệp
  tải lên nằm trên đĩa.
- Số bản: chung dãy số với bản nguồn XML3176 của cùng (cơ sở, kỳ) — cùng cơ chế `soBanKeTiep` +
  thử lại khi trùng khoá.
- `bh01_dong.ma_lk` = rỗng (tệp không có `MA_LK`).
- Tổng tiền của bản tính từ chính các dòng đã ghi (như thiết kế gốc).
- `id_ds_chitiet` sinh một lần lúc tạo.
- Sau khi ghi xong: `Bh01XepHang::xepKyBanMoi()` như nút "Tạo bản".
- **Lưu lại tệp tải lên** trên đĩa `exportTt12`, đường dẫn `BH01/nguon/<ma_ban>.xlsx`, cột
  `duong_dan_tep_nguon` — *quyết định của người thiết kế*. Lý do: thiết kế gốc mục 3 chống việc
  "một `maGiaoDich` mà không chỉ ra được đúng tệp và đúng danh sách `ma_lk` đã gửi"; bản Excel
  không có `ma_lk`, nên dấu vết thay thế là **chính tệp người dùng nộp** cùng `bh01_dong` và tệp đã
  ký. Chỉ ghi tệp khi transaction tạo bản đã commit; nhập hỏng thì không giữ gì. Không xoá tệp
  qua giao diện.

## 5. Xuất

- Nút **"Xuất Excel"** trên mỗi dòng của danh sách bản (mọi trạng thái).
- Nội dung: 20 cột theo mục 3, từ `bh01_dong` theo `stt` — tức đúng giá trị đã (hoặc sẽ) in vào
  XML, không phải giá trị XML3176 hiện tại.
- Ghi **theo luồng** bằng Spout (khuôn `App\Services\ExcelLuong\GhiExcelLuong` — PhpSpreadsheet
  giữ mọi ô trong RAM, 21 nghìn dòng × 20 cột vượt 128 MB). Chạy ngay trong request (cỡ vài giây
  với 21 nghìn dòng; người làm đo và ghi lại), trả tệp tải về.
- Tên tệp: `<ma_ban>.xlsx`.
- Tệp mẫu: route riêng, chỉ dòng tiêu đề, tên `HSTH01BH_mau.xlsx`.

## 6. Dữ liệu

Migration mới thêm vào `bh01_ban`:

| Cột | Kiểu | Ý nghĩa |
|---|---|---|
| `nguon` | string(10), mặc định `'xml3176'` | `'xml3176'` hoặc `'excel'` |
| `ten_tep_nguon` | string nullable | tên tệp gốc người dùng tải lên |
| `duong_dan_tep_nguon` | string nullable | đường dẫn tệp gốc đã lưu trên đĩa `exportTt12` |

Bản đã có giữ `nguon = 'xml3176'` nhờ giá trị mặc định.

## 7. Thay đổi so với thiết kế gốc

| Chỗ | Thay đổi |
|---|---|
| Thiết kế gốc mục 5.5 (cảnh báo dữ liệu đã đổi) | chỉ tính cho bản `nguon = 'xml3176'`; bản Excel luôn `false` |
| Mục 5.5 "bản mới nhất của kỳ" | so với bản `xml3176` mới nhất của kỳ, bỏ qua bản Excel |
| Thiết kế gốc mục 5.4 (hộp xác nhận kỳ đã được cổng nhận) | **tính chéo mọi nguồn**: kỳ đã có bất kỳ bản nào được trả `200` (XML3176 hay Excel) thì gửi bản khác phải xác nhận; câu xác nhận **nêu nguồn** của bản đã nhận và của bản sắp gửi, vd "…đã tiếp nhận ở bản 1 (nguồn XML3176, mã GD …). Gửi bản 2 (nguồn Excel)?" — *quyết định của người thiết kế*: gửi chồng bản Excel lên bản XML3176 là chỗ dễ nhầm nhất |
| Danh sách bản | thêm cột "Nguồn" (XML3176 / Excel + tên tệp khi rê chuột) và nút "Xuất Excel" |
| Màn hình | khối "Lập bản mới" thêm ô chọn tệp, nút "Nhập từ Excel", liên kết "Tải tệp mẫu" |

## 8. Mã nguồn

**Dùng lại:** `Tt12DocExcel` (đọc lô + `chuanHoaO`), `Bh01ChuanHoa::tien()`, `Bh01Kiem`,
`Bh01DacTa::the()` (tên và thứ tự 20 cột — một nguồn duy nhất cho tiêu đề nhập/xuất/mẫu),
`Bh01XepHang::xepKyBanMoi()`, `Bh01Ky`, `DanhSachCoSo`, khuôn `GhiExcelLuong`/Spout.

**Mới** (tên cụ thể do người làm chọn, giữ tiền tố `Bh01`):

- Lớp nhập: đọc tiêu đề + chuẩn hoá ô (4.4) + kiểm phạm vi (4.3) + ghi bản (4.6). Phần ghi chia sẻ
  với `Bh01TaoBan` (số bản, transaction, tổng tiền) thay vì chép lại — người làm chọn cách tách.
- Lớp xuất Excel một bản và tệp mẫu.
- Route: POST nhập (multipart, có CSRF), GET xuất theo `{ma_ban}`, GET tệp mẫu. Route tĩnh đặt
  trước route có tham số như các route bh01 hiện có.

**Quyền:** như các thao tác bh01 hiện có (menu `xml-man`, không middleware riêng).

## 9. Nghiệm thu

- Nhập tệp HIS mẫu (20 cột, sheet `C79`, giá trị chữ) → bản `nguon = excel`, STT 1..n theo thứ tự
  tệp, `ma_lk` rỗng, tổng tiền đúng, job ký được xếp.
- Tệp có cột đổi thứ tự / tên cột chữ thường / có cột thừa → vẫn nhập được.
- Thiếu một cột, trùng cột → từ chối, nêu tên cột.
- Một dòng khác kỳ, một dòng khác cơ sở → từ chối cả tệp, nêu cặp lạ + số dòng.
- Ô số `1929`, `1`, `9` → thành `01929`, `01`, `09`; ô tiền số thực `1234.567` → `1234.57`; ô
  ngày kiểu Ngày → chặn.
- Dòng bị chặn → từ chối, báo "Dòng N: …" theo số dòng Excel.
- Không có dòng dữ liệu → từ chối.
- Xuất một bản → mở lại bằng chính chức năng nhập được bản mới có 20 trường từng dòng y hệt bản cũ
  (vòng xuất → nhập không mất dữ liệu, kể cả số 0 đứng đầu).
- Bản nguồn Excel không bao giờ có cờ "dữ liệu đã đổi"; bản XML3176 vẫn có như cũ.
- Đo trên máy dev: nhập và xuất một tệp ~21 nghìn dòng trong giới hạn 60 giây / 128 MB.
