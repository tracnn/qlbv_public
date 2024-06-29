@extends('adminlte::page')

@section('title', 'Quản lý xếp hàng')

@section('content_header')
<h1>
    Quản lý
    <small>Xếp hàng</small>
</h1>
{{ Breadcrumbs::render('queue.manage') }}
@stop

@section('content')
<!-- Messages -->
@include('includes.message')

<div class="panel panel-default table-responsive">
    <div class="panel-body">
        <table class="table table-hover responsive" width="100%">
        <thead>
            <tr>
                <th>#</th>
                <th>Ngày tạo</th>
                <th>Khoa phòng</th>
                <th>Số điện thoại</th>
                <th>Số thứ tự</th>
                <th>Gửi SMS</th>
                <th>Thao Tác</th>
            </tr>
        </thead>
        <tbody>
            @foreach($queueNumbers as $queueNumber)
                <tr>
                    <td>{{ $queueNumber->id }}</td>
                    <td>{{ date("d/m/Y H:i",strtotime($queueNumber->created_at)) }}</td>
                    <td>{{ $queueNumber->department_code }}</td>
                    <td>{{ $queueNumber->phone_number }}</td>
                    <td>{{ $queueNumber->number }}</td>
                    <td>@if($queueNumber->is_sended_sms)
                            <i class="fa fa-check-square" aria-hidden="true"></i>
                        @else
                            <i class="fa fa-window-close" aria-hidden="true"></i>
                        @endif
                    </td>
                    <td>
                        <a href="" class="btn btn-sm btn-success"><span class="glyphicon glyphicon-send"></span> SMS</a>
                        <a href="" class="btn btn-sm btn-danger"><span class="glyphicon glyphicon-remove"></span> Xóa</a>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
    {{ $queueNumbers->links() }}
    </div>
</div>
@stop