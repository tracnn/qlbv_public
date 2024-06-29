@extends('adminlte::page')

@section('title', __('insurance.backend.labels.list'))

@section('content_header')
<h1>
    Vaccination
    <small>Danh sách Vaccines</small>
</h1>
{{ Breadcrumbs::render('vaccination.index') }}
@stop

@section('content')

<div class="panel panel-default">
<!--     <div class="panel-heading text-right">
        <a href="{{ route('vaccines.create') }}" class="btn btn-sm btn-primary add-modal">
                                        <span class="glyphicon glyphicon-plus"></span> Thêm mới</a>
    </div> -->
    <div class="panel-body table-responsive">
        <table id="vaccination-index" class="table display table-hover responsive nowrap datatable dtr-inline" width="100%">
            <thead>
                <tr>
                    <th>STT</th>
                    <th>Mã Vắc Xin</th>
                    <th>Tên Vắc Xin</th>
                    <th>Nhà Sản Xuất</th>
                    <th>Độ Tuổi Khuyến Cáo</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($vaccines as $vaccine)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $vaccine->code }}</td>
                    <td>{{ $vaccine->name }}</td>
                    <td>{{ $vaccine->manufacturer }}</td>
                    <td>{{ $vaccine->recommended_age }}</td>
                    <td>
                        <a href="{{ route('vaccines.show', $vaccine->id) }}" class="btn btn-info btn-sm">Xem</a>
                        <a href="{{ route('vaccines.edit', $vaccine->id) }}" class="btn btn-primary btn-sm">Chỉnh sửa</a>
                        <!-- <form action="{{ route('vaccines.destroy', $vaccine->id) }}" method="POST" style="display: inline-block;">
                            {{ csrf_field() }}
                            {{ method_field('DELETE') }}
                            <button type="submit" class="btn btn-danger" onclick="return confirm('Bạn có chắc muốn xóa?')">Xóa</button>
                        </form> -->
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@stop

@push('after-scripts')
<script>
    $(document).ready(function() {
        $('#vaccination-index').DataTable({
            "responsive": true,
            "searching": true, // Enable search
            "language": {
                "search": "Tìm kiếm:", // Customize search label
                "paginate": {
                    "first": "Đầu",
                    "last": "Cuối",
                    "next": "Sau",
                    "previous": "Trước"
                }
            }
        });
    });
</script>
@endpush