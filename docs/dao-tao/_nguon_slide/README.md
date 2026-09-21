# Nguồn dựng bốn bộ slide đào tạo

Bốn tệp `.pptx` trong thư mục cha được sinh ra từ các script trong thư mục này bằng thư viện
[`pptxgenjs`](https://www.npmjs.com/package/pptxgenjs) (Node.js). Sửa nội dung trong các tệp
`deck-*.js` rồi dựng lại — **không sửa trực tiếp tệp `.pptx`**, vì lần dựng sau sẽ ghi đè.

Cách làm này giống hệt `docs/huong-dan-su-dung/_nguon`: tài liệu gốc còn cập nhật theo phiên
bản, slide bám theo tài liệu, nên có nguồn thì lần sau chỉ sửa vài dòng.

## Cấu trúc

| Tệp | Nội dung |
|---|---|
| `lib.js` | Bảng màu, phông, và chín hàm dựng slide dùng chung |
| `deck-a.js` | Deck A — Khoa lâm sàng (28 slide) |
| `deck-b.js` | Deck B — Phòng ban nghiệp vụ (37 slide) |
| `deck-c.js` | Deck C — Tiếp đón & Viện phí (18 slide) |
| `deck-d.js` | Deck D — Báo cáo giao ban (42 slide, ảnh nhúng từ `../anh/`) |
| `build.js` | Ghép và ghi ra tệp `.pptx` |

## Nguồn nội dung

| Deck | Lấy từ |
|---|---|
| A | `docs/huong-dan-su-dung/_nguon/part2.js` (sai sót y lệnh), `part5.js` (tra cứu lỗi hồ sơ), `part3.js` (thẻ BHYT — phần đọc kết quả) |
| B | `part1.js` (XML 3176), `part3.js` (tra thẻ hàng loạt), `part4.js` (danh mục), `part6.js` (chứng từ điện tử), `part7.js` (danh mục TT12) |
| C | `docs/huong-dan-su-dung/_nguon_mcct/build.js` (toàn bộ) |
| D | Chưa có tài liệu gốc — viết thẳng từ mã nguồn module giao ban: `resources/views/khth/giaoban-*.blade.php`, `app/Http/Controllers/*/GiaoBan*Controller.php`, `app/Services/GiaoBan/*`, `public/js/giaoban/*` |

Khi tài liệu gốc đổi, sửa deck tương ứng theo bảng này. Số mục (ví dụ "Mục 2.4") in ở góc
trên mỗi slide chính là đường dẫn ngược về tài liệu gốc.

## Dựng lại tệp

Ở thư mục gốc dự án:

```bash
npm install pptxgenjs --no-save --no-package-lock
```

Rồi ở thư mục này:

```bash
node build.js
```

Dựng một deck riêng: `node build.js a` hoặc `node build.js b c`.

Dựng xong thì xoá `node_modules` ở thư mục gốc đi:

```bash
rm -rf ../../../node_modules
```

Hai cờ `--no-save --no-package-lock` là bắt buộc: dự án **cố ý không có `package.json`** —
xem giải thích đầy đủ ở `docs/huong-dan-su-dung/_nguon/README.md`.

## Ảnh chụp màn hình

Deck D nhúng ảnh thật từ `../anh/` (hàm `imageSlide` trong `deck-d.js`), dựng lại không mất ảnh.
Deck A, B, C: slide có khung viền đứt ghi `[Ảnh n]` là chỗ chờ ảnh chụp màn hình thật. Danh sách đầy đủ
chín ảnh cần chụp, kèm yêu cầu từng ảnh, nằm ở `../DANH-SACH-ANH-CAN-CHUP.md`.

Chụp xong thì dán ảnh vào PowerPoint đè lên khung viền đứt, rồi xoá khung và dòng chữ chú
thích. Nếu muốn ảnh được nhúng sẵn mỗi lần dựng lại, thay lời gọi `shotSlide` bằng
`slide.addImage({ path, x, y, w, h })` trong deck tương ứng.

## Lưu ý khi sửa

- **Khổ slide là `LAYOUT_WIDE` (13.333 × 7.5 inch), không phải `LAYOUT_16x9`.** `LAYOUT_16x9`
  của pptxgenjs là 10 × 5.625 inch; đổi sang nó thì mọi toạ độ trong `lib.js` tràn ra ngoài mép.
- Mảng `colW` của `tableSlide` là **tỉ lệ**, không phải inch — `lib.js` tự chuẩn hoá về đúng
  bề rộng vùng nội dung. Khai theo tỉ lệ nào cũng được, miễn đúng tương quan giữa các cột.
- Bảng quá 9 dòng hoặc ô quá dài thì hạ `fontSize` (mặc định 12) xuống 11, hoặc tách hai slide.
  `pptxgenjs` không tự thu nhỏ chữ cho vừa ô.
- Mỗi slide nội dung nên có `speaker` — ghi chú người trình bày, để người khác cầm deck đi
  giảng thay vẫn nói đúng ý.
- Khối `note` chỉ đặt được **một** khối mỗi slide, luôn nằm sát đáy. Ba kiểu: `warn` (mặc
  định, vàng), `danger` (đỏ), `ok` (xanh lá).

## Kiểm tra sau khi dựng

Không có cách nào thay thế việc mở bằng PowerPoint xem lại. Nhưng để bắt nhanh lỗi tràn chữ,
có thể dựng PDF rồi soát bằng máy:

```bash
soffice --headless --convert-to pdf --outdir /tmp/render ../*.pptx
```

Rồi kiểm tra không có khối chữ nào vượt mép trang hoặc đè lên chân trang. Cả bốn deck hiện
đạt yêu cầu này.
