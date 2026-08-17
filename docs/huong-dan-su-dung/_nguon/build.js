const fs = require('fs');
const path = require('path');
const {
  Document, Packer, Paragraph, TextRun, LevelFormat, AlignmentType, Footer, Header,
  PageNumber, convertInchesToTwip, BorderStyle,
} = require('docx');

const { FONT } = require('./lib');
const { cover, toc, chapter0 } = require('./front');
const part1 = require('./part1');
const part2 = require('./part2');
const part3 = require('./part3');
const part4 = require('./part4');
const part5 = require('./part5');
const appendix = require('./appendix');

const children = [
  ...toc(),
  ...chapter0(),
  ...part1(),
  ...part2(),
  ...part3(),
  ...part4(),
  ...part5(),
  ...appendix(),
];

const doc = new Document({
  creator: 'Phòng Công nghệ thông tin',
  title: 'Tài liệu hướng dẫn sử dụng — XML 3176, Kiểm tra sai sót y lệnh, Thẻ BHYT, Quản lý danh mục',
  description: 'Tài liệu hướng dẫn sử dụng dành cho người dùng nghiệp vụ',
  styles: {
    default: {
      document: {
        run: { font: FONT, size: 26 },
        paragraph: { spacing: { line: 300 } },
      },
    },
  },
  numbering: {
    config: [
      {
        reference: 'bullets',
        levels: [
          { level: 0, format: LevelFormat.BULLET, text: '•', alignment: AlignmentType.LEFT,
            style: { paragraph: { indent: { left: 520, hanging: 260 } } } },
          { level: 1, format: LevelFormat.BULLET, text: '◦', alignment: AlignmentType.LEFT,
            style: { paragraph: { indent: { left: 980, hanging: 260 } } } },
        ],
      },
      {
        reference: 'steps',
        levels: [
          { level: 0, format: LevelFormat.DECIMAL, text: '%1.', alignment: AlignmentType.LEFT,
            style: { paragraph: { indent: { left: 560, hanging: 300 } } } },
        ],
      },
    ],
  },
  sections: [
    {
      properties: {
        page: {
          margin: {
            top: convertInchesToTwip(1),
            bottom: convertInchesToTwip(1),
            left: convertInchesToTwip(1),
            right: convertInchesToTwip(1),
          },
        },
      },
      children: cover(),
    },
    {
      properties: {
        page: {
          margin: {
            top: convertInchesToTwip(0.9),
            bottom: convertInchesToTwip(0.9),
            left: convertInchesToTwip(1),
            right: convertInchesToTwip(1),
          },
        },
      },
      headers: {
        default: new Header({
          children: [
            new Paragraph({
              alignment: AlignmentType.RIGHT,
              border: { bottom: { style: BorderStyle.SINGLE, size: 4, color: 'B4C6E7' } },
              children: [
                new TextRun({
                  text: 'Hướng dẫn sử dụng — XML 3176 · Kiểm tra y lệnh · Thẻ BHYT · Danh mục',
                  font: FONT, size: 18, color: '808080',
                }),
              ],
            }),
          ],
        }),
      },
      footers: {
        default: new Footer({
          children: [
            new Paragraph({
              alignment: AlignmentType.CENTER,
              children: [
                new TextRun({ text: 'Trang ', font: FONT, size: 20, color: '808080' }),
                new TextRun({ children: [PageNumber.CURRENT], font: FONT, size: 20, color: '808080' }),
                new TextRun({ text: ' / ', font: FONT, size: 20, color: '808080' }),
                new TextRun({ children: [PageNumber.TOTAL_PAGES], font: FONT, size: 20, color: '808080' }),
              ],
            }),
          ],
        }),
      },
      children,
    },
  ],
});

const out = process.argv[2] || path.join(__dirname, 'out.docx');
Packer.toBuffer(doc).then((buf) => {
  fs.mkdirSync(path.dirname(out), { recursive: true });
  fs.writeFileSync(out, buf);
  console.log('OK ->', out, (buf.length / 1024).toFixed(1) + ' KB');
});
