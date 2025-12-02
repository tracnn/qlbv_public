@extends('adminlte::page')

@section('title', 'Bác sĩ y lệnh')

@section('content_header')
  <h1>
    Báo cáo
    <small>Bác sĩ y lệnh</small>
  </h1>
{{ Breadcrumbs::render('bhyt.index') }}
@stop

@section('content')
<!-- Messages -->
@include('includes.message')
<!-- /Messages -->
@include('bhyt.reports.partials.search-report-bac-si-y-lenh')

<div class="panel panel-default">
    <div class="panel-body table-responsive">
        <table id="bac-si-y-lenh-list" class="table display table-hover responsive nowrap datatable dtr-inline" width="100%">
            <thead>
                <tr>
                    <th>Mã Khoa</th>
                    <th>Tên Khoa</th>
                    <th>Mã Bác sĩ</th>
                    <th>Tên Bác sĩ</th>
                    <th>Số lượng</th>
                </tr>
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

        table = $('#bac-si-y-lenh-list').DataTable({
            "processing": true,
            "serverSide": true,
            "destroy": true, // Destroy any existing DataTable before reinitializing
            "responsive": true, // Giữ responsive
            "scrollX": true, // Đảm bảo cuộn ngang khi bảng quá rộng
            "ajax": {
                url: "{{ route('bhyt.fetch-bac-si-y-lenh') }}",
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
                { "data": "ma_khoa" },
                { "data": "ten_khoa" },
                { "data": "ma_bac_si" },
                { "data": "ten_bac_si" },
                { "data": "so_luong" },
            ],
        });

        table.ajax.reload();
    }


    $(document).ready(function() {
        $('.select2').select2({
            width: '100%' // Đặt chiều rộng của Select2 là 100%
        });
        fetchData();
    });
</script>
@endpush