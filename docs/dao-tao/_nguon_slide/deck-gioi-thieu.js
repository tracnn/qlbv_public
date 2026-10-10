// DECK GIỚI THIỆU — giới thiệu hệ thống Tiền giám định cho các bệnh viện khác: lãnh đạo bệnh
// viện, lãnh đạo phòng KHTH và nhân viên nghiệp vụ. Khoảng 15 slide, 20–30 phút.
//
// Giao diện theo màn mở đầu của video "Demo công cụ Tiếp nhận XML3176 phục vụ cho DRG": nền
// navy chuyển sắc, nhãn viên thuốc, chữ nhấn xanh và vàng, thẻ trong mờ nối mũi tên. Toàn bộ
// deck nền tối. Nền chuyển sắc là ảnh PNG sinh ngay lúc dựng (pptxgenjs không có gradient
// fill), nên không cần tệp ảnh nền đi kèm. Ảnh chụp màn hình lấy từ ../anh/ — đều đã che bằng
// dữ liệu mẫu.
//
// Nguồn số liệu (chỉ dùng số có trong mã hoặc tài liệu, không có số đo kết quả kiểu "giảm X%"):
//   - 60 giây: config('order_check.scan_sleep_interval', 60) — HISProKiemTraYLenh.php
//   - 19 quy tắc y lệnh: các migration *seed_order_check*
//   - ~300 mã lỗi XML 3176: đếm mã riêng biệt trong app/Services/Xml3176*Checker.php
//   - 12 bảng XML có bộ kiểm riêng + Xml3176CompleteChecker kiểm chéo toàn hồ sơ
//   - 100 MB, thử lại 3 lần: BHYTXml3176Controller.php, SubmitXml3176Job.php ($tries = 3)
//   - 11 bộ danh mục BHYT: config/danh_muc_bhyt.php
//   - Bốn biểu đồ dashboard: docs/huong-dan-su-dung/_nguon/part1.js mục 1.7.3
//   - Lộ trình, no-blame: docs/tai-lieu-tong-hop-xml3176-order-check.md mục 10
//   - Giới hạn: deck-tgd.js slide "Giới hạn"

const fs = require('fs');
const path = require('path');
const zlib = require('zlib');
const L = require('./lib');

const { W, H, FONT } = L;

const NEN_TOI = [11, 26, 48];     // 0B1A30 — góc dưới phải
const NEN_SANG = [22, 52, 92];    // 16345C — quầng sáng phía trên bên trái
const XANH = '38BDF8';            // chữ nhấn xanh, như "3176" trong video
const VANG = 'FBBF24';            // dòng thông điệp + viền thẻ nhấn
const DO = 'F87171';
const XANH_LA = '4ADE80';
const TRANG = 'FFFFFF';
const CHU_PHU = 'A9BCD6';
const CHU_MO = '7F93B0';
const THE_NEN = '1C3558';
const THE_VIEN = '2E4B74';
const EMOJI = 'Segoe UI Emoji';

const M = 0.7;
const BODY_W = W - M * 2;
const ANH = path.resolve(__dirname, '..', 'anh');
// Đơn vị đứng tên trình bày (bìa, slide kết). Bạch Mai là khách hàng tham chiếu.
const DON_VI = 'Công ty Cổ phần Y tế Thông minh';

// ---------------------------------------------------------------- ảnh nền chuyển sắc

function crc32(buf) {
  let c = ~0;
  for (let i = 0; i < buf.length; i++) {
    c ^= buf[i];
    for (let k = 0; k < 8; k++) c = (c >>> 1) ^ (0xEDB88320 & -(c & 1));
  }
  return ~c >>> 0;
}

function chunk(type, data) {
  const len = Buffer.alloc(4);
  len.writeUInt32BE(data.length);
  const td = Buffer.concat([Buffer.from(type), data]);
  const crc = Buffer.alloc(4);
  crc.writeUInt32BE(crc32(td));
  return Buffer.concat([len, td, crc]);
}

// Quầng sáng hình elip tâm ở 1/3 chiều ngang, 1/4 chiều dọc, nhạt dần ra mép.
function nenChuyenSac(w = 1600, h = 900) {
  const raw = Buffer.alloc((w * 3 + 1) * h);
  for (let y = 0; y < h; y++) {
    const row = y * (w * 3 + 1);
    raw[row] = 0;
    for (let x = 0; x < w; x++) {
      const dx = (x - w * 0.32) / (w * 0.85);
      const dy = (y - h * 0.22) / (h * 0.95);
      const t = Math.min(1, Math.sqrt(dx * dx + dy * dy));
      const k = 1 - t * t * (3 - 2 * t);
      for (let ch = 0; ch < 3; ch++) {
        raw[row + 1 + x * 3 + ch] = Math.round(NEN_TOI[ch] + (NEN_SANG[ch] - NEN_TOI[ch]) * k);
      }
    }
  }
  const ihdr = Buffer.alloc(13);
  ihdr.writeUInt32BE(w, 0);
  ihdr.writeUInt32BE(h, 4);
  ihdr[8] = 8; ihdr[9] = 2; ihdr[10] = 0; ihdr[11] = 0; ihdr[12] = 0;
  const png = Buffer.concat([
    Buffer.from([0x89, 0x50, 0x4E, 0x47, 0x0D, 0x0A, 0x1A, 0x0A]),
    chunk('IHDR', ihdr),
    chunk('IDAT', zlib.deflateSync(raw, { level: 9 })),
    chunk('IEND', Buffer.alloc(0)),
  ]);
  return 'image/png;base64,' + png.toString('base64');
}

let NEN = null;

// ---------------------------------------------------------------- tiện ích dựng

function chu(s, text, opts) {
  s.addText(text, { fontFace: FONT, margin: 0, isTextBox: true, ...opts });
}

function the(s, x, y, w, h, { nhan = false, mo = false } = {}) {
  s.addShape('roundRect', {
    x, y, w, h, rectRadius: 0.1,
    fill: { color: nhan ? '25324A' : THE_NEN, transparency: mo ? 55 : 15 },
    line: { color: nhan ? VANG : THE_VIEN, width: nhan ? 1.5 : 0.75, dashType: mo ? 'dash' : 'solid' },
  });
}

function bieuTuong(s, icon, x, y, w, h, size = 26) {
  chu(s, icon, { x, y, w, h, fontFace: EMOJI, fontSize: size, align: 'center', valign: 'middle' });
}

function slideMoi(pptx, ctx) {
  const s = pptx.addSlide();
  if (!NEN) NEN = nenChuyenSac();
  s.background = { data: NEN };
  ctx.slides.push(s);
  return s;
}

// Chân trang "nn / NN" cần tổng số slide nên vẽ sau cùng, bỏ qua slide bìa.
function chanTrang(ctx) {
  const tong = ctx.slides.length;
  ctx.slides.forEach((s, i) => {
    if (i === 0) return;
    chu(s, `GIẢI PHÁP TIỀN GIÁM ĐỊNH  ·  ${String(i + 1).padStart(2, '0')} / ${tong}`, {
      x: W - M - 5, y: H - 0.48, w: 5, h: 0.28, fontSize: 9.5, color: CHU_MO, align: 'right', charSpacing: 1,
    });
  });
}

function dau(s, kicker, title, phu) {
  chu(s, kicker.toUpperCase(), { x: M, y: 0.45, w: BODY_W, h: 0.3, fontSize: 12, bold: true, color: XANH, charSpacing: 2 });
  chu(s, title, { x: M, y: 0.78, w: BODY_W, h: 0.62, fontSize: 28, bold: true, color: TRANG, valign: 'middle' });
  if (phu) chu(s, phu, { x: M, y: 1.42, w: BODY_W, h: 0.36, fontSize: 15, color: CHU_PHU, valign: 'middle' });
}

function dongVang(s, text, y = 6.3) {
  chu(s, text, { x: M, y, w: BODY_W, h: 0.42, fontSize: 16, color: VANG, align: 'center', valign: 'middle' });
}

function kichThuocPng(file) {
  const b = fs.readFileSync(file);
  return { w: b.readUInt32BE(16), h: b.readUInt32BE(20) };
}

// Ảnh co vừa khung (giữ tỉ lệ), căn giữa theo chiều ngang, sát trên; có viền mảnh.
function anhChup(s, tep, x, y, w, h) {
  const file = path.join(ANH, tep);
  const k = kichThuocPng(file);
  let iw = w;
  let ih = (w * k.h) / k.w;
  if (ih > h) { ih = h; iw = (h * k.w) / k.h; }
  const ix = x + (w - iw) / 2;
  s.addShape('rect', { x: ix - 0.04, y: y - 0.04, w: iw + 0.08, h: ih + 0.08, fill: { color: THE_VIEN }, line: { color: '4A6890', width: 0.75 } });
  s.addImage({ path: file, x: ix, y, w: iw, h: ih });
  return { x: ix, y, w: iw, h: ih };
}

// ---------------------------------------------------------------- 01 bìa

