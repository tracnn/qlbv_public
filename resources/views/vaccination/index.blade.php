@extends('adminlte::page')

@section('title', __('insurance.backend.labels.list'))

@section('content_header')
<h1>
    Vaccination
    <small>Thông tin tiêm chủng</small>
</h1>
{{ Breadcrumbs::render('vaccination.index') }}
@stop

@section('content')

@include('vaccination.patials.search')

<div class="panel panel-default">
    <div class="panel-heading">
        Danh sách hồ sơ
    </div>
    <div class="panel-body table-responsive">
        <table id="vaccination-index" class="table display table-hover responsive nowrap datatable dtr-inline" width="100%">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Họ và tên</th>
                    <th>Ngày tiêm</th>
                    <th>Loại vắc xin</th>
                    <th>Người tiêm</th>
                    <th>Hành Động</th>
                </tr>
            </thead>
        </table>
    </div>
</div>

@stop

@push('after-scripts')
<script type="text/javascript">
    var currentAjaxRequest = null; // Biến để lưu trữ yêu cầu AJAX hiện tại

    function fetchData(startDate, endDate) {
        // Kiểm tra và hủy yêu cầu AJAX trước đó (nếu có)
        if (currentAjaxRequest != null) {
            currentAjaxRequest.abort();
        }

        var table = $('#vaccination-index').DataTable({
            "processing": true,
            "serverSide": true,
            "destroy": true, // Destroy any existing DataTable before reinitializing
            "ajax": {
                url: "{{ route('vaccination.fetch-vaccinations') }}",
                data: function(d) {
                    d.date_from = startDate;
                    d.date_to = endDate;
                },
                beforeSend: function(xhr) {
                    currentAjaxRequest = xhr;
                },
                complete: function(xhr, status) {
                    currentAjaxRequest = null;
                },
                error: function(xhr, error, code) {
                    console.log('Error:', error);
                    console.log('Code:', code);
                    console.log('XHR:', xhr);
                }
            },
            "columns": [
                { "data": "id", "name": "id", "orderable": false, "searchable": false },
                { "data": "patient_name", "name": "patient.name" },
                { "data": "date_of_vaccination", "name": "date_of_vaccination" },
                { "data": "vaccine_type", "name": "vaccine.name" },
                { "data": "administered_by", "name": "administered_by" },
                { "data": "actions", "name": "actions", "orderable": false, "searchable": false }
            ],
            "createdRow": function(row, data, dataIndex) {
                if (!data.administered_by) {
                    $(row).addClass('highlight-red');
                }
            }
        });

        table.ajax.reload();
    }
</script>
@endpush