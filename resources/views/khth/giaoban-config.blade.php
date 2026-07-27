@extends('adminlte::page')
@section('title', 'Cấu hình báo cáo giao ban')
@section('content_header')<h1>Cấu hình báo cáo giao ban</h1>@stop

@section('content')
<div class="row">
  <div class="col-md-8">
    <div class="box box-primary">
      <div class="box-header with-border"><b>Khoa hiển thị trên báo cáo</b></div>
      <div class="box-body table-responsive">
        <table class="table table-bordered" id="tbl-configs">
          <thead><tr><th style="width:70px">TT</th><th>Tên hiển thị</th><th style="width:130px">Loại khối</th><th>Khoa HIS (gộp)</th><th>Chỉ tiêu</th><th style="width:60px">BID</th><th style="width:60px"></th></tr></thead>
          <tbody></tbody>
        </table>
        <button id="btn-add" class="btn btn-primary"><i class="fa fa-plus"></i> Thêm khoa</button>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="box box-warning">
      <div class="box-header with-border"><b>Gán tài khoản HIS ↔ khoa</b></div>
      <div class="box-body">
        <label>Tìm tài khoản (tên / loginname)</label>
        <input type="text" id="user-search" class="form-control" placeholder="gõ ≥ 2 ký tự...">
        <div id="user-results" class="list-group" style="max-height:180px;overflow:auto;margin-top:4px"></div>
        <div id="user-picked" style="margin-top:8px"></div>
        <label style="margin-top:10px">Khoa được nhập</label>
        <select id="assign-depts" class="form-control" multiple size="10"></select>
        <button id="btn-assign" class="btn btn-warning" style="margin-top:10px" disabled>Lưu gán khoa</button>
      </div>
    </div>
  </div>
</div>

<div class="row">
  <div class="col-md-6">
    <div class="box box-success">
      <div class="box-header with-border"><b>Danh mục chức danh trực</b></div>
      <div class="box-body table-responsive">
        <table class="table table-bordered" id="tbl-duty"><thead><tr><th style="width:70px">TT</th><th>Chức danh</th><th style="width:70px">Hoạt động</th><th style="width:60px"></th></tr></thead><tbody></tbody></table>
        <button id="btn-add-duty" class="btn btn-success"><i class="fa fa-plus"></i> Thêm chức danh</button>
      </div>
    </div>
  </div>
  <div class="col-md-6">
    <div class="box box-info">
      <div class="box-header with-border"><b>Người được cập nhật kíp trực</b></div>
      <div class="box-body">
        <label>Thêm tài khoản (tên / loginname)</label>
        <input type="text" id="editor-search" class="form-control" placeholder="gõ ≥ 2 ký tự...">
        <div id="editor-results" class="list-group" style="max-height:160px;overflow:auto;margin-top:4px"></div>
        <div id="editor-list" style="margin-top:8px"></div>
        <button id="btn-save-editors" class="btn btn-info" style="margin-top:10px">Lưu danh sách</button>
        <p class="text-muted" style="margin-top:6px">Admin luôn được cập nhật. Các tài khoản trong danh sách này cũng được cập nhật kíp trực.</p>
      </div>
    </div>
  </div>
</div>

@include('khth.partials.giaoban-metric-builder')
@stop

