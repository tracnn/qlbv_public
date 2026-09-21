// Hàm dựng slide dùng chung cho ba bộ slide đào tạo.
// Mọi kích thước tính bằng inch trên khổ 16:9 (13.333 x 7.5).

const FONT = 'Arial';

const C = {
  primary: '1F4E79',   // xanh đậm — tiêu đề, dải đầu slide
  accent: '0E7C7B',    // xanh ngọc — nhấn phụ
  danger: 'C62828',    // đỏ — cảnh báo, lỗi chặn
  warn: 'B26A00',      // cam đậm — lưu ý
  ok: '2E7D32',        // xanh lá — trạng thái tốt
  ink: '1A1A1A',       // chữ chính
  muted: '5A6472',     // chữ phụ
  line: 'D3DAE3',      // đường kẻ
  soft: 'F2F6FA',      // nền khối nhạt
  softWarn: 'FFF6E5',
  softDanger: 'FDECEC',
  softOk: 'EAF5EC',
  white: 'FFFFFF',
};

const W = 13.333;
const H = 7.5;
const M = 0.62;          // lề trái/phải
const BODY_W = W - M * 2;
const BODY_TOP = 1.45;   // mép trên vùng nội dung

// ---------------------------------------------------------------- tiện ích

function notes(slide, text) {
  if (text) slide.addNotes(text);
}

// Dải tiêu đề chuẩn ở đầu mỗi slide nội dung.
function header(slide, title, kicker) {
  slide.addShape('rect', { x: 0, y: 0, w: W, h: 0.09, fill: { color: C.primary } });
  if (kicker) {
    slide.addText(kicker.toUpperCase(), {
      x: M, y: 0.32, w: BODY_W, h: 0.26,
      fontFace: FONT, fontSize: 11, bold: true, color: C.accent, charSpacing: 1,
    });
    slide.addText(title, {
      x: M, y: 0.6, w: BODY_W, h: 0.62,
      fontFace: FONT, fontSize: 26, bold: true, color: C.primary, valign: 'top',
    });
  } else {
    slide.addText(title, {
      x: M, y: 0.42, w: BODY_W, h: 0.7,
      fontFace: FONT, fontSize: 26, bold: true, color: C.primary, valign: 'middle',
    });
  }
  slide.addShape('line', {
    x: M, y: BODY_TOP - 0.16, w: BODY_W, h: 0,
    line: { color: C.line, width: 1 },
  });
}

// Số trang + tên deck ở chân slide.
function footer(slide, deckName, pageNo) {
  slide.addText(deckName, {
    x: M, y: H - 0.46, w: BODY_W - 1, h: 0.28,
    fontFace: FONT, fontSize: 9, color: C.muted,
  });
  slide.addText(String(pageNo), {
    x: W - M - 0.9, y: H - 0.46, w: 0.9, h: 0.28,
    fontFace: FONT, fontSize: 9, color: C.muted, align: 'right',
  });
}

// ---------------------------------------------------------------- các loại slide

// Slide bìa.
function titleSlide(pptx, { title, subtitle, audience, meta }) {
  const s = pptx.addSlide();
  s.background = { color: C.primary };
  s.addShape('rect', { x: 0, y: H - 0.55, w: W, h: 0.55, fill: { color: C.accent } });
  s.addText(title, {
    x: 0.9, y: 2.0, w: W - 1.8, h: 1.5,
    fontFace: FONT, fontSize: 40, bold: true, color: C.white, valign: 'bottom',
  });
  s.addText(subtitle, {
    x: 0.9, y: 3.55, w: W - 1.8, h: 0.6,
    fontFace: FONT, fontSize: 20, color: 'C9DDF0',
  });
  s.addShape('line', { x: 0.9, y: 4.35, w: 3.2, h: 0, line: { color: C.accent, width: 3 } });
  s.addText(audience, {
    x: 0.9, y: 4.6, w: W - 1.8, h: 0.5,
    fontFace: FONT, fontSize: 16, bold: true, color: C.white,
  });
  s.addText(meta, {
    x: 0.9, y: 5.25, w: W - 1.8, h: 0.9,
    fontFace: FONT, fontSize: 12, color: 'A9C4DC', lineSpacing: 18,
  });
  return s;
}

