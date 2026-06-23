@extends('adminlte::page')
@section('title', 'Doanh thu theo khoa/phòng')
@section('content_header')<h1>Doanh thu theo khoa/phòng thực hiện</h1>@stop
@section('content')
@include('khth.partials.search-revenue-dept-room')

<div class="row">
  <div class="col-md-4"><div class="info-box"><span class="info-box-icon bg-green"><i class="fa fa-money"></i></span><div class="info-box-content"><span class="info-box-text">Tổng doanh thu (Tr)</span><span class="info-box-number" id="kpi-tong">0</span></div></div></div>
  <div class="col-md-4"><div class="info-box"><span class="info-box-icon bg-aqua"><i class="fa fa-hospital-o"></i></span><div class="info-box-content"><span class="info-box-text">Số khoa</span><span class="info-box-number" id="kpi-khoa">0</span></div></div></div>
  <div class="col-md-4"><div class="info-box"><span class="info-box-icon bg-yellow"><i class="fa fa-th"></i></span><div class="info-box-content"><span class="info-box-text">Số phòng</span><span class="info-box-number" id="kpi-phong">0</span></div></div></div>
</div>

<div class="row">
  <div class="col-md-12"><div class="box"><div class="box-header"><h3 class="box-title">Doanh thu theo khoa (triệu)</h3></div><div class="box-body"><canvas id="chart-khoa" height="120"></canvas></div></div></div>
</div>
<div class="row">
  <div class="col-md-12"><div class="box"><div class="box-header"><h3 class="box-title">Bảng theo khoa</h3></div><div class="box-body table-responsive"><table class="table table-bordered" id="tbl-khoa" width="100%"></table></div></div></div>
</div>

<div class="box">
  <div class="box-header"><h3 class="box-title">Chi tiết theo phòng</h3><button id="export_xlsx" class="btn btn-success btn-sm pull-right"><i class="fa fa-file-excel-o"></i> Xuất Excel</button></div>
  <div class="box-body table-responsive">
    <table id="detail-table" class="table table-hover" width="100%">
      <thead><tr><th>Khoa</th><th>Loại phòng</th><th>Phòng</th><th>Doanh thu</th><th>Số lượng</th></tr></thead>
    </table>
  </div>
</div>
@stop

@push('after-scripts')
@stack('after-scripts-date-range')
@stack('after-scripts-load-data-button')
<script src="{{ asset('vendor/chart/js/Chart.min.js') }}"></script>
<script>
let chartKhoa=null, detailTable=null, curFrom=null, curTo=null;
const PALETTE=['#3c8dbc','#00a65a','#dd4b39','#f39c12','#605ca8','#39cccc','#d81b60','#00c0ef','#001f3f','#f012be'];

function getRange(){ var d=$('#date_range').data('daterangepicker'); return {from:d.startDate.format('YYYY-MM-DD HH:mm:ss'), to:d.endDate.format('YYYY-MM-DD HH:mm:ss')}; }
function baseFilters(){ return {date_from:curFrom, date_to:curTo, department_id:$('#department_id').val(), room_type_id:$('#room_type_id').val(), room_id:$('#room_id').val()}; }

// partial load_data_button tự gọi fetchData(startDate,endDate) khi tải trang & bấm nút
function fetchData(startDate, endDate){ curFrom=startDate; curTo=endDate; loadDropdowns(); reloadAll(); }

function loadDropdowns(){
  $.getJSON("{{ route('khth.revenue-dept-room-departments') }}", {date_from:curFrom, date_to:curTo}, function(data){
    var cur=$('#department_id').val();
    var h='<option value="">-- Tất cả --</option>';
    data.forEach(function(it){ h+='<option value="'+it.department_id+'">'+it.department_name+'</option>'; });
    $('#department_id').html(h).val(cur);
  });
  $.getJSON("{{ route('khth.revenue-dept-room-room-types') }}", {date_from:curFrom, date_to:curTo, department_id:$('#department_id').val()}, function(data){
    var cur=$('#room_type_id').val();
    var h='<option value="">-- Tất cả --</option>';
    data.forEach(function(it){ h+='<option value="'+it.room_type_id+'">'+it.room_type_name+'</option>'; });
    $('#room_type_id').html(h).val(cur);
  });
  $.getJSON("{{ route('khth.revenue-dept-room-rooms') }}", {date_from:curFrom, date_to:curTo, department_id:$('#department_id').val(), room_type_id:$('#room_type_id').val()}, function(data){
    var cur=$('#room_id').val();
    var h='<option value="">-- Tất cả --</option>';
    data.forEach(function(it){ h+='<option value="'+it.room_id+'">'+it.room_name+'</option>'; });
    $('#room_id').html(h).val(cur);
  });
}

