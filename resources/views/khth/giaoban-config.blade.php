@extends('adminlte::page')
@section('title', 'Cấu hình báo cáo giao ban')
@section('content_header')<h1>Cấu hình báo cáo giao ban</h1>@stop

@section('content')
<div class="row">
  <div class="col-md-7">
    <div class="box box-primary">
      <div class="box-header with-border"><b>Khoa hiển thị trên báo cáo</b></div>
      <div class="box-body">
        <table class="table table-bordered" id="tbl-configs">
          <thead><tr><th>Thứ tự</th><th>Tên hiển thị</th><th>Khoa HIS</th><th>Chỉ tiêu (JSON)</th><th>Hoạt động</th><th></th></tr></thead>
          <tbody></tbody>
        </table>
        <button id="btn-add" class="btn btn-primary"><i class="fa fa-plus"></i> Thêm khoa</button>
      </div>
    </div>
  </div>
  <div class="col-md-5">
    <div class="box box-warning">
      <div class="box-header with-border"><b>Gán tài khoản ↔ khoa</b></div>
      <div class="box-body">
        <label>Tài khoản</label>
        <select id="assign-user" class="form-control">
          @foreach($users as $u)<option value="{{ $u->id }}">{{ $u->name }} ({{ $u->email }})</option>@endforeach
        </select>
        <label style="margin-top:10px">Khoa được nhập</label>
        <select id="assign-depts" class="form-control" multiple size="12"></select>
        <button id="btn-assign" class="btn btn-warning" style="margin-top:10px">Lưu gán khoa</button>
      </div>
    </div>
  </div>
</div>

<script type="application/json" id="default-metrics">[
  {"code":"bn_cu","name":"BN cũ","type":"census_from"},
  {"code":"bn_vao","name":"BN vào","type":"movement_in"},
  {"code":"bn_chuyen_den","name":"BN chuyển đến","type":"movement_transfer_in"},
  {"code":"bn_ra_vien","name":"BN ra viện","type":"end_type","end_codes":["RV","HK","CC","XV","KH","TR"]},
  {"code":"bn_chuyen_vien","name":"BN chuyển viện","type":"end_type","end_codes":["CV"]},
  {"code":"bn_tu_vong","name":"BN tử vong","type":"end_type","end_codes":["TV"]},
  {"code":"bn_chuyen_khoa","name":"BN chuyển khoa","type":"movement_transfer_out"},
  {"code":"hien_co","name":"Hiện có","type":"census_to"}
]</script>
@stop

@section('js')
<script>
var HIS_DEPTS = @json($hisDepartments);
var STATE = { configs: [], assignments: [] };

function deptOptions(selected) {
  var html = '<option value="">— Không gắn khoa HIS —</option>';
  HIS_DEPTS.forEach(function (d) {
    html += '<option value="' + d.id + '"' + (d.id == selected ? ' selected' : '') + '>' + d.department_name + '</option>';
  });
  return html;
}

function renderConfigs() {
  var $tb = $('#tbl-configs tbody').empty();
  STATE.configs.forEach(function (c) {
    $tb.append('<tr data-id="' + c.id + '">' +
      '<td><input class="form-control f-sort" type="number" value="' + c.sort_order + '" style="width:70px"></td>' +
      '<td><input class="form-control f-name" value="' + c.display_name + '"></td>' +
      '<td><select class="form-control f-dept">' + deptOptions(c.his_department_id) + '</select></td>' +
      '<td><textarea class="form-control f-metrics" rows="2">' + c.metrics + '</textarea></td>' +
      '<td><input type="checkbox" class="f-active"' + (c.is_active ? ' checked' : '') + '></td>' +
      '<td><button class="btn btn-sm btn-primary btn-save-cfg">Lưu</button></td></tr>');
  });
  var $sel = $('#assign-depts').empty();
  STATE.configs.forEach(function (c) {
    if (c.is_active) $sel.append('<option value="' + c.id + '">' + c.display_name + '</option>');
  });
}

function loadAll() {
  $.get('{{ route('khth.giao-ban-config-fetch') }}', function (res) {
    STATE = res; renderConfigs(); syncAssign();
  });
}

function syncAssign() {
  var uid = parseInt($('#assign-user').val(), 10);
  var mine = STATE.assignments.filter(function (a) { return a.user_id === uid; })
    .map(function (a) { return String(a.dept_config_id); });
  $('#assign-depts').val(mine);
}

$(function () {
  loadAll();
  $('#assign-user').on('change', syncAssign);

  $('#btn-add').on('click', function () {
    var name = prompt('Tên hiển thị khoa mới:');
    if (!name) return;
    $.post('{{ route('khth.giao-ban-config-store') }}', {
      _token: '{{ csrf_token() }}', display_name: name, sort_order: STATE.configs.length + 1,
      metrics: $('#default-metrics').text().trim()
    }).done(loadAll);
  });

  $('#tbl-configs').on('click', '.btn-save-cfg', function () {
    var $tr = $(this).closest('tr');
    $.post('{{ url('khth/giao-ban/cau-hinh') }}/' + $tr.data('id'), {
      _token: '{{ csrf_token() }}',
      sort_order: $tr.find('.f-sort').val(), display_name: $tr.find('.f-name').val(),
      his_department_id: $tr.find('.f-dept').val() || null,
      metrics: $tr.find('.f-metrics').val(),
      is_active: $tr.find('.f-active').is(':checked') ? 1 : 0
    }).done(loadAll).fail(function (xhr) {
      alert(xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Lỗi lưu');
    });
  });

  $('#btn-assign').on('click', function () {
    $.post('{{ route('khth.giao-ban-config-assign') }}', {
      _token: '{{ csrf_token() }}', user_id: $('#assign-user').val(),
      dept_config_ids: $('#assign-depts').val() || []
    }).done(function () { alert('Đã lưu'); loadAll(); });
  });
});
</script>
@stop
