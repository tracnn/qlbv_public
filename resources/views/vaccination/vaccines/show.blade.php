@extends('adminlte::page')

@section('title', __('insurance.backend.labels.list'))

@section('content_header')
<h1>
    Vaccination
    <small>Thông tin tiêm chủng</small>
</h1>
{{ Breadcrumbs::render('vaccination.index') }}
@stop

@section('content')

<div class="panel panel-default">
    <h1>Thông Tin Vắc Xin</h1>
    <div class="card">
        <div class="card-body">
            <h5 class="card-title">{{ $vaccine->name }}</h5>
            <p class="card-text"><strong>Mã Vắc Xin:</strong> {{ $vaccine->code }}</p>
            <p class="card-text"><strong>Nhà Sản Xuất:</strong> {{ $vaccine->manufacturer }}</p>
            <p class="card-text"><strong>Độ Tuổi Khuyến Cáo:</strong> {{ $vaccine->recommended_age }}</p>
        </div>
    </div>
</div>

@stop
