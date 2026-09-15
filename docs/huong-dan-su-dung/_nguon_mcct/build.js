const fs = require('fs');
const d = require('docx');
const {
  Document, Packer, Paragraph, TextRun, HeadingLevel, AlignmentType,
  Table, TableRow, TableCell, WidthType, ShadingType, BorderStyle,
  LevelFormat, convertInchesToTwip, TableOfContents, PageBreak,
} = d;

const FONT = 'Times New Roman';
const W = 9026; // be rong long trang A4 voi le 1 inch

function P(text, opts = {}) {
  const { bold = false, italics = false, size = 26, align, spacing, color } = opts;
  return new Paragraph({
    alignment: align,
    spacing: spacing || { after: 120 },
    children: [new TextRun({ text, bold, italics, size, color, font: FONT })],
  });
}

function Rich(runs, opts = {}) {
  return new Paragraph({
    alignment: opts.align,
    spacing: opts.spacing || { after: 120 },
    children: runs.map((r) =>
      new TextRun({
        text: r.t,
        bold: !!r.b,
        italics: !!r.i,
        color: r.c,
        size: r.size || 26,
        font: FONT,
      })),
  });
}

function H(text, level) {
  return new Paragraph({
    heading: level,
    spacing: { before: 240, after: 120 },
    children: [new TextRun({ text, font: FONT })],
  });
}

function Bullet(text, opts = {}) {
  return new Paragraph({
    numbering: { reference: 'cham', level: 0 },
    spacing: { after: 80 },
    children: [new TextRun({ text, font: FONT, size: 26, bold: !!opts.bold })],
  });
}

function Step(text, instance = 0) {
  return new Paragraph({
    numbering: { reference: 'buoc', level: 0, instance },
    spacing: { after: 80 },
    children: [new TextRun({ text, font: FONT, size: 26 })],
  });
}

function cell(text, { widths, i, bold = false, fill, align, size = 24 }) {
  return new TableCell({
    width: { size: widths[i], type: WidthType.DXA },
    shading: fill ? { type: ShadingType.CLEAR, fill, color: 'auto' } : undefined,
    margins: { top: 60, bottom: 60, left: 100, right: 100 },
    children: [new Paragraph({
      alignment: align,
      spacing: { after: 0 },
      children: [new TextRun({ text, bold, size, font: FONT })],
    })],
  });
}

function Tbl(widths, header, rows) {
  const border = { style: BorderStyle.SINGLE, size: 4, color: '999999' };
  return new Table({
    width: { size: widths.reduce((a, b) => a + b, 0), type: WidthType.DXA },
    columnWidths: widths,
    borders: {
      top: border, bottom: border, left: border, right: border,
      insideHorizontal: border, insideVertical: border,
    },
    rows: [
      new TableRow({
        tableHeader: true,
        children: header.map((t, i) =>
          cell(t, { widths, i, bold: true, fill: 'DDEBF7', align: AlignmentType.CENTER })),
      }),
      ...rows.map((r) => new TableRow({
        children: r.map((t, i) => cell(String(t), { widths, i })),
      })),
    ],
  });
}

/* ------------------------------------------------------------------ */

const noiDung = [];

// Bia
noiDung.push(new Paragraph({
  alignment: AlignmentType.CENTER,
  spacing: { before: 1200, after: 120 },
  children: [new TextRun({ text: 'HƯỚNG DẪN SỬ DỤNG', bold: true, size: 36, font: FONT })],
}));
noiDung.push(new Paragraph({
  alignment: AlignmentType.CENTER,
  spacing: { after: 240 },
  children: [new TextRun({
    text: 'CHỨC NĂNG TRA CỨU TIỀN CÙNG CHI TRẢ / MIỄN CÙNG CHI TRẢ (MCCT)',
    bold: true, size: 32, font: FONT,
  })],
}));
noiDung.push(P('Phần mềm quản lý bệnh viện qlbv', { align: AlignmentType.CENTER, size: 28 }));
noiDung.push(P('Phiên bản tài liệu: 2.1 — Ngày 15/9/2026',
  { align: AlignmentType.CENTER, italics: true, size: 24 }));

noiDung.push(new Paragraph({ children: [new PageBreak()] }));

