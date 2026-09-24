// DECK GỘP — Giải pháp Tiền giám định: tài liệu đào tạo người dùng.
// Gộp ba deck cũ (A khoa lâm sàng, B phòng ban, C tra cứu tiền cùng chi trả) thành một, cho
// hai khối người dùng: các khoa lâm sàng và các phòng ban chức năng;
// theo khung và giao diện của "Bài Trình Bày Nội Dung.pdf" (báo cáo Hội đồng KHCN, bản 1.7).
//
// Phần mở đầu (vị trí Tiền giám định, quy trình 5 bước, 5 bước × 3 luồng, 7 nhóm chức năng)
// viết mới trong tệp này. Nội dung từng chương nằm ở phan-*.js.

const L = require('./lib');
const K = require('./phan-khoa-lam-sang');
const P = require('./phan-phong-ban');
const T = require('./phan-mcct');

const { C, W, H, M, BODY_W, BODY_TOP, FONT } = L;

// ------------------------------------------------------------------ slide riêng của deck

function arrowLine(s, x, y, w, h, opts = {}) {
  s.addShape('line', {
    x, y, w, h, flipH: !!opts.flipH, flipV: !!opts.flipV,
    line: { color: opts.color || C.warn, width: 1.5, endArrowType: 'triangle' },
  });
}

// Vị trí của Tiền giám định trong quy trình: 5 ô, ô 03 là điểm kiểm soát chính.
function viTriSlide(pptx, ctx, speaker) {
  const s = pptx.addSlide();
  L.header(s, 'Tiền giám định đặt điểm kiểm soát trước khi hồ sơ được gửi', 'Vị trí trong quy trình', ctx);
  s.addText('Một điểm kiểm soát mới, nằm giữa lập hồ sơ và ký số — cộng một điểm kiểm soát sớm ngay khi bác sĩ ra y lệnh.', {
    x: M, y: 1.18, w: BODY_W, h: 0.3, fontFace: FONT, fontSize: 13, color: C.muted,
  });

  const nodes = [
    ['01', 'Y lệnh', 'Bác sĩ chỉ định, điều dưỡng thực hiện trên HIS'],
    ['02', 'Hồ sơ XML 3176', 'Kết xuất từ HIS, nạp vào phần mềm'],
    ['03', 'Kiểm lỗi tại Bệnh viện', 'Bộ quy tắc kiểm từng XML, tra thẻ, đối chiếu danh mục'],
    ['04', 'Ký số', 'HSM hoặc USB Token'],
    ['05', 'Gửi Cổng tiếp nhận', 'Cổng giám định BHXH'],
  ];
  const arrow = 0.34;
  const bw = (BODY_W - arrow * (nodes.length - 1)) / nodes.length;
  const y = 2.2;
  const bh = 1.3;
  nodes.forEach(([no, head, body], i) => {
    const x = M + i * (bw + arrow);
    const hot = i === 2;
    s.addShape('roundRect', {
      x, y, w: bw, h: bh, rectRadius: 0.07,
      fill: { color: hot ? C.primary : C.soft }, line: { color: hot ? C.primary : C.line },
    });
    s.addText([
      { text: no, options: { fontSize: 10, bold: true, color: hot ? 'BFE0F7' : C.primary, breakLine: true } },
      { text: head, options: { fontSize: 15, bold: true, color: hot ? C.white : C.title, breakLine: true } },
      { text: body, options: { fontSize: 10, color: hot ? 'DCEBF8' : C.muted } },
    ], { x: x + 0.14, y: y + 0.06, w: bw - 0.26, h: bh - 0.12, fontFace: FONT, valign: 'top', lineSpacing: 16 });
    if (i < nodes.length - 1) {
      s.addText('→', {
        x: x + bw, y: y + bh / 2 - 0.2, w: arrow, h: 0.4,
        fontFace: FONT, fontSize: 18, bold: true, color: C.accent, align: 'center', valign: 'middle',
      });
    }
  });

  // Nhãn "điểm kiểm soát" trên ô 03 và ô 01
  const x3 = M + 2 * (bw + arrow);
  s.addShape('roundRect', { x: x3 - 0.1, y: 1.72, w: bw + 0.2, h: 0.36, rectRadius: 0.18, fill: { color: C.accent }, line: { color: C.accent } });
  s.addText('ĐIỂM KIỂM SOÁT MỚI', {
    x: x3 - 0.1, y: 1.72, w: bw + 0.2, h: 0.36, fontFace: FONT, fontSize: 10, bold: true, color: C.white, align: 'center', valign: 'middle',
  });
  s.addShape('roundRect', { x: M, y: 1.72, w: bw, h: 0.36, rectRadius: 0.18, fill: { color: C.softAccent }, line: { color: C.accent } });
  s.addText('KIỂM SOÁT SỚM', {
    x: M, y: 1.72, w: bw, h: 0.36, fontFace: FONT, fontSize: 10, bold: true, color: C.primary, align: 'center', valign: 'middle',
  });

  // Vòng sửa lỗi: ô 03 → "sửa tại đơn vị phát sinh" → quay lại ô 01/02
  const loopY = 3.95;
  const loopX = M + bw * 0.8;
  const loopW = x3 + bw / 2 + 1.6 - loopX;
  arrowLine(s, x3 + bw / 2, y + bh, 0, loopY - (y + bh));
  s.addShape('roundRect', { x: loopX, y: loopY, w: loopW, h: 0.5, rectRadius: 0.06, fill: { color: C.softWarn }, line: { color: 'F1C58E' } });
  s.addText('Sửa tại đơn vị phát sinh trên HIS  →  nạp lại  →  quay lại kiểm tra', {
    x: loopX, y: loopY, w: loopW, h: 0.5, fontFace: FONT, fontSize: 11.5, bold: true, color: C.warn, align: 'center', valign: 'middle',
  });
  arrowLine(s, M + bw / 2, y + bh, 0, loopY + 0.25 - (y + bh), { flipV: true });
  s.addShape('line', { x: M + bw / 2, y: loopY + 0.25, w: loopX - (M + bw / 2), h: 0, line: { color: C.warn, width: 1.5 } });

  // Hai mức phản hồi + kiểm soát sớm
  const cy = 4.72;
  const ch = 1.02;
  const cw = (BODY_W - 0.3) / 3;
  [
    ['Lỗi nghiêm trọng', 'Chặn xuất và chặn gửi hồ sơ cho tới khi được sửa.', C.danger],
    ['Lỗi cảnh báo', 'Cho hồ sơ đi, kèm ghi nhận để theo dõi.', C.warn],
    ['Kiểm soát sớm', 'Sai sót y lệnh được bắt khoảng 60 giây sau khi phát sinh — trước khi hồ sơ được lập.', C.primary],
  ].forEach(([head, body, tone], i) => {
    const x = M + i * (cw + 0.15);
    s.addShape('rect', { x, y: cy, w: cw, h: ch, fill: { color: C.soft }, line: { color: C.line } });
    s.addShape('rect', { x, y: cy, w: 0.07, h: ch, fill: { color: tone }, line: { color: tone } });
    s.addText([
      { text: head, options: { fontSize: 14, bold: true, color: tone, breakLine: true } },
      { text: body, options: { fontSize: 11.5, color: C.ink } },
    ], { x: x + 0.22, y: cy + 0.05, w: cw - 0.34, h: ch - 0.1, fontFace: FONT, valign: 'middle', lineSpacing: 17 });
  });

  s.addShape('roundRect', { x: M, y: 5.98, w: BODY_W, h: 0.5, rectRadius: 0.06, fill: { color: C.navy }, line: { color: C.navy } });
  s.addText('Tiền giám định không thay thế quyết định chuyên môn; đây là lớp kiểm soát dữ liệu trước khi gửi.', {
    x: M, y: 5.98, w: BODY_W, h: 0.5, fontFace: FONT, fontSize: 13.5, bold: true, color: C.white, align: 'center', valign: 'middle',
  });
  L.footer(s, ctx.deck, ctx.page);
  L.notes(s, speaker);
  ctx.page += 1;
  return s;
}

