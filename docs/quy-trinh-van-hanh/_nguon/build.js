// Dựng tài liệu "Quy trình vận hành Tiền giám định hồ sơ XML 3176" (.docx).
//
// Chạy ở thư mục gốc dự án (thư viện docx cài tạm hoặc toàn cục, xem README.md):
//   python docs/quy-trinh-van-hanh/_nguon/ve_so_do.py
//   node docs/quy-trinh-van-hanh/_nguon/build.js docs/quy-trinh-van-hanh/Quy-trinh-van-hanh-XML3176.docx
//
// Dùng lại bộ hàm trình bày của tài liệu hướng dẫn sử dụng để hai tài liệu cùng kiểu.
const fs = require('fs');
const path = require('path');
const {
  Document, Packer, Paragraph, TextRun, LevelFormat, AlignmentType, Footer, Header,
  PageNumber, convertInchesToTwip, BorderStyle, ImageRun, PageOrientation,
} = require('docx');
const L = require('../../huong-dan-su-dung/_nguon/lib');

const { FONT, run, h1, h2, p, bullet, note, forIt, table, spacer } = L;
const PHIEN_BAN = '0.1 — dự thảo để góp ý';
const NGAY = '28/09/2026';

const b = (t) => run(t, { bold: true });
const para = (...parts) => p(parts.map((x) => (typeof x === 'string' ? run(x) : x)));

function anh(ten, rongPx) {
  const file = path.join(__dirname, 'anh', ten);
  const data = fs.readFileSync(file);
  // Kích thước gốc PNG (IHDR) để giữ đúng tỉ lệ.
  const w = data.readUInt32BE(16);
  const h = data.readUInt32BE(20);
  return new Paragraph({
    alignment: AlignmentType.CENTER,
    spacing: { before: 120, after: 80 },
    children: [new ImageRun({ type: 'png', data, transformation: { width: rongPx, height: Math.round((rongPx * h) / w) } })],
  });
}

function chuThich(text) {
  return new Paragraph({
    alignment: AlignmentType.CENTER,
    spacing: { after: 200 },
    children: [run(text, { italics: true, size: 22, color: '595959' })],
  });
}

// ---------------------------------------------------------------- trang bìa
function bia() {
  const dong = (text, opts = {}) => new Paragraph({
    alignment: AlignmentType.CENTER,
    spacing: { before: opts.before || 0, after: opts.after || 120 },
    children: [new TextRun({ text, font: FONT, size: opts.size || 26, bold: !!opts.bold, color: opts.color, italics: !!opts.italics })],
  });
  return [
    dong('BỆNH VIỆN BẠCH MAI', { bold: true, size: 28, before: 600 }),
    dong('PHÒNG CÔNG NGHỆ THÔNG TIN', { bold: true, size: 26, after: 1800 }),
    dong('QUY TRÌNH VẬN HÀNH', { bold: true, size: 44, color: '1F3864' }),
    dong('TIỀN GIÁM ĐỊNH HỒ SƠ BHYT THEO XML 3176', { bold: true, size: 36, color: '1F3864', after: 400 }),
    dong('Nạp hồ sơ → Kiểm tra dữ liệu → Ký số → Gửi cổng → Theo dõi kết quả', { italics: true, size: 26, color: '2E5496', after: 2400 }),
    dong('Phiên bản ' + PHIEN_BAN, { size: 24 }),
    dong('Ngày soạn: ' + NGAY, { size: 24, after: 600 }),
    dong('Tài liệu gợi ý để các khoa, phòng góp ý. Các thời hạn và lịch định kỳ ghi "đề xuất" do Hội đồng / Ban Giám đốc chốt.', { italics: true, size: 22, color: '595959' }),
  ];
}

