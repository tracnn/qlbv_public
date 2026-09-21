// Dựng bốn bộ slide đào tạo ra tệp .pptx.
//
//   node build.js            -> dựng cả bốn deck vào ../
//   node build.js a          -> chỉ dựng deck A
//   node build.js b c        -> dựng deck B và C
//
// Yêu cầu: pptxgenjs cài tạm ở thư mục gốc dự án, xem README.md.

const path = require('path');

let PptxGenJS;
try {
  PptxGenJS = require('pptxgenjs');
} catch (e) {
  console.error('Chưa cài pptxgenjs. Ở thư mục gốc dự án chạy:');
  console.error('  npm install pptxgenjs --no-save --no-package-lock');
  process.exit(1);
}

const DECKS = {
  a: { build: require('./deck-a'), file: 'Slide-dao-tao-A-Khoa-lam-sang.pptx' },
  b: { build: require('./deck-b'), file: 'Slide-dao-tao-B-Phong-ban-nghiep-vu.pptx' },
  c: { build: require('./deck-c'), file: 'Slide-dao-tao-C-Tiep-don-Vien-phi.pptx' },
  d: { build: require('./deck-d'), file: 'Slide-dao-tao-D-Bao-cao-giao-ban.pptx' },
};

const OUT_DIR = path.resolve(__dirname, '..');

async function main() {
  const args = process.argv.slice(2).map((s) => s.toLowerCase());
  const keys = args.length ? args : Object.keys(DECKS);

  for (const k of keys) {
    const deck = DECKS[k];
    if (!deck) {
      console.error(`Không có deck "${k}". Chọn một trong: ${Object.keys(DECKS).join(', ')}`);
      process.exitCode = 1;
      continue;
    }
    const pptx = deck.build(PptxGenJS);
    const out = path.join(OUT_DIR, deck.file);
    await pptx.writeFile({ fileName: out });
    console.log(`Đã dựng: ${out}  (${pptx.slides.length} slide)`);
  }
}

main().catch((err) => {
  console.error(err);
  process.exit(1);
});
