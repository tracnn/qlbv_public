const {
  Paragraph, TextRun, HeadingLevel, Table, TableRow, TableCell, WidthType,
  ShadingType, AlignmentType, BorderStyle, PageBreak,
} = require('docx');

const CONTENT_W = 9020; // A4 (11906) - 2*1440 margins, làm tròn

const FONT = 'Times New Roman';

function run(text, opts = {}) {
  return new TextRun({ text: String(text), font: FONT, size: opts.size || 26, bold: !!opts.bold, italics: !!opts.italics, color: opts.color });
}

function h1(text, { pageBreak = true } = {}) {
  return new Paragraph({
    heading: HeadingLevel.HEADING_1,
    pageBreakBefore: pageBreak,
    spacing: { before: 240, after: 200 },
    children: [new TextRun({ text, font: FONT, size: 34, bold: true, color: '1F3864' })],
  });
}

function h2(text) {
  return new Paragraph({
    heading: HeadingLevel.HEADING_2,
    spacing: { before: 280, after: 140 },
    children: [new TextRun({ text, font: FONT, size: 28, bold: true, color: '2E5496' })],
  });
}

function h3(text) {
  return new Paragraph({
    heading: HeadingLevel.HEADING_3,
    spacing: { before: 220, after: 110 },
    children: [new TextRun({ text, font: FONT, size: 26, bold: true, color: '333333' })],
  });
}

function p(text, opts = {}) {
  const children = Array.isArray(text) ? text : [run(text, opts)];
  return new Paragraph({
    spacing: { after: opts.after === undefined ? 120 : opts.after, line: 300 },
    alignment: opts.align,
    indent: opts.indent,
    children,
  });
}

function bullet(text, level = 0) {
  return new Paragraph({
    numbering: { reference: 'bullets', level },
    spacing: { after: 60, line: 300 },
    children: Array.isArray(text) ? text : [run(text)],
  });
}

function num(text, level = 0) {
  return new Paragraph({
    numbering: { reference: 'steps', level },
    spacing: { after: 60, line: 300 },
    children: Array.isArray(text) ? text : [run(text)],
  });
}

function note(label, text) {
  return new Paragraph({
    spacing: { before: 120, after: 160, line: 300 },
    shading: { type: ShadingType.CLEAR, fill: 'FFF2CC' },
    border: {
      top: { style: BorderStyle.SINGLE, size: 4, color: 'E0B000' },
      bottom: { style: BorderStyle.SINGLE, size: 4, color: 'E0B000' },
      left: { style: BorderStyle.SINGLE, size: 12, color: 'E0B000' },
      right: { style: BorderStyle.SINGLE, size: 4, color: 'E0B000' },
    },
    indent: { left: 120, right: 120 },
    children: [run(label + ' ', { bold: true }), run(text)],
  });
}

function forIt(text) {
  return new Paragraph({
    spacing: { before: 60, after: 160, line: 300 },
    shading: { type: ShadingType.CLEAR, fill: 'E7EEF7' },
    indent: { left: 120, right: 120 },
    children: [run('Dành cho bộ phận CNTT. ', { bold: true, italics: true }), run(text, { italics: true })],
  });
}

function cell(content, { widthDxa, bold = false, fill, align, size = 24 } = {}) {
  const paras = (Array.isArray(content) ? content : [content]).map((t) =>
    t instanceof Paragraph
      ? t
      : new Paragraph({
          spacing: { before: 40, after: 40, line: 260 },
          alignment: align,
          children: [run(t, { bold, size })],
        }),
  );
  return new TableCell({
    width: { size: widthDxa, type: WidthType.DXA },
    shading: fill ? { type: ShadingType.CLEAR, fill } : undefined,
    margins: { top: 60, bottom: 60, left: 80, right: 80 },
    children: paras,
  });
}

/**
 * table(headers, rows, widths)
 *  - headers: string[]
 *  - rows: (string|string[])[][]
 *  - widths: number[] (dxa, tổng = CONTENT_W)
 */
function table(headers, rows, widths) {
  const total = widths.reduce((a, b) => a + b, 0);
  if (total !== CONTENT_W) {
    const k = CONTENT_W / total;
    widths = widths.map((w) => Math.round(w * k));
    const diff = CONTENT_W - widths.reduce((a, b) => a + b, 0);
    widths[widths.length - 1] += diff;
  }
  const headerRow = new TableRow({
    tableHeader: true,
    cantSplit: true,
    children: headers.map((hh, i) => cell(hh, { widthDxa: widths[i], bold: true, fill: 'D9E2F3', align: AlignmentType.CENTER })),
  });
  const bodyRows = rows.map(
    (r) =>
      new TableRow({
        cantSplit: true,
        children: r.map((c, i) => cell(c, { widthDxa: widths[i] })),
      }),
  );
  return new Table({
    columnWidths: widths,
    width: { size: CONTENT_W, type: WidthType.DXA },
    rows: [headerRow, ...bodyRows],
    borders: {
      top: { style: BorderStyle.SINGLE, size: 4, color: '8EA9DB' },
      bottom: { style: BorderStyle.SINGLE, size: 4, color: '8EA9DB' },
      left: { style: BorderStyle.SINGLE, size: 4, color: '8EA9DB' },
      right: { style: BorderStyle.SINGLE, size: 4, color: '8EA9DB' },
      insideHorizontal: { style: BorderStyle.SINGLE, size: 2, color: 'B4C6E7' },
      insideVertical: { style: BorderStyle.SINGLE, size: 2, color: 'B4C6E7' },
    },
  });
}

// Bảng thao tác 3 cột: Bước | Thao tác | Kết quả mong đợi
function steps(rows) {
  return table(['Bước', 'Thao tác của người dùng', 'Kết quả mong đợi'], rows, [760, 4330, 3930]);
}

// Bảng lỗi 3 cột
function errors(rows) {
  return table(['Thông báo trên màn hình', 'Nguyên nhân', 'Cách xử lý'], rows, [2900, 3060, 3060]);
}

function spacer() {
  return new Paragraph({ spacing: { after: 140 }, children: [] });
}

function pageBreak() {
  return new Paragraph({ children: [new PageBreak()] });
}

module.exports = { CONTENT_W, FONT, run, h1, h2, h3, p, bullet, num, note, forIt, table, steps, errors, spacer, pageBreak, cell };
