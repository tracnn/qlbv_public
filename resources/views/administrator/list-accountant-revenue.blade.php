@extends('adminlte::page')

@section('title', __('insurance.backend.labels.list'))

@section('content_header')
<h1>
    Report
    <small>Thống kê doanh thu</small>
</h1>
@stop

@section('content')

@include('administrator.partials.search-accountant-revenue')

<div class="panel panel-default">
    <div class="panel-body table-responsive">
        <table id="list-index" class="table display table-hover responsive nowrap datatable dtr-inline" width="100%">
            <thead>
                <tr>
                    <th>Khoa chỉ định</th>
                    <th>Phòng chỉ định</th>
                    <th>Xét nghiệm</th>
                    <th>CĐHA</th>
                    <th>Thuốc</th>
                    <th>Máu</th>
                    <th>Thủ thuật</th>
                    <th>VTYT</th>
                    <th>Nội soi</th>
                    <th>TDCN</th>
                    <th>Siêu âm</th>
                    <th>Phẫu thuật</th>
                    <th>GPB</th>
                    <th>Suất ăn</th>
                    <th>Khác</th>
                    <th>Khám</th>
                    <th>Giường</th>
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
                url: "{{ route('reports-administrator.fetch-accountant-revenue') }}",
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
                { data: 'deptname' },
                { data: 'roomname' },
                { 
                    data: 'xn',
                    createdCell: function(td, cellData, rowData, row, col) {
                        $(td).addClass('text-right');
                    }
                },
                { 
                    data: 'ha',
                    createdCell: function(td, cellData, rowData, row, col) {
                        $(td).addClass('text-right');
                    }
                },
                { 
                    data: 'th',
                    createdCell: function(td, cellData, rowData, row, col) {
                        $(td).addClass('text-right');
                    }
                },
                { 
                    data: 'ma',
                    createdCell: function(td, cellData, rowData, row, col) {
                        $(td).addClass('text-right');
                    } 
                },
                { 
                    data: 'tt',
                    createdCell: function(td, cellData, rowData, row, col) {
                        $(td).addClass('text-right');
                    }
                },
                { 
                    data: 'vt',
                    createdCell: function(td, cellData, rowData, row, col) {
                        $(td).addClass('text-right');
                    }
                },
                { 
                    data: 'ns',
                    createdCell: function(td, cellData, rowData, row, col) {
                        $(td).addClass('text-right');
                    }
                },
                {
                    data: 'cn', 
                    createdCell: function(td, cellData, rowData, row, col) {
                        $(td).addClass('text-right');
                    }
                },
                {
                    data: 'sa', 
                    createdCell: function(td, cellData, rowData, row, col) {
                        $(td).addClass('text-right');
                    }
                },
                {
                    data: 'pt', 
                    createdCell: function(td, cellData, rowData, row, col) {
                        $(td).addClass('text-right');
                    }
                },
                {
                    data: 'gb', 
                    createdCell: function(td, cellData, rowData, row, col) {
                        $(td).addClass('text-right');
                    }
                },
                {
                    data: 'an', 
                    createdCell: function(td, cellData, rowData, row, col) {
                        $(td).addClass('text-right');
                    }
                },
                {
                    data: 'cl', 
                    createdCell: function(td, cellData, rowData, row, col) {
                        $(td).addClass('text-right');
                    }
                },
                {
                    data: 'kh', 
                    createdCell: function(td, cellData, rowData, row, col) {
                        $(td).addClass('text-right');
                    }
                },
                {
                    data: 'gi', 
                    createdCell: function(td, cellData, rowData, row, col) {
                        $(td).addClass('text-right');
                    }
                }
            ],
        });

        table.ajax.reload();
    }

    // $(document).ready(function() {
    //     $('#export_xlsx').click(function() {
    //         var dateRange = $('#date_range').data('daterangepicker');

    //         var startDate = dateRange.startDate.format('YYYY-MM-DD HH:mm:ss');
    //         var endDate = dateRange.endDate.format('YYYY-MM-DD HH:mm:ss');
    //         var date_type = $('#date_type').val();
            
    //         // Tạo URL với các tham số query
    //         var href = '{{ route("reports-administrator.export-debt-data") }}?' + $.param({
    //             'date_from': startDate,
    //             'date_to': endDate,
    //             'date_type': date_type
    //         });
            
    //         // Chuyển hướng tới URL với các tham số
    //         window.location.href = href;
    //     });
    // });
</script>
@endpush