// Slide phân chương.
function sectionSlide(pptx, ctx, { no, title, sub }) {
  const s = pptx.addSlide();
  s.background = { color: C.soft };
  s.addShape('rect', { x: 0, y: 0, w: 0.28, h: H, fill: { color: C.primary } });
  s.addText(String(no), {
    x: 1.0, y: 2.3, w: 1.6, h: 1.4,
    fontFace: FONT, fontSize: 72, bold: true, color: 'C3D3E2',
  });
  s.addText(title, {
    x: 2.5, y: 2.45, w: W - 3.4, h: 1.0,
    fontFace: FONT, fontSize: 32, bold: true, color: C.primary, valign: 'middle',
  });
  if (sub) {
    s.addText(sub, {
      x: 2.5, y: 3.5, w: W - 3.4, h: 0.9,
      fontFace: FONT, fontSize: 15, color: C.muted, lineSpacing: 22,
    });
  }
  ctx.page += 1;
  return s;
}

// Slide gạch đầu dòng. bullets: chuỗi, hoặc { t, b (đậm), sub (dòng phụ) }.
function bulletSlide(pptx, ctx, { title, kicker, bullets, note, speaker }) {
  const s = pptx.addSlide();
  header(s, title, kicker);
  const items = [];
  bullets.forEach((b) => {
    const o = typeof b === 'string' ? { t: b } : b;
    items.push({
      text: o.t,
      options: {
        bullet: { code: '25AA' }, fontSize: o.size || 16, bold: !!o.b,
        color: o.color || C.ink, breakLine: true, paraSpaceAfter: o.sub ? 2 : 10,
      },
    });
    if (o.sub) {
      items.push({
        // Thụt lề bằng khoảng trắng: pptxgenjs chỉ áp indentLevel cho dòng có bullet.
        text: '   ' + o.sub,
        options: {
          bullet: false, fontSize: 13, color: C.muted,
          breakLine: true, paraSpaceAfter: 10,
        },
      });
    }
  });
  const bodyH = note ? H - BODY_TOP - 1.55 : H - BODY_TOP - 0.7;
  s.addText(items, {
    x: M, y: BODY_TOP, w: BODY_W, h: bodyH,
    fontFace: FONT, valign: 'top', lineSpacing: 24,
  });
  if (note) calloutAt(s, note, H - 1.95);
  footer(s, ctx.deck, ctx.page);
  notes(s, speaker);
  ctx.page += 1;
  return s;
}

// Khối "Lưu ý" / "Cảnh báo" đặt ở toạ độ y cho trước.
function calloutAt(s, note, y) {
  const kind = note.kind || 'warn';
  const bg = kind === 'danger' ? C.softDanger : kind === 'ok' ? C.softOk : C.softWarn;
  const bar = kind === 'danger' ? C.danger : kind === 'ok' ? C.ok : C.warn;
  s.addShape('rect', { x: M, y, w: BODY_W, h: 1.15, fill: { color: bg }, line: { color: bg } });
  s.addShape('rect', { x: M, y, w: 0.07, h: 1.15, fill: { color: bar }, line: { color: bar } });
  s.addText([
    { text: (note.label || 'Lưu ý') + ' ', options: { bold: true, color: bar } },
    { text: note.text, options: { color: C.ink } },
  ], {
    x: M + 0.22, y: y + 0.08, w: BODY_W - 0.45, h: 0.99,
    fontFace: FONT, fontSize: 13, valign: 'middle', lineSpacing: 19,
  });
}

// Slide các bước thao tác. steps: [[việc làm, kết quả nhìn thấy], ...]
function stepSlide(pptx, ctx, { title, kicker, intro, steps, note, speaker }) {
  const s = pptx.addSlide();
  header(s, title, kicker);
  let y = BODY_TOP;
  if (intro) {
    s.addText(intro, {
      x: M, y, w: BODY_W, h: 0.34,
      fontFace: FONT, fontSize: 13, italic: true, color: C.muted,
    });
    y += 0.42;
  }
  const avail = (note ? H - 1.95 : H - 0.62) - y - 0.1;
  const gap = 0.12;
  const h = Math.min(0.92, (avail - gap * (steps.length - 1)) / steps.length);
  steps.forEach((st, i) => {
    const top = y + i * (h + gap);
    s.addShape('rect', { x: M, y: top, w: BODY_W, h, fill: { color: i % 2 ? C.white : C.soft }, line: { color: C.line } });
    s.addShape('ellipse', { x: M + 0.16, y: top + (h - 0.42) / 2, w: 0.42, h: 0.42, fill: { color: C.primary } });
    s.addText(String(i + 1), {
      x: M + 0.16, y: top + (h - 0.42) / 2, w: 0.42, h: 0.42,
      fontFace: FONT, fontSize: 14, bold: true, color: C.white, align: 'center', valign: 'middle',
    });
    s.addText(st[0], {
      x: M + 0.72, y: top, w: BODY_W * 0.52, h,
      fontFace: FONT, fontSize: 14, color: C.ink, valign: 'middle', lineSpacing: 19,
    });
    s.addText(st[1], {
      x: M + 0.72 + BODY_W * 0.53, y: top, w: BODY_W * 0.44 - 0.85, h,
      fontFace: FONT, fontSize: 12, color: C.muted, valign: 'middle', lineSpacing: 17,
    });
  });
  if (note) calloutAt(s, note, H - 1.95);
  footer(s, ctx.deck, ctx.page);
  notes(s, speaker);
  ctx.page += 1;
  return s;
}