// ---------------------------------------------------------------- phần 1–3 (dọc)
function phanDau() {
  return [
    h1('1. Mục đích và phạm vi', { pageBreak: false }),
    p('Tài liệu thống nhất cách các khoa, phòng phối hợp vận hành phần mềm Tiền giám định để hồ sơ BHYT được kiểm tra và sửa lỗi TRƯỚC khi ký số và gửi lên Cổng tiếp nhận của BHXH, giảm hồ sơ bị trả về và bị xuất toán.'),
    bullet('Phạm vi: toàn bộ hồ sơ khám bệnh, chữa bệnh BHYT kết xuất theo chuẩn dữ liệu XML 3176 (XML1–XML15), mọi cơ sở của Bệnh viện.'),
    bullet('Đối tượng: các khoa lâm sàng và bộ phận tiếp đón; Phòng BHYT / Giám định; Phòng Tài chính kế toán (viện phí); Phòng Công nghệ thông tin.'),
    bullet('Không thay thế quy định chuyên môn và quy định thanh toán BHYT; phần mềm là công cụ hỗ trợ kiểm soát dữ liệu.'),

    h1('2. Ba nguyên tắc', { pageBreak: false }),
    note('Nguyên tắc 1 — Đủ 100%:', '100% hồ sơ BHYT phải qua Tiền giám định trước khi lên cổng. Trong lần BHXH trả lỗi mã đối tượng 1.7 (tháng 9/2026), 12/13 hồ sơ bị trả chưa từng được nạp vào Tiền giám định nên không quy tắc nào kiểm được.'),
    note('Nguyên tắc 2 — Sửa tại nguồn:', 'Sai ở đâu sửa ở đó, trên HIS, tại đơn vị phát sinh. Phần mềm chỉ ĐỌC dữ liệu HIS. Sửa xong phải NẠP LẠI hồ sơ thì kết quả kiểm mới đổi; bấm "Đã xử lý" không sửa được dữ liệu.'),
    note('Nguyên tắc 3 — Cổng nhận là nhận thật:', 'Không có đường rút lại hồ sơ đã được cổng tiếp nhận. Mọi việc chặn lỗi phải xong trước bước ký số và gửi.'),

    h1('3. Vai trò và trách nhiệm', { pageBreak: false }),
    table(['Đơn vị', 'Trách nhiệm chính'], [
      ['Khoa lâm sàng', 'Xử lý vi phạm y lệnh trong ngày; sửa trên HIS các lỗi hồ sơ được giao (ICD, giờ y lệnh, ngày giường, người thực hiện, chứng chỉ hành nghề…); quét mã vạch tra lỗi hồ sơ trước khi cho người bệnh ra viện.'],
      ['Tiếp đón / hành chính khoa', 'Nhập đúng thẻ BHYT, nơi ĐKBĐ, mã đối tượng KCB, ngày sinh; chọn đúng loại kết thúc điều trị (chỉ "Hẹn khám lại" mới sinh giấy hẹn khám lại).'],
      ['Phòng BHYT / Giám định', 'Theo dõi Danh sách hồ sơ và Dashboard lỗi; giao lỗi cho khoa và đôn đốc; quản lý danh mục mã lỗi (mức Nghiêm trọng / Cảnh báo); theo dõi kết quả cổng; tổng hợp hồ sơ BHXH trả về.'],
      ['Phòng Tài chính kế toán', 'Tra cứu tiền cùng chi trả (MCCT); đối chiếu chi phí trước khi chốt thanh toán ra viện.'],
      ['Phòng Công nghệ thông tin', 'Vận hành hàng đợi, bộ quét thư mục, ký số (HSM / USB Token), tài khoản cổng; cập nhật danh mục dùng chung; sửa quy tắc khi có báo nhầm; triển khai phiên bản mới.'],
    ], [2600, 6420]),
  ];
}

// ---------------------------------------------------------------- sơ đồ (ngang)
function soDo() {
  return [
    h1('4. Sơ đồ luồng', { pageBreak: false }),
    h2('4.1. Luồng tổng thể theo vai trò'),
    anh('so-do-luong-tong-the.png', 860),
    chuThich('Hình 1. Luồng một hồ sơ từ khi điều trị tới khi có kết quả giám định. Đường đỏ là vòng sửa lỗi: sửa trên HIS rồi nạp lại.'),
    new Paragraph({ pageBreakBefore: true, children: [] }),
    h2('4.2. Xử lý một lỗi'),
    anh('so-do-xu-ly-loi.png', 820),
    chuThich('Hình 2. Mỗi lỗi đi theo một trong ba nhánh: báo nhầm, Nghiêm trọng hoặc Cảnh báo.'),
  ];
}

