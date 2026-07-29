@extends('adminlte::page')
@section('title', 'Kiểm tra sai sót y lệnh')
@section('content_header')<h1>Kiểm tra sai sót y lệnh</h1>@stop

@section('content')
<div class="box box-primary">
  <div class="box-body">
    <div class="row">
      <div class="col-md-2"><label>Từ ngày</label><input type="date" id="date_from" class="form-control" value="{{ date('Y-m-d') }}"></div>
      <div class="col-md-2"><label>Đến ngày</label><input type="date" id="date_to" class="form-control" value="{{ date('Y-m-d') }}"></div>
      <div class="col-md-2"><label>Mức độ</label>
        <select id="severity" class="form-control select2"><option value="">Tất cả</option>
          <option value="critical">Nghiêm trọng</option><option value="warning">Cảnh báo</option><option value="info">Thông tin</option>
        </select>
      </div>
      <div class="col-md-2"><label>Trạng thái</label>
        <select id="status" class="form-control select2"><option value="">Tất cả</option>
          <option value="new">Mới</option><option value="seen">Đã xem</option><option value="processed">Đã xử lý</option><option value="false_positive">Bỏ qua</option>
        </select>
      </div>
      <div class="col-md-2"><label>Loại luật</label>
        <select id="rule_code" class="form-control select2"><option value="">Tất cả</option>
          @foreach($rules as $r)<option value="{{ $r->code }}">{{ $r->name }}</option>@endforeach
        </select>
      </div>
      <div class="col-md-2"><label>Tìm BN/BS/ĐT/DV</label><input type="text" id="keyword" class="form-control" placeholder="mã/tên..."></div>
    </div>
    <div class="row" style="margin-top:10px">
      <div class="col-md-3"><label>Loại dịch vụ</label>
        <select id="service_req_type_id" class="form-control select2"><option value="">Tất cả</option></select>
      </div>
      <div class="col-md-3"><label>Khoa thực hiện</label>
        <select id="department_id" class="form-control select2"><option value="">Tất cả</option></select>
      </div>
      @include('partials.ma_cskcb')
    </div>
    <div class="row" style="margin-top:10px"><div class="col-md-12">
      <button id="btn-load" class="btn btn-primary"><i class="fa fa-search"></i> Tải dữ liệu</button>
      <a id="btn-export" class="btn btn-success"><i class="fa fa-file-excel-o"></i> Xuất Excel</a>
    </div></div>
  </div>
</div>

<div class="row">
  <div class="col-md-3"><div class="info-box"><span class="info-box-icon bg-aqua"><i class="fa fa-list"></i></span><div class="info-box-content"><span class="info-box-text">Tổng</span><span class="info-box-number" id="kpi-total">0</span></div></div></div>
  <div class="col-md-3"><div class="info-box"><span class="info-box-icon bg-red"><i class="fa fa-exclamation-triangle"></i></span><div class="info-box-content"><span class="info-box-text">Nghiêm trọng</span><span class="info-box-number" id="kpi-critical">0</span></div></div></div>
  <div class="col-md-3"><div class="info-box"><span class="info-box-icon bg-yellow"><i class="fa fa-bell"></i></span><div class="info-box-content"><span class="info-box-text">Cảnh báo</span><span class="info-box-number" id="kpi-warning">0</span></div></div></div>
  <div class="col-md-3"><div class="info-box"><span class="info-box-icon bg-gray"><i class="fa fa-inbox"></i></span><div class="info-box-content"><span class="info-box-text">Chưa xử lý</span><span class="info-box-number" id="kpi-new">0</span></div></div></div>
</div>

<div class="box box-info collapsed-box">
  <div class="box-header with-border">
    <h3 class="box-title"><i class="fa fa-database"></i> Thống kê quét — tổng đã quét: <b id="scan-total">0</b> | lượt chạy: <span id="scan-runs">0</span> | tổng TG: <b id="scan-total-time">0s</b> | TB: <b id="scan-avg-time">0s</b>/lượt</h3>
    <div class="box-tools pull-right"><button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-plus"></i></button></div>
  </div>
  <div class="box-body table-responsive">
    <table class="table table-bordered table-condensed" id="scan-stats-table">
      <thead><tr><th>Nguồn quét</th><th>source_key</th><th class="text-right">Đã quét</th><th class="text-right">Vi phạm</th><th class="text-right">Lượt chạy</th><th class="text-right">Lỗi</th><th class="text-right">Tổng TG</th><th class="text-right">TG TB</th><th>Chạy gần nhất</th></tr></thead>
      <tbody></tbody>
    </table>
  </div>
</div>

<div class="box">
  <div class="box-header"><h3 class="box-title">Danh sách vi phạm</h3></div>
  <div class="box-body table-responsive">
    <table id="oc-table" class="table table-hover table-bordered" width="100%">
      <thead><tr>
        <th>Thời điểm</th><th>Mức độ</th><th>Luật</th><th>Loại DV</th><th>Phiếu</th><th>Mã ĐT</th><th>Tên BN</th><th>Bác sĩ</th><th>Khoa TH</th><th>Nội dung</th><th>Trạng thái</th><th>Thao tác</th>
      </tr></thead>
    </table>
  </div>
