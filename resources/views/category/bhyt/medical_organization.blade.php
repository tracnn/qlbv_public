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
            ],
        });
    }
    $(document).ready(function () { fetchData(); });
</script>
@endpush
