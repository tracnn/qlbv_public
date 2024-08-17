@extends('adminlte::page')

@section('title', __('insurance.backend.labels.list'))

@section('content_header')
<h1>
    Điều dưỡng
    <small>Thực hiện y lệnh</small>
</h1>
@stop

@section('content')
@include('nurse.partials.search')
<div class="panel panel-default">
    <div class="panel-body table-responsive">
        <table id="nurse-index" class="table display table-hover responsive nowrap datatable dtr-inline" width="100%">
            <thead>
                <th>STT</th>
                <th>Mã điều trị</th>
                <th>Mã bệnh nhân</th>
                <th>Họ và tên</th>
                <th>Ngày sinh</th>
                <th>Giới tính</th>
                <th>Số thẻ BHYT</th>
                <th>Ngày vào</th>
                <th>Ngày nhập viện</th>
                <th>Giường</th>
                <th>Số điện thoại</th>
            </thead>
        </table>
    </div>
</div>
@stop

@push('after-scripts')
<script type="text/javascript">
    var currentAjaxRequest = null; // Biến để lưu trữ yêu cầu AJAX hiện tại
    var table = null;

    function fetchData(startDate, endDate) {
        // Kiểm tra và hủy yêu cầu AJAX trước đó (nếu có)
        if (currentAjaxRequest != null) {
            currentAjaxRequest.abort();
        }

        table = $('#nurse-list').DataTable({
            "processing": true,
            "serverSide": true,
            "destroy": true, // Destroy any existing DataTable before reinitializing
            "ajax": {
                url: "{{ route('nurse.execute.medication.fetch.data') }}",
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
                    //populateErrorCodeDropdown(xhr.responseJSON.errorCodes);
                },
                error: function(xhr, error, code) {
                    console.log('Error:', error);
                    console.log('Code:', code);
                    console.log('XHR:', xhr);
                }
            },
            "columns": [
                { "data": "treatment_code" },
                { "data": "tdl_patient_code" },
                { "data": "tdl_patient_name" },
                { "data": "tdl_patient_dob" },
                { "data": "tdl_patient_gender_name" },
                { "data": "tdl_hein_card_number" },
                { "data": "in_time" },
                { "data": "add_time" },
                { "data": "bed_name" },
                { "data": "tdl_patient_phone" },
            ],
        });

        table.ajax.reload();
    }
</script>
@endpush