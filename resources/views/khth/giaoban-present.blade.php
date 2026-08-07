<!doctype html>
<html lang="vi">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Trình chiếu giao ban</title>
<style>
  /* He so phong chu do nguoi trinh chieu chinh (nut A- / A+ o thanh duoi). Chi nhan CO CHU,
     khong nhan padding/gap: phong ca khung thi noi dung tran khoi slide. Doi lai, zoom cao thi
     chu chat dan trong khung va cac danh sach dai phai cuon som hon. */
  /* Bang mau nen toi (mac dinh). Theme sang khai bao lai dung bo bien nay o cuoi khoi style.
     Moi mau cua trinh chieu phai di qua day: mau viet cung con sot lai o dau thi cho do se
     khong doi theo theme va thanh mang lac long tren nen sang. */
  :root {
    --z: 1;
    --bg: #0d1b2a;          /* nen trang */
    --text: #e8eef5;        /* chu tren nen trang */
    --panel: #13293d;       /* nen panel, note, nut */
    --panel-2: #14293e;     /* nen o tieu de bang va dong tong */
    --line: #24384d;        /* duong ke chung, vien nut */
    --line-2: #24405c;      /* vien o bang */
    --brand: #6ea8d8;       /* nhan thuong hieu, dot dang xem, so dien thoai */
    --muted: #8aa4bd;       /* chu phu */
    --txt-2: #dbe6f0;       /* chu noi dung */
    --strong: #fff;         /* chu nhan manh */
    --teal: #5dcaa5;        /* tang / tot */
    --amber: #ef9f27;       /* giam / canh bao */
    --amber-2: #efc877;     /* nhan cua khoi ghi chu */
    --red: #e57373;         /* xau */
    --blue: #378add;        /* cong suat thap */
    --btn-text: #cfe0f0;
    --btn-hover: #1b3348;
    --bar-text: #6f8aa6;
    --dot: #3a5570;
    --track: #24384d;       /* ranh thanh cong suat, vong nen donut */
    --ok-bg: #122b23;
    --bad-bg: #33201f;
    --badge-nhap-bg: #3a2f12;
    --badge-nhap-fg: #efc877;
    --badge-chot-bg: #12331f;
    --badge-chot-fg: #5dcaa5;
  }
  * { box-sizing: border-box; margin: 0; padding: 0; }
  html, body { height: 100%; background: var(--bg); color: var(--text);
    font-family: "Segoe UI", Roboto, Arial, sans-serif; overflow: hidden; }
  #deck { height: 100vh; display: flex; flex-direction: column; }
  #stage { flex: 1; position: relative; }
  /* overflow:auto la luoi an toan cho zoom: phong chu du to thi noi dung tran khoi slide, khong
     co no thi phan tran chui xuong duoi thanh dieu khien va mat hut chu khong phai cuon duoc. */
  .slide { position: absolute; inset: 0; padding: 3vh 4vw; display: none; flex-direction: column;
    overflow: auto; }
  .slide.active { display: flex; }
  .s-head { display: flex; justify-content: space-between; align-items: flex-start;
    border-bottom: 1px solid var(--line); padding-bottom: 1.2vh; }
  .s-brand { font-size: calc(1.88vh * var(--z)); letter-spacing: 1px; color: var(--brand); }
  .s-title { font-size: calc(4.25vh * var(--z)); font-weight: 500; color: var(--strong); margin-top: .3vh; }
  .s-sub { text-align: right; font-size: calc(1.88vh * var(--z)); color: var(--muted); }
  .charts { display: grid; grid-template-columns: 1fr; gap: 1.6vh; margin-top: 2vh; flex: 1; min-height: 0; }
  .panel { background: var(--panel); border-radius: 10px; padding: 1.4vh 1.4vw; display: flex; flex-direction: column; }
  .panel .lbl { font-size: calc(2.12vh * var(--z)); color: var(--muted); margin-bottom: 1vh; }
  .note { background: var(--panel); border-left: 3px solid var(--amber); border-radius: 0 8px 8px 0;
    padding: 1.4vh 1.4vw; margin-top: 2vh; }
  .note .lbl { font-size: calc(2.12vh * var(--z)); color: var(--amber-2); }
  .note .txt { font-size: calc(2.75vh * var(--z)); color: var(--txt-2); margin-top: .5vh; }
  /* Chi tieu chuoi luu van ban thuan: xuong dong la \n that, khong phai <br> */
  .note .txt-pre { white-space: pre-wrap; }
  /* Nhieu danh sach dai thi cuon trong khung thay vi tran ra ngoai slide va mat hut */
  .ds-chuoi { flex: 1; min-height: 0; overflow: auto; }
  .ov-canh-bao { margin-top: 1.4vh; padding: 1.1vh 1.4vw; border-radius: 8px;
    font-size: calc(2.38vh * var(--z)); color: var(--txt-2); border-left: 4px solid; }
  .ov-canh-bao .lbl { color: var(--muted); font-size: calc(1.88vh * var(--z)); letter-spacing: .5px; margin-right: .8vw; }
  .ov-canh-bao.tot { background: var(--ok-bg); border-color: var(--teal); }
  .ov-canh-bao.xau { background: var(--bad-bg); border-color: var(--red); }
  .ov-badge { font-size: calc(1.88vh * var(--z)); padding: .3vh .8vw; border-radius: 20px; vertical-align: middle;
    margin-left: .8vw; letter-spacing: 1px; }
  .ov-badge.nhap { background: var(--badge-nhap-bg); color: var(--badge-nhap-fg); }
  .ov-badge.chot { background: var(--badge-chot-bg); color: var(--badge-chot-fg); }
  .warn { color: var(--amber); font-size: calc(2.5vh * var(--z)); margin-left: 8px; }
  /* Bang tong hop khoi dieu tri. Co chu do JS dat theo so cot; cham san ma van tran thi
     khung nay cho cuon thay vi de bang tran ra ngoai slide. */
  .bdt-wrap { flex: 1; min-height: 0; overflow: auto; margin-top: 1.4vh; }
  .bdt { width: 100%; border-collapse: collapse; color: var(--txt-2); }
  .bdt th, .bdt td { border: 1px solid var(--line-2); padding: .5vh .6vw; white-space: nowrap;
    text-align: center; }
  .bdt th { background: var(--panel-2); color: var(--muted); font-weight: 600; }
  .bdt th.ten, .bdt td.ten { text-align: left; }
  /* O so lieu can phai; o khuyet la chu nen can trai. Tieu de cot van can giua. */
  .bdt td.so { text-align: right; }
  .bdt td.khuyet { text-align: left; }
  .bdt td.ten { color: var(--strong); }
  .bdt tr.tong td { background: var(--panel-2); color: var(--strong); font-weight: 700; }
  /* Bang chi tieu cua man Tong quan va man tung khoa. Dung lai he vien cua .bdt de hai man
     nhin cung mot kieu, nhung cang le theo kieu du lieu: ten trai, so phai. */
  .bct-wrap { min-height: 0; overflow: auto; margin-top: 2vh; }
  /* Chi man tung khoa moi cho bang gian het chieu cao: man Tong quan con cac khoi canh bao
     va ghi chu nam ngay duoi bang, gian ra la day chung xuong day man. */
  .bct-wrap.gian { flex: 1; }
  .bct { width: 100%; border-collapse: collapse; color: var(--txt-2); table-layout: fixed; }
  .bct th, .bct td { border: 1px solid var(--line-2); padding: .6vh .8vw; }
  .bct th { background: var(--panel-2); color: var(--muted); font-weight: 600; text-align: center; }
  .bct th.so, .bct td.so { width: 15%; }
  .bct td.ten, .bct td.khuyet { text-align: left; }
  .bct td.ten { color: var(--strong); }
  .bct td.so { text-align: right; font-weight: 600; color: var(--strong); white-space: nowrap; }
  .bct td.so.teal { color: var(--teal); }
  .bct td.so.amber { color: var(--amber); }
  /* Thanh dieu khien KHONG theo --z: no la dieu khien, khong phai noi dung. De no phong theo
     thi o 200% thanh nay xuong dong va an mat 1/4 chieu cao man chieu. */
  #bar { display: flex; justify-content: space-between; align-items: center;
    padding: 1.2vh 4vw; font-size: 1.88vh; color: var(--bar-text); border-top: 1px solid var(--line); }
  #dots { display: flex; gap: 6px; align-items: center; }
  #dots i { width: 6px; height: 6px; border-radius: 50%; background: var(--dot); display: inline-block; }
  #dots i.on { width: 20px; border-radius: 3px; background: var(--brand); }
  .btn { background: var(--panel); color: var(--btn-text); border: 1px solid var(--line); border-radius: 6px;
    padding: .6vh 1vw; font-size: 1.88vh; cursor: pointer; }
  .btn:hover { background: var(--btn-hover); }
  #jump { position: relative; display: inline-block; z-index: 5; }
  #jump-list { position: absolute; left: 0; bottom: 100%; margin-bottom: 6px; background: var(--panel); border: 1px solid var(--line);
    border-radius: 8px; padding: 6px; display: none; max-height: 70vh; overflow: auto; min-width: 220px; }
  #jump-list.open { display: block; }
  #jump-list button { display: block; width: 100%; text-align: left; background: none; border: none;
    color: var(--btn-text); padding: 8px 10px; font-size: 2vh; cursor: pointer; border-radius: 6px; }
  #jump-list button:hover { background: var(--btn-hover); }
  #center { position: absolute; inset: 0; display: flex; align-items: center; justify-content: center;
    text-align: center; font-size: calc(3vh * var(--z)); color: var(--muted); }
  .ov-main { display: flex; gap: 1.6vh; margin-top: 2vh; flex: 1; min-height: 0; }
  .donut-panel { width: 26vw; flex: none; align-items: center; }
  /* Trong luoi cap-grid, be rong do cot quyet dinh — bo width co dinh cua bo cuc flex cu */
  .cap-grid > .donut-panel { width: auto; }
  .donut-wrap { flex: 1; display: flex; align-items: center; justify-content: center; min-height: 0; }
  .donut-svg { width: 22vh; height: 22vh; }
  .donut-pct { fill: var(--strong); font-size: 17px; font-weight: 600; }
  .donut-cap { fill: var(--muted); font-size: 7px; }
  .donut-legend { display: flex; justify-content: space-around; width: 100%; margin-top: 1.4vh; }
  .donut-legend > div { display: flex; flex-direction: column; align-items: center; }
  .donut-legend .dl-num { font-size: calc(3.75vh * var(--z)); font-weight: 600; }
  .donut-legend small { font-size: calc(1.62vh * var(--z)); color: var(--muted); margin-top: .2vh; }
  .cap-grid { display: grid; gap: 1.6vh; margin-top: 2vh; flex: 1; min-height: 0; }
  .caplist { flex: 1; display: flex; flex-direction: column; gap: 1.1vh; justify-content: center; overflow: auto; }
  .caprow { display: flex; align-items: center; gap: 1vw; }
  .capname { width: 15vw; font-size: calc(2.25vh * var(--z)); color: var(--txt-2); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
  .captrack { flex: 1; height: 2.4vh; background: var(--track); border-radius: 4px; overflow: hidden; }
  .capfill { height: 100%; border-radius: 4px; transition: none; }
  .cappct { width: 5vw; text-align: right; font-size: calc(2.38vh * var(--z)); font-weight: 600; }
  .capnum { width: 6vw; text-align: right; font-size: calc(2vh * var(--z)); color: var(--muted); }
  /* Bang mau nen sang. Khong dao mau may moc tu ban toi: cac mau pastel cua nen toi (#5dcaa5,
     #efc877) doc khong ro tren nen trang nen mau nhan o day dam va bao hoa hon. */
  html[data-theme="light"] {
    --bg: #f4f6f9;
    --text: #1c2733;
    --panel: #ffffff;
    --panel-2: #eaf0f6;
    --line: #d3dde7;
    --line-2: #c3d0dd;
    --brand: #1d6fa5;
    --muted: #4f6577;
    --txt-2: #2b3949;
    --strong: #0f1a26;
    /* Ba mau duoi da dam hon ban dau (#12855f, #b96a00, #5a7085): do tuong phan tren nen
       trang chi dat 3.8-4.5, chieu len tuong phong sang la doc khong ra. */
    --teal: #0e6d4e;
    --amber: #8f5200;
    --amber-2: #8a5a00;
    --red: #c53a3a;
    --blue: #1668b3;
    --btn-text: #1c2733;
    --btn-hover: #e6eef6;
    --bar-text: #5a7085;
    --dot: #b8c6d3;
    --track: #dbe4ec;
    --ok-bg: #e6f5ee;
    --bad-bg: #fdeceb;
    --badge-nhap-bg: #fdf0d6;
    --badge-nhap-fg: #8a5a00;
    --badge-chot-bg: #ddf3e7;
    --badge-chot-fg: #12855f;
  }
  /* Nen sang: panel trang tren nen trang nga can vien moi tach khoi duoc, ban toi thi khong. */
  html[data-theme="light"] .panel,
  html[data-theme="light"] .note { border: 1px solid var(--line); }
  html[data-theme="light"] .note { border-left: 3px solid var(--amber); }
</style>
</head>
<body>
<div id="deck">
  <div id="stage">
    <div id="center">Đang tải dữ liệu…</div>
  </div>
  <div id="bar">
    <span style="display:flex;gap:.6vw;align-items:center">
      <button class="btn" id="fs-btn">⛶ Toàn màn hình (F)</button>
      <button class="btn" id="z-out" title="Chữ nhỏ lại (phím −)">A−</button>
      <span id="z-val" title="Bấm để về 100% (phím 0)" style="cursor:pointer;min-width:3.5em;text-align:center">100%</span>
      <button class="btn" id="z-in" title="Chữ to lên (phím +)">A+</button>
      <button class="btn" id="theme-btn" title="Chuyển nền sáng">☀</button>
      <span id="jump">
        <button class="btn" id="jump-btn">☰ Khoa</button>
        <div id="jump-list"></div>
      </span>
    </span>
    <span id="dots"></span>
    <span id="counter">–/–</span>
    <span>← → chuyển slide · ESC thoát</span>
  </div>
</div>

<script>
(function () {
  var SHOW_URL = @json($showUrl);
  var DATE = @json($date);

  function esc(s) {
    return String(s === null || s === undefined ? '' : s).replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
  }
  function fmtDate(d) {
    var days = ['Chủ nhật','thứ Hai','thứ Ba','thứ Tư','thứ Năm','thứ Sáu','thứ Bảy'];
    var dt = new Date(d + 'T00:00:00');
    var s = isNaN(dt.getTime()) ? d : days[dt.getDay()] + ', ' +
      ('0' + dt.getDate()).slice(-2) + '/' + ('0' + (dt.getMonth() + 1)).slice(-2) + '/' + dt.getFullYear();
    return s;
  }
  function num(v) { return v === null || v === undefined || v === '' ? '—' : String(Math.round(Number(v) * 100) / 100); }

  var slides = [];
  var current = 0;
  var deptNames = [];

  function cellVal(data, deptId, code) {
    for (var i = 0; i < data.cells.length; i++) {
      var c = data.cells[i];
      if (c.dept_config_id === deptId && c.metric_code === code) {
        return c.manual_value !== null && c.manual_value !== undefined ? c.manual_value : c.auto_value;
      }
    }
    return null;
  }
  function cellExists(data, code) {
    for (var i = 0; i < data.cells.length; i++) if (data.cells[i].metric_code === code) return true;
    return false;
  }
  /**
   * Cac the KPI cua man Tong quan: chi tieu duoc tich `overview`, gom theo `overview_label`
   * (thieu thi lay ten chi tieu). Thu tu the = thu tu gap dau tien khi duyet khoa roi chi tieu.
   */
  function theTongQuan(data) {
    var thu = [], theo = {};
    data.configs.forEach(function (cfg) {
      (cfg.metrics || []).forEach(function (m) {
        if (m.overview !== true || laChiTieuChuoi(m)) return;
        var nhan = (m.overview_label && String(m.overview_label).trim() !== '')
          ? String(m.overview_label) : m.name;
        var v = cellVal(data, cfg.id, m.code);
        if (!theo[nhan]) {
          theo[nhan] = { nhan: nhan, tong: null, cls: kpiClass(m) };
          thu.push(theo[nhan]);
        }
        if (v !== null && v !== undefined && v !== '') {
          theo[nhan].tong = Number(theo[nhan].tong || 0) + Number(v);
        }
      });
    });
    return thu;
  }

  /** Khoa nao con o bat buoc chua dien. Dung du lieu da co san trong payload. */
  function khoaThieuBatBuoc(data) {
    var ds = [];
    data.configs.forEach(function (cfg) {
      var thieu = 0;
      (cfg.metrics || []).forEach(function (m) {
        if (m.type !== 'manual' || !m.input || !m.input.required) return;
        if (laChiTieuChuoi(m)) {
          var t = cellNote(data, cfg.id, m.code);
          if (!t || String(t).trim() === '') thieu++;
          return;
        }
        var c = null;
        for (var i = 0; i < data.cells.length; i++) {
          if (data.cells[i].dept_config_id === cfg.id && data.cells[i].metric_code === m.code) { c = data.cells[i]; break; }
        }
        // Ke thua tu ky truoc ma khoa chua xac nhan thi van tinh la chua dien.
        var trong = !c || c.manual_value === null || c.manual_value === undefined || c.manual_value === '';
        if (trong || (c && c.carried_over)) thieu++;
      });
      if (thieu > 0) ds.push({ ten: cfg.display_name, so: thieu });
    });
    return ds;
  }

  /** Chi tieu nay co phai loai nhap tay kieu chuoi khong. */
  function laChiTieuChuoi(m) {
    return m.type === 'manual' && m.input && m.input.value_type === 'text';
  }

  /**
   * Noi dung chi tieu chuoi nam o cot `note` cua o, khong phai manual_value —
   * nen cellVal() khong bao gio lay duoc.
   */
  function cellNote(data, deptId, code) {
    for (var i = 0; i < data.cells.length; i++) {
      var c = data.cells[i];
      if (c.dept_config_id === deptId && c.metric_code === code) return c.note;
    }
    return null;
  }

  function noteOf(data, deptId) {
    for (var i = 0; i < data.cells.length; i++) {
      var c = data.cells[i];
      if (c.dept_config_id === deptId && c.metric_code === 'note') return c.note;
    }
    return null;
  }

  function kpiClass(metric) {
    if (metric.type === 'movement_in' || metric.type === 'movement_transfer_in') return ' teal';
    if (metric.code === 'bn_ra_vien' || metric.code === 'bn_chuyen_vien') return ' amber';
    return '';
  }

  // Tra TEN BIEN chu khong phai hex: mau nam trong style noi tuyen cua thanh cong suat va donut,
  // tra bien thi doi theme la mau tu doi theo, khong phai dung lai DOM.
  function capColor(pct) {
    return pct >= 90 ? 'var(--red)' : pct >= 80 ? 'var(--amber)' : pct >= 60 ? 'var(--teal)' : 'var(--blue)';
  }

  function donutHtml(used, total) {
    if (!total) return '';
    var pct = Math.round(used / total * 100);
    var free = Math.max(0, total - used);
    var col = capColor(pct);
    var R = 42, C = 2 * Math.PI * R;
    var off = C * (1 - Math.min(100, pct) / 100);
    return '<div class="panel donut-panel"><div class="lbl">CÔNG SUẤT GIƯỜNG</div>' +
      '<div class="donut-wrap"><svg viewBox="0 0 100 100" class="donut-svg">' +
      '<circle cx="50" cy="50" r="42" fill="none" stroke="var(--track)" stroke-width="12"/>' +
      '<circle cx="50" cy="50" r="42" fill="none" stroke="' + col + '" stroke-width="12" stroke-linecap="round" ' +
      'stroke-dasharray="' + C.toFixed(2) + '" stroke-dashoffset="' + off.toFixed(2) + '" transform="rotate(-90 50 50)"/>' +
      '<text x="50" y="49" text-anchor="middle" class="donut-pct">' + pct + '%</text>' +
      '<text x="50" y="62" text-anchor="middle" class="donut-cap">công suất</text></svg></div>' +
      '<div class="donut-legend">' +
      '<div><span class="dl-num" style="color:var(--strong)">' + total + '</span><small>Tổng giường</small></div>' +
      '<div><span class="dl-num" style="color:var(--teal)">' + used + '</span><small>Đang dùng</small></div>' +
      '<div><span class="dl-num" style="color:var(--muted)">' + free + '</span><small>Trống</small></div>' +
      '</div></div>';
  }

  /**
   * Bang chi tieu dung chung cho man Tong quan va man tung khoa.
   *
   * Moi dong hai cap "Chi tieu | So lieu" de tan dung be ngang man chieu; so chi tieu le thi
   * cap cuoi de trong nhung van co vien cho bang khong khuyet goc.
   *
   * Cang le theo KIEU DU LIEU chu khong theo cot: so va phan tram can phai, con lai can trai.
   * O khuyet (num() tra dau gach) tinh la chuoi nen can trai.
   *
   * Co chu nho dan theo so dong, cung tinh than voi cach .bdt nho dan theo so cot. Cham san
   * ma van tran thi .bct-wrap cho cuon (khi gian) hoac .slide cho cuon (khi khong gian).
   */
  /**
   * O so lieu dung chung cho ca bang tieu chi lan bang Hoat dong dieu tri.
   * So va phan tram can phai; o khuyet (num() tra dau gach) la chu nen can trai.
   * `cls` la mau nhan tu kpiClass(), chi ap khi o that su la so.
   */
  function oSoLieu(v, cls) {
    var s = num(v);
    var laSo = /^-?[\d.,]+%?$/.test(s);
    return '<td class="' + (laSo ? 'so' + (cls || '') : 'khuyet') + '">' + s + '</td>';
  }

  function bangChiTieu(items, gian) {
    if (!items.length) return '';

    var CAP = 2; // so cap "Chi tieu | So lieu" tren mot dong
    var soDong = Math.ceil(items.length / CAP);
    var co = soDong <= 6 ? 2.75 : (soDong <= 10 ? 2.3 : (soDong <= 14 ? 2 : 1.75));

    var thead = '<tr>';
    for (var c = 0; c < CAP; c++) thead += '<th class="ten">TIÊU CHÍ</th><th class="so">SỐ LIỆU</th>';
    thead += '</tr>';

    var tbody = '';
    for (var r = 0; r < soDong; r++) {
      tbody += '<tr>';
      for (var k = 0; k < CAP; k++) {
        var it = items[r * CAP + k];
        if (!it) { tbody += '<td class="ten"></td><td class="so"></td>'; continue; }
        tbody += '<td class="ten">' + esc(it.nhan) + '</td>' + oSoLieu(it.gia_tri, it.cls);
      }
      tbody += '</tr>';
    }

    return '<div class="bct-wrap' + (gian ? ' gian' : '') + '">' +
      '<table class="bct" style="font-size:calc(' + co + 'vh * var(--z))">' +
      '<thead>' + thead + '</thead><tbody>' + tbody + '</tbody></table></div>';
  }

  function overviewSlide(data) {
    var r = data.report;
    // Chi tieu do KHTH danh dau, gom theo NHAN chu khong theo MA. Ban cu tra theo ma viet cung
    // trong code nen chi can KHTH doi ma la man nay trong tron.
    // Khong gian bang: cac khoi canh bao va ghi chu nam ngay duoi, gian ra la day chung xuong day man.
    var kpiHtml = bangChiTieu(theTongQuan(data).map(function (t) {
      return { nhan: t.nhan, gia_tri: t.tong, cls: t.cls };
    }), false);
    if (kpiHtml === '') {
      // Cau huong dan goi dung ten nut that ben man Cau hinh giao ban.
      kpiHtml = '<div class="note" style="margin-top:2vh"><div class="lbl">CHƯA ĐÁNH DẤU TIÊU CHÍ NÀO</div>' +
        '<div class="txt">Vào Cấu hình giao ban → mở Tiêu chí của khoa → tích "Hiện ở màn Tổng quan".</div></div>';
    }

    var duties = (data.duties || []).filter(function (d) { return (d.person_name || '').trim() !== ''; });
    var byPosD = {};
    duties.forEach(function (d) { (byPosD[d.position_id] = byPosD[d.position_id] || []).push(d); });
    var dutyHtml = '';
    if (duties.length) {
      // Khoi nay nam DAU slide Tong quan nen dung margin-bottom, khong phai margin-top.
      dutyHtml = '<div class="panel" style="margin-bottom:1.6vh"><div class="lbl">KÍP TRỰC LÃNH ĐẠO</div><div style="display:flex;flex-wrap:wrap;gap:1vh 2vw">';
      (data.duty_positions || []).forEach(function (p) {
        var people = byPosD[p.id]; if (!people || !people.length) return;
        var names = people.map(function (d) {
          return '<b style="color:var(--strong)">' + esc(d.person_name) + '</b>' + (d.phone ? ' <span style="color:var(--brand)">' + esc(d.phone) + '</span>' : '');
        }).join(', ');
        dutyHtml += '<div style="font-size:calc(2.38vh * var(--z))"><span style="color:var(--muted)">' + esc(p.name) + ':</span> ' + names + '</div>';
      });
      dutyHtml += '</div></div>';
    }

    var gnote = r && r.general_note ? String(r.general_note).trim() : '';
    var noteHtml = gnote !== ''
      ? '<div class="note" style="margin-top:1.6vh"><div class="lbl">GHI CHÚ CHUNG</div><div class="txt">' + gnote + '</div></div>'
      : '';

    // Chi con MOT khoi canh bao tren man tong hop. Lech can doi da bo khoi day theo yeu cau
    // su dung: no van hien o badge tren slide tung khoa va o man nhap lieu — hai cho co ngu
    // canh de xu ly, con man tong hop thi chi lam nhieu.

    var dsThieu = khoaThieuBatBuoc(data).map(function (x) { return esc(x.ten) + ' (' + x.so + ')'; });
    var thieuHtml = '<div class="ov-canh-bao' + (dsThieu.length ? ' xau' : ' tot') + '">' +
      '<span class="lbl">Ô BẮT BUỘC CÒN TRỐNG</span> ' +
      (dsThieu.length ? dsThieu.length + ' khoa: ' + dsThieu.join(' · ') : 'Các khoa đã nhập đủ') + '</div>';

    var trangThai = r && r.status === 'final'
      ? '<span class="ov-badge chot">ĐÃ CHỐT</span>'
      : '<span class="ov-badge nhap">BẢN NHÁP</span>';

    var sub = r ? ('Số liệu ' + esc(r.from_time) + ' → ' + esc(r.to_time)) : '';
    return '<div class="slide"><div class="s-head"><div>' +
      '<div class="s-brand">BÁO CÁO GIAO BAN</div>' +
      '<div class="s-title">Giao ban ' + esc(fmtDate(DATE)) + ' ' + trangThai + '</div></div>' +
      '<div class="s-sub">' + sub + '</div></div>' +
      // Kip truc len DAU: nguoi du giao ban can biet ngay ai truc truoc khi doc so lieu.
      dutyHtml +
      kpiHtml +
      thieuHtml + noteHtml + '</div>';
  }

  /**
   * Bang tong hop khoi Dieu tri noi tru.
   *
   * May chu da dung san cau truc (App\Services\GiaoBan\BangDieuTri): loc khoi, loc quyen,
   * gop cot theo nhan, quy null ve 0, bo tong cot phan tram. O day CHI VE.
   *
   * Co chu nho dan theo so cot: bay khoa moi khoa vai chi tieu rieng thi de len 20+ cot,
   * giu co chu goc thi tran khoi man chieu. Co san toi thieu; cham san van tran thi
   * .bdt-wrap cho cuon.
   *
   * Cac nguong duoi chi tang 15%, khong phai 25% nhu phan con lai cua trinh chieu: bang nay
   * la cho chat nhat, tang manh la bang nhieu cot phai cuon — ma chieu len tuong thi phan
   * phai cuon coi nhu mat du lieu.
   */
  function dieuTriSlide(data) {
    var b = data.bang_dieu_tri;
    if (!b || !b.cot || !b.cot.length || !b.dong || !b.dong.length) return '';

    var soCot = b.cot.length;
    var co = soCot <= 8 ? 2.3 : (soCot <= 14 ? 1.95 : (soCot <= 20 ? 1.65 : 1.45));

    var thead = '<tr><th class="ten">KHOA PHÒNG</th>' +
      b.cot.map(function (c) { return '<th>' + esc(c.nhan) + '</th>'; }).join('') + '</tr>';

    var tbody = b.dong.map(function (d) {
      return '<tr><td class="ten">' + esc(d.ten) + '</td>' +
        d.o.map(function (v) { return oSoLieu(v); }).join('') + '</tr>';
    }).join('');

    var tfoot = '<tr class="tong"><td class="ten">TỔNG CỘNG</td>' +
      b.tong.map(function (v) { return oSoLieu(v); }).join('') + '</tr>';

    return '<div class="slide"><div class="s-head"><div class="s-title">Hoạt động điều trị</div>' +
      '<div class="s-sub">Giao ban ' + esc(fmtDate(DATE)) + '</div></div>' +
      '<div class="bdt-wrap"><table class="bdt" style="font-size:calc(' + co + 'vh * var(--z))">' +
      '<thead>' + thead + '</thead><tbody>' + tbody + tfoot + '</tbody></table></div></div>';
  }

  function capacityDeptSlide(data) {
    // Ưu tiên khoa báo cáo điều trị; nếu không có thì dùng từng khoa HIS có giường (như dashboard).
    var bedSrc = (data.bed_by_config && data.bed_by_config.length) ? data.bed_by_config : (data.bed_by_department || []);
    var beds = bedSrc.filter(function (b) { return Number(b.total) > 0; })
      .map(function (b) {
        var t = Number(b.total), u = Number(b.used);
        return { name: b.display_name, total: t, used: u, pct: Math.round(u / t * 100) };
      })
      .sort(function (a, b) { return b.pct - a.pct; });
    var capHtml = '';
    if (beds.length) {
      var rowsC = beds.map(function (x) {
        var col = capColor(x.pct);
        return '<div class="caprow"><div class="capname">' + esc(x.name) + '</div>' +
          '<div class="captrack"><div class="capfill" style="width:' + Math.min(100, x.pct) + '%;background:' + col + '"></div></div>' +
          '<div class="cappct" style="color:' + col + '">' + x.pct + '%</div>' +
          '<div class="capnum">' + x.used + '/' + x.total + '</div></div>';
      }).join('');
      capHtml = '<div class="panel"><div class="lbl">CÔNG SUẤT GIƯỜNG THEO KHOA</div>' +
        '<div class="caplist">' + rowsC + '</div></div>';
    }

    // Donut tong vien chuyen tu man Tong quan sang day, de moi noi dung ve giuong nam mot cho.
    var tongGiuong = Number(data.bed_total || 0);
    var donut = tongGiuong > 0 ? donutHtml(Number(data.bed_used || 0), tongGiuong) : '';

    if (!capHtml && !donut) return '';

    // Donut mot cot, cong suat theo khoa mot cot.
    var cols = (donut && capHtml) ? 'minmax(24vw,1fr) 2fr' : '1fr';

    return '<div class="slide"><div class="s-head"><div class="s-title">Công suất giường</div>' +
      '<div class="s-sub">Giao ban ' + esc(fmtDate(DATE)) + '</div></div>' +
      '<div class="cap-grid" style="grid-template-columns:' + cols + '">' + donut + capHtml + '</div></div>';
  }

  function deptSlide(data, cfg) {
    var warn = data.balance_warnings && data.balance_warnings[cfg.id]
      ? '<span class="warn" title="Lệch cân đối">▲ ' + num(data.balance_warnings[cfg.id]) + '</span>' : '';
    // Bang chi tieu chi nhan chi tieu SO. Chi tieu chuoi xuong khoi rieng ben duoi.
    var items = cfg.metrics.filter(function (m) { return !laChiTieuChuoi(m); }).map(function (m) {
      return { nhan: m.name, gia_tri: cellVal(data, cfg.id, m.code), cls: kpiClass(m) };
    });
    var bangHtml = bangChiTieu(items, true);

    // Moi chi tieu chuoi mot khoi, dung lai class .note. Khoi rong thi bo qua.
    // Noi dung da qua htmlspecialchars o server nen chen thang vao HTML se hien dung dau < >;
    // xuong dong la ky tu \n that -> can white-space: pre-wrap (class .txt-pre).
    var chuoiHtml = cfg.metrics.filter(laChiTieuChuoi).map(function (m) {
      var t = cellNote(data, cfg.id, m.code);
      if (!t || String(t).trim() === '') return '';
      return '<div class="note"><div class="lbl">' + esc(m.name) +
        '</div><div class="txt txt-pre">' + t + '</div></div>';
    }).join('');
    if (chuoiHtml) chuoiHtml = '<div class="ds-chuoi">' + chuoiHtml + '</div>';

    var note = noteOf(data, cfg.id);
    var noteHtml = (note && String(note).trim() !== '')
      ? '<div class="note"><div class="lbl">Ghi chú khoa</div><div class="txt">' + note + '</div></div>' : '';
    return '<div class="slide"><div class="s-head"><div class="s-title">' + esc(cfg.display_name) + warn +
      '</div><div class="s-sub">Giao ban ' + esc(fmtDate(DATE)) + '</div></div>' +
      bangHtml +
      chuoiHtml + noteHtml + '</div>';
  }

  function build(data) {
    slides = [];
    deptNames = [];
    setupNav(); // gắn phím tắt/fullscreen/nút khoa cả khi chưa có dữ liệu (go() tự no-op nếu deck rỗng)
    if (!data.report) {
      document.getElementById('center').innerHTML =
        'Chưa có dữ liệu báo cáo cho ngày ' + esc(fmtDate(DATE)) + '.<br>Hãy lấy số liệu ở màn nhập trước.';
      document.getElementById('counter').textContent = '0/0';
      return;
    }
    // Thu tu: Tong quan -> Hoat dong dieu tri -> tung khoa (theo sort_order) -> Cong suat giuong.
    // deptNames phai gan chi so theo dung thu tu nay, khong thi bam ten khoa se nhay sai slide.
    slides.push(overviewSlide(data));
    deptNames.push({ idx: 0, name: 'Tổng quan' });
    // Xem buc tranh toan khoi dieu tri truoc roi moi di vao tung khoa.
    var dtHtml = dieuTriSlide(data);
    if (dtHtml) {
      deptNames.push({ idx: slides.length, name: 'Hoạt động điều trị' });
      slides.push(dtHtml);
    }
    data.configs.forEach(function (cfg) {
      deptNames.push({ idx: slides.length, name: cfg.display_name });
      slides.push(deptSlide(data, cfg));
    });
    var capHtml = capacityDeptSlide(data);
    if (capHtml) {
      deptNames.push({ idx: slides.length, name: 'Công suất giường' });
      slides.push(capHtml);
    }

    var stage = document.getElementById('stage');
    document.getElementById('center').remove();
    var frag = '';
    slides.forEach(function (h) { frag += h; });
    stage.insertAdjacentHTML('afterbegin', frag);

    var jl = document.getElementById('jump-list');
    jl.innerHTML = deptNames.map(function (d) {
      return '<button data-idx="' + d.idx + '">' + esc(d.name) + '</button>';
    }).join('');

    var dots = document.getElementById('dots');
    dots.innerHTML = slides.map(function () { return '<i></i>'; }).join('');

    go(0);
  }

  function go(i) {
    if (!slides.length) return;
    current = Math.max(0, Math.min(slides.length - 1, i));
    var els = document.querySelectorAll('#stage .slide');
    for (var k = 0; k < els.length; k++) els[k].classList.toggle('active', k === current);
    var dots = document.querySelectorAll('#dots i');
    for (var d = 0; d < dots.length; d++) dots[d].classList.toggle('on', d === current);
    document.getElementById('counter').textContent = (current + 1) + '/' + slides.length;
  }

  /*
   * Phong chu. Muc zoom la cua MAY DANG CHIEU, khong phai cua bao cao: luu localStorage de
   * lan chieu sau khoi chinh lai, khong gui len may chu.
   */
  var ZOOM_KEY = 'giaoban.present.zoom';
  var ZOOM_MIN = 0.7, ZOOM_MAX = 2, ZOOM_BUOC = 0.1;
  var zoom = 1;

  function datZoom(z) {
    // Lam tron 2 chu so: cong don 0.1 nhieu lan sinh 1.2000000000000002, hien ra thanh "120%"
    // thi khong sao nhung ghi vao localStorage roi doc lai la rac.
    zoom = Math.round(Math.min(ZOOM_MAX, Math.max(ZOOM_MIN, z)) * 100) / 100;
    document.documentElement.style.setProperty('--z', String(zoom));
    var o = document.getElementById('z-val');
    if (o) o.textContent = Math.round(zoom * 100) + '%';
    try { localStorage.setItem(ZOOM_KEY, String(zoom)); } catch (err) { /* che do rieng tu */ }
  }

  function napZoom() {
    var z = 1;
    try { z = parseFloat(localStorage.getItem(ZOOM_KEY)); } catch (err) { z = 1; }
    datZoom(isNaN(z) || !z ? 1 : z);
  }

  /*
   * Theme la thiet lap cua MAY DANG CHIEU giong muc zoom, khong phai cua bao cao: luu
   * localStorage, khong gui len may chu. Mac dinh nen toi ke ca khi may dat che do sang.
   */
  var THEME_KEY = 'giaoban.present.theme';
  var theme = 'dark';

  function datTheme(t) {
    theme = t === 'light' ? 'light' : 'dark';
    document.documentElement.setAttribute('data-theme', theme);
    var b = document.getElementById('theme-btn');
    if (b) {
      // Nhan la thu SE chuyen sang khi bam, khong phai thu dang dung.
      b.textContent = theme === 'dark' ? '☀' : '☾';
      b.title = theme === 'dark' ? 'Chuyển nền sáng' : 'Chuyển nền tối';
    }
    try { localStorage.setItem(THEME_KEY, theme); } catch (err) { /* che do rieng tu */ }
  }

  function napTheme() {
    var t = 'dark';
    try { t = localStorage.getItem(THEME_KEY) || 'dark'; } catch (err) { t = 'dark'; }
    datTheme(t);
  }

  function setupNav() {
    document.addEventListener('keydown', function (e) {
      if (e.key === 'ArrowRight' || e.key === ' ' || e.key === 'PageDown') { go(current + 1); e.preventDefault(); }
      else if (e.key === 'ArrowLeft' || e.key === 'PageUp') { go(current - 1); e.preventDefault(); }
      else if (e.key === 'Home') { go(0); }
      else if (e.key === 'End') { go(slides.length - 1); }
      else if (e.key === 'f' || e.key === 'F') { toggleFs(); }
      // '=' vi phim + tren hang so phai giu Shift moi ra '+'
      else if (e.key === '+' || e.key === '=') { datZoom(zoom + ZOOM_BUOC); e.preventDefault(); }
      else if (e.key === '-' || e.key === '_') { datZoom(zoom - ZOOM_BUOC); e.preventDefault(); }
      else if (e.key === '0') { datZoom(1); e.preventDefault(); }
    });
    document.getElementById('stage').addEventListener('click', function (e) {
      if (e.target.closest('#jump')) return;
      var mid = window.innerWidth / 2;
      go(e.clientX < mid ? current - 1 : current + 1);
    });
    var jb = document.getElementById('jump-btn'), jl = document.getElementById('jump-list');
    jb.addEventListener('click', function (e) { e.stopPropagation(); jl.classList.toggle('open'); });
    jl.addEventListener('click', function (e) {
      var b = e.target.closest('button'); if (!b) return;
      e.stopPropagation(); go(parseInt(b.getAttribute('data-idx'), 10)); jl.classList.remove('open');
    });
    document.addEventListener('click', function () { jl.classList.remove('open'); });
    document.getElementById('fs-btn').addEventListener('click', toggleFs);
    document.getElementById('z-in').addEventListener('click', function () { datZoom(zoom + ZOOM_BUOC); });
    document.getElementById('z-out').addEventListener('click', function () { datZoom(zoom - ZOOM_BUOC); });
    document.getElementById('z-val').addEventListener('click', function () { datZoom(1); });
    document.getElementById('theme-btn').addEventListener('click', function () {
      datTheme(theme === 'dark' ? 'light' : 'dark');
    });
    napTheme();
    napZoom();
  }

  function toggleFs() {
    var el = document.documentElement;
    if (!document.fullscreenElement) { (el.requestFullscreen || el.webkitRequestFullscreen || function(){}).call(el); }
    else { (document.exitFullscreen || document.webkitExitFullscreen || function(){}).call(document); }
  }

  fetch(SHOW_URL + '?date=' + encodeURIComponent(DATE), { credentials: 'same-origin', headers: { 'Accept': 'application/json' } })
    .then(function (r) { return r.json(); })
    .then(function (data) { build(data); })
    .catch(function () {
      document.getElementById('center').textContent = 'Lỗi tải dữ liệu. Kiểm tra đăng nhập và thử lại.';
    });
})();
</script>
</body>
</html>
