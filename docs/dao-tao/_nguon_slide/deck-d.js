// DECK D — Báo cáo giao ban
// Nguồn: mã nguồn module giao ban (routes/web.php, GiaoBanController, GiaoBanConfigController,
// app/Services/GiaoBan/*, resources/views/khth/giaoban-*.blade.php, public/js/giaoban/*).
// Khi module đổi giao diện hoặc cách tính, sửa deck này theo.

const L = require('./lib');

const { C, FONT, M, BODY_W, BODY_TOP, H } = L;

// Slide "Ý nghĩa màu của ô số liệu": vẽ lại ô nhập đúng màu như trên màn hình.
function cellLegendSlide(pptx, ctx, { title, kicker, rows, note, speaker }) {
  const s = pptx.addSlide();
  L.header(s, title, kicker);
  const avail = (note ? H - 1.95 : H - 0.62) - BODY_TOP - 0.1;
  const gap = 0.14;
  const h = Math.min(0.95, (avail - gap * (rows.length - 1)) / rows.length);
  rows.forEach((r, i) => {
    const top = BODY_TOP + i * (h + gap);
    s.addShape('rect', { x: M, y: top, w: BODY_W, h, fill: { color: i % 2 ? C.white : C.soft }, line: { color: C.line } });
    // Ô nhập mô phỏng
    s.addShape('rect', {
      x: M + 0.25, y: top + (h - 0.5) / 2, w: 1.5, h: 0.5,
      fill: { color: r.fill }, line: { color: r.border, width: r.borderW || 1 },
    });
    if (r.undo) {
      s.addShape('rect', {
        x: M + 1.3, y: top + (h - 0.5) / 2, w: 0.45, h: 0.5,
        fill: { color: 'F4F4F4' }, line: { color: C.line, width: 1 },
      });
      s.addText('↺', {
        x: M + 1.3, y: top + (h - 0.5) / 2, w: 0.45, h: 0.5,
        fontFace: FONT, fontSize: 16, bold: true, color: C.ink, align: 'center', valign: 'middle', margin: 0,
      });
    }
    s.addText(r.value, {
      x: M + 0.25, y: top + (h - 0.5) / 2, w: r.undo ? 1.05 : 1.5, h: 0.5,
      fontFace: FONT, fontSize: 16, color: r.ink || C.ink, italic: !!r.italic,
      align: 'center', valign: 'middle', margin: 0,
    });
    s.addText(r.head, {
      x: M + 2.05, y: top, w: 3.4, h,
      fontFace: FONT, fontSize: 14, bold: true, color: C.primary, valign: 'middle', lineSpacing: 18,
    });
    s.addText(r.body, {
      x: M + 5.55, y: top, w: BODY_W - 5.75, h,
      fontFace: FONT, fontSize: 12.5, color: C.ink, valign: 'middle', lineSpacing: 17,
    });
  });
  if (note) L.calloutAt(s, note, H - 1.95);
  L.footer(s, ctx.deck, ctx.page);
  L.notes(s, speaker);
  ctx.page += 1;
  return s;
}

// Slide "Công thức cân đối": các ô cộng/trừ xếp thành phương trình.
function balanceSlide(pptx, ctx, { title, kicker, intro, terms, result, legend, note, speaker }) {
  const s = pptx.addSlide();
  L.header(s, title, kicker);
  let y = BODY_TOP;
  s.addText(intro, { x: M, y, w: BODY_W, h: 0.34, fontFace: FONT, fontSize: 13, italic: true, color: C.muted });
  y += 0.55;
  const n = terms.length + 1;
  const op = 0.32;
  const bw = (BODY_W - op * (n - 1)) / n;
  const bh = 1.2;
  terms.concat([{ label: result.label, code: result.code, sign: '=' }]).forEach((t, i) => {
    const x = M + i * (bw + op);
    const isResult = i === n - 1;
    const tone = isResult ? C.primary : t.sign === '−' ? C.softWarn : C.softOk;
    s.addShape('roundRect', {
      x, y, w: bw, h: bh, rectRadius: 0.08,
      fill: { color: tone }, line: { color: isResult ? C.primary : C.line },
    });
    s.addText(t.label, {
      x: x + 0.05, y: y + 0.1, w: bw - 0.1, h: 0.7,
      fontFace: FONT, fontSize: 12.5, bold: true, align: 'center', valign: 'middle',
      color: isResult ? C.white : C.primary, lineSpacing: 16,
    });
    s.addText(t.code, {
      x: x + 0.05, y: y + 0.8, w: bw - 0.1, h: 0.3,
      fontFace: 'Courier New', fontSize: 9.5, align: 'center', valign: 'middle',
      color: isResult ? 'DCE9F5' : C.muted,
    });
    if (i > 0) {
      s.addText(t.sign, {
        x: x - op, y: y + bh / 2 - 0.22, w: op, h: 0.44,
        fontFace: FONT, fontSize: 20, bold: true, align: 'center', valign: 'middle',
        color: t.sign === '−' ? C.warn : t.sign === '=' ? C.primary : C.ok, margin: 0,
      });
    }
  });
  s.addText(legend.map((l) => ({
    text: l,
    options: { bullet: { code: '25AA' }, fontSize: 13, color: C.ink, breakLine: true, paraSpaceAfter: 8 },
  })), {
    x: M, y: y + bh + 0.35, w: BODY_W, h: (note ? H - 1.95 : H - 0.62) - (y + bh + 0.4),
    fontFace: FONT, valign: 'top', lineSpacing: 19,
  });
  if (note) L.calloutAt(s, note, H - 1.95);
  L.footer(s, ctx.deck, ctx.page);
  L.notes(s, speaker);
  ctx.page += 1;
  return s;
}

const path = require('path');
const ANH = path.resolve(__dirname, '..', 'anh');

// Đặt ảnh vừa khung (giữ tỉ lệ), căn giữa trong khung, viền mảnh.
function fitImage(s, file, px, box) {
  const r = Math.min(box.w / px[0], box.h / px[1]);
  const w = px[0] * r;
  const h = px[1] * r;
  const x = box.x + (box.w - w) / 2;
  const y = box.y + (box.h - h) / 2;
  s.addImage({ path: path.join(ANH, file), x, y, w, h });
  s.addShape('rect', { x, y, w, h, fill: { type: 'none' }, line: { color: C.line, width: 0.75 } });
}

// Slide ảnh chụp màn hình thật: ảnh bên trái, các điểm cần chú ý bên phải.
// images: [{ file, px: [rộng, cao] }] — một hoặc hai ảnh xếp cạnh nhau.
function imageSlide(pptx, ctx, { title, kicker, images, points, pointsW, note, speaker }) {
  const s = pptx.addSlide();
  L.header(s, title, kicker);
  const pw = points && points.length ? (pointsW || 3.4) : 0;
  const areaW = BODY_W - (pw ? pw + 0.3 : 0);
  const areaH = (note ? H - 1.95 : H - 0.7) - BODY_TOP - 0.1;
  const gap = 0.25;
  const each = (areaW - gap * (images.length - 1)) / images.length;
  images.forEach((im, i) => {
    fitImage(s, im.file, im.px, { x: M + i * (each + gap), y: BODY_TOP, w: each, h: areaH });
  });
  if (pw) {
    s.addText(points.map((p) => ({
      text: p,
      options: { bullet: { code: '25AA' }, fontSize: 13, color: C.ink, breakLine: true, paraSpaceAfter: 9 },
    })), {
      x: M + areaW + 0.3, y: BODY_TOP, w: pw, h: areaH,
      fontFace: FONT, valign: 'top', lineSpacing: 19,
    });
  }
  if (note) L.calloutAt(s, note, H - 1.95);
  L.footer(s, ctx.deck, ctx.page);
  L.notes(s, speaker);
  ctx.page += 1;
  return s;
}

