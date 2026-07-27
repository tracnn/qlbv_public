# Nhóm A — Ánh xạ chỉ tiêu XN & CĐHA sang dịch vụ HIS

Ngày: 2026-07-27
Mục tiêu: cấu hình lại khoa XN&CĐHA cho đúng mẫu KHTH yêu cầu (mục I của phiếu yêu cầu).
**Không cần sửa code.** Toàn bộ làm bằng form builder chỉ tiêu.

Số liệu tra trên HIS thật (`HISPro` = 14.160.70.2/orcl), thống kê tháng 6/2026 để biết dịch vụ nào thực sự phát sinh.

## 0. Việc đầu tiên: đổi khối

Khoa XN&CĐHA trên server đang để khối **Điều trị (nội trú)** nên hiển thị BN cũ / BN vào / BN chuyển đến — đây là nguyên nhân của mục I, không phải lỗi phần mềm.

Vào *Cấu hình giao ban* → hàng khoa XN&CĐHA → đổi **Loại khối** sang **Cận lâm sàng** → bấm **Lưu** → mở **Chỉ tiêu** và cấu hình theo bảng dưới.

## 1. Nhóm chắc chắn — dùng loại dịch vụ, không cần chọn dịch vụ lẻ

Tám chỉ tiêu xét nghiệm ánh xạ thẳng vào `test_type`, dùng được ngay mẫu *Xét nghiệm* có sẵn:

| Chỉ tiêu | Loại dịch vụ | Loại xét nghiệm |
|---|---|---|
| Huyết học | Xét nghiệm | Xét nghiệm Huyết học |
| Sinh hóa | Xét nghiệm | Xét nghiệm Sinh hóa |
| Nước tiểu | Xét nghiệm | Nước tiểu |
| Test | Xét nghiệm | Test |
| Vi sinh | Xét nghiệm | Xét nghiệm Vi sinh |
| Miễn dịch | Xét nghiệm | Xét nghiệm Miễn dịch |

Bốn chỉ tiêu CĐHA cơ bản dùng `diim_type` (mẫu *CĐHA* có sẵn):

| Chỉ tiêu | Loại dịch vụ | Loại CĐHA |
|---|---|---|
| Xquang | Chẩn đoán hình ảnh | X-Quang |
| CT | Chẩn đoán hình ảnh | CT |
| CĐHA khác | Chẩn đoán hình ảnh | *(tick "Là nhóm Khác" sau khi chọn X-Quang, CT)* |
| Siêu âm | Siêu âm | *(để trống — lấy toàn bộ loại Siêu âm)* |

Danh mục đầy đủ để đối chiếu:

- **Loại xét nghiệm:** 1 Huyết học · 2 Vi sinh · 3 Sinh hóa · 4 Miễn dịch · 5 Test · 6 Giải phẫu bệnh · 7 Nước tiểu · 8 Dấu ấn ung thư · 9 Phân · 10 Phiến đồ âm đạo/CTC
- **Loại CĐHA:** 1 X-Quang · 2 CT · 3 MRI · 4 PET/CT
- **Loại dịch vụ:** KH Khám · XN Xét nghiệm · HA CĐHA · TT Thủ thuật · CN Thăm dò chức năng · NS Nội soi · SA Siêu âm · PT Phẫu thuật · GB Giải phẫu bệnh · PH PHCN · MA Máu

## 2. Nhóm phải chọn dịch vụ cụ thể

Những chỉ tiêu này **không** ứng với một loại dịch vụ nào — phải dùng ô *Dịch vụ cụ thể* trong form builder (gõ tên để tìm).

Dưới đây là dịch vụ **thực sự có phát sinh trong tháng 6/2026**, kèm số ca để biết cái nào đáng kể.

### Điện tim

| Dịch vụ | Số ca T6 |
|---|---|
| Điện tim thường [Nội khoa] | 2.784 |
| Ghi điện tim cấp cứu tại giường | 29 |
| Holter điện tâm đồ | 33 |

**Cần KHTH quyết:** Holter điện tâm đồ có tính vào "Điện tim" không? Đây là theo dõi 24 giờ, khác điện tim thường — tôi không tự quyết được.

### Điện não

| Dịch vụ | Số ca T6 |
|---|---|
| Ghi điện não thường quy | 7 |
| Ghi điện não đồ thông thường | 4 |

Hai dịch vụ tên gần giống nhau, cùng đang được dùng — chọn cả hai.

### Đo mật độ xương

