@extends('adminlte::page')
@section('title', 'Tỷ lệ trả KQ đúng hẹn')
@section('content_header')<h1>Tỷ lệ trả kết quả đúng hẹn</h1>@stop
@section('content')
@include('khth.partials.search-on-time-result')

{{-- Card KPI --}}
<div class="row" id="kpi-cards">
  <div class="col-md-2"><div class="info-box"><span class="info-box-icon bg-aqua"><i class="fa fa-list"></i></span><div class="info-box-content"><span class="info-box-text">Tổng có hẹn</span><span class="info-box-number" id="kpi-tong">0</span></div></div></div>
  <div class="col-md-2"><div class="info-box"><span class="info-box-icon bg-green"><i class="fa fa-check"></i></span><div class="info-box-content"><span class="info-box-text">% Đúng hẹn</span><span class="info-box-number" id="kpi-pct-dung">0%</span></div></div></div>
  <div class="col-md-2"><div class="info-box"><span class="info-box-icon bg-red"><i class="fa fa-times"></i></span><div class="info-box-content"><span class="info-box-text">% Trễ hẹn</span><span class="info-box-number" id="kpi-pct-tre">0%</span></div></div></div>
  <div class="col-md-2"><div class="info-box"><span class="info-box-icon bg-yellow"><i class="fa fa-clock-o"></i></span><div class="info-box-content"><span class="info-box-text">Chưa trả KQ</span><span class="info-box-number" id="kpi-chua">0</span></div></div></div>
  <div class="col-md-2"><div class="info-box"><span class="info-box-icon bg-gray"><i class="fa fa-exclamation"></i></span><div class="info-box-content"><span class="info-box-text">Bất thường</span><span class="info-box-number" id="kpi-bat">0</span></div></div></div>
  <div class="col-md-2"><div class="info-box"><span class="info-box-icon bg-info"><i class="fa fa-ban"></i></span><div class="info-box-content"><span class="info-box-text">Không có hẹn</span><span class="info-box-number" id="kpi-khong-hen">0</span></div></div></div>
  <div class="col-md-2"><div class="info-box"><span class="info-box-icon bg-purple"><i class="fa fa-hourglass-half"></i></span><div class="info-box-content"><span class="info-box-text">TG trả KQ TB</span><span class="info-box-number" id="kpi-tgtb">0</span></div></div></div>
</div>

{{-- Tong hop --}}
<div class="row">
  <div class="col-md-6"><div class="box"><div class="box-header"><h3 class="box-title">Theo loại dịch vụ</h3></div><div class="box-body"><canvas id="chart-loai-dv" height="120"></canvas><table class="table table-bordered" id="tbl-loai-dv"></table></div></div></div>
  <div class="col-md-6"><div class="box"><div class="box-header"><h3 class="box-title">Xu hướng % đúng hẹn theo ngày</h3></div><div class="box-body"><canvas id="chart-trend" height="120"></canvas></div></div></div>
</div>
<div class="row">
  <div class="col-md-6"><div class="box"><div class="box-header"><h3 class="box-title">Theo khoa/phòng thực hiện (xếp % trễ)</h3></div><div class="box-body table-responsive"><table class="table table-bordered" id="tbl-phong"></table></div></div></div>
  <div class="col-md-6"><div class="box"><div class="box-header"><h3 class="box-title">Top dịch vụ trễ hẹn</h3></div><div class="box-body table-responsive"><table class="table table-bordered" id="tbl-dich-vu"></table></div></div></div>
</div>

{{-- Chi tiet --}}
<div class="box">
  <div class="box-header"><h3 class="box-title">Chi tiết</h3><button id="export_xlsx" class="btn btn-success btn-sm pull-right"><i class="fa fa-file-excel-o"></i> Xuất Excel</button></div>
  <div class="box-body table-responsive">
    <table id="detail-table" class="table table-hover" width="100%">
      <thead><tr>
        <th>Mã ĐT</th><th>Họ tên BN</th><th>Khoa/Phòng TH</th><th>Loại DV</th><th>Tên DV</th>
        <th>Giờ chỉ định</th><th>Giờ trả KQ</th><th>TG thực tế</th><th>TG hẹn</th><th>Chênh lệch</th><th>Trạng thái</th>
      </tr></thead>
    </table>
  </div>
</div>
@stop

@push('after-scripts')
@stack('after-scripts-date-range')
@stack('after-scripts-load-data-button')
<script src="{{ asset('vendor/chart/js/Chart.min.js') }}"></script>
<script>
let chartLoai=null, chartTrend=null, detailTable=null;

// Khoảng ngày hiện hành lưu lại để summary/detail/export dùng chung
let curFrom=null, curTo=null;
function getRange(){ var d=$('#date_range').data('daterangepicker'); return {from:d.startDate.format('YYYY-MM-DD HH:mm:ss'), to:d.endDate.format('YYYY-MM-DD HH:mm:ss')}; }
function baseFilters(){ return {date_from:curFrom, date_to:curTo, execute_room_id:$('#execute_room_id').val(), service_type_id:$('#service_type_id').val(), service_id:$('#drill_service_id').val(), status:$('#drill_status').val()}; }

// CONVENTION BẮT BUỘC: partial load_data_button tự gọi fetchData(startDate,endDate)
// khi trang tải xong và mỗi lần bấm nút #load_data_button.
function fetchData(startDate, endDate){
  curFrom=startDate; curTo=endDate;
  $('#drill_service_id').val(''); $('#drill_status').val(''); // reset drill khi tải lại từ nút
  reloadAll();
}

