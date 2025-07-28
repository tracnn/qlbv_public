@extends('adminlte::page')

@section('title', 'Thống kê điều trị')

@section('content_header')
  <h1>
    KHTH
    <small>Thống kê điều trị</small>
  </h1>

@stop

@section('content')
<!-- Messages -->
@include('includes.message')
<!-- /Messages -->

<div class="panel panel-default">
    <div class="panel-body table-responsive">
        <table id="treatment-detail-table" class="table display table-hover responsive wrap datatable dtr-inline">
            <thead>
                <tr>
                    <th>Mã điều trị</th>
                    <th>Mã bệnh nhân</th>
                    <th>Tên bệnh nhân</th>
                    <th>Ngày vào</th>
                    <th>Ngày ra</th>
                    <th>Mã bệnh</th>
                    <th>Tên bệnh</th>
                    <th>Action</th>
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

  var table = $('#treatment-detail-table').DataTable({
    processing: true,
    serverSide: true,
    ajax: {
      url: "{{ route('dashboard.fetch-treatment-detail') }}",
      type: "GET",
      data: function(d) {
        d.data_type = query.data_type;
        d.from_date = query.from_date;
        d.to_date = query.to_date;
      }
    },
    columns: [
      {data: 'treatment_code', name: 'treatment_code'},
      {data: 'tdl_patient_code', name: 'tdl_patient_code'},
      {data: 'tdl_patient_name', name: 'tdl_patient_name'},
      {data: 'in_time', name: 'in_time'},
      {data: 'out_time', name: 'out_time'},
      {data: 'icd_code', name: 'icd_code'},
      {data: 'icd_name', name: 'icd_name'},
      {data: 'action', name: 'action'},
    ]
  });
});

</script>
@endpush