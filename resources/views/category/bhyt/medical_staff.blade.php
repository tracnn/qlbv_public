@extends('adminlte::page')

@section('title', 'Danh mục Nhân viên y tế BHYT')

@section('content_header')
  <h1>
    Danh mục
    <small>Nhân viên y tế BHYT</small>
  </h1>
@stop

@section('content')
<!-- Messages -->
@include('includes.message')
<!-- /Messages -->

<div class="panel panel-default">
    <div class="panel-body table-responsive">
        <table id="medical-staff-list" class="table display table-hover responsive nowrap datatable dtr-inline" width="100%">
            <thead>
                <tr>
                    <th>Mã BHXH</th>
                    <th>Họ và tên</th>
                    <th>Mã khoa</th>
                    <th>Tên khoa</th>
                    <th>Mã CCHN</th>
                    <th>Ngày cấp CCHN</th>
                    <th>Nơi cấp CCHN</th>
                    <th>Thời gian ĐK</th>
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

        table = $('#medical-staff-list').DataTable({
            "processing": true,
            "serverSide": true,
            "destroy": true, // Destroy any existing DataTable before reinitializing
            "responsive": true, // Giữ responsive
            "scrollX": true, // Đảm bảo cuộn ngang khi bảng quá rộng
            "ajax": {
                url: "{{ route('category-bhyt.fetch-medical-staff') }}",
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
                { "data": "ma_bhxh" },
                { "data": "ho_ten" },
                { "data": "ma_khoa" },
                { "data": "ten_khoa" },
                { "data": "macchn" },
                { "data": "ngaycap_cchn" },
                { "data": "noicap_cchn" },
                { "data": "thoigian_dk" },
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