| Dịch vụ | Số ca T6 |
|---|---|
| Đo mật độ xương bằng phương pháp DEXA | 108 |
| Đo mật độ xương 3 vị trí | 29 |
| Đo mật độ xương 2 vị trí | 28 |
| Đo mật độ xương toàn thân | 3 |

Chọn cả bốn.

### Đo chức năng hô hấp

**Cả tháng 6/2026 không có ca nào.** Hai dịch vụ tồn tại trong danh mục (*Đo chức năng hô hấp*, *Thăm dò chức năng hô hấp (Mã TE)*) nhưng không phát sinh.

Vẫn cấu hình để có chỗ, nhưng đừng ngạc nhiên khi ra 0. Nếu KHTH khẳng định có làm thì tên dịch vụ họ dùng khác — cần hỏi lại.

### Nội soi tiêu hóa (NSTH)

| Dịch vụ | Số ca T6 |
|---|---|
| Nội soi can thiệp - làm Clo test chẩn đoán nhiễm H.Pylori | 573 |
| Nội soi đại trực tràng toàn bộ ống mềm không sinh thiết | 133 |
| Nội soi thực quản - dạ dày - tá tràng không sinh thiết | 44 |
| Nội soi trực tràng ống mềm không sinh thiết | 31 |
| Nội soi can thiệp - cắt polyp ống tiêu hóa > 1cm | 22 |
| Nội soi thực quản, dạ dày, tá tràng | 15 |
| Nội soi can thiệp - cắt 1 polyp ống tiêu hóa < 1cm | 14 |
| Nội soi đại tràng sigma không sinh thiết | 13 |
| Nội soi trực tràng ống mềm | 11 |
| Nội soi thực quản - Dạ dày - Tá tràng cấp cứu | 6 |
| *(và 5 dịch vụ lẻ 1–2 ca)* | |

Nhiều dịch vụ, gõ tay dễ sót. **Cách gọn hơn:** đặt chỉ tiêu là *Loại dịch vụ = Nội soi*, rồi làm chỉ tiêu **NS TMH** riêng và tick "Là nhóm Khác" cho NSTH — nhưng nhóm Khác hiện chỉ áp dụng cho loại CĐHA và loại xét nghiệm, không áp cho danh sách dịch vụ. Nên trước mắt vẫn phải chọn từng dịch vụ.

### Nội soi TMH

| Dịch vụ | Số ca T6 | Ghi chú |
|---|---|---|
| Nội soi tai mũi họng | 1.704 | loại **Nội soi** |
| Nội soi tai (Mã TE) | 63 | loại **Nội soi** |
| Nội soi tai mũi họng (Ngoài giờ) | 1 | loại **Thăm dò chức năng** ⚠ |

⚠ **Phát hiện sai danh mục trong HIS:** *Nội soi tai mũi họng (Ngoài giờ)* đang được xếp vào loại **Thăm dò chức năng** thay vì **Nội soi**. Nếu cấu hình theo loại dịch vụ thì ca ngoài giờ sẽ rơi nhầm sang cột thăm dò chức năng. Đáng báo cho bên quản trị danh mục HIS sửa; trước mắt cứ chọn đích danh dịch vụ này vào NS TMH.

### NSTH gây mê

Không tìm thấy dịch vụ nào có "gây mê" trong nhóm nội soi, và không có ca nào tháng 6. **Cần KHTH cho biết tên dịch vụ thật**, hoặc xác nhận cột này bỏ trống.

### Siêu âm — các biến thể

Loại *Siêu âm* có hơn 30 dịch vụ đang dùng. Việc xếp dịch vụ nào vào cột nào là **quyết định chuyên môn, không phải kỹ thuật** — tôi liệt kê để KHTH chọn:

| Nhóm gợi ý | Dịch vụ (số ca T6) |
|---|---|
| **Siêu âm tim** | Siêu âm Doppler tim (611) · Siêu âm Doppler tim, van tim (522) · Siêu âm tim, màng tim qua thành ngực (9) |
| **Siêu âm mạch** | Siêu âm Doppler mạch máu (401) · Doppler ĐM/TM chi dưới (375) · Doppler ĐM thận (14) · Doppler tinh hoàn (11) |
| **SÂ đầu dò** | Siêu âm tử cung buồng trứng qua đường âm đạo (106) |
| **Siêu âm màu** | *Cần KHTH định nghĩa* — thường "siêu âm màu" chính là các dịch vụ Doppler, sẽ **trùng** với hai nhóm trên |
| **SÂ đàn hồi mô** | Không có dịch vụ nào phát sinh tháng 6 — cần xác nhận tên |
| **Siêu âm** (chung) | Phần còn lại: ổ bụng (3.098 + 2.034) · khớp (1.441) · tuyến giáp (564) · phần mềm (499) · thai · tuyến vú · tử cung phần phụ… |

