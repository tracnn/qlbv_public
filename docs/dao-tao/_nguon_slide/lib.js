// Hàm dựng slide dùng chung cho ba bộ slide đào tạo.
// Mọi kích thước tính bằng inch trên khổ 16:9 (13.333 x 7.5).

const FONT = 'Arial';

// Hai bộ giao diện. newDeck() chọn một bộ và chép đè vào C, nên mọi hàm dựng (kể cả hàm
// riêng trong deck-d.js đọc L.C) tự đổi màu theo deck đang dựng. build.js dựng tuần tự
// từng deck, nên chép đè như vậy là an toàn.
//   classic — giao diện gốc, deck D (giao ban) vẫn dùng.
//   tgd     — theo tệp "Bài Trình Bày Nội Dung.pdf" (Giải pháp Tiền giám định): nền navy,
//             nhãn chương "01 / …", chân trang "GIẢI PHÁP TIỀN GIÁM ĐỊNH · nn / NN".
const THEMES = {
  classic: {
    primary: '1F4E79',   // xanh đậm — dải đầu slide, đầu bảng
    title: '1F4E79',     // chữ tiêu đề
    navy: '1F4E79',      // nền trang bìa / trang kết
    accent: '0E7C7B',    // xanh ngọc — nhấn phụ
    danger: 'C62828',    // đỏ — cảnh báo, lỗi chặn
    warn: 'B26A00',      // cam đậm — lưu ý
    ok: '2E7D32',        // xanh lá — trạng thái tốt
    ink: '1A1A1A',       // chữ chính
    muted: '5A6472',     // chữ phụ
    line: 'D3DAE3',      // đường kẻ
    soft: 'F2F6FA',      // nền khối nhạt
    softAccent: 'E3F0F0',
    softWarn: 'FFF6E5',
    softDanger: 'FDECEC',
    softOk: 'EAF5EC',
    white: 'FFFFFF',
  },
  tgd: {
    primary: '1A5EA8',
    title: '0B1F3A',
    navy: '0B1F3A',
    accent: '2A9FD8',
    danger: 'C62828',
    warn: 'C96A12',
    ok: '2E7D32',
    ink: '14233A',
    muted: '5B6B7F',
    line: 'D6E2EE',
    soft: 'EEF3F8',
    softAccent: 'E6F1FA',
    softWarn: 'FFF4E6',
    softDanger: 'FDECEC',
    softOk: 'EAF5EC',
    white: 'FFFFFF',
  },
};
const C = Object.assign({}, THEMES.classic);
let THEME = 'classic';
// Chân trang theme tgd cần tổng số slide nên được ghi nhận rồi vẽ ở finishDeck().
let pendingFooters = [];

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
// ctx (tuỳ chọn): ở theme tgd, nếu ctx.chap có giá trị thì nhãn thành "03 / MỤC 2.4".
function header(slide, title, kicker, ctx) {
  const tgd = THEME === 'tgd';
  if (!tgd) slide.addShape('rect', { x: 0, y: 0, w: W, h: 0.09, fill: { color: C.primary } });
  const label = tgd && kicker && ctx && ctx.chap ? `${ctx.chap} / ${kicker}` : kicker;
  if (label) {
    slide.addText(label.toUpperCase(), {
      x: M, y: 0.32, w: BODY_W, h: 0.26,
      fontFace: FONT, fontSize: 11, bold: true, color: tgd ? C.primary : C.accent, charSpacing: 1,
    });
    slide.addText(title, {
      x: M, y: 0.6, w: BODY_W, h: 0.62,
      fontFace: FONT, fontSize: 26, bold: true, color: C.title, valign: 'top',
    });
  } else {
    slide.addText(title, {
      x: M, y: 0.42, w: BODY_W, h: 0.7,
      fontFace: FONT, fontSize: 26, bold: true, color: C.title, valign: 'middle',
    });
  }
  if (!tgd) {
    slide.addShape('line', {
      x: M, y: BODY_TOP - 0.16, w: BODY_W, h: 0,
      line: { color: C.line, width: 1 },
    });
  }
}