function biaSlide(pptx, ctx, { pill, ten, mo, luong, tieuDeLuong, chuan, speaker }) {
  const s = slideMoi(pptx, ctx);

  const pw = 7.2;
  s.addShape('roundRect', {
    x: (W - pw) / 2, y: 0.62, w: pw, h: 0.42, rectRadius: 0.21,
    fill: { color: 'FFFFFF', transparency: 94 }, line: { color: '4A6890', width: 0.75 },
  });
  chu(s, pill, { x: (W - pw) / 2, y: 0.62, w: pw, h: 0.42, fontSize: 12.5, color: 'C9D6E8', align: 'center', valign: 'middle' });

  chu(s, ten.map(([text, mau]) => ({ text, options: { color: mau } })), {
    x: 0.5, y: 1.18, w: W - 1, h: 1.1, fontSize: 60, bold: true, align: 'center', valign: 'middle',
  });
  chu(s, mo, { x: 1, y: 2.32, w: W - 2, h: 0.95, fontSize: 19, color: CHU_PHU, align: 'center', valign: 'top', lineSpacing: 28 });
  chu(s, tieuDeLuong, { x: 0.5, y: 3.5, w: W - 1, h: 0.45, fontSize: 17, color: CHU_PHU, align: 'center', valign: 'middle' });

  const mx = 0.75;
  const muiTen = 0.62;
  const cw = (W - mx * 2 - muiTen * (luong.length - 1)) / luong.length;
  const cy = 4.12;
  const ch = 1.95;
  luong.forEach((t, i) => {
    const x = mx + i * (cw + muiTen);
    the(s, x, cy, cw, ch, { nhan: t.nhan });
    bieuTuong(s, t.icon, x, cy + 0.16, cw, 0.56, 28);
    chu(s, t.dau, { x: x + 0.1, y: cy + 0.76, w: cw - 0.2, h: 0.46, fontSize: 19, bold: true, color: TRANG, align: 'center', valign: 'middle' });
    chu(s, t.than, { x: x + 0.18, y: cy + 1.22, w: cw - 0.36, h: 0.62, fontSize: 13, color: CHU_PHU, align: 'center', valign: 'top', lineSpacing: 18 });
    if (i < luong.length - 1) {
      chu(s, '→', { x: x + cw, y: cy + ch / 2 - 0.25, w: muiTen, h: 0.5, fontSize: 22, color: 'C9D6E8', align: 'center', valign: 'middle' });
    }
  });

  dongVang(s, chuan, 6.32);
  L.notes(s, speaker);
}

// ---------------------------------------------------------------- dòng thời gian (slide 02, 03)

// nodes: [{ dau, phu, mau }]. Vẽ một trục ngang, mỗi mốc một chấm tròn, nhãn bên dưới.
function dongThoiGian(s, nodes, y) {
  const x0 = M + 0.9;
  const x1 = W - M - 0.9;
  const buoc = (x1 - x0) / (nodes.length - 1);
  s.addShape('line', { x: x0, y, w: x1 - x0, h: 0, line: { color: '4A6890', width: 2 } });
  nodes.forEach((n, i) => {
    const cx = x0 + i * buoc;
    const r = n.mau ? 0.17 : 0.12;
    s.addShape('ellipse', {
      x: cx - r, y: y - r, w: r * 2, h: r * 2,
      fill: { color: n.mau || '4A6890' }, line: { color: n.mau || '4A6890' },
    });
    if (n.nhan) {
      const nw = 2.3;
      const px = Math.min(Math.max(cx - nw / 2, M), W - M - nw);
      s.addShape('roundRect', { x: px, y: y - 0.72, w: nw, h: 0.36, rectRadius: 0.18, fill: { color: n.mau }, line: { color: n.mau } });
      chu(s, n.nhan, { x: px, y: y - 0.72, w: nw, h: 0.36, fontSize: 11, bold: true, color: '0B1A30', align: 'center', valign: 'middle' });
    }
    chu(s, [
      { text: n.dau, options: { fontSize: 15, bold: true, color: n.mau || TRANG, breakLine: true } },
      { text: n.phu, options: { fontSize: 12, color: CHU_PHU } },
    ], { x: cx - 1.15, y: y + 0.28, w: 2.3, h: 0.8, align: 'center', valign: 'top', lineSpacing: 18 });
  });
}

function vanDeSlide(pptx, ctx) {
  const s = slideMoi(pptx, ctx);
  dau(s, 'Bài toán', 'Lỗi hồ sơ BHYT thường được phát hiện quá muộn',
    'Khi cổng giám định báo lỗi, hồ sơ đã rời bệnh viện từ lâu — người ra y lệnh, người lập hồ sơ đã chuyển sang việc khác.');

  dongThoiGian(s, [
    { dau: 'Ra y lệnh', phu: 'Bác sĩ chỉ định trên HIS' },
    { dau: 'Ra viện', phu: 'Kết thúc điều trị' },
    { dau: 'Lập hồ sơ XML', phu: 'Kết xuất từ HIS' },
    { dau: 'Ký số, gửi cổng', phu: 'Cổng giám định BHXH' },
    { dau: 'Cổng báo lỗi', phu: 'Hàng tuần lễ sau', mau: DO, nhan: 'LÚC NÀY MỚI BIẾT LỖI' },
  ], 2.95);

  const cy = 4.3;
  const ch = 1.7;
  const gap = 0.3;
  const cw = (BODY_W - gap * 2) / 3;
  [
    ['⏳', 'Sửa muộn, tốn công', 'Phải truy lại khoa, người thực hiện, bệnh án đã đóng — một lỗi nhỏ thành nhiều vòng trao đổi.'],
    ['💸', 'Rủi ro xuất toán', 'Chi phí của hồ sơ lỗi có thể không được thanh toán, hoặc bị treo trong lúc chờ sửa.'],
    ['🔁', 'Lỗi cứ lặp lại', 'Không thấy lỗi tập trung ở khoa nào, mã lỗi nào — nên không sửa được tận gốc.'],
  ].forEach(([icon, h, b], i) => {
    const x = M + i * (cw + gap);
    the(s, x, cy, cw, ch);
    bieuTuong(s, icon, x + 0.2, cy + 0.2, 0.55, 0.55, 22);
    chu(s, h, { x: x + 0.85, y: cy + 0.2, w: cw - 1.05, h: 0.55, fontSize: 17, bold: true, color: TRANG, valign: 'middle' });
    chu(s, b, { x: x + 0.25, y: cy + 0.85, w: cw - 0.5, h: 0.75, fontSize: 13, color: CHU_PHU, valign: 'top', lineSpacing: 18 });
  });

  dongVang(s, 'Câu hỏi: làm sao biết lỗi khi người làm sai còn đang ở đó, và hồ sơ còn chưa rời bệnh viện?');
  L.notes(s, 'Mở bằng nỗi đau mà lãnh đạo và phòng KHTH đều biết. Không đưa con số xuất toán cụ thể — mỗi viện có số riêng; nếu viện bạn có số liệu, mời họ tự liên hệ. Ý chính: phát hiện càng muộn, chi phí sửa càng lớn và lỗi càng dễ lặp lại.');
}

// chiXml: bản cho lãnh đạo — chỉ nhấn điểm kiểm soát hồ sơ XML 3176; kiểm y lệnh thành phần mở rộng.
function haiDiemSlide(pptx, ctx, { chiXml = false } = {}) {
  const s = slideMoi(pptx, ctx);
  dau(s, 'Giải pháp', chiXml ? 'Chặn lỗi ngay trong bệnh viện, trước khi gửi đi' : 'Đưa việc phát hiện lỗi về sớm nhất có thể',
    chiXml
      ? 'Tiền giám định đặt một điểm kiểm soát bên trong bệnh viện — mọi hồ sơ được kiểm trước khi ký số và gửi cổng.'
      : 'Tiền giám định đặt hai điểm kiểm soát bên trong bệnh viện — trước khi hồ sơ được ký số và gửi đi.');

  dongThoiGian(s, [
    chiXml
      ? { dau: 'Ra y lệnh', phu: 'Bác sĩ chỉ định trên HIS' }
      : { dau: 'Ra y lệnh', phu: 'Kiểm sai sót y lệnh', mau: VANG, nhan: '① KHOẢNG 60 GIÂY' },
    { dau: 'Ra viện', phu: 'Kết thúc điều trị' },
    { dau: 'Lập hồ sơ XML', phu: 'Nạp vào Tiền giám định' },
    { dau: 'Kiểm hồ sơ', phu: 'Lỗi nghiêm trọng bị chặn', mau: VANG, nhan: chiXml ? 'ĐIỂM KIỂM SOÁT' : '② TRƯỚC KÝ SỐ' },
    { dau: 'Ký số, gửi cổng', phu: 'Hồ sơ đã sạch lỗi hơn', mau: XANH_LA },
  ], 2.95);

  // Vòng sửa lỗi
  const vy = 4.3;
  const buoc = ['Phát hiện lỗi', 'Báo về khoa phát sinh', 'Sửa trên HIS', 'Nạp lại', 'Kiểm lại'];
  const muiTen = 0.4;
  const bw = (BODY_W - muiTen * (buoc.length - 1)) / buoc.length;
  chu(s, 'VÒNG SỬA LỖI', { x: M, y: vy - 0.02, w: BODY_W, h: 0.3, fontSize: 11, bold: true, color: CHU_MO, charSpacing: 2 });
  buoc.forEach((t, i) => {
    const x = M + i * (bw + muiTen);
    s.addShape('roundRect', { x, y: vy + 0.35, w: bw, h: 0.55, rectRadius: 0.27, fill: { color: THE_NEN, transparency: 15 }, line: { color: THE_VIEN, width: 0.75 } });
    chu(s, t, { x, y: vy + 0.35, w: bw, h: 0.55, fontSize: 14, bold: i === 2, color: i === 2 ? VANG : TRANG, align: 'center', valign: 'middle' });
    if (i < buoc.length - 1) {
      chu(s, '→', { x: x + bw, y: vy + 0.35, w: muiTen, h: 0.55, fontSize: 18, color: 'C9D6E8', align: 'center', valign: 'middle' });
    }
  });

  chu(s, chiXml
    ? 'Mở rộng về sau: kiểm sai sót ngay khi bác sĩ ra y lệnh, để bắt lỗi sớm hơn nữa — sau khi danh mục của viện đã chuẩn.'
    : 'Phần mềm chỉ đọc HIS, không sửa hộ: lỗi được sửa đúng nơi phát sinh, nên dữ liệu gốc trên HIS và hồ sơ gửi đi luôn khớp nhau.', {
    x: M, y: 5.45, w: BODY_W, h: 0.6, fontSize: 14, color: CHU_PHU, align: 'center', valign: 'middle',
  });
  dongVang(s, 'Phát hiện sớm  ·  Sửa tại nguồn  ·  Gửi hồ sơ sạch');
  if (chiXml) {
    L.notes(s, 'Bản cho lãnh đạo chỉ nhấn một điểm kiểm soát: hồ sơ XML 3176 được kiểm trước khi ký số, lỗi nghiêm trọng thì không được ký, không được gửi. Phần mềm chỉ đọc HIS, không sửa hộ — khoa sửa trên HIS rồi nạp lại. Kiểm sai sót y lệnh là bước mở rộng: nó cần danh mục thuốc, vật tư, nhân sự của viện đã chuẩn, nếu không sẽ sinh rất nhiều cảnh báo giả. Nếu được hỏi về kiểm y lệnh tại Bạch Mai: đang ở giai đoạn làm sạch danh mục, chưa giao khoa xử lý.');
    return;
  }
  L.notes(s, 'Hai điểm kiểm soát: ① ngay sau khi bác sĩ ra y lệnh — hệ thống quét HIS mỗi 60 giây, lỗi hiện ra khi người làm còn trong ca; ② trước khi ký số — hồ sơ XML 3176 có lỗi nghiêm trọng thì không được ký, không được gửi. Nhấn vòng sửa lỗi: phần mềm không sửa dữ liệu, chỉ chỉ ra lỗi; khoa sửa trên HIS rồi nạp lại.');
}

