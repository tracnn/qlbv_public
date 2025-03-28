@extends('adminlte::page')

@section('title', __('insurance.backend.labels.list'))

@section('content_header')
<h1>
    Report
    <small>Danh sách nợ viện phí</small>
</h1>
@stop

@section('content')

@include('administrator.partials.search-patient-pt')

<div class="panel panel-default">
    <div class="panel-body table-responsive">
        <table id="list-index" class="table display table-hover responsive nowrap datatable dtr-inline" width="100%">
            <thead>
                <tr>
                    <th>Mã ĐT</th>
                    <th>Họ tên</th>
                    <th>Ngày sinh</th>
                    <th>Địa chỉ</th>
                    <th>Ngày vào</th>
                    <th>Ngày ra</th>
                    <th>Số ĐT</th>
                    <th>Đối tượng</th>
                    <th>Khoa ĐT</th>
                    <th>Tổng chi phí</th>
                    <th>BH t.toán</th>
                    <th>BN t.toán</th>
                    <th>Đã t.toán</th>
                    <th>Tạm ứng</th>
                    <th>Hoàn ứng</th>
                    <th>Chi phí khác</th>
                    <th>Cần t.toán</th>
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
            "responsive": true, // Giữ responsive
            "scrollX": true, // Đảm bảo cuộn ngang khi bảng quá rộng
            "ajax": {
                url: "{{ route('reports-administrator.fetch-accoutant-debt') }}",
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
                { data: 'treatment_code', name: "treatment_code" },
                { data: 'tdl_patient_name', name: "tdl_patient_name" },
                { data: 'tdl_patient_dob', name: "tdl_patient_dob" },
                { data: 'tdl_patient_address', name: "tdl_patient_address" },
                { data: 'in_time', name: "in_time" },
                { data: 'out_time', name: "out_time"},
                { data: 'tdl_patient_phone', name: "tdl_patient_phone" },
                { data: 'patient_type_name', name: "patient_type_name" },
                { data: 'department_name', name: "department_name" },
                {
                    data: 'total_price', 
                    name: "total_price",
                    createdCell: function(td, cellData, rowData, row, col) {
                        $(td).addClass('text-right');
                    }
                },
                {
                    data: 'total_hein_price', 
                    name: "total_hein_price",
                    createdCell: function(td, cellData, rowData, row, col) {
                        $(td).addClass('text-right');
                    }
                },
                {
                    data: 'total_patient_price', 
                    name: "total_patient_price",
                    createdCell: function(td, cellData, rowData, row, col) {
                        $(td).addClass('text-right');
                    }
                },
                {
                    data: 'da_thanh_toan', 
                    name: "da_thanh_toan",
                    createdCell: function(td, cellData, rowData, row, col) {
                        $(td).addClass('text-right');
                    }
                },
                {
                    data: 'tam_ung', 
                    name: "tam_ung",
                    createdCell: function(td, cellData, rowData, row, col) {
                        $(td).addClass('text-right');
                    }
                },
                {
                    data: 'hoan_ung', 
                    name: "hoan_ung",
                    createdCell: function(td, cellData, rowData, row, col) {
                        $(td).addClass('text-right');
                    }
                },
                {
                    data: 'tu_nhap', 
                    name: "tu_nhap",
                    createdCell: function(td, cellData, rowData, row, col) {
                        $(td).addClass('text-right');
                    }
                },
                {
                    data: 'can_thanh_toan', 
                    name: "can_thanh_toan",
                    createdCell: function(td, cellData, rowData, row, col) {
                        $(td).addClass('text-right');
                    }
                }
            ],
        });

        table.ajax.reload();
    }

    $(document).ready(function() {
        $('#export_xlsx').click(function() {
            var dateRange = $('#date_range').data('daterangepicker');

            var startDate = dateRange.startDate.format('YYYY-MM-DD HH:mm:ss');
            var endDate = dateRange.endDate.format('YYYY-MM-DD HH:mm:ss');
            var date_type = $('#date_type').val();
            
            // Tạo URL với các tham số query
            var href = '{{ route("reports-administrator.export-debt-data") }}?' + $.param({
                'date_from': startDate,
                'date_to': endDate,
                'date_type': date_type
            });
            
            // Chuyển hướng tới URL với các tham số
            window.location.href = href;
        });
    });
</script>
@endpush