// Quy trình 5 bước: mỗi bước một cột — ai làm, chạy thế nào, xem ở đâu, xong khi nào.
function quyTrinhSlide(pptx, ctx, { steps, strip, speaker }) {
  const s = pptx.addSlide();
  L.header(s, 'Năm bước hồ sơ đi qua trước khi tới cổng BHXH', 'Quy trình vận hành', ctx);
  s.addText('Nạp hồ sơ → Kiểm tra dữ liệu → Ký số → Gửi cổng → Theo dõi kết quả. Ví dụ theo luồng XML 3176.', {
    x: M, y: 1.18, w: BODY_W, h: 0.3, fontFace: FONT, fontSize: 13, color: C.muted,
  });
  const gap = 0.12;
  const cw = (BODY_W - gap * (steps.length - 1)) / steps.length;
  const topY = 1.65;
  steps.forEach((st, i) => {
    const x = M + i * (cw + gap);
    s.addShape(i === 0 ? 'homePlate' : 'chevron', {
      x, y: topY, w: cw + (i < steps.length - 1 ? 0.1 : 0), h: 0.72,
      fill: { color: i === 1 ? C.primary : C.navy }, line: { color: C.white, width: 1 },
    });
    s.addText([
      { text: `0${i + 1}  `, options: { fontSize: 11, bold: true, color: '8FC8EC' } },
      { text: st.head, options: { fontSize: 13, bold: true, color: C.white } },
    ], { x: x + (i === 0 ? 0.14 : 0.3), y: topY, w: cw - 0.4, h: 0.72, fontFace: FONT, valign: 'middle' });

    const cy = topY + 0.86;
    const chh = 3.52;
    s.addShape('rect', { x, y: cy, w: cw, h: chh, fill: { color: i === 1 ? C.softAccent : C.soft }, line: { color: C.line } });
    const runs = [];
    [['AI LÀM', st.who], ['CHẠY THẾ NÀO', st.how], ['XEM Ở ĐÂU', st.where], ['XONG KHI', st.done]].forEach(([lab, txt], k) => {
      runs.push({ text: lab, options: { fontSize: 8.5, bold: true, color: C.primary, breakLine: true, paraSpaceBefore: k ? 6 : 0 } });
      runs.push({ text: txt, options: { fontSize: 10, color: C.ink, breakLine: true } });
    });
    s.addText(runs, { x: x + 0.12, y: cy + 0.08, w: cw - 0.22, h: chh - 0.14, fontFace: FONT, valign: 'top', lineSpacing: 14 });
  });

  s.addShape('roundRect', { x: M, y: 6.14, w: BODY_W, h: 0.52, rectRadius: 0.06, fill: { color: C.softWarn }, line: { color: 'F1C58E' } });
  s.addText(strip, {
    x: M + 0.2, y: 6.14, w: BODY_W - 0.4, h: 0.52, fontFace: FONT, fontSize: 12, bold: true, color: C.warn, align: 'center', valign: 'middle',
  });
  L.footer(s, ctx.deck, ctx.page);
  L.notes(s, speaker);
  ctx.page += 1;
  return s;
}