// 1
noiDung.push(H('1. Chức năng này dùng để làm gì', HeadingLevel.HEADING_1));
noiDung.push(P('Chức năng cho phép tra cứu số tiền cùng chi trả bảo hiểm y tế mà người bệnh đã trả '
  + 'lũy kế trong năm tài chính, lấy trực tiếp từ Cổng tiếp nhận dữ liệu thuộc Hệ thống thông tin '
  + 'giám định BHYT. Từ số lũy kế đó, phần mềm xác định người bệnh đã đủ điều kiện được miễn cùng '
  + 'chi trả hay chưa.'));
noiDung.push(P('Căn cứ:', { bold: true }));
noiDung.push(Bullet('Điểm b khoản 2 Điều 18 Nghị định số 188/2025/NĐ-CP ngày 01/7/2025 của Chính phủ.'));
noiDung.push(Bullet('Công văn số 1839/CNTT-PM ngày 18/8/2026 của Trung tâm Công nghệ thông tin và '
  + 'Chuyển đổi số, Bảo hiểm xã hội Việt Nam.'));
noiDung.push(P('Điều kiện miễn cùng chi trả gồm HAI vế, phải đủ cả hai:', { bold: true }));
noiDung.push(Bullet('Tham gia BHYT đủ 5 năm liên tục trở lên; và'));
noiDung.push(Bullet('Số tiền cùng chi trả lũy kế trong năm lớn hơn 6 tháng lương cơ sở. Bằng '
  + 'đúng mức đó thì chưa đủ.'));
noiDung.push(Rich([
  { t: 'Phần mềm chỉ kiểm được vế thứ hai. ', b: true, c: 'C00000' },
  { t: 'Hàm tra cứu của cổng BHXH không trả về dữ kiện 5 năm liên tục, nên khi đạt ngưỡng '
    + 'tiền, màn hình chỉ ghi “ĐỦ NGƯỠNG 6 THÁNG LƯƠNG CƠ SỞ” chứ không kết luận là đã đủ '
    + 'điều kiện miễn. Vế 5 năm liên tục phải do cán bộ kiểm tra riêng.' },
]));

// 2
noiDung.push(H('2. Trước khi sử dụng (dành cho quản trị hệ thống)', HeadingLevel.HEADING_1));
noiDung.push(P('Ba việc phải hoàn tất một lần trước khi cán bộ có thể tra cứu:'));
noiDung.push(Step('Chạy lệnh tạo bảng lưu dữ liệu: php artisan migrate. Chưa chạy thì vẫn tra cứu '
  + 'được nhưng phần mềm không lưu được lịch sử và sẽ hiện cảnh báo.', 1));
noiDung.push(Step('Khai tài khoản cổng BHXH của từng cơ sở khám chữa bệnh trong tệp '
  + 'config/organization.php, khối BHYT_CO_SO. Cơ sở chưa khai thì không tra cứu được — phần mềm '
  + 'không dùng tài khoản của cơ sở khác thay thế.', 1));
noiDung.push(Step('Kiểm tra mức lương cơ sở trong tệp config/mcct.php, khối luong_co_so. Đây là căn '
  + 'cứ tính ngưỡng miễn cùng chi trả. Mỗi lần Nhà nước điều chỉnh lương cơ sở phải bổ sung một '
  + 'dòng mốc mới vào đây.', 1));
noiDung.push(Rich([
  { t: 'Lưu ý quan trọng về địa chỉ IP: ', b: true },
  { t: 'cổng BHXH khóa phiên làm việc theo địa chỉ IP. Địa chỉ IP mà máy chủ dùng để gọi cổng '
    + 'phải trùng với địa chỉ IP đã đăng ký với cơ quan BHXH, nếu không mọi lần tra cứu đều báo '
    + 'lỗi xác thực.' },
]));

// 3
noiDung.push(H('3. Hai cách tra cứu', HeadingLevel.HEADING_1));

noiDung.push(H('3.1. Tra ngay trên màn hình Tra cứu thẻ BHYT (khuyến nghị)', HeadingLevel.HEADING_2));
noiDung.push(P('Đây là cách nhanh nhất khi cán bộ đang tiếp đón người bệnh, vì thông tin thẻ đã có '
  + 'sẵn, không phải nhập lại.'));
