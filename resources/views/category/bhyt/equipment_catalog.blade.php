@extends('adminlte::page')

@section('title', 'Danh mục Trang thiết bị BHYT')

@section('content_header')
  <h1>
    Danh mục
    <small>Trang thiết bị BHYT</small>
  </h1>
@stop

@section('content')
<!-- Messages -->
@include('includes.message')
<!-- /Messages -->

<div class="panel panel-default">
    <div class="panel-body table-responsive">
        <table id="equipment-list" class="table display table-hover responsive nowrap datatable dtr-inline" width="100%">
            <thead>
                <tr>
                    <th>Ký hiệu</th>
                    <th>Tên thiết bị</th>
                    <th>Công ty sản xuất</th>
                    <th>Nước sản xuất</th>
                    <th>Năm sản xuất</th>
                    <th>Năm sử dụng</th>
                    <th>Mã máy</th>
                    <th>Số lưu hành</th>
                    <th>Từ ngày</th>
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

        table = $('#equipment-list').DataTable({
            "processing": true,
            "serverSide": true,
            "destroy": true, // Destroy any existing DataTable before reinitializing
            "ajax": {
                url: "{{ route('category-bhyt.fetch-equipment-catalog') }}",
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
                { "data": "ky_hieu" },
                { "data": "ten_tb" },
                { "data": "congty_sx" },
                { "data": "nuoc_sx" },
                { "data": "nam_sx" },
                { "data": "nam_sd" },
                { "data": "ma_may" },
                { "data": "so_luu_hanh" },
                { "data": "tu_ngay" },
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