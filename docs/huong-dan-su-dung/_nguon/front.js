const { AlignmentType, Paragraph, TextRun, TableOfContents } = require('docx');
const { h1, h2, h3, p, bullet, note, table, run, FONT, spacer, pageBreak } = require('./lib');
const { PHIEN_BAN, NGAY_PHAT_HANH, LICH_SU } = require('./version');

function cover() {
  const big = (t, size, bold, color, after) =>
    new Paragraph({
      alignment: AlignmentType.CENTER,
      spacing: { after },
      children: [new TextRun({ text: t, font: FONT, size, bold, color })],
    });
  return [
    new Paragraph({ spacing: { after: 1800 }, children: [] }),
    big('PHẦN MỀM QUẢN LÝ BỆNH VIỆN', 26, true, '444444', 200),
    big('TÀI LIỆU', 44, true, '1F3864', 60),
    big('HƯỚNG DẪN SỬ DỤNG', 44, true, '1F3864', 400),
    big('Hồ sơ XML 3176  •  Kiểm tra sai sót y lệnh', 28, false, '2E5496', 80),
    big('Thẻ BHYT  •  Quản lý danh mục', 28, false, '2E5496', 80),
    big('Tra cứu lỗi hồ sơ theo mã điều trị', 28, false, '2E5496', 80),
    big('Chứng từ điện tử theo Phụ lục 02', 28, false, '2E5496', 1400),
    big('Dành cho người dùng nghiệp vụ', 26, false, '555555', 100),
    // Số phiên bản đọc từ version.js chứ không viết cứng ở đây - xem ghi chú trong tệp đó.
    big('Phiên bản ' + PHIEN_BAN + ' — ' + NGAY_PHAT_HANH, 24, false, '777777', 0),
  ];
}

/**
 * Bảng lịch sử phát hành.
 *
 * Đặt SAU mục lục chứ không trước: là một đề mục cấp 1 nên nó tự vào mục lục, và người cần
 * biết "bản này khác bản tôi đã đọc ở chỗ nào" tìm được ngay từ trang mục lục.
 */
function history() {
  return [
    h1('LỊCH SỬ CẬP NHẬT TÀI LIỆU'),
    p('Tài liệu được cập nhật theo từng đợt bổ sung chức năng của phần mềm. Bảng dưới đây liệt kê các bản đã phát hành, mới nhất ở trên cùng.'),
    table(
      ['Phiên bản', 'Ngày', 'Nội dung thay đổi', 'Phần liên quan'],
      LICH_SU.map((m) => [m.ban, m.ngay, m.noi_dung, m.lien_quan]),
      [1100, 1200, 4720, 2000],
    ),
    p([
      run('Bản đang đọc: '),
      run('phiên bản ' + PHIEN_BAN + ' — ' + NGAY_PHAT_HANH + '.', { bold: true }),
    ]),
    note('Lưu ý:', 'Trước khi in hoặc gửi tài liệu cho đơn vị khác, hãy đối chiếu số phiên bản ở trang bìa với bản mới nhất do phòng Công nghệ thông tin phát hành. Chức năng của phần mềm thay đổi theo từng đợt, và một bản in cũ có thể mô tả màn hình không còn tồn tại.'),
  ];
}

function toc() {
  return [
    h1('MỤC LỤC', { pageBreak: false }),
    p([
      run('Mục lục dưới đây là trường tự động. '),
      run('Khi mở tệp bằng Microsoft Word, nhấn Ctrl+A rồi F9 và chọn "Update entire table" để hiển thị đầy đủ số trang.', { italics: true }),
    ]),
    new TableOfContents('Mục lục', { hyperlink: true, headingStyleRange: '1-3' }),
    pageBreak(),
  ];
}