noiDung.push(Step('Vào menu Thẻ BHYT > Tra cứu thẻ BHYT.', 2));
noiDung.push(Step('Chọn cơ sở khám chữa bệnh, nhập mã thẻ BHYT/CCCD, họ tên, ngày sinh rồi bấm Tra cứu.', 2));
noiDung.push(Step('Khi tra thẻ thành công, nút Tra tiền cùng chi trả (màu xanh lá) hiện ra ngay '
  + 'cạnh nút Tra cứu. Bấm vào đó.', 2));
noiDung.push(Step('Một cửa sổ hiện lên và bắt đầu hỏi cổng BHXH. Chờ cho tới khi có kết quả.', 2));
noiDung.push(Rich([
  { t: 'Nút chỉ xuất hiện khi tra thẻ thành công. ', b: true },
  { t: 'Nếu tra thẻ báo lỗi hoặc không tìm thấy thẻ thì nút không hiện, vì khi đó thông tin thẻ '
    + 'chưa đủ tin cậy để tra tiếp.' },
]));

noiDung.push(H('3.2. Tra trên màn hình riêng', HeadingLevel.HEADING_2));
noiDung.push(P('Dùng khi cần tra độc lập, không đi từ màn tra cứu thẻ. Màn hình có ô Quét QR ở '
  + 'hàng trên cùng; hàng dưới là bốn ô Cơ sở KCB, Mã thẻ BHYT/CCCD, Họ và tên, Ngày sinh và nút '
  + 'Tra cứu.'));
noiDung.push(Step('Vào menu Thẻ BHYT > Tra cứu tiền cùng chi trả.', 3));
noiDung.push(Step('Kiểm tra ô Cơ sở KCB. Thông thường ô này đã được chọn sẵn — xem mục 3.4.', 3));
noiDung.push(Step('Cách nhanh: con trỏ đã nằm sẵn ở ô Quét QR, chỉ cần quét mã QR trên thẻ BHYT '
  + 'hoặc căn cước công dân. Mã thẻ, họ tên, ngày sinh được điền tự động và phần mềm tự tra '
  + 'cứu luôn, không phải bấm thêm.', 3));
noiDung.push(Step('Cách thủ công: nhập mã thẻ BHYT/CCCD, họ tên, ngày sinh rồi bấm Tra cứu (hoặc '
  + 'nhấn Enter ở bất kỳ ô nào).', 3));
noiDung.push(Step('Kết quả hiện ra ngay bên dưới, trang không tải lại. Nếu thẻ đã từng được tra '
  + 'thành công, phần mềm hiện kết quả lần trước — xem mục 3.3.', 3));
noiDung.push(Rich([
  { t: 'Trang không bị tải lại khi tra cứu. ', b: true },
  { t: 'Trong lúc chờ cổng trả lời, phần tìm kiếm vẫn hiển thị và đồng hồ đếm giây chạy ngay trên '
    + 'trang (xem mục 6). Thanh địa chỉ của trình duyệt tự cập nhật theo thẻ đang tra, nên có thể '
    + 'sao chép đường dẫn gửi cho người khác, hoặc nhấn F5 để mở lại đúng thẻ đó.' },
]));
noiDung.push(Rich([
  { t: 'Về ngày sinh khi quét QR: ', b: true },
  { t: 'mã QR trên thẻ thường ghi ngày sinh thành 8 chữ số liền (ví dụ 20101964). Phần mềm tự '
    + 'chuyển sang dạng 20/10/1964 trước khi tra, nên không bị báo sai định dạng.' },
]));

noiDung.push(H('3.3. Kết quả lần tra trước và nút Tra cứu lại', HeadingLevel.HEADING_2));
noiDung.push(P('Cổng BHXH trả lời chậm và giới hạn số lượt tra của mỗi tài khoản. Vì vậy, khi bấm '
  + 'Tra cứu (trên màn hình riêng) hoặc bấm Tra tiền cùng chi trả (trên màn tra cứu thẻ), phần '
  + 'mềm KHÔNG gọi cổng ngay mà làm như sau:'));
noiDung.push(Bullet('Nếu thẻ này đã từng được tra cứu thành công, phần mềm hiện ngay kết quả của '
  + 'lần tra thành công gần nhất — gần như tức thì và không tốn lượt tra của cổng.'));
noiDung.push(Bullet('Nếu thẻ chưa từng được tra cứu thành công lần nào, phần mềm gọi cổng BHXH như '
  + 'bình thường.'));