// Truy cập Cổng Tiền giám định bằng tài khoản đào tạo. Khung đăng nhập vẽ lại theo đúng nhãn
// của trang thật (resources/lang/vendor/adminlte/vi/adminlte.php): trang https của Cổng không
// gửi được ảnh chụp về máy nội bộ nên không nhúng ảnh.
function truyCapSlide(pptx, ctx, { url, taiKhoan, matKhau, speaker }) {
  const s = pptx.addSlide();
  L.header(s, 'Truy cập Cổng Tiền giám định', 'Chuẩn bị thực hành', ctx);
  s.addText('Mỗi học viên đăng nhập trước khi vào phần thực hành. Dùng tài khoản đào tạo dưới đây.', {
    x: M, y: 1.18, w: BODY_W, h: 0.3, fontFace: FONT, fontSize: 13, color: C.muted,
  });

  // --- Khung đăng nhập (bên trái)
  const fx = M;
  const fy = 1.7;
  const fw = 5.3;
  const fh = 3.35;
  s.addShape('roundRect', { x: fx, y: fy, w: fw, h: fh, rectRadius: 0.06, fill: { color: 'ECF0F5' }, line: { color: C.line } });
  s.addText('GĐBHYT', {
    x: fx, y: fy + 0.18, w: fw, h: 0.45, fontFace: FONT, fontSize: 20, bold: true, color: C.title, align: 'center',
  });
  s.addShape('rect', { x: fx + 0.45, y: fy + 0.72, w: fw - 0.9, h: fh - 0.95, fill: { color: C.white }, line: { color: 'D2D6DE' } });
  s.addText('Đăng nhập để bắt đầu phiên làm việc', {
    x: fx + 0.45, y: fy + 0.8, w: fw - 0.9, h: 0.35, fontFace: FONT, fontSize: 11, color: C.muted, align: 'center',
  });
  const oNhap = (y, nhan, giaTri) => {
    s.addShape('rect', { x: fx + 0.7, y, w: fw - 1.4, h: 0.44, fill: { color: C.white }, line: { color: 'C9CFD8' } });
    s.addText([
      { text: nhan + ':  ', options: { color: C.muted, fontSize: 11 } },
      { text: giaTri, options: { color: C.ink, fontSize: 14, bold: true, fontFace: 'Consolas' } },
    ], { x: fx + 0.82, y, w: fw - 1.6, h: 0.44, fontFace: FONT, valign: 'middle' });
  };
  oNhap(fy + 1.25, 'Tên đăng nhập', taiKhoan);
  oNhap(fy + 1.82, 'Mật khẩu', matKhau);
  s.addShape('rect', { x: fx + 0.72, y: fy + 2.48, w: 0.17, h: 0.17, fill: { color: C.white }, line: { color: C.muted } });
  s.addText('Ghi nhớ thông tin', { x: fx + 0.95, y: fy + 2.4, w: 2.2, h: 0.32, fontFace: FONT, fontSize: 11, color: C.ink });
  s.addShape('rect', { x: fx + fw - 2.05, y: fy + 2.38, w: 1.35, h: 0.4, fill: { color: '3C8DBC' }, line: { color: '367FA9' } });
  s.addText('Đăng nhập', {
    x: fx + fw - 2.05, y: fy + 2.38, w: 1.35, h: 0.4, fontFace: FONT, fontSize: 12, bold: true, color: C.white, align: 'center', valign: 'middle',
  });
  s.addText('Hình vẽ lại trang đăng nhập — trên màn hình thật, mật khẩu hiện dạng ••••••••', {
    x: fx, y: fy + fh + 0.05, w: fw, h: 0.28, fontFace: FONT, fontSize: 9.5, italic: true, color: C.muted, align: 'center',
  });

  // --- Địa chỉ + tài khoản + các bước (bên phải)
  const rx = M + fw + 0.4;
  const rw = BODY_W - fw - 0.4;
  s.addShape('roundRect', { x: rx, y: fy, w: rw, h: 0.95, rectRadius: 0.06, fill: { color: C.navy }, line: { color: C.navy } });
  s.addText([
    { text: 'ĐỊA CHỈ CỔNG', options: { fontSize: 9.5, bold: true, color: '8FC8EC', breakLine: true } },
    { text: url, options: { fontSize: 19, bold: true, color: C.white } },
  ], { x: rx + 0.25, y: fy, w: rw - 0.4, h: 0.95, fontFace: FONT, valign: 'middle' });

  const cw = (rw - 0.2) / 2;
  [['TÊN ĐĂNG NHẬP', taiKhoan], ['MẬT KHẨU', matKhau]].forEach(([nhan, v], i) => {
    const x = rx + i * (cw + 0.2);
    s.addShape('roundRect', { x, y: fy + 1.1, w: cw, h: 0.8, rectRadius: 0.06, fill: { color: C.softAccent }, line: { color: C.accent } });
    s.addText([
      { text: nhan, options: { fontSize: 9.5, bold: true, color: C.primary, breakLine: true } },
      { text: v, options: { fontSize: 20, bold: true, color: C.title, fontFace: 'Consolas' } },
    ], { x: x + 0.2, y: fy + 1.1, w: cw - 0.3, h: 0.8, fontFace: FONT, valign: 'middle' });
  });

  const buoc = [
    ['Mở Chrome hoặc Edge, gõ địa chỉ Cổng vào thanh địa chỉ', 'Trang đăng nhập GĐBHYT hiện ra'],
    [`Nhập Tên đăng nhập: ${taiKhoan}`, 'Không phân biệt chữ hoa, chữ thường'],
    [`Nhập Mật khẩu: ${matKhau}, bấm Đăng nhập`, 'Không tích "Ghi nhớ thông tin" trên máy dùng chung'],
    ['Vào được: menu chức năng hiện ở cột bên trái', 'Chỉ thấy những menu tài khoản được cấp quyền'],
  ];
  const by = fy + 2.02;
  const bh = 0.4;
  buoc.forEach(([viec, kq], i) => {
    const y = by + i * (bh + 0.04);
    s.addShape('ellipse', { x: rx, y: y + 0.05, w: 0.34, h: 0.34, fill: { color: C.primary }, line: { color: C.primary } });
    s.addText(String(i + 1), {
      x: rx, y: y + 0.05, w: 0.34, h: 0.34, fontFace: FONT, fontSize: 11, bold: true, color: C.white, align: 'center', valign: 'middle',
    });
    s.addText([
      { text: viec, options: { fontSize: 12.5, color: C.ink, bold: true, breakLine: true } },
      { text: kq, options: { fontSize: 10.5, color: C.muted } },
    ], { x: rx + 0.45, y, w: rw - 0.45, h: bh, fontFace: FONT, valign: 'middle', lineSpacing: 15 });
  });

  L.calloutAt(s, {
    label: 'Tài khoản đào tạo dùng chung:',
    text: 'không đổi mật khẩu (sẽ khoá cả lớp); không bấm Ký và gửi, Xoá hay nhập danh mục trong lúc thực hành; '
        + 'dữ liệu trên Cổng là dữ liệu người bệnh thật — không chụp, không chia sẻ ra ngoài; đăng xuất khi kết thúc buổi học. '
        + 'Không vào được trang thì báo Phòng Công nghệ thông tin.',
    kind: 'danger',
  }, H - 1.95);
  L.footer(s, ctx.deck, ctx.page);
  L.notes(s, speaker);
  ctx.page += 1;
  return s;
}

