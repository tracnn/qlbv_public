# Chế độ trình chiếu (Present) báo cáo giao ban — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Nút "Trình chiếu" trên màn `khth/giao-ban` mở một trang trình chiếu toàn màn hình (kiểu slide deck) hiển thị số liệu giao ban của ngày đang chọn: slide tổng quan toàn viện + mỗi khoa 1 slide, điều hướng bằng phím/click, nền tối chuyên nghiệp.

**Architecture:** Trang Blade độc lập (không dùng layout AdminLTE) tại route `khth/giao-ban/present`. Trang tự gọi API AJAX `khth.giao-ban-show` sẵn có để lấy JSON (report + configs + cells + balance_warnings), rồi dựng toàn bộ slide phía client bằng JS thuần + SVG/CSS (không thư viện ngoài, chạy offline). Không đụng service/model/DB.

**Tech Stack:** Laravel 5.5 / PHP 7.4, Blade (trang trần), JavaScript ES5-an toàn + jQuery (đã có toàn cục qua CDN của layout? KHÔNG — trang này trần, nên dùng `fetch` + DOM thuần, KHÔNG phụ thuộc jQuery), Fullscreen API, SVG cho biểu đồ.

**Spec:** `docs/superpowers/specs/2026-07-08-giao-ban-present-design.md`

**Dữ liệu từ API `khth.giao-ban-show`** (GET, param `date=YYYY-MM-DD`, trả JSON) — đã tồn tại, KHÔNG sửa. Cấu trúc:
```
{
  report: null | { id, report_date, from_time, to_time, status, general_note, ... },
  configs: [ { id, display_name, his_department_id, metrics: [ {code, name, type, ...} ] } ],
  cells:   [ { dept_config_id, metric_code, auto_value, manual_value, note } ],
  balance_warnings: { "<dept_config_id>": <number> },
  is_admin: bool, assigned_dept_ids: [int]
}
```
Giá trị hiển thị 1 ô = `manual_value != null ? manual_value : auto_value`. Ghi chú khoa nằm ở cell có `metric_code === 'note'` (dùng field `note`). Chỉ tiêu của khoa là mảng `config.metrics` (mỗi phần tử có `code`, `name`, `type`); `note` KHÔNG nằm trong `metrics`.

**Ràng buộc quan trọng (bài học từ lần review trước):** MỌI dữ liệu do người dùng nhập (display_name, name chỉ tiêu, note) phải được escape HTML khi dựng DOM — dùng hàm `esc()`. Trang present là trang trần nên KHÔNG có jQuery; dùng `fetch()` và `textContent`/`innerHTML` cẩn thận.

---

## File Structure

| File | Trách nhiệm |
|---|---|
| `routes/web.php` | Thêm 1 route `GET khth/giao-ban/present` trong group `checkrole:giaoban` |
| `app/Http/Controllers/KHTH/GiaoBanController.php` | Thêm method `present(Request $request)` trả view `khth.giaoban-present` |
| `resources/views/khth/giaoban-present.blade.php` | Trang trần: HTML doc + CSS theme tối + JS deck (fetch → render slide → nav/fullscreen). File mới, tự chứa. |
| `resources/views/khth/giaoban-index.blade.php` | Thêm nút "Trình chiếu"; đổi nhãn nút "Xem" → "Làm mới" |

Không có unit test mới (thuần view client-side). Verify bằng trình duyệt/preview + kiểm tra escape XSS.

---

### Task 1: Route + controller method + nút trên màn index

**Files:**
- Modify: `app/Http/Controllers/KHTH/GiaoBanController.php`
- Modify: `routes/web.php`
- Modify: `resources/views/khth/giaoban-index.blade.php`

- [ ] **Step 1: Thêm method `present` vào GiaoBanController**

Mở `app/Http/Controllers/KHTH/GiaoBanController.php`. Ngay TRƯỚC method `export(Request $request)` (dòng bắt đầu `public function export`), thêm:

```php
    /** Trang trình chiếu toàn màn hình cho báo cáo ngày đang chọn. */
    public function present(Request $request)
    {
        $date = $request->input('date', date('Y-m-d'));
        return view('khth.giaoban-present', [
            'date' => $date,
            'showUrl' => route('khth.giao-ban-show'),
        ]);
    }

```