noiDung.push(P('Khi đang xem kết quả lần trước, phía trên kết quả có một khung màu vàng ghi: '
  + '“Số liệu này lấy từ lần tra trước, tra lúc …. Cổng có thể đã cập nhật thêm.” kèm nút '
  + 'Tra cứu lại ở bên phải.'));
noiDung.push(Step('Đọc thời điểm “tra lúc” trong khung vàng để biết số liệu cũ đến mức nào.', 4));
noiDung.push(Step('Nếu cần số liệu mới nhất — ví dụ người bệnh vừa ra viện ở cơ sở khác, hoặc '
  + 'sắp trả lời kết luận miễn cùng chi trả — bấm Tra cứu lại.', 4));
noiDung.push(Step('Phần mềm gọi cổng BHXH thật; chờ như mục 6. Kết quả mới thay thế kết quả cũ và '
  + 'khung vàng biến mất.', 4));
noiDung.push(Rich([
  { t: 'Phân biệt hai mốc thời gian. ', b: true, c: 'C00000' },
  { t: '“Tra lúc” trong khung vàng là thời điểm phần mềm hỏi cổng. “Tính đến” ở dòng chữ nhỏ dưới '
    + 'khối kết luận (mục 5.2) là mốc dữ liệu của chính cổng BHXH. Hai mốc này khác nhau; kết '
    + 'luận với người bệnh phải căn cứ vào mốc “tính đến”.' },
]));
noiDung.push(P('Kết quả lần trước được tính lại ngưỡng theo đúng ngày của lần tra đó, nên kết luận '
  + 'hiển thị khớp với kết luận tại thời điểm tra. Chỉ những lần tra THÀNH CÔNG mới được dùng lại; '
  + 'lần tra báo lỗi không bao giờ được hiển thị như kết quả. Nút Thử lại ở khung báo lỗi luôn gọi '
  + 'thẳng cổng.', { italics: true }));

noiDung.push(H('3.4. Phần mềm tự ghi nhớ cơ sở và tự viết hoa họ tên', HeadingLevel.HEADING_2));
noiDung.push(P('Ghi nhớ cơ sở khám chữa bệnh:', { bold: true }));
noiDung.push(Bullet('Mỗi lần chọn Cơ sở KCB, phần mềm ghi nhớ lựa chọn đó trên trình duyệt. Lần '
  + 'sau mở màn hình, ô Cơ sở KCB được chọn sẵn, không phải chọn lại.'));
noiDung.push(Bullet('Màn Tra cứu tiền cùng chi trả và màn Tra cứu thẻ BHYT dùng chung lựa chọn này: '
  + 'chọn cơ sở ở màn nào thì màn kia cũng nhớ theo.'));
noiDung.push(Bullet('Việc ghi nhớ gắn với từng máy tính và từng trình duyệt. Đổi máy, đổi trình '
  + 'duyệt, dùng cửa sổ ẩn danh hoặc xóa dữ liệu duyệt web thì phải chọn lại một lần.'));
noiDung.push(Bullet('Nếu đơn vị chỉ có một cơ sở, ô này luôn được chọn sẵn.'));
noiDung.push(Bullet('Nếu cơ sở đã nhớ sau đó bị gỡ khỏi cấu hình, ô để trống để cán bộ tự chọn — '
  + 'phần mềm không tự chọn một cơ sở khác thay thế, tránh tra nhầm bằng tài khoản của cơ sở khác.'));
noiDung.push(P('Chỉ lựa chọn cơ sở được ghi nhớ; phần mềm không lưu tài khoản, mật khẩu hay thông '
  + 'tin người bệnh trên trình duyệt.', { italics: true }));
noiDung.push(P('Viết hoa họ tên:', { bold: true }));
noiDung.push(Bullet('Họ và tên tự chuyển thành chữ in hoa ngay khi gõ, kể cả họ tên được điền từ mã '
  + 'QR. Có thể gõ chữ thường bình thường, không cần bật Caps Lock.'));

