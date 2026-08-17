# Spec — Tài liệu hướng dẫn sử dụng (DOCX): XML3176, Order-check, Thẻ BHYT, Quản lý danh mục

Ngày: 2026-08-11
Trạng thái: đã chốt với người dùng

## 1. Mục tiêu

Sản xuất **một tệp `.docx` duy nhất** làm tài liệu hướng dẫn sử dụng chính thức cho 4 mảng chức năng
của phần mềm QLBV: Hồ sơ XML 3176, Kiểm tra sai sót y lệnh (order-check), Thẻ BHYT, và Quản lý danh mục.

## 2. Quyết định đã chốt

| Hạng mục | Quyết định |
|---|---|
| Đối tượng đọc | Người dùng cuối nghiệp vụ (KHTH / thống kê / BHYT). Phần quản trị chỉ đưa ở mức "ai được làm, làm ở đâu". |
| Đóng gói | 1 tệp `.docx` tổng hợp, có mục lục tự động |
| Ảnh minh hoạ | Không chèn ảnh. Diễn đạt bằng bảng liệt kê nút/bộ lọc/cột và mô tả từng bước |
| Ngôn ngữ | Tiếng Việt |
| Nguồn dữ liệu | Đọc trực tiếp mã nguồn repo `C:\Users\tracnn\qlbv` (route, blade, config, migration, command) |

## 3. Phạm vi bổ sung theo yêu cầu (so với dàn ý ban đầu)

1. **Phần I** — thêm mục *Tự động quét và nhập khẩu hồ sơ* (command `xml3176import:day`, thư mục theo dõi, xử lý tệp lỗi).
2. **Phần I** — thêm mục *Bổ sung quy tắc kiểm tra XML mới theo yêu cầu*: quy trình 9 bước dành cho lập trình viên.
3. **Phần II** — thêm mục *Bổ sung quy tắc kiểm tra y lệnh mới theo yêu cầu*: quy trình 9 bước dành cho lập trình viên.
4. **Phần II** — thêm mục *Quét định kỳ hồ sơ đang điều trị nội trú*: cơ chế watermark, tần suất, và giới hạn đã biết.

Hai mục "bổ sung quy tắc" được đặt ở cuối mỗi phần, đánh dấu rõ là **dành cho bộ phận CNTT**, để người dùng
nghiệp vụ biết cách đặt yêu cầu và biết chi phí thực hiện, còn coder có checklist thi hành ngay.

## 4. Cấu trúc tài liệu

```
Trang bìa
Mục lục tự động
Chương 0. Thông tin chung
  0.1 Mục đích – đối tượng – quy ước ký hiệu
  0.2 Bản đồ menu và quyền truy cập
  0.3 Khái niệm dùng chung (mã điều trị, MA_CSKCB, hàng đợi nền)

PHẦN I. HỒ SƠ XML 3176
  1.1 Tổng quan quy trình 6 bước
  1.2 Nhập khẩu hồ sơ thủ công
  1.3 Tự động quét và nhập khẩu hồ sơ          [BỔ SUNG]
  1.4 Màn hình Danh sách hồ sơ (bộ lọc, cột, nút)
  1.5 Xem chi tiết một hồ sơ
  1.6 Ký số, xuất và gửi hồ sơ lên cổng BHXH
  1.7 Dashboard lỗi XML
  1.8 Danh mục mã lỗi XML 3176
  1.9 Xử lý sự cố thường gặp
  1.10 Bổ sung quy tắc kiểm tra mới theo yêu cầu (CNTT)   [BỔ SUNG]

PHẦN II. KIỂM TRA SAI SÓT Y LỆNH
  2.1 Cơ chế hoạt động
  2.2 Quét định kỳ hồ sơ đang điều trị nội trú  [BỔ SUNG]
  2.3 Màn hình Danh sách vi phạm
  2.4 Quy trình xử lý một vi phạm
  2.5 Từ điển 19 quy tắc kiểm tra
  2.6 Các trường hợp được miễn trừ
  2.7 Danh mục giới hạn dịch vụ & Quản lý quy tắc
  2.8 Xử lý sự cố thường gặp
  2.9 Bổ sung quy tắc kiểm tra mới theo yêu cầu (CNTT)    [BỔ SUNG]

PHẦN III. THẺ BHYT
  3.1 Tra cứu thẻ BHYT thủ công
  3.2 Kết quả tra cứu thẻ tự động
  3.3 Tra cứu hàng loạt theo hồ sơ XML
  3.4 Bảng mã tra cứu (000–500)
  3.5 Bảng mã kiểm tra (00–401)
  3.6 Xử lý sự cố thường gặp

PHẦN IV. QUẢN LÝ DANH MỤC
  4.1 Bản đồ 11 bộ danh mục BHYT
  4.2 Nguyên tắc "chỉ đọc – cập nhật bằng nhập lại tệp"
  4.3 Quy trình nhập khẩu danh mục
  4.4 Đặc tả 11 định dạng tệp Excel
  4.5 Cột MA_CSKCB và 4 bộ danh mục theo cơ sở
  4.6 Nhập khẩu tự động từ thư mục
  4.7 Xoá toàn bộ một danh mục
  4.8 Xử lý sự cố khi nhập khẩu

PHẦN V. TRA CỨU LỖI HỒ SƠ THEO MÃ ĐIỀU TRỊ     [BỔ SUNG 17/08/2026]
  5.1 Chức năng này dùng để làm gì
  5.2 Mở màn hình và quyền truy cập
  5.3 Nhập hoặc quét mã điều trị
  5.4 Khối Thông tin hồ sơ
  5.5 Ba bảng lỗi
  5.6 Đổi trạng thái một vi phạm y lệnh
  5.7 In phiếu lỗi
  5.8 Tra lại thẻ BHYT
  5.9 Xử lý sự cố thường gặp

PHỤ LỤC A. Tra cứu nhanh sự cố theo triệu chứng
PHỤ LỤC B. Các lệnh nền và dịch vụ hệ thống
```