// ---------------------------------------------------------------- 04 con số

function conSoSlide(pptx, ctx) {
  const s = slideMoi(pptx, ctx);
  dau(s, 'Năng lực', 'Hệ thống đang chạy thực tế tại Bệnh viện Bạch Mai',
    'Các con số lấy từ cấu hình và bộ quy tắc hiện hành của phần mềm.');

  const so = [
    ['~60', 'giây', 'Sai sót y lệnh được phát hiện sau khi phát sinh'],
    ['19', 'quy tắc', 'Kiểm y lệnh nạp sẵn, thêm được quy tắc mới'],
    ['~300', 'mã lỗi', 'Kiểm hồ sơ XML 3176 theo từng trường dữ liệu'],
    ['12', 'bảng XML', 'Có bộ kiểm riêng, cộng kiểm chéo toàn hồ sơ'],
    ['2', 'mức lỗi', 'Nghiêm trọng thì chặn gửi; cảnh báo thì ghi nhận'],
  ];
  const gap = 0.25;
  const cw = (BODY_W - gap * (so.length - 1)) / so.length;
  const cy = 2.2;
  const ch = 3.4;
  so.forEach(([n, dv, mo], i) => {
    const x = M + i * (cw + gap);
    the(s, x, cy, cw, ch, { nhan: i === 0 });
    chu(s, n, { x, y: cy + 0.35, w: cw, h: 1.0, fontSize: 50, bold: true, color: i === 0 ? VANG : XANH, align: 'center', valign: 'middle' });
    chu(s, dv, { x, y: cy + 1.35, w: cw, h: 0.4, fontSize: 16, bold: true, color: TRANG, align: 'center', valign: 'middle' });
    chu(s, mo, { x: x + 0.2, y: cy + 1.95, w: cw - 0.4, h: 1.2, fontSize: 13, color: CHU_PHU, align: 'center', valign: 'top', lineSpacing: 18 });
  });

  dongVang(s, 'Ký số bằng HSM hoặc USB Token  ·  Tự gửi cổng, tự thử lại tối đa 3 lần  ·  Nhận tệp XML tới 100 MB', 6.05);
  L.notes(s, 'Slide cho lãnh đạo: quy mô bộ kiểm. "Khoảng 300" là số mã lỗi riêng biệt trong bộ kiểm XML 3176 hiện tại; con số thay đổi khi quy định đổi. 19 quy tắc y lệnh gồm hai họ: lâm sàng/danh mục và cấu trúc/thời gian/chứng chỉ hành nghề. Không hứa số phần trăm giảm xuất toán — mỗi viện đo trên dữ liệu của mình.');
}

// ---------------------------------------------------------------- 05 bảy nhóm chức năng

function chucNangSlide(pptx, ctx) {
  const s = slideMoi(pptx, ctx);
  dau(s, 'Chức năng', 'Bảy nhóm chức năng, hai trọng tâm');

  const o = [
    ['🩺', 'Kiểm sai sót y lệnh', 'Quét HIS liên tục, báo lỗi khoảng 60 giây sau khi ra y lệnh', true],
    ['📄', 'Kiểm hồ sơ XML 3176', 'Kiểm từng bảng và kiểm chéo; lỗi nghiêm trọng chặn ký số, gửi cổng', true],
    ['💳', 'Thẻ BHYT, quyền lợi', 'Tra thẻ hàng loạt trên cổng BHXH; tra lũy kế tiền cùng chi trả'],
    ['💊', 'Thuốc, VTYT, DVKT', 'Đối chiếu mã và tên với danh mục đã đăng ký'],
    ['📚', 'Danh mục và giá', 'Quản lý 11 bộ danh mục BHYT, kiểm hiệu lực và giá'],
    ['🔎', 'Phản hồi lỗi về khoa', 'Quét mã vạch mã điều trị, in phiếu lỗi cho đơn vị phát sinh'],
    ['📊', 'Chỉ số và báo cáo', 'Dashboard lỗi XML, chứng từ, độ phủ danh mục'],
    ['➕', 'Mở rộng', 'Chứng từ điện tử Phụ lục 02 · Danh mục theo Thông tư 12/2026', false, true],
  ];
  const cols = 4;
  const gap = 0.25;
  const cw = (BODY_W - gap * (cols - 1)) / cols;
  const ch = 2.2;
  o.forEach(([icon, h, b, nhan, mo], i) => {
    const x = M + (i % cols) * (cw + gap);
    const y = 1.65 + Math.floor(i / cols) * (ch + 0.25);
    the(s, x, y, cw, ch, { nhan, mo });
    bieuTuong(s, icon, x + 0.2, y + 0.2, 0.55, 0.55, 22);
    if (nhan) {
      chu(s, 'TRỌNG TÂM', { x: x + cw - 1.35, y: y + 0.28, w: 1.15, h: 0.3, fontSize: 9.5, bold: true, color: VANG, align: 'right', charSpacing: 1 });
    }
    chu(s, h, { x: x + 0.22, y: y + 0.85, w: cw - 0.44, h: 0.45, fontSize: 16, bold: true, color: TRANG, valign: 'middle' });
    chu(s, b, { x: x + 0.22, y: y + 1.32, w: cw - 0.44, h: 0.8, fontSize: 12.5, color: CHU_PHU, valign: 'top', lineSpacing: 17 });
  });
  L.notes(s, 'Hai trọng tâm tạo giá trị lớn nhất là kiểm sai sót y lệnh (bắt lỗi sớm nhất) và kiểm hồ sơ XML 3176 (chặn lỗi trước khi gửi). Năm nhóm còn lại hỗ trợ: đúng thẻ, đúng danh mục, đúng giá, phản hồi về đúng khoa, và đo được bằng số liệu. Ô viền đứt là phần mở rộng cùng hạ tầng ký số và gửi cổng.');
}

// ---------------------------------------------------------------- 06 quy trình 5 bước

function quyTrinhSlide(pptx, ctx) {
  const s = slideMoi(pptx, ctx);
  dau(s, 'Vận hành', 'Năm bước, bốn bước máy tự làm',
    'Người dùng không phải bấm ký hay bấm gửi từng hồ sơ XML 3176 — chỉ tập trung đọc lỗi và phối hợp sửa.');

  const b = [
    ['Nạp hồ sơ', 'Tự quét thư mục HIS xuất ra, hoặc kéo thả tệp (tới 100 MB)', 'TỰ ĐỘNG'],
    ['Kiểm tra', 'Chạy nền: kiểm XML, tra thẻ BHYT, đối chiếu danh mục', 'TỰ ĐỘNG'],
    ['Ký số', 'Khi hồ sơ hết lỗi nghiêm trọng — HSM hoặc USB Token', 'TỰ ĐỘNG'],
    ['Gửi cổng', 'Tự gửi, tự thử lại tối đa 3 lần; bật theo từng cơ sở', 'TỰ ĐỘNG'],
    ['Theo dõi', 'Lọc hồ sơ lỗi, đọc phản hồi cổng, xem dashboard', 'NGHIỆP VỤ'],
  ];
  const gap = 0.1;
  const cw = (BODY_W - gap * (b.length - 1)) / b.length;
  const ty = 2.35;
  b.forEach(([h, mo, tag], i) => {
    const x = M + i * (cw + gap);
    const nguoi = tag === 'NGHIỆP VỤ';
    s.addShape(i === 0 ? 'homePlate' : 'chevron', {
      x, y: ty, w: cw + (i < b.length - 1 ? 0.12 : 0), h: 0.75,
      fill: { color: nguoi ? '7A5A12' : '1E4E86' }, line: { color: '0B1A30', width: 1 },
    });
    chu(s, [
      { text: `0${i + 1}  `, options: { fontSize: 12, bold: true, color: nguoi ? VANG : XANH } },
      { text: h, options: { fontSize: 16, bold: true, color: TRANG } },
    ], { x: x + (i === 0 ? 0.22 : 0.4), y: ty, w: cw - 0.5, h: 0.75, valign: 'middle' });

    const cy = ty + 0.95;
    the(s, x, cy, cw, 1.9, { nhan: nguoi });
    chu(s, tag, { x: x + 0.2, y: cy + 0.2, w: cw - 0.4, h: 0.3, fontSize: 10.5, bold: true, color: nguoi ? VANG : XANH_LA, charSpacing: 1 });
    chu(s, mo, { x: x + 0.2, y: cy + 0.6, w: cw - 0.4, h: 1.2, fontSize: 14, color: TRANG, valign: 'top', lineSpacing: 20 });
  });

  dongVang(s, 'Sửa trên HIS xong thì nạp lại — hồ sơ được kiểm lại từ đầu, hết lỗi nghiêm trọng là tự đi tiếp', 5.75);
  L.notes(s, 'Bốn bước đầu máy tự làm, chạy nền. Chức năng tự gửi cổng mặc định tắt và bật riêng cho từng cơ sở — viện chủ động quyết định khi nào đủ tin để bật (thường sau giai đoạn chạy thử). Bước 05 là việc của nghiệp vụ: đọc lỗi, giao khoa sửa, theo dõi tồn đọng.');
}

// ---------------------------------------------------------------- 07–10 slide có ảnh chụp

