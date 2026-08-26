# Thiết kế: Dashboard độ phủ danh mục TT12

Ngày: 2026-08-26
Trạng thái: đã thống nhất, chờ lập kế hoạch thực thi

## 1. Câu hỏi màn hình này trả lời

**Sáu mẫu danh mục của từng cơ sở, cái nào đã được cổng BHXH tiếp nhận, cái nào chưa
bao giờ gửi.**

Đây là câu hỏi tuân thủ, và hiện **không màn hình nào trả lời được**. Màn Danh sách hồ sơ
cho biết từng hồ sơ đi tới đâu, nhưng nó không nói được điều ngược lại: *thiếu* gì. Một mẫu
chưa bao giờ được nạp thì không có dòng nào trong danh sách — nó vô hình đúng nghĩa đen.

## 2. Phạm vi

**Trong phạm vi:** lưới độ phủ 6 mẫu × N cơ sở, và một dải hồ sơ đang dở dang.

**Ngoài phạm vi, ghi lại để không quên:**

- Biểu đồ theo thời gian. TT12 phát sinh vài chục hồ sơ mỗi năm; một biểu đồ đường trên
  tập đó là một khối gần như rỗng.
- Ngưỡng "quá hạn". TT12 **không quy định chu kỳ cố định** — danh mục chỉ gửi khi CÓ THAY
  ĐỔI, nên "sáu tháng chưa gửi" hoàn toàn có thể là đúng. Đặt ngưỡng là tạo báo động giả
  hàng loạt, và người dùng sẽ học cách bỏ qua màu vàng.
- Khối chất lượng dữ liệu (mã lỗi hay gặp). Có giá trị khi khối lượng lớn và lặp lại; chưa
  phải lúc này.

## 3. Quyết định đã chốt

| # | Quyết định | Lý do |
|---|---|---|
| Đ1 | Đo "đã khai" ở mức **hồ sơ gửi**, đọc `tt12_ho_so` | Xem mục 4 — bảng danh mục không phân biệt được nguồn |
| Đ2 | Không có khái niệm quá hạn, chỉ đã/chưa | TT12 không quy định chu kỳ; xem mục 2 |
| Đ3 | Dùng lại `Tt12DanhSach::truyVan()`, không viết SQL đếm mới | Xem mục 6 |
| Đ4 | Số dòng lấy từ hồ sơ được tiếp nhận **gần nhất**, không cộng dồn | Lần gửi sau thay thế lần trước; cộng dồn là đếm trùng |
| Đ5 | Trục cơ sở lấy từ `DanhSachCoSo::danhSach()` | Cùng nguồn với ô lọc trên màn danh sách, nên hai màn không lệch nhau |

## 4. Vì sao KHÔNG đo ở mức dòng danh mục

Cách đo giàu thông tin hơn là đếm dòng đang hiệu lực trong sáu bảng danh mục đích — nó phát
hiện được "cơ sở này khai 320 dịch vụ, cơ sở kia 12".

**Cách đó hiện không trung thực được.** Migration `2026_08_25_100011_them_cot_tt12_vao_danh_muc`
chỉ thêm các **cột dữ liệu** (`tu_ngay_hd`, `tccl`, `so_luu_hanh`…) chứ không thêm cột đánh
dấu nguồn, và `Tt12DongBoDanhMuc::ghiMotDong()` cũng chỉ ghi các cột đã ánh xạ. Nên dòng do
TT12 đồng bộ xuống **không phân biệt được** với dòng nhập tay từ màn Quản lý danh mục.

Đó là hệ quả cố ý của quyết định Đ1 trong thiết kế TT12 gốc: dùng lại sáu bảng danh mục sẵn
có để `Xml3176Xml2/3/4Checker` chạy không cần sửa.

Một dashboard nói sai số còn tệ hơn không có dashboard. Nên đo ở mức hồ sơ — nó chỉ nói về
thứ TT12 thực sự gửi, và đúng tuyệt đối trong phạm vi đó.

