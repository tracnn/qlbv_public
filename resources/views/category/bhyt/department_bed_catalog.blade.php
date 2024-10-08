@extends('adminlte::page')

@section('title', 'Danh mục Khoa phòng giường BHYT')

@section('content_header')
  <h1>
    Danh mục
    <small>Khoa phòng giường BHYT</small>
  </h1>
@stop

@section('content')
<!-- Messages -->
@include('includes.message')
<!-- /Messages -->

<div class="panel panel-default">
    <div class="panel-body table-responsive">
        <table id="department-bed-list" class="table display table-hover responsive nowrap datatable dtr-inline" width="100%">
            <thead>
                <tr>
                    <th>Mã khoa</th>
                    <th>Tên khoa</th>
                    <th>Mã loại KCB</th>
                    <th>Bàn khám</th>
                    <th>Giường PD</th>
                    <th>Giường 2015</th>
                    <th>Giường TK</th>
                    <th>Giường HSTC</th>
                    <th>Giường HSCC</th>
                    <th>Đến ngày</th>
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

    function fetchData() {
        // Kiểm tra và hủy yêu cầu AJAX trước đó (nếu có)
        if (currentAjaxRequest != null) {
            currentAjaxRequest.abort();
        }

        table = $('#department-bed-list').DataTable({
            "processing": true,
            "serverSide": true,
            "destroy": true, // Destroy any existing DataTable before reinitializing
            "responsive": true, // Giữ responsive
            "scrollX": true, // Đảm bảo cuộn ngang khi bảng quá rộng
            "ajax": {
                url: "{{ route('category-bhyt.fetch-department-bed-catalog') }}",
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
                { "data": "ma_loai_kcb" },
                { "data": "ban_kham" },
                { "data": "giuong_pd" },
                { "data": "giuong_2015" },
                { "data": "giuong_tk" },
                { "data": "giuong_hstc" },
                { "data": "giuong_hscc" },
                { "data": "den_ngay" },
            ],
        });

        table.ajax.reload();
    }

    $(document).ready(function() {
        fetchData();
    });
</script>
@endpush