// Slide bảng. head: [..], rows: [[..]], colW: tỉ lệ cột (tổng bất kỳ).
function tableSlide(pptx, ctx, { title, kicker, intro, head, rows, colW, note, fontSize, speaker }) {
  const s = pptx.addSlide();
  header(s, title, kicker);
  let y = BODY_TOP;
  if (intro) {
    s.addText(intro, {
      x: M, y, w: BODY_W, h: 0.34,
      fontFace: FONT, fontSize: 13, italic: true, color: C.muted,
    });
    y += 0.42;
  }
  const sum = colW.reduce((a, b) => a + b, 0);
  const w = colW.map((c) => (c / sum) * BODY_W);
  const fs = fontSize || 12;
  const body = [
    head.map((t) => ({
      text: t,
      options: { bold: true, color: C.white, fill: { color: C.primary }, fontSize: fs, valign: 'middle' },
    })),
    ...rows.map((r, i) => r.map((cell) => {
      const o = typeof cell === 'string' ? { t: cell } : cell;
      return {
        text: o.t,
        options: {
          color: o.color || C.ink, bold: !!o.b, fontSize: fs, valign: 'middle',
          fill: { color: i % 2 ? C.white : C.soft },
        },
      };
    })),
  ];
  s.addTable(body, {
    x: M, y, w: BODY_W, colW: w,
    border: { type: 'solid', color: C.line, pt: 1 },
    fontFace: FONT, margin: [4, 6, 4, 6], autoPage: false,
  });
  if (note) calloutAt(s, note, H - 1.95);
  footer(s, ctx.deck, ctx.page);
  notes(s, speaker);
  ctx.page += 1;
  return s;
}

// Slide khung chờ ảnh chụp màn hình. Ghi số ảnh để khớp với DANH-SACH-ANH-CAN-CHUP.md
function shotSlide(pptx, ctx, { title, kicker, shot, caption, points, note, speaker }) {
  const s = pptx.addSlide();
  header(s, title, kicker);
  const hasPoints = points && points.length;
  const boxW = hasPoints ? BODY_W * 0.6 : BODY_W;
  const boxH = (note ? H - 1.95 : H - 0.7) - BODY_TOP - 0.1;
  s.addShape('rect', {
    x: M, y: BODY_TOP, w: boxW, h: boxH,
    fill: { color: C.soft }, line: { color: C.muted, width: 1.25, dashType: 'dash' },
  });
  s.addText([
    { text: `[Ảnh ${shot}]`, options: { bold: true, color: C.primary, fontSize: 15, breakLine: true } },
    { text: caption, options: { color: C.muted, fontSize: 13 } },
  ], {
    x: M + 0.3, y: BODY_TOP, w: boxW - 0.6, h: boxH,
    fontFace: FONT, align: 'center', valign: 'middle', lineSpacing: 20,
  });
  if (hasPoints) {
    s.addText(points.map((p) => ({
      text: p,
      options: { bullet: { code: '25AA' }, fontSize: 13, color: C.ink, breakLine: true, paraSpaceAfter: 9 },
    })), {
      x: M + boxW + 0.3, y: BODY_TOP, w: BODY_W - boxW - 0.3, h: boxH,
      fontFace: FONT, valign: 'top', lineSpacing: 19,
    });
  }
  if (note) calloutAt(s, note, H - 1.95);
  footer(s, ctx.deck, ctx.page);
  notes(s, speaker);
  ctx.page += 1;
  return s;
}

