@extends('adminlte::page')

@section('title', 'Chi tiết hồ sơ')

@section('content_header')
  <h1>
    Chi tiết
    <small>hồ sơ</small>
  </h1>
@stop

@push('after-styles')
<style>
    .modal-body {
        max-height: 500px; /* Set a fixed height for the modal body */
        overflow-y: auto; /* Enable vertical scrolling */
        overflow-x: auto; /* Enable horizontal scrolling */
    }
    .table-responsive {
        display: block;
        width: 100%;
        overflow-x: auto;
        overflow-y: auto;
        -webkit-overflow-scrolling: touch;
    }
    .highlight-row {
        background-color: #f0f8ff !important; /* Light blue background for highlighted row */
    }
</style>
@endpush

@section('content')
    @include('bhxh.partials.search-detail')
    <div class="panel panel-default">
        <div class="panel-heading">
            CĐHA
        </div>
        <div class="panel-body table-responsive">
            <table id="service-cdha" class="table display table-hover responsive wrap datatable dtr-inline" width="100%">
                <thead>
                    <tr>
                        <th>STT</th>
                        <th>Tên dịch vụ</th>
                        <th>Tác vụ</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>

    <div class="panel panel-default">
        <div class="panel-heading">
            Chi tiết hồ sơ
        </div>
        <div class="panel-body table-responsive">
            <table id="emr-detail" class="table display table-hover responsive wrap datatable dtr-inline" width="100%">
                <thead>
                    <tr>
                        <th>STT</th>
                        <th>Tên văn bản</th>
                        <th>Loại văn bản</th>
                        <th>Ngày tạo</th>
                        <th>Hành động</th>
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

        if (table != null) {
            table.ajax.reload(); // Nếu đã khởi tạo thì chỉ cần reload
            return;
        }

        table = $('#emr-detail').DataTable({
            "processing": true,
            "serverSide": true,
            "destroy": true, // Destroy any existing DataTable before reinitializing
            "responsive": true, // Giữ responsive
            "scrollX": true, // Đảm bảo cuộn ngang khi bảng quá rộng
            "ajax": {
                url: '{{ route('bhxh.emr-checker-document-list') }}',
                data: function(d) {
                    d.document_type = $('#document_type').val();
                    d.treatment_code = '{{ $treatment_code }}';
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
                { 
                    "data": null, 
                    "orderable": false, 
                    "searchable": false, 
                    "render": function (data, type, row, meta) {
                        return meta.row + meta.settings._iDisplayStart + 1;
                    }
                },
                { "data": "document_name" }, // Tên văn bản
                { "data": "document_type_name" }, // Loại văn bản
                { "data": "create_date" }, // Ngày tạo
                { "data": "action" }, // Hành động
            ],
        });
    }

    // Gọi khi trang load
    $(document).ready(function () {
        fetchData();

        // Nếu có lọc document_type, bạn có thể thêm:
        $('#document_type').on('change', function () {
            fetchData();
        });

        table = $('#service-cdha').DataTable({
            "processing": true,
            "serverSide": true,
            "destroy": true, // Destroy any existing DataTable before reinitializing
            "responsive": true, // Giữ responsive
            "scrollX": true, // Đảm bảo cuộn ngang khi bảng quá rộng
            "ajax": {
                url: '{{ route('bhxh.service-cdha-list') }}',
                data: function(d) {
                    d.treatment_code = '{{ $treatment_code }}';
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
                { 
                    "data": null, 
                    "orderable": false, 
                    "searchable": false, 
                    "render": function (data, type, row, meta) {
                        return meta.row + meta.settings._iDisplayStart + 1;
                    }
                },
                { "data": "tdl_service_name" },
                { "data": "action" }, // Hành động
            ],
        });
    });
</script>
@endpush