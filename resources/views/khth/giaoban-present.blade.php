<!doctype html>
<html lang="vi">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Trình chiếu giao ban</title>
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  html, body { height: 100%; background: #0d1b2a; color: #e8eef5;
    font-family: "Segoe UI", Roboto, Arial, sans-serif; overflow: hidden; }
  #deck { height: 100vh; display: flex; flex-direction: column; }
  #stage { flex: 1; position: relative; }
  .slide { position: absolute; inset: 0; padding: 3vh 4vw; display: none; flex-direction: column; }
  .slide.active { display: flex; }
  .s-head { display: flex; justify-content: space-between; align-items: flex-start;
    border-bottom: 1px solid #24384d; padding-bottom: 1.2vh; }
  .s-brand { font-size: 1.5vh; letter-spacing: 1px; color: #6ea8d8; }
  .s-title { font-size: 3.4vh; font-weight: 500; color: #fff; margin-top: .3vh; }
  .s-sub { text-align: right; font-size: 1.5vh; color: #8aa4bd; }
  .kpis { display: grid; gap: 1.4vh; margin-top: 2vh; }
  .kpi { background: #13293d; border-radius: 10px; padding: 1.6vh 1.6vw; }
  .kpi .lbl { font-size: 1.7vh; color: #8aa4bd; }
  .kpi .val { font-size: 4.6vh; font-weight: 500; color: #fff; line-height: 1.1; }
  .kpi.teal .val { color: #5dcaa5; } .kpi.amber .val { color: #ef9f27; }
  .charts { display: grid; grid-template-columns: 1fr; gap: 1.6vh; margin-top: 2vh; flex: 1; min-height: 0; }
  .panel { background: #13293d; border-radius: 10px; padding: 1.4vh 1.4vw; display: flex; flex-direction: column; }
  .panel .lbl { font-size: 1.7vh; color: #8aa4bd; margin-bottom: 1vh; }
  .note { background: #13293d; border-left: 3px solid #ef9f27; border-radius: 0 8px 8px 0;
    padding: 1.4vh 1.4vw; margin-top: 2vh; }
  .note .lbl { font-size: 1.7vh; color: #efc877; }
  .note .txt { font-size: 2.2vh; color: #dbe6f0; margin-top: .5vh; }
  .warn { color: #ef9f27; font-size: 2vh; margin-left: 8px; }
  #bar { display: flex; justify-content: space-between; align-items: center;
    padding: 1.2vh 4vw; font-size: 1.5vh; color: #6f8aa6; border-top: 1px solid #24384d; }
  #dots { display: flex; gap: 6px; align-items: center; }
  #dots i { width: 6px; height: 6px; border-radius: 50%; background: #3a5570; display: inline-block; }
  #dots i.on { width: 20px; border-radius: 3px; background: #6ea8d8; }
  .btn { background: #13293d; color: #cfe0f0; border: 1px solid #24384d; border-radius: 6px;
    padding: .6vh 1vw; font-size: 1.5vh; cursor: pointer; }
  .btn:hover { background: #1b3348; }
  #jump { position: absolute; top: 3vh; right: 4vw; z-index: 5; }
  #jump-list { position: absolute; right: 0; top: 4vh; background: #13293d; border: 1px solid #24384d;
    border-radius: 8px; padding: 6px; display: none; max-height: 70vh; overflow: auto; min-width: 220px; }
  #jump-list.open { display: block; }
  #jump-list button { display: block; width: 100%; text-align: left; background: none; border: none;
    color: #cfe0f0; padding: 8px 10px; font-size: 1.6vh; cursor: pointer; border-radius: 6px; }
  #jump-list button:hover { background: #1b3348; }
  #center { position: absolute; inset: 0; display: flex; align-items: center; justify-content: center;
    text-align: center; font-size: 2.4vh; color: #8aa4bd; }
  .bars { display: flex; align-items: flex-end; gap: 1.2vw; flex: 1; padding-bottom: 1vh; }
  .barcol { flex: 1; display: flex; flex-direction: column; align-items: center; gap: .4vh; }
  .barpair { display: flex; gap: 3px; align-items: flex-end; }
  .barpair span { width: .9vw; display: block; }
  .barcol small { font-size: 1.2vh; color: #6f8aa6; text-align: center; }
  .legend { display: flex; gap: 1.4vw; font-size: 1.3vh; color: #8aa4bd; }
  .legend b { display: inline-block; width: 10px; height: 10px; margin-right: 4px; }
</style>
</head>
<body>
<div id="deck">
  <div id="stage">
    <div id="jump">
      <button class="btn" id="jump-btn">☰ Khoa</button>
      <div id="jump-list"></div>
    </div>
    <div id="center">Đang tải dữ liệu…</div>
  </div>
  <div id="bar">
    <span><button class="btn" id="fs-btn">⛶ Toàn màn hình (F)</button></span>
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
  function sumMetric(data, codes) {
    var s = 0, any = false;
    data.configs.forEach(function (cfg) {
      codes.forEach(function (code) {
        var v = cellVal(data, cfg.id, code);
        if (v !== null && v !== undefined && v !== '') { s += Number(v); any = true; }
      });
    });
    return any ? s : null;
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

  function overviewSlide(data) {
    var r = data.report;
    var kpiHtml = '';
    function kpi(label, val, cls) {
      if (val === null) return '';
      return '<div class="kpi' + (cls || '') + '"><div class="lbl">' + esc(label) +
        '</div><div class="val">' + num(val) + '</div></div>';
    }
    kpiHtml += kpi('Nội trú hiện có', sumMetric(data, ['hien_co']), '');
    if (cellExists(data, 'kham_benh') || cellExists(data, 'kham'))
      kpiHtml += kpi('Khám ngoại trú', sumMetric(data, ['kham_benh', 'kham']), '');
    kpiHtml += kpi('Giường yêu cầu', sumMetric(data, ['giuong_yc']), ' teal');
    if (cellExists(data, 'pt_cap_cuu') || cellExists(data, 'pt_phien'))
      kpiHtml += kpi('PT trong ngày', sumMetric(data, ['pt_cap_cuu', 'pt_phien']), ' amber');

    var rows = [];
    data.configs.forEach(function (cfg) {
      if (cellVal(data, cfg.id, 'hien_co') === null) return;
      var vao = Number(cellVal(data, cfg.id, 'bn_vao') || 0) + Number(cellVal(data, cfg.id, 'bn_chuyen_den') || 0);
      var ra = Number(cellVal(data, cfg.id, 'bn_ra_vien') || 0) + Number(cellVal(data, cfg.id, 'bn_chuyen_vien') || 0);
      rows.push({ name: cfg.display_name, vao: vao, ra: ra });
    });
    var maxV = 1;
    rows.forEach(function (x) { maxV = Math.max(maxV, x.vao, x.ra); });
    var bars = rows.map(function (x) {
      var hv = Math.round(x.vao / maxV * 100), hr = Math.round(x.ra / maxV * 100);
      return '<div class="barcol"><div class="barpair" style="height:16vh">' +
        '<span style="height:' + hv + '%;background:#378add"></span>' +
        '<span style="height:' + hr + '%;background:#5dcaa5"></span></div>' +
        '<small>' + esc(x.name) + '</small></div>';
    }).join('');
    var chart = rows.length
      ? '<div class="panel"><div class="lbl">BN vào / ra theo khoa</div>' +
        '<div class="bars">' + bars + '</div>' +
        '<div class="legend"><span><b style="background:#378add"></b>Vào</span>' +
        '<span><b style="background:#5dcaa5"></b>Ra</span></div></div>'
      : '';

    var sub = r ? ('Số liệu ' + esc(r.from_time) + ' → ' + esc(r.to_time)) : '';
    return '<div class="slide"><div class="s-head"><div>' +
      '<div class="s-brand">BÁO CÁO GIAO BAN</div>' +
      '<div class="s-title">Giao ban ' + esc(fmtDate(DATE)) + '</div></div>' +
      '<div class="s-sub">' + sub + '</div></div>' +
      '<div class="kpis" style="grid-template-columns:repeat(4,1fr)">' + kpiHtml + '</div>' +
      '<div class="charts">' + chart + '</div></div>';
  }

  function deptSlide(data, cfg) {
    var warn = data.balance_warnings && data.balance_warnings[cfg.id]
      ? '<span class="warn" title="Lệch cân đối">▲ ' + num(data.balance_warnings[cfg.id]) + '</span>' : '';
    var cards = cfg.metrics.map(function (m) {
      var v = cellVal(data, cfg.id, m.code);
      return '<div class="kpi' + kpiClass(m) + '"><div class="lbl">' + esc(m.name) +
        '</div><div class="val">' + num(v) + '</div></div>';
    }).join('');
    var note = noteOf(data, cfg.id);
    var noteHtml = (note && String(note).trim() !== '')
      ? '<div class="note"><div class="lbl">Ghi chú khoa</div><div class="txt">' + note + '</div></div>' : '';
    return '<div class="slide"><div class="s-head"><div class="s-title">' + esc(cfg.display_name) + warn +
      '</div><div class="s-sub">Giao ban ' + esc(fmtDate(DATE)) + '</div></div>' +
      '<div class="kpis" style="grid-template-columns:repeat(4,1fr)">' + cards + '</div>' +
      noteHtml + '</div>';
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
    slides.push(overviewSlide(data));
    deptNames.push({ idx: 0, name: 'Tổng quan' });
    data.configs.forEach(function (cfg) {
      deptNames.push({ idx: slides.length, name: cfg.display_name });
      slides.push(deptSlide(data, cfg));
    });

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

  function setupNav() {
    document.addEventListener('keydown', function (e) {
      if (e.key === 'ArrowRight' || e.key === ' ' || e.key === 'PageDown') { go(current + 1); e.preventDefault(); }
      else if (e.key === 'ArrowLeft' || e.key === 'PageUp') { go(current - 1); e.preventDefault(); }
      else if (e.key === 'Home') { go(0); }
      else if (e.key === 'End') { go(slides.length - 1); }
      else if (e.key === 'f' || e.key === 'F') { toggleFs(); }
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
