# Nguồn dựng các bộ slide đào tạo

Hai tệp `.pptx` trong thư mục cha được sinh ra từ các script trong thư mục này bằng thư viện
[`pptxgenjs`](https://www.npmjs.com/package/pptxgenjs) (Node.js). Sửa nội dung trong các tệp
`deck-*.js` / `phan-*.js` rồi dựng lại — **không sửa trực tiếp tệp `.pptx`**, vì lần dựng sau
sẽ ghi đè.

| Tệp ra | Nội dung |
|---|---|
| `Slide-dao-tao-Tien-giam-dinh.pptx` | Deck gộp — Giải pháp Tiền giám định, đào tạo các khoa lâm sàng và các phòng ban chức năng (80 slide) |
| `Slide-dao-tao-D-Bao-cao-giao-ban.pptx` | Deck D — Báo cáo giao ban (42 slide) |

Deck gộp thay cho ba deck A (khoa lâm sàng), B (phòng ban), C (tra cứu tiền cùng chi trả)
trước đây. Khung và giao diện theo `../Bài Trình Bày Nội Dung.pdf` — báo cáo Hội đồng KHCN về
Giải pháp Tiền giám định, bản 1.7.

Cách làm này giống hệt `docs/huong-dan-su-dung/_nguon`: tài liệu gốc còn cập nhật theo phiên
bản, slide bám theo tài liệu, nên có nguồn thì lần sau chỉ sửa vài dòng.

## Cấu trúc

| Tệp | Nội dung |
|---|---|
| `lib.js` | Hai bộ giao diện (`classic`, `tgd`), phông, và các hàm dựng slide dùng chung |
| `deck-tgd.js` | Deck gộp: phần mở đầu (truy cập Cổng, vị trí Tiền giám định, quy trình 5 bước, 5 bước × 3 luồng, 7 nhóm chức năng), gọi các chương theo thứ tự, phần kết |
| `phan-khoa-lam-sang.js` | Chương 02 sai sót y lệnh, 06 tra cứu lỗi hồ sơ, phần thẻ BHYT của khoa, tình huống tại khoa |
| `phan-phong-ban.js` | Chương 03 XML 3176, 07 danh mục, 08 chứng từ điện tử, 09 TT12, phần thẻ BHYT của phòng ban, tình huống ở phòng ban |
| `phan-mcct.js` | Chương 05 tra cứu tiền cùng chi trả |
| `deck-d.js` | Deck D — Báo cáo giao ban (ảnh nhúng từ `../anh/`) |
| `build.js` | Ghép và ghi ra tệp `.pptx` |

Mỗi hàm chương nhận `(pptx, ctx, no)`. Hàm nào tự có `sectionSlide` thì mở chương với số `no`;
hàm không có (thẻ BHYT, MCCT, tình huống) thì `deck-tgd.js` mở chương trước khi gọi.

### Hai bộ giao diện

`newDeck(PptxGenJS, { …, theme })` chọn giao diện cho cả deck:

- `classic` (mặc định) — giao diện gốc. Deck D dùng bộ này và **không được đổi**.
- `tgd` — nền navy, nhãn chương "02 / MỤC 2.4", chân trang "GIẢI PHÁP TIỀN GIÁM ĐỊNH · nn / NN".
  Vì cần tổng số slide, chân trang theme này chỉ được vẽ khi gọi `L.finishDeck(pptx)` ở cuối deck
  — quên gọi thì deck không có chân trang.

Sau khi sửa `lib.js`, kiểm tra lại deck D không đổi: giải nén bản vừa dựng và bản trong git rồi
so thư mục `ppt/slides/` — chỉ được khác ở `docProps/` (ngày giờ dựng).

## Nguồn nội dung

| Tệp | Lấy từ |
|---|---|
| `deck-tgd.js` (mở đầu, kết) | `../Bài Trình Bày Nội Dung.pdf` (trang 2, 3, 6), `part1.js` mục 1.1 và 1.6, `part6.js`, `part7.js` |
| `phan-khoa-lam-sang.js` | `docs/huong-dan-su-dung/_nguon/part2.js` (sai sót y lệnh), `part5.js` (tra cứu lỗi hồ sơ), `part3.js` (thẻ BHYT — phần đọc kết quả) |
| `phan-phong-ban.js` | `part1.js` (XML 3176), `part3.js` (tra thẻ hàng loạt), `part4.js` (danh mục), `part6.js` (chứng từ điện tử), `part7.js` (danh mục TT12) |
| `phan-mcct.js` | `docs/huong-dan-su-dung/_nguon_mcct/build.js` (toàn bộ). Nhãn slide ghi "HDSD MCCT · Mục x" vì số mục trỏ về tài liệu MCCT, không phải tài liệu chính |
| D | Chưa có tài liệu gốc — viết thẳng từ mã nguồn module giao ban: `resources/views/khth/giaoban-*.blade.php`, `app/Http/Controllers/*/GiaoBan*Controller.php`, `app/Services/GiaoBan/*`, `public/js/giaoban/*` |

Khi tài liệu gốc đổi, sửa tệp tương ứng theo bảng này. Số mục (ví dụ "Mục 2.4") in ở góc
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

Dựng một deck riêng: `node build.js tgd` hoặc `node build.js d`.

Dựng xong thì xoá `node_modules` ở thư mục gốc đi:

```bash
rm -rf ../../../node_modules
```

Hai cờ `--no-save --no-package-lock` là bắt buộc: dự án **cố ý không có `package.json`** —
xem giải thích đầy đủ ở `docs/huong-dan-su-dung/_nguon/README.md`.

## Ảnh chụp màn hình

Deck D nhúng ảnh thật từ `../anh/` (hàm `imageSlide` trong `deck-d.js`), dựng lại không mất ảnh.
Deck gộp: `shotSlide` tra bảng `SHOT_FILES` trong `lib.js` — có tệp ảnh trong `../anh/` thì
nhúng ảnh (khung co theo tỉ lệ ảnh; ảnh bẹt từ 2,4:1 trở lên thì trải ngang, ghi chú xuống dưới),
chưa có thì vẽ khung viền đứt `[Ảnh n]`. Cả sáu ảnh hiện đã có. Yêu cầu từng ảnh và cách đã
chụp nằm ở `../DANH-SACH-ANH-CAN-CHUP.md`. Thay ảnh = ghi đè tệp cùng tên rồi dựng lại.

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

Rồi kiểm tra không có khối chữ nào vượt mép trang hoặc đè lên chân trang. Cả hai deck hiện
đạt yêu cầu này.