// 4
noiDung.push(H('4. Quy tắc nhập liệu', HeadingLevel.HEADING_1));
noiDung.push(Tbl([1800, 2400, 4826],
  ['Trường', 'Yêu cầu', 'Ghi chú'],
  [
    ['Cơ sở KCB', 'Bắt buộc chọn', 'Chỉ hiện những cơ sở đã khai tài khoản cổng BHXH. Chưa chọn thì phần mềm nhắc chọn chứ không báo lỗi. Lựa chọn được ghi nhớ cho lần sau (mục 3.4).'],
    ['Mã thẻ BHYT/CCCD', 'Đúng 10, 12, 15 hoặc 17 ký tự', 'Mã thẻ BHYT 15 hoặc 17 ký tự, mã số BHXH/số CCCD 10 hoặc 12 ký tự (Công văn 2746/BHXH-CNTT). Có thể gõ kèm dấu cách cho dễ đọc; phần mềm tự bỏ khoảng trắng trước khi kiểm tra.'],
    ['Họ và tên', 'Bắt buộc', 'Tự chuyển thành chữ in hoa ngay khi gõ (mục 3.4).'],
    ['Ngày sinh', 'Một trong ba dạng: 20/10/1964 hoặc 10/1964 hoặc 1964', 'Phải đủ hai chữ số cho ngày và tháng. Gõ 1/1/1964 sẽ bị báo sai. Ngày sinh 8 chữ số liền từ mã QR được tự chuyển sang dạng có dấu gạch chéo.'],
  ]));

// 5
noiDung.push(H('5. Đọc kết quả tra cứu', HeadingLevel.HEADING_1));

noiDung.push(H('5.1. Thông tin thẻ', HeadingLevel.HEADING_2));
noiDung.push(P('Họ tên, ngày sinh, mã số BHXH và ngày hết hạn thẻ do cổng BHXH trả về. Đối chiếu với '
  + 'giấy tờ của người bệnh.'));

noiDung.push(H('5.2. Khối kết luận', HeadingLevel.HEADING_2));
noiDung.push(P('Đây là phần quan trọng nhất, gồm ba số:'));
noiDung.push(Bullet('Lũy kế cùng chi trả: tổng số tiền người bệnh đã cùng chi trả trong năm, tính '
  + 'đến thời điểm dữ liệu của cổng.'));
noiDung.push(Bullet('Ngưỡng cả năm: số tiền cùng chi trả người bệnh cần đạt trong năm. Xem '
  + 'mục 5.3 — con số này KHÔNG phải lúc nào cũng bằng 6 tháng lương cơ sở hiện hành.'));
noiDung.push(Bullet('Kết luận: nhãn màu xanh ĐỦ NGƯỠNG 6 THÁNG LƯƠNG CƠ SỞ, hoặc nhãn màu vàng '
  + 'CÒN THIẾU kèm số tiền còn thiếu.'));
noiDung.push(P('Hai số đầu đều tính từ ngày 01/01 nên so sánh trực tiếp được với nhau: lũy kế '
  + 'đạt tới ngưỡng cả năm là đủ.'));
noiDung.push(Rich([
  { t: 'Khi đạt ngưỡng, màn hình hiện thêm một khung nhắc kiểm tra điều kiện 5 năm liên tục. ',
    b: true },
  { t: 'Đọc kỹ khung đó trước khi trả lời người bệnh — đạt ngưỡng tiền mới là một nửa điều kiện.' },
]));

noiDung.push(Rich([
  { t: 'Bắt buộc đọc dòng chữ nhỏ ngay bên dưới. ', b: true, c: 'C00000' },
  { t: 'Dòng đó ghi nguồn dữ liệu và mốc thời gian, ví dụ: “Nguồn DL lấy từ các CSKCB đề nghị '
    + 'thanh toán KCB BHYT trên HTTTGĐ BHYT tính đến: 14/08/2026 14:41”. Số liệu của cổng ' },
  { t: 'có độ trễ', b: true },
  { t: ' — những đợt khám chữa bệnh mà cơ sở chưa gửi hồ sơ đề nghị thanh toán thì chưa được '
    + 'tính vào lũy kế. Phải xem mốc này trước khi kết luận với người bệnh.' },
]));

noiDung.push(H('5.3. Khi lương cơ sở thay đổi giữa năm', HeadingLevel.HEADING_2));
noiDung.push(P('Năm 2026 lương cơ sở đổi từ 2.340.000 đồng lên 2.530.000 đồng kể từ ngày '
  + '01/7/2026. Khi đó ngưỡng cả năm KHÔNG phải là 6 x 2.530.000 = 15.180.000 đồng.'));