@section('js')
<script src="{{ asset('js/giaoban/metric-builder.js') }}"></script>
<script>
MetricBuilder.init({
  schema: @json(\App\Services\GiaoBan\MetricSchema::TYPES),
  commonFields: @json(\App\Services\GiaoBan\MetricSchema::COMMON_FIELDS),
  blockLabels: { dieu_tri: 'Điều trị (nội trú)', kham: 'Khám (ngoại trú)', can_lam_sang: 'Cận lâm sàng' },
  csrf: '{{ csrf_token() }}',
  routes: {
    update: '{{ url('khth/giao-ban/cau-hinh') }}/__ID__',
    catalogs: '{{ route('khth.giao-ban-config-catalogs') }}',
    catalog: '{{ url('khth/giao-ban/cau-hinh/danh-muc') }}/__KEY__',
    preview: '{{ url('khth/giao-ban/cau-hinh') }}/__ID__/tinh-thu',
    templateStore: '{{ route('khth.giao-ban-config-template-store') }}'
  },
  remoteCatalogs: @json(array_values(array_diff(
      \App\Services\GiaoBan\GiaoBanCatalogService::allKeys(),
      \App\Services\GiaoBan\GiaoBanCatalogService::smallKeys()
  )))
});
</script>
<script>
var HIS_DEPTS = @json($hisDepartments);
var STATE = { configs: [], assignments: [], user_names: {} };
var PICKED_USER = null;
var BLOCKS = { dieu_tri: 'Điều trị (nội trú)', kham: 'Khám (ngoại trú)', can_lam_sang: 'Cận lâm sàng' };

function esc(s) {
  return String(s === null || s === undefined ? '' : s).replace(/[&<>"']/g, function (c) {
    return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
  });
}
function blockOptions(sel) {
  var h = '';
  for (var k in BLOCKS) h += '<option value="' + k + '"' + (k === sel ? ' selected' : '') + '>' + esc(BLOCKS[k]) + '</option>';
  return h;
}
function deptMultiOptions(selectedIds) {
  var sel = {};
  (selectedIds || []).forEach(function (id) { sel[String(id)] = 1; });
  var h = '';
  HIS_DEPTS.forEach(function (d) {
    h += '<option value="' + d.id + '"' + (sel[String(d.id)] ? ' selected' : '') + '>' + esc(d.department_name) + '</option>';
  });
  return h;
}
function parseIds(jsonStr) {
  try { var a = JSON.parse(jsonStr || '[]'); return Array.isArray(a) ? a : []; } catch (e) { return []; }
}
function demChiTieu(jsonStr) {
  try { var a = JSON.parse(jsonStr || '[]'); return Array.isArray(a) ? a.length : 0; } catch (e) { return '?'; }
}

function renderConfigs() {
  var $tb = $('#tbl-configs tbody').empty();
  STATE.configs.forEach(function (c) {
    var ids = parseIds(c.his_department_ids);
    var block = c.block_type || 'dieu_tri';
    $tb.append('<tr data-id="' + c.id + '">' +
      '<td><input class="form-control f-sort" type="number" value="' + (c.sort_order || 0) + '"></td>' +
      '<td><input class="form-control f-name" value="' + esc(c.display_name) + '"></td>' +
      '<td><select class="form-control f-block">' + blockOptions(block) + '</select></td>' +
      '<td><select class="form-control f-depts" multiple size="4">' + deptMultiOptions(ids) + '</select></td>' +
      '<td><button class="btn btn-default btn-sm btn-edit-metrics">Chỉ tiêu (' + demChiTieu(c.metrics) + ') <i class="fa fa-pencil"></i></button></td>' +
      '<td><input type="checkbox" class="f-active"' + (c.is_active ? ' checked' : '') + '></td>' +
      '<td><button class="btn btn-sm btn-primary btn-save-cfg">Lưu</button></td></tr>');
  });
  var $sel = $('#assign-depts').empty();
  STATE.configs.forEach(function (c) {
    if (c.is_active) $sel.append('<option value="' + c.id + '">' + esc(c.display_name) + '</option>');
  });
}

function renderDutyPositions() {
  var $tb = $('#tbl-duty tbody').empty();
  (STATE.duty_positions || []).forEach(function (p) {
    $tb.append('<tr data-id="' + p.id + '">' +
      '<td><input class="form-control d-sort" type="number" value="' + (p.sort_order || 0) + '"></td>' +
      '<td><input class="form-control d-name" value="' + esc(p.name) + '"></td>' +
      '<td><input type="checkbox" class="d-active"' + (p.is_active ? ' checked' : '') + '></td>' +
      '<td><button class="btn btn-sm btn-primary btn-save-duty">Lưu</button></td></tr>');
  });
}

