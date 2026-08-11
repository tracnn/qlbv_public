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

  /*
   * === Uoc luong chieu cao ===
   *
   * PowerPoint xep khoi theo NOI DUNG, con ma nay xep theo toa do tinh san. Chieu cao khai bao
   * cho bang va hop van ban chi la muc TOI THIEU: o nao chu dai phai xuong dong thi hang do cao
   * len, va khoi dat ngay ben duoi bi de len.
   *
   * Do la loi da xay ra that: slide Khoa Kham benh co tieu chi "Trong do luot kham tai PK Son
   * Luong" xuong 2 dong, bang no tu 1,80" len 2,42", con khoi "BS truc" dat cung o 2,93" nen bi
   * bang de len 0,44".
   *
   * Nen truoc khi xep phai uoc luong chieu cao THAT. Cac he so duoi day do doi chieu voi tep
   * PPTX that sinh ra tu du lieu ngay 11/08/2026.
   */

  var LE_TRONG_O = 0.14;      // le trai + phai ben trong mot o bang
  var HE_SO_RONG_KY_TU = 0.5; // be rong trung binh mot ky tu, tinh theo co chu
  var HE_SO_CAO_DONG = 1.25;  // chieu cao mot dong, tinh theo co chu
  var DEM_O = 0.08;           // le tren + duoi cua mot hang bang

  /** So dong mot doan chu chiem khi bi bo vao khung rong `rong` inch o co chu `co` pt. */
  function soDong(chu, rong, co) {
    var dung = Math.max(0.2, rong);
    var rongKyTu = co * HE_SO_RONG_KY_TU / 72;
    var moiDong = Math.max(1, Math.floor(dung / rongKyTu));
    return Math.max(1, Math.ceil(String(chu).length / moiDong));
  }

  /** Chieu cao that cua mot hop van ban, ke ca khi noi dung co nhieu doan. */
  function caoHopChu(chu, rong, co) {
    var tong = 0;
    String(chu).split('\n').forEach(function (d) { tong += soDong(d, rong, co); });
    return Math.max(1, tong) * co * HE_SO_CAO_DONG / 72 + 0.06;
  }

  /** Chieu cao that cua mot hang bang: theo o phai xuong nhieu dong nhat. */
  function caoHangBang(oChu, colW, co) {
    var nhieuNhat = 1;
    oChu.forEach(function (t, i) {
      var d = soDong(t, (colW[i] || 1) - LE_TRONG_O, co);
      if (d > nhieuNhat) nhieuNhat = d;
    });
    return nhieuNhat * co * HE_SO_CAO_DONG / 72 + DEM_O;
  }

  /** Khung chung: nen, tieu de trai, ngay phai, duong ke duoi tieu de. */
  function khungSlide(pptx, s, mau) {
    var sl = pptx.addSlide();
    sl.background = { color: mau.bg };
    var tieuDe = s.tieuDe + (s.badge ? '   ' + s.badge.chu : '');
    // Tieu de dai (ten khoa dai + " — Ghi chu (1/2)") xuong 2 dong se de len duong ke va mep
    // tren noi dung, vi hai thu do dat o toa do co dinh. Co chu tu rut cho tieu de luon 1 dong.
    var rongTieuDe = RONG - 2.6;
    var coTieuDe = 24;
    while (coTieuDe > 14 && soDong(tieuDe, rongTieuDe, coTieuDe) > 1) coTieuDe -= 2;
    sl.addText(tieuDe, { x: LE, y: 0.22, w: rongTieuDe, h: 0.5,
      fontSize: coTieuDe, bold: true, color: mau.strong, valign: 'middle' });
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

  var CAP_TIEU_CHI = 2;   // so cap "Tieu chi | So lieu" tren mot hang
  var COT_TIEU_CHI = [RONG * 0.3, RONG * 0.2, RONG * 0.3, RONG * 0.2];

  function coChuTieuChi(soHang) {
    return soHang <= 6 ? 14 : (soHang <= 10 ? 12 : (soHang <= 14 ? 10 : 9));
  }

  /** Chu cua tung o tren mot hang, dung de do chieu cao va de dung o that. */
  function chuHangTieuChi(items, r) {
    var chu = [];
    for (var k = 0; k < CAP_TIEU_CHI; k++) {
      var it = items[r * CAP_TIEU_CHI + k];
      chu.push(it ? it.nhan : '');
      chu.push(it ? so(it.giaTri) : '');
    }
    return chu;
  }

  /**
   * Chieu cao THAT cua tung hang bang tieu chi, ke ca hang tieu de.
   *
   * Phai tra ve TUNG HANG chu khong phai tong: PptxGenJS chia deu chieu cao bang cho cac hang,
   * nen dua tong vao thi hang nao can cao hon muc trung binh van no ra va lai de len khoi duoi.
   */
  function caoHangBangTieuChi(items, co) {
    var soHang = Math.ceil(items.length / CAP_TIEU_CHI), ds = [];
    ds.push(caoHangBang(['TIÊU CHÍ', 'SỐ LIỆU', 'TIÊU CHÍ', 'SỐ LIỆU'], COT_TIEU_CHI, co));
    for (var r = 0; r < soHang; r++) ds.push(caoHangBang(chuHangTieuChi(items, r), COT_TIEU_CHI, co));
    return ds;
  }

  function tong(ds) { return ds.reduce(function (a, b) { return a + b; }, 0); }

  /** Chieu cao THAT cua bang tieu chi. */
  function caoBangTieuChi(items, co) {
    return tong(caoHangBangTieuChi(items, co));
  }

  /**
   * Chia danh sach tieu chi thanh cac trang vua chieu cao cho phep.
   * Luon lay it nhat mot hang moi trang de khong lap vo han khi mot hang cao hon ca khung.
   */
  function chiaTrangTieuChi(items, caoCho, co) {
    var soHang = Math.ceil(items.length / CAP_TIEU_CHI);
    var caoTieuDe = caoHangBang(['TIÊU CHÍ', 'SỐ LIỆU', 'TIÊU CHÍ', 'SỐ LIỆU'], COT_TIEU_CHI, co);
    var trang = [], hienTai = [], cao = caoTieuDe;
    for (var r = 0; r < soHang; r++) {
      var caoHang = caoHangBang(chuHangTieuChi(items, r), COT_TIEU_CHI, co);
      if (hienTai.length && cao + caoHang > caoCho) {
        trang.push(hienTai); hienTai = []; cao = caoTieuDe;
      }
      for (var k = 0; k < CAP_TIEU_CHI; k++) {
        var it = items[r * CAP_TIEU_CHI + k];
        if (it) hienTai.push(it);
      }
      cao += caoHang;
    }
    if (hienTai.length) trang.push(hienTai);
    return trang.length ? trang : [[]];
  }

  /**
   * Bang tieu chi: moi dong hai cap "Tieu chi | So lieu", giong het tren man chieu.
   * So chi tieu le thi cap cuoi de trong nhung van co vien.
   *
   * Tra ve chieu cao THAT de nguoi goi biet dat khoi ke tiep o dau.
   */
  function bangTieuChi(sl, items, mau, y, co) {
    var soHang = Math.ceil(items.length / CAP_TIEU_CHI), rows = [], r, k;

    var head = [];
    for (k = 0; k < CAP_TIEU_CHI; k++) {
      head.push(oTieuDe('TIÊU CHÍ', mau)); head.push(oTieuDe('SỐ LIỆU', mau));
    }
    rows.push(head);

    for (r = 0; r < soHang; r++) {
      var hang = [];
      for (k = 0; k < CAP_TIEU_CHI; k++) {
        var it = items[r * CAP_TIEU_CHI + k];
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

    var caoHang = caoHangBangTieuChi(items, co);
    sl.addTable(rows, {
      x: LE, y: y, w: RONG, h: tong(caoHang),
      colW: COT_TIEU_CHI, rowH: caoHang,
      border: { type: 'solid', pt: 0.5, color: mau.line2 },
      fontSize: co, color: mau.txt2, valign: 'middle', autoPage: false
    });
    return tong(caoHang);
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
      var caoKip = caoHopChu(dong, RONG, 12);
      sl.addText('KÍP TRỰC LÃNH ĐẠO', { x: LE, y: y, w: RONG, h: 0.24, fontSize: 10, color: mau.muted });
      sl.addText(dong, { x: LE, y: y + 0.24, w: RONG, h: caoKip, fontSize: 12, color: mau.strong });
      y += 0.24 + caoKip + 0.1;
    }

    if (s.items.length) {
      // Chua chieu cao cho dong canh bao ben duoi, khong thi bang no ra la de len no.
      var coTQ = coChuTieuChi(Math.ceil(s.items.length / CAP_TIEU_CHI));
      var trangTQ = chiaTrangTieuChi(s.items, H - y - 0.3 - 0.42, coTQ);
      y += bangTieuChi(sl, trangTQ[0], mau, y, coTQ) + 0.18;
      // Tieu chi khong vua mot slide thi phan con lai xuong cac slide noi tiep.
      for (var t = 1; t < trangTQ.length; t++) {
        var slT = khungSlide(pptx, { tieuDe: s.tieuDe, ngay: s.ngay,
          badge: { chu: '(' + (t + 1) + '/' + trangTQ.length + ')' } }, mau);
        bangTieuChi(slT, trangTQ[t], mau, Y_NOI_DUNG, coTQ);
      }
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
    var soCot = s.cot.length + 1;
    var wTen = Math.min(2.6, RONG * 0.28);
    var wSo = (RONG - wTen) / (soCot - 1);
    var colW = [wTen], i;
    for (i = 1; i < soCot; i++) colW.push(wSo);
    var co = soCot <= 8 ? 12 : (soCot <= 14 ? 10 : 8);

    var chuTieuDe = ['KHOA PHÒNG'].concat(s.cot);
    var caoTieuDe = caoHangBang(chuTieuDe, colW, co);
    var chuTong = ['TỔNG CỘNG'].concat(s.tong.map(so));
    var caoTong = caoHangBang(chuTong, colW, co);

    // Chia khoa thanh cac trang vua chieu cao; hang tieu de lap lai moi trang, hang TONG CONG
    // chi o trang cuoi. Nhieu khoa qua ma khong chia thi bang tran xuong duoi day slide.
    var caoCho = H - Y_NOI_DUNG - 0.25;
    var trang = [], hienTai = [], cao = caoTieuDe;
    s.dong.forEach(function (d) {
      var caoHang = caoHangBang([d.ten].concat(d.o.map(so)), colW, co);
      if (hienTai.length && cao + caoHang + caoTong > caoCho) {
        trang.push(hienTai); hienTai = []; cao = caoTieuDe;
      }
      hienTai.push(d); cao += caoHang;
    });
    if (hienTai.length) trang.push(hienTai);
    if (!trang.length) trang = [[]];

    var sl = null;
    trang.forEach(function (dong, ti) {
      var phuTrang = trang.length > 1 ? ((ti + 1) + '/' + trang.length) : '';
      sl = khungSlide(pptx, { tieuDe: s.tieuDe, ngay: s.ngay,
        badge: phuTrang ? { chu: '(' + phuTrang + ')' } : null }, mau);

      var head = [oTieuDe('KHOA PHÒNG', mau)];
      s.cot.forEach(function (c) { head.push(oTieuDe(c, mau)); });
      var rows = [head];

      dong.forEach(function (d) {
        var hang = [{ text: d.ten, options: { align: 'left', color: mau.strong } }];
        d.o.forEach(function (v) {
          var t = so(v);
          hang.push({ text: t, options: { align: laSo(t) ? 'right' : 'left', color: mau.txt2 } });
        });
        rows.push(hang);
      });

      if (ti === trang.length - 1) {
        var hangTong = [{ text: 'TỔNG CỘNG', options: { align: 'left', bold: true,
          color: mau.strong, fill: { color: mau.panel2 } } }];
        s.tong.forEach(function (v) {
          var t = so(v);
          hangTong.push({ text: t, options: { align: laSo(t) ? 'right' : 'left', bold: true,
            color: mau.strong, fill: { color: mau.panel2 } } });
        });
        rows.push(hangTong);
      }

      var caoHang = [caoTieuDe];
      dong.forEach(function (d) { caoHang.push(caoHangBang([d.ten].concat(d.o.map(so)), colW, co)); });
      if (ti === trang.length - 1) caoHang.push(caoTong);

      sl.addTable(rows, {
        x: LE, y: Y_NOI_DUNG, w: RONG, h: tong(caoHang),
        colW: colW, rowH: caoHang,
        border: { type: 'solid', pt: 0.5, color: mau.line2 },
        fontSize: co, color: mau.txt2, valign: 'middle', autoPage: false
      });
    });
    return sl;
  }

  /** Chieu cao khoi mot chi tieu chuoi: nhan + noi dung + khoang cach duoi. */
  function caoKhoiChuoi(k) {
    return 0.22 + caoHopChu(chuThuan(k.noiDung), RONG, 11) + 0.1;
  }

  /**
   * Slide khoa. Bang tieu chi dai qua thi cat sang slide noi tiep "(2/2)" thay vi de no de len
   * khoi ben duoi — day chinh la cho da vo o ban xuat truoc.
   *
   * Khoi "BS truc / DD truc" chi in o trang CUOI, de khong lap lai tren moi trang.
   */
  function veKhoa(pptx, s, mau) {
    var caoLech = s.lechCanDoi ? 0.3 : 0;
    var caoChuoi = 0;
    s.khoiChuoi.forEach(function (k) { caoChuoi += caoKhoiChuoi(k); });

    var caoDung = H - Y_NOI_DUNG - 0.3 - caoLech;     // chieu cao con lai cho noi dung
    var co = coChuTieuChi(Math.ceil(s.items.length / CAP_TIEU_CHI));

    // Trang cuoi phai chua duoc ca khoi chuoi; cac trang truoc thi khong.
    var trang = s.items.length ? chiaTrangTieuChi(s.items, caoDung, co) : [[]];
    if (trang.length === 1 && caoBangTieuChi(trang[0], co) + caoChuoi > caoDung) {
      trang = chiaTrangTieuChi(s.items, caoDung - caoChuoi, co);
    }

    var sl = null;
    trang.forEach(function (items, i) {
      var phuTrang = trang.length > 1 ? ((i + 1) + '/' + trang.length) : '';
      sl = khungSlide(pptx, { tieuDe: s.tieuDe, ngay: s.ngay,
        badge: phuTrang ? { chu: '(' + phuTrang + ')' } : null }, mau);
      var y = Y_NOI_DUNG;

      if (s.lechCanDoi && i === 0) {
        sl.addText('▲ Lệch cân đối: ' + so(s.lechCanDoi), { x: LE, y: y, w: RONG, h: 0.26,
          fontSize: 10, color: mau.amber });
        y += 0.3;
      }

      if (items.length) y += bangTieuChi(sl, items, mau, y, co) + 0.18;

      if (i === trang.length - 1) {
        s.khoiChuoi.forEach(function (k) {
          var chu = chuThuan(k.noiDung);
          var caoChu = caoHopChu(chu, RONG, 11);
          if (y + 0.22 + caoChu > H - 0.15) return; // het cho that su, thoi in tiep
          sl.addText(k.nhan, { x: LE, y: y, w: RONG, h: 0.22, fontSize: 10, color: mau.amber });
          // chuThuan chu khong phai giaiMa: chi tieu chuoi cung do trinh soan thao nhap nen co
          // the day the <p>/<span>. giaiMa khong luoc the, de nguyen thi PPTX hien ca the ra chu.
          sl.addText(chu, { x: LE, y: y + 0.22, w: RONG, h: caoChu,
            fontSize: 11, color: mau.txt2, valign: 'top' });
          y += 0.22 + caoChu + 0.1;
        });
      }
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
