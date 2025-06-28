@extends('adminlte::page')

@section('title', 'Chi tiết hồ sơ')

@section('content_header')
  <h1>
    Chi tiết
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
    @include('bhxh.partials.search-detail')

    <div class="panel panel-default">
        <div class="panel-heading">
            Chi tiết hồ sơ
        </div>
        <div class="panel-body table-responsive">
            <table id="emr-detail" class="table display table-hover responsive nowrap datatable dtr-inline" width="100%">
                <thead>
                    <tr>
                        <th>STT</th>
                        <th>Tên văn bản</th>
                        <th>Loại văn bản</th>
                        <th>Ngày tạo</th>
                        <th>Hành động</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
@stop