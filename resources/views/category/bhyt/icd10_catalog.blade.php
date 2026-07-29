@extends('adminlte::page')

@section('title', 'Danh mục ICD-10')

@section('content_header')
  <h1>
    Danh mục
    <small>ICD-10</small>
  </h1>
@stop

@section('content')
@include('includes.message')

<div class="panel panel-default">
    <div class="panel-body table-responsive">
        <table id="icd10-list" class="table display table-hover responsive nowrap datatable dtr-inline" width="100%">
            <thead>
                <tr>
                    <th>Mã ICD</th>
                    <th>Tên ICD</th>
                    <th>Mãn tính</th>
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
        table = $('#icd10-list').DataTable({
            "processing": true,
            "serverSide": true,
            "destroy": true,
            "responsive": true,
            "scrollX": true,
            "ajax": { url: "{{ route('category-bhyt.fetch-icd10-catalog') }}" },
            "columns": [
                { "data": "icd_code" },
                { "data": "icd_name" },
                { "data": "is_chronic", "render": function (d) { return d ? 'Có' : ''; } },
                { "data": "id", "orderable": false, "searchable": false, "render": function (d) {
                    return '<button type="button" class="btn btn-xs btn-default nut-chi-tiet" data-loai="icd10" data-id="' + d + '">Xem</button>';
                } },
            ],
        });
    }
    $(document).ready(function () { fetchData(); });
</script>
@endpush
