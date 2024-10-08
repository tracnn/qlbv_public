@extends('adminlte::page')

@section('title', __('insurance.backend.labels.list'))

@section('content_header')
<h1>
    DRUGS
    <small>Báo cáo sử dụng thuốc</small>
</h1>
@stop

@section('content')

@include('drug.partials.search')

<div class="panel panel-default">
    <div class="panel-body table-responsive">
        <table id="drug-index" class="table display table-hover responsive nowrap datatable dtr-inline" width="100%">
            <thead>
                <tr>
                    <th>Patient Name</th>
                    <th>Gender</th>
                    <th>DOB</th>
                    <th>Treatment Code</th>
                    <th>Patient Code</th>
                    <th>Service Req Code</th>
                    <th>Username</th>
                    <th>Card Number</th>
                    <th>Patient Type</th>
                    <th>ICD Code</th>
                    <th>ICD Name</th>
                    <th>ICD Sub Code</th>
                    <th>ICD Text</th>
                    <th>In Time</th>
                    <th>Out Time</th>
                    <th>Treatment Type</th>
                    <th>Service Type</th>
                    <th>Amount</th>
                    <th>Original Price</th>
                    <th>Department Name</th>
                    <th>Instruction Time</th>
                    <th>Service BHYT Name</th>
                    <th>Medicine Type Code</th>
                    <th>Medicine Type Name</th>
                    <th>Concentration</th>
                    <th>Active Ingr BHYT Code</th>
                    <th>Active Ingr BHYT Name</th>
                    <th>Use Form Name</th>
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

        var table = $('#drug-index').DataTable({
            "processing": true,
            "serverSide": true,
            "destroy": true, // Destroy any existing DataTable before reinitializing
            "responsive": true, // Giữ responsive
            "scrollX": true, // Đảm bảo cuộn ngang khi bảng quá rộng
            "ajax": {
                url: "{{ route('reports-duoc.fetch-drug-use') }}",
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
                { data: 'tdl_patient_name' },
                { data: 'tdl_patient_gender_name' },
                { data: 'tdl_patient_dob' },
                { data: 'tdl_treatment_code' },
                { data: 'tdl_patient_code' },
                { data: 'service_req_code' },
                { data: 'request_username' },
                { data: 'tdl_hein_card_number' },
                { data: 'patient_type_name' },
                { data: 'icd_code' },
                { data: 'icd_name' },
                { data: 'icd_sub_code' },
                { data: 'icd_text' },
                { data: 'in_time' },
                { data: 'out_time' },
                { data: 'treatment_type_name' },
                { data: 'service_type_name' },
                { data: 'amount' },
                { data: 'original_price' },
                { data: 'department_name' },
                { data: 'tdl_intruction_time' },
                { data: 'tdl_hein_service_bhyt_name' },
                { data: 'medicine_type_code' },
                { data: 'medicine_type_name' },
                { data: 'concentra' },
                { data: 'active_ingr_bhyt_code' },
                { data: 'active_ingr_bhyt_name' },
                { data: 'medicine_use_form_name' }
            ],
        });

        table.ajax.reload();
    }

    $(document).ready(function() {
        $('#export_xlsx').click(function() {
            var dateRange = $('#date_range').data('daterangepicker');

            var startDate = dateRange.startDate.format('YYYY-MM-DD HH:mm:ss');
            var endDate = dateRange.endDate.format('YYYY-MM-DD HH:mm:ss');
            
            // Tạo URL với các tham số query
            var href = '{{ route("reports-duoc.export-drugs-use") }}?' + $.param({
                'date_from': startDate,
                'date_to': endDate
            });
            
            // Chuyển hướng tới URL với các tham số
            window.location.href = href;
        });
    });
</script>
@endpush