function anhSlide(pptx, ctx, { kicker, title, diem, anh, chuThich, speaker }) {
  const s = slideMoi(pptx, ctx);
  dau(s, kicker, title);

  const tw = 4.3;
  const nho = diem.length > 3;
  const rowH = nho ? 1.12 : 1.45;
  diem.forEach(([icon, h, b], i) => {
    const y = 1.75 + i * (rowH + 0.08);
    s.addShape('ellipse', { x: M, y, w: 0.6, h: 0.6, fill: { color: THE_NEN }, line: { color: THE_VIEN, width: 0.75 } });
    bieuTuong(s, icon, M, y, 0.6, 0.6, 16);
    chu(s, [
      { text: h, options: { fontSize: 16, bold: true, color: TRANG, breakLine: true } },
      { text: b, options: { fontSize: nho ? 12.5 : 13.5, color: CHU_PHU } },
    ], { x: M + 0.8, y: y - 0.02, w: tw - 0.8, h: rowH, valign: 'top', lineSpacing: nho ? 17 : 19 });
  });

  const ax = M + tw + 0.35;
  const aw = W - M - ax;
  const r = anhChup(s, anh, ax, 1.75, aw, 4.6);
  chu(s, chuThich, { x: ax, y: r.y + r.h + 0.12, w: aw, h: 0.3, fontSize: 10.5, italic: true, color: CHU_MO, align: 'center' });
  L.notes(s, speaker);
}

// ---------------------------------------------------------------- 11 dashboard

function dashboardSlide(pptx, ctx) {
  const s = slideMoi(pptx, ctx);
  dau(s, 'Quản trị', 'Dashboard trả lời bốn câu hỏi của lãnh đạo',
    'Dashboard lỗi XML có bốn biểu đồ — mỗi biểu đồ dẫn tới một hành động cụ thể.');

  const o = [
    ['Phễu xử lý hồ sơ', 'Hồ sơ đang nghẽn ở bậc nào?', 'Đã nạp → Không lỗi nghiêm trọng → Đã xuất → Đã ký → Đã gửi', 'pheu'],
    ['Top 15 mã lỗi (Pareto)', 'Sửa lỗi nào thì được nhiều nhất?', 'Xử lý vài mã đầu bảng giải quyết phần lớn hồ sơ lỗi', 'pareto'],
    ['Tồn đọng theo tuổi hồ sơ', 'Hồ sơ nào sắp quá hạn?', '0–7 · 8–15 · 16–30 · trên 30 ngày — nhóm trên 30 ngày xử lý trước', 'ton'],
    ['Lỗi nghiêm trọng theo khoa', 'Giao việc cho ai?', 'Phân bổ lỗi theo khoa thực hiện để giao đúng đầu mối', 'khoa'],
  ];
  const gap = 0.3;
  const cw = (BODY_W - gap) / 2;
  const ch = 2.1;
  o.forEach(([h, hoi, mo, kieu], i) => {
    const x = M + (i % 2) * (cw + gap);
    const y = 1.95 + Math.floor(i / 2) * (ch + 0.22);
    the(s, x, y, cw, ch);
    // Hình minh hoạ bên trái
    const gx = x + 0.25;
    const gy = y + 0.3;
    const gw = 2.0;
    const gh = 1.5;
    if (kieu === 'pheu') {
      [1, 0.82, 0.66, 0.52, 0.4].forEach((k, j) => {
        const w = gw * k;
        s.addShape('rect', { x: gx + (gw - w) / 2, y: gy + j * 0.3, w, h: 0.24, fill: { color: j === 4 ? XANH_LA : XANH, transparency: 15 + j * 8 }, line: { color: XANH, width: 0 } });
      });
    } else if (kieu === 'pareto') {
      [1, 0.72, 0.5, 0.36, 0.26, 0.19, 0.14, 0.1].forEach((k, j) => {
        const bh = gh * k;
        s.addShape('rect', { x: gx + j * 0.25, y: gy + gh - bh, w: 0.19, h: bh, fill: { color: j < 2 ? VANG : XANH, transparency: j < 2 ? 0 : 30 }, line: { color: XANH, width: 0 } });
      });
    } else if (kieu === 'ton') {
      [0.45, 0.7, 0.55, 1].forEach((k, j) => {
        const bh = gh * k;
        s.addShape('rect', { x: gx + j * 0.5, y: gy + gh - bh, w: 0.36, h: bh, fill: { color: j === 3 ? DO : XANH, transparency: j === 3 ? 0 : 30 }, line: { color: XANH, width: 0 } });
      });
    } else {
      [1, 0.78, 0.6, 0.42, 0.3].forEach((k, j) => {
        s.addShape('rect', { x: gx, y: gy + j * 0.3, w: gw * k, h: 0.22, fill: { color: j === 0 ? VANG : XANH, transparency: j === 0 ? 0 : 30 }, line: { color: XANH, width: 0 } });
      });
    }
    const tx = x + 2.55;
    const tw = cw - 2.8;
    chu(s, h, { x: tx, y: y + 0.22, w: tw, h: 0.4, fontSize: 16, bold: true, color: TRANG, valign: 'middle' });
    chu(s, hoi, { x: tx, y: y + 0.65, w: tw, h: 0.4, fontSize: 14, italic: true, color: VANG, valign: 'middle' });
    chu(s, mo, { x: tx, y: y + 1.1, w: tw, h: 0.85, fontSize: 12.5, color: CHU_PHU, valign: 'top', lineSpacing: 17 });
  });
  chu(s, 'Hình bên trái mỗi ô là minh hoạ dạng biểu đồ, không phải số liệu thật.', {
    x: M, y: 6.6, w: BODY_W, h: 0.28, fontSize: 10.5, italic: true, color: CHU_MO,
  });
  L.notes(s, 'Slide cho lãnh đạo và phòng KHTH. Ngoài bốn biểu đồ còn có các thẻ chỉ số, trong đó "Chi phí BHYT bị treo" — tổng chi phí của hồ sơ chưa gửi được — là con số lãnh đạo hay hỏi nhất. Có thêm dashboard chứng từ điện tử và dashboard độ phủ danh mục TT12.');
}

// ---------------------------------------------------------------- 12 giá trị theo vai trò

function vaiTroSlide(pptx, ctx) {
  const s = slideMoi(pptx, ctx);
  dau(s, 'Giá trị', 'Mỗi vai trò nhận được gì');

  const cot = [
    ['👔', 'Lãnh đạo bệnh viện', [
      'Giảm rủi ro xuất toán từ gốc — lỗi được chặn trước khi gửi',
      'Nắm chất lượng hồ sơ bằng số liệu, không chờ cổng báo về',
      'Thấy được chi phí BHYT đang bị treo vì hồ sơ chưa gửi được',
    ]],
    ['📋', 'Lãnh đạo phòng KHTH', [
      'Biết lỗi tập trung ở khoa nào, mã lỗi nào để ưu tiên',
      'Theo dõi tồn đọng theo tuổi hồ sơ, xử lý nhóm quá hạn trước',
      'Giao việc sửa lỗi có căn cứ: phiếu lỗi theo mã điều trị',
    ]],
    ['🧑‍💻', 'Nhân viên nghiệp vụ', [
      'Không dò hồ sơ thủ công — lỗi chỉ ra tận bảng, tận trường',
      'Ký số và gửi cổng tự động khi hồ sơ đạt yêu cầu',
      'Tra thẻ hàng loạt, tra tiền cùng chi trả ngay trên một màn hình',
    ]],
  ];
  const gap = 0.3;
  const cw = (BODY_W - gap * 2) / 3;
  const cy = 1.75;
  const ch = 4.35;
  cot.forEach(([icon, h, ds], i) => {
    const x = M + i * (cw + gap);
    the(s, x, cy, cw, ch);
    s.addShape('ellipse', { x: x + (cw - 0.95) / 2, y: cy + 0.3, w: 0.95, h: 0.95, fill: { color: '25324A' }, line: { color: VANG, width: 1.25 } });
    bieuTuong(s, icon, x + (cw - 0.95) / 2, cy + 0.3, 0.95, 0.95, 26);
    chu(s, h, { x: x + 0.2, y: cy + 1.4, w: cw - 0.4, h: 0.5, fontSize: 19, bold: true, color: TRANG, align: 'center', valign: 'middle' });
    chu(s, ds.map((t, k) => ({ text: t, options: { bullet: { indent: 16 }, breakLine: k < ds.length - 1 } })), {
      x: x + 0.3, y: cy + 2.1, w: cw - 0.55, h: 2.5, fontSize: 14, color: CHU_PHU, valign: 'top', paraSpaceAfter: 10, lineSpacing: 19,
    });
  });
  L.notes(s, 'Nói theo đúng người đang ngồi trong phòng. Lãnh đạo: quản trị rủi ro tài chính và chất lượng. KHTH: công cụ điều phối — biết ai cần sửa gì, cái gì sắp quá hạn. Nghiệp vụ: bớt việc tay, máy làm phần lặp lại.');
}

// ---------------------------------------------------------------- 13 nguyên tắc và giới hạn