**Cảnh báo trùng đếm:** nếu "Siêu âm màu" gồm các dịch vụ Doppler mà "Siêu âm tim" và "Siêu âm mạch" cũng lấy Doppler, một ca sẽ bị đếm hai lần và tổng sẽ vênh. Phải chốt ranh giới trước khi cấu hình.

### CT tiêm thuốc

Chưa tra. Cần lọc trong loại CĐHA / diim_type CT các dịch vụ có "tiêm thuốc cản quang" trong tên.

### Liên kết, Đông máu

Hai cột này có trong mẫu Excel nhưng **không ứng với `test_type` nào**:

- **Đông máu** thường nằm trong Huyết học → nếu tách riêng thì phải chọn dịch vụ cụ thể, và **nhớ loại khỏi cột Huyết học** kẻo đếm hai lần.
- **Liên kết** nhiều khả năng là xét nghiệm gửi đơn vị liên kết bên ngoài — cần KHTH cho biết dấu hiệu nhận biết trong HIS (loại riêng? máy thực hiện? phòng thực hiện?).

## 3. Cách làm và cách tự kiểm

Với mỗi chỉ tiêu: mở modal **Chỉ tiêu** của khoa XN&CĐHA → *Thêm chỉ tiêu* → **Đếm dịch vụ** → đặt tên → chọn phạm vi khoa → khai điều kiện lọc theo bảng trên.

Sau khi cấu hình xong, bấm **Tính thử** với đúng mốc thời gian của một ngày đã có bản Excel, rồi so từng cột. Đây chính là việc tính năng Tính thử sinh ra để làm.

**Lưu ý về mốc thời gian.** Tôi đã thử đối chiếu với bản Excel ngày 15/6/2026 và không khớp: cửa sổ 14/6 07:00 → 15/6 07:00 cho Điện tim 76, cửa sổ 15/6 07:00 → 16/6 07:00 cho 118, trong khi Excel ghi 80. Riêng Đo MĐX thì cửa sổ sau khớp đúng (4). Nghĩa là bản Excel đang được tổng hợp theo quy tắc riêng mà tôi không suy ra được từ dữ liệu.

**Cần KHTH xác nhận:** báo cáo giao ban ngày X lấy số liệu từ mốc nào đến mốc nào. Chốt được cái này thì mọi so sánh sau đó mới có nghĩa.

## 4. Các mục còn lại của nhóm A

### Tách "Xin về"

Hiện `XV` bị gộp vào "BN ra viện" (`end_codes: RV, HK, CC, XV, KH, TR`). Sửa: bỏ `XV` khỏi chỉ tiêu ra viện, thêm chỉ tiêu mới *Xin về* loại **Kết thúc điều trị** với mã kết thúc `XV`.

**Làm số "BN ra viện" giảm so với các ngày trước.** Chốt mốc áp dụng với KHTH trước khi sửa.

### BN nằm yêu cầu (5 khoa)

HSCC, Nội Nhi, Ngoại TH-CK, Phụ Sản, YHCT&PHCN. Dùng chỉ tiêu **Đếm BN trên giường chỉ định**, chọn các giường yêu cầu trong ô *Giường*.

Nếu HIS không đánh dấu giường yêu cầu riêng thì chuyển sang chỉ tiêu **Nhập tay**, đơn vị "BN", bật *kế thừa kỳ trước*.

### BN cấp cứu (HSCC)

Chưa đủ thông tin. Cần KHTH cho biết đếm cái gì: lượt khám cấp cứu, số BN vào viện qua đường cấp cứu, hay số BN đang nằm diện cấp cứu.

## 5. Tóm tắt việc cần KHTH quyết

1. Mốc thời gian của một kỳ báo cáo giao ban.
2. Holter điện tâm đồ có thuộc "Điện tim" không.
3. Ranh giới giữa Siêu âm màu / Siêu âm tim / Siêu âm mạch, tránh đếm trùng.
4. "SÂ đàn hồi mô", "NSTH gây mê", "Đo CN hô hấp" — tên dịch vụ thật, vì không có ca nào phát sinh.
5. "Liên kết" nhận biết thế nào trong HIS.
6. "Đông máu" tách riêng hay để trong Huyết học.
7. Định nghĩa "BN cấp cứu" của HSCC.
8. Mốc áp dụng việc tách "Xin về".

Việc cần báo bên quản trị danh mục HIS: *Nội soi tai mũi họng (Ngoài giờ)* đang xếp nhầm loại **Thăm dò chức năng**.