var EDITORS = []; // {id, name}
function initEditors() {
  EDITORS = (STATE.duty_editors || []).map(function (uid) {
    return { id: uid, name: (STATE.duty_editor_names && STATE.duty_editor_names[uid]) ? STATE.duty_editor_names[uid] : ('#' + uid) };
  });
  renderEditors();
}
function renderEditors() {
  var $l = $('#editor-list').empty();
  if (!EDITORS.length) { $l.html('<i class="text-muted">Chưa có ai (ngoài admin).</i>'); return; }
  EDITORS.forEach(function (e) {
    $l.append('<span style="display:inline-flex;align-items:center;gap:4px;background:#eef;border:1px solid #cce;border-radius:4px;padding:2px 8px;margin:2px">' +
      esc(e.name) + ' <a href="#" class="editor-remove" data-id="' + e.id + '" style="color:#c00;font-weight:bold">&times;</a></span>');
  });
}

function loadAll() {
  $.get('{{ route('khth.giao-ban-config-fetch') }}', function (res) {
    STATE = res; renderConfigs(); renderDutyPositions(); initEditors(); syncAssign();
  });
}

function collectIds($sel) {
  var v = $sel.val() || [];
  return JSON.stringify(v.map(function (x) { return parseInt(x, 10); }));
}

function syncAssign() {
  if (!PICKED_USER) return;
  var mine = STATE.assignments.filter(function (a) { return a.user_id === PICKED_USER.id; })
    .map(function (a) { return String(a.dept_config_id); });
  $('#assign-depts').val(mine);
}

