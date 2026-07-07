@extends('adminlte::page')

@section('title', 'Danh mục ICD-YHCT')

@section('content_header')
  <h1>
    Danh mục
    <small>ICD Y học cổ truyền</small>
  </h1>
@stop

@section('content')
@include('includes.message')

<div class="panel panel-default">
    <div class="panel-body table-responsive">
        <table id="icd-yhct-list" class="table display table-hover responsive nowrap datatable dtr-inline" width="100%">
            <thead>
                <tr>
                    <th>Mã ICD</th>
                    <th>Tên ICD</th>
                    <th>Tên bệnh YHCT</th>
                    <th>Mã ICD10</th>
                    <th>Tên ICD10</th>
                </tr>
            </thead>
        </table>
    </div>
</div>
@stop

@push('after-scripts')
<script type="text/javascript">
    var table = null;
    function fetchData() {
        table = $('#icd-yhct-list').DataTable({
            "processing": true,
            "serverSide": true,
            "destroy": true,
            "responsive": true,
            "scrollX": true,
            "ajax": { url: "{{ route('category-bhyt.fetch-icd-yhct-catalog') }}" },
            "columns": [
                { "data": "icd_code" },
                { "data": "icd_name" },
                { "data": "icd_yhct_name" },
                { "data": "icd10_code" },
                { "data": "icd10_name" },
            ],
        });
    }
    $(document).ready(function () { fetchData(); });
</script>
@endpush
