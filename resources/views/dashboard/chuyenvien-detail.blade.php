@extends('adminlte::page')

@section('title', 'Thống kê chuyển viện')

@section('content_header')
  <h1>
    KHTH
    <small>Thống kê chuyển viện</small>
  </h1>

@stop

@section('content')
<!-- Messages -->
@include('includes.message')
<!-- /Messages -->

<div class="panel panel-default">
    <div class="panel-body">
        <div class="form-group">
            <b>Tìm kiếm</b>
        </div>
    </div>
</div>

@stop
@push('after-scripts')
<script>
$(document).ready(function() {
    
});

</script>
@endpush