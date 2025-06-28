@extends('adminlte::page')

@section('title', 'QL hồ sơ chuyển BHXH')

@section('content_header')
  <h1>
    QL hồ sơ
    <small>chuyển BHXH</small>
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
@include('emr-checker.partials.search')
<button id="delete-selected" class="btn-sm btn-danger" disabled>Xóa hồ sơ đã chọn</button>
<div class="panel panel-default">
    <div class="panel-body table-responsive">
        <table id="list" class="table display table-hover responsive nowrap datatable dtr-inline" width="100%">
            <thead>
                <tr>
                    <th>Mã ĐT</th>
                    <th><input type="checkbox" id="select-all"></th>
                    <th>Mã BN</th>
                    <th>Họ tên</th>
                    <th>Ngày sinh</th>
                    <th>Số ĐT</th>
                    <th>Khoa ĐT</th>
                    <th>Loại ra viện</th>
                    <th>Ngày vào</th>
                    <th>Ngày ra</th>
                    <th>Ngày t.toán</th>
                    <th>Ngày tạo</th>
                    <th>Ngày cập nhật</th>
                    <th>Ngày hết hạn</th>
                    <th>Action</th>
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
        
        table = $('#list').DataTable({
            "processing": true,
            "serverSide": true,
            "destroy": true,
            "responsive": true,
            "scrollX": true,
            "ajax": {
                url: '{{ route('emr-checker.emr-checker-bhxh-list') }}',
                data: function(d) {
                    d.date_from = startDate;
                    d.date_to = endDate;
                    d.treatment_code = $('#treatment_code').val();
                    d.date_type = $('#date_type').val();
                    d.department_catalog = $('#department_catalog').val();
                    d.patient_type = $('#patient_type').val();
                    d.treatment_type = $('#treatment_type').val();
                    d.treatment_end_type = $('#treatment_end_type').val();
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
            "lengthMenu": [
                [10, 25, 50, 100, 200, 500, 1000, 2000],
                [10, 25, 50, 100, 200, 500, 1000, 2000]
            ],
            "columns": [
                { "data": "treatment_code" },
                {
                    "data": null,
                    "orderable": false,
                    "searchable": false,
                    "render": function (data, type, row) {
                        return `<input type="checkbox" class="select-row" value="${row.treatment_code}">`;
                    }
                },
                { "data": "patient_code" },
                { "data": "patient_name" },
                { "data": "patient_dob" },
                { "data": "hein_card_number" },
                { "data": "last_department_name" },
                { "data": "treatment_end_type_name" },
                { "data": "in_time" },
                { "data": "out_time" },
                { "data": "fee_lock_time" },
                { "data": "created_at" },
                { "data": "updated_at" },
                { "data": "allow_view_at" },
                { "data": "action", "orderable": false, "searchable": false },
            ],
        });
    }

    $(document).on('change', '#select-all', function () {
        var isChecked = $(this).is(':checked');
        $('.select-row').prop('checked', isChecked);
    });

    $(document).on('change', '.select-row, #select-all', function () {
        var anyChecked = $('.select-row:checked').length > 0;
        $('#delete-selected').prop('disabled', !anyChecked);
    });

    $(document).on('click', '#delete-selected', function () {
        var selectedIds = $('.select-row:checked').map(function () {
            return $(this).val();
        }).get();

        if (selectedIds.length === 0) {
            alert('Vui lòng chọn ít nhất một dòng để xóa.');
            return;
        }

        if (!confirm('Bạn có chắc chắn muốn xóa các bản ghi đã chọn không?')) {
            return;
        }

        $.ajax({
            url: '{{ route('emr-checker.emr-checker-bhxh-delete-multiple') }}',
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                ids: selectedIds
            },
            success: function (response) {
                alert('Xóa thành công!');
                table.ajax.reload();
                $('#select-all').prop('checked', false);
                $('#delete-selected').prop('disabled', true);
            },
            error: function (xhr) {
                alert('Có lỗi xảy ra khi xóa.');
            }
        });
    });    
    
    $(document).ready(function() {
        $('.select2').select2({
            width: '100%' // Đặt chiều rộng của Select2 là 100%
        });
    });
</script>
@endpush