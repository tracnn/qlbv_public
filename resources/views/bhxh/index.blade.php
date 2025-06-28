@extends('adminlte::page')

@section('title', 'Danh sách hồ sơ')

@section('content_header')
  <h1>
    Danh sách
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
<!-- Messages -->
@include('includes.message')
<!-- /Messages -->
<div class="panel panel-default">
    <div class="panel-body table-responsive">
        <table id="list" class="table display table-hover responsive nowrap datatable dtr-inline" width="100%">
            <thead>
                <tr>
                    <th>Mã ĐT</th>
                    <th>Mã BN</th>
                    <th>Họ tên</th>
                    <th>Ngày sinh</th>
                    <th>Số ĐT</th>
                    <th>Diện</th>
                    <th>Đối tượng</th>
                    <th>Mã thẻ</th>
                    <th>Khoa ĐT</th>
                    <th>Ngày vào</th>
                    <th>Ngày ra</th>
                    <th>Ngày t.toán</th>
                    <th>Action</th>
                </tr>
            </thead>
        </table>
    </div>
</div>

<!-- Modal hiển thị chi tiết -->
<div id="infoModal" class="modal fade" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-xxl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <label>Chi tiết hồ sơ</label>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="modalContent" class="table-responsive">
                    <!-- Nội dung chi tiết sẽ được tải ở đây -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
            </div>
        </div>
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

        // Khởi tạo hoặc reload DataTable
        if (table != null) {
            table.ajax.reload(); // Nếu đã khởi tạo thì chỉ cần reload
            return;
        }

        table = $('#list').DataTable({
            "processing": true,
            "serverSide": true,
            "destroy": true, // Xóa DataTable cũ nếu có
            "responsive": true,
            "scrollX": true,
            "ajax": {
                url: '{{ route('bhxh.emr-checker-list') }}',
                beforeSend: function (xhr) {
                    currentAjaxRequest = xhr; // Gán request hiện tại
                },
                complete: function () {
                    currentAjaxRequest = null; // Reset biến sau khi hoàn tất
                },
                error: function (xhr, error, code) {
                    console.log('Error:', error);
                    console.log('Code:', code);
                    console.log('XHR:', xhr);
                }
            },
            "lengthMenu": [
                [10, 25, 50, 100, 200, 500, 1000, 2000],
                [10, 25, 50, 100, 200, 500, 1000, 2000]
            ],
            "columns": [
                { "data": "treatment_code" },
                { "data": "tdl_patient_code" },
                { "data": "tdl_patient_name" },
                { "data": "tdl_patient_dob" },
                { "data": "phone" },
                { "data": "treatment_type_name", "name": "his_treatment_type.treatment_type_name" },
                { "data": "patient_type_name", "name": "his_patient_type.patient_type_name" },
                { "data": "tdl_hein_card_number" },
                { "data": "last_department", "name": "last_department.department_name" },
                { "data": "in_time" },
                { "data": "out_time" },
                { "data": "fee_lock_time" },
                { "data": "action" },
            ],
        });
    }

    // Tự động load dữ liệu khi trang load lần đầu
    $(document).ready(function () {
        fetchData(); // Có thể thêm tham số nếu muốn lọc theo ngày
    });
</script>
@endpush