function chapter0() {
  return [
    h1('CHƯƠNG 0. THÔNG TIN CHUNG'),

    h2('0.1. Mục đích và đối tượng sử dụng'),
    p('Tài liệu này hướng dẫn sử dụng bảy nhóm chức năng có liên quan chặt chẽ với nhau trong phần mềm quản lý bệnh viện:'),
    bullet('Hồ sơ XML 3176 — nhập khẩu, kiểm tra, ký số và gửi hồ sơ điện tử lên cổng giám định Bảo hiểm xã hội.'),
    bullet('Kiểm tra sai sót y lệnh (order-check) — rà soát tự động các chỉ định, đơn thuốc, dịch vụ kỹ thuật phát sinh trong quá trình khám chữa bệnh.'),
    bullet('Thẻ BHYT — tra cứu thông tin thẻ và lịch sử khám chữa bệnh trên cổng Bảo hiểm xã hội.'),
    bullet('Quản lý danh mục — cập nhật các bộ danh mục do Bảo hiểm xã hội phát hành, làm cơ sở đối chiếu cho hai nhóm chức năng trên.'),
    bullet('Tra cứu lỗi hồ sơ theo mã điều trị — gộp lỗi của cả ba nhóm trên vào một lần tra, phục vụ tra cứu nhanh tại khoa phòng.'),
    bullet('Chứng từ điện tử theo Phụ lục 02 — nạp, kiểm tra, ký số và gửi giấy chứng sinh, giấy báo tử, giấy chứng nhận nghỉ việc hưởng bảo hiểm xã hội và các chứng từ liên quan lên cổng Bảo hiểm xã hội.'),
    bullet('Danh mục theo Thông tư 12/2026 — khai báo sáu bộ danh mục năng lực của cơ sở bằng tệp Excel, ký số và gửi lên cổng Bảo hiểm xã hội, rồi đồng bộ sang bộ danh mục dùng để đối chiếu hồ sơ XML 3176.'),
    p('Đối tượng sử dụng chính là cán bộ phòng Kế hoạch tổng hợp, cán bộ thống kê và cán bộ phụ trách bảo hiểm y tế. Tài liệu mô tả những gì người dùng nhìn thấy và thao tác trên màn hình.'),
    p('Hai mục 1.10 và 2.9 được viết riêng cho bộ phận công nghệ thông tin, mô tả quy trình bổ sung quy tắc kiểm tra mới. Người dùng nghiệp vụ nên đọc lướt hai mục này để biết cách đặt yêu cầu và ước lượng công sức thực hiện.'),

    h2('0.2. Quy ước trình bày'),
    table(
      ['Ký hiệu', 'Ý nghĩa'],
      [
        ['Chữ in đậm', 'Tên menu, tên nút bấm, tên ô nhập liệu trên màn hình.'],
        ['Menu A → Menu B', 'Đường dẫn thao tác: mở menu A rồi chọn mục B.'],
        ['Khối nền vàng "Lưu ý"', 'Điểm dễ nhầm lẫn hoặc có thể gây hậu quả nghiêm trọng nếu làm sai.'],
        ['Khối nền xanh "Dành cho bộ phận CNTT"', 'Nội dung kỹ thuật, người dùng nghiệp vụ không cần thực hiện.'],
      ],
      [2400, 6620],
    ),

    h2('0.3. Bản đồ menu và quyền truy cập'),
    p('Các chức năng trong tài liệu này nằm rải ở năm nhóm menu. Nếu không nhìn thấy một menu nào đó, nguyên nhân gần như luôn là tài khoản chưa được cấp quyền tương ứng; liên hệ quản trị hệ thống để được bổ sung.'),
    table(
      ['Nhóm menu', 'Các mục con', 'Quyền cần có'],
      [
        ['Hồ sơ XML', 'Kết quả tra cứu thẻ; Xml 3176 (Danh sách hồ sơ, Nhập khẩu hồ sơ, Dashboard lỗi XML); Xml 4750', 'xml-man'],
        ['Hồ sơ XML', 'Chứng từ điện tử (Danh sách hồ sơ, Nạp hồ sơ, Dashboard chứng từ)', 'xml-man'],
        ['Hồ sơ XML', 'Nút Xóa hồ sơ trong màn chi tiết chứng từ điện tử', 'superadministrator'],
        ['Hồ sơ XML', 'Danh mục TT12 (Danh sách hồ sơ, Nạp danh mục, Dashboard danh mục)', 'xml-man'],
        ['Hồ sơ XML', 'Nút Xoá hồ sơ, Kiểm lại và Đồng bộ lại danh mục trong màn chi tiết danh mục TT12', 'superadministrator'],
        ['Kiểm tra sai sót y lệnh', 'Danh sách vi phạm', 'order-check'],
        ['Kiểm tra sai sót y lệnh', 'Danh mục giới hạn DV; Quản lý quy tắc kiểm tra', 'superadministrator'],
        ['Thẻ BHYT', 'Tra cứu thẻ BHYT; Tra cứu Thuốc – Thầu', 'Mọi tài khoản đã đăng nhập'],
        ['Quản lý danh mục', '11 bộ danh mục BHYT; DM lỗi Xml 3176; DM lỗi Xml 4750; Nhập khẩu danh mục; Tra cứu giá dịch vụ', 'category-manager'],
        ['Quản lý danh mục', 'DVKT có điều kiện; Thuốc có điều kiện; Danh mục Khoa phòng; Xoá toàn bộ một danh mục', 'superadministrator'],
        ['Tra cứu lỗi hồ sơ', 'Tra cứu lỗi hồ sơ theo mã điều trị (menu cấp ngoài cùng)', 'tra-cuu-loi-ho-so'],
      ],
      [2200, 5020, 1800],
    ),

    h2('0.4. Các khái niệm dùng chung'),
    table(
      ['Khái niệm', 'Giải thích'],
      [
        ['Mã điều trị (ma_lk)', 'Mã liên kết duy nhất của một đợt khám hoặc một đợt điều trị. Đây là khoá tra cứu chính xuyên suốt hồ sơ XML, kết quả tra thẻ và danh sách vi phạm y lệnh.'],
        ['MA_CSKCB / Cơ sở KCB', 'Mã cơ sở khám chữa bệnh do Bảo hiểm xã hội cấp. Một phần mềm có thể phục vụ nhiều cơ sở; hầu hết màn hình đều có ô lọc Cơ sở KCB, mặc định là "Tất cả cơ sở".'],
        ['Hàng đợi nền (queue)', 'Các việc nặng như kiểm lỗi hồ sơ, tra cứu thẻ, ký số, gửi cổng đều không chạy ngay khi bấm nút mà được xếp hàng và xử lý dần ở nền. Vì vậy kết quả có thể xuất hiện chậm vài giây đến vài phút.'],
        ['Lỗi nghiêm trọng (critical)', 'Lỗi chặn hồ sơ, hồ sơ còn lỗi nghiêm trọng thì hệ thống không xuất và không gửi lên cổng Bảo hiểm xã hội. Mức nghiêm trọng của từng mã lỗi được đặt trong màn Danh mục lỗi.'],
        ['Lỗi cảnh báo (warning)', 'Lỗi cần xem xét nhưng không chặn việc xuất và gửi hồ sơ.'],
      ],
      [2400, 6620],
    ),
    note('Lưu ý:', 'Thứ tự đọc được khuyến nghị cho người mới: Chương 0 → Phần IV (Quản lý danh mục) → Phần I (Hồ sơ XML 3176) → Phần II → Phần III → Phần V → Phần VI. Danh mục là nền tảng đối chiếu; danh mục sai hoặc thiếu sẽ làm hai phần còn lại báo lỗi hàng loạt. Phần V đọc sau cùng trong nhóm tra cứu vì nó chỉ hiển thị lại kết quả của ba phần trước. Phần VI đứng khá độc lập: người chỉ làm giấy chứng sinh, giấy báo tử có thể đọc thẳng Chương 0 rồi sang Phần VI. Phần VII nên đọc sau Phần IV và trước Phần I: danh mục do cơ sở khai ở Phần VII, sau khi được cổng tiếp nhận, chính là căn cứ đối chiếu khi kiểm hồ sơ XML 3176 ở Phần I.'),
  ];
}

module.exports = { cover, toc, history, chapter0 };
