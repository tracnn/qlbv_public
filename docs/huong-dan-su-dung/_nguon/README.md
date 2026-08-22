# Nguồn dựng tài liệu hướng dẫn sử dụng

Tệp `.docx` trong thư mục cha được sinh ra từ các script trong thư mục này bằng thư viện
[`docx`](https://www.npmjs.com/package/docx) (Node.js). Sửa nội dung trong các tệp `part*.js`
rồi dựng lại — không sửa trực tiếp tệp `.docx`, vì lần dựng sau sẽ ghi đè.

## Cấu trúc

| Tệp | Nội dung |
|---|---|
| `version.js` | **Số phiên bản và lịch sử phát hành — nguồn duy nhất.** Trang bìa, bảng lịch sử và chân trang đều đọc từ đây |
| `lib.js` | Hàm dựng chung: tiêu đề, đoạn văn, gạch đầu dòng, bảng, khối "Lưu ý", khối "Dành cho CNTT" |
| `front.js` | Trang bìa, mục lục tự động, bảng Lịch sử cập nhật, Chương 0 |
| `part1.js` | Phần I — Hồ sơ XML 3176 |
| `part2.js` | Phần II — Kiểm tra sai sót y lệnh |
| `part3.js` | Phần III — Thẻ BHYT |
| `part4.js` | Phần IV — Quản lý danh mục |
| `part5.js` | Phần V — Tra cứu lỗi hồ sơ theo mã điều trị |
| `part6.js` | Phần VI — Chứng từ điện tử theo Phụ lục 02 |
| `appendix.js` | Phụ lục A (tra cứu sự cố) và Phụ lục B (tiến trình nền) |
| `build.js` | Ghép các phần, khai báo trang, header/footer, đánh số |

## Dựng lại tệp

```bash
npm install docx
node build.js ../Huong-dan-su-dung-XML3176-OrderCheck-TheBHYT-DanhMuc.docx
```

## Quy trình phát hành một đợt cập nhật

Tài liệu đánh số phiên bản theo từng đợt. Mỗi lần bổ sung hoặc sửa nội dung đáng kể, làm đủ
bốn bước sau — bỏ bước nào thì tài liệu vẫn dựng ra bình thường, chỉ mang thông tin phiên bản
sai, và đó là kiểu sai không ai phát hiện cho tới lúc hai người mở hai bản khác nhau mà thấy
cùng một số:

1. Sửa nội dung trong `part*.js` / `front.js` / `appendix.js`.
2. Mở `version.js`: thêm **một** mục vào đầu mảng `LICH_SU`, rồi sửa `PHIEN_BAN` và
   `NGAY_PHAT_HANH` cho khớp mục vừa thêm.
   - Tăng số phụ (1.1 → 1.2) khi bổ sung một phần mới hoặc cập nhật nội dung một phần.
   - Tăng số chính (1.x → 2.0) khi đổi cấu trúc tài liệu hoặc đổi phạm vi đối tượng đọc.
3. Dựng lại tệp `.docx` (lệnh ở mục trên).
4. Mở bằng Word, `Ctrl+A` rồi `F9` để cập nhật mục lục, và lưu lại.

Cột "Nội dung thay đổi" trong `LICH_SU` viết cho người đọc nghiệp vụ: nói cái gì mới dùng
được, không nói tệp nào được sửa.

## Lưu ý khi sửa

- Mọi bảng phải có tổng độ rộng cột bằng `CONTENT_W` (9020 twip). `lib.js` tự chuẩn hoá nếu lệch,
  nhưng nên khai đúng để kiểm soát bố cục.
- Không dùng ký tự `\n` trong chuỗi — mỗi đoạn là một `Paragraph` riêng.
- Mục lục là trường tự động: sau khi mở bằng Word, nhấn `Ctrl+A` rồi `F9` và chọn
  "Update entire table" để hiển thị số trang.
- Thiết kế và phạm vi tài liệu: xem `docs/superpowers/specs/2026-08-11-tai-lieu-huong-dan-su-dung-design.md`.
