# Nguồn dựng tài liệu quy trình vận hành XML 3176

Tệp `../Quy-trinh-van-hanh-XML3176.docx` được sinh ra từ các script ở đây. Sửa nội dung trong
`build.js` (hoặc sơ đồ trong `ve_so_do.py`) rồi dựng lại — không sửa trực tiếp tệp `.docx`.

| Tệp | Nội dung |
|---|---|
| `ve_so_do.py` | Vẽ hai sơ đồ luồng ra `anh/*.png` bằng PyMuPDF + phông Arial của Windows (bộ dựng SVG của MuPDF không có phông phủ tiếng Việt) |
| `build.js` | Nội dung tài liệu và bố cục trang; dùng lại `../../huong-dan-su-dung/_nguon/lib.js` để cùng kiểu với tài liệu hướng dẫn sử dụng |

## Dựng lại

Chạy ở thư mục gốc dự án:

```bash
python docs/quy-trinh-van-hanh/_nguon/ve_so_do.py
```

```bash
npm install docx --no-save --no-package-lock
```

```bash
node docs/quy-trinh-van-hanh/_nguon/build.js docs/quy-trinh-van-hanh/Quy-trinh-van-hanh-XML3176.docx
```

```bash
rm -rf node_modules
```

Nếu `docx` đã cài toàn cục thì bỏ bước cài/xoá và chạy `build.js` với `NODE_PATH="$(npm root -g)"`.

`build.js` sắp lại thứ tự viền đoạn văn (`w:pBdr`) sau khi thư viện `docx` xuất tệp, để tệp
đạt kiểm tra lược đồ OOXML.