$(function () {
  loadAll();

  $('#btn-add').on('click', function () {
    var name = prompt('Tên hiển thị khoa mới:');
    if (!name) return;
    $.post('{{ route('khth.giao-ban-config-store') }}', {
      _token: '{{ csrf_token() }}', display_name: name, block_type: 'dieu_tri',
      sort_order: STATE.configs.length + 1, his_department_ids: '[]',
      metrics: JSON.stringify([{ code: 'bn_cu', name: 'BN cũ', type: 'census_from' }])
    }).done(loadAll).fail(function (xhr) {
      alert(xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Lỗi thêm khoa');
    });
  });

  // mo modal form builder de sua chi tieu cua mot khoa
  $('#tbl-configs').on('click', '.btn-edit-metrics', function () {
    var id = $(this).closest('tr').data('id');
    var cfg = null;
    STATE.configs.forEach(function (c) { if (c.id === id) cfg = c; });
    if (!cfg) return;
    // lay block_type + danh sach khoa HIS dang chon tren hang (co the vua doi chua luu) —
    // neu dung cfg.his_department_ids goc trong DB thi tinh thu se ra so theo khoa cu, gay hieu nham
    cfg = $.extend({}, cfg, {
      block_type: $(this).closest('tr').find('.f-block').val(),
      his_department_ids: collectIds($(this).closest('tr').find('.f-depts'))
    });
    MetricBuilder.open(cfg, function () { loadAll(); }, {
      templates: STATE.metric_templates || [],
      configs: STATE.configs || []
    });
  });

  $('#tbl-configs').on('click', '.btn-save-cfg', function () {
    var $tr = $(this).closest('tr');
    $.post('{{ url('khth/giao-ban/cau-hinh') }}/' + $tr.data('id'), {
      _token: '{{ csrf_token() }}',
      sort_order: $tr.find('.f-sort').val(), display_name: $tr.find('.f-name').val(),
      block_type: $tr.find('.f-block').val(),
      his_department_ids: collectIds($tr.find('.f-depts')),
      is_active: $tr.find('.f-active').is(':checked') ? 1 : 0
    }).done(loadAll).fail(function (xhr) {
      alert(xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Lỗi lưu');
    });
  });

  var timer = null;
  $('#user-search').on('input', function () {
    var q = $(this).val();
    clearTimeout(timer);
    if (q.length < 2) { $('#user-results').empty(); return; }
    timer = setTimeout(function () {
      $.get('{{ route('khth.giao-ban-config-search-users') }}', { q: q }, function (rows) {
        var $r = $('#user-results').empty();
        rows.forEach(function (u) {
          $r.append('<a href="#" class="list-group-item u-pick" data-id="' + u.id + '" data-name="' +
            esc((u.username || u.loginname)) + '">' + esc(u.username || u.loginname) +
            ' <small>(' + esc(u.loginname) + ')</small></a>');
        });
      });
    }, 300);
  });
  $('#user-results').on('click', '.u-pick', function (e) {
    e.preventDefault();
    PICKED_USER = { id: parseInt($(this).data('id'), 10), name: $(this).data('name') };
    $('#user-picked').html('Đang gán cho: <b>' + esc(PICKED_USER.name) + '</b>');
    $('#user-results').empty();
    $('#btn-assign').prop('disabled', false);
    syncAssign();
  });

  $('#btn-assign').on('click', function () {
    if (!PICKED_USER) return;
    $.post('{{ route('khth.giao-ban-config-assign') }}', {
      _token: '{{ csrf_token() }}', user_id: PICKED_USER.id,
      dept_config_ids: $('#assign-depts').val() || []
    }).done(function () { alert('Đã lưu'); loadAll(); });
  });

  $('#btn-add-duty').on('click', function () {
    var name = prompt('Tên chức danh trực:');
    if (!name) return;
    $.post('{{ route('khth.giao-ban-config-duty-store') }}', { _token: '{{ csrf_token() }}', name: name, sort_order: (STATE.duty_positions || []).length + 1 })
      .done(loadAll);
  });
  $('#tbl-duty').on('click', '.btn-save-duty', function () {
    var $tr = $(this).closest('tr');
    $.post('{{ url('khth/giao-ban/cau-hinh-duty') }}/' + $tr.data('id'), {
      _token: '{{ csrf_token() }}', name: $tr.find('.d-name').val(),
      sort_order: $tr.find('.d-sort').val(), is_active: $tr.find('.d-active').is(':checked') ? 1 : 0
    }).done(loadAll);
  });

  // Người được cập nhật kíp trực
  var editorTimer = null;
  $('#editor-search').on('input', function () {
    var q = $(this).val();
    clearTimeout(editorTimer);
    if (q.length < 2) { $('#editor-results').empty(); return; }
    editorTimer = setTimeout(function () {
      $.get('{{ route('khth.giao-ban-config-search-users') }}', { q: q }, function (rows) {
        var $r = $('#editor-results').empty();
        (rows || []).forEach(function (u) {
          $r.append('<a href="#" class="editor-pick" data-id="' + u.id + '" data-name="' + esc(u.username || u.loginname) +
            '">' + esc(u.username || u.loginname) + ' <small>(' + esc(u.loginname) + ')</small></a>');
        });
      });
    }, 300);
  });
  $('#editor-results').on('click', '.editor-pick', function (e) {
    e.preventDefault();
    var id = parseInt($(this).data('id'), 10);
    if (!EDITORS.some(function (x) { return x.id === id; })) {
      EDITORS.push({ id: id, name: $(this).data('name') });
      renderEditors();
    }
    $('#editor-results').empty();
    $('#editor-search').val('');
  });
  $('#editor-list').on('click', '.editor-remove', function (e) {
    e.preventDefault();
    var id = parseInt($(this).data('id'), 10);
    EDITORS = EDITORS.filter(function (x) { return x.id !== id; });
    renderEditors();
  });
  $('#btn-save-editors').on('click', function () {
    $.post('{{ route('khth.giao-ban-config-duty-editors') }}', {
      _token: '{{ csrf_token() }}', user_ids: EDITORS.map(function (x) { return x.id; })
    }).done(function () { alert('Đã lưu'); loadAll(); });
  });
});
</script>
@stop
