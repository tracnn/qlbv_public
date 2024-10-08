@extends('adminlte::page')

@section('title', 'Danh mục Dịch vụ kỹ thuật BHYT')

@section('content_header')
  <h1>
    Danh mục
    <small>Dịch vụ kỹ thuật BHYT</small>
  </h1>
@stop

@section('content')
<!-- Messages -->
@include('includes.message')
<!-- /Messages -->

<div class="panel panel-default">
    <div class="panel-body table-responsive">
        <table id="service-list" class="table display table-hover responsive nowrap datatable dtr-inline" width="100%">
            <thead>
                <tr>
                    <th>Mã dịch vụ</th>
                    <th>Tên dịch vụ</th>
                    <th>Đơn giá</th>
                    <th>Quy trình</th>
                    <th>CSCKB CGKT</th>
                    <th>CSKCB CLS</th>
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

        table = $('#service-list').DataTable({
            "processing": true,
            "serverSide": true,
            "destroy": true, // Destroy any existing DataTable before reinitializing
            "responsive": true, // Giữ responsive
            "scrollX": true, // Đảm bảo cuộn ngang khi bảng quá rộng
            "ajax": {
                url: "{{ route('category-bhyt.fetch-service-catalog') }}",
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
                { "data": "ma_dich_vu" },
                { "data": "ten_dich_vu" },
                { "data": "don_gia" },
                { "data": "quy_trinh" },
                { "data": "cskcb_cgkt" },
                { "data": "cskcb_cls" },
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