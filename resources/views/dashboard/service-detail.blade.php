@extends('adminlte::page')

@section('title', 'Thống kê BN phẫu thuật')

@section('content_header')
  <h1>
    KHTH
    <small>Thống kê BN phẫu thuật</small>
  </h1>

@stop

@section('content')
<!-- Messages -->
@include('includes.message')
<!-- /Messages -->

<div class="panel panel-default">
    <div class="panel-body">
        <table id="service-detail-table" class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>Mã điều trị</th>
                    <th>Mã bệnh nhân</th>
                    <th>Tên bệnh nhân</th>
                    <th>Ngày vào</th>
                    <th>Ngày ra</th>
                    <th>Tên dịch vụ</th>
                    <th>Ngày y lệnh</th>
                    <th>Tên bác sĩ</th>
                </tr>
            </thead>
            <tbody>
            </tbody>
        </table>
    </div>
</div>

@stop
@push('after-scripts')
<script>
function getQueryParams() {
  var params = {};
  var search = window.location.search.substring(1); // bỏ dấu ?
  if (!search) return params;

  search.split("&").forEach(function(part) {
    var item = part.split("=");
    if (item.length === 2) {
      var key = decodeURIComponent(item[0]);
      var value = decodeURIComponent(item[1].replace(/\+/g, ' '));
      params[key] = value;
    }
  });
  return params;
}

$(document).ready(function() {
  const query = getQueryParams();

  var table = $('#service-detail-table').DataTable({
    processing: true,
    serverSide: true,
    ajax: {
      url: "{{ route('dashboard.fetch-service-detail') }}",
      type: "GET",
      data: function(d) {
        d.data_type = query.data_type;
        d.from_date = query.from_date;
        d.to_date = query.to_date;
      }
    },
    columns: [
      {data: 'tdl_treatment_code', name: 'tdl_treatment_code'},
      {data: 'tdl_patient_code', name: 'tdl_patient_code'},
      {data: 'tdl_patient_name', name: 'tdl_patient_name'},
      {data: 'in_time', name: 'in_time'},
      {data: 'out_time', name: 'out_time'},
      {data: 'tdl_service_name', name: 'tdl_service_name'},
      {data: 'intruction_time', name: 'intruction_time'},
      {data: 'request_username', name: 'request_username'},
    ]
  });
});

</script>
@endpush