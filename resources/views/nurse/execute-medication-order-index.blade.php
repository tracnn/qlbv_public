@extends('adminlte::page')

@section('title', __('insurance.backend.labels.list'))

@section('content_header')
<h1>
    Điều dưỡng
    <small>Thực hiện y lệnh</small>
</h1>
@stop

@section('content')
<div class="panel panel-default">
    <div class="panel-body table-responsive">
        <table id="ksk-index" class="table display table-hover responsive nowrap datatable dtr-inline" width="100%">
            <thead>
                <th>STT</th>
                <th>Mã điều trị</th>
                <th>Mã bệnh nhân</th>
                <th>Họ và tên</th>
                <th>Ngày sinh</th>
                <th>Giới tính</th>
                <th>Số thẻ BHYT</th>
                <th>Ngày vào</th>
                <th>Ngày nhập viện</th>
                <th>Giường</th>
                <th>Số điện thoại</th>
            </thead>
        </table>
    </div>
</div>
@stop

@push('after-scripts')

@endpush