noiDung.push(P('Theo điểm c khoản 2 Điều 18 Nghị định 188/2025/NĐ-CP, phần tiền đã cùng chi trả '
  + 'trước ngày đổi lương được quy đổi ra số tháng theo lương cũ; phần còn thiếu mới tính theo '
  + 'lương mới:'));
noiDung.push(P('Số tiền còn phải cùng chi trả = ( 6 − Tiền đã đóng trước 01/7 ÷ Lương cũ ) × Lương mới',
  { bold: true, align: AlignmentType.CENTER }));
noiDung.push(P('Ngưỡng cả năm = Tiền đã đóng trước 01/7 + Số tiền còn phải cùng chi trả.'));
noiDung.push(P('Ví dụ theo Thông báo của Bệnh viện: người bệnh đã cùng chi trả 13.000.000 đồng '
  + 'tính đến ngày 30/6/2026.'));
noiDung.push(Bullet('Số còn phải đóng = (6 − 13.000.000 ÷ 2.340.000) × 2.530.000 = 1.124.444 đồng.'));
noiDung.push(Bullet('Ngưỡng cả năm = 13.000.000 + 1.124.444 = 14.124.444 đồng — chưa đủ miễn '
  + 'cùng chi trả.'));
noiDung.push(P('Màn hình tự tính và hiện đầy đủ các bước này ngay dưới khối kết luận, nên không '
  + 'cần tính tay. Nếu người bệnh đã đóng đủ 6 tháng lương cũ trước ngày đổi lương thì được '
  + 'hưởng quyền lợi ngay, không áp dụng công thức.'));
noiDung.push(P('Đợt khám chữa bệnh được xếp vào trước hay sau mốc đổi lương theo NGÀY RA VIỆN.',
  { italics: true }));

noiDung.push(H('5.4. Bảng chi tiết các đợt khám chữa bệnh', HeadingLevel.HEADING_2));
noiDung.push(P('Liệt kê từng đợt khám chữa bệnh có phát sinh cùng chi trả, sắp xếp giảm dần theo '
  + 'ngày ra viện (đợt mới nhất ở trên cùng).'));
noiDung.push(Tbl([2400, 6626],
  ['Cột', 'Ý nghĩa'],
  [
    ['Mã CSKCB', 'Cơ sở nơi phát sinh đợt khám chữa bệnh đó — có thể là cơ sở khác, không phải cơ sở đang tra.'],
    ['Ngày vào / Ngày ra', 'Ngày vào viện và ngày ra viện của đợt đó.'],
    ['Đối tượng', 'Mã đối tượng khi đi khám chữa bệnh của đợt đó.'],
    ['Tiền CCT thuộc diện miễn', 'Số tiền cùng chi trả của riêng đợt đó được tính vào diện xét miễn.'],
    ['Lũy kế', 'Số tiền cùng chi trả cộng dồn tính đến hết đợt đó. Đây là con số dùng để xét ngưỡng.'],
    ['Ngày nhận', 'Ngày cổng tiếp nhận hồ sơ của đợt đó.'],
  ]));

noiDung.push(H('5.5. Lịch sử tra cứu', HeadingLevel.HEADING_2));
noiDung.push(P('Trên màn hình riêng, phần cuối trang hiển thị vài lần tra cứu gần nhất của chính '
  + 'thẻ đó: thời điểm tra, cơ sở, kết quả, lũy kế và ngưỡng áp dụng lúc đó.'));
noiDung.push(P('Ngưỡng được lưu lại theo đúng thời điểm tra cứu. Khi lương cơ sở tăng, các bản ghi '
  + 'cũ vẫn giữ nguyên kết luận cũ — đúng như tại thời điểm đó.', { italics: true }));

// 6
noiDung.push(H('6. Vì sao phải chờ, và không nên bấm nhiều lần', HeadingLevel.HEADING_1));
noiDung.push(P('Cổng BHXH thường trả lời trong khoảng 5 đến 30 giây, đôi khi lâu hơn. Trong lúc chờ, '
  + 'cửa sổ tra cứu (hoặc phần kết quả trên màn hình riêng) hiển thị đồng hồ đếm số giây đã chờ. Đồng hồ còn chạy nghĩa là hệ thống vẫn '
  + 'đang làm việc bình thường.'));
noiDung.push(P('Sau 25 giây, dòng chữ tự đổi thành “Cổng BHXH đang phản hồi chậm, vẫn đang chờ…”. '
  + 'Đây vẫn là trạng thái bình thường, không phải lỗi.'));
