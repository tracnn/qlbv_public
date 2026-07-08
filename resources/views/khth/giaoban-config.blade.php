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
          <thead><tr><th style="width:70px">TT</th><th>Tên hiển thị</th><th style="width:130px">Loại khối</th><th>Khoa HIS (gộp)</th><th>Chỉ tiêu (JSON)</th><th style="width:60px">BID</th><th style="width:60px"></th></tr></thead>
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

<script type="application/json" id="tpl-dieu_tri">[
  {"code":"bn_cu","name":"BN cũ","type":"census_from"},
  {"code":"bn_vao","name":"BN vào","type":"movement_in"},
  {"code":"bn_chuyen_den","name":"BN chuyển đến","type":"movement_transfer_in"},
  {"code":"bn_ra_vien","name":"BN ra viện","type":"end_type","end_codes":["RV","HK","CC","XV","KH","TR"]},
  {"code":"bn_chuyen_vien","name":"BN chuyển viện","type":"end_type","end_codes":["CV"]},
  {"code":"bn_tu_vong","name":"BN tử vong","type":"end_type","end_codes":["TV"]},
  {"code":"bn_chuyen_khoa","name":"BN chuyển khoa","type":"movement_transfer_out"},
  {"code":"hien_co","name":"Hiện có","type":"census_to"}
]</script>
<script type="application/json" id="tpl-kham">[
  {"code":"luot_kham","name":"Lượt khám","type":"exam_visit"},
  {"code":"vao_vien","name":"Vào viện","type":"exam_visit","filter":{"treatment_type_ids":[3]}},
  {"code":"cap_toa_ve","name":"Cấp toa/ngoại trú","type":"exam_visit","filter":{"treatment_type_ids":[2]}},
  {"code":"kham_yeu_cau","name":"Khám yêu cầu","type":"exam_visit","filter":{"patient_type_ids":[82]}},
  {"code":"kham_bhyt","name":"Khám BHYT","type":"exam_visit","filter":{"patient_type_ids":[1]}},
  {"code":"chuyen_gia","name":"Khám chuyên gia","type":"manual"}
]</script>
<script type="application/json" id="tpl-cls_tong">[
  {"code":"tong_dv","name":"Tổng dịch vụ","type":"service_count","filter":{"execute_department_id_self":true}}
]</script>
<script type="application/json" id="tpl-cls_cdha">[
  {"code":"cdha_xq","name":"X-Quang","type":"service_count","filter":{"execute_department_id_self":true,"service_type_ids":[3],"diim_type_ids":[1]}},
  {"code":"cdha_ct","name":"CT","type":"service_count","filter":{"execute_department_id_self":true,"service_type_ids":[3],"diim_type_ids":[2]}},
  {"code":"cdha_mri","name":"MRI","type":"service_count","filter":{"execute_department_id_self":true,"service_type_ids":[3],"diim_type_ids":[3]}},
  {"code":"cdha_khac","name":"CĐHA khác","type":"service_count","filter":{"execute_department_id_self":true,"service_type_ids":[3],"diim_type_other_of":[1,2,3]}},
  {"code":"sieu_am","name":"Siêu âm","type":"service_count","filter":{"execute_department_id_self":true,"service_type_ids":[10]}}
]</script>
<script type="application/json" id="tpl-cls_xn">[
  {"code":"xn_hh","name":"Huyết học","type":"service_count","filter":{"execute_department_id_self":true,"service_type_ids":[2],"test_type_ids":[1]}},
  {"code":"xn_sh","name":"Sinh hóa","type":"service_count","filter":{"execute_department_id_self":true,"service_type_ids":[2],"test_type_ids":[3]}},
  {"code":"xn_vs","name":"Vi sinh","type":"service_count","filter":{"execute_department_id_self":true,"service_type_ids":[2],"test_type_ids":[2]}},
  {"code":"xn_md","name":"Miễn dịch","type":"service_count","filter":{"execute_department_id_self":true,"service_type_ids":[2],"test_type_ids":[4]}},
  {"code":"xn_nt","name":"Nước tiểu","type":"service_count","filter":{"execute_department_id_self":true,"service_type_ids":[2],"test_type_ids":[7]}},
  {"code":"xn_khac","name":"XN khác","type":"service_count","filter":{"execute_department_id_self":true,"service_type_ids":[2],"test_type_other_of":[1,2,3,4,7]}}
]</script>
@stop

@section('js')
<script>
var HIS_DEPTS = @json($hisDepartments);
var STATE = { configs: [], assignments: [], user_names: {} };
var PICKED_USER = null;
var BLOCKS = { dieu_tri: 'Điều trị (nội trú)', kham: 'Khám (ngoại trú)', can_lam_sang: 'Cận lâm sàng' };
var TEMPLATES = {
  dieu_tri: [{ key: 'dieu_tri', label: 'Điều trị (mặc định)' }],
  kham: [{ key: 'kham', label: 'Khám (mặc định)' }],
  can_lam_sang: [
    { key: 'cls_tong', label: 'Tổng dịch vụ' },
    { key: 'cls_cdha', label: 'CĐHA (XQ/CT/MRI/SA)' },
    { key: 'cls_xn', label: 'Xét nghiệm (HH/SH/VS...)' }
  ]
};

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
function tplOptions(block) {
  var h = '<option value="">-- Nạp mẫu --</option>';
  (TEMPLATES[block] || []).forEach(function (t) { h += '<option value="' + t.key + '">' + esc(t.label) + '</option>'; });
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
      '<td><textarea class="form-control f-metrics" rows="3">' + esc(c.metrics) + '</textarea>' +
      '<select class="form-control input-sm f-tpl" style="margin-top:4px">' + tplOptions(block) + '</select></td>' +
      '<td><input type="checkbox" class="f-active"' + (c.is_active ? ' checked' : '') + '></td>' +
      '<td><button class="btn btn-sm btn-primary btn-save-cfg">Lưu</button></td></tr>');
  });
  var $sel = $('#assign-depts').empty();
  STATE.configs.forEach(function (c) {
    if (c.is_active) $sel.append('<option value="' + c.id + '">' + esc(c.display_name) + '</option>');
  });
}

function loadAll() {
  $.get('{{ route('khth.giao-ban-config-fetch') }}', function (res) {
    STATE = res; renderConfigs(); syncAssign();
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
      metrics: $('#tpl-dieu_tri').text().trim()
    }).done(loadAll).fail(function (xhr) {
      alert(xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Lỗi thêm khoa');
    });
  });

  // đổi loại khối -> nạp lại danh sách mẫu tương ứng
  $('#tbl-configs').on('change', '.f-block', function () {
    var $tr = $(this).closest('tr');
    $tr.find('.f-tpl').html(tplOptions($(this).val()));
  });

  // chọn mẫu -> đổ vào ô JSON chỉ tiêu
  $('#tbl-configs').on('change', '.f-tpl', function () {
    var key = $(this).val();
    if (!key) return;
    var $tr = $(this).closest('tr');
    $tr.find('.f-metrics').val($('#tpl-' + key).text().trim());
    $(this).val('');
  });

  $('#tbl-configs').on('click', '.btn-save-cfg', function () {
    var $tr = $(this).closest('tr');
    $.post('{{ url('khth/giao-ban/cau-hinh') }}/' + $tr.data('id'), {
      _token: '{{ csrf_token() }}',
      sort_order: $tr.find('.f-sort').val(), display_name: $tr.find('.f-name').val(),
      block_type: $tr.find('.f-block').val(),
      his_department_ids: collectIds($tr.find('.f-depts')),
      metrics: $tr.find('.f-metrics').val(),
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
});
</script>
@stop