Phần V được bổ sung ngày 17/08/2026, sau khi màn hình tra cứu lỗi hồ sơ theo mã điều trị
được xây dựng (xem `docs/superpowers/specs/2026-08-17-tra-cuu-loi-ho-so-design.md`). Bìa
tài liệu chuyển sang phiên bản 1.1.

Màn hình này không quét mã bằng camera: chức năng đó đã thử nghiệm rồi gỡ bỏ vì camera
thiết bị thông thường cho ảnh quá thấp so với mức cần để đọc mã vạch Code 128 của mã điều
trị. Tài liệu phải nói rõ điều này để người dùng không đi tìm nút quét.

## 5. Quy ước trình bày

- Tiêu đề cấp 1 = "PHẦN …", cấp 2 = "x.y", cấp 3 = "x.y.z". Mục lục tự sinh tới cấp 3.
- Mỗi thao tác của người dùng viết dạng bảng **Bước | Thao tác | Kết quả mong đợi**.
- Mỗi bảng lỗi có 3 cột: **Thông báo | Nguyên nhân | Cách xử lý**.
- Các mục dành cho CNTT có nhãn "Dành cho bộ phận CNTT" ngay dưới tiêu đề.
- Đường dẫn tệp mã nguồn chỉ xuất hiện trong hai mục "bổ sung quy tắc" và Phụ lục B.
- Không dùng ảnh chụp màn hình.

## 6. Ràng buộc nội dung (điểm dễ sai, phải nêu rõ)

1. Nút **Xuất XML3176** chỉ tải ZIP về máy, **không** gửi lên cổng BHXH; gửi là tự động và phụ thuộc cấu hình.
2. Người dùng thường chỉ thấy hồ sơ **do chính mình import**.
3. Nhãn "Đã/Chưa Export 4750" trên bộ lọc XML3176 là nhãn cũ, hiểu là đã/chưa xuất XML.
4. Bộ quét y lệnh **không quét lại** phiếu đã quét; hệ quả với hồ sơ nội trú dài ngày phải nêu thành lưu ý.
5. Mã lỗi XML3176 chưa có trong danh mục mặc định được coi là **nghiêm trọng** → chặn xuất XML.
6. Nhập ĐVHC / Cơ sở KCB là thay thế trọn bộ (dòng thiếu bị vô hiệu hoá).
7. Xoá danh mục khi chưa có tệp thay thế sẽ sinh hàng chục nghìn vi phạm giả.

## 7. Đầu ra

- `docs/huong-dan-su-dung/Huong-dan-su-dung-XML3176-OrderCheck-TheBHYT-DanhMuc.docx`
- Tệp nguồn Markdown giữ lại cùng thư mục để lần sau chỉnh sửa và dựng lại.

## 8. Tiêu chí nghiệm thu

- Mở được bằng Microsoft Word, mục lục cập nhật đúng, không lỗi phông tiếng Việt.
- Đủ 4 phần + 2 phụ lục theo cấu trúc mục 4, có đủ 4 mục bổ sung ở mục 3.
- Mọi tên menu, nhãn nút, tên cột trong tài liệu khớp với mã nguồn hiện tại.
