@extends('adminlte::page')

@section('title', 'Danh mục Đơn vị hành chính')

@section('content_header')
  <h1>
    Danh mục
    <small>Đơn vị hành chính</small>
  </h1>
@stop

@section('content')
@include('includes.message')

<div class="panel panel-default">
    <div class="panel-body table-responsive">
        <table id="administrative-unit-list" class="table display table-hover responsive nowrap datatable dtr-inline" width="100%">
            <thead>
                <tr>
                    <th>Mã tỉnh</th>
                    <th>Tên tỉnh</th>
                    <th>Mã huyện</th>
                    <th>Tên huyện</th>
                    <th>Mã xã</th>
                    <th>Tên xã</th>
                    <th>Xem</th>
                </tr>
            </thead>
        </table>
    </div>
</div>

@include('category.bhyt._chi_tiet')

@stop

@push('after-scripts')
<script type="text/javascript">
    var table = null;
    function fetchData() {
        table = $('#administrative-unit-list').DataTable({
            "processing": true,
            "serverSide": true,
            "destroy": true,
            "responsive": true,
            "scrollX": true,
            "ajax": { url: "{{ route('category-bhyt.fetch-administrative-unit') }}" },
            "columns": [
                { "data": "province_code" },
                { "data": "province_name" },
                { "data": "district_code" },
                { "data": "district_name" },
                { "data": "commune_code" },
                { "data": "commune_name" },
                { "data": "id", "orderable": false, "searchable": false, "render": function (d) {
                    return '<button type="button" class="btn btn-xs btn-default nut-chi-tiet" data-loai="administrative_unit" data-id="' + d + '">Xem</button>';
                } },
            ],
        });
    }
    $(document).ready(function () { fetchData(); });
</script>
@endpush
