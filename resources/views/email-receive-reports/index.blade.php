@extends('adminlte::page')

@section('title', 'Quản lý Email Nhận Báo Cáo')

@section('content_header')
@stop

@section('content')

@include('includes.message')

<div class="panel panel-default">
    <div class="panel-heading">
        <div class="row">
            <div class="col-lg-6 col-xs-6">
                <h4><i class="fa fa-envelope"></i> Quản lý Email Nhận Báo Cáo</h4>
            </div>
            <div class="col-lg-6 col-xs-6">
                <div class="pull-right">
                    <button class="btn btn-success btn-sm add-email" data-toggle="modal" data-target="#addEmailModal">
                        <i class="fa fa-plus"></i> Thêm mới
                    </button>
                </div>
            </div>
        </div>
    </div>
    <div class="panel-body table-responsive">
        <table id="email-reports-index" class="table display table-hover responsive nowrap datatable dtr-inline" width="100%">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Tên người nhận</th>
                    <th>Email</th>
                    <th>Trạng thái</th>
                    <th>Báo cáo đặc thù</th>
                    <th>Loại báo cáo</th>
                    <th>Ngày tạo</th>
                    <th>Thao tác</th>
                </tr>
            </thead>
        </table>
    </div>
</div>

<!-- Add Email Modal -->
<div class="modal fade" id="addEmailModal" tabindex="-1" role="dialog" aria-labelledby="addEmailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <form id="addEmailForm" action="{{ route('email-receive-reports.store') }}">
                <meta name="csrf-token" content="{{ csrf_token() }}">
                <div class="modal-header">
                    <label class="modal-title" id="addEmailModalLabel">Thêm Email Nhận Báo Cáo</label>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <!-- Form content will be loaded here -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Đóng</button>
                    <button type="submit" class="btn btn-primary">Lưu</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Email Modal -->
<div class="modal fade" id="editEmailModal" tabindex="-1" role="dialog" aria-labelledby="editEmailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <form id="editEmailForm" action="" method="PUT">
                <meta name="csrf-token" content="{{ csrf_token() }}">
                <div class="modal-header">
                    <label class="modal-title" id="editEmailModalLabel">Chỉnh Sửa Email Nhận Báo Cáo</label>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <!-- Form content will be loaded here -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Đóng</button>
                    <button type="submit" class="btn btn-primary">Cập nhật</button>
                </div>
            </form>
        </div>
    </div>
</div>

@stop

@push('after-scripts')
<script type="text/javascript">
    $(document).ready(function() {
        $('#email-reports-index').DataTable({
            "processing": true,
            "serverSide": true,
            "responsive": true,
            "scrollX": true,
            "ajax": {
                url: "{{ route('email-receive-reports.get-data') }}",
            },
            "columns": [
                { "data": "id", "name": "id" },
                { "data": "name", "name": "name" },
                { "data": "email", "name": "email" },
                { "data": "active", "name": "active" },
                { "data": "period", "name": "period" },
                { "data": "report_types", "name": "report_types" },
                { "data": "created_at", "name": "created_at" },
                { "data": "action", "name": "action" },
            ],
        });

        // Add email modal
        $(document).on('click', '.add-email', function() {
            var url = "{{ route('email-receive-reports.create') }}";
            $.ajax({
                url: url,
                method: 'GET',
                success: function(response) {
                    $('#addEmailModal .modal-body').html(response.html);
                    $('#addEmailModal').modal('show');
                }
            });
        });

        // Edit email modal
        $(document).on('click', '.edit-email', function() {
            var editUrl = $(this).data('edit-url');
            $('#editEmailForm').attr('action', editUrl);
            $.ajax({
                url: editUrl,
                method: 'GET',
                success: function(response) {
                    $('#editEmailModal .modal-body').html(response.html);
                    $('#editEmailModal').modal('show');
                }
            });
        });

        // Add email form submit
        $('#addEmailForm').submit(function(e) {
            e.preventDefault();
            var formData = $(this).serialize();
            var url = $(this).attr('action');
            
            $.ajax({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                url: url,
                type: 'POST',
                data: formData,
                success: function(response) {
                    $('#addEmailModal').modal('hide');
                    if (response.success) {
                        toastr.success(response.message);
                        $('#email-reports-index').DataTable().ajax.reload();
                    } else {
                        toastr.error(response.message);
                    }
                },
                error: function(xhr, status, error) {
                    toastr.error('Có lỗi xảy ra khi thêm email');
                }
            });
        });

        // Edit email form submit
        $('#editEmailForm').submit(function(e) {
            e.preventDefault();
            var formData = $(this).serialize();
            var url = $(this).attr('action');
            
            $.ajax({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                url: url,
                type: 'PUT',
                data: formData,
                success: function(response) {
                    $('#editEmailModal').modal('hide');
                    if (response.success) {
                        toastr.success(response.message);
                        $('#email-reports-index').DataTable().ajax.reload();
                    } else {
                        toastr.error(response.message);
                    }
                },
                error: function(xhr, status, error) {
                    toastr.error('Có lỗi xảy ra: ' + error);
                }
            });
        });

        // Toggle status
        $(document).on('click', '.toggle-status', function(e) {
            e.preventDefault();
            var id = $(this).data('id');
            var url = $(this).data('toggle-url');
            var currentActive = $(this).data('active');
            var action = currentActive == '1' ? 'vô hiệu hóa' : 'kích hoạt';
            
            Swal.fire({
                title: 'Bạn có chắc chắn?',
                text: 'Bạn có chắc chắn muốn ' + action + ' email này?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Có, ' + action + '!',
                cancelButtonText: 'Hủy'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        url: url,
                        type: 'GET',
                        dataType: 'json',
                        success: function(response) {
                            if (response.success) {
                                toastr.success(response.message);
                                $('#email-reports-index').DataTable().ajax.reload();
                            } else {
                                toastr.error(response.message);
                            }
                        },
                        error: function(xhr, status, error) {
                            toastr.error('Có lỗi xảy ra khi thay đổi trạng thái');
                        }
                    });
                }
            });
        });

        // Delete email
        $(document).on('click', '.delete-email', function(e) {
            e.preventDefault();
            var id = $(this).data('id');
            var url = $(this).data('delete-url');
            
            Swal.fire({
                title: 'Bạn có chắc chắn?',
                text: 'Bạn có chắc chắn muốn xóa email này? Hành động này không thể hoàn tác!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Có, xóa!',
                cancelButtonText: 'Hủy'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        url: url,
                        type: 'DELETE',
                        dataType: 'json',
                        success: function(response) {
                            if (response.success) {
                                toastr.success(response.message);
                                $('#email-reports-index').DataTable().ajax.reload();
                            } else {
                                toastr.error(response.message);
                            }
                        },
                        error: function(xhr, status, error) {
                            toastr.error('Có lỗi xảy ra khi xóa email');
                        }
                    });
                }
            });
        });
    });
</script>
@endpush