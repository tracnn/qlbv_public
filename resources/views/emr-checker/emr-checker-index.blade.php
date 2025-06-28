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
@include('emr-checker.partials.search')
<button id="openExpireModal" class="btn btn-primary" style="margin-bottom: 10px;">Chuyển BHXH</button>
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

<div class="modal fade" id="expireModal" tabindex="-1" role="dialog" aria-labelledby="expireModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <label class="modal-title">Chọn ngày hết hạn</label>
                <button type="button" class="btn btn-primary" id="saveExpireBtn">Lưu</button>
            </div>
            <div class="modal-body">
                <input type="datetime-local" id="expireDate" class="form-control">
            </div>
        </div>
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

        table = $('#list').DataTable({
            "processing": true,
            "serverSide": true,
            "destroy": true, // Destroy any existing DataTable before reinitializing
            "responsive": true, // Giữ responsive
            "scrollX": true, // Đảm bảo cuộn ngang khi bảng quá rộng
            "ajax": {
                url: '{{ route('emr-checker.emr-checker-list') }}',
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
                    //populateErrorCodeDropdown(xhr.responseJSON.errorCodes);
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
                { "data": "tdl_patient_code" },
                { "data": "tdl_patient_name" },
                { "data": "tdl_patient_dob" },
                { "data": "phone"},
                { "data": "treatment_type_name", "name": "his_treatment_type.treatment_type_name" },
                { "data": "patient_type_name", "name": "his_patient_type.patient_type_name" },
                { "data": "tdl_hein_card_number" },
                { "data": "last_department", "name": "last_department.department_name"},
                { "data": "in_time" },
                { "data": "out_time" },
                { "data": "fee_lock_time" },
                { "data": "action" },
            ],
        });

        table.ajax.reload();
    }

    $(document).on('change', '#select-all', function () {
        var isChecked = $(this).is(':checked');
        $('.select-row').prop('checked', isChecked);
    });

    $(document).on('click', '#openExpireModal', function () {
        var selected = $('.select-row:checked');
        if (selected.length === 0) {
            toastr.warning('Vui lòng chọn ít nhất một hồ sơ.');
            return;
        }
        $('#expireModal').modal('show');
    });

    $(document).on('click', '#saveExpireBtn', function () {
        var selectedRows = [];
        $('.select-row:checked').each(function () {
            selectedRows.push($(this).val());
        });

        var expireDate = $('#expireDate').val();
        if (!expireDate) {
            toastr.warning('Vui lòng chọn ngày hết hạn.');
            return;
        }

        var currentDateTime = new Date();
        var selectedDateTime = new Date(expireDate);

        if (selectedDateTime <= currentDateTime) {
            toastr.error('Ngày hết hạn phải lớn hơn thời điểm hiện tại.');
            return;
        }
        
        $.ajax({
            url: '{{ route("emr-checker.set-permission") }}',
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({
                _token: '{{ csrf_token() }}',
                treatment_codes: selectedRows,
                expire_date: expireDate
            }),
            success: function (response) {
                if (response.success) {
                    toastr.success(response.message);
                    $('#expireModal').modal('hide');
                } else {
                    toastr.error(response.message);
                }
            },
            error: function (xhr) {
                toastr.error('Đã xảy ra lỗi khi lưu.');
            }
        });
    });

    $(document).ready(function() {
        $('.select2').select2({
            width: '100%' // Đặt chiều rộng của Select2 là 100%
        });

        $('#list tbody').on('dblclick', 'tr', function () {
            $("#loading_center").show();
            let data = table.row(this).data();
            // Remove highlight from any previously highlighted row
            $('#list tbody tr').removeClass('highlight-row');
            // Add highlight to the current row
            $(this).addClass('highlight-row');

            // Tải chi tiết hồ sơ bằng AJAX
            $.ajax({
                url: '{{ route('emr-checker.emr-checker-detail-fetch-data') }}', // Đặt URL cho endpoint bạn muốn gọi
                method: 'GET',
                data: {
                    treatment_code: data.treatment_code // Lấy dữ liệu từ treatment_code
                },
                success: function(response) {
                    $('#infoModal').modal('show');
                    $('#modalContent').html(response);
                },
                error: function(xhr, error, code) {
                    console.log('Error:', error);
                    console.log('Code:', code);
                    console.log('XHR:', xhr);
                },
                complete: function() {
                    // Hide loading spinner
                    $("#loading_center").hide();
                }
            });
        });
    });
</script>
@endpush