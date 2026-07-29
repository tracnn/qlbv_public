@extends('adminlte::page')

@section('title', 'Danh mục Nghề nghiệp')

@section('content_header')
  <h1>
    Danh mục
    <small>Nghề nghiệp</small>
  </h1>
@stop

@section('content')
@include('includes.message')

<div class="panel panel-default">
    <div class="panel-body table-responsive">
        <table id="job-category-list" class="table display table-hover responsive nowrap datatable dtr-inline" width="100%">
            <thead>
                <tr>
                    <th>Mã nghề nghiệp</th>
                    <th>Tên nghề nghiệp</th>
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
        table = $('#job-category-list').DataTable({
            "processing": true,
            "serverSide": true,
            "destroy": true,
            "responsive": true,
            "scrollX": true,
            "ajax": { url: "{{ route('category-bhyt.fetch-job-category') }}" },
            "columns": [
                { "data": "job_code" },
                { "data": "job_name" },
                { "data": "id", "orderable": false, "searchable": false, "render": function (d) {
                    return '<button type="button" class="btn btn-xs btn-default nut-chi-tiet" data-loai="job_categories" data-id="' + d + '">Xem</button>';
                } },
            ],
        });
    }
    $(document).ready(function () { fetchData(); });
</script>
@endpush
