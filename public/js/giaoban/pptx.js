/**
 * Dung tep PPTX tu mo ta deck cua man trinh chieu giao ban.
 *
 * Tep nay KHONG duoc doc DOM: moi thu no can den tu tham so. Nho vay no chay duoc ca trong
 * Node de kiem chung tu dong — thu khong lam duoc neu ma nam trong Blade.
 *
 * Moi slide dung bang doi tuong PowerPoint THAT (bang, hop van ban, bieu do) chu khong phai
 * anh: nguoi nhan con phai sua lai noi dung va chieu o cuoc hop khac.
 */
(function (root) {
  'use strict';

  var W = 10, H = 5.625;       // khung 16:9 tinh bang inch
  var LE = 0.4;                // le trai/phai
  var RONG = W - LE * 2;
  var Y_NOI_DUNG = 0.95;       // day cua khoi tieu de

  /** htmlspecialchars nguoc. May chu da ma hoa 5 ky tu nay truoc khi tra ve. */
  function giaiMa(s) {
    return String(s === null || s === undefined ? '' : s)
      .replace(/&lt;/g, '<').replace(/&gt;/g, '>')
      .replace(/&quot;/g, '"').replace(/&#0?39;/g, "'")
      .replace(/&amp;/g, '&');
  }

  /**
   * Doi mot doan HTML thanh chu thuan cho hop van ban PowerPoint.
   * Ghi chu den tu trinh soan thao nen day the <p> va &nbsp; — de nguyen thi PPTX hien ca the.
   *
   * Luoc the TRUOC, giai ma entity SAU: nguoc lai thi chuoi nguoi dung go tay nhu &lt;p&gt;
   * se bi hieu nham thanh the roi bi xoa mat.
   */
  function chuThuan(html) {
    return giaiMa(String(html === null || html === undefined ? '' : html)
      .replace(/<[^>]*>/g, ''))
      .replace(/&nbsp;/g, ' ')
      .replace(/ /g, ' ')
      .replace(/[ \t]+/g, ' ')
      .trim();
  }

  function so(v) {
    return v === null || v === undefined || v === ''
      ? '—' : String(Math.round(Number(v) * 100) / 100);
  }

  function laSo(s) { return /^-?[\d.,]+%?$/.test(s); }

  /** Mau cua o so: theo nhan teal/amber neu co, khong thi mau chu nhan manh. */
  function mauO(m, mau) {
    if (m === 'teal') return mau.teal;
    if (m === 'amber') return mau.amber;
    return mau.strong;
  }

  function mauTheoPct(pct, mau) {
    return pct >= 90 ? mau.red : pct >= 80 ? mau.amber : pct >= 60 ? mau.teal : mau.blue;
  }

  /** Khung chung: nen, tieu de trai, ngay phai, duong ke duoi tieu de. */
  function khungSlide(pptx, s, mau) {
    var sl = pptx.addSlide();
    sl.background = { color: mau.bg };
    var tieuDe = s.tieuDe + (s.badge ? '   ' + s.badge.chu : '');
    sl.addText(tieuDe, { x: LE, y: 0.22, w: RONG - 2.6, h: 0.5,
      fontSize: 24, bold: true, color: mau.strong, valign: 'middle' });
    sl.addText('Giao ban ' + s.ngay, { x: W - LE - 2.6, y: 0.28, w: 2.6, h: 0.38,
      fontSize: 11, color: mau.muted, align: 'right', valign: 'middle' });
    sl.addShape(pptx.ShapeType.rect, { x: LE, y: 0.82, w: RONG, h: 0.014,
      fill: { color: mau.line2 } });
    return sl;
  }

  function oTieuDe(chu, mau) {
    return { text: chu, options: { align: 'center', bold: true, color: mau.muted,
      fill: { color: mau.panel2 } } };
  }

  /**
   * Bang tieu chi: moi dong hai cap "Tieu chi | So lieu", giong het tren man chieu.
   * So chi tieu le thi cap cuoi de trong nhung van co vien.
   */
  function bangTieuChi(sl, items, mau, y, cao) {
    var CAP = 2, soDong = Math.ceil(items.length / CAP), rows = [], r, k;

    var head = [];
    for (k = 0; k < CAP; k++) { head.push(oTieuDe('TIÊU CHÍ', mau)); head.push(oTieuDe('SỐ LIỆU', mau)); }
    rows.push(head);

    for (r = 0; r < soDong; r++) {
      var hang = [];
      for (k = 0; k < CAP; k++) {
        var it = items[r * CAP + k];
        if (!it) {
          hang.push({ text: '', options: {} });
          hang.push({ text: '', options: {} });
          continue;
        }
        var v = so(it.giaTri);
        hang.push({ text: it.nhan, options: { align: 'left', color: mau.strong } });
        hang.push({ text: v, options: laSo(v)
          ? { align: 'right', bold: true, color: mauO(it.mau, mau) }
          : { align: 'left', color: mau.txt2 } });
      }
      rows.push(hang);
    }

    var coChu = soDong <= 6 ? 14 : (soDong <= 10 ? 12 : (soDong <= 14 ? 10 : 9));
    sl.addTable(rows, {
      x: LE, y: y, w: RONG, h: cao,
      colW: [RONG * 0.3, RONG * 0.2, RONG * 0.3, RONG * 0.2],
      border: { type: 'solid', pt: 0.5, color: mau.line2 },
      fontSize: coChu, color: mau.txt2, valign: 'middle', autoPage: false
    });
  }

  function veTongQuan(pptx, s, mau) {
    var sl = khungSlide(pptx, s, mau);
    var y = Y_NOI_DUNG;

    if (s.phuDe) {
      sl.addText(s.phuDe, { x: LE, y: y, w: RONG, h: 0.26, fontSize: 10, color: mau.muted });
      y += 0.3;
    }

    if (s.kipTruc.length) {
      var dong = s.kipTruc.map(function (v) {
        return v.viTri + ': ' + v.nguoi.map(function (n) {
          return n.ten + (n.dienThoai ? ' (' + n.dienThoai + ')' : '');
        }).join(', ');
      }).join('    •    ');
      sl.addText('KÍP TRỰC LÃNH ĐẠO', { x: LE, y: y, w: RONG, h: 0.24, fontSize: 10, color: mau.muted });
      sl.addText(dong, { x: LE, y: y + 0.24, w: RONG, h: 0.34, fontSize: 12, color: mau.strong });
      y += 0.66;
    }

    if (s.items.length) {
      var caoBang = Math.min(2.6, 0.32 * (Math.ceil(s.items.length / 2) + 1));
      bangTieuChi(sl, s.items, mau, y, caoBang);
      y += caoBang + 0.18;
    } else {
      sl.addText('CHƯA ĐÁNH DẤU TIÊU CHÍ NÀO', { x: LE, y: y, w: RONG, h: 0.3,
        fontSize: 12, bold: true, color: mau.amber });
      y += 0.36;
    }

    sl.addText('Ô BẮT BUỘC CÒN TRỐNG:  ' + s.canhBao.chu, { x: LE, y: y, w: RONG, h: 0.32,
      fontSize: 11, color: s.canhBao.dat ? mau.teal : mau.red });
    // Ghi chu chung KHONG in o day: no da co slide rieng do moTaDeck sinh ra. In ca hai cho
    // la noi dung lap lai.
    return sl;
  }

  function veDieuTri(pptx, s, mau) {
    var sl = khungSlide(pptx, s, mau);

    var head = [oTieuDe('KHOA PHÒNG', mau)];
    s.cot.forEach(function (c) { head.push(oTieuDe(c, mau)); });

    var rows = [head];
    s.dong.forEach(function (d) {
      var hang = [{ text: d.ten, options: { align: 'left', color: mau.strong } }];
      d.o.forEach(function (v) {
        var t = so(v);
        hang.push({ text: t, options: { align: laSo(t) ? 'right' : 'left', color: mau.txt2 } });
      });
      rows.push(hang);
    });

    var hangTong = [{ text: 'TỔNG CỘNG', options: { align: 'left', bold: true, color: mau.strong,
      fill: { color: mau.panel2 } } }];
    s.tong.forEach(function (v) {
      var t = so(v);
      hangTong.push({ text: t, options: { align: laSo(t) ? 'right' : 'left', bold: true,
        color: mau.strong, fill: { color: mau.panel2 } } });
    });
    rows.push(hangTong);

    var soCot = s.cot.length + 1;
    var wTen = Math.min(2.6, RONG * 0.28);
    var wSo = (RONG - wTen) / (soCot - 1);
    var colW = [wTen], i;
    for (i = 1; i < soCot; i++) colW.push(wSo);

    sl.addTable(rows, {
      x: LE, y: Y_NOI_DUNG, w: RONG,
      colW: colW,
      border: { type: 'solid', pt: 0.5, color: mau.line2 },
      fontSize: soCot <= 8 ? 12 : (soCot <= 14 ? 10 : 8),
      color: mau.txt2, valign: 'middle', autoPage: false
    });
    return sl;
  }

  function veKhoa(pptx, s, mau) {
    var sl = khungSlide(pptx, s, mau);
    var y = Y_NOI_DUNG;

    if (s.lechCanDoi) {
      sl.addText('▲ Lệch cân đối: ' + so(s.lechCanDoi), { x: LE, y: y, w: RONG, h: 0.26,
        fontSize: 10, color: mau.amber });
      y += 0.3;
    }

    if (s.items.length) {
      var caoBang = Math.min(2.8, 0.3 * (Math.ceil(s.items.length / 2) + 1));
      bangTieuChi(sl, s.items, mau, y, caoBang);
      y += caoBang + 0.18;
    }

    s.khoiChuoi.forEach(function (k) {
      if (y > H - 0.6) return; // het cho tren slide, bo qua phan con lai
      sl.addText(k.nhan, { x: LE, y: y, w: RONG, h: 0.22, fontSize: 10, color: mau.amber });
      // chuThuan chu khong phai giaiMa: chi tieu chuoi cung do trinh soan thao nhap nen co
      // the day the <p>/<span>. giaiMa khong luoc the, de nguyen thi PPTX hien ca the ra chu.
      sl.addText(chuThuan(k.noiDung), { x: LE, y: y + 0.22, w: RONG, h: 0.5,
        fontSize: 11, color: mau.txt2, valign: 'top' });
      y += 0.78;
    });

    // Ghi chu khoa KHONG in o day: no da co slide rieng do moTaDeck sinh ra.
    return sl;
  }

  /**
   * Slide ghi chu. Deck da cat san trang o tang mo ta nen o day chi do chu ra.
   * `khungSlide` ghep `badge` vao tieu de, nen so trang di duong badge.
   */
  function veGhiChu(pptx, s, mau) {
    var sKhung = { tieuDe: s.tieuDe, ngay: s.ngay,
      badge: s.phuTrang ? { chu: '(' + s.phuTrang + ')' } : null };
    var sl = khungSlide(pptx, sKhung, mau);
    var dong = s.doan.map(function (d) { return chuThuan(d); })
      .filter(function (d) { return d !== ''; });
    sl.addText(dong.join('\n'), { x: LE, y: Y_NOI_DUNG, w: RONG, h: H - Y_NOI_DUNG - 0.3,
      fontSize: 12, color: mau.txt2, valign: 'top', lineSpacingMultiple: 1.1 });
    return sl;
  }

  function veCongSuat(pptx, s, mau) {
    var sl = khungSlide(pptx, s, mau);
    var coDonut = !!s.donut, coKhoa = s.theoKhoa.length > 0;

    if (coDonut) {
      sl.addChart(pptx.ChartType.doughnut,
        [{ name: 'Công suất giường', labels: ['Đang dùng', 'Trống'],
           values: [s.donut.dung, s.donut.trong] }],
        { x: LE, y: Y_NOI_DUNG, w: coKhoa ? 3.3 : RONG, h: H - Y_NOI_DUNG - 0.3,
          holeSize: 55, showLegend: true, legendPos: 'b', legendColor: mau.muted,
          showValue: true, dataLabelColor: mau.bg, dataLabelFontSize: 11,
          chartColors: [mauTheoPct(s.donut.pct, mau), mau.muted],
          title: s.donut.pct + '% — ' + s.donut.dung + '/' + s.donut.tong + ' giường',
          showTitle: true, titleColor: mau.strong, titleFontSize: 12 });
    }

    if (coKhoa) {
      var x = coDonut ? LE + 3.5 : LE;
      sl.addChart(pptx.ChartType.bar,
        [{ name: 'Công suất %', labels: s.theoKhoa.map(function (k) { return k.ten; }),
           values: s.theoKhoa.map(function (k) { return k.pct; }) }],
        { x: x, y: Y_NOI_DUNG, w: W - LE - x, h: H - Y_NOI_DUNG - 0.3,
          barDir: 'bar', valAxisMaxVal: 100, showValue: true, dataLabelColor: mau.txt2,
          dataLabelFontSize: 9, catAxisLabelColor: mau.txt2, catAxisLabelFontSize: 9,
          valAxisLabelColor: mau.muted, valAxisLabelFontSize: 9,
          chartColors: [mau.blue], showLegend: false });
    }
    return sl;
  }

  function veSlide(pptx, s, mau) {
    if (s.loai === 'tong-quan') return veTongQuan(pptx, s, mau);
    if (s.loai === 'dieu-tri') return veDieuTri(pptx, s, mau);
    if (s.loai === 'khoa') return veKhoa(pptx, s, mau);
    if (s.loai === 'ghi-chu') return veGhiChu(pptx, s, mau);
    return veCongSuat(pptx, s, mau);
  }

  /**
   * `PptxGenJS` nhan qua tham so chu khong doc bien toan cuc, de Node truyen ban da nap vao.
   */
  function xuatPptx(deck, mau, tenTep, PptxGenJS) {
    var pptx = new PptxGenJS();
    pptx.layout = 'LAYOUT_16x9';
    deck.forEach(function (s) { veSlide(pptx, s, mau); });
    return pptx.writeFile({ fileName: tenTep });
  }

  var api = { xuatPptx: xuatPptx, giaiMa: giaiMa, chuThuan: chuThuan };
  if (typeof module === 'object' && module.exports) module.exports = api;
  else root.GiaoBanPptx = api;
})(typeof window !== 'undefined' ? window : this);
