@extends('adminlte::page')

@section('title', 'Thêm mới Vaccine')

@section('content_header')
<h1>
    Vaccination
    <small>Thêm mới vaccine</small>
</h1>
{{ Breadcrumbs::render('vaccination.index') }}
@stop

@section('content')

<div class="box box-info">
    <div class="box-header with-border">
        <h3 class="box-title">Cập Nhật Thông Tin Vắc Xin</h3>
    </div>
    <!-- form start -->
    <form role="form" action="{{ route('vaccines.update', $vaccine->id) }}" method="POST">
        {{ csrf_field() }}
        {{ method_field('PUT') }}
        <div class="box-body">
            <div class="form-group">
                <label for="code">Mã Vắc Xin</label>
                <input type="text" class="form-control" id="code" name="code" value="{{ $vaccine->code }}" required>
            </div>
            <div class="form-group">
                <label for="name">Tên Vắc Xin</label>
                <input type="text" class="form-control" id="name" name="name" value="{{ $vaccine->name }}" required>
            </div>
            <div class="form-group">
                <label for="manufacturer">Nhà Sản Xuất</label>
                <input type="text" class="form-control" id="manufacturer" name="manufacturer" value="{{ $vaccine->manufacturer }}" required>
            </div>
            <div class="form-group">
                <label for="recommended_age">Độ Tuổi Khuyến Cáo</label>
                <input type="text" class="form-control" id="recommended_age" name="recommended_age" value="{{ $vaccine->recommended_age }}" required>
            </div>
        </div>
        <!-- /.box-body -->

        <div class="box-footer">
            <button type="submit" class="btn btn-primary">Cập Nhật</button>
        </div>
    </form>
</div>

@stop
