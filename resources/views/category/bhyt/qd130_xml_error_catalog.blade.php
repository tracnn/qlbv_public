@extends('adminlte::page')

@section('title', 'Danh mục lỗi Xml 4750')

@section('content_header')
  <h1>
    Danh mục
    <small>Lỗi Xml 4750</small>
  </h1>
@stop

@section('content')
<!-- Messages -->
@include('includes.message')
<!-- /Messages -->

<div class="panel panel-default">
    <div class="panel-body table-responsive">
        <table id="qd130-xml-error-catalog-list" class="table display table-hover responsive nowrap datatable dtr-inline" width="100%">
            <thead>
                <tr>
                    <th>XML</th>
                    <th>Mã lỗi</th>
                    <th>Tên lỗi</th>
                    <th>Mô tả</th>
                    <th>Nghiêm trọng</th>
                    <th>Có kiểm tra</th>
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

        table = $('#qd130-xml-error-catalog-list').DataTable({
            "processing": true,
            "serverSide": true,
            "destroy": true, // Destroy any existing DataTable before reinitializing
            "responsive": true, // Giữ responsive
            "scrollX": true, // Đảm bảo cuộn ngang khi bảng quá rộng
            "ajax": {
                url: "{{ route('category-bhyt.fetch-qd130-xml-error-catalog-datatable') }}",
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
                { "data": "xml" },
                { "data": "error_code" },
                { "data": "error_name" },
                { "data": "description" },
                { "data": "critical_error"
                },
                { 
                    "data": "is_check"
                },
            ],
        });

        table.ajax.reload();
    }

    $(document).ready(function() {
        fetchData();

        // Sự kiện thay đổi checkbox cho is_check
        $('#qd130-xml-error-catalog-list').on('change', '.is-check-toggle', function() {
            var isChecked = $(this).is(':checked');
            var catalogId = $(this).data('id');
            var self = $(this);

            // Hỏi người dùng xem có chắc chắn muốn thay đổi không
            Swal.fire({
                title: 'Bạn có chắc chắn?',
                icon: 'warning',
                showCancelButton: true,
            }).then((result) => {
                if (result.isConfirmed) {
                    // Gửi yêu cầu AJAX để cập nhật giá trị is_check
                    $.ajax({
                        url: "{{ route('category-bhyt.update-qd130-xml-error-catalog') }}",
                        method: "POST",
                        data: {
                            _token: "{{ csrf_token() }}",
                            id: catalogId,
                            is_check: isChecked ? 1 : 0
                        },
                        success: function(response) {
                            if (response.success) {
                                toastr.success('Cập nhật thành công.');
                            } else {
                                toastr.error('Có lỗi xảy ra khi cập nhật.');
                            }
                        },
                        error: function(xhr, status, error) {
                            toastr.error('Có lỗi xảy ra khi gửi yêu cầu: ' + error);
                            console.log('Error:', error);
                        }
                    });
                } else {
                    // Nếu người dùng chọn hủy, không thay đổi trạng thái checkbox
                    self.prop('checked', !isChecked);
                }
            });
        });

        // Sự kiện thay đổi checkbox cho critical_error
        $('#qd130-xml-error-catalog-list').on('change', '.critical-error-toggle', function() {
            var isChecked = $(this).is(':checked');
            var catalogId = $(this).data('id');
            var self = $(this);

            // Hỏi người dùng xem có chắc chắn muốn thay đổi không
            Swal.fire({
                title: 'Bạn có chắc chắn?',
                icon: 'warning',
                showCancelButton: true,
            }).then((result) => {
                if (result.isConfirmed) {
                    // Gửi yêu cầu AJAX để cập nhật giá trị critical_error
                    $.ajax({
                        url: "{{ route('category-bhyt.update-qd130-xml-error-catalog') }}",
                        method: "POST",
                        data: {
                            _token: "{{ csrf_token() }}",
                            id: catalogId,
                            critical_error: isChecked ? 1 : 0
                        },
                        success: function(response) {
                            if (response.success) {
                                toastr.success('Cập nhật thành công.');
                            } else {
                                toastr.error('Có lỗi xảy ra khi cập nhật.');
                            }
                        },
                        error: function(xhr, status, error) {
                            toastr.error('Có lỗi xảy ra khi gửi yêu cầu: ' + error);
                            console.log('Error:', error);
                        }
                    });
                } else {
                    // Nếu người dùng chọn hủy, không thay đổi trạng thái checkbox
                    self.prop('checked', !isChecked);
                }
            });
        });
        
    });
</script>
@endpush