function nguyenTacSlide(pptx, ctx) {
  const s = slideMoi(pptx, ctx);
  dau(s, 'Nói rõ trước', 'Nguyên tắc vận hành và giới hạn cần biết');

  const cot = [
    ['✅', 'Nguyên tắc', XANH_LA, [
      ['Chỉ đọc HIS', 'Không ghi, không sửa dữ liệu, không đổi cấu trúc HIS'],
      ['Sửa tại nơi phát sinh', 'Lỗi sửa trên HIS rồi nạp lại — phần mềm không sửa hộ'],
      ['Không đánh giá cá nhân', 'Công cụ giảm sai sót, bảo vệ bác sĩ và người bệnh (no-blame)'],
    ]],
    ['⚠️', 'Giới hạn', VANG, [
      ['Chưa trùng toàn bộ quy tắc BHXH', 'Hồ sơ qua Tiền giám định vẫn có thể bị cổng trả về'],
      ['Chỉ bắt lỗi có trong bộ quy tắc', 'Quy định mới cần thời gian cập nhật quy tắc'],
      ['Phụ thuộc chất lượng danh mục', 'Danh mục thiếu hoặc sai hiệu lực sinh ra lỗi giả hàng loạt'],
    ]],
  ];
  const gap = 0.35;
  const cw = (BODY_W - gap) / 2;
  const cy = 1.65;
  const ch = 4.0;
  cot.forEach(([icon, h, mau, ds], i) => {
    const x = M + i * (cw + gap);
    the(s, x, cy, cw, ch);
    bieuTuong(s, icon, x + 0.25, cy + 0.22, 0.5, 0.5, 20);
    chu(s, h, { x: x + 0.85, y: cy + 0.22, w: cw - 1.1, h: 0.5, fontSize: 19, bold: true, color: mau, valign: 'middle' });
    ds.forEach(([t, b], k) => {
      const y = cy + 0.95 + k * 0.98;
      chu(s, [
        { text: t, options: { fontSize: 15.5, bold: true, color: TRANG, breakLine: true } },
        { text: b, options: { fontSize: 13.5, color: CHU_PHU } },
      ], { x: x + 0.3, y, w: cw - 0.6, h: 1.0, valign: 'top', lineSpacing: 19 });
    });
  });
  dongVang(s, 'Tiền giám định là công cụ hỗ trợ — không thay thế quyết định chuyên môn', 5.95);
  L.notes(s, 'Nói thẳng giới hạn giúp viện bạn tin phần còn lại. Bài học từ Bạch Mai: khi ba danh mục bị rỗng đã sinh ra khoảng 36.100 vi phạm giả — vì vậy giai đoạn chuẩn bị phải chốt chủ sở hữu danh mục. Thông điệp no-blame đặc biệt quan trọng với bác sĩ: phần mềm bắt lỗi dữ liệu, không chấm điểm người.');
}

// ---------------------------------------------------------------- 14 triển khai

function trienKhaiSlide(pptx, ctx) {
  const s = slideMoi(pptx, ctx);
  dau(s, 'Triển khai', 'Điều kiện và lộ trình triển khai tại bệnh viện');

  // Cột trái: mức phụ thuộc HIS
  const lx = M;
  const lw = 5.3;
  const ky = 1.65;
  [
    ['🔌', 'Dùng được với mọi HIS', 'Hồ sơ XML 3176 — nhận tệp XML chuẩn theo Quyết định 3176, không phụ thuộc phần mềm HIS', false],
    ['🧩', 'Cần kết nối riêng với HIS của viện', 'Kiểm sai sót y lệnh — đọc trực tiếp CSDL HIS (chỉ đọc); viện dùng HIS khác cần làm lớp kết nối riêng', true],
  ].forEach(([icon, h, b, nhan], i) => {
    const y = ky + i * 1.62;
    the(s, lx, y, lw, 1.45, { nhan });
    bieuTuong(s, icon, lx + 0.2, y + 0.2, 0.5, 0.5, 20);
    chu(s, h, { x: lx + 0.85, y: y + 0.2, w: lw - 1.05, h: 0.5, fontSize: 16, bold: true, color: nhan ? VANG : TRANG, valign: 'middle' });
    chu(s, b, { x: lx + 0.3, y: y + 0.75, w: lw - 0.55, h: 0.65, fontSize: 12.5, color: CHU_PHU, valign: 'top', lineSpacing: 17 });
  });

  // Cột phải: viện cần chuẩn bị
  const rx = lx + lw + 0.35;
  const rw = W - M - rx;
  the(s, rx, ky, rw, 3.07);
  chu(s, 'VIỆN CẦN CHUẨN BỊ', { x: rx + 0.3, y: ky + 0.2, w: rw - 0.6, h: 0.32, fontSize: 12, bold: true, color: XANH, charSpacing: 2 });
  const ds = [
    'Máy chủ: Windows Server, hoặc Linux / Docker',
    'Tài khoản chỉ đọc vào cơ sở dữ liệu HIS',
    'Thư mục nhận tệp XML do HIS xuất ra',
    'HSM hoặc USB Token để ký số',
    'Tài khoản Cổng giám định BHXH và mã cơ sở KCB',
    'Người phụ trách danh mục BHYT của viện',
  ];
  chu(s, ds.map((t, k) => ({ text: t, options: { bullet: { indent: 16 }, breakLine: k < ds.length - 1 } })), {
    x: rx + 0.3, y: ky + 0.62, w: rw - 0.6, h: 2.35, fontSize: 13.5, color: TRANG, valign: 'top', paraSpaceAfter: 5, lineSpacing: 18,
  });

  // Lộ trình
  const gy = 5.0;
  const gd = [
    ['Chuẩn bị', '2–4 tuần', 'Lập tổ, chốt chủ sở hữu danh mục, ban hành no-blame'],
    ['Chạy thử', '4–8 tuần', '1–2 khoa, chạy song song quy trình cũ'],
    ['Nhân rộng', '2–4 tháng', 'Mở rộng theo khoa, đào tạo người dùng nòng cốt'],
    ['Thường quy', 'sau ổn định', 'Bật tự gửi cổng, đưa vào quy chế'],
  ];
  const gap = 0.1;
  const cw = (BODY_W - gap * (gd.length - 1)) / gd.length;
  gd.forEach(([h, tg, mo], i) => {
    const x = M + i * (cw + gap);
    s.addShape(i === 0 ? 'homePlate' : 'chevron', {
      x, y: gy, w: cw + (i < gd.length - 1 ? 0.12 : 0), h: 0.6,
      fill: { color: i === gd.length - 1 ? '1F6B45' : '1E4E86' }, line: { color: '0B1A30', width: 1 },
    });
    chu(s, [
      { text: h + '  ', options: { fontSize: 15, bold: true, color: TRANG } },
      { text: tg, options: { fontSize: 12.5, color: i === gd.length - 1 ? XANH_LA : XANH } },
    ], { x: x + (i === 0 ? 0.22 : 0.4), y: gy, w: cw - 0.5, h: 0.6, valign: 'middle' });
    chu(s, mo, { x: x + (i === 0 ? 0.22 : 0.4), y: gy + 0.7, w: cw - 0.55, h: 0.7, fontSize: 12.5, color: CHU_PHU, valign: 'top', lineSpacing: 17 });
  });
  L.notes(s, 'Phân biệt rõ hai phần khi viện hỏi "HIS của chúng tôi có dùng được không": phần XML 3176 dùng được ngay vì đầu vào là tệp XML chuẩn; phần kiểm sai sót y lệnh đọc thẳng CSDL HIS nên với HIS khác cần khảo sát và làm lớp kết nối. Lộ trình lấy từ tài liệu tổng hợp mục 10.1; điều kiện chuyển giai đoạn: tỷ lệ lỗi nghiêm trọng ổn định, dương tính giả của kiểm y lệnh về mức chấp nhận được.');
}

// ---------------------------------------------------------------- 15 kết

function ketSlide(pptx, ctx) {
  const s = slideMoi(pptx, ctx);
  chu(s, [
    { text: 'Phát hiện sớm', options: { color: TRANG } },
    { text: '  ·  ', options: { color: CHU_MO } },
    { text: 'Sửa tại nguồn', options: { color: XANH } },
    { text: '  ·  ', options: { color: CHU_MO } },
    { text: 'Gửi hồ sơ sạch', options: { color: VANG } },
  ], { x: 0.5, y: 1.55, w: W - 1, h: 1.0, fontSize: 36, bold: true, align: 'center', valign: 'middle' });
  chu(s, 'Tiền giám định đưa việc kiểm tra hồ sơ BHYT về bên trong bệnh viện —\nkhi người làm sai còn ở đó, và hồ sơ còn chưa rời bệnh viện.', {
    x: 1, y: 2.8, w: W - 2, h: 0.95, fontSize: 19, color: CHU_PHU, align: 'center', valign: 'top', lineSpacing: 28,
  });

  const pw = 7.2;
  the(s, (W - pw) / 2, 4.3, pw, 1.4);
  chu(s, [
    { text: 'HỎI ĐÁP  ·  LIÊN HỆ', options: { fontSize: 12, bold: true, color: XANH, charSpacing: 2, breakLine: true } },
    { text: DON_VI, options: { fontSize: 17, bold: true, color: TRANG, breakLine: true } },
    { text: 'Điện thoại: ………………   ·   Email: ………………', options: { fontSize: 14, color: CHU_PHU } },
  ], { x: (W - pw) / 2, y: 4.3, w: pw, h: 1.4, align: 'center', valign: 'middle', lineSpacing: 24 });
  L.notes(s, 'Nhắc lại ba từ khoá rồi mở hỏi đáp. Điền số điện thoại và email đầu mối trước khi trình bày. Câu hỏi hay gặp: HIS khác có dùng được không (xem slide Triển khai), có thay bộ phận giám định không (không — xem slide Nguyên tắc và giới hạn), chi phí và thời gian (xem slide Đề xuất thí điểm).');
}

// ---------------------------------------------------------------- kết quả (để trống)