**Nếu sau này cần đo mức dòng:** thêm một cột nguồn vào sáu bảng danh mục và ghi nó trong
`Tt12DongBoDanhMuc`. Dữ liệu cũ sẽ không có dấu vết, nên phải chấp nhận một mốc thời gian
trước đó là không biết.

## 5. Màn hình

### 5.1. Lưới độ phủ

Sáu hàng (mẫu) × N cột (cơ sở). Hiện là 3 cơ sở Bạch Mai → 18 ô.

| | 01929 | 37470 | 01283 |
|---|---|---|---|
| Mẫu 01/DM | đã tiếp nhận · 26/08/2026 · 1 dòng | chưa gửi | chưa gửi |
| Mẫu 02/DM | chưa gửi | chưa gửi | chưa gửi |
| … | | | |

Mỗi ô có hai trạng thái:

- **Đã tiếp nhận** — có ít nhất một hồ sơ mang mã kết quả tiếp nhận. Hiện kèm ngày tiếp nhận
  gần nhất và số dòng của **chính hồ sơ đó**.
- **Chưa gửi** — chưa có hồ sơ nào được tiếp nhận. Bao gồm cả trường hợp đã nạp nhưng đang
  kẹt; dải ở mục 5.2 mới là nơi nói ra chuyện kẹt.

### 5.2. Dải "đang dở dang"

Đếm hồ sơ **chưa được tiếp nhận**, tách theo năm trạng thái sẵn có của
`Tt12DanhSach::cacTrangThai()`:

`chua_kiem` · `con_loi` · `san_sang` · `da_ky` · `loi_gui`

**Vì sao bắt buộc phải có dải này:** lưới chỉ nói về thứ đã xong. Không có nó thì một hồ sơ
kẹt ở "Gửi lỗi" hoàn toàn vô hình — ô vẫn xám như thể chưa ai làm gì, trong khi thực ra có
người đã làm và đang hỏng.

### 5.3. Bấm vào ô

Sang `bhyt.tt12.index` với `mau`, `ma_cskcb` và `trang_thai` điền sẵn. Ô xám dẫn tới danh
sách rỗng — đúng, vì nó xác nhận "thật sự chưa có gì" thay vì để người dùng tự hỏi.

## 6. Nguyên tắc: không viết lại luật đếm

Đếm theo trạng thái thì gọi `Tt12DanhSach::truyVan(['trang_thai' => ...])`. Bản SQL đó đã có
và đã được test ràng với `Tt12QuyetDinhGui`.

Viết bản SQL thứ hai trong service dashboard là tạo ra **một màn hình báo số khác với chính
bộ lọc ngay cạnh nó**, và người dùng sẽ thôi tin cả hai. Đây là chú thích mở đầu của
`CtdtDashboardService` và nó đã đúng một lần.

Cái giá phải trả là nhiều truy vấn đếm thay vì một câu `GROUP BY`. Với 18 ô thì cái giá đó
không đáng kể — khác hẳn CTĐT, nơi phải chốt trần 20.000 hồ sơ.

## 7. Cây tệp

```
routes/web.php                    (sửa)  2 route, trong nhóm checkrole:xml-man sẵn có
config/adminlte.php               (sửa)  thêm mục "Dashboard danh mục" cạnh hai mục TT12

app/Http/Controllers/Dashboard/Tt12DashboardController.php   (mới)
    index()   trả view
    doPhu()   trả JSON lưới + dải

app/Services/Dashboard/Tt12DashboardService.php              (mới)
    doPhu(array $loc)  -> ['luoi' => [...], 'dang_do_dang' => [...]]

resources/views/dashboard/tt12.blade.php                     (mới)
```

Hai route: `dashboard/tt12` trả màn hình, `dashboard/tt12/do-phu` trả JSON. CTĐT có bốn vì
nó có ba khối nạp riêng; ở đây một khối là đủ.

## 8. Hình dạng dữ liệu trả về