// ---------------------------------------------------------------- phần sau (dọc)
function phanSau() {
  return [
    h1('5. Các bước chi tiết', { pageBreak: false }),
    table(['Bước', 'Ai làm', 'Xem ở đâu', 'Xong khi'], [
      ['Trước khi chốt: kiểm sớm', 'Hệ thống (kiểm y lệnh khoảng 60 giây sau khi phát sinh; tra thẻ BHYT người bệnh đang nằm hằng ngày); khoa xử lý', 'Kiểm tra sai sót y lệnh → Danh sách vi phạm; Hồ sơ XML → Kết quả tra cứu thẻ', 'Vi phạm trong ngày đã "Đã xử lý" / "Bỏ qua" kèm lý do; thẻ lỗi đã sửa và bấm "Tra lại"'],
      ['1. Nạp hồ sơ', 'Hệ thống tự quét thư mục; Phòng BHYT nạp tay khi cần', 'Hồ sơ XML → Xml 3176 → Nhập khẩu / Danh sách hồ sơ', 'Hồ sơ có trong Danh sách hồ sơ; tệp hỏng nằm ở thư mục con "loi"'],
      ['2. Kiểm tra', 'Hệ thống chạy nền; Phòng BHYT đọc lỗi và giao khoa', 'Danh sách hồ sơ (dòng đỏ), tab Lỗi XML, Tra cứu lỗi hồ sơ theo mã điều trị', 'Hàng đợi về 0 và hồ sơ hết lỗi Nghiêm trọng'],
      ['3. Ký số', 'Hệ thống (HSM hoặc USB Token)', 'Cột "Ký XML"', '"Đã ký số (HSM)" hoặc "Đã ký số (USB Token)"'],
      ['4. Gửi cổng', 'Hệ thống — chức năng tự gửi bật theo từng cơ sở', 'Cột "Sub" (rê chuột xem thông điệp cổng)', 'Có thời điểm gửi, không kèm thông điệp lỗi'],
      ['5. Theo dõi kết quả', 'Phòng BHYT / Giám định', 'Lọc "Gửi có lỗi"; Dashboard lỗi XML (phễu, Pareto mã lỗi)', 'Lỗi cổng trả về đã sửa trên HIS, nạp lại và gửi lại'],
    ], [1500, 2500, 2520, 2500]),
    spacer(),

    h1('6. Xử lý lỗi theo mức', { pageBreak: false }),
    table(['Loại', 'Ảnh hưởng', 'Cách xử lý'], [
      ['Nghiêm trọng', 'Chặn xuất, ký số và gửi (khi cấu hình chặn đang bật — xem Phụ lục B)', 'Phòng BHYT giao khoa; khoa sửa trên HIS trong hạn đề xuất 2 ngày làm việc; nạp lại tới khi hết lỗi.'],
      ['Cảnh báo', 'Hồ sơ vẫn được ký và gửi; lỗi được ghi nhận', 'Phòng BHYT tổng hợp theo tuần (Dashboard, Pareto mã lỗi); lỗi lặp lại nhiều thì nhắc khoa, đào tạo lại hoặc cân nhắc nâng lên Nghiêm trọng.'],
      ['Báo nhầm (quy tắc sai)', 'Làm mất thời gian, dễ khiến người dùng bỏ qua cảnh báo thật', 'Ghi mã hồ sơ, gửi Phòng CNTT sửa quy tắc. KHÔNG tắt mã lỗi trong danh mục để "cho qua".'],
    ], [2000, 3000, 4020]),
    note('Lưu ý:', 'Mã lỗi mới chưa có trong danh mục được hệ thống mặc định coi là Nghiêm trọng. Mỗi lần triển khai quy tắc mới, CNTT phải nạp mã lỗi vào danh mục (lệnh migrate) trước khi kiểm tra lại hồ sơ.'),

    h1('7. Lịch vận hành đề xuất', { pageBreak: false }),
    table(['Tần suất', 'Ai', 'Việc'], [
      ['Hằng ngày — sáng', 'Khoa lâm sàng', 'Xử lý vi phạm y lệnh của ngày hôm trước; quét mã vạch tra lỗi hồ sơ trước khi cho ra viện.'],
      ['Hằng ngày — sáng', 'Phòng BHYT', 'Xem hồ sơ có lỗi Nghiêm trọng của các ngày ra viện gần nhất và giao khoa; xem Kết quả tra cứu thẻ (lọc "Chỉ lỗi"), bấm "Tra lại" các thẻ đã sửa.'],
      ['Hằng ngày — chiều', 'Phòng BHYT', 'Lọc "Gửi có lỗi", đọc thông điệp cổng, giao khoa sửa.'],
      ['Hằng ngày', 'Phòng CNTT', 'Kiểm hàng đợi, bộ quét thư mục, ký số; đọc email báo lỗi hằng ngày.'],
      ['Hằng tuần', 'Phòng BHYT + các khoa nhiều lỗi', 'Họp ngắn theo Pareto mã lỗi trên Dashboard; đối chiếu số hồ sơ đã lên cổng với số hồ sơ đã qua Tiền giám định để phát hiện hồ sơ gửi vòng qua.'],
      ['Hằng tháng / trước quyết toán', 'Phòng BHYT, CNTT', 'Tra thẻ hàng loạt; rà danh sách BHXH từ chối để đề xuất quy tắc mới; cập nhật danh mục có ngày hiệu lực mới; rà lại mức lỗi trong danh mục mã lỗi.'],
    ], [2200, 2200, 4620]),

    h1('8. Chỉ số theo dõi', { pageBreak: false }),
    table(['Chỉ số', 'Cách tính', 'Mục tiêu đề xuất'], [
      ['Độ phủ', 'Hồ sơ đã qua Tiền giám định / hồ sơ đã lên cổng', '100%'],
      ['Hồ sơ sạch ở lần kiểm đầu', 'Hồ sơ không có lỗi Nghiêm trọng ngay lần nạp đầu / tổng hồ sơ nạp', 'Tăng dần theo tháng'],
      ['Thời gian sửa lỗi', 'Từ lúc giao lỗi tới lúc nạp lại hết lỗi Nghiêm trọng', '≤ 2 ngày làm việc'],
      ['Tỷ lệ cổng / BHXH từ chối', 'Hồ sơ bị trả về / hồ sơ đã gửi; tách phần hệ thống đã báo trước', 'Giảm theo tháng'],
      ['Top 10 mã lỗi theo khoa', 'Pareto mã lỗi trên Dashboard, lọc theo khoa', 'Dùng để đào tạo có trọng tâm'],
    ], [2600, 4200, 2220]),

    h1('9. Việc cần chốt ngay', { pageBreak: false }),
    bullet([b('Không để hồ sơ lên cổng mà chưa qua Tiền giám định. '), run('Mọi đường gửi khác (ví dụ HIS tự gửi) cần tắt hoặc bắt buộc đi qua Tiền giám định.')]),
    bullet([b('Sửa từ khâu nhập liệu trên HIS các lỗi có hệ thống, '), run('ví dụ: 171 đợt điều trị người lớn khai mã đối tượng 1.7 từ tháng 7/2026; chọn "Ra viện" nhưng vẫn điền ngày hẹn khám lại. Đào tạo lại bộ phận tiếp đón.')]),
    bullet([b('Phân quyền danh mục mã lỗi: '), run('chỉ Phòng BHYT (và CNTT) được đổi mức Nghiêm trọng / Cảnh báo; mỗi lần đổi ghi lý do.')]),
    bullet([b('Quy trình triển khai của CNTT: '), run('kéo mã nguồn → php artisan migrate → php artisan config:cache → php artisan queue:restart → kiểm tra lại các hồ sơ bị ảnh hưởng.')]),

    h1('Phụ lục A. Tra nhanh màn hình'),
    table(['Việc', 'Đường dẫn trên phần mềm'], [
      ['Xem, lọc, xuất danh sách hồ sơ', 'Hồ sơ XML → Xml 3176 → Danh sách hồ sơ'],
      ['Nạp hồ sơ thủ công', 'Hồ sơ XML → Xml 3176 → Nhập khẩu hồ sơ'],
      ['Tra toàn bộ lỗi của một hồ sơ (quét mã vạch)', 'Tra cứu lỗi hồ sơ theo mã điều trị'],
      ['Vi phạm y lệnh', 'Kiểm tra sai sót y lệnh → Danh sách vi phạm'],
      ['Kết quả tra thẻ, tra lại thẻ lỗi', 'Hồ sơ XML → Kết quả tra cứu thẻ'],
      ['Tiền cùng chi trả', 'Thẻ BHYT → Tra cứu tiền cùng chi trả'],
      ['Mức lỗi Nghiêm trọng / Cảnh báo', 'Quản lý danh mục → BHYT → Danh mục mã lỗi XML 3176'],
      ['Chỉ số, phễu, Pareto mã lỗi', 'Dashboard lỗi XML'],
    ], [4000, 5020]),

    h1('Phụ lục B. Công tắc cấu hình (dành cho CNTT)', { pageBreak: false }),
    forIt('Các công tắc dưới đây quyết định hồ sơ lỗi có bị chặn hay không. Ghi rõ giá trị đang dùng trên máy chủ thật và người chịu trách nhiệm mỗi lần đổi.'),
    table(['Khóa cấu hình', 'Ý nghĩa', 'Khuyến nghị'], [
      ['organization.xml_3176_not_check', 'true = bỏ qua kiểm tra chéo (XMLComplete) khi nạp', 'false — luôn kiểm tra'],
      ['organization.export_xml_not_check', 'true = vẫn xuất / ký / gửi hồ sơ có lỗi Nghiêm trọng', 'false khi các khoa đã quen quy trình; để true thì mức Nghiêm trọng chỉ còn là cảnh báo'],
      ['organization.BHYT.submit_xml_3176_enabled', 'Bật / tắt chức năng gửi hồ sơ lên cổng', 'true trên máy chủ gửi thật'],
      ['xml3176.export_xml3176_enabled', 'Tự động xuất XML cho hồ sơ không có lỗi Nghiêm trọng', 'true'],
      ['xml3176.sign_queue_name (dịch vụ QLBV JobSignXml3176)', 'Hàng đợi bước ký số trong chuỗi kiểm → xuất → ký → gửi', 'Dịch vụ phải luôn chạy; dừng thì mọi hồ sơ nằm chờ ở bước ký'],
      ['xml3176.xuat_tep_queue_name (dịch vụ QLBV JobXuatTepXml3176)', 'Hàng đợi xuất danh sách lỗi chạy nền, kết nối xuat_tep (retry_after 3600)', 'Dịch vụ phải luôn chạy; dừng thì yêu cầu xuất nằm ở "Đang chờ". Một lần xuất ngày lớn cần ~2,5 GB RAM'],
    ], [3100, 3400, 2520]),
    forIt('Hồ sơ đã kiểm xong mà chưa xuất (ví dụ sau sự cố HSM hoặc mất kết nối cơ sở dữ liệu) được đẩy lại bằng lệnh php artisan xml3176:chay-lai-tu-xuat — lệnh chỉ đếm; thêm --thuc-hien để đẩy thật, --ma-lk=<mã> để chỉ định từng hồ sơ. Chỉ chạy sau khi hàng đợi kiểm đã cạn (select count(*) from jobs where queue=\'JobXml3176\' trả về 0); lệnh an toàn khi chạy lại — mỗi lần cấp mã phiên mới, chuỗi cũ tự thôi — nên chạy lại nếu lúc trước còn hồ sơ đang kiểm dở. Lệnh từ chối chạy khi xml3176.export_xml3176_enabled tắt; hồ sơ nạp qua đường không cho phép xuất (cho_phep_xuat=false) không phân biệt được và sẽ bị chọn, ở cơ sở đó hãy dùng --ma-lk.'),
  ];
}