function loadSummary(){
  $.getJSON("{{ route('khth.on-time-result-summary') }}", baseFilters(), function(res){
    var k=res.kpi;
    $('#kpi-tong').text(k.tong_co_hen); $('#kpi-pct-dung').text(k.pct_dung_hen+'%'); $('#kpi-pct-tre').text(k.pct_tre_hen+'%');
    $('#kpi-chua').text(k.chua_tra); $('#kpi-bat').text(k.bat_thuong); $('#kpi-khong-hen').text(k.khong_hen); $('#kpi-tgtb').text(k.tg_tra_tb+' phút');
    renderBreakdownTable('#tbl-loai-dv', res.breakdown_loai_dich_vu, 'service_type_id', false);
    renderBreakdownTable('#tbl-phong', res.breakdown_phong, 'execute_room_id', true);
    renderBreakdownTable('#tbl-dich-vu', res.breakdown_dich_vu, 'service_id', true);
    renderLoaiChart(res.breakdown_loai_dich_vu);
    renderTrendChart(res.trend_theo_ngay);
  });
}

var DT_VI = {
  search:'Tìm:', lengthMenu:'Hiện _MENU_ dòng', info:'Hiển thị _START_-_END_ / _TOTAL_',
  infoEmpty:'Không có dữ liệu', zeroRecords:'Không tìm thấy', emptyTable:'Không có dữ liệu',
  paginate:{ first:'Đầu', last:'Cuối', next:'Sau', previous:'Trước' }
};

function renderBreakdownTable(sel, rows, drillField, paginate){
  // Hủy DataTable cũ (nếu có) trước khi thay nội dung
  if (paginate && $.fn.DataTable.isDataTable(sel)) { $(sel).DataTable().destroy(); }
  var html='<thead><tr><th>Nhóm</th><th>Tổng</th><th>Đúng</th><th>Trễ</th><th>% Trễ</th></tr></thead><tbody>';
  rows.forEach(function(g){
    html+='<tr class="drill" style="cursor:pointer" data-field="'+drillField+'" data-id="'+g.id+'"><td>'+(g.name||'(trống)')+'</td><td>'+g.tong+'</td><td>'+g.dung_hen+'</td><td>'+g.tre_hen+'</td><td>'+g.pct_tre_hen+'%</td></tr>';
  });
  $(sel).html(html+'</tbody>');
  if (paginate) {
    // Phân trang 10 dòng + ô tìm kiếm; giữ thứ tự xếp % trễ từ backend (ordering:false)
    $(sel).DataTable({ pageLength:10, lengthChange:false, ordering:false, autoWidth:false, language:DT_VI });
  }
}

function renderLoaiChart(rows){
  var ctx=document.getElementById('chart-loai-dv').getContext('2d');
  if(chartLoai) chartLoai.destroy();
  chartLoai=new Chart(ctx,{type:'bar',data:{labels:rows.map(r=>r.name),datasets:[{label:'% Đúng hẹn',backgroundColor:'#00a65a',data:rows.map(r=>r.pct_dung_hen)},{label:'% Trễ hẹn',backgroundColor:'#dd4b39',data:rows.map(r=>r.pct_tre_hen)}]},options:{scales:{yAxes:[{ticks:{beginAtZero:true,max:100}}]}}});
}
function renderTrendChart(rows){
  rows.sort((a,b)=>a.id-b.id);
  var ctx=document.getElementById('chart-trend').getContext('2d');
  if(chartTrend) chartTrend.destroy();
  chartTrend=new Chart(ctx,{type:'line',data:{labels:rows.map(r=>String(r.id).substr(6,2)+'/'+String(r.id).substr(4,2)),datasets:[{label:'% Đúng hẹn',borderColor:'#00a65a',fill:false,data:rows.map(r=>r.pct_dung_hen)}]},options:{scales:{yAxes:[{ticks:{beginAtZero:true,max:100}}]}}});
}

function loadDetail(){
  var f=baseFilters();
  if(detailTable){ detailTable.ajax.reload(); return; }
  detailTable=$('#detail-table').DataTable({
    processing:true, serverSide:true, destroy:true, scrollX:true,
    ajax:{ url:"{{ route('khth.on-time-result-fetch') }}", data:function(d){ Object.assign(d, baseFilters()); } },
    columns:[
      {data:'tdl_treatment_code'},{data:'tdl_patient_name'},{data:'execute_room_name'},{data:'service_type_name'},{data:'service_name'},
      {data:'intruction_time'},{data:'finish_time'},{data:'actual_minutes_fmt'},{data:'estimate_duration'},{data:'chenh_lech'},{data:'trang_thai'}
    ]
  });
}

function reloadAll(){ loadSummary(); loadDetail(); }

$(function(){
  $('.select2').select2({width:'100%'});
  // nap dropdown phong (theo khoang ngay mac dinh cua daterangepicker)
  var r0=getRange();
  $.getJSON("{{ route('khth.on-time-result-rooms') }}", {date_from:r0.from, date_to:r0.to}, function(data){
    data.forEach(function(it){ $('#execute_room_id').append('<option value="'+it.room_id+'">'+it.execute_room_name+'</option>'); });
  });

  // Nút "Tải dữ liệu" KHÔNG bind ở đây — partial load_data_button tự gọi fetchData().

  // drill-down tu bang tong hop
  $(document).on('click', '.drill', function(){
    var field=$(this).data('field'), id=$(this).data('id');
    if(field==='service_type_id') $('#service_type_id').val(id).trigger('change');
    if(field==='execute_room_id') $('#execute_room_id').val(id).trigger('change');
    if(field==='service_id') $('#drill_service_id').val(id);
    reloadAll();
  });

  // export theo filter hien hanh
  $('#export_xlsx').click(function(){
    window.location.href="{{ route('khth.on-time-result-export') }}?"+$.param(baseFilters());
  });
});
</script>
@endpush