```
[
  'luoi' => [
    'MAU_01' => [
      '01929' => ['da_tiep_nhan' => true,  'tiep_nhan_luc' => '20260826104112', 'so_dong' => 1],
      '37470' => ['da_tiep_nhan' => false, 'tiep_nhan_luc' => null,             'so_dong' => 0],
    ],
    ...
  ],
  'dang_do_dang' => [
    'chua_kiem' => 0, 'con_loi' => 2, 'san_sang' => 0, 'da_ky' => 1, 'loi_gui' => 0,
  ],
]
```

Khoá của `luoi` là **mã mẫu** và **mã cơ sở**, không phải nhãn hiển thị. Nhãn do view tra từ
`Tt12MauRegistry` và `DanhSachCoSo` — để đổi tên hiển thị không phải sửa service.

## 9. Xử lý lỗi

**HIS không đọc được.** `DanhSachCoSo::danhSach()` trả mảng rỗng khi HIS hỏng, và nó có
cache 60 phút. Lưới khi đó không có cột nào. Màn hình phải nói rõ "Không đọc được danh sách
cơ sở" chứ không hiện một lưới trống như thể mọi thứ đều chưa khai — hai tình huống đó khác
hẳn nhau và cùng trông giống nhau.

**Cơ sở có hồ sơ nhưng không còn trong HIS.** Xảy ra khi một cơ sở ngừng hoạt động sau khi
đã gửi danh mục. Những cơ sở đó **vẫn phải hiện** trong lưới, đánh dấu là ngoài danh sách
hiện hành — giấu đi là làm mất dấu vết một bộ danh mục đã thực sự gửi lên cổng.

## 10. Kiểm thử

| Khẳng định | Vì sao cần |
|---|---|
| Chưa có hồ sơ nào thì lưới đủ 6 × N ô, tất cả "chưa gửi" | Lưới phải đầy đủ ngay cả khi rỗng, không phải chỉ vẽ ô có dữ liệu |
| Ô chỉ xanh khi **đã tiếp nhận**, không phải chỉ "đã ký" | Đây là nhầm lẫn dễ xảy ra nhất, và nó nói dối theo hướng nguy hiểm: bảo là xong khi chưa gửi |
| Số dòng lấy từ hồ sơ tiếp nhận **gần nhất**, không cộng dồn | Đ4 |
| Cơ sở có trong HIS mà chưa gửi vẫn hiện thành ô xám | Nếu chỉ gom theo dữ liệu đã có thì cơ sở chưa khai biến mất — đúng cái mà màn này sinh ra để phát hiện |
| Cơ sở có hồ sơ mà không còn trong HIS vẫn hiện | Mục 9 |
| Service **không tự viết SQL đếm trạng thái** | Canh Đ3 bằng cách soi mã nguồn: không được có `where('ma_ket_qua'` hay `where('is_signed'` trong service |
| Dải dở dang khớp với số đếm của chính bộ lọc trên màn danh sách | Hai màn không được báo hai con số |

Test dùng `DungBangTt12Sqlite` và nạp sẵn `DanhSachCoSo::KHOA_CACHE` để không phụ thuộc
Oracle HIS — theo đúng khuôn đã dùng ở `Tt12ControllerTest`.

## 11. Rủi ro còn để ngỏ

| Rủi ro | Cách xử lý |
|---|---|
| Lưới nói "đã tiếp nhận" nhưng dữ liệu chưa vào bảng danh mục (`dong_bo_at` rỗng) | Chưa thể hiện ở bản này. Cột "Đã đồng bộ" trên màn danh sách vẫn là nơi thấy điều đó. Nếu thực tế hay gặp thì thêm dấu hiệu vào ô ở đợt sau |
| Số cơ sở tăng nhiều thì lưới rộng ra khó đọc | 3 cơ sở hiện tại thì không vấn đề. Vượt khoảng 8 cột mới cần đổi sang bố cục khác |