// ------------------------------------------------------------------ deck

module.exports = function deckTgd(PptxGenJS) {
  const { pptx, ctx } = L.newDeck(PptxGenJS, {
    title: 'Giải pháp Tiền giám định — Tài liệu đào tạo người dùng',
    subject: 'Hướng dẫn sử dụng phần mềm qlbv cho các khoa lâm sàng và các phòng ban chức năng',
    deck: 'Giải pháp Tiền giám định · Đào tạo người dùng',
    theme: 'tgd',
  });

  L.titleSlide(pptx, {
    kicker: 'Tài liệu đào tạo người dùng',
    title: 'Giải pháp Tiền giám định\nhồ sơ BHYT',
    subtitle: 'Đưa phát hiện lỗi về trước khi hồ sơ BHYT rời bệnh viện —\nhướng dẫn sử dụng cho khoa lâm sàng và phòng ban chức năng',
    cells: [
      { label: 'Đối tượng', value: 'Các khoa lâm sàng\nCác phòng ban chức năng' },
      { label: 'Cách học', value: 'Trọn bộ, hoặc theo\nphần của khối mình' },
      { label: 'Tài liệu gốc', value: 'HDSD XML3176 – OrderCheck\nHDSD tra cứu MCCT' },
    ],
    message: 'Phần lớn lỗi có thể phát hiện ngay từ thời điểm bác sĩ ra y lệnh — trước khi hồ sơ rời bệnh viện hàng tuần lễ.',
    meta: 'Bệnh viện Bạch Mai  |  Phần mềm Tiền giám định BHYT  |  Tài liệu đào tạo tháng 9/2026',
  });

  // ------------------------------------------------------------ 01 — Tổng quan
  ctx.chap = '01';

  // Thời lượng một buổi 120 phút: ~30 phút lý thuyết, còn lại thực hành trên Cổng.
  // [phần, nội dung, khoa, phòng ban, lý thuyết, thực hành]. Lý thuyết mỗi phần chỉ nêu ý
  // chính; hai trọng tâm (02, 03) nhiều thực hành nhất; 01 thực hành = đăng nhập Cổng;
  // 10 dành cho giải tình huống và hỏi đáp. Tổng phải bằng 120 — xem kiểm tra dưới.
  const LO_TRINH = [
    ['01', 'Tổng quan: vị trí, quy trình 5 bước, 7 nhóm chức năng, nguyên tắc và giới hạn', '●', '●', 6, 5],
    ['02', 'Kiểm tra sai sót y lệnh', '●', '○', 4, 14],
    ['03', 'Hồ sơ XML 3176: nạp, kiểm tra, ký số, gửi, theo dõi', '○', '●', 4, 16],
    ['04', 'Thẻ BHYT: đọc mã kết quả, tra hàng loạt', '●', '●', 2, 8],
    ['05', 'Tra cứu tiền cùng chi trả (MCCT) — Phòng TCKT, Phòng BHYT', '●', '●', 2, 6],
    ['06', 'Tra cứu lỗi hồ sơ theo mã điều trị', '●', '●', 2, 10],
    ['07', 'Quản lý danh mục BHYT', '', '●', 2, 6],
    ['08', 'Chứng từ điện tử theo Phụ lục 02', '', '●', 3, 7],
    ['09', 'Danh mục theo Thông tư 12/2026', '', '●', 2, 6],
    ['10', 'Phân công, tình huống thường gặp · Hỏi đáp', '●', '●', 3, 12],
  ];
  const tongLT = LO_TRINH.reduce((t, r) => t + r[4], 0);
  const tongTH = LO_TRINH.reduce((t, r) => t + r[5], 0);
  if (tongLT + tongTH !== 120) throw new Error(`Lộ trình phải đủ 120 phút, đang là ${tongLT + tongTH}`);
  const phutKhoi = (cot, dau) => LO_TRINH.filter((r) => r[cot] === dau).reduce((t, r) => t + r[4] + r[5], 0);

  L.tableSlide(pptx, ctx, {
    kicker: 'Lộ trình',
    title: 'Ai học phần nào, trong bao lâu',
    intro: `Một buổi 120 phút: ${tongLT} phút lý thuyết, ${tongTH} phút thực hành trên phần mềm. ● bắt buộc với khối đó · ○ nên biết để phối hợp.`,
    head: ['Phần', 'Nội dung', 'Khoa lâm sàng', 'Phòng ban', 'Lý thuyết', 'Thực hành', 'Tổng'],
    colW: [0.6, 4.9, 1.3, 1.2, 1.05, 1.1, 0.85],
    rows: [
      ...LO_TRINH.map(([no, nd, khoa, pb, lt, th]) => [
        { t: no, align: 'center' },
        nd,
        ...[khoa, pb].map((c) => ({ t: c, b: c === '●', color: c === '●' ? C.primary : C.muted, align: 'center' })),
        { t: lt ? `${lt}'` : '—', align: 'center' },
        { t: th ? `${th}'` : '—', align: 'center' },
        { t: `${lt + th}'`, b: true, align: 'center' },
      ]),
      [
        { t: '', fill: C.softAccent },
        { t: 'TỔNG', b: true, fill: C.softAccent },
        { t: '', fill: C.softAccent },
        { t: '', fill: C.softAccent },
        { t: `${tongLT}'`, b: true, align: 'center', fill: C.softAccent },
        { t: `${tongTH}'`, b: true, align: 'center', fill: C.softAccent },
        { t: `${tongLT + tongTH}'`, b: true, color: C.primary, align: 'center', fill: C.softAccent },
      ],
    ],
    fontSize: 12.5,
    speaker: `Buổi chung 120 phút đi hết 10 phần theo bảng. Nếu tách buổi theo khối: các khoa lâm sàng học phần ● mất khoảng ${phutKhoi(2, '●')} phút (thêm phần ○ thì khoảng ${phutKhoi(2, '●') + phutKhoi(2, '○')} phút); các phòng ban chức năng học phần ● mất khoảng ${phutKhoi(3, '●')} phút. Thực hành cần: mỗi học viên một máy, tài khoản đã cấp quyền, và vài mã điều trị mẫu có lỗi để quét. Phần 02 thực hành lọc vi phạm theo khoa và đánh dấu Đã xử lý / Bỏ qua; phần 03 lọc "Lỗi critical", mở chi tiết, đọc tab Lỗi XML; phần 06 quét mã vạch trên phiếu và in phiếu lỗi; phần 10 giải tình huống và hỏi đáp. Phần 01 thực hành là đăng nhập Cổng bằng tài khoản đào tạo (slide 3). Phần 05 chỉ cần cho Phòng TCKT (viện phí) và Phòng BHYT.`,
  });

  truyCapSlide(pptx, ctx, {
    url: 'https://tgdbhyt.bachmai.edu.vn',
    taiKhoan: 'tndaotao',
    matKhau: 'tndaotao',
    speaker: 'Cho cả lớp đăng nhập NGAY lúc này, trước khi giảng, để xử lý sớm máy nào không vào được — đừng để tới phần thực hành. Nhấn: tài khoản dùng chung, không ai đổi mật khẩu; Cổng chạy trên dữ liệu người bệnh thật nên không chụp màn hình, không chia sẻ; trong lúc thực hành chỉ xem và lọc, không bấm Ký và gửi, Xoá hay nhập danh mục. Tài khoản này chỉ thấy những menu đã được cấp quyền — học viên không thấy một menu nào đó là bình thường.',
  });

  viTriSlide(pptx, ctx, 'Slide lấy từ báo cáo Hội đồng KHCN (trang 2). Nhấn hai điểm kiểm soát: điểm sớm ở y lệnh (phần 02) và điểm chính trước khi ký số (phần 03). Vòng màu cam là điều quan trọng nhất với người dùng: lỗi được sửa ở nơi phát sinh, trên HIS, rồi nạp lại — phần mềm không sửa hộ.');

  quyTrinhSlide(pptx, ctx, {
    steps: [
      { head: 'Nạp hồ sơ',
        who: 'Phòng BHYT, hoặc hệ thống tự quét thư mục',
        how: 'Kéo thả tệp .xml (tối đa 100 MB), hoặc đặt tệp vào thư mục theo dõi — nhận trong vài giây',
        where: 'Hồ sơ XML → Xml 3176 → Nhập khẩu hồ sơ',
        done: 'Hồ sơ hiện ở Danh sách hồ sơ. Tệp hỏng nằm trong thư mục con "loi"' },
      { head: 'Kiểm tra dữ liệu',
        who: 'Hệ thống chạy nền; người dùng đọc lỗi, phối hợp khoa sửa',
        how: 'Kiểm từng XML, tra thẻ BHYT, đối chiếu chéo và đối chiếu danh mục',
        where: 'Danh sách hồ sơ (dòng đỏ), tab Lỗi XML, Tra cứu lỗi hồ sơ',
        done: 'Số hàng đợi ở góc dưới phải về 0 và hồ sơ hết lỗi Nghiêm trọng' },
      { head: 'Ký số',
        who: 'Hệ thống, bằng HSM hoặc USB Token',
        how: 'Tự động khi hết lỗi Nghiêm trọng và ngày ra viện không ở tương lai',
        where: 'Cột Ký XML',
        done: '"Đã ký số (HSM)" hoặc "Đã ký số (USB Token)"' },
      { head: 'Gửi cổng',
        who: 'Hệ thống — chức năng tự gửi phải được bật cho từng cơ sở (mặc định TẮT)',
        how: 'Chỉ gửi hồ sơ đã ký. Lỗi mạng thì tự thử lại tối đa 3 lần',
        where: 'Cột Sub (rê chuột xem thông điệp)',
        done: 'Có thời điểm gửi, không kèm thông điệp lỗi' },
      { head: 'Theo dõi kết quả',
        who: 'Phòng BHYT / Giám định',
        how: 'Lọc "Gửi có lỗi", đọc thông điệp cổng, xem phễu và Pareto mã lỗi',
        where: 'Cột Sub, Dashboard lỗi XML',
        done: 'Lỗi cổng trả về đã sửa trên HIS và nạp lại; phễu không còn bậc tụt' },
    ],
    strip: 'Sửa trên HIS xong phải NẠP LẠI (quay về bước 01) thì kết quả mới đổi. Nút "Xuất XML3176" chỉ tải ZIP về máy — không phải bước 04.',
    speaker: 'Bước 02 tô sáng vì đây là chỗ Tiền giám định tạo giá trị. Bước 03 và 04 hoàn toàn tự động — không có nút bấm cho người dùng ở luồng XML 3176. Ở luồng chứng từ điện tử và TT12 thì khác, xem slide kế tiếp.',
  });

  L.tableSlide(pptx, ctx, {
    kicker: 'Quy trình vận hành',
    title: 'Cùng năm bước, ba luồng chạy khác nhau',
    head: ['Bước', 'Hồ sơ XML 3176 (phần 03)', 'Chứng từ điện tử PL02 (phần 08)', 'Danh mục TT12/2026 (phần 09)'],
    colW: [1.5, 3.0, 3.0, 3.0],
    rows: [
      [{ t: '01 Nạp hồ sơ', b: true }, 'Kéo thả tệp .xml, hoặc tự quét thư mục', 'Kéo thả ở màn Nạp hồ sơ, hoặc tiến trình quét thư mục', 'Chỉ qua màn Nạp danh mục, bằng biểu mẫu Excel tải từ phần mềm'],
      [{ t: '02 Kiểm tra dữ liệu', b: true }, 'Tự động: bộ kiểm XML1–XML15 và kiểm chéo. Lỗi Nghiêm trọng chặn xuất', 'Tự động: mã CTDT001–008, mức Chặn / Cảnh báo. "Chưa kiểm" chưa phải là sạch', 'Tự động sau khi nạp. MA_CSKCB lệch một dòng là từ chối cả tệp'],
      [{ t: '03 Ký số', b: true }, 'Tự động khi hết lỗi Nghiêm trọng', 'Người bấm "Ký và gửi" — một hồ sơ, hoặc tối đa 50 hồ sơ mỗi lượt', 'Một lần bấm "Ký và gửi" chạy cả ký và gửi'],
      [{ t: '04 Gửi cổng', b: true }, 'Tự động nếu cơ sở bật (mặc định tắt). Tự thử lại 3 lần', 'Người bấm. Tự gửi là công tắc riêng; mỗi hồ sơ chỉ tự gửi đúng 1 lần', 'Cùng lượt với ký số. Mã 200 = cổng tiếp nhận'],
      [{ t: '05 Theo dõi kết quả', b: true }, 'Cột Sub, lọc "Gửi có lỗi", Dashboard lỗi XML', 'Chín trạng thái gửi, khối Lịch sử gửi, Dashboard chứng từ', 'Mã giao dịch, cột Đã đồng bộ, Dashboard độ phủ'],
    ],
    fontSize: 13.5,
    note: { label: 'Khác biệt phải nhớ:', text: 'Chứng từ điện tử PL02 không có mã giao dịch chống trùng — gửi hai lần là hai chứng từ trên cổng, không rút lại được. Vì thế luồng này để người bấm gửi, còn XML 3176 thì máy tự gửi.', kind: 'danger' },
    speaker: 'Slide đối chiếu này giúp phòng ban hiểu vì sao cùng là "gửi cổng" mà ba màn hình hành xử khác nhau. Không cần đi sâu, chi tiết nằm ở phần 03, 08, 09.',
  });

  L.tableSlide(pptx, ctx, {
    kicker: 'Cách vận hành',
    title: 'Bảy nhóm chức năng, hai trọng tâm tạo giá trị lớn nhất',
    intro: 'Phát hiện tại nguồn → phân mức lỗi → sửa trước khi ký số → gửi hồ sơ sạch hơn.',
    head: ['#', 'Nhóm chức năng', 'Học ở phần', 'Màn hình chính'],
    colW: [0.6, 4.2, 1.4, 4.4],
    rows: [
      [{ t: '01', b: true, color: C.primary }, { t: 'Kiểm tra sai sót y lệnh — TRỌNG TÂM', b: true, color: C.primary }, '02', 'Kiểm tra sai sót y lệnh → Danh sách vi phạm'],
      [{ t: '02', b: true, color: C.primary }, { t: 'Kiểm hồ sơ XML 3176 — TRỌNG TÂM', b: true, color: C.primary }, '03', 'Hồ sơ XML → Xml 3176 → Danh sách hồ sơ'],
      ['03', 'Đối chiếu thẻ, mức hưởng và quyền lợi', '04, 05', 'Thẻ BHYT → Tra cứu thẻ BHYT / Tra cứu tiền cùng chi trả'],
      ['04', 'Đối chiếu mã và tên thuốc, VTYT, DVKT', '02, 03', 'Quy tắc đối chiếu danh mục; mã lỗi XML2, XML3'],
      ['05', 'Đối chiếu danh mục và giá còn hiệu lực', '07, 09', 'Quản lý danh mục → Nhập khẩu danh mục; Danh mục TT12'],
      ['06', 'Phản hồi lỗi tới đơn vị phát sinh theo mã điều trị', '06', 'Tra cứu lỗi hồ sơ (quét mã vạch trên phiếu)'],
      ['07', 'Tổng hợp chỉ số và báo cáo', '03, 08, 09', 'Dashboard lỗi XML · Dashboard chứng từ · Dashboard danh mục'],
    ],
    fontSize: 12,
    note: { label: 'Hai mức lỗi xuyên suốt:', text: 'NGHIÊM TRỌNG = chặn xuất và gửi. CẢNH BÁO = cho đi, ghi nhận để theo dõi. Mã lỗi mới chưa có trong danh mục được mặc định coi là NGHIÊM TRỌNG.' },
    speaker: 'Lấy từ trang 3 báo cáo Hội đồng. Chứng từ điện tử (phần 08) không nằm trong 7 nhóm nhưng dùng chung hạ tầng ký số và gửi cổng nên học cùng.',
  });

  L.cardSlide(pptx, ctx, {
    kicker: 'Nguyên tắc',
    title: 'Ba nguyên tắc cần hiểu trước khi dùng',
    cards: [
      { head: 'Chỉ đọc HIS — sửa tại HIS', tone: 'danger',
        body: 'Phần mềm đọc dữ liệu HIS, không bao giờ ghi hay sửa HIS.\n\nMọi sai sót phải sửa trên HIS, tại đơn vị phát sinh. Bấm "Đã xử lý" không sửa được dữ liệu.\n\nSửa xong phải nạp lại hồ sơ thì kết quả kiểm mới đổi.' },
      { head: 'Hai mức lỗi', tone: 'warn',
        body: 'Nghiêm trọng: hồ sơ không được xuất, không được gửi.\n\nCảnh báo: hồ sơ vẫn đi, lỗi được ghi nhận.\n\nMức của từng mã lỗi do phòng ban đặt ở danh mục mã lỗi — một ô tích sai có thể chặn hàng nghìn hồ sơ.' },
      { head: 'Cổng nhận là nhận thật', tone: 'accent',
        body: 'Không có đường rút lại hồ sơ đã được cổng tiếp nhận.\n\nSửa sai sau đó phải theo quy trình nghiệp vụ với cơ quan BHXH.\n\nĐối chiếu kỹ trước khi bấm gửi — đặc biệt với chứng từ điện tử.' },
    ],
    speaker: 'Ba nguyên tắc này trả lời trước phần lớn thắc mắc trong các phần sau. Nếu thời gian ít, dừng ở đây rồi chuyển thẳng sang phần của khối mình.',
  });

  L.bulletSlide(pptx, ctx, {
    kicker: 'Giới hạn',
    title: 'Những gì phần mềm chưa làm được',
    bullets: [
      { t: 'Chưa đồng nhất với toàn bộ quy tắc giám định của BHXH', b: true,
        sub: 'Hồ sơ qua được Tiền giám định vẫn có thể bị cổng trả về — luôn đọc kết quả ở bước 05' },
      { t: 'Chỉ bắt được lỗi đã có trong bộ quy tắc', b: true,
        sub: 'Quy định mới ban hành thì luôn có độ trễ cập nhật. Đề xuất quy tắc mới: gửi đủ điều kiện sai, mức độ, trường hợp loại trừ và căn cứ pháp lý' },
      { t: 'Kết quả phụ thuộc danh mục', b: true,
        sub: 'Danh mục thiếu hoặc sai ngày hiệu lực sẽ sinh lỗi giả hàng loạt — xem Phần 07' },
      { t: 'Kiểm tra y lệnh có độ trễ và không quét lùi', b: true,
        sub: 'Phiếu đã quét không được đánh giá lại trừ khi chính phiếu đó bị sửa trên HIS — xem mục 2.2.2' },
    ],
    note: { label: 'Nguyên tắc cuối cùng:', text: 'Phần mềm là công cụ hỗ trợ, không thay thế quyết định chuyên môn. Trách nhiệm cuối cùng vẫn thuộc về đơn vị tạo lập và kiểm soát hồ sơ.', kind: 'danger' },
    speaker: 'Đặt ở cuối phần 01 để học viên có kỳ vọng đúng trước khi vào các phần thực hành. Lấy từ trang 6 báo cáo Hội đồng, chuyển sang góc nhìn người dùng. Nói thẳng giới hạn giúp người dùng không tin tuyệt đối vào màu xanh trên màn hình.',
  });

  // ------------------------------------------------------------ 02 – 09 — các chương
  K.yLenh(pptx, ctx, 2);
  P.xml3176(pptx, ctx, 3);

  L.sectionSlide(pptx, ctx, {
    no: 4,
    title: 'Thẻ BHYT',
    sub: 'Đọc hai mã kết quả · Lỗi khoa sửa được trên HIS · Tra cứu tự động và hàng loạt ở phòng ban',
  });
  K.theBhytKhoa(pptx, ctx);
  P.theBhytPhongBan(pptx, ctx);

  L.sectionSlide(pptx, ctx, {
    no: 5,
    title: 'Tra cứu tiền cùng chi trả (MCCT)',
    sub: 'Dành cho Phòng Tài chính kế toán (viện phí) và Phòng BHYT · Hai vế điều kiện miễn · Đọc kết luận · Không bấm nhiều lần',
  });
  T.mcct(pptx, ctx);
  K.traCuuLoi(pptx, ctx, 6);

  P.danhMuc(pptx, ctx, 7);
  P.ctdt(pptx, ctx, 8);
  P.tt12(pptx, ctx, 9);

  // ------------------------------------------------------------ 10 — Kết
  L.sectionSlide(pptx, ctx, {
    no: 10,
    title: 'Phân công và tình huống thường gặp',
    sub: 'Ai làm gì · Tình huống thường gặp tại khoa và phòng ban · Hỏi đáp',
  });

  L.tableSlide(pptx, ctx, {
    kicker: 'Ranh giới trách nhiệm',
    title: 'Ai làm gì',
    head: ['Khối', 'Tự làm', 'Báo công nghệ thông tin / cơ quan BHXH'],
    colW: [1.7, 4.6, 4.2],
    rows: [
      [{ t: 'Các khoa lâm sàng', b: true },
        'Sửa dữ liệu gốc trên HIS: y lệnh, ICD, giờ y lệnh, người thực hiện, chứng chỉ hành nghề; thông tin hành chính lệch với cổng (họ tên, ngày sinh, giới tính, nơi ĐKBĐ). Đánh dấu Đã xử lý / Bỏ qua kèm ghi chú. Quét mã tra lỗi, in phiếu lỗi',
        'Bộ quét dừng (cột "Chạy gần nhất" quá vài phút, cột "Lỗi" khác 0). Thiếu quyền, không thấy menu. "Không lấy được thông tin từ HIS"'],
      [{ t: 'Các phòng ban chức năng', b: true },
        'Nạp và theo dõi hồ sơ XML, giao việc theo khoa. Nhập danh mục BHYT, đặt mức lỗi. Ký và gửi chứng từ điện tử, danh mục TT12. Giữ mã giao dịch để đối soát. Tra MCCT: đọc mốc "tính đến …", kiểm riêng vế 5 năm liên tục',
        'Hàng đợi đứng, cả loạt "Chưa kiểm". Lỗi ký số (HSM, USB Token, chứng thư). Mã 401 / 403 từ cổng. Bật tắt tự gửi. Nạp mã lỗi mới sau nâng cấp. Tài khoản bị cổng hạn chế tra cứu → BHXH tỉnh. Lương cơ sở đổi → CNTT cập nhật'],
    ],
    fontSize: 13,
    note: { label: 'Khi báo lên:', text: 'Gửi kèm nguyên văn thông báo (chụp màn hình), mã điều trị hoặc mã hồ sơ, cơ sở KCB và thời điểm xảy ra. Cột Sub có nút sao chép nguyên văn thông điệp cổng.' },
    speaker: 'Gộp ba slide "ranh giới trách nhiệm" của ba deck cũ. Mục tiêu: không ai mất thời gian với việc không phải của mình, và không gọi hỗ trợ sai địa chỉ.',
  });

  K.tinhHuongKhoa(pptx, ctx);
  P.tinhHuongPhongBan(pptx, ctx);

  L.closingSlide(pptx, ctx, {
    title: 'Tóm lại — năm điều mang về',
    points: [
      'Tiền giám định là điểm kiểm soát trước khi ký số và gửi cổng — lỗi phải sửa tại đơn vị phát sinh, trên HIS.',
      'Năm bước: Nạp hồ sơ → Kiểm tra dữ liệu → Ký số → Gửi cổng → Theo dõi kết quả. Sửa xong phải nạp lại.',
      'Lỗi Nghiêm trọng chặn hồ sơ; "Bỏ qua" là vĩnh viễn và luôn phải ghi lý do.',
      'Cổng nhận là nhận thật. Chứng từ điện tử gửi hai lần là hai chứng từ.',
      'Đủ ngưỡng tiền cùng chi trả mới là một nửa điều kiện miễn; luôn đọc mốc "tính đến …".',
    ],
    contact: 'Tra cứu chi tiết: HDSD XML3176 – OrderCheck – Thẻ BHYT – Danh mục (Phần I – VII, Phụ lục A, B) · HDSD tra cứu MCCT\nHỗ trợ: Phòng Công nghệ thông tin — số máy lẻ: ………',
  });

  L.finishDeck(pptx);
  return pptx;
};