- [ ] **Step 2: Thêm route**

Trong `routes/web.php`, tìm group `middleware => ['checkrole:giaoban']` chứa các route `giao-ban*`. Ngay sau dòng route `giao-ban/export` (`->name('khth.giao-ban-export');`), thêm:

```php
        Route::get('giao-ban/present', 'KHTH\GiaoBanController@present')->name('khth.giao-ban-present');
```

(Đặt trước các route `giao-ban/cau-hinh*` cũng được — không có route tham số động nên thứ tự không xung đột. Chỉ cần nằm trong group `checkrole:giaoban`.)

- [ ] **Step 3: Thêm nút "Trình chiếu" và đổi nhãn "Xem" → "Làm mới" trong index**

Trong `resources/views/khth/giaoban-index.blade.php`, thay khối nút (dòng 16–22) hiện tại:

```blade
        <button id="btn-view" class="btn btn-default"><i class="fa fa-eye"></i> Xem</button>
        @if($isAdmin)
        <button id="btn-fetch" class="btn btn-primary"><i class="fa fa-refresh"></i> Lấy số liệu</button>
        <button id="btn-finalize" class="btn btn-danger"><i class="fa fa-lock"></i> Chốt báo cáo</button>
        <button id="btn-unlock" class="btn btn-warning" style="display:none"><i class="fa fa-unlock"></i> Mở khóa</button>
        @endif
        <a id="btn-export" class="btn btn-success"><i class="fa fa-file-excel-o"></i> Xuất Excel</a>
```

bằng:

```blade
        <button id="btn-view" class="btn btn-default"><i class="fa fa-refresh"></i> Làm mới</button>
        <button id="btn-present" class="btn btn-info"><i class="fa fa-desktop"></i> Trình chiếu</button>
        @if($isAdmin)
        <button id="btn-fetch" class="btn btn-primary"><i class="fa fa-cloud-download"></i> Lấy số liệu</button>
        <button id="btn-finalize" class="btn btn-danger"><i class="fa fa-lock"></i> Chốt báo cáo</button>
        <button id="btn-unlock" class="btn btn-warning" style="display:none"><i class="fa fa-unlock"></i> Mở khóa</button>
        @endif
        <a id="btn-export" class="btn btn-success"><i class="fa fa-file-excel-o"></i> Xuất Excel</a>
```

(Đổi icon "Lấy số liệu" sang `fa-cloud-download` để không trùng icon refresh của "Làm mới".)

- [ ] **Step 4: Wire nút Present (mở tab mới) trong JS của index**

Trong `resources/views/khth/giaoban-index.blade.php`, tìm trong `$(function () { ... })` dòng gắn sự kiện nút export:

```js
  $('#btn-export').on('click', function () {
    window.location = '{{ route('khth.giao-ban-export') }}?date=' + $('#report_date').val();
  });
```

Ngay SAU khối đó (trước `loadReport();` cuối hàm), thêm:

```js
  $('#btn-present').on('click', function () {
    window.open('{{ route('khth.giao-ban-present') }}?date=' + $('#report_date').val(), '_blank');
  });
```

- [ ] **Step 5: Verify cú pháp**