// ---------------------------------------------------------------- tài liệu
const le = (inch) => convertInchesToTwip(inch);
const header = new Header({
  children: [new Paragraph({
    alignment: AlignmentType.RIGHT,
    border: { bottom: { style: BorderStyle.SINGLE, size: 4, color: 'B4C6E7' } },
    children: [new TextRun({ text: 'Quy trình vận hành Tiền giám định hồ sơ XML 3176', font: FONT, size: 18, color: '808080' })],
  })],
});
const footer = new Footer({
  children: [new Paragraph({
    alignment: AlignmentType.CENTER,
    children: [
      new TextRun({ text: 'Phiên bản ' + PHIEN_BAN + '   ·   Trang ', font: FONT, size: 20, color: '808080' }),
      new TextRun({ children: [PageNumber.CURRENT], font: FONT, size: 20, color: '808080' }),
      new TextRun({ text: ' / ', font: FONT, size: 20, color: '808080' }),
      new TextRun({ children: [PageNumber.TOTAL_PAGES], font: FONT, size: 20, color: '808080' }),
    ],
  })],
});
const doc_ = (children, landscape = false) => ({
  properties: {
    page: {
      size: landscape ? { orientation: PageOrientation.LANDSCAPE } : undefined,
      margin: { top: le(0.9), bottom: le(0.9), left: le(1), right: le(1) },
    },
  },
  headers: { default: header },
  footers: { default: footer },
  children,
});