// Số trang + tên deck ở chân slide.
function footer(slide, deckName, pageNo) {
  if (THEME === 'tgd') { pendingFooters.push({ slide, deckName, pageNo }); return; }
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
function titleSlide(pptx, opts) {
  if (THEME === 'tgd') return titleSlideTgd(pptx, opts);
  const { title, subtitle, audience, meta } = opts;
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

// Bìa theme tgd: nền navy, vạch dọc bên trái, ba ô thông tin, một khối thông điệp.
// kicker, cells: [{ label, value }], message, question — đều tuỳ chọn.
function titleSlideTgd(pptx, { kicker, title, subtitle, cells, message, question, meta }) {
  const s = pptx.addSlide();
  s.background = { color: C.navy };
  s.addShape('rect', { x: 0, y: 0, w: 0.16, h: H, fill: { color: C.accent }, line: { color: C.accent } });
  const x = 0.72;
  if (kicker) {
    s.addText(kicker.toUpperCase(), {
      x, y: 0.5, w: W - 1.5, h: 0.3, fontFace: FONT, fontSize: 11, bold: true, color: '8FC8EC', charSpacing: 1,
    });
  }
  s.addText(title, {
    x, y: 0.95, w: W - 2.5, h: 1.45, fontFace: FONT, fontSize: 38, bold: true, color: C.white, valign: 'bottom',
  });
  if (subtitle) {
    s.addText(subtitle, {
      x, y: 2.45, w: W - 3, h: 0.75, fontFace: FONT, fontSize: 18, color: 'C9DDF0', valign: 'top', lineSpacing: 24,
    });
  }
  let y = 3.55;
  if (cells && cells.length) {
    const cw = 3.35;
    cells.forEach((c, i) => {
      const cx = x + i * (cw + 0.2);
      s.addShape('roundRect', {
        x: cx, y, w: cw, h: 1.1, rectRadius: 0.06, fill: { color: '17385F' }, line: { color: '2B5580' },
      });
      s.addText(c.label.toUpperCase(), {
        x: cx + 0.16, y: y + 0.1, w: cw - 0.3, h: 0.3, fontFace: FONT, fontSize: 9, bold: true, color: '8FC8EC',
      });
      s.addText(c.value, {
        x: cx + 0.16, y: y + 0.36, w: cw - 0.3, h: 0.66, fontFace: FONT, fontSize: 13, bold: true, color: C.white, valign: 'middle', lineSpacing: 17,
      });
    });
    y += 1.4;
  }
  if (message) {
    s.addShape('roundRect', {
      x, y, w: 3 * 3.35 + 0.4, h: 0.95, rectRadius: 0.06, fill: { color: '14365C' }, line: { color: '2B5580' },
    });
    s.addText(message, {
      x: x + 0.22, y, w: 3 * 3.35, h: 0.95, fontFace: FONT, fontSize: 15, bold: true, color: C.white, valign: 'middle', lineSpacing: 21,
    });
    y += 1.15;
  }
  if (question) {
    s.addText(question, { x, y, w: 9, h: 0.5, fontFace: FONT, fontSize: 13, color: '8FC8EC', valign: 'top' });
  }
  if (meta) {
    s.addText(meta, { x, y: H - 0.55, w: W - 1.5, h: 0.3, fontFace: FONT, fontSize: 10, bold: true, color: 'A9C4DC' });
  }
  return s;
}

// Slide phân chương. Ghi số chương vào ctx.chap để nhãn các slide sau thành "0n / …".
function sectionSlide(pptx, ctx, { no, title, sub }) {
  ctx.chap = String(no).padStart(2, '0');
  if (THEME === 'tgd') {
    const s = pptx.addSlide();
    s.background = { color: C.navy };
    s.addShape('rect', { x: 0, y: 0, w: 0.16, h: H, fill: { color: C.accent }, line: { color: C.accent } });
    s.addText(ctx.chap, {
      x: 0.9, y: 2.2, w: 2.0, h: 1.5, fontFace: FONT, fontSize: 80, bold: true, color: C.accent,
    });
    s.addText(title, {
      x: 2.9, y: 2.35, w: W - 3.7, h: 1.0, fontFace: FONT, fontSize: 32, bold: true, color: C.white, valign: 'middle',
    });
    if (sub) {
      s.addText(sub, {
        x: 2.9, y: 3.4, w: W - 3.7, h: 0.9, fontFace: FONT, fontSize: 15, color: 'A9C4DC', lineSpacing: 22, valign: 'top',
      });
    }
    ctx.page += 1;
    return s;
  }
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
  header(s, title, kicker, ctx);
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
  header(s, title, kicker, ctx);
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
  header(s, title, kicker, ctx);
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
          // Chỉ gắn align khi ô khai báo: gắn mặc định sẽ đổi XML của deck D dù nhìn như cũ.
          ...(o.align ? { align: o.align } : {}),
          fill: { color: o.fill || (i % 2 ? C.white : C.soft) },
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

// Ảnh chụp màn hình thật cho từng số [Ảnh n], nằm ở docs/dao-tao/anh/. Có tệp thì shotSlide
// nhúng ảnh; chưa có thì vẽ khung chờ. Tên tệp khớp DANH-SACH-ANH-CAN-CHUP.md.
const SHOT_FILES = {
  1: 'anh-01-danh-sach-vi-pham.png',
  2: 'anh-02-tra-cuu-loi-ho-so.png',
  3: 'anh-03-danh-sach-ho-so-xml3176.png',
  4: 'anh-04-ctdt-so-loi-bang-0.png',
  5: 'anh-05-ket-qua-mcct.png',
  6: 'anh-06-nut-tra-tien-cung-chi-tra.png',
};
const SHOT_DIR = require('path').resolve(__dirname, '..', 'anh');

// Kích thước điểm ảnh của tệp PNG, đọc từ khối IHDR (byte 16–23).
function pngSize(file) {
  const b = require('fs').readFileSync(file);
  return { w: b.readUInt32BE(16), h: b.readUInt32BE(20) };
}

function shotFile(shot) {
  const f = SHOT_FILES[shot] && require('path').join(SHOT_DIR, SHOT_FILES[shot]);
  return f && require('fs').existsSync(f) ? f : null;
}

// Slide khung chờ ảnh chụp màn hình. Ghi số ảnh để khớp với DANH-SACH-ANH-CAN-CHUP.md
function shotSlide(pptx, ctx, { title, kicker, shot, caption, points, note, speaker }) {
  const s = pptx.addSlide();
  header(s, title, kicker, ctx);
  const hasPoints = points && points.length;
  const boxH = (note ? H - 1.95 : H - 0.7) - BODY_TOP - 0.1;
  const img = shotFile(shot);
  if (img) {
    // Ảnh thật: vừa khít chiều cao vùng nội dung, không rộng quá 66% khi có cột ghi chú.
    const px = pngSize(img);
    const ratio = px.w / px.h;
    if (hasPoints && ratio >= 2.4) {
      // Ảnh bẹt: trải hết chiều ngang, ghi chú xuống dưới thành hai cột.
      let iw = BODY_W;
      let ih = iw / ratio;
      const maxH = boxH * 0.64;
      if (ih > maxH) { ih = maxH; iw = ih * ratio; }
      s.addImage({ path: img, x: M, y: BODY_TOP, w: iw, h: ih });
      s.addShape('rect', { x: M, y: BODY_TOP, w: iw, h: ih, fill: { type: 'none' }, line: { color: C.line, width: 1 } });
      const py = BODY_TOP + ih + 0.25;
      const half = Math.ceil(points.length / 2);
      const colW = (BODY_W - 0.4) / 2;
      [points.slice(0, half), points.slice(half)].forEach((col, k) => {
        s.addText(col.map((p) => ({
          text: p,
          options: { bullet: { code: '25AA' }, fontSize: 13, color: C.ink, breakLine: true, paraSpaceAfter: 7 },
        })), {
          x: M + k * (colW + 0.4), y: py, w: colW, h: BODY_TOP + boxH - py,
          fontFace: FONT, valign: 'top', lineSpacing: 18,
        });
      });
      if (note) calloutAt(s, note, H - 1.95);
      footer(s, ctx.deck, ctx.page);
      notes(s, speaker);
      ctx.page += 1;
      return s;
    }
    const maxW = hasPoints ? BODY_W * 0.66 : BODY_W;
    let h = boxH;
    let w = h * (px.w / px.h);
    if (w > maxW) { w = maxW; h = w * (px.h / px.w); }
    s.addImage({ path: img, x: M, y: BODY_TOP, w, h });
    s.addShape('rect', { x: M, y: BODY_TOP, w, h, fill: { type: 'none' }, line: { color: C.line, width: 1 } });
    if (hasPoints) {
      s.addText(points.map((p) => ({
        text: p,
        options: { bullet: { code: '25AA' }, fontSize: 13, color: C.ink, breakLine: true, paraSpaceAfter: 9 },
      })), {
        x: M + w + 0.3, y: BODY_TOP, w: BODY_W - w - 0.3, h: boxH,
        fontFace: FONT, valign: 'top', lineSpacing: 19,
      });
    }
    if (note) calloutAt(s, note, H - 1.95);
    footer(s, ctx.deck, ctx.page);
    notes(s, speaker);
    ctx.page += 1;
    return s;
  }
  const boxW = hasPoints ? BODY_W * 0.6 : BODY_W;
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
  header(s, title, kicker, ctx);
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
  header(s, title, kicker, ctx);
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
  header(s, title, kicker, ctx);
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
  header(s, title, kicker, ctx);
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
  s.background = { color: C.navy };
  if (THEME === 'tgd') s.addShape('rect', { x: 0, y: 0, w: 0.16, h: H, fill: { color: C.accent }, line: { color: C.accent } });
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

function newDeck(PptxGenJS, { title, subject, deck, theme }) {
  THEME = theme === 'tgd' ? 'tgd' : 'classic';
  Object.assign(C, THEMES[THEME]);
  pendingFooters = [];
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

// Gọi một lần sau slide cuối. Theme tgd: vẽ chân trang "TÊN DECK · nn / NN" cho mọi slide
// đã ghi nhận. Theme classic: chân trang đã vẽ ngay, hàm không làm gì.
function finishDeck(pptx) {
  const total = String(pptx.slides.length).padStart(2, '0');
  pendingFooters.forEach(({ slide, deckName, pageNo }) => {
    slide.addShape('line', { x: M, y: H - 0.52, w: BODY_W, h: 0, line: { color: C.line, width: 0.75 } });
    slide.addText(deckName.toUpperCase(), {
      x: M, y: H - 0.46, w: BODY_W - 1.5, h: 0.28,
      fontFace: FONT, fontSize: 8.5, bold: true, color: C.muted, charSpacing: 0.5,
    });
    slide.addText(`${String(pageNo).padStart(2, '0')} / ${total}`, {
      x: W - M - 1.5, y: H - 0.46, w: 1.5, h: 0.28,
      fontFace: FONT, fontSize: 8.5, bold: true, color: C.muted, align: 'right',
    });
  });
  pendingFooters = [];
}

module.exports = {
  FONT, C, W, H, M, BODY_W, BODY_TOP,
  header, footer, notes, calloutAt, finishDeck,
  newDeck, titleSlide, sectionSlide, bulletSlide, stepSlide, tableSlide,
  shotSlide, cardSlide, flowSlide, splitSlide, caseSlide, closingSlide,
};