// Khung trước / sau. Ô số liệu để trống: bảng kết quả kiểm lỗi trong phần mềm chỉ giữ lần kiểm
// mới nhất, nên số "trước" phải lấy từ cổng BHXH hoặc số liệu KHTH lưu riêng, và phải được
// Bạch Mai đồng ý công bố. Không thay bằng ước đoán.
function ketQuaSlide(pptx, ctx) {
  const s = slideMoi(pptx, ctx);
  dau(s, 'Kết quả tại Bạch Mai', 'Trước và sau khi áp dụng Tiền giám định',
    'Kỳ trước: ………………   ·   Kỳ sau: ………………   ·   Nguồn số liệu: ………………');

  const cot = [['Chỉ số', 5.3], ['Trước', 2.0], ['Sau', 2.0], ['Thay đổi', 0]];
  cot[3][1] = BODY_W - cot.slice(0, 3).reduce((t, c) => t + c[1], 0);
  const xs = [];
  cot.reduce((x, c) => { xs.push(x); return x + c[1]; }, M);
  const hy = 2.05;
  cot.forEach(([t, w], i) => {
    chu(s, t.toUpperCase(), {
      x: xs[i] + 0.3, y: hy, w: w - 0.6, h: 0.32, fontSize: 11, bold: true, color: XANH, charSpacing: 2,
      align: i === 0 ? 'left' : 'center',
    });
  });
  const dong = [
    'Tỷ lệ hồ sơ còn lỗi nghiêm trọng khi gửi cổng',
    'Số hồ sơ bị cổng giám định trả về mỗi tháng',
    'Số tiền BHYT bị xuất toán mỗi tháng',
    'Thời gian từ khi phát sinh lỗi tới khi sửa xong',
  ];
  dong.forEach((t, k) => {
    const y = 2.5 + k * 0.88;
    the(s, M, y, BODY_W, 0.76);
    chu(s, t, { x: xs[0] + 0.3, y, w: cot[0][1] - 0.4, h: 0.76, fontSize: 15, bold: true, color: TRANG, valign: 'middle' });
    [1, 2, 3].forEach((i) => {
      chu(s, '………', { x: xs[i], y, w: cot[i][1], h: 0.76, fontSize: 20, bold: i === 3, color: i === 3 ? VANG : CHU_PHU, align: 'center', valign: 'middle' });
    });
  });
  dongVang(s, 'Viện của anh chị sẽ tự đo được đúng các chỉ số này ngay trong giai đoạn thí điểm', 6.15);
  L.notes(s, 'ĐIỀN SỐ LIỆU TRƯỚC KHI TRÌNH BÀY — và phải được Bệnh viện Bạch Mai đồng ý công bố. '
    + 'Số "sau" lấy từ Dashboard lỗi XML (thẻ chỉ số, phễu, tồn đọng); số "trước" phải lấy từ báo cáo kết quả giám định trên Cổng BHXH hoặc số liệu phòng KHTH lưu riêng, '
    + 'vì phần mềm chỉ lưu kết quả của lần kiểm mới nhất. Chọn hai kỳ dài bằng nhau. '
    + 'Nếu chưa có số đáng tin, bỏ slide này khỏi buổi trình bày — đừng thay bằng ước đoán; lãnh đạo hỏi lại một con số sai thì mất uy tín cả deck.');
}

// ---------------------------------------------------------------- quy ra tiền

function quyRaTienSlide(pptx, ctx) {
  const s = slideMoi(pptx, ctx);
  dau(s, 'Quy ra tiền', 'Viện mình đang có bao nhiêu tiền chịu rủi ro mỗi tháng?',
    'Ba con số đầu vào phòng KHTH và Phòng BHYT của viện đều có sẵn.');

  const o = [
    ['📄', 'Số hồ sơ BHYT', 'mỗi tháng'],
    ['⚠️', 'Tỷ lệ hồ sơ', 'bị cổng trả lỗi'],
    ['💰', 'Chi phí BHYT', 'trung bình mỗi hồ sơ'],
    ['🎯', 'Tiền chịu rủi ro', 'mỗi tháng'],
  ];
  const op = 0.55;
  const bw = (BODY_W - op * (o.length - 1)) / o.length;
  const by = 2.1;
  const bh = 1.85;
  o.forEach(([icon, h, phu], i) => {
    const x = M + i * (bw + op);
    const cuoi = i === o.length - 1;
    the(s, x, by, bw, bh, { nhan: cuoi });
    bieuTuong(s, icon, x, by + 0.12, bw, 0.5, 20);
    chu(s, [
      { text: h, options: { fontSize: 16, bold: true, color: TRANG, breakLine: true } },
      { text: phu, options: { fontSize: 12.5, color: CHU_PHU } },
    ], { x: x + 0.1, y: by + 0.62, w: bw - 0.2, h: 0.62, align: 'center', valign: 'top', lineSpacing: 18 });
    chu(s, '………', { x, y: by + 1.25, w: bw, h: 0.48, fontSize: 20, bold: true, color: cuoi ? VANG : XANH, align: 'center', valign: 'middle' });
    if (!cuoi) {
      chu(s, i === o.length - 2 ? '=' : '×', { x: x + bw, y: by + bh / 2 - 0.3, w: op, h: 0.6, fontSize: 26, bold: true, color: 'C9D6E8', align: 'center', valign: 'middle' });
    }
  });

  const vy = 4.2;
  s.addShape('roundRect', { x: M, y: vy, w: BODY_W, h: 0.62, rectRadius: 0.08, fill: { color: 'FFFFFF', transparency: 94 }, line: { color: '4A6890', width: 0.75, dashType: 'dash' } });
  chu(s, [
    { text: 'VÍ DỤ GIẢ ĐỊNH   ', options: { fontSize: 11, bold: true, color: CHU_MO, charSpacing: 1 } },
    { text: '20.000 hồ sơ  ×  5%  ×  1.500.000 đ  =  1,5 tỷ đồng mỗi tháng', options: { fontSize: 16, bold: true, color: TRANG } },
    { text: '   — không phải số liệu của bệnh viện nào', options: { fontSize: 12, italic: true, color: CHU_MO } },
  ], { x: M, y: vy, w: BODY_W, h: 0.62, align: 'center', valign: 'middle' });

  const cy = 5.05;
  const gap = 0.3;
  const cw = (BODY_W - gap) / 2;
  [
    ['Tiền bị treo', 'Hồ sơ lỗi chưa gửi được: chậm thanh toán, ảnh hưởng dòng tiền của viện.'],
    ['Tiền bị mất', 'Chi phí bị xuất toán sau giám định — phần không lấy lại được.'],
  ].forEach(([h, b], i) => {
    const x = M + i * (cw + gap);
    the(s, x, cy, cw, 0.95);
    chu(s, [
      { text: h, options: { fontSize: 15, bold: true, color: i ? DO : VANG, breakLine: true } },
      { text: b, options: { fontSize: 13, color: CHU_PHU } },
    ], { x: x + 0.3, y: cy, w: cw - 0.6, h: 0.95, valign: 'middle', lineSpacing: 18 });
  });
  dongVang(s, 'Tiền giám định kéo phần rủi ro này về lại trong tầm kiểm soát của bệnh viện', 6.15);
  L.notes(s, 'Mời lãnh đạo tự nhẩm với số của viện mình — con số họ tự ra thuyết phục hơn mọi con số ta đưa. '
    + 'Dòng ví dụ là giả định thuần tuý (20.000 × 5% × 1,5 triệu = 1,5 tỷ) để minh hoạ cách tính, nói rõ như vậy khi trình bày. '
    + 'Không hứa Tiền giám định xoá được toàn bộ khoản này: nó giảm phần lỗi nằm trong bộ quy tắc, và đưa phần còn lại ra ánh sáng sớm hơn.');
}

// ---------------------------------------------------------------- an toàn

function anToanSlide(pptx, ctx) {
  const s = slideMoi(pptx, ctx);
  dau(s, 'An toàn', 'Dữ liệu người bệnh và hệ thống HIS được bảo vệ thế nào');

  const o = [
    ['🏠', 'Đặt tại bệnh viện', 'Máy chủ đặt trong mạng nội bộ của viện. Dữ liệu chỉ gửi lên Cổng giám định BHXH — như quy trình hiện nay.'],
    ['🔒', 'Chỉ đọc HIS', 'Tài khoản chỉ có quyền đọc: không ghi, không cài trigger, không đổi cấu trúc cơ sở dữ liệu HIS.'],
    ['⚙️', 'Không làm nặng HIS', 'Truy vấn chạy nền theo lô; khảo sát chỉ mục các bảng HIS lớn trước khi bật.'],
    ['👥', 'Phân quyền và ghi vết', 'Mỗi người chỉ thấy chức năng được cấp; ghi lại ai nạp, ai gửi hồ sơ, ai tra cứu thẻ.'],
  ];
  const gap = 0.3;
  const cw = (BODY_W - gap) / 2;
  const ch = 1.95;
  o.forEach(([icon, h, b], i) => {
    const x = M + (i % 2) * (cw + gap);
    const y = 1.65 + Math.floor(i / 2) * (ch + 0.25);
    the(s, x, y, cw, ch);
    s.addShape('ellipse', { x: x + 0.3, y: y + 0.3, w: 0.8, h: 0.8, fill: { color: '25324A' }, line: { color: XANH, width: 1 } });
    bieuTuong(s, icon, x + 0.3, y + 0.3, 0.8, 0.8, 22);
    chu(s, h, { x: x + 1.35, y: y + 0.3, w: cw - 1.6, h: 0.5, fontSize: 18, bold: true, color: TRANG, valign: 'middle' });
    chu(s, b, { x: x + 1.35, y: y + 0.85, w: cw - 1.6, h: 0.95, fontSize: 13.5, color: CHU_PHU, valign: 'top', lineSpacing: 19 });
  });
  dongVang(s, 'Dữ liệu người bệnh ở lại trong bệnh viện', 6.15);
  L.notes(s, 'Ba nỗi lo của lãnh đạo: làm hỏng HIS, lộ dữ liệu người bệnh, làm chậm hệ thống. '
    + 'Phần mềm triển khai trên máy chủ của viện (Windows Server, hoặc Linux / Docker), đọc HIS bằng tài khoản chỉ đọc. '
    + 'Kết nối ra ngoài duy nhất là Cổng giám định BHXH — để tra thẻ và gửi hồ sơ, đúng như viện đang làm. '
    + 'Đừng hứa chứng nhận an toàn thông tin nếu chưa có văn bản.');
}

// ---------------------------------------------------------------- đề xuất thí điểm

