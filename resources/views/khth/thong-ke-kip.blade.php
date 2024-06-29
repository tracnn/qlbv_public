@extends('adminlte::page')

@section('title', 'Kíp thực hiện')

@section('content_header')
  <h1>
    KHTH
    <small>Thống kê Kip thực hiện</small>
  </h1>
@stop

@section('content')

@include('khth.patials.search')

<div class="panel panel-default">
    <div class="panel-heading">
        Danh sách hồ sơ
    </div>
    <div class="panel-body table-responsive">
        <table id="kip-index" class="table display table-hover responsive nowrap datatable dtr-inline" width="100%">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Họ và tên</th>
                    <th>Ngày tiêm</th>
                    <th>Loại vắc xin</th>
                    <th>Người tiêm</th>
                    <th>Hành Động</th>
                </tr>
            </thead>
        </table>
    </div>
</div>

@stop

@push('after-scripts')

@endpush