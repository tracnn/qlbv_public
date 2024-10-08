@extends('adminlte::page')

@section('title', 'Danh mục Vật tư y tế BHYT')

@section('content_header')
  <h1>
    Danh mục
    <small>Vật tư y tế BHYT</small>
  </h1>
@stop

@section('content')
<!-- Messages -->
@include('includes.message')
<!-- /Messages -->

<div class="panel panel-default">
    <div class="panel-body table-responsive">
        <table id="medical-supply-list" class="table display table-hover responsive nowrap datatable dtr-inline" width="100%">
            <thead>
                <tr>
                    <th>Mã vật tư</th>
                    <th>Tên vật tư</th>
                    <th>Nhóm vật tư</th>
                    <th>Đơn vị tính</th>
                    <th>Đơn giá</th>
                    <th>Nhà thầu</th>
                    <th>TT Thầu</th>
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

        table = $('#medical-supply-list').DataTable({
            "processing": true,
            "serverSide": true,
            "destroy": true, // Destroy any existing DataTable before reinitializing
            "responsive": true, // Giữ responsive
            "scrollX": true, // Đảm bảo cuộn ngang khi bảng quá rộng
            "ajax": {
                url: "{{ route('category-bhyt.fetch-medical-supply-catalog') }}",
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
                { "data": "ma_vat_tu" },
                { "data": "ten_vat_tu" },
                { "data": "nhom_vat_tu" },
                { "data": "don_vi_tinh" },
                { "data": "don_gia" },
                { "data": "nha_thau" },
                { "data": "tt_thau" },
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