noiDung.push(Rich([
  { t: 'Không bấm lại nhiều lần. ', b: true, c: 'C00000' },
  { t: 'Cơ quan BHXH giới hạn số lượt tra cứu của mỗi tài khoản và có danh sách tài khoản bị hạn '
    + 'chế tra cứu. Mỗi lần bấm là một lượt gọi thật. Vì vậy trong lúc đang chờ, phần mềm tự khóa '
    + 'nút và các ô nhập liệu.' },
]));
noiDung.push(P('Để tiết kiệm lượt tra, hãy dùng kết quả lần trước (mục 3.3) khi số liệu vẫn còn đủ '
  + 'mới, và chỉ bấm Tra cứu lại khi thực sự cần số liệu cập nhật.'));
noiDung.push(P('Phần mềm chờ cổng tối đa 60 giây. Quá thời gian đó mà cổng không trả lời thì '
  + 'dừng chờ và báo lỗi; hãy đợi vài phút rồi bấm Thử lại.'));

// 7
noiDung.push(H('7. Các thông báo và cách xử lý', HeadingLevel.HEADING_1));
noiDung.push(Tbl([3400, 5626],
  ['Thông báo', 'Nguyên nhân và cách xử lý'],
  [
    ['Không tìm thấy dữ liệu. Có thể do sai thông tin thẻ, hoặc thẻ chưa phát sinh chi phí cùng chi trả.',
      'Cổng dùng chung một mã cho hai tình huống khác nhau. Kiểm tra lại mã thẻ, họ tên, ngày sinh. Nếu thông tin đã đúng thì nghĩa là thẻ chưa phát sinh chi phí cùng chi trả trong năm — đây không phải lỗi.'],
    ['Chọn cơ sở khám chữa bệnh rồi bấm Tra cứu',
      'Chưa chọn cơ sở. Chọn cơ sở trong ô đầu tiên rồi tra lại.'],
    ['Mã thẻ BHYT/CCCD phải có 10, 12, 15 hoặc 17 ký tự',
      'Nhập sai số ký tự. Kiểm tra lại mã thẻ trên thẻ BHYT hoặc trên căn cước công dân.'],
    ['Ngày sinh phải theo dd/mm/yyyy, mm/yyyy hoặc yyyy',
      'Nhập thiếu chữ số. Ví dụ phải gõ 01/01/1964 chứ không gõ 1/1/1964.'],
    ['Không xác thực được với cổng BHXH…',
      'Phiên làm việc hết hạn, hoặc địa chỉ IP của máy chủ khác với IP đã đăng ký với cơ quan BHXH. Báo bộ phận công nghệ thông tin.'],
    ['Tài khoản của cơ sở … đang bị cổng hạn chế tra cứu',
      'Đây là vấn đề tài khoản, không phải lỗi phần mềm. Liên hệ cơ quan BHXH tỉnh để được mở lại.'],
    ['Cơ sở … chưa khai tài khoản cổng BHXH…',
      'Cơ sở chưa được cấu hình. Báo bộ phận công nghệ thông tin khai tài khoản trong config/organization.php.'],
    ['Cổng BHXH không trả lời sau 60 giây…',
      'Cổng vẫn hoạt động nhưng trả lời quá chậm. Không phải sự cố đường truyền của bệnh viện — đợi vài phút rồi bấm Thử lại.'],
    ['Không kết nối được cổng BHXH',
      'Sự cố đường truyền hoặc cổng đang bảo trì. Chờ vài phút rồi thử lại.'],
    ['Cổng BHXH báo lỗi (500)…',
      'Lỗi phía hệ thống của cổng. Chờ và thử lại sau; nếu kéo dài thì báo cơ quan BHXH.'],
    ['Đã tra cứu được nhưng không lưu được lịch sử tra cứu.',
      'Kết quả hiển thị vẫn đúng và dùng được, chỉ là không ghi được vào cơ sở dữ liệu. Thường do chưa chạy lệnh tạo bảng. Báo bộ phận công nghệ thông tin.'],
  ]));

// 8
noiDung.push(H('8. Những điều cần lưu ý', HeadingLevel.HEADING_1));
noiDung.push(Bullet('Số liệu do cổng BHXH cung cấp, phần mềm không tự tính. Luôn đối chiếu mốc thời '
  + 'gian “tính đến …” trước khi trả lời người bệnh.'));
