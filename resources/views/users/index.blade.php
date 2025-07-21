@extends('adminlte::page')

@section('title', 'Quản lý người dùng')

@section('content_header')
@stop

@section('content')

@include('includes.message')

<div class="panel panel-default">
    <div class="panel-body table-responsive">
        <table id="users-index" class="table display table-hover responsive nowrap datatable dtr-inline" width="100%">
            <thead>
                <tr>
                	<th>ID</th>
                    <th>Tên đăng nhập</th>
                    <th>Họ và tên</th>
                    <th>Email</th>
                    <th>Mobile</th>
                    <th>Kích hoạt</th>
                    <th>Action</th>
                </tr>
            </thead>
        </table>
    </div>
</div>

<!-- Edit Permissions Modal -->
<div class="modal fade" id="editPermissionsModal" tabindex="-1" role="dialog" aria-labelledby="editPermissionsModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <form id="permissionsForm" action="">
      	<meta name="csrf-token" content="{{ csrf_token() }}">
        <div class="modal-header">
          <label class="modal-title" id="editPermissionsModalLabel">Chỉnh Sửa Quyền</label>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <!-- Các input cho quyền sẽ được tải ở đây -->
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
          <button type="submit" class="btn btn-primary save-changes">Lưu Thay Đổi</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Edit Roles Modal -->
<div class="modal fade" id="editRolesModal" tabindex="-1" role="dialog" aria-labelledby="editRolesModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <form id="rolesForm" action="">
      	<meta name="csrf-token" content="{{ csrf_token() }}">
        <div class="modal-header">
          <label class="modal-title" id="editRolesModalLabel">Chỉnh Sửa Vai trò</label>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <!-- Các input cho quyền sẽ được tải ở đây -->
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
          <button type="submit" class="btn btn-primary save-changes">Lưu Thay Đổi</button>
        </div>
      </form>
    </div>
  </div>
</div>

@stop

@push('after-scripts')
<script type="text/javascript">
	$(document).ready( function () {

		$('#users-index').DataTable({
      "processing": true,
      "serverSide": true,
      "responsive": true, // Giữ responsive
      "scrollX": true, // Đảm bảo cuộn ngang khi bảng quá rộng
      "ajax": {
          url: "{{ route('users.get-users') }}",
      },
      "columns": [
      	{ "data": "id", "name": "id" },
        { "data": "loginname", "name": "loginname" },
        { "data": "username", "name": "username"},
        { "data": "email", "name": "email"},
        { "data": "mobile", "name": "mobile"},
        { "data": "is_active", "name": "is_active"},
        { "data": "action", "name": "action"},
      ],
    });

		$(document).on('click', '.edit-permissions', function() {
			var updateUrl = $(this).data('update-permission-url'); // Lấy URL cập nhật quyền
    		$('#permissionsForm').attr('action', updateUrl); // Cập nhật thuộc tính action của form
		    var url = $(this).data('permission-url'); // Lấy URL từ thuộc tính data-url của nút được nhấn
		    $.ajax({
		        url: url,
		        method: 'GET',
		        success: function(response) {
		            $('#editPermissionsModal .modal-body').html(response.html); // Chèn form vào modal
		            $('#editPermissionsModal').modal('show'); // Hiển thị modal
		        }
		    });
		});

		$(document).on('click', '.edit-roles', function() {
			var updateUrl = $(this).data('update-role-url'); // Lấy URL cập nhật quyền
    		$('#rolesForm').attr('action', updateUrl); // Cập nhật thuộc tính action của form
		    var url = $(this).data('role-url'); // Lấy URL từ thuộc tính data-url của nút được nhấn
		    $.ajax({
		        url: url,
		        method: 'GET',
		        success: function(response) {
		            $('#editRolesModal .modal-body').html(response.html); // Chèn form vào modal
		            $('#editRolesModal').modal('show'); // Hiển thị modal
		        }
		    });
		});


		$('#permissionsForm').submit(function (e) {
		    e.preventDefault(); // Ngăn form submit theo cách truyền thống
		    var formData = $(this).serialize(); // Lấy dữ liệu từ form
		    var url = $(this).attr('action'); // URL để gửi dữ liệu, đặt trong thuộc tính 'action' của form
		    // Thực hiện AJAX request
		    $.ajax({
		    	headers: {
			        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
			    },
		        url: url,
		        type: 'POST',
		        data: formData,
		        success: function (response) {
		            $('#editPermissionsModal').modal('hide'); // Ẩn modal sau khi lưu thành công
		            // Tùy chọn: hiển thị thông báo thành công
		            if (response.success) {
			        		toastr.success(response.message);
			        	} else {
			        		toastr.error(response.message);
			        	}
		            // Refresh DataTable hoặc cập nhật UI nếu cần
		        },
		        error: function (xhr, status, error) {
		            // Xử lý lỗi ở đây nếu có
		        }
		    });
		});

		$('#rolesForm').submit(function (e) {
		    e.preventDefault(); // Ngăn form submit theo cách truyền thống
		    var formData = $(this).serialize(); // Lấy dữ liệu từ form
		    var url = $(this).attr('action'); // URL để gửi dữ liệu, đặt trong thuộc tính 'action' của form
		    // Thực hiện AJAX request
		    $.ajax({
		    	headers: {
			        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
			    },
		        url: url,
		        type: 'POST',
		        data: formData,
		        success: function (response) {
		            $('#editRolesModal').modal('hide'); // Ẩn modal sau khi lưu thành công
		            // Tùy chọn: hiển thị thông báo thành công
		            if (response.success) {
			        		toastr.success(response.message);
			        	} else {
			        		toastr.error(response.message);
			        	}
		            // Refresh DataTable hoặc cập nhật UI nếu cần
		        },
		        error: function (xhr, status, error) {
		            // Xử lý lỗi ở đây nếu có
		        }
		    });
		});

	})
</script>
@endpush