const doc = new Document({
  creator: 'Phòng Công nghệ thông tin',
  title: 'Quy trình vận hành Tiền giám định hồ sơ BHYT theo XML 3176',
  description: 'Gợi ý quy trình vận hành cho các khoa, phòng',
  styles: { default: { document: { run: { font: FONT, size: 26 }, paragraph: { spacing: { line: 300 } } } } },
  numbering: {
    config: [
      { reference: 'bullets', levels: [
        { level: 0, format: LevelFormat.BULLET, text: '•', alignment: AlignmentType.LEFT, style: { paragraph: { indent: { left: 520, hanging: 260 } } } },
        { level: 1, format: LevelFormat.BULLET, text: '◦', alignment: AlignmentType.LEFT, style: { paragraph: { indent: { left: 980, hanging: 260 } } } },
      ] },
      { reference: 'steps', levels: [
        { level: 0, format: LevelFormat.DECIMAL, text: '%1.', alignment: AlignmentType.LEFT, style: { paragraph: { indent: { left: 560, hanging: 300 } } } },
      ] },
    ],
  },
  sections: [
    { properties: { page: { margin: { top: le(1), bottom: le(1), left: le(1), right: le(1) } } }, children: bia() },
    doc_(phanDau()),
    doc_(soDo(), true),
    doc_(phanSau()),
  ],
});