noiDung.push(Bullet('Lũy kế chỉ bao gồm các đợt khám chữa bệnh mà cơ sở đã gửi hồ sơ đề nghị thanh '
  + 'toán lên cổng. Đợt vừa kết thúc hôm nay thường chưa có trong số liệu.'));
noiDung.push(Bullet('Lũy kế tính theo năm tài chính và bao gồm cả các đợt khám chữa bệnh tại cơ sở '
  + 'khác, không chỉ riêng cơ sở đang tra.'));
noiDung.push(Bullet('Mọi lần tra cứu đều được ghi lại, kể cả lần không thành công, phục vụ đối chiếu '
  + 'về sau.'));
noiDung.push(Bullet('Khi thấy khung vàng “Số liệu này lấy từ lần tra trước”, đó là số liệu đã lưu, '
  + 'không phải số liệu vừa hỏi cổng. Cân nhắc bấm Tra cứu lại trước khi trả lời người bệnh.'));
noiDung.push(Bullet('Việc xác định người bệnh đủ điều kiện miễn cùng chi trả và cấp giấy chứng nhận '
  + 'thực hiện theo quy định hiện hành; kết quả tra cứu là căn cứ tham khảo, không thay thế thủ tục.'));

// Phu luc
noiDung.push(H('Phụ lục. Mức lương cơ sở và 6 tháng lương cơ sở', HeadingLevel.HEADING_1));
noiDung.push(Tbl([3000, 3000, 3026],
  ['Áp dụng từ ngày', 'Lương cơ sở', '6 tháng lương cơ sở'],
  [
    ['01/7/2023', '1.800.000 đ', '10.800.000 đ'],
    ['01/7/2024', '2.340.000 đ', '14.040.000 đ'],
    ['01/7/2026', '2.530.000 đ', '15.180.000 đ'],
  ]));
noiDung.push(Rich([
  { t: 'Cột cuối KHÔNG phải ngưỡng cả năm ', b: true },
  { t: 'trong những năm có thay đổi lương cơ sở giữa năm — xem mục 5.3. Nó chỉ là ngưỡng '
    + 'trong trường hợp cả năm áp dụng một mức lương duy nhất.' },
]));
noiDung.push(P('Bảng này lấy theo cấu hình hiện tại của phần mềm. Khi Nhà nước điều chỉnh lương cơ '
  + 'sở, bộ phận công nghệ thông tin bổ sung mốc mới vào tệp config/mcct.php.',
  { italics: true, size: 24 }));

/* ------------------------------------------------------------------ */

const doc = new Document({
  numbering: {
    config: [
      {
        reference: 'cham',
        levels: [{
          level: 0, format: LevelFormat.BULLET, text: '•',
          alignment: AlignmentType.LEFT,
          style: { paragraph: { indent: { left: 720, hanging: 360 } } },
        }],
      },
      {
        reference: 'buoc',
        levels: [{
          level: 0, format: LevelFormat.DECIMAL, text: '%1.',
          alignment: AlignmentType.START,
          style: { paragraph: { indent: { left: 720, hanging: 360 } } },
        }],
      },
    ],
  },
  styles: {
    default: {
      document: { run: { font: FONT, size: 26 } },
    },
    paragraphStyles: [
      {
        id: 'Heading1', name: 'Heading 1', basedOn: 'Normal', next: 'Normal', quickFormat: true,
        run: { font: FONT, size: 30, bold: true, color: '1F4E79' },
        paragraph: { spacing: { before: 280, after: 140 } },
      },
      {
        id: 'Heading2', name: 'Heading 2', basedOn: 'Normal', next: 'Normal', quickFormat: true,
        run: { font: FONT, size: 27, bold: true, color: '2E74B5' },
        paragraph: { spacing: { before: 220, after: 120 } },
      },
    ],
  },
  sections: [{
    properties: {
      page: { margin: { top: convertInchesToTwip(1), bottom: convertInchesToTwip(1),
        left: convertInchesToTwip(1), right: convertInchesToTwip(1) } },
    },
    children: noiDung,
  }],
});

Packer.toBuffer(doc).then((buf) => {
  const ra = process.argv[2];
  fs.writeFileSync(ra, buf);
  console.log('Da ghi: ' + ra + ' (' + buf.length + ' bytes)');
});
