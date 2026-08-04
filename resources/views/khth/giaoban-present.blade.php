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
  :root { --z: 1; }
  * { box-sizing: border-box; margin: 0; padding: 0; }
  html, body { height: 100%; background: #0d1b2a; color: #e8eef5;
    font-family: "Segoe UI", Roboto, Arial, sans-serif; overflow: hidden; }
  #deck { height: 100vh; display: flex; flex-direction: column; }
  #stage { flex: 1; position: relative; }
  /* overflow:auto la luoi an toan cho zoom: phong chu du to thi noi dung tran khoi slide, khong
     co no thi phan tran chui xuong duoi thanh dieu khien va mat hut chu khong phai cuon duoc. */
  .slide { position: absolute; inset: 0; padding: 3vh 4vw; display: none; flex-direction: column;
    overflow: auto; }
  .slide.active { display: flex; }
  .s-head { display: flex; justify-content: space-between; align-items: flex-start;
    border-bottom: 1px solid #24384d; padding-bottom: 1.2vh; }
  .s-brand { font-size: calc(1.88vh * var(--z)); letter-spacing: 1px; color: #6ea8d8; }
  .s-title { font-size: calc(4.25vh * var(--z)); font-weight: 500; color: #fff; margin-top: .3vh; }
  .s-sub { text-align: right; font-size: calc(1.88vh * var(--z)); color: #8aa4bd; }
  .kpis { display: grid; gap: 1.4vh; margin-top: 2vh; }
  .kpi { background: #13293d; border-radius: 10px; padding: 1.6vh 1.6vw; }
  .kpi .lbl { font-size: calc(2.12vh * var(--z)); color: #8aa4bd; }
  .kpi .val { font-size: calc(5.75vh * var(--z)); font-weight: 500; color: #fff; line-height: 1.1; }
  .kpi.teal .val { color: #5dcaa5; } .kpi.amber .val { color: #ef9f27; }
  .charts { display: grid; grid-template-columns: 1fr; gap: 1.6vh; margin-top: 2vh; flex: 1; min-height: 0; }
  .panel { background: #13293d; border-radius: 10px; padding: 1.4vh 1.4vw; display: flex; flex-direction: column; }
  .panel .lbl { font-size: calc(2.12vh * var(--z)); color: #8aa4bd; margin-bottom: 1vh; }
  .note { background: #13293d; border-left: 3px solid #ef9f27; border-radius: 0 8px 8px 0;
    padding: 1.4vh 1.4vw; margin-top: 2vh; }
  .note .lbl { font-size: calc(2.12vh * var(--z)); color: #efc877; }
  .note .txt { font-size: calc(2.75vh * var(--z)); color: #dbe6f0; margin-top: .5vh; }
  /* Chi tieu chuoi luu van ban thuan: xuong dong la \n that, khong phai <br> */
  .note .txt-pre { white-space: pre-wrap; }
  /* Nhieu danh sach dai thi cuon trong khung thay vi tran ra ngoai slide va mat hut */
  .ds-chuoi { flex: 1; min-height: 0; overflow: auto; }
  .ov-canh-bao { margin-top: 1.4vh; padding: 1.1vh 1.4vw; border-radius: 8px;
    font-size: calc(2.38vh * var(--z)); color: #dbe6f0; border-left: 4px solid; }
  .ov-canh-bao .lbl { color: #8aa4bd; font-size: calc(1.88vh * var(--z)); letter-spacing: .5px; margin-right: .8vw; }
  .ov-canh-bao.tot { background: #122b23; border-color: #5dcaa5; }
  .ov-canh-bao.xau { background: #33201f; border-color: #e57373; }
  .ov-badge { font-size: calc(1.88vh * var(--z)); padding: .3vh .8vw; border-radius: 20px; vertical-align: middle;
    margin-left: .8vw; letter-spacing: 1px; }
  .ov-badge.nhap { background: #3a2f12; color: #efc877; }
  .ov-badge.chot { background: #12331f; color: #5dcaa5; }
  .warn { color: #ef9f27; font-size: calc(2.5vh * var(--z)); margin-left: 8px; }
  /* Bang tong hop khoi dieu tri. Co chu do JS dat theo so cot; cham san ma van tran thi
     khung nay cho cuon thay vi de bang tran ra ngoai slide. */
  .bdt-wrap { flex: 1; min-height: 0; overflow: auto; margin-top: 1.4vh; }
  .bdt { width: 100%; border-collapse: collapse; color: #dbe6f0; }
  .bdt th, .bdt td { border: 1px solid #24405c; padding: .5vh .6vw; white-space: nowrap;
    text-align: center; }
  .bdt th { background: #14293e; color: #8aa4bd; font-weight: 600; }
  .bdt th.ten, .bdt td.ten { text-align: left; }
  .bdt td.ten { color: #fff; }
  .bdt tr.tong td { background: #14293e; color: #fff; font-weight: 700; }
  /* Thanh dieu khien KHONG theo --z: no la dieu khien, khong phai noi dung. De no phong theo
     thi o 200% thanh nay xuong dong va an mat 1/4 chieu cao man chieu. */
  #bar { display: flex; justify-content: space-between; align-items: center;
    padding: 1.2vh 4vw; font-size: 1.88vh; color: #6f8aa6; border-top: 1px solid #24384d; }
  #dots { display: flex; gap: 6px; align-items: center; }
  #dots i { width: 6px; height: 6px; border-radius: 50%; background: #3a5570; display: inline-block; }
  #dots i.on { width: 20px; border-radius: 3px; background: #6ea8d8; }
  .btn { background: #13293d; color: #cfe0f0; border: 1px solid #24384d; border-radius: 6px;
    padding: .6vh 1vw; font-size: 1.88vh; cursor: pointer; }
  .btn:hover { background: #1b3348; }
  #jump { position: relative; display: inline-block; z-index: 5; }
  #jump-list { position: absolute; left: 0; bottom: 100%; margin-bottom: 6px; background: #13293d; border: 1px solid #24384d;
    border-radius: 8px; padding: 6px; display: none; max-height: 70vh; overflow: auto; min-width: 220px; }
  #jump-list.open { display: block; }
  #jump-list button { display: block; width: 100%; text-align: left; background: none; border: none;
    color: #cfe0f0; padding: 8px 10px; font-size: 2vh; cursor: pointer; border-radius: 6px; }
  #jump-list button:hover { background: #1b3348; }
  #center { position: absolute; inset: 0; display: flex; align-items: center; justify-content: center;
    text-align: center; font-size: calc(3vh * var(--z)); color: #8aa4bd; }
  .ov-main { display: flex; gap: 1.6vh; margin-top: 2vh; flex: 1; min-height: 0; }
  .ov-kpis { flex: 1; align-content: start; }
  .donut-panel { width: 26vw; flex: none; align-items: center; }
  /* Trong luoi cap-grid, be rong do cot quyet dinh — bo width co dinh cua bo cuc flex cu */
  .cap-grid > .donut-panel { width: auto; }
  .donut-wrap { flex: 1; display: flex; align-items: center; justify-content: center; min-height: 0; }
  .donut-svg { width: 22vh; height: 22vh; }
  .donut-pct { fill: #fff; font-size: 17px; font-weight: 600; }
  .donut-cap { fill: #8aa4bd; font-size: 7px; }
  .donut-legend { display: flex; justify-content: space-around; width: 100%; margin-top: 1.4vh; }
  .donut-legend > div { display: flex; flex-direction: column; align-items: center; }
  .donut-legend .dl-num { font-size: calc(3.75vh * var(--z)); font-weight: 600; }
  .donut-legend small { font-size: calc(1.62vh * var(--z)); color: #8aa4bd; margin-top: .2vh; }
  .cap-grid { display: grid; gap: 1.6vh; margin-top: 2vh; flex: 1; min-height: 0; }
  .caplist { flex: 1; display: flex; flex-direction: column; gap: 1.1vh; justify-content: center; overflow: auto; }
  .caprow { display: flex; align-items: center; gap: 1vw; }
  .capname { width: 15vw; font-size: calc(2.25vh * var(--z)); color: #dbe6f0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
  .captrack { flex: 1; height: 2.4vh; background: #24384d; border-radius: 4px; overflow: hidden; }
  .capfill { height: 100%; border-radius: 4px; transition: none; }
  .cappct { width: 5vw; text-align: right; font-size: calc(2.38vh * var(--z)); font-weight: 600; }
  .capnum { width: 6vw; text-align: right; font-size: calc(2vh * var(--z)); color: #8aa4bd; }
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

  function capColor(pct) {
    return pct >= 90 ? '#e57373' : pct >= 80 ? '#ef9f27' : pct >= 60 ? '#5dcaa5' : '#378add';
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
      '<circle cx="50" cy="50" r="42" fill="none" stroke="#24384d" stroke-width="12"/>' +
      '<circle cx="50" cy="50" r="42" fill="none" stroke="' + col + '" stroke-width="12" stroke-linecap="round" ' +
      'stroke-dasharray="' + C.toFixed(2) + '" stroke-dashoffset="' + off.toFixed(2) + '" transform="rotate(-90 50 50)"/>' +
      '<text x="50" y="49" text-anchor="middle" class="donut-pct">' + pct + '%</text>' +
      '<text x="50" y="62" text-anchor="middle" class="donut-cap">công suất</text></svg></div>' +
      '<div class="donut-legend">' +
      '<div><span class="dl-num" style="color:#fff">' + total + '</span><small>Tổng giường</small></div>' +
      '<div><span class="dl-num" style="color:#5dcaa5">' + used + '</span><small>Đang dùng</small></div>' +
      '<div><span class="dl-num" style="color:#8aa4bd">' + free + '</span><small>Trống</small></div>' +
      '</div></div>';
  }

  function overviewSlide(data) {
    var r = data.report;
    var kpiHtml = '';
    function kpi(label, val, cls) {
      if (val === null) return '';
      return '<div class="kpi' + (cls || '') + '"><div class="lbl">' + esc(label) +
        '</div><div class="val">' + num(val) + '</div></div>';
    }
    // KPI do KHTH danh dau tren tung chi tieu, gom theo NHAN chu khong theo MA.
    // Ban cu tra theo ma viet cung trong code nen chi can KHTH doi ma la man nay trong tron.
    theTongQuan(data).forEach(function (t) {
      kpiHtml += kpi(t.nhan, t.tong, t.cls);
    });
    if (kpiHtml === '') {
      kpiHtml = '<div class="kpi" style="grid-column:1/-1"><div class="lbl">Chưa đánh dấu chỉ tiêu nào</div>' +
        '<div class="txt" style="font-size:calc(2.25vh * var(--z));color:#8aa4bd;margin-top:.6vh">' +
        'Vào Cấu hình giao ban → mở Chỉ tiêu của khoa → tích "Hiện ở màn Tổng quan".</div></div>';
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
          return '<b style="color:#fff">' + esc(d.person_name) + '</b>' + (d.phone ? ' <span style="color:#6ea8d8">' + esc(d.phone) + '</span>' : '');
        }).join(', ');
        dutyHtml += '<div style="font-size:calc(2.38vh * var(--z))"><span style="color:#8aa4bd">' + esc(p.name) + ':</span> ' + names + '</div>';
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
      // Bo class ov-kpis: no co flex:1, von danh cho bo cuc hang ngang cu (KPI canh donut).
      // Gio luoi KPI la con truc tiep cua .slide (flex cot) nen phai de no cao tu nhien,
      // khong thi no gian ra day cac khoi canh bao xuong day man.
      // Kip truc len DAU: nguoi du giao ban can biet ngay ai truc truoc khi doc so lieu.
      dutyHtml +
      '<div class="kpis" style="grid-template-columns:repeat(4,1fr)">' + kpiHtml + '</div>' +
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
        d.o.map(function (v) { return '<td>' + num(v) + '</td>'; }).join('') + '</tr>';
    }).join('');

    var tfoot = '<tr class="tong"><td class="ten">TỔNG CỘNG</td>' +
      b.tong.map(function (v) { return '<td>' + (v === null ? '—' : num(v)) + '</td>'; }).join('') +
      '</tr>';

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
    // Luoi KPI chi nhan chi tieu SO. Chi tieu chuoi xuong khoi rieng ben duoi.
    var cards = cfg.metrics.filter(function (m) { return !laChiTieuChuoi(m); }).map(function (m) {
      var v = cellVal(data, cfg.id, m.code);
      return '<div class="kpi' + kpiClass(m) + '"><div class="lbl">' + esc(m.name) +
        '</div><div class="val">' + num(v) + '</div></div>';
    }).join('');

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
      '<div class="kpis" style="grid-template-columns:repeat(4,1fr)">' + cards + '</div>' +
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