function deXuatSlide(pptx, ctx) {
  const s = slideMoi(pptx, ctx);
  dau(s, 'Đề xuất', 'Thí điểm 4–8 tuần, đo bằng số liệu của chính viện');

  const lw = 6.9;
  const dong = [
    ['PHẠM VI', 'Kiểm hồ sơ XML 3176 chạy song song quy trình hiện tại; chưa bật tự gửi cổng.'],
    ['VIỆN CỬ', '1 đầu mối phòng KHTH  ·  1 người phụ trách danh mục BHYT  ·  1 cán bộ CNTT'],
    ['CHÚNG TÔI LÀM', 'Cài đặt tại viện, nạp danh mục, đào tạo người dùng, theo dõi kết quả cùng viện.'],
    ['CHI PHÍ', '………………………………'],
  ];
  dong.forEach(([lab, t], k) => {
    const y = 1.65 + k * 1.06;
    the(s, M, y, lw, 0.92);
    chu(s, lab, { x: M + 0.3, y, w: 1.9, h: 0.92, fontSize: 11, bold: true, color: XANH, charSpacing: 1, valign: 'middle' });
    chu(s, t, { x: M + 2.25, y, w: lw - 2.5, h: 0.92, fontSize: 14, color: TRANG, valign: 'middle', lineSpacing: 19 });
  });

  const rx = M + lw + 0.3;
  const rw = W - M - rx;
  the(s, rx, 1.65, rw, 4.1, { nhan: true });
  chu(s, 'ĐO GÌ ĐỂ QUYẾT ĐỊNH', { x: rx + 0.3, y: 1.85, w: rw - 0.6, h: 0.34, fontSize: 12, bold: true, color: VANG, charSpacing: 2 });
  const kpi = [
    'Tỷ lệ hồ sơ còn lỗi nghiêm trọng trước khi gửi',
    'Số hồ sơ bị cổng giám định trả về',
    'Thời gian sửa một hồ sơ lỗi',
    'Số hồ sơ tồn đọng theo tuổi',
  ];
  chu(s, kpi.map((t, k) => ({ text: t, options: { bullet: { indent: 16 }, breakLine: k < kpi.length - 1 } })), {
    x: rx + 0.3, y: 2.35, w: rw - 0.6, h: 2.2, fontSize: 15, color: TRANG, valign: 'top', paraSpaceAfter: 10, lineSpacing: 20,
  });
  chu(s, 'Kết thúc thí điểm: báo cáo số liệu để lãnh đạo quyết định nhân rộng.', {
    x: rx + 0.3, y: 4.75, w: rw - 0.6, h: 0.8, fontSize: 13, italic: true, color: CHU_PHU, valign: 'top', lineSpacing: 18,
  });
  dongVang(s, 'Hôm nay chỉ cần một quyết định: cho phép thí điểm', 6.15);
  L.notes(s, 'Slide quan trọng nhất với lãnh đạo: một quyết định nhỏ, rủi ro thấp, có thước đo rõ. '
    + 'Thí điểm chạy song song, chưa bật tự gửi cổng — nên không ảnh hưởng quy trình đang chạy. '
    + 'Bốn chỉ số theo tài liệu tổng hợp mục 10.3. ĐIỀN CHI PHÍ và rà lại dòng "Chúng tôi làm" theo đúng cam kết của công ty trước khi trình bày.');
}

// ---------------------------------------------------------------- nhận danh sách lỗi

// Nguồn: email hằng ngày lỗi thẻ BHYT + lỗi XML 3176 — sendXml3176XmlErrors.php; email tổng hợp
// sai sót y lệnh theo ngưỡng mức độ — OrderCheckNotifier.php. Cả hai gửi tới danh sách người nhận
// trong bảng email_receive_report.
function nhanLoiSlide(pptx, ctx) {
  const s = slideMoi(pptx, ctx);
  dau(s, 'Nhận lỗi', 'Danh sách lỗi đến tay người cần biết theo hai cách',
    'Người cần xem thì tự vào Cổng; người cần được nhắc thì hệ thống tự gửi email — không ai phải đi hỏi.');

  const cot = [
    ['🖥️', 'CHỦ ĐỘNG', 'Vào Cổng Tiền giám định', XANH, [
      ['Danh sách hồ sơ, danh sách vi phạm', 'Lọc theo khoa, mức độ, ngày; xuất Excel để giao việc.'],
      ['Tra cứu theo mã điều trị', 'Quét mã vạch, thấy mọi lỗi của một hồ sơ; in phiếu lỗi.'],
      ['Dashboard', 'Lỗi tập trung ở đâu, tồn đọng bao lâu — xem bất cứ lúc nào.'],
    ]],
    ['📧', 'THỤ ĐỘNG', 'Nhận email do hệ thống tự gửi', VANG, [
      ['Báo cáo lỗi hằng ngày', 'Tổng hợp lỗi thẻ BHYT và lỗi hồ sơ XML 3176 trong ngày.'],
      ['Bản tin sai sót y lệnh', 'Gửi các vi phạm mới từ mức độ do viện đặt trở lên.'],
      ['Danh sách người nhận do viện quản lý', 'Thêm, tắt người nhận ngay trên phần mềm.'],
    ]],
  ];
  const gap = 0.35;
  const cw = (BODY_W - gap) / 2;
  const cy = 2.0;
  const ch = 3.95;
  cot.forEach(([icon, tag, h, mau, ds], i) => {
    const x = M + i * (cw + gap);
    the(s, x, cy, cw, ch, { nhan: i === 1 });
    s.addShape('ellipse', { x: x + 0.3, y: cy + 0.28, w: 0.8, h: 0.8, fill: { color: '25324A' }, line: { color: mau, width: 1.25 } });
    bieuTuong(s, icon, x + 0.3, cy + 0.28, 0.8, 0.8, 22);
    chu(s, [
      { text: tag, options: { fontSize: 11.5, bold: true, color: mau, charSpacing: 2, breakLine: true } },
      { text: h, options: { fontSize: 19, bold: true, color: TRANG } },
    ], { x: x + 1.3, y: cy + 0.25, w: cw - 1.55, h: 0.86, valign: 'middle', lineSpacing: 24 });
    ds.forEach(([t, b], k) => {
      const y = cy + 1.35 + k * 0.84;
      chu(s, [
        { text: t, options: { fontSize: 15, bold: true, color: TRANG, breakLine: true } },
        { text: b, options: { fontSize: 13, color: CHU_PHU } },
      ], { x: x + 0.35, y, w: cw - 0.7, h: 0.8, valign: 'top', lineSpacing: 18 });
    });
  });
  dongVang(s, 'Lỗi tự tìm đến người phụ trách — không chờ cổng giám định báo về', 6.15);
  L.notes(s, 'Hai cách nhận lỗi. Chủ động: nghiệp vụ và KHTH vào Cổng Tiền giám định xem danh sách, tra theo mã điều trị, xem dashboard. '
    + 'Thụ động: hệ thống tự gửi email — báo cáo lỗi thẻ BHYT và lỗi XML 3176 hằng ngày, và bản tin các sai sót y lệnh mới (chỉ gửi từ mức độ viện chọn trở lên để không làm phiền). '
    + 'Lãnh đạo không cần đăng nhập vẫn nắm được tình hình qua email. Danh sách người nhận do viện tự thêm, tắt.');
}

// ---------------------------------------------------------------- cam kết

// Cam kết thương mại của công ty — rà lại với ban giám đốc công ty trước mỗi buổi trình bày.
function camKetSlide(pptx, ctx) {
  const s = slideMoi(pptx, ctx);
  dau(s, 'Cam kết', 'Đồng hành lâu dài cùng bệnh viện',
    'Quy định BHYT thay đổi liên tục — phần mềm phải thay đổi theo mà viện không phải lo.');

  const o = [
    ['🔄', 'Cập nhật quy tắc thường xuyên', 'Bám sát quy định mới của BHXH và Bộ Y tế. Cổng tự động cập nhật quy tắc, danh mục mã lỗi — không cần người can thiệp.', 'CAM KẾT'],
    ['🆓', 'Nâng cấp nền tảng miễn phí', 'Đã có kế hoạch nâng cấp để đáp ứng yêu cầu tốc độ ngày một cao khi số hồ sơ tăng.', 'CAM KẾT'],
    ['🤖', 'Ứng dụng AI tra soát lỗi', 'Tra soát lỗi thông minh: gợi ý nguyên nhân, cách sửa, phát hiện bất thường mà quy tắc cứng chưa bắt được.', 'LỘ TRÌNH'],
    ['🔗', 'Tích hợp HIS sâu hơn', 'Chủ động phát hiện lỗi logic ngay trên HIS — ví dụ sai thời gian, sai mã đối tượng — trước khi lập hồ sơ.', 'LỘ TRÌNH'],
  ];
  const gap = 0.3;
  const cw = (BODY_W - gap) / 2;
  const ch = 1.95;
  o.forEach(([icon, h, b, tag], i) => {
    const x = M + (i % 2) * (cw + gap);
    const y = 1.95 + Math.floor(i / 2) * (ch + 0.22);
    const loTrinh = tag === 'LỘ TRÌNH';
    const mau = loTrinh ? XANH : XANH_LA;
    the(s, x, y, cw, ch, { nhan: i === 1 });
    s.addShape('ellipse', { x: x + 0.3, y: y + 0.3, w: 0.8, h: 0.8, fill: { color: '25324A' }, line: { color: mau, width: 1 } });
    bieuTuong(s, icon, x + 0.3, y + 0.3, 0.8, 0.8, 22);
    chu(s, tag, { x: x + cw - 1.5, y: y + 0.18, w: 1.25, h: 0.28, fontSize: 9.5, bold: true, color: i === 1 ? VANG : mau, align: 'right', charSpacing: 1 });
    chu(s, h, { x: x + 1.35, y: y + 0.38, w: cw - 1.6, h: 0.45, fontSize: 18, bold: true, color: TRANG, valign: 'middle' });
    chu(s, b, { x: x + 1.35, y: y + 0.88, w: cw - 1.6, h: 0.95, fontSize: 13.5, color: CHU_PHU, valign: 'top', lineSpacing: 19 });
  });
  dongVang(s, 'Viện tập trung vào chuyên môn — phần mềm theo kịp quy định là việc của chúng tôi', 6.4);
  L.notes(s, 'Trả lời câu hỏi lãnh đạo hay đặt ra: "mua về rồi quy định đổi thì sao?". '
    + 'Hai cam kết hiện tại: cập nhật quy tắc thường xuyên — Cổng tự động cập nhật, không cần cán bộ của viện can thiệp; và nâng cấp nền tảng miễn phí, đã có kế hoạch nâng cấp để đáp ứng tốc độ khi khối lượng hồ sơ tăng. '
    + 'Hai hướng phát triển: ứng dụng AI để tra soát lỗi thông minh, và tích hợp HIS sâu hơn để chủ động bắt lỗi logic (thời gian, mã đối tượng…) ngay trên HIS. '
    + 'Nói rõ ô "Lộ trình" là kế hoạch, không phải chức năng đã có. Rà lại phạm vi "miễn phí" với ban giám đốc công ty trước khi trình bày.');
}

