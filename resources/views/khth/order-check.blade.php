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
        <select id="service_req_type_id" class="form-control select2"><option value="">Tất cả</option>
          @foreach($serviceReqTypes as $t)<option value="{{ $t->service_req_type_id }}">{{ $t->service_req_type_name }}</option>@endforeach
        </select>
      </div>
      <div class="col-md-3"><label>Khoa thực hiện</label><input type="text" id="department_keyword" class="form-control" placeholder="mã/tên khoa..."></div>
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

<div class="box">
  <div class="box-header"><h3 class="box-title">Danh sách vi phạm</h3></div>
  <div class="box-body table-responsive">
    <table id="oc-table" class="table table-hover table-bordered" width="100%">
      <thead><tr>
        <th>Thời điểm</th><th>Mức độ</th><th>Luật</th><th>Loại DV</th><th>Phiếu</th><th>Mã ĐT</th><th>Tên BN</th><th>Bác sĩ</th><th>Khoa TH</th><th>Mã DV</th><th>Nội dung</th><th>Trạng thái</th><th>Thao tác</th>
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
  return { date_from:$('#date_from').val(), date_to:$('#date_to').val(), severity:$('#severity').val(), status:$('#status').val(), rule_code:$('#rule_code').val(), service_req_type_id:$('#service_req_type_id').val(), department_keyword:$('#department_keyword').val(), keyword:$('#keyword').val() };
}

function loadSummary(){
  $.getJSON("{{ route('khth.order-check-summary') }}", filters(), function(r){
    $('#kpi-total').text(r.total); $('#kpi-critical').text(r.critical); $('#kpi-warning').text(r.warning); $('#kpi-new').text(r.new);
  });
}

function reload(){
  loadSummary();
  if(ocTable){ ocTable.ajax.reload(); return; }
  ocTable = $('#oc-table').DataTable({
    processing:true, serverSide:true, destroy:true, scrollX:true, order:[[0,'desc']],
    ajax:{ url:"{{ route('khth.order-check-fetch') }}", data:function(d){ Object.assign(d, filters()); } },
    language:DT_VI,
    columns:[
      {data:'detected_at'},{data:'severity_badge'},{data:'rule_code'},{data:'service_req_type_name'},
      {data:'service_req_code'},{data:'treatment_code'},{data:'patient_name'},{data:'doctor'},
      {data:'department_label'},{data:'service_code'},{data:'message'},
      {data:'status_badge'},{data:'actions',orderable:false,searchable:false}
    ]
  });
}

$(function(){
  $('.select2').select2({width:'100%'});
  $('#btn-load').on('click', reload);

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
