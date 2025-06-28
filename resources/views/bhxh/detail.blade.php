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

@stop