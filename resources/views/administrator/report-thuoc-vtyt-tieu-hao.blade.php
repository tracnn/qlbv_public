@extends('adminlte::page')

@section('title', __('insurance.backend.labels.list'))

@section('content_header')
<h1>
    Report
    <small>Báo cáo thuốc, vtyt tiêu hao</small>
</h1>
@stop

@section('content')

@include('administrator.partials.search-thuoc-vtyt-tieu-hao')

<div class="panel panel-default">
    <div class="panel-body table-responsive">
        <table id="thuoc-vtyt-tieu-hao-index" class="table display table-hover responsive nowrap datatable dtr-inline" width="100%">
            <thead>
                <tr>
                    <th>Khoa</th>
                    <th>Đối Tượng</th>
                    <th>Loại</th>
                    <th>Tên</th>
                    <th>ĐVT</th>
                    <th>Đã Xuất</th>
                    <th>SL Đề Nghị</th>
                    <th>SL Xuất</th>
                    <th>SL Thu Hổi</th>
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

        var table = $('#thuoc-vtyt-tieu-hao-index').DataTable({
            "processing": true,
            "serverSide": true,
            "destroy": true, // Destroy any existing DataTable before reinitializing
            "responsive": true, // Giữ responsive
            "scrollX": true, // Đảm bảo cuộn ngang khi bảng quá rộng
            "ajax": {
                url: "{{ route('reports-administrator.fetch-thuoc-vtyt-tieu-hao') }}",
                data: function(d) {
                    d.date_from = startDate;
                    d.date_to = endDate;
                    d.date_type = $('#date_type').val();
                    d.department_catalog = $('#department_catalog').val();
                    d.patient_type = $('#patient_type').val();
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
                { data: 'department_name' },
                { data: 'patient_type_name' },
                { data: 'service_type_name' },
                { data: 'service_name' },
                { data: 'service_unit_name' },
                { data: 'is_export' },
                { data: 'total_export_amount', className: 'text-right' },
                { data: 'total_sere_serv_amount', className: 'text-right' },
                { data: 'total_th_amount', className: 'text-right' },
            ],
        });

        table.ajax.reload();
    }

    $(document).ready(function() {
        $('.select2').select2({
            width: '100%' // Đặt chiều rộng của Select2 là 100%
        });
        
        $('#export_xlsx').click(function() {
            var dateRange = $('#date_range').data('daterangepicker');

            var startDate = dateRange.startDate.format('YYYY-MM-DD HH:mm:ss');
            var endDate = dateRange.endDate.format('YYYY-MM-DD HH:mm:ss');
            var date_type = $('#date_type').val();
            var department_catalog = $('#department_catalog').val();
            var patient_type = $('#patient_type').val();
            
            // Tạo URL với các tham số query
            var href = '{{ route("reports-administrator.export-thuoc-vtyt-tieu-hao-data") }}?' + $.param({
                'date_from': startDate,
                'date_to': endDate,
                'date_type': date_type,
                'department_catalog': department_catalog,
                'patient_type': patient_type
            });
            
            // Chuyển hướng tới URL với các tham số
            window.location.href = href;
        });
    });
</script>
@endpush