// Slide các thẻ (card) ngang — dùng cho "3 việc cần nhớ", "4 chỉ số"...
function cardSlide(pptx, ctx, { title, kicker, cards, note, speaker }) {
  const s = pptx.addSlide();
  header(s, title, kicker);
  const n = cards.length;
  const gap = 0.26;
  const cw = (BODY_W - gap * (n - 1)) / n;
  const ch = note ? H - BODY_TOP - 2.05 : H - BODY_TOP - 0.8;
  cards.forEach((c, i) => {
    const x = M + i * (cw + gap);
    const bar = c.tone === 'danger' ? C.danger : c.tone === 'ok' ? C.ok : c.tone === 'warn' ? C.warn : C.accent;
    s.addShape('rect', { x, y: BODY_TOP, w: cw, h: ch, fill: { color: C.soft }, line: { color: C.line } });
    s.addShape('rect', { x, y: BODY_TOP, w: cw, h: 0.1, fill: { color: bar }, line: { color: bar } });
    s.addText(c.head, {
      x: x + 0.22, y: BODY_TOP + 0.3, w: cw - 0.44, h: 0.75,
      fontFace: FONT, fontSize: 16, bold: true, color: C.primary, valign: 'top', lineSpacing: 21,
    });
    s.addText(c.body, {
      x: x + 0.22, y: BODY_TOP + 1.08, w: cw - 0.44, h: ch - 1.3,
      fontFace: FONT, fontSize: 13, color: C.ink, valign: 'top', lineSpacing: 19,
    });
  });
  if (note) calloutAt(s, note, H - 1.95);
  footer(s, ctx.deck, ctx.page);
  notes(s, speaker);
  ctx.page += 1;
  return s;
}

// Slide sơ đồ luồng ngang: các ô nối bằng mũi tên.
function flowSlide(pptx, ctx, { title, kicker, intro, nodes, legend, note, speaker }) {
  const s = pptx.addSlide();
  header(s, title, kicker);
  let y = BODY_TOP;
  if (intro) {
    s.addText(intro, { x: M, y, w: BODY_W, h: 0.34, fontFace: FONT, fontSize: 13, italic: true, color: C.muted });
    y += 0.5;
  }
  const n = nodes.length;
  const arrow = 0.3;
  const bw = (BODY_W - arrow * (n - 1)) / n;
  const bh = 1.55;
  nodes.forEach((nd, i) => {
    const x = M + i * (bw + arrow);
    const tone = nd.tone === 'primary' ? C.primary : nd.tone === 'accent' ? C.accent : null;
    s.addShape('roundRect', {
      x, y, w: bw, h: bh, rectRadius: 0.08,
      fill: { color: tone || C.soft }, line: { color: tone || C.line },
    });
    s.addText(nd.head, {
      x: x + 0.12, y: y + 0.14, w: bw - 0.24, h: 0.5,
      fontFace: FONT, fontSize: 14, bold: true, align: 'center',
      color: tone ? C.white : C.primary, valign: 'middle', lineSpacing: 18,
    });
    s.addText(nd.body, {
      x: x + 0.12, y: y + 0.64, w: bw - 0.24, h: bh - 0.78,
      fontFace: FONT, fontSize: 11, align: 'center',
      color: tone ? 'DCE9F5' : C.muted, valign: 'top', lineSpacing: 16,
    });
    if (i < n - 1) {
      s.addText('▶', {
        x: x + bw, y: y + bh / 2 - 0.18, w: arrow, h: 0.36,
        fontFace: FONT, fontSize: 14, color: C.muted, align: 'center', valign: 'middle',
      });
    }
  });
  if (legend && legend.length) {
    s.addText(legend.map((l) => ({
      text: l,
      options: { bullet: { code: '25AA' }, fontSize: 13, color: C.ink, breakLine: true, paraSpaceAfter: 8 },
    })), {
      x: M, y: y + bh + 0.3, w: BODY_W, h: (note ? H - 1.95 : H - 0.62) - (y + bh + 0.35),
      fontFace: FONT, valign: 'top', lineSpacing: 19,
    });
  }
  if (note) calloutAt(s, note, H - 1.95);
  footer(s, ctx.deck, ctx.page);
  notes(s, speaker);
  ctx.page += 1;
  return s;
}

// Slide hai cột "Việc của anh/chị" vs "Việc của CNTT".
function splitSlide(pptx, ctx, { title, kicker, left, right, note, speaker }) {
  const s = pptx.addSlide();
  header(s, title, kicker);
  const cw = (BODY_W - 0.36) / 2;
  const ch = note ? H - BODY_TOP - 2.05 : H - BODY_TOP - 0.8;
  [[left, M, C.ok], [right, M + cw + 0.36, C.primary]].forEach(([col, x, tone]) => {
    s.addShape('rect', { x, y: BODY_TOP, w: cw, h: ch, fill: { color: C.white }, line: { color: C.line } });
    s.addShape('rect', { x, y: BODY_TOP, w: cw, h: 0.5, fill: { color: tone }, line: { color: tone } });
    s.addText(col.head, {
      x: x + 0.18, y: BODY_TOP, w: cw - 0.36, h: 0.5,
      fontFace: FONT, fontSize: 15, bold: true, color: C.white, valign: 'middle',
    });
    s.addText(col.items.map((it) => ({
      text: it,
      options: { bullet: { code: '25AA' }, fontSize: 13.5, color: C.ink, breakLine: true, paraSpaceAfter: 9 },
    })), {
      x: x + 0.22, y: BODY_TOP + 0.66, w: cw - 0.44, h: ch - 0.82,
      fontFace: FONT, valign: 'top', lineSpacing: 19,
    });
  });
  if (note) calloutAt(s, note, H - 1.95);
  footer(s, ctx.deck, ctx.page);
  notes(s, speaker);
  ctx.page += 1;
  return s;
}

