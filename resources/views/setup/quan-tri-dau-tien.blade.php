@extends('adminlte::page')

@section('title', 'Khởi tạo quản trị viên')

@section('content_header')
    <h1>Khởi tạo quản trị viên đầu tiên</h1>
@stop

@section('content')
<div class="row">
    <div class="col-md-8">
        <div class="box box-warning">
            <div class="box-header with-border">
                <h3 class="box-title">Hệ thống chưa có quản trị viên</h3>
            </div>

            <div class="box-body">
                <p>
                    Chưa tài khoản nào được cấp quyền quản trị cao nhất
                    (<code>superadministrator</code>). Nếu bạn là người phụ trách
                    cài đặt, hãy khởi tạo ngay bây giờ.
                </p>

                <p>
                    Tài khoản sẽ được cấp quyền:
                    <strong>{{ $nguoiDung->loginname }}</strong>
                </p>

                <div class="callout callout-danger">
                    <h4>Bước này chỉ dành cho lúc cài đặt</h4>
                    <p>
                        Sau khi xác nhận, màn hình này đóng lại chừng nào hệ thống
                        còn quản trị viên. Việc cấp quyền cho người khác về sau
                        phải làm qua mục <em>Quản lý người dùng</em>.
                    </p>
                    <p>
                        Nếu về sau chạy <code>php artisan db:seed</code>, bảng
                        <code>role_user</code> bị xoá trắng và màn hình này mở lại
                        cho mọi người đăng nhập. Hãy chạy seeder <strong>trước</strong>
                        bước này, không chạy sau.
                    </p>
                </div>
            </div>

            <div class="box-footer">
                <form method="POST" action="{{ route('setup.quan-tri-dau-tien.gan') }}">
                    {{ csrf_field() }}
                    <button type="submit" class="btn btn-warning">
                        Cấp quyền quản trị cho tài khoản này
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@stop