Run: `php -l app/Http/Controllers/KHTH/GiaoBanController.php`
Expected: `No syntax errors detected`.
Cũng grep xác nhận route đã thêm:
Run (PowerShell): `Select-String -Path routes/web.php -Pattern 'giao-ban-present'`
Expected: 1 dòng khớp.

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/KHTH/GiaoBanController.php routes/web.php resources/views/khth/giaoban-index.blade.php
git commit -m "feat(giao-ban): route+nut Present, doi nhan Xem->Lam moi"
```

---

### Task 2: Trang trình chiếu — khung trang, tải dữ liệu, dựng slide

**Files:**
- Create: `resources/views/khth/giaoban-present.blade.php`

Đây là trang HTML hoàn chỉnh, độc lập (không extends layout). Ở task này viết TOÀN BỘ file gồm CSS theme, khung deck, tải JSON, dựng slide tổng quan + slide từng khoa, và điều hướng cơ bản (phím/click/fullscreen/nav). File tự chứa, không phụ thuộc jQuery/thư viện ngoài.

- [ ] **Step 1: Tạo file view đầy đủ**

Tạo `resources/views/khth/giaoban-present.blade.php` với nội dung:

```blade
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
      <button class="btn" id="jump-btn"><i></i>☰ Khoa</button>
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

  var slides = [];   // mảng HTML string
  var current = 0;
  var deptNames = []; // {idx, name} để jump

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

    // bar chart: chỉ khoa có hien_co
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
      ? '<div class="note"><div class="lbl">Ghi chú khoa</div><div class="txt">' + esc(note) + '</div></div>' : '';
    return '<div class="slide"><div class="s-head"><div class="s-title">' + esc(cfg.display_name) + warn +
      '</div><div class="s-sub">Giao ban ' + esc(fmtDate(DATE)) + '</div></div>' +
      '<div class="kpis" style="grid-template-columns:repeat(4,1fr)">' + cards + '</div>' +
      noteHtml + '</div>';
  }

  function build(data) {
    slides = [];
    deptNames = [];
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
    // chèn slide vào đầu #stage; #jump (position:absolute, z-index:5) vẫn nổi trên
    stage.insertAdjacentHTML('afterbegin', frag);

    // jump list
    var jl = document.getElementById('jump-list');
    jl.innerHTML = deptNames.map(function (d) {
      return '<button data-idx="' + d.idx + '">' + esc(d.name) + '</button>';
    }).join('');

    // dots
    var dots = document.getElementById('dots');
    dots.innerHTML = slides.map(function () { return '<i></i>'; }).join('');

    go(0);
    setupNav();
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
      if (e.target.closest('#jump')) return; // không lật khi bấm vùng jump
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
```

- [ ] **Step 2: Verify Blade compile (không lỗi cú pháp)**

Run: `php artisan view:clear`
Expected: chạy không lỗi. Sau đó xác nhận file tồn tại:
Run (PowerShell): `Test-Path resources/views/khth/giaoban-present.blade.php`
Expected: `True`. (Blade trần chỉ có `@json()` là directive; nếu compile lỗi sẽ báo khi mở trang ở Task 3.)

- [ ] **Step 3: Commit**

```bash
git add resources/views/khth/giaoban-present.blade.php
git commit -m "feat(giao-ban): trang trinh chieu slide deck (present)"
```

---

### Task 3: Kiểm thử end-to-end + escape XSS

**Files:** không sửa code (trừ khi phát hiện lỗi). Chỉ verify.

Dùng preview tools nếu có dev server; nếu không, dùng script bootstrap tạo dữ liệu rồi mở URL trong trình duyệt/preview.

- [ ] **Step 1: Tạo dữ liệu báo cáo mẫu để trình chiếu**

Tạo file tạm `scratchpad/seed_present.php` (đường dẫn tuyệt đối tới project) với nội dung — seed 2 khoa + report có số liệu + 1 ghi chú chứa payload XSS để kiểm tra escape:

```php
<?php
require 'C:/Users/tracnn/qlbv/vendor/autoload.php';
$app = require 'C:/Users/tracnn/qlbv/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use App\Models\GiaoBan\GiaoBanDeptConfig;
use App\Services\GiaoBan\GiaoBanMetricService;
use App\Services\GiaoBan\GiaoBanReportService;
use App\Models\GiaoBan\GiaoBanReportCell;
use Illuminate\Support\Facades\DB;

GiaoBanReportCell::query()->delete();
DB::table('giaoban_reports')->delete();
GiaoBanDeptConfig::query()->delete();

$metrics = json_encode([
  ['code'=>'bn_cu','name'=>'BN cũ','type'=>'census_from'],
  ['code'=>'bn_vao','name'=>'BN vào','type'=>'movement_in'],
  ['code'=>'bn_chuyen_den','name'=>'BN chuyển đến','type'=>'movement_transfer_in'],
  ['code'=>'bn_ra_vien','name'=>'BN ra viện','type'=>'end_type','end_codes'=>['RV','HK','CC','XV','KH','TR']],
  ['code'=>'bn_chuyen_vien','name'=>'BN chuyển viện','type'=>'end_type','end_codes'=>['CV']],
  ['code'=>'hien_co','name'=>'Hiện có','type'=>'census_to'],
]);
foreach ([['Khoa Nội TH CS1',73],['Khoa Ngoại CT',51]] as $d) {
  GiaoBanDeptConfig::create(['his_department_id'=>$d[1],'display_name'=>$d[0],'sort_order'=>1,'is_active'=>1,'metrics'=>$metrics]);
}
$svc = new GiaoBanReportService(new GiaoBanMetricService());
$report = $svc->getOrCreateReport(date('Y-m-d'), date('Y-m-d 07:00:00', strtotime('-1 day')), date('Y-m-d 07:00:00'), 1);
$svc->fetchAndStore($report, date('Y-m-d 07:00:00', strtotime('-1 day')), date('Y-m-d 07:00:00'), 1);
// ghi chú có payload XSS
$cfg = GiaoBanDeptConfig::first();
GiaoBanReportCell::create(['report_id'=>$report->id,'dept_config_id'=>$cfg->id,'metric_code'=>'note',
  'note'=>'Test <script>alert(1)</script> </textarea> ghi chú','updated_by'=>1]);
echo "Seeded report id {$report->id} date " . date('Y-m-d') . "\n";
```

Run: `php scratchpad/seed_present.php`
Expected: in ra `Seeded report id ... date YYYY-MM-DD`.

- [ ] **Step 2: Mở trang present và xác minh hiển thị**

Khởi động dev server (dùng preview_start nếu có cấu hình, hoặc `php artisan serve`), đăng nhập bằng tài khoản có quyền giao ban, mở:
`/khth/giao-ban/present?date=<hôm nay>`

Xác minh (chụp màn hình):
- Slide 1 tổng quan: tiêu đề "Giao ban <thứ>, dd/mm/yyyy", KPI "Nội trú hiện có" hiện số > 0, KPI "Giường yêu cầu" (nếu có cell) ; biểu đồ cột BN vào/ra hiển thị 2 khoa.
- Slide 2, 3: mỗi khoa 1 slide với các card chỉ tiêu đúng số; slide khoa Nội TH có hộp ghi chú.
- Bấm `→`/`←` và click trái/phải chuyển slide; counter đổi (VD 2/3); dấu chấm cập nhật.
- Nút "Toàn màn hình" / phím F bật fullscreen.
- Nút "☰ Khoa" mở danh sách, click tên khoa nhảy đúng slide.

- [ ] **Step 3: Xác minh escape XSS**

Trên slide khoa Nội TH (có ghi chú chứa `<script>`), xác nhận:
- KHÔNG có hộp thoại alert bật ra.
- Ghi chú hiển thị nguyên văn chuỗi `Test <script>alert(1)</script> </textarea> ghi chú` dưới dạng text.
Nếu alert bật ra → lỗi escape, kiểm tra lại hàm `esc()` được áp cho `note`.

- [ ] **Step 4: Xác minh slide trống**

Mở `/khth/giao-ban/present?date=2000-01-01` (ngày chắc chắn không có báo cáo).
Expected: thông báo "Chưa có dữ liệu báo cáo cho ngày …", counter `0/0`, không lỗi JS (kiểm tra console).

- [ ] **Step 5: Dọn dữ liệu test & xác nhận test cũ không vỡ**

Xoá dữ liệu seed:
```bash
php -r "require 'C:/Users/tracnn/qlbv/vendor/autoload.php'; \$a=require 'C:/Users/tracnn/qlbv/bootstrap/app.php'; \$a->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap(); App\Models\GiaoBan\GiaoBanReportCell::query()->delete(); Illuminate\Support\Facades\DB::table('giaoban_reports')->delete(); App\Models\GiaoBan\GiaoBanDeptConfig::query()->delete(); echo 'cleaned';"
```
Chạy lại test cũ để chắc không vỡ:
Run: `vendor\bin\phpunit tests\Unit\GiaoBan`
Expected: `OK (12 tests, ...)`.

- [ ] **Step 6: Cập nhật readme + commit**

Thêm đầu `readme.md`:
```markdown
# 08/07/2026 (cập nhật 3)

- Bổ sung chế độ Trình chiếu (Present) cho Báo cáo giao ban: mở trang slide toàn màn hình (tổng quan toàn viện + mỗi khoa 1 slide), điều hướng bằng phím/click, nền tối chuyên nghiệp; đổi nút "Xem" thành "Làm mới".
```

```bash
git add readme.md
git commit -m "docs(giao-ban): readme che do trinh chieu; hoan tat kiem thu"
```
