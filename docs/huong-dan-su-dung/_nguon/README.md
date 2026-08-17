# Nguồn dựng tài liệu hướng dẫn sử dụng

Tệp `.docx` trong thư mục cha được sinh ra từ các script trong thư mục này bằng thư viện
[`docx`](https://www.npmjs.com/package/docx) (Node.js). Sửa nội dung trong các tệp `part*.js`
rồi dựng lại — không sửa trực tiếp tệp `.docx`, vì lần dựng sau sẽ ghi đè.

## Cấu trúc

| Tệp | Nội dung |
|---|---|
| `lib.js` | Hàm dựng chung: tiêu đề, đoạn văn, gạch đầu dòng, bảng, khối "Lưu ý", khối "Dành cho CNTT" |
| `front.js` | Trang bìa, mục lục tự động, Chương 0 |
| `part1.js` | Phần I — Hồ sơ XML 3176 |
| `part2.js` | Phần II — Kiểm tra sai sót y lệnh |
| `part3.js` | Phần III — Thẻ BHYT |
| `part4.js` | Phần IV — Quản lý danh mục |
| `part5.js` | Phần V — Tra cứu lỗi hồ sơ theo mã điều trị |
| `appendix.js` | Phụ lục A (tra cứu sự cố) và Phụ lục B (tiến trình nền) |
| `build.js` | Ghép các phần, khai báo trang, header/footer, đánh số |

## Dựng lại tệp

```bash
npm install docx
node build.js ../Huong-dan-su-dung-XML3176-OrderCheck-TheBHYT-DanhMuc.docx
```

## Lưu ý khi sửa

- Mọi bảng phải có tổng độ rộng cột bằng `CONTENT_W` (9020 twip). `lib.js` tự chuẩn hoá nếu lệch,
  nhưng nên khai đúng để kiểm soát bố cục.
- Không dùng ký tự `\n` trong chuỗi — mỗi đoạn là một `Paragraph` riêng.
- Mục lục là trường tự động: sau khi mở bằng Word, nhấn `Ctrl+A` rồi `F9` và chọn
  "Update entire table" để hiển thị số trang.
- Thiết kế và phạm vi tài liệu: xem `docs/superpowers/specs/2026-08-11-tai-lieu-huong-dan-su-dung-design.md`.
