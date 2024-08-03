@extends('adminlte::page')

@section('title', __('insurance.backend.labels.list'))

@section('content_header')
<h1>
    Report
    <small>Danh sách BN PT</small>
</h1>
@stop

@section('content')

@include('administrator.partials.search-patient-pt')

<div class="panel panel-default">
    <div class="panel-body table-responsive">
        <table id="list-index" class="table display table-hover responsive nowrap datatable dtr-inline" width="100%">
            <thead>
                <tr>
                    <th>Mã Điều Trị</th>
                    <th>Họ Và Tên</th>
                    <th>Địa chỉ</th>
                    <th>Ngày Sinh</th>
                    <th>Số điện thoại</th>
                    <th>Ngày vào</th>
                    <th>Ngày ra</th>
                    <th>Ngày chỉ định</th>
                    <th>Tên dịch vụ</th>
                    <th>BS chỉ định</th>
                    <th>Người nhà</th>
                    <th>Số người nhà</th>
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

        var table = $('#list-index').DataTable({
            "processing": true,
            "serverSide": true,
            "destroy": true, // Destroy any existing DataTable before reinitializing
            "ajax": {
                url: "{{ route('reports-administrator.fetch-patient-pt') }}",
                data: function(d) {
                    d.date_from = startDate;
                    d.date_to = endDate;
                    d.date_type = $('#date_type').val();
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
                { data: 'tdl_treatment_code' },
                { data: 'tdl_patient_name' },
                { data: 'tdl_patient_district_name' },
                { data: 'tdl_patient_dob' },
                { data: 'tdl_patient_phone' },
                { data: 'in_time' },
                { data: 'out_time' },
                { data: 'intruction_time' },
                { data: 'tdl_service_name' },
                { data: 'request_username' },
                { data: 'relative_name' },
                { data: 'relative_mobile' },
            ],
        });

        table.ajax.reload();
    }
</script>
@endpush