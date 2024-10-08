@extends('adminlte::page')

@section('title', __('insurance.backend.labels.list'))

@section('content_header')
<h1>
    Report
    <small>Báo cáo thu tiền</small>
</h1>
@stop

@section('content')

@include('administrator.partials.search-payment-accountant')

<div class="panel panel-default">
    <div class="panel-body table-responsive">
        <table id="cvcr-index" class="table display table-hover responsive nowrap datatable dtr-inline" width="100%">
            <thead>
                <tr>
                    <th>Mã Điều Trị</th>
                    <th>Mã BN</th>
                    <th>Họ Và Tên</th>
                    <th>Ngày Sinh</th>
                    <th>Mã Giao Dịch</th>
                    <th>Ngày Giao Dịch</th>
                    <th>Số Tiền</th>
                    <th>Kế Toán</th>
                    <th>Loại Thanh Toán</th>
                    <th>Hình Thức Thanh Toán</th>
                    <th>Khoa Điều Trị</th>
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
                url: "{{ route('reports-administrator.fetch-accoutant-payment') }}",
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
                { data: 'tdl_treatment_code' },
                { data: 'tdl_patient_code' },
                { data: 'tdl_patient_name' },
                { data: 'tdl_patient_dob' },
                { data: 'transaction_code' },
                { data: 'transaction_time' },
                { data: 'amount', className: 'text-right' },
                { data: 'cashier_username' },
                { data: 'transaction_type_name' },
                { data: 'pay_form_name' },
                { data: 'department_name' },
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
            var href = '{{ route("reports-administrator.export-accoutant-payment-data") }}?' + $.param({
                'date_from': startDate,
                'date_to': endDate
            });
            
            // Chuyển hướng tới URL với các tham số
            window.location.href = href;
        });
    });
</script>
@endpush