// Thư viện docx luôn ghi viền đoạn văn theo thứ tự top, bottom, left, right; lược đồ OOXML
// (w:pBdr) đòi top, left, bottom, right, between, bar. Word vẫn mở được, nhưng sắp lại cho
// tệp đạt kiểm tra lược đồ. jszip là gói phụ thuộc của docx.
const JSZip = require(require.resolve('jszip', { paths: [path.dirname(require.resolve('docx'))] }));
const THU_TU_VIEN = ['top', 'left', 'bottom', 'right', 'between', 'bar'];

function sapVien(xml) {
  return xml.replace(/<w:pBdr>([\s\S]*?)<\/w:pBdr>/g, (all, inner) => {
    const phan = inner.match(/<w:(\w+)\b[^>]*\/>/g) || [];
    phan.sort((a, c) => THU_TU_VIEN.indexOf(a.match(/<w:(\w+)/)[1]) - THU_TU_VIEN.indexOf(c.match(/<w:(\w+)/)[1]));
    return '<w:pBdr>' + phan.join('') + '</w:pBdr>';
  });
}

const out = process.argv[2] || path.join(__dirname, 'out.docx');
Packer.toBuffer(doc)
  .then((buf) => JSZip.loadAsync(buf))
  .then(async (zip) => {
    for (const ten of Object.keys(zip.files).filter((n) => /^word\/(document|header\d*|footer\d*)\.xml$/.test(n))) {
      zip.file(ten, sapVien(await zip.file(ten).async('string')));
    }
    return zip.generateAsync({ type: 'nodebuffer', compression: 'DEFLATE' });
  })
  .then((buf) => {
    fs.mkdirSync(path.dirname(out), { recursive: true });
    fs.writeFileSync(out, buf);
    console.log('OK ->', out, (buf.length / 1024).toFixed(1) + ' KB');
  });
