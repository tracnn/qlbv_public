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

<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title">Nhập Thông Tin Vắc Xin</h3>
    </div>
    <!-- form start -->
    <form role="form" action="{{ route('vaccines.store') }}" method="POST">
        {{ csrf_field() }}
        <div class="box-body">
            <div class="form-group">
                <label for="code">Mã Vắc Xin</label>
                <input type="text" class="form-control" id="code" name="code" placeholder="Nhập mã vắc xin" required>
            </div>
            <div class="form-group">
                <label for="name">Tên Vắc Xin</label>
                <input type="text" class="form-control" id="name" name="name" placeholder="Nhập tên vắc xin" required>
            </div>
            <div class="form-group">
                <label for="manufacturer">Nhà Sản Xuất</label>
                <input type="text" class="form-control" id="manufacturer" name="manufacturer" placeholder="Nhập tên nhà sản xuất" required>
            </div>
            <div class="form-group">
                <label for="recommended_age">Độ Tuổi Khuyến Cáo</label>
                <input type="text" class="form-control" id="recommended_age" name="recommended_age" placeholder="Nhập độ tuổi khuyến cáo" required>
            </div>
        </div>
        <!-- /.box-body -->

        <div class="box-footer">
            <button type="submit" class="btn btn-primary">Thêm Mới</button>
        </div>
    </form>
</div>

@stop