</div>
@stop

@push('after-scripts')
<script>
var DT_VI = { search:'Tìm:', lengthMenu:'Hiện _MENU_ dòng', info:'Hiển thị _START_-_END_ / _TOTAL_', infoEmpty:'Không có dữ liệu', zeroRecords:'Không tìm thấy', emptyTable:'Không có dữ liệu', paginate:{ first:'Đầu', last:'Cuối', next:'Sau', previous:'Trước' } };
var ocTable = null;

function filters(){
  return { date_from:$('#date_from').val(), date_to:$('#date_to').val(), severity:$('#severity').val(), status:$('#status').val(), rule_code:$('#rule_code').val(), service_req_type_id:$('#service_req_type_id').val(), department_id:$('#department_id').val(), ma_cskcb:$('#ma_cskcb').val(), keyword:$('#keyword').val() };
}

function loadSummary(){
  $.getJSON("{{ route('khth.order-check-summary') }}", filters(), function(r){
    $('#kpi-total').text(r.total); $('#kpi-critical').text(r.critical); $('#kpi-warning').text(r.warning); $('#kpi-new').text(r.new);
  });
}

function fmtSecs(s){
  s = Number(s) || 0;
  if (s < 60) return s + 's';
  var m = Math.floor(s/60), r = Math.round(s%60);
  if (m < 60) return m + 'm ' + r + 's';
  var h = Math.floor(m/60); m = m%60;
  return h + 'h ' + m + 'm';
}
function loadScanStats(){
  $.getJSON("{{ route('khth.order-check-scan-stats') }}", filters(), function(r){
    $('#scan-total').text(r.total_scanned); $('#scan-runs').text(r.total_runs);
    $('#scan-total-time').text(fmtSecs(r.total_secs)); $('#scan-avg-time').text(fmtSecs(r.avg_secs));
    var html='';
    r.sources.forEach(function(s){
      var err = s.errors > 0 ? '<span class="label label-danger">'+s.errors+'</span>' : '0';
      html += '<tr><td>'+s.label+'</td><td><code>'+s.source_key+'</code></td><td class="text-right">'+s.scanned+'</td><td class="text-right">'+s.violations+'</td><td class="text-right">'+s.runs+'</td><td class="text-right">'+err+'</td><td class="text-right">'+fmtSecs(s.total_secs)+'</td><td class="text-right">'+fmtSecs(s.avg_secs)+'</td><td>'+s.last_run+'</td></tr>';
    });
    $('#scan-stats-table tbody').html(html || '<tr><td colspan="9" class="text-center">Không có dữ liệu</td></tr>');
  });
}

function reload(){
  loadSummary();
  loadScanStats();
  if(ocTable){ ocTable.ajax.reload(); return; }
  ocTable = $('#oc-table').DataTable({
    processing:true, serverSide:true, destroy:true, scrollX:true, order:[[0,'desc']],
    ajax:{ url:"{{ route('khth.order-check-fetch') }}", data:function(d){ Object.assign(d, filters()); } },
    language:DT_VI,
    columns:[
      {data:'detected_at'},{data:'severity_badge'},{data:'rule_code'},{data:'service_req_type_name'},
      {data:'service_req_code'},{data:'treatment_code'},{data:'patient_name'},{data:'doctor'},
      {data:'department_label'},{data:'message'},
      {data:'status_badge'},{data:'actions',orderable:false,searchable:false}
    ]
  });
}

$(function(){
  $('.select2').select2({width:'100%'});
  $('#btn-load').on('click', reload);

  // Nạp danh mục Khoa/Phòng/TT cho filter "Khoa thực hiện"
  $.getJSON('{{ route("category-his.fetch-department-catalog") }}', function(data){
    var sel = $('#department_id');
    $.each(data, function(i, c){ sel.append('<option value="'+c.id+'">'+c.department_name+'</option>'); });
  });

  // Nạp danh mục Loại phiếu chỉ định cho filter "Loại dịch vụ"
  $.getJSON('{{ route("category-his.fetch-service-req-type") }}', function(data){
    var sel = $('#service_req_type_id');
    $.each(data, function(i, c){ sel.append('<option value="'+c.id+'">'+c.service_req_type_name+'</option>'); });
  });

  $('#btn-export').on('click', function(){
    var q = $.param(filters());
    window.location = "{{ route('khth.order-check-export') }}?" + q;
  });

  // Thao tác workflow
  $(document).on('click', '.oc-act', function(){
    var id=$(this).data('id'), status=$(this).data('status');
    var note = prompt('Ghi chú (tùy chọn):', '');
    if(note === null) return; // bấm Cancel
    $.ajax({
      url:"{{ route('khth.order-check-update-status') }}", method:'POST',
      data:{ _token:"{{ csrf_token() }}", id:id, status:status, note:note },
      success:function(){ reload(); },
      error:function(xhr){ alert((xhr.responseJSON && xhr.responseJSON.message) || 'Lỗi cập nhật'); }
    });
  });

  reload(); // tải lần đầu
});
</script>
@endpush
