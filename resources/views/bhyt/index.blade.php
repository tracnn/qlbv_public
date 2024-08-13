@extends('adminlte::page')

@section('title', 'Danh sách hồ sơ XML')

@section('content_header')
  <h1>
    Danh sách
    <small>hồ sơ XML</small>
  </h1>
{{ Breadcrumbs::render('bhyt.index') }}
@stop

@section('content')
<!-- Messages -->
@include('includes.message')
<!-- /Messages -->
@include('bhyt.partials.search')

<div class="panel panel-default">
    <div class="panel-body table-responsive">
        <table id="xml-list" class="table display table-hover responsive nowrap datatable dtr-inline" width="100%">
            <thead>
                <tr>
                    <th>Mã điều trị</th>
                    <th>Mã BN</th>
                    <th>Họ tên</th>
                    <th>Mã thẻ</th>
                    <th>Năm sinh</th>
                    <th>Ngày vào</th>
                    <th>Ngày ra</th>
                    <th>Ngày t.toán</th>
                    <th>Ngày gửi</th>
                    <th>Ngày sửa</th>
                    <th>Action</th>
                </tr>
            </thead>
        </table>
    </div>
</div>

<!-- Dynamic Error Code Dropdown -->
<!-- <select id="error_code_xml" name="error_code">
    <option value="">Select Error Code</option>
</select> -->

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
            <div class="modal-body" id="modalContent">
                <!-- Nội dung chi tiết sẽ được tải ở đây -->
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

        table = $('#xml-list').DataTable({
            "processing": true,
            "serverSide": true,
            "destroy": true, // Destroy any existing DataTable before reinitializing
            "ajax": {
                url: "{{ route('bhyt.get-xml') }}",
                data: function(d) {
                    d.date_from = startDate;
                    d.date_to = endDate;
                    d.date_type = $('#date_type').val();
                    d.treatment_code = $('#treatment_code').val();
                    d.xml_filter_status = $('#xml_filter_status').val();
                    d.xml_error_catalog = $('#xml_error_catalog').val();
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
            "columns": [
                { "data": "ma_lk" },
                { "data": "ma_bn" },
                { "data": "ho_ten" },
                { "data": "ma_the" },
                { "data": "ngay_sinh" },
                { "data": "ngay_vao" },
                { "data": "ngay_ra" },
                { "data": "ngay_ttoan" },
                { "data": "created_at" },
                { "data": "updated_at" },
                { "data": "action" },
            ],
        });

        table.ajax.reload();
    }

    // function populateErrorCodeDropdown(errorCodes) {
    //     let dropdown = $('#error_code_xml');
    //     dropdown.empty();
    //     dropdown.append('<option value="">Select Error Code</option>');
    //     errorCodes.forEach(function(code) {
    //         dropdown.append('<option value="' + code + '">' + code + '</option>');
    //     });
    // }

    function deleteXML(ma_lk) {
        if (confirm('Chắc chắn xóa?')) {
            $.ajax({
                url: '{{ route('xml.delete', ['ma_lk' => '']) }}/' + ma_lk,
                type: 'DELETE',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    if (response.success) {
                        toastr.success(response.success);
                        $('#xml-list').DataTable().ajax.reload();
                    } else {
                        alert('Có lỗi xảy ra, vui lòng thử lại.');
                    }
                },
                error: function(response) {
                    alert('Có lỗi xảy ra, vui lòng thử lại.');
                }
            });
        }
    }

    $(document).ready(function() {
        $('.select2').select2({
            width: '100%' // Đặt chiều rộng của Select2 là 100%
        });
        $('#xml-list tbody').on('dblclick', 'tr', function () {
            let data = table.row(this).data();
            // Tải chi tiết hồ sơ bằng AJAX
            $.ajax({
                url: '{{ route('bhyt.detailxml', '') }}/' + data.ma_lk,
                type: 'GET',
                success: function(response) {
                    $('#modalContent').html(response);
                    $('#infoModal').modal('show');
                    initializeModalDataTables();
                },
                error: function(xhr, error, code) {
                    console.log('Error:', error);
                    console.log('Code:', code);
                    console.log('XHR:', xhr);
                }
            });
        });
    });

    function initializeModalDataTables() {
        $('#thuocvt').DataTable();
        $('#dvkt').DataTable();
        $('#cls').DataTable();
        $('#dienbien').DataTable();
        $('#checkHeinCard').DataTable();
        $('#xmlErrorChecks').DataTable();
    }
</script>
@endpush