module.exports = function deckD(PptxGenJS) {
  const { pptx, ctx } = L.newDeck(PptxGenJS, {
    title: 'Đào tạo sử dụng module Báo cáo giao ban',
    subject: 'Hướng dẫn nhập số liệu, chốt, trình chiếu, xuất PPTX và cấu hình báo cáo giao ban',
    deck: 'Deck D — Báo cáo giao ban',
  });

  L.titleSlide(pptx, {
    title: 'Báo cáo giao ban\ntrên phần mềm qlbv',
    subtitle: 'Nhập số liệu khoa · Chốt báo cáo · Trình chiếu · Xuất PPTX',
    audience: 'Dành cho cán bộ nhập liệu các khoa và phòng Kế hoạch tổng hợp (KHTH)',
    meta: 'Thời lượng: 60–90 phút (khoa: phần 1–2 · KHTH: toàn bộ)\nMenu: Báo cáo giao ban → Báo cáo giao ban / Cấu hình giao ban',
  });

  L.bulletSlide(pptx, ctx, {
    kicker: 'Mục tiêu',
    title: 'Sau buổi này, anh/chị làm được gì',
    bullets: [
      { t: 'Mở đúng màn hình, chọn đúng ngày và hiểu khung giờ 07:00 → 07:00 của số liệu', b: true },
      { t: 'Khoa: lấy số liệu từ HIS, kiểm tra, sửa khi cần và điền đủ các ô bắt buộc', b: true,
        sub: 'Đọc được trạng thái từng ô: nút ↺ = đã sửa tay, xám nghiêng = kế thừa chưa xác nhận, viền đỏ = còn thiếu' },
      { t: 'Hiểu cảnh báo "Lệch cân đối" và tự tìm ra ô sai', b: true },
      { t: 'KHTH: rà soát, chốt báo cáo, trình chiếu tại buổi giao ban và xuất tệp PPTX / Excel', b: true },
      { t: 'Quản trị: cấp vai trò giao ban, gán khoa cho tài khoản, cấu hình tiêu chí, chức danh trực', b: true },
      { t: 'Tự xử lý các thông báo hay gặp trước khi gọi điện hỗ trợ', b: true },
    ],
    speaker: 'Nếu lớp chỉ có cán bộ khoa thì dạy phần 1, 2 và phần 6 (tình huống). Phần 3 dành cho phòng KHTH; phần 4 (phân quyền) và 5 (cấu hình) dành cho người quản trị.',
  });

  L.tableSlide(pptx, ctx, {
    kicker: 'Vai trò',
    title: 'Ai được làm gì',
    intro: 'Vai trò do quản trị hệ thống cấp (Phần 4). Dòng "Chế độ" ngay dưới thanh nút cho biết tài khoản đang ở vai trò nào.',
    head: ['Việc', 'Cán bộ khoa (quyền giaoban)', 'Quản trị KHTH (quyền giaoban-admin)'],
    colW: [3.2, 3.6, 3.6],
    rows: [
      [{ t: 'Khoa nhìn thấy', b: true }, 'Chỉ các khoa được gán cho mình', 'Toàn viện'],
      [{ t: 'Tạo số liệu từ HIS', b: true }, 'Một lần, khi ngày đó CHƯA có số liệu', 'Bất cứ lúc nào, bao nhiêu lần cũng được'],
      [{ t: 'Sửa số, Ghi chú khoa', b: true }, 'Các khoa được gán', 'Mọi khoa'],
      [{ t: 'Kíp trực lãnh đạo', b: true }, 'Chỉ khi có tên trong danh sách "Người được cập nhật kíp trực"', 'Có'],
      [{ t: 'Ghi chú chung', b: true }, 'Không', 'Có'],
      [{ t: 'Chốt / Mở khóa', b: true }, 'Không', 'Có'],
      [{ t: 'Trình chiếu, Xuất PPTX, Xuất Excel', b: true }, 'Không (nút bị ẩn)', 'Có'],
      [{ t: 'Cấu hình giao ban', b: true }, 'Không', 'Có'],
    ],
    note: { label: 'Hai nhóm quyền có sẵn:', text: '"Giao ban - Nhập liệu khoa" (giaoban_khoa) và "Giao ban - Quản trị" (giaoban_admin). Tài khoản administrator có sẵn cả hai quyền.', kind: 'ok' },
    speaker: 'Nhấn dòng 2: khoa chỉ được tạo số liệu một lần mỗi ngày. Lấy lại lần hai phải nhờ KHTH, để không ai vô tình ghi đè số liệu đã rà soát.',
  });

  L.flowSlide(pptx, ctx, {
    kicker: 'Toàn cảnh',
    title: 'Một ngày giao ban đi qua bốn bước',
    intro: 'Mỗi ngày có đúng MỘT báo cáo. Số liệu mặc định tính từ 07:00 hôm trước đến 07:00 ngày giao ban.',
    nodes: [
      { head: '1. Tạo số liệu', body: 'Khoa (hoặc KHTH) bấm "Tạo số liệu" — phần mềm đếm tự động từ HIS', tone: 'accent' },
      { head: '2. Khoa rà soát', body: 'Kiểm tra số tự động, sửa khi cần, điền ô bắt buộc, ghi chú khoa' },
      { head: '3. KHTH chốt', body: 'Xem ô trống, lệch cân đối, viết ghi chú chung, bấm "Chốt báo cáo"', tone: 'primary' },
      { head: '4. Giao ban', body: 'Trình chiếu trên màn hình lớn, xuất PPTX / Excel để lưu và gửi' },
    ],
    legend: [
      'Số tự động lấy trực tiếp từ HIS: người bệnh cũ, vào, chuyển đến, chuyển khoa, ra viện, hiện có, lượt khám, dịch vụ, công suất giường…',
      'Số nhập tay chỉ khoa mới biết (ví dụ: số ca nặng, sự cố) — điền trực tiếp trên màn hình.',
      'Sau khi chốt, KHÔNG ai sửa được nữa cho tới khi KHTH bấm "Mở khóa".',
    ],
    speaker: 'Dùng slide này làm bản đồ cho cả buổi. Mỗi phần sau tương ứng với một ô trên sơ đồ.',
  });

  // ---------------------------------------------------------------- Phần 1
  L.sectionSlide(pptx, ctx, {
    no: 1, title: 'Vào màn hình Báo cáo giao ban',
    sub: 'Menu, chọn ngày, đọc trạng thái báo cáo và các nút chức năng',
  });

  L.stepSlide(pptx, ctx, {
    kicker: 'Phần 1',
    title: 'Mở màn hình và chọn ngày',
    steps: [
      ['Vào menu Báo cáo giao ban → Báo cáo giao ban', 'Màn hình mở sẵn ngày hôm nay'],
      ['Chọn "Ngày giao ban" nếu cần xem ngày khác', 'Báo cáo tự tải lại theo ngày vừa chọn'],
      ['Đọc chữ nhỏ cạnh tiêu đề', '"(nháp, số liệu … → …)", "(ĐÃ CHỐT)" hoặc "(chưa có dữ liệu — bấm Lấy số liệu)"'],
      ['Đọc dòng "Chế độ" dưới thanh nút', '"Quản trị — xem toàn viện" hoặc "Khoa — phân công N khoa, đang hiện M: …"'],
      ['Nếu thấy "Bạn chưa được phân công khoa nào"', 'Tài khoản chưa được gán khoa — liên hệ phòng KHTH'],
    ],
    note: { label: 'Mẹo:', text: 'Nếu dòng Chế độ ghi "phân công 3 khoa, đang hiện 2" thì một khoa đã bị tắt trong cấu hình. Báo KHTH, không phải lỗi máy của anh/chị.' },
    speaker: 'Cho học viên tự mở màn hình trên máy mình và đọc to dòng Chế độ để chắc chắn đã được gán đúng khoa trước khi học tiếp.',
  });

  imageSlide(pptx, ctx, {
    kicker: 'Phần 1',
    title: 'Bố cục màn hình Báo cáo giao ban',
    images: [{ file: 'anh-07a-bao-cao-giao-ban.png', px: [2400, 1350] }],
    points: [
      'Tiêu đề kèm trạng thái: "(nháp, số liệu … → …)"',
      'Hàng trên: Ngày giao ban · Từ thời điểm · Đến thời điểm',
      'Thanh nút: Làm mới · Trình chiếu · Tạo số liệu · Chốt báo cáo · Xuất Excel (tài khoản khoa chỉ thấy Làm mới, Tạo số liệu)',
      'Dòng "Số liệu đã lấy lúc …" và dòng "Chế độ"',
      'Mỗi khoa một khung: ô số, ô chữ, Ghi chú khoa',
    ],
    speaker: 'Ảnh chụp bằng tài khoản quản trị trên cấu hình mẫu của môi trường huấn luyện. Tài khoản khoa chỉ thấy khung của khoa mình và ít nút hơn.',
  });

  L.tableSlide(pptx, ctx, {
    kicker: 'Phần 1',
    title: 'Các nút trên màn hình',
    head: ['Nút', 'Ai thấy', 'Tác dụng'],
    colW: [2.0, 2.0, 6.4],
    rows: [
      [{ t: 'Làm mới', b: true }, 'Mọi người', 'Tải lại số liệu đang lưu (hiện "Đang tải..."). KHÔNG lấy lại từ HIS'],
      [{ t: 'Tạo số liệu', b: true }, 'Người đang được phép lấy', 'Đếm lại số tự động từ HIS theo khung giờ. Số nhập tay được giữ nguyên'],
      [{ t: 'Trình chiếu', b: true }, 'KHTH', 'Mở màn trình chiếu của ngày đang chọn trong một tab mới'],
      [{ t: 'Chốt báo cáo', b: true }, 'KHTH, khi còn nháp', 'Hỏi xác nhận rồi khoá báo cáo — không ai sửa được nữa'],
      [{ t: 'Mở khóa', b: true }, 'KHTH, khi đã chốt', 'Mở lại để sửa. Bấm là mở ngay, không hỏi lại'],
      [{ t: 'Xuất Excel', b: true }, 'KHTH', 'Tải tệp bao-cao-giao-ban-YYYY-MM-DD.xlsx (thêm "-nhap" nếu chưa chốt)'],
    ],
    note: { label: 'Phân biệt:', text: '"Làm mới" chỉ đọc lại những gì đã lưu. Muốn đếm lại từ HIS phải bấm "Tạo số liệu" — với tài khoản khoa, nút này tự ẩn khi ngày đó đã có số liệu.' },
    speaker: 'Lỗi hay gặp nhất: bấm Làm mới rồi tưởng số liệu đã cập nhật từ HIS. Nhấn mạnh sự khác nhau giữa hai nút.',
  });

  // ---------------------------------------------------------------- Phần 2
  L.sectionSlide(pptx, ctx, {
    no: 2, title: 'Khoa nhập và kiểm tra số liệu',
    sub: 'Tạo số liệu, đọc màu ô, sửa số, ghi chú khoa, cân đối người bệnh, kíp trực',
  });

  L.stepSlide(pptx, ctx, {
    kicker: 'Phần 2',
    title: 'Tạo số liệu lần đầu trong ngày',
    intro: 'Làm sau 07:00 sáng ngày giao ban, khi ca trực đã kết thúc.',
    steps: [
      ['Chọn đúng "Ngày giao ban"', 'Tiêu đề ghi "(chưa có dữ liệu — bấm Lấy số liệu)"'],
      ['Kiểm tra "Từ thời điểm" / "Đến thời điểm"', 'Mặc định 07:00 hôm trước → 07:00 hôm nay. Chỉ đổi khi có chỉ đạo'],
      ['Bấm "Tạo số liệu"', 'Hiện "Đang lấy số liệu...", chờ tới khi các khung khoa hiện số'],
      ['Đọc dòng "Số liệu đã lấy lúc …"', 'Từ giờ nút Tạo số liệu biến mất với tài khoản khoa'],
      ['Rà từng khung khoa, sửa và bổ sung (các slide sau)', 'Mỗi ô tự lưu ngay khi rời khỏi ô'],
    ],
    note: { label: 'Chỉ được một lần:', text: 'Tài khoản khoa chỉ tạo số liệu được khi ngày đó chưa từng có số liệu. Cần lấy lại thì phần mềm báo "Báo cáo ngày này đã có số liệu. Cần lấy lại thì liên hệ phòng KHTH."', kind: 'danger' },
    speaker: 'Không có nút Lưu chung. Mỗi ô tự lưu khi rời ô (bấm Tab hoặc bấm chỗ khác). Nhắc học viên đừng đóng trình duyệt khi con trỏ còn đang ở trong ô vừa gõ.',
  });

  cellLegendSlide(pptx, ctx, {
    kicker: 'Phần 2',
    title: 'Đọc trạng thái của ô số liệu',
    rows: [
      { value: '12', fill: C.white, border: C.line, head: 'Nền trắng', body: 'Số tự động lấy từ HIS, chưa ai sửa. Hoặc số nhập tay khoa đã điền.' },
      { value: '14', fill: C.white, border: C.line, undo: true, head: 'Có nút ↺ bên phải — đã sửa tay', body: 'Rê chuột vào ô thấy "Số HIS: x". Bấm ↺ ("Trả về số tự động") để quay lại số của HIS.' },
      { value: '3', fill: 'F1F1F1', border: C.line, ink: '888888', italic: true, head: 'Xám nghiêng — kế thừa', body: 'Lấy từ báo cáo hôm trước, "chưa xác nhận". Vẫn tính là CHƯA điền — gõ lại (dù cùng số) để xác nhận.' },
      { value: '', fill: C.white, border: C.danger, borderW: 2.25, head: 'Viền đỏ — bắt buộc còn trống', body: 'Ô có dấu * đỏ. Khoa sẽ bị nêu tên ở dòng "Ô BẮT BUỘC CÒN TRỐNG" trên slide Tổng quan.' },
      { value: '?', fill: C.soft, border: C.line, ink: C.accent, head: 'Dấu ? cạnh tên tiêu chí', body: 'Rê chuột để đọc giải thích cách tính hoặc cách điền của tiêu chí đó.' },
    ],
    speaker: 'Ô xám nghiêng là bẫy hay gặp: nhìn có số nên tưởng đã điền. Phải gõ lại để xác nhận, nếu không khoa vẫn bị báo thiếu.',
  });

  imageSlide(pptx, ctx, {
    kicker: 'Phần 2',
    title: 'Trên màn hình thật',
    images: [{ file: 'anh-07b-o-thieu-lech-can-doi.png', px: [2400, 1350] }],
    points: [
      'Khoa Mắt: ô "BN nặng *" viền đỏ nền hồng — ô bắt buộc còn trống',
      'Khoa Tai Mũi Họng: ô "BN ra viện" có nút ↺ — đã sửa tay từ 2 thành 3',
      'Vì sửa ô đó mà khoa lệch cân đối 1 → biểu tượng ⚠ cạnh tên khoa',
      'Ô nhập tay đã điền ("BN nặng", "Giường kê thêm") cũng có nút ↺',
    ],
    speaker: 'Rê chuột vào ⚠ để thấy "Lệch cân đối: 1", rê vào ô BN ra viện để thấy "Số HIS: 2.00". Đây là tình huống cố ý dựng để minh hoạ.',
  });

  L.stepSlide(pptx, ctx, {
    kicker: 'Phần 2',
    title: 'Sửa số, điền ô chữ và ghi chú khoa',
    steps: [
      ['Bấm vào ô, gõ số mới, bấm Tab hoặc bấm ra ngoài', 'Bên phải ô hiện nút ↺. Số HIS vẫn được giữ để đối chiếu'],
      ['Sửa nhầm? Bấm nút ↺ ("Trả về số tự động")', 'Ô quay về số HIS, nút ↺ biến mất'],
      ['Tiêu chí dạng chữ: gõ vào ô văn bản rộng bên dưới', 'Tối đa 5000 ký tự'],
      ['Ghi chú khoa: bấm "Sửa" → soạn → "Lưu"', 'Có in đậm, nghiêng, gạch chân, màu, danh sách, căn lề'],
      ['Nhập sai kiểu, phần mềm báo ngay', '"Tiêu chí này chỉ nhận số nguyên." · "Giá trị phần trăm phải trong khoảng 0–100."'],
    ],
    note: { label: 'Yên tâm:', text: 'Nếu KHTH bấm "Tạo số liệu" lại sau khi khoa đã sửa, số khoa sửa tay VẪN được giữ. Tạo số liệu chỉ cập nhật phần số HIS.', kind: 'ok' },
    speaker: 'Chỉ sửa tay khi chắc chắn HIS sai (ví dụ nhập nhầm khoa trên HIS). Tốt nhất vẫn là sửa gốc trên HIS để các báo cáo khác cũng đúng.',
  });

  balanceSlide(pptx, ctx, {
    kicker: 'Phần 2',
    title: 'Cảnh báo "Lệch cân đối" — tự kiểm tra trước khi KHTH hỏi',
    intro: 'Với khoa điều trị nội trú, số người bệnh phải cân: đầu kỳ + vào − ra = cuối kỳ.',
    terms: [
      { label: 'BN cũ', code: 'bn_cu', sign: '' },
      { label: 'BN vào', code: 'bn_vao', sign: '+' },
      { label: 'Chuyển đến', code: 'bn_chuyen_den', sign: '+' },
      { label: 'Ra viện', code: 'bn_ra_vien', sign: '−' },
      { label: 'Chuyển viện', code: 'bn_chuyen_vien', sign: '−' },
      { label: 'Tử vong', code: 'bn_tu_vong', sign: '−' },
      { label: 'Chuyển khoa', code: 'bn_chuyen_khoa', sign: '−' },
    ],
    result: { label: 'Hiện có', code: 'hien_co' },
    legend: [
      'Không cân → cạnh tên khoa hiện biểu tượng ⚠ vàng; rê chuột vào thấy "Lệch cân đối: X" (X là số chênh). Trên slide trình chiếu có dấu "▲ X" cạnh tên khoa.',
      'Nguyên nhân hay gặp: sửa tay một ô mà quên sửa ô liên quan; người bệnh ra viện nhưng HIS chưa kết thúc điều trị.',
      'Phép tính dùng số đang hiển thị — ô đã sửa tay (có nút ↺) được tính theo số sửa tay.',
    ],
    note: { label: 'Chỉ áp dụng khi', text: 'khoa dùng đúng tám mã tiêu chí ghi dưới mỗi ô (do quản trị đặt). Khoa đặt mã khác thì không bao giờ có cảnh báo này — không có nghĩa là số đã cân.' },
    speaker: 'Cho học viên làm một ví dụ: cũ 30, vào 5, chuyển đến 2, ra viện 6, chuyển khoa 1, hiện có phải là 30. Nếu HIS ghi 31 thì lệch 1.',
  });

  L.stepSlide(pptx, ctx, {
    kicker: 'Phần 2',
    title: 'Kíp trực lãnh đạo',
    intro: 'Do KHTH hoặc người có tên trong danh sách "Người được cập nhật kíp trực" nhập.',
    steps: [
      ['Nhanh nhất: bấm "Sao chép kíp ngày trước"', 'Chép kíp của ngày gần nhất có dữ liệu. Nếu không có: "Không có kíp trực ngày trước để sao chép."'],
      ['Thêm người: trên đúng dòng chức danh, gõ ít nhất 2 ký tự tên / mã nhân viên vào ô "+ thêm người…"', 'Tìm trong danh sách nhân viên HIS, gõ không dấu cũng được'],
      ['Bấm chọn người trong danh sách hiện ra', 'Người đó vào cột "Người trực"; số điện thoại tự điền nếu HIS có'],
      ['Sửa số điện thoại trực tiếp trong cột SĐT nếu cần', 'Tự lưu khi rời ô'],
      ['Bấm "×" để bỏ một người', 'Xoá khỏi kíp của ngày đang chọn'],
    ],
    note: { label: 'Nếu thấy', text: '"Chưa cấu hình chức danh trực (Cấu hình giao ban)." — quản trị cần khai danh mục chức danh trực trước (Phần 5).' },
    speaker: 'Kíp trực hiện trên slide Tổng quan và dòng "KÍP TRỰC LÃNH ĐẠO" trong tệp PPTX, nên cần nhập trước khi trình chiếu.',
  });

  imageSlide(pptx, ctx, {
    kicker: 'Phần 2',
    title: 'Khung Kíp trực lãnh đạo và Ghi chú chung',
    images: [{ file: 'anh-07c-kip-truc-ghi-chu.png', px: [2400, 1350] }],
    points: [
      'Mỗi dòng một chức danh; người trực hiện thành thẻ: tên · SĐT · ×',
      'Ô "+ thêm người (gõ tên/mã NV)..." ngay dưới thẻ',
      'Nút "Sao chép kíp ngày trước" cạnh tiêu đề',
      'Ghi chú chung ở cuối trang — chỉ KHTH sửa được',
    ],
    speaker: 'Tên và số điện thoại trong ảnh là dữ liệu giả.',
  });

  L.tableSlide(pptx, ctx, {
    kicker: 'Phần 2',
    title: 'Số tự động được đếm như thế nào',
    intro: 'Tất cả tính trong khung "Từ thời điểm" → "Đến thời điểm". Nội trú = diện điều trị nội trú trên HIS.',
    head: ['Loại tiêu chí', 'Phần mềm đếm'],
    colW: [2.6, 7.8],
    fontSize: 11.5,
    rows: [
      [{ t: 'BN cũ (đầu kỳ)', b: true }, 'Người bệnh nội trú đang nằm ở khoa tại thời điểm BẮT ĐẦU: đã vào khoa, chưa ra viện, chưa chuyển đi'],
      [{ t: 'Hiện có (cuối kỳ)', b: true }, 'Cách đếm như trên, tại thời điểm KẾT THÚC'],
      [{ t: 'BN vào thẳng', b: true }, 'Người bệnh vào khoa trong khung giờ mà khoa này là khoa đầu tiên của đợt điều trị'],
      [{ t: 'BN chuyển đến', b: true }, 'Người bệnh vào khoa trong khung giờ, từ một khoa khác chuyển sang'],
      [{ t: 'BN chuyển khoa (đi)', b: true }, 'Người bệnh rời khoa sang khoa khác trong khung giờ — tính cho khoa chuyển đi'],
      [{ t: 'Kết thúc điều trị', b: true }, 'Đợt nội trú kết thúc trong khung giờ, tính cho KHOA CUỐI CÙNG, lọc theo loại ra viện / chuyển viện / tử vong…'],
      [{ t: 'BN trên giường chỉ định', b: true }, 'Số người bệnh đang nằm trên các giường được chọn tại thời điểm kết thúc (ví dụ giường yêu cầu)'],
      [{ t: 'Lượt khám', b: true }, 'Số yêu cầu khám chính khoa đã thực hiện trong khung giờ'],
      [{ t: 'Đếm dịch vụ', b: true }, 'Số dịch vụ chỉ định trong khung giờ, lọc theo loại dịch vụ và phạm vi khoa'],
      [{ t: 'Nhập tay', b: true }, 'Không đếm — khoa tự điền'],
    ],
    note: { label: 'Khoa gộp:', text: 'Nếu một dòng báo cáo gộp nhiều khoa HIS, người bệnh chuyển qua lại GIỮA các khoa đó không bị tính là chuyển đến / chuyển khoa.', kind: 'ok' },
    speaker: 'Khi khoa thắc mắc số không khớp, hỏi ngay: khung giờ nào? người bệnh đó được chuyển lúc mấy giờ trên HIS? Hầu hết chênh lệch nằm ở giờ ghi nhận trên HIS.',
  });

  // ---------------------------------------------------------------- Phần 3
  L.sectionSlide(pptx, ctx, {
    no: 3, title: 'KHTH: chốt, trình chiếu và xuất PPTX',
    sub: 'Dành cho phòng Kế hoạch tổng hợp — tài khoản có quyền giaoban-admin',
  });

  L.stepSlide(pptx, ctx, {
    kicker: 'Phần 3',
    title: 'Rà soát và chốt báo cáo trước giờ giao ban',
    steps: [
      ['Bấm "Trình chiếu", xem slide Tổng quan', 'Dòng "Ô BẮT BUỘC CÒN TRỐNG" nêu tên khoa còn thiếu, hoặc "Các khoa đã nhập đủ"'],
      ['Tìm khoa có dấu ▲ / ⚠ "Lệch cân đối"', 'Gọi khoa kiểm tra, hoặc sửa trực tiếp trên màn nhập'],
      ['Cần đếm lại từ HIS? Bấm "Tạo số liệu"', 'Số sửa tay của khoa được giữ nguyên'],
      ['Viết "Ghi chú chung" (nút Sửa ở khung Ghi chú chung)', 'Hiện thành slide riêng ngay sau Tổng quan'],
      ['Bấm "Chốt báo cáo" → xác nhận', 'Tiêu đề chuyển "(ĐÃ CHỐT)", slide đổi nhãn "BẢN NHÁP" thành "ĐÃ CHỐT"'],
    ],
    note: { label: 'Sau khi chốt:', text: 'không sửa được số, ghi chú hay kíp trực ("Báo cáo đã chốt."). Cần sửa thì bấm "Mở khóa" — mở ngay, không hỏi lại — sửa xong nhớ chốt lại.' },
    speaker: 'Nên chốt trước khi xuất PPTX / Excel để tệp lưu trữ mang nhãn ĐÃ CHỐT và tên tệp Excel không có đuôi -nhap.',
  });

  L.flowSlide(pptx, ctx, {
    kicker: 'Phần 3',
    title: 'Thứ tự slide khi trình chiếu (và trong tệp PPTX)',
    nodes: [
      { head: 'Tổng quan', body: 'Nhãn nháp/chốt, kíp trực, tiêu chí toàn viện, ô còn trống', tone: 'primary' },
      { head: 'Ghi chú chung', body: 'Nếu có. Dài thì tự tách "(1/3)", "(2/3)"…' },
      { head: 'Hoạt động điều trị', body: 'Bảng các khoa nội trú, dòng TỔNG CỘNG cuối bảng', tone: 'accent' },
      { head: 'Từng khoa', body: 'Bảng TIÊU CHÍ / SỐ LIỆU, ô chữ, rồi các slide "<Khoa> — Ghi chú"' },
      { head: 'Công suất giường', body: 'Biểu đồ vòng toàn viện và thanh % theo khoa', tone: 'primary' },
    ],
    legend: [
      'Tổng quan chỉ hiện tiêu chí được quản trị tích "Hiện ở màn Tổng quan". Chưa tích gì thì slide nhắc cách cấu hình.',
      'Công suất giường tô màu: từ 90% đỏ · từ 80% cam · từ 60% xanh lá · dưới 60% xanh dương.',
      'Số trên slide là số đang lưu lúc mở — khoa sửa thêm thì bấm F5 để tải lại.',
    ],
    speaker: 'Người trình bày chỉ cần nhớ thứ tự này để điều hành buổi giao ban, dùng menu "☰ Khoa" để nhảy tới khoa cần bàn.',
  });

  imageSlide(pptx, ctx, {
    kicker: 'Phần 3',
    title: 'Slide Tổng quan khi trình chiếu',
    images: [{ file: 'anh-08a-trinh-chieu-tong-quan.png', px: [2400, 1350] }],
    points: [
      'Nhãn "BẢN NHÁP" — sau khi chốt đổi thành "ĐÃ CHỐT"',
      'Khối KÍP TRỰC LÃNH ĐẠO',
      'Bảng tiêu chí gộp toàn viện: các khoa cùng Nhãn gộp được cộng dồn',
      'Thanh đỏ "Ô BẮT BUỘC CÒN TRỐNG: 1 khoa: Khoa Mắt (1)"',
      'Thanh điều khiển dưới cùng, nút "⬇ PPTX", bộ đếm 1/13',
    ],
    speaker: 'Đây là màn đầu tiên lãnh đạo nhìn thấy. KHTH nên xử lý hết thanh đỏ trước giờ giao ban.',
  });

  imageSlide(pptx, ctx, {
    kicker: 'Phần 3',
    title: 'Slide Hoạt động điều trị và slide từng khoa',
    images: [
      { file: 'anh-08b-hoat-dong-dieu-tri.png', px: [2400, 1350] },
      { file: 'anh-08c-slide-khoa-lech.png', px: [2400, 1350] },
    ],
    note: { label: 'Đọc ảnh:', text: 'Trái — bảng Hoạt động điều trị, mỗi khoa nội trú một dòng, dòng TỔNG CỘNG cuối bảng. Phải — slide Khoa Tai Mũi Họng có dấu "▲ 1" cạnh tên: khoa đang lệch cân đối 1. Số vào/chuyển đến tô xanh, ra viện/chuyển viện tô cam.', kind: 'ok' },
    speaker: 'Hai slide này chiếm phần lớn thời gian buổi giao ban.',
  });

  imageSlide(pptx, ctx, {
    kicker: 'Phần 3',
    title: 'Slide Công suất giường',
    images: [{ file: 'anh-08d-cong-suat-giuong.png', px: [2400, 1350] }],
    points: [
      'Trái: vòng công suất — Tổng giường · Đang dùng · Trống',
      'Phải: thanh % theo khoa, xếp từ cao xuống thấp',
      'Màu: từ 90% đỏ · từ 80% cam · từ 60% xanh lá · dưới 60% xanh dương',
      'Trên 100% nghĩa là số người bệnh nằm giường vượt số giường kê khai',
    ],
    speaker: 'Số giường lấy từ danh mục giường trên HIS tại thời điểm kết thúc. Khoa hiện 0% dù đang có người bệnh thì báo KHTH kiểm tra lại buồng giường của khoa trên HIS.',
  });

  L.tableSlide(pptx, ctx, {
    kicker: 'Phần 3',
    title: 'Điều khiển màn trình chiếu',
    head: ['Muốn làm', 'Bàn phím', 'Chuột / nút'],
    colW: [3.0, 3.4, 4.0],
    rows: [
      [{ t: 'Slide tiếp theo', b: true }, '→ · Space · PageDown', 'Bấm nửa PHẢI màn hình'],
      [{ t: 'Slide trước', b: true }, '← · PageUp', 'Bấm nửa TRÁI màn hình'],
      [{ t: 'Về đầu / cuối', b: true }, 'Home / End', '—'],
      [{ t: 'Nhảy tới một khoa', b: true }, '—', '"☰ Khoa" → chọn trong danh sách'],
      [{ t: 'Toàn màn hình', b: true }, 'F', '"⛶ Toàn màn hình (F)"'],
      [{ t: 'Phóng to / thu nhỏ chữ', b: true }, '+  /  −  /  0 (về 100%)', '"A−" · "100%" · "A+" (70%–200%)'],
      [{ t: 'Đổi nền sáng / tối', b: true }, '—', '"☀" / "☾" — chiếu phòng sáng nên dùng nền sáng'],
      [{ t: 'Xuất tệp PPTX', b: true }, '—', '"⬇ PPTX" (chỉ hiện khi có dữ liệu)'],
    ],
    note: { label: 'Lưu ý:', text: 'Phím ESC chỉ thoát chế độ toàn màn hình, không đóng màn trình chiếu. Cỡ chữ và màu nền được nhớ trên từng máy tính.', kind: 'ok' },
    speaker: 'Trước buổi giao ban, mở thử trên máy chiếu thật, chỉnh cỡ chữ cho người ngồi cuối phòng đọc được. Máy nhớ cỡ chữ cho lần sau.',
  });

  L.splitSlide(pptx, ctx, {
    kicker: 'Phần 3',
    title: 'Xuất tệp: PPTX hay Excel?',
    left: {
      head: '⬇ PPTX — trên màn Trình chiếu',
      items: [
        'Tên tệp: giao-ban-YYYY-MM-DD.pptx, khổ 16:9',
        'Cùng thứ tự slide với màn trình chiếu, màu theo nền sáng/tối đang chọn',
        'Bảng, chữ, biểu đồ là đối tượng PowerPoint thật — sửa được',
        'Bảng dài tự tách nhiều slide "(1/n)", dòng tiêu đề lặp lại',
        'Nút đổi thành "Đang xuất…"; lỗi thì hiện "Xuất lỗi" 3 giây',
        'Dùng để: gửi ban giám đốc, lưu hồ sơ, chiếu ở phòng không vào được phần mềm',
      ],
    },
    right: {
      head: 'Xuất Excel — trên màn Báo cáo giao ban',
      items: [
        'Tên tệp: bao-cao-giao-ban-YYYY-MM-DD.xlsx (thêm -nhap nếu chưa chốt)',
        'Mỗi khoa một khối: tên khoa, dòng tên tiêu chí, dòng số liệu, ghi chú khoa',
        'Số sửa tay được ưu tiên hơn số HIS',
        'Cuối tệp: GHI CHÚ CHUNG',
        'Dùng để: tổng hợp, làm báo cáo tuần/tháng, đối chiếu số liệu',
        'Ngày chưa có báo cáo thì không xuất được',
      ],
    },
    speaker: 'Hai tệp phục vụ hai mục đích khác nhau: PPTX để trình bày, Excel để tính toán tiếp.',
  });

  L.cardSlide(pptx, ctx, {
    kicker: 'Phần 3',
    title: 'Kiểm tra tệp PPTX trước khi gửi đi',
    cards: [
      { head: 'Định dạng ghi chú bị bỏ', tone: 'warn',
        body: 'Ghi chú chung và ghi chú khoa được xuất thành chữ thường: mất in đậm, màu, căn lề.\n\nCần nhấn mạnh thì sửa lại trong PowerPoint sau khi xuất.' },
      { head: 'Ô chữ quá dài có thể bị bỏ', tone: 'danger',
        body: 'Tiêu chí dạng chữ không vừa slide sẽ KHÔNG có trong tệp, và không có thông báo.\n\nMở tệp, lướt các slide khoa có ô chữ dài để kiểm tra.' },
      { head: 'Nhãn BẢN NHÁP', tone: 'ok',
        body: 'Xuất trước khi chốt thì slide Tổng quan mang nhãn "BẢN NHÁP".\n\nMuốn tệp lưu trữ ghi "ĐÃ CHỐT" thì chốt báo cáo trước, rồi tải lại màn trình chiếu (F5) và xuất.' },
    ],
    speaker: 'Ba điểm này học viên hay phát hiện sau khi đã gửi tệp đi. Dặn thói quen: xuất xong mở tệp lướt một lượt.',
  });

  // ---------------------------------------------------------------- Phần 4
  L.sectionSlide(pptx, ctx, {
    no: 4, title: 'Phân quyền và vai trò',
    sub: 'Cấp quyền dùng module giao ban cho một tài khoản — người quản trị hệ thống (superadministrator)',
  });

  L.tableSlide(pptx, ctx, {
    kicker: 'Phần 4',
    title: 'Hai quyền, hai vai trò của module giao ban',
    intro: 'Quyền là từng việc được phép làm. Vai trò là "gói" nhiều quyền. Nên cấp theo vai trò.',
    head: ['Vai trò (ô tích trong cửa sổ Roles)', 'Gồm quyền (ô tích trong cửa sổ Permissions)', 'Dành cho'],
    colW: [3.3, 4.4, 2.7],
    rows: [
      [{ t: 'Giao ban - Nhập liệu khoa', b: true }, 'Báo cáo giao ban - Xem/nhập theo khoa', 'Cán bộ nhập liệu các khoa'],
      [{ t: 'Giao ban - Quản trị', b: true }, 'Báo cáo giao ban - Xem/nhập theo khoa\nBáo cáo giao ban - Quản trị (lấy số liệu, chốt, cấu hình)', 'Phòng KHTH'],
      [{ t: 'Administrator', b: true }, 'Có sẵn cả hai quyền giao ban', 'Quản trị hệ thống'],
    ],
    note: { label: 'Có vai trò vẫn chưa đủ với tài khoản khoa:', text: 'phải gán thêm khoa ở Cấu hình giao ban → "Gán tài khoản HIS ↔ khoa" (Phần 5). Chưa gán thì màn hình báo "Bạn chưa được phân công khoa nào".' },
    speaker: 'Hai tên dễ nhầm: vai trò ghi "Giao ban - …", còn quyền ghi "Báo cáo giao ban - …". Cấp một trong hai đều mở được màn hình, nhưng cấp theo vai trò thì sau này thu hồi gọn hơn.',
  });

  L.stepSlide(pptx, ctx, {
    kicker: 'Phần 4',
    title: 'Cấp vai trò giao ban cho một tài khoản',
    intro: 'Chỉ tài khoản có vai trò SuperAdministrator mới thấy màn hình này.',
    steps: [
      ['Vào menu Thiết lập hệ thống → Quyền và Vai trò', 'Bảng danh sách người dùng: Tên đăng nhập, Họ và tên, Email, Kích hoạt'],
      ['Gõ tên đăng nhập hoặc họ tên vào ô "Search"', 'Bảng lọc còn đúng tài khoản cần cấp'],
      ['Bấm nút đỏ "Roles" trên dòng tài khoản đó', 'Cửa sổ "Chỉnh Sửa Vai trò" hiện ra, ô đã tích là vai trò đang có'],
      ['Tích "Giao ban - Nhập liệu khoa" hoặc "Giao ban - Quản trị"', 'Không bỏ tích các vai trò khác tài khoản đang dùng'],
      ['Bấm "Lưu Thay Đổi"', 'Người dùng đăng xuất rồi đăng nhập lại để menu "Báo cáo giao ban" hiện ra'],
      ['Với tài khoản khoa: sang Cấu hình giao ban gán khoa (Phần 5)', 'Dòng "Chế độ: Khoa — phân công N khoa" xác nhận đã đúng'],
    ],
    note: { label: 'Cấp xong mà menu chưa hiện:', text: 'nhờ CNTT chạy "php artisan cache:clear", rồi người dùng đăng nhập lại. Nếu cột "Kích hoạt" là dấu ✗ đỏ thì tài khoản đang bị khoá, phải mở trước.' },
    speaker: 'Nhấn bước 4: chỉ tích thêm, không bỏ các ô đang có — bỏ nhầm là người dùng mất quyền ở module khác.',
  });

  imageSlide(pptx, ctx, {
    kicker: 'Phần 4',
    title: 'Màn Quyền và Vai trò',
    images: [{ file: 'anh-10a-quan-ly-nguoi-dung.png', px: [2400, 1350] }],
    points: [
      'Menu bên trái: Thiết lập hệ thống → Quyền và Vai trò',
      'Ô "Search" góc phải để tìm tài khoản',
      'Cột "Kích hoạt": ✓ xanh = đang dùng, ✗ đỏ = bị khoá',
      'Nút xanh "Permissions": cấp từng quyền lẻ',
      'Nút đỏ "Roles": cấp theo vai trò (nên dùng)',
    ],
    speaker: 'Ảnh đã được che tên và email thật của nhân viên.',
  });

  imageSlide(pptx, ctx, {
    kicker: 'Phần 4',
    title: 'Cửa sổ Vai trò và cửa sổ Quyền',
    images: [
      { file: 'anh-10b-modal-vai-tro.png', px: [1200, 832] },
      { file: 'anh-10c-modal-quyen.png', px: [1200, 1290] },
    ],
    points: [
      'Trái — "Chỉnh Sửa Vai trò" (nút Roles): hai ô "Giao ban - Nhập liệu khoa" và "Giao ban - Quản trị"',
      'Phải — "Chỉnh Sửa Quyền" (nút Permissions): hai ô cuối "Báo cáo giao ban - …"',
      'Tích xong bấm "Lưu Thay Đổi"; "Đóng" là bỏ qua, không lưu',
    ],
    pointsW: 3.0,
    speaker: 'Thường chỉ dùng cửa sổ bên trái. Cửa sổ bên phải dành cho trường hợp đặc biệt cần cấp lẻ một quyền.',
  });

  // ---------------------------------------------------------------- Phần 5
  L.sectionSlide(pptx, ctx, {
    no: 5, title: 'Cấu hình giao ban (quản trị)',
    sub: 'Menu Báo cáo giao ban → Cấu hình giao ban — quyền giaoban-admin',
  });

  L.tableSlide(pptx, ctx, {
    kicker: 'Phần 5',
    title: 'Bốn khối trên màn Cấu hình giao ban',
    head: ['Khối', 'Dùng để', 'Thao tác chính'],
    colW: [2.6, 3.6, 4.2],
    fontSize: 11.5,
    rows: [
      [{ t: '1. Khoa hiển thị trên báo cáo', b: true }, 'Danh sách khoa có khung trên báo cáo và slide', 'TT (thứ tự), Tên hiển thị, Loại khối (Điều trị / Khám / Cận lâm sàng), Khoa HIS (gộp được nhiều), cột BID = bật/tắt khoa, nút "Tiêu chí (N)", "Lưu" từng dòng. "Thêm khoa" để tạo mới'],
      [{ t: '2. Gán tài khoản HIS ↔ khoa', b: true }, 'Quyết định cán bộ nào nhập cho khoa nào', 'Tìm tài khoản (≥ 2 ký tự) → chọn "Khoa được nhập" → "Lưu gán khoa". Lưu là THAY TOÀN BỘ danh sách khoa cũ của tài khoản đó'],
      [{ t: '3. Danh mục chức danh trực', b: true }, 'Các chức danh chọn trong Kíp trực lãnh đạo', 'TT, Chức danh, Hoạt động, Lưu. "Thêm chức danh"'],
      [{ t: '4. Người được cập nhật kíp trực', b: true }, 'Cho người không phải quản trị nhập kíp trực', 'Thêm tài khoản → "Lưu danh sách". Quản trị luôn được cập nhật'],
    ],
    note: { label: 'Sau khi đổi quyền / nhóm quyền', text: 'cho một tài khoản mà màn hình chưa đổi theo: nhờ CNTT chạy "php artisan cache:clear", rồi người dùng đăng nhập lại.' },
    speaker: 'Khoa mới tạo nằm ở khối Điều trị với một tiêu chí "BN cũ". Phải vào "Tiêu chí" để khai đủ trước khi cho khoa dùng.',
  });

  imageSlide(pptx, ctx, {
    kicker: 'Phần 5',
    title: 'Màn Cấu hình báo cáo giao ban',
    images: [{ file: 'anh-11-cau-hinh-giao-ban.png', px: [2400, 1350] }],
    points: [
      'Trái: bảng "Khoa hiển thị trên báo cáo" — mỗi dòng một khoa, sửa xong bấm "Lưu" cuối dòng',
      'Cột "Khoa HIS (gộp)": Khoa Phụ Sản trong ví dụ gộp 3 khoa HIS',
      'Nút "Tiêu chí (N)": số trong ngoặc là số tiêu chí đang có',
      'Phải: "Gán tài khoản HIS ↔ khoa"',
      'Cuộn xuống: Danh mục chức danh trực, Người được cập nhật kíp trực',
    ],
    speaker: 'Ảnh chụp trên cấu hình mẫu của môi trường huấn luyện: 5 khoa điều trị, 1 khoa khám, 1 đơn vị chẩn đoán hình ảnh.',
  });

  L.stepSlide(pptx, ctx, {
    kicker: 'Phần 5',
    title: 'Khai báo tiêu chí cho một khoa',
    intro: 'Bấm nút "Tiêu chí (N)" trên dòng khoa — mở cửa sổ "Tiêu chí — <khoa> [khối]".',
    steps: [
      ['Nạp nhanh: "Nạp mẫu" hoặc "Nhân bản từ khoa"', 'Mẫu có sẵn: Điều trị, Khám, Tổng dịch vụ, CĐHA, Xét nghiệm. Nhân bản hỏi thay thế hay nối thêm'],
      ['Hoặc "Thêm tiêu chí" → chọn loại', 'Chỉ hiện các loại hợp với khối của khoa'],
      ['Điền Mã tiêu chí, Tên hiển thị và điều kiện lọc', 'Mã: chữ thường a–z, số, dấu _, tối đa 32 ký tự, không trùng'],
      ['Kéo thẻ để đổi thứ tự', 'Thứ tự này là thứ tự ô trên màn nhập và slide'],
      ['Bấm "Tính thử" với một khung giờ', 'Bảng Tiêu chí / Giá trị / Ghi chú — "chưa ghi vào báo cáo"'],
      ['Bấm "Lưu tiêu chí"', 'Thẻ lỗi tô đỏ kèm "(tiêu chí thứ N)"'],
    ],
    note: { label: 'Nhớ:', text: 'Khoa điều trị muốn có cảnh báo cân đối thì PHẢI dùng đúng mã: bn_cu, bn_vao, bn_chuyen_den, bn_ra_vien, bn_chuyen_vien, bn_tu_vong, bn_chuyen_khoa, hien_co. Nạp mẫu "Điều trị (mặc định)" là có sẵn.' },
    speaker: 'Luôn bấm Tính thử trước khi Lưu. "Đếm dịch vụ" chưa chọn Phạm vi khoa sẽ ra 0 — Tính thử báo ngay "Chưa có phạm vi khoa".',
  });

  L.tableSlide(pptx, ctx, {
    kicker: 'Phần 5',
    title: 'Các trường quan trọng của một tiêu chí',
    head: ['Trường', 'Ý nghĩa'],
    colW: [3.0, 7.4],
    fontSize: 11.5,
    rows: [
      [{ t: 'Phạm vi khoa', b: true }, '(Đếm dịch vụ) Dịch vụ do khoa này thực hiện / do khoa này chỉ định / chỉ định phòng, dịch vụ cụ thể'],
      [{ t: 'Kiểu giá trị', b: true }, '(Nhập tay) số nguyên · số thập phân (≤ 2 chữ số lẻ) · phần trăm (0–100) · chữ'],
      [{ t: 'Nhỏ nhất / Lớn nhất, Đơn vị', b: true }, 'Chặn giá trị ngoài khoảng khi khoa nhập'],
      [{ t: 'Bắt buộc nhập', b: true }, 'Ô có dấu * đỏ; để trống thì khoa bị nêu ở "Ô BẮT BUỘC CÒN TRỐNG"'],
      [{ t: 'Giá trị mặc định', b: true }, 'Điền sẵn ở lần tạo số liệu đầu tiên của ngày'],
      [{ t: 'Kế thừa từ phiên trước', b: true }, 'Chép số của báo cáo hôm trước (hiện xám nghiêng, khoa phải xác nhận)'],
      [{ t: 'Giải thích cho khoa', b: true }, 'Nội dung hiện khi khoa rê chuột vào dấu ?'],
      [{ t: 'Hiện ở màn Tổng quan + Nhãn gộp', b: true }, 'Đưa lên slide Tổng quan; các khoa cùng Nhãn gộp được cộng dồn thành một ô'],
      [{ t: 'Hiện ở slide Hoạt động điều trị + Thứ tự cột', b: true }, '(Chỉ khối Điều trị) Chọn cột và thứ tự cột của bảng Hoạt động điều trị'],
    ],
    note: { label: 'Bảng Hoạt động điều trị gộp cột theo TÊN tiêu chí:', text: 'hai khoa đặt tên khác nhau cho cùng một thứ (ví dụ "BN vào" và "Vào viện") sẽ thành hai cột. Thống nhất tên giữa các khoa.' },
    speaker: 'Tiêu chí dạng chữ không đưa lên Tổng quan được — phần mềm báo lỗi khi lưu.',
  });

  imageSlide(pptx, ctx, {
    kicker: 'Phần 5',
    title: 'Cửa sổ khai báo tiêu chí',
    images: [{ file: 'anh-09-khai-bao-tieu-chi.png', px: [1350, 1693] }],
    pointsW: 6.4,
    points: [
      'Thanh công cụ: Thêm tiêu chí · Nạp mẫu · Nhân bản từ khoa · Tính thử',
      'Thẻ "Form" là nơi làm việc thường ngày; "JSON (nâng cao)" chỉ dành cho người quản trị thành thạo',
      'Mỗi dòng một tiêu chí: mã (chữ đỏ), tên, nhãn loại. Kéo tay nắm ⠿ bên trái để đổi thứ tự; thùng rác đỏ để xoá',
      'Thẻ đang mở trong ảnh: tiêu chí nhập tay "BN nặng" — Kiểu giá trị int, Nhỏ nhất 0, Bắt buộc nhập, có Giải thích cho khoa',
      'Mục "Màn Tổng quan": tích "Hiện ở màn Tổng quan" với Nhãn gộp "BN nặng", tích "Hiện ở slide Hoạt động điều trị" ở cột thứ 9',
      'Bấm "Tính thử" trước, rồi mới "Lưu tiêu chí". "Huỷ" là bỏ mọi thay đổi',
    ],
    speaker: 'Nếu có thời gian, làm mẫu trực tiếp: nạp mẫu Điều trị, Tính thử cho hôm qua, so số với HIS.',
  });

  // ---------------------------------------------------------------- Phần 5
  L.sectionSlide(pptx, ctx, {
    no: 6, title: 'Xử lý tình huống thường gặp',
    sub: 'Gặp thông báo gì → vì sao → làm gì',
  });

  L.caseSlide(pptx, ctx, {
    kicker: 'Phần 6',
    title: 'Thông báo khi nhập liệu',
    cases: [
      { what: '"Bạn chưa được phân công khoa nào"', why: 'Tài khoản chưa được gán khoa', fix: 'Báo KHTH gán ở khối "Gán tài khoản HIS ↔ khoa"' },
      { what: '"Chưa có số liệu cho ngày này"', why: 'Chưa ai bấm Tạo số liệu cho ngày đó', fix: 'Tài khoản khoa được gán thì tự bấm "Tạo số liệu"; nếu không có nút, báo KHTH' },
      { what: '"Báo cáo ngày này đã có số liệu. Cần lấy lại thì liên hệ phòng KHTH."', why: 'Khoa chỉ được tạo số liệu một lần', fix: 'Sửa tay các ô sai, hoặc nhờ KHTH tạo lại' },
      { what: '"Báo cáo đã chốt."', why: 'KHTH đã chốt báo cáo', fix: 'Báo KHTH mở khóa nếu thật sự cần sửa' },
      { what: '"Bạn không có quyền nhập số liệu khoa này."', why: 'Khoa không nằm trong danh sách được gán', fix: 'Báo KHTH kiểm tra phân công' },
    ],
    speaker: 'Đọc từng dòng, hỏi học viên sẽ làm gì trước khi lật cột bên phải.',
  });

  L.caseSlide(pptx, ctx, {
    kicker: 'Phần 6',
    title: 'Tình huống khi rà soát và trình chiếu',
    cases: [
      { what: 'Khoa bị nêu ở "Ô BẮT BUỘC CÒN TRỐNG" dù ô có số', why: 'Số là kế thừa (xám nghiêng) chưa xác nhận', fix: 'Khoa gõ lại số vào ô để xác nhận, rồi tải lại trình chiếu' },
      { what: '⚠ "Lệch cân đối: X"', why: 'Các ô vào/ra/hiện có không khớp nhau', fix: 'Soát lại từng ô theo công thức; ưu tiên sửa gốc trên HIS' },
      { what: 'Tổng quan ghi "CHƯA ĐÁNH DẤU TIÊU CHÍ NÀO"', why: 'Chưa tích "Hiện ở màn Tổng quan"', fix: 'Quản trị vào Tiêu chí của khoa, tích ô đó rồi lưu' },
      { what: 'Nút "⬇ PPTX" báo "Xuất lỗi"', why: 'Trình duyệt không tạo được tệp', fix: 'Tải lại trang (F5) rồi thử lại; không được thì chụp màn hình báo CNTT' },
      { what: 'Slide không thấy số khoa vừa sửa', why: 'Màn trình chiếu lấy số lúc mở', fix: 'Bấm F5 trên màn trình chiếu' },
    ],
    speaker: 'Tình huống số 1 gặp nhiều nhất ở những ngày đầu dùng tiêu chí có kế thừa.',
  });

  L.splitSlide(pptx, ctx, {
    kicker: 'Ranh giới trách nhiệm',
    title: 'Việc của khoa và việc của KHTH / CNTT',
    left: {
      head: 'CÁN BỘ KHOA tự làm',
      items: [
        'Tạo số liệu sau 07:00, đúng ngày giao ban',
        'Rà từng ô, sửa khi HIS sai, gõ xác nhận các ô xám nghiêng',
        'Điền đủ ô bắt buộc (*), ghi chú khoa',
        'Tự kiểm tra cảnh báo "Lệch cân đối" trước giờ giao ban',
        'Ưu tiên sửa dữ liệu gốc trên HIS khi sai do nhập liệu',
      ],
    },
    right: {
      head: 'BÁO PHÒNG KHTH / CNTT',
      items: [
        'Chưa được gán khoa, gán sai khoa, dòng Chế độ đếm khoa lệch',
        'Cần lấy lại số liệu từ HIS khi ngày đã có số liệu',
        'Cần sửa khi báo cáo đã chốt',
        'Cần thêm / sửa tiêu chí, chức danh trực, khoa trên báo cáo',
        '"Lỗi lấy số liệu từ HIS", "Xuất lỗi" lặp lại — kèm ảnh chụp màn hình',
      ],
    },
    speaker: 'Slide nên in ra dán ở phòng hành chính khoa.',
  });

  L.closingSlide(pptx, ctx, {
    title: 'Tóm lại — năm điều cần nhớ',
    points: [
      'Số liệu tính từ 07:00 hôm trước đến 07:00 ngày giao ban. Khoa chỉ "Tạo số liệu" được một lần.',
      '"Làm mới" chỉ đọc lại; "Tạo số liệu" mới đếm lại từ HIS — và vẫn giữ số khoa sửa tay.',
      'Nút ↺ cạnh ô = đã sửa tay · ô xám nghiêng = kế thừa, phải gõ lại để xác nhận · viền đỏ = còn thiếu.',
      '"Lệch cân đối" = cũ + vào + chuyển đến − ra viện − chuyển viện − tử vong − chuyển khoa ≠ hiện có.',
      'KHTH chốt trước rồi mới xuất PPTX / Excel; mở tệp PPTX kiểm tra trước khi gửi.',
    ],
    contact: 'Màn hình: Báo cáo giao ban → Báo cáo giao ban · Cấu hình giao ban\nHướng dẫn chẩn đoán phân quyền khoa: docs/giaoban-chan-doan-phan-quyen-khoa.md\nHỗ trợ: Phòng Kế hoạch tổng hợp · Phòng Công nghệ thông tin — số máy lẻ: ………',
  });

  return pptx;
};