// Slide tình huống: "Gặp thông báo X → làm gì".
function caseSlide(pptx, ctx, { title, kicker, cases, note, speaker }) {
  const s = pptx.addSlide();
  header(s, title, kicker);
  const avail = (note ? H - 1.95 : H - 0.62) - BODY_TOP - 0.1;
  const gap = 0.14;
  const h = Math.min(1.25, (avail - gap * (cases.length - 1)) / cases.length);
  cases.forEach((c, i) => {
    const top = BODY_TOP + i * (h + gap);
    s.addShape('rect', { x: M, y: top, w: BODY_W, h, fill: { color: C.white }, line: { color: C.line } });
    s.addShape('rect', { x: M, y: top, w: 0.07, h, fill: { color: C.danger }, line: { color: C.danger } });
    s.addText(c.what, {
      x: M + 0.2, y: top, w: BODY_W * 0.4, h,
      fontFace: FONT, fontSize: 13, bold: true, color: C.danger, valign: 'middle', lineSpacing: 18,
    });
    s.addText(c.why, {
      x: M + BODY_W * 0.42, y: top, w: BODY_W * 0.24, h,
      fontFace: FONT, fontSize: 12, color: C.muted, valign: 'middle', lineSpacing: 17,
    });
    s.addText(c.fix, {
      x: M + BODY_W * 0.67, y: top, w: BODY_W * 0.32, h,
      fontFace: FONT, fontSize: 12.5, color: C.ink, valign: 'middle', lineSpacing: 17,
    });
  });
  if (note) calloutAt(s, note, H - 1.95);
  footer(s, ctx.deck, ctx.page);
  notes(s, speaker);
  ctx.page += 1;
  return s;
}

// Slide kết thúc.
function closingSlide(pptx, ctx, { title, points, contact }) {
  const s = pptx.addSlide();
  s.background = { color: C.primary };
  s.addText(title, {
    x: 0.9, y: 1.2, w: W - 1.8, h: 0.8,
    fontFace: FONT, fontSize: 32, bold: true, color: C.white, valign: 'middle',
  });
  s.addShape('line', { x: 0.9, y: 2.1, w: 3.2, h: 0, line: { color: C.accent, width: 3 } });
  s.addText(points.map((p) => ({
    text: p,
    options: { bullet: { code: '25AA' }, fontSize: 16, color: 'DCE9F5', breakLine: true, paraSpaceAfter: 12 },
  })), {
    x: 0.9, y: 2.45, w: W - 1.8, h: 3.0,
    fontFace: FONT, valign: 'top', lineSpacing: 24,
  });
  s.addText(contact, {
    x: 0.9, y: 5.9, w: W - 1.8, h: 0.9,
    fontFace: FONT, fontSize: 13, color: 'A9C4DC', lineSpacing: 20,
  });
  ctx.page += 1;
  return s;
}

function newDeck(PptxGenJS, { title, subject, deck }) {
  const pptx = new PptxGenJS();
  // LAYOUT_WIDE = 13.333 x 7.5 inch. KHÔNG dùng LAYOUT_16x9: nó là 10 x 5.625 inch,
  // mọi toạ độ trong tệp này sẽ tràn ra ngoài mép slide.
  pptx.layout = 'LAYOUT_WIDE';
  pptx.author = 'Phòng Công nghệ thông tin';
  pptx.company = 'Phần mềm quản lý bệnh viện qlbv';
  pptx.title = title;
  pptx.subject = subject;
  // page bắt đầu từ 2 vì slide 1 luôn là trang bìa (không đánh số).
  return { pptx, ctx: { deck, page: 2 } };
}

module.exports = {
  FONT, C, W, H, M, BODY_W, BODY_TOP,
  newDeck, titleSlide, sectionSlide, bulletSlide, stepSlide, tableSlide,
  shotSlide, cardSlide, flowSlide, splitSlide, caseSlide, closingSlide,
};
