@extends('adminlte::page')

@section('title', 'Danh mục Cơ sở KCB')

@section('content_header')
  <h1>
    Danh mục
    <small>Cơ sở khám chữa bệnh</small>
  </h1>
@stop

@section('content')
@include('includes.message')

<div class="panel panel-default">
    <div class="panel-body table-responsive">
        <table id="medical-organization-list" class="table display table-hover responsive nowrap datatable dtr-inline" width="100%">
            <thead>
                <tr>
                    <th>Mã CSKCB</th>
                    <th>Tên CSKCB</th>
                    <th>Địa chỉ</th>
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
        table = $('#medical-organization-list').DataTable({
            "processing": true,
            "serverSide": true,
            "destroy": true,
            "responsive": true,
            "scrollX": true,
            "ajax": { url: "{{ route('category-bhyt.fetch-medical-organization') }}" },
            "columns": [
                { "data": "ma_cskcb" },
                { "data": "ten_cskcb" },
                { "data": "dia_chi_cskcb" },
                { "data": "id", "orderable": false, "searchable": false, "render": function (d) {
                    return '<button type="button" class="btn btn-xs btn-default nut-chi-tiet" data-loai="medical_organization" data-id="' + d + '">Xem</button>';
                } },
            ],
        });
    }
    $(document).ready(function () { fetchData(); });
</script>
@endpush