// ---------------------------------------------------------------- deck

function deckMoi(PptxGenJS, title) {
  const pptx = new PptxGenJS();
  pptx.layout = 'LAYOUT_WIDE';
  pptx.title = title;
  pptx.subject = 'Giới thiệu hệ thống Tiền giám định hồ sơ BHYT cho các bệnh viện';
  return { pptx, ctx: { slides: [] } };
}

function bia(pptx, ctx) {
  biaSlide(pptx, ctx, {
    pill: `${DON_VI} · Giải pháp Tiền giám định BHYT`,
    ten: [['Tiền ', TRANG], ['giám định', XANH]],
    mo: 'Kiểm tra hồ sơ khám chữa bệnh BHYT ngay tại bệnh viện\ntrước khi ký số và gửi Cổng giám định BHXH',
    tieuDeLuong: 'Tiền giám định đặt điểm kiểm soát trước khi hồ sơ rời bệnh viện',
    luong: [
      { icon: '🏥', dau: 'Bệnh viện', than: 'Bác sĩ ra y lệnh trên HIS,\nxuất hồ sơ XML 3176' },
      { icon: '🔍', dau: 'Tiền giám định', than: 'Kiểm lỗi, tra thẻ BHYT,\nđối chiếu danh mục', nhan: true },
      { icon: '🔏', dau: 'Ký số', than: 'HSM hoặc USB Token,\nkhi hết lỗi nghiêm trọng' },
      { icon: '🌐', dau: 'Cổng BHXH', than: 'Tiếp nhận hồ sơ\nđã sạch lỗi' },
    ],
    chuan: 'Lỗi được phát hiện sớm và sửa tại nơi phát sinh — trên HIS, trước khi gửi',
    speaker: 'Slide mở đầu, nói trong khoảng một phút. Hồ sơ BHYT đi từ HIS của bệnh viện tới Cổng giám định BHXH; '
      + 'Tiền giám định là trạm kiểm tra nằm giữa: kiểm từng hồ sơ XML 3176 theo bộ quy tắc, tra thẻ BHYT, đối chiếu danh mục. '
      + 'Lỗi nghiêm trọng chặn ký số và gửi cho tới khi được sửa — và sửa trên HIS, tại khoa phòng phát sinh, rồi nạp lại. '
      + 'Hệ thống đang vận hành tại Bệnh viện Bạch Mai.',
  });
}

function anhYLenh(pptx, ctx) {
  anhSlide(pptx, ctx, {
    kicker: 'Trọng tâm 1',
    title: 'Kiểm sai sót y lệnh — bắt lỗi khi người làm còn trong ca',
    diem: [
      ['⏱️', 'Gần thời gian thực', 'Quét HIS mỗi 60 giây, chỉ đọc. Ví dụ: chưa khai chứng chỉ hành nghề, ra viện trước vào viện.'],
      ['🎯', 'Lọc đúng người, đúng khoa', 'Theo mức độ, khoa thực hiện, bác sĩ, loại dịch vụ, cơ sở KCB.'],
      ['✔️', 'Khép vòng xử lý', 'Đánh dấu Đã xử lý hoặc Bỏ qua từng vi phạm; xuất Excel để giao khoa.'],
    ],
    anh: 'anh-01-danh-sach-vi-pham.png',
    chuThich: 'Màn Danh sách vi phạm — dữ liệu trong ảnh là dữ liệu mẫu',
    speaker: 'Đây là điểm kiểm soát sớm. Lỗi hiện ra khi bác sĩ, kỹ thuật viên còn trong ca, sửa trên HIS mất vài phút thay vì truy lại sau nhiều tuần. Bộ quy tắc do Hội đồng chuyên môn của viện chọn và tinh chỉnh; bắt đầu với ít quy tắc, giảm dương tính giả trước rồi mới mở rộng. Nếu được hỏi về Bạch Mai: phần này đang ở giai đoạn làm sạch danh mục để giảm cảnh báo giả, chưa giao khoa xử lý thường quy.',
  });
}

function anhXml(pptx, ctx) {
  anhSlide(pptx, ctx, {
    kicker: 'Trọng tâm 2',
    title: 'Kiểm hồ sơ XML 3176 — chặn lỗi trước khi ký số',
    diem: [
      ['📥', 'Nạp tự động', 'Theo dõi thư mục HIS xuất ra, nhận hồ sơ trong vài giây; hoặc kéo thả tệp.'],
      ['🧪', 'Kiểm từng hồ sơ', '12 bảng XML có bộ kiểm riêng, kiểm chéo toàn hồ sơ, tra thẻ BHYT, đối chiếu danh mục.'],
      ['🚦', 'Tự ký, tự gửi', 'Chỉ hồ sơ hết lỗi nghiêm trọng mới được ký và gửi; trạng thái hiện ngay trên danh sách.'],
    ],
    anh: 'anh-03-danh-sach-ho-so-xml3176.png',
    chuThich: 'Màn Danh sách hồ sơ XML 3176 — dòng đỏ là hồ sơ còn lỗi',
    speaker: 'Điểm kiểm soát chính. Danh sách cho thấy hồ sơ nào lỗi (dòng đỏ), đã ký chưa, đã gửi chưa. Mức lỗi của từng mã do viện tự đặt ở danh mục mã lỗi — mã nào chặn, mã nào chỉ cảnh báo.',
  });
}

function anhTraLoi(pptx, ctx) {
  anhSlide(pptx, ctx, {
    kicker: 'Phản hồi về khoa',
    title: 'Một mã điều trị — thấy mọi lỗi của hồ sơ',
    diem: [
      ['🧾', 'Gom lỗi một chỗ', 'Sai sót y lệnh, lỗi tra thẻ BHYT, lỗi XML 3176 của cùng một hồ sơ trên một trang.'],
      ['📷', 'Quét mã vạch', 'Quét mã trên phiếu, bệnh án để mở ngay hồ sơ, không phải gõ tay.'],
      ['🖨️', 'In phiếu lỗi', 'In phiếu giao khoa phát sinh sửa trên HIS — có căn cứ, đúng đầu mối.'],
    ],
    anh: 'anh-02-tra-cuu-loi-ho-so.png',
    chuThich: 'Màn Tra cứu lỗi hồ sơ theo mã điều trị',
    speaker: 'Đây là công cụ của khoa và phòng KHTH khi trao đổi về một hồ sơ cụ thể: mọi lỗi nằm trên một trang, in ra được để giao việc.',
  });
}

function anhHoTro(pptx, ctx) {
  anhSlide(pptx, ctx, {
    kicker: 'Hỗ trợ nghiệp vụ',
    title: 'Thẻ BHYT, tiền cùng chi trả, chứng từ và danh mục',
    diem: [
      ['💳', 'Tra thẻ hàng loạt', 'Tra trên cổng BHXH, đọc mã kết quả để phát hiện thẻ sai, hết hạn.'],
      ['💰', 'Tiền cùng chi trả', 'Lũy kế từ dữ liệu các cơ sở KCB trên cổng, so với ngưỡng cả năm.'],
      ['✍️', 'Chứng từ điện tử', 'Ký số và gửi chứng từ theo Phụ lục 02, theo dõi trạng thái gửi.'],
      ['📚', 'Danh mục TT12/2026', 'Nạp theo biểu mẫu, kiểm, ký và gửi danh mục lên cổng.'],
    ],
    anh: 'anh-05-ket-qua-mcct.png',
    chuThich: 'Kết quả tra cứu tiền cùng chi trả — dữ liệu mẫu',
    speaker: 'Các chức năng hỗ trợ dùng chung hạ tầng ký số và gửi cổng. Tra cứu tiền cùng chi trả hữu ích cho Phòng Tài chính kế toán và Phòng BHYT khi xác định người bệnh đã đủ ngưỡng miễn cùng chi trả hay chưa.',
  });
}

// Bản đầy đủ: lãnh đạo BV, lãnh đạo KHTH, nhân viên nghiệp vụ — khoảng 30 phút.
function deckDayDu(PptxGenJS) {
  const { pptx, ctx } = deckMoi(PptxGenJS, 'Giải pháp Tiền giám định — Giới thiệu hệ thống');
  bia(pptx, ctx);
  vanDeSlide(pptx, ctx);
  haiDiemSlide(pptx, ctx);
  conSoSlide(pptx, ctx);
  ketQuaSlide(pptx, ctx);
  quyRaTienSlide(pptx, ctx);
  chucNangSlide(pptx, ctx);
  quyTrinhSlide(pptx, ctx);
  anhYLenh(pptx, ctx);
  anhXml(pptx, ctx);
  anhTraLoi(pptx, ctx);
  nhanLoiSlide(pptx, ctx);
  anhHoTro(pptx, ctx);
  dashboardSlide(pptx, ctx);
  vaiTroSlide(pptx, ctx);
  nguyenTacSlide(pptx, ctx);
  anToanSlide(pptx, ctx);
  trienKhaiSlide(pptx, ctx);
  camKetSlide(pptx, ctx);
  deXuatSlide(pptx, ctx);
  ketSlide(pptx, ctx);
  chanTrang(ctx);
  return pptx;
}

// Bản cho lãnh đạo bệnh viện: khoảng 15 phút, XML 3176 là trọng tâm duy nhất, kiểm y lệnh là
// phần mở rộng. Không có slide thao tác màn hình.
function deckLanhDao(PptxGenJS) {
  const { pptx, ctx } = deckMoi(PptxGenJS, 'Giải pháp Tiền giám định — Dành cho lãnh đạo bệnh viện');
  bia(pptx, ctx);
  vanDeSlide(pptx, ctx);
  haiDiemSlide(pptx, ctx, { chiXml: true });
  ketQuaSlide(pptx, ctx);
  quyRaTienSlide(pptx, ctx);
  dashboardSlide(pptx, ctx);
  nhanLoiSlide(pptx, ctx);
  anToanSlide(pptx, ctx);
  trienKhaiSlide(pptx, ctx);
  camKetSlide(pptx, ctx);
  deXuatSlide(pptx, ctx);
  ketSlide(pptx, ctx);
  chanTrang(ctx);
  return pptx;
}

module.exports = { deckDayDu, deckLanhDao };
