# Nguồn dựng hướng dẫn tra cứu tiền cùng chi trả (MCCT)

Tệp `../Huong-dan-su-dung-tra-cuu-MCCT.docx` được sinh từ `build.js` bằng thư viện
[`docx`](https://www.npmjs.com/package/docx). Sửa nội dung trong `build.js` rồi dựng lại —
không sửa trực tiếp tệp `.docx`, vì lần dựng sau sẽ ghi đè.

Tài liệu này tách riêng khỏi tài liệu chung (`../_nguon`) vì viết cho một nhóm đọc hẹp hơn
(cán bộ tiếp đón, kế toán viện phí) và có số phiên bản riêng, ghi trên trang bìa trong `build.js`.

## Dựng lại tệp

Ở thư mục gốc dự án:

```bash
npm install docx --no-save --no-package-lock
node docs/huong-dan-su-dung/_nguon_mcct/build.js docs/huong-dan-su-dung/Huong-dan-su-dung-tra-cuu-MCCT.docx
rm -rf node_modules
```

Hai cờ `--no-save --no-package-lock` là bắt buộc: dự án cố ý không có `package.json` — xem
`../_nguon/README.md`.

Mỗi lần sửa nội dung đáng kể, tăng số phiên bản và ngày ở dòng "Phiên bản tài liệu" trên
trang bìa.
