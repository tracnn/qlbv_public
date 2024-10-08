@extends('adminlte::page')

@section('title', __('insurance.backend.labels.list'))

@section('content_header')
<h1>
    DRUGS
    <small>Summary of the number of drugs per prescription</small>
</h1>
@stop

@section('content')

@include('administrator.partials.search-ndp')

<div class="panel panel-default">
    <div class="panel-body table-responsive">
        <table id="cvcr-index" class="table display table-hover responsive nowrap datatable dtr-inline" width="100%">
            <thead>
                <tr>
                    <th>Mã Điều Trị</th>
                    <th>Mã BN</th>
                    <th>Họ Và Tên</th>
                    <th>ICD Code</th>
                    <th>ICD Sub Code</th>
                    <th>Diện Điều Trị</th>
                    <th>Phòng Kê Đơn</th>
                    <th>BS Kê Đơn</th>
                    <th>Mã Y Lệnh</th>
                    <th>Ngày Y Lệnh</th>
                    <th>Loại Đơn</th>
                    <th>Loại Thuốc Kê</th>
                    <th>SL Loại Thuốc</th>
                    <th>SL Mua Ngoài</th>
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

        var table = $('#cvcr-index').DataTable({
            "processing": true,
            "serverSide": true,
            "destroy": true, // Destroy any existing DataTable before reinitializing
            "responsive": true, // Giữ responsive
            "scrollX": true, // Đảm bảo cuộn ngang khi bảng quá rộng
            "ajax": {
                url: "{{ route('reports-administrator.fetch-ndp') }}",
                data: function(d) {
                    d.date_from = startDate;
                    d.date_to = endDate;
                    d.drug_req_type = $('#drug_req_type').val();
                    d.prescription_type = $('#prescription_type').val();
                    d.treatment_code = $('#treatment_code').val();
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
                { data: 'tdl_patient_code' },
                { data: 'tdl_patient_name' },
                { data: 'icd_code' },
                { data: 'icd_sub_code' },
                { data: 'treatment_type_name' },
                { data: 'request_room_name' },
                { data: 'request_username' },
                { data: 'service_req_code' },
                { data: 'intruction_time' },
                { data: 'service_req_type_name' },
                { data: 'prescription_type_id' },
                { data: 'drug_count' },
                { data: 'mety_count' },
            ],
        });

        table.ajax.reload();
    }

    $(document).ready(function() {
        $('#export_xlsx').click(function() {
            var dateRange = $('#date_range').data('daterangepicker');

            var startDate = dateRange.startDate.format('YYYY-MM-DD HH:mm:ss');
            var endDate = dateRange.endDate.format('YYYY-MM-DD HH:mm:ss');
            var drug_req_type = $('#drug_req_type').val();
            var prescription_type = $('#prescription_type').val();
            var treatment_code = $('#treatment_code').val();
            
            // Tạo URL với các tham số query
            var href = '{{ route("reports-administrator.export-ndp-data") }}?' + $.param({
                'date_from': startDate,
                'date_to': endDate,
                'drug_req_type': drug_req_type,
                'prescription_type': prescription_type,
                'treatment_code': treatment_code
            });
            
            // Chuyển hướng tới URL với các tham số
            window.location.href = href;
        });
    });
</script>
@endpush