function loadSummary(){
  $.getJSON("{{ route('khth.revenue-dept-room-summary') }}", baseFilters(), function(res){
    var k=res.kpi;
    $('#kpi-tong').text(numberFmt(Math.round(k.tong_doanh_thu/1e6))); $('#kpi-khoa').text(k.so_khoa); $('#kpi-phong').text(k.so_phong);
    renderKhoaChart(res.by_department);
    renderKhoaTable(res.by_department);
  });
}
function numberFmt(n){ return (n||0).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ','); }

function renderKhoaChart(rows){
  var ctx=document.getElementById('chart-khoa').getContext('2d');
  if(chartKhoa) chartKhoa.destroy();
  chartKhoa=new Chart(ctx,{type:'bar',data:{labels:rows.map(r=>r.department_name),datasets:[{label:'Doanh thu (Tr)',data:rows.map(r=>Math.round(r.thanh_tien/1e6)),backgroundColor:rows.map((r,i)=>PALETTE[i%PALETTE.length])}]},options:{legend:{display:false},scales:{xAxes:[{ticks:{autoSkip:false,maxRotation:60,minRotation:45}}],yAxes:[{ticks:{beginAtZero:true}}]}}});
}
var DT_VI={ search:'Tìm:', lengthMenu:'Hiện _MENU_ dòng', info:'Hiển thị _START_-_END_ / _TOTAL_', infoEmpty:'Không có dữ liệu', zeroRecords:'Không tìm thấy', emptyTable:'Không có dữ liệu', paginate:{first:'Đầu',last:'Cuối',next:'Sau',previous:'Trước'} };

function renderKhoaTable(rows){
  if ($.fn.DataTable.isDataTable('#tbl-khoa')) { $('#tbl-khoa').DataTable().destroy(); }
  var html='<thead><tr><th>Khoa</th><th>Doanh thu (Tr)</th><th>SL</th><th>%</th></tr></thead><tbody>';
  rows.forEach(function(r){
    html+='<tr class="drill" style="cursor:pointer" data-id="'+r.department_id+'"><td>'+r.department_name+'</td><td>'+numberFmt(Math.round(r.thanh_tien/1e6))+'</td><td>'+numberFmt(Math.round(r.so_luong))+'</td><td>'+r.pct+'%</td></tr>';
  });
  $('#tbl-khoa').html(html+'</tbody>');
  // Phân trang 10 dòng + ô tìm kiếm; giữ thứ tự tự nhiên (ordering:false)
  $('#tbl-khoa').DataTable({ pageLength:10, lengthChange:false, ordering:false, autoWidth:false, language:DT_VI });
}

function loadDetail(){
  if(detailTable){ detailTable.ajax.reload(); return; }
  detailTable=$('#detail-table').DataTable({
    processing:true, serverSide:true, destroy:true, scrollX:true,
    ajax:{ url:"{{ route('khth.revenue-dept-room-fetch') }}", data:function(d){ Object.assign(d, baseFilters()); } },
    columns:[ {data:'department_name'},{data:'room_type_name'},{data:'room_name'},{data:'thanh_tien'},{data:'so_luong'} ]
  });
}
function reloadAll(){ loadSummary(); loadDetail(); }

$(function(){
  $('.select2').select2({width:'100%'});
  // đổi khoa / loại phòng -> nạp lại phòng
  $(document).on('change', '#department_id, #room_type_id', function(){ loadDropdowns(); });
  // drill: click khoa ở bảng -> set filter khoa -> reload
  $(document).on('click', '#tbl-khoa .drill', function(){ $('#department_id').val($(this).data('id')).trigger('change'); reloadAll(); });
  // export
  $('#export_xlsx').click(function(){ window.location.href="{{ route('khth.revenue-dept-room-export') }}?"+$.param(baseFilters()); });
});
</script>
@endpush
