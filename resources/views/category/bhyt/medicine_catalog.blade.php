@extends('adminlte::page')

@section('title', 'Danh mục thuốc BHYT')

@section('content_header')
  <h1>
    Danh mục
    <small>Thuốc BHYT</small>
  </h1>
@stop

@section('content')
<!-- Messages -->
@include('includes.message')
<!-- /Messages -->

<div class="panel panel-default">
    <div class="panel-body table-responsive">
        <table id="medicine-list" class="table display table-hover responsive nowrap datatable dtr-inline" width="100%">
            <thead>
                <tr>
                    <th>Mã thuốc</th>
                    <th>Tên hoạt chất</th>
                    <th>Tên thuốc</th>
                    <th>Đơn vị tính</th>
                    <th>Hàm lượng</th>
                    <th>Đường dùng</th>
                    <th>Số đăng ký</th>
                    <th>TT thầu</th>
                    <th>Đơn giá</th>
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

        table = $('#medicine-list').DataTable({
            "processing": true,
            "serverSide": true,
            "destroy": true, // Destroy any existing DataTable before reinitializing
            "ajax": {
                url: "{{ route('category-bhyt.fetch-medicine-catalog') }}",
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
                { "data": "ma_thuoc" },
                { "data": "ten_hoat_chat" },
                { "data": "ten_thuoc" },
                { "data": "don_vi_tinh" },
                { "data": "ham_luong" },
                { "data": "duong_dung" },
                { "data": "so_dang_ky" },
                { "data": "tt_thau" },
